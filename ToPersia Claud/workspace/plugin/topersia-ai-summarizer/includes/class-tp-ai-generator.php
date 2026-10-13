<?php
/**
 * Admin-side generation: metabox, bulk action, queue and auto-generate.
 *
 * 1.3.0 · P5-D358: the summary of a page is made by the site admin only, in
 * WordPress, with the engine chosen in the settings. No visitor - guest or
 * logged in - asks for a generation (TP_AI_Summary::authorize()).
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Status, daily cap and queue for summaries made by the admin.
 */
class TP_AI_Generator {

	/** Action Scheduler hook - one job per (post, type, lang). */
	const JOB = 'tpas_generate';

	/** Items run at once when the bulk action finds no queue. */
	const BATCH = 3;

	/**
	 * Register hooks. The job hook is registered everywhere: the queue runs
	 * from cron / WP-CLI, not only in the admin.
	 */
	public static function init() {
		add_action( self::JOB, array( __CLASS__, 'run_job' ), 10, 3 );
		add_action( 'save_post', array( __CLASS__, 'maybe_auto' ), 30, 2 );
	}

	// -----------------------------------------------------------------
	// What there is
	// -----------------------------------------------------------------

	/**
	 * The language a post is written in (WPML), else the site language.
	 * Each post is summarized in its own language only.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function post_lang( $post_id ) {
		return TP_AI_Localize::for_post( (int) $post_id );
	}

	/**
	 * Output types enabled in the settings.
	 *
	 * @return string[]
	 */
	public static function types() {
		$on = (array) TP_AI_Options::get_field( 'internal_types', array() );

		return array_values( array_intersect( TP_AI_Options::panel_types(), $on ) );
	}

	/**
	 * Whether a post may have a summary at all: published (an unpublished
	 * translation is a post of its own and has none) and of an enabled type.
	 *
	 * @param int $post_id Post id.
	 * @return bool
	 */
	public static function eligible( $post_id ) {
		$post_id = absint( $post_id );

		if ( ! $post_id || ! TP_AI_Context::published_ids( array( $post_id ) ) ) {
			return false;
		}

		$allowed = array_map( 'sanitize_key', (array) TP_AI_Options::get_field( 'post_types', array() ) );

		return in_array( get_post_type( $post_id ), $allowed, true );
	}

	/**
	 * none · fresh · stale - read from the store only, never generated.
	 *
	 * @param int    $post_id Post id.
	 * @param string $type    Output type.
	 * @param string $lang    Language code.
	 * @return string
	 */
	public static function status( $post_id, $type, $lang ) {
		$cached = TP_AI_Summary::get_cached( (int) $post_id, (string) $type, (string) $lang );

		if ( ! $cached ) {
			return 'none';
		}

		return empty( $cached['stale'] ) ? 'fresh' : 'stale';
	}

	// -----------------------------------------------------------------
	// Daily cap
	// -----------------------------------------------------------------

	/** Today, in the site time zone (same day as ToPersia Render's cap). */
	protected static function day() {
		if ( function_exists( 'topersia_g_ai_day' ) ) {
			return (string) topersia_g_ai_day();
		}

		return function_exists( 'wp_date' ) ? (string) wp_date( 'Y-m-d' ) : gmdate( 'Y-m-d' );
	}

	/** Admin generations made today. */
	public static function used_today() {
		return (int) get_transient( 'tpas_admin_gen_' . self::day() );
	}

	/**
	 * Engine calls still allowed today: the admin cap from the settings and,
	 * when ToPersia Render is active, the site's daily cap - whichever is
	 * smaller. 0 means stop.
	 *
	 * @return int
	 */
	public static function cap_left() {
		$left = max( 0, absint( TP_AI_Options::get_field( 'admin_daily_cap', 50 ) ) - self::used_today() );

		if ( function_exists( 'topersia_g_ai_caps' ) && function_exists( 'topersia_g_ai_site_used' ) ) {
			$caps = (array) topersia_g_ai_caps();
			$site = max( 0, (int) ( $caps['site'] ?? 0 ) - (int) topersia_g_ai_site_used() );
			$left = min( $left, $site );
		}

		return $left;
	}

