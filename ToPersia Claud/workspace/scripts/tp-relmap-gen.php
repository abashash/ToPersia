<?php
/**
 * مولد نقشهٔ رابطه — گام ۱ CODEX-BRIEF
 *
 * جهت هر رابطه فقط از args.parent_object و args.child_object خوانده می‌شود.
 * ستون «سوی استان» در TP-G-RELATIONS.md و برچسب‌های labels هر دو وارونه‌اند — P5-D170
 *
 * اجرا:  php scripts/tp-relmap-gen.php            ← فقط چاپ و ادعا
 *        php scripts/tp-relmap-gen.php --write    ← در topersia-g-map.php می‌نویسد
 */

$ROOT = dirname( __DIR__ );
$SKIN = $ROOT . '/reference/Jetengine_Skin.json';
$MAPF = $ROOT . '/plugin/topersia-render/includes/topersia-g-map.php';

/* نوع پست‌هایی که قالب می‌گیرند · tp_county عمداً نیست — P5-D172 */
$TPL = array( 'tp_country', 'tp_region', 'tp_province', 'tp_city', 'tp_district',
	'tp_village', 'tp_island', 'tp_mountain', 'tp_water_body', 'tp_desert',
	'tp_forest_plain', 'tp_border_crossing' );

/* سلسله‌مراتب اداری — برای تشخیص مسیر راهنما از فهرست زیرمجموعه */
$ADMIN = array( 'tp_country', 'tp_region', 'tp_province', 'tp_county',
	'tp_city', 'tp_district', 'tp_village' );

$SETTLE = array( 'tp_country' => 'countries', 'tp_region' => 'regions',
	'tp_province' => 'provinces', 'tp_county' => 'counties', 'tp_city' => 'cities',
	'tp_district' => 'districts', 'tp_village' => 'villages' );

$BY_TARGET = array(
	'tp_mountain' => 'nature', 'tp_water_body' => 'nature', 'tp_desert' => 'nature',
	'tp_forest_plain' => 'nature', 'tp_island' => 'nature', 'tp_protected_area' => 'nature',
	'tp_natural_feature' => 'nature', 'tp_landscape' => 'nature',
	'tp_ancient_site' => 'ancient', 'tp_monument' => 'ancient', 'tp_historical_era' => 'ancient',
	'tp_museum' => 'ancient', 'tp_religious_site' => 'ancient',
	'tp_handicraft' => 'culture', 'tp_local_food' => 'culture', 'tp_costume' => 'culture',
	'tp_music' => 'culture', 'tp_dance' => 'culture', 'tp_ritual' => 'culture',
	'tp_language' => 'culture', 'tp_festival' => 'culture', 'tp_sports_event' => 'culture',
	'tp_religious_event' => 'culture', 'tp_seasonal_event' => 'culture', 'tp_figure' => 'culture',
	'tp_currency' => 'practical', 'tp_calendar' => 'practical', 'tp_health_safety' => 'practical',
	'tp_travel_etiquette' => 'practical', 'tp_visa' => 'practical',
	'tp_route' => 'routes', 'tp_border_crossing' => 'border',
);

/*
 * روابطی که عمداً کنار گذاشته می‌شوند.
 *
 * اسکین برای استان ۳۴ رابطه دارد ولی نقشهٔ دست‌نویس فعلی ۳۰ تا را به کار
 * می‌برد. این چهار تا تصمیم جداگانه می‌خواهند و تا آن تصمیم، خروجی استان
 * نباید عوض شود — ۴۹ آزمون رفتار استان را قفل کرده‌اند.
 *
 *   ۸۲  province_border   گذرهای مرزی استان
 *   ۸۳  region_province   منطقه به‌عنوان والد دوم در مسیر راهنما
 *   ۱۲۵ produced_g3_c1    صنایع‌دستی استان
 *   ۱۲۸ produced_g3_c2    غذای محلی استان
 */
$FREEZE = array( 'tp_province' => array( 82, 83, 125, 128 ) );

