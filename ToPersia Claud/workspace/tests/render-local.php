<?php
/**
 * ادعاهای topersia-render ۴٫۴٫۲ روی وردپرسِ واقعیِ محلی.
 *
 *   php tests/render-local.php
 *
 * نگهبان: فقط topersia.local، محیط local، پایگاه topersia_local. هر دادۀ آزمون
 * همین‌جا ساخته و در پایان پاک می‌شود (پست موقت، ردیف رابطه).
 *
 *   JSON-LD   موتور واقعیِ `topersia-schema` + صافی render: والدِ پیش‌نویس نه در
 *             BreadcrumbList نه در containedInPlace؛ منتشرشده می‌ماند  P5-D247
 *   کاتالوگ   وردپرسِ واقعی کاتالوگ `topersia` را از languages/ بار می‌کند —
 *             en_US و ar، با بافت و شکل جمع (l10n.php وردپرس ۶٫۵+)
 *   REST هوا  برچسب روز و وضعیت به زبان نشانی (/en/ · /ar/ · فارسی)
 *   صفحه      /en/ و /ar/ واقعی: تیتر و eyebrow به زبان صفحه
 *
 * هر ادعا جهش دارد. کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص
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

if ( ! function_exists( 'topersia_g_schema_graph' ) || ! function_exists( 'topersia_schema_json' ) || ! function_exists( 'topersia_relation' ) ) {
	fwrite( STDERR, "render ۴٫۴٫۲ یا موتور شِما روی نسخۀ محلی نیست — اول همگام‌سازی\n" );
	exit( 2 );
}

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function t( $label, $ok, $note = '' ) {
	if ( $ok ) { $GLOBALS['pass'] ++; echo "  ✅ {$label}\n"; }
	else       { $GLOBALS['fail'] ++; echo "  ❌ {$label}" . ( '' !== $note ? "   ← {$note}" : '' ) . "\n"; }
}

function mut( $label, callable $claim, $real, $mutant, $note = '' ) {
	$ok  = (bool) $claim( $real );
	$bad = (bool) $claim( $mutant );
	t( $label, $ok && ! $bad, ( $ok ? '' : 'روی رفتار واقعی افتاد' ) . ( $bad ? ' · جهش را نگرفت' : '' ) . ( '' !== $note ? ' · ' . $note : '' ) );
}

function section( $s ) { echo "\n{$s}\n" . str_repeat( '─', 78 ) . "\n"; }

$cleanup = array( 'posts' => array(), 'rel' => array() );

register_shutdown_function( function () use ( &$cleanup ) {
	foreach ( $cleanup['rel'] as $r ) {
		$rel = topersia_relation( $r[0] );
		if ( $rel ) { $rel->delete_rows( $r[1], $r[2] ); }
	}
	foreach ( $cleanup['posts'] as $p ) {
		wp_delete_post( $p, true );
	}
} );

function tmp_post( $type, $title, $status = 'publish' ) {
	$id = wp_insert_post( array( 'post_type' => $type, 'post_status' => $status, 'post_title' => $title, 'post_content' => '' ), true );
	if ( is_wp_error( $id ) ) {
		fwrite( STDERR, 'tmp_post: ' . $id->get_error_message() . "\n" );
		exit( 2 );
	}
	/* پستِ ساختۀ خط فرمان زبان WPML نمی‌گیرد و دروازۀ زبان (P5-D247) کنارش می‌گذارد — مثل ویرایشگر، فارسی */
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_' . $type, 'trid' => false, 'language_code' => 'fa' ) );
	$GLOBALS['cleanup']['posts'][] = $id;
	return (int) $id;
}

function tmp_rel( $rel_id, $parent, $child ) {
	$rel = topersia_relation( $rel_id );
	if ( ! $rel ) {
		fwrite( STDERR, "رابطۀ {$rel_id} نیست\n" );
		exit( 2 );
	}
	$rel->update( $parent, $child );
	$GLOBALS['cleanup']['rel'][] = array( $rel_id, $parent, $child );
}

