<?php
/**
 * ToPersia 4.4.1 phase 2 — temperature (SPRINT-4.4 2.7), the bridge to the
 * core's services layer (P5-D249) and central cache (P5-D250), the Gemini
 * key in a header.
 *
 * Every new claim is run on the real code and on a mutant that must fail.
 * Runs after phase 1; it declares stand-ins for the core's contract
 * functions, and a declared function cannot be undone.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Phase 2 / 1.2.0' );

/* ---- 2.7 temperature: a decimal 0-2, sent as is ---------------------------- */
$temp_claim = function ( callable $f ) {
	return 0.7 === $f( 0.7 ) && 1.3 === $f( '1,3' ) && 1.5 === $f( '۱٫۵' ) && 2.0 === $f( 7 )
		&& 0.0 === $f( -1 ) && 0.3 === $f( 'x' ) && 1.25 === $f( '1.25' );
};
$old_temp = function ( $t ) { return min( 2, absint( $t ) ) / 10; };   /* 1.1.1 */
t( 'temperature: a decimal 0-2 kept as is (7 -> 2, 0.7 -> 0.7, Persian digits and separator accepted); the 1.1.1 formula fails the same claim',
	$temp_claim( array( 'TP_AI_Options', 'sanitize_temperature' ) ) && ! $temp_claim( $old_temp ) );

$admin_src = file_get_contents( TP_AI_PATH . 'includes/class-tp-ai-admin.php' );
t( 'temperature: the settings field is the decimal itself (step 0.1, 0-2), not tenths',
	false !== strpos( $admin_src, 'type="number" step="0.1" min="0" max="2"' )
	&& false === strpos( $admin_src, "round( \$options['internal_temperature'] * 10 )" ) );

/* ---- Gemini key: header, never the URL -------------------------------------- */
$api_src    = file_get_contents( TP_AI_PATH . 'includes/class-tp-ai-api.php' );
$gemini_ok  = function ( $src ) {
	return false !== strpos( $src, "'x-goog-api-key' => \$key," ) && false === strpos( $src, ':generateContent?key=' );
};
t( 'gemini: key in the x-goog-api-key header, not in the URL (URLs are logged); a ?key= mutant fails',
	$gemini_ok( $api_src ) && ! $gemini_ok( str_replace( "':generateContent';", "':generateContent?key=' . rawurlencode( \$key );", $api_src ) ) );

update_option( TP_AI_Options::OPTION_KEY, array( 'internal_enabled' => 1, 'internal_engines' => array( array( 'id' => 'eng_a', 'provider' => 'openai', 'api_key' => 'sk-test-1234', 'model' => 'gpt-4o-mini', 'base_url' => '' ) ), 'internal_default_engine' => 'eng_a' ) );
TP_AI_Options::flush();
/* ---- 1.2.1: no ai.text in the core layer — the summarizer's own settings are the engine ---- */
$opt_src    = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-tp-ai-options.php' );
$bridge_src = (string) file_get_contents( dirname( __DIR__ ) . '/includes/class-tp-ai-core-bridge.php' );
$own_engine = function ( $opt, $bridge ) {
	return false === strpos( $opt, 'TP_AI_Core_Bridge::engine()' ) && false === strpos( $bridge, "'topersia_service_adapters'" ) && false === strpos( $bridge, "'topersia_services_legacy'" );
};
t( 'bridge 1.2.1: engine and key only from the summarizer settings; no ai.text adapters or legacy for the core layer (mutant: the 1.2.0 layer engine)',
	$own_engine( $opt_src, $bridge_src ) && 'eng_a' === TP_AI_Options::default_engine()['id']
	&& ! $own_engine( str_replace( 'return self::legacy_engine();', "\$layer = TP_AI_Core_Bridge::engine();\n\t\treturn self::legacy_engine();", $opt_src ), $bridge_src ) );
/* ---- Cache: the summaries table is the store; stale is a hash mismatch ------ */
$GLOBALS['tp_hash'] = array( 51 => str_repeat( 'a', 40 ) );
if ( ! function_exists( 'topersia_content_hash' ) ) {
	function topersia_content_hash( $post_id ) { return isset( $GLOBALS['tp_hash'][ $post_id ] ) ? $GLOBALS['tp_hash'][ $post_id ] : ''; }
}
if ( ! function_exists( 'topersia_cache_is_stale' ) ) {
	function topersia_cache_is_stale( $post_id, $hash ) { return '' !== (string) $hash && topersia_content_hash( $post_id ) !== (string) $hash; }
}
if ( ! function_exists( 'topersia_cache_remember' ) ) {
	function topersia_cache_remember() { return ''; }
}
/* the stub wpdb keeps nothing — an in-memory summaries table for this claim only */
$keep_wpdb       = $GLOBALS['wpdb'];
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
$store = TP_AI_Core_Bridge::store( 51, 'summary', 'fa' );
call_user_func( $store['put'], 'k', array( 'value' => 'خلاصۀ آزمون', 'meta' => array( 'provider' => 'Gemini', 'model' => 'gemini-3.8-flash' ), 'hash' => str_repeat( 'a', 40 ) ) );
$got   = call_user_func( $store['get'], 'k' );
$fresh = ! TP_AI_Core_Bridge::is_stale( 51, 'summary', 'fa' );
$GLOBALS['tp_hash'][51] = str_repeat( 'b', 40 );   /* a FIELDMAP meta changed */
$stale = TP_AI_Core_Bridge::is_stale( 51, 'summary', 'fa' );
$cached = TP_AI_Summary::get_cached( 51, 'summary', 'fa' );
t( 'cache: an output is stored once in the summaries table with its content hash; a changed hash marks it stale, it is not deleted  P5-D250',
	is_array( $got ) && 'خلاصۀ آزمون' === $got['value'] && str_repeat( 'a', 40 ) === $got['hash'] && $fresh && $stale
	&& is_array( $cached ) && 'خلاصۀ آزمون' === $cached['text'] && true === $cached['stale'] );
$GLOBALS['wpdb'] = $keep_wpdb;

$sum_src  = file_get_contents( TP_AI_PATH . 'includes/class-tp-ai-summary.php' );
$route_ok = function ( $src ) {
	return false !== strpos( $src, "topersia_cache_remember(\n\t\t\t\t'ai.text'," ) && false !== strpos( $src, "'expensive'      => true," )
		&& false !== strpos( $src, "if ( class_exists( 'TP_AI_Core_Bridge' ) && TP_AI_Core_Bridge::cache_available() ) {\n\t\t\treturn;\n\t\t}\n\n\t\tTP_AI_DB::delete_post_summaries( \$post_id );" );
};
t( 'cache: generation goes through the core\'s central cache as an expensive output, and editing a post no longer deletes it; a mutant that deletes on save fails  P5-D250 · rule 0-6',
	$route_ok( $sum_src ) && ! $route_ok( str_replace( "if ( class_exists( 'TP_AI_Core_Bridge' ) && TP_AI_Core_Bridge::cache_available() ) {\n\t\t\treturn;\n\t\t}\n\n", '', $sum_src ) ) );
