<?php
/**
 * ثبت نظر از صفحۀ موجودیت.  P5-D234
 *
 * فرمِ بخش «نظر بازدیدکنندگان» را «هستۀ نمایش» می‌سازد؛ نوشتن کارِ این
 * افزونه است. دادۀ نظر در جدول‌های خودِ JetReviews می‌نشیند و با API
 * دادۀ خودش (`Reviews\Data::add_new_review`) نوشته می‌شود، نه SQL.
 *
 * چرا نقطۀ REST خودِ JetReviews نه: `submit-review` شناسۀ نویسنده را از
 * درخواست می‌خواند (`author_id`)، پس هر کاربر واردشده می‌توانست به نام
 * دیگری نظر بنویسد. این‌جا نویسنده همیشه کاربر جاری است.
 *
 * قاعده‌ها
 *   فقط کاربر واردشده، با نانس `topersia_user_core`
 *   فقط نوع‌های پنج خانوادۀ دارای نظر؛ معیارها از همان خانواده
 *   نظر به شناسۀ زبان پیش‌فرض WPML بسته می‌شود — امتیاز یک مکان در
 *                   همۀ زبان‌ها یکی است
 *   همیشه در انتظار تأیید (`approved = 0`)؛ مدیر در JetReviews تأیید می‌کند
 *   یک نظر برای هر کاربر و هر مکان
 *   بی‌اعلان ایمیلی: `mail()` روی این هاست غیرفعال است
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Review_Form {

	private static ?self $instance = null;

	/** مرز متن نظر به نویسه. */
	public const MIN_TEXT = 10;
	public const MAX_TEXT = 3000;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'wp_ajax_topersia_submit_review', array( $this, 'ajax_submit' ) );
	}

	/** معیارهای یک نوع‌پست — خالی یعنی بی‌نظر. */
	public static function criteria( string $post_type ): array {
		return class_exists( 'ToPersia_JetReviews_Setup' ) ? ToPersia_JetReviews_Setup::criteria( $post_type ) : array();
	}

	/** آیا جدول‌های JetReviews هستند — بی‌حساسیت به حروف. */
	public static function available(): bool {
		return class_exists( 'ToPersia_Reviews' ) && ToPersia_Reviews::available() && class_exists( '\Jet_Reviews\Reviews\Data' );
	}

	/** شناسۀ زبان پیش‌فرض. */
	public static function canonical( int $post_id, string $type ): int {
		return class_exists( 'ToPersia_JetEngine' ) ? ToPersia_JetEngine::canonical_id( $post_id, $type ) : $post_id;
	}

	/** آیا این کاربر برای این مکان (به شناسۀ زبان پیش‌فرض) نظری دارد. */
	public static function user_has_review( int $user_id, int $canonical ): bool {

		if ( $user_id <= 0 || $canonical <= 0 || ! class_exists( 'ToPersia_Reviews' ) || ! ToPersia_Reviews::available() ) {
			return false;
		}

		global $wpdb;

		$table = $wpdb->prefix . 'jet_reviews';

		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE author = %s AND post_id = %d LIMIT 1",
			(string) $user_id,
			$canonical
		) );
	}

	/**
	 * امتیازِ JetReviews — میانگین درصدِ معیارها.
	 *
	 * همان فرمول `Submit_Review::calculate_rating()` خودِ افزونه، تا
	 * نظرِ این مسیر و نظرِ مسیر JetReviews یک مقیاس داشته باشند.
	 *
	 * @param array<int,int> $values امتیاز هر معیار، یک تا پنج.
	 */
	public static function rating( array $values, int $max = 5 ): int {

		if ( ! $values || $max <= 0 ) {
			return 0;
		}

		$each = array();

		foreach ( $values as $v ) {
			$each[] = round( ( 100 * (int) $v ) / $max, 2 );
		}

		return (int) round( array_sum( $each ) / count( $each ) );
	}

	/**
	 * ورودی را می‌سنجد و ردیفِ آمادۀ JetReviews را می‌سازد.
	 *
	 * خالص است (بی‌پایگاه‌داده) تا آزمون‌پذیر باشد.
	 *
	 * @return array{ok:bool,error?:string,row?:array}
	 */
	public static function prepare( string $post_type, int $canonical, int $user_id, array $scores, string $title, string $content ): array {

		$criteria = self::criteria( $post_type );

		if ( ! $criteria ) {
			return array( 'ok' => false, 'error' => 'review_not_allowed' );
		}

		$values = array();

		foreach ( $criteria as $i => $label ) {

			$v = isset( $scores[ $i ] ) ? (int) $scores[ $i ] : 0;

			if ( $v < 1 || $v > 5 ) {
				return array( 'ok' => false, 'error' => 'review_invalid' );
			}

			$values[ $i ] = $v;
		}

		$content = trim( $content );
		$title   = trim( $title );

		/* شمار نویسه با preg، نه mb_strlen: روی هاست زنده mbstring نیست. */
		$len = (int) preg_match_all( '/./us', $content );

		if ( $len < self::MIN_TEXT || $len > self::MAX_TEXT ) {
			return array( 'ok' => false, 'error' => 'review_invalid' );
		}

		$rating_data = array();

		foreach ( $criteria as $i => $label ) {
			$rating_data[] = array(
				'field_label' => $label,
				'field_value' => $values[ $i ],
				'field_step'  => 1,
				'field_max'   => 5,
			);
		}

		return array(
			'ok'  => true,
			'row' => array(
				'source'      => 'post',
				'post_id'     => $canonical,
				'post_type'   => $post_type,
				'author'      => (string) $user_id,
				'date'        => function_exists( 'current_time' ) ? current_time( 'mysql' ) : gmdate( 'Y-m-d H:i:s' ),
				'title'       => $title,
				'content'     => $content,
				'type_slug'   => class_exists( 'ToPersia_JetReviews_Setup' ) ? ToPersia_JetReviews_Setup::type_slug( $post_type ) : $post_type . '-review-type',
				'rating_data' => serialize( $rating_data ),
				'rating'      => self::rating( $values ),
				'approved'    => 0,
			),
		);
	}

	public function ajax_submit(): void {

		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );

		if ( ! is_user_logged_in() || ! wp_verify_nonce( $nonce, 'topersia_user_core' ) ) {
			wp_send_json_error( array( 'message' => ToPersia_I18n::t( 'invalid_request', 'درخواست نامعتبر است.' ) ), 403 );
		}

		if ( ! self::available() ) {
			wp_send_json_error( array( 'message' => ToPersia_I18n::t( 'review_not_allowed' ) ), 503 );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$post    = get_post( $post_id );

		/* منتشرشده و بی‌گذرواژه — P5-D247 */
		if ( ! $post || 'publish' !== $post->post_status || '' !== (string) $post->post_password || ! self::criteria( $post->post_type ) ) {
			wp_send_json_error( array( 'message' => ToPersia_I18n::t( 'review_not_allowed' ) ), 400 );
		}

		$user_id   = get_current_user_id();
		$canonical = self::canonical( (int) $post->ID, $post->post_type );

		if ( self::user_has_review( $user_id, $canonical ) ) {
			wp_send_json_error( array( 'message' => ToPersia_I18n::t( 'review_exists' ) ), 409 );
		}

		$raw    = isset( $_POST['scores'] ) ? (array) wp_unslash( $_POST['scores'] ) : array();
		$scores = array_values( array_map( 'intval', $raw ) );
		$title  = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		$text   = sanitize_textarea_field( wp_unslash( $_POST['content'] ?? '' ) );

		$prep = self::prepare( $post->post_type, $canonical, $user_id, $scores, $title, $text );

		if ( ! $prep['ok'] ) {
			wp_send_json_error( array( 'message' => ToPersia_I18n::t( $prep['error'] ) ), 422 );
		}

		/* دومین آرگومان false: بی اعلان به ناظر — آن مسیر ایمیل می‌فرستد. */
		$insert = \Jet_Reviews\Reviews\Data::get_instance()->add_new_review( $prep['row'], false );

		if ( ! $insert ) {
			if ( function_exists( 'topersia_log' ) ) {
				topersia_log( 'ثبت نظر ناموفق', 'error', 'user', array( 'post' => $canonical ) );
			}
			wp_send_json_error( array( 'message' => ToPersia_I18n::t( 'network_error' ) ), 500 );
		}

		wp_send_json_success( array( 'message' => ToPersia_I18n::t( 'review_submitted' ) ) );
	}
}
