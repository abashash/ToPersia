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

/* ── دستهٔ 55 بند ۱ — پرونده‌های اجرای سایت (site.zip) و فهرست محتویات با sha256 هر پرونده ── */

/** در ریشهٔ public_html فقط این‌ها پروندهٔ اجرای سایت‌اند؛ بقیه مخزن git (workspace · .git · .claude)، یادداشت یا لاگ. */
const TPB_SITE_TOP = array( '.htaccess', 'index.php', 'robots.txt', '.well-known', 'geo' );

/** پرونده‌ای از site.zip بیرون می‌ماند (جز رازها، که tpb_site_secret می‌سنجد): uploads (آرشیو جدا)، کش، صف، پشتیبان‌های دیگر، لاگ و دامپ. */
function tpb_site_skip( $rel ) {
	$base = basename( $rel );
	return (bool) preg_match( '#^geo/wp-content/(uploads|cache|upgrade|upgrade-temp-backup|backups-dup-pro|backups-dup-lite|duplicator-backups|abzarwp-backups|languages/wpml/queue)(/|$)#i', $rel )
		|| (bool) preg_match( '#(\.log|\.sql|\.sql\.gz|\.sql\.zip|\.daf|_archive\.zip)$#i', $base );
}

/**
 * رازِ پرونده‌های سایت با نام **و** محتوا (اجرای نخست دستهٔ 55: صافی فقط‌نام دو بستهٔ گواهی عمومی cacert.pem و قالب
 * wp-config-check.php دوپلیکیتور را هم انداخت — بی آن‌ها افزونه پس از بازگردانی ناقص است).
 *   wp-config.php هر جا ← همیشه راز · .env* و id_* و .htpasswd و .key/.p12/.pfx/.ppk ← همیشه راز ·
 *   .pem ← فقط اگر بلوک PRIVATE KEY دارد · wp-config-*.php دیگر ← فقط اگر DB_PASSWORD یا کلیدهای احراز با مقدارِ غیرِ نمونه دارد ·
 *   .gitignore/.gitattributes/.gitkeep ← راز نیست.
 */
function tpb_site_secret( $rel, $abs ) {
	$base = basename( $rel );
	if ( ! tpb_secret_file( $base ) ) { return false; }
	if ( preg_match( '/^\.git(ignore|attributes|keep)$/i', $base ) ) { return false; }
	if ( 0 === strcasecmp( $base, 'wp-config.php' ) ) { return true; }
	$src = is_file( $abs ) && filesize( $abs ) <= ( 4 << 20 ) ? (string) file_get_contents( $abs ) : '';
	if ( preg_match( '/\.pem$/i', $base ) ) { return '' === $src || false !== stripos( $src, 'PRIVATE KEY' ); }
	if ( preg_match( '/^wp-config.*\.php$/i', $base ) ) {
		return '' === $src || (bool) preg_match( "/define\(\s*['\"](?:DB_PASSWORD|AUTH_KEY|SECURE_AUTH_KEY|LOGGED_IN_KEY|NONCE_KEY|AUTH_SALT)['\"]\s*,\s*['\"](?!password_here['\"]|put your unique phrase here['\"])[^'\"]+['\"]/i", $src );
	}
	return true;
}

/**
 * پویش کلید روی پرونده‌های متنی سایت. الگوها همان چهار الگوی git-guard‌اند با دو سخت‌گیری که مقدارِ واقعی را از
 * متنِ کدِ شخص ثالث جدا می‌کند (پیش‌پویش ۱۸ نوامبر): کلید OpenAI حروف بزرگ و کوچک و رقم با هم دارد (نام تصویرِ
 * «c-sk-…-600x686» در دادهٔ نمونهٔ پوسته ندارد) و کلید خصوصی پس از سرآیند بدنهٔ base64 دارد (رشتهٔ سرآیند در phpseclib ندارد).
 */
function tpb_site_key_patterns() {
	return array(
		'کلید Google'     => '/AIza[0-9A-Za-z_-]{35}/',
		'کلید OpenAI'     => '/(?<![A-Za-z0-9_\/-])sk-(?:proj-)?(?=[A-Za-z0-9_-]*[A-Z])(?=[A-Za-z0-9_-]*[a-z])(?=[A-Za-z0-9_-]*[0-9])[A-Za-z0-9_-]{20,}/',
		'مقدار xi-api-key' => '/xi-api-key[\'"]?\s*(?::|=>?|,)\s*[\'"]?[A-Za-z0-9_]{20,}/',
		'کلید خصوصی'      => '/-----BEGIN (?:[A-Z]+ )*PRIVATE KEY-----[\r\n]+[A-Za-z0-9+\/=]{40,}/',
	);
}

/**
 * نقشهٔ site.zip: [ مسیر درون آرشیو ⇒ مسیر روی دیسک ]. ریشهٔ public_html (TPB_SITE_TOP) با مسیر نسبی خودش،
 * و تنظیمات محیط زیر env/ (php.ini · phpForApache.ini · بارگذار SourceGuardian که AbzarWP بی آن بالا نمی‌آید · vhost آپاچی).
 * @return array [ map, skipped ⇒ [مسیر رازدار], missing ⇒ [پروندهٔ محیطِ نبود] ]
 */
