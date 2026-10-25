<?php
/**
 * ادعاهای فاز ۲و SPRINT-4.4 (رابطه‌ها در قالب) — از assert.php پس از assert-2e.php بار می‌شود؛
 * PHP خالص با stub. هر کدام با جهش (tp441_mut).
 *
 *   ۲و٫۲  ثبت‌نامهٔ جایگاه رابطه (P5-D324 · P5-D336) · والد مکانی فقط در کارت «اطلاعات جغرافیایی»، بی کشور و بی همتا (P5-D339)
 *         مسیر راهنما = BreadcrumbList، بی کشور؛ کشور فقط در containedInPlace (D338) · باکس شهرهای مسیر زیر نقشه (P5-D340)
 *         «دربارهٔ …» بی نام نوعِ رندر، هر ۴۲ نوع و سه زبان (P5-D341)
 */

$relreg2f = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-relreg.php' );
$zones2f  = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );
$main2f   = file_get_contents( TP_PLUGIN_DIR . '/topersia-render.php' );
$css2f    = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-template.css' );
$js2f     = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-zones.js' );

echo "\nفاز ۲و — ۲و٫۲ ثبت‌نامهٔ رابطه · والد مکانی در اطلاعات جغرافیایی · مسیر راهنما · مسیر گردشگری · «دربارهٔ»  P5-D324 · D338 · P5-D339 · P5-D340 · P5-D341\n" . str_repeat( '─', 78 ) . "\n";

tp441_mut( 'ثبت‌نامه از جت‌انجین زنده ساخته می‌شود (همهٔ روابط فعال، هر دو سو) و صافی دارد؛ ماژول بار می‌شود و در هیچ متایی نمی‌نویسد (بی ذخیرهٔ موازی)',
	function ( $x ) {
		return false !== strpos( $x[0], "jet_engine()->relations->get_active_relations()" )
			&& false !== strpos( $x[0], "apply_filters( 'topersia_g_relreg'" )
			&& false === strpos( $x[0], 'update_post_meta' ) && false === strpos( $x[0], 'add_post_meta' )
			&& false !== strpos( $x[1], "'includes/topersia-g-relreg.php'" );
	},
	array( $relreg2f, $main2f ), array( str_replace( "jet_engine()->relations->get_active_relations()", 'array()', $relreg2f ), $main2f ) );

tp441_mut( 'هیچ کاشی‌ای در سربرگ نیست (P5-D339): نه تابع، نه فراخوانی، نه شیوه‌نامه؛ باکس «ارتباط‌ها» و گروه خودکار ستون کنار ساخته نشده (P5-D322)',
	function ( $x ) {
		return false === strpos( $x[0], 'topersia_g_header_tiles' ) && false === strpos( $x[0], 'tp-ctx' )
			&& false === strpos( $x[1], 'tp-ctx' ) && false === strpos( $x[2], 'tp-ctx' )
			&& false === strpos( $x[2], 'topersia_g_header_tiles' ) && false === strpos( $x[2], 'topersia_g_relreg_groups' );
	},
	array( $zones2f, $css2f, $relreg2f ), array( $zones2f . ' tp-ctx', $css2f, $relreg2f ) );

tp441_mut( 'کارت «اطلاعات جغرافیایی» کاشی‌های والد مکانی را می‌گیرد؛ مسیر راهنمای هر نوع از زنجیرهٔ ثبت‌نامه بی کشور می‌آید؛ BreadcrumbList همان را می‌خواند و containedInPlace با کشور می‌ماند',
	function ( $s ) {
		return false !== strpos( $s, "topersia_g_geo_parent_tiles( \$pid )" )
			&& false !== strpos( $s, "return topersia_g_crumb_places( \$pid );" )
			&& false !== strpos( $s, "/* BreadcrumbList = مسیر راهنمای HTML: بی کشور (D338) */" )
			&& false !== strpos( $s, "if ( \$ancestors && '' !== \$uid( \$ancestors[0] ) ) {" );
	},
	$zones2f, str_replace( "return topersia_g_crumb_places( \$pid );", '', $zones2f ) );

