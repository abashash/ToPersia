<?php
/**
 * پل هستۀ نمایش به هستۀ ToPersia — SPRINT-4.4 فاز ۲.
 *
 *   P5-D249  هوا، کاشی نقشه و سقف‌های هوش مصنوعی از «سرویس‌های خارجی» هسته
 *            خوانده می‌شوند، اگر سطرشان ذخیره شده؛ وگرنه همان پیش‌فرض و تنظیم
 *            خود هستۀ نمایش — تا «انتقال». تنظیم قدیمی پاک نمی‌شود.
 *   P5-D250  هش محتوای هسته متاهای FIELDMAP را می‌گیرد (نه هر متایی).
 *   P5-D265  پنجرۀ فهرست جدول قاعدۀ تاکردنِ هسته را می‌خواند.
 *
 * هسته نیست: هیچ‌کدام از این‌ها کاری نمی‌کند و رفتار ۴٫۴٫۰ می‌ماند.
 */

/**
 * سطر ذخیره‌شدۀ یک سرویس در لایۀ هسته، یا null اگر هسته نیست یا سطر
 * هنوز ذخیره نشده (در آن صورت تنظیم قدیمی خوانده می‌شود).
 *
 * @return array|null
 */
if ( ! function_exists( 'topersia_g_layer' ) ) {
	function topersia_g_layer( $service ) {

		if ( ! function_exists( 'topersia_api' ) ) {
			return null;
		}

		$cfg = topersia_api( $service );

		return ! empty( $cfg['configured'] ) ? $cfg : null;
	}
}

/** تاکردن نام برای پنجرۀ فهرست — تابع هسته، یا تهی اگر هسته نیست. */
if ( ! function_exists( 'topersia_g_fold' ) ) {
	function topersia_g_fold( $text ) {
		return function_exists( 'topersia_search_fold' ) ? (string) topersia_search_fold( (string) $text ) : '';
	}
}

if ( function_exists( 'add_filter' ) ) {

	/*
	 * هش محتوا: فیلدهای FIELDMAP، نه هر متایی (بازبینی، سئو و شمارنده هش را
	 * عوض نمی‌کنند).  P5-D250. به فهرست افزوده می‌شود، نه جایش: خلاصه‌ساز
	 * ورودی‌های خودش را در اولویت ۲۰ می‌افزاید.
	 */
	add_filter( 'topersia_content_hash_fields', function ( $keys ) {
		return function_exists( 'topersia_g_map' )
			? array_values( array_unique( array_merge( is_array( $keys ) ? $keys : array(), array_keys( (array) topersia_g_map() ) ) ) )
			: $keys;
	}, 10 );

	/*
	 * تنظیم قدیمی برای «انتقال»: ارائه‌دهندۀ هوا (پیش‌فرض MET Norway یا فیلتر
	 * topersia_g_wx_provider)، مسیر کاشی، و دو سقف روزانۀ هوش مصنوعی.
	 */
	add_filter( 'topersia_services_legacy', function ( $legacy ) {

		$legacy = (array) $legacy;

		if ( function_exists( 'topersia_g_wx_provider_legacy' ) ) {
			$legacy['weather'] = array_merge( isset( $legacy['weather'] ) ? (array) $legacy['weather'] : array(), array( 'provider' => topersia_g_wx_provider_legacy() ) );
		}

		$legacy['map.tiles'] = array_merge( isset( $legacy['map.tiles'] ) ? (array) $legacy['map.tiles'] : array(), array(
			'provider' => 'pmtiles',
			'extra'    => array( 'path' => 'tiles/iran.pmtiles' ),
		) );

		if ( false ) { /* ۴٫۴٫۳: ai.text در لایۀ هسته نیست — سقف‌ها در تنظیمات همین افزونه */
			$caps              = topersia_g_ai_caps_legacy();
			$legacy['ai.text'] = array_merge( isset( $legacy['ai.text'] ) ? (array) $legacy['ai.text'] : array(), array(
				'limit_user' => (string) $caps['user'],
				'limit_site' => (string) $caps['site'],
			) );
		}

		return $legacy;
	} );
}
