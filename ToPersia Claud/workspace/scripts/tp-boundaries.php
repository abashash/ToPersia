<?php
/**
 * مرز محدوده از OpenStreetMap — P5-D351 · بازنویسی P5-D363.  بازتولیدپذیر؛ فقط محلی؛ هیچ فیلد یا متای تازه در پایگاه
 *
 *   wp eval-file scripts/tp-boundaries.php fetch            ← یک پرس‌وجوی Overpass برای همهٔ مرزهای اداری و چندضلعی‌های place=city|town ایران → کش محلی (تنها گام شبکه برای match)
 *   wp eval-file scripts/tp-boundaries.php match            ← آفلاین، روی کش: reference/osm-boundary-map.csv
 *   wp eval-file scripts/tp-boundaries.php build [province|city|all] [force]   ← دسته‌ای (چند شناسه در هر پرس‌وجو)؛ GeoJSON ساده‌شده
 *   wp eval-file scripts/tp-boundaries.php report           ← شمارش، suggested با نام استان والد، ۲۰ شهر none مهم‌تر
 *
 * کش و خروجی همه بیرون از git: `uploads/tiles/boundaries-cache/` (پاسخ خام Overpass + شکست‌های build با علت) و `uploads/tiles/boundaries/`.
 *
 * قواعد (P5-D363):
 *   استان   منبع اصلی نام است (اصلاحیه P5-D363): نام نرمال‌شدهٔ استان در رابطه‌های admin_level=4 ایران (ISO3166-2 خودِ OSM با «IR-») — یکتا ← auto، وگرنه none.
 *           ISO3166-2 فقط از خود OSM ثبت می‌شود (reference/province-osm.csv)، نه از حافظه. دو نگهبان: شناسهٔ OSM تکراری ← none؛ مرز مرکز استان درون چندضلعی ← وگرنه none.
 *   شهر     نامزد فقط مرز اداری (جز سطح ۴) یا چندضلعی place=city|town هم‌نام باشد، و مرکزش (out center) درون GeoJSON استان والد
 *           (والد از رابطهٔ شمول خودِ داده)؛ یک نامزد ← auto · چند ← suggested · صفر ← none؛ GeoJSON والد نبود ← suggested.
 *   پهنهٔ طبیعی (آب · بیابان · کوه · جنگل · پارک · جزیره · منطقه · عارضه) ← skip.
 *   confirmed (دست کاربر) هرگز بازنویسی نمی‌شود؛ به GeoJSON فقط auto و confirmed می‌رسد.
 * منبع: OpenStreetMap contributors, ODbL. POST آینه‌ها urlencoded (چندبخشی ← ۴۰۰).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }

require_once __DIR__ . '/lib/tp-boundaries-lib.php';

$cmd   = isset( $args[0] ) ? $args[0] : 'report';
$root  = dirname( __DIR__ );
$csv   = $root . '/reference/osm-boundary-map.csv';
$up    = wp_get_upload_dir();
$out   = trailingslashit( $up['basedir'] ) . 'tiles/boundaries/';
$cache = trailingslashit( $up['basedir'] ) . 'tiles/boundaries-cache/';
$AREA  = array( 'tp_province', 'tp_city' );
$SKIP  = array( 'tp_water_body', 'tp_desert', 'tp_mountain', 'tp_forest_plain', 'tp_protected_area', 'tp_island', 'tp_region', 'tp_natural_feature' );

/* ── شبکه ────────────────────────────────────────────────────────── */
function tpb_overpass( $ql, $tries = 4, $timeout = 180 ) {
	/* آینه‌ها یک‌درمیان؛ گواهی از بستهٔ وردپرس وقتی php.ini ندارد */
	$mirrors = array( 'https://overpass-api.de/api/interpreter', 'https://lz4.overpass-api.de/api/interpreter', 'https://overpass.private.coffee/api/interpreter' );
	$ca      = defined( 'ABSPATH' ) && is_file( ABSPATH . WPINC . '/certificates/ca-bundle.crt' ) ? ABSPATH . WPINC . '/certificates/ca-bundle.crt' : '';
	$last    = array( 'code' => 0, 'body' => '' );
	for ( $i = 0; $i < $tries; $i++ ) {
		$ch  = curl_init( $mirrors[ $i % count( $mirrors ) ] );
		$opt = array( CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query( array( 'data' => $ql ) ), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_USERAGENT => 'ToPersia-local-boundary-script/1.1', CURLOPT_PROXY => '' );
		if ( '' !== $ca && ! ini_get( 'curl.cainfo' ) ) { $opt[ CURLOPT_CAINFO ] = $ca; }
		curl_setopt_array( $ch, $opt );
		$body = curl_exec( $ch );
		$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );
		$last = array( 'code' => $code, 'body' => (string) $body );
		if ( 200 === $code && $body ) {
			$j = json_decode( $body, true );
			if ( is_array( $j ) && isset( $j['elements'] ) ) { return array( $j['elements'], $last ); }
		}
		sleep( 3 * ( $i + 1 ) );
	}
	return array( null, $last );
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
function tpb_cache_elements( $cache ) {
	$f = $cache . 'overpass-iran.json';
	if ( ! is_file( $f ) ) { return null; }
	$j = json_decode( (string) file_get_contents( $f ), true );
	return is_array( $j ) && isset( $j['elements'] ) ? $j['elements'] : null;
}
function tpb_province_of( $pid ) {
	if ( ! function_exists( 'topersia_g_place_parents' ) ) { return 0; }
	foreach ( topersia_g_place_parents( $pid ) as $p ) { if ( 'tp_province' === get_post_type( $p['id'] ) ) { return (int) $p['id']; } }
	return 0;
}

