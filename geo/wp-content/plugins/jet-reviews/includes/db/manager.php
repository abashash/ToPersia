<?php
namespace Jet_Reviews\DB;

/**
 * Database manager class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Base DB class
 */
class Manager {

	/**
	 * Option key for the DB schema version.
	 *
	 * @var string
	 */
	const DB_SCHEMA_VERSION_OPTION = 'jet_reviews_db_schema_version';

	/**
	 * Dedicated reaction schema version. This is intentionally independent of
	 * the plugin version so an interrupted upgrade can safely be retried.
	 */
	const REACTION_SCHEMA_VERSION_OPTION = 'jet_reviews_reaction_schema_version';
	const REACTION_SCHEMA_VERSION        = '1';

	/**
	 * A reference to an instance of this class.
	 *
	 * @since 1.0.0
	 * @var   object
	 */
	private static $instance = null;

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
		self::init_db_required();
	}

	/**
	 * [wpdb description]
	 * @return [type] [description]
	 */
	public function wpdb() {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Return table name by key
	 *
	 * @param  string $table table key.
	 * @return array|string
	 */
	public static function tables( $table = null, $return = 'all' ) {

		global $wpdb;

		$prefix = 'jet_';

		$tables = array(
			'reviews' => array(
				'name'        => $wpdb->prefix . $prefix . 'reviews',
				'export_name' => $prefix . 'reviews',
				'query'       => "
					id bigint(20) NOT NULL AUTO_INCREMENT,
					post_id bigint(20),
					post_type text,
					author varchar(255),
					date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
					title text,
					content longtext,
					type_slug text,
					rating_data longtext,
					rating int,
					likes bigint(20) DEFAULT 0 NOT NULL,
					dislikes bigint(20) DEFAULT 0 NOT NULL,
					approved varchar(20) DEFAULT 1 NOT NULL,
					pinned varchar(20) DEFAULT 0 NOT NULL,
					PRIMARY KEY (id)
				",
			),
			'review_meta' => array(
				'name'        => $wpdb->prefix . $prefix . 'review_meta',
				'export_name' => $prefix . 'review_meta',
				'query'       => "
					id bigint(20) NOT NULL AUTO_INCREMENT,
					review_id bigint(20),
					meta_key varchar(255),
					meta_value longtext,
					PRIMARY KEY (id),
					KEY review_id_meta_key (review_id, meta_key(191)),
					KEY meta_key_uuid (meta_key(191), meta_value(36))
				",
			),
			'review_reactions' => array(
				'name'        => $wpdb->prefix . $prefix . 'review_reactions',
				'export_name' => $prefix . 'review_reactions',
				'engine'      => 'InnoDB',
				'query'       => "
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					review_uuid char(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
					user_id bigint(20) unsigned NOT NULL,
					reaction tinyint NOT NULL,
					created_at datetime NOT NULL,
					updated_at datetime NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY review_user (review_uuid, user_id),
					KEY user_review (user_id, review_uuid)
				",
			),
			'review_media' => array(
				'name'        => $wpdb->prefix . $prefix . 'review_media',
				'export_name' => $prefix . 'review_media',
				'query'       => "
					id bigint(20) NOT NULL AUTO_INCREMENT,
					review_id bigint(20),
					media_type varchar(255) DEFAULT 'image' NOT NULL,
					media_url text DEFAULT '' NOT NULL,
					created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (id)
				",
			),
			'review_types' => array(
				'name'        => $wpdb->prefix . $prefix . 'review_types',
				'export_name' => $prefix . 'review_types',
				'query'       => "
					id bigint(20) NOT NULL AUTO_INCREMENT,
					name text,
					slug varchar(255),
					description longtext DEFAULT '' NOT NULL,
					source varchar(255) DEFAULT 'post' NOT NULL,
					source_type varchar(255) DEFAULT 'post' NOT NULL,
					fields longtext DEFAULT '' NOT NULL,
					settings longtext DEFAULT '' NOT NULL,
					meta_data longtext DEFAULT '' NOT NULL,
					PRIMARY KEY (id)
				",
			),
			'review_comments' => array(
				'name'        => $wpdb->prefix . $prefix . 'review_comments',
				'export_name' => $prefix . 'review_comments',
				'query'       => "
					id bigint(20) NOT NULL AUTO_INCREMENT,
					post_id bigint(20),
					parent_id bigint(20),
					review_id bigint(20),
					author varchar(255),
					content longtext,
					date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
					likes bigint(20) DEFAULT 0 NOT NULL,
					dislikes bigint(20) DEFAULT 0 NOT NULL,
					approved varchar(20),
					PRIMARY KEY (id)
				",
			),
			'review_guests' => array(
				'name'        => $wpdb->prefix . $prefix . 'review_guests',
				'export_name' => $prefix . 'review_guests',
				'query'       => "
					id bigint(20) NOT NULL AUTO_INCREMENT,
					guest_id varchar(255),
					name varchar(255),
					mail varchar(255),
					phone varchar(255),
					meta_data longtext DEFAULT '' NOT NULL,
					PRIMARY KEY (id)
				",
			)
		);

		if ( ! $table && 'all' === $return ) {
			return $tables;
		}

		switch ( $return ) {
			case 'all':
				return isset( $tables[ $table ] ) ? $tables[ $table ] : false;

			case 'name':
				return isset( $tables[ $table ] ) ? $tables[ $table ]['name'] : false;

			case 'query':
				return isset( $tables[ $table ] ) ? $tables[ $table ]['query'] : false;
		}

		return false;

	}

	/**
	 * [init_db_required description]
	 * @return [type] [description]
	 */
	public static function init_db_required() {
		self::create_all_tables();
		self::maybe_upgrade_reaction_schema();
		self::add_default_data();

		if ( self::is_db_schema_update_required() ) {
			self::maybe_modify_tables();
			self::set_db_schema_version();
		}
	}

	/**
	 * @return string
	 */
	public static function get_target_db_schema_version() {
		return jet_reviews()->get_version();
	}

	/**
	 * @return bool
	 */
	public static function is_db_schema_update_required() {
		$current_version = get_option( self::DB_SCHEMA_VERSION_OPTION, '0.0.0' );

		return version_compare( $current_version, self::get_target_db_schema_version(), '<' );
	}

	/**
	 * @return void
	 */
	public static function set_db_schema_version() {
		update_option( self::DB_SCHEMA_VERSION_OPTION, self::get_target_db_schema_version() );
	}

	/**
	 * Create all tables on activation
	 *
	 * @return [type] [description]
	 */
	public static function create_all_tables() {

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		foreach ( self::tables() as $table ) {
			$table_name  = $table['name'];
			$table_query = $table['query'];

			if ( $table_name !== $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) ) {

				$engine = isset( $table['engine'] ) ? ' ENGINE=' . $table['engine'] : '';
				$sql = "CREATE TABLE $table_name (
					$table_query
				)$engine $charset_collate;";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

				dbDelta( $sql );
			}
		}

	}

	/**
	 * Create/upgrade reaction storage and the identity lookup indexes.
	 *
	 * dbDelta makes this safe to call again after a partial schema upgrade.
	 */
	public static function maybe_upgrade_reaction_schema() {
		if ( self::REACTION_SCHEMA_VERSION === get_option( self::REACTION_SCHEMA_VERSION_OPTION, '' ) ) {
			return;
		}

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$meta_table      = self::tables( 'review_meta' );
		$reaction_table  = self::tables( 'review_reactions' );

		dbDelta(
			"CREATE TABLE {$meta_table['name']} (
				{$meta_table['query']}
			) $charset_collate;"
		);

		dbDelta(
			"CREATE TABLE {$reaction_table['name']} (
				{$reaction_table['query']}
			) ENGINE=InnoDB $charset_collate;"
		);

		$exists = $reaction_table['name'] === $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $reaction_table['name'] )
		);
		$engine = '';

		if ( $exists ) {
			$engine = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
					DB_NAME,
					$reaction_table['name']
				)
			);

			if ( 'innodb' !== strtolower( (string) $engine ) ) {
				$wpdb->query( "ALTER TABLE {$reaction_table['name']} ENGINE=InnoDB" );
				$engine = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
						DB_NAME,
						$reaction_table['name']
					)
				);
			}
		}

		$reaction_indexes = $exists ? $wpdb->get_col( "SHOW INDEX FROM {$reaction_table['name']}", 2 ) : array();
		$meta_indexes     = $wpdb->get_col( "SHOW INDEX FROM {$meta_table['name']}", 2 );
		$schema_complete  = $exists
			&& 'innodb' === strtolower( (string) $engine )
			&& in_array( 'review_user', $reaction_indexes, true )
			&& in_array( 'user_review', $reaction_indexes, true )
			&& in_array( 'review_id_meta_key', $meta_indexes, true )
			&& in_array( 'meta_key_uuid', $meta_indexes, true );

		if ( $schema_complete ) {
			update_option( self::REACTION_SCHEMA_VERSION_OPTION, self::REACTION_SCHEMA_VERSION, false );
		}
	}

	/**
	 * [maybe_modify_tables description]
	 * @return [type] [description]
	 */
	public static function maybe_modify_tables() {
		global $wpdb;

		// Modify reviews table
		$reviews_table_name = self::tables( 'reviews', 'name' );
		$column_exist = self::table_column_exists( $reviews_table_name, 'source' );

		if ( ! $column_exist ) {
			$wpdb->query( "ALTER TABLE $reviews_table_name ADD source VARCHAR(255) NOT NULL DEFAULT 'post' AFTER id" );
		}

		$wpdb->query( "ALTER TABLE $reviews_table_name MODIFY COLUMN author VARCHAR(255)" );

		// Modify review_comments table
		$review_comments_table_name = self::tables( 'review_comments', 'name' );
		$column_exist = self::table_column_exists( $review_comments_table_name, 'source' );

		if ( ! $column_exist ) {
			$wpdb->query( "ALTER TABLE $review_comments_table_name ADD source VARCHAR(255) NOT NULL DEFAULT 'post' AFTER id" );
		}

		$wpdb->query( "ALTER TABLE $review_comments_table_name MODIFY COLUMN author VARCHAR(255)" );

		// Modify review_types table
		$review_types_table_name = self::tables( 'review_types', 'name' );
		$column_exist = self::table_column_exists( $review_types_table_name, 'source_type' );

		if ( ! $column_exist ) {
			$wpdb->query( "ALTER TABLE $review_types_table_name ADD source_type VARCHAR(255) NOT NULL DEFAULT 'post' AFTER source" );
		}

		$column_exist = self::table_column_exists( $review_types_table_name, 'settings' );

		if ( ! $column_exist ) {
			$wpdb->query( "ALTER TABLE $review_types_table_name ADD settings LONGTEXT NOT NULL DEFAULT '' AFTER source_type" );
		}
	}

	/**
	 * @param $table_name
	 * @param $column_name
	 *
	 * @return bool
	 */
	public static function table_column_exists( $table_name, $column_name ) {

		global $wpdb;

		$column = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s ",
			DB_NAME, $table_name, $column_name
		) );

		if ( ! empty( $column ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add default data.
	 */
	public static function add_default_data() {

		global $wpdb;

		$prefix = 'jet_';

		$query = $wpdb->replace( $wpdb->prefix . $prefix . 'review_types', array(
			'id'          => 1,
			'name'        => 'Default',
			'slug'        => 'default',
			'description' => '',
			'source'      => 'default',
			'fields'      => maybe_serialize( array(
				array(
					'label' => esc_html__( 'Rating', 'jet-reviews' ),
					'step'  => 1,
					'max'   => 5,
				)
			) ),
			'meta_data' => '',
		) );

	}

	/**
	 * Check if table is exists
	 *
	 * @param  string  $table Table name.
	 * @return boolean
	 */
	public function is_table_exists( $table = null ) {

		global $wpdb;

		$table_name = $this->tables( $table, 'name' );

		if ( ! $table_name ) {
			return false;
		}

		return ( $table_name === $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) );
	}

}
