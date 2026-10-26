<?php
/**
 * تصویرهای گم‌شده — فقط می‌خواند، هیچ چیز پاک یا نوشته نمی‌شود (دستهٔ ۳۰ بند ۱).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-media-audit.php [md]
 *
 * هر ارجاع رسانه در پست‌های ما (همهٔ نوع‌های tp_ و tpc_ و برگه، هر وضعیت جز زباله): hero_image، فیلدهای media/gallery
 * متاباکس‌های جت‌انجین، referenced_media_ids، تصویر شاخص (_thumbnail_id)، و تصویرهای متن (wp-image-ID و src زیر uploads).
 * برای هر پیوست: پروندهٔ اصلی و هر اندازهٔ ساخته‌شده (متادیتای پیوست، از جمله webp) روی دیسک هست؟ ارجاع به پیوستِ ناموجود هم.
 * خروجی: پست · نوع · فیلد · شناسهٔ پیوست · پرونده · وضع (attachment-missing · file-missing · size-missing · url-missing).
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }

$md    = in_array( 'md', (array) ( $args ?? array() ), true );
$up    = wp_get_upload_dir();
$base  = trailingslashit( $up['basedir'] );
$baseu = trailingslashit( $up['baseurl'] );
$rows  = array();
$seen  = array();

/* فیلدهای رسانه از متاباکس‌ها */
$media_fields = array( 'hero_image' => 'media', 'featured_image_gallery' => 'gallery', 'referenced_media_ids' => 'gallery' );
foreach ( (array) get_option( 'jet_engine_meta_boxes', array() ) as $box ) {
	foreach ( (array) ( $box['meta_fields'] ?? array() ) as $f ) {
		if ( in_array( $f['type'] ?? '', array( 'media', 'gallery' ), true ) ) { $media_fields[ $f['name'] ] = $f['type']; }
	}
}

$ids_of = function ( $v ) {
	if ( is_array( $v ) ) {
		$out = array();
		array_walk_recursive( $v, function ( $x, $k ) use ( &$out ) { if ( is_numeric( $x ) && ( 'id' === $k || is_int( $k ) ) ) { $out[] = (int) $x; } } );
		return $out;
	}
	return array_map( 'intval', array_filter( preg_split( '/[\s,]+/', (string) $v ), 'is_numeric' ) );
};

$check = function ( $pid, $field, $aid ) use ( &$rows, &$seen, $base ) {
	$key = $pid . '|' . $field . '|' . $aid;
	if ( isset( $seen[ $key ] ) || $aid <= 0 ) { return; }
	$seen[ $key ] = 1;
	if ( 'attachment' !== get_post_type( $aid ) ) {
		$rows[] = array( $pid, get_post_type( $pid ), $field, $aid, '', 'attachment-missing' );
		return;
	}
	$file = (string) get_attached_file( $aid );
	if ( '' === $file || ! file_exists( $file ) ) {
		$rows[] = array( $pid, get_post_type( $pid ), $field, $aid, str_replace( $base, '', $file ), 'file-missing' );
	}
	$meta = wp_get_attachment_metadata( $aid );
	$dir  = dirname( $file );
	foreach ( (array) ( $meta['sizes'] ?? array() ) as $size => $s ) {
		foreach ( array_merge( array( $s['file'] ?? '' ), array_column( (array) ( $s['sources'] ?? array() ), 'file' ) ) as $sf ) {
			if ( '' !== $sf && ! file_exists( $dir . '/' . $sf ) ) {
				$rows[] = array( $pid, get_post_type( $pid ), $field . ' (' . $size . ')', $aid, str_replace( $base, '', $dir . '/' . $sf ), 'size-missing' );
			}
		}
	}
	foreach ( (array) ( $meta['sources'] ?? array() ) as $mime => $src ) {   /* نسخه‌های webp/avif پروندهٔ اصلی */
		if ( ! empty( $src['file'] ) && ! file_exists( $dir . '/' . $src['file'] ) ) {
			$rows[] = array( $pid, get_post_type( $pid ), $field . ' (' . $mime . ')', $aid, str_replace( $base, '', $dir . '/' . $src['file'] ), 'size-missing' );
		}
	}
};

