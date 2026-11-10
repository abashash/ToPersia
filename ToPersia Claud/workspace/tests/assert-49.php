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
	return false !== strpos( $s, '$max = TOPERSIA_HOME_PHOTOS_MAX,' ) && false !== strpos( $s, 'if ( count( $out ) >= $max ) {' )
		&& false !== strpos( $s, "topersia_g_home_slider( 'tp-home-photoslide'" )
		&& false !== strpos( $s, 'if ( $att && ! isset( $own[ $att ] ) && wp_attachment_is_image( $att ) ) {' )   /* یکتا: هر پیوست فقط یک بار (P5-D569) */
		&& false !== strpos( $s, 'if ( $n < 4 ) {' )
		&& 0 === preg_match( '/tp-home-photos(?!lide)/', $s );
};
tp441_mut( 'بند ۲: گالری اسلایدر tp-home-photoslide، سقف ۱۲، پیوست یکتا، کمتر از ۴ ← پنهان، بی گرید ۴/۵/۸ — جهش: سقف ۸', $w49_gal, $w49_home,
	str_replace( '$max = TOPERSIA_HOME_PHOTOS_MAX,', '$max = 8,', $w49_home ) );
tp441_mut( 'بند ۲: هر عکس یکتا — جهش: بی ردکردن تکرار', $w49_gal, $w49_home,
	str_replace( '! isset( $own[ $att ] ) && ', '', $w49_home ) );

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
		&& false !== strpos( $c, '.tp-home-lazy#tp-home-guide{contain-intrinsic-size:auto 240px}' )   /* دستۀ 50: گالری درون ردیف پایانی، راهنما تمام‌عرض */
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

/* ── بخش ۲ — آرشیو خبر P5-D534 ── */
echo "\nدستۀ 49 — آرشیو خبر\n" . str_repeat( '─', 78 ) . "\n";
$w49_nui  = $w49_read( 'plugin/topersia-render/includes/topersia-g-news-ui.php' );
$w49_news = $w49_read( 'plugin/topersia-render/includes/topersia-g-news.php' );
$w49_ntpl = $w49_read( 'plugin/topersia-render/templates/news-archive.php' );
$w49_ltpl = $w49_read( 'plugin/topersia-render/templates/news-list.php' );
$w49_ncss = $w49_read( 'plugin/topersia-render/assets/topersia-g-news.css' );
$w49_njs  = $w49_read( 'plugin/topersia-render/assets/topersia-g-news.js' );
$w49_wpml = $w49_read( 'plugin/topersia-render/wpml-config.xml' );

$w49_tax = function ( $s ) {
	return false !== strpos( $s, "\$on = function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type();" )
		&& false !== strpos( $s, "if ( ! topersia_news_topic_enabled() || ! function_exists( 'register_taxonomy' ) ) {" )
		&& false !== strpos( $s, "register_taxonomy( 'tpc_news_topic', topersia_g_content_types(), array(" )   /* موضوع مشترک خبر و مقاله — P5-D567 */
		&& false !== strpos( $s, "'rewrite'            => false," ) && false !== strpos( $s, "'query_var'          => false," );
};
tp441_mut( 'P5-D534: موضوع خبر tpc_news_topic فقط محلی (پیش از ممیزی)، بی نشانی و query_var خودش — جهش: بی دروازۀ محلی', $w49_tax, $w49_nui,
	str_replace( "if ( ! topersia_news_topic_enabled() || ! function_exists( 'register_taxonomy' ) ) {", "if ( ! function_exists( 'register_taxonomy' ) ) {", $w49_nui ) );
t( 'P5-D534: موضوع ترجمه‌پذیر و زمان مطالعه ignore در wpml-config',
	false !== strpos( $w49_wpml, '<taxonomy translate="1">tpc_news_topic</taxonomy>' ) && false !== strpos( $w49_wpml, '<custom-field action="ignore">_tp_read_minutes</custom-field>' ) );

$w49_read_m = function ( $s ) {
	return false !== strpos( $s, "update_post_meta( \$post_id, '_tp_read_minutes', topersia_g_news_read_minutes_calc( \$post_id ) );" )
		&& false !== strpos( $s, "add_action( 'save_post_' . \$topersia_ct, function ( \$post_id ) {" )
		&& false !== strpos( $s, 'return $words ? max( 1, (int) ceil( count( $words ) / 200 ) ) : 0;' );
};
tp441_mut( 'P5-D534: زمان مطالعه هنگام ذخیره در متا (۲۰۰ واژه در دقیقه، کمینه ۱) — جهش: بی ذخیره', $w49_read_m, $w49_nui,
	str_replace( "update_post_meta( \$post_id, '_tp_read_minutes'", "get_post_meta( \$post_id, '_tp_read_minutes'", $w49_nui ) );

