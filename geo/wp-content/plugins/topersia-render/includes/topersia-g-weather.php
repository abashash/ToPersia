<?php
/**
 * قرارداد آداپتور هوا — واژگان وضعیت.  P5-D196
 *
 * آداپتور فقط دما نمی‌دهد؛ یک وضعیتِ نرمال‌شده هم می‌دهد. لایۀ نمایش
 * هرگز کد خام ارائه‌دهنده را نمی‌بیند و هیچ‌جا `if ( 95 === $code )`
 * نوشته نمی‌شود.
 *
 * هر ارائه‌دهنده جدول ترجمه‌اش را **داخل خودش** نگه می‌دارد. اگر جدول
 * بیرون می‌آمد، افزودن ارائه‌دهندۀ دوم یعنی ویرایش یک فایل مشترک و
 * همان‌جاست که واژگان بی‌صدا از هم می‌پاشد.
 *
 * ارائه‌دهندۀ فعال MET Norway است (P5-D242)؛ آداپتور Open-Meteo برای
 * اشتراک تجاری احتمالی مانده. نقطۀ REST پایین همین فایل است.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'TP_PLUGIN_DIR' ) ) {
	return;
}

/**
 * نُه وضعیت. هیچ‌چیز دیگری از هیچ ارائه‌دهنده‌ای بیرون نمی‌آید.
 *
 * `dust` عمداً جداست. گردوغبار در ایران وضعیت واقعی و پرتکرار است و اگر
 * زیر `fog` پنهان شود، هم توصیه‌های سفر غلط می‌شوند هم انیمیشن سربرگ
 * چیز اشتباهی نشان می‌دهد.  P5-D196
 *
 * `cold` متقارنِ `hot` است و هیچ انیمیشنی ندارد. `snow` فقط بارش را
 * می‌گیرد، نه سرمای خشک — اردبیل و سبلان و کوهرنگ روزهای بسیاری
 * بی‌بارش و به‌شدت سردند.  P5-D196
 */
if ( ! function_exists( 'topersia_g_wx_states' ) ) {
	function topersia_g_wx_states() {
		return array( 'clear', 'cloudy', 'rain', 'snow', 'storm', 'fog', 'dust', 'hot', 'cold' );
	}
}

/** آیا این رشته یکی از هشت وضعیت است؟ */
if ( ! function_exists( 'topersia_g_wx_is_state' ) ) {
	function topersia_g_wx_is_state( $state ) {
		return in_array( $state, topersia_g_wx_states(), true );
	}
}

/**
 * ارائه‌دهندۀ Open-Meteo.
 *
 * کد هوا را به شکل WMO می‌دهد، ولی زیرمجموعه‌ای از آن. دو چیز در این
 * زیرمجموعه **نیست** و از جای دیگری می‌آید:
 *
 *   hot   دمایی است، نه کد. از `temperature_2m_max` درمی‌آید.
 *   dust  در کدهای پیش‌بینی Open-Meteo اصلاً وجود ندارد. از نقطۀ
 *         کیفیت هوا (`dust` بر حسب میکروگرم بر مترمکعب) می‌آید.
 *
 * یعنی این ارائه‌دهنده به‌تنهایی شش وضعیت از هشت را از کد می‌سازد و دو
 * تای دیگر را از سیگنال جدا. این را صریح می‌نویسم چون اگر کسی بعداً
 * دنبال «کد WMO گردوغبار» بگردد، پیدا نمی‌کند و وسوسه می‌شود آن را
 * زیر `fog` بگذارد — دقیقاً همان چیزی که `P5-D196` منع کرده.
 */
