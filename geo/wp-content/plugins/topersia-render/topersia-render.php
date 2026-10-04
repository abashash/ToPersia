<?php
/**
 * Plugin Name:  ToPersia — هستۀ نمایش
 * Description:  لایۀ نمایش ToPersia — قلم، پالت سه‌حالته، کلید حالت رنگ در سربرگ، و پنل نمایش و خوانایی. قلم و رنگ تم و صفحه‌ساز را تصاحب می‌کند تا یک مرجع واحد بماند.
 * Version:      4.4.3
 * Requires PHP: 7.4
 * Text Domain:  topersia
 *
 * تصمیم‌های حاکم
 *   P5-D144  لایۀ نمایش افزونۀ مستقل است؛ topersia-core.php دست نمی‌خورد.
 *   P5-D137  هر مسیر تازه پشت یک فلگ خاموش‌شدنی.
 *   P5-D151  تنظیمات کاربر هرگز HTML سرور را عوض نمی‌کند — کش دست‌نخورده.
 *   P5-D146  هر برچسب ثابت در PHP با __().
 *   P5-D156  پنل نمایش و خوانایی بخشی از سربرگ سراسری است.
 *   P5-D157  معناشناسی رادیویی، اعلام زنده، ادغام کنترل‌ها.
 *   P5-D158  پل تم: متغیرهای وودمارت و المنتور به توکن‌های ما نگاشت می‌شوند.
 *   P5-D159  کلید حالت رنگ از پنل بیرون آمد و در سربرگ نشست؛ پنل جای بقیۀ
 *            تنظیمات و افزوده‌های آینده است.
 *   P5-D160  قلم و حالت پیش‌فرض از صفحۀ تنظیمات مدیر کنترل می‌شوند، نه از کد.
 *
 * BC-AMENDMENT-002 بند ۲: هر فراخوانی بیرونی گارد دارد، شامل توابع وردپرس.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * گارد بارگذاری یگانه.
 *
 * اگر نسخۀ دیگری از همین افزونه فعال باشد — مثلاً پوشۀ نسخۀ قبلی حذف
 * نشده باشد — PHP با «Cannot redeclare» می‌افتد و کل سایت خطای مرگبار
 * می‌دهد. این چند خط آن حالت را به یک بارگذاری بی‌صدا تبدیل می‌کند.
 */
if ( function_exists( 'topersia_render_ready' ) || defined( 'TOPERSIA_RENDER_LOADED' ) ) {
	return;
}

define( 'TOPERSIA_RENDER_LOADED', __FILE__ );

if ( ! defined( 'TOPERSIA_RENDER_VERSION' ) ) {
	define( 'TOPERSIA_RENDER_VERSION', '4.4.3' );
}

/** فلگ خاموش‌کننده — در wp-config.php می‌توان false کرد. P5-D137 */
if ( ! defined( 'TOPERSIA_RENDER_ENABLED' ) ) {
	define( 'TOPERSIA_RENDER_ENABLED', true );
}

/**
 * آیا محیط آماده است؟
 *
 * @return bool
 */
if ( ! function_exists( 'topersia_render_ready' ) ) {
	function topersia_render_ready() {
		if ( ! TOPERSIA_RENDER_ENABLED ) {
			return false;
		}

		static $ready = null;
		if ( null !== $ready ) {
			return $ready;
		}

		$required = array(
			'add_action', 'add_filter', 'add_shortcode',
			'plugin_dir_path', 'plugin_dir_url',
			'wp_enqueue_style', 'wp_enqueue_script', 'wp_add_inline_style',
			'esc_attr', 'esc_html', 'esc_url', 'esc_html__', 'esc_attr__',
		);

		foreach ( $required as $function_name ) {
			if ( ! function_exists( $function_name ) ) {
				$ready = false;
				return $ready;
			}
		}

		$ready = true;
		return $ready;
	}
}

/**
 * آیا هستۀ ToPersia هست؟ برای کالبک‌های ناحیه در نسخۀ بعد.
 *
 * @return bool
 */
if ( ! function_exists( 'topersia_render_core_ready' ) ) {
	function topersia_render_core_ready() {
		return function_exists( 'topersia_active_rows' );
	}
}

/**
 * مسیر افزونه.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_path' ) ) {
	function topersia_render_path() {
		static $path = null;
		if ( null === $path ) {
			$path = function_exists( 'plugin_dir_path' ) ? plugin_dir_path( __FILE__ ) : '';
		}
		return $path;
	}
}

/**
 * نشانی افزونه.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_url' ) ) {
	function topersia_render_url() {
		static $url = null;
		if ( null === $url ) {
			$url = function_exists( 'plugin_dir_url' ) ? plugin_dir_url( __FILE__ ) : '';
		}
		return $url;
	}
}

/**
 * نسخۀ فایل ایستا از روی زمان ویرایش.
 *
 * @param string $relative مسیر نسبی.
 * @return string
 */
