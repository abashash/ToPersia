<?php
/**
 * عددهای پایه از ویکی‌داده با منبع — P5-D393.  فقط محلی؛ پیشنهاد، نه نوشتن.
 *
 *   wp eval-file scripts/tp-facts.php match     ← تطبیق QID، دریافت مقدارها، reference/facts-proposed.csv + خلاصه
 *   wp eval-file scripts/tp-facts.php report    ← شمارها و پوشش پیش/پس از روی همان CSV (آفلاین)
 *   wp eval-file scripts/tp-facts.php mark      ← ستون approved را با قاعدهٔ تأییدشدهٔ کاربر پر می‌کند (دستهٔ ۲۲ب)
 *   wp eval-file scripts/tp-facts.php apply dry-run | apply   ← فقط approved؛ پشتیبان؛ لاگ؛ شمار انتظار ۳۸۳ وگرنه توقف
 *   wp eval-file scripts/tp-facts.php rollback <لاگ>          ← برگشت از لاگ
 *   wp eval-file scripts/tp-facts.php verify    ← QID استان‌ها (P31) و sameAs JSON-LD پنج استان نمونه
 *
 * منبع ذخیره‌شده فقط ویکی‌داده (CC0): P625 مختصات · P1082 جمعیت با P585 (سال) و ارجاعش · P2046 مساحت · P2044 ارتفاع · P36 مرکز استان.
 * OSM فقط برای تطبیق و سنجش (تگ wikidata عنصرِ همان شناسه‌ای که reference/osm-boundary-map.csv دارد، از کش Overpass محلی)، هرگز منبع مقدار.
 * هیچ عدد ساختهٔ هوش مصنوعی. بی فیلد، جدول یا ستون تازه: QID در `external_reference_uri` (متاباکس مشترک، WPML copy)، منبع هر عدد
 * در repeater `source_references` (WPML translate) — پیشنهادش در همین CSV می‌آید.
 *
 * تطبیق QID به این ترتیب: QID موجود در داده ← تگ wikidata عنصر OSM ← نام فارسی (برچسب یا نام دیگر) + نوع (P31/P279*) + استان (P131*).
 * نگهبان‌ها: QID (حتی موجود در داده) ردهٔ نوع را دارد (P31/P279*)، وگرنه کنار گذاشته و راه بعدی؛ نقطه درون GeoJSON استان خودش (و شهر اگر مرز دارد)؛
 * یک QID برای دو موجودیت نه؛ جمعیت با نوع بخواند. وضعیت: auto · suggested · none. QID نادرست داده ردیف `external_reference_uri · diff` می‌گیرد (فقط گزارش).
 * فقط فیلد خالی پیشنهاد «fill» می‌گیرد؛ مقدار موجودِ ناهمخوان «diff» (فقط گزارش، هرگز نوشته نمی‌شود).
 *
 * SPARQL مؤدبانه: دسته‌ای (۴۰)، User-Agent با نشانی تماس پروژه، فاصلهٔ ۲ ثانیه، Retry-After برای ۴۲۹/۵۰۳. پاسخ خام در کش محلی بیرون از git.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }

require_once __DIR__ . '/lib/tp-boundaries-lib.php';

$cmd    = isset( $args[0] ) ? $args[0] : 'report';
$root   = dirname( __DIR__ );
$csv    = $root . '/reference/facts-proposed.csv';
$up     = wp_get_upload_dir();
$bdir   = trailingslashit( $up['basedir'] ) . 'tiles/boundaries/';
$cache  = trailingslashit( $up['basedir'] ) . 'tiles/facts-cache/';
if ( ! defined( 'TPF_UA' ) ) { define( 'TPF_UA', 'ToPersiaFacts/1.0 (https://topersia.com; privacy@topersia.com) WordPress' ); }
if ( ! defined( 'TPF_CACHE' ) ) { define( 'TPF_CACHE', $cache ); }
if ( ! function_exists( 'tpb_province_of' ) ) {
	function tpb_province_of( $pid ) {
		if ( ! function_exists( 'topersia_g_place_parents' ) ) { return 0; }
		foreach ( topersia_g_place_parents( $pid ) as $p ) { if ( 'tp_province' === get_post_type( $p['id'] ) ) { return (int) $p['id']; } }
		return 0;
	}
}
$NOW    = gmdate( 'Y-m-d' );

/* نوع‌های مکانی و فیلدهای موجودشان (از متاباکس‌های جت‌انجین؛ reference/Jetengine_Skin.json) */
$TYPES = array( 'tp_country', 'tp_region', 'tp_province', 'tp_city', 'tp_district', 'tp_village', 'tp_island', 'tp_mountain', 'tp_water_body', 'tp_desert', 'tp_forest_plain', 'tp_border_crossing', 'tp_monument', 'tp_religious_site', 'tp_ancient_site', 'tp_natural_feature', 'tp_museum', 'tp_protected_area', 'tp_landscape' );

/* ریشهٔ ردهٔ ویکی‌داده برای هر نوع (P31/P279*) — نگهبان «نوع بخواند» */
$ROOTS = array(
	'tp_country'         => array( 'Q6256', 'Q3624078' ),             /* کشور · دولت مستقل */
	'tp_province'        => array( 'Q1344695' ),                      /* استان ایران */
	'tp_city'            => array( 'Q486972' ),                       /* زیستگاه انسانی */
	'tp_village'         => array( 'Q486972' ),
	'tp_district'        => array( 'Q486972', 'Q123705' ),            /* + محله */
	'tp_island'          => array( 'Q23442' ),
	'tp_mountain'        => array( 'Q8502', 'Q46831' ),               /* کوه · رشته‌کوه */
	'tp_water_body'      => array( 'Q15324' ),
	'tp_desert'          => array( 'Q8514' ),
	'tp_forest_plain'    => array( 'Q4421', 'Q160091' ),              /* جنگل · دشت */
	'tp_border_crossing' => array( 'Q874664' ),
	'tp_monument'        => array( 'Q811979', 'Q4989906', 'Q839954', 'Q2065736' ),
	'tp_religious_site'  => array( 'Q24398318', 'Q811979' ),
	'tp_ancient_site'    => array( 'Q839954', 'Q811979' ),
	'tp_natural_feature' => array( 'Q35145263', 'Q8502', 'Q15324' ),
	'tp_museum'          => array( 'Q33506' ),
	'tp_protected_area'  => array( 'Q473972' ),
	'tp_landscape'       => array( 'Q107425', 'Q473972' ),
);

