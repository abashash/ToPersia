<?php
/**
 * مرز محدوده از OpenStreetMap — دستهٔ ۲ پرامت یکپارچه · P5-D351.  بازتولیدپذیر؛ فقط محلی؛ هیچ فیلد یا متای تازه در پایگاه‌داده.
 *
 *   php wp-cli.phar --path=… eval-file scripts/tp-boundaries.php match     ← reference/osm-boundary-map.csv (پیشنهاد؛ پست‌ها فقط خوانده می‌شوند)
 *   php wp-cli.phar --path=… eval-file scripts/tp-boundaries.php build     ← GeoJSON ساده‌شده برای ردیف‌های auto/confirmed در uploads/tiles/boundaries/
 *   php wp-cli.phar --path=… eval-file scripts/tp-boundaries.php report    ← شمارش و موارد نامطمئن
 *
 * منبع: داده‌های OpenStreetMap از Overpass API (© OpenStreetMap contributors, ODbL) — جایگزین مستقیم «برش Geofabrik + osmium» که روی ویندوز و بی VPN ممکن نبود.
 * وضعیت ۱۳ اکتبر از ایرانِ بی VPN: Geofabrik، Nominatim و polygons.openstreetmap.fr باز نشدند؛ Overpass یک بار باز شد ولی POST چهار آینه
 * (overpass-api.de · lz4 · kumi · private.coffee) همه بی پاسخ ماند. اسکریپت بی تغییر و تکرارپذیر است: با اتصال سالم `match` و `build` را اجرا کنید.
 *
 * نگاشت موجودیت ← شناسهٔ OSM: `reference/osm-boundary-map.csv` (entity_uid, osm_type, osm_id, روش تطبیق, وضعیت). تطبیق خودکار فقط پیشنهاد است:
 * وضعیت `auto` = نام فارسی دقیقاً یکی و ردهٔ درست؛ `suggested` = نامطمئن (به GeoJSON نمی‌رسد تا کاربر `confirmed` بنویسد)؛ `none` = پیدا نشد.
 * خروجی لایهٔ مشتق و بازساختنی است (REQ-D-05)، هر موجودیت یک GeoJSON ساده‌شده (هدف زیر ~۵۰KB).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }

$cmd  = isset( $args[0] ) ? $args[0] : 'report';
$root = dirname( __DIR__ );
$csv  = $root . '/reference/osm-boundary-map.csv';
$up   = wp_get_upload_dir();
$out  = trailingslashit( $up['basedir'] ) . 'tiles/boundaries/';
$bbox = '25,44,40.5,63.5';
$AREA = array( 'tp_province' => array( 'rel', 4 ), 'tp_water_body' => array( 'any', 0 ), 'tp_desert' => array( 'any', 0 ), 'tp_mountain' => array( 'any', 0 ), 'tp_forest_plain' => array( 'any', 0 ), 'tp_protected_area' => array( 'any', 0 ), 'tp_island' => array( 'any', 0 ), 'tp_region' => array( 'any', 0 ), 'tp_natural_feature' => array( 'any', 0 ), 'tp_city' => array( 'rel', 8 ) );

/* ── ابزار ───────────────────────────────────────────────────────── */
function tpb_overpass( $ql, $tries = 4 ) {
	/* آینه‌ها یک‌درمیان؛ POST به‌صورت urlencoded (چندبخشی را Overpass با ۴۰۰ رد می‌کند)؛ گواهی از بستهٔ وردپرس وقتی php.ini ندارد */
	$mirrors = array( 'https://overpass-api.de/api/interpreter', 'https://lz4.overpass-api.de/api/interpreter', 'https://overpass.private.coffee/api/interpreter' );
	$ca      = defined( 'ABSPATH' ) && is_file( ABSPATH . WPINC . '/certificates/ca-bundle.crt' ) ? ABSPATH . WPINC . '/certificates/ca-bundle.crt' : '';
	for ( $i = 0; $i < $tries; $i++ ) {
		$ch  = curl_init( $mirrors[ $i % count( $mirrors ) ] );
		$opt = array( CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query( array( 'data' => $ql ) ), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_USERAGENT => 'ToPersia-local-boundary-script/1.0', CURLOPT_PROXY => '' );
		if ( '' !== $ca && ! ini_get( 'curl.cainfo' ) ) { $opt[ CURLOPT_CAINFO ] = $ca; }
		curl_setopt_array( $ch, $opt );
		$body = curl_exec( $ch );
		$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
		if ( 200 === $code && $body ) {
			$j = json_decode( $body, true );
			if ( is_array( $j ) && isset( $j['elements'] ) ) { return $j['elements']; }
		}
		sleep( 3 * ( $i + 1 ) );
	}
	return null;
}
function tpb_key( $uid ) {
	$path = (string) preg_replace( '~^https?://[^/]+/e/~', '', trim( (string) $uid ) );
	return preg_match( '~^[a-z0-9-]+(/[a-z0-9-]+)*$~', $path ) ? str_replace( '/', '__', $path ) : '';
}
function tpb_norm( $s ) {
	$s = str_replace( array( 'ي', 'ك', "\u{200C}", 'ۀ', 'ة' ), array( 'ی', 'ک', ' ', 'ه', 'ه' ), (string) $s );
	$s = preg_replace( '/^(استان|شهرستان|شهر|دریاچه|تالاب|کویر|رشته‌?کوه|جزیره|منطقه حفاظت شده|پارک ملی)\s+/u', '', trim( $s ) );
	return trim( preg_replace( '/\s+/u', ' ', $s ) );
}
function tpb_ring( $ways ) {
	/* وصل‌کردن راه‌های یک رابطه به حلقه‌های بسته */
	$rings = array();
	$ways  = array_values( array_filter( $ways, function ( $w ) { return count( $w ) > 1; } ) );
	while ( $ways ) {
		$ring = array_shift( $ways );
		$safe = 0;
		while ( $ring[0] !== $ring[ count( $ring ) - 1 ] && $ways && $safe++ < 5000 ) {
			$end = $ring[ count( $ring ) - 1 ];
			$hit = false;
			foreach ( $ways as $k => $w ) {
				if ( $w[0] === $end ) { $ring = array_merge( $ring, array_slice( $w, 1 ) ); $hit = true; }
				elseif ( $w[ count( $w ) - 1 ] === $end ) { $ring = array_merge( $ring, array_slice( array_reverse( $w ), 1 ) ); $hit = true; }
				if ( $hit ) { unset( $ways[ $k ] ); $ways = array_values( $ways ); break; }
			}
			if ( ! $hit ) { break; }
		}
		if ( count( $ring ) > 3 && $ring[0] === $ring[ count( $ring ) - 1 ] ) { $rings[] = $ring; }
	}
	return $rings;
}
function tpb_dp( $pts, $eps ) {
	$n = count( $pts );
	if ( $n < 3 ) { return $pts; }
	$keep = array_fill( 0, $n, false );
	$keep[0] = $keep[ $n - 1 ] = true;
	$stack = array( array( 0, $n - 1 ) );
	while ( $stack ) {
		list( $a, $b ) = array_pop( $stack );
		$dmax = 0; $idx = 0;
		$dx = $pts[ $b ][0] - $pts[ $a ][0]; $dy = $pts[ $b ][1] - $pts[ $a ][1]; $len = sqrt( $dx * $dx + $dy * $dy );
		for ( $i = $a + 1; $i < $b; $i++ ) {
			$d = $len > 0 ? abs( $dy * $pts[ $i ][0] - $dx * $pts[ $i ][1] + $pts[ $b ][0] * $pts[ $a ][1] - $pts[ $b ][1] * $pts[ $a ][0] ) / $len : sqrt( pow( $pts[ $i ][0] - $pts[ $a ][0], 2 ) + pow( $pts[ $i ][1] - $pts[ $a ][1], 2 ) );
			if ( $d > $dmax ) { $dmax = $d; $idx = $i; }
		}
		if ( $dmax > $eps ) { $keep[ $idx ] = true; $stack[] = array( $a, $idx ); $stack[] = array( $idx, $b ); }
	}
	$o = array();
	foreach ( $pts as $i => $p ) { if ( $keep[ $i ] ) { $o[] = $p; } }
	return $o;
}
function tpb_geojson( $rings, $limit = 50000 ) {
	$eps = 0.0005;
	for ( $k = 0; $k < 14; $k++ ) {
		$polys = array();
		foreach ( $rings as $r ) {
			$s = tpb_dp( $r, $eps );
			if ( count( $s ) >= 4 ) { $polys[] = array( array_map( function ( $p ) { return array( round( $p[0], 4 ), round( $p[1], 4 ) ); }, $s ) ); }
		}
		$js = wp_json_encode( array( 'type' => 'Feature', 'properties' => new stdClass(), 'geometry' => array( 'type' => 'MultiPolygon', 'coordinates' => $polys ) ) );
		if ( strlen( $js ) <= $limit || $k === 13 ) { return array( $js, count( $polys ), $eps ); }
		$eps *= 1.7;
	}
}
function tpb_rows( $csv ) {
	$rows = array();
	if ( is_file( $csv ) ) {
		$fh = fopen( $csv, 'r' );
		$hd = fgetcsv( $fh, 0, ',', '"', '\\' );
		while ( ( $r = fgetcsv( $fh, 0, ',', '"', '\\' ) ) ) { if ( count( $r ) === count( $hd ) ) { $rows[ $r[0] ] = array_combine( $hd, $r ); } }
		fclose( $fh );
	}
	return $rows;
}

