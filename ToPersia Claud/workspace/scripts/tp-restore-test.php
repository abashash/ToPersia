<?php
/**
 * آزمون بازگردانی بسته — دستهٔ 37 · P5-D475 (LAUNCH.md B-2). فقط topersia.local؛ هرگز روی پایگاه‌دادهٔ اصلی.
 *
 *   php scripts/tp-restore-test.php [نام-بسته] [--dir=C:/wamp64/tp-backups] [--keep-work]
 *
 * بی نام ← تازه‌ترین بسته. سقف زمان کل ۱۰ دقیقه. گام‌ها (هر کدام نتیجهٔ جدا؛ اثر انگشت نخواند = توقف):
 *   ۱ sha256 پرونده‌ها با manifest · ۲ ساخت پایگاهٔ موقت topersia_rt_* و درون‌ریزی db.sql.gz ·
 *   ۳ اثبات اینکه WP-CLI به پایگاهٔ موقت وصل است نه اصلی ·
 *   ۴ اثبات اصلی — اثر انگشت SQL روی پایگاهٔ موقت = manifest: شمار ردیف هر جدول، شمار پست هر نوع، ردیف‌های رابطه،
 *     نمایهٔ جغرافیایی، چکسام تجمیعی posts (و هش post_content) و postmeta، نسخهٔ WPML ·
 *   ۵ search-replace نشانی فقط روی posts · postmeta · options · termmeta (بی --precise؛ جدول‌های لاگ و صف رد) ·
 *   ۶ خواندن چند پست از نسخهٔ بازگردانی‌شده با WP-CLI و مقایسه با اصل ·
 *   ۷ آرشیو uploads را در پوشهٔ موقت باز کن و شمار/حجم را بسنج · ۸ پاک‌کردن (همیشه، در finally).
 * اگر اجرای پیشین قطع شده باشد، موقت‌های بازمانده (topersia_rt_*، _rt-*) در آغاز پاک می‌شوند.
 * هم‌زمان با Lighthouse اجرا نمی‌شود. نتیجه: RESTORE-TEST.json کنار بسته؛ در موفقیت LAST-RESTORED.json.
 */

require_once __DIR__ . '/lib/tp-backup-lib.php';

const RT_LIMIT = 600;

$opt  = tpb_opts( $argv );
$root = tpb_root( $opt );
$db   = tpb_db();
$pre  = $db['prefix'];
$t0   = microtime( true );
$left = function () use ( $t0 ) { return max( 5, RT_LIMIT - (int) ( microtime( true ) - $t0 ) ); };

/* Lighthouse و آزمون‌های سنگین هم‌زمان نه */
$ps = tpb_run( array( 'powershell', '-NoProfile', '-Command', "Get-CimInstance Win32_Process | Where-Object { \$_.CommandLine -match 'lighthouse|chrome-launcher' -and \$_.ProcessId -ne \$PID -and \$_.Name -notmatch 'powershell|php' } | Measure-Object | Select-Object -ExpandProperty Count" ) );
if ( (int) trim( $ps[1] ) > 0 ) { tpb_fail( 'Lighthouse/کروم در جریان است؛ آزمون بازگردانی هم‌زمان با آن اجرا نمی‌شود', 5 ); }

function rt_mysqli( $db ) { return new mysqli( $db['host'], $db['user'], $db['pass'], '', $db['port'] ); }

/* موقت‌های بازمانده از اجرای قطع‌شده */
$m = rt_mysqli( $db );
foreach ( array_column( $m->query( "SHOW DATABASES LIKE 'topersia\\_rt\\_%'" )->fetch_all(), 0 ) as $d ) {
	if ( preg_match( '/^topersia_rt_\d{12}$/', $d ) && $d !== $db['name'] ) { $m->query( "DROP DATABASE `$d`" ); tpb_out( "  · پایگاهٔ موقت بازمانده پاک شد: {$d}" ); }
}
foreach ( (array) glob( $root . '/_rt-*', GLOB_ONLYDIR ) as $d ) { tpb_rrmdir( $d ); tpb_out( '  · پوشهٔ موقت بازمانده پاک شد: ' . basename( $d ) ); }
$m->close();

$pk   = tpb_packages( $root );
$name = isset( $opt['_'][0] ) ? basename( (string) $opt['_'][0] ) : (string) key( $pk );
if ( '' === $name || ! isset( $pk[ $name ] ) ) { tpb_fail( 'بسته پیدا نشد', 2 ); }
$dir = $root . '/' . $name;
$man = json_decode( (string) file_get_contents( $dir . '/manifest.json' ), true );
if ( ! is_array( $man ) ) { tpb_fail( 'manifest خوانده نشد', 2 ); }

