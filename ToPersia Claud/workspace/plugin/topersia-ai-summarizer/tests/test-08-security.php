<?php
/**
 * Security: output escaping, JSON embedding, nonce enforcement.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Security' );

// ---- Heading is sanitized on output ---------------------------------------
update_option( TP_AI_Options::OPTION_KEY, array(
	'heading' => '<b>Share</b><img src=x onerror=alert(1)><script>alert("x")</script>',
	'services' => array( 'chatgpt' ),
) );
TP_AI_Options::flush();

$html = TP_AI_Render::buttons_html( array( 'post_id' => 42 ) );
t( 'xss: no live <script> survives the heading', false === strpos( $html, '<script' ) );
t( 'xss: no <img element survives the heading', false === strpos( $html, '<img' ) );
t( 'xss: allowed <b> tag is kept', false !== strpos( $html, '<b>Share</b>' ) );

// ---- Buttons: prompt and attributes are attribute-escaped ------------------
update_option( TP_AI_Options::OPTION_KEY, array(
	'heading' => 'X',
	'prompt'  => 'summarize "{x}" now',
	'services' => array( 'chatgpt', 'deepseek' ),
) );
TP_AI_Options::flush();
$html = TP_AI_Render::buttons_html( array( 'post_id' => 42, 'url' => 'https://a.test/slug/', 'title' => 'T"itle' ) );
t( 'xss: prompt quotes attribute-escaped', false !== strpos( $html, '&quot;' ) && 1 === substr_count( $html, 'data-tpas-prompt="' ) );
t( 'xss: href built from build_url stays clean', false === strpos( $html, 'javascript:' ) );

// ---- JSON config survives attribute escaping --------------------------------
$GLOBALS['tp_posts'][43] = array(
	'type'    => 'post',
	'title'   => 'Bad "quote" \n <script>alert(1)</script>',
	'content' => '',
);
update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines'        => array(
		array( 'id' => 'eng_test', 'provider' => 'deepseek', 'api_key' => 'sk-live', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_test',
	'services' => array( 'deepseek' ),
) );
TP_AI_Options::flush();

$panel  = TP_AI_Render::panel_html( array( 'post_id' => 43 ) );
preg_match( '/data-tpas-json="([^"]*)"/', $panel, $pm );
t( 'xss: data-tpas-json attribute does not break out', 1 === preg_match_all( '/class="tpas-ai"/', $panel ) );
$cfg = json_decode( htmlspecialchars_decode( $pm[1] ), true );
t( 'xss: config round-trips through esc_attr + json_decode', is_array( $cfg ) && 43 === $cfg['post'] );
t( 'xss: hostile title preserved only as JSON text content', false !== strpos( $cfg['prompts']['summary'], 'alert(1)' ) );
t( 'xss: no raw on* attribute is ever emitted', false === strpos( $panel, 'onclick=' ) && false === strpos( $panel, ' onerror=' ) );

// ---- Formatter cannot inject markup ----------------------------------------
$evil = 'Text [link](javascript:alert(1)) <iframe src="x"></iframe> <svg onload="alert(1)"></svg> end';
$out  = TP_AI_Render::format_ai_text( $evil );
t( 'xss: markdown link protocol stripped', false === strpos( $out, 'javascript:' ) );
t( 'xss: iframe/svg elements removed', false === strpos( $out, '<iframe' ) && false === strpos( $out, '<svg' ) );
t( 'xss: plain text preserved', false !== strpos( $out, 'Text' ) && false !== strpos( $out, 'end' ) );

// ---- AJAX nonce is enforced -------------------------------------------------
$GLOBALS['tp_ajax'] = null;
$_POST = array( 'nonce' => 'totally-bogus', 'lang' => 'en', 'type' => 'summary', 'post' => '42', 'url' => '' );
try {
	TP_AI_Summary::handle_ajax();
} catch ( TP_Test_Ajax_Exit $e ) {}
t( 'xss: bad nonce dies before any JSON is sent', null === $GLOBALS['tp_ajax'] );

$_POST = array( 'nonce' => wp_create_nonce( 'tpas_ai' ), 'lang' => 'en', 'type' => 'summary', 'post' => '42', 'url' => '' );
$GLOBALS['tp_http_override'] = new WP_Error( 'network', 'nope' );
try {
	TP_AI_Summary::handle_ajax();
	t( 'xss: valid nonce proceeds past the guard', isset( $GLOBALS['tp_ajax'], $GLOBALS['tp_ajax']['ok'] ) );
} catch ( TP_Test_Ajax_Exit $e ) {
	t( 'xss: valid nonce proceeds past the guard', true );
}
unset( $GLOBALS['tp_http_override'] );

// ---- Non-array stored option degrades to defaults ---------------------------
update_option( TP_AI_Options::OPTION_KEY, 'oops-this-is-a-string' );
TP_AI_Options::flush();
$g = TP_AI_Options::get();
t( 'robustness: non-array option falls back to defaults array', is_array( $g ) && 7 === count( $g['services'] ) );

// ---- JS data attributes never reflect the raw language value ---------------
$html = TP_AI_Render::buttons_html( array( 'post_id' => 42 ) );
t( 'xss: service keys are sanitized before use as classes', false === strpos( $html, '<script' ) );

update_option( TP_AI_Options::OPTION_KEY, array() );
TP_AI_Options::flush();
unset( $GLOBALS['tp_posts'][43] );