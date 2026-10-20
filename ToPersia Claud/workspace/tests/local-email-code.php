<?php
/**
 * ابزار آزمون مرورگری ورود ایمیلی — فقط topersia.local (۱٫۱۴٫۰ · P5-D391)
 *
 *   php tests/local-email-code.php set <token> <code>    کد شناخته‌شده برای توکنی که مرورگر گرفت (HMAC، نه کد خام)
 *   php tests/local-email-code.php expire <token>        انقضای کد
 *   php tests/local-email-code.php cool <email>          برداشتن فاصلهٔ ۶۰ ثانیه
 *   php tests/local-email-code.php cap <email>           پرکردن سقف ساعتی همان نشانی
 *   php tests/local-email-code.php state <email> <since> حساب (بی نشانی در خروجی) + شمار نامه‌های ثبت‌شده و ارسال‌شده از since
 *   php tests/local-email-code.php cleanup <needle>      حساب‌های مشترک آزمون با این تکه در ایمیل + ترنزینت‌های کانال ایمیل
 *
 * نامه هرگز فرستاده نمی‌شود (ثابت‌های SMTP محلی نیستند)؛ این ابزار کد را نمی‌خواند — یکی می‌نشاند.
 */
$tp_args = array_slice( $argv, 1 );
require_once __DIR__ . '/env.php';
$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$cmd = (string) ( $tp_args[0] ?? '' );
$out = array( 'ok' => false );

switch ( $cmd ) {
	case 'set':
		$tok = (string) ( $tp_args[1] ?? '' );
		$p   = get_transient( 'tp_ec_' . $tok );
		if ( is_array( $p ) && preg_match( '/^\d{6}$/', (string) ( $tp_args[2] ?? '' ) ) ) {
			$p['hash'] = ToPersia_Auth::code_hash( $tok, (string) $tp_args[2] );
			set_transient( 'tp_ec_' . $tok, $p, ToPersia_Email_Code::TTL );
			$out['ok'] = true;
		}
		break;
	case 'expire':
		$out['ok'] = delete_transient( 'tp_ec_' . (string) ( $tp_args[1] ?? '' ) );
		break;
	case 'cool':
	case 'cap':
		$k = 'tp_ec_rl_' . ToPersia_Email_Code::key_of( strtolower( (string) ( $tp_args[1] ?? '' ) ) );
		$v = get_transient( $k );
		$v = is_array( $v ) ? $v : array( 'h' => array( 'count' => 0, 'start' => time() ), 'd' => array( 'count' => 0, 'start' => time() ) );
		$v['last'] = 0;
		if ( 'cap' === $cmd ) { $v['h'] = array( 'count' => ToPersia_Email_Code::PER_EMAIL_H, 'start' => time() ); }
		$out['ok'] = set_transient( $k, $v, DAY_IN_SECONDS );
		break;
	case 'state':
		global $wpdb;
		$u     = get_user_by( 'email', strtolower( (string) ( $tp_args[1] ?? '' ) ) );
		$since = wp_date( 'Y-m-d H:i:s', (int) ( $tp_args[2] ?? 0 ) );   /* logged_at = current_time('mysql') — زمان محلی سایت */
		$t     = $wpdb->prefix . 'topersia_log';
		$col   = function ( $like ) use ( $wpdb, $t, $since ) {
			return $wpdb->get_var( "SHOW TABLES LIKE '{$t}'" ) ? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE channel = 'mail' AND message LIKE %s AND logged_at >= %s", $like, $since ) ) : -1;   // phpcs:ignore
		};
		$out = array(
			'ok'       => true,
			'user'     => $u ? (int) $u->ID : 0,
			'role'     => $u ? implode( ',', $u->roles ) : '',
			'neutral'  => $u ? ToPersia_Privacy::is_neutral( (string) $u->display_name ) : false,
			'verified' => $u ? (int) get_user_meta( $u->ID, '_topersia_email_verified', true ) > 0 : false,
			'captured' => $col( '%فرستاده نشد%' ),
			'sent'     => $col( 'ایمیل فرستاده شد%' ),
		);
		break;
	case 'cleanup':
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$needle = (string) ( $tp_args[1] ?? '' );
		$n      = 0;
		if ( strlen( $needle ) >= 8 ) {
			foreach ( get_users( array( 'search' => '*' . $needle . '*', 'search_columns' => array( 'user_email' ), 'role' => 'subscriber' ) ) as $u ) {
				wp_delete_user( $u->ID );
				$n++;
			}
		}
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_tp\\_ec\\_%' OR option_name LIKE '\\_transient\\_timeout\\_tp\\_ec\\_%'" );   // phpcs:ignore
		$out = array( 'ok' => true, 'removed' => $n );
		break;
}

echo wp_json_encode( $out ), "\n";
