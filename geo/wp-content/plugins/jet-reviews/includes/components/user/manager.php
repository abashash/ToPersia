<?php
namespace Jet_Reviews\User;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Base DB class
 */
class Manager {

	/**
	 * A reference to an instance of this class.
	 *
	 * @since 1.0.0
	 * @var   object
	 */
	private static $instance = null;

	/**
	 * [$_conditions description]
	 * @var array
	 */
	public $registered_conditions = array();

	/**
	 * [$register_verifications description]
	 * @var array
	 */
	public $registered_verifications = array();

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @return object
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Constructor for the class
	 */
	public function __construct() {
		$this->register_conditions();
		$this->register_verifications();
	}

	/**
	 * [register_conditions description]
	 * @return [type] [description]
	 */
	public function register_conditions() {

		$base_path = jet_reviews()->plugin_path( 'includes/components/user/conditions/' );

		require $base_path . 'base.php';

		$default = array(
			'\Jet_Reviews\User\Conditions\User_Guest'       => $base_path . 'user-guest.php',
			'\Jet_Reviews\User\Conditions\User_Role'        => $base_path . 'user-role.php',
			'\Jet_Reviews\User\Conditions\Moderator_Check'  => $base_path . 'moderator-check.php',
			'\Jet_Reviews\User\Conditions\Already_Reviewed' => $base_path . 'already-reviewed.php',
		);

		foreach ( $default as $class => $file ) {
			require $file;

			$this->register_condition( $class );
		}

		/**
		 * You could register custom conditions on this hook.
		 * Note - each condition should be presented like instance of class 'Jet_Reviews\User\Conditions\Base_Condition'
		 */
		do_action( 'jet-reviews/user/conditions/register', $this );

	}

	/**
	 * [register_condition description]
	 * @param  [type] $class [description]
	 * @return [type]        [description]
	 */
	public function register_condition( $class ) {

		$instance = new $class;
		$this->registered_conditions[ $instance->get_slug() ] = $instance;
	}

	/**
	 * [get_condition description]
	 * @param  [type] $class [description]
	 * @return [type]        [description]
	 */
	public function get_condition( $slug ) {

		if ( array_key_exists( $slug, $this->registered_conditions ) ) {
			return $this->registered_conditions[ $slug ];
		}

		return false;
	}

	/**
	 * [register_conditions description]
	 * @return [type] [description]
	 */
	public function register_verifications() {

		$base_path = jet_reviews()->plugin_path( 'includes/components/user/verifications/' );

		require_once $base_path . 'base.php';

		$default = array(
			'\Jet_Reviews\User\Verifications\Guest_User' => $base_path . 'guest-user.php',
		);

		foreach ( $default as $class => $file ) {
			require $file;

			$this->register_verification( $class );
		}

		/**
		 * You could register custom conditions on this hook.
		 * Note - each condition should be presented like instance of class 'Jet_Reviews\User\Verifications\Base_Verification'
		 */
		do_action( 'jet-reviews/user/verifications/register', $this );

	}

	/**
	 * [register_condition description]
	 * @param  [type] $class [description]
	 * @return [type]        [description]
	 */
	public function register_verification( $class ) {
		$instance = new $class;
		$this->registered_verifications[ $instance->get_slug() ] = $instance;
	}

	/**
	 * [get_condition description]
	 * @param  [type] $class [description]
	 * @return [type]        [description]
	 */
	public function get_verification( $slug ) {

		if ( array_key_exists( $slug, $this->registered_verifications ) ) {
			return $this->registered_verifications[ $slug ];
		}

		return false;
	}

	/**
	 * [get_verification_data description]
	 * @param  [type] $slug [description]
	 * @return [type]       [description]
	 */
	public function get_verification_data( $verifications = array(), $args = array() ) {

		if ( empty( $verifications ) ) {
			return false;
		}

		$verification_data = array();

		foreach ( $verifications as $key => $slug ) {
			$verification_instance = $this->get_verification( $slug );

			if ( ! $verification_instance ) {
				continue;
			}

			$check = $verification_instance->check( $args );

			if ( ! $check ) {
				continue;
			}

			$verification_data[] = array(
				'slug'    => $verification_instance->get_slug(),
				'icon'    => $verification_instance->get_icon(),
				'message' => $verification_instance->get_message(),
			);
		}

		return $verification_data;
	}

	/**
	 * [get_post_types_options description]
	 * @return [type] [description]
	 */
	public function get_verification_options() {

		$registered_verifications = $this->registered_verifications;

		if ( empty( $registered_verifications ) ) {
			return array();
		}

		$verification_options_options = array();

		foreach ( $registered_verifications as $slug => $verification ) {
			$verification_options_options[] = array(
				'label' => $verification->get_name(),
				'value' => $slug,
			);
		}

		return $verification_options_options;
	}

