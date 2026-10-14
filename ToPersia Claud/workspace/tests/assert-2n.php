<?php
/**
 * ادعاهای مرز OSM — P5-D363 · بی شبکه، با پاسخ‌های ساختگی
 * از assert.php پس از assert-2m.php بار می‌شود؛ PHP خالص، هر کدام با جهش. شبکه فقط در scripts/tp-boundaries.php (fetch · build).
 */

require_once dirname( TP_PLUGIN_DIR, 2 ) . '/scripts/lib/tp-boundaries-lib.php';

echo "\nمرز OSM — تطبیق آفلاین روی کش، حلقه‌ها، استان با ISO  P5-D363\n" . str_repeat( '─', 78 ) . "\n";

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

/* استان با ISO3166-2 */
$bIsoEl = function ( $id, $code ) { return array( 'type' => 'relation', 'id' => $id, 'tags' => array( 'boundary' => 'administrative', 'admin_level' => '4', 'ISO3166-2' => $code ) ); };
$bProv  = array( $bIsoEl( 100, 'IR-04' ), $bIsoEl( 101, 'IR-18' ), $bIsoEl( 102, 'IR-18' ) );
list( $p1, $pe1 ) = tpb_province_match( 'IR-04', $bProv );
list( $p2 ) = tpb_province_match( 'IR-18', $bProv );
list( $p3 ) = tpb_province_match( 'IR-99', $bProv );
$bNamed = array( array( 'type' => 'relation', 'id' => 200, 'tags' => array( 'boundary' => 'administrative', 'admin_level' => '4', 'ISO3166-2' => 'IR-30', 'name' => 'استان البرز', 'name:fa' => 'استان البرز' ) ) );
list( $p4, $pe4 ) = tpb_province_match( 'IR-32', $bNamed, 'البرز' );
list( $p5 ) = tpb_province_match( 'IR-32', $bNamed, 'قم' );
t( 'مرز: استان با ISO3166-2 — یکتا ← auto؛ دو عنصر با همان کد ← none؛ کدِ بی عنصر ← none، مگر نام دقیقاً با یک رابطهٔ سطح ۴ ایرانی بخواند ← suggested نه auto (برچسب OSM البرز و خراسان شمالی با فهرست رسمی نمی‌خواند؛ نام در تطبیق auto در میان نیست: «کهگیلویه و بویراحمد» دیگر none نمی‌شود)',
	'auto' === $p1 && 100 === $pe1['id'] && 'none' === $p2 && 'none' === $p3 && 'suggested' === $p4 && 200 === $pe4['id'] && 'none' === $p5, json_encode( array( $p1, $p2, $p3, $p4, $p5 ) ) );

/* جدول ISO ثابت */
$bIso = array_map( function ( $l ) { return str_getcsv( $l, ',', '"', '\\' ); }, file( dirname( TP_PLUGIN_DIR, 2 ) . '/reference/province-iso.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) );
array_shift( $bIso );
$bCodes = array_column( $bIso, 1 );
$bWant  = array();
foreach ( array_merge( range( 1, 8 ), range( 10, 32 ) ) as $n ) { $bWant[] = sprintf( 'IR-%02d', $n ); }
sort( $bCodes );
t( 'مرز: reference/province-iso.csv — ۳۱ استان، ISO3166-2 یکتا و برابر فهرست رسمی IR-01…IR-32 (بی IR-09)، entity_uid یکتا',
	31 === count( $bIso ) && $bCodes === $bWant && 31 === count( array_unique( array_column( $bIso, 0 ) ) ), count( $bIso ) . ' ردیف' );

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
