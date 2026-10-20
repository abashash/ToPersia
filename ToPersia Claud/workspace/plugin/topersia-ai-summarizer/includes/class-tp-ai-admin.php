<?php
/**
 * Settings screen, per-post metabox and admin AJAX helpers.
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin UI for ToPersia AI.
 */
class TP_AI_Admin {

	/**
	 * Boot hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta' ) );
		add_action( 'wp_ajax_tpas_test_key', array( __CLASS__, 'test_key' ) );
		// 1.3.0 · P5-D358: the admin makes the summaries - metabox button, bulk action.
		add_action( 'wp_ajax_tpas_admin_generate', array( __CLASS__, 'ajax_generate' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'metabox_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'bulk_register' ) );
		add_action( 'admin_notices', array( __CLASS__, 'bulk_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( TP_AI_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Real hook suffixes returned by WordPress for each registered page,
	 * used by assets() to gate script/style loading. Captured directly
	 * rather than guessed, since the hook-suffix naming convention differs
	 * depending on which parent menu we actually land under (Core,
	 * topersia-render, or a standalone top-level menu of our own) - see
	 * menu() below.
	 *
	 * @var string[]
	 */
	protected static $hooks = array();

	/**
	 * Registers the settings pages. Priority order, matching the pattern
	 * already established by topersia-user-core: live under Core's shared
	 * "topersia" top-level menu so every ToPersia plugin's settings sit in
	 * one place in the sidebar; if Core isn't active, fall back to
	 * topersia-render's menu; if neither is active, stand alone. No hard
	 * dependency is created either way.
	 *
	 * add_submenu_page() returns false (silently - WordPress shows no
	 * error) if the named parent slug's menu hasn't been registered yet,
	 * which can happen from plugin load order or a temporarily deactivated
	 * dependency. The return value is always checked rather than trusted,
	 * per the project's own documented lesson (a submenu disappeared from
	 * the dashboard once from an unchecked add_submenu_page() call).
	 */
	public static function menu() {
		$cap    = 'manage_options';
		$parent = false;

		if ( function_exists( 'topersia_admin_page' ) ) {
			$hook = add_submenu_page( 'topersia', __( 'ToPersia AI', 'topersia-ai-summarizer' ), __( 'هوش مصنوعی', 'topersia-ai-summarizer' ), $cap, 'topersia-ai', array( __CLASS__, 'page' ) );
			if ( $hook ) {
				$parent = 'topersia';
			}
		}

		if ( ! $parent && function_exists( 'topersia_render_settings_page' ) ) {
			$hook = add_submenu_page( 'topersia-render', __( 'ToPersia AI', 'topersia-ai-summarizer' ), __( 'هوش مصنوعی', 'topersia-ai-summarizer' ), $cap, 'topersia-ai', array( __CLASS__, 'page' ) );
			if ( $hook ) {
				$parent = 'topersia-render';
			}
		}

		if ( ! $parent ) {
			$hook = add_menu_page(
				__( 'ToPersia AI', 'topersia-ai-summarizer' ),
				__( 'ToPersia AI', 'topersia-ai-summarizer' ),
				$cap,
				'topersia-ai',
				array( __CLASS__, 'page' ),
				'dashicons-superhero-alt',
				59
			);
			$parent = 'topersia-ai'; // Our own top-level slug becomes the parent for the two pages below.
		}

		self::$hooks[] = $hook;
		self::$hooks   = array_values( array_filter( self::$hooks ) );
	}

	/**
	 * Register the settings groups with the Settings API.
	 */
	public static function register() {
		register_setting( TP_AI_Options::GROUP, TP_AI_Options::OPTION_KEY, array( 'TP_AI_Options', 'sanitize' ) );
		register_setting( 'tpas_prompts', TP_AI_Prompt_Manager::KEY, array( 'TP_AI_Prompt_Manager', 'sanitize' ) );
	}

