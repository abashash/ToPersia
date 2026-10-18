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
/*
 * esc_url و wp_strip_all_tags و number_format_i18n عیناً به سختیِ وردپرس‌اند،
 * نه نرم‌تر.  ۴٫۲٫۱ بند ۱
 *
 * نسخهٔ نرم (`(string) $v`) آرایه را با یک هشدار به «Array» تبدیل می‌کرد؛
 * وردپرس `ltrim( $url )` می‌زند و PHP 8 همان‌جا TypeError می‌دهد — یعنی ۵۰۰.
 * آزمونی که از سایت مهربان‌تر است، خطای سایت را نمی‌بیند.
 */
function esc_url( $v ) {
	if ( '' === $v ) { return $v; }
	return htmlspecialchars( str_replace( ' ', '%20', ltrim( $v ) ), ENT_QUOTES, 'UTF-8' );
}
/*
 * ترجمه — مثل وردپرس: بی کاتالوگ همان منبع فارسی. آزمون زبان را با
 * `$GLOBALS['tp_l10n']` عوض می‌کند: همان آرایۀ `messages` پروندۀ
 * `languages/topersia-{locale}.l10n.php` (بافت «بافت\4متن»، جمع «مفرد\0جمع») و
 * `$GLOBALS['tp_l10n_plural']` قاعدۀ شکل جمع.  ۴٫۴٫۲
 */
function tp_l10n_get( $key, $d ) {
	return ( 'topersia' === $d && isset( $GLOBALS['tp_l10n'][ $key ] ) && '' !== $GLOBALS['tp_l10n'][ $key ] ) ? $GLOBALS['tp_l10n'][ $key ] : null;
}
function __( $v, $d = '' ) { $t = tp_l10n_get( $v, $d ); return null === $t ? $v : $t; }
function esc_html__( $v, $d = '' ) { return esc_html( __( $v, $d ) ); }
function esc_attr__( $v, $d = '' ) { return esc_attr( __( $v, $d ) ); }
function _x( $v, $ctx, $d = '' ) { $t = tp_l10n_get( $ctx . "\4" . $v, $d ); return null === $t ? $v : $t; }
function _n( $single, $plural, $n, $d = '' ) {
	$t = tp_l10n_get( $single . "\0" . $plural, $d );
	if ( null === $t ) { return 1 === (int) $n ? $single : $plural; }
	$forms = explode( "\0", $t );
	$rule  = isset( $GLOBALS['tp_l10n_plural'] ) ? $GLOBALS['tp_l10n_plural'] : function ( $n ) { return 1 === $n ? 0 : 1; };
	$i     = (int) $rule( (int) $n );
	return isset( $forms[ $i ] ) ? $forms[ $i ] : end( $forms );
}
function wp_strip_all_tags( $v ) {
	/* وردپرس ۶٫۲+: null خالی است، غیرعددی/غیررشته‌ای _doing_it_wrong و خالی. */
	if ( null === $v ) { return ''; }
	if ( ! is_scalar( $v ) ) {
		trigger_error( 'wp_strip_all_tags(): ' . gettype( $v ) . ' given', E_USER_NOTICE );
		return '';
	}
	$v = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $v );
	return trim( strip_tags( $v ) );
}
/*
 * wp_kses — همان‌قدر سخت‌گیر که وردپرس.  P5-D230
 *   برچسب مجاز می‌ماند و فقط ویژگی‌های مجازش را نگه می‌دارد
 *   برچسب نامجاز برداشته می‌شود و متن درونش می‌ماند — رفتار خودِ kses
 *   توضیح HTML برداشته می‌شود؛ `href` فقط با پروتکل مجاز
 * نرم‌ترش — مثلاً نگه‌داشتن `class` — آزمونِ «هیچ ویژگی بیگانه» را بی‌معنا می‌کرد.
 */