if ( ! function_exists( 'topersia_render_asset_version' ) ) {
	function topersia_render_asset_version( $relative ) {
		$base = topersia_render_path();

		if ( '' === $base || ! function_exists( 'file_exists' ) || ! function_exists( 'filemtime' ) ) {
			return TOPERSIA_RENDER_VERSION;
		}

		$full = $base . ltrim( $relative, '/' );

		if ( ! @file_exists( $full ) ) {
			return TOPERSIA_RENDER_VERSION;
		}

		$time = @filemtime( $full );

		return ( false === $time ) ? TOPERSIA_RENDER_VERSION : (string) $time;
	}
}

/* -------------------------------------------------------------------------
   ترجمه — کاتالوگ `topersia`  ·  ۴٫۴٫۲
   -------------------------------------------------------------------------
   رشته‌های رابط فارسی‌اند و فارسی منبع است؛ انگلیسی و عربی از
   `languages/topersia-en_US` و `languages/topersia-ar` (l10n.php · mo · po).
   تا ۴٫۴٫۱ هیچ کاتالوگی بار نمی‌شد و صفحۀ انگلیسی و عربی همه‌جا فارسی بود.
   کاتالوگ را `scripts/tp-i18n.php` از خودِ کد می‌سازد و می‌سنجد.  P5-D146
   ------------------------------------------------------------------------- */

