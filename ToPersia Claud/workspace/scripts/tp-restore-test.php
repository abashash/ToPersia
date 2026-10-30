<?php
/**
 * آزمون بازگردانی بسته — دستهٔ 37 · P5-D475 (LAUNCH.md B-2). فقط topersia.local؛ هرگز روی پایگاه‌دادهٔ اصلی.
 *
 *   php scripts/tp-restore-test.php [نام-بسته] [--dir=C:/wamp64/tp-backups] [--keep]
 *
 * بی نام ← تازه‌ترین بسته. گام‌ها (هر کدام نتیجهٔ جدا؛ افتادن اثر انگشت = توقف):
 *   ۱ sha256 پرونده‌ها با manifest · ۲ ساخت پایگاهٔ موقت topersia_rt_* و درون‌ریزی db.sql.gz ·
 *   ۳ اثبات اینکه WP-CLI به پایگاهٔ موقت وصل است نه اصلی · ۴ اثر انگشت و واقعیت‌ها در برابر manifest ·
 *   ۵ search-replace نشانی ← نشانی موقت و سنجش · ۶ بوت کامل وردپرس با افزونه‌ها روی پایگاهٔ بازگردانی‌شده ·
 *   ۷ آرشیو uploads را در پوشهٔ موقت باز کن و شمار/حجم را بسنج · ۸ پاک‌کردن موقت‌ها (همیشه).
 * نتیجه: RESTORE-TEST.json کنار بسته؛ در موفقیت LAST-RESTORED.json در ریشهٔ پشتیبان‌ها (نگه‌داری آن را پاک نمی‌کند).
 */

require_once __DIR__ . '/lib/tp-backup-lib.php';

$opt  = tpb_opts( $argv );
$root = tpb_root( $opt );
$db   = tpb_db();

$pk = tpb_packages( $root );
$name = isset( $opt['_'][0] ) ? basename( (string) $opt['_'][0] ) : (string) key( $pk );
if ( '' === $name || ! isset( $pk[ $name ] ) ) { tpb_fail( 'بسته پیدا نشد', 2 ); }
$dir = $root . '/' . $name;
$man = json_decode( (string) file_get_contents( $dir . '/manifest.json' ), true );
if ( ! is_array( $man ) ) { tpb_fail( 'manifest خوانده نشد', 2 ); }

$GLOBALS['tpb_fp_scrub'] = json_encode( isset( $man['scrub'] ) ? $man['scrub'] : array(), JSON_UNESCAPED_UNICODE );   /* همان دید بی ردیف‌های رازدار */
$stamp   = date( 'YmdHi' );
$tmpdb   = 'topersia_rt_' . $stamp;
$tmpurl  = 'http://restore-test.topersia.local';
$work    = $root . '/_rt-' . $stamp;
$override = $work . '/override.php';
$steps   = array();
$ok      = true;
$t0      = microtime( true );

if ( $tmpdb === $db['name'] || ! preg_match( '/^topersia_rt_\d{12}$/', $tmpdb ) ) { tpb_fail( 'نام پایگاهٔ موقت نامعتبر', 2 ); }
mkdir( $work, 0777, true );
file_put_contents( $override, "<?php\nif ( ! defined( 'DB_NAME' ) ) { define( 'DB_NAME', '" . $tmpdb . "' ); }\n" );

function rt_step( &$steps, &$ok, $title, $pass, $note = '' ) {
	$steps[] = array( 'step' => $title, 'ok' => (bool) $pass, 'note' => $note );
	tpb_out( '  ' . ( $pass ? '✅' : '❌' ) . ' ' . $title . ( $pass || '' === $note ? '' : '   ← ' . $note ) );
	if ( ! $pass ) { $ok = false; }
	return (bool) $pass;
}

function rt_mysql( $db, array $args, $stdin_gz = null, $database = '' ) {
	$cmd = array( TP_MYSQL, '--protocol=tcp', '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['user'], '--default-character-set=utf8mb4', '--max-allowed-packet=256M' );
	if ( '' !== $database ) { $cmd[] = $database; }
	$cmd = array_merge( $cmd, $args );
	$env = tpb_env( array( 'MYSQL_PWD' => $db['pass'] ) );
	$p = proc_open( $cmd, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes, null, $env );
	if ( ! is_resource( $p ) ) { return array( 127, '', 'proc_open' ); }
	if ( null !== $stdin_gz ) {
		$g = gzopen( $stdin_gz, 'rb' );
		while ( ! gzeof( $g ) ) { $c = gzread( $g, 1 << 20 ); if ( false === $c ) { break; } fwrite( $pipes[0], $c ); }
		gzclose( $g );
	}
	fclose( $pipes[0] );
	stream_set_blocking( $pipes[1], true );
	$out = stream_get_contents( $pipes[1] ); $err = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] ); fclose( $pipes[2] );
	return array( proc_close( $p ), $out, $err );
}

function rt_clean_up( $db, $tmpdb, $work, $keep ) {
	if ( preg_match( '/^topersia_rt_\d{12}$/', $tmpdb ) && $tmpdb !== $db['name'] ) { rt_mysql( $db, array( '-e', "DROP DATABASE IF EXISTS `{$tmpdb}`" ) ); }
	if ( ! $keep ) { tpb_rrmdir( $work ); }
}