function wp_kses( $html, $allowed ) {
	$html = preg_replace( '/<!--.*?-->/s', '', (string) $html );
	return preg_replace_callback( '#<(/?)([a-zA-Z][a-zA-Z0-9]*)([^>]*)>#', function ( $m ) use ( $allowed ) {
		$tag = strtolower( $m[2] );
		if ( ! isset( $allowed[ $tag ] ) ) { return ''; }
		if ( '/' === $m[1] ) { return '</' . $tag . '>'; }
		$attrs = '';
		preg_match_all( '#([a-zA-Z_:][-a-zA-Z0-9_:.]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))#', $m[3], $am, PREG_SET_ORDER );
		foreach ( $am as $a ) {
			$name = strtolower( $a[1] );
			if ( empty( $allowed[ $tag ][ $name ] ) ) { continue; }
			$val = isset( $a[4] ) && '' !== $a[4] ? $a[4] : ( isset( $a[3] ) && '' !== $a[3] ? $a[3] : $a[2] );
			if ( 'href' === $name && preg_match( '#^\s*([a-z0-9+.-]+):#i', $val, $pm ) && ! in_array( strtolower( $pm[1] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) { continue; }
			$attrs .= ' ' . $name . '="' . htmlspecialchars( $val, ENT_QUOTES, 'UTF-8' ) . '"';
		}
		return '<' . $tag . $attrs . ( preg_match( '#/\s*$#', $m[3] ) ? ' /' : '' ) . '>';
	}, $html );
}
function home_url( $p = '/' ) { return 'https://topersia.com' . $p; }

function add_shortcode( $tag, $cb ) { $GLOBALS['tp_shortcode'][ $tag ] = $cb; }
function shortcode_exists( $tag ) { return isset( $GLOBALS['tp_shortcode'][ $tag ] ); }
function add_action() {}
function add_filter( $tag = '', $cb = null, $prio = 10, $args = 1 ) {
	if ( $cb ) { $GLOBALS['tp_filters'][ $tag ][] = $cb; }
}
function has_filter() { return false; }
function apply_filters( $tag, $value ) {
	$extra = array_slice( func_get_args(), 2 );
	$list  = isset( $GLOBALS['tp_filters'][ $tag ] ) ? $GLOBALS['tp_filters'][ $tag ] : array();
	foreach ( $list as $cb ) {
		$value = call_user_func_array( $cb, array_merge( array( $value ), $extra ) );
	}
	return $value;
}

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

	/* alltypes.php برچسبِ هر ۴۲ نوع را از اسکین می‌گذارد — مثل وردپرسِ زنده، که برای هیچ نوعِ ثبت‌شده‌ای null نمی‌دهد. */
	if ( isset( $GLOBALS['tp_pt_labels'] ) ) {
		$names = $GLOBALS['tp_pt_labels'];
	}

	/*
	 * هر نوع tp_ ثبت‌شده است، مثل وردپرس زنده؛ برچسبِ بیرون از فهرست بالا
	 * خالی می‌ماند تا خروجی‌های پیشین دست نخورند.  ۴٫۳٫۰ بند ۰
	 */
	if ( ! isset( $names[ $type ] ) && 0 !== strpos( (string) $type, 'tp_' ) ) {
		return null;
	}

	$o                       = new stdClass();
	$o->labels               = new stdClass();
	$o->labels->singular_name = isset( $names[ $type ] ) ? $names[ $type ] : '';

	/* همان سایت زنده: هر ۴۲ نوع در JetEngine `publicly_queryable` دارند،
	   شهرستان هم (CHANGE-PACKAGE-001 بند ۶٫۱). آزمون می‌تواند خاموشش کند. */
	$o->publicly_queryable = ! isset( $GLOBALS['tp_pt_private'][ $type ] );

	return $o;
}

/** وضعیت انتشار — پیش‌فرض منتشرشده؛ داده‌ٔ آزمون می‌تواند `status` بدهد. */
function get_post_status( $id ) {
	if ( ! isset( $GLOBALS['tp_posts'][ $id ] ) ) {
		return false;
	}
	return isset( $GLOBALS['tp_posts'][ $id ]['status'] ) ? $GLOBALS['tp_posts'][ $id ]['status'] : 'publish';
}

/*
 * پست — همان ویژگی‌هایی که WP_Post همیشه دارد. داده‌ٔ آزمون می‌تواند `status`
 * و `password` بدهد؛ پستِ ناموجود null، مثل وردپرس.  P5-D247
 */
function get_post( $id = null ) {
	$id = null === $id ? $GLOBALS['tp_current'] : $id;
	if ( ! is_scalar( $id ) || ! isset( $GLOBALS['tp_posts'][ $id ] ) ) {
		return null;
	}
	$p                = $GLOBALS['tp_posts'][ $id ];
	$o                = new stdClass();
	$o->ID            = (int) $id;
	$o->post_type     = isset( $p['type'] ) ? $p['type'] : '';
	$o->post_title    = isset( $p['title'] ) ? $p['title'] : '';
	$o->post_status   = isset( $p['status'] ) ? $p['status'] : 'publish';
	$o->post_password = isset( $p['password'] ) ? $p['password'] : '';
	$o->post_content  = '';
	$o->post_excerpt  = '';
	return $o;
}

/*
 * ابعاد پیوست — همان `wp_get_attachment_image_src` وردپرس: [نشانی، پهنا، بلندی،
 * میانی؟]. ابعاد از `$GLOBALS['tp_img_dims'][ id ]`، پیش‌فرض ۱۶۰۰×۱۰۶۷؛ [0, 0]
 * یعنی پیوستِ بی‌فراداده (مثل SVG) که وردپرس هم برایش ابعاد ندارد. اندازه‌های
 * میانی مثل پیش‌فرض وردپرس: thumbnail برش ۱۵۰، medium ۳۰۰، medium_large پهنای
 * ۷۶۸، large ۱۰۲۴.  P5-D246
 */
function wp_get_attachment_image_src( $id, $size = 'thumbnail', $icon = false ) {
	if ( ! $id ) { return false; }
	list( $w, $h ) = isset( $GLOBALS['tp_img_dims'][ $id ] ) ? $GLOBALS['tp_img_dims'][ $id ] : array( 1600, 1067 );
	$box = array( 'medium' => array( 300, 300 ), 'medium_large' => array( 768, 0 ), 'large' => array( 1024, 1024 ) );
	if ( $w && $h && 'thumbnail' === $size ) {
		$w = 150;
		$h = 150;
	} elseif ( $w && $h && isset( $box[ $size ] ) ) {
		$r = min( 1, $box[ $size ][0] / $w, $box[ $size ][1] ? $box[ $size ][1] / $h : 1 );
		$w = (int) round( $w * $r );
		$h = (int) round( $h * $r );
	}
	return array( 'https://topersia.com/wp-content/uploads/' . absint( $id ) . '.jpg', (int) $w, (int) $h, 'full' !== $size );
}

/*
 * همان شکلِ `wp_get_attachment_image` وردپرس: width و height از فراداده
 * (image_hwstring — پیوستِ بی‌ابعاد هیچ‌کدام را نمی‌گیرد)، srcset از نسخه‌های
 * میانی، و هر ویژگی‌ای که فراخوان داده. کلاس فقط همانی که فراخوان داده:
 * کلاس پیش‌فرضِ وردپرس (attachment-* size-*) از قالب ما نیست و گارد P5-D180
 * خروجیِ خودِ ما را می‌سنجد.
 */
function wp_get_attachment_image( $id, $size = 'full', $icon = false, $attr = array() ) {
	$src = wp_get_attachment_image_src( $id, $size );
	if ( ! $src ) { return ''; }
	$attr = (array) $attr;
	$html = '<img' . ( $src[1] && $src[2] ? ' width="' . $src[1] . '" height="' . $src[2] . '"' : '' )
		. ' class="' . esc_attr( isset( $attr['class'] ) ? $attr['class'] : '' ) . '"'
		. ' src="https://topersia.com/wp-content/uploads/' . absint( $id ) . '.jpg"'
		. ' alt="' . esc_attr( isset( $attr['alt'] ) ? $attr['alt'] : '' ) . '"';
	if ( ! isset( $attr['srcset'] ) && $src[1] && $src[2] ) {
		$full = wp_get_attachment_image_src( $id, 'full' );
		$set  = array();
		foreach ( array( 'medium', 'medium_large', 'large', 'full' ) as $one ) {
			$s = wp_get_attachment_image_src( $id, $one );
			if ( $s[1] <= $full[1] && ! isset( $set[ $s[1] ] ) ) {
				$set[ $s[1] ] = 'https://topersia.com/wp-content/uploads/' . absint( $id ) . ( 'full' === $one ? '' : '-' . $s[1] . 'x' . $s[2] ) . '.jpg ' . $s[1] . 'w';
			}
		}
		$attr['srcset'] = implode( ', ', $set );
	}
	foreach ( array( 'loading', 'decoding', 'fetchpriority', 'srcset', 'sizes' ) as $k ) {
		if ( isset( $attr[ $k ] ) && false !== $attr[ $k ] && '' !== (string) $attr[ $k ] ) {
			$html .= ' ' . $k . '="' . esc_attr( $attr[ $k ] ) . '"';
		}
	}
	return $html . '>';
}

/* تصویر شاخص — `_thumbnail_id` همان متای وردپرس. */
function get_post_thumbnail_id( $post = null ) {
	return absint( get_post_meta( is_object( $post ) ? $post->ID : (int) $post, '_thumbnail_id', true ) );
}

function get_the_post_thumbnail( $post = null, $size = 'post-thumbnail', $attr = '' ) {
	$thumb = get_post_thumbnail_id( $post );
	return $thumb ? wp_get_attachment_image( $thumb, $size, false, (array) $attr ) : '';
}

function wp_get_attachment_url( $id ) { return $id ? 'https://topersia.com/audio/' . absint( $id ) . '.mp3' : ''; }
/* توضیحِ دلخواه برای هر پیوست — تا «سطر دوم فقط توضیح» سنجیدنی باشد.  ۴٫۲٫۱ بند ۸ */
function wp_get_attachment_caption( $id ) {
	return isset( $GLOBALS['tp_captions'][ $id ] ) ? $GLOBALS['tp_captions'][ $id ] : 'تصویر ' . absint( $id );
}

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
		$GLOBALS['tp_rel_calls'] = ( isset( $GLOBALS['tp_rel_calls'] ) ? $GLOBALS['tp_rel_calls'] : 0 ) + 1;
		return isset( $GLOBALS['tp_rel'][ $this->id ]['children'][ $pid ] )
			? $GLOBALS['tp_rel'][ $this->id ]['children'][ $pid ] : array();
	}
	public function get_parents( $pid, $f = 'ids' ) {
		$GLOBALS['tp_rel_calls'] = ( isset( $GLOBALS['tp_rel_calls'] ) ? $GLOBALS['tp_rel_calls'] : 0 ) + 1;
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
	/* هر فراخوانی یک «درخواست» است: حافظهٔ درون‌درخواستی SC1 با دادهٔ دستکاری‌شدهٔ آزمون کهنه نشود. */
	if ( function_exists( 'topersia_g_memo_reset' ) ) { topersia_g_memo_reset(); }
	if ( ! isset( $GLOBALS['tp_shortcode'][ $tag ] ) ) { return "‹شورت‌کد $tag ثبت نشده›"; }
	return call_user_func( $GLOBALS['tp_shortcode'][ $tag ], $atts );
}

