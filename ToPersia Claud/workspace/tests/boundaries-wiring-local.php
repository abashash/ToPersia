<?php
/**
 * سیم‌کشی tp-boundaries match روی وردپرس واقعی با کش ساختگی — P5-D363  (فقط topersia.local؛ آفلاین)
 *
 *   wp eval-file tests/boundaries-wiring-local.php
 *
 * کش Overpass ساختگی (دو عنصر استان با ISO، یک شهر درون اصفهان، همان نام بیرون از اصفهان، بستر رود هم‌نام) در پوشهٔ موقت کش می‌نشیند،
 * `match` اجرا می‌شود، نتیجه خوانده می‌شود، و CSV مرجع و کش به حالت پیش برمی‌گردند. شبکه ندارد.
 * پیش‌شرط: GeoJSON اصفهان در uploads/tiles/boundaries/ (ساختهٔ build). GeoJSONهای موجود پیش و پس پشتیبان و برگردانده می‌شوند.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$root  = dirname( __DIR__ );
$csv   = $root . '/reference/osm-boundary-map.csv';
$up    = wp_get_upload_dir();
$cache = trailingslashit( $up['basedir'] ) . 'tiles/boundaries-cache/';
$geo   = trailingslashit( $up['basedir'] ) . 'tiles/boundaries/province__isfahan.geojson';
$GLOBALS["tpb_t"] = array( 0, 0 );
function t( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS["tpb_t"][0]++; echo "  ✅ {$label}\n"; } else { $GLOBALS["tpb_t"][1]++; echo "  ❌ {$label}   ← {$note}\n"; } }

if ( ! is_file( $geo ) ) { echo "  ℹ︎ GeoJSON اصفهان نیست؛ نخست build استان‌ها\n"; exit( 2 ); }

$po_csv = $root . '/reference/province-osm.csv';
$bdir   = trailingslashit( $up['basedir'] ) . 'tiles/boundaries/';
$bk     = sys_get_temp_dir() . '/tpb-wiring-backup-' . uniqid() . '/';
mkdir( $bk, 0777, true );
foreach ( (array) glob( $bdir . '*.geojson' ) as $f ) { copy( $f, $bk . basename( $f ) ); }   /* match پاک‌کردنِ GeoJSON استانِ بی‌تطبیق دارد؛ آزمون نباید فایل واقعی را از دست بدهد */
$po_before    = is_file( $po_csv ) ? file_get_contents( $po_csv ) : null;
$csv_before   = is_file( $csv ) ? file_get_contents( $csv ) : null;
$cache_before = is_file( $cache . 'overpass-iran.json' ) ? file_get_contents( $cache . 'overpass-iran.json' ) : null;
if ( ! is_dir( $cache ) ) { mkdir( $cache, 0777, true ); }

$adm = function ( $id, $name, $lon, $lat ) { return array( 'type' => 'relation', 'id' => $id, 'tags' => array( 'boundary' => 'administrative', 'admin_level' => '8', 'name' => $name, 'name:fa' => $name ), 'center' => array( 'lon' => $lon, 'lat' => $lat ) ); };
$els = array(
	array( 'type' => 'relation', 'id' => 7001, 'tags' => array( 'boundary' => 'administrative', 'admin_level' => '4', 'ISO3166-2' => 'IR-10', 'name' => 'استان اصفهان', 'name:fa' => 'استان اصفهان' ) ),
	array( 'type' => 'relation', 'id' => 7002, 'tags' => array( 'boundary' => 'administrative', 'admin_level' => '4', 'ISO3166-2' => 'IR-17', 'name' => 'استان کهگیلویه و بویر احمد', 'name:fa' => 'استان کهگیلویه و بویر احمد' ) ),
	$adm( 8001, 'کاشان', 51.4, 33.98 ),                       /* درون اصفهان */
	$adm( 8002, 'کاشان', 59.0, 36.0 ),                        /* همان نام، بیرون */
	array( 'type' => 'way', 'id' => 9001, 'tags' => array( 'waterway' => 'riverbank', 'name' => 'اصفهان' ), 'center' => array( 'lon' => 51.67, 'lat' => 32.65 ) ),
);