register_shutdown_function( function () use ( $db, $tmpdb, $work, $opt ) { rt_clean_up( $db, $tmpdb, $work, ! empty( $opt['keep'] ) ); } );

tpb_out( "آزمون بازگردانی: {$name} → {$tmpdb} ({$tmpurl})" );

/* ۱ — sha256 */
$bad = array();
foreach ( $man['files'] as $f => $m ) {
	if ( ! is_file( $dir . '/' . $f ) || filesize( $dir . '/' . $f ) !== $m['bytes'] || tpb_sha( $dir . '/' . $f ) !== $m['sha256'] ) { $bad[] = $f; }
}
$go = rt_step( $steps, $ok, '۱ sha256 و اندازهٔ همهٔ پرونده‌ها با manifest', ! $bad, implode( ',', $bad ) );

/* ۲ — پایگاهٔ موقت و درون‌ریزی */
if ( $go ) {
	list( $c, , $e ) = rt_mysql( $db, array( '-e', "DROP DATABASE IF EXISTS `{$tmpdb}`; CREATE DATABASE `{$tmpdb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci" ) );
	$go = rt_step( $steps, $ok, "۲ ساخت پایگاهٔ موقت {$tmpdb}", 0 === $c, substr( tpb_clean( $e ), 0, 200 ) );
}
if ( $go ) {
	list( $c, , $e ) = rt_mysql( $db, array(), $dir . '/db.sql.gz', $tmpdb );
	$tables = (int) trim( rt_mysql( $db, array( '-N', '-e', 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=\'' . $tmpdb . '\'' ) )[1] );
	$go = rt_step( $steps, $ok, "۲ درون‌ریزی db.sql.gz ({$tables} جدول از " . $man['database']['tables'] . ')', 0 === $c && $tables === (int) $man['database']['tables'], substr( tpb_clean( $e ), 0, 200 ) );
}

/* ۳ — اتصال به پایگاهٔ موقت، نه اصلی */
if ( $go ) {
	$r = tpb_run( tpb_wp_cmd( array( 'eval', 'global $wpdb; echo "DBN=" . $wpdb->dbname;' ), $override ) );
	$go = rt_step( $steps, $ok, '۳ WP-CLI با override به پایگاهٔ موقت وصل است (نه topersia_local)', false !== strpos( $r[1], 'DBN=' . $tmpdb ) && false === strpos( $r[1], 'DBN=' . $db['name'] ), substr( tpb_clean( $r[1] . $r[2] ), 0, 200 ) );
}

/* ۴ — اثر انگشت و واقعیت‌ها */
if ( $go ) {
	$fp_now = tpb_eval_file( TP_WORKSPACE . '/tests/lib/db-fingerprint.php', $work . '/fp.json', $override );
	$fx_now = tpb_eval_file( __DIR__ . '/lib/tp-backup-facts.php', $work . '/facts.json', $override );
	$fp_man = json_decode( (string) file_get_contents( $dir . '/fingerprint.json' ), true );
	$diff   = array();
	foreach ( $fp_man as $k => $v ) { if ( ! isset( $fp_now[ $k ] ) || $fp_now[ $k ] !== $v ) { $diff[] = $k; } }
	foreach ( $diff as $d ) { if ( is_array( $fp_man[ $d ] ) ) { foreach ( $fp_man[ $d ] as $kk => $vv ) { if ( $vv !== ( $fp_now[ $d ][ $kk ] ?? null ) ) { $diff[] = $d . '.' . $kk . '(' . ( is_scalar( $vv ) ? $vv : '…' ) . '≠' . ( isset( $fp_now[ $d ][ $kk ] ) && is_scalar( $fp_now[ $d ][ $kk ] ) ? $fp_now[ $d ][ $kk ] : '؟' ) . ')'; } } } }
	if ( $diff ) {   /* تشخیص: نام گزینه‌های اضافه/کم (فقط نام) */
		$m = new mysqli( $db['host'], $db['user'], $db['pass'], '', $db['port'] );
		$pre = 'jwWSU9v_';
		$la = array_column( $m->query( "SELECT option_name FROM `{$db['name']}`.`{$pre}options`" )->fetch_all(), 0 );
		$lb = array_column( $m->query( "SELECT option_name FROM `{$tmpdb}`.`{$pre}options`" )->fetch_all(), 0 );
		$sc = isset( $man['scrub']['options'] ) ? $man['scrub']['options'] : array();
		tpb_out( '   فقط در بازگردانی: ' . implode( ' | ', array_diff( $lb, $la ) ) . ' ؛ فقط در اصلی (بی رازدار): ' . implode( ' | ', array_slice( array_diff( $la, $lb, $sc ), 0, 10 ) ) );
	}
	$go = rt_step( $steps, $ok, '۴ اثر انگشت پایگاه بازگردانی‌شده = اثر انگشت manifest (شمار ردیف هر جدول، چکسام جدول‌های محتوا، گزینه‌ها، متای هر پست)', ! $diff, 'ناهمخوان: ' . implode( '،', $diff ) );
	if ( $go ) {
		$same = $fx_now['posts_by_type'] === $man['posts_by_type'] && $fx_now['relation_rows'] === $man['relation_rows'] && $fx_now['wpml'] === $man['wpml'] && $fx_now['wp_version'] === $man['wordpress'] && $fx_now['tables'] === $man['database']['tables'];
		$go = rt_step( $steps, $ok, '۴ شمار پست هر نوع، ردیف رابطه، نسخهٔ WPML و وردپرس = manifest', $same, 'posts/rel/wpml' );
	}
}