/* همان مقداری که وردپرس در wp-includes/class-wpdb.php تعریف می‌کند. */
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

/* ── افزوده‌های ۴٫۲٫۰ ───────────────────────────────────────────────── */

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ) {
		return json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}
}

if ( ! function_exists( 'post_type_exists' ) ) {
	function post_type_exists( $type ) {
		return false;
	}
}

if ( ! function_exists( 'get_post_time' ) ) {
	function get_post_time( $format = 'U', $gmt = false, $post = null ) {
		return '';
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
		/* بی‌تبدیل، مثل وردپرس: رشتهٔ غیرعددی در PHP 8 همین‌جا TypeError است. */
		return number_format( $number, absint( $decimals ), '.', '' );
	}
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	function wp_get_attachment_image_url( $id, $size = 'thumbnail' ) {
		return 'https://example.test/img/' . absint( $id ) . '-' . $size . '.jpg';
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $id = 0 ) {
		return '';
	}
}

/* جهت صفحه — پیش‌فرض فارسی؛ آزمون با $GLOBALS['tp_ltr'] چپ‌به‌راستش می‌کند.  P5-D243 */
if ( ! function_exists( 'is_rtl' ) ) {
	function is_rtl() { return empty( $GLOBALS['tp_ltr'] ); }
}

/* ── افزوده‌های ۴٫۳٫۰ ───────────────────────────────────────────────── */

