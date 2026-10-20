<?php
/**
 * ToPersia — نقشۀ رندر دستۀ G
 *
 * بخشی از افزونۀ «هستۀ نمایش». این فایل هیچ HTML تولید نمی‌کند؛ فقط
 * می‌گوید هر فیلد چه برچسبی دارد، از چه نوعی است و در کدام ناحیه می‌نشیند.
 *
 * ── چرا نقشه، و نه شرط به‌ازای نوع پست ────────────────────────────────
 * سیزده نوع پست دستۀ G روی هم ۱۰۸ کاربرد فیلد دارند ولی فقط ۵۷ نام یکتا،
 * و هیچ نامی در دو نوع مختلف، نوع داده‌ای متفاوت ندارد. این در export
 * ۱۳۹۹/۰۹/۰۹ راستی‌آزمایی شد. پس یک رندرکنندۀ نوع‌محور برای هر سیزده کافی
 * است و افزودن G14 با فیلدهای موجود، صفر خط کد می‌خواهد.  P5-D147
 *
 * ── تصمیم‌های حاکم بر این فایل ────────────────────────────────────────
 *   P5-D144  لایۀ نمایش افزونۀ مستقل است؛ هسته دست نمی‌خورد.
 *   P5-D145  دادۀ مشترک HTML و JSON-LD از یک تولیدکننده.
 *   P5-D168  منبع واحد برچسب و واحد، همین نقشه است — نه عنوان فیلد
 *            JetEngine. عنوان‌های JetEngine ناسازگارند (چهار مورد
 *            راستی‌آزمایی‌شده) و بعضی واحد را داخل خودشان دارند.
 *   P5-D169  نوع پنجم `choice`: سیزده فیلد select مقدار واژه‌نامه ذخیره
 *            می‌کنند («peak»)، نه برچسب («قله»). بدون این نوع، صفحه
 *            اسلاگ انگلیسی چاپ می‌کرد.
 *   P5-D175  برچسب geo_boundary از «مختصات جغرافیایی» به «محدودۀ
 *            جغرافیایی» اصلاح شد. در متاباکس G3 و G10 این فیلد دقیقاً
 *            هم‌نام geo_coordinates بود و دو کارت هم‌عنوان می‌ساخت.
 *            راستی‌آزمایی: geo_boundary و geographic_boundary هرگز در یک
 *            نوع پست با هم نمی‌آیند، پس هم‌نامی‌شان بی‌خطر است.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * نقشۀ ۵۷ فیلد اختصاصی دستۀ G.
 *
 * kind:  number · year · latin · flag · text · choice
 * zone:  card · details · badge · names · neighbors · travel · skip
 *        neighbors: فهرست متنی همسایه‌ها ← کاشی‌های «همسایه‌ها» در ستون کناری (P5-D281)
 *        travel:    «اطلاعات سفر» — وضعیت اداری و دسترسی، نه افتخار (P5-D279 · P5-D282)
 *        badge:     فقط عنوان رسمی افتخاری، جفت‌شده با سال (P5-D279)
 *        پرچمِ card یا travel فقط روشن می‌آید، با مقدار «بله»
 * year:  کلید فیلد سالی که با این نشان جفت می‌شود.
 * glossary: شناسۀ واژه‌نامۀ JetEngine برای فیلدهای choice.
 *
 * @return array
 */