if ( ! function_exists( 'topersia_render_textdomain' ) ) {
	function topersia_render_textdomain() {
		if ( function_exists( 'load_plugin_textdomain' ) && function_exists( 'plugin_basename' ) ) {
			load_plugin_textdomain( 'topersia', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}
	}
}

if ( topersia_render_ready() ) {
	add_action( 'init', 'topersia_render_textdomain', 0 );
}

/**
 * رشته‌های رابطِ `topersia-g-zones.js` — منبع فارسی ← ترجمۀ زبان صفحه.
 *
 * جاوااسکریپت هر پیام را با `t( 'فارسی' )` می‌خواهد و این جدول از همان
 * کاتالوگ PHP پر می‌شود. آزمون می‌سنجد که هر `t( '…' )` پرونده این‌جا
 * هست و برعکس.  ۴٫۴٫۲
 *
 * @return array
 */
if ( ! function_exists( 'topersia_render_js_strings' ) ) {
	function topersia_render_js_strings() {
		return array(
			'کپی شد ✓',
			'در حال ساختن…',
			'ساختن ممکن نشد.',
			'ارتباط برقرار نشد. دوباره تلاش کنید.',
			'به همۀ معیارها امتیاز بدهید.',
			'متن نظر دست‌کم ده نویسه باشد.',
			'ثبت ممکن نشد.',
			'ارتباط برقرار نشد.',
			'نشانی کپی شد.',
			'دنبال می‌کنید',
			'دنبال‌کردن',
			'ذخیره ممکن نشد.',
			'دنبال‌کردن ممکن نشد.',
		);
	}
}

if ( ! function_exists( 'topersia_render_js_i18n' ) ) {
	function topersia_render_js_i18n() {

		$out = array();

		foreach ( topersia_render_js_strings() as $fa ) {
			$tr = function_exists( 'topersia_g_t' ) ? topersia_g_t( $fa ) : $fa;
			if ( $tr !== $fa ) {
				$out[ $fa ] = $tr;
			}
		}

		return $out;
	}
}

/** خطِ رقم صفحه برای جاوااسکریپت — fa · ar · latn.  ۴٫۴٫۲ */
if ( ! function_exists( 'topersia_render_js_digits' ) ) {
	function topersia_render_js_digits() {
		$lang = function_exists( 'topersia_g_page_lang' ) ? topersia_g_page_lang() : 'fa';
		return in_array( $lang, array( 'fa', 'ar' ), true ) ? $lang : 'latn';
	}
}

/* -------------------------------------------------------------------------
   تنظیمات مدیر
   ------------------------------------------------------------------------- */

$topersia_settings_file = topersia_render_path() . 'includes/topersia-settings.php';

if ( '' !== topersia_render_path() && @file_exists( $topersia_settings_file ) ) {
	require_once $topersia_settings_file;
}

if ( topersia_render_ready() && function_exists( 'topersia_render_settings_menu' ) ) {
	add_action( 'admin_menu', 'topersia_render_settings_menu' );
	add_action( 'admin_init', 'topersia_render_settings_register' );
}

/* -------------------------------------------------------------------------
   نواحی دستۀ G — نقشۀ فیلد و پانزده شورت‌کد
   -------------------------------------------------------------------------
   المنتور اسکلت چیدمان می‌سازد؛ این دو فایل محتوای هر ناحیه را.  P5-D147
   ------------------------------------------------------------------------- */

foreach ( array( 'includes/topersia-g-core.php', 'includes/topersia-g-map.php', 'includes/topersia-g-weather.php', 'includes/topersia-g-zones.php', 'includes/topersia-g-ai.php', 'includes/topersia-g-news.php' ) as $topersia_g_file ) {

	$topersia_g_path = topersia_render_path() . $topersia_g_file;

	if ( '' !== topersia_render_path() && @file_exists( $topersia_g_path ) ) {
		require_once $topersia_g_path;
	}
}

unset( $topersia_g_file, $topersia_g_path );


/**
 * یک تنظیم، با جایگزین امن اگر فایل تنظیمات نبود.
 *
 * @param string $key      کلید.
 * @param mixed  $fallback جایگزین.
 * @return mixed
 */
if ( ! function_exists( 'topersia_render_option' ) ) {
	function topersia_render_option( $key, $fallback = '' ) {
		if ( function_exists( 'topersia_render_setting' ) ) {
			return topersia_render_setting( $key, $fallback );
		}
		return $fallback;
	}
}

/* -------------------------------------------------------------------------
   دارایی‌ها
   ------------------------------------------------------------------------- */

/**
 * نشانی فایل قلم روی میزبانِ همین سایت.  ۴٫۳٫۰ بند ۱۳
 *
 * قلم فقط از این‌جا می‌آید. نشانی ذخیره‌شده در تنظیمات مطلق است و روی
 * نسخۀ محلی یا دامنۀ دیگر به میزبان دیگری اشاره می‌کرد؛ اگر مسیرش زیر
 * پوشۀ محتوای وردپرس است (مثلاً `/assets/uploads/fonts/…`)، روی
 * `content_url()` همین میزبان بازسازی می‌شود. نشانی نسبی هم همین‌طور.
 * بیرون از پوشۀ محتوا دست نمی‌خورد.
 *
 * @param string $file نشانی ذخیره‌شده.
 * @return string
 */
if ( ! function_exists( 'topersia_render_font_url' ) ) {
	function topersia_render_font_url( $file ) {

		$file = trim( (string) $file );

		if ( '' === $file || ! function_exists( 'content_url' ) ) {
			return $file;
		}

		$base      = untrailingslashit( content_url() );
		$base_path = (string) wp_parse_url( $base, PHP_URL_PATH );
		$path      = (string) wp_parse_url( $file, PHP_URL_PATH );
		$host      = wp_parse_url( $file, PHP_URL_HOST );

		if ( '' === $path ) {
			return $file;
		}

		/* نسبی به پوشۀ محتوا: «uploads/fonts/x.woff2» */
		if ( null === $host && 0 !== strpos( $path, '/' ) ) {
			return $base . '/' . ltrim( $path, '/' );
		}

		if ( '' !== $base_path && 0 === strpos( $path, trailingslashit( $base_path ) ) ) {
			return $base . substr( $path, strlen( $base_path ) );
		}

		return $file;
	}
}

/**
 * CSS پویا از روی تنظیمات: تعریف قلم، پشتۀ قلم و اندازۀ پایه.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_dynamic_css' ) ) {
	function topersia_render_dynamic_css() {
		$name     = trim( (string) topersia_render_option( 'font_name', '' ) );
		$variable = (int) topersia_render_option( 'font_variable', 0 );
		$stack    = trim( (string) topersia_render_option( 'font_stack', 'Vazirmatn, system-ui, sans-serif' ) );
		$size     = (int) topersia_render_option( 'base_size', 17 );

		if ( $size < 14 || $size > 22 ) {
			$size = 17;
		}

		$css       = '';
		$safe_name = str_replace( array( '<', '>', '{', '}', ';', '"', '\\' ), '', $name );

		if ( '' !== $name ) {
			if ( $variable ) {
				$file = trim( (string) topersia_render_option( 'font_woff2', '' ) );

				if ( '' !== $file ) {
					$css .= '@font-face{font-family:"' . $safe_name . '";'
						. 'src:url("' . esc_url( topersia_render_font_url( $file ) ) . '") format("woff2");'
						. 'font-weight:100 900;font-style:normal;font-display:swap;}';
				}
			} else {
				$weights = array( '300', '400', '500', '600', '700', '800' );

				foreach ( $weights as $weight ) {
					$file = trim( (string) topersia_render_option( 'font_w' . $weight, '' ) );

					if ( '' === $file ) {
						continue;
					}

					$css .= '@font-face{font-family:"' . $safe_name . '";'
						. 'src:url("' . esc_url( topersia_render_font_url( $file ) ) . '") format("woff2");'
						. 'font-weight:' . $weight . ';font-style:normal;font-display:swap;}';
				}
			}
		}

		$family = ( '' !== $name ) ? '"' . $name . '", ' . $stack : $stack;

		/* esc_attr گیومه را به &quot; تبدیل می‌کند و مقدار CSS را می‌شکند.
		   اینجا فقط نویسه‌هایی حذف می‌شوند که می‌توانند از اعلان بیرون بزنند. */
		$family = str_replace( array( '<', '>', '{', '}', ';', '\\' ), '', $family );

		$css .= ':root{--tp-font:' . $family . ';--tp-base:' . $size . 'px;}';
		$css .= 'html:root{font-size:calc(' . $size . 'px * var(--tp-scale,1));}';

		/* ── شکل و ابعاد ──────────────────────────────────────────────
		   همان عددها هم به توکن‌های ما می‌روند و هم به متغیرهای تم، تا
		   دکمۀ وودمارت و دکمۀ ما یک شکل داشته باشند. */
		$radius   = (int) topersia_render_option( 'radius', 10 );
		$c_radius = (int) topersia_render_option( 'control_radius', 8 );
		$border   = (int) topersia_render_option( 'border_width', 1 );
		$height   = (int) topersia_render_option( 'control_height', 42 );
		$width    = (int) topersia_render_option( 'container', 1222 );
		$shadow   = (string) topersia_render_option( 'shadow', 'soft' );

		if ( ! in_array( $shadow, array( 'none', 'soft', 'deep' ), true ) ) {
			$shadow = 'soft';
		}

		$css .= 'html:root{'
			. '--tp-radius:' . $radius . 'px;'
			. '--tp-control-radius:' . $c_radius . 'px;'
			. '--tp-border-w:' . $border . 'px;'
			. '--tp-shadow:var(--tp-shadow-' . $shadow . ');'
			. '--wd-text-font-size:' . $size . 'px;'
			. '--wd-brd-radius:' . $radius . 'px;'
			. '--wd-form-brd-radius:' . $c_radius . 'px;'
			. '--wd-form-brd-width:' . $border . 'px;'
			. '--wd-form-height:' . $height . 'px;'
			. '--wd-container-w:' . $width . 'px;'
			. '--btn-default-brd-radius:' . $c_radius . 'px;'
			. '--btn-accented-brd-radius:' . $c_radius . 'px;'
			. '--btn-brd-radius:' . $c_radius . 'px;'
			. '--btn-height:' . $height . 'px;'
			. '}';

		return $css;
	}
}

