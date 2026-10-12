<?php
/**
 * آزمون رندر دستهٔ G — سه موجودیت با ساختار کاملاً متفاوت.
 * هدف: اثبات اینکه یک رندرکننده برای هر سه کار می‌کند.
 */

require __DIR__ . '/stubs.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-map.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-weather.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-panel.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-zones.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-relreg.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-air.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-mappoints.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-ai.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-news.php';

/* ── پست‌ها ───────────────────────────────────────────────────────── */

$GLOBALS['tp_posts'] = array(
	10 => array( 'type' => 'tp_country',  'title' => 'ایران',        'url' => 'https://topersia.com/iran/' ),
	20 => array( 'type' => 'tp_province', 'title' => 'استان اصفهان', 'url' => 'https://topersia.com/isfahan-province/' ),
	21 => array( 'type' => 'tp_city',     'title' => 'شهر اصفهان',   'url' => 'https://topersia.com/isfahan/' ),
	22 => array( 'type' => 'tp_county',   'title' => 'کاشان',        'url' => 'https://topersia.com/kashan-county/' ),
	23 => array( 'type' => 'tp_desert',   'title' => 'کویر مرنجاب',  'url' => 'https://topersia.com/maranjab/' ),
	24 => array( 'type' => 'tp_mountain', 'title' => 'کرکس',         'url' => 'https://topersia.com/karkas/' ),
	25 => array( 'type' => 'tp_village',  'title' => 'ابیانه',       'url' => 'https://topersia.com/abyaneh/' ),
	26 => array( 'type' => 'tp_village',  'title' => 'نیاسر',        'url' => 'https://topersia.com/niasar/' ),
	27 => array( 'type' => 'tp_city',     'title' => 'شهر کاشان',    'url' => 'https://topersia.com/kashan/' ),
	28 => array( 'type' => 'tp_city',     'title' => 'آران و بیدگل', 'url' => 'https://topersia.com/aran/' ),
	50 => array( 'type' => 'tp_city',     'title' => 'نطنز', 'url' => 'https://topersia.com/city-50/' ),
	51 => array( 'type' => 'tp_city',     'title' => 'قمصر', 'url' => 'https://topersia.com/city-51/' ),
	52 => array( 'type' => 'tp_city',     'title' => 'جوشقان', 'url' => 'https://topersia.com/city-52/' ),
	53 => array( 'type' => 'tp_city',     'title' => 'ابوزیدآباد', 'url' => 'https://topersia.com/city-53/' ),
	54 => array( 'type' => 'tp_city',     'title' => 'مشکات', 'url' => 'https://topersia.com/city-54/' ),
	55 => array( 'type' => 'tp_city',     'title' => 'برزک', 'url' => 'https://topersia.com/city-55/' ),
	56 => array( 'type' => 'tp_city',     'title' => 'نوش‌آباد', 'url' => 'https://topersia.com/city-56/' ),
	57 => array( 'type' => 'tp_city',     'title' => 'سفیدشهر', 'url' => 'https://topersia.com/city-57/' ),
	/* سه شهر دیگر — بیش از دوازده، تا سقف اسلایدرِ شهرها سنجیده شود.  P5-D220 */
	58 => array( 'type' => 'tp_city',     'title' => 'بادرود', 'url' => 'https://topersia.com/city-58/' ),
	59 => array( 'type' => 'tp_city',     'title' => 'کامو', 'url' => 'https://topersia.com/city-59/' ),
	29 => array( 'type' => 'tp_city',     'title' => 'طرق‌رود', 'url' => 'https://topersia.com/city-29/' ),
	30 => array( 'type' => 'tp_city',     'title' => 'شیراز',        'url' => 'https://topersia.com/shiraz/' ),
	31 => array( 'type' => 'tp_district', 'title' => 'زندیه',        'url' => 'https://topersia.com/zandieh/' ),
	32 => array( 'type' => 'tp_handicraft', 'title' => 'خاتم‌کاری',  'url' => 'https://topersia.com/khatam/' ),
	33 => array( 'type' => 'tp_ancient_site', 'title' => 'تخت جمشید', 'url' => 'https://topersia.com/persepolis/' ),
	34 => array( 'type' => 'tp_route',    'title' => 'مسیر پارس',    'url' => 'https://topersia.com/pars-route/' ),
	35 => array( 'type' => 'tp_border_crossing', 'title' => 'گذر مرزی', 'url' => 'https://topersia.com/crossing/' ),
	36 => array( 'type' => 'tp_county',   'title' => 'شهرستان شیراز', 'url' => 'https://topersia.com/shiraz-county/' ),
	37 => array( 'type' => 'tp_mountain', 'title' => 'زیرقلهٔ کرکس', 'url' => 'https://topersia.com/karkas-sub/' ),
	40 => array( 'type' => 'tp_desert',   'title' => 'دشت لوت',      'url' => 'https://topersia.com/lut/' ),

	/* جاذبه‌ها و فرهنگ — اعضای بخش‌هایی که تا ۴٫۲٫۰ ساخته نشده بودند */
	60 => array( 'type' => 'tp_monument',       'title' => 'میدان نقش جهان', 'url' => 'https://topersia.com/naqsh-e-jahan/' ),
	61 => array( 'type' => 'tp_religious_site', 'title' => 'مسجد جامع',      'url' => 'https://topersia.com/jame-mosque/' ),
	62 => array( 'type' => 'tp_museum',         'title' => 'موزهٔ آران',      'url' => 'https://topersia.com/aran-museum/' ),
	63 => array( 'type' => 'tp_handicraft',     'title' => 'میناکاری',       'url' => 'https://topersia.com/mina/' ),
	64 => array( 'type' => 'tp_local_food',     'title' => 'بریانی',         'url' => 'https://topersia.com/biryani/' ),

	/* چهار کارت ستون کناری که تا ۴٫۲٫۰ در داده‌ٔ آزمون خالی بودند و آزمون
	   پذیرش هیچ‌وقت جفتشان نمی‌کرد. */
	65 => array( 'type' => 'tp_province',       'title' => 'استان یزد',      'url' => 'https://topersia.com/yazd-province/' ),
	66 => array( 'type' => 'tp_route',          'title' => 'راه ابریشم',     'url' => 'https://topersia.com/silk-road/' ),
	67 => array( 'type' => 'tp_ancient_site',   'title' => 'تپهٔ سیلک',      'url' => 'https://topersia.com/sialk/' ),
	68 => array( 'type' => 'tp_health_safety',  'title' => 'سلامت و ایمنی',  'url' => 'https://topersia.com/health/' ),
	69 => array( 'type' => 'tp_currency',       'title' => 'واحد پول',       'url' => 'https://topersia.com/currency/' ),
	/* «اطلاعات سفر» کشور — ارث‌بری استان و شهر از آن.  ۴٫۳٫۰ بند ۸ */
	70 => array( 'type' => 'tp_travel_etiquette', 'title' => 'پوشش در اماکن عمومی', 'url' => 'https://topersia.com/dress-code/' ),
	71 => array( 'type' => 'tp_calendar',       'title' => 'تقویم رسمی ایران', 'url' => 'https://topersia.com/calendar/' ),
	/* پیش‌نویس: نه پیوند می‌گیرد، نه ارث‌بری را می‌بندد */
	72 => array( 'type' => 'tp_calendar',       'title' => 'تقویم پیش‌نویس استان', 'url' => 'https://topersia.com/calendar-draft/', 'status' => 'draft' ),
	/* دو پیوست گالری با عنوان — یکی با توضیحِ جدا، یکی با توضیحی که همان عنوان است.  بند ۸ */
	601 => array( 'type' => 'attachment', 'title' => 'میدان نقش جهان' ),
	602 => array( 'type' => 'attachment', 'title' => 'چهل‌ستون' ),
);

