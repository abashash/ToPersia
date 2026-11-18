<?php
/**
 * آمار تجمیعی بی‌نام — دستۀ 54 بند ۴ (P5-D622).
 *
 * جدول روزانه، نه ردیف به‌ازای هر بازدید:
 *   {prefix}tp_ad_stats        ad_id · slot · day · lang · imp · clk     کلید اصلی (ad_id, slot, day, lang) — upsert
 *   {prefix}tp_ad_stats_month  ad_id · slot · month · lang · imp · clk   خلاصۀ روزهای کهنه‌تر از ۱۳ ماه
 * هیچ ستونی برای IP، کارگزار، کوکی، نشانی یا شناسۀ کاربر نیست.
 *
 * مهاجرت نسخه‌دار و بازگشت‌پذیر: گزینۀ tp_ads_db_version؛ topersia_ads_migrate_up() (dbDelta، ایدمپوتنت) و
 * topersia_ads_migrate_down() (فقط از scripts/tp-ads-migrate.php down --confirm؛ پیش از آن CSV از همه).
 * خلاصۀ ماهانه با رویداد روزانۀ topersia_ads_daily.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_ADS_KEEP_MONTHS' ) ) {
	define( 'TOPERSIA_ADS_KEEP_MONTHS', 13 );
}

if ( ! function_exists( 'topersia_ads_tables' ) ) {
	function topersia_ads_tables() {
		global $wpdb;
		return array( 'day' => $wpdb->prefix . 'tp_ad_stats', 'month' => $wpdb->prefix . 'tp_ad_stats_month' );
	}
}

if ( ! function_exists( 'topersia_ads_schema_sql' ) ) {
	/** CREATE برای dbDelta — دو فاصله پیش از PRIMARY KEY قاعدۀ dbDelta است. */
	function topersia_ads_schema_sql( $charset = '' ) {
		$t = topersia_ads_tables();
		return array(
			"CREATE TABLE {$t['day']} (
  ad_id bigint(20) unsigned NOT NULL,
  slot varchar(32) NOT NULL,
  day date NOT NULL,
  lang varchar(5) NOT NULL DEFAULT '',
  imp int(10) unsigned NOT NULL DEFAULT 0,
  clk int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (ad_id,slot,day,lang),
  KEY day (day)
) {$charset};",
			"CREATE TABLE {$t['month']} (
  ad_id bigint(20) unsigned NOT NULL,
  slot varchar(32) NOT NULL,
  month char(7) NOT NULL,
  lang varchar(5) NOT NULL DEFAULT '',
  imp bigint(20) unsigned NOT NULL DEFAULT 0,
  clk bigint(20) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (ad_id,slot,month,lang)
) {$charset};",
		);
	}
}

if ( ! function_exists( 'topersia_ads_migrate_up' ) ) {
	/** نسخۀ ۱: دو جدول. ایدمپوتنت. */
	function topersia_ads_migrate_up() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( topersia_ads_schema_sql( $wpdb->get_charset_collate() ) as $sql ) {
			dbDelta( $sql );
		}
		update_option( 'tp_ads_db_version', TOPERSIA_ADS_DB, false );
		if ( ! wp_next_scheduled( 'topersia_ads_daily' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'topersia_ads_daily' );
		}
	}
}

if ( ! function_exists( 'topersia_ads_migrate_down' ) ) {
	/** برگشت نسخۀ ۱: دو جدول و گزینۀ نسخه. داده پاک می‌شود — فقط پس از CSV و با تأیید صریح (اسکریپت). */
	function topersia_ads_migrate_down() {
		global $wpdb;
		foreach ( topersia_ads_tables() as $t ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$t}" );   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- نام جدول از پیشوند
		}
		delete_option( 'tp_ads_db_version' );
		wp_clear_scheduled_hook( 'topersia_ads_daily' );
	}
}

/* به‌روزرسانی افزونه بی فعال‌سازی دوباره */
add_action( 'admin_init', function () {
	if ( (int) get_option( 'tp_ads_db_version', 0 ) !== TOPERSIA_ADS_DB ) {
		topersia_ads_migrate_up();
	}
} );

if ( ! function_exists( 'topersia_ads_table_ok' ) ) {
	/** جدول هست؟ (نسخه برابر) — بی آن هیچ نوشتنی. */
	function topersia_ads_table_ok() {
		return (int) get_option( 'tp_ads_db_version', 0 ) === TOPERSIA_ADS_DB;
	}
}

