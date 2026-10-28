<?php
/**
 * آزمون رفت‌وبرگشت نمونه‌ها روی کپی پایگاه — دستهٔ 32 بند 7 · P5-D457. فقط محلی.
 *
 *   php tests/samples-roundtrip-local.php
 *
 * topersia_local ← کپی `topersia_rt_b32` (پوشهٔ بارگذاری جدا؛ کپی روی سایت اصلی اثر ندارد). در کپی:
 *   remove confirm (نمونه‌های پیشین) ← اثر انگشت S0 ← create ← remove confirm ← اثر انگشت S1.
 * S0 باید با S1 برابر باشد: نوشته‌های واقعی (شمار و چکسام)، شمار کل ردیف‌های رابطه، نمایهٔ جغرافیایی، trid ها،
 * خلاصه‌های AI و شمار هر جدول. تا این آزمون نگذرد نمونه‌ها نباید روی سایت زنده بروند (P5-D457).
 * جهش: یک ردیف رابطهٔ بازمانده در S1 باید دیده شود.
 */
require_once __DIR__ . '/env.php';

$db   = 'topersia_rt_b32';
$cfg  = (string) file_get_contents( TP_WP_DIR . '/wp-config.php' );
$get  = function ( $k ) use ( $cfg ) { return preg_match( "~define\(\s*'$k'\s*,\s*'([^']*)'~", $cfg, $m ) ? $m[1] : ''; };
$user = $get( 'DB_USER' );
$pass = $get( 'DB_PASSWORD' );
$src  = $get( 'DB_NAME' );
$hp   = array_pad( explode( ':', $get( 'DB_HOST' ) ), 2, '3306' );
$host = $hp[0];
$port = $hp[1];
$bin  = dirname( TP_MYSQL );
$tmp  = sys_get_temp_dir() . '/tp-rt32-' . getmypid();
@mkdir( $tmp );
putenv( 'MYSQL_PWD=' . $pass );