$GLOBALS['tp_captions'] = array(
	601 => 'میدان امام، ۱۶۰۲',
	602 => 'چهل‌ستون',
);

/* ── روابط — جهت از export، نه از سند ─────────────────────────────── */

$GLOBALS['tp_rel'] = array(
	'71'  => array( 'parents'  => array( 20 => array( 10 ) ) ),                  // country_province
	'96'  => array( 'children' => array( 20 => array( 21 ) ) ),                  // capital_of_province
	'72'  => array( 'children' => array( 20 => array( 22 ) ),
	                'parents'  => array( 22 => array( 20 ) ) ),                  // province_county
	/* شهرستان→شهر — پلهٔ دوم زنجیرهٔ شهرهای استان.  P5-D210 */
	'73'  => array( 'children' => array( 22 => array( 27, 28, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 29 ) ),
	                'parents'  => array( 27 => array( 22 ) ) ),                  // county_city
	'80'  => array( 'children' => array( 20 => array( 23 ) ) ),                  // province_desert
	/* هر دو سو، چون کارت رابطه والدِ مکانیِ موجودیتِ مقابل را می‌خواهد
	   و آن از سوی `parents` خوانده می‌شود.  P5-D204 */
	'78'  => array( 'children' => array( 20 => array( 24 ) ),
	                'parents'  => array( 24 => array( 20 ) ) ),                  // province_mountain
	'102' => array( 'children' => array( 20 => array( 65 ) ) ),                  // borders_province
	'92'  => array( 'children' => array( 20 => array( 67 ) ) ),                  // province_ancient
	'157' => array( 'children' => array( 20 => array( 68 ) ) ),                  // applies_g3_p4
	'227' => array( 'children' => array( 20 => array( 69 ) ) ),                  // applies_g3_p2
	'229' => array( 'children' => array( 20 => array( 72 ) ) ),                  // applies_g3_p6 — پیش‌نویس
	'159' => array( 'children' => array( 10 => array( 70 ) ) ),                  // applies_g1_p5
	'228' => array( 'children' => array( 10 => array( 71 ) ) ),                  // applies_g1_p6
	/* مسیر: والدِ استان است (۱۷۲)، و شهرهایش از ۱۷۳ — کاشان و نطنز هر دو
	   زیر همین استان‌اند، پس سطرِ «از کجا می‌گذرد» باید هر دو را نشان دهد. */
	'172' => array( 'parents'  => array( 20 => array( 66 ) ) ),                  // passes_g3_p9
	'173' => array( 'children' => array( 66 => array( 27, 50 ) ),
	                'parents'  => array( 30 => array( 34 ) ) ),                  // passes_g5_p9
	'75'  => array( 'children' => array( 20 => array( 25, 26 ) ),
	                'parents'  => array( 25 => array( 20 ), 26 => array( 20 ) ) ), // province_village

	/* شهر — گروه‌هایی که تا پیش از نقشهٔ نوع‌محور هرگز رندر نمی‌شدند */
	'74'  => array( 'children' => array( 30 => array( 31 ) ) ),                  // city_district
	'93'  => array( 'children' => array( 30 => array( 33 ) ) ),                  // city_ancient
	'108' => array( 'children' => array( 30 => array( 32 ) ) ),                  // practiced_g5_c1
	'100' => array( 'children' => array( 30 => array( 35 ) ) ),                  // nearest_city_border
	/* هر دو سو: کارت شهرستانِ `#places` از سوی `children` شهرِ مرکز را
	   پیدا می‌کند، چون خودِ شهرستان صفحه ندارد.  P5-D215 */
	'97'  => array( 'parents'  => array( 30 => array( 36 ) ),
	                'children' => array( 22 => array( 27 ) ) ),                  // capital_of_county

	/* کوه — رابطهٔ خودارجاع یک‌به‌چند، نه هم‌مرزی */
	'101' => array( 'children' => array( 24 => array( 37 ) ) ),                  // range_parent

	/*
	 * جاذبه‌های استان — از راه زنجیرهٔ ۷۲→۷۳→۸۴/۸۶/۸۸/۹۳.  P5-D219
	 * بی‌این، `#attractions` روی همهٔ سه مورد خالی می‌ماند و شاخهٔ پُرش
	 * هیچ‌وقت اجرا نمی‌شود.
	 */
	'84'  => array( 'children' => array( 27 => array( 60 ) ),
	                'parents'  => array( 60 => array( 27 ) ) ),                  // city_monument
	'86'  => array( 'children' => array( 27 => array( 61 ) ),
	                'parents'  => array( 61 => array( 27 ) ) ),                  // city_religious
	'88'  => array( 'children' => array( 28 => array( 62 ) ),
	                'parents'  => array( 62 => array( 28 ) ) ),                  // city_museum

	/* فرهنگ استان — دو خانواده، تا شبکهٔ `#culture` هم اجرا شود */
	'107' => array( 'children' => array( 20 => array( 63 ) ) ),                  // practiced_g3_c1
	'110' => array( 'children' => array( 20 => array( 64 ) ) ),                  // practiced_g3_c2
);