if ( ! class_exists( 'TP_Wx_Open_Meteo' ) ) {
	class TP_Wx_Open_Meteo {

		const NAME = 'open-meteo';

		/**
		 * آستانۀ `hot` بر حسب سلسیوس.
		 *
		 * ۳۸ بود و به ۴۲ رسید: تهران معمولاً از ۳۸ رد می‌شود و وضعیتی که
		 * چهار ماه سال روشن بماند، دیگر وضعیت نیست.
		 *
		 * ⚠ با دادۀ واقعی ایران اعتبارسنجی نشده.
		 */
		const HOT_C = 42.0;

		/**
		 * آستانۀ `cold` بر حسب سلسیوس — متقارنِ `hot`.
		 *
		 * ⚠ با دادۀ واقعی ایران اعتبارسنجی نشده.
		 */
		const COLD_C = -5.0;

		/**
		 * آستانۀ `dust` بر حسب میکروگرم بر مترمکعب.
		 *
		 * ۵۰ بود و به ۱۵۰ رسید: ۵۰ یک روز غبارآلود معمولی است و ۱۵۰
		 * همان‌جایی است که دید واقعاً می‌افتد.
		 *
		 * ⚠ با دادۀ واقعی ایران اعتبارسنجی نشده.
		 */
		const DUST_UGM3 = 150.0;

		/*
		 * شبکه و کش — دو فراخوان، دو دانه‌بندی.
		 *
		 * غبار پدیده‌ای منطقه‌ای است نه محلی، پس کیفیت هوا روی شبکۀ
		 * درشت‌تر و با کش بلندتر خوانده می‌شود. اگر هر دو روی یک شبکه
		 * می‌رفتند، فراخوان دوم حجم را دو برابر می‌کرد و به سقف ده هزار
		 * نزدیک می‌شدیم.
		 */
		const GRID_FORECAST = 0.25;
		const GRID_AIR      = 0.5;
		const TTL_FORECAST  = 10800;   /* سه ساعت */
		const TTL_AIR       = 21600;   /* شش ساعت */

		/**
		 * جدول ترجمه — کد WMO به وضعیت.
		 *
		 * داخل همین کلاس می‌ماند.  P5-D196
		 */
		public static function codes() {

			return array(
				/* صاف و ابری */
				0  => 'clear',
				1  => 'clear',
				2  => 'cloudy',
				3  => 'cloudy',

				/* مه */
				45 => 'fog',
				48 => 'fog',

				/* نم‌نم — باران است، نه چیز دیگر */
				51 => 'rain',
				53 => 'rain',
				55 => 'rain',
				56 => 'rain',
				57 => 'rain',

				/* باران */
				61 => 'rain',
				63 => 'rain',
				65 => 'rain',
				66 => 'rain',
				67 => 'rain',

				/* برف */
				71 => 'snow',
				73 => 'snow',
				75 => 'snow',
				77 => 'snow',

				/* رگبار باران */
				80 => 'rain',
				81 => 'rain',
				82 => 'rain',

				/* رگبار برف */
				85 => 'snow',
				86 => 'snow',

				/* تندر */
				95 => 'storm',
				96 => 'storm',
				99 => 'storm',
			);
		}

		/** کد را به وضعیت می‌برد. هرگز `hot` یا `dust` برنمی‌گرداند. */
		public static function map( $code ) {

			$table = self::codes();
			$code  = (int) $code;

			return isset( $table[ $code ] ) ? $table[ $code ] : 'clear';
		}

		/**
		 * یک ردیف خام را به وضعیت نرمال‌شده می‌برد.
		 *
		 * ترتیب اولویت عمدی است: هوای خطرناک بر هوای ناخوشایند مقدم است،
		 * و گردوغبار بر مه — وگرنه یک روز غبارآلود «مه» گزارش می‌شود.
		 *
		 *   storm · snow · rain   از کد، بی‌چون‌وچرا
		 *   dust                  اگر سیگنال غبار از آستانه گذشت
		 *   fog                   از کد
		 *   hot                   اگر دما از آستانه گذشت
		 *   cloudy · clear        باقی‌مانده
		 *
		 * @param array $row کلیدهای شناخته‌شده: code · temp_c · dust_ugm3
		 * @return string یکی از هشت وضعیت
		 */
		public static function from( $row ) {

			$code  = isset( $row['code'] ) ? (int) $row['code'] : 0;
			$state = self::map( $code );

			if ( in_array( $state, array( 'storm', 'snow', 'rain' ), true ) ) {
				return $state;
			}

			if ( isset( $row['dust_ugm3'] ) && (float) $row['dust_ugm3'] >= self::DUST_UGM3 ) {
				return 'dust';
			}

			if ( 'fog' === $state ) {
				return 'fog';
			}

			if ( isset( $row['temp_c'] ) ) {

				$temp = (float) $row['temp_c'];

				if ( $temp >= self::HOT_C ) {
					return 'hot';
				}
				if ( $temp <= self::COLD_C ) {
					return 'cold';
				}
			}

			return $state;
		}

		/**
		 * یک مختصات را به مرکز سلول شبکه می‌برد.
		 *
		 * دو موجودیت نزدیک به هم یک سلول می‌گیرند و یک فراخوان. همین
		 * است که مصرف را زیر سقف نگه می‌دارد.
		 */
		public static function snap( $value, $grid ) {
			$grid = (float) $grid;
			if ( $grid <= 0 ) { return (float) $value; }
			return round( (float) $value / $grid ) * $grid;
		}

		/** کلید سلول برای یک جفت مختصات. */
		public static function cell( $lat, $lon, $grid ) {
			return sprintf(
				'%.4f,%.4f',
				self::snap( $lat, $grid ),
				self::snap( $lon, $grid )
			);
		}

		/** کلید کش — سلول به‌اضافۀ نوع فراخوان. */
		public static function cache_key( $kind, $lat, $lon ) {

			$grid = 'air' === $kind ? self::GRID_AIR : self::GRID_FORECAST;

			return 'tp-wx-' . self::NAME . '-' . $kind . '-' . self::cell( $lat, $lon, $grid );
		}

		/** عمر کش بر حسب ثانیه. */
		public static function ttl( $kind ) {
			return 'air' === $kind ? self::TTL_AIR : self::TTL_FORECAST;
		}
	}
}


/**
 * ارائه‌دهندۀ MET Norway — Locationforecast 2.0 (compact).  P5-D242
 *
 * جای Open-Meteo را گرفت: Open-Meteo رایگان فقط غیرتجاری است. آداپتور
 * آن بالاتر دست‌نخورده مانده برای اشتراک تجاری احتمالی؛ انتخاب با فیلتر
 * `topersia_g_wx_provider`.
 *
 * MET کد عددی نمی‌دهد؛ `symbol_code` می‌دهد، مثل `lightrainshowers_day`.
 * پسوند روز و شب (`_day` · `_night` · `_polartwilight`) از وضعیت جداست و
 * فقط «روز/شب» انیمیشن را می‌دهد. نگاشت، همان قرارداد P5-D196:
 *
 *   …thunder…            storm
 *   fog                  fog
 *   …snow… · …sleet…     snow
 *   …rain…               rain
 *   partlycloudy · cloudy  cloudy
 *   clearsky · fair      clear
 *   hot · cold           از دما، با همان آستانه‌های Open-Meteo
 *   dust                 منبعی ندارد — MET کیفیت هوا نمی‌دهد
 *
 * ترتیب اولویت همان `TP_Wx_Open_Meteo::from()`: خطرناک بر ناخوشایند.
 */
