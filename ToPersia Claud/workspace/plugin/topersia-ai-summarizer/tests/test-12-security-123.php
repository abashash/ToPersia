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

/* ── R53 · R54 · R55 — freshness and the cap (P5-D356) ─────────────── */

if ( ! function_exists( 'topersia_content_hash' ) ) {
	function topersia_content_hash( $post_id ) { return isset( $GLOBALS['tp_hash'][ $post_id ] ) ? $GLOBALS['tp_hash'][ $post_id ] : ''; }
}
if ( ! function_exists( 'topersia_cache_is_stale' ) ) {
	function topersia_cache_is_stale( $post_id, $hash ) { return '' !== (string) $hash && topersia_content_hash( $post_id ) !== (string) $hash; }
}
if ( ! function_exists( 'topersia_cache_remember' ) ) {
	function topersia_cache_remember() { return 'rebuilt'; }
}
/* the gate's cap functions (ToPersia Render) — stand-ins with the same contract */
$GLOBALS['tp123_used'] = 0;
if ( ! function_exists( 'topersia_g_ai_caps' ) ) {
	function topersia_g_ai_caps() { return array( 'user' => 2, 'site' => 100 ); }
	function topersia_g_ai_user_used( $u ) { return (int) $GLOBALS['tp123_used']; }
	function topersia_g_ai_site_used() { return (int) $GLOBALS['tp123_used']; }
	function topersia_g_ai_count( $u ) { $GLOBALS['tp123_used']++; }
	function topersia_g_ai_cap_check( $user_used, $site_used, $caps ) { return (int) $site_used >= (int) $caps['site'] ? 'site' : ( (int) $user_used >= (int) $caps['user'] ? 'user' : '' ); }
}

$tp123_keep_wpdb = $GLOBALS['wpdb'];
$GLOBALS['wpdb'] = new class() extends TP_Test_WPDB {
	public $rows = array();
	public function get_row( $query = null, $output = OBJECT ) {
		foreach ( $this->rows as $r ) {
			if ( false !== strpos( (string) $query, 'post_id = ' . $r->post_id ) && false !== strpos( (string) $query, "type = '" . $r->type . "'" ) && false !== strpos( (string) $query, "language = '" . $r->language . "'" ) ) {
				return $r;
			}
		}
		return null;
	}
	public function insert( $table, $data, $format = null ) {
		$this->rows[] = (object) array_merge( $data, array( 'id' => count( $this->rows ) + 1 ) );
		return 1;
	}
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		foreach ( $this->rows as $i => $r ) {
			if ( (int) $r->id === (int) $where['id'] ) {
				$this->rows[ $i ] = (object) array_merge( (array) $r, $data );
			}
		}
		return 1;
	}
};

/* R55 — post_content (tags stripped) is part of the freshness check */
$GLOBALS['tp_hash'][42] = 'hash-1';
$GLOBALS['tp_posts'][42]['content'] = 'Isfahan <b>text</b> one.';
$tp123_store = TP_AI_Core_Bridge::store( 42, 'summary', 'en' );
call_user_func( $tp123_store['put'], 'k', array( 'value' => 'AI text', 'meta' => array( 'provider' => 'x', 'model' => 'y' ), 'hash' => 'hash-1' ) );
$r55_fresh = ! TP_AI_Core_Bridge::is_stale( 42, 'summary', 'en' ) && 'hash-1' === call_user_func( $tp123_store['get'], 'k' )['hash'];
$GLOBALS['tp_posts'][42]['content'] = 'Isfahan <b>text</b> one.'; // markup-only change below
$GLOBALS['tp_posts'][42]['content'] = 'Isfahan <i>text</i> one.';
$r55_tags = ! TP_AI_Core_Bridge::is_stale( 42, 'summary', 'en' );   // a tag-only edit does not make it stale
$GLOBALS['tp_posts'][42]['content'] = 'Isfahan text two - the body changed.';
$r55_stale = TP_AI_Core_Bridge::is_stale( 42, 'summary', 'en' ) && 'hash-1|content' === call_user_func( $tp123_store['get'], 'k' )['hash'] && true === TP_AI_Summary::get_cached( 42, 'summary', 'en' )['stale'];
$m55 = tp123_mutant( 'class-tp-ai-core-bridge.php', 'TP_AI_Core_Bridge', 'M55', "return '' !== \$seen && self::content_digest( \$post_id ) !== \$seen;", 'return false;' );
t( 'R55: an edit of post_content makes the stored output stale (core hash unchanged); a tag-only edit does not; mutant without the digest fails',
	$r55_fresh && $r55_tags && $r55_stale && '' !== $m55 && ! call_user_func( array( $m55, 'is_stale' ), 42, 'summary', 'en' ) );

