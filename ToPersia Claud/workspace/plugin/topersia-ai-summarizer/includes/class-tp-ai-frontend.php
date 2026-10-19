<?php
/**
 * Auto-insertion of the buttons and the AI panel into the content.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end output location and asset loading.
 */
class TP_AI_Frontend {

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'the_content', array( __CLASS__, 'maybe_inject' ) );
		add_filter( 'post_thumbnail_html', array( __CLASS__, 'maybe_after_thumbnail' ), 10, 3 );
	}

	/**
	 * Register and enqueue the front-end CSS/JS when needed.
	 */
	public static function enqueue_assets() {
		$css_file = TP_AI_PATH . 'assets/css/topersia-ai.css';
		$js_file  = TP_AI_PATH . 'assets/js/topersia-ai.js';

		// filemtime-based version busts caches whenever the asset changes.
		$css_ver = is_file( $css_file ) ? filemtime( $css_file ) : TP_AI_VERSION;
		$js_ver  = is_file( $js_file ) ? filemtime( $js_file ) : TP_AI_VERSION;

		wp_register_style(
			'topersia-ai',
			TP_AI_URL . 'assets/css/topersia-ai.css',
			array(),
			$css_ver
		);

		wp_register_script(
			'topersia-ai',
			TP_AI_URL . 'assets/js/topersia-ai.js',
			array(),
			$js_ver,
			true
		);

		wp_localize_script( 'topersia-ai', 'tpasFront', array(
			'ajax'   => admin_url( 'admin-ajax.php' ),
			/*
			 * 1.3.2 · P5-D374: no nonce in the guest HTML. Generation by a visitor is off (P5-D358) and the panel is for
			 * logged-in users, so a guest never needs it - and a nonce printed into a page cached for guests expires
			 * (12-24 h) inside the cached HTML. Logged-in pages are never cached and keep it. Guest click tracking
			 * (`tpas_track`, off by default) therefore stays inactive: the script skips the request without a nonce.
			 */
			'nonce'  => is_user_logged_in() ? wp_create_nonce( 'tpas_ai' ) : '',
			'gen'    => TP_AI_Summary::visitor_requests() ? 1 : 0, // 1.3.0 · P5-D358: per-panel `gen` decides; this is the site switch.
			'copied' => esc_html__( 'Copied to clipboard.', 'topersia-ai-summarizer' ),
		) );

		$options = TP_AI_Options::get();

		$needs = false;

		if ( is_singular() ) {
			$needs = true;
		} elseif ( ( is_home() || is_archive() || is_search() ) && ! empty( $options['show_on_archive'] ) ) {
			$needs = true;
		}

		/**
		 * Whether the front-end component assets should load on this request.
		 *
		 * @param bool $needs Load assets or not.
		 */
		$needs = apply_filters( 'tpas_needs_assets', $needs );

		if ( $needs ) {
			wp_enqueue_style( 'topersia-ai' );
			wp_enqueue_script( 'topersia-ai' );
		}
	}

	/**
	 * Inject buttons / panel into the content body.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function maybe_inject( $content ) {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id    = get_the_ID();
		$options    = TP_AI_Options::get();
		$post_types = array_map( 'sanitize_key', (array) $options['post_types'] );
		$type       = get_post_type( $post_id );

		if ( ! in_array( $type, $post_types, true ) ) {
			return $content;
		}

		$hide_buttons = (bool) get_post_meta( $post_id, '_tpas_hide_buttons', true );
		$hide_panel   = (bool) get_post_meta( $post_id, '_tpas_hide_panel', true );

		$buttons = '';
		$panel   = '';

		// The panels and buttons are only meaningful on the singular view.
		if ( is_singular() ) {
			$position = $options['position'];

			if ( 'manual' !== $position && ! $hide_buttons ) {
				$buttons = TP_AI_Render::buttons_html( array( 'post_id' => $post_id ) );
			}

			if ( ! empty( $options['internal_enabled'] ) && ! $hide_panel && is_user_logged_in() ) {
				$panel_position = $options['panel_position'];

				if ( in_array( $panel_position, array( 'top', 'bottom' ), true ) ) {
					$panel = TP_AI_Render::panel_html( array( 'post_id' => $post_id ) );
				}
			}
		}

		if ( '' === $buttons && '' === $panel ) {
			return $content;
		}

		switch ( $options['position'] ) {
			case 'before':
				$content = $buttons . $content;
				break;

			case 'both':
				$content = $buttons . $content . $buttons;
				break;

			case 'after_thumbnail':
				// Handled by the thumbnail filter.
				break;

			default:
				$content = $content . $buttons;
		}

		if ( 'top' === $options['panel_position'] ) {
			$content = $panel . $content;
		} elseif ( 'bottom' === $options['panel_position'] ) {
			$content = $content . $panel;
		}

		return $content;
	}

	/**
	 * Append the buttons right below the featured image.
	 *
	 * @param string $html      Thumbnail markup.
	 * @param int    $post_id   Post id.
	 * @param string $thumbnail Image id (unused).
	 * @return string
	 */
	public static function maybe_after_thumbnail( $html, $post_id, $thumbnail ) {
		$options = TP_AI_Options::get();

		if ( 'after_thumbnail' !== $options['position'] ) {
			return $html;
		}

		$post_types = array_map( 'sanitize_key', (array) $options['post_types'] );
		$type       = get_post_type( $post_id );

		if ( ! in_array( $type, $post_types, true ) ) {
			return $html;
		}

		if ( (bool) get_post_meta( $post_id, '_tpas_hide_buttons', true ) ) {
			return $html;
		}

		return $html . TP_AI_Render::buttons_html( array( 'post_id' => $post_id ) );
	}
}