<?php
/**
 * Plugin Name: ToPersia Schema Engine
 * Description: موتور دادهٔ ساختاریافتهٔ ToPersia — یک گراف JSON-LD واحد در هر صفحه، با @id مبتنی بر entity_uid برای بازشناسی میان‌زبانی و میان‌صفحه‌ای. معماری داده‌محور: افزودن موجودیت تازه یعنی افزودن یک بلوک به نقشه، نه نوشتن تابع. وابسته به افزونهٔ ToPersia Core Functions.
 * Version: 1.1.0
 * Requires PHP: 7.4
 * Author: ToPersia
 * Text Domain: topersia-schema
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  چرا این افزونه از هسته جدا است
 * ══════════════════════════════════════════════════════════════════════════
 * چهار دلیل، به ترتیب اهمیت در افق پنج‌ساله:
 *
 * ۱ — نرخ تغییر. نقشهٔ شِما باید به ۴۲ نوع پست برسد و هر بار که Schema.org
 *     چیزی را منسوخ می‌کند یا اعتبارسنج ایرادی می‌گیرد، دوباره ویرایش
 *     می‌شود. بقیهٔ هسته — اسلاگ، شناسهٔ موجودیت، ماسک /geo — پس از تثبیت
 *     تقریباً بی‌تغییر می‌ماند. نگه‌داشتن پرتغییرترین و کم‌تغییرترین کد در
 *     یک فایل یعنی هر ویرایش شِما، کد حیاتی URL را در معرض خطر می‌گذارد.
 *
 * ۲ — دامنهٔ شکست. اگر یک بلوک شِما خطای مرگبار بدهد، در حالت یکپارچه کل
 *     سایت می‌خوابد — شامل ریدایرکت G4 و ماسک /geo. با جداسازی، بدترین
 *     حالت این است که صفحه بدون JSON-LD رندر شود.
 *
 * ۳ — عیب‌یابی. غیرفعال‌کردن موقت شِما برای آزمایش، دیگر به معنی
 *     غیرفعال‌کردن تولید اسلاگ و شناسهٔ موجودیت نیست.
 *
 * ۴ — مرز مسئولیت. هسته داده را می‌سازد (entity_uid، روابط، ترتیب توقف).
 *     این افزونه فقط همان داده را می‌خواند و منتشر می‌کند. یک‌طرفه بودن این
 *     وابستگی، خودش یک ضمانت معماری است.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  وابستگی به هسته — یک‌طرفه و صریح
 * ══════════════════════════════════════════════════════════════════════════
 * این افزونه بدون ToPersia Core Functions کار نمی‌کند و اگر هسته نباشد،
 * خودش را خاموش نگه می‌دارد و در پیشخوان هشدار می‌دهد. هسته هیچ وابستگی
 * معکوسی به این افزونه ندارد.
 *
 * آنچه از هسته مصرف می‌شود:
 *   topersia_relation()      شیء رابطهٔ JetEngine با شناسه
 *   topersia_p9_stops()      توقف‌های مرتب مسیر
 *   topersia_active_rows()   فیلتر ردیف‌های بازنشستهٔ Repeater — P5-D121
 *   topersia_is_entity()     تشخیص موجودیت ToPersia
 *   topersia_admin_tabs      فیلتر افزودن زبانه به پنل مدیریت
 *   TOPERSIA_SITE_ID         دامنهٔ ریشه برای @id های سراسری
 *   TOPERSIA_REL_COUNTY_CITY · TOPERSIA_REL_PROVINCE_COUNTY
 *   متای entity_uid          که بخش ۳ هسته می‌سازد
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  فهرست بخش‌ها
 * ══════════════════════════════════════════════════════════════════════════
 *  ۰ — گارد وابستگی               بررسی حضور و نسخهٔ هسته
 *  ۱ — خاموش‌کردن شِمای Rank Math  تا دو گراف روی یک صفحه ننشیند
 *  ۲ — نقشهٔ موجودیت‌ها             تنها جایی که برای موجودیت تازه ویرایش می‌شود
 *  ۳ — ابزارهای کمکی موتور
 *  ۴ — گره‌های سراسری              Organization · WebSite · Breadcrumb · FAQ
 *  ۵ — سازنده‌های اختصاصی          مسیر گردشگری · موجودیت مکانی
 *  ۶ — ساخت و انتشار گراف
 *  ۷ — زبانهٔ پنل مدیریت           نقشه، وضعیت، و پیش‌نمایش زندهٔ گراف
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  چهار اصل طراحی
 * ══════════════════════════════════════════════════════════════════════════
 * ۱ — یک گراف واحد در هر صفحه. همهٔ گره‌ها در یک <script> با @graph منتشر
 *     می‌شوند تا ارجاع‌های @id درون همان گراف حل شوند.
 *
 * ۲ — @id هر موجودیت از entity_uid می‌آید، نه از URL. چون entity_uid کلاس
 *     Copy دارد، نسخهٔ فارسی و انگلیسی و عربیِ یک شهر دقیقاً یک @id
 *     می‌گیرند. این تفاوت بنیادی یک دانشنامه با یک وبلاگ است.
 *
 * ۳ — داده‌محور، نه تابع‌محور. افزودن موجودیت تازه یعنی افزودن یک بلوک به
 *     topersia_schema_map() — نه نوشتن تابع جدید.
 *
 * ۴ — شکست بی‌صدا ممنوع. هر فیلد خالی حذف می‌شود، نه اینکه با مقدار تهی
 *     منتشر شود. گره‌ای که داده ندارد اصلاً ساخته نمی‌شود.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  Changelog
 * ══════════════════════════════════════════════════════════════════════════
 * 1.1.0 — افزوده شد بخش ۷: زبانهٔ «شِما» در پنل مدیریت ToPersia.
 *          • نقشهٔ موجودیت‌ها به‌صورت جدول خوانا — بدون بازکردن فایل.
 *          • فهرست نوع‌های پستی که هنوز در نقشه نیستند و هیچ JSON-LD
 *            منتشر نمی‌کنند.
 *          • پیش‌نمایش زندهٔ گراف برای هر شناسهٔ پست. این ابزار مشکل
 *            راستی‌آزمایی را در حالت تعمیر حل می‌کند: تا وقتی حالت تعمیر
 *            روشن است، خروجی فرانت برای بررسی در دسترس نیست.
 *        — افزوده شد ثابت TOPERSIA_SCHEMA_VERSION تا زبانهٔ تشخیص هسته
 *          بتواند فعال‌بودن و نسخهٔ این افزونه را گزارش کند.
 * 1.0.0 — جداسازی از topersia-core نسخهٔ ۳.۳.۰ بدون تغییر رفتار.
 *        — افزوده شد گارد وابستگی با هشدار پیشخوان.
 *        — چهار فیلتر خاموش‌کنندهٔ شِمای Rank Math از هسته به اینجا منتقل
 *          شدند. دلیل: آن فیلترها فقط به این خاطر وجود دارند که موتور ما
 *          جایشان را پر می‌کند. ماندنشان در هسته یعنی اگر این افزونه
 *          غیرفعال شود، سایت هیچ شِمایی نداشته باشد — نه مال ما، نه مال
 *          Rank Math.
 *        — پیشینه: منطق این فایل از نسخهٔ ۲.۰.۰ هسته آمده و اصلاحات
 *          ۲.۰.۱ (پنج ایراد اعتبارسنج روی TouristTrip)، ۳.۰.۰ (رفع XSS با
 *          JSON_HEX_TAG و JSON_HEX_AMP)، ۳.۲.۰ (موجودیت G5 و سازندهٔ
 *          مکانی) و ۳.۳.۰ (فیلتر row_status روی faq_items) را در بر دارد.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // دسترسی مستقیم مجاز نیست.
}

/* ═══════════════════════════════════════════════════════════════════════
 *  بخش ۰ — گارد وابستگی
 * ═══════════════════════════════════════════════════════════════════════
 * بدون هسته، هیچ‌کدام از توابع و ثابت‌های لازم وجود ندارند. در PHP 8 ارجاع
 * به ثابت تعریف‌نشده خطای مرگبار می‌دهد، نه هشدار. پس پیش از اجرای هر
 * منطقی حضور هسته بررسی می‌شود.
 *
 * بررسی داخل هوک انجام می‌شود، نه در لحظهٔ بارگذاری فایل: وردپرس افزونه‌ها
 * را به ترتیب حروف الفبا بارگذاری می‌کند و اگرچه topersia-core پیش از
 * topersia-schema می‌آید، اتکا به ترتیب الفبایی شکننده است.
 */

