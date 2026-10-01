<?php
/**
 * topersia-schema ۱٫۲٫۰ روی وردپرسِ واقعیِ محلی — نقشۀ شِمای هر نوع پست.
 *
 *   php tests/schema-local.php
 *
 * ادعاها، هر کدام با جهش:
 *   ۱ هر نوع عمومی موجودیت (tp_*) جز شهرستان (G4، بی‌صفحه) در نقشه است
 *   ۲ هر رابطۀ زنجیرۀ نیاکان در JetEngine هست و نوعِ همان پست سوی فرزندش است
 *   ۳ هر نوعی که پست منتشرشدۀ محلی دارد، گرافِ درست می‌دهد: گرۀ موجودیت با همان
 *     @type، WebPage و BreadcrumbList؛ geo و containedInPlace فقط روی شاخۀ Place؛
 *     inLanguage هرگز روی موجودیت
 *
 * کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص
 */

$_SERVER['HTTP_HOST']   = 'topersia.local';
$_SERVER['SERVER_NAME'] = 'topersia.local';
$_SERVER['REQUEST_URI'] = '/';

require 'C:/wamp64/www/topersia/geo/wp-load.php';

if ( 'local' !== wp_get_environment_type() || 'topersia_local' !== DB_NAME || ! function_exists( 'topersia_schema_map' ) ) {
	fwrite( STDERR, "فقط نسخۀ محلی با topersia-schema فعال\n" );
	exit( 2 );
}

$pass = 0;
$fail = 0;
function t( $label, $ok, $note = '' ) {
	global $pass, $fail;
	if ( $ok ) { $pass ++; echo "  ✅ {$label}\n"; }
	else { $fail ++; echo "  ❌ {$label}" . ( '' !== $note ? "   ← {$note}" : '' ) . "\n"; }
}

/* شاخۀ Place در Schema.org — نوع‌هایی که نقشه به کار می‌برد */
$PLACE = array( 'Place', 'Country', 'State', 'City', 'AdministrativeArea', 'Landform', 'Mountain', 'BodyOfWater', 'LandmarksOrHistoricalBuildings', 'PlaceOfWorship', 'Museum', 'TouristAttraction' );
$OTHER = array( 'Thing', 'Person', 'Language', 'TouristTrip' );

$types = array_values( array_filter( get_post_types( array( 'public' => true ) ), function ( $t ) {
	return 0 === strpos( $t, 'tp_' ) && 'tp_county' !== $t;
} ) );

/* ۱ پوشش */
$covered = function ( $map ) use ( $types ) {
	return array_values( array_diff( $types, array_keys( $map ) ) );
};
$map     = topersia_schema_map();
$missing = $covered( $map );
$mut     = $map;
unset( $mut['tp_village'] );
t( 'پوشش: هر نوع عمومی موجودیت جز شهرستان نقشه دارد (' . count( $types ) . ' نوع)؛ جهش: یک نوعِ جامانده',
	! $missing && array( 'tp_village' ) === $covered( $mut ), implode( ', ', $missing ) );

/* نوع و سازنده از شاخۀ درست */
$bad = array();
foreach ( $map as $pt => $c ) {
	$ok = in_array( $c['type'], $PLACE, true ) ? 'topersia_schema_place' === $c['builder'] : ( in_array( $c['type'], $OTHER, true ) && ( 'tp_route' === $pt || 'topersia_schema_thing' === $c['builder'] ) );
	if ( ! $ok ) { $bad[] = $pt . ':' . $c['type']; }
}
t( 'نوع هر گره از فهرست بستۀ Schema.org و سازندۀ همان شاخه (مکان ← place، بقیه ← thing)', ! $bad, implode( ', ', $bad ) );

/* ۲ رابطه‌های زنجیره */
$chain_ok = function ( $map ) {
	$bad = array();
	foreach ( $map as $pt => $c ) {
		$child = 'posts::' . $pt;
		foreach ( isset( $c['parents'] ) ? $c['parents'] : array() as $i => $step ) {
			$rel = topersia_relation( $step['relation'] );
			if ( ! $rel ) { $bad[] = "{$pt}#{$i}:{$step['relation']} نیست"; break; }
			if ( 0 === $i && $child !== $rel->get_args( 'child_object' ) ) { $bad[] = "{$pt}#{$i}:{$step['relation']} فرزندش " . $rel->get_args( 'child_object' ); }
			$child = $rel->get_args( 'parent_object' );
		}
	}
	return $bad;
};
$chain_bad = $chain_ok( $map );
$mut       = $map;
$mut['tp_mountain']['parents'][0]['relation'] = 79;   /* رابطۀ پهنۀ آبی به‌جای کوه */
t( 'زنجیرۀ نیاکان: هر رابطه در JetEngine هست، نوعِ پست سوی فرزندِ گام اول است و هر گام از والدِ گام قبل؛ جهش: شناسۀ رابطۀ نوع دیگر',
	! $chain_bad && $chain_ok( $mut ), implode( ' · ', array_slice( $chain_bad, 0, 6 ) ) );

/* ۳ گراف روی پست‌های واقعی */
$seen = 0;
$gbad = array();
foreach ( array_keys( $map ) as $pt ) {
	$ids = get_posts( array( 'post_type' => $pt, 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) );
	if ( ! $ids ) { continue; }
	$seen ++;
	$graph = topersia_schema_build( $ids[0] );
	$nodes = isset( $graph['@graph'] ) ? $graph['@graph'] : $graph;
	$by    = array();
	foreach ( (array) $nodes as $n ) { if ( isset( $n['@type'] ) ) { $by[ is_array( $n['@type'] ) ? $n['@type'][0] : $n['@type'] ][] = $n; } }
	$type  = $map[ $pt ]['type'];
	$ent   = isset( $by[ $type ] ) ? $by[ $type ][0] : null;
	$place = in_array( $type, $PLACE, true );
	if ( ! $ent || empty( $by['WebPage'] ) || empty( $by['BreadcrumbList'] ) || isset( $ent['inLanguage'] ) || ( ! $place && ( isset( $ent['geo'] ) || isset( $ent['containedInPlace'] ) ) ) || false === wp_json_encode( $graph ) ) {
		$gbad[] = $pt . '#' . $ids[0];
	}
}
t( "گراف: هر نوعِ دارای پست منتشرشدۀ محلی ({$seen} نوع) گرۀ همان @type، WebPage و BreadcrumbList دارد؛ geo و containedInPlace فقط روی مکان؛ inLanguage نه",
	$seen > 0 && ! $gbad, implode( ', ', $gbad ) );

echo str_repeat( '─', 78 ) . "\n  گذشته: {$pass}   افتاده: {$fail}\n";
exit( $fail ? 1 : 0 );
