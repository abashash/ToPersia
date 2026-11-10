<?php
/**
 * دستۀ 50 — صفحه‌های خبر با یک سبک (P5-D540 · P5-D541 · P5-D542 · P5-D544 · P5-D546).
 * از assert.php بار می‌شود؛ خواندن کد با جهش. رفتار روی وردپرس واقعی: tests/w50-local.js.
 */

echo "\nدستۀ 50 — صفحه‌های خبر\n" . str_repeat( '─', 78 ) . "\n";

$w50_root = dirname( __DIR__ );
$w50_read = function ( $p ) use ( $w50_root ) { return (string) @file_get_contents( $w50_root . '/' . $p ); };
$w50_ui   = $w50_read( 'plugin/topersia-render/includes/topersia-g-news-ui.php' );
$w50_news = $w50_read( 'plugin/topersia-render/includes/topersia-g-news.php' );
$w50_arch = $w50_read( 'plugin/topersia-render/templates/news-archive.php' );
$w50_list = $w50_read( 'plugin/topersia-render/templates/news-list.php' );
$w50_one  = $w50_read( 'plugin/topersia-render/templates/news-single.php' );
$w50_css  = $w50_read( 'plugin/topersia-render/assets/topersia-g-news.css' );
$w50_js   = $w50_read( 'plugin/topersia-render/assets/topersia-g-news.js' );

/* ── P5-D541: یک مجموعۀ جزء برای همۀ صفحه‌های خبر، بی کد تکراری ── */
$w50_shared = function ( $a, $l, $o ) {
	foreach ( array( $a, $l, $o ) as $t ) {
		if ( 1 !== substr_count( $t, 'topersia_g_news_hero(' ) || false !== strpos( $t, '<header class="tp-news-hero' ) || false !== strpos( $t, '<nav class="tp-crumb"' ) ) {
			return false;
		}
	}
	return 2 === substr_count( $a . $l, 'topersia_g_news_chips( $tp_topic_items' ) && 2 === substr_count( $a . $l, 'topersia_g_news_view_toggle()' )
		&& 2 === substr_count( $a . $l, 'topersia_g_news_pages( $tp_pages' ) && 1 <= substr_count( $l, 'topersia_g_news_archive_card( $tp_one )' )
		&& 1 <= substr_count( $o, 'topersia_g_news_archive_card( $tp_one )' ) && false === strpos( $l, 'topersia_g_news_card( $tp_one )' );
};
t( 'P5-D541: آرشیو، خبرهای مکان و تک‌خبر همه با سربرگ مشترک؛ تراشه، نما، صفحه‌بندی و کارت یکسان — بی markup تکراری', $w50_shared( $w50_arch, $w50_list, $w50_one ) );
t( 'P5-D541: جهش — سربرگ دستی در یک قالب می‌افتد', ! $w50_shared( $w50_arch, str_replace( 'topersia_g_news_hero(', 'tp_x(', $w50_list ) . '<header class="tp-news-hero">', $w50_one ) );
t( 'P5-D541: جهش — کارت قدیمی در خبرهای مکان می‌افتد', ! $w50_shared( $w50_arch, str_replace( 'topersia_g_news_archive_card( $tp_one )', 'topersia_g_news_card( $tp_one )', $w50_list ), $w50_one ) );

