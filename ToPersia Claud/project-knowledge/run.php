<?php
/**
 * آزمون رندر دستهٔ G — سه موجودیت با ساختار کاملاً متفاوت.
 * هدف: اثبات اینکه یک رندرکننده برای هر سه کار می‌کند.
 */

require __DIR__ . '/stubs.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-map.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-zones.php';

/* ── پست‌ها ───────────────────────────────────────────────────────── */

$GLOBALS['tp_posts'] = array(
	10 => array( 'type' => 'tp_country',  'title' => 'ایران',        'url' => 'https://topersia.com/iran/' ),
	20 => array( 'type' => 'tp_province', 'title' => 'استان اصفهان', 'url' => 'https://topersia.com/isfahan-province/' ),
	21 => array( 'type' => 'tp_city',     'title' => 'شهر اصفهان',   'url' => 'https://topersia.com/isfahan/' ),
	22 => array( 'type' => 'tp_county',   'title' => 'کاشان',        'url' => 'https://topersia.com/kashan-county/' ),
	23 => array( 'type' => 'tp_desert',   'title' => 'کویر مرنجاب',  'url' => 'https://topersia.com/maranjab/' ),
	24 => array( 'type' => 'tp_mountain', 'title' => 'کرکس',         'url' => 'https://topersia.com/karkas/' ),
	30 => array( 'type' => 'tp_city',     'title' => 'شیراز',        'url' => 'https://topersia.com/shiraz/' ),
	40 => array( 'type' => 'tp_desert',   'title' => 'دشت لوت',      'url' => 'https://topersia.com/lut/' ),
);

/* ── روابط — جهت از export، نه از سند ─────────────────────────────── */

$GLOBALS['tp_rel'] = array(
	'71'  => array( 'parents'  => array( 20 => array( 10 ) ) ),                  // country_province
	'96'  => array( 'children' => array( 20 => array( 21 ) ) ),                  // capital_of_province
	'72'  => array( 'children' => array( 20 => array( 22 ) ) ),                  // province_county
	'80'  => array( 'children' => array( 20 => array( 23 ) ) ),                  // province_desert
	'78'  => array( 'children' => array( 20 => array( 24 ) ) ),                  // province_mountain
	'102' => array( 'children' => array( 20 => array() ) ),                      // borders_province
);

/* ── G3 استان ─────────────────────────────────────────────────────── */

$GLOBALS['tp_meta'][20] = array(
	'name_fa'                => 'استان اصفهان',
	'official_name_en'       => 'Isfahan Province',
	'official_name_ar'       => 'محافظة أصفهان',
	'name_local'             => 'ایالت اصفهان',
	'alternative_names'      => 'سپاهان · نصف جهان',
	'short_summary'          => 'سومین استان پهناور ایران در مرکز فلات.',
	'hero_image'             => 501,
	'featured_image_gallery' => '601,602',
	'province_code'          => '04',
	'area_km2'               => '107018',
	'population'             => '5120850',
	'geo_coordinates'        => '32.6539, 51.6660',
	'geo_boundary'           => 'POLYGON((50.1 33.9, 52.8 33.9, 52.8 30.8, 50.1 30.8, 50.1 33.9))',
	'best_season'            => 'spring',
	'bordering_countries'    => '',
	'climate_type'           => 'نیمه‌خشک',
	'free_zone'              => 'false',
	'entity_uid'             => 'tp:province:isfahan',
	'content_status'         => 'verified',
	'external_reference_uri' => 'https://www.wikidata.org/wiki/Q83848',
	'last_verified_date'     => '2025-06-02',
	'audio_narration'        => array(
		'item-1' => array( 'audio_title' => 'روایت کوتاه استان', 'audio_external_url' => 'https://topersia.com/a/1.mp3', 'audio_duration' => '4:12', 'audio_row_status' => 'active' ),
		'item-2' => array( 'audio_title' => 'راهنمای نقش جهان', 'audio_file' => 701, 'audio_duration' => '7:38', 'audio_row_status' => 'not_applicable' ),
	),
	'faq_items'              => array(
		'item-1' => array( 'faq_question' => 'بهترین زمان سفر به اصفهان کی است؟', 'faq_answer' => 'بهار، به‌ویژه اردیبهشت.', 'faq_row_status' => 'active' ),
		'item-2' => array( 'faq_question' => 'پرسش بازنشسته', 'faq_answer' => 'نباید دیده شود.', 'faq_row_status' => 'not_applicable' ),
	),
	'source_references'      => array(
		'item-1' => array( 'source_name' => 'مرکز آمار ایران', 'source_url' => 'https://amar.org.ir', 'source_row_status' => 'active' ),
		'item-2' => array( 'source_name' => 'یونسکو', 'source_url' => '', 'source_row_status' => '' ),
	),
);

/* ── G5 شهر ───────────────────────────────────────────────────────── */

