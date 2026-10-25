<?php
/**
 * 1.5.0 — length bounds of the public outputs (ToPersia batch 26 · F-01).
 *
 * Summary 250-450 characters, key points 4-6 items of 40-140, plan 200-400;
 * the prompt states the bound; an answer outside it is asked for once more;
 * still outside -> stored with a review flag and tpas_get_summary() says
 * review = true. Each claim has a mutant that must fail. Runs after test-14
 * (post 47, engine 'ai', HTTP stub).
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Length bounds / 1.5.0' );

$b150 = function ( $n, $c = 'ا' ) { return str_repeat( $c, $n ); };
$kp   = function ( $items, $len ) use ( $b150 ) { return implode( "\n", array_map( function () use ( $b150, $len ) { return '- ' . $b150( $len ); }, range( 1, $items ) ) ); };

t( 'bounds: summary 300 ok · 249 and 451 out', TP_AI_Bounds::check( 'summary', $b150( 300 ) )['ok'] && ! TP_AI_Bounds::check( 'summary', $b150( 249 ) )['ok'] && ! TP_AI_Bounds::check( 'summary', $b150( 451 ) )['ok'] );
t( 'bounds: key points 5 x 60 ok · 3 items out · 7 items out · one item of 39 out',
	TP_AI_Bounds::check( 'keypoints', $kp( 5, 60 ) )['ok'] && ! TP_AI_Bounds::check( 'keypoints', $kp( 3, 60 ) )['ok']
	&& ! TP_AI_Bounds::check( 'keypoints', $kp( 7, 60 ) )['ok'] && ! TP_AI_Bounds::check( 'keypoints', $kp( 4, 60 ) . "\n- " . $b150( 39 ) )['ok'] );
t( 'bounds: plan 200 ok · 401 out', TP_AI_Bounds::check( 'plan', $b150( 200 ) )['ok'] && ! TP_AI_Bounds::check( 'plan', $b150( 401 ) )['ok'] );
t( 'bounds: counted on plain text — tags, "**" and list markers do not count',
	300 === TP_AI_Bounds::chars( '<p>**' . $b150( 300 ) . '**</p>' ) && array( 60, 60, 60, 60 ) === TP_AI_Bounds::check( 'keypoints', str_replace( '- ', '• ', $kp( 4, 60 ) ) )['items'] );

/*
 * The test core cache never runs its callback (test-10), so the engine path is
 * measured on a copy of the class without the core cache; a mutant copy drops
 * the second request.
 */
function tp150_class( $suffix, array $swap ) {
	$src  = (string) file_get_contents( TP_AI_PATH . 'includes/class-tp-ai-summary.php' );
	$swap = array_merge( array(
		'class TP_AI_Summary ' => 'class TP_AI_Summary_' . $suffix . ' ',
		"class_exists( 'TP_AI_Core_Bridge' ) && TP_AI_Core_Bridge::cache_available()" => 'false',
	), $swap );
	foreach ( $swap as $from => $to ) {
		if ( false === strpos( $src, $from ) ) {
			return '';
		}
		$src = str_replace( $from, $to, $src );
	}
	eval( '?>' . $src );
	return 'TP_AI_Summary_' . $suffix;
}
$S150  = tp150_class( 'D', array() );
$M150  = tp150_class( 'B1', array( '$again = TP_AI_API::chat( $system, $user . ' => '$again = array( \'ok\' => false ); ( $system . $user . ' ) );

$GLOBALS['tp123_used'] = 0;
update_option( TP_AI_Options::OPTION_KEY, array_merge( (array) get_option( TP_AI_Options::OPTION_KEY ), array( 'internal_engine' => 'ai', 'admin_daily_cap' => 50 ) ) );
TP_AI_Options::flush();
$lang150 = TP_AI_Generator::post_lang( 47 );
$body    = function ( $text ) { return json_encode( array( 'choices' => array( array( 'message' => array( 'content' => $text ), 'finish_reason' => 'stop' ) ) ) ); };
$clean   = function () use ( $lang150 ) {
	delete_post_meta( 47, '_tpas_ai_summary_' . $lang150 );
	delete_post_meta( 47, TP_AI_Bounds::REVIEW . 'summary_' . $lang150 );
	tp130_reset();
};

/* short answer -> asked once more (two calls) -> still short -> stored and flagged; the prompt carries the bound */
$clean();
$GLOBALS['tp_http_override'] = array( 'code' => 200, 'body' => $body( 'Kashan text.' ) );
$first  = '' !== $S150 ? call_user_func( array( $S150, 'produce' ), 47, 'summary', $lang150 ) : array();
$sent   = (string) ( $GLOBALS['tp_http_last']['args']['body'] ?? '' );
$calls1 = $GLOBALS['tp_http_calls'];
$short  = tpas_get_summary( 47, 'summary', $lang150 );
t( 'engine: answer outside the bounds -> asked exactly once more, with the miss stated', 2 === $calls1 && false !== strpos( $sent, 'previous answer was outside' ), 'calls ' . $calls1 . ' ' . json_encode( $first ) );
t( 'engine: still outside -> stored, flagged, tpas_get_summary review = true',
	is_array( $short ) && true === $short['review'] && '' !== (string) wp_json_encode( get_post_meta( 47, TP_AI_Bounds::REVIEW . 'summary_' . $lang150, true ) ) && ! empty( get_post_meta( 47, TP_AI_Bounds::REVIEW . 'summary_' . $lang150, true ) ), json_encode( $short ) );
t( 'engine: the prompt states the bound (250 to 450 characters)', false !== strpos( $sent, '250 to 450 characters' ) );

/* inside the bounds -> one call, no flag */
tp130_reset();
$GLOBALS['tp_http_override'] = array( 'code' => 200, 'body' => $body( $b150( 300 ) ) );
call_user_func( array( $S150, 'produce' ), 47, 'summary', $lang150 );
$fine = tpas_get_summary( 47, 'summary', $lang150 );
t( 'engine: answer inside the bounds -> one call, flag cleared, review = false',
	1 === $GLOBALS['tp_http_calls'] && is_array( $fine ) && false === $fine['review'] && 300 === $fine['chars'] && empty( get_post_meta( 47, TP_AI_Bounds::REVIEW . 'summary_' . $lang150, true ) ), json_encode( $fine ) );

/* mutant without the second request: one call only (the first engine claim would fail) */
$clean();
$GLOBALS['tp_http_override'] = array( 'code' => 200, 'body' => $body( 'Kashan text.' ) );
if ( '' !== $M150 ) {
	call_user_func( array( $M150, 'produce' ), 47, 'summary', $lang150 );
}
t( 'mutant: without the second request -> one call only', '' !== $M150 && 1 === $GLOBALS['tp_http_calls'] );

/* stored before 1.5.0: no flag, but outside the bounds now -> still not shown */
delete_post_meta( 47, TP_AI_Bounds::REVIEW . 'summary_' . $lang150 );
$legacy = tpas_get_summary( 47, 'summary', $lang150 );
t( 'stored before 1.5.0 (no flag) but outside the bounds now -> review = true', is_array( $legacy ) && true === $legacy['review'] );

unset( $GLOBALS['tp_http_override'] );
$clean();
