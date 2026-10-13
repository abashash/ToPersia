<?php
/**
 * به‌روزرسانی نقشهٔ آفلاین (PMTiles خودمیزبان) — دستهٔ ۳ پرامت یکپارچه · P5-D271 · P5-D352 · P5-D353
 *
 *   php scripts/tp-tiles-update.php [--dry-run] [--force] [--build=YYYYMMDD] [--bbox=w,s,e,n] [--maxzoom=14] [--cli=PATH]
 *
 * آخرین ساخت Protomaps (build.protomaps.com، فهرست از build-metadata.protomaps.dev) را با همان bbox برش می‌دهد (پیش‌فرض
 * `44,25,63.5,42.5`: شمال تا ۴۲٫۵ تا برچسب «دریای خزر» (۴۱٫۹۹°N) در فایل باشد؛ فایل فعلی تا ۴۰٫۵ بود)، می‌سنجد و فقط پس از سنجش جایگزین می‌کند:
 *   ۱ `pmtiles verify` و بازهٔ بزرگنمایی/bbox درست
 *   ۲ حجم نه کمتر از ۵۰٪ فایل فعلی
 *   ۳ شمار pois تهران (۹ کاشی z14 دور میدان آزادی) نه کمتر از ۹۰٪ فایل فعلی — شمارش از کاشی‌های MVT، بی کتابخانه
 * فایل پیشین با تاریخ ساختش در نام می‌ماند (`iran-YYYYMMDD.pmtiles`)؛ تازه `iran.pmtiles` می‌شود. ردیف `map.tiles` در «سرویس‌های خارجی» تاریخ ساخت و حجم را از
 * خودِ پرونده می‌خواند؛ نسخه (mtime) در نشانی کاشی است و کش را می‌شکند. دوره: ماهانه — مثلاً ویندوز:
 *   schtasks /Create /SC MONTHLY /D 1 /ST 03:30 /TN "ToPersia tiles update" /TR "php ...\scripts\tp-tiles-update.php"
 * (ساخته نمی‌شود؛ فقط مستند.) CLI: پروندهٔ `pmtiles` رسمی (github.com/protomaps/go-pmtiles) — مسیر از --cli، متغیر TP_PMTILES، پوشهٔ ابزارهای محلی
 * (`%LOCALAPPDATA%\topersia-tools\pmtiles`) یا PATH. دانلود بی VPN از ایران ممکن نبود ← اسکریپت با خطای روشن و کد ۳ متوقف می‌شود و هیچ فایلی عوض نمی‌شود.
 *
 * کد خروج: ۰ سالم یا به‌روز · ۱ ورودی/محیط نادرست · ۲ سنجش رد شد (فایل فعلی دست‌نخورده) · ۳ دانلود شکست خورد
 */
if ( 'cli' !== PHP_SAPI ) { exit( 1 ); }

$opt = array( 'dry-run' => false, 'force' => false, 'build' => '', 'bbox' => '44,25,63.5,42.5', 'maxzoom' => '14', 'cli' => '' );
foreach ( array_slice( $argv, 1 ) as $a ) {
	if ( preg_match( '/^--([a-z-]+)(?:=(.*))?$/', $a, $m ) && array_key_exists( $m[1], $opt ) ) { $opt[ $m[1] ] = isset( $m[2] ) ? $m[2] : true; }
}

$root  = dirname( __DIR__ );
$env   = json_decode( (string) file_get_contents( $root . '/tests/env.json' ), true );
$dir   = rtrim( (string) $env['wp'], '/\\' ) . '/wp-content/uploads/tiles/';
$cur   = $dir . 'iran.pmtiles';
$say   = function ( $s ) { echo $s . "\n"; };

/* ── CLI ───────────────────────────────────────────────────────── */
$cli = $opt['cli'] ?: getenv( 'TP_PMTILES' );
if ( ! $cli ) {
	$local = ( getenv( 'LOCALAPPDATA' ) ?: '' ) . '/topersia-tools/pmtiles/pmtiles' . ( DIRECTORY_SEPARATOR === '\\' ? '.exe' : '' );
	$cli   = is_file( $local ) ? $local : 'pmtiles';
}
$ver = (string) shell_exec( escapeshellarg( $cli ) . ' version 2>&1' );
if ( false === strpos( $ver, 'pmtiles' ) ) { $say( '❌ CLI «pmtiles» پیدا نشد: ' . $cli ); exit( 1 ); }
$say( 'CLI: ' . trim( $ver ) );

