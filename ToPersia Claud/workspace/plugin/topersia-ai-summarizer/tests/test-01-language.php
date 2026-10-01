<?php
/**
 * Nine-language coverage and prompt wiring.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Localization' );

t( '9 languages in ToPersia order', array( 'fa', 'en', 'ar', 'fr', 'de', 'es', 'ru', 'tr', 'zh' ) === TP_AI_Localize::languages() );

t( 'locale normalization: fa_IR -> fa', 'fa' === TP_AI_Localize::normalize( 'fa_IR' ) );
t( 'locale normalization: en-US -> en', 'en' === TP_AI_Localize::normalize( 'en-US' ) );
t( 'locale normalization: zh_Hans -> zh', 'zh' === TP_AI_Localize::normalize( 'zh_Hans' ) );
t( 'locale normalization: unknown -> empty', '' === TP_AI_Localize::normalize( 'xx_XX' ) );
t( 'sanitize: garbage falls back to en', 'en' === TP_AI_Localize::sanitize( 'garbage!!' ) );

foreach ( TP_AI_Localize::languages() as $code ) {
	$strings = TP_AI_Localize::all( $code );
	$missing = array();
	foreach ( array( 'heading_default', 'prompt_default', 'prompt_keypoints', 'prompt_plan',
		'ai_kicker', 'ai_title', 'ai_desc', 'ai_type_summary', 'ai_type_keypoints', 'ai_type_plan',
		'ai_copy', 'ai_copied', 'ai_generate', 'ai_regenerate', 'ai_loading', 'ai_error',
		'ai_no_key', 'ai_meta_engine', 'ai_meta_output', 'ai_internal_label', 'ai_disclaimer', 'ai_engines_label' ) as $key ) {
		if ( ! isset( $strings[ $key ] ) || '' === $strings[ $key ] ) {
			$missing[] = $key;
		}
	}
	t( "lang {$code}: every UI key translated (" . count( $missing ) . ' missing)', empty( $missing ), implode( ',', $missing ) );
}

$fa = TP_AI_Localize::all( 'fa' );
t( 'fa: prompt mentions the four structured parts', false !== strpos( $fa['prompt_default'], '۱)' ) && false !== strpos( $fa['prompt_default'], '۴)' ) );
t( 'fa: keypoints prompt is a separate, short template', false !== strpos( $fa['prompt_keypoints'], 'نکات کلیدی' ) );

t( 'fallback: unknown lang merges English', TP_AI_Localize::get( 'heading_default', 'zz' ) === TP_AI_Localize::get( 'heading_default', 'en' ) );

t( 'prompt_for_type: summary uses the structured default', TP_AI_Localize::prompt_for_type( 'summary', 'en' ) === TP_AI_Localize::get( 'prompt_default', 'en' ) );
t( 'prompt_for_type: keypoints -> prompt_keypoints', TP_AI_Localize::prompt_for_type( 'keypoints', 'fa' ) === TP_AI_Localize::get( 'prompt_keypoints', 'fa' ) );
t( 'prompt_for_type: plan -> prompt_plan', TP_AI_Localize::prompt_for_type( 'plan', 'de' ) === TP_AI_Localize::get( 'prompt_plan', 'de' ) );

$sys = TP_AI_Localize::context_system( 'en' );
t( 'context_system: keeps the site name', false !== strpos( $sys, 'ToPersia' ) );
t( 'context_system: forbids outside knowledge and HTML', false !== strpos( $sys, 'outside knowledge' ) && false !== strpos( $sys, 'Never output HTML' ) );

$GLOBALS['tp_locale'] = 'fa_IR';
t( 'current language follows get_locale()', 'fa' === TP_AI_Localize::language() );
$GLOBALS['tp_locale'] = 'en_US';
t( 'current language falls back to en', 'en' === TP_AI_Localize::language() );

add_filter( 'wpml_current_language', function ( $locale ) { return 'de'; } );
t( 'wpml filter overrides the locale', 'de' === TP_AI_Localize::language() );
$GLOBALS['tp_hooks']['wpml_current_language'] = array();