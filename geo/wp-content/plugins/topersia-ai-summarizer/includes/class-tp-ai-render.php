<?php
/**
 * Markup generation for the buttons and the on-page AI panel.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds both front-end components.
 */
class TP_AI_Render {

	/**
	 * Instance counter, used for unique ids.
	 *
	 * @var int
	 */
	protected static $instance = 0;

	/**
	 * Build the HTML for the external assistant buttons.
	 *
	 * @param array $args Optional overrides: heading, prompt, services, style,
	 *                    align, url, title, post_id, show_icons, copy_button.
	 * @return string
	 */
	public static function buttons_html( $args = array() ) {
		$options = TP_AI_Options::get();

		$defaults = array(
			'heading'      => $options['heading'],
			'prompt'       => $options['prompt'],
			'services'     => $options['services'],
			'style'        => $options['style'],
			'align'        => $options['align'],
			'layout'       => $options['layout'],
			'heading_size' => $options['heading_size'],
			'show_icons'   => $options['show_icons'],
			'copy_button'  => $options['copy_button'],
			'post_id'      => 0,
			'url'          => '',
			'title'        => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$post_id = $args['post_id'] ? absint( $args['post_id'] ) : get_the_ID();
		$url     = $args['url'] ? $args['url'] : self::current_url( $post_id );
		$title   = $args['title'] ? $args['title'] : self::current_title( $post_id );
		$lang    = TP_AI_Localize::for_post( $post_id );

		$heading = '' !== trim( (string) $args['heading'] )
			? $args['heading']
			: TP_AI_Options::default_heading( $post_id );

		$template = '' !== trim( (string) $args['prompt'] )
			? $args['prompt']
			: TP_AI_Options::default_prompt( $post_id );

		$prompt = self::build_prompt( $template, $url, $title, $post_id, $lang );

		$services = array_values( array_filter( (array) $args['services'] ) );
		if ( empty( $services ) ) {
			return '';
		}

		$all     = TP_AI_Services::get_all();
		$style   = in_array( $args['style'], array( 'outline', 'filled', 'minimal' ), true ) ? $args['style'] : 'filled';
		$align   = in_array( $args['align'], array( 'start', 'center', 'end' ), true ) ? $args['align'] : 'start';
		$layout  = in_array( $args['layout'], array( 'stretch', 'inline' ), true ) ? $args['layout'] : 'stretch';
		$size    = in_array( $args['heading_size'], array( 'large', 'normal', 'small' ), true ) ? $args['heading_size'] : 'large';
		$track   = ! empty( $options['track_clicks'] );
		$new_tab = ! empty( $options['new_tab'] );
		$nofollow = ! empty( $options['nofollow'] );

		self::$instance++;
		$heading_id = 'tpas-heading-' . self::$instance;

		$inline_style = sprintf(
			'--tpas-accent:%1$s;--tpas-radius:%2$dpx;',
			$options['accent'],
			absint( $options['radius'] )
		);

		$classes = array(
			'tpas',
			'tpas--' . $style,
			'tpas--align-' . $align,
			'tpas--layout-' . $layout,
			'tpas--heading-' . $size,
		);

		if ( $track ) {
			$classes[] = 'tpas--tracking';
		}

		$rel = array( 'noopener' );
		if ( $nofollow ) {
			$rel[] = 'nofollow';
		}
		if ( $new_tab ) {
			$rel[] = 'noreferrer';
		}

		$html  = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="' . esc_attr( $inline_style ) . '" data-tpas-tracking="' . ( $track ? '1' : '0' ) . '">';
		$html .= '<p class="tpas__heading" id="' . esc_attr( $heading_id ) . '">' . wp_kses( $heading, TP_AI_Options::heading_allowed_html() ) . '</p>';
		$html .= '<div class="tpas__buttons" role="group" aria-labelledby="' . esc_attr( $heading_id ) . '">';

		foreach ( $services as $key ) {
			$key = sanitize_key( $key );

			if ( ! isset( $all[ $key ] ) ) {
				continue;
			}

			$link = TP_AI_Services::build_url( $key, $prompt );

			if ( ! $link ) {
				continue;
			}

			$label = $all[ $key ]['label'];

			$html .= '<a class="tpas__btn tpas__btn--' . esc_attr( $key ) . '"';
			$html .= ' href="' . esc_url( $link ) . '"';
			$html .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';

			if ( $new_tab ) {
				$html .= ' target="_blank"';
			}

			$html .= ' data-tpas-service="' . esc_attr( $key ) . '"';
			$html .= ' data-tpas-post="' . esc_attr( (string) $post_id ) . '"';
			$html .= ' style="' . esc_attr( '--tpas-brand:' . $all[ $key ]['color'] ) . '"';
			/* translators: %s: name of the AI assistant, e.g. ChatGPT. */
			$html .= ' aria-label="' . esc_attr( sprintf( __( 'Summarize this page with %s', 'topersia-ai-summarizer' ), $label ) ) . '">';

			if ( ! empty( $args['show_icons'] ) ) {
				$html .= '<span class="tpas__icon">' . wp_kses( TP_AI_Services::get_icon( $key ), TP_AI_Services::icon_allowed_html() ) . '</span>';
			}

			$html .= '<span class="tpas__label">' . esc_html( $label ) . '</span>';
			$html .= '</a>';
		}

		if ( ! empty( $args['copy_button'] ) ) {
			$html .= '<button type="button" class="tpas__btn tpas__copy" data-tpas-prompt="' . esc_attr( $prompt ) . '">';
			$html .= '<span class="tpas__label">' . esc_html__( 'Copy prompt', 'topersia-ai-summarizer' ) . '</span>';
			$html .= '</button>';
		}

		$html .= '</div></div>';

		if ( $track ) {
			wp_enqueue_script( 'topersia-ai' );
		}

		/**
		 * Filter the complete buttons markup.
		 *
		 * @param string $html   Markup.
		 * @param array  $args   Resolved arguments.
		 * @param string $prompt Prompt text.
		 */
		return apply_filters( 'tpas_buttons_html', $html, $args, $prompt );
	}

	/**
	 * Build the HTML for the on-page AI panel.
	 *
	 * @param array $args Optional overrides: types, default_type, heading,
	 *                    post_id, url, title, show_engines.
	 * @return string
	 */
	public static function panel_html( $args = array() ) {
		// The compact public AI component (Summary + Travel Planning) is
		// authenticated-only. This check lives here - not only in the
		// auto-injection code path in TP_AI_Frontend - so shortcode/block/
		// Elementor placements are protected too, with a single change.
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$options = TP_AI_Options::get();

		$defaults = array(
			// The public panel only ever offers Summary + Travel Planning,
			// regardless of the admin's internal_types setting (which still
			// governs what TYPES exist for internal/API use, e.g. a
			// shortcode a developer builds elsewhere) - "Key points" and any
			// other type stay out of the public-facing component by design.
			'types'        => array( 'summary', 'plan' ),
			'default_type' => in_array( $options['internal_default_type'], array( 'summary', 'plan' ), true )
				? $options['internal_default_type']
				: 'summary',
			'post_id'      => 0,
			'url'          => '',
			'title'        => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$post_id = $args['post_id'] ? absint( $args['post_id'] ) : get_the_ID();
		$url     = $args['url'] ? $args['url'] : self::current_url( $post_id );
		$title   = $args['title'] ? $args['title'] : self::current_title( $post_id );
		$lang    = TP_AI_Localize::for_post( $post_id );

		$public_types = array( 'summary', 'plan' );
		$all_types    = TP_AI_Options::panel_types();
		$types        = array_values( array_intersect( (array) $args['types'], $all_types, $public_types ) );

		if ( empty( $types ) ) {
			$types = array_values( array_intersect( $public_types, $all_types ) );
		}
		if ( empty( $types ) ) {
			return ''; // Neither type is enabled in settings - nothing to show.
		}

		$default_type = in_array( $args['default_type'], $types, true ) ? $args['default_type'] : $types[0];

		$strings = TP_AI_Localize::all( $lang );

		// Build one ready prompt per output type - used server-side only,
		// to send with the generate request; never displayed to the user
		// (no "copy prompt" / raw-prompt UI in the public component).
		$prompts = array();
		foreach ( $types as $type ) {
			$prompts[ $type ] = self::build_prompt( TP_AI_Prompt_Manager::get( $lang, $type ), $url, $title, $post_id, $lang );
		}

		$configured     = TP_AI_API::is_configured();
		$enabled        = ! empty( $options['internal_enabled'] );
		$tracking       = ! empty( $options['track_clicks'] );
		$serve_internal = TP_AI_Summary::engine_is_internal();

		$config = array(
			'post'    => $post_id,
			'lang'    => $lang,
			'enabled' => $enabled ? 1 : 0,
			'ready'   => ( $enabled && ( $configured || $serve_internal ) ) ? 1 : 0,
			// Automatic generation on page load is intentionally never
			// offered here, regardless of the internal_auto_first setting -
			// the public component only ever generates on explicit request.
			'auto'    => 0,
			'default' => $default_type,
			'msg'     => array(
				'copy'       => $strings['ai_copy'],
				'copied'     => $strings['ai_copied'],
				'generate'   => $strings['ai_generate'],
				'regenerate' => $strings['ai_regenerate'],
				'loading'    => $strings['ai_loading'],
				'error'      => $strings['ai_error'],
			),
		);

		/**
		 * Filter the JSON config embedded in the panel.
		 *
		 * @param array $config Config payload.
		 */
		$config = apply_filters( 'tpas_panel_config', $config );

		$json = wp_json_encode( $config );
		$json = $json ? $json : '{}';

		self::$instance++;
		$panel_id = 'tpas-ai-' . self::$instance;

		$html  = '<div class="tpas-ai" id="' . esc_attr( $panel_id ) . '" data-tpas-json="' . esc_attr( $json ) . '">';

		$html .= '<div class="tpas-ai__kicker">' . esc_html( $strings['ai_kicker'] ) . '</div>';
		$html .= '<h2 class="tpas-ai__title">' . esc_html( $strings['ai_title'] ) . '</h2>';

		$html .= '<div class="tpas-ai__actions" role="group" aria-label="' . esc_attr( $strings['ai_title'] ) . '">';
		foreach ( $types as $type ) {
			$active = ( $type === $default_type ) ? ' is-active' : '';
			$html  .= '<button type="button" class="tpas-ai__btn' . esc_attr( $active ) . '" data-tpas-type="' . esc_attr( $type ) . '" aria-pressed="' . ( $type === $default_type ? 'true' : 'false' ) . '">'
				. esc_html( $strings[ 'ai_type_' . $type ] ) . '</button>';
		}
		$html .= '</div>';

		$cached = TP_AI_Summary::get_cached( $post_id, $default_type, $lang );

		// Internal engine: serve a real, server-side generated result
		// immediately for whichever type is already cached - still only on
		// a page where the panel is shown, never as a background/automatic
		// call triggered by this render.
		if ( ! $cached && $serve_internal && 'summary' === $default_type ) {
			$text = TP_AI_Summary::internal_text( $post_id, $default_type, $lang );

			if ( '' !== trim( (string) $text ) ) {
				$cached = array( 'text' => $text );
			}
		}

		$html .= '<div class="tpas-ai__out" id="' . esc_attr( $panel_id ) . '-out" aria-live="polite">';

		if ( $cached ) {
			$html .= '<div class="tpas-ai__out-text" data-tpas-state="content">' . self::format_ai_text( $cached['text'] ) . '</div>';
			$html .= '<div class="tpas-ai__out-toolbar">'
				. '<button type="button" class="tpas-ai__regenerate" data-tpas-action="generate">'
				. esc_html( $strings['ai_regenerate'] ) . '</button>'
				. '<button type="button" class="tpas-ai__btn--copy" data-tpas-action="copy">' . esc_html( $strings['ai_copy'] ) . '</button>'
				. '</div>';
		} elseif ( $config['ready'] ) {
			$html .= '<div class="tpas-ai__out-text" data-tpas-state="generate">'
				. '<button type="button" class="tpas-ai__cta" data-tpas-action="generate">'
				. esc_html( $strings['ai_generate'] ) . '</button></div>';
		} else {
			// Extremely unlikely: the internal engine is always available
			// once "internal_enabled" is on, so this only shows when the
			// admin has disabled AI outright. No technical details, no raw
			// prompt - just a plain unavailable notice (see section 40).
			$html .= '<div class="tpas-ai__out-text" data-tpas-state="unavailable">'
				. '<p class="tpas-ai__nokey-note">' . esc_html( $strings['ai_unavailable'] ) . '</p>'
				. '</div>';
		}

		$html .= '</div>';

		$html .= '<p class="tpas-ai__disclaimer">' . esc_html( $strings['ai_disclaimer'] ) . '</p>';
		$html .= '</div>';

		wp_enqueue_script( 'topersia-ai' );

		/**
		 * Filter the complete AI panel markup.
		 *
		 * @param string $html  Markup.
		 * @param array  $args  Resolved arguments.
		 * @param string $lang  Language code.
		 */
		return apply_filters( 'tpas_panel_html', $html, $args, $lang );
	}

	/**
	 * Resolve the URL to summarize.
	 *
	 * @param int $post_id Post id, when available.
	 * @return string
	 */
	public static function current_url( $post_id = 0 ) {
		if ( $post_id ) {
			$permalink = get_permalink( $post_id );
			if ( $permalink ) {
				return $permalink;
			}
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$path = wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return esc_url_raw( home_url( $path ) );
		}

		return home_url( '/' );
	}

	/**
	 * Resolve the title to use in the prompt.
	 *
	 * @param int $post_id Post id, when available.
	 * @return string
	 */
	public static function current_title( $post_id = 0 ) {
		if ( $post_id ) {
			$title = get_the_title( $post_id );
			if ( $title ) {
				return wp_strip_all_tags( $title );
			}
		}

		return wp_strip_all_tags( get_bloginfo( 'name' ) );
	}

	/**
	 * Fill the prompt placeholders.
	 *
	 * @param string $template Prompt template.
	 * @param string $url      Page URL.
	 * @param string $title    Page title.
	 * @param int    $post_id  Post id.
	 * @param string $lang     Language code.
	 * @return string
	 */
	public static function build_prompt( $template, $url, $title, $post_id, $lang ) {
		$replace = array(
			'{url}'       => $url,
			'{title}'     => $title,
			'{site}'      => get_bloginfo( 'name' ),
			'{site_name}' => get_bloginfo( 'name' ),
		);

		// Structured-data placeholders (JetEngine facts / related items) are
		// resolved only when the template actually references them.
		$requested = strpos( (string) $template, '{related}' ) !== false
			|| strpos( (string) $template, '{facts}' ) !== false;

		if ( $requested && $post_id ) {
			$context    = TP_AI_Context::build( $post_id );
			$facts      = array();
			foreach ( ( $context['jetengine']['meta_fields'] ?? array() ) as $key => $value ) {
				$label = ucwords( str_replace( array( '_', '-' ), ' ', (string) $key ) );
				$facts[] = sprintf( '%s: %s', $label, $value );
			}
			$replace['{related}'] = implode( ', ', (array) ( $context['jetengine']['relations'] ?? array() ) );
			$replace['{facts}']   = implode( '; ', $facts );
		}

		$prompt = strtr( (string) $template, $replace );

		/**
		 * Filter the final prompt sent to the AI services.
		 *
		 * @param string $prompt  Prompt text.
		 * @param int    $post_id Current post id.
		 * @param string $url     Target URL.
		 * @param string $lang    Language code.
		 */
		return apply_filters( 'tpas_prompt', $prompt, $post_id, $url, $lang );
	}

	/**
	 * Safely turn raw model output into display-ready HTML.
	 *
	 * The text is stripped, escaped, then only a tiny safe markdown subset is
	 * applied (bold, bullets, line breaks). Never trusts model-provided HTML.
	 *
	 * @param string $text Raw model output.
	 * @return string
	 */
	public static function format_ai_text( $text ) {
		$text = (string) $text;
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = esc_html( $text );

		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$html  = '';
		$in_list = false;

		foreach ( $lines as $line ) {
			$line = preg_replace( '/^[\x{200E}\x{200F}\s]+/u', '', $line );
			$line = rtrim( $line );

			if ( '' === $line ) {
				if ( $in_list ) {
					$html .= '</ul>';
					$in_list = false;
				}
				$html .= '<br><br>';
				continue;
			}

			if ( preg_match( '/^[-*•]\s+(.*)$/u', $line, $m ) || preg_match( '/^\d+[.)]\s+(.*)$/u', $line, $m ) ) {
				if ( ! $in_list ) {
					$html .= '<ul>';
					$in_list = true;
				}
				$html .= '<li>' . self::inline_text( $m[1] ) . '</li>';
				continue;
			}

			if ( $in_list ) {
				$html .= '</ul>';
				$in_list = false;
			}

			if ( preg_match( '/^#{1,3}\s+(.*)$/u', $line, $m ) ) {
				$html .= '<strong>' . self::inline_text( $m[1] ) . '</strong><br>';
			} else {
				$html .= self::inline_text( $line ) . '<br>';
			}
		}

		if ( $in_list ) {
			$html .= '</ul>';
		}

		$html = preg_replace( '#(?:<br>)+$#', '', $html );

		return wp_kses(
			$html,
			array(
				'br'     => array(),
				'strong' => array(),
				'ul'     => array(),
				'li'     => array(),
			)
		);
	}

	/**
	 * Inline markdown (bold / code) over already-escaped text.
	 *
	 * @param string $text Escaped inline text.
	 * @return string
	 */
	protected static function inline_text( $text ) {
		$text = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text );
		$text = preg_replace( '/`([^`]+)`/u', '<strong>$1</strong>', $text );

		return $text;
	}
}