if ( ! class_exists( 'TP_Wx_Met_Norway' ) ) {
	class TP_Wx_Met_Norway {

		const NAME     = 'met-norway';
		const CREDIT   = 'MET Norway';
		const ENDPOINT = 'https://api.met.no/weatherapi/locationforecast/2.0/compact';

		/* کمینه و بیشینۀ عمر کش وقتی Expires پاسخ غایب یا نامعقول است. */
		const TTL_MIN = 600;      /* ده دقیقه */
		const TTL_MAX = 21600;    /* شش ساعت */

		/* کش خودِ داده بلندتر از Expires می‌ماند تا Last-Modified برای
		   درخواست شرطی (If-Modified-Since) در دست باشد. */
		const KEEP = 172800;      /* دو روز */

		/** همۀ نمادهای پایۀ MET — از راهنمای weathericons خودِ MET. */
		public static function codes() {

			$out = array();

			foreach ( array(
				'clearsky', 'fair', 'partlycloudy', 'cloudy', 'fog',
				'lightrainshowers', 'rainshowers', 'heavyrainshowers',
				'lightrainshowersandthunder', 'rainshowersandthunder', 'heavyrainshowersandthunder',
				'lightsleetshowers', 'sleetshowers', 'heavysleetshowers',
				'lightssleetshowersandthunder', 'sleetshowersandthunder', 'heavysleetshowersandthunder',
				'lightsnowshowers', 'snowshowers', 'heavysnowshowers',
				'lightssnowshowersandthunder', 'snowshowersandthunder', 'heavysnowshowersandthunder',
				'lightrain', 'rain', 'heavyrain',
				'lightrainandthunder', 'rainandthunder', 'heavyrainandthunder',
				'lightsleet', 'sleet', 'heavysleet',
				'lightsleetandthunder', 'sleetandthunder', 'heavysleetandthunder',
				'lightsnow', 'snow', 'heavysnow',
				'lightsnowandthunder', 'snowandthunder', 'heavysnowandthunder',
			) as $code ) {
				$out[ $code ] = self::rule( $code );
			}

			return $out;
		}

		/** نماد بی پسوند روز و شب. */
		public static function base( $symbol ) {
			return preg_replace( '/_(day|night|polartwilight)$/', '', strtolower( trim( (string) $symbol ) ) );
		}

		/** قاعدۀ نگاشت روی نماد پایه. */
		private static function rule( $base ) {

			if ( false !== strpos( $base, 'thunder' ) ) {
				return 'storm';
			}
			if ( false !== strpos( $base, 'snow' ) || false !== strpos( $base, 'sleet' ) ) {
				return 'snow';
			}
			if ( false !== strpos( $base, 'rain' ) ) {
				return 'rain';
			}
			if ( 'fog' === $base ) {
				return 'fog';
			}
			if ( 'partlycloudy' === $base || 'cloudy' === $base ) {
				return 'cloudy';
			}

			return 'clear';
		}

		/** نماد را به وضعیت می‌برد. هرگز `hot` یا `cold` یا `dust` برنمی‌گرداند. */
		public static function map( $symbol ) {

			$base  = self::base( $symbol );
			$table = self::codes();

			return isset( $table[ $base ] ) ? $table[ $base ] : 'clear';
		}

		/**
		 * نماد و دما ← وضعیت.
		 *
		 * @param array $row کلیدها: symbol · temp_c
		 */
		public static function from( $row ) {

			$state = self::map( isset( $row['symbol'] ) ? $row['symbol'] : '' );

			if ( in_array( $state, array( 'storm', 'snow', 'rain', 'fog' ), true ) ) {
				return $state;
			}

			if ( isset( $row['temp_c'] ) ) {

				$temp = (float) $row['temp_c'];

				if ( $temp >= TP_Wx_Open_Meteo::HOT_C ) {
					return 'hot';
				}
				if ( $temp <= TP_Wx_Open_Meteo::COLD_C ) {
					return 'cold';
				}
			}

			return $state;
		}

		/** رتبۀ شدت — برای خلاصۀ روزانه، شدیدترین برنده است. */
		public static function severity( $state ) {
			$rank = array( 'storm' => 6, 'snow' => 5, 'rain' => 4, 'fog' => 3, 'cloudy' => 2, 'clear' => 1 );
			return isset( $rank[ $state ] ) ? $rank[ $state ] : 0;
		}

		/** شب؟ از پسوند نماد؛ بی پسوند، نامعلوم (null). */
		public static function is_night( $symbol ) {
			$symbol = strtolower( (string) $symbol );
			if ( preg_match( '/_night$/', $symbol ) ) {
				return true;
			}
			if ( preg_match( '/_(day|polartwilight)$/', $symbol ) ) {
				return false;
			}
			return null;
		}

		/** مختصات گردشده به دو رقم — هم کلید کش هم همان که به MET می‌رود. */
		public static function round2( $value ) {
			return sprintf( '%.2f', round( (float) $value, 2 ) );
		}

		public static function cache_key( $lat, $lon ) {
			return 'tp-wx-met-' . self::round2( $lat ) . ',' . self::round2( $lon );
		}

		/**
		 * شناسۀ درخواست — MET درخواستِ بی‌شناسه یا با شناسۀ عمومی را
		 * مسدود می‌کند و نشانی تماس می‌خواهد.
		 */
		public static function user_agent() {
			$mail = function_exists( 'get_option' ) ? sanitize_email( (string) get_option( 'admin_email' ) ) : '';
			return 'ToPersia/1.0 (+https://topersia.com; ' . $mail . ')';
		}

		/** عمر تازگی از سرآیند Expires — میان کمینه و بیشینه. */
		public static function ttl_from_expires( $expires, $now ) {

			$at = $expires ? strtotime( (string) $expires ) : false;

			if ( false === $at ) {
				return self::TTL_MIN;
			}

			return (int) max( self::TTL_MIN, min( self::TTL_MAX, $at - (int) $now ) );
		}

		/**
		 * پاسخ خام ← سه روز کارت + وضعیت اکنون برای انیمیشن.
		 *
		 * روزها به ساعت محلی سایت بریده می‌شوند. دمای روز بیشینه و کمینۀ
		 * `air_temperature` است؛ وضعیت روز، شدیدترین نمادِ ساعت‌های روشن
		 * (۶ تا ۱۸)، و اگر نبود شدیدترینِ کل روز.
		 *
		 * @param array        $body پاسخ JSON رمزگشایی‌شده.
		 * @param DateTimeZone $tz   منطقۀ زمانی.
		 * @param int          $now  اکنون (یونیکس).
		 * @return array|null days · fx
		 */
		public static function parse( $body, $tz, $now ) {

			if ( empty( $body['properties']['timeseries'] ) || ! is_array( $body['properties']['timeseries'] ) ) {
				return null;
			}

			$series = $body['properties']['timeseries'];
			$days   = array();
			$first  = null;

			foreach ( $series as $step ) {

				$at = isset( $step['time'] ) ? strtotime( (string) $step['time'] ) : false;

				if ( false === $at || ! isset( $step['data']['instant']['details'] ) ) {
					continue;
				}

				$local = ( new DateTime( '@' . $at ) )->setTimezone( $tz );
				$date  = $local->format( 'Y-m-d' );
				$hour  = (int) $local->format( 'G' );
				$det   = $step['data']['instant']['details'];
				$temp  = isset( $det['air_temperature'] ) ? (float) $det['air_temperature'] : null;

				$symbol = '';
				foreach ( array( 'next_1_hours', 'next_6_hours', 'next_12_hours' ) as $span ) {
					if ( ! empty( $step['data'][ $span ]['summary']['symbol_code'] ) ) {
						$symbol = (string) $step['data'][ $span ]['summary']['symbol_code'];
						break;
					}
				}

				if ( null === $first && $at >= $now - 3600 && '' !== $symbol ) {
					$first = array( 'step' => $step, 'symbol' => $symbol, 'hour' => $hour );
				}

				if ( ! isset( $days[ $date ] ) ) {
					$days[ $date ] = array( 'max' => null, 'min' => null, 'day' => array(), 'all' => array() );
				}

				if ( null !== $temp ) {
					$days[ $date ]['max'] = null === $days[ $date ]['max'] ? $temp : max( $days[ $date ]['max'], $temp );
					$days[ $date ]['min'] = null === $days[ $date ]['min'] ? $temp : min( $days[ $date ]['min'], $temp );
				}

				if ( '' !== $symbol ) {
					$days[ $date ]['all'][] = $symbol;
					if ( $hour >= 6 && $hour <= 18 ) {
						$days[ $date ]['day'][] = $symbol;
					}
				}
			}

			$today = ( new DateTime( '@' . (int) $now ) )->setTimezone( $tz )->format( 'Y-m-d' );
			$names = array( 'امروز', 'فردا', 'پس‌فردا' );
			$out   = array();

			foreach ( $days as $date => $d ) {

				if ( $date < $today || null === $d['max'] ) {
					continue;
				}

				$pool  = $d['day'] ? $d['day'] : $d['all'];
				$worst = 'clearsky';

				/* نمادِ شدیدترین نگه داشته می‌شود، نه وضعیتش — `from()` نماد می‌خواهد. */
				foreach ( $pool as $symbol ) {
					if ( self::severity( self::map( $symbol ) ) > self::severity( self::map( $worst ) ) ) {
						$worst = $symbol;
					}
				}

				$state = self::from( array( 'symbol' => $worst, 'temp_c' => $d['max'] ) );
				$n     = count( $out );

				$out[] = array(
					'd' => $names[ $n ],
					't' => (int) round( $d['max'] ),
					'n' => (int) round( $d['min'] ),
					'i' => topersia_g_wx_icon( $state ),
					's' => topersia_g_wx_label( $state ),
					'k' => $state,
				);

				if ( 3 === count( $out ) ) {
					break;
				}
			}

			$fx = null;

			if ( $first ) {

				$det   = $first['step']['data']['instant']['details'];
				$next1 = isset( $first['step']['data']['next_1_hours']['details'] ) ? $first['step']['data']['next_1_hours']['details'] : array();
				$night = self::is_night( $first['symbol'] );

				$fx = array(
					'state'    => self::from( array(
						'symbol' => $first['symbol'],
						'temp_c' => isset( $det['air_temperature'] ) ? (float) $det['air_temperature'] : null,
					) ),
					'windDeg'  => isset( $det['wind_from_direction'] ) ? round( (float) $det['wind_from_direction'], 1 ) : null,
					'windKph'  => isset( $det['wind_speed'] ) ? round( (float) $det['wind_speed'] * 3.6, 1 ) : 0,
					'precipMm' => isset( $next1['precipitation_amount'] ) ? (float) $next1['precipitation_amount'] : 0,
					'isDay'    => null === $night ? ( $first['hour'] >= 6 && $first['hour'] < 18 ? 1 : 0 ) : ( $night ? 0 : 1 ),
				);
			}

			return $out ? array( 'days' => $out, 'fx' => $fx ) : null;
		}
	}
}

