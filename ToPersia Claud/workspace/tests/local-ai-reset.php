<?php
/**
 * آنچه `tests/user-flows.js` ساخت برمی‌گرداند — فقط topersia.local.  ۴٫۳٫۰ پذیرش
 *
 *   php tests/local-ai-reset.php <since> <user_used> <site_used> [--reviews] <post>:<type> …
 *
 *   <since>       زمان یونیکسِ آغاز اجرای آزمون؛ فقط چیزی که پس از آن ساخته
 *                 شده برداشته می‌شود
 *   <post>:<type> کش خروجیِ (پست، نوع، fa) — فقط keypoints و plan، هرگز
 *                 خلاصه: متای `_tpas_ai_{type}_fa` و ردیف جدول خلاصه‌ساز
 *   --reviews     نظرهای کاربر آزمون روی همان پست‌ها (فقط پاک‌سازی اجرای
 *                 نیمه‌کاره؛ اجرای کامل نظرش را برای دیدن مدیر نگه می‌دارد)
 *
 * و مصرف امروزِ کاربر آزمون و سایت را به مقدار پیشین می‌نشاند. به هیچ
 * کاربر، پست یا کش دیگری دست نمی‌زند. خروجی: JSON آنچه برداشته شد.
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
$args = array_slice( $argv, 1 );
$revs = in_array( '--reviews', $args, true );
$args = array_values( array_diff( $args, array( '--reviews' ) ) );

if ( ! $user || count( $args ) < 3 || ! ctype_digit( $args[0] ) || ! ctype_digit( $args[1] ) || ! ctype_digit( $args[2] )
	|| ! class_exists( 'TP_AI_DB' ) || ! function_exists( 'topersia_g_ai_day' ) ) {
	fwrite( STDERR, "کاربر آزمون، ورودی یا افزونه‌ها نیستند\n" );
	exit( 2 );
}

global $wpdb;

$since = (int) $args[0];
$done  = array();
$posts = array();

foreach ( array_slice( $args, 3 ) as $pair ) {

	$bits = explode( ':', $pair );
	$pid  = isset( $bits[0] ) ? absint( $bits[0] ) : 0;
	$type = isset( $bits[1] ) ? (string) $bits[1] : '';

	if ( ! $pid || ! in_array( $type, array( 'keypoints', 'plan' ), true ) ) {
		continue;
	}

	$posts[ $pid ] = true;

	$key  = '_tpas_ai_' . $type . '_fa';
	$meta = get_post_meta( $pid, $key, true );

	if ( is_array( $meta ) && isset( $meta['created'] ) && (int) $meta['created'] >= $since ) {
		delete_post_meta( $pid, $key );
		$done[] = $pid . ':' . $type . ' متا';
	}

	$row = TP_AI_DB::get_summary( $pid, $type, 'fa' );

	/* جدول زمان محلی سایت می‌نویسد؛ به یونیکس برمی‌گردد */
	if ( $row && strtotime( get_gmt_from_date( (string) $row->created_at ) . ' UTC' ) >= $since ) {
		$wpdb->delete( TP_AI_DB::summaries_table(), array( 'id' => (int) $row->id ), array( '%d' ) );
		$done[] = $pid . ':' . $type . ' ردیف';
	}
}

if ( $revs && $posts ) {
	foreach ( array_keys( $posts ) as $pid ) {
		foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, date FROM {$wpdb->prefix}jet_reviews WHERE author = %d AND post_id = %d", $user->ID, $pid ) ) as $r ) {
			if ( strtotime( get_gmt_from_date( (string) $r->date ) . ' UTC' ) >= $since ) {
				$wpdb->delete( $wpdb->prefix . 'jet_reviews', array( 'id' => (int) $r->id ), array( '%d' ) );
				$done[] = $pid . ' نظر #' . (int) $r->id;
			}
		}
	}
}

$day = topersia_g_ai_day();
update_user_meta( $user->ID, '_topersia_ai_daily', array( 'd' => $day, 'n' => (int) $args[1] ) );
set_transient( 'tp-ai-site-' . $day, (int) $args[2], 2 * DAY_IN_SECONDS );

echo wp_json_encode( $done, JSON_UNESCAPED_UNICODE );
