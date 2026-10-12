<?php
/**
 * اسکیمای رویداد با تاریخ واقعی روی وردپرس واقعی — P5-D357 · topersia-schema 1.3.0.
 *
 *   php tests/event-schema-local.php
 *
 *   ۱ داده: متای event_start_date یک پست E1 واقعی (اگر هست) قالب خام Y-m-d دارد
 *   ۲ رویداد آینده (E1 tp_festival) و گذشته (E5 tp_religious_event) در fa · en · ar — موقت، ساختهٔ همین اجرا، پایان پاک‌شده
 *      آینده ← Event/Festival با startDate و endDate، eventStatus و location؛ گذشته ← بی Event (Thing با additionalType)
 *      JSON-LD از صفحهٔ واقعی (HTTP)، نه از تابع
 *
 * فقط topersia.local؛ فقط پست‌هایی که همین اجرا ساخته پاک می‌شوند.
 */
require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST'] = TP_HOST; $_SERVER['SERVER_NAME'] = TP_HOST; $_SERVER['SERVER_PORT'] = 80; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; $_SERVER['REQUEST_METHOD'] = 'GET';
require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$pass = 0; $fail = 0;
function t( $label, $ok, $note = '' ) { global $pass, $fail; if ( $ok ) { $pass++; echo "  ✅ {$label}\n"; } else { $fail++; echo "  ❌ {$label}" . ( '' !== $note ? "   ← {$note}" : '' ) . "\n"; } }

echo 'START ' . gmdate( 'c' ) . "\n";

/* ── ۱ داده ───────────────────────────────────────────────────────── */
do_action( 'wpml_switch_language', 'fa' );
$real = get_posts( array( 'post_type' => 'tp_festival', 'post_status' => 'publish', 'numberposts' => 1, 'meta_key' => 'event_start_date', 'meta_compare' => 'EXISTS', 'suppress_filters' => false ) );
if ( $real ) {
	$raw = get_post_meta( $real[0]->ID, 'event_start_date', true );
	t( 'E1 واقعی: event_start_date رشتهٔ خام Y-m-d است (نه شمارهٔ زمانی)', is_string( $raw ) && 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ), gettype( $raw ) );
} else {
	echo "  ℹ︎ هیچ پست E1 منتشرشدهٔ فارسی با event_start_date در پایگاه محلی نیست — فقط موقت‌ها سنجیده می‌شوند\n";
}

/* ── ۲ رویدادهای موقت ─────────────────────────────────────────────── */
$made = array();
$mk = function ( $type, $lang, $title, $trid, $meta ) use ( &$made ) {
	$id = wp_insert_post( array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_name' => sanitize_title( $title ) ), true );
	if ( is_wp_error( $id ) ) { return array( 0, 0 ); }
	$made[] = $id;
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_' . $type, 'trid' => $trid ?: false, 'language_code' => $lang, 'source_language_code' => $trid ? 'fa' : null ) );
	foreach ( $meta as $k => $v ) { update_post_meta( $id, $k, $v ); }
	$details = apply_filters( 'wpml_element_language_details', null, array( 'element_id' => $id, 'element_type' => $type ) );
	return array( $id, $details ? (int) $details->trid : 0 );
};

$cases = array(
	'future' => array( 'tp_festival', 'Temp Event Future R39', array( 'event_start_date' => '2027-09-01', 'event_end_date' => '2027-09-03' ) ),
	'past'   => array( 'tp_religious_event', 'Temp Event Past R39', array( 'event_start_date' => '2020-03-01', 'event_end_date' => '2020-03-02' ) ),
);
$urls = array();

try {
	foreach ( $cases as $key => $c ) {
		list( $fa, $trid ) = $mk( $c[0], 'fa', $c[1] . ' fa', 0, $c[2] );
		$urls[ $key ]['fa'] = $fa;
		foreach ( array( 'en', 'ar' ) as $lang ) {
			list( $id ) = $mk( $c[0], $lang, $c[1] . ' ' . $lang, $trid, array() );
			$urls[ $key ][ $lang ] = $id;
		}
	}
	clean_post_cache( 0 );

	$fetch = function ( $id ) {
		do_action( 'wpml_switch_language', 'all' );
		$u = get_permalink( $id );
		$r = wp_remote_get( $u, array( 'timeout' => 90, 'sslverify' => false, 'redirection' => 3, 'headers' => array( 'Cookie' => '' ) ) );
		return array( $u, is_wp_error( $r ) ? '' : (string) wp_remote_retrieve_body( $r ), is_wp_error( $r ) ? 0 : (int) wp_remote_retrieve_response_code( $r ) );
	};

	foreach ( $urls as $key => $by_lang ) {
		foreach ( $by_lang as $lang => $id ) {
			list( $u, $html, $code ) = $fetch( $id );
			preg_match_all( '#<script[^>]*application/ld\+json[^>]*>(.*?)</script>#su', $html, $m );
			$nodes = array();
			foreach ( $m[1] as $blob ) {
				$j = json_decode( $blob, true );
				if ( isset( $j['@graph'] ) ) { foreach ( $j['@graph'] as $n ) { $nodes[] = $n; } } elseif ( is_array( $j ) ) { $nodes[] = $j; }
			}
			$main = null;
			foreach ( $nodes as $n ) {
				$ty = isset( $n['@type'] ) ? (array) $n['@type'] : array();
				if ( array_intersect( $ty, array( 'Event', 'Festival', 'SportsEvent', 'Thing' ) ) || ( isset( $n['url'] ) && rtrim( (string) $n['url'], '/' ) === rtrim( $u, '/' ) && ! array_intersect( $ty, array( 'WebPage', 'BreadcrumbList' ) ) ) ) { $main = $n; break; }
			}
			$types = $main && isset( $main['@type'] ) ? (array) $main['@type'] : array();
			$is_event = (bool) array_intersect( $types, array( 'Event', 'Festival', 'SportsEvent' ) );
			if ( 'future' === $key ) {
				t( "E1 آینده · {$lang}: HTTP {$code}؛ گرهٔ Event/Festival با startDate 2027-09-01 و endDate، eventStatus، location",
					200 === $code && $main && $is_event && isset( $main['startDate'] ) && '2027-09-01' === substr( (string) $main['startDate'], 0, 10 ) && isset( $main['endDate'] ) && isset( $main['eventStatus'] ) && isset( $main['location'] ),
					$main ? implode( ',', $types ) . ' ' . ( isset( $main['startDate'] ) ? $main['startDate'] : 'بی startDate' ) : 'گرهٔ اصلی نیست' );
			} else {
				t( "E5 گذشته · {$lang}: HTTP {$code}؛ Event منتشر نمی‌شود (Thing با additionalType)، بی startDate",
					200 === $code && $main && ! $is_event && ! isset( $main['startDate'] ),
					$main ? implode( ',', $types ) : 'گرهٔ اصلی نیست' );
			}
		}
	}
} finally {
	foreach ( $made as $id ) { wp_delete_post( $id, true ); }
	$left = 0;
	foreach ( $made as $id ) { if ( get_post( $id ) ) { $left++; } }
	t( 'پاک‌سازی: همهٔ ' . count( $made ) . ' پستِ موقتِ همین اجرا پاک شد', 0 === $left, "مانده: {$left}" );
}

echo "\nEND " . gmdate( 'c' ) . " — گذشته: {$pass}   افتاده: {$fail}\n";
exit( $fail ? 1 : 0 );