/**
 * ارائه‌دهندگان ثبت‌شده — هر دو جدولشان در آزمون سنجیده می‌شود.
 */
if ( ! function_exists( 'topersia_g_wx_providers' ) ) {
	function topersia_g_wx_providers() {

		$providers = array( 'TP_Wx_Met_Norway', 'TP_Wx_Open_Meteo' );

		if ( function_exists( 'apply_filters' ) ) {
			$providers = apply_filters( 'topersia_g_wx_providers', $providers );
		}

		return $providers;
	}
}

/**
 * ارائه‌دهندۀ فعال کارت و انیمیشن — پیش‌فرض MET Norway.  P5-D242
 *
 * @return string met-norway · open-meteo
 */
if ( ! function_exists( 'topersia_g_wx_provider_legacy' ) ) {
	/** پیش از مدیریت سرویس‌ها — پیش‌فرض MET Norway یا فیلتر.  منبع «انتقال». */
	function topersia_g_wx_provider_legacy() {

		$name = TP_Wx_Met_Norway::NAME;

		if ( function_exists( 'apply_filters' ) ) {
			$name = (string) apply_filters( 'topersia_g_wx_provider', $name );
		}

		return in_array( $name, array( TP_Wx_Met_Norway::NAME, TP_Wx_Open_Meteo::NAME ), true ) ? $name : TP_Wx_Met_Norway::NAME;
	}
}

