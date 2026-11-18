<?php
/**
 * مسیر سبک قطعۀ نتایج — P5-D571 (دستۀ 52 بند ۱۳).
 *
 * وردپرس را بار نمی‌کند: فقط پروندۀ کشِ همان پاسخ `?tp_partial=1` را برمی‌گرداند که وردپرس پیش‌تر ساخته و
 * topersia-g-news-ui.php در wp-content/uploads/topersia-cache/frag/ گذاشته است.
 *
 *   GET fragment.php?u=/news/?tp_topic=events      ← ۲۰۰ و HTML ناحیه‌های تعویض‌پذیر، یا ۲۰۴ «نیست»
 *
 * نبود، کهنه، کاربر واردشده یا هر ورودی ناجور ← ۲۰۴؛ مرورگر همان‌جا سراغ نشانی عادی می‌رود (که کش را هم پر می‌کند).
 * هیچ نامی از ورودی به مسیر پرونده نمی‌رسد — فقط md5. هیچ چیز نوشته نمی‌شود. پاسخ هرگز نمایه و هرگز در کش مشترک نمی‌ماند.
 * وقتی سایت پشت «به‌زودی» است نویسنده پرونده نمی‌سازد، همۀ قطعه‌ها را پاک می‌کند و نشان `off` می‌گذارد؛
 * این‌جا با دیدن همان نشان هیچ چیز نمی‌دهد (گزینۀ وردپرس از این‌جا دیده نمی‌شود).
 */

require __DIR__ . '/includes/topersia-frag-key.php';

header( 'X-Robots-Tag: noindex, nofollow' );
header( 'Cache-Control: no-store' );

$tp_miss = function () {
	http_response_code( 204 );
	exit;
};

$tp_u = ( isset( $_GET['u'] ) && is_string( $_GET['u'] ) ) ? $_GET['u'] : '';

if ( '' === $tp_u || strlen( $tp_u ) > 600 || '/' !== $tp_u[0] || 0 === strpos( $tp_u, '//' ) ) {
	http_response_code( 400 );
	exit;
}

/* کاربر واردشده همیشه از خود وردپرس می‌گیرد */
foreach ( array_keys( $_COOKIE ) as $tp_c ) {
	if ( 0 === strpos( (string) $tp_c, 'wordpress_logged_in_' ) ) {
		$tp_miss();
	}
}

$tp_parts = parse_url( $tp_u );
$tp_get   = array();

if ( ! is_array( $tp_parts ) || empty( $tp_parts['path'] ) ) {
	$tp_miss();
}

parse_str( isset( $tp_parts['query'] ) ? (string) $tp_parts['query'] : '', $tp_get );

$tp_key  = topersia_frag_key( isset( $_SERVER['HTTP_HOST'] ) ? (string) $_SERVER['HTTP_HOST'] : '', (string) $tp_parts['path'], $tp_get );
$tp_dir  = topersia_frag_dir( dirname( __DIR__, 2 ) );
$tp_file = $tp_dir . '/' . $tp_key . '.html';

/* نشان «خاموش»: سایت پشت «به‌زودی» است — حتی اگر پروندۀ کهنه‌ای مانده باشد، چیزی داده نمی‌شود */
if ( is_file( $tp_dir . '/off' ) || 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '' ) ) {
	$tp_miss();
}

if ( '' === $tp_key || ! is_file( $tp_file ) || filemtime( $tp_file ) < time() - TOPERSIA_FRAG_TTL ) {
	$tp_miss();
}

$tp_html = (string) file_get_contents( $tp_file );

/* دستۀ 54 (P5-D623): جایگاه تبلیغ چرخش ده‌دقیقه‌ای دارد — قطعه‌ای که در پنجرۀ دیگری ساخته شده کهنه است، نه «hit» */
if ( preg_match( '/data-tp-adw="(\d+)"/', $tp_html, $tp_m ) && (int) $tp_m[1] !== (int) floor( time() / TOPERSIA_FRAG_AD_WINDOW ) ) {
	$tp_miss();
}

header( 'Content-Type: text/html; charset=UTF-8' );
header( 'X-TP-Frag: hit' );
echo $tp_html; // phpcs:ignore WordPress.Security.EscapeOutput -- همان پروندۀ ساختۀ وردپرس
