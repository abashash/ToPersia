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
	$code = preg_replace( '/\}, \d+ \);/', '}, P );', $code );   /* اولویت قلاب وردپرس عدد آمار نیست — دستۀ 46 */
	$code = preg_replace( '/\), 0, \d+ \);/', '), 0, N );', $code );   /* سقف array_slice عدد آمار نیست — دستۀ 47 */
	$code = preg_replace( '/count\( \$picks \) >= \d+ \)/', 'count( $picks ) >= N )', $code );   /* سقف عکس‌های گالری عدد آمار نیست — دستۀ 49 */
	return 0 === preg_match( '/(?<![\w$])\d{2,}(?![\w])/', $code );
};
tp441_mut( 'صفحهٔ اول عدد ثابت (آمار) ندارد — جهش: عدد در کد', $w45_nodigit, $w45_src, $w45_src . "\n\$x = 31;" );
/* P5-D536 (دستۀ 49) جای حکم دستۀ 45 را گرفت: جست‌وجوی سراسری واقعی هست، پس SearchAction فقط با همان دروازه. */
$w45_search = function ( $s ) {
	return false !== strpos( $s, "'SearchAction'" ) && false !== strpos( $s, "if ( function_exists( 'topersia_g_search_form' ) ) {" );
};
tp441_mut( 'SearchAction اسکیمای صفحهٔ اول فقط وقتی جست‌وجوی سراسری render هست (P5-D536) — جهش: بی دروازه', $w45_search, ( preg_match( '/function topersia_schema_home_json.*?
}
/s', $w45_schema, $w45_m ) ? $w45_m[0] : '' ), str_replace( "if ( function_exists( 'topersia_g_search_form' ) ) {", 'if ( true ) {', ( preg_match( '/function topersia_schema_home_json.*?
}
/s', $w45_schema, $w45_m ) ? $w45_m[0] : '' ) ) );

/* اسکیمای صفحهٔ اول فقط روی صفحهٔ اول. */
$w45_sch = function ( $s ) {
	return false !== strpos( $s, "'page' === get_post_type( \$post_id ) && is_front_page() ) ? topersia_schema_home_json( \$post_id )" ) && false !== strpos( $s, "\$graph = array( topersia_schema_organization(), \$site, \$page );" ) && false !== strpos( $s, "'@graph'   => \$graph," );
};
tp441_mut( 'اسکیمای صفحهٔ اول: Organization · WebSite · WebPage فقط وقتی صفحهٔ اول است — جهش: برای هر صفحه', $w45_sch, $w45_schema, str_replace( "'page' === get_post_type( \$post_id ) && is_front_page() )", 'true )', $w45_schema ) );

/* ترجمهٔ رشته‌های تازه. */
$w45_str = function ( $c ) {
	foreach ( array( 'ایران را کشف کنید', 'ارز و پرداخت', 'استان‌های ایران', 'محبوب‌ترین مقاصد', 'تازه‌ترین اخبار' ) as $k ) {   /* «حساب کاربری» و «ورود / ثبت‌نام» با کادر حساب برداشته شدند — P5-D531 */
		if ( empty( $c[ $k ]['en'] ) || empty( $c[ $k ]['ar'] ) ) { return false; }
	}
	return true;
};
tp441_mut( 'رشته‌های صفحهٔ اول انگلیسی و عربی دارند — جهش: یکی بی ترجمه', $w45_str, $w45_cat, array_diff_key( $w45_cat, array( 'ارز و پرداخت' => 1 ) ) );

/* ─── بخش الف·۱–۲ — صفحه‌های ثابت و پاورقی ─── */
$w45_legal = (string) @file_get_contents( $w45_root . '/plugin/topersia-render/includes/topersia-g-legal.php' );
$w45_lp    = (string) @file_get_contents( $w45_root . '/scripts/tp-legal-pages.php' );

$w45_lreg = function ( $loader ) use ( $w45_i18n ) {
	return false !== strpos( $loader, "'includes/topersia-g-legal.php'" ) && false !== strpos( $w45_i18n, "'includes/topersia-g-legal.php'" );
};
tp441_mut( 'پاورقی بار و برای کاتالوگ اسکن می‌شود — جهش: بار نشود', $w45_lreg, $w45_loader, str_replace( ", 'includes/topersia-g-legal.php'", '', $w45_loader ) );

$w45_ext = function ( $s ) {
	return false !== strpos( $s, "\$ext  = ' target=\"_blank\" rel=\"noopener nofollow\"';" ) && 2 === substr_count( $s, '"\' . $ext . \'>\' . esc_html__(' );
};
tp441_mut( 'دو پیوند اعتبار _blank با noopener nofollow و لنگر فقط نام — جهش: بی nofollow', $w45_ext, $w45_legal, str_replace( 'rel="noopener nofollow"', 'rel="noopener"', $w45_legal ) );

$w45_col2 = function ( $s ) {
	$code = preg_replace( '#/\*.*?\*/#s', '', $s );
	return false === strpos( $code, "'copyrights2'" ) && false !== strpos( $code, "'copyrights' === \$slug" ) && false !== strpos( $code, 'is_admin()' );
};
tp441_mut( 'فقط ستون نخست کپی‌رایت وودمارت؛ ستون دوم (payments) دست نمی‌خورد — جهش: copyrights2 هم', $w45_col2, $w45_legal, str_replace( "if ( 'copyrights' === \$slug )", "if ( 'copyrights2' === \$slug ) { return ''; }\n\tif ( 'copyrights' === \$slug )", $w45_legal ) );

$w45_year = function ( $s ) {
	$code = preg_replace( '#/\*.*?\*/#s', '', $s );
	return false !== strpos( $code, "topersia_g_fa( wp_date( 'Y' ) )" ) && 0 === preg_match( '/(?<![\w$])20\d\d(?![\w])/', $code );
};
tp441_mut( 'سال پاورقی از ساعت سرور به خط زبان صفحه، بی سال ثابت — جهش: سال ثابت', $w45_year, $w45_legal, str_replace( "topersia_g_fa( wp_date( 'Y' ) )", "'2026'", $w45_legal ) );

$w45_pub = function ( $s ) {
	return false !== strpos( $s, 'topersia_g_published( array( $id ) )' ) && false !== strpos( $s, "'post_status'      => 'publish'" );
};
tp441_mut( 'پیوند پاورقی فقط به صفحۀ منتشرشدۀ زبان جاری — جهش: بی دروازه', $w45_pub, $w45_legal, str_replace( 'topersia_g_published( array( $id ) )', 'array( $id )', $w45_legal ) );

$w45_fstr = function ( $c ) {
	foreach ( array( 'خدمات وب:', 'آمستریدا', 'طراحی و اجرا:', 'امیرعباس بشاش', 'صفحه‌های سایت' ) as $k ) {
		if ( empty( $c[ $k ]['en'] ) || empty( $c[ $k ]['ar'] ) ) { return false; }
	}
	foreach ( $c as $k => $v ) {
		if ( 0 === strpos( $k, '© %1$s ToPersia (%2$s).' ) ) { return 0 === strpos( (string) $v['en'], '© %1$s %2$s.' ) && 0 === strpos( (string) $v['ar'], '© %1$s ToPersia (%2$s).' ) && 2 === substr_count( (string) $v['ar'], '%2$s' ); }
	}
	return false;
};
tp441_mut( 'متن پاورقی سه‌زبانه در کاتالوگ — جهش: عربی کپی‌رایت نباشد', $w45_fstr, $w45_cat, array_map( function ( $v ) { return isset( $v['ar'] ) && is_string( $v['ar'] ) && 0 === strpos( $v['ar'], '© %1$s' ) ? array( 'en' => $v['en'], 'ar' => '' ) : $v; }, $w45_cat ) );

/* اسکریپت ساخت صفحه‌ها: فقط محلی، متن از دو فایل، یادداشت و GA و جای‌نگهدار. */
$w45_lps = function ( $s ) {
	return false !== strpos( $s, "'local' !== wp_get_environment_type()" )
		&& false !== strpos( $s, "0 === strpos( \$ln, '>' )" )
		&& false !== strpos( $s, "is_plugin_active( 'google-site-kit/google-site-kit.php' )" )
		&& false !== strpos( $s, "! \$sitekit && false !== strpos( \$ln, 'Google Analytics' )" )
		&& false !== strpos( $s, "(int) get_option( 'wp_page_for_privacy_policy' )" )
		&& false !== strpos( $s, '$warn[]' );
};
tp441_mut( 'ساخت صفحه‌ها: خط یادداشت بیرون — جهش: یادداشت بماند', $w45_lps, $w45_lp, str_replace( "0 === strpos( \$ln, '>' ) || ", '', $w45_lp ) );
tp441_mut( 'ساخت صفحه‌ها: بند GA فقط با Site Kit — جهش: همیشه بیاید', $w45_lps, $w45_lp, str_replace( "! \$sitekit && false !== strpos( \$ln, 'Google Analytics' )", 'false', $w45_lp ) );
tp441_mut( 'ساخت صفحه‌ها: حریم خصوصی همان صفحۀ پیش‌نویس موجود — جهش: صفحۀ دوم با نامک', $w45_lps, $w45_lp, str_replace( "(int) get_option( 'wp_page_for_privacy_policy' )", "\$find( \$slug, 'fa' )", $w45_lp ) );