tp441_mut( 'پیوند بخش ستون کناریِ چسبان: اگر در دید است اسکرول نمی‌کند، وگرنه یک‌بار و بی تعقیب (بی تعقیبِ بی‌پایانِ هدفی که هرگز بالای نوار نمی‌رسد)',
	function ( $s ) {
		return false !== strpos( $s, "'sticky' === window.getComputedStyle( p ).position" )
			&& false !== strpos( $s, 'if ( aim < 0 || ( ! pin && Math.abs( y - aim ) > 2 ) ) {' )
			&& false !== strpos( $s, 'r.top >= by && r.bottom <= window.innerHeight' );
	},
	$js2f, str_replace( 'if ( aim < 0 || ( ! pin && Math.abs( y - aim ) > 2 ) ) {', 'if ( Math.abs( y - aim ) > 2 ) {', $js2f ) );

/* ثبت‌نامهٔ پنل چپ (includes/topersia-g-panel.php) جای باکس شهرهای مسیر را می‌دهد؛ دیگر قلابی نیست (P5-D347) */

/* ── دنیای آزمون: کشور ۸۰۰۱ ← استان ۸۰۰۲ ← شهرستان (بی‌صفحه) ۸۰۰۳ ← شهر ۸۰۰۴ ← روستا ۸۰۰۹؛ آب ۸۰۰۵؛ بنا ۸۰۰۶؛ همجوار ۸۰۰۷ */
$f2_keep_posts = $GLOBALS['tp_posts'];
$f2_keep_rel   = $GLOBALS['tp_rel'];
$f2_keep_meta  = $GLOBALS['tp_meta'];
$f2_keep_flt   = isset( $GLOBALS['tp_filters']['topersia_g_relreg'] ) ? $GLOBALS['tp_filters']['topersia_g_relreg'] : null;

$f2_post = function ( $id, $type, $title, $extra = array() ) {
	$GLOBALS['tp_posts'][ $id ] = array_merge( array( 'type' => $type, 'title' => $title, 'url' => 'https://topersia.com/p' . $id . '/' ), $extra );
};
/* برچسب‌ها مثل جت‌انجین: parent_page_control_title نام فرزند را می‌گوید (کنترل روی صفحهٔ والد)، child_page_control_title نام والد را */
$f2_reg = array(
	7001 => array( 'id' => 7001, 'name' => 'country_province', 'type' => 'one_to_many', 'parent' => 'tp_country', 'child' => 'tp_province', 'parent_label' => 'استان', 'child_label' => 'کشور' ),
	7002 => array( 'id' => 7002, 'name' => 'province_county', 'type' => 'one_to_many', 'parent' => 'tp_province', 'child' => 'tp_county', 'parent_label' => 'شهرستان', 'child_label' => 'استان' ),
	7003 => array( 'id' => 7003, 'name' => 'county_city', 'type' => 'one_to_many', 'parent' => 'tp_county', 'child' => 'tp_city', 'parent_label' => 'شهر', 'child_label' => 'شهرستان' ),
	7004 => array( 'id' => 7004, 'name' => 'province_water', 'type' => 'one_to_many', 'parent' => 'tp_province', 'child' => 'tp_water_body', 'parent_label' => 'پهنهٔ آبی', 'child_label' => 'استان' ),
	7005 => array( 'id' => 7005, 'name' => 'province_neighbour', 'type' => 'many_to_many', 'parent' => 'tp_province', 'child' => 'tp_province', 'parent_label' => 'استان همجوار', 'child_label' => 'استان همجوار' ),
	7006 => array( 'id' => 7006, 'name' => 'city_monument_assoc', 'type' => 'many_to_many', 'parent' => 'tp_city', 'child' => 'tp_monument', 'parent_label' => 'بنای مرتبط', 'child_label' => 'شهر مرتبط' ),
	7008 => array( 'id' => 7008, 'name' => 'city_village', 'type' => 'one_to_many', 'parent' => 'tp_city', 'child' => 'tp_village', 'parent_label' => 'روستا', 'child_label' => 'شهر' ),
);
$f2_world = function ( $reg ) use ( $f2_post ) {
	$GLOBALS['tp_posts'] = array();
	$GLOBALS['tp_meta']  = array();
	$f2_post( 8001, 'tp_country', 'ایران' );
	$f2_post( 8002, 'tp_province', 'اصفهان' );
	$f2_post( 8003, 'tp_county', 'شهرستان آزمون' );
	$f2_post( 8004, 'tp_city', 'شهر آزمون' );
	$f2_post( 8005, 'tp_water_body', 'تالاب آزمون' );
	$f2_post( 8006, 'tp_monument', 'بنای آزمون' );
	$f2_post( 8007, 'tp_province', 'استان همجوار' );
	$f2_post( 8009, 'tp_village', 'روستای آزمون' );
	foreach ( array( 8002, 8004, 8005, 8009 ) as $pid ) {
		$GLOBALS['tp_meta'][ $pid ]['climate_type'] = 'نیمه‌خشک';   /* فیلد ناحیهٔ card ← کارت «اطلاعات جغرافیایی» هست */
	}
	$GLOBALS['tp_rel'] = array(
		'7001' => array( 'children' => array( 8001 => array( 8002, 8007 ) ), 'parents' => array( 8002 => array( 8001 ), 8007 => array( 8001 ) ) ),
		'7002' => array( 'children' => array( 8002 => array( 8003 ) ), 'parents' => array( 8003 => array( 8002 ) ) ),
		'7003' => array( 'children' => array( 8003 => array( 8004 ) ), 'parents' => array( 8004 => array( 8003 ) ) ),
		'7004' => array( 'children' => array( 8002 => array( 8005 ) ), 'parents' => array( 8005 => array( 8002 ) ) ),
		'7005' => array( 'children' => array( 8002 => array( 8007 ) ), 'parents' => array( 8007 => array( 8002 ) ) ),
		'7006' => array( 'children' => array( 8004 => array( 8006 ) ), 'parents' => array( 8006 => array( 8004 ) ) ),
		'7008' => array( 'children' => array( 8004 => array( 8009 ) ), 'parents' => array( 8009 => array( 8004 ) ) ),
		'96'   => array( 'children' => array( 8002 => array( 8004 ) ), 'parents' => array( 8004 => array( 8002 ) ) ),
	);
	$GLOBALS['tp_filters']['topersia_g_relreg'] = array( function () use ( $reg ) { return $reg; } );
};
$f2_geo = function ( $pid ) {
	preg_match( '#<section id="tp-geo".*?</section>#us', r( $pid, 'tp_page' ), $m );
	return isset( $m[0] ) ? $m[0] : '';
};
$f2_tile_names = function ( $html ) {
	preg_match_all( '#<a href="([^"]*)" class="tp-tile"><span class="tp-tile__k">([^<]*)</span><b>([^<]*)</b></a>#u', $html, $m, PREG_SET_ORDER );
	return array_map( function ( $x ) { return $x[2] . ':' . $x[3] . ':' . basename( rtrim( $x[1], '/' ) ); }, $m );
};