/** نسخهٔ این افزونه — زبانهٔ تشخیص هسته این را می‌خواند. */
if ( ! defined( 'TOPERSIA_SCHEMA_VERSION' ) ) {
	define( 'TOPERSIA_SCHEMA_VERSION', '1.1.0' );
}

/** حداقل نسخهٔ هستهٔ سازگار. */
if ( ! defined( 'TOPERSIA_SCHEMA_MIN_CORE' ) ) {
	define( 'TOPERSIA_SCHEMA_MIN_CORE', '4.0.0' );
}

/**
 * دامنهٔ ریشه برای @id های سراسری.
 *
 * هسته هم همین را تعریف می‌کند. هر کدام زودتر بارگذاری شد مقدار را می‌گذارد
 * و گارد if(!defined) از تعارض جلوگیری می‌کند.
 */
if ( ! defined( 'TOPERSIA_SITE_ID' ) ) {
	define( 'TOPERSIA_SITE_ID', 'https://topersia.com' );
}

/**
 * آیا هسته حاضر و سازگار است؟
 *
 * نتیجه در همان درخواست کش می‌شود چون در چند هوک بررسی می‌شود.
 */
function topersia_schema_core_ready() {

	static $ready = null;

	if ( null !== $ready ) {
		return $ready;
	}

	$ready = defined( 'TOPERSIA_CORE_VERSION' )
		&& version_compare( TOPERSIA_CORE_VERSION, TOPERSIA_SCHEMA_MIN_CORE, '>=' )
		&& function_exists( 'topersia_relation' )
		&& function_exists( 'topersia_active_rows' )
		&& function_exists( 'topersia_is_entity' );

	return $ready;
}

/** هشدار پیشخوان وقتی هسته غایب یا قدیمی است. */
add_action(
	'admin_notices',
	function () {

		if ( topersia_schema_core_ready() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$message = defined( 'TOPERSIA_CORE_VERSION' )
			? sprintf(
				/* translators: ۱ نسخهٔ فعلی هسته · ۲ نسخهٔ لازم */
				__( 'افزونهٔ ToPersia Schema Engine غیرفعال است: نسخهٔ هسته %1$s است ولی دست‌کم %2$s لازم دارد.', 'topersia-schema' ),
				TOPERSIA_CORE_VERSION,
				TOPERSIA_SCHEMA_MIN_CORE
			)
			: __( 'افزونهٔ ToPersia Schema Engine غیرفعال است: افزونهٔ ToPersia Core Functions فعال نیست. تا فعال‌شدن آن، هیچ دادهٔ ساختاریافته‌ای منتشر نمی‌شود.', 'topersia-schema' );

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong></p></div>',
			esc_html( $message )
		);
	}
);

