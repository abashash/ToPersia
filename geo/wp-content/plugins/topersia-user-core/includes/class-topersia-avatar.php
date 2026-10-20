<?php
/**
 * آواتار محلی — بی gravatar.com (P5-D377).
 *
 * `pre_get_avatar_data` پیش از آن‌که وردپرس نشانی gravatar (با md5 ایمیل) بسازد
 * نشانی را می‌دهد؛ پس نه درخواستی به gravatar می‌رود نه هش ایمیل در HTML می‌آید.
 * `show_avatars` دست‌نخورده می‌ماند.
 *
 * - عکس کاربر: فقط JPG/PNG/WebP، ≤ ۲ مگابایت، نوع واقعی فایل، nonce، فقط برای خودش.
 *   با ویرایشگر تصویر وردپرس بازکدگذاری می‌شود (مربع، WebP، ۲۵۶ و ۹۶) و EXIF/GPS با آن می‌رود.
 * - محل: uploads/topersia-avatars/ با نام تصادفی، بیرون از کتابخانۀ رسانه، با index.php خالی.
 * - فقط user meta `_topersia_avatar` (توکن + زمان). جدول و فیلد تازه نه.
 * - پیش‌فرض: یکی از ۱۲ شمایل خنثی ایستای افزونه، با رنگ مشتق از هش شناسه؛ ابعاد صریح.
 * - کاربر غیرفعال (ToPersia_Inactivity) شمایل خنثی می‌گیرد، نه عکسش.
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Avatar {

	private static ?self $instance = null;

	public const META     = '_topersia_avatar';
	public const DIR      = 'topersia-avatars';
	public const MAX_BYTE = 2097152;
	public const MAX_SIDE = 8000;
	public const BIG      = 256;
	public const SMALL    = 96;
	public const TONES    = 12;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_filter( 'pre_get_avatar_data', array( $this, 'pre_get_avatar_data' ), 5, 2 );
		add_action( 'wp_ajax_topersia_avatar_upload', array( $this, 'ajax_upload' ) );
		add_action( 'wp_ajax_topersia_avatar_delete', array( $this, 'ajax_delete' ) );
		add_action( 'delete_user', array( __CLASS__, 'delete_files_for' ), 5 );
	}

	/* ── مسیر و نشانی ──────────────────────────────────────────────────── */

	public static function dir(): string {
		$u = wp_upload_dir( null, false );

		return trailingslashit( (string) $u['basedir'] ) . self::DIR . '/';
	}

	public static function base_url(): string {
		$u = wp_upload_dir( null, false );

		return trailingslashit( (string) $u['baseurl'] ) . self::DIR . '/';
	}

	private static function ensure_dir(): bool {
		$dir = self::dir();

		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		if ( ! file_exists( $dir . 'index.php' ) ) {
			file_put_contents( $dir . 'index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		return true;
	}

	/** @return array{t:string,v:int}|null */
	public static function record( int $user_id ): ?array {
		$m = get_user_meta( $user_id, self::META, true );

		if ( is_array( $m ) && isset( $m['t'] ) && 1 === preg_match( '/^[a-f0-9]{24}$/', (string) $m['t'] ) ) {
			return array( 't' => (string) $m['t'], 'v' => (int) ( $m['v'] ?? 0 ) );
		}

		return null;
	}

	/** نشانی عکس بارگذاری‌شده، یا '' */
	public static function photo_url( int $user_id, int $size = 96 ): string {
		$r = self::record( $user_id );

		if ( ! $r ) {
			return '';
		}

		$px = $size <= self::SMALL ? self::SMALL : self::BIG;

		return self::base_url() . $r['t'] . '-' . $px . '.webp?v=' . $r['v'];
	}

	/** شمایل خنثی: رنگ از هش شناسه (HMAC با نمک سایت). */
	public static function default_url( int $user_id = 0 ): string {
		$file = 'avatar-neutral.svg';

		if ( $user_id > 0 ) {
			$h    = hash_hmac( 'sha256', 'tp-avatar-' . $user_id, wp_salt( 'auth' ) );
			$file = 'avatar-' . ( hexdec( substr( $h, 0, 6 ) ) % self::TONES ) . '.svg';
		}

		return TOPERSIA_USER_CORE_URL . 'assets/img/' . $file;
	}

	/* ── قلاب نشانی ────────────────────────────────────────────────────── */

	/** @param mixed $id_or_email */
	private static function resolve_user( $id_or_email ): int {

		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}

		if ( $id_or_email instanceof WP_User ) {
			return (int) $id_or_email->ID;
		}

		if ( $id_or_email instanceof WP_Post ) {
			return (int) $id_or_email->post_author;
		}

		if ( $id_or_email instanceof WP_Comment ) {
			return (int) $id_or_email->user_id;
		}

		if ( is_string( $id_or_email ) && false !== strpos( $id_or_email, '@' ) ) {
			$u = get_user_by( 'email', $id_or_email );

			return $u ? (int) $u->ID : 0;
		}

		return 0;
	}

	public function pre_get_avatar_data( $args, $id_or_email ) {

		$args = (array) $args;
		$uid  = self::resolve_user( $id_or_email );
		$size = max( 1, (int) ( $args['size'] ?? 96 ) );
		$url  = '';

		if ( $uid > 0 && ! ( class_exists( 'ToPersia_Inactivity' ) && ToPersia_Inactivity::is_inactive( $uid ) ) ) {
			$url = self::photo_url( $uid, $size );
		}

		$args['url']          = '' !== $url ? $url : self::default_url( $uid );
		$args['found_avatar'] = true;
		$args['width']        = $size;
		$args['height']       = $size;

		if ( '' !== $url && $size <= self::SMALL ) {
			$args['url2x'] = self::base_url() . self::record( $uid )['t'] . '-' . self::BIG . '.webp?v=' . self::record( $uid )['v'];
		}

		return $args;
	}

	/* ── پاک‌کردن فایل ─────────────────────────────────────────────────── */

	public static function delete_files( string $token ): void {

		if ( 1 !== preg_match( '/^[a-f0-9]{24}$/', $token ) ) {
			return;
		}

		foreach ( array( self::BIG, self::SMALL ) as $px ) {
			$f = self::dir() . $token . '-' . $px . '.webp';

			if ( is_file( $f ) ) {
				wp_delete_file( $f );
			}
		}
	}

	/** فایل + متا؛ حذف حساب و پاک‌کنندۀ حریم خصوصی */
	public static function delete_files_for( $user_id ): void {

		$user_id = (int) $user_id;
		$r       = self::record( $user_id );

		if ( $r ) {
			self::delete_files( $r['t'] );
		}

		delete_user_meta( $user_id, self::META );
	}

	/* ── بارگذاری ──────────────────────────────────────────────────────── */

	private function nonce_ok(): bool {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		return (bool) wp_verify_nonce( $nonce, 'topersia_user_core' );
	}

	private static function fail( string $key, string $fa, int $code = 400 ): void {
		wp_send_json_error( array( 'message' => class_exists( 'ToPersia_I18n' ) ? ToPersia_I18n::t( $key, $fa ) : $fa ), $code );
	}

	public function ajax_upload(): void {

		if ( ! $this->nonce_ok() ) {
			self::fail( 'invalid_request', 'درخواست نامعتبر است.', 403 );
		}

		if ( ! is_user_logged_in() ) {
			self::fail( 'login_required', 'برای ادامه وارد حساب شوید.', 401 );
		}

		$uid = get_current_user_id();

		$tkey = 'tp_av_rl_' . $uid;
		$n    = (int) get_transient( $tkey );

		if ( $n >= 10 ) {
			self::fail( 'avatar_rate', 'تلاش‌های بارگذاری زیاد بود؛ کمی بعد دوباره امتحان کنید.', 429 );
		}

		set_transient( $tkey, $n + 1, HOUR_IN_SECONDS );

		$file = $_FILES['avatar'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput

		if ( ! is_array( $file ) || ! isset( $file['tmp_name'], $file['error'], $file['size'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( (string) $file['tmp_name'] ) ) {
			self::fail( 'avatar_bad', 'فایل دریافت نشد.' );
		}

		$res = self::process( (string) $file['tmp_name'], $uid );

		if ( is_wp_error( $res ) ) {
			self::fail( $res->get_error_code(), $res->get_error_message() );
		}

		wp_send_json_success( array(
			'url'     => self::photo_url( $uid, 96 ),
			'message' => class_exists( 'ToPersia_I18n' ) ? ToPersia_I18n::t( 'avatar_saved', 'عکس نمایه ذخیره شد.' ) : 'ok',
		) );
	}

	public function ajax_delete(): void {

		if ( ! $this->nonce_ok() ) {
			self::fail( 'invalid_request', 'درخواست نامعتبر است.', 403 );
		}

		if ( ! is_user_logged_in() ) {
			self::fail( 'login_required', 'برای ادامه وارد حساب شوید.', 401 );
		}

		$uid = get_current_user_id();
		self::delete_files_for( $uid );

		wp_send_json_success( array(
			'url'     => self::default_url( $uid ),
			'message' => class_exists( 'ToPersia_I18n' ) ? ToPersia_I18n::t( 'avatar_removed', 'عکس نمایه حذف شد.' ) : 'ok',
		) );
	}

	/**
	 * بررسی و بازکدگذاری. تست‌پذیر بی HTTP.
	 *
	 * @return true|WP_Error
	 */
	public static function process( string $tmp, int $user_id ) {

		$size = is_file( $tmp ) ? (int) filesize( $tmp ) : 0;

		if ( $size <= 0 || $size > self::MAX_BYTE ) {
			return new WP_Error( 'avatar_size', 'حجم عکس باید حداکثر ۲ مگابایت باشد.' );
		}

		/* نوع واقعی از محتوا، نه نام و نه نوع اعلام‌شدۀ مرورگر */
		$info = @getimagesize( $tmp ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$mime = is_array( $info ) ? (string) ( $info['mime'] ?? '' ) : '';

		if ( ! in_array( $mime, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
			return new WP_Error( 'avatar_type', 'فقط JPG و PNG و WebP پذیرفته می‌شود.' );
		}

		if ( (int) $info[0] < 16 || (int) $info[1] < 16 || (int) $info[0] > self::MAX_SIDE || (int) $info[1] > self::MAX_SIDE ) {
			return new WP_Error( 'avatar_dim', 'ابعاد عکس مجاز نیست.' );
		}

		if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) || ! self::ensure_dir() ) {
			return new WP_Error( 'avatar_env', 'ذخیرۀ عکس در این سرور ممکن نیست.', array( 'status' => 500 ) );
		}

		$token = bin2hex( random_bytes( 12 ) );
		$out   = array();

		foreach ( array( self::BIG, self::SMALL ) as $px ) {
			$ed = wp_get_image_editor( $tmp );

			if ( is_wp_error( $ed ) ) {
				self::delete_files( $token );

				return new WP_Error( 'avatar_decode', 'عکس خوانده نشد.' );
			}

			if ( method_exists( $ed, 'maybe_exif_rotate' ) ) {
				$ed->maybe_exif_rotate();
			}

			$ed->resize( $px, $px, true );
			$ed->set_quality( 82 );
			$dest = self::dir() . $token . '-' . $px . '.webp';
			$saved = $ed->save( $dest, 'image/webp' );

			if ( is_wp_error( $saved ) || ! is_file( $dest ) ) {
				self::delete_files( $token );

				return new WP_Error( 'avatar_save', 'ذخیرۀ عکس انجام نشد.' );
			}

			$out[] = $dest;
		}

		$old = self::record( $user_id );

		update_user_meta( $user_id, self::META, array( 't' => $token, 'v' => time() ) );

		if ( $old ) {
			self::delete_files( $old['t'] );
		}

		return true;
	}
}