/* ── موجودیت‌های مقابل در کارت رابطه ───────────────────────────────
   کم ولی عمدی: فقط آن‌قدر که پاورقی کارت و توضیح کوتاه هر دو مقدار
   بگیرند. بی‌این، تنها شاخهٔ «خالی ⇒ ناپدید» آزمون می‌شد و شاخهٔ پُر
   هیچ‌وقت اجرا نمی‌شد.  P5-D204 */
$GLOBALS['tp_meta'][27] = array(
	'name_fa'       => 'کاشان',
	'short_summary' => 'شهر گلاب و خانه‌های تاریخی، در حاشیهٔ کویر مرکزی.',
	'population'    => 304487,
);

$GLOBALS['tp_meta'][28] = array(
	'name_fa'       => 'آران و بیدگل',
	'short_summary' => 'دروازهٔ کویر مرنجاب و بافت کهن.',
	'population'    => 65000,
);

$GLOBALS['tp_meta'][25] = array(
	'name_fa'       => 'ابیانه',
	'short_summary' => 'روستای سرخ‌رنگ دامنهٔ کرکس، با بافت پلکانی.',
	'elevation_m'   => 2235,
);

$GLOBALS['tp_meta'][26] = array(
	'name_fa'       => 'نیاسر',
	'short_summary' => 'آبشار و غار دست‌کند، در دامنهٔ کرکس.',
	'elevation_m'   => 1630,
);