/* دستهٔ ۲۶ · F-05: کاشی‌های والد مکانی (P5-D339، بی کشور) جدا از پرکردن تا سه ردیف سنجیده می‌شوند — پرکردن این‌جا خاموش، پایین‌تر جدا */
$f2_keep_fill = $GLOBALS['tp_filters']['topersia_g_geo_fill'] ?? null;
$GLOBALS['tp_filters']['topersia_g_geo_fill'] = array( function () { return false; } );

$f2_world( $f2_reg );
$f2_water = $f2_tile_names( $f2_geo( 8005 ) );
$f2_city  = $f2_tile_names( $f2_geo( 8004 ) );
$f2_prov  = $f2_tile_names( $f2_geo( 8002 ) );
$f2_vill  = $f2_tile_names( $f2_geo( 8009 ) );

tp441_mut( 'گاوخونی (آب): تنها کاشی والد مکانی «استان» از همان رابطهٔ یک‌به‌چند در کارت «اطلاعات جغرافیایی»، پیش از کاشی‌های حقیقت؛ کشور نه، همتا (استان همجوار) نه؛ در کارت نه در سربرگ',
	function ( $x ) {
		return isset( $x[0][0] ) && 'استان:اصفهان:p8002' === $x[0][0] && ! preg_grep( '/p8001|p8007|ایران/u', $x[0] )
			&& false === strpos( $x[1], 'tp-ctx' ) && 1 === count( preg_grep( '/^استان:/u', $x[0] ) );
	},
	array( $f2_water, r( 8005, 'tp_hero' ) ), array( array( 'کشور:ایران:p8001', 'استان:اصفهان:p8002' ), '<nav class="tp-ctx">' ) );