	/**
	 * @param string $source_type
	 * @param false $user_data
	 *
	 * @return array
	 */
	public function is_user_can_review( $user_data = false, $review_type_settings = false ) {

		if ( ! $user_data ) {
			return [
				'allowed' => true,
				'code'    => 'can_review',
				'message' => __( '*Publish your review', 'jet-reviews' ),
			];
		}

		foreach ( $this->registered_conditions as $slug => $instance ) {
			$condition_type = $instance->get_type();

			if ( 'can-review' !== $condition_type ) {
				continue;
			}

			$instance_check = $instance->check(  $user_data, $review_type_settings );

			if ( ! $instance_check ) {
				return [
					'allowed' => false,
					'code'    => $instance->get_slug(),
					'message' => $instance->get_invalid_message(),
				];
			}
		}

		return [
			'allowed' => true,
			'code'    => 'can_review',
			'message' => __( '*Publish your review', 'jet-reviews' ),
		];
	}

	/**
	 * @param int    $user_id
	 * @param string $source
	 * @param int    $source_id
	 *
	 * @return bool
	 */
	public function is_review_submission_hidden_for_post_author( $user_id = 0, $source = 'post', $source_id = 0 ) {

		$setting = jet_reviews()->settings->get( 'hide-post-author-review-control', array(
			'enable' => false,
		) );

		if ( empty( $setting['enable'] ) ) {
			return false;
		}

		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return false;
		}

		$post_author_id = $this->get_current_post_author_id( $source, $source_id );

		if ( ! $post_author_id ) {
			return false;
		}

