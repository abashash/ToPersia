<?php
/**
 * دستۀ 49 — بخش ۱: صفحۀ اول (P5-D533 · بندهای ۲ تا ۵)، پاورقی (P5-D537)، جست‌وجوی سراسری (P5-D536).
 * از assert.php بار می‌شود؛ خواندن کد با جهش، و تاکردن با تابع واقعی هسته. رفتار روی وردپرس واقعی: tests/w49-local.js.
 */

echo "\nدستۀ 49 — صفحۀ اول، پاورقی، جست‌وجو\n" . str_repeat( '─', 78 ) . "\n";

$w49_root   = dirname( __DIR__ );
$w49_read   = function ( $p ) use ( $w49_root ) { return (string) @file_get_contents( $w49_root . '/' . $p ); };
$w49_home   = $w49_read( 'plugin/topersia-render/includes/topersia-g-home.php' );
$w49_css    = $w49_read( 'plugin/topersia-render/assets/topersia-g-home.css' );
$w49_bridge = $w49_read( 'plugin/topersia-render/assets/topersia-theme-bridge.css' );
$w49_srch   = $w49_read( 'plugin/topersia-render/includes/topersia-g-search.php' );
$w49_tpl    = $w49_read( 'plugin/topersia-render/templates/search-results.php' );
$w49_scss   = $w49_read( 'plugin/topersia-render/assets/topersia-g-search.css' );
$w49_fold   = $w49_read( 'plugin/topersia-core/includes/topersia-fold.php' );
$w49_render = $w49_read( 'plugin/topersia-render/topersia-render.php' );
$w49_schema = $w49_read( 'plugin/topersia-schema/topersia-schema.php' );

/* ── بند ۲: ایران در تصویر — اسلایدر یک‌ردیفه تا ۱۲ عکس یکتا، فقط با ≥ ۴ ── */
$w49_gal = function ( $s ) {
	return false !== strpos( $s, 'if ( count( $picks ) >= 12 ) {' )
		&& false !== strpos( $s, "topersia_g_home_slider( 'tp-home-photoslide'" )
		&& false !== strpos( $s, 'if ( ! $att || isset( $seen[ $att ] ) || ! wp_attachment_is_image( $att ) ) {' )
		&& false !== strpos( $s, 'if ( $n < 4 ) {' )
		&& 0 === preg_match( '/tp-home-photos(?!lide)/', $s );
};
tp441_mut( 'بند ۲: گالری اسلایدر tp-home-photoslide، سقف ۱۲، پیوست یکتا، کمتر از ۴ ← پنهان، بی گرید ۴/۵/۸ — جهش: سقف ۸', $w49_gal, $w49_home,
	str_replace( 'if ( count( $picks ) >= 12 ) {', 'if ( count( $picks ) >= 8 ) {', $w49_home ) );
tp441_mut( 'بند ۲: هر عکس یکتا — جهش: بی ردکردن تکرار', $w49_gal, $w49_home,
	str_replace( '|| isset( $seen[ $att ] ) ', '', $w49_home ) );

/* ── P5-D533: دکمۀ بخش در ردیف عنوان ── */
$w49_btn = function ( $s ) {
	$tiles = preg_match( '/<div class="tp-home-prov__tiles">.*?\$dlg : \'\';/s', $s, $m ) ? $m[0] : '';
	return false !== strpos( $s, "if ( is_string( \$more ) ) {\n\t\t\t\$link = \$more;" )
		&& false !== strpos( $s, "\$prov_more = ( '' !== \$prov_body && '' !== \$dlg ) ? '<button type=\"button\" class=\"tp-home-slider__all\" data-tp-provdlg" )
		&& '' !== $tiles && false === strpos( $tiles, 'data-tp-provdlg' )
		&& false !== strpos( $s, "topersia_g_home_section( 'tp-home-provinces', __( 'استان‌های ایران', 'topersia' ), \$prov_body, \$prov_more )" );
};
tp441_mut( 'P5-D533: دکمۀ «همۀ استان‌ها» در سرفصل بخش، نه زیر اسلایدر — جهش: دکمه درون ستون کاشی', $w49_btn, $w49_home,
	str_replace( "'<div class=\"tp-home-prov__tiles\">' . topersia_g_home_slider(", "'<div class=\"tp-home-prov__tiles\"><button data-tp-provdlg></button>' . topersia_g_home_slider(", $w49_home ) );