/* ── fetch ───────────────────────────────────────────────────────── */
if ( 'fetch' === $cmd ) {
	if ( ! is_dir( $cache ) ) { mkdir( $cache, 0777, true ); }
	/* bbox به‌جای area: سبک‌تر برای سرور (area روی کل کشور بارها 504 داد)؛ مرز ایران ≈ ۲۵–۴۰٫۱°N · ۴۳٫۹–۶۳٫۴°E */
	$ql = '[out:json][timeout:300][bbox:25,43.9,40.1,63.4];'
		. '(rel["boundary"="administrative"]["admin_level"~"^(4|8)$"]["name"];rel["place"~"^(city|town)$"]["name"];way["place"~"^(city|town)$"]["name"];);out tags center;';
	echo "یک پرس‌وجو به Overpass (چند دقیقه)…\n";
	list( $el, $raw ) = tpb_overpass( $ql, 8, 330 );
	if ( null === $el ) { file_put_contents( $cache . 'fetch-failed-' . gmdate( 'Ymd-His' ) . '.txt', 'HTTP ' . $raw['code'] . "\n" . substr( $raw['body'], 0, 4000 ) ); echo "✗ پرس‌وجو شکست خورد (HTTP {$raw['code']})؛ پاسخ خام در کش ذخیره شد\n"; return; }
	file_put_contents( $cache . 'overpass-iran.json', json_encode( array( 'fetched' => gmdate( 'c' ), 'elements' => $el ), JSON_UNESCAPED_UNICODE ) );
	$c = array();
	foreach ( $el as $e ) { $k = $e['type'] . ':' . ( isset( $e['tags']['boundary'] ) ? 'admin' . ( isset( $e['tags']['admin_level'] ) ? $e['tags']['admin_level'] : '' ) : ( isset( $e['tags']['place'] ) ? 'place=' . $e['tags']['place'] : '?' ) ); $c[ $k ] = ( isset( $c[ $k ] ) ? $c[ $k ] : 0 ) + 1; }
	ksort( $c );
	echo 'کش نوشته شد: ' . count( $el ) . ' عنصر · ' . json_encode( $c ) . "\n";
	return;
}