/* ── P5-D541: فاصلۀ صفر هدر تا سربرگ — هر سه صفحه ریشۀ tp-news-page دارند و .tp-main بی padding بالا ── */
$w50_gap = function ( $c, $a, $l, $o ) {
	return false !== strpos( $c, 'html .tp-page.tp-news-page > .tp-main{padding-block-start:0}' )
		&& false !== strpos( $a, '<div class="tp-page tp-news-page tp-news-archive"' ) && false !== strpos( $l, '<div class="tp-page tp-news-page tp-news-place"' )
		&& false !== strpos( $o, '<div class="tp-page tp-news-page tp-news-single"' );
};
t( 'P5-D541: فاصلۀ صفر هدر تا سربرگ در هر سه صفحه (همان شیوۀ صفحۀ اول)', $w50_gap( $w50_css, $w50_arch, $w50_list, $w50_one ) );
t( 'P5-D541: جهش — بی صفرکردن padding .tp-main می‌افتد', ! $w50_gap( str_replace( '> .tp-main{padding-block-start:0}', '> .tp-main{padding-block-start:44px}', $w50_css ), $w50_arch, $w50_list, $w50_one ) );
t( 'P5-D541: تک‌خبر قالب خودش را می‌گیرد (template_include روی is_singular tpc_news)', false !== strpos( $w50_news, "\$file = topersia_render_path() . 'templates/news-single.php';" ) && false !== strpos( $w50_news, "if ( ! is_singular( 'tpc_news' ) || ! function_exists( 'topersia_g_news_hero' ) ) {" ) );
t( 'P5-D541: منبع یک بار — صافی the_content در قالب تک‌خبر منبع را دوباره نمی‌افزاید', false !== strpos( $w50_news, "&& ! defined( 'TOPERSIA_NEWS_SINGLE_TPL' ) ) {" ) && false !== strpos( $w50_one, "define( 'TOPERSIA_NEWS_SINGLE_TPL', true );" ) );

/* ── P5-D542: تراشه‌ها یک خط، هرگز دو خط ── */
$w50_chips = function ( $c ) {
	return 1 === preg_match( '/\.tp-news-chips__list\{[^}]*flex-wrap:nowrap[^}]*overflow-x:auto[^}]*scroll-snap-type:x proximity[^}]*scrollbar-width:none/', $c )
		&& 1 === preg_match( '/\.tp-news-chips a\{[^}]*white-space:nowrap/', $c )
		&& false !== strpos( $c, '.tp-news-chips__list::-webkit-scrollbar{display:none}' )
		&& 0 === preg_match( '/\.tp-news-chips[^{]*\{[^}]*flex-wrap:wrap/', $c );
};
tp441_mut( 'P5-D542: تراشه‌ها در یک خط افقی اسکرولی (nowrap، snap proximity، نوار پنهان) — جهش: flex-wrap:wrap', $w50_chips, $w50_css,
	str_replace( 'flex-wrap:nowrap;gap:8px;flex:1 1 auto', 'flex-wrap:wrap;gap:8px;flex:1 1 auto', $w50_css ) );
$w50_cjs = function ( $s ) {
	return false !== strpos( $s, "list.toggleAttribute( 'data-tp-l', hasL );" ) && false !== strpos( $s, 'prev.hidden = next.hidden = ! over;' )
		&& false !== strpos( $s, "var rtl  = getComputedStyle( list ).direction === 'rtl';" ) && false !== strpos( $s, 'chipReveal( list );' );
};
tp441_mut( 'P5-D542: پیکان فقط با سرریز، محوشدن لبه، تراشۀ فعال در دید، جهت از getComputedStyle — جهش: پیکان همیشه', $w50_cjs, $w50_js,
	str_replace( 'prev.hidden = next.hidden = ! over;', 'prev.hidden = next.hidden = false;', $w50_js ) );