$w49_head = function ( $c ) {
	return 1 === preg_match( '/\.tp-home-sec > div\.tp-section-head\{display:flex;flex-direction:row;flex-wrap:nowrap;align-items:center;justify-content:space-between;/', $c )
		&& 1 === preg_match( '/\.tp-home-sec > div\.tp-section-head > div\{display:block;flex:1 1 auto;min-inline-size:0\}/', $c )
		&& 1 === preg_match( '/\.tp-home-sec > div\.tp-section-head > :is\(\.tp-more,\.tp-home-slider__all\)\{flex:0 0 auto;white-space:nowrap;min-block-size:44px/', $c );
};
tp441_mut( 'P5-D533 CSS: سرفصل یک ردیف space-between، عنوان می‌شکند، دکمه nowrap و ≥ ۴۴px — جهش: دکمه می‌شکند', $w49_head, $w49_css,
	str_replace( 'flex:0 0 auto;white-space:nowrap;min-block-size:44px', 'flex:0 0 auto;min-block-size:44px', $w49_css ) );

/* ── P5-D533: استان‌ها فقط ≥ ۱۰۲۴ دو ردیف ── */
$w49_rows = function ( $c ) {
	$media = preg_match( '/@media \(min-width:1024px\)\{(.*?)\n\}\n/s', $c, $m ) ? $m[1] : '';
	return false !== strpos( $media, '.tp-home-prov__tiles .tp-home-slider{grid-template-rows:repeat(2,minmax(0,1fr))}' )
		&& 1 === substr_count( $c, 'grid-template-rows:repeat(2' );
};
tp441_mut( 'P5-D533 CSS: دو ردیف فقط برای استان‌ها و فقط در min-width:1024 — جهش: دوردیفۀ همیشگی', $w49_rows, $w49_css,
	$w49_css . "\nhtml .tp-page.tp-home .tp-home-slider{grid-template-rows:repeat(2,auto)}" );

/* ── بند ۴: فاصلۀ نوار شمار ── */
$w49_gap = function ( $c ) {
	return 1 === preg_match( '/\.tp-home-stats\{[^}]*margin:calc\(-1 \* var\(--tp-home-st-ov\)\) 0 0/', $c )
		&& ! preg_match( '/\.tp-home-stats\{[^}]*transform/', $c )
		&& false !== strpos( $c, '.tp-home-stats + .tp-home-sec{margin-block-start:clamp(20px,2.8vw,40px)}' )
		&& false !== strpos( $c, '.tp-home-body > .tp-home-sec:first-child{margin-block-start:clamp(20px,2.8vw,40px)}' );
};
tp441_mut( 'بند ۴: نوار شمار با هم‌پوشانی ثابت، بی translateY(-50%)؛ فاصلۀ بخش بعد ≤ ۴۰px — جهش: translate بازگردد', $w49_gap, $w49_css,
	str_replace( 'border-radius:var(--tp-home-r)}' . "\n" . 'html .tp-page.tp-home .tp-home-stats ul', 'border-radius:var(--tp-home-r);transform:translateY(-50%)}' . "\n" . 'html .tp-page.tp-home .tp-home-stats ul', $w49_css ) );
$w49_cis = function ( $c ) {
	return false !== strpos( $c, '.tp-home-lazy{content-visibility:auto;contain-intrinsic-size:auto 440px}' )
		&& false !== strpos( $c, '.tp-home-lazy#tp-home-gallery{contain-intrinsic-size:auto 340px}' )
		&& false !== strpos( $c, '.tp-home-lazy#tp-home-events{contain-intrinsic-size:auto 260px}' )
		&& 4 <= substr_count( $c, '.tp-home-trio.tp-home-lazy{contain-intrinsic-size:auto ' )
		&& false === strpos( $c, 'contain-intrinsic-size:640px' );
};
tp441_mut( 'بند ۴: contain-intrinsic-size هر بخش نزدیک بلندی واقعی، با auto (CLS صفر) — جهش: بی auto', $w49_cis, $w49_css,
	str_replace( 'contain-intrinsic-size:auto 440px', 'contain-intrinsic-size:640px', $w49_css ) );

