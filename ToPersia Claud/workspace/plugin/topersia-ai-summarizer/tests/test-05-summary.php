<?php
/**
 * On-page generation: caching, rate limiting and the API client.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Summary / API' );

update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines'        => array(
		array( 'id' => 'eng_test', 'provider' => 'deepseek', 'api_key' => 'sk-live', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_test',
) );
TP_AI_Options::flush();
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// ---- Cached response is served without touching the API -------------------
update_post_meta( 42, '_tpas_ai_summary_en', array(
	'text'     => 'Cached summary text.',
	'provider' => 'DeepSeek',
	'model'    => 'deepseek-chat',
	'created'  => time(),
) );
$GLOBALS['tp_http_override'] = array( 'code' => 500, 'body' => 'should-not-be-called' );

$res = tp_ajax( array( 'TP_AI_Summary', 'handle_ajax' ), array(
	'lang' => 'en',
	'type' => 'summary',
	'post' => '42',
	'url'  => '',
) );
t( 'ajax: cached response succeeds', is_array( $res ) && true === $res['ok'] );
t( 'ajax: cached flags cached=true with text', is_array( $res ) && true === $res['data']['cached'] && 'Cached summary text.' === $res['data']['text'] );
t( 'ajax: the browser reply carries no provider or model (1.2.3 · P5-D356)', is_array( $res ) && ! array_key_exists( 'provider', $res['data'] ) && ! array_key_exists( 'model', $res['data'] ) );

unset( $GLOBALS['tp_http_override'] );

// ---- Fresh generation hits the API and writes the cache -------------------
update_post_meta( 42, '_tpas_ai_summary_en', 'old-not-array' );

$GLOBALS['tp_http_override'] = array( 'code' => 200, 'body' => json_encode( array(
	'choices' => array(
		array( 'message' => array( 'content' => 'Fresh summary.' ), 'finish_reason' => 'stop' ),
	),
) ) );
$res = tp_ajax( array( 'TP_AI_Summary', 'handle_ajax' ), array(
	'lang' => 'en',
	'type' => 'summary',
	'post' => '42',
	'url'  => '',
) );
t( 'ajax: fresh generation succeeds', is_array( $res ) && true === $res['ok'] );
t( 'ajax: fresh returned text', is_array( $res ) && 'Fresh summary.' === $res['data']['text'] );
t( 'ajax: non-array cache replaced on write', false !== strpos( (string) serialize( get_post_meta( 42, '_tpas_ai_summary_en', true ) ), 'Fresh summary.' ) );

// ---- Cache expiry -----------------------------------------------------------
$key = '_tpas_ai_summary_en';
/* 1.2.1: one hour past the configured cache hours (the default went 168 → 720) */
update_post_meta( 42, $key, array( 'text' => 'Old', 'created' => time() - ( (int) TP_AI_Options::get_field( 'internal_cache_hours', 720 ) + 1 ) * 3600 ) );
t( 'cache: expired entry returns null', null === TP_AI_Summary::get_cached( 42, 'summary', 'en', '' ) );
t( 'cache: expired entry is purged', '' === get_post_meta( 42, $key, true ) );

$fresh = array( 'text' => 'Fresh', 'created' => time() );
update_post_meta( 42, $key, $fresh );
t( 'cache: valid entry returned', $fresh === TP_AI_Summary::get_cached( 42, 'summary', 'en', '' ) );
t( 'cache: post id 0 never caches', null === TP_AI_Summary::get_cached( 0, 'summary', 'en', '' ) );

// ---- API error handling ------------------------------------------------------
$GLOBALS['tp_http_override'] = array( 'code' => 401, 'body' => json_encode( array(
	'error' => array( 'message' => 'Invalid API key' ),
) ) );
$res = tp_ajax( array( 'TP_AI_Summary', 'handle_ajax' ), array(
	'lang' => 'en',
	'type' => 'keypoints',
	'post' => '42',
	'url'  => '',
) );
t( 'ajax: HTTP 401 surfaces the provider message', is_array( $res ) && false === $res['ok'] && false !== strpos( $res['data']['message'], 'Invalid API key' ) );