/**
 * ثبت شیوه‌نامه‌ها و اسکریپت.
 *
 * @return void
 */
if ( ! function_exists( 'topersia_render_enqueue_assets' ) ) {
	function topersia_render_enqueue_assets() {
		if ( ! topersia_render_ready() ) {
			return;
		}

		$url = topersia_render_url();
		if ( '' === $url ) {
			return;
		}

		wp_enqueue_style(
			'topersia-tokens',
			$url . 'assets/topersia-tokens.css',
			array(),
			topersia_render_asset_version( 'assets/topersia-tokens.css' )
		);

		/* پل تم بعد از توکن‌ها می‌آید تا متغیرهای تم را بازنویسی کند. */
		if ( topersia_render_option( 'bridge_woodmart', 1 ) || topersia_render_option( 'bridge_elementor', 1 ) ) {
			wp_enqueue_style(
				'topersia-theme-bridge',
				$url . 'assets/topersia-theme-bridge.css',
				array( 'topersia-tokens' ),
				topersia_render_asset_version( 'assets/topersia-theme-bridge.css' )
			);
		}

		/*
		 * قالب دستۀ G — بعد از توکن‌ها می‌آید چون از --tp-* می‌خواند.
		 *
		 * `topersia-g-zones.css` حذف شد؛ این فایل جایش را گرفت و با
		 * `scripts/tp-template-gen.php` از خودِ مرجع ساخته می‌شود. P5-D219
		 */
		wp_enqueue_style(
			'topersia-g-template',
			$url . 'assets/topersia-g-template.css',
			array( 'topersia-tokens' ),
			topersia_render_asset_version( 'assets/topersia-g-template.css' )
		);

		wp_add_inline_style( 'topersia-tokens', topersia_render_dynamic_css() );

		wp_enqueue_script(
			'topersia-display',
			$url . 'assets/topersia-display.js',
			array(),
			topersia_render_asset_version( 'assets/topersia-display.js' ),
			true
		);

		wp_enqueue_script(
			'topersia-g-zones',
			$url . 'assets/topersia-g-zones.js',
			array(),
			topersia_render_asset_version( 'assets/topersia-g-zones.js' ),
			true
		);

		/* نشانی نقطۀ هوا — کارت سه‌روزه از همین می‌خواند.  P5-D219 */
		wp_localize_script(
			'topersia-g-zones',
			'TopersiaG',
			array(
				'rest'   => esc_url_raw( rest_url( 'topersia/v1/weather' ) ),
				/* جدول قاعدۀ تاکردن جست‌وجو از هسته — پنجرۀ فهرست همان را اجرا می‌کند.  P5-D265 */
				'fold'   => function_exists( 'topersia_search_fold_js_rules' ) ? topersia_search_fold_js_rules() : array(),
				/* پیام‌های جاوااسکریپت و خطِ رقم به زبان صفحه  ۴٫۴٫۲ */
				'i18n'   => topersia_render_js_i18n(),
				'digits' => topersia_render_js_digits(),
			)
		);

		/*
		 * انیمیشن هوا — دو فایل، و ترتیبشان اجباری است: نمایش از پایه
		 * مشتق می‌شود و بدون آن خودش را ثبت نمی‌کند.  P5-D197
		 *
		 * هر دو در پاورقی می‌نشینند و تا پاسخ هوا نرسد هیچ‌چیز روی صفحه
		 * نمی‌گذارند — سربرگ عنصر LCP است و نباید منتظر اینها بماند.
		 * بی‌سربرگ بی‌اثرند، مثل `topersia-g-zones.js`.
		 *
		 * نوعی که ماتریس هوا را برایش خاموش کرده (کشور — P5-D281) این دو
		 * پرونده را اصلاً نمی‌گیرد: بی کارت هوا انیمیشنی هم نیست.
		 */
		$entity = ( function_exists( 'is_singular' ) && is_singular() ) ? (int) get_queried_object_id() : 0;

		if ( $entity && function_exists( 'topersia_g_zone_on' ) && ! topersia_g_zone_on( $entity, 'tp-weather' ) ) {
			return;
		}

		wp_enqueue_script(
			'topersia-g-weather',
			$url . 'assets/topersia-g-weather.js',
			array(),
			topersia_render_asset_version( 'assets/topersia-g-weather.js' ),
			true
		);

		wp_enqueue_script(
			'topersia-g-weather-fx',
			$url . 'assets/topersia-g-weather-fx.js',
			array( 'topersia-g-weather' ),
			topersia_render_asset_version( 'assets/topersia-g-weather-fx.js' ),
			true
		);
	}
}

