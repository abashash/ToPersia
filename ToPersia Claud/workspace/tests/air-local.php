<?php
/**
 * کیفیت هوا روی وردپرسِ واقعیِ محلی — P5-D345.
 *
 *   php tests/air-local.php
 *
 * بالادست (Open-Meteo) با `pre_http_request` جایگزین می‌شود (شمارش فراخوان)؛ فقط یک بار آخر یک فراخوان واقعی برای شکل پاسخ.
 * سنجیده می‌شود: REST با کش مرکزی (سلول ۰٫۲۵ درجه، یک فراخوان بالادست برای نقطه‌های هم‌سلول)، خطای مختصات، بیرون ایران، پرچم
 * تجاری روشن و بی کلید پولی ← ۵۰۳ و بی جعبه در صفحه، سه زبان (fa · en · ar)، نبودِ کلید در پاسخ و صفحه، کشور بی جعبه،
 * دادهٔ کهنه به‌جای خطا. هر دادهٔ آزمون برگردانده می‌شود (گزینهٔ تجاری).
 *
 * کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص
 */
require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخهٔ محلی\n" );
	exit( 2 );
}

if ( ! function_exists( 'topersia_g_aq_rest' ) || ! function_exists( 'topersia_cache_remember' ) ) {
	fwrite( STDERR, "افزونه‌ها روی نسخهٔ محلی همگام نیست\n" );
	exit( 2 );
}

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function t( $label, $ok, $note = '' ) {
	if ( $ok ) { $GLOBALS['pass']++; echo "  ✅ {$label}\n"; }
	else       { $GLOBALS['fail']++; echo "  ❌ {$label}" . ( '' !== $note ? "   ← {$note}" : '' ) . "\n"; }
}

function call_air( $lat, $lng, $lang = null ) {
	if ( $lang ) { do_action( 'wpml_switch_language', $lang ); }
	$req = new WP_REST_Request( 'GET', '/topersia/v1/air' );
	$req->set_param( 'lat', (string) $lat );
	$req->set_param( 'lng', (string) $lng );
	$res = rest_do_request( $req );
	if ( $lang ) { do_action( 'wpml_switch_language', 'fa' ); }
	return $res;
}

echo "\nکیفیت هوا — وردپرس واقعی (فقط محلی)\n" . str_repeat( '─', 78 ) . "\n";

$calls = array();
$mode  = 'ok';

add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$calls, &$mode ) {
	if ( false === strpos( $url, 'air-quality-api.open-meteo.com' ) ) {
		return $pre;
	}
	$calls[] = $url;
	if ( 'down' === $mode ) {
		return new WP_Error( 'x', 'down' );
	}
	return array(
		'headers'  => array(),
		'body'     => '{"current":{"time":"2026-10-12T00:00","interval":3600,"us_aqi":112,"pm10":40.2,"pm2_5":38.6,"dust":10.0}}',
		'response' => array( 'code' => 200, 'message' => 'OK' ),
		'cookies'  => array(),
		'filename' => null,
	);
}, 10, 3 );

$keep_commercial = get_option( TOPERSIA_COMMERCIAL_OPTION, false );
$gav     = 3267;
$country = 2095;

