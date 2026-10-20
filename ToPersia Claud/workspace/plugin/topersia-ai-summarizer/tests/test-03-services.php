<?php
/**
 * Assistant registry — 1.4.1 · P5-D392: empty. No outside-assistant link (ChatGPT, Claude, Gemini,
 * Copilot, Perplexity, Grok, DeepSeek, …) may reach public output; the `tpas_services` filter is gone
 * so no other plugin can add them back.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Services' );

t( 'P5-D392: no assistant registered', array() === TP_AI_Services::get_all() );

add_filter( 'tpas_services', function ( $s ) {
	$s['chatgpt'] = array( 'label' => 'ChatGPT', 'url' => 'https://chatgpt.com/?q=%s', 'color' => '#000', 'prefill' => true );
	return $s;
} );
t( 'P5-D392: the tpas_services filter cannot add an assistant back', array() === TP_AI_Services::get_all() );

t( 'build_url: unknown slug returns empty', '' === TP_AI_Services::build_url( 'nope', 'x' ) );
t( 'P5-D392: build_url for a former assistant returns empty', '' === TP_AI_Services::build_url( 'chatgpt', 'x' ) && '' === TP_AI_Services::build_url( 'deepseek', 'x' ) );

$allowed = TP_AI_Services::icon_allowed_html();
t( 'icon whitelist covers svg + path + circle', isset( $allowed['svg'], $allowed['path'], $allowed['circle'] ) );
