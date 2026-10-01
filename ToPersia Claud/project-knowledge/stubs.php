<?php
/**
 * حداقل وردپرس و جت‌انجین برای آزمون شورت‌کدهای دستهٔ G.
 * هدف: اجرای واقعی کد، نه شبیه‌سازی رفتارش.
 */

define( 'ABSPATH', __DIR__ . '/' );

/**
 * مسیر افزونه — یک بار حل می‌شود، سه فایل از همین می‌خوانند.
 *
 * چیدمان‌های شناخته‌شده: فضای کاری تحویلی (plugin/)، ظرف ساخت (build/)،
 * و حالتی که افزونه کنار پوشهٔ آزمون باشد. اگر هیچ‌کدام نبود، با پیام
 * روشن می‌ایستد — نه با «fatal: file not found» که چیزی نمی‌گوید.
 */
if ( ! defined( 'TP_PLUGIN_DIR' ) ) {

	$tp_tries = array(
		dirname( __DIR__ ) . '/plugin/topersia-render/',
		dirname( __DIR__ ) . '/build/topersia-render/',
		dirname( __DIR__ ) . '/topersia-render/',
	);

	$tp_found = '';

	foreach ( $tp_tries as $tp_one ) {
		if ( file_exists( $tp_one . 'includes/topersia-g-zones.php' ) ) {
			$tp_found = $tp_one;
			break;
		}
	}

	if ( '' === $tp_found ) {
		fwrite( STDERR, "\n⛔ افزونه پیدا نشد. این مسیرها بررسی شدند:\n  "
			. implode( "\n  ", $tp_tries ) . "\n\n" );
		exit( 2 );
	}

	define( 'TP_PLUGIN_DIR', $tp_found );
	unset( $tp_tries, $tp_one, $tp_found );
}

$GLOBALS['tp_meta']      = array();
$GLOBALS['tp_posts']     = array();
$GLOBALS['tp_shortcode'] = array();
$GLOBALS['tp_current']   = 0;
$GLOBALS['tp_rel']       = array();
$GLOBALS['tp_log']       = array();

function absint( $v ) { return abs( (int) $v ); }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $v, $d = '' ) { return esc_html( $v ); }
function esc_attr__( $v, $d = '' ) { return esc_attr( $v ); }
function __( $v, $d = '' ) { return $v; }
function wp_strip_all_tags( $v ) { return strip_tags( (string) $v ); }
function home_url( $p = '/' ) { return 'https://topersia.com' . $p; }

function add_shortcode( $tag, $cb ) { $GLOBALS['tp_shortcode'][ $tag ] = $cb; }
function shortcode_exists( $tag ) { return isset( $GLOBALS['tp_shortcode'][ $tag ] ); }
function add_action() {}
function add_filter() {}
function has_filter() { return false; }
function apply_filters( $tag, $value ) { return $value; }

function get_the_ID() { return $GLOBALS['tp_current']; }

function get_post_meta( $id, $key, $single = true ) {
	return isset( $GLOBALS['tp_meta'][ $id ][ $key ] ) ? $GLOBALS['tp_meta'][ $id ][ $key ] : '';
}

function get_post_type( $id ) {
	return isset( $GLOBALS['tp_posts'][ $id ]['type'] ) ? $GLOBALS['tp_posts'][ $id ]['type'] : '';
}

function get_the_title( $id ) {
	return isset( $GLOBALS['tp_posts'][ $id ]['title'] ) ? $GLOBALS['tp_posts'][ $id ]['title'] : '';
}

function get_permalink( $id ) {
	return isset( $GLOBALS['tp_posts'][ $id ]['url'] ) ? $GLOBALS['tp_posts'][ $id ]['url'] : '#';
}

function get_post_type_object( $type ) {
	$names = array(
		'tp_country'        => 'کشور',
		'tp_province'       => 'استان',
		'tp_county'         => 'شهرستان',
		'tp_city'           => 'شهر',
		'tp_desert'         => 'کویر',
		'tp_mountain'       => 'کوه',
		'tp_water_body'     => 'پهنهٔ آبی',
		'tp_protected_area' => 'پارک ملی',
		'tp_handicraft'     => 'صنایع دستی',
	);

	if ( ! isset( $names[ $type ] ) ) {
		return null;
	}

	$o                       = new stdClass();
	$o->labels               = new stdClass();
	$o->labels->singular_name = $names[ $type ];

	return $o;
}

function wp_get_attachment_image( $id, $size = 'full', $icon = false, $attr = array() ) {
	if ( ! $id ) { return ''; }
	$class = isset( $attr['class'] ) ? $attr['class'] : '';
	return '<img class="' . esc_attr( $class ) . '" src="https://topersia.com/wp-content/uploads/' . absint( $id ) . '.jpg" alt="">';
}

function wp_get_attachment_url( $id ) { return $id ? 'https://topersia.com/audio/' . absint( $id ) . '.mp3' : ''; }
function wp_get_attachment_caption( $id ) { return 'تصویر ' . absint( $id ); }

/* ── هسته: همان دو تابعی که لایهٔ نمایش از آن مصرف می‌کند ───────────── */

function topersia_log( $m, $l = 'info', $c = 'core' ) { $GLOBALS['tp_log'][] = "[$l/$c] $m"; }

function topersia_active_rows( $rows, $status_key = '' ) {
	if ( ! is_array( $rows ) ) { return array(); }
	$out = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) { continue; }
		$status = '';
		if ( $status_key && isset( $row[ $status_key ] ) ) {
			$status = $row[ $status_key ];
		} else {
			foreach ( $row as $k => $v ) {
				if ( '_row_status' === substr( $k, -11 ) ) { $status = $v; break; }
			}
		}
		if ( 'not_applicable' === $status ) { continue; }
		$out[] = $row;
	}
	return $out;
}

class TP_Stub_Relation {
	public $id;
	public function __construct( $id ) { $this->id = (string) $id; }
	public function get_children( $pid, $f = 'ids' ) {
		return isset( $GLOBALS['tp_rel'][ $this->id ]['children'][ $pid ] )
			? $GLOBALS['tp_rel'][ $this->id ]['children'][ $pid ] : array();
	}
	public function get_parents( $pid, $f = 'ids' ) {
		return isset( $GLOBALS['tp_rel'][ $this->id ]['parents'][ $pid ] )
			? $GLOBALS['tp_rel'][ $this->id ]['parents'][ $pid ] : array();
	}
}

function topersia_relation( $id ) {
	return isset( $GLOBALS['tp_rel'][ (string) $id ] ) ? new TP_Stub_Relation( $id ) : false;
}

function topersia_render_ready() { return true; }
function topersia_render_path() { return TP_PLUGIN_DIR; }

function tp_do( $tag, $atts = array() ) {
	if ( ! isset( $GLOBALS['tp_shortcode'][ $tag ] ) ) { return "‹شورت‌کد $tag ثبت نشده›"; }
	return call_user_func( $GLOBALS['tp_shortcode'][ $tag ], $atts );
}