$w49_args = function ( $s ) {
	return false !== strpos( $s, "sanitize_text_field( (string) get_query_var( 'tp_q' ) )" )
		&& false !== strpos( $s, 'mb_substr( $q, 0, 80 )' )
		&& false !== strpos( $s, "'tp_sort'  => 'old' === get_query_var( 'tp_sort' ) ? 'old' : ''," );
};
tp441_mut( 'D468: ورودی پالایش پاک‌سازی‌شده، جست‌وجو تا ۸۰ نویسه، ترتیب فقط old یا تهی — جهش: ترتیب خام', $w49_args, $w49_nui,
	str_replace( "'tp_sort'  => 'old' === get_query_var( 'tp_sort' ) ? 'old' : '',", "'tp_sort'  => (string) get_query_var( 'tp_sort' ),", $w49_nui ) );

$w49_kind = function ( $s ) {
	return 1 === preg_match( '/\$wpdb->prepare\(\s*"SELECT DISTINCT parent_object_id FROM \{\$table\} WHERE rel_id = %d"/', $s );
};
tp441_mut( 'D468: پالایش نوع مکان با SQL آماده‌شده روی جدول رابطه — جهش: بی prepare', $w49_kind, $w49_nui,
	preg_replace( '/\$wpdb->prepare\(\s*"SELECT DISTINCT/', '( "SELECT DISTINCT', $w49_nui ) );

$w49_seo = function ( $s ) {
	return false !== strpos( $s, 'return topersia_g_news_filtered() || count( topersia_g_news_archive_ids() ) < 3;' )
		&& false !== strpos( $s, "topersia_g_news_archive_url( topersia_g_news_filtered() ? 1 : max( 1, (int) get_query_var( 'paged' ) ) )" )
		&& false !== strpos( $s, "return \$q['total'] < 3 || '' !== (string) get_query_var( 'tp_topic' ) || '' !== (string) get_query_var( 'tp_sort' );" );
};
tp441_mut( 'D468: حالت پالایش‌شده noindex و canonical به نسخۀ بی‌پالایش — جهش: canonical با پالایش', $w49_seo, $w49_news,
	str_replace( 'topersia_g_news_filtered() ? 1 : max(', 'false ? 1 : max(', $w49_news ) );
tp441_mut( 'D468: صفحۀ مکان با موضوع یا ترتیب هم noindex — جهش: فقط آستانه', $w49_seo, $w49_news,
	str_replace( "return \$q['total'] < 3 || '' !== (string) get_query_var( 'tp_topic' ) || '' !== (string) get_query_var( 'tp_sort' );", "return \$q['total'] < 3;", $w49_news ) );

$w49_get = function ( $t, $l ) {
	return 1 === substr_count( $t, '<form id="tp-news-form" class="tp-news-filter" method="get"' ) && 1 === substr_count( $l, '<form class="tp-news-filter tp-news-filter--min" method="get"' )
		&& 0 === preg_match( '/method="post"|wp_nonce_field/i', $t . $l );
};
t( 'D468: پالایش فقط GET — هر دو قالب', $w49_get( $w49_ntpl, $w49_ltpl ) );
t( 'D468: جهش — فرم POST می‌افتد', ! $w49_get( str_replace( 'method="get"', 'method="post"', $w49_ntpl ), $w49_ltpl ) );

$w49_img = function ( $s ) {
	return false !== strpos( $s, "return ( \$att && wp_attachment_is_image( \$att ) && ! metadata_exists( 'post', \$att, '_tp_sample' ) ) ? \$att : 0;" )
		&& 2 === substr_count( $s, "metadata_exists( 'post', \$pid, '_tp_sample' )" ) + substr_count( $s, "metadata_exists( 'post', \$place_id, '_tp_sample' )" )
		&& false !== strpos( $s, 'topersia_g_news_real_img( topersia_g_home_hero_att()[0] )' )
		&& 0 === preg_match( '#https?://#', preg_replace( '#/\*.*?\*/#s', '', $s ) );
};
tp441_mut( 'P5-D534/D535: تصویر خبر و سربرگ فقط پیوست همین سایت، هرگز نمونه؛ خود ← مکان ← آیکون؛ بی نشانی بیرونی — جهش: پیوست نمونه پذیرفته', $w49_img, $w49_nui,
	str_replace( " && ! metadata_exists( 'post', \$att, '_tp_sample' )", '', $w49_nui ) );
