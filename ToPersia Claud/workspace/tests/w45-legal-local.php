<?php
/**
 * دستۀ 45 بخش الف·۱–۲ — صفحه‌های ثابت و پاورقی روی وردپرس واقعی (fa · en · ar):
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/w45-legal-local.php
 * فقط خواندن (GET صفحه‌ها، پیوندهای پاورقی و نقشۀ سایت).
 *
 * «عیناً»: هر واحد متن صفحه (h1، h2، p به تفکیک <br>، li، th، td) باید زیررشتۀ متن منبع باشد
 * (`PAGES-TEXT` و بخش ۲ `PRIVACY-DRAFT`، بی نشانۀ مارک‌داون، «هٔ» و «ۀ» یکسان). تنها افزوده‌ها:
 * تاریخ انتشار به‌جای {تاریخ انتشار}، و عنوان en/ar تماس (فایل عنوان ندارد). جهش: جملۀ دست‌خورده باید بیفتد.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }
if ( ! function_exists( 'is_plugin_active' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }

$GLOBALS['pL'] = 0; $GLOBALS['fL'] = 0;
function tL( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['pL']++; echo "  ✅ $label\n"; } else { $GLOBALS['fL']++; echo "  ❌ $label   " . mb_substr( (string) $note, 0, 400 ) . "\n"; } }

$root = dirname( __DIR__ );
$norm = function ( $s ) {
	$s = html_entity_decode( (string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$s = str_replace( array( '**', '`', "هٔ", "\u{200F}" ), array( '', '', 'ۀ', '' ), $s );
	/* گونه‌نگاری wptexturize وردپرس (… “ ” ‘ ’ –) هم‌ارز نویسۀ منبع است؛ واژه‌ای عوض نمی‌شود. */
	$s = str_replace( array( '…', '“', '”', '‘', '’', '–', '″', '′' ), array( '...', '"', '"', "'", "'", '-', '"', "'" ), $s );
	return trim( preg_replace( '/\s+/u', ' ', $s ) );
};
$prv    = (string) file_get_contents( $root . '/docs/legal/PRIVACY-DRAFT.md' );
$prv2   = preg_match( '/^## بخش ۲ — .*?^## بخش ۳ /msu', $prv, $m ) ? $m[0] : '';
$corpus = $norm( str_replace( "\n", ' ¶ ', file_get_contents( $root . '/docs/legal/PAGES-TEXT-2026-11-04.md' ) . "\n" . $prv2 ) );
$in     = function ( $unit, array $allow = array() ) use ( $norm, $corpus ) {
	$u = $norm( $unit );
	if ( '' === $u || in_array( $u, $allow, true ) ) { return true; }
	foreach ( $allow as $a ) { $u = str_replace( $a, '¦', $u ); }
	foreach ( array_filter( array_map( 'trim', explode( '¦', $u ) ) ) as $piece ) {
		if ( false === mb_strpos( $corpus, $piece ) ) { return false; }
	}
	return true;
};

/* خودآزمایی سنجه: جملۀ واقعی می‌گذرد، جملۀ دست‌خورده نه. */
tL( 'سنجۀ «عیناً»: جملۀ منبع می‌گذرد', $in( 'سرویس «همان‌گونه که هست» و بدون تضمین ارائه می‌شود.' ) );
tL( 'سنجۀ «عیناً» — جهش: یک واژۀ عوض‌شده می‌افتد', ! $in( 'سرویس «همان‌گونه که هست» و با تضمین ارائه می‌شود.' ) );

$get = function ( $url ) {
	$ch = curl_init( $url ); curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_FOLLOWLOCATION => 0 ) );
	$b = curl_exec( $ch ); $c = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE ); curl_close( $ch ); return array( $c, (string) $b );
};
$units = function ( $html ) {
	$out = array();
	preg_match_all( '#<(h1|h2|li|th|td|p)\b[^>]*>(.*?)</\1>#su', $html, $mm, PREG_SET_ORDER );
	foreach ( $mm as $x ) { foreach ( preg_split( '#<br\s*/?>#', $x[2] ) as $piece ) { $out[] = array( $x[1], wp_strip_all_tags( $piece ) ); } }
	return $out;
};