$GLOBALS['tp_meta'][30] = array(
	'name_fa'              => 'شیراز',
	'name_en'              => 'Shiraz',
	'name_ar'              => 'شيراز',
	'short_summary'        => 'مرکز استان فارس و شهر شعر و باغ.',
	'geo_coordinates'      => '29.5918, 52.5837',
	'population'           => '1565572',
	'area_km2'             => '240',
	'elevation_m'          => '1500',
	'best_season'          => 'autumn',
	'transport_access'     => 'فرودگاه بین‌المللی، پایانهٔ مسافربری و بزرگراه‌های شمال–جنوب.',
	'founded_year'         => '550',
	'airport_name'         => 'فرودگاه بین‌المللی شهید دستغیب',
	'airport_iata'         => 'SYZ',
	'railway_station'      => 'true',
	'unesco_creative_city' => 'true',
	'free_zone'            => 'false',
	'entity_uid'           => 'tp:city:shiraz',
	'content_status'       => 'current',
	'last_verified_date'   => '1749081600',
);

/* ── G11 کویر ─────────────────────────────────────────────────────── */

$GLOBALS['tp_meta'][40] = array(
	'name_fa'                 => 'دشت لوت',
	'name_en'                 => 'Lut Desert',
	'short_summary'           => 'گرم‌ترین نقطهٔ ثبت‌شدهٔ سطح زمین.',
	'desert_type'             => 'salt',
	'area_km2'                => '51800',
	'geo_boundary'            => 'POLYGON((57.0 31.5, 59.5 31.5, 59.5 28.5, 57.0 28.5, 57.0 31.5))',
	'best_season'             => 'winter',
	'provinces_spanning'      => 'کرمان، سیستان و بلوچستان، خراسان جنوبی',
	'max_temperature_c'       => '70.7',
	'world_heritage'          => 'true',
	'world_heritage_year'     => '2016',
	'world_heritage_criteria' => 'vii, viii',
	'biosphere_reserve'       => 'false',
	'national_monument'       => 'true',
	'national_monument_year'  => '1385',
	'geo_coordinates'         => '30.2500, 58.5000',
	'entity_uid'              => 'tp:desert:lut',
	'content_status'          => 'verified',
);

/* ── اجرا ─────────────────────────────────────────────────────────── */

$zones = array(
	'tp_hero', 'tp_actionbar', 'tp_crumbs', 'tp_stats', 'tp_facts', 'tp_badges',
	'tp_seasons', 'tp_weather', 'tp_map', 'tp_slider', 'tp_relgrid',
	'tp_gallery', 'tp_audio', 'tp_faq', 'tp_sources',
);

$cases = array(
	20 => array( 'نام' => 'G3 — استان اصفهان', 'slider' => 'counties', 'relgrid' => 'nature' ),
	30 => array( 'نام' => 'G5 — شهر شیراز',    'slider' => 'counties', 'relgrid' => 'nature' ),
	40 => array( 'نام' => 'G11 — دشت لوت',     'slider' => 'counties', 'relgrid' => 'nature' ),
);

$summary = array();

foreach ( $cases as $pid => $case ) {

	$GLOBALS['tp_current'] = $pid;

	echo "\n" . str_repeat( '═', 78 ) . "\n";
	echo '  ' . $case['نام'] . "   (post_id {$pid} · " . get_post_type( $pid ) . ")\n";
	echo str_repeat( '═', 78 ) . "\n";

	foreach ( $zones as $tag ) {

		$atts = array();

		if ( 'tp_slider' === $tag )  { $atts = array( 'rel' => $case['slider'] ); }
		if ( 'tp_relgrid' === $tag ) { $atts = array( 'rel' => $case['relgrid'], 'limit' => 2 ); }

		$html = tp_do( $tag, $atts );
		$len  = strlen( $html );

		$summary[ $case['نام'] ][ $tag ] = $len;

		if ( '' === $html ) {
			printf( "  %-14s ▫ ناپدید (بدون داده)\n", $tag );
			continue;
		}

		printf( "  %-14s ▪ %d بایت\n", $tag, $len );
		echo "      " . preg_replace( '/></', ">\n      <", $html ) . "\n";
	}
}

echo "\n" . str_repeat( '═', 78 ) . "\n  جدول پوشش — بایت خروجی هر ناحیه\n" . str_repeat( '═', 78 ) . "\n";
printf( "  %-14s %14s %14s %14s\n", 'ناحیه', 'استان', 'شهر', 'کویر' );

foreach ( $zones as $tag ) {
	printf(
		"  %-14s %14s %14s %14s\n",
		$tag,
		$summary['G3 — استان اصفهان'][ $tag ] ?: '—',
		$summary['G5 — شهر شیراز'][ $tag ] ?: '—',
		$summary['G11 — دشت لوت'][ $tag ] ?: '—'
	);
}

echo "\n  لاگ هسته: " . ( $GLOBALS['tp_log'] ? implode( ' | ', $GLOBALS['tp_log'] ) : 'خالی' ) . "\n";