/* R54 — the internal engine's row is stamped, goes stale, and is rebuilt */
$GLOBALS['tp_hash'][42] = 'hash-2';
$GLOBALS['tp_posts'][42]['content'] = 'Isfahan is a province in central Iran. It has a famous square. The square is large and old.';
$t1 = TP_AI_Summary::internal_text( 42, 'keypoints', 'it' );
$r54_fresh = '' !== trim( $t1 ) && ! TP_AI_Core_Bridge::is_stale( 42, 'keypoints', 'it', 'internal' ) && 'hash-2' === get_post_meta( 42, TP_AI_Core_Bridge::hash_key( 'keypoints', 'it' ), true );
$GLOBALS['tp_hash'][42] = 'hash-3';
$GLOBALS['tp_posts'][42]['content'] = 'Isfahan is a province in central Iran. Zayandeh Rud flows through it. Si-o-se-pol is a bridge.';
$r54_stale = true === TP_AI_Summary::get_cached( 42, 'keypoints', 'it' )['stale'];
$t2 = TP_AI_Summary::internal_text( 42, 'keypoints', 'it' );
$r54_rebuilt = $t2 !== $t1 && false !== stripos( $t2, 'Zayandeh' ) && ! TP_AI_Core_Bridge::is_stale( 42, 'keypoints', 'it', 'internal' );
$m54a = tp123_mutant( 'class-tp-ai-summary.php', 'TP_AI_Summary', 'M54a', "if ( \$cached && empty( \$cached['stale'] ) ) {\n\t\t\treturn (string) \$cached['text'];", "if ( \$cached ) {\n\t\t\treturn (string) \$cached['text'];" );
$m54b = tp123_mutant( 'class-tp-ai-core-bridge.php', 'TP_AI_Core_Bridge', 'M54b', "if ( '' === \$hash && 'internal' === \$source ) {\n\t\t\treturn true;", "if ( false ) {\n\t\t\treturn true;" );
$GLOBALS['tp_hash'][42] = 'hash-4';
$GLOBALS['tp_posts'][42]['content'] = 'Isfahan again: a new fact about the Naqsh-e Jahan Square appears here.';
t( 'R54: an internal row is stamped on save, goes stale on edit and is regenerated; mutant that serves the stale text fails',
	$r54_fresh && $r54_stale && $r54_rebuilt && '' !== $m54a && '' !== $m54b
	&& TP_AI_Summary::get_cached( 42, 'keypoints', 'it' )['stale'] && $t2 === call_user_func( array( $m54a, 'internal_text' ), 42, 'keypoints', 'it' ) );
delete_post_meta( 42, TP_AI_Core_Bridge::hash_key( 'keypoints', 'it' ) );
delete_post_meta( 42, TP_AI_Core_Bridge::digest_key( 'keypoints', 'it' ) );
t( 'R54: an internal row without any hash (written before 1.2.3) is stale; an AI row without one is not (old rule); mutant fails',
	TP_AI_Core_Bridge::is_stale( 42, 'keypoints', 'it', 'internal' ) && ! TP_AI_Core_Bridge::is_stale( 42, 'keypoints', 'it', 'ai' ) && ! call_user_func( array( $m54b, 'is_stale' ), 42, 'keypoints', 'it', 'internal' ) );

/* R53 — a rebuild of a stale output is counted against the daily cap */
update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_enabled'        => 1,
	'internal_engines'        => array( array( 'id' => 'eng_a', 'provider' => 'deepseek', 'api_key' => 'sk-x', 'model' => '', 'base_url' => '' ) ),
	'internal_default_engine' => 'eng_a',
) );
TP_AI_Options::flush();
if ( ! isset( $GLOBALS['tp_posts'][47] ) ) {
	$GLOBALS['tp_posts'][47] = array( 'type' => 'post', 'title' => 'Published', 'content' => 'Published content body text.' );
}
$GLOBALS['tp_hash'][47] = 'old';
$tp123_s47 = TP_AI_Core_Bridge::store( 47, 'summary', 'en' );
call_user_func( $tp123_s47['put'], 'k', array( 'value' => 'Old AI text', 'meta' => array(), 'hash' => 'old' ) );
$GLOBALS['tp_hash'][47] = 'new';   // the post changed: the stored row is stale
$tp123_run = function ( $handler ) {
	foreach ( array_keys( (array) ( $GLOBALS['tp_transients'] ?? array() ) ) as $k ) {
		if ( 0 === strpos( $k, 'tpas_rt_' ) ) {
			delete_transient( $k );
		}
	}
	$GLOBALS['tp123_used'] = 0;
	$out = array();
	for ( $i = 0; $i < 3; $i++ ) {
		$r     = tp_ajax( array( $handler, 'handle_ajax' ), array( 'lang' => 'en', 'type' => 'summary', 'post' => '47' ) );
		$out[] = is_array( $r ) ? ( $r['ok'] ? 'ok' : (int) $r['status'] ) : 'none';
	}
	return array( $out, $GLOBALS['tp123_used'] );
};
list( $r53_out, $r53_used ) = $tp123_run( 'TP_AI_Summary' );
$m53 = tp123_mutant( 'class-tp-ai-summary.php', 'TP_AI_Summary', 'M53', "if ( \$cached ) {\n\t\t\tself::count_rebuild( \$user_id );", "if ( false ) {\n\t\t\tself::count_rebuild( \$user_id );" );
list( $m53_out, $m53_used ) = $tp123_run( $m53 );
t( 'R53: rebuilding a stale output is counted — two pass, the third is a 429 at the cap; mutant that skips the count never reaches it',
	array( 'ok', 'ok', 429 ) === $r53_out && 2 === $r53_used && '' !== $m53 && array( 'ok', 'ok', 'ok' ) === $m53_out && 0 === $m53_used );
$GLOBALS['tp_hash'][47] = 'old';   // fresh again: served from cache, never counted
list( $r53b_out, $r53b_used ) = $tp123_run( 'TP_AI_Summary' );
t( 'R53: a fresh cached output is served without touching the cap', array( 'ok', 'ok', 'ok' ) === $r53b_out && 0 === $r53b_used );
$GLOBALS['wpdb'] = $tp123_keep_wpdb;

/* ── constant — keep last (cannot be undefined) ──────────────────────── */

update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines'        => array( array( 'id' => 'eng_a', 'provider' => 'deepseek', 'api_key' => 'sk-secret-123', 'model' => '', 'base_url' => '' ) ),
	'internal_default_engine' => 'eng_a',
) );
TP_AI_Options::flush();

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