/* ── P5-D542: پنج پالایش + بازۀ زمانی از فهرست بسته روی post_date ── */
$w50_five = function ( $a, $u, $n ) {
	preg_match_all( "/topersia_g_news_select\( 'tp-news-(\w+)', 'tp_(\w+)'/", $a, $m );
	$f = array_merge( $m[2], false !== strpos( $a, 'name="tp_q"' ) ? array( 'q' ) : array(), false !== strpos( $a, "topersia_g_loc_field( 'tp-news-place', \$tp_place )" ) ? array( 'place' ) : array() );   /* P5-D549: مکان combobox متنی */
	sort( $f );
	return array( 'kind', 'place', 'q', 'sort', 'when' ) === $f
		&& false !== strpos( $a, "'tp-news-form' ); // phpcs:ignore" ) && false !== strpos( $a, '<form id="tp-news-form"' )
		&& false !== strpos( $u, "'tp_when'  => isset( topersia_g_news_when_map()[ \$when ] ) ? \$when : ''," )
		&& false !== strpos( $u, "return array( '7d' => '7 days ago', '30d' => '30 days ago', '12m' => '12 months ago' );" )
		&& false !== strpos( $n, "'column' => 'post_date', 'after' => topersia_g_news_when_map()[ \$args['tp_when'] ]" );
};
tp441_mut( 'P5-D542: پنج پالایش (جست‌وجو، مکان، نوع، بازۀ زمانی، ترتیب) — بازه فقط از فهرست بسته روی post_date — جهش: بی بازۀ زمانی', function ( $a ) use ( $w50_five, $w50_ui, $w50_news ) { return $w50_five( $a, $w50_ui, $w50_news ); }, $w50_arch,
	preg_replace( "/\techo topersia_g_news_select\( 'tp-news-when'.*\n/", '', $w50_arch ) );
t( 'P5-D542: جهش — مقدار خام بازه (بی فهرست بسته) می‌افتد', ! $w50_five( $w50_arch, str_replace( "isset( topersia_g_news_when_map()[ \$when ] ) ? \$when : ''", '$when', $w50_ui ), $w50_news ) );

/* ── P5-D542: فهرست کشویی با توکن ── */
$w50_sel = function ( $c ) {
	return 1 === preg_match( '/\.tp-news-sel select\{[^}]*appearance:none/', $c ) && false !== strpos( $c, 'background-image:none' )
		&& 1 === preg_match( '/\.tp-news-sel::after\{[^}]*background:var\(--tp-text-strong\)[^}]*mask:url\("data:image\/svg\+xml/', $c )
		&& false !== strpos( $c, '.tp-news-sel :is(option,optgroup){background:var(--tp-surface);color:var(--tp-text-strong)}' )
		&& 1 === preg_match( '/:is\(\.tp-news-sel select,[^)]*\)\{[^}]*min-block-size:44px[^}]*border:1px solid var\(--tp-text-2\);background:var\(--tp-surface\)/', $c );
};
tp441_mut( 'P5-D542: فهرست کشویی بی ظاهر مرورگر، پیکان SVG به رنگ توکن، گزینه خوانا در شب، ≥ ۴۴px — جهش: بی رنگ option', $w50_sel, $w50_css,
	str_replace( '.tp-news-sel :is(option,optgroup){background:var(--tp-surface);color:var(--tp-text-strong)}', '', $w50_css ) );

/* ── P5-D544 → P5-D556: بلوک «دسته‌بندی‌ها» کاملاً حذف (کد، CSS، رشته)؛ موضوع فقط در تراشه‌ها ── */
$w50_cats = function ( $a, $c, $cat ) {
	return false === strpos( $a, 'tp-news-cats' ) && false === strpos( $c, 'tp-news-cats' ) && false === strpos( $cat, "'دسته‌بندی‌ها' =>" );
};
$w50_catf = (string) @file_get_contents( dirname( __DIR__ ) . '/scripts/i18n/topersia-render.php' );
tp441_mut( 'P5-D556: بلوک «دسته‌بندی‌ها» نیست — نه در قالب، نه CSS، نه کاتالوگ — جهش: بازگشت nav.tp-news-cats', function ( $a ) use ( $w50_cats, $w50_css, $w50_catf ) { return $w50_cats( $a, $w50_css, $w50_catf ); }, $w50_arch,
	$w50_arch . '<nav class="tp-news-cats" aria-labelledby="tp-news-cats-h"></nav>' );

