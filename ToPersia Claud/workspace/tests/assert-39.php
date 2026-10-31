<?php
/**
 * دستهٔ 39 — کیفیت هوا: بی دادهٔ خودِ موجودیت ← نزدیک‌ترین والد (P5-D461)؛ مسیریابی نشان/گوگل/ویز بی بلد (P5-D462).
 * از assert.php بار می‌شود؛ PHP خالص با stub. رفتار روی صفحهٔ واقعی: tests/air-local.php · tests/routing-local.php.
 */

echo "\nدستهٔ 39 — هوای والد و مسیریابی  P5-D461 · P5-D462\n" . str_repeat( '─', 78 ) . "\n";

/* ── هوا: جهان stub دستهٔ 2f — کشور ۸۰۰۱ ← استان ۸۰۰۲ (مرکز: شهر ۸۰۰۴) ← شهرستان ۸۰۰۳ ← شهر؛ تالاب ۸۰۰۵ زیر استان ── */
$keep39 = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], isset( $GLOBALS['tp_rel'] ) ? $GLOBALS['tp_rel'] : null, isset( $GLOBALS['tp_filters'] ) ? $GLOBALS['tp_filters'] : null, isset( $GLOBALS['tp2g_status'] ) ? $GLOBALS['tp2g_status'] : null );   /* جهان 2f جهان‌های دیگر را پاک می‌کند */
$f2_world( $f2_reg );
$GLOBALS['tp2g_status'] = 'active';
$GLOBALS['tp_meta'][8001]['geo_coordinates'] = '32.4, 53.7';          /* کشور */
$GLOBALS['tp_meta'][8004]['geo_coordinates'] = '32.6539, 51.6660';    /* شهرِ مرکز استان */
$air_water  = r( 8005, 'tp_air' );     /* تالاب بی مختصات ← استان ← مرکز استان */
$GLOBALS['tp_meta'][8004]['geo_coordinates'] = '';
$air_city   = r( 8004, 'tp_air' );     /* شهر بی مختصات، مرکز استان هم بی مختصات ← کشور */
$GLOBALS['tp_meta'][8004]['geo_coordinates'] = '32.6539, 51.6660';
$air_own    = r( 8004, 'tp_air' );     /* مختصات خودش ← «برای شهر …»؛ بی جایگزین */
$GLOBALS['tp_meta'][8001]['geo_coordinates'] = '';
$GLOBALS['tp_meta'][8004]['geo_coordinates'] = '';
$air_none   = r( 8005, 'tp_air' );     /* هیچ‌کس مختصات ندارد ← جعبه نمی‌آید */
$air_ok = function ( $x ) {
	list( $w, $c, $o, $n ) = $x;
	return false !== strpos( $w, 'data-tp-coords="32.6539, 51.666"' ) && false !== strpos( $w, '<span data-tp-air-for>برای استان اصفهان</span>' )
		&& false !== strpos( $c, 'data-tp-coords="32.4, 53.7"' ) && false !== strpos( $c, '<span data-tp-air-for>برای کشور ایران</span>' )
		&& false !== strpos( $o, '<span data-tp-air-for>برای شهر آزمون</span>' ) && false === strpos( $o, 'برای استان' ) && false === strpos( $o, 'برای کشور' )
		&& '' === $n
		&& false !== strpos( $w, 'کیفیت هوا: CAMS، Open-Meteo' ) && false === stripos( $w, 'neshan' ) && false === strpos( $w, 'نشان' );   /* منبع همان CAMS؛ والد منبع نیست */
};
tp441_mut( 'هوا (P5-D461): تالاب بی مختصات ← مرکز استان با «برای استان اصفهان»؛ شهرِ بی مختصات ← کشور «برای کشور ایران»؛ مختصات خودش اولویت دارد؛ هیچ‌کس مختصات ندارد ← جعبه نیست؛ منبع همچنان CAMS/Open-Meteo — جهش: بی جایگزین می‌افتد',
	$air_ok, array( $air_water, $air_city, $air_own, $air_none ), array( '', '', $air_own, '' ) );

