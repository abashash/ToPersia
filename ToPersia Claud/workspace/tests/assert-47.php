<?php
/**
 * دستۀ 47 — بخش ۱: بازدارنده‌های راه‌اندازی (P5-D518 · P5-D520 · P5-D524 · P5-D525).
 * از assert.php بار می‌شود؛ خواندن کد و متن منبع با جهش. رفتار روی وردپرس واقعی: tests/w47-local.js.
 *
 *   php tests/assert.php --launch   ← جای‌نگهدار «[تکمیل …]» یا TODO در متن صفحۀ منتشرشونده قرمز است؛
 *                                     بی آن فقط هشدار.
 */

echo "\nدستۀ 47 — بازدارنده‌های راه‌اندازی\n" . str_repeat( '─', 78 ) . "\n";

$w47_root   = dirname( __DIR__ );
$w47_read   = function ( $p ) use ( $w47_root ) { return (string) @file_get_contents( $w47_root . '/' . $p ); };
$w47_brand  = $w47_read( 'plugin/topersia-core/includes/topersia-brand.php' );
$w47_core   = $w47_read( 'plugin/topersia-core/topersia-core.php' );
$w47_zones  = $w47_read( 'plugin/topersia-render/includes/topersia-g-zones.php' );
$w47_home   = $w47_read( 'plugin/topersia-render/includes/topersia-g-home.php' );
$w47_legal  = $w47_read( 'plugin/topersia-render/includes/topersia-g-legal.php' );
$w47_schema = $w47_read( 'plugin/topersia-schema/topersia-schema.php' );
$w47_css    = $w47_read( 'plugin/topersia-render/assets/topersia-g-home.css' );
$w47_text   = $w47_read( 'docs/legal/PAGES-TEXT-2026-11-04.md' );
$w47_prv    = $w47_read( 'docs/legal/PRIVACY-DRAFT.md' );
$w47_cat    = include $w47_root . '/scripts/i18n/topersia-render.php';
$w47_launch = in_array( '--launch', isset( $argv ) ? (array) $argv : array(), true );

/* ── ۱٫۱ نام برند — یک منبع ─────────────────────────────────────────── */
t( 'P5-D518: topersia_brand_name — fa «توپرشیا» · ar «توبيرشيا» (اصلاح ۸ نوامبر) · بقیه ToPersia؛ alternateName [توپرشیا، توبيرشيا، توبرشيا]',
	function_exists( 'topersia_brand_name' ) && 'توپرشیا' === topersia_brand_name( 'fa' ) && 'توبيرشيا' === topersia_brand_name( 'ar' )
	&& 'ToPersia' === topersia_brand_name( 'en' ) && 'ToPersia' === topersia_brand_name( 'de' ) && array( 'توپرشیا', 'توبيرشيا', 'توبرشيا' ) === topersia_brand_alternates() );

$w47_one = function ( $s ) {
	return 1 === substr_count( $s, "'fa' => 'توپرشیا'" ) && 1 === substr_count( $s, "'ar' => 'توبيرشيا'" ) && 1 === substr_count( $s, "return array( 'توبرشيا' );" );
};
tp441_mut( 'P5-D518: نام نمایشی fa و ar فقط یک بار در ثابت مرکزی، «توبرشيا» فقط شکل دیگر — جهش: املای پیشین «توبرشيا» به‌جای نام نمایشی', $w47_one, $w47_brand, str_replace( "'ar' => 'توبيرشيا'", "'ar' => 'توبرشيا'", $w47_brand ) );

$w47_req = function ( $s ) { return false !== strpos( $s, "require_once __DIR__ . '/includes/topersia-brand.php';" ); };
tp441_mut( 'P5-D518: هسته پروندۀ برند را بار می‌کند — جهش: بی require', $w47_req, $w47_core, str_replace( "require_once __DIR__ . '/includes/topersia-brand.php';", '', $w47_core ) );

