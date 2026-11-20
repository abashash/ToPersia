<?php
/**
 * دستۀ 56-الف بند ۲ و ۵ — فهرست هر صفحۀ شبکه/فهرست کارت برای حسابرسی و گیت tests/ads-infeed-gate.js. فقط خواندن.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/local/tp-ads-infeed-pages.php
 *
 * خروجی JSON: { rule, preview, pages: [ { k, u, f } ] } — u نشانی نسبی fa، f نسخۀ پالایش‌شده یا تهی.
 * منبع فهرست خود سایت است (ثبت‌نامۀ هاب، گروه‌ها، فصل‌ها، برچسب پرکاربرد، اخبار، مقالات، خبرهای یک موجودیت)،
 * پس هاب تازه‌ای که ثبت‌نامه بگیرد بی ویرایش این‌جا وارد گیت می‌شود.
 *
 * @package topersia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 2 );
}

$tp_rel   = function ( $url ) {
	return '' === (string) $url ? '' : wp_make_link_relative( (string) $url );
};
$tp_q     = '?tp_q=' . rawurlencode( 'ا' );
$tp_pages = array();

foreach ( topersia_g_hub_registry() as $tp_type => $tp_row ) {
	$tp_u = $tp_rel( get_post_type_archive_link( $tp_type ) );
	if ( '' !== $tp_u ) {
		$tp_pages[] = array( 'k' => $tp_type, 'u' => $tp_u, 'f' => $tp_u . $tp_q );
	}
}
foreach ( array_keys( topersia_g_hub_groups() ) as $tp_g ) {
	$tp_u = $tp_rel( topersia_g_hub_group_url( $tp_g ) );
	if ( '' !== $tp_u ) {
		$tp_pages[] = array( 'k' => 'group:' . $tp_g, 'u' => $tp_u, 'f' => $tp_u . $tp_q );
	}
}
if ( function_exists( 'topersia_g_season_url' ) ) {
	foreach ( array( 'spring', 'summer', 'autumn', 'winter' ) as $tp_s ) {
		$tp_u = $tp_rel( topersia_g_season_url( $tp_s ) );
		if ( '' !== $tp_u ) {
			$tp_pages[] = array( 'k' => 'season:' . $tp_s, 'u' => $tp_u, 'f' => '' );
		}
	}
}
if ( taxonomy_exists( 'tpc_tag' ) ) {
	$tp_t = get_terms( array( 'taxonomy' => 'tpc_tag', 'number' => 1, 'orderby' => 'count', 'order' => 'DESC', 'hide_empty' => true ) );
	if ( $tp_t && ! is_wp_error( $tp_t ) && ! is_wp_error( get_term_link( $tp_t[0] ) ) ) {
		$tp_pages[] = array( 'k' => 'tag:' . $tp_t[0]->slug, 'u' => $tp_rel( get_term_link( $tp_t[0] ) ), 'f' => '' );
	}
}
foreach ( array( 'tpc_news' => '?tp_when=12m', 'tpc_article' => '' ) as $tp_pt => $tp_f ) {
	$tp_u = post_type_exists( $tp_pt ) ? $tp_rel( get_post_type_archive_link( $tp_pt ) ) : '';
	if ( '' !== $tp_u ) {
		$tp_pages[] = array( 'k' => $tp_pt, 'u' => $tp_u, 'f' => '' === $tp_f ? '' : $tp_u . $tp_f );
	}
}
if ( function_exists( 'topersia_g_news_list_url' ) ) {
	$tp_p = get_posts( array( 'post_type' => 'tp_province', 'name' => 'isfahan', 'numberposts' => 1, 'post_status' => 'publish' ) );
	if ( $tp_p ) {
		$tp_pages[] = array( 'k' => 'news-list:isfahan', 'u' => $tp_rel( topersia_g_news_list_url( $tp_p[0]->ID ) ), 'f' => '' );
	}
}

echo wp_json_encode( array(
	'rule'    => function_exists( 'topersia_ads_feed_rule' ) ? topersia_ads_feed_rule() : null,
	'preview' => function_exists( 'topersia_ads_demo' ) && topersia_ads_demo(),
	'pages'   => $tp_pages,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