/*
 * شش خانوادهٔ فرهنگی — مرجع بصری `#culture` را با آیکون و شمار نشان می‌دهد.
 * گروه `culture` سیزده رابطه دارد و بدون این نقشه یک تودهٔ بی‌شکل می‌شود.
 * خانواده از نوعِ سرِ دیگر مشتق می‌شود، نه از نام رابطه.
 */
$FAMILY = array(
	'tp_handicraft'      => 'artisanat',
	'tp_local_food'      => 'cuisine',
	'tp_music'           => 'music',
	'tp_dance'           => 'music',
	'tp_ritual'          => 'music',
	'tp_festival'        => 'festivals',
	'tp_sports_event'    => 'festivals',
	'tp_seasonal_event'  => 'seasonal',
	'tp_religious_event' => 'seasonal',
	'tp_language'        => 'heritage',
	'tp_costume'         => 'heritage',
	'tp_figure'          => 'heritage',
);

/* ترتیب ثابت گروه‌ها در خروجی */
$ORDER = array( 'capital', 'capital-of', 'countries', 'regions', 'provinces', 'counties',
	'cities', 'districts', 'villages', 'nature', 'ancient', 'culture', 'practical',
	'border', 'subranges', 'neighbors', 'routes', 'crumbs' );

/* عنوان و kicker · کلید «نوع:گروه» بر کلید عام مقدم است */
$TITLE = array(
	'tp_province:capital'   => array( 'مرکز استان', null ),
	'tp_country:capital'    => array( 'پایتخت', null ),
	'tp_province:neighbors' => array( 'استان‌های هم‌مرز', 'NEIGHBORS' ),
	'capital'    => array( 'مرکز', null ),
	'capital-of' => array( 'مرکزیت', 'CAPITAL' ),
	'countries'  => array( 'کشور', 'COUNTRY' ),
	'regions'    => array( 'منطقه', 'REGION' ),
	'provinces'  => array( 'استان‌ها', 'PROVINCES' ),
	'counties'   => array( 'شهرستان‌ها', 'COUNTIES' ),
	'cities'     => array( 'شهرها', 'CITIES' ),
	'districts'  => array( 'محله‌ها', 'DISTRICTS' ),
	'villages'   => array( 'روستاها', 'VILLAGES' ),
	'nature'     => array( 'پهنه‌های طبیعی', 'NATURE' ),
	'ancient'    => array( 'محوطه‌های باستانی', 'HERITAGE' ),
	'culture'    => array( 'فرهنگ و رویداد', 'CULTURE' ),
	'practical'  => array( 'سفر عملی', 'PRACTICAL' ),
	'border'     => array( 'گذرهای مرزی', 'BORDER' ),
	'subranges'  => array( 'زیرشاخه‌ها', 'RANGES' ),
	'neighbors'  => array( 'هم‌مرزها', 'NEIGHBORS' ),
	'routes'     => array( 'مسیرهای گردشگری', 'ROUTES' ),
);

/* ── خواندن اسکین ───────────────────────────────────────────────── */
$json = json_decode( file_get_contents( $SKIN ), true );
if ( ! is_array( $json ) || empty( $json['relations'] ) ) {
	fwrite( STDERR, "اسکین خوانده نشد\n" );
	exit( 2 );
}

$rels = array();
foreach ( $json['relations'] as $row ) {
	$a = @unserialize( $row['args'] );
	$l = @unserialize( $row['labels'] );
	if ( ! is_array( $a ) ) { continue; }
	$p = isset( $a['parent_object'] ) ? $a['parent_object'] : '';
	$c = isset( $a['child_object'] ) ? $a['child_object'] : '';
	if ( 0 !== strpos( $p, 'posts::' ) || 0 !== strpos( $c, 'posts::' ) ) { continue; }
	$rels[] = array(
		'id'     => (int) $row['id'],
		'parent' => substr( $p, 7 ),
		'child'  => substr( $c, 7 ),
		'type'   => $a['type'],
		'name'   => is_array( $l ) && isset( $l['name'] ) ? $l['name'] : '',
	);
}

