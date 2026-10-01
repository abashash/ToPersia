<?php
/**
 * دنبال‌کردن خبرهای یک موجودیت.  P5-D238
 *
 * «دنبال‌کردن» یعنی خبرهای همین موجودیت و مکان‌های درون آن — نه فید
 * دیدگاه‌ها. تا ۱٫۹٫۱۲ دکمۀ صفحه پیوند RSS دیدگاه‌های پست بود؛ آن پیوند
 * برداشته شد و این API جایش نشست.
 *
 * ذخیره: متای کاربر `_topersia_followed`، فهرست شناسه‌های **زبان پیش‌فرض**
 * WPML — همان قاعدۀ «ذخیره‌شده‌ها»: فارسی و انگلیسیِ یک مکان یک مورد‌اند.
 * برای نمایش به زبان جاری ترجمه می‌شوند.
 *
 * خبرِ هر مورد از این افزونه نمی‌آید: فیلتر `topersia_entity_latest_news`
 * را «هستۀ نمایش» پر می‌کند (همان پرس‌وجوی بخش خبر صفحه). بی آن افزونه،
 * فهرست بی‌خبر می‌ماند و خطا نمی‌دهد.
 *
 * @package ToPersia
 */

defined( 'ABSPATH' ) || exit;

final class ToPersia_Follow {

	private static ?self $instance = null;

	public const META = '_topersia_followed';

	/** سقف فهرست — یک متای سریال‌شده نباید بی‌حد بزرگ شود. */
	public const MAX = 500;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	private function __construct() {
		add_action( 'wp_ajax_topersia_follow_entity', array( $this, 'ajax_follow' ) );
		add_action( 'wp_ajax_topersia_entity_state', array( $this, 'ajax_state' ) );
	}

	/**
	 * شناسه‌های زبان پیش‌فرضِ دنبال‌شده.
	 *
	 * @return array<int,int>
	 */
	public static function ids( int $user_id ): array {

		if ( $user_id <= 0 ) {
			return array();
		}

		$raw = get_user_meta( $user_id, self::META, true );

		if ( ! is_array( $raw ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) );
	}

	/** شناسۀ زبان پیش‌فرض یک پست. */
	private static function canonical( int $post_id, string $type ): int {
		return class_exists( 'ToPersia_JetEngine' ) ? ToPersia_JetEngine::canonical_id( $post_id, $type ) : $post_id;
	}

	public static function is_following( int $user_id, int $post_id, string $type = '' ): bool {

		if ( $user_id <= 0 || $post_id <= 0 ) {
			return false;
		}

		$type = '' !== $type ? $type : (string) get_post_type( $post_id );

		return in_array( self::canonical( $post_id, $type ), self::ids( $user_id ), true );
	}

	/** موجودیتِ منتشرشدۀ ToPersia، یا null. */
	private static function entity( int $post_id, string $type ): ?WP_Post {

		$post = get_post( $post_id );

		/* منتشرشده و بی‌گذرواژه — P5-D247 */
		if ( ! $post || 'publish' !== $post->post_status || '' !== (string) $post->post_password || ! str_starts_with( $post->post_type, 'tp_' ) ) {
			return null;
		}

		if ( '' !== $type && $post->post_type !== $type ) {
			return null;
		}

		return $post;
	}

	private static function guard(): void {

		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );

