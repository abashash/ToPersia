<?php
/**
 * Anonymous click tracking.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Tracker' );

/* 1.4.1 · P5-D392: there are no assistant links, so no click is a valid one — every slug is refused, nothing counted */
foreach ( array( 'deepseek', 'chatgpt', 'bogus-service' ) as $tp392_s ) {
	$res = tp_ajax( array( 'TP_AI_Tracker', 'handle' ), array( 'post' => '42', 'service' => $tp392_s ) );
	t( "P5-D392: tracker refuses a click for «{$tp392_s}»", is_array( $res ) && false === $res['ok'] );
}
t( 'P5-D392: tracker counted nothing', array() === TP_AI_Tracker::post_stats( 42 ) && 0 === (int) ( TP_AI_Tracker::totals()['total'] ?? 0 ) );

/* stored clicks from before 1.4.1 are still read and purged */
update_post_meta( 42, '_tpas_clicks', array( 'deepseek' => 2 ) );
t( 'tracker: post_stats reads the meta', 2 === (int) TP_AI_Tracker::post_stats( 42 )['deepseek'] );
TP_AI_Tracker::purge_post( 42 );
t( 'tracker: purge clears per-post clicks', array() === TP_AI_Tracker::post_stats( 42 ) );