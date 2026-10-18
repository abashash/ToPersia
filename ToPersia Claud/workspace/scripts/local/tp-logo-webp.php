<?php
/**
 * لوگوی سربرگ ← WebP دو برابر اندازهٔ نمایش — دستهٔ ۱۰ ب · P5-D368  (فقط محلی؛ موقت و برگشت‌پذیر)
 *
 *   wp eval-file scripts/local/tp-logo-webp.php apply   [مسیر WebP، پیش‌فرض uploads/2026/09/ToPersia-Logo-2x.webp]
 *   wp eval-file scripts/local/tp-logo-webp.php restore
 *   wp eval-file scripts/local/tp-logo-webp.php status
 *
 * ToPersia-Logo.svg ۷۶۱KB است چون تصویر raster نیست بلکه رَدگیری برداری (۱۳ مسیر با ۳ رقم اعشار) از یک bitmap است.
 * `apply`: WebP را (اگر نیست) پیوست می‌کند، `image` هر دو عنصر لوگوی `whb_default_header` (سربرگ دسکتاپ و موبایل — دو عنصر
 * جدا، هر دو چاپ می‌شوند و CSS یکی را پنهان می‌کند) را با {id, url, 300, 98} عوض می‌کند و گزینهٔ پیشین را در
 * پروندهٔ پشتیبان (بیرون از git) می‌گذارد. پروندهٔ SVG و پیوست ۱۸۹۹ دست‌نخورده می‌مانند. `restore`: همان گزینه را برمی‌گرداند.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$mode   = isset( $args[0] ) ? $args[0] : ( isset( $GLOBALS['argv'][3] ) ? $GLOBALS['argv'][3] : 'status' );
$bk_dir = getenv( 'LOCALAPPDATA' ) . '/topersia-tools/diag-2026-10-18';
$bk     = $bk_dir . '/whb_default_header.backup.json';
$opt    = get_option( 'whb_default_header' );
$logos  = function ( &$o ) { $out = array(); $w = function ( &$a ) use ( &$w, &$out ) { foreach ( $a as $k => &$v ) { if ( is_array( $v ) ) { if ( isset( $v['type'] ) && 'logo' === $v['type'] && isset( $v['params']['image']['value'] ) ) { $out[] = &$v; } $w( $v ); } } }; $w( $o ); return $out; };

if ( 'status' === $mode ) {
	foreach ( $logos( $opt ) as $l ) { $v = $l['params']['image']['value']; echo "logo: id={$v['id']} {$v['width']}x{$v['height']} " . basename( $v['url'] ) . "\n"; }
	echo is_file( $bk ) ? "پشتیبان هست\n" : "پشتیبان نیست\n"; exit( 0 );
}
if ( 'apply' === $mode ) {
	if ( ! is_array( $opt ) ) { fwrite( STDERR, "گزینه آرایه نیست\n" ); exit( 2 ); }
	if ( ! is_dir( $bk_dir ) ) { mkdir( $bk_dir, 0777, true ); }
	if ( ! is_file( $bk ) ) { file_put_contents( $bk, wp_json_encode( $opt ) ); }
	$up   = wp_get_upload_dir();
	$file = $up['basedir'] . '/2026/09/ToPersia-Logo-2x.webp';
	if ( ! is_file( $file ) ) { fwrite( STDERR, "پروندهٔ WebP نیست: $file\n" ); exit( 2 ); }
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$url = $up['baseurl'] . '/2026/09/ToPersia-Logo-2x.webp';
	$id  = (int) attachment_url_to_postid( $url );
	if ( ! $id ) {
		$id = wp_insert_attachment( array( 'post_mime_type' => 'image/webp', 'post_title' => 'ToPersia Logo 2x', 'post_status' => 'inherit' ), $file );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
	}
	$meta = wp_get_attachment_metadata( $id );
	$w = isset( $meta['width'] ) ? (int) $meta['width'] : 300; $h = isset( $meta['height'] ) ? (int) $meta['height'] : 98;
	$n = 0;
	foreach ( $logos( $opt ) as &$l ) { $l['params']['image']['value'] = array( 'id' => $id, 'url' => $url, 'width' => $w, 'height' => $h ); $n++; }
	unset( $l );
	update_option( 'whb_default_header', $opt );
	echo "اعمال شد: $n عنصر لوگو ← پیوست #$id ({$w}x{$h})\n"; exit( 0 );
}
if ( 'restore' === $mode ) {
	if ( ! is_file( $bk ) ) { fwrite( STDERR, "پشتیبان نیست\n" ); exit( 2 ); }
	update_option( 'whb_default_header', json_decode( file_get_contents( $bk ), true ) );
	echo "برگشت\n"; exit( 0 );
}
fwrite( STDERR, "حالت نامعتبر\n" ); exit( 2 );
