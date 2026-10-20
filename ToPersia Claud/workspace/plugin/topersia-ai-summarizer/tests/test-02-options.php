<?php
/**
 * Settings defaults, engine-row endpoint resolution and sanitization.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Options' );

TP_AI_Options::flush();
$defaults = TP_AI_Options::defaults();

$expect_keys = array( 'heading', 'prompt', 'services', 'post_types', 'position', 'style', 'accent',
	'internal_enabled', 'internal_engines', 'internal_default_engine', 'internal_temperature',
	'internal_max_tokens', 'internal_context_chars', 'internal_cache_hours', 'internal_throttle',
	'internal_types', 'internal_default_type', 'panel_position' );
$missing = array();
foreach ( $expect_keys as $k ) {
	if ( ! array_key_exists( $k, $defaults ) ) {
		$missing[] = $k;
	}
}
t( 'defaults contain every required key', empty( $missing ), implode( ',', $missing ) );

/* 1.4.1 · P5-D392: no outside-assistant links — the default list is empty */
t( 'P5-D392: no assistant enabled by default', array() === $defaults['services'] );
t( 'no internal engines configured by default', array() === $defaults['internal_engines'] );

t( 'get() merges stored value over defaults', 'test' === ( function () {
	update_option( TP_AI_Options::OPTION_KEY, array( 'heading' => 'test' ) );
	TP_AI_Options::flush();
	return TP_AI_Options::get()['heading'];
} )() );

t( 'get_field() with missing key returns fallback', 'fb' === TP_AI_Options::get_field( 'nope', 'fb' ) );

// providers() now covers 8 presets across several protocols: deepseek/openai/
// grok/perplexity (openai protocol), claude (anthropic), gemini (gemini),
// azure (azure), custom (openai protocol, admin-supplied URL).
$providers = TP_AI_Options::providers();
t( 'providers() lists 8 presets', 8 === count( $providers ) );
t( 'openai provider speaks the openai protocol', 'openai' === $providers['openai']['protocol'] );
t( 'claude provider speaks its own protocol', 'anthropic' === $providers['claude']['protocol'] );
t( 'gemini provider speaks its own protocol', 'gemini' === $providers['gemini']['protocol'] );
t( 'azure provider speaks the azure protocol', 'azure' === $providers['azure']['protocol'] );

// internal_endpoint() resolves ONE engine row - no more single global provider.
$ep = TP_AI_Options::internal_endpoint( array( 'provider' => 'deepseek' ) );
t( 'endpoint: deepseek base URL', 'https://api.deepseek.com/v1' === $ep['base_url'] );
t( 'endpoint: deepseek default model', 'deepseek-chat' === $ep['model'] );
t( 'endpoint: deepseek protocol', 'openai' === $ep['protocol'] );

$ep_custom = TP_AI_Options::internal_endpoint( array(
	'provider' => 'custom',
	'base_url' => 'https://gateway.example.com/v1/',
	'model'    => 'my-model',
) );
t( 'endpoint: custom URL is untrailingslashed', 'https://gateway.example.com/v1' === $ep_custom['base_url'] );
t( 'endpoint: custom model respected', 'my-model' === $ep_custom['model'] );

t( 'endpoint: unknown provider resolves to nothing (no silent fallback)', '' === TP_AI_Options::internal_endpoint( array( 'provider' => 'bogus' ) )['provider'] );
t( 'endpoint: no engine at all resolves to nothing', '' === TP_AI_Options::internal_endpoint( null )['provider'] );

// engines() / engine_by_id() / default_engine() over a seeded multi-row config.
update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines'        => array(
		array( 'id' => 'eng_a', 'provider' => 'openai', 'api_key' => 'sk-a', 'model' => '', 'base_url' => '' ),
		array( 'id' => 'eng_b', 'provider' => 'claude', 'api_key' => 'sk-b', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_b',
) );
TP_AI_Options::flush();

t( 'engines() returns every configured row', 2 === count( TP_AI_Options::engines() ) );
t( 'engine_by_id() finds a specific row', 'openai' === TP_AI_Options::engine_by_id( 'eng_a' )['provider'] );
t( 'engine_by_id() returns null for an unknown id', null === TP_AI_Options::engine_by_id( 'nope' ) );
t( 'default_engine() honours internal_default_engine', 'eng_b' === TP_AI_Options::default_engine()['id'] );

update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines' => array(
		array( 'id' => 'eng_only', 'provider' => 'openai', 'api_key' => 'sk-a', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => '',
) );
TP_AI_Options::flush();
t( 'default_engine() falls back to the first row when nothing is marked default', 'eng_only' === TP_AI_Options::default_engine()['id'] );

