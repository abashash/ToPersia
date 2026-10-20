<?php
/**
 * 1.4.0 — P5-D382: automatic generation after publish / save by an editor.
 *
 * One queue job per (post, language) for all three types; nothing queued when
 * every type is fresh; a full cap moves the job to tomorrow; an engine error
 * retries with a growing delay; no public path. Each claim has a mutant that
 * must fail. Loaded after test-13 (queue stand-in, post 47, engine stub).
 *
 * @package TP_AI_Summarizer_Tests
 */

group( 'Auto-generation / 1.4.0' );

$tp140_opts = function ( array $more ) {
	update_option( TP_AI_Options::OPTION_KEY, array_merge( (array) get_option( TP_AI_Options::OPTION_KEY ), $more ) );
	TP_AI_Options::flush();
};
$tp140_clear = function () {
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
$tp140_ok_body = json_encode( array( 'choices' => array( array( 'message' => array( 'content' => 'Kashan text.' ), 'finish_reason' => 'stop' ) ) ) );
$tp140_lang    = TP_AI_Generator::post_lang( 47 );
$GLOBALS['tp123_used'] = 0;
$tp140_opts( array( 'auto_generate' => 1, 'visitor_requests' => 0, 'admin_daily_cap' => 50, 'internal_engine' => 'ai' ) );
$GLOBALS['tp130_queue_ready'] = true;
$GLOBALS['tp_http_override']  = array( 'code' => 200, 'body' => $tp140_ok_body );

/* one job for the post and its language, all three types, never inline */
$tp140_clear();
tp130_reset();
TP_AI_Generator::maybe_auto( 47, get_post( 47 ) );
$q = $GLOBALS['tp130_queue'];
t( 'P5-D382: save by an editor -> exactly ONE job (post, lang) for all three types, nothing run inline',
	1 === count( $q ) && TP_AI_Generator::JOB_POST === $q[0][0] && array( 47, $tp140_lang, 0 ) === $q[0][1] && 0 === $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'], json_encode( $q ) );

TP_AI_Generator::maybe_auto( 47, get_post( 47 ) );
t( 'P5-D382: a second save while the job waits -> still one job', 1 === count( $GLOBALS['tp130_queue'] ) );

/* only a user who may edit the post */
$tp140_clear();
$GLOBALS['tp_cannot'] = array( 'edit_post' => true );
TP_AI_Generator::maybe_auto( 47, get_post( 47 ) );
$tp140_none = count( $GLOBALS['tp130_queue'] );
$m1 = tp130_mutant( 'class-tp-ai-generator.php', 'TP_AI_Generator', 'A1', "if ( ! current_user_can( 'edit_post', \$post_id ) || ", 'if ( ' );
if ( '' !== $m1 ) {
	call_user_func( array( $m1, 'maybe_auto' ), 47, get_post( 47 ) );
}
$GLOBALS['tp_cannot'] = array();
t( 'P5-D382: a user without edit_post queues nothing; mutant without the check queues', 0 === $tp140_none && '' !== $m1 && 1 === count( $GLOBALS['tp130_queue'] ) );

/* run the job: all three types made, then fresh -> no further job */
$tp140_clear();
tp130_reset();
$res = TP_AI_Generator::run_post_job( 47, $tp140_lang, 0 );
t( 'P5-D382: the job makes summary · keypoints · plan', array( 'summary' => 'ok', 'keypoints' => 'ok', 'plan' => 'ok' ) === $res, json_encode( $res ) );
/* the test core cache keeps nothing: «made» = a stored output in the post's language (same as test-13) */
foreach ( TP_AI_Options::panel_types() as $tp140_t ) {
	update_post_meta( 47, '_tpas_ai_' . $tp140_t . '_' . $tp140_lang, array( 'text' => 'Made ' . $tp140_t . '.', 'created' => time() ) );
}
$GLOBALS['tp130_queue'] = array();
TP_AI_Generator::maybe_auto( 47, get_post( 47 ) );
$tp140_fresh_q = count( $GLOBALS['tp130_queue'] );
$m2 = tp130_mutant( 'class-tp-ai-generator.php', 'TP_AI_Generator', 'A2', "if ( 'fresh' !== self::status( \$post_id, \$type, \$lang ) ) {", 'if ( true ) {' );
if ( '' !== $m2 ) {
	call_user_func( array( $m2, 'maybe_auto' ), 47, get_post( 47 ) );
}
t( 'P5-D382: source text unchanged (all fresh) -> no job; mutant ignoring freshness queues', 0 === $tp140_fresh_q && '' !== $m2 && count( $GLOBALS['tp130_queue'] ) > 0 );
tp130_reset();
$again = TP_AI_Generator::run_post_job( 47, $tp140_lang, 0 );
t( 'P5-D382: a job on fresh outputs calls no engine', array( 'summary' => 'fresh', 'keypoints' => 'fresh', 'plan' => 'fresh' ) === $again && 0 === $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'], json_encode( $again ) );

/* cap full -> moved to tomorrow (delay > 0, same attempt) */
$tp140_clear();
$tp140_opts( array( 'admin_daily_cap' => 0 ) );
TP_AI_Generator::run_post_job( 47, $tp140_lang, 2 );
$q = $GLOBALS['tp130_queue'];
t( 'P5-D382: daily cap full -> the job moves to tomorrow (same attempt, delay 60 s .. 1 day)',
	1 === count( $q ) && array( 47, $tp140_lang, 2 ) === $q[0][1] && $q[0][2] >= 60 && $q[0][2] <= DAY_IN_SECONDS + 700, json_encode( $q ) );
$tp140_opts( array( 'admin_daily_cap' => 50 ) );

/* engine error -> retry later with a growing delay, up to RETRIES.
   The test core cache never calls an engine, so the error is injected: a copy whose produce() fails (F),
   and the same copy without the retry branch (M3) must not requeue. */
$tp140_fail = "\$made = array( 'ok' => false, 'message' => 'engine down' );";
$f1 = tp130_mutant( 'class-tp-ai-generator.php', 'TP_AI_Generator', 'F1', '$made = TP_AI_Summary::produce( $post_id, $type, $lang );', $tp140_fail );
$tp140_clear();
tp130_reset();
if ( '' !== $f1 ) {
	call_user_func( array( $f1, 'run_post_job' ), 47, $tp140_lang, 1 );
}
$q = $GLOBALS['tp130_queue'];
t( 'P5-D382: engine error -> retried later (attempt+1, delay 1800 s)', '' !== $f1 && 1 === count( $q ) && array( 47, $tp140_lang, 2 ) === $q[0][1] && 1800 === $q[0][2], json_encode( $q ) );
$GLOBALS['tp130_queue'] = array();
call_user_func( array( $f1, 'run_post_job' ), 47, $tp140_lang, TP_AI_Generator::RETRIES );
$tp140_stop = count( $GLOBALS['tp130_queue'] );
$src = (string) file_get_contents( TP_AI_PATH . 'includes/class-tp-ai-generator.php' );
$src = str_replace( array( 'class TP_AI_Generator ', '$made = TP_AI_Summary::produce( $post_id, $type, $lang );', "} elseif ( in_array( 'error', \$out, true ) && \$attempt < self::RETRIES ) {" ), array( 'class TP_AI_Generator_R140 ', $tp140_fail, '} elseif ( false ) {' ), $src );
eval( '?>' . $src );
$GLOBALS['tp130_queue'] = array();
TP_AI_Generator_R140::run_post_job( 47, $tp140_lang, 1 );
t( 'P5-D382: after RETRIES the job stops (no endless loop); mutant without retry never requeues', 0 === $tp140_stop && 0 === count( $GLOBALS['tp130_queue'] ) );
/* no queue -> nothing at all (a save never waits for an engine) */
$tp140_clear();
$GLOBALS['tp130_queue_ready'] = false;
tp130_reset();
TP_AI_Generator::maybe_auto( 47, get_post( 47 ) );
$GLOBALS['tp130_queue_ready'] = true;
t( 'P5-D382: without a queue a save generates nothing inline', 0 === count( $GLOBALS['tp130_queue'] ) && 0 === $GLOBALS['tp_http_calls'] + $GLOBALS['tp130_replies'] );

/* no public path */
t( 'P5-D382: no public start — no wp_ajax_nopriv_ and no REST route for generation',
	empty( $GLOBALS['tp_hooks']['wp_ajax_nopriv_tpas_ai_generate'] ) && empty( $GLOBALS['tp_hooks'][ 'wp_ajax_nopriv_' . TP_AI_Generator::JOB_POST ] )
	&& false === strpos( (string) file_get_contents( TP_AI_PATH . 'includes/class-tp-ai-generator.php' ), 'register_rest_route' ) );

/* migration: an old install that saved auto_generate = 0 is switched on once */
delete_option( TP_AI_Generator::AUTO_MIGRATED );
$tp140_opts( array( 'auto_generate' => 0 ) );
TP_AI_Generator::migrate_auto();
TP_AI_Options::flush();
$tp140_on = TP_AI_Options::just_flag( 'auto_generate' );
$tp140_opts( array( 'auto_generate' => 0 ) );
TP_AI_Generator::migrate_auto();
TP_AI_Options::flush();
t( 'P5-D382: migration switches a saved «off» on once; a later «off» by the admin stays off', $tp140_on && ! TP_AI_Options::just_flag( 'auto_generate' ) );
$tp140_clear();
