<?php
/**
 * پاک‌کردن دادۀ نمایشی — دستۀ 51 بخش ۱ (P5-D548). فقط محلی (topersia.local).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-demo-purge.php           ← dry-run: فهرست
 *   … eval-file scripts/tp-demo-purge.php confirm                                                     ← پاک‌کردن کامل
 *
 * فقط پست‌هایی که `_tp_demo` دارند (ساختۀ tp-demo-seed.php)، با همۀ ترجمه‌ها و فراداده؛ نمونه‌های دیگر
 * (`_tp_sample` بی `_tp_demo`، ساختۀ tp-samples.php) دست نمی‌خورند. رابطه ندارند، پس ردیف رابطه‌ای نمی‌ماند؛
 * با این حال ردیف jet_rel_default هر دو سو و ردیف icl_translations هم پاک می‌شود.
 */
if ( 'local' !== wp_get_environment_type() || false === strpos( (string) home_url(), 'topersia.local' ) ) { echo "فقط topersia.local\n"; return; }

global $wpdb;
$confirm = 'confirm' === (string) ( $args[0] ?? '' );

$ids = array_map( 'intval', $wpdb->get_col( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tp_demo'" ) );
$by  = array();
foreach ( $ids as $id ) {
	$t = (string) get_post_type( $id );
	$by[ $t ] = ( $by[ $t ] ?? 0 ) + 1;
}
ksort( $by );
foreach ( $by as $t => $n ) {
	printf( "%-22s %d\n", $t, $n );
}
echo ( $confirm ? 'deleting: ' : 'would delete (dry-run): ' ), count( $ids ), "\n";

if ( ! $confirm || ! $ids ) {
	return;
}

$rel = $wpdb->prefix . 'jet_rel_default';
$has_rel = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rel ) );
foreach ( $ids as $id ) {
	if ( $has_rel ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$rel} WHERE parent_object_id = %d OR child_object_id = %d", $id, $id ) );
	}
	$type = (string) get_post_type( $id );
	wp_delete_post( $id, true );
	$wpdb->delete( $wpdb->prefix . 'icl_translations', array( 'element_id' => $id, 'element_type' => 'post_' . $type ), array( '%d', '%s' ) );
}
$left = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_tp_demo'" );
echo 'left: ', $left, "\n";