if ( ! function_exists( 'topersia_g_wx_provider' ) ) {
	/**
	 * از «سرویس‌های خارجی» هسته اگر سطر weather ذخیره شده؛ وگرنه پیش‌فرض.  P5-D249
	 * آداپتوری که پرچم تجاری خاموشش کرده (Open-Meteo بی کلید پولی) ← MET Norway.
	 */
	function topersia_g_wx_provider() {

		$layer = function_exists( 'topersia_g_layer' ) ? topersia_g_layer( 'weather' ) : null;

		if ( ! $layer ) {
			return topersia_g_wx_provider_legacy();
		}

		$name = (string) $layer['provider'];

		if ( 'off' === $layer['status'] && ! empty( $layer['enabled'] ) ) {
			$name = TP_Wx_Met_Norway::NAME;
		}

		return in_array( $name, array( TP_Wx_Met_Norway::NAME, TP_Wx_Open_Meteo::NAME ), true ) ? $name : TP_Wx_Met_Norway::NAME;
	}
}

/** سطر منبع زیر کارت — «داده‌های هوا: MET Norway».  P5-D242 */
if ( ! function_exists( 'topersia_g_wx_credit' ) ) {
	function topersia_g_wx_credit() {
		return sprintf(
			/* translators: %s: نام ارائه‌دهنده */
			__( 'داده‌های هوا: %s', 'topersia' ),
			TP_Wx_Open_Meteo::NAME === topersia_g_wx_provider() ? 'Open-Meteo' : TP_Wx_Met_Norway::CREDIT
		);
	}
}

/**
 * برچسب‌های پاسخ هوا به زبان درخواست — پس از کش.  ۴٫۴٫۲
 *
 * کش مرکزی دادۀ یک نقطه را یک بار برای همۀ زبان‌ها نگه می‌دارد (کلید `fa`)،
 * و وضعیت هر روز کلیدِ بی‌زبان دارد (`k`). پس برچسب روز، وضعیت و یادداشت
 * همین‌جا، هنگام پاسخ، از کاتالوگ ساخته می‌شوند — بی فراخوان دوم به MET.
 *
 * @param array $out پاسخ کش‌شده.
 * @return array
 */
if ( ! function_exists( 'topersia_g_wx_localize' ) ) {
	function topersia_g_wx_localize( $out ) {

		if ( ! is_array( $out ) ) {
			return $out;
		}

		$names = array( __( 'امروز', 'topersia' ), __( 'فردا', 'topersia' ), __( 'پس‌فردا', 'topersia' ) );

		if ( isset( $out['days'] ) && is_array( $out['days'] ) ) {
			foreach ( array_values( $out['days'] ) as $n => $day ) {
				if ( ! is_array( $day ) ) {
					continue;
				}
				$out['days'][ $n ]['d'] = isset( $names[ $n ] ) ? $names[ $n ] : '';
				if ( isset( $day['k'] ) ) {
					$out['days'][ $n ]['s'] = topersia_g_wx_label( $day['k'] );
				}
			}
		}

		$out['note'] = __( 'به‌روزرسانی: چند دقیقه پیش', 'topersia' );

		return $out;
	}
}

