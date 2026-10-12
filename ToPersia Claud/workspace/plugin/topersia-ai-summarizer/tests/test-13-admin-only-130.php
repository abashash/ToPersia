<?php
/**
 * 1.3.0 — P5-D358: the AI summary is made by the site admin only.
 *
 * Each claim runs against a mutant — the same class with the 1.3.0 rule taken
 * out — and must fail there. Loaded after test-12 (its core-cache and cap
 * stand-ins are in place).
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Admin-only generation / 1.3.0' );

/** Load a copy of a plugin class with one textual mutation, under a new name. */
function tp130_mutant( $file, $class, $suffix, $from, $to ) {
	$src = (string) file_get_contents( TP_AI_PATH . 'includes/' . $file );
	if ( false === strpos( $src, $from ) ) {
		return '';
	}
	$name = $class . '_' . $suffix;
	$src  = str_replace( array( 'class ' . $class . ' ', $from ), array( 'class ' . $name . ' ', $to ), $src );
	eval( '?>' . $src );
	return $name;
}

/** Engine traffic since the last reset: outbound HTTP calls + core-cache rebuilds + replies. */
function tp130_reset() {
	$GLOBALS['tp_http_calls'] = 0;
	$GLOBALS['tp130_replies'] = 0;
	foreach ( array_keys( (array) ( $GLOBALS['tp_transients'] ?? array() ) ) as $k ) {
		if ( 0 === strpos( $k, 'tpas_rt_' ) ) {
			delete_transient( $k );
		}
	}
}
add_action( 'tpas_reply', function () {
	$GLOBALS['tp130_replies']++;
} );

/* 47: the one id the central published-gate stand-in (test-09) lets through */
$GLOBALS['tp_posts'][47] = array(
	'type'    => 'post',
	'title'   => 'Kashan',
	'content' => 'Kashan is a city in Isfahan province, known for its historic houses, the Fin Garden and rosewater. Its population is about 300,000.',
);

update_option( TP_AI_Options::OPTION_KEY, array(
	'internal_engines'        => array(
		array( 'id' => 'eng_130', 'provider' => 'deepseek', 'api_key' => 'sk-130', 'model' => '', 'base_url' => '' ),
	),
	'internal_default_engine' => 'eng_130',
	'internal_engine'         => 'ai',
) );
TP_AI_Options::flush();
$GLOBALS['tp_http_override'] = array( 'code' => 200, 'body' => json_encode( array( 'choices' => array( array( 'message' => array( 'content' => 'Kashan summary.' ), 'finish_reason' => 'stop' ) ) ) ) );

/* ── بند ۱ — tpas_ai_generate: only an editor of the post (and the nonce) ── */

t( 'P5-D358: visitor requests are off by default', 0 === TP_AI_Options::defaults()['visitor_requests'] && ! TP_AI_Summary::visitor_requests() );

$hooks = (array) ( $GLOBALS['tp_hooks']['wp_ajax_tpas_ai_generate'] ?? array() );
TP_AI_Summary::init();
$hooks = (array) $GLOBALS['tp_hooks']['wp_ajax_tpas_ai_generate'];
t( 'P5-D358: the editor-only rule is hooked on tpas_ai_generate (priority 0, before the cap gate)', in_array( array( 'TP_AI_Summary', 'authorize' ), $hooks, true ) );
t( 'P5-D358: still no wp_ajax_nopriv_tpas_ai_generate', empty( $GLOBALS['tp_hooks']['wp_ajax_nopriv_tpas_ai_generate'] ) );

/** Who: guest | subscriber | other (editor of another post) | editor. Returns [reply, engine traffic]. */
$tp130_as = function ( $class, $who, $nonce = true ) {
	tp130_reset();
	$GLOBALS['tp_logged_in'] = 'guest' !== $who;
	$GLOBALS['tp_cannot']    = in_array( $who, array( 'guest', 'subscriber', 'other' ), true ) ? array( 'edit_post' => true ) : array();
	$post = array( 'lang' => 'en', 'type' => 'keypoints', 'post' => '47' );
	if ( ! $nonce ) {
		$post['nonce'] = 'bogus';
	}
	$r = tp_ajax( array( $class, 'handle_ajax' ), $post );
	$GLOBALS['tp_logged_in'] = true;
	$GLOBALS['tp_cannot']    = array();
	return array( $r, $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] );
};
$tp130_refused = function ( $class, $who, $nonce = true ) use ( $tp130_as ) {
	list( $r, $traffic ) = $tp130_as( $class, $who, $nonce );
	return is_array( $r ) && false === $r['ok'] && 403 === $r['status'] && 0 === $traffic;
};

