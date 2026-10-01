<?php
/**
 * دادهٔ محلی برای آزمون همهٔ بخش‌ها — ۴٫۲٫۳ بند ۹ · P5-D232
 *
 * فقط روی نسخهٔ محلی. هیچ‌چیز از این پرونده به سایت زنده نمی‌رود: نگهبانِ
 * آغاز، بیرون از `topersia.local` با محیط `local` اجرا نمی‌شود.
 *
 *   گام ۱  پیش‌نویس‌هایی که با استان اصفهان رابطه دارند منتشر می‌شوند —
 *          شهرستان‌ها (رابطهٔ ۷۲) و شهرهایشان (۷۳)
 *   گام ۲  برای هر نوعی که صفحهٔ استان یا شهر نشان می‌دهد دست‌کم یک نمونهٔ
 *          کامل، با رابطه‌های وصل
 *
 * قاعده‌ها
 *   هر واقعیت یا منبع دارد یا نوشته نمی‌شود؛ هر عدد با نشانیِ منبعش در
 *   source_references. بخش بی‌داده ناپدید می‌شود و این درست است.
 *   content_status: واژه‌نامهٔ ۳۷ گزینهٔ «نیازمند راستی‌آزمایی» ندارد؛
 *   نزدیک‌ترینِ موجود `pending_review` («نیازمند بازبینی…») گذاشته
 *   می‌شود — هرگز `verified`.
 *   رابطه‌ها با API جت‌انجین (`Relation::update`)، نه SQL. فقط فارسی.
 *   هیچ تصویری ساخته یا گذاشته نمی‌شود.
 *
 * اجرا پی‌درپی بی‌خطر است: پستِ هم‌نامک به‌روز می‌شود نه تکرار، و
 * `Relation::update` ردیفِ موجود را دوباره نمی‌سازد.
 *
 *   php scripts/tp-local-data.php
 */

$_SERVER['HTTP_HOST']   = 'topersia.local';
$_SERVER['SERVER_NAME'] = 'topersia.local';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require 'C:/wamp64/www/topersia/geo/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) {
	fwrite( STDERR, "فقط نسخهٔ محلی — topersia.local با محیط local\n" );
	exit( 2 );
}

if ( ! function_exists( 'jet_engine' ) || ! jet_engine()->relations ) {
	fwrite( STDERR, "جت‌انجین بار نشد\n" );
	exit( 2 );
}

wp_set_current_user( 1 );

const TP_DAY    = '2026-09-26';
const TP_STATUS = 'pending_review';
const TP_ISF    = 99;           /* استان اصفهان */

$log = array( 'published' => array(), 'created' => array(), 'updated' => array(), 'relations' => array(), 'skipped' => array() );

/* ── منابع ─────────────────────────────────────────────────────────── */

