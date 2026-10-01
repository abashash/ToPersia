<?php
/**
 * Front-end injection, shortcodes and blocks.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Frontend / Shortcodes / Blocks' );

update_option( TP_AI_Options::OPTION_KEY, array(
	'position'  => 'after',
	'panel_position' => 'bottom',
	'internal_enabled' => 1,
) );
TP_AI_Options::flush();
$GLOBALS['tp_current'] = 42;
$GLOBALS['tp_loop'] = true;
$GLOBALS['tp_main_query'] = true;
$GLOBALS['tp_is_singular'] = true;

TP_AI_Frontend::init();

t( 'frontend: content hook registered', ! empty( $GLOBALS['tp_hooks']['the_content'] ) );
t( 'frontend: thumbnail hook registered', ! empty( $GLOBALS['tp_hooks']['post_thumbnail_html'] ) );
t( 'frontend: asset hook registered', ! empty( $GLOBALS['tp_hooks']['wp_enqueue_scripts'] ) );

$content = "Body text.";
$out = TP_AI_Frontend::maybe_inject( $content );

t( 'frontend: after appends both components', strpos( $out, 'Body text.' ) < strpos( $out, 'tpas__buttons' ) && strpos( $out, 'tpas__buttons' ) < strpos( $out, 'tpas-ai' ) );
t( 'frontend: content preserved', false !== strpos( $out, 'Body text.' ) );

update_post_meta( 42, '_tpas_hide_buttons', 1 );
$out = TP_AI_Frontend::maybe_inject( $content );
t( 'frontend: hide_buttons drops the buttons group', false === strpos( $out, 'tpas__buttons' ) );

update_post_meta( 42, '_tpas_hide_panel', 1 );
$out = TP_AI_Frontend::maybe_inject( $content );
t( 'frontend: hide_panel drops the panel', false === strpos( $out, 'tpas-ai__title' ) );

$out = TP_AI_Frontend::maybe_inject( $content );
t( 'frontend: both hidden -> content untouched', $content === $out );

delete_post_meta( 42, '_tpas_hide_buttons' );
delete_post_meta( 42, '_tpas_hide_panel' );

update_option( TP_AI_Options::OPTION_KEY, array( 'position' => 'before', 'panel_position' => 'manual' ) );
TP_AI_Options::flush();
$out = TP_AI_Frontend::maybe_inject( $content );
t( 'frontend: before prepends buttons', 0 === strpos( ltrim( $out ), '<div class="tpas' ) && false !== strpos( $out, 'Body text.' ) );
t( 'frontend: before keeps panel manual', false === strpos( $out, 'tpas-ai' ) );

update_option( TP_AI_Options::OPTION_KEY, array( 'position' => 'both', 'panel_position' => 'manual' ) );
TP_AI_Options::flush();
$out = TP_AI_Frontend::maybe_inject( $content );
t( 'frontend: both shows the buttons twice', 2 === substr_count( $out, 'tpas__buttons' ) );

update_option( TP_AI_Options::OPTION_KEY, array( 'position' => 'after_thumbnail', 'panel_position' => 'manual' ) );
TP_AI_Options::flush();
$thumb = '<img src="x.jpg">';
$after = TP_AI_Frontend::maybe_after_thumbnail( $thumb, 42, '' );
t( 'frontend: buttons appended under the thumbnail', 0 === strpos( $after, $thumb ) && false !== strpos( $after, 'tpas__buttons' ) );

update_option( TP_AI_Options::OPTION_KEY, array( 'position' => 'manual', 'panel_position' => 'manual' ) );
TP_AI_Options::flush();
$out = TP_AI_Frontend::maybe_inject( $content );
t( 'frontend: manual placement leaves content untouched', $content === $out );

// Asset loading.
$GLOBALS['tp_enqueued'] = array('scripts'=>array(),'styles'=>array());
TP_AI_Frontend::enqueue_assets();
t( 'assets: css + js enqueued on singular', ! empty( $GLOBALS['tp_enqueued']['styles']['topersia-ai'] ) && ! empty( $GLOBALS['tp_enqueued']['scripts']['topersia-ai'] ) );
t( 'assets: localized object present with nonce', isset( $GLOBALS['tp_localized']['tpasFront']['nonce'] ) && '' !== $GLOBALS['tp_localized']['tpasFront']['nonce'] );
t( 'assets: localized ajax url points to admin-ajax', false !== strpos( $GLOBALS['tp_localized']['tpasFront']['ajax'], 'admin-ajax.php' ) );

$GLOBALS['tp_is_singular'] = false;
$GLOBALS['tp_is_archive']  = true;
$GLOBALS['tp_enqueued'] = array('scripts'=>array(),'styles'=>array());
update_option( TP_AI_Options::OPTION_KEY, array( 'show_on_archive' => 0 ) );
TP_AI_Options::flush();
TP_AI_Frontend::enqueue_assets();
t( 'assets: archive without show_on_archive loads nothing', empty( $GLOBALS['tp_enqueued']['scripts'] ) );

update_option( TP_AI_Options::OPTION_KEY, array( 'show_on_archive' => 1 ) );
TP_AI_Options::flush();
TP_AI_Frontend::enqueue_assets();
t( 'assets: archive with show_on_archive loads js', ! empty( $GLOBALS['tp_enqueued']['scripts']['topersia-ai'] ) );

// Shortcodes.
TP_AI_Shortcode::init();
t( 'shortcodes: all four registered', isset( $GLOBALS['tp_shortcodes']['tpas'], $GLOBALS['tp_shortcodes']['topersia_ai'], $GLOBALS['tp_shortcodes']['tpas_ai'], $GLOBALS['tp_shortcodes']['topersia_ai_panel'] ) );
$html = do_shortcode( '[tpas services="chatgpt,deepseek"]' );
t( 'shortcode: [tpas] honors the services attribute', 2 === substr_count( $html, 'class="tpas__btn tpas__btn--' ) );
$ph = do_shortcode( '[tpas_ai]' );
t( 'shortcode: [tpas_ai] renders the panel', false !== strpos( $ph, 'data-tpas-json=' ) );

// Blocks.
TP_AI_Block::register_blocks();
t( 'blocks: both block types registered', ! empty( $GLOBALS['tp_blocks']['tpas/buttons'] ) && ! empty( $GLOBALS['tp_blocks']['tpas/panel'] ) );
$bh = TP_AI_Block::render_buttons( array( 'services' => array( 'gemini' ) ) );
t( 'block: buttons callback honors attributes', 1 === substr_count( $bh, 'gemini.google.com' ) );
$bp = TP_AI_Block::render_panel( array( 'types' => array( 'plan' ), 'default_type' => 'plan' ) );
preg_match( '/data-tpas-json="([^"]*)"/', $bp, $bpm );
$bc = json_decode( htmlspecialchars_decode( $bpm[1] ), true );
t( 'block: panel callback bakes attributes into config', is_array( $bc ) && 'plan' === $bc['default'] );

// Reset.
update_option( TP_AI_Options::OPTION_KEY, array() );
TP_AI_Options::flush();
$GLOBALS['tp_is_singular'] = true;
$GLOBALS['tp_is_archive']  = false;