<?php
/**
 * Custom tables for persisted summaries and capability analytics.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Schema management via dbDelta plus small repositories for summaries and
 * analytics events. All SQL goes through $wpdb::prepare().
 */
class TP_AI_DB {

	/**
	 * Schema version stored in options.
	 */
	const VERSION = '1.4.0';

	/**
	 * The 1.0 unique key that 1.1 replaced but dbDelta could not drop.
	 */
	const LEGACY_INDEX = 'post_language';

	/**
	 * Option holding the installed schema version.
	 */
	const VERSION_KEY = 'tpas_db_version';

	/**
	 * Fully-qualified summaries table.
	 *
	 * @return string
	 */
	public static function summaries_table() {
		global $wpdb;

		return $wpdb->prefix . 'topersia_ai_summaries';
	}

	/**
	 * Fully-qualified analytics events table.
	 *
	 * @return string
	 */
	public static function events_table() {
		global $wpdb;

		return $wpdb->prefix . 'topersia_ai_events';
	}

	/**
	 * Create / upgrade the plugin tables. Safe to call on every upgrade check.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$summaries = self::summaries_table();
		$events    = self::events_table();

		dbDelta( "CREATE TABLE {$summaries} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			type VARCHAR(20) NOT NULL DEFAULT 'summary',
			language VARCHAR(10) NOT NULL DEFAULT '',
			content LONGTEXT NOT NULL,
			provider VARCHAR(50) NULL,
			model VARCHAR(100) NULL,
			source VARCHAR(20) NOT NULL DEFAULT 'ai',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY post_type_lang (post_id, type, language),
			KEY post (post_id),
			KEY source (source)
		) {$charset_collate};" );

		dbDelta( "CREATE TABLE {$events} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT UNSIGNED NOT NULL,
			event_type VARCHAR(30) NOT NULL,
			provider VARCHAR(50) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY post_event (post_id, event_type),
			KEY provider (provider)
		) {$charset_collate};" );

		// 1.2.2 · P5-D355: the settings row carries API keys - no autoload.
		if ( class_exists( 'TP_AI_Options' ) ) {
			TP_AI_Options::no_autoload();
		}

		self::drop_legacy_index();

		update_option( self::VERSION_KEY, self::VERSION );
	}

	/**
	 * Drop the pre-1.1 unique key `post_language (post_id, language)`.
	 *
	 * dbDelta never drops an index, so installs upgraded from 1.0 kept it
	 * beside `post_type_lang (post_id, type, language)` — and it rejected
	 * every keypoints and plan row (one row per post and language), which
	 * then lived only in expiring post meta. Idempotent: SHOW INDEX first,
	 * nothing to do when the key is already gone.
	 *
	 * @return bool True when the key was dropped.
	 */
	public static function drop_legacy_index() {
		global $wpdb;

		$table = self::summaries_table();
		$found = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name = %s", self::LEGACY_INDEX ) );

		if ( empty( $found ) ) {
			return false;
		}

		return false !== $wpdb->query( "ALTER TABLE {$table} DROP INDEX " . self::LEGACY_INDEX );
	}

	/**
	 * Whether the tables need (re)creating.
	 *
	 * @return bool
	 */
	public static function needs_install() {
		return self::VERSION !== get_option( self::VERSION_KEY );
	}

	// -----------------------------------------------------------------
	// Summaries repository
	// -----------------------------------------------------------------

