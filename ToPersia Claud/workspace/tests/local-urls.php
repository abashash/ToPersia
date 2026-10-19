<?php
/**
 * نشانی صفحهٔ استان، شهر و یک جاذبهٔ منتشرشده در fa · en · ar — فقط topersia.local.  P5-D374
 *
 *   php tests/local-urls.php      → JSON { "province": {fa,en,ar}, "city": {…}, "monument": {…} }   (فقط می‌خواند)
 */
require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) {
	fwrite( STDERR, "فقط نسخهٔ محلی\n" );
	exit( 2 );
}

global $wpdb;

$uid = static function ( $u ) use ( $wpdb ) {
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='entity_uid' AND meta_value=%s LIMIT 1", $u ) );
};

$ids = array(
	'province' => $uid( home_url( '/e/province/isfahan' ) ),
	'city'     => $uid( home_url( '/e/city/isfahan' ) ),
	'monument' => (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('tp_monument','tp_ancient_site') AND post_status='publish' ORDER BY ID LIMIT 1" ),
);

$out = array();

foreach ( $ids as $name => $pid ) {
	foreach ( array( 'fa', 'en', 'ar' ) as $lang ) {
		$id = (int) apply_filters( 'wpml_object_id', $pid, get_post_type( $pid ), false, $lang );
		if ( $id ) {
			do_action( 'wpml_switch_language', $lang );
			$out[ $name ][ $lang ] = get_permalink( $id );
		}
	}
}

do_action( 'wpml_switch_language', 'fa' );
echo wp_json_encode( $out );
