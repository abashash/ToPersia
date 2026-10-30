<?php
/**
 * دستهٔ 35 — «اطلاعات سفر» تصویری (P5-D460). از assert.php بار می‌شود؛ PHP خالص با stub.
 *
 * کاشی = آیکون SVG درون‌خط (بی کتابخانه) + مقدار کوتاه + برچسب؛ HTML سرور، بی جاوااسکریپت؛ فقط از متای موجود؛ نبود «—» با «نامشخص».
 * رفتار روی صفحهٔ واقعی و در سه زبان: tests/trip-local.php.
 */

echo "\nدستهٔ 35 — اطلاعات سفر تصویری  P5-D460\n" . str_repeat( '─', 78 ) . "\n";

$z35 = (string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );
$g35 = (string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-map.php' );

/* ── آیکون‌ها: SVG درون‌خط، همان قلم مجموعه، بی منبع بیرونی ── */
$keys35 = array( 'plug', 'wheel', 'clock', 'phone', 'passport', 'coins', 'plane', 'train', 'metro', 'tent', 'shield', 'heart', 'car' );
$icons35 = array();
foreach ( $keys35 as $k ) { $icons35[ $k ] = topersia_g_icon( $k ); }
$icons_ok = function ( $x ) {
	foreach ( $x as $svg ) {
		if ( ! preg_match( '#^<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1\.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">.+</svg>$#s', $svg ) || preg_match( '#<img|http|xlink|<script|<use#i', $svg ) ) { return false; }
	}
	return 13 === count( $x );
};
tp441_mut( 'اطلاعات سفر: ۱۳ آیکون SVG درون‌خط (دوشاخه، فرمان، ساعت، تلفن، گذرنامه، سکه، هواپیما، قطار، مترو، چادر، سپر، دل، خودرو) — بی کتابخانه و بی منبع بیرونی — جهش: آیکونی با <img> می‌افتد',
	$icons_ok, $icons35, array_merge( $icons35, array( 'plug' => '<img src="https://x/p.svg">' ) ) );

/* ── کاشی ── */
$tile_val  = topersia_g_trip_tile( 'plug', 'دوشاخۀ برق', 'نوع C' );
$tile_none = topersia_g_trip_tile( 'plug', 'دوشاخۀ برق', '' );
$tile_link = topersia_g_trip_tile( 'coins', 'ارز', 'ریال', 'https://topersia.com/currency/' );
$tile_ok = function ( $x ) {
	return 1 === preg_match( '#^<div class="tp-trip__t" role="listitem"><span class="tp-trip__i" aria-hidden="true"><svg .*?</svg></span><b class="tp-trip__v">نوع C</b><span class="tp-trip__k">دوشاخۀ برق</span></div>$#su', $x[0] )
		&& false !== strpos( $x[1], '<b class="tp-trip__v tp-trip__v--none" role="img" aria-label="نامشخص">—</b>' ) && false === strpos( $x[1], 'نوع C' )
		&& 0 === strpos( $x[2], '<a class="tp-trip__t" role="listitem" href="https://topersia.com/currency/">' ) && false !== strpos( $x[2], '<b class="tp-trip__v">ریال</b>' );
};
tp441_mut( 'کاشی: آیکون + مقدار (b) + برچسب (span)، جزء لیست؛ نبود ← «—» با aria-label «نامشخص» (role=img)؛ نشانی ← پیوند — جهش: برچسب دسترس‌پذیر خالی می‌افتد',
	$tile_ok, array( $tile_val, $tile_none, $tile_link ), array( $tile_val, str_replace( 'aria-label="نامشخص"', 'aria-label=""', $tile_none ), $tile_link ) );

/* ── کشور: دوشاخه، سمت رانندگی و منطقهٔ زمانی همیشه کاشی دارند؛ بی داده «—»؛ هیچ مقدار ساختگی ── */
$GLOBALS['tp_posts'][9501] = array( 'type' => 'tp_country', 'title' => 'کشور آزمون', 'url' => 'https://topersia.com/country/t/' );
$GLOBALS['tp_meta'][9501]  = array();
$c_none = topersia_g_practical_section( 9501, array( 'items' => array(), 'group' => array() ) );
$GLOBALS['tp_meta'][9501]  = array( 'drive_side' => 'left', 'timezone' => '+03:30 UTC' );
$c_data = topersia_g_practical_section( 9501, array( 'items' => array(), 'group' => array() ) );
$cnt_none = function ( $h ) { return substr_count( $h, 'tp-trip__v--none' ); };
$c_ok = function ( $x ) use ( $cnt_none ) {
	return 3 === $cnt_none( $x[0] ) && 3 === substr_count( $x[0], '<svg' )                       /* دوشاخه · سمت · روادید — همه «—»؛ منطقهٔ زمانی دیگر اینجا نیست (دستهٔ 36-ج) */
		&& false === strpos( $x[0], 'منطقۀ زمانی' ) && false === strpos( $x[1], 'منطقۀ زمانی' ) && false === strpos( $x[1], '۰۳:۳۰' )
		&& 2 === $cnt_none( $x[1] );                                                            /* با سمت رانندگی: دوشاخه و روادید «—» */
};
tp441_mut( 'کشور: «اطلاعات سفر» بی داده سه کاشی «—» (دوشاخه · سمت رانندگی · روادید) و منطقهٔ زمانی در آن نیست؛ با سمت رانندگی فقط دو «—» — جهش: «—» مقدار ساختگی بگیرد می‌افتد',
	$c_ok, array( $c_none, $c_data ), array( str_replace( 'tp-trip__v--none', 'x', $c_none ), $c_data ) );

/* ── نوع دیگر: فقط فیلدهای سفرِ موجود، بی «—» ── */
$GLOBALS['tp_posts'][9502] = array( 'type' => 'tp_city', 'title' => 'شهر آزمون', 'url' => 'https://topersia.com/city/t/' );
$GLOBALS['tp_meta'][9502]  = array( 'airport_name' => 'فرودگاه آزمون', 'airport_iata' => 'TST', 'railway_station' => 'true' );
$city_h = topersia_g_practical_section( 9502, array( 'items' => array(), 'group' => array() ) );
t( 'شهر: کاشی فرودگاه «نام (کد)» با آیکون هواپیما و کاشی ایستگاه راه‌آهن؛ بی «—» برای نوعی که فیلدش را ندارد',
	false !== strpos( $city_h, 'فرودگاه آزمون <span class="tp-lat" dir="ltr">(TST)</span>' ) && 2 === substr_count( $city_h, 'tp-trip__t' ) && false === strpos( $city_h, 'tp-trip__v--none' ) && false !== strpos( $city_h, 'ایستگاه راه‌آهن' ) );

/* ── بی جاوااسکریپت و فقط توکن؛ جملهٔ نمونهٔ ولتاژ از توضیح فیلد برداشته شد ── */
$p450_35 = (string) file_get_contents( dirname( __DIR__ ) . '/scripts/lib/tp-p5d450.php' );
$fn_src  = function ( $src, $name ) { return preg_match( '/function ' . $name . '\(.*?\n\t}\n}/s', $src, $m ) ? $m[0] : ''; };
$src_ok = function ( $z, $p ) use ( $fn_src ) {
	$body = $fn_src( $z, 'topersia_g_trip_tile' ) . $fn_src( $z, 'topersia_g_practical_section' );
	return '' !== $body && false === stripos( $body, '<script' ) && false === stripos( $body, 'onclick' ) && false === stripos( $body, 'data-tp-' )
		&& false === strpos( $p, '۲۳۰ ولت' ) && false !== strpos( $p, "'electric_plug'        => array( 'checkbox', 'دوشاخۀ برق', 'Electric plug type', 'نوع القابس الكهربائي', ''," );
};
$css35 = (string) file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' );
$tok_ok = function ( $css ) { return 1 === preg_match( '/html body \.tp-page \.tp-trip__t\{[^}]*var\(--tp-tile-bg\)[^}]*var\(--tp-tile-border\)[^}]*var\(--tp-tile-radius\)[^}]*var\(--tp-tile-text\)/', $css ) && 1 === preg_match( '/\.tp-trip__i\{[^}]*color:var\(--tp-primary-text\)/', $css ) && 0 === preg_match( '/\.tp-trip[^{]*\{[^}]*(#[0-9a-fA-F]{3,6}|rgb\()/', $css ); };
t( 'اطلاعات سفر: بی جاوااسکریپت و بی data-tp-؛ جملهٔ نمونهٔ «۲۳۰ ولت» از توضیح فیلد دوشاخه برداشته شد؛ CSS فقط توکن کاشی/پالت (بی رنگ خام) — جهش: رنگ خام یا جملهٔ نمونه می‌افتد',
	$src_ok( $z35, $p450_35 ) && $tok_ok( $css35 )
	&& ! $src_ok( $z35, str_replace( "'نوع القابس الكهربائي', '',", "'نوع القابس الكهربائي', 'نوع دوشاخه‌های رایج (ایران: C و F، ۲۳۰ ولت).',", $p450_35 ) )
	&& ! $tok_ok( str_replace( '.tp-trip__i{grid-area:i;', '.tp-trip__i{background:#ff0000;grid-area:i;', $css35 ) . 'html body .tp-page .tp-trip__v{color:#123456}' ) );

/* ── هر فیلد سفر یک جا: دوشاخه · سمت · منطقه · پیش‌شماره · مترو · روادید هنگام ورود در ناحیهٔ travel (نه card/visit) ── */
$moved = true;
foreach ( array( 'electric_plug', 'drive_side', 'calling_code', 'metro_available', 'visa_on_arrival', 'free_zone', 'airport_name', 'railway_station' ) as $k ) {
	if ( 'travel' !== ( topersia_g_map()[ $k ]['zone'] ?? '' ) ) { $moved = false; }
}
/* دستهٔ 36-ج: منطقهٔ زمانی فقط در «اطلاعات جغرافیایی» (ناحیهٔ card)، نه «اطلاعات سفر» */
t( 'FIELDMAP: فیلدهای سفر همه در ناحیهٔ travel‌اند و منطقهٔ زمانی در card — هر مقدار فقط در یک کارت', $moved && 'card' === ( topersia_g_map()['timezone']['zone'] ?? '' ) );

unset( $GLOBALS['tp_posts'][9501], $GLOBALS['tp_posts'][9502], $GLOBALS['tp_meta'][9501], $GLOBALS['tp_meta'][9502] );