/* ── بند ۵: نوار اسکرول ── */
$w49_bar = function ( $c ) {
	return 1 === preg_match( '/\.tp-home-slider\{display:grid;[^}]*scrollbar-width:thin;[^}]*padding:6px 6px 16px;margin-inline:-6px;scroll-padding-inline:6px\}/', $c )
		&& 1 === preg_match( '/@media \(max-width:768px\)\{[^@]*\.tp-home-slider\{scrollbar-width:none;[^}]*\}[^@]*\.tp-home-slider::-webkit-scrollbar\{display:none\}/', $c );
};
tp441_mut( 'بند ۵: ۱۶px میان کارت و نوار، فضای سایه، موبایل نوار پنهان — جهش: ۶px', $w49_bar, $w49_css,
	str_replace( 'padding:6px 6px 16px;', 'padding-block-end:6px;', $w49_css ) );

/* ── P5-D537: پاورقی فقط CSS، ≥ ۱۲px، هرگز nowrap ── */
$w49_foot = function ( $c ) {
	$blk = preg_match( '/۶ — اندازۀ متن کپی‌رایت پاورقی.*$/s', $c, $m ) ? $m[0] : '';
	$blk = preg_replace( '#/\*.*?\*/#s', '', (string) substr( $blk, (int) strpos( $blk, '*/' ) + 2 ) );   /* توضیح سرفصل بیرون */
	return '' !== $blk && false !== strpos( $blk, 'font-size:clamp(12px,1.09cqi,14px)' )
		&& false !== strpos( $blk, '.wd-copyrights > .container{container-type:inline-size}' )
		&& false === strpos( $blk, 'nowrap' ) && false === strpos( $blk, 'display:none' );
};
tp441_mut( 'P5-D537: کپی‌رایت پاورقی clamp(12px…14px) نسبت به ظرف، بی nowrap (بی سرریز) — جهش: nowrap', $w49_foot, $w49_bridge,
	$w49_bridge . "\nhtml:root .wd-copyrights p{white-space:nowrap}" );
t( 'P5-D537: خروجی پاورقی افزونه نیست (فقط CSS)', false === strpos( $w49_home . $w49_srch, 'tp-footer' ) );

/* ── P5-D536: جست‌وجوی سراسری ── */
$w49_q = function ( $s ) {
	return false !== strpos( $s, "\$q->set( 'meta_query', array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) );" )
		&& false !== strpos( $s, "\$q->set( 'post_status', 'publish' );" )
		&& false !== strpos( $s, "\$q->set( 'has_password', false );" )
		&& false !== strpos( $s, "\$q->set( 'tp_fold_only', 1 );" )
		&& false !== strpos( $s, "0 === strpos( (string) \$type, 'tp_' )" )
		&& false !== strpos( $s, "\$q->set( 'post__in', array( 0 ) );" );
};
tp441_mut( 'P5-D536: کوئری جست‌وجو فقط tp_*، منتشرشده، بی گذرواژه، غیرنمونه، فقط نمایۀ تاشده؛ عبارت تهی ← هیچ — جهش: نمونه هم', $w49_q, $w49_srch,
	str_replace( "'key' => '_tp_sample', 'compare' => 'NOT EXISTS'", "'key' => '_tp_x', 'compare' => 'NOT EXISTS'", $w49_srch ) );
$w49_names = function ( $s ) {
	return false !== strpos( $s, "'names' === \$def['zone']" ) && false !== strpos( $s, "array( 'name_local', 'alternative_names' )" )
		&& false !== strpos( $s, "add_filter( 'topersia_search_fold_fields'" );
};
tp441_mut( 'P5-D536: فیلدهای نام از FIELDMAP (ناحیۀ names) + نام محلی و جایگزین — جهش: بی FIELDMAP', $w49_names, $w49_srch,
	str_replace( "'names' === \$def['zone']", "'x' === \$def['zone']", $w49_srch ) );
$w49_robots = function ( $s ) {
	return false !== strpos( $s, "if ( topersia_g_search_is() ) {\n\t\t\tunset( \$robots['index'], \$robots['max-image-preview'] );\n\t\t\t\$robots['noindex'] = true;" )
		&& false !== strpos( $s, "\$robots['index'] = 'noindex';" );
};
tp441_mut( 'P5-D536: نتیجۀ جست‌وجو noindex (وردپرس و Rank Math) — جهش: بی noindex', $w49_robots, $w49_srch,
	str_replace( "\$robots['noindex'] = true;", '', $w49_srch ) );
