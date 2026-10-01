<?php
/**
 * Minimal WordPress stubs so the plugin classes can be exercised without WP.
 *
 * Stores live in $GLOBALS so tests can seed and inspect state.
 *
 * @package TP_AI_Summarizer_Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/wp/' );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

$GLOBALS['tp_options']     = array();
$GLOBALS['tp_postmeta']    = array();
$GLOBALS['tp_transients']  = array();
$GLOBALS['tp_hooks']       = array();
$GLOBALS['tp_shortcodes']  = array();
$GLOBALS['tp_enqueued']    = array();
$GLOBALS['tp_localized']   = array();
$GLOBALS['tp_registered']  = array();
$GLOBALS['tp_ajax']        = null;
$GLOBALS['tp_is_admin']    = false;
$GLOBALS['tp_locale']      = 'en_US';
$GLOBALS['tp_current']     = 0;
$GLOBALS['tp_posts']       = array();
$GLOBALS['tp_post_types']  = array( 'post' => true, 'page' => true );
$GLOBALS['tp_loop']        = true;
$GLOBALS['tp_main_query']  = true;
$GLOBALS['tp_is_singular'] = true;
$GLOBALS['tp_is_home']     = false;
$GLOBALS['tp_is_archive']  = false;
$GLOBALS['tp_is_search']   = false;
$GLOBALS['tp_http']        = array(
	'code' => 200,
	'body' => json_encode( array(
		'choices' => array(
			array( 'message' => array( 'content' => 'OK' ), 'finish_reason' => 'stop' ),
		),
	) ),
);

/**
 * Stops execution the way wp_die() would, carrying a payload.
 */
class TP_Test_Ajax_Exit extends Exception {
	public $payload;
	public function __construct( $payload ) {
		$this->payload = $payload;
		parent::__construct( 'ajax-stop' );
	}
}

/**
 * Stand-in for WP_Error.
 */
class WP_Error {
	public $code = '';
	public $message = '';
	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
		$this->message = $message;
	}
	public function get_error_message() {
		return $this->message;
	}
}

/**
 * Stand-in for WP_Post.
 */
class WP_Post {
	public $ID = 0;
	public $post_title = '';
	public $post_content = '';
	public $post_type = 'post';
	/* WP_Post واقعی همیشه این دو را دارد؛ ۱٫۱٫۱ «فقط منتشرشده» را می‌سنجد (P5-D247). */
	public $post_status = 'publish';
	public $post_password = '';
}

function add_action( $hook, $cb ) {
	$GLOBALS['tp_hooks'][ $hook ][] = $cb;
}

function add_filter( $hook, $cb, $pri = 10, $args = 1 ) {
	$GLOBALS['tp_hooks'][ $hook ][] = $cb;
}

function apply_filters( $hook, $value ) {
	$args = func_get_args();
	$args = array_slice( $args, 1 );
	if ( ! empty( $GLOBALS['tp_hooks'][ $hook ] ) ) {
		foreach ( $GLOBALS['tp_hooks'][ $hook ] as $cb ) {
			$value = call_user_func_array( $cb, array_merge( array( $value ), array_slice( $args, 1 ) ) );
		}
	}
	return $value;
}

function do_action( $hook ) {
	if ( ! empty( $GLOBALS['tp_hooks'][ $hook ] ) ) {
		foreach ( $GLOBALS['tp_hooks'][ $hook ] as $cb ) {
			call_user_func_array( $cb, array_slice( func_get_args(), 1 ) );
		}
	}
}

function did_action( $hook ) {
	return isset( $GLOBALS['tp_did'][ $hook ] ) ? $GLOBALS['tp_did'][ $hook ] : 0;
}

function get_option( $key, $default = false ) {
	return isset( $GLOBALS['tp_options'][ $key ] ) ? $GLOBALS['tp_options'][ $key ] : $default;
}

function update_option( $key, $value ) {
	$GLOBALS['tp_options'][ $key ] = $value;
	return true;
}

function add_option( $key, $value = '' ) {
	if ( ! isset( $GLOBALS['tp_options'][ $key ] ) ) {
		$GLOBALS['tp_options'][ $key ] = $value;
	}
	return true;
}

function delete_option( $key ) {
	unset( $GLOBALS['tp_options'][ $key ] );
	return true;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	if ( '' === $key ) {
		return isset( $GLOBALS['tp_postmeta'][ $post_id ] ) ? $GLOBALS['tp_postmeta'][ $post_id ] : array();
	}
	$all = isset( $GLOBALS['tp_postmeta'][ $post_id ] ) ? $GLOBALS['tp_postmeta'][ $post_id ] : array();
	$val = isset( $all[ $key ] ) ? $all[ $key ] : '';
	if ( $single ) {
		return $val;
	}
	return array( $val );
}