/* ── match ───────────────────────────────────────────────────────── */
if ( 'match' === $cmd ) {
	do_action( 'wpml_switch_language', 'fa' );
	$els = tpb_cache_elements( $cache );
	if ( null === $els ) { echo "کش Overpass نیست — نخست `fetch` (با اتصال)\n"; return; }
	$old = tpb_rows( $csv );
	$rows     = array();
	$osm_iso  = array();   /* uid ← ISO3166-2 خودِ رابطهٔ OSM (فقط برای ثبت) */
	$prov_pid = array();   /* uid ← شناسهٔ پست استان */
	$prov_ll  = array();   /* uid ← «lat, lng» مرکز استان از دادهٔ خودمان */

	/* استان‌ها پیش از شهرها: والد شهر GeoJSON استان است */
	foreach ( array_merge( array( 'tp_province' ), array( 'tp_city' ), $SKIP ) as $type ) {
		$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false, 'orderby' => 'ID', 'order' => 'ASC' ) );
		foreach ( $ids as $p ) {
			$uid = (string) get_post_meta( $p->ID, 'entity_uid', true );
			if ( '' === $uid || '' === tpb_key( $uid ) ) { continue; }
			if ( isset( $old[ $uid ] ) && 'confirmed' === $old[ $uid ]['status'] ) { $rows[ $uid ] = $old[ $uid ]; continue; }
			$name = tpb_norm( (string) get_post_meta( $p->ID, 'official_name_fa', true ) ?: (string) get_post_meta( $p->ID, 'name_fa', true ) ?: $p->post_title );
			$row  = array( 'entity_uid' => $uid, 'osm_type' => '', 'osm_id' => '', 'method' => '', 'status' => 'none', 'post_type' => $type, 'name_fa' => $name, 'parent_province' => '' );

			if ( in_array( $type, $SKIP, true ) ) {
				$row['status'] = 'skip'; $row['method'] = 'natural-area';
			} elseif ( 'tp_province' === $type ) {
				list( $st, $el, $how, $isoc ) = tpb_province_by_name( $name, $els );
				$row['status'] = $st; $row['method'] = $how;
				if ( $el ) { $row['osm_type'] = $el['type']; $row['osm_id'] = (string) $el['id']; $osm_iso[ $uid ] = $isoc; }
				$prov_pid[ $uid ] = $p->ID;
				$cap_id = function_exists( 'topersia_g_capital_id' ) ? (int) topersia_g_capital_id( $p->ID ) : 0;   /* پیش از حلقهٔ شهرها؛ مختصات مرکز برای نگهبان ۲ */
				/* شهرِ «مرکز استان» اگر مختصات دارد (۱ از ۳۱ در پایگاه محلی)، وگرنه نقطهٔ خودِ استان (geo_coordinates همان پست) — منبع ثبت می‌شود */
				$ll_cap = $cap_id ? (string) get_post_meta( $cap_id, 'geo_coordinates', true ) : '';
				$ll_own = (string) get_post_meta( $p->ID, 'geo_coordinates', true );
				$prov_ll[ $uid ] = '' !== $ll_cap ? array( $ll_cap, 'capital' ) : ( '' !== $ll_own ? array( $ll_own, 'province-point' ) : array( '', '' ) );
			} else {
				$pp   = tpb_province_of( $p->ID );
				$pu   = $pp ? (string) get_post_meta( $pp, 'entity_uid', true ) : '';
				$pf   = $pu && is_file( $out . tpb_key( $pu ) . '.geojson' ) ? (string) file_get_contents( $out . tpb_key( $pu ) . '.geojson' ) : '';
				$row['parent_province'] = $pp ? tpb_norm( get_the_title( $pp ) ) : '';
				list( $st, $el, $how ) = tpb_classify_city( $name, $els, $pf );
				$row['status'] = $st; $row['method'] = $how;
				if ( $el ) { $row['osm_type'] = $el['type']; $row['osm_id'] = (string) $el['id']; }
			}
			$rows[ $uid ] = $row;
		}
	}
	/* نگهبان‌های استان (P5-D363 اصلاحیه) — رد ← none و گزارش */
	$guard = array();
	$dups  = tpb_duplicate_ids( array_filter( $rows, function ( $r ) { return 'tp_province' === $r['post_type'] && in_array( $r['status'], array( 'auto', 'confirmed' ), true ); } ) );
	foreach ( $dups as $id => $uids ) {
		foreach ( $uids as $u ) {
			if ( 'auto' === $rows[ $u ]['status'] ) { $rows[ $u ]['status'] = 'none'; $rows[ $u ]['method'] = 'guard1-duplicate-osm-id:' . $id; $rows[ $u ]['osm_type'] = ''; $rows[ $u ]['osm_id'] = ''; }
			$guard[ $u ]['g1'] = 'FAIL dup ' . $id;
		}
		echo "✗ نگهبان ۱: شناسهٔ OSM {$id} برای چند استان: " . implode( ' · ', $uids ) . "\n";
	}
	foreach ( $rows as $uid => &$pr ) {
		if ( 'tp_province' !== $pr['post_type'] ) { continue; }
		if ( ! isset( $guard[ $uid ]['g1'] ) ) { $guard[ $uid ]['g1'] = 'ok'; }
		$gf = $out . tpb_key( $uid ) . '.geojson';
		if ( ! in_array( $pr['status'], array( 'auto', 'confirmed' ), true ) ) {
			$guard[ $uid ]['g2'] = 'n/a';
			if ( is_file( $gf ) ) { unlink( $gf ); echo "• GeoJSON کهنهٔ استانِ بی تطبیق پاک شد: {$pr['name_fa']}\n"; }
			continue;
		}
		if ( ! is_file( $gf ) ) { $guard[ $uid ]['g2'] = 'pending (no GeoJSON yet)'; continue; }
		list( $ll, $src ) = isset( $prov_ll[ $uid ] ) ? $prov_ll[ $uid ] : array( '', '' );
		if ( '' === $ll ) { $guard[ $uid ]['g2'] = 'no-center-coordinates'; echo "• نگهبان ۲: مختصات مرکز استان در داده نیست: {$pr['name_fa']}\n"; continue; }
		$in = tpb_center_inside( $ll, (string) file_get_contents( $gf ) );
		$guard[ $uid ]['g2'] = ( true === $in ? 'ok' : ( false === $in ? 'FAIL center outside' : 'bad-coordinates' ) ) . ' (' . $src . ')';
		if ( false === $in && 'auto' === $pr['status'] ) {
			$pr['status'] = 'none'; $pr['method'] = 'guard2-center-outside-polygon'; unlink( $gf );
			echo "✗ نگهبان ۲: مرکز استان ({$ll}) درون چندضلعی نیست: {$pr['name_fa']} — ردیف none و GeoJSON پاک شد\n";
		}
	}
	unset( $pr );

	/* reference/province-osm.csv — ISO3166-2 فقط از خود OSM */
	$fp = fopen( $root . '/reference/province-osm.csv', 'w' );
	fputcsv( $fp, array( 'entity_uid', 'osm_relation', 'ISO3166-2', 'name_fa', 'status', 'guard1_unique', 'guard2_center' ), ',', '"', '\\' );
	foreach ( $rows as $uid => $r ) {
		if ( 'tp_province' === $r['post_type'] ) { fputcsv( $fp, array( $uid, $r['osm_id'], isset( $osm_iso[ $uid ] ) && '' !== $r['osm_id'] ? $osm_iso[ $uid ] : '', $r['name_fa'], $r['status'], $guard[ $uid ]['g1'], $guard[ $uid ]['g2'] ), ',', '"', '\\' ); }
	}
	fclose( $fp );

	if ( ! is_dir( dirname( $csv ) ) ) { mkdir( dirname( $csv ), 0777, true ); }
	$fh = fopen( $csv, 'w' );
	fputcsv( $fh, array( 'entity_uid', 'osm_type', 'osm_id', 'method', 'status', 'post_type', 'name_fa', 'parent_province' ), ',', '"', '\\' );
	foreach ( $rows as $r ) { fputcsv( $fh, array( $r['entity_uid'], $r['osm_type'], $r['osm_id'], $r['method'], $r['status'], $r['post_type'], $r['name_fa'], isset( $r['parent_province'] ) ? $r['parent_province'] : '' ), ',', '"', '\\' ); }
	fclose( $fh );
	echo 'CSV: ' . count( $rows ) . " ردیف\n";
	$cmd = 'report';
}

