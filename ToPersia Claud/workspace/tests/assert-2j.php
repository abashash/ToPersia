<?php
/**
 * ادعاهای دستهٔ ۲ (۱۳ اکتبر) — مسیریابی، لایهٔ مکان‌ها، قلم Italic، مرز محدوده  P5-D350 · P5-D351 · P5-D352
 * از assert.php پس از assert-2i.php بار می‌شود؛ PHP خالص، هر کدام با جهش. رفتار در مرورگر: TP_ONLY=map node tests/behaviour.js · tests/map-2j.js.
 */

$route2j = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-routing.php' );
$svc2j   = file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-services.php' );
$js2j    = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-zones.js' );
$zones2j = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );

echo "\nدستهٔ ۲ (۱۳ اکتبر) — مسیریابی، نقشهٔ آفلاین (مکان‌ها، قلم)، مرز محدوده  P5-D350 · P5-D351 · P5-D352\n" . str_repeat( '─', 78 ) . "\n";

/* ── الگوهای سرویس navigation ─────────────────────────────────────── */
$nav2j = array();
if ( preg_match( '/\'deep-links\'\s+=> array\( \'service\'.*?\'extra\' => (array\(.*?\R\t\t\)), \'doc\'/su', $svc2j, $m2j ) ) {
	$nav2j = eval( 'return ' . $m2j[1] . ';' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged — الگوها را از خودِ پروندهٔ هسته می‌خواند
}

tp441_mut( 'مسیریابی: الگوها از سرویس navigation مدیریت مرکزی؛ فقط مستند رسمی — Google و Waze هم مختصات هم متن، نشان فقط نمایش نقطهٔ مستند (nshn.ir)، بلد بی مستند خاموش؛ سرویس روشن',
	function ( $x ) {
		$g = $x[0]['google']; $w = $x[0]['waze']; $n = $x[0]['neshan']; $b = $x[0]['balad'];
		return $g['on'] && $w['on'] && $n['on'] && ! $b['on']
			&& false !== strpos( $g['pattern'], 'api=1&destination={lat},{lng}' ) && false !== strpos( $g['text'], 'api=1&destination={q}' )
			&& false !== strpos( $w['pattern'], 'll={lat},{lng}&navigate=yes' ) && false !== strpos( $w['text'], 'q={q}&navigate=yes' )
			&& 'https://nshn.ir/?lat={lat}&lng={lng}' === $n['pattern'] && '' === $n['text'] && '' === $b['pattern'] && '' === $b['text']
			&& false !== strpos( $x[1], "'navigation'      => array( 'label' => 'پیوند مسیریابی', 'purpose' => 'دکمه‌های جعبۀ «مسیریابی» — فاز ۳', 'provider' => 'deep-links', 'enabled' => true )" );
	},
	array( $nav2j, $svc2j ), array( array_merge( $nav2j, array( 'balad' => array( 'on' => true, 'pattern' => 'https://x', 'text' => '' ) ) ), str_replace( "'provider' => 'deep-links', 'enabled' => true", "'provider' => 'deep-links', 'enabled' => false", $svc2j ) ) );

/* ── جدول ارائه‌دهنده × مختصات/متن ─────────────────────────────────── */
$links_c = topersia_g_routing_links( $nav2j, array( 'mode' => 'coords', 'lat' => 32.6539, 'lng' => 51.666 ) );
$links_t = topersia_g_routing_links( $nav2j, array( 'mode' => 'text', 'q' => 'تالاب آزمون، استان اصفهان' ) );
$q2j     = rawurlencode( 'تالاب آزمون، استان اصفهان' );

tp441_mut( 'مسیریابی: نشانی هر دکمه در هر دو حالت برابر الگوی مستند — مختصات: Google · Waze · نشان؛ متن: Google · Waze (نشان و بلد در حالت متن نمی‌آیند)',
	function ( $x ) use ( $q2j ) {
		return array( 'google', 'waze', 'neshan' ) === array_keys( $x[0] ) && array( 'google', 'waze' ) === array_keys( $x[1] )
			&& 'https://www.google.com/maps/dir/?api=1&destination=32.6539,51.666' === $x[0]['google']
			&& 'https://waze.com/ul?ll=32.6539,51.666&navigate=yes' === $x[0]['waze']
			&& 'https://nshn.ir/?lat=32.6539&lng=51.666' === $x[0]['neshan']
			&& 'https://www.google.com/maps/dir/?api=1&destination=' . $q2j === $x[1]['google']
			&& 'https://waze.com/ul?q=' . $q2j . '&navigate=yes' === $x[1]['waze'];
	},
	array( $links_c, $links_t ), array( array_merge( $links_c, array( 'balad' => 'https://x' ) ), array_merge( $links_t, array( 'neshan' => 'https://nshn.ir/?q=x' ) ) ) );

/* ── مقصد: نقطه ← مختصات؛ پهنه یا بی مختصات ← نام + والد؛ کشور و بی‌والد ← هیچ ─────────── */
$rel2j = $f2_reg;
$f2_world( $rel2j );
$f2_post( 8010, 'tp_city', 'شهر بی مختصات' );
$GLOBALS['tp_rel']['7003']['children'][8003][] = 8010;
$GLOBALS['tp_rel']['7003']['parents'][8010]     = array( 8003 );
$GLOBALS['tp_meta'][8004]['geo_coordinates'] = '32.6539, 51.6660';
$GLOBALS['tp_meta'][8005]['geo_coordinates'] = '32.0, 52.0';
$f2_post( 8020, 'tp_festival', 'جشن بی‌والد' );

$t_point = topersia_g_routing_target( 8004 );
$t_area  = topersia_g_routing_target( 8005 );
$t_nocoo = topersia_g_routing_target( 8010 );
$t_orph  = topersia_g_routing_target( 8020 );
$kinds2j = topersia_g_kind_labels();

tp441_mut( 'مقصد مسیریابی: شهر (نقطه‌ای) با مختصات معتبر ← مختصات؛ پهنهٔ آبی ← نام فارسی + والد مکانی، هرگز مرکز هندسی پهنه (مختصاتش هست ولی استفاده نمی‌شود)؛ شهرِ بی مختصات ← نام + والد؛ نوع غیرمکانیِ بی والد ← هیچ',
	function ( $x ) use ( $kinds2j ) {
		return 'coords' === $x[0]['mode'] && 32.6539 === $x[0]['lat'] && 51.666 === $x[0]['lng']
			&& 'text' === $x[1]['mode'] && 0 === strpos( $x[1]['q'], $kinds2j['tp_water_body'] ) && false !== strpos( $x[1]['q'], 'تالاب آزمون' ) && '، ' . $kinds2j['tp_province'] . ' اصفهان' === substr( $x[1]['q'], - strlen( '، ' . $kinds2j['tp_province'] . ' اصفهان' ) )
			&& 'text' === $x[2]['mode'] && array() === $x[3];
	},
	array( $t_point, $t_area, $t_nocoo, $t_orph ), array( $t_area, $t_point, array(), array( 'mode' => 'text', 'q' => 'x' ) ) );

/* ── باکس: همهٔ نوع‌ها جز کشور ───────────────────────────────────────── */
$GLOBALS['tp_services_nav'] = $nav2j;
$box_point = r( 8004, 'tp_routing' );
$box_area  = r( 8005, 'tp_routing' );
$page_ctry = r( 8001, 'tp_page' );
$box_none  = r( 8020, 'tp_routing' );

tp441_mut( 'باکس مسیریابی: شهر و پهنهٔ آبی دارند (کاشی فقط نام، هر دکمه data-tp-nav، باز در برگهٔ تازه با rel=noopener)؛ صفحهٔ کشور ندارد (ماتریس)؛ بی مقصد باکسی نیست',
	function ( $x ) {
		return false !== strpos( $x[0], 'id="tp-routing"' ) && 3 === substr_count( $x[0], 'data-tp-nav="' ) && false !== strpos( $x[0], 'target="_blank" rel="noopener nofollow"' )
			/* P5-D383: پهنهٔ آبی با مختصات نماینده در geo_coordinates ← نشان با همان نقطه؛ Google و Waze همان متن (P5-D350) */
			&& false !== strpos( $x[1], 'id="tp-routing"' ) && 3 === substr_count( $x[1], 'data-tp-nav="' ) && false !== strpos( $x[1], 'https://nshn.ir/?lat=32&amp;lng=52' ) && false !== strpos( $x[1], 'destination=' ) && false === strpos( $x[1], 'destination=32' )
			&& false === strpos( $x[2], 'id="tp-routing"' ) && '' === trim( $x[3] ) && false === strpos( $x[0], '<svg' );
	},
	array( $box_point, $box_area, $page_ctry, $box_none ), array( str_replace( 'data-tp-nav="google"', 'x', $box_point ), preg_replace( '#<a [^>]*data-tp-nav="neshan"[^>]*>.*?</a>#u', '', $box_area ), 'id="tp-routing"', 'x' ) );

tp441_mut( 'مسیریابی: موقعیت کاربر خواسته نمی‌شود و نگه داشته نمی‌شود — نه geolocation در JS، نه مبدأ در نشانی؛ آخرین انتخاب فقط localStorage (در try/catch) و دکمهٔ آن اول',
	function ( $x ) {
		return false === strpos( $x[0], 'geolocation' ) && false === stripos( $x[1], 'origin=' ) && false === stripos( $x[1], 'saddr' )
			&& false !== strpos( $x[0], "var KEY = 'tp-nav-last';" ) && 1 === preg_match( "/try \{ return window\.localStorage\.getItem\( KEY \)[^}]*\} catch \( e \) \{ return ''; \}/", $x[0] )
			&& false !== strpos( $x[0], 'box.insertBefore( tile, box.firstElementChild )' );
	},
	array( $js2j, $svc2j ), array( $js2j . ' navigator.geolocation', $svc2j . " 'https://x/?origin=1" ) );

/* ── نقشه: قلم Italic، letter-spacing فارسی و عربی، لایهٔ مکان‌ها، مرز ─────────── */
tp441_mut( 'سبک نقشه: قلم Italic بستهٔ Noto (بازه‌های عربی تهی) ← Noto Sans Regular؛ text-letter-spacing برای fa و ar برداشته (لاتین همان)',
	function ( $x ) {
		return false !== strpos( $x, "flavor.italic = 'Noto Sans Regular';" )
			&& false !== strpos( $x, "if ( ( 'fa' === lang || 'ar' === lang ) && layer.layout && undefined !== layer.layout[ 'text-letter-spacing' ] ) {" )
			&& false !== strpos( $x, "delete layer.layout[ 'text-letter-spacing' ];" );
	},
	$js2j, str_replace( "flavor.italic = 'Noto Sans Regular';", '', $js2j ) );

tp441_mut( 'لایهٔ مکان‌ها روشن: جاذبه · موزه · مکان مذهبی · پارک · هتل · رستوران و کافه · فرودگاه · ایستگاه (kindهای Protomaps v4)؛ برچسب از lang صفحه',
	function ( $x ) {
		if ( ! preg_match( "/var POI_KINDS = \[([^\]]*)\];/", $x, $m ) ) { return false; }
		foreach ( array( 'attraction', 'museum', 'place_of_worship', 'park', 'hotel', 'restaurant', 'cafe', 'aerodrome', 'station' ) as $k ) {
			if ( false === strpos( $m[1], "'" . $k . "'" ) ) { return false; }
		}
		return false !== strpos( $x, "layer.filter = [ 'all', [ 'in', [ 'get', 'kind' ], [ 'literal', POI_KINDS ] ]" ) && false !== strpos( $x, "{ lang: lang }" );
	},
	$js2j, str_replace( "'hotel', ", '', $js2j ) );

tp441_mut( 'مرز محدوده: cfg.bound فقط وقتی فایل GeoJSON همان موجودیت هست؛ خط + پرشدگی کم‌رنگ با رنگ برند روی هر دو سبک؛ بار فقط هنگام mount (نقشه در دید)؛ نقشه روی همان محدوده؛ انتساب OSM',
	function ( $x ) {
		return false !== strpos( $x[0], 'topersia_g_boundary_key( topersia_g_text( $post_id' ) && false !== strpos( $x[0], "\$cfg['bound'] = set_url_scheme(" ) && false !== strpos( $x[0], "'tiles/boundaries/' . \$uid . '.geojson'" ) && false !== strpos( $x[0], 'if ( is_file( $file ) ) {' )
			&& false !== strpos( $x[1], "map.addLayer( { id: 'tp-bound-fill', type: 'fill'" ) && false !== strpos( $x[1], "id: 'tp-bound-line', type: 'line'" )
			&& false !== strpos( $x[1], "attribution: '© OpenStreetMap contributors'" ) && false !== strpos( $x[1], 'map.fitBounds(' )
			&& false !== strpos( $x[1], "map.once( 'style.load', function () { addPoints( map, ml, box, interactive ); addBound( map, ml, box ); } );" )
			&& false !== strpos( $x[1], 'loadBound( map, ml, box, cfg );' );
	},
	array( $zones2j, $js2j ), array( str_replace( 'if ( is_file( $file ) ) {', 'if ( true ) {', $zones2j ), str_replace( 'addBound( map, ml, box ); } );', '} );', $js2j ) ) );

/* برگرداندن دنیای آزمون */
unset( $GLOBALS['tp_services_nav'] );
$GLOBALS['tp_posts'] = $f2_keep_posts;
$GLOBALS['tp_rel']   = $f2_keep_rel;
$GLOBALS['tp_meta']  = $f2_keep_meta;
if ( null === $f2_keep_flt ) { unset( $GLOBALS['tp_filters']['topersia_g_relreg'] ); } else { $GLOBALS['tp_filters']['topersia_g_relreg'] = $f2_keep_flt; }
