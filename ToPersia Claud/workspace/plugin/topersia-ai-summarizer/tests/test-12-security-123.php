<?php
/**
 * 1.2.3 — security round 2: P5-D355 (key) · P5-D356 (freshness, XSS, AJAX reply).
 *
 * Each claim is run against a mutant — the same class with the 1.2.3 fix taken
 * out — and must fail there. Loaded last: the wp-config constant cannot be
 * undefined, so the constant section is the final one.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Security / 1.2.3' );

/** Load a copy of a plugin class with one textual mutation, under a new name. */
function tp123_mutant( $file, $class, $suffix, $from, $to ) {
	$src = (string) file_get_contents( TP_AI_PATH . 'includes/' . $file );
	if ( false === strpos( $src, $from ) ) {
		return '';
	}
	$name = $class . '_' . $suffix;
	$src  = str_replace( array( 'class ' . $class . ' ', $from ), array( 'class ' . $name . ' ', $to ), $src );
	eval( '?>' . $src );
	return $name;
}

/* ── R56 · P5-D355 — the key never reaches the public tpas_api_payload filter ── */

$tp123_seen = array();
add_filter( 'tpas_api_payload', function ( $body, $endpoint = null, $key = null ) use ( &$tp123_seen ) {
	$tp123_seen[] = array( $body, $endpoint, $key );
	return $body;
}, 10, 3 );
update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines'        => array(
		array( 'id' => 'eng_a', 'provider' => 'deepseek', 'api_key' => 'sk-secret-123', 'model' => '', 'base_url' => '' ),
		array( 'id' => 'eng_b', 'provider' => 'claude', 'api_key' => 'sk-secret-123', 'model' => 'm1', 'base_url' => '' ),
		array( 'id' => 'eng_c', 'provider' => 'gemini', 'api_key' => 'sk-secret-123', 'model' => 'm2', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_a',
) );
TP_AI_Options::flush();
$GLOBALS['tp_http_override'] = array( 'code' => 200, 'body' => '{}' );

$tp123_leaks = function ( $api ) use ( &$tp123_seen ) {
	$tp123_seen = array();
	foreach ( array( 'eng_a', 'eng_b', 'eng_c' ) as $id ) {
		call_user_func( array( $api, 'chat' ), 's', 'u', array(), $id );
	}
	if ( 3 !== count( $tp123_seen ) ) {
		return true;
	}
	return false !== strpos( serialize( $tp123_seen ), 'sk-secret-123' );
};
$m56 = tp123_mutant( 'class-tp-ai-api.php', 'TP_AI_API', 'M56', "apply_filters( 'tpas_api_payload', \$body, self::public_endpoint( \$endpoint ) )", "apply_filters( 'tpas_api_payload', \$body, \$endpoint, \$key )" );
$tp123_real_leak = $tp123_leaks( 'TP_AI_API' );
$tp123_first     = $tp123_seen[0];
t( 'R56: the key is absent from tpas_api_payload args in all three protocols; mutant leaks',
	! $tp123_real_leak && '' !== $m56 && $tp123_leaks( $m56 ) );
t( 'R56: the filter still gets the body and a keyless endpoint (provider/model)',
	isset( $tp123_first[0]['model'] ) || isset( $tp123_first[0]['messages'] ) ? isset( $tp123_first[1]['provider'] ) && ! array_key_exists( 'api_key', (array) $tp123_first[1] ) : false );

/* ── constant — keep last (cannot be undefined) ──────────────────────── */

$tp123_form = function ( $class ) {
	$m = new ReflectionMethod( $class, 'engine_row_html' );
	$m->setAccessible( true );
	ob_start();
	$m->invoke( null, array( 'id' => 'eng_a', 'provider' => 'deepseek', 'api_key' => 'sk-secret-123', 'model' => '', 'base_url' => '' ), true );
	return ob_get_clean();
};
$tp123_sanitize = function ( $class ) {
	$_POST = array();
	$m = new ReflectionMethod( $class, 'sanitize_engines' );
	$m->setAccessible( true );
	$rows = $m->invoke( null, array( array( 'id' => 'eng_a', 'provider' => 'deepseek', 'api_key' => 'typed-new-key', 'model' => '', 'base_url' => '' ) ) );
	return $rows[0]['api_key'];
};

t( 'const: before it is defined the form shows the key field and the typed key is stored',
	false !== strpos( $tp123_form( 'TP_AI_Admin' ), 'type="password"' ) && 'typed-new-key' === $tp123_sanitize( 'TP_AI_Options' ) );

define( 'TOPERSIA_AI_API_KEY', 'wpconfig-key-777' );
TP_AI_Options::flush();

$mc1 = tp123_mutant( 'class-tp-ai-options.php', 'TP_AI_Options', 'MC1', "'api_key'  => self::key_from_constant()", "'api_key'  => false && self::key_from_constant()" );
$mc3 = tp123_mutant( 'class-tp-ai-admin.php', 'TP_AI_Admin', 'MC3', "if ( TP_AI_Options::key_from_constant() ) {\n\t\t\t// 1.2.3", "if ( false ) {\n\t\t\t// 1.2.3" );
$mc2 = tp123_mutant( 'class-tp-ai-options.php', 'TP_AI_Options', 'MC2', "if ( self::key_from_constant() ) {\n\t\t\t\t// 1.2.3", "if ( false ) {\n\t\t\t\t// 1.2.3" );

$tp123_html = $tp123_form( 'TP_AI_Admin' );
$tp123_row = TP_AI_Options::engine_by_id( 'eng_a' );
t( 'const: the constant beats the option in the resolved endpoint; mutant that ignores it fails',
	'wpconfig-key-777' === TP_AI_Options::internal_endpoint( $tp123_row )['api_key'] && '' !== $mc1 && 'sk-secret-123' === call_user_func( array( $mc1, 'internal_endpoint' ), $tp123_row )['api_key'] );
t( 'const: the admin field says "set in wp-config" and has no input',
	false !== strpos( $tp123_html, 'tpas-engine-key-const' ) && false === strpos( $tp123_html, 'type="password"' ) && false === strpos( $tp123_html, 'sk-secret-123' ) && false === strpos( $tp123_html, 'wpconfig-key-777' ) && '' !== $mc3 && false !== strpos( $tp123_form( $mc3 ), 'type="password"' ) );
t( 'const: saving stores nothing from the form (the typed key is dropped, the stored row is kept)',
	'sk-secret-123' === $tp123_sanitize( 'TP_AI_Options' ) && '' !== $mc2 && 'typed-new-key' === $tp123_sanitize( $mc2 ) );
$GLOBALS['tp_http_override'] = array( 'code' => 200, 'body' => '{}' );
unset( $GLOBALS['tp_http_last'] );
TP_AI_API::chat( 's', 'u', array(), 'eng_a' );
t( 'const: the outgoing request carries the constant, not the stored key',
	isset( $GLOBALS['tp_http_last'] ) && false !== strpos( json_encode( $GLOBALS['tp_http_last']['args'] ), 'wpconfig-key-777' ) && false === strpos( json_encode( $GLOBALS['tp_http_last']['args'] ), 'sk-secret-123' ) );
