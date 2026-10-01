<?php
namespace Jet_Reviews\Elementor\Dynamic_Tags;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Reviews_Info extends \Elementor\Core\DynamicTags\Tag {

	public function get_name() {
		return 'reviews-info';
	}

	public function get_title() {
		return __( 'Reviews Info', 'jet-reviews' );
	}

	public function get_group() {
		return 'jet_reviews';
	}

	public function get_categories() {
		return array(
			Module::TEXT_CATEGORY,
		);
	}

	protected function register_controls() {

		$this->add_control(
			'info_type',
			array(
				'label'   => __( 'Type', 'jet-reviews' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'reviews_count',
				'options' => array(
					'reviews_count'       => esc_html__( 'Reviews Count', 'jet-reviews' ),
					'reviews_count_label' => esc_html__( 'Reviews Count Label', 'jet-reviews' ),
				),
			)
		);

	}

	public function get_content( array $options = [] ) {

		$settings = $this->get_settings();

		ob_start();

		$this->render();

		$value = ob_get_clean();

		if ( '0' === trim( $value ) && ! \Elementor\Utils::is_empty( $settings, 'fallback' ) ) {
			$value = wp_kses_post_deep( $settings['fallback'] );
		} elseif ( ! \Elementor\Utils::is_empty( $value ) ) {
			if ( ! \Elementor\Utils::is_empty( $settings, 'before' ) ) {
				$value = wp_kses_post( $settings['before'] ) . $value;
			}

			if ( ! \Elementor\Utils::is_empty( $settings, 'after' ) ) {
				$value .= wp_kses_post( $settings['after'] );
			}
		} elseif ( ! \Elementor\Utils::is_empty( $settings, 'fallback' ) ) {
			$value = wp_kses_post_deep( $settings['fallback'] );
		}

		// Some Elementor widgets treat a plain "0" as empty on frontend.
		if ( '0' === trim( $value ) ) {
			$value = sprintf(
				'<span id="elementor-tag-%1$s" class="elementor-tag">%2$s</span>',
				esc_attr( $this->get_id() ),
				$value
			);
		}

		return $value;
	}

	public function render() {

		$settings = $this->get_settings();

		$info_type = $settings['info_type'];

		$post_id = get_the_ID();
		
		if ( function_exists( 'jet_engine' ) ) {
			$post_id = jet_engine()->listings->data->get_current_object_id();
		}

		$table_name = jet_reviews()->db->tables( 'reviews', 'name' );

		$query = jet_reviews()->db->wpdb()->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND approved=1",
			$post_id
		);

		$reviews_count = (float) jet_reviews()->db->wpdb()->get_var( $query );
		$return_value  = $reviews_count;

		switch ( $info_type ) {
			case 'reviews_count':
				$return_value = $reviews_count;

				break;

			case 'reviews_count_label':
				$return_value = sprintf( _n( '1 Review', '%s Reviews', $reviews_count, 'jet-reviews' ), $reviews_count );

				break;
		}

		echo esc_html( $return_value );

	}

}
