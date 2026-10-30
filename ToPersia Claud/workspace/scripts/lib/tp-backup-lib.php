<?php
/**
 * کتابخانهٔ مشترک پشتیبان و آزمون بازگردانی — دستهٔ 37 · P5-D475.
 *
 * فقط topersia.local. کلید یا رمزی چاپ یا نوشته نمی‌شود: گذرواژهٔ پایگاه‌داده تنها در حافظه و فقط
 * به‌صورت متغیر محیطی MYSQL_PWD به کلاینت می‌رسد.
 */

require_once dirname( __DIR__, 2 ) . '/tests/env.php';

const TPB_DEFAULT_ROOT = 'C:/wamp64/tp-backups';
const TPB_PKG_RE       = '/^full-(\d{8})-(\d{4})$/';

function tpb_out( $s ) { echo $s . "\n"; }

function tpb_fail( $msg, $code = 1 ) {
	fwrite( STDERR, "✗ {$msg}\n" );
	exit( $code );
}

/** پوشهٔ بسته‌ها: باید بیرون از مخزن git باشد. */
function tpb_root( array $opt ) {
	$root = isset( $opt['dir'] ) ? (string) $opt['dir'] : TPB_DEFAULT_ROOT;
	$root = rtrim( str_replace( '\\', '/', $root ), '/' );
	if ( ! is_dir( $root ) && ! mkdir( $root, 0777, true ) ) {
		tpb_fail( "پوشهٔ مقصد ساخته نشد: {$root}", 2 );
	}
	$real = str_replace( '\\', '/', (string) realpath( $root ) );
	$repo = str_replace( '\\', '/', (string) realpath( TP_ROOT ) );
	if ( '' === $real || 0 === stripos( $real . '/', $repo . '/' ) ) {
		tpb_fail( 'مقصد پشتیبان باید بیرون از مخزن git باشد', 2 );
	}
	return $real;
}

function tpb_opts( array $argv ) {
	$o = array();
	foreach ( array_slice( $argv, 1 ) as $a ) {
		if ( preg_match( '/^--([a-z0-9-]+)(?:=(.*))?$/', $a, $m ) ) {
			$o[ $m[1] ] = isset( $m[2] ) ? $m[2] : true;
		} else {
			$o['_'][] = $a;
		}
	}
	return $o;
}

/** مشخصات اتصال از wp-config.php، فقط در حافظه. */
function tpb_db() {
	static $db = null;
	if ( null !== $db ) { return $db; }
	$src = (string) file_get_contents( TP_WP_DIR . '/wp-config.php' );
	$get = function ( $k ) use ( $src ) {
		return preg_match( "/define\(\s*'" . $k . "'\s*,\s*'((?:[^'\\\\]|\\\\.)*)'/", $src, $m ) ? stripcslashes( $m[1] ) : '';
	};
	$host = $get( 'DB_HOST' );
	$port = 3306;
	if ( false !== strpos( $host, ':' ) ) { list( $host, $port ) = explode( ':', $host, 2 ); }
	$pre  = preg_match( '/\$table_prefix\s*=\s*[\'"]([^\'"]+)[\'"]/', $src, $pm ) ? $pm[1] : 'wp_';
	$db = array( 'prefix' => $pre, 'name' => $get( 'DB_NAME' ), 'user' => $get( 'DB_USER' ), 'pass' => $get( 'DB_PASSWORD' ), 'host' => $host, 'port' => (int) $port );
	if ( '' === $db['name'] || '' === $db['user'] ) { tpb_fail( 'wp-config.php خوانده نشد', 2 ); }
	if ( 'topersia_local' !== $db['name'] || ! in_array( $db['host'], array( '127.0.0.1', 'localhost' ), true ) ) {
		tpb_fail( 'فقط پایگاه‌دادهٔ محلی topersia_local', 2 );
	}
	return $db;
}

function tpb_env( array $extra = array() ) {
	$env  = getenv();
	$bin  = dirname( TP_MYSQL );
	$path = isset( $env['Path'] ) ? 'Path' : 'PATH';
	$env[ $path ] = $bin . PATH_SEPARATOR . ( isset( $env[ $path ] ) ? $env[ $path ] : '' );
	if ( ! empty( $GLOBALS['tpb_fp_scrub'] ) ) { $extra['TP_FP_SCRUB'] = $GLOBALS['tpb_fp_scrub']; }   /* اثر انگشت همان دید بی ردیف‌های رازدار */
	return $extra + $env;
}

/** WP-CLI روی وردپرس محلی. $override ← پروندهٔ define(DB_NAME) برای پایگاهِ موقت. */
function tpb_wp_cmd( array $args, $override = '', $plugins = false ) {
	$cmd = array( TP_PHP, TP_WP_CLI, '--path=' . TP_WP_DIR, '--url=' . TP_BASE_URL );
	if ( ! $plugins ) { $cmd[] = '--skip-plugins'; $cmd[] = '--skip-themes'; }
	if ( '' !== $override ) { $cmd[] = '--require=' . $override; }
	return array_merge( $cmd, $args );
}

/**
 * اجرا و گرفتن خروجی. خروجی در پروندهٔ موقت می‌رود (نه لوله) تا هم بن‌بست نشود هم مهلت اعمال شود.
 * $timeout ثانیه (۰ = بی مهلت)؛ با گذشتن مهلت کل درخت فرایند کشته و کد ۱۲۴ برمی‌گردد.
 */