/* ── build — دسته‌ای ─────────────────────────────────────────────── */
if ( 'build' === $cmd ) {
	$scope = isset( $args[1] ) && in_array( $args[1], array( 'province', 'city', 'all' ), true ) ? $args[1] : 'all';
	$force = in_array( 'force', $args, true );
	$rows  = tpb_rows( $csv );
	if ( ! is_dir( $out ) ) { mkdir( $out, 0777, true ); }
	if ( ! is_dir( $cache . 'fail' ) ) { mkdir( $cache . 'fail', 0777, true ); }
	$todo = array(); $done = 0; $fail = 0; $bytes = array();
	foreach ( $rows as $uid => $r ) {
		if ( ! in_array( $r['status'], array( 'auto', 'confirmed' ), true ) || '' === $r['osm_id'] || '' === tpb_key( $uid ) ) { continue; }
		if ( 'province' === $scope && 'tp_province' !== $r['post_type'] ) { continue; }
		if ( 'city' === $scope && 'tp_city' !== $r['post_type'] ) { continue; }
		$f = $out . tpb_key( $uid ) . '.geojson';
		if ( is_file( $f ) && ! $force ) { $done++; $bytes[] = filesize( $f ); continue; }
		$todo[ $r['osm_type'] . ':' . $r['osm_id'] ] = array( 'uid' => $uid, 'row' => $r );
	}
	/* چند شناسه در هر پرس‌وجو: ۶ تا؛ استان‌ها (حجیم) ۳ تا */
	$batches = array(); $cur = array(); $curn = 0;
	foreach ( $todo as $k => $t ) {
		$cap = 'tp_province' === $t['row']['post_type'] ? 3 : 6;
		if ( $curn >= $cap ) { $batches[] = $cur; $cur = array(); $curn = 0; }
		$cur[ $k ] = $t; $curn++;
	}
	if ( $cur ) { $batches[] = $cur; }
	foreach ( $batches as $b ) {
		$rel = array(); $way = array();
		foreach ( $b as $k => $t ) { list( $ty, $id ) = explode( ':', $k ); if ( 'way' === $ty ) { $way[] = $id; } else { $rel[] = $id; } }
		$ql = '[out:json][timeout:300];(' . ( $rel ? 'rel(id:' . implode( ',', $rel ) . ');' : '' ) . ( $way ? 'way(id:' . implode( ',', $way ) . ');' : '' ) . ');out geom;';
		list( $els, $raw ) = tpb_overpass( $ql, 4, 320 );
		sleep( 2 );
		$by = array();
		foreach ( (array) $els as $e ) { $by[ $e['type'] . ':' . $e['id'] ] = $e; }
		foreach ( $b as $k => $t ) {
			$uid = $t['uid']; $r = $t['row']; $why = array();
			if ( null === $els ) { $why[] = 'batch-query-failed:HTTP ' . $raw['code']; file_put_contents( $cache . 'fail/batch-' . md5( $ql ) . '.txt', 'HTTP ' . $raw['code'] . "\n" . substr( $raw['body'], 0, 8000 ) ); }
			elseif ( ! isset( $by[ $k ] ) ) { $why[] = 'element-missing-in-response'; }
			else {
				$ways = tpb_element_ways( $by[ $k ], $missing );
				if ( $missing ) { $why[] = 'member_without_geometry:' . $missing; }
				$ringwhy = array();
				$rings = tpb_ring( $ways, $ringwhy );
				$why = array_merge( $why, array_map( function ( $x ) { return is_string( $x ) ? $x : (string) $x; }, array_values( $ringwhy ) ) );
				if ( isset( $ringwhy['short_way'] ) ) { $why[] = 'short_way:' . $ringwhy['short_way']; }
			}
			if ( empty( $rings ) || null === $els || ! isset( $by[ $k ] ) ) {
				$fail++;
				if ( isset( $by[ $k ] ) ) { file_put_contents( $cache . 'fail/' . str_replace( ':', '-', $k ) . '.json', json_encode( array( 'uid' => $uid, 'why' => $why, 'element' => $by[ $k ] ), JSON_UNESCAPED_UNICODE ) ); }
				echo "✗ {$uid} {$r['name_fa']} — هندسه ساخته نشد (" . implode( ' · ', array_unique( $why ) ) . ")\n";
				unset( $rings );
				continue;
			}
			list( $js, $n ) = tpb_geojson( $rings );
			file_put_contents( $out . tpb_key( $uid ) . '.geojson', $js );
			$bytes[] = strlen( $js ); $done++;
			echo "✓ {$uid} {$r['name_fa']} — {$n} حلقه · " . strlen( $js ) . ' بایت' . ( $why ? ' · ' . implode( ' · ', $why ) : '' ) . "\n";
			unset( $rings );
		}
	}
	echo "ساخته/موجود: {$done} · شکست: {$fail} · میانه: " . ( $bytes ? (int) ( array_sum( $bytes ) / count( $bytes ) ) : 0 ) . ' بایت · بیشینه: ' . ( $bytes ? max( $bytes ) : 0 ) . " بایت\n";
	$cmd = 'report';
}