function update_post_meta( $post_id, $key, $value ) {
	$GLOBALS['tp_postmeta'][ $post_id ][ $key ] = $value;
	return true;
}

function delete_post_meta( $post_id, $key ) {
	if ( isset( $GLOBALS['tp_postmeta'][ $post_id ][ $key ] ) ) {
		unset( $GLOBALS['tp_postmeta'][ $post_id ][ $key ] );
	}
	return true;
}

function get_transient( $key ) {
	if ( ! isset( $GLOBALS['tp_transients'][ $key ] ) ) {
		return false;
	}
	$t = $GLOBALS['tp_transients'][ $key ];
	if ( $t['expires'] < time() ) {
		unset( $GLOBALS['tp_transients'][ $key ] );
		return false;
	}
	return $t['value'];
}

function set_transient( $key, $value, $expiration ) {
	$GLOBALS['tp_transients'][ $key ] = array( 'value' => $value, 'expires' => time() + (int) $expiration );
	return true;
}

function delete_transient( $key ) {
	unset( $GLOBALS['tp_transients'][ $key ] );
	return true;
}

function wp_parse_args( $args, $defaults = array() ) {
	if ( is_object( $args ) ) {
		$args = get_object_vars( $args );
	}
	if ( ! is_array( $args ) ) {
		$args = array();
	}
	return array_merge( $defaults, $args );
}

function wp_unslash( $value ) {
	return is_array( $value )
		? array_map( 'wp_unslash', $value )
		: stripslashes( (string) $value );
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	$options |= JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
	return json_encode( $data, $options, $depth );
}

function absint( $maybe_int ) {
	return max( 0, (int) $maybe_int );
}

function sanitize_key( $key ) {
	return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key ) );
}

function sanitize_text_field( $str ) {
	return trim( wp_strip_all_tags( (string) $str ) );
}

function sanitize_textarea_field( $str ) {
	return trim( wp_strip_all_tags( (string) $str ) );
}

function sanitize_hex_color( $color ) {
	if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', trim( $color ) ) ) {
		return $color;
	}
	return '';
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return esc_html( $text );
}

function esc_html__( $text, $domain = 'default' ) {
	return esc_html( $text );
}

function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( $text );
}

function esc_html_e( $text, $domain = 'default' ) {
	echo esc_html( $text );
}

function esc_attr_e( $text, $domain = 'default' ) {
	echo esc_attr( $text );
}

function esc_textarea( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function __( $text, $domain = 'default' ) {
	return $text;
}

function _e( $text, $domain = 'default' ) {
	echo $text;
}

function _x( $text, $context, $domain = 'default' ) {
	return $text;
}

function _n( $single, $plural, $number, $domain = 'default' ) {
	return ( 1 === $number ) ? $single : $plural;
}

function wp_strip_all_tags( $text ) {
	return strip_tags( (string) $text );
}

/**
 * Minimal kses: keep only the allowed tags (along with a small whitelist of
 * harmless attributes), drop everything else.
 */
function wp_kses( $html, $allowed, $protocols = array() ) {
	if ( ! is_array( $allowed ) ) {
		$allowed = array();
	}
	$attr_ok = array( 'class', 'id', 'viewbox', 'width', 'height', 'aria-hidden', 'focusable', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'd', 'cx', 'cy', 'r', 'x', 'y', 'rx' );
	$html    = preg_replace( '/<!--.*?-->/s', '', (string) $html );

	return preg_replace_callback( '/<(\/?)([a-zA-Z0-9]+)([^>]*)>/', function ( $m ) use ( $allowed, $attr_ok ) {
		$tag = strtolower( $m[2] );
		if ( ! isset( $allowed[ $tag ] ) ) {
			return esc_html( $m[0] );
		}
		$attrs = '';
		if ( ! $m[1] ) {
			if ( preg_match_all( '/([a-zA-Z0-9\-]+)\s*=\s*"([^"]*)"/', $m[3], $pairs, PREG_SET_ORDER ) ) {
				$out = array();
				foreach ( $pairs as $p ) {
					$name = strtolower( $p[1] );
					if ( in_array( $name, $attr_ok, true ) ) {
						$out[] = $name . '="' . esc_attr( $p[2] ) . '"';
					}
				}
				if ( $out ) {
					$attrs = ' ' . implode( ' ', $out );
				}
			}
		}
		return '<' . $m[1] . $tag . $attrs . '>';
	}, $html );
}

function esc_url( $url, $protocols = null ) {
	return esc_url_raw( $url );
}

function esc_url_raw( $url ) {
	$url = (string) $url;
	if ( '' === $url ) {
		return '';
	}
	/* `#` داخل کلاس نویسه همان جداکنندۀ الگو بود و الگو را می‌شکست. */
	return preg_replace( '#[^a-zA-Z0-9\-._~:/?\#\[\]@!$&\'()*+,;=%]#', '', $url );
}

function untrailingslashit( $value ) {
	return rtrim( (string) $value, '/\\' );
}

function trailingslashit( $value ) {
	return untrailingslashit( $value ) . '/';
}

function home_url( $path = '' ) {
	return 'https://example.test' . (string) $path;
}

function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . $path;
}

