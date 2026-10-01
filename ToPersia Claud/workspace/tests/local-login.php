<?php
/**
 * کوکی ورودِ کاربر آزمون — فقط روی topersia.local.  ۴٫۳٫۰ پذیرش
 *
 *   php tests/local-login.php [login]      → JSON کوکی‌ها برای Playwright
 *
 * هیچ گذرواژه‌ای تایپ، ارسال یا ذخیره نمی‌شود: یک نشستِ تازهٔ وردپرس برای
 * کاربر ساخته می‌شود و همان کوکی‌هایی که فرم ورود می‌ساخت، از آن تولید
 * می‌شوند. نگهبان: بیرون از topersia.local با محیط local اجرا نمی‌شود.
 */

$_SERVER['HTTP_HOST']      = 'topersia.local';
$_SERVER['SERVER_NAME']    = 'topersia.local';
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require 'C:/wamp64/www/topersia/geo/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) {
	fwrite( STDERR, "فقط نسخهٔ محلی\n" );
	exit( 2 );
}

$login = isset( $argv[1] ) ? (string) $argv[1] : 'tp_test_430';
$user  = get_user_by( 'login', $login );

/* مشترکِ آزمون، یا مدیرِ آزمونی که scripts/tp-local-441.php ساخته و نشان _tp_test_account دارد — هرگز مدیر واقعی */
$test_admin = $user && in_array( 'administrator', (array) $user->roles, true ) && '1' === (string) get_user_meta( $user->ID, '_tp_test_account', true );

if ( ! $user || ( ! in_array( 'subscriber', (array) $user->roles, true ) && ! $test_admin ) ) {
	fwrite( STDERR, "کاربر آزمونِ مشترک پیدا نشد: {$login}\n" );
	exit( 2 );
}

$expire = time() + 2 * HOUR_IN_SECONDS;
$token  = WP_Session_Tokens::get_instance( $user->ID )->create( $expire );

$out = array(
	'user'    => $user->ID,
	'cookies' => array(
		array( 'name' => AUTH_COOKIE, 'value' => wp_generate_auth_cookie( $user->ID, $expire, 'auth', $token ) ),
		array( 'name' => LOGGED_IN_COOKIE, 'value' => wp_generate_auth_cookie( $user->ID, $expire, 'logged_in', $token ) ),
	),
);

foreach ( $out['cookies'] as $i => $c ) {
	$out['cookies'][ $i ] += array( 'domain' => 'topersia.local', 'path' => '/', 'httpOnly' => true, 'secure' => false, 'sameSite' => 'Lax', 'expires' => $expire );
}

echo wp_json_encode( $out );
