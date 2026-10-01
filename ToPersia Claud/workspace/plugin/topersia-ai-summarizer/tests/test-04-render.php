<?php
/**
 * Buttons / panel markup, prompts and formatting.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Render' );

TP_AI_Options::flush();
$GLOBALS['tp_current'] = 42;

$buttons = TP_AI_Render::buttons_html( array( 'post_id' => 42 ) );

t( 'buttons: renders for the seeded post', '' !== $buttons );
t( 'buttons: contains chatgpt link', false !== strpos( $buttons, 'chatgpt.com' ) );
t( 'buttons: contains deepseek link', false !== strpos( $buttons, 'chat.deepseek.com' ) );
t( 'buttons: seven assistant anchors', 7 === substr_count( $buttons, 'class="tpas__btn tpas__btn--' ) );
t( 'buttons: prompt carries the current URL', false !== strpos( $buttons, rawurlencode( 'https://example.test/permalink-42/' ) ) );
t( 'buttons: rel contains noopener+nofollow', 1 === preg_match( '/rel="noopener nofollow noreferrer"/', $buttons ) );
t( 'buttons: heading id matches aria-labelledby', 1 === preg_match( '/id="tpas-heading-\d+"[^>]*aria-labelledby="tpas-heading-\d+"/', $buttons ) || false !== strpos( $buttons, 'aria-labelledby' ) );
t( 'buttons: copy button included by default', false !== strpos( $buttons, 'tpas__copy' ) );

$sel = TP_AI_Render::buttons_html( array( 'post_id' => 42, 'services' => array( 'chatgpt' ), 'copy_button' => 0 ) );
t( 'buttons: service filter works', 1 === substr_count( $sel, 'tpas__btn--' ) && false === strpos( $sel, 'tpas__copy' ) );

t( 'buttons: unknown service silently skipped', false === strpos( TP_AI_Render::buttons_html( array( 'post_id' => 42, 'services' => array( 'bogus' ) ) ), 'tpas__btn--' ) );

// Empty services returns nothing.
t( 'buttons: empty list renders nothing', '' === TP_AI_Render::buttons_html( array( 'post_id' => 42, 'services' => array() ) ) );

$lang = TP_AI_Localize::for_post( 42 );
$panel = TP_AI_Render::panel_html( array( 'post_id' => 42 ) );

t( 'panel: renders', '' !== $panel );
t( 'panel: embeds data-tpas-json', false !== strpos( $panel, 'data-tpas-json=' ) );
preg_match( '/data-tpas-json="([^"]*)"/', $panel, $m );
$cfg = json_decode( htmlspecialchars_decode( $m[1] ), true );
t( 'panel: config parses as JSON', is_array( $cfg ) );
t( 'panel: config has post + lang', 42 === $cfg['post'] && $lang === $cfg['lang'] );
t( 'panel: config has the three prompts', isset( $cfg['prompts']['summary'], $cfg['prompts']['keypoints'], $cfg['prompts']['plan'] ) );
t( 'panel: config keeps placeholders replaced', false !== strpos( $cfg['prompts']['summary'], 'permalink-42' ) );
t( 'panel: config exposes engine links', ! empty( $cfg['links']['summary']['deepseek'] ) );
t( 'panel: copy/regenerate/loading labels localized', isset( $cfg['msg']['copied'], $cfg['msg']['loading'], $cfg['msg']['error'] ) && ! empty( $cfg['msg']['error'] ) );
t( 'panel: default type is summary', 'summary' === $cfg['default'] );
t( 'panel: disabled engine keeps key flag off', isset( $cfg['key'] ) );
t( 'panel: no key -> state is nokey with a code block', false !== strpos( $panel, 'data-tpas-state="nokey"' ) && false !== strpos( $panel, 'tpas-ai__prompt' ) );

// Once configured, the panel shows the generate CTA instead.
update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines'        => array(
		array( 'id' => 'eng_test', 'provider' => 'deepseek', 'api_key' => 'sk-live', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_test',
) );
TP_AI_Options::flush();
$panel_ready = TP_AI_Render::panel_html( array( 'post_id' => 42 ) );
t( 'panel: configured -> generate CTA', false !== strpos( $panel_ready, 'data-tpas-state="generate"' ) );
t( 'panel: configured -> key flag on', 1 === ( ( preg_match( '/"key":(\d+)/', $panel_ready, $km ) ) ? (int) $km[1] : -1 ) );

// Auto-first only fires when configured.
update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_auto_first'     => 1,
	'internal_engines'        => array(
		array( 'id' => 'eng_test', 'provider' => 'deepseek', 'api_key' => 'sk-live', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_test',
) );
TP_AI_Options::flush();
preg_match( '/"auto":(\d+)/', TP_AI_Render::panel_html( array( 'post_id' => 42 ) ), $am );
t( 'panel: auto flag on when configured', isset( $am[1] ) && '1' === $am[1] );

// Prompt building.
$prompt = TP_AI_Render::build_prompt( 'X {url} Y {title} Z {site}', 'https://u/', 'T', 1, 'en' );
t( 'build_prompt: replaces every placeholder', 'X https://u/ Y T Z ToPersia' === $prompt );

// Formatting.
$raw = "Overview\n\n**Key:**\n- Point one\n- Point two\n1) Last\n<script>alert(1)</script>end";
$fmt = TP_AI_Render::format_ai_text( $raw );
t( 'format: strips raw HTML', false === strpos( $fmt, '<script>' ) );
t( 'format: bold markdown becomes strong', false !== strpos( $fmt, '<strong>Key:</strong>' ) );
t( 'format: bullets become ul/li', false !== strpos( $fmt, '<ul><li>Point one</li><li>Point two</li>' ) );

$plain = TP_AI_Render::format_ai_text( "**Bold** and `code`" );
t( 'format: inline code becomes strong', false !== strpos( $plain, '<strong>Bold</strong>' ) && false !== strpos( $plain, '<strong>code</strong>' ) );

// Reset config for other suites.
update_option( TP_AI_Options::OPTION_KEY, array() );
TP_AI_Options::flush();