/* هیچ کد نمایشی نام فارسی/عربی را خودش ننویسد؛ همه از topersia_g_brand(). */
$w47_nolit = function ( $s ) {
	$code = preg_replace( '#/\*.*?\*/#s', '', $s );
	return false === mb_strpos( $code, 'توپرشیا' ) && false === mb_strpos( $code, 'توبيرشيا' ) && false === mb_strpos( $code, 'توبرشيا' );
};
$w47_render = $w47_zones . $w47_home . $w47_legal . $w47_schema;
tp441_mut( 'P5-D518: render و schema نام برند را دستی نمی‌نویسند — جهش: «توپرشیا» در کد', $w47_nolit, $w47_render, $w47_render . "\n\$x = 'توپرشیا';" );

$w47_use = function ( $s ) {
	return false !== strpos( $s, "__( '%s در یک نگاه', 'topersia' ), topersia_g_brand() )" )
		&& false !== strpos( $s, "__( '%s را منبع ترجیحی گوگل کنید', 'topersia' ), topersia_g_brand() )" )
		&& (bool) preg_match( "/__\( '© %1\\\$s ToPersia \(%2\\\$s\)\. [^']*متعلق به %2\\\$s [^']*', 'topersia' \), \\\$year, topersia_g_brand\(\) \)/u", $s )
		&& false !== strpos( $s, "return function_exists( 'topersia_brand_name' ) ? topersia_brand_name( \$lang ) : 'ToPersia';" );
};
tp441_mut( 'P5-D518: سه رشتۀ دیدنی (نوار شمار، منبع ترجیحی، کپی‌رایت) نام را از topersia_g_brand() می‌گیرند — جهش: کپی‌رایت با نام ثابت', $w47_use, $w47_render, str_replace( '$year, topersia_g_brand() )', "\$year, 'ToPersia' )", $w47_render ) );

/* کاتالوگ: هیچ msgid یا ترجمه‌ای «ToPersia» را جز در «ToPersia (%2$s)» کپی‌رایت ندارد. */
$w47_catok = function ( $c ) {
	$bad = array();
	foreach ( $c as $k => $v ) {
		foreach ( array_merge( array( 'fa' => $k ), is_array( $v ) ? $v : array() ) as $l => $s ) {
			if ( 'en' !== $l && false !== strpos( str_replace( 'ToPersia (%2$s)', '', ( is_array( $s ) ? json_encode( $s, JSON_UNESCAPED_UNICODE ) : (string) $s ) ), 'ToPersia' ) ) { $bad[] = "$l"; }
		}
	}
	return array() === $bad;
};
tp441_mut( 'P5-D518: کاتالوگ render — fa و ar نام لاتین ندارند (جز «ToPersia (نام)» کپی‌رایت) — جهش: ترجمۀ عربی با ToPersia', $w47_catok, $w47_cat, array( 'x' => array( 'en' => 'x', 'ar' => 'اجعل ToPersia' ) ) + $w47_cat );

/* عنوان و og در fa/ar: پسوند پس از جداکننده؛ og:site_name. اولویت ۹۹ پس از topersia_seo_title (۲۰). */
$w47_rm = function ( $s ) {
	foreach ( array( "'rank_math/frontend/title', 'topersia_brand_localize_title', 99", "'rank_math/opengraph/facebook/og_title', 'topersia_brand_localize_title', 99", "'rank_math/opengraph/twitter/twitter_title', 'topersia_brand_localize_title', 99", "'rank_math/opengraph/facebook/og_site_name', 'topersia_brand_site_name', 99" ) as $h ) {
		if ( false === strpos( $s, "add_filter( $h );" ) ) { return false; }
	}
	return true;
};
tp441_mut( 'P5-D518: عنوان، og:title، twitter:title و og:site_name به زبان صفحه (Rank Math، اولویت ۹۹) — جهش: بی og:site_name', $w47_rm, $w47_brand, str_replace( "'rank_math/opengraph/facebook/og_site_name'", "'x'", $w47_brand ) );

t( 'P5-D518: الگوی پسوند فقط نام پس از جداکننده را می‌گیرد، نه «© … ToPersia (…)»',
	1 === preg_match( '/(\s[-—–|·]\s)ToPersia\s*$/u', 'شرایط و قوانین استفاده - ToPersia' ) && 1 === preg_match( '/(\s[-—–|·]\s)ToPersia\s*$/u', 'اصفهان — ToPersia' )
	&& 0 === preg_match( '/(\s[-—–|·]\s)ToPersia\s*$/u', '© ۲۰۲۶ ToPersia (توپرشیا).' )
	&& false !== strpos( $w47_brand, "preg_replace( '/(\\s[-—–|·]\\s)' . preg_quote( TOPERSIA_BRAND, '/' ) . '\\s*\$/u'" ) );