function get_bloginfo( $show = '' ) {
	if ( 'name' === $show ) {
		return 'ToPersia';
	}
	return 'ToPersia';
}

function get_locale() {
	return $GLOBALS['tp_locale'];
}

function is_admin() {
	return $GLOBALS['tp_is_admin'];
}

function is_singular() {
	return $GLOBALS['tp_is_singular'];
}

function is_home() {
	return $GLOBALS['tp_is_home'];
}

function is_archive() {
	return $GLOBALS['tp_is_archive'];
}

function is_search() {
	return $GLOBALS['tp_is_search'];
}

function is_main_query() {
	return $GLOBALS['tp_main_query'];
}

function in_the_loop() {
	return $GLOBALS['tp_loop'];
}

function get_the_ID() {
	return (int) $GLOBALS['tp_current'];
}

function get_post( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	if ( empty( $GLOBALS['tp_posts'][ $post_id ] ) ) {
		return null;
	}
	$p = $GLOBALS['tp_posts'][ $post_id ];
	if ( is_object( $p ) ) {
		return $p;
	}
	$post       = new WP_Post();
	$post->ID   = $post_id;
	$post->post_title   = isset( $p['title'] ) ? $p['title'] : '';
	$post->post_content = isset( $p['content'] ) ? $p['content'] : '';
	$post->post_type    = isset( $p['type'] ) ? $p['type'] : 'post';
	$post->post_status  = isset( $p['status'] ) ? $p['status'] : 'publish';
	$post->post_password = isset( $p['password'] ) ? $p['password'] : '';
	return $post;
}

function get_post_type( $post_id = null ) {
	$post = get_post( $post_id );
	return $post ? $post->post_type : false;
}

function get_post_types( $args = array(), $output = 'names' ) {
	$public = ! empty( $args['public'] );
	$list   = $GLOBALS['tp_post_types'];
	if ( $public ) {
		$list = array_filter( $list, function ( $t ) {
			return is_array( $t ) ? ( ! empty( $t['public'] ) ) : true;
		} );
	}
	if ( 'objects' === $output ) {
		$out = array();
		foreach ( $list as $slug => $t ) {
			$obj                = new stdClass();
			$obj->name          = $slug;
			$obj->labels        = new stdClass();
			$obj->labels->name  = $slug;
			$obj->public        = true;
			$out[ $slug ]       = $obj;
		}
		return $out;
	}
	return array_keys( $list );
}

function get_permalink( $post_id = 0 ) {
	$post = get_post( $post_id );
	return $post ? 'https://example.test/permalink-' . $post_id . '/' : false;
}

function get_the_title( $post_id = 0 ) {
	$post = get_post( $post_id );
	return $post ? $post->post_title : '';
}

/**
 * Minimal excerpt stub: uses an explicit post_excerpt property when the
 * test seeded one (WP_Post stub doesn't declare it by default, so this is
 * read via a dynamic property), otherwise derives a short excerpt from
 * post_content the same way WP's own fallback does.
 *
 * @param WP_Post|int|null $post Post object or ID.
 * @return string
 */
function get_the_excerpt( $post = null ) {
	$post = is_object( $post ) ? $post : get_post( $post );
	if ( ! $post ) {
		return '';
	}
	if ( ! empty( $post->post_excerpt ) ) {
		return $post->post_excerpt;
	}
	$words = preg_split( '/\s+/u', trim( wp_strip_all_tags( $post->post_content ) ) );
	$words = array_filter( (array) $words, static function ( $w ) {
		return '' !== $w;
	} );
	return implode( ' ', array_slice( $words, 0, 55 ) );
}