/** گروه یک رابطه از دید یک نوع پست. */
function tp_gen_group( $self, $other, $dir, $type, $name, $ADMIN, $SETTLE, $BY_TARGET ) {
	/* نام رابطه در اسکین زیرخط دارد؛ کلید گروه خط‌تیره، چون شناسهٔ بخش از
	   همین کلید ساخته می‌شود و باید با لنگر ناوبری بخواند. */
	if ( 0 === strpos( $name, 'capital_of' ) ) {
		return 'children' === $dir ? 'capital' : 'capital-of';
	}
	if ( $other === $self ) {
		if ( 'many_to_many' === $type ) { return 'neighbors'; }
		return 'children' === $dir ? 'subranges' : 'crumbs';
	}
	if ( in_array( $other, $ADMIN, true ) ) {
		return 'parents' === $dir ? 'crumbs' : $SETTLE[ $other ];
	}
	return isset( $BY_TARGET[ $other ] ) ? $BY_TARGET[ $other ] : null;
}

function tp_gen_cmp( $x, $y ) {
	if ( $x['id'] === $y['id'] ) { return 0; }
	return $x['id'] < $y['id'] ? -1 : 1;
}

$map          = array();
$unclassified = array();
$frozen       = array();
foreach ( $TPL as $pt ) {
	foreach ( $rels as $r ) {
		foreach ( array( 'children', 'parents' ) as $dir ) {
			$mine  = 'children' === $dir ? $r['parent'] : $r['child'];
			$other = 'children' === $dir ? $r['child'] : $r['parent'];
			if ( $mine !== $pt ) { continue; }
			if ( isset( $FREEZE[ $pt ] ) && in_array( $r['id'], $FREEZE[ $pt ], true ) ) {
				$frozen[ $pt ][] = $r['id'];
				continue;
			}
			$g = tp_gen_group( $pt, $other, $dir, $r['type'], $r['name'], $ADMIN, $SETTLE, $BY_TARGET );
			if ( null === $g ) {
				$unclassified[] = $pt . ' → ' . $other . ' (رابطهٔ ' . $r['id'] . ')';
				continue;
			}
			$spec = array( 'id' => $r['id'], 'dir' => $dir );
				if ( 'culture' === $g && isset( $FAMILY[ $other ] ) ) { $spec['fam'] = $FAMILY[ $other ]; }
				$map[ $pt ][ $g ][] = $spec;
		}
	}
}

$ordered = array();
foreach ( $TPL as $pt ) {
	if ( empty( $map[ $pt ] ) ) { continue; }
	foreach ( $ORDER as $g ) {
		if ( empty( $map[ $pt ][ $g ] ) ) { continue; }
		$rs = $map[ $pt ][ $g ];
		usort( $rs, 'tp_gen_cmp' );
		$ordered[ $pt ][ $g ] = $rs;
	}
}

/* ── ادعاهای مولد ───────────────────────────────────────────────── */
$A = array( 'pass' => 0, 'fail' => 0 );
function ga( $label, $ok, $note = '' ) {
	global $A;
	if ( $ok ) { $A['pass']++; printf( "  ✅ %s\n", $label ); }
	else { $A['fail']++; printf( "  ❌ %s   %s\n", $label, $note ); }
}

echo "ادعاهای مولد\n", str_repeat( '─', 74 ), "\n";
ga( 'هر ۱۳۵ رابطه خوانده شد', 135 === count( $rels ), count( $rels ) );
ga( 'هیچ رابطه‌ای طبقه‌بندی‌نشده نماند', empty( $unclassified ), implode( ' · ', $unclassified ) );
ga( 'دوازده نوع پست در نقشه هست', 12 === count( $ordered ), count( $ordered ) );

