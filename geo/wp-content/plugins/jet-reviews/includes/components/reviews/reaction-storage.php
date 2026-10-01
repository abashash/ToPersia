<?php
namespace Jet_Reviews\Reviews;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Dedicated logged-in review reaction storage and legacy migration.
 */
class Reaction_Storage {

	const MIGRATION_OPTION         = 'jet_reviews_reaction_migration';
	const MIGRATION_CRON_HOOK      = 'jet_reviews_reaction_migration_batch';
	const MIGRATION_RETRY_ACTION   = 'jet_reviews_reaction_migration_retry';
	const MIGRATION_BATCH_SIZE     = 100;
	const INSERT_BATCH_SIZE        = 250;
	const MAX_WORK_SECONDS         = 3;
	const ADMIN_FALLBACK_INTERVAL  = 60;
	const STALLED_MIGRATION_PERIOD = 900;

	/**
	 * Register migration, privacy, and administration hooks.
	 */
	public function __construct() {
		add_action( 'deleted_user', array( $this, 'delete_user_reactions' ) );
		add_action( self::MIGRATION_CRON_HOOK, array( $this, 'run_scheduled_migration' ) );
		add_action( 'admin_init', array( $this, 'maybe_run_admin_fallback' ) );
		add_action( 'admin_notices', array( $this, 'render_migration_notice' ) );
		add_action( 'admin_post_' . self::MIGRATION_RETRY_ACTION, array( $this, 'handle_migration_retry' ) );
		$this->initialize_migration();
		$this->ensure_automatic_migration();
	}

	/**
	 * Return the dedicated table name.
	 *
	 * @return string
	 */
	public function table_name() {
		return jet_reviews()->db->tables( 'review_reactions', 'name' );
	}

	/**
	 * Convert a stored integer to the public state shape.
	 *
	 * @param mixed $reaction Stored value.
	 * @return array
	 */
	public static function reaction_to_state( $reaction ) {
		return array(
			'like'    => 1 === (int) $reaction,
			'dislike' => -1 === (int) $reaction,
		);
	}

	/**
	 * Convert a public state to 1, -1, or zero (no row).
	 *
	 * @param mixed $state Public state.
	 * @return int
	 */
	public static function state_to_reaction( $state ) {
		$state = Reaction_Identity::normalize_state( $state );

		if ( $state['like'] ) {
			return 1;
		}

		return $state['dislike'] ? -1 : 0;
	}

	/**
	 * Read one user's reactions for all requested UUIDs in one query.
	 *
	 * @param int   $user_id User ID.
	 * @param array $uuids Review UUIDs.
	 * @param bool  $for_update Lock selected rows in the active transaction.
	 * @return array UUID => public state.
	 */
	public function get_many( $user_id, $uuids, $for_update = false ) {
		$user_id = absint( $user_id );
		$uuids   = array_values( array_unique( array_filter( array_map( function ( $uuid ) {
			return is_scalar( $uuid ) ? strtolower( (string) $uuid ) : '';
		}, (array) $uuids ) ) ) );

		if ( ! $user_id || empty( $uuids ) ) {
			return array();
		}

		$valid_uuids = array();
		$identity    = jet_reviews()->reviews_manager->reaction_identity;

		foreach ( $uuids as $uuid ) {
			if ( $identity->is_valid_uuid( $uuid ) ) {
				$valid_uuids[] = $uuid;
			}
		}

		if ( empty( $valid_uuids ) ) {
			return array();
		}

		$wpdb         = jet_reviews()->db->wpdb();
		$table        = $this->table_name();
		$placeholders = implode( ',', array_fill( 0, count( $valid_uuids ), '%s' ) );
		$args         = array_merge( array( $user_id ), $valid_uuids );
		$lock_clause  = $for_update ? ' FOR UPDATE' : '';
		$query        = $wpdb->prepare(
			"SELECT review_uuid, reaction FROM $table WHERE user_id = %d AND review_uuid IN ($placeholders)$lock_clause",
			$args
		);
		$rows         = $wpdb->get_results( $query, ARRAY_A );
		$result       = array();

		foreach ( (array) $rows as $row ) {
			$result[ strtolower( $row['review_uuid'] ) ] = self::reaction_to_state( $row['reaction'] );
		}

		return $result;
	}

