<?php
/**
 * ادعاهای نقشه (P5-D334 · P5-D346) — از assert.php پس از assert-2g.php بار می‌شود؛ PHP خالص، هر کدام با جهش.
 * رفتار روی وردپرس واقعی (نمایه، پاک و بازسازی، REST، کش، زبان): tests/map-local.php؛ مرورگر: TP_ONLY=map node tests/behaviour.js.
 */

$geo2h  = file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-geo.php' );
$idx2h  = file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-geo-index.php' );
$mp2h   = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-mappoints.php' );
$js2h   = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-zones.js' );
$zones2h = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );
$rr2h   = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-relreg.php' );

echo "\nفاز ۳ (بخشی، پیش از ۲د) — نقشه: نمایهٔ مشتق، map-points، نشانگر، سبک تیره  P5-D334 · P5-D346\n" . str_repeat( '─', 78 ) . "\n";

/* ── تجزیهٔ مختصات: وضعیت و نوع شکل ──────────────────────────────────── */
require_once dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-geo.php';
$p2h = array(
	topersia_geo_parse( '32.6539, 51.6660' ),
	topersia_geo_parse( '' ),
	topersia_geo_parse( '48.8566, 2.3522' ),
	topersia_geo_parse( 'نامشخص' ),
	topersia_geo_parse( '{"type":"Polygon","coordinates":[[[52.0,32.0],[53.0,32.0],[53.0,33.0],[52.0,33.0],[52.0,32.0]]]}' ),
	topersia_geo_parse( '{"type":"LineString","coordinates":[[52.0,32.0],[53.0,33.0],[54.0,33.5]]}' ),
	topersia_geo_parse( '32.1,52.1; 32.3,52.1; 32.3,52.5' ),
	topersia_geo_parse( '32°20′N, 52°47′E' ),
);
tp441_mut( 'تجزیهٔ کامل مختصات: valid (نقطه · شکل ← مرکز) · absent · outside (خوانده شد ولی بیرون از ایران) · invalid (قالب ناخوانا)؛ نوع شکل point · polygon · line · list',
	function ( $x ) {
		return 'valid' === $x[0]['status'] && 'point' === $x[0]['kind'] && 'absent' === $x[1]['status'] && 'outside' === $x[2]['status'] && 48.8566 === $x[2]['lat']
			&& 'invalid' === $x[3]['status'] && 'valid' === $x[4]['status'] && 'polygon' === $x[4]['kind'] && 32.5 === $x[4]['lat']
			&& 'line' === $x[5]['kind'] && 'list' === $x[6]['kind'] && 'point' === $x[7]['kind'] && 32.333333 === $x[7]['lat']
			&& null !== topersia_geo_point( '32.6539, 51.6660' ) && null === topersia_geo_point( '48.8566, 2.3522' ) && null === topersia_geo_point( '' );
	},
	$p2h, array_replace( $p2h, array( 2 => array( "status" => "valid", "kind" => "point", "lat" => 48.8566, "lng" => 2.3522 ) ) ) );

/* ── نمایه: ساختار A، فقط منتشرشدهٔ زبان پیش‌فرض، مختصات از تابع مرکزی، زنجیره از صافی ── */
tp441_mut( 'نمایهٔ مشتق: ساختار A (DECIMAL + نمایهٔ ترکیبی ll · tll · prov · city)، ستون‌های post_id · post_type · lat · lng · geom_kind · province_id · city_id · index_version؛ فقط منتشرشده و زبان پیش‌فرض؛ مختصات از topersia_geo_parse؛ زنجیره از صافی؛ فقط محلی؛ هرگز منبع نوشتن نیست',
	function ( $s ) {
		return false !== strpos( $s, 'lat DECIMAL(8,6) NOT NULL' ) && false !== strpos( $s, 'lng DECIMAL(9,6) NOT NULL' ) && false !== strpos( $s, 'KEY tll (post_type,lat,lng)' )
			&& false !== strpos( $s, 'KEY prov (province_id,post_type)' ) && false !== strpos( $s, 'KEY city (city_id,post_type)' ) && false === strpos( $s, 'SPATIAL' )
			&& false !== strpos( $s, "'publish' !== get_post_status( \$post_id )" ) && false !== strpos( $s, 'topersia_geo_index_is_default_lang( $post_id )' )
			&& false !== strpos( $s, "topersia_geo_parse( get_post_meta( \$post_id, 'geo_coordinates', true ) )" ) && false !== strpos( $s, "apply_filters( 'topersia_geo_index_chain'" )
			&& false !== strpos( $s, "'local' === wp_get_environment_type()" ) && false === strpos( $s, 'update_post_meta' );
	},
	$idx2h, str_replace( 'lat DECIMAL(8,6) NOT NULL', 'lat FLOAT NOT NULL', $idx2h ) );