	/**
	 * Fetch one stored summary row.
	 *
	 * @param int    $post_id Post id.
	 * @param string $type    Output type.
	 * @param string $lang    Language code.
	 * @return object|null
	 */
	public static function get_summary( $post_id, $type, $lang ) {
		global $wpdb;

		$table = self::summaries_table();

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE post_id = %d AND type = %s AND language = %s LIMIT 1",
				$post_id,
				$type,
				$lang
			)
		);
	}

	/**
	 * Insert or update a summary row.
	 *
	 * @param int    $post_id Post id.
	 * @param string $type    Output type.
	 * @param string $lang    Language code.
	 * @param string $content Stored plain text.
	 * @param string $provider Provider label (internal → '').
	 * @param string $model    Model label ('' for internal).
	 * @param string $source   'ai' or 'internal'.
	 * @return bool
	 */
	public static function save_summary( $post_id, $type, $lang, $content, $provider = '', $model = '', $source = 'ai' ) {
		global $wpdb;

		if ( ! $post_id || '' === trim( (string) $content ) ) {
			return false;
		}

		$table = self::summaries_table();
		$now   = current_time( 'mysql' );

		$row = self::get_summary( $post_id, $type, $lang );

		$data = array(
			'post_id'    => $post_id,
			'type'       => sanitize_key( $type ),
			'language'   => sanitize_key( $lang ),
			'content'    => (string) $content,
			'provider'   => '' !== (string) $provider ? sanitize_key( $provider ) : null,
			'model'      => '' !== (string) $model ? sanitize_text_field( $model ) : null,
			'source'     => 'internal' === $source ? 'internal' : 'ai',
			'updated_at' => $now,
		);

		if ( $row ) {
			$where = array( 'id' => (int) $row->id );

			return false !== $wpdb->update( $table, $data, $where );
		}

		$data['created_at'] = $now;

		return false !== $wpdb->insert( $table, $data );
	}

	/**
	 * Remove every summary line for a post (used when the post is saved).
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	public static function delete_post_summaries( $post_id ) {
		global $wpdb;

		$table = self::summaries_table();

		$wpdb->delete( $table, array( 'post_id' => $post_id ) );
	}

	/**
	 * Delete a single summary row by id (admin list page).
	 *
	 * @param int $id Row id.
	 * @return bool
	 */
	public static function delete_summary( $id ) {
		global $wpdb;

		return false !== $wpdb->delete( self::summaries_table(), array( 'id' => absint( $id ) ) );
	}

	/**
	 * Paged list of stored summaries (newest first) with post title joined in.
	 *
	 * @param int $limit  Rows per page.
	 * @param int $offset Start offset.
	 * @return array
	 */
	public static function list_summaries( $limit = 25, $offset = 0 ) {
		global $wpdb;

		$table = self::summaries_table();

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.id, s.post_id, s.type, s.language, s.content, s.provider, s.source,
				 s.created_at, COALESCE( p.post_title, '#' ) AS post_title
				 FROM {$table} s LEFT JOIN {$wpdb->posts} p ON p.ID = s.post_id
				 ORDER BY s.created_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			'ARRAY_A'
		);
	}

	// -----------------------------------------------------------------
	// Analytics repository
	// -----------------------------------------------------------------

	/**
	 * Record an anonymous event.
	 *
	 * @param int         $post_id Post id.
	 * @param string      $event    Event type.
	 * @param string|null $provider Provider slug.
	 * @return bool
	 */
	public static function record_event( $post_id, $event, $provider = null ) {
		global $wpdb;

		return false !== $wpdb->insert(
			self::events_table(),
			array(
				'post_id'    => absint( $post_id ),
				'event_type' => sanitize_key( $event ),
				'provider'   => $provider ? sanitize_key( $provider ) : null,
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Event counts per provider within the last N days.
	 *
	 * @param string $event Event type.
	 * @param int    $days  Look-back window.
	 * @return array
	 */
	public static function events_by_provider( $event = 'assistant_click', $days = 30 ) {
		global $wpdb;

		$table = self::events_table();

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT provider, COUNT(*) AS total FROM {$table}
				 WHERE event_type = %s AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				 GROUP BY provider ORDER BY total DESC",
				$event,
				max( 1, absint( $days ) )
			),
			'ARRAY_A'
		);
	}

	/**
	 * Event counts per post within the last N days.
	 *
	 * @param string $event Event type.
	 * @param int    $days  Look-back window.
	 * @param int    $limit Max rows.
	 * @return array
	 */
	public static function events_top_posts( $event = 'assistant_click', $days = 30, $limit = 10 ) {
		global $wpdb;

		$table = self::events_table();

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id, COUNT(*) AS total FROM {$table}
				 WHERE event_type = %s AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
				 GROUP BY post_id ORDER BY total DESC LIMIT %d",
				$event,
				max( 1, absint( $days ) ),
				max( 1, absint( $limit ) )
			),
			'ARRAY_A'
		);
	}
}