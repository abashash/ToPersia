<?php
/**
 * شمارۀ موبایل هرگز در شناسۀ عمومی کاربر.  ۱٫۱۱٫۰ · P5-D354 (R37)
 *
 * تا ۱٫۱۰٫۴ حساب پیامکی با `user_login = tp_{موبایل}` و ایمیل
 * `{موبایل}@users.topersia.com` ساخته می‌شد؛ وردپرس `display_name`،
 * `nickname` و `user_nicename` (نامک /author/…) را از همان نام ورود می‌سازد،
 * پس شماره در نام نویسندۀ نظر، نشانی نویسنده و هش gravatar (md5 ایمیل)
 * عمومی می‌شد.
 *
 *   حساب تازه    نام ورود، نامک و ایمیل تصادفی؛ نام نمایشی خنثی («مسافر ۴۸۲۱»).
 *                شماره فقط در متای `_topersia_mobile` — جست‌وجوی حساب هم از
 *                همان متاست (`ToPersia_Auth::find_user_by_mobile()`)، نه نام ورود.
 *   حساب موجود   مهاجرت یک‌باره و دسته‌ای هنگام ارتقا: نام نمایشی، nickname،
 *                نامک و ایمیلِ شماره‌دار. `user_login` موجود دست نمی‌خورد
 *                (وردپرس تغییرش را پشتیبانی نمی‌کند؛ با $wpdb هم عوض نمی‌شود).
 *                لاگ فقط شمار.
 *   دفاع دوم     فیلتر `get_the_author_display_name`: هر نام شبیه `tp_<رقم>` یا
 *                شمارۀ ایرانی (۰۹… · +98… · 0098… با رقم لاتین، فارسی و عربی)
 *                به نام خنثیِ زبان صفحه برمی‌گردد — بی تغییر در هستۀ نمایش.
 *
 * نام خنثی از کاتالوگ (`traveler_name`): «مسافر ۴۸۲۱» · «Traveler 4821» ·
 * «مسافر ٤٨٢١». عدد چهاررقمی از شناسۀ کاربر و salt سایت مشتق است — پایدار،
 * و نه شناسه را لو می‌دهد نه شماره را.
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Privacy {

	private static ?self $instance = null;

	/** نسخۀ مهاجرت انجام‌شده (بی autoload). */
	public const MIGRATION_OPTION = 'topersia_user_core_pii_migration';

	/** نشانگر پیشروی دسته‌ها: بزرگ‌ترین شناسۀ بررسی‌شده. */
	public const CURSOR_OPTION = 'topersia_user_core_pii_cursor';

	public const MIGRATION_VERSION = '1.11.0';

	public const BATCH = 100;

	/** جانشین اگر واژه‌نامه بار نشده باشد — همان مقدارهای `traveler_name`. */
	private const FALLBACK = array(
		'fa' => 'مسافر %s',
		'en' => 'Traveler %s',
		'ar' => 'مسافر %s',
	);

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_filter( 'get_the_author_display_name', array( $this, 'filter_author_name' ), 20, 2 );
		add_action( 'init', array( __CLASS__, 'maybe_migrate' ), 30 );
	}

	/* ── رقم ─────────────────────────────────────────────────────────── */

	/** رقم فارسی و عربی ← لاتین. */
	public static function latin_digits( string $s ): string {
		return strtr( $s, array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		) );
	}

	/** رقم لاتین ← رقم زبان صفحه. */
	public static function local_digits( string $s, string $lang ): string {
		if ( 'fa' === $lang ) {
			return strtr( $s, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
		}
		if ( 'ar' === $lang ) {
			return strtr( $s, array( '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩' ) );
		}
		return $s;
	}

	/* ── تشخیص ───────────────────────────────────────────────────────── */

	/**
	 * آیا این رشته شماره‌دار است: `tp_<رقم>` (نام ورود و نامک ۱٫۱۰) یا شمارۀ
	 * موبایل ایرانی با هر رقم و جداکننده‌ای (`0912…` · `+98 912…` · `0098912…` · `۰۹۱۲…`).
	 */
	public static function looks_like_phone( string $s ): bool {
		$s = self::latin_digits( trim( $s ) );

		if ( '' === $s ) {
			return false;
		}

		if ( preg_match( '/^tp[_-]?\d{4,}/i', $s ) ) {
			return true;
		}

		/* جداکننده‌های رایج شماره بیرون، تا «0912 123 4567» و «+98-912-…» هم گرفته شوند */
		$compact = preg_replace( '/[\s\-\.\(\)\x{200C}\x{200F}\x{200E}]+/u', '', $s );

		return (bool) preg_match( '/(?:^|\D)(?:\+98|0098|98|0)?9\d{9}(?:\D|$)/', (string) $compact );
	}

	/** آیا رشته نشانی ایمیل دارد؟ (هر جای رشته؛ «＠» تمام‌پهنا هم) — ۱٫۱۴٫۰ */
	public static function looks_like_email( string $s ): bool {
		return (bool) preg_match( '/[^\s@＠]+[@＠][^\s@＠]+\.[^\s@＠]{2,}/u', $s );
	}

	/** نام خنثیِ ساختۀ همین کلاس؟ (هر زبان، هر رقم) */
	public static function is_neutral( string $s ): bool {
		foreach ( array( 'fa', 'en', 'ar' ) as $lang ) {
			$re = '/^' . str_replace( '%s', '[0-9\x{06F0}-\x{06F9}\x{0660}-\x{0669}]{4}', preg_quote( self::template( $lang ), '/' ) ) . '$/u';
			if ( preg_match( $re, trim( $s ) ) ) {
				return true;
			}
		}
		return false;
	}

	/* ── نام خنثی ────────────────────────────────────────────────────── */

	/** قالب نام خنثی از کاتالوگ user-core، به زبان خواسته‌شده. */
	public static function template( string $lang ): string {
		$lang = in_array( $lang, array( 'fa', 'en', 'ar' ), true ) ? $lang : 'en';

		if ( class_exists( 'ToPersia_I18n' ) && method_exists( 'ToPersia_I18n', 'in' ) ) {
			$t = (string) ToPersia_I18n::in( 'traveler_name', $lang );
			if ( false !== strpos( $t, '%s' ) ) {
				return $t;
			}
		}

		return self::FALLBACK[ $lang ];
	}

	/** عدد چهاررقمی پایدار از شناسه — نه خودِ شناسه، نه شماره. */
	public static function number( int $user_id ): string {
		$salt = function_exists( 'wp_salt' ) ? (string) wp_salt( 'auth' ) : '';
		return (string) ( 1000 + ( abs( crc32( 'tp-traveler|' . $user_id . '|' . $salt ) ) % 9000 ) );
	}

	public static function neutral_name( int $user_id, string $lang = 'fa' ): string {
		return sprintf( self::template( $lang ), self::local_digits( self::number( $user_id ), $lang ) );
	}

	/** زبان صفحه، از همان دروازۀ واژه‌نامه. */
	public static function lang(): string {
		return ( class_exists( 'ToPersia_I18n' ) && method_exists( 'ToPersia_I18n', 'current_language' ) ) ? (string) ToPersia_I18n::current_language() : 'fa';
	}

	/**
	 * نامی که بیرون می‌رود: نام واقعی کاربر اگر هست و شماره‌دار نیست؛ وگرنه
	 * نام خنثی. نام خنثیِ ذخیره‌شده هم به زبان صفحه برگردانده می‌شود.
	 */
	public static function public_name( string $name, int $user_id, string $lang = '' ): string {
		$lang = '' !== $lang ? $lang : self::lang();
		$name = trim( $name );

		/* ۱٫۱۴٫۰ — ایمیل هم هرگز نام عمومی نیست (ورود ایمیلی، P5-D391 · تعمیم P5-D354) */
		if ( '' === $name || self::looks_like_phone( $name ) || self::looks_like_email( $name ) || self::is_neutral( $name ) ) {
			return $user_id > 0 ? self::neutral_name( $user_id, $lang ) : '';
		}

		return $name;
	}

	/** دفاع دوم: `get_the_author_meta( 'display_name', … )` — نظرهای صفحه در هستۀ نمایش. */
	public function filter_author_name( $value, $user_id = 0 ) {
		return self::public_name( (string) $value, (int) $user_id );
	}

	/* ── شناسه‌های تصادفی حساب تازه ────────────────────────────────────── */

	/** فقط حرف کوچک لاتین — هیچ ترکیبی از آن هرگز شبیه شماره یا `tp_<رقم>` نمی‌شود. */
	private static function token( int $len ): string {
		$abc = 'abcdefghijklmnopqrstuvwxyz';
		$out = '';
		for ( $i = 0; $i < $len; $i++ ) {
			$out .= $abc[ random_int( 0, 25 ) ];
		}
		return $out;
	}

	public static function random_login(): string {
		do {
			$login = 'tp_' . self::token( 12 );
		} while ( function_exists( 'username_exists' ) && username_exists( $login ) );
		return $login;
	}

	public static function random_nicename(): string {
		do {
			$slug = 'tp-' . self::token( 12 );
		} while ( function_exists( 'get_user_by' ) && get_user_by( 'slug', $slug ) );
		return $slug;
	}

	public static function email_domain(): string {
		return (string) ( function_exists( 'apply_filters' ) ? apply_filters( 'topersia_user_email_domain', 'users.topersia.com' ) : 'users.topersia.com' );
	}

	public static function random_email(): string {
		do {
			$email = 'u' . self::token( 16 ) . '@' . self::email_domain();
		} while ( function_exists( 'email_exists' ) && email_exists( $email ) );
		return $email;
	}

	/** ایمیل ساختگیِ حساب پیامکی (هر نسخه) — روی دامنۀ کاربران. */
	public static function synthetic_email( string $email ): bool {
		$domain = '@' . strtolower( self::email_domain() );
		return strlen( $email ) > strlen( $domain ) && substr( strtolower( $email ), -strlen( $domain ) ) === $domain;
	}

	/** ایمیل ساختگیِ ۱٫۱۰ (`{شماره}@دامنۀ کاربران`)؟ */
	public static function phone_email( string $email ): bool {
		$at = strrpos( $email, '@' );
		return false !== $at && strtolower( substr( $email, $at + 1 ) ) === strtolower( self::email_domain() ) && self::looks_like_phone( substr( $email, 0, $at ) );
	}

	/* ── مهاجرت حساب‌های موجود ─────────────────────────────────────────── */

	/**
	 * تغییرهای لازم یک حساب — خالص، برای آزمون. `user_login` هرگز در خروجی نیست.
	 *
	 * @param array{display_name?:string,user_nicename?:string,user_email?:string,nickname?:string} $u
	 * @return array<string,string>
	 */
	public static function fix_fields( int $user_id, array $u ): array {
		$out = array();

		$display = (string) ( $u['display_name'] ?? '' );
		if ( '' === trim( $display ) || self::looks_like_phone( $display ) ) {
			$out['display_name'] = self::neutral_name( $user_id, 'fa' );
		}

		$nick = (string) ( $u['nickname'] ?? '' );
		if ( '' !== $nick && self::looks_like_phone( $nick ) ) {
			$out['nickname'] = self::neutral_name( $user_id, 'fa' );
		}

		if ( self::looks_like_phone( (string) ( $u['user_nicename'] ?? '' ) ) ) {
			$out['user_nicename'] = self::random_nicename();
		}

		if ( self::phone_email( (string) ( $u['user_email'] ?? '' ) ) ) {
			$out['user_email'] = self::random_email();
		}

		return $out;
	}

	/**
	 * یک دسته در هر درخواست، تا تمام شود؛ سپس فقط یک خواندن گزینه.
	 * شناسه‌محور (`ID > نشانگر`) — هر حساب یک بار دیده می‌شود، پس تکرار امن است.
	 */
	public static function maybe_migrate(): void {
		if ( ! function_exists( 'get_option' ) || self::MIGRATION_VERSION === get_option( self::MIGRATION_OPTION ) ) {
			return;
		}

		if ( get_transient( 'topersia_uc_pii_lock' ) ) {
			return;
		}
		set_transient( 'topersia_uc_pii_lock', 1, 60 );

		global $wpdb;

		$cursor = (int) get_option( self::CURSOR_OPTION, 0 );
		$rows   = (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID > %d ORDER BY ID ASC LIMIT %d", $cursor, self::BATCH ),
			ARRAY_A
		);

		$fixed = 0;

		/* ایمیل تازۀ ساختگی نباید نامۀ «ایمیل شما عوض شد» بفرستد */
		add_filter( 'send_email_change_email', '__return_false', 99 );

		foreach ( $rows as $row ) {
			$id   = (int) $row['ID'];
			$user = get_userdata( $id );

			if ( $user ) {
				$change = self::fix_fields( $id, array(
					'display_name'  => (string) $user->display_name,
					'nickname'      => (string) get_user_meta( $id, 'nickname', true ),
					'user_nicename' => (string) $user->user_nicename,
					'user_email'    => (string) $user->user_email,
				) );

				if ( $change ) {
					$done = wp_update_user( array( 'ID' => $id ) + $change );
					if ( ! is_wp_error( $done ) ) {
						$fixed++;
					}
				}
			}

			$cursor = $id;
		}

		remove_filter( 'send_email_change_email', '__return_false', 99 );

		update_option( self::CURSOR_OPTION, $cursor, false );

		if ( count( $rows ) < self::BATCH ) {
			update_option( self::MIGRATION_OPTION, self::MIGRATION_VERSION, false );
			delete_option( self::CURSOR_OPTION );
		}

		if ( $fixed && function_exists( 'topersia_log' ) ) {
			/* فقط شمار — نه شناسه، نه نام، نه شماره */
			topersia_log( 'مهاجرت شناسۀ عمومی بی شماره: ' . $fixed . ' حساب', 'notice', 'user', array( 'count' => $fixed ) );
		}

		delete_transient( 'topersia_uc_pii_lock' );
	}
}