function local_get( $path ) {
	/* نشانی ثابت، نه home_url(): WPML در بافت فارسی پیشوند /en/ را از home_url برمی‌دارد */
	$res = wp_remote_get( TP_BASE_URL . $path, array( 'timeout' => 240, 'redirection' => 0 ) );
	return is_wp_error( $res ) ? array( 'code' => 0, 'body' => '' ) : array( 'code' => (int) wp_remote_retrieve_response_code( $res ), 'body' => (string) wp_remote_retrieve_body( $res ) );
}

$GLOBALS['cleanup'] = &$cleanup;

echo 'topersia-render ' . TOPERSIA_RENDER_VERSION . " — روی topersia.local\n";

/* ══ JSON-LD از دروازه ═════════════════════════════════════════════════ */
section( 'JSON-LD — موتور شِما + صافی render  P5-D247 · P5-D145' );

$prov = tmp_post( 'tp_province', 'استان آزمون ۴۴۲', 'draft' );
$cnty = tmp_post( 'tp_county', 'شهرستان آزمون ۴۴۲' );
$city = tmp_post( 'tp_city', 'شهر آزمون ۴۴۲' );
tmp_rel( TOPERSIA_REL_PROVINCE_COUNTY, $prov, $cnty );
tmp_rel( TOPERSIA_REL_COUNTY_CITY, $cnty, $city );

$ld = function () use ( $city ) { return (string) topersia_schema_json( $city ); };

$draft_real = $ld();
remove_filter( 'topersia_schema_graph', 'topersia_g_schema_graph', 10 );
$draft_raw = $ld();
add_filter( 'topersia_schema_graph', 'topersia_g_schema_graph', 10, 3 );

$prov_uid = topersia_schema_uid( $prov );

