<?php
/**
 * ادعاهای مرز OSM — P5-D363 · بی شبکه، با پاسخ‌های ساختگی
 * از assert.php پس از assert-2m.php بار می‌شود؛ PHP خالص، هر کدام با جهش. شبکه فقط در scripts/tp-boundaries.php (fetch · build).
 */

require_once dirname( TP_PLUGIN_DIR, 2 ) . '/scripts/lib/tp-boundaries-lib.php';

echo "\nمرز OSM — تطبیق آفلاین روی کش، حلقه‌ها، استان با نام و نگهبان‌ها  P5-D363\n" . str_repeat( '─', 78 ) . "\n";

$bsq = function ( $x0, $y0, $x1, $y1 ) { return json_encode( array( 'type' => 'Feature', 'geometry' => array( 'type' => 'MultiPolygon', 'coordinates' => array( array( array( array( $x0, $y0 ), array( $x1, $y0 ), array( $x1, $y1 ), array( $x0, $y1 ), array( $x0, $y0 ) ) ) ) ) ) ); };
$bP1 = $bsq( 50, 30, 52, 32 );     /* استان یک */
$bP2 = $bsq( 54, 30, 56, 32 );     /* استان دو */
$bEl = function ( $type, $id, $tags, $lon, $lat ) { return array( 'type' => $type, 'id' => $id, 'tags' => $tags, 'center' => array( 'lon' => $lon, 'lat' => $lat ) ); };
$bAdm = function ( $id, $name, $lon, $lat ) use ( $bEl ) { return $bEl( 'relation', $id, array( 'boundary' => 'administrative', 'admin_level' => '8', 'name' => $name, 'name:fa' => $name ), $lon, $lat ); };

/* شهر هم‌نام در دو استان */
$bTwo = array( $bAdm( 11, 'نجف‌آباد', 51, 31 ), $bAdm( 22, 'نجف‌آباد', 55, 31 ) );
list( $s1, $e1 ) = tpb_classify_city( 'نجف‌آباد', $bTwo, $bP1 );
list( $s2, $e2 ) = tpb_classify_city( 'نجف‌آباد', $bTwo, $bP2 );
list( $s3, $e3 ) = tpb_classify_city( 'نجف‌آباد', array( $bAdm( 33, 'نجف‌آباد', 70, 10 ) ), $bP1 );
t( 'مرز: شهر هم‌نام در دو استان — فقط نامزد درون استان والد (۱۱ در استان یک، ۲۲ در استان دو)؛ بیرون هر دو ← none',
	'auto' === $s1 && 11 === $e1['id'] && 'auto' === $s2 && 22 === $e2['id'] && 'none' === $s3 && null === $e3, json_encode( array( $s1, $s2, $s3 ) ) );

/* بستر رود هم‌نام شهر و نقطهٔ place */
$bRiver = $bEl( 'way', 5, array( 'waterway' => 'riverbank', 'natural' => 'water', 'name' => 'زاینده رود' ), 51, 31 );
$bPoint = array( 'type' => 'node', 'id' => 6, 'lat' => 31, 'lon' => 51, 'tags' => array( 'place' => 'city', 'name' => 'زاینده رود' ) );
list( $r1 ) = tpb_classify_city( 'زاینده رود', array( $bRiver, $bPoint ), $bP1 );
list( $r2, $re2 ) = tpb_classify_city( 'زاینده رود', array( $bRiver, $bAdm( 7, 'زاینده رود', 51, 31 ) ), $bP1 );
t( 'مرز: بستر رود هم‌نام شهر و نقطهٔ place نامزد نیستند؛ فقط مرز اداری یا چندضلعی place=city|town',
	'none' === $r1 && 'auto' === $r2 && 7 === $re2['id'], json_encode( array( $r1, $r2 ) ) );

/* چند نامزد · والد بی GeoJSON · گره‌گشای مرز اداری */
$bTown = $bEl( 'way', 8, array( 'place' => 'town', 'name' => 'گلشهر' ), 51.1, 31.1 );
list( $m1 ) = tpb_classify_city( 'گلشهر', array( $bAdm( 9, 'گلشهر', 51, 31 ), $bAdm( 10, 'گلشهر', 51.5, 31.5 ) ), $bP1 );
list( $m2, , $m2h ) = tpb_classify_city( 'گلشهر', array( $bAdm( 9, 'گلشهر', 51, 31 ) ), '' );
list( $m3, $me3 ) = tpb_classify_city( 'گلشهر', array( $bAdm( 9, 'گلشهر', 51, 31 ), $bTown ), $bP1 );
list( $m4 ) = tpb_classify_city( 'گلشهر', array(), $bP1 );
t( 'مرز: دو مرز اداری درون استان ← suggested؛ GeoJSON والد نبود ← suggested (راستی‌آزمایی نشد)؛ مرز اداری + چندضلعی place هم‌شهر ← auto با مرز اداری؛ بی نامزد ← none',
	'suggested' === $m1 && 'suggested' === $m2 && false !== strpos( $m2h, 'parent-province-geojson-missing' ) && 'auto' === $m3 && 9 === $me3['id'] && 'none' === $m4, json_encode( array( $m1, $m2, $m3, $m4 ) ) );

