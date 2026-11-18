<?php
/**
 * مهاجرت افزونۀ تبلیغ — دستۀ 54 بند ۴ و ۵ (P5-D621 · P5-D623).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-ads-migrate.php status
 *   … eval-file scripts/tp-ads-migrate.php up                    ← جدول‌های آمار، نسخۀ TOPERSIA_ADS_DB (ایدمپوتنت)
 *   … eval-file scripts/tp-ads-migrate.php down confirm          ← برداشتن جدول‌ها و گزینۀ نسخه (آمار پاک می‌شود)
 *   … eval-file scripts/tp-ads-migrate.php footer                ← dry-run: تبلیغ‌های گزینۀ پاورقی که منتقل می‌شوند
 *   … eval-file scripts/tp-ads-migrate.php footer confirm        ← هر تبلیغ روشن گزینه ← یک پست tp_ad (جایگاه footer)
 *
 * پاورقی: گزینۀ topersia_footer_blocks دست نمی‌خورد (راه برگشت: پاک‌کردن پست‌ها ← render همان گزینه را نشان می‌دهد).
 * هر پست نشان `_tp_ad_from_footer` = شمارۀ ردیف گزینه دارد، پس اجرای دوباره چیزی را دو بار نمی‌سازد.
 * گزینه یک متن جایگزین دارد ← همان در fa · en · ar (مدیر باید ترجمه‌اش کند؛ گزارش می‌گوید). تصویر ← نسبت wide.
 * فقط وقتی افزونه روشن است (topersia_ads_enabled — تا ممیزی فقط محلی).
 */

if ( ! defined( 'ABSPATH' ) || ! function_exists( 'topersia_ads_enabled' ) || ! topersia_ads_enabled() ) {
	echo "افزونۀ topersia-ads فعال و روشن نیست\n";
	exit( 2 );
}

global $wpdb;
$cmd     = (string) ( $args[0] ?? 'status' );
$confirm = 'confirm' === (string) ( $args[1] ?? '' );

$from_footer = function () use ( $wpdb ) {
	$rows = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_tp_ad_from_footer'" );
	$out  = array();
	foreach ( (array) $rows as $r ) {
		$out[ (int) $r->meta_value ] = (int) $r->post_id;
	}
	return $out;
};

if ( 'status' === $cmd ) {
	$t = topersia_ads_tables();
	printf( "db_version=%s (کد %d) · جدول‌ها=%s\n", (string) get_option( 'tp_ads_db_version', '-' ), TOPERSIA_ADS_DB, topersia_ads_table_ok() ? 'ok' : 'نیست' );
	foreach ( $t as $k => $name ) {
		$ok = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $name ) );
		printf( "  %-6s %s %s\n", $k, $name, $ok ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$name}`" ) . ' ردیف' : 'نیست' );
	}
	$by = $wpdb->get_results( "SELECT post_status, COUNT(*) n FROM {$wpdb->posts} WHERE post_type = 'tp_ad' GROUP BY post_status" );
	foreach ( (array) $by as $r ) {
		printf( "  tp_ad %-8s %d\n", $r->post_status, (int) $r->n );
	}
	printf( "  منتقل‌شده از پاورقی: %d\n", count( $from_footer() ) );
	exit( 0 );
}

if ( 'up' === $cmd ) {
	topersia_ads_migrate_up();
	echo topersia_ads_table_ok() ? "up: ok\n" : "up: شکست\n";
	exit( topersia_ads_table_ok() ? 0 : 1 );
}

if ( 'down' === $cmd ) {
	if ( ! $confirm ) {
		echo "down جدول‌های آمار را برمی‌دارد (برگشت‌ناپذیر برای آمار) — با confirm\n";
		exit( 2 );
	}
	topersia_ads_migrate_down();
	echo topersia_ads_table_ok() ? "down: جدول هنوز هست\n" : "down: ok\n";
	exit( topersia_ads_table_ok() ? 1 : 0 );
}

if ( 'footer' === $cmd ) {

	if ( ! function_exists( 'topersia_g_ft_opts' ) ) {
		echo "topersia-render فعال نیست\n";
		exit( 2 );
	}

	$opts = topersia_g_ft_opts();
	$done = $from_footer();
	$todo = array();

	foreach ( $opts['ads'] as $i => $a ) {
		if ( ! $a['on'] || $a['img'] <= 0 || '' === $a['url'] ) {
			continue;
		}
		$todo[ $i ] = $a;
	}

	printf( "گزینۀ پاورقی: %d تبلیغ روشن · پیش‌تر منتقل‌شده: %d\n", count( $todo ), count( array_intersect_key( $done, $todo ) ) );

	$made = 0;
	foreach ( $todo as $i => $a ) {
		if ( isset( $done[ $i ] ) ) {
			printf( "  ردیف %d ← #%d (پیش‌تر)\n", $i + 1, $done[ $i ] );
			continue;
		}
		$alt = '' !== $a['alt'] ? $a['alt'] : 'تبلیغ';
		$meta = array(
			'advertiser' => '',
			'url'        => $a['url'],
			'slots'      => array( 'footer' ),
			'img'        => array( 'wide' => (int) $a['img'], 'mwide' => 0, 'rect' => 0, 'card' => 0 ),
			'alt'        => array( 'fa' => $alt, 'en' => $alt, 'ar' => $alt ),
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
			'note'       => 'منتقل‌شده از گزینۀ پاورقی، ردیف ' . ( $i + 1 ) . ' — متن جایگزین en و ar را ترجمه کنید.',
		);
		/* همان قاعدۀ ذخیرۀ پیشخوان: فعال بی آمادگی ← متوقف */
		$why = topersia_ads_missing( topersia_ads_norm( $meta ) );
		if ( $why ) {
			$meta['status'] = 'paused';
		}
		if ( ! $confirm ) {
			printf( "  ردیف %d → tp_ad تازه (dry-run) · تصویر #%d · %s\n", $i + 1, (int) $a['img'], $why ? 'متوقف: ' . implode( '، ', $why ) : 'فعال' );
			continue;
		}
		$id = wp_insert_post( array(
			'post_type'   => 'tp_ad',
			'post_status' => 'publish',
			'post_title'  => 'تبلیغ پاورقی ' . ( $i + 1 ),
		), true );
		if ( is_wp_error( $id ) || ! $id ) {
			printf( "  ردیف %d: شکست %s\n", $i + 1, is_wp_error( $id ) ? $id->get_error_message() : '' );
			exit( 1 );
		}
		update_post_meta( $id, '_tp_ad', $meta );
		update_post_meta( $id, '_tp_ad_from_footer', (string) $i );
		$made++;
		printf( "  ردیف %d → #%d\n", $i + 1, (int) $id );
	}

	if ( $made && function_exists( 'topersia_ads_bump' ) ) {
		topersia_ads_bump();
	}
	printf( "%s: %d ساخته شد\n", $confirm ? 'footer' : 'footer (dry-run)', $made );
	exit( 0 );
}

echo "دستور: status | up | down confirm | footer [confirm]\n";
exit( 2 );
