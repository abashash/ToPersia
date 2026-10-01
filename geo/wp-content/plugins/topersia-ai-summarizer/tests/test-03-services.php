<?php
/**
 * Assistant registry and outbound URLs.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Services' );

$all = TP_AI_Services::get_all();

t( 'exactly seven assistants registered', 7 === count( $all ) );
foreach ( array( 'chatgpt', 'perplexity', 'claude', 'grok', 'copilot', 'gemini', 'deepseek' ) as $slug ) {
	t( "assistant {$slug} present with label/URL/color", isset( $all[ $slug ]['label'], $all[ $slug ]['url'], $all[ $slug ]['color'] ) );
}

t( 'deepseek points to chat.deepseek.com', false !== strpos( $all['deepseek']['url'], 'chat.deepseek.com' ) );

$url = TP_AI_Services::build_url( 'deepseek', 'Hello world' );
t( 'build_url: prompt is URL-encoded once', false !== strpos( $url, 'Hello%20world' ) && false === strpos( $url, ' ' ) );
t( 'build_url: unknown slug returns empty', '' === TP_AI_Services::build_url( 'nope', 'x' ) );

$icon = TP_AI_Services::get_icon( 'deepseek' );
t( 'get_icon: brand set returns svg', false !== strpos( $icon, '<svg' ) );
$neutral = TP_AI_Services::get_icon( 'chatgpt', 'neutral' );
t( 'get_icon: forced neutral set works', false !== strpos( $neutral, '<svg' ) );
t( 'get_icon: brand and neutral differ', $icon !== TP_AI_Services::get_icon( 'deepseek', 'brand' ) );

$allowed = TP_AI_Services::icon_allowed_html();
t( 'icon whitelist covers svg + path + circle', isset( $allowed['svg'], $allowed['path'], $allowed['circle'] ) );