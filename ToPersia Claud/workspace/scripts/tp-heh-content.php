<?php
/**
 * یکسان‌سازی «ۀ» در محتوا — P5-D265 · SPRINT-4.4 بند ۲٫۶ گام ۲ — فقط روی topersia.local.
 *
 *   php scripts/tp-heh-content.php --report             شمار «ه» + همزۀ بالا (U+0647 U+0654) در محتوا
 *   php scripts/tp-heh-content.php --apply              ← «ۀ» یک‌نویسه (U+06C0)؛ پشتیبان ردیف‌ها پیش از نوشتن
 *   php scripts/tp-heh-content.php --restore=FILE       برگرداندن از همان پشتیبان
 *
 * دامنه: عنوان، متن و چکیدۀ پست‌ها، و مقدار متاهای بی‌زیرخط. بیرون از
 * دامنه (فقط شمرده می‌شوند): `post_name` · `guid` · متای زیرخط‌دار ·
 * بازبینی‌ها و متای آن‌ها · برچسب‌ها · گزینه‌ها · نظرها.
 *
 * مقدار سریال‌شده امن باز و دوباره سریال می‌شود: بی شیء (allowed_classes
 * خاموش)، و فقط اگر سریال‌کردنِ دوبارۀ همان مقدارِ دست‌نخورده عیناً همان
 * رشته را بدهد — وگرنه دست نمی‌خورد و در گزارش می‌آید. فقط مقدارها، نه
 * کلیدها. توالیِ گریزخوردۀ JSON (`\u0647\u0654`) نویسۀ خام نیست و دست
 * نمی‌خورد؛ جدا شمرده می‌شود.
 *
 * نوشتن مستقیم با $wpdb، بی قلاب: یک نویسه به شکل دیگرش همان محتواست، پس
 * هش محتوا کثیف نمی‌شود و هیچ خروجی هوش مصنوعی «کهنه» نمی‌شود. کش پست و
 * متا پاک می‌شود. زنده بعداً، با پشتیبان کامل از طرف کاربر.
 *
 * کد خروج: ۰ سالم · ۱ پس از apply هنوز در دامنه مانده · ۲ محیط ناقص
 */

require_once dirname( __DIR__ ) . '/tests/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

const TP_HEH_TWO = "\u{0647}\u{0654}";
const TP_HEH_ONE = "\u{06C0}";
const TP_HEH_HEX = '0xD987D994';              /* بایت‌های UTF-8 همان دو نقطۀ کد */
const TP_HEH_DIR = TP_ROOT . '/'; /* بیرون از مخزن */

$mode    = isset( $argv[1] ) ? $argv[1] : '';
$restore = 0 === strpos( $mode, '--restore=' ) ? substr( $mode, 10 ) : '';

if ( ! in_array( $mode, array( '--report', '--apply' ), true ) && '' === $restore ) {
	fwrite( STDERR, "--report · --apply · --restore=FILE\n" );
	exit( 2 );
}

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی — topersia.local، محیط local، پایگاه topersia_local\n" );
	exit( 2 );
}

global $wpdb;

/* ── شمار ── */

/** ردیف‌های دامنه: [جدول، ستون شناسه، ستون، شرط]. */
function tp_heh_scope() {
	global $wpdb;
	return array(
		'posts.post_title'   => array( $wpdb->posts, 'ID', 'post_title', "post_type <> 'revision'" ),
		'posts.post_content' => array( $wpdb->posts, 'ID', 'post_content', "post_type <> 'revision'" ),
		'posts.post_excerpt' => array( $wpdb->posts, 'ID', 'post_excerpt', "post_type <> 'revision'" ),
		'postmeta.meta_value' => array( $wpdb->postmeta, 'meta_id', 'meta_value', "meta_key NOT LIKE '\\_%' AND post_id NOT IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' )" ),
	);
}

