<?php
namespace Jet_Reviews\Bricks;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Manager {

	/**
	 * Dynamic tags integration instance.
	 *
	 * @var Dynamic_Tags\Module
	 */
	public $dynamic_tags;

	/**
	 * Constructor for the class.
	 */
	public function __construct() {
		require_once jet_reviews()->plugin_path( 'includes/components/bricks/dynamic-tags/module.php' );

		$this->dynamic_tags = new Dynamic_Tags\Module();

		add_action( 'init', array( $this, 'register_elements' ), 11 );
		add_filter( 'bricks/builder/i18n', array( $this, 'register_builder_i18n' ) );
		add_filter( 'jet-reviews/source/source-post/current-id', array( $this, 'maybe_modify_post_source_id' ), 10, 2 );
		add_filter( 'get_post_metadata', array( $this, 'normalize_bricks_content_metadata' ), 10, 4 );
	}

	/**
	 * Check whether Bricks element APIs are available.
	 *
	 * @return bool
	 */
	public function is_bricks_available() {
		return class_exists( '\Bricks\Elements' );
	}

	/**
	 * Register JetReviews Bricks elements.
	 *
	 * @return void
	 */
	public function register_elements() {

		if ( ! $this->is_bricks_available() ) {
			return;
		}

		\Bricks\Elements::register_element(
			jet_reviews()->plugin_path( 'includes/components/bricks/elements/reviews-listing.php' ),
			'jet-reviews-listing',
			'\Jet_Reviews\Bricks\Elements\Reviews_Listing'
		);
	}

	/**
	 * Register the custom JetReviews element category label in Bricks builder.
	 *
	 * @param array $i18n Builder labels.
	 * @return array
	 */
	public function register_builder_i18n( $i18n ) {
		$i18n['jet-reviews'] = esc_html__( 'JetReviews', 'jet-reviews' );

		return $i18n;
	}