/* ── match ───────────────────────────────────────────────────────── */
if ( 'match' === $cmd ) {
	do_action( 'wpml_switch_language', 'fa' );
	$old  = tpb_rows( $csv );
	$prov = tpb_overpass( "[out:json][timeout:120];rel[\"boundary\"=\"administrative\"][\"admin_level\"=\"4\"][\"ISO3166-2\"~\"^IR-\"]({$bbox});out tags;" );
	echo 'استان‌های OSM: ' . ( null === $prov ? 'شکست' : count( $prov ) ) . "\n";
	$byname = array();
	foreach ( (array) $prov as $e ) {
		foreach ( array( 'name', 'name:fa' ) as $k ) { if ( ! empty( $e['tags'][ $k ] ) ) { $byname[ tpb_norm( $e['tags'][ $k ] ) ][ $e['id'] ] = true; } }
	}
	$rows = array();
	foreach ( $AREA as $type => $cfg ) {
		$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false, 'orderby' => 'ID', 'order' => 'ASC' ) );
		foreach ( $ids as $p ) {
			$uid = (string) get_post_meta( $p->ID, 'entity_uid', true );
			if ( '' === $uid ) { continue; }
			if ( isset( $old[ $uid ] ) && in_array( $old[ $uid ]['status'], array( 'confirmed' ), true ) ) { $rows[ $uid ] = $old[ $uid ]; continue; }
			$name = tpb_norm( (string) get_post_meta( $p->ID, 'official_name_fa', true ) ?: (string) get_post_meta( $p->ID, 'name_fa', true ) ?: $p->post_title );
			$row  = array( 'entity_uid' => $uid, 'osm_type' => '', 'osm_id' => '', 'method' => 'none', 'status' => 'none', 'post_type' => $type, 'name_fa' => $name );
			if ( 'tp_province' === $type ) {
				if ( isset( $byname[ $name ] ) && 1 === count( $byname[ $name ] ) ) { $row = array_merge( $row, array( 'osm_type' => 'relation', 'osm_id' => (string) key( $byname[ $name ] ), 'method' => 'admin_level=4+ISO3166-2+exact-name', 'status' => 'auto' ) ); }
			} else {
				$q  = "[out:json][timeout:60];(rel[\"name\"=\"{$name}\"][\"type\"~\"multipolygon|boundary\"]({$bbox});way[\"name\"=\"{$name}\"][\"natural\"~\"water|wood|scrub|sand|bare_rock|wetland|coastline\"]({$bbox});way[\"name\"=\"{$name}\"][\"place\"~\"island|islet\"]({$bbox}););out tags;";
				$el = tpb_overpass( $q );
				echo '.'; flush();
				sleep( 1 );
				if ( is_array( $el ) && $el ) {
					usort( $el, function ( $a, $b ) { return ( 'relation' === $b['type'] ) <=> ( 'relation' === $a['type'] ); } );
					$best = $el[0];
					$row  = array_merge( $row, array( 'osm_type' => $best['type'], 'osm_id' => (string) $best['id'], 'method' => 'exact-name+class(' . count( $el ) . ' candidates)', 'status' => 1 === count( $el ) ? 'auto' : 'suggested' ) );
				}
			}
			if ( isset( $old[ $uid ] ) && 'none' !== $old[ $uid ]['status'] && 'none' === $row['status'] ) { $row = $old[ $uid ]; }
			$rows[ $uid ] = $row;
		}
	}
	if ( ! is_dir( dirname( $csv ) ) ) { mkdir( dirname( $csv ), 0777, true ); }
	$fh = fopen( $csv, 'w' );
	fputcsv( $fh, array( 'entity_uid', 'osm_type', 'osm_id', 'method', 'status', 'post_type', 'name_fa' ), ',', '"', '\\' );
	foreach ( $rows as $r ) { fputcsv( $fh, array( $r['entity_uid'], $r['osm_type'], $r['osm_id'], $r['method'], $r['status'], $r['post_type'], $r['name_fa'] ), ',', '"', '\\' ); }
	fclose( $fh );
	echo 'CSV: ' . count( $rows ) . " ردیف\n";
	$cmd = 'report';
}

