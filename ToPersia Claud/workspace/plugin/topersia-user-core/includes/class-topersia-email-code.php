<?php
/**
 * ToPersia User Core — ورود با کد ایمیلی  ۱٫۱۴٫۰ · P5-D391 · docs/design/LOGIN.md §۳
 *
 * کانال دوم کنار پیامک ایران؛ کاربر انتخاب می‌کند. ثبت‌نام = ورود (ثبت‌نام فقط با ایمیل مجاز). حساب می‌تواند ایمیل
 * و شمارۀ تأییدشده هر دو را داشته باشد (پیوند فقط پس از ورود، کد به شناسۀ تازه).
 *
 * ── امنیت ────────────────────────────────────────────────────────────
 * - کد شش رقمی (صفر پیشین مجاز)، HMAC با wp_salt و توکن (ToPersia_Auth::code_hash)، ده دقیقه، پنج حدس در همان
 *   ترنزینت، یک‌بارمصرف؛ کد تازه کد قبلی همان نشانی را باطل می‌کند. پیوند جادویی نیست.
 * - سقف‌ها (LOGIN.md §۳-الف): هر نشانی ۵/ساعت و ۱۰/روز و ۶۰ ثانیه فاصله · هر IP ۱۰/ساعت و ۳۰/روز و حداکثر ۵ نشانی
 *   متمایز در ساعت · کانال ۳۰۰/ساعت و ۲٬۰۰۰/روز · و سقف ارسال سایت (ToPersia_Mailer: ≤ ۸۰٪ میزبان). همه شکست‌بسته.
 * - پاسخ یکسان (§۳-ب): برای هر نشانی با قالب معتبر همان ۲۰۰ و همان متن — چه حساب باشد چه نه، چه دارای دسترسی (نامۀ
 *   «ورود عادی» به‌جای کد)، چه در پیوند مال حساب دیگر (نامۀ «کسی خواست وصل کند»، بی کد). نامه پس از پاسخ می‌رود.
 * - CSRF: نانس uc-nonce + ToPersia_Auth::origin_ok() روی send و verify.
 * - لاگ: هرگز نشانی خام، دامنه، کد یا توکن — فقط HMAC کوتاه (ToPersia_Mailer::hmac) و شناسۀ کاربر در موفقیت.
 * - ایمیل هرگز در خروجی عمومی: نام ورود، نامک و نام نمایشی تصادفی/خنثی (همان قاعدۀ P5-D354).
 * - جدول OTP برای ایمیل استفاده نمی‌شود (LOGIN.md §۷)؛ فقط ترنزینت.
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Email_Code {

	public const TTL         = 600;
	public const MAX_TRIES   = 5;
	public const COOLDOWN    = 60;
	public const PER_EMAIL_H = 5;
	public const PER_EMAIL_D = 10;
	public const PER_IP_H    = 10;
	public const PER_IP_D    = 30;
	public const IP_EMAILS_H = 5;
	public const CHANNEL_H   = 300;
	public const CHANNEL_D   = 2000;

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'wp_ajax_nopriv_topersia_email_code_send', array( $this, 'ajax_send' ) );
		add_action( 'wp_ajax_topersia_email_code_send', array( $this, 'ajax_send' ) );
		add_action( 'wp_ajax_nopriv_topersia_email_code_verify', array( $this, 'ajax_verify' ) );
		add_action( 'wp_ajax_topersia_email_code_verify', array( $this, 'ajax_verify' ) );
	}

	/** زبان پیام‌های پاسخ — از `lang` درخواست (admin-ajax زبان صفحه را همیشه نمی‌داند)؛ تهی ← زبان جاری. */
	private static string $lang = '';

	private static function msg( string $key, string $fa ): string {
		if ( ! class_exists( 'ToPersia_I18n' ) ) {
			return $fa;
		}
		return '' !== self::$lang ? ToPersia_I18n::in( $key, self::$lang ) : ToPersia_I18n::t( $key, $fa );
	}

	private static function take_lang(): string {
		$l = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
		self::$lang = in_array( $l, array( 'fa', 'en', 'ar' ), true ) ? $l : '';
		return self::$lang;
	}

	private static function log( string $message, string $level = 'info', array $ctx = array() ): void {
		if ( function_exists( 'topersia_log' ) ) {
			topersia_log( $message, $level, 'auth', $ctx + array( 'channel' => 'email' ) );
		}
	}

	/** کلید ترنزینت از HMAC نشانی — نشانی خام هرگز در نام کلید یا مقدار. */
	public static function key_of( string $email ): string {
		return substr( hash_hmac( 'sha256', 'tp-email|' . strtolower( $email ), (string) wp_salt( 'auth' ) ), 0, 40 );
	}

	/* ── نشانی ────────────────────────────────────────────────────────── */

	/** نرمال یا '' — کوچک‌حرف، بی فاصله، قالب معتبر، ≤ ۲۵۴، نه دامنۀ ساختگی، نه دامنۀ یک‌بارمصرف شناخته‌شده. */
	public static function normalize( string $raw ): string {
		$email = strtolower( trim( $raw ) );

		if ( '' === $email || strlen( $email ) > 254 || preg_match( '/\s/', $email ) || ! is_email( $email ) || sanitize_email( $email ) !== $email ) {
			return '';
		}

		if ( class_exists( 'ToPersia_Privacy' ) && ToPersia_Privacy::synthetic_email( $email ) ) {
			return '';   /* کاربر نمی‌تواند حساب ساختگی بسازد یا بدزدد (LOGIN.md §۲-ت) */
		}

		$domain = substr( strrchr( $email, '@' ), 1 );

		if ( in_array( $domain, self::blocklist(), true ) ) {
			return '';
		}

		/* MX فقط وقتی ارسال واقعی روشن است — نسخۀ محلی هیچ درخواست شبکه‌ای نمی‌فرستد */
		if ( class_exists( 'ToPersia_Mailer' ) && ToPersia_Mailer::configured() && function_exists( 'checkdnsrr' )
			&& apply_filters( 'topersia_email_mx_check', true, $domain )
			&& ! checkdnsrr( $domain . '.', 'MX' ) && ! checkdnsrr( $domain . '.', 'A' ) ) {
			return '';
		}

		return $email;
	}

	/** @return string[] */
	private static function blocklist(): array {
		static $list = null;
		if ( null === $list ) {
			$file = __DIR__ . '/data/disposable-domains.php';
			$list = is_file( $file ) ? (array) include $file : array();
			$list = (array) apply_filters( 'topersia_email_domain_blocklist', $list );
			$list = array_values( array_diff( $list, (array) apply_filters( 'topersia_email_domain_allowlist', array() ) ) );
		}
		return $list;
	}

	/* ── سقف‌ها ───────────────────────────────────────────────────────── */

	/** پنجرۀ ساعتی و روزانه در یک ساختار. */
	private static function window( $v, int $now ): array {
		$v = is_array( $v ) ? $v : array();
		foreach ( array( 'h' => HOUR_IN_SECONDS, 'd' => DAY_IN_SECONDS ) as $k => $len ) {
			if ( ! isset( $v[ $k ] ) || ! is_array( $v[ $k ] ) || ( $now - (int) $v[ $k ]['start'] ) >= $len ) {
				$v[ $k ] = array( 'count' => 0, 'start' => $now );
			}
		}
		return $v;
	}

	/**
	 * '' یعنی مجاز (و مصرف ثبت شد)؛ وگرنه کلید پیام. شکست‌بسته.
	 */
	private static function rate( string $email ): string {
		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			return 'service_unavailable';
		}

		$now = time();
		$ipk = 'tp_ec_ip_' . ToPersia_Auth::client_ip_hash();
		$emk = 'tp_ec_rl_' . self::key_of( $email );

		$ch = self::window( get_transient( 'tp_ec_channel' ), $now );
		if ( (int) $ch['h']['count'] >= self::CHANNEL_H || (int) $ch['d']['count'] >= self::CHANNEL_D ) {
			self::log( 'سقف کانال ایمیل پر شد', 'error' );
			return 'mail_later';
		}

		$ip = self::window( get_transient( $ipk ), $now );
		/* فهرست نشانی‌های متمایز درون پنجرۀ ساعتی است و با آن از نو می‌شود */
		$ip['h']['emails'] = isset( $ip['h']['emails'] ) && is_array( $ip['h']['emails'] ) ? $ip['h']['emails'] : array();
		$digest            = substr( self::key_of( $email ), 0, 16 );
		if ( (int) $ip['h']['count'] >= self::PER_IP_H || (int) $ip['d']['count'] >= self::PER_IP_D
			|| ( ! in_array( $digest, $ip['h']['emails'], true ) && count( $ip['h']['emails'] ) >= self::IP_EMAILS_H ) ) {
			self::log( 'سقف ایمیل برای یک نشانی IP پر شد', 'warning' );
			return 'too_many_requests';
		}

		$em = self::window( get_transient( $emk ), $now );
		if ( (int) $em['h']['count'] >= self::PER_EMAIL_H || (int) $em['d']['count'] >= self::PER_EMAIL_D ) {
			return 'email_rate_limited';
		}
		if ( ! empty( $em['last'] ) && ( $now - (int) $em['last'] ) < self::COOLDOWN ) {
			return 'email_wait';
		}

		/* سقف ارسال سایت — آخر، تا سهمش فقط وقتی گرفته شود که لایه‌های دیگر گذشته‌اند */
		if ( ! class_exists( 'ToPersia_Mailer' ) || ! ToPersia_Mailer::reserve() ) {
			return 'mail_later';
		}

		$em['h']['count']++;
		$em['d']['count']++;
		$em['last'] = $now;
		set_transient( $emk, $em, DAY_IN_SECONDS );

		$ip['h']['count']++;
		$ip['d']['count']++;
		if ( ! in_array( $digest, $ip['h']['emails'], true ) ) {
			$ip['h']['emails'][] = $digest;
		}
		set_transient( $ipk, $ip, DAY_IN_SECONDS );

		$ch['h']['count']++;
		$ch['d']['count']++;
		set_transient( 'tp_ec_channel', $ch, DAY_IN_SECONDS );

		return '';
	}

	/* ── درخواست کد ───────────────────────────────────────────────────── */

	/**
	 * منطق خالص «ارسال کد» — بی خروجی HTTP، برای آزمون. نامه برگردانده می‌شود، فرستاده نمی‌شود.
	 *
	 * @return array{status:int, body:array<string,mixed>, mail:?array<string,string>}
	 */
	public static function request( string $raw, string $purpose, string $lang, int $uid ): array {

		$purpose = 'link' === $purpose ? 'link' : 'login';
		$lang    = in_array( $lang, array( 'fa', 'en', 'ar' ), true ) ? $lang : ( class_exists( 'ToPersia_I18n' ) ? ToPersia_I18n::current_language() : 'fa' );
		$err     = static function ( int $code, string $key, string $fa ): array {
			return array( 'status' => $code, 'body' => array( 'message' => self::msg( $key, $fa ) ), 'mail' => null );
		};

		$email = self::normalize( $raw );

		if ( '' === $email ) {
			return $err( 422, 'invalid_email', 'نشانی ایمیل معتبر نیست.' );
		}

		if ( 'link' === $purpose ) {
			$me = $uid > 0 ? get_userdata( $uid ) : false;
			if ( ! $me instanceof WP_User ) {
				return $err( 401, 'login_required', 'برای ادامه وارد حساب شوید.' );
			}
			if ( ToPersia_Auth::privileged( $me ) ) {
				return $err( 403, 'use_normal_login', 'این حساب باید از فرم ورود عادی وارد شود.' );
			}
			if ( self::has_real_email( $me ) && ToPersia_Auth::change_too_soon( $uid, '_topersia_email_verified' ) ) {
				return $err( 429, 'identity_cooldown', 'این شناسه کمتر از ۳۰ روز پیش تغییر کرده است. بعداً دوباره تلاش کنید.' );
			}
		}

		$blocked = self::rate( $email );

		if ( '' !== $blocked ) {
			$fa = array(
				'service_unavailable' => 'سرویس در دسترس نیست.',
				'mail_later'          => 'ارسال ایمیل اکنون ممکن نیست. بعداً دوباره تلاش کنید.',
				'too_many_requests'   => 'تعداد درخواست‌ها زیاد است. یک ساعت دیگر تلاش کنید.',
				'email_rate_limited'  => 'برای این نشانی بیش از حد درخواست شده. بعداً دوباره تلاش کنید.',
				'email_wait'          => 'یک دقیقه دیگر می‌توانید دوباره درخواست بدهید.',
			);
			return $err( 'service_unavailable' === $blocked ? 503 : 429, $blocked, $fa[ $blocked ] );
		}

		/* همه از این‌جا یک مسیر: توکن، ترنزینت، نامه‌ای در صف — تنها فرق نوع نامه است */
		$owner = get_user_by( 'email', $email );
		$kind  = 'code';

		if ( 'login' === $purpose && $owner instanceof WP_User && ToPersia_Auth::privileged( $owner ) ) {
			$kind = 'privileged';
		} elseif ( 'link' === $purpose && $owner instanceof WP_User && (int) $owner->ID !== $uid ) {
			$kind = 'conflict';
		}

		$token = wp_generate_password( 32, false, false );
		$code  = ToPersia_Auth::new_code();

		set_transient(
			'tp_ec_' . $token,
			array(
				'email_h' => self::key_of( $email ),
				'hash'    => 'code' === $kind ? ToPersia_Auth::code_hash( $token, $code ) : ToPersia_Auth::dummy_hash(),
				'tries'   => 0,
				'purpose' => $purpose,
				'uid'     => 'link' === $purpose ? $uid : 0,
			),
			self::TTL
		);
		set_transient( 'tp_ec_latest_' . self::key_of( $email ), $token, self::TTL );

		self::log( 'درخواست کد ایمیلی', 'code' === $kind ? 'info' : 'warning', array( 'kind' => $kind, 'purpose' => $purpose, 'to_h' => ToPersia_Mailer::hmac( $email ) ) );

		return array(
			'status' => 200,
			'body'   => array(
				'token'     => $token,
				'expiresIn' => self::TTL,
				'message'   => self::msg( 'email_code_sent', 'اگر نشانی درست باشد، کدی به آن فرستاده شد.' ),
			),
			'mail'   => self::compose( $kind, $lang, $email, 'code' === $kind ? $code : '', $purpose ),
		);
	}

	/* ── بررسی کد ─────────────────────────────────────────────────────── */

	/**
	 * منطق خالص «بررسی کد» — حساب را می‌یابد یا می‌سازد یا پیوند می‌دهد، ولی نشست نمی‌سازد.
	 *
	 * @return array{status:int, body:array<string,mixed>, user:?WP_User}
	 */
	public static function check( string $token, string $raw, string $code, int $uid ): array {

		$err = static function ( int $status, string $key, string $fa, array $extra = array() ): array {
			return array( 'status' => $status, 'body' => array( 'message' => self::msg( $key, $fa ) ) + $extra, 'user' => null );
		};

		$code  = preg_replace( '/\D/', '', $code );
		$email = strtolower( trim( $raw ) );

		if ( ! preg_match( '/^[A-Za-z0-9]{32}$/', $token ) || ! preg_match( '/^\d{6}$/', (string) $code ) || '' === $email ) {
			return $err( 422, 'otp_invalid', 'کد تأیید معتبر نیست.' );
		}

		$key     = 'tp_ec_' . $token;
		$payload = get_transient( $key );

		if ( ! is_array( $payload ) || empty( $payload['hash'] ) || empty( $payload['email_h'] ) ) {
			return $err( 410, 'otp_expired', 'کد منقضی یا نامعتبر است.' );
		}

		if ( ! hash_equals( (string) $payload['email_h'], self::key_of( $email ) ) ) {
			return $err( 422, 'otp_invalid', 'کد تأیید معتبر نیست.' );
		}

		/* کد تازه‌تر برای همین نشانی ← این یکی باطل */
		if ( get_transient( 'tp_ec_latest_' . self::key_of( $email ) ) !== $token ) {
			delete_transient( $key );
			return $err( 410, 'otp_expired', 'کد منقضی یا نامعتبر است.' );
		}

		$tries = (int) ( $payload['tries'] ?? 0 );

		if ( $tries >= self::MAX_TRIES ) {
			delete_transient( $key );
			self::log( 'سقف حدس کد ایمیلی پر شد', 'error' );
			return $err( 429, 'too_many_attempts', 'تعداد تلاش‌ها بیش از حد مجاز شد. کد تازه بگیرید.' );
		}

		$payload['tries'] = $tries + 1;
		set_transient( $key, $payload, self::TTL );

		if ( ! ToPersia_Auth::code_matches( (string) $payload['hash'], $token, (string) $code ) ) {
			return $err( 422, 'otp_wrong', 'کد تأیید اشتباه است.', array( 'left' => max( 0, self::MAX_TRIES - $payload['tries'] ) ) );
		}

		$done = static function () use ( $key, $email ): void {
			delete_transient( $key );
			delete_transient( 'tp_ec_latest_' . self::key_of( $email ) );
		};

		/* ── پیوند به حساب واردشده ── */
		if ( 'link' === ( $payload['purpose'] ?? '' ) ) {

			if ( $uid <= 0 || $uid !== (int) ( $payload['uid'] ?? 0 ) ) {
				$done();
				return $err( 403, 'invalid_request', 'درخواست نامعتبر است.' );
			}

			$locked = ToPersia_Auth::lock( 'e:' . $email );
			$owner  = get_user_by( 'email', $email );
			$me     = get_userdata( $uid );

			if ( ! $me instanceof WP_User || ( $owner instanceof WP_User && (int) $owner->ID !== $uid )
				|| ( self::has_real_email( $me ) && strtolower( (string) $me->user_email ) !== $email && ToPersia_Auth::change_too_soon( $uid, '_topersia_email_verified' ) ) ) {
				if ( $locked ) { ToPersia_Auth::unlock( 'e:' . $email ); }
				$done();
				return $err( 422, 'otp_invalid', 'کد تأیید معتبر نیست.' );
			}

			$old = self::has_real_email( $me ) && strtolower( (string) $me->user_email ) !== $email ? (string) $me->user_email : '';

			add_filter( 'send_email_change_email', '__return_false', 99 );
			$up = wp_update_user( array( 'ID' => $uid, 'user_email' => $email ) );
			remove_filter( 'send_email_change_email', '__return_false', 99 );

			if ( is_wp_error( $up ) ) {
				if ( $locked ) { ToPersia_Auth::unlock( 'e:' . $email ); }
				return $err( 500, 'internal_error', 'خطای داخلی.' );
			}

			update_user_meta( $uid, '_topersia_email_verified', time() );
			if ( $locked ) { ToPersia_Auth::unlock( 'e:' . $email ); }
			$done();

			if ( '' !== $old && ToPersia_Mailer::reserve() ) {
				/* اعلان امنیتی به نشانی پیشین — فقط اگر واقعی بود؛ سهم سقف سایت هم دارد */
				ToPersia_Mailer::queue( self::compose( 'changed', self::user_lang( $uid ), $old, '', 'link' ) );
			}

			self::log( '' === $old ? 'ایمیل به حساب افزوده شد' : 'ایمیل حساب عوض شد', 'info', array( 'user' => $uid ) );

			return array( 'status' => 200, 'body' => array( 'message' => self::msg( 'identity_saved', 'شناسه تأیید و ذخیره شد.' ), 'reload' => true ), 'user' => $me );
		}

		/* ── ورود یا ثبت‌نام ── */
		$locked = ToPersia_Auth::lock( 'e:' . $email );
		$user   = get_user_by( 'email', $email );

		if ( $user instanceof WP_User && ToPersia_Auth::privileged( $user ) ) {
			if ( $locked ) { ToPersia_Auth::unlock( 'e:' . $email ); }
			$done();
			self::log( 'ورود حساب دارای دسترسی با کد ایمیلی رد شد', 'error', array( 'user' => $user->ID ) );
			return $err( 403, 'use_normal_login', 'این حساب باید از فرم ورود عادی وارد شود.' );
		}

		if ( ! $user instanceof WP_User ) {
			$user = self::create_user( $email );
		}

		if ( $locked ) { ToPersia_Auth::unlock( 'e:' . $email ); }

		if ( ! $user instanceof WP_User ) {
			return $err( 500, 'account_create_failed', 'ساخت حساب کاربری انجام نشد.' );
		}

		if ( ! get_user_meta( $user->ID, '_topersia_email_verified', true ) ) {
			update_user_meta( $user->ID, '_topersia_email_verified', time() );
		}

		$done();

		return array( 'status' => 200, 'body' => array(), 'user' => $user );
	}

	/**
	 * حساب ایمیلی — همان قاعدۀ حساب پیامکی (P5-D354): نام ورود، نامک و نام نمایشی تصادفی/خنثی؛ ایمیل فقط در
	 * user_email (وردپرس یکتایی را می‌سنجد). نقش صریح مشترک.
	 */
	private static function create_user( string $email ): ?WP_User {

		if ( ! class_exists( 'ToPersia_Privacy' ) ) {
			return null;
		}

		$user_id = wp_create_user( ToPersia_Privacy::random_login(), wp_generate_password( 32, true, true ), $email );

		if ( is_wp_error( $user_id ) ) {
			self::log( 'ساخت حساب ایمیلی ناموفق', 'error', array( 'code' => $user_id->get_error_code() ) );
			return null;
		}

		$neutral = ToPersia_Privacy::neutral_name( (int) $user_id, 'fa' );

		wp_update_user( array(
			'ID'            => $user_id,
			'display_name'  => $neutral,
			'nickname'      => $neutral,
			'user_nicename' => ToPersia_Privacy::random_nicename(),
		) );

		$user = new WP_User( $user_id );
		$user->set_role( 'subscriber' );

		update_user_meta( $user_id, '_topersia_email_verified', time() );

		self::log( 'حساب ایمیلی ساخته شد', 'info', array( 'user' => (int) $user_id ) );

		return $user;
	}

	public static function has_real_email( WP_User $u ): bool {
		$e = (string) $u->user_email;
		return '' !== $e && ! ( class_exists( 'ToPersia_Privacy' ) && ToPersia_Privacy::synthetic_email( $e ) );
	}

	private static function user_lang( int $uid ): string {
		$l = (string) get_user_meta( $uid, '_topersia_language', true );
		return in_array( $l, array( 'fa', 'en', 'ar' ), true ) ? $l : 'fa';
	}

	/** اعلان امنیتی تغییر شناسۀ دیگر (از کانال پیامک) به ایمیل واقعی حساب. */
	public static function notify_change( int $uid, string $what ): void {
		$u = get_userdata( $uid );
		if ( $u instanceof WP_User && self::has_real_email( $u ) && class_exists( 'ToPersia_Mailer' ) && ToPersia_Mailer::reserve() ) {
			ToPersia_Mailer::queue( self::compose( 'mobile' === $what ? 'mobile_changed' : 'changed', self::user_lang( $uid ), (string) $u->user_email, '', 'link' ) );
		}
	}

	/* ── نامه ─────────────────────────────────────────────────────────── */

	/**
	 * نامه به زبان صفحۀ درخواست — HTML سبک و متن ساده از دو قالب templates/email/.
	 *
	 * @return array{to:string,subject:string,text:string,html:string,kind:string,lang:string}
	 */
	public static function compose( string $kind, string $lang, string $to, string $code, string $purpose ): array {

		$in  = static function ( string $k ) use ( $lang ): string {
			return class_exists( 'ToPersia_I18n' ) ? ToPersia_I18n::in( $k, $lang ) : $k;
		};
		$map = array(
			'code'           => array( 'mail_subject_code', 'mail_title_code', array( 'link' === $purpose ? 'mail_code_intro_link' : 'mail_code_intro' ), array( 'mail_code_ttl', 'mail_code_ignore', 'mail_never_ask' ) ),
			'privileged'     => array( 'mail_subject_login', 'mail_title_login', array( 'mail_privileged_body' ), array( 'mail_code_ignore' ) ),
			'conflict'       => array( 'mail_subject_conflict', 'mail_title_conflict', array( 'mail_conflict_body' ), array( 'mail_code_ignore' ) ),
			'changed'        => array( 'mail_subject_changed', 'mail_title_changed', array( 'mail_changed_body' ), array( 'mail_changed_help' ) ),
			'mobile_changed' => array( 'mail_subject_mobile', 'mail_title_mobile', array( 'mail_mobile_body' ), array( 'mail_changed_help' ) ),
		);
		$row = $map[ $kind ] ?? $map['code'];

		$tp_m = array(
			'lang'   => $lang,
			'dir'    => in_array( $lang, array( 'fa', 'ar' ), true ) ? 'rtl' : 'ltr',
			'title'  => $in( $row[1] ),
			'lines'  => array_map( $in, $row[2] ),
			'code'   => $code,
			'notes'  => array_map( $in, $row[3] ),
			'footer' => $in( 'mail_footer' ),
		);

		ob_start();
		include TOPERSIA_USER_CORE_DIR . 'templates/email/message.php';
		$html = (string) ob_get_clean();

		ob_start();
		include TOPERSIA_USER_CORE_DIR . 'templates/email/message.txt.php';
		$text = (string) ob_get_clean();

		return array( 'to' => $to, 'subject' => $in( $row[0] ), 'text' => $text, 'html' => $html, 'kind' => $kind, 'lang' => $lang );
	}

	/* ── AJAX ─────────────────────────────────────────────────────────── */

	private static function guard(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'topersia_user_core' ) || ! ToPersia_Auth::origin_ok() ) {
			wp_send_json_error( array( 'message' => self::msg( 'invalid_request', 'درخواست نامعتبر است.' ) ), 403 );
		}
	}

	public function ajax_send(): void {
		self::take_lang();
		self::guard();

		$purpose = isset( $_POST['purpose'] ) && 'link' === sanitize_key( wp_unslash( $_POST['purpose'] ) ) ? 'link' : 'login';
		$lang    = self::take_lang();
		$raw     = isset( $_POST['email'] ) ? (string) wp_unslash( $_POST['email'] ) : '';
		$r       = self::request( $raw, $purpose, $lang, get_current_user_id() );

		if ( $r['mail'] ) {
			ToPersia_Mailer::queue( $r['mail'] );   /* پس از پاسخ (shutdown) — زمان پاسخ برای همه یکی */
		}

		if ( 200 === $r['status'] ) {
			wp_send_json_success( $r['body'] );
		}
		wp_send_json_error( $r['body'], $r['status'] );
	}

	public function ajax_verify(): void {
		self::take_lang();
		self::guard();

		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$raw   = isset( $_POST['email'] ) ? (string) wp_unslash( $_POST['email'] ) : '';
		$code  = isset( $_POST['otp'] ) ? (string) wp_unslash( $_POST['otp'] ) : '';
		$r     = self::check( $token, $raw, $code, get_current_user_id() );

		if ( 200 !== $r['status'] ) {
			wp_send_json_error( $r['body'], $r['status'] );
		}

		if ( ! empty( $r['body']['reload'] ) ) {
			wp_send_json_success( $r['body'] );
		}

		ToPersia_Auth::start_session( $r['user'], ! empty( $_POST['remember'] ) );
		self::log( 'ورود با کد ایمیلی', 'info', array( 'user' => $r['user']->ID ) );

		$back = isset( $_POST['redirect'] ) ? (string) wp_unslash( $_POST['redirect'] ) : '';
		wp_send_json_success( array( 'redirect' => ToPersia_Auth::safe_redirect( $back ) ) );
	}
}
