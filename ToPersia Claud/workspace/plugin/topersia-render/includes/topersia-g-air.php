<?php
/**
 * کیفیت هوا — جعبه‌ای زیر کارت آب‌وهوا.  P5-D345 · P5-D346 (بخشی از فاز ۳ پیش از ۲د)
 *
 * منبع: Open-Meteo Air Quality (مدل CAMS) — endpoint و پارامترها فقط از مستند رسمی (قاعدۀ ۱۱):
 *   https://air-quality-api.open-meteo.com/v1/air-quality ?latitude&longitude &current=us_aqi,pm10,pm2_5,dust
 *   پاسخ: `current` = { time, interval (۳۶۰۰ ثانیه)، us_aqi، pm10، pm2_5، dust } — µg/m³ برای سه ذره.
 * استفادۀ رایگان فقط غیرتجاری است و انتساب (CAMS و Open-Meteo) الزامی: با `site_is_commercial` روشن و بی کلید پولی
 * ← سرویس خاموش و جعبه نمی‌آید (همان منطق `topersia_api()` هسته).
 *
 * سمت سرور، با کش مرکزی (P5-D250): کلید سلول ۰٫۲۵ درجه، تا پایان ساعت جاری؛ دادۀ کهنه به‌جای خطا.
 * P5-D381: «برآورد مدل CAMS · میانگین ۲۴ ساعت» — us_aqi خودِ Open-Meteo برای ذرات میانگین غلتان ۲۴ ساعت گذشته است (مستند رسمی:
 * «Particulate Matter (PM) uses a rolling average over the preceding 24 hours»)؛ مقدار pm2_5/pm10/dust در `current` لحظه‌ای است، پس سه
 * عدد ذره از میانگین ۲۴ مقدار ساعتیِ گذشته (`hourly` با `past_days=1`) ساخته می‌شود تا با برچسب بخواند. بی اعشار. نقطه: نام همان
 * نقطه («برای شهر …»؛ استان ← مرکز استان). ساعت به‌روزرسانی به وقت ایران. صافی `topersia_g_air_enabled` جعبه را برای نوع یا موجودیت خاموش می‌کند.
 * مختصات از تابع مرکزی `topersia_geo_point()` (۲ه٫۵). دادۀ زنده است و در HTML کش‌شده نمی‌آید: اسکلتِ هم‌اندازه
 * در HTML و پر شدن پس از بارگذاری از REST (CLS صفر)؛ خطا ← «—».
 *
 * @package ToPersia_Render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TP_AQ_ENDPOINT' ) ) {
	define( 'TP_AQ_ENDPOINT', 'https://air-quality-api.open-meteo.com/v1/air-quality' );
}

/** رده‌ٔ US AQI (۱ تا ۶) از شاخص — حدّ ردۀ EPA: ۵۰ · ۱۰۰ · ۱۵۰ · ۲۰۰ · ۳۰۰. */
if ( ! function_exists( 'topersia_g_aqi_cat' ) ) {
	function topersia_g_aqi_cat( $aqi ) {

		$aqi = (float) $aqi;

		foreach ( array( 50 => 1, 100 => 2, 150 => 3, 200 => 4, 300 => 5 ) as $max => $cat ) {
			if ( $aqi <= $max ) {
				return $cat;
			}
		}

		return 6;
	}
}

/** برچسب هر رده به زبان صفحه — از کاتالوگ افزونه. */
if ( ! function_exists( 'topersia_g_aqi_label' ) ) {
	function topersia_g_aqi_label( $cat ) {

		$map = array(
			1 => __( 'خوب', 'topersia' ),
			2 => __( 'متوسط', 'topersia' ),
			3 => __( 'ناسالم برای گروه‌های حساس', 'topersia' ),
			4 => __( 'ناسالم', 'topersia' ),
			5 => __( 'بسیار ناسالم', 'topersia' ),
			6 => __( 'خطرناک', 'topersia' ),
		);

		return isset( $map[ (int) $cat ] ) ? $map[ (int) $cat ] : $map[6];
	}
}

/** سلول ۰٫۲۵ درجه — کلید کش و مختصات درخواست. */
if ( ! function_exists( 'topersia_g_aq_cell' ) ) {
	function topersia_g_aq_cell( $lat, $lng ) {
		return array( number_format( round( (float) $lat * 4 ) / 4, 2, '.', '' ), number_format( round( (float) $lng * 4 ) / 4, 2, '.', '' ) );
	}
}