/* ═══════════════════════════════════════════════════════════════════════
 *  بخش ۱ — خاموش‌کردن شِمای بومی Rank Math
 * ═══════════════════════════════════════════════════════════════════════
 * کل JSON-LD پروژه از همین افزونه منتشر می‌شود تا @id ها بر پایهٔ
 * entity_uid یکدست بمانند. اسکیمای Rank Math باید کامل کنار برود، وگرنه دو
 * گراف با @id متفاوت روی یک صفحه می‌نشیند.
 *
 * چرا اینجا و نه در هسته: این چهار فیلتر فقط به این دلیل وجود دارند که
 * موتور ما جایشان را پر می‌کند. اگر در هسته می‌ماندند و این افزونه غیرفعال
 * می‌شد، صفحه هیچ شِمایی نداشت — نه مال ما، نه مال Rank Math. حالا
 * غیرفعال‌کردن این افزونه یعنی بازگشت خودکار به شِمای بومی Rank Math.
 */
add_action(
	'plugins_loaded',
	function () {

		if ( ! topersia_schema_core_ready() ) {
			return;
		}

		add_filter( 'rank_math/json_ld', '__return_empty_array', 99 );

		/** breadcrumb بومی هم خاموش — زنجیرهٔ ما از روابط JetEngine می‌آید. */
		add_filter( 'rank_math/json_ld/breadcrumbs_enabled', '__return_false' );

		/** SearchAction لازم نیست. */
		add_filter( 'rank_math/json_ld/disable_search', '__return_true' );

		/**
		 * کمربند ایمنی: موجودیت‌های سراسری (Organization / Person / WebSite)
		 * از مسیر جداگانه‌ای منتشر می‌شوند و فیلتر json_ld همیشه جلویشان را
		 * نمی‌گیرد.
		 */
		add_filter( 'rank_math/schema/add_global_entities', '__return_false' );
	},
	20
);

/* ═══════════════════════════════════════════════════════════════════════
 *  بخش ۲ — نقشهٔ موجودیت‌ها
 * ═══════════════════════════════════════════════════════════════════════
 * ⬅ برای افزودن موجودیت تازه فقط یک بلوک به این آرایه اضافه کن.
 *
 * کلیدهای هر بلوک:
 *   type            نوع Schema.org
 *   additionalType  (اختیاری) نوع تکمیلی
 *   props           نگاشت ویژگی‌های Schema به منبع داده
 *   builder         (اختیاری) تابعی برای بخش‌های پیچیدهٔ مخصوص همان نوع
 *   parents         (اختیاری) زنجیرهٔ نیاکان برای BreadcrumbList
 *
 * انواع منبع داده در props:
 *   array( 'meta'      => 'key' )               متای ساده
 *   array( 'meta_lang' => 'name' )              name_fa / name_en / name_ar
 *   array( 'image'     => 'hero_image' )        شناسهٔ رسانه → ImageObject
 *   array( 'number'    => 'total_distance_km' ) عدد
 *   array( 'const'     => 'مقدار' )             مقدار ثابت
 *
 * پیش از افزودن هر ویژگی، شاخهٔ ارث‌بری آن نوع در Schema.org را راستی‌آزمایی
 * کن. درس نسخهٔ ۲.۰.۱: پنج ویژگی روی TouristTrip گذاشته شده بود که هیچ‌کدام
 * روی شاخهٔ Trip ← Intangible ← Thing معتبر نبودند.
 */
function topersia_schema_map() {

	$map = array(

		/* ─── P9 — مسیر گردشگری ─── */
		'tp_route' => array(
			'type'    => 'TouristTrip',
			'props'   => array(
				'name'        => array( 'meta_lang' => 'name' ),
				'description' => array( 'meta' => 'short_summary' ),
				'image'       => array( 'image' => 'hero_image' ),
			),
			'builder' => 'topersia_schema_route',
		),

		/* ─── G5 — شهر ───
		 *
		 * @type برابر City است، نه TouristDestination. دلیل: City دقت
		 * جغرافیایی می‌دهد و زیرشاخهٔ AdministrativeArea ← Place است.
		 * TouristDestination در additionalType می‌آید — روشی که خودِ
		 * مستندات Schema.org توصیه می‌کند. این‌طور هم دقت حفظ می‌شود و هم
		 * پیوند با itinerary مسیرها.
		 */
		'tp_city'  => array(
			'type'           => 'City',
			'additionalType' => 'https://schema.org/TouristDestination',
			'props'          => array(
				'name'        => array( 'meta_lang' => 'name' ),
				'description' => array( 'meta' => 'short_summary' ),
				'image'       => array( 'image' => 'hero_image' ),
			),
			'builder'        => 'topersia_schema_place',
			'parents'        => array(
				array(
					'relation' => TOPERSIA_REL_COUNTY_CITY,
					'skip'     => true, // شهرستان صفحه ندارد.
				),
				array( 'relation' => TOPERSIA_REL_PROVINCE_COUNTY ),
			),
		),
	);

	return apply_filters( 'topersia_schema_map', $map );
}

/* ═══════════════════════════════════════════════════════════════════════
 *  بخش ۳ — ابزارهای کمکی موتور
 * ═══════════════════════════════════════════════════════════════════════
 */

/** کد زبان جاری برای inLanguage. */
function topersia_schema_lang() {

	if ( has_filter( 'wpml_current_language' ) ) {

		$code = apply_filters( 'wpml_current_language', null );

		if ( $code ) {
			return $code;
		}
	}

	return substr( get_locale(), 0, 2 );
}

/**
 * شناسهٔ یکتای یک موجودیت.
 *
 * چون entity_uid کلاس Copy دارد، در هر سه زبان یکسان است — و همین باعث
 * می‌شود ارجاع میان‌زبانی کار کند. تولید این مقدار وظیفهٔ بخش ۳ هسته است؛
 * اینجا فقط خوانده می‌شود.
 */