/** بیرون از دامنه — فقط شمار. */
function tp_heh_outside() {
	global $wpdb;
	return array(
		'posts.post_name (کدشده در نشانی)' => array( $wpdb->posts, 'ID', 'post_name', "post_name LIKE '%\\%d9\\%87\\%d9\\%94%'" ),
		'posts.guid'                      => array( $wpdb->posts, 'ID', 'guid', '1=1' ),
		'بازبینی‌ها'                       => array( $wpdb->posts, 'ID', 'CONCAT_WS(\' \', post_title, post_content, post_excerpt)', "post_type = 'revision'" ),
		'متای زیرخط‌دار'                   => array( $wpdb->postmeta, 'meta_id', 'meta_value', "meta_key LIKE '\\_%'" ),
		'متای بازبینی‌ها'                  => array( $wpdb->postmeta, 'meta_id', 'meta_value', "meta_key NOT LIKE '\\_%' AND post_id IN ( SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' )" ),
		'terms.name'                      => array( $wpdb->terms, 'term_id', 'name', '1=1' ),
		'term_taxonomy.description'       => array( $wpdb->term_taxonomy, 'term_taxonomy_id', 'description', '1=1' ),
		'options.option_value'            => array( $wpdb->options, 'option_id', 'option_value', '1=1' ),
		'comments.comment_content'        => array( $wpdb->comments, 'comment_ID', 'comment_content', '1=1' ),
	);
}

/** ردیف‌هایی که نویسۀ خامِ دونویسه دارند. */
function tp_heh_rows( $def ) {
	global $wpdb;
	list( $table, $id, $col, $where ) = $def;
	$like = false !== strpos( $where, 'post_name LIKE' ) ? '1=1' : 'INSTR( BINARY ' . $col . ', ' . TP_HEH_HEX . ' ) > 0';
	return $wpdb->get_results( "SELECT {$id} AS id, {$col} AS v" . ( $wpdb->postmeta === $table ? ', post_id, meta_key' : '' ) . " FROM {$table} WHERE {$where} AND {$like}", ARRAY_A );
}

function tp_heh_count( $rows, $raw = true ) {
	$n = 0;
	foreach ( $rows as $r ) {
		$n += $raw ? substr_count( (string) $r['v'], TP_HEH_TWO ) : max( 1, substr_count( strtolower( (string) $r['v'] ), '%d9%87%d9%94' ) );
	}
	return $n;
}

function tp_heh_escaped() {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key NOT LIKE '\\_%' AND LOWER( meta_value ) LIKE '%\\\\\\\\u0647\\\\\\\\u0654%'" );
}

function tp_heh_report( $title ) {

	echo "\n{$title}\n" . str_repeat( '─', 72 ) . "\n";
	printf( "  %-38s %8s %10s\n", 'در دامنه', 'ردیف', 'نویسه' );

	$total = 0;

	foreach ( tp_heh_scope() as $name => $def ) {
		$rows   = tp_heh_rows( $def );
		$n      = tp_heh_count( $rows );
		$total += $n;
		printf( "  %-38s %8d %10d\n", $name, count( $rows ), $n );
	}

	printf( "  %-38s %8s %10d\n", 'همه', '', $total );
	printf( "\n  %-38s %8s %10s\n", 'بیرون از دامنه — دست نمی‌خورد', 'ردیف', 'نویسه' );

	foreach ( tp_heh_outside() as $name => $def ) {
		$rows = tp_heh_rows( $def );
		printf( "  %-38s %8d %10d\n", $name, count( $rows ), tp_heh_count( $rows, false === strpos( $name, 'post_name' ) ) );
	}

	printf( "  %-38s %8d\n", 'JSON گریزخورده (\\u0647\\u0654)', tp_heh_escaped() );

	return $total;
}

/* ── جای‌گزینی امن ── */

function tp_heh_walk( $v, &$n ) {
	if ( is_string( $v ) ) {
		$n += substr_count( $v, TP_HEH_TWO );
		return str_replace( TP_HEH_TWO, TP_HEH_ONE, $v );
	}
	if ( is_array( $v ) ) {
		foreach ( $v as $k => $x ) {
			$v[ $k ] = tp_heh_walk( $x, $n );
		}
	}
	return $v;
}

