<?php
/**
 * یاور `tests/services-admin.js` — فقط topersia.local.  ۲ج٫۱۱
 *
 *   php tests/services-admin-state.php get            زبان پیشخوان مدیر آزمون (متای locale)
 *   php tests/services-admin-state.php set <locale>   fa_IR · en_US · "" (پیش‌فرض سایت)
 *
 * فقط مدیر آزمونی که نشان `_tp_test_account` دارد (tp_test_admin) دست می‌خورد؛
 * آزمون مقدار پیشین را می‌گیرد و در پایان برمی‌گرداند.
 */

require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

$user = get_user_by( 'login', 'tp_test_admin' );

if ( ! $user || '1' !== (string) get_user_meta( $user->ID, '_tp_test_account', true ) ) {
	fwrite( STDERR, "مدیر آزمون پیدا نشد\n" );
	exit( 2 );
}

$cmd = isset( $argv[1] ) ? $argv[1] : '';

if ( 'get' === $cmd ) {
	echo wp_json_encode( array( 'locale' => (string) get_user_meta( $user->ID, 'locale', true ) ) );
	exit( 0 );
}

if ( 'set' === $cmd ) {
	$locale = isset( $argv[2] ) ? (string) $argv[2] : '';
	if ( ! in_array( $locale, array( 'fa_IR', 'en_US', '' ), true ) ) {
		fwrite( STDERR, "زبان نامعتبر\n" );
		exit( 2 );
	}
	update_user_meta( $user->ID, 'locale', $locale );
	echo wp_json_encode( array( 'locale' => $locale ) );
	exit( 0 );
}

/* عکس خام گزینه‌ها پیش از «ذخیره»ی آزمون، و برگرداندنش پس از آن */
if ( 'snapshot' === $cmd ) {
	echo wp_json_encode( array(
		'services'   => base64_encode( serialize( get_option( TOPERSIA_SERVICES_OPTION, null ) ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		'commercial' => base64_encode( serialize( get_option( TOPERSIA_COMMERCIAL_OPTION, null ) ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	) );
	exit( 0 );
}

if ( 'restore' === $cmd ) {
	$snap = json_decode( (string) file_get_contents( 'php://stdin' ), true );
	foreach ( array( 'services' => TOPERSIA_SERVICES_OPTION, 'commercial' => TOPERSIA_COMMERCIAL_OPTION ) as $k => $opt ) {
		$v = unserialize( base64_decode( (string) $snap[ $k ] ), array( 'allowed_classes' => false ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		if ( null === $v ) {
			delete_option( $opt );
		} else {
			update_option( $opt, $v, 'services' === $k ? false : true );
		}
	}
	topersia_api_flush();
	echo wp_json_encode( array( 'restored' => true ) );
	exit( 0 );
}

/* پیکربندی مؤثر هر سرویس و شکاف دومش — همان که پیشخوان نشان می‌دهد ($exact) */
if ( 'effective' === $cmd ) {
	topersia_api_flush();
	$out = array();
	foreach ( array_keys( topersia_services_catalog() ) as $s ) {
		foreach ( array( $s, $s . TOPERSIA_SERVICES_ALT ) as $id ) {
			$c          = topersia_api( $id, false, true );
			$out[ $id ] = array( $c['provider'], $c['enabled'], $c['endpoint'], $c['model'], $c['version'], $c['ttl'], $c['limits'], $c['extra'], $c['key_tail'], $c['key_source'] );
		}
	}
	echo wp_json_encode( $out );
	exit( 0 );
}

fwrite( STDERR, "دستور: get | set <locale> | snapshot | restore (stdin) | effective\n" );
exit( 2 );