function topersia_schema_uid( $post_id ) {

	$uid = get_post_meta( $post_id, 'entity_uid', true );

	if ( ! empty( $uid ) ) {
		return $uid;
	}

	// پشتیبان: اگر uid هنوز ساخته نشده، از permalink استفاده کن.
	return get_permalink( $post_id ) . '#entity';
}

/** نام موجودیت به زبان جاری، با پشتیبان عنوان پست. */
function topersia_schema_name( $post_id ) {

	$lang = topersia_schema_lang();
	$name = get_post_meta( $post_id, 'name_' . $lang, true );

	if ( empty( $name ) ) {
		$name = get_the_title( $post_id );
	}

	return wp_strip_all_tags( (string) $name );
}

/** گرهٔ ImageObject از شناسهٔ رسانه. */
function topersia_schema_image( $attachment_id ) {

	$attachment_id = absint( $attachment_id );

	if ( ! $attachment_id ) {
		return null;
	}

	$src = wp_get_attachment_image_src( $attachment_id, 'full' );

	if ( ! $src ) {
		return null;
	}

	$node = array(
		'@type'  => 'ImageObject',
		'url'    => $src[0],
		'width'  => absint( $src[1] ),
		'height' => absint( $src[2] ),
	);

	$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

	if ( $alt ) {
		$node['caption'] = wp_strip_all_tags( $alt );
	}

	return $node;
}

/** حذف بازگشتیِ کلیدهای خالی — گره‌های تهی منتشر نمی‌شوند. */
function topersia_schema_prune( $data ) {

	if ( ! is_array( $data ) ) {
		return $data;
	}

	foreach ( $data as $key => $value ) {

		if ( is_array( $value ) ) {
			$data[ $key ] = topersia_schema_prune( $value );
		}

		if ( null === $data[ $key ] || '' === $data[ $key ] || array() === $data[ $key ] ) {
			unset( $data[ $key ] );
		}
	}

	return $data;
}

/** یک منبع داده را به مقدار تبدیل می‌کند. */
function topersia_schema_resolve( $spec, $post_id ) {

	if ( isset( $spec['const'] ) ) {
		return $spec['const'];
	}

	if ( isset( $spec['meta_lang'] ) ) {

		$lang  = topersia_schema_lang();
		$value = get_post_meta( $post_id, $spec['meta_lang'] . '_' . $lang, true );

		return $value ? wp_strip_all_tags( (string) $value ) : null;
	}

	if ( isset( $spec['image'] ) ) {
		return topersia_schema_image( get_post_meta( $post_id, $spec['image'], true ) );
	}

	if ( isset( $spec['number'] ) ) {

		$value = get_post_meta( $post_id, $spec['number'], true );

		return is_numeric( $value ) ? 0 + $value : null;
	}

	if ( isset( $spec['meta'] ) ) {

		$value = get_post_meta( $post_id, $spec['meta'], true );

		return $value ? wp_strip_all_tags( (string) $value ) : null;
	}

	return null;
}

/* ═══════════════════════════════════════════════════════════════════════
 *  بخش ۴ — گره‌های سراسری
 * ═══════════════════════════════════════════════════════════════════════
 */

/** Organization — یک‌بار برای کل سایت. */
function topersia_schema_organization() {

	return array(
		'@type' => 'Organization',
		'@id'   => TOPERSIA_SITE_ID . '/#organization',
		'name'  => get_bloginfo( 'name' ),
		'url'   => TOPERSIA_SITE_ID,
	);
}

/** WebSite — با ارجاع به Organization. */
function topersia_schema_website() {

	return array(
		'@type'       => 'WebSite',
		'@id'         => TOPERSIA_SITE_ID . '/#website',
		'url'         => TOPERSIA_SITE_ID,
		'name'        => get_bloginfo( 'name' ),
		'description' => get_bloginfo( 'description' ),
		'publisher'   => array( '@id' => TOPERSIA_SITE_ID . '/#organization' ),
		'inLanguage'  => topersia_schema_lang(),
	);
}

/**
 * BreadcrumbList از زنجیرهٔ روابط JetEngine.
 *
 * چرا از روابط و نه از parent/child وردپرس: سلسله‌مراتب این پروژه در روابط
 * JetEngine زندگی می‌کند و URLها مسطح‌اند. هیچ افزونه‌ای این را به‌صورت
 * پیش‌فرض درست نمی‌سازد.
 *
 * گره‌های علامت‌خوردهٔ skip (مثل شهرستان که صفحه ندارد) از نمایش حذف
 * می‌شوند ولی زنجیره از میانشان عبور می‌کند.
 */
function topersia_schema_breadcrumb( $post_id, $config ) {

	$items = array();
	$pos   = 1;

	$items[] = array(
		'@type'    => 'ListItem',
		'position' => $pos ++,
		'name'     => __( 'خانه', 'topersia-schema' ),
		'item'     => TOPERSIA_SITE_ID,
	);

	// زنجیرهٔ نیاکان را از پایین به بالا بپیما، بعد معکوس کن.
	$ancestors = array();
	$current   = $post_id;

	if ( ! empty( $config['parents'] ) && function_exists( 'jet_engine' ) ) {

		foreach ( $config['parents'] as $step ) {

			$relation = topersia_relation( $step['relation'] );

			if ( ! $relation ) {
				break;
			}

			$parents = $relation->get_parents( $current, 'ids' );

			if ( empty( $parents ) ) {
				break;
			}

			$parent_id = absint( reset( $parents ) );

			if ( empty( $step['skip'] ) ) {
				$ancestors[] = $parent_id;
			}

			$current = $parent_id;
		}
	}

	foreach ( array_reverse( $ancestors ) as $ancestor_id ) {

		$view = apply_filters( 'wpml_object_id', $ancestor_id, get_post_type( $ancestor_id ), true );

		if ( ! $view ) {
			continue;
		}

		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $pos ++,
			'name'     => topersia_schema_name( $view ),
			'item'     => get_permalink( $view ),
		);
	}

	$items[] = array(
		'@type'    => 'ListItem',
		'position' => $pos ++,
		'name'     => topersia_schema_name( $post_id ),
	);

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => get_permalink( $post_id ) . '#breadcrumb',
		'itemListElement' => $items,
	);
}