if ( ! function_exists( 'topersia_g_map' ) ) {
	function topersia_g_map() {

		static $map = null;

		if ( null !== $map ) {
			return $map;
		}

		$map = array(
		'official_name_fa'           => array( 'label' => 'نام رسمی فارسی', 'unit' => '', 'kind' => 'text', 'zone' => 'names' ),
		'official_name_en'           => array( 'label' => 'نام رسمی انگلیسی', 'unit' => '', 'kind' => 'text', 'zone' => 'names' ),
		'official_name_ar'           => array( 'label' => 'نام رسمی عربی', 'unit' => '', 'kind' => 'text', 'zone' => 'names' ),
		'geo_coordinates'            => array( 'label' => 'مختصات جغرافیایی', 'unit' => '', 'kind' => 'latin', 'zone' => 'card' ),
		'population'                 => array( 'label' => 'جمعیت', 'unit' => 'نفر', 'kind' => 'number', 'zone' => 'card' ),
		'area_km2'                   => array( 'label' => 'مساحت', 'unit' => 'کیلومتر مربع', 'abbr' => 'km²', 'kind' => 'number', 'zone' => 'card' ),
		'official_language'          => array( 'label' => 'زبان رسمی', 'unit' => '', 'kind' => 'text', 'zone' => 'card' ),
		'timezone'                   => array( 'label' => 'منطقۀ زمانی', 'unit' => '', 'kind' => 'text', 'zone' => 'card' ),
		'currency_note'              => array( 'label' => 'یادداشت ارز', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),
		'government_type'            => array( 'label' => 'نوع حکومت', 'unit' => '', 'kind' => 'text', 'zone' => 'card' ),
		'neighboring_countries'      => array( 'label' => 'کشورهای همسایه', 'unit' => '', 'kind' => 'text', 'zone' => 'neighbors' ),
		'calling_code'               => array( 'label' => 'پیش‌شمارۀ تلفن', 'unit' => '', 'kind' => 'latin', 'zone' => 'card' ),
		'best_season'                => array( 'label' => 'مناسب‌ترین فصل برای سفر', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '34' ),
		'internet_tld'               => array( 'label' => 'دامنۀ اینترنتی', 'unit' => '', 'kind' => 'latin', 'zone' => 'card' ),
		'region_classification'      => array( 'label' => 'نوع منطقه', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '38' ),
		'geographic_boundary'        => array( 'label' => 'محدودۀ جغرافیایی', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),
		'climate_overview'           => array( 'label' => 'مرور اقلیم', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),
		'historical_context'         => array( 'label' => 'زمینۀ تاریخی', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),
		'province_code'              => array( 'label' => 'کد استان', 'unit' => '', 'kind' => 'latin', 'zone' => 'skip' ),   /* P5-D386: فیلد و داده می‌ماند؛ هیچ‌جا نمایش داده نمی‌شود */
		'geo_boundary'               => array( 'label' => 'محدودۀ جغرافیایی', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),
		'bordering_countries'        => array( 'label' => 'کشورهای هم‌مرز', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),
		'climate_type'               => array( 'label' => 'نوع اقلیم', 'unit' => '', 'kind' => 'text', 'zone' => 'card' ),
		'free_zone'                  => array( 'label' => 'منطقۀ آزاد', 'unit' => '', 'kind' => 'flag', 'zone' => 'travel' ),
		'elevation_m'                => array( 'label' => 'ارتفاع از سطح دریا', 'unit' => 'متر', 'kind' => 'number', 'zone' => 'card' ),
		'transport_access'           => array( 'label' => 'دسترسی و حمل‌ونقل', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),
		'founded_year'               => array( 'label' => 'سال بنیان‌گذاری', 'unit' => '', 'kind' => 'year', 'zone' => 'card' ),
		'airport_name'               => array( 'label' => 'نام فرودگاه', 'unit' => '', 'kind' => 'text', 'zone' => 'travel' ),
		'airport_iata'               => array( 'label' => 'کد فرودگاه', 'unit' => '', 'kind' => 'latin', 'zone' => 'travel' ),
		'railway_station'            => array( 'label' => 'ایستگاه راه‌آهن', 'unit' => '', 'kind' => 'flag', 'zone' => 'travel' ),
		'unesco_creative_city'       => array( 'label' => 'شهر خلاق یونسکو', 'unit' => '', 'kind' => 'flag', 'zone' => 'badge' ),
		'neighborhood_type'          => array( 'label' => 'نوع محله', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '39' ),
		'tourism_target_village'     => array( 'label' => 'روستای هدف گردشگری', 'unit' => '', 'kind' => 'flag', 'zone' => 'badge' ),
		'island_type'                => array( 'label' => 'نوع جزیره', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '40' ),
		'access_method'              => array( 'label' => 'راه دسترسی', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),
		'mountain_type'              => array( 'label' => 'نوع عارضه', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '41' ),
		'provinces_spanning'         => array( 'label' => 'استان‌های گسترده در آن', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),
		'trekking_difficulty'        => array( 'label' => 'دشواری صعود', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '42' ),
		'volcanic'                   => array( 'label' => 'آتشفشانی', 'unit' => '', 'kind' => 'flag', 'zone' => 'card' ),
		'national_monument'          => array( 'label' => 'اثر طبیعی ملی', 'unit' => '', 'kind' => 'flag', 'zone' => 'badge', 'year' => 'national_monument_year' ),
		'national_monument_year'     => array( 'label' => 'سال ثبت اثر طبیعی ملی', 'unit' => '', 'kind' => 'year', 'zone' => 'skip' ),
		'water_body_type'            => array( 'label' => 'نوع پهنۀ آبی', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '43' ),
		'length_km'                  => array( 'label' => 'طول', 'unit' => 'کیلومتر', 'abbr' => 'km', 'kind' => 'number', 'zone' => 'card' ),
		'max_depth_m'                => array( 'label' => 'حداکثر عمق', 'unit' => 'متر', 'kind' => 'number', 'zone' => 'card' ),
		'salinity_type'              => array( 'label' => 'نوع شوری', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '44' ),
		'biosphere_reserve'          => array( 'label' => 'ذخیره‌گاه زیست‌کره', 'unit' => '', 'kind' => 'flag', 'zone' => 'badge', 'year' => 'biosphere_year' ),
		'biosphere_year'             => array( 'label' => 'سال پیوستن به ذخیره‌گاه زیست‌کره', 'unit' => '', 'kind' => 'year', 'zone' => 'skip' ),
		'desert_type'                => array( 'label' => 'نوع کویر', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '45' ),
		'max_temperature_c'          => array( 'label' => 'حداکثر دمای ثبت‌شده', 'unit' => 'درجۀ سانتی‌گراد', 'abbr' => '°C', 'kind' => 'number', 'zone' => 'card' ),
		'world_heritage'             => array( 'label' => 'میراث جهانی', 'unit' => '', 'kind' => 'flag', 'zone' => 'badge', 'year' => 'world_heritage_year' ),
		'world_heritage_year'        => array( 'label' => 'سال ثبت میراث جهانی', 'unit' => '', 'kind' => 'year', 'zone' => 'skip' ),
		'world_heritage_criteria'    => array( 'label' => 'معیار ثبت میراث جهانی', 'unit' => '', 'kind' => 'latin', 'zone' => 'card' ),
		'area_type'                  => array( 'label' => 'نوع پهنه', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '46' ),
		'forest_type'                => array( 'label' => 'نوع جنگل', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '47' ),
		'crossing_type'              => array( 'label' => 'نوع گذرگاه', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '48' ),
		'border_country'             => array( 'label' => 'کشور مقابل', 'unit' => '', 'kind' => 'text', 'zone' => 'card' ),
		'status'                     => array( 'label' => 'وضعیت', 'unit' => '', 'kind' => 'choice', 'zone' => 'card', 'glossary' => '49' ),
		'operating_hours'            => array( 'label' => 'ساعات کاری', 'unit' => '', 'kind' => 'text', 'zone' => 'details' ),

		/*
		 * ۲ج٫۱ — فیلدهای کوتاه نوع‌های غیر G، فقط برای کارت آمار سربرگ (سطر `stats`
		 * ماتریس `topersia_g_type_matrix()`). ناحیۀ stat: هیچ‌جای دیگر خودبه‌خود
		 * نمی‌آیند. منبع نام، نوع و واژه‌نامه: Jetengine_Skin.json؛ برچسب کوتاه و
		 * واحد از همین نقشه (P5-D168). `classification` در هر نوع واژه‌نامۀ خودش را
		 * دارد — glossary به‌ازای نوع پست.
		 */
		'classification'             => array( 'label' => 'گونه', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => array( 'tp_monument' => '51', 'tp_religious_site' => '53', 'tp_museum' => '55', 'tp_natural_feature' => '57', 'tp_handicraft' => '60', 'tp_costume' => '63', 'tp_music' => '64', 'tp_ritual' => '66', 'tp_figure' => '68', 'tp_route' => '171' ) ),
		'classification_'            => array( 'label' => 'گونه', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '62' ),
		'dance_type'                 => array( 'label' => 'گونه', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '65' ),
		'suggested_duration'         => array( 'label' => 'مدت بازدید', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '35' ),
		'construction_year'          => array( 'label' => 'سال ساخت', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'architectural_style'        => array( 'label' => 'سبک معماری', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'restoration_status'         => array( 'label' => 'وضعیت مرمت', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '52' ),
		'religion'                   => array( 'label' => 'دین', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'photography_policy'         => array( 'label' => 'عکاسی', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '54' ),
		'excavation_status'          => array( 'label' => 'وضعیت کاوش', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '58' ),
		'height_m'                   => array( 'label' => 'ارتفاع', 'unit' => 'متر', 'kind' => 'number', 'zone' => 'stat' ),
		'depth_m'                    => array( 'label' => 'عمق', 'unit' => 'متر', 'kind' => 'number', 'zone' => 'stat' ),
		'park_type'                  => array( 'label' => 'نوع حفاظت', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '56' ),
		'landscape_type'             => array( 'label' => 'نوع منظر', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '59' ),
		'total_area_km2'             => array( 'label' => 'مساحت', 'unit' => 'کیلومتر مربع', 'abbr' => 'km²', 'kind' => 'number', 'zone' => 'stat' ),
		'intangible_heritage_year'   => array( 'label' => 'سال ثبت یونسکو', 'unit' => '', 'kind' => 'year', 'zone' => 'stat' ),
		'intangible_heritage_status' => array( 'label' => 'وضعیت ثبت', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '61' ),
		'spatial_scope'              => array( 'label' => 'گستره', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '67' ),
		'language_family'            => array( 'label' => 'خانوادۀ زبانی', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'script'                     => array( 'label' => 'خط', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'speaker_estimate_iran'      => array( 'label' => 'گویشوران در ایران', 'unit' => 'نفر', 'kind' => 'number', 'zone' => 'stat' ),
		'name_endonym'               => array( 'label' => 'نام بومی', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'population_estimate'        => array( 'label' => 'جمعیت تخمینی', 'unit' => 'نفر', 'kind' => 'number', 'zone' => 'stat' ),
		'birth_year_approx'          => array( 'label' => 'سال تولد', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'death_year_approx'          => array( 'label' => 'سال درگذشت', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'start_year_approx'          => array( 'label' => 'آغاز', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'end_year_approx'            => array( 'label' => 'پایان', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'emergency_police'           => array( 'label' => 'پلیس', 'unit' => '', 'kind' => 'latin', 'zone' => 'stat' ),
		'emergency_ambulance'        => array( 'label' => 'اورژانس', 'unit' => '', 'kind' => 'latin', 'zone' => 'stat' ),
		'emergency_fire'             => array( 'label' => 'آتش‌نشانی', 'unit' => '', 'kind' => 'latin', 'zone' => 'stat' ),
		'programme_start_year'       => array( 'label' => 'سال آغاز برنامه', 'unit' => '', 'kind' => 'year', 'zone' => 'stat' ),
		'total_distance_km'          => array( 'label' => 'مسافت', 'unit' => 'کیلومتر', 'abbr' => 'km', 'kind' => 'number', 'zone' => 'stat' ),
		'estimated_duration_days'    => array( 'label' => 'مدت پیشنهادی', 'unit' => 'روز', 'kind' => 'number', 'zone' => 'stat' ),
		'difficulty_level'           => array( 'label' => 'دشواری', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '176' ),
		'passport_validity_months'   => array( 'label' => 'اعتبار گذرنامه', 'unit' => 'ماه', 'kind' => 'number', 'zone' => 'stat' ),
		'max_total_stay_days'        => array( 'label' => 'حداکثر اقامت', 'unit' => 'روز', 'kind' => 'number', 'zone' => 'stat' ),
		'free_zone_stay_days'        => array( 'label' => 'اقامت در منطقۀ آزاد', 'unit' => 'روز', 'kind' => 'number', 'zone' => 'stat' ),
		'currency_iso_code'          => array( 'label' => 'کد ISO', 'unit' => '', 'kind' => 'latin', 'zone' => 'stat' ),
		'currency_symbol'            => array( 'label' => 'نماد', 'unit' => '', 'kind' => 'text', 'zone' => 'stat' ),
		'international_card_status'  => array( 'label' => 'کارت بین‌المللی', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '195' ),
		'redenomination_status'      => array( 'label' => 'بازتعریف واحد پول', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '192' ),
		/* تاریخ رویداد (E1 · E3 · E5) — مقدار خام Y-m-d یا زمان یونیکس؛ نمایش به زبان صفحه (topersia_g_date_fa) با <time datetime>.  P5-D357 */
		'event_start_date'           => array( 'label' => 'تاریخ آغاز', 'unit' => '', 'kind' => 'date', 'zone' => 'card' ),
		'event_end_date'             => array( 'label' => 'تاریخ پایان', 'unit' => '', 'kind' => 'date', 'zone' => 'card' ),
		'event_frequency'            => array( 'label' => 'تکرار', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '213' ),
		'sport_type'                 => array( 'label' => 'رشته', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '214' ),
		'competition_level'          => array( 'label' => 'سطح', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '215' ),
		'calendar_system'            => array( 'label' => 'تقویم', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '204' ),
		'ceremony_duration_days'     => array( 'label' => 'مدت مراسم', 'unit' => 'روز', 'kind' => 'number', 'zone' => 'stat' ),
		'crowd_scale'                => array( 'label' => 'گسترۀ حضور', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '217' ),
		'season'                     => array( 'label' => 'فصل', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '34' ),
		'natural_phenomenon_type'    => array( 'label' => 'پدیده', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '218' ),
		'weather_dependency'         => array( 'label' => 'وابستگی به هوا', 'unit' => '', 'kind' => 'choice', 'zone' => 'stat', 'glossary' => '207' ),
		);

		/** اجازۀ افزودن فیلد بدون ویرایش این فایل. */
		if ( function_exists( 'apply_filters' ) ) {
			$map = apply_filters( 'topersia_g_map', $map );
		}

		return $map;
	}
}

/**
 * جدول پشتیبان واژه‌نامه‌ها.
 *
 * مسیر نخست همیشه خود JetEngine است. این جدول فقط وقتی به کار می‌آید که
 * JetEngine در دسترس نباشد — مثلاً در آزمون واحد یا هنگام خطای افزونه.
 * بدون آن، نبودِ JetEngine یعنی چاپ «peak» روی صفحۀ زنده.
 *
 * منبع: export ۱۴۰۵/۰۶/۱۸ — سیزده واژه‌نامه.
 *
 * @return array
 */
if ( ! function_exists( 'topersia_g_glossary_fallback' ) ) {
	function topersia_g_glossary_fallback() {

		return array(
		'36' => array(
			'uploaded_file'      => 'فایل آپلودی',
			'direct_link'        => 'لینک مستقیم فایل',
			'external_url'       => 'آدرس خارجی',
			'api_generated'      => 'تولیدشده با API',
			'tts_generated'      => 'تبدیل متن به گفتار',
			'ai_voice'           => 'صدای هوش مصنوعی',
			'podcast_episode'    => 'قسمت پادکست',
		),
		'37' => array(
			'verified'           => 'راستی‌آزمایی‌شده',
			'current'            => 'به‌روز',
			'pending_review'     => 'نیازمند بازبینی پس از اجلاس',
			'updating'           => 'در حال به‌روزرسانی',
			'outdated'           => 'منسوخ',
			'draft'              => 'پیش‌نویس',
			/* ۴٫۳٫۰ بند ۱۳ — گزینۀ تازۀ واژه‌نامۀ ۳۷ (WPML: Copy) */
			'needs_verification' => 'نیازمند راستی‌آزمایی',
		),
		'266' => array(
			'active'             => 'فعال',
			'not_applicable'     => 'بازنشسته / منتفی',
		),
		'34' => array(
			'spring'             => 'بهار',
			'summer'             => 'تابستان',
			'autumn'             => 'پاییز',
			'winter'             => 'زمستان',
			'all_year'           => 'تمام سال',
		),
		'38' => array(
			'natural'            => 'طبیعی',
			'historical'         => 'تاریخی',
			'cultural'           => 'فرهنگی',
			'climate'            => 'اقلیمی',
		),
		'39' => array(
			'historic'           => 'بافت تاریخی',
			'bazaar'             => 'بازار',
			'residential'        => 'مسکونی',
			'modern'             => 'مدرن',
			'mixed'              => 'ترکیبی',
		),
		'40' => array(
			'persian_gulf'       => 'خلیج فارس',
			'caspian'            => 'دریای خزر',
			'oman_sea'           => 'دریای عمان',
		),
		'41' => array(
			'peak'               => 'قله',
			'range'              => 'رشته‌کوه',
			'volcano'            => 'آتشفشان',
		),
		'42' => array(
			'easy'               => 'آسان',
			'moderate'           => 'متوسط',
			'difficult'          => 'دشوار',
			'technical'          => 'فنی — نیازمند تجهیزات',
		),
		'43' => array(
			'sea'                => 'دریا',
			'lake'               => 'دریاچه',
			'river'              => 'رودخانه',
			'wetland'            => 'تالاب',
		),
		'44' => array(
			'fresh'              => 'شیرین',
			'brackish'           => 'لب‌شور',
			'saline'             => 'شور',
			'hypersaline'        => 'فوق‌شور',
		),
		'45' => array(
			'salt'               => 'نمکی',
			'sandy'              => 'شنی',
			'mixed'              => 'ترکیبی',
		),
		'46' => array(
			'forest'             => 'جنگل',
			'plain'              => 'دشت',
		),
		'47' => array(
			'hyrcanian'          => 'هیرکانی',
			'zagros'             => 'زاگرسی',
			'mangrove'           => 'حرا (مانگرو)',
			'desert_scrub'       => 'بیابانی و تنک',
		),
		'48' => array(
			'road'               => 'جاده‌ای',
			'rail'               => 'ریلی',
		),
		'49' => array(
			'active'             => 'فعال',
			'limited'            => 'محدود',
			'closed'             => 'بسته',
		),
		/* ۲ج٫۱ — واژه‌نامه‌های فیلدهای کارت آمار نوع‌های غیر G — export ۱۴۰۵/۰۶/۱۸ */
		'35' => array(   /* tp_suggested_duration */
			'under_30min'          => 'کمتر از ۳۰ دقیقه',
			'30_60min'             => '۳۰ تا ۶۰ دقیقه',
			'1_2h'                 => '۱ تا ۲ ساعت',
			'2_4h'                 => '۲ تا ۴ ساعت',
			'half_day'             => 'نیم‌روز',
			'full_day'             => 'یک روز کامل',
		),
		'51' => array(   /* a2_classification */
			'caravanserai'         => 'کاروانسرا',
			'historic_bridge'      => 'پل تاریخی',
			'historic_tower'       => 'برج تاریخی',
			'palace'               => 'کاخ',
			'historic_bath'        => 'حمام تاریخی',
			'historic_school'      => 'مدرسۀ تاریخی',
			'mausoleum'            => 'آرامگاه',
		),
		'52' => array(   /* a2_restoration_status */
			'active'               => 'در حال بهره‌برداری',
			'restored'             => 'مرمت‌شده',
			'restricted'           => 'دسترسی محدود',
		),
		'53' => array(   /* a3_classification */
			'mosque'               => 'مسجد',
			'church'               => 'کلیسا',
			'temple'               => 'معبد',
			'fire_temple'          => 'آتشکده',
			'synagogue'            => 'کنیسه',
			'imamzadeh'            => 'امامزاده',
			'sufi_lodge'           => 'خانقاه',
		),
		'54' => array(   /* tp_photography_policy */
			'unverified'           => 'راستی‌آزمایی نشده',
			'allowed'              => 'آزاد',
			'ask_first'            => 'با اجازه',
			'restricted'           => 'محدود',
			'prohibited'           => 'ممنوع',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		'55' => array(   /* a7_classification */
			'art_gallery'          => 'گالری هنری',
			'house_museum'         => 'خانه‌موزه',
			'general_museum'       => 'موزۀ عمومی',
		),
		'56' => array(   /* a9_park_type */
			'national_park'        => 'پارک ملی',
			'wildlife_refuge'      => 'پناهگاه حیات وحش',
			'protected_area'       => 'منطقۀ حفاظت‌شده',
			'nature_reserve'       => 'اثر طبیعی / ذخیره‌گاه',
		),
		'57' => array(   /* a10_classification */
			'waterfall'            => 'آبشار',
			'cave'                 => 'غار',
			'spring'               => 'چشمه',
			'canyon_gorge'         => 'تنگه / دره',
			'rock_formation'       => 'صخره',
			'salt_formation'       => 'کلوت',
		),
		'58' => array(   /* a12_excavation_status */
			'active'               => 'در حال کاوش',
			'completed'            => 'کاوش پایان‌یافته',
			'ongoing'              => 'کاوش مقطعی',
			'unexcavated'          => 'کاوش‌نشده',
		),
		'59' => array(   /* a13_landscape_type */
			'natural'              => 'طبیعی',
			'cultural'             => 'فرهنگی',
			'mixed'                => 'ترکیبی',
		),
		'60' => array(   /* c1_classification */
			'carpet_kilim'         => 'فرش و گلیم',
			'tilework'             => 'کاشی‌کاری',
			'miniature'            => 'مینیاتور',
			'inlaid_work'          => 'معرق و خاتم',
			'filigree'             => 'ملیله',
			'pottery'              => 'سفالگری',
			'traditional_textile'  => 'نساجی سنتی',
			'block_print'          => 'چاپ قلمکار',
		),
		'61' => array(   /* c1_ich_status */
			'active'               => 'فعال',
			'under_review'         => 'در حال بازنگری',
			'threatened'           => 'در معرض خطر',
		),
		'62' => array(   /* c2_classification */
			'soup_stew'            => 'آش و سوپ',
			'khoresh'              => 'خورش',
			'kebab'                => 'کباب',
			'traditional_bread'    => 'نان سنتی',
			'sweet_confection'     => 'شیرینی و حلوا',
			'preserve_pickle'      => 'ترشی و مربا',
			'ceremonial_food'      => 'غذای مناسبتی',
			'local_beverage'       => 'نوشیدنی محلی',
		),
		'63' => array(   /* c5_classification */
			'mens_dress'           => 'لباس مردانه',
			'womens_dress'         => 'لباس زنانه',
			'ceremonial_dress'     => 'لباس مناسبتی',
			'wedding_dress'        => 'لباس عروسی',
		),
		'64' => array(   /* c6_classification */
			'classical_dastgah'    => 'موسیقی دستگاهی',
			'regional_ethnic'      => 'موسیقی محلی و قومی',
			'ceremonial_music'     => 'موسیقی مراسمی',
			'traditional_instrument' => 'ساز سنتی',
		),
		'65' => array(   /* c7_dance_type */
			'regional_folk'        => 'محلی و بومی',
			'ceremonial'           => 'مراسمی',
			'ritual'               => 'آیینی',
			'wedding'              => 'عروسی',
		),
		'66' => array(   /* c10_classification */
			'nowruz_rituals'       => 'آیین‌های نوروزی',
			'mourning_ceremony'    => 'مراسم عزاداری',
			'seasonal_celebration' => 'جشن فصلی',
			'traditional_wedding'  => 'عروسی سنتی',
			'ethnic_ritual'        => 'آیین قومی',
		),
		'67' => array(   /* c10_spatial_scope */
			'national'             => 'ملی',
			'regional'             => 'منطقه‌ای',
			'provincial'           => 'استانی',
			'local'                => 'محلی',
		),
		'68' => array(   /* c12_classification */
			'poet'                 => 'شاعر',
			'scientist_scholar'    => 'دانشمند',
			'king_ruler'           => 'پادشاه',
			'military_commander'   => 'سردار',
			'artist'               => 'هنرمند',
			'philosopher_mystic'   => 'فیلسوف و عارف',
		),
		'171' => array(   /* p9_classification */
			'historical'           => 'مسیر تاریخی',
			'natural'              => 'مسیر طبیعی',
			'thematic'             => 'مسیر موضوعی',
		),
		'176' => array(   /* p9_difficulty */
			'easy'                 => 'آسان',
			'moderate'             => 'متوسط',
			'challenging'          => 'دشوار',
			'technical'            => 'تخصصی',
		),
		'192' => array(   /* p2_redenomination_status */
			'not_planned'          => 'برنامه‌ریزی نشده',
			'legislated'           => 'قانون تصویب شده',
			'preparation'          => 'دورۀ آماده‌سازی',
			'transition'           => 'دورۀ گذار',
			'completed'            => 'تکمیل شده',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		'195' => array(   /* p2_card_status */
			'accepted'             => 'پذیرفته می‌شود',
			'partially_accepted'   => 'پذیرش محدود',
			'not_accepted'         => 'پذیرفته نمی‌شود',
			'unverified'           => 'راستی‌آزمایی نشده',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		'204' => array(   /* p6_calendar_system */
			'solar_hijri'          => 'هجری شمسی',
			'lunar_hijri'          => 'هجری قمری',
			'gregorian'            => 'میلادی',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		'207' => array(   /* tp_impact_level */
			'high'                 => 'زیاد',
			'medium'               => 'متوسط',
			'low'                  => 'کم',
			'unverified'           => 'راستی‌آزمایی نشده',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		'213' => array(   /* e_event_frequency */
			'annual'               => 'سالانه',
			'biennial'             => 'دوسالانه',
			'multi_year'           => 'چندسالانه',
			'one_time'             => 'یک‌بار',
			'irregular'            => 'نامنظم',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		'214' => array(   /* e3_sport_type */
			'football'             => 'فوتبال',
			'volleyball'           => 'والیبال',
			'wrestling'            => 'کشتی',
			'martial_arts'         => 'رزمی',
			'athletics'            => 'دو و میدانی',
			'motorsport'           => 'موتورسواری و اتومبیل‌رانی',
			'water_sports'         => 'ورزش آبی',
			'winter_sports'        => 'ورزش زمستانی',
			'cycling'              => 'دوچرخه‌سواری',
			'equestrian'           => 'سوارکاری',
			'traditional'          => 'بومی و سنتی',
			'multi_sport'          => 'چندرشته‌ای',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		'215' => array(   /* e3_competition_level */
			'local'                => 'محلی',
			'national'             => 'ملی',
			'international'        => 'بین‌المللی',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		'217' => array(   /* e5_crowd_scale */
			'local'                => 'محلی',
			'provincial'           => 'استانی',
			'national'             => 'ملی',
			'international'        => 'بین‌المللی',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		'218' => array(   /* e6_phenomenon_type */
			'blossom'              => 'شکوفه',
			'foliage'              => 'برگ‌ریزان',
			'migration'            => 'مهاجرت پرندگان',
			'wildflower'           => 'گل وحشی',
			'snowfall'             => 'بارش برف',
			'salt_bloom'           => 'شکوفۀ نمک',
			'star_visibility'      => 'رصد آسمان',
			'tide'                 => 'جزر و مد',
			'bioluminescence'      => 'درخشش دریا',
			'not_applicable'       => 'موضوعیت ندارد',
		),
		);
	}
}

/**
 * برچسب یک مقدار واژه‌نامه.
 *
 * ترتیب: JetEngine زنده ← جدول پشتیبان ← خود مقدار.
 * بازگشت به «خود مقدار» عمدی است: شکست باید به سمت نمایش باشد نه
 * ناپدید شدن — همان قطبیتی که topersia_active_rows در هسته دارد.
 *
 * @param string $glossary_id شناسۀ واژه‌نامه.
 * @param string $value       مقدار ذخیره‌شده.
 * @return string
 */
if ( ! function_exists( 'topersia_g_glossary_label' ) ) {
	function topersia_g_glossary_label( $glossary_id, $value ) {

		$glossary_id = (string) $glossary_id;
		$value       = (string) $value;

		if ( '' === $value || '' === $glossary_id ) {
			return $value;
		}

		/*
		 * زبان دیگر: ترجمۀ کاتالوگ به کلیدِ مقدار — برچسب جدول پشتیبان کلید
		 * ترجمه است، نه برچسب زندۀ JetEngine، تا ویرایش برچسب در پیشخوان
		 * ترجمه را نشکند. فارسی همان مسیر پیشین.  ۴٫۴٫۲
		 */
		if ( function_exists( 'topersia_g_page_lang' ) && 'fa' !== topersia_g_page_lang() ) {

			$fallback = topersia_g_glossary_fallback();

			if ( isset( $fallback[ $glossary_id ][ $value ] ) ) {
				return topersia_g_t( $fallback[ $glossary_id ][ $value ] );
			}
		}

		static $cache = array();

		if ( ! isset( $cache[ $glossary_id ] ) ) {

			$table = array();

			if ( function_exists( 'jet_engine' ) ) {

				$engine = jet_engine();

				if ( is_object( $engine ) && isset( $engine->glossaries ) && is_object( $engine->glossaries )
					&& isset( $engine->glossaries->data ) && is_object( $engine->glossaries->data )
					&& method_exists( $engine->glossaries->data, 'get_items' ) ) {

					$items = $engine->glossaries->data->get_items();

					if ( is_array( $items ) ) {

						foreach ( $items as $item ) {

							$item = is_object( $item ) ? get_object_vars( $item ) : $item;

							if ( ! is_array( $item ) || ! isset( $item['id'] ) ) {
								continue;
							}

							if ( (string) $item['id'] !== $glossary_id ) {
								continue;
							}

							$fields = isset( $item['fields'] ) ? $item['fields'] : array();

							if ( is_string( $fields ) ) {
								$fields = json_decode( $fields, true );
							}

							if ( ! is_array( $fields ) ) {
								continue;
							}

							foreach ( $fields as $row ) {

								if ( is_array( $row ) && isset( $row['value'] ) && isset( $row['label'] ) ) {
									$table[ (string) $row['value'] ] = (string) $row['label'];
								}
							}
						}
					}
				}
			}

			if ( empty( $table ) ) {

				$fallback = topersia_g_glossary_fallback();
				$table    = isset( $fallback[ $glossary_id ] ) ? $fallback[ $glossary_id ] : array();
			}

			$cache[ $glossary_id ] = $table;
		}

		$table = $cache[ $glossary_id ];

		return isset( $table[ $value ] ) ? $table[ $value ] : $value;
	}
}

/**
 * نقشۀ گروه‌های رابطه به‌ازای هر نوع پست.
 *
 * چرا اینجا و نه در قالب المنتور: اگر نام رابطه در قالب نوشته شود، هر
 * تغییر رابطه یعنی ویرایش دستی قالب و شکستن مرز معماری. همان الگویی که
 * topersia_schema_map() در افزونۀ شِما دارد.
 *
 * شناسه‌ها از Jetengine_Skin.json — export ۱۴۰۵/۰۶/۱۸ — گرفته شده‌اند و
 * جهت parent/child از همان فایل است، نه از TP-G-RELATIONS.md که ستون
 * جهتش برای هر ۳۴ سطر وارونه است.  P5-D170
 *
 * dir: children یعنی این پست والد است و فرزندانش گرفته می‌شوند.
 *
 * @return array
 */
if ( ! function_exists( 'topersia_g_relmap' ) ) {
	function topersia_g_relmap() {

		$map = array(

			'tp_country' => array(

				'capital' => array(
					'title'  => 'پایتخت',
					'rels'   => array(
						array( 'id' => 95, 'dir' => 'children' ),
					),
				),

				'provinces' => array(
					'title'  => 'استان‌ها',
					'kicker' => 'PROVINCES',
					'rels'   => array(
						array( 'id' => 71, 'dir' => 'children' ),
					),
				),

				'practical' => array(
					'title'  => 'اطلاعات سفر',
					'kicker' => 'PRACTICAL',
					'rels'   => array(
						array( 'id' => 156, 'dir' => 'children' ),
						array( 'id' => 159, 'dir' => 'children' ),
						array( 'id' => 185, 'dir' => 'children' ),
						array( 'id' => 226, 'dir' => 'children' ),
						array( 'id' => 228, 'dir' => 'children' ),
					),
				),
			),

			'tp_region' => array(

				'provinces' => array(
					'title'  => 'استان‌ها',
					'kicker' => 'PROVINCES',
					'rels'   => array(
						array( 'id' => 83, 'dir' => 'children' ),
					),
				),
			),

			'tp_province' => array(

				'capital' => array(
					'title'  => 'مرکز استان',
					'rels'   => array(
						array( 'id' => 96, 'dir' => 'children' ),
					),
				),

				/*
				 * «شهرها» — بخش `#places` مرجع.  P5-D220
				 *
				 * تا ۴٫۲٫۰ شهرستان‌ها بودند (P5-D215). حالا شهرهای استان
				 * از زنجیرۀ ۷۲ استان→شهرستان و ۷۳ شهرستان→شهر: کارت اول
				 * مرکز استان است (رابطۀ ۹۶، برچسب «مرکز استان») و بقیه
				 * «شهر» با پیوند به صفحۀ خودِ شهر. ترتیب P5-D211.
				 *
				 * اسلایدر هشت‌تای اول را نشان می‌دهد، مثل هر اسلایدر دیگر
				 * (P5-D276 — سقف دوازدۀ پیشین برداشته شد)؛ فهرست کامل در
				 * مودالِ فهرستی است که «همه شهرها ←» بازش می‌کند (P5-D221).
				 */
				'places' => array(
					'title'  => 'شهرها',
					'lead'   => 'مرکز استان و شهرهای %s',
					'more'   => 'همه شهرها ←',
					'hub'    => '',
					'kicker' => 'PLACES',
					'chains' => array(
						array(
							array( 'id' => 72, 'dir' => 'children' ),
							array( 'id' => 73, 'dir' => 'children' ),
						),
					),
				),

				'villages' => array(
					'title'  => 'روستاها',
					'kicker' => 'VILLAGES',
					'rels'   => array(
						array( 'id' => 75, 'dir' => 'children' ),
					),
				),

				/*
				 * «جاذبه‌های منتخب» — بخش `#attractions` مرجع.  P5-D219
				 *
				 * هیچ نوع پستی به نام «جاذبه» وجود ندارد؛ جاذبه در این
				 * پروژه چهار نوع است: بنا، مکان مذهبی، موزه، محوطۀ
				 * باستانی. هیچ‌کدام هم مستقیم به استان وصل نیستند — به
				 * شهر وصل‌اند، و شهر دو پله پایین‌تر از استان است.
				 * پس زنجیره: ۷۲ استان→شهرستان، ۷۳ شهرستان→شهر، بعد
				 * ۸۴ · ۸۶ · ۸۸ · ۹۳.
				 *
				 * محوطۀ باستانیِ خودِ استان (۹۲) این‌جا نمی‌آید؛ کارت
				 * مستقل خودش را در ستون کناری دارد و دوباره آوردنش یعنی
				 * یک موجودیت در دو جا.
				 */
				'attractions' => array(
					'title'  => 'جاذبه‌های منتخب',
					'lead'   => 'جاذبه‌های شاخص %s و شهرهایش',
					'more'   => 'همه جاذبه‌ها ←',
					'hub'    => '',
					'kicker' => 'ATTRACTIONS',
					'chains' => array(
						array(
							array( 'id' => 72, 'dir' => 'children' ),
							array( 'id' => 73, 'dir' => 'children' ),
							array( 'id' => 84, 'dir' => 'children' ),
						),
						array(
							array( 'id' => 72, 'dir' => 'children' ),
							array( 'id' => 73, 'dir' => 'children' ),
							array( 'id' => 86, 'dir' => 'children' ),
						),
						array(
							array( 'id' => 72, 'dir' => 'children' ),
							array( 'id' => 73, 'dir' => 'children' ),
							array( 'id' => 88, 'dir' => 'children' ),
						),
						array(
							array( 'id' => 72, 'dir' => 'children' ),
							array( 'id' => 73, 'dir' => 'children' ),
							array( 'id' => 93, 'dir' => 'children' ),
						),
					),
				),

				'nature' => array(
					'title'  => 'پهنه‌های طبیعی',
					'kicker' => 'NATURE',
					'rels'   => array(
						array( 'id' => 77, 'dir' => 'children' ),
						array( 'id' => 78, 'dir' => 'children' ),
						array( 'id' => 79, 'dir' => 'children' ),
						array( 'id' => 80, 'dir' => 'children' ),
						array( 'id' => 81, 'dir' => 'children' ),
						array( 'id' => 89, 'dir' => 'children' ),
						array( 'id' => 90, 'dir' => 'children' ),
						array( 'id' => 94, 'dir' => 'children' ),
					),
				),

				'ancient' => array(
					'title'  => 'محوطه‌های باستانی',
					'kicker' => 'HERITAGE',
					'rels'   => array(
						array( 'id' => 92, 'dir' => 'children' ),
					),
				),

				'culture' => array(
					'title'  => 'فرهنگ و رویداد',
					'kicker' => 'CULTURE',
					'rels'   => array(
						array( 'id' => 107, 'dir' => 'children', 'fam' => 'artisanat' ),
						array( 'id' => 110, 'dir' => 'children', 'fam' => 'cuisine' ),
						array( 'id' => 113, 'dir' => 'children', 'fam' => 'heritage' ),
						array( 'id' => 116, 'dir' => 'children', 'fam' => 'music' ),
						array( 'id' => 119, 'dir' => 'children', 'fam' => 'music' ),
						array( 'id' => 122, 'dir' => 'children', 'fam' => 'music' ),
						array( 'id' => 131, 'dir' => 'children', 'fam' => 'heritage' ),
						array( 'id' => 232, 'dir' => 'children', 'fam' => 'festivals' ),
						array( 'id' => 245, 'dir' => 'children', 'fam' => 'festivals' ),
						array( 'id' => 253, 'dir' => 'children', 'fam' => 'seasonal' ),
						array( 'id' => 263, 'dir' => 'children', 'fam' => 'seasonal' ),
					),
				),

				'practical' => array(
					'title'  => 'اطلاعات سفر',
					'kicker' => 'PRACTICAL',
					'rels'   => array(
						array( 'id' => 157, 'dir' => 'children' ),
						array( 'id' => 160, 'dir' => 'children' ),
						array( 'id' => 227, 'dir' => 'children' ),
						array( 'id' => 229, 'dir' => 'children' ),
					),
				),

				'neighbors' => array(
					'title'  => 'استان‌های هم‌مرز',
					'kicker' => 'NEIGHBORS',
					'rels'   => array(
						array( 'id' => 102, 'dir' => 'children' ),
						array( 'id' => 102, 'dir' => 'parents' ),
					),
				),

				'routes' => array(
					'title'  => 'مسیرهای گردشگری',
					'kicker' => 'ROUTES',
					'rels'   => array(
						array( 'id' => 172, 'dir' => 'parents' ),
					),
				),

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 71, 'dir' => 'parents' ),
					),
				),
			),

			'tp_city' => array(

				'capital-of' => array(
					'title'  => 'مرکزیت',
					'kicker' => 'CAPITAL',
					'rels'   => array(
						array( 'id' => 95, 'dir' => 'parents' ),
						array( 'id' => 96, 'dir' => 'parents' ),
						array( 'id' => 97, 'dir' => 'parents' ),
					),
				),

				'districts' => array(
					'title'  => 'محله‌ها',
					'kicker' => 'DISTRICTS',
					'rels'   => array(
						array( 'id' => 74, 'dir' => 'children' ),
					),
				),

				'villages' => array(
					'title'  => 'روستاها',
					'kicker' => 'VILLAGES',
					'rels'   => array(
						array( 'id' => 98, 'dir' => 'children' ),
					),
				),

				/* روی شهر، جاذبه‌ها مستقیم‌اند — زنجیره لازم نیست. */
				'attractions' => array(
					'title'  => 'جاذبه‌های منتخب',
					'lead'   => 'جاذبه‌های شاخص %s',
					'more'   => 'همه جاذبه‌ها ←',
					'hub'    => '',
					'kicker' => 'ATTRACTIONS',
					'rels'   => array(
						array( 'id' => 84, 'dir' => 'children' ),
						array( 'id' => 86, 'dir' => 'children' ),
						array( 'id' => 88, 'dir' => 'children' ),
						array( 'id' => 93, 'dir' => 'children' ),
					),
				),

				'nature' => array(
					'title'  => 'پهنه‌های طبیعی',
					'kicker' => 'NATURE',
					'rels'   => array(
						array( 'id' => 91, 'dir' => 'children' ),
					),
				),

				'ancient' => array(
					'title'  => 'محوطه‌های باستانی',
					'kicker' => 'HERITAGE',
					'rels'   => array(
						array( 'id' => 84, 'dir' => 'children' ),
						array( 'id' => 86, 'dir' => 'children' ),
						array( 'id' => 88, 'dir' => 'children' ),
						array( 'id' => 93, 'dir' => 'children' ),
						array( 'id' => 143, 'dir' => 'parents' ),
					),
				),

				'culture' => array(
					'title'  => 'فرهنگ و رویداد',
					'kicker' => 'CULTURE',
					'rels'   => array(
						array( 'id' => 108, 'dir' => 'children', 'fam' => 'artisanat' ),
						array( 'id' => 111, 'dir' => 'children', 'fam' => 'cuisine' ),
						array( 'id' => 114, 'dir' => 'children', 'fam' => 'heritage' ),
						array( 'id' => 117, 'dir' => 'children', 'fam' => 'music' ),
						array( 'id' => 120, 'dir' => 'children', 'fam' => 'music' ),
						array( 'id' => 123, 'dir' => 'children', 'fam' => 'music' ),
						array( 'id' => 126, 'dir' => 'children', 'fam' => 'artisanat' ),
						array( 'id' => 129, 'dir' => 'children', 'fam' => 'cuisine' ),
						array( 'id' => 132, 'dir' => 'children', 'fam' => 'heritage' ),
						array( 'id' => 151, 'dir' => 'parents', 'fam' => 'heritage' ),
						array( 'id' => 153, 'dir' => 'children', 'fam' => 'heritage' ),
						array( 'id' => 154, 'dir' => 'children', 'fam' => 'heritage' ),
						array( 'id' => 233, 'dir' => 'children', 'fam' => 'festivals' ),
						array( 'id' => 246, 'dir' => 'children', 'fam' => 'festivals' ),
						array( 'id' => 254, 'dir' => 'children', 'fam' => 'seasonal' ),
						array( 'id' => 264, 'dir' => 'children', 'fam' => 'seasonal' ),
					),
				),

				'practical' => array(
					'title'  => 'اطلاعات سفر',
					'kicker' => 'PRACTICAL',
					'rels'   => array(
						array( 'id' => 158, 'dir' => 'children' ),
						array( 'id' => 230, 'dir' => 'children' ),
					),
				),

				'border' => array(
					'title'  => 'گذرهای مرزی',
					'kicker' => 'BORDER',
					'rels'   => array(
						array( 'id' => 100, 'dir' => 'children' ),
					),
				),

				'routes' => array(
					'title'  => 'مسیرهای گردشگری',
					'kicker' => 'ROUTES',
					'rels'   => array(
						array( 'id' => 173, 'dir' => 'parents' ),
					),
				),

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 73, 'dir' => 'parents' ),
					),
				),
			),

			'tp_district' => array(

				'ancient' => array(
					'title'  => 'محوطه‌های باستانی',
					'kicker' => 'HERITAGE',
					'rels'   => array(
						array( 'id' => 144, 'dir' => 'parents' ),
					),
				),

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 74, 'dir' => 'parents' ),
					),
				),
			),

			'tp_village' => array(

				'ancient' => array(
					'title'  => 'محوطه‌های باستانی',
					'kicker' => 'HERITAGE',
					'rels'   => array(
						array( 'id' => 85, 'dir' => 'children' ),
						array( 'id' => 87, 'dir' => 'children' ),
					),
				),

				'culture' => array(
					'title'  => 'فرهنگ و رویداد',
					'kicker' => 'CULTURE',
					'rels'   => array(
						array( 'id' => 109, 'dir' => 'children', 'fam' => 'artisanat' ),
						array( 'id' => 112, 'dir' => 'children', 'fam' => 'cuisine' ),
						array( 'id' => 118, 'dir' => 'children', 'fam' => 'music' ),
						array( 'id' => 121, 'dir' => 'children', 'fam' => 'music' ),
						array( 'id' => 124, 'dir' => 'children', 'fam' => 'music' ),
						array( 'id' => 127, 'dir' => 'children', 'fam' => 'artisanat' ),
						array( 'id' => 130, 'dir' => 'children', 'fam' => 'cuisine' ),
						array( 'id' => 234, 'dir' => 'children', 'fam' => 'festivals' ),
						array( 'id' => 239, 'dir' => 'children', 'fam' => 'festivals' ),
						array( 'id' => 247, 'dir' => 'children', 'fam' => 'festivals' ),
						array( 'id' => 252, 'dir' => 'children', 'fam' => 'festivals' ),
						array( 'id' => 255, 'dir' => 'children', 'fam' => 'seasonal' ),
						array( 'id' => 258, 'dir' => 'children', 'fam' => 'seasonal' ),
						array( 'id' => 265, 'dir' => 'children', 'fam' => 'seasonal' ),
					),
				),

				'routes' => array(
					'title'  => 'مسیرهای گردشگری',
					'kicker' => 'ROUTES',
					'rels'   => array(
						array( 'id' => 174, 'dir' => 'parents' ),
					),
				),

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 75, 'dir' => 'parents' ),
						array( 'id' => 76, 'dir' => 'parents' ),
						array( 'id' => 98, 'dir' => 'parents' ),
					),
				),
			),

			'tp_island' => array(

				'routes' => array(
					'title'  => 'مسیرهای گردشگری',
					'kicker' => 'ROUTES',
					'rels'   => array(
						array( 'id' => 175, 'dir' => 'parents' ),
					),
				),

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 77, 'dir' => 'parents' ),
					),
				),
			),

			'tp_mountain' => array(

				'subranges' => array(
					'title'  => 'زیرشاخه‌ها',
					'kicker' => 'RANGES',
					'rels'   => array(
						array( 'id' => 101, 'dir' => 'children' ),
					),
				),

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 78, 'dir' => 'parents' ),
						array( 'id' => 101, 'dir' => 'parents' ),
					),
				),
			),

			'tp_water_body' => array(

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 79, 'dir' => 'parents' ),
					),
				),
			),

			'tp_desert' => array(

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 80, 'dir' => 'parents' ),
					),
				),
			),

			'tp_forest_plain' => array(

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 81, 'dir' => 'parents' ),
					),
				),
			),

			'tp_border_crossing' => array(

				'routes' => array(
					'title'  => 'مسیرهای گردشگری',
					'kicker' => 'ROUTES',
					'rels'   => array(
						array( 'id' => 177, 'dir' => 'parents' ),
					),
				),

				'crumbs' => array(
					'rels'   => array(
						array( 'id' => 82, 'dir' => 'parents' ),
						array( 'id' => 100, 'dir' => 'parents' ),
					),
				),
			),

		);

		if ( function_exists( 'apply_filters' ) ) {
			$map = apply_filters( 'topersia_g_relmap', $map );
		}

		return $map;
	}
}