$w49_tplf = function ( $s ) {
	return false !== strpos( $s, "topersia_g_published( \$tp_ids )" ) && false !== strpos( $s, "metadata_exists( 'post', \$id, '_tp_sample' )" )
		&& false !== strpos( $s, "__( 'نتیجه‌ای نیست.', 'topersia' )" ) && false !== strpos( $s, 'topersia_g_search_tiles( $tp_ids )' );
};
tp441_mut( 'P5-D536: قالب نتیجه از دروازۀ انتشار و بی نمونه، کاشی‌های موجود، «نتیجه‌ای نیست.» — جهش: بی دروازه', $w49_tplf, $w49_tpl,
	str_replace( "topersia_g_published( \$tp_ids )", '$tp_ids', $w49_tpl ) );
t( 'P5-D536: پروندۀ جست‌وجو در فهرست بار render', false !== strpos( $w49_render, "'includes/topersia-g-search.php'" ) );
t( 'P5-D536: فرم هیرو زیر زیرعنوان (پس از tp-home-hero__lead، پیش از دسترسی سریع)', 1 === preg_match( "/tp-home-hero__lead.*?topersia_g_search_form\( 'tp-home-q', '', 'tp-home-search' \).*?tp-home-quick/s", $w49_home ) );
t( 'P5-D536: شیوه‌نامۀ جست‌وجو فقط توکن، بی رنگ خام، هدف ≥ ۴۴px', '' !== $w49_scss && ! preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(/i', $w49_scss ) && false !== strpos( $w49_scss, 'min-block-size:52px' ) );
$w49_core = function ( $s ) {
	return false !== strpos( $s, "if ( \$query->get( 'tp_fold_only' ) ) {\n\t\treturn ' AND ( ' . \$exists . ' ) ';" )
		&& false !== strpos( $s, 'foreach ( topersia_search_fold_fields() as $key ) {' )
		&& false === strpos( $s, "get_post_meta( \$post->ID, 'short_summary', true )" );
};
tp441_mut( 'P5-D536 هسته: نمایۀ تاشده = عنوان و نام‌های جایگزین؛ tp_fold_only فقط نمایه — جهش: خلاصه در نمایه', $w49_core, $w49_fold,
	str_replace( 'foreach ( topersia_search_fold_fields() as $key ) {', "\$s = get_post_meta( \$post->ID, 'short_summary', true );\n\tforeach ( topersia_search_fold_fields() as \$key ) {", $w49_fold ) );
$w49_sa = function ( $s ) {
	$fn = preg_match( '/function topersia_schema_home_json.*?\n}\n/s', $s, $m ) ? $m[0] : '';
	return false !== strpos( $fn, "'@type'       => 'SearchAction'" ) && false !== strpos( $fn, "add_query_arg( 's', '{search_term_string}', \$home )" )
		&& false !== strpos( $fn, "'query-input' => 'required name=search_term_string'" );
};
tp441_mut( 'P5-D536: SearchAction در WebSite صفحۀ اول با نشانی همان فرم (?s=) — جهش: نشانی دیگر', $w49_sa, $w49_schema,
	str_replace( "add_query_arg( 's', '{search_term_string}', \$home )", "'https://example.com/?q={search_term_string}'", $w49_schema ) );

/* تاکردن واقعی: ی/ي، ک/ك، رقم فارسی و عربی، نیم‌فاصله */
if ( function_exists( 'topersia_search_fold' ) ) {
	t( 'تاکردن: «كرمان» = «کرمان»، «شيراز» = «شیراز»', topersia_search_fold( 'كرمان' ) === topersia_search_fold( 'کرمان' ) && topersia_search_fold( 'شيراز' ) === topersia_search_fold( 'شیراز' ) );
	t( 'تاکردن: رقم «۱۴۰۵» و «١٤٠٥» = «1405»', '1405' === topersia_search_fold( '۱۴۰۵' ) && '1405' === topersia_search_fold( '١٤٠٥' ) );
	t( 'تاکردن: «تخت‌جمشید» = «تخت جمشید» = «تختجمشید»', topersia_search_fold( "تخت\u{200C}جمشید" ) === topersia_search_fold( 'تخت جمشید' ) && topersia_search_fold( 'تخت جمشید' ) === topersia_search_fold( 'تختجمشید' ) );
	t( 'تاکردن: جهش — «كرمان» با «کرمان» بی قاعدۀ ک برابر نیست', topersia_search_fold( 'كرمان' ) !== str_replace( 'ک', 'ك', topersia_search_fold( 'کرمان' ) ) );
} else {
	t( 'تاکردن هسته در دسترس (topersia_search_fold)', false );
}
