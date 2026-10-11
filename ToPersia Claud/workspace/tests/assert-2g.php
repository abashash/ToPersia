<?php
/**
 * ادعاهای کیفیت هوا (P5-D345 · P5-D346) — از assert.php پس از assert-2f.php بار می‌شود؛ PHP خالص با stub، هر کدام با جهش.
 * رفتار روی وردپرس واقعی (کش مرکزی، REST، پرچم تجاری، سه زبان): tests/air-local.php.
 */

$air2g  = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-air.php' );
$js2g   = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-zones.js' );
$css2g  = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-template.css' );
$tok2g  = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-tokens.css' );
$zones2g = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );

echo "\nفاز ۳ (بخشی، پیش از ۲د) — کیفیت هوا زیر کارت آب‌وهوا  P5-D345 · P5-D346\n" . str_repeat( '─', 78 ) . "\n";

/* ── رده‌ها ───────────────────────────────────────────────────────────── */
$cats2g = array_map( 'topersia_g_aqi_cat', array( 0, 50, 51, 100, 101, 150, 151, 200, 201, 300, 301, 500 ) );
tp441_mut( 'US AQI: شش رده با حدّ EPA — ۰–۵۰ · ۵۱–۱۰۰ · ۱۰۱–۱۵۰ · ۱۵۱–۲۰۰ · ۲۰۱–۳۰۰ · ۳۰۱+',
	function ( $x ) { return array( 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6 ) === $x; },
	$cats2g, array( 1, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6 ) );

/* برچسب‌ها: سه زبان، هیچ تکهٔ فارسی در en و ar */
$exp2g = array(
	'fa' => array( 'خوب', 'متوسط', 'ناسالم برای گروه‌های حساس', 'ناسالم', 'بسیار ناسالم', 'خطرناک' ),
	'en' => array( 'Good', 'Moderate', 'Unhealthy for sensitive groups', 'Unhealthy', 'Very unhealthy', 'Hazardous' ),
	'ar' => array( 'جيد', 'معتدل', 'غير صحي للفئات الحساسة', 'غير صحي', 'غير صحي جدًا', 'خطير' ),
);
$lab2g = array();
foreach ( array( 'fa', 'en', 'ar' ) as $lang ) {
	$lab2g[ $lang ] = tp442_lang( $lang, function () {
		return array_map( 'topersia_g_aqi_label', array( 1, 2, 3, 4, 5, 6 ) );
	} );
}
tp441_mut( 'برچسب شش ردهٔ AQI در سه زبان از کاتالوگ افزونه — fa · en · ar؛ در en و ar هیچ حرف فارسی نیست',
	function ( $x ) use ( $exp2g ) {
		return $exp2g === $x && ! preg_match( '/[پچژگ]/u', implode( '', $x['en'] ) . implode( '', $x['ar'] ) );
	},
	$lab2g, array_merge( $lab2g, array( 'en' => $lab2g['fa'] ) ) );

