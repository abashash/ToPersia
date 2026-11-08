<?php
/**
 * نقشۀ ایستای ۳۱ استان برای صفحۀ اول — دستۀ 46 · بخش ب (P5-D513).  بازتولیدپذیر؛ بی شبکه.
 *
 *   php scripts/tp-home-map-svg.php            ← plugin/topersia-render/assets/home/iran-provinces.svg
 *   php scripts/tp-home-map-svg.php --check    ← فقط می‌سنجد که پروندۀ ثبت‌شده همان خروجی است
 *
 * ورودی: همان GeoJSON مرز استان‌ها که `scripts/tp-boundaries.php build province` ساخته
 * (`uploads/tiles/boundaries/province__<نامک>.geojson`؛ منبع OpenStreetMap contributors، ODbL).
 * تصویر: هم‌فاصله با ضریب cos(عرض میانه)، ساده‌سازی داگلاس–پوکر روی پیکسل (همان tpb_dp)،
 * حلقۀ ریزتر از آستانه بیرون. خروجی فقط داده است — هر <path> با نامک استان؛ پیوند، رنگ و
 * aria در رندر (`topersia_g_home_map()`) گذاشته می‌شود تا استان بی‌صفحه بی‌پیوند بماند.
 * سقف حجم ۸۰KB (P5-D513)؛ بیشتر شد، آستانه بزرگ‌تر می‌شود.
 */

$root = dirname( __DIR__ );
$env  = json_decode( (string) file_get_contents( $root . '/tests/env.json' ), true );
$dir  = rtrim( str_replace( '\\', '/', (string) ( $env['wp'] ?? '' ) ), '/' ) . '/wp-content/uploads/tiles/boundaries/';
$out  = $root . '/plugin/topersia-render/assets/home/iran-provinces.svg';
$max  = 80 * 1024;
$W    = 1000;

if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
require_once __DIR__ . '/lib/tp-boundaries-lib.php';

$files = glob( $dir . 'province__*.geojson' );
sort( $files );
if ( 31 !== count( $files ) ) { fwrite( STDERR, 'GeoJSON استان‌ها ۳۱ نیست: ' . count( $files ) . " در $dir\n" ); exit( 2 ); }

/* مرز کل برای تصویر */
$geo = array(); $minx = $miny = INF; $maxx = $maxy = -INF;
foreach ( $files as $f ) {
	$slug = preg_replace( '/^province__|\.geojson$/', '', basename( $f ) );
	$j    = json_decode( (string) file_get_contents( $f ), true );
	$g    = $j['geometry'] ?? array();
	$polys = 'Polygon' === ( $g['type'] ?? '' ) ? array( $g['coordinates'] ) : (array) ( $g['coordinates'] ?? array() );
	foreach ( $polys as $poly ) { foreach ( $poly[0] as $p ) { $minx = min( $minx, $p[0] ); $maxx = max( $maxx, $p[0] ); $miny = min( $miny, $p[1] ); $maxy = max( $maxy, $p[1] ); } }
	$geo[ $slug ] = $polys;
}
$k   = cos( deg2rad( ( $miny + $maxy ) / 2 ) );
$sx  = $W / ( ( $maxx - $minx ) * $k );
$H   = (int) ceil( ( $maxy - $miny ) * $sx );
$xy  = function ( $p ) use ( $minx, $maxy, $k, $sx ) { return array( ( $p[0] - $minx ) * $k * $sx, ( $maxy - $p[1] ) * $sx ); };

$build = function ( $eps, $minarea ) use ( $geo, $xy, $W, $H ) {
	$paths = '';
	foreach ( $geo as $slug => $polys ) {
		$d = '';
		foreach ( $polys as $poly ) {   /* فقط حلقۀ بیرونی؛ سوراخ استان‌ها (تهران در البرز نیست) در این مقیاس دیده نمی‌شود */
			$pts = tpb_dp( array_map( $xy, $poly[0] ), $eps );
			$a = 0; $n = count( $pts );
			for ( $i = 0; $i < $n; $i++ ) { $q = $pts[ ( $i + 1 ) % $n ]; $a += $pts[ $i ][0] * $q[1] - $q[0] * $pts[ $i ][1]; }
			if ( $n < 4 || abs( $a ) / 2 < $minarea ) { continue; }
			$prev = null; $seg = '';
			foreach ( $pts as $i => $p ) {
				$r = array( (int) round( $p[0] ), (int) round( $p[1] ) );
				if ( $prev === $r ) { continue; }
				$seg .= ( null === $prev ? 'M' : ( $i ? 'L' : '' ) ) . $r[0] . ' ' . $r[1];
				$prev = $r;
			}
			$d .= $seg . 'Z';
		}
		$d = preg_replace( '/L/', ' ', $d );
		$paths .= '<path id="tp-prov-' . $slug . '" data-tp-prov="' . $slug . '" d="' . $d . '"/>' . "\n";
	}
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $W . ' ' . $H . '" data-tp-src="OpenStreetMap contributors, ODbL">' . "\n" . $paths . '</svg>' . "\n";
};

$eps = 0.6; $svg = '';
for ( $i = 0; $i < 12; $i++ ) {
	$svg = $build( $eps, 4 );
	if ( strlen( $svg ) <= $max * 0.6 ) { break; }   /* هدف ۴۸KB تا جای پیوند و aria در رندر بماند */
	$eps *= 1.35;
}

if ( strlen( $svg ) > $max ) { fwrite( STDERR, 'SVG بیش از ۸۰KB: ' . strlen( $svg ) . "\n" ); exit( 1 ); }

if ( in_array( '--check', $argv, true ) ) {
	$ok = is_file( $out ) && file_get_contents( $out ) === $svg;
	echo $ok ? "سالم — همان خروجی\n" : "ناهمخوان — php scripts/tp-home-map-svg.php\n";
	exit( $ok ? 0 : 1 );
}

if ( ! is_dir( dirname( $out ) ) ) { mkdir( dirname( $out ), 0777, true ); }
file_put_contents( $out, $svg );
printf( "نوشته شد: %s — %d استان، %s بایت، eps=%.2f، %dx%d\n", 'plugin/topersia-render/assets/home/iran-provinces.svg', count( $geo ), number_format( strlen( $svg ) ), $eps, $W, $H );