		return $user_id === $post_author_id;
	}

	/**
	 * @param string $source
	 * @param int    $source_id
	 *
	 * @return int
	 */
	public function get_current_post_author_id( $source = 'post', $source_id = 0 ) {

		$post_id = 0;

		if ( 'post' === $source ) {
			$post_id = absint( $source_id );

			if ( ! $post_id ) {
				$post_id = get_the_ID();
			}
		} elseif ( is_singular() ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return 0;
		}

		return (int) get_post_field( 'post_author', $post_id );
	}

	/**
	 * [get_raw_user_data description]
	 * @return [type] [description]
	 */
	public function get_raw_user_data( $user_id = false ) {

		if ( false === $user_id ) {
			$user_id = get_current_user_id();
		}

		$user_data = get_user_by( 'id', $user_id );

		if ( ! $user_data ) {

			$guest_data = $this->get_guest_by_id( $user_id );

			if ( $guest_data ) {
				return apply_filters( 'jet-reviews/user-manager/raw-user-data', array(
					'id'     => $guest_data['guest_id'],
					'name'   => $guest_data['name'],
					'mail'   => $guest_data['mail'],
					'avatar' => get_avatar( $guest_data['mail'], 64 ),
					'roles'  => [ 'guest' ],
				) );
			}

			return apply_filters( 'jet-reviews/user-manager/raw-user-data', array(
				'id'     => 0,
				'name'   => esc_html__( 'Guest', 'jet-reviews' ),
				'mail'   => 'email@example.com',
				'avatar' => get_avatar( 'email@example.com', 64 ),
				'roles'  => [ 'guest' ],
			) );
		}

		return apply_filters( 'jet-reviews/user-manager/raw-user-data', array(
			'id'     => $user_data->data->ID,
			'name'   => $user_data->data->display_name,
			'mail'   => $user_data->data->user_email,
			'avatar' => get_avatar( $user_data->data->user_email, 64 ),
			'roles'  => array_values( $user_data->roles ),
		) );
	}

	/**
	 * [add_user_reviewed_post_id description]
	 */
	public function update_user_approval_review( $review_id = false, $data = array() ) {

		if ( ! $review_id || empty( $data ) ) {
			return false;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$identity = jet_reviews()->reviews_manager->reaction_identity->ensure( $review_id );

		if ( ! $identity ) {
			return false;
		}

		return jet_reviews()->reviews_manager->reaction_storage->persist( $identity['uuid'], $user_id, $data );
	}

	/**
	 * [add_user_reviewed_post_id description]
	 */
	public function delete_user_approval_review( $review_id = false ) {

		if ( ! $review_id ) {
			return false;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$identity = jet_reviews()->reviews_manager->reaction_identity->get( $review_id );

		if ( ! $identity ) {
			return false;
		}

		return jet_reviews()->reviews_manager->reaction_storage->persist(
			$identity['uuid'],
			$user_id,
			array( 'like' => false, 'dislike' => false )
		);

	}

	/**
	 * [is_post_already_reviewed description]
	 * @return boolean [description]
	 */
	public function get_review_approval_data( $review_id, $identity = false ) {

		$empty_state = array(
			'like'    => false,
			'dislike' => false,
		);

		if ( ! $identity ) {
			$identity = jet_reviews()->reviews_manager->reaction_identity->ensure( $review_id );
		}

		if ( ! $identity ) {
			return $empty_state;
		}

		$approval_data = $this->get_reviews_approval_data( array( absint( $review_id ) => $identity ) );

		return isset( $approval_data[ $review_id ] ) ? $approval_data[ $review_id ] : $empty_state;
	}

	/**
	 * Resolve approval state for a batch of reviews under one user lock/read.
	 *
	 * @param array $identities Review identities keyed by current review ID.
	 * @return array
	 */
	public function get_reviews_approval_data( $identities ) {
		$empty_state = array(
			'like'    => false,
			'dislike' => false,
		);
		$result = array();

		foreach ( (array) $identities as $review_id => $identity ) {
			$result[ absint( $review_id ) ] = $empty_state;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id || empty( $identities ) ) {
			return $result;
		}

		// Keep lightweight consumers that bootstrap only the legacy manager usable.
		if ( empty( jet_reviews()->reviews_manager->reaction_storage ) ) {
			$identity_helper = jet_reviews()->reviews_manager->reaction_identity;
			$lock_name       = $identity_helper->get_lock_name( 'approval-user-' . $user_id );

			if ( ! $identity_helper->acquire_lock( $lock_name ) ) {
				return $result;
			}

			try {
				$approval_reviews = $this->get_user_approval_reviews( $user_id, true );
				$migrated         = false;

				foreach ( $identities as $review_id => $identity ) {
					$review_id = absint( $review_id );

					if ( ! $review_id || empty( $identity['uuid'] ) || empty( $identity['legacy_id'] ) ) {
						continue;
					}

					$resolved             = $this->resolve_review_approval( $approval_reviews, $review_id, $identity );
					$result[ $review_id ] = $resolved['state'];

					if ( $resolved['migrated'] ) {
						$approval_reviews = $resolved['approval_reviews'];
						$migrated = true;
					}
				}

				if ( $migrated ) {
					$this->save_user_approval_reviews( $user_id, $approval_reviews );
				}

				return $result;
			} finally {
				$identity_helper->release_lock( $lock_name );
			}
		}

		$storage        = jet_reviews()->reviews_manager->reaction_storage;
		$uuid_to_review = array();

		foreach ( $identities as $review_id => $identity ) {
			if ( ! empty( $identity['uuid'] ) ) {
				$uuid_to_review[ strtolower( $identity['uuid'] ) ] = absint( $review_id );
			}
		}

		$stored = $storage->get_many( $user_id, array_keys( $uuid_to_review ) );

		foreach ( $stored as $uuid => $state ) {
			if ( isset( $uuid_to_review[ $uuid ] ) ) {
				$result[ $uuid_to_review[ $uuid ] ] = $state;
			}
		}

		if ( $storage->is_migration_complete() || count( $stored ) === count( $uuid_to_review ) ) {
			return $result;
		}

		// One legacy read covers every missing UUID while the migration is active.
		$approval_reviews = $this->get_user_approval_reviews( $user_id, true );

		foreach ( $identities as $review_id => $identity ) {
			$review_id = absint( $review_id );
			$uuid      = ! empty( $identity['uuid'] ) ? strtolower( $identity['uuid'] ) : '';

			if ( ! $review_id || ! $uuid || isset( $stored[ $uuid ] ) ) {
				continue;
			}

			$resolved             = $this->resolve_review_approval( $approval_reviews, $review_id, $identity );
			$result[ $review_id ] = $resolved['state'];
		}

		return $result;
	}

	/**
	 * Keep a fallback entry aligned with a runtime transition until its legacy
	 * migration batch has passed. The meta row itself is retained for rollback.
	 *
	 * @param int   $user_id User ID.
	 * @param array $identity Stable review identity.
	 * @param array $state New authoritative state.
	 * @return bool
	 */
	public function sync_legacy_review_approval( $user_id, $identity, $state ) {
		$user_id = absint( $user_id );

		if ( ! $user_id || empty( $identity['uuid'] ) ) {
			return false;
		}

		$approval_reviews = $this->get_user_approval_reviews( $user_id, true );
		$approval_reviews[ 'uuid:' . strtolower( $identity['uuid'] ) ] = \Jet_Reviews\Reviews\Reaction_Identity::normalize_state( $state );

		return $this->save_user_approval_reviews( $user_id, $approval_reviews );
	}

	/**
	 * Read the complete approval map for a user.
	 *
	 * @param int  $user_id User ID.
	 * @param bool $uncached Read persisted data directly.
	 * @return array
	 */
	public function get_user_approval_reviews( $user_id, $uncached = false ) {
		$user_id = absint( $user_id );

		if ( ! $user_id ) {
			return array();
		}

		if ( ! $uncached ) {
			$approval_reviews = get_user_meta( $user_id, 'jet-approval-reviews', true );

			return is_array( $approval_reviews ) ? $approval_reviews : array();
		}

		global $wpdb;

		$raw_value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s ORDER BY umeta_id ASC LIMIT 1",
				$user_id,
				'jet-approval-reviews'
			)
		);
		$approval_reviews = maybe_unserialize( $raw_value );

		return is_array( $approval_reviews ) ? $approval_reviews : array();
	}

	/**
	 * Persist the complete approval map.
	 *
	 * @param int   $user_id User ID.
	 * @param array $approval_reviews Approval map.
	 * @return bool
	 */
	public function save_user_approval_reviews( $user_id, $approval_reviews ) {
		if ( ! $user_id || ! is_array( $approval_reviews ) ) {
			return false;
		}

		return false !== update_user_meta( $user_id, 'jet-approval-reviews', $approval_reviews );
	}

	/**
	 * Resolve UUID state and lazily copy a compatible numeric state.
	 *
	 * @param array $approval_reviews Complete approval map.
	 * @param int   $review_id Current review ID.
	 * @param array $identity Stable identity.
	 * @return array
	 */
	public function resolve_review_approval( $approval_reviews, $review_id, $identity ) {
		$approval_reviews = is_array( $approval_reviews ) ? $approval_reviews : array();
		$uuid_key         = 'uuid:' . strtolower( $identity['uuid'] );
		$migrated         = false;

		if ( array_key_exists( $uuid_key, $approval_reviews ) ) {
			$state = $approval_reviews[ $uuid_key ];
		} else {
			$state      = array( 'like' => false, 'dislike' => false );
			$legacy_ids = array_unique( array_filter( array( absint( $review_id ), absint( $identity['legacy_id'] ) ) ) );

			foreach ( $legacy_ids as $legacy_id ) {
				if ( array_key_exists( $legacy_id, $approval_reviews ) ) {
					$state = $approval_reviews[ $legacy_id ];
					$approval_reviews[ $uuid_key ] = \Jet_Reviews\Reviews\Reaction_Identity::normalize_state( $state );
					$migrated = true;
					break;
				}
			}
		}

		return array(
			'state'            => \Jet_Reviews\Reviews\Reaction_Identity::normalize_state( $state ),
			'approval_reviews' => $approval_reviews,
			'uuid_key'         => $uuid_key,
			'migrated'         => $migrated,
		);
	}


	/**
	 * [delete_review_by_id description]
	 * @param  integer $id [description]
	 * @return [type]      [description]
	 */
	public function add_new_guest( $data = array() ) {

		if ( empty( $data ) ) {
			return false;
		}

		global $wpdb;

		$table_name = jet_reviews()->db->tables( 'review_guests', 'name' );

		$prepare_data = array(
			'guest_id'  => $data['guest_id'],
			'name'      => $data['name'],
			'mail'      => $data['mail'],
			'phone'     => '',
			'meta_data' => '',
		);

		$count_query = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE mail = %s",
			$prepare_data['mail']
		) );

		if ( '0' === $count_query ) {
			$query = $wpdb->insert( $table_name, $prepare_data );
		}

		if ( ! $query ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * [get_guest_by_id description]
	 * @param  boolean $guest_id [description]
	 * @return [type]            [description]
	 */
	public function get_guest_by_id( $guest_id = false ) {

		if ( ! $guest_id ) {
			return false;
		}

		global $wpdb;

		$table_name = jet_reviews()->db->tables( 'review_guests', 'name' );

		$query = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE guest_id = %s",
			$guest_id
		), ARRAY_A );

		if ( ! $query ) {
			return false;
		}

		return $query;

	}

	/**
	 * @param $email
	 *
	 * @return array|false|object|\stdClass|void
	 */
	public function get_guest_by_email( $email = false ) {

		if ( ! $email ) {
			return false;
		}

		global $wpdb;

		$table_name = jet_reviews()->db->tables( 'review_guests', 'name' );

		$query = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE mail = %s",
			$email
		), ARRAY_A );

		if ( ! $query ) {
			return false;
		}

		return $query;

	}

}