/* ── build ───────────────────────────────────────────────────────── */
if ( 'build' === $cmd ) {
	$rows = tpb_rows( $csv );
	if ( ! is_dir( $out ) ) { mkdir( $out, 0777, true ); }
	$done = 0; $fail = 0; $bytes = array();
	foreach ( $rows as $uid => $r ) {
		if ( ! in_array( $r['status'], array( 'auto', 'confirmed' ), true ) || '' === $r['osm_id'] || '' === tpb_key( $uid ) ) { continue; }
		if ( is_file( $out . tpb_key( $uid ) . '.geojson' ) && ! in_array( 'force', $args, true ) ) { $done++; $bytes[] = filesize( $out . tpb_key( $uid ) . '.geojson' ); continue; }
		$el = tpb_overpass( "[out:json][timeout:180];{$r['osm_type']}({$r['osm_id']});out geom;" );
		sleep( 3 );
		$rings = array();
		if ( is_array( $el ) && $el ) {
			$e = $el[0];
			if ( 'way' === $e['type'] && ! empty( $e['geometry'] ) ) {
				$pts = array_map( function ( $g ) { return array( $g['lon'], $g['lat'] ); }, $e['geometry'] );
				$rings = tpb_ring( array( $pts ) );
			} elseif ( 'relation' === $e['type'] ) {
				$ways = array();
				foreach ( (array) $e['members'] as $m ) {
					if ( 'way' === $m['type'] && 'inner' !== ( isset( $m['role'] ) ? $m['role'] : '' ) && ! empty( $m['geometry'] ) ) {
						$ways[] = array_map( function ( $g ) { return array( $g['lon'], $g['lat'] ); }, $m['geometry'] );
					}
				}
				$rings = tpb_ring( $ways );
			}
		}
		if ( ! $rings ) { $fail++; echo "✗ {$uid} {$r['name_fa']} — هندسه ساخته نشد\n"; continue; }
		list( $js, $n, $eps ) = tpb_geojson( $rings );
		file_put_contents( $out . tpb_key( $uid ) . '.geojson', $js );
		$bytes[] = strlen( $js );
		$done++;
		echo "✓ {$uid} {$r['name_fa']} — {$n} حلقه · " . strlen( $js ) . " بایت\n";
	}
	echo "ساخته/موجود: {$done} · شکست: {$fail} · میانه: " . ( $bytes ? (int) ( array_sum( $bytes ) / count( $bytes ) ) : 0 ) . " بایت · بیشینه: " . ( $bytes ? max( $bytes ) : 0 ) . "\n";
	$cmd = 'report';
}

/* ── report ──────────────────────────────────────────────────────── */
if ( 'report' === $cmd ) {
	$rows = tpb_rows( $csv );
	$c = array();
	foreach ( $rows as $r ) { $c[ $r['post_type'] ][ $r['status'] ] = ( isset( $c[ $r['post_type'] ][ $r['status'] ] ) ? $c[ $r['post_type'] ][ $r['status'] ] : 0 ) + 1; }
	foreach ( $c as $t => $v ) { echo str_pad( $t, 22 ) . json_encode( $v ) . "\n"; }
	foreach ( $rows as $r ) { if ( 'suggested' === $r['status'] || ( 'none' === $r['status'] && 'tp_province' === $r['post_type'] ) ) { echo "تأیید/بررسی کاربر: {$r['entity_uid']} {$r['post_type']} «{$r['name_fa']}» ← {$r['osm_type']}/{$r['osm_id']} ({$r['status']})\n"; } }
}
