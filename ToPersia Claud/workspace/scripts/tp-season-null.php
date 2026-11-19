<?php
/**
 * دستۀ 55 بند ۸ (P5-D633) — «بهترین فصل» پیش‌فرض «بهار» ← بی‌مقدار. فقط topersia.local.
 *
 *   php wp-cli.phar … eval-file scripts/tp-season-null.php                 ← گزارش (فقط خواندن)
 *   php wp-cli.phar … eval-file scripts/tp-season-null.php apply <out.json> ← نخست فهرست و مقدار پیشین در out.json، سپس حذف متا
 *   php wp-cli.phar … eval-file scripts/tp-season-null.php restore <in.json> ← برگرداندن عیناً همان مقدارها
 *
 * «پیش‌فرض بهار» = مقدار تک‌گزینه‌ای `spring`، یا آرایۀ چندگزینه‌ای (کشور) که فقط spring در آن روشن است.
 * فیلد select اجباری JetEngine بی گزینۀ خالی است و ذخیرۀ پست بی انتخاب «بهار» می‌نوشت — پس بهار هیچ‌جا گواه نیست.
 * پست نمایشی (`_tp_demo`، tp-demo-seed.php فصل گردان) بیرون است. بی‌مقدار = حذف متا (topersia_g_raw برای نبود همان ''
 * را می‌دهد). هر زبان جدا (fa · en · ar)؛ بی save_post، پس WPML فیلد copy را از اصل دوباره نمی‌نشاند. پس از نوشتن،
 * کش قطعه باطل می‌شود. خروجی: ۰ سالم · ۱ ناسازگاری (پس از نوشتن هنوز بهار مانده، یا برگرداندن ناقص) · ۲ محیط/ورودی.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "فقط با wp eval-file\n" );
	exit( 2 );
}
if ( 'topersia.local' !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	WP_CLI::error( 'فقط topersia.local' );
}

$tp_args = isset( $args ) ? (array) $args : array();
$tp_mode = isset( $tp_args[0] ) ? (string) $tp_args[0] : 'report';
$tp_file = isset( $tp_args[1] ) ? (string) $tp_args[1] : '';

/** آیا مقدار خام «پیش‌فرض بهار» است. */
function tp_sn_is_spring( $raw ) {
	$v = maybe_unserialize( $raw );
	if ( is_array( $v ) ) {
		/* کشور: فهرست مقدارها (a:2:{i:0;"spring";i:1;"autumn"}) — یا نگاشت گزینه ← true در checkbox */
		$on = array_keys( $v ) === range( 0, count( $v ) - 1 )
			? array_values( array_filter( array_map( 'strval', $v ), 'strlen' ) )
			: array_keys( array_filter( $v, function ( $x ) { return true === $x || 'true' === $x || '1' === $x || 1 === $x; } ) );
		return array( 'spring' ) === array_values( $on );
	}
	return 'spring' === trim( (string) $v );
}

/** همۀ ردیف‌های best_season (هر وضعیت جز زباله) با زبان WPML و نشان نمایشی. */
function tp_sn_rows() {
	global $wpdb;
	$t       = $wpdb->prefix . 'icl_translations';
	$has_icl = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) );
	$sql     = 'SELECT m.meta_id, m.post_id, m.meta_value, p.post_type, p.post_status, ( d.meta_id IS NOT NULL ) demo, ( s.meta_id IS NOT NULL ) sample' . ( $has_icl ? ', i.language_code lang' : ", '' lang" )
		. " FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id"
		. " LEFT JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = '_tp_demo'"
		. " LEFT JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = '_tp_sample'"
		. ( $has_icl ? " LEFT JOIN {$t} i ON i.element_id = p.ID AND i.element_type = CONCAT( 'post_', p.post_type )" : '' )
		. " WHERE m.meta_key = 'best_season' AND p.post_status <> 'trash' ORDER BY p.post_type, m.post_id";
	return (array) $wpdb->get_results( $sql );
}

/** ردیف‌های هدف: پیش‌فرض بهار، نه نمایشی و نه نمونه (هر دو فقط در پیش‌نمایش دیده می‌شوند — P5-D634). */
function tp_sn_targets() {
	return array_values( array_filter( tp_sn_rows(), function ( $r ) { return ! (int) $r->demo && ! (int) $r->sample && tp_sn_is_spring( $r->meta_value ); } ) );
}