mut( 'JSON-LD شهر: استانِ پیش‌نویس نه نامش در BreadcrumbList، نه شناسه‌اش در containedInPlace — بی صافی render (موتور شِما تنها) هر دو می‌آمد',
	function ( $j ) use ( $prov_uid ) { return '' !== $j && false === strpos( $j, 'استان آزمون ۴۴۲' ) && false === strpos( $j, json_encode( $prov_uid, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); },
	$draft_real, $draft_raw );

wp_update_post( array( 'ID' => $prov, 'post_status' => 'publish' ) );
clean_post_cache( $prov );
$pub_real = json_decode( $ld(), true );
$prov_uid = topersia_schema_uid( $prov );   /* منتشرشده نشانیِ خودش را دارد، نه ?p= پیش‌نویس */

$crumbs = array();
$placed = null;
foreach ( (array) ( $pub_real['@graph'] ?? array() ) as $node ) {
	if ( 'BreadcrumbList' === ( $node['@type'] ?? '' ) ) {
		$crumbs = array_map( function ( $i ) { return $i['name']; }, $node['itemListElement'] );
	}
	if ( isset( $node['containedInPlace'] ) ) { $placed = $node['containedInPlace']; }
}

mut( 'JSON-LD شهر: استانِ منتشرشده می‌ماند — BreadcrumbList همان پله‌های موتور (خانه › استان › شهر؛ شهرستانِ بی‌صفحه skip) و containedInPlace همان استان',
	function ( $r ) use ( $prov_uid ) { return array( 'خانه', 'استان آزمون ۴۴۲', 'شهر آزمون ۴۴۲' ) === $r[0] && array( '@id' => $prov_uid ) === $r[1]; },
	array( $crumbs, $placed ), array( array( 'خانه', 'شهر آزمون ۴۴۲' ), null ), implode( ' › ', $crumbs ) );

/* ══ کاتالوگ روی وردپرس واقعی ══════════════════════════════════════════ */
section( 'کاتالوگ `topersia` روی وردپرس ۷' );

$in_locale = function ( $locale ) {
	switch_to_locale( $locale );
	$r = array(
		__( 'استان', 'topersia' ),
		_x( 'مه', 'ماه میلادی', 'topersia' ),
		__( 'مه', 'topersia' ),
		sprintf( _n( '%s تصویر', '%s تصویر', 3, 'topersia' ), 3 ),
		sprintf( _n( '%s تصویر', '%s تصویر', 1, 'topersia' ), 1 ),
	);
	restore_previous_locale();
	return $r;
};

mut( 'کاتالوگ: en_US — Province · May (ماه) · Fog (هوا) · «3 images» · «1 image»',
	function ( $r ) { return array( 'Province', 'May', 'Fog', '3 images', '1 image' ) === $r; },
	$in_locale( 'en_US' ), $in_locale( 'fa_IR' ) );

mut( 'کاتالوگ: ar — محافظة · مايو · ضباب · «3 صور» (۳ تا ۱۰) · «صورة واحدة» (یک) — شش شکل جمع عربی',
	function ( $r ) { return array( 'محافظة', 'مايو', 'ضباب', '3 صور', 'صورة واحدة' ) === $r; },
	$in_locale( 'ar' ), $in_locale( 'fa_IR' ) );

/* ══ REST هوا به زبان نشانی ═════════════════════════════════════════════ */
section( 'REST هوا — برچسب پس از کش، به زبان نشانی' );

$coords = rawurlencode( '32.6539, 51.6660' );
$wx = function ( $prefix ) use ( $coords ) {
	$r = local_get( $prefix . 'wp-json/topersia/v1/weather?coords=' . $coords );
	$j = json_decode( $r['body'], true );
	return array( $r['code'], isset( $j['days'][0]['d'] ) ? $j['days'][0]['d'] : '', isset( $j['days'][0]['s'] ) ? $j['days'][0]['s'] : '', isset( $j['note'] ) ? $j['note'] : '' );
};
$wx_fa = $wx( '/' );
$wx_en = $wx( '/en/' );
$wx_ar = $wx( '/ar/' );

mut( 'REST هوا: /en/ «Today» و وضعیت انگلیسی، /ar/ «اليوم»، فارسی «امروز» — یک کش برای همه، برچسب هنگام پاسخ',
	function ( $r ) { return 200 === $r[0][0] && 'امروز' === $r[0][1] && 'Today' === $r[1][1] && ! preg_match( '/[\x{0600}-\x{06FF}]/u', $r[1][2] . $r[1][3] ) && 'اليوم' === $r[2][1]; },
	array( $wx_fa, $wx_en, $wx_ar ), array( $wx_fa, $wx_fa, $wx_fa ), wp_json_encode( array( $wx_fa, $wx_en, $wx_ar ), JSON_UNESCAPED_UNICODE ) );

/* ══ صفحۀ واقعی ════════════════════════════════════════════════════════ */
section( 'صفحۀ واقعی /en/ و /ar/' );

$hero = function ( $path ) {
	$b = local_get( $path )['body'];
	return preg_match( '#<p class="tp-eyebrow">([^<]*)</p><h1>([^<]*)</h1>#u', $b, $m ) ? array( $m[1], $m[2] ) : array( '', '' );
};
$h_fa = $hero( '/province/isfahan/' );
$h_en = $hero( '/en/province/isfahan/' );
$h_ar = $hero( '/ar/province/isfahan/' );

/*
 * تیتر ترجمه از name_{زبان} (فیلد copy WPML)، بی پیشوند نوع؛ نوع در eyebrow. تا ۲ج٫۸
 * ترجمه‌های محلیِ ۴٫۴٫۱ فیلدهای copy نداشتند و تیتر به عنوان پست («Isfahan
 * Province») می‌افتاد — زنده چنین نیست (WPML copy را روی ترجمه می‌نشاند).
 */
mut( 'صفحه: /en/ «Province» + «Isfahan»، /ar/ «محافظة» + «أصفهان»، فارسی «استان» + «استان اصفهان» — بی پیشوندِ فارسی روی ترجمه',
	function ( $r ) { return array( 'Province', 'Isfahan' ) === $r[1] && array( 'محافظة', 'أصفهان' ) === $r[2] && array( 'استان', 'استان اصفهان' ) === $r[0]; },
	array( $h_fa, $h_en, $h_ar ), array( $h_fa, array( 'استان', 'استان Isfahan' ), array( 'استان', 'استان أصفهان' ) ),
	wp_json_encode( array( $h_fa, $h_en, $h_ar ), JSON_UNESCAPED_UNICODE ) );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] ? 1 : 0 );
