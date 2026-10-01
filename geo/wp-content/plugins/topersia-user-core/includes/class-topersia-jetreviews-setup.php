<?php
/**
 * راه‌اندازی یک‌کلیکی JetReviews روی نوع‌پست‌های ToPersia.  P5-D234
 *
 * این کلاس هیچ کد JetReviews را کپی/بازنویسی نمی‌کند. دو جا را با API
 * خودِ JetReviews پر می‌کند:
 *
 *   `jet-reviews-settings`   کدام نوع‌پست نظر می‌گیرد — همان تیک‌های
 *                            صفحۀ Post Types افزونه
 *   جدول review types        یک ردیف به ازای هر نوع‌پستِ دارای نظر، با
 *                            معیارهای خانوادۀ خودش
 *
 * ── چرا «هر خانواده یک نوع» به «هر نوع‌پست یک ردیف» رسید ─────────────
 * در JetReviews 3.1 هر ردیف review type دقیقاً به یک جفت
 * (source, source_type) بسته است و افزونه نوعِ نظرِ یک پست را با
 * `get_review_type_slug_by_source_type( 'post', $post_type )` پیدا
 * می‌کند. یک ردیف نمی‌تواند چند نوع‌پست را بپوشاند. پس معیارها یک‌جا،
 * در `families()`، به ازای خانواده تعریف می‌شوند و هر نوع‌پستِ آن
 * خانواده ردیفی با همان معیارها می‌گیرد. دو منبع حقیقت نیست.
 *
 * نوع‌های بی‌نظر — کشور، منطقه، شهرستان، صنایع دستی، خوراک و نوع‌های
 * اطلاعاتی — در تنظیمات JetReviews خاموش می‌شوند.
 *
 * `structuredata` خاموش است: این نوع‌ها مشمول ستارۀ گوگل نیستند و
 * AggregateRating نباید در اسکیما بیاید.
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_JetReviews_Setup {

	const OPTION_KEY   = 'jet-reviews-settings';
	const NONCE_ACTION = 'topersia_setup_jetreviews';

	/** بیشینۀ هر معیار — ستارۀ یک تا پنج. */
	const MAX = 5;

	public static function instance(): self {
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct() {
		add_action( 'admin_post_topersia_setup_jetreviews', array( $this, 'handle_submit' ) );
	}

	/**
	 * خانواده‌های دارای نظر و معیارهای هر کدام.  P5-D234
	 *
	 * «موزه و هم‌خانواده‌ها»: از نوع‌های دستۀ A آن‌که میراثی است و در
	 * خانوادۀ طبیعت نیست — منظر فرهنگی (A13) — کنار موزه نشست.
	 *
	 * @return array<string,array{label:string,types:array<int,string>,criteria:array<int,string>}>
	 */
	public static function families(): array {
		return array(
			'destination' => array(
				'label'    => 'مقصد',
				'types'    => array( 'tp_province', 'tp_city', 'tp_village', 'tp_island', 'tp_district' ),
				'criteria' => array( 'جاذبه‌ها', 'دسترسی', 'اقامت و خدمات', 'مهمان‌نوازی' ),
			),
			'nature'      => array(
				'label'    => 'طبیعت',
				'types'    => array( 'tp_mountain', 'tp_desert', 'tp_water_body', 'tp_forest_plain', 'tp_protected_area', 'tp_natural_feature' ),
				'criteria' => array( 'چشم‌انداز', 'دسترسی', 'ایمنی', 'امکانات' ),
			),
			'heritage'    => array(
				'label'    => 'میراث',
				'types'    => array( 'tp_monument', 'tp_religious_site', 'tp_ancient_site', 'tp_museum', 'tp_landscape' ),
				'criteria' => array( 'ارزش دیدن', 'نگهداری', 'دسترسی', 'خدمات بازدید' ),
			),
			'event'       => array(
				'label'    => 'رویداد',
				'types'    => array( 'tp_festival', 'tp_seasonal_event', 'tp_religious_event', 'tp_sports_event' ),
				'criteria' => array( 'برگزاری', 'فضا', 'ارزش حضور' ),
			),
			'border'      => array(
				'label'    => 'گذرگاه مرزی',
				'types'    => array( 'tp_border_crossing' ),
				'criteria' => array( 'سرعت تردد', 'امکانات', 'برخورد کارکنان' ),
			),
		);
	}

	/** خانوادۀ یک نوع‌پست، یا رشتۀ خالی اگر نظر نمی‌گیرد. */
	public static function family_of( string $post_type ): string {
		foreach ( self::families() as $key => $family ) {
			if ( in_array( $post_type, $family['types'], true ) ) {
				return $key;
			}
		}
		return '';
	}

	/** معیارهای یک نوع‌پست — خالی یعنی بی‌نظر. */
	public static function criteria( string $post_type ): array {
		$family = self::family_of( $post_type );
		return '' === $family ? array() : self::families()[ $family ]['criteria'];
	}

	/**
	 * نامک ردیف review type — همان قراردادی که مبدل خودِ JetReviews
	 * (`maybe_convert_settings`) می‌سازد: `{post_type}-review-type`.
	 */
	public static function type_slug( string $post_type ): string {
		return $post_type . '-review-type';
	}

	/**
	 * نوع‌پست‌های اختصاصی ToPersia که با JetEngine ساخته شده‌اند.
	 *
	 * @return array<string,string> slug => برچسب فارسی
	 */
	public static function post_types(): array {
		return array(
			'tp_country'          => 'G1 — کشور',
			'tp_province'         => 'G3 — استان',
			'tp_county'           => 'G4 — شهرستان',
			'tp_city'             => 'G5 — شهر',
			'tp_district'         => 'G6 — محله',
			'tp_region'           => 'G2 — منطقه جغرافیایی',
			'tp_village'          => 'G7 — روستا',
			'tp_island'           => 'G8 — جزیره',
			'tp_mountain'         => 'G9 — کوه',
			'tp_water_body'       => 'G10 — پهنه آبی',
			'tp_desert'           => 'G11 — کویر',
			'tp_forest_plain'     => 'G12 — جنگل/دشت',
			'tp_border_crossing'  => 'G13 — گذرگاه مرزی',
			'tp_monument'         => 'A2 — بنای تاریخی',
			'tp_religious_site'   => 'A3 — مکان مذهبی',
			'tp_museum'           => 'A7 — موزه',
			'tp_protected_area'   => 'A9 — پارک ملی',
			'tp_natural_feature'  => 'A10 — عارضه طبیعی',
			'tp_ancient_site'     => 'A12 — محوطه باستانی',
			'tp_landscape'        => 'A13 — منظر فرهنگی',
			'tp_handicraft'       => 'C1 — صنایع دستی',
			'tp_local_food'       => 'C2 — غذای محلی',
			'tp_costume'          => 'C5 — لباس سنتی',
			'tp_music'            => 'C6 — موسیقی محلی',
			'tp_dance'            => 'C7 — رقص محلی',
			'tp_language'         => 'C8 — زبان / گویش',
			'tp_ethnic_group'     => 'C9 — قوم / فرهنگ',
			'tp_ritual'           => 'C10 — آیین / مراسم سنتی',
			'tp_figure'           => 'C12 — شخصیت تاریخی',
			'tp_historical_era'   => 'C13 — دوره / سلسلۀ تاریخی',
			'tp_designation'      => 'D — تعیین رسمی',
			'tp_visa'             => 'P1 — ویزا و شرایط ورود',
			'tp_currency'         => 'P2 — ارز و روش‌های پرداخت',
			'tp_health_safety'    => 'P4 — سلامت و ایمنی',
			'tp_travel_etiquette' => 'P5 — فرهنگ سفر و آداب',
			'tp_calendar'         => 'P6 — تقویم و مناسبت‌های رسمی',
			'tp_transport'        => 'P7 — حمل‌ونقل عمومی',
			'tp_route'            => 'P9 — مسیرهای گردشگری',
			'tp_festival'         => 'E1 — جشنواره و رویداد',
			'tp_sports_event'     => 'E3 — رویداد ورزشی',
			'tp_religious_event'  => 'E5 — مراسم مذهبی عمومی',
			'tp_seasonal_event'   => 'E6 — رویداد طبیعی و فصلی',
		);
	}

	/**
	 * وضعیت فعلی، برای نمایش در صفحۀ تنظیمات — بدون تغییر چیزی.
	 *
	 * @return array{plugin_active:bool,total:int,configured:int}
	 */
	public static function status(): array {
		$settings = get_option( self::OPTION_KEY, array() );
		$allowed  = is_array( $settings ) && isset( $settings['allowed-post-types'] ) && is_array( $settings['allowed-post-types'] )
			? $settings['allowed-post-types']
			: array();

		$total      = 0;
		$configured = 0;

		foreach ( array_keys( self::post_types() ) as $slug ) {

			if ( '' === self::family_of( $slug ) ) {
				continue;
			}

			++$total;

			if ( isset( $allowed[ $slug ] ) && filter_var( $allowed[ $slug ], FILTER_VALIDATE_BOOLEAN ) ) {
				++$configured;
			}
		}

		return array(
			'plugin_active' => function_exists( 'jet_reviews' ),
			'total'         => $total,
			'configured'    => $configured,
		);
	}

	/** کلاس داده‌ای JetReviews — یا null اگر افزونه نیست. */
	private static function data() {
		return class_exists( '\Jet_Reviews\Reviews\Data' ) ? \Jet_Reviews\Reviews\Data::get_instance() : null;
	}

	/**
	 * تنظیمات یک ردیف review type — همان پیش‌فرض‌های خودِ JetReviews
	 * (`Types::get_review_type_settings`) با چهار تغییر: تأیید مدیر،
	 * نقش مشترک هم مجاز، بی‌فرادادۀ پست، بی‌دادۀ ساختاریافته.
	 */
	public static function type_settings( string $post_type, bool $need_approve ): array {
		return array(
			'source'                => 'post',
			'source_type'           => $post_type,
			'fields'                => array(),
			'allowed_roles'         => array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ),
			'verifications'         => array(),
			'comment_verifications' => array(),
			'need_approve'          => $need_approve,
			'comments_allowed'      => true,
			'comments_need_approve' => $need_approve,
			'approval_allowed'      => true,
			'upload_media'          => false,
			'allowed_media'         => array( 'image/jpeg', 'image/png', 'image/gif' ),
			'maxsize_media'         => 5,
			'metadata'              => false,
			'metadata_rating_key'   => '_jet_reviews_average_rating',
			'metadata_ratio_bound'  => self::MAX,
			'structuredata'         => false,
			'structuredata_type'    => 'Product',
		);
	}

	/** معیارهای یک نوع‌پست به شکل `fields` جدول JetReviews. */
	public static function type_fields( string $post_type ): array {
		$out = array();
		foreach ( self::criteria( $post_type ) as $label ) {
			$out[] = array( 'label' => $label, 'step' => 1, 'max' => self::MAX );
		}
		return $out;
	}

	/**
	 * اجرای واقعی.
	 *
	 * @return int تعداد نوع‌پستی که نظر گرفت.
	 */
	public static function run( bool $need_approve = true ): int {

		if ( ! current_user_can( 'manage_options' ) ) {
			return 0;
		}

		$settings = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$allowed = isset( $settings['allowed-post-types'] ) && is_array( $settings['allowed-post-types'] )
			? $settings['allowed-post-types']
			: array( 'post' => 'true' );

		$data    = self::data();
		$updated = 0;

		foreach ( self::post_types() as $slug => $label ) {

			if ( ! post_type_exists( $slug ) ) {
				continue; // JetEngine هنوز این نوع‌پست را ثبت نکرده — بی‌سروصدا رد شو.
			}

			$type_key = $slug . '-type-settings';
			$existing = isset( $settings[ $type_key ] ) && is_array( $settings[ $type_key ] ) ? $settings[ $type_key ] : array();
			$family   = self::family_of( $slug );

			if ( '' === $family ) {

				$allowed[ $slug ] = 'false';
				$settings[ $type_key ] = array_merge( $existing, array( 'allowed' => false ) );

				/* ردیفی که خودِ ما با همین نامک ساخته بودیم برداشته می‌شود؛
				   به ردیف‌های دیگر JetReviews دست نمی‌زنیم. */
				if ( $data && $data->is_review_type_exist( self::type_slug( $slug ) ) ) {
					$data->delete_review_type_by_slug( self::type_slug( $slug ) );
				}

				continue;
			}

			$allowed[ $slug ] = 'true';

			$pt = get_post_type_object( $slug );

			$settings[ $type_key ] = array_merge(
				$existing,
				array(
					'allowed'               => true,
					'name'                  => $pt && $pt->labels ? $pt->labels->name : $label,
					'slug'                  => $slug,
					'review_type'           => self::type_slug( $slug ),
					'need_approve'          => $need_approve,
					'comments_allowed'      => true,
					'comments_need_approve' => $need_approve,
					'approval_allowed'      => true,
					'structuredata'         => false,
					'metadata'              => false,
				)
			);

			if ( $data ) {

				$row = array(
					'name'        => self::families()[ $family ]['label'] . ' — ' . $label,
					'source'      => 'post',
					'source_type' => $slug,
					'fields'      => maybe_serialize( self::type_fields( $slug ) ),
					'settings'    => maybe_serialize( self::type_settings( $slug, $need_approve ) ),
				);

				if ( $data->is_review_type_exist( self::type_slug( $slug ) ) ) {
					$data->update_review_type( array_merge( $row, array( 'slug' => self::type_slug( $slug ) ) ) );
				} else {
					$data->add_new_review_type( array_merge( $row, array( 'slug' => self::type_slug( $slug ), 'description' => '', 'meta_data' => '' ) ) );
				}
			}

			++$updated;
		}

		$settings['allowed-post-types'] = $allowed;

		update_option( self::OPTION_KEY, $settings );

		return $updated;
	}

	/**
	 * مدیریت‌کنندۀ فرم — نانس، سطح دسترسی، سپس اجرا و بازگشت با پیام.
	 *
	 * تأیید مدیر پیش از انتشار الزامی است (P5-D234)؛ کادر فرم فقط برای
	 * سازگاری مانده و خاموش‌کردنش اثری ندارد.
	 */
	public function handle_submit(): void {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی مجاز نیست.', 'topersia-user-core' ) );
		}

		check_admin_referer( self::NONCE_ACTION );

		$count = self::run( true );

		$redirect = add_query_arg(
			array(
				'page'                => 'topersia-user-core',
				'tp_jetreviews_setup' => '1',
				'tp_jetreviews_count' => $count,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}