if ( topersia_render_ready() ) {
	add_action( 'wp_enqueue_scripts', 'topersia_render_enqueue_assets', 20 );
}

/*
 * کش عنصر المنتور برای ویجتی که شورت‌کد ما را دارد خاموش.  SPRINT-4.4 ۲ج
 *
 * المنتور ۴ خروجی هر عنصرِ «ایستا» را تا `elementor_element_cache_ttl` (این‌جا
 * ۲۴ ساعت) در متای پست نگه می‌دارد. بدنۀ [tp_page] ایستا نیست: از رابطه‌ها،
 * دروازۀ منتشرشده (P5-D247) و نسخۀ همین افزونه ساخته می‌شود — فرزندِ پیش‌نویس‌شده
 * یا به‌روزرسانی افزونه تا یک روز دیده نمی‌شد (در ۲ج روی صفحۀ موزه و غذا
 * دیده شد). ویجتی که تنظیمش `[tp_` دارد پویا اعلام می‌شود و هر بار ساخته
 * می‌شود؛ کش صفحه (اگر هست) کار خودش را می‌کند.
 */
add_filter( 'elementor/element/is_dynamic_content', function ( $dynamic, $raw = array() ) {

	if ( $dynamic || ! is_array( $raw ) || empty( $raw['settings'] ) ) {
		return $dynamic;
	}

	return false !== strpos( (string) wp_json_encode( $raw['settings'] ), '[tp_' );
}, 10, 2 );

/* -------------------------------------------------------------------------
   بازیابی پیش از نخستین رنگ‌آمیزی
   ------------------------------------------------------------------------- */

/**
 * اسکریپت درون‌خطی راه‌انداز در <head>.
 *
 * تنها چیزی که از سرور می‌آید، حالت پیش‌فرض است و از فهرست بستۀ
 * auto/light/evening/dark بیرون نمی‌رود.
 *
 * @return void
 */
