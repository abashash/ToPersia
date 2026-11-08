<?php
/**
 * دستۀ 46 بخش ب — بازطراحی صفحۀ اول (P5-D513 تا P5-D515).  از assert.php بار می‌شود.
 * رفتار تابع‌ها روی خودِ متن کد سنجیده می‌شود: تابع از پرونده بیرون کشیده، با دادۀ ساختگی اجرا می‌شود؛
 * جهش همان متن را عوض می‌کند و باید بیفتد. رفتار روی وردپرس واقعی: tests/w45-local.php.
 */

echo "\nدستۀ 46 — صفحۀ اول تازه\n" . str_repeat( '─', 78 ) . "\n";

if ( ! function_exists( 'esc_html__' ) ) {
	require_once __DIR__ . '/stubs.php';
}

$w46_root = dirname( __DIR__ );
$w46_src  = (string) @file_get_contents( $w46_root . '/plugin/topersia-render/includes/topersia-g-home.php' );
$w46_css  = (string) @file_get_contents( $w46_root . '/plugin/topersia-render/assets/topersia-g-home.css' );
$w46_svgf = $w46_root . '/plugin/topersia-render/assets/home/iran-provinces.svg';
$w46_svg  = (string) @file_get_contents( $w46_svgf );
$w46_sch  = (string) @file_get_contents( $w46_root . '/plugin/topersia-schema/topersia-schema.php' );

/* eval فقط روی متن کد خودِ همین مخزن (پروندۀ افزونه یا جهش ساختۀ همین آزمون)، هرگز ورودی بیرونی. */
/** تابع NAME را از متن کد بیرون می‌کشد، فراخوانی‌های وابسته را به نسخۀ ساختگی برمی‌گرداند و با نام یکتا تعریف می‌کند. */
function w46_load( $src, $name, array $swap = array() ) {
	static $n = 0;
	if ( ! preg_match( '/function ' . preg_quote( $name, '/' ) . '\(.*?\n\t}\n/s', $src, $m ) ) {
		return null;
	}
	$fn   = 'w46_f' . ( ++$n );
	$code = preg_replace( '/^function ' . preg_quote( $name, '/' ) . '\(/', 'function ' . $fn . '(', $m[0] );
	$code = strtr( $code, $swap );
	try {
		eval( $code );
	} catch ( \Throwable $e ) {
		return null;
	}
	return $fn;
}

/* داده‌ی ساختگی شمار: نوع نخست هر پرسش ← تعداد. */
$GLOBALS['w46_n'] = array();
function w46_real( array $types ) {
	$k = $types[0];
	return array_fill( 0, isset( $GLOBALS['w46_n'][ $k ] ) ? $GLOBALS['w46_n'][ $k ] : 0, 1 );
}
function w46_groups() {
	return array( 'heritage' => array( 'g', array( 'tp_monument' ) ) );
}
$w46_cswap = array( 'topersia_g_home_real(' => 'w46_real(', 'topersia_g_home_groups(' => 'w46_groups(' );

/* ۱ — نوار شمار: صفر پنهان؛ کمتر از سه عدد ← هیچ. */
$w46_counts = function ( $s ) use ( $w46_cswap ) {
	$fn = w46_load( $s, 'topersia_g_home_counts', $w46_cswap );
	if ( ! $fn ) { return false; }
	$GLOBALS['w46_n'] = array( 'tp_province' => 31, 'tp_city' => 114, 'tp_village' => 0, 'tp_monument' => 18, 'tp_festival' => 0, 'tp_route' => 1 );
	$a = $fn();
	$GLOBALS['w46_n'] = array( 'tp_province' => 1, 'tp_city' => 1 );
	$b = $fn();
	$nums = array_map( function ( $r ) { return $r[0]; }, $a );
	return array( 31, 114, 18, 1 ) === $nums && array() === $b;
};
tp441_mut( 'نوار شمار: عدد صفر پنهان — جهش: صافی صفر برداشته شود', $w46_counts, $w46_src, str_replace( 'return $r[0] >= 1;', 'return true;', $w46_src ) );
tp441_mut( 'نوار شمار: کمتر از سه عدد ← کل نوار پنهان — جهش: بی آستانه', $w46_counts, $w46_src, str_replace( 'count( $rows ) >= 3 ? $rows : array()', '$rows', $w46_src ) );

