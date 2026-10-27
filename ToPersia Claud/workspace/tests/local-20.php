<?php
/**
 * دستهٔ ۲۰ روی وردپرس واقعی — نشانگر یک سطح پایین‌تر (P5-D387)، نشان (P5-D383)، «همه …» (P5-D384)  (فقط topersia.local؛ فقط خواندنی)
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/local-20.php
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }
$GLOBALS['l20'] = array( 0, 0 );
function t( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['l20'][0]++; echo "  ✅ $label\n"; } else { $GLOBALS['l20'][1]++; echo "  ❌ $label   ← $note\n"; } }
$uid = function ( $u ) { global $wpdb; return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='entity_uid' AND meta_value=%s LIMIT 1", $u ) ); };

echo "دستهٔ ۲۰ — محلی\n";
$prov = $uid( home_url( '/e/province/isfahan' ) );
$city = $uid( home_url( '/e/city/isfahan' ) );
$fams = function ( $fc ) { return array_count_values( array_map( function ( $f ) { return $f['properties']['k']; }, $fc['features'] ) ); };

/* P5-D387 */
$p = topersia_g_map_points_build( $prov );
$c = topersia_g_map_points_build( $city );
t( 'نشانگر استان اصفهان: فقط شهرها', $p['features'] && array( 'city' ) === array_keys( $fams( $p ) ), wp_json_encode( $fams( $p ) ) );
$kinds = array_keys( $fams( $c ) );
t( 'نشانگر شهر اصفهان: فقط جاذبه و طبیعت (نه محله، روستا، شهر)', ! array_diff( $kinds, array( 'attraction', 'nature' ) ), wp_json_encode( $fams( $c ) ) );
$vill_child = 0;
foreach ( $c['features'] as $f ) {
	$near = topersia_g_place_parents( (int) $f['properties']['id'], 0, true );
	$near = $near ? reset( $near ) : null;
	if ( $near && 'tp_village' === get_post_type( $near['id'] ) ) { $vill_child++; }
}
t( 'نشانگر شهر: هیچ جاذبه‌ای که والد نزدیکش روستاست', 0 === $vill_child, (string) $vill_child );
$vills = get_posts( array( 'post_type' => 'tp_village', 'post_status' => 'publish', 'numberposts' => 5, 'fields' => 'ids' ) );
$vok = true; $vnote = array();
foreach ( $vills as $v ) {
	$fc = topersia_g_map_points_build( $v );
	foreach ( $fc['features'] as $f ) {
		$near = topersia_g_place_parents( (int) $f['properties']['id'], 0, true );
		$near = $near ? reset( $near ) : null;
		if ( 'attraction' !== $f['properties']['k'] || ! $near || (int) topersia_g_default_id( $near['id'] ) !== (int) topersia_g_default_id( $v ) ) { $vok = false; }
	}
	$vnote[] = get_the_title( $v ) . ':' . count( $fc['features'] );
}
t( 'نشانگر روستا: فقط جاذبه‌های خودش (والد R01 نزدیک = همان روستا)', $vok, implode( ' · ', $vnote ) );
t( 'نشانگر: پیکربندی نقشه برای روستا نشانی نشانگر می‌گیرد', ! $vills || topersia_g_map_points_on( $vills[0] ) );

/* P5-D383 */
$cap = topersia_g_capital_id( $prov );
$pt  = topersia_g_routing_point( $prov );
$cp  = $cap ? topersia_geo_parse( topersia_g_text( $cap, 'geo_coordinates' ) ) : null;
t( 'نشان استان اصفهان ← مختصات مرکز استان', $cap && $pt && abs( $pt['lat'] - $cp['lat'] ) < 1e-6 && abs( $pt['lng'] - $cp['lng'] ) < 1e-6, wp_json_encode( array( $cap, $pt ) ) );
$links = topersia_g_routing_links( topersia_api( 'navigation' )['extra'], topersia_g_routing_target( $prov ) );
t( 'باکس مسیریابی استان: نیست (P5-D455)؛ نقطهٔ مرکز استان در داده می‌ماند', array() === $links && $pt, wp_json_encode( $links ) );

/* P5-D384 */
$GLOBALS['post'] = get_post( $city ); setup_postdata( $GLOBALS['post'] );
$html  = do_shortcode( '[tp_page]' );
$sl    = preg_match_all( '#<section id="tp-([a-z-]+)" class="tp-section">(?:(?!</section>).)*?<div class="tp-slider"#su', $html, $m ) ? $m[1] : array();
$miss  = array();
foreach ( $sl as $k ) {
	if ( false === strpos( $html, 'href="#tp-list-' . $k . '"' ) || false === strpos( $html, 'id="tp-list-' . $k . '"' ) ) { $miss[] = $k; }
}
t( 'شهر اصفهان: هر اسلایدر کارتی (' . count( $sl ) . ') دکمهٔ «همه …» و پنجرهٔ فهرست دارد', $sl && ! $miss, implode( ',', $miss ) );

$g = $GLOBALS['l20'];
echo "\n  گذشته: {$g[0]}   افتاده: {$g[1]}\n";
if ( $g[1] ) { exit( 1 ); }
