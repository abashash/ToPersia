<?php
/**
 * نمایهٔ مشتق مکانی و map-points روی وردپرسِ واقعیِ محلی — P5-D334 · P5-D346.
 *
 *   php tests/map-local.php
 *
 * سنجیده می‌شود: ساخت نمایه، پاک‌کردنِ کاملِ جدول و بازسازی ← همان اثرانگشت؛ ردیف گاوخونی (نقطه، استان از زنجیرهٔ R01)؛
 * فقط منتشرشده و زبان پیش‌فرض؛ ردیف تازه برای بنای موقتِ منتشرشده و حذفش با پیش‌نویس‌شدن؛ REST استان و شهر
 * (خانواده‌ها، GeoJSON، نام به زبان صفحه، کش، حذف پیش‌نویس)؛ نوع دیگر ← بی نشانگر؛ زمینهٔ نامعتبر ← ۴۰۰؛ گزارش کیفیت.
 * پست و ردیف رابطهٔ موقت (نشان `_tp_test_marker`) در پایان، حتی با خطا، فقط همان‌ها پاک می‌شوند.
 *
 * کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص
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
	fwrite( STDERR, "فقط نسخهٔ محلی\n" );
	exit( 2 );
}

if ( ! function_exists( 'topersia_geo_index_rebuild' ) || ! function_exists( 'topersia_g_map_points_rest' ) ) {
	fwrite( STDERR, "افزونه‌ها روی نسخهٔ محلی همگام نیست\n" );
	exit( 2 );
}

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function t( $label, $ok, $note = '' ) {
	if ( $ok ) { $GLOBALS['pass']++; echo "  ✅ {$label}\n"; }
	else       { $GLOBALS['fail']++; echo "  ❌ {$label}" . ( '' !== $note ? "   ← {$note}" : '' ) . "\n"; }
}

function mp( $ctx, $lang = null ) {
	if ( $lang ) { do_action( 'wpml_switch_language', $lang ); }
	$req = new WP_REST_Request( 'GET', '/topersia/v1/map-points' );
	$req->set_param( 'context', (string) $ctx );
	$res = rest_do_request( $req );
	if ( $lang ) { do_action( 'wpml_switch_language', 'fa' ); }
	return $res;
}

function ids_of( $res ) {
	$d = $res->get_data();
	return isset( $d['features'] ) ? array_map( function ( $f ) { return (int) $f['properties']['id']; }, $d['features'] ) : array();
}

echo "\nنقشه — نمایهٔ مشتق و map-points (وردپرس واقعی، فقط محلی)\n" . str_repeat( '─', 78 ) . "\n";

global $wpdb;
$table   = topersia_geo_index_table();
$gav     = 3267;
$prov    = 99;
$city    = 399;
$created = array();
$relrows = array();

try {
	/* ── نمایه ── */
	$a = topersia_geo_index_rebuild();
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	delete_option( 'topersia_geo_index_schema' );
	$b = topersia_geo_index_rebuild();
	t( 'پاک‌کردن کامل جدول و بازسازی ← همان اثرانگشت و همان شمار ردیف', isset( $a['fingerprint'], $b['fingerprint'] ) && $a['fingerprint'] === $b['fingerprint'] && $a['rows'] === $b['rows'] && $b['rows'] > 0, wp_json_encode( array( $a, $b ) ) );

	$g = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE post_id = %d", $gav ), ARRAY_A );
	t( 'ردیف گاوخونی: نقطه، مختصات DMS نرمال‌شده، استان از زنجیرهٔ R01، شهر ندارد، نسخهٔ نمایه',
		$g && 'point' === $g['geom_kind'] && abs( (float) $g['lat'] - 32.333333 ) < 0.000001 && abs( (float) $g['lng'] - 52.783333 ) < 0.000001 && (int) $g['province_id'] === $prov && 0 === (int) $g['city_id'] && (int) TOPERSIA_GEO_INDEX_VERSION === (int) $g['index_version'], wp_json_encode( $g ) );

	$only_pub = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} t JOIN {$wpdb->posts} p ON p.ID = t.post_id WHERE p.post_status <> 'publish'" );
	$only_def = 0;
	foreach ( $wpdb->get_col( "SELECT post_id FROM {$table}" ) as $pid ) { if ( ! topersia_geo_index_is_default_lang( $pid ) ) { $only_def++; } }
	t( 'فقط منتشرشده‌ها و فقط زبان پیش‌فرض در نمایه‌اند', 0 === $only_pub && 0 === $only_def );

	/* ── REST استان و شهر ── */
	$rp = mp( $prov );
	$rc = mp( $city );
	$ip = ids_of( $rp );
	t( 'استان اصفهان: ۲۰۰ و GeoJSON با شهرها (id، نام، نوع، نشانی، مختصات [lng,lat])', 200 === $rp->get_status() && 'FeatureCollection' === $rp->get_data()['type'] && count( $ip ) >= 1 && in_array( $city, $ip, true )
		&& isset( $rp->get_data()['features'][0]['geometry']['coordinates'][1] ) && '' !== $rp->get_data()['features'][0]['properties']['n'], wp_json_encode( array_slice( $rp->get_data()['features'], 0, 1 ) ) );
	t( 'شهر اصفهان: جاذبه‌های همان شهر از R01 (باغ چهل‌ستون)؛ شهر دیگری که استان‌اش یکی است نیست', in_array( 1950, ids_of( $rc ), true ) );
	$other = mp( $gav );
	$bad   = mp( 99999999 );
	t( 'هر نوع دیگر (آب) ← ۲۰۰ بی ویژگی؛ زمینهٔ نامعتبر ← ۴۰۰', 200 === $other->get_status() && array() === ids_of( $other ) && 400 === $bad->get_status() );

	$en = mp( $prov, 'en' )->get_data();
	$en_names = array_map( function ( $f ) { return $f['properties']['n']; }, $en['features'] );
	t( 'زبان en: نام و برچسب نوع و نشانی به زبان صفحه، شناسهٔ نسخهٔ en (نه فارسی)', in_array( 'Isfahan', $en_names, true ) && 'City' === $en['features'][0]['properties']['t'] && false !== strpos( $en['features'][0]['properties']['u'], '/en/' ) );

	topersia_cache_last( array() );
	mp( $prov );
	t( 'کش مرکزی: خواندن دوباره hit', 'hit' === ( topersia_cache_last()['status'] ?? '' ), wp_json_encode( topersia_cache_last() ) );

	/* ── بنای موقتِ منتشرشده با رابطهٔ شهر ── */
	$tmp = wp_insert_post( array( 'post_type' => 'tp_monument', 'post_status' => 'publish', 'post_title' => 'tp-test نشانگر نقشه' ) );
	$created[] = $tmp;
	update_post_meta( $tmp, '_tp_test_marker', 'map-local' );
	/* پست بی ردیف زبان WPML رابطه را نمی‌بیند (جت‌انجین شناسه را با WPML می‌نگارد) — مثل هر پست واقعی: فارسی */
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $tmp, 'element_type' => 'post_tp_monument', 'trid' => false, 'language_code' => 'fa', 'source_language_code' => null ) );
	update_post_meta( $tmp, 'geo_coordinates', '32.66, 51.68' );
	$relation = topersia_relation( 84 );
	$relation->update( $city, $tmp );   /* API خود جت‌انجین (نه ردیف دستی) — حافظهٔ رابطه را درست ابطال می‌کند */
	$relrows[] = array( 84, $city, $tmp );
	wp_cache_flush();   /* ردیف را مستقیم در جدول گذاشتیم؛ حافظهٔ رابطهٔ جت‌انجین (همین درخواست) باید دوباره بخواند */
	topersia_geo_index_put( $tmp );
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE post_id = %d", $tmp ), ARRAY_A );
	$in_city = in_array( $tmp, ids_of( mp( $city ) ), true );
	t( 'بنای موقتِ منتشرشده با رابطهٔ شهر: ردیف با city_id شهر اصفهان و استانش، و در map-points شهر', $row && (int) $row['city_id'] === $city && (int) $row['province_id'] === $prov && $in_city, wp_json_encode( $row ) );

	wp_update_post( array( 'ID' => $tmp, 'post_status' => 'draft' ) );
	$gone = ! $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$table} WHERE post_id = %d", $tmp ) ) && ! in_array( $tmp, ids_of( mp( $city ) ), true );
	t( 'پیش‌نویس‌شدن: ردیف برداشته می‌شود و در map-points نیست (کش با گواه نمایه باطل شد)', $gone );

	/* ── گزارش کیفیت ── */
	$rep = topersia_geo_index_report();
	$sum = array( 'posts' => 0, 'valid' => 0, 'absent' => 0, 'outside' => 0, 'invalid' => 0 );
	foreach ( $rep as $r ) { foreach ( $sum as $k => $v ) { $sum[ $k ] += $r[ $k ]; } }
	t( 'گزارش کیفیت به تفکیک نوع: جمع valid + absent + outside + invalid = شمار پست‌ها؛ valid برابر ردیف‌های نمایه (جز موقت)', $sum['valid'] + $sum['absent'] + $sum['outside'] + $sum['invalid'] === $sum['posts'] && $sum['valid'] === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ), wp_json_encode( $sum ) );
} finally {
	foreach ( $relrows as $r ) {
		$wpdb->delete( $wpdb->prefix . 'jet_rel_default', array( 'rel_id' => $r[0], 'parent_object_id' => $r[1], 'child_object_id' => $r[2] ), array( '%d', '%d', '%d' ) );
	}
	foreach ( $created as $id ) {
		if ( 'map-local' === get_post_meta( $id, '_tp_test_marker', true ) ) { wp_delete_post( $id, true ); }
	}
	topersia_geo_index_rebuild();
}

echo str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['pass']}   افتاده: {$GLOBALS['fail']}\n";
exit( $GLOBALS['fail'] ? 1 : 0 );