/**
 * Taxonomy registry stub. Tests can seed
 * $GLOBALS['tp_taxonomies'][$post_type] = array( 'category', 'post_tag' )
 * before calling into code that needs it; defaults to none registered.
 *
 * @param string $post_type Post type.
 * @param string $output    'names' (only supported mode here).
 * @return string[]
 */
function get_object_taxonomies( $post_type, $output = 'names' ) {
	return isset( $GLOBALS['tp_taxonomies'][ $post_type ] ) ? $GLOBALS['tp_taxonomies'][ $post_type ] : array();
}

/**
 * Term-lookup stub, backed by $GLOBALS['tp_terms'][$post_id][$taxonomy] =
 * array( array('name' => '...'), ... ). Returns an empty array (not
 * WP_Error) when nothing is seeded, matching the "no terms" case that
 * TP_AI_Context::taxonomies() already treats as empty()/skip.
 *
 * @param int    $post_id  Post id.
 * @param string $taxonomy Taxonomy slug.
 * @return array|WP_Error
 */
function get_the_terms( $post_id, $taxonomy ) {
	return isset( $GLOBALS['tp_terms'][ $post_id ][ $taxonomy ] ) ? $GLOBALS['tp_terms'][ $post_id ][ $taxonomy ] : array();
}

/**
 * Standard WP_List_Util::pluck stand-in: pull one field out of a list of
 * arrays or objects.
 *
 * @param array      $list       List of arrays/objects.
 * @param int|string $field      Field to pluck.
 * @param int|string $index_key  Optional key to index the result by.
 * @return array
 */
function wp_list_pluck( $list, $field, $index_key = null ) {
	$out = array();
	foreach ( (array) $list as $key => $item ) {
		$value = is_object( $item ) ? ( $item->$field ?? null ) : ( $item[ $field ] ?? null );
		if ( null !== $index_key ) {
			$index_value = is_object( $item ) ? ( $item->$index_key ?? null ) : ( $item[ $index_key ] ?? null );
			$out[ $index_value ] = $value;
		} else {
			$out[ $key ] = $value;
		}
	}
	return $out;
}

/**
 * Supports the ( $key, $value, $url ) call signature used in this plugin's
 * admin screen. Not a full port of WP's add_query_arg() (which also
 * accepts an associative array as the first argument and can omit $url to
 * default to the current request URI - not needed by any current call
 * site here).
 *
 * @param string $key   Query arg name.
 * @param string $value Query arg value.
 * @param string $url   Base URL.
 * @return string
 */
function add_query_arg( $key, $value = null, $url = '' ) {
	$separator = ( false === strpos( $url, '?' ) ) ? '?' : '&';
	return $url . $separator . rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
}

/**
 * Autosave/revision checks. Tests can seed
 * $GLOBALS['tp_autosaves'][$post_id] = true / $GLOBALS['tp_revisions'][$post_id] = true
 * to exercise the save_post guard in TP_AI_Summary; defaults to "no" for both,
 * matching a normal, non-autosave, non-revision save.
 *
 * @param int $post_id Post id.
 * @return bool
 */
function wp_is_post_autosave( $post_id ) {
	return ! empty( $GLOBALS['tp_autosaves'][ $post_id ] );
}

function wp_is_post_revision( $post_id ) {
	return ! empty( $GLOBALS['tp_revisions'][ $post_id ] );
}

/**
 * Post-content-safe kses stub: reuses the wp_kses() stub above with the
 * standard set of tags WordPress's own wp_kses_post() allows.
 *
 * @param string $html Raw HTML.
 * @return string
 */
function wp_kses_post( $html ) {
	$allowed = array_fill_keys(
		array( 'p', 'br', 'strong', 'em', 'b', 'i', 'ul', 'ol', 'li', 'a', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'code' ),
		true
	);
	return wp_kses( $html, $allowed );
}

/**
 * Deterministic-enough stub (no cryptographic requirement in tests).
 *
 * @param int  $length             Password length.
 * @param bool $special_chars      Unused here.
 * @param bool $extra_special_chars Unused here.
 * @return string
 */
function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
	$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
	$out   = '';
	for ( $i = 0; $i < $length; $i++ ) {
		$out .= $chars[ array_rand( str_split( $chars ) ) ];
	}
	return $out;
}

function current_user_can( $cap, $args = null ) {
	return true;
}