$GLOBALS['tp_meta'][22] = array(
	'name_fa'       => 'کاشان',
	'short_summary' => 'شهر آب‌انبارها و خانه‌های تاریخی، در حاشیهٔ کویر.',
	'population'    => 364482,
);

$GLOBALS['tp_meta'][24] = array(
	'name_fa'       => 'کرکس',
	'short_summary' => 'بلندترین قلهٔ مرکز ایران، مشرف به کاشان و نطنز.',
	'elevation_m'   => 3899,
);

/* ── G3 استان ─────────────────────────────────────────────────────── */

$GLOBALS['tp_meta'][20] = array(
	'name_fa'                => 'استان اصفهان',
	'official_name_en'       => 'Isfahan Province',
	'official_name_ar'       => 'محافظة أصفهان',
	'name_local'             => 'ایالت اصفهان',
	'alternative_names'      => 'سپاهان · نصف جهان',
	'short_summary'          => 'سومین استان پهناور ایران در مرکز فلات.',
	/* «متن اصلی صفحه» — wysiwyg با سرفصل خودش، به همان شکلِ متنِ چسبانده در
	   سایت: شناسهٔ فارسی و کلاس بیگانه روی سرفصل، style روی پاراگراف و یک
	   div پوشش. خروجی باید هیچ‌کدام را نداشته باشد.  P5-D230 */
	'overview_text'          => '<div class="markdown prose"><p class="text-token-text-primary">استان اصفهان در <strong>مرکز فلات ایران</strong> است.</p>'
		. '<h2 id="جغرافیا-و-تقسیمات" class="scroll-mt-24 text-[16pt] font-semibold">جغرافیا و تقسیمات</h2>'
		. '<p style="color:red">مرکز استان شهر اصفهان است.</p>'
		. '<ul class="list-disc"><li>کاشان</li><li>نطنز</li></ul></div>',
	'hero_image'             => 501,
	/* هجده تصویر — همان شماری که مرجع در مودال دارد، پس الگوی کاشیِ
	   پهن (اول و نهم) و شمارِ «+N تصویر» هر دو اجرا می‌شوند. */
	'featured_image_gallery' => '601,602,603,604,605,606,607,608,609,610,611,612,613,614,615,616,617,618',

	/* ناحیهٔ `details` — این‌ها `#overview` را می‌سازند، نه کارت جغرافیا. */
	'geographic_boundary'    => 'از شمال به قم و سمنان، از شرق به یزد، از جنوب به فارس و از غرب به لرستان.',
	'climate_overview'       => 'سه اقلیم در یک استان: کوهستانی در غرب، بیابانی در شرق، معتدل در مرکز.',
	'historical_context'     => 'هستهٔ تاریخی استان از سدهٔ یازدهم میلادی یکی از مراکز معماری ایرانی است.',
	'transport_access'       => 'فرودگاه بین‌المللی، راه‌آهن سراسری و بزرگراه‌های شمال–جنوب.',

	/* خلاصهٔ هوش مصنوعی. «برنامهٔ سفر» فیلدی در JetEngine ندارد، پس در
	   داده‌ٔ آزمون هم نیست — آزمونِ فیلدی که وجود ندارد، آزمون خیال است. */
	'ai_summary'             => 'استان اصفهان در مرکز ایران، سومین استان پهناور کشور، با مرکزیت شهر اصفهان.',

	/* یک نشان روشن، تا بخش «نشان‌ها» هم رندر شود */
	'unesco_creative_city'   => 'true',
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
		/* ردیفِ ویدیوییِ یوتیوب — در شاسیِ پخش، نه رفتن از صفحه.  P5-D222 */
		'item-3' => array( 'audio_title' => 'روایت تصویری میدان', 'audio_external_url' => 'https://www.youtube.com/watch?v=Zm9vYmFyMTI', 'audio_duration' => '4:12', 'audio_row_status' => 'active' ),
	),
	/* ویدیوی نمونهٔ آپارات — کاشیِ بزرگ گالری پوسترش می‌شود.  P5-D222 */
	'video_sample'           => 'https://www.aparat.com/v/Qw7kd',
	'faq_items'              => array(
		'item-1' => array( 'faq_question' => 'بهترین زمان سفر به اصفهان کی است؟', 'faq_answer' => 'بهار، به‌ویژه اردیبهشت.', 'faq_row_status' => 'active' ),
		'item-2' => array( 'faq_question' => 'پرسش بازنشسته', 'faq_answer' => 'نباید دیده شود.', 'faq_row_status' => 'not_applicable' ),
	),
	'source_references'      => array(
		'item-1' => array( 'source_name' => 'مرکز آمار ایران', 'source_url' => 'https://amar.org.ir', 'source_row_status' => 'active' ),
		'item-2' => array( 'source_name' => 'یونسکو', 'source_url' => '', 'source_row_status' => '' ),
	),
);