tp441_mut( 'P5-D534: سربرگ بی‌پالایش از همان انتخاب صفحۀ اول — جهش: تصویر دیگر', $w49_img, $w49_nui,
	str_replace( 'topersia_g_news_real_img( topersia_g_home_hero_att()[0] )', 'topersia_g_news_real_img( topersia_g_card_image( 1 ) )', $w49_nui ) );

$w49_ntok = function ( $c ) {
	return '' !== $c && ! preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|letter-spacing/i', $c )
		&& false !== strpos( $c, 'color-mix(in srgb,var(--tp-media-ink)' ) && false !== strpos( $c, 'aspect-ratio:16/10' )
		&& false !== strpos( $c, '-webkit-line-clamp:2' ) && false !== strpos( $c, 'grid-template-columns:repeat(4,minmax(0,1fr))' );
};
tp441_mut( 'P5-D534 CSS: پوشش تیره از توکن --tp-media-ink، کارت ۱۶:۱۰، دو خط، چهار ستون، بی رنگ خام — جهش: rgba', $w49_ntok, $w49_ncss,
	preg_replace( '/color-mix\(in srgb,var\(--tp-media-ink\) (\d+)%,transparent\)/', 'rgba(0,0,0,.$1)', $w49_ncss, 1 ) );
$w49_js = function ( $s ) {
	return false !== strpos( $s, 'window.localStorage.getItem( KEY )' ) && 2 <= substr_count( $s, '} catch ( e ) {' )
		&& 0 === preg_match( "/\\.submit\\(\\)|requestSubmit/", $s );   /* دستۀ 50 (P5-D546): تغییر فهرست کشویی بی‌رفرش جای‌گزین می‌شود و aria-live اعلام می‌کند — هرگز ارسال کامل فرم */
};
tp441_mut( 'P5-D534 JS: شبکه/فهرست با localStorage در try، بی ارسال کامل فرم با تغییر (بی‌رفرش P5-D546) — جهش: ارسال با change', $w49_js, $w49_njs,
	str_replace( "var KEY = 'tp-news-view';", "var KEY = 'tp-news-view'; document.addEventListener( 'change', function ( e ) { e.target.form.submit(); } );", $w49_njs ) );
$w49_feat = function ( $s ) {
	return false !== strpos( $s, 'hidden data-tp-news-view>' ) && false !== strpos( $s, 'topersia_g_news_feature( $ids[0] )' )   /* P5-D556: بلوک بالا در topersia_g_news_top() */
		&& false !== strpos( $s, 'topersia_g_news_mini( $one )' ) && 0 === preg_match( '/newsletter|خبرنامه/u', $s );
};
tp441_mut( 'P5-D534 قالب: خبر برگزیده + کناری‌ها (P5-D556)، دکمۀ نما پنهان تا JS (جزء مشترک دستۀ 50)، بی خبرنامه — جهش: خبرنامه', $w49_feat, $w49_ntpl . $w49_nui,
	str_replace( 'hidden data-tp-news-view>', 'hidden data-tp-news-view data-newsletter>', $w49_ntpl . $w49_nui ) );
$w49_inv = function ( $s ) {
	return false !== strpos( $s, "foreach ( array( 'tp_place', 'tp_loc', 'tp_kind', 'tp_topic', 'tp_q', 'tp_sort', 'tp_when' ) as \$qv ) {" )
		&& false !== strpos( $s, "if ( ! is_scalar( \$v ) || ( '' !== trim( (string) \$v ) && '0' !== (string) \$v ) ) {" );
};
tp441_mut( 'D468: مقدار نامعتبر پالایش (موضوع یا مکان ناموجود) هم پالایش‌شده است ← noindex و پیام آرام — جهش: فقط مقدار معتبر', $w49_inv, $w49_nui,
	str_replace( "foreach ( array( 'tp_place', 'tp_loc', 'tp_kind', 'tp_topic', 'tp_q', 'tp_sort', 'tp_when' ) as \$qv ) {", "foreach ( array() as \$qv ) {", $w49_nui ) );