$pv   = isset( $ordered['tp_province'] ) ? $ordered['tp_province'] : array();
$flat = array();
foreach ( $pv as $g => $rs ) {
	foreach ( $rs as $r ) { $flat[ $r['id'] ] = 1; }
}
ga( 'اسکین برای استان ۳۴ رابطه دارد', 34 === count( $flat ) + count( $FREEZE['tp_province'] ),
	count( $flat ) + count( $FREEZE['tp_province'] ) );
ga( 'استان ۳۰ رابطه می‌گیرد — چهار تا کنار گذاشته شد', 30 === count( $flat ), count( $flat ) );
ga( 'هر چهار رابطهٔ کنارگذاشته واقعاً در اسکین بودند',
	isset( $frozen['tp_province'] ) && 4 === count( array_unique( $frozen['tp_province'] ) ),
	isset( $frozen['tp_province'] ) ? implode( ',', $frozen['tp_province'] ) : 'هیچ' );
ga( 'استان: ۹۶ در capital با جهت children',
	isset( $pv['capital'][0] ) && 96 === $pv['capital'][0]['id'] && 'children' === $pv['capital'][0]['dir'] );
ga( 'استان: ۷۱ در crumbs با جهت parents',
	isset( $pv['crumbs'] ) && in_array( array( 'id' => 71, 'dir' => 'parents' ), $pv['crumbs'], true ) );
ga( 'استان: ۱۷۲ در routes با جهت parents',
	isset( $pv['routes'][0] ) && 172 === $pv['routes'][0]['id'] && 'parents' === $pv['routes'][0]['dir'] );
ga( 'استان: ۷۵ به villages رفت', isset( $pv['villages'][0] ) && 75 === $pv['villages'][0]['id'] );
$nat = isset( $pv['nature'] ) ? array_column( $pv['nature'], 'id' ) : array();
ga( 'استان: nature هشت رابطه دارد و ۷۵ در آن نیست',
	8 === count( $nat ) && ! in_array( 75, $nat, true ), implode( ',', $nat ) );
ga( 'استان: چهار رابطهٔ غایب اضافه نشد',
	! isset( $flat[82] ) && ! isset( $flat[83] ) && ! isset( $flat[125] ) && ! isset( $flat[128] ) );
ga( 'کوه: ۱۰۱ در subranges',
	isset( $ordered['tp_mountain']['subranges'][0] ) && 101 === $ordered['tp_mountain']['subranges'][0]['id'] );
ga( 'شهر: سه رابطهٔ capital_of با جهت parents',
	isset( $ordered['tp_city']['capital-of'] ) && 3 === count( $ordered['tp_city']['capital-of'] ) );
ga( 'کویر فقط crumbs دارد',
	isset( $ordered['tp_desert'] ) && array( 'crumbs' ) === array_keys( $ordered['tp_desert'] ) );

/* هر رابطهٔ فرهنگی باید خانواده داشته باشد، وگرنه از شبکهٔ شش‌خانواده می‌افتد */
$fam_missing = array();
$fam_seen    = array();
foreach ( $ordered as $pt => $groups ) {
	if ( empty( $groups['culture'] ) ) { continue; }
	foreach ( $groups['culture'] as $r ) {
		if ( empty( $r['fam'] ) ) { $fam_missing[] = $pt . ':' . $r['id']; continue; }
		$fam_seen[ $r['fam'] ] = 1;
	}
}
ga( 'هر رابطهٔ فرهنگی خانواده دارد', empty( $fam_missing ), implode( ' · ', $fam_missing ) );
ga( 'هر شش خانواده در نقشه دیده شد', 6 === count( $fam_seen ), implode( ' · ', array_keys( $fam_seen ) ) );