/* اعضای جاذبه — پاورقی کارت شهر · سال ثبت می‌خواهد */
$GLOBALS['tp_meta'][60] = array(
	'name_fa'             => 'میدان نقش جهان',
	'short_summary'       => 'مجموعهٔ شهری صفوی با مسجد امام و عالی‌قاپو.',
	'world_heritage'      => 'true',
	'world_heritage_year' => '1979',
);

$GLOBALS['tp_meta'][61] = array(
	'name_fa'       => 'مسجد جامع',
	'short_summary' => 'لایه‌نشانی هزار سالهٔ معماری ایرانی.',
);

$GLOBALS['tp_meta'][62] = array(
	'name_fa'       => 'موزهٔ آران',
	'short_summary' => 'مجموعهٔ مردم‌شناسی حاشیهٔ کویر.',
);

/* همسایه — جهت از دو مختصات. یزد نسبت به اصفهان «شرق» است، همان که مرجع
   نوشته بود؛ اگر کاوش جهت غلط باشد، این‌جا چیز دیگری درمی‌آید. */
$GLOBALS['tp_meta'][65] = array( 'name_fa' => 'یزد', 'geo_coordinates' => '31.8974, 54.3569' );
$GLOBALS['tp_meta'][66] = array( 'name_fa' => 'راه ابریشم' );
$GLOBALS['tp_meta'][67] = array( 'name_fa' => 'تپهٔ سیلک' );
$GLOBALS['tp_meta'][68] = array( 'name_fa' => 'سلامت و ایمنی' );
$GLOBALS['tp_meta'][69] = array( 'name_fa' => 'واحد پول' );