$GLOBALS['tpb_fp_scrub'] = json_encode( isset( $man['scrub'] ) ? $man['scrub'] : array(), JSON_UNESCAPED_UNICODE );   /* همان دید بی ردیف‌های رازدار */
$stamp    = date( 'YmdHi' );
$tmpdb    = 'topersia_rt_' . $stamp;
$tmpurl   = 'http://restore-test.topersia.local';
$work     = $root . '/_rt-' . $stamp;
$override = $work . '/override.php';
$steps    = array();
$ok       = true;

if ( $tmpdb === $db['name'] || ! preg_match( '/^topersia_rt_\d{12}$/', $tmpdb ) ) { tpb_fail( 'نام پایگاهٔ موقت نامعتبر', 2 ); }
mkdir( $work, 0777, true );
file_put_contents( $override, "<?php\nif ( ! defined( 'DB_NAME' ) ) { define( 'DB_NAME', '" . $tmpdb . "' ); }\n" );

function rt_step( &$steps, &$ok, $title, $pass, $note = '' ) {
	$steps[] = array( 'step' => $title, 'ok' => (bool) $pass, 'note' => $pass ? '' : $note );
	tpb_out( '  ' . ( $pass ? '✅' : '❌' ) . ' ' . $title . ( $pass || '' === $note ? '' : '   ← ' . $note ) );
	if ( ! $pass ) { $ok = false; }
	return (bool) $pass;
}

/** درون‌ریزی gz در پایگاه؛ گذرواژه فقط از MYSQL_PWD. */
function rt_import( $db, $database, $gzfile, $deadline ) {
	$cmd = array( TP_MYSQL, '--protocol=tcp', '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['user'], '--default-character-set=utf8mb4', '--max-allowed-packet=256M', $database );
	$p   = proc_open( $cmd, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'file', sys_get_temp_dir() . '/tp-rt-err.txt', 'w' ) ), $pipes, null, tpb_env( array( 'MYSQL_PWD' => $db['pass'] ) ) );
	if ( ! is_resource( $p ) ) { return array( 127, 'proc_open' ); }
	$g = gzopen( $gzfile, 'rb' );
	while ( ! gzeof( $g ) ) {
		if ( microtime( true ) > $deadline ) { gzclose( $g ); proc_terminate( $p ); return array( 124, 'مهلت' ); }
		$c = gzread( $g, 1 << 20 );
		if ( false === $c ) { break; }
		fwrite( $pipes[0], $c );
	}
	gzclose( $g );
	fclose( $pipes[0] );
	stream_get_contents( $pipes[1] ); fclose( $pipes[1] );
	$code = proc_close( $p );
	return array( $code, substr( (string) @file_get_contents( sys_get_temp_dir() . '/tp-rt-err.txt' ), 0, 200 ) );
}

function rt_clean_up( $db, $tmpdb, $work, $keep ) {
	if ( preg_match( '/^topersia_rt_\d{12}$/', $tmpdb ) && $tmpdb !== $db['name'] ) {
		$m = new mysqli( $db['host'], $db['user'], $db['pass'], '', $db['port'] );
		$m->query( "DROP DATABASE IF EXISTS `{$tmpdb}`" );
	}
	if ( ! $keep ) { tpb_rrmdir( $work ); }
	@unlink( sys_get_temp_dir() . '/tp-rt-err.txt' );
}

register_shutdown_function( function () use ( $db, $tmpdb, $work, $opt ) { rt_clean_up( $db, $tmpdb, $work, ! empty( $opt['keep-work'] ) ); } );

tpb_out( "آزمون بازگردانی: {$name} → {$tmpdb} ({$tmpurl}) · سقف " . RT_LIMIT . ' ثانیه' );