/* ── سلول و دریافت (با جایگزین HTTP) ─────────────────────────────────── */
$cell2g = array( topersia_g_aq_cell( 32.33, 52.78 ), topersia_g_aq_cell( 32.2, 52.7 ), topersia_g_aq_cell( 32.4, 52.78 ) );
tp441_mut( 'کلید کش = سلول ۰٫۲۵ درجه: نقطه‌های نزدیک یک سلول‌اند، دورتر نه',
	function ( $x ) { return array( '32.25', '52.75' ) === $x[0] && $x[0] === $x[1] && $x[0] !== $x[2]; },
	$cell2g, array( $cell2g[0], $cell2g[0], $cell2g[0] ) );

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error { private $c; private $m; function __construct( $c = '', $m = '', $d = null ) { $this->c = $c; $this->m = $m; } function get_error_code() { return $this->c; } function get_error_message() { return $this->m; } }
}
if ( ! function_exists( 'is_wp_error' ) ) { function is_wp_error( $v ) { return $v instanceof WP_Error; } }
if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args = array() ) { $GLOBALS['tp_http_log'][] = $url; return isset( $GLOBALS['tp_http'] ) ? $GLOBALS['tp_http'] : new WP_Error( 'x', 'x' ); }
	function wp_remote_retrieve_response_code( $r ) { return is_array( $r ) ? $r['code'] : 0; }
	function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? $r['body'] : ''; }
}
$GLOBALS['tp_http_log'] = array();
$GLOBALS['tp_http'] = array( 'code' => 200, 'body' => '{"current":{"time":"2026-10-11T23:00","interval":3600,"us_aqi":79,"pm10":31.44,"pm2_5":12.66,"dust":72.0}}' );
$ok2g = topersia_g_aq_fetch( array( '32.25', '52.75' ) );
$GLOBALS['tp_http'] = array( 'code' => 200, 'body' => '{"current":{"time":"x","pm10":3}}' );
$shape2g = topersia_g_aq_fetch( array( '32.25', '52.75' ) );
$GLOBALS['tp_http'] = array( 'code' => 500, 'body' => '' );
$up2g = topersia_g_aq_fetch( array( '32.25', '52.75' ) );
$url2g = $GLOBALS['tp_http_log'][0];
tp441_mut( 'دریافت: endpoint و پارامترها عیناً مستند رسمی (air-quality-api.open-meteo.com/v1/air-quality · latitude · longitude · current=us_aqi,pm10,pm2_5,dust)؛ شاخص و ذره‌ها خوانده می‌شود؛ شکل ناجور ← ۵۰۲، خطای بالادست ← ۵۰۳',
	function ( $x ) {
		return 79 === $x[0]['aqi'] && 12.7 === $x[0]['pm25'] && 31.4 === $x[0]['pm10'] && 72.0 === $x[0]['dust']
			&& is_wp_error( $x[1] ) && 'tp_aq_shape' === $x[1]->get_error_code() && is_wp_error( $x[2] ) && 'tp_aq_upstream' === $x[2]->get_error_code()
			&& 0 === strpos( $x[3], 'https://air-quality-api.open-meteo.com/v1/air-quality' )
			&& false !== strpos( $x[3], 'latitude=32.25' ) && false !== strpos( $x[3], 'longitude=52.75' ) && false !== strpos( $x[3], 'current=us_aqi' ) && false !== strpos( $x[3], 'pm10' ) && false !== strpos( $x[3], 'dust' )
			&& false === strpos( $x[3], 'apikey' );
	},
	array( $ok2g, $shape2g, $up2g, $url2g ), array( array_merge( (array) $ok2g, array( 'aqi' => 0 ) ), $shape2g, $up2g, $url2g ) );