$my = function ( $exe, $args, $stdin = null, $stdout = null ) use ( $bin, $user, $host, $port ) {
	$cmd = '"' . $bin . '/' . $exe . '.exe" -u' . $user . ' -h' . $host . ' -P' . $port . ' ' . $args;
	if ( $stdout ) { $cmd .= ' > "' . $stdout . '"'; }
	if ( $stdin ) { $cmd .= ' < "' . $stdin . '"'; }
	exec( $cmd . ' 2>&1', $o, $rc );
	return array( $rc, implode( "\n", $o ) );
};
$pass_n = 0;
$fail_n = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass_n, &$fail_n ) {
	$ok ? $pass_n++ : $fail_n++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
$cleanup = function () use ( $my, $db, $tmp ) {
	$my( 'mysql', '-e "DROP DATABASE IF EXISTS ' . $db . '"' );
	$ud = TP_WP_DIR . '/wp-content/uploads-rt32';
	if ( is_dir( $ud ) ) { exec( 'rmdir /s /q "' . str_replace( '/', '\\', $ud ) . '"' ); }
	foreach ( (array) glob( $tmp . '/*' ) as $f ) { @unlink( $f ); }
	@rmdir( $tmp );
};
register_shutdown_function( $cleanup );

echo "کپی پایگاه…\n";
$my( 'mysql', '-e "DROP DATABASE IF EXISTS ' . $db . '; CREATE DATABASE ' . $db . ' CHARACTER SET utf8mb4"' );
$r = $my( 'mysqldump', '--single-transaction --skip-comments ' . $src, null, $tmp . '/dump.sql' );
$t( 'mysqldump از پایگاه اصلی', 0 === $r[0], $r[1] );
$r = $my( 'mysql', $db, $tmp . '/dump.sql' );
$t( 'درون‌ریزی در کپی', 0 === $r[0], $r[1] );

file_put_contents( $tmp . '/boot.php', "<?php\ndefine( 'DB_NAME', '$db' );\ndefine( 'UPLOADS', 'wp-content/uploads-rt32' );\n" );
@mkdir( TP_WP_DIR . '/wp-content/uploads-rt32' );
putenv( 'TP_SAMPLES_BACKUP_DIR=' . $tmp );

$wp = function ( $file, $extra = '' ) use ( $tmp ) {
	$cmd = '"' . TP_PHP . '" "' . TP_WP_CLI . '" --path="' . TP_WP_DIR . '" --url=' . TP_BASE_URL . ' --require="' . $tmp . '/boot.php" eval-file "' . $file . '" ' . $extra;
	exec( $cmd . ' 2>&1', $o, $rc );
	return array( $rc, implode( "\n", $o ) );
};
$fp = function ( $name ) use ( $wp, $tmp ) {
	$wp( __DIR__ . '/lib/db-fingerprint.php', '"' . $tmp . '/' . $name . '.json"' );
	return is_file( $tmp . '/' . $name . '.json' ) ? json_decode( file_get_contents( $tmp . '/' . $name . '.json' ), true ) : null;
};
$sam = dirname( __DIR__ ) . '/scripts/tp-samples.php';

/* wp-cli واقعاً به کپی وصل است؟ (نام پایگاه از خود وردپرس) */
file_put_contents( $tmp . '/who.php', "<?php\nglobal \$wpdb;\nfile_put_contents( '" . str_replace( '\\', '/', $tmp ) . "/who.txt', \$wpdb->dbname );\n" );
$wp( $tmp . '/who.php' );
$who = is_file( $tmp . '/who.txt' ) ? trim( file_get_contents( $tmp . '/who.txt' ) ) : '';
$t( 'wp-cli بر کپی اجرا می‌شود، نه سایت اصلی', $who === $db, $who );
if ( $who !== $db ) { echo "  ↳ توقف\n"; exit( 1 ); }

$r = $wp( $sam, 'remove confirm' );
echo '  · ' . trim( strtok( $r[1], "\n" ) ) . "\n";
$s0 = $fp( 's0' );
$t( 'نمونه‌های پیشین از کپی برداشته شد (S0)', is_array( $s0 ) && ( false !== strpos( $r[1], 'پاک شد' ) || false !== strpos( $r[1], 'نمونه‌ای نیست' ) ), substr( $r[1], 0, 300 ) );

$r = $wp( $sam, 'create' );
$t( 'create در کپی اجرا شد', false !== strpos( $r[1], '+ tp_' ), substr( $r[1], -300 ) );
$mid = $fp( 'smid' );
$t( 'پس از create ردیف‌ها آمده‌اند (آزمون بی‌معنی نیست)', is_array( $mid ) && $mid['real']['posts'] > $s0['real']['posts'] && $mid['real']['rel_total'] > $s0['real']['rel_total'], json_encode( array( $s0['real'], $mid['real'] ?? null ) ) );

$r = $wp( $sam, 'remove confirm' );
$t( 'remove confirm پس از create', false !== strpos( $r[1], 'پاک شد' ) && false === strpos( $r[1], 'هشدار P5-D456' ), substr( $r[1], 0, 400 ) );
$s1 = $fp( 's1' );

$diff = function ( $a, $b ) {
	$d = array();
	/* فقط‌افزودنی/تاریخ‌دار و بی‌اثر بر محتوا: لاگ هسته، لاگ صف، و دستهٔ ترجمهٔ روزانهٔ WPML («Manual Translations from <تاریخ>») */
	$noise = array( 'topersia_log', 'actionscheduler_logs', 'actionscheduler_actions', 'icl_translation_batches' );
	foreach ( array( 'count', 'sum' ) as $k ) {
		foreach ( $a[ $k ] as $tab => $v ) {
			if ( in_array( $tab, $noise, true ) ) { continue; }
			if ( ( $b[ $k ][ $tab ] ?? null ) !== $v ) { $d[] = "$k:$tab " . $v . '→' . ( $b[ $k ][ $tab ] ?? '؟' ); }
		}
	}
	foreach ( $a['real'] as $k => $v ) {
		if ( $b['real'][ $k ] !== $v ) { $d[] = "real:$k " . $v . '→' . $b['real'][ $k ]; }
	}
	foreach ( $a['options'] as $k => $v ) {
		if ( $b['options'][ $k ] !== $v ) { $d[] = "options:$k"; }
	}
	/* صف: فقط کارهای در انتظار (کار لغوشده/انجام‌شده تاریخچه است)؛ تفاوت با نشان آرگومان */
	$pa = array_diff( $b['as_pending_args'] ?? array(), $a['as_pending_args'] ?? array() );
	$pb = array_diff( $a['as_pending_args'] ?? array(), $b['as_pending_args'] ?? array() );
	if ( $pa || $pb ) { $d[] = 'صف در انتظار: تازه ' . implode( ' ; ', array_slice( $pa, 0, 6 ) ) . ' | گم ' . implode( ' ; ', array_slice( $pb, 0, 3 ) ); }
	foreach ( array() as $k ) {
		foreach ( array() as $x ) {
			if ( ( $a[ $k ][ $x ] ?? 0 ) !== ( $b[ $k ][ $x ] ?? 0 ) ) { $d[] = "$k:$x " . ( $a[ $k ][ $x ] ?? 0 ) . '→' . ( $b[ $k ][ $x ] ?? 0 ); }
		}
	}
	$ch = array();
	foreach ( array_unique( array_merge( array_keys( $a['meta_by_post'] ?? array() ), array_keys( $b['meta_by_post'] ?? array() ) ) ) as $pid ) {
		$x = $a['meta_by_post'][ $pid ] ?? array();
		$y = $b['meta_by_post'][ $pid ] ?? array();
		if ( $x !== $y ) {
			$keys = array_unique( array_map( function ( $z ) { return strstr( $z, '=', true ); }, array_merge( array_diff( $x, $y ), array_diff( $y, $x ) ) ) );
			$ch[] = $pid . '{' . implode( ',', array_slice( $keys, 0, 4 ) ) . '}';
		}
	}
	if ( $ch ) { $d[] = 'meta تغییر کرده برای ' . count( $ch ) . ' پست: ' . implode( ' ', array_slice( $ch, 0, 12 ) ); }
	return $d;
};
$d = is_array( $s1 ) ? $diff( $s0, $s1 ) : array( 'بی خروجی' );
$t( 'پس از create + remove همه‌چیز برابر پیش از نمونه‌هاست: نوشته‌های واقعی، ردیف‌های رابطه، نمایهٔ جغرافیایی، trid، AI، شمار هر جدول', ! $d, implode( ' · ', array_slice( $d, 0, 14 ) ) );
if ( is_array( $s1 ) ) {
	echo '  · نوشته‌ها ' . $s1['real']['posts'] . ' · ردیف رابطه ' . $s1['real']['rel_total'] . ' · نمایهٔ جغرافیایی ' . $s1['real']['geo'] . "\n";
	$mut = $s1;
	$mut['real']['rel_total']++;
	$mut['count']['jet_rel_default']++;
	$t( 'جهش: یک ردیف رابطهٔ بازمانده آزمون را می‌اندازد', (bool) $diff( $s0, $mut ) );
}

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass_n, $fail_n );
exit( $fail_n ? 1 : 0 );
