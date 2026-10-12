<?php
/**
 * مدیریت مرکزی سرویس‌های خارجی — P5-D249 · SPRINT-4.4 بند ۲٫۲.
 *
 * صفحۀ پیشخوان «ToPersia ← سرویس‌های خارجی». هر سرویس یک سطر: ارائه‌دهنده،
 * وضعیت، کلید (فقط‌نوشتنی)، endpoint و مدل و نسخه، سقف‌ها، TTL کش،
 * آخرین فراخوانی موفق و مصرف امروز، و «آزمون اتصال».
 *
 *   topersia_api( 'weather' )   پیکربندی یک سرویس — فقط سمت سرور.
 *
 * قاعده‌ها:
 *   — ثابت wp-config (مثلاً TOPERSIA_GEMINI_API_KEY) بر پیشخوان برنده است؛
 *     فیلد «از wp-config» و قفل نشان داده می‌شود.
 *   — ذخیره: option با autoload=no؛ کلید با sodium_crypto_secretbox و کلیدِ
 *     مشتق از wp_salt('auth') رمز می‌شود. متن آشکار کلید هرگز ذخیره نمی‌شود.
 *   — کلید هرگز در HTML، JS، پاسخ REST، پیام آزمون یا لاگ نمی‌آید؛ پیشخوان
 *     فقط «••••» و چهار نویسۀ آخر را نشان می‌دهد. فراخوانی کلیددار کلید را
 *     در سرآیند می‌فرستد، نه در نشانی — نشانی در لاگ‌ها ثبت می‌شود.
 *   — آداپتورها با فیلتر `topersia_service_adapters` ثبت می‌شوند و کلید را
 *     فقط از topersia_api() می‌گیرند.
 *   — پرچم سراسری site_is_commercial (پیش‌فرض خاموش): آداپتوری که ردۀ
 *     رایگانش غیرتجاری است (Open-Meteo) با پرچم روشن و بی کلید پولی خاموش
 *     می‌شود و به مدیر هشدار می‌دهد.
 *   — مهاجرت: خلاصه‌ساز، هوا، کاشی و سقف‌های هوش مصنوعی از این لایه
 *     می‌خوانند؛ اگر سطر سرویس هنوز ذخیره نشده (configured=false)، تنظیم
 *     قدیمی خودشان. دکمۀ «انتقال» یک بار منتقل می‌کند؛ تنظیم قدیمی پاک
 *     نمی‌شود — هیچ تنظیمی از دست نمی‌رود.
 *
 * رفتار هر سرویس فقط از مستند رسمی (قاعدۀ ۱۱) — منبع هر آداپتور در `doc`.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TOPERSIA_SERVICES_OPTION' ) ) {
	define( 'TOPERSIA_SERVICES_OPTION', 'topersia_services' );
}
if ( ! defined( 'TOPERSIA_SERVICES_USAGE' ) ) {
	define( 'TOPERSIA_SERVICES_USAGE', 'topersia_services_usage' );
}
if ( ! defined( 'TOPERSIA_COMMERCIAL_OPTION' ) ) {
	define( 'TOPERSIA_COMMERCIAL_OPTION', 'topersia_site_is_commercial' );
}
/* شکاف دوم هر سرویس — «+ ارائه‌دهندۀ دوم»: پشتیبان وقتی اولی در دسترس نیست. */
if ( ! defined( 'TOPERSIA_SERVICES_ALT' ) ) {
	define( 'TOPERSIA_SERVICES_ALT', '#2' );
}

/* ══ فهرست سرویس‌ها ═══════════════════════════════════════════════════ */

/**
 * @return array<string,array{label:string,purpose:string,provider:string,enabled:bool}>
 */
function topersia_services_catalog() {

	return (array) apply_filters( 'topersia_services_catalog', array(
		'ai.tts'          => array( 'label' => 'صدای هوش مصنوعی', 'purpose' => 'روایت و پادکست — فاز ۴', 'provider' => 'gemini-tts', 'enabled' => false ),
		'weather'         => array( 'label' => 'هوا', 'purpose' => 'کارت «هوای این روزها» و انیمیشن سربرگ', 'provider' => 'met-norway', 'enabled' => true ),
		'air_quality'     => array( 'label' => 'کیفیت هوا', 'purpose' => 'جعبۀ «کیفیت هوا» زیر کارت آب‌وهوا (US AQI · PM2.5 · PM10 · غبار)', 'provider' => 'open-meteo-aq', 'enabled' => true ),
		'map.tiles'       => array( 'label' => 'کاشی نقشه', 'purpose' => 'نقشۀ خودمیزبان PMTiles', 'provider' => 'pmtiles', 'enabled' => true ),
		'routing'         => array( 'label' => 'مسیریابی', 'purpose' => 'مسیر میان دو نقطه — فاز ۳', 'provider' => 'neshan-routing', 'enabled' => false ),
		'distance_matrix' => array( 'label' => 'ماتریس فاصله', 'purpose' => 'فاصلۀ چند نقطه — فاز ۳', 'provider' => 'neshan-matrix', 'enabled' => false ),
		'geocoding'       => array( 'label' => 'آدرس و آدرس معکوس', 'purpose' => 'نقطه ← آدرس، آدرس ← نقطه — فاز ۳', 'provider' => 'neshan-geocoding', 'enabled' => false ),
		'navigation'      => array( 'label' => 'پیوند مسیریابی', 'purpose' => 'دکمه‌های جعبۀ «مسیریابی» — فاز ۳', 'provider' => 'deep-links', 'enabled' => false ),
		'auth.google'     => array( 'label' => 'ورود با گوگل', 'purpose' => 'ورود و ثبت‌نام — فاز ۵', 'provider' => 'google-identity', 'enabled' => false ),
	) );
}

/**
 * آداپتورهای پیش‌فرض هسته — هر endpoint و مدل از مستند رسمی (`doc`).
 *
 * Gemini: ai.google.dev از این شبکه ۴۰۳ داد؛ endpoint و مدل از مخزن رسمی
 * google-gemini/cookbook (آخرین commit ۲ سپتامبر ۲۰۲۶) و ارسال کلید در
 * سرآیند x-goog-api-key از SDK رسمی googleapis/python-genai.
 * ElevenLabs: elevenlabs.io/docs از این شبکه ۴۰۳ داد؛ نشانی پایه
 * (https://api.elevenlabs.io)، سرآیند xi-api-key، مسیر POST v1/text-to-speech/{voice_id}
 * با model_id و GET v1/models از SDK رسمی elevenlabs/elevenlabs-python؛ مدل eleven_v3
 * («70+ languages» در README همان مخزن). پشتیبانی فارسی راستی‌آزمایی نشد.
 * تولید متن (ai.text) این‌جا نیست: کلید و موتورش در خودِ خلاصه‌ساز.
 * پیوندهای مسیریابی (Google · Waze) از جدول ۳٫۲ سند فاز؛ صفحۀ رسمی‌شان از
 * این شبکه ۴۰۳ داد — در فاز ۳ راستی‌آزمایی و روشن می‌شوند.
 */