$w47_alt = function ( $s ) {
	return 2 === substr_count( $s, ') + topersia_schema_brand_alt();' )
		&& false !== strpos( $s, "return \$alt ? array( 'alternateName' => \$alt ) : array();" )
		&& false !== strpos( $s, "'name'  => get_bloginfo( 'name' )," );
};
tp441_mut( 'P5-D518: Organization و WebSite: name = ToPersia، alternateName = [توپرشیا، توبيرشيا، توبرشيا] از هسته — جهش: WebSite بی alternateName', $w47_alt, $w47_schema, preg_replace( '/\) \+ topersia_schema_brand_alt\(\);/', ');', $w47_schema, 1 ) );

/* ── ۱٫۲ «گزارش مشکل» — P5-D524 ─────────────────────────────────────── */
$w47_sent = array(
	'fa' => 'برای گزارش خطا، محتوای ناقض حقوق یا درخواست حذف، به abuse@topersia.com ایمیل بفرستید.',
	'en' => 'To report an error, infringing content or a removal request, email abuse@topersia.com.',
	'ar' => 'للإبلاغ عن خطأ أو محتوى مخالف أو طلب حذف، راسلونا على abuse@topersia.com.',
);
$w47_rep = function ( $s ) use ( $w47_sent ) {
	$body = preg_replace( '/^.*?(?=^## ۱\. شرایط)/msu', '', $s, 1 );   /* یادداشت‌های سرِ پرونده منتشر نمی‌شوند */
	foreach ( $w47_sent as $x ) {
		if ( 3 !== substr_count( $body, $x ) ) { return false; }   /* شرایط ۲ و ۱۳ · تماس */
	}
	return 0 === preg_match( '/گزارش مشکل|Report a problem|الإبلاغ عن مشكلة/u', $body );
};
tp441_mut( 'P5-D524: شرایط (بند ۲ و ۱۳) و تماس، سه زبان، عیناً جملۀ abuse@ — هیچ ارجاع «گزارش مشکل» در متن منتشرشونده — جهش: یک ارجاع بماند', $w47_rep, $w47_text, str_replace( 'أرسل ملاحظاتك وتصويباتك إلى', 'استخدم «الإبلاغ عن مشكلة» أو', $w47_text ) );

/* نام برند در متن منتشرشوندۀ fa/ar (PAGES-TEXT بی بخش‌های انگلیسی + PRIVACY-DRAFT بخش ۲ بی en). */
$w47_txtbrand = function ( $s ) {
	$s = preg_replace( '/^.*?(?=^## ۱\. شرایط)/msu', '', $s, 1 );
	$s = preg_replace( '/^### en$.*?(?=^### ar$)/msu', '', $s );           /* شرایط انگلیسی */
	$s = preg_replace( '/^\*\*en[^\n]*\n(?:(?!\*\*ar)[^\n]*\n)*/mu', '', $s ); /* درباره و تماس انگلیسی */
	$s = preg_replace( '/^\*\*en\*\* —[^\n]*$/mu', '', $s );                 /* عنوان انگلیسی صفحۀ اول */
	return false === strpos( str_replace( array( 'ToPersia (توپرشیا)', 'ToPersia (توبيرشيا)' ), '', $s ), 'ToPersia' );
};
$w47_prv2 = preg_match( '/^## بخش ۲ — .*?(?=^## بخش ۳ )/msu', $w47_prv, $w47_pm ) ? preg_replace( '/^### English \(en\)$.*?(?=^### العربية \(ar\)$)/msu', '', $w47_pm[0] ) : '';
t( 'P5-D518: متن منتشرشوندۀ fa/ar نام بومی دارد (PAGES-TEXT بی بخش انگلیسی، PRIVACY بخش ۲ بی en) — جهش: «ToPersia» در متن فارسی',
	$w47_txtbrand( $w47_text ) && '' !== $w47_prv2 && false === strpos( $w47_prv2, 'ToPersia' ) && ! $w47_txtbrand( str_replace( 'استفاده از توپرشیا', 'استفاده از ToPersia', $w47_text ) ) );

