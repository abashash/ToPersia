<?php
/**
 * ToPersia User Core — احراز هویت با کد یک‌بارمصرف
 *
 * سه ایراد بحرانی نسخۀ ۱٫۸٫۱ اینجا بسته شده‌اند:
 *
 *   ۱. بمباران پیامک — محدودیت فقط روی شمارۀ موبایل بود. ip_hash() نوشته
 *      شده بود ولی هیچ‌جا برای محدودکردن به کار نمی‌رفت، پس چرخاندن شماره
 *      یعنی پیامک نامحدود به هزینۀ ما. حالا سه لایه محدودیت هست: شماره،
 *      نشانی، و سقف کل سایت.
 *
 *   ۲. شکست باز در حدس‌زدن کد — سقف پنج تلاش داخل if ($attempt_id) بود.
 *      اگر آن ترنزینت از کش می‌افتاد، سقف کاملاً دور زده می‌شد. حالا شمارندۀ
 *      حدس داخل همان ترنزینتی است که هش کد را نگه می‌دارد؛ اگر آن نباشد
 *      اصلاً کدی برای بررسی وجود ندارد. شکست بسته است.
 *
 *   ۳. ورود مدیر با پیامک — هیچ بررسی سطح دسترسی نبود. اگر شمارۀ موبایل
 *      روی حساب مدیر ثبت می‌شد، پیامک تبدیل می‌شد به دور زدن کامل رمز.
 *      حالا هر حسابی که می‌تواند محتوا بسازد از این مسیر رد می‌شود.
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Auth {

	/** سقف پیامک برای یک شماره در یک ساعت. */
	private const MAX_PER_MOBILE = 5;

	/** سقف پیامک از یک نشانی در یک ساعت. */
	private const MAX_PER_IP = 10;

	/** سقف شماره‌های متمایز از یک نشانی — این جلوی چرخاندن شماره را می‌گیرد. */
	private const MAX_MOBILES_PER_IP = 3;

	/** سقف کل سایت در یک ساعت — شیر اطمینان هزینه. */
	private const MAX_GLOBAL = 120;

	/** فاصلۀ لازم میان دو درخواست برای یک شماره. */
	private const COOLDOWN = 45;

	/** سقف حدس برای هر کد. */
	private const MAX_TRIES = 5;

	/** عمر کد. */
	private const TTL = 300;

	/** فاصلۀ لازم میان دو تغییر یک شناسه (P5-D391 بند ۳ — ۳۰ روز). */
	public const CHANGE_GAP = 30 * DAY_IN_SECONDS;

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'wp_ajax_nopriv_topersia_send_otp', array( $this, 'send_otp' ) );
		add_action( 'wp_ajax_topersia_send_otp', array( $this, 'send_otp' ) );
		add_action( 'wp_ajax_nopriv_topersia_verify_otp', array( $this, 'verify_otp' ) );
		add_action( 'wp_ajax_topersia_verify_otp', array( $this, 'verify_otp' ) );
		add_action( 'wp_ajax_topersia_logout', array( $this, 'logout' ) );
		add_shortcode( 'topersia_login', array( $this, 'login_shortcode' ) );
		add_action( 'wp_footer', array( $this, 'login_modal' ), 20 );
		add_action( 'rest_api_init', array( $this, 'register_rest' ) );
	}

	/**
	 * نانسِ مهمان از یک نقطۀ REST بی‌کش — ۱٫۱۲٫۱ · P5-D374.
	 *
	 * HTML مهمان کش می‌شود (برچسب‌های `tp-post-…`)؛ نانس وردپرس ۱۲ تا ۲۴ ساعت اعتبار دارد و درون HTML کش‌شده منقضی می‌شد.
	 * پس در HTML مهمان نیست و هنگام نیاز (شروع ورود با شماره) از همین‌جا گرفته می‌شود. پاسخ هرگز کش نمی‌شود:
	 * `Cache-Control: no-store` و استثنای LiteSpeed (`X-LiteSpeed-Cache-Control: no-cache` + `litespeed_control_set_nocache`).
	 */
	public function register_rest(): void {

		register_rest_route(
			'topersia/v1',
			'/uc-nonce',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => function () {
					do_action( 'topersia_pagecache_nocache', 'uc-nonce' );
					do_action( 'litespeed_control_set_nocache', 'topersia: uc-nonce' );

					$r = rest_ensure_response( array( 'nonce' => wp_create_nonce( 'topersia_user_core' ) ) );
					$r->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
					$r->header( 'Pragma', 'no-cache' );
					$r->header( 'X-LiteSpeed-Cache-Control', 'no-cache' );

					return $r;
				},
			)
		);
	}

	/**
	 * نشانی بازگشت پس از ورود — فقط همین میزبان.  ۱٫۱۰٫۰
	 *
	 * «پنجرۀ ورود» روی صفحۀ موجودیت باز می‌شود و کاربر باید پس از ورود
	 * به همان صفحه برگردد، نه به پنل. هر نشانی بیرونی یا نامعتبر به
	 * برگۀ حساب می‌افتد — `wp_validate_redirect` میزبان را می‌سنجد، پس
	 * این مسیر به تغییرمسیرِ باز تبدیل نمی‌شود.
	 *
	 * @param string $raw نشانی خام.
	 * @return string
	 */
	public static function safe_redirect( string $raw ): string {

		$fallback = class_exists( 'ToPersia_Admin' ) ? ToPersia_Admin::account_url() : home_url( '/' );
		$raw      = trim( $raw );

		if ( '' === $raw ) {
			return $fallback;
		}

		return (string) wp_validate_redirect( esc_url_raw( $raw ), $fallback );
	}

	/**
	 * پیام به زبان صفحه — از واژه‌نامۀ سه‌زبانۀ همین افزونه.  ۱٫۱۰٫۳
	 *
	 * تا ۱٫۱۰٫۲ همۀ پیام‌های مسیر ورود `__()` فارسی بودند و کاتالوگی نداشتند؛
	 * روی صفحۀ انگلیسی و عربی فارسی می‌ماندند. این کلاس بی واژه‌نامه هم
	 * بار می‌شود (هارنس SECURITY.md فقط همین پرونده را می‌خواند)، پس جانشینِ
	 * فارسی همین‌جاست. منطق احراز هویت دست نخورده؛ فقط متن پیام.
	 */
	private static function msg( string $key, string $fa ): string {
		return class_exists( 'ToPersia_I18n' ) ? ToPersia_I18n::t( $key, $fa ) : $fa;
	}

	/* ==================================================================
	   کمکی
	   ================================================================== */

	/**
	 * ثبت رویداد در لاگ هسته، اگر هسته باشد.
	 *
	 * error_log روی این هاست غیرفعال است و نباید به آن تکیه کرد.
	 *
	 * @param string $message پیام.
	 * @param string $level   سطح.
	 * @param array  $context بافت.
	 * @return void
	 */
	private function log( string $message, string $level = 'warning', array $context = array() ): void {
		if ( function_exists( 'topersia_log' ) ) {
			topersia_log( $message, $level, 'auth', $context );
		}
	}

	private function nonce_ok(): bool {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		return (bool) wp_verify_nonce( $nonce, 'topersia_user_core' ) && self::origin_ok();
	}

	/**
	 * CSRF ورود — P5-D391 بند ۴: نانس uc-nonce (دستۀ ۱۸) می‌ماند و توکن دومی ساخته نمی‌شود؛ به‌علاوه
	 * اگر مرورگر `Origin` یا `Sec-Fetch-Site` فرستاد، باید همین میزبان باشد. بی سرآیند ← فقط نانس.
	 * روی send و verify هر دو کانال (پیامک و ایمیل).
	 */
	public static function origin_ok(): bool {

		$sfs = isset( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ? strtolower( trim( (string) wp_unslash( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ) ) : '';

		if ( '' !== $sfs && 'same-origin' !== $sfs ) {
			return false;
		}

		$origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? trim( (string) wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';

		if ( '' === $origin ) {
			return true;
		}

		$o = parse_url( $origin );

		if ( ! is_array( $o ) || empty( $o['scheme'] ) || empty( $o['host'] ) ) {
			return false;   /* از جمله «null» — سند sandbox یا file */
		}

		$port = static function ( array $u ): int {
			return isset( $u['port'] ) ? (int) $u['port'] : ( 'https' === strtolower( (string) $u['scheme'] ) ? 443 : 80 );
		};

		foreach ( array_unique( array( home_url( '/' ), function_exists( 'site_url' ) ? site_url( '/' ) : home_url( '/' ) ) ) as $base ) {
			$b = parse_url( (string) $base );
			if ( is_array( $b ) && ! empty( $b['host'] ) && ! empty( $b['scheme'] )
				&& strtolower( (string) $b['scheme'] ) === strtolower( (string) $o['scheme'] )
				&& strtolower( (string) $b['host'] ) === strtolower( (string) $o['host'] )
				&& $port( $b ) === $port( $o ) ) {
				return true;
			}
		}

		return false;
	}

	/* ── کد یک‌بارمصرف: ساخت و هش — مشترک دو کانال ───────────────────── */

	/** شش رقم با صفر پیشین — فضای کامل ۰۰۰۰۰۰ تا ۹۹۹۹۹۹ (LOGIN.md §۳-الف). */
	public static function new_code(): string {
		return str_pad( (string) random_int( 0, 999999 ), 6, '0', STR_PAD_LEFT );
	}

	/**
	 * HMAC کد با نمک سایت و توکن همان درخواست — کد خام هرگز ذخیره نمی‌شود؛ مقایسه با hash_equals (ثابت‌زمان).
	 * توکن در پیام است، پس هش یک کد برای توکن دیگر به کار نمی‌آید.
	 */
	public static function code_hash( string $token, string $code ): string {
		return hash_hmac( 'sha256', 'tp-code|' . $token . '|' . $code, (string) wp_salt( 'auth' ) );
	}

	public static function code_matches( string $stored, string $token, string $code ): bool {
		if ( '' !== $stored && '$' === $stored[0] ) {
			return wp_check_password( $code, $stored );   /* ترنزینت پیش از ۱٫۱۴ (حداکثر چند دقیقه) */
		}
		return '' !== $stored && hash_equals( $stored, self::code_hash( $token, $code ) );
	}

	/** هش بی‌جفت — برای پاسخ یکسان وقتی کدی فرستاده نمی‌شود؛ هیچ کدی با آن نمی‌خواند. */
	public static function dummy_hash(): string {
		return hash_hmac( 'sha256', 'tp-dummy|' . bin2hex( random_bytes( 16 ) ), (string) wp_salt( 'auth' ) );
	}

	/** قفل یکتایی ساخت/پیوند شناسه (LOGIN.md §۱۰ گام ۵) — دو درخواست هم‌زمان یک حساب. */
	public static function lock( string $ident ): bool {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_var' ) ) {
			return true;
		}
		return '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', 'tp_ident_' . substr( hash( 'sha256', $ident ), 0, 40 ) ) );
	}

	public static function unlock( string $ident ): void {
		global $wpdb;
		if ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'get_var' ) ) {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'tp_ident_' . substr( hash( 'sha256', $ident ), 0, 40 ) ) );
		}
	}

	/** نشست پس از کد درست — هر دو کانال. */
	public static function start_session( WP_User $user, bool $remember ): void {
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, $remember, is_ssl() );

		/* بدون این، WPML و افزونه‌های امنیتی و گزارش ورود این رویداد را اصلاً نمی‌بینند. */
		do_action( 'wp_login', $user->user_login, $user );
	}

	/** آیا تغییر این شناسه زودتر از ۳۰ روز پس از تغییر قبلی است؟ (شناسۀ تازه — بی مقدار پیشین — آزاد است) */
	public static function change_too_soon( int $user_id, string $meta ): bool {
		$at = (int) get_user_meta( $user_id, $meta, true );
		return $at > 0 && ( time() - $at ) < self::CHANGE_GAP;
	}

	private function normalize_mobile( string $mobile ): string {
		$mobile = preg_replace( '/\s+/', '', $mobile );
		$mobile = str_replace( array( '+98', '0098' ), '0', $mobile );

		return preg_replace( '/[^0-9]/', '', $mobile );
	}

	private function valid_mobile( string $mobile ): bool {
		return (bool) preg_match( '/^09[0-9]{9}$/', $mobile );
	}

	/**
	 * نشانی درخواست‌کننده، هش‌شده.
	 *
	 * هدرهای X-Forwarded جعل‌شدنی‌اند و فقط وقتی خوانده می‌شوند که ثابت
	 * TOPERSIA_TRUST_PROXY صریحاً تعریف شده باشد.
	 *
	 * @return string
	 */
	public static function client_ip_hash(): string {
		return self::instance()->ip_hash();
	}

	private function ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( defined( 'TOPERSIA_TRUST_PROXY' ) && TOPERSIA_TRUST_PROXY && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$chain = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$first = trim( (string) reset( $chain ) );

			if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
				$ip = $first;
			}
		}

		return hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
	}

	/**
	 * آیا درخواست باید رد شود؟
	 *
	 * سه لایه، و هر سه شکست‌بسته‌اند: اگر ذخیره‌سازی کار نکند، درخواست رد
	 * می‌شود نه اینکه از محدودیت بگذرد.
	 *
	 * @param string $mobile شماره.
	 * @return string رشتۀ خالی یعنی مجاز؛ وگرنه پیام رد.
	 */
	private function rate_limited( string $mobile ): string {

		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			return self::msg( 'service_unavailable', 'سرویس در دسترس نیست.' );
		}

		$now = time();
		$ip  = $this->ip_hash();

		/* ── لایۀ سوم: سقف کل سایت ── */
		$g_key  = 'tp_otp_global';
		$global = get_transient( $g_key );
		$global = is_array( $global ) ? $global : array( 'count' => 0, 'start' => $now );

		if ( ( $now - (int) $global['start'] ) >= HOUR_IN_SECONDS ) {
			$global = array( 'count' => 0, 'start' => $now );
		}

		if ( (int) $global['count'] >= self::MAX_GLOBAL ) {
			$this->log( 'سقف ساعتی پیامک کل سایت پر شد', 'error', array( 'cap' => self::MAX_GLOBAL ) );

			return self::msg( 'otp_paused', 'ارسال کد موقتاً در دسترس نیست. کمی بعد دوباره تلاش کنید.' );
		}

		/* ── لایۀ دوم: نشانی درخواست‌کننده ── */
		$ip_key = 'tp_otp_ip_' . $ip;
		$per_ip = get_transient( $ip_key );
		$per_ip = is_array( $per_ip ) ? $per_ip : array( 'count' => 0, 'mobiles' => array(), 'start' => $now );

		if ( ( $now - (int) $per_ip['start'] ) >= HOUR_IN_SECONDS ) {
			$per_ip = array( 'count' => 0, 'mobiles' => array(), 'start' => $now );
		}

		$mobiles = is_array( $per_ip['mobiles'] ) ? $per_ip['mobiles'] : array();
		$digest  = substr( hash( 'sha256', $mobile ), 0, 16 );

		if ( (int) $per_ip['count'] >= self::MAX_PER_IP ) {
			$this->log( 'سقف ساعتی پیامک برای یک نشانی پر شد', 'warning' );

			return self::msg( 'too_many_requests', 'تعداد درخواست‌ها زیاد است. یک ساعت دیگر تلاش کنید.' );
		}

		if ( ! in_array( $digest, $mobiles, true ) && count( $mobiles ) >= self::MAX_MOBILES_PER_IP ) {
			$this->log( 'چرخاندن شماره از یک نشانی', 'error', array( 'seen' => count( $mobiles ) ) );

			return self::msg( 'too_many_requests', 'تعداد درخواست‌ها زیاد است. یک ساعت دیگر تلاش کنید.' );
		}

		/* ── لایۀ اول: خود شماره ── */
		$m_key = 'tp_otp_rl_' . hash( 'sha256', $mobile . wp_salt( 'nonce' ) );
		$per_m = get_transient( $m_key );
		$per_m = is_array( $per_m ) ? $per_m : array( 'count' => 0, 'last' => 0, 'start' => $now );

		if ( ( $now - (int) $per_m['start'] ) >= HOUR_IN_SECONDS ) {
			$per_m = array( 'count' => 0, 'last' => 0, 'start' => $now );
		}

		if ( (int) $per_m['count'] >= self::MAX_PER_MOBILE ) {
			return self::msg( 'number_rate_limited', 'برای این شماره بیش از حد درخواست شده. یک ساعت دیگر تلاش کنید.' );
		}

		if ( $per_m['last'] && ( $now - (int) $per_m['last'] ) < self::COOLDOWN ) {
			$wait = self::COOLDOWN - ( $now - (int) $per_m['last'] );

			/* translators: %s: seconds left */
			return sprintf( self::msg( 'wait_seconds', '%s ثانیه دیگر می‌توانید دوباره درخواست بدهید.' ), (string) $wait );
		}

		/* ── ثبت مصرف ── */
		$per_m['count'] = (int) $per_m['count'] + 1;
		$per_m['last']  = $now;
		set_transient( $m_key, $per_m, HOUR_IN_SECONDS );

		$per_ip['count'] = (int) $per_ip['count'] + 1;

		if ( ! in_array( $digest, $mobiles, true ) ) {
			$mobiles[] = $digest;
		}

		$per_ip['mobiles'] = $mobiles;
		set_transient( $ip_key, $per_ip, HOUR_IN_SECONDS );

		$global['count'] = (int) $global['count'] + 1;
		set_transient( $g_key, $global, HOUR_IN_SECONDS );

		return '';
	}

	/**
	 * پس‌دادن مصرف هر سه لایه وقتی پیامک از سمت مدیانا نرفته.
	 *
	 * مسئله: شمارنده‌ها پیش از فراخوانی مدیانا مصرف می‌شوند (و این
	 * درست است — فلسفۀ fail-closed). ولی اگر مدیانا خطا بدهد، هیچ
	 * پیامکی نرفته ولی یکی از پنج سهمیۀ ساعتی کاربر سوخته. با قطعی
	 * ۵ دقیقه‌ای درگاه، کاربر برای یک ساعت به طور کامل قفل می‌شد —
	 * خطایی که تقصیر او نبود.
	 *
	 * نکتۀ امنیتی مهم: فقط count برمی‌گردد؛ `last` روی همین لحطه می‌ماند.
	 * پس سردشدنِ ۴۵ ثانیه‌ای همچنان برقرار است و نمی‌توان با خراب‌کردن
	 * عمدی درخواست، سقف را بی‌نهایت دور زد. مهاجم در بدترین حالت به
	 * یک درخواست هر ۴۵ ثانیه محدود می‌ماند، و سقف کل سایت دست‌نخورده
	 * پشت سرش ایستاده.
	 */
	private function refund_rate_limit( string $mobile ): void {

		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			return;
		}

		/* لایۀ اول: خود شماره */
		$m_key = 'tp_otp_rl_' . hash( 'sha256', $mobile . wp_salt( 'nonce' ) );
		$per_m = get_transient( $m_key );

		if ( is_array( $per_m ) && (int) $per_m['count'] > 0 ) {
			$per_m['count'] = (int) $per_m['count'] - 1;
			// « last » عمداً دست‌نخورده می‌ماند تا سردشدن باقی بماند.
			set_transient( $m_key, $per_m, HOUR_IN_SECONDS );
		}

		/* لایۀ دوم: نشانی. فهرست mobiles پاک نمی‌شود — چرخاندن شماره
		   همچنان دیده می‌شود. */
		$ip_key = 'tp_otp_ip_' . $this->ip_hash();
		$per_ip = get_transient( $ip_key );

		if ( is_array( $per_ip ) && (int) $per_ip['count'] > 0 ) {
			$per_ip['count'] = (int) $per_ip['count'] - 1;
			set_transient( $ip_key, $per_ip, HOUR_IN_SECONDS );
		}

		/* لایۀ سوم: کل سایت */
		$g_key  = 'tp_otp_global';
		$global = get_transient( $g_key );

		if ( is_array( $global ) && (int) $global['count'] > 0 ) {
			$global['count'] = (int) $global['count'] - 1;
			set_transient( $g_key, $global, HOUR_IN_SECONDS );
		}
	}

	private function find_user_by_mobile( string $mobile ): ?WP_User {
		$users = get_users(
			array(
				'meta_key'    => '_topersia_mobile',
				'meta_value'  => $mobile,
				'number'      => 1,
				'count_total' => false,
				'fields'      => 'all',
			)
		);

		return $users ? $users[0] : null;
	}

	/**
	 * آیا این حساب اجازه دارد با کد یک‌بارمصرف وارد شود؟
	 *
	 * حسابی که می‌تواند محتوا بسازد یا تنظیمات را عوض کند، نباید از مسیری
	 * وارد شود که امنیتش به یک کد شش‌رقمی پیامکی بند است.
	 *
	 * @param WP_User $user کاربر.
	 * @return bool
	 */
	private function may_use_otp( WP_User $user ): bool {
		return ! self::privileged( $user );
	}

	/** حساب دارای دسترسی — از هر دو کانال کد رد می‌شود؛ بازنشانی گذرواژه فقط برای همین‌ها (ToPersia_Mailer). */
	public static function privileged( WP_User $user ): bool {
		foreach ( array( 'manage_options', 'edit_posts', 'edit_pages', 'upload_files' ) as $cap ) {
			if ( user_can( $user, $cap ) ) {
				return true;
			}
		}

		return false;
	}

	/* ==================================================================
	   ارسال کد
	   ================================================================== */

	public function send_otp(): void {

		if ( ! $this->nonce_ok() ) {
			wp_send_json_error( array( 'message' => self::msg( 'invalid_request', 'درخواست نامعتبر است.' ) ), 403 );
		}

		$raw    = isset( $_POST['mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['mobile'] ) ) : '';
		$mobile = $this->normalize_mobile( $raw );

		if ( ! $this->valid_mobile( $mobile ) ) {
			wp_send_json_error( array( 'message' => self::msg( 'invalid_mobile', 'شماره موبایل معتبر نیست.' ) ), 422 );
		}

		/* ۱٫۱۴٫۰ — پیوند شمارۀ دوم به حساب واردشده (LOGIN.md §۲-پ) */
		$purpose = isset( $_POST['purpose'] ) && 'link' === sanitize_key( wp_unslash( $_POST['purpose'] ) ) ? 'link' : 'login';
		$uid     = get_current_user_id();

		if ( 'link' === $purpose ) {

			if ( $uid <= 0 ) {
				wp_send_json_error( array( 'message' => self::msg( 'login_required', 'برای ادامه وارد حساب شوید.' ) ), 401 );
			}

			if ( self::privileged( wp_get_current_user() ) ) {
				wp_send_json_error( array( 'message' => self::msg( 'use_normal_login', 'این حساب باید از فرم ورود عادی وارد شود.' ) ), 403 );
			}

			if ( '' !== (string) get_user_meta( $uid, '_topersia_mobile', true ) && self::change_too_soon( $uid, '_topersia_mobile_verified' ) ) {
				wp_send_json_error( array( 'message' => self::msg( 'identity_cooldown', 'این شناسه کمتر از ۳۰ روز پیش تغییر کرده است. بعداً دوباره تلاش کنید.' ) ), 429 );
			}
		}

		/*
		 * ۱٫۱۴٫۰ · P5-D391 بند ۳ — پاسخ یکسان (LOGIN.md §۳-ب): شمارۀ حساب دارای دسترسی، یا شماره‌ای که در پیوند مال
		 * حساب دیگری است، همان پاسخ «کد فرستاده شد» و همان مصرف سهمیه را می‌گیرد — ولی پیامکی نمی‌رود و توکنش با
		 * هش بی‌جفت است، پس بررسی هرگز موفق نمی‌شود. تا ۱٫۱۳ این‌جا ۴۰۳ «ورود عادی» بود: شمارش حساب‌های مدیر.
		 */
		$existing = $this->find_user_by_mobile( $mobile );
		$silent   = ( 'login' === $purpose && $existing && ! $this->may_use_otp( $existing ) )
			|| ( 'link' === $purpose && $existing && (int) $existing->ID !== $uid );

		$blocked = $this->rate_limited( $mobile );

		if ( '' !== $blocked ) {
			wp_send_json_error( array( 'message' => $blocked ), 429 );
		}

		$token = wp_generate_password( 32, false, false );

		/*
		 * ۱٫۱۴٫۱ · زمان پاسخ یکسان (دستۀ ۲۳): شاخۀ «بی پیامک» (حساب دارای دسترسی یا شمارۀ مال حساب دیگر) همان کارهای شاخۀ
		 * عادی را می‌کند — ساخت کد، هر دو هش، رد پیامک (تأخیر همسان با میانگین تأخیر واقعی مدیانا)، ردیف سابقۀ تلاش و
		 * نوشتن ترنزینت (با هش بی‌جفت) — و فقط پیامک نمی‌فرستد؛ به‌علاوه تأخیر تصادفی کوچک روی هر پاسخ موفق.
		 */
		$otp  = self::new_code();
		$real = self::code_hash( $token, $otp );
		$dumm = self::dummy_hash();
		$hash = $silent ? $dumm : $real;

		if ( $silent ) {
			$this->log( 'login' === $purpose ? 'درخواست کد برای حساب دارای دسترسی — پیامکی نرفت' : 'پیوند شماره‌ای که مال حساب دیگری است — پیامکی نرفت', 'error', array( 'user' => (int) $existing->ID ) );
			self::mimic_provider_latency();
			$result = array( 'success' => true, 'message' => '', 'request_id' => '' );
		} else {
			$t0       = microtime( true );
			$provider = new ToPersia_Mediana_SMS();
			$result   = $provider->send_otp( $mobile, $otp, (string) get_option( 'topersia_mediana_pattern_code', '' ) );
			if ( $result['success'] ) {
				self::note_provider_latency( ( microtime( true ) - $t0 ) * 1000 );
			}
		}

		if ( ! $result['success'] ) {
			$this->log( 'ارسال پیامک ناموفق: ' . $result['message'], 'error' );

			/* پیامکی نرفته، پس سهمیه نباید مصرف بماند. سردشدنِ ۴۵
			   ثانیه‌ای سر جایش می‌ماند. */
			$this->refund_rate_limit( $mobile );

			wp_send_json_error( array( 'message' => $result['message'] ), 502 );
		}

		$expires    = gmdate( 'Y-m-d H:i:s', time() + self::TTL );
		$attempt_id = ToPersia_DB::create_attempt( $mobile, 'login', $expires, (string) $result['request_id'], $this->ip_hash() );

		/* شمارندۀ حدس داخل همین ترنزینت است، نه جای دیگر. اگر این نباشد
		   اصلاً کدی برای بررسی وجود ندارد، پس سقف هرگز دور زده نمی‌شود. */
		set_transient(
			'tp_otp_' . $token,
			/* شمارۀ متن ساده در ذخیره نمی‌ماند (نه جدول، نه ترنزینت)؛ تطبیق با هش، و شمارۀ ورودیِ مرحلۀ بررسی. P5-D375 */
			array(
				'mobile_h'   => ToPersia_DB::mobile_hash( $mobile ),
				'hash'       => $hash,
				'tries'      => 0,
				'attempt_id' => $attempt_id,
				'purpose'    => $purpose,
				'uid'        => $uid,
			),
			self::TTL
		);

		self::jitter();

		wp_send_json_success(
			array(
				'token'     => $token,
				'expiresIn' => self::TTL,
				'message'   => self::msg( 'otp_sent', 'کد تأیید ارسال شد.' ),
			)
		);
	}

	/** تأخیر تصادفی کوچک (۰ تا ۴۰ms) روی هر پاسخ موفق — نوفۀ مشترک شاخه‌ها. */
	private static function jitter(): void {
		usleep( random_int( 0, 40000 ) );
	}

	/** میانگین متحرک تأخیر فراخوانی واقعی مدیانا (ms) — شاخۀ بی پیامک همین‌قدر معطل می‌شود. */
	private static function note_provider_latency( float $ms ): void {
		$old = get_transient( 'tp_sms_lat' );
		$ms  = min( 5000.0, max( 0.0, $ms ) );
		set_transient( 'tp_sms_lat', is_numeric( $old ) ? ( 0.7 * (float) $old + 0.3 * $ms ) : $ms, 6 * HOUR_IN_SECONDS );
	}

	private static function mimic_provider_latency(): void {
		$lat = get_transient( 'tp_sms_lat' );
		$ms  = is_numeric( $lat ) ? min( 5000.0, (float) $lat ) : 300.0;   /* هنوز پیامی نرفته: پیش‌فرض ۳۰۰ms */
		$end = microtime( true ) + $ms / 1000;

		/* usleep در ویندوز به گام ~۱۵ms گرد می‌شود و تأخیر واقعی (که همان را اندازه گرفته) را بی‌تقارن می‌کرد؛ بخش آخر با انتظار فعال */
		if ( $ms > 20 ) {
			usleep( (int) ( 1000 * ( $ms - 20 ) ) );
		}

		while ( microtime( true ) < $end ) {
			// انتظار فعال، حداکثر ~۲۰ms
		}
	}

	/* ==================================================================
	   بررسی کد
	   ================================================================== */

	public function verify_otp(): void {

		if ( ! $this->nonce_ok() ) {
			wp_send_json_error( array( 'message' => self::msg( 'invalid_request', 'درخواست نامعتبر است.' ) ), 403 );
		}

		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$otp   = isset( $_POST['otp'] ) ? preg_replace( '/\D/', '', (string) wp_unslash( $_POST['otp'] ) ) : '';

		if ( '' === $token || ! preg_match( '/^\d{6}$/', (string) $otp ) ) {
			wp_send_json_error( array( 'message' => self::msg( 'otp_invalid', 'کد تأیید معتبر نیست.' ) ), 422 );
		}

		$key     = 'tp_otp_' . $token;
		$payload = get_transient( $key );

		if ( ! is_array( $payload ) || empty( $payload['hash'] ) || ( empty( $payload['mobile_h'] ) && empty( $payload['mobile'] ) ) ) {
			wp_send_json_error( array( 'message' => self::msg( 'otp_expired', 'کد منقضی یا نامعتبر است.' ) ), 410 );
		}

		/*
		 * شمارۀ این مرحله از ورودی می‌آید و با هشِ ذخیره‌شده تطبیق می‌خورد؛ ترنزینت‌های پیش از ۱٫۱۲ هنوز شمارۀ متن ساده دارند
		 * (حداکثر چند دقیقه) و همان‌طور بررسی می‌شوند. P5-D375
		 */
		$mobile_in = $this->normalize_mobile( isset( $_POST['mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['mobile'] ) ) : '' );

		if ( ! empty( $payload['mobile_h'] ) ) {
			if ( ! $this->valid_mobile( $mobile_in ) || ! hash_equals( (string) $payload['mobile_h'], ToPersia_DB::mobile_hash( $mobile_in ) ) ) {
				wp_send_json_error( array( 'message' => self::msg( 'otp_invalid', 'کد تأیید معتبر نیست.' ) ), 422 );
			}
			$payload['mobile'] = $mobile_in;
		}

		/* سقف حدس — پیش از هر مقایسه‌ای، و شکست‌بسته. */
		$tries = isset( $payload['tries'] ) ? (int) $payload['tries'] : 0;

		if ( $tries >= self::MAX_TRIES ) {
			delete_transient( $key );
			$this->log( 'سقف حدس کد یک‌بارمصرف پر شد', 'error' );

			wp_send_json_error(
				array( 'message' => self::msg( 'too_many_attempts', 'تعداد تلاش‌ها بیش از حد مجاز شد. کد تازه بگیرید.' ) ),
				429
			);
		}

		$payload['tries'] = $tries + 1;
		$stored = $payload;
		unset( $stored['mobile'] );   /* شمارۀ ورودی همین درخواست است، نه چیزی برای ذخیره (ترنزینت پیشین هم با اولین حدس بی‌شماره می‌شود) */
		if ( empty( $stored['mobile_h'] ) && ! empty( $payload['mobile'] ) ) {
			$stored['mobile_h'] = ToPersia_DB::mobile_hash( (string) $payload['mobile'] );
		}
		set_transient( $key, $stored, self::TTL );

		$attempt_id = isset( $payload['attempt_id'] ) ? absint( $payload['attempt_id'] ) : 0;

		if ( $attempt_id ) {
			ToPersia_DB::increment_attempt( $attempt_id );
		}

		if ( ! self::code_matches( (string) $payload['hash'], $token, (string) $otp ) ) {
			wp_send_json_error(
				array(
					'message' => self::msg( 'otp_wrong', 'کد تأیید اشتباه است.' ),
					'left'    => max( 0, self::MAX_TRIES - $payload['tries'] ),
				),
				422
			);
		}

		/* ۱٫۱۴٫۰ — پیوند شمارۀ دوم: فقط همان حساب واردشده‌ای که کد را خواست */
		if ( isset( $payload['purpose'] ) && 'link' === $payload['purpose'] ) {
			$this->attach_mobile( (int) ( $payload['uid'] ?? 0 ), (string) $payload['mobile'], $key, $attempt_id );
		}

		$user = $this->find_user_by_mobile( (string) $payload['mobile'] );

		/* دروازۀ دوم: اگر میان ارسال و بررسی، شماره به حسابی با دسترسی
		   وصل شده باشد. */
		if ( $user && ! $this->may_use_otp( $user ) ) {
			delete_transient( $key );
			$this->log( 'ورود حساب دارای دسترسی با کد یک‌بارمصرف رد شد', 'error', array( 'user' => $user->ID ) );

			wp_send_json_error(
				array( 'message' => self::msg( 'use_normal_login', 'این حساب باید از فرم ورود عادی وارد شود.' ) ),
				403
			);
		}

		if ( $attempt_id ) {
			ToPersia_DB::mark_verified( $attempt_id );
		}

		if ( ! $user ) {
			/* قفل یکتایی: دو درخواست هم‌زمان برای یک شمارۀ نو یک حساب می‌سازند، نه دو (LOGIN.md §۱-ت) */
			$locked = self::lock( 'm:' . (string) $payload['mobile'] );
			$user   = $this->find_user_by_mobile( (string) $payload['mobile'] );
			$user   = $user ?: $this->create_user( (string) $payload['mobile'] );
			if ( $locked ) {
				self::unlock( 'm:' . (string) $payload['mobile'] );
			}

			if ( ! $user ) {
				wp_send_json_error( array( 'message' => self::msg( 'account_create_failed', 'ساخت حساب کاربری انجام نشد.' ) ), 500 );
			}
		}

		delete_transient( $key );

		$remember = ! empty( $_POST['remember'] );

		self::start_session( $user, $remember );

		$this->log( 'ورود با کد یک‌بارمصرف', 'info', array( 'user' => $user->ID ) );

		/* بازگشت به همان صفحه‌ای که ورود از آن خواسته شد.  ۱٫۱۰٫۰ */
		$back = isset( $_POST['redirect'] ) ? (string) wp_unslash( $_POST['redirect'] ) : '';

		wp_send_json_success( array( 'redirect' => self::safe_redirect( $back ) ) );
	}

	/**
	 * ساخت حساب تازه با نقش مشترک.
	 *
	 * نقش صریح داده می‌شود؛ اگر default_role سایت روزی عوض شود، کاربر تازه
	 * نباید ناخواسته دسترسی بیشتری بگیرد.
	 *
	 * ۱٫۱۱٫۰ · P5-D354 — شماره هرگز در شناسۀ عمومی: نام ورود، نامک و ایمیل
	 * تصادفی‌اند و نام نمایشی و nickname خنثی («مسافر ۴۸۲۱»). شماره فقط در
	 * متای `_topersia_mobile` — همان که `find_user_by_mobile()` می‌خواند؛
	 * پیداکردن حساب به نام ورود وابسته نیست.
	 *
	 * @param string $mobile شماره.
	 * @return WP_User|null
	 */
	private function create_user( string $mobile ): ?WP_User {

		if ( ! class_exists( 'ToPersia_Privacy' ) ) {
			$this->log( 'ساخت حساب ناموفق: کلاس حریم خصوصی بار نشده', 'error' );

			return null;
		}

		$user_id = wp_create_user( ToPersia_Privacy::random_login(), wp_generate_password( 32, true, true ), ToPersia_Privacy::random_email() );

		if ( is_wp_error( $user_id ) ) {
			$this->log( 'ساخت حساب ناموفق: ' . $user_id->get_error_message(), 'error' );

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

		update_user_meta( $user_id, '_topersia_mobile', $mobile );
		update_user_meta( $user_id, '_topersia_mobile_verified', time() );

		return $user;
	}

	/**
	 * پیوند شمارۀ تأییدشده به حساب واردشده — ۱٫۱۴٫۰ · LOGIN.md §۲-پ و §۲-ت.
	 *
	 * فقط همان حسابی که کد را خواست؛ شماره‌ای که مال حساب دیگری است هرگز پیوند نمی‌خورد (ادغام نه — فقط دستی با
	 * پشتیبانی) و پاسخش همان «کد نامعتبر» است. فاصلۀ ۳۰ روز میان دو تغییر. همیشه با wp_send_json پایان می‌یابد.
	 */
	private function attach_mobile( int $uid, string $mobile, string $key, int $attempt_id ): void {

		if ( $uid <= 0 || get_current_user_id() !== $uid ) {
			delete_transient( $key );
			wp_send_json_error( array( 'message' => self::msg( 'invalid_request', 'درخواست نامعتبر است.' ) ), 403 );
		}

		$locked = self::lock( 'm:' . $mobile );
		$owner  = $this->find_user_by_mobile( $mobile );
		$had    = (string) get_user_meta( $uid, '_topersia_mobile', true );

		if ( ( $owner && (int) $owner->ID !== $uid ) || ( '' !== $had && $had !== $mobile && self::change_too_soon( $uid, '_topersia_mobile_verified' ) ) ) {
			if ( $locked ) {
				self::unlock( 'm:' . $mobile );
			}
			delete_transient( $key );
			wp_send_json_error( array( 'message' => self::msg( 'otp_invalid', 'کد تأیید معتبر نیست.' ) ), 422 );
		}

		update_user_meta( $uid, '_topersia_mobile', $mobile );
		update_user_meta( $uid, '_topersia_mobile_verified', time() );

		if ( $locked ) {
			self::unlock( 'm:' . $mobile );
		}

		delete_transient( $key );

		if ( $attempt_id ) {
			ToPersia_DB::mark_verified( $attempt_id );
		}

		/* اعلان امنیتی به ایمیل واقعی حساب، اگر هست (پیامک اعلان الگوی جدا در مدیانا ندارد) */
		if ( '' !== $had && $had !== $mobile && class_exists( 'ToPersia_Email_Code' ) ) {
			ToPersia_Email_Code::notify_change( $uid, 'mobile' );
		}

		$this->log( '' === $had ? 'شمارۀ موبایل به حساب افزوده شد' : 'شمارۀ موبایل حساب عوض شد', 'info', array( 'user' => $uid ) );

		wp_send_json_success( array( 'message' => self::msg( 'identity_saved', 'شناسه تأیید و ذخیره شد.' ), 'reload' => true ) );
	}

	/* ==================================================================
	   خروج و فرم
	   ================================================================== */

	public function logout(): void {

		if ( ! $this->nonce_ok() ) {
			wp_send_json_error( array( 'message' => self::msg( 'invalid_request', 'درخواست نامعتبر است.' ) ), 403 );
		}

		wp_logout();

		wp_send_json_success( array( 'redirect' => home_url( '/' ) ) );
	}

	public function login_shortcode(): string {

		if ( is_user_logged_in() ) {
			return '<div class="tp-auth-card"><p>'
				. esc_html( self::msg( 'already_logged_in', 'شما وارد شده‌اید.' ) )
				. '</p><a class="tp-btn" href="' . esc_url( ToPersia_Admin::account_url() ) . '">'
				. esc_html( self::msg( 'go_to_account', 'رفتن به حساب کاربری' ) )
				. '</a></div>';
		}

		/* پیوندِ «ورود/ثبت‌نام» صفحۀ موجودیت `redirect_to` می‌فرستد. */
		$tp_redirect = isset( $_GET['redirect_to'] ) ? self::safe_redirect( (string) wp_unslash( $_GET['redirect_to'] ) ) : '';

		ob_start();
		require TOPERSIA_USER_CORE_DIR . 'templates/login.php';

		return (string) ob_get_clean();
	}

	/**
	 * «پنجرۀ ورود/ثبت‌نام» — برای مهمان، روی صفحه‌هایی که دارایی‌های این
	 * افزونه بار شده‌اند.  ۱٫۱۰٫۰ · P5-D237 · P5-D238
	 *
	 * دکمه‌های ذخیره، دنبال‌کردن، نظر و هوش مصنوعیِ «هستۀ نمایش» برای
	 * مهمان همین را باز می‌کنند (رویداد `topersia:login-required`). روی
	 * برگۀ حساب چاپ نمی‌شود: فرم آن‌جا خودش هست و شناسه‌ها دوتایی می‌شدند.
	 */
	public function login_modal(): void {

		if ( is_user_logged_in() || ! function_exists( 'wp_script_is' ) || ! wp_script_is( 'topersia-user-core', 'enqueued' ) ) {
			return;
		}

		if ( class_exists( 'ToPersia_Admin' ) ) {

			$account = ToPersia_Admin::account_page_id_canonical();
			$current = (int) get_queried_object_id();

			if ( $account > 0 && $current > 0 ) {

				$default = apply_filters( 'wpml_default_language', null );

				if ( is_string( $default ) && '' !== $default ) {
					$mapped  = apply_filters( 'wpml_object_id', $current, 'page', true, $default );
					$current = is_numeric( $mapped ) && (int) $mapped > 0 ? (int) $mapped : $current;
				}

				if ( $current === $account ) {
					return;
				}
			}
		}

		$t = static function ( string $key ): string {
			return esc_html( ToPersia_I18n::t( $key ) );
		};

		echo '<div class="tp-uc-login" id="tp-uc-login" role="dialog" aria-modal="true" aria-labelledby="tp-uc-login-title" hidden>'
			. '<div class="tp-uc-login__box">'
			. '<button type="button" class="tp-uc-login__close" data-tp-uc-close aria-label="' . $t( 'close' ) . '">×</button>'
			. '<h2 id="tp-uc-login-title">' . $t( 'login_title' ) . '</h2>'
			. '<p class="tp-uc-login__reason" data-tp-uc-reason></p>'
			. '<p class="tp-muted">' . $t( 'login_intro' ) . '</p>';

		$tp_redirect = '';
		$tp_compact  = true;
		require TOPERSIA_USER_CORE_DIR . 'templates/login.php';

		echo '</div></div>';
	}
}
