<?php
/**
 * بنچمارک جدول مشتق مکانی — P5-D334 · ۱۲ اکتبر.  فقط محلی؛ فقط خواندن/نوشتن جدول‌های موقت `…tpbench_*` که در پایان پاک می‌شوند.
 *
 *   php wp-cli.phar --path=… --url=… eval-file scripts/geo-bench.php [rows=200000]
 *
 * A) DECIMAL(lat) + DECIMAL(lng) با نمایهٔ ترکیبی (lat,lng) و (post_type,lat,lng)
 * B) POINT + SPATIAL INDEX (MBRContains)
 * هر دو: ستون post_type، province_id، city_id با نمایهٔ جدا. داده: ۲۰۰ هزار نقطهٔ ساختگی در کادر ایران، خوشه‌ای (شهر)،
 * نوع‌ها با وزن واقع‌گرایانه. پرس‌وجوها: (۱) قاب دید نقشه ≈ ۲°×۳° (۲) همان قاب با فهرست نوع (۳) زمینهٔ استان یا شهر (R01)
 * (۴) نزدیک‌ترین‌های یک نقطه (قاب ۰٫۲°، مرتب با فاصله). خروجی JSON.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }

global $wpdb;
$n   = isset( $args[0] ) ? max( 1000, (int) $args[0] ) : 200000;
$ta  = $wpdb->prefix . 'tpbench_a';
$tb  = $wpdb->prefix . 'tpbench_b';
$cs  = $wpdb->get_charset_collate();
mt_srand( 20261012 );

$wpdb->query( "DROP TABLE IF EXISTS {$ta}" );
$wpdb->query( "DROP TABLE IF EXISTS {$tb}" );
$wpdb->query( "CREATE TABLE {$ta} ( id INT UNSIGNED NOT NULL AUTO_INCREMENT, post_type VARCHAR(30) NOT NULL, lat DECIMAL(8,6) NOT NULL, lng DECIMAL(9,6) NOT NULL, province_id INT UNSIGNED NOT NULL, city_id INT UNSIGNED NOT NULL,
	PRIMARY KEY (id), KEY ll (lat,lng), KEY tll (post_type,lat,lng), KEY prov (province_id,post_type), KEY city (city_id,post_type) ) ENGINE=InnoDB {$cs}" );
$wpdb->query( "CREATE TABLE {$tb} ( id INT UNSIGNED NOT NULL AUTO_INCREMENT, post_type VARCHAR(30) NOT NULL, pt POINT NOT NULL, province_id INT UNSIGNED NOT NULL, city_id INT UNSIGNED NOT NULL,
	PRIMARY KEY (id), SPATIAL INDEX sp (pt), KEY tp (post_type), KEY prov (province_id,post_type), KEY city (city_id,post_type) ) ENGINE=InnoDB {$cs}" );

$types = array( 'tp_village' => 40, 'tp_monument' => 14, 'tp_city' => 8, 'tp_ancient_site' => 7, 'tp_mountain' => 6, 'tp_religious_site' => 5, 'tp_district' => 5, 'tp_water_body' => 4, 'tp_natural_feature' => 4, 'tp_museum' => 3, 'tp_desert' => 2, 'tp_forest_plain' => 2 );
$pool  = array(); foreach ( $types as $t => $w ) { for ( $i = 0; $i < $w; $i++ ) { $pool[] = $t; } }
/* ۱۰۰۰ مرکز شهری در کادر ایران، هر کدام در یک استان از ۳۱؛ هر نقطه کنار یکی از مرکزها (گسترش ~۰٫۳°) */
$centres = array();
for ( $c = 1; $c <= 1000; $c++ ) { $centres[ $c ] = array( 25.3 + mt_rand( 0, 1450 ) / 100, 44.2 + mt_rand( 0, 1880 ) / 100, 1 + ( $c % 31 ) ); }