/* ── REST و خاموشی ───────────────────────────────────────────────────── */
if ( ! function_exists( 'rest_ensure_response' ) ) { function rest_ensure_response( $v ) { return $v; } }
if ( ! function_exists( 'topersia_geo_point' ) ) { /* هسته در این مجموعه بار می‌شود (assert-2e) */ }
class TP2G_Req { public $p; function __construct( $p ) { $this->p = $p; } function get_param( $k ) { return isset( $this->p[ $k ] ) ? $this->p[ $k ] : null; } }
if ( ! function_exists( 'topersia_api' ) ) {
	/* همان قرارداد هسته: status · enabled — فقط برای این ادعاها؛ `configured` تهی تا لایهٔ هوا (topersia_g_layer) را عوض نکند */
	function topersia_api( $service ) { return array( 'service' => $service, 'configured' => false, 'status' => isset( $GLOBALS['tp2g_status'] ) ? $GLOBALS['tp2g_status'] : 'active', 'enabled' => true, 'key' => '' ); }
}
$GLOBALS['tp_http'] = array( 'code' => 200, 'body' => '{"current":{"time":"t","us_aqi":160,"pm10":31.44,"pm2_5":12.66,"dust":72.0}}' );
$GLOBALS['tp2g_status'] = 'active';
$rest_ok  = topersia_g_aq_rest( new TP2G_Req( array( 'lat' => '32.33', 'lng' => '52.78' ) ) );
$rest_out = topersia_g_aq_rest( new TP2G_Req( array( 'lat' => '48.85', 'lng' => '2.35' ) ) );
$rest_bad = topersia_g_aq_rest( new TP2G_Req( array( 'lat' => 'x', 'lng' => '2' ) ) );
$GLOBALS['tp2g_status'] = 'off';          /* سایت تجاری و بی کلید پولی: هسته status=off می‌دهد */
$rest_off = topersia_g_aq_rest( new TP2G_Req( array( 'lat' => '32.33', 'lng' => '52.78' ) ) );
$GLOBALS['tp2g_status'] = 'nokey';
$rest_nok = topersia_g_aq_rest( new TP2G_Req( array( 'lat' => '32.33', 'lng' => '52.78' ) ) );
$GLOBALS['tp2g_status'] = 'error';        /* آخرین فراخوانی ناموفق خاموشی نیست */
$rest_err = topersia_g_aq_rest( new TP2G_Req( array( 'lat' => '32.33', 'lng' => '52.78' ) ) );
$GLOBALS['tp2g_status'] = 'active';
tp441_mut( 'REST هوا: پاسخ aqi · cat (۴ برای ۱۶۰) · label · pm25 · pm10 · dust · model؛ مختصات نامعتبر یا بیرون از ایران ← ۴۰۰؛ خاموش (off · nokey) ← ۵۰۳ «tp_aq_off»؛ error دوباره آزموده می‌شود',
	function ( $x ) {
		$code = function ( $e ) { return is_wp_error( $e ) ? $e->get_error_code() : ''; };
		return 160 === $x[0]['aqi'] && 4 === $x[0]['cat'] && 'ناسالم' === $x[0]['label'] && 12.7 === $x[0]['pm25'] && 'برآورد مدل CAMS' === $x[0]['model']
			&& 'tp_aq_coords' === $code( $x[1] ) && 'tp_aq_coords' === $code( $x[2] ) && 'tp_aq_off' === $code( $x[3] ) && 'tp_aq_off' === $code( $x[4] ) && is_array( $x[5] )
			&& false === strpos( wp_json_encode( $x[0] ), 'apikey' );
	},
	array( $rest_ok, $rest_out, $rest_bad, $rest_off, $rest_nok, $rest_err ), array( $rest_ok, $rest_ok, $rest_bad, $rest_ok, $rest_nok, $rest_err ) );

/* ── جعبه در صفحه ────────────────────────────────────────────────────── */
$f2g_keep = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'] );
$GLOBALS['tp_posts'][8700] = array( 'type' => 'tp_water_body', 'title' => 'تالاب آزمون هوا', 'url' => 'https://topersia.com/p8700/' );
$GLOBALS['tp_posts'][8701] = array( 'type' => 'tp_country', 'title' => 'کشور آزمون هوا', 'url' => 'https://topersia.com/p8701/' );
$GLOBALS['tp_posts'][8702] = array( 'type' => 'tp_water_body', 'title' => 'تالاب بی مختصات', 'url' => 'https://topersia.com/p8702/' );
$GLOBALS['tp_meta'][8700] = array( 'geo_coordinates' => '32°20′N, 52°47′E', 'overview_text' => '<p>x</p>' );
$GLOBALS['tp_meta'][8701] = array( 'geo_coordinates' => '32.4, 53.7', 'overview_text' => '<p>x</p>' );
$GLOBALS['tp_meta'][8702] = array( 'overview_text' => '<p>x</p>' );
$air_box     = r( 8700, 'tp_air' );
$air_country = r( 8701, 'tp_air' );
$air_nocoord = r( 8702, 'tp_air' );
$GLOBALS['tp2g_status'] = 'off';
$air_off = r( 8700, 'tp_air' );
$GLOBALS['tp2g_status'] = 'active';
$air_page = r( 8700, 'tp_page' );
$air_en   = tp442_lang( 'en', function () { return r( 8700, 'tp_air' ); } );
$GLOBALS['tp_posts'] = $f2g_keep[0];
$GLOBALS['tp_meta']  = $f2g_keep[1];
tp441_mut( 'جعبهٔ کیفیت هوا: اسکلتِ هم‌ساختار و بی هیچ عدد (فقط «—»، پر شدن پس از بارگذاری)، مختصات نرمال‌شدهٔ DMS، «برآورد مدل CAMS» و انتساب «داده‌های هوا: MET Norway · کیفیت هوا: CAMS، Open-Meteo»؛ کشور و بی‌مختصات و خاموش ندارند',
	function ( $x ) {
		return false !== strpos( $x[0], '<div class="tp-air is-load" data-tp-air data-tp-coords="32.333333, 52.783333"' )
			&& 5 === substr_count( $x[0], '>—<' ) && ! preg_match( '/>[^<]*[0-9۰-۹][^<]*</u', preg_replace( '/PM2\.5|PM10/', '', $x[0] ) )
			&& false !== strpos( $x[0], 'برآورد مدل CAMS' ) && false !== strpos( $x[0], 'داده‌های هوا: MET Norway · کیفیت هوا: CAMS، Open-Meteo' )
			&& '' === $x[1] && '' === $x[2] && '' === $x[3]
			&& false !== strpos( $x[4], 'id="tp-weather"' ) && strpos( $x[4], 'id="tp-weather"' ) < strpos( $x[4], 'id="tp-aqi"' )
			&& false !== strpos( $x[5], 'Weather data: MET Norway · Air quality: CAMS, Open-Meteo' ) && false !== strpos( $x[5], 'CAMS model estimate' ) && false !== strpos( $x[5], '>Dust<' );
	},
	array( $air_box, $air_country, $air_nocoord, $air_off, $air_page, $air_en ), array( str_replace( 'data-tp-air', 'x', $air_box ), 'x', '', '', $air_page, $air_en ) );