/* استان با نام (P5-D363 اصلاحیه) — کدگذاری تازهٔ OSM: البرز IR-30، خراسان شمالی IR-28؛ ISO از خود OSM خوانده می‌شود، نه از حافظه */
$bPr   = function ( $id, $iso, $name, $lvl = '4' ) { return array( 'type' => 'relation', 'id' => $id, 'tags' => array( 'boundary' => 'administrative', 'admin_level' => $lvl, 'ISO3166-2' => $iso, 'name' => $name, 'name:fa' => $name ) ); };
$bOsm  = array( $bPr( 1015996, 'IR-30', 'استان البرز' ), $bPr( 225444, 'IR-28', 'استان خراسان شمالی' ), $bPr( 241593, 'IR-04', 'استان اصفهان' ), $bPr( 900, 'QA-US', 'مرکزی' ), $bPr( 901, 'IR-22', 'استان مرکزی' ), $bPr( 902, 'IR-24', 'استان همدان' ), $bPr( 903, 'IR-24', 'استان همدان' ), $bPr( 904, 'IR-08', 'استان چهارمحال و بختیاری' ), $bPr( 905, 'IR-99', 'استان اصفهان', '6' ), $bPr( 906, 'IR-17', 'استان کهگیلویه و بویر احمد' ) );
list( $p1, $pe1, , $pi1 ) = tpb_province_by_name( 'البرز', $bOsm );
list( $p2, $pe2, , $pi2 ) = tpb_province_by_name( 'خراسان شمالی', $bOsm );
list( $p3, $pe3 ) = tpb_province_by_name( 'مرکزی', $bOsm );
list( $p4 ) = tpb_province_by_name( 'همدان', $bOsm );
list( $p5 ) = tpb_province_by_name( 'قم', $bOsm );
list( $p6, $pe6 ) = tpb_province_by_name( "چهارمحال\u{200C} و بختیاری", $bOsm );
list( $p7, $pe7 ) = tpb_province_by_name( 'اصفهان', $bOsm );
list( $p8, $pe8 ) = tpb_province_by_name( 'کهگیلویه و بویراحمد', $bOsm );
t( 'مرز: استان با نام، بی کد — «البرز» ← رابطهٔ 1015996 با ISO3166-2 خودِ OSM = IR-30 (نه IR-32 حافظه)، «خراسان شمالی» ← 225444 با IR-28؛ نام با و بی «استان»؛ نیم‌فاصله و فاصلهٔ جداشدهٔ «بویر احمد»؛ فقط رابطهٔ admin_level=4 با ISO ایرانی (مرکزی قطر و سطح ۶ اصفهان نامزد نیستند)',
	'auto' === $p1 && 1015996 === $pe1['id'] && 'IR-30' === $pi1 && 'auto' === $p2 && 225444 === $pe2['id'] && 'IR-28' === $pi2 && 'auto' === $p3 && 901 === $pe3['id']
	&& 'auto' === $p6 && 904 === $pe6['id'] && 'auto' === $p7 && 241593 === $pe7['id'] && 'auto' === $p8 && 906 === $pe8['id'], json_encode( array( $p1, $pi1, $p2, $pi2, $p3, $p6, $p7 ) ) );
t( 'مرز: نام دوپهلو (دو رابطهٔ ایرانی با همان نام) ← none؛ نامِ بی رابطه ← none؛ هیچ‌وقت حدس',
	'none' === $p4 && 'none' === $p5, json_encode( array( $p4, $p5 ) ) );

