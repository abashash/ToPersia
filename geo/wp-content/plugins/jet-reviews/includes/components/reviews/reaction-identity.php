<?php
namespace Jet_Reviews\Reviews;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Stable, site-bound identity and retained user-state cleanup for reviews.
 */
class Reaction_Identity {

	const UUID_META_KEY       = '_jet_reviews_reaction_uuid';
	const LEGACY_ID_META_KEY  = '_jet_reviews_reaction_legacy_id';
	const ORIGIN_OPTION       = 'jet_reviews_reaction_origin';
	const SECRET_OPTION       = 'jet_reviews_reaction_secret';
	const CLEANUP_QUEUE_OPTION = 'jet_reviews_reaction_cleanup_queue';
	const CLEANUP_HOOK        = 'jet_reviews_reaction_cleanup';
	const CLEANUP_ADMIN_ACTION = 'jet_reviews_reaction_cleanup';
	const SCHEMA_VERSION      = '1';
	const USER_META_KEY       = 'jet-approval-reviews';
	const RETENTION_DAYS      = 90;
	const CLEANUP_BATCH_SIZE  = 50;

	/**
	 * Register cleanup entry points.
	 */
	public function __construct() {
		add_action( self::CLEANUP_HOOK, array( $this, 'run_cleanup' ) );
		add_action( 'admin_post_' . self::CLEANUP_ADMIN_ACTION, array( $this, 'run_admin_cleanup' ) );

		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	/**
	 * Return the installation UUID, creating a non-autoloaded option on demand.
	 *
	 * @return string
	 */
	public function get_origin() {
		$origin = get_option( self::ORIGIN_OPTION, '' );

		if ( $this->is_valid_uuid( $origin ) ) {
			return strtolower( $origin );
		}

		$origin = wp_generate_uuid4();

		if ( ! add_option( self::ORIGIN_OPTION, $origin, '', false ) ) {
			$stored_origin = get_option( self::ORIGIN_OPTION, '' );

			if ( ! $this->is_valid_uuid( $stored_origin ) ) {
				update_option( self::ORIGIN_OPTION, $origin, false );
			}
		}

		$stored_origin = get_option( self::ORIGIN_OPTION, $origin );

		return $this->is_valid_uuid( $stored_origin ) ? strtolower( $stored_origin ) : $origin;
	}

	/**
	 * Return the local signing secret, creating it on demand without autoloading.
	 *
	 * @return string
	 */
	public function get_secret() {
		$secret = get_option( self::SECRET_OPTION, '' );

		if ( is_string( $secret ) && strlen( $secret ) >= 32 ) {
			return $secret;
		}

		$secret = wp_generate_password( 64, true, true );

		if ( ! add_option( self::SECRET_OPTION, $secret, '', false ) ) {
			$stored_secret = get_option( self::SECRET_OPTION, '' );

			if ( ! is_string( $stored_secret ) || strlen( $stored_secret ) < 32 ) {
				update_option( self::SECRET_OPTION, $secret, false );
			}
		}

		$stored_secret = get_option( self::SECRET_OPTION, $secret );

		return is_string( $stored_secret ) && strlen( $stored_secret ) >= 32 ? $stored_secret : $secret;
	}

	/**
	 * Validate a UUID v4 value.
	 *
	 * @param mixed $uuid UUID candidate.
	 * @return bool
	 */
	public function is_valid_uuid( $uuid ) {
		return is_string( $uuid ) && 1 === preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
			$uuid
		);
	}

	/**
	 * Ensure one review has a stable local identity.
	 *
	 * @param int $review_id Review ID.
	 * @return array|false
	 */
	public function ensure( $review_id ) {
		$review_id = absint( $review_id );

		if ( ! $review_id ) {
			return false;
		}

		$identity = $this->get( $review_id );

		if ( $identity ) {
			return $identity;
		}

		$lock_name = $this->get_lock_name( 'identity-create' );

		if ( ! $this->acquire_lock( $lock_name ) ) {
			return false;
		}

		try {
			$identity = $this->get( $review_id );

			if ( $identity ) {
				return $identity;
			}

			$reviews_table = jet_reviews()->db->tables( 'reviews', 'name' );
			$review_exists = jet_reviews()->db->wpdb()->get_var(
				jet_reviews()->db->wpdb()->prepare( "SELECT id FROM $reviews_table WHERE id = %d LIMIT 1", $review_id )
			);

			if ( ! $review_exists ) {
				return false;
			}

			for ( $attempt = 0; $attempt < 3; $attempt++ ) {
				$identity = array(
					'uuid'      => strtolower( wp_generate_uuid4() ),
					'legacy_id' => $review_id,
				);

				if ( ! $this->find_live_review_id( $identity['uuid'] ) && $this->store( $review_id, $identity ) ) {
					return $identity;
				}
			}

			return false;
		} finally {
			$this->release_lock( $lock_name );
		}
	}