/* ۱٫۱٫۱ — پنل خروجی را از ۱٫۱٫۰ به ورود وابسته کرد و stub جا مانده بود. */
function is_user_logged_in() {
	return ! isset( $GLOBALS['tp_logged_in'] ) || (bool) $GLOBALS['tp_logged_in'];
}

function get_current_user_id() {
	return 1;
}

function wp_create_nonce( $action = -1 ) {
	return md5( $action . ':nonce' );
}

function wp_verify_nonce( $nonce, $action = -1 ) {
	return wp_create_nonce( $action ) === $nonce;
}

function check_ajax_referer( $action, $query_arg = false, $die = true ) {
	$key    = $query_arg ? $query_arg : 'nonce';
	$given  = isset( $_POST[ $key ] ) ? (string) $_POST[ $key ] : ( isset( $_REQUEST[ $key ] ) ? (string) $_REQUEST[ $key ] : '' );
	$valid  = wp_verify_nonce( $given, $action );
	if ( ! $valid && $die ) {
		wp_die();
	}
	return $valid;
}

function wp_die( $message = '', $title = '', $args = array() ) {
	throw new TP_Test_Ajax_Exit( array( 'die' => $message ) );
}

/* امضای وردپرس: ( $value, $status_code = null, $flags = 0 ) — کد وضعیت هم ثبت می‌شود. */
function wp_send_json_success( $data = null, $status_code = null ) {
	$GLOBALS['tp_ajax'] = array( 'ok' => true, 'data' => $data, 'status' => $status_code );
	throw new TP_Test_Ajax_Exit( $GLOBALS['tp_ajax'] );
}

function wp_send_json_error( $data = null, $status_code = null ) {
	$GLOBALS['tp_ajax'] = array( 'ok' => false, 'data' => $data, 'status' => $status_code );
	throw new TP_Test_Ajax_Exit( $GLOBALS['tp_ajax'] );
}

function wp_json_encode_pretty( $data ) {
	return wp_json_encode( $data );
}

function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
	$field = '<input type="hidden" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '">';
	if ( $echo ) {
		echo $field;
	}
	return $field;
}

function wp_remote_post( $url, $args = array() ) {
	if ( isset( $GLOBALS['tp_http_override'] ) ) {
		$r = $GLOBALS['tp_http_override'];
		if ( is_a( $r, 'WP_Error' ) ) {
			return $r;
		}
		return array( 'response' => array( 'code' => $r['code'] ), 'body' => $r['body'] );
	}
	return array(
		'response' => array( 'code' => $GLOBALS['tp_http']['code'] ),
		'body'     => $GLOBALS['tp_http']['body'],
	);
}

function wp_remote_retrieve_response_code( $response ) {
	return isset( $response['response']['code'] ) ? $response['response']['code'] : 0;
}

function wp_remote_retrieve_body( $response ) {
	return isset( $response['body'] ) ? $response['body'] : '';
}

function is_wp_error( $thing ) {
	return is_a( $thing, 'WP_Error' );
}

function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	$GLOBALS['tp_enqueued']['scripts'][ $handle ] = true;
}

function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['tp_enqueued']['styles'][ $handle ] = true;
}

function wp_register_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
	$GLOBALS['tp_registered']['scripts'][ $handle ] = $src;
}

function wp_register_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['tp_registered']['styles'][ $handle ] = $src;
}

function wp_localize_script( $handle, $object_name, $data ) {
	$GLOBALS['tp_localized'][ $object_name ] = $data;
}

function add_shortcode( $tag, $cb ) {
	$GLOBALS['tp_shortcodes'][ $tag ] = $cb;
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$atts = (array) $atts;
	foreach ( $atts as $k => $v ) {
		if ( is_int( $k ) && isset( $pairs[ $v ] ) ) {
			$pairs[ $v ] = true;
		}
	}
	return array_merge( $pairs, $atts );
}

function do_shortcode( $content ) {
	foreach ( (array) $GLOBALS['tp_shortcodes'] as $tag => $cb ) {
		$content = preg_replace_callback(
			'/\[(' . preg_quote( $tag, '/' ) . ')([^\]]*)\]/',
			function ( $m ) use ( $cb ) {
				$atts = array();
				if ( preg_match_all( '/(\w+)\s*=\s*"([^"]*)"/', $m[2], $pairs, PREG_SET_ORDER ) ) {
					foreach ( $pairs as $p ) {
						$atts[ $p[1] ] = $p[2];
					}
				}
				return call_user_func( $cb, $atts );
			},
			$content
		);
	}
	return $content;
}