/* ۲ — بخش بی داده اصلاً چاپ نمی‌شود. */
$w46_section = function ( $s ) {
	$fn = w46_load( $s, 'topersia_g_home_section' );
	if ( ! $fn ) { return false; }
	$full = $fn( 'tp-home-x', 'T', '<p>x</p>' );
	return '' === $fn( 'tp-home-x', 'T', '' ) && '' === $fn( 'tp-home-x', 'T', '   ' ) && false !== strpos( $full, 'id="tp-home-x"' ) && false !== strpos( $full, '<h2 id="tp-home-xTitle">' );
};
tp441_mut( 'بخش بی‌داده پنهان است (بدنۀ خالی ← هیچ) — جهش: سرفصل تنها چاپ شود', $w46_section, $w46_src, str_replace( "if ( '' === trim( (string) \$body ) ) {\n\t\t\treturn '';", "if ( false ) {\n\t\t\treturn '';", $w46_src ) );

/* ۳ — نمونه هرگز: هر پرسش صفحه (دروازۀ مشترک و پرسش اخبار) شرط _tp_sample و دروازۀ انتشار دارد. */
$w46_sample = function ( $s ) {
	$code = preg_replace( '#/\*.*?\*/#s', '', $s );
	$gp   = substr_count( $code, 'get_posts(' );
	$smp  = substr_count( $code, "array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' )" );
	return $gp >= 2 && $gp === $smp && substr_count( $code, 'topersia_g_published(' ) >= $gp;
};
tp441_mut( 'هر پرسش صفحۀ اول نمونه را بیرون می‌گذارد و از دروازۀ انتشار می‌گذرد — جهش: پرسش اخبار بی شرط نمونه', $w46_sample, $w46_src, preg_replace( "/(tpc_news.*?)'meta_query'\s*=> array\( array\( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' \) \),/s", '$1', $w46_src ) );
$w46_nodirect = function ( $s ) {
	return 0 === preg_match( '/get_post_meta\(\s*\$(id|pid|one)\b/', $s );
};
tp441_mut( 'فیلد موجودیت فقط از topersia_g_raw (دروازۀ زبان P5-D278)، نه get_post_meta مستقیم — جهش: مستقیم', $w46_nodirect, $w46_src, $w46_src . "\nget_post_meta( \$id, 'hero_image', true );" );

/* ۴ — نقشه: ≤ ۸۰KB، همان خروجی مولد، ۳۱ استان؛ پیوند فقط برای استان دارای صفحه. */
$w46_svgok = function ( $svg ) {
	return '' !== $svg && strlen( $svg ) <= 80 * 1024 && 31 === preg_match_all( '#<path id="tp-prov-[a-z0-9-]+" data-tp-prov="[a-z0-9-]+" d="[^"]+"/>#', $svg );
};
tp441_mut( 'SVG استان‌ها ≤ ۸۰KB و ۳۱ مسیر — جهش: پرونده بزرگ', $w46_svgok, $w46_svg, $w46_svg . str_repeat( ' ', 81 * 1024 ) );
$w46_php = defined( 'TP_PHP' ) ? TP_PHP : PHP_BINARY;
exec( escapeshellarg( $w46_php ) . ' ' . escapeshellarg( $w46_root . '/scripts/tp-home-map-svg.php' ) . ' --check 2>&1', $w46_o, $w46_rc );
t( 'SVG ثبت‌شده همان خروجی scripts/tp-home-map-svg.php است (--check)', 0 === $w46_rc, implode( ' ', (array) $w46_o ) );

