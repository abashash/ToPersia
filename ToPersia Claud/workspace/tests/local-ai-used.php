<?php
/**
 * مصرف امروزِ هوش مصنوعیِ کاربر آزمون را می‌نشاند — فقط topersia.local.  ۴٫۳٫۰ پذیرش
 *
 *   php tests/local-ai-used.php <n>      → مقدار پیشین روی stdout
 *
 * برای سنجیدن سقف روزانه بی‌آنکه ده بار تولید شود: `tests/user-flows.js`
 * مصرف را روی سقف می‌گذارد، ۴۲۹ را می‌سنجد، و مقدار پیشین را برمی‌گرداند.
 * فقط متای `_topersia_ai_daily` کاربر `tp_test_430` را می‌نویسد.
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

$user = get_user_by( 'login', 'tp_test_430' );

if ( ! $user || ! isset( $argv[1] ) || ! ctype_digit( (string) $argv[1] ) || ! function_exists( 'topersia_g_ai_user_used' ) ) {
	fwrite( STDERR, "کاربر آزمون، عدد یا topersia-render نیست\n" );
	exit( 2 );
}

$before = topersia_g_ai_user_used( $user->ID );

update_user_meta( $user->ID, '_topersia_ai_daily', array( 'd' => topersia_g_ai_day(), 'n' => (int) $argv[1] ) );

echo (int) $before;