/* ── جاوااسکریپت: فقط متن عنصرهای موجود، خطا ← «—»، بی innerHTML ─────── */
$a2g = strpos( $js2g, '══ کیفیت هوا' );
$air_js = false === $a2g ? '' : substr( $js2g, $a2g, 2600 );
tp441_mut( 'جاوااسکریپت هوا: از `TopersiaG.air` می‌خواند، فقط textContent عنصرهای موجودِ اسکلت را عوض می‌کند (بی innerHTML ← CLS صفر)، رده را در data-tp-cat می‌گذارد، و خطا یا پاسخ ناجور فقط `is-load` را برمی‌دارد («—» می‌ماند)',
	function ( $s ) {
		return false !== strpos( $s, "window.TopersiaG.air" ) && false === strpos( $s, 'innerHTML' ) && false !== strpos( $s, 'el.textContent = text;' )
			&& false !== strpos( $s, "'number' !== typeof d.aqi" ) && false !== strpos( $s, ".catch( done );" ) && false !== strpos( $s, "box.setAttribute( 'data-tp-cat', d.cat );" )
			&& false !== strpos( $s, "box.classList.remove( 'is-load' )" );
	},
	$air_js, str_replace( 'el.textContent = text;', 'el.innerHTML = text;', $air_js ) );

