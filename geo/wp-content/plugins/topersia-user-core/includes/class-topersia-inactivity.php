<?php
/**
 * غیرفعال‌سازی حساب بی‌فعالیت — نه حذف (P5-D377).
 *
 * پس از ۲۴ ماه بدون ورود، حساب «غیرفعال» می‌شود؛ ورود موفق فوراً فعالش می‌کند.
 * حذف کامل فقط با دکمۀ «حذف حساب» یا درخواست ایمیلی است (دستۀ ۱۷).
 *
 * - آخرین ورود: user meta `_topersia_last_login`، از قلاب `wp_login` — هر دو مسیر ورود
 *   (OTP این افزونه `do_action( 'wp_login' )` می‌زند و ورود وردپرس خودش).
 * - بی‌سابقۀ ورود: شمارش از لحظۀ نصب این نسخه (گزینۀ `topersia_uc_inactivity_since`، بی autoload).
 * - کار روزانۀ WP-Cron، دسته‌ای (۲۰۰) و idempotent. نقش دارای edit_posts و مدیر هرگز.
 * - اثر: نام و عکس خنثی (نظرهای منتشرشده می‌مانند)، دیده‌نشدن در نمایۀ عمومی/نویسنده،
 *   اعلان و پیامک نه (`can_notify()`). کد ورود (OTP) البته همچنان می‌رود — ورود فعالش می‌کند.
 * - مدت با صافی `topersia_user_core_inactive_months`؛ لاگ فقط شمار؛ کش با `topersia_pagecache_purge`.
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Inactivity {

	private static ?self $instance = null;

	public const LAST_LOGIN = '_topersia_last_login';
	public const FLAG       = '_topersia_inactive';
	public const SINCE      = 'topersia_uc_inactivity_since';
	public const HOOK       = 'topersia_user_core_inactivity';
	public const BATCH      = 200;
	public const MAX_BATCH  = 10;

	/** @var array<int,bool> */
	private static array $memo = array();

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'wp_login', array( __CLASS__, 'on_login' ), 10, 2 );
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		add_action( 'template_redirect', array( $this, 'hide_author_archive' ), 1 );
		add_filter( 'get_the_author_display_name', array( $this, 'author_name' ), 30, 2 );
		add_filter( 'jet-reviews/user-manager/raw-user-data', array( $this, 'review_user' ), 30 );
		self::ensure_cron();
	}

	public static function ensure_cron(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + 2 * HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	/** لحظۀ مبنا برای کاربر بی‌سابقۀ ورود. بی autoload. */
	public static function baseline(): int {
		$v = get_option( self::SINCE, null );

		if ( null === $v || false === $v ) {
			$v = time();
			add_option( self::SINCE, $v, '', 'no' );
		}

		return (int) $v;
	}

	public static function months(): int {
		return max( 1, (int) apply_filters( 'topersia_user_core_inactive_months', 24 ) );
	}

	public static function cutoff( ?int $now = null ): int {
		$now = $now ?? time();

		return (int) strtotime( '-' . self::months() . ' months', $now );
	}

	/** آخرین ورودِ مؤثر: متا یا مبنا */
	public static function last_login( int $user_id ): int {
		$m = (int) get_user_meta( $user_id, self::LAST_LOGIN, true );

		return $m > 0 ? $m : self::baseline();
	}

	public static function is_inactive( int $user_id ): bool {

		if ( $user_id <= 0 ) {
			return false;
		}

		if ( ! isset( self::$memo[ $user_id ] ) ) {
			self::$memo[ $user_id ] = '1' === (string) get_user_meta( $user_id, self::FLAG, true );
		}

		return self::$memo[ $user_id ];
	}

	/** اعلان و پیامک (غیر از کد ورود) فقط برای حساب فعال. */
	public static function can_notify( int $user_id ): bool {
		return ! self::is_inactive( $user_id );
	}

	/** حساب دارای دسترسی ویرایشی یا مدیر هرگز غیرفعال نمی‌شود */
	public static function exempt( $user ): bool {

		if ( ! $user instanceof WP_User ) {
			return true;
		}

		foreach ( array( 'manage_options', 'edit_posts', 'edit_pages', 'upload_files' ) as $cap ) {
			if ( user_can( $user, $cap ) ) {
				return true;
			}
		}

		return false;
	}

	/* ── ورود ──────────────────────────────────────────────────────────── */

	public static function on_login( $login, $user = null ): void {

		if ( ! $user instanceof WP_User ) {
			$user = get_user_by( 'login', (string) $login );
		}

		if ( ! $user ) {
			return;
		}

		$id  = (int) $user->ID;
		$was = '1' === (string) get_user_meta( $id, self::FLAG, true );

		update_user_meta( $id, self::LAST_LOGIN, time() );
		self::baseline();

		if ( $was ) {
			delete_user_meta( $id, self::FLAG );
			unset( self::$memo[ $id ] );
			self::purge_pages_for( array( $id ) );
		}
	}

	/* ── کار روزانه ────────────────────────────────────────────────────── */

	/** @return int شمار غیرفعال‌شده‌های این اجرا */
	public static function run( ?int $now = null ): int {

		global $wpdb;

		$base   = self::baseline();
		$cutoff = self::cutoff( $now );
		$cursor = 0;
		$total  = 0;
		$ids_all = array();

		for ( $i = 0; $i < self::MAX_BATCH; $i++ ) {

			$ids = $wpdb->get_col( $wpdb->prepare(
				"SELECT u.ID FROM {$wpdb->users} u
				 LEFT JOIN {$wpdb->usermeta} ll ON ( ll.user_id = u.ID AND ll.meta_key = %s )
				 LEFT JOIN {$wpdb->usermeta} ia ON ( ia.user_id = u.ID AND ia.meta_key = %s )
				 WHERE u.ID > %d AND ia.user_id IS NULL
				   AND COALESCE( NULLIF( CAST( ll.meta_value AS UNSIGNED ), 0 ), %d ) < %d
				 ORDER BY u.ID ASC LIMIT %d",
				self::LAST_LOGIN,
				self::FLAG,
				$cursor,
				$base,
				$cutoff,
				self::BATCH
			) );

			if ( ! $ids ) {
				break;
			}

			foreach ( $ids as $id ) {
				$id     = (int) $id;
				$cursor = max( $cursor, $id );

				if ( self::exempt( get_userdata( $id ) ) ) {
					continue;
				}

				update_user_meta( $id, self::FLAG, '1' );
				unset( self::$memo[ $id ] );
				$ids_all[] = $id;
				$total++;
			}

			if ( count( $ids ) < self::BATCH ) {
				break;
			}
		}

		if ( $total ) {
			self::purge_pages_for( $ids_all );

			if ( function_exists( 'topersia_log' ) ) {
				topersia_log( 'غیرفعال‌سازی حساب بی‌فعالیت', 'info', 'user', array( 'count' => $total ) );
			}
		}

		return $total;
	}

	/* ── کش صفحه ───────────────────────────────────────────────────────── */

	/** برچسب صفحه‌هایی که نظر این کاربرها را نشان می‌دهند */
	public static function purge_pages_for( array $user_ids ): void {

		global $wpdb;

		$user_ids = array_values( array_filter( array_map( 'intval', $user_ids ) ) );

		if ( ! $user_ids || ! has_action( 'topersia_pagecache_purge' ) ) {
			return;
		}

		$table = $wpdb->prefix . 'jet_reviews';
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

		if ( ! is_string( $found ) || 0 !== strcasecmp( $found, $table ) ) {
			return;
		}

		$in   = implode( ',', array_fill( 0, count( $user_ids ), '%s' ) );
		$pids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$table} WHERE author IN ($in)", ...array_map( 'strval', $user_ids ) ) );
		$tags = array();

		foreach ( (array) $pids as $pid ) {
			$tags[] = 'tp-post-' . (int) $pid;
		}

		if ( $tags ) {
			do_action( 'topersia_pagecache_purge', array_slice( array_values( array_unique( $tags ) ), 0, 500 ) );
		}
	}

	/* ── اثر عمومی ─────────────────────────────────────────────────────── */

	public function author_name( $value, $user_id = 0 ) {

		if ( $user_id && self::is_inactive( (int) $user_id ) && class_exists( 'ToPersia_Privacy' ) ) {
			return ToPersia_Privacy::neutral_name( (int) $user_id );
		}

		return $value;
	}

	/** نظر منتشرشدۀ کاربر غیرفعال: نام خنثی، بی ایمیل */
	public function review_user( $data ) {

		if ( is_array( $data ) && isset( $data['id'] ) && ctype_digit( (string) $data['id'] ) && self::is_inactive( (int) $data['id'] ) && class_exists( 'ToPersia_Privacy' ) ) {
			$data['name'] = ToPersia_Privacy::neutral_name( (int) $data['id'] );
			$data['mail'] = '';
		}

		return $data;
	}

	/** بایگانی نویسندۀ غیرفعال دیده نمی‌شود */
	public function hide_author_archive(): void {

		if ( ! is_author() ) {
			return;
		}

		$a = (int) get_queried_object_id();

		if ( $a > 0 && self::is_inactive( $a ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}
}