/* ── سرآیند و فراداده ──────────────────────────────────────────── */
function tpt_info( $path ) {
	$fh = @fopen( $path, 'rb' );
	if ( ! $fh ) { return array(); }
	$h = (string) fread( $fh, 127 );
	if ( strlen( $h ) < 127 || 'PMTiles' !== substr( $h, 0, 7 ) ) { fclose( $fh ); return array(); }
	$u = function ( $o ) use ( $h ) { $a = unpack( 'P', substr( $h, $o, 8 ) ); return (int) $a[1]; };
	$i = function ( $o ) use ( $h ) { $a = unpack( 'l', substr( $h, $o, 4 ) ); return $a[1] / 1e7; };
	$info = array( 'size' => filesize( $path ), 'minz' => ord( $h[100] ), 'maxz' => ord( $h[101] ), 'bbox' => array( $i( 102 ), $i( 106 ), $i( 110 ), $i( 114 ) ), 'built' => '' );
	fseek( $fh, $u( 24 ) );
	$raw  = (string) fread( $fh, min( $u( 32 ), 1048576 ) );
	$meta = json_decode( 2 === ord( $h[97] ) ? (string) gzdecode( $raw ) : $raw, true );
	if ( is_array( $meta ) && isset( $meta['planetiler:buildtime'] ) ) { $info['built'] = str_replace( '-', '', substr( $meta['planetiler:buildtime'], 0, 10 ) ); }
	fclose( $fh );
	return $info;
}

/* ── شمار ویژگی هر لایهٔ یک کاشی MVT (بی کتابخانه) ─────────────────── */
function tpt_varint( $b, &$p ) { $r = 0; $s = 0; $n = strlen( $b ); while ( $p < $n ) { $c = ord( $b[ $p++ ] ); $r |= ( $c & 0x7F ) << $s; if ( ! ( $c & 0x80 ) ) { break; } $s += 7; } return $r; }
function tpt_layer_counts( $bytes ) {
	if ( "\x1f\x8b" === substr( $bytes, 0, 2 ) ) { $bytes = (string) gzdecode( $bytes ); }
	$out = array(); $p = 0; $n = strlen( $bytes );
	while ( $p < $n ) {
		$tag = tpt_varint( $bytes, $p ); $f = $tag >> 3; $w = $tag & 7;
		if ( 2 === $w ) {
			$len = tpt_varint( $bytes, $p );
			if ( 3 === $f ) {
				$end = $p + $len; $name = ''; $cnt = 0; $q = $p;
				while ( $q < $end ) {
					$t = tpt_varint( $bytes, $q ); $ff = $t >> 3; $ww = $t & 7;
					if ( 2 === $ww ) { $l = tpt_varint( $bytes, $q ); if ( 1 === $ff ) { $name = substr( $bytes, $q, $l ); } elseif ( 2 === $ff ) { $cnt++; } $q += $l; }
					elseif ( 0 === $ww ) { tpt_varint( $bytes, $q ); }
					elseif ( 5 === $ww ) { $q += 4; } elseif ( 1 === $ww ) { $q += 8; } else { break; }
				}
				$out[ $name ] = $cnt;
			}
			$p += $len;
		} elseif ( 0 === $w ) { tpt_varint( $bytes, $p ); } elseif ( 5 === $w ) { $p += 4; } elseif ( 1 === $w ) { $p += 8; } else { break; }
	}
	return $out;
}
function tpt_tehran_pois( $cli, $file ) {
	$z = 14; $lat = 35.70; $lon = 51.337;   /* میدان آزادی */
	$x0 = (int) floor( ( $lon + 180 ) / 360 * pow( 2, $z ) );
	$y0 = (int) floor( ( 1 - log( tan( deg2rad( $lat ) ) + 1 / cos( deg2rad( $lat ) ) ) / M_PI ) / 2 * pow( 2, $z ) );
	$sum = 0; $tiles = 0;
	for ( $dx = -1; $dx <= 1; $dx++ ) {
		for ( $dy = -1; $dy <= 1; $dy++ ) {
			$tmp = tempnam( sys_get_temp_dir(), 'tpt' );
			exec( escapeshellarg( $cli ) . ' tile ' . escapeshellarg( $file ) . ' ' . $z . ' ' . ( $x0 + $dx ) . ' ' . ( $y0 + $dy ) . ' > ' . escapeshellarg( $tmp ) . ' 2>&1' );
			$b = (string) file_get_contents( $tmp );
			@unlink( $tmp );
			if ( strlen( $b ) > 10 ) { $c = tpt_layer_counts( $b ); $sum += isset( $c['pois'] ) ? $c['pois'] : 0; $tiles++; }
		}
	}
	return array( 'pois' => $sum, 'tiles' => $tiles );
}

/* ── وضع فعلی و آخرین ساخت ─────────────────────────────────────── */
$have = tpt_info( $cur );
$say( 'فایل فعلی: ' . ( $have ? sprintf( '%s · %.0f MB · ساخت %s · z%d–%d · bbox %s', basename( $cur ), $have['size'] / 1048576, $have['built'] ?: '—', $have['minz'], $have['maxz'], implode( ',', $have['bbox'] ) ) : 'نیست' ) );

