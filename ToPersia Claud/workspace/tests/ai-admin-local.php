<?php
/**
 * خلاصه‌ساز 1.3.0 · P5-D358 — دادهٔ موقت و سنجش سمت سرور برای tests/ai-admin-local.js  (فقط topersia.local)
 *
 *   wp eval-file tests/ai-admin-local.php setup      ← یک شهر منتشرشدهٔ موقت (متن کافی) · چاپ JSON
 *   wp eval-file tests/ai-admin-local.php state <id> ← خروجی‌های ذخیره‌شده، صف، مصرف امروز
 *   wp eval-file tests/ai-admin-local.php run-queue  ← فقط کار tpas_generate همان پست موقت را اجرا می‌کند
 *   wp eval-file tests/ai-admin-local.php cleanup <id> ← پست موقت، ردیف‌ها، کار صف و مصرف امروز به حالت پیش
 *
 * هیچ پست واقعی را نمی‌خواند و نمی‌نویسد.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

global $wpdb;
$act = isset( $args[0] ) ? $args[0] : ( isset( $GLOBALS['argv'][ count( $GLOBALS['argv'] ) - 1 ] ) ? $GLOBALS['argv'][ count( $GLOBALS['argv'] ) - 1 ] : '' );
$arg = isset( $args[1] ) ? absint( $args[1] ) : 0;

$pending = function ( $pid ) {
	if ( ! function_exists( 'as_get_scheduled_actions' ) ) { return array(); }
	$out = array();
	foreach ( as_get_scheduled_actions( array( 'hook' => 'tpas_generate', 'status' => ActionScheduler_Store::STATUS_PENDING, 'per_page' => -1 ), 'OBJECT' ) as $id => $a ) {
		$p = $a->get_args();
		if ( isset( $p[0] ) && (int) $p[0] === (int) $pid ) { $out[ $id ] = array( 'type' => $p[1], 'lang' => $p[2], 'group' => $a->get_group() ); }
	}
	return $out;
};

switch ( $act ) {
	case 'setup':
		$id = wp_insert_post( array( 'post_type' => 'tp_city', 'post_status' => 'publish', 'post_title' => 'شهر آزمون خلاصه‌ساز', 'post_name' => 'tp-ai-admin-test',
			'post_content' => 'این شهر آزمایشی برای سنجش دکمهٔ مدیر است. ' . str_repeat( 'متن نمونه دربارهٔ تاریخ، بازار، غذا و جاذبه‌های گردشگری این شهر. ', 12 ) ), true );
		echo wp_json_encode( array( 'id' => (int) $id, 'used' => (int) get_transient( 'tpas_admin_gen_' . TP_AI_Generator_Probe::day() ) ) );
		break;

	case 'state':
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT type,language,source FROM ' . TP_AI_DB::summaries_table() . ' WHERE post_id = %d ORDER BY type', $arg ), ARRAY_A );
		echo wp_json_encode( array( 'rows' => $rows, 'pending' => array_values( $pending( $arg ) ), 'used' => (int) TP_AI_Generator::used_today(), 'site' => (int) topersia_g_ai_site_used() ) );
		break;

	case 'run-queue':
		$n = 0;
		foreach ( $pending( $arg ) as $aid => $p ) { TP_AI_Generator::run_job( $arg, $p['type'], $p['lang'] ); $n++; as_unschedule_action( 'tpas_generate', array( $arg, $p['type'], $p['lang'] ) ); }
		echo wp_json_encode( array( 'ran' => $n ) );
		break;

	case 'cleanup':
		foreach ( $pending( $arg ) as $aid => $p ) { as_unschedule_action( 'tpas_generate', array( $arg, $p['type'], $p['lang'] ) ); }
		$wpdb->delete( TP_AI_DB::summaries_table(), array( 'post_id' => $arg ) );
		wp_delete_post( $arg, true );
		echo wp_json_encode( array( 'cleaned' => true, 'left' => $pending( $arg ), 'post' => (bool) get_post( $arg ) ) );
		break;

	default:
		fwrite( STDERR, "act?\n" ); exit( 2 );
}
echo "\n";

/** مصرف امروز با همان روز خلاصه‌ساز */
class TP_AI_Generator_Probe { static function day() { return function_exists( 'topersia_g_ai_day' ) ? topersia_g_ai_day() : wp_date( 'Y-m-d' ); } }
