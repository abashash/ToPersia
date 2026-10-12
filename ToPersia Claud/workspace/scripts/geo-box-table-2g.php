<?php
/**
 * جدول «نوع ← فیلدها و رابطه‌های باکس اطلاعات جغرافیایی» — دستهٔ ۱ · P5-D348.  فقط‌خواندنی.
 *
 *   php wp-cli.phar --path=geo eval-file scripts/geo-box-table-2g.php   ← چاپ Markdown
 *
 * برای هر نوع پست عمومی: نخستین پستِ منتشرشدهٔ فارسی؛ باکس `tp-geo` آمد یا نه، کاشی‌های والد مکانی/مرتبط/مرکزیت و
 * فیلدهای ناحیهٔ card از FIELDMAP. منبع همان توابع صفحه است، نه حدس.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

do_action( 'wpml_switch_language', 'fa' );
$types = get_post_types( array( 'public' => true ), 'names' );
$rows  = array();

foreach ( $types as $type ) {

	if ( 0 !== strpos( $type, 'tp_' ) || 'tpc_news' === $type ) {
		continue;
	}

	$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => 40, 'suppress_filters' => false, 'orderby' => 'ID', 'order' => 'ASC' ) );

	if ( ! $ids ) {
		$rows[] = array( $type, 0, '—', '—', '—', '—' );
		continue;
	}

	$pid = (int) $ids[0]->ID;

	/* نخستین پستی که باکس دارد؛ وگرنه نخستین */
	foreach ( $ids as $cand ) {
		if ( '' !== trim( topersia_g_sc_facts( array( 'id' => $cand->ID ) ) ) ) {
			$pid = (int) $cand->ID;
			break;
		}
	}

	$taken  = array_merge( topersia_g_stat_keys( $pid ), array( 'geo_coordinates', 'best_season' ) );
	$fields = array_keys( topersia_g_zone_fields( $pid, 'card', $taken ) );
	$map    = topersia_g_map();
	$labels = array();

	foreach ( $fields as $f ) {
		$labels[] = isset( $map[ $f ] ) ? $f : $f;
	}

	$parents = array_map( function ( $t ) { return $t['label'] . ': ' . $t['name']; }, topersia_g_geo_parent_tiles( $pid ) );
	$centre  = array_map( function ( $t ) { return $t['label'] . ': ' . $t['name']; }, topersia_g_geo_center_tiles( $pid ) );
	$related = $parents ? array() : array_map( function ( $t ) { return $t['label'] . ': ' . $t['name']; }, topersia_g_geo_related_tiles( $pid ) );
	$layout  = topersia_g_layout( $pid );
	$on      = false;

	foreach ( $layout['rail'] as $z ) {
		if ( 'tp-geo' === $z['id'] ) { $on = true; }
	}

	$html = $on ? topersia_g_sc_facts( array( 'id' => $pid ) ) : '';

	$rows[] = array(
		$type,
		$pid,
		$on ? ( '' !== trim( $html ) ? 'می‌آید' : 'ماتریس روشن، بی داده ← نمی‌آید' ) : 'ماتریس: خاموش',
		$labels ? implode( '، ', $labels ) : '—',
		$parents ? implode( ' · ', array_slice( $parents, 0, 3 ) ) : '—',
		$related ? implode( ' · ', array_slice( $related, 0, 3 ) ) . ( count( $related ) > 3 ? ' (+' . ( count( $related ) - 3 ) . ')' : '' ) : ( $centre ? implode( ' · ', $centre ) : '—' ),
	);
}

echo "| نوع | پست نمونه | باکس | فیلدهای card (FIELDMAP) | والد مکانی (R01) | مکان‌های مرتبط / مرکزیت |\n|---|---|---|---|---|---|\n";

foreach ( $rows as $r ) {
	echo '| ' . implode( ' | ', $r ) . " |\n";
}
