<?php
/**
 * برای tests/phase2c.js — برای هر نوع صفحه‌دار، نشانی نخستین پست منتشرشدۀ فارسی
 * که کارت آمار سربرگ دارد (پرخانه‌ترین از میان چند پست نخست). JSON: نوع ← نشانی.
 *
 *   php tests/phase2c-urls.php
 *
 * فقط خواندن؛ فقط topersia.local.
 */

require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']   = TP_HOST;
$_SERVER['REQUEST_URI'] = '/';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || ! function_exists( 'topersia_g_type_matrix' ) ) {
	exit( 2 );
}

$out = array();

foreach ( array_keys( topersia_g_kind_labels() ) as $type ) {

	if ( in_array( $type, topersia_g_no_page_types(), true ) ) {
		continue;
	}

	$ids  = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => 12, 'fields' => 'ids', 'suppress_filters' => false ) );
	/* پرداده‌ترین پست‌های محلی (LOCAL-DATA اصفهان) هم */
	$ids  = array_unique( array_merge( get_posts( array( 'post_type' => $type, 'name' => 'isfahan', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) ), $ids ) );
	$best = 0;
	$most = 0;

	foreach ( $ids as $id ) {
		$n = substr_count( (string) topersia_g_sc_stats( array( 'id' => $id ) ), 'class="tp-stat"' );
		if ( $n > $most ) {
			$most = $n;
			$best = $id;
		}
	}

	if ( $best ) {
		$out[ $type ] = array( 'url' => wp_make_link_relative( get_permalink( $best ) ), 'cells' => $most );
	}
}

echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE ), "\n";
