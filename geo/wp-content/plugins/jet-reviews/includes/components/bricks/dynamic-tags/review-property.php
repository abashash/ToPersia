<?php
namespace Jet_Reviews\Bricks\Dynamic_Tags;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Review_Property extends Base_Tag {

	/**
	 * Return tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'jet_reviews_review_property';
	}

	/**
	 * Return tag label.
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Review Property', 'jet-reviews' );
	}

	/**
	 * Render tag value.
	 *
	 * Usage: {jet_reviews_review_property[:property]}.
	 *
	 * @param array    $args    Tag arguments.
	 * @param \WP_Post $post    Current post object.
	 * @param string   $context Bricks render context.
	 * @return mixed
	 */
	public function render( $args, $post, $context = 'text' ) {
		$prop = $this->get_arg( $args, 0, 'rating' );

		if ( ! in_array( $prop, $this->get_allowed_review_properties(), true ) ) {
			return '';
		}

		$current_object = $this->get_current_jet_engine_object();

		if ( ! $current_object ) {
			return '';
		}

		$prefixed_prop = 'jet_reviews::' . $prop;

		if ( isset( $current_object->$prefixed_prop ) ) {
			return $current_object->$prefixed_prop;
		}

		if ( isset( $current_object->$prop ) ) {
			return $current_object->$prop;
		}

		return '';
	}

	/**
	 * Return allowed review object properties.
	 *
	 * @return array
	 */
	protected function get_allowed_review_properties() {
		return array(
			'id',
			'post_id',
			'post_type',
			'author',
			'date',
			'title',
			'content',
			'rating',
			'likes',
			'dislikes',
		);
	}
}
