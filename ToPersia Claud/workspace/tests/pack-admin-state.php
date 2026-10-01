<?php
/**
 * یاور `tests/pack-admin.js` — فقط topersia.local.  ۴٫۱۲٫۰ · فاز ۲ب
 *
 *   php tests/pack-admin-state.php setup <tag>          شهرستان موقت؛ عکس گزینۀ آخرین درون‌ریزی
 *   php tests/pack-admin-state.php state <tag>          پست‌های آزمون: وضعیت، زبان، وضعیت محتوا، والد ۷۳
 *   php tests/pack-admin-state.php cleanup <tag> <b64>  پاک‌کردن پست‌ها و ردیف‌های لاگ آزمون؛ برگرداندن گزینه
 *
 * فقط پست‌هایی که عنوانشان نشان آزمون (<tag>) را دارد دست می‌خورند.
 */

$_SERVER['HTTP_HOST']      = 'topersia.local';
$_SERVER['SERVER_NAME']    = 'topersia.local';
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require 'C:/wamp64/www/topersia/geo/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

$cmd = isset( $argv[1] ) ? $argv[1] : '';
$tag = isset( $argv[2] ) ? (string) $argv[2] : '';

if ( '' === $tag || ! preg_match( '/^[a-z0-9]+$/', $tag ) ) {
	fwrite( STDERR, "نشان آزمون لازم است\n" );
	exit( 2 );
}

global $wpdb;

function tagged( $tag ) {
	global $wpdb;
	return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE %s", '%' . $wpdb->esc_like( 'آزمون پیشخوان ' . $tag ) . '%' ) ) );
}

if ( 'setup' === $cmd ) {
	$id = wp_insert_post( array( 'post_type' => 'tp_county', 'post_status' => 'publish', 'post_title' => 'شهرستان آزمون پیشخوان ' . $tag ), true );
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_tp_county', 'trid' => false, 'language_code' => 'fa' ) );
	echo wp_json_encode( array( 'county' => (int) $id, 'last_run' => base64_encode( serialize( get_option( TOPERSIA_PACK_LAST_RUN, null ) ) ) ) );
	exit( 0 );
}

if ( 'state' === $cmd ) {
	$out = array();
	foreach ( tagged( $tag ) as $id ) {
		$rel   = topersia_relation( 73 );
		$out[] = array(
			'id'      => $id,
			'type'    => get_post_type( $id ),
			'title'   => get_post_field( 'post_title', $id ),
			'status'  => get_post_status( $id ),
			'lang'    => topersia_pack_post_language( $id ),
			'content' => (string) get_post_meta( $id, 'content_status', true ),
			'season'  => (string) get_post_meta( $id, 'best_season', true ),
			'parents' => 'tp_city' === get_post_type( $id ) && $rel ? array_map( 'intval', (array) $rel->get_parents( $id, 'ids' ) ) : array(),
		);
	}
	echo wp_json_encode( array( 'posts' => $out, 'last_run' => get_option( TOPERSIA_PACK_LAST_RUN, null ) ), JSON_UNESCAPED_UNICODE );
	exit( 0 );
}

if ( 'cleanup' === $cmd ) {
	$last = get_option( TOPERSIA_PACK_LAST_RUN, null );
	foreach ( tagged( $tag ) as $id ) {
		wp_delete_post( $id, true );
	}
	/* ردیف‌های لاگ اجراهای همین آزمون — شناسۀ اجرا از گزینۀ آخرین درون‌ریزی، که آزمون ساخته */
	if ( is_array( $last ) && ! empty( $last['run'] ) && isset( $last['file'] ) && false !== strpos( (string) $last['file'], $tag ) ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . topersia_log_table() . " WHERE channel = 'import' AND context LIKE %s", '%' . $wpdb->esc_like( '"run":"' . $last['run'] . '"' ) . '%' ) );
	}
	$prev = isset( $argv[3] ) ? unserialize( base64_decode( $argv[3] ), array( 'allowed_classes' => false ) ) : null;
	if ( null === $prev ) {
		delete_option( TOPERSIA_PACK_LAST_RUN );
	} else {
		update_option( TOPERSIA_PACK_LAST_RUN, $prev, false );
	}
	echo wp_json_encode( array( 'left' => count( tagged( $tag ) ) ) );
	exit( 0 );
}

fwrite( STDERR, "فرمان: setup · state · cleanup\n" );
exit( 2 );