/* ── P5-D546: بی‌رفرش، با بهبود تدریجی ── */
$w50_nr = function ( $s ) {
	return false !== strpos( $s, "part.searchParams.set( 'tp_partial', '1' );" )
		&& false !== strpos( $s, 'ctrl.abort();' ) && false !== strpos( $s, 'new window.AbortController()' ) && false !== strpos( $s, 'if ( mine !== ctrl ) {' )
		&& false !== strpos( $s, "window.history.pushState( { tpNews: 1 }, '', url.href );" ) && false !== strpos( $s, "window.addEventListener( 'popstate'" )
		&& false !== strpos( $s, "live.textContent = res.getAttribute( 'data-tp-news-msg' ) || '';" )
		&& false !== strpos( $s, 'window.location.assign( url.href );' ) && false !== strpos( $s, "if ( e && e.name === 'AbortError' ) {" )
		&& false !== strpos( $s, "'(prefers-reduced-motion: reduce)'" ) && false !== strpos( $s, 'e.preventDefault();' );
};
tp441_mut( 'P5-D546: قطعه با tp_partial، آخرین درخواست برنده (AbortController)، pushState/popstate، aria-live، بازگشت به GET با خطا، حرکت کم — جهش: بی لغو درخواست قبلی', $w50_nr, $w50_js,
	str_replace( 'ctrl.abort();', '/* */', $w50_js ) );
$w50_part = function ( $u, $a, $l ) {
	return false !== strpos( $u, "return '1' === (string) get_query_var( 'tp_partial' );" )
		&& false !== strpos( $u, "header( 'X-Robots-Tag: noindex, nofollow' );" ) && false !== strpos( $u, "header( 'Cache-Control: no-store' );" )
		&& false !== strpos( $u, "'tp_sort', 'tp_when', 'tp_partial' ) );" )
		&& 2 === substr_count( $a . $l, "if ( ! \$tp_part ) {\n\tget_footer();" ) && 2 === substr_count( $a . $l, "topersia_g_news_partial_headers();\n} else {\n\tget_header();" )
		&& 1 === substr_count( $a, '<p class="tp-news-vh" role="status" aria-live="polite" data-tp-news-live></p>' )
		&& 1 === substr_count( $l, '<p class="tp-news-vh" role="status" aria-live="polite" data-tp-news-live></p>' );
};
tp441_mut( 'P5-D546: پاسخ قطعه همان قالب بی سربرگ و پاورقی سایت، noindex و no-store؛ ناحیۀ aria-live بیرون از ناحیۀ تعویض — جهش: قطعه نمایه‌پذیر', function ( $u ) use ( $w50_part, $w50_arch, $w50_list ) { return $w50_part( $u, $w50_arch, $w50_list ); }, $w50_ui,
	str_replace( "header( 'X-Robots-Tag: noindex, nofollow' );", '', $w50_ui ) );
/* پیشرفت تدریجی: پیوند و فرم GET واقعی می‌مانند؛ canonical و noindex حالت پالایش‌شده همان (assert-48 · assert-49) */
t( 'P5-D546: بی JS — پیوندهای واقعی و فرم GET؛ canonical پالایش‌شده بی‌پالایش دست نخورد', false !== strpos( $w50_arch, '<form id="tp-news-form" class="tp-news-filter" method="get" action="' )
	&& false !== strpos( $w50_news, "topersia_g_news_archive_url( topersia_g_news_filtered() ? 1 : max( 1, (int) get_query_var( 'paged' ) ) )" ) && 0 === preg_match( '/href="#"|javascript:/', $w50_arch . $w50_list . $w50_ui ) );

/* ── تصویر: خود خبر ← مکان ← گرادیان؛ هرگز نمونه ── */
t( 'P5-D541: تصویر سربرگ تک‌خبر از زنجیرۀ خبر (خود ← مکان غیرنمونه)، نبود ← گرادیان', false !== strpos( $w50_one, 'topersia_g_news_hero_img( topersia_g_news_image( $tp_id ) )' ) && false !== strpos( $w50_ui, "'<header class=\"tp-news-hero' . ( '' === \$img ? ' tp-news-hero--art' : '' ) . '\"" ) );