function topersia_services_core_adapters( $adapters ) {

	$cookbook = 'https://github.com/google-gemini/cookbook/blob/main/quickstarts/rest/';

	return array_merge( (array) $adapters, array(
		'gemini-tts'       => array( 'service' => 'ai.tts', 'label' => 'Google Gemini TTS', 'needs_key' => true, 'key_const' => 'TOPERSIA_GEMINI_API_KEY', 'endpoint' => 'https://generativelanguage.googleapis.com/v1beta', 'model' => 'gemini-3.8-flash-tts', 'free_commercial' => true, 'doc' => 'https://github.com/google-gemini/cookbook/blob/main/quickstarts/Get_started_TTS.ipynb', 'test' => 'topersia_service_test_gemini' ),
		'elevenlabs'       => array( 'service' => 'ai.tts', 'label' => 'ElevenLabs', 'needs_key' => true, 'key_const' => 'TOPERSIA_ELEVENLABS_API_KEY', 'endpoint' => 'https://api.elevenlabs.io/v1/text-to-speech', 'model' => 'eleven_v3', 'free_commercial' => true, 'extra' => array( 'voice_id' => '' ), 'fields' => array( 'voice_id' => 'شناسۀ صدا (voice_id)' ), 'doc' => 'https://github.com/elevenlabs/elevenlabs-python', 'test' => 'topersia_service_test_elevenlabs' ),
		'met-norway'       => array( 'service' => 'weather', 'label' => 'MET Norway', 'needs_key' => false, 'endpoint' => 'https://api.met.no/weatherapi/locationforecast/2.0/compact', 'free_commercial' => true, 'doc' => 'https://api.met.no/weatherapi/locationforecast/2.0/documentation', 'note' => 'CC BY 4.0 — User-Agent شناسا، Expires و If-Modified-Since (api.met.no/doc/TermsOfService)', 'test' => 'topersia_service_test_met' ),
		'open-meteo'       => array( 'service' => 'weather', 'label' => 'Open-Meteo', 'needs_key' => false, 'key_const' => 'TOPERSIA_OPEN_METEO_API_KEY', 'endpoint' => 'https://api.open-meteo.com/v1/forecast', 'free_commercial' => false, 'doc' => 'https://open-meteo.com/en/terms', 'note' => 'ردۀ رایگان فقط غیرتجاری (CC BY 4.0)', 'test' => 'topersia_service_test_open_meteo' ),
		'open-meteo-aq'    => array( 'service' => 'air_quality', 'label' => 'Open-Meteo Air Quality (CAMS)', 'needs_key' => false, 'key_const' => 'TOPERSIA_OPEN_METEO_API_KEY', 'endpoint' => 'https://air-quality-api.open-meteo.com/v1/air-quality', 'free_commercial' => false, 'doc' => 'https://open-meteo.com/en/docs/air-quality-api', 'note' => 'انتساب به CAMS و Open-Meteo لازم است', 'test' => 'topersia_service_test_open_meteo_aq' ),
		'pmtiles'          => array( 'service' => 'map.tiles', 'label' => 'PMTiles خودمیزبان', 'needs_key' => false, 'endpoint' => '', 'free_commercial' => true, 'extra' => array( 'path' => 'tiles/iran.pmtiles' ), 'doc' => 'https://docs.protomaps.com/pmtiles/', 'test' => 'topersia_service_test_pmtiles' ),
		'neshan-routing'   => array( 'service' => 'routing', 'label' => 'نشان — مسیریابی', 'needs_key' => true, 'key_const' => 'TOPERSIA_NESHAN_API_KEY', 'endpoint' => 'https://api.neshan.org/v4/direction', 'free_commercial' => true, 'doc' => 'https://platform.neshan.org/docs/api/routing-category/routing/', 'test' => 'topersia_service_test_neshan' ),
		'neshan-matrix'    => array( 'service' => 'distance_matrix', 'label' => 'نشان — ماتریس فاصله', 'needs_key' => true, 'key_const' => 'TOPERSIA_NESHAN_API_KEY', 'endpoint' => 'https://api.neshan.org/v1/distance-matrix', 'free_commercial' => true, 'doc' => 'https://platform.neshan.org/docs/api/routing-category/distance-matrix/', 'test' => 'topersia_service_test_neshan' ),
		'neshan-geocoding' => array( 'service' => 'geocoding', 'label' => 'نشان — آدرس', 'needs_key' => true, 'key_const' => 'TOPERSIA_NESHAN_API_KEY', 'endpoint' => 'https://api.neshan.org/geocoding/v1/plus', 'free_commercial' => true, 'extra' => array( 'reverse' => 'https://api.neshan.org/v5/reverse' ), 'doc' => 'https://platform.neshan.org/docs/api/search-category/geocoding/', 'test' => 'topersia_service_test_neshan' ),
		'deep-links'       => array( 'service' => 'navigation', 'label' => 'پیوند اپ مسیریابی', 'needs_key' => false, 'endpoint' => '', 'free_commercial' => true, 'extra' => array(
			'google' => array( 'on' => false, 'pattern' => 'https://www.google.com/maps/dir/?api=1&destination={lat},{lng}' ),
			'waze'   => array( 'on' => false, 'pattern' => 'https://waze.com/ul?ll={lat},{lng}&navigate=yes' ),
			'neshan' => array( 'on' => false, 'pattern' => '' ),
			'balad'  => array( 'on' => false, 'pattern' => '' ),
		), 'doc' => 'SPRINT-4.4 بند ۳٫۲', 'note' => 'پیوند است، نه فراخوانی — آزمون اتصال ندارد' ),
		'google-identity'  => array( 'service' => 'auth.google', 'label' => 'Google Identity', 'needs_key' => true, 'key_const' => 'TOPERSIA_GOOGLE_CLIENT_SECRET', 'endpoint' => '', 'free_commercial' => true, 'doc' => 'فاز ۵ — P5-D257', 'note' => 'فاز ۵' ),
	) );
}
add_filter( 'topersia_service_adapters', 'topersia_services_core_adapters', 5 );

/** همۀ آداپتورهای ثبت‌شده. */
function topersia_service_adapters() {
	return (array) apply_filters( 'topersia_service_adapters', array() );
}

/* ══ رمزنگاری ══════════════════════════════════════════════════════════ */

function topersia_services_can_encrypt() {
	return function_exists( 'sodium_crypto_secretbox' ) && function_exists( 'sodium_crypto_generichash' );
}

/** کلید رمز — مشتق از wp_salt('auth')؛ عوض‌شدن salt یعنی کلیدهای ذخیره‌شده باز نمی‌شوند. */
function topersia_services_box_key() {
	return sodium_crypto_generichash( 'topersia-services-v1|' . wp_salt( 'auth' ), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
}

/** @return string «v1:» + base64(nonce ‖ ciphertext)، یا تهی اگر رمزنگاری در دسترس نیست. */
function topersia_services_encrypt( $plain ) {

	if ( '' === (string) $plain || ! topersia_services_can_encrypt() ) {
		return '';
	}

	$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

	return 'v1:' . base64_encode( $nonce . sodium_crypto_secretbox( (string) $plain, $nonce, topersia_services_box_key() ) );
}

/** @return string|false متن آشکار، یا false اگر باز نشد. */
function topersia_services_decrypt( $blob ) {

	$blob = (string) $blob;

	if ( '' === $blob ) {
		return '';
	}

	if ( 0 !== strpos( $blob, 'v1:' ) || ! topersia_services_can_encrypt() ) {
		return false;
	}

	$raw = base64_decode( substr( $blob, 3 ), true );

	if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
		return false;
	}

	$plain = sodium_crypto_secretbox_open( substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ), topersia_services_box_key() );

	return false === $plain ? false : (string) $plain;
}

/* ══ خواندن ═══════════════════════════════════════════════════════════ */

function topersia_services_saved() {
	$v = get_option( TOPERSIA_SERVICES_OPTION, array() );
	return is_array( $v ) ? $v : array();
}

function topersia_site_is_commercial() {
	return (bool) get_option( TOPERSIA_COMMERCIAL_OPTION, false );
}

/** حافظۀ همان درخواست — پس از ذخیره پاک می‌شود. */
function topersia_api_flush() {
	topersia_api( null, true );
}

/**
 * پیکربندی یک سرویس — فقط سمت سرور.
 *
 * @param string|null $service شناسۀ سرویس.
 * @param bool        $flush   پاک‌کردن حافظۀ درخواست.
 * @return array service · configured · provider · adapter · enabled · status ·
 *               reason · key · key_source · key_tail · endpoint · model ·
 *               version · limits{user,site} · ttl · extra · doc
 */
