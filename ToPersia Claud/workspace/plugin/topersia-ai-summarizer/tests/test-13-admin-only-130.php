<?php
/**
 * 1.3.0 — P5-D358: the AI summary is made by the site admin only.
 *
 * Each claim runs against a mutant — the same class with the 1.3.0 rule taken
 * out — and must fail there. Loaded after test-12 (its core-cache and cap
 * stand-ins are in place).
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Admin-only generation / 1.3.0' );

/** Load a copy of a plugin class with one textual mutation, under a new name. */
function tp130_mutant( $file, $class, $suffix, $from, $to ) {
	$src = (string) file_get_contents( TP_AI_PATH . 'includes/' . $file );
	if ( false === strpos( $src, $from ) ) {
		return '';
	}
	$name = $class . '_' . $suffix;
	$src  = str_replace( array( 'class ' . $class . ' ', $from ), array( 'class ' . $name . ' ', $to ), $src );
	eval( '?>' . $src );
	return $name;
}

/** Engine traffic since the last reset: outbound HTTP calls + core-cache rebuilds + replies. */
function tp130_reset() {
	$GLOBALS['tp_http_calls'] = 0;
	$GLOBALS['tp130_replies'] = 0;
	foreach ( array_keys( (array) ( $GLOBALS['tp_transients'] ?? array() ) ) as $k ) {
		if ( 0 === strpos( $k, 'tpas_rt_' ) ) {
			delete_transient( $k );
		}
	}
}
add_action( 'tpas_reply', function () {
	$GLOBALS['tp130_replies']++;
} );

/* 47: the one id the central published-gate stand-in (test-09) lets through */
$GLOBALS['tp_posts'][47] = array(
	'type'    => 'post',
	'title'   => 'Kashan',
	'content' => 'Kashan is a city in Isfahan province, known for its historic houses, the Fin Garden and rosewater. Its population is about 300,000.',
);

update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines'        => array(
		array( 'id' => 'eng_130', 'provider' => 'deepseek', 'api_key' => 'sk-130', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_130',
	'internal_engine'         => 'ai',
) );
TP_AI_Options::flush();
$GLOBALS['tp_http_override'] = array( 'code' => 200, 'body' => json_encode( array( 'choices' => array( array( 'message' => array( 'content' => 'Kashan summary.' ), 'finish_reason' => 'stop' ) ) ) ) );

/* ── بند ۱ — tpas_ai_generate: only an editor of the post (and the nonce) ── */

t( 'P5-D358: visitor requests are off by default', 0 === TP_AI_Options::defaults()['visitor_requests'] && ! TP_AI_Summary::visitor_requests() );

$hooks = (array) ( $GLOBALS['tp_hooks']['wp_ajax_tpas_ai_generate'] ?? array() );
TP_AI_Summary::init();
$hooks = (array) $GLOBALS['tp_hooks']['wp_ajax_tpas_ai_generate'];
t( 'P5-D358: the editor-only rule is hooked on tpas_ai_generate (priority 0, before the cap gate)', in_array( array( 'TP_AI_Summary', 'authorize' ), $hooks, true ) );
t( 'P5-D358: still no wp_ajax_nopriv_tpas_ai_generate', empty( $GLOBALS['tp_hooks']['wp_ajax_nopriv_tpas_ai_generate'] ) );

/** Who: guest | subscriber | other (editor of another post) | editor. Returns [reply, engine traffic]. */
$tp130_as = function ( $class, $who, $nonce = true ) {
	tp130_reset();
	$GLOBALS['tp_logged_in'] = 'guest' !== $who;
	$GLOBALS['tp_cannot']    = in_array( $who, array( 'guest', 'subscriber', 'other' ), true ) ? array( 'edit_post' => true ) : array();
	$post = array( 'lang' => 'en', 'type' => 'keypoints', 'post' => '47' );
	if ( ! $nonce ) {
		$post['nonce'] = 'bogus';
	}
	$r = tp_ajax( array( $class, 'handle_ajax' ), $post );
	$GLOBALS['tp_logged_in'] = true;
	$GLOBALS['tp_cannot']    = array();
	return array( $r, $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] );
};
$tp130_refused = function ( $class, $who, $nonce = true ) use ( $tp130_as ) {
	list( $r, $traffic ) = $tp130_as( $class, $who, $nonce );
	return is_array( $r ) && false === $r['ok'] && 403 === $r['status'] && 0 === $traffic;
};

$m1 = tp130_mutant( 'class-tp-ai-summary.php', 'TP_AI_Summary', 'M1', "|| ! current_user_can( 'edit_post', \$post ) ) {", ') {' );
t( 'P5-D358: guest -> 403 and zero engine calls', $tp130_refused( 'TP_AI_Summary', 'guest' ) );
t( 'P5-D358: subscriber (logged in, cannot edit the post) -> 403 and zero engine calls; mutant without edit_post generates',
	$tp130_refused( 'TP_AI_Summary', 'subscriber' ) && '' !== $m1 && ! $tp130_refused( $m1, 'subscriber' ) );
t( 'P5-D358: editor of the post with a bad nonce -> 403 and zero engine calls', $tp130_refused( 'TP_AI_Summary', 'editor', false ) );
list( $tp130_r, $tp130_t ) = $tp130_as( 'TP_AI_Summary', 'editor' );
t( 'P5-D358: editor of the post -> generated (one engine reply)', is_array( $tp130_r ) && true === $tp130_r['ok'] && $tp130_t >= 1 );

/* the option «visitor requests (later versions)» restores the 1.2.x logged-in path — and only it */
update_option( TP_AI_Options::OPTION_KEY, array_merge( get_option( TP_AI_Options::OPTION_KEY ), array( 'visitor_requests' => 1 ) ) );
TP_AI_Options::flush();
list( $tp130_r ) = $tp130_as( 'TP_AI_Summary', 'subscriber' );
list( $tp130_g ) = $tp130_as( 'TP_AI_Summary', 'guest' );
t( 'P5-D358: option on -> a logged-in visitor may ask again; a guest still may not (401)', is_array( $tp130_r ) && true === $tp130_r['ok'] && is_array( $tp130_g ) && 401 === $tp130_g['status'] );
update_option( TP_AI_Options::OPTION_KEY, array_merge( get_option( TP_AI_Options::OPTION_KEY ), array( 'visitor_requests' => 0 ) ) );
TP_AI_Options::flush();
