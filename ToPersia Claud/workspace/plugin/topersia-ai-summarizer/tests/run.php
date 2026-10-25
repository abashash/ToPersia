<?php
/**
 * Test bootstrap + runner for ToPersia AI Summarizer.
 *
 * Loads the plugin classes against the WP stubs and runs every test file
 * under tests/. Run with:  php tests/run.php
 *
 * @package TP_AI_Summarizer_Tests
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

define( 'ABSPATH', __DIR__ . '/wp/' );
define( 'TP_AI_VERSION', '1.0.0' );
define( 'TP_AI_FILE', __DIR__ . '/topersia-ai-summarizer.php' );
define( 'TP_AI_PATH', __DIR__ . '/../' );
define( 'TP_AI_URL', 'https://example.test/wp-content/plugins/topersia-ai-summarizer/' );

require __DIR__ . '/stubs.php';

$GLOBALS['tp_is_admin'] = true;

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
foreach ( array( 'TPAS_SUMMARY_MIN' => 250, 'TPAS_SUMMARY_MAX' => 450, 'TPAS_KEYPOINTS_MIN' => 4, 'TPAS_KEYPOINTS_MAX' => 6, 'TPAS_KEYPOINT_MIN' => 40, 'TPAS_KEYPOINT_MAX' => 140, 'TPAS_PLAN_MIN' => 200, 'TPAS_PLAN_MAX' => 400 ) as $k => $v ) {
	defined( $k ) || define( $k, $v );
}
require_once TP_AI_PATH . 'includes/class-tp-ai-bounds.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-summary.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-generator.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-core-bridge.php';
require_once TP_AI_PATH . 'includes/class-tp-ai-admin.php';

$GLOBALS['tp_pass'] = 0;
$GLOBALS['tp_fail'] = 0;
$GLOBALS['tp_group'] = '';

function t( $label, $ok, $note = '' ) {
	if ( $ok ) {
		$GLOBALS['tp_pass']++;
		echo "  OK  " . $label . "\n";
	} else {
		$GLOBALS['tp_fail']++;
		echo "  FAIL " . $label . ( '' !== $note ? "   [{$note}]" : '' ) . "\n";
	}
}

function group( $name ) {
	$GLOBALS['tp_group'] = $name;
	echo "\n== " . $name . " ==\n";
}

/**
 * Call an AJAX handler and capture the wp_send_json payload.
 *
 * @return array|null { ok, data } or null when no JSON was sent.
 */
function tp_ajax( $handler, $post = array() ) {
	$GLOBALS['tp_ajax'] = null;
	$_POST = array_merge( array( 'nonce' => wp_create_nonce( 'tpas_ai' ) ), $post );
	try {
		call_user_func( $handler );
	} catch ( TP_Test_Ajax_Exit $e ) {
		return $GLOBALS['tp_ajax'];
	}
	return null;
}

// Seed a post the tests can use.
$GLOBALS['tp_posts'][42] = array(
	'type'    => 'post',
	'title'   => 'Isfahan: The Jewel of Persia',
	'content' => 'Isfahan is the third-largest province of Iran, located in the center of the Iranian plateau. The city is famous for Naqsh-e Jahan Square, a UNESCO World Heritage Site, and the historic Si-o-se-pol bridge. The population of Isfahan province is about 5.1 million people and its area is about 107,000 square kilometers.',
);

// Drive every group of tests.
foreach ( glob( __DIR__ . '/test-*.php' ) as $file ) {
	require $file;
}

echo "\n" . str_repeat( '=', 60 ) . "\n";
printf( "  passed: %d   failed: %d\n", $GLOBALS['tp_pass'], $GLOBALS['tp_fail'] );
echo str_repeat( '=', 60 ) . "\n";

exit( $GLOBALS['tp_fail'] ? 1 : 0 );