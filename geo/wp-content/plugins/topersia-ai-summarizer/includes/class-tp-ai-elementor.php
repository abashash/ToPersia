<?php
/**
 * Elementor integration.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Elementor widget.
 */
class TP_AI_Elementor {

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'elementor/widgets/register', array( __CLASS__, 'register' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( __CLASS__, 'editor_assets' ) );
	}

	/**
	 * Register the widget with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor manager.
	 */
	public static function register( $widgets_manager ) {
		require_once TP_AI_PATH . 'includes/class-tp-ai-elementor-widget.php';

		$widgets_manager->register( new TP_AI_Elementor_Widget() );
	}

	/**
	 * Load the editor script so the widget controls work.
	 */
	public static function editor_assets() {
		wp_enqueue_script(
			'topersia-ai-elementor',
			TP_AI_URL . 'assets/js/topersia-ai-elementor.js',
			array( 'elementor-editor' ),
			TP_AI_VERSION,
			true
		);
	}
}