/**
 * نقطۀ REST هوا — `topersia/v1/weather`.  P5-D219 · P5-D242
 *
 * چرا REST و نه HTML سرور: صفحه کش تمام‌صفحه می‌شود، و هوای روزِ ساختِ
 * کش تا روزها روی صفحه می‌ماند.  همان دلیلی که مرجع هم نوشته بود.
 *
 * پاسخ: days (سه روز کارت) · note · src · fx (وضعیت اکنون برای انیمیشن
 * سربرگ — همان یک پاسخ، دو مصرف).
 */
if ( ! defined( 'TP_WX_REST_TTL' ) ) {
	define( 'TP_WX_REST_TTL', 1800 );   /* نیم ساعت — مسیر Open-Meteo */
}

/* پهنۀ پذیرفته — همان کادر نقشۀ کاشی‌ها. بیرون از آن درخواست رد می‌شود
   تا پراکسی برای هر نقطۀ جهان به MET نزند. */
if ( ! defined( 'TP_WX_BBOX' ) ) {
	define( 'TP_WX_BBOX', '44,25,63.5,40.5' );
}

/* سقف فراخوانِ بالادست در هر دقیقه، روی کل سایت. */
if ( ! defined( 'TP_WX_UPSTREAM_PER_MIN' ) ) {
	define( 'TP_WX_UPSTREAM_PER_MIN', 60 );
}