$GLOBALS['tp_http_override'] = new WP_Error( 'boom', 'Connection refused' );
/* 1.2.2: was `summary`, which the cache claims above already filled - a served
   cache never reaches the network. `plan` has no cache, so the error surfaces. */
$res = tp_ajax( array( 'TP_AI_Summary', 'handle_ajax' ), array(
	'lang' => 'en',
	'type' => 'plan',
	'post' => '42',
	'url'  => '',
) );
t( 'ajax: network error surfaced', is_array( $res ) && false === $res['ok'] && false !== strpos( $res['data']['message'], 'Connection refused' ) );
unset( $GLOBALS['tp_http_override'] );

// ---- Rate limiting ------------------------------------------------------------
/* 1.2.2: the 1.0 key `tpas_rt_{md5(ip)}` no longer exists — since 1.1 the primary
   layer is per user and type (`tpas_rt_u_{user}_{type}`), the secondary per IP
   (`tpas_rt_ip_{md5(ip)}`, five times the limit). Same claim, current keys. */
set_transient( 'tpas_rt_u_1_summary', 10, 3600 );
$res = tp_ajax( array( 'TP_AI_Summary', 'handle_ajax' ), array(
	'lang' => 'en',
	'type' => 'summary',
	'post' => '42',
	'url'  => '',
) );
t( 'ajax: rate limited after threshold', is_array( $res ) && false === $res['ok'] && false !== strpos( $res['data']['message'], 'Rate limit' ) );
delete_transient( 'tpas_rt_u_1_summary' );

set_transient( 'tpas_rt_ip_' . md5( '127.0.0.1' ), 50, 3600 );
$res = tp_ajax( array( 'TP_AI_Summary', 'handle_ajax' ), array( 'lang' => 'en', 'type' => 'summary', 'post' => '42' ) );
t( 'ajax: secondary IP layer limits at five times the user limit', is_array( $res ) && false === $res['ok'] && false !== strpos( $res['data']['message'], 'Rate limit' ) );
delete_transient( 'tpas_rt_ip_' . md5( '127.0.0.1' ) );

$res = tp_ajax( array( 'TP_AI_Summary', 'handle_ajax' ), array(
	'lang' => 'en',
	'type' => 'keypoints',
	'post' => '42',
	'url'  => '',
) );
t( 'ajax: transient counting path works (1 -> 2)', is_array( $res ) && true === $res['ok'] );

// ---- Missing target ------------------------------------------------------------
$res = tp_ajax( array( 'TP_AI_Summary', 'handle_ajax' ), array(
	'lang' => 'en',
	'type' => 'summary',
	'post' => '0',
	'url'  => '',
) );
t( 'ajax: no post/url is rejected', is_array( $res ) && false === $res['ok'] );

// ---- sanitizers ---------------------------------------------------------------
$refl = new ReflectionClass( 'TP_AI_Summary' );
$m    = $refl->getMethod( 'sanitize_lang' );
$m->setAccessible( true );
$m2   = $refl->getMethod( 'sanitize_type' );
$m2->setAccessible( true );

t( 'summary: valid lang kept', 'fr' === $m->invoke( null, 'fr' ) );
t( 'summary: junk lang falls back to site locale', 'en' === $m->invoke( null, 'fr!_Bogus' ) );
t( 'summary: valid type kept', 'keypoints' === $m2->invoke( null, 'keypoints' ) );
t( 'summary: junk type falls back to summary', 'summary' === $m2->invoke( null, 'evil;drop' ) );

// ---- endpoint wiring --------------------------------------------------------------
$url = TP_AI_Options::internal_endpoint( TP_AI_Options::default_engine() );
t( 'api: endpoint uses deepseek defaults', 'https://api.deepseek.com/v1' === $url['base_url'] && 'deepseek-chat' === $url['model'] );

update_option( TP_AI_Options::OPTION_KEY, array() );
TP_AI_Options::flush();