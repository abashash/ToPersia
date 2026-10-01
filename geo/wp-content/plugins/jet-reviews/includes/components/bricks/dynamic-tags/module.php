<?php
namespace Jet_Reviews\Bricks\Dynamic_Tags;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Module {

	/**
	 * Registered tag instances.
	 *
	 * @var Base_Tag[]
	 */
	protected $tags = array();

	/**
	 * Constructor for the class.
	 */
	public function __construct() {
		$this->load_tags();

		add_filter( 'bricks/dynamic_tags_list', array( $this, 'add_tags_to_builder' ) );
		add_filter( 'bricks/frontend/render_data', array( $this, 'render_content' ), 20, 3 );
		add_filter( 'bricks/dynamic_data/render_content', array( $this, 'render_content' ), 20, 3 );
		add_filter( 'bricks/dynamic_data/render_tag', array( $this, 'render_tag' ), 20, 3 );
	}

	/**
	 * Return tag class map.
	 *
	 * @return array
	 */
	public function get_tag_classes_names() {
		return apply_filters( 'jet-reviews/bricks/registered-dynamic-tags', array(
			'\\Jet_Reviews\\Bricks\\Dynamic_Tags\\Average_Rating'        => jet_reviews()->plugin_path( 'includes/components/bricks/dynamic-tags/average-rating.php' ),
			'\\Jet_Reviews\\Bricks\\Dynamic_Tags\\Reviews_Info'          => jet_reviews()->plugin_path( 'includes/components/bricks/dynamic-tags/reviews-info.php' ),
			'\\Jet_Reviews\\Bricks\\Dynamic_Tags\\Review_Average_Rating' => jet_reviews()->plugin_path( 'includes/components/bricks/dynamic-tags/review-average-rating.php' ),
			'\\Jet_Reviews\\Bricks\\Dynamic_Tags\\Review_Property'       => jet_reviews()->plugin_path( 'includes/components/bricks/dynamic-tags/review-property.php' ),
		) );
	}

	/**
	 * Load tag classes.
	 *
	 * @return void
	 */
	protected function load_tags() {
		require_once jet_reviews()->plugin_path( 'includes/components/bricks/dynamic-tags/base-tag.php' );

		foreach ( $this->get_tag_classes_names() as $tag_class => $tag_filepath ) {
			if ( file_exists( $tag_filepath ) ) {
				require_once $tag_filepath;
			}

			if ( class_exists( $tag_class ) ) {
				$tag = new $tag_class();

				$this->tags[ $tag->get_name() ] = $tag;
			}
		}
	}

	/**
	 * Add JetReviews tags to Bricks dynamic data picker.
	 *
	 * @param array $tags Registered tags.
	 * @return array
	 */
	public function add_tags_to_builder( $tags ) {
		foreach ( $this->tags as $tag ) {
			$tags[] = $tag->get_builder_tag();
		}

		return $tags;
	}

	/**
	 * Render JetReviews dynamic tags inside a content string.
	 *
	 * @param mixed    $content Content.
	 * @param \WP_Post $post    Current post object.
	 * @param string   $context Bricks render context.
	 * @return mixed
	 */
	public function render_content( $content, $post, $context = 'text' ) {
		if ( ! is_string( $content ) || false === strpos( $content, '{jet_reviews_' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/{(jet_reviews_[^}]+)}/',
			function( $matches ) use ( $post, $context ) {
				return $this->get_tag_value( $matches[1], $post, $context );
			},
			$content
		);
	}

	/**
	 * Render a single JetReviews dynamic tag.
	 *
	 * @param mixed    $value   Tag or previously rendered value.
	 * @param \WP_Post $post    Current post object.
	 * @param string   $context Bricks render context.
	 * @return mixed
	 */
	public function render_tag( $value, $post, $context = 'text' ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$tag = $this->normalize_tag( $value );

		if ( ! $this->is_jet_reviews_tag( $tag ) ) {
			return $value;
		}

		return $this->get_tag_value( $tag, $post, $context );
	}

	/**
	 * Normalize tag text.
	 *
	 * @param string $value Tag value.
	 * @return string
	 */
	protected function normalize_tag( $value ) {
		$value = html_entity_decode( trim( $value ), ENT_QUOTES, get_bloginfo( 'charset' ) );

		if ( 0 === strpos( $value, '{' ) && '}' === substr( $value, -1 ) ) {
			$value = substr( $value, 1, -1 );
		}

		return $value;
	}

	/**
	 * Check whether tag belongs to JetReviews.
	 *
	 * @param string $tag Tag name.
	 * @return bool
	 */
	protected function is_jet_reviews_tag( $tag ) {
		return 0 === strpos( $tag, 'jet_reviews_' );
	}

	/**
	 * Resolve JetReviews tag value.
	 *
	 * @param string   $tag     Tag name with optional arguments.
	 * @param \WP_Post $post    Current post object.
	 * @param string   $context Bricks render context.
	 * @return mixed
	 */
	protected function get_tag_value( $tag, $post, $context = 'text' ) {
		$parsed = $this->parse_tag( $tag );

		if ( empty( $this->tags[ $parsed['name'] ] ) ) {
			return '{' . $tag . '}';
		}

		return $this->tags[ $parsed['name'] ]->render( $parsed['args'], $post, $context );
	}

	/**
	 * Parse Bricks dynamic tag arguments.
	 *
	 * @param string $tag Tag name with optional arguments.
	 * @return array
	 */
	protected function parse_tag( $tag ) {
		$parts = array_map( 'trim', explode( ':', $tag ) );
		$name  = array_shift( $parts );

		return array(
			'name' => $name,
			'args' => $parts,
		);
	}
}
