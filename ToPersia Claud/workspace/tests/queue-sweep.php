<?php
/**
 * کارهای صف هسته که خودِ یک آزمون محلی می‌سازد — ۲ج٫۵ · P5-D290.
 *
 * از render ۴٫۵٫۰ هر ذخیره، انتشار، حذف یا ردیف رابطۀ پستِ `tp_` بازسازی گالری
 * تجمیعی را در صف می‌گذارد؛ پس هر آزمونی که پست موقت می‌سازد ردیف صف هم می‌سازد.
 *
 *   $floor = tp_queue_floor();   // پیش از ساختن هر داده، پس از wp-load
 *   …
 *   tp_queue_sweep( $floor );    // در پایان، پس از پاک‌کردن پست‌های موقت
 *
 * پاک‌سازی: کارهای در انتظار گالری تجمیعی همین‌جا اجرا می‌شوند (والدها بی دادۀ
 * آزمون بازساخته) و هر ردیف `topersia…` بالاتر از کف — با لاگش — پاک.
 */

/** بزرگ‌ترین شناسۀ کار صف پیش از آزمون؛ ‎-1 اگر صف هسته نیست. */
function tp_queue_floor() {
	global $wpdb;
	if ( ! function_exists( 'topersia_queue_ready' ) || ! topersia_queue_ready() ) {
		return -1;
	}
	return (int) $wpdb->get_var( "SELECT COALESCE( MAX( action_id ), 0 ) FROM {$wpdb->prefix}actionscheduler_actions" );
}

/** اجرای کارهای مانده و پاک‌کردن ردیف‌های ساختۀ آزمون. @return int شمار ردیف پاک‌شده */
function tp_queue_sweep( $floor ) {
	global $wpdb;
	if ( $floor < 0 || ! function_exists( 'topersia_queue_run_now' ) ) {
		return 0;
	}
	for ( $i = 0; $i < 8 && topersia_queue_run_now( 'topersia_gallery_agg_rebuild' ); $i++ ) {
		continue;
	}
	$n = 0;
	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT action_id FROM {$wpdb->prefix}actionscheduler_actions WHERE action_id > %d AND hook LIKE %s", $floor, 'topersia%' ) ) as $aid ) {
		ActionScheduler::store()->delete_action( (int) $aid );
		$n++;
	}
	return $n;
}