/** آیا سرویس کیفیت هوا برای این سایت روشن و مجاز است (خاموشی مدیر، پرچم تجاری بی کلید پولی). */
if ( ! function_exists( 'topersia_g_aq_enabled' ) ) {
	function topersia_g_aq_enabled() {

		if ( ! function_exists( 'topersia_api' ) ) {
			return false;
		}

		$cfg = topersia_api( 'air_quality' );

		/* «error» (آخرین فراخوانی ناموفق) خاموشی نیست: وگرنه پس از یک قطعی هرگز دوباره آزموده نمی‌شد؛ «off» (خاموش یا تجاری بی کلید پولی) و «nokey» خاموشی‌اند */
		return ! empty( $cfg ) && in_array( isset( $cfg['status'] ) ? $cfg['status'] : '', array( 'active', 'error' ), true ) && ! empty( $cfg['enabled'] );
	}
}

/**
 * یک دریافت از Open-Meteo Air Quality برای یک سلول.
 *
 * @return array|WP_Error  value: aqi · pm25 · pm10 · dust · time
 */
if ( ! function_exists( 'topersia_g_aq_fetch' ) ) {
	function topersia_g_aq_fetch( $cell ) {

		$args = array(
			'latitude'  => $cell[0],
			'longitude' => $cell[1],
			'current'       => 'us_aqi,pm10,pm2_5,dust',
			'hourly'        => 'pm10,pm2_5,dust',
			'past_days'     => 1,
			'forecast_days' => 1,
			'timezone'      => 'GMT',
		);

		/* ردۀ پولی (تجاری): مستند رسمی فقط پارامتر apikey و میزبان customer- می‌شناسد؛ کلید فقط سمت سرور از هسته */
		$cfg = function_exists( 'topersia_api' ) ? topersia_api( 'air_quality' ) : array();
		$url = TP_AQ_ENDPOINT;

		if ( ! empty( $cfg['key'] ) ) {
			$url             = str_replace( 'https://air-quality-api.', 'https://customer-air-quality-api.', TP_AQ_ENDPOINT );
			$args['apikey']  = $cfg['key'];
		}

		$response = wp_remote_get( add_query_arg( $args, $url ), array( 'timeout' => 6, 'headers' => array( 'Accept' => 'application/json' ) ) );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'tp_aq_upstream', __( 'سرویس کیفیت هوا در دسترس نیست.', 'topersia' ), array( 'status' => 503 ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$cur  = is_array( $body ) && isset( $body['current'] ) && is_array( $body['current'] ) ? $body['current'] : array();

		if ( ! isset( $cur['us_aqi'] ) || ! is_numeric( $cur['us_aqi'] ) ) {
			return new WP_Error( 'tp_aq_shape', __( 'پاسخ سرویس کیفیت هوا شکل مورد انتظار را ندارد.', 'topersia' ), array( 'status' => 502 ) );
		}

		$hourly = is_array( $body ) && isset( $body['hourly'] ) && is_array( $body['hourly'] ) ? $body['hourly'] : array();
		$now    = isset( $cur['time'] ) ? (string) $cur['time'] : '';

		/* میانگین ۲۴ ساعت تا ساعت جاری (P5-D381)؛ بی دادۀ ساعتی ← همان مقدار لحظه‌ای */
		$mean = function ( $k ) use ( $cur, $hourly, $now ) {
			return topersia_g_aq_mean24( isset( $hourly['time'] ) ? (array) $hourly['time'] : array(), isset( $hourly[ $k ] ) ? (array) $hourly[ $k ] : array(), $now,
				isset( $cur[ $k ] ) && is_numeric( $cur[ $k ] ) ? (float) $cur[ $k ] : null );
		};

		return array(
			'aqi'  => (int) round( (float) $cur['us_aqi'] ),
			'pm25' => $mean( 'pm2_5' ),
			'pm10' => $mean( 'pm10' ),
			'dust' => $mean( 'dust' ),
			'time' => $now,
			'avg'  => 24,
		);
	}
}

/**
 * میانگین ۲۴ مقدار ساعتیِ پایان‌یافته تا `$now` (زمان‌ها ISO به GMT) — خالص، برای آزمون. بی اعشار.  P5-D381
 *
 * @return int|null
 */
if ( ! function_exists( 'topersia_g_aq_mean24' ) ) {
	function topersia_g_aq_mean24( array $times, array $values, $now, $fallback = null ) {

		$take = array();

		foreach ( $times as $i => $t ) {
			if ( '' !== (string) $now && strcmp( (string) $t, (string) $now ) > 0 ) {
				continue;
			}
			if ( isset( $values[ $i ] ) && is_numeric( $values[ $i ] ) ) {
				$take[] = (float) $values[ $i ];
			}
		}

		$take = array_slice( $take, -24 );

		if ( count( $take ) >= 12 ) {
			return (int) round( array_sum( $take ) / count( $take ) );
		}

		return null === $fallback ? null : (int) round( (float) $fallback );
	}
}

/** ساعت به‌روزرسانی به وقت ایران («۱۴:۰۰») از زمان GMT پاسخ، رقم به زبان صفحه.  P5-D381 */
if ( ! function_exists( 'topersia_g_aq_time_ir' ) ) {
	function topersia_g_aq_time_ir( $gmt ) {

		$gmt = trim( (string) $gmt );

		if ( '' === $gmt ) {
			return '';
		}

		try {
			$d = new DateTimeImmutable( $gmt, new DateTimeZone( 'UTC' ) );
		} catch ( Exception $e ) {
			return '';
		}

		return topersia_g_fa( $d->setTimezone( new DateTimeZone( 'Asia/Tehran' ) )->format( 'H:i' ) );
	}
}

/** کش مرکزی: کلید سلول، تا پایان ساعت؛ بی هسته همان دریافت بی کش. */
if ( ! function_exists( 'topersia_g_aq_get' ) ) {
	function topersia_g_aq_get( $lat, $lng ) {

		$cell = topersia_g_aq_cell( $lat, $lng );

		if ( function_exists( 'topersia_cache_remember' ) ) {
			return topersia_cache_remember(
				'air_quality',
				array( $cell[0], $cell[1], 'avg24' ),   /* شکل پاسخ عوض شد (P5-D381) ← کلید تازه */
				function () use ( $cell ) {
					$out = topersia_g_aq_fetch( $cell );
					return is_wp_error( $out ) ? $out : array( 'value' => $out, 'ttl' => 'hour' );   /* تا پایان ساعت جاری — قاعدۀ مرکزی کش */
				},
				array( 'provider' => 'open-meteo-aq', 'lang' => 'fa', 'keep' => 7200, 'stale_on_error' => true )
			);
		}

		return topersia_g_aq_fetch( $cell );
	}
}

/**
 * پاسخ REST — `topersia/v1/air?lat&lng`.
 *
 * پاسخ: aqi · cat (۱–۶) · label (زبان نشانی) · pm25 · pm10 · dust · time · model · src.
 */
if ( ! function_exists( 'topersia_g_aq_rest' ) ) {
	function topersia_g_aq_rest( $request ) {

		$lat   = $request->get_param( 'lat' );
		$lng   = $request->get_param( 'lng' );
		$point = ( is_numeric( $lat ) && is_numeric( $lng ) && function_exists( 'topersia_geo_point' ) )
			? topersia_geo_point( $lat . ', ' . $lng ) : null;

		if ( ! $point ) {
			return new WP_Error( 'tp_aq_coords', __( 'مختصات نامعتبر است.', 'topersia' ), array( 'status' => 400 ) );
		}

		if ( ! topersia_g_aq_enabled() ) {
			return new WP_Error( 'tp_aq_off', __( 'سرویس کیفیت هوا خاموش است.', 'topersia' ), array( 'status' => 503 ) );
		}

		$out = topersia_g_aq_get( $point['lat'], $point['lng'] );

		if ( is_wp_error( $out ) ) {
			return $out;
		}

		$cat = topersia_g_aqi_cat( $out['aqi'] );

		return rest_ensure_response( array(
			'aqi'   => (int) $out['aqi'],
			'cat'   => $cat,
			'label' => topersia_g_aqi_label( $cat ),
			'pm25'  => $out['pm25'],
			'pm10'  => $out['pm10'],
			'dust'  => $out['dust'],
			'time'  => $out['time'],
			/* translators: %s: ساعت به وقت ایران */
			'updated' => '' !== topersia_g_aq_time_ir( $out['time'] ) ? sprintf( __( 'به‌روزرسانی %s به وقت ایران', 'topersia' ), topersia_g_aq_time_ir( $out['time'] ) ) : '',
			'model' => __( 'برآورد مدل CAMS · میانگین ۲۴ ساعت', 'topersia' ),
		) );
	}
}

/** انتسابِ زیر جعبه: داده‌های هوای کارت آب‌وهوا + کیفیت هوا. */
if ( ! function_exists( 'topersia_g_aq_credit' ) ) {
	function topersia_g_aq_credit() {

		$wx = TP_Wx_Open_Meteo::NAME === topersia_g_wx_provider() ? 'Open-Meteo' : TP_Wx_Met_Norway::CREDIT;

		return sprintf(
			/* translators: %s: نام ارائه‌دهندۀ آب‌وهوا */
			__( 'داده‌های هوا: %s · کیفیت هوا: CAMS، Open-Meteo', 'topersia' ),
			$wx
		);
	}
}

/**
 * نقطۀ اندازه‌گیری کیفیت هوا برای یک پست — P5-D461 (دستۀ 39).
 *
 * ۱) خودِ پست اگر مختصات دارد (استان ← مرکز استان، رابطۀ ۹۶ — همان رفتار پیشین)؛
 * ۲) وگرنه نزدیک‌ترین والد مکانی که مختصات دارد: شهر ← استان ← کشور (`topersia_g_place_chain`، از نزدیک به دور).
 * منبع همچنان یکی است (Open-Meteo/CAMS، P5-D251)؛ والد فقط «برای چه جایی» را می‌گوید، نه منبع داده.
 *
 * @return array{at:int,owner:int,coords:string,fallback:bool}|array{} at = پستی که مختصاتش خوانده شد؛ owner = پستی که برچسب «برای …» می‌گیرد.
 */
if ( ! function_exists( 'topersia_g_air_at' ) ) {
	function topersia_g_air_at( $pid ) {

		$pid   = absint( $pid );
		$chain = function_exists( 'topersia_g_place_chain' ) ? array_reverse( array_map( 'absint', (array) topersia_g_place_chain( $pid ) ) ) : array();

		foreach ( array_merge( array( $pid ), $chain ) as $n => $id ) {

			if ( ! $id ) {
				continue;
			}

			$at = $id;

			if ( 'tp_province' === get_post_type( $id ) && function_exists( 'topersia_g_capital_id' ) && topersia_g_capital_id( $id ) ) {
				$at = (int) topersia_g_view_id( topersia_g_capital_id( $id ) );
			}

			$coords = topersia_g_text( $at, 'geo_coordinates' );
			$coords = function_exists( 'topersia_geo_attr' ) ? topersia_geo_attr( $coords ) : $coords;

			if ( '' !== $coords ) {
				return array( 'at' => $at, 'owner' => $n ? $id : $at, 'coords' => $coords, 'fallback' => $n > 0 );
			}
		}

		return array();
	}
}
/**
 * [tp_air] — جعبۀ کیفیت هوا زیر کارت آب‌وهوا، برای هر نوعی که کارت هوا دارد (کشور ندارد). اسکلتِ هم‌اندازه؛ بی عدد.
 */
if ( ! function_exists( 'topersia_g_sc_air' ) ) {
	function topersia_g_sc_air( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid || ! topersia_g_zone_on( $pid, 'tp-weather' ) || ! topersia_g_aq_enabled() ) {
			return '';
		}

		$type = (string) get_post_type( $pid );

		/* صافی خاموشی جعبه برای نوع یا موجودیت — P5-D381 */
		if ( function_exists( 'apply_filters' ) && ! apply_filters( 'topersia_g_air_enabled', true, $pid, $type ) ) {
			return '';
		}

		/* نقطه: استان ← مرکز استان (رابطۀ ۹۶)؛ هر نوع دیگر ← مختصات خودش؛ بی داده ← نزدیک‌ترین والد (P5-D461). نام کنار عدد — P5-D381 */
		$pt = topersia_g_air_at( $pid );

		if ( ! $pt ) {
			return '';
		}

		$at     = $pt['owner'];
		$coords = $pt['coords'];

		/* نامی که خودش نوع را دارد («شهر اصفهان») دوباره پیشوند نمی‌گیرد؛ والدِ جایگزین «استان/کشور {نام}» */
		$at_name = topersia_g_name( $at );
		$kind    = (string) topersia_g_kind_label( $at );
		/* translators: %s: نام مکان */
		$for = sprintf( __( 'برای %s', 'topersia' ), $at_name );

		if ( ! $pt['fallback'] && 'tp_city' === get_post_type( $at ) && 0 !== strpos( $at_name, $kind ) ) {
			/* translators: %s: نام شهر */
			$for = sprintf( __( 'برای شهر %s', 'topersia' ), $at_name );
		} elseif ( $pt['fallback'] && '' !== $kind && 0 !== strpos( $at_name, $kind ) ) {
			/* translators: 1: نوع (استان/کشور) 2: نام والد */
			$for = sprintf( __( 'برای %1$s %2$s', 'topersia' ), $kind, $at_name );
		}

		$body = '<div class="tp-air is-load" data-tp-air data-tp-coords="' . esc_attr( $coords ) . '" aria-live="polite">'
			. '<div class="tp-air__main"><b class="tp-air__idx" data-tp-air-idx>—</b>'
			. '<span class="tp-air__unit"><span dir="ltr">US AQI</span></span>'
			. '<span class="tp-air__cat" data-tp-air-cat>—</span></div>'
			. '<dl class="tp-air__list">'
			. '<div><dt><span dir="ltr">PM2.5</span></dt><dd data-tp-air-pm25>—</dd></div>'
			. '<div><dt><span dir="ltr">PM10</span></dt><dd data-tp-air-pm10>—</dd></div>'
			. '<div><dt>' . esc_html__( 'غبار', 'topersia' ) . '</dt><dd data-tp-air-dust>—</dd></div>'
			. '</dl></div>'
			. '<p class="tp-wx__src tp-air__model">' . esc_html__( 'برآورد مدل CAMS · میانگین ۲۴ ساعت', 'topersia' )
			. ' · <span data-tp-air-for>' . esc_html( $for ) . '</span>'
			. ' · <span data-tp-air-time>—</span></p>'
			. '<p class="tp-wx__src tp-wx__credit">' . esc_html( topersia_g_aq_credit() ) . '</p>';

		return topersia_g_section( 'tp-aqi', __( 'کیفیت هوا', 'topersia' ), 'AIR', $body );
	}
}