update_option( TP_AI_Options::OPTION_KEY, array() );
TP_AI_Options::flush();
t( 'default_engine() is null with no rows configured', null === TP_AI_Options::default_engine() );

// Sanitization behaves like the Settings API would call it.
$clean = TP_AI_Options::sanitize( array(
	'heading'      => '<b>Heading</b><script>alert(1)</script>',
	'prompt'       => "  Prompt\nwith newlines  ",
	'services'     => array( 'chatgpt', 'deepseek', 'not-a-service' ),
	'post_types'   => array( 'post', 'page', 'evil' ),
	'position'     => 'both',
	'panel_position' => 'top',
	'style'        => 'minimal',
	'accent'       => '#123fff',
	'radius'       => 999,
	'internal_engines' => array(
		array( 'id' => '', 'provider' => 'openai', 'api_key' => ' sk-abc ', 'model' => 'gpt-4o-mini', 'base_url' => '' ),
		array( 'id' => '', 'provider' => 'not-a-provider', 'api_key' => 'sk-ignored' ), // dropped: unknown provider.
	),
	'internal_temperature' => 7,
	'new_tab' => 1,
) );
t( 'sanitize: script tag stripped from heading', false === strpos( $clean['heading'], '<script' ) && false !== strpos( $clean['heading'], '<b>' ) );
t( 'sanitize: prompt trimmed', "Prompt\nwith newlines" === $clean['prompt'] );
t( 'P5-D392: sanitize: any submitted assistant is dropped (the option is gone)', array() === $clean['services'] );
t( 'sanitize: unknown post type dropped', array( 'post', 'page' ) === $clean['post_types'] );
t( 'sanitize: position whitelisted', 'both' === $clean['position'] );
t( 'sanitize: panel position whitelisted', 'top' === $clean['panel_position'] );
t( 'sanitize: accent validated', '#123fff' === $clean['accent'] );
t( 'sanitize: radius clamped to 40', 40 === $clean['radius'] );
t( 'sanitize: row with unknown provider dropped', 1 === count( $clean['internal_engines'] ) );
t( 'sanitize: valid row kept with provider intact', 'openai' === $clean['internal_engines'][0]['provider'] );
t( 'sanitize: key trimmed and stored', 'sk-abc' === $clean['internal_engines'][0]['api_key'] );
t( 'sanitize: a new row gets a real generated id', '' !== $clean['internal_engines'][0]['id'] && 0 !== strpos( $clean['internal_engines'][0]['id'], 'new_' ) );
t( 'sanitize: default engine auto-set to the only row when unset', $clean['internal_engines'][0]['id'] === $clean['internal_default_engine'] );
t( 'sanitize: temperature 7 -> 2.0 (a decimal 0-2, no division; SPRINT-4.4 2.7)', 2.0 === $clean['internal_temperature'] );
t( 'sanitize: invalid hex falls back to default', '#177E7A' === TP_AI_Options::sanitize( array( 'accent' => 'purple' ) )['accent'] );

// A row's API key is kept when that row is resubmitted with an empty key field.
update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines' => array(
		array( 'id' => 'eng_kept', 'provider' => 'openai', 'api_key' => 'secret-kept', 'model' => '', 'base_url' => '' ),
	),
) );
TP_AI_Options::flush();
$clean2 = TP_AI_Options::sanitize( array(
	'internal_engines' => array(
		array( 'id' => 'eng_kept', 'provider' => 'openai', 'api_key' => '', 'model' => '', 'base_url' => '' ),
	),
) );
t( 'sanitize: empty key on an existing row keeps its stored secret', 'secret-kept' === $clean2['internal_engines'][0]['api_key'] );
t( 'sanitize: an existing row keeps its own id (not regenerated)', 'eng_kept' === $clean2['internal_engines'][0]['id'] );

// A brand-new row (client-side "new_" id) always gets a freshly generated id,
// even though it has no matching stored row - new rows never "inherit" a
// stored secret from an unrelated row.
$clean3 = TP_AI_Options::sanitize( array(
	'internal_engines' => array(
		array( 'id' => 'new_abc123', 'provider' => 'gemini', 'api_key' => '', 'model' => '', 'base_url' => '' ),
	),
) );
t( 'sanitize: a "new_" row id is replaced with a real id', 0 !== strpos( $clean3['internal_engines'][0]['id'], 'new_' ) );
t( 'sanitize: a "new_" row with no key submitted stays keyless (nothing to inherit)', '' === $clean3['internal_engines'][0]['api_key'] );

// Reset for other suites.
update_option( TP_AI_Options::OPTION_KEY, array() );
TP_AI_Options::flush();
