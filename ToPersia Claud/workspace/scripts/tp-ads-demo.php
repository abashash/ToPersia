<?php
/**
 * تبلیغ نمایشی — دستۀ 54 بند ۱۰ (P5-D548 · P5-D624). فقط topersia.local و فقط در حالت پیش‌نمایش.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-ads-demo.php status
 *   … eval-file scripts/tp-ads-demo.php seed              ← یک «نمونۀ نمایشی» برای هر جایگاه (home-mid و hub-infeed دو تا)
 *   … eval-file scripts/tp-ads-demo.php purge             ← dry-run
 *   … eval-file scripts/tp-ads-demo.php purge confirm     ← پاک‌کردن همۀ tp_ad نمایشی
 *   … eval-file scripts/tp-ads-demo.php launch            ← گیت انتشار: کد خروج ۱ اگر هر tp_ad نمایشی مانده
 *
 * هر پست `_tp_demo=1` · `_tp_sample=1` · `_tp_demo_key=ads-…` (ایدمپوتنت) — پس tp-demo-purge.php و گیت `--launch`
 * assert-51 هم آن را می‌بینند. پاورقی نه: نمونۀ پاورقی همان جای نمونۀ گزینۀ پیشین (demo_ads) است که footer-gate می‌شمارد.
 * نمایشی تصویر و نشانی ندارد و فقط با topersia_demo_on() دیده می‌شود (topersia_ads_eligible).
 */

if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( (string) home_url(), 'topersia.local' ) ) {
	echo "فقط topersia.local\n";
	exit( 2 );
}
if ( ! function_exists( 'topersia_ads_slots' ) || ! post_type_exists( 'tp_ad' ) ) {
	echo "افزونۀ topersia-ads فعال نیست\n";
	exit( 2 );
}

global $wpdb;
$cmd     = (string) ( $args[0] ?? 'status' );
$confirm = 'confirm' === (string) ( $args[1] ?? '' );

$demo_ids = function () use ( $wpdb ) {
	return array_map( 'intval', $wpdb->get_col( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_tp_demo' AND m.meta_value = '1' WHERE p.post_type = 'tp_ad' ORDER BY p.ID" ) );
};
$by_key = function ( $key ) use ( $wpdb ) {
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT m.post_id FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_type = 'tp_ad' WHERE m.meta_key = '_tp_demo_key' AND m.meta_value = %s LIMIT 1", $key ) );
};

if ( 'status' === $cmd || 'launch' === $cmd ) {
	$ids = $demo_ids();
	printf( "tp_ad نمایشی: %d%s\n", count( $ids ), $ids ? ' (#' . implode( ' #', $ids ) . ')' : '' );
	if ( 'launch' === $cmd ) {
		echo $ids ? "گیت انتشار: قرمز — tp_ad نمایشی مانده؛ purge confirm\n" : "گیت انتشار: سبز\n";
		exit( $ids ? 1 : 0 );
	}
	exit( 0 );
}

if ( 'seed' === $cmd ) {

	if ( ! function_exists( 'topersia_demo_on' ) || ! topersia_demo_on() ) {
		echo "حالت پیش‌نمایش خاموش است (TOPERSIA_DEMO_PREVIEW) — نمایشی ساخته نمی‌شود\n";
		exit( 2 );
	}

	$made = 0;
	foreach ( topersia_ads_slots() as $slot => $s ) {
		if ( 'footer' === $slot ) {
			continue;
		}
		$count = min( 2, (int) $s['max'] );
		for ( $n = 1; $n <= $count; $n++ ) {
			$key = 'ads-' . $slot . '-' . $n;
			if ( $by_key( $key ) ) {
				continue;
			}
			$id = wp_insert_post( array(
				'post_type'   => 'tp_ad',
				'post_status' => 'publish',
				'post_title'  => 'نمونۀ نمایشی — ' . $s['name'] . ' ' . $n,
			), true );
			if ( is_wp_error( $id ) || ! $id ) {
				echo "شکست: {$key}\n";
				exit( 1 );
			}
			update_post_meta( $id, '_tp_ad', array(
				'advertiser' => 'نمونۀ نمایشی',
				'url'        => '',
				'slots'      => array( $slot ),
				'img'        => array( 'wide' => 0, 'mwide' => 0, 'rect' => 0, 'card' => 0 ),
				'alt'        => array( 'fa' => '', 'en' => '', 'ar' => '' ),
				'langs'      => array( 'all' ),
				'device'     => 'all',
				'pages'      => array( 'all' ),
				'start'      => 0,
				'end'        => 0,
				'cap_imp'    => 0,
				'cap_clk'    => 0,
				'weight'     => 10,
				'priority'   => 0,
				'status'     => 'active',
				'note'       => 'نمونۀ نمایشی دستۀ 54 — scripts/tp-ads-demo.php',
			) );
			update_post_meta( $id, '_tp_demo', '1' );
			update_post_meta( $id, '_tp_sample', '1' );
			update_post_meta( $id, '_tp_demo_key', $key );
			$made++;
			printf( "  %-24s #%d\n", $key, (int) $id );
		}
	}
	if ( $made ) {
		topersia_ads_bump();
	}
	printf( "seed: %d ساخته شد · کل نمایشی %d\n", $made, count( $demo_ids() ) );
	exit( 0 );
}

if ( 'purge' === $cmd ) {
	$ids = $demo_ids();
	printf( "%s %d\n", $confirm ? 'پاک‌کردن:' : 'پاک می‌شود (dry-run):', count( $ids ) );
	if ( ! $confirm ) {
		exit( 0 );
	}
	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}
	topersia_ads_bump();
	printf( "مانده: %d\n", count( $demo_ids() ) );
	exit( 0 );
}

echo "دستور: status | seed | purge [confirm] | launch\n";
exit( 2 );