if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'tp_air', 'topersia_g_sc_air' );
}

/**
 * برگۀ تشخیصی کیفیت هوا در پیشخوان — فقط مدیر (`manage_options`).  P5-D381
 *
 * AQI، رده و PM2.5 (میانگین ۲۴ ساعت) و ساعت به وقت ایران برای ده شهر، از همان مسیر سرور (کش مرکزی، سلول ۰٫۲۵ درجه).
 * مختصات مرکز شهرها ثابت (برای سنجش مدل، نه دادۀ صفحه). فقط خواندن؛ هیچ تنظیمی نمی‌نویسد.
 */
if ( ! function_exists( 'topersia_g_aq_diag_cities' ) ) {
	function topersia_g_aq_diag_cities() {
		/*
		 * نام شهرها نام موجودیت است، نه رشتۀ رابط — در کاتالوگ نمی‌آیند (دستۀ ۲۸: msgid «اصفهان» با نام فارسی
		 * داده در صفحۀ /en/ و /ar/ یکی می‌شد و آزمون «بی رشتۀ رابط فارسی» می‌افتاد). سه نام کنار مختصات.
		 */
		return array(
			'تهران'    => array( 35.6892, 51.3890, 'Tehran', 'طهران' ),   /* i18n:skip */
			'مشهد'     => array( 36.2605, 59.6168, 'Mashhad', 'مشهد' ),   /* i18n:skip */
			'اصفهان'   => array( 32.6539, 51.6660, 'Isfahan', 'أصفهان' ),   /* i18n:skip */
			'شیراز'    => array( 29.5918, 52.5837, 'Shiraz', 'شيراز' ),   /* i18n:skip */
			'تبریز'    => array( 38.0800, 46.2919, 'Tabriz', 'تبريز' ),   /* i18n:skip */
			'اهواز'    => array( 31.3183, 48.6706, 'Ahvaz', 'الأهواز' ),   /* i18n:skip */
			'کرج'      => array( 35.8400, 50.9391, 'Karaj', 'كرج' ),   /* i18n:skip */
			'قم'       => array( 34.6399, 50.8759, 'Qom', 'قم' ),   /* i18n:skip */
			'کرمانشاه' => array( 34.3142, 47.0650, 'Kermanshah', 'كرمانشاه' ),   /* i18n:skip */
			'زاهدان'   => array( 29.4963, 60.8629, 'Zahedan', 'زاهدان' ),   /* i18n:skip */
		);
	}
}
if ( ! function_exists( 'topersia_g_aq_diag_page' ) ) {
	function topersia_g_aq_diag_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی ندارید.', 'topersia' ), 403 );
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'کیفیت هوا — تشخیص', 'topersia' ) . '</h1>';

		if ( ! topersia_g_aq_enabled() ) {
			echo '<p>' . esc_html__( 'سرویس کیفیت هوا خاموش است.', 'topersia' ) . '</p></div>';
			return;
		}

		echo '<p>' . esc_html__( 'برآورد مدل CAMS · میانگین ۲۴ ساعت — همان دادۀ جعبۀ صفحه، از کش مرکزی.', 'topersia' ) . '</p>';
		echo '<table class="widefat striped" style="max-inline-size:760px"><thead><tr><th>' . esc_html__( 'شهر', 'topersia' ) . '</th><th>US AQI</th><th>'
			. esc_html__( 'رده', 'topersia' ) . '</th><th>PM2.5 (µg/m³)</th><th>' . esc_html__( 'ساعت (وقت ایران)', 'topersia' ) . '</th></tr></thead><tbody>';

		$loc = function_exists( 'get_user_locale' ) ? substr( (string) get_user_locale(), 0, 2 ) : 'fa';

		foreach ( topersia_g_aq_diag_cities() as $city => $ll ) {

			$city = 'fa' === $loc ? $city : ( 'ar' === $loc ? $ll[3] : $ll[2] );

			$out = topersia_g_aq_get( $ll[0], $ll[1] );

			if ( is_wp_error( $out ) || ! is_array( $out ) ) {
				echo '<tr><td>' . esc_html( $city ) . '</td><td colspan="4">' . esc_html( is_wp_error( $out ) ? $out->get_error_message() : '—' ) . '</td></tr>';
				continue;
			}

			$cat = topersia_g_aqi_cat( $out['aqi'] );

			echo '<tr><td>' . esc_html( $city ) . '</td><td>' . esc_html( topersia_g_fa( (string) (int) $out['aqi'] ) ) . '</td><td>' . esc_html( topersia_g_aqi_label( $cat ) )
				. '</td><td>' . esc_html( null === $out['pm25'] ? '—' : topersia_g_fa( (string) (int) $out['pm25'] ) ) . '</td><td>' . esc_html( topersia_g_aq_time_ir( isset( $out['time'] ) ? $out['time'] : '' ) ) . '</td></tr>';
		}

		echo '</tbody></table></div>';
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'admin_menu', function () {
		if ( function_exists( 'topersia_admin_page' ) ) {
			add_submenu_page( 'topersia', esc_html__( 'کیفیت هوا — تشخیص', 'topersia' ), esc_html__( 'کیفیت هوا (تشخیص)', 'topersia' ), 'manage_options', 'topersia-air-diag', 'topersia_g_aq_diag_page' );
		} else {
			add_submenu_page( 'topersia-render', esc_html__( 'کیفیت هوا — تشخیص', 'topersia' ), esc_html__( 'کیفیت هوا (تشخیص)', 'topersia' ), 'manage_options', 'topersia-air-diag', 'topersia_g_aq_diag_page' );
		}
	}, 20 );
}

if ( function_exists( 'add_action' ) ) {

	add_action( 'rest_api_init', function () {

		register_rest_route( 'topersia/v1', '/air', array(
			'methods'             => 'GET',
			'callback'            => 'topersia_g_aq_rest',
			'permission_callback' => '__return_true',
			'args'                => array(
				'lat' => array( 'required' => true, 'type' => 'string' ),
				'lng' => array( 'required' => true, 'type' => 'string' ),
			),
		) );
	} );
}
