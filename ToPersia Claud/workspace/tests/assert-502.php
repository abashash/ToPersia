<?php
/**
 * P5-D502 و کوچک‌های دستهٔ 43: آستانهٔ تازگی، نرمال‌سازی ver= در lighthouse.js (بی اجرای Lighthouse)، حافظهٔ هش ver.
 * از assert.php پس از assert-42 بار می‌شود؛ t و tp441_mut از آن‌جاست.
 */
echo "\nP5-D502 — آستانه‌ها، lighthouse و هش ver\n";

/* آستانه: مرز هر نوع، با پست آزمون 8301 که assert-42 می‌سازد */
$p502 = array( 'tp_health_safety' => 180, 'tp_transport' => 90, 'tp_calendar' => 365, 'tp_currency' => 30, 'tp_visa' => 90 );
t( 'P5-D502: پیش‌فرض آستانه‌ها سلامت ۱۸۰، حمل‌ونقل ۹۰، تقویم ۳۶۵، ارز ۳۰، روادید ۹۰', $p502 == topersia_render_stale_types(), wp_json_encode( topersia_render_stale_types() ) );
$p502_now = strtotime( '2026-11-01 12:00:00 UTC' );
foreach ( $p502 as $type => $days ) {
	$iso = function ( $d ) use ( $p502_now ) { return gmdate( 'Y-m-d', $p502_now - $d * 86400 ); };
	t( "P5-D502: {$type} در روز {$days} تازه، در روز " . ( $days + 1 ) . ' قدیمی — جهش: یک روز دیرتر',
		! topersia_g_is_stale( $iso( $days ), $days, $p502_now ) && topersia_g_is_stale( $iso( $days + 1 ), $days, $p502_now ) && ! topersia_g_is_stale( $iso( $days + 1 ), 0, $p502_now ) );
}
$p502_set = (string) file_get_contents( __DIR__ . '/../plugin/topersia-render/includes/topersia-settings.php' );
$p502_ok  = function ( $s ) { return false !== strpos( $s, "'tp_health_safety' => 180, 'tp_transport' => 90, 'tp_calendar' => 365" ); };
tp441_mut( 'P5-D502: پیش‌فرض در تنظیمات هستهٔ نمایش — جهش: خاموش', $p502_ok, $p502_set, str_replace( "'tp_health_safety' => 180", "'tp_health_safety' => 0", $p502_set ) );

/* LAUNCH */
$p502_l = (string) file_get_contents( __DIR__ . '/../docs/AUDIT-2026-10/LAUNCH.md' );
t( 'LAUNCH H-1 شرط «TTL کش صفحه ≤ ۷ روز» را دارد', 1 === preg_match( '/\| H-1 \|[^\n]*TTL کش صفحه ≤ ۷ روز/u', $p502_l ) );

/* نرمال‌سازی ver= */
$p502_js = function ( $body ) {
	$f = tempnam( sys_get_temp_dir(), 'lh' ) . '.js';
	file_put_contents( $f, $body );
	$o = (string) shell_exec( 'node ' . escapeshellarg( $f ) . ' 2>&1' );
	@unlink( $f );
	return json_decode( $o, true );
};
$p502_lib = (string) file_get_contents( __DIR__ . '/lib/lh-compare.js' );
$p502_cases = <<<'JS'
const { compareRequests: c } = M;
const r = [];
const U = 'd GET http://t/a.css';
const v = c( [ U + '?ver=aa', 'd GET http://t/b' ], [ 'd GET http://t/b', U + '?ver=bb' ] );
r.push( v.equal && 1 === v.normalized.length && v.normalized[0].a === U + '?ver=aa' );
r.push( ! c( [ U + '?ver=aa' ], [ U ] ).equal );
r.push( ! c( [ U + '?v=aa' ], [ U + '?v=bb' ] ).equal );
r.push( c( [ U + '?x=1&ver=aa&y=2' ], [ U + '?x=1&ver=bb&y=2' ] ).equal );
r.push( ! c( [ U + '?x=1&ver=aa' ], [ U + '?x=2&ver=aa' ] ).equal );
r.push( ! c( [ U + '?ver=a', U + '?ver=b' ], [ U + '?ver=a' ] ).equal );
r.push( ! c( [ U + '?ver=a range=0-1' ], [ U + '?ver=b range=2-3' ] ).equal );
r.push( ! c( [ U + '?server=1' ], [ U + '?server=2' ] ).equal );
r.push( 0 === c( [ U + '?ver=a' ], [ U + '?ver=a' ] ).normalized.length );
console.log( JSON.stringify( r ) );
JS;
$p502_lh = function ( $lib ) use ( $p502_js, $p502_cases ) {
	$r = $p502_js( "const M = ( () => { const module = { exports: {} };\n{$lib}\nreturn module.exports; } )();\n{$p502_cases}" );
	return is_array( $r ) && 9 === count( $r ) && ! in_array( false, $r, true );
};
tp441_mut( 'lh-compare: فقط ver= نرمال می‌شود و گزارش می‌شود؛ ?v= و server= و range و شمار تکرار تفاوت واقعی‌اند — جهش: نرمال‌سازی هر پارامتر', $p502_lh, $p502_lib,
	str_replace( '/([?&])ver=[^&\s]*/g', '/([?&])\w*ver=[^&\s]*/g', $p502_lib ) );