tp441_mut( 'شهر: کاشی استان با برچسب «مرکز استان» اگر مرکز است (شهرستان بی‌صفحه و کشور نه)؛ روستا: کاشی‌های استان و شهر از زنجیره، به ترتیب استان ← شهر؛ استان: کاشی والد ندارد (والدش کشور است)',
	function ( $x ) {
		return isset( $x[0][0] ) && 'مرکز استان:اصفهان:p8002' === $x[0][0] && 1 === count( preg_grep( '/p8002/', $x[0] ) ) && ! preg_grep( '/p8003|p8001/', $x[0] )
			&& array( 'استان:اصفهان:p8002', 'شهر:شهر آزمون:p8004' ) === array_slice( $x[1], 0, 2 )
			&& ! preg_grep( '/p8001|p8007|p8003/', $x[2] );
	},
	array( $f2_city, $f2_vill, $f2_prov ), array( array( 'کشور:ایران:p8001' ), array(), array( 'کشور:ایران:p8001' ) ) );

/* نوع دارای والد مکانی ولی بی فیلد ناحیهٔ card ← کارت ساخته می‌شود، با فقط کاشی والد (فیلد دیگری نمی‌آید) — P5-D342 */
$f2_world( $f2_reg );
unset( $GLOBALS['tp_meta'][8005]['climate_type'] );
$f2_nocard = array( $f2_geo( 8005 ), $f2_tile_names( $f2_geo( 8005 ) ) );
tp441_mut( 'نوعی که والد مکانی دارد ولی فیلد card ندارد (گاوخونی، شهر، روستا، کوه، بیابان): کارت «اطلاعات جغرافیایی» ساخته می‌شود و فقط کاشی والد دارد؛ کاشی دیگری نمی‌آید',
	function ( $x ) { return '' !== $x[0] && array( 'استان:اصفهان:p8002' ) === $x[1]; },
	$f2_nocard, array( '', array() ) );

/* مرکزیت: استان ← «مرکز استان: شهر» از رابطهٔ ۹۶ (کاشی با پیوند)؛ شهرِ مرکز استان ← برچسب روی کاشی استان؛ مرکز شهرستان فقط با داده (۹۷)، بی پیوند */
$f2_world( $f2_reg );
$GLOBALS['tp_rel']['97'] = array( 'children' => array( 8003 => array( 8004 ) ), 'parents' => array( 8004 => array( 8003 ) ) );
$f2_center_prov = $f2_tile_names( $f2_geo( 8002 ) );
$f2_center_city = $f2_geo( 8004 );
$f2_center_tiles = array_map( function ( $t ) { return $t['label'] . ':' . $t['name'] . ':' . ( '' === $t['url'] ? '-' : 'link' ); }, topersia_g_geo_center_tiles( 8004 ) );
$f2_world( $f2_reg );
$f2_center_none = topersia_g_geo_center_tiles( 8004 );
tp441_mut( 'مرکزیت در همان کارت: صفحهٔ استان کاشی «مرکز استان: شهر آزمون» با پیوند؛ شهرِ مرکز استان ← برچسب «مرکز استان» روی کاشی استان؛ مرکز شهرستان فقط وقتی داده دارد (بی پیوند چون شهرستان صفحه ندارد)',
	function ( $x ) {
		return in_array( 'مرکز استان:شهر آزمون:p8004', $x[0], true ) && false !== strpos( $x[1], 'tp-tile__k">مرکز استان</span><b>اصفهان</b>' )
			&& array( 'مرکز شهرستان:شهرستان آزمون:-' ) === $x[2] && array() === $x[3];
	},
	array( $f2_center_prov, $f2_center_city, $f2_center_tiles, $f2_center_none ), array( array(), '', array(), array( 'x' ) ) );

