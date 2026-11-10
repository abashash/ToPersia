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
 * وقتی سایت پشت «به‌زودی» است نویسنده اصلاً پرونده نمی‌سازد، پس این‌جا چیزی برای مهمان نیست.
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
$tp_file = topersia_frag_dir( dirname( __DIR__, 2 ) ) . '/' . $tp_key . '.html';

if ( '' === $tp_key || ! is_file( $tp_file ) || filemtime( $tp_file ) < time() - TOPERSIA_FRAG_TTL ) {
	$tp_miss();
}

header( 'Content-Type: text/html; charset=UTF-8' );
header( 'X-TP-Frag: hit' );
readfile( $tp_file );