function tpb_run( array $cmd, array $env = null, $stdin = null, $timeout = 0 ) {
	$o = tempnam( sys_get_temp_dir(), 'tpbo' ); $e = tempnam( sys_get_temp_dir(), 'tpbe' );
	$p = proc_open( $cmd, array( 0 => array( 'pipe', 'r' ), 1 => array( 'file', $o, 'w' ), 2 => array( 'file', $e, 'w' ) ), $pipes, null, $env ?: tpb_env() );
	if ( ! is_resource( $p ) ) { return array( 127, '', 'proc_open failed' ); }
	if ( null !== $stdin ) { fwrite( $pipes[0], $stdin ); }
	fclose( $pipes[0] );
	$dead = $timeout > 0 ? microtime( true ) + $timeout : 0;
	$code = null;
	while ( true ) {
		$st = proc_get_status( $p );
		if ( ! $st['running'] ) { $code = $st['exitcode']; break; }
		if ( $dead && microtime( true ) > $dead ) { exec( 'taskkill /F /T /PID ' . (int) $st['pid'] . ' >NUL 2>&1' ); proc_terminate( $p ); $code = 124; break; }
		usleep( 150000 );
	}
	$rc = proc_close( $p );
	if ( null === $code || -1 === $code ) { $code = $rc; }
	$out = (string) file_get_contents( $o ); $err = (string) file_get_contents( $e );
	@unlink( $o ); @unlink( $e );
	return array( $code, $out, $err );
}

/** خط‌های هشدار ثابت‌تعریف‌شدهٔ override را از خروجی بر می‌دارد. */
function tpb_clean( $s ) {
	return trim( preg_replace( '/^Warning: Constant [A-Z_]+ already defined.*$/m', '', (string) $s ) );
}

/** eval-file با پروندهٔ خروجی JSON. */
function tpb_eval_file( $script, $jsonOut, $override = '' ) {
	@unlink( $jsonOut );
	$r = tpb_run( tpb_wp_cmd( array( 'eval-file', $script, $jsonOut ), $override ) );
	$j = is_file( $jsonOut ) ? json_decode( (string) file_get_contents( $jsonOut ), true ) : null;
	if ( ! is_array( $j ) ) { tpb_fail( 'eval-file ' . basename( $script ) . " ناموفق: " . substr( tpb_clean( $r[1] . $r[2] ), 0, 300 ) ); }
	return $j;
}

function tpb_sha( $file ) { return hash_file( 'sha256', $file ); }

function tpb_rrmdir( $dir ) {
	if ( ! is_dir( $dir ) ) { return; }
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $f ) { $f->isDir() ? @rmdir( $f->getPathname() ) : @unlink( $f->getPathname() ); }
	@rmdir( $dir );
}

/** پروندهٔ مستثنا از آرشیو uploads — هیچ کلید یا رمزی در بسته نمی‌رود. */
function tpb_secret_file( $name ) {
	return (bool) preg_match( '/^(wp-config.*|\.env.*|.*\.(pem|key|p12|pfx|ppk)|id_(rsa|dsa|ecdsa|ed25519).*|\.htpasswd|\.git.*)$/i', $name );
}

/** فهرست بسته‌ها به‌ترتیب جدید ← قدیم: [ نام ⇒ زمان ]. */
function tpb_packages( $root ) {
	$out = array();
	foreach ( (array) glob( $root . '/full-*', GLOB_ONLYDIR ) as $d ) {
		$n = basename( $d );
		if ( preg_match( TPB_PKG_RE, $n, $m ) && is_file( $d . '/manifest.json' ) ) {
			$out[ $n ] = strtotime( substr( $m[1], 0, 4 ) . '-' . substr( $m[1], 4, 2 ) . '-' . substr( $m[1], 6, 2 ) . ' ' . substr( $m[2], 0, 2 ) . ':' . substr( $m[2], 2, 2 ) );
		}
	}
	arsort( $out );
	return $out;
}

/**
 * نگه‌داری: ۷ روزانه (تازه‌ترین بستهٔ هر روز، هفت روز تقویمیِ دارای بسته) + ۴ هفتگی (تازه‌ترین بستهٔ هر هفتهٔ ISO،
 * چهار هفتهٔ دارای بسته). آخرین بستهٔ بازگردانی‌شدهٔ موفق و تازه‌ترین بسته هرگز پاک نمی‌شود.
 * @return array [ keep ⇒ دلیل, drop ⇒ [نام] ]
 */
function tpb_retention( $root, $daily = 7, $weekly = 4 ) {
	$pk   = tpb_packages( $root );
	$keep = array();
	$days = array();
	$wks  = array();
	foreach ( $pk as $n => $t ) {
		$d = date( 'Y-m-d', $t );
		$w = date( 'o-W', $t );
		if ( ! isset( $days[ $d ] ) && count( $days ) < $daily ) { $days[ $d ] = $n; $keep[ $n ][] = 'روزانه'; }
		if ( ! isset( $wks[ $w ] ) && count( $wks ) < $weekly ) { $wks[ $w ] = $n; $keep[ $n ][] = 'هفتگی'; }
	}
	$first = key( $pk );
	if ( null !== $first ) { $keep[ $first ][] = 'تازه‌ترین'; }
	$lr = @json_decode( (string) @file_get_contents( $root . '/LAST-RESTORED.json' ), true );
	if ( is_array( $lr ) && isset( $lr['package'], $pk[ $lr['package'] ] ) ) { $keep[ $lr['package'] ][] = 'آخرین بازگردانی موفق'; }
	return array( 'keep' => $keep, 'drop' => array_values( array_diff( array_keys( $pk ), array_keys( $keep ) ) ) );
}
