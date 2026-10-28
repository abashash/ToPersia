<?php
/* قاعدۀ ۱۶ — عکس همۀ پست‌های پیش از آزمون (شناسه · نوع · وضعیت · زمان تغییر) و مقایسه.  php tests/canary-posts.php save|check <file>
 * دستهٔ 32 بند 8 · P5-D456: یال‌های رابطه هم — یال میان دو پست غیرنمونه نباید گم یا تازه شود، و هیچ یال تک‌والد/یک‌به‌یکی
 * میان نمونه و پست واقعی نباید باشد (نمونه فقط در چندبه‌چند به پست واقعی می‌بندد، یا والد واقعی فرزندِ نمونه در یک‌به‌چند). */
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

/* یال‌ها: «rel|parent|child» برای هر ردیف که هیچ‌کدام از دو سر نمونه نیست؛ و فهرست یال‌های نمونه↔واقعی ناروا */
$sample = array_flip( array_map( 'intval', $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tp_sample'" ) ) );
$types  = array();
if ( function_exists( 'jet_engine' ) && isset( jet_engine()->relations ) ) {
	foreach ( jet_engine()->relations->get_active_relations() as $rid => $o ) { $types[ (int) $rid ] = (string) $o->get_args( 'type' ); }
}
$edges = array();
$bad   = array();
foreach ( $wpdb->get_results( "SELECT rel_id, parent_object_id p, child_object_id c FROM {$wpdb->prefix}jet_rel_default", ARRAY_A ) as $e ) {
	$p  = (int) $e['p'];
	$c  = (int) $e['c'];
	$ps = isset( $sample[ $p ] );
	$cs = isset( $sample[ $c ] );
	if ( ! $ps && ! $cs ) { $edges[ $e['rel_id'] . '|' . $p . '|' . $c ] = 1; continue; }
	if ( $ps !== $cs ) {
		$ty = $types[ (int) $e['rel_id'] ] ?? '?';
		if ( 'many_to_many' !== $ty && ! ( 'one_to_many' === $ty && $cs ) ) { $bad[] = $e['rel_id'] . ':' . $p . '→' . $c . ' (' . $ty . ')'; }
	}
}

if ( 'save' === $cmd ) {
	file_put_contents( $file, serialize( array( 'posts' => $now, 'edges' => $edges ) ) );
	echo count( $now ) . ' posts, ' . count( $edges ) . " edges saved\n";
	exit( 0 );
}
$saved  = unserialize( file_get_contents( $file ) );
$before = isset( $saved['posts'] ) ? $saved['posts'] : $saved;   /* پرونده‌های قدیمی فقط پست دارند */
$gone = array(); $changed = array();
foreach ( $before as $id => $sig ) {
	if ( ! isset( $now[ $id ] ) ) { $gone[] = $id . ' (' . explode( '|', $sig )[0] . ')'; }
	elseif ( $now[ $id ] !== $sig ) { $changed[] = $id . ' (' . $sig . ' → ' . $now[ $id ] . ')'; }
}
echo 'gone: ' . ( $gone ? implode( ', ', $gone ) : 'none' ) . "\n";
echo 'changed: ' . ( $changed ? implode( ' ; ', array_slice( $changed, 0, 10 ) ) . ( count( $changed ) > 10 ? ' …' . count( $changed ) : '' ) : 'none' ) . "\n";
if ( isset( $saved['edges'] ) ) {
	$rg = array_keys( array_diff_key( $saved['edges'], $edges ) );
	$rn = array_keys( array_diff_key( $edges, $saved['edges'] ) );
	echo 'edges-gone: ' . ( $rg ? implode( ', ', array_slice( $rg, 0, 10 ) ) . ( count( $rg ) > 10 ? ' …' . count( $rg ) : '' ) : 'none' ) . "\n";
	echo 'edges-new: ' . ( $rn ? implode( ', ', array_slice( $rn, 0, 10 ) ) . ( count( $rn ) > 10 ? ' …' . count( $rn ) : '' ) : 'none' ) . "\n";
}
echo 'single-parent-sample-edges: ' . ( $bad ? implode( ', ', array_slice( $bad, 0, 10 ) ) . ( count( $bad ) > 10 ? ' …' . count( $bad ) : '' ) : 'none' ) . "\n";
if ( $gone || $bad || ( isset( $saved['edges'] ) && ( $rg || $rn ) ) ) { exit( 1 ); }