/* ── مسیر راهنما = BreadcrumbList (D338) ─────────────────────────────── */
$f2_world( $f2_reg );
$f2_crumb = function ( $pid ) {
	$html = r( $pid, 'tp_crumbs' );
	preg_match_all( '#<a href="([^"]*)">([^<]*)</a>#u', $html, $m, PREG_SET_ORDER );
	return array_map( function ( $x ) { return $x[2]; }, array_slice( $m, 1 ) );   /* بی «ToPersia» ریشه */
};
$f2_ld = function ( $pid ) {
	$graph = array(
		array( '@type' => 'BreadcrumbList', 'itemListElement' => array(
			array( '@type' => 'ListItem', 'position' => 1, 'name' => 'ToPersia', 'item' => 'https://topersia.com/' ),
			array( '@type' => 'ListItem', 'position' => 2, 'name' => topersia_g_name( $pid ) ),
		) ),
		array( '@type' => 'Place', 'containedInPlace' => array( '@id' => 'x' ) ),
	);
	return topersia_g_schema_graph( $graph, $pid, array() );
};
$f2_ldn = function ( $g ) { return array_map( function ( $i ) { return $i['name']; }, array_slice( $g[0]['itemListElement'], 1, -1 ) ); };
$f2_ld_water    = $f2_ld( 8005 );
$f2_ld_prov     = $f2_ld( 8002 );
$f2_ld_vill     = $f2_ld( 8009 );
$f2_chain_city  = topersia_g_place_chain( 8004 );
$f2_places_city = topersia_g_crumb_places( 8004 );

tp441_mut( 'مسیر راهنما: خانه › استان › … › صفحه، بی کشور و بی شهرستان بی‌صفحه؛ BreadcrumbList همان نام‌ها به همان ترتیب؛ زنجیرهٔ کامل (با کشور) فقط در مدل است',
	function ( $x ) {
		return array( 'اصفهان' ) === $x[0] && $x[0] === $x[1] && array( 'اصفهان', 'شهر آزمون' ) === $x[2] && $x[2] === $x[3]
			&& array() === $x[4] && array( 8001, 8002 ) === $x[5] && array( 8002 ) === $x[6];
	},
	array( $f2_crumb( 8005 ), $f2_ldn( $f2_ld_water ), $f2_crumb( 8009 ), $f2_ldn( $f2_ld_vill ), $f2_crumb( 8002 ), $f2_chain_city, $f2_places_city ),
	array( array( 'ایران', 'اصفهان' ), array( 'اصفهان' ), array( 'اصفهان', 'شهر آزمون' ), array( 'اصفهان', 'شهر آزمون' ), array(), array( 8001, 8002 ), array( 8002 ) ) );

tp441_mut( 'کشور فقط در containedInPlace اسکیما می‌ماند (نامرئی): آب ← استان، استان ← کشور؛ در هیچ BreadcrumbList نیست',
	function ( $x ) {
		return array( '@id' => topersia_schema_uid( 8002 ) ) === $x[0][1]['containedInPlace'] && array( '@id' => topersia_schema_uid( 8001 ) ) === $x[1][1]['containedInPlace']
			&& false === strpos( wp_json_encode( $x[1][0] ), 'ایران' ) && false === strpos( wp_json_encode( $x[0][0] ), '/p8001/' );
	},
	array( $f2_ld_water, $f2_ld_prov ), array( $f2_ld_prov, $f2_ld_water ) );

/* پیش‌نویس ← نه در مسیر راهنما، نه در کاشی؛ بی والد ← بی زنجیره، بی کاشی، بی حدس */
$f2_world( $f2_reg );
$GLOBALS['tp_posts'][8002]['status'] = 'draft';
$f2_draft = array( $f2_crumb( 8005 ), $f2_tile_names( $f2_geo( 8005 ) ) );
$f2_world( $f2_reg );
$GLOBALS['tp_rel']['7004'] = array( 'children' => array(), 'parents' => array() );
$f2_gap = array( $f2_crumb( 8005 ), topersia_g_place_chain( 8005 ), $f2_tile_names( $f2_geo( 8005 ) ) );
tp441_mut( 'فقط منتشرشده (P5-D247): والد پیش‌نویس نه در مسیر راهنما نه در کاشی؛ نبودِ والد مکانی = بی زنجیره، بی کاشی، بی حدس (شکاف گزارش می‌شود)',
	function ( $x ) { return array() === $x[0][0] && array() === $x[0][1] && array() === $x[1][0] && array() === $x[1][1] && array() === $x[1][2]; },
	array( $f2_draft, $f2_gap ), array( array( array( 'اصفهان' ), array( 'استان:اصفهان:p8002' ) ), array( array( 'ایران' ), array( 8001 ), array( 'x' ) ) ) );

