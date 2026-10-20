<?php
/**
 * Registry of supported AI assistants for ToPersia.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Holds the list of AI services and builds their links.
 */
class TP_AI_Services {

	/**
	 * All registered services.
	 *
	 * Each entry: label, url (sprintf template), color, prefill (bool).
	 *
	 * @return array
	 */
	public static function get_all() {
		/*
		 * 1.4.1 · P5-D392 (P5-D255 · H03 ممیزی ۲د): هیچ پیوند دستیار بیرونی (ChatGPT · Claude · Gemini · Copilot ·
		 * Perplexity · Grok · DeepSeek …) در خروجی عمومی. فهرست خالی است و صافی `tpas_services` هم برداشته شد تا
		 * افزونۀ دیگری نتواند دوباره پیوند بیفزاید. کلاس برای سازگاری (رهگیر، پیشخوان) می‌ماند.
		 */
		return array();
	}

	/**
	 * Get a single service definition.
	 *
	 * @param string $key Service slug.
	 * @return array|false
	 */
	public static function get( $key ) {
		$all = self::get_all();

		return isset( $all[ $key ] ) ? $all[ $key ] : false;
	}

	/**
	 * Build the outbound URL for a service.
	 *
	 * @param string $key    Service slug.
	 * @param string $prompt Prompt text.
	 * @return string Empty string when the service is unknown.
	 */
	public static function build_url( $key, $prompt ) {
		$service = self::get( $key );

		if ( ! $service || empty( $service['url'] ) ) {
			return '';
		}

		$url = sprintf( $service['url'], rawurlencode( $prompt ) );

		/**
		 * Filter a single outbound service URL.
		 *
		 * @param string $url    Final URL.
		 * @param string $key    Service slug.
		 * @param string $prompt Prompt text.
		 */
		return apply_filters( 'tpas_service_url', $url, $key, $prompt );
	}

	/**
	 * Brand-style marks for each assistant.
	 *
	 * Hand-drawn simplified glyphs; replace any of them with the
	 * tpas_service_icon filter. DeepSeek is rendered as a whale silhouette.
	 *
	 * @return array
	 */
	public static function brand_icons() {
		return array(
			'chatgpt' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.2a5.2 5.2 0 0 0-4.5 2.6 5.2 5.2 0 0 0-3.4 7.7 5.2 5.2 0 0 0 3.9 7.3 5.2 5.2 0 0 0 8 .9 5.2 5.2 0 0 0 3.4-7.7 5.2 5.2 0 0 0-3.9-7.3A5.2 5.2 0 0 0 12 2.2zm0 1.8c1 0 1.9.4 2.5 1.1l-4 2.3a.9.9 0 0 0-.5.8v5l-1.7-1V7.6c0-2 1.7-3.6 3.7-3.6zm4.6 2.6c1.7.5 2.8 2.3 2.4 4.1-.1.4-.3.8-.5 1.2l-4-2.3a.9.9 0 0 0-.9 0l-4.3 2.5V10l3.9-2.2c1.1-.7 2.4-.8 3.4-.2zM5.6 8.3l.1 4.7c0 .4.2.7.5.9l4.3 2.5-1.7 1-3.9-2.3a3.7 3.7 0 0 1 .7-6.8zm8.2 2.4 1.7 1v4.6c0 2-1.7 3.7-3.7 3.7-1 0-1.9-.4-2.5-1.1l4-2.3c.3-.2.5-.5.5-.9zm-2.9.6 1.2.7v1.4l-1.2.7-1.2-.7v-1.4zm6.3 1.6a3.7 3.7 0 0 1-.5 6.7l-.1-4.7a.9.9 0 0 0-.5-.9l-4.3-2.5 1.7-1z"/></svg>',
			'perplexity' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M12 4.2 5.4 9v9M12 4.2 18.6 9v9M12 4.2v15.6M5.4 9h13.2M8.6 18h6.8"/></svg>',
			'claude' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.6c.3 0 .6.2.7.5l1.3 4.3 3.5-2.7c.3-.2.7-.2.9.1.2.2.3.6.1.9l-2.7 3.5 4.3 1.3c.3.1.5.4.5.7s-.2.6-.5.7l-4.3 1.3 2.7 3.5c.2.3.1.7-.1.9-.2.2-.6.3-.9.1l-3.5-2.7-1.3 4.3c-.1.3-.4.5-.7.5s-.6-.2-.7-.5l-1.3-4.3-3.5 2.7c-.3.2-.7.1-.9-.1-.2-.2-.3-.6-.1-.9l2.7-3.5-4.3-1.3c-.3-.1-.5-.4-.5-.7s.2-.6.5-.7l4.3-1.3-2.7-3.5c-.2-.3-.1-.7.1-.9.2-.2.6-.3.9-.1l3.5 2.7 1.3-4.3c.1-.3.4-.5.7-.5z"/></svg>',
			'grok' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" d="M9.3 14.7 19.2 4.8M8.2 19.2h3.1c4 0 7.2-3.2 7.2-7.2V8.3M5 19.2l5.8-5.8"/></svg>',
			'copilot' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" d="M3.6 12.4c0-3.2 2.4-5.6 5.6-5.6h5.6c3.2 0 5.6 2.4 5.6 5.6 0 3-1.6 4.6-4 5.2-2.3.6-3.2 1.6-4.4 1.6s-2.1-1-4.4-1.6c-2.4-.6-4-2.2-4-5.2z"/><path fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" d="M9 12.4v1.2M15 12.4v1.2"/></svg>',
			'gemini' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.4c.4 4.9 4.3 8.8 9.2 9.2v.8c-4.9.4-8.8 4.3-9.2 9.2h-.8c-.4-4.9-4.3-8.8-9.2-9.2v-.8c4.9-.4 8.8-4.3 9.2-9.2z"/></svg>',
			'deepseek' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M7.8 4.8c4.1-1.5 8.6 1.4 8.6 6 0 1.8-.7 3.3-1.8 4.5 2-1 3.9-2.8 4.9-5.1.5 3.4-1.1 6.4-3.8 7.9l-.5.3c-.5.9-.8 1.9-.8 3 0 .4-.3.7-.7.7h-1.6c-.4 0-.7-.3-.7-.7 0-2.6.9-4.9 2.4-6.8-.5-.8-.9-1.7-.9-2.7 0-1.4.6-2.7 1.5-3.7C14.2 5.5 11.5 4.2 9 3.9c.4-.4.9-.8 1.5-1.1L.9 4.3c4.6-1 7.7.1 6.9.5z"/><circle cx="8.6" cy="12.2" r="1.1"/></svg>',
		);
	}