/* جمعیت قابل‌قبول به ازای نوع (نگهبان ۳) */
$POP = array( 'tp_country' => array( 1000000, 2000000000 ), 'tp_province' => array( 100000, 30000000 ), 'tp_city' => array( 500, 20000000 ), 'tp_village' => array( 1, 60000 ) );

/* فیلدهای هر نوع — همان که متاباکس دارد؛ نوعی که فیلد ندارد پیشنهاد نمی‌گیرد */
function tpf_has_field( $type, $field ) {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		$j   = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/reference/Jetengine_Skin.json' ), true );
		foreach ( (array) $j['meta_boxes'] as $mb ) {
			$a  = is_string( $mb['args'] ) ? @unserialize( $mb['args'] ) : (array) $mb['args'];
			$ff = is_string( $mb['meta_fields'] ) ? @unserialize( $mb['meta_fields'] ) : (array) $mb['meta_fields'];
			foreach ( (array) $ff as $f ) {
				foreach ( (array) ( $a['allowed_post_type'] ?? array() ) as $t ) { $map[ $t ][ $f['name'] ] = 1; }
			}
		}
	}
	return isset( $map[ $type ][ $field ] );
}

function tpf_qid( $s ) { return preg_match( '~(?:wikidata\.org/(?:wiki|entity)/)?(Q[1-9][0-9]*)\b~', (string) $s, $m ) ? $m[1] : ''; }

/** SPARQL با فاصله و تلاش دوباره؛ پاسخ خام در کش. */
function tpf_sparql( $q, $tag ) {
	$cache = TPF_CACHE;
	if ( ! is_dir( $cache ) ) { wp_mkdir_p( $cache ); }
	$key = $cache . 'q-' . substr( md5( $q ), 0, 16 ) . '-' . $tag . '.json';
	if ( is_file( $key ) && filemtime( $key ) > time() - 7 * DAY_IN_SECONDS ) { return json_decode( (string) file_get_contents( $key ), true ); }
	for ( $try = 0; $try < 5; $try++ ) {
		sleep( 2 );
		/* curl مستقیم با بستهٔ گواهی خودِ وردپرس: WP_HTTP_BLOCK_EXTERNAL محلی (درست، برای افزونه‌ها) این ابزار دستیِ صریح را نمی‌گیرد */
		$c = curl_init( 'https://query.wikidata.org/sparql' );
		curl_setopt_array( $c, array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => http_build_query( array( 'query' => $q ) ),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_TIMEOUT        => 90,
			CURLOPT_CAINFO         => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
			CURLOPT_HTTPHEADER     => array( 'User-Agent: ' . TPF_UA, 'Accept: application/sparql-results+json', 'Content-Type: application/x-www-form-urlencoded' ),
		) );
		$raw  = curl_exec( $c );
		$code = (int) curl_getinfo( $c, CURLINFO_HTTP_CODE );
		$err  = curl_error( $c );
		$hs   = (int) curl_getinfo( $c, CURLINFO_HEADER_SIZE );
		curl_close( $c );
		$hdr  = false === $raw ? '' : substr( $raw, 0, $hs );
		if ( 200 === $code ) {
			$body = json_decode( substr( $raw, $hs ), true );
			if ( is_array( $body ) ) { file_put_contents( $key, wp_json_encode( $body ) ); return $body; }
		}
		$ra   = preg_match( '/^retry-after:\s*(\d+)/mi', $hdr, $m ) ? (int) $m[1] : 0;
		$wait = 429 === $code || 503 === $code ? max( 5, $ra ) : 4 * ( $try + 1 );
		$r    = '' !== $err ? new WP_Error( 'curl', $err ) : null;
		echo "  · SPARQL {$tag}: " . ( $r ? $r->get_error_message() : 'HTTP ' . $code ) . " — دوباره پس از {$wait} ثانیه\n";
		sleep( $wait );
	}
	return null;
}

function tpf_rows( $res ) {
	$out = array();
	foreach ( (array) ( $res['results']['bindings'] ?? array() ) as $b ) {
		$row = array();
		foreach ( $b as $k => $v ) { $row[ $k ] = $v['value']; }
		$out[] = $row;
	}
	return $out;
}

function tpf_q( $uri ) { return preg_replace( '~^.*/~', '', (string) $uri ); }

