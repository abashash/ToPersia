<?php
namespace Jet_Reviews\Bricks\Dynamic_Tags;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Reviews_Info extends Base_Tag {

	/**
	 * Return tag name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'jet_reviews_reviews_info';
	}

	/**
	 * Return tag label.
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Reviews Info', 'jet-reviews' );
	}

	/**
	 * Render tag value.
	 *
	 * Usage: {jet_reviews_reviews_info[:source][:reviews_count|reviews_count_label]}.
	 *
	 * @param array    $args    Tag arguments.
	 * @param \WP_Post $post    Current post object.
	 * @param string   $context Bricks render context.
	 * @return string
	 */
	public function render( $args, $post, $context = 'text' ) {
		$source = '';

		if ( $this->is_registered_source( $this->get_arg( $args, 0 ) ) ) {
			$source = array_shift( $args );
		}

		$info_type = $this->get_arg( $args, 0, 'reviews_count' );
		$post_id   = $source ? $this->get_source_current_id( $source, $post ) : $this->get_current_post_id( $post );

		if ( ! $post_id ) {
			return '';
		}

		$reviews_count = $this->get_post_reviews_count( $post_id, $source );

		if ( 'reviews_count_label' === $info_type ) {
			return sprintf( _n( '1 Review', '%s Reviews', $reviews_count, 'jet-reviews' ), $reviews_count );
		}

		return (string) $reviews_count;
	}

	/**
	 * Return approved reviews count for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $source  Review source.
	 * @return int
	 */
	protected function get_post_reviews_count( $post_id, $source = '' ) {
		$table_name = jet_reviews()->db->tables( 'reviews', 'name' );

		if ( $source ) {
			$query = jet_reviews()->db->wpdb()->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE source = %s AND post_id = %d AND approved=1",
				$source,
				$post_id
			);
		} else {
			$query = jet_reviews()->db->wpdb()->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND approved=1",
				$post_id
			);
		}

		return absint( jet_reviews()->db->wpdb()->get_var( $query ) );
	}

	/**
	 * Get current post ID for Bricks or JetEngine contexts.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return int
	 */
	protected function get_current_post_id( $post ) {
		if ( $post instanceof \WP_Post ) {
			return absint( $post->ID );
		}

		if ( function_exists( 'jet_engine' ) ) {
			$object_id = jet_engine()->listings->data->get_current_object_id();

			if ( $object_id ) {
				return absint( $object_id );
			}
		}

		return absint( get_the_ID() );
	}

	/**
	 * Return current object ID for a review source.
	 *
	 * @param string   $source Review source.
	 * @param \WP_Post $post   Current post object.
	 * @return int
	 */
	protected function get_source_current_id( $source, $post ) {
		if ( 'post' === $source ) {
			return $this->get_current_post_id( $post );
		}

		if ( 'user' === $source && $post instanceof \WP_Post ) {
			return absint( $post->post_author );
		}

		$source_instance = jet_reviews()->reviews_manager->sources->get_source_instance( $source );

		if ( ! $source_instance ) {
			return 0;
		}

		$source_id = absint( $source_instance->get_current_id() );

		if ( $source_id ) {
			return $source_id;
		}

		return 0;
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