	/**
	 * Neutral geometric marks, free of any brand shape.
	 *
	 * @return array
	 */
	public static function neutral_icons() {
		return array(
			'chatgpt' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M12 3.2 19 7v10l-7 3.8L5 17V7z"/><circle cx="12" cy="12" r="2.6" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
			'perplexity' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.2" fill="none" stroke="currentColor" stroke-width="1.6"/><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M12 3.8v16.4M3.8 12h16.4"/></svg>',
			'claude' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M12 3.5v17M4.6 7.8l14.8 8.4M19.4 7.8 4.6 16.2"/></svg>',
			'grok' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" d="M5 19 19 5M14.5 5H19v4.5M5 14v5h5"/></svg>',
			'copilot' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M4 11.5c0-3 2.3-5 5.2-5h5.6c2.9 0 5.2 2 5.2 5v2c0 3-2.3 5-5.2 5H9.2C6.3 18.5 4 16.5 4 13.5z"/><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M9 12.2h1.5M13.5 12.2H15"/></svg>',
			'gemini' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M12 3.5c.6 4.6 3.9 7.9 8.5 8.5-4.6.6-7.9 3.9-8.5 8.5-.6-4.6-3.9-7.9-8.5-8.5 4.6-.6 7.9-3.9 8.5-8.5z"/></svg>',
			'deepseek' => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M6.5 14.5c2.4 2.2 6.3 2.2 8.7 0m-8.7-1.3h.01m3.3-4.5c1.8 1.3 5.8.8 7.4-1.3-1.2 3.1-3.9 4.9-6.9 4.9M12 3.5v17"/></svg>',
		);
	}

	/**
	 * Inline icon for a service, following the icon style setting.
	 *
	 * @param string $key   Service slug.
	 * @param string $force Optional forced set: 'brand' or 'neutral'. Leave
	 *                      empty to follow the icon_style setting.
	 * @return string Inline SVG markup.
	 */
	public static function get_icon( $key, $force = '' ) {
		if ( $force ) {
			$style = ( 'neutral' === $force ) ? 'neutral' : 'brand';
		} else {
			$style = TP_AI_Options::get_field( 'icon_style', 'brand' );
		}
		$icons    = ( 'neutral' === $style ) ? self::neutral_icons() : self::brand_icons();
		$fallback = self::neutral_icons();

		if ( isset( $icons[ $key ] ) ) {
			$icon = $icons[ $key ];
		} elseif ( isset( $fallback[ $key ] ) ) {
			$icon = $fallback[ $key ];
		} else {
			$icon = $fallback['gemini'];
		}

		/**
		 * Filter the icon markup for a service.
		 *
		 * @param string $icon Inline SVG markup.
		 * @param string $key  Service slug.
		 */
		return apply_filters( 'tpas_service_icon', $icon, $key );
	}

	/**
	 * Allowed SVG tags for wp_kses when printing icons.
	 *
	 * @return array
	 */
	public static function icon_allowed_html() {
		$svg_atts = array(
			'viewbox'         => true,
			'width'           => true,
			'height'          => true,
			'aria-hidden'     => true,
			'focusable'       => true,
			'xmlns'           => true,
			'fill'            => true,
			'stroke'          => true,
			'stroke-width'    => true,
			'stroke-linecap'  => true,
			'stroke-linejoin' => true,
			'class'           => true,
		);

		return array(
			'svg'    => $svg_atts,
			'path'   => array_merge( $svg_atts, array( 'd' => true ) ),
			'circle' => array_merge( $svg_atts, array( 'cx' => true, 'cy' => true, 'r' => true ) ),
			'rect'   => array_merge( $svg_atts, array( 'x' => true, 'y' => true, 'rx' => true ) ),
			'g'      => $svg_atts,
			'line'   => array_merge( $svg_atts, array( 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true ) ),
		);
	}
}