function tpb_site_map() {
	$root = rtrim( str_replace( '\\', '/', (string) realpath( TP_ROOT ) ), '/' );
	$map  = array(); $skipped = array(); $missing = array();
	foreach ( TPB_SITE_TOP as $top ) {
		$abs = $root . '/' . $top;
		if ( is_file( $abs ) ) { $map[ $top ] = $abs; continue; }
		if ( ! is_dir( $abs ) ) { continue; }
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $abs, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::LEAVES_ONLY );
		foreach ( $it as $f ) {
			if ( ! $f->isFile() ) { continue; }
			$rel = substr( str_replace( '\\', '/', $f->getPathname() ), strlen( $root ) + 1 );
			if ( tpb_site_skip( $rel ) ) { continue; }
			if ( tpb_site_secret( $rel, $f->getPathname() ) ) { $skipped[] = $rel; continue; }
			$map[ $rel ] = $f->getPathname();
		}
	}
	$php = dirname( TP_PHP );
	$bin = dirname( dirname( $php ) );
	$env = array(
		'env/php/php.ini'              => $php . '/php.ini',
		'env/php/phpForApache.ini'     => $php . '/phpForApache.ini',
		'env/php/ext/ixed.8.3ts.win'   => $php . '/ext/ixed.8.3ts.win',
		'env/apache/httpd-vhosts.conf' => (string) current( (array) glob( $bin . '/apache/*/conf/extra/httpd-vhosts.conf' ) ),
	);
	foreach ( $env as $rel => $abs ) { if ( '' !== $abs && is_file( $abs ) ) { $map[ $rel ] = $abs; } else { $missing[] = $rel; } }
	ksort( $map, SORT_STRING );
	return array( 'map' => $map, 'skipped' => $skipped, 'missing' => $missing );
}

/** فقط نام ثابت‌های wp-config (بی هیچ مقدار) — برای ساختن wp-config تازه هنگام بازگردانی. */
function tpb_wp_config_names() {
	$src = (string) file_get_contents( TP_WP_DIR . '/wp-config.php' );
	preg_match_all( "/define\(\s*'([A-Z0-9_]+)'/", $src, $m );
	return array_values( array_unique( $m[1] ) );
}

/** ناحیهٔ هر پرونده در site.zip برای شمار خلاصهٔ manifest. */
function tpb_site_area( $rel ) {
	if ( 0 === strpos( $rel, 'env/' ) ) { return 'env'; }
	if ( preg_match( '#^geo/wp-content/(plugins|mu-plugins|themes|languages)/#', $rel, $m ) ) { return $m[1]; }
	if ( 0 === strpos( $rel, 'geo/wp-content/' ) ) { return 'wp-content-other'; }
	if ( 0 === strpos( $rel, 'geo/' ) ) { return 'core'; }
	return 'root';
}

/**
 * آرشیو zip از نقشهٔ [ مسیر درون آرشیو ⇒ مسیر روی دیسک ] با فهرست محتویات (sha256 و اندازهٔ هر پرونده).
 * رسانه و پروندهٔ فشرده بی فشرده‌سازی؛ دستگیره‌ها هر ۴۰۰ پرونده بسته می‌شوند (سقف پرونده‌های باز ویندوز).
 * هش پیش از افزودن گرفته می‌شود؛ اگر پرونده‌ای بین هش و نوشتن عوض شود، آزمون بازگردانی (sha256 هر پرونده) آن را می‌گیرد.
 * @return array [ files, bytes, rows ⇒ [ [sha256, bytes, rel] ] ]
 */
