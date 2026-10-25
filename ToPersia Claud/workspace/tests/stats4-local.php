<?php
/**
 * چهار خانهٔ ثابت آمار روی وردپرس واقعی — P5-D395 (فقط topersia.local؛ فقط خواندنی)
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/stats4-local.php
 *
 * ۱. هر پست منتشرشدهٔ هر ۴۱ نوع: [tp_stats] دقیقاً همان ۴ خانهٔ STAT_CELLS به همان ترتیب؛ «—» فقط با aria-label.
 * ۲. «—» هرگز در JSON-LD (topersia_schema_build)، ورودی هوش مصنوعی (TP_AI_Context)، نمایهٔ جستجو (topersia_search_fold_post)
 *    و متای description صفحهٔ واقعی — روی پستی که خانهٔ «—» دارد. جهش: همان فیلدهای تهی با صافی get_post_metadata
 *    «—» برگردانند (یعنی «—» در داده) ← آزمون باید بیفتد.
 * ۳. بستهٔ درون‌ریزی: فیلدهای بخش (ج) الزامی‌اند (فقط پست تازه)؛ نوع بی فیلد کوتاه الزامی تازه ندارد.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$GLOBALS['s4'] = array( 0, 0 );
function s4( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['s4'][0]++; echo "  ✅ $label\n"; } else { $GLOBALS['s4'][1]++; echo "  ❌ $label   ← $note\n"; } }
echo "چهار خانهٔ ثابت آمار — P5-D395\n";

$cells = topersia_g_stat_cells();
$lang  = apply_filters( 'wpml_default_language', 'fa' );
$bad   = array();
$n     = 0;
$dash  = array();   /* پست‌هایی با دست‌کم یک «—» برای بند ۲ */

foreach ( $cells as $type => $list ) {
	$want = array_column( $list, 'key' );
	foreach ( get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false, 'lang' => $lang ) ) as $pid ) {
		$html = do_shortcode( '[tp_stats id="' . $pid . '"]' );
		preg_match_all( '~<div class="tp-stat( tp-stat--none)?" role="listitem" data-tp-k="([^"]+)">(.*?)</div>~su', $html, $m );
		$n++;
		if ( $want !== $m[2] || false === strpos( $html, 'data-tp-n="4"' ) ) {
			$bad[] = $type . '#' . $pid . ' ' . implode( ',', $m[2] );
		}
		foreach ( $m[1] as $i => $none ) {
			if ( '' !== $none && false === strpos( $m[3][ $i ], 'aria-label="نامشخص">—</b>' ) ) {
				$bad[] = $type . '#' . $pid . ' ' . $m[2][ $i ] . ' بی aria-label';
			}
			if ( '' !== $none ) {
				$dash[ $pid ][] = $m[2][ $i ];
			}
		}
	}
}
s4( "هر {$n} پست منتشرشدهٔ ۴۱ نوع: دقیقاً همان ۴ خانهٔ STAT_CELLS به همان ترتیب، «—» با aria-label «نامشخص»", $n > 100 && ! $bad, implode( ' · ', array_slice( $bad, 0, 6 ) ) );

/* ── بند ۲: «—» بیرون از [tp_stats] نه ── */
$leaks = function ( $pid ) {
	$out = array();
	$walk = function ( $v ) use ( &$walk, &$out ) {
		if ( is_array( $v ) ) { foreach ( $v as $x ) { $walk( $x ); } return; }
		if ( is_string( $v ) && '—' === trim( $v ) ) { $out['jsonld'] = 1; }
	};
	$walk( function_exists( 'topersia_schema_build' ) ? topersia_schema_build( $pid ) : array() );
	if ( class_exists( 'TP_AI_Context' ) ) {
		$block = (string) TP_AI_Context::structured_block( (array) TP_AI_Context::build( $pid ) );
		if ( preg_match( '/[:：]\s*—\s*$/mu', $block ) || preg_match( '/^\s*—\s*$/mu', $block ) ) { $out['ai'] = 1; }
	}
	if ( function_exists( 'topersia_search_fold_post' ) && preg_match( '/(^|[\s|])—([\s|]|$)/u', (string) topersia_search_fold_post( $pid ) ) ) { $out['search'] = 1; }
	return array_keys( $out );
};

