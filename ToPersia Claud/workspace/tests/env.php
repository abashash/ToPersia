<?php
/**
 * محیط محلی از یک فایل — P5-D285 (فاز ۰ب٫۱).
 *
 *   require_once __DIR__ . '/env.php';                    // در tests/
 *   require_once dirname( __DIR__ ) . '/tests/env.php';   // در scripts/
 *
 * ثابت‌ها: TP_ROOT (هم‌ارز public_html) · TP_WP_DIR (وردپرس، `geo/`) · TP_BASE_URL ·
 * TP_HOST · TP_WORKSPACE · TP_PHP · TP_MYSQL · TP_WP_CLI. همه از `tests/env.json`؛
 * مسیر ثابت در هیچ آزمون یا اسکریپتی نوشته نمی‌شود. متغیر محیطی `TP_ENV` فایل
 * دیگری را جایگزین می‌کند (مثلاً ماشین دیگر).
 */

if ( ! defined( 'TP_ROOT' ) ) {

	$tp_env_file = getenv( 'TP_ENV' ) ? (string) getenv( 'TP_ENV' ) : __DIR__ . '/env.json';
	$tp_env      = json_decode( (string) @file_get_contents( $tp_env_file ), true );

	if ( ! is_array( $tp_env ) || empty( $tp_env['wp'] ) || empty( $tp_env['base'] ) ) {
		fwrite( STDERR, "env.json خوانده نشد: {$tp_env_file}\n" );
		exit( 2 );
	}

	define( 'TP_ROOT', rtrim( (string) $tp_env['root'], '/\\' ) );
	define( 'TP_WP_DIR', rtrim( (string) $tp_env['wp'], '/\\' ) );
	define( 'TP_BASE_URL', rtrim( (string) $tp_env['base'], '/' ) );
	define( 'TP_HOST', (string) parse_url( TP_BASE_URL, PHP_URL_HOST ) );
	define( 'TP_WORKSPACE', rtrim( (string) $tp_env['workspace'], '/\\' ) );
	define( 'TP_PHP', (string) $tp_env['php'] );
	define( 'TP_MYSQL', isset( $tp_env['mysql'] ) ? (string) $tp_env['mysql'] : '' );
	define( 'TP_WP_CLI', isset( $tp_env['wp_cli'] ) ? (string) $tp_env['wp_cli'] : '' );

	unset( $tp_env_file, $tp_env );
}
