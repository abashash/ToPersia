<?php
/**
 * Settings storage, defaults and sanitization.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin options.
 */
class TP_AI_Options {

	/**
	 * Option name in the wp_options table.
	 */
	const OPTION_KEY = 'tpas_settings';

	/**
	 * Settings group used by the Settings API.
	 */
	const GROUP = 'tpas_group';

	/**
	 * Runtime cache.
	 *
	 * @var array|null
	 */
	protected static $cache = null;

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// External buttons.
			'heading'              => '',
			'prompt'               => '',
			'services'             => array( 'chatgpt', 'perplexity', 'claude', 'grok', 'copilot', 'gemini', 'deepseek' ),
			'post_types'           => self::default_post_types(),
			'position'             => 'after_thumbnail',
			'show_on_archive'      => 0,
			'style'                => 'filled',
			'accent'               => '#177E7A',
			'radius'               => 12,
			'align'                => 'start',
			'layout'               => 'stretch',
			'heading_size'         => 'large',
			'show_icons'           => 1,
			'icon_style'           => 'brand',
			'copy_button'          => 1,
			'new_tab'              => 1,
			'nofollow'             => 1,
			'track_clicks'         => 0,
			// On-page AI panel.
			'internal_enabled'     => 1,
			'internal_engine'      => 'auto',
			'internal_engines'     => array(), // Repeatable list: [ ['id'=>, 'provider'=>, 'api_key'=>, 'model'=>, 'base_url'=>], ... ].
			'internal_default_engine' => '',    // id of the row used for automatic/on-page generation.
			'internal_temperature' => 0.3,
			'internal_max_tokens'  => 700,    // ۱٫۲٫۱: کمترین مصرف — خلاصه و نکات در این اندازه جا می‌شوند
			'internal_context_chars' => 8000, // ۱٫۲٫۱: بافت کوتاه‌تر؛ دادۀ ساختاریافته اول می‌آید
			'internal_cache_hours' => 720,     // ۱٫۲٫۱: سی روز؛ ویرایش محتوا از راه هش کهنه‌اش می‌کند
			'internal_throttle'    => 10,
			'internal_types'       => array( 'summary', 'keypoints', 'plan' ),
			'internal_default_type' => 'summary',
			'internal_auto_first'  => 0,
			'panel_position'       => 'manual',
			// Analytics.
			'analytics_enabled'    => 0,
		);
	}

	/**
	 * Default post types AI is available on: every ToPersia entity post
	 * type (the real `tp_*` architecture, detected via Core's
	 * topersia_is_entity()) when Core is active, or a conservative
	 * post/page fallback when running standalone. The admin checklist
	 * itself already lists every public post type dynamically (see
	 * TP_AI_Admin::post_types_row()) - this only changes what is
	 * pre-checked out of the box.
	 *
	 * @return string[]
	 */
	protected static function default_post_types() {
		if ( ! function_exists( 'topersia_is_entity' ) ) {
			return array( 'post', 'page' );
		}

		$entity_types = array();

		foreach ( get_post_types( array( 'public' => true ) ) as $type ) {
			if ( topersia_is_entity( $type ) ) {
				$entity_types[] = $type;
			}
		}

		return $entity_types ? $entity_types : array( 'post', 'page' );
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get() {
		if ( null === self::$cache ) {
			$stored = get_option( self::OPTION_KEY, array() );

			if ( ! is_array( $stored ) ) {
				$stored = array();
			}

			self::$cache = wp_parse_args( $stored, self::defaults() );
		}

		return self::$cache;
	}

	/**
	 * Get a single setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Value returned when the key is unknown.
	 * @return mixed
	 */
	public static function get_field( $key, $fallback = '' ) {
		$options = self::get();

		return isset( $options[ $key ] ) ? $options[ $key ] : $fallback;
	}

	/**
	 * Get a boolean flag setting.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function just_flag( $key ) {
		return (bool) self::get_field( $key, 0 );
	}

	/**
	 * Clear the runtime cache.
	 *
	 * @return void
	 */
	/**
	 * Keep the settings row (it holds engine API keys) out of autoload, so the
	 * keys are not pulled into memory on every request.  1.2.2 · P5-D355
	 *
	 * Hooked on added_option / updated_option and called on every schema
	 * upgrade (TP_AI_DB::install()). Idempotent.
	 *
	 * @param string $option Option name (any; only ours is touched).
	 * @return void
	 */
	public static function no_autoload( $option = self::OPTION_KEY ) {
		if ( self::OPTION_KEY === $option && function_exists( 'wp_set_option_autoload' ) && false !== get_option( self::OPTION_KEY, false ) ) {
			wp_set_option_autoload( self::OPTION_KEY, false );
		}
	}

	public static function flush() {
		self::$cache = null;
	}

	/**
	 * Default heading text, localized for the current language.
	 *
	 * @param int $post_id Optional post id to resolve its language.
	 * @return string
	 */
	public static function default_heading( $post_id = 0 ) {
		return TP_AI_Localize::get( 'heading_default', TP_AI_Localize::for_post( $post_id ) );
	}

	/**
	 * Default structured prompt template, localized for the current language.
	 *
	 * @param int $post_id Optional post id to resolve its language.
	 * @return string
	 */
	public static function default_prompt( $post_id = 0 ) {
		return TP_AI_Localize::get( 'prompt_default', TP_AI_Localize::for_post( $post_id ) );
	}

	/**
	 * Allowed HTML tags inside the heading field.
	 *
	 * @return array
	 */
	public static function heading_allowed_html() {
		return array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'br'     => array(),
			'span'   => array( 'class' => array() ),
		);
	}

	/**
	 * Allowed output types for the AI panel.
	 *
	 * @return array
	 */
	public static function panel_types() {
		return array( 'summary', 'keypoints', 'plan' );
	}

	/**
	 * All configured internal-engine rows.
	 *
	 * @return array<int,array{id:string,provider:string,api_key:string,model:string,base_url:string}>
	 */
	public static function engines() {
		$rows = self::get_field( 'internal_engines', array() );

		return is_array( $rows ) ? array_values( $rows ) : array();
	}

	/**
	 * Finds one configured engine row by its stable id.
	 *
	 * @param string $id Engine row id.
	 * @return array|null
	 */
	public static function engine_by_id( $id ) {
		if ( '' === (string) $id ) {
			return null;
		}

		foreach ( self::engines() as $row ) {
			if ( isset( $row['id'] ) && (string) $row['id'] === (string) $id ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * The engine row used for automatic/on-page generation: the row marked
	 * as default, or the first configured row when nothing is explicitly
	 * marked, or null when no engine has been configured yet.
	 *
	 * @return array|null
	 */
	public static function default_engine() {

		/* ۱٫۲٫۱: فقط تنظیمات خودِ خلاصه‌ساز — هسته دیگر سطر ai.text ندارد */
		return self::legacy_engine();
	}

	/**
	 * موتور پیش‌فرض از تنظیم خودِ خلاصه‌ساز — همان رفتار ۱٫۱٫۱؛ منبع «انتقال».
	 *
	 * @return array|null
	 */
	public static function legacy_engine() {
		$rows = self::engines();

		if ( empty( $rows ) ) {
			return null;
		}

		$default_id = (string) self::get_field( 'internal_default_engine', '' );
		$match      = self::engine_by_id( $default_id );

		return $match ? $match : $rows[0];
	}

	/**
	 * دمای مدل — عدد اعشاری در بازۀ ۰ تا ۲، همان‌طور که به مدل فرستاده می‌شود.
	 * جداکنندۀ اعشار فارسی «٫» و رقم فارسی هم پذیرفته است. نامعتبر ← ۰٫۳.  SPRINT-4.4 ۲٫۷
	 *
	 * @param mixed $value ورودی.
	 * @return float
	 */
	public static function sanitize_temperature( $value ) {

		if ( is_string( $value ) ) {
			$value = strtr( trim( $value ), array(
				'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
				'٫' => '.', ',' => '.',
			) );
		}

		if ( ! is_numeric( $value ) ) {
			return 0.3;
		}

		return round( max( 0.0, min( 2.0, (float) $value ) ), 2 );
	}

	/**
	 * Generates a new, unpredictable-enough row id for a freshly added
	 * engine row.
	 *
	 * @return string
	 */
	public static function new_engine_id() {
		return 'eng_' . substr( str_replace( '.', '', (string) microtime( true ) ), 0, 14 ) . '_' . wp_generate_password( 6, false, false );
	}

	/**
	 * Presets for the internal LLM providers.
	 *
	 * @return array
	 */
	public static function providers() {
		return array(
			'deepseek'   => array(
				'label'    => 'DeepSeek',
				'url'      => 'https://api.deepseek.com/v1',
				'model'    => 'deepseek-chat',
				'protocol' => 'openai', // Native OpenAI-compatible /chat/completions API.
			),
			'openai'     => array(
				'label'    => 'OpenAI',
				'url'      => 'https://api.openai.com/v1',
				'model'    => 'gpt-4o-mini',
				'protocol' => 'openai',
			),
			'grok'       => array(
				'label'    => 'Grok (xAI)',
				'url'      => 'https://api.x.ai/v1',
				'model'    => 'grok-4-fast',
				'protocol' => 'openai', // xAI publishes an OpenAI-compatible endpoint.
			),
			'perplexity' => array(
				'label'    => 'Perplexity',
				'url'      => 'https://api.perplexity.ai',
				'model'    => 'sonar',
				'protocol' => 'openai', // Perplexity's API is also OpenAI-compatible.
			),
			'claude'     => array(
				'label'    => 'Claude (Anthropic)',
				'url'      => 'https://api.anthropic.com/v1',
				'model'    => 'claude-sonnet-4-5',
				'protocol' => 'anthropic', // Different auth header + request/response shape - see TP_AI_API::chat_anthropic().
			),
			'gemini'     => array(
				'label'    => 'Gemini (Google)',
				'url'      => 'https://generativelanguage.googleapis.com/v1beta',
				'model'    => 'gemini-2.0-flash',
				'protocol' => 'gemini', // Different auth (query param) + request/response shape - see TP_AI_API::chat_gemini().
			),
			'azure'      => array(
				'label'    => 'Azure OpenAI (incl. Copilot/enterprise deployments)',
				'url'      => '',
				'model'    => '',
				'protocol' => 'azure', // Same JSON body as OpenAI, but a per-deployment URL and an "api-key" header instead of "Authorization: Bearer". Set the full deployment URL (through "?api-version=...") as this row's base URL.
			),
			'custom'     => array(
				'label'    => 'OpenAI-compatible (custom)',
				'url'      => '',
				'model'    => '',
				'protocol' => 'openai',
			),
		);
	}

	/**
	 * Resolves one engine row into a ready-to-call endpoint: provider
	 * preset (or row overrides for "custom"/"azure"), protocol, and the
	 * row's own API key.
	 *
	 * @param array|null $engine An engine row (see engines()). Defaults to
	 *                           default_engine() when omitted.
	 * @return array{id:string,provider:string,protocol:string,label:string,base_url:string,model:string,api_key:string}
	 */
	public static function internal_endpoint( $engine = null ) {
		if ( null === $engine ) {
			$engine = self::default_engine();
		}

		$empty = array(
			'id'       => '',
			'provider' => '',
			'protocol' => '',
			'label'    => '',
			'base_url' => '',
			'model'    => '',
			'api_key'  => '',
		);

		if ( ! is_array( $engine ) || empty( $engine['provider'] ) ) {
			return $empty;
		}

		$providers = self::providers();
		$provider  = (string) $engine['provider'];

		if ( ! isset( $providers[ $provider ] ) ) {
			return $empty;
		}

		$base_url = $providers[ $provider ]['url'];
		$model    = $providers[ $provider ]['model'];

		// "custom" and "azure" both need a row-supplied URL (Azure's is a
		// full per-deployment URL, not a fixed API root) and, usually, an
		// explicit model/deployment name.
		if ( in_array( $provider, array( 'custom', 'azure' ), true ) ) {
			$base_url = isset( $engine['base_url'] ) ? untrailingslashit( trim( (string) $engine['base_url'] ) ) : '';
			$model    = isset( $engine['model'] ) ? trim( (string) $engine['model'] ) : '';
		} elseif ( ! empty( $engine['model'] ) ) {
			$model = trim( (string) $engine['model'] );
		}

		return array(
			'id'       => isset( $engine['id'] ) ? (string) $engine['id'] : '',
			'provider' => $provider,
			'protocol' => $providers[ $provider ]['protocol'],
			'label'    => $providers[ $provider ]['label'],
			'base_url' => $base_url,
			'model'    => $model,
			'api_key'  => isset( $engine['api_key'] ) ? trim( (string) $engine['api_key'] ) : '',
		);
	}

	/**
	 * Sanitize the settings array before it is stored.
	 *
	 * @param array $input Raw input from the settings screen.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$output   = $defaults;

		if ( ! is_array( $input ) ) {
			return $output;
		}

		$output['heading'] = isset( $input['heading'] )
			? wp_kses( trim( wp_unslash( $input['heading'] ) ), self::heading_allowed_html() )
			: '';

		$output['prompt'] = isset( $input['prompt'] )
			? sanitize_textarea_field( wp_unslash( $input['prompt'] ) )
			: '';

		$valid_services = array_keys( TP_AI_Services::get_all() );
		$services       = isset( $input['services'] ) ? (array) $input['services'] : array();
		$services       = array_map( 'sanitize_key', $services );
		$output['services'] = array_values( array_intersect( $services, $valid_services ) );

		if ( empty( $output['services'] ) ) {
			$output['services'] = $defaults['services'];
		}

		$valid_types = get_post_types( array( 'public' => true ) );
		$types       = isset( $input['post_types'] ) ? (array) $input['post_types'] : array();
		$types       = array_map( 'sanitize_key', $types );
		$output['post_types'] = array_values( array_intersect( $types, array_keys( $valid_types ) ) );

		if ( empty( $output['post_types'] ) ) {
			$output['post_types'] = $defaults['post_types'];
		}

		$positions          = array( 'after_thumbnail', 'before', 'after', 'both', 'manual' );
		$position           = isset( $input['position'] ) ? sanitize_key( $input['position'] ) : '';
		$output['position'] = in_array( $position, $positions, true ) ? $position : $defaults['position'];

		$panel_positions           = array( 'manual', 'top', 'bottom' );
		$panel_position            = isset( $input['panel_position'] ) ? sanitize_key( $input['panel_position'] ) : '';
		$output['panel_position']  = in_array( $panel_position, $panel_positions, true ) ? $panel_position : $defaults['panel_position'];

		$styles          = array( 'outline', 'filled', 'minimal' );
		$style           = isset( $input['style'] ) ? sanitize_key( $input['style'] ) : '';
		$output['style'] = in_array( $style, $styles, true ) ? $style : $defaults['style'];

		$aligns          = array( 'start', 'center', 'end' );
		$align           = isset( $input['align'] ) ? sanitize_key( $input['align'] ) : '';
		$output['align'] = in_array( $align, $aligns, true ) ? $align : $defaults['align'];

		$layouts          = array( 'stretch', 'inline' );
		$layout           = isset( $input['layout'] ) ? sanitize_key( $input['layout'] ) : '';
		$output['layout'] = in_array( $layout, $layouts, true ) ? $layout : $defaults['layout'];

		$sizes                  = array( 'large', 'normal', 'small' );
		$size                   = isset( $input['heading_size'] ) ? sanitize_key( $input['heading_size'] ) : '';
		$output['heading_size'] = in_array( $size, $sizes, true ) ? $size : $defaults['heading_size'];

		$icon_styles          = array( 'brand', 'neutral' );
		$icon_style           = isset( $input['icon_style'] ) ? sanitize_key( $input['icon_style'] ) : '';
		$output['icon_style'] = in_array( $icon_style, $icon_styles, true ) ? $icon_style : $defaults['icon_style'];

		$accent           = isset( $input['accent'] ) ? sanitize_hex_color( wp_unslash( $input['accent'] ) ) : '';
		$output['accent'] = $accent ? $accent : $defaults['accent'];

		$radius           = isset( $input['radius'] ) ? absint( $input['radius'] ) : $defaults['radius'];
		$output['radius'] = min( 40, $radius );

		foreach ( array( 'show_on_archive', 'show_icons', 'copy_button', 'new_tab', 'nofollow', 'track_clicks', 'internal_enabled', 'internal_auto_first', 'analytics_enabled' ) as $flag ) {
			$output[ $flag ] = empty( $input[ $flag ] ) ? 0 : 1;
		}

		// Internal engine mode: auto, ai, internal.
		$engines           = array( 'auto', 'ai', 'internal' );
		$engine            = isset( $input['internal_engine'] ) ? sanitize_key( wp_unslash( $input['internal_engine'] ) ) : '';
		$output['internal_engine'] = in_array( $engine, $engines, true ) ? $engine : $defaults['internal_engine'];

		// Internal AI engines: a repeatable list of { id, provider, api_key, model, base_url } rows.
		$output['internal_engines']        = self::sanitize_engines( isset( $input['internal_engines'] ) ? (array) $input['internal_engines'] : array() );
		$valid_ids                         = wp_list_pluck( $output['internal_engines'], 'id' );
		$default_id                        = isset( $input['internal_default_engine'] ) ? sanitize_text_field( wp_unslash( $input['internal_default_engine'] ) ) : '';
		$output['internal_default_engine'] = in_array( $default_id, $valid_ids, true ) ? $default_id : ( $valid_ids ? $valid_ids[0] : '' );

		/* دما عدد اعشاری ۰ تا ۲، بی تقسیم — پیش‌تر `min( 2, $t ) / 10` هر دمای بالای ۰٫۲ را ۰٫۲ می‌کرد.  SPRINT-4.4 ۲٫۷ */
		$output['internal_temperature']    = self::sanitize_temperature( isset( $input['internal_temperature'] ) ? wp_unslash( $input['internal_temperature'] ) : $defaults['internal_temperature'] );
		$output['internal_max_tokens']     = isset( $input['internal_max_tokens'] ) ? min( 8000, max( 100, absint( $input['internal_max_tokens'] ) ) ) : $defaults['internal_max_tokens'];
		$output['internal_context_chars']  = isset( $input['internal_context_chars'] ) ? min( 200000, max( 1000, absint( $input['internal_context_chars'] ) ) ) : $defaults['internal_context_chars'];
		$output['internal_cache_hours']    = isset( $input['internal_cache_hours'] ) ? min( 720, max( 1, absint( $input['internal_cache_hours'] ) ) ) : $defaults['internal_cache_hours'];
		$output['internal_throttle']       = isset( $input['internal_throttle'] ) ? min( 100, max( 1, absint( $input['internal_throttle'] ) ) ) : $defaults['internal_throttle'];

		$types = isset( $input['internal_types'] ) ? (array) $input['internal_types'] : array();
		$types = array_values( array_intersect( array_map( 'sanitize_key', $types ), self::panel_types() ) );
		$output['internal_types'] = empty( $types ) ? $defaults['internal_types'] : $types;

		$default_type                  = isset( $input['internal_default_type'] ) ? sanitize_key( $input['internal_default_type'] ) : '';
		$output['internal_default_type'] = in_array( $default_type, self::panel_types(), true ) ? $default_type : $defaults['internal_default_type'];

		self::flush();

		return $output;
	}

	/**
	 * Sanitizes the repeatable internal_engines rows submitted from the
	 * settings screen: validates the provider against providers(), keeps
	 * each row's previously stored API key when its field is submitted
	 * empty (matched by row id, since rows can be reordered/added/removed
	 * between saves), and drops rows with no provider selected (e.g. a
	 * row added then removed client-side before submit).
	 *
	 * @param array $rows Raw posted rows, e.g. $_POST['tpas_settings']['internal_engines'].
	 * @return array<int,array{id:string,provider:string,api_key:string,model:string,base_url:string}>
	 */
	private static function sanitize_engines( array $rows ) {
		$valid_providers = array_keys( self::providers() );
		$clean           = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$provider = isset( $row['provider'] ) ? sanitize_key( wp_unslash( $row['provider'] ) ) : '';

			if ( ! in_array( $provider, $valid_providers, true ) ) {
				continue; // No provider chosen for this row - skip it.
			}

			$id = isset( $row['id'] ) ? sanitize_text_field( wp_unslash( $row['id'] ) ) : '';
			if ( '' === $id || 0 === strpos( $id, 'new_' ) ) {
				// A brand-new row from the browser (client-side ids are
				// prefixed "new_") gets a real, stable server-assigned id.
				$id = self::new_engine_id();
			}

			$submitted_key = isset( $row['api_key'] ) ? trim( wp_unslash( $row['api_key'] ) ) : '';

			if ( ! empty( $row['api_key_clear'] ) ) {
				// 1.2.2 · P5-D355: the only way to delete a stored key is the
				// explicit "Clear stored key" box - an empty field keeps it.
				$api_key = '';
			} elseif ( '' !== $submitted_key ) {
				$api_key = sanitize_text_field( $submitted_key );
			} else {
				// Keep the previously stored key for this exact row (a
				// secret field is intentionally left blank on redisplay).
				$existing = self::engine_by_id( $id );
				$api_key  = $existing && isset( $existing['api_key'] ) ? $existing['api_key'] : '';
			}

			$clean[] = array(
				'id'       => $id,
				'provider' => $provider,
				'api_key'  => $api_key,
				'model'    => isset( $row['model'] ) ? sanitize_text_field( wp_unslash( $row['model'] ) ) : '',
				'base_url' => isset( $row['base_url'] ) ? esc_url_raw( trim( wp_unslash( $row['base_url'] ) ), array( 'https', 'http' ) ) : '',
			);
		}

		return $clean;
	}
}