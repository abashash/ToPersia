<?php
/**
 * گزارش مختصات — فقط خواندنی  (فقط محلی؛ هیچ نوشتنی در پایگاه‌داده یا پرونده)  دستهٔ ۸
 *
 *   wp eval-file scripts/local/tp-coords-report.php
 *
 * ۱) منتشرشده‌های هر نوع tp_ بی geo_coordinates معتبر: خالی · «0» (یا صفر-صفر) · ناخوانا · بیرون از ایران.
 * ۲) برای سمنان و شهرهای بی مختصات: نامزد مختصات از کش Overpass (uploads/tiles/boundaries-cache/overpass-iran.json).
 *    کش فقط رابطه/راه با `center` دارد، نه گرهٔ place — پس «شناسهٔ گره» نمی‌دهد؛ شناسهٔ رابطه/راه می‌دهد.
 *    پیشنهاد فقط وقتی درون GeoJSON استان والد باشد.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) { exit; }
require_once dirname( __DIR__ ) . '/lib/tp-boundaries-lib.php';
global $wpdb;

$IR = array( 'lat' => array( 25.0, 39.8 ), 'lon' => array( 44.0, 63.4 ) );
$classify = function ( $v ) use ( $IR ) {
	$v = trim( (string) $v );
	if ( '' === $v ) { return 'empty'; }
	if ( ! preg_match( '/^(-?\d+(?:\.\d+)?)\s*[,،]\s*(-?\d+(?:\.\d+)?)$/u', $v, $m ) ) { return ( '0' === $v ) ? 'zero' : 'malformed'; }
	$la = (float) $m[1]; $lo = (float) $m[2];
	if ( 0.0 === $la && 0.0 === $lo ) { return 'zero'; }
	if ( $la < $IR['lat'][0] || $la > $IR['lat'][1] || $lo < $IR['lon'][0] || $lo > $IR['lon'][1] ) { return 'outside'; }
	return 'ok';
};

$rows = $wpdb->get_results( "SELECT p.ID, p.post_type, p.post_title, (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'geo_coordinates' LIMIT 1) ll, (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'entity_uid' LIMIT 1) uid FROM {$wpdb->posts} p WHERE p.post_status = 'publish' AND p.post_type LIKE 'tp\\_%' ORDER BY p.post_type, p.ID" );
$tab = array(); $bad = array();
foreach ( $rows as $r ) {
	$c = $classify( $r->ll );
	if ( ! isset( $tab[ $r->post_type ] ) ) { $tab[ $r->post_type ] = array( 'total' => 0, 'ok' => 0, 'empty' => 0, 'zero' => 0, 'malformed' => 0, 'outside' => 0 ); }
	$tab[ $r->post_type ]['total']++; $tab[ $r->post_type ][ $c ]++;
	if ( 'ok' !== $c ) { $bad[] = array( $r, $c ); }
}
echo "ردیف | نوع | منتشرشده | معتبر | خالی | «0» | ناخوانا | بیرون از ایران\n";
$sum = array( 'total' => 0, 'ok' => 0, 'empty' => 0, 'zero' => 0, 'malformed' => 0, 'outside' => 0 );
foreach ( $tab as $t => $c ) { echo "$t | {$c['total']} | {$c['ok']} | {$c['empty']} | {$c['zero']} | {$c['malformed']} | {$c['outside']}\n"; foreach ( $sum as $k => $_ ) { $sum[ $k ] += $c[ $k ]; } }
echo "جمع | {$sum['total']} | {$sum['ok']} | {$sum['empty']} | {$sum['zero']} | {$sum['malformed']} | {$sum['outside']}\n\n";

/* بیرون از ایران / ناخوانا — فهرست کامل (کم‌اند) */
foreach ( $bad as $b ) { if ( in_array( $b[1], array( 'outside', 'malformed', 'zero' ), true ) ) { echo "غیرعادی: #{$b[0]->ID} {$b[0]->post_type} «{$b[0]->post_title}» = " . var_export( $b[0]->ll, true ) . " [{$b[1]}]\n"; } }

