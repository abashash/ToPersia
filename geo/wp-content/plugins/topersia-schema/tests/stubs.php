<?php
/**
 * stub وردپرس، JetEngine و WPML برای آزمون topersia-schema — P5-D357.
 *
 * فقط آنچه topersia_schema_build می‌خواند. دادۀ پست در $GLOBALS['tps_posts']،
 * یال رابطه در $GLOBALS['tps_rel'][ شناسۀ رابطه ][ فرزند ] = والدها، ترجمه در
 * $GLOBALS['tps_tr'][ ترجمه ] = اصل. از وردپرس مهربان‌تر نیست: متای ناموجود
 * رشتۀ خالی، پست ناموجود false.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'TOPERSIA_CORE_VERSION', '4.12.0' );
define( 'TOPERSIA_REL_COUNTY_CITY', 72 );
define( 'TOPERSIA_REL_PROVINCE_COUNTY', 73 );

function tps_reset() {
	$GLOBALS['tps_posts']   = array();
	$GLOBALS['tps_rel']     = array();
	$GLOBALS['tps_tr']      = array();
	$GLOBALS['tps_filters'] = array();
}
tps_reset();

function add_filter( $tag, $cb, $prio = 10, $args = 1 ) {
	$GLOBALS['tps_filters'][ $tag ][ $prio ][] = array( $cb, $args );
	return true;
}
function add_action( $tag, $cb, $prio = 10, $args = 1 ) {
	return add_filter( $tag, $cb, $prio, $args );
}
function has_filter( $tag, $cb = false ) {
	return ! empty( $GLOBALS['tps_filters'][ $tag ] );
}
function apply_filters( $tag, $value, ...$rest ) {
	if ( empty( $GLOBALS['tps_filters'][ $tag ] ) ) {
		return $value;
	}
	ksort( $GLOBALS['tps_filters'][ $tag ] );
	foreach ( $GLOBALS['tps_filters'][ $tag ] as $list ) {
		foreach ( $list as $item ) {
			$value = call_user_func_array( $item[0], array_slice( array_merge( array( $value ), $rest ), 0, $item[1] ) );
		}
	}
	return $value;
}
function __( $text, $domain = 'default' ) {
	return $text;
}
function absint( $v ) {
	return abs( (int) $v );
}
function wp_strip_all_tags( $text ) {
	if ( ! is_scalar( $text ) ) {
		throw new TypeError( 'wp_strip_all_tags: رشته لازم است' );
	}
	return trim( strip_tags( (string) $text ) );
}
function esc_url_raw( $url ) {
	return (string) $url;
}
function get_locale() {
	return 'fa_IR';
}
function get_bloginfo( $show = '' ) {
	return 'name' === $show ? 'ToPersia' : '';
}
function get_post( $id ) {
	return isset( $GLOBALS['tps_posts'][ $id ] ) ? (object) $GLOBALS['tps_posts'][ $id ] : false;
}
function get_post_type( $id ) {
	return isset( $GLOBALS['tps_posts'][ $id ] ) ? $GLOBALS['tps_posts'][ $id ]['type'] : false;
}
function get_post_status( $id ) {
	return isset( $GLOBALS['tps_posts'][ $id ] ) ? $GLOBALS['tps_posts'][ $id ]['status'] : false;
}
function get_the_title( $id ) {
	return isset( $GLOBALS['tps_posts'][ $id ] ) ? $GLOBALS['tps_posts'][ $id ]['title'] : '';
}
function get_permalink( $id ) {
	return 'https://topersia.test/p/' . (int) $id . '/';
}
function home_url( $path = '/' ) {
	return 'https://topersia.test' . $path;
}
function get_post_modified_time( $format, $gmt, $id ) {
	return '2026-10-01T00:00:00+00:00';
}
function get_post_meta( $id, $key = '', $single = false ) {
	return isset( $GLOBALS['tps_posts'][ $id ]['meta'][ $key ] ) ? $GLOBALS['tps_posts'][ $id ]['meta'][ $key ] : '';
}
function wp_get_attachment_image_src( $id, $size ) {
	return false;
}
function current_user_can( $cap ) {
	return false;
}
function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}
function load_plugin_textdomain() {
	return true;
}

/* هسته */
function topersia_active_rows( $rows ) {
	return is_array( $rows ) ? $rows : array();
}
function topersia_is_entity( $type ) {
	return 0 === strpos( (string) $type, 'tp_' );
}

/* JetEngine */
function jet_engine() {
	return true;
}
class TPS_Relation {
	public $id;
	public function __construct( $id ) {
		$this->id = $id;
	}
	public function get_parents( $child, $what = 'ids' ) {
		return isset( $GLOBALS['tps_rel'][ $this->id ][ $child ] ) ? $GLOBALS['tps_rel'][ $this->id ][ $child ] : array();
	}
}
function topersia_relation( $id ) {
	return new TPS_Relation( $id );
}

/** WPML: زبان پیش‌فرض fa و نگاشت ترجمه ← اصل. */
function tps_wpml_on() {
	add_filter( 'wpml_default_language', function () {
		return 'fa';
	} );
	add_filter( 'wpml_object_id', function ( $id, $type = '', $original = false, $lang = null ) {
		if ( 'fa' === $lang && isset( $GLOBALS['tps_tr'][ $id ] ) ) {
			return $GLOBALS['tps_tr'][ $id ];
		}
		return $id;
	}, 10, 4 );
}

function tps_post( $id, $type, $meta = array(), $status = 'publish' ) {
	$GLOBALS['tps_posts'][ $id ] = array(
		'ID'     => $id,
		'type'   => $type,
		'title'  => 'post-' . $id,
		'status' => $status,
		'meta'   => $meta,
	);
}
