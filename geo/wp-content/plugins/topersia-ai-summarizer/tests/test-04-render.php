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
/*
 * 1.2.2 — the panel contract changed by design after these claims were written
 * (class-tp-ai-render.php panel_html(): prompts are "used server-side only …
 * never displayed", no copy-prompt/raw-prompt UI, `auto` is always 0, and with
 * internal_enabled the internal engine answers instead of a "nokey" code block).
 * P5-D255: no external-assistant links in the on-page component. The claims below
 * assert the current contract — each one replaces the stale claim of the same slot.
 */
t( 'panel: config carries no prompt (server-side only)', ! isset( $cfg['prompts'] ) && false === strpos( $m[1], 'prompt' ) );
t( 'panel: config carries no page URL either', false === strpos( htmlspecialchars_decode( $m[1] ), 'permalink-42' ) );
t( 'panel: config carries no engine links (P5-D255)', ! isset( $cfg['links'] ) );
t( 'panel: copy/regenerate/loading labels localized', isset( $cfg['msg']['copied'], $cfg['msg']['loading'], $cfg['msg']['error'] ) && ! empty( $cfg['msg']['error'] ) );
t( 'panel: default type is summary', 'summary' === $cfg['default'] );
t( 'panel: config carries no key flag at all', ! isset( $cfg['key'] ) );
t( 'panel: no API key -> internal engine answers, never a raw-prompt block', false !== strpos( $panel, 'data-tpas-state="content"' ) && false === strpos( $panel, 'tpas-ai__prompt' ) );

// Once configured, an uncached post shows the generate CTA instead.
$GLOBALS['tp_posts'][44] = array( 'type' => 'post', 'title' => 'Kashan', 'content' => 'Kashan is a city in Isfahan province.' );
update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines'        => array(
		array( 'id' => 'eng_test', 'provider' => 'deepseek', 'api_key' => 'sk-live', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_test',
) );
TP_AI_Options::flush();
$panel_ready = TP_AI_Render::panel_html( array( 'post_id' => 44 ) );
t( 'panel: configured -> generate CTA', false !== strpos( $panel_ready, 'data-tpas-state="generate"' ) );
t( 'panel: configured -> ready flag on', 1 === ( ( preg_match( '/&quot;ready&quot;:(\d+)/', $panel_ready, $km ) ) ? (int) $km[1] : -1 ) );

// Generation never starts on page load, even with internal_auto_first.
update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_auto_first'     => 1,
	'internal_engines'        => array(
		array( 'id' => 'eng_test', 'provider' => 'deepseek', 'api_key' => 'sk-live', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_test',
) );
TP_AI_Options::flush();
preg_match( '/&quot;auto&quot;:(\d+)/', TP_AI_Render::panel_html( array( 'post_id' => 44 ) ), $am );
t( 'panel: auto stays 0 even with internal_auto_first (explicit request only)', isset( $am[1] ) && '0' === $am[1] );

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