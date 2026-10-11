<?php
/* قاعدۀ ۱۶ — عکس همۀ پست‌های پیش از آزمون (شناسه · نوع · وضعیت · زمان تغییر) و مقایسه.  php tests/canary-posts.php save|check <file> — فقط محلی، فقط می‌خواند */
require_once __DIR__ . '/env.php';
$_SERVER['HTTP_HOST'] = TP_HOST; $_SERVER['SERVER_NAME'] = TP_HOST; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; $_SERVER['REQUEST_METHOD'] = 'GET';
require TP_WP_DIR . '/wp-load.php';
global $wpdb;
$cmd  = $argv[1];
$file = $argv[2];
$rows = $wpdb->get_results( "SELECT ID, post_type, post_status, post_modified_gmt FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','nav_menu_item','customize_changeset','oembed_cache') ORDER BY ID", ARRAY_A );
$now  = array();
foreach ( $rows as $r ) {
	$now[ (int) $r['ID'] ] = $r['post_type'] . '|' . $r['post_status'] . '|' . $r['post_modified_gmt'];
}
if ( 'save' === $cmd ) {
	file_put_contents( $file, serialize( $now ) );
	echo count( $now ) . " posts saved\n";
	exit( 0 );
}
$before = unserialize( file_get_contents( $file ) );
$gone = array(); $changed = array();
foreach ( $before as $id => $sig ) {
	if ( ! isset( $now[ $id ] ) ) { $gone[] = $id . ' (' . explode( '|', $sig )[0] . ')'; }
	elseif ( $now[ $id ] !== $sig ) { $changed[] = $id . ' (' . $sig . ' → ' . $now[ $id ] . ')'; }
}
echo 'gone: ' . ( $gone ? implode( ', ', $gone ) : 'none' ) . "\n";
echo 'changed: ' . ( $changed ? implode( ' ; ', array_slice( $changed, 0, 10 ) ) . ( count( $changed ) > 10 ? ' …' . count( $changed ) : '' ) : 'none' ) . "\n";