if ( '--selftest' === ( isset( $argv[1] ) ? $argv[1] : '' ) ) { $r = tpt_tehran_pois( $cli, $cur ); $say( 'شمار pois تهران در فایل فعلی: ' . json_encode( $r ) ); exit( 0 ); }

$ctx    = stream_context_create( array( 'http' => array( 'timeout' => 30, 'header' => "User-Agent: ToPersia-tiles-update/1.0\r\n" ) ) );
$builds = json_decode( (string) @file_get_contents( 'https://build-metadata.protomaps.dev/builds.json', false, $ctx ), true );
$key    = '';
if ( is_array( $builds ) && $builds ) { $last = end( $builds ); $key = isset( $last['key'] ) ? $last['key'] : ''; }
if ( $opt['build'] ) { $key = $opt['build'] . '.pmtiles'; }
if ( ! preg_match( '/^\d{8}\.pmtiles$/', $key ) ) { $say( '❌ فهرست ساخت‌ها خوانده نشد (build-metadata.protomaps.dev)؛ اتصال؟' ); exit( 3 ); }
$new_date = substr( $key, 0, 8 );
$say( 'آخرین ساخت Protomaps: ' . $key );

if ( $have && ! $opt['force'] && '' !== $have['built'] && $have['built'] >= $new_date && abs( $have['bbox'][3] - (float) explode( ',', $opt['bbox'] )[3] ) < 0.01 ) { $say( '✅ فایل فعلی به‌روز است (ساخت ' . $have['built'] . ').' ); exit( 0 ); }

$url  = 'https://build.protomaps.com/' . $key;
$part = $dir . 'iran-' . $new_date . '.pmtiles.part';
$cmd  = escapeshellarg( $cli ) . ' extract ' . escapeshellarg( $url ) . ' ' . escapeshellarg( $part ) . ' --bbox=' . escapeshellarg( $opt['bbox'] ) . ' --maxzoom=' . (int) $opt['maxzoom'];
if ( $opt['dry-run'] ) { $say( 'dry-run: ' . $cmd . ' --dry-run' ); passthru( $cmd . ' --dry-run', $code ); exit( 0 === $code ? 0 : 3 ); }

$say( 'برش: ' . $opt['bbox'] . ' تا z' . $opt['maxzoom'] . ' ← ' . basename( $part ) );
passthru( $cmd, $code );
if ( 0 !== $code || ! is_file( $part ) ) { @unlink( $part ); $say( '❌ دانلود/برش شکست خورد (کد ' . $code . ') — فایل فعلی دست‌نخورده.' ); exit( 3 ); }

/* ── سنجش ──────────────────────────────────────────────────────── */
$ni = tpt_info( $part );
exec( escapeshellarg( $cli ) . ' verify ' . escapeshellarg( $part ) . ' 2>&1', $vout, $vcode );
$pb = $have ? tpt_tehran_pois( $cli, $cur ) : array( 'pois' => 0 );
$pa = tpt_tehran_pois( $cli, $part );
$ok = array(
	'verify' => 0 === $vcode,
	'zoom'   => $ni && $ni['minz'] <= 0 && $ni['maxz'] >= 14,
	'bbox'   => $ni && $ni['bbox'][3] >= 42.0,
	'size'   => ! $have || $ni['size'] >= 0.5 * $have['size'],
	'pois'   => $pa['pois'] >= 0.9 * $pb['pois'],
);
$say( sprintf( 'سنجش: %s · pois تهران پیش %d ← پس %d · حجم %.0f ← %.0f MB', json_encode( $ok ), $pb['pois'], $pa['pois'], $have ? $have['size'] / 1048576 : 0, $ni ? $ni['size'] / 1048576 : 0 ) );
if ( in_array( false, $ok, true ) ) { $say( '❌ سنجش رد شد — فایل فعلی دست‌نخورده؛ فایل تازه: ' . basename( $part ) ); exit( 2 ); }

/* ── جایگزینی: پیشین با تاریخ ساختش می‌ماند ─────────────────────── */
if ( $have ) {
	$arch = $dir . 'iran-' . ( $have['built'] ?: date( 'Ymd', filemtime( $cur ) ) ) . '.pmtiles';
	if ( ! is_file( $arch ) ) { rename( $cur, $arch ); $say( 'آرشیو: ' . basename( $arch ) ); }
}
rename( $part, $cur );
$say( '✅ جایگزین شد: ' . basename( $cur ) . ' (ساخت ' . $ni['built'] . ')' );
exit( 0 );