function topersia_api( $service, $flush = false, $exact = false ) {

	static $memo = array();

	if ( $flush ) {
		$memo = array();
		return array();
	}

	$service = (string) $service;
	$mkey    = $service . ( $exact ? '|exact' : '' );

	if ( isset( $memo[ $mkey ] ) ) {
		return $memo[ $mkey ];
	}

	$catalog  = topersia_services_catalog();
	$adapters = topersia_service_adapters();
	$saved    = topersia_services_saved();
	$bsvc     = topersia_services_base( $service );
	$slot     = $bsvc === $service ? 1 : 2;
	$row      = isset( $saved[ $service ] ) && is_array( $saved[ $service ] ) ? $saved[ $service ] : array();
	$base     = isset( $catalog[ $bsvc ] ) ? $catalog[ $bsvc ] : array( 'label' => $service, 'purpose' => '', 'provider' => '', 'enabled' => false );

	if ( 2 === $slot ) {
		/* شکاف دوم: پیش‌فرضش نخستین آداپتور دیگرِ همان سرویس، و خاموش تا ذخیره شود */
		$first = isset( $saved[ $bsvc ]['provider'] ) ? (string) $saved[ $bsvc ]['provider'] : (string) $base['provider'];
		$other = array_values( array_diff( topersia_services_providers_for( $bsvc, $adapters ), array( $first ) ) );
		$base  = array_merge( $base, array( 'provider' => $other ? $other[0] : $base['provider'], 'enabled' => false ) );
	}

	$provider = isset( $row['provider'] ) && isset( $adapters[ $row['provider'] ] ) ? (string) $row['provider'] : (string) $base['provider'];
	$adapter  = isset( $adapters[ $provider ] ) ? $adapters[ $provider ] : array();
	$const    = isset( $adapter['key_const'] ) ? (string) $adapter['key_const'] : '';

	$key        = '';
	$key_source = '';
	$broken     = false;

	if ( '' !== $const && defined( $const ) && '' !== (string) constant( $const ) ) {
		$key        = (string) constant( $const );
		$key_source = 'config';
	} elseif ( ! empty( $row['key'] ) ) {
		$plain = topersia_services_decrypt( $row['key'] );
		if ( false === $plain ) {
			$broken = true;
		} else {
			$key        = $plain;
			$key_source = 'panel';
		}
	}

	$enabled = isset( $row['enabled'] ) ? (bool) $row['enabled'] : (bool) $base['enabled'];
	$status  = 'active';
	$reason  = '';
	$usage   = topersia_api_usage( $service );

	if ( ! $enabled ) {
		$status = 'off';
		$reason = 'خاموش';
	} elseif ( ! $adapter ) {
		$status = 'error';
		$reason = 'آداپتور «' . $provider . '» ثبت نشده';
	} elseif ( $broken ) {
		$status = 'error';
		$reason = 'کلید ذخیره‌شده با salt فعلی باز نمی‌شود — دوباره وارد کنید';
	} elseif ( ! empty( $adapter['needs_key'] ) && '' === $key ) {
		$status = 'nokey';
		$reason = 'بدون کلید';
	} elseif ( empty( $adapter['free_commercial'] ) && '' === $key && topersia_site_is_commercial() ) {
		$status = 'off';
		$reason = 'سایت تجاری است و ردۀ رایگان این ارائه‌دهنده غیرتجاری — کلید پولی لازم است';
	} elseif ( ! empty( $usage['last_error_at'] ) && (int) $usage['last_error_at'] > (int) ( isset( $usage['last_ok_at'] ) ? $usage['last_ok_at'] : 0 ) ) {
		$status = 'error';
		$reason = (string) ( isset( $usage['last_error'] ) ? $usage['last_error'] : 'خطای آخرین فراخوانی' );
	}

	/* آخرین فراخوانی ناموفق فقط تا پانزده دقیقه «در دسترس نیست» است؛ پس از آن اولی دوباره آزموده می‌شود */
	$recent_error = 'error' === $status && ! empty( $usage['last_error_at'] ) && time() - (int) $usage['last_error_at'] < 15 * MINUTE_IN_SECONDS;

	$extra = isset( $adapter['extra'] ) && is_array( $adapter['extra'] ) ? $adapter['extra'] : array();
	if ( isset( $row['extra'] ) && is_array( $row['extra'] ) ) {
		$extra = array_replace_recursive( $extra, $row['extra'] );
	}

	$memo[ $mkey ] = array(
		'service'    => $service,
		'label'      => (string) $base['label'],
		'purpose'    => (string) $base['purpose'],
		'configured' => ! empty( $row ),
		'provider'   => $provider,
		'adapter'    => isset( $adapter['label'] ) ? (string) $adapter['label'] : $provider,
		'enabled'    => $enabled,
		'status'     => $status,
		'reason'     => $reason,
		'key'        => $key,
		'key_source' => $key_source,
		'key_tail'   => 'config' === $key_source ? substr( $key, -4 ) : ( isset( $row['key_tail'] ) ? (string) $row['key_tail'] : '' ),
		'endpoint'   => ! empty( $row['endpoint'] ) ? (string) $row['endpoint'] : ( isset( $adapter['endpoint'] ) ? (string) $adapter['endpoint'] : '' ),
		'model'      => ! empty( $row['model'] ) ? (string) $row['model'] : ( isset( $adapter['model'] ) ? (string) $adapter['model'] : '' ),
		'version'    => ! empty( $row['version'] ) ? (string) $row['version'] : ( isset( $adapter['version'] ) ? (string) $adapter['version'] : '' ),
		'limits'     => array(
			'user' => isset( $row['limit_user'] ) && '' !== $row['limit_user'] ? absint( $row['limit_user'] ) : null,
			'site' => isset( $row['limit_site'] ) && '' !== $row['limit_site'] ? absint( $row['limit_site'] ) : null,
		),
		'ttl'        => isset( $row['ttl'] ) && '' !== $row['ttl'] ? absint( $row['ttl'] ) : '',
		'extra'      => $extra,
		'doc'        => isset( $adapter['doc'] ) ? (string) $adapter['doc'] : '',
		'needs_key'  => ! empty( $adapter['needs_key'] ),
		'fields'     => isset( $adapter['fields'] ) && is_array( $adapter['fields'] ) ? $adapter['fields'] : array(),
		'slot'       => $slot,
		'fallback'   => false,
	);

	/*
	 * بازگشت به شکاف دوم — فقط برای مصرف‌کننده، نه پیشخوان ($exact): اولی خاموش است، کلید
	 * ندارد، آداپتورش نیست یا همین تازگی شکست خورد، و دومی فعال است.
	 */
	if ( 1 === $slot && ! $exact && isset( $saved[ $service . TOPERSIA_SERVICES_ALT ] ) && ( in_array( $status, array( 'off', 'nokey' ), true ) || ( 'error' === $status && ( ! $adapter || $broken || $recent_error ) ) ) ) {
		$alt = topersia_api( $service . TOPERSIA_SERVICES_ALT, false, true );
		if ( 'active' === $alt['status'] ) {
			$memo[ $mkey ] = array_merge( $alt, array( 'service' => $service, 'fallback' => true, 'primary_reason' => $reason ) );
		}
	}

	return $memo[ $mkey ];
}

/** شناسۀ سرویس بی نشان شکاف دوم. */
function topersia_services_base( $service ) {
	$service = (string) $service;
	return TOPERSIA_SERVICES_ALT === substr( $service, -strlen( TOPERSIA_SERVICES_ALT ) ) ? substr( $service, 0, -strlen( TOPERSIA_SERVICES_ALT ) ) : $service;
}

/** آداپتورهای یک سرویس، به ترتیب ثبت. */
function topersia_services_providers_for( $service, $adapters = null ) {
	$out = array();
	foreach ( (array) ( null === $adapters ? topersia_service_adapters() : $adapters ) as $id => $a ) {
		if ( isset( $a['service'] ) && $service === $a['service'] ) {
			$out[] = (string) $id;
		}
	}
	return $out;
}

/* ══ مصرف ══════════════════════════════════════════════════════════════ */

/** مصرف یک سرویس: last_ok_at · last_error_at · last_error · day · count. */
function topersia_api_usage( $service ) {
	$all = get_option( TOPERSIA_SERVICES_USAGE, array() );
	return is_array( $all ) && isset( $all[ $service ] ) && is_array( $all[ $service ] ) ? $all[ $service ] : array();
}

/**
 * ثبت یک فراخوانی بالادست — لایۀ کش پس از هر دریافت صدا می‌زند.
 * پیام خطا هرگز کلید ندارد: هر رخداد کلیدِ همان سرویس با «•••» عوض می‌شود.
 */
function topersia_api_record( $service, $ok, $error = '' ) {

	$all   = get_option( TOPERSIA_SERVICES_USAGE, array() );
	$all   = is_array( $all ) ? $all : array();
	$u     = isset( $all[ $service ] ) && is_array( $all[ $service ] ) ? $all[ $service ] : array();
	$today = current_time( 'Y-m-d' );

	if ( ! isset( $u['day'] ) || $u['day'] !== $today ) {
		$u['day']   = $today;
		$u['count'] = 0;
	}

	$u['count'] = (int) $u['count'] + 1;

	if ( $ok ) {
		$u['last_ok_at'] = time();
	} else {
		$cfg             = topersia_api( $service );
		$u['last_error_at'] = time();
		$u['last_error']    = topersia_services_scrub( $service, substr( wp_strip_all_tags( (string) $error ), 0, 300 ), $cfg );
	}

	$all[ $service ] = $u;
	update_option( TOPERSIA_SERVICES_USAGE, $all, false );
	topersia_api_flush();
}