function tp_heh_has_object( $v ) {
	if ( is_object( $v ) ) {
		return true;
	}
	if ( is_array( $v ) ) {
		foreach ( $v as $x ) {
			if ( tp_heh_has_object( $x ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * مقدار تازه، یا WP_Error اگر امن نیست.
 *
 * @return string|WP_Error
 */
function tp_heh_convert( $value, &$n ) {

	$n = 0;

	if ( ! is_serialized( $value ) ) {
		return tp_heh_walk( (string) $value, $n );
	}

	$data = @unserialize( $value, array( 'allowed_classes' => false ) ); // phpcs:ignore

	if ( false === $data && 'b:0;' !== $value ) {
		return new WP_Error( 'unserialize', 'سریال‌شدۀ ناخوانا' );
	}

	if ( tp_heh_has_object( $data ) ) {
		return new WP_Error( 'object', 'شیء درون مقدار سریال‌شده' );
	}

	if ( serialize( $data ) !== $value ) {
		return new WP_Error( 'roundtrip', 'سریال‌کردن دوباره همان رشته را نمی‌دهد' );
	}

	$new = serialize( tp_heh_walk( $data, $n ) );

	return is_serialized( $new ) && unserialize( $new, array( 'allowed_classes' => false ) ) == tp_heh_walk( $data, $dummy ) ? $new : new WP_Error( 'verify', 'بازخوانی نشد' ); // phpcs:ignore
}

/* ── برگرداندن ── */

if ( '' !== $restore ) {

	$file = realpath( $restore );

	if ( ! $file || 0 !== strpos( str_replace( '\\', '/', $file ), rtrim( TP_HEH_DIR, '/' ) ) || ! is_readable( $file ) ) {
		fwrite( STDERR, "پشتیبان باید در " . TP_HEH_DIR . " باشد\n" );
		exit( 2 );
	}

	$rows = json_decode( (string) file_get_contents( $file ), true );
	$n    = 0;

	foreach ( (array) $rows['rows'] as $r ) {
		$wpdb->update( $r['table'], array( $r['col'] => $r['old'] ), array( $r['idcol'] => $r['id'] ) );
		$n ++;
	}

	wp_cache_flush();
	echo "برگشت: {$n} ردیف از " . basename( $file ) . "\n";
	exit( 0 );
}

$before = tp_heh_report( 'پیش' );

if ( '--report' === $mode ) {
	exit( 0 );
}

/* ── apply ── */

$plan    = array();
$skipped = array();

foreach ( tp_heh_scope() as $name => $def ) {

	list( $table, $idcol, $col ) = $def;

	foreach ( tp_heh_rows( $def ) as $r ) {

		$new = tp_heh_convert( $r['v'], $n );

		if ( is_wp_error( $new ) ) {
			$skipped[] = $name . ' #' . $r['id'] . ( isset( $r['meta_key'] ) ? ' ' . $r['meta_key'] : '' ) . ': ' . $new->get_error_message();
			continue;
		}

		if ( $new !== $r['v'] ) {
			$plan[] = array( 'table' => $table, 'idcol' => $idcol, 'id' => (int) $r['id'], 'col' => $col, 'old' => $r['v'], 'new' => $new, 'n' => $n, 'post' => isset( $r['post_id'] ) ? (int) $r['post_id'] : (int) $r['id'] );
		}
	}
}

$backup = TP_HEH_DIR . 'heh-content-backup-' . gmdate( 'Ymd-His' ) . '.json';

if ( false === file_put_contents( $backup, wp_json_encode( array(
	'made'  => gmdate( 'c' ),
	'db'    => DB_NAME,
	'rows'  => array_map( function ( $p ) { return array_intersect_key( $p, array_flip( array( 'table', 'idcol', 'id', 'col', 'old' ) ) ); }, $plan ),
), JSON_UNESCAPED_UNICODE ) ) ) {
	fwrite( STDERR, "پشتیبان نوشته نشد — هیچ ردیفی تغییر نکرد\n" );
	exit( 2 );
}

$written = 0;
$chars   = 0;
$posts   = array();

foreach ( $plan as $p ) {
	if ( false !== $wpdb->update( $p['table'], array( $p['col'] => $p['new'] ), array( $p['idcol'] => $p['id'] ) ) ) {
		$written ++;
		$chars          += $p['n'];
		$posts[ $p['post'] ] = true;
	}
}

foreach ( array_keys( $posts ) as $id ) {
	clean_post_cache( $id );
	wp_cache_delete( $id, 'post_meta' );
}

echo "\n  نوشته شد: {$written} ردیف · {$chars} نویسه · پشتیبان: " . basename( $backup ) . "\n";

foreach ( $skipped as $s ) {
	echo "  دست نخورد — {$s}\n";
}

$after = tp_heh_report( 'پس' );

exit( $after > 0 && count( $skipped ) < $after ? 1 : 0 );