$rows = array();
$t0   = microtime( true );
for ( $i = 0; $i < $n; $i++ ) {
	$c   = mt_rand( 1, 1000 );
	$lat = min( 40.4, max( 25.1, $centres[ $c ][0] + ( mt_rand( -300, 300 ) / 1000 ) ) );
	$lng = min( 63.3, max( 44.1, $centres[ $c ][1] + ( mt_rand( -300, 300 ) / 1000 ) ) );
	$rows[] = array( $pool[ mt_rand( 0, count( $pool ) - 1 ) ], round( $lat, 6 ), round( $lng, 6 ), $centres[ $c ][2], $c );
}
$gen = microtime( true ) - $t0;

$ins = function ( $table, $mk ) use ( $wpdb, $rows ) {
	$t = microtime( true );
	foreach ( array_chunk( $rows, 2000 ) as $chunk ) {
		$vals = array();
		foreach ( $chunk as $r ) { $vals[] = $mk( $r ); }
		$wpdb->query( "INSERT INTO {$table} VALUES " . implode( ',', $vals ) );
	}
	return microtime( true ) - $t;
};
$ia = $ins( $ta, function ( $r ) { return "(NULL,'{$r[0]}',{$r[1]},{$r[2]},{$r[3]},{$r[4]})"; } );
$ib = $ins( $tb, function ( $r ) { return "(NULL,'{$r[0]}',ST_GeomFromText('POINT({$r[2]} {$r[1]})'),{$r[3]},{$r[4]})"; } );
$wpdb->query( "ANALYZE TABLE {$ta}, {$tb}" );

$size = function ( $table ) use ( $wpdb ) {
	$r = $wpdb->get_row( $wpdb->prepare( "SELECT DATA_LENGTH d, INDEX_LENGTH i FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s", $table ), ARRAY_A );
	return array( 'data_mb' => round( $r['d'] / 1048576, 1 ), 'index_mb' => round( $r['i'] / 1048576, 1 ) );
};

$fam = array( 'tp_monument', 'tp_religious_site', 'tp_ancient_site', 'tp_museum' );
$in  = "'" . implode( "','", $fam ) . "'";
$q   = array(
	'bbox' => array(
		'A' => function ( $w ) use ( $ta ) { return "SELECT id,lat,lng FROM {$ta} WHERE lat BETWEEN {$w[0]} AND {$w[1]} AND lng BETWEEN {$w[2]} AND {$w[3]}"; },
		'B' => function ( $w ) use ( $tb ) { return "SELECT id,ST_Y(pt),ST_X(pt) FROM {$tb} WHERE MBRContains(ST_GeomFromText('POLYGON(({$w[2]} {$w[0]},{$w[3]} {$w[0]},{$w[3]} {$w[1]},{$w[2]} {$w[1]},{$w[2]} {$w[0]}))'), pt)"; },
	),
	'bbox+type' => array(
		'A' => function ( $w ) use ( $ta, $in ) { return "SELECT id,lat,lng FROM {$ta} WHERE post_type IN ({$in}) AND lat BETWEEN {$w[0]} AND {$w[1]} AND lng BETWEEN {$w[2]} AND {$w[3]}"; },
		'B' => function ( $w ) use ( $tb, $in ) { return "SELECT id,ST_Y(pt),ST_X(pt) FROM {$tb} WHERE post_type IN ({$in}) AND MBRContains(ST_GeomFromText('POLYGON(({$w[2]} {$w[0]},{$w[3]} {$w[0]},{$w[3]} {$w[1]},{$w[2]} {$w[1]},{$w[2]} {$w[0]}))'), pt)"; },
	),
	'context-province' => array(
		'A' => function ( $w, $c ) use ( $ta, $in ) { return "SELECT id,lat,lng FROM {$ta} WHERE province_id = {$c[0]} AND post_type IN ({$in},'tp_city','tp_mountain','tp_water_body','tp_desert')"; },
		'B' => function ( $w, $c ) use ( $tb, $in ) { return "SELECT id,ST_Y(pt),ST_X(pt) FROM {$tb} WHERE province_id = {$c[0]} AND post_type IN ({$in},'tp_city','tp_mountain','tp_water_body','tp_desert')"; },
	),
	'context-city' => array(
		'A' => function ( $w, $c ) use ( $ta, $in ) { return "SELECT id,lat,lng FROM {$ta} WHERE city_id = {$c[1]} AND post_type IN ({$in},'tp_district','tp_village')"; },
		'B' => function ( $w, $c ) use ( $tb, $in ) { return "SELECT id,ST_Y(pt),ST_X(pt) FROM {$tb} WHERE city_id = {$c[1]} AND post_type IN ({$in},'tp_district','tp_village')"; },
	),
	'nearest-20' => array(
		'A' => function ( $w, $c, $p ) use ( $ta ) { return "SELECT id,lat,lng FROM {$ta} WHERE lat BETWEEN " . ( $p[0] - 0.2 ) . ' AND ' . ( $p[0] + 0.2 ) . ' AND lng BETWEEN ' . ( $p[1] - 0.2 ) . ' AND ' . ( $p[1] + 0.2 ) . " ORDER BY POW(lat-{$p[0]},2)+POW(lng-{$p[1]},2) LIMIT 20"; },
		'B' => function ( $w, $c, $p ) use ( $tb ) { $a = $p[0] - 0.2; $b = $p[0] + 0.2; $l = $p[1] - 0.2; $r = $p[1] + 0.2; return "SELECT id,ST_Y(pt),ST_X(pt) FROM {$tb} WHERE MBRContains(ST_GeomFromText('POLYGON(({$l} {$a},{$r} {$a},{$r} {$b},{$l} {$b},{$l} {$a}))'), pt) ORDER BY POW(ST_Y(pt)-{$p[0]},2)+POW(ST_X(pt)-{$p[1]},2) LIMIT 20"; },
	),
);