if ( ! function_exists( 'topersia_ads_record' ) ) {
	/**
	 * افزودن به آمار روز — upsert یک ردیف برای هر (تبلیغ، جایگاه، روز، زبان)، و شمار کل برای سقف.
	 *
	 * @param int    $ad_id
	 * @param string $slot  شناسۀ ثبت‌شده
	 * @param string $lang  fa · en · ar
	 * @param int    $imp
	 * @param int    $clk
	 */
	function topersia_ads_record( $ad_id, $slot, $lang, $imp, $clk ) {
		global $wpdb;

		$ad_id = absint( $ad_id );
		$imp   = absint( $imp );
		$clk   = absint( $clk );

		if ( ! $ad_id || ( ! $imp && ! $clk ) || ! isset( topersia_ads_slots()[ $slot ] ) || ! in_array( $lang, array( 'fa', 'en', 'ar' ), true ) || ! topersia_ads_table_ok() ) {
			return false;
		}

		$t  = topersia_ads_tables();
		$ok = $wpdb->query( $wpdb->prepare(
			"INSERT INTO {$t['day']} (ad_id, slot, day, lang, imp, clk) VALUES (%d, %s, %s, %s, %d, %d)
			 ON DUPLICATE KEY UPDATE imp = imp + VALUES(imp), clk = clk + VALUES(clk)",   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$ad_id, $slot, wp_date( 'Y-m-d' ), $lang, $imp, $clk
		) );

		/* شمار کل — بالا بردن اتمی در خود جدول متا؛ اگر ردیف نیست، ساخته می‌شود */
		foreach ( array( '_tp_ad_imp' => $imp, '_tp_ad_clk' => $clk ) as $key => $n ) {
			if ( ! $n ) {
				continue;
			}
			$done = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + %d WHERE post_id = %d AND meta_key = %s", $n, $ad_id, $key ) );
			if ( ! $done ) {
				add_post_meta( $ad_id, $key, $n, true ) || update_post_meta( $ad_id, $key, (int) get_post_meta( $ad_id, $key, true ) + $n );
			}
			wp_cache_delete( $ad_id, 'post_meta' );
		}

		return false !== $ok;
	}
}

if ( ! function_exists( 'topersia_ads_rollup' ) ) {
	/** روزهای کهنه‌تر از ۱۳ ماه ← خلاصۀ ماهانه، سپس پاک. در یک تراکنش. */
	function topersia_ads_rollup( $now = null ) {
		global $wpdb;

		if ( ! topersia_ads_table_ok() ) {
			return 0;
		}

		$t   = topersia_ads_tables();
		$cut = gmdate( 'Y-m-01', strtotime( '-' . TOPERSIA_ADS_KEEP_MONTHS . ' months', null === $now ? time() : (int) $now ) );

		$wpdb->query( 'START TRANSACTION' );
		$ins = $wpdb->query( $wpdb->prepare(
			/* جدول مشتق: ارجاع به ستون منبع در ON DUPLICATE با GROUP BY مستقیم مجاز نیست */
			"INSERT INTO {$t['month']} (ad_id, slot, month, lang, imp, clk)
			 SELECT r.ad_id, r.slot, r.mo, r.lang, r.si, r.sc FROM (
			   SELECT ad_id, slot, DATE_FORMAT(day, '%%Y-%%m') mo, lang, SUM(imp) si, SUM(clk) sc FROM {$t['day']} WHERE day < %s GROUP BY ad_id, slot, mo, lang
			 ) r
			 ON DUPLICATE KEY UPDATE imp = {$t['month']}.imp + r.si, clk = {$t['month']}.clk + r.sc",   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$cut
		) );
		if ( false === $ins ) {
			$wpdb->query( 'ROLLBACK' );
			return 0;
		}
		$del = $wpdb->query( $wpdb->prepare( "DELETE FROM {$t['day']} WHERE day < %s", $cut ) );   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( false === $del ? 'ROLLBACK' : 'COMMIT' );
		return false === $del ? 0 : (int) $del;
	}
}

add_action( 'topersia_ads_daily', 'topersia_ads_rollup' );

if ( ! function_exists( 'topersia_ads_report' ) ) {
	/**
	 * گزارش جمع نمایش و کلیک به‌ازای تبلیغ (و جایگاه) در بازه — روزانه و ماهانه با هم.
	 *
	 * @param string $from Y-m-d (شامل)
	 * @param string $to   Y-m-d (شامل)
	 * @param string $slot تهی = همه
	 * @return array[] ad_id · slot · imp · clk
	 */
	function topersia_ads_report( $from, $to, $slot = '' ) {
		global $wpdb;

		if ( ! topersia_ads_table_ok() ) {
			return array();
		}

		$t     = topersia_ads_tables();
		$where = '' !== $slot ? $wpdb->prepare( ' AND slot = %s', $slot ) : '';
		$day   = $wpdb->get_results( $wpdb->prepare( "SELECT ad_id, slot, SUM(imp) imp, SUM(clk) clk FROM {$t['day']} WHERE day BETWEEN %s AND %s{$where} GROUP BY ad_id, slot", $from, $to ), ARRAY_A );   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$mon   = $wpdb->get_results( $wpdb->prepare( "SELECT ad_id, slot, SUM(imp) imp, SUM(clk) clk FROM {$t['month']} WHERE month BETWEEN %s AND %s{$where} GROUP BY ad_id, slot", substr( $from, 0, 7 ), substr( $to, 0, 7 ) ), ARRAY_A );   // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$out = array();
		foreach ( array_merge( (array) $day, (array) $mon ) as $r ) {
			$k = (int) $r['ad_id'] . '|' . $r['slot'];
			if ( ! isset( $out[ $k ] ) ) {
				$out[ $k ] = array( 'ad_id' => (int) $r['ad_id'], 'slot' => (string) $r['slot'], 'imp' => 0, 'clk' => 0 );
			}
			$out[ $k ]['imp'] += (int) $r['imp'];
			$out[ $k ]['clk'] += (int) $r['clk'];
		}
		return array_values( $out );
	}
}