/** هر رخداد کلید یک سرویس در یک متن ← «•••». */
function topersia_services_scrub( $service, $text, $cfg = null ) {
	$cfg = is_array( $cfg ) ? $cfg : topersia_api( $service );
	return ( isset( $cfg['key'] ) && strlen( (string) $cfg['key'] ) >= 4 ) ? str_replace( (string) $cfg['key'], '•••', (string) $text ) : (string) $text;
}

/* ══ آزمون اتصال — یک فراخوانی کمینۀ سمت سرور ═══════════════════════════ */

/**
 * @return array{ok:bool,tested:bool,message:string}
 */
function topersia_service_test( $service ) {

	$cfg      = topersia_api( $service, false, true );
	$adapters = topersia_service_adapters();
	$adapter  = isset( $adapters[ $cfg['provider'] ] ) ? $adapters[ $cfg['provider'] ] : array();

	if ( empty( $adapter['test'] ) || ! is_callable( $adapter['test'] ) ) {
		return array( 'ok' => false, 'tested' => false, 'message' => 'آزموده نشد: ' . ( ! empty( $adapter['note'] ) ? $adapter['note'] : 'این ارائه‌دهنده آزمون اتصال ندارد' ) );
	}

	if ( ! empty( $adapter['needs_key'] ) && '' === $cfg['key'] ) {
		return array( 'ok' => false, 'tested' => false, 'message' => 'آزموده نشد: کلید ندارد' );
	}

	/* میزبانی که WP_HTTP_BLOCK_EXTERNAL بسته: درخواست اصلاً نمی‌رود — «آزموده نشد» با دلیل، نه «خطای شبکه» */
	$host = (string) wp_parse_url( (string) $cfg['endpoint'], PHP_URL_HOST );

	if ( '' !== $host && class_exists( 'WP_Http' ) && ( new WP_Http() )->block_request( (string) $cfg['endpoint'] ) ) {
		return array( 'ok' => false, 'tested' => false, 'message' => 'آزموده نشد: میزبان ' . $host . ' با WP_HTTP_BLOCK_EXTERNAL بسته است (WP_ACCESSIBLE_HOSTS)' );
	}

	$res = call_user_func( $adapter['test'], $cfg );
	$res = is_array( $res ) ? $res : array( 'ok' => false, 'message' => 'پاسخ نامعتبر' );

	topersia_api_record( $service, ! empty( $res['ok'] ), ! empty( $res['ok'] ) ? '' : (string) $res['message'] );

	return array(
		'ok'      => ! empty( $res['ok'] ),
		'tested'  => true,
		'message' => topersia_services_scrub( $service, (string) $res['message'], $cfg ),
	);
}

/** نتیجۀ یک پاسخ HTTP به پیام کوتاه — بی بدنه، بی نشانی. */
function topersia_service_http_result( $response, $expect = 200 ) {

	if ( is_wp_error( $response ) ) {
		return array( 'ok' => false, 'message' => 'خطای شبکه: ' . $response->get_error_code() );
	}

	$code = (int) wp_remote_retrieve_response_code( $response );

	return array( 'ok' => $expect === $code, 'message' => 'HTTP ' . $code );
}

/** Gemini — models.get (بی هزینۀ تولید)؛ کلید در سرآیند x-goog-api-key. */
function topersia_service_test_gemini( $cfg ) {
	$url = trailingslashit( $cfg['endpoint'] ) . 'models/' . rawurlencode( $cfg['model'] );
	return topersia_service_http_result( wp_remote_get( $url, array( 'timeout' => 10, 'headers' => array( 'x-goog-api-key' => $cfg['key'] ) ) ) );
}

/** MET Norway — یک پیش‌بینی compact برای تهران، با User-Agent شناسا. */
function topersia_service_test_met( $cfg ) {
	$ua  = class_exists( 'TP_Wx_Met_Norway' ) && method_exists( 'TP_Wx_Met_Norway', 'user_agent' ) ? TP_Wx_Met_Norway::user_agent() : 'ToPersia/' . TOPERSIA_CORE_VERSION . ' ' . home_url( '/' );
	$url = add_query_arg( array( 'lat' => '35.69', 'lon' => '51.39' ), $cfg['endpoint'] );
	return topersia_service_http_result( wp_remote_get( $url, array( 'timeout' => 10, 'user-agent' => $ua, 'headers' => array( 'Accept' => 'application/json' ) ) ) );
}

function topersia_service_test_open_meteo( $cfg ) {
	$url = add_query_arg( array( 'latitude' => '35.69', 'longitude' => '51.39', 'current' => 'temperature_2m' ), $cfg['endpoint'] );
	return topersia_service_http_result( wp_remote_get( $url, array( 'timeout' => 10 ) ) );
}

function topersia_service_test_open_meteo_aq( $cfg ) {
	$url = add_query_arg( array( 'latitude' => '35.69', 'longitude' => '51.39', 'current' => 'us_aqi' ), $cfg['endpoint'] );
	return topersia_service_http_result( wp_remote_get( $url, array( 'timeout' => 10 ) ) );
}

/** PMTiles — پرونده هست و با امضای «PMTiles» آغاز می‌شود. */
function topersia_service_test_pmtiles( $cfg ) {

	$up   = wp_upload_dir( null, false );
	$path = trailingslashit( (string) $up['basedir'] ) . ltrim( (string) ( isset( $cfg['extra']['path'] ) ? $cfg['extra']['path'] : '' ), '/' );

	if ( ! is_file( $path ) || ! is_readable( $path ) ) {
		return array( 'ok' => false, 'message' => 'پرونده نیست: uploads/' . ltrim( (string) $cfg['extra']['path'], '/' ) );
	}

	$fh   = fopen( $path, 'rb' );
	$head = $fh ? fread( $fh, 7 ) : '';
	if ( $fh ) {
		fclose( $fh );
	}

	return array( 'ok' => 'PMTiles' === $head, 'message' => 'PMTiles' === $head ? 'پرونده هست · ' . size_format( filesize( $path ) ) : 'امضای PMTiles نیست' );
}

/** ElevenLabs — فهرست مدل‌ها (GET v1/models)، کلید در سرآیند xi-api-key؛ بی هزینۀ تولید صدا. */
function topersia_service_test_elevenlabs( $cfg ) {
	$p   = wp_parse_url( (string) $cfg['endpoint'] );
	$url = ( isset( $p['scheme'], $p['host'] ) ? $p['scheme'] . '://' . $p['host'] : 'https://api.elevenlabs.io' ) . '/v1/models';
	return topersia_service_http_result( wp_remote_get( $url, array( 'timeout' => 10, 'headers' => array( 'xi-api-key' => $cfg['key'] ) ) ) );
}

/** نشان — آدرس معکوس یک نقطه؛ کلید در سرآیند Api-Key. */
function topersia_service_test_neshan( $cfg ) {
	$reverse = isset( $cfg['extra']['reverse'] ) ? (string) $cfg['extra']['reverse'] : 'https://api.neshan.org/v5/reverse';
	$url     = add_query_arg( array( 'lat' => '35.69', 'lng' => '51.39' ), $reverse );
	return topersia_service_http_result( wp_remote_get( $url, array( 'timeout' => 10, 'headers' => array( 'Api-Key' => $cfg['key'] ) ) ) );
}

/* ══ ذخیره ═════════════════════════════════════════════════════════════ */

/**
 * ذخیرۀ یک سطر از فرم. کلید: فیلد خالی ← کلید پیشین می‌ماند؛ «حذف» ← پاک.
 * کلیدِ ثابت wp-config هرگز در option نمی‌رود.
 *
 * @return string|true پیام خطا یا true
 */