$runs   = 60;
$result = array();
foreach ( $q as $name => $variants ) {
	foreach ( $variants as $v => $mk ) {
		$times = array(); $count = 0;
		mt_srand( 77 );
		for ( $i = 0; $i < $runs + 5; $i++ ) {
			$cen = $centres[ mt_rand( 1, 1000 ) ];
			$w   = array( round( $cen[0] - 1, 4 ), round( $cen[0] + 1, 4 ), round( $cen[1] - 1.5, 4 ), round( $cen[1] + 1.5, 4 ) );
			$sql = $mk( $w, array( $cen[2], mt_rand( 1, 1000 ) ), array( $cen[0], $cen[1] ) );
			$t   = microtime( true );
			$res = $wpdb->get_results( $sql, ARRAY_N );
			$ms  = ( microtime( true ) - $t ) * 1000;
			if ( $i >= 5 ) { $times[] = $ms; $count += count( $res ); }   /* پنج اجرای گرم‌کردن */
		}
		sort( $times );
		$result[ $name ][ $v ] = array( 'avg_ms' => round( array_sum( $times ) / count( $times ), 2 ), 'p95_ms' => round( $times[ (int) floor( count( $times ) * 0.95 ) - 1 ], 2 ), 'rows_avg' => round( $count / count( $times ), 1 ) );
	}
}

$out = array( 'rows' => $n, 'server' => $wpdb->db_version() . ' ' . $wpdb->get_var( 'SELECT VERSION()' ), 'insert_s' => array( 'A' => round( $ia, 1 ), 'B' => round( $ib, 1 ) ), 'size' => array( 'A' => $size( $ta ), 'B' => $size( $tb ) ), 'runs_per_query' => $runs, 'results' => $result );

$wpdb->query( "DROP TABLE IF EXISTS {$ta}" );
$wpdb->query( "DROP TABLE IF EXISTS {$tb}" );

echo wp_json_encode( $out, JSON_PRETTY_PRINT ), "\n";