/**
 * FAQPage از Repeater مشترک faq_items.
 *
 * ردیف‌های بازنشسته (P5-D121) با topersia_active_rows حذف می‌شوند. اگر این
 * فیلتر نبود، یک پرسش بازنشسته به JSON-LD نشت می‌کرد و موتور پاسخ داده‌ای
 * می‌دید که در صفحه وجود ندارد — بدترین نوع ناسازگاری برای AEO.
 */
function topersia_schema_faq( $post_id ) {

	$rows = topersia_active_rows( get_post_meta( $post_id, 'faq_items', true ) );

	if ( empty( $rows ) ) {
		return null;
	}

	$questions = array();

	foreach ( $rows as $row ) {

		$question = isset( $row['faq_question'] ) ? trim( wp_strip_all_tags( $row['faq_question'] ) ) : '';
		$answer   = isset( $row['faq_answer'] ) ? trim( wp_strip_all_tags( $row['faq_answer'] ) ) : '';

		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$questions[] = array(
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	if ( empty( $questions ) ) {
		return null;
	}

	return array(
		'@type'      => 'FAQPage',
		'@id'        => get_permalink( $post_id ) . '#faq',
		'mainEntity' => $questions,
	);
}

/* ═══════════════════════════════════════════════════════════════════════
 *  بخش ۵ — سازنده‌های اختصاصی
 * ═══════════════════════════════════════════════════════════════════════
 */

/**
 * افزودن مسیر مرتب توقف‌ها به گرهٔ TouristTrip.
 *
 * این قلب ارزش AEO این صفحه است: itinerary به‌صورت ItemList با position، و
 * هر توقف با @id مبتنی بر entity_uid خودش — نه با URL. یعنی موتور پاسخ
 * می‌تواند به پرسش «جادهٔ ابریشم از چه شهرهایی و به چه ترتیبی می‌گذرد» دقیق
 * پاسخ دهد و همان شهرها را در صفحات دیگر بازشناسی کند.
 */
function topersia_schema_route( $node, $post_id, $config = array() ) {

	// طبقه‌بندی → touristType
	$classification = get_post_meta( $post_id, 'classification', true );

	if ( $classification ) {

		$labels = array(
			'historical' => 'Historical Route',
			'natural'    => 'Natural Route',
			'thematic'   => 'Thematic Route',
		);

		$node['touristType'] = isset( $labels[ $classification ] ) ? $labels[ $classification ] : $classification;
	}

	/*
	 * سطح دشواری.
	 *
	 * TouristTrip ویژگی معتبری برای دشواری ندارد، ولی touristType یک Text
	 * آزاد است و می‌تواند هر دو سیگنال را با هم حمل کند. این تنها راه
	 * معتبرِ رساندن این اطلاعات به موتور پاسخ است.
	 */
	$difficulty = get_post_meta( $post_id, 'difficulty_level', true );

	if ( $difficulty && isset( $node['touristType'] ) ) {

		$levels = array(
			'easy'        => 'Easy',
			'moderate'    => 'Moderate',
			'challenging' => 'Challenging',
			'technical'   => 'Technical',
		);

		if ( isset( $levels[ $difficulty ] ) ) {
			$node['touristType'] = array(
				$node['touristType'],
				$levels[ $difficulty ] . ' Difficulty',
			);
		}
	}

	/*
	 * مسافت و مدت عمداً اینجا نیستند.
	 *
	 * TouristTrip از Trip ← Intangible ← Thing ارث می‌برد و هیچ‌کدام ویژگی
	 * distance یا estimatedDuration ندارند. Schema Markup Validator این دو
	 * را رد می‌کند. هیچ ویژگی معتبر جایگزینی هم روی این نوع وجود ندارد —
	 * additionalProperty فقط روی Place و Product مجاز است.
	 *
	 * این دو واقعیت در متن قابل‌مشاهدهٔ صفحه و در ai_summary (که به
	 * WebPage.abstract منتقل شده) موجودند — و موتورهای پاسخ متن صفحه را
	 * می‌خوانند، نه فقط شِما را.
	 */

	// مسیر مرتب توقف‌ها — از هسته.
	if ( ! function_exists( 'topersia_p9_stops' ) ) {
		return $node;
	}

	$stops = topersia_p9_stops( $post_id );

	if ( empty( $stops ) ) {
		return $node;
	}

	$elements = array();

	foreach ( $stops as $stop ) {

		$destination = array(
			'@type' => 'TouristDestination',
			'@id'   => topersia_schema_uid( $stop['id'] ),
			'name'  => $stop['title'],
			'url'   => $stop['url'],
		);

		if ( ! empty( $stop['note'] ) ) {
			$destination['description'] = wp_strip_all_tags( $stop['note'] );
		}

		$elements[] = array(
			'@type'    => 'ListItem',
			'position' => absint( $stop['position'] ),
			'item'     => $destination,
		);
	}

	$node['itinerary'] = array(
		'@type'           => 'ItemList',
		'numberOfItems'   => count( $elements ),
		'itemListOrder'   => 'https://schema.org/ItemListOrderAscending',
		'itemListElement' => $elements,
	);

	return $node;
}

/**
 * سازندهٔ عمومی موجودیت‌های مکانی — شهر، روستا، جزیره، کوه و بقیه.
 *
 * سه چیز اضافه می‌کند که هیچ‌کدام روی TouristTrip ممکن نبود:
 *
 * geo — مختصات جغرافیایی. برای موتورهای پاسخ ارزش بالایی دارد؛ پرسشی مثل
 * «نزدیک‌ترین شهر تاریخی به مشهد» بدون مختصات اصلاً قابل پاسخ نیست.
 *
 * additionalProperty — برخلاف Trip، نوع Place این ویژگی را می‌پذیرد. پس
 * جمعیت و مساحت را می‌شود معتبر منتشر کرد. خودِ Schema.org ویژگی population
 * ندارد و افزودنش خطای اعتبارسنجی می‌داد.
 *
 * containedInPlace — گرهٔ جغرافیایی والد از روی زنجیرهٔ روابط JetEngine. تا
 * وقتی زنجیرهٔ G5 ← G4 ← G3 در داده برقرار نشده (DEBT-P9-001) خالی می‌ماند
 * و بی‌صدا حذف می‌شود.
 */
function topersia_schema_place( $node, $post_id, $config = array() ) {

	/* مختصات */
	$coords = get_post_meta( $post_id, 'geo_coordinates', true );

	if ( ! empty( $coords ) && preg_match( '/(-?\d+\.?\d*)\s*[,،]\s*(-?\d+\.?\d*)/u', $coords, $matches ) ) {

		$node['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => 0 + $matches[1],
			'longitude' => 0 + $matches[2],
		);
	}

	/* جمعیت و مساحت — تنها راه معتبر روی Place */
	$extras = array();

	$population = get_post_meta( $post_id, 'population', true );

	if ( is_numeric( $population ) && $population > 0 ) {
		$extras[] = array(
			'@type' => 'PropertyValue',
			'name'  => 'population',
			'value' => 0 + $population,
		);
	}

	$area = get_post_meta( $post_id, 'area_km2', true );

	if ( is_numeric( $area ) && $area > 0 ) {
		$extras[] = array(
			'@type'    => 'PropertyValue',
			'name'     => 'area',
			'value'    => 0 + $area,
			'unitCode' => 'KMK',
		);
	}

	if ( $extras ) {
		$node['additionalProperty'] = $extras;
	}

	/* گرهٔ جغرافیایی والد */
	if ( ! empty( $config['parents'] ) && function_exists( 'jet_engine' ) ) {

		$current = $post_id;

		foreach ( $config['parents'] as $step ) {

			$relation = topersia_relation( $step['relation'] );

			if ( ! $relation ) {
				break;
			}

			$parents = $relation->get_parents( $current, 'ids' );

			if ( empty( $parents ) ) {
				break;
			}

			$current = absint( reset( $parents ) );

			if ( empty( $step['skip'] ) ) {
				$node['containedInPlace'] = array( '@id' => topersia_schema_uid( $current ) );
				break;
			}
		}
	}

	/* شناسهٔ خارجی — ویکی‌داده یا مرجع رسمی */
	$external = get_post_meta( $post_id, 'external_reference_uri', true );

	if ( ! empty( $external ) ) {
		$node['sameAs'] = esc_url_raw( $external );
	}

	return $node;
}

/* ═══════════════════════════════════════════════════════════════════════
 *  بخش ۶ — ساخت و انتشار گراف
 * ═══════════════════════════════════════════════════════════════════════
 */

/** گراف کامل یک صفحهٔ موجودیت را می‌سازد. */
function topersia_schema_build( $post_id ) {

	$post_type = get_post_type( $post_id );
	$map       = topersia_schema_map();

	if ( ! isset( $map[ $post_type ] ) ) {
		return array();
	}

	$config    = $map[ $post_type ];
	$permalink = get_permalink( $post_id );
	$uid       = topersia_schema_uid( $post_id );

	/*
	 * گرهٔ خودِ موجودیت.
	 *
	 * inLanguage اینجا نیست: ویژگیِ CreativeWork است و روی انواع Intangible
	 * مثل TouristTrip معتبر نیست. زبان روی گرهٔ WebPage اعلام می‌شود که خودش
	 * CreativeWork است — و پیوند دوسویهٔ about/subjectOf باعث می‌شود
	 * مصرف‌کننده زبان را به موجودیت هم نسبت دهد.
	 */
	$entity = array(
		'@type'     => $config['type'],
		'@id'       => $uid,
		'url'       => $permalink,
		'subjectOf' => array( '@id' => $permalink . '#webpage' ),
	);

	if ( ! empty( $config['additionalType'] ) ) {
		$entity['additionalType'] = $config['additionalType'];
	}

	foreach ( $config['props'] as $property => $spec ) {
		$entity[ $property ] = topersia_schema_resolve( $spec, $post_id );
	}

	if ( empty( $entity['name'] ) ) {
		$entity['name'] = topersia_schema_name( $post_id );
	}

	if ( ! empty( $config['builder'] ) && is_callable( $config['builder'] ) ) {
		$entity = call_user_func( $config['builder'], $entity, $post_id, $config );
	}

	/* گرهٔ صفحه */
	$webpage = array(
		'@type'      => 'WebPage',
		'@id'        => $permalink . '#webpage',
		'url'        => $permalink,
		'name'       => topersia_schema_name( $post_id ),
		'isPartOf'   => array( '@id' => TOPERSIA_SITE_ID . '/#website' ),
		'about'      => array( '@id' => $uid ),
		'breadcrumb' => array( '@id' => $permalink . '#breadcrumb' ),
		'inLanguage' => topersia_schema_lang(),
	);

	$modified = get_post_modified_time( 'c', true, $post_id );

	if ( $modified ) {
		$webpage['dateModified'] = $modified;
	}

	/*
	 * توضیح و چکیده روی WebPage می‌نشینند، نه روی موجودیت.
	 *
	 * WebPage یک CreativeWork است، پس abstract و inLanguage اینجا معتبرند.
	 * ai_summary — چکیدهٔ فشرده برای موتورهای پاسخ — دقیقاً همان چیزی است
	 * که abstract برایش ساخته شده.
	 */
	$page_desc = get_post_meta( $post_id, 'meta_description', true );

	if ( empty( $page_desc ) ) {
		$page_desc = get_post_meta( $post_id, 'short_summary', true );
	}

	if ( ! empty( $page_desc ) ) {
		$webpage['description'] = wp_strip_all_tags( (string) $page_desc );
	}

	$page_abstract = get_post_meta( $post_id, 'ai_summary', true );

	if ( ! empty( $page_abstract ) ) {
		$webpage['abstract'] = wp_strip_all_tags( (string) $page_abstract );
	}

	$graph = array(
		topersia_schema_organization(),
		topersia_schema_website(),
		$webpage,
		topersia_schema_breadcrumb( $post_id, $config ),
		$entity,
	);

	$faq = topersia_schema_faq( $post_id );

	if ( $faq ) {
		$graph[] = $faq;
	}

	return apply_filters( 'topersia_schema_graph', $graph, $post_id, $config );
}

/** گراف را به JSON آماده برای انتشار تبدیل می‌کند. */
function topersia_schema_json( $post_id ) {

	$graph = topersia_schema_build( $post_id );

	if ( empty( $graph ) ) {
		return '';
	}

	$data = topersia_schema_prune(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( $graph ),
		)
	);

	/*
	 * JSON_HEX_TAG و JSON_HEX_AMP حیاتی‌اند، نه تزئینی.
	 *
	 * بدون آن‌ها، اگر رشتهٔ </script> در هر فیلد متایی بیفتد — یک یادداشت
	 * توقف، یک پاسخ پرسش، یک توضیح — از تگ <script> بیرون می‌زند و هر چه
	 * پس از آن بیاید به‌عنوان HTML اجرا می‌شود. یعنی یک مسیر XSS واقعی.
	 * این دو پرچم < و > و & را به کد یونیکد تبدیل می‌کنند؛ JSON معتبر
	 * می‌ماند و آدرس‌ها هم خوانا.
	 */
	$json = wp_json_encode(
		$data,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT
	);

	return $json ? $json : '';
}

/** انتشار گراف در head. */
add_action( 'wp_head', 'topersia_schema_output', 5 );

function topersia_schema_output() {

	if ( ! topersia_schema_core_ready() ) {
		return;
	}

	if ( ! is_singular() || is_feed() ) {
		return;
	}

	$post_id = get_queried_object_id();

	if ( ! $post_id ) {
		return;
	}

	// صفحاتی که noindex‌اند نباید دادهٔ ساختاریافته بدهند.
	if ( 'tp_county' === get_post_type( $post_id ) ) {
		return;
	}

	$json = topersia_schema_json( $post_id );

	if ( '' === $json ) {
		return;
	}

	echo "\n<!-- ToPersia Structured Data -->\n";
	echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
}

/* ═══════════════════════════════════════════════════════════════════════
 *  بخش ۷ — زبانهٔ پنل مدیریت
 * ═══════════════════════════════════════════════════════════════════════
 * زبانه‌ای در پنل ToPersia که هسته می‌سازد. اگر هسته غیرفعال شود، فیلتر
 * اصلاً وجود ندارد و این بخش بی‌صدا کنار می‌ماند.
 *
 * سه چیز نشان می‌دهد:
 *
 * ۱ — نقشهٔ موجودیت‌ها، خوانا و بدون بازکردن فایل.
 * ۲ — نوع‌های پستی که هنوز در نقشه نیستند و هیچ JSON-LD منتشر نمی‌کنند.
 *     این فهرست عملاً برنامهٔ کاری فاز ۶ است.
 * ۳ — پیش‌نمایش زندهٔ گراف برای هر شناسهٔ پست.
 *
 * مورد سوم یک مشکل واقعی را حل می‌کند: تا وقتی حالت تعمیر المنتور روشن
 * است، خروجی فرانت برای بررسی در دسترس نیست. با این ابزار می‌شود پیش از
 * انتشار، دقیقاً همان چیزی را دید که منتشر خواهد شد.
 */
add_filter( 'topersia_admin_tabs', 'topersia_schema_admin_tab' );

function topersia_schema_admin_tab( $tabs ) {

	$tabs['schema'] = array(
		'label'    => 'شِما',
		'callback' => 'topersia_schema_admin_render',
	);

	return $tabs;
}

/** رندر زبانهٔ شِما. */
function topersia_schema_admin_render() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$map = topersia_schema_map();

	/* ── وضعیت ── */
	echo '<h2>وضعیت</h2><table class="widefat striped"><tbody>';

	printf(
		'<tr><td style="width:20em"><strong>نسخهٔ افزونه</strong></td><td><code>%s</code></td></tr>',
		esc_html( TOPERSIA_SCHEMA_VERSION )
	);

	printf(
		'<tr><td><strong>هستهٔ سازگار</strong></td><td>%s</td></tr>',
		topersia_schema_core_ready()
			? '✅ نسخهٔ <code>' . esc_html( TOPERSIA_CORE_VERSION ) . '</code> — حداقل لازم <code>' . esc_html( TOPERSIA_SCHEMA_MIN_CORE ) . '</code>'
			: '🔴 هسته غایب یا قدیمی است'
	);

	printf(
		'<tr><td><strong>شِمای بومی Rank Math</strong></td><td>%s</td></tr>',
		has_filter( 'rank_math/json_ld', '__return_empty_array' )
			? '✅ خاموش — کل JSON-LD از این افزونه می‌آید'
			: '⚠️ خاموش نشده؛ ممکن است دو گراف روی یک صفحه بنشیند'
	);

	echo '</tbody></table>';

	/* ── نقشهٔ موجودیت‌ها ── */
	echo '<h2>نقشهٔ موجودیت‌ها</h2>';
	echo '<p style="max-width:62em">برای افزودن موجودیت تازه، یک بلوک به <code>topersia_schema_map()</code> در بخش ۲ فایل <code>topersia-schema.php</code> اضافه کن. این جدول فقط نمایش است.</p>';
	echo '<table class="widefat striped"><thead><tr><th>نوع پست</th><th>نوع Schema</th><th>نوع تکمیلی</th><th>سازنده</th><th>ویژگی‌ها</th><th>زنجیرهٔ نیاکان</th></tr></thead><tbody>';

	foreach ( $map as $post_type => $config ) {

		$object = get_post_type_object( $post_type );
		$label  = $object ? $object->labels->name : $post_type;

		printf(
			'<tr><td><strong>%s</strong><br><code>%s</code></td><td><code>%s</code></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_html( $label ),
			esc_html( $post_type ),
			esc_html( $config['type'] ),
			! empty( $config['additionalType'] ) ? '<code>' . esc_html( str_replace( 'https://schema.org/', '', $config['additionalType'] ) ) . '</code>' : '—',
			! empty( $config['builder'] ) ? '<code>' . esc_html( $config['builder'] ) . '</code>' : '—',
			esc_html( implode( '، ', array_keys( $config['props'] ) ) ),
			! empty( $config['parents'] ) ? count( $config['parents'] ) . ' گام' : '—'
		);
	}

	echo '</tbody></table>';

	/* ── موجودیت‌های بدون نگاشت ── */
	$unmapped = array();

	foreach ( get_post_types( array(), 'objects' ) as $type => $object ) {

		if ( ! topersia_is_entity( $type ) || isset( $map[ $type ] ) ) {
			continue;
		}

		if ( 'tp_county' === $type ) {
			continue; // G4 عمداً شِما نمی‌گیرد — noindex است.
		}

		$unmapped[ $type ] = $object->labels->name;
	}

	echo '<h2>موجودیت‌های بدون نگاشت</h2>';

	if ( empty( $unmapped ) ) {

		echo '<p>✅ همهٔ نوع‌های پست در نقشه هستند.</p>';

	} else {

		printf(
			'<p><strong>%s نوع پست</strong> هنوز هیچ JSON-LD منتشر نمی‌کنند. صفحه‌شان برای موتور پاسخ فقط متن است، بدون موجودیت قابل بازشناسی. این فهرست عملاً برنامهٔ کاری ساخت شِما است.</p>',
			esc_html( number_format_i18n( count( $unmapped ) ) )
		);

		echo '<div style="columns:3;max-width:70em">';

		foreach ( $unmapped as $type => $label ) {
			printf( '<div style="break-inside:avoid">%s — <code>%s</code></div>', esc_html( $label ), esc_html( $type ) );
		}

		echo '</div>';
	}

	/* ── پیش‌نمایش زنده ── */
	echo '<h2>پیش‌نمایش گراف</h2>';
	echo '<p style="max-width:62em">شناسهٔ یک پست را وارد کن تا دقیقاً همان JSON-LD که منتشر می‌شود را ببینی. تا وقتی حالت تعمیر روشن است، این تنها راه راستی‌آزمایی خروجی است.</p>';

	$preview_id = isset( $_GET['topersia_preview'] ) ? absint( $_GET['topersia_preview'] ) : 0;

	echo '<form method="get">';
	echo '<input type="hidden" name="page" value="topersia" />';
	echo '<input type="hidden" name="tab" value="schema" />';
	printf(
		'<input type="number" name="topersia_preview" value="%s" class="regular-text" placeholder="شناسهٔ پست" /> ',
		esc_attr( $preview_id ? $preview_id : '' )
	);
	submit_button( 'نمایش گراف', 'secondary', '', false );
	echo '</form>';

	if ( ! $preview_id ) {
		return;
	}

	$post = get_post( $preview_id );

	if ( ! $post ) {
		echo '<div class="notice notice-error inline"><p>پستی با این شناسه پیدا نشد.</p></div>';
		return;
	}

	printf(
		'<p><strong>%s</strong> — نوع <code>%s</code> — وضعیت <code>%s</code></p>',
		esc_html( get_the_title( $preview_id ) ),
		esc_html( $post->post_type ),
		esc_html( $post->post_status )
	);

	if ( ! isset( $map[ $post->post_type ] ) ) {
		echo '<div class="notice notice-warning inline"><p>این نوع پست در نقشهٔ شِما نیست، پس هیچ JSON-LD منتشر نمی‌کند.</p></div>';
		return;
	}

	$json = topersia_schema_json( $preview_id );

	if ( '' === $json ) {
		echo '<div class="notice notice-warning inline"><p>گراف خالی برگشت. احتمالاً دادهٔ لازم هنوز وارد نشده است.</p></div>';
		return;
	}

	printf(
		'<textarea readonly rows="28" style="width:100%%;font-family:monospace;direction:ltr;text-align:left">%s</textarea>',
		esc_textarea( $json )
	);

	printf(
		'<p><a href="%s" target="_blank" rel="noopener" class="button">آزمون Rich Results گوگل</a></p>',
		esc_url( 'https://search.google.com/test/rich-results?url=' . rawurlencode( get_permalink( $preview_id ) ) )
	);
}