$w46_mapf = function ( $s ) use ( $w46_svgf ) {
	$fn = w46_load( $s, 'topersia_g_home_map', array( "dirname( __DIR__ ) . '/assets/home/iran-provinces.svg'" => var_export( $w46_svgf, true ) ) );
	if ( ! $fn ) { return false; }
	$one  = $fn( array( 'isfahan' => 7 ) );
	$none = $fn( array() );
	return 1 === substr_count( $one, '<a class="tp-home-map__a"' ) && 30 === substr_count( $one, 'tp-home-map__off' )
		&& false !== strpos( $one, 'aria-label=' ) && false !== strpos( $one, '<figcaption' ) && '' === $none;
};
tp441_mut( 'نقشه: فقط استانِ دارای صفحه پیوند و aria-label دارد، بقیه کم‌رنگ؛ هیچ پیوند ← هیچ نقشه — جهش: استان بی‌صفحه پنهان', $w46_mapf, $w46_src, str_replace( "\$out .= '<path class=\"tp-home-map__off\" d=\"' . esc_attr( \$d ) . '\"/>';", '', $w46_src ) );
tp441_mut( 'نقشه: بی هیچ استان دارای صفحه، نقشه چاپ نمی‌شود — جهش: نقشۀ تهی', $w46_mapf, $w46_src, str_replace( "if ( ! \$links ) {\n\t\t\treturn '';", "if ( false ) {\n\t\t\treturn '';", $w46_src ) );

/* ۵ — سقف‌ها: استان همه (اسلایدر · دستۀ 47 ب)، مقصد ۱۲ (دستۀ 47 پ)، رویداد ۴، عکس ۸ و فقط ۴/۵/۸ (دستۀ 48 بند ۶)، پرسش ۶. */
$w46_caps = function ( $s ) {
	return false !== strpos( $s, 'foreach ( $provinces as $id ) {' )
		&& false !== strpos( $s, "array_filter( \$cities, 'topersia_g_card_image' ) ), 0, 12 )" )
		&& false !== strpos( $s, 'array_slice( array_keys( $events ), 0, 4 )' )
		&& false !== strpos( $s, 'if ( count( $picks ) >= 8 ) {' ) && false !== strpos( $s, '$n     = $n >= 8 ? 8 : ( $n >= 5 ? 5 : $n );' )
		&& false !== strpos( $s, 'count( $items ) < 6' );
};
tp441_mut( 'سقف هر بخش (استان همه · مقصد ۱۲ · رویداد ۴ · عکس ۸ · پرسش ۶) — جهش: سقف پرسش برداشته شود', $w46_caps, $w46_src, str_replace( 'count( $items ) < 6', 'true', $w46_src ) );

/* ۶ — رویداد فقط با تاریخ آغاز معتبر. */
$w46_ev = function ( $s ) {
	return false !== strpos( $s, "topersia_g_date_iso( topersia_g_raw( \$id, 'event_start_date' ) )" ) && false !== strpos( $s, "if ( '' !== \$iso ) {" );
};
tp441_mut( 'رویداد بی event_start_date معتبر نمایش داده نمی‌شود — جهش: بی شرط', $w46_ev, $w46_src, str_replace( "if ( '' !== \$iso ) {", 'if ( true ) {', $w46_src ) );

/* ۷ — FAQPage اسکیما فقط از همان آیتم‌های دیده‌شده. */
$w46_faq = function ( $s ) {
	return 1 === substr_count( $s, 'topersia_g_home_faq_items()' ) && false !== strpos( $s, "'@type' => 'FAQPage'" ) && false === strpos( $s, "get_post_meta( \$post_id, 'faq_items', true ) ) ) );\n\tif ( \$faq )" );
};
tp441_mut( 'FAQPage صفحۀ اول از همان topersia_g_home_faq_items صفحه — جهش: منبع دیگر', $w46_faq, $w46_sch, str_replace( 'topersia_g_home_faq_items()', 'topersia_g_home_faq_other()', $w46_sch ) );

/* ۸ — هیرو: تنها تصویر eager با fetchpriority؛ بقیه lazy با ابعاد. */
$w46_img = function ( $s ) {
	return 1 === substr_count( $s, "'fetchpriority' => 'high'" ) && 1 === substr_count( $s, "'loading'       => 'eager'" ) && false !== strpos( $s, "'loading'  => 'lazy'" ) && false !== strpos( $s, 'rel="preload" as="image"' );
};
tp441_mut( 'هیرو تنها تصویر eager/fetchpriority با preload؛ بقیه lazy — جهش: کارت eager', $w46_img, $w46_src, str_replace( "'loading'  => 'lazy'", "'loading'  => 'eager'", $w46_src ) );

