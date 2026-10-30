<?php
/**
 * پشتیبان کامل محلی — دستهٔ 37 · P5-D475 (LAUNCH.md P0-1 · B-1). فقط topersia.local.
 *
 *   php scripts/tp-backup.php [--dir=C:/wamp64/tp-backups] [--no-prune] [--allow-unstable]
 *   php scripts/tp-backup.php --prune-only [--dry-run]
 *
 * بسته (full-YYYYMMDD-HHMM/): db.sql.gz (wp db export، فشرده، single-transaction) · uploads.zip · fingerprint.json ·
 * manifest.json (sha256 و اندازه، نسخهٔ WPML، شمار پست هر نوع، شمار ردیف رابطه، اثر انگشت tests/lib/db-fingerprint.php).
 * wp-config و هر کلید و رمز در هیچ بسته‌ای نیست: آرشیو uploads پروندهٔ کلیددار را رد می‌کند و خروجی پایگاه‌داده
 * هنگام نوشتن با الگوهای git-guard (AIza · sk- · xi-api-key · کلید خصوصی) پویش می‌شود؛ اگر چیزی پیدا شد بسته پاک و
 * کار با خروجی ۳ رد می‌شود. مقصد باید بیرون از مخزن git باشد. نگه‌داری ۷ روزانه + ۴ هفتگی.
 */

require_once __DIR__ . '/lib/tp-backup-lib.php';

$opt  = tpb_opts( $argv );
$root = tpb_root( $opt );

function tpb_prune( $root, $dry ) {
	$r = tpb_retention( $root );
	foreach ( $r['keep'] as $n => $why ) { tpb_out( "  نگه: {$n} (" . implode( '، ', array_unique( $why ) ) . ')' ); }
	foreach ( $r['drop'] as $n ) {
		if ( $dry ) { tpb_out( "  پاک می‌شد: {$n}" ); continue; }
		tpb_rrmdir( $root . '/' . $n );
		tpb_out( "  پاک شد: {$n}" );
	}
}

if ( ! empty( $opt['prune-only'] ) ) {
	tpb_prune( $root, ! empty( $opt['dry-run'] ) );
	exit( 0 );
}

$db   = tpb_db();
$name = 'full-' . date( 'Ymd-Hi' );
$dir  = $root . '/' . $name;
if ( file_exists( $dir ) ) { tpb_fail( "بسته هست: {$name}", 2 ); }
mkdir( $dir, 0777, true );
$t0 = microtime( true );

$fp_script = TP_WORKSPACE . '/tests/lib/db-fingerprint.php';
$fx_script = __DIR__ . '/lib/tp-backup-facts.php';
$tmp       = $dir . '/_tmp';
mkdir( $tmp );

/* ── ۰) ردیف‌های رازدار: نامشان فهرست می‌شود و در بسته نمی‌روند ── */
tpb_out( '۰) فهرست ردیف‌های رازدار (فقط نام)…' );
$scrub = tpb_eval_file( __DIR__ . '/lib/tp-backup-scrub.php', $tmp . '/scrub.json' );
$GLOBALS['tpb_fp_scrub'] = json_encode( $scrub, JSON_UNESCAPED_UNICODE );
tpb_out( '   گزینه‌های کنار‌گذاشته: ' . count( $scrub['options'] ) . ' · کلیدهای usermeta: ' . count( $scrub['usermeta'] ) );

/* ── ۱) اثر انگشت و واقعیت‌ها پیش از خروجی ── */
tpb_out( '۱) اثر انگشت پیش از خروجی…' );
$fp_before = tpb_eval_file( $fp_script, $tmp . '/fp-before.json' );
$facts     = tpb_eval_file( $fx_script, $tmp . '/facts.json' );

