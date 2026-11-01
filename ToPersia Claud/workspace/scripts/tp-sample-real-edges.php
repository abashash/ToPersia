<?php
/**
 * برداشتن همهٔ یال‌های نمونه↔واقعی — P5-D488 (canary مطلق). فقط topersia.local. idempotent؛ پیش‌فرض dry-run.
 *
 *   wp eval-file scripts/tp-sample-real-edges.php [apply]
 *
 * فقط **یال** برداشته می‌شود (هرگز پست)، با API جت‌انجین (`relation->delete_rows( والد، فرزند )`)، از هر نوع رابطه.
 * یال نمونه↔نمونه و واقعی↔واقعی دست نمی‌خورد. پس از apply مشتق‌های پست واقعی درگیر (گالری تجمیعی، نمایهٔ نقشه، هش) تازه می‌شوند.
 * پیش از apply پشتیبان (`scripts/tp-backup.php`) بگیر؛ `--` را WP-CLI از $args جدا می‌کند، پس apply موضعی است.
 */
global $wpdb;
if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { echo "فقط نسخهٔ محلی\n"; return; }
$apply  = in_array( 'apply', (array) ( $args ?? array() ), true );
$sample = array_flip( array_map( 'intval', $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tp_sample'" ) ) );
$rows   = array();
$by     = array();
$real   = array();
foreach ( $wpdb->get_results( "SELECT rel_id, parent_object_id p, child_object_id c FROM {$wpdb->prefix}jet_rel_default ORDER BY rel_id, parent_object_id, child_object_id", ARRAY_A ) as $e ) {
	$ps = isset( $sample[ (int) $e['p'] ] );
	$cs = isset( $sample[ (int) $e['c'] ] );
	if ( $ps === $cs ) { continue; }
	$rows[] = $e;
	$by[ (int) $e['rel_id'] ] = ( $by[ (int) $e['rel_id'] ] ?? 0 ) + 1;
	$real[ $ps ? (int) $e['c'] : (int) $e['p'] ] = 1;
}
echo ( $apply ? 'حالت: اجرای واقعی' : 'حالت: dry-run' ) . ' — یال نمونه↔واقعی: ' . count( $rows ) . ' · پست واقعی درگیر: ' . count( $real ) . "\n";
foreach ( $by as $rid => $n ) { echo "  رابطهٔ {$rid}: {$n}\n"; }
echo '  پست‌های واقعی: ' . implode( ' ', array_map( function ( $i ) { return '#' . $i; }, array_keys( $real ) ) ) . "\n";
if ( ! $apply ) { echo "(dry-run — برای برداشتن: آرگومان apply، پس از پشتیبان)\n"; return; }

$gone = 0;
foreach ( $rows as $e ) {
	$o = topersia_relation( (int) $e['rel_id'] );
	if ( ! $o ) { echo "  ✗ رابطهٔ #{$e['rel_id']} نیست\n"; continue; }
	$o->delete_rows( (int) $e['p'], (int) $e['c'] );
	$left = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default WHERE rel_id=%d AND parent_object_id=%d AND child_object_id=%d", $e['rel_id'], $e['p'], $e['c'] ) );
	$gone += $left ? 0 : 1;
}
foreach ( array_keys( $real ) as $rid ) {
	if ( ! get_post( $rid ) ) { continue; }
	if ( function_exists( 'topersia_geo_index_put' ) ) { topersia_geo_index_put( $rid ); }
	if ( function_exists( 'topersia_content_hash' ) ) { topersia_content_hash( $rid, true ); }
}
if ( function_exists( 'topersia_g_gallery_agg_affected' ) ) {
	$aff = array();
	foreach ( array_keys( $real ) as $rid ) { if ( get_post( $rid ) ) { foreach ( topersia_g_gallery_agg_affected( $rid ) as $a ) { $aff[ (int) $a ] = 1; } } }
	$aff = array_keys( $aff );
	usort( $aff, function ( $x, $y ) { return topersia_g_gallery_agg_rank( $x ) <=> topersia_g_gallery_agg_rank( $y ); } );
	foreach ( $aff as $a ) { topersia_g_gallery_agg_rebuild( $a, false ); }
	echo 'گالری تجمیعی بازسازی شد: ' . count( $aff ) . " پست\n";
}
if ( function_exists( 'topersia_geo_index_stamp' ) ) { topersia_geo_index_stamp( true ); }
clean_post_cache( 0 );
echo "برداشته شد: {$gone} از " . count( $rows ) . "\n";
