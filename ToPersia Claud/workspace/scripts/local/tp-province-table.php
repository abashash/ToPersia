<?php
/**
 * جدول ۳۱ استان: تطبیق فعلی (reference/province-osm.csv) در برابر 76b40e11 (تطبیق نام) و f19dd40e (تطبیق ISO نادرست) — فقط گزارش  (فقط محلی؛ بی نوشتن)
 *
 *   php scripts/local/tp-province-table.php
 *
 * ستون‌ها: نام · رابطهٔ OSM · ISO3166-2 خود OSM · نگهبان ۱ · نگهبان ۲ (منبع مرکز) · شناسهٔ همین استان در 76b40e11 · شناسهٔ f19dd40e · توضیح ناهمخوانی.
 * شناسه‌های قدیمی با `git show` از تاریخ خوانده می‌شوند (نه حافظه).
 */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }

$ws   = dirname( __DIR__, 2 );
$root = dirname( $ws, 2 );
$csv  = function ( $text ) {
	$rows = array();
	$fh   = fopen( 'php://temp', 'r+' ); fwrite( $fh, $text ); rewind( $fh );
	$hd   = fgetcsv( $fh, 0, ',', '"', '\\' );
	while ( ( $r = fgetcsv( $fh, 0, ',', '"', '\\' ) ) ) { if ( count( $r ) === count( $hd ) ) { $rows[ $r[0] ] = array_combine( $hd, $r ); } }
	fclose( $fh );
	return $rows;
};
$old = function ( $rev ) use ( $root, $csv ) {
	return $csv( (string) shell_exec( 'git -C ' . escapeshellarg( $root ) . ' show ' . escapeshellarg( $rev . ':ToPersia Claud/workspace/reference/osm-boundary-map.csv' ) ) );
};
$now  = $csv( (string) file_get_contents( $ws . '/reference/province-osm.csv' ) );
$a    = $old( '76b40e11' );
$b    = $old( 'f19dd40e' );
$note = function ( $uid, $new ) use ( $a, $b ) {
	$ida = isset( $a[ $uid ] ) ? $a[ $uid ]['osm_id'] : '';
	$idb = isset( $b[ $uid ] ) ? $b[ $uid ]['osm_id'] : '';
	$out = array();
	if ( '' === $ida ) { $out[] = '76b40e11: بی تطبیق'; } elseif ( $ida !== $new ) { $out[] = "با 76b40e11 فرق دارد ($ida)"; }
	if ( '' !== $idb && $idb !== $new ) { $out[] = "f19dd40e غلط بود ($idb)"; }
	return $out ? implode(' · ', $out ) : 'همخوان با 76b40e11';
};

printf( "%-26s | %-9s | %-6s | %-4s | %-24s | %-9s | %-9s | %s\n", 'نام', 'رابطه', 'ISO-OSM', 'گ۱', 'گ۲ (منبع مرکز)', '76b40e11', 'f19dd40e', 'توضیح' );
$diff = 0;
foreach ( $now as $uid => $r ) {
	$ida = isset( $a[ $uid ] ) ? $a[ $uid ]['osm_id'] : '';
	$idb = isset( $b[ $uid ] ) ? $b[ $uid ]['osm_id'] : '';
	if ( '' !== $ida && $ida !== $r['osm_relation'] ) { $diff++; }
	printf( "%-26s | %-9s | %-6s | %-4s | %-24s | %-9s | %-9s | %s\n", $r['name_fa'], $r['osm_relation'], $r['ISO3166-2'], $r['guard1_unique'], $r['guard2_center'], $ida, $idb, $note( $uid, $r['osm_relation'] ) );
}
echo "\nاستان: " . count( $now ) . ' · ناهمخوان با 76b40e11: ' . $diff . ' · f19dd40e غلط: ' . count( array_filter( $now, function ( $r, $u ) use ( $b ) { return isset( $b[ $u ] ) && '' !== $b[ $u ]['osm_id'] && $b[ $u ]['osm_id'] !== $r['osm_relation']; }, ARRAY_FILTER_USE_BOTH ) ) . "\n";