try {

	/* ۱ — sha256 */
	$bad = array();
	foreach ( $man['files'] as $f => $mf ) {
		if ( ! is_file( $dir . '/' . $f ) || filesize( $dir . '/' . $f ) !== $mf['bytes'] || tpb_sha( $dir . '/' . $f ) !== $mf['sha256'] ) { $bad[] = $f; }
	}
	$go = rt_step( $steps, $ok, '۱ sha256 و اندازهٔ همهٔ پرونده‌ها با manifest', ! $bad, implode( ',', $bad ) );

	/* ۲ — پایگاهٔ موقت و درون‌ریزی */
	if ( $go ) {
		$m = rt_mysqli( $db );
		$okc = $m->query( "DROP DATABASE IF EXISTS `{$tmpdb}`" ) && $m->query( "CREATE DATABASE `{$tmpdb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci" );
		$go = rt_step( $steps, $ok, "۲ ساخت پایگاهٔ موقت {$tmpdb}", $okc, $m->error );
	}
	if ( $go ) {
		list( $c, $e ) = rt_import( $db, $tmpdb, $dir . '/db.sql.gz', microtime( true ) + $left() );
		$tables = (int) $m->query( "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='{$tmpdb}'" )->fetch_row()[0];
		$go = rt_step( $steps, $ok, "۲ درون‌ریزی db.sql.gz ({$tables} جدول از " . $man['database']['tables'] . ')', 0 === $c && $tables === (int) $man['database']['tables'], $e );
	}

	/* ۳ — اتصال به پایگاهٔ موقت، نه اصلی */
	if ( $go ) {
		$r = tpb_run( tpb_wp_cmd( array( 'eval', 'global $wpdb; echo "DBN=" . $wpdb->dbname;' ), $override ), null, null, $left() );
		$go = rt_step( $steps, $ok, '۳ WP-CLI با override به پایگاهٔ موقت وصل است (نه topersia_local)', false !== strpos( $r[1], 'DBN=' . $tmpdb ) && false === strpos( $r[1], 'DBN=' . $db['name'] ), substr( tpb_clean( $r[1] . $r[2] ), 0, 200 ) );
	}

	/* ۴ — اثبات اصلی: اثر انگشت SQL */
	if ( $go ) {
		$fp_now = tpb_eval_file( TP_WORKSPACE . '/tests/lib/db-fingerprint.php', $work . '/fp.json', $override );
		$fx_now = tpb_eval_file( __DIR__ . '/lib/tp-backup-facts.php', $work . '/facts.json', $override );
		$fp_man = json_decode( (string) file_get_contents( $dir . '/fingerprint.json' ), true );
		$diff   = array();
		foreach ( $fp_man as $k => $v ) {
			if ( ! isset( $fp_now[ $k ] ) || $fp_now[ $k ] !== $v ) {
				if ( is_array( $v ) ) { foreach ( $v as $kk => $vv ) { if ( $vv !== ( $fp_now[ $k ][ $kk ] ?? null ) ) { $diff[] = $k . '.' . $kk; } } } else { $diff[] = $k; }
			}
		}
		$go = rt_step( $steps, $ok, '۴ اثر انگشت SQL پایگاه بازگردانی‌شده = manifest (شمار ردیف هر جدول · چکسام posts و postmeta · هش post_content · نمایهٔ جغرافیایی · ردیف‌های رابطه · گزینه‌ها · متای هر پست)', ! $diff, 'ناهمخوان: ' . implode( '،', array_slice( $diff, 0, 12 ) ) );
		if ( $go ) {
			$cmp = array(
				'شمار پست هر نوع'  => $fx_now['posts_by_type'] === $man['posts_by_type'],
				'ردیف‌های رابطه'   => $fx_now['relation_rows'] === $man['relation_rows'],
				'نسخهٔ WPML'      => $fx_now['wpml'] === $man['wpml'],
				'نسخهٔ وردپرس'    => $fx_now['wp_version'] === $man['wordpress'],
				'نمایهٔ جغرافیایی' => ( $fp_now['real']['geo'] ?? -1 ) === ( $fp_man['real']['geo'] ?? -2 ),
			);
			$badc = array_keys( array_filter( $cmp, function ( $x ) { return ! $x; } ) );
			$go = rt_step( $steps, $ok, '۴ شمار پست هر نوع · ردیف‌های رابطه · نسخهٔ WPML و وردپرس · نمایهٔ جغرافیایی = manifest', ! $badc, implode( '،', $badc ) );
		}
	}

	/* ۵ — search-replace فقط روی جدول‌های هسته */
	if ( $go ) {
		/* ویندوز: نام جدول‌ها در پایگاه کوچک‌حرف است و WP-CLI نام را دقیق تطبیق می‌دهد */
		$tabs = array_map( 'strtolower', array( $pre . 'posts', $pre . 'postmeta', $pre . 'options', $pre . 'termmeta' ) );
		$old  = $man['home'];
		$rows_before = $fp_now['count'];
		$r = tpb_run( tpb_wp_cmd( array_merge( array( 'search-replace', $old, $tmpurl ), $tabs, array( '--skip-columns=guid', '--report=false' ) ), $override ), null, null, $left() );
		$chk = tpb_run( tpb_wp_cmd( array_merge( array( 'search-replace', $old, $tmpurl ), $tabs, array( '--skip-columns=guid', '--dry-run', '--format=count' ) ), $override ), null, null, $left() );
		$left_n = (int) trim( tpb_clean( $chk[1] ) );
		$h = trim( tpb_clean( tpb_run( tpb_wp_cmd( array( 'option', 'get', 'home' ), $override ), null, null, $left() )[1] ) );
		$s = trim( tpb_clean( tpb_run( tpb_wp_cmd( array( 'option', 'get', 'siteurl' ), $override ), null, null, $left() )[1] ) );
		$fp2 = tpb_eval_file( TP_WORKSPACE . '/tests/lib/db-fingerprint.php', $work . '/fp2.json', $override );
		$go = rt_step( $steps, $ok, '۵ search-replace (posts · postmeta · options · termmeta): نشانی قدیم در آن‌ها نمانده؛ home و siteurl موقت‌اند؛ شمار ردیف هیچ جدولی عوض نشد',
			0 === $r[0] && 0 === $left_n && 0 === strpos( $h, $tmpurl ) && 0 === strpos( $s, $tmpurl ) && $fp2['count'] === $rows_before, "exit={$r[0]} left={$left_n} home={$h} " . substr( tpb_clean( $r[1] . $r[2] ), 0, 200 ) );
	}

	/* ۶ — خواندن چند پست با WP-CLI از نسخهٔ بازگردانی‌شده، در برابر اصل */
	if ( $go ) {
		$mm  = rt_mysqli( $db );
		$ids = array_column( $mm->query( "SELECT ID FROM `{$db['name']}`.`{$pre}posts` WHERE post_type IN ('tp_province','tp_city','tp_country') AND post_status='publish' ORDER BY ID LIMIT 3" )->fetch_all(), 0 );
		$mm->close();
		$same = ! empty( $ids ); $shown = array();
		foreach ( $ids as $id ) {
			$get = function ( $ovr ) use ( $id, $left ) {
				$t = tpb_clean( tpb_run( tpb_wp_cmd( array( 'post', 'get', (string) $id, '--field=post_title' ), $ovr ), null, null, $left() )[1] );
				$c = tpb_clean( tpb_run( tpb_wp_cmd( array( 'post', 'meta', 'list', (string) $id, '--format=count' ), $ovr ), null, null, $left() )[1] );
				return $t . '|' . $c;
			};
			$a = $get( '' ); $b = $get( $override );
			if ( $a !== $b || '|' === substr( $a, 0, 1 ) ) { $same = false; }
			$shown[] = '#' . $id . ' «' . explode( '|', $b )[0] . '» (' . explode( '|', $b )[1] . ' متا)';
		}
		$go = rt_step( $steps, $ok, '۶ WP-CLI: ' . count( $ids ) . ' پست از نسخهٔ بازگردانی‌شده خوانده شد؛ عنوان و شمار متا = اصل', $same, implode( ' · ', $shown ) );
		if ( $same ) { tpb_out( '      ' . implode( ' · ', $shown ) ); }
	}

	/* ۷ — uploads */
	if ( $go ) {
		$z   = new ZipArchive();
		$okz = true === $z->open( $dir . '/uploads.zip', ZipArchive::CHECKCONS ) && $z->numFiles === $man['uploads']['files'];
		$cnt = 0; $bytes = 0;
		if ( $okz ) {
			$x = $work . '/uploads';
			mkdir( $x );
			$okz = $z->extractTo( $x );
			$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $x, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $f ) { if ( $f->isFile() ) { $cnt ++; $bytes += $f->getSize(); } }
			$z->close();
		}
		$go = rt_step( $steps, $ok, '۷ uploads.zip باز می‌شود؛ شمار پرونده و حجم = manifest', $okz && $cnt === $man['uploads']['files'] && $bytes === $man['uploads']['bytes'], "files={$cnt} bytes={$bytes}" );
	}

	if ( microtime( true ) - $t0 > RT_LIMIT ) { rt_step( $steps, $ok, '۸ سقف زمان ۱۰ دقیقه', false, 'از مهلت گذشت' ); }

} finally {
	rt_clean_up( $db, $tmpdb, $work, ! empty( $opt['keep-work'] ) );
}

$res = array( 'package' => $name, 'when' => date( 'c' ), 'temp_db' => $tmpdb, 'temp_url' => $tmpurl, 'ok' => $ok, 'seconds' => round( microtime( true ) - $t0 ), 'steps' => $steps, 'cleaned' => true );
file_put_contents( $dir . '/RESTORE-TEST.json', json_encode( $res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
if ( $ok ) { file_put_contents( $root . '/LAST-RESTORED.json', json_encode( array( 'package' => $name, 'when' => date( 'c' ) ), JSON_PRETTY_PRINT ) ); }
tpb_out( ( $ok ? '✓ بازگردانی و تطبیق موفق' : '✗ بازگردانی ناموفق — ادامه داده نشد' ) . sprintf( ' (%d ثانیه)؛ پایگاه و پوشهٔ موقت پاک شد', $res['seconds'] ) );
exit( $ok ? 0 : 1 );