		if ( ! is_user_logged_in() || ! wp_verify_nonce( $nonce, 'topersia_user_core' ) ) {
			wp_send_json_error( array( 'message' => ToPersia_I18n::t( 'invalid_request', 'درخواست نامعتبر است.' ) ), 403 );
		}
	}

	/**
	 * دنبال‌کردن یا لغو آن.
	 *
	 * ورودی: post_id · post_type · mode (follow | unfollow)
	 */
	public function ajax_follow(): void {

		self::guard();

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$type    = sanitize_key( wp_unslash( $_POST['post_type'] ?? '' ) );
		$mode    = 'unfollow' === sanitize_key( wp_unslash( $_POST['mode'] ?? '' ) ) ? 'unfollow' : 'follow';
		$user_id = get_current_user_id();
		$ids     = self::ids( $user_id );

		if ( 'unfollow' === $mode ) {

			/* لغو برای پستی که دیگر منتشر نیست هم باید کار کند. */
			$type      = '' !== $type ? $type : (string) get_post_type( $post_id );
			$canonical = self::canonical( $post_id, $type );
			$ids       = array_values( array_diff( $ids, array( $canonical, $post_id ) ) );

		} else {

			$post = self::entity( $post_id, $type );

			if ( ! $post ) {
				wp_send_json_error( array( 'message' => ToPersia_I18n::t( 'invalid_entity', 'موجودیت معتبر نیست.' ) ), 400 );
			}

			$canonical = self::canonical( $post_id, $post->post_type );

			if ( ! in_array( $canonical, $ids, true ) ) {

				if ( count( $ids ) >= self::MAX ) {
					wp_send_json_error( array( 'message' => ToPersia_I18n::t( 'follow_list_full', 'فهرست دنبال‌شده‌ها پر است.' ) ), 400 );
				}

				$ids[] = $canonical;
			}
		}

		update_user_meta( $user_id, self::META, $ids );

		wp_send_json_success( array(
			'following' => 'follow' === $mode,
			'count'     => count( $ids ),
			'message'   => ToPersia_I18n::t( 'follow' === $mode ? 'follow_added' : 'follow_removed' ),
		) );
	}

	/**
	 * وضعیت کاربر برای یک موجودیت — ذخیره‌شده؟ دنبال‌شده؟
	 *
	 * صفحه کش تمام‌صفحه می‌شود، پس این وضعیت در HTML نمی‌آید و پس از
	 * بارگذاری پرسیده می‌شود.
	 */
	public function ajax_state(): void {

		self::guard();

		$post_id = absint( $_POST['post_id'] ?? 0 );
		$type    = sanitize_key( wp_unslash( $_POST['post_type'] ?? '' ) );
		$post    = self::entity( $post_id, $type );

		if ( ! $post ) {
			wp_send_json_error( array( 'message' => ToPersia_I18n::t( 'invalid_entity', 'موجودیت معتبر نیست.' ) ), 400 );
		}

		$user_id   = get_current_user_id();
		$canonical = self::canonical( $post_id, $post->post_type );
		$saved     = false;

		if ( class_exists( 'ToPersia_JetEngine' ) ) {
			foreach ( ToPersia_JetEngine::instance()->saved_ids( $user_id ) as $item ) {
				if ( (int) $item['post_id'] === $canonical ) {
					$saved = true;
					break;
				}
			}
		}

		wp_send_json_success( array(
			'saved'     => $saved,
			'following' => in_array( $canonical, self::ids( $user_id ), true ),
		) );
	}

	/**
	 * مورد‌های پنل — منتشرشده‌ها به زبان جاری، هر کدام با آخرین خبرها.
	 *
	 * @return array<int,array{post_id:int,view_id:int,post_type:string,news:array}>
	 */
	public static function items( int $user_id, int $news_per_item = 3 ): array {

		$ids = self::ids( $user_id );

		if ( ! $ids ) {
			return array();
		}

		$posts = get_posts( array(
			'post_type'        => 'any',
			'post__in'         => $ids,
			'post_status'      => 'publish',
			'has_password'     => false,   // P5-D247
			'posts_per_page'   => -1,
			'orderby'          => 'post__in',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		) );

		$out = array();

		foreach ( $posts as $post ) {

			if ( ! str_starts_with( $post->post_type, 'tp_' ) ) {
				continue;
			}

			$view = class_exists( 'ToPersia_JetEngine' )
				? ToPersia_JetEngine::localized_id( (int) $post->ID, $post->post_type )
				: (int) $post->ID;

			$news = apply_filters( 'topersia_entity_latest_news', array(), $view, $news_per_item );

			$out[] = array(
				'post_id'   => (int) $post->ID,
				'view_id'   => $view,
				'post_type' => $post->post_type,
				'news'      => is_array( $news ) ? $news : array(),
			);
		}

		return $out;
	}
}