/* نامزدها */
$up    = wp_get_upload_dir();
$cache = json_decode( (string) @file_get_contents( trailingslashit( $up['basedir'] ) . 'tiles/boundaries-cache/overpass-iran.json' ), true );
$els   = isset( $cache['elements'] ) ? $cache['elements'] : array();
$bdir  = trailingslashit( $up['basedir'] ) . 'tiles/boundaries/';

$csv = array();
$fh  = fopen( dirname( __DIR__, 2 ) . '/reference/osm-boundary-map.csv', 'r' );
$hd  = fgetcsv( $fh, 0, ',', '"', '\\' );
while ( ( $r = fgetcsv( $fh, 0, ',', '"', '\\' ) ) ) { if ( count( $r ) === count( $hd ) ) { $csv[ $r[0] ] = array_combine( $hd, $r ); } }
fclose( $fh );

$prov_geo = array();
foreach ( $rows as $r ) { if ( 'tp_province' === $r->post_type ) { $f = $bdir . tpb_key( $r->uid ) . '.geojson'; if ( is_file( $f ) ) { $prov_geo[ tpb_norm( $r->post_title ) ] = json_decode( file_get_contents( $f ), true ); } } }

echo "\nنامزدهای مختصات (کش Overpass؛ رابطه/راه با center — گرهٔ place در کش نیست)\n";
echo "نوع | عنوان | استان والد | نامزد (OSM) | lat,lon | درون استان والد؟ | شمار هم‌نام\n";
$n = 0;
foreach ( $rows as $r ) {
	$semnan = ( false !== mb_strpos( tpb_norm( $r->post_title ), 'سمنان' ) );
	$need   = ( 'tp_city' === $r->post_type && 'empty' === $classify( $r->ll ) && isset( $csv[ $r->uid ] ) && 'no-coords' === $csv[ $r->uid ]['city_point'] && 'auto' === $csv[ $r->uid ]['status'] ) || ( $semnan && in_array( $r->post_type, array( 'tp_city', 'tp_province' ), true ) );
	if ( ! $need ) { continue; }
	$n++;
	$parent = isset( $csv[ $r->uid ] ) ? $csv[ $r->uid ]['parent_province'] : '';
	$pg     = isset( $prov_geo[ tpb_norm( $parent ) ] ) ? $prov_geo[ tpb_norm( $parent ) ] : null;
	$cands  = tpb_candidates( $r->post_title, $els );
	/* تطبیق مرز (auto در CSV) ← همان عنصر، نه حدس نام */
	if ( isset( $csv[ $r->uid ] ) && '' !== $csv[ $r->uid ]['osm_id'] ) {
		foreach ( $els as $e ) { if ( (string) $e['id'] === $csv[ $r->uid ]['osm_id'] && $e['type'] === $csv[ $r->uid ]['osm_type'] && tpb_el_center( $e ) ) { $cands = array( array( 'el' => $e, 'class' => 'csv-match', 'center' => tpb_el_center( $e ) ) ); break; } }
	}
	$best   = null; $inside = '—';
	foreach ( $cands as $c ) {
		$in = $pg ? tpb_point_in_geojson( $c['center'][0], $c['center'][1], $pg ) : null;
		if ( true === $in ) { $best = $c; $inside = 'بله'; break; }
		if ( null === $best ) { $best = $c; $inside = $pg ? 'خیر' : 'استان والد نامعلوم'; }
	}
	if ( 'tp_province' === $r->post_type ) { $inside = '(خودِ استان)'; }
	echo "{$r->post_type} | {$r->post_title} (#{$r->ID}) [" . $classify( $r->ll ) . "] | " . ( $parent ?: '—' ) . " | "
		. ( $best ? $best['el']['type'] . ' ' . $best['el']['id'] . ' (' . $best['class'] . ')' : 'نامزدی در کش نیست' ) . " | "
		. ( $best ? sprintf( '%.5f,%.5f', $best['center'][1], $best['center'][0] ) : '—' ) . " | $inside | " . count( $cands ) . "\n";
}
echo "\nجمع ردیف‌های نامزد: $n\n";
