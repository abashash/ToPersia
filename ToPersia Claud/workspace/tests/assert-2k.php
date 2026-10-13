<?php
/**
 * ادعاهای دستهٔ ۳ (۱۳ اکتبر) — نقشهٔ آنلاین با پشتیبان آفلاین، سبک‌ها، به‌روزرسانی ماهانه، sprite تیره  P5-D353
 * از assert.php پس از assert-2j.php بار می‌شود؛ PHP خالص، هر کدام با جهش. رفتار در مرورگر: tests/map-3.js (کروم و فایرفاکس).
 */

$js2k  = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-zones.js' );
$svc2k = file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-services.php' );
$zn2k  = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );
$upd2k = file_get_contents( dirname( TP_PLUGIN_DIR, 2 ) . '/scripts/tp-tiles-update.php' );

echo "\nدستهٔ ۳ (۱۳ اکتبر) — نقشهٔ آنلاین با پشتیبان آفلاین، سبک‌ها، به‌روزرسانی ماهانه  P5-D353\n" . str_repeat( '─', 78 ) . "\n";

tp441_mut( 'سرویس map.tiles: پیش‌فرض آنلاین اول (OpenFreeMap بی کلید، تجاری مجاز)، PMTiles پشتیبان؛ ماهواره فقط کلیددار و خاموش؛ ردیف PMTiles تاریخ ساخت و حجم را از خودِ پرونده می‌خواند',
	function ( $x ) {
		return 1 === preg_match( "/'map\.tiles'\s*=> array\( 'label' => 'کاشی نقشه', 'purpose' => '[^']*', 'provider' => 'openfreemap', 'enabled' => true \)/u", $x )
			&& 1 === preg_match( "/'openfreemap'\s*=> array\( 'service' => 'map\.tiles'[^\n]*'needs_key' => false[^\n]*'free_commercial' => true[^\n]*'probe_ms' => '2500'/u", $x )
			&& 1 === preg_match( "/'maptiler-sat'\s*=> array\( 'service' => 'map\.satellite'[^\n]*'needs_key' => true/u", $x )
			&& 1 === preg_match( "/'map\.satellite'\s*=> array\( 'label' => 'ماهواره'[^\n]*'enabled' => false \)/u", $x )
			&& false !== strpos( $x, 'function topersia_pmtiles_info( $path )' ) && false !== strpos( $x, "'planetiler:buildtime'" ) && false !== strpos( $x, "'pmtiles' === \$cfg['provider']" );
	},
	$svc2k, str_replace( "'provider' => 'openfreemap', 'enabled' => true", "'provider' => 'pmtiles', 'enabled' => true", $svc2k ) );

tp441_mut( 'نقشه: یک درخواست آزمایشی (خودِ style آنلاین) با AbortController و مهلت probe_ms (۲٫۵ ثانیه)؛ خطا، مهلت یا غیر ۲۰۰ ← آفلاین و sessionStorage=۰؛ تلاش دوباره ندارد؛ آفلاین‌اول آنلاین نمی‌پرسد',
	function ( $x ) {
		return false !== strpos( $x, "var ONLINE_KEY = 'tp-map-online';" ) && false !== strpos( $x, "'0' === sget( ONLINE_KEY )" ) && false !== strpos( $x, "'offline-first' === cfg.order" )
			&& false !== strpos( $x, 'new AbortController()' ) && false !== strpos( $x, "parseInt( o.probe_ms, 10 ) || 2500" ) && false !== strpos( $x, "if ( ! r.ok ) { throw new Error( 'http' ); }" )
			&& false !== strpos( $x, "sset( ONLINE_KEY, '0' );" ) && 1 === substr_count( $x, 'window.fetch( url, { signal:' ) && false === strpos( $x, 'retry' )
			&& false !== strpos( $x, "function fallBack() {" ) && false !== strpos( $x, 'if ( fell || ! box.tpOnline ) {' );
	},
	$js2k, str_replace( "if ( ! r.ok ) { throw new Error( 'http' ); }", '', $js2k ) );

tp441_mut( 'نقشه: هیچ درخواست آنلاین پیش از دیده‌شدن — نقشه فقط در mount (IntersectionObserver برای کارت، کلیک برای مودال) ساخته می‌شود؛ style آنلاین فقط در resolveStyle',
	function ( $x ) {
		return false === strpos( $x, "fetch( cfg.online" ) && false === strpos( $x, 'nominatim' ) && substr_count( $x, 'onlineStyle( cfg )' ) >= 2
			&& false !== strpos( $x, "var io = new IntersectionObserver( function ( entries ) {" ) && false !== strpos( $x, "e.target.closest( '[data-tp-map-open]' )" )
			&& false !== strpos( $x, 'return resolveStyle( cfg, interactive ? savedSel( cfg ) : \'base\' ).then(' );
	},
	$js2k, $js2k . " fetch( cfg.online.style_light )" );

tp441_mut( 'سبک‌ها: پایه · حمل‌ونقل و قطار (آفلاین؛ خط آهن #B9871A و جاده کم‌رنگ، ایستگاه و فرودگاه برجسته) · ماهواره (فقط با cfg.sat کلیددار، برچسب‌های خودِ PMTiles روی تصویر)؛ انتخاب در localStorage؛ هر سبک فقط هنگام انتخاب',
	function ( $x ) {
		return false !== strpos( $x, "'transit' === kind" ) && false !== strpos( $x, "'line-color': '#B9871A'" ) && false !== strpos( $x, "'line-opacity': 0.3" ) && false !== strpos( $x, "[ 'literal', [ 'station', 'aerodrome' ] ]" )
			&& false !== strpos( $x, "if ( 'satellite' === sel && cfg.sat ) {" ) && false !== strpos( $x, "'labels' === kind" ) && false !== strpos( $x, "src.sat = { type: 'raster', tiles: [ cfg.sat ]" )
			&& false !== strpos( $x, "var STYLE_KEY  = 'tp-map-style';" ) && false !== strpos( $x, "window.localStorage.setItem( STYLE_KEY, v )" ) && false !== strpos( $x, 'applySel( v );' );
	},
	$js2k, str_replace( "'line-color': '#B9871A'", "'line-color': '#ff0000'", $js2k ) );

