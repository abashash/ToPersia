<?php
/**
 * Plugin Name: ToPersia User Core
 * Plugin URI: https://topersia.com/
 * Description: هسته اختصاصی حساب کاربری ToPersia با پنل تب‌محور، ماژول‌های مسافر و ساختار آماده توسعه.
 * Version: 1.14.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: ToPersia
 * Text Domain: topersia-user-core
 * Domain Path: /languages
 */
defined('ABSPATH') || exit;

if (defined('TOPERSIA_USER_CORE_LOADED')) {
    return;
}
define('TOPERSIA_USER_CORE_LOADED', true);
define('TOPERSIA_USER_CORE_VERSION', '1.14.0');
define('TOPERSIA_USER_CORE_FILE', __FILE__);
define('TOPERSIA_USER_CORE_DIR', plugin_dir_path(__FILE__));
define('TOPERSIA_USER_CORE_URL', plugin_dir_url(__FILE__));

$tp_files = [
    'class-topersia-db.php',
    'class-topersia-privacy.php',
    'class-topersia-data-requests.php',
    'class-topersia-inactivity.php',
    'class-topersia-avatar.php',
    'class-topersia-sms-provider.php',
    'class-topersia-mediana-sms.php',
    'class-topersia-auth.php',
    'class-topersia-mailer.php',      /* ۱٫۱۴٫۰ · P5-D391 */
    'class-topersia-email-code.php',
    'class-topersia-profile.php',
    'class-topersia-admin.php',
    'class-topersia-jetengine.php',
    'class-topersia-icons.php',
    'class-topersia-display-prefs.php',
    'class-topersia-reviews.php',
    'class-topersia-jetreviews-setup.php',
    'class-topersia-follow.php',
    'class-topersia-review-form.php',
    'class-topersia-dashboard.php',
    'class-topersia-assets.php',
    'class-topersia-i18n.php',
];
foreach ($tp_files as $tp_file) {
    $tp_path = TOPERSIA_USER_CORE_DIR . 'includes/' . $tp_file;
    if (is_readable($tp_path)) {
        require_once $tp_path;
    }
}

register_activation_hook(__FILE__, static function (): void {
    if (class_exists('ToPersia_DB')) {
        ToPersia_DB::activate();
    }
});

/**
 * قلاب غیرفعال‌سازی — تا نسخۀ ۱٫۹٫۱۱ وجود نداشت. نتیجه‌اش این بود که
 * رویداد زمان‌بندی‌شدۀ `topersia_user_core_prune` روی فعال‌سازی ثبت
 * می‌شد ولی هرگز پاک نمی‌شد؛ پس وردپرس تا ابد در هر چرخۀ کرون سراغ یک
 * قلاب مرده می‌رفت. جدول‌ها و داده‌ها عمداً دست‌نخورده می‌مانند، چون
 * غیرفعال‌سازی موقتی است — پاک‌سازی کار uninstall.php است.
 */
register_deactivation_hook(__FILE__, static function (): void {
    if (class_exists('ToPersia_DB')) {
        ToPersia_DB::deactivate();
    }
});

add_action('topersia_user_core_prune', static function (): void {
    if (class_exists('ToPersia_DB')) {
        ToPersia_DB::prune();
    }
});

add_action('plugins_loaded', static function (): void {
    if (class_exists('ToPersia_DB')) {
        ToPersia_DB::maybe_upgrade();
        ToPersia_DB::maybe_hash_otp_rows();
        ToPersia_DB::ensure_cron();
    }

    load_plugin_textdomain(
        'topersia-user-core',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );

    foreach ([
        'ToPersia_Privacy',
        'ToPersia_Data_Requests',
        'ToPersia_Inactivity',
        'ToPersia_Avatar',
        'ToPersia_Auth',
        'ToPersia_Mailer',
        'ToPersia_Email_Code',
        'ToPersia_Profile',
        'ToPersia_Admin',
        'ToPersia_JetEngine',
        'ToPersia_JetReviews_Setup',
        'ToPersia_Follow',
        'ToPersia_Review_Form',
        'ToPersia_Display_Prefs',
        'ToPersia_Dashboard',
        'ToPersia_I18n',
        'ToPersia_Assets',
    ] as $tp_class) {
        if (class_exists($tp_class) && method_exists($tp_class, 'instance')) {
            $tp_class::instance();
        }
    }
});

/*
 * ۱٫۱۰٫۴ — نشانی‌های رایج پنل کاربر (`/user-account/` · `/my-account/`، با پیشوند زبان
 * هم) به برگۀ حساب در زبان جاری، به‌جای ۴۰۴. فقط وقتی وردپرس خودش آن نشانی را
 * نشناخت؛ برگه‌ای با همین نامک اگر روزی ساخته شود، برنده است.
 */
add_action( 'template_redirect', function () {
	if ( ! is_404() || ! class_exists( 'ToPersia_Admin' ) || ! method_exists( 'ToPersia_Admin', 'account_url' ) ) {
		return;
	}
	$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( in_array( strtolower( basename( trim( $path, '/' ) ) ), array( 'user-account', 'my-account' ), true ) ) {
		$url = ToPersia_Admin::account_url();
		if ( '' !== $url ) {
			wp_safe_redirect( $url, 301 );
			exit;
		}
	}
}, 1 );
