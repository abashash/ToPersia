<?php
/**
 * Elementor widget rendering the buttons or the AI panel.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * One widget, two modes.
 */
class TP_AI_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'tpas_ai';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'ToPersia AI', 'topersia-ai-summarizer' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-code';
	}

	/**
	 * Widget category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'basic' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'tpas_section',
			array(
				'label' => __( 'ToPersia AI', 'topersia-ai-summarizer' ),
			)
		);

		$this->add_control(
			'mode',
			array(
				'label'   => __( 'Component', 'topersia-ai-summarizer' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'buttons',
				'options' => array(
					'buttons' => __( 'AI buttons', 'topersia-ai-summarizer' ),
					'panel'   => __( 'AI panel', 'topersia-ai-summarizer' ),
				),
			)
		);

		$this->add_control(
			'services',
			array(
				'label'       => __( 'Assistants (comma separated)', 'topersia-ai-summarizer' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => '',
				'condition'   => array( 'mode' => 'buttons' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$mode     = isset( $settings['mode'] ) ? $settings['mode'] : 'buttons';

		if ( 'panel' === $mode ) {
			echo TP_AI_Render::panel_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted markup.
			return;
		}

		$args = array();

		if ( ! empty( $settings['services'] ) ) {
			$services = str_replace( ' ', '', (string) $settings['services'] );
			$args['services'] = array_values( array_filter( array_map( 'sanitize_key', explode( ',', $services ) ) ) );
		}

		echo TP_AI_Render::buttons_html( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted markup.
	}
}