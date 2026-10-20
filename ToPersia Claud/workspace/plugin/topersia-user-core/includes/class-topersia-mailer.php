<?php
/**
 * ToPersia User Core — ارسال ایمیل  ۱٫۱۴٫۰ · P5-D391
 *
 * همۀ ایمیل‌های این افزونه از همین کلاس می‌روند. فروشنده = SMTP میزبان (cPanel) با ثابت‌های wp-config:
 *
 *   TOPERSIA_SMTP_HOST · TOPERSIA_SMTP_PORT · TOPERSIA_SMTP_SECURE (ssl|tls) · TOPERSIA_SMTP_USER · TOPERSIA_SMTP_PASS
 *   TOPERSIA_MAIL_FROM · TOPERSIA_MAIL_HOURLY_CAP (اختیاری)
 *
 * تعویض فروشنده = تغییر همین ثابت‌ها، نه کد. گذرواژه هرگز در option، HTML، لاگ یا پاسخ (درس P5-D249/D355).
 * SPF، DKIM و DMARC کار cPanel است (docs/design/LOGIN.md §۵)، نه این کد.
 *
 * ── قاعده‌ها ─────────────────────────────────────────────────────────────
 * ۱. `phpmailer_init` فقط در طول ارسال یک ایمیل ToPersia بسته می‌شود و بلافاصله برداشته می‌شود؛ ایمیل‌های دیگر
 *    وردپرس و افزونه‌ها دست نمی‌خورند.
 * ۲. اگر هر ثابت SMTP تعریف نشده (نسخۀ محلی) **هیچ ارسالی نیست** — نه wp_mail، نه mail(). ایمیل فقط ثبت می‌شود:
 *    لاگ کانال `mail` با HMAC گیرنده و هش متن (هرگز نشانی، کد یا متن خام)، و صندوق آزمون درون حافظه برای CLI.
 * ۳. سقف سایت: ساعتی = TOPERSIA_MAIL_HOURLY_CAP × ۸۰٪ و روزانه = همان × ۲۴ × ۸۰٪ (P5-D391: ≤ ۸۰٪ سقف میزبان)؛
 *    تا سقف میزبان معلوم نشده ۵۰ در ساعت و ۲۰۰ در روز. شکست‌بسته: بی ذخیره‌سازی، ارسال نه.
 * ۴. نامه به دامنۀ ساختگی حساب‌های پیامکی (`users.topersia.com`) — از هر افزونه‌ای — بی‌صدا دور ریخته می‌شود؛
 *    لاگ فقط شمار (LOGIN.md §۱-ث).
 * ۵. بازنشانی گذرواژه برای حساب‌هایی که با کد وارد می‌شوند خاموش (P5-D391 بند ۳).
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Mailer {

	/** ثابت‌هایی که بی هر کدام ارسال واقعی نیست. */
	public const REQUIRED = array( 'TOPERSIA_SMTP_HOST', 'TOPERSIA_SMTP_PORT', 'TOPERSIA_SMTP_SECURE', 'TOPERSIA_SMTP_USER', 'TOPERSIA_SMTP_PASS', 'TOPERSIA_MAIL_FROM' );

	/** سقف پیش‌فرض تا سقف واقعی میزبان معلوم شود (P5-D391؛ دستۀ ۲۱). */
	private const DEFAULT_HOURLY = 50;
	private const DEFAULT_DAILY  = 200;

	/** سهم سایت از سقف میزبان. */
	private const SHARE = 0.8;

	/** @var array<int,array<string,string>> صف ارسال پس از پاسخ */
	private static array $queue = array();

	/** @var array<int,array<string,string>> صندوق آزمون — فقط CLI، فقط وقتی ارسال واقعی خاموش است */
	public static array $outbox = array();

	private static bool $flushing = false;

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_filter( 'pre_wp_mail', array( __CLASS__, 'drop_synthetic' ), 5, 2 );
		add_filter( 'allow_password_reset', array( __CLASS__, 'allow_password_reset' ), 20, 2 );
	}

	/* ── پیکربندی ─────────────────────────────────────────────────────── */

	public static function configured(): bool {
		foreach ( self::REQUIRED as $c ) {
			if ( ! defined( $c ) || '' === trim( (string) constant( $c ) ) ) {
				return false;
			}
		}
		return true;
	}

	/** @return array{hour:int,day:int} */
	public static function caps(): array {
		if ( defined( 'TOPERSIA_MAIL_HOURLY_CAP' ) && (int) TOPERSIA_MAIL_HOURLY_CAP > 0 ) {
			$host = (int) TOPERSIA_MAIL_HOURLY_CAP;
			$caps = array( 'hour' => max( 1, (int) floor( $host * self::SHARE ) ), 'day' => max( 1, (int) floor( $host * 24 * self::SHARE ) ) );
		} else {
			$caps = array( 'hour' => self::DEFAULT_HOURLY, 'day' => self::DEFAULT_DAILY );
		}
		$f = function_exists( 'apply_filters' ) ? apply_filters( 'topersia_mail_caps', $caps ) : $caps;
		/* صافی فقط می‌تواند سخت‌تر کند — هرگز بالاتر از سقف محاسبه‌شده */
		return array(
			'hour' => max( 0, min( $caps['hour'], (int) ( $f['hour'] ?? $caps['hour'] ) ) ),
			'day'  => max( 0, min( $caps['day'], (int) ( $f['day'] ?? $caps['day'] ) ) ),
		);
	}

	/**
	 * یک سهم از سقف سایت — پیش از پاسخ گرفته می‌شود تا پاسخ «بعداً» برای همه یکسان باشد.
	 * شکست‌بسته: اگر ذخیره‌سازی نیست، false.
	 */
	public static function reserve(): bool {
		if ( ! function_exists( 'get_transient' ) || ! function_exists( 'set_transient' ) ) {
			return false;
		}
		$caps = self::caps();
		$now  = time();
		$h    = get_transient( 'tp_mail_cap_h' );
		$h    = is_array( $h ) && ( $now - (int) $h['start'] ) < HOUR_IN_SECONDS ? $h : array( 'count' => 0, 'start' => $now );
		$d    = get_transient( 'tp_mail_cap_d' );
		$d    = is_array( $d ) && ( $now - (int) $d['start'] ) < DAY_IN_SECONDS ? $d : array( 'count' => 0, 'start' => $now );

		if ( (int) $h['count'] >= $caps['hour'] || (int) $d['count'] >= $caps['day'] ) {
			self::log( 'سقف ارسال ایمیل سایت پر شد', 'error', array( 'hour' => (int) $h['count'], 'day' => (int) $d['count'], 'cap_hour' => $caps['hour'], 'cap_day' => $caps['day'] ) );
			return false;
		}
		$h['count'] = (int) $h['count'] + 1;
		$d['count'] = (int) $d['count'] + 1;
		set_transient( 'tp_mail_cap_h', $h, HOUR_IN_SECONDS );
		set_transient( 'tp_mail_cap_d', $d, DAY_IN_SECONDS );
		return true;
	}

	/* ── ارسال ────────────────────────────────────────────────────────── */

	/**
	 * در صف پس از پاسخ — زمان پاسخ برای حساب‌دار و بی‌حساب یکی است (LOGIN.md §۳-ب).
	 *
	 * @param array{to:string,subject:string,text:string,html:string,kind:string,lang:string} $m
	 */
	public static function queue( array $m ): void {
		self::$queue[] = $m;
		if ( 1 === count( self::$queue ) ) {
			register_shutdown_function( array( __CLASS__, 'flush' ) );
		}
	}

	/** صف را می‌فرستد؛ در FPM پیش از آن پاسخ را می‌بندد. */
	public static function flush(): int {
		if ( self::$flushing || ! self::$queue ) {
			return 0;
		}
		self::$flushing = true;
		if ( function_exists( 'fastcgi_finish_request' ) && 'cli' !== PHP_SAPI ) {
			fastcgi_finish_request();
		}
		$n = 0;
		while ( self::$queue ) {
			$n += self::send( array_shift( self::$queue ) ) ? 1 : 0;
		}
		self::$flushing = false;
		return $n;
	}

	/**
	 * یک ایمیل. بی پیکربندی کامل: هیچ ارسال — فقط ثبت (هش) و صندوق آزمون CLI.
	 *
	 * @param array{to:string,subject:string,text:string,html:string,kind:string,lang:string} $m
	 */
	public static function send( array $m ): bool {
		$to = strtolower( trim( (string) $m['to'] ) );

		if ( class_exists( 'ToPersia_Privacy' ) && ToPersia_Privacy::synthetic_email( $to ) ) {
			self::log( 'ایمیل به نشانی ساختگی فرستاده نشد', 'warning', array( 'kind' => (string) $m['kind'] ) );
			return false;
		}

		if ( ! self::configured() ) {
			self::capture( $m, $to );
			return true;
		}

		$hook = static function ( $phpmailer ) use ( $m ) {
			$phpmailer->isSMTP();
			$phpmailer->Host       = (string) TOPERSIA_SMTP_HOST;
			$phpmailer->Port       = (int) TOPERSIA_SMTP_PORT;
			$phpmailer->SMTPSecure = 'tls' === strtolower( (string) TOPERSIA_SMTP_SECURE ) ? 'tls' : 'ssl';
			$phpmailer->SMTPAuth   = true;
			$phpmailer->Username   = (string) TOPERSIA_SMTP_USER;
			$phpmailer->Password   = (string) TOPERSIA_SMTP_PASS;
			$phpmailer->Timeout    = 15;
			$phpmailer->CharSet    = 'UTF-8';
			$phpmailer->setFrom( (string) TOPERSIA_MAIL_FROM, 'ToPersia', false );
			$phpmailer->Sender     = (string) TOPERSIA_MAIL_FROM;
			$phpmailer->AltBody    = (string) $m['text'];
			$phpmailer->XMailer    = ' ';
			$domain                = substr( strrchr( (string) TOPERSIA_MAIL_FROM, '@' ), 1 );
			$phpmailer->MessageID  = '<' . bin2hex( random_bytes( 16 ) ) . '@' . $domain . '>';
			$phpmailer->clearReplyTos();
			$phpmailer->addCustomHeader( 'Auto-Submitted', 'auto-generated' );
			$phpmailer->addCustomHeader( 'X-Auto-Response-Suppress', 'All' );
		};

		$fail = static function () {
			$GLOBALS['tp_mail_failed'] = true;
		};

		$GLOBALS['tp_mail_failed'] = false;
		add_action( 'phpmailer_init', $hook, 999 );
		add_action( 'wp_mail_failed', $fail );
		$ok = wp_mail( $to, (string) $m['subject'], (string) $m['html'], array( 'Content-Type: text/html; charset=UTF-8' ) );
		remove_action( 'phpmailer_init', $hook, 999 );
		remove_action( 'wp_mail_failed', $fail );

		$ok = $ok && empty( $GLOBALS['tp_mail_failed'] );
		self::log( $ok ? 'ایمیل فرستاده شد' : 'ارسال ایمیل ناموفق', $ok ? 'info' : 'error', array( 'kind' => (string) $m['kind'], 'to_h' => self::hmac( $to ), 'lang' => (string) $m['lang'] ) );

		return $ok;
	}

	/** بی ارسال: لاگ بی دادۀ خام، و صندوق آزمون فقط در CLI. */
	private static function capture( array $m, string $to ): void {
		self::log( 'ایمیل ثبت شد، فرستاده نشد (ثابت‌های SMTP تعریف نشده)', 'info', array(
			'kind'   => (string) $m['kind'],
			'lang'   => (string) $m['lang'],
			'to_h'   => self::hmac( $to ),
			'body_h' => substr( hash( 'sha256', (string) $m['text'] ), 0, 16 ),
		) );
		if ( 'cli' === PHP_SAPI ) {
			self::$outbox[] = $m + array( 'to' => $to );
		}
	}

	/* ── صافی‌های وردپرس ─────────────────────────────────────────────── */

	/**
	 * `pre_wp_mail`: نامه‌ای که هر گیرنده‌اش روی دامنۀ ساختگی است، از هر افزونه‌ای، فرستاده نمی‌شود.
	 *
	 * @param null|bool $short
	 * @param array     $atts
	 * @return null|bool
	 */
	public static function drop_synthetic( $short, $atts ) {
		if ( null !== $short || ! class_exists( 'ToPersia_Privacy' ) ) {
			return $short;
		}
		$to = $atts['to'] ?? array();
		$to = is_array( $to ) ? $to : explode( ',', (string) $to );
		foreach ( $to as $one ) {
			$addr = trim( (string) $one );
			if ( preg_match( '/<([^>]+)>/', $addr, $mm ) ) {
				$addr = $mm[1];
			}
			if ( '' !== $addr && ToPersia_Privacy::synthetic_email( $addr ) ) {
				self::log( 'نامه به دامنۀ ساختگی حساب‌ها دور ریخته شد', 'notice', array( 'count' => 1 ) );
				return false;
			}
		}
		return $short;
	}

	/**
	 * بازنشانی گذرواژه فقط برای حساب‌های دارای دسترسی — حساب کدی گذرواژه‌ای ندارد که بداند،
	 * و فرم lostpassword کانال سوم ورود می‌شد (LOGIN.md §۲-ث · P5-D391).
	 */
	public static function allow_password_reset( $allow, $user_id ) {
		$user = get_userdata( (int) $user_id );
		if ( $user instanceof WP_User && class_exists( 'ToPersia_Auth' ) && ! ToPersia_Auth::privileged( $user ) ) {
			return false;
		}
		return $allow;
	}

	/* ── کمکی ────────────────────────────────────────────────────────── */

	/** HMAC کوتاه برای هم‌بستگی در لاگ — هرگز نشانی خام. */
	public static function hmac( string $email ): string {
		return substr( hash_hmac( 'sha256', strtolower( trim( $email ) ), (string) wp_salt( 'auth' ) ), 0, 12 );
	}

	private static function log( string $msg, string $level, array $ctx = array() ): void {
		if ( function_exists( 'topersia_log' ) ) {
			topersia_log( $msg, $level, 'mail', $ctx );
		}
	}
}
