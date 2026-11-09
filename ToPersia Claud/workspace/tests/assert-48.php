<?php
/**
 * دستۀ 48 — بخش ۱: اصلاح‌های صفحۀ اول (P5-D528 · P5-D529 · P5-D530 · P5-D531، گالری بی سوراخ).
 * از assert.php بار می‌شود؛ خواندن کد با جهش. رفتار روی وردپرس واقعی: tests/w48-local.js.
 */

echo "\nدستۀ 48 — صفحۀ اول\n" . str_repeat( '─', 78 ) . "\n";

$w48_root  = dirname( __DIR__ );
$w48_read  = function ( $p ) use ( $w48_root ) { return (string) @file_get_contents( $w48_root . '/' . $p ); };
$w48_home  = $w48_read( 'plugin/topersia-render/includes/topersia-g-home.php' );
$w48_js    = $w48_read( 'plugin/topersia-render/assets/topersia-g-home.js' );
$w48_css   = $w48_read( 'plugin/topersia-render/assets/topersia-g-home.css' );
$w48_legal = $w48_read( 'plugin/topersia-render/includes/topersia-g-legal.php' );
$w48_cat   = include $w48_root . '/scripts/i18n/topersia-render.php';

/* ── P5-D529: هر اسلایدر یک ردیف، با نوار و دستگیرۀ صفحۀ موجودیت (P5-D243) ── */
$w48_sl = function ( $s ) {
	return false !== strpos( $s, "topersia_g_slider_bar( \$id, \$count )" )
		&& 3 === preg_match_all( "/topersia_g_home_slider\( 'tp-home-(?:prov|dest|attr)slide'/", $s )
		&& false === strpos( $s, 'data-tp-slide' ) && false === strpos( $s, 'tp-home-destgrid' ) && false === strpos( $s, 'tp-home-grid--4' );
};
tp441_mut( 'P5-D529: استان‌ها، مقاصد و جاذبه‌ها هر سه با topersia_g_home_slider و نوار topersia_g_slider_bar — جهش: گرید دوردیفۀ پیشین', $w48_sl, $w48_home,
	str_replace( "topersia_g_home_slider( 'tp-home-attrslide'", "topersia_g_home_grid( 'tp-home-attrslide'", $w48_home ) );
$w48_row = function ( $c ) {
	return 1 === preg_match( '/\.tp-home-slider\{display:grid;grid-auto-flow:column;grid-template-rows:auto;/', $c )
		&& false === strpos( preg_replace( '/@media \(min-width:1024px\)\{.*?\n\}\n/s', '', $c ), 'grid-template-rows:repeat(2' ) && false === strpos( $c, 'destgrid' ); /* دستۀ 49: فقط استان‌ها در ≥ ۱۰۲۴ دو ردیف (P5-D533) */
};
tp441_mut( 'P5-D529 CSS: اسلایدر grid-template-rows:auto (یک ردیف)، بی repeat(2) — جهش: دوردیفه', $w48_row, $w48_css,
	str_replace( 'grid-template-rows:auto;', 'grid-template-rows:repeat(2,auto);', $w48_css ) );
$w48_bp = function ( $c ) {
	foreach ( array( '1024', '768', '600' ) as $w ) {
		if ( ! preg_match( '/@media \(max-width:' . $w . 'px\)\{[^@]*\.tp-home-slider[^{]*\{grid-auto-columns:/', $c ) ) { return false; }
	}
	return true;
};
tp441_mut( 'P5-D529 CSS: پهنای ستون اسلایدر در ۱۰۲۴، ۷۶۸ و ۶۰۰ — جهش: بند ۷۶۸ برداشته شود', $w48_bp, $w48_css,
	preg_replace( '/@media \(max-width:768px\)\{[^@]*\}\n\}\n/', '', $w48_css ) );
/* دستگیرۀ تکراری نه: home.js اسکرول نمی‌کند (topersia-g-zones.js همان کار را برای همۀ اسلایدرها می‌کند) */
$w48_nodup = function ( $j ) { return '' !== $j && false === strpos( $j, 'scrollBy' ) && false === strpos( $j, 'data-tp-slide' ) && strlen( $j ) <= 4096; };
tp441_mut( 'P5-D243: home.js دستگیرۀ اسلایدر ندارد (کد تکراری نه)، زیر ۴KB — جهش: scrollBy دوباره', $w48_nodup, $w48_js,
	$w48_js . "\nbox.scrollBy( { left: 1 } );" );

/* ── P5-D530: سه ستون هم‌ارتفاع ── */
$w48_trio = function ( $c ) {
	return 1 === preg_match( '/\.tp-home-trio\{display:grid;grid-auto-flow:column;grid-auto-columns:minmax\(0,1fr\);align-items:stretch;/', $c )
		&& false !== strpos( $c, '.tp-home-trio > .tp-home-col{display:flex;flex-direction:column;' )
		&& false !== strpos( $c, '.tp-home-trio > .tp-home-col > :last-child{flex:1 1 auto;align-content:start}' )
		&& 1 === preg_match( '/@media \(max-width:900px\)\{[^@]*\.tp-home-trio\{display:flex;flex-direction:column;/', $c );
};
tp441_mut( 'P5-D530 CSS: ردیف سه‌ستونه grid stretch، ستون flex، پنل flex:1؛ زیر ۹۰۰ عمودی — جهش: align-items:flex-start', $w48_trio, $w48_css,
	str_replace( 'grid-auto-columns:minmax(0,1fr);align-items:stretch;', 'grid-auto-columns:minmax(0,1fr);align-items:flex-start;', $w48_css ) );