if ( ! function_exists( 'topersia_render_boot_script' ) ) {
	function topersia_render_boot_script() {
		if ( ! topersia_render_ready() ) {
			return;
		}

		$default = (string) topersia_render_option( 'default_mode', 'auto' );

		if ( ! in_array( $default, array( 'auto', 'light', 'evening', 'dark' ), true ) ) {
			$default = 'auto';
		}

		echo "<script>/* ToPersia display boot */\n";
		/* `tp-js` — نشان اجرای جاوااسکریپت پیش از نخستین رنگ‌آمیزی؛ نوار
		   پایین موبایل فقط زیر آن پنهان آغاز می‌شود.  ۴٫۳٫۰ بند ۱۲ */
		echo "(function(){document.documentElement.classList.add('tp-js')})();";
		echo "(function(){try{var h=document.documentElement,g=function(k){return localStorage.getItem('tp-'+k)};";
		echo "var m=g('mode')||'" . esc_attr( $default ) . "',d=false;";
		echo "try{d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches}catch(e){}";
		echo "h.setAttribute('data-tp-mode',m==='auto'?(d?'dark':'light'):m);";
		echo "var s=g('scale');if(s){h.style.setProperty('--tp-scale',s)}";
		echo "var p=g('spacing');if(p){h.setAttribute('data-spacing',p)}";
		echo "var w=g('measure');if(w){h.setAttribute('data-measure',w)}";
		echo "['contrast','calm','motion'].forEach(function(k){var v=g(k);if(v){h.setAttribute('data-'+k,v)}});";
		echo "}catch(e){}})();</script>\n";
	}
}

if ( topersia_render_ready() ) {
	add_action( 'wp_head', 'topersia_render_boot_script', 1 );
}

/* -------------------------------------------------------------------------
   کلید حالت رنگ — در سربرگ  ·  P5-D159
   ------------------------------------------------------------------------- */

/**
 * نشانۀ هر حالت.
 *
 * @param string $mode light | evening | dark.
 * @return string
 */
if ( ! function_exists( 'topersia_render_mode_icon' ) ) {
	function topersia_render_mode_icon( $mode ) {
		$open = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">';
		$line = ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"';

		if ( 'light' === $mode ) {
			return $open . '<circle cx="12" cy="12" r="4.6"' . $line . '/>'
				. '<path d="M12 2.4V4.6M12 19.4v2.2M2.4 12h2.2M19.4 12h2.2'
				. 'M5.2 5.2l1.6 1.6M17.2 17.2l1.6 1.6M18.8 5.2l-1.6 1.6M6.8 17.2l-1.6 1.6"' . $line . '/></svg>';
		}

		if ( 'evening' === $mode ) {
			return $open . '<path d="M4 16h16"' . $line . '/>'
				. '<path d="M7.2 16a4.8 4.8 0 0 1 9.6 0"' . $line . '/>'
				. '<path d="M12 4.2v2.2M4.9 7.1l1.6 1.6M19.1 7.1l-1.6 1.6M2.6 20h18.8"' . $line . '/></svg>';
		}

		return $open . '<path d="M20 14.2A8.2 8.2 0 0 1 9.8 4 8.4 8.4 0 1 0 20 14.2Z"' . $line . '/></svg>';
	}
}

/**
 * کلید سه‌حالتۀ رنگ برای سربرگ.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_mode_switch' ) ) {
	function topersia_render_mode_switch() {
		if ( ! topersia_render_ready() ) {
			return '';
		}

		$modes = array(
			'light'   => esc_html__( 'روز', 'topersia' ),
			'evening' => esc_html__( 'بعدازظهر', 'topersia' ),
			'dark'    => esc_html__( 'شب', 'topersia' ),
		);

		$html = '<div class="tp-modes" role="radiogroup"'
			. ' data-tp-key="mode"'
			. ' data-tp-label="' . esc_attr__( 'حالت رنگ', 'topersia' ) . '"'
			. ' aria-label="' . esc_attr__( 'حالت رنگ', 'topersia' ) . '">';

		foreach ( $modes as $value => $label ) {
			$html .= '<button type="button" role="radio" aria-checked="false" tabindex="-1"'
				. ' class="tp-modes__btn tp-modes__btn--' . esc_attr( $value ) . '"'
				. ' data-tp-set="mode" data-tp-val="' . esc_attr( $value ) . '"'
				. ' title="' . esc_attr( $label ) . '">'
				. topersia_render_mode_icon( $value )
				. '<span class="tp-modes__label">' . esc_html( $label ) . '</span>'
				. '</button>';
		}

		$html .= '</div>';

		return $html;
	}
}

/* -------------------------------------------------------------------------
   پنل نمایش و خوانایی
   ------------------------------------------------------------------------- */