	/**
	 * Get an existing complete identity without creating it.
	 *
	 * @param int $review_id Review ID.
	 * @return array|false
	 */
	public function get( $review_id ) {
		$identities = $this->get_many( array( $review_id ), false );
		$review_id  = absint( $review_id );

		return isset( $identities[ $review_id ] ) ? $identities[ $review_id ] : false;
	}

	/**
	 * Batch-load identities and optionally create missing values.
	 *
	 * @param array $review_ids Review IDs.
	 * @param bool  $ensure_missing Create missing identities.
	 * @return array
	 */
	public function get_many( $review_ids, $ensure_missing = false ) {
		$review_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $review_ids ) ) ) );

		if ( empty( $review_ids ) ) {
			return array();
		}

		$meta_table   = jet_reviews()->db->tables( 'review_meta', 'name' );
		$placeholders = implode( ',', array_fill( 0, count( $review_ids ), '%d' ) );
		$query_args   = array_merge( $review_ids, array( self::UUID_META_KEY, self::LEGACY_ID_META_KEY ) );
		$query         = jet_reviews()->db->wpdb()->prepare(
			"SELECT review_id, meta_key, meta_value FROM $meta_table WHERE review_id IN ($placeholders) AND meta_key IN (%s, %s)",
			$query_args
		);
		$rows          = jet_reviews()->db->wpdb()->get_results( $query, ARRAY_A );
		$raw           = array();

		foreach ( $rows as $row ) {
			$id = absint( $row['review_id'] );
			$raw[ $id ][ $row['meta_key'] ] = maybe_unserialize( $row['meta_value'] );
		}

		$identities = array();

		foreach ( $review_ids as $review_id ) {
			$uuid      = isset( $raw[ $review_id ][ self::UUID_META_KEY ] ) ? $raw[ $review_id ][ self::UUID_META_KEY ] : '';
			$legacy_id = isset( $raw[ $review_id ][ self::LEGACY_ID_META_KEY ] ) ? absint( $raw[ $review_id ][ self::LEGACY_ID_META_KEY ] ) : 0;

			if ( $this->is_valid_uuid( $uuid ) && $legacy_id ) {
				$identities[ $review_id ] = array(
					'uuid'      => strtolower( $uuid ),
					'legacy_id' => $legacy_id,
				);
			} elseif ( $ensure_missing ) {
				$identity = $this->ensure( $review_id );

				if ( $identity ) {
					$identities[ $review_id ] = $identity;
				}
			}
		}

		return $identities;
	}

	/**
	 * Store a trusted identity for a newly inserted review.
	 *
	 * @param int   $review_id Review ID.
	 * @param array $identity Trusted identity.
	 * @return bool
	 */
	public function store( $review_id, $identity ) {
		$review_id = absint( $review_id );
		$uuid      = isset( $identity['uuid'] ) ? strtolower( (string) $identity['uuid'] ) : '';
		$legacy_id = isset( $identity['legacy_id'] ) ? absint( $identity['legacy_id'] ) : 0;

		if ( ! $review_id || ! $this->is_valid_uuid( $uuid ) || ! $legacy_id ) {
			return false;
		}

		$meta_table = jet_reviews()->db->tables( 'review_meta', 'name' );
		$cleared = jet_reviews()->db->wpdb()->query(
			jet_reviews()->db->wpdb()->prepare(
				"DELETE FROM $meta_table WHERE review_id = %d AND meta_key IN (%s, %s)",
				$review_id,
				self::UUID_META_KEY,
				self::LEGACY_ID_META_KEY
			)
		);

		if ( false === $cleared ) {
			return false;
		}

		$uuid_saved = jet_reviews()->db->wpdb()->insert(
			$meta_table,
			array(
				'review_id'  => $review_id,
				'meta_key'   => self::UUID_META_KEY,
				'meta_value' => $uuid,
			)
		);

		if ( ! $uuid_saved ) {
			return false;
		}

		$legacy_saved = jet_reviews()->db->wpdb()->insert(
			$meta_table,
			array(
				'review_id'  => $review_id,
				'meta_key'   => self::LEGACY_ID_META_KEY,
				'meta_value' => $legacy_id,
			)
		);

		if ( ! $legacy_saved ) {
			jet_reviews()->db->wpdb()->delete(
				$meta_table,
				array(
					'review_id' => $review_id,
					'meta_key'  => self::UUID_META_KEY,
				)
			);

			return false;
		}

		return true;
	}

	/**
	 * Build the optional identity CSV fields.
	 *
	 * @param array $identity Review identity.
	 * @return array
	 */
	public function get_export_fields( $identity ) {
		if ( empty( $identity['uuid'] ) || empty( $identity['legacy_id'] ) ) {
			return array_fill_keys( $this->get_csv_headers(), '' );
		}

		$origin = $this->get_origin();
		$fields = array(
			'reaction_schema'    => self::SCHEMA_VERSION,
			'reaction_uuid'      => strtolower( $identity['uuid'] ),
			'reaction_legacy_id' => absint( $identity['legacy_id'] ),
			'reaction_origin'    => $origin,
		);

		$fields['reaction_signature'] = $this->sign(
			$fields['reaction_schema'],
			$fields['reaction_uuid'],
			$fields['reaction_legacy_id'],
			$fields['reaction_origin']
		);

		return $fields;
	}

	/**
	 * Verify optional CSV identity fields as current-installation data.
	 *
	 * @param array $row CSV row.
	 * @return array|false
	 */
	public function verify_import_fields( $row ) {
		$schema    = isset( $row['reaction_schema'] ) && is_scalar( $row['reaction_schema'] ) ? (string) $row['reaction_schema'] : '';
		$uuid      = isset( $row['reaction_uuid'] ) && is_scalar( $row['reaction_uuid'] ) ? strtolower( trim( (string) $row['reaction_uuid'] ) ) : '';
		$legacy_id = isset( $row['reaction_legacy_id'] ) && is_scalar( $row['reaction_legacy_id'] )
			? filter_var( $row['reaction_legacy_id'], FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) )
			: false;
		$origin    = isset( $row['reaction_origin'] ) && is_scalar( $row['reaction_origin'] ) ? strtolower( trim( (string) $row['reaction_origin'] ) ) : '';
		$signature = isset( $row['reaction_signature'] ) && is_scalar( $row['reaction_signature'] ) ? strtolower( trim( (string) $row['reaction_signature'] ) ) : '';

		if (
			self::SCHEMA_VERSION !== $schema
			|| ! $this->is_valid_uuid( $uuid )
			|| false === $legacy_id
			|| ! $this->is_valid_uuid( $origin )
			|| $origin !== $this->get_origin()
			|| 1 !== preg_match( '/^[0-9a-f]{64}$/', $signature )
		) {
			return false;
		}

		$expected = $this->sign( $schema, $uuid, $legacy_id, $origin );

		if ( ! hash_equals( $expected, $signature ) ) {
			return false;
		}

		return array(
			'uuid'      => $uuid,
			'legacy_id' => (int) $legacy_id,
		);
	}

	/**
	 * Return appended CSV identity headers.
	 *
	 * @return array
	 */
	public function get_csv_headers() {
		return array(
			'reaction_schema',
			'reaction_uuid',
			'reaction_legacy_id',
			'reaction_origin',
			'reaction_signature',
		);
	}

	/**
	 * Find a live review using a stable UUID.
	 *
	 * @param string $uuid Review UUID.
	 * @return int
	 */
	public function find_live_review_id( $uuid ) {
		if ( ! $this->is_valid_uuid( $uuid ) ) {
			return 0;
		}

		$reviews_table = jet_reviews()->db->tables( 'reviews', 'name' );
		$meta_table    = jet_reviews()->db->tables( 'review_meta', 'name' );
		$query         = jet_reviews()->db->wpdb()->prepare(
			"SELECT r.id FROM $reviews_table r INNER JOIN $meta_table m ON m.review_id = r.id WHERE m.meta_key = %s AND m.meta_value = %s LIMIT 1",
			self::UUID_META_KEY,
			strtolower( $uuid )
		);

		return absint( jet_reviews()->db->wpdb()->get_var( $query ) );
	}

	/**
	 * Add a deleted identity to the retained cleanup queue.
	 *
	 * @param array $identity Review identity.
	 * @return bool
	 */
	public function queue_deleted_identity( $identity ) {
		if ( empty( $identity['uuid'] ) || empty( $identity['legacy_id'] ) || ! $this->is_valid_uuid( $identity['uuid'] ) ) {
			return false;
		}

		$lock_name = $this->get_lock_name( 'cleanup-queue' );

		if ( ! $this->acquire_lock( $lock_name, 5 ) ) {
			return false;
		}

		try {
			$queue = $this->get_cleanup_queue();
			$queue[ strtolower( $identity['uuid'] ) ] = array(
				'legacy_id' => absint( $identity['legacy_id'] ),
				'deleted_at' => time(),
			);

			return update_option( self::CLEANUP_QUEUE_OPTION, $queue, false );
		} finally {
			$this->release_lock( $lock_name );
		}
	}

	/**
	 * Cancel retained cleanup after a verified same-site import.
	 *
	 * @param string $uuid Review UUID.
	 * @return bool
	 */
	public function cancel_cleanup( $uuid ) {
		$uuid = strtolower( (string) $uuid );

		if ( ! $this->is_valid_uuid( $uuid ) ) {
			return false;
		}

		$lock_name = $this->get_lock_name( 'cleanup-queue' );

		if ( ! $this->acquire_lock( $lock_name, 5 ) ) {
			return false;
		}

		try {
			$queue = $this->get_cleanup_queue();

			if ( ! isset( $queue[ $uuid ] ) ) {
				return true;
			}

			unset( $queue[ $uuid ] );

			return update_option( self::CLEANUP_QUEUE_OPTION, $queue, false );
		} finally {
			$this->release_lock( $lock_name );
		}
	}

	/**
	 * Delete reaction rows for a bounded set of expired UUIDs.
	 *
	 * @return int Number of cleanup queue items completed.
	 */
	public function run_cleanup() {
		$storage = isset( jet_reviews()->reviews_manager->reaction_storage )
			? jet_reviews()->reviews_manager->reaction_storage
			: null;

		// A later legacy batch must not be able to recreate purged rows.
		if ( ! $storage || ! $storage->is_migration_complete() ) {
			return 0;
		}

		$queue_lock   = $this->get_lock_name( 'cleanup-queue' );
		$retention    = (int) apply_filters( 'jet-reviews/reactions/cleanup-retention-days', self::RETENTION_DAYS );
		$batch_size   = (int) apply_filters( 'jet-reviews/reactions/cleanup-batch-size', self::CLEANUP_BATCH_SIZE );
		$expired_time = time() - ( max( 1, $retention ) * DAY_IN_SECONDS );
		$batch_size   = max( 1, min( 500, $batch_size ) );
		$expired      = array();

		if ( ! $this->acquire_lock( $queue_lock, 0 ) ) {
			return 0;
		}

		try {
			$queue = $this->get_cleanup_queue();

			foreach ( $queue as $queued_uuid => $queued_item ) {
				if ( isset( $queued_item['deleted_at'] ) && (int) $queued_item['deleted_at'] <= $expired_time ) {
					$expired[] = $queued_uuid;

					if ( count( $expired ) >= $batch_size ) {
						break;
					}
				}
			}
		} finally {
			$this->release_lock( $queue_lock );
		}

		if ( empty( $expired ) ) {
			return 0;
		}

		$completed = 0;

		foreach ( $expired as $uuid ) {
			$uuid_lock = $this->get_lock_name( 'identity-uuid-' . $uuid );

			if ( ! $this->acquire_lock( $uuid_lock, 0 ) ) {
				continue;
			}

			try {
				if ( ! $this->acquire_lock( $queue_lock, 0 ) ) {
					continue;
				}

				try {
					$queue = $this->get_cleanup_queue();

					if (
						! isset( $queue[ $uuid ]['deleted_at'] )
						|| (int) $queue[ $uuid ]['deleted_at'] > $expired_time
					) {
						continue;
					}

					if ( $this->find_live_review_id( $uuid ) ) {
						unset( $queue[ $uuid ] );
						update_option( self::CLEANUP_QUEUE_OPTION, $queue, false );
						$completed++;
						continue;
					}

					$deleted = jet_reviews()->db->wpdb()->query(
						jet_reviews()->db->wpdb()->prepare(
							'DELETE FROM ' . $storage->table_name() . ' WHERE review_uuid = %s',
							$uuid
						)
					);

					if ( false === $deleted ) {
						continue;
					}

					unset( $queue[ $uuid ] );

					if ( update_option( self::CLEANUP_QUEUE_OPTION, $queue, false ) || ! isset( $this->get_cleanup_queue()[ $uuid ] ) ) {
						$completed++;
					}
				} finally {
					$this->release_lock( $queue_lock );
				}
			} finally {
				$this->release_lock( $uuid_lock );
			}
		}

		return $completed;
	}

	/**
	 * Capability- and nonce-protected manual cleanup continuation.
	 */
	public function run_admin_cleanup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You don\'t have permissions to do this', 'jet-reviews' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::CLEANUP_ADMIN_ACTION );
		$this->run_cleanup();

		wp_safe_redirect(
			add_query_arg(
				array( 'jet_reviews_reaction_cleanup' => 'complete' ),
				admin_url( 'admin.php?page=jet-reviews-list-page' )
			)
		);
		exit;
	}

	/**
	 * Acquire a named MySQL advisory lock.
	 *
	 * @param string $lock_name Already-normalized lock name.
	 * @param int    $timeout Lock timeout in seconds.
	 * @return bool
	 */
	public function acquire_lock( $lock_name, $timeout = 2 ) {
		$result = jet_reviews()->db->wpdb()->get_var(
			jet_reviews()->db->wpdb()->prepare( 'SELECT GET_LOCK(%s, %d)', $lock_name, max( 0, (int) $timeout ) )
		);

		return '1' === (string) $result;
	}

	/**
	 * Release a named MySQL advisory lock.
	 *
	 * @param string $lock_name Already-normalized lock name.
	 */
	public function release_lock( $lock_name ) {
		jet_reviews()->db->wpdb()->get_var(
			jet_reviews()->db->wpdb()->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name )
		);
	}

	/**
	 * Build a short installation-specific lock name.
	 *
	 * @param string $resource Resource name.
	 * @return string
	 */
	public function get_lock_name( $resource ) {
		global $wpdb;

		return 'jetrev_' . substr( sha1( $wpdb->prefix . '|' . (string) $resource ), 0, 40 );
	}

	/**
	 * Normalize reaction state to none, like, or dislike.
	 *
	 * @param mixed $state Raw state.
	 * @return array
	 */
	public static function normalize_state( $state ) {
		if ( ! is_array( $state ) ) {
			return array( 'like' => false, 'dislike' => false );
		}

		$like    = isset( $state['like'] ) && filter_var( $state['like'], FILTER_VALIDATE_BOOLEAN );
		$dislike = isset( $state['dislike'] ) && filter_var( $state['dislike'], FILTER_VALIDATE_BOOLEAN );

		if ( $like === $dislike ) {
			return array( 'like' => false, 'dislike' => false );
		}

		return array( 'like' => $like, 'dislike' => $dislike );
	}

	/**
	 * Calculate one clicked-control transition and its aggregate deltas.
	 *
	 * @param mixed  $state Current state.
	 * @param string $type Clicked type.
	 * @return array|false
	 */
	public static function transition( $state, $type ) {
		if ( 'like' !== $type && 'dislike' !== $type ) {
			return false;
		}

		$state = self::normalize_state( $state );
		$new   = array( 'like' => false, 'dislike' => false );

		if ( $state[ $type ] ) {
			$new[ $type ] = false;
		} else {
			$new[ $type ] = true;
		}

		return array(
			'state'         => $new,
			'like_delta'    => (int) $new['like'] - (int) $state['like'],
			'dislike_delta' => (int) $new['dislike'] - (int) $state['dislike'],
		);
	}

	/**
	 * Sign the canonical schema/identity/origin tuple.
	 */
	private function sign( $schema, $uuid, $legacy_id, $origin ) {
		$canonical = implode( '|', array( (string) $schema, strtolower( $uuid ), absint( $legacy_id ), strtolower( $origin ) ) );

		return hash_hmac( 'sha256', $canonical, $this->get_secret() );
	}

	/**
	 * Return a normalized cleanup queue.
	 *
	 * @return array
	 */
	private function get_cleanup_queue() {
		$queue = get_option( self::CLEANUP_QUEUE_OPTION, array() );

		return is_array( $queue ) ? $queue : array();
	}

	/**
	 * Expose normalized retained identities to the migration resolver.
	 *
	 * @return array
	 */
	public function get_cleanup_queue_items() {
		return $this->get_cleanup_queue();
	}
}