if ( ! function_exists( 'topersia_g_wx_rest' ) ) {

	/** کد WMO به آیکون کارت. آیکون‌ها همان‌های مرجع‌اند. */
	function topersia_g_wx_icon( $state ) {

		$map = array(
			'clear'  => 'sun',
			'cloudy' => 'part',
			'fog'    => 'part',
			'rain'   => 'rain',
			'storm'  => 'rain',
			'snow'   => 'snow',
			'dust'   => 'wind',
			'hot'    => 'sun',
			'cold'   => 'snow',
		);

		return isset( $map[ $state ] ) ? $map[ $state ] : 'sun';
	}

	/** برچسب وضعیت — از واژه‌نامۀ خود پروژه، نه رشتۀ تازه؛ به زبان صفحه  ۴٫۴٫۲ */
	function topersia_g_wx_label( $state ) {

		$map = array(
			'clear'  => __( 'آفتابی', 'topersia' ),
			'cloudy' => __( 'کمی ابری', 'topersia' ),
			'fog'    => __( 'مه', 'topersia' ),
			'rain'   => __( 'بارانی', 'topersia' ),
			'storm'  => __( 'رعدوبرق', 'topersia' ),
			'snow'   => __( 'برفی', 'topersia' ),
			'dust'   => __( 'گردوغبار', 'topersia' ),
			'hot'    => __( 'گرم', 'topersia' ),
			'cold'   => __( 'سرد', 'topersia' ),
		);

		return isset( $map[ $state ] ) ? $map[ $state ] : $map['clear'];
	}

	/** آیا این مختصات درون کادر پذیرفته است. */
	function topersia_g_wx_in_bbox( $lat, $lon ) {
		$b = array_map( 'floatval', explode( ',', TP_WX_BBOX ) );
		return 4 === count( $b ) && $lon >= $b[0] && $lat >= $b[1] && $lon <= $b[2] && $lat <= $b[3];
	}

	/** یک سهم از سقف دقیقه‌ای بالادست؛ false یعنی پر است. */
	function topersia_g_wx_take_slot() {

		$key   = 'tp-wx-upstream-' . gmdate( 'YmdHi' );
		$count = (int) get_transient( $key );

		if ( $count >= TP_WX_UPSTREAM_PER_MIN ) {
			return false;
		}

		set_transient( $key, $count + 1, 120 );

		return true;
	}

	/**
	 * مسیر MET Norway — کش طبق Expires، درخواست شرطی با If-Modified-Since.
	 *
	 * @return array|WP_Error
	 */
	function topersia_g_wx_met( $lat, $lon ) {

		/*
		 * با هستۀ ToPersia از کش مرکزی می‌گذرد (قاعدۀ ۰-۶ · P5-D250): کلید مختصات
		 * دو رقمی، ارائه‌دهنده و زبان؛ TTL از Expires؛ دادۀ کهنه به‌جای خطا.
		 */
		if ( function_exists( 'topersia_cache_remember' ) ) {
			return topersia_cache_remember(
				'weather',
				array( TP_Wx_Met_Norway::round2( $lat ), TP_Wx_Met_Norway::round2( $lon ) ),
				function ( $prev ) use ( $lat, $lon ) {
					return topersia_g_wx_met_fetch( $lat, $lon, $prev );
				},
				array( 'provider' => TP_Wx_Met_Norway::NAME, 'lang' => 'fa', 'keep' => TP_Wx_Met_Norway::KEEP, 'stale_on_error' => true )
			);
		}

		/* بی هسته: همان کش ۴٫۳٫۰ */
		$key    = TP_Wx_Met_Norway::cache_key( $lat, $lon );
		$cached = get_transient( $key );
		$now    = time();

		if ( is_array( $cached ) && isset( $cached['out'] ) && (int) $cached['fresh_until'] > $now ) {
			return $cached['out'];
		}

		$prev = is_array( $cached ) && isset( $cached['out'] )
			? array( 'value' => $cached['out'], 'meta' => array( 'last_modified' => isset( $cached['last_modified'] ) ? (string) $cached['last_modified'] : '' ) )
			: null;
		$res  = topersia_g_wx_met_fetch( $lat, $lon, $prev );

		if ( is_wp_error( $res ) ) {
			return $prev ? $prev['value'] : $res;
		}

		set_transient( $key, array(
			'out'           => $res['value'],
			'last_modified' => isset( $res['meta']['last_modified'] ) ? (string) $res['meta']['last_modified'] : '',
			'fresh_until'   => $now + (int) $res['ttl'],
		), TP_Wx_Met_Norway::KEEP );

		return $res['value'];
	}

	/**
	 * یک دریافت از MET Norway — درخواست شرطی با If-Modified-Since، TTL از Expires.
	 * شرط‌های TermsOfService خود MET: User-Agent شناسا، Expires، If-Modified-Since.
	 *
	 * @param array|null $prev پوشۀ پیشین: value · meta.last_modified.
	 * @return array|WP_Error value · ttl · meta{last_modified} (· from_cache)
	 */
	function topersia_g_wx_met_fetch( $lat, $lon, $prev ) {

		$now = time();

		if ( ! topersia_g_wx_take_slot() ) {
			/* سقف دقیقه‌ای پر است: کهنه یک دقیقۀ دیگر، بی ثبت خطای بالادست */
			return ( is_array( $prev ) && array_key_exists( 'value', $prev ) )
				? array( 'value' => $prev['value'], 'ttl' => 60, 'meta' => isset( $prev['meta'] ) ? (array) $prev['meta'] : array(), 'from_cache' => true )
				: new WP_Error( 'tp_wx_busy', __( 'سرویس هوا موقتاً شلوغ است.', 'topersia' ), array( 'status' => 503 ) );
		}

		$headers = array( 'User-Agent' => TP_Wx_Met_Norway::user_agent(), 'Accept' => 'application/json' );

		if ( is_array( $prev ) && ! empty( $prev['meta']['last_modified'] ) ) {
			$headers['If-Modified-Since'] = (string) $prev['meta']['last_modified'];
		}

		$url = add_query_arg(
			array(
				'lat' => TP_Wx_Met_Norway::round2( $lat ),
				'lon' => TP_Wx_Met_Norway::round2( $lon ),
			),
			TP_Wx_Met_Norway::ENDPOINT
		);

		$response = wp_remote_get( $url, array( 'timeout' => 8, 'headers' => $headers, 'user-agent' => TP_Wx_Met_Norway::user_agent() ) );
		$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		$expires  = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_header( $response, 'expires' );

		/* ۳۰۴: همان دادۀ پیشین، با تازگی تازه */
		if ( 304 === $code && is_array( $prev ) && array_key_exists( 'value', $prev ) ) {
			return array( 'value' => $prev['value'], 'ttl' => TP_Wx_Met_Norway::ttl_from_expires( $expires, $now ), 'meta' => isset( $prev['meta'] ) ? (array) $prev['meta'] : array() );
		}

		if ( 200 !== $code ) {
			/* خطای بالادست — کهنه بهتر از هیچ را فراخوان (کش مرکزی یا مسیر بی‌هسته) تصمیم می‌گیرد */
			return new WP_Error( 'tp_wx_upstream', __( 'سرویس هوا در دسترس نیست.', 'topersia' ), array( 'status' => 503 ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$tz   = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'Asia/Tehran' );
		$data = is_array( $body ) ? TP_Wx_Met_Norway::parse( $body, $tz, $now ) : null;

		if ( ! $data ) {
			return new WP_Error( 'tp_wx_shape', __( 'پاسخ سرویس هوا شکل مورد انتظار را ندارد.', 'topersia' ), array( 'status' => 502 ) );
		}

		$out = array(
			'days' => $data['days'],
			'fx'   => $data['fx'],
			'note' => 'به‌روزرسانی: چند دقیقه پیش',
			'src'  => TP_Wx_Met_Norway::CREDIT,
		);

		return array(
			'value' => $out,
			'ttl'   => TP_Wx_Met_Norway::ttl_from_expires( $expires, $now ),
			'meta'  => array( 'last_modified' => (string) wp_remote_retrieve_header( $response, 'last-modified' ) ),
		);
	}

	/**
	 * مسیر Open-Meteo — همان ۴٫۲٫۳، برای اشتراک تجاری احتمالی.
	 *
	 * @return array|WP_Error
	 */
	function topersia_g_wx_open_meteo( $lat, $lon ) {

		/* با هستۀ ToPersia از کش مرکزی — کلید سلول شبکۀ پیش‌بینی (قاعدۀ ۰-۶) */
		if ( function_exists( 'topersia_cache_remember' ) ) {
			return topersia_cache_remember(
				'weather',
				array( 'card', TP_Wx_Open_Meteo::cell( $lat, $lon, TP_Wx_Open_Meteo::GRID_FORECAST ) ),
				function () use ( $lat, $lon ) {
					$out = topersia_g_wx_open_meteo_fetch( $lat, $lon );
					return is_wp_error( $out ) ? $out : array( 'value' => $out, 'ttl' => TP_WX_REST_TTL );
				},
				array( 'provider' => TP_Wx_Open_Meteo::NAME, 'lang' => 'fa' )
			);
		}

		$key    = TP_Wx_Open_Meteo::cache_key( 'card', $lat, $lon );
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$out = topersia_g_wx_open_meteo_fetch( $lat, $lon );

		if ( ! is_wp_error( $out ) ) {
			set_transient( $key, $out, TP_WX_REST_TTL );
		}

		return $out;
	}

	/** یک دریافت از Open-Meteo — سه روز کارت. */
	function topersia_g_wx_open_meteo_fetch( $lat, $lon ) {

		$cell = explode( ',', TP_Wx_Open_Meteo::cell( $lat, $lon, TP_Wx_Open_Meteo::GRID_FORECAST ) );

		$url = add_query_arg(
			array(
				'latitude'      => $cell[0],
				'longitude'     => $cell[1],
				'daily'         => 'weather_code,temperature_2m_max,temperature_2m_min',
				'timezone'      => 'auto',
				'forecast_days' => 3,
			),
			'https://api.open-meteo.com/v1/forecast'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 6 ) );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'tp_wx_upstream', __( 'سرویس هوا در دسترس نیست.', 'topersia' ), array( 'status' => 503 ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! isset( $body['daily']['time'] ) || ! is_array( $body['daily']['time'] ) ) {
			return new WP_Error( 'tp_wx_shape', __( 'پاسخ سرویس هوا شکل مورد انتظار را ندارد.', 'topersia' ), array( 'status' => 502 ) );
		}

		$names = array( 'امروز', 'فردا', 'پس‌فردا' );
		$days  = array();

		foreach ( $body['daily']['time'] as $n => $ignored ) {

			if ( $n > 2 ) {
				break;
			}

			$high  = isset( $body['daily']['temperature_2m_max'][ $n ] ) ? (float) $body['daily']['temperature_2m_max'][ $n ] : 0.0;
			$state = TP_Wx_Open_Meteo::from( array(
				'code'   => isset( $body['daily']['weather_code'][ $n ] ) ? (int) $body['daily']['weather_code'][ $n ] : 0,
				'temp_c' => $high,
			) );

			$days[] = array(
				'd' => isset( $names[ $n ] ) ? $names[ $n ] : '',
				't' => (int) round( $high ),
				'n' => (int) round( isset( $body['daily']['temperature_2m_min'][ $n ] ) ? (float) $body['daily']['temperature_2m_min'][ $n ] : 0.0 ),
				'i' => topersia_g_wx_icon( $state ),
				's' => topersia_g_wx_label( $state ),
				'k' => $state,
			);
		}

		$out = array(
			'days' => $days,
			'fx'   => $days ? array( 'state' => $days[0]['k'], 'windDeg' => null, 'windKph' => 0, 'precipMm' => 0, 'isDay' => 1 ) : null,
			'note' => 'به‌روزرسانی: چند دقیقه پیش',
			'src'  => 'Open-Meteo',
		);

		return $out;
	}

	function topersia_g_wx_rest( $request ) {

		$coords = (string) $request->get_param( 'coords' );

		/* هر قالبی که تابع مرکزی هسته می‌خواند (۲ه٫۵ · P5-D314)؛ بی آن یا نامعتبر ← همان تجزیۀ دهدهی پیشین */
		$point = function_exists( 'topersia_geo_point' ) ? topersia_geo_point( $coords ) : null;

		if ( $point ) {
			$lat = (float) $point['lat'];
			$lon = (float) $point['lng'];
		} else {

			$parts = array_map( 'trim', explode( ',', $coords ) );

			if ( count( $parts ) < 2 || ! is_numeric( $parts[0] ) || ! is_numeric( $parts[1] ) ) {
				return new WP_Error( 'tp_wx_coords', __( 'مختصات نامعتبر است.', 'topersia' ), array( 'status' => 400 ) );
			}

			$lat = (float) $parts[0];
			$lon = (float) $parts[1];
		}

		if ( ! topersia_g_wx_in_bbox( $lat, $lon ) ) {
			return new WP_Error( 'tp_wx_coords', __( 'مختصات بیرون از پهنۀ پوشش است.', 'topersia' ), array( 'status' => 400 ) );
		}

		/* مدیر سرویس هوا را در «سرویس‌های خارجی» خاموش کرده  P5-D249 */
		$layer = function_exists( 'topersia_g_layer' ) ? topersia_g_layer( 'weather' ) : null;

		if ( $layer && empty( $layer['enabled'] ) ) {
			return new WP_Error( 'tp_wx_off', __( 'سرویس هوا خاموش است.', 'topersia' ), array( 'status' => 503 ) );
		}

		$out = TP_Wx_Open_Meteo::NAME === topersia_g_wx_provider()
			? topersia_g_wx_open_meteo( $lat, $lon )
			: topersia_g_wx_met( $lat, $lon );

		return is_wp_error( $out ) ? $out : rest_ensure_response( topersia_g_wx_localize( $out ) );
	}
}

if ( function_exists( 'add_action' ) ) {

	add_action( 'rest_api_init', function () {

		register_rest_route( 'topersia/v1', '/weather', array(
			'methods'             => 'GET',
			'callback'            => 'topersia_g_wx_rest',
			'permission_callback' => '__return_true',
			'args'                => array(
				'coords' => array( 'required' => true, 'type' => 'string' ),
			),
		) );
	} );
}
