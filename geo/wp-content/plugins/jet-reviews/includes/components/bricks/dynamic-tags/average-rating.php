<?php
namespace Jet_Reviews\Bricks\Dynamic_Tags;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Average_Rating extends Base_Tag {

	/**
	 * Return tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'jet_reviews_average_rating';
	}

	/**
	 * Return tag label.
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Average Rating', 'jet-reviews' );
	}

	/**
	 * Render tag value.
	 *
	 * Usage: {jet_reviews_average_rating[:source][:percent|ratio][:ratio_bound][:decimal_count]}.
	 *
	 * @param array    $args    Tag arguments.
	 * @param \WP_Post $post    Current post object.
	 * @param string   $context Bricks render context.
	 * @return string
	 */
	public function render( $args, $post, $context = 'text' ) {
		$source = 'post';

		if ( $this->is_registered_source( $this->get_arg( $args, 0 ) ) ) {
			$source = array_shift( $args );
		}

		$average_type  = $this->get_arg( $args, 0, 'percent' );
		$ratio_bound   = absint( $this->get_arg( $args, 1, 5 ) );
		$decimal_count = absint( $this->get_arg( $args, 2, 1 ) );

		$source_instance = jet_reviews()->reviews_manager->sources->get_source_instance( $source );

		if ( ! $source_instance ) {
			return esc_html__( 'Any Sources not found', 'jet-reviews' );
		}

		$average_rating = $this->get_source_average_rating(
			$source_instance->get_slug(),
			$this->get_source_current_id( $source, $source_instance, $post )
		);

		return $this->format_average_rating( $average_rating, $average_type, $ratio_bound, $decimal_count );
	}

	/**
	 * Return current object ID for a review source.
	 *
	 * @param string $source          Review source.
	 * @param object $source_instance Review source instance.
	 * @param mixed  $post            Current post object.
	 * @return int
	 */
	protected function get_source_current_id( $source, $source_instance, $post ) {
		if ( 'post' === $source && $post instanceof \WP_Post ) {
			return absint( $post->ID );
		}

		if ( 'user' === $source && $post instanceof \WP_Post ) {
			return absint( $post->post_author );
		}

		return absint( $source_instance->get_current_id() );
	}

	/**
	 * Return source average rating.
	 *
	 * @param string $source    Review source.
	 * @param int    $source_id Review source ID.
	 * @return float
	 */
	protected function get_source_average_rating( $source, $source_id ) {
		$table_name = jet_reviews()->db->tables( 'reviews', 'name' );

		$query = jet_reviews()->db->wpdb()->prepare(
			"SELECT AVG(rating) FROM $table_name WHERE source = %s AND post_id = %d AND approved=1",
			$source,
			$source_id
		);

		return (float) jet_reviews()->db->wpdb()->get_var( $query );
	}

	/**
	 * Check whether a review source is registered.
	 *
	 * @param string $source Source slug.
	 * @return bool
	 */
	protected function is_registered_source( $source ) {
		$sources = jet_reviews()->reviews_manager->sources->get_registered_source_list();

		return isset( $sources[ $source ] );
	}
}
