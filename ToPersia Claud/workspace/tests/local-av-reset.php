<?php
/**
 * سهمیهٔ بارگذاری آواتار کاربر آزمون را صفر می‌کند — فقط topersia.local.  P5-D377
 *
 *   php tests/local-av-reset.php
 *
 * `tests/user-flows.js` پیش از بارگذاری صدایش می‌زند: سقف ده بارگذاری در ساعت (ToPersia_Avatar) با اجرای پشت‌سرهم آزمون پر می‌شد.
 * فقط ترنزینت `tp_av_rl_<شناسهٔ کاربر آزمون>` پاک می‌شود.
 */
require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) {
	fwrite( STDERR, "فقط نسخهٔ محلی\n" );
	exit( 2 );
}

$user = get_user_by( 'login', 'tp_test_430' );
echo wp_json_encode( array( 'reset' => $user ? delete_transient( 'tp_av_rl_' . $user->ID ) : false ) );
