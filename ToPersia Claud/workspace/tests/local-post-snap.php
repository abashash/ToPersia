<?php
/**
 * عکس و برگرداندن کامل چند پست — برای آزمونی که باید یک پست واقعی را در پیشخوان «به‌روزرسانی» کند و سپس هیچ ردی نگذارد.
 * دستۀ 55-ب بند ۲ (P5-D644): «ذخیرۀ مجدد در پیشخوان مقدار خالی فصل را نگه می‌دارد … سپس بازگرداندن». فقط topersia.local.
 *
 *   php tests/local-post-snap.php pick <type,type…>        ← JSON: برای هر نوع یک پست fa منتشرشدۀ غیرنمایشی بی best_season
 *   php tests/local-post-snap.php save <file> <id> [id…]   ← عکس: ردیف posts · همۀ postmeta · term_relationships · بازنگری‌ها ·
 *                                                             ردیف‌های jet_rel_* · icl_translations — برای خود پست و همۀ ترجمه‌هایش
 *   php tests/local-post-snap.php restore <file>           ← همان‌ها عیناً (شناسۀ meta و ردیف رابطه همان)؛ بازنگری تازه حذف
 *   php tests/local-post-snap.php check <file>             ← فرق وضعیت کنونی با عکس؛ کد ۱ اگر فرقی هست
 *   php tests/local-post-snap.php season <id> [id…]        ← JSON: مقدار خام best_season هر پست (null = نبود ردیف)
 *
 * خروجی: ۰ سالم · ۱ فرق/ناسازگاری · ۲ محیط/ورودی.
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
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

global $wpdb;
$mode = isset( $argv[1] ) ? (string) $argv[1] : '';

/** شناسۀ خود پست‌ها و همۀ ترجمه‌هایشان (WPML trid). */
function tp_ps_family( array $ids ) {
	global $wpdb;
	$out = array_map( 'absint', $ids );
	$t   = $wpdb->prefix . 'icl_translations';
	foreach ( $ids as $id ) {
		$trid = $wpdb->get_var( $wpdb->prepare( "SELECT trid FROM {$t} WHERE element_id = %d AND element_type LIKE 'post\\_%%'", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( $trid ) {
			foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT element_id FROM {$t} WHERE trid = %d AND element_type LIKE 'post\\_%%'", $trid ) ) as $e ) { // phpcs:ignore WordPress.DB.PreparedSQL
				$out[] = (int) $e;
			}
		}
	}
	$out = array_values( array_unique( array_filter( $out ) ) );
	sort( $out );
	return $out;
}

/** جدول‌های رابطۀ JetEngine که ستون parent_object_id دارند. */
function tp_ps_rel_tables() {
	global $wpdb;
	$out = array();
	foreach ( (array) $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix . 'jet_rel_' ) . '%' ) ) as $t ) {
		$cols = (array) $wpdb->get_col( "SHOW COLUMNS FROM `{$t}`" ); // phpcs:ignore WordPress.DB.PreparedSQL
		if ( in_array( 'parent_object_id', $cols, true ) && in_array( 'child_object_id', $cols, true ) ) {
			$out[] = $t;
		}
	}
	return $out;
}

/** عکس کامل خانوادۀ پست‌ها. */
function tp_ps_take( array $fam ) {
	global $wpdb;
	$in   = implode( ',', array_map( 'intval', $fam ) );
	$snap = array(
		'family' => $fam,
		'posts'  => $wpdb->get_results( "SELECT * FROM {$wpdb->posts} WHERE ID IN ({$in}) ORDER BY ID", ARRAY_A ), // phpcs:ignore WordPress.DB.PreparedSQL
		'meta'   => $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE post_id IN ({$in}) ORDER BY meta_id", ARRAY_A ), // phpcs:ignore WordPress.DB.PreparedSQL
		'terms'  => $wpdb->get_results( "SELECT * FROM {$wpdb->term_relationships} WHERE object_id IN ({$in}) ORDER BY object_id, term_taxonomy_id", ARRAY_A ), // phpcs:ignore WordPress.DB.PreparedSQL
		'kids'   => array_map( 'intval', (array) $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_parent IN ({$in}) AND post_type = 'revision' ORDER BY ID" ) ), // phpcs:ignore WordPress.DB.PreparedSQL
		'icl'    => $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_id IN ({$in}) AND element_type LIKE 'post\\_%' ORDER BY translation_id", ARRAY_A ), // phpcs:ignore WordPress.DB.PreparedSQL
		'rel'    => array(),
	);
	foreach ( tp_ps_rel_tables() as $t ) {
		$snap['rel'][ $t ] = $wpdb->get_results( "SELECT * FROM `{$t}` WHERE parent_object_id IN ({$in}) OR child_object_id IN ({$in}) ORDER BY 1", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
	}
	return $snap;
}

/** فرق دو عکس — فهرست خوانا. */
function tp_ps_diff( array $a, array $b ) {
	$out = array();
	foreach ( array( 'posts', 'meta', 'terms', 'kids', 'icl' ) as $k ) {
		if ( wp_json_encode( $a[ $k ] ) !== wp_json_encode( $b[ $k ] ) ) {
			$out[] = $k;
		}
	}
	foreach ( array_unique( array_merge( array_keys( $a['rel'] ), array_keys( $b['rel'] ) ) ) as $t ) {
		if ( wp_json_encode( $a['rel'][ $t ] ?? array() ) !== wp_json_encode( $b['rel'][ $t ] ?? array() ) ) {
			$out[] = 'rel:' . $t;
		}
	}
	if ( in_array( 'meta', $out, true ) ) {
		$ka = array();
		foreach ( $a['meta'] as $r ) {
			$ka[ $r['meta_id'] ] = $r;
		}
		$kb = array();
		foreach ( $b['meta'] as $r ) {
			$kb[ $r['meta_id'] ] = $r;
		}
		foreach ( array_unique( array_merge( array_keys( $ka ), array_keys( $kb ) ) ) as $mid ) {
			if ( wp_json_encode( $ka[ $mid ] ?? null ) !== wp_json_encode( $kb[ $mid ] ?? null ) ) {
				$r     = $ka[ $mid ] ?? $kb[ $mid ];
				$out[] = '  meta ' . $r['post_id'] . ' ' . $r['meta_key'];
			}
		}
	}
	return $out;
}

if ( 'pick' === $mode ) {
	$types = array_filter( array_map( 'sanitize_key', explode( ',', (string) ( $argv[2] ?? '' ) ) ) );
	$out   = array();
	foreach ( $types as $type ) {
		$id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 JOIN {$wpdb->prefix}icl_translations i ON i.element_id = p.ID AND i.element_type = CONCAT( 'post_', p.post_type ) AND i.language_code = 'fa'
			 LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'best_season'
			 LEFT JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key IN ( '_tp_demo', '_tp_sample' )
			 WHERE p.post_type = %s AND p.post_status = 'publish' AND p.post_password = '' AND d.meta_id IS NULL AND ( m.meta_id IS NULL OR m.meta_value = '' )
			 ORDER BY p.ID LIMIT 1",
			$type
		) );
		if ( $id ) {
			$out[ $type ] = $id;
		}
	}
	echo wp_json_encode( $out );
	exit( $out ? 0 : 1 );
}