function topersia_services_save_row( $service, array $in ) {

	$catalog = topersia_services_catalog();
	$bsvc    = topersia_services_base( $service );

	if ( ! isset( $catalog[ $bsvc ] ) ) {
		return 'سرویس ناشناخته';
	}

	$adapters = topersia_service_adapters();
	$saved    = topersia_services_saved();
	$row      = isset( $saved[ $service ] ) && is_array( $saved[ $service ] ) ? $saved[ $service ] : array();

	if ( $bsvc !== $service ) {
		/* شکاف دوم: «حذف» پاکش می‌کند؛ سطرِ پنهان‌ماندۀ ذخیره‌نشده چیزی نمی‌سازد */
		if ( ! empty( $in['remove'] ) ) {
			unset( $saved[ $service ] );
			update_option( TOPERSIA_SERVICES_OPTION, $saved, false );
			topersia_api_flush();
			return true;
		}
		if ( ! $row && ( ! isset( $in['use'] ) || '1' !== (string) $in['use'] ) ) {
			return true;
		}
	}

	$provider = isset( $in['provider'] ) ? sanitize_key( (string) $in['provider'] ) : ( isset( $row['provider'] ) ? $row['provider'] : $catalog[ $bsvc ]['provider'] );

	if ( ! isset( $adapters[ $provider ] ) || $bsvc !== $adapters[ $provider ]['service'] ) {
		return 'ارائه‌دهنده برای این سرویس نیست';
	}

	$row['provider']   = $provider;
	$row['enabled']    = ! empty( $in['enabled'] );
	$row['endpoint']   = isset( $in['endpoint'] ) ? esc_url_raw( trim( (string) $in['endpoint'] ) ) : ( isset( $row['endpoint'] ) ? $row['endpoint'] : '' );
	$row['model']      = isset( $in['model'] ) ? sanitize_text_field( (string) $in['model'] ) : ( isset( $row['model'] ) ? $row['model'] : '' );
	$row['version']    = isset( $in['version'] ) ? sanitize_text_field( (string) $in['version'] ) : ( isset( $row['version'] ) ? $row['version'] : '' );
	$row['limit_user'] = isset( $in['limit_user'] ) && '' !== trim( (string) $in['limit_user'] ) ? absint( $in['limit_user'] ) : '';
	$row['limit_site'] = isset( $in['limit_site'] ) && '' !== trim( (string) $in['limit_site'] ) ? absint( $in['limit_site'] ) : '';
	$row['ttl']        = isset( $in['ttl'] ) && '' !== trim( (string) $in['ttl'] ) ? absint( $in['ttl'] ) : '';

	if ( isset( $in['extra'] ) && is_array( $in['extra'] ) ) {
		$row['extra'] = topersia_services_clean_extra( $in['extra'] );
	}

	$const = isset( $adapters[ $provider ]['key_const'] ) ? (string) $adapters[ $provider ]['key_const'] : '';
	$lock  = '' !== $const && defined( $const ) && '' !== (string) constant( $const );

	if ( ! $lock ) {
		if ( ! empty( $in['key_clear'] ) ) {
			unset( $row['key'], $row['key_tail'] );
		} elseif ( isset( $in['key'] ) && '' !== trim( (string) $in['key'] ) ) {
			$plain = trim( (string) wp_unslash( $in['key'] ) );
			$blob  = topersia_services_encrypt( $plain );
			if ( '' === $blob ) {
				return 'رمزنگاری در دسترس نیست (ext-sodium) — کلید ذخیره نشد';
			}
			$row['key']      = $blob;
			$row['key_tail'] = substr( $plain, -4 );
		}
	}

	$saved[ $service ] = $row;
	update_option( TOPERSIA_SERVICES_OPTION, $saved, false );
	topersia_api_flush();

	return true;
}

/** «extra» فرم — فقط رشتۀ کوتاه و بولی، بی HTML. */
function topersia_services_clean_extra( $extra ) {

	$out = array();

	foreach ( (array) $extra as $k => $v ) {
		$k = sanitize_key( (string) $k );
		if ( is_array( $v ) ) {
			$out[ $k ] = topersia_services_clean_extra( $v );
		} elseif ( 'on' === $k ) {
			$out[ $k ] = (bool) $v;
		} else {
			$out[ $k ] = sanitize_text_field( (string) $v );
		}
	}

	return $out;
}

/* ══ مهاجرت — دکمۀ «انتقال» ═══════════════════════════════════════════ */

/**
 * آنچه از تنظیم قدیمی خوانده می‌شود، بی نوشتن. هر مصرف‌کننده با فیلتر
 * `topersia_services_legacy` تنظیم قدیمی خودش را می‌دهد (خلاصه‌ساز،
 * render). خروجی: سرویس ← سطر همان شکلِ فرم (کلید آشکار فقط درون همین درخواست).
 */
function topersia_services_legacy() {
	return (array) apply_filters( 'topersia_services_legacy', array() );
}

/**
 * انتقال یک‌باره: هر سرویسی که سطرش هنوز ذخیره نشده، از تنظیم قدیمی پر
 * می‌شود. سطر موجود دست نمی‌خورد؛ تنظیم قدیمی پاک نمی‌شود.
 *
 * @return array سرویس ← «منتقل شد» · «پیش‌تر در لایه بود» · خطا
 */
function topersia_services_migrate() {

	$saved  = topersia_services_saved();
	$report = array();

	foreach ( topersia_services_legacy() as $service => $in ) {

		if ( isset( $saved[ $service ] ) ) {
			$report[ $service ] = 'پیش‌تر در لایه بود — دست نخورد';
			continue;
		}

		$in['enabled'] = isset( $in['enabled'] ) ? $in['enabled'] : true;
		$r             = topersia_services_save_row( $service, $in );
		$report[ $service ] = true === $r ? 'منتقل شد' : $r;
		$saved          = topersia_services_saved();
	}

	update_option( 'topersia_services_migrated', array( 'at' => time(), 'report' => $report ), false );

	return $report;
}

/* ══ پیشخوان ═══════════════════════════════════════════════════════════ */

add_action( 'admin_menu', function () {
	add_submenu_page( 'topersia', 'سرویس‌های خارجی', 'سرویس‌های خارجی', 'manage_options', 'topersia-services', 'topersia_services_page' );
}, 20 );

/** ذخیره، انتقال و پرچم تجاری — پیش از چاپ صفحه. */
add_action( 'admin_init', function () {

	if ( ! isset( $_POST['topersia_services_action'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'topersia_services' );

	$action = sanitize_key( wp_unslash( $_POST['topersia_services_action'] ) );
	$notice = array();

	if ( 'save' === $action ) {
		update_option( TOPERSIA_COMMERCIAL_OPTION, ! empty( $_POST['site_is_commercial'] ), true );
		foreach ( (array) ( isset( $_POST['services'] ) ? wp_unslash( $_POST['services'] ) : array() ) as $service => $in ) {
			$r = topersia_services_save_row( (string) $service, (array) $in );
			if ( true !== $r ) {
				$notice[] = $service . ': ' . $r;
			}
		}
		topersia_api_flush();
		$notice[] = 'ذخیره شد';
	} elseif ( 'migrate' === $action ) {
		foreach ( topersia_services_migrate() as $service => $r ) {
			$notice[] = $service . ': ' . $r;
		}
	}

	set_transient( 'topersia_services_notice_' . get_current_user_id(), $notice, 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=topersia-services' ) );
	exit;
} );

/** آزمون اتصال — AJAX مدیر؛ پاسخ هرگز کلید ندارد. */
add_action( 'wp_ajax_topersia_service_test', function () {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'denied' ), 403 );
	}

	check_ajax_referer( 'topersia_service_test' );

	$service = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';
	$catalog = topersia_services_catalog();

	if ( ! isset( $catalog[ topersia_services_base( $service ) ] ) ) {
		wp_send_json_error( array( 'message' => 'سرویس ناشناخته' ), 400 );
	}

	wp_send_json_success( topersia_service_test( $service ) );
} );

/** هشدار مدیر: سرویسی که پرچم تجاری خاموشش کرده. */
add_action( 'admin_notices', function () {

	if ( ! current_user_can( 'manage_options' ) || ! topersia_site_is_commercial() ) {
		return;
	}

	$off = array();
	foreach ( array_keys( topersia_services_catalog() ) as $service ) {
		$cfg = topersia_api( $service );
		if ( $cfg['enabled'] && 'off' === $cfg['status'] && false !== strpos( $cfg['reason'], 'تجاری' ) ) {
			$off[] = $cfg['label'] . ' (' . $cfg['adapter'] . ')';
		}
	}

	if ( $off ) {
		printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( 'سایت تجاری علامت خورده؛ این سرویس‌ها بی کلید پولی خاموش‌اند: ' . implode( '، ', $off ) ) );
	}
} );

/** برچسب وضعیت. */
function topersia_services_status_label( $status ) {
	$map = array( 'active' => 'فعال', 'nokey' => 'بدون کلید', 'error' => 'خطا', 'off' => 'خاموش' );
	return isset( $map[ $status ] ) ? $map[ $status ] : $status;
}