/* ══ match ═════════════════════════════════════════════════════════════ */
if ( 'match' === $cmd ) {

	/* ── موجودیت‌ها ── */
	$ents = array();
	foreach ( $TYPES as $t ) {
		foreach ( get_posts( array( 'post_type' => $t, 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false, 'lang' => apply_filters( 'wpml_default_language', 'fa' ) ) ) as $p ) {
			$uid = (string) get_post_meta( $p->ID, 'entity_uid', true );
			if ( '' === $uid ) { continue; }
			/* استان از زنجیرهٔ کامل R01 (جاذبه ← شهر ← … ← استان)، نه فقط والد مستقیم */
			$prov = 'tp_province' === $t ? $p->ID : 0;
			if ( ! $prov && function_exists( 'topersia_g_place_chain' ) ) { foreach ( topersia_g_place_chain( $p->ID ) as $up ) { if ( 'tp_province' === get_post_type( $up ) ) { $prov = (int) ( function_exists( 'topersia_g_default_id' ) ? topersia_g_default_id( $up ) : $up ); break; } } }
			if ( ! $prov ) { $prov = tpb_province_of( $p->ID ); }
			$ents[ $uid ] = array(
				'id' => $p->ID, 'type' => $t, 'name' => tpb_norm( get_the_title( $p ) ), 'title' => get_the_title( $p ), 'prov' => (int) $prov,
				'prov_uid' => $prov ? (string) get_post_meta( $prov, 'entity_uid', true ) : '',
				'qid' => tpf_qid( get_post_meta( $p->ID, 'external_reference_uri', true ) ), 'method' => '',
				'cur' => array(
					'geo_coordinates' => trim( (string) get_post_meta( $p->ID, 'geo_coordinates', true ) ),
					'population'      => trim( (string) get_post_meta( $p->ID, 'population', true ) ),
					'area_km2'        => trim( (string) get_post_meta( $p->ID, 'tp_landscape' === $t ? 'total_area_km2' : 'area_km2', true ) ),
					'elevation_m'     => trim( (string) get_post_meta( $p->ID, 'elevation_m', true ) ),
				),
			);
			if ( '' !== $ents[ $uid ]['qid'] ) { $ents[ $uid ]['method'] = 'data'; }
		}
	}
	echo 'موجودیت مکانی منتشرشده: ' . count( $ents ) . "\n";

	/* ── ۲) تگ wikidata عنصر OSM ── */
	$osm = array();
	$ofile = $root . '/reference/osm-boundary-map.csv';
	$rows = array_map( 'str_getcsv', file( $ofile ) );
	$h = array_shift( $rows );
	foreach ( $rows as $r ) { $a = array_combine( $h, $r ); if ( in_array( $a['status'], array( 'auto', 'confirmed' ), true ) && '' !== $a['osm_id'] ) { $osm[ $a['entity_uid'] ] = $a['osm_type'] . ':' . $a['osm_id']; } }
	$opc = trailingslashit( $up['basedir'] ) . 'tiles/boundaries-cache/overpass-iran.json';
	$tagq = array();
	if ( is_file( $opc ) ) {
		$oj = json_decode( (string) file_get_contents( $opc ), true );
		foreach ( (array) $oj['elements'] as $e ) { if ( ! empty( $e['tags']['wikidata'] ) ) { $tagq[ $e['type'] . ':' . $e['id'] ] = tpf_qid( $e['tags']['wikidata'] ); } }
	}
	foreach ( $ents as $uid => &$e ) {
		if ( '' === $e['qid'] && isset( $osm[ $uid ], $tagq[ $osm[ $uid ] ] ) && '' !== $tagq[ $osm[ $uid ] ] ) { $e['qid'] = $tagq[ $osm[ $uid ] ]; $e['method'] = 'osm-tag ' . $osm[ $uid ]; }
	}
	unset( $e );

	/* نگهبان رده (P31/P279*) — QID موجود در داده یا تگ OSM هم باید ردهٔ همان نوع را داشته باشد؛ وگرنه کنار گذاشته می‌شود */
	$have = array();
	foreach ( $ents as $e ) { if ( '' !== $e['qid'] && isset( $ROOTS[ $e['type'] ] ) ) { $have[ $e['qid'] ] = 1; } }
	$allroots = array();
	foreach ( $ROOTS as $rs ) { foreach ( $rs as $r ) { $allroots[ $r ] = 1; } }
	$rootof = array();
	foreach ( array_chunk( array_keys( $have ), 60 ) as $i => $chunk ) {
		$res = tpf_sparql( 'SELECT ?item ?root WHERE { VALUES ?item { ' . implode( ' ', array_map( function ( $q ) { return 'wd:' . $q; }, $chunk ) ) . ' } VALUES ?root { ' . implode( ' ', array_map( function ( $r ) { return 'wd:' . $r; }, array_keys( $allroots ) ) ) . ' } ?item wdt:P31/wdt:P279* ?root . }', 'class' . $i );
		if ( null === $res ) { echo "✗ پرس‌وجوی رده دستهٔ {$i} شکست خورد — QIDهای این دسته بی‌نگهبان نمی‌مانند (none)\n"; foreach ( $chunk as $q ) { $rootof[ $q ] = array( '?' => 1 ); } continue; }
		foreach ( tpf_rows( $res ) as $r ) { $rootof[ tpf_q( $r['item'] ) ][ tpf_q( $r['root'] ) ] = 1; }
	}
	$wrong = 0;
	foreach ( $ents as $uid => &$e ) {
		if ( '' === $e['qid'] || ! isset( $ROOTS[ $e['type'] ] ) ) { continue; }
		$ok = array_intersect( $ROOTS[ $e['type'] ], array_keys( (array) ( $rootof[ $e['qid'] ] ?? array() ) ) );
		if ( ! $ok ) {
			$e['bad_qid'] = $e['qid'] . ' (' . $e['method'] . ')';
			$e['qid'] = ''; $e['method'] = '';
			$wrong++;
		}
	}
	unset( $e );
	echo "QID ناسازگار با ردهٔ نوع (داده یا OSM) کنار گذاشته شد: {$wrong}\n";

	/* ── ۳) نام فارسی + نوع + استان — دو گذر: اول کشور و استان‌ها (با «استان X»)، سپس بقیه با QID استانِ تازه‌یافته (P131*) ── */
	$name_pass = function ( $need, $prov_q, $tagp ) use ( $ROOTS ) {
		$cands = array();
		foreach ( array_chunk( $need, 40, true ) as $i => $chunk ) {
			$vals = array();
			foreach ( $chunk as $e ) {
				$vals[ $e['name'] ] = 1;
				$vals[ trim( $e['title'] ) ] = 1;
				if ( 'tp_province' === $e['type'] ) { $vals[ 'استان ' . $e['name'] ] = 1; }
			}
			$v     = implode( ' ', array_map( function ( $n ) { return '"' . addcslashes( $n, '"\\' ) . '"@fa'; }, array_keys( $vals ) ) );
			$roots = array();
			foreach ( $chunk as $e ) { foreach ( $ROOTS[ $e['type'] ] as $r ) { $roots[ $r ] = 1; } }
			$rv  = implode( ' ', array_map( function ( $r ) { return 'wd:' . $r; }, array_keys( $roots ) ) );
			$pv  = $prov_q ? 'OPTIONAL { VALUES ?prov { ' . implode( ' ', array_map( function ( $q ) { return 'wd:' . $q; }, array_unique( $prov_q ) ) ) . ' } ?item wdt:P131* ?prov . }' : '';
			$q   = "SELECT DISTINCT ?name ?item ?root ?prov ?coord WHERE {
  VALUES ?name { $v }
  ?item rdfs:label|skos:altLabel ?name .
  ?item wdt:P17 wd:Q794 .
  VALUES ?root { $rv }
  ?item wdt:P31/wdt:P279* ?root .
  $pv
  OPTIONAL { ?item wdt:P625 ?coord . }
}";
			$res = tpf_sparql( $q, $tagp . $i );
			if ( null === $res ) { echo "✗ پرس‌وجوی نام {$tagp} دستهٔ {$i} شکست خورد\n"; continue; }
			foreach ( tpf_rows( $res ) as $r ) { $cands[ $r['name'] ][ tpf_q( $r['item'] ) ][] = $r; }
			echo '  · نام ' . $tagp . ' ' . ( $i + 1 ) . ': ' . count( $chunk ) . ' موجودیت، ' . count( tpf_rows( $res ) ) . " ردیف\n";
		}
		$found = array();
		foreach ( $need as $uid => $e ) {
			$hits  = array();
			$names = array( $e['name'], trim( $e['title'] ) );
			if ( 'tp_province' === $e['type'] ) { $names[] = 'استان ' . $e['name']; }
			foreach ( $names as $n ) {
				foreach ( (array) ( $cands[ $n ] ?? array() ) as $qid => $rs ) {
					$okroot = false; $okprov = in_array( $e['type'], array( 'tp_province', 'tp_country' ), true ); $coord = '';
					foreach ( $rs as $r ) {
						if ( in_array( tpf_q( $r['root'] ), $ROOTS[ $e['type'] ], true ) ) { $okroot = true; }
						if ( isset( $r['prov'] ) && isset( $prov_q[ $e['prov_uid'] ] ) && tpf_q( $r['prov'] ) === $prov_q[ $e['prov_uid'] ] ) { $okprov = true; }
						if ( ! empty( $r['coord'] ) ) { $coord = $r['coord']; }
					}
					if ( $okroot && $okprov ) { $hits[ $qid ] = $coord; }
				}
			}
			if ( 1 === count( $hits ) ) { $found[ $uid ] = array( (string) key( $hits ), 'name+P31+P131', 0 ); }
			elseif ( count( $hits ) > 1 ) { $found[ $uid ] = array( (string) key( $hits ), 'name+P31+P131 (چند نامزد: ' . implode( ' · ', array_keys( $hits ) ) . ')', 1 ); }
		}
		return $found;
	};

	$prov_q = array();
	foreach ( $ents as $uid => $e ) { if ( 'tp_province' === $e['type'] && '' !== $e['qid'] ) { $prov_q[ $uid ] = $e['qid']; } }

	$need1 = array_filter( $ents, function ( $e ) use ( $ROOTS ) { return '' === $e['qid'] && in_array( $e['type'], array( 'tp_province', 'tp_country' ), true ) && '' !== $e['name']; } );
	foreach ( $name_pass( $need1, $prov_q, 'prov' ) as $uid => $x ) { $ents[ $uid ]['qid'] = $x[0]; $ents[ $uid ]['method'] = $x[1]; if ( $x[2] ) { $ents[ $uid ]['multi'] = 1; } }

	$prov_q = array();
	foreach ( $ents as $uid => $e ) { if ( 'tp_province' === $e['type'] && '' !== $e['qid'] && empty( $e['multi'] ) ) { $prov_q[ $uid ] = $e['qid']; } }
	echo 'QID استان پس از گذر اول: ' . count( $prov_q ) . " از 31\n";

	$need2 = array_filter( $ents, function ( $e ) use ( $ROOTS ) { return '' === $e['qid'] && isset( $ROOTS[ $e['type'] ] ) && ! in_array( $e['type'], array( 'tp_province', 'tp_country' ), true ) && '' !== $e['name']; } );
	foreach ( $name_pass( $need2, $prov_q, 'rest' ) as $uid => $x ) { $ents[ $uid ]['qid'] = $x[0]; $ents[ $uid ]['method'] = $x[1]; if ( $x[2] ) { $ents[ $uid ]['multi'] = 1; } }
	/* ── مقدارها برای همهٔ QIDها ── */
	$qids = array_values( array_unique( array_filter( array_column( $ents, 'qid' ) ) ) );
	$vals = array();
	foreach ( array_chunk( $qids, 40 ) as $i => $chunk ) {
		$v = implode( ' ', array_map( function ( $q ) { return 'wd:' . $q; }, $chunk ) );
		$q = "SELECT ?item ?coord ?pop ?popYear ?popRank ?popRef ?area ?areaUnit ?elev ?elevUnit ?capital WHERE {
  VALUES ?item { $v }
  OPTIONAL { ?item wdt:P625 ?coord . }
  OPTIONAL { ?item p:P1082 ?ps . ?ps ps:P1082 ?pop ; wikibase:rank ?popRank . FILTER( ?popRank != wikibase:DeprecatedRank )
             OPTIONAL { ?ps pq:P585 ?popYear . } OPTIONAL { ?ps prov:wasDerivedFrom ?refn . OPTIONAL { ?refn pr:P854 ?popRef1 . } OPTIONAL { ?refn pr:P248 ?popRef2 . } BIND( COALESCE( ?popRef1, ?popRef2 ) AS ?popRef ) } }
  OPTIONAL { ?item p:P2046/psv:P2046 [ wikibase:quantityAmount ?area ; wikibase:quantityUnit ?areaUnit ] . }
  OPTIONAL { ?item p:P2044/psv:P2044 [ wikibase:quantityAmount ?elev ; wikibase:quantityUnit ?elevUnit ] . }
  OPTIONAL { ?item wdt:P36 ?capital . }
}";
		$res = tpf_sparql( $q, 'vals' . $i );
		if ( null === $res ) { echo "✗ پرس‌وجوی مقدار دستهٔ {$i} شکست خورد\n"; continue; }
		foreach ( tpf_rows( $res ) as $r ) { $vals[ tpf_q( $r['item'] ) ][] = $r; }
		echo '  · دستهٔ مقدار ' . ( $i + 1 ) . ': ' . count( $chunk ) . " QID\n";
	}

	/* انتخاب: مختصات نخستین؛ جمعیت رتبهٔ preferred وگرنه تازه‌ترین سال؛ مساحت به km²؛ ارتفاع به m */
	$pick = function ( $rows ) {
		$o = array( 'coord' => '', 'pop' => '', 'pop_year' => '', 'pop_ref' => '', 'area' => '', 'elev' => '', 'capital' => '' );
		$best = null;
		foreach ( $rows as $r ) {
			if ( '' === $o['coord'] && ! empty( $r['coord'] ) && preg_match( '~Point\(([-0-9.]+) ([-0-9.]+)\)~', $r['coord'], $m ) ) { $o['coord'] = round( (float) $m[2], 6 ) . ', ' . round( (float) $m[1], 6 ); }
			if ( ! empty( $r['pop'] ) ) {
				$score = ( false !== strpos( $r['popRank'] ?? '', 'Preferred' ) ? 10000 : 0 ) + (int) substr( (string) ( $r['popYear'] ?? '0' ), 0, 4 );
				if ( null === $best || $score > $best[0] ) { $best = array( $score, $r ); }
			}
			if ( '' === $o['area'] && ! empty( $r['area'] ) ) {
				$u = tpf_q( $r['areaUnit'] ?? '' );
				$k = array( 'Q712226' => 1, 'Q25343' => 1e-6, 'Q35852' => 0.01, 'Q232291' => 2.58999 );   /* km² · m² · هکتار · مایل² */
				if ( isset( $k[ $u ] ) ) { $o['area'] = round( (float) $r['area'] * $k[ $u ], 2 ); }
			}
			if ( '' === $o['elev'] && ! empty( $r['elev'] ) ) {
				$u = tpf_q( $r['elevUnit'] ?? '' );
				if ( 'Q11573' === $u ) { $o['elev'] = (int) round( (float) $r['elev'] ); } elseif ( 'Q3710' === $u ) { $o['elev'] = (int) round( (float) $r['elev'] * 0.3048 ); }
			}
			if ( '' === $o['capital'] && ! empty( $r['capital'] ) ) { $o['capital'] = tpf_q( $r['capital'] ); }
		}
		if ( $best ) {
			$o['pop'] = (int) round( (float) $best[1]['pop'] );
			$o['pop_year'] = ! empty( $best[1]['popYear'] ) ? substr( $best[1]['popYear'], 0, 4 ) : '';
			$o['pop_ref'] = (string) ( $best[1]['popRef'] ?? '' );
		}
		return $o;
	};

	/* ── نگهبان‌ها و وضعیت ── */
	$by_q = array();
	foreach ( $ents as $uid => $e ) { if ( '' !== $e['qid'] ) { $by_q[ $e['qid'] ][] = $uid; } }
	$q2uid = array();
	foreach ( $by_q as $q => $l ) { if ( 1 === count( $l ) ) { $q2uid[ $q ] = $l[0]; } }

	$out  = array();
	$head = array( 'entity_uid', 'post_type', 'name_fa', 'qid', 'qid_method', 'status', 'guards', 'field', 'current', 'proposed', 'action', 'property', 'year', 'reference', 'retrieved', 'approved' );
	$sum  = array();
	foreach ( $ents as $uid => $e ) {
		$guards = array(); $status = 'none';
		$v = '' !== $e['qid'] && isset( $vals[ $e['qid'] ] ) ? $pick( $vals[ $e['qid'] ] ) : null;
		if ( '' !== $e['qid'] ) {
			$status = empty( $e['multi'] ) ? 'auto' : 'suggested';
			if ( count( $by_q[ $e['qid'] ] ) > 1 ) { $status = 'suggested'; $guards[] = 'QID تکراری با ' . implode( ' · ', array_diff( $by_q[ $e['qid'] ], array( $uid ) ) ); }
			if ( $v && '' !== $v['coord'] ) {
				$pf = $e['prov_uid'] ? $bdir . tpb_key( $e['prov_uid'] ) . '.geojson' : '';
				if ( $pf && is_file( $pf ) ) {
					$in = tpb_center_inside( $v['coord'], (string) file_get_contents( $pf ) );
					if ( false === $in ) { $status = 'suggested'; $guards[] = 'نقطه بیرون از GeoJSON استان'; } elseif ( true === $in ) { $guards[] = 'درون استان ✓'; }
				} else { $guards[] = 'GeoJSON استان نیست'; }
				$cf = $bdir . tpb_key( $uid ) . '.geojson';
				if ( 'tp_city' === $e['type'] && is_file( $cf ) ) {
					$in = tpb_center_inside( $v['coord'], (string) file_get_contents( $cf ) );
					if ( false === $in ) { $status = 'suggested'; $guards[] = 'نقطه بیرون از GeoJSON شهر'; } elseif ( true === $in ) { $guards[] = 'درون شهر ✓'; }
				}
			}
			if ( $v && '' !== $v['pop'] && isset( $POP[ $e['type'] ] ) && ( $v['pop'] < $POP[ $e['type'] ][0] || $v['pop'] > $POP[ $e['type'] ][1] ) ) { $status = 'suggested'; $guards[] = 'جمعیت با نوع نمی‌خواند (' . $v['pop'] . ')'; }
		}
		$sum[ $e['type'] ][ $status ] = ( $sum[ $e['type'] ][ $status ] ?? 0 ) + 1;
		if ( ! empty( $e['bad_qid'] ) ) { $guards[] = 'QID داده نادرست: ' . $e['bad_qid']; }
		$base = array( $uid, $e['type'], $e['title'], $e['qid'], $e['method'], $status, implode( ' · ', $guards ) );
		/* QID خودِ موجودیت در external_reference_uri: خالی ← fill؛ نادرست ← diff (فقط گزارش) */
		$cur_uri = trim( (string) get_post_meta( $e['id'], 'external_reference_uri', true ) );
		if ( '' !== $e['qid'] && tpf_qid( $cur_uri ) !== $e['qid'] ) {
			$out[] = array_merge( $base, array( 'external_reference_uri', $cur_uri, 'https://www.wikidata.org/wiki/' . $e['qid'], '' === $cur_uri ? 'fill' : 'diff', 'QID', '', '', $NOW, '' ) );
		} elseif ( '' === $e['qid'] && ! empty( $e['bad_qid'] ) ) {
			$out[] = array_merge( $base, array( 'external_reference_uri', $cur_uri, '', 'diff (QID داده نادرست؛ جایگزین پیدا نشد)', 'QID', '', '', $NOW, '' ) );
		}
		$n0 = count( $out );
		if ( ! $v ) { $out[] = array_merge( $base, array( '', '', '', 'none', '', '', '', $NOW, '' ) ); continue; }

		$fields = array(
			'geo_coordinates' => array( $v['coord'], 'P625', '', '' ),
			'population'      => array( $v['pop'], 'P1082', $v['pop_year'], $v['pop_ref'] ),
			'area_km2'        => array( $v['area'], 'P2046', '', '' ),
			'elevation_m'     => array( $v['elev'], 'P2044', '', '' ),
		);
		foreach ( $fields as $f => $x ) {
			$real = 'area_km2' === $f && 'tp_landscape' === $e['type'] ? 'total_area_km2' : $f;
			if ( '' === (string) $x[0] || ! tpf_has_field( $e['type'], $real ) ) { continue; }
			$cur = $e['cur'][ $f ];
			if ( '' === $cur ) { $act = 'fill'; }
			elseif ( 'geo_coordinates' === $f ) {
				$a = array_map( 'floatval', array_map( 'trim', explode( ',', $cur . ',' ) ) ); $b = array_map( 'floatval', explode( ',', $x[0] ) );
				$act = ( abs( $a[0] - $b[0] ) < 0.05 && abs( $a[1] - $b[1] ) < 0.05 ) ? 'same' : 'diff';
			} else {
				$cn = (float) preg_replace( '/[^0-9.]/', '', strtr( $cur, array( '۰' => 0, '۱' => 1, '۲' => 2, '۳' => 3, '۴' => 4, '۵' => 5, '۶' => 6, '۷' => 7, '۸' => 8, '۹' => 9, '٬' => '', ',' => '' ) ) );
				$act = $cn > 0 && abs( $cn - (float) $x[0] ) / max( $cn, 1 ) < 0.02 ? 'same' : 'diff';
			}
			$out[] = array_merge( $base, array( $real, $cur, (string) $x[0], $act, $x[1], $x[2], $x[3], $NOW, '' ) );
		}
		if ( count( $out ) === $n0 ) { $out[] = array_merge( $base, array( '', '', '', 'none (ویکی‌داده هیچ مقدار P625/P1082/P2046/P2044 ندارد)', '', '', '', $NOW, '' ) ); }
		/* رابطهٔ ۹۶: مرکز استان (P36 ← شهری که همان QID را دارد) */
		if ( 'tp_province' === $e['type'] && '' !== $v['capital'] ) {
			$cap_uid = $q2uid[ $v['capital'] ] ?? '';
			$have    = function_exists( 'topersia_g_capital_id' ) ? (int) topersia_g_capital_id( $e['id'] ) : 0;
			$have_u  = $have ? (string) get_post_meta( $have, 'entity_uid', true ) : '';
			$act     = '' === $cap_uid ? 'none (شهر مرکز با این QID در داده نیست: ' . $v['capital'] . ')' : ( '' === $have_u ? 'fill' : ( $have_u === $cap_uid ? 'same' : 'diff' ) );
			$out[] = array_merge( $base, array( 'rel:96 (مرکز استان)', $have_u, $cap_uid, $act, 'P36', '', '', $NOW, '' ) );
		}
	}

	/* رابطهٔ ۷۱ کشور ↔ استان: استانی که ردیف ندارد */
	global $wpdb;
	$country = get_posts( array( 'post_type' => 'tp_country', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false, 'lang' => 'fa' ) );
	if ( $country ) {
		foreach ( $ents as $uid => $e ) {
			if ( 'tp_province' !== $e['type'] ) { continue; }
			$has = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default WHERE rel_id = 71 AND parent_object_id = %d AND child_object_id = %d", $country[0], $e['id'] ) );
			if ( ! $has ) { $out[] = array( $uid, $e['type'], $e['title'], $e['qid'], $e['method'], 'auto', 'Q794 P150 (تقسیمات) — کشور تنها', 'rel:71 (کشور ↔ استان)', '', (string) get_post_meta( $country[0], 'entity_uid', true ), 'fill', 'P131', '', '', $NOW, '' ); }
		}
	}

	$fh = fopen( $csv, 'w' );
	fwrite( $fh, "\xEF\xBB\xBF" );
	fputcsv( $fh, $head );
	foreach ( $out as $r ) { fputcsv( $fh, $r ); }
	fclose( $fh );

	echo "\nنوشته شد: reference/facts-proposed.csv — " . count( $out ) . " ردیف\n";
	foreach ( $sum as $t => $s ) { echo '  ' . str_pad( $t, 20 ) . ' ' . wp_json_encode( $s ) . "\n"; }
	return;
}

