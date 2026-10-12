<?php
/**
 * نشانی یک پست منتشرشدهٔ فارسی برای هر نوع پست عمومی (باکس‌دارترین اول) — ورودی tests/panel-order-2i.js.
 *   php wp-cli.phar --path=geo eval-file tests/panel-urls-2i.php   → JSON
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
do_action( 'wpml_switch_language', 'fa' );
$out = array();
foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
	if ( 0 !== strpos( $type, 'tp_' ) || 'tpc_news' === $type ) { continue; }
	$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => 40, 'orderby' => 'ID', 'order' => 'ASC' ) );
	if ( ! $ids ) { continue; }
	$pick = $ids[0];
	foreach ( $ids as $c ) {
		if ( function_exists( 'topersia_g_sc_facts' ) && '' !== trim( topersia_g_sc_facts( array( 'id' => $c->ID ) ) ) ) { $pick = $c; break; }
	}
	$out[ $type ] = wp_parse_url( get_permalink( $pick ), PHP_URL_PATH );
}
echo wp_json_encode( $out );
