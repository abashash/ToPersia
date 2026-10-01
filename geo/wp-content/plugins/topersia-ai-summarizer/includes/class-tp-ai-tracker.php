<?php
/**
 * Anonymous click tracking for the outbound assistant buttons.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Counts service clicks per post and globally.
 */
class TP_AI_Tracker {

	/**
	 * Option storing the global totals.
	 */
	const STATS_KEY = 'tpas_stats';

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'wp_ajax_tpas_track', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_tpas_track', array( __CLASS__, 'handle' ) );
		add_action( 'trashed_post', array( __CLASS__, 'purge_post' ), 10, 1 );
		add_action( 'before_delete_post', array( __CLASS__, 'purge_post' ), 10, 1 );
	}

	/**
	 * Store a single click.
	 */
	public static function handle() {
		check_ajax_referer( 'tpas_ai', 'nonce' );

		$post    = absint( $_POST['post'] ?? 0 );
		$service = isset( $_POST['service'] ) ? sanitize_key( wp_unslash( $_POST['service'] ) ) : '';
		$service = ( false !== TP_AI_Services::get( $service ) ) ? $service : '';

		if ( ! $post || ! $service ) {
			wp_send_json_error();
		}

		if ( $post ) {
			$meta  = (array) get_post_meta( $post, '_tpas_clicks', true );
			$meta[ $service ] = absint( $meta[ $service ] ?? 0 ) + 1;
			update_post_meta( $post, '_tpas_clicks', $meta );
		}

		$stats = get_option( self::STATS_KEY, array() );
		if ( ! is_array( $stats ) ) {
			$stats = array();
		}
		$stats[ $service ] = absint( $stats[ $service ] ?? 0 ) + 1;
		$stats['total']    = absint( $stats['total'] ?? 0 ) + 1;
		update_option( self::STATS_KEY, $stats );

		// Optional DB-backed capability row (privacy-safe: no IP/UA stored).
		if ( TP_AI_Options::just_flag( 'analytics_enabled' ) ) {
			TP_AI_DB::record_event( $post, 'assistant_click', $service );
		}

		wp_send_json_success();
	}

	/**
	 * Per-post click counts.
	 *
	 * @param int $post_id Post id.
	 * @return array
	 */
	public static function post_stats( $post_id ) {
		$meta = get_post_meta( $post_id, '_tpas_clicks', true );

		return is_array( $meta ) ? $meta : array();
	}

	/**
	 * Global click stats.
	 *
	 * @return array
	 */
	public static function totals() {
		$stats = get_option( self::STATS_KEY, array() );

		return is_array( $stats ) ? $stats : array();
	}

	/**
	 * Remove per-post clicks when the post disappears.
	 *
	 * @param int $post_id Post id.
	 */
	public static function purge_post( $post_id ) {
		delete_post_meta( $post_id, '_tpas_clicks' );
	}
}