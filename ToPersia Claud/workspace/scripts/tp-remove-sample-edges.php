<?php
/**
 * برداشتن یال‌های «پست واقعی ← نمونه» — دستهٔ 39-ب · P5-D456. فقط topersia.local. idempotent؛ پیش‌فرض dry-run.
 *
 *   wp eval-file scripts/tp-remove-sample-edges.php <شناسهٔ پست واقعی> [apply]
 *
 * فقط **یال** برداشته می‌شود (هرگز پست)، با API خود جت‌انجین (`relation->delete_rows( والد، فرزند )`):
 * هر یال ‹پست→فرزند› که فرزندش `_tp_sample` دارد. یال‌های پست واقعی دست نمی‌خورد. در پایان (apply) گالری تجمیعی بازسازی می‌شود.
 * `--` را WP-CLI از $args جدا می‌کند؛ پس apply موضعی است.
 */
global $wpdb;
if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { echo "فقط نسخهٔ محلی\n"; return; }

$pid   = isset( $args[0] ) ? (int) $args[0] : 0;
$apply = in_array( 'apply', $args, true );
$post  = $pid ? get_post( $pid ) : null;
if ( ! $post || get_post_meta( $pid, '_tp_sample', true ) ) { echo "پست واقعی پیدا نشد (یا نمونه است)\n"; return; }
echo ( $apply ? 'حالت: اجرای واقعی' : 'حالت: dry-run' ) . " — #{$pid} «{$post->post_title}»\n";

$rows = $wpdb->get_results( $wpdb->prepare(
	"SELECT d.rel_id, d.child_object_id c, p.post_type t, p.post_title ti FROM {$wpdb->prefix}jet_rel_default d
	 JOIN {$wpdb->postmeta} s ON s.post_id=d.child_object_id AND s.meta_key='_tp_sample'
	 LEFT JOIN {$wpdb->posts} p ON p.ID=d.child_object_id WHERE d.parent_object_id=%d ORDER BY d.rel_id, d.child_object_id", $pid ), ARRAY_A );

echo 'یال‌های «واقعی ← نمونه»: ' . count( $rows ) . "\n";
foreach ( $rows as $r ) { echo "  #{$r['rel_id']}: #{$r['c']} «{$r['ti']}» ({$r['t']}) — نمونه\n"; }

if ( ! $apply ) { echo "\n(dry-run — برای برداشتن: آرگومان apply، پس از پشتیبان)\n"; return; }

$removed = 0;
foreach ( $rows as $r ) {
	$object = topersia_relation( (int) $r['rel_id'] );
	if ( ! $object ) { echo "  ✗ رابطهٔ #{$r['rel_id']} نیست\n"; continue; }
	$object->delete_rows( $pid, (int) $r['c'] );
	$gone = ! $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default WHERE rel_id=%d AND parent_object_id=%d AND child_object_id=%d", $r['rel_id'], $pid, $r['c'] ) );
	echo ( $gone ? '  ✓' : '  ✗' ) . " برداشته شد #{$r['rel_id']} {$pid}→{$r['c']}\n";
	$removed += $gone ? 1 : 0;
}
if ( function_exists( 'topersia_g_gallery_agg_rebuild' ) ) {
	topersia_g_gallery_agg_rebuild( $pid, true );
	echo 'گالری تجمیعی بازسازی شد — افزوده‌ها: ' . count( topersia_g_gallery_agg_stored( $pid ) ) . "\n";
}
echo "برداشته شد: {$removed} از " . count( $rows ) . "\n";