$types = array_values( array_filter( get_post_types(), function ( $t ) { return 0 === strpos( $t, 'tp_' ) || 0 === strpos( $t, 'tpc_' ) || 'page' === $t || 'post' === $t; } ) );
$posts = get_posts( array( 'post_type' => $types, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'numberposts' => -1, 'suppress_filters' => true, 'fields' => 'ids' ) );
foreach ( $posts as $pid ) {
	foreach ( $media_fields as $name => $kind ) {
		foreach ( $ids_of( get_post_meta( $pid, $name, true ) ) as $aid ) { $check( $pid, $name, $aid ); }
	}
	$thumb = (int) get_post_thumbnail_id( $pid );
	if ( $thumb ) { $check( $pid, '_thumbnail_id', $thumb ); }
	$content = (string) get_post_field( 'post_content', $pid );
	if ( preg_match_all( '/wp-image-(\d+)/', $content, $m ) ) { foreach ( $m[1] as $aid ) { $check( $pid, 'post_content', (int) $aid ); } }
	if ( preg_match_all( '#(?:' . preg_quote( $baseu, '#' ) . '|/assets/uploads/)([^"\'\s?)]+\.(?:jpe?g|png|webp|gif|avif|svg))#i', $content, $m ) ) {
		foreach ( array_unique( $m[1] ) as $rel ) {
			if ( ! file_exists( $base . $rel ) ) { $rows[] = array( $pid, get_post_type( $pid ), 'post_content (url)', 0, $rel, 'url-missing' ); }
		}
	}
}

/* همهٔ پیوست‌ها — ارجاع بیرون از پست‌های ما (سربرگ و مگامنوی المنتور، ابزارک‌ها، …) هم: «ارجاع» = پست‌هایی که شناسه یا نام پرونده در متا یا متنشان آمده */
global $wpdb;
foreach ( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => -1, 'fields' => 'ids' ) ) as $aid ) {
	$before = count( $rows );
	$check( 0, 'attachment', $aid );
	if ( count( $rows ) === $before ) { continue; }
	$name  = pathinfo( (string) get_attached_file( $aid ), PATHINFO_FILENAME );
	$users = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE %s LIMIT 5", '%' . $wpdb->esc_like( $name ) . '%' ) );
	$users = array_merge( $users, $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment','revision') AND post_content LIKE %s LIMIT 5", '%' . $wpdb->esc_like( $name ) . '%' ) ) );
	$users = array_values( array_unique( array_filter( array_map( 'intval', $users ), function ( $u ) use ( $aid ) { return $u !== $aid; } ) ) );
	for ( $i = $before; $i < count( $rows ); $i++ ) {
		$rows[ $i ][0] = $users ? implode( ',', array_slice( $users, 0, 5 ) ) : '(بی ارجاع)';
		$rows[ $i ][1] = $users ? implode( ',', array_unique( array_map( 'get_post_type', array_slice( $users, 0, 5 ) ) ) ) : '';
		$rows[ $i ][2] = 'پیوست: ' . $rows[ $i ][2];
	}
}
/* تکراری‌ها (همان پرونده از راه پست و از راه پیوست) یک بار */
$uniq = array();
foreach ( $rows as $r ) { $uniq[ $r[4] . '|' . $r[5] ][] = $r; }
$rows = array_map( function ( $g ) { $r = $g[0]; $ids = array(); foreach ( array_column( $g, 0 ) as $c ) { foreach ( explode( ',', (string) $c ) as $x ) { if ( is_numeric( $x ) && (int) $x > 0 && 'attachment' !== get_post_type( (int) $x ) ) { $ids[ (int) $x ] = 1; } } } $r[0] = $ids ? implode( ',', array_slice( array_keys( $ids ), 0, 6 ) ) : '(بی ارجاع)'; $r[1] = implode( ',', array_unique( array_map( 'get_post_type', array_slice( array_keys( $ids ), 0, 6 ) ) ) ); return $r; }, array_values( $uniq ) );

$by = array();
foreach ( $rows as $r ) { $by[ $r[5] ] = ( $by[ $r[5] ] ?? 0 ) + 1; }
if ( $md ) {
	echo "| پست | نوع | فیلد | پیوست | پرونده | وضع |\n|---|---|---|---|---|---|\n";
	foreach ( $rows as $r ) { echo '| ' . $r[0] . ( is_numeric( $r[0] ) ? ' · ' . esc_html( get_the_title( (int) $r[0] ) ) : '' ) . ' | `' . $r[1] . '` | ' . $r[2] . ' | ' . ( $r[3] ?: '—' ) . ' | `' . $r[4] . '` | ' . $r[5] . " |\n"; }
	echo "\n";
}
echo count( $posts ) . ' پست · ' . count( $seen ) . ' ارجاع · گم‌شده: ' . wp_json_encode( $by ) . "\n";