$m1 = tp130_mutant( 'class-tp-ai-summary.php', 'TP_AI_Summary', 'M1', "|| ! current_user_can( 'edit_post', \$post ) ) {", ') {' );
t( 'P5-D358: guest -> 403 and zero engine calls', $tp130_refused( 'TP_AI_Summary', 'guest' ) );
t( 'P5-D358: subscriber (logged in, cannot edit the post) -> 403 and zero engine calls; mutant without edit_post generates',
	$tp130_refused( 'TP_AI_Summary', 'subscriber' ) && '' !== $m1 && ! $tp130_refused( $m1, 'subscriber' ) );
t( 'P5-D358: editor of the post with a bad nonce -> 403 and zero engine calls', $tp130_refused( 'TP_AI_Summary', 'editor', false ) );
list( $tp130_r, $tp130_t ) = $tp130_as( 'TP_AI_Summary', 'editor' );
t( 'P5-D358: editor of the post -> generated (one engine reply)', is_array( $tp130_r ) && true === $tp130_r['ok'] && $tp130_t >= 1 );

/* the option «visitor requests (later versions)» restores the 1.2.x logged-in path — and only it */
update_option( TP_AI_Options::OPTION_KEY, array_merge( get_option( TP_AI_Options::OPTION_KEY ), array( 'visitor_requests' => 1 ) ) );
TP_AI_Options::flush();
list( $tp130_r ) = $tp130_as( 'TP_AI_Summary', 'subscriber' );
list( $tp130_g ) = $tp130_as( 'TP_AI_Summary', 'guest' );
t( 'P5-D358: option on -> a logged-in visitor may ask again; a guest still may not (401)', is_array( $tp130_r ) && true === $tp130_r['ok'] && is_array( $tp130_g ) && 401 === $tp130_g['status'] );
update_option( TP_AI_Options::OPTION_KEY, array_merge( get_option( TP_AI_Options::OPTION_KEY ), array( 'visitor_requests' => 0 ) ) );
TP_AI_Options::flush();

/* ── بند ۲ — generation by the admin: metabox, bulk action, auto ────────── */

/* ToPersia Core's queue (P5-D290) — stand-ins with the same contract */
$GLOBALS['tp130_queue']       = array();
$GLOBALS['tp130_queue_ready'] = true;
if ( ! function_exists( 'topersia_queue_add' ) ) {
	function topersia_queue_ready() { return (bool) $GLOBALS['tp130_queue_ready']; }
	function topersia_queue_add( $hook, array $args = array(), $delay = 0 ) {
		foreach ( $GLOBALS['tp130_queue'] as $job ) {
			if ( $job[0] === $hook && $job[1] === $args ) {
				return 0; // pending duplicate
			}
		}
		$GLOBALS['tp130_queue'][] = array( $hook, $args );
		return count( $GLOBALS['tp130_queue'] );
	}
}
$tp130_opts = function ( array $more ) {
	update_option( TP_AI_Options::OPTION_KEY, array_merge( get_option( TP_AI_Options::OPTION_KEY ), $more ) );
	TP_AI_Options::flush();
};
$tp130_clear = function () {
	$GLOBALS['tp130_queue'] = array();
	foreach ( array_keys( (array) $GLOBALS['tp_transients'] ) as $k ) {
		if ( 0 === strpos( $k, 'tpas_admin_gen_' ) ) {
			delete_transient( $k );
		}
	}
	foreach ( TP_AI_Options::panel_types() as $type ) {
		delete_post_meta( 47, '_tpas_ai_' . $type . '_' . TP_AI_Generator::post_lang( 47 ) );
	}
};
$GLOBALS['tp123_used'] = 0; // the site cap stand-in from test-12: 100 a day
$tp130_lang = TP_AI_Generator::post_lang( 47 );