tp441_mut( 'نمایه همگام می‌ماند: ذخیرهٔ پست، حذف، و خروج از «منتشرشده» ردیف را عوض یا برمی‌دارد؛ هر نوشتن گواه تازگی (کلید کش map-points) را عوض می‌کند؛ بازسازی کامل با WP-CLI (rebuild · verify · report)',
	function ( $s ) {
		return false !== strpos( $s, "add_action( 'save_post'" ) && false !== strpos( $s, "add_action( 'before_delete_post'" ) && false !== strpos( $s, "add_action( 'transition_post_status'" )
			&& false !== strpos( $s, 'update_option( \'topersia_geo_index_stamp\'' ) && false !== strpos( $s, "WP_CLI::add_command( 'topersia geo-index'" )
			&& false !== strpos( $s, "'DROP TABLE IF EXISTS ' . topersia_geo_index_table()" );
	},
	$idx2h, str_replace( "add_action( 'before_delete_post'", "add_action( 'x_before_delete_post'", $idx2h ) );

tp441_mut( 'زنجیرهٔ R01 نمایه را افزونهٔ نمایش می‌دهد (`topersia_geo_index_chain` ← `topersia_g_place_chain`)؛ هسته به افزونهٔ نمایش وابسته نیست',
	function ( $x ) { return false !== strpos( $x[0], "add_filter( 'topersia_geo_index_chain'" ) && false !== strpos( $x[0], 'topersia_g_place_chain( $pid )' ) && false === strpos( $x[1], 'topersia_g_' ); },
	array( $rr2h, $idx2h ), array( str_replace( "'topersia_geo_index_chain'", "'x_chain'", $rr2h ), $idx2h ) );

/* ── REST و خانواده‌ها ──────────────────────────────────────────────── */
/* P5-D387 (جایگزین بخش زمینهٔ P5-D334): فقط یک سطح پایین‌تر */
tp441_mut( 'map-points: یک سطح پایین‌تر — استان ← شهرها؛ شهر ← جاذبه‌ها و طبیعتِ درون شهر (نه زیر روستا)؛ محله و روستا ← جاذبه‌های خودش (والد R01 نزدیک)؛ از نمایه، فقط منتشرشده، کش با گواه نمایه  P5-D387',
	function ( $s ) {
		return 1 === preg_match( "/'tp_province' => array\( 'scope' => 'province', 'families' => array\( 'city' \) \)/", $s )
			&& 1 === preg_match( "/'tp_city'\s+=> array\( 'scope' => 'city', 'families' => array\( 'attraction', 'nature' \), 'inside' => true \)/", $s )
			&& 1 === preg_match( "/'tp_district' => array\( 'scope' => 'up', 'families' => array\( 'attraction' \), 'own' => true \)/", $s )
			&& 1 === preg_match( "/'tp_village'\s+=> array\( 'scope' => 'up', 'families' => array\( 'attraction' \), 'own' => true \)/", $s )
			&& false !== strpos( $s, "if ( ! empty( \$def['own'] ) && \$nid !== (int) \$base ) {" ) && false !== strpos( $s, "'tp_village' === get_post_type( \$nid )" )
			&& false !== strpos( $s, "topersia_g_published( array( (int) \$row['post_id'] ) )" ) && false !== strpos( $s, "array( \$ctx, topersia_g_page_lang(), topersia_geo_index_stamp() )" )
			&& false !== strpos( $s, "'topersia/v1', '/map-points'" ) && false !== strpos( $s, '$cap      = 400;' )
			&& false !== strpos( $s, "'publish' !== get_post_status( \$ctx )" ) && false === strpos( $s, 'get_post_meta' ) && false === strpos( $s, 'update_post_meta' );
	},
	$mp2h, str_replace( "'tp_province' => array( 'scope' => 'province', 'families' => array( 'city' ) )", "'tp_province' => array( 'scope' => 'province', 'families' => array( 'city', 'attraction', 'nature' ) )", $mp2h ) );