/* پستی با بیشترین «—» که JSON-LD می‌گیرد (جاذبه/مکان) */
uasort( $dash, function ( $a, $b ) { return count( $b ) <=> count( $a ); } );
$pick = 0;
foreach ( $dash as $pid => $keys ) {
	if ( array_filter( $keys, function ( $k ) { return false === strpos( $k, ':' ) && 'capital' !== $k; } ) && topersia_schema_build( $pid ) ) { $pick = $pid; break; }
}
$fields = $pick ? array_values( array_filter( $dash[ $pick ], function ( $k ) { return false === strpos( $k, ':' ) && 'capital' !== $k; } ) ) : array();
$real   = $pick ? $leaks( $pick ) : array( 'no-post' );

$c = curl_init( get_permalink( $pick ) );
curl_setopt_array( $c, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_PROXY => '' ) );
$page = (string) curl_exec( $c );
curl_close( $c );
preg_match( '~<meta name="description" content="([^"]*)"~u', $page, $md );
$meta_ok = isset( $md[1] ) ? '—' !== trim( $md[1] ) && ! preg_match( '/(^\s*—|—\s*$)/u', $md[1] ) : true;
preg_match_all( '~<script type="application/ld\+json"[^>]*>(.*?)</script>~su', $page, $ld );
$ld_ok = ! preg_match( '/"\s*—\s*"/u', implode( '', $ld[1] ) );
s4( 'پست #' . $pick . ' (' . get_post_type( $pick ) . '، خانه‌های «—»: ' . implode( ' · ', $dash[ $pick ] ?? array() ) . '): «—» نه در JSON-LD، نه ورودی AI، نه نمایهٔ جستجو، نه متای description و JSON-LD صفحهٔ واقعی',
	$pick && ! $real && $meta_ok && $ld_ok && false !== strpos( $page, 'tp-stat--none' ), wp_json_encode( array( $real, $meta_ok, $ld_ok ) ) );

/* جهش: «—» در داده (فیلدهای تهی خانه‌ها + فیلدهایی که خروجی‌ها می‌خوانند: خلاصه، متا، جمعیت، مساحت) ← آشکارساز باید ببیند */
$mut_keys = array_merge( $fields, array( 'short_summary', 'meta_description', 'population', 'area_km2' ) );
$mut = function ( $v, $obj, $key, $single ) use ( $pick, $mut_keys ) {
	return (int) $obj === (int) $pick && in_array( $key, $mut_keys, true ) ? ( $single ? '—' : array( '—' ) ) : $v;
};
add_filter( 'get_post_metadata', $mut, 1, 4 );
wp_cache_flush();
$mutated = $leaks( $pick );
remove_filter( 'get_post_metadata', $mut, 1 );
wp_cache_flush();
s4( 'جهش: «—» در داده (' . implode( ' · ', $mut_keys ) . ') ← آشکارساز نشت را در ' . implode( ' · ', $mutated ) . ' می‌بیند', $fields && count( $mutated ) >= 2, '' );

/* ── بند ۳: الزامی در بستهٔ درون‌ریزی ── */
$req_bad = array();
foreach ( $cells as $type => $list ) {
	$schema = topersia_pack_schema( $type );
	foreach ( $list as $cell ) {
		$fld = topersia_g_stat_field_of( $cell['key'] );
		if ( $cell['req'] && '' !== $fld && ( ! isset( $schema['fields'][ $fld ] ) || empty( $schema['fields'][ $fld ]['required'] ) ) ) {
			$req_bad[] = $type . '.' . $cell['key'];
		}
		if ( ! $cell['req'] && '' !== $fld && isset( $schema['fields'][ $fld ] ) && ! empty( $schema['fields'][ $fld ]['stat_required'] ) ) {
			$req_bad[] = $type . '.' . $cell['key'] . ' (الزامی بی علامت)';
		}
	}
}
$city = topersia_pack_schema( 'tp_city' );
s4( 'بستهٔ درون‌ریزی: هر فیلد «الزامی» STAT_CELLS در اسکیمای زندهٔ نوعش هست و required است (فقط پست تازه — topersia_pack_plan create)؛ elevation_m شهر الزامی نشد',
	! $req_bad && ! empty( $city['fields']['area_km2']['required'] ) && empty( $city['fields']['elevation_m']['required'] ), implode( ' · ', $req_bad ) );

$g = $GLOBALS['s4'];
echo "\n  گذشته: {$g[0]}   افتاده: {$g[1]}\n";
if ( $g[1] ) { exit( 1 ); }