try {
	file_put_contents( $cache . 'overpass-iran.json', json_encode( array( 'fetched' => 'synthetic', 'elements' => $els ), JSON_UNESCAPED_UNICODE ) );
	ob_start();
	$args = array( 'match' );
	include $root . '/scripts/tp-boundaries.php';
	$log = ob_get_clean();

	$fh = fopen( $csv, 'r' ); $hd = fgetcsv( $fh, 0, ',', '"', '\\' ); $rows = array();
	while ( ( $r = fgetcsv( $fh, 0, ',', '"', '\\' ) ) ) { if ( count( $r ) === count( $hd ) ) { $rows[ $r[0] ] = array_combine( $hd, $r ); } }
	fclose( $fh );

	$by = function ( $slug ) use ( $rows ) { foreach ( $rows as $uid => $r ) { if ( preg_match( '~/e/' . preg_quote( $slug, '~' ) . '$~', $uid ) ) { return $r; } } return null; };
	$isf = $by( 'province/isfahan' ); $kg = $by( 'province/kohgiluyeh-and-boyer-ahmad' ); $far = $by( 'province/fars' ); $kas = $by( 'city/kashan' );

	t( 'اصفهان ← auto با نام (relation 7001)؛ کهگیلویه و بویراحمد ← auto با «بویر احمد» جداشدهٔ OSM (7002) — کد در میان نیست', $isf && 'auto' === $isf['status'] && '7001' === $isf['osm_id'] && $kg && 'auto' === $kg['status'] && '7002' === $kg['osm_id'], json_encode( array( $isf, $kg ), JSON_UNESCAPED_UNICODE ) );
	t( 'فارس ← none (در کش ساختگی رابطه‌ای با نام فارس نیست)', $far && 'none' === $far['status'], json_encode( $far, JSON_UNESCAPED_UNICODE ) );
	t( 'کاشان ← auto با نامزد درون اصفهان (8001)، نه همان نام در بیرون (8002)', $kas && 'auto' === $kas['status'] && '8001' === $kas['osm_id'] && 'اصفهان' === $kas['parent_province'], json_encode( $kas, JSON_UNESCAPED_UNICODE ) );
	$skip = array_filter( $rows, function ( $r ) { return in_array( $r['post_type'], array( 'tp_water_body', 'tp_desert', 'tp_mountain' ), true ); } );
	t( 'پهنه‌های طبیعی ← skip (نه none)', $skip && ! array_filter( $skip, function ( $r ) { return 'skip' !== $r['status']; } ), json_encode( array_column( $skip, 'status' ) ) );
	t( 'گزارش match چاپ شد (شمارش وضعیت‌ها)', false !== strpos( $log, 'tp_city' ), substr( $log, 0, 120 ) );
} finally {
	foreach ( (array) glob( $bdir . '*.geojson' ) as $f ) { unlink( $f ); }
	foreach ( (array) glob( $bk . '*.geojson' ) as $f ) { copy( $f, $bdir . basename( $f ) ); unlink( $f ); }
	@rmdir( $bk );
	if ( null !== $po_before ) { file_put_contents( $po_csv, $po_before ); }
	if ( null !== $csv_before ) { file_put_contents( $csv, $csv_before ); } else { @unlink( $csv ); }
	if ( null !== $cache_before ) { file_put_contents( $cache . 'overpass-iran.json', $cache_before ); } else { @unlink( $cache . 'overpass-iran.json' ); }
	t( 'برگشت: CSV مرجع و کش به حالت پیش', ( null === $csv_before || file_get_contents( $csv ) === $csv_before ), '' );
}
echo "
گذشته: {$GLOBALS["tpb_t"][0]}   افتاده: {$GLOBALS["tpb_t"][1]}
";
exit( $GLOBALS["tpb_t"][1] ? 1 : 0 );