$GLOBALS['tp_meta'][63] = array( 'name_fa' => 'میناکاری' );
$GLOBALS['tp_meta'][64] = array( 'name_fa' => 'بریانی' );

/*
 * نظرها — JetReviews روی سایت زنده است و اینجا نیست، پس همان فیلتری که
 * افزونه از آن می‌خواند، دادهٔ آزمون را می‌دهد. شاخهٔ «بی‌نظر ⇒ ناپدید»
 * با پست‌های دیگر آزمون می‌شود.
 */
add_filter( 'topersia_g_reviews_data', function ( $data, $post_id ) {

	if ( 20 !== (int) $post_id ) {
		return $data;
	}

	return array(
		'avg'   => 4.6,
		'count' => 214,
		'dist'  => array( 1 => 2, 2 => 5, 3 => 13, 4 => 36, 5 => 158 ),
		'last'  => array(
			array( 'author' => 'مریم ک.', 'score' => 5, 'text' => 'پاییز رفتیم و هوا عالی بود.' ),
			array( 'author' => 'سهیل ر.', 'score' => 4, 'text' => 'برای کویر حتماً راهنمای محلی بگیرید.' ),
		),
	);
}, 10, 2 );

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

/*
 * گروه اسلایدر و شبکه از نقشهٔ همان نوع پست می‌آید، نه ثابت.
 *
 * پیش‌تر هر سه مورد `counties` و `nature` می‌پرسیدند — شکلِ استان. شهر
 * شهرستان ندارد و کویر هیچ‌کدام، پس بازبینی چشمی برایشان خالی درمی‌آمد و
 * خروجی با هر تغییری بایت‌به‌بایت یکسان می‌ماند.
 */
function tp_case_group( $pid, $zone ) {
	$type  = get_post_type( $pid );
	$map   = topersia_g_relmap();
	$zones = topersia_g_rel_zones();
	if ( empty( $map[ $type ] ) ) { return ''; }
	foreach ( $zones as $key => $spec ) {
		if ( empty( $map[ $type ][ $key ] ) ) { continue; }
		if ( $zone === ( 'topersia_g_sc_slider' === $spec[1] ? 'slider' : 'relgrid' ) ) { return $key; }
	}
	return '';
}

$cases = array(
	20 => array( 'نام' => 'G3 — استان اصفهان' ),
	30 => array( 'نام' => 'G5 — شهر شیراز' ),
	24 => array( 'نام' => 'G9 — کوه کرکس' ),
	40 => array( 'نام' => 'G11 — دشت لوت' ),
);

foreach ( $cases as $case_pid => $ignored ) {
	$cases[ $case_pid ]['slider']  = tp_case_group( $case_pid, 'slider' );
	$cases[ $case_pid ]['relgrid'] = tp_case_group( $case_pid, 'relgrid' );
}

$summary = array();

foreach ( $cases as $pid => $case ) {

	$GLOBALS['tp_current'] = $pid;

	echo "\n" . str_repeat( '═', 78 ) . "\n";
	echo '  ' . $case['نام'] . "   (post_id {$pid} · " . get_post_type( $pid ) . ")\n";
	echo '  اسلایدر: ' . ( $case['slider'] ? $case['slider'] : '—' )
		. '   شبکه: ' . ( $case['relgrid'] ? $case['relgrid'] : '—' ) . "\n";
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
