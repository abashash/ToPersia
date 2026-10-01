<?php
/**
 * ToPersia 4.4.0 phase 1 — P5-D247 published-only context, the FIELDMAP
 * whitelist for meta facts, and the legacy index drop.
 *
 * Runs last: it declares stand-ins for ToPersia Render's contract
 * functions part-way through, and a declared function cannot be undone.
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Phase 1 / 4.4.0' );

/* ---- Fixture: one post per non-public state, plus a published sibling ---- */
$GLOBALS['tp_posts'][43] = array( 'type' => 'post', 'title' => 'TPHIDDEN draft', 'status' => 'draft' );
$GLOBALS['tp_posts'][44] = array( 'type' => 'post', 'title' => 'TPHIDDEN private', 'status' => 'private' );
$GLOBALS['tp_posts'][45] = array( 'type' => 'post', 'title' => 'TPHIDDEN password', 'password' => 'secret' );
$GLOBALS['tp_posts'][46] = array( 'type' => 'post', 'title' => 'TPHIDDEN pending', 'status' => 'pending' );
$GLOBALS['tp_posts'][47] = array( 'type' => 'post', 'title' => 'Kashan' );

// ---- published_ids: WordPress fallback (Render not loaded yet) -------------
t( 'published_ids: fallback keeps only published, password-free posts',
	array( 42, 47 ) === TP_AI_Context::published_ids( array( 43, 42, 44, 45, 46, 47, 999 ) ) );
t( 'published_ids: fallback de-duplicates and ignores non-ids',
	array( 42 ) === TP_AI_Context::published_ids( array( 42, '42', 0, 'x' ) ) );

