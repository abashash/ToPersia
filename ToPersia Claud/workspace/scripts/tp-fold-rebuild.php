<?php
/**
 * بازسازی متای `_tp_search_fold` — P5-D265 · SPRINT-4.4 بند ۲٫۶ گام ۱ — فقط روی topersia.local.
 *
 *   php scripts/tp-fold-rebuild.php --report    هر نوع جست‌وجوپذیر: درست، کهنه، نبوده
 *   php scripts/tp-fold-rebuild.php --rebuild   کهنه و نبوده از نو؛ شمار پیش و پس
 *
 * همان متنی که قلاب save_post هسته می‌نویسد (`topersia_search_fold_post()`)،
 * روی همان نوع‌ها (`topersia_search_fold_types()`)، در هر وضعیتی جز
 * auto-draft و زباله. متا فقط محلی است تا ممیزی پیوست الف بند ۶؛ بیرون از
 * local هسته این متا را نه می‌نویسد نه می‌خواند، و این اسکریپت هم اجرا
 * نمی‌شود. بی قلاب: فقط همین یک متا، با update_post_meta.
 *
 * کد خروج: ۰ سالم · ۱ پس از بازسازی هنوز کهنه یا نبوده · ۲ محیط ناقص
 */

$_SERVER['HTTP_HOST']      = 'topersia.local';
$_SERVER['SERVER_NAME']    = 'topersia.local';
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

$mode = isset( $argv[1] ) ? $argv[1] : '';

if ( ! in_array( $mode, array( '--report', '--rebuild' ), true ) ) {
	fwrite( STDERR, "--report یا --rebuild\n" );
	exit( 2 );
}

require 'C:/wamp64/www/topersia/geo/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی — topersia.local، محیط local، پایگاه topersia_local\n" );
	exit( 2 );
}

if ( ! function_exists( 'topersia_search_fold_post' ) || ! topersia_search_fold_enabled() ) {
	fwrite( STDERR, "هستۀ ۴٫۱۱٫۰ با تاکردن روشن روی نسخۀ محلی نیست\n" );
	exit( 2 );
}

global $wpdb;

/** وضعیت متای هر پست نوع‌های جست‌وجوپذیر. */
function tp_fold_scan() {

	global $wpdb;

	$types = topersia_search_fold_types();
	$in    = implode( ',', array_fill( 0, count( $types ), '%s' ) );
	$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( {$in} ) AND post_status NOT IN ( 'auto-draft', 'trash', 'inherit' ) ORDER BY ID", $types ) );
	$out   = array();

	foreach ( $ids as $id ) {

		$post  = get_post( (int) $id );
		$want  = topersia_search_fold_post( (int) $id );
		$have  = get_post_meta( (int) $id, TOPERSIA_SEARCH_FOLD_META, false );
		$state = ! $have ? 'missing' : ( 1 === count( $have ) && $have[0] === $want ? 'ok' : 'stale' );

		$out[ $post->post_type ][ $state ][] = (int) $id;
	}

	ksort( $out );

	return $out;
}

function tp_fold_print( $scan, $title ) {

	echo "\n{$title}\n" . str_repeat( '─', 64 ) . "\n";
	printf( "  %-26s %8s %8s %8s\n", 'نوع', 'درست', 'کهنه', 'نبوده' );

	$sum = array( 'ok' => 0, 'stale' => 0, 'missing' => 0 );

	foreach ( $scan as $type => $s ) {
		$n = array();
		foreach ( array_keys( $sum ) as $k ) {
			$n[ $k ]    = isset( $s[ $k ] ) ? count( $s[ $k ] ) : 0;
			$sum[ $k ] += $n[ $k ];
		}
		printf( "  %-26s %8d %8d %8d\n", $type, $n['ok'], $n['stale'], $n['missing'] );
	}

	printf( "  %-26s %8d %8d %8d\n", 'همه', $sum['ok'], $sum['stale'], $sum['missing'] );

	return $sum;
}

$before = tp_fold_scan();
$sum    = tp_fold_print( $before, 'پیش' );

if ( '--report' === $mode ) {
	exit( 0 );
}

$done = 0;

foreach ( $before as $type => $s ) {
	foreach ( array( 'stale', 'missing' ) as $k ) {
		foreach ( isset( $s[ $k ] ) ? $s[ $k ] : array() as $id ) {
			delete_post_meta( $id, TOPERSIA_SEARCH_FOLD_META );
			add_post_meta( $id, TOPERSIA_SEARCH_FOLD_META, topersia_search_fold_post( $id ), true );
			$done ++;
		}
	}
}

echo "\n  از نو: {$done}\n";

$after = tp_fold_print( tp_fold_scan(), 'پس' );

exit( $after['stale'] || $after['missing'] ? 1 : 0 );