try {
	/* نقطه‌ای بی صفحهٔ واقعی، تا با کش واقعی قاطی نشود */
	$a  = call_air( 30.01, 60.02 );
	$n1 = count( $calls );
	$b  = call_air( 30.10, 60.10 );   /* همان سلول */
	$n2 = count( $calls );
	$c  = call_air( 30.40, 60.40 );   /* سلول دیگر */
	$n3 = count( $calls );
	$d  = $a->get_data();

	t( 'REST: ۲۰۰ با aqi · cat · label · pm25 · pm10 · dust · model؛ ۱۱۲ ← رده ۳ «ناسالم برای گروه‌های حساس»',
		200 === $a->get_status() && 112 === $d['aqi'] && 3 === $d['cat'] && 'ناسالم برای گروه‌های حساس' === $d['label'] && 38.6 === $d['pm25'] && 40.2 === $d['pm10'] && 10.0 === $d['dust'] && 'برآورد مدل CAMS' === $d['model'], wp_json_encode( $d ) );
	t( 'کش مرکزی: نقطه‌های هم‌سلول (۰٫۲۵ درجه) یک فراخوان بالادست؛ سلول دیگر فراخوان تازه', 1 === $n1 && 1 === $n2 && 2 === $n3, "{$n1}/{$n2}/{$n3}" );

	call_air( 30.41, 60.41 );
	$st = topersia_cache_last();
	t( 'وضعیت کش پس از دومین خواندنِ همان سلول: hit', isset( $st['status'] ) && 'hit' === $st['status'], wp_json_encode( $st ) );

	$bad = call_air( 'abc', 5 );
	$out = call_air( 48.85, 2.35 );
	t( 'مختصات نامعتبر ← ۴۰۰؛ بیرون از ایران ← ۴۰۰ و بی فراخوان بالادست', 400 === $bad->get_status() && 400 === $out->get_status() && 2 === count( $calls ) );

	foreach ( array( 'en' => 'Unhealthy for sensitive groups', 'ar' => 'غير صحي للفئات الحساسة', 'fa' => 'ناسالم برای گروه‌های حساس' ) as $lang => $want ) {
		$r = call_air( 30.01, 60.02, $lang )->get_data();
		t( "برچسب رده به زبان {$lang}", $want === ( isset( $r['label'] ) ? $r['label'] : '' ), isset( $r['label'] ) ? $r['label'] : '' );
	}

	/* دادهٔ کهنه به‌جای خطا: بالادست پایین؛ سلولِ کش‌دار ← کش، سلولِ بی کش ← ۵۰۳ */
	$mode   = 'down';
	$cached = call_air( 30.01, 60.02 );
	$fresh  = call_air( 31.51, 61.01 );
	t( 'بالادست پایین: سلولِ کش‌دار همچنان ۲۰۰، سلولِ بی کش ← ۵۰۳ «tp_aq_upstream»',
		200 === $cached->get_status() && 503 === $fresh->get_status() && 'tp_aq_upstream' === $fresh->as_error()->get_error_code() );
	$mode = 'ok';

	/* جعبه در صفحه */
	$html_g = do_shortcode( '[tp_air id="' . $gav . '"]' );
	$html_c = do_shortcode( '[tp_air id="' . $country . '"]' );
	t( 'گاوخونی: اسکلت با مختصات نرمال‌شده و بی عدد؛ کشور: جعبه ندارد',
		false !== strpos( $html_g, 'data-tp-coords="32.333333, 52.783333"' ) && 5 === substr_count( $html_g, '>—<' ) && '' === $html_c );
	t( 'هیچ کلید یا apikey در پاسخ REST و HTML', false === strpos( wp_json_encode( $d ) . $html_g, 'apikey' ) && false === stripos( $html_g, 'api_key' ) );

	/* پرچم تجاری روشن و بی کلید پولی ← خاموش */
	update_option( TOPERSIA_COMMERCIAL_OPTION, true );
	if ( function_exists( 'topersia_api_flush' ) ) { topersia_api_flush(); }
	$off  = call_air( 30.01, 60.02 );
	$html = do_shortcode( '[tp_air id="' . $gav . '"]' );
	t( 'site_is_commercial روشن و بی کلید پولی: REST ← ۵۰۳ «tp_aq_off» و جعبه در صفحه نمی‌آید',
		503 === $off->get_status() && 'tp_aq_off' === $off->as_error()->get_error_code() && '' === $html );
} finally {
	update_option( TOPERSIA_COMMERCIAL_OPTION, $keep_commercial );
	if ( function_exists( 'topersia_api_flush' ) ) { topersia_api_flush(); }
}

/* یک فراخوان واقعی — شکل پاسخ رسمی */
remove_all_filters( 'pre_http_request' );
$real = topersia_g_aq_fetch( topersia_g_aq_cell( 32.33, 52.78 ) );
t( 'فراخوان واقعی Open-Meteo: current.us_aqi · pm2_5 · pm10 · dust خوانده می‌شود',
	is_array( $real ) && isset( $real['aqi'] ) && is_int( $real['aqi'] ) && array_key_exists( 'pm25', $real ) && array_key_exists( 'dust', $real ),
	is_wp_error( $real ) ? $real->get_error_message() : wp_json_encode( $real ) );

echo str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['pass']}   افتاده: {$GLOBALS['fail']}\n";
exit( $GLOBALS['fail'] ? 1 : 0 );