/* ۵ — search-replace نشانی */
if ( $go ) {
	$old = $man['home'];
	$r = tpb_run( tpb_wp_cmd( array( 'search-replace', $old, $tmpurl, '--all-tables-with-prefix', '--skip-columns=guid', '--precise', '--report=false' ), $override ) );
	$rows_before = $fp_now['count'];
	$fp2   = tpb_eval_file( TP_WORKSPACE . '/tests/lib/db-fingerprint.php', $work . '/fp2.json', $override );
	$home  = tpb_run( tpb_wp_cmd( array( 'option', 'get', 'home' ), $override ) );
	$site  = tpb_run( tpb_wp_cmd( array( 'option', 'get', 'siteurl' ), $override ) );
	$left  = tpb_run( tpb_wp_cmd( array( 'search-replace', $old, $tmpurl, '--all-tables-with-prefix', '--skip-columns=guid', '--precise', '--dry-run', '--format=count' ), $override ) );
	$left_n = (int) trim( tpb_clean( $left[1] ) );
	$h = trim( tpb_clean( $home[1] ) ); $s = trim( tpb_clean( $site[1] ) );
	$go = rt_step( $steps, $ok, '۵ search-replace: نشانی قدیم در هیچ جدولی نمانده؛ home و siteurl نشانی موقت‌اند؛ شمار ردیف هیچ جدولی عوض نشد',
		0 === $r[0] && 0 === $left_n && 0 === strpos( $h, $tmpurl ) && 0 === strpos( $s, $tmpurl ) && $fp2['count'] === $rows_before, "home={$h} siteurl={$s} exit={$r[0]}" );
}

/* ۶ — بوت کامل وردپرس با افزونه‌ها */
if ( $go ) {
	$r = tpb_run( tpb_wp_cmd( array( 'eval', 'echo "PUB=" . (int) wp_count_posts( "tp_province" )->publish . " CITY=" . (int) wp_count_posts( "tp_city" )->publish . " REL=" . (function_exists( "jet_engine" ) && jet_engine()->relations ? count( jet_engine()->relations->get_active_relations() ) : -1 );' ), $override, true ) );
	preg_match( '/PUB=(\d+) CITY=(\d+) REL=(-?\d+)/', $r[1], $m );
	$want_p = $man['posts_by_type']['tp_province']['total'] ?? -2;
	$go = rt_step( $steps, $ok, '۶ وردپرس با همهٔ افزونه‌ها روی پایگاه بازگردانی‌شده بالا می‌آید؛ استان‌ها و رابطه‌های جت‌انجین خوانده می‌شوند', isset( $m[1] ) && (int) $m[1] > 0 && (int) $m[1] <= $want_p && (int) $m[3] > 0, substr( tpb_clean( $r[1] . $r[2] ), 0, 250 ) );
}

/* ۷ — uploads */
if ( $go ) {
	$z = new ZipArchive();
	$okz = true === $z->open( $dir . '/uploads.zip', ZipArchive::CHECKCONS ) && $z->numFiles === $man['uploads']['files'];
	$cnt = 0; $bytes = 0;
	if ( $okz ) {
		$x = $work . '/uploads';
		mkdir( $x );
		$okz = $z->extractTo( $x );
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $x, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $f ) { if ( $f->isFile() ) { $cnt ++; $bytes += $f->getSize(); } }
		$z->close();
	}
	$go = rt_step( $steps, $ok, '۷ uploads.zip باز می‌شود؛ شمار پرونده و حجم = manifest', $okz && $cnt === $man['uploads']['files'] && $bytes === $man['uploads']['bytes'], "files={$cnt} bytes={$bytes}" );
}

/* نتیجه */
$res = array(
	'package'  => $name, 'when' => date( 'c' ), 'temp_db' => $tmpdb, 'temp_url' => $tmpurl, 'ok' => $ok,
	'seconds'  => round( microtime( true ) - $t0 ), 'steps' => $steps,
);
file_put_contents( $dir . '/RESTORE-TEST.json', json_encode( $res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
if ( $ok ) { file_put_contents( $root . '/LAST-RESTORED.json', json_encode( array( 'package' => $name, 'when' => date( 'c' ) ), JSON_PRETTY_PRINT ) ); }
tpb_out( ( $ok ? '✓ بازگردانی و تطبیق موفق' : '✗ بازگردانی ناموفق — ادامه داده نشد' ) . sprintf( ' (%d ثانیه)؛ موقت‌ها پاک می‌شوند', $res['seconds'] ) );
exit( $ok ? 0 : 1 );
