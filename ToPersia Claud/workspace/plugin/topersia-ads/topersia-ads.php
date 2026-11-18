<?php
/**
 * Plugin Name: ToPersia Ads
 * Description: فروش تبلیغ مستقیم ToPersia — جایگاه‌ها در همۀ صفحه‌ها، تبلیغ زمانی، نمایشی و کلیکی، آمار تجمیعی بی‌نام، گزارش و صفحۀ فروش. بی JS بیرونی.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Author: ToPersia
 * Text Domain: topersia-ads
 * Domain Path: /languages
 *
 * دستۀ 54 — P5-D621 (فروش مستقیم با افزونۀ اختصاصی؛ شبکۀ تبلیغاتی فقط نقطۀ توسعه) · P5-D622 (زمانی، نمایشی، کلیکی؛ آمار
 * تجمیعی بی‌نام) · P5-D623 (تراکم ۳/۲؛ بی تبلیغ در هیرو و صفحه‌های حقوقی) · P5-D624 (جایگاه خالی پنهان، برای مدیر نشانۀ فروش)
 * · P5-D625 (data-nosnippet؛ بیرون از خلاصه‌ساز و ساختار قابل‌استناد).
 *
 * مرز با topersia-render: render فقط قلاب `topersia_ad_slot` · `topersia_ad_inline` · `topersia_ad_feed` را صدا می‌زند
 * (topersia_g_ad_slot · _inline · _feed در g-core) و برای فوتر `topersia_g_footer_ads_pre` را؛ صفحۀ مقصد از فیلتر موجود
 * `topersia_g_layout`. قطعۀ کش‌شدۀ نتایج (fragment.php) با `data-tp-adw` پنجرۀ دیگر کهنه است.
 * این افزونه خاموش ← render همان رفتار پیشین (فوتر از گزینۀ topersia_footer_blocks، بقیه هیچ).
 *
 * فقط محلی تا ممیزی NotebookLM (همان قاعدۀ «ساختار تازه» — topersia_cache_table_enabled): topersia_ads_enabled()
 * پیش‌فرض `'local' === wp_get_environment_type()`. خاموش ← نه نوع پست، نه جدول، نه مسیر، نه خروجی.
 *
 *   includes/tp-ads-logic.php   منطق خالص (بی وردپرس) — assert-54 مستقیم می‌خواند
 *   includes/tp-ads-data.php    نوع خصوصی tp_ad، فیلدها، اعتبارسنجی ذخیره، بارکردن تبلیغ‌های زنده
 *   includes/tp-ads-stats.php   جدول tp_ad_stats و tp_ad_stats_month — مهاجرت نسخه‌دار up/down، upsert، خلاصۀ ۱۳ ماه
 *   includes/tp-ads-render.php  قلاب‌های جایگاه، تراکم، HTML، نشانۀ «جایگاه خالی» برای مدیر، دارایی‌ها
 *   includes/tp-ads-track.php   /ad-go/{id}/ (۳۰۲، شمارش بی‌نام)، REST نمایش، robots
 *   includes/tp-ads-admin.php   پنل «تبلیغات»: فهرست، گزارش، پایان نزدیک، جایگاه‌های خالی، CSV، اقدام گروهی
 *   includes/tp-ads-pages.php   صفحۀ فروش [tp_advertise] — noindex تا تأیید کاربر
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TOPERSIA_ADS_VERSION', '1.0.0' );
define( 'TOPERSIA_ADS_DB', 1 );
define( 'TOPERSIA_ADS_CAP', 'manage_tp_ads' );
define( 'TOPERSIA_ADS_FILE', __FILE__ );
define( 'TOPERSIA_ADS_DIR', __DIR__ );

if ( ! function_exists( 'topersia_ads_enabled' ) ) {
	/** روشن فقط روی نسخۀ محلی تا ممیزی؛ صافی برای آزمون و پس از ممیزی. */
	function topersia_ads_enabled() {
		$local = function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type();
		return (bool) apply_filters( 'topersia_ads_enabled', $local );
	}
}

if ( ! function_exists( 'topersia_ads_url' ) ) {
	/** نشانی پروندۀ افزونه. */
	function topersia_ads_url( $rel = '' ) {
		return plugins_url( ltrim( (string) $rel, '/' ), TOPERSIA_ADS_FILE );
	}
}

require_once TOPERSIA_ADS_DIR . '/includes/tp-ads-logic.php';

if ( topersia_ads_enabled() ) {
	foreach ( array( 'data', 'stats', 'render', 'track', 'admin', 'pages' ) as $tp_ads_part ) {
		require_once TOPERSIA_ADS_DIR . '/includes/tp-ads-' . $tp_ads_part . '.php';
	}
	unset( $tp_ads_part );
}

add_action( 'init', function () {
	load_plugin_textdomain( 'topersia-ads', false, dirname( plugin_basename( TOPERSIA_ADS_FILE ) ) . '/languages' );
}, 1 );

/** نصب: جدول (مهاجرت up)، توان مدیر، مسیر /ad-go/. فقط وقتی افزونه روشن است. */
register_activation_hook( __FILE__, function () {
	if ( ! topersia_ads_enabled() ) {
		return;
	}
	topersia_ads_migrate_up();
	topersia_ads_grant_caps();
	topersia_ads_register_type();
	topersia_ads_rewrite();
	flush_rewrite_rules( false );
} );

register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'topersia_ads_daily' );
	flush_rewrite_rules( false );
} );

add_action( 'admin_notices', function () {
	if ( ! topersia_ads_enabled() && current_user_can( 'manage_options' ) ) {
		echo '<div class="notice notice-info"><p>' . esc_html__( 'ToPersia Ads فقط روی نسخۀ محلی روشن است تا ممیزی پیش از انتشار انجام شود.', 'topersia-ads' ) . '</p></div>';
	}
} );
