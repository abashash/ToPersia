<?php
/**
 * هوش مصنوعی — دروازۀ تولید و سقف روزانه.  ۴٫۳٫۰ بند ۴ · P5-D235
 *
 * مهمان و ربات: فقط خروجی ذخیره‌شده، صفر فراخوانی. دو جا تضمینش می‌کنند:
 *   بلوک صفحه      `topersia_g_ai_outputs()` فقط کش خلاصه‌ساز را می‌خواند؛
 *                  «تولید آنی» ۴٫۲٫۱ (`internal_text` در رندر) برداشته شد
 *   نقطۀ تولید     خودِ خلاصه‌ساز `wp_ajax_nopriv_tpas_ai_generate` ندارد
 *
 * ۱۳ اکتبر · P5-D358: تولید برای غیرمدیر بسته؛ سقف کاربر بی‌اثر («برای نسخه‌های بعد»). متن زیر تاریخچۀ دورۀ پیشین است.
 *
 * واردشده: تولید سمت سرور از نقطۀ خودِ خلاصه‌ساز (`tpas_ai_generate`،
 * نانس `tpas_ai`)، که کش مشترکِ (پست، نوع خروجی، زبان) دارد — یک‌بار
 * ساخته می‌شود و برای همه می‌ماند. این فایل پیش از آن، با اولویت ۱، دو
 * سقف روزانه را می‌سنجد: هر کاربر (پیش‌فرض ۱۰) و کل سایت؛ هر دو در
 * تنظیمات «هستۀ نمایش». خروجیِ ازپیش‌ذخیره‌شده از سقف کم نمی‌کند.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'TP_PLUGIN_DIR' ) ) {
	return;
}

/**
 * دو سقف از تنظیمات هستۀ نمایش (زبانۀ «هوش مصنوعی»). ۴٫۴٫۳: «سرویس‌های خارجی»
 * هسته دیگر سطر ai.text ندارد — پیش از آن:
 * تنظیم خود هستۀ نمایش — منبع «انتقال».  P5-D249
 */
if ( ! function_exists( 'topersia_g_ai_caps' ) ) {
	function topersia_g_ai_caps() {

		$legacy = topersia_g_ai_caps_legacy();
		$layer  = null;

		if ( ! $layer ) {
			return $legacy;
		}

		return array(
			'user' => null !== $layer['limits']['user'] ? (int) $layer['limits']['user'] : $legacy['user'],
			'site' => null !== $layer['limits']['site'] ? (int) $layer['limits']['site'] : $legacy['site'],
		);
	}
}

/** دو سقف از تنظیمات هستۀ نمایش. */
if ( ! function_exists( 'topersia_g_ai_caps_legacy' ) ) {
	function topersia_g_ai_caps_legacy() {

		$get = function_exists( 'topersia_render_setting' ) ? 'topersia_render_setting' : null;

		return array(
			'user' => $get ? max( 0, (int) $get( 'ai_user_daily', 10 ) ) : 10,
			'site' => $get ? max( 0, (int) $get( 'ai_site_daily', 200 ) ) : 200,
		);
	}
}

/** روزِ جاری به منطقۀ زمانی سایت — سقف با نیمه‌شب محلی نو می‌شود. */
if ( ! function_exists( 'topersia_g_ai_day' ) ) {
	function topersia_g_ai_day() {
		return function_exists( 'wp_date' ) ? (string) wp_date( 'Y-m-d' ) : gmdate( 'Y-m-d' );
	}
}

/**
 * آیا سقف اجازه می‌دهد؟ خالص، برای آزمون.
 *
 * @return string خالی یعنی مجاز؛ وگرنه user یا site.
 */
if ( ! function_exists( 'topersia_g_ai_cap_check' ) ) {
	function topersia_g_ai_cap_check( $user_used, $site_used, $caps ) {

		if ( (int) $site_used >= (int) $caps['site'] ) {
			return 'site';
		}

		if ( (int) $user_used >= (int) $caps['user'] ) {
			return 'user';
		}

		return '';
	}
}

/** مصرف امروزِ یک کاربر. */
if ( ! function_exists( 'topersia_g_ai_user_used' ) ) {
	function topersia_g_ai_user_used( $user_id ) {
		$raw = get_user_meta( (int) $user_id, '_topersia_ai_daily', true );
		return ( is_array( $raw ) && isset( $raw['d'], $raw['n'] ) && topersia_g_ai_day() === $raw['d'] ) ? (int) $raw['n'] : 0;
	}
}