/* اصلاح P5-D518 (۸ نوامبر): متن دیدنی عربی فقط «توبيرشيا»؛ «توبرشيا» فقط شکل دیگر alternateName در ثابت مرکزی. */
$w47_cat_flat = json_encode( $w47_cat, JSON_UNESCAPED_UNICODE );
$w47_noold = function ( $s ) { return false === mb_strpos( $s, 'توبرشيا' ); };
tp441_mut( 'P5-D518 اصلاح: «توبرشيا» در هیچ متن دیدنی نیست (PAGES-TEXT، PRIVACY، کاتالوگ، render، schema) — جهش: املای پیشین در متن عربی', $w47_noold, $w47_text . $w47_prv . $w47_cat_flat . $w47_render, str_replace( 'ينشر توبيرشيا', 'ينشر توبرشيا', $w47_text ) . $w47_prv . $w47_cat_flat . $w47_render );
t( 'P5-D518 اصلاح: متن عربی PAGES-TEXT و PRIVACY نام «توبيرشيا» را دارد', false !== mb_strpos( $w47_text, 'ينشر توبيرشيا' ) && false !== mb_strpos( $w47_prv, 'يجمعها توبيرشيا' ) );

/* ── ۱٫۳ جای‌نگهدار — گیت --launch ───────────────────────────────────── */
$w47_ph = function ( $s ) {
	preg_match_all( '/\[(?:تکمیل|to be completed|يُستكمل)[^\]]*\]|\bTODO\b/u', $s, $m );
	return $m[0];
};
$w47_pub   = ( preg_match( '/^## ۱\. شرایط.*?(?=^## ۴\. )/msu', $w47_text, $w47_tm ) ? $w47_tm[0] : '' ) . "\n" . ( isset( $w47_pm[0] ) ? $w47_pm[0] : '' );
$w47_found = $w47_ph( $w47_pub );
t( 'گیت --launch: کاشف جای‌نگهدار کار می‌کند — جهش: «[تکمیل …]» و TODO دیده می‌شوند', 2 === count( $w47_ph( '[تکمیل به دست کاربر — x] TODO' ) ) && array() === $w47_ph( 'متن بی جای خالی' ) && '' !== trim( $w47_pub ) );
if ( $w47_launch ) {
	t( 'گیت --launch: هیچ «[تکمیل …]» یا TODO در صفحۀ منتشرشونده', array() === $w47_found, implode( ' · ', $w47_found ) );
} elseif ( $w47_found ) {
	echo '  ⚠ هشدار (بی --launch قرمز نیست): ' . count( $w47_found ) . " جای‌نگهدار در متن صفحۀ منتشرشونده — با --launch قرمز می‌شود\n";
}

/* ── ۱٫۴ پاورقی — P5-D520 با P5-D528 (دستۀ 48) جایگزین شد: افزونه در پاورقی هیچ خروجی ندارد؛ گارد در assert-48.php ── */

/* ── ۱٫۵ هدر — فاصلۀ صفر با CSS ─────────────────────────────────────── */
$w47_gap = function ( $c ) { return false !== strpos( $c, 'html .tp-page.tp-home > .tp-main{padding-block-start:0}' ); };
tp441_mut( 'P5-D525: فاصلۀ هدر تا هیرو فقط با CSS صفحۀ اول — جهش: بی قاعده', $w47_gap, $w47_css, str_replace( 'padding-block-start:0}', 'padding-block-start:44px}', $w47_css ) );

/* ══ بخش ۲ — صفحۀ اول طبق مرجع (فقط دادۀ واقعی) ═══════════════════════ */
echo "\nدستۀ 47 بخش ۲ — صفحۀ اول\n" . str_repeat( '─', 78 ) . "\n";
$w47_js = $w47_read( 'plugin/topersia-render/assets/topersia-g-home.js' );