/* ── P5-D531: کادر حساب کاربری برداشته شد (کد، CSS، رشته) ── */
$w48_acct = function ( $s ) { return false === strpos( $s, 'tp-home-account' ) && false === strpos( $s, 'tp-home-cta' ) && false === strpos( $s, "get_page_by_path( 'account' )" ); };
tp441_mut( 'P5-D531: کادر حساب کاربری در PHP نیست — جهش: بخش #tp-home-account', $w48_acct, $w48_home, $w48_home . "\n'<section id=\"tp-home-account\">'" );
t( 'P5-D531: بی قاعدۀ CSS کادر و بی رشتۀ بی‌مصرف آن در کاتالوگ', false === strpos( $w48_css, 'tp-home-cta' )
	&& ! isset( $w48_cat['ورود / ثبت‌نام'] ) && ! isset( $w48_cat['با حساب کاربری می‌توانید مکان‌ها را ذخیره و دنبال کنید و نظر بدهید.'] ) );

/* ── P5-D528: افزونه در پاورقی هیچ خروجی ندارد (پیش‌فرض «0») ── */
$w48_foot = function ( $s ) {
	return false !== strpos( $s, "get_option( 'topersia_footer_credits', '0' )" )
		&& false !== strpos( $s, "return in_array( \$v, array( '1', 1, true, 'on', 'yes' ), true );" )
		&& false !== strpos( $s, "if ( is_admin() || ! topersia_g_footer_credits_on() ) {\n\t\treturn \$value;" )
		&& false !== strpos( $s, "if ( ! topersia_g_footer_credits_on() || ! wp_style_is( 'topersia-tokens', 'enqueued' ) ) {" );
};
tp441_mut( 'P5-D528: صافی woodmart_option و شیوه‌نامۀ پاورقی فقط با option «1»؛ پیش‌فرض «0» — جهش: پیش‌فرض «1»', $w48_foot, $w48_legal,
	str_replace( "get_option( 'topersia_footer_credits', '0' )", "get_option( 'topersia_footer_credits', '1' )", $w48_legal ) );
tp441_mut( 'P5-D528: خاموش ← مقدار خود وودمارت برمی‌گردد — جهش: صافی بی شرط', $w48_foot, $w48_legal,
	str_replace( "if ( is_admin() || ! topersia_g_footer_credits_on() ) {", "if ( is_admin() ) {", $w48_legal ) );
if ( function_exists( 'topersia_g_footer_credits_on' ) ) {
	$w48_o = isset( $GLOBALS['tp_options'] ) ? $GLOBALS['tp_options'] : array();
	unset( $GLOBALS['tp_options']['topersia_footer_credits'] );
	$w48_def = topersia_g_footer_credits_on();
	$GLOBALS['tp_options']['topersia_footer_credits'] = '0';
	$w48_off = topersia_g_footer_credits_on();
	$GLOBALS['tp_options']['topersia_footer_credits'] = '1';
	$w48_on = topersia_g_footer_credits_on();
	$GLOBALS['tp_options'] = $w48_o;
	t( 'P5-D528: بی option خاموش، «0» خاموش، فقط «1» روشن', ! $w48_def && ! $w48_off && $w48_on );
}

/* ── گالری: گرید ۴/۵/۸ دستۀ 48 جای خود را به اسلایدر یک‌ردیفۀ ۱۲تایی داد (دستۀ 49 بند ۲؛ آزمونش در assert-49) ── */
t( 'گالری: گرید ۴/۵/۸ و --lead برداشته شد (دستۀ 49)', false === strpos( $w48_home, 'tp-home-photos--lead' ) && false === strpos( $w48_css, 'tp-home-photos' ) );

/* ── بخش ۲: آرشیو سراسری /news/ (SPRINT-4.4 فاز ۶٫۱، P5-D260) ── */
$w48_news  = $w48_read( 'plugin/topersia-render/includes/topersia-g-news.php' );
$w48_ntpl  = $w48_read( 'plugin/topersia-render/templates/news-archive.php' );
$w48_ncss  = $w48_read( 'plugin/topersia-render/assets/topersia-g-news.css' );
$w48_sch   = $w48_read( 'plugin/topersia-schema/topersia-schema.php' );
$w48_q = function ( $s ) {
	return false !== strpos( $s, "\$q->set( 'meta_query', array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) );" )
		&& false !== strpos( $s, "\$q->set( 'post_status', 'publish' );" ) && false !== strpos( $s, "\$q->set( 'has_password', false );" )
		&& false !== strpos( $s, "if ( ! metadata_exists( 'post', \$id, '_tp_sample' ) ) {" )
		&& false !== strpos( $s, "foreach ( topersia_g_published( array_map( 'absint', (array) \$ids ) ) as \$id ) {" );
};
tp441_mut( 'news: کوئری اصلی آرشیو فقط منتشرشده، بی‌گذرواژه، غیرنمونه؛ شناسه‌ها از دروازۀ انتشار — جهش: بی شرط نمونه', $w48_q, $w48_news,
	str_replace( "\$q->set( 'meta_query', array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) );", '', $w48_news ) );