	/**
	 * Read one reaction.
	 *
	 * @param string $uuid Review UUID.
	 * @param int    $user_id User ID.
	 * @param bool   $for_update Lock the row.
	 * @return array|null Null means no table row.
	 */
	public function get( $uuid, $user_id, $for_update = false ) {
		$items = $this->get_many( $user_id, array( $uuid ), $for_update );
		$uuid  = strtolower( (string) $uuid );

		return isset( $items[ $uuid ] ) ? $items[ $uuid ] : null;
	}

	/**
	 * Insert, update, or remove one user's current reaction.
	 *
	 * @param string $uuid Review UUID.
	 * @param int    $user_id User ID.
	 * @param mixed  $state Public state.
	 * @return bool
	 */
	public function persist( $uuid, $user_id, $state ) {
		$uuid     = strtolower( (string) $uuid );
		$user_id  = absint( $user_id );
		$reaction = self::state_to_reaction( $state );

		if ( ! $user_id || ! jet_reviews()->reviews_manager->reaction_identity->is_valid_uuid( $uuid ) ) {
			return false;
		}

		$wpdb  = jet_reviews()->db->wpdb();
		$table = $this->table_name();

		if ( ! $reaction ) {
			return false !== $wpdb->delete(
				$table,
				array(
					'review_uuid' => $uuid,
					'user_id'     => $user_id,
				)
			);
		}

		$now = current_time( 'mysql', true );
		$sql = $wpdb->prepare(
			"INSERT INTO $table (review_uuid, user_id, reaction, created_at, updated_at)
			VALUES (%s, %d, %d, %s, %s)
			ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), updated_at = VALUES(updated_at)",
			$uuid,
			$user_id,
			$reaction,
			$now,
			$now
		);

