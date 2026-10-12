<?php
/**
 * Handles AJAX generation, caching and rate-limiting for the AI panel.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nonce-protected bridge between the JS panel and TP_AI_API.
 */
class TP_AI_Summary {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_tpas_ai_generate', array( __CLASS__, 'handle_ajax' ) );
		// wp_ajax_nopriv_tpas_ai_generate intentionally NOT registered: AI
		// generation is authenticated-only (logged-in ToPersia users). See
		// handle_ajax()'s explicit is_user_logged_in() check below, which
		// stays even though the nopriv hook is gone - defense in depth if
		// something else ever calls this method directly.
		add_action( 'save_post', array( __CLASS__, 'mark_stale' ), 20, 1 );
	}

	/**
	 * Invalidate stored summaries whenever the post is edited.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public static function mark_stale( $post_id ) {
		if ( ! $post_id || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		/*
		 * با کش مرکزی هسته خروجی گران پاک نمی‌شود: هش محتوای پست عوض می‌شود و
		 * خروجی «کهنه» است تا اقدام مجاز (کلیک کاربر واردشده) دوباره بسازدش.
		 * P5-D250 · SPRINT-4.4 ۲٫۳. بی هسته، همان پاک‌کردن ۱٫۱٫۱.
		 */
		if ( class_exists( 'TP_AI_Core_Bridge' ) && TP_AI_Core_Bridge::cache_available() ) {
			return;
		}

		TP_AI_DB::delete_post_summaries( $post_id );

		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s",
				$post_id,
				$wpdb->esc_like( '_tpas_ai_' ) . '%'
			)
		);
	}

	/**
	 * Whether the internal engine should be used for generation.
	 *
	 * @return bool
	 */
	public static function engine_is_internal() {
		if ( ! TP_AI_Options::just_flag( 'internal_enabled' ) ) {
			return false;
		}

		$mode = (string) TP_AI_Options::get_field( 'internal_engine', 'auto' );

		if ( 'internal' === $mode ) {
			return true;
		}

		if ( TP_AI_API::is_configured() ) {
			return false;
		}

		return in_array( $mode, array( 'auto', 'ai' ), true );
	}

	/**
	 * Process a generate or regenerate request from the front-end.
	 */
	public static function handle_ajax() {
		if ( ! is_user_logged_in() ) {
			// No details beyond a generic rejection - do not reveal whether
			// the nonce/post/etc. would otherwise have been valid.
			wp_send_json_error( array( 'message' => 'Authentication required.' ), 401 );
		}

		check_ajax_referer( 'tpas_ai', 'nonce' );

		$lang = self::sanitize_lang( wp_unslash( $_POST['lang'] ?? '' ) );
		$type = self::sanitize_type( wp_unslash( $_POST['type'] ?? '' ) );
		$post = absint( $_POST['post'] ?? 0 );

		// 1.2.2 · P5-D356 (R36): a post id is mandatory. A browser-sent URL is
		// never trusted - without a post it is rejected (400), with one it is
		// ignored: the prompt only ever carries the post's own permalink in the
		// page language. Every gate below therefore runs on every request.
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => 'Missing post.' ), 400 );
		}

		// A draft, private or password-protected post never becomes AI
		// context: a logged-in user could post its id and read a summary of
		// unpublished content. P5-D247
		if ( ! TP_AI_Context::published_ids( array( $post ) ) ) {
			wp_send_json_error( array( 'message' => 'Not available.' ), 404 );
		}

		if ( ! self::content_type_allowed( $post ) ) {
			wp_send_json_error( array( 'message' => 'AI is not available for this content type.' ) );
		}

		$user_id = get_current_user_id();

		if ( self::rate_limited( $user_id, self::client_ip(), $type ) ) {
			wp_send_json_error( array( 'message' => 'Rate limit. Please wait a minute.' ) );
		}

		// Serve the existing cache if still valid. A stale output (content hash
		// changed since it was made) is rebuilt: this click is the authorized
		// action P5-D250 asks for.
		$cached = self::get_cached( $post, $type, $lang );

		if ( $cached && empty( $cached['stale'] ) ) {
			self::log_reply( $post, $type, $lang, true, $cached['provider'] ?? '', $cached['model'] ?? '' );

			wp_send_json_success( array(
				'text'   => $cached['text'],
				'html'   => TP_AI_Render::format_ai_text( $cached['text'] ),
				'cached' => true,
			) );
		}

		// Internal engine path: deterministic, no rate limit, no API call.
		if ( self::engine_is_internal() ) {
			$text = self::internal_text( $post, $type, $lang );

			if ( '' === trim( $text ) ) {
				wp_send_json_error( array( 'message' => 'Not enough content to summarize.' ) );
			}

			wp_send_json_success( array(
				'text'   => $text,
				'html'   => TP_AI_Render::format_ai_text( $text ),
				'cached' => true,
				'engine' => 'internal',
			) );
		}

		if ( ! TP_AI_API::is_configured() ) {
			wp_send_json_error( array( 'message' => 'No internal engine configured.' ) );
		}

		/*
		 * 1.2.3 · P5-D356 (R53): the daily-cap gate (ToPersia Render) steps aside
		 * whenever get_cached() returns anything - a stale row included - and so
		 * never counts the rebuild that follows. The rebuild is counted here.
		 */
		if ( $cached ) {
			self::count_rebuild( $user_id );
		}

		// Build the prompt the user actually requested. The URL is the post's
		// own permalink in the page language (WPML) - never the browser's.
		$url_key   = self::post_url( $post, $lang );
		$title     = TP_AI_Render::current_title( $post );
		$base      = TP_AI_Prompt_Manager::get( $lang, $type );
		$user_msg  = TP_AI_Render::build_prompt( $base, $url_key, $title, $post, $lang );

		// Attach the page context.
		$context   = self::page_context( $post, $url_key );
		$user_full = $user_msg . "\n\n" . $context;

		$system    = TP_AI_Localize::context_system( $lang );

		/*
		 * هر فراخوانی سرویس بیرونی از کش مرکزی هسته می‌گذرد (قاعدۀ ۰-۶): انبارۀ همین
		 * جدول خلاصه‌ها، هش محتوای پست، خروجی گران. بی هسته، همان مسیر ۱٫۱٫۱.
		 */
		if ( class_exists( 'TP_AI_Core_Bridge' ) && TP_AI_Core_Bridge::cache_available() ) {

			$failed = null;
			$meta   = array();
			$text   = topersia_cache_remember(
				'ai.text',
				array( (int) $post, (string) $type ),
				function () use ( $system, $user_full, &$failed, &$meta ) {
					$r = TP_AI_API::chat( $system, $user_full );
					if ( ! $r['ok'] ) {
						$failed = $r;
						return new WP_Error( 'tpas_ai', (string) $r['message'] );
					}
					$meta = array( 'provider' => (string) ( $r['provider'] ?? '' ), 'model' => (string) ( $r['model'] ?? '' ) );
					return array( 'value' => (string) $r['data'], 'meta' => $meta );
				},
				array(
					'post'           => (int) $post,
					'lang'           => (string) $lang,
					'expensive'      => true,
					'rebuild'        => true,
					'stale_on_error' => false,
					'store'          => TP_AI_Core_Bridge::store( (int) $post, $type, $lang ),
				)
			);

			if ( is_wp_error( $text ) ) {
				wp_send_json_error( array( 'message' => $failed ? $failed['message'] : $text->get_error_message() ) );
			}

			self::log_reply( $post, $type, $lang, false, $meta['provider'] ?? '', $meta['model'] ?? '' );

			wp_send_json_success( array(
				'text'   => $text,
				'html'   => TP_AI_Render::format_ai_text( $text ),
				'cached' => false,
			) );
		}

		$result = TP_AI_API::chat( $system, $user_full );

		if ( ! $result['ok'] ) {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}

		$text  = $result['data'];
		$label = $result['provider'] ?? '';

		self::store( $post, $type, $lang, $text, $label, $result['model'] ?? '' );

		self::log_reply( $post, $type, $lang, false, $label, $result['model'] ?? '' );

		wp_send_json_success( array(
			'text'   => $text,
			'html'   => TP_AI_Render::format_ai_text( $text ),
			'cached' => false,
		) );
	}

	/**
	 * Server-side record of which engine answered. 1.2.3 · P5-D356 (A04): the
	 * browser reply no longer names the provider or model (P5-D317 - no engine
	 * choice is shown); the summaries table keeps both, and this line and the
	 * action make them reachable for the site owner.
	 *
	 * @return void
	 */
	private static function log_reply( $post, $type, $lang, $cached, $provider, $model ) {
		do_action( 'tpas_reply', (int) $post, (string) $type, (string) $lang, (bool) $cached, (string) $provider, (string) $model );

		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf( 'tpas: post=%d type=%s lang=%s cached=%d provider=%s model=%s', $post, $type, $lang, $cached ? 1 : 0, $provider, $model ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Counts a stale-output rebuild against the same daily caps the gate uses
	 * (per user, per site) and refuses it with 429 once either is full.
	 *
	 * @param int $user_id Current user.
	 * @return void
	 */
	private static function count_rebuild( $user_id ) {
		if ( ! apply_filters( 'tpas_rebuild_counts_cap', true ) ) {
			return; // The gate counts stale rebuilds itself.
		}

		if ( ! function_exists( 'topersia_g_ai_cap_check' ) || ! function_exists( 'topersia_g_ai_count' ) ) {
			return;
		}

		$why = topersia_g_ai_cap_check( topersia_g_ai_user_used( $user_id ), topersia_g_ai_site_used(), topersia_g_ai_caps() );

		if ( '' !== $why ) {
			wp_send_json_error( array( 'cap' => $why, 'message' => 'Daily generation cap reached.' ), 429 );
		}

		topersia_g_ai_count( $user_id );
	}

	// -----------------------------------------------------------------
	// Caching helpers
	// -----------------------------------------------------------------

	/**
	 * Return the cached summary for a post/type/lang, or null if missing/expired.
	 *
	 * @param int    $post_id Post id.
	 * @param string $type    Output type.
	 * @param string $lang    Language code.
	 * @return array|null
	 */
	public static function get_cached( $post_id, $type, $lang ) {
		if ( ! $post_id ) {
			return null;
		}

		// Persisted archive row first.
		$row = TP_AI_DB::get_summary( $post_id, $type, $lang );

		if ( $row && '' !== trim( (string) $row->content ) ) {
			return array(
				'text'     => (string) $row->content,
				'provider' => (string) $row->provider,
				'model'    => (string) $row->model,
				'created'  => strtotime( (string) $row->updated_at ),
				/* هش محتوا پس از ساخت عوض شده؟  P5-D250 */
				'stale'    => class_exists( 'TP_AI_Core_Bridge' ) && TP_AI_Core_Bridge::is_stale( $post_id, $type, $lang, (string) ( $row->source ?? 'ai' ) ),
			);
		}

		// Legacy post-meta cache (pre-1.1 installs).
		$key   = '_tpas_ai_' . $type . '_' . $lang;
		$cache = get_post_meta( $post_id, $key, true );

		if ( ! is_array( $cache ) || empty( $cache['text'] ) ) {
			return null;
		}

		$hours = absint( TP_AI_Options::get_field( 'internal_cache_hours', 168 ) );

		if ( $hours && isset( $cache['created'] ) && ( time() - (int) $cache['created'] ) > $hours * 3600 ) {
			delete_post_meta( $post_id, $key );

			return null;
		}

		return $cache;
	}

	/**
	 * Generate (or reuse) text for a post without any API call. Safe to run
	 * during page render — fast and fully deterministic.
	 *
	 * @param int    $post_id Post id.
	 * @param string $type    Output type.
	 * @param string $lang    Language code.
	 * @return string
	 */
	public static function internal_text( $post_id, $type, $lang ) {
		if ( ! $post_id ) {
			return '';
		}

		$cached = self::get_cached( $post_id, $type, $lang );

		/* 1.2.3 (R54): ردیف داخلیِ کهنه از نو ساخته می‌شود — هزینه‌ای ندارد */
		if ( $cached && empty( $cached['stale'] ) ) {
			return (string) $cached['text'];
		}

		$engine   = new TP_AI_Internal();
		$context  = TP_AI_Context::build( $post_id );
		$text     = $engine->generate( $type, $context );

		if ( '' !== trim( $text ) ) {
			self::store( $post_id, $type, $lang, $text, '', '', 'internal' );
		}

		return $text;
	}

	/**
	 * Write a generated summary into the DB archive and post meta.
	 *
	 * @param int    $post_id Post id.
	 * @param string $type    Output type.
	 * @param string $lang    Language code.
	 * @param string $text    Content.
	 * @param string $provider Provider label.
	 * @param string $model    Model label.
	 * @param string $source   'ai' or 'internal'.
	 */
	private static function store( $post_id, $type, $lang, $text, $provider, $model, $source = 'ai' ) {
		if ( ! $post_id ) {
			return;
		}

		if ( $post_id ) {
			TP_AI_DB::save_summary( $post_id, $type, $lang, $text, $provider, $model, $source );

			if ( 'internal' === $source && class_exists( 'TP_AI_Core_Bridge' ) ) {
				TP_AI_Core_Bridge::stamp( $post_id, $type, $lang ); // 1.2.3 (R54): ردیف داخلی هم هش می‌گیرد
			}
		}

		$key = '_tpas_ai_' . $type . '_' . $lang;

		update_post_meta( $post_id, $key, array(
			'text'     => $text,
			'provider' => $provider,
			'model'    => $model,
			'created'  => time(),
		) );
	}

	// -----------------------------------------------------------------
	// Rate-limiting
	// -----------------------------------------------------------------

	/**
	 * Per-user (primary) + per-IP (secondary) transient throttle. Since AI
	 * generation is authenticated-only, the primary key is the WordPress
	 * user ID rather than IP - this avoids unfairly throttling every user
	 * behind a shared office/NAT IP together, while the IP check remains
	 * as a defense-in-depth layer against one compromised/scripted account
	 * hammering the endpoint from many requests per minute.
	 *
	 * @param int    $user_id Logged-in user ID.
	 * @param string $ip      Client IP (secondary layer).
	 * @param string $type    Requested feature type ("summary", "plan", ...).
	 * @return bool True when the request should be blocked.
	 */
	private static function rate_limited( $user_id, $ip, $type ) {
		$limit = absint( TP_AI_Options::get_field( 'internal_throttle', 10 ) );

		if ( $limit <= 0 ) {
			return false;
		}

		if ( $user_id && self::bump_counter( 'tpas_rt_u_' . $user_id . '_' . $type, $limit ) ) {
			return true;
		}

		// Secondary, more permissive IP layer (catches many accounts from
		// one abusive source without being the primary fairness mechanism).
		if ( $ip && self::bump_counter( 'tpas_rt_ip_' . md5( $ip ), $limit * 5 ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Increments an hourly transient counter and reports whether it has
	 * reached the given limit.
	 *
	 * @param string $key   Transient key.
	 * @param int    $limit Max allowed count within the window.
	 * @return bool True once the limit is reached (request blocked).
	 */
	private static function bump_counter( $key, $limit ) {
		$current = get_transient( $key );

		if ( false === $current ) {
			set_transient( $key, 1, HOUR_IN_SECONDS );
			return false;
		}

		if ( (int) $current >= $limit ) {
			return true;
		}

		set_transient( $key, (int) $current + 1, HOUR_IN_SECONDS );

		return false;
	}

	// -----------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------

	/**
	 * Plain-text excerpt from the current or requested page.
	 *
	 * @param int    $post_id Post id.
	 * @param string $url     Page URL (fallback).
	 * @return string
	 */
	private static function page_context( $post_id, $url ) {
		$chars = absint( TP_AI_Options::get_field( 'internal_context_chars', 40000 ) );

		/**
		 * Filter the maximum context characters fed to the model.
		 *
		 * @param int    $chars    Max chars.
		 * @param int    $post_id  Post id.
		 * @param string $url      Page URL.
		 */
		$chars = apply_filters( 'tpas_context_chars', $chars, $post_id, $url );

		$text = '';

		if ( $post_id ) {
			$post  = get_post( $post_id );
			if ( $post ) {
				$text = $post->post_title . "\n\n" . wp_strip_all_tags( $post->post_content );
			}

			// Append structured data (JetEngine facts + related items).
			$structured = TP_AI_Context::structured_block( TP_AI_Context::build( $post_id ) );

			if ( '' !== $structured ) {
				$text .= "\n\n" . $structured;
			}
		}

		$text = preg_replace( '/[\t ]+/', ' ', $text );
		$text = trim( $text );

		if ( $chars > 0 && function_exists( 'mb_strlen' ) && mb_strlen( $text, 'UTF-8' ) > $chars ) {
			$text = mb_substr( $text, 0, $chars, 'UTF-8' ) . '…';
		}

		/**
		 * Filter the context text fed to the model.
		 *
		 * @param string $text     Context string.
		 * @param int    $post_id  Post id.
		 * @param string $url      Page URL.
		 */
		return apply_filters( 'tpas_context', $text, $post_id, $url );
	}

	/**
	 * Normalise the language code against the registered set.
	 *
	 * @param string $lang Raw language code.
	 * @return string Sanitised code.
	 */
	private static function sanitize_lang( $lang ) {
		$lang  = preg_replace( '/[^a-z0-9_-]/i', '', strtolower( $lang ) );
		$langs = TP_AI_Localize::languages();

		return in_array( $lang, $langs, true ) ? $lang : TP_AI_Localize::sanitize( get_locale() );
	}

	/**
	 * Whether AI is enabled for a given post's type, per the admin's
	 * "post_types" setting. The frontend already avoids rendering the
	 * panel for disallowed types, but the AJAX endpoint re-checks this
	 * server-side since a logged-in user could otherwise POST an arbitrary
	 * post ID directly.
	 *
	 * @param int $post_id Post id.
	 * @return bool
	 */
	private static function content_type_allowed( $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( ! $post_type ) {
			return false;
		}

		$allowed = array_map( 'sanitize_key', (array) TP_AI_Options::get_field( 'post_types', array() ) );

		return in_array( $post_type, $allowed, true );
	}

	/**
	 * Constrain the output type to the allowed list.
	 *
	 * @param string $type Raw output type.
	 * @return string Sanitised type.
	 */
	private static function sanitize_type( $type ) {
		$slug  = preg_replace( '/[^a-z_]/i', '', strtolower( $type ) );
		$valid = TP_AI_Options::panel_types();

		return in_array( $slug, $valid, true ) ? $slug : 'summary';
	}

	/**
	 * The post's own permalink, in the page language when WPML is active.
	 * 1.2.2 · P5-D356 - replaces the browser-sent `url` in the prompt.
	 *
	 * @param int    $post_id Post id (already checked as published).
	 * @param string $lang    Sanitised page language.
	 * @return string
	 */
	public static function post_url( $post_id, $lang ) {
		$url = (string) get_permalink( $post_id );

		if ( '' !== $url && '' !== (string) $lang && function_exists( 'apply_filters' ) ) {
			// WPML documented hook: the URL of the same content in another language.
			$localized = apply_filters( 'wpml_permalink', $url, $lang );
			if ( is_string( $localized ) && '' !== $localized ) {
				$url = $localized;
			}
		}

		return esc_url_raw( $url );
	}

	/**
	 * Client IP for the secondary rate-limit layer.
	 *
	 * 1.2.2 · P5-D356 (R47): the same rule as topersia-user-core
	 * (`ToPersia_Auth::ip_hash()`): X-Forwarded-For is spoofable and is read
	 * only when the site explicitly declares TOPERSIA_TRUST_PROXY (behind a
	 * known proxy); otherwise REMOTE_ADDR alone. CF-Connecting-IP / X-Real-IP
	 * are never trusted.
	 *
	 * @return string
	 */
	private static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		if ( defined( 'TOPERSIA_TRUST_PROXY' ) && TOPERSIA_TRUST_PROXY && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$chain = explode( ',', (string) wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			$first = trim( (string) reset( $chain ) );

			if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
				$ip = $first;
			}
		}

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}
}