/* ── رنگ‌ها: سه حالت، ≥۴٫۵ روی --tp-bg و --tp-surface ──────────────── */
$lum2g = function ( $hex ) {
	$c = array_map( function ( $i ) use ( $hex ) { $v = hexdec( substr( $hex, $i, 2 ) ) / 255; return $v <= 0.03928 ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 ); }, array( 1, 3, 5 ) );
	return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
};
$cr2g = function ( $a, $b ) use ( $lum2g ) { $x = $lum2g( $a ); $y = $lum2g( $b ); return ( max( $x, $y ) + 0.05 ) / ( min( $x, $y ) + 0.05 ); };
$tok_report = array();
foreach ( array( 'light', 'evening', 'dark' ) as $mode ) {
	preg_match( '/html\[data-tp-mode="' . $mode . '"\]\{\s*--tp-bg:(#[0-9A-Fa-f]{6});\s*--tp-surface:(#[0-9A-Fa-f]{6});(.*?)\n\}/su', $tok2g, $m );
	$bg = isset( $m[1] ) ? $m[1] : '#000000'; $sf = isset( $m[2] ) ? $m[2] : '#000000';
	preg_match_all( '/--tp-aqi-([1-6]):(#[0-9A-Fa-f]{6});/', isset( $m[3] ) ? $m[3] : '', $cm, PREG_SET_ORDER );
	$tok_report[ $mode ] = array( 'n' => count( $cm ), 'min' => count( $cm ) ? min( array_map( function ( $c ) use ( $bg, $sf, $cr2g ) { return min( $cr2g( $c[2], $bg ), $cr2g( $c[2], $sf ) ); }, $cm ) ) : 0 );
}
$tok_bad = str_replace( '--tp-aqi-2:#836407;', '--tp-aqi-2:#e0c050;', $tok2g );   /* زردِ روشن روی زمینهٔ روشن */
$tok_bad_report = array();
preg_match( '/html\[data-tp-mode="light"\]\{\s*--tp-bg:(#[0-9A-Fa-f]{6});\s*--tp-surface:(#[0-9A-Fa-f]{6});(.*?)\n\}/su', $tok_bad, $mb );
preg_match_all( '/--tp-aqi-([1-6]):(#[0-9A-Fa-f]{6});/', $mb[3], $cb, PREG_SET_ORDER );
$tok_bad_min = min( array_map( function ( $c ) use ( $mb, $cr2g ) { return min( $cr2g( $c[2], $mb[1] ), $cr2g( $c[2], $mb[2] ) ); }, $cb ) );
tp441_mut( 'توکن رنگ شش ردهٔ AQI در هر سه حالت (روز · عصر · شب): هر رنگ ≥۴٫۵ روی --tp-bg و --tp-surface همان حالت؛ جهش: زردِ روشن روی روز می‌افتد',
	function ( $x ) { return 6 === $x[0]['light']['n'] && 6 === $x[0]['evening']['n'] && 6 === $x[0]['dark']['n'] && min( $x[0]['light']['min'], $x[0]['evening']['min'], $x[0]['dark']['min'] ) >= 4.5 && $x[1] >= 4.5; },
	array( $tok_report, $tok_report['light']['min'] ), array( $tok_report, $tok_bad_min ),
	json_encode( $tok_report ) );

tp441_mut( 'شیوه‌نامهٔ جعبه: رنگ رده فقط از توکن --tp-aqi-N (شش قاعدهٔ رنگ متن و شش خطّ کناری)، بی رنگ خام؛ برچسب متنی هست (رنگ تنها حامل معنا نیست)',
	function ( $c ) {
		preg_match_all( '/html \.tp-page \.tp-air[^{]*\{[^}]*\}/u', $c, $m );
		$all = implode( '', $m[0] );
		return 6 === preg_match_all( '/tp-air\[data-tp-cat="\d"\] \.tp-air__cat\{color:var\(--tp-aqi-\d\)\}/', $all ) && 6 === preg_match_all( '/\.tp-air__main\{border-inline-start-color:var\(--tp-aqi-\d\)\}/', $all )
			&& ! preg_match( '/#[0-9a-fA-F]{3,6}|rgba?\(/', $all );
	},
	$css2g, str_replace( 'color:var(--tp-aqi-3)}', 'color:#cc5500}', $css2g ) );

tp441_mut( 'چیدمان: «کیفیت هوا» (tp-aqi) بلافاصله پس از کارت آب‌وهوا در ستون کنار؛ کشور از ماتریس خاموش دارد؛ سرویس در هستهٔ ردهٔ رایگان روشن (با پرچم تجاری و بی کلید پولی خودش off می‌شود)',
	function ( $x ) {
		return 1 === preg_match( "/'id' => 'tp-weather', 'nav' => '' \),\s*array\( 'fn' => 'topersia_g_sc_air',\s*'id' => 'tp-aqi'/u", $x[0] )
			&& false !== strpos( $x[0], "'off'   => array( 'tp-weather', 'tp-routing', 'tp-aqi' )," ) && false !== strpos( $x[1], "'provider' => 'open-meteo-aq', 'enabled' => true )" );
	},
	array( $zones2g, file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-services.php' ) ),
	array( str_replace( "'id' => 'tp-aqi'", "'id' => 'tp-x'", $zones2g ), file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-services.php' ) ) );