		return false !== $wpdb->query( $sql );
	}

	/**
	 * Insert legacy records without replacing runtime state.
	 *
	 * @param array $records Rows containing uuid, user_id, and reaction.
	 * @return int|false Number inserted or false on error.
	 */
	public function insert_legacy_records( $records ) {
		$records = array_values( (array) $records );

		if ( empty( $records ) ) {
			return 0;
		}

		$wpdb       = jet_reviews()->db->wpdb();
		$table      = $this->table_name();
		$chunk_size = (int) apply_filters( 'jet-reviews/reactions/migration-insert-size', self::INSERT_BATCH_SIZE );
		$chunk_size = max( 1, min( 1000, $chunk_size ) );
		$inserted   = 0;
		$now        = current_time( 'mysql', true );

		foreach ( array_chunk( $records, $chunk_size ) as $chunk ) {
			$values = array();
			$args   = array();

			foreach ( $chunk as $record ) {
				$values[] = '(%s, %d, %d, %s, %s)';
				$args[]   = strtolower( $record['uuid'] );
				$args[]   = absint( $record['user_id'] );
				$args[]   = (int) $record['reaction'];
				$args[]   = $now;
				$args[]   = $now;
			}

			$sql = "INSERT IGNORE INTO $table (review_uuid, user_id, reaction, created_at, updated_at) VALUES " . implode( ',', $values );
			$result = $wpdb->query( $wpdb->prepare( $sql, $args ) );

			if ( false === $result ) {
				return false;
			}

			$inserted += (int) $result;
		}

		return $inserted;
	}

	/**
	 * Remove all private reaction ownership when a user is permanently deleted.
	 *
	 * @param int $user_id Deleted user ID.
	 * @return int|false
	 */
	public function delete_user_reactions( $user_id ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return false;
		}

		return jet_reviews()->db->wpdb()->delete( $this->table_name(), array( 'user_id' => $user_id ) );
	}

	/**
	 * Return whether legacy fallback and cleanup protection can stop.
	 *
	 * @return bool
	 */
	public function is_migration_complete() {
		$status = $this->get_migration_status();

		return 'complete' === $status['status'];
	}

	/**
	 * Return normalized resumable migration state.
	 *
	 * @return array
	 */
	public function get_migration_status() {
		$defaults = array(
			'status'               => 'pending',
			'cursor'               => 0,
			'processed_users'      => 0,
			'migrated_reactions'   => 0,
			'unresolved_entries'   => 0,
			'failures'             => 0,
			'last_error'           => '',
			'updated_at'           => '',
		);
		$status = get_option( self::MIGRATION_OPTION, array() );

		return wp_parse_args( is_array( $status ) ? $status : array(), $defaults );
	}

	/**
	 * Automatically start or resume any pre-release pending migration state.
	 *
	 * @return array
	 */
	public function ensure_automatic_migration() {
		$status = $this->get_migration_status();

		if ( in_array( $status['status'], array( 'pending', 'paused' ), true ) ) {
			$status['status']     = 'running';
			$status['last_error'] = '';
			$status = $this->save_migration_status( $status );
		}

		if ( 'running' === $status['status'] ) {
			$this->schedule_migration_batch();
		}

		return $status;
	}

	/**
	 * Resume a failed/stalled operation from its stored cursor.
	 *
	 * @return array
	 */
	public function retry_migration() {
		$status               = $this->get_migration_status();
		$status['status']     = 'running';
		$status['last_error'] = '';
		$status               = $this->save_migration_status( $status );
		$this->schedule_migration_batch( 1 );

		return $status;
	}

	/**
	 * Queue one background batch without creating duplicate cron events.
	 *
	 * @param int $delay Delay in seconds.
	 * @return bool
	 */
	public function schedule_migration_batch( $delay = 10 ) {
		if ( wp_next_scheduled( self::MIGRATION_CRON_HOOK ) ) {
			return true;
		}

		return wp_schedule_single_event( time() + max( 1, absint( $delay ) ), self::MIGRATION_CRON_HOOK );
	}

	/**
	 * Process one bounded user-meta batch.
	 *
	 * @return array Migration status.
	 */
	public function run_migration_batch() {
		$status   = $this->get_migration_status();
		$identity = jet_reviews()->reviews_manager->reaction_identity;

		if ( 'running' !== $status['status'] ) {
			return $status;
		}

		$lock_name = $identity->get_lock_name( 'reaction-migration' );

		if ( ! $identity->acquire_lock( $lock_name, 0 ) ) {
			return $status;
		}

		try {
			global $wpdb;

			$batch_size = (int) apply_filters( 'jet-reviews/reactions/migration-batch-size', self::MIGRATION_BATCH_SIZE );
			$batch_size = max( 1, min( 500, $batch_size ) );
			$max_work   = (float) apply_filters( 'jet-reviews/reactions/migration-max-seconds', self::MAX_WORK_SECONDS );
			$deadline   = microtime( true ) + max( 2, min( 5, $max_work ) );
			$rows       = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT umeta_id, user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s AND umeta_id > %d ORDER BY umeta_id ASC LIMIT %d",
					Reaction_Identity::USER_META_KEY,
					absint( $status['cursor'] ),
					$batch_size
				),
				ARRAY_A
			);

			if ( ! is_array( $rows ) ) {
				return $this->fail_migration( $status, $wpdb->last_error );
			}

			if ( empty( $rows ) ) {
				$status['status']     = 'complete';
				$status['last_error'] = '';
				return $this->save_migration_status( $status );
			}

			$numeric_ids = array();

			foreach ( $rows as $row ) {
				$approval_reviews = maybe_unserialize( $row['meta_value'] );

				if ( ! is_array( $approval_reviews ) ) {
					continue;
				}

				foreach ( array_keys( $approval_reviews ) as $key ) {
					if ( ( is_int( $key ) || ctype_digit( (string) $key ) ) && absint( $key ) ) {
						$numeric_ids[] = absint( $key );
					}
				}
			}

			$numeric_map = $this->resolve_numeric_identities( $numeric_ids );
			$processed   = 0;
			$unresolved  = 0;
			$inserted    = 0;
			$cursor      = absint( $status['cursor'] );

			foreach ( $rows as $row ) {
				if ( $processed && microtime( true ) >= $deadline ) {
					break;
				}

				$user_id         = absint( $row['user_id'] );
				$candidates      = array();
				$uuid_candidates = array();
				$user_records    = array();
				$user_lock       = $identity->get_lock_name( 'approval-user-' . $user_id );
				$raw_state       = maybe_unserialize( $row['meta_value'] );
				$raw_invalid     = ! is_array( $raw_state );

				if ( ! $user_id ) {
					$unresolved++;
				} else {
					if ( ! $identity->acquire_lock( $user_lock, 5 ) ) {
						break;
					}

					try {
						// Re-read while locked: a runtime transition may have updated the
						// compatibility UUID entry after this batch was selected.
						$approval_reviews = jet_reviews()->user_manager->get_user_approval_reviews( $user_id, true );

						if ( ! is_array( $approval_reviews ) ) {
							$unresolved++;
						} else {
							if ( $raw_invalid && empty( $approval_reviews ) ) {
								$unresolved++;
							}

							foreach ( $approval_reviews as $key => $state ) {
								$reaction = self::state_to_reaction( $state );

								if ( ! $reaction ) {
									if ( ! $this->is_explicit_none_state( $state ) ) {
										$unresolved++;
									}
									continue;
								}

								$key_string = (string) $key;

								if ( 0 === strpos( $key_string, 'uuid:' ) ) {
									$uuid = strtolower( substr( $key_string, 5 ) );

									if ( $identity->is_valid_uuid( $uuid ) ) {
										$uuid_candidates[ $uuid ] = $reaction;
									} else {
										$unresolved++;
									}
								} elseif ( ( is_int( $key ) || ctype_digit( $key_string ) ) && absint( $key ) ) {
									$legacy_id = absint( $key );

									if ( isset( $numeric_map[ $legacy_id ] ) ) {
										$candidates[ $numeric_map[ $legacy_id ] ] = $reaction;
									} else {
										$resolved_identity = $this->resolve_numeric_identities( array( $legacy_id ) );

										if ( isset( $resolved_identity[ $legacy_id ] ) ) {
											$numeric_map[ $legacy_id ] = $resolved_identity[ $legacy_id ];
											$candidates[ $resolved_identity[ $legacy_id ] ] = $reaction;
										} else {
											$unresolved++;
										}
									}
								} else {
									$unresolved++;
								}
							}

							// Explicit UUID entries are the authoritative legacy format.
							$candidates = array_merge( $candidates, $uuid_candidates );

							foreach ( $candidates as $uuid => $reaction ) {
								$user_records[] = array(
									'uuid'     => $uuid,
									'user_id'  => $user_id,
									'reaction' => $reaction,
								);
							}
						}

						$user_inserted = $this->insert_legacy_records( $user_records );

						if ( false === $user_inserted ) {
							$status['cursor']             = $cursor;
							$status['processed_users']    = absint( $status['processed_users'] ) + $processed;
							$status['migrated_reactions'] = absint( $status['migrated_reactions'] ) + $inserted;
							$status['unresolved_entries'] = absint( $status['unresolved_entries'] ) + $unresolved;
							return $this->fail_migration( $status, $wpdb->last_error );
						}

						$inserted += $user_inserted;
					} finally {
						$identity->release_lock( $user_lock );
					}
				}

				$cursor = absint( $row['umeta_id'] );
				$processed++;
			}

			$status['cursor']             = $cursor;
			$status['processed_users']    = absint( $status['processed_users'] ) + $processed;
			$status['migrated_reactions'] = absint( $status['migrated_reactions'] ) + $inserted;
			$status['unresolved_entries'] = absint( $status['unresolved_entries'] ) + $unresolved;
			$status['last_error']         = '';

			if ( $processed === count( $rows ) && count( $rows ) < $batch_size ) {
				$remaining = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT umeta_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND umeta_id > %d ORDER BY umeta_id ASC LIMIT 1",
						Reaction_Identity::USER_META_KEY,
						$cursor
					)
				);

				if ( ! $remaining ) {
					$status['status'] = 'complete';
				}
			}

			return $this->save_migration_status( $status );
		} catch ( \Throwable $exception ) {
			return $this->fail_migration( $status, $exception->getMessage() );
		} finally {
			$identity->release_lock( $lock_name );
		}
	}

	/**
	 * Run one cron/admin-fallback batch and keep scheduling while incomplete.
	 *
	 * @return array Migration status.
	 */
	public function run_scheduled_migration() {
		$status = $this->run_migration_batch();

		if ( 'running' === $status['status'] ) {
			$this->schedule_migration_batch( 10 );
		} else {
			wp_clear_scheduled_hook( self::MIGRATION_CRON_HOOK );
		}

		return $status;
	}

	/**
	 * Advance a stale migration from an authorized admin request when WP-Cron
	 * is disabled or delayed. The normal request pays for at most one batch.
	 */
	public function maybe_run_admin_fallback() {
		if ( ! current_user_can( 'manage_options' ) || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) ) {
			return;
		}

		$status = $this->get_migration_status();

		if ( 'running' !== $status['status'] || ! $this->status_is_older_than( $status, self::ADMIN_FALLBACK_INTERVAL ) ) {
			return;
		}

		$this->run_scheduled_migration();
	}

	/**
	 * Bulk-resolve numeric legacy keys through live identity metadata/queue.
	 *
	 * @param array $legacy_ids Numeric keys.
	 * @return array Legacy ID => UUID.
	 */
	public function resolve_numeric_identities( $legacy_ids ) {
		$legacy_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $legacy_ids ) ) ) );

		if ( empty( $legacy_ids ) ) {
			return array();
		}

		$identity = jet_reviews()->reviews_manager->reaction_identity;
		$result   = array();

		foreach ( $identity->get_many( $legacy_ids, false ) as $review_id => $item ) {
			$result[ absint( $review_id ) ] = strtolower( $item['uuid'] );
			$result[ absint( $item['legacy_id'] ) ] = strtolower( $item['uuid'] );
		}

		$wpdb         = jet_reviews()->db->wpdb();
		$meta_table   = jet_reviews()->db->tables( 'review_meta', 'name' );
		$placeholders = implode( ',', array_fill( 0, count( $legacy_ids ), '%s' ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT legacy.meta_value AS legacy_id, uuid.meta_value AS uuid
				FROM $meta_table legacy
				INNER JOIN $meta_table uuid ON uuid.review_id = legacy.review_id AND uuid.meta_key = %s
				WHERE legacy.meta_key = %s AND legacy.meta_value IN ($placeholders)",
				array_merge( array( Reaction_Identity::UUID_META_KEY, Reaction_Identity::LEGACY_ID_META_KEY ), array_map( 'strval', $legacy_ids ) )
			),
			ARRAY_A
		);

		foreach ( (array) $rows as $row ) {
			if ( $identity->is_valid_uuid( $row['uuid'] ) ) {
				$result[ absint( $row['legacy_id'] ) ] = strtolower( $row['uuid'] );
			}
		}

		foreach ( $identity->get_cleanup_queue_items() as $uuid => $item ) {
			$legacy_id = isset( $item['legacy_id'] ) ? absint( $item['legacy_id'] ) : 0;

			if ( $legacy_id && in_array( $legacy_id, $legacy_ids, true ) && $identity->is_valid_uuid( $uuid ) ) {
				$result[ $legacy_id ] = strtolower( $uuid );
			}
		}

		return $result;
	}

	/**
	 * Show UI only when automatic progress needs administrator attention.
	 */
	public function render_migration_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status  = $this->get_migration_status();
		$failed  = 'failed' === $status['status'];
		$stalled = 'running' === $status['status']
			&& $this->status_is_older_than( $status, self::STALLED_MIGRATION_PERIOD );

		if ( ! $failed && ! $stalled ) {
			return;
		}

		$retry_url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::MIGRATION_RETRY_ACTION ),
			self::MIGRATION_RETRY_ACTION
		);
		$message = $failed
			? __( 'JetReviews could not finish the automatic review reaction migration.', 'jet-reviews' )
			: __( 'JetReviews automatic review reaction migration has stopped making progress.', 'jet-reviews' );
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php echo esc_html( $message ); ?></strong>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: processed users, 2: migrated reactions, 3: unresolved entries, 4: failures. */
						__( ' Processed users: %1$d; migrated reactions: %2$d; unresolved entries: %3$d; failures: %4$d.', 'jet-reviews' ),
						absint( $status['processed_users'] ),
						absint( $status['migrated_reactions'] ),
						absint( $status['unresolved_entries'] ),
						absint( $status['failures'] )
					)
				);
				?>
			</p>
			<?php if ( ! empty( $status['last_error'] ) ) : ?>
				<p><?php echo esc_html( $status['last_error'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! $this->supports_transactions() ) : ?>
				<p><?php esc_html_e( 'A legacy MyISAM table was detected. Advisory locks serialize requests, but full rollback guarantees are unavailable.', 'jet-reviews' ); ?></p>
			<?php endif; ?>
			<p><a class="button button-primary" href="<?php echo esc_url( $retry_url ); ?>"><?php esc_html_e( 'Retry migration', 'jet-reviews' ); ?></a></p>
		</div>
		<?php
	}

	/**
	 * Capability- and nonce-protected automatic migration retry.
	 */
	public function handle_migration_retry() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You don\'t have permissions to do this', 'jet-reviews' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::MIGRATION_RETRY_ACTION );
		$this->retry_migration();
		$this->run_scheduled_migration();

		$redirect = wp_get_referer();
		wp_safe_redirect( $redirect ? $redirect : admin_url() );
		exit;
	}

	/**
	 * Whether all involved tables provide transactional rollback.
	 *
	 * @return bool
	 */
	public function supports_transactions() {
		$wpdb   = jet_reviews()->db->wpdb();
		$tables = array(
			jet_reviews()->db->tables( 'reviews', 'name' ),
			$this->table_name(),
		);

		foreach ( $tables as $table ) {
			$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s', DB_NAME, $table ) );

			if ( 'innodb' !== strtolower( (string) $engine ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Identify a valid legacy none state (which intentionally creates no row).
	 */
	private function is_explicit_none_state( $state ) {
		return is_array( $state )
			&& array_key_exists( 'like', $state )
			&& array_key_exists( 'dislike', $state )
			&& ! filter_var( $state['like'], FILTER_VALIDATE_BOOLEAN )
			&& ! filter_var( $state['dislike'], FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Persist normalized migration state.
	 */
	private function save_migration_status( $status ) {
		$status['updated_at'] = current_time( 'mysql', true );
		update_option( self::MIGRATION_OPTION, $status, false );

		return $status;
	}

	/**
	 * Record a resumable migration error without advancing the cursor.
	 */
	private function fail_migration( $status, $error ) {
		$status['status']     = 'failed';
		$status['failures']   = absint( $status['failures'] ) + 1;
		$status['last_error'] = sanitize_text_field( (string) $error );

		return $this->save_migration_status( $status );
	}

	/**
	 * Initialize a fresh site as complete or automatically running.
	 */
	private function initialize_migration() {
		$stored_status = get_option( self::MIGRATION_OPTION, null );

		if ( is_array( $stored_status ) ) {
			return;
		}

		$status = $this->get_migration_status();

		global $wpdb;

		$first_row = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT umeta_id FROM {$wpdb->usermeta} WHERE meta_key = %s ORDER BY umeta_id ASC LIMIT 1",
				Reaction_Identity::USER_META_KEY
			)
		);

		if ( ! $first_row ) {
			$status['status'] = 'complete';
		} else {
			$status['status'] = 'running';
		}

		$this->save_migration_status( $status );
	}

	/**
	 * Check a UTC migration timestamp against a bounded age.
	 *
	 * @param array $status Migration status.
	 * @param int   $seconds Maximum age in seconds.
	 * @return bool
	 */
	private function status_is_older_than( $status, $seconds ) {
		if ( empty( $status['updated_at'] ) ) {
			return true;
		}

		$updated = strtotime( $status['updated_at'] . ' UTC' );

		return ! $updated || $updated <= time() - max( 1, absint( $seconds ) );
	}
}