/* metabox: status of each type in the post's own language */
$tp130_clear();
update_post_meta( 47, '_tpas_ai_summary_' . $tp130_lang, array( 'text' => 'Made.', 'created' => time() ) );
t( 'P5-D358: metabox status - a stored output is «fresh», a missing one «none» (store read only, zero calls)',
	( function () use ( $tp130_lang ) {
		tp130_reset();
		return 'fresh' === TP_AI_Generator::status( 47, 'summary', $tp130_lang ) && 'none' === TP_AI_Generator::status( 47, 'plan', $tp130_lang ) && 0 === $GLOBALS['tp_http_calls'];
	} )() );
ob_start();
TP_AI_Admin::meta_box_html( get_post( 47 ) );
$tp130_box = ob_get_clean();
t( 'P5-D358: the «ToPersia AI» metabox lists every enabled type with its state and a button + its own nonce',
	3 === substr_count( $tp130_box, 'tpas-gen__go' ) && false !== strpos( $tp130_box, 'data-status="fresh"' ) && false !== strpos( $tp130_box, 'data-status="none"' )
	&& false !== strpos( $tp130_box, wp_create_nonce( 'tpas_admin_gen' ) ) );

/* metabox button (admin AJAX) */
$tp130_btn = function ( $class, $who, $nonce = 'tpas_admin_gen' ) {
	tp130_reset();
	$GLOBALS['tp_cannot'] = 'editor' === $who ? array() : array( 'edit_post' => true );
	$GLOBALS['tp_ajax']   = null;
	$_POST = array( 'nonce' => wp_create_nonce( $nonce ), 'post' => '47', 'type' => 'plan' );
	try {
		call_user_func( array( $class, 'ajax_generate' ) );
	} catch ( TP_Test_Ajax_Exit $e ) {}
	$GLOBALS['tp_cannot'] = array();
	return array( $GLOBALS['tp_ajax'], $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] );
};
list( $r_ed, $t_ed )   = $tp130_btn( 'TP_AI_Admin', 'editor' );
list( $r_sub, $t_sub ) = $tp130_btn( 'TP_AI_Admin', 'subscriber' );
list( $r_bad )         = $tp130_btn( 'TP_AI_Admin', 'editor', 'tpas_ai' );
$m2 = tp130_mutant( 'class-tp-ai-admin.php', 'TP_AI_Admin', 'M2', "if ( ! \$post || ! current_user_can( 'edit_post', \$post ) ) {", 'if ( ! $post ) {' );
list( $r_m2 ) = '' !== $m2 ? $tp130_btn( $m2, 'subscriber' ) : array( null );
t( 'P5-D358: metabox button - editor of the post -> generated (engine reply, status fresh)', is_array( $r_ed ) && true === $r_ed['ok'] && $t_ed >= 1 );
t( 'P5-D358: metabox button - subscriber -> 403, zero calls; mutant without edit_post generates',
	is_array( $r_sub ) && 403 === $r_sub['status'] && 0 === $t_sub && is_array( $r_m2 ) && true === $r_m2['ok'] );
t( 'P5-D358: metabox button - the visitor nonce (tpas_ai) does not open it', null === $r_bad || ( is_array( $r_bad ) && false === $r_bad['ok'] ) );

/* bulk action: in the queue, each in its own language, fresh skipped, daily cap */
$tp130_opts( array( 'admin_daily_cap' => 1 ) );
$tp130_clear();
update_post_meta( 47, '_tpas_ai_summary_' . $tp130_lang, array( 'text' => 'Made.', 'created' => time() ) );
tp130_reset();
$tp130_url = TP_AI_Admin::bulk_handle( 'edit.php', 'tpas_generate', array( 47, 43 ) );
$tp130_q   = $GLOBALS['tp130_queue'];
t( 'P5-D358: bulk action -> queued, not run: one job (cap 1), fresh summary skipped, the draft skipped, zero engine calls',
	1 === count( $tp130_q ) && TP_AI_Generator::JOB === $tp130_q[0][0] && 47 === $tp130_q[0][1][0] && 'summary' !== $tp130_q[0][1][1]
	&& 0 === $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies']
	&& false !== strpos( $tp130_url, 'tpas_q=1' ) && false !== strpos( $tp130_url, 'tpas_f=1' ) && false !== strpos( $tp130_url, 'tpas_c=1' ) && false !== strpos( $tp130_url, 'tpas_s=1' ) );