function tpb_zip_map( $zp, array $map ) {
	$store = '/\.(jpe?g|png|gif|webp|avif|mp4|webm|mp3|m4a|ogg|zip|gz|pmtiles|woff2?)$/i';
	$zip   = new ZipArchive();
	if ( true !== $zip->open( $zp, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) { tpb_fail( basename( $zp ) . ' ساخته نشد' ); }
	$rows = array(); $bytes = 0; $batch = 0;
	foreach ( $map as $rel => $abs ) {
		$size   = filesize( $abs );
		$rows[] = array( hash_file( 'sha256', $abs ), $size, $rel );
		$zip->addFile( $abs, $rel );
		$zip->setCompressionName( $rel, preg_match( $store, $rel ) ? ZipArchive::CM_STORE : ZipArchive::CM_DEFLATE );
		$bytes += $size; $batch ++;
		if ( $batch >= 400 ) {
			if ( ! $zip->close() || true !== $zip->open( $zp ) ) { tpb_fail( basename( $zp ) . ' نوشته نشد' ); }
			$batch = 0;
		}
	}
	if ( ! $zip->close() ) { tpb_fail( basename( $zp ) . ' بسته نشد' ); }
	$chk = new ZipArchive();
	if ( true !== $chk->open( $zp, ZipArchive::CHECKCONS ) || $chk->numFiles !== count( $map ) ) { tpb_fail( basename( $zp ) . ' ناسازگار است' ); }
	$chk->close();
	return array( 'files' => count( $map ), 'bytes' => $bytes, 'rows' => $rows );
}

/** فهرست محتویات «آرشیو ⇥ sha256 ⇥ بایت ⇥ مسیر» (سطر اول سرآیند). @return array [ آرشیو ⇒ [ مسیر ⇒ [sha256, bytes] ] ] */
function tpb_read_contents( $file ) {
	$out = array();
	if ( ! is_file( $file ) ) { return $out; }
	foreach ( file( $file, FILE_IGNORE_NEW_LINES ) as $i => $line ) {
		if ( 0 === $i || '' === $line ) { continue; }
		$p = explode( "\t", $line, 4 );
		if ( 4 === count( $p ) ) { $out[ $p[0] ][ $p[3] ] = array( $p[1], (int) $p[2] ); }
	}
	return $out;
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

/**
 * حکم پیش‌بررسی Action Scheduler (P5-D649 ب) — تابع خالص. $facts خروجی tp-backup-as.php؛ $window ثانیه‌هایی که پشتیبان طول می‌کشد.
 * ناپایدار = ردیفی که اکنون یا درون پنجره مشمول پاک‌سازی خودکار می‌شود، یا اجرای in-progress گیرکرده (پاک‌ساز آن را failed می‌کند)؛
 * هر کدام اثر انگشت را میان «پیش» و «پس» عوض می‌کند و کد ۴ می‌دهد. pending_due فقط هشدار است (اجرایش به درخواست وب نیاز دارد).
 * بازگشت: [ 'stable' => bool, 'now' => n, 'soon' => n, 'stale' => n, 'pending_due' => n, 'next_in' => ثانیه تا نخستین مشمول‌شدن|null, 'lines' => [..] ]
 */
function tpb_as_verdict( array $facts, $window = 1800 ) {
	$window = max( 0, (int) $window );
	$now    = (int) ( $facts['now'] ?? time() );
	$cut    = $now - (int) ( $facts['retention'] ?? 2678400 );
	$n_now  = 0; $n_soon = 0; $first = null;
	foreach ( (array) ( $facts['purgeable_last_attempt'] ?? array() ) as $ts ) {
		$at = strtotime( $ts . ' UTC' );
		if ( false === $at ) { continue; }
		if ( $at <= $cut ) { $n_now++; continue; }
		if ( $at <= $cut + $window ) { $n_soon++; }
		if ( null === $first || $at < $first ) { $first = $at; }
	}
	$stale = (int) ( $facts['stale_in_progress'] ?? 0 );
	$due   = (int) ( $facts['pending_due'] ?? 0 );
	$lines = array();
	if ( $n_now ) { $lines[] = "{$n_now} ردیف complete/canceled همین حالا مشمول پاک‌سازی خودکار است"; }
	if ( $n_soon ) { $lines[] = "{$n_soon} ردیف دیگر طی " . (int) ( $window / 60 ) . ' دقیقهٔ آینده مشمول می‌شود'; }
	if ( $stale ) { $lines[] = "{$stale} اجرای in-progress بیش از ۵ دقیقه مانده (پاک‌ساز failed می‌کند)"; }
	if ( $due ) { $lines[] = "{$due} کار pending سررسیده (با درخواست وب بعدی اجرا می‌شود)"; }
	return array(
		'stable'      => ! $n_now && ! $n_soon && ! $stale,
		'now'         => $n_now,
		'soon'        => $n_soon,
		'stale'       => $stale,
		'pending_due' => $due,
		'next_in'     => null === $first ? null : max( 0, $first - $cut ),
		'lines'       => $lines,
	);
}

/** پیش‌بررسی زنده: اگر ناپایدار بود تا $wait ثانیه هر ۱۵ ثانیه بازمی‌سنجد؛ هنوز ناپایدار ← false. */
function tpb_as_preflight( $window, $wait, $script ) {
	$deadline = time() + max( 0, (int) $wait );
	$tmp      = sys_get_temp_dir() . '/tpb-as-' . getmypid() . '.json';
	do {
		$facts = tpb_eval_file( $script, $tmp );
		@unlink( $tmp );
		if ( empty( $facts['table'] ) ) { tpb_out( '   جدول Action Scheduler نیست — بی خطر' ); return true; }
		$v = tpb_as_verdict( $facts, $window );
		if ( $v['stable'] ) {
			tpb_out( '   Action Scheduler آرام است' . ( null === $v['next_in'] ? '' : ' — نخستین ردیف مشمول پاک‌سازی ' . (int) ( $v['next_in'] / 60 ) . ' دقیقهٔ دیگر' ) . ( $v['pending_due'] ? ' · ' . $v['lines'][ count( $v['lines'] ) - 1 ] : '' ) );
			return true;
		}
		foreach ( $v['lines'] as $l ) { tpb_out( '   ⚠ ' . $l ); }
		if ( time() >= $deadline ) { return false; }
		tpb_out( '   صبر ۱۵ ثانیه…' );
		sleep( 15 );
	} while ( true );
}