	/**
	 * Load admin assets on the settings screen only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function assets( $hook ) {
		if ( ! in_array( $hook, self::$hooks, true ) ) {
			return;
		}

		$css_file = TP_AI_PATH . 'assets/css/topersia-ai-admin.css';
		$js_file  = TP_AI_PATH . 'assets/js/topersia-ai-admin.js';

		// filemtime-based version busts caches whenever the asset changes.
		$css_ver = is_file( $css_file ) ? filemtime( $css_file ) : TP_AI_VERSION;
		$js_ver  = is_file( $js_file ) ? filemtime( $js_file ) : TP_AI_VERSION;

		wp_enqueue_style(
			'topersia-ai-admin',
			TP_AI_URL . 'assets/css/topersia-ai-admin.css',
			array(),
			$css_ver
		);

		wp_enqueue_script(
			'topersia-ai-admin',
			TP_AI_URL . 'assets/js/topersia-ai-admin.js',
			array(),
			$js_ver,
			true
		);

		$provider_options = array();
		foreach ( TP_AI_Options::providers() as $slug => $p ) {
			$provider_options[ $slug ] = $p['label'];
		}

		wp_localize_script( 'topersia-ai-admin', 'tpasAdmin', array(
			'ajax'       => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'tpas_admin' ),
			'testing'    => __( 'Testing…', 'topersia-ai-summarizer' ),
			'success'    => __( 'Connected — model replied.', 'topersia-ai-summarizer' ),
			'error'      => __( 'Connection failed.', 'topersia-ai-summarizer' ),
			'providers'  => $provider_options,
			'customProviders' => array( 'custom', 'azure' ),
			'removeLabel'     => __( 'Remove', 'topersia-ai-summarizer' ),
			'testLabel'       => __( 'Test', 'topersia-ai-summarizer' ),
			'apiKeyPlaceholder' => __( 'Paste API key', 'topersia-ai-summarizer' ), // 1.2.2: a new row has no stored key.
		) );
	}

	/**
	 * Add a per-post visibility box on supported post types.
	 */
	public static function meta_box() {
		$post_types = array_map( 'sanitize_key', (array) TP_AI_Options::get_field( 'post_types' ) );

		foreach ( $post_types as $type ) {
			add_meta_box(
				'tpas-visibility',
				__( 'ToPersia AI', 'topersia-ai-summarizer' ),
				array( __CLASS__, 'meta_box_html' ),
				$type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Metabox markup.
	 *
	 * @param WP_Post $post Current post.
	 */
	public static function meta_box_html( $post ) {
		wp_nonce_field( 'tpas_meta', 'tpas_meta_nonce' );

		$hide_buttons = (bool) get_post_meta( $post->ID, '_tpas_hide_buttons', true );
		$hide_panel   = (bool) get_post_meta( $post->ID, '_tpas_hide_panel', true );

		echo '<label style="display:block;margin:0 0 8px;">';
		echo '<input type="checkbox" name="tpas_hide_buttons" value="1"' . checked( $hide_buttons, true, false ) . '> ';
		esc_html_e( 'Hide AI buttons on this post', 'topersia-ai-summarizer' );
		echo '</label>';

		echo '<label style="display:block;margin:0;">';
		echo '<input type="checkbox" name="tpas_hide_panel" value="1"' . checked( $hide_panel, true, false ) . '> ';
		esc_html_e( 'Hide on-page AI panel', 'topersia-ai-summarizer' );
		echo '</label>';

		self::meta_box_outputs( $post );
	}

	/**
	 * Labels of the three states an output can be in.
	 *
	 * @return array
	 */
	public static function status_labels() {
		return array(
			'none'  => __( 'Not made', 'topersia-ai-summarizer' ),
			'fresh' => __( 'Fresh', 'topersia-ai-summarizer' ),
			'stale' => __( 'Stale', 'topersia-ai-summarizer' ),
		);
	}

	/**
	 * 1.3.0 · P5-D358: the state of each output type in this post's own
	 * language, with a Generate / Regenerate button (admin AJAX, own nonce).
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function meta_box_outputs( $post ) {
		$labels = array(
			'summary'   => __( 'Structured summary', 'topersia-ai-summarizer' ),
			'keypoints' => __( 'Key points', 'topersia-ai-summarizer' ),
			'plan'      => __( 'Travel plan', 'topersia-ai-summarizer' ),
		);
		$states = self::status_labels();
		$lang   = TP_AI_Generator::post_lang( $post->ID );

		echo '<div class="tpas-gen" data-post="' . esc_attr( (string) $post->ID ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'tpas_admin_gen' ) ) . '" style="margin-top:12px;border-top:1px solid #dcdcde;padding-top:8px;">';
		echo '<p style="margin:0 0 6px;"><strong>' . esc_html__( 'AI outputs', 'topersia-ai-summarizer' ) . '</strong> <code>' . esc_html( $lang ) . '</code></p>';

		if ( ! TP_AI_Generator::eligible( $post->ID ) ) {
			echo '<p class="description" style="margin:0;">' . esc_html__( 'Made once this post is published.', 'topersia-ai-summarizer' ) . '</p></div>';
			return;
		}

		foreach ( TP_AI_Generator::types() as $type ) {
			$status = TP_AI_Generator::status( $post->ID, $type, $lang );
			echo '<p class="tpas-gen__row" data-type="' . esc_attr( $type ) . '" style="display:flex;gap:6px;align-items:center;justify-content:space-between;margin:0 0 6px;">';
			echo '<span>' . esc_html( $labels[ $type ] ?? $type ) . ' · <span class="tpas-gen__status" data-status="' . esc_attr( $status ) . '">' . esc_html( $states[ $status ] ) . '</span></span>';
			echo '<button type="button" class="button button-small tpas-gen__go">' . esc_html( 'none' === $status ? __( 'Generate', 'topersia-ai-summarizer' ) : __( 'Regenerate', 'topersia-ai-summarizer' ) ) . '</button>';
			echo '</p>';
		}

		echo '<p class="tpas-gen__msg description" aria-live="polite" style="margin:0;"></p></div>';
	}

	/**
	 * Script of the metabox button, on the post editor only.
	 *
	 * @param string $hook Admin page hook.
	 * @return void
	 */
	public static function metabox_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$file = TP_AI_PATH . 'assets/js/topersia-ai-metabox.js';

		wp_enqueue_script( 'topersia-ai-metabox', TP_AI_URL . 'assets/js/topersia-ai-metabox.js', array(), is_file( $file ) ? filemtime( $file ) : TP_AI_VERSION, true );
		wp_localize_script( 'topersia-ai-metabox', 'tpasGen', array(
			'ajax'       => admin_url( 'admin-ajax.php' ),
			'working'    => __( 'Generating…', 'topersia-ai-summarizer' ),
			'done'       => __( 'Done.', 'topersia-ai-summarizer' ),
			'error'      => __( 'Generation failed.', 'topersia-ai-summarizer' ),
			'regenerate' => __( 'Regenerate', 'topersia-ai-summarizer' ),
			'states'     => self::status_labels(),
		) );
	}

	/**
	 * Metabox button: make one output now, for a user who can edit the post.
	 * 1.3.0 · P5-D358. Fresh outputs are rebuilt on purpose (the button says
	 * so); the engine call counts against the daily caps.
	 *
	 * @return void
	 */
	public static function ajax_generate() {
		check_ajax_referer( 'tpas_admin_gen', 'nonce' );

		$post = absint( $_POST['post'] ?? 0 );
		$type = sanitize_key( wp_unslash( $_POST['type'] ?? '' ) );

		if ( ! $post || ! current_user_can( 'edit_post', $post ) ) {
			wp_send_json_error( array( 'message' => 'Forbidden.' ), 403 );
		}

		if ( ! in_array( $type, TP_AI_Generator::types(), true ) ) {
			wp_send_json_error( array( 'message' => 'Unknown output type.' ), 400 );
		}

		$r      = TP_AI_Generator::generate( $post, $type, TP_AI_Generator::post_lang( $post ), true );
		$labels = self::status_labels();
		$status = (string) ( $r['status'] ?? 'none' );
		$data   = array(
			'status' => $status,
			'label'  => $labels[ $status ] ?? $status,
		);

		if ( empty( $r['ok'] ) ) {
			$data['message'] = 'cap' === ( $r['skipped'] ?? '' ) ? __( 'Daily generation cap reached.', 'topersia-ai-summarizer' ) : (string) ( $r['message'] ?? '' );
			wp_send_json_error( $data, 'cap' === ( $r['skipped'] ?? '' ) ? 429 : 200 );
		}

		wp_send_json_success( $data );
	}

	/**
	 * «ساخت خلاصۀ هوش مصنوعی» in the posts list of every enabled type.
	 *
	 * @return void
	 */
	public static function bulk_register() {
		foreach ( array_map( 'sanitize_key', (array) TP_AI_Options::get_field( 'post_types', array() ) ) as $type ) {
			add_filter( 'bulk_actions-edit-' . $type, array( __CLASS__, 'bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-' . $type, array( __CLASS__, 'bulk_handle' ), 10, 3 );
		}
	}

	/**
	 * Add the bulk action.
	 *
	 * @param array $actions Bulk actions.
	 * @return array
	 */
	public static function bulk_actions( $actions ) {
		$actions['tpas_generate'] = __( 'Generate AI summary', 'topersia-ai-summarizer' );

		return $actions;
	}

	/**
	 * Queue the selected posts (Action Scheduler of ToPersia Core, P5-D290),
	 * each in its own language, without rebuilding fresh outputs, up to the
	 * daily cap. No queue: a small batch now, the rest reported.
	 *
	 * @param string $redirect Redirect URL.
	 * @param string $action   Chosen action.
	 * @param int[]  $ids      Selected posts.
	 * @return string
	 */
	public static function bulk_handle( $redirect, $action, $ids ) {
		if ( 'tpas_generate' !== $action ) {
			return $redirect;
		}

		$ids = array_values( array_filter( array_map( 'absint', (array) $ids ), function ( $id ) {
			return $id && current_user_can( 'edit_post', $id );
		} ) );

		$out = TP_AI_Generator::enqueue( $ids, true );

		return add_query_arg( array(
			'tpas_q' => (int) $out['queued'],
			'tpas_r' => (int) $out['ran'],
			'tpas_f' => (int) $out['fresh'],
			'tpas_c' => (int) $out['capped'],
			'tpas_s' => (int) $out['skipped'],
			'tpas_n' => TP_AI_Generator::queue_ready() ? 0 : 1,
		), $redirect );
	}

	/**
	 * Report of the bulk action.
	 *
	 * @return void
	 */
	public static function bulk_notice() {
		if ( ! isset( $_GET['tpas_q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
			return;
		}

		$n = function ( $k ) {
			return absint( $_GET[ $k ] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		};

		$text = sprintf(
			/* translators: 1: queued, 2: made now, 3: already fresh, 4: over the daily cap, 5: skipped */
			__( 'AI summaries — queued: %1$d · made now: %2$d · already fresh: %3$d · over the daily cap: %4$d · skipped: %5$d', 'topersia-ai-summarizer' ),
			$n( 'tpas_q' ),
			$n( 'tpas_r' ),
			$n( 'tpas_f' ),
			$n( 'tpas_c' ),
			$n( 'tpas_s' )
		);

		if ( $n( 'tpas_n' ) ) {
			$text .= ' ' . __( 'The ToPersia Core queue is not available: only a small batch was made now.', 'topersia-ai-summarizer' );
		}

		echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
	}

	/**
	 * Persist the metabox flags.
	 *
	 * @param int $post_id Post id.
	 */
	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['tpas_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['tpas_meta_nonce'] ), 'tpas_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_tpas_hide_buttons', empty( $_POST['tpas_hide_buttons'] ) ? 0 : 1 );
		update_post_meta( $post_id, '_tpas_hide_panel', empty( $_POST['tpas_hide_panel'] ) ? 0 : 1 );
	}

	/**
	 * Settings page wrapper.
	 */
	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Summary-row deletion posts back to this same page (see
		// summaries_section()) - handled here, before any output, so the
		// redirect+exit still works regardless of which tab is showing.
		self::maybe_handle_summary_delete();

		$tabs = array(
			'buttons'   => __( 'AI Assistant buttons', 'topersia-ai-summarizer' ),
			'panel'     => __( 'On-page AI panel', 'topersia-ai-summarizer' ),
			'prompts'   => __( 'Prompt templates', 'topersia-ai-summarizer' ),
			'summaries' => __( 'Summary archive', 'topersia-ai-summarizer' ),
		);

		$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'buttons'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab selector, no state change.
		if ( ! isset( $tabs[ $active ] ) ) {
			$active = 'buttons';
		}
		?>
		<div class="wrap tpas-wrap">
			<div class="tpas-hero">
				<div class="tpas-hero__title">
					<h1><?php esc_html_e( 'ToPersia AI Summarizer', 'topersia-ai-summarizer' ); ?></h1>
					<p><?php esc_html_e( 'Buttons that open AI assistants with a ready structured-summary prompt, and an on-page AI panel that generates summaries from the current page.', 'topersia-ai-summarizer' ); ?></p>
				</div>
				<span class="tpas-hero__badge">v<?php echo esc_html( TP_AI_VERSION ); ?></span>
			</div>

			<nav class="tpas-nav" role="tablist" aria-label="<?php esc_attr_e( 'Plugin sections', 'topersia-ai-summarizer' ); ?>">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<button
						type="button"
						class="tpas-nav__tab<?php echo $slug === $active ? ' is-active' : ''; ?>"
						role="tab"
						aria-selected="<?php echo $slug === $active ? 'true' : 'false'; ?>"
						data-tpas-tab="<?php echo esc_attr( $slug ); ?>"
					><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</nav>

			<?php
			/*
			 * Each tab that saves settings is its OWN <form>, not a shared
			 * one, because "buttons" + "panel" share one settings group
			 * (TP_AI_Options::GROUP) while "prompts" uses a different group
			 * (tpas_prompts) - submitting one group's form must never wipe
			 * out the other group's stored values, which is exactly what
			 * would happen if their fields were split across forms sharing
			 * one settings group. The tab bar above is a sibling of all of
			 * them; CSS/JS toggles panel visibility purely by class, so
			 * panels don't need to share a DOM parent to switch together.
			 */
			?>
			<form method="post" action="options.php">
				<?php settings_fields( TP_AI_Options::GROUP ); ?>

				<div class="tpas-tab-panel<?php echo 'buttons' === $active ? ' is-active' : ''; ?>" data-tpas-panel="buttons" role="tabpanel">
					<?php self::buttons_section(); ?>
					<div class="tpas-submit"><?php submit_button( null, 'primary', 'submit_buttons' ); ?></div>
				</div>

				<div class="tpas-tab-panel<?php echo 'panel' === $active ? ' is-active' : ''; ?>" data-tpas-panel="panel" role="tabpanel">
					<?php self::panel_section(); ?>
					<div class="tpas-submit"><?php submit_button( null, 'primary', 'submit_panel' ); ?></div>
				</div>
			</form>

			<form method="post" action="options.php" class="tpas-tab-panel<?php echo 'prompts' === $active ? ' is-active' : ''; ?>" data-tpas-panel="prompts" role="tabpanel">
				<?php settings_fields( 'tpas_prompts' ); ?>
				<?php self::prompts_section(); ?>
				<div class="tpas-submit"><?php submit_button(); ?></div>
			</form>

			<div class="tpas-tab-panel<?php echo 'summaries' === $active ? ' is-active' : ''; ?>" data-tpas-panel="summaries" role="tabpanel">
				<?php self::summaries_section(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Deletes one archived summary row when its per-row delete form is
	 * submitted. Runs unconditionally at the top of page() - before any
	 * output - regardless of which tab is currently active, since the
	 * delete form posts back to the same URL.
	 *
	 * @return void
	 */
	private static function maybe_handle_summary_delete() {
		if ( ! isset( $_POST['tpas_summary_delete'], $_POST['_tpas_delete_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_tpas_delete_nonce'] ) ), 'tpas_summary_delete' ) ) {
			return;
		}

		TP_AI_DB::delete_summary( absint( $_POST['tpas_summary_delete'] ) );

		wp_safe_redirect( add_query_arg( array( 'deleted' => '1', 'tab' => 'summaries' ), admin_url( 'admin.php?page=topersia-ai' ) ) );
		exit;
	}

	/**
	 * "Prompt templates" tab content: per-language, per-output-type prompt
	 * overrides. Its own settings group (tpas_prompts) - see page()'s
	 * comment on why this is a separate <form>.
	 */
	protected static function prompts_section() {
		$languages = TP_AI_Prompt_Manager::languages();
		$types     = TP_AI_Prompt_Manager::types();
		$overrides = TP_AI_Prompt_Manager::all();

		$type_labels = array(
			'summary'   => __( 'Structured summary', 'topersia-ai-summarizer' ),
			'keypoints' => __( 'Key points', 'topersia-ai-summarizer' ),
			'plan'      => __( 'Travel plan', 'topersia-ai-summarizer' ),
		);

		echo '<p class="description">' . esc_html__( 'Placeholders: {url}, {title}, {site}, {related} (JetEngine relations), {facts} (JetEngine meta). Leave a field empty to use the built-in default.', 'topersia-ai-summarizer' ) . '</p>';

		foreach ( $languages as $lang ) {
			echo '<details class="tpas-lang"' . ( 'fa' === $lang ? ' open' : '' ) . '>';
			echo '<summary class="tpas-lang__summary"><span>' . esc_html( TP_AI_Localize::label( $lang ) ) . '</span> <code>' . esc_html( $lang ) . '</code></summary>';
			echo '<table class="form-table" role="presentation">';

			foreach ( $types as $type ) {
				$default      = TP_AI_Localize::prompt_for_type( $type, $lang );
				$current      = isset( $overrides[ $lang ][ $type ] ) ? $overrides[ $lang ][ $type ] : '';
				$is_override  = is_string( $current ) && '' !== trim( $current );
				$field_id     = 'tpas-prompt-' . $lang . '-' . $type;

				echo '<tr><th scope="row"><label for="' . esc_attr( $field_id ) . '">' . esc_html( $type_labels[ $type ] ?? $type ) . '</label>';
				if ( $is_override ) {
					echo ' <em class="tpas-flag">' . esc_html__( 'custom', 'topersia-ai-summarizer' ) . '</em>';
				}
				echo '</th><td><textarea class="large-text code" rows="5" id="' . esc_attr( $field_id ) . '" name="tpas_prompt_overrides[' . esc_attr( $lang ) . '][' . esc_attr( $type ) . ']" placeholder="' . esc_attr( $default ) . '">' . esc_textarea( $current ) . '</textarea></td></tr>';
			}

			echo '</table></details>';
		}
	}

	/**
	 * "Summary archive" tab content: the stored-summaries list with inline
	 * viewing and per-row deletion. Not a settings form (the Settings API
	 * doesn't fit a paginated list+actions view), so it sits outside both
	 * <form> elements in page() as a plain panel.
	 */
	protected static function summaries_section() {
		$per_page = 25;
		$paged    = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination.
		$rows     = TP_AI_DB::list_summaries( $per_page, ( $paged - 1 ) * $per_page );

		$type_labels = array(
			'summary'   => __( 'Structured summary', 'topersia-ai-summarizer' ),
			'keypoints' => __( 'Key points', 'topersia-ai-summarizer' ),
			'plan'      => __( 'Travel plan', 'topersia-ai-summarizer' ),
		);

		if ( isset( $_GET['deleted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Summary deleted.', 'topersia-ai-summarizer' ) . '</p></div>';
		}

		echo '<p class="description">' . esc_html__( 'AI summaries stored while the panel runs. They are rebuilt automatically when a post is edited.', 'topersia-ai-summarizer' ) . '</p>';

		echo '<table class="widefat striped"><thead><tr>'
			. '<th>' . esc_html__( 'Post', 'topersia-ai-summarizer' ) . '</th>'
			. '<th>' . esc_html__( 'Output', 'topersia-ai-summarizer' ) . '</th>'
			. '<th>' . esc_html__( 'Lang', 'topersia-ai-summarizer' ) . '</th>'
			. '<th>' . esc_html__( 'Engine', 'topersia-ai-summarizer' ) . '</th>'
			. '<th>' . esc_html__( 'Updated', 'topersia-ai-summarizer' ) . '</th>'
			. '<th></th></tr></thead><tbody>';

		if ( empty( $rows ) ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No summaries stored yet. Open any page with the AI panel and generate one.', 'topersia-ai-summarizer' ) . '</td></tr>';
		}

		foreach ( $rows as $row ) {
			echo '<tr><td><strong>' . esc_html( $row['post_title'] ) . '</strong> <code>#' . esc_html( $row['post_id'] ) . '</code>'
				. '<details class="tpas-summary-detail"><summary>' . esc_html__( 'View content', 'topersia-ai-summarizer' ) . '</summary><pre>' . esc_html( $row['content'] ) . '</pre></details></td>';
			echo '<td>' . esc_html( $type_labels[ $row['type'] ] ?? $row['type'] ) . '</td>';
			echo '<td><code>' . esc_html( $row['language'] ) . '</code></td>';
			echo '<td>';
			if ( 'internal' === $row['source'] ) {
				echo esc_html__( 'Internal', 'topersia-ai-summarizer' );
			} else {
				echo esc_html( $row['provider'] ? $row['provider'] : '—' );
				echo $row['model'] ? ' <code>' . esc_html( $row['model'] ) . '</code>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped above.
			}
			echo '</td>';
			echo '<td>' . esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['created_at'] ) ) . '</td>';
			echo '<td><form method="post" style="display:inline;">';
			wp_nonce_field( 'tpas_summary_delete', '_tpas_delete_nonce' );
			echo '<input type="hidden" name="tpas_summary_delete" value="' . esc_attr( $row['id'] ) . '">'
				. '<button type="submit" class="button-link-delete">' . esc_html__( 'Delete', 'topersia-ai-summarizer' ) . '</button></form></td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render the "AI Assistant buttons" settings section.
	 */
	protected static function buttons_section() {
		$options = TP_AI_Options::get();
		echo '<table class="form-table" role="presentation">';
		self::title_row( __( 'AI Assistant buttons', 'topersia-ai-summarizer' ) );

		self::input_row(
			__( 'Heading', 'topersia-ai-summarizer' ),
			'heading',
			esc_attr( $options['heading'] ),
			__( 'Leave empty to use the localized default for the page language.', 'topersia-ai-summarizer' )
		);

		self::textarea_row(
			__( 'Prompt', 'topersia-ai-summarizer' ),
			'prompt',
			$options['prompt'],
			__( 'Placeholders: {url}, {title}, {site}.', 'topersia-ai-summarizer' )
		);

		/* 1.4.1 · P5-D392: گزینۀ «دستیارها» برداشته شد — هیچ پیوند دستیار بیرونی */

		self::post_types_row( $options );
		self::select_row( __( 'Position', 'topersia-ai-summarizer' ), 'position', $options['position'], array(
			'after_thumbnail' => __( 'After the featured image', 'topersia-ai-summarizer' ),
			'before'          => __( 'Before the content', 'topersia-ai-summarizer' ),
			'after'           => __( 'After the content', 'topersia-ai-summarizer' ),
			'both'            => __( 'Before and after', 'topersia-ai-summarizer' ),
			'manual'          => __( 'Manual (shortcode / block)', 'topersia-ai-summarizer' ),
		) );
		self::checkbox_row( __( 'Also show on archive pages', 'topersia-ai-summarizer' ), 'show_on_archive', $options['show_on_archive'] );
		self::select_row( __( 'Style', 'topersia-ai-summarizer' ), 'style', $options['style'], array(
			'filled'  => __( 'Filled', 'topersia-ai-summarizer' ),
			'outline' => __( 'Outline', 'topersia-ai-summarizer' ),
			'minimal' => __( 'Minimal', 'topersia-ai-summarizer' ),
		) );
		self::select_row( __( 'Layout', 'topersia-ai-summarizer' ), 'layout', $options['layout'], array(
			'stretch' => __( 'Full width', 'topersia-ai-summarizer' ),
			'inline'  => __( 'Inline', 'topersia-ai-summarizer' ),
		) );
		self::select_row( __( 'Alignment', 'topersia-ai-summarizer' ), 'align', $options['align'], array(
			'start'  => __( 'Start', 'topersia-ai-summarizer' ),
			'center' => __( 'Center', 'topersia-ai-summarizer' ),
			'end'    => __( 'End', 'topersia-ai-summarizer' ),
		) );
		self::select_row( __( 'Heading size', 'topersia-ai-summarizer' ), 'heading_size', $options['heading_size'], array(
			'large'  => __( 'Large', 'topersia-ai-summarizer' ),
			'normal' => __( 'Normal', 'topersia-ai-summarizer' ),
			'small'  => __( 'Small', 'topersia-ai-summarizer' ),
		) );
		self::input_row( __( 'Accent color', 'topersia-ai-summarizer' ), 'accent', esc_attr( $options['accent'] ), '', 'color' );
		self::input_row( __( 'Corner radius (px)', 'topersia-ai-summarizer' ), 'radius', (int) $options['radius'], '', 'number' );
		self::select_row( __( 'Icon style', 'topersia-ai-summarizer' ), 'icon_style', $options['icon_style'], array(
			'brand'   => __( 'Brand marks', 'topersia-ai-summarizer' ),
			'neutral' => __( 'Neutral glyphs', 'topersia-ai-summarizer' ),
		) );
		self::checkbox_row( __( 'Show icons', 'topersia-ai-summarizer' ), 'show_icons', $options['show_icons'] );
		self::checkbox_row( __( 'Copy-prompt button', 'topersia-ai-summarizer' ), 'copy_button', $options['copy_button'] );
		self::checkbox_row( __( 'Open in a new tab', 'topersia-ai-summarizer' ), 'new_tab', $options['new_tab'] );
		self::checkbox_row( __( 'Add rel="nofollow"', 'topersia-ai-summarizer' ), 'nofollow', $options['nofollow'] );
		self::checkbox_row( __( 'Track clicks (anonymously)', 'topersia-ai-summarizer' ), 'track_clicks', $options['track_clicks'] );

		echo '</table>';
	}

	/**
	 * Renders the repeatable "AI Engines" field: one row per configured
	 * engine (provider + its own API key + optional model/base URL), a
	 * radio per row marking the default engine used for automatic
	 * generation, a "+" button to add another row, and a hidden
	 * `<template>` the admin JS clones for that "+" button.
	 *
	 * @param array $options Current plugin settings.
	 * @return void
	 */
	protected static function engines_field( array $options ) {
		$rows       = TP_AI_Options::engines();
		$default_id = (string) $options['internal_default_engine'];

		echo '<tr><th scope="row">' . esc_html__( 'AI engines', 'topersia-ai-summarizer' ) . '</th><td>';
		echo '<table class="tpas-engines-table" id="tpas-engines-table"><thead><tr>';
		echo '<th class="tpas-engines-col-default">' . esc_html__( 'Default', 'topersia-ai-summarizer' ) . '</th>';
		echo '<th>' . esc_html__( 'Provider', 'topersia-ai-summarizer' ) . '</th>';
		echo '<th>' . esc_html__( 'API key', 'topersia-ai-summarizer' ) . '</th>';
		echo '<th>' . esc_html__( 'Model / deployment (optional)', 'topersia-ai-summarizer' ) . '</th>';
		echo '<th class="tpas-engines-col-url" data-tpas-custom>' . esc_html__( 'Base URL (custom / Azure)', 'topersia-ai-summarizer' ) . '</th>';
		echo '<th></th><th></th>';
		echo '</tr></thead><tbody id="tpas-engines-body">';

		foreach ( $rows as $row ) {
			self::engine_row_html( $row, $row['id'] === $default_id );
		}

		echo '</tbody></table>';
		echo '<p><button type="button" class="button" id="tpas-add-engine">+ ' . esc_html__( 'Add AI engine', 'topersia-ai-summarizer' ) . '</button></p>';
		echo '<p class="description">' . esc_html__( 'Add one row per AI provider you have a key for. Pick the "Default" radio on the row the on-page panel should use automatically.', 'topersia-ai-summarizer' ) . '</p>';

		echo '<template id="tpas-engine-template"><table><tbody>';
		self::engine_row_html(
			array( 'id' => '', 'provider' => '', 'api_key' => '', 'model' => '', 'base_url' => '' ),
			false
		);
		echo '</tbody></table></template>';

		echo '</td></tr>';
	}

	/**
	 * Renders one engine row (`<tr>`). Used both for already-configured
	 * rows and, with an empty row, as the content of the `<template>` the
	 * "+" button clones - the JS side is responsible for giving a cloned
	 * template row a fresh client-side id (see topersia-ai-admin.js).
	 *
	 * @param array $row             Engine row: id, provider, api_key, model, base_url.
	 * @param bool  $checked_default Whether this row is the current default engine.
	 * @return void
	 */
	protected static function engine_row_html( array $row, $checked_default ) {
		$id        = isset( $row['id'] ) ? (string) $row['id'] : '';
		$provider  = isset( $row['provider'] ) ? (string) $row['provider'] : '';
		$has_key   = ! empty( $row['api_key'] );
		$name_base = 'tpas_settings[internal_engines][' . esc_attr( $id ? $id : 'new' ) . ']';

		echo '<tr class="tpas-engine-row" data-tpas-engine-row data-id="' . esc_attr( $id ) . '">';

		echo '<td class="tpas-engines-col-default">';
		echo '<input type="radio" name="tpas_settings[internal_default_engine]" value="' . esc_attr( $id ) . '"' . checked( $checked_default, true, false ) . '>';
		echo '</td>';

		echo '<td>';
		echo '<select name="' . $name_base . '[provider]" data-tpas-provider>';
		echo '<option value="">' . esc_html__( '— choose —', 'topersia-ai-summarizer' ) . '</option>';
		foreach ( TP_AI_Options::providers() as $slug => $p ) {
			echo '<option value="' . esc_attr( $slug ) . '"' . selected( $provider, $slug, false ) . '>' . esc_html( $p['label'] ) . '</option>';
		}
		echo '</select>';
		echo '</td>';

		echo '<td>';
		// 1.2.2 · P5-D355: write-only - the stored key is never printed; an
		// empty field keeps it, and only the explicit box deletes it.
		if ( TP_AI_Options::key_from_constant() ) {
			// 1.2.3 · P5-D355: TOPERSIA_AI_API_KEY in wp-config wins; nothing is stored from this form.
			echo '<span class="tpas-engine-key-const">' . esc_html__( 'Set in wp-config', 'topersia-ai-summarizer' ) . '</span>';
			echo '</td>';
			$has_key = false;
		} else {
		echo '<input type="password" name="' . $name_base . '[api_key]" value="" placeholder="' . esc_attr( $has_key ? __( 'Saved — type a new value to change it', 'topersia-ai-summarizer' ) : __( 'Paste API key', 'topersia-ai-summarizer' ) ) . '" autocomplete="new-password">';
		if ( $has_key ) {
			echo '<span class="tpas-engine-key-set">' . esc_html__( 'Key saved', 'topersia-ai-summarizer' ) . '</span>';
			echo '<label class="tpas-engine-key-clear"><input type="checkbox" name="' . $name_base . '[api_key_clear]" value="1"> ' . esc_html__( 'Clear stored key', 'topersia-ai-summarizer' ) . '</label>';
		}
		echo '</td>';
		}

		echo '<td>';
		echo '<input type="text" name="' . $name_base . '[model]" value="' . esc_attr( $row['model'] ?? '' ) . '">';
		echo '</td>';

		echo '<td class="tpas-engines-col-url" data-tpas-custom>';
		echo '<input type="url" name="' . $name_base . '[base_url]" value="' . esc_attr( $row['base_url'] ?? '' ) . '" placeholder="https://…">';
		echo '</td>';

		echo '<td><button type="button" class="button tpas-engine-test">' . esc_html__( 'Test', 'topersia-ai-summarizer' ) . '</button><span class="tpas-engine-test-result" aria-live="polite"></span></td>';
		echo '<td><button type="button" class="button-link tpas-engine-remove" aria-label="' . esc_attr__( 'Remove this engine', 'topersia-ai-summarizer' ) . '">&times;</button></td>';

		echo '<input type="hidden" name="' . $name_base . '[id]" value="' . esc_attr( $id ) . '">';
		echo '</tr>';
	}

	/**
	 * Render the "On-page AI panel" settings section.
	 */
	protected static function panel_section() {
		$options = TP_AI_Options::get();

		echo '<table class="form-table" role="presentation">';
		self::title_row( __( 'On-page AI panel', 'topersia-ai-summarizer' ) );

		self::checkbox_row( __( 'Enable the on-page AI panel', 'topersia-ai-summarizer' ), 'internal_enabled', $options['internal_enabled'] );

		self::select_row( __( 'Engine mode', 'topersia-ai-summarizer' ), 'internal_engine', $options['internal_engine'], array(
			'auto'     => __( 'Auto — API when a key is set, else internal', 'topersia-ai-summarizer' ),
			'ai'       => __( 'AI API always', 'topersia-ai-summarizer' ),
			'internal' => __( 'Internal engine (free, offline)', 'topersia-ai-summarizer' ),
		) );

		self::engines_field( $options );

		/* عدد اعشاری ۰ تا ۲ همان‌طور که به مدل می‌رود — نه دهم آن.  SPRINT-4.4 ۲٫۷ */
		echo '<tr><th scope="row"><label for="tpas-internal_temperature">' . esc_html__( 'Temperature (0–2)', 'topersia-ai-summarizer' ) . '</label></th><td>'
			. '<input type="number" step="0.1" min="0" max="2" class="small-text" id="tpas-internal_temperature" name="tpas_settings[internal_temperature]" value="' . esc_attr( (string) TP_AI_Options::sanitize_temperature( $options['internal_temperature'] ) ) . '">'
			. '<p class="description">' . esc_html__( 'A decimal from 0 to 2, sent to the model as is.', 'topersia-ai-summarizer' ) . '</p></td></tr>';
		self::input_row( __( 'Max output tokens', 'topersia-ai-summarizer' ), 'internal_max_tokens', (int) $options['internal_max_tokens'], '', 'number' );
		self::input_row( __( 'Context size (chars)', 'topersia-ai-summarizer' ), 'internal_context_chars', (int) $options['internal_context_chars'], '', 'number' );
		self::input_row( __( 'Cache (hours)', 'topersia-ai-summarizer' ), 'internal_cache_hours', (int) $options['internal_cache_hours'], '', 'number' );
		self::input_row( __( 'Rate limit (requests/hour/IP)', 'topersia-ai-summarizer' ), 'internal_throttle', (int) $options['internal_throttle'], '', 'number' );

		echo '<tr><th scope="row">' . esc_html__( 'Output types', 'topersia-ai-summarizer' ) . '</th><td>';
		$labels = array(
			'summary'   => __( 'Structured summary', 'topersia-ai-summarizer' ),
			'keypoints' => __( 'Key points', 'topersia-ai-summarizer' ),
			'plan'      => __( 'Travel plan', 'topersia-ai-summarizer' ),
		);
		echo '<span class="tpas-chips">';
		foreach ( $labels as $slug => $label ) {
			$checked = in_array( $slug, (array) $options['internal_types'], true );
			echo '<label class="tpas-chip">';
			echo '<input type="checkbox" name="tpas_settings[internal_types][]" value="' . esc_attr( $slug ) . '"' . checked( $checked, true, false ) . '>';
			echo esc_html( $label ) . '</label>';
		}
		echo '</span></td></tr>';

		$type_labels = array();
		foreach ( $labels as $slug => $label ) {
			$type_labels[ $slug ] = $label;
		}
		self::select_row( __( 'Default output type', 'topersia-ai-summarizer' ), 'internal_default_type', $options['internal_default_type'], $type_labels );
		self::checkbox_row( __( 'Auto-generate on first view', 'topersia-ai-summarizer' ), 'internal_auto_first', $options['internal_auto_first'] );

		/* 1.3.0 · P5-D358: summaries are made by the admin only */
		self::title_row( __( 'Who makes the summaries', 'topersia-ai-summarizer' ) );
		self::checkbox_row( __( 'Allow visitor requests (later versions)', 'topersia-ai-summarizer' ), 'visitor_requests', $options['visitor_requests'] );
		self::checkbox_row( __( 'Generate automatically after publishing or a content change', 'topersia-ai-summarizer' ), 'auto_generate', $options['auto_generate'] );
		self::input_row( __( 'Admin generations per day', 'topersia-ai-summarizer' ), 'admin_daily_cap', (int) $options['admin_daily_cap'], __( 'Metabox, bulk action and automatic generation together. The site daily cap of ToPersia Render also applies.', 'topersia-ai-summarizer' ), 'number' );
		self::select_row( __( 'Panel position', 'topersia-ai-summarizer' ), 'panel_position', $options['panel_position'], array(
			'manual' => __( 'Manual (shortcode / block)', 'topersia-ai-summarizer' ),
			'top'    => __( 'Top of content', 'topersia-ai-summarizer' ),
			'bottom' => __( 'Bottom of content', 'topersia-ai-summarizer' ),
		) );

		echo '<tr><th scope="row"><label for="tpas-analytics_enabled">' . esc_html__( 'Capability analytics', 'topersia-ai-summarizer' ) . '</label></th><td>';
		echo '<label>';
		echo '<input type="checkbox" id="tpas-analytics_enabled" name="tpas_settings[analytics_enabled]" value="1"' . checked( (int) $options['analytics_enabled'], 1, false ) . '>';
		echo ' ' . esc_html__( 'Enable', 'topersia-ai-summarizer' ) . '</label>';
		echo '<p class="description">' . esc_html__( 'Store anonymous click counts on the AI panel. No IP or user agent is recorded.', 'topersia-ai-summarizer' ) . '</p>';
		echo '</td></tr>';

		echo '</table>';

		self::insights_card( $options );
	}

	/**
	 * Small analytics snapshot card shown on the panel tab.
	 *
	 * @param array $options Settings array.
	 */
	protected static function insights_card( $options ) {
		$enabled = ! empty( $options['analytics_enabled'] );

		echo '<div class="tpas-card">';
		echo '<h2 class="tpas-section">' . esc_html__( 'Insights (last 30 days)', 'topersia-ai-summarizer' ) . '</h2>';

		if ( ! $enabled ) {
			echo '<p class="description">' . esc_html__( 'Enable "Capability analytics" above to collect anonymous usage counts.', 'topersia-ai-summarizer' ) . '</p>';
			echo '</div>';

			return;
		}

		$by_provider = TP_AI_DB::events_by_provider( 'assistant_click', 30 );
		$top_posts   = TP_AI_DB::events_top_posts( 'assistant_click', 30, 8 );

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Engine', 'topersia-ai-summarizer' ) . '</th>';
		echo '<th>' . esc_html__( 'Clicks', 'topersia-ai-summarizer' ) . '</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $by_provider ) ) {
			echo '<tr><td colspan="2">' . esc_html__( 'No clicks recorded yet.', 'topersia-ai-summarizer' ) . '</td></tr>';
		}

		foreach ( $by_provider as $row ) {
			$label = $row['provider'] ? $row['provider'] : 'internal';
			echo '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( $row['total'] ) . '</td></tr>';
		}

		echo '</tbody></table>';

		if ( ! empty( $top_posts ) ) {
			echo '<h3 class="tpas-subhead">' . esc_html__( 'Most used pages', 'topersia-ai-summarizer' ) . '</h3>';
			echo '<ol class="tpas-top">';
			foreach ( $top_posts as $row ) {
				$title = get_the_title( (int) $row['post_id'] );
				$title = $title ? $title : '#' . $row['post_id'];
				echo '<li><span>' . esc_html( wp_strip_all_tags( $title ) ) . '</span> — ' . esc_html( $row['total'] ) . '</li>';
			}
			echo '</ol>';
		}

		echo '</div>';
	}

	/**
	 * A section heading row.
	 *
	 * @param string $title Row title.
	 */
	protected static function title_row( $title ) {
		echo '<tr><th colspan="2"><span class="tpas-eyebrow"></span><h2 class="tpas-section" style="margin:18px 0 6px;">' . esc_html( $title ) . '</h2></th></tr>';
	}

	/**
	 * Generic single-line field row.
	 *
	 * @param string $title   Row title.
	 * @param string $key     Option key.
	 * @param string $value   Current value.
	 * @param string $note    Helper text.
	 * @param string $type    Input type.
	 * @param string $data    Optional data attribute (space separated selector).
	 * @param array  $extra   Extra attributes.
	 */
	protected static function input_row( $title, $key, $value, $note = '', $type = 'text', $data = '', array $extra = array() ) {
		echo '<tr><th scope="row"><label for="tpas-' . esc_attr( $key ) . '">' . esc_html( $title ) . '</label></th><td>';
		echo '<input type="' . esc_attr( $type ) . '" class="regular-text" id="tpas-' . esc_attr( $key ) . '" name="tpas_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" ' . ( $data ? 'data-tpas-field="' . esc_attr( $data ) . '"' : '' ) . '>';
		if ( $note ) {
			echo '<p class="description">' . esc_html( $note ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Textarea row.
	 */
	protected static function textarea_row( $title, $key, $value, $note = '' ) {
		echo '<tr><th scope="row"><label for="tpas-' . esc_attr( $key ) . '">' . esc_html( $title ) . '</label></th><td>';
		echo '<textarea class="large-text code" rows="4" id="tpas-' . esc_attr( $key ) . '" name="tpas_settings[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea>';
		if ( $note ) {
			echo '<p class="description">' . esc_html( $note ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Select row.
	 *
	 * @param string $title   Row title.
	 * @param string $key     Option key.
	 * @param string $value   Current value.
	 * @param array  $choices Labeled choices.
	 * @param array  $extra   Extra attributes.
	 */
	protected static function select_row( $title, $key, $value, $choices, array $extra = array() ) {
		echo '<tr><th scope="row"><label for="tpas-' . esc_attr( $key ) . '">' . esc_html( $title ) . '</label></th><td>';
		echo '<select id="tpas-' . esc_attr( $key ) . '" name="tpas_settings[' . esc_attr( $key ) . ']"' . ( empty( $extra ) ? '' : ' data-tpas-field="provider"' ) . '>';
		foreach ( $choices as $slug => $label ) {
			echo '<option value="' . esc_attr( $slug ) . '"' . selected( $value, $slug, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '</td></tr>';
	}

	/**
	 * Checkbox row.
	 *
	 * @param string $title Row title.
	 * @param string $key   Option key.
	 * @param int    $value Current value.
	 */
	protected static function checkbox_row( $title, $key, $value ) {
		echo '<tr><th scope="row"><label for="tpas-' . esc_attr( $key ) . '">' . esc_html( $title ) . '</label></th><td>';
		echo '<label>';
		echo '<input type="checkbox" id="tpas-' . esc_attr( $key ) . '" name="tpas_settings[' . esc_attr( $key ) . ']" value="1"' . checked( (int) $value, 1, false ) . '>';
		echo ' ' . esc_html__( 'Enable', 'topersia-ai-summarizer' ) . '</label>';
		echo '</td></tr>';
	}

	/**
	 * Post type checkboxes.
	 *
	 * @param array $options Settings array.
	 */
	protected static function post_types_row( $options ) {
		$types = get_post_types( array( 'public' => true ), 'objects' );
		$row   = array();
		foreach ( $types as $slug => $type ) {
			if ( 'attachment' === $slug ) {
				continue;
			}
			$checked = in_array( $slug, (array) $options['post_types'], true );
			$row[]   = '<label class="tpas-chip">'
				. '<input type="checkbox" name="tpas_settings[post_types][]" value="' . esc_attr( $slug ) . '"' . checked( $checked, true, false ) . '>'
				. esc_html( $type->labels->name ) . '</label>';
		}
		self::raw_row( __( 'Show on post types', 'topersia-ai-summarizer' ), '<span class="tpas-chips">' . implode( '', $row ) . '</span>' );
	}

	/**
	 * Raw HTML row.
	 *
	 * @param string $title Row title.
	 * @param string $html  Row markup.
	 */
	protected static function raw_row( $title, $html ) {
		echo '<tr><th scope="row">' . esc_html( $title ) . '</th><td>' . $html . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Settings link on the plugins list.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public static function action_links( $links ) {
		$url    = admin_url( 'admin.php?page=topersia-ai' );
		$built  = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'topersia-ai-summarizer' ) . '</a>';

		array_unshift( $links, $built );

		return $links;
	}

	/**
	 * Test the typed (or stored) API key for one row, without saving.
	 */
	public static function test_key() {
		check_ajax_referer( 'tpas_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'deepseek';
		$api_key  = isset( $_POST['api_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) ) : '';

		// The key field may be submitted empty when testing an already-saved
		// row (the browser never re-sends a stored secret) - fall back to
		// the stored value for that row id in that case.
		if ( '' === $api_key && ! empty( $_POST['id'] ) ) {
			$stored = TP_AI_Options::engine_by_id( sanitize_text_field( wp_unslash( $_POST['id'] ) ) );
			$api_key = $stored && isset( $stored['api_key'] ) ? $stored['api_key'] : '';
		}

		$engine = array(
			'provider' => $provider,
			'api_key'  => $api_key,
			'base_url' => isset( $_POST['base_url'] ) ? esc_url_raw( wp_unslash( $_POST['base_url'] ) ) : '',
			'model'    => isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : '',
		);

		$result = TP_AI_API::chat(
			'You are a connectivity check. Reply with the single word OK and nothing else.',
			'Connectivity check.',
			array( 'temperature' => 0, 'max_tokens' => 8 ),
			$engine
		);

		if ( $result['ok'] ) {
			wp_send_json_success( array( 'message' => $result['data'] ) );
		}

		wp_send_json_error( array( 'message' => $result['message'] ) );
	}
}