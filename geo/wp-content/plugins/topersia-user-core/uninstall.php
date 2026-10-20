<?php
/**
 * پاک‌سازی هنگام حذف کامل افزونه.
 *
 * تفاوت مهم با غیرفعال‌سازی:
 *
 *   غیرفعال‌سازی (register_deactivation_hook در فایل اصلی) → فقط کرون
 *   پاک می‌شود. داده‌ها دست‌نخورده می‌مانند، چون غیرفعال‌سازی معمولاً
 *   موقتی است (رفع اشکال، بروزرسانی دستی، جابه‌جایی).
 *
 *   حذف (همین فایل) → کاربر صریحاً گفته «Delete». اینجا داده پاک می‌شود.
 *
 * چرا این فایل لازم بود: پیش از ۱٫۹٫۱۲ افزونه هیچ مسیر پاک‌سازی نداشت.
 * حذف افزونه دو جدول، شش گزینه و هشت متای کاربر را برای همیشه در
 * پایگاه‌داده جا می‌گذاشت — روی سایتی با هدف ۲۱٬۶۰۰ صفحه و چند سال
 * عمر، این یعنی آشغال دائمی در جدول‌های usermeta و options که در هر
 * پشتیبان‌گیری و هر مهاجرت حمل می‌شود.
 *
 * تصمیم آگاهانه: جدول `{prefix}jet_reviews` و گزینۀ `jet-reviews-settings`
 * دست نمی‌خورند. آن‌ها دارایی افزونۀ JetReviews هستند، نه ما. افزونه‌ای
 * که داده‌های افزونۀ دیگری را در uninstall پاک کند، یک بمب ساعتی است.
 *
 * @package ToPersia_User_Core
 */

// این فایل را فقط وردپرس در جریان حذف اجرا می‌کند.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/* ── ۱) جدول‌های اختصاصی ──────────────────────────────────────────
   نام جدول با $wpdb->prefix ساخته می‌شود، نه 'wp_' سخت‌کد. پیشوند
   واقعی این سایت `jwWSU9v_` است. */
$tp_tables = array(
	$wpdb->prefix . 'topersia_otp_attempts',
	$wpdb->prefix . 'topersia_saved',
);

foreach ( $tp_tables as $tp_table ) {
	// نام جدول را نمی‌توان با prepare جایگذاری کرد؛ از esc_sql استفاده
	// می‌شود و مقدار از $wpdb->prefix می‌آید، نه از ورودی کاربر.
	$wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $tp_table ) . '`' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/* ── ۲) گزینه‌ها ─────────────────────────────────────────────────
   کلید مدیانا هم اینجا پاک می‌شود؛ نگه‌داشتن یک کلید API پس از حذف
   افزونه، ریسک امنیتی است. */
$tp_options = array(
	'topersia_mediana_api_key',
	'topersia_mediana_pattern_code',
	'topersia_account_page_url',
	'topersia_user_tabs',
	'topersia_user_core_db_version',
	'topersia_user_core_strings_fingerprint',
	'topersia_user_core_pii_migration',   /* ۱٫۱۱٫۰ */
	'topersia_user_core_pii_cursor',
	'topersia_user_core_otp_hashed',   /* ۱٫۱۲٫۰ */
	'topersia_uc_inactivity_since',    /* ۱٫۱۳٫۰ */
);

foreach ( $tp_options as $tp_option ) {
	delete_option( $tp_option );

	// روی نصب چندسایتی، گزینه ممکن است در سطح شبکه هم نشسته باشد.
	if ( is_multisite() ) {
		delete_site_option( $tp_option );
	}
}

/* ── ۳) متاهای کاربر ────────────────────────────────────────────
   delete_metadata با آرگومان آخر true یعنی «برای همۀ کاربران» — یک
   کوئری به‌جای پیمایش کل کاربران. روی سایتی با ده‌ها هزار کاربر،
   تفاوت میان چند میلی‌ثانیه و یک تایم‌اوت است.

   `first_name` و `last_name` عمداً نیستند: متای هستۀ وردپرس‌اند و
   مالکیتشان با ما نیست. */
$tp_user_meta = array(
	'_topersia_mobile',
	'_topersia_country',
	'_topersia_language',
	'_topersia_travel_style',
	'_topersia_display_prefs',
	'_topersia_trips_count',
	'_topersia_saved_entities',
	'_topersia_saved_count',
	'_topersia_saved_wpml_normalized',
	'_topersia_followed',
	'_topersia_avatar',      /* ۱٫۱۳٫۰ */
	'_topersia_last_login',
	'_topersia_inactive',
	'_topersia_email_verified',   /* ۱٫۱۴٫۰ */
	'_topersia_mobile_verified',
);

foreach ( $tp_user_meta as $tp_meta_key ) {
	delete_metadata( 'user', 0, $tp_meta_key, '', true );
}

/* ── ۴) ترنزینت‌های محدودسازی نرخ ─────────────────────────────────
   کلید این ترنزینت‌ها هش است، پس تک‌تک قابل حدس زدن نیستند و باید با
   LIKE پیدا شوند. اگر کش ماندگار بیرونی (Redis/Memcached) فعال باشد،
   ترنزینت‌ها اصلاً در options نمی‌نشینند و این کوئری چیزی پیدا
   نمی‌کند — که اشکالی ندارد: آن‌ها خودشان حداکثر یک ساعت بعد
   منقضی می‌شوند. */
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options}
		 WHERE option_name LIKE %s
		    OR option_name LIKE %s
		    OR option_name LIKE %s
		    OR option_name LIKE %s
		    OR option_name LIKE %s
		    OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_tp_otp_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_tp_otp_' ) . '%',
		$wpdb->esc_like( '_transient_tp_otp_rl_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_tp_otp_rl_' ) . '%',
		$wpdb->esc_like( '_transient_tp_otp_ip_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_tp_otp_ip_' ) . '%'
	)
);

delete_transient( 'tp_otp_global' );

/* ۱٫۱۴٫۰ — کانال ایمیل (tp_ec_*) و سقف ارسال سایت (tp_mail_cap_*) */
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_tp_ec_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_tp_ec_' ) . '%',
		$wpdb->esc_like( '_transient_tp_mail_cap_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_tp_mail_cap_' ) . '%'
	)
);

/* ── ۵) کرون ────────────────────────────────────────────────────
   غیرفعال‌سازی این کار را انجام می‌دهد، ولی حذفِ یک افزونۀ از پیش
   غیرفعال‌شده هم ممکن است، و در آن مسیر قلاب غیرفعال‌سازی اجرا
   نمی‌شود. پس دوباره پاک می‌شود — این عمل idempotent است. */
wp_clear_scheduled_hook( 'topersia_user_core_prune' );
wp_clear_scheduled_hook( 'topersia_user_core_inactivity' );

/* ── ۵ب) فایل‌های آواتار (۱٫۱۳٫۰) ── پوشۀ ما در uploads، با نام تصادفی. */
$tp_up  = wp_upload_dir( null, false );
$tp_dir = trailingslashit( (string) $tp_up['basedir'] ) . 'topersia-avatars/';

if ( is_dir( $tp_dir ) ) {
	foreach ( (array) glob( $tp_dir . '*' ) as $tp_f ) {
		if ( is_file( $tp_f ) ) {
			wp_delete_file( $tp_f );
		}
	}
	@rmdir( $tp_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
}

/* ── ۶) کش ──────────────────────────────────────────────────────
   وگرنه تا پایان درخواست، گزینه‌های پاک‌شده از کش خوانده می‌شوند. */
wp_cache_flush();
