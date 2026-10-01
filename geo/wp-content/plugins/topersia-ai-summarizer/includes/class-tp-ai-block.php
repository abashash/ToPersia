<?php
/**
 * Server-rendered Gutenberg blocks for ToPersia AI.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the tpas/buttons and tpas/panel dynamic blocks.
 */
class TP_AI_Block {

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Register both dynamic blocks.
	 */
	public static function register_blocks() {
		wp_register_script(
			'topersia-ai-block',
			TP_AI_URL . 'assets/js/topersia-ai-block.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-block-editor', 'wp-components' ),
			TP_AI_VERSION,
			true
		);

		register_block_type( 'tpas/buttons', array(
			'api_version'     => 2,
			'editor_script'   => 'topersia-ai-block',
			'render_callback' => array( __CLASS__, 'render_buttons' ),
			'attributes'      => array(
				'services' => array(
					'type'    => 'array',
					'default' => array(),
				),
				'align'    => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		) );

		register_block_type( 'tpas/panel', array(
			'api_version'     => 2,
			'editor_script'   => 'topersia-ai-block',
			'render_callback' => array( __CLASS__, 'render_panel' ),
			'attributes'      => array(
				'types'        => array(
					'type'    => 'array',
					'default' => array(),
				),
				'default_type' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
		) );
	}

	/**
	 * Render callback for the buttons block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_buttons( $attributes ) {
		$args = array();

		if ( ! empty( $attributes['services'] ) && is_array( $attributes['services'] ) ) {
			$args['services'] = array_map( 'sanitize_key', $attributes['services'] );
		}

		if ( ! empty( $attributes['align'] ) ) {
			$args['align'] = sanitize_key( $attributes['align'] );
		}

		return TP_AI_Render::buttons_html( $args );
	}

	/**
	 * Render callback for the panel block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_panel( $attributes ) {
		$args = array();

		if ( ! empty( $attributes['types'] ) && is_array( $attributes['types'] ) ) {
			$args['types'] = array_map( 'sanitize_key', $attributes['types'] );
		}

		if ( ! empty( $attributes['default_type'] ) ) {
			$args['default_type'] = sanitize_key( $attributes['default_type'] );
		}

		$args['post_id'] = get_the_ID();

		return TP_AI_Render::panel_html( $args );
	}
}