// ---- AJAX: unpublished post id -> 404 before any cache or API work --------
update_option( TP_AI_Options::OPTION_KEY, array(
	'post_types'              => array( 'post' ),
	'internal_engines'        => array(
		array( 'id' => 'eng_test', 'provider' => 'deepseek', 'api_key' => 'sk-live', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_test',
) );
TP_AI_Options::flush();
$GLOBALS['tp_http_calls']    = 0;
$GLOBALS['tp_http_override'] = array( 'code' => 200, 'body' => json_encode( array(
	'choices' => array( array( 'message' => array( 'content' => 'TPHIDDEN leaked summary' ), 'finish_reason' => 'stop' ) ),
) ) );
update_post_meta( 43, '_tpas_ai_summary_en', array( 'text' => 'TPHIDDEN cached', 'created' => time() ) );

foreach ( array( 43 => 'draft', 44 => 'private', 45 => 'password', 46 => 'pending' ) as $pid => $why ) {
	$res = tp_ajax( array( 'TP_AI_Summary', 'handle_ajax' ), array( 'lang' => 'en', 'type' => 'summary', 'post' => (string) $pid, 'url' => '' ) );
	t( "ajax: {$why} post -> 404 'Not available.', nothing summarised",
		is_array( $res ) && false === $res['ok'] && 404 === $res['status'] && 'Not available.' === $res['data']['message']
		&& false === strpos( wp_json_encode( $res ), 'TPHIDDEN' ) );
}
unset( $GLOBALS['tp_http_override'] );

// ---- is_serialized_text / fact_allowed ------------------------------------
$serial = array( 'a:0:{}', 'a:1:{i:0;s:3:"abc";}', 's:3:"abc";', 'i:5;', 'b:1;', 'd:0.5;', 'N;', 'O:8:"stdClass":0:{}' );
$plain  = array( 'متن عادی', '5120850', 'a:0', 'Isfahan, Iran', '' );
t( 'is_serialized_text: every PHP-serialized shape is recognised',
	count( $serial ) === count( array_filter( $serial, array( 'TP_AI_Context', 'is_serialized_text' ) ) ) );
t( 'is_serialized_text: plain text and numbers are not',
	array() === array_filter( $plain, array( 'TP_AI_Context', 'is_serialized_text' ) ) );

t( 'fact_allowed: underscore, WPML, media-ids and status keys are refused',
	! TP_AI_Context::fact_allowed( '', 'x' )
	&& ! TP_AI_Context::fact_allowed( '_edit_lock', '1' )
	&& ! TP_AI_Context::fact_allowed( 'wpml_media_processed', '1' )
	&& ! TP_AI_Context::fact_allowed( '_wpml_media_duplicate', '1' )
	&& ! TP_AI_Context::fact_allowed( 'copied_media_ids', '1' )
	&& ! TP_AI_Context::fact_allowed( 'content_status', 'verified' )
	&& ! TP_AI_Context::fact_allowed( 'status', 'active' )
	&& ! TP_AI_Context::fact_allowed( 'faq_row_status', 'active' ) );
t( 'fact_allowed: a plain field passes, the same field with a serialized value does not',
	TP_AI_Context::fact_allowed( 'population', '5120850' ) && ! TP_AI_Context::fact_allowed( 'population', 'a:0:{}' ) );

// ---- meta_facts without ToPersia Render: nothing, never raw post meta -----
update_post_meta( 42, 'copied_media_ids', 'a:0:{}' );
update_post_meta( 42, 'population', '5120850' );
$ctx = TP_AI_Context::build( 42 );
t( 'meta_facts: without ToPersia Render the context carries no meta facts at all',
	array() === $ctx['jetengine']['meta_fields'] );

// ---- Stand-ins for ToPersia Render's contract ------------------------------
if ( ! function_exists( 'topersia_g_map' ) ) {
	function topersia_g_map() {
		return array(
			'population'       => array( 'label' => 'جمعیت', 'zone' => 'card' ),
			'area_km2'         => array( 'label' => 'مساحت', 'zone' => 'card' ),
			'content_status'   => array( 'label' => 'وضعیت محتوا', 'zone' => 'card' ),
			'status'           => array( 'label' => 'وضعیت', 'zone' => 'card' ),
			'copied_media_ids' => array( 'label' => 'Copied Media Ids', 'zone' => 'card' ),
			'gallery_meta'     => array( 'label' => 'گالری', 'zone' => 'card' ),
			'_edit_lock'       => array( 'label' => 'قفل', 'zone' => 'card' ),
			'climate_overview' => array( 'label' => 'اقلیم', 'zone' => 'details' ),
			'unlabelled'       => array( 'zone' => 'card' ),
			'name_fa'          => array( 'label' => 'نام', 'zone' => 'names' ),
		);
	}
	function topersia_g_zone_fields( $post_id, $zone ) {
		$fields = array(
			'card'    => array(
				'population'       => '5120850',
				'area_km2'         => '107018',
				'content_status'   => 'verified',
				'status'           => 'active',
				'copied_media_ids' => 'a:0:{}',
				'gallery_meta'     => 'plain-but-formats-serialized',
				'_edit_lock'       => '1700000000:1',
				'unlabelled'       => 'x',
			),
			'details' => array( 'climate_overview' => 'نیمه‌خشک' ),
			'names'   => array( 'name_fa' => 'استان اصفهان' ),
		);
		return isset( $fields[ $zone ] ) ? $fields[ $zone ] : array();
	}
	function topersia_g_value( $name, $raw ) {
		/* One field whose formatted value is serialized text — the second guard. */
		return 'gallery_meta' === $name ? 'a:1:{i:0;i:5;}' : '<b>' . esc_html( (string) $raw ) . '</b>';
	}
}

$ctx   = TP_AI_Context::build( 42 );
$facts = $ctx['jetengine']['meta_fields'];
t( 'meta_facts: only labelled card/details FIELDMAP fields, keyed by their glossary label',
	array( 'جمعیت' => '5120850', 'مساحت' => '107018', 'اقلیم' => 'نیمه‌خشک' ) === $facts, wp_json_encode( $facts ) );

$block = TP_AI_Context::structured_block( $ctx );
t( 'meta_facts: "Copied Media Ids: a:0:{}" cannot reach the prompt — nor any status, WPML or serialized value',
	false === strpos( $block, 'Copied Media Ids' ) && false === strpos( $block, 'a:0:{}' ) && false === strpos( $block, 'a:1:{' )
	&& false === strpos( $block, 'verified' ) && false === strpos( $block, 'active' ) && false === strpos( $block, '1700000000' )
	&& false !== strpos( $block, '5120850' ) );

// ---- relations: the JetEngine fallback only names published items ----------
if ( ! function_exists( 'jet_engine' ) ) {
	class TP_Test_Relation {
		public function get_children( $post_id, $what ) { return 42 === (int) $post_id ? array( 43, 47, 45 ) : array(); }
		public function get_parents( $post_id, $what ) { return 42 === (int) $post_id ? array( 44, 46 ) : array(); }
	}
	class TP_Test_Relations {
		public function get_relations() { return array( new TP_Test_Relation() ); }
	}
	function jet_engine() {
		static $engine = null;
		if ( null === $engine ) {
			$engine            = new stdClass();
			$engine->relations = new TP_Test_Relations();
		}
		return $engine;
	}
}

$ctx = TP_AI_Context::build( 42 );
t( 'relations: draft, private, pending and password-protected neighbours are never named',
	array( 'Kashan' ) === $ctx['jetengine']['relations']
	&& false === strpos( TP_AI_Context::structured_block( $ctx ), 'TPHIDDEN' ), wp_json_encode( $ctx['jetengine']['relations'] ) );

// ---- published_ids defers to ToPersia Render's central gate ----------------
if ( ! function_exists( 'topersia_g_published' ) ) {
	function topersia_g_published( array $ids ) {
		$GLOBALS['tp_gate_calls'] = ( $GLOBALS['tp_gate_calls'] ?? 0 ) + 1;
		return array_values( array_intersect( array_map( 'intval', $ids ), array( 47 ) ) );
	}
}
$GLOBALS['tp_gate_calls'] = 0;
t( 'published_ids: with ToPersia Render active, the central topersia_g_published() decides',
	array( 47 ) === TP_AI_Context::published_ids( array( 42, 47 ) ) && 1 === $GLOBALS['tp_gate_calls'] );

// ---- DB: the old post_language index is dropped once, idempotently --------
global $wpdb;
$wpdb->queries = array();
$wpdb->indexes = array( (object) array( 'Table' => 'wp_tpas_ai_summaries', 'Key_name' => 'post_language' ) );
$first         = TP_AI_DB::drop_legacy_index();
$alters        = array_values( array_filter( $wpdb->queries, function ( $q ) { return is_string( $q ) && 0 === stripos( $q, 'ALTER TABLE' ); } ) );
t( 'db: SHOW INDEX finds post_language -> one ALTER TABLE … DROP INDEX post_language',
	true === $first && 1 === count( $alters ) && false !== strpos( $alters[0], 'DROP INDEX post_language' )
	&& false !== strpos( implode( "\n", $wpdb->queries ), "SHOW INDEX FROM" ), implode( ' | ', $wpdb->queries ) );

$wpdb->queries = array();
$wpdb->indexes = array();
$second        = TP_AI_DB::drop_legacy_index();
t( 'db: index already gone -> no ALTER at all (idempotent)',
	false === $second && array() === array_filter( $wpdb->queries, function ( $q ) { return is_string( $q ) && 0 === stripos( $q, 'ALTER' ); } ) );

$db_src = file_get_contents( TP_AI_PATH . 'includes/class-tp-ai-db.php' );
t( 'db: install() runs the drop on every schema upgrade, before the version is stored',
	(bool) preg_match( '/self::drop_legacy_index\(\);\s*update_option\( self::VERSION_KEY, self::VERSION \);/', $db_src ) );

// ---- One version everywhere ------------------------------------------------
$main   = file_get_contents( TP_AI_PATH . 'topersia-ai-summarizer.php' );
$readme = file_get_contents( TP_AI_PATH . 'readme.txt' );
preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $main, $v_head );
preg_match( "/define\\(\\s*'TP_AI_VERSION',\\s*'([^']+)'/", $main, $v_const );
preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $v_readme );
t( 'version: header, TP_AI_VERSION, readme stable tag and DB schema agree',
	isset( $v_head[1], $v_const[1], $v_readme[1] )
	&& $v_head[1] === $v_const[1] && $v_const[1] === $v_readme[1] && TP_AI_DB::VERSION === $v_head[1],
	( $v_head[1] ?? '?' ) . ' / ' . ( $v_const[1] ?? '?' ) . ' / ' . ( $v_readme[1] ?? '?' ) . ' / ' . TP_AI_DB::VERSION );