$sitekit = is_plugin_active( 'google-site-kit/google-site-kit.php' );
list( , $sm ) = $get( home_url( '/page-sitemap.xml' ) );
$digits = array( 'fa' => '۰۱۲۳۴۵۶۷۸۹', 'ar' => '٠١٢٣٤٥٦٧٨٩' );
$year   = function ( $l ) use ( $digits ) { $y = wp_date( 'Y' ); return isset( $digits[ $l ] ) ? strtr( $y, array_combine( str_split( '0123456789' ), mb_str_split( $digits[ $l ] ) ) ) : $y; };
/* «روز … سال» نخستین انتشار محلی (متای _tp_legal_published صفحۀ فارسی) به خط زبان؛ ماه با $months */
$date_want = function ( $fa, $l ) use ( $digits ) { $ts = (int) get_post_meta( $fa, '_tp_legal_published', true ); if ( ! $ts ) { return "\0"; } $y = gmdate( 'Y', $ts ); return isset( $digits[ $l ] ) ? strtr( $y, array_combine( str_split( '0123456789' ), mb_str_split( $digits[ $l ] ) ) ) : $y; };
$months = array( 'fa' => 'نوامبر|اکتبر|دسامبر|ژانویه|فوریه|مارس|آوریل|مه|ژوئن|ژوئیه|اوت|سپتامبر', 'en' => 'January|February|March|April|May|June|July|August|September|October|November|December', 'ar' => 'يناير|فبراير|مارس|أبريل|مايو|يونيو|يوليو|أغسطس|سبتمبر|أكتوبر|نوفمبر|ديسمبر' );
$table  = array();