	/**
	 * One engine call against both caps.
	 *
	 * @return void
	 */
	protected static function count() {
		$day = self::day();

		set_transient( 'tpas_admin_gen_' . $day, self::used_today() + 1, 2 * DAY_IN_SECONDS );

		// The site's counter (ToPersia Render). A per-site counter function is
		// used when Render offers one; until then its documented transient.
		if ( function_exists( 'topersia_g_ai_count_site' ) ) {
			topersia_g_ai_count_site();
		} elseif ( function_exists( 'topersia_g_ai_site_used' ) && function_exists( 'topersia_g_ai_day' ) ) {
			set_transient( 'tp-ai-site-' . $day, (int) topersia_g_ai_site_used() + 1, 2 * DAY_IN_SECONDS );
		}
	}

	// -----------------------------------------------------------------
	// Making one output
	// -----------------------------------------------------------------

	/**
	 * Generate one output for an admin. A fresh output is not rebuilt unless
	 * $force (the metabox button); an engine call counts against the caps.
	 *
	 * @param int    $post_id Post id.
	 * @param string $type    Output type.
	 * @param string $lang    Language code ('' = the post's own).
	 * @param bool   $force   Rebuild even when fresh.
	 * @return array { ok: bool, status: string, message?: string, skipped?: string }
	 */
	public static function generate( $post_id, $type, $lang = '', $force = false ) {
		$post_id = absint( $post_id );
		$type    = in_array( $type, TP_AI_Options::panel_types(), true ) ? $type : 'summary';
		$lang    = '' !== (string) $lang ? (string) $lang : self::post_lang( $post_id );

		if ( ! self::eligible( $post_id ) ) {
			return array( 'ok' => false, 'status' => 'none', 'message' => 'Not available.' );
		}

		$status = self::status( $post_id, $type, $lang );

		if ( 'fresh' === $status && ! $force ) {
			return array( 'ok' => true, 'status' => 'fresh', 'skipped' => 'fresh' );
		}

		$external = ! TP_AI_Summary::engine_is_internal();

		if ( $external ) {
			if ( self::cap_left() <= 0 ) {
				return array( 'ok' => false, 'status' => $status, 'message' => 'Daily generation cap reached.', 'skipped' => 'cap' );
			}

			self::count(); // The attempt costs tokens whether or not it succeeds.
		}

		$made = TP_AI_Summary::produce( $post_id, $type, $lang );

		if ( empty( $made['ok'] ) ) {
			return array( 'ok' => false, 'status' => $status, 'message' => (string) ( $made['message'] ?? '' ) );
		}

		return array( 'ok' => true, 'status' => self::status( $post_id, $type, $lang ) );
	}

	// -----------------------------------------------------------------
	// Queue (Action Scheduler of ToPersia Core, P5-D290)
	// -----------------------------------------------------------------

	/** Whether ToPersia Core's queue is ready. */
	public static function queue_ready() {
		return function_exists( 'topersia_queue_add' ) && function_exists( 'topersia_queue_ready' ) && topersia_queue_ready();
	}