t( 'P5-D358: bulk job carries the post\'s own language', $tp130_lang === $tp130_q[0][1][2] );

$m3 = tp130_mutant( 'class-tp-ai-generator.php', 'TP_AI_Generator', 'M3', 'if ( $left <= 0 ) {', 'if ( false ) {' );
$tp130_clear();
update_post_meta( 47, '_tpas_ai_summary_' . $tp130_lang, array( 'text' => 'Made.', 'created' => time() ) );
call_user_func( array( $m3, 'enqueue' ), array( 47 ) );
$tp130_m3 = count( $GLOBALS['tp130_queue'] );
$m4 = tp130_mutant( 'class-tp-ai-generator.php', 'TP_AI_Generator', 'M4', "if ( 'fresh' === self::status( \$post_id, \$type, \$lang ) ) {", 'if ( false ) {' );
$tp130_opts( array( 'admin_daily_cap' => 10 ) );
$tp130_clear();
update_post_meta( 47, '_tpas_ai_summary_' . $tp130_lang, array( 'text' => 'Made.', 'created' => time() ) );
call_user_func( array( $m4, 'enqueue' ), array( 47 ) );
$tp130_m4 = $GLOBALS['tp130_queue'];
$tp130_clear();
update_post_meta( 47, '_tpas_ai_summary_' . $tp130_lang, array( 'text' => 'Made.', 'created' => time() ) );
TP_AI_Generator::enqueue( array( 47 ) );
t( 'P5-D358: mutants - without the cap 2 jobs are queued over a cap of 1; without the freshness check the fresh summary is queued',
	2 === $tp130_m3 && in_array( 'summary', array_map( function ( $j ) { return $j[1][1]; }, $tp130_m4 ), true )
	&& ! in_array( 'summary', array_map( function ( $j ) { return $j[1][1]; }, $GLOBALS['tp130_queue'] ), true ) );

/* the queue job honours the cap at run time too */
$tp130_opts( array( 'admin_daily_cap' => 0 ) );
tp130_reset();
TP_AI_Generator::run_job( 47, 'plan', $tp130_lang );
t( 'P5-D358: queue job over the daily cap -> zero engine calls', 0 === $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] );
$tp130_opts( array( 'admin_daily_cap' => 10 ) );
$tp130_clear();
tp130_reset();
TP_AI_Generator::run_job( 47, 'plan', $tp130_lang );
t( 'P5-D358: queue job under the cap -> generated and counted once', $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] >= 1 && 1 === TP_AI_Generator::used_today() );

/* site cap of ToPersia Render (work C) holds for the admin path as well */
$GLOBALS['tp123_used'] = 100;
tp130_reset();
$tp130_site = TP_AI_Generator::generate( 47, 'keypoints', $tp130_lang, true );
$GLOBALS['tp123_used'] = 0;
t( 'P5-D358: site daily cap full -> the admin path makes no call either', false === $tp130_site['ok'] && 'cap' === ( $tp130_site['skipped'] ?? '' ) && 0 === $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] );

/* language: an unpublished translation has no summary */
tp130_reset();
$tp130_draft = TP_AI_Generator::generate( 43, 'summary', 'en', true );
t( 'P5-D358: an unpublished post (translation) gets no summary, zero calls', false === $tp130_draft['ok'] && 0 === $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] );

