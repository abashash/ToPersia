<?php
/**
 * پوشش منوی اصلی — دستهٔ 55-د بند ۴ · P5-D648. فقط می‌خواند؛ هیچ چیز را عوض نمی‌کند.
 *
 *   wp --url=http://topersia.local eval-file scripts/tp-hub-menu-coverage.php <خروجی.json>
 *
 * هاب عمومی = آرشیو هر نوع REGISTRY هاب، دو گروه (جاذبه‌ها، رویدادها)، چهار فصل، اخبار و مقالات. به ازای هر زبان (fa · en · ar):
 * نشانی، شمار موجودیت واقعی منتشرشدهٔ همان زبان (بی نشان _tp_sample)، و حضور در منوی اصلی همان زبان (fa #19 · en #78 · ar #77).
 */
global $wpdb, $sitepress;
if ( 'topersia.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { WP_CLI::error( 'فقط topersia.local' ); }
$norm = function ( $u ) { return untrailingslashit( preg_replace( '/[?#].*$/', '', (string) $u ) ); };
$menus = array( 'fa' => 19, 'en' => 78, 'ar' => 77 );
$reg   = array_keys( topersia_g_hub_registry() );
$groups = topersia_g_hub_groups();
$out   = array();
foreach ( array( 'fa', 'en', 'ar' ) as $lang ) {
	do_action( 'wpml_switch_language', $lang );
	/* آیتم‌های منو: نشانی‌ها (بی # و تهی) */
	$items = (array) $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_title, p.post_status FROM {$wpdb->posts} p JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id WHERE tt.term_id=%d AND p.post_type='nav_menu_item' AND p.post_status='publish'", $menus[ $lang ] ), ARRAY_A );
	$menu_urls = array(); $menu_labels = array();
	foreach ( $items as $it ) {
		$u = (string) get_post_meta( $it['ID'], '_menu_item_url', true );
		if ( '' === $u || '#' === $u ) { $menu_labels[] = $it['post_title'] . ' (بی مقصد)'; continue; }
		$menu_urls[ $norm( $u ) ] = $it['post_title'];
		$menu_labels[] = $it['post_title'] . ' → ' . $u;
	}
	$count = function ( array $types ) {
		if ( ! $types ) { return array( 0, 0 ); }
		$q = new WP_Query( array( 'post_type' => $types, 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => false, 'suppress_filters' => false, 'meta_query' => array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) ) );
		$all = new WP_Query( array( 'post_type' => $types, 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters' => false ) );
		return array( (int) $q->found_posts, (int) $all->found_posts - (int) $q->found_posts );
	};
	$rows = array();
	$add = function ( $id, $label, $url, array $types ) use ( &$rows, $count, $norm, $menu_urls ) {
		list( $real, $sample ) = $count( $types );
		$rows[] = array( 'id' => $id, 'label' => $label, 'url' => (string) $url, 'real' => $real, 'sample' => $sample, 'in_menu' => '' !== (string) $url && isset( $menu_urls[ $norm( $url ) ] ) ? $menu_urls[ $norm( $url ) ] : '' );
	};
	foreach ( $reg as $type ) {
		$o = get_post_type_object( $type );
		$add( 'type:' . $type, $o ? (string) $o->labels->name : $type, post_type_exists( $type ) ? get_post_type_archive_link( $type ) : '', post_type_exists( $type ) ? array( $type ) : array() );
	}
	foreach ( $groups as $k => $g ) { $add( 'group:' . $k, (string) ( $g['label'] ?? $k ), topersia_g_hub_group_url( $k ), array_values( array_filter( (array) $g['types'], 'post_type_exists' ) ) ); }
	foreach ( array( 'spring', 'summer', 'autumn', 'winter' ) as $s ) { $add( 'season:' . $s, 'فصل ' . $s, topersia_g_ft_season_url( $s, 1 ), array() ); }
	foreach ( array( 'tpc_news', 'tpc_article' ) as $t ) {
		if ( post_type_exists( $t ) && ! in_array( $t, $reg, true ) ) { $add( 'type:' . $t, (string) get_post_type_object( $t )->labels->name, 'tpc_news' === $t ? topersia_g_news_archive_url() : get_post_type_archive_link( $t ), array( $t ) ); }
	}
	$out[ $lang ] = array( 'menu' => $menus[ $lang ], 'menu_items' => $menu_labels, 'rows' => $rows );
}
do_action( 'wpml_switch_language', 'fa' );
file_put_contents( (string) $args[0], wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
echo "ok\n";