/* ── report ──────────────────────────────────────────────────────── */
if ( 'report' === $cmd ) {
	$rows = tpb_rows( $csv );
	$c = array();
	foreach ( $rows as $r ) { $c[ $r['post_type'] ][ $r['status'] ] = ( isset( $c[ $r['post_type'] ][ $r['status'] ] ) ? $c[ $r['post_type'] ][ $r['status'] ] : 0 ) + 1; }
	foreach ( $c as $t => $v ) { echo str_pad( $t, 22 ) . json_encode( $v ) . "\n"; }
	foreach ( $rows as $r ) { if ( 'suggested' === $r['status'] ) { echo "suggested | {$r['name_fa']} | استان: " . ( isset( $r['parent_province'] ) ? $r['parent_province'] : '' ) . " | {$r['osm_type']}/{$r['osm_id']} | {$r['method']}\n"; } }
	foreach ( $rows as $r ) { if ( 'none' === $r['status'] && 'tp_province' === $r['post_type'] ) { echo "none-province | {$r['name_fa']} | {$r['method']}\n"; } }

	/* ۲۰ شهر none مهم‌تر: ترتیب خودِ سایت (topersia_g_sort_entities — display_priority نزولی، جانشین جمعیت، الفبا) */
	$none = array();
	foreach ( $rows as $uid => $r ) { if ( 'none' === $r['status'] && 'tp_city' === $r['post_type'] ) { $none[ $uid ] = $r; } }
	if ( $none && function_exists( 'topersia_g_sort_entities' ) ) {
		$byid = array();
		foreach ( $none as $uid => $r ) {
			$q = get_posts( array( 'post_type' => 'tp_city', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false, 'meta_key' => 'entity_uid', 'meta_value' => $uid ) );
			if ( $q ) { $byid[ $q[0] ] = $r; }
		}
		$order = topersia_g_sort_entities( array_keys( $byid ) );
		echo 'none-city (۲۰ نخست به ترتیب سایت؛ display_priority هنوز خالی است ← جمعیت، سپس الفبا): ' . count( $none ) . " کل\n";
		foreach ( array_slice( $order, 0, 20 ) as $i => $id ) { echo '  ' . ( $i + 1 ) . ' | ' . $byid[ $id ]['name_fa'] . ' | استان: ' . $byid[ $id ]['parent_province'] . ' | ' . $byid[ $id ]['method'] . "\n"; }
	}
}
