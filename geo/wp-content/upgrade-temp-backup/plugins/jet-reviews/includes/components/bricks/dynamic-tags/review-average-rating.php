<?php
namespace Jet_Reviews\Bricks\Dynamic_Tags;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Review_Average_Rating extends Base_Tag {

	/**
	 * Return tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'jet_reviews_review_average_rating';
	}

	/**
	 * Return tag label.
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Review Average Rating', 'jet-reviews' );
	}

	/**
	 * Render tag value.
	 *
	 * Usage: {jet_reviews_review_average_rating[:percent|ratio][:ratio_bound][:decimal_count]}.
	 *
	 * @param array    $args    Tag arguments.
	 * @param \WP_Post $post    Current post object.
	 * @param string   $context Bricks render context.
	 * @return string
	 */
	public function render( $args, $post, $context = 'text' ) {
		$current_object = $this->get_current_jet_engine_object();

		if ( ! $current_object || ! isset( $current_object->rating ) ) {
			return '';
		}

		$average_type   = $this->get_arg( $args, 0, 'percent' );
		$ratio_bound    = absint( $this->get_arg( $args, 1, 5 ) );
		$decimal_count  = absint( $this->get_arg( $args, 2, 1 ) );
		$average_rating = (float) $current_object->rating;

		return $this->format_average_rating( $average_rating, $average_type, $ratio_bound, $decimal_count );
	}
}