/* کاربر: پیش‌فرض مهمان؛ آزمون با $GLOBALS['tp_user'] واردش می‌کند. */
if ( ! function_exists( 'is_user_logged_in' ) ) {
	function is_user_logged_in() { return ! empty( $GLOBALS['tp_user'] ); }
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() { return empty( $GLOBALS['tp_user'] ) ? 0 : (int) $GLOBALS['tp_user']; }
}
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) { return 'n' . substr( md5( (string) $action ), 0, 9 ); }
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $p = '' ) { return 'https://topersia.com/geo/wp-admin/' . ltrim( $p, '/' ); }
}
if ( ! function_exists( 'wp_login_url' ) ) {
	function wp_login_url( $r = '' ) { return 'https://topersia.com/geo/wp-login.php'; }
}
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $key, $value = null, $url = null ) {
		if ( is_array( $key ) ) {
			$url   = $value;
			$pairs = $key;
		} else {
			$pairs = array( $key => $value );
		}
		$url = (string) $url;
		$q   = array();
		foreach ( $pairs as $k => $v ) { $q[] = $k . '=' . $v; }
		return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . implode( '&', $q );
	}
}

/* user-core روی پشتهٔ زنده هست؛ آزمون همان را فرض می‌کند و با فیلتر
   `topersia_g_user_core` حالتِ «نیست» را جدا می‌سنجد.  ۴٫۳٫۰ بند ۵ */