/* ══ report ════════════════════════════════════════════════════════════ */
if ( 'report' === $cmd ) {
	if ( ! is_file( $csv ) ) { echo "اول match\n"; return; }
	$rows = array_map( 'str_getcsv', file( $csv ) );
	$h    = array_shift( $rows );
	$h[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $h[0] );
	$rows = array_map( function ( $r ) use ( $h ) { return array_combine( $h, $r ); }, $rows );
	$st = array(); $act = array(); $diffs = array();
	$ent = array();
	foreach ( $rows as $r ) {
		$ent[ $r['entity_uid'] ] = array( $r['post_type'], $r['status'] );
		if ( '' !== $r['field'] ) {
			$k = $r['field'] . ' · ' . preg_replace( '/ \(.*/', '', $r['action'] );
			$act[ $r['status'] ][ $k ] = ( $act[ $r['status'] ][ $k ] ?? 0 ) + 1;
			if ( 'diff' === $r['action'] ) { $diffs[] = $r; }
		}
	}
	foreach ( $ent as $x ) { $st[ $x[0] ][ $x[1] ] = ( $st[ $x[0] ][ $x[1] ] ?? 0 ) + 1; }
	echo "وضعیت تطبیق به تفکیک نوع:\n";
	foreach ( $st as $t => $s ) { echo '  ' . str_pad( $t, 20 ) . ' ' . wp_json_encode( $s ) . "\n"; }
	echo "\nپیشنهاد به تفکیک وضعیت و فیلد:\n";
	foreach ( $act as $s => $a ) { ksort( $a ); echo "  [$s] " . wp_json_encode( $a, JSON_UNESCAPED_UNICODE ) . "\n"; }
	/* ── پوشش پیش و پس (پس = فقط ردیف‌های auto با action=fill، رابطهٔ ۹۶ هم) ── */
	$fill = array();
	foreach ( $rows as $r ) {
		if ( 'auto' === $r['status'] && 'fill' === $r['action'] && '' !== $r['field'] ) { $fill[ $r['entity_uid'] ][ $r['field'] ] = $r['proposed']; }
	}
	$uid2id = array(); $id2uid = array();
	foreach ( array_keys( $ent ) as $uid ) {
		$id = (int) $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT post_id FROM {$GLOBALS['wpdb']->postmeta} WHERE meta_key='entity_uid' AND meta_value=%s LIMIT 1", $uid ) );
		$uid2id[ $uid ] = $id; $id2uid[ $id ] = $uid;
	}
	$geo_ok = function ( $v ) { return function_exists( 'topersia_geo_point' ) ? (bool) topersia_geo_point( (string) $v ) : '' !== trim( (string) $v ); };
	$val = function ( $uid, $field, $after ) use ( $fill, $uid2id ) {
		if ( $after && isset( $fill[ $uid ][ $field ] ) ) { return $fill[ $uid ][ $field ]; }
		return trim( (string) get_post_meta( $uid2id[ $uid ], $field, true ) );
	};
	$cap = function ( $uid, $after ) use ( $fill, $uid2id ) {
		if ( $after && isset( $fill[ $uid ]['rel:96 (مرکز استان)'] ) ) { return $fill[ $uid ]['rel:96 (مرکز استان)']; }
		$c = function_exists( 'topersia_g_capital_id' ) ? (int) topersia_g_capital_id( $uid2id[ $uid ] ) : 0;
		return $c ? (string) get_post_meta( $c, 'entity_uid', true ) : '';
	};
	$cov = array();
	foreach ( $ent as $uid => $x ) {
		$t = $x[0];
		foreach ( array( 'before' => false, 'after' => true ) as $k => $after ) {
			$own = $geo_ok( $val( $uid, 'geo_coordinates', $after ) );
			$pt  = $own;
			if ( 'tp_province' === $t ) { $c = $cap( $uid, $after ); $pt = '' !== $c && isset( $uid2id[ $c ] ) && $geo_ok( $val( $c, 'geo_coordinates', $after ) ); }
			$row = function_exists( 'topersia_g_type_row' ) ? topersia_g_type_row( $t ) : array();
			$keys = array_values( array_filter( (array) ( $row['stats'] ?? array() ), function ( $k2 ) { return 'province_code' !== $k2 && 0 !== strpos( $k2, 'count:' ); } ) );
			$filled = 0;
			foreach ( $keys as $k2 ) { $filled += 'capital' === $k2 ? ( '' !== $cap( $uid, $after ) ? 1 : 0 ) : ( '' !== $val( $uid, $k2, $after ) && '0' !== $val( $uid, $k2, $after ) ? 1 : 0 ); }
			$cov[ $t ][ $k ]['n']       = ( $cov[ $t ][ $k ]['n'] ?? 0 ) + 1;
			$cov[ $t ][ $k ]['نشان']    = ( $cov[ $t ][ $k ]['نشان'] ?? 0 ) + ( $pt ? 1 : 0 );
			$cov[ $t ][ $k ]['هوا']     = ( $cov[ $t ][ $k ]['هوا'] ?? 0 ) + ( $own ? 1 : 0 );
			$cov[ $t ][ $k ]['کیفیت هوا'] = ( $cov[ $t ][ $k ]['کیفیت هوا'] ?? 0 ) + ( $pt ? 1 : 0 );
			$cov[ $t ][ $k ]['نشانگر']  = ( $cov[ $t ][ $k ]['نشانگر'] ?? 0 ) + ( $own ? 1 : 0 );
			$cov[ $t ][ $k ]['۴ خانه'] = ( $cov[ $t ][ $k ]['۴ خانه'] ?? 0 ) + ( $filled >= 4 ? 1 : 0 );
		}
	}
	echo "\nپوشش پیش ← پس (فقط auto · fill؛ «۴ خانه» = خانه‌های فعلی ماتریس نوع پر، بی مشتق):\n";
	foreach ( $cov as $t => $c ) {
		$line = array();
		foreach ( array( 'نشان', 'هوا', 'کیفیت هوا', 'نشانگر', '۴ خانه' ) as $m ) { $line[] = $m . ' ' . $c['before'][ $m ] . '←' . $c['after'][ $m ]; }
		echo '  ' . str_pad( $t, 20 ) . ' n=' . $c['before']['n'] . ' · ' . implode( ' · ', $line ) . "\n";
	}
	echo "\nاختلاف‌ها (نوشته نمی‌شوند): " . count( $diffs ) . "\n";
	foreach ( array_slice( $diffs, 0, 40 ) as $r ) { echo "  {$r['name_fa']} · {$r['field']}: داده «{$r['current']}» ↔ ویکی‌داده «{$r['proposed']}» ({$r['qid']})\n"; }
	return;
}