if ( 'season' === $mode ) {
	$out = array();
	foreach ( array_slice( $argv, 2 ) as $id ) {
		$row              = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'best_season' LIMIT 1", (int) $id ) );
		$out[ (int) $id ] = $row ? (string) $row->meta_value : null;
	}
	echo wp_json_encode( $out );
	exit( 0 );
}

$file = (string) ( $argv[2] ?? '' );

if ( 'save' === $mode ) {
	$ids = array_filter( array_map( 'absint', array_slice( $argv, 3 ) ) );
	if ( '' === $file || ! $ids ) {
		fwrite( STDERR, "save <file> <id…>\n" );
		exit( 2 );
	}
	$snap = tp_ps_take( tp_ps_family( $ids ) );
	if ( false === file_put_contents( $file, wp_json_encode( $snap, JSON_UNESCAPED_UNICODE ) ) ) {
		fwrite( STDERR, "نوشتن نشد\n" );
		exit( 2 );
	}
	printf( "عکس: %d پست (با ترجمه‌ها) · %d meta · %d رابطه\n", count( $snap['family'] ), count( $snap['meta'] ), array_sum( array_map( 'count', $snap['rel'] ) ) );
	exit( 0 );
}

$snap = '' !== $file && is_file( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
if ( ! is_array( $snap ) || empty( $snap['family'] ) ) {
	fwrite( STDERR, "عکس نامعتبر\n" );
	exit( 2 );
}

if ( 'check' === $mode ) {
	$d = tp_ps_diff( $snap, tp_ps_take( $snap['family'] ) );
	echo $d ? "فرق:\n" . implode( "\n", $d ) . "\n" : "بی فرق با عکس\n";
	exit( $d ? 1 : 0 );
}

if ( 'restore' === $mode ) {
	$fam = array_map( 'intval', $snap['family'] );
	$in  = implode( ',', $fam );
	/* بازنگری‌های تازه — هر چه پس از عکس زیر این پست‌ها ساخته شد */
	$now_kids = array_map( 'intval', (array) $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_parent IN ({$in}) AND post_type = 'revision' ORDER BY ID" ) ); // phpcs:ignore WordPress.DB.PreparedSQL
	foreach ( array_diff( $now_kids, $snap['kids'] ) as $kid ) {
		$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $kid ) );
		$wpdb->delete( $wpdb->posts, array( 'ID' => $kid ) );
	}
	foreach ( $snap['posts'] as $row ) {
		$wpdb->replace( $wpdb->posts, $row );
	}
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL
	foreach ( $snap['meta'] as $row ) {
		$wpdb->insert( $wpdb->postmeta, $row );
	}
	$wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL
	foreach ( $snap['terms'] as $row ) {
		$wpdb->insert( $wpdb->term_relationships, $row );
	}
	foreach ( $snap['icl'] as $row ) {
		$wpdb->replace( $wpdb->prefix . 'icl_translations', $row );
	}
	foreach ( $snap['rel'] as $t => $rows ) {
		$wpdb->query( "DELETE FROM `{$t}` WHERE parent_object_id IN ({$in}) OR child_object_id IN ({$in})" ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( $rows as $row ) {
			$wpdb->insert( $t, $row );
		}
	}
	foreach ( $fam as $id ) {
		clean_post_cache( $id );
	}
	wp_cache_flush();
	$d = tp_ps_diff( $snap, tp_ps_take( $fam ) );
	echo $d ? "برگرداندن ناقص:\n" . implode( "\n", $d ) . "\n" : "برگردانده شد — بی فرق با عکس\n";
	exit( $d ? 1 : 0 );
}

fwrite( STDERR, "حالت: pick | season | save | check | restore\n" );
exit( 2 );