/* ۹ — شیوه‌نامه: شیشه فقط سه جا + سربرگ چسبان، با بازگشت؛ حرکت و شفافیت کم؛ content-visibility؛ بی رنگ خام جز scrim. */
$w46_cssok = function ( $c ) {
	$ok_sel = true;
	if ( ! preg_match_all( '/([^{}]*)\{[^}]*backdrop-filter:blur\(14px\)/', $c, $mm ) ) { return false; }
	foreach ( $mm[1] as $s ) {
		foreach ( array_map( 'trim', explode( ',', preg_replace( '#/\*.*?\*/#s', '', $s ) ) ) as $one ) {
			if ( '' !== $one && false === strpos( $one, '.tp-home-glass' ) && false === strpos( $one, '.whb-sticky-clone.whb-sticked' ) ) { $ok_sel = false; }
		}
	}
	$bare = preg_replace( '#/\*.*?\*/#s', '', preg_replace( '/\.tp-home-hero__scrim\{[^}]*\}/', '', $c ) );
	/* دستۀ 47: جابه‌جایی ایستای نوار شمار (نیم‌هم‌پوشانی دقیق) و راهنمای نقشه حرکت نیست — فقط همین دو قاعده بیرون از سنجش حرکت. */
	$still     = preg_replace( '/\.tp-home-(stats|map__tip)\{[^}]*\}/', '', $c );
	$motion_ok = 0 === preg_match_all( '/transition|animation|transform:translate/', preg_replace( '/@media \(prefers-reduced-motion:no-preference\)\{.*?
\}/s', '', $still ) );
	return $ok_sel && $motion_ok
		&& false !== strpos( $c, '@supports not ((backdrop-filter:blur(1px)) or (-webkit-backdrop-filter:blur(1px)))' )
		&& false !== strpos( $c, '@media (prefers-reduced-transparency:reduce)' )
		&& false !== strpos( $c, '.tp-home-lazy{content-visibility:auto;contain-intrinsic-size:' )
		&& 0 === preg_match( '/#[0-9a-fA-F]{3,8}\b|rgba?\(|hsla?\(/', $bare )
		&& 0 === preg_match( '/letter-spacing:(?!0[;}])/', $c );
};
tp441_mut( 'شیوه‌نامۀ صفحۀ اول: فقط توکن (جز scrim)، شیشه فقط .tp-home-glass و سربرگ چسبان — جهش: رنگ خام', $w46_cssok, $w46_css, $w46_css . "\nhtml .tp-page.tp-home .tp-x{color:#fff}" );
tp441_mut( 'شیوه‌نامۀ صفحۀ اول: شیشه روی کاشی تکراری نه — جهش: شیشه روی کارت', $w46_cssok, $w46_css, $w46_css . "\nhtml .tp-page.tp-home .tp-home-card{-webkit-backdrop-filter:blur(14px) saturate(1.2);backdrop-filter:blur(14px) saturate(1.2)}" );
tp441_mut( 'شیوه‌نامۀ صفحۀ اول: بازگشت مات برای مرورگر بی backdrop-filter و کاربر «شفافیت کم» — جهش: بی @supports', $w46_cssok, $w46_css, str_replace( '@supports not', '@supports', $w46_css ) );
tp441_mut( 'شیوه‌نامۀ صفحۀ اول: حرکت فقط زیر prefers-reduced-motion:no-preference — جهش: گذار بیرون از آن', $w46_cssok, $w46_css, $w46_css . "
html .tp-page.tp-home .tp-home-photo{transition:opacity .2s}" );
tp441_mut( 'شیوه‌نامۀ صفحۀ اول: بخش‌های زیر تاخوردگی content-visibility دارند — جهش: بی آن', $w46_cssok, $w46_css, str_replace( 'content-visibility:auto', 'content-visibility:visible', $w46_css ) );

/* ۱۰ — شیوه‌نامه فقط روی صفحۀ اول بار می‌شود. */
$w46_enq = function ( $s ) {
	return (bool) preg_match( "/if \( ! topersia_g_home_is\(\) \|\| ! function_exists\( 'topersia_render_url' \) \) \{\s*return;\s*\}\s*wp_enqueue_style\( 'topersia-g-home'/", $s );
};
tp441_mut( 'topersia-g-home.css فقط روی صفحۀ اول — جهش: همه‌جا', $w46_enq, $w46_src, str_replace( '! topersia_g_home_is() || ', '', $w46_src ) );
