<?php
/**
 * P5-D496 نسخهٔ دارایی از هش محتوا و P5-D495 گارد uid محلی.
 * از assert.php بار می‌شود؛ PHP خالص. نسخهٔ واقعی هسته روی صفحه: tests/core-local.php.
 */

echo "\nP5-D496 · P5-D495 — ver از محتوا و گارد uid\n" . str_repeat( '─', 78 ) . "\n";

/* پروندهٔ اصلی render در این هارنس بار نمی‌شود؛ خودِ تابع از همان منبع */
if ( ! function_exists( 'topersia_render_asset_version' ) ) {
	if ( ! function_exists( 'topersia_render_path' ) ) {
		function topersia_render_path() { return __DIR__ . '/../plugin/topersia-render/'; }
	}
	if ( ! defined( 'TOPERSIA_RENDER_VERSION' ) ) { define( 'TOPERSIA_RENDER_VERSION', '0-test' ); }
	preg_match( "/if \( ! function_exists\( 'topersia_render_asset_version' \) \) \{.*?\n\}\n/s", (string) file_get_contents( __DIR__ . '/../plugin/topersia-render/topersia-render.php' ), $v496_fn );
	eval( $v496_fn[0] );
}

$v496_dir = __DIR__ . '/.t496';
@mkdir( $v496_dir );
$v496_abs = $v496_dir . '/a.bin';
file_put_contents( $v496_abs, str_repeat( 'x', 2000 ) );

try {
	/* هر فراخوانی نام تازه دارد، چون حافظهٔ درخواست بر نام است */
	$v496_ver = function ( $path ) use ( $v496_dir ) {
		static $n = 0;
		$alias = 'al' . ( ++$n ) . '.bin';
		copy( $path, $v496_dir . '/' . $alias );
		touch( $v496_dir . '/' . $alias, filemtime( $path ) );
		clearstatcache();
		$v = topersia_render_asset_version( '../../tests/.t496/' . $alias );
		unlink( $v496_dir . '/' . $alias );
		return $v;
	};
	$v1 = $v496_ver( $v496_abs );
	touch( $v496_abs, time() + 5000 ); clearstatcache();
	$v2 = $v496_ver( $v496_abs );
	file_put_contents( $v496_abs, 'y' . str_repeat( 'x', 1999 ) );
	$v3 = $v496_ver( $v496_abs );
	t( 'render: ver ده نویسه از هش؛ جهش یک بایت ver را عوض می‌کند؛ جهش mtime بی تغییر محتوا ثابت می‌ماند',
		1 === preg_match( '/^[0-9a-f]{10}$/', (string) $v1 ) && $v1 === $v2 && $v1 !== $v3, "{$v1} · {$v2} · {$v3}" );
	t( 'render: در نبود فایل، نسخهٔ افزونه', TOPERSIA_RENDER_VERSION === topersia_render_asset_version( 'assets/no-such-file-496.css' ) );

	/* فایل دادهٔ کوچک (مرز) و بزرگ (PMTiles) */
	$s1 = topersia_g_file_ver( $v496_abs );
	touch( $v496_abs, time() + 9000 ); clearstatcache();
	$s2 = topersia_g_file_ver( $v496_abs );
	file_put_contents( $v496_abs, 'z' . str_repeat( 'x', 1999 ) ); clearstatcache();
	$s3 = topersia_g_file_ver( $v496_abs );
	t( 'مرز: فایل کوچک هش کامل؛ جهش mtime ثابت، جهش بایت عوض', $s1 === $s2 && $s1 !== $s3 && 10 === strlen( $s1 ), "{$s1} · {$s2} · {$s3}" );

	$big = $v496_dir . '/big.bin';
	$fh  = fopen( $big, 'wb' ); ftruncate( $fh, 22 * 1048576 ); fclose( $fh );
	$b1 = topersia_g_file_ver( $big );
	touch( $big, time() + 7000 ); clearstatcache();
	$b2 = topersia_g_file_ver( $big );
	$fh = fopen( $big, 'r+b' ); fwrite( $fh, 'H' ); fclose( $fh ); clearstatcache();
	$b3 = topersia_g_file_ver( $big );
	$fh = fopen( $big, 'r+b' ); fseek( $fh, 11 * 1048576 ); fwrite( $fh, 'M' ); fclose( $fh ); clearstatcache();
	$b4 = topersia_g_file_ver( $big );
	$fh = fopen( $big, 'r+b' ); fseek( $fh, -1, SEEK_END ); fwrite( $fh, 'T' ); fclose( $fh ); clearstatcache();
	$b5 = topersia_g_file_ver( $big );
	t( 'PMTiles: جهش mtime ثابت؛ بایت آغاز و پایان ver را عوض می‌کند؛ محدودیت ثبت‌شده: بایت میانی دیده نمی‌شود',
		$b1 === $b2 && $b1 !== $b3 && $b3 === $b4 && $b4 !== $b5 );
} finally {
	foreach ( (array) glob( $v496_dir . '/*' ) as $f ) { @unlink( $f ); }
	@rmdir( $v496_dir );
}

