<?php
/**
 * پل خلاصه‌ساز به هستۀ ToPersia — کش مرکزی و هش محتوا (P5-D250).
 *
 * از ۱٫۲٫۱ موتور و کلید تولید متن فقط از تنظیمات خودِ خلاصه‌ساز است: «سرویس‌های
 * خارجی» هسته دیگر سطر ai.text ندارد (خواستۀ کاربر، ۲۹ سپتامبر ۲۰۲۶). آنچه از
 * هسته می‌ماند:
 *   — تولید از کش مرکزی می‌گذرد با انبارۀ همین جدول خلاصه‌ها (تکراری ساخته
 *     نمی‌شود) و هش محتوا؛ ویرایش پست خروجی را پاک نمی‌کند، کهنه علامت می‌زند.
 *   — هش محتوای هسته فیلدهای ورودیِ خلاصه‌ساز را هم می‌گیرد (نام‌ها، خلاصۀ
 *     کوتاه، متن مرور، …) — FIELDMAP ِ هستۀ نمایش آن‌ها را ندارد.
 *
 * هسته نیست: همان رفتار ۱٫۱٫۱.
 */

defined( 'ABSPATH' ) || exit;

class TP_AI_Core_Bridge {

	public static function init() {
		add_filter( 'topersia_content_hash_fields', array( __CLASS__, 'hash_fields' ), 20 );
	}

	/**
	 * فیلدهای هش محتوا: ورودی‌های خلاصه‌ساز به فهرست افزوده می‌شوند. تهی
	 * (null) یعنی «هر متای بی‌زیرخط» — همان می‌ماند، که این‌ها را هم دارد.
	 *
	 * @param array|null $keys فهرست تا این‌جا.
	 * @return array|null
	 */
	public static function hash_fields( $keys ) {
		return is_array( $keys ) ? array_values( array_unique( array_merge( $keys, TP_AI_Context::input_fields() ) ) ) : $keys;
	}

	/** کش مرکزی در دسترس است؟ */
	public static function cache_available() {
		return function_exists( 'topersia_cache_remember' ) && function_exists( 'topersia_content_hash' );
	}

	/** دما: عدد اعشاری ۰ تا ۲ از تنظیمات خلاصه‌ساز، بی تقسیم.  ۲٫۷ */
	public static function temperature() {
		return TP_AI_Options::sanitize_temperature( TP_AI_Options::get_field( 'internal_temperature', 0.3 ) );
	}

	/* ── کش مرکزی ─────────────────────────────────────────────────── */

	/** متای هشی که خروجی با آن ساخته شد. */
	public static function hash_key( $type, $lang ) {
		return '_tpas_hash_' . sanitize_key( $type ) . '_' . sanitize_key( $lang );
	}

	/**
	 * انبارۀ کش مرکزی روی جدول خلاصه‌ها — همان ردیف یکتای (پست، نوع، زبان).
	 *
	 * @return array{get:callable,put:callable}
	 */
	public static function store( $post_id, $type, $lang ) {

		return array(
			'get' => function () use ( $post_id, $type, $lang ) {
				$row = TP_AI_DB::get_summary( $post_id, $type, $lang );
				if ( ! $row || '' === trim( (string) $row->content ) ) {
					return null;
				}
				/* ردیفِ پیش از ۱٫۲٫۰ هش ندارد؛ آن نسخه‌ها با هر ویرایش پاک می‌شدند، پس
				   ردیفی که مانده با محتوای امروز ساخته شده — همان هش امروز */
				$hash = (string) get_post_meta( $post_id, self::hash_key( $type, $lang ), true );
				return array(
					'value'       => (string) $row->content,
					'meta'        => array( 'provider' => (string) $row->provider, 'model' => (string) $row->model ),
					'hash'        => '' !== $hash ? $hash : topersia_content_hash( $post_id ),
					'fresh_until' => 0,
					'created'     => strtotime( (string) $row->updated_at ),
				);
			},
			'put' => function ( $key, $envelope ) use ( $post_id, $type, $lang ) {
				$meta = isset( $envelope['meta'] ) ? (array) $envelope['meta'] : array();
				TP_AI_DB::save_summary( $post_id, $type, $lang, (string) $envelope['value'], isset( $meta['provider'] ) ? (string) $meta['provider'] : '', isset( $meta['model'] ) ? (string) $meta['model'] : '' );
				update_post_meta( $post_id, self::hash_key( $type, $lang ), (string) $envelope['hash'] );
			},
		);
	}

	/** آیا خروجی ذخیره‌شدۀ این پست کهنه است (هش محتوا عوض شده)؟ */
	public static function is_stale( $post_id, $type, $lang ) {

		if ( ! self::cache_available() || ! $post_id ) {
			return false;
		}

		$hash = (string) get_post_meta( $post_id, self::hash_key( $type, $lang ), true );

		return function_exists( 'topersia_cache_is_stale' ) ? topersia_cache_is_stale( $post_id, $hash ) : false;
	}
}