$w48_thin = function ( $s ) {
	return false !== strpos( $s, 'return topersia_g_news_filter_place() > 0 || count( topersia_g_news_archive_ids() ) < 3;' )
		&& 2 === substr_count( $s, 'topersia_g_news_is_archive() && topersia_g_news_archive_thin()' );
};
tp441_mut( 'news: noindex زیر ۳ خبر و پالایش‌شده، هم wp_robots هم Rank Math — جهش: آستانۀ ۱', $w48_thin, $w48_news,
	str_replace( 'count( topersia_g_news_archive_ids() ) < 3', 'count( topersia_g_news_archive_ids() ) < 1', $w48_news ) );
$w48_badge = function ( $s ) { return false !== strpos( $s, 'foreach ( topersia_g_published( topersia_g_news_places( $news_id ) ) as $pid ) {' ); };
tp441_mut( 'news: نشان مکان‌های کارت از دروازۀ انتشار (P5-D247) — جهش: بی دروازه', $w48_badge, $w48_news,
	str_replace( 'foreach ( topersia_g_published( topersia_g_news_places( $news_id ) ) as $pid ) {', 'foreach ( topersia_g_news_places( $news_id ) as $pid ) {', $w48_news ) );
$w48_tpl = function ( $s ) {
	return false !== strpos( $s, '<nav class="tp-crumb"' ) && false !== strpos( $s, "topersia_g_news_archive_card( \$tp_one )" )
		&& false !== strpos( $s, "__( 'هنوز خبری منتشر نشده است.', 'topersia' )" ) && false !== strpos( $s, "dir=\"<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>\"" )
		&& ! preg_match( '/>\s*[\x{0600}-\x{06FF}][^<{]*</u', preg_replace( '/<\?php.*?\?>/s', '', $s ) );
};
tp441_mut( 'news قالب: مسیر راهنما، کارت، خالی امن، جهت از زبان، بی متن فارسی خام (همه از کاتالوگ) — جهش: متن خام', $w48_tpl, $w48_ntpl,
	str_replace( '<section id="tp-news" class="tp-section">', '<section id="tp-news" class="tp-section"><p>خبرها</p>', $w48_ntpl ) );
$w48_tok = function ( $c ) { return '' !== $c && ! preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|letter-spacing/i', $c ) && false !== strpos( $c, 'var(--tp-tile-bg)' ); };
tp441_mut( 'news CSS: فقط توکن --tp-* (بی رنگ خام)، کاشی از توکن کاشی، بی letter-spacing — جهش: رنگ خام', $w48_tok, $w48_ncss,
	str_replace( 'color:var(--tp-primary-text);font:inherit', 'color:#0a7;font:inherit', $w48_ncss ) );
$w48_ld = function ( $s ) {
	return false !== strpos( $s, 'function topersia_schema_news_archive_json(' ) && false !== strpos( $s, "'@type'         => 'NewsArticle'," )
		&& false !== strpos( $s, "if ( function_exists( 'topersia_g_news_archive_thin' ) && ! topersia_g_news_archive_thin() ) {" );
};
tp441_mut( 'news اسکیما: CollectionPage + ItemList از NewsArticle در موتور topersia-schema، فقط نمایه‌پذیر — جهش: بی شرط noindex', $w48_ld, $w48_sch,
	str_replace( "if ( function_exists( 'topersia_g_news_archive_thin' ) && ! topersia_g_news_archive_thin() ) {", 'if ( true ) {', $w48_sch ) );

/* ── بخش ۳: سیاست خلیج فارس (P5-D526 · P5-D527) — گیت ایستا در فرایند جدا؛ محلی: همان پرونده زیر WP-CLI ── */
$w48_php = defined( 'TP_PHP' ) ? TP_PHP : PHP_BINARY;
exec( escapeshellarg( $w48_php ) . ' ' . escapeshellarg( $w48_root . '/tests/gulf-policy.php' ) . ' 2>&1', $w48_gp, $w48_gprc );
$w48_gpf = preg_grep( '/❌/u', $w48_gp );
t( 'خلیج فارس (tests/gulf-policy.php ایستا): رجیستری ده زبان، بی نام ممنوع در کد و کاتالوگ، مصرف‌کننده‌ها فقط از رجیستری — با جهش', 0 === $w48_gprc && ! $w48_gpf && preg_grep( '/گذشته: \d+   افتاده: 0/u', $w48_gp ),
	implode( ' | ', $w48_gpf ) . ' rc=' . $w48_gprc );