/* هیچ نسخهٔ نشانی از filemtime نیاید؛ تنها استثنا کلید حافظهٔ داخلی topersia_pmtiles_info است */
$v496_srcs = array();
foreach ( array( 'topersia-render/topersia-render.php', 'topersia-render/includes/topersia-g-zones.php', 'topersia-core/topersia-core.php', 'topersia-ai-summarizer/includes/class-tp-ai-admin.php', 'topersia-ai-summarizer/includes/class-tp-ai-frontend.php' ) as $f ) {
	$v496_srcs[ $f ] = (string) file_get_contents( __DIR__ . '/../plugin/' . $f );
}
$v496_clean = function ( $srcs ) { foreach ( $srcs as $s ) { if ( false !== strpos( $s, 'filemtime(' ) ) { return false; } } return true; };
$v496_mut = $v496_srcs; $v496_mut['topersia-core/topersia-core.php'] .= "add_query_arg( 'ver', filemtime( \$f ) );";
tp441_mut( 'نسخهٔ نشانی در render، کاشی و مرز، هسته و خلاصه‌ساز از filemtime نمی‌آید — جهش: filemtime برگردد می‌افتد', $v496_clean, $v496_srcs, $v496_mut );

/* P5-D495 — گارد؛ رفتار بسته در tests/w42-local.php */
$v496_pack = (string) file_get_contents( __DIR__ . '/../plugin/topersia-core/includes/topersia-pack.php' );
$v496_pack_ok = function ( $s ) { return false !== strpos( $s, 'function topersia_pack_uid_is_local( $uid )' ) && false !== strpos( $s, 'پست غیرنمونه با entity_uid محلی' ); };
tp441_mut( 'P5-D495: بسته هشدار «پست غیرنمونه با entity_uid محلی» می‌دهد — جهش: هشدار حذف شود می‌افتد', $v496_pack_ok, $v496_pack, str_replace( 'پست غیرنمونه با entity_uid محلی', 'x', $v496_pack ) );
$v496_uid  = (string) file_get_contents( __DIR__ . '/../scripts/tp-uid-hosts.php' );
$v496_rel2 = (string) file_get_contents( __DIR__ . '/../scripts/relay/tp-relay.php' );
$v496_read = function ( $s ) { return false === strpos( $s, 'update_post_meta' ) && false === strpos( $s, 'wpdb->update' ) && false === strpos( $s, 'wpdb->query' ) && false !== strpos( $s, "'strict' === \$mode && \$local" ); };
tp441_mut( 'P5-D495: گزارش uid فقط‌خواندنی است و حالت strict کد خروج می‌دهد — جهش: نوشتن اضافه شود می‌افتد', $v496_read, $v496_uid, $v496_uid . 'update_post_meta( 1, "entity_uid", "" );' );
t( 'P5-D495: هشدار preflight هرگز fail را زیاد نمی‌کند (OPS-D006)', false !== strpos( $v496_rel2, 'tp_uid_host_warning();' ) && 1 === preg_match( '/function tp_uid_host_warning\(\): void \{(?:(?!\$fail).)*?\n\}/su', $v496_rel2 ) );
$v496_lau = (string) file_get_contents( __DIR__ . '/../docs/AUDIT-2026-10/LAUNCH.md' );
t( 'LAUNCH: H-10 و H-11 مانع راه‌اندازی، H-12 تازه‌سازی AI', false !== strpos( $v496_lau, '| H-10 |' ) && false !== strpos( $v496_lau, '| H-11 |' ) && false !== strpos( $v496_lau, '| H-12 |' ) && false !== strpos( $v496_lau, '· H-10 · H-11 ·' ) );
