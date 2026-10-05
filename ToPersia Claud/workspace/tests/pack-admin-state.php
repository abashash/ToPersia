<?php
/**
 * یاور `tests/pack-admin.js` — فقط topersia.local.  ۴٫۱۲٫۰ · فاز ۲ب
 *
 *   php tests/pack-admin-state.php setup <tag>                 شهرستان و شهر فارسی موقت؛ عکس گزینۀ آخرین درون‌ریزی؛ کف صف
 *   php tests/pack-admin-state.php state <tag>                 پست‌های آزمون: وضعیت، زبان، وضعیت محتوا، والد ۷۳، trid
 *   php tests/pack-admin-state.php cleanup <tag> <b64> <floor> پاک‌کردن پست‌ها، ردیف‌های لاگ و کارهای صف آزمون؛ برگرداندن گزینه
 *
 * فقط پست‌هایی که عنوانشان نشان آزمون (<tag>) را دارد — و ترجمۀ WPML همان‌ها
 * (۲ج٫۹؛ عنوانش انگلیسی است) — دست می‌خورند.
 */

require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

$cmd = isset( $argv[1] ) ? $argv[1] : '';
$tag = isset( $argv[2] ) ? (string) $argv[2] : '';

if ( '' === $tag || ! preg_match( '/^[a-z0-9]+$/', $tag ) ) {
	fwrite( STDERR, "نشان آزمون لازم است\n" );
	exit( 2 );
}

global $wpdb;

require_once __DIR__ . '/queue-sweep.php';

function tagged( $tag ) {
	global $wpdb;
	$ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE %s", '%' . $wpdb->esc_like( 'آزمون پیشخوان ' . $tag ) . '%' ) ) );
	/* ترجمه‌های WPML همان پست‌ها — ۲ج٫۹ */
	foreach ( $ids as $id ) {
		$etype = 'post_' . get_post_type( $id );
		$trid  = apply_filters( 'wpml_element_trid', null, $id, $etype );
		foreach ( $trid ? (array) apply_filters( 'wpml_get_element_translations', null, $trid, $etype, false, true ) : array() as $t ) {
			$ids[] = (int) $t->element_id;
		}
	}
	return array_values( array_unique( array_filter( $ids ) ) );
}

if ( 'setup' === $cmd ) {
	$floor = tp_queue_floor();
	$id    = wp_insert_post( array( 'post_type' => 'tp_county', 'post_status' => 'publish', 'post_title' => 'شهرستان آزمون پیشخوان ' . $tag ), true );
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_tp_county', 'trid' => false, 'language_code' => 'fa' ) );
	/* شهر فارسیِ مقصد درون‌ریزی ترجمه (۲ج٫۹) — پیش‌نویس */
	$city = wp_insert_post( array(
		'post_type'   => 'tp_city',
		'post_status' => 'draft',
		'post_title'  => 'شهر ترجمۀ آزمون پیشخوان ' . $tag,
		'post_name'   => 'admin-tr-' . $tag,
		'meta_input'  => array( 'name_fa' => 'شهر ترجمۀ آزمون پیشخوان ' . $tag, 'name_en' => 'Admin Translation ' . $tag, 'short_summary' => 'شهری ساختگی برای آزمون ترجمه.', 'population' => 777 ),
	), true );
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $city, 'element_type' => 'post_tp_city', 'trid' => false, 'language_code' => 'fa' ) );
	wp_update_post( array( 'ID' => $city ) );
	echo wp_json_encode( array( 'county' => (int) $id, 'city' => (int) $city, 'last_run' => base64_encode( serialize( get_option( TOPERSIA_PACK_LAST_RUN, null ) ) ), 'floor' => $floor ) );
	exit( 0 );
}

if ( 'state' === $cmd ) {
	$out = array();
	foreach ( tagged( $tag ) as $id ) {
		$rel   = topersia_relation( 73 );
		$out[] = array(
			'id'      => $id,
			'type'    => get_post_type( $id ),
			'title'   => get_post_field( 'post_title', $id ),
			'status'  => get_post_status( $id ),
			'lang'    => topersia_pack_post_language( $id ),
			'content' => (string) get_post_meta( $id, 'content_status', true ),
			'season'  => (string) get_post_meta( $id, 'best_season', true ),
			'parents' => 'tp_city' === get_post_type( $id ) && $rel ? array_map( 'intval', (array) $rel->get_parents( $id, 'ids' ) ) : array(),
			'trid'    => (int) apply_filters( 'wpml_element_trid', null, $id, 'post_' . get_post_type( $id ) ),
			'slug'    => get_post_field( 'post_name', $id ),
			'pop'     => (string) get_post_meta( $id, 'population', true ),
		);
	}
	echo wp_json_encode( array( 'posts' => $out, 'last_run' => get_option( TOPERSIA_PACK_LAST_RUN, null ) ), JSON_UNESCAPED_UNICODE );
	exit( 0 );
}

if ( 'cleanup' === $cmd ) {
	$last = get_option( TOPERSIA_PACK_LAST_RUN, null );
	$ids  = tagged( $tag );
	/* هر اجرایی که پستی از این‌ها را لمس کرد (اصلی و ترجمه) — ردیف‌های لاگش پایین پاک */
	$runs = array();
	foreach ( $ids as $id ) {
		foreach ( $wpdb->get_col( $wpdb->prepare( 'SELECT context FROM ' . topersia_log_table() . " WHERE channel = 'import' AND context LIKE %s", '%' . $wpdb->esc_like( '"post":' . $id . ',' ) . '%' ) ) as $ctx ) {
			$c = json_decode( (string) $ctx, true );
			if ( is_array( $c ) && ! empty( $c['run'] ) ) {
				$runs[ $c['run'] ] = true;
			}
		}
	}
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
	foreach ( array_keys( $runs ) as $run ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . topersia_log_table() . " WHERE channel = 'import' AND context LIKE %s", '%' . $wpdb->esc_like( '"run":"' . $run . '"' ) . '%' ) );
	}
	tp_queue_sweep( isset( $argv[4] ) ? (int) $argv[4] : -1 );
	/* ردیف‌های لاگ اجراهای همین آزمون — شناسۀ اجرا از گزینۀ آخرین درون‌ریزی، که آزمون ساخته */
	if ( is_array( $last ) && ! empty( $last['run'] ) && isset( $last['file'] ) && false !== strpos( (string) $last['file'], $tag ) ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . topersia_log_table() . " WHERE channel = 'import' AND context LIKE %s", '%' . $wpdb->esc_like( '"run":"' . $last['run'] . '"' ) . '%' ) );
	}
	$prev = isset( $argv[3] ) ? unserialize( base64_decode( $argv[3] ), array( 'allowed_classes' => false ) ) : null;
	if ( null === $prev ) {
		delete_option( TOPERSIA_PACK_LAST_RUN );
	} else {
		update_option( TOPERSIA_PACK_LAST_RUN, $prev, false );
	}
	echo wp_json_encode( array( 'left' => count( tagged( $tag ) ) ) );
	exit( 0 );
}

fwrite( STDERR, "فرمان: setup · state · cleanup\n" );
exit( 2 );
