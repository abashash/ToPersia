<?php
/**
 * Per-language prompt template overrides.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prompts come from TP_AI_Localize by default (language + type). This manager
 * lets the site admin override any of them from the admin "Prompt templates"
 * page. Overrides live in one option, keyed lang → type → template.
 */
class TP_AI_Prompt_Manager {

	/**
	 * Settings key (also the register_setting group).
	 */
	const KEY = 'tpas_prompt_overrides';

	/**
	 * Which output types accept custom prompts.
	 *
	 * @return string[]
	 */
	public static function types() {
		return array( 'summary', 'keypoints', 'plan' );
	}

	/**
	 * Resolve the effective prompt for a language + type.
	 *
	 * @param string $lang Language code (2 or 5 chars).
	 * @param string $type Output type.
	 * @return string
	 */
	public static function get( $lang, $type ) {
		$lang = TP_AI_Localize::normalize( $lang );
		$type = sanitize_key( $type );

		$overrides = self::all();

		if ( isset( $overrides[ $lang ][ $type ] ) && '' !== trim( (string) $overrides[ $lang ][ $type ] ) ) {
			return (string) $overrides[ $lang ][ $type ];
		}

		return (string) TP_AI_Localize::prompt_for_type( $type, $lang );
	}

	/**
	 * Remove template placeholders unsupported by an engine (internal engines
	 * have no {type}/{context} tokens) so no literal braces leak into output.
	 *
	 * @param string $prompt Raw prompt.
	 * @return string
	 */
	public static function strip_unused( $prompt ) {
		return trim( (string) preg_replace( '/\{\w+\}/', '', (string) $prompt ) );
	}

	/**
	 * All overrides: array lang => type => template.
	 *
	 * @return array
	 */
	public static function all() {
		$value = get_option( self::KEY, array() );

		return is_array( $value ) ? $value : array();
	}

	/**
	 * Sanitizer used by register_setting().
	 *
	 * @param mixed $input Raw POSTed value.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$clean   = array();
		$allowed = self::types();

		if ( ! is_array( $input ) ) {
			return $clean;
		}

		foreach ( $input as $lang => $types ) {
			$lang = TP_AI_Localize::normalize( sanitize_key( $lang ) );
			if ( '' === $lang || ! is_array( $types ) ) {
				continue;
			}
			foreach ( $types as $type => $template ) {
				$type = sanitize_key( $type );
				if ( ! in_array( $type, $allowed, true ) ) {
					continue;
				}
				$clean[ $lang ][ $type ] = wp_kses_post( (string) $template );
			}
		}

		return $clean;
	}

	/**
	 * The languages that already exist in the localized defaults.
	 *
	 * @return string[]
	 */
	public static function languages() {
		return TP_AI_Localize::languages();
	}
}