$p502_src = (string) file_get_contents( __DIR__ . '/lighthouse.js' );
$p502_wired = function ( $s ) { return false !== strpos( $s, "require( './lib/lh-compare.js' )" ) && false !== strpos( $s, 'compareRequests( Object.keys( sets[0].store.m.entries )' ) && false !== strpos( $s, 'process.exit( 1 )' ) && false !== strpos( $s, 'نرمال شد' ); };
tp441_mut( 'lighthouse.js پیش از اجرا مقایسه می‌کند، با تفاوت واقعی خارج می‌شود و می‌نویسد چه چیزی نرمال شد — جهش: حذف مقایسه', $p502_wired, $p502_src, str_replace( 'compareRequests( Object.keys( sets[0].store.m.entries )', '( Object.keys( sets[0].store.m.entries )', $p502_src ) );

/* حافظهٔ هش ver: md5 تکراری در یک درخواست نیست؛ ver همچنان از محتوا */
$p502_z = (string) file_get_contents( __DIR__ . '/../plugin/topersia-render/includes/topersia-g-zones.php' );
$p502_memo = function ( $s ) { return (bool) preg_match( '/function topersia_g_file_ver\( \$file \) \{\s*static \$memo = array\(\);/', $s ); };
tp441_mut( 'topersia_g_file_ver حافظهٔ درخواستی دارد — جهش: بی حافظه', $p502_memo, $p502_z, str_replace( "function topersia_g_file_ver( \$file ) {\n\n\t\tstatic \$memo = array();", "function topersia_g_file_ver( \$file ) {\n\n\t\t\$memo = array();", $p502_z ) );
$p502_f = tempnam( sys_get_temp_dir(), 'ver' );
file_put_contents( $p502_f, 'a' );
$p502_v1 = topersia_g_file_ver( $p502_f );
file_put_contents( $p502_f, 'b' );
clearstatcache();
t( 'همان مسیر در یک درخواست از حافظه می‌آید و md5 دوباره نمی‌شود', $p502_v1 === topersia_g_file_ver( $p502_f ) );
@unlink( $p502_f );

$p502_r = (string) file_get_contents( __DIR__ . '/../plugin/topersia-render/topersia-render.php' );
t( 'render هش را از حافظهٔ هسته می‌گیرد تا یک فایل دو بار هش نشود', false !== strpos( $p502_r, "function_exists( 'topersia_asset_hash_ver' )" ) );
$p502_af = (string) file_get_contents( __DIR__ . '/../plugin/topersia-ai-summarizer/includes/class-tp-ai-frontend.php' ) . (string) file_get_contents( __DIR__ . '/../plugin/topersia-ai-summarizer/includes/class-tp-ai-admin.php' );
t( 'خلاصه‌ساز هش را فقط از یک تابع با حافظه می‌گیرد', 1 === substr_count( $p502_af, 'function tp_ai_asset_ver( $file )' ) && 1 === substr_count( $p502_af, 'md5_file(' ) );

/* P5-D500: جدول بلند ستون موبایل را باز نکند؛ رفتار در tests/overflow-h.js */
$p500 = (string) file_get_contents( __DIR__ . '/../plugin/topersia-render/assets/topersia-g-template.css' );
$p500_ok = function ( $s ) { return false !== strpos( $s, '.tp-content-grid > * > *{min-width:0}' ); };
tp441_mut( 'P5-D500: فرزند ستون محتوا min-width:0 دارد — جهش: حذف قاعده', $p500_ok, $p500, str_replace( '.tp-content-grid > * > *{min-width:0}', '.x{}', $p500 ) );
