<?php
/**
 * پیش‌بررسی Action Scheduler پیش از پشتیبان — دستهٔ 55-د · P5-D649 (ب). فقط می‌خواند؛ هیچ ردیفی را عوض نمی‌کند.
 *
 *   wp --skip-plugins --skip-themes eval-file scripts/lib/tp-backup-as.php <خروجی.json>
 *
 * ریشه: ۲۰ نوامبر `tp-backup` با کد ۴ افتاد چون پاک‌سازی خودکار Action Scheduler (`ActionScheduler_QueueCleaner`: وضعیت complete و
 * canceled با آخرین تلاش قدیمی‌تر از دورهٔ نگه‌داری، پیش‌فرض ۳۱ روز) میان اثر انگشت «پیش» و «پس» ردیف‌ها را پاک کرد.
 * خروجی: واقعیت‌های خام؛ حکم را `tpb_as_verdict()` در lib می‌دهد (تابع خالص، آزمون‌پذیر).
 * دورهٔ نگه‌داری از فیلتر `action_scheduler_retention_period` نمی‌آید (افزونه‌ها بارگذاری نمی‌شوند)؛ ۳۱ روز AS عدد ثابت کلاس است و
 * جدول `rows` همهٔ ردیف‌های complete/canceled را با لحظهٔ مشمول‌شدن می‌دهد تا حکم با هر پنجره‌ای حساب شود.
 */
global $wpdb;
$t   = $wpdb->prefix . 'actionscheduler_actions';
$out = array( 'retention' => 2678400, 'now' => time(), 'table' => (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$t}'" ) );
if ( $out['table'] ) {
	$out['purgeable_last_attempt'] = array_map( 'strval', $wpdb->get_col( "SELECT last_attempt_gmt FROM {$t} WHERE status IN ('complete','canceled') AND last_attempt_gmt > '1970-01-02' ORDER BY last_attempt_gmt ASC LIMIT 5000" ) );
	$out['stale_in_progress']      = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='in-progress' AND last_attempt_gmt <= '" . gmdate( 'Y-m-d H:i:s', time() - 300 ) . "'" );
	$out['pending_due']            = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t} WHERE status='pending' AND scheduled_date_gmt <= '" . gmdate( 'Y-m-d H:i:s' ) . "'" );
}
file_put_contents( (string) $args[0], wp_json_encode( $out ) );
echo "ok\n";
