<?php
/**
 * Plugin Name: ToPersia — کاوشگر HTTP محلی
 * Description: فقط topersia.local — هر درخواست HTTP بیرونیِ وردپرس را در http-probe.log می‌نویسد. ۴٫۳٫۰ پذیرش.
 *
 * برای شمردن فراخوانی API هوش مصنوعی هنگام بار صفحه برای مهمان (باید صفر
 * باشد) و هر تماس بیرونیِ دیگر. در mu-plugins نسخهٔ محلی کپی می‌شود؛ به
 * سایت زنده نمی‌رود. درخواست را نمی‌بندد و عوض نمی‌کند — فقط می‌نویسد.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wp_get_environment_type' ) || 'local' !== wp_get_environment_type() ) {
	return;
}

add_filter( 'pre_http_request', function ( $pre, $args, $url ) {

	$who  = function_exists( 'is_user_logged_in' ) && did_action( 'init' ) && is_user_logged_in() ? 'user' : 'guest';
	$line = gmdate( 'Y-m-d\TH:i:s\Z' ) . "\t" . $who . "\t" . ( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' )
		. "\t" . (string) wp_parse_url( $url, PHP_URL_HOST ) . "\t" . $url . "\n";

	@file_put_contents( 'C:/wamp64/www/topersia/http-probe.log', $line, FILE_APPEND );

	return $pre;
}, 0, 3 );
