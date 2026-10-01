<?php
/**
 * Shortcodes for the buttons and the AI panel.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers [tpas] and [tpas_ai] plus their ToPersia aliases.
 */
class TP_AI_Shortcode {

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_shortcode( 'tpas', array( __CLASS__, 'buttons' ) );
		add_shortcode( 'topersia_ai', array( __CLASS__, 'buttons' ) );

		add_shortcode( 'tpas_ai', array( __CLASS__, 'panel' ) );
		add_shortcode( 'topersia_ai_panel', array( __CLASS__, 'panel' ) );
	}

	/**
	 * [tpas] shortcode.
	 *
	 * @param array $atts Attributes: prompt, services, style, align, layout,
	 *                    heading_size, show_icons, copy_button, heading, url, title.
	 * @return string
	 */
	public static function buttons( $atts ) {
		$atts = shortcode_atts(
			array(
				'heading'      => '',
				'prompt'       => '',
				'services'     => '',
				'style'        => '',
				'align'        => '',
				'layout'       => '',
				'heading_size' => '',
				'show_icons'   => '',
				'copy_button'  => '',
				'url'          => '',
				'title'        => '',
			),
			$atts,
			'tpas'
		);

		if ( '' !== $atts['services'] ) {
			$atts['services'] = array_values( array_filter( array_map( 'sanitize_key', explode( ',', $atts['services'] ) ) ) );
		}

		foreach ( array( 'style', 'align', 'layout', 'heading_size', 'show_icons', 'copy_button' ) as $key ) {
			if ( in_array( $atts[ $key ], array( '0', 'no', 'off', 'false' ), true ) ) {
				$atts[ $key ] = false;
			} elseif ( '' === $atts[ $key ] ) {
				unset( $atts[ $key ] );
			}
		}

		return TP_AI_Render::buttons_html( $atts );
	}

	/**
	 * [tpas_ai] shortcode.
	 *
	 * @param array $atts Attributes: types, default_type, post_id, url, title.
	 * @return string
	 */
	public static function panel( $atts ) {
		$atts = shortcode_atts(
			array(
				'types'        => '',
				'default_type' => '',
				'post_id'      => 0,
				'url'          => '',
				'title'        => '',
			),
			$atts,
			'tpas_ai'
		);

		if ( '' !== $atts['types'] ) {
			$atts['types'] = array_values( array_filter( array_map( 'sanitize_key', explode( ',', $atts['types'] ) ) ) );
		}

		return TP_AI_Render::panel_html( $atts );
	}
}