if ( ! defined( 'TP_UC_REAL' ) && ! class_exists( 'ToPersia_JetEngine' ) ) { class ToPersia_JetEngine {} }
if ( ! defined( 'TP_UC_REAL' ) && ! class_exists( 'ToPersia_Follow' ) ) { class ToPersia_Follow {} }

/* گزینه، ایمیل، ترنزینت و متای کاربر — حافظهٔ درون‌فرایندی. */
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $k, $d = false ) { return isset( $GLOBALS['tp_options'][ $k ] ) ? $GLOBALS['tp_options'][ $k ] : $d; }
}
if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $v ) { return filter_var( (string) $v, FILTER_VALIDATE_EMAIL ) ? (string) $v : ''; }
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $k ) { return isset( $GLOBALS['tp_transients'][ $k ] ) ? $GLOBALS['tp_transients'][ $k ] : false; }
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $k, $v, $ttl = 0 ) { $GLOBALS['tp_transients'][ $k ] = $v; return true; }
}
if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $u, $k, $single = true ) { return isset( $GLOBALS['tp_umeta'][ $u ][ $k ] ) ? $GLOBALS['tp_umeta'][ $u ][ $k ] : ''; }
}
if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $u, $k, $v ) { $GLOBALS['tp_umeta'][ $u ][ $k ] = $v; return true; }
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $s ) { return rtrim( (string) $s, '/' ) . '/'; }
}
if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $s ) { return rtrim( (string) $s, '/' ); }
}