/* ── ۲) خروجی پایگاه‌داده، فشرده، با پویش کلید ── */
tpb_out( '۲) خروجی پایگاه‌داده (wp db export، فشرده)…' );
$gz  = gzopen( $dir . '/db.sql.gz', 'wb6' );
$pre  = 'jwWSU9v_';
$q    = function ( $l ) { return "'" . implode( "','", array_map( function ( $x ) { return str_replace( array( chr( 92 ), "'" ), array( chr( 92 ) . chr( 92 ), chr( 92 ) . "'" ), $x ); }, $l ) ) . "'"; };
/* سه گذر: همه‌چیز جز options و usermeta ؛ options بی ردیف‌های رازدار ؛ usermeta بی نشست‌ها */
$passes = array(
	array( 'exclude_tables=' . $pre . 'options,' . $pre . 'usermeta' ),
	array( 'tables=' . $pre . 'options', 'where=option_name NOT IN (' . $q( $scrub['options'] ) . ')' ),
	array( 'tables=' . $pre . 'usermeta', 'where=meta_key NOT IN (' . $q( $scrub['usermeta'] ) . ')' ),
);
$hits = array(); $table = ''; $carry = ''; $bytes = 0; $err = ''; $code = 0; $ended = 0;
foreach ( $passes as $pi => $pass ) {
$cmd = tpb_wp_cmd( array_merge( array( 'db', 'export', '-', '--single-transaction', '--quick', '--default-character-set=utf8mb4', '--routines', '--events' ), array_map( function ( $a ) { return '--' . $a; }, $pass ) ) );
$p   = proc_open( $cmd, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes, null, tpb_env() );
if ( ! is_resource( $p ) ) { tpb_fail( 'wp db export اجرا نشد' ); }
fclose( $pipes[0] );

$pat   = array(
	'کلید Google'     => '/AIza[0-9A-Za-z_-]{35}/',
	'کلید OpenAI'     => '/(?<![A-Za-z0-9_])sk-(?:proj-)?[A-Za-z0-9_-]{20,}/',
	'مقدار xi-api-key' => '/xi-api-key[\'"]?\s*(?::|=>?|,)\s*[\'"]?[A-Za-z0-9_]{20,}/',
	'کلید خصوصی'      => '/-----BEGIN (?:[A-Z]+ )*PRIVATE KEY-----/',
);
while ( ! feof( $pipes[1] ) ) {
	$chunk = fread( $pipes[1], 1 << 20 );
	if ( false === $chunk || '' === $chunk ) { continue; }
	gzwrite( $gz, $chunk );
	$bytes += strlen( $chunk );
	$buf = $carry . $chunk;
	foreach ( $pat as $label => $re ) {
		if ( preg_match_all( $re, $buf, $m, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $m[0] as $hit ) {
				$before = substr( $buf, 0, $hit[1] );
				$t      = preg_match_all( '/(?:INSERT INTO|CREATE TABLE|LOCK TABLES) `([^`]+)`/', $before, $tm ) ? end( $tm[1] ) : $table;
				$hits[ $label ][ $t ?: '؟' ] = ( isset( $hits[ $label ][ $t ?: '؟' ] ) ? $hits[ $label ][ $t ?: '؟' ] : 0 ) + 1;
			}
		}
	}
	if ( preg_match_all( '/(?:INSERT INTO|CREATE TABLE) `([^`]+)`/', $buf, $tm ) ) { $table = end( $tm[1] ); }
	$carry = substr( $buf, -256 );
}
$err .= stream_get_contents( $pipes[2] );
fclose( $pipes[1] ); fclose( $pipes[2] );
$c = proc_close( $p );
if ( 0 !== $c ) { $code = $c; }
}   /* پایان گذرها */
gzclose( $gz );

if ( 0 !== $code || $bytes < 1000000 || 3 !== preg_match_all( '/-- Dump completed/', (string) tpb_all_gz( $dir . '/db.sql.gz' ) ) ) {
	tpb_rrmdir( $dir );
	tpb_fail( 'خروجی پایگاه‌داده کامل نشد (کد ' . $code . '): ' . substr( tpb_clean( $err ), 0, 300 ) );
}
if ( $hits ) {
	tpb_rrmdir( $dir );
	tpb_out( 'پویش کلید در خروجی پایگاه‌داده: پیدا شد (فقط جدول و شمار، بی مقدار):' );
	foreach ( $hits as $label => $ts ) { foreach ( $ts as $t => $n ) { tpb_out( "  {$label} — {$t}: {$n}" ); } }
	tpb_fail( 'بسته پاک شد؛ ' . 'کلید داخل پایگاه‌داده است و باید پیش از پشتیبان تصمیم شود', 3 );
}