/**
 * گروه انتخاب انحصاری با معناشناسی رادیویی.
 *
 * @param string $legend  عنوان.
 * @param string $key     کلید تنظیم.
 * @param array  $options مقدار => برچسب.
 * @param string $extra   کلاس افزوده.
 * @return string
 */
if ( ! function_exists( 'topersia_render_a11y_radiogroup' ) ) {
	function topersia_render_a11y_radiogroup( $legend, $key, $options, $extra = '' ) {
		$legend_id = 'tp-lg-' . $key;
		$class     = ( '' === $extra ) ? '' : ' class="' . esc_attr( $extra ) . '"';

		$html  = '<fieldset' . $class . '>';
		$html .= '<legend id="' . esc_attr( $legend_id ) . '">' . esc_html( $legend ) . '</legend>';
		$html .= '<div class="tp-a11y__row" role="radiogroup"'
			. ' aria-labelledby="' . esc_attr( $legend_id ) . '"'
			. ' data-tp-key="' . esc_attr( $key ) . '"'
			. ' data-tp-label="' . esc_attr( $legend ) . '">';

		foreach ( $options as $value => $label ) {
			$html .= '<button type="button" role="radio" aria-checked="false" tabindex="-1"'
				. ' data-tp-set="' . esc_attr( $key ) . '"'
				. ' data-tp-val="' . esc_attr( $value ) . '">'
				. esc_html( $label )
				. '</button>';
		}

		$html .= '</div></fieldset>';

		return $html;
	}
}

/**
 * نشانۀ پنل.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_a11y_icon' ) ) {
	function topersia_render_a11y_icon() {
		return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
			. '<circle cx="12" cy="4.2" r="2.1"/>'
			. '<path d="M3.5 8.2 C7 9.6 17 9.6 20.5 8.2 M12 8.8 V14 M12 14 L8.6 21 M12 14 L15.4 21"'
			. ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>'
			. '</svg>';
	}
}

/**
 * دکمۀ باز کردن پنل.
 *
 * برچسب در موبایل با clip-path پنهان می‌شود نه display:none، وگرنه دکمه
 * برای صفحه‌خوان بی‌نام می‌شد.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_a11y_trigger' ) ) {
	function topersia_render_a11y_trigger() {
		if ( ! topersia_render_ready() ) {
			return '';
		}

		return '<button type="button" class="tp-a11y-btn" id="tp-a11y-trigger"'
			. ' aria-expanded="false" aria-controls="tp-a11y-panel">'
			. topersia_render_a11y_icon()
			. '<span class="tp-a11y-btn__label">' . esc_html__( 'نمایش و خوانایی', 'topersia' ) . '</span>'
			. '</button>';
	}
}

/**
 * بدنۀ پنل. حالت رنگ اینجا نیست؛ در سربرگ است. P5-D159
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_a11y_panel' ) ) {
	function topersia_render_a11y_panel() {
		if ( ! topersia_render_ready() ) {
			return '';
		}

		$html = '<div class="tp-a11y" id="tp-a11y-panel" hidden><div class="tp-a11y__in">';

		$html .= topersia_render_a11y_radiogroup(
			esc_html__( 'اندازۀ متن', 'topersia' ),
			'scale',
			array(
				'1'    => esc_html__( '۱۰۰٪', 'topersia' ),
				'1.15' => esc_html__( '۱۱۵٪', 'topersia' ),
				'1.3'  => esc_html__( '۱۳۰٪', 'topersia' ),
				'1.5'  => esc_html__( '۱۵۰٪', 'topersia' ),
			)
		);

		/*
		 * «فاصلۀ متن» هم‌زمان فاصلۀ سطر، پاراگراف و کلمه را می‌برد.
		 * فاصلۀ حروف عمداً نیست: خط فارسی متصل است و letter-spacing اتصال را می‌شکند.
		 */
		$html .= topersia_render_a11y_radiogroup(
			esc_html__( 'فاصلۀ متن', 'topersia' ),
			'spacing',
			array(
				'compact' => esc_html__( 'فشرده', 'topersia' ),
				'normal'  => esc_html__( 'استاندارد', 'topersia' ),
				'loose'   => esc_html__( 'باز', 'topersia' ),
			)
		);

		/* در موبایل ثابت شد بی‌اثر است؛ CSS زیر ۹۰۰px پنهانش می‌کند. */
		$html .= topersia_render_a11y_radiogroup(
			esc_html__( 'پهنای ستون متن', 'topersia' ),
			'measure',
			array(
				'narrow'   => esc_html__( 'باریک', 'topersia' ),
				'standard' => esc_html__( 'استاندارد', 'topersia' ),
			),
			'tp-a11y__measure'
		);

		$toggles = array(
			'contrast' => array( 'high', esc_html__( 'کنتراست بالا', 'topersia' ) ),
			'calm'     => array( 'on',   esc_html__( 'کاهش شدت رنگ و نور', 'topersia' ) ),
			'motion'   => array( 'off',  esc_html__( 'توقف حرکت و پخش خودکار', 'topersia' ) ),
		);

		$on_text  = esc_attr__( 'روشن', 'topersia' );
		$off_text = esc_attr__( 'خاموش', 'topersia' );

		$html .= '<fieldset class="tp-a11y--wide">';
		$html .= '<legend>' . esc_html__( 'راحتی چشم و حرکت', 'topersia' ) . '</legend>';
		$html .= '<div class="tp-a11y__row">';

		foreach ( $toggles as $key => $data ) {
			$html .= '<button type="button"'
				. ' data-tp-toggle="' . esc_attr( $key ) . '"'
				. ' data-tp-on="' . esc_attr( $data[0] ) . '"'
				. ' data-tp-on-text="' . $on_text . '"'
				. ' data-tp-off-text="' . $off_text . '"'
				. ' aria-pressed="false">'
				. esc_html( $data[1] )
				. '</button>';
		}

		$html .= '</div></fieldset>';

		$html .= '<p class="tp-a11y__foot">'
			. '<button type="button" class="tp-a11y__preset"'
			. ' data-tp-done="' . esc_attr__( 'خواندن آسان اعمال شد', 'topersia' ) . '">'
			. esc_html__( 'خواندن آسان', 'topersia' )
			. '</button>'
			. '<button type="button" class="tp-a11y__reset"'
			. ' data-tp-done="' . esc_attr__( 'تنظیمات به پیش‌فرض بازگشت', 'topersia' ) . '">'
			. esc_html__( 'بازگرداندن به پیش‌فرض', 'topersia' )
			. '</button>'
			. '<span class="tp-a11y__note">'
			. esc_html__( 'انتخاب شما روی همین دستگاه ذخیره می‌شود.', 'topersia' )
			. '</span></p>';

		/* ناحیۀ اعلام. سبک درون‌خطی است تا اگر شیوه‌نامه دیر رسید یا کش قدیمی
		   سرو شد، این متن وسط صفحه ظاهر نشود. */
		$html .= '<p id="tp-a11y-status" role="status" aria-live="polite" class="tp-vh"'
			. ' style="position:absolute;width:1px;height:1px;margin:-1px;padding:0;'
			. 'overflow:hidden;clip-path:inset(50%);white-space:nowrap;border:0"></p>';

		$html .= '</div></div>';

		return $html;
	}
}