function register_block_type( $name, $args ) {
	$GLOBALS['tp_blocks'][ $name ] = true;
}

function register_setting( $group, $key, $sanitize = null ) {
	$GLOBALS['tp_settings'][ $key ] = $sanitize;
}

function add_options_page( $title, $menu_title, $cap, $slug, $cb ) {
	$GLOBALS['tp_admin_pages'][ $slug ] = $cb;
}

function add_meta_box( $id, $title, $cb, $screen, $context, $priority ) {
	$GLOBALS['tp_metaboxes'][] = $id;
}

function checked( $checked, $current = true, $echo = true ) {
	$out = ( $checked === $current ) ? ' checked="checked"' : '';
	if ( $echo ) {
		echo $out;
	}
	return $out;
}

function selected( $selected, $current = true, $echo = true ) {
	$out = ( $selected === $current ) ? ' selected="selected"' : '';
	if ( $echo ) {
		echo $out;
	}
	return $out;
}

function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

function plugin_dir_path( $file ) {
	return rtrim( str_replace( '\\', '/', dirname( $file ) ), '/' ) . '/';
}

function plugin_dir_url( $file ) {
	return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

function plugins_url( $path = '', $file = '' ) {
	return 'https://example.test/wp-content/plugins/' . ( $path ? ltrim( $path, '/' ) : '' );
}

function load_plugin_textdomain( $domain, $deprecated = false, $rel_path = false ) {
	return true;
}

function register_activation_hook( $file, $cb ) {
	$GLOBALS['tp_activation'][] = $cb;
}

function settings_fields( $group ) {
	echo '<input type="hidden" name="settings_fields" value="' . esc_attr( $group ) . '">';
}

function submit_button( $text = '', $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {
	echo '<button type="submit">' . esc_html( $text ) . '</button>';
}

function wp_safe_redirect( $location ) {
	$GLOBALS['tp_redirect'] = $location;
}

function current_filter() {
	return '';
}
/*
 * $wpdb ساختگی — ۱٫۱٫۰ جدول خلاصه‌ها را افزود و آزمون‌ها هیچ $wpdb نداشتند
 * (خطای مرگبار در get_summary). بی‌حافظه: هر خواندن هیچ برمی‌گرداند، هر
 * نوشتن موفق است؛ آخرین پرس‌وجوها برای ادعا نگه داشته می‌شوند.
 */
class TP_Test_WPDB {
	public $prefix     = 'wp_';
	public $postmeta   = 'wp_postmeta';
	public $options    = 'wp_options';
	public $last_error = '';
	public $queries    = array();
	public $indexes    = array();
	public function prepare( $query, ...$args ) {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) { $args = $args[0]; }
		foreach ( $args as $a ) {
			$query = preg_replace( '/%[sdf]/', is_numeric( $a ) ? (string) $a : "'" . addslashes( (string) $a ) . "'", $query, 1 );
		}
		return $query;
	}
	public function esc_like( $text ) { return addcslashes( (string) $text, '_%\\' ); }
	public function get_row( $query = null, $output = OBJECT ) { $this->queries[] = $query; return null; }
	public function get_var( $query = null ) { $this->queries[] = $query; return null; }
	public function get_col( $query = null ) { $this->queries[] = $query; return array(); }
	public function get_results( $query = null, $output = OBJECT ) {
		$this->queries[] = $query;
		if ( is_string( $query ) && 0 === stripos( ltrim( $query ), 'SHOW INDEX' ) ) { return $this->indexes; }
		return array();
	}
	public function query( $query ) { $this->queries[] = $query; return 0; }
	public function insert( $table, $data, $format = null ) { $this->queries[] = 'INSERT ' . $table; return 1; }
	public function update( $table, $data, $where, $format = null, $where_format = null ) { $this->queries[] = 'UPDATE ' . $table; return 1; }
	public function delete( $table, $where, $where_format = null ) { $this->queries[] = 'DELETE ' . $table; return 1; }
	public function get_charset_collate() { return ''; }
}

function current_time( $type, $gmt = 0 ) {
	return 'mysql' === $type ? gmdate( 'Y-m-d H:i:s' ) : time();
}

if ( ! defined( 'OBJECT' ) ) { define( 'OBJECT', 'OBJECT' ); }
if ( ! defined( 'ARRAY_A' ) ) { define( 'ARRAY_A', 'ARRAY_A' ); }

$GLOBALS['wpdb'] = new TP_Test_WPDB();