/* ── مسیر گردشگری: باکس شهرها زیر نقشه (P5-D340) ───────────────────── */
/* topersia_p9_stops را assert-442 با `$GLOBALS['tp442_stops']` تعریف کرده؛ همان قرارداد هستهٔ زنده (position·id·title·url·note) */
if ( ! function_exists( 'topersia_p9_stops' ) ) {
	function topersia_p9_stops( $route_id = null ) { return isset( $GLOBALS['tp442_stops'] ) ? $GLOBALS['tp442_stops'] : array(); }
}
$f2_keep_stops = isset( $GLOBALS['tp442_stops'] ) ? $GLOBALS['tp442_stops'] : null;
$f2_world( $f2_reg );
$f2_post( 8300, 'tp_route', 'مسیر آزمون' );
$f2_post( 8301, 'tp_city', 'شهر سوم', array( 'status' => 'draft' ) );
$f2_post( 8302, 'tp_city', 'شهر دوم' );
$GLOBALS['tp_meta'][8300]['geo_coordinates'] = '32.5, 52.5';
$GLOBALS['tp442_stops'] = array(
	array( 'position' => 1, 'id' => 8302, 'title' => 'شهر دوم', 'url' => 'https://topersia.com/p8302/', 'note' => '' ),
	array( 'position' => 2, 'id' => 8301, 'title' => 'شهر سوم', 'url' => 'https://topersia.com/p8301/', 'note' => '' ),
	array( 'position' => 3, 'id' => 8004, 'title' => 'شهر آزمون', 'url' => 'https://topersia.com/p8004/', 'note' => '' ),
);
$f2_route_box  = r( 8300, 'tp_route_cities' );
$f2_route_page = r( 8300, 'tp_page' );
$GLOBALS['tp442_stops'] = array();
$f2_route_none = r( 8300, 'tp_route_cities' );
preg_match( '#<aside class="tp-rail".*?</aside>#us', $f2_route_page, $f2_rail );
$f2_rail = isset( $f2_rail[0] ) ? $f2_rail[0] : '';
tp441_mut( 'مسیر گردشگری: باکس «شهرهای مسیر» به ترتیب مسیر با شماره و پیوند، فقط منتشرشده‌ها (پیش‌نویس نه)، زیر نقشه در ستون کناری؛ بی ایستگاه ← بی باکس؛ نوع دیگر باکس ندارد',
	function ( $x ) {
		return false !== strpos( $x[0], '<a href="https://topersia.com/p8302/" class="tp-tile"><span class="tp-tile__k">۱</span><b>شهر دوم</b></a><a href="https://topersia.com/p8004/" class="tp-tile"><span class="tp-tile__k">۲</span><b>شهر آزمون</b></a>' )
			&& false === strpos( $x[0], 'p8301' ) && '' === $x[1]
			&& false !== strpos( $x[2], 'id="tp-route-cities"' ) && ( false === strpos( $x[2], 'id="tp-map"' ) || strpos( $x[2], 'id="tp-map"' ) < strpos( $x[2], 'id="tp-route-cities"' ) )
			&& false === strpos( $x[3], 'id="tp-route-cities"' );
	},
	array( $f2_route_box, $f2_route_none, $f2_rail, r( 8004, 'tp_page' ) ), array( '<a href="https://topersia.com/p8301/" class="tp-tile">', 'x', '', 'id="tp-route-cities"' ) );