/* ── بخش ۳ — صفحۀ اول (P5-D539 · P5-D540) ── */
$w50_home = $w50_read( 'plugin/topersia-render/includes/topersia-g-home.php' );
$w50_hcss = $w50_read( 'plugin/topersia-render/assets/topersia-g-home.css' );
$w50_sch  = $w50_read( 'plugin/topersia-schema/topersia-schema.php' );
$w50_nofaq = function ( $h, $c, $s ) {
	return false === strpos( $h, 'topersia_g_home_faq_items' ) && false === strpos( $h, 'tp-home-faq' ) && false === strpos( $h, "__( 'پرسش‌های متداول'" )
		&& false === strpos( $c, 'tp-home-faq' )
		&& false === strpos( $s, 'topersia_g_home_faq_items' ) && false === strpos( $s, "\$home . '#faq'" );
};
tp441_mut( 'P5-D539: «پرسش‌های متداول» و FAQPage صفحۀ اول نیست (render، CSS، اسکیما) — جهش: بخش پرسش برگردد', function ( $h ) use ( $w50_nofaq, $w50_hcss, $w50_sch ) { return $w50_nofaq( $h, $w50_hcss, $w50_sch ); }, $w50_home,
	$w50_home . "\n/* topersia_g_home_section( 'tp-home-faq', __( 'پرسش‌های متداول', 'topersia' ) ) */" );
tp441_mut( 'P5-D539: اسکیمای صفحۀ اول بی FAQPage — جهش: گره FAQPage صفحۀ اول', function ( $s ) use ( $w50_nofaq, $w50_home, $w50_hcss ) { return $w50_nofaq( $w50_home, $w50_hcss, $s ); }, $w50_sch,
	$w50_sch . "\n\$graph[] = array( '@type' => 'FAQPage', '@id' => \$home . '#faq' );" );
$w50_order = function ( $h ) {
	$p = array();
	/* دستۀ 52 (P5-D562): مقاصد درون ردیف [تجربه‌ها | مقاصد] ($duo)؛ ردیف پایانی $mag */
	foreach ( array( '$stats', "'tp-home-provinces'", '. $duo', "'tp-home-attractions'", "'tp-home-events'", "'tp-home-guide'", '. $mag' ) as $k ) {
		$p[] = strpos( $h, $k, (int) strpos( $h, '$body = $stats' ) );
	}
	$ok = ! in_array( false, $p, true );
	for ( $i = 1; $ok && $i < count( $p ); $i++ ) {
		$ok = $p[ $i ] > $p[ $i - 1 ];
	}
	$tiles = strpos( $h, "'<div class=\"tp-home-prov__tiles\">'" );
	$map   = strpos( $h, "'<div class=\"tp-home-prov__map\">" );
	$gal   = strpos( $h, "topersia_g_home_section( 'tp-home-gallery'" );
	$nws   = strpos( $h, "topersia_g_home_section( 'tp-home-news'" );
	return $ok && false !== $tiles && false !== $map && $tiles < $map && false !== $gal && false !== $nws && $gal < $nws
		&& false !== strpos( $h, "\$quick[] = array( '#tp-home-news', __( 'اخبار', 'topersia' ), 'news' );" );
};
tp441_mut( 'P5-D540 · P5-D562: ترتیب — آمار · [کاشی | نقشه] · [تجربه‌ها | مقاصد] · جاذبه · رویداد · راهنما · [تصویر | اخبار | مقالات]؛ پیوند سریع اخبار — جهش: نقشه پیش از کاشی', $w50_order, $w50_home,
	str_replace( "'<div class=\"tp-home-prov__tiles\">'", "'<div class=\"tp-home-prov__tilesX\">'", $w50_home ) );
t( 'P5-D540: ستون کاشی استان در آغاز سطر پهن‌تر (1.25fr · 1fr)', false !== strpos( $w50_hcss, '.tp-home-prov{display:grid;gap:var(--tp-home-gap);grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);' ) );
