<?php
/**
 * شمار موجودیت‌های بی نقطهٔ نشان، به تفکیک نوع — P5-D383 (فقط خواندنی؛ فقط topersia.local)
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/neshan-gaps.php
 *
 * همان قاعدهٔ رندر (`topersia_g_routing_point()`): نوع نقطه‌ای و شهر ← مختصات خودش؛ استان ← مرکز استان؛ پهنهٔ دیگر ←
 * نقطهٔ نماینده در geo_coordinates. نوع‌های بی جعبهٔ مسیریابی (کشور، scope none) شمرده نمی‌شوند.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }
if ( ! function_exists( 'topersia_g_routing_point' ) ) { fwrite( STDERR, "رندرِ نصب‌شده P5-D383 را ندارد — اول همگام‌سازی\n" ); exit( 2 ); }

$rows  = array();
$total = array( 'all' => 0, 'none' => 0 );

foreach ( topersia_g_type_matrix() as $type => $row ) {

	$scope = isset( $row['scope'] ) ? $row['scope'] : 'area';

	if ( 'none' === $scope || 'tp_country' === $type ) {
		continue;
	}

	$ids  = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false, 'lang' => apply_filters( 'wpml_default_language', 'fa' ) ) );
	$miss = array();

	foreach ( $ids as $pid ) {
		if ( ! topersia_g_routing_point( $pid ) ) {
			$miss[] = get_the_title( $pid );
		}
	}

	$rows[ $type ] = array( 'kind' => topersia_g_kind_label_for_type( $type ), 'scope' => $scope, 'posts' => count( $ids ), 'no_point' => count( $miss ), 'sample' => array_slice( $miss, 0, 5 ) );
	$total['all']  += count( $ids );
	$total['none'] += count( $miss );
}

echo wp_json_encode( array( 'types' => $rows, 'total' => $total ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

function topersia_g_kind_label_for_type( $type ) {
	$l = topersia_g_kind_labels();
	return isset( $l[ $type ] ) ? $l[ $type ] : $type;
}
