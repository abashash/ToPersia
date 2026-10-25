<?php
/**
 * گزارش «خانهٔ خالی» چهار خانهٔ ثابت آمار — P5-D395 بند ۴ (فقط خواندنی)
 *
 *   php wp-cli.phar --path=… --url=… eval-file scripts/tp-stats-empty.php [md] [all]
 *
 * برای هر نوع از ۴۱ نوع STAT_CELLS، روی همهٔ پست‌های منتشرشدهٔ زبان پیش‌فرض و با همان topersia_g_stat_cell() رندر:
 *   ‏شمار پست با ۰، ۱، ۲، ۳ و ۴ خانهٔ «—»؛ شمار «—» به تفکیک خانه؛ فهرست پست‌هایی که برای پر شدن نیاز دارند.
 * گیت راه‌اندازی (فعلاً فقط گزارش): حداکثر ۱ خانهٔ «—» در هر پست منتشرشده — پست‌های بیش از آن «رد گیت» می‌آیند.
 * md جدول Markdown برای docs/STATS-4-MATRIX.md · all همهٔ پست‌های ناقص (پیش‌فرض: تا ۸ در هر نوع). هیچ چیز نوشته نمی‌شود.
 */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'topersia_g_stat_cells' ) ) {
	fwrite( STDERR, "بی وردپرس یا بی topersia-render\n" );
	exit( 2 );
}

$md    = (bool) array_intersect( array( 'md', '--md' ), (array) ( $args ?? array() ) );
$all   = (bool) array_intersect( array( 'all', '--all' ), (array) ( $args ?? array() ) );
$cells = topersia_g_stat_cells();
$lang  = apply_filters( 'wpml_default_language', 'fa' );
$rows  = array();
$tot   = array( 'posts' => 0, 'gate' => 0, 'dist' => array_fill( 0, 5, 0 ) );

foreach ( $cells as $type => $list ) {

	$ids  = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false, 'lang' => $lang, 'orderby' => 'ID', 'order' => 'ASC' ) );
	$keys = array_column( $list, 'key' );
	$dist = array_fill( 0, 5, 0 );
	$per  = array_fill_keys( $keys, 0 );
	$need = array();

	foreach ( $ids as $pid ) {
		$miss = array();
		foreach ( $keys as $k ) {
			if ( null === topersia_g_stat_cell( $pid, $k ) ) {
				$miss[] = $k;
				$per[ $k ]++;
			}
		}
		$dist[ count( $miss ) ]++;
		$tot['dist'][ count( $miss ) ]++;
		if ( count( $miss ) > 1 ) {
			$tot['gate']++;
		}
		if ( $miss ) {
			$need[] = array( 'id' => $pid, 'title' => get_the_title( $pid ), 'miss' => $miss );
		}
	}

	$tot['posts'] += count( $ids );
	$rows[ $type ] = array( 'posts' => count( $ids ), 'dist' => $dist, 'per' => $per, 'need' => $need, 'keys' => $keys );
}

/* P5-D398 بند ۷ — فیلدهای الزامی با بیشترین کمبود: برای ساختن فایل‌های درون‌ریزی (کدام فیلد از کدام نوع بیشتر خالی است) */
$short = array();
foreach ( $rows as $t => $r ) {
	foreach ( $cells[ $t ] as $cell ) {
		$n = $r['per'][ $cell['key'] ] ?? 0;
		if ( $n > 0 ) {
			$short[] = array( 'type' => $t, 'key' => $cell['key'], 'field' => topersia_g_stat_field_of( $cell['key'] ), 'req' => $cell['req'], 'missing' => $n, 'posts' => $r['posts'] );
		}
	}
}
usort( $short, function ( $a, $b ) { return $b['missing'] <=> $a['missing'] ?: strcmp( $a['type'], $b['type'] ); } );

$lab = function ( $k ) {
	return topersia_g_stat_label( $k ) . ' (`' . $k . '`)';
};

if ( $md ) {
	echo "| نوع | پست | ۰ «—» | ۱ | ۲ | ۳ | ۴ | رد گیت (> ۱) | «—» به تفکیک خانه |\n|---|---|---|---|---|---|---|---|---|\n";
	foreach ( $rows as $t => $r ) {
		$per = array();
		foreach ( $r['per'] as $k => $n ) {
			if ( $n ) { $per[] = topersia_g_stat_label( $k ) . ' ' . $n; }
		}
		printf( "| `%s` | %d | %d | %d | %d | %d | %d | %d | %s |\n", $t, $r['posts'], $r['dist'][0], $r['dist'][1], $r['dist'][2], $r['dist'][3], $r['dist'][4], $r['dist'][2] + $r['dist'][3] + $r['dist'][4], $per ? implode( ' · ', $per ) : '—' );
	}
	printf( "| **همه** | %d | %d | %d | %d | %d | %d | **%d** | |\n", $tot['posts'], $tot['dist'][0], $tot['dist'][1], $tot['dist'][2], $tot['dist'][3], $tot['dist'][4], $tot['gate'] );
	echo "\n**پست‌هایی که باید پر شوند** (خانه‌های «—»؛ " . ( $all ? 'همه' : 'تا ۸ در هر نوع' ) . "):\n\n";
	foreach ( $rows as $t => $r ) {
		if ( ! $r['need'] ) { continue; }
		$items = array();
		foreach ( $all ? $r['need'] : array_slice( $r['need'], 0, 8 ) as $p ) {
			$items[] = $p['title'] . ' #' . $p['id'] . ' (' . implode( '، ', array_map( 'topersia_g_stat_label', $p['miss'] ) ) . ')';
		}
		echo '- `' . $t . '` (' . count( $r['need'] ) . '): ' . implode( ' · ', $items ) . ( ! $all && count( $r['need'] ) > 8 ? ' · …' : '' ) . "\n";
	}
	echo "\n**فیلدهای کم‌مقدار (بیشترین کمبود اول) — برای فایل‌های درون‌ریزی:**\n\n| نوع | خانه | فیلد | الزامی در بسته | پست «—» | از پست |\n|---|---|---|---|---|---|\n";
	foreach ( array_slice( $short, 0, $all ? 999 : 25 ) as $x ) {
		echo '| `' . $x['type'] . '` | ' . topersia_g_stat_label( $x['key'] ) . ' | ' . ( '' === $x['field'] ? '— (مشتق از رابطه/مختصات)' : '`' . $x['field'] . '`' ) . ' | ' . ( $x['req'] ? 'بله' : 'خیر' ) . ' | ' . $x['missing'] . ' | ' . $x['posts'] . " |\n";
	}
	exit( 0 );
}

echo "خانهٔ خالی — P5-D395 (پست منتشرشدهٔ {$lang})\n";
foreach ( $rows as $t => $r ) {
	if ( ! $r['posts'] ) {
		continue;
	}
	printf( "  %-20s %3d پست · «—»: ۰=%d ۱=%d ۲=%d ۳=%d ۴=%d · رد گیت %d\n", $t, $r['posts'], $r['dist'][0], $r['dist'][1], $r['dist'][2], $r['dist'][3], $r['dist'][4], $r['dist'][2] + $r['dist'][3] + $r['dist'][4] );
}
printf( "همه: %d پست · ۰=%d ۱=%d ۲=%d ۳=%d ۴=%d · رد گیت راه‌اندازی (بیش از ۱ «—»): %d\n", $tot['posts'], $tot['dist'][0], $tot['dist'][1], $tot['dist'][2], $tot['dist'][3], $tot['dist'][4], $tot['gate'] );
