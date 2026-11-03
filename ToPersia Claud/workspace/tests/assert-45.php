<?php
/**
 * دستهٔ 45 — صفحهٔ اول `[tp_home]`: فقط داده‌ی واقعی، بی نمونه، بی عدد جعلی، بی کادر و SearchAction جعلی.
 * از assert.php بار می‌شود؛ خواندن کد با جهش. رفتار روی وردپرس واقعی: tests/w45-local.php.
 */

echo "\nدستهٔ 45 — صفحهٔ اول [tp_home]\n" . str_repeat( '─', 78 ) . "\n";

$w45_root   = dirname( __DIR__ );
$w45_src    = (string) @file_get_contents( $w45_root . '/plugin/topersia-render/includes/topersia-g-home.php' );
$w45_schema = (string) @file_get_contents( $w45_root . '/plugin/topersia-schema/topersia-schema.php' );
$w45_zones  = (string) @file_get_contents( $w45_root . '/plugin/topersia-render/includes/topersia-g-zones.php' );
$w45_loader = (string) @file_get_contents( $w45_root . '/plugin/topersia-render/topersia-render.php' );
$w45_i18n   = (string) @file_get_contents( $w45_root . '/scripts/tp-i18n.php' );
$w45_cat    = include $w45_root . '/scripts/i18n/topersia-render.php';

/* ثبت: شورت‌کد، بارگذاری فایل و منبع کاتالوگ. */
$w45_reg = function ( $z ) use ( $w45_loader, $w45_i18n ) {
	return false !== strpos( $z, "'tp_home'      => 'topersia_g_sc_home'" ) && false !== strpos( $w45_loader, "'includes/topersia-g-home.php'" ) && false !== strpos( $w45_i18n, "'includes/topersia-g-home.php'" );
};
tp441_mut( 'شورت‌کد tp_home ثبت است و فایلش بار و برای کاتالوگ اسکن می‌شود — جهش: ثبت نباشد', $w45_reg, $w45_zones, str_replace( "'tp_home'      => 'topersia_g_sc_home',", '', $w45_zones ) );

/* فقط واقعی: نمونه بیرون، دروازهٔ مرکزی، فیلتر زبان WPML. */
$w45_real = function ( $s ) {
	return false !== strpos( $s, "'key' => '_tp_sample', 'compare' => 'NOT EXISTS'" ) && false !== strpos( $s, 'topersia_g_published(' ) && false !== strpos( $s, "'suppress_filters' => false" );
};
tp441_mut( 'صفحهٔ اول فقط پست واقعی، منتشرشده و هم‌زبان می‌آورد — جهش: شرط نمونه برداشته شود', $w45_real, $w45_src, str_replace( "array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' )", 'array()', $w45_src ) );
tp441_mut( 'صفحهٔ اول از دروازهٔ مرکزی می‌گذرد — جهش: بی دروازه', $w45_real, $w45_src, str_replace( 'topersia_g_published(', 'array_values(', $w45_src ) );

/* بی عدد و آمار جعلی؛ بی جست‌وجو. */
$w45_nodigit = function ( $s ) {
	$code = preg_replace( '#/\*.*?\*/#s', '', $s );
	$code = preg_replace( '#^\s*(\*|//).*$#m', '', $code );
	return 0 === preg_match( '/(?<![\w$])\d{2,}(?![\w])/', $code );
};
tp441_mut( 'صفحهٔ اول عدد ثابت (آمار) ندارد — جهش: عدد در کد', $w45_nodigit, $w45_src, $w45_src . "\n\$x = 31;" );
$w45_nosearch = function ( $s ) {
	$s = preg_replace( '#/\*.*?\*/#s', '', $s );
	$s = preg_replace( '#^\s*(\*|//).*$#m', '', $s );
	return false === stripos( $s, 'SearchAction' ) && false === stripos( $s, 'type="search"' ) && false === stripos( $s, '<form' );
};
tp441_mut( 'صفحهٔ اول و اسکیمایش کادر جست‌وجو و SearchAction ندارند (جست‌وجوی سراسری نیست) — جهش: SearchAction', $w45_nosearch, $w45_src . "\n" . ( preg_match( '/function topersia_schema_home_json.*?\n}\n/s', $w45_schema, $w45_m ) ? $w45_m[0] : 'SearchAction' ), $w45_src . '<form role="search">' );

/* اسکیمای صفحهٔ اول فقط روی صفحهٔ اول. */
$w45_sch = function ( $s ) {
	return false !== strpos( $s, "'page' === get_post_type( \$post_id ) && is_front_page() ) ? topersia_schema_home_json( \$post_id )" ) && false !== strpos( $s, "'@graph'   => array( topersia_schema_organization(), \$site, \$page )" );
};
tp441_mut( 'اسکیمای صفحهٔ اول: Organization · WebSite · WebPage فقط وقتی صفحهٔ اول است — جهش: برای هر صفحه', $w45_sch, $w45_schema, str_replace( "'page' === get_post_type( \$post_id ) && is_front_page() )", 'true )', $w45_schema ) );

/* ترجمهٔ رشته‌های تازه. */
$w45_str = function ( $c ) {
	foreach ( array( 'دسته‌های اصلی', 'غذا و صنایع‌دستی', 'فرهنگ و تاریخ', 'اطلاعات کاربردی', 'ارز', 'تازه‌ترین اخبار', 'حساب کاربری', 'ورود به پنل کاربری' ) as $k ) {
		if ( empty( $c[ $k ]['en'] ) || empty( $c[ $k ]['ar'] ) ) { return false; }
	}
	return true;
};
tp441_mut( 'رشته‌های صفحهٔ اول انگلیسی و عربی دارند — جهش: یکی بی ترجمه', $w45_str, $w45_cat, array_diff_key( $w45_cat, array( 'ارز' => 1 ) ) );
