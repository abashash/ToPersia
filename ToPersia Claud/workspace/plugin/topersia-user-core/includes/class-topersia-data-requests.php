<?php
/**
 * حذف حساب و صدور داده — ابزار حریم خصوصی وردپرس + دکمۀ پنل.  ۱٫۱۲٫۰ · P5-D375
 *
 * چرا ابزار وردپرس بدون صف تازه کار می‌کند: حساب پیامکی ایمیل **ساختگی** دارد (`u…@users.topersia.com`،
 * P5-D354؛ نامۀ تأیید به آن نمی‌رسد)، ولی ابزار حریم خصوصی وردپرس فقط ایمیل را به کاربر نگاشت می‌کند
 * (`get_user_by( 'email' )`) و ایمیلِ ساختگی همین‌جا یکتاست. پس درخواست همان نوشتۀ `user_request` خودِ هسته است
 * (بی جدول، بی فیلد تازه) و مدیر آن را در «ابزارها ← خروج/پاک‌کردن داده‌های شخصی» می‌بیند و اجرا می‌کند:
 * «صدور» با دکمۀ دانلود (نه «ایمیل»)، «پاک‌کردن» با اجرای پاک‌کننده. درخواست فقط پس از تأیید دومرحلۀ خودِ کاربر
 * در پنل (توکن یک‌بارمصرف، ۱۰ دقیقه) ساخته می‌شود و در حالت «تأییدشده» می‌نشیند — نامۀ تأیید لازم نیست.
 *
 * صدورکننده و پاک‌کننده همۀ داده‌های user-core را می‌گیرند: نمایه، سبک سفر، ذخیره‌ها، دنبال‌شده‌ها، تنظیم نمایش،
 * ردیف‌های OTP (با HMAC شمارۀ خودِ کاربر)، نظرها و پاسخ‌ها. پاک‌کننده نظرها را **ناشناس** می‌کند: متن می‌ماند،
 * نویسنده به مهمانِ مشترکِ `tp-deleted` می‌رود (نامش به زبان صفحه «کاربر حذف‌شده»)، و user_id · ایمیل · IP · شماره پاک
 * می‌شود؛ سپس حساب (اگر دسترسی ویرایشی ندارد) حذف می‌شود.
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Data_Requests {

	private static ?self $instance = null;

	/** شناسۀ مهمانِ مشترکِ نویسندۀ ناشناس در جدول مهمان‌های JetReviews. */
	public const ANON = 'tp-deleted';

	/** «کاربر حذف‌شده» — نُه زبان (همان فهرست نُه زبانِ خلاصه‌ساز: fa en ar fr de es ru tr zh؛ ناشناخته ← انگلیسی). */
	private const DELETED = array(
		'fa' => 'کاربر حذف‌شده',
		'en' => 'Deleted user',
		'ar' => 'مستخدم محذوف',
		'tr' => 'Silinmiş kullanıcı',
		'ru' => 'Удалённый пользователь',
		'de' => 'Gelöschter Nutzer',
		'fr' => 'Utilisateur supprimé',
		'es' => 'Usuario eliminado',
		'zh' => '已删除用户',
	);

	/** متای کاربرِ user-core و نزدیک‌هایش که با حساب پاک می‌شوند. */
	private const META = array(
		'_topersia_mobile', '_topersia_country', '_topersia_language', '_topersia_travel_style', '_topersia_display_prefs',
		'_topersia_trips_count', '_topersia_saved_entities', '_topersia_saved_count', '_topersia_saved_wpml_normalized',
		'_topersia_followed', '_topersia_ai_daily', '_topersia_avatar', '_topersia_last_login', '_topersia_inactive',
		'_topersia_email_verified', '_topersia_mobile_verified',   /* ۱٫۱۴٫۰ */
	);

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
		add_filter( 'jet-reviews/user-manager/raw-user-data', array( $this, 'raw_user_data' ), 20 );
		add_action( 'wp_ajax_topersia_data_request', array( $this, 'ajax' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/* ── برچسب «کاربر حذف‌شده» ─────────────────────────────────────────── */

	public static function deleted_label( string $lang = '' ): string {

		$lang = strtolower( substr( '' !== $lang ? $lang : ( class_exists( 'ToPersia_Privacy' ) ? ToPersia_Privacy::lang() : 'en' ), 0, 2 ) );

		return self::DELETED[ $lang ] ?? self::DELETED['en'];
	}

	/** نویسندۀ ناشناس در خروجی JetReviews: نام به زبان صفحه، بی ایمیل. */
	public function raw_user_data( $data ) {

		if ( is_array( $data ) && isset( $data['id'] ) && self::ANON === (string) $data['id'] ) {
			$data['name'] = self::deleted_label();
			$data['mail'] = '';
		}

		return $data;
	}

	/* ── شناسه‌ها ───────────────────────────────────────────────────────── */

	private static function user_by_email( string $email ): ?WP_User {

		$user = '' !== $email ? get_user_by( 'email', $email ) : false;

		return $user instanceof WP_User ? $user : null;
	}

	/** هشِ OTP شمارۀ خودِ کاربر (و شمارۀ متن‌سادۀ ردیف‌های پیشینِ هش‌نشده). */
	private static function otp_keys( WP_User $user ): array {

		$mobile = (string) get_user_meta( $user->ID, '_topersia_mobile', true );

		return '' === $mobile ? array() : array( ToPersia_DB::mobile_hash( $mobile ), $mobile );
	}

	private static function table_exists( string $table ): bool {

		global $wpdb;

		/* بی‌حساسیت به حروف: lower_case_table_names=1 نام را کوچک برمی‌گرداند (ویندوز) */
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

		return is_string( $found ) && 0 === strcasecmp( $found, $table );
	}

	/* ── صدورکننده ──────────────────────────────────────────────────────── */

	public function register_exporter( array $exporters ): array {

		$exporters['topersia-user-core'] = array(
			'exporter_friendly_name' => 'ToPersia',
			'callback'               => array( $this, 'export' ),
		);

		return $exporters;
	}

	/**
	 * @return array{data:array,done:bool}
	 */
	public function export( $email, $page = 1 ): array {

		global $wpdb;

		$user = self::user_by_email( (string) $email );

		if ( ! $user ) {
			return array( 'data' => array(), 'done' => true );
		}

		$id    = (int) $user->ID;
		$items = array();
		$row   = static function ( string $name, $value ): array {
			return array( 'name' => $name, 'value' => is_scalar( $value ) ? (string) $value : wp_json_encode( $value, JSON_UNESCAPED_UNICODE ) );
		};

		/* نمایه، سبک سفر، تنظیم نمایش */
		$profile = array( $row( 'display_name', $user->display_name ), $row( 'registered', $user->user_registered ) );

		foreach ( array( '_topersia_mobile' => 'mobile', '_topersia_country' => 'country', '_topersia_language' => 'language', '_topersia_travel_style' => 'travel_style', '_topersia_display_prefs' => 'display_prefs', 'first_name' => 'first_name', 'last_name' => 'last_name' ) as $key => $label ) {
			$val = get_user_meta( $id, $key, true );
			if ( '' !== $val && array() !== $val && false !== $val ) {
				$profile[] = $row( $label, $val );
			}
		}

		if ( ! ( class_exists( 'ToPersia_Privacy' ) && ToPersia_Privacy::synthetic_email( (string) $user->user_email ) ) ) {
			$profile[] = $row( 'email', $user->user_email );
		}

		if ( class_exists( 'ToPersia_Avatar' ) && ToPersia_Avatar::record( $id ) ) {
			$profile[] = $row( 'avatar', ToPersia_Avatar::photo_url( $id, 256 ) );
		}

		/* ۱٫۱۴٫۰ — زمان تأیید ایمیل و موبایل (ورود ایمیلی، LOGIN.md §۸-ب) */
		foreach ( array( '_topersia_email_verified' => 'email_verified', '_topersia_mobile_verified' => 'mobile_verified' ) as $key => $label ) {
			$at = (int) get_user_meta( $id, $key, true );
			if ( $at > 0 ) {
				$profile[] = $row( $label, gmdate( 'Y-m-d H:i:s', $at ) . ' UTC' );
			}
		}

		$ll = (int) get_user_meta( $id, '_topersia_last_login', true );

		if ( $ll > 0 ) {
			$profile[] = $row( 'last_login', gmdate( 'Y-m-d H:i:s', $ll ) . ' UTC' );
		}

		if ( '1' === (string) get_user_meta( $id, '_topersia_inactive', true ) ) {
			$profile[] = $row( 'inactive', 'yes' );
		}

		$items[] = array( 'group_id' => 'topersia-profile', 'group_label' => 'ToPersia — نمایه', 'item_id' => 'user-' . $id, 'data' => $profile );

		/* ذخیره‌ها */
		$saved = array();

		foreach ( ToPersia_DB::saved_rows( $id ) as $s ) {
			$saved[] = get_the_title( $s['post_id'] ) . ' (' . $s['post_type'] . ' #' . $s['post_id'] . ')';
		}

		if ( $saved ) {
			$items[] = array( 'group_id' => 'topersia-saved', 'group_label' => 'ToPersia — مکان‌های ذخیره‌شده', 'item_id' => 'saved-' . $id, 'data' => array( $row( 'saved', implode( ' · ', $saved ) ) ) );
		}

		/* دنبال‌شده‌ها */
		$followed = get_user_meta( $id, '_topersia_followed', true );

		if ( ! empty( $followed ) ) {
			$items[] = array( 'group_id' => 'topersia-follow', 'group_label' => 'ToPersia — دنبال‌شده‌ها', 'item_id' => 'follow-' . $id, 'data' => array( $row( 'followed', $followed ) ) );
		}

		/* ردیف‌های OTP — فقط فراداده؛ هش و IP هش‌شده برون‌داد نمی‌شود */
		$keys = self::otp_keys( $user );

		if ( $keys && self::table_exists( ToPersia_DB::table_name() ) ) {
			$in   = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
			$rows = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT purpose, created_at, expires_at, attempts, verified FROM ' . ToPersia_DB::table_name() . " WHERE mobile IN ($in) ORDER BY id DESC LIMIT 200", ...$keys ), ARRAY_A );

			foreach ( $rows as $i => $r ) {
				$items[] = array( 'group_id' => 'topersia-otp', 'group_label' => 'ToPersia — درخواست‌های کد ورود', 'item_id' => 'otp-' . $id . '-' . $i, 'data' => array( $row( 'purpose', $r['purpose'] ), $row( 'created_at', $r['created_at'] ), $row( 'verified', $r['verified'] ), $row( 'attempts', $r['attempts'] ) ) );
			}
		}

		/* نظرها */
		$table = $wpdb->prefix . 'jet_reviews';

		if ( self::table_exists( $table ) ) {
			foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, post_id, date, title, content, rating, approved FROM {$table} WHERE author = %s ORDER BY id", (string) $id ), ARRAY_A ) as $r ) {
				$items[] = array( 'group_id' => 'topersia-reviews', 'group_label' => 'ToPersia — نظرها', 'item_id' => 'review-' . $r['id'], 'data' => array( $row( 'post', get_the_title( (int) $r['post_id'] ) ), $row( 'date', $r['date'] ), $row( 'title', $r['title'] ), $row( 'content', $r['content'] ), $row( 'rating', $r['rating'] ), $row( 'approved', $r['approved'] ) ) );
			}
		}

		return array( 'data' => $items, 'done' => true );
	}

	/* ── پاک‌کننده ──────────────────────────────────────────────────────── */

	public function register_eraser( array $erasers ): array {

		$erasers['topersia-user-core'] = array(
			'eraser_friendly_name' => 'ToPersia',
			'callback'             => array( $this, 'erase' ),
		);

		return $erasers;
	}

	/** مهمانِ مشترکِ نویسندۀ ناشناس را (یک‌بار) می‌سازد. */
	private static function ensure_anon_guest(): void {

		global $wpdb;

		$table = $wpdb->prefix . 'jet_review_guests';

		if ( ! self::table_exists( $table ) || $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE guest_id = %s LIMIT 1", self::ANON ) ) ) {
			return;
		}

		$wpdb->insert( $table, array( 'guest_id' => self::ANON, 'name' => self::DELETED['fa'], 'mail' => '', 'phone' => '', 'meta_data' => '' ), array( '%s', '%s', '%s', '%s', '%s' ) );
	}

	/**
	 * نظرها و پاسخ‌ها را ناشناس می‌کند: متن می‌ماند، هر شناسه پاک می‌شود.
	 *
	 * @return int شمار ردیف ناشناس‌شده.
	 */
	public static function anonymize_reviews( int $user_id ): int {

		global $wpdb;

		$n = 0;

		if ( self::table_exists( $wpdb->prefix . 'jet_reviews' ) ) {
			self::ensure_anon_guest();

			foreach ( array( 'jet_reviews', 'jet_review_comments' ) as $t ) {
				if ( self::table_exists( $wpdb->prefix . $t ) ) {
					$n += (int) $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}{$t} SET author = %s WHERE author = %s", self::ANON, (string) $user_id ) );
				}
			}

			if ( self::table_exists( $wpdb->prefix . 'jet_review_reactions' ) ) {
				$wpdb->delete( $wpdb->prefix . 'jet_review_reactions', array( 'user_id' => $user_id ), array( '%d' ) );
			}
		}

		/* نظر هستۀ وردپرس (اگر روزی باشد): محتوا می‌ماند، هر شناسه پاک */
		$n += (int) $wpdb->update(
			$wpdb->comments,
			array( 'user_id' => 0, 'comment_author' => self::DELETED['fa'], 'comment_author_email' => '', 'comment_author_url' => '', 'comment_author_IP' => '' ),
			array( 'user_id' => $user_id ),
			array( '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return $n;
	}

	/**
	 * @return array{items_removed:bool,items_retained:bool,messages:string[],done:bool}
	 */
	public function erase( $email, $page = 1 ): array {

		global $wpdb;

		$out  = array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		$user = self::user_by_email( (string) $email );

		if ( ! $user ) {
			return $out;
		}

		$id = (int) $user->ID;

		/* حساب دارای دسترسی ویرایشی را این مسیر نمی‌گیرد */
		foreach ( array( 'manage_options', 'edit_posts', 'edit_pages', 'upload_files' ) as $cap ) {
			if ( user_can( $user, $cap ) ) {
				$out['items_retained'] = true;
				$out['messages'][]     = 'حساب دارای دسترسی ویرایشی است؛ پاک نشد.';

				return $out;
			}
		}

		/* OTP — پیش از پاک‌شدن متای شماره */
		$keys = self::otp_keys( $user );

		if ( $keys && self::table_exists( ToPersia_DB::table_name() ) ) {
			$in = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
			$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . ToPersia_DB::table_name() . " WHERE mobile IN ($in)", ...$keys ) );
		}

		/* ۱٫۱۴٫۰ — ترنزینت‌های کانال ایمیل (کلید از HMAC نشانی؛ کد، آخرین توکن، سهمیه) */
		if ( class_exists( 'ToPersia_Privacy' ) && class_exists( 'ToPersia_Email_Code' ) && ! ToPersia_Privacy::synthetic_email( (string) $user->user_email ) ) {
			$ek = ToPersia_Email_Code::key_of( strtolower( (string) $user->user_email ) );
			$tk = get_transient( 'tp_ec_latest_' . $ek );
			if ( is_string( $tk ) && '' !== $tk ) {
				delete_transient( 'tp_ec_' . $tk );
			}
			delete_transient( 'tp_ec_latest_' . $ek );
			delete_transient( 'tp_ec_rl_' . $ek );
		}

		/* ذخیره‌ها */
		if ( self::table_exists( ToPersia_DB::saved_table_name() ) ) {
			$wpdb->delete( ToPersia_DB::saved_table_name(), array( 'user_id' => $id ), array( '%d' ) );
		}

		/* نظرها: ناشناس */
		self::anonymize_reviews( $id );

		/* فایل آواتار، پیش از پاک‌شدن متای آن */
		if ( class_exists( 'ToPersia_Avatar' ) ) {
			ToPersia_Avatar::delete_files_for( $id );
		}

		/* متاها: فهرست شناخته‌شده + هر متای `_topersia_…` دیگر */
		foreach ( self::META as $key ) {
			delete_user_meta( $id, $key );
		}

		foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s", $id, $wpdb->esc_like( '_topersia_' ) . '%' ) ) as $key ) {
			delete_user_meta( $id, $key );
		}

		/* حساب */
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $id );

		$out['items_removed'] = true;
		$out['messages'][]    = 'حساب و داده‌های ToPersia پاک و نظرها ناشناس شد.';

		if ( function_exists( 'topersia_log' ) ) {
			topersia_log( 'پاک‌سازی حریم خصوصی: یک حساب پاک شد', 'notice', 'user', array( 'count' => 1 ) );
		}

		return $out;
	}

	/* ── درخواست از پنل (تأیید دومرحله‌ای) ──────────────────────────── */

	private static function action_name( string $type ): string {
		return 'erase' === $type ? 'remove_personal_data' : 'export_personal_data';
	}

	/** آیا برای این کاربر درخواست فعال (در انتظار یا تأییدشده) هست؟ */
	public static function active_request( WP_User $user, string $type ): bool {

		$ids = get_posts(
			array(
				'post_type'      => 'user_request',
				'post_status'    => array( 'request-pending', 'request-confirmed' ),
				'name'           => self::action_name( $type ),
				's'              => '',
				'title'          => $user->user_email,
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		return ! empty( $ids );
	}

	private function reply( bool $ok, string $key, string $fa, array $extra = array(), int $code = 200 ): void {

		$msg = class_exists( 'ToPersia_I18n' ) ? ToPersia_I18n::t( $key, $fa ) : $fa;

		if ( $ok ) {
			wp_send_json_success( array( 'message' => $msg ) + $extra );
		}

		wp_send_json_error( array( 'message' => $msg ) + $extra, $code );
	}

	public function ajax(): void {

		if ( ! is_user_logged_in() || ! wp_verify_nonce( isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '', 'topersia_user_core' ) ) {
			$this->reply( false, 'invalid_request', 'درخواست نامعتبر است.', array(), 403 );
		}

		$user = wp_get_current_user();
		$type = isset( $_POST['type'] ) && 'erase' === $_POST['type'] ? 'erase' : 'export';
		$step = isset( $_POST['step'] ) ? sanitize_key( wp_unslash( $_POST['step'] ) ) : 'start';
		$tkey = 'tp_dr_' . $user->ID . '_' . $type;

		if ( 'erase' === $type ) {
			foreach ( array( 'manage_options', 'edit_posts', 'edit_pages', 'upload_files' ) as $cap ) {
				if ( user_can( $user, $cap ) ) {
					$this->reply( false, 'dr_not_allowed', 'این حساب از این مسیر حذف نمی‌شود.', array(), 403 );
				}
			}
		}

		if ( self::active_request( $user, $type ) ) {
			$this->reply( false, 'dr_already', 'درخواست شما ثبت شده و در انتظار بررسی مدیر است.', array(), 409 );
		}

		if ( 'start' === $step ) {

			$token = wp_generate_password( 24, false, false );
			set_transient( $tkey, hash( 'sha256', $token . wp_salt( 'nonce' ) ), 10 * MINUTE_IN_SECONDS );

			$this->reply( true, 'erase' === $type ? 'dr_confirm_erase' : 'dr_confirm_export', 'erase' === $type ? 'با تأیید نهایی، درخواست حذف حساب برای مدیر ثبت می‌شود. پس از اجرا، حساب و داده‌های شما پاک و نظرهایتان ناشناس می‌شود.' : 'با تأیید نهایی، درخواست نسخه‌ای از داده‌هایتان برای مدیر ثبت می‌شود.', array( 'token' => $token ) );
		}

		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$want  = get_transient( $tkey );

		if ( '' === $token || ! is_string( $want ) || ! hash_equals( $want, hash( 'sha256', $token . wp_salt( 'nonce' ) ) ) ) {
			$this->reply( false, 'dr_expired', 'تأیید منقضی شد؛ دوباره درخواست دهید.', array(), 410 );
		}

		delete_transient( $tkey );

		if ( ! function_exists( 'wp_create_user_request' ) ) {
			require_once ABSPATH . 'wp-admin/includes/privacy-tools.php';
		}

		$req = wp_create_user_request( $user->user_email, self::action_name( $type ), array( 'source' => 'topersia-panel' ), 'confirmed' );

		if ( is_wp_error( $req ) ) {
			$this->reply( false, 'dr_failed', 'ثبت درخواست انجام نشد.', array(), 500 );
		}

		if ( function_exists( 'topersia_log' ) ) {
			topersia_log( 'درخواست حریم خصوصی از پنل: ' . $type, 'notice', 'user', array( 'count' => 1 ) );
		}

		$this->reply( true, 'dr_done', 'درخواست شما ثبت شد؛ پس از بررسی مدیر اجرا می‌شود.' );
	}

	/** یادداشت برای مدیر روی صفحه‌های ابزار حریم خصوصی. */
	public function admin_notice(): void {

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->id, array( 'tools_page_export_personal_data', 'tools_page_remove_personal_data' ), true ) ) {
			return;
		}

		echo '<div class="notice notice-info"><p>ToPersia: ایمیلِ حساب‌های پیامکی ساختگی است (<code>@' . esc_html( ToPersia_Privacy::email_domain() ) . '</code>) و نامه به آن نمی‌رسد. درخواست‌هایی که از پنل کاربر آمده‌اند از پیش «تأییدشده‌اند»؛ برای صدور دکمۀ «دانلود داده‌های شخصی» را بزنید (نه «ایمیل»)، و برای حذف «پاک‌کردن داده‌های شخصی». نظرها ناشناس می‌شوند و حساب پاک می‌شود.</p></div>';
	}
}