$S = array(
	'prov'      => array( 'Wikipedia — Isfahan province (استان‌های هم‌مرز، مرکز استان، جمعیت شهرها در سرشماری ۱۳۹۵)', 'https://en.wikipedia.org/wiki/Isfahan_province' ),
	'isf'       => array( 'Wikipedia — Isfahan (ارتفاع، مساحت، مختصات)', 'https://en.wikipedia.org/wiki/Isfahan' ),
	'airport'   => array( 'Wikipedia — Isfahan International Airport (کد IATA)', 'https://en.wikipedia.org/wiki/Isfahan_International_Airport' ),
	'rail'      => array( 'Wikipedia — Isfahan railway station', 'https://en.wikipedia.org/wiki/Isfahan_railway_station' ),
	'creative'  => array( 'UNESCO Creative Cities Network — Isfahan (صنایع دستی و هنرهای مردمی)', 'https://www.unesco.org/en/creative-cities/isfahan' ),
	'silk'      => array( 'Silk Road Seattle, University of Washington — Isfahan', 'https://depts.washington.edu/silkroad/cities/iran/isfahan/isfahan.html' ),
	'kashan'    => array( 'Wikipedia — Kashan (مختصات)', 'https://en.wikipedia.org/wiki/Kashan' ),
	'najaf'     => array( 'Wikipedia — Najafabad (مختصات)', 'https://en.wikipedia.org/wiki/Najafabad' ),
	'shahreza'  => array( 'Wikipedia — Shahreza (مختصات)', 'https://en.wikipedia.org/wiki/Shahreza' ),
	'abyaneh'   => array( 'Wikipedia — Abyaneh (جمعیت ۱۳۹۵، ارتفاع، مختصات)', 'https://en.wikipedia.org/wiki/Abyaneh' ),
	'maranjab'  => array( 'Wikipedia — Maranjab Desert', 'https://en.wikipedia.org/wiki/Maranjab_Desert' ),
	'gavkhouni' => array( 'Wikipedia — Gavkhouni (شوری، ژرفا، ثبت رامسر)', 'https://en.wikipedia.org/wiki/Gavkhouni' ),
	'karkas'    => array( 'Wikipedia — Karkas Mountains (ارتفاع قله)', 'https://en.wikipedia.org/wiki/Karkas_Mountains' ),
	'whc115'    => array( 'UNESCO World Heritage Centre — Meidan Emam, Esfahan (115)', 'https://whc.unesco.org/en/list/115/' ),
	'whc1397'   => array( 'UNESCO World Heritage Centre — Masjed-e Jāmé of Isfahan (1397)', 'https://whc.unesco.org/en/list/1397/' ),
	'whc1372'   => array( 'UNESCO World Heritage Centre — The Persian Garden (1372)', 'https://whc.unesco.org/en/list/1372/' ),
	'whc1506'   => array( 'UNESCO World Heritage Centre — The Persian Qanat (1506)', 'https://whc.unesco.org/en/list/1506/' ),
	'whc1668'   => array( 'UNESCO World Heritage Centre — The Persian Caravanserai (1668)', 'https://whc.unesco.org/en/list/1668/' ),
	'caravan'   => array( 'Wikipedia — The Persian Caravanserai (جدول اجزا و استان هر کدام)', 'https://en.wikipedia.org/wiki/The_Persian_Caravanserai' ),
	'fin'       => array( 'Wikipedia — Fin Garden (کاشان، ۱۵۹۰)', 'https://en.wikipedia.org/wiki/Fin_Garden' ),
	'moon'      => array( 'Friendly Iran — The Persian Qanat (قنات ماه در اردستان)', 'https://friendlyiran.com/the-persian-qanat/' ),
	'mozd'      => array( 'Wikidata — Mozdabad Qanat', 'https://www.wikidata.org/wiki/Q5879041' ),
	'mozdwhc'   => array( 'UNESCO World Heritage Centre — The Persian Qanat: Mozd Abad Qanat', 'https://whc.unesco.org/en/documents/141568' ),
	'khatam'    => array( 'Wikipedia — Khatam', 'https://en.wikipedia.org/wiki/Khatam' ),
	'mina'      => array( 'Wikipedia — Minakari', 'https://en.wikipedia.org/wiki/Minakari' ),
	'ghalam'    => array( 'Tehran Times — Isfahan’s ancient art of Ghalamzani gains international recognition', 'https://www.tehrantimes.com/news/505274/Isfahan-s-ancient-art-of-Ghalamzani-gains-international-recognition' ),
	'gaz'       => array( 'Wikipedia — Gaz (candy)', 'https://en.wikipedia.org/wiki/Gaz_(candy)' ),
	'golab'     => array( 'Wikipedia — Golabgiri', 'https://en.wikipedia.org/wiki/Golabgiri' ),
	'qamsar'    => array( 'Wikipedia — Qamsar', 'https://en.wikipedia.org/wiki/Qamsar' ),
);

/** ردیف‌های ریپیترِ منابع — همان شکلِ ذخیرهٔ جت‌انجین: item-0، item-1، … */
function tp_sources( $keys ) {
	global $S;
	$rows = array();
	foreach ( array_values( $keys ) as $i => $k ) {
		$rows[ 'item-' . $i ] = array(
			'source_name'        => $S[ $k ][0],
			'source_url'         => $S[ $k ][1],
			'source_access_date' => TP_DAY,
			'source_row_status'  => 'active',
		);
	}
	return $rows;
}

/** منابع تازه را به ریپیترِ موجود می‌افزاید، بی‌تکرارِ نشانی. */
function tp_add_sources( $post_id, $keys ) {
	global $S;
	$rows = get_post_meta( $post_id, 'source_references', true );
	$rows = is_array( $rows ) ? $rows : array();
	$urls = array();
	foreach ( $rows as $r ) { if ( ! empty( $r['source_url'] ) ) { $urls[] = $r['source_url']; } }
	$n = count( $rows );
	foreach ( $keys as $k ) {
		if ( in_array( $S[ $k ][1], $urls, true ) ) { continue; }
		while ( isset( $rows[ 'item-' . $n ] ) ) { $n ++; }
		$rows[ 'item-' . $n ] = array(
			'source_name' => $S[ $k ][0], 'source_url' => $S[ $k ][1],
			'source_access_date' => TP_DAY, 'source_row_status' => 'active',
		);
		$n ++;
	}
	update_post_meta( $post_id, 'source_references', $rows );
}

/** زبان فارسی — WPML. */
function tp_lang_fa( $post_id, $type ) {
	do_action( 'wpml_set_element_language_details', array(
		'element_id' => $post_id, 'element_type' => 'post_' . $type, 'trid' => false, 'language_code' => 'fa',
	) );
}