	/**
	 * Resolve the edited/previewed Bricks post for post-source reviews.
	 *
	 * @param mixed $current_id Current source ID.
	 * @return mixed
	 */
	public function maybe_modify_post_source_id( $current_id ) {

		if ( ! $this->is_bricks_request() ) {
			return $current_id;
		}

		foreach ( array( 'postId', 'post_id', 'post', 'preview_id', 'p', 'page_id' ) as $key ) {
			if ( empty( $_REQUEST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Bricks preview context.
				continue;
			}

			$post_id = absint( wp_unslash( $_REQUEST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Bricks preview context.

			if ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		return $current_id;
	}

	/**
	 * Check if the current request is related to Bricks rendering/editing.
	 *
	 * @return bool
	 */
	public function is_bricks_request() {

		if ( ! $this->is_bricks_available() && ! defined( 'BRICKS_VERSION' ) ) {
			return false;
		}

		if ( isset( $_REQUEST['bricks'] ) || isset( $_REQUEST['bricks_preview'] ) || isset( $_REQUEST['bricks_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Bricks request detection.
			return true;
		}

		if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) {
			return true;
		}

		if ( function_exists( 'bricks_is_builder_iframe' ) && bricks_is_builder_iframe() ) {
			return true;
		}

		return false;
	}

	/**
	 * Normalize saved Bricks content when it is read by the builder/frontend.
	 *
	 * Bricks icon controls do not display PHP control defaults when the element
	 * setting key is missing, so old/new unsaved JetReviews elements need real
	 * default setting values in the data returned to Bricks.
	 *
	 * @param mixed  $value Current metadata short-circuit value.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key Metadata key.
	 * @param bool   $single Whether a single value was requested.
	 * @return mixed
	 */
	public function normalize_bricks_content_metadata( $value, $object_id, $meta_key, $single ) {

		if ( ! defined( 'BRICKS_VERSION' ) && ! $this->is_bricks_available() ) {
			return $value;
		}

		if ( null !== $value || ! $this->is_bricks_content_meta_key( $meta_key ) ) {
			return $value;
		}

		$raw_value = $this->get_raw_post_meta_value( $object_id, $meta_key );

		if ( null === $raw_value ) {
			return $value;
		}

		$elements = maybe_unserialize( $raw_value );

		if ( ! is_array( $elements ) ) {
			return $value;
		}

		$elements = $this->normalize_elements_settings( $elements );

		return array( $elements );
	}

	/**
	 * Check whether the metadata key stores Bricks content elements.
	 *
	 * @param string $meta_key Metadata key.
	 * @return bool
	 */
	public function is_bricks_content_meta_key( $meta_key ) {
		$content_meta_key = defined( 'BRICKS_DB_PAGE_CONTENT' ) ? BRICKS_DB_PAGE_CONTENT : '_bricks_page_content_2';

		return $content_meta_key === $meta_key;
	}

	/**
	 * Read a raw post meta value without recursively invoking get_post_meta().
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Metadata key.
	 * @return string|null
	 */
	public function get_raw_post_meta_value( $post_id, $meta_key ) {
		global $wpdb;

		$value = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s LIMIT 1",
				$post_id,
				$meta_key
			)
		);

		return null === $value ? null : $value;
	}

	/**
	 * Normalize every JetReviews Bricks element in a Bricks content tree.
	 *
	 * @param array $elements Bricks elements.
	 * @return array
	 */
	public function normalize_elements_settings( $elements ) {

		foreach ( $elements as $index => $element ) {
			if ( empty( $element['name'] ) || 'jet-reviews-listing' !== $element['name'] ) {
				continue;
			}

			$settings = ! empty( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();

			$elements[ $index ]['settings'] = $this->get_normalized_reviews_listing_settings( $settings );
		}

		return $elements;
	}

	/**
	 * Add missing default values expected by the Bricks editor controls.
	 *
	 * @param array $settings Element settings.
	 * @return array
	 */
	public function get_normalized_reviews_listing_settings( $settings ) {
		$defaults = array_merge(
			array(
				'source'           => 'post',
				'ratingLayout'     => 'stars-field',
				'ratingInputType'  => 'slider-input',
				'reviewRatingType' => 'average',
				'reviewsPerPage'   => 10,
			),
			$this->get_default_icon_settings()
		);
		$is_configured = $this->has_configured_reviews_listing_settings( $settings );

		foreach ( $defaults as $key => $default ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				$settings[ $key ] = $default;
			}
		}

		foreach ( $this->get_visibility_control_defaults() as $key => $default ) {
			if ( ! array_key_exists( $key, $settings ) ) {
				$settings[ $key ] = $is_configured ? false : $default;
			}
		}

		return $settings;
	}

	/**
	 * Check whether Bricks has already saved element settings.
	 *
	 * Bricks omits unchecked checkbox controls from saved element settings. For a saved
	 * element, a missing visibility key therefore means false; for a brand new empty
	 * element it still needs to use the JetReviews default.
	 *
	 * @param array $settings Element settings.
	 * @return bool
	 */
	public function has_configured_reviews_listing_settings( $settings ) {
		$configured_keys = array(
			'source',
			'ratingLayout',
			'ratingInputType',
			'reviewRatingType',
			'reviewsPerPage',
			'reviewTitleInputVisible',
			'reviewContentInputVisible',
			'commentAuthorAvatarVisible',
			'reviewAuthorAvatarVisible',
			'reviewTitleVisible',
		);

		foreach ( $configured_keys as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return defaults for visibility controls.
	 *
	 * @return array
	 */
	public function get_visibility_control_defaults() {
		return array(
			'reviewAuthorAvatarVisible'  => true,
			'reviewTitleVisible'         => true,
			'reviewTitleInputVisible'    => true,
			'reviewContentInputVisible'  => true,
			'commentAuthorAvatarVisible' => true,
		);
	}

	/**
	 * Return default Bricks icon settings matching Elementor defaults.
	 *
	 * @return array
	 */
	public function get_default_icon_settings() {
		return array(
			'empty_star_icon'           => array( 'library' => 'fontawesomeRegular', 'icon' => 'far fa-star' ),
			'filled_star_icon'          => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-star' ),
			'new_review_button_icon'    => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-pen' ),
			'show_comments_button_icon' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-comment' ),
			'new_comment_button_icon'   => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-pen' ),
			'pinned_icon'               => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-thumbtack' ),
			'review_empty_like_icon'    => array( 'library' => 'fontawesomeRegular', 'icon' => 'far fa-thumbs-up' ),
			'review_filled_like_icon'   => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-thumbs-up' ),
			'review_empty_dislike_icon' => array( 'library' => 'fontawesomeRegular', 'icon' => 'far fa-thumbs-down' ),
			'review_filled_dislike_icon' => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-thumbs-down' ),
			'reply_button_icon'         => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-reply' ),
			'prev_icon'                 => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-chevron-left' ),
			'next_icon'                 => array( 'library' => 'fontawesomeSolid', 'icon' => 'fas fa-chevron-right' ),
		);
	}
}