/* ── «دربارهٔ …» بی نام نوع — هر ۴۲ نوع، سه زبان (P5-D341) ────────────── */
$f2_kinds = array_keys( topersia_g_kind_labels() );
$f2_about = array();
foreach ( array( 'fa', 'en', 'ar' ) as $lang ) {
	foreach ( $f2_kinds as $n => $type ) {
		$pid = 8500 + $n;
		$f2_post( $pid, $type, 'عنوان آزمون ' . $n );
		$GLOBALS['tp_meta'][ $pid ] = array( 'name_fa' => 'نام آزمون', 'name_en' => 'Test Name', 'name_ar' => 'اسم اختبار', 'overview_text' => '<p>Overview ' . $n . '</p>' );
		$f2_about[ $lang ][ $type ] = tp442_lang( $lang, function () use ( $pid ) {
			preg_match( '#<section id="tp-overview"[^>]*><h2>([^<]*)</h2>#u', r( $pid, 'tp_overview' ), $m );
			return array( isset( $m[1] ) ? html_entity_decode( $m[1] ) : null, topersia_g_kind_label( $pid ), sprintf( topersia_g_t( 'دربارۀ %s' ), topersia_g_heading( $pid ) ) );
		} );
	}
}
$f2_about_bad = array();
foreach ( $f2_about as $lang => $rows ) {
	foreach ( $rows as $type => $row ) {
		if ( null === $row[0] || $row[0] !== $row[2] || ( '' !== $row[1] && false !== strpos( $row[0], $row[1] ) ) ) {
			$f2_about_bad[] = $lang . ':' . $type . ' → ' . json_encode( $row, JSON_UNESCAPED_UNICODE );
		}
	}
}
/* نامی که نوعِ خودش را دارد بریده نمی‌شود */
$f2_post( 8599, 'tp_province', 'استان تهران' );
$GLOBALS['tp_meta'][8599] = array( 'name_fa' => 'استان تهران', 'overview_text' => '<p>Overview</p>' );
preg_match( '#<section id="tp-overview"[^>]*><h2>([^<]*)</h2>#u', r( 8599, 'tp_overview' ), $f2_keep_h );
$f2_n = count( $f2_kinds );
tp441_mut( 'عنوان «دربارهٔ …»: فقط نام (همان H1)، بی نام نوعی که رندر اضافه می‌کرد — در هر ۴۲ نوع و fa · en · ar؛ نامی که خودش نوع را دارد («استان تهران») بریده نمی‌شود',
	function ( $x ) { return 42 === $x[0] && array() === $x[1] && 'دربارۀ استان تهران' === html_entity_decode( $x[2] ); },
	array( $f2_n, $f2_about_bad, isset( $f2_keep_h[1] ) ? $f2_keep_h[1] : '' ), array( 42, array( 'x' ), 'دربارۀ تهران' ), implode( ' | ', array_slice( $f2_about_bad, 0, 2 ) ) );

/* برگرداندن دنیای آزمون */
/* F-05: پرکردن روشن — گاوخونی (یک کاشی استان) به سه ردیف می‌رسد: کشور از زنجیره، سپس مشتق‌ها؛ هیچ «—»؛ جهش: پرکردن خاموش ← یک ردیف */
$f2_world( $f2_reg );
unset( $GLOBALS['tp_filters']['topersia_g_geo_fill'] );
$f2_fill_on = $f2_geo( 8005 );
$GLOBALS['tp_filters']['topersia_g_geo_fill'] = array( function () { return false; } );
$f2_fill_off = $f2_geo( 8005 );
$f2_rows = function ( $h ) { return substr_count( $h, 'class="tp-tile"' ); };
t( 'اطلاعات جغرافیایی (F-05): کمتر از سه ردیف ← ردیف‌های مشتق تا سه (استان، سپس کشور …)، بی «—» — جهش: بی پرکردن کمتر از سه',
	$f2_rows( $f2_fill_on ) >= 3 && false !== strpos( $f2_fill_on, '>استان</span><b>اصفهان' ) && false !== strpos( $f2_fill_on, '<b>ایران</b>' )
	&& false === strpos( $f2_fill_on, ">—<" ) && $f2_rows( $f2_fill_off ) < 3, $f2_rows( $f2_fill_on ) . '/' . $f2_rows( $f2_fill_off ) );
if ( null === $f2_keep_fill ) { unset( $GLOBALS['tp_filters']['topersia_g_geo_fill'] ); } else { $GLOBALS['tp_filters']['topersia_g_geo_fill'] = $f2_keep_fill; }

if ( null === $f2_keep_stops ) { unset( $GLOBALS['tp442_stops'] ); } else { $GLOBALS['tp442_stops'] = $f2_keep_stops; }
$GLOBALS['tp_posts'] = $f2_keep_posts;
$GLOBALS['tp_rel']   = $f2_keep_rel;
$GLOBALS['tp_meta']  = $f2_keep_meta;
if ( null === $f2_keep_flt ) { unset( $GLOBALS['tp_filters']['topersia_g_relreg'] ); } else { $GLOBALS['tp_filters']['topersia_g_relreg'] = $f2_keep_flt; }
