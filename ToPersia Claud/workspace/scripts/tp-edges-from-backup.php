<?php
/**
 * یال‌های رابطهٔ یک پست از روی بستهٔ پشتیبان — فقط‌خواندنی؛ دستهٔ 38-ب · P5-D481. فقط topersia.local.
 *
 *   php scripts/tp-edges-from-backup.php full-20261030-1340 5485 2095,3303,3304 out.json [--rels=71,95,156,159,185,226,228]
 *
 * بسته را روی پایگاه موقت `topersia_rt_*` درون‌ریزی می‌کند (مثل tp-restore-test؛ هرگز روی پایگاه اصلی)، یال‌های
 * `jet_rel_default` که یکی از شناسه‌های قدیمی در آن‌هاست را می‌خواند، همراه نوع/وضعیت/نامک/عنوان/entity_uid سرِ دیگر،
 * در out.json می‌نویسد و پایگاه موقت را (همیشه) پاک می‌کند. هیچ‌چیز در پایگاه اصلی نوشته نمی‌شود.
 * خروجی: { old_ids, edges: [ { rel_id, side: parent|child, other_id, other: { type, status, slug, title, uid } } ] }
 */

require_once __DIR__ . '/lib/tp-backup-lib.php';

$opt  = tpb_opts( $argv );
$a    = isset( $opt['_'] ) ? $opt['_'] : array();
if ( count( $a ) < 4 ) { tpb_fail( 'استفاده: tp-edges-from-backup.php <بسته> <شناسهٔ جدید> <شناسه‌های قدیمی> <out.json>', 2 ); }
list( $pkg, $new_id, $old_csv, $out_file ) = $a;
$old  = array_values( array_filter( array_map( 'intval', explode( ',', $old_csv ) ) ) );
$rels = isset( $opt['rels'] ) ? array_map( 'intval', explode( ',', (string) $opt['rels'] ) ) : array( 71, 95, 156, 159, 185, 226, 228 );
$root = tpb_root( $opt );
$db   = tpb_db();
$dir  = $root . '/' . basename( $pkg );
$man  = json_decode( (string) @file_get_contents( $dir . '/manifest.json' ), true );
if ( ! is_array( $man ) || ! is_file( $dir . '/db.sql.gz' ) ) { tpb_fail( 'بسته پیدا نشد: ' . $pkg, 2 ); }
if ( ! $old ) { tpb_fail( 'شناسهٔ قدیمی نیست', 2 ); }

$tmpdb = 'topersia_rt_' . date( 'YmdHi' );
if ( ! preg_match( '/^topersia_rt_\d{12}$/', $tmpdb ) || $tmpdb === $db['name'] ) { tpb_fail( 'نام موقت نامعتبر', 2 ); }
$m = new mysqli( $db['host'], $db['user'], $db['pass'], '', $db['port'] );
register_shutdown_function( function () use ( $db, $tmpdb ) { $x = new mysqli( $db['host'], $db['user'], $db['pass'], '', $db['port'] ); $x->query( "DROP DATABASE IF EXISTS `{$tmpdb}`" ); } );

try {
	$m->query( "DROP DATABASE IF EXISTS `{$tmpdb}`" );
	$m->query( "CREATE DATABASE `{$tmpdb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci" );

	$cmd = array( TP_MYSQL, '--protocol=tcp', '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['user'], '--default-character-set=utf8mb4', '--max-allowed-packet=256M', $tmpdb );
	$p   = proc_open( $cmd, array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'file', sys_get_temp_dir() . '/tp-eb-err.txt', 'w' ) ), $pipes, null, tpb_env( array( 'MYSQL_PWD' => $db['pass'] ) ) );
	$g   = gzopen( $dir . '/db.sql.gz', 'rb' );
	while ( ! gzeof( $g ) ) { $c = gzread( $g, 1 << 20 ); if ( false === $c ) { break; } fwrite( $pipes[0], $c ); }
	gzclose( $g ); fclose( $pipes[0] ); stream_get_contents( $pipes[1] ); fclose( $pipes[1] );
	if ( 0 !== proc_close( $p ) ) { tpb_fail( 'درون‌ریزی ناموفق' ); }

	$pre = strtolower( $db['prefix'] );   /* ویندوز: نام جدول‌ها کوچک‌حرف */
	$in  = implode( ',', $old );
	$rl  = implode( ',', $rels );
	$res = $m->query( "SELECT rel_id, parent_object_id p, child_object_id c FROM `{$tmpdb}`.`{$pre}jet_rel_default` WHERE rel_id IN ({$rl}) AND ( parent_object_id IN ({$in}) OR child_object_id IN ({$in}) ) ORDER BY rel_id, p, c" );
	$edges = array();
	while ( $r = $res->fetch_assoc() ) {
		$side  = in_array( (int) $r['p'], $old, true ) ? 'parent' : 'child';
		$other = (int) ( 'parent' === $side ? $r['c'] : $r['p'] );
		$o     = $m->query( "SELECT post_type, post_status, post_name, post_title FROM `{$tmpdb}`.`{$pre}posts` WHERE ID = {$other}" )->fetch_assoc();
		$uid   = $m->query( "SELECT meta_value FROM `{$tmpdb}`.`{$pre}postmeta` WHERE post_id = {$other} AND meta_key = 'entity_uid' LIMIT 1" )->fetch_row();
		$edges[] = array( 'rel_id' => (int) $r['rel_id'], 'side' => $side, 'old_owner' => (int) ( 'parent' === $side ? $r['p'] : $r['c'] ), 'other_id' => $other,
			'other' => array( 'type' => $o ? $o['post_type'] : '', 'status' => $o ? $o['post_status'] : '', 'slug' => $o ? $o['post_name'] : '', 'title' => $o ? $o['post_title'] : '', 'uid' => $uid ? $uid[0] : '' ) );
	}
	file_put_contents( $out_file, json_encode( array( 'package' => basename( $pkg ), 'new_id' => (int) $new_id, 'old_ids' => $old, 'edges' => $edges ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
	tpb_out( '✓ ' . count( $edges ) . ' یال از بستهٔ ' . basename( $pkg ) . ' → ' . $out_file );
} finally {
	$m->query( "DROP DATABASE IF EXISTS `{$tmpdb}`" );
	@unlink( sys_get_temp_dir() . '/tp-eb-err.txt' );
}
