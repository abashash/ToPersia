<?php
/**
 * ۲و٫۲ — شکاف‌های والد مکانی: برای هر نوع (تا ۳۰ پست منتشرشده) چند پست زنجیرهٔ R01 دارد، چند پست کارت «اطلاعات جغرافیایی»
 * دارد (کاشی والد مکانی فقط درون همان کارت می‌آید)، و چند پست زنجیره دارد ولی کارت ندارد (فقط گزارش، کارت تازه ساخته نمی‌شود —
 * P5-D339). فقط خواندن؛ اجرا با wp eval-file؛ خروجی JSON. «بی‌زنجیره» = رابطه‌ای نیست یا داده‌ای ندارد؛ حدس نیست.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }
$out = array();
foreach ( get_post_types( array( 'public' => true ) ) as $t ) {
	if ( 0 !== strpos( $t, 'tp_' ) || in_array( $t, topersia_g_no_page_types(), true ) ) { continue; }
	$ids = get_posts( array( 'post_type' => $t, 'post_status' => 'publish', 'numberposts' => 30, 'fields' => 'ids', 'orderby' => 'ID' ) );
	$r = array( 'posts' => count( $ids ), 'with_place_parent' => 0, 'with_geo_card' => 0, 'geo_card_with_parent_tile' => 0, 'parent_but_no_geo_card' => 0, 'no_parent' => 0 );
	foreach ( $ids as $id ) {
		$places = topersia_g_crumb_places( $id );
		$taken  = array_merge( topersia_g_stat_keys( $id ), array( 'geo_coordinates', 'best_season' ) );
		$fields = (bool) topersia_g_zone_fields( $id, 'card', $taken );
		$card   = $fields || $places || ( function_exists( 'topersia_g_geo_center_tiles' ) && topersia_g_geo_center_tiles( $id ) );   /* P5-D342: والد یا مرکزیت ← کارت ساخته می‌شود */
		if ( $places ) { $r['with_place_parent']++; } else { $r['no_parent']++; }
		if ( $card ) { $r['with_geo_card']++; }
		if ( $card && $places ) { $r['geo_card_with_parent_tile']++; }
		if ( $places && ! $card ) { $r['parent_but_no_geo_card']++; }
	}
	$out[ $t ] = $r;
}
echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ), "\n";
