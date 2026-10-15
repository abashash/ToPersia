<?php
/**
 * ردهٔ فراخوانی هشدار «strstr(): Passing null …» از functions.php هستهٔ وردپرس — فقط گزارش، بی هیچ تغییر  (فقط محلی)
 *
 *   php scripts/local/tp-find-deprecated.php [مسیر، پیش‌فرض /province/isfahan/]
 *
 * یک بار صفحه را مثل درخواست واقعی بار می‌کند (wp-blog-header) و پیش از وردپرس یک error handler می‌گذارد که فقط
 * هشدار «Passing null» از functions.php را می‌گیرد و ردهٔ فراخوانی (فایل:خط تابع) را چاپ می‌کند؛ بدنهٔ صفحه دور ریخته می‌شود. چیزی نوشته نمی‌شود.
 */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }

$env  = json_decode( (string) file_get_contents( dirname( __DIR__, 2 ) . '/tests/env.json' ), true );
$path = isset( $argv[1] ) ? $argv[1] : '/province/isfahan/';
$_SERVER['HTTP_HOST'] = 'topersia.local'; $_SERVER['SERVER_NAME'] = 'topersia.local'; $_SERVER['SERVER_PORT'] = 80; $_SERVER['REQUEST_URI'] = $path; $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; $_SERVER['REQUEST_METHOD'] = 'GET';
parse_str( (string) parse_url( $path, PHP_URL_QUERY ), $_GET ); $_REQUEST = $_GET; $_SERVER['QUERY_STRING'] = (string) parse_url( $path, PHP_URL_QUERY );
define( 'WP_USE_THEMES', true );

$hits = array();
set_error_handler( function ( $no, $str, $file, $line ) use ( &$hits ) {
	if ( false === strpos( $str, 'Passing null to parameter' ) || false === strpos( str_replace( '\\', '/', $file ), 'wp-includes/functions.php' ) ) { return false; }
	$trace = array();
	foreach ( array_slice( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 14 ), 1 ) as $f ) {
		$trace[] = ( isset( $f['file'] ) ? str_replace( array( '\\', 'C:/wamp64/www/topersia/' ), array( '/', '' ), $f['file'] ) : '?' ) . ':' . ( isset( $f['line'] ) ? $f['line'] : '?' ) . ' ' . ( isset( $f['class'] ) ? $f['class'] . '::' : '' ) . $f['function'];
	}
	$hits[] = $trace;
	return true;
}, E_DEPRECATED );

register_shutdown_function( function () use ( &$hits, $path ) {
	while ( ob_get_level() ) { ob_end_clean(); }
	echo "
مسیر " . $path . ' — هشدار «Passing null» از functions.php: ' . count( $hits ) . " بار
";
	$seen = array();
	foreach ( $hits as $t ) {
		$k = md5( json_encode( array_slice( $t, 0, 8 ) ) );
		if ( isset( $seen[ $k ] ) ) { $seen[ $k ]++; continue; }
		$seen[ $k ] = 1;
		echo "── ردهٔ یکتا (نخستین بار)
  " . implode( "
  ", $t ) . "
";
	}
	echo 'ردهٔ یکتا: ' . count( $seen ) . ' · شمار هر کدام: ' . implode( ',', $seen ) . "
";
} );

ob_start();
require $env['wp'] . '/wp-blog-header.php';