/* نگهبان ۱: شناسهٔ تکراری · نگهبان ۲: مرکز استان درون چندضلعی */
$bRows = array( 'a' => array( 'osm_id' => '1015996' ), 'b' => array( 'osm_id' => '1015996' ), 'c' => array( 'osm_id' => '241593' ), 'd' => array( 'osm_id' => '' ), 'e' => array( 'osm_id' => '' ) );
$bDup  = tpb_duplicate_ids( $bRows );
$bSq   = $bsq( 50, 30, 52, 32 );
t( 'مرز: نگهبان ۱ — شناسهٔ OSM تکراری برای دو استان گرفته می‌شود (۱۰۱۵۹۹۶ برای a و b)؛ شناسهٔ یکتا و خالی‌ها نه',
	array( 1015996 ) === array_keys( $bDup ) && array( 'a', 'b' ) === $bDup['1015996'], json_encode( $bDup ) );
t( 'مرز: نگهبان ۲ — مرز مرکز استان: «۳۱٫۵, ۵۱» درون ← true؛ «۳۱٫۵, ۶۰» بیرون ← false؛ مختصات خراب ← null (نه حکم)',
	true === tpb_center_inside( '31.5, 51', $bSq ) && false === tpb_center_inside( '31.5, 60', $bSq ) && null === tpb_center_inside( 'x,y', $bSq ) && null === tpb_center_inside( '', $bSq ), '' );

/* حلقه‌ها: ترتیب و جهت دلخواه · شکاف کوچک · رابطهٔ شکسته با علت */
$A = array( array( 0, 0 ), array( 1, 0 ) ); $B = array( array( 1, 0 ), array( 1, 1 ) ); $C = array( array( 1, 1 ), array( 0, 1 ) ); $D = array( array( 0, 1 ), array( 0, 0 ) );
$rg     = tpb_ring( array( $C, array_reverse( $A ), $D, array_reverse( $B ) ), $w1 );
$gapped = tpb_ring( array( $A, $B, $C, array( array( 0, 1 ), array( 0.0002, 0.0001 ) ) ), $w2 );
$broken = tpb_ring( array( $A, $B, $C ), $w3 );      /* ضلع چهارم گم */
$noway  = tpb_ring( array(), $w4 );
$two    = tpb_ring( array( $A, $B, $C, $D, array( array( 5, 5 ), array( 6, 5 ), array( 6, 6 ), array( 5, 5 ) ) ), $w5 );
t( 'مرز: tpb_ring — چهار راه با ترتیب و جهت درهم ← یک حلقهٔ بسته؛ شکاف زیر ۵۰ متر بسته می‌شود؛ رابطهٔ شکسته ← هیچ حلقه و علت open_ring با شکاف و شمار نقطه؛ بی راه ← no_member_ways؛ دو حلقهٔ جدا ← دو حلقه',
	1 === count( $rg ) && 5 === count( $rg[0] ) && 1 === count( $gapped ) && array() === $broken && (bool) preg_grep( '/^open_ring:gap_m=\d+:points=\d+$/', $w3 ) && array() === $noway && in_array( 'no_member_ways', $w4, true ) && 2 === count( $two ),
	json_encode( array( count( $rg ), count( $gapped ), count( $broken ), $w3, count( $two ) ) ) );

/* عنصر relation: عضو بی geometry شمرده می‌شود */
$bRel = array( 'type' => 'relation', 'id' => 1, 'members' => array(
	array( 'type' => 'way', 'role' => 'outer', 'geometry' => array( array( 'lon' => 0, 'lat' => 0 ), array( 'lon' => 1, 'lat' => 0 ) ) ),
	array( 'type' => 'way', 'role' => 'outer' ),
	array( 'type' => 'way', 'role' => 'inner', 'geometry' => array( array( 'lon' => 0, 'lat' => 0 ), array( 'lon' => 1, 'lat' => 1 ) ) ),
	array( 'type' => 'node', 'role' => 'admin_centre' ),
) );
$bways = tpb_element_ways( $bRel, $bmiss );
t( 'مرز: tpb_element_ways — فقط راه‌های outer و بی‌نقش؛ inner و گره کنار؛ عضو بی geometry شمرده می‌شود (علت شکست build)', 1 === count( $bways ) && 1 === $bmiss );

$bsrc = file_get_contents( dirname( TP_PLUGIN_DIR, 2 ) . '/scripts/tp-boundaries.php' );
tp441_mut( 'مرز: اسکریپت — match آفلاین روی کش (overpass-iran.json)؛ build دسته‌ای و پاسخ خام شکست در کش؛ پهنهٔ طبیعی skip؛ confirmed بازنویسی نمی‌شود؛ POST urlencoded (چندبخشی ۴۰۰)؛ یک پرس‌وجو با out tags center',
	function ( $x ) {
		return false !== strpos( $x, "'overpass-iran.json'" ) && false !== strpos( $x, "\$row['status'] = 'skip'" )
			&& false !== strpos( $x, "'confirmed' === \$old[ \$uid ]['status']" ) && false !== strpos( $x, "'fail/' . str_replace( ':', '-', \$k )" ) && false !== strpos( $x, "'rel(id:' . implode( ',', \$rel ) . ');'" )
			&& false !== strpos( $x, "http_build_query( array( 'data' => \$ql ) )" ) && false === strpos( $x, "CURLOPT_POSTFIELDS => array( 'data'" )
			&& false !== strpos( $x, 'out tags center;' ) && false !== strpos( $x, "require_once __DIR__ . '/lib/tp-boundaries-lib.php';" );
	},
	$bsrc, str_replace( "http_build_query( array( 'data' => \$ql ) )", "array( 'data' => \$ql )", $bsrc ) );