/* auto-generate: off by default -> queue empty; on -> the same queue */
$tp130_clear();
t( 'P5-D358: auto-generate is off by default', 0 === TP_AI_Options::defaults()['auto_generate'] );
TP_AI_Generator::maybe_auto( 47, get_post( 47 ) );
$tp130_off = count( $GLOBALS['tp130_queue'] );
$m5 = tp130_mutant( 'class-tp-ai-generator.php', 'TP_AI_Generator', 'M5', "if ( ! TP_AI_Options::just_flag( 'auto_generate' ) ) {", 'if ( false ) {' );
call_user_func( array( $m5, 'maybe_auto' ), 47, get_post( 47 ) );
$tp130_m5 = count( $GLOBALS['tp130_queue'] );
t( 'P5-D358: auto-generate off -> queue empty after a save; mutant without the switch queues', 0 === $tp130_off && $tp130_m5 > 0 );
$tp130_clear();
$tp130_opts( array( 'auto_generate' => 1 ) );
tp130_reset();
TP_AI_Generator::maybe_auto( 47, get_post( 47 ) );
t( 'P5-D358: auto-generate on -> jobs in the same queue, never run inline', count( $GLOBALS['tp130_queue'] ) > 0 && 0 === $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] );
$tp130_opts( array( 'auto_generate' => 0 ) );
$tp130_clear();

/* ── بند ۳ — tpas_get_summary(): stored only, never an outside call ──────── */

/** A renamed copy of tpas_get_summary() with one textual mutation. */
function tp130_fn_mutant( $suffix, $from, $to ) {
	$src = (string) file_get_contents( TP_AI_PATH . 'includes/class-tp-ai-generator.php' );
	if ( ! preg_match( '/\tfunction tpas_get_summary\(.*?\n\t}\n/s', $src, $m ) || false === strpos( $m[0], $from ) ) {
		return '';
	}
	$name = 'tpas_get_summary_' . $suffix;
	eval( str_replace( array( 'function tpas_get_summary(', $from ), array( 'function ' . $name . '(', $to ), $m[0] ) );
	return $name;
}

$tp130_opts( array( 'visitor_requests' => 1, 'auto_generate' => 1, 'admin_daily_cap' => 10 ) ); // every door open: still nothing may be made
$tp130_clear();
update_post_meta( 47, '_tpas_ai_summary_' . $tp130_lang, array( 'text' => 'Stored **summary**.', 'created' => $tp130_when = time() - 60 ) );
tp130_reset();
$GLOBALS['tp_logged_in'] = false; // a guest's page view
$s_ok   = tpas_get_summary( 47, 'summary', $tp130_lang );
$s_own  = tpas_get_summary( 47, 'summary' );
$s_none = tpas_get_summary( 47, 'plan', $tp130_lang );
$s_bad  = tpas_get_summary( 47, 'evil', $tp130_lang );
$s_dft  = tpas_get_summary( 43, 'summary', $tp130_lang );
$GLOBALS['tp_logged_in'] = true;
$tp130_traffic = $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] + count( $GLOBALS['tp130_queue'] );
t( 'P5-D358: tpas_get_summary() is defined', function_exists( 'tpas_get_summary' ) );
t( 'P5-D358: tpas_get_summary() returns the stored output (text, html, stale, updated); \'\' lang = the post\'s own',
	is_array( $s_ok ) && 'Stored **summary**.' === $s_ok['text'] && false !== strpos( $s_ok['html'], '<strong>' ) && false === $s_ok['stale'] && $tp130_when === $s_ok['updated'] && $s_ok === $s_own );
t( 'P5-D358: tpas_get_summary() - missing output, unknown type, unpublished post -> null', null === $s_none && null === $s_bad && null === $s_dft );
$m6 = tp130_fn_mutant( 'M6', "if ( ! \$cached || '' === trim( (string) ( \$cached['text'] ?? '' ) ) ) {\n\t\t\treturn null;", "if ( ! \$cached || '' === trim( (string) ( \$cached['text'] ?? '' ) ) ) {\n\t\t\tTP_AI_Summary::produce( \$post_id, \$type, \$lang );\n\t\t\treturn null;" );
tp130_reset();
if ( '' !== $m6 ) {
	call_user_func( $m6, 47, 'plan', $tp130_lang );
}
t( 'P5-D358: tpas_get_summary() makes zero engine calls and queues nothing, every option on; mutant that builds a missing output calls out',
	0 === $tp130_traffic && '' !== $m6 && $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] >= 1 );
$tp130_opts( array( 'visitor_requests' => 0, 'auto_generate' => 0 ) );
$tp130_clear();