	/**
	 * Queue every non-fresh enabled type of the given posts, each in its own
	 * language, up to today's cap. Without a queue, a small batch runs now.
	 *
	 * @param int[] $post_ids Post ids (already checked for edit_post by the caller).
	 * @param bool  $run_now  Run a small batch when no queue (bulk action only).
	 * @return array { queued, ran, fresh, capped, skipped }
	 */
	public static function enqueue( array $post_ids, $run_now = false ) {
		$out      = array( 'queued' => 0, 'ran' => 0, 'fresh' => 0, 'capped' => 0, 'skipped' => 0 );
		$external = ! TP_AI_Summary::engine_is_internal();
		$left     = $external ? self::cap_left() : PHP_INT_MAX;
		$queue    = self::queue_ready();

		foreach ( array_unique( array_map( 'absint', $post_ids ) ) as $post_id ) {
			if ( ! self::eligible( $post_id ) ) {
				$out['skipped']++;
				continue;
			}

			$lang = self::post_lang( $post_id );

			foreach ( self::types() as $type ) {
				if ( 'fresh' === self::status( $post_id, $type, $lang ) ) {
					$out['fresh']++;
					continue;
				}

				if ( $left <= 0 ) {
					$out['capped']++;
					continue;
				}

				if ( $queue ) {
					if ( topersia_queue_add( self::JOB, array( $post_id, $type, $lang ) ) ) {
						$out['queued']++;
						$left--;
					} else {
						$out['skipped']++; // Already pending.
					}
					continue;
				}

				if ( $run_now && $out['ran'] < self::BATCH ) {
					$r = self::generate( $post_id, $type, $lang );
					$out['ran'] += empty( $r['ok'] ) ? 0 : 1;
					$left        = $external ? self::cap_left() : $left;
					continue;
				}

				$out['skipped']++;
			}
		}

		return $out;
	}

	/**
	 * Queue job. The cap and the freshness are checked again at run time.
	 *
	 * @param int    $post_id Post id.
	 * @param string $type    Output type.
	 * @param string $lang    Language code.
	 * @return void
	 */
	public static function run_job( $post_id, $type = 'summary', $lang = '' ) {
		self::generate( (int) $post_id, (string) $type, (string) $lang );
	}

	/**
	 * «ساخت خودکار پس از انتشار یا تغییر محتوا» - off by default. When on, a
	 * published post's non-fresh outputs go to the same queue, under the same
	 * cap. Never run inline: no queue, no generation.
	 *
	 * @param int     $post_id Post id.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function maybe_auto( $post_id, $post = null ) {
		if ( ! TP_AI_Options::just_flag( 'auto_generate' ) ) {
			return;
		}

		if ( ! $post_id || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! self::queue_ready() ) {
			return;
		}

		self::enqueue( array( (int) $post_id ) );
	}
}

if ( ! function_exists( 'tpas_get_summary' ) ) {
	/**
	 * The stored AI output of a post - the stable read API for ToPersia Render.
	 * 1.3.0 · P5-D358.
	 *
	 * Reads the store only: it never generates, never calls an engine and
	 * never queues anything, whoever is viewing. A missing output is null; an
	 * output whose post content changed since it was made is returned with
	 * `stale` true (the caller decides whether to show it).
	 *
	 * @param int    $post_id Post id (published; anything else is null).
	 * @param string $type    summary · keypoints · plan.
	 * @param string $lang    Language code; '' = the post's own language.
	 * @return array|null { text: string, html: string, stale: bool, updated: int }
	 */
	function tpas_get_summary( $post_id, $type = 'summary', $lang = '' ) {
		$post_id = absint( $post_id );
		$type    = (string) $type;

		if ( ! $post_id || ! in_array( $type, TP_AI_Options::panel_types(), true ) ) {
			return null;
		}

		if ( ! TP_AI_Context::published_ids( array( $post_id ) ) ) {
			return null; // An unpublished post (or translation) has no public summary.
		}

		$lang   = '' !== (string) $lang ? TP_AI_Localize::sanitize( (string) $lang ) : TP_AI_Generator::post_lang( $post_id );
		$cached = TP_AI_Summary::get_cached( $post_id, $type, $lang );

		if ( ! $cached || '' === trim( (string) ( $cached['text'] ?? '' ) ) ) {
			return null;
		}

		return array(
			'text'    => (string) $cached['text'],
			'html'    => TP_AI_Render::format_ai_text( (string) $cached['text'] ),
			'stale'   => ! empty( $cached['stale'] ),
			'updated' => (int) ( $cached['created'] ?? 0 ),
		);
	}
}