$blib = file_get_contents( dirname( TP_PLUGIN_DIR, 2 ) . '/scripts/lib/tp-boundaries-lib.php' );
tp441_mut( 'مرز: استان فقط با نام — کد ورودی ندارد؛ فقط رابطهٔ admin_level=4 با ISO3166-2 ایرانیِ خود OSM؛ نام نرمال دقیق و یکتا؛ ISO از همان برچسب برگردانده می‌شود (جهش: بی شرط «IR-» همسایه‌ها می‌آیند؛ بی نام، کد جایش می‌نشیند)',
	function ( $x ) {
		return false !== strpos( $x, 'function tpb_province_by_name( $name, $elements )' ) && false !== strpos( $x, "0 !== strpos( isset( \$t['ISO3166-2'] ) ? \$t['ISO3166-2'] : '', 'IR-' )" )
			&& false !== strpos( $x, 'in_array( $n, tpb_el_names( $e ), true )' ) && false !== strpos( $x, "return array( 'auto', \$e, 'admin_level=4+exact-name', \$e['tags']['ISO3166-2'] );" ) && false === strpos( $x, 'tpb_province_match' );
	},
	$blib, str_replace( "0 !== strpos( isset( \$t['ISO3166-2'] ) ? \$t['ISO3166-2'] : '', 'IR-' )", 'false', $blib ) );
$bsrc2 = file_get_contents( dirname( TP_PLUGIN_DIR, 2 ) . '/scripts/tp-boundaries.php' );
tp441_mut( 'مرز: دو نگهبان خودکار در match — ۱ شناسهٔ OSM تکراری ← هر دو none؛ ۲ مرکز استان (capital + geo_coordinates) درون چندضلعی، وگرنه none و پاک‌شدن GeoJSON؛ province-osm.csv با ISO خود OSM (جایگزین province-iso.csv)',
	function ( $x ) {
		return false !== strpos( $x, 'tpb_duplicate_ids(' ) && false !== strpos( $x, "'guard1-duplicate-osm-id:'" ) && false !== strpos( $x, 'tpb_center_inside(' ) && false !== strpos( $x, "'guard2-center-outside-polygon'" )
			&& false !== strpos( $x, "'/reference/province-osm.csv'" ) && false !== strpos( $x, "'osm_relation', 'ISO3166-2', 'name_fa'" ) && false === strpos( $x, 'province-iso.csv' ) && false === strpos( $x, 'tpb_province_match' );
	},
	$bsrc2, str_replace( 'tpb_duplicate_ids(', 'array_values(', $bsrc2 ) );

$bsrc3 = file_get_contents( dirname( TP_PLUGIN_DIR, 2 ) . '/scripts/tp-boundaries.php' );
tp441_mut( 'مرز: نگهبان نقطهٔ شهر — geo_coordinates خودِ شهر باید درون GeoJSON همان شهر باشد، وگرنه suggested (نه auto) و GeoJSON پاک؛ بی مختصات no-coords، بی GeoJSON pending؛ شهر بی تطبیق GeoJSON کهنه‌اش پاک می‌شود؛ ستون city_point در CSV',
	function ( $x ) {
		return false !== strpos( $x, "get_post_meta( \$p->ID, 'geo_coordinates', true )" ) && false !== strpos( $x, "\$row['city_point'] = 'FAIL'; \$row['status'] = 'suggested';" ) && false !== strpos( $x, 'guard-city-point-outside-polygon' )
			&& false !== strpos( $x, "'no-coords'" ) && false !== strpos( $x, "'pending'" ) && false !== strpos( $x, 'GeoJSON کهنهٔ شهرِ بی تطبیق پاک شد' ) && false !== strpos( $x, "'parent_province', 'city_point' )" );
	},
	$bsrc3, str_replace( "\$row['status'] = 'suggested'; \$row['method'] .=", "\$row['method'] .=", $bsrc3 ) );
