<?php
/**
 * یاور بلوک lang در tests/phase2c.js — SPRINT-4.4 ۲ج٫۸ · P5-D278. فقط topersia.local.
 *
 *   php tests/phase2c-lang.php setup          برای هر نوع صفحه‌دار یک پست فارسی منتشرشده و ترجمۀ
 *                                             en و ar آن — موجود، یا موقت با درون‌ریزی ترجمۀ ۲ج٫۹ و
 *                                             انتشار. JSON: pages (نوع ← fa · en · ar) · made · run · floor
 *   php tests/phase2c-lang.php cleanup <b64>  پست‌های موقت، ردیف‌های لاگ درون‌ریزی و کارهای صف
 *
 * ترجمۀ موقت فقط عنوان دارد (نام همان زبان، یا انگلیسی)؛ بقیه copy از پست فارسی و
 * رابطه با هم‌گام‌سازی گارددار ۲ج٫۹ — همان چیزی که ویرایشگر ترجمه می‌سازد.
 */

require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

if ( ! function_exists( 'topersia_pack_tr_apply' ) || ! function_exists( 'topersia_g_kind_labels' ) ) {
	fwrite( STDERR, "هستۀ ۲ج٫۹ یا render روی نسخۀ محلی نیست\n" );
	exit( 2 );
}

require_once __DIR__ . '/queue-sweep.php';

global $wpdb;

$cmd = isset( $argv[1] ) ? $argv[1] : '';

/** نشانی نسبی یک پست به زبان خودش — WPML پیشوند زبان را فقط در بافت همان زبان می‌گذارد. */
function tp2c8_url( $id, $lang ) {
	do_action( 'wpml_switch_language', $lang );
	$url = wp_make_link_relative( get_permalink( $id ) );
	do_action( 'wpml_switch_language', 'fa' );
	return $url;
}

if ( 'setup' === $cmd ) {

	$floor = tp_queue_floor();
	$pfloor = tp_post_floor();
	$run   = 'imp-test-2c8-' . strtolower( wp_generate_password( 6, false, false ) );
	$made  = array();
	$pages = array();

	foreach ( array_keys( topersia_g_kind_labels() ) as $type ) {

		if ( in_array( $type, topersia_g_no_page_types(), true ) ) {
			continue;
		}

		/* همان انتخاب phase2c-urls: اصفهان نخست، سپس پرداده‌ترین کارت آمار */
		$ids  = array_unique( array_merge(
			get_posts( array( 'post_type' => $type, 'name' => 'isfahan', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) ),
			get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => 8, 'fields' => 'ids', 'suppress_filters' => false ) )
		) );
		$best = $ids ? (int) $ids[0] : 0;
		$most = -1;
		foreach ( $ids as $id ) {
			$n = substr_count( (string) topersia_g_sc_stats( array( 'id' => $id ) ), 'class="tp-stat"' );
			if ( $n > $most ) {
				$most = $n;
				$best = (int) $id;
			}
		}

		if ( ! $best ) {
			continue;
		}

		$row = array( 'id' => $best, 'fa' => tp2c8_url( $best, 'fa' ), 'made' => array() );

		foreach ( array( 'en', 'ar' ) as $lang ) {

			$tr = topersia_pack_tr_of( $best, $lang );

			if ( ! $tr ) {
				$tpl  = topersia_pack_tr_export( array( $best ), $lang );
				$name = '';
				foreach ( 'ar' === $lang ? array( 'name_ar', 'official_name_en', 'name_en' ) : array( 'official_name_en', 'name_en', 'page_title' ) as $k ) {
					$name = trim( (string) get_post_meta( $best, $k, true ) );
					if ( '' !== $name ) {
						break;
					}
				}
				if ( ! $tpl ) {
					$row[ $lang ] = '';
					continue;
				}
				$tpl['posts'][0]['title']['value'] = '' !== $name ? $name : 'Test ' . $type;
				$plan = topersia_pack_tr_apply( topersia_pack_tr_parse( wp_json_encode( $tpl ) ), 0, $run );
				$tr   = (int) $plan['target'];
				if ( ! $tr ) {
					$row[ $lang ] = '';
					continue;
				}
				$made[]        = $tr;
				$row['made'][] = $lang;
				wp_update_post( array( 'ID' => $tr, 'post_status' => 'publish' ) );
			}

			$row[ $lang ] = 'publish' === get_post_status( $tr ) ? tp2c8_url( $tr, $lang ) : '';
		}

		$pages[ $type ] = $row;
	}

	echo wp_json_encode( array( 'pages' => $pages, 'made' => $made, 'run' => $run, 'floor' => $floor, 'post_floor' => $pfloor ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
	exit( 0 );
}

if ( 'cleanup' === $cmd ) {

	$data = isset( $argv[2] ) ? json_decode( (string) base64_decode( $argv[2] ), true ) : null;

	if ( ! is_array( $data ) ) {
		fwrite( STDERR, "دادۀ setup لازم است\n" );
		exit( 2 );
	}

	foreach ( (array) $data['made'] as $id ) {
		wp_delete_post( (int) $id, true );
	}

	if ( ! empty( $data['run'] ) && 0 === strpos( (string) $data['run'], 'imp-test-2c8-' ) ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . topersia_log_table() . " WHERE channel = 'import' AND context LIKE %s", '%' . $wpdb->esc_like( '"run":"' . $data['run'] . '"' ) . '%' ) );
	}

	tp_queue_sweep( isset( $data['floor'] ) ? (int) $data['floor'] : -1 );
	tp_wpml_sweep( isset( $data['post_floor'] ) ? (int) $data['post_floor'] : 0 );

	$left = 0;
	foreach ( (array) $data['made'] as $id ) {
		$left += get_post( (int) $id ) ? 1 : 0;
	}

	echo wp_json_encode( array( 'left' => $left ) ), "\n";
	exit( 0 );
}

fwrite( STDERR, "فرمان: setup · cleanup\n" );
exit( 2 );