/**
 * دکمه و پنل با هم — فقط بیرون از ردیف فلکس.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_a11y_block' ) ) {
	function topersia_render_a11y_block() {
		return topersia_render_a11y_trigger() . topersia_render_a11y_panel();
	}
}

/**
 * پیوند پرش به محتوا.
 *
 * @return string
 */
if ( ! function_exists( 'topersia_render_skip_link' ) ) {
	function topersia_render_skip_link() {
		if ( ! topersia_render_ready() ) {
			return '';
		}

		return '<a class="tp-skip" href="#tp-main">'
			. esc_html__( 'پرش به محتوای اصلی', 'topersia' )
			. '</a>';
	}
}

if ( topersia_render_ready() ) {
	add_shortcode( 'topersia_mode_switch', 'topersia_render_mode_switch' );
	add_shortcode( 'topersia_display_button', 'topersia_render_a11y_trigger' );
	add_shortcode( 'topersia_display_panel', 'topersia_render_a11y_panel' );
	add_shortcode( 'topersia_display_block', 'topersia_render_a11y_block' );
	add_shortcode( 'topersia_skip_link', 'topersia_render_skip_link' );
}

/* -------------------------------------------------------------------------
   کلاس پوسته روی <body>
   ------------------------------------------------------------------------- */

/**
 * افزودن کلاس‌های لایۀ نمایش.
 *
 * @param array $classes کلاس‌های موجود.
 * @return array
 */
if ( ! function_exists( 'topersia_render_body_class' ) ) {
	function topersia_render_body_class( $classes ) {
		if ( ! is_array( $classes ) ) {
			return $classes;
		}

		$classes[] = 'tp-shell';

		if ( topersia_render_option( 'own_typography', 1 ) ) {
			$classes[] = 'tp-own-type';
		}

		return $classes;
	}
}

if ( topersia_render_ready() ) {
	add_filter( 'body_class', 'topersia_render_body_class' );
}