/**
 * متن جداشده — ۲ج٫۱۱. مقدار لاتین (نشانی، شناسه، کلید پوشیده، تاریخ، عدد) در
 * `<bdi dir="ltr">`؛ عبارت فارسی — حتی اگر با واژۀ لاتین آغاز شود («endpoint ·
 * مدل»، «TTL کش») — در `<bdi dir="rtl">`، تا در پیشخوان چپ‌به‌راست هم ترتیب و
 * نشانه‌گذاری‌اش («»، پرانتز، دونقطه) سر جایش بماند. dir خودکار برای این دومی
 * از نخستین حرف لاتین جهت را چپ‌به‌راست می‌گرفت.
 *
 * @param string $text متن خام.
 * @param string $html HTMLِ گریخته‌ای که پس از متن درون همان جداساز می‌آید (مقدار یک برچسب).
 * @return string HTML گریخته
 */
function topersia_services_bdi( $text, $html = '' ) {
	$text = (string) $text;
	return '<bdi dir="' . ( preg_match( '/\p{Arabic}/u', $text ) ? 'rtl' : 'ltr' ) . '">' . esc_html( $text ) . $html . '</bdi>';
}

/** «برچسب: مقدار» — یک جداساز فارسی، مقدار درونش جدا. وگرنه دونقطه در پیشخوان چپ‌به‌راست به آن سو می‌رفت. */
function topersia_services_pair( $label, $value ) {
	return topersia_services_bdi( $label . ': ', '' === (string) $value ? '—' : topersia_services_bdi( $value ) );
}

/** پیش‌فرض TTL به زبان آدمی — عدد ثانیه است، ۰ دائمی، رشته قاعدۀ جدول ۲٫۳. */
function topersia_services_ttl_label( $ttl ) {
	if ( '' === $ttl || null === $ttl ) {
		return '';
	}
	if ( is_numeric( $ttl ) ) {
		return 0 === (int) $ttl ? 'دائمی' : human_time_diff( 0, (int) $ttl );
	}
	$map = array( 'expires' => 'سرآیند Expires پاسخ', 'hour' => 'تا پایان ساعت', 'hash' => 'تا تغییر محتوا' );
	return isset( $map[ $ttl ] ) ? $map[ $ttl ] : (string) $ttl;
}