/**
 * پست بساز یا اگر همین نامک هست، به‌روزش کن.
 *
 * @return int
 */
function tp_upsert( $type, $slug, $title, $meta, $source_keys ) {
	global $log;

	$found = get_posts( array( 'post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'suppress_filters' => true ) );

	if ( $found ) {
		$id = (int) $found[0]->ID;
		wp_update_post( array( 'ID' => $id, 'post_title' => $title, 'post_status' => 'publish' ) );
		$log['updated'][] = array( $id, $type, $title, 'بازاجرا' );
	} else {
		$id = wp_insert_post( array(
			'post_type' => $type, 'post_status' => 'publish', 'post_title' => $title,
			'post_name' => $slug, 'post_author' => 1,
		), true );
		if ( is_wp_error( $id ) ) {
			fwrite( STDERR, "ساخته نشد: {$type} {$slug} — " . $id->get_error_message() . "\n" );
			exit( 1 );
		}
		tp_lang_fa( $id, $type );
		$log['created'][] = array( $id, $type, $title, $slug );
	}

	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	update_post_meta( $id, 'content_status', TP_STATUS );
	update_post_meta( $id, 'source_references', tp_sources( $source_keys ) );

	return $id;
}

/** رابطه از راه API جت‌انجین. */
function tp_rel( $rel_id, $parent, $child ) {
	global $log;
	$rel = jet_engine()->relations->get_active_relations( $rel_id );
	if ( ! $rel ) {
		fwrite( STDERR, "رابطهٔ {$rel_id} پیدا نشد\n" );
		exit( 1 );
	}
	$had = $rel->get_children( $parent, 'ids' );
	$rel->set_update_context( 'child' );
	$rel->update( $parent, $child );
	$now = $rel->get_children( $parent, 'ids' );
	if ( ! in_array( (int) $child, array_map( 'intval', (array) $now ), true ) ) {
		fwrite( STDERR, "رابطهٔ {$rel_id} {$parent}→{$child} ثبت نشد\n" );
		exit( 1 );
	}
	$log['relations'][] = array( $rel_id, $parent, get_the_title( $parent ), $child, get_the_title( $child ),
		in_array( (int) $child, array_map( 'intval', (array) $had ), true ) ? 'بود' : 'تازه' );
}

/* ══ گام ۱ — پیش‌نویس‌های وابسته به استان اصفهان ═════════════════════ */

$counties = array_map( 'intval', (array) jet_engine()->relations->get_active_relations( 72 )->get_children( TP_ISF, 'ids' ) );
$cities   = array();
foreach ( $counties as $c ) {
	$cities = array_merge( $cities, array_map( 'intval', (array) jet_engine()->relations->get_active_relations( 73 )->get_children( $c, 'ids' ) ) );
}
$cities = array_values( array_unique( $cities ) );

foreach ( array_merge( $counties, $cities ) as $pid ) {
	$post = get_post( $pid );
	if ( ! $post ) { continue; }
	if ( 'publish' !== $post->post_status ) {
		wp_update_post( array( 'ID' => $pid, 'post_status' => 'publish' ) );
		$post = get_post( $pid );
		if ( 'publish' !== $post->post_status ) {
			$log['skipped'][] = array( $pid, $post->post_type, $post->post_title, 'منتشر نشد: ' . $post->post_status );
			continue;
		}
		$log['published'][] = array( $pid, $post->post_type, $post->post_title );
	}
	if ( '' === (string) get_post_meta( $pid, 'content_status', true ) ) {
		update_post_meta( $pid, 'content_status', TP_STATUS );
	}
}

/* ══ گام ۲ — نمونه‌های کامل ═════════════════════════════════════════ */

/* ── شهر اصفهان و پنج شهر پرجمعیت — جمعیت: سرشماری ۱۳۹۵ ───────────── */
$city_facts = array(
	399 => array( 'population' => '1961260', 'geo_coordinates' => '32.6653, 51.6703' ),
	401 => array( 'population' => '304487',  'geo_coordinates' => '33.9825, 51.4325' ),
	403 => array( 'population' => '247128' ),
	410 => array( 'population' => '235281',  'geo_coordinates' => '32.6331, 51.3650' ),
	413 => array( 'population' => '173329' ),
	414 => array( 'population' => '134952',  'geo_coordinates' => '32.0122, 51.8594' ),
);
$city_src = array( 399 => array( 'prov' ), 401 => array( 'prov', 'kashan' ), 403 => array( 'prov' ),
	410 => array( 'prov', 'najaf' ), 413 => array( 'prov' ), 414 => array( 'prov', 'shahreza' ) );

foreach ( $city_facts as $pid => $meta ) {
	foreach ( $meta as $k => $v ) { update_post_meta( $pid, $k, $v ); }
	update_post_meta( $pid, 'content_status', TP_STATUS );
	tp_add_sources( $pid, $city_src[ $pid ] );
	$log['updated'][] = array( $pid, 'tp_city', get_the_title( $pid ), 'جمعیت ۱۳۹۵' . ( isset( $meta['geo_coordinates'] ) ? ' · مختصات' : '' ) );
}

/* اصفهان — نمونهٔ کاملِ شهر */
foreach ( array(
	'elevation_m'          => '1574',
	'area_km2'             => '551',
	'airport_name'         => 'فرودگاه بین‌المللی شهید بهشتی اصفهان',
	'airport_iata'         => 'IFN',
	'railway_station'      => 'true',
	'unesco_creative_city' => 'true',
	'short_summary'        => 'مرکز استان اصفهان و عضو شبکهٔ شهرهای خلاق یونسکو در رشتهٔ صنایع دستی و هنرهای مردمی.',
	'overview_text'        =>
		'<p>اصفهان مرکز استان اصفهان است و در سرشماری ۱۳۹۵ جمعیتی برابر ۱٬۹۶۱٬۲۶۰ نفر داشته است. ارتفاع شهر از سطح دریا ۱۵۷۴ متر و مساحت آن ۵۵۱ کیلومتر مربع است.</p>'
		. '<h2>میراث جهانی</h2>'
		. '<p>میدان نقش جهان (۱۹۷۹) و مسجد جامع اصفهان (۲۰۱۲) در فهرست میراث جهانی یونسکو ثبت شده‌اند، و باغ چهل‌ستون یکی از نُه باغ مجموعهٔ «باغ ایرانی» (۲۰۱۱) است.</p>'
		. '<h2>صنایع دستی</h2>'
		. '<p>اصفهان در شبکهٔ شهرهای خلاق یونسکو در رشتهٔ صنایع دستی و هنرهای مردمی عضو است.</p>'
		. '<h2>راه ابریشم</h2>'
		. '<p>به نوشتهٔ پروژهٔ Silk Road Seattle دانشگاه واشنگتن، شاه عباس یکم راه ابریشم را از اصفهان گذراند تا تجارت در انحصار امپراتوری‌اش باشد.</p>'
		. '<h2>دسترسی</h2>'
		. '<p>فرودگاه بین‌المللی شهید بهشتی اصفهان (IFN) و ایستگاه راه‌آهن اصفهان به شهر خدمت می‌دهند.</p>',
) as $k => $v ) {
	update_post_meta( 399, $k, $v );
}
tp_add_sources( 399, array( 'isf', 'airport', 'rail', 'creative', 'silk', 'whc115', 'whc1397', 'whc1372' ) );
$log['updated'][] = array( 399, 'tp_city', 'اصفهان', 'ارتفاع · مساحت · فرودگاه · راه‌آهن · شهر خلاق · خلاصه · متن اصلی' );

tp_rel( 96, TP_ISF, 399 );   /* مرکز استان */

/* ── روستای ابیانه ─────────────────────────────────────────────────── */
$abyaneh = tp_upsert( 'tp_village', 'abyaneh', 'ابیانه', array(
	'name_fa' => 'ابیانه', 'name_en' => 'Abyaneh',
	'population' => '301', 'elevation_m' => '2100', 'geo_coordinates' => '33.5867, 51.5900',
	'short_summary' => 'روستایی در دهستان برزرود شهرستان نطنز، در دره‌ای از رشته‌کوه کرکس؛ ۷۰ کیلومتری جنوب‌شرقی کاشان.',
	'overview_text' =>
		'<p>ابیانه روستایی در دهستان برزرود از بخش مرکزی شهرستان نطنز در استان اصفهان است؛ در ۷۰ کیلومتری جنوب‌شرقی کاشان و در ارتفاع ۲۱۰۰ متری از سطح دریا، در دره‌ای از رشته‌کوه کرکس.</p>'
		. '<p>جمعیت آن در سرشماری ۱۳۹۵، ۳۰۱ نفر در ۱۴۷ خانوار بوده است. رنگ سرخ بناهای روستا از خاک رس سرخ پیرامون آن است که اکسید آهن دارد.</p>',
), array( 'abyaneh' ) );
tp_rel( 75, TP_ISF, $abyaneh );

/* ── کویر مرنجاب ───────────────────────────────────────────────────── */
$maranjab = tp_upsert( 'tp_desert', 'maranjab-desert', 'کویر مرنجاب', array(
	'name_fa' => 'کویر مرنجاب', 'name_en' => 'Maranjab Desert',
	'desert_type' => 'mixed', 'provinces_spanning' => 'اصفهان',
	'short_summary' => 'بیابانی در شمال شهرستان آران و بیدگل با تپه‌های ماسه‌ای، پهنه‌های نمکی و تالاب‌های فصلی.',
	'overview_text' =>
		'<p>بیابان مرنجاب در بخش شمالی شهرستان آران و بیدگل در استان اصفهان است و تپه‌های ماسه‌ای، پهنه‌های نمکی و تالاب‌های فصلی دارد. از شمال به دریاچهٔ نمک مسیله محدود می‌شود.</p>'
		. '<p>کاروانسرای مرنجاب در همین بیابان در سال ۱۶۰۳ ساخته شده است.</p>',
), array( 'maranjab' ) );
tp_rel( 80, TP_ISF, $maranjab );

/* ── تالاب گاوخونی ─────────────────────────────────────────────────── */
$gavkhouni = tp_upsert( 'tp_water_body', 'gavkhouni-wetland', 'تالاب گاوخونی', array(
	'name_fa' => 'تالاب گاوخونی', 'name_en' => 'Gavkhouni',
	'water_body_type' => 'wetland', 'salinity_type' => 'hypersaline', 'provinces_spanning' => 'اصفهان',
	'short_summary' => 'تالاب شور پایانهٔ زاینده‌رود در شرق شهر اصفهان؛ ثبت‌شده در کنوانسیون رامسر در ۱۹۷۵.',
	'overview_text' =>
		'<p>گاوخونی تالابی شور در شرق شهر اصفهان و پایانهٔ رود زاینده‌رود است. شوری آن ۳۱٫۵ درصد و ژرفای میانگینش حدود یک متر گزارش شده است.</p>'
		. '<p>این تالاب در سال ۱۹۷۵ در فهرست تالاب‌های بین‌المللی کنوانسیون رامسر ثبت شد.</p>',
), array( 'gavkhouni' ) );
tp_rel( 79, TP_ISF, $gavkhouni );

/* ── کوه کرکس ──────────────────────────────────────────────────────── */
$karkas = tp_upsert( 'tp_mountain', 'mount-karkas', 'کوه کرکس', array(
	'name_fa' => 'کوه کرکس', 'name_en' => 'Mount Karkas',
	'mountain_type' => 'peak', 'elevation_m' => '3895', 'provinces_spanning' => 'اصفهان',
	'short_summary' => 'بلندترین قلهٔ رشته‌کوه کرکس، نزدیک نطنز.',
	'overview_text' => '<p>کرکس بلندترین قلهٔ رشته‌کوه کرکس با ارتفاع ۳۸۹۵ متر است و نزدیک نطنز در استان اصفهان قرار دارد.</p>',
), array( 'karkas' ) );
tp_rel( 78, TP_ISF, $karkas );

/* ── جاذبه‌های ثبت یونسکوی استان ───────────────────────────────────── */
$naqsh = tp_upsert( 'tp_monument', 'naqsh-e-jahan-square', 'میدان نقش جهان', array(
	'name_fa' => 'میدان نقش جهان', 'name_en' => 'Meidan Emam, Esfahan', 'alternative_names' => 'میدان امام',
	'world_heritage' => 'true', 'world_heritage_year' => '1979', 'world_heritage_criteria' => '(i)(v)(vi)',
	'world_heritage_ref_number' => '115', 'national_registration' => 'true',
	'short_summary' => 'میدان شهری عمومی در مرکز اصفهان که شاه عباس یکم در آغاز سدهٔ هفدهم ساخت؛ میراث جهانی یونسکو از ۱۹۷۹.',
), array( 'whc115' ) );
tp_rel( 84, 399, $naqsh );

$jameh = tp_upsert( 'tp_religious_site', 'jameh-mosque-of-isfahan', 'مسجد جامع اصفهان', array(
	'name_fa' => 'مسجد جامع اصفهان', 'name_en' => 'Masjed-e Jāmé of Isfahan',
	'classification' => 'mosque', 'religion' => 'اسلام',
	'world_heritage' => 'true', 'world_heritage_year' => '2012', 'world_heritage_criteria' => '(ii)', 'national_registration' => 'true',
	'short_summary' => 'کهن‌ترین مسجد جامع ایران در مرکز تاریخی اصفهان و نخستین بنای اسلامی با چیدمان چهار ایوانی؛ میراث جهانی یونسکو از ۲۰۱۲.',
), array( 'whc1397' ) );
tp_rel( 86, 399, $jameh );

$chehel = tp_upsert( 'tp_monument', 'chehel-sotoun-garden', 'باغ چهل‌ستون', array(
	'name_fa' => 'باغ چهل‌ستون', 'name_en' => 'Bagh-e Chehel Sotun',
	'world_heritage' => 'true', 'world_heritage_year' => '2011', 'world_heritage_criteria' => '(i)(ii)(iii)(iv)(vi)',
	'world_heritage_ref_number' => '1372-003', 'geo_coordinates' => '32.6575, 51.6722',
	'short_summary' => 'یکی از نُه باغ مجموعهٔ «باغ ایرانی» در فهرست میراث جهانی یونسکو (۲۰۱۱)، در شهر اصفهان.',
), array( 'whc1372' ) );
tp_rel( 84, 399, $chehel );

$fin = tp_upsert( 'tp_monument', 'fin-garden', 'باغ فین', array(
	'name_fa' => 'باغ فین', 'name_en' => 'Bagh-e Fin',
	'world_heritage' => 'true', 'world_heritage_year' => '2011', 'world_heritage_criteria' => '(i)(ii)(iii)(iv)(vi)',
	'world_heritage_ref_number' => '1372-004', 'geo_coordinates' => '33.9463, 51.3725', 'construction_year' => '1590',
	'short_summary' => 'باغ ایرانی در کاشان که در ۱۵۹۰ تکمیل شد؛ یکی از نُه باغ مجموعهٔ «باغ ایرانی» در فهرست میراث جهانی یونسکو (۲۰۱۱).',
), array( 'whc1372', 'fin' ) );
tp_rel( 84, 401, $fin );

$qanats = array(
	array( 'moon-qanat',     'قنات ماه',      'Qanat of the Moon', '1506-008', '33.3792, 52.3750', 'قناتی دوطبقه در اردستان؛ یکی از یازده قنات مجموعهٔ «قنات ایرانی» در فهرست میراث جهانی یونسکو (۲۰۱۶).', array( 'whc1506', 'moon' ), 389 ),
	array( 'vazvan-qanat',   'قنات وزوان',    'Qanat of Vazvan',   '1506-006', '33.4317, 51.1783', 'یکی از یازده قنات مجموعهٔ «قنات ایرانی» در فهرست میراث جهانی یونسکو (۲۰۱۶).', array( 'whc1506' ), 1292 ),
	array( 'mozdabad-qanat', 'قنات مزدآباد',  'Mozd Abad Qanat',   '1506-007', '33.2591, 51.6007', 'قناتی در شهرستان شاهین‌شهر و میمه؛ یکی از یازده قنات مجموعهٔ «قنات ایرانی» در فهرست میراث جهانی یونسکو (۲۰۱۶).', array( 'whc1506', 'mozdwhc', 'mozd' ), 0 ),
);
foreach ( $qanats as $q ) {
	$qid = tp_upsert( 'tp_ancient_site', $q[0], $q[1], array(
		'name_fa' => $q[1], 'name_en' => $q[2],
		'world_heritage' => 'true', 'world_heritage_year' => '2016', 'world_heritage_criteria' => '(iii)(iv)',
		'world_heritage_ref_number' => $q[3], 'geo_coordinates' => $q[4], 'short_summary' => $q[5],
	), $q[6] );
	tp_rel( 92, TP_ISF, $qid );
	if ( $q[7] ) {
		tp_rel( 93, $q[7], $qid );
	}
}

/* کاروانسراها — هشت جزءِ استان اصفهان در جدول ویکی‌پدیا. پیوند به شهر فقط
   جایی که شهری هم‌نام در استان هست (گز، کوهپایه)؛ بقیه بی‌شهر، چون هیچ
   منبعی آن‌ها را به شهری از فهرست نسبت نمی‌دهد. */
$caravans = array(
	array( 'sheikhali-khan-caravanserai', 'کاروانسرای شیخعلی‌خان', 'Sheikhali Khān',  '1668-012', '32.8682, 51.3704', 0 ),
	array( 'maranjab-caravanserai',       'کاروانسرای مرنجاب',     'Maranjāb',        '1668-013', '34.2991, 51.8124', 0 ),
	array( 'amin-abad-caravanserai',      'کاروانسرای امین‌آباد',  'Amin Ābād',       '1668-014', '31.6727, 52.0685', 0 ),
	array( 'gabr-abad-caravanserai',      'کاروانسرای گبرآباد',    'Gabr Ābād',       '1668-015', '33.7686, 51.4895', 0 ),
	array( 'mahyar-caravanserai',         'کاروانسرای مهیار',      'Mahyār',          '1668-016', '32.2650, 51.8097', 0 ),
	array( 'gaz-caravanserai',            'کاروانسرای گز',         'Gaz',             '1668-017', '32.8094, 51.6093', 1289 ),
	array( 'kuhpayeh-caravanserai',       'کاروانسرای کوهپایه',    'Kūhpāyeh',        '1668-018', '32.7134, 52.4358', 1267 ),
	array( 'neyestanak-caravanserai',     'کاروانسرای نیستانک',    'Neyestānak',      '1668-042', '32.9712, 52.7994', 0 ),
);
foreach ( $caravans as $c ) {
	$meta = array(
		'name_fa' => $c[1], 'name_en' => $c[2], 'classification' => 'caravanserai',
		'world_heritage' => 'true', 'world_heritage_year' => '2023', 'world_heritage_criteria' => '(ii)(iii)',
		'world_heritage_ref_number' => $c[3], 'geo_coordinates' => $c[4],
		'short_summary' => 'یکی از پنجاه‌وچهار کاروانسرای مجموعهٔ «کاروانسراهای ایرانی» در فهرست میراث جهانی یونسکو (۲۰۲۳)، در استان اصفهان.',
	);
	$src = array( 'whc1668', 'caravan' );
	if ( 'maranjab-caravanserai' === $c[0] ) {
		$meta['construction_year'] = '1603';
		$meta['short_summary']     = 'کاروانسرایی در بیابان مرنجاب، ساخته‌شده در ۱۶۰۳؛ یکی از پنجاه‌وچهار کاروانسرای مجموعهٔ «کاروانسراهای ایرانی» در فهرست میراث جهانی یونسکو (۲۰۲۳).';
		$src[] = 'maranjab';
	}
	$cid = tp_upsert( 'tp_monument', $c[0], $c[1], $meta, $src );
	if ( $c[5] ) {
		tp_rel( 84, $c[5], $cid );
	}
}

/* ── صنایع دستی، خوراک، جشن ────────────────────────────────────────── */
$khatam = tp_upsert( 'tp_handicraft', 'khatam', 'خاتم‌کاری', array(
	'name_fa' => 'خاتم‌کاری', 'name_en' => 'Khatam', 'classification' => 'inlaid_work',
	'materials' => 'چوب (فوفل، گردو، سرو، کاج)، استخوان، فلز، طلا، نقره، برنج، آلومینیوم و سیم تابیده',
	'technique_summary' => 'هنر کهن ایرانیِ منبت: سطح اشیای چوبی با قطعه‌های ظریف چوب، استخوان و فلز در نقش‌های هندسیِ دقیق آراسته می‌شود.',
	'short_summary' => 'هنر کهن ایرانیِ آراستن سطح چوب با قطعه‌های ظریف چوب، استخوان و فلز؛ اصفهان یکی از مراکز آن است.',
), array( 'khatam' ) );

$mina = tp_upsert( 'tp_handicraft', 'minakari', 'میناکاری', array(
	'name_fa' => 'میناکاری', 'name_en' => 'Minakari',
	'materials' => 'فلز — به‌ویژه مس — و لعاب رنگیِ شیشه‌مانند',
	'technique_summary' => 'لعاب رنگیِ شیشه‌مانند روی فلز — به‌ویژه مس — کشیده و با حرارت کوره تثبیت می‌شود.',
	'short_summary' => 'آراستن فلز با لعاب رنگی که با حرارت تثبیت می‌شود؛ از هنرهای سنتی اصفهان.',
), array( 'mina' ) );

$ghalam = tp_upsert( 'tp_handicraft', 'ghalamzani', 'قلم‌زنی', array(
	'name_fa' => 'قلم‌زنی', 'name_en' => 'Ghalamzani',
	'materials' => 'مس، برنج، نقره و طلا',
	'technique_summary' => 'حکاکیِ نقش‌های ظریف روی فلزهایی چون مس، برنج، نقره و طلا.',
	'short_summary' => 'حکاکی نقش روی فلز؛ اصفهان مرکز اصلی آن است و این هنر نشان جغرافیایی بین‌المللی گرفته است.',
), array( 'ghalam' ) );

foreach ( array( $khatam, $mina, $ghalam ) as $h ) {
	tp_rel( 107, TP_ISF, $h );
	tp_rel( 108, 399, $h );
}

$gaz = tp_upsert( 'tp_local_food', 'gaz', 'گز', array(
	'name_fa' => 'گز', 'name_en' => 'Gaz', 'classification_' => 'sweet_confection',
	'main_ingredients' => 'سفیدهٔ تخم‌مرغ، شکر، پسته و/یا بادام، گلاب؛ و به‌طور سنتی انگبینِ گز',
	'short_summary' => 'نوقای ایرانی که از منطقهٔ اصفهان برخاسته است.',
), array( 'gaz' ) );
tp_rel( 110, TP_ISF, $gaz );
tp_rel( 129, 399, $gaz );

$golab = tp_upsert( 'tp_festival', 'golabgiri-kashan', 'جشن گلاب‌گیری کاشان', array(
	'name_fa' => 'جشن گلاب‌گیری کاشان', 'name_en' => 'Golabgiri',
	'is_recurring' => 'true', 'event_frequency' => 'annual', 'best_season' => 'spring',
	'calendar_timing' => 'هر سال از میانهٔ ماه مه تا میانهٔ ژوئن',
	'venue_name' => 'شهرستان کاشان، به‌ویژه قمصر و نیاسر',
	'short_summary' => 'جشن سالانهٔ گلاب‌گیری در شهرستان کاشان، به‌ویژه قمصر و نیاسر، از میانهٔ مه تا میانهٔ ژوئن.',
), array( 'golab', 'qamsar' ) );
tp_rel( 232, TP_ISF, $golab );
foreach ( array( 401, 1306, 1309 ) as $c ) {
	tp_rel( 233, $c, $golab );
}

/* ── استان‌های هم‌مرز — ویکی‌پدیای استان اصفهان ───────────────────── */
foreach ( array( 129, 119, 115, 117, 123, 134, 127, 106 ) as $n ) {
	tp_rel( 102, TP_ISF, $n );
}
tp_add_sources( TP_ISF, array( 'prov', 'silk' ) );
$log['updated'][] = array( TP_ISF, 'tp_province', 'اصفهان', 'منبع هم‌مرزها، مرکز استان و راه ابریشم افزوده شد' );

/* ── راه ابریشم ────────────────────────────────────────────────────── */
tp_rel( 172, 95, TP_ISF );
tp_rel( 173, 95, 399 );
tp_add_sources( 95, array( 'silk' ) );
$log['updated'][] = array( 95, 'tp_route', get_the_title( 95 ), 'منبع اصفهان افزوده شد' );

/* ══ گزارش ══════════════════════════════════════════════════════════ */

$out  = "ToPersia — دادهٔ محلی برای آزمون بخش‌ها — ۴٫۲٫۳ بند ۹ · P5-D232\n";
$out .= "فقط روی topersia_local. هیچ‌چیز از این فهرست به سایت زنده نمی‌رود.\n";
$out .= "ساخته با: workspace/scripts/tp-local-data.php · " . gmdate( 'Y-m-d H:i' ) . " UTC\n\n";
$out .= "content_status: واژه‌نامهٔ ۳۷ گزینهٔ «نیازمند راستی‌آزمایی» ندارد؛ برای همهٔ پست‌های این فهرست\n";
$out .= "  pending_review («نیازمند بازبینی پس از اجلاس») گذاشته شد — هرگز verified.\n";
$out .= "تصویر: هیچ. زبان: فقط فارسی. رابطه‌ها: API جت‌انجین.\n\n";

$out .= "── منتشرشده (پیش‌نویس‌های وابسته به استان اصفهان) — " . count( $log['published'] ) . " ──\n";
foreach ( $log['published'] as $r ) { $out .= sprintf( "  #%-5d %-10s %s\n", $r[0], $r[1], $r[2] ); }
$out .= "\n── ساخته‌شده — " . count( $log['created'] ) . " ──\n";
foreach ( $log['created'] as $r ) { $out .= sprintf( "  #%-5d %-18s %s  (%s)\n", $r[0], $r[1], $r[2], $r[3] ); }
$out .= "\n── به‌روزشده — " . count( $log['updated'] ) . " ──\n";
foreach ( $log['updated'] as $r ) { $out .= sprintf( "  #%-5d %-12s %s — %s\n", $r[0], $r[1], $r[2], $r[3] ); }
$out .= "\n── رابطه‌ها — " . count( $log['relations'] ) . " ──\n";
foreach ( $log['relations'] as $r ) { $out .= sprintf( "  %-4s #%d %s → #%d %s  [%s]\n", $r[0], $r[1], $r[2], $r[3], $r[4], $r[5] ); }
if ( $log['skipped'] ) {
	$out .= "\n── منتشرنشده ──\n";
	foreach ( $log['skipped'] as $r ) { $out .= sprintf( "  #%-5d %-10s %s — %s\n", $r[0], $r[1], $r[2], $r[3] ); }
}
$out .= "\n── منابع ──\n";
foreach ( $S as $s ) { $out .= '  ' . $s[0] . "\n    " . $s[1] . "\n"; }

file_put_contents( 'C:/wamp64/www/topersia/LOCAL-DATA.txt', $out );

printf( "منتشر: %d · ساخته: %d · به‌روز: %d · رابطه: %d · منتشرنشده: %d\n",
	count( $log['published'] ), count( $log['created'] ), count( $log['updated'] ), count( $log['relations'] ), count( $log['skipped'] ) );