/* ── تولید متن PHP ─────────────────────────────────────────────── */
$t    = "\t";
$out  = $t . "function topersia_g_relmap() {\n\n";
$out .= $t . $t . "\$map = array(\n";
foreach ( $ordered as $pt => $groups ) {
	$out .= "\n" . $t . $t . $t . "'" . $pt . "' => array(\n";
	foreach ( $groups as $g => $rs ) {
		$key = isset( $TITLE[ $pt . ':' . $g ] ) ? $pt . ':' . $g : $g;
		$pair = isset( $TITLE[ $key ] ) ? $TITLE[ $key ] : array( null, null );
		$out .= "\n" . $t . $t . $t . $t . "'" . $g . "' => array(\n";
		if ( 'crumbs' !== $g && null !== $pair[0] ) {
			$out .= $t . $t . $t . $t . $t . "'title'  => '" . $pair[0] . "',\n";
			if ( null !== $pair[1] ) {
				$out .= $t . $t . $t . $t . $t . "'kicker' => '" . $pair[1] . "',\n";
			}
		}
		$out .= $t . $t . $t . $t . $t . "'rels'   => array(\n";
		foreach ( $rs as $r ) {
			$out .= $t . $t . $t . $t . $t . $t . "array( 'id' => " . $r['id'] . ", 'dir' => '" . $r['dir'] . "'"
				. ( isset( $r['fam'] ) ? ", 'fam' => '" . $r['fam'] . "'" : '' ) . " ),\n";
		}
		$out .= $t . $t . $t . $t . $t . "),\n";
		$out .= $t . $t . $t . $t . "),\n";
	}
	$out .= $t . $t . $t . "),\n";
}
$out .= "\n" . $t . $t . ");\n\n";
$out .= $t . $t . "if ( function_exists( 'apply_filters' ) ) {\n";
$out .= $t . $t . $t . "\$map = apply_filters( 'topersia_g_relmap', \$map );\n";
$out .= $t . $t . "}\n\n";
$out .= $t . $t . "return \$map;\n";
$out .= $t . "}\n";

$tmp = tempnam( sys_get_temp_dir(), 'relmap' );
file_put_contents( $tmp, "<?php\n" . $out );
$o = array();
exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $tmp ) . ' 2>&1', $o, $rc );
ga( 'متن تولیدشده از نظر نحوی معتبر است', 0 === $rc, implode( ' ', $o ) );
unlink( $tmp );

printf( "%s\n  گذشته: %d   افتاده: %d\n", str_repeat( '─', 74 ), $A['pass'], $A['fail'] );
if ( $A['fail'] ) { exit( 1 ); }

echo "\nنقشه\n", str_repeat( '─', 74 ), "\n";
$tot = 0;
foreach ( $ordered as $pt => $groups ) {
	$n    = array_sum( array_map( 'count', $groups ) );
	$tot += $n;
	printf( "  %-20s %2d   %s\n", $pt, $n, implode( ' · ', array_keys( $groups ) ) );
}
printf( "  %-20s %2d مدخل\n", 'جمع', $tot );

/* ── نوشتن ─────────────────────────────────────────────────────── */
if ( ! in_array( '--write', $argv, true ) ) {
	echo "\n(برای نوشتن: --write)\n";
	exit( 0 );
}

$src   = file_get_contents( $MAPF );
$start = strpos( $src, $t . 'function topersia_g_relmap() {' );
if ( false === $start ) {
	fwrite( STDERR, "تابع پیدا نشد\n" );
	exit( 2 );
}
$end = strpos( $src, "\n" . $t . "}\n", $start );
if ( false === $end ) {
	fwrite( STDERR, "پایان تابع پیدا نشد\n" );
	exit( 2 );
}
$end += strlen( "\n" . $t . "}\n" );

file_put_contents( $MAPF, substr( $src, 0, $start ) . $out . substr( $src, $end ) );
$o2 = array();
exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $MAPF ) . ' 2>&1', $o2, $rc2 );
if ( 0 !== $rc2 ) {
	fwrite( STDERR, "فایل بعد از نوشتن خراب شد!\n" . implode( "\n", $o2 ) . "\n" );
	exit( 2 );
}
echo "\n✅ نوشته شد: ", $MAPF, "\n";
