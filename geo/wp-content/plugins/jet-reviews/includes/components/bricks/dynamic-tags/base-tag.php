<?php
namespace Jet_Reviews\Bricks\Dynamic_Tags;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

abstract class Base_Tag {

	/**
	 * Return Bricks dynamic tag name without curly braces.
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Return dynamic tag label.
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Render tag value.
	 *
	 * @param array    $args    Tag arguments.
	 * @param \WP_Post $post    Current post object.
	 * @param string   $context Bricks render context.
	 * @return mixed
	 */
	abstract public function render( $args, $post, $context = 'text' );

	/**
	 * Return tag data for Bricks builder.
	 *
	 * @return array
	 */
	public function get_builder_tag() {
		return array(
			'name'     => '{' . $this->get_name() . '}',
			'label'    => $this->get_label(),
			'group'    => 'JetReviews',
			'provider' => 'jet-reviews',
		);
	}

	/**
	 * Get an argument by index.
	 *
	 * @param array $args    Arguments.
	 * @param int   $index   Argument index.
	 * @param mixed $default Default value.
	 * @return mixed
	 */
	protected function get_arg( $args, $index, $default = '' ) {
		return isset( $args[ $index ] ) && '' !== $args[ $index ] ? $args[ $index ] : $default;
	}

	/**
	 * Format average rating.
	 *
	 * @param float  $average_rating Rating in percent.
	 * @param string $average_type   Output type.
	 * @param int    $ratio_bound    Ratio bound.
	 * @param int    $decimal_count  Decimal count.
	 * @return string
	 */
	protected function format_average_rating( $average_rating, $average_type, $ratio_bound, $decimal_count ) {
		if ( 'ratio' === $average_type ) {
			$ratio_bound    = $ratio_bound ? $ratio_bound : 5;
			$average_rating = ( $average_rating / 100 ) * $ratio_bound;
		}

		return number_format( $average_rating, $decimal_count ? $decimal_count : 1 );
	}

	/**
	 * Get current JetEngine listing object.
	 *
	 * @return mixed
	 */
	protected function get_current_jet_engine_object() {
		if ( ! function_exists( 'jet_engine' ) ) {
			return false;
		}

		return jet_engine()->listings->data->get_current_object();
	}
}