/* ══ mark · apply · rollback · verify — P5-D394 (دستهٔ ۲۲ب) ══════════════════════════ */
require_once __DIR__ . '/lib/tp-facts-lib.php';

if ( 'mark' === $cmd ) {
	list( $h, $rows ) = tpf_csv_load( $csv );
	$n = tpf_mark( $rows );
	tpf_csv_save( $csv, $h, $rows );
	echo 'approved: yes=' . $n['yes'] . ' · yes-replace=' . $n['yes-replace'] . ' · جمع ' . ( $n['yes'] + $n['yes-replace'] ) . "\n";
	return;
}

if ( 'apply' === $cmd ) {
	$dry = isset( $args[1] ) && in_array( $args[1], array( 'dry-run', 'dry', '--dry-run' ), true );
	list( $h, $rows ) = tpf_csv_load( $csv );
	$res = tpf_apply( $rows, array( 'dry' => $dry, 'backup' => true, 'expect' => 383 ) );
	if ( isset( $res['error'] ) ) { echo '✗ ' . $res['error'] . "\n"; return; }
	echo ( $dry ? "dry-run (هیچ نوشتنی نشد):\n" : "اجرای واقعی:\n" );
	if ( $dry ) { foreach ( $res['lines'] as $l ) { echo '  ' . $l . "\n"; } }
	ksort( $res['by'] );
	foreach ( $res['by'] as $k => $v ) { echo '  ' . $k . ' = ' . $v . "\n"; }
	if ( ! $dry ) { echo "پشتیبان: {$res['backup']}\nلاگ: {$res['logfile']}\nبرگشت: wp eval-file scripts/tp-facts.php rollback \"{$res['logfile']}\"\n"; }
	return;
}