/** همهٔ «Dump completed»ها (یکی برای هر گذر) از فایل gz — بی نگه‌داشتن کل فایل در حافظه. */
function tpb_all_gz( $file ) {
	$g = gzopen( $file, 'rb' ); $out = ''; $carry = '';
	while ( ! gzeof( $g ) ) { $c = gzread( $g, 1 << 20 ); if ( false === $c ) { break; } $buf = $carry . $c; if ( preg_match_all( '/-- Dump completed[^
]*/', $buf, $m ) ) { $out .= implode( "
", $m[0] ) . "
"; } $carry = substr( $buf, -64 ); }
	gzclose( $g );
	return $out;
}

/* ── ۳) اثر انگشت پس از خروجی؛ باید یکی باشد ── */
tpb_out( '۳) اثر انگشت پس از خروجی…' );
$fp_after = tpb_eval_file( $fp_script, $tmp . '/fp-after.json' );
$stable   = ( $fp_before === $fp_after );
if ( ! $stable && empty( $opt['allow-unstable'] ) ) {
	tpb_rrmdir( $dir );
	$diff = array_keys( array_filter( $fp_before, function ( $v, $k ) use ( $fp_after ) { return $v !== $fp_after[ $k ]; }, ARRAY_FILTER_USE_BOTH ) );
	foreach ( $diff as $d ) { if ( is_array( $fp_before[ $d ] ) ) { foreach ( $fp_before[ $d ] as $kk => $vv ) { if ( $vv !== ( $fp_after[ $d ][ $kk ] ?? null ) ) { $diff[] = $d . '.' . $kk; } } } }
	tpb_fail( 'پایگاه‌داده هنگام پشتیبان عوض شد (بخش‌ها: ' . implode( '، ', $diff ) . ') — وقتی سایت آرام است دوباره اجرا کنید', 4 );
}

/* ── ۴) آرشیو uploads ── */
tpb_out( '۴) آرشیو uploads…' );
$up   = TP_WP_DIR . '/wp-content/uploads';
$zip  = new ZipArchive();
$zp   = $dir . '/uploads.zip';
$n    = 0; $size = 0; $skipped = array(); $batch = 0;
$store = '/\.(jpe?g|png|gif|webp|avif|mp4|webm|mp3|m4a|ogg|zip|gz|pmtiles|woff2?)$/i';
if ( true !== $zip->open( $zp, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) { tpb_fail( 'uploads.zip ساخته نشد' ); }
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $up, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::LEAVES_ONLY );
foreach ( $it as $f ) {
	if ( ! $f->isFile() ) { continue; }
	if ( tpb_secret_file( $f->getFilename() ) ) { $skipped[] = substr( str_replace( '\\', '/', $f->getPathname() ), strlen( $up ) + 1 ); continue; }
	$rel = substr( str_replace( '\\', '/', $f->getPathname() ), strlen( $up ) + 1 );
	$zip->addFile( $f->getPathname(), $rel );
	$zip->setCompressionName( $rel, preg_match( $store, $rel ) ? ZipArchive::CM_STORE : ZipArchive::CM_DEFLATE );
	$n ++; $size += $f->getSize(); $batch ++;
	if ( $batch >= 400 ) {   /* دستگیرهٔ باز پرونده‌ها در ویندوز محدود است */
		if ( ! $zip->close() || true !== $zip->open( $zp ) ) { tpb_fail( 'آرشیو uploads نوشته نشد' ); }
		$batch = 0;
	}
}
if ( ! $zip->close() ) { tpb_fail( 'آرشیو uploads بسته نشد' ); }
$chk = new ZipArchive();
if ( true !== $chk->open( $zp, ZipArchive::CHECKCONS ) || $chk->numFiles !== $n ) { tpb_fail( 'آرشیو uploads ناسازگار است' ); }
$chk->close();

/* ── ۵) manifest ── */
tpb_out( '۵) manifest…' );
copy( $tmp . '/fp-before.json', $dir . '/fingerprint.json' );
$fp = json_decode( (string) file_get_contents( $dir . '/fingerprint.json' ), true );
$files = array();
foreach ( array( 'db.sql.gz', 'uploads.zip', 'fingerprint.json' ) as $f ) { $files[ $f ] = array( 'bytes' => filesize( $dir . '/' . $f ), 'sha256' => tpb_sha( $dir . '/' . $f ) ); }
$manifest = array(
	'format'       => 1,
	'package'      => $name,
	'created'      => date( 'c' ),
	'site'         => TP_BASE_URL,
	'files'        => $files,
	'uploads'      => array( 'files' => $n, 'bytes' => $size, 'skipped_secret_files' => count( $skipped ) ),
	'database'     => array( 'server' => $facts['db_version'], 'tables' => $facts['tables'], 'dump_uncompressed_bytes' => $bytes ),
	'wordpress'    => $facts['wp_version'],
	'wpml'         => $facts['wpml'],
	'posts_by_type' => $facts['posts_by_type'],
	'relation_rows' => $facts['relation_rows'],
	'fingerprint'  => array( 'file' => 'fingerprint.json', 'sha256' => $files['fingerprint.json']['sha256'], 'stable_during_backup' => $stable, 'real' => $fp['real'], 'tables' => count( $fp['count'] ) ),
	'secret_scan'  => array( 'patterns' => array_keys( $pat ), 'hits' => 0 ),
	'scrub'        => $scrub,   /* ردیف‌هایی که عمداً در بسته نیستند (فقط نام) */
	'home'         => $facts['home'],
	'tools'        => array( 'php' => PHP_VERSION ),
);
file_put_contents( $dir . '/manifest.json', json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
tpb_rrmdir( $tmp );

tpb_out( sprintf( '✓ %s — db %s MB (فشرده) · uploads %d پرونده / %s MB · %.0f ثانیه', $name, number_format( $files['db.sql.gz']['bytes'] / 1048576, 1 ), $n, number_format( $files['uploads.zip']['bytes'] / 1048576, 1 ), microtime( true ) - $t0 ) );

if ( empty( $opt['no-prune'] ) ) { tpb_out( 'نگه‌داری:' ); tpb_prune( $root, false ); }
exit( 0 );