$z39 = (string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-air.php' );
$fb39 = function ( $s ) { return false !== strpos( $s, 'topersia_g_air_at( $pid )' ) && false !== strpos( $s, 'array_reverse( array_map( \'absint\', (array) topersia_g_place_chain( $pid ) ) )' ); };
t( 'هوا: نقطه از خودِ پست و سپس زنجیرهٔ مکانی از نزدیک به دور (شهر ← استان ← کشور) خوانده می‌شود — جهش: زنجیره حذف شود می‌افتد',
	$fb39( $z39 ) && ! $fb39( str_replace( 'topersia_g_place_chain( $pid )', 'array()', $z39 ) ) );

/* ── مسیریابی: نشان · گوگل · ویز؛ بلد نه ── */
$extra39 = array(
	'google' => array( 'on' => 1, 'pattern' => 'https://www.google.com/maps/dir/?api=1&destination={lat},{lng}', 'text' => 'https://www.google.com/maps/dir/?api=1&destination={q}' ),
	'waze'   => array( 'on' => 1, 'pattern' => 'https://waze.com/ul?ll={lat},{lng}&navigate=yes', 'text' => 'https://waze.com/ul?q={q}&navigate=yes' ),
	'neshan' => array( 'on' => 1, 'pattern' => 'https://nshn.ir/?lat={lat}&lng={lng}', 'text' => '' ),
	'balad'  => array( 'on' => 1, 'pattern' => 'https://balad.ir/location?latitude={lat}&longitude={lng}', 'text' => 'https://balad.ir/search?q={q}' ),
);
$l_coords = topersia_g_routing_links( $extra39, array( 'mode' => 'coords', 'lat' => 32.6539, 'lng' => 51.666 ) );
$l_text   = topersia_g_routing_links( $extra39, array( 'mode' => 'text', 'q' => 'تالاب گاوخونی، استان اصفهان', 'point' => array( 'lat' => 32.6, 'lng' => 51.6 ) ) );
$lab39    = topersia_g_routing_labels();
$rt_ok = function ( $x ) {
	list( $c, $t, $lab ) = $x;
	return array( 'google', 'waze', 'neshan' ) === array_keys( $c ) && array( 'google', 'waze', 'neshan' ) === array_keys( $t ) && ! isset( $lab['balad'] )
		&& false !== strpos( $c['neshan'], 'lat=32.6539' ) && false !== strpos( $t['google'], rawurlencode( 'تالاب گاوخونی، استان اصفهان' ) )
		&& false !== strpos( $t['neshan'], 'lat=32.6' )                                   /* ناحیه‌ای: نشان با نقطهٔ نماینده (P5-D383) */
		&& false === strpos( wp_json_encode( $c ) . wp_json_encode( $t ), 'balad' );
};
tp441_mut( 'مسیریابی (P5-D462): فقط گوگل · ویز · نشان (بلد حذف، حتی اگر در تنظیمات روشن باشد)؛ نقطه‌ای با مختصات، ناحیه‌ای با نام والد (استثنای P5-D253) — جهش: بلد برگردد می‌افتد',
	$rt_ok, array( $l_coords, $l_text, $lab39 ), array( $l_coords + array( 'balad' => 'https://balad.ir/x' ), $l_text, $lab39 ) );

/* استان مسیریابی ندارد (P5-D455)؛ کشور هم نه */
$GLOBALS['tp_meta'][8002]['geo_coordinates'] = '32.6, 51.6';
$prov_t = topersia_g_routing_target( 8002 );
t( 'مسیریابی: استان مقصد ندارد (P5-D455)', array() === $prov_t );

/* لینک: rel=noopener و هدف لمسی ≥۴۴ */
$rsrc = (string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-routing.php' );
$css39 = (string) file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' );
$rel_ok = function ( $src, $css ) {
	return false !== strpos( $src, 'target="_blank" rel="noopener nofollow" data-tp-nav=' ) && 1 === preg_match( '/#tp-routing \.tp-tile\{[^}]*min-block-size:44px/', $css );
};
t( 'مسیریابی: هر لینک target=_blank با rel=noopener و کاشی با هدف لمسی ≥۴۴px — جهش: noopener حذف شود می‌افتد',
	$rel_ok( $rsrc, $css39 ) && ! $rel_ok( str_replace( 'rel="noopener nofollow"', 'rel="nofollow"', $rsrc ), $css39 ) );

/* جهان آزمون‌های پس از این فایل برمی‌گردد */
$GLOBALS['tp_posts'] = $keep39[0]; $GLOBALS['tp_meta'] = $keep39[1];
if ( null === $keep39[2] ) { unset( $GLOBALS['tp_rel'] ); } else { $GLOBALS['tp_rel'] = $keep39[2]; }
if ( null === $keep39[3] ) { unset( $GLOBALS['tp_filters'] ); } else { $GLOBALS['tp_filters'] = $keep39[3]; }
if ( null === $keep39[4] ) { unset( $GLOBALS['tp2g_status'] ); } else { $GLOBALS['tp2g_status'] = $keep39[4]; }