/** صفحۀ «ToPersia ← سرویس‌های خارجی». */
function topersia_services_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = get_transient( 'topersia_services_notice_' . get_current_user_id() );
	delete_transient( 'topersia_services_notice_' . get_current_user_id() );

	$catalog  = topersia_services_catalog();
	$adapters = topersia_service_adapters();
	$saved    = topersia_services_saved();
	$legacy   = topersia_services_legacy();
	$pending  = array_diff_key( $legacy, $saved );

	/* جهت صفحه همان جهت پیشخوان — ۲ج٫۱۱. dir ثابت rtl پیشخوان انگلیسی را به‌هم می‌ریخت. */
	echo '<div class="wrap tp-admin tp-services-page"><h1>سرویس‌های خارجی</h1>';

	if ( function_exists( 'topersia_admin_css' ) ) {
		echo '<style>' . topersia_admin_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput -- شیوه‌نامۀ ثابت هسته
	}

	/*
	 * نُه ستون، بی اسکرول افقی از ۱۰۲۴ به بالا (۲ج٫۱۱): پهنای ستون درصدی، هر
	 * مقدار بلند می‌شکند (نشانی و مدل در textarea خودبلندشونده، ارائه‌دهنده رادیو
	 * نه select) — هیچ مقداری پشت اسکرول یا لبۀ فیلد پنهان نمی‌ماند. خانه‌ها
	 * وسطِ عمودی؛ ستون‌های کوتاه (روشن · وضعیت · TTL · آزمون) وسط‌چین و بقیه
	 * start. فقط ویژگی منطقی (inline/block)؛ هیچ left/right ثابت.
	 */
	echo '<style>'
		. '.tp-services-page.wrap{margin-block:10px 24px;margin-inline:20px}'
		. '.tp-services-scroll{overflow-x:auto;margin-block:0 12px}'
		. '.tp-services{table-layout:fixed;inline-size:100%;min-inline-size:760px}'
		. '.tp-services-page .tp-services :is(th,td){vertical-align:middle;text-align:start;padding-block:10px;padding-inline:5px;overflow-wrap:anywhere}'
		. '.tp-services-page .tp-services :is(th,td).tp-c-short{text-align:center}'
		. '.tp-services-page .tp-services :is(input[type=text],input[type=password],input[type=number],textarea){inline-size:100%;max-inline-size:100%;margin-block:0 6px;margin-inline:0}'
		. '.tp-services-page .tp-services textarea.tp-svc-grow{display:block;resize:none;overflow:hidden;line-height:1.5;min-block-size:0;padding-block:.3rem}'
		. '.tp-services-page .tp-services input[type=number]{padding-inline:4px;appearance:textfield;-moz-appearance:textfield}'
		. '.tp-services-page .tp-services input[type=number]::-webkit-inner-spin-button,.tp-services-page .tp-services input[type=number]::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}'
		. '.tp-services .description{font-size:12px;line-height:1.5}'
		. '.tp-services code{font-size:11px}'
		. '.tp-services .tp-svc-line{display:block}'
		. '.tp-services .tp-svc-num{display:block}.tp-services .tp-svc-num span{display:block;font-size:12px;line-height:1.4}'
		. '.tp-services fieldset.tp-svc-prov{margin:0;padding:0;border:0;min-inline-size:0}'
		. '.tp-services fieldset.tp-svc-prov label{display:block;line-height:1.5;margin-block:0 4px}'
		. '.tp-services :is(.tp-service-test,.tp-alt-add){white-space:normal;block-size:auto;min-block-size:30px;line-height:1.4;padding-block:4px;padding-inline:6px;max-inline-size:100%}'
		. '.tp-services col.c-service{width:13%}.tp-services col.c-on{width:7%}.tp-services col.c-provider{width:13%}.tp-services col.c-status{width:12%}.tp-services col.c-key{width:12%}'
		. '.tp-services col.c-endpoint{width:18%}.tp-services col.c-limits{width:8%}.tp-services col.c-ttl{width:9%}.tp-services col.c-test{width:8%}'
		. '.tp-services tr.tp-alt td{background:#f6f7f7}.tp-services tr.tp-alt[hidden]{display:none}'
		. '.tp-services .tp-alt-add{margin-block-start:6px}'
		. '</style>';

	if ( is_array( $notice ) && $notice ) {
		echo '<div class="notice notice-info"><p>' . implode( ' · ', array_map( 'topersia_services_bdi', $notice ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- topersia_services_bdi می‌گریزد
	}

	if ( ! topersia_services_can_encrypt() ) {
		echo '<div class="notice notice-error"><p>' . topersia_services_bdi( 'ext-sodium در دسترس نیست — هیچ کلیدی ذخیره نمی‌شود.' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	if ( $pending ) {
		echo '<form method="post" class="tp-card" style="margin-block:1em;padding:1em">';
		wp_nonce_field( 'topersia_services' );
		echo '<input type="hidden" name="topersia_services_action" value="migrate">';
		echo '<p><strong>تنظیم قدیمی پیدا شد:</strong> ' . topersia_services_bdi( implode( '، ', array_map( function ( $s ) use ( $catalog ) { // phpcs:ignore WordPress.Security.EscapeOutput
			return isset( $catalog[ $s ] ) ? $catalog[ $s ]['label'] : $s;
		}, array_keys( $pending ) ) ) . '. تا انتقال، همان تنظیم خوانده می‌شود. «انتقال» یک بار آن را به این لایه می‌آورد (کلید رمز می‌شود)؛ تنظیم قدیمی پاک نمی‌شود.' ) . '</p>';
		submit_button( 'انتقال', 'secondary', 'submit', false );
		echo '</form>';
	}

	echo '<form method="post">';
	wp_nonce_field( 'topersia_services' );
	echo '<input type="hidden" name="topersia_services_action" value="save">';

	printf(
		'<p><label><input type="checkbox" name="site_is_commercial" value="1"%s> %s</label></p>',
		checked( topersia_site_is_commercial(), true, false ),
		topersia_services_bdi( 'سایت تجاری است (اشتراک یا تبلیغ) — آداپتورهای با ردۀ رایگانِ غیرتجاری بی کلید پولی خاموش می‌شوند' ) // phpcs:ignore WordPress.Security.EscapeOutput
	);

	echo '<p class="description">' . topersia_services_bdi( 'کلید تولید متن هوش مصنوعی در تنظیمات خودِ خلاصه‌ساز است. «+ ارائه‌دهندۀ دوم» پشتیبان است: وقتی اولی خاموش است، کلید ندارد یا همین تازگی شکست خورد، دومی به کار می‌رود.' ) // phpcs:ignore WordPress.Security.EscapeOutput
		. ' ' . topersia_services_bdi( 'فیلد خالی یعنی پیش‌فرض — نوشتۀ کم‌رنگ درون فیلد، یا «پیش‌فرض» زیر آن.' ) . '</p>';

	echo '<div class="tp-services-scroll"><table class="widefat striped tp-services">'
		. '<colgroup><col class="c-service"><col class="c-on"><col class="c-provider"><col class="c-status"><col class="c-key"><col class="c-endpoint"><col class="c-limits"><col class="c-ttl"><col class="c-test"></colgroup>'
		. '<thead><tr><th scope="col">سرویس</th><th scope="col" class="tp-c-short">روشن</th><th scope="col">ارائه‌دهنده</th><th scope="col" class="tp-c-short">' . topersia_services_bdi( 'وضعیت · مصرف' ) . '</th>'
		. '<th scope="col">کلید</th><th scope="col">' . topersia_services_bdi( 'endpoint · مدل · نسخه' ) . '</th>'
		. '<th scope="col">' . topersia_services_bdi( 'سقف روزانه (کاربر · سایت)' ) . '</th><th scope="col" class="tp-c-short">' . topersia_services_bdi( 'TTL کش (ثانیه)' ) . '</th><th scope="col" class="tp-c-short">آزمون</th>'
		. '</tr></thead><tbody>';

	foreach ( $catalog as $service => $meta ) {
		$many = count( topersia_services_providers_for( $service, $adapters ) ) > 1;
		echo topersia_services_row_html( $service, $meta, $adapters, $many ); // phpcs:ignore WordPress.Security.EscapeOutput -- هر مقدار درون تابع گریخته
		if ( $many ) {
			echo topersia_services_row_html( $service . TOPERSIA_SERVICES_ALT, $meta, $adapters, false ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	echo '</tbody></table></div>';
	submit_button( 'ذخیره' );
	echo '</form></div>';

	/*
	 * آزمون اتصال — فقط شناسۀ سرویس فرستاده می‌شود؛ پاسخ پیام کوتاه است. «+» سطر دوم را نشان می‌دهد.
	 * textarea هم‌قد متنش (یا نگه‌دارش، وقتی خالی است) — هیچ نشانی پشت لبۀ فیلد نمی‌ماند؛ Enter مثل input فرم را می‌فرستد، خط تازه نمی‌سازد.
	 */
	$ajax  = admin_url( 'admin-ajax.php' );
	$nonce = wp_create_nonce( 'topersia_service_test' );
	echo '<script>(function(){'
		. 'function grow(t){var v=t.value;if(!v&&t.placeholder){t.value=t.placeholder}t.style.height="auto";t.style.height=(t.scrollHeight+t.offsetHeight-t.clientHeight)+"px";if(!v){t.value=""}}'
		. 'function growAll(r){r.querySelectorAll("textarea.tp-svc-grow").forEach(grow)}'
		. 'document.querySelectorAll("textarea.tp-svc-grow").forEach(function(t){t.addEventListener("input",function(){grow(t)});t.addEventListener("keydown",function(e){if("Enter"===e.key){e.preventDefault();if(t.form&&t.form.requestSubmit){t.form.requestSubmit()}}})});'
		. 'growAll(document);window.addEventListener("load",function(){growAll(document)});window.addEventListener("resize",function(){growAll(document)});'
		. 'document.querySelectorAll(".tp-service-test").forEach(function(b){b.addEventListener("click",function(){'
		. 'var out=b.nextElementSibling;out.textContent="…";var f=new FormData();f.append("action","topersia_service_test");f.append("_ajax_nonce",' . wp_json_encode( $nonce ) . ');f.append("service",b.getAttribute("data-service"));'
		. 'fetch(' . wp_json_encode( $ajax ) . ',{method:"POST",body:f,credentials:"same-origin"}).then(function(r){return r.json()}).then(function(j){var d=j&&j.data?j.data:{};out.textContent=(d.ok?"✓ ":(d.tested===false?"— ":"✗ "))+(d.message||"");}).catch(function(){out.textContent="✗ خطا";});});});'
		. 'document.querySelectorAll(".tp-alt-add").forEach(function(b){b.addEventListener("click",function(){var r=document.querySelector("tr[data-service=\""+b.getAttribute("data-tp-alt")+"\"]");if(!r){return}r.hidden=false;growAll(r);var u=r.querySelector("[data-tp-alt-use]");if(u){u.value="1"}b.hidden=true;var f=r.querySelector("input:not([type=hidden]),textarea");if(f){f.focus()}});});})();</script>';
}

/**
 * یک سطر جدول — ارائه‌دهندۀ اول، یا شکاف دوم (پشتیبان) همان سرویس.
 *
 * @param string $service  شناسه؛ شکاف دوم با TOPERSIA_SERVICES_ALT.
 * @param array  $meta     سطر فهرست سرویس.
 * @param array  $adapters همۀ آداپتورها.
 * @param bool   $can_add  دکمۀ «+» (فقط سطر اول، وقتی سرویس بیش از یک آداپتور دارد).
 * @return string HTML گریخته
 */
function topersia_services_row_html( $service, array $meta, array $adapters, $can_add ) {

	$bsvc  = topersia_services_base( $service );
	$alt   = $bsvc !== $service;
	$cfg   = topersia_api( $service, false, true );
	$saved = topersia_services_saved();
	$row   = isset( $saved[ $service ] ) && is_array( $saved[ $service ] ) ? $saved[ $service ] : array();
	$name  = 'services[' . $service . ']';
	$usage = topersia_api_usage( $service );
	$ttl_d = topersia_cache_ttl_defaults();
	$ttl_d = isset( $ttl_d[ $bsvc ] ) ? $ttl_d[ $bsvc ] : '';
	$ad    = isset( $adapters[ $cfg['provider'] ] ) ? $adapters[ $cfg['provider'] ] : array();
	$label = $alt ? 'ارائه‌دهندۀ دوم ' . $meta['label'] : $meta['label'];
	$hide  = $alt && ! $row;
	$h     = '';

	$h .= '<tr data-service="' . esc_attr( $service ) . '"' . ( $alt ? ' class="tp-alt"' : '' ) . ( $hide ? ' hidden' : '' ) . '>';

	/* سرویس */
	if ( $alt ) {
		$h .= '<td><strong>ارائه‌دهندۀ دوم</strong><span class="tp-svc-line description">' . topersia_services_bdi( 'پشتیبان «' . $meta['label'] . '»' ) . '</span>'
			. '<input type="hidden" name="' . esc_attr( $name ) . '[use]" value="' . ( $row ? '1' : '0' ) . '" data-tp-alt-use>'
			. ( $row ? '<label class="tp-svc-line"><input type="checkbox" name="' . esc_attr( $name ) . '[remove]" value="1"> حذف</label>' : '' ) . '</td>';
	} else {
		$h .= '<td><strong>' . topersia_services_bdi( $meta['label'] ) . '</strong><span class="tp-svc-line"><code><bdi dir="ltr">' . str_replace( array( '_', '.' ), array( '_<wbr>', '.<wbr>' ), esc_html( $service ) ) . '</bdi></code></span>'
			. '<span class="tp-svc-line description">' . topersia_services_bdi( $meta['purpose'] ) . '</span>'
			. ( $can_add && ! isset( $saved[ $service . TOPERSIA_SERVICES_ALT ] ) ? '<button type="button" class="button button-small tp-alt-add" data-tp-alt="' . esc_attr( $service . TOPERSIA_SERVICES_ALT ) . '">' . topersia_services_bdi( '+ ارائه‌دهندۀ دوم' ) . '</button>' : '' ) . '</td>';
	}

	/* روشن */
	$h .= '<td class="tp-c-short"><label><input type="checkbox" name="' . esc_attr( $name ) . '[enabled]" value="1"' . checked( $cfg['enabled'], true, false ) . '><span class="screen-reader-text">' . esc_html( 'روشن — ' . $label ) . '</span></label></td>';

	/* ارائه‌دهنده — رادیو، نه select: نام بلند در ستون باریک بریده نمی‌شود */
	$ids = topersia_services_providers_for( $bsvc, $adapters );
	$h  .= '<td>';
	if ( count( $ids ) > 1 ) {
		$h .= '<fieldset class="tp-svc-prov"><legend class="screen-reader-text">' . esc_html( 'ارائه‌دهنده — ' . $label ) . '</legend>';
		foreach ( $ids as $id ) {
			$h .= '<label><input type="radio" name="' . esc_attr( $name ) . '[provider]" value="' . esc_attr( $id ) . '"' . checked( $cfg['provider'], $id, false ) . '> ' . topersia_services_bdi( $adapters[ $id ]['label'] ) . '</label>';
		}
		$h .= '</fieldset>';
	} elseif ( $ids ) {
		$h .= '<input type="hidden" name="' . esc_attr( $name ) . '[provider]" value="' . esc_attr( $ids[0] ) . '">' . topersia_services_bdi( $adapters[ $ids[0] ]['label'] );
	}
	if ( ! empty( $ad['doc'] ) ) {
		$doc  = (string) $ad['doc'];
		$host = 0 === strpos( $doc, 'https://' ) ? (string) wp_parse_url( $doc, PHP_URL_HOST ) : '';
		$h   .= '<span class="tp-svc-line description">' . ( '' !== $host
			? topersia_services_bdi( 'مستند: ', '<a href="' . esc_url( $doc ) . '" title="' . esc_attr( $doc ) . '" target="_blank" rel="noopener noreferrer">' . topersia_services_bdi( $host ) . '</a>' )
			: topersia_services_bdi( $doc ) ) . '</span>';
	}
	$h .= '</td>';

	/* وضعیت · مصرف */
	$last  = ! empty( $usage['last_ok_at'] ) ? wp_date( 'Y-m-d H:i', (int) $usage['last_ok_at'] ) : '';
	$today = isset( $usage['day'], $usage['count'] ) && current_time( 'Y-m-d' ) === $usage['day'] ? (int) $usage['count'] : 0;
	$h    .= '<td class="tp-c-short"><strong>' . esc_html( topersia_services_status_label( $cfg['status'] ) ) . '</strong>'
		. ( '' !== $cfg['reason'] && 'active' !== $cfg['status'] && $cfg['reason'] !== topersia_services_status_label( $cfg['status'] ) ? '<span class="tp-svc-line description">' . topersia_services_bdi( $cfg['reason'] ) . '</span>' : '' )
		. '<span class="tp-svc-line description">' . topersia_services_pair( 'آخرین موفق', $last ) . '</span>'
		. '<span class="tp-svc-line description">' . topersia_services_pair( 'امروز', (string) $today ) . '</span></td>';

	/* کلید — فقط‌نوشتنی؛ آشکار فقط «••••» و چهار نویسۀ آخر */
	$h .= '<td>';
	if ( 'config' === $cfg['key_source'] ) {
		$h .= topersia_services_bdi( 'از ثابت wp-config — قفل' ) . '<span class="tp-svc-line">' . topersia_services_bdi( '••••' . $cfg['key_tail'] ) . '</span>';
	} elseif ( ! empty( $cfg['needs_key'] ) || '' !== $cfg['key_tail'] ) {
		$h .= ( '' !== $cfg['key_tail'] ? '<span class="tp-svc-line">' . topersia_services_bdi( '••••' . $cfg['key_tail'] ) . '</span>' : '' )
			. '<input type="password" dir="ltr" name="' . esc_attr( $name ) . '[key]" value="" autocomplete="new-password" placeholder="' . esc_attr( '' !== $cfg['key_tail'] ? 'کلید تازه' : 'کلید' ) . '" aria-label="' . esc_attr( 'کلید — ' . $label ) . '">'
			. ( '' !== $cfg['key_tail'] ? '<label class="tp-svc-line"><input type="checkbox" name="' . esc_attr( $name ) . '[key_clear]" value="1"> حذف کلید</label>' : '' );
	} else {
		$h .= '<span class="description">کلید لازم نیست</span>';
	}
	$h .= '</td>';

	/* endpoint · مدل · نسخه — خالی یعنی پیش‌فرض آداپتور (نگه‌دار). extra بی فیلد: فقط خواندنی، تا پیدا باشد. */
	$h .= '<td><textarea class="tp-svc-grow" rows="1" dir="ltr" name="' . esc_attr( $name ) . '[endpoint]" placeholder="' . esc_attr( ! empty( $ad['endpoint'] ) ? $ad['endpoint'] : 'endpoint' ) . '" aria-label="' . esc_attr( 'endpoint — ' . $label ) . '">' . esc_textarea( $cfg['configured'] && ! empty( $row['endpoint'] ) ? $cfg['endpoint'] : '' ) . '</textarea>'
		. '<textarea class="tp-svc-grow" rows="1" dir="ltr" name="' . esc_attr( $name ) . '[model]" placeholder="' . esc_attr( isset( $ad['model'] ) && '' !== $ad['model'] ? $ad['model'] : 'مدل' ) . '" aria-label="' . esc_attr( 'مدل — ' . $label ) . '">' . esc_textarea( $cfg['configured'] && ! empty( $row['model'] ) ? $cfg['model'] : '' ) . '</textarea>'
		. '<input type="text" dir="ltr" name="' . esc_attr( $name ) . '[version]" value="' . esc_attr( $cfg['configured'] && ! empty( $row['version'] ) ? $cfg['version'] : '' ) . '" placeholder="نسخه" aria-label="' . esc_attr( 'نسخه — ' . $label ) . '">';
	foreach ( $cfg['fields'] as $k => $flabel ) {
		$h .= '<textarea class="tp-svc-grow" rows="1" dir="ltr" name="' . esc_attr( $name ) . '[extra][' . esc_attr( $k ) . ']" placeholder="' . esc_attr( $flabel ) . '" aria-label="' . esc_attr( $flabel ) . '">' . esc_textarea( isset( $cfg['extra'][ $k ] ) && is_scalar( $cfg['extra'][ $k ] ) ? (string) $cfg['extra'][ $k ] : '' ) . '</textarea>';
	}
	foreach ( $cfg['extra'] as $k => $v ) {
		if ( isset( $cfg['fields'][ $k ] ) ) {
			continue;
		}
		if ( is_scalar( $v ) && '' !== (string) $v ) {
			$h .= '<span class="tp-svc-line description">' . topersia_services_bdi( $k . ': ' . $v ) . '</span>';
		} elseif ( is_array( $v ) && isset( $v['on'] ) ) {
			$h .= '<span class="tp-svc-line description">' . topersia_services_bdi( (string) $k ) . ' ' . ( $v['on'] ? 'روشن' : 'خاموش' ) . '</span>';
		}
	}
	$h .= '</td>';

	/* سقف روزانه */
	$h .= '<td><label class="tp-svc-num"><span>کاربر</span><input type="number" min="0" name="' . esc_attr( $name ) . '[limit_user]" value="' . esc_attr( null === $cfg['limits']['user'] ? '' : $cfg['limits']['user'] ) . '" placeholder="—"></label>'
		. '<label class="tp-svc-num"><span>سایت</span><input type="number" min="0" name="' . esc_attr( $name ) . '[limit_site]" value="' . esc_attr( null === $cfg['limits']['site'] ? '' : $cfg['limits']['site'] ) . '" placeholder="—"></label></td>';

	/* TTL — پیش‌فرض جدول ۲٫۳ نوشته می‌شود، نه فقط نگه‌دار بریده‌شده */
	$ttl_l = topersia_services_ttl_label( $ttl_d );
	$h    .= '<td class="tp-c-short"><input type="number" min="0" name="' . esc_attr( $name ) . '[ttl]" value="' . esc_attr( $cfg['ttl'] ) . '" placeholder="—" aria-label="' . esc_attr( 'TTL کش (ثانیه) — ' . $label ) . '">'
		. ( '' !== $ttl_l ? '<span class="tp-svc-line description">' . topersia_services_pair( 'پیش‌فرض', $ttl_l ) . '</span>' : '' ) . '</td>';

	/* آزمون */
	$h .= '<td class="tp-c-short"><button type="button" class="button tp-service-test" data-service="' . esc_attr( $service ) . '">آزمون اتصال</button><div class="tp-service-result" aria-live="polite" dir="auto"></div></td>';

	return $h . '</tr>';
}
