<?php
/**
 * یاور `tests/keys-settings-2i.js` — فقط topersia.local.  P5-D355
 *
 *   php tests/keys-state-2i.php snapshot            عکس خام دو گزینه (کلید Mediana و tpas_settings) با autoload
 *   php tests/keys-state-2i.php restore             عکس را از stdin برمی‌گرداند (مقدار و autoload عیناً)
 *   php tests/keys-state-2i.php probe [needle]      فقط طول، autoload و «needle در گزینه‌ها هست؟» — هرگز مقدار
 */
require_once __DIR__ . '/env.php';
$_SERVER['HTTP_HOST'] = TP_HOST; $_SERVER['SERVER_NAME'] = TP_HOST; $_SERVER['SERVER_PORT'] = 80; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; $_SERVER['REQUEST_METHOD'] = 'GET';
require TP_WP_DIR . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) { fwrite( STDERR, "فقط نسخۀ محلی\n" ); exit( 2 ); }

global $wpdb;
$names = array( 'topersia_mediana_api_key', 'tpas_settings' );
$cmd   = isset( $argv[1] ) ? $argv[1] : '';
$row   = function ( $o ) use ( $wpdb ) { return $wpdb->get_row( $wpdb->prepare( "SELECT option_value v, autoload a FROM {$wpdb->options} WHERE option_name=%s", $o ), ARRAY_A ); };

if ( 'snapshot' === $cmd ) {
	$out = array();
	foreach ( $names as $o ) { $r = $row( $o ); $out[ $o ] = $r ? array( 'v' => base64_encode( $r['v'] ), 'a' => $r['a'] ) : null; }
	echo wp_json_encode( $out );
	exit( 0 );
}
if ( 'restore' === $cmd ) {
	$in = json_decode( (string) stream_get_contents( STDIN ), true );
	foreach ( $names as $o ) {
		if ( ! isset( $in[ $o ] ) ) { $wpdb->delete( $wpdb->options, array( 'option_name' => $o ) ); continue; }
		$exists = $row( $o );
		if ( $exists ) { $wpdb->update( $wpdb->options, array( 'option_value' => base64_decode( $in[ $o ]['v'] ), 'autoload' => $in[ $o ]['a'] ), array( 'option_name' => $o ) ); }
		else { $wpdb->insert( $wpdb->options, array( 'option_name' => $o, 'option_value' => base64_decode( $in[ $o ]['v'] ), 'autoload' => $in[ $o ]['a'] ) ); }
		wp_cache_delete( $o, 'options' ); wp_cache_delete( 'alloptions', 'options' ); wp_cache_delete( 'notoptions', 'options' );
	}
	echo wp_json_encode( array( 'restored' => true ) );
	exit( 0 );
}
if ( 'probe' === $cmd ) {
	$needle = isset( $argv[2] ) ? $argv[2] : '';
	$out    = array();
	foreach ( $names as $o ) { $r = $row( $o ); $out[ $o ] = $r ? array( 'len' => strlen( $r['v'] ), 'autoload' => $r['a'], 'has' => '' !== $needle && false !== strpos( $r['v'], $needle ) ) : null; }
	echo wp_json_encode( $out );
	exit( 0 );
}
fwrite( STDERR, "فرمان نامعتبر\n" );
exit( 2 );
