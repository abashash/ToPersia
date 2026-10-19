<?php
/**
 * درخواست‌های حریم خصوصیِ کاربر آزمون — فقط topersia.local.  P5-D375
 *
 *   php tests/local-dr.php [reset]      → JSON { export, erase } (و با reset: درخواست‌ها را برمی‌دارد)
 *
 * `tests/user-flows.js` پس از کلیک دکمه‌های پنل می‌پرسد درخواست ثبت شده یا نه و در پایان همان‌ها را برمی‌دارد
 * (نوشتهٔ `user_request` هستهٔ وردپرس؛ حساب آزمون پاک نمی‌شود).
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
$out  = array( 'export' => false, 'erase' => false, 'removed' => 0 );

if ( $user && class_exists( 'ToPersia_Data_Requests' ) ) {

	$out['export'] = ToPersia_Data_Requests::active_request( $user, 'export' );
	$out['erase']  = ToPersia_Data_Requests::active_request( $user, 'erase' );

	if ( isset( $argv[1] ) && 'reset' === $argv[1] ) {
		foreach ( get_posts( array( 'post_type' => 'user_request', 'post_status' => 'any', 'title' => $user->user_email, 'numberposts' => 50 ) ) as $p ) {
			wp_delete_post( $p->ID, true );
			$out['removed']++;
		}
	}
}

echo wp_json_encode( $out );
