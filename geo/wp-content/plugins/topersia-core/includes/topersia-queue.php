<?php
/**
 * ToPersia Core — صف پس‌زمینۀ واحد (P5-D290 · SPRINT-4.4 ۲ج٫۵)
 *
 * Action Scheduler، بسته‌بندی‌شده در خودِ هسته (`vendor/action-scheduler`، نسخۀ
 * 3.9.3 رسمی wordpress.org). به نسخۀ درون Rank Math تکیه نمی‌شود: اگر Rank Math
 * نباشد هم صف کار می‌کند. چرا 3.9.3 و نه 4.x: نسخۀ ۴ ستون `unique_key` و یک
 * نمایۀ یکتا به جدول مشترک `actionscheduler_actions` می‌افزاید و رفتار کار یکتا
 * و پاک‌سازی شکست‌خورده‌ها را عوض می‌کند — یعنی تغییر ساختار جدولِ زنده که
 * Rank Math هم از آن می‌خواند (قاعدۀ ۱۲). 3.9.3 همان نسخۀ Rank Math است: بی
 * مهاجرت جدول. ارتقا تصمیم جداست (۲د).
 *
 * هیچ صف یا کرون موازی ساخته نمی‌شود. هر کار پس‌زمینۀ ToPersia از
 * `topersia_queue_add()` و گروه `topersia` می‌گذرد. روی زنده `DISABLE_WP_CRON`
 * روشن است؛ اجرای صف به کرون سیستمی فاز ۱۰ وابسته است.
 *
 * @package topersia-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** گروه همۀ کارهای ToPersia در Action Scheduler. */
if ( ! defined( 'TOPERSIA_QUEUE_GROUP' ) ) {
	define( 'TOPERSIA_QUEUE_GROUP', 'topersia' );
}

/** صف آماده است؟ کتابخانه بار شده و انبارش راه افتاده. */
function topersia_queue_ready() {
	return function_exists( 'as_schedule_single_action' ) && function_exists( 'as_has_scheduled_action' )
		&& class_exists( 'ActionScheduler' ) && ActionScheduler::is_initialized();
}

/**
 * یک کار پس‌زمینه — با ادغام تکراری: همان قلاب با همان آرگومان‌ها اگر هنوز «در
 * انتظار» است، دوباره افزوده نمی‌شود. کارِ «در حال اجرا» ادغام نمی‌شود: شاید
 * داده را پیش از همین تغییر خوانده باشد، پس کار تازه پس از آن می‌آید.
 *
 * @param string $hook  قلاب کار (`topersia_…`).
 * @param array  $args  آرگومان‌ها — شناسه‌ها، نه داده.
 * @param int    $delay ثانیه پس از اکنون.
 * @return int شناسۀ کار تازه؛ ۰ اگر تکراری یا صف آماده نبود.
 */
function topersia_queue_add( $hook, array $args = array(), $delay = 0 ) {

	if ( ! topersia_queue_ready() ) {
		if ( function_exists( 'topersia_log' ) ) {
			topersia_log( 'صف آماده نیست: ' . $hook, 'warning', 'queue', $args );
		}
		return 0;
	}

	if ( topersia_queue_pending( $hook, $args ) ) {
		return 0;
	}

	return (int) as_schedule_single_action( time() + max( 0, (int) $delay ), $hook, $args, TOPERSIA_QUEUE_GROUP );
}

/**
 * کارهای در انتظارِ یک قلاب — برای آزمون و پیشخوان.
 *
 * @return int[] شناسۀ کارها
 */
function topersia_queue_pending( $hook, $args = null ) {

	if ( ! topersia_queue_ready() ) {
		return array();
	}

	$query = array(
		'hook'     => $hook,
		'group'    => TOPERSIA_QUEUE_GROUP,
		'status'   => ActionScheduler_Store::STATUS_PENDING,
		'per_page' => -1,
	);

	if ( null !== $args ) {
		$query['args'] = $args;
	}

	return array_map( 'intval', (array) as_get_scheduled_actions( $query, 'ids' ) );
}

/**
 * کارهای در انتظارِ یک قلاب را همین حالا اجرا کن — WP-CLI، دکمۀ پیشخوان و آزمون.
 * ترتیب: زمان برنامه، همان ترتیب اجرای خودِ صف.
 *
 * @return int شمار کارهای اجراشده
 */
function topersia_queue_run_now( $hook ) {

	$done = 0;

	foreach ( topersia_queue_pending( $hook ) as $id ) {
		ActionScheduler::runner()->process_action( $id, 'topersia' );
		$done++;
	}

	return $done;
}
