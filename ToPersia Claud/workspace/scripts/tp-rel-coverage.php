<?php
/**
 * P5-D503: رابطه‌های جت‌انجین بی گروه نمایش — فقط‌خواندنی؛ چیزی نمی‌نویسد.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-rel-coverage.php [gaps]
 *
 * بی آرگومان: جدول همهٔ رابطه‌ها. gaps: فقط شکاف‌ها، به شکل PHP برای نقشه.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

require_once __DIR__ . '/lib/tp-rel-coverage-lib.php';

$rows = tp_rel_cov_rows();
$gaps = tp_rel_cov_gaps( $rows );

if ( in_array( 'gaps', isset( $args ) ? (array) $args : array(), true ) ) {
	$by = array();
	foreach ( $gaps as $g ) { $by[ $g[1] ][] = "array( {$g[0]}, '{$g[2]}' )"; }
	foreach ( $by as $type => $list ) { echo "\t\t\t'{$type}' => array( " . implode( ', ', $list ) . " ),\n"; }
	return;
}

printf( "رابطهٔ فعال: %d · با یال: %d · شکاف بی‌دلیل: %d\n", count( $rows ), count( array_filter( $rows, function ( $r ) { return $r['real'] || $r['sample']; } ) ), count( $gaps ) );
echo "رابطه\tوالد\tفرزند\tیال واقعی\tیال نمونه\tگروه والد\tگروه فرزند\tدلیل بی‌گروهی والد\tدلیل بی‌گروهی فرزند\n";
foreach ( $rows as $r ) {
	printf( "%d\t%s\t%s\t%d\t%d\t%s\t%s\t%s\t%s\n", $r['id'], $r['p'], $r['c'], $r['real'], $r['sample'], '' === $r['gp'] ? '—' : $r['gp'], '' === $r['gc'] ? '—' : $r['gc'], '' === $r['gp'] ? $r['wp'] : '', '' === $r['gc'] ? $r['wc'] : '' );
}