/** مصرف امروزِ کل سایت. */
if ( ! function_exists( 'topersia_g_ai_site_used' ) ) {
	function topersia_g_ai_site_used() {
		return (int) get_transient( 'tp-ai-site-' . topersia_g_ai_day() );
	}
}

/** یک واحد از هر دو سقف. */
if ( ! function_exists( 'topersia_g_ai_count' ) ) {
	function topersia_g_ai_count( $user_id ) {

		$day = topersia_g_ai_day();

		update_user_meta( (int) $user_id, '_topersia_ai_daily', array( 'd' => $day, 'n' => topersia_g_ai_user_used( $user_id ) + 1 ) );
		set_transient( 'tp-ai-site-' . $day, topersia_g_ai_site_used() + 1, 2 * DAY_IN_SECONDS );
	}
}

/**
 * دروازه — پیش از `TP_AI_Summary::handle_ajax()`.
 *
 * درخواستِ بی‌ورود یا با نانس نادرست را همان خلاصه‌ساز رد می‌کند؛ این‌جا
 * چیزی از سقف کم نمی‌شود. اگر همان خروجی در کش هست، رایگان است و
 * دست‌نخورده رد می‌شود.
 */
if ( ! function_exists( 'topersia_g_ai_gate' ) ) {
	function topersia_g_ai_gate() {

		if ( ! is_user_logged_in() || ! check_ajax_referer( 'tpas_ai', 'nonce', false ) || ! class_exists( 'TP_AI_Summary' ) ) {
			return;
		}

		/*
		 * تولید برای غیرمدیر بسته است — هیچ بازدیدکننده‌ای (مهمان یا واردشده) خلاصه نمی‌سازد؛ بلوک صفحه هم دیگر نانس و دکمۀ تولید ندارد.
		 * نقطۀ خودِ خلاصه‌ساز را مستقیم صدا بزنند هم همین‌جا پیش از هر شمارش و هر فراخوانی رد می‌شود. تولید فقط مدیر، از پیشخوان.  P5-D358
		 */
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'ساختن خلاصه فعلاً فقط برای مدیر است.', 'topersia' ) ), 403 );
		}

		$post = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;
		$type = isset( $_POST['type'] ) ? preg_replace( '/[^a-z_]/', '', strtolower( (string) wp_unslash( $_POST['type'] ) ) ) : '';
		$lang = isset( $_POST['lang'] ) ? preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) wp_unslash( $_POST['lang'] ) ) ) : '';

		if ( ! in_array( $type, array( 'summary', 'keypoints', 'plan' ), true ) ) {
			$type = 'summary';
		}

		/*
		 * پستِ منتشرنشده بافتِ هوش مصنوعی نمی‌شود — نه تولید، نه کش، نه
		 * شمارش سقف. کاربر واردشده می‌توانست شناسۀ یک پیش‌نویس را بفرستد و
		 * خلاصه‌اش را بگیرد.  P5-D247
		 */
		if ( $post && function_exists( 'topersia_g_published' ) && ! topersia_g_published( array( $post ) ) ) {
			wp_send_json_error( array( 'message' => __( 'این محتوا در دسترس نیست.', 'topersia' ) ), 404 );
		}

		if ( $post && '' !== $lang && method_exists( 'TP_AI_Summary', 'get_cached' ) && TP_AI_Summary::get_cached( $post, $type, $lang ) ) {
			return;
		}

		/* سقف هر کاربر بی‌اثر است («برای نسخه‌های بعد»)؛ فقط سقف کل سایت می‌سنجد.  P5-D358 */
		$user = get_current_user_id();
		$caps = topersia_g_ai_caps();
		$why  = topersia_g_ai_cap_check( 0, topersia_g_ai_site_used(), array( 'site' => $caps['site'], 'user' => PHP_INT_MAX ) );

		if ( '' !== $why ) {
			wp_send_json_error( array(
				'cap'     => $why,
				'message' => 'user' === $why
					? __( 'سقف روزانۀ شما برای تولید پر شده است؛ فردا دوباره امتحان کنید.', 'topersia' )
					: __( 'سقف روزانۀ تولید سایت پر شده است؛ فردا دوباره امتحان کنید.', 'topersia' ),
			), 429 );
		}

		topersia_g_ai_count( $user );
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_ajax_tpas_ai_generate', 'topersia_g_ai_gate', 1 );
}