foreach ( array( 'terms', 'about', 'contact', 'privacy-policy' ) as $slug ) {
	global $wpdb;
	$ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish' AND post_name = %s", $slug ) ) );
	$fa  = 0;
	foreach ( $ids as $i ) { if ( 'fa' === apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $i, 'element_type' => 'post_page' ) ) ) { $fa = (int) $i; } }
	tL( "$slug: صفحۀ فارسی منتشرشده با نامک ثابت، دقیقاً سه صفحه با این نامک", $fa > 0 && 3 === count( $ids ), count( $ids ) );
	if ( 'privacy-policy' === $slug ) { tL( 'حریم خصوصی همان صفحۀ wp_page_for_privacy_policy است (نه صفحۀ دوم)', (int) get_option( 'wp_page_for_privacy_policy' ) === $fa, (string) get_option( 'wp_page_for_privacy_policy' ) ); }
	if ( ! $fa ) { continue; }
	$row = array( $slug );

	foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
		$id = (int) apply_filters( 'wpml_object_id', $fa, 'page', false, $l );
		$row[] = $id;
		echo "\n[$slug · $l #$id]\n";
		tL( "$slug/$l: ترجمۀ منتشرشده با همان نامک", $id && 'publish' === get_post_status( $id ) && $slug === get_post_field( 'post_name', $id ) );
		if ( ! $id ) { continue; }
		$url = ( 'fa' === $l ? '' : "/$l" ) . "/$slug/";
		$row[] = $url;
		list( $code, $h ) = $get( home_url( $url ) );
		$title = get_the_title( $id );
		tL( "$l: پاسخ ۲۰۰", 200 === $code, (string) $code );
		tL( "$l: lang و dir سند", 1 === preg_match( '#<html[^>]*\bdir="' . ( 'en' === $l ? 'ltr' : 'rtl' ) . '"[^>]*\blang="' . $l . '#', $h ) || 1 === preg_match( '#<html[^>]*\blang="' . $l . '[^"]*"[^>]*\bdir="' . ( 'en' === $l ? 'ltr' : 'rtl' ) . '"#', $h ) || ( 'en' === $l && 1 === preg_match( '#<html[^>]*\blang="en#', $h ) && false === strpos( substr( $h, 0, 400 ), 'dir="rtl"' ) ) );
		tL( "$l: یک h1 با عنوان صفحه", 1 === preg_match_all( '#<h1\b#', $h ) && false !== strpos( $h, '<h1>' . esc_html( $title ) . '</h1>' ) );
		tL( "$l: <title> با عنوان صفحه", 1 === preg_match( '#<title>' . preg_quote( esc_html( $title ), '#' ) . '\b#u', $h ) );
		$desc = (string) get_post_meta( $id, 'rank_math_description', true );
		tL( "$l: متای توضیح ناتهی، چاپ‌شده و برگرفته از متن", '' !== $desc && false !== strpos( $h, '<meta name="description" content="' . esc_attr( $desc ) ) && $in( str_replace( ' · ', '¦', $desc ) ), $desc );
		tL( "$l: بی noindex خودِ صفحه (rank_math_robots)", ! get_post_meta( $id, 'rank_math_robots', true ) );
		tL( "$l: در نقشۀ سایت", false !== strpos( $sm, '<loc>' . home_url( $url ) . '</loc>' ) );

		$body  = preg_match( '#<div class="tp-page tp-legal">.*?</main>#s', $h, $bm ) ? $bm[0] : '';
		$stamp = preg_match( '#(?:[0-9]+|[۰-۹]+|[٠-٩]+) (?:' . $months[ $l ] . ') (?:[0-9]{4}|[۰-۹]{4}|[٠-٩]{4})#u', $body, $sm2 ) ? $sm2[0] : '';
		$allow = array_filter( array( $stamp, ( 'contact' === $slug && 'fa' !== $l ) ? $title : '' ) );
		$bad = array(); $n = 0;
		foreach ( $units( $body ) as $u ) { $n++; if ( ! $in( $u[1], $allow ) ) { $bad[] = $u[0] . ': ' . mb_substr( $norm( $u[1] ), 0, 90 ); } }
		tL( "$l: متن عیناً از منبع ($n واحد)", $n > 2 && ! $bad, implode( ' ‖ ', array_slice( $bad, 0, 3 ) ) );
		if ( in_array( $slug, array( 'terms', 'privacy-policy' ), true ) ) {
			tL( "$l: «آخرین به‌روزرسانی» = تاریخ انتشار محلی", '' !== $stamp && false === strpos( $body, '{' ) && false === strpos( $body, '۲۰ اکتبر' ) && false === strpos( $body, '20 October' ) && false !== strpos( $stamp, $date_want( $fa, $l ) ), $stamp );
		}
		tL( "$l: بی خط یادداشت و یادداشت مشاور", false === mb_strpos( $body, 'پیش از انتشار' ) && false === mb_strpos( $body, 'مشاور حقوقی تأیید' ) && false === stripos( $body, 'legal counsel' ) && false === mb_strpos( $body, 'المستشار القانوني' ) && false === mb_strpos( $body, 'یادداشت پیاده‌سازی' ) );
		if ( 'privacy-policy' === $slug ) {
			tL( "$l: بند Google Analytics " . ( $sitekit ? 'هست (Site Kit فعال)' : 'نیست (Site Kit نصب نیست)' ), $sitekit === ( false !== strpos( $body, 'Google Analytics' ) ) );
			tL( "$l: جدول کوکی در ظرف کشش‌پذیر", false !== strpos( $body, '<div class="tp-data-table" tabindex="0" role="group"><table>' ) );
		}
		if ( 'contact' === $slug ) {
			tL( "$l: ایمیل‌ها متن‌اند، نه پیوند و نه فرم", false !== strpos( $body, 'contact@topersia.com' ) && false === strpos( $body, 'mailto:' ) && false === stripos( $body, '<form' ) );
		}

		/* پاورقی: P5-D528 — افزونه هیچ خروجی ندارد (جای P5-D520) */
		tL( "$l: پاورقی افزونه نیست — بی .tp-footer (متن کپی‌رایت از تنظیم خود وودمارت)", false === strpos( $h, 'class="tp-footer' ) );
	}
	$table[] = $row;
}

/* پاورقی روی صفحۀ موجودیت و صفحۀ اول هم — P5-D528: نیست */
foreach ( array( '/', '/province/isfahan/', '/en/' ) as $u ) {
	list( $c, $h ) = $get( home_url( $u ) );
	tL( "بی پاورقی افزونه روی $u", 200 === $c && false === strpos( $h, 'class="tp-footer' ) );
}
echo "\nنامک · fa · en · ar\n";
foreach ( $table as $r ) { echo '  ' . $r[0] . ' · #' . $r[1] . ' ' . $r[2] . ' · #' . $r[3] . ' ' . $r[4] . ' · #' . $r[5] . ' ' . $r[6] . "\n"; }
echo str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['pL']}   افتاده: {$GLOBALS['fL']}\n";
exit( $GLOBALS['fL'] ? 1 : 0 );
