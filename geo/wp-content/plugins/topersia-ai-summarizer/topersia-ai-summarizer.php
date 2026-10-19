<?php
/**
 * Plugin Name:       ToPersia AI Summarizer
 * Plugin URI:        https://topersia.com/
 * Description:       Dedicated for ToPersia: buttons that open AI assistants (ChatGPT, Perplexity, Claude, Grok, Copilot, Gemini, DeepSeek) with a ready structured-summary prompt, plus an on-page AI panel that generates summaries server-side from the current page.
 * Version:           1.3.2
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * Author:            ToPersia
 * Author URI:        https://topersia.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       topersia-ai-summarizer
 * Domain Path:       /languages
 *
 * @package TP_AI_Summarizer
 */

defined( 'ABSPATH' ) || exit;

define( 'TP_AI_VERSION', '1.3.2' );
define( 'TP_AI_FILE', __FILE__ );
define( 'TP_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'TP_AI_URL', plugin_dir_url( __FILE__ ) );

require_once TP_AI_PATH . 'includes/class-tp-ai-localize.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-options.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-services.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-api.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-context.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-internal.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-prompt-manager.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-db.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-render.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-frontend.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-shortcode.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-block.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-tracker.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-summary.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-generator.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-core-bridge.php';

if ( is_admin() ) {
	require_once TP_AI_PATH . 'includes/class-tp-ai-admin.php';
}

/**
 * Load the plugin text domain.
 *
 * @return void
 */
function tp_ai_load_textdomain() {
	load_plugin_textdomain(
		'topersia-ai-summarizer',
		false,
		dirname( plugin_basename( TP_AI_FILE ) ) . '/languages'
	);
}
add_action( 'init', 'tp_ai_load_textdomain' );

/**
 * Boot the plugin components.
 *
 * @return void
 */
function tp_ai_init() {
	// Create / upgrade custom tables once the schema changes.
	if ( TP_AI_DB::needs_install() ) {
		TP_AI_DB::install();
	}

	TP_AI_Frontend::init();
	TP_AI_Shortcode::init();
	TP_AI_Block::init();
	TP_AI_Tracker::init();
	TP_AI_Summary::init();
	TP_AI_Generator::init(); // 1.3.0 · P5-D358: admin-only generation, queue job, auto-generate.
	TP_AI_Core_Bridge::init();

	// 1.2.2 · P5-D355: whenever the settings row is created or saved, keep it out of autoload.
	add_action( 'added_option', array( 'TP_AI_Options', 'no_autoload' ), 10, 1 );
	add_action( 'updated_option', array( 'TP_AI_Options', 'no_autoload' ), 10, 1 );

	if ( is_admin() ) {
		TP_AI_Admin::init();
	}
}
add_action( 'plugins_loaded', 'tp_ai_init' );

/**
 * Register the Elementor widget when Elementor is active.
 *
 * @return void
 */
function tp_ai_init_elementor() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		return;
	}
	require_once TP_AI_PATH . 'includes/class-tp-ai-elementor.php';
	TP_AI_Elementor::init();
}
add_action( 'plugins_loaded', 'tp_ai_init_elementor', 20 );

/**
 * Store default settings on activation.
 *
 * @return void
 */
function tp_ai_activate() {
	if ( false === get_option( TP_AI_Options::OPTION_KEY ) ) {
		add_option( TP_AI_Options::OPTION_KEY, TP_AI_Options::defaults(), '', false ); // 1.2.2: keys never autoloaded.
	}

	TP_AI_DB::install();
}
register_activation_hook( __FILE__, 'tp_ai_activate' );