tp441_mut( 'پیکربندی سرور: order (آنلاین‌اول/آفلاین‌اول از ارائه‌دهنده)، styles، online؛ ماهواره فقط وقتی سرویس map.satellite فعال با کلید؛ انتخاب‌گر سبک در مودال؛ کاشی آفلاین با نسخه (mtime) در نشانی',
	function ( $x ) {
		return false !== strpos( $x, "'order'       => 'pmtiles' === \$prov ? 'offline-first' : 'online-first'," ) && false !== strpos( $x, "'styles'      => \$have ? array( 'base', 'transit' ) : array( 'base' )," )
			&& false !== strpos( $x, "'active' === \$sat['status'] && ! empty( \$sat['key'] )" ) && false !== strpos( $x, "\$cfg['styles'][] = 'satellite';" )
			&& false !== strpos( $x, "'?v=' . filemtime( \$file )" ) && false !== strpos( $x, 'function topersia_g_map_style_bar( $post_id )' ) && false !== strpos( $x, 'data-tp-map-styles role="group"' )
			&& false !== strpos( $x, "topersia_api( 'map.tiles', false, true )" );
	},
	$zn2k, str_replace( "'order'       => 'pmtiles' === \$prov ? 'offline-first' : 'online-first',", '', $zn2k ) );

/* ── sprite تیره از منبع رسمی ─────────────────────────────────────── */
$sp2k = TP_PLUGIN_DIR . '/assets/map/sprites/';
$lj   = json_decode( (string) @file_get_contents( $sp2k . 'light.json' ), true );
$dj   = json_decode( (string) @file_get_contents( $sp2k . 'dark.json' ), true );
tp441_mut( 'sprite تیره: dark.json و dark@2x و PNGها از Protomaps basemaps-assets v4 خودمیزبان، همان نام نمادها و ابعاد light؛ مجوز؛ سبک آنلاین/آفلاین تیره sprite تیره می‌خواند',
	function ( $x ) use ( $sp2k ) {
		return is_array( $x[0] ) && is_array( $x[1] ) && array_keys( $x[0] ) === array_keys( $x[1] )
			&& is_file( $sp2k . 'dark.png' ) && is_file( $sp2k . 'dark@2x.png' ) && is_file( $sp2k . 'dark@2x.json' ) && is_file( dirname( $sp2k ) . '/LICENSE-sprites.txt' )
			&& "\x89PNG" === substr( (string) file_get_contents( $sp2k . 'dark.png' ), 0, 4 )
			&& false !== strpos( $x[2], "sprite: base + 'sprites/' + ( 'dark' === flavorOf() ? 'dark' : 'light' )," );
	},
	array( $lj, $dj, $js2k ), array( $lj, array_slice( (array) $dj, 1 ), str_replace( "'dark' === flavorOf() ? 'dark' : 'light'", "'light'", $js2k ) ) );

/* ── به‌روزرسانی ماهانه ───────────────────────────────────────────── */
tp441_mut( 'tp-tiles-update: آخرین ساخت Protomaps با bbox تا ۴۲٫۵°N (دریای خزر) و z14؛ سنجش verify + بازه + حجم + شمار pois تهران (۹ کاشی) پیش از جایگزینی؛ پیشین با تاریخ ساخت می‌ماند؛ دانلود شکسته ← کد ۳ و بی تغییر',
	function ( $x ) {
		return false !== strpos( $x, "'bbox' => '44,25,63.5,42.5'" ) && false !== strpos( $x, "' verify '" ) && false !== strpos( $x, "\$pa['pois'] >= 0.9 * \$pb['pois']" ) && false !== strpos( $x, "\$ni['size'] >= 0.5 * \$have['size']" )
			&& false !== strpos( $x, "'iran-' . ( \$have['built']" ) && false !== strpos( $x, "exit( 3 )" ) && false !== strpos( $x, "exit( 2 )" ) && false !== strpos( $x, 'function tpt_layer_counts( $bytes )' )
			&& false !== strpos( $x, 'build-metadata.protomaps.dev/builds.json' ) && false !== strpos( $x, '--maxzoom=' ) && false !== strpos( $x, 'schtasks /Create /SC MONTHLY' );
	},
	$upd2k, str_replace( "\$pa['pois'] >= 0.9 * \$pb['pois']", 'true', $upd2k ) );

tp441_mut( 'نقشه: برچسب‌ها و دکمه‌های سبک در کاتالوگ سه‌زبانه (پایه · حمل‌ونقل و قطار · ماهواره · سبک نقشه)',
	function ( $x ) {
		foreach ( array( "'پایه'", "'حمل‌ونقل و قطار'", "'ماهواره'", "'سبک نقشه'" ) as $k ) {
			if ( false === strpos( $x, $k ) ) { return false; }
		}
		return true;
	},
	file_get_contents( dirname( TP_PLUGIN_DIR, 2 ) . '/scripts/i18n/topersia-render.php' ), "'x'" );
