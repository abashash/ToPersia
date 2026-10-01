<?php
/**
 * Anonymous click tracking.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Tracker' );

$res = tp_ajax( array( 'TP_AI_Tracker', 'handle' ), array(
	'post'    => '42',
	'service' => 'deepseek',
) );
t( 'tracker: click accepted', is_array( $res ) && true === $res['ok'] );
t( 'tracker: post meta incremented', 1 === (int) get_post_meta( 42, '_tpas_clicks', true )['deepseek'] );

$res = tp_ajax( array( 'TP_AI_Tracker', 'handle' ), array(
	'post'    => '42',
	'service' => 'deepseek',
) );
t( 'tracker: second click bumps the count', 2 === (int) get_post_meta( 42, '_tpas_clicks', true )['deepseek'] );

$stats = TP_AI_Tracker::totals();
t( 'tracker: global totals recorded', 2 === (int) $stats['total'] && 2 === (int) $stats['deepseek'] );

$res = tp_ajax( array( 'TP_AI_Tracker', 'handle' ), array(
	'post'    => '42',
	'service' => 'bogus-service',
) );
t( 'tracker: unknown service rejected', is_array( $res ) && false === $res['ok'] );

t( 'tracker: post_stats reads the meta', 2 === (int) TP_AI_Tracker::post_stats( 42 )['deepseek'] );

TP_AI_Tracker::purge_post( 42 );
t( 'tracker: purge clears per-post clicks', array() === TP_AI_Tracker::post_stats( 42 ) );