/* JS: سقف ۴KB، بی کتابخانه، فقط صفحۀ اول */
$w47_jsok = function ( $j ) { return '' !== $j && strlen( $j ) <= 4096 && ! preg_match( '/\b(require|import)\s*\(|jQuery|\$\.\w+\(/', $j ); };
tp441_mut( 'ب·JS: topersia-g-home.js ≤ ۴۰۹۶ بایت و بی کتابخانه — جهش: پرونده‌ای بزرگ‌تر از سقف', $w47_jsok, $w47_js, $w47_js . str_repeat( '/* x */', 120 ) );
$w47_enq = function ( $s ) { $g = strpos( $s, "if ( ! topersia_g_home_is() || ! function_exists( 'topersia_render_url' ) ) {" ); $e = strpos( $s, "wp_enqueue_script( 'topersia-g-home'" ); return false !== $g && false !== $e && $e > $g && $e - $g < 900; };
tp441_mut( 'ب·JS: فقط پشت topersia_g_home_is() بار می‌شود — جهش: بی نگهبان', $w47_enq, $w47_home, str_replace( '! topersia_g_home_is() ||', '', $w47_home ) );

/* ب — راهنمای هر استان، شمار صفر پنهان، پنجرۀ ۳۱ استان */
$w47_tip = function ( $s ) { return false !== strpos( $s, "data-tp-tip=\"' . esc_attr( \$tip )" ) || 1 === preg_match( '/data-tp-tip="\'\s*\.\s*esc_attr\(/', $s ); };
tp441_mut( 'ب: هر پیوند استان روی نقشه data-tp-tip دارد — جهش: بی راهنما', $w47_tip, $w47_home, preg_replace( '/ data-tp-tip="\'\s*\.\s*esc_attr\([^)]*\)\s*\.\s*\'"/', '', $w47_home ) );
if ( function_exists( 'topersia_g_home_prov_label' ) ) {
	$w47_l0 = topersia_g_home_prov_label( array( 0, 3 ) );
	$w47_l1 = topersia_g_home_prov_label( array( 2, 0 ) );
	t( 'ب: «N شهر · M جاذبه» — شمار صفر پنهان؛ هر دو صفر ← خالی', '' === topersia_g_home_prov_label( array( 0, 0 ) ) && false === mb_strpos( $w47_l0, 'شهر' ) && false !== mb_strpos( $w47_l0, 'جاذبه' ) && false === mb_strpos( $w47_l1, 'جاذبه' ) && false === strpos( $w47_l0 . $w47_l1, '0' ), $w47_l0 . ' | ' . $w47_l1 );
}
$w47_zero = function ( $s ) { return 2 === preg_match_all( '/if \( \$c\[[01]\] > 0 \) \{/', $s ); };
tp441_mut( 'ب: شمار صفر با شرط > 0 کنار می‌رود — جهش: شرط برداشته شود', $w47_zero, $w47_home, str_replace( 'if ( $c[0] > 0 ) {', 'if ( true ) {', $w47_home ) );
if ( function_exists( 'topersia_g_home_prov_all' ) ) {
	preg_match_all( '#<path id="tp-prov-([a-z0-9-]+)"#', $w47_read( 'plugin/topersia-render/assets/home/iran-provinces.svg' ), $w47_sm );
	$w47_by  = array_flip( array_unique( $w47_sm[1] ) );
	$w47_all = topersia_g_home_prov_all( array_map( function ( $i ) { return $i + 1; }, $w47_by ) );
	t( 'ب: پنجرۀ «همۀ استان‌ها» = ۳۱ ردیف (از نقشه)، هر کدام با پیوند وقتی صفحه دارد', 31 === count( $w47_sm[1] ) && 31 === count( $w47_all ), count( $w47_all ) );
}
$w47_dlg = function ( $s ) { return false !== strpos( $s, '<dialog id="tp-home-provdlg"' ) && false !== strpos( $s, "'<noscript><ul class=\"tp-home-provall tp-home-provall--ns\">' . \$all_li . '</ul></noscript>'" ) && false !== strpos( $s, 'topersia_search_fold(' ); };
tp441_mut( 'ب: <dialog> بومی با همان فهرست در noscript و کلید تاکردن هسته — جهش: بی noscript', $w47_dlg, $w47_home, str_replace( '<noscript>', '<div>', $w47_home ) );

