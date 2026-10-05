<?php
/**
 * زیپ نصب یک افزونه — پایان فاز ۲ج.
 *
 *   php scripts/tp-zip.php topersia-render topersia-core
 *
 * dist/<slug>-<نسخۀ سربرگ>.zip با پوشۀ ریشۀ <slug>/ و همۀ پرونده‌های
 * plugin/<slug> (پوشۀ languages/ و vendor/ هم)، به ترتیب نام، با جداکنندۀ «/».
 * زیپ موجود بازنویسی نمی‌شود — نسخه را بالا ببر. فهرست ورودی‌ها و حجم چاپ می‌شود.
 */

$ws = dirname( __DIR__ );

foreach ( array_slice( $argv, 1 ) as $slug ) {

	$dir  = $ws . '/plugin/' . $slug;
	$main = $dir . '/' . $slug . '.php';

	if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) || ! is_file( $main ) ) {
		fwrite( STDERR, "افزونه پیدا نشد: {$slug}\n" );
		exit( 2 );
	}
	if ( ! preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', (string) file_get_contents( $main ), $m ) ) {
		fwrite( STDERR, "نسخه در سربرگ {$slug} نیست\n" );
		exit( 2 );
	}

	$out = $ws . '/dist/' . $slug . '-' . $m[1] . '.zip';
	if ( file_exists( $out ) ) {
		fwrite( STDERR, "هست: " . basename( $out ) . " — بازنویسی نمی‌شود\n" );
		exit( 2 );
	}

	$files = array();
	$it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $f ) {
		if ( $f->isFile() ) {
			$files[] = str_replace( '\\', '/', substr( $f->getPathname(), strlen( $dir ) + 1 ) );
		}
	}
	sort( $files, SORT_STRING );

	$zip = new ZipArchive();
	if ( true !== $zip->open( $out, ZipArchive::CREATE ) ) {
		fwrite( STDERR, "زیپ ساخته نشد: {$out}\n" );
		exit( 1 );
	}
	foreach ( $files as $rel ) {
		$zip->addFile( $dir . '/' . $rel, $slug . '/' . $rel );
	}
	$zip->close();

	printf( "%s — %d پرونده، %s بایت\n", basename( $out ), count( $files ), number_format( filesize( $out ) ) );
}