if ( 'report' === $tp_mode ) {
	$by   = array();
	$demo = 0;
	$smp  = 0;
	foreach ( tp_sn_rows() as $r ) {
		if ( (int) $r->demo || (int) $r->sample ) {
			$demo += (int) $r->demo;
			$smp  += (int) $r->sample && ! (int) $r->demo ? 1 : 0;
			continue;
		}
		$v = maybe_unserialize( $r->meta_value );
		$k = tp_sn_is_spring( $r->meta_value ) ? 'spring*' : ( is_array( $v ) ? 'array' : ( '' === trim( (string) $v ) ? '(empty)' : (string) $v ) );
		$by[ $k ] = isset( $by[ $k ] ) ? $by[ $k ] + 1 : 1;
	}
	ksort( $by );
	$sp   = tp_sn_targets();
	$lang = array();
	$stat = array();
	foreach ( $sp as $r ) {
		$l          = '' !== (string) $r->lang ? (string) $r->lang : '-';
		$lang[ $l ] = isset( $lang[ $l ] ) ? $lang[ $l ] + 1 : 1;
		$stat[ $r->post_status ] = isset( $stat[ $r->post_status ] ) ? $stat[ $r->post_status ] + 1 : 1;
	}
	WP_CLI::log( 'مقدارها (بی نمایشی و نمونه): ' . wp_json_encode( $by, JSON_UNESCAPED_UNICODE ) . ' · کنار گذاشته: نمایشی ' . $demo . '، نمونه ' . $smp );
	WP_CLI::log( 'پیش‌فرض بهار: ' . count( $sp ) . ' ردیف · زبان ' . wp_json_encode( $lang ) . ' · وضعیت ' . wp_json_encode( $stat ) );
	foreach ( $sp as $r ) {
		WP_CLI::log( sprintf( '  #%d %s %s %s %s', $r->post_id, $r->post_type, '' !== (string) $r->lang ? $r->lang : '-', $r->post_status, is_array( maybe_unserialize( $r->meta_value ) ) ? 'array' : 'single' ) );
	}
	exit( 0 );
}

if ( 'apply' === $tp_mode ) {
	if ( '' === $tp_file || file_exists( $tp_file ) ) {
		WP_CLI::error( 'پروندۀ خروجی تازه لازم است (موجود بازنویسی نمی‌شود)', false );
		exit( 2 );
	}
	$rows = tp_sn_targets();
	$out  = array(
		'what'    => 'best_season پیش‌فرض «بهار» پیش از تهی‌کردن — دستۀ 55 بند ۸ (P5-D633)',
		'site'    => home_url(),
		'created' => gmdate( 'c' ),
		'restore' => 'php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-season-null.php restore <this.json>',
		'rows'    => array_map( function ( $r ) {
			return array( 'post_id' => (int) $r->post_id, 'meta_id' => (int) $r->meta_id, 'type' => $r->post_type, 'lang' => (string) $r->lang, 'status' => $r->post_status, 'meta_value' => (string) $r->meta_value );
		}, $rows ),
	);
	/* ثبت پیش از نوشتن: بی پروندۀ کامل و خوانا، هیچ حذفی */
	if ( false === file_put_contents( $tp_file, wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" ) ) {
		WP_CLI::error( 'نوشتن پروندۀ پشتیبان نشد — هیچ چیز حذف نشد', false );
		exit( 2 );
	}
	$back = json_decode( (string) file_get_contents( $tp_file ), true );
	if ( ! is_array( $back ) || ! isset( $back['rows'] ) || count( $back['rows'] ) !== count( $rows ) ) {
		WP_CLI::error( 'پروندۀ پشتیبان خوانا نیست — هیچ چیز حذف نشد', false );
		exit( 2 );
	}
	foreach ( $rows as $r ) {
		delete_metadata_by_mid( 'post', (int) $r->meta_id );
		clean_post_cache( (int) $r->post_id );
	}
	if ( function_exists( 'topersia_frag_purge' ) ) {
		topersia_frag_purge();
	}
	$left = tp_sn_targets();
	WP_CLI::log( 'تهی شد: ' . count( $rows ) . ' ردیف · پیش‌فرض بهار مانده: ' . count( $left ) . ' · پشتیبان: ' . $tp_file );
	exit( $left ? 1 : 0 );
}

if ( 'restore' === $tp_mode ) {
	$in = is_file( $tp_file ) ? json_decode( (string) file_get_contents( $tp_file ), true ) : null;
	if ( ! is_array( $in ) || empty( $in['rows'] ) ) {
		WP_CLI::error( 'پروندۀ ورودی نامعتبر', false );
		exit( 2 );
	}
	$bad = 0;
	foreach ( $in['rows'] as $r ) {
		$pid = (int) $r['post_id'];
		if ( ! get_post( $pid ) || metadata_exists( 'post', $pid, 'best_season' ) ) {
			$bad++;   /* پست نیست، یا از آن پس مقدار تازه گرفته — بازنویسی نمی‌شود */
			continue;
		}
		add_post_meta( $pid, 'best_season', maybe_unserialize( $r['meta_value'] ), true );
		clean_post_cache( $pid );
	}
	if ( function_exists( 'topersia_frag_purge' ) ) {
		topersia_frag_purge();
	}
	WP_CLI::log( 'برگشت: ' . ( count( $in['rows'] ) - $bad ) . ' · رد (پست نیست یا مقدار تازه دارد): ' . $bad );
	exit( $bad ? 1 : 0 );
}

WP_CLI::error( 'حالت ناشناخته: report · apply <out.json> · restore <in.json>', false );
exit( 2 );