/* پ · ث — آستانه‌ها و بی نمونه */
$w47_min = function ( $s ) { return false !== strpos( $s, 'count( $dest ) >= 4' ) && false !== strpos( $s, 'if ( $n < 4 ) {' ) && false !== strpos( $s, "array_filter( \$cities, 'topersia_g_card_image' ) ), 0, 12 )" ); };
tp441_mut( 'پ·ث: مقاصد ≥ ۴ (سقف ۱۲، فقط دارای تصویر) و گالری ≥ ۴ عکس یکتا — جهش: آستانۀ گالری برداشته شود', $w47_min, $w47_home, str_replace( 'if ( $n < 4 ) {', 'if ( false ) {', $w47_home ) );
$w47_real = function ( $s ) { return false === strpos( $s, '_tp_sample\', \'compare\' => \'EXISTS' ) && 2 <= substr_count( $s, "'key' => '_tp_sample', 'compare' => 'NOT EXISTS'" ); };
tp441_mut( 'بی نمونه: پرسش‌های تازۀ صفحۀ اول _tp_sample را کنار می‌گذارند — جهش: NOT EXISTS برداشته شود', $w47_real, $w47_home, str_replace( "'key' => '_tp_sample', 'compare' => 'NOT EXISTS'", "'key' => '_tp_x', 'compare' => 'NOT EXISTS'", $w47_home ) );
$w47_ext = function ( $s ) { return ! preg_match( '#<img[^>]+src=["\']https?://#i', $s ) && ! preg_match( '#https?://(?!schema\.org)[a-z0-9.-]+\.[a-z]{2,}/[^\'"]*\.(jpe?g|png|webp|avif|gif)#i', $s ); };
tp441_mut( 'بی پیوند تصویر بیرونی در صفحۀ اول — جهش: <img src="https://…">', $w47_ext, $w47_home, $w47_home . "\n/* '<img src=\"https://example.com/a.jpg\">' */" );

/* ت — ردیف سه‌ستونه؛ ج — بی صافی فصل (ممیزی P5-D522: تابستان و زمستان صفر) */
$w47_trio = function ( $s ) { return false !== strpos( $s, "'<div class=\"tp-home-trio tp-home-lazy\">' . \$trio . '</div>'" ) && 3 === substr_count( $s, "'tp-home-col' )" ); };
tp441_mut( 'ت: راهنما | اخبار | پرسش در یک ردیف؛ ستون خالی رندر نمی‌شود — جهش: بی ردیف', $w47_trio, $w47_home, str_replace( "'tp-home-col' )", "'' )", $w47_home ) );
t( 'ج: صافی فصل ساخته نشد (ممیزی: کمتر از ۲ مورد در دست‌کم یک فصل)', false === strpos( $w47_home, 'best_season' ) );

/* CSS — نیم‌هم‌پوشانی دقیق، توکن، بی letter-spacing */
$w47_c2 = function ( $c ) { return 1 === preg_match( '/\.tp-home-stats\{[^}]*transform:translateY\(-50%\)/', $c ) && ! preg_match( '/#[0-9a-f]{3,8}\b/i', $c ) && ! preg_match( '/letter-spacing:(?!0[;}])/', $c ) && 1 === preg_match( '/\.tp-home-prov\{[^}]*align-items:stretch/', $c ); };
tp441_mut( 'الف·ب CSS: نوار شمار translateY(-50%)، ستون‌ها stretch، بی رنگ خام و letter-spacing — جهش: رنگ خام', $w47_c2, $w47_css, $w47_css . "\nhtml .tp-page.tp-home .x{color:#ff0000}" );

/* قاب فیلد جست‌وجوی پنجرۀ استان‌ها عنصر رابط است (۳٫۰ در برابر زمینه): از توکن متن، نه --tp-border تزئینی — کنتراست ۱٫۳۶ گرفته بود */
$w47_fld = function ( $c ) { return 1 === preg_match( '/\.tp-home-dlg__q\{[^}]*border:1px solid var\(--tp-text(?:-2|-strong)?\)/', $c ); };
tp441_mut( 'ب CSS: قاب فیلد جست‌وجوی پنجره از توکن متن — جهش: --tp-border', $w47_fld, $w47_css, str_replace( 'border:1px solid var(--tp-text-2);background:var(--tp-surface)', 'border:1px solid var(--tp-border);background:var(--tp-surface)', $w47_css ) );