if ( 'rollback' === $cmd ) {
	$lf = isset( $args[1] ) ? $args[1] : '';
	if ( '' === $lf || ! is_file( $lf ) ) { echo "rollback <مسیر لاگ>\n"; return; }
	echo wp_json_encode( tpf_rollback( $lf ), JSON_UNESCAPED_UNICODE ) . "\n";
	return;
}

if ( 'verify' === $cmd ) {
	$prov = array();
	foreach ( get_posts( array( 'post_type' => 'tp_province', 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false, 'lang' => 'fa' ) ) as $p ) { $prov[ $p->ID ] = array( get_the_title( $p ), tpf_qid( get_post_meta( $p->ID, 'external_reference_uri', true ) ), get_permalink( $p ) ); }
	$qs = array_values( array_unique( array_filter( array_column( $prov, 1 ) ) ) );
	$res = tpf_sparql( 'SELECT ?item ?label ?root WHERE { VALUES ?item { ' . implode( ' ', array_map( function ( $q ) { return 'wd:' . $q; }, $qs ) ) . ' } OPTIONAL { ?item rdfs:label ?label FILTER( lang( ?label ) = "en" ) } OPTIONAL { ?item wdt:P31/wdt:P279* wd:Q1344695 . BIND( "yes" AS ?root ) } }', 'verify' . substr( md5( implode( ',', $qs ) ), 0, 6 ) );
	$ok = array(); $lab = array();
	foreach ( tpf_rows( $res ) as $r ) { $q = tpf_q( $r['item'] ); if ( ! empty( $r['root'] ) ) { $ok[ $q ] = 1; } if ( ! empty( $r['label'] ) ) { $lab[ $q ] = $r['label']; } }
	$good = 0; $bad = array();
	foreach ( $prov as $id => $x ) { if ( isset( $ok[ $x[1] ] ) ) { $good++; } else { $bad[] = $x[0] . ' ' . $x[1] . ' (' . ( $lab[ $x[1] ] ?? '?' ) . ')'; } }
	echo 'استان ایران (P31/P279* = Q1344695): ' . $good . ' از ' . count( $prov ) . "\n";
	foreach ( $bad as $b ) { echo '  ✗ ' . $b . "\n"; }
	foreach ( array( 'گیلان', 'مازندران', 'گلستان', 'البرز', 'آذربایجان شرقی' ) as $name ) {
		foreach ( $prov as $id => $x ) {
			if ( tpb_norm( $x[0] ) !== tpb_norm( $name ) ) { continue; }
			$c = curl_init( $x[2] );
			curl_setopt_array( $c, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => array( 'Accept-Encoding: identity' ), CURLOPT_PROXY => '' ) );
			$html = (string) curl_exec( $c );
			curl_close( $c );
			preg_match_all( '~<script type="application/ld\+json"[^>]*>(.*?)</script>~s', $html, $m );
			$same = array();
			foreach ( $m[1] as $js ) { $j = json_decode( $js, true ); $walk = function ( $n ) use ( &$walk, &$same ) { if ( is_array( $n ) ) { foreach ( $n as $k => $v ) { if ( 'sameAs' === $k ) { foreach ( (array) $v as $u ) { $same[] = $u; } } else { $walk( $v ); } } } }; $walk( $j ); }
			$want = 'https://www.wikidata.org/wiki/' . $x[1];
			echo '  ' . $name . ' ' . $x[1] . ' (' . ( $lab[ $x[1] ] ?? '?' ) . '): sameAs=' . wp_json_encode( $same ) . ' ' . ( in_array( $want, $same, true ) ? '✓' : '✗' ) . ' · ' . strlen( $html ) . " بایت\n";
		}
	}
	return;
}

echo "match · report · mark · apply [dry-run] · rollback <log> · verify\n";