tp441_mut( 'پیکربندی نقشه فقط برای استان و شهر نشانی نشانگر می‌گیرد (`points`)، از نمایه؛ مسیریابی و نشان و /nearby ساخته نشده',
	function ( $x ) {
		return false !== strpos( $x[0], "\$cfg['points'] = esc_url_raw( add_query_arg( 'context', absint( \$post_id ), rest_url( 'topersia/v1/map-points' ) ) );" )
			&& false !== strpos( $x[0], 'topersia_g_map_points_on( $post_id )' )
			&& false === stripos( $x[1] . $x[2], '/nearby' ) && false === stripos( $x[1] . $x[2], 'routing' ) && false === stripos( $x[1], 'neshan' );
	},
	array( $zones2h, $mp2h, $idx2h ), array( str_replace( "\$cfg['points'] = ", "\$cfg['x'] = ", $zones2h ), $mp2h, $idx2h ) );

/* ── جاوااسکریپت: خوشه داخلی، آیکون هر خانواده، پنجره، سبک تیره ─────── */
$m2h = strpos( $js2h, 'نشانگرها: آیکون هر خانواده' );
$map_js = false === $m2h ? '' : substr( $js2h, $m2h - 40, 9000 );
tp441_mut( 'نشانگر روی MapLibre موجود: خوشه‌بندی داخلی GeoJSON (cluster:true، clusterRadius، بی کتابخانهٔ تازه)، پنج آیکون خانواده، شمارندهٔ خوشه، کلیک خوشه ← بزرگ‌نمایی، پنجرهٔ کوچک با تصویر · نام · نوع · پیوند ساخته با DOM (بی innerHTML، نشانی امن)',
	function ( $s ) {
		return false !== strpos( $s, "cluster: true, clusterRadius: 46, clusterMaxZoom: 11" ) && 5 === preg_match_all( "/^\t\t\t(city|attraction|nature|district|village):\s+'M/m", $s )
			&& false !== strpos( $s, "'text-field': [ 'get', 'point_count_abbreviated' ]" ) && false !== strpos( $s, 'getClusterExpansionZoom( f.properties.cluster_id )' )
			&& false !== strpos( $s, '.setDOMContent( popupNode( f.properties ) )' ) && false !== strpos( $s, 'name.textContent = p.n' ) && false !== strpos( $s, 'function safeUrl( u )' )
			&& false === strpos( substr( $s, 0, strpos( $s, 'function loadPoints' ) ), 'innerHTML' ) && 0 === preg_match( '/supercluster|unpkg|cdn/i', $s );
	},
	$map_js, str_replace( 'cluster: true, clusterRadius: 46', 'cluster: false, clusterRadius: 46', $map_js ) );

tp441_mut( 'سبک نقشه: روز ← روشن، عصر و شب ← تیره (Protomaps `dark`)؛ تغییر حالت در جا با MutationObserver روی data-tp-mode: سبک عوض و نشانگرها از همان دادهٔ دریافت‌شده دوباره می‌آیند؛ برچسب به زبان صفحه (lang سبک)',
	function ( $s ) {
		return false !== strpos( $s, "return ( 'dark' === m || 'evening' === m ) ? 'dark' : 'light';" ) && false !== strpos( $s, "window.basemaps.namedFlavor( flavorOf() )" ) && false !== strpos( $s, 'var lay  = mapLayers( lang );' )
			&& false !== strpos( $s, "attributeFilter: [ 'data-tp-mode' ]" ) && false !== strpos( $s, "map.once( 'style.load', function () { addPoints( map, ml, box, interactive ); addBound( map, ml, box ); } );" );
	},
	$js2h, str_replace( "'evening' === m", "'x' === m", $js2h ) );
