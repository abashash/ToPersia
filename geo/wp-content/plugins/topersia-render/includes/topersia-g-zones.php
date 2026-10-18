<?php
/**
 * ToPersia — نواحی نمایش دستۀ G
 *
 * پانزده شورت‌کد که محتوای هر ناحیۀ صفحۀ موجودیت جغرافیایی را می‌سازند.
 *
 * ── مرز معماری ────────────────────────────────────────────────────────
 * المنتور اسکلت چیدمان می‌سازد. این فایل محتوای هر ناحیه را تولید می‌کند.
 * المنتور نباید ۵۷ متا و ۳۴ رابطه را بشناسد؛ اگر بشناسد، هر تغییر داده
 * یعنی ویرایش دستی قالب و شکستن قاعدۀ تک‌تولیدکنندۀ P5-D145 — چون همان
 * داده باید در JSON-LD هم برود.
 *
 * ── قواعد ثابت ────────────────────────────────────────────────────────
 * هر بخش بدون داده کامل ناپدید می‌شود؛ سرفصل خالی نمی‌ماند.
 * عدد با رقم فارسی و جداکنندۀ ٬ · سال بدون جداکننده · لاتین مونو و LTR.
 * هیچ رنگ ثابتی اینجا نوشته نمی‌شود؛ رنگ از توکن‌های --tp-* می‌آید.
 * letter-spacing روی متن فارسی ممنوع است.
 * دادۀ زنده — هوا و وضعیت ذخیره — در HTML کش‌شده نمی‌آید.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
   ۱ — ابزارهای پایه
   ========================================================================= */

/** شناسۀ پست جاری، با احترام به حلقۀ المنتور و JetEngine. */
if ( ! function_exists( 'topersia_g_pid' ) ) {
	function topersia_g_pid( $atts = array() ) {

		if ( ! empty( $atts['id'] ) ) {
			return absint( $atts['id'] );
		}

		if ( function_exists( 'get_the_ID' ) ) {
			$id = get_the_ID();

			if ( $id ) {
				return absint( $id );
			}
		}

		return 0;
	}
}

/**
 * زبان صفحه — fa · en · ar. بی WPML همان فارسی.
 */
if ( ! function_exists( 'topersia_g_page_lang' ) ) {
	function topersia_g_page_lang() {

		$lang = function_exists( 'apply_filters' ) ? apply_filters( 'wpml_current_language', null ) : null;
		$lang = is_string( $lang ) ? strtolower( substr( $lang, 0, 2 ) ) : '';

		return in_array( $lang, array( 'fa', 'en', 'ar' ), true ) ? $lang : 'fa';
	}
}

/**
 * $fn در زبان پیش‌فرض WPML، و بازگرداندن زبان پیشین — حتی با خطا. فقط قلاب‌های
 * مستند WPML (wpml_default_language · wpml_current_language · wpml_switch_language).
 * گالری تجمیعی (۲ج٫۵) و آمار حقیقی (P5-D306) هر دو از این‌جا.
 */
if ( ! function_exists( 'topersia_g_in_default_lang' ) ) {
	function topersia_g_in_default_lang( callable $fn ) {

		$default = function_exists( 'apply_filters' ) ? apply_filters( 'wpml_default_language', null ) : null;
		$current = function_exists( 'apply_filters' ) ? apply_filters( 'wpml_current_language', null ) : null;

		if ( ! $default || ! $current || $default === $current ) {
			return $fn();
		}

		/*
		 * پیش‌نمایش پیش‌نویس: WPML زبان پست پیش‌نمایش را بر هر تعویض زبان می‌نشاند
		 * (WPML_Language_Resolution::current_lang_filter) و تعویض بی‌اثر می‌ماند — شمار
		 * «استان‌ها» روی ترجمۀ پیش‌نویس ایران ۱ می‌شد. فقط در همین بازه خاموش.  P5-D306
		 */
		$no_preview = function_exists( 'add_filter' ) && add_filter( 'wpml_should_filter_preview_lang', '__return_false', 99 );

		do_action( 'wpml_switch_language', $default );

		try {
			return $fn();
		} finally {
			do_action( 'wpml_switch_language', $current );
			if ( $no_preview ) {
				remove_filter( 'wpml_should_filter_preview_lang', '__return_false', 99 );
				/* بازگشت به زبان صفحه، با همان قاعدۀ پیش‌نمایش */
				do_action( 'wpml_switch_language', $current );
			}
		}
	}
}

/** پست زبان پیش‌فرض WPML (یا خودش). */
if ( ! function_exists( 'topersia_g_default_id' ) ) {
	function topersia_g_default_id( $post_id ) {

		$post_id = absint( $post_id );
		$type    = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';
		$default = function_exists( 'apply_filters' ) ? apply_filters( 'wpml_default_language', null ) : null;

		if ( '' === $type || ! $default ) {
			return $post_id;
		}

		$id = absint( apply_filters( 'wpml_object_id', $post_id, $type, false, $default ) );

		return $id ? $id : $post_id;
	}
}

/**
 * رشتۀ رابط به زبان صفحه.  P5-D146 · ۴٫۴٫۲
 *
 * منبع هر برچسب فارسی است و ترجمه‌اش در کاتالوگ `topersia` افزونه
 * (`languages/topersia-en_US` · `languages/topersia-ar`). برچسب‌هایی که در
 * جدول‌اند — نقشۀ فیلد، نقشۀ رابطه، نوار بخش‌ها — از همین‌جا در خروجی
 * ترجمه می‌شوند؛ رشتۀ ثابتِ درون خط همان `__()` است.
 */
if ( ! function_exists( 'topersia_g_t' ) ) {
	function topersia_g_t( $text ) {

		$text = (string) $text;

		if ( '' === $text || ! function_exists( '__' ) ) {
			return $text;
		}

		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- برچسبِ جدول؛ کاتالوگ از خودِ جدول ساخته می‌شود (scripts/tp-i18n.php).
		return __( $text, 'topersia' );
	}
}

/**
 * رقم به خطِ زبان صفحه — فارسی «۰–۹»، عربی «٠–٩»، بقیه لاتین.  ۴٫۴٫۲
 *
 * نام تاریخی مانده (همۀ فراخوان‌ها و آزمون‌ها همین را می‌شناسند). ورودی
 * ممکن است از پیش رقم فارسی یا عربی داشته باشد — مثلاً سالی که در داده
 * فارسی نوشته شده — پس هر سه خط به خطِ مقصد برده می‌شوند.
 */
if ( ! function_exists( 'topersia_g_fa' ) ) {
	function topersia_g_fa( $text ) {

		$sets = array(
			'latn' => array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
			'fa'   => array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ),
			'ar'   => array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ),
		);

		$lang = topersia_g_page_lang();
		$to   = isset( $sets[ $lang ] ) ? $sets[ $lang ] : $sets['latn'];
		$text = (string) $text;

		foreach ( $sets as $from ) {
			if ( $from !== $to ) {
				$text = str_replace( $from, $to, $text );
			}
		}

		return $text;
	}
}

/**
 * متن به زبانی جز زبان صفحه؟ — برای متن پیوست (عنوان، توضیح، alt) که WPML
 * رسانه حتی در نسخۀ انگلیسیِ پیوست فارسی رونوشت می‌کند.  P5-D278
 *
 * چیرگی، نه «یک نویسه»: متن انگلیسی‌ای که نامی فارسی در پرانتز دارد بیگانه
 * نیست. صفحۀ فارسی: هرگز. صفحۀ عربی: بیش از سه درصد حروفش ویژۀ فارسی
 * (پ چ ژ گ ی ک — «ی» و «ک» در متن فارسی پرکاربردند، در عربی نه). بقیه: حرف
 * عربی‌خط بیش از لاتین — زبان محتوای اصلی فارسی است.
 */
if ( ! function_exists( 'topersia_g_foreign_text' ) ) {
	function topersia_g_foreign_text( $text ) {

		$text = trim( (string) $text );
		$lang = topersia_g_page_lang();

		if ( '' === $text || 'fa' === $lang ) {
			return false;
		}

		$arabic = (int) preg_match_all( '/\p{Arabic}/u', $text );

		if ( ! $arabic ) {
			return false;
		}

		if ( 'ar' === $lang ) {
			return (int) preg_match_all( '/[\x{067E}\x{0686}\x{0698}\x{06AF}\x{06CC}\x{06A9}]/u', $text ) > 0.03 * $arabic;
		}

		return $arabic > (int) preg_match_all( '/\p{Latin}/u', $text );
	}
}

/**
 * متن خامِ آمیخته ← HTML گریخته: در صفحۀ غیرفارسی رقم به زبان صفحه (پیش از
 * گریز، تا نهاد عددی HTML دست نخورد)؛ و در صفحۀ لاتین‌خط هر تکۀ عربی‌خط با زبان
 * خودش (`<bdi lang="fa">`) — منبعی که نام فارسی در عنوان انگلیسی‌اش دارد،
 * دامنۀ «.ایران» در کد.  P5-D278
 */
if ( ! function_exists( 'topersia_g_lang_runs' ) ) {
	function topersia_g_lang_runs( $text ) {

		$lang = topersia_g_page_lang();

		if ( 'fa' === $lang ) {
			return esc_html( (string) $text );
		}

		$html = esc_html( topersia_g_fa( (string) $text ) );

		/* صفحۀ عربی: فقط تکه‌ای که خودش چیره‌فارسی است («.ایران»)، نه جملۀ عربیِ دارای نامی فارسی */
		return (string) preg_replace_callback( '/\p{Arabic}+(?:[\s\x{200C}،؛:.()«»\-—]*\p{Arabic}+)*/u', function ( $m ) use ( $lang ) {
			return ( 'ar' !== $lang || topersia_g_foreign_text( $m[0] ) ) ? '<bdi lang="fa" dir="rtl">' . $m[0] . '</bdi>' : $m[0];
		}, $html );
	}
}

/**
 * فیلدِ به زبان دیگر؟ — در صفحۀ غیرفارسی، فیلدی که متنش چیره به زبان دیگر است
 * خالی شمرده می‌شود: ناحیه‌اش نمی‌آید.  P5-D278
 *
 * هر دو راه به این‌جا می‌رسند: ترجمه‌ای که از ویرایشگر WPML با رونوشت متن ساخته
 * و هرگز ترجمه نشد (کلاس translate با متن فارسی)، و متن بلندی که WPML «copy»
 * دارد و متن فارسی اصل را روی هر زبان می‌نشاند. کلاس WPML ملاک نیست — زبان
 * خودِ متن است.
 *
 * بیرون: نام‌ها (رسمی، محلی، دیگر) و منبع‌ها — به زبان دیگر بودنشان درست است و
 * با lang="fa" نشان داده می‌شوند؛ و فیلد «latin» (کد) — بخش عربی‌خطش با bdi.
 */
if ( ! function_exists( 'topersia_g_untranslated' ) ) {
	function topersia_g_untranslated( $key, $value ) {

		static $keep = array( 'name_fa', 'name_en', 'name_ar', 'official_name_fa', 'official_name_en', 'official_name_ar', 'name_local', 'alternative_names', 'source_references' );

		if ( '' === $value || null === $value || in_array( $key, $keep, true ) || 'fa' === topersia_g_page_lang() ) {
			return false;
		}

		$map = topersia_g_map();

		if ( isset( $map[ $key ]['kind'] ) && 'latin' === $map[ $key ]['kind'] ) {
			return false;
		}

		/* آرایه (repeater، چندگزینه‌ای): فقط مقدارها — کلیدهای لاتین زیرفیلد چیرگی متن فارسی را نپوشانند */
		if ( is_array( $value ) || is_object( $value ) ) {
			$parts = array();
			array_walk_recursive( $value, function ( $v ) use ( &$parts ) {
				if ( is_scalar( $v ) ) {
					$parts[] = (string) $v;
				}
			} );
			$text = implode( ' ', $parts );
		} else {
			$text = (string) $value;
		}

		return topersia_g_foreign_text( wp_strip_all_tags( $text ) );
	}
}

/**
 * جداکنندۀ فهرست به جهت صفحه — «، » در راست‌به‌چپ، «, » در چپ‌به‌راست.  ۴٫۴٫۲
 */
if ( ! function_exists( 'topersia_g_list_sep' ) ) {
	function topersia_g_list_sep() {
		return topersia_g_is_rtl() ? '، ' : ', ';
	}
}

/**
 * جداکنندۀ هزارگان — U+066C در فارسی و عربی، «,» در بقیه.  ۴٫۴٫۲
 *
 * فقط روی بخش صحیح اعمال می‌شود؛ بخش اعشاری دست‌نخورده می‌ماند.
 */
if ( ! function_exists( 'topersia_g_sep' ) ) {
	function topersia_g_sep( $number ) {

		$raw = trim( (string) $number );

		if ( '' === $raw ) {
			return '';
		}

		$sign = '';

		if ( '-' === substr( $raw, 0, 1 ) ) {
			$sign = '-';
			$raw  = substr( $raw, 1 );
		}

		$parts    = explode( '.', $raw, 2 );
		$integer  = preg_replace( '/[^0-9]/', '', $parts[0] );
		$fraction = isset( $parts[1] ) ? preg_replace( '/[^0-9]/', '', $parts[1] ) : '';

		if ( '' === $integer ) {
			return (string) $number;
		}

		$grouped = '';
		$length  = strlen( $integer );
		$mark    = in_array( topersia_g_page_lang(), array( 'fa', 'ar' ), true ) ? '٬' : ',';

		for ( $i = 0; $i < $length; $i++ ) {

			if ( $i > 0 && 0 === ( ( $length - $i ) % 3 ) ) {
				$grouped .= $mark;
			}

			$grouped .= $integer[ $i ];
		}

		return $sign . $grouped . ( '' !== $fraction ? '.' . $fraction : '' );
	}
}

/**
 * آیا این مقدار «پر» است؟
 *
 * قطبیت عمدی: هر چیزی جز خالی و «نادرست صریح»، پر است. شکست باید به سمت
 * نمایش باشد نه ناپدیدشدن — همان قاعده‌ای که topersia_active_rows دارد.
 */
if ( ! function_exists( 'topersia_g_filled' ) ) {
	function topersia_g_filled( $value ) {

		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return false;
		}

		return ! in_array( $value, array( '0', 'false', 'no', 'off' ), true );
	}
}

/** آیا این سوییچر روشن است؟ */
if ( ! function_exists( 'topersia_g_flag_on' ) ) {
	function topersia_g_flag_on( $value ) {

		if ( is_bool( $value ) ) {
			return $value;
		}

		$value = strtolower( trim( (string) $value ) );

		return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
	}
}

/** متای خام — بدون پاک‌سازی، چون ریپیتر و گالری آرایه‌اند. */
if ( ! function_exists( 'topersia_g_raw' ) ) {
	function topersia_g_raw( $post_id, $key ) {

		if ( ! $post_id || ! function_exists( 'get_post_meta' ) ) {
			return '';
		}

		$value = get_post_meta( $post_id, $key, true );

		/* ترجمه‌پذیرِ ترجمه‌نشده در صفحۀ غیرفارسی — خالی  P5-D278 */
		if ( topersia_g_untranslated( $key, $value ) ) {
			return is_array( $value ) ? array() : '';
		}

		return $value;
	}
}

/** متن ساده و امن. */
if ( ! function_exists( 'topersia_g_text' ) ) {
	function topersia_g_text( $post_id, $key ) {

		$value = topersia_g_raw( $post_id, $key );

		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return trim( wp_strip_all_tags( (string) $value ) );
	}
}

/**
 * مقدار یک فیلد، آمادۀ نمایش، بر پایۀ «نوع» نه نوع پست.
 *
 * @param string $name نام فیلد.
 * @param mixed  $raw  مقدار خام.
 * @return string HTML
 */
if ( ! function_exists( 'topersia_g_value' ) ) {
	function topersia_g_value( $name, $raw, $post_id = 0 ) {

		$map   = topersia_g_map();
		$field = isset( $map[ $name ] ) ? $map[ $name ] : array( 'kind' => 'text', 'unit' => '' );
		$kind  = isset( $field['kind'] ) ? $field['kind'] : 'text';
		$unit  = isset( $field['unit'] ) ? $field['unit'] : '';

		if ( is_array( $raw ) ) {
			$raw = implode( topersia_g_list_sep(), array_filter( array_map( 'strval', $raw ) ) );
		}

		$raw = trim( (string) $raw );

		if ( '' === $raw ) {
			return '';
		}

		switch ( $kind ) {

			case 'number':
				$out = topersia_g_fa( topersia_g_sep( $raw ) );

				if ( '' !== $unit ) {
					$out .= ' <i class="tp-u">' . esc_html( topersia_g_t( $unit ) ) . '</i>';
				}

				return $out;

			case 'year':
				return topersia_g_fa( preg_replace( '/[^0-9۰-۹]/u', '', $raw ) );

			case 'latin':
				/*
				 * کد لاتین با رقم زبان صفحه — «+۹۸» ذخیره‌شده در /en/ «+98». بخش
				 * عربی‌خطِ کد (دامنۀ «.ایران») در صفحۀ غیرفارسی با زبان خودش.  P5-D278
				 */
				return '<span class="tp-lat" dir="ltr">' . topersia_g_lang_runs( topersia_g_fa( $raw ) ) . '</span>';

			case 'choice':
				$glossary = isset( $field['glossary'] ) ? $field['glossary'] : '';

				/* یک نام، واژه‌نامۀ هر نوع (`classification`) — ۲ج٫۱ */
				if ( is_array( $glossary ) ) {
					$type     = ( $post_id && function_exists( 'get_post_type' ) ) ? (string) get_post_type( $post_id ) : '';
					$glossary = isset( $glossary[ $type ] ) ? $glossary[ $type ] : '';
				}

				return esc_html( topersia_g_glossary_label( $glossary, $raw ) );

			case 'date':
				/* تاریخ رویداد: متن به زبان صفحه + <time datetime> ماشین‌خوان؛ نامعتبر ← خالی.  P5-D357 */
				return topersia_g_date_html( $raw );

			case 'flag':
				/* پرچم بیرون از «نشان‌ها» (کارت حقایق، «اطلاعات سفر») فقط روشن می‌آید: «بله».  P5-D279 */
				return topersia_g_flag_on( $raw ) ? esc_html__( 'بله', 'topersia' ) : '';
		}

		/* متن آزاد: رقم به زبان صفحه («۱۶۰۳» نه «1603»؛ /en/ «+03:30») — کد لاتین (latin) دست نمی‌خورد.  P5-D278 */
		return esc_html( topersia_g_fa( $raw ) );
	}
}

/**
 * پوستۀ یک بخش. بدنۀ خالی یعنی هیچ خروجی — نه سرفصل خالی.
 */
if ( ! function_exists( 'topersia_g_section' ) ) {
	function topersia_g_section( $id, $title, $kicker, $body, $class = 'tp-rail-card', $body_style = '', $after = '', $head_extra = '' ) {

		$body = trim( (string) $body );

		if ( '' === $body ) {
			return '';
		}

		$head = '';

		/*
		 * کیکرِ لاتینِ کنار عنوان (MAP · FORECAST · WHEN · GEO …) دیگر چاپ
		 * نمی‌شود.  P5-D240 — در نسخۀ انگلیسی همان عنوان را تکرار می‌کرد و
		 * در فارسی واژۀ بیگانه بود. پارامتر برای سازگاری فراخوان‌ها مانده.
		 */
		unset( $kicker );

		/* عنوان از جدول می‌آید (نقشۀ رابطه، شورت‌کدها) — ترجمه همین‌جا.  ۴٫۴٫۲ */
		$title = topersia_g_t( $title );

		if ( '' !== (string) $title ) {

			/*
			 * پیوند «همه …» پس از h3: سربرگ مرجع flex با space-between است، پس عنوان در آغاز و
			 * پیوند در انتهای سطر می‌نشیند؛ سبک سربرگ و h3 دست نخورد و ترتیب خواندن هم طبیعی است.  ۲ه٫۴
			 */
			$head = '<div class="tp-rail-card__head">'
				. '<h3 id="' . esc_attr( $id ) . 'Title">' . esc_html( $title ) . '</h3>'
				. $head_extra
				. '</div>';
		}

		return '<section id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '"'
			. ( '' !== (string) $title ? ' aria-labelledby="' . esc_attr( $id ) . 'Title"' : '' )
			. '>' . $head . '<div class="tp-rail-card__body"'
			. ( '' !== $body_style ? ' style="' . esc_attr( $body_style ) . '"' : '' )
			. '>' . $body . '</div>' . $after . '</section>';
	}
}

/** فیلدهای یک ناحیه که این پست واقعاً دارد، به ترتیب نقشه. */
if ( ! function_exists( 'topersia_g_zone_fields' ) ) {
	function topersia_g_zone_fields( $post_id, $zone, $exclude = array() ) {

		$out = array();

		foreach ( topersia_g_map() as $name => $field ) {

			if ( ! isset( $field['zone'] ) || $field['zone'] !== $zone ) {
				continue;
			}

			if ( in_array( $name, $exclude, true ) ) {
				continue;
			}

			$raw = topersia_g_raw( $post_id, $name );

			if ( ! topersia_g_filled( $raw ) ) {
				continue;
			}

			/* پرچمِ خاموش «پر» است ولی چیزی نمی‌گوید.  P5-D279 */
			if ( isset( $field['kind'] ) && 'flag' === $field['kind'] && ! topersia_g_flag_on( $raw ) ) {
				continue;
			}

			$out[ $name ] = $raw;
		}

		return $out;
	}
}

/* =========================================================================
   ۲ — روابط
   ========================================================================= */

/** پیکربندی یک گروه رابطه برای این نوع پست. */
if ( ! function_exists( 'topersia_g_group' ) ) {
	function topersia_g_group( $post_id, $key ) {

		$type = function_exists( 'get_post_type' ) ? get_post_type( $post_id ) : '';
		$map  = topersia_g_relmap();

		if ( ! $type || ! isset( $map[ $type ][ $key ] ) ) {
			return array();
		}

		return $map[ $type ][ $key ];
	}
}

/**
 * نوع‌هایی که با وجود ثبت عمومی، صفحۀ محتوایی ندارند.
 *
 * فقط شهرستان: موجودیت زیرساخت است (`P5-D172`، `D-010`). در پیکربندی
 * JetEngine هنوز `publicly_queryable` دارد و نشانی‌اش پاسخ می‌دهد؛ طبق
 * CHANGE-PACKAGE-001 بند ۶٫۱ باید به استانش ریدایرکت شود، که کار
 * `topersia-core` است. تا آن روز این‌جا صریح کنار گذاشته می‌شود.
 */
if ( ! function_exists( 'topersia_g_no_page_types' ) ) {
	function topersia_g_no_page_types() {

		$types = array( 'tp_county' );

		if ( function_exists( 'apply_filters' ) ) {
			$types = (array) apply_filters( 'topersia_g_no_page_types', $types );
		}

		return $types;
	}
}

/**
 * نشانیِ عمومی — یا رشتۀ خالی اگر آن پست صفحه ندارد.  ۴٫۳٫۰ بند ۰
 *
 * هر جا نشانی خالی برگردد، کارت `<article>` می‌شود نه `<a>`.
 *
 * تا ۴٫۲٫۳ مبنا نقشۀ رابطه بود: نوعی که در `topersia_g_relmap()` مدخل
 * داشت صفحه داشت، یعنی فقط سیزده نوع G. آن فرض غلط بود — هر نوع موجودیت
 * عمومی صفحه دارد — و کارت‌های جاذبه، محوطۀ باستانی، مسیر، فرهنگ و
 * هم‌مرز بی‌پیوند می‌ماندند. حالا سه شرط:
 *
 *   ۱ نوعش `publicly_queryable` است — همان تنظیمی که وردپرس با آن نشانی
 *     را پاسخ می‌دهد
 *   ۲ خودِ پست منتشر شده — پیش‌نویس و زباله‌دان ۴۰۴ می‌دهند
 *   ۳ در `topersia_g_no_page_types()` نیست
 */
if ( ! function_exists( 'topersia_g_public_url' ) ) {
	function topersia_g_public_url( $post_id ) {

		$post_id = absint( $post_id );
		$type    = ( $post_id && function_exists( 'get_post_type' ) ) ? (string) get_post_type( $post_id ) : '';

		if ( '' === $type || in_array( $type, topersia_g_no_page_types(), true ) ) {
			return '';
		}

		$object = function_exists( 'get_post_type_object' ) ? get_post_type_object( $type ) : null;

		if ( ! $object || empty( $object->publicly_queryable ) ) {
			return '';
		}

		if ( function_exists( 'get_post_status' ) && 'publish' !== get_post_status( $post_id ) ) {
			return '';
		}

		return (string) get_permalink( $post_id );
	}
}

if ( ! function_exists( 'topersia_g_sort_entities' ) ) {
	/**
	 * ترتیب کارت‌های موجودیتِ ماندگار — یک قاعده برای همه.  P5-D211
	 *
	 *   ۱ `display_priority` نزولی
	 *   ۲ جانشین نوع — شهر و روستا: `population` نزولی
	 *   ۳ الفبای فارسی
	 *
	 * `display_priority` هنوز فیلد نیست. این‌جا **ساخته نمی‌شود**: متا
	 * خوانده می‌شود و نبودش صفر حساب می‌شود. روزی که در JetEngine ساخته
	 * شود، بی‌هیچ تغییری در کد اثر می‌گذارد.
	 *
	 * الفبا با `Collator` فارسی است، نه `strcmp`: «آ» و «ا» و «ء» در
	 * بایت‌ها فاصله دارند ولی در الفبا کنار هم‌اند. اگر `intl` نبود،
	 * `strcoll` جای آن می‌نشیند — بدتر از Collator، بهتر از بایت.
	 */
	function topersia_g_sort_entities( $ids ) {

		$ids = array_values( array_unique( array_map( 'absint', (array) $ids ) ) );

		if ( count( $ids ) < 2 ) {
			return $ids;
		}

		$rank = array();

		foreach ( $ids as $one ) {

			$view = topersia_g_view_id( $one );

			$rank[ $one ] = array(
				'priority' => (float) topersia_g_raw( $view, 'display_priority' ),
				'fallback' => (float) topersia_g_raw( $view, 'population' ),
				'name'     => (string) topersia_g_name( $view ),
			);
		}

		$collator = class_exists( 'Collator' ) ? new Collator( 'fa' ) : null;

		usort( $ids, function ( $a, $b ) use ( $rank, $collator ) {

			if ( $rank[ $a ]['priority'] !== $rank[ $b ]['priority'] ) {
				return $rank[ $a ]['priority'] < $rank[ $b ]['priority'] ? 1 : -1;
			}

			if ( $rank[ $a ]['fallback'] !== $rank[ $b ]['fallback'] ) {
				return $rank[ $a ]['fallback'] < $rank[ $b ]['fallback'] ? 1 : -1;
			}

			return $collator
				? (int) $collator->compare( $rank[ $a ]['name'], $rank[ $b ]['name'] )
				: strcoll( $rank[ $a ]['name'], $rank[ $b ]['name'] );
		} );

		return $ids;
	}
}

if ( ! function_exists( 'topersia_g_chain_ids' ) ) {
	/**
	 * زنجیرۀ رابطه — چند پله پشت سر هم.  P5-D210
	 *
	 * استان شهر ندارد؛ شهرستان دارد. ولی شهرستان موجودیتِ زیرساخت است و
	 * صفحه ندارد (`P5-D172`)، پس آنچه روی صفحۀ استان باید بیاید شهرهای
	 * آن است: ۷۲ (استان→شهرستان) و بعد ۷۳ (شهرستان→شهر).
	 *
	 * هر پله روی **همۀ** نتیجه‌های پلۀ قبل اجرا می‌شود و تکراری‌ها یک‌بار
	 * می‌مانند — دو شهرستان می‌توانند به یک شهر وصل باشند.
	 *
	 * @param array $chain فهرست مرتبِ spec ها.
	 * @return array شناسه‌ها.
	 */
	function topersia_g_chain_ids( $post_id, $chain ) {

		if ( ! is_array( $chain ) || ! $chain ) {
			return array();
		}

		$cur  = array( absint( $post_id ) );
		$last = count( $chain ) - 1;

		/* پله‌های میانی فقط پیموده می‌شوند؛ پلۀ آخر نمایش است.  P5-D247 */
		foreach ( array_values( $chain ) as $step => $spec ) {

			$next = array();

			foreach ( $cur as $one ) {
				foreach ( topersia_g_rel_ids( $one, array( $spec ), $step === $last ) as $found ) {
					$next[ $found ] = $found;
				}
			}

			$cur = array_values( $next );

			if ( ! $cur ) {
				return array();
			}
		}

		return $cur;
	}
}

/**
 * یک پست: منتشرشده و بی‌رمز.  P5-D247
 */
if ( ! function_exists( 'topersia_g_is_live' ) ) {
	function topersia_g_is_live( $post_id ) {

		if ( function_exists( 'get_post' ) ) {
			$post = get_post( $post_id );
			return $post && 'publish' === $post->post_status && '' === (string) $post->post_password;
		}

		return function_exists( 'get_post_status' ) && 'publish' === get_post_status( $post_id );
	}
}

/**
 * فقط منتشرشده — یک دروازه برای هر خروجی عمومی.  P5-D247
 *
 * شناسه‌ای می‌ماند که:
 *   ۱ پستش هست و `post_status` آن `publish` است
 *   ۲ رمز ندارد — پستِ رمزدار در فهرست عمومی نامش را لو می‌دهد
 *   ۳ با WPML، نسخۀ زبان جاری هست و آن هم منتشرشده و بی‌رمز است
 *
 * حتی اگر رابطه یا متا به پستی منتشرنشده اشاره کند، آن پست نمی‌آید —
 * نه کارت، نه نام در مسیر راهنما، نه عضو پنجره، نه بافت هوش مصنوعی.
 * ترتیب ورودی حفظ می‌شود و هر موجودیت یک بار.
 *
 * با WPML شناسۀ **نسخۀ زبان جاری** برمی‌گردد، نه شناسۀ ورودی. رابطه ممکن
 * است به ترجمۀ زبان دیگری اشاره کند: همگام‌ساز WPML جت‌انجین پس از هر
 * ترجمه ردیفِ «ترجمه ← طرف مقابل» می‌سازد، و دادۀ زنده از پیش استان
 * فارسی را به مسیرِ انگلیسی وصل دارد. اگر شناسۀ ورودی برمی‌گشت، صفحۀ
 * فارسیِ هم‌مرز اصفهان را سه بار نشان می‌داد (فارسی، انگلیسی، عربی) —
 * هر سه به نسخۀ فارسی می‌رسند. پس یک موجودیت، یک بار، در زبان صفحه.
 *
 * @param array $ids شناسه‌ها.
 * @return array<int,int>
 */
if ( ! function_exists( 'topersia_g_published' ) ) {
	function topersia_g_published( array $ids ) {

		$out  = array();
		$lang = function_exists( 'apply_filters' ) ? apply_filters( 'wpml_current_language', null ) : null;
		$lang = ( is_string( $lang ) && '' !== $lang && 'all' !== $lang ) ? $lang : '';

		foreach ( $ids as $one ) {

			$one = absint( $one );

			if ( ! $one || isset( $out[ $one ] ) || ! topersia_g_is_live( $one ) ) {
				continue;
			}

			if ( '' !== $lang ) {

				$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $one ) : '';
				$here = absint( apply_filters( 'wpml_object_id', $one, $type, false, $lang ) );

				if ( ! $here || ( $here !== $one && ! topersia_g_is_live( $here ) ) ) {
					continue;
				}

				$one = $here;
			}

			$out[ $one ] = $one;
		}

		return array_values( $out );
	}
}

/**
 * شناسه‌های طرف مقابل یک یا چند رابطه.
 *
 * جهت از export گرفته می‌شود، نه از سند روابط.  P5-D170
 *
 * پیش‌فرض فقط منتشرشده (`P5-D247`). `$published = false` فقط برای
 * پیمودن است، نه نمایش: زنجیرۀ استان ← شهرستانِ پیش‌نویس ← شهرِ
 * منتشرشده نباید شهر را گم کند، و مسیر راهنما باید از نیای پیش‌نویس
 * بگذرد بی‌آنکه نامش را بیاورد.
 */
if ( ! function_exists( 'topersia_g_rel_ids' ) ) {
	function topersia_g_rel_ids( $post_id, $rels, $published = true ) {

		$ids = array();

		if ( ! is_array( $rels ) || ! function_exists( 'topersia_relation' ) ) {
			return $ids;
		}

		foreach ( $rels as $spec ) {

			$id  = isset( $spec['id'] ) ? absint( $spec['id'] ) : 0;
			$dir = isset( $spec['dir'] ) ? $spec['dir'] : 'children';

			if ( ! $id ) {
				continue;
			}

			$relation = topersia_relation( $id );

			if ( ! $relation ) {
				continue;
			}

			$found = ( 'parents' === $dir )
				? $relation->get_parents( $post_id, 'ids' )
				: $relation->get_children( $post_id, 'ids' );

			if ( empty( $found ) || ! is_array( $found ) ) {
				continue;
			}

			foreach ( $found as $one ) {

				$one = absint( $one );

				if ( $one ) {
					$ids[ $one ] = $one;
				}
			}
		}

		return $published ? topersia_g_published( array_values( $ids ) ) : array_values( $ids );
	}
}

/** شناسه را به نسخۀ زبان جاری می‌برد. */
if ( ! function_exists( 'topersia_g_view_id' ) ) {
	function topersia_g_view_id( $post_id ) {

		if ( ! function_exists( 'apply_filters' ) ) {
			return $post_id;
		}

		$type = function_exists( 'get_post_type' ) ? get_post_type( $post_id ) : '';

		if ( ! $type ) {
			return $post_id;
		}

		$view = apply_filters( 'wpml_object_id', $post_id, $type, true );

		return $view ? absint( $view ) : absint( $post_id );
	}
}

/** زبان خودِ پست در WPML — یا رشتۀ خالی (بی WPML، یا پستِ بی‌زبان). */
if ( ! function_exists( 'topersia_g_post_lang' ) ) {
	function topersia_g_post_lang( $post_id ) {

		$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';

		if ( '' === $type || ! function_exists( 'apply_filters' ) ) {
			return '';
		}

		$code = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => absint( $post_id ), 'element_type' => $type ) );

		return is_string( $code ) ? strtolower( substr( $code, 0, 2 ) ) : '';
	}
}

/**
 * نام یک موجودیت در یک زبان — `official_name_{زبان}`، سپس `name_{زبان}`.  ۴٫۴٫۲
 *
 * اگر خودِ پست آن فیلد را ندارد و با WPML نسخه‌ای در آن زبان هست، نامِ همان
 * نسخه. عنوانِ پست فقط وقتی جانشین است که زبانِ خواسته زبانِ خودِ پست است:
 * عنوانِ انگلیسی هرگز «نام فارسی» نمی‌شود، و `official_name_fa` که WPML روی
 * ترجمه رونوشت کرده هرگز نام انگلیسی — تا ۴٫۴٫۱ کاشیِ مسیرِ صفحۀ انگلیسی
 * «جادۀ ابریشم در ایران» بود.
 *
 * @return string نام، یا رشتۀ خالی.
 */
if ( ! function_exists( 'topersia_g_name_in' ) ) {
	function topersia_g_name_in( $post_id, $lang ) {

		foreach ( array( 'official_name_' . $lang, 'name_' . $lang ) as $key ) {

			$value = topersia_g_text( $post_id, $key );

			if ( '' !== $value ) {
				return $value;
			}
		}

		$own = topersia_g_post_lang( $post_id );

		/* بی WPML، پست به زبان پیش‌فرض (فارسی) است */
		if ( $own === $lang || ( '' === $own && 'fa' === $lang ) ) {
			return function_exists( 'get_the_title' ) ? (string) get_the_title( $post_id ) : '';
		}

		$type  = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';
		$other = ( '' !== $type && function_exists( 'apply_filters' ) )
			? absint( apply_filters( 'wpml_object_id', $post_id, $type, false, $lang ) )
			: 0;

		return ( $other && $other !== absint( $post_id ) ) ? topersia_g_name_in( $other, $lang ) : '';
	}
}

/** نام نمایشی یک موجودیت — به زبان صفحه.  ۴٫۴٫۲ */
if ( ! function_exists( 'topersia_g_name' ) ) {
	function topersia_g_name( $post_id ) {

		$name = topersia_g_name_in( $post_id, topersia_g_page_lang() );

		if ( '' !== $name ) {
			return $name;
		}

		return function_exists( 'get_the_title' ) ? (string) get_the_title( $post_id ) : '';
	}
}

/**
 * برچسب مرجعِ هر نوع — همان برچسب مفرد JetEngine (export ۱۴۰۵/۰۶/۱۸).  ۴٫۴٫۲
 *
 * فقط کلیدِ ترجمه است برای زبان‌های دیگر؛ صفحۀ فارسی همان برچسبِ زندۀ
 * JetEngine را می‌گیرد. «ۀ» یک‌نویسه، مثل هر رشتۀ کد (P5-D248).
 *
 * @return array نوع پست ← برچسب
 */
if ( ! function_exists( 'topersia_g_kind_labels' ) ) {
	function topersia_g_kind_labels() {

		return array(
			'tp_country'          => 'کشور',
			'tp_region'           => 'منطقه',
			'tp_province'         => 'استان',
			'tp_county'           => 'شهرستان',
			'tp_city'             => 'شهر',
			'tp_district'         => 'محله',
			'tp_village'          => 'روستا',
			'tp_island'           => 'جزیره',
			'tp_mountain'         => 'کوه',
			'tp_water_body'       => 'پهنه آبی',
			'tp_desert'           => 'کویر',
			'tp_forest_plain'     => 'جنگل/دشت',
			'tp_border_crossing'  => 'گذرگاه مرزی',
			'tp_monument'         => 'بنای تاریخی',
			'tp_religious_site'   => 'مکان مذهبی',
			'tp_ancient_site'     => 'محوطه باستانی',
			'tp_natural_feature'  => 'عارضه طبیعی',
			'tp_museum'           => 'موزه',
			'tp_protected_area'   => 'پارک ملی',
			'tp_landscape'        => 'منظر فرهنگی',
			'tp_handicraft'       => 'صنایع دستی',
			'tp_local_food'       => 'غذای محلی',
			'tp_costume'          => 'لباس سنتی',
			'tp_music'            => 'موسیقی محلی',
			'tp_dance'            => 'رقص محلی',
			'tp_language'         => 'زبان / گویش',
			'tp_ethnic_group'     => 'قوم / فرهنگ',
			'tp_ritual'           => 'آیین / مراسم سنتی',
			'tp_figure'           => 'شخصیت تاریخی',
			'tp_historical_era'   => 'دوره / سلسلۀ تاریخی',
			'tp_health_safety'    => 'سلامت و ایمنی',
			'tp_travel_etiquette' => 'فرهنگ سفر و آداب',
			'tp_designation'      => 'تعیین رسمی',
			'tp_route'            => 'مسیر گردشگری',
			'tp_visa'             => 'ویزا و شرایط ورود',
			'tp_currency'         => 'ارز و روش‌های پرداخت',
			'tp_calendar'         => 'تقویم و مناسبت‌های رسمی',
			'tp_transport'        => 'حمل‌ونقل عمومی',
			'tp_festival'         => 'جشنواره و رویداد',
			'tp_sports_event'     => 'رویداد ورزشی',
			'tp_religious_event'  => 'مراسم مذهبی عمومی',
			'tp_seasonal_event'   => 'رویداد طبیعی و فصلی',
		);
	}
}

/**
 * برچسب نوع موجودیت — «کوه»، «تالاب» — به زبان صفحه.  P5-D218 · ۴٫۴٫۲
 *
 * فارسی: برچسب مفرد نوع پست، همان JetEngine. زبان دیگر: ترجمۀ کاتالوگ از
 * برچسب مرجعِ همان نوع (`topersia_g_kind_labels()`). P5-D218 فرض کرده بود
 * WPML خودِ برچسب نوع را ترجمه می‌کند؛ نکرد و «استان» روی صفحۀ انگلیسی و
 * عربی ماند. کلید نوع پست است، نه متن برچسب، تا ویرایش برچسب در JetEngine
 * ترجمه را نشکند.
 */
if ( ! function_exists( 'topersia_g_kind_label' ) ) {
	function topersia_g_kind_label( $post_id ) {

		$type = function_exists( 'get_post_type' ) ? get_post_type( $post_id ) : '';

		if ( ! $type || ! function_exists( 'get_post_type_object' ) ) {
			return '';
		}

		$object = get_post_type_object( $type );

		if ( ! $object || empty( $object->labels->singular_name ) ) {
			return '';
		}

		$label = (string) $object->labels->singular_name;

		if ( 'fa' !== topersia_g_page_lang() ) {
			$ref   = topersia_g_kind_labels();
			$label = topersia_g_t( isset( $ref[ $type ] ) ? $ref[ $type ] : $label );
		}

		return function_exists( 'apply_filters' ) ? (string) apply_filters( 'topersia_g_kind_label', $label, $type ) : $label;
	}
}

/**
 * نام کامل موجودیت — پیشوند نوع + نام، همان تیترِ سربرگ.
 *
 * یک تابع، دو مصرف: `h1` سربرگ و سرفصلِ «دربارۀ …».  P5-D224
 * اگر نامِ ذخیره‌شده خودش با همان پیشوند شروع شده باشد، دوباره نمی‌آید —
 * «استان استان اصفهان» نه.
 *
 * پیشوند قاعدۀ نام‌گذاری فارسی است. در زبان دیگر تیتر همان نام رسمی است
 * («Isfahan Province» · «محافظة أصفهان») که نوع را هر جا طبیعی است در خود
 * دارد؛ نوع در eyebrow بالای تیتر می‌آید.  ۴٫۴٫۲ — تا ۴٫۴٫۱ «استان Isfahan Province»
 */
if ( ! function_exists( 'topersia_g_title' ) ) {
	function topersia_g_title( $post_id ) {

		/*
		 * تیترِ «فقط نام» — سطر نوع در ماتریس (`title` = name).  P5-D281
		 * نام کوتاهِ زبان صفحه (`name_{زبان}`: «ایران»)، نه نام رسمی و نه
		 * پیشوند نوع؛ نام رسمی در ردیف نام‌های زیر تیتر می‌ماند.
		 */
		if ( 'name' === topersia_g_type_row( $post_id )['title'] ) {
			$short = topersia_g_text( $post_id, 'name_' . topersia_g_page_lang() );
			return '' !== $short ? $short : topersia_g_name( $post_id );
		}

		$name = topersia_g_name( $post_id );

		if ( 'fa' !== topersia_g_page_lang() ) {
			return $name;
		}

		$kind = topersia_g_kind_label( $post_id );

		if ( '' !== $kind && '' !== $name && 0 !== strpos( $name, $kind ) ) {
			return $kind . ' ' . $name;
		}

		return $name;
	}
}

/**
 * تیتر سربرگ (H1) — فقط نام، هر نوع و هر زبان.  ۲ه٫۶ · P5-D315
 *
 * قاعدۀ کشور (P5-D281) برای همه: نام کوتاه زبان صفحه (`name_{زبان}`)، وگرنه نام
 * نمایشی. نام نوع فقط در eyebrow بالای تیتر است. نام نوعی که جزو خود داده است
 * (مثلاً `name_fa` = «استان …») بریده نمی‌شود — فهرست و تصمیم با کاربر.
 * فقط H1: `topersia_g_title()` (سرفصل‌ها، خبر، breadcrumb فهرست خبر، alt) دست نمی‌خورد.
 */
if ( ! function_exists( 'topersia_g_heading' ) ) {
	function topersia_g_heading( $post_id ) {

		$short = topersia_g_text( $post_id, 'name_' . topersia_g_page_lang() );

		return '' !== $short ? $short : topersia_g_name( $post_id );
	}
}

/* =========================================================================
   ۳ — تاریخ شمسی
   ========================================================================= */

/**
 * میلادی به شمسی.
 *
 * چرا الگوریتم و نه intl: افزونۀ intl روی این میزبان تضمین‌شده نیست و
 * وابستگی‌ای که ممکن است نباشد، یعنی تاریخ ناپدید یا خطای مرگبار.
 */
if ( ! function_exists( 'topersia_g_jalali' ) ) {
	function topersia_g_jalali( $gy, $gm, $gd ) {

		$g_days = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );

		$gy2 = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;

		$days = 355666 + ( 365 * $gy ) + ( (int) ( ( $gy2 + 3 ) / 4 ) )
			- ( (int) ( ( $gy2 + 99 ) / 100 ) ) + ( (int) ( ( $gy2 + 399 ) / 400 ) )
			+ $gd + $g_days[ $gm - 1 ];

		$jy   = -1595 + ( 33 * ( (int) ( $days / 12053 ) ) );
		$days %= 12053;

		$jy   += 4 * ( (int) ( $days / 1461 ) );
		$days %= 1461;

		if ( $days > 365 ) {
			$jy   += (int) ( ( $days - 1 ) / 365 );
			$days  = ( $days - 1 ) % 365;
		}

		if ( $days < 186 ) {
			$jm = 1 + (int) ( $days / 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + (int) ( ( $days - 186 ) / 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}

		return array( $jy, $jm, $jd );
	}
}

/**
 * نام ماه‌های میلادی — منبع فارسی، ترجمه از کاتالوگ.  ۴٫۴٫۲
 *
 * با بافتِ «ماه میلادی»: «مه» هم ماه است هم وضعیت هوا.
 *
 * @return array دوازده نام، از ژانویه.
 */
if ( ! function_exists( 'topersia_g_greg_months' ) ) {
	function topersia_g_greg_months() {

		return array(
			_x( 'ژانویه', 'ماه میلادی', 'topersia' ), _x( 'فوریه', 'ماه میلادی', 'topersia' ),
			_x( 'مارس', 'ماه میلادی', 'topersia' ), _x( 'آوریل', 'ماه میلادی', 'topersia' ),
			_x( 'مه', 'ماه میلادی', 'topersia' ), _x( 'ژوئن', 'ماه میلادی', 'topersia' ),
			_x( 'ژوئیه', 'ماه میلادی', 'topersia' ), _x( 'اوت', 'ماه میلادی', 'topersia' ),
			_x( 'سپتامبر', 'ماه میلادی', 'topersia' ), _x( 'اکتبر', 'ماه میلادی', 'topersia' ),
			_x( 'نوامبر', 'ماه میلادی', 'topersia' ), _x( 'دسامبر', 'ماه میلادی', 'topersia' ),
		);
	}
}

/**
 * تاریخ فیلد date جت‌انجین — چه timestamp باشد چه Y-m-d — به متن زبان صفحه.
 *
 * فارسی شمسی (همان پیشین). انگلیسی و عربی میلادی، با نام ماهِ همان زبان و
 * رقمِ همان زبان: «September 21, 2026» · «٢١ سبتمبر ٢٠٢٦».  ۴٫۴٫۲
 * نام تابع تاریخی مانده.
 */
if ( ! function_exists( 'topersia_g_date_fa' ) ) {
	function topersia_g_date_fa( $value ) {

		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}/', $value ) ) {
			$gy = (int) substr( $value, 0, 4 );
			$gm = (int) substr( $value, 5, 2 );
			$gd = (int) substr( $value, 8, 2 );
		} elseif ( preg_match( '/^\d{9,11}$/', $value ) ) {
			$gy = (int) gmdate( 'Y', (int) $value );
			$gm = (int) gmdate( 'n', (int) $value );
			$gd = (int) gmdate( 'j', (int) $value );
		} else {
			return '';
		}

		if ( $gm < 1 || $gm > 12 || $gd < 1 || $gd > 31 ) {
			return '';
		}

		$lang = topersia_g_page_lang();

		if ( 'fa' !== $lang ) {

			$month = topersia_g_greg_months()[ $gm - 1 ];

			return 'en' === $lang
				? $month . ' ' . topersia_g_fa( $gd ) . ', ' . topersia_g_fa( $gy )
				: topersia_g_fa( $gd ) . ' ' . $month . ' ' . topersia_g_fa( $gy );
		}

		$months = array(
			'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
			'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
		);

		list( $jy, $jm, $jd ) = topersia_g_jalali( $gy, $gm, $gd );

		return topersia_g_fa( $jd ) . ' ' . $months[ $jm - 1 ] . ' ' . topersia_g_fa( $jy );
	}
}

/**
 * تاریخ Y-m-d یک فیلد date (رشتۀ Y-m-d یا زمان یونیکس) ← «2027-09-01»، یا رشتۀ خالی.  P5-D357
 */
if ( ! function_exists( 'topersia_g_date_iso' ) ) {
	function topersia_g_date_iso( $value ) {

		$value = trim( (string) $value );

		if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})/', $value, $m ) ) {
			return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $m[1] . '-' . $m[2] . '-' . $m[3] : '';
		}

		return preg_match( '/^\d{9,11}$/', $value ) ? gmdate( 'Y-m-d', (int) $value ) : '';
	}
}

/** تاریخ به زبان صفحه داخل <time datetime> — نامعتبر ← ''.  P5-D357 */
if ( ! function_exists( 'topersia_g_date_html' ) ) {
	function topersia_g_date_html( $value ) {

		$iso  = topersia_g_date_iso( $value );
		$text = '' !== $iso ? topersia_g_date_fa( $iso ) : '';

		return '' === $text ? '' : '<time datetime="' . esc_attr( $iso ) . '">' . esc_html( $text ) . '</time>';
	}
}

/**
 * سطر «بازه برگزاری» سربرگ رویدادها (E1 · E3 · E5) — جایگاه از CA-E: سربرگ و بلوک هویت.  P5-D357
 *
 * هر دو تاریخ ← «از … تا …»؛ فقط آغاز ← همان تاریخ؛ فقط پایان ← «تا …»؛ هیچ‌کدام ← ''، و چیزی نمی‌آید.
 * قالب تاریخ از زبان صفحه است (فارسی شمسی؛ انگلیسی و عربی میلادی) — topersia_g_date_fa.
 *
 * @return string HTML، یا ''.
 */
if ( ! function_exists( 'topersia_g_event_when' ) ) {
	function topersia_g_event_when( $pid ) {

		if ( ! in_array( function_exists( 'get_post_type' ) ? (string) get_post_type( $pid ) : '', array( 'tp_festival', 'tp_sports_event', 'tp_religious_event' ), true ) ) {
			return '';
		}

		$from = topersia_g_date_html( topersia_g_raw( $pid, 'event_start_date' ) );
		$to   = topersia_g_date_html( topersia_g_raw( $pid, 'event_end_date' ) );

		if ( '' !== $from && '' !== $to && $from === $to ) {
			$to = '';
		}

		if ( '' === $from && '' === $to ) {
			return '';
		}

		if ( '' !== $from && '' !== $to ) {
			$text = sprintf( esc_html__( 'از %1$s تا %2$s', 'topersia' ), $from, $to );
		} elseif ( '' !== $to ) {
			$text = sprintf( esc_html__( 'تا %s', 'topersia' ), $to );
		} else {
			$text = $from;
		}

		return '<p class="tp-hero__alt tp-hero__when"><span class="tp-hero__when-k">' . esc_html__( 'بازه برگزاری', 'topersia' ) . ':</span> ' . $text . '</p>';
	}
}

/**
 * کاشی‌های «برگزارکننده» و «وب‌سایت رسمی» رویداد — کارت حقایق، کنار کاشی‌های تاریخ.  P5-D361
 *
 * شرط نمایش دقیقاً شرط اسکیما (topersia-schema 1.3.1) است تا دو قاعده از هم جدا نشوند:
 *   برگزارکننده  `topersia_schema_event_organizer()`: متای organizer_name همان پست؛ ترجمۀ بی مقدار ← هیچ
 *   وب‌سایت رسمی `topersia_schema_event_official_url()`: ترجمۀ بی‌مقدار از پست اصلی؛ فقط https معتبر، بی نام‌کاربری، دامنه‌ای جز topersia.com
 * اسکیما نبود ← همان قاعده با کدِ کوتاه این‌جا. هر کاشی فقط با مقدار می‌آید، چه گره Event باشد چه Thing؛ پیوند بیرونی rel="noopener" و متن پیوند نام دامنه.
 *
 * @return array[] کاشی‌ها برای topersia_g_tiles
 */
if ( ! function_exists( 'topersia_g_event_extra_tiles' ) ) {
	function topersia_g_event_extra_tiles( $pid ) {

		$tiles = array();

		if ( function_exists( 'topersia_schema_event_organizer' ) ) {
			$org  = topersia_schema_event_organizer( $pid );
			$name = isset( $org['name'] ) ? (string) $org['name'] : '';
		} else {
			$raw  = get_post_meta( $pid, 'organizer_name', true );
			$name = is_scalar( $raw ) ? trim( wp_strip_all_tags( (string) $raw ) ) : '';
		}

		if ( '' !== $name ) {
			$tiles[] = array( 'label' => __( 'برگزارکننده', 'topersia' ), 'name' => topersia_g_lang_runs( $name ), 'raw' => true );
		}

		/* محل برگزاری — venue_name همان پست (همان قاعدۀ اسکیما در topersia_schema_event_location)؛ ترجمۀ بی‌مقدار ← هیچ؛ S3 */
		$venue = get_post_meta( $pid, 'venue_name', true );
		$venue = is_scalar( $venue ) ? trim( wp_strip_all_tags( (string) $venue ) ) : '';

		if ( '' !== $venue ) {
			$tiles[] = array( 'label' => __( 'محل برگزاری', 'topersia' ), 'name' => topersia_g_lang_runs( $venue ), 'raw' => true );
		}

		if ( function_exists( 'topersia_schema_event_official_url' ) ) {
			$url = (string) topersia_schema_event_official_url( $pid );
		} else {
			$url = trim( (string) get_post_meta( $pid, 'official_event_url', true ) );
			$p   = parse_url( $url );
			$ok  = filter_var( $url, FILTER_VALIDATE_URL ) && is_array( $p ) && 'https' === strtolower( (string) ( isset( $p['scheme'] ) ? $p['scheme'] : '' ) ) && ! isset( $p['user'] ) && ! isset( $p['pass'] ) && false !== strpos( (string) ( isset( $p['host'] ) ? $p['host'] : '' ), '.' ) && 'topersia.com' !== strtolower( (string) $p['host'] );
			$url = $ok ? $url : '';
		}

		if ( '' !== $url ) {
			$host    = (string) preg_replace( '/^www\./i', '', strtolower( (string) parse_url( $url, PHP_URL_HOST ) ) );
			/* خودِ کاشی پیوند است (<a> تودرتو نامعتبر است و مرورگر می‌شکندش)؛ متن پیوند نام دامنه است */
			$tiles[] = array( 'label' => __( 'وب‌سایت رسمی', 'topersia' ), 'name' => '<span class="tp-lat" dir="ltr">' . esc_html( $host ) . '</span>', 'raw' => true, 'url' => $url, 'attrs' => ' rel="noopener"' );
		}

		return $tiles;
	}
}

/**
 * نام رسمی موجودیت در سه زبان — official_name_* و در نبودش name_*، و اگر
 * خودِ پست ندارد، از نسخۀ WPML همان زبان.  ۴٫۴٫۲ — تا ۴٫۴٫۱ روی صفحۀ
 * انگلیسی عنوانِ انگلیسی جای «نام فارسی» می‌نشست.
 *
 * @return array fa · en · ar ← نام، یا رشتۀ خالی
 */
if ( ! function_exists( 'topersia_g_official_names' ) ) {
	function topersia_g_official_names( $pid ) {

		$out = array();

		foreach ( array( 'fa', 'en', 'ar' ) as $code ) {
			$out[ $code ] = topersia_g_name_in( $pid, $code );
		}

		return $out;
	}
}

/* =========================================================================
   ۴ — شورت‌کدهای ناحیه
   ========================================================================= */

/**
 * `sizes` تصویر سربرگ — تمام‌عرض (A3 · P5-D366). پیش‌فرض وردپرس برای اندازۀ full «(max-width: 2560px) 100vw, 2560px» است
 * و مرورگر را روی پهنای نمایشگر اطمینان نمی‌دهد؛ سربرگ همیشه عرض کامل صفحه است. preload همین مقدار را می‌گیرد.
 */
if ( ! function_exists( 'topersia_g_hero_sizes' ) ) {
	function topersia_g_hero_sizes() {
		return (string) apply_filters( 'topersia_hero_sizes', '100vw' );
	}
}

/** [tp_hero] — تصویر، نام سه‌زبانه، نام محلی، خلاصه. */
if ( ! function_exists( 'topersia_g_sc_hero' ) ) {
	function topersia_g_sc_hero( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$official = topersia_g_official_names( $pid );

		$local = topersia_g_text( $pid, 'name_local' );
		$alt   = topersia_g_text( $pid, 'alternative_names' );
		$lead  = topersia_g_text( $pid, 'short_summary' );
		$kind  = topersia_g_kind_label( $pid );

		$image = '';
		$hero  = topersia_g_raw( $pid, 'hero_image' );

		if ( topersia_g_filled( $hero ) && function_exists( 'wp_get_attachment_image' ) ) {

			$hero_id = is_array( $hero ) ? absint( reset( $hero ) ) : absint( $hero );
			$attr    = array(
				'class'         => 'tp-hero__img',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'sizes'         => topersia_g_hero_sizes(),
			);

			/* alt پیوست به زبان دیگر ← نام موجودیت به زبان صفحه  P5-D278 */
			if ( topersia_g_foreign_text( get_post_meta( $hero_id, '_wp_attachment_image_alt', true ) ) ) {
				$attr['alt'] = topersia_g_title( $pid );
			}

			$image = wp_get_attachment_image( $hero_id, 'full', false, $attr );
		}

		/*
		 * نام‌های رسمیِ دو زبانِ دیگر — نه زبان صفحه.  ۴٫۳٫۰ بند ۹ · P5-D240
		 *
		 * تا ۴٫۲٫۳ همیشه انگلیسی و عربی می‌آمد؛ روی صفحۀ انگلیسی همان
		 * تیتر را دوباره می‌گفت و فارسی جا می‌ماند. ترتیب ثابت است: فارسی،
		 * انگلیسی، عربی، منهای زبان جاری.
		 */
		$names = array();
		$here  = topersia_g_page_lang();
		$title = topersia_g_heading( $pid );

		/*
		 * تیترِ «فقط نام» (P5-D281، از ۲ه٫۶ همۀ نوع‌ها · P5-D315) نام رسمیِ زبان صفحه
		 * را در خود ندارد — «ایران» نه «جمهوری اسلامی ایران» — پس آن هم در همین ردیف
		 * می‌آید، مگر همان تیتر باشد.
		 */
		foreach ( array( 'fa', 'en', 'ar' ) as $code ) {

			if ( '' === $official[ $code ] || ( $code === $here && $official[ $code ] === $title ) ) {
				continue;
			}

			if ( 'en' === $code ) {
				$names[] = '<span lang="en" dir="ltr">' . esc_html( $official['en'] ) . '</span>';
			} elseif ( 'ar' === $code ) {
				$names[] = '<em lang="ar">' . esc_html( $official['ar'] ) . '</em>';
			} else {
				$names[] = '<span lang="fa">' . esc_html( $official['fa'] ) . '</span>';
			}
		}

		/*
		 * جداکنندۀ نام‌ها — ویرگول، نه «·».  SPRINT-4.4 ۱٫۶
		 * صفحۀ راست‌به‌چپ (فارسی، عربی) «، »، چپ‌به‌راست «, »؛ از جهت صفحه،
		 * نه زبان ثابت. `alternative_names` هر نام را در یک سطر نگه می‌دارد
		 * («اِصفَهان\nاِسپَهان»)؛ هر سطر یک نام است: «اِصفَهان، اِسپَهان».
		 */
		$sep    = topersia_g_is_rtl() ? '، ' : ', ';
		$others = array_filter( array_map( 'trim', array_merge(
			array( $local ),
			(array) preg_split( '/\R|[،,·]+/u', (string) $alt )
		) ), 'strlen' );

		$out = '<header class="tp-hero">'
			. '<div class="tp-hero__fallback" aria-hidden="true"></div>'
			. $image
			. '<div class="tp-hero__overlay" aria-hidden="true"></div>'
			. '<div class="tp-wrap tp-hero__in"><div class="tp-hero__copy">';

		/*
		 * eyebrow به زبان صفحه، نه لاتین.  P5-D218
		 *
		 * «PROVINCE» لغو شد: برچسب نوع — فارسی «استان»، انگلیسی «Province»،
		 * عربی «محافظة». از ۴٫۴٫۲ ترجمه از کاتالوگ افزونه به کلید نوع پست
		 * (`topersia_g_kind_label()`)؛ WPML برچسب نوع را ترجمه نکرده بود.
		 */
		if ( '' !== $kind ) {
			$out .= '<p class="tp-eyebrow">' . esc_html( $kind ) . '</p>';
		}

		/* تیتر: پیشوند نوع + نام — همان تابعی که «دربارۀ …» می‌خواند.  P5-D224 */
		$out .= '<h1>' . esc_html( $title ) . '</h1>';

		if ( $names ) {
			$out .= '<p class="tp-hero__alt">' . implode( $sep, $names ) . '</p>';
		}

		if ( $others ) {
			/* نام محلی و نام‌های دیگرِ فارسی در صفحۀ غیرفارسی — با زبان خودشان  P5-D278 */
			$fa_names = topersia_g_foreign_text( implode( ' ', $others ) ) ? ' lang="fa" dir="rtl"' : '';
			$out     .= '<p class="tp-altnames"' . $fa_names . '>' . esc_html( $fa_names ? topersia_g_fa( implode( '، ', $others ) ) : implode( $sep, $others ) ) . '</p>';
		}

		/* بازه برگزاری رویداد — بی تاریخ چیزی نمی‌آید.  P5-D357 */
		$out .= topersia_g_event_when( $pid );

		if ( '' !== $lead ) {
			$out .= '<p class="tp-hero__lead">' . esc_html( $lead ) . '</p>';
		}

		return $out . '</div></div></header>';
	}
}

/**
 * [tp_actionbar] — اشتراک · ذخیره · دنبال‌کردن · منبع ترجیحی گوگل.
 *
 * یک عنصر است، نه دو نسخه. جابه‌جایی میان ستون کناری و بالای مسیر راهنما
 * را matchMedia انجام می‌دهد. وضعیت «ذخیره‌شده» و «دنبال‌شده» عمداً در
 * HTML نمی‌آید چون صفحه کش تمام‌صفحه می‌شود؛ برای واردشده پس از بارگذاری
 * از user-core پرسیده می‌شود.
 *
 * ۴٫۳٫۰ بند ۵:
 *   اشتراک   P5-D239 — پنل آیکون شبکه‌ها با hover یا focus، در لمس با
 *            ضربه. آیکون‌ها همان مجموعۀ شبکه‌های اجتماعی وودمارت‌اند
 *            (`.wd-social-icons` · `woodmart-font`، که هدر بار کرده). بی
 *            اسکریپت شخص ثالث: نشانی‌های استاندارد اشتراک با noopener.
 *   ذخیره    P5-D237 — API ذخیرۀ user-core؛ مهمان ← پنجرۀ ورود/ثبت‌نام.
 *   دنبال    P5-D238 — API دنبال‌کردن user-core؛ پیوند RSS از دکمه رفت.
 *
 * «هستۀ نمایش» فقط دکمه می‌سازد: بی user-core، ذخیره و دنبال‌کردن اصلاً
 * نمی‌آیند — هیچ سامانۀ موازی‌ای این‌جا نیست.
 */
/**
 * آیا user-core (ذخیره، دنبال‌کردن، پنجرۀ ورود) بار شده است.  ۴٫۳٫۰ بند ۰
 *
 * فیلتر `topersia_g_user_core` برای آزمون است.
 */
if ( ! function_exists( 'topersia_g_user_core' ) ) {
	function topersia_g_user_core() {

		$on = class_exists( 'ToPersia_JetEngine' ) && class_exists( 'ToPersia_Follow' );

		return function_exists( 'apply_filters' ) ? (bool) apply_filters( 'topersia_g_user_core', $on ) : $on;
	}
}

if ( ! function_exists( 'topersia_g_share_links' ) ) {
	/**
	 * نشانی‌های استاندارد اشتراک — به ترتیب مشخصات.
	 *
	 * @return array کلید ← [برچسب، کلاس آیکون وودمارت، نشانی]
	 */
	function topersia_g_share_links( $url, $title ) {

		$u = rawurlencode( (string) $url );
		$t = rawurlencode( (string) $title );

		return array(
			'telegram' => array( __( 'تلگرام', 'topersia' ), 'social-tg', 'https://t.me/share/url?url=' . $u . '&text=' . $t ),
			'whatsapp' => array( __( 'واتس‌اپ', 'topersia' ), 'social-whatsapp', 'https://wa.me/?text=' . $t . '%20' . $u ),
			'x'        => array( 'X', 'social-twitter', 'https://x.com/intent/tweet?url=' . $u . '&text=' . $t ),
			'facebook' => array( __( 'فیسبوک', 'topersia' ), 'social-facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $u ),
			'linkedin' => array( __( 'لینکدین', 'topersia' ), 'social-linkedin', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u ),
			'email'    => array( __( 'ایمیل', 'topersia' ), 'social-email', 'mailto:?subject=' . $t . '&body=' . $u ),
		);
	}
}

if ( ! function_exists( 'topersia_g_sc_actionbar' ) ) {
	function topersia_g_sc_actionbar( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$uid   = topersia_g_text( $pid, 'entity_uid' );
		$type  = function_exists( 'get_post_type' ) ? (string) get_post_type( $pid ) : '';
		$url   = (string) get_permalink( $pid );
		$title = topersia_g_title( $pid );

		/* ذخیره و دنبال‌کردن مال user-core است؛ بی آن، دکمه‌ای هم نیست. */
		$user_core = topersia_g_user_core();

		$svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">';

		$links = '';

		foreach ( topersia_g_share_links( $url, $title ) as $key => $one ) {
			/* آیکون گردِ وودمارت بی‌متن است؛ نام شبکه برای صفحه‌خوان و راهنمای ماوس. */
			$links .= '<a class="' . esc_attr( $one[1] ) . '" href="' . esc_url( $one[2], array( 'https', 'mailto' ) ) . '"'
				. ( 'email' === $key ? '' : ' target="_blank"' ) . ' rel="noopener nofollow" title="' . esc_attr( $one[0] ) . '"'
				. ' data-tp-share-to="' . esc_attr( $key ) . '">'
				. '<span class="wd-icon" aria-hidden="true"></span><span class="tp-vh">' . esc_html( $one[0] ) . '</span></a>';
		}

		$panel = '<div class="tp-share__panel" id="tp-share-panel" data-tp-share-panel>'
			. '<div class="wd-social-icons tp-share__icons">' . $links . '</div>'
			. '<div class="tp-share__row">'
			. '<button type="button" class="tp-share__btn" data-tp-share-copy data-tp-url="' . esc_attr( $url ) . '">'
			. $svg . '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>'
			. '<span>' . esc_html__( 'کپی نشانی', 'topersia' ) . '</span></button>'
			/* «بیشتر» فقط در موبایل و فقط اگر navigator.share هست — اسکریپت آشکارش می‌کند. */
			. '<button type="button" class="tp-share__btn" data-tp-share-more hidden>'
			. $svg . '<circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg>'
			. '<span>' . esc_html__( 'بیشتر', 'topersia' ) . '</span></button>'
			. '</div></div>';

		$share = '<div class="tp-share" data-tp-share-wrap>'
			. '<button type="button" class="tp-act" data-tp-share aria-expanded="false" aria-controls="tp-share-panel">' . $svg
			. '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>'
			. '<path d="M8.6 13.5l6.8 4M15.4 6.5l-6.8 4"/></svg>'
			. '<span>' . esc_html__( 'اشتراک', 'topersia' ) . '</span></button>'
			. $panel
			. '</div>';

		$save = $user_core
			? '<button type="button" class="tp-act" data-tp-save aria-pressed="false">' . $svg
				. '<path d="M6 3h12v18l-6-4.5L6 21z"/></svg>'
				. '<span>' . esc_html__( 'ذخیره', 'topersia' ) . '</span></button>'
			: '';

		$follow = $user_core
			? '<button type="button" class="tp-act" data-tp-follow aria-pressed="false">' . $svg
				. '<path d="M6 10a6 6 0 0 1 12 0v4.5l1.6 2.5H4.4L6 14.5Z"/><path d="M10 19.5a2 2 0 0 0 4 0"/></svg>'
				. '<span data-tp-follow-label>' . esc_html__( 'دنبال‌کردن', 'topersia' ) . '</span></button>'
			: '';

		/*
		 * عیناً مرجع: `section#actions` با کلاس حالت کارتی (دسکتاپ)؛ روی
		 * موبایل جاوااسکریپت آن را بالای مسیر راهنما می‌برد و کلاسش را
		 * `tp-actionbar` می‌کند. بی‌جاوااسکریپت هم درست می‌نشیند.  P5-D219
		 */
		return '<section id="tp-actions" class="tp-rail-card tp-actionbar tp-actionbar--rail" data-tp-actionbar'
			. ' data-tp-entity="' . esc_attr( $uid ) . '"'
			. ' data-tp-post="' . esc_attr( $pid ) . '" data-tp-type="' . esc_attr( $type ) . '">'
			. '<div class="tp-actions">' . $share . $save . $follow . '</div>'
			. topersia_g_gps_link()
			. '<p class="tp-act__note" role="status" aria-live="polite"></p>'
			. '</section>';
	}
}

/**
 * منبع ترجیحی گوگل — پیوند، نه دکمه.  P5-D207
 *
 * مرجع بصری `<button>` داشت، ولی این کار یک **ناوبری** است نه کنشِ
 * درون‌صفحه‌ای: کاربر به تنظیمات گوگل می‌رود. با `<a>` کلیک میانی و
 * «باز کردن در برگۀ تازه» و صفحه‌کلید همه خودبه‌خود کار می‌کنند و
 * هیچ جاوااسکریپتی لازم نیست.
 *
 * اسکریپت رسمی گوگل عمداً نیامده: یک وابستگی بیرونی روی هر صفحۀ
 * جغرافیا، برای پیوندی که یک آدرس ثابت است.
 *
 * `nofollow` چون توصیۀ ما به گوگل نیست، و `noopener` چون مقصد بیرونی
 * است. آیکون همان G مرجع است و `aria-hidden` می‌ماند — معنایش در متن
 * پیوند آمده، نه در تصویر.
 */
if ( ! function_exists( 'topersia_g_gps_link' ) ) {
	function topersia_g_gps_link() {

		$icon = '<svg class="tp-g" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
			. '<path fill="#4285F4" d="M45.1 24.5c0-1.6-.1-3.2-.4-4.7H24v8.9h11.8c-.5 2.7-2 5-4.4 6.6v5.5h7.1c4.2-3.8 6.6-9.5 6.6-16.3z"/>'
			. '<path fill="#34A853" d="M24 46c5.9 0 10.9-2 14.5-5.2l-7.1-5.5c-2 1.3-4.5 2.1-7.4 2.1-5.7 0-10.6-3.9-12.3-9.1H4.4v5.7C8 41.4 15.4 46 24 46z"/>'
			. '<path fill="#FBBC05" d="M11.7 28.3c-.4-1.3-.7-2.7-.7-4.3s.3-3 .7-4.3v-5.7H4.4A22 22 0 0 0 2 24c0 3.6.9 6.9 2.4 9.9l7.3-5.6z"/>'
			. '<path fill="#EA4335" d="M24 10.6c3.2 0 6.1 1.1 8.4 3.3l6.3-6.3C34.9 4 29.9 2 24 2 15.4 2 8 6.6 4.4 13.9l7.3 5.7c1.7-5.2 6.6-9 12.3-9z"/>'
			. '</svg>';

		return '<a class="tp-gps-btn" href="https://www.google.com/preferences/source?q=topersia.com"'
			. ' rel="nofollow noopener" target="_blank">'
			. $icon
			. '<span class="tp-gps-btn__t">' . esc_html__( 'ToPersia را منبع ترجیحی گوگل کنید', 'topersia' ) . '</span>'
			. '<span class="tp-gps-btn__s">' . esc_html__( 'مطالب ما را بیشتر در نتایج گوگل و AI Mode ببینید', 'topersia' ) . '</span>'
			. '</a>';
	}
}

/**
 * پلۀ بعدیِ پیمودن رو به بالا — والدِ منتشرشده اگر هست، وگرنه نخستین.  P5-D247
 *
 * پیمودن از نیای منتشرنشده می‌گذرد (مسیر راهنما و ارث‌بری نباید با یک
 * پیش‌نویس بشکنند)، ولی وقتی کنار آن والدی منتشرشده هم هست — ترجمه یا
 * نسخۀ تازه‌ای که هنوز پیش‌نویس است — راه از منتشرشده می‌رود.
 *
 * @param array $found شناسه‌های والد، بی‌دروازه.
 * @return int
 */
if ( ! function_exists( 'topersia_g_walk_parent' ) ) {
	function topersia_g_walk_parent( $found ) {

		$found = array_values( array_map( 'absint', (array) $found ) );
		$live  = topersia_g_published( $found );

		return $live ? (int) $live[0] : ( $found ? (int) $found[0] : 0 );
	}
}

/**
 * نیاکانِ مسیر راهنمای HTML — منتشرشده، از ریشه به پایین، در زبان جاری.  P5-D247
 *
 * پیمودن از نیای منتشرنشده می‌گذرد ولی آن نیا در زنجیره نمی‌آید. (JSON-LD
 * پله‌های خودِ موتور شِما را دارد — `topersia_g_schema_graph()` — چون آن‌جا
 * شهرستانِ بی‌صفحه `skip` است و هر ListItem جز آخری نشانی می‌خواهد.)
 *
 * @return array<int,int> شناسۀ نسخۀ زبان جاری هر نیا.
 */
if ( ! function_exists( 'topersia_g_crumb_chain' ) ) {
	function topersia_g_crumb_chain( $pid ) {

		$group = topersia_g_group( $pid, 'crumbs' );
		$chain = array();
		$cur   = $pid;

		/*
		 * با ثبت‌نامۀ زندۀ رابطه (جت‌انجین): زنجیرۀ کامل والدهای مکانی R01 برای هر نوع — همان که BreadcrumbList می‌خواند.
		 * بی ثبت‌نامه (آزمون بی جت‌انجین): گروه دستی `crumbs`.  ۲و٫۲ · D338
		 */
		if ( function_exists( 'topersia_g_crumb_places' ) && topersia_g_relreg() ) {
			return topersia_g_crumb_places( $pid );
		}

		if ( ! empty( $group['rels'] ) ) {

			foreach ( $group['rels'] as $spec ) {

				/* از نیای منتشرنشده می‌گذرد، ولی نامش نمی‌آید.  P5-D247 */
				$found = topersia_g_rel_ids( $cur, array( $spec ), false );

				if ( empty( $found ) ) {
					break;
				}

				$parent = topersia_g_walk_parent( $found );

				if ( empty( $spec['skip'] ) && topersia_g_published( array( $parent ) ) ) {
					$chain[] = $parent;
				}

				$cur = $parent;
			}
		}

		/* کشور در مسیر راهنما نیست (D338)؛ فقط در containedInPlace اسکیما می‌ماند */
		return array_values( array_filter( array_map( 'topersia_g_view_id', array_reverse( $chain ) ), function ( $id ) {
			return 'tp_country' !== get_post_type( $id );
		} ) );
	}
}

/** [tp_crumbs] — مسیر راهنما از سلسله‌مراتب رابطه. */
if ( ! function_exists( 'topersia_g_sc_crumbs' ) ) {
	function topersia_g_sc_crumbs( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$items = array( '<a href="' . esc_url( home_url( '/' ) ) . '">ToPersia</a>' );

		foreach ( topersia_g_crumb_chain( $pid ) as $view ) {

			$url = topersia_g_public_url( $view );

			/* نیای بی‌صفحه (شهرستان) نام است نه پیوند؛ منتشرنشده اصلاً نمی‌آید.  ۴٫۳٫۰ بند ۰ · P5-D247 */
			$items[] = '' !== $url
				? '<a href="' . esc_url( $url ) . '">' . esc_html( topersia_g_name( $view ) ) . '</a>'
				: '<span>' . esc_html( topersia_g_name( $view ) ) . '</span>';
		}

		$items[] = '<span aria-current="page">' . esc_html( topersia_g_name( $pid ) ) . '</span>';

		return '<p class="tp-crumb" aria-label="' . esc_attr__( 'مسیر راهنما', 'topersia' ) . '">'
			. implode( '<span>›</span>', $items ) . '</p>';
	}
}

/**
 * JSON-LD افزونۀ `topersia-schema` از همان دروازه — P5-D247 · P5-D145.  ۴٫۴٫۲
 *
 * موتور شِما نیاکان را بی دروازه می‌پیماید: والد پیش‌نویس، خصوصی یا رمزدار
 * با نام و نشانی‌اش در BreadcrumbList می‌آمد و شناسه‌اش در containedInPlace،
 * و ایستگاهِ منتشرنشده یا بی‌نسخه در این زبان در itinerary مسیر. این صافی —
 * قلاب رسمی خودِ موتور — هر سه را از همان دروازه می‌گذراند و جز این هیچ
 * چیزِ گراف را عوض نمی‌کند:
 *
 *   BreadcrumbList    همان پله‌های خودِ موتور (`$config['parents']` با `skip` —
 *                     شهرستان بی‌صفحه در JSON-LD نمی‌آید)، همان نام‌گذاری
 *                     (`topersia_schema_name()`)؛ پیمودن از نیای منتشرنشده
 *                     می‌گذرد ولی آن نیا نمی‌آید (مثل مسیر راهنمای HTML)
 *   containedInPlace  نزدیک‌ترین نیای منتشرشدۀ همان پله‌ها؛ نبود ← برداشته
 *   itinerary         فقط ایستگاهی که نسخۀ منتشرشدۀ زبان جاری دارد؛ شماره از نو
 *
 * موتور شِما در این مخزن نیست؛ کدش دست نمی‌خورد.
 *
 * @param array $graph   گراف.
 * @param int   $post_id پست.
 * @param array $config  پیکربندی همان نوع در `topersia_schema_map()`.
 * @return array
 */
if ( ! function_exists( 'topersia_g_schema_graph' ) ) {
	function topersia_g_schema_graph( $graph, $post_id, $config = array() ) {

		if ( ! is_array( $graph ) || ! $post_id ) {
			return $graph;
		}

		$uid  = function ( $id ) {
			return function_exists( 'topersia_schema_uid' ) ? topersia_schema_uid( $id ) : '';
		};
		$name = function ( $id ) {
			return function_exists( 'topersia_schema_name' ) ? topersia_schema_name( $id ) : topersia_g_name( $id );
		};

		/* نیاکانِ موتور، از پایین به بالا — فقط منتشرشده، در زبان جاری */
		$ancestors = array();
		$cur       = $post_id;

		if ( function_exists( 'topersia_g_place_chain' ) && topersia_g_relreg() ) {

			/* مسیر راهنمای HTML و BreadcrumbList یک زنجیره‌اند: نزدیک‌ترین نیا اول؛ بی‌صفحه نمی‌آید  D338 */
			$ancestors = array_reverse( array_values( array_filter( topersia_g_place_chain( $post_id ), function ( $id ) {
				return '' !== topersia_g_public_url( $id );
			} ) ) );   /* نزدیک‌ترین نیا اول، با کشور */

		} elseif ( is_array( $config ) && ! empty( $config['parents'] ) && is_array( $config['parents'] ) ) {

			foreach ( $config['parents'] as $step ) {

				$rel   = isset( $step['relation'] ) ? absint( $step['relation'] ) : 0;
				$found = $rel ? topersia_g_rel_ids( $cur, array( array( 'id' => $rel, 'dir' => 'parents' ) ), false ) : array();

				if ( ! $found ) {
					break;
				}

				$parent = topersia_g_walk_parent( $found );

				if ( empty( $step['skip'] ) ) {
					$live = topersia_g_published( array( $parent ) );
					if ( $live ) {
						$ancestors[] = (int) $live[0];
					}
				}

				$cur = $parent;
			}
		}

		foreach ( $graph as $i => $node ) {

			if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
				continue;
			}

			if ( 'BreadcrumbList' === $node['@type'] && isset( $node['itemListElement'] ) && is_array( $node['itemListElement'] ) ) {

				$list  = array_values( $node['itemListElement'] );
				$items = array();

				/* ریشه (خانه) و برگ (خودِ صفحه) همانی که موتور ساخته */
				if ( count( $list ) >= 2 ) {
					$items[] = $list[0];
				}

				/* BreadcrumbList = مسیر راهنمای HTML: بی کشور (D338) */
				foreach ( array_reverse( $ancestors ) as $view ) {
					if ( 'tp_country' === get_post_type( $view ) ) {
						continue;
					}
					$items[] = array( '@type' => 'ListItem', 'name' => $name( $view ), 'item' => (string) get_permalink( $view ) );
				}

				$items[] = end( $list );

				foreach ( $items as $k => $item ) {
					$items[ $k ]['position'] = $k + 1;
				}

				$graph[ $i ]['itemListElement'] = $items;
				continue;
			}

			if ( array_key_exists( 'containedInPlace', $node ) ) {

				if ( $ancestors && '' !== $uid( $ancestors[0] ) ) {
					$graph[ $i ]['containedInPlace'] = array( '@id' => $uid( $ancestors[0] ) );
				} else {
					unset( $graph[ $i ]['containedInPlace'] );
				}
			}

			if ( isset( $node['itinerary']['itemListElement'] ) && is_array( $node['itinerary']['itemListElement'] ) ) {

				/* ایستگاه‌ها با شناسۀ خودشان — همان فهرستی که موتور از آن ساخته */
				$by_uid = array();

				if ( function_exists( 'topersia_p9_stops' ) ) {
					foreach ( (array) topersia_p9_stops( $post_id ) as $stop ) {
						if ( isset( $stop['id'] ) ) {
							$by_uid[ $uid( (int) $stop['id'] ) ] = (int) $stop['id'];
						}
					}
				}

				$kept = array();

				foreach ( $node['itinerary']['itemListElement'] as $item ) {

					$id = isset( $item['item']['@id'], $by_uid[ $item['item']['@id'] ] ) ? $by_uid[ $item['item']['@id'] ] : 0;

					if ( $id && topersia_g_published( array( $id ) ) ) {
						$item['position'] = count( $kept ) + 1;
						$kept[]           = $item;
					}
				}

				if ( $kept ) {
					$graph[ $i ]['itinerary']['itemListElement'] = $kept;
					$graph[ $i ]['itinerary']['numberOfItems']   = count( $kept );
				} else {
					unset( $graph[ $i ]['itinerary'] );
				}
			}
		}

		return $graph;
	}
}

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'topersia_schema_graph', 'topersia_g_schema_graph', 10, 3 );
}

/**
 * آیا این پست کارت «مرکز» دارد؟ یک خانه از چهار خانۀ آمار را می‌گیرد.
 */
if ( ! function_exists( 'topersia_g_capital_id' ) ) {
	function topersia_g_capital_id( $post_id ) {

		$group = topersia_g_group( $post_id, 'capital' );

		if ( empty( $group['rels'] ) ) {
			return 0;
		}

		$ids = topersia_g_rel_ids( $post_id, $group['rels'] );

		return empty( $ids ) ? 0 : absint( reset( $ids ) );
	}
}

/**
 * نام فیلدهایی که کارت آمار مصرف می‌کند.
 *
 * تولیدکنندۀ واحد: کارت آمار این را مصرف می‌کند و کارت حقایق همین را
 * کنار می‌گذارد. اگر هر کدام حساب خودش را می‌کرد، فیلدی که «مرکز» جایش
 * را تنگ کرده بود بی‌صدا از هر دو کارت می‌افتاد — همان اتفاقی که در
 * آزمون T-1 برای climate_type افتاد.
 */
if ( ! function_exists( 'topersia_g_stat_keys' ) ) {
	/**
	 * والدِ مکانیِ بی‌واسطه — همان پلۀ اولِ مسیر راهنما.
	 *
	 * از همان نقشۀ `crumbs` می‌آید که `[tp_crumbs]` استفاده می‌کند، نه از
	 * فهرست جدا. پله‌های `skip` (مثل شهرستان — `P5-D172`) رد می‌شوند و
	 * اولین والدِ دیدنی برمی‌گردد.
	 *
	 * @return string نام، یا رشتۀ خالی.
	 */
	function topersia_g_place_parent( $post_id ) {

		$group = topersia_g_group( $post_id, 'crumbs' );

		if ( empty( $group['rels'] ) ) {
			return '';
		}

		$cur = $post_id;

		foreach ( $group['rels'] as $spec ) {

			/* نخستین والدِ دیدنیِ منتشرشده؛ از منتشرنشده می‌گذرد.  P5-D247 */
			$found = topersia_g_rel_ids( $cur, array( $spec ), false );

			if ( empty( $found ) ) {
				return '';
			}

			$parent = topersia_g_walk_parent( $found );

			if ( empty( $spec['skip'] ) && topersia_g_published( array( $parent ) ) ) {
				return topersia_g_name( topersia_g_view_id( $parent ) );
			}

			$cur = $parent;
		}

		return '';
	}
}

/**
 * پاورقی کارت: والد مکانی · یک واقعیت کلیدی.
 *
 * اگر هیچ‌کدام نبود، خودِ پاورقی هم نیست — نه جعبۀ خالی، نه جداکنندۀ
 * بی‌طرف. همان قاعدۀ ناپدیدشدن که در کل قالب هست.
 */
if ( ! function_exists( 'topersia_g_card_foot' ) ) {
	function topersia_g_card_foot( $item ) {

		/*
		 * پاورقیِ `#places` مرجع: «N جاذبه» و «N رویداد».  P5-D215
		 * هر کدام صفر بود، همان `span` نمی‌آید.
		 */
		if ( isset( $item['counts'] ) ) {

			$spots  = (int) $item['counts']['spots'];
			$events = (int) $item['counts']['events'];

			if ( ! $spots && ! $events ) {
				return '';
			}

			/* شمار با شکل جمعِ هر زبان — فارسی پس از عدد مفرد است  ۴٫۴٫۲ */
			return '<div class="tp-card-slide__foot">'
				/* translators: %s: شمار جاذبه */
				. ( $spots ? '<span>' . esc_html( sprintf( _n( '%s جاذبه', '%s جاذبه', $spots, 'topersia' ), topersia_g_fa( $spots ) ) ) . '</span>' : '' )
				/* translators: %s: شمار رویداد */
				. ( $events ? '<span>' . esc_html( sprintf( _n( '%s رویداد', '%s رویداد', $events, 'topersia' ), topersia_g_fa( $events ) ) ) . '</span>' : '' )
				. '</div>';
		}

		$place = isset( $item['place'] ) ? (string) $item['place'] : '';
		$fact  = isset( $item['fact'] ) ? (string) $item['fact'] : '';

		if ( '' === $place && '' === $fact ) {
			return '';
		}

		/* پاورقی مرجع دو `span` خالیِ بی‌کلاس است. */
		return '<div class="tp-card-slide__foot">'
			. ( '' !== $place ? '<span>' . esc_html( $place ) . '</span>' : '' )
			. ( '' !== $fact ? '<span>' . esc_html( $fact ) . '</span>' : '' )
			. '</div>';
	}
}

/**
 * برچسب نوعِ اعضای یک گروه — از واژه‌نامه، نه ثابت.  P5-D210
 *
 * «شهر» در متن دکمه نباید سخت‌کد شود: همین شورت‌کد برای روستا و محله و
 * استان هم کار می‌کند. برچسب از نخستین عضو گرفته می‌شود، چون اعضای یک
 * گروه هم‌نوع‌اند.
 */
/**
 * سرفصل بخش — عیناً مرجع: `h2` + `p.lead` + `a.more`.  P5-D215
 *
 * شمارندۀ «N مورد» در مرجع نیست و برداشته شد.
 *
 * `a.more` فقط وقتی می‌آید که برگۀ هاب برای آن نوع وجود داشته باشد.
 * چون هنوز هیچ هابی تصمیم نشده، `hub` در نقشه خالی است و این پیوند
 * رندر نمی‌شود — پیوند به صفحه‌ای که نیست، ۴۰۴ است نه ناوبری.
 */
/**
 * برچسب «همه … ←» بخشی که مودالِ فهرست دارد — یا خالی.  P5-D221
 *
 * سه بخش: شهرها، جاذبه‌ها، فرهنگ. برچسب از نقشۀ رابطه (`more`) می‌آید؛
 * گروه فرهنگ در نقشۀ هیچ نوعی `more` ندارد و برچسبِ مرجع همین‌جاست.
 */
if ( ! function_exists( 'topersia_g_list_more' ) ) {
	function topersia_g_list_more( $group, $key ) {

		/*
		 * هر اسلایدر با بیش از هشت عضو پیوند فهرست می‌گیرد — الگوی «همه شهرها ←».
		 * P5-D276. برچسب گروه (`more`) اول؛ نبود، برچسب همین جدول؛ نبود، عمومی.
		 */
		$keys = array(
			'places'      => '',
			'attractions' => '',
			'provinces'   => 'همه استان‌ها ←',
			'districts'   => 'همه محله‌ها ←',
			'culture'     => 'همه فرهنگ ←',
			/* بخش‌های ستون کناری که کاشی‌هایشان بریده می‌شود — ۲ه٫۴ · P5-D313 */
			'villages'    => 'همه روستاها ←',
			'nature'      => 'همه پهنه‌های طبیعی ←',
			'ancient'     => 'همه محوطه‌های باستانی ←',
			'routes'      => 'همه مسیرها ←',
			'subranges'   => 'همه زیرشاخه‌ها ←',
			'border'      => 'همه گذرهای مرزی ←',
		);

		if ( isset( $group['more'] ) && '' !== (string) $group['more'] ) {
			return topersia_g_t( (string) $group['more'] );
		}

		return ( isset( $keys[ $key ] ) && '' !== $keys[ $key ] ) ? topersia_g_t( $keys[ $key ] ) : __( 'همه موارد ←', 'topersia' );
	}
}

/**
 * سقف کارتِ دیدنیِ هر بخش — بیش از آن «همه …» و پنجرۀ فهرست.  ۲ه٫۴ · P5-D313
 * اسلایدرها هشت (P5-D276)؛ کاشی‌های ستون کناری شش (`topersia_g_tiles`).
 */
if ( ! function_exists( 'topersia_g_list_cap' ) ) {
	function topersia_g_list_cap( $key ) {

		return in_array( $key, array( 'villages', 'nature', 'ancient', 'routes', 'subranges', 'border' ), true ) ? 6 : topersia_g_slider_max();
	}
}

/**
 * پیوند «همه … ←» کنار عنوان کارتِ ستون کناری — فقط وقتی اعضا بیش از کاشی‌های دیدنی‌اند.
 * همان `data-tp-list-open` اسلایدرها؛ جاوااسکریپت پنجره را باز می‌کند.  ۲ه٫۴ · P5-D313
 */
if ( ! function_exists( 'topersia_g_rail_more' ) ) {
	function topersia_g_rail_more( $group, $key, $total, $shown ) {

		if ( ! $shown || $total <= $shown ) {
			return '';
		}

		$list = 'tp-list-' . $key;

		return '<a class="tp-more tp-more--rail" href="#' . esc_attr( $list ) . '" role="button" aria-haspopup="dialog"'
			. ' aria-controls="' . esc_attr( $list ) . '" data-tp-list-open>' . esc_html( topersia_g_list_more( $group, $key ) ) . '</a>';
	}
}

if ( ! function_exists( 'topersia_g_section_head' ) ) {
	/**
	 * @param array  $group  گروه رابطه.
	 * @param string $entity نامی که جای `%s` متن راهنما می‌نشیند.
	 * @param string $list   شناسۀ مودالِ فهرست؛ اگر هست، `.more` آن را باز می‌کند.  P5-D221
	 */
	function topersia_g_section_head( $group, $entity = '', $list = '' ) {

		$title = isset( $group['title'] ) ? topersia_g_t( (string) $group['title'] ) : '';

		if ( '' === $title ) {
			return '';
		}

		/* متن راهنما نام موجودیت را می‌گیرد، نه رشتۀ ثابت.  P5-D215 — ترجمه پیش از جای‌گذاری  ۴٫۴٫۲ */
		$lead = isset( $group['lead'] ) ? topersia_g_t( (string) $group['lead'] ) : '';

		if ( '' !== $lead && false !== strpos( $lead, '%s' ) ) {
			$lead = sprintf( $lead, $entity );
		}
		$hub  = isset( $group['hub'] ) ? (string) $group['hub'] : '';
		$more = isset( $group['more'] ) ? topersia_g_t( (string) $group['more'] ) : '';
		$link = '';

		/*
		 * `.more` مرجع یک `<a>` درون‌خط است؛ همان می‌ماند و مودالِ فهرست را
		 * باز می‌کند، نه برگۀ هابی که هنوز نیست.  P5-D221
		 * نشانی‌اش خودِ مودال است: بی‌جاوااسکریپت هم جای درستی می‌رود.
		 */
		if ( '' !== $more && '' !== $list ) {
			$link = '<a class="tp-more" href="#' . esc_attr( $list ) . '" role="button" aria-haspopup="dialog"'
				. ' aria-controls="' . esc_attr( $list ) . '" data-tp-list-open>' . esc_html( $more ) . '</a>';
		} elseif ( '' !== $more && '' !== $hub ) {
			$link = '<a class="tp-more" href="' . esc_url( $hub ) . '">' . esc_html( $more ) . '</a>';
		}

		return '<div class="tp-section-head"><div>'
			. '<h2>' . esc_html( $title ) . '</h2>'
			. ( '' !== $lead ? '<p class="tp-lead">' . esc_html( $lead ) . '</p>' : '' )
			. '</div>'
			. $link
			. '</div>';
	}
}

if ( ! function_exists( 'topersia_g_group_kind' ) ) {
	function topersia_g_group_kind( $result ) {

		if ( ! empty( $result['items'][0]['kind'] ) ) {
			return (string) $result['items'][0]['kind'];
		}

		return esc_html__( 'مورد', 'topersia' );
	}
}

/**
 * جهت صفحه — همان منبعی که `<html dir>` را می‌سازد (`is_rtl()` زبان جاری).
 * نه زبانِ ثابت: فارسی و عربی راست‌به‌چپ، انگلیسی و بقیه چپ‌به‌راست.
 */
if ( ! function_exists( 'topersia_g_is_rtl' ) ) {
	function topersia_g_is_rtl() {
		return function_exists( 'is_rtl' ) ? (bool) is_rtl() : true;
	}
}

/**
 * نویسۀ دو فلشِ «قبلی» و «بعدی» — جایگزین P5-D226.  P5-D243
 *
 * فلش همیشه به سمتی اشاره می‌کند که محتوای دیده‌نشده آن‌جاست. در
 * راست‌به‌چپ محتوای بعدی سمت چپ است: «بعدی» = «‹» در سمت چپ، «قبلی» =
 * «›» در سمت راست. در چپ‌به‌راست برعکس. رفتار حرکتی همان ۴٫۳٫۰ است؛ فقط
 * نگاشت نویسه به کار برگشت.
 *
 * «‹» و «›» در متن راست‌به‌چپ آینه‌ای رسم می‌شوند (Bidi_Mirrored)، پس
 * نویسه در `<span dir="ltr">` می‌نشیند تا همان دیده شود که نوشته شده.
 *
 * @return array prev · next
 */
if ( ! function_exists( 'topersia_g_arrows' ) ) {
	function topersia_g_arrows() {
		return topersia_g_is_rtl()
			? array( 'prev' => '›', 'next' => '‹' )
			: array( 'prev' => '‹', 'next' => '›' );
	}
}

/**
 * نوار زیر اسلایدر — راهنما و دو دکمۀ پیمایش.
 *
 * با یک کارت اصلاً نمی‌آید: دکمۀ قبلی/بعدی وقتی جایی برای رفتن نیست،
 * فقط سر می‌دواند.
 */
if ( ! function_exists( 'topersia_g_slider_bar' ) ) {
	function topersia_g_slider_bar( $uid, $count ) {

		if ( $count < 2 ) {
			return '';
		}

		/*
		 * `span.slider-hint` مرجع و دو دکمۀ بی‌کلاس با data-target/data-dir.
		 *
		 * یک قاعده برای همۀ اسلایدرها و نمای تمام‌صفحه.  P5-D243
		 *   ترتیب DOM «قبلی» سپس «بعدی»: در راست‌به‌چپ «قبلی» سمت راست و
		 *   «بعدی» سمت چپ می‌نشیند، در چپ‌به‌راست برعکس — جای دیدنیِ هر
		 *   فلش با جهتش می‌خواند و ترتیب فوکوس همان ترتیب خواندن است
		 *   ابتدای اسلایدر: «بعدی» فعال، «قبلی» غیرفعال؛ انتها: «بعدی» غیرفعال
		 */
		$arrow = topersia_g_arrows();

		return '<div class="tp-slider-bar">'
			. '<span class="tp-slider-hint">' . esc_html__( 'برای کشف موارد بیشتر بکشید', 'topersia' ) . '</span>'
			. '<div class="tp-slider-nav">'
			. '<button type="button" data-target="' . esc_attr( $uid ) . '" data-dir="-1" disabled'
			. ' aria-label="' . esc_attr__( 'قبلی', 'topersia' ) . '"><span dir="ltr">' . $arrow['prev'] . '</span></button>'
			. '<button type="button" data-target="' . esc_attr( $uid ) . '" data-dir="1"'
			. ' aria-label="' . esc_attr__( 'بعدی', 'topersia' ) . '"><span dir="ltr">' . $arrow['next'] . '</span></button>'
			. '</div></div>';
	}
}

/**
 * یک واقعیت کلیدی — نخستین فیلدِ ناحیۀ `card` که مقدار دارد.
 *
 * فهرست دستی ندارد: همان `topersia_g_zone_fields()` را می‌خواند که
 * `[tp_stats]` می‌خواند، پس ترتیبِ اهمیت از نقشۀ فیلد می‌آید نه از حدس
 * ما، و برای هر نوع پست خودبه‌خود درست است.
 *
 * مختصات و بهترین فصل کنار گذاشته می‌شوند — یکی زیر نقشه می‌نشیند و
 * دیگری در جدول فصل‌ها، و هیچ‌کدام روی کارت معنا نمی‌دهند.
 *
 * @return string مقدارِ قالب‌بندی‌شده، یا رشتۀ خالی.
 */
if ( ! function_exists( 'topersia_g_key_fact' ) ) {
	function topersia_g_key_fact( $post_id ) {

		$fields = topersia_g_zone_fields( $post_id, 'card', array( 'geo_coordinates', 'best_season' ) );
		$map    = topersia_g_map();

		foreach ( array_keys( $fields ) as $name ) {

			/* «بله» تنها، روی کارت چیزی نمی‌گوید — پرچم حقیقت کلیدی نیست.  P5-D279 */
			if ( 'flag' === $map[ $name ]['kind'] ) {
				continue;
			}

			$raw = topersia_g_raw( $post_id, $name );

			if ( '' === $raw || null === $raw || array() === $raw ) {
				continue;
			}

			$value = trim( wp_strip_all_tags( topersia_g_value( $name, $raw ) ) );

			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}
}

/**
 * خانه‌های کارت آمار سربرگ، به ترتیب — حداکثر چهار.  P5-D282 · ۲ج٫۱
 *
 * نوعی که در ماتریس سطر `stats` دارد همان فهرست را به ترتیب می‌گیرد و هر
 * کلیدِ بی‌مقدار جایش را به کلید بعدی می‌دهد. بقیه قاعدۀ پیشین: مرکز (اگر
 * هست) + فیلدهای کوتاه `card` به ترتیب `topersia_g_stat_keys()`.
 *
 * @return array کلیدها: نام فیلد · `capital` · `count:{گروه}`
 */
if ( ! function_exists( 'topersia_g_stat_plan' ) ) {
	function topersia_g_stat_plan( $post_id ) {

		$row = topersia_g_type_row( $post_id );

		/* بی سطر در ماتریس (نوع آینده): قاعدۀ پیشین. سطرِ `stats` خالی یعنی بی کارت آمار. */
		if ( null === $row['stats'] ) {
			return array_merge( topersia_g_capital_id( $post_id ) ? array( 'capital' ) : array(), topersia_g_stat_keys( $post_id ) );
		}

		$plan = array();

		foreach ( $row['stats'] as $key ) {

			if ( count( $plan ) >= 4 ) {
				break;
			}

			if ( null !== topersia_g_stat_cell( $post_id, $key ) ) {
				$plan[] = $key;
			}
		}

		return $plan;
	}
}

/** بیشینۀ نویسۀ مقدارِ غیرعددیِ خانۀ آمار — یک خط در ۳۹۰ (P5-D282). */
if ( ! defined( 'TOPERSIA_STAT_MAX' ) ) {
	define( 'TOPERSIA_STAT_MAX', 16 );
}

/**
 * آمار حقیقی مستقل از زبان — P5-D306 · وصلۀ ۲ج و۲.
 *
 * «ایران ۳۱ استان دارد» واقعیتی دربارۀ جهان است، نه دربارۀ صفحه‌هایی که ما به
 * انگلیسی ترجمه کرده‌ایم: در /en/ شمردن به زبان صفحه «۱» می‌نوشت. پس شمار روی
 * گراف زبان پیش‌فرض است — پست اصل، فقط منتشرشده‌های فارسی (P5-D247) — در هر
 * زبان صفحه، با رقم همان زبان. فهرست، اسلایدر و پنجرۀ همان گروه دست نمی‌خورند:
 * فقط صفحه‌های همان زبان.
 */
if ( ! function_exists( 'topersia_g_fact_count' ) ) {
	function topersia_g_fact_count( $post_id, $group_key ) {

		$origin = topersia_g_default_id( $post_id );

		return (int) topersia_g_in_default_lang( function () use ( $origin, $group_key ) {
			return count( topersia_g_group_ids( $origin, topersia_g_group( $origin, $group_key ) ) );
		} );
	}
}
/**
 * یک خانۀ کارت آمار — برچسب و مقدارِ آمادۀ چاپ، یا null اگر داده نیست.
 *
 *   capital         مرکز یا پایتخت از رابطۀ یک‌به‌یک، با پیوند
 *   count:{گروه}    شمار اعضای منتشرشدۀ گروه رابطه (P5-D247)
 *   نام فیلد        برچسب و مقدار از FIELDMAP
 */
if ( ! function_exists( 'topersia_g_stat_cell' ) ) {
	function topersia_g_stat_cell( $post_id, $key ) {

		if ( 'capital' === $key ) {

			$capital = topersia_g_capital_id( $post_id );

			if ( ! $capital ) {
				return null;
			}

			$group = topersia_g_group( $post_id, 'capital' );
			$view  = topersia_g_view_id( $capital );
			$url   = topersia_g_public_url( $view );

			return array(
				isset( $group['title'] ) ? topersia_g_t( $group['title'] ) : '',
				'' !== $url
					? '<a href="' . esc_url( $url ) . '">' . esc_html( topersia_g_name( $view ) ) . '</a>'
					: esc_html( topersia_g_name( $view ) ),
			);
		}

		if ( 0 === strpos( $key, 'count:' ) ) {

			$labels = array( 'provinces' => __( 'شمار استان‌ها', 'topersia' ) );
			$group  = substr( $key, 6 );
			$count  = isset( $labels[ $group ] ) ? topersia_g_fact_count( $post_id, $group ) : 0;

			return $count ? array( $labels[ $group ], esc_html( topersia_g_fa( $count ) ) ) : null;
		}

		$map = topersia_g_map();
		$raw = topersia_g_raw( $post_id, $key );

		if ( ! isset( $map[ $key ] ) || ! topersia_g_filled( $raw ) ) {
			return null;
		}

		/*
		 * واحدِ بلند در خانۀ آمار نماد مرجع است — «۱۰۷٬۰۱۸ km²»، نه «کیلومتر مربع» —
		 * تا مقدار در ۳۹۰ یک خط بماند (P5-D282). کارت حقایق همان واحد کامل را دارد.
		 */
		if ( 'number' === $map[ $key ]['kind'] && ! empty( $map[ $key ]['abbr'] ) && ! is_array( $raw ) ) {
			$num   = trim( (string) $raw );
			$value = '' === $num ? '' : topersia_g_fa( topersia_g_sep( $num ) ) . ' <i class="tp-u">' . esc_html( $map[ $key ]['abbr'] ) . '</i>';
		} else {
			$value = topersia_g_value( $key, $raw, $post_id );
		}

		$plain = trim( wp_strip_all_tags( (string) $value ) );

		/*
		 * مقدار بلند هرگز در کارت آمار نمی‌آید (P5-D282) — خانه جایش را به نامزد
		 * بعدی ماتریس می‌دهد. عدد و سال کوتاه‌اند؛ متن آزاد، کد و برچسب واژه‌نامه
		 * بالای TOPERSIA_STAT_MAX نویسه در ۳۹۰ دو خط می‌شد («شهرستان کاشان،
		 * به‌ویژه قمصر و نیاسر» سه خط، هر دو مرورگر).
		 */
		if ( ! in_array( $map[ $key ]['kind'], array( 'number', 'year' ), true )
			&& ( function_exists( 'mb_strlen' ) ? mb_strlen( $plain, 'UTF-8' ) : strlen( $plain ) ) > TOPERSIA_STAT_MAX ) {
			return null;
		}

		return '' === $plain ? null : array( topersia_g_t( $map[ $key ]['label'] ), $value );
	}
}

if ( ! function_exists( 'topersia_g_stat_keys' ) ) {
	function topersia_g_stat_keys( $post_id ) {

		/* نوعِ ماتریس‌دار: فقط فیلدهای همان خانه‌ها — کارت حقایق بقیه را می‌گیرد. */
		if ( null !== topersia_g_type_row( $post_id )['stats'] ) {
			return array_values( array_filter( topersia_g_stat_plan( $post_id ), function ( $key ) {
				return 'capital' !== $key && 0 !== strpos( $key, 'count:' );
			} ) );
		}

		$slots = 4 - ( topersia_g_capital_id( $post_id ) ? 1 : 0 );

		/* پرچم («آتشفشانی: بله») جای کارت حقایق است، نه آمار سربرگ.  P5-D279 · P5-D282 */
		$map    = topersia_g_map();
		$fields = array_filter( topersia_g_zone_fields( $post_id, 'card', array( 'geo_coordinates', 'best_season' ) ), function ( $name ) use ( $map ) {
			return 'flag' !== $map[ $name ]['kind'];
		}, ARRAY_FILTER_USE_KEY );

		/*
		 * ترتیب کارت آمار صریح است، نه ترتیبِ نقشۀ فیلد.  P5-D208
		 *
		 * خواسته: مرکز · جمعیت · مساحت · اقلیم. با ترتیبِ نقشه،
		 * `province_code` پیش از `climate_type` می‌افتاد و خانۀ چهارم را
		 * می‌گرفت. کد استان جای خودش کارت حقایق است — و چون آن کارت هرچه
		 * را آمار نبرده نشان می‌دهد، همین‌که از این فهرست بیرون بماند
		 * خودبه‌خود آن‌جا می‌رود.
		 *
		 * فهرست برای هر سیزده نوع پست یکی است؛ هر نوع هرچه را دارد از آن
		 * برمی‌دارد. آنچه نامش این‌جا نیست، به ترتیبِ نقشه ته صف می‌رود —
		 * پس نوع تازه بی‌صدا خالی نمی‌ماند.
		 *
		 * ترتیب از لایۀ دادۀ مرجع می‌آید: مرکز · کد · مساحت · جمعیت.
		 * `P5-D208` (مرکز · جمعیت · مساحت · اقلیم) لغو شد.  P5-D215
		 */
		$order = array( 'province_code', 'area_km2', 'population', 'climate_type', 'elevation_m', 'founded_year' );

		$ranked = array();

		foreach ( $order as $name ) {
			if ( isset( $fields[ $name ] ) ) { $ranked[] = $name; }
		}

		foreach ( array_keys( $fields ) as $name ) {
			if ( ! in_array( $name, $ranked, true ) ) { $ranked[] = $name; }
		}

		return array_slice( $ranked, 0, max( 0, $slots ) );
	}
}

/** [tp_stats] — کارت آمار: مرکز (رابطۀ یک‌به‌یک) + سه حقیقت سرخط. */
if ( ! function_exists( 'topersia_g_sc_stats' ) ) {
	function topersia_g_sc_stats( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		/* مختصات زیر نقشه می‌نشیند و بهترین فصل در جدول فصل‌ها. */
		$cells = array();

		foreach ( topersia_g_stat_plan( $pid ) as $key ) {

			$cell = topersia_g_stat_cell( $pid, $key );

			if ( null !== $cell ) {
				$cells[] = $cell;
			}
		}

		if ( empty( $cells ) ) {
			return '';
		}

		$out = '';

		foreach ( $cells as $cell ) {

			$out .= '<div class="tp-stat" role="listitem">'
				. '<span class="tp-stat__k">' . esc_html( $cell[0] ) . '</span>'
				. '<b class="tp-stat__v">' . $cell[1] . '</b></div>';
		}

		/* شمار خانه‌ها برای شبکه — کمتر از چهار، بی جای خالی.  P5-D275 */
		return '<div class="tp-wrap tp-stats-wrap"><div class="tp-stats" role="list" data-tp-n="' . count( $cells ) . '" aria-label="'
			. esc_attr__( 'اطلاعات کلیدی', 'topersia' ) . '">' . $out . '</div></div>';
	}
}

/** [tp_facts] — هر فیلد کارتی که در آمار نیامده، و فهرست جزئیات. */
if ( ! function_exists( 'topersia_g_sc_facts' ) ) {
	function topersia_g_sc_facts( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$map  = topersia_g_map();
		$body = '';

		/*
		 * کارت `#geo` مرجع فقط `.geo-grid` است: حقایق کوتاه.  P5-D219
		 *
		 * فیلدهای ناحیۀ `details` — محدودۀ جغرافیایی، مرور اقلیم،
		 * زمینۀ تاریخی، دسترسی — از این کارت برداشته شدند و به
		 * `#overview` رفتند. آن‌ها پاراگراف‌اند، نه حقیقتِ یک‌خطی؛ در
		 * ستون ۳۴۰ پیکسلی خوانده نمی‌شدند.
		 */
		$taken  = array_merge( topersia_g_stat_keys( $pid ), array( 'geo_coordinates', 'best_season' ) );
		$fields = topersia_g_zone_fields( $pid, 'card', $taken );

		/*
		 * کاشی‌های `.neighbors` مرجع: نام فیلد بالا، مقدار پررنگ.  P5-D244
		 * پیش از آن‌ها کاشی‌های والد مکانی R01 (استان، شهر، روستا — بی کشور، بی همتا) و مرکزیت (مرکز استان؛ مرکز شهرستان فقط
		 * با داده)، با پیوند. کارت برای هر نوعی که والد مکانی یا مرکزیت دارد ساخته می‌شود، حتی بی فیلد ناحیۀ card؛ فیلد دیگری
		 * به آن اضافه نمی‌شود.  P5-D339 · P5-D342
		 */
		$tiles = function_exists( 'topersia_g_geo_parent_tiles' ) ? topersia_g_geo_parent_tiles( $pid ) : array();

		if ( function_exists( 'topersia_g_geo_center_tiles' ) ) {
			$tiles = array_merge( $tiles, topersia_g_geo_center_tiles( $pid ) );
		}

		/* نوع غیرمکانی بی والد مکانی: مکان‌های مرتبط از رابطه‌های موجود (استان/شهر/روستا).  P5-D348 */
		if ( ! $tiles && function_exists( 'topersia_g_geo_related_tiles' ) ) {
			$tiles = topersia_g_geo_related_tiles( $pid );
		}

		foreach ( $fields as $name => $raw ) {
			$tiles[] = array( 'label' => topersia_g_t( $map[ $name ]['label'] ), 'name' => topersia_g_value( $name, $raw ), 'raw' => true );
		}

		/* برگزارکنندۀ رویداد و وب‌سایت رسمی — بعد از کاشی‌های تاریخ.  P5-D361 */
		$tiles = array_merge( $tiles, topersia_g_event_extra_tiles( $pid ) );

		if ( $tiles ) {
			$body .= topersia_g_tiles( $tiles );
		}

		return topersia_g_section( 'tp-geo', 'اطلاعات جغرافیایی', 'GEO', $body );
	}
}

/** [tp_badges] — سوییچرهای درست، جفت‌شده با سالشان. */
if ( ! function_exists( 'topersia_g_sc_badges' ) ) {
	function topersia_g_sc_badges( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		/* کاشی‌های `.neighbors` مرجع: سال بالا (اگر هست)، نام نشان پررنگ.  P5-D244 */
		$tiles = array();

		foreach ( topersia_g_map() as $name => $field ) {

			if ( 'badge' !== $field['zone'] || 'flag' !== $field['kind'] ) {
				continue;
			}

			if ( ! topersia_g_flag_on( topersia_g_raw( $pid, $name ) ) ) {
				continue;
			}

			$year = '';

			if ( ! empty( $field['year'] ) ) {

				$raw = topersia_g_raw( $pid, $field['year'] );

				if ( topersia_g_filled( $raw ) ) {
					$year = wp_strip_all_tags( topersia_g_value( $field['year'], $raw ) );
				}
			}

			$tiles[] = array( 'label' => $year, 'name' => topersia_g_t( $field['label'] ) );
		}

		if ( ! $tiles ) {
			return '';
		}

		return topersia_g_section( 'tp-badges', 'نشان‌ها', 'STATUS', topersia_g_tiles( $tiles ) );
	}
}

/**
 * [tp_seasons] — جدول چهار فصل.
 *
 * فیلد best_season یک select تک‌مقداری روی واژه‌نامۀ ۳۴ است
 * (spring · summer · autumn · winter · all_year) — نه بازۀ متنی ماه.
 * پس ماه‌های همان فصل روشن می‌شوند و تطبیق زیررشته‌ای انجام نمی‌شود؛
 * «دی» زیررشتۀ «اردیبهشت» است و آن مسیر یک بار هزینه داده.
 */
if ( ! function_exists( 'topersia_g_sc_seasons' ) ) {
	function topersia_g_sc_seasons( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		/*
		 * برای کشور همین فیلد چندگزینه‌ای است (`is_multiple` در اسکین) و
		 * JetEngine آرایه ذخیره می‌کند. `(string)` روی آرایه «Array» می‌داد
		 * و کارت بی‌صدا ناپدید می‌شد.  ۴٫۲٫۱ بند ۱
		 */
		$raw    = topersia_g_raw( $pid, 'best_season' );
		$picked = array();

		foreach ( is_array( $raw ) ? $raw : array( $raw ) as $one ) {
			$one = is_scalar( $one ) ? trim( (string) $one ) : '';
			if ( '' !== $one ) {
				$picked[] = $one;
			}
		}

		if ( ! $picked ) {
			return '';
		}

		$seasons = array(
			'spring' => array( 'بهار', array( 'فروردین', 'اردیبهشت', 'خرداد' ) ),
			'summer' => array( 'تابستان', array( 'تیر', 'مرداد', 'شهریور' ) ),
			'autumn' => array( 'پاییز', array( 'مهر', 'آبان', 'آذر' ) ),
			'winter' => array( 'زمستان', array( 'دی', 'بهمن', 'اسفند' ) ),
		);

		/*
		 * زبان دیگر: ماه میلادیِ همان فصل (بهار ← مارس تا مه)، نه نام ماه شمسی
		 * که برای خوانندۀ انگلیسی و عربی معنایی ندارد. فصل شمسی از ۱ فروردین
		 * (۲۱ مارس) آغاز می‌شود؛ سه ماهِ میلادیِ بیشترِ آن فصل.  ۴٫۴٫۲
		 */
		if ( 'fa' !== topersia_g_page_lang() ) {

			$greg = topersia_g_greg_months();

			foreach ( array( 'spring' => array( 2, 3, 4 ), 'summer' => array( 5, 6, 7 ), 'autumn' => array( 8, 9, 10 ), 'winter' => array( 11, 0, 1 ) ) as $key => $idx ) {
				$seasons[ $key ][1] = array( $greg[ $idx[0] ], $greg[ $idx[1] ], $greg[ $idx[2] ] );
			}
		}

		foreach ( $seasons as $key => $season ) {
			$seasons[ $key ][0] = topersia_g_t( $season[0] );
		}

		$all    = in_array( 'all_year', $picked, true );
		$picked = array_values( array_intersect( $picked, array_keys( $seasons ) ) );

		if ( ! $all && ! $picked ) {
			return '';
		}

		$value = $all ? 'all_year' : $picked[0];
		$grid  = '';

		foreach ( $seasons as $key => $season ) {

			$on    = ( $all || in_array( $key, $picked, true ) );
			$class = 'tp-season tp-season--' . $key . ( $on ? ' tp-has-on' : '' );
			$cells = '<div class="tp-season__h">' . esc_html( $season[0] ) . '</div>';

			foreach ( $season[1] as $month ) {
				$cells .= '<div class="tp-month' . ( $on ? ' tp-is-on' : '' ) . '">' . esc_html( $month ) . '</div>';
			}

			$grid .= '<div class="' . esc_attr( $class ) . '">' . $cells . '</div>';
		}

		$caption = $all
			? topersia_g_glossary_label( '34', $value )
			: implode( topersia_g_list_sep(), array_map( function ( $one ) {
				return topersia_g_glossary_label( '34', $one );
			}, $picked ) );

		$body = '<div class="tp-seasons" role="img" aria-label="'
			/* translators: %s: فصل‌ها */
			. esc_attr( sprintf( __( 'بهترین زمان سفر: %s', 'topersia' ), $caption ) ) . '">' . $grid . '</div>'
			. '<p class="tp-seasons__cap">' . esc_html( $caption ) . '</p>';

		return topersia_g_section( 'tp-season', 'بهترین زمان سفر', 'WHEN', $body );
	}
}

/**
 * [tp_weather] — هوای سه روز.
 *
 * فقط پوسته و جای خالی. دادۀ زنده نباید در HTML کش‌شده بیاید، پس پس از
 * بارگذاری از یک نقطۀ REST با کش نیم‌ساعته خوانده می‌شود. مبنای مکان همان
 * geo_coordinates خود رکورد است؛ فیلد تازه‌ای لازم نیست.
 */
if ( ! function_exists( 'topersia_g_sc_weather' ) ) {
	function topersia_g_sc_weather( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		/*
		 * مختصات از تابع مرکزی هسته (۲ه٫۵ · P5-D314): دهدهی، درجه-دقیقه-ثانیه، رقم فارسی،
		 * GeoJSON ← «lat, lng» دهدهی؛ نامعتبر یا بیرون از ایران ← کارت هوا نمی‌آید.
		 * بی هستۀ ۴٫۱۳٫۲ همان رشتۀ خام (رفتار پیشین).
		 */
		$coords = topersia_g_text( $pid, 'geo_coordinates' );
		$coords = function_exists( 'topersia_geo_attr' ) ? topersia_geo_attr( $coords ) : $coords;

		if ( '' === $coords ) {
			return '';
		}

		/* سطر آخر، منبع داده — شرط مجوز MET و راستی برای کاربر.  P5-D242 */
		$body = '<div class="tp-wx" data-tp-weather data-tp-coords="' . esc_attr( $coords ) . '">'
			. '<div class="tp-wx__day tp-is-load"></div>'
			. '<div class="tp-wx__day tp-is-load"></div>'
			. '<div class="tp-wx__day tp-is-load"></div></div>'
			. '<p class="tp-wx__src" data-tp-wx-note>' . esc_html__( 'در حال گرفتن داده…', 'topersia' ) . '</p>'
			. '<p class="tp-wx__src tp-wx__credit">' . esc_html( function_exists( 'topersia_g_wx_credit' ) ? topersia_g_wx_credit() : '' ) . '</p>';

		return topersia_g_section( 'tp-weather', 'هوای این روزها', 'FORECAST', $body );
	}
}

/**
 * ارائه‌دهندۀ نقشه — لایۀ آداپتور.  P5-D195 · P5-D241
 *
 * صفحه با نقشه فقط از راه همین پیکربندی و رابط `TopersiaMap` در
 * topersia-g-zones.js حرف می‌زند؛ کتابخانه و منبع کاشی پشت آن‌اند و با
 * فیلتر `topersia_g_map_provider` جایگزین می‌شوند — همان الگوی آداپتور هوا.
 *
 * ارائه‌دهندۀ فعلی: MapLibre GL JS 5 + PMTiles خودمیزبان (نقشۀ پایۀ
 * Protomaps، دادۀ OpenStreetMap). کتابخانه، قلم‌ها و اسپرایت در
 * assets/map افزونه‌اند؛ کاشی در uploads/tiles/iran.pmtiles. اگر پروندۀ
 * کاشی نیست، آرایۀ خالی — و کارت همان SVG مرجع می‌ماند.
 *
 * @return array name · base · tiles · attribution — یا خالی
 */
if ( ! function_exists( 'topersia_g_map_provider' ) ) {
	function topersia_g_map_provider() {

		$cfg = array();

		if ( function_exists( 'wp_upload_dir' ) && function_exists( 'topersia_render_url' ) ) {

			/*
			 * «سرویس‌های خارجی» هسته (map.tiles): ارائه‌دهندۀ آنلاین (پیش‌فرض OpenFreeMap) با PMTiles خودمیزبان پشتیبان؛ ارائه‌دهندۀ «pmtiles» ←
			 * آفلاین اول. خاموش ← بی نقشۀ زنده. اولویت از پیشخوان قابل تنظیم است.  P5-D249 · P5-D353
			 */
			/* نه topersia_g_layer: آن فقط سطرِ ذخیره‌شده را می‌دهد؛ پیش‌فرض کاتالوگ (آنلاین اول) هم باید کار کند */
			$layer = function_exists( 'topersia_api' ) ? topersia_api( 'map.tiles', false, true ) : null;
			$layer = is_array( $layer ) && isset( $layer['provider'] ) ? $layer : null;
			$prov  = $layer && ! empty( $layer['provider'] ) ? (string) $layer['provider'] : 'pmtiles';
			$rel   = 'pmtiles' === $prov && $layer && ! empty( $layer['extra']['path'] ) ? ltrim( (string) $layer['extra']['path'], '/' ) : 'tiles/iran.pmtiles';
			$up    = wp_upload_dir( null, false );
			$file  = trailingslashit( (string) $up['basedir'] ) . $rel;
			$on    = ! $layer || ! empty( $layer['enabled'] );
			$have  = is_file( $file );

			$online = array();

			if ( $on && 'openfreemap' === $prov && $layer && ! empty( $layer['extra']['style_light'] ) && ! empty( $layer['extra']['style_dark'] ) ) {
				$online = array(
					'provider'    => 'openfreemap',
					'style_light' => esc_url_raw( (string) $layer['extra']['style_light'] ),
					'style_dark'  => esc_url_raw( (string) $layer['extra']['style_dark'] ),
					'probe_ms'    => isset( $layer['extra']['probe_ms'] ) ? max( 500, min( 8000, (int) $layer['extra']['probe_ms'] ) ) : 2500,
				);
			}

			if ( $on && ( $have || $online ) ) {

				$cfg = array(
					'name'        => 'maplibre-pmtiles',
					'base'        => topersia_render_url() . 'assets/map/',
					'tiles'       => $have ? set_url_scheme( trailingslashit( (string) $up['baseurl'] ) . $rel ) . '?v=' . filemtime( $file ) : '',
					'attribution' => __( '© مشارکت‌کنندگان OpenStreetMap', 'topersia' ),
					'order'       => 'pmtiles' === $prov ? 'offline-first' : 'online-first',
					'styles'      => $have ? array( 'base', 'transit' ) : array( 'base' ),
				);

				if ( $online ) {
					$cfg['online'] = $online;
				}

				/* ماهواره: فقط با ارائه‌دهندۀ کلیددارِ پیکربندی‌شده و روشن؛ برچسب‌ها از PMTiles خودمان */
				$sat = function_exists( 'topersia_api' ) ? topersia_api( 'map.satellite' ) : array();

				if ( $have && is_array( $sat ) && isset( $sat['status'] ) && 'active' === $sat['status'] && ! empty( $sat['key'] ) && ! empty( $sat['endpoint'] ) ) {
					$cfg['sat']      = esc_url_raw( add_query_arg( 'key', rawurlencode( (string) $sat['key'] ), (string) $sat['endpoint'] ) );
					$cfg['styles'][] = 'satellite';
				}
			}
		}
		if ( function_exists( 'apply_filters' ) ) {
			$cfg = (array) apply_filters( 'topersia_g_map_provider', $cfg );
		}

		return $cfg;
	}
}

/**
 * بزرگنمایی آغاز نقشه به نوع موجودیت — استان کل استان را نشان دهد،
 * شهر بافتش را.
 */
if ( ! function_exists( 'topersia_g_map_zoom' ) ) {
	function topersia_g_map_zoom( $post_id ) {

		$zoom = array(
			'tp_country' => 4, 'tp_region' => 5, 'tp_province' => 6, 'tp_county' => 8, 'tp_city' => 10,
			'tp_district' => 13, 'tp_village' => 12, 'tp_island' => 10, 'tp_mountain' => 9, 'tp_water_body' => 8,
			'tp_desert' => 7, 'tp_forest_plain' => 8, 'tp_border_crossing' => 12,
		);

		$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';

		return isset( $zoom[ $type ] ) ? $zoom[ $type ] : 10;
	}
}

/** انتخاب‌گر سبک نقشه در مودال: پایه · حمل‌ونقل و قطار · ماهواره (فقط سبک‌های موجود).  P5-D353 */
if ( ! function_exists( 'topersia_g_map_style_bar' ) ) {
	function topersia_g_map_style_bar( $post_id ) {

		$cfg = topersia_g_map_provider();

		if ( empty( $cfg['styles'] ) || count( $cfg['styles'] ) < 2 ) {
			return '';
		}

		$labels = array(
			'base'      => __( 'پایه', 'topersia' ),
			'transit'   => __( 'حمل‌ونقل و قطار', 'topersia' ),
			'satellite' => __( 'ماهواره', 'topersia' ),
		);

		$out = '';

		foreach ( $cfg['styles'] as $key ) {
			if ( isset( $labels[ $key ] ) ) {
				$out .= '<button type="button" data-style="' . esc_attr( $key ) . '" aria-pressed="false">' . esc_html( $labels[ $key ] ) . '</button>';
			}
		}

		return '<div class="tp-map-styles" data-tp-map-styles role="group" aria-label="' . esc_attr__( 'سبک نقشه', 'topersia' ) . '">' . $out . '</div>';
	}
}
/** دادۀ نقشۀ زنده برای یک ظرف — یا رشتۀ خالی. */
if ( ! function_exists( 'topersia_g_map_attr' ) ) {
	function topersia_g_map_attr( $post_id ) {

		$cfg = topersia_g_map_provider();

		if ( empty( $cfg['base'] ) || ( empty( $cfg['tiles'] ) && empty( $cfg['online'] ) ) ) {
			return '';
		}

		$cfg['zoom']   = topersia_g_map_zoom( $post_id );
		$cfg['lang']   = topersia_g_page_lang();
		$cfg['locale'] = topersia_g_map_locale();

		/* نشانگرهای استان و شهر (شهرها، جاذبه‌ها، طبیعت · محله‌ها، روستاها) از نمایۀ مشتق هسته  P5-D346 */
		if ( function_exists( 'topersia_g_map_points_on' ) && topersia_g_map_points_on( $post_id ) ) {
			$cfg['points'] = esc_url_raw( add_query_arg( 'context', absint( $post_id ), rest_url( 'topersia/v1/map-points' ) ) );
		}

		/* مرز محدوده — GeoJSON مشتق از OSM (scripts/tp-boundaries.php)؛ فقط اگر فایلِ همین موجودیت هست.  P5-D351 */
		$uid = function_exists( 'topersia_g_boundary_key' ) ? topersia_g_boundary_key( topersia_g_text( $post_id, 'entity_uid' ) ) : '';

		if ( '' !== $uid && function_exists( 'wp_get_upload_dir' ) ) {
			$up   = wp_get_upload_dir();
			$file = trailingslashit( (string) $up['basedir'] ) . 'tiles/boundaries/' . $uid . '.geojson';

			if ( is_file( $file ) ) {
				$cfg['bound'] = set_url_scheme( trailingslashit( (string) $up['baseurl'] ) . 'tiles/boundaries/' . $uid . '.geojson' ) . '?v=' . filemtime( $file );
			}
		}

		return " data-tp-map-cfg='" . esc_attr( wp_json_encode( $cfg ) ) . "'";
	}
}

/**
 * رشته‌های رابط خودِ MapLibre — دکمه‌ها و aria-label نقشه و نشانگر — به زبان
 * صفحه، با گزینۀ مستند `locale` نقشه (کلیدهای MapLibre 5.x). برچسب کاشی جدا
 * از `lang` سبک Protomaps است.  P5-D278
 */
if ( ! function_exists( 'topersia_g_map_locale' ) ) {
	function topersia_g_map_locale() {
		return array(
			'Map.Title'                            => __( 'نقشه', 'topersia' ),
			'Marker.Title'                         => __( 'نشانگر نقشه', 'topersia' ),
			'NavigationControl.ZoomIn'             => __( 'بزرگ‌نمایی', 'topersia' ),
			'NavigationControl.ZoomOut'            => __( 'کوچک‌نمایی', 'topersia' ),
			'NavigationControl.ResetBearing'       => __( 'بکشید تا نقشه بچرخد؛ کلیک: شمال به بالا', 'topersia' ),
			'AttributionControl.ToggleAttribution' => __( 'نمایش یا پنهان‌کردن منبع نقشه', 'topersia' ),
			'AttributionControl.MapFeedback'       => __( 'بازخورد نقشه', 'topersia' ),
			'Popup.Close'                          => __( 'بستن پنجرۀ نقشه', 'topersia' ),
		);
	}
}

/** [tp_map] — نقشه با پاپ‌آپ و مختصات زیرش. */
if ( ! function_exists( 'topersia_g_sc_map' ) ) {
	function topersia_g_sc_map( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$coords = topersia_g_text( $pid, 'geo_coordinates' );

		if ( '' === $coords ) {
			return '';
		}

		/* data-tp-coords دهدهیِ نرمال‌شده (جاوااسکریپت نقشه فقط «lat, lng» می‌خواند)؛ متن دیدنی همان خام  ۲ه٫۵ */
		$coords_attr = function_exists( 'topersia_geo_attr' ) ? topersia_geo_attr( $coords ) : '';
		$coords_attr = '' !== $coords_attr ? $coords_attr : $coords;

		$uid = 'tpmap' . $pid;

		/*
		 * سطر دوم پاپ‌آپ مرجع: «مرکز: شهر اصفهان · ۲۸ شهرستان».  P5-D219
		 * مرکز از رابطۀ یک‌به‌یکِ `capital` و شمار از گروه `places` — هر
		 * کدام نبود، همان تکه نمی‌آید؛ هیچ‌کدام نبود، سطر نمی‌آید.
		 */
		$pop = array();

		$capital = topersia_g_capital_id( $pid );

		if ( $capital ) {
			$pop[] = sprintf(
				/* translators: %s: نام مرکز */
				__( 'مرکز: %s', 'topersia' ),
				topersia_g_name( topersia_g_view_id( $capital ) )
			);
		}

		/* گروه `places` از P5-D220 زنجیره است، نه رابطۀ مستقیم — «۱۲۴ شهر». */
		$ids = topersia_g_group_ids( $pid, topersia_g_group( $pid, 'places' ) );

		if ( $ids ) {
			/* فارسی «۱۲۴ شهر»؛ زبان دیگر «Cities: 124» — بی شکل جمعِ نوع پست  ۴٫۴٫۲ */
			$places = topersia_g_group( $pid, 'places' );
			$pop[]  = 'fa' === topersia_g_page_lang() || empty( $places['title'] )
				? topersia_g_fa( count( $ids ) ) . ' ' . topersia_g_kind_label( topersia_g_view_id( reset( $ids ) ) )
				: topersia_g_t( $places['title'] ) . ': ' . topersia_g_fa( count( $ids ) );
		}

		/*
		 * نقشۀ زنده روی همان SVG مرجع می‌نشیند و SVG تا بار شدنش دیده
		 * می‌شود.  P5-D241 — غیرتعاملی، با IntersectionObserver بار می‌شود؛
		 * بی پیکربندی (پروندۀ کاشی نیست) لایۀ زنده اصلاً ساخته نمی‌شود.
		 */
		$live = topersia_g_map_attr( $pid );

		$body = '<div class="tp-map-inner" data-tp-map data-tp-coords="' . esc_attr( $coords_attr ) . '"' . $live . '>'
			. ( '' !== $live ? '<div class="tp-map-live" data-tp-map-live aria-hidden="true"></div>' : '' )
			. '<svg class="tp-map-svg" viewBox="0 0 320 230" role="img" aria-label="'
			. esc_attr__( 'نمای شماتیک نقشه', 'topersia' ) . '">'
			. '<defs><pattern id="' . esc_attr( $uid ) . '" width="24" height="24" patternUnits="userSpaceOnUse">'
			. '<path d="M24 0H0V24" fill="none" stroke="currentColor" stroke-opacity=".16" stroke-width="1"/>'
			. '</pattern></defs>'
			. '<rect width="320" height="230" fill="url(#' . esc_attr( $uid ) . ')"/>'
			. '<path d="M40 60 C 80 40, 140 50, 190 42 C 240 36, 280 60, 290 100'
			. ' C 296 140, 260 180, 210 190 C 160 200, 100 195, 60 175 C 30 160, 22 110, 40 60 Z"'
			. ' fill="none" stroke="currentColor" stroke-width="1.6" stroke-dasharray="4 3" opacity=".65"/>'
			. '<circle cx="150" cy="118" r="3" fill="currentColor" opacity=".35"/>'
			. '<circle cx="95" cy="90" r="2.4" fill="currentColor" opacity=".3"/>'
			. '<circle cx="220" cy="150" r="2.4" fill="currentColor" opacity=".3"/>'
			. '</svg>'
			. '<div class="tp-map-pop" role="note"><b>' . esc_html( topersia_g_name( $pid ) ) . '</b>'
			. ( $pop ? '<span>' . esc_html( implode( ' · ', $pop ) ) . '</span>' : '' ) . '</div>'
			. '<button class="tp-map-pin" type="button" aria-label="'
			. esc_attr__( 'نمایش اطلاعات نشانگر', 'topersia' ) . '"></button></div>'
			. '<div class="tp-map-coord"><span class="tp-lbl">'
			. esc_html__( 'مختصات مرکز', 'topersia' ) . '</span>'
			. '<span dir="ltr">' . esc_html( $coords ) . '</span></div>';

		/* دکمه در مرجع بیرون از بدنۀ کارت است، تمام‌عرض و چسبیده به لبه. */
		$after = '<button class="tp-map-btn" type="button" data-tp-map-open>'
			. esc_html__( 'باز کردن نقشۀ کامل', 'topersia' ) . '</button>';

		return topersia_g_section( 'tp-map', 'نقشه', 'MAP', $body, 'tp-rail-card', 'padding:0', $after );
	}
}

/**
 * سال ثبتِ یک جاذبه — «ثبت ۱۹۷۹» مرجع.  P5-D219
 *
 * فهرست دستی ندارد: هر فیلد نشان که `year` دارد و خودش روشن است، سالش
 * را می‌دهد. نخستینِ مقداردار برنده است.
 *
 * @return string رشتۀ آمادۀ چاپ، یا خالی.
 */
if ( ! function_exists( 'topersia_g_listed_year' ) ) {
	function topersia_g_listed_year( $post_id ) {

		foreach ( topersia_g_map() as $name => $field ) {

			if ( empty( $field['year'] ) || 'badge' !== $field['zone'] ) {
				continue;
			}

			if ( ! topersia_g_flag_on( topersia_g_raw( $post_id, $name ) ) ) {
				continue;
			}

			$raw = topersia_g_raw( $post_id, $field['year'] );

			if ( ! topersia_g_filled( $raw ) ) {
				continue;
			}

			return sprintf(
				/* translators: %s: سال ثبت */
				esc_html__( 'ثبت %s', 'topersia' ),
				trim( wp_strip_all_tags( topersia_g_value( $field['year'], $raw ) ) )
			);
		}

		return '';
	}
}

/**
 * تصویر کارت — `hero_image` خودِ موجودیت، وگرنه تصویر شاخص.  P5-D246
 *
 * بی تصویر، هنرِ گرادیانی مرجع سر جایش می‌ماند. با تصویر، `<img>` با
 * width و height صریح و srcset وردپرس، کلِ جعبۀ ۱۶۲ پیکسلی را با
 * `object-fit:cover` می‌پوشاند.
 *
 * @return int شناسۀ پیوست، یا صفر.
 */
if ( ! function_exists( 'topersia_g_card_image' ) ) {
	function topersia_g_card_image( $post_id ) {

		$hero = topersia_g_raw( $post_id, 'hero_image' );

		if ( is_array( $hero ) ) {
			$hero = isset( $hero['id'] ) ? $hero['id'] : reset( $hero );
		}

		$id = is_scalar( $hero ) ? absint( $hero ) : 0;

		if ( ! $id && function_exists( 'get_post_thumbnail_id' ) ) {
			$id = absint( get_post_thumbnail_id( $post_id ) );
		}

		return $id;
	}
}

/**
 * یک کارت رابطه.  P5-D215
 *
 * برای `#places` دو چیز فرق می‌کند و هر دو از مرجع می‌آیند:
 *
 *   برچسب   کارت اول «مرکز استان» است، بقیه «شهرستان».
 *   پیوند   شهرستان صفحه ندارد (`P5-D172`)، ولی شهرِ مرکزش دارد —
 *           رابطۀ ۹۷. اگر نبود، کارت `<article>` می‌شود.
 */
if ( ! function_exists( 'topersia_g_place_item' ) ) {
	function topersia_g_place_item( $view, $key, $is_lead ) {

		$name = topersia_g_name( $view );
		$url  = topersia_g_public_url( $view );

		if ( 'places' !== $key ) {

			/*
			 * پاورقی جاذبه: شهر · سال ثبت.  P5-D219
			 *
			 * مرجع «ثبت ۱۹۷۹» دارد، نه نخستین حقیقتِ کارت. سال از همان
			 * فیلدهای سالِ نشان می‌آید که بخش «نشان‌ها» می‌خواند، پس
			 * هیچ فیلد تازه‌ای لازم نیست. نبود که باشد، همان حقیقت
			 * کلیدیِ همیشگی می‌آید.
			 */
			$fact  = 'attractions' === $key ? topersia_g_listed_year( $view ) : '';
			$place = '';

			if ( '' === $fact ) {
				$fact = topersia_g_key_fact( $view );
			}

			/*
			 * شهرِ جاذبه — از همان چهار رابطه‌ای که جاذبه را به شهر وصل
			 * می‌کنند، در جهت والد. انواع جاذبه در نقشۀ رابطه مدخل ندارند،
			 * پس `topersia_g_place_parent()` برایشان چیزی پیدا نمی‌کرد و
			 * نیمۀ اولِ «شهر · سال ثبت» خالی می‌ماند.
			 */
			if ( 'attractions' === $key ) {

				$city = topersia_g_rel_ids( $view, array(
					array( 'id' => 84, 'dir' => 'parents' ),
					array( 'id' => 86, 'dir' => 'parents' ),
					array( 'id' => 88, 'dir' => 'parents' ),
					array( 'id' => 93, 'dir' => 'parents' ),
				) );

				if ( $city ) {
					$place = topersia_g_name( topersia_g_view_id( absint( reset( $city ) ) ) );
				}
			}

			if ( '' === $place ) {
				$place = topersia_g_place_parent( $view );
			}

			return array(
				'kind'  => topersia_g_kind_label( $view ),
				'id'    => $view,
				'name'  => $name,
				'url'   => $url,
				'sum'   => topersia_g_text( $view, 'short_summary' ),
				'place' => $place,
				'fact'  => $fact,
				'img'   => topersia_g_card_image( $view ),
			);
		}

		/* شهرستان خودش صفحه ندارد؛ پیوند به شهرِ مرکزش می‌رود */
		$target = $view;

		if ( ! $is_lead && '' === $url ) {
			$seat = topersia_g_rel_ids( $view, array( array( 'id' => 97, 'dir' => 'children' ) ) );

			if ( $seat ) {
				$target = topersia_g_view_id( absint( reset( $seat ) ) );
				$url    = topersia_g_public_url( $target );
			}
		}

		return array(
			'kind'  => $is_lead
				? esc_html__( 'مرکز استان', 'topersia' )
				: topersia_g_kind_label( $view ),
			'id'    => $view,
			'name'  => $name,
			'url'   => $url,
			'sum'   => topersia_g_text( $view, 'short_summary' ),
			'place' => '',
			'fact'  => '',
			'counts' => topersia_g_place_counts( $target ),
			'img'    => topersia_g_card_image( $view ) ? topersia_g_card_image( $view ) : topersia_g_card_image( $target ),
		);
	}
}

/**
 * شمار جاذبه و رویدادِ یک شهر — برای پاورقی کارت مرجع.  P5-D215
 *
 * جاذبه از گروه `ancient` آن شهر می‌آید و رویداد از زیرخانوادۀ
 * `festivals` گروه `culture`. هیچ‌کدام فهرست دستی ندارند؛ همان نقشۀ
 * رابطه‌ای است که بقیۀ صفحه از آن می‌خواند.
 *
 * صفر که باشد، آن `span` اصلاً نمی‌آید.
 */
if ( ! function_exists( 'topersia_g_place_counts' ) ) {
	function topersia_g_place_counts( $view ) {

		$out = array( 'spots' => 0, 'events' => 0 );

		/*
		 * «N جاذبه» از گروه `attractions` شهر — همان چهار رابطه‌ای که بخش
		 * جاذبه‌ها از آن می‌خواند (۸۴ · ۸۶ · ۸۸ · ۹۳). نوعی که آن گروه را
		 * ندارد، همان `ancient` پیشین را می‌شمارد.  P5-D220
		 */
		$spots = topersia_g_group( $view, 'attractions' );

		if ( empty( $spots['rels'] ) && empty( $spots['chains'] ) ) {
			$spots = topersia_g_group( $view, 'ancient' );
		}

		$out['spots'] = count( topersia_g_group_ids( $view, $spots ) );

		$culture = topersia_g_group( $view, 'culture' );

		if ( ! empty( $culture['rels'] ) ) {

			$fest = array();

			foreach ( $culture['rels'] as $spec ) {
				if ( isset( $spec['fam'] ) && 'festivals' === $spec['fam'] ) { $fest[] = $spec; }
			}

			if ( $fest ) {
				$out['events'] = count( topersia_g_rel_ids( $view, $fest ) );
			}
		}

		return $out;
	}
}

/**
 * همۀ شناسه‌های یک گروه — رابطه‌های مستقیم به‌اضافۀ زنجیره‌ها، بی‌تکرار.
 *
 * زنجیره برای بخش‌هایی است که موجودیتشان یک پله پایین‌تر است: جاذبه‌های
 * استان روی خود استان ننشسته‌اند، روی شهرهایش‌اند، و شهر دو پله پایین‌تر
 * است (استان→شهرستان→شهر). بدون زنجیره، این بخش‌ها روی هر استانی خالی
 * درمی‌آمدند. یک جا، تا بخش و شمارندۀ پاپ‌آپ نقشه هرگز واگرا نشوند.
 */
if ( ! function_exists( 'topersia_g_group_ids' ) ) {
	function topersia_g_group_ids( $post_id, $group ) {

		$ids = empty( $group['rels'] ) ? array() : topersia_g_rel_ids( $post_id, $group['rels'] );

		foreach ( empty( $group['chains'] ) ? array() : $group['chains'] as $chain ) {
			foreach ( topersia_g_chain_ids( $post_id, $chain ) as $one ) {
				$ids[] = $one;
			}
		}

		return array_values( array_unique( array_map( 'absint', $ids ) ) );
	}
}

/**
 * رابطه‌های «اطلاعات سفر» به تفکیک دسته و نوعِ والد.  P5-D233
 *
 * از خروجی JetEngine (Jetengine_Skin.json): هر دسته به ازای نوعِ مکان
 * رابطۀ خودش را دارد. حمل‌ونقل هیچ رابطه‌ای با نوع‌های G ندارد، پس
 * این‌جا نیست. ویزا فقط در سطح کشور است و فقط روی خودِ کشور می‌آید.
 */
if ( ! function_exists( 'topersia_g_practical_map' ) ) {
	function topersia_g_practical_map() {

		$map = array(
			'health'    => array( 'tp_city' => 158, 'tp_province' => 157, 'tp_country' => 156 ),
			'etiquette' => array( 'tp_province' => 160, 'tp_country' => 159 ),
			'currency'  => array( 'tp_province' => 227, 'tp_country' => 226 ),
			'calendar'  => array( 'tp_city' => 230, 'tp_province' => 229, 'tp_country' => 228 ),
		);

		if ( function_exists( 'apply_filters' ) ) {
			$map = apply_filters( 'topersia_g_practical_map', $map );
		}

		return $map;
	}
}

/**
 * نیاکانِ مکانی، نزدیک‌ترین اول: شهر ← استان ← کشور.
 *
 * شهر از راه شهرستانش (۷۳ ← ۷۲) به استان می‌رسد و استان با ۷۱ به کشور.
 * شهرستان خودش صفحه و «اطلاعات سفر» ندارد، پس در زنجیره نمی‌آید.
 *
 * @return array<int,int>
 */
if ( ! function_exists( 'topersia_g_ancestors' ) ) {
	function topersia_g_ancestors( $post_id ) {

		$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';
		$out  = array();
		$prov = 0;

		/*
		 * فقط پیمودن — نیا خودش نمایش داده نمی‌شود، فقط رابطه‌هایش خوانده
		 * می‌شوند. پس از نیای منتشرنشده هم می‌گذرد (ایرانِ محلی پیش‌نویس
		 * است)؛ موردهای ارث‌رسیده خودشان از دروازه می‌گذرند.  P5-D247
		 */
		if ( 'tp_city' === $type ) {

			$county = topersia_g_rel_ids( $post_id, array( array( 'id' => 73, 'dir' => 'parents' ) ), false );
			$up     = $county ? topersia_g_rel_ids( topersia_g_walk_parent( $county ), array( array( 'id' => 72, 'dir' => 'parents' ) ), false ) : array();

			if ( $up ) {
				$prov  = topersia_g_walk_parent( $up );
				$out[] = $prov;
			}
		} elseif ( 'tp_province' === $type ) {
			$prov = absint( $post_id );
		}

		if ( $prov ) {

			$country = topersia_g_rel_ids( $prov, array( array( 'id' => 71, 'dir' => 'parents' ) ), false );

			if ( $country ) {
				$out[] = topersia_g_walk_parent( $country );
			}
		}

		return $out;
	}
}

/**
 * موردهای «اطلاعات سفر» با ارث‌بری.  ۴٫۳٫۰ بند ۸ · P5-D233
 *
 * برای هر دستۀ سلامت و ایمنی، آداب سفر، واحد پول و تقویم: اگر خودِ
 * موجودیت موردِ منتشرشده‌ای دارد همان، وگرنه از نزدیک‌ترین نیا (استان،
 * بعد کشور ایران). موردِ منتشرنشده حساب نمی‌شود، وگرنه یک پیش‌نویس
 * ارث‌بری را می‌بست و بخش خالی می‌ماند.
 *
 * خودِ کشور ارث نمی‌برد و همان رابطه‌های گروهش را دارد (ویزا هم).
 *
 * @return array<int,int>
 */
if ( ! function_exists( 'topersia_g_practical_ids' ) ) {
	function topersia_g_practical_ids( $post_id ) {

		$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';

		if ( 'tp_country' === $type ) {
			return topersia_g_group_ids( $post_id, topersia_g_group( $post_id, 'practical' ) );
		}

		$chain = array_merge( array( absint( $post_id ) ), topersia_g_ancestors( $post_id ) );
		$ids   = array();

		foreach ( topersia_g_practical_map() as $by_type ) {

			foreach ( $chain as $node ) {

				$node_type = function_exists( 'get_post_type' ) ? (string) get_post_type( $node ) : '';

				if ( empty( $by_type[ $node_type ] ) ) {
					continue;
				}

				/* فقط منتشرشده — دروازۀ مرکزی.  P5-D247 */
				$found = topersia_g_rel_ids( $node, array( array( 'id' => $by_type[ $node_type ], 'dir' => 'children' ) ) );

				if ( $found ) {
					$ids = array_merge( $ids, $found );
					break;
				}
			}
		}

		return array_values( array_unique( array_map( 'absint', $ids ) ) );
	}
}

/** سازندۀ مشترک بخش‌های رابطه. */
if ( ! function_exists( 'topersia_g_rel_items' ) ) {
	function topersia_g_rel_items( $post_id, $key, $limit ) {

		$group = topersia_g_group( $post_id, $key );

		if ( empty( $group['rels'] ) && empty( $group['chains'] ) ) {
			return array( 'items' => array(), 'total' => 0, 'group' => $group );
		}

		/* «اطلاعات سفر» از نزدیک‌ترین نیا ارث می‌برد.  P5-D233 */
		$ids = 'practical' === $key ? topersia_g_practical_ids( $post_id ) : topersia_g_group_ids( $post_id, $group );

		/*
		 * بخش `#places` کارت اولش مرکز استان است، بعد شهرستان‌ها —
		 * عیناً مرجع.  P5-D215
		 *
		 * مرکز از رابطۀ ۹۶ می‌آید و برچسبش فرق دارد، پس جدا از صف
		 * شهرستان‌ها جلو می‌افتد و در مرتب‌سازی شرکت نمی‌کند.
		 */
		$lead_id = 'places' === $key ? topersia_g_capital_id( $post_id ) : 0;

		if ( $lead_id ) {
			$ids = array_values( array_diff( $ids, array( $lead_id ) ) );
		}

		/* یک ترتیب برای همۀ کارت‌های موجودیت ماندگار.  P5-D211 */
		$ids   = topersia_g_sort_entities( $ids );
		$total = count( $ids );
		$items = array();
		$all   = array();

		if ( $lead_id ) {
			array_unshift( $ids, $lead_id );
			$total ++;
		}

		foreach ( $ids as $n => $one ) {

			if ( $limit && $n >= $limit ) {
				break;
			}

			$view = topersia_g_view_id( $one );
			$items[] = topersia_g_place_item( $view, $key, $lead_id && $one === $lead_id );
		}

		return array( 'items' => $items, 'total' => $total, 'group' => $group );
	}
}

/** [tp_slider rel="..."] — اسلاید کارت برای یک گروه رابطه. */
if ( ! function_exists( 'topersia_g_sc_slider' ) ) {
	function topersia_g_sc_slider( $atts = array() ) {

		$pid = topersia_g_pid( $atts );
		$key = isset( $atts['rel'] ) ? trim( (string) $atts['rel'] ) : '';

		if ( ! $pid || '' === $key ) {
			return '';
		}

		/*
		 * حداکثر هشت کارت در هر اسلایدرِ هر قالب، به ترتیب P5-D211.  P5-D276
		 * بیشتر: پیوند «همه … ←» کنار عنوان و مودالِ فهرست با همۀ اعضا در
		 * HTML سرور (P5-D221). هشت یا کمتر: نه پیوند، نه مودال.
		 */
		$max    = topersia_g_slider_max();
		$result = topersia_g_rel_items( $pid, $key, $max );

		if ( empty( $result['items'] ) ) {
			return '';
		}

		$group         = $result['group'];
		$group['more'] = $result['total'] > $max ? topersia_g_list_more( $group, $key ) : '';

		/* `places` شناسۀ خودش را دارد چون در مرجع `#places` است، نه
		   یکی از `#rel-*` ها.  P5-D215 */
		return topersia_g_slider_section( 'tp-' . $key, 'tpsl' . $pid . preg_replace( '/[^a-z0-9]+/i', '', $key ),
			$group, $result['items'], topersia_g_title( $pid ), '' !== $group['more'] ? 'tp-list-' . $key : '' );
	}
}

/** سقف کارت هر اسلایدر در همۀ قالب‌ها — بی صافی، تا ادعا قطعی بماند.  P5-D276 */
if ( ! function_exists( 'topersia_g_slider_max' ) ) {
	function topersia_g_slider_max() {
		return 8;
	}
}

/**
 * بخش اسلایدر — یک سازنده برای گروه‌های رابطه و برنامه‌های نشان.  P5-D276
 *
 * @param string $sec_id  شناسۀ بخش.
 * @param string $uid     شناسۀ اسلایدر (هدف دکمه‌های پیمایش).
 * @param array  $group   title · lead · more (برچسب پیوند فهرست، خالی = بی پیوند) · hub.
 * @param array  $items   کارت‌ها — حداکثر `topersia_g_slider_max()`.
 * @param string $entity  نامی که جای `%s` متن راهنما می‌نشیند.
 * @param string $list    شناسۀ مودالِ فهرست، یا خالی.
 */
if ( ! function_exists( 'topersia_g_slider_section' ) ) {
	function topersia_g_slider_section( $sec_id, $uid, $group, $items, $entity, $list ) {

		$items = array_slice( $items, 0, topersia_g_slider_max() );
		$cards = '';
		$no    = 0;

		foreach ( $items as $item ) {

			$no ++;

			/*
			 * نوعی که صفحۀ عمومی ندارد، پیوند هم نمی‌گیرد: کارتش
			 * `<article>` است و حالت هاور ندارد.  P5-D210
			 */
			$linked = '' !== $item['url'];
			$open   = $linked
				? '<a class="tp-card-slide" href="' . esc_url( $item['url'] ) . '">'
				: '<article class="tp-card-slide">';

			/*
			 * تصویر کارت، اگر هست — ابعاد صریح و srcset وردپرس؛ جعبۀ هنر در
			 * جریان عادی کارت، ۲۰۰ پیکسل (موبایل ۱۸۰) با `object-fit:cover`،
			 * پس همۀ آن دیده می‌شود. نبود، گرادیان مرجع.  P5-D246 · P5-D266
			 */
			$art = '';

			if ( ! empty( $item['img'] ) && function_exists( 'wp_get_attachment_image' ) ) {
				$art = (string) wp_get_attachment_image( $item['img'], 'medium_large', false, array(
					'class'     => 'tp-card-slide__img',
					'alt'       => '',
					'draggable' => 'false',   /* کشیدن با موس — P5-D277 */
					'loading'  => 'lazy',
					'decoding' => 'async',
					'sizes'    => '(max-width: 720px) 82vw, 300px',
				) );
			}

			/*
			 * شماره در مرجع لاتینِ دورقمی است — «01»، نه «۱».  P5-D215
			 * توضیح (`card-slide__desc`) در هیچ کارت اسلایدری نمی‌آید: بدنه
			 * برچسب نوع، عنوان دوخطی و پاورقی است، با ارتفاع ثابت.  P5-D266
			 */
			$cards .= $open
				. '<div class="tp-card-slide__art' . ( '' !== $art ? ' tp-card-slide__art--img' : '' ) . '" aria-hidden="true">'
				. $art
				. '<span class="tp-card-slide__no">' . esc_html( sprintf( '%02d', $no ) ) . '</span></div>'
				. '<div class="tp-card-slide__body">'
				. ( '' !== $item['kind'] ? '<span class="tp-card-slide__tag">' . esc_html( $item['kind'] ) . '</span>' : '' )
				. '<h3 class="tp-card-slide__title">' . esc_html( $item['name'] ) . '</h3>'
				. topersia_g_card_foot( $item )
				. '</div>'
				. ( $linked ? '</a>' : '</article>' );
		}

		/* «همه … ←» مودالِ فهرست را باز می‌کند (P5-D221)؛ نوار زیر اسلایدر در مرجع **داخل** `.slider-wrap` است. */
		return '<section id="' . esc_attr( $sec_id ) . '" class="tp-section">'
			. topersia_g_section_head( $group, $entity, $list )
			. '<div class="tp-slider-wrap">'
			. '<div class="tp-slider" id="' . esc_attr( $uid ) . '">' . $cards . '</div>'
			. topersia_g_slider_bar( $uid, count( $items ) )
			. '</div></section>';
	}
}

/** برنامه‌ای با بیش از این شمار عضو مودال نمی‌گیرد؛ فقط شمار و پیوند به صفحۀ خودش.  P5-D276 */
if ( ! function_exists( 'topersia_g_programme_list_max' ) ) {
	function topersia_g_programme_list_max() {
		/* صافی فقط برای آزمونِ همین شاخه بی ساختن صد عضو. */
		return function_exists( 'apply_filters' ) ? (int) apply_filters( 'topersia_g_programme_list_max', 100 ) : 100;
	}
}

/**
 * پرس‌وجوهای عضویت برنامه‌ها — Query Builder جت‌انجین (D1–D6).  P5-D281
 *
 * هر پرس‌وجوی پست با تنها یک شرط متا «سوییچ = true» یک قاعدۀ عضویت است:
 * نوع‌های پست و کلید سوییچ. بی جت‌انجین، خالی.
 *
 * @return array هر عضو: id · key · types
 */
if ( ! function_exists( 'topersia_g_programme_queries' ) ) {
	function topersia_g_programme_queries() {

		$out = array();

		if ( ! class_exists( '\Jet_Engine\Query_Builder\Manager' ) ) {
			return $out;
		}

		foreach ( (array) \Jet_Engine\Query_Builder\Manager::instance()->get_queries() as $query ) {

			if ( ! is_object( $query ) || 'posts' !== $query->query_type ) {
				continue;
			}

			$args  = (array) $query->query;
			$meta  = isset( $args['meta_query'] ) ? array_values( (array) $args['meta_query'] ) : array();
			$types = isset( $args['post_type'] ) ? array_values( array_filter( (array) $args['post_type'] ) ) : array();

			if ( 1 !== count( $meta ) || empty( $meta[0]['key'] ) || ! $types
				|| ( isset( $meta[0]['compare'] ) && '=' !== $meta[0]['compare'] )
				|| ! isset( $meta[0]['value'] ) || 'true' !== (string) $meta[0]['value'] ) {
				continue;
			}

			$out[] = array( 'id' => (int) $query->id, 'key' => (string) $meta[0]['key'], 'types' => $types );
		}

		return $out;
	}
}

/**
 * برنامه‌های نشان (رکوردهای D) با اعضای منتشرشدۀ هر کدام.  P5-D281
 *
 * فهرست برنامه‌ها از داده است، نه از کد: هر رکورد منتشرشدۀ `tp_designation`
 * زبان جاری یک برنامه است، به ترتیب P5-D211. عضویت از پرس‌وجوی همان برنامه
 * (`topersia_g_programme_queries()`)؛ پرس‌وجو با کلید سوییچش به `programme_code`
 * رکورد می‌رسد — یکی دیگری را در بر دارد (`creative_city` ⊂
 * `unesco_creative_city` · `global_geopark` ⊃ `geopark`) و فقط تطبیق یکتا
 * پذیرفته است. اعضا از دروازۀ منتشرشده (P5-D247)، به ترتیب P5-D211.
 * برنامۀ بی عضو نمی‌آید.
 *
 * @param bool $flush از نو، نه از حافظۀ همین درخواست (آزمون).
 * @return array هر عضو: id · code · name · url · ids
 */
if ( ! function_exists( 'topersia_g_programmes' ) ) {
	function topersia_g_programmes( $flush = false ) {

		static $memo = array();

		$lang = topersia_g_page_lang();

		if ( isset( $memo[ $lang ] ) && ! $flush ) {
			return $memo[ $lang ];
		}

		$out = array();

		if ( ! function_exists( 'get_posts' ) ) {
			return $memo[ $lang ] = $out;
		}

		$queries = topersia_g_programme_queries();
		$records = get_posts( array( 'post_type' => 'tp_designation', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false ) );

		foreach ( topersia_g_sort_entities( topersia_g_published( array_map( 'absint', (array) $records ) ) ) as $rid ) {

			$code = trim( (string) topersia_g_raw( $rid, 'programme_code' ) );
			$hits = array_values( array_filter( $queries, function ( $q ) use ( $code ) {
				return '' !== $code && ( false !== strpos( $q['key'], $code ) || false !== strpos( $code, $q['key'] ) );
			} ) );

			if ( 1 !== count( $hits ) ) {
				continue;
			}

			$ids = get_posts( array(
				'post_type'        => $hits[0]['types'],
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'meta_query'       => array( array( 'key' => $hits[0]['key'], 'value' => 'true' ) ),
			) );
			$ids = topersia_g_sort_entities( topersia_g_published( array_map( 'absint', (array) $ids ) ) );

			if ( $ids ) {
				$out[] = array( 'id' => $rid, 'code' => $code, 'name' => topersia_g_name( $rid ), 'url' => topersia_g_public_url( $rid ), 'ids' => $ids );
			}
		}

		return $memo[ $lang ] = $out;
	}
}

/** شناسۀ بخش و مودال یک برنامه — خط‌تیره، نه زیرخط (P5-D180). نخستین برنامه لنگرِ نوار بخش‌هاست. */
if ( ! function_exists( 'topersia_g_programme_ids' ) ) {
	function topersia_g_programme_ids( $code, $first ) {
		$slug = trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $code ) ), '-' );
		return array(
			'section' => $first ? 'tp-programmes' : 'tp-prog-' . $slug,
			'list'    => 'tp-list-prog-' . $slug,
			'slider'  => 'tpslprog' . preg_replace( '/[^a-z0-9]+/', '', strtolower( (string) $code ) ),
		);
	}
}

/**
 * [tp_programmes] — یک اسلایدر برای هر برنامۀ نشان، مثل استان‌ها.  P5-D281
 *
 * هر اسلایدر طبق P5-D276: هشت کارت؛ بیشتر ← پیوند و مودالِ فهرست؛ بیش از
 * صد عضو ← فقط شمار و پیوند به صفحۀ خودِ برنامه.
 */
if ( ! function_exists( 'topersia_g_sc_programmes' ) ) {
	function topersia_g_sc_programmes( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$max   = topersia_g_slider_max();
		$out   = '';
		$first = true;

		foreach ( topersia_g_programmes() as $prog ) {

			$items = array();

			foreach ( array_slice( $prog['ids'], 0, $max ) as $one ) {
				$items[] = topersia_g_place_item( topersia_g_view_id( $one ), 'attractions', false );
			}

			$total = count( $prog['ids'] );
			$ids   = topersia_g_programme_ids( $prog['code'], $first );
			$group = array( 'title' => $prog['name'], 'more' => '', 'hub' => '' );
			$list  = '';

			if ( $total > topersia_g_programme_list_max() && '' !== $prog['url'] ) {
				/* translators: %s: شمار اعضای برنامه */
				$group['more'] = sprintf( __( 'همه %s مورد ←', 'topersia' ), topersia_g_fa( $total ) );
				$group['hub']  = $prog['url'];
			} elseif ( $total > $max ) {
				$group['more'] = __( 'همه موارد ←', 'topersia' );
				$list          = $ids['list'];
			}

			$out  .= topersia_g_slider_section( $ids['section'], $ids['slider'], $group, $items, '', $list );
			$first = false;
		}

		return $out;
	}
}

/** مودالِ فهرست هر برنامه با بیش از هشت و تا صد عضو.  P5-D276 */
if ( ! function_exists( 'topersia_g_programme_modals' ) ) {
	function topersia_g_programme_modals( $pid ) {

		unset( $pid );

		$out   = '';
		$first = true;

		foreach ( topersia_g_programmes() as $prog ) {

			$total = count( $prog['ids'] );
			$ids   = topersia_g_programme_ids( $prog['code'], $first );
			$first = false;

			if ( $total <= topersia_g_slider_max() || $total > topersia_g_programme_list_max() ) {
				continue;
			}

			$items = array();

			foreach ( $prog['ids'] as $one ) {
				$item    = topersia_g_place_item( topersia_g_view_id( $one ), 'attractions', false );
				$items[] = array( 'name' => $item['name'], 'meta' => topersia_g_list_meta( 'attractions', $item ), 'url' => $item['url'] );
			}

			$out .= topersia_g_list_modal_html( $ids['list'], $prog['name'], array( array( 'head' => '', 'items' => $items ) ) );
		}

		return $out;
	}
}

/**
 * [tp_neighbor_names] — همسایه‌ها از فیلد متنی، کاشی `.neighbors`.  P5-D281
 *
 * فیلدی که ناحیه‌اش در FIELDMAP `neighbors` است — امروز `neighboring_countries`
 * کشور. جداکننده در دادۀ واقعی خط است (CRLF)؛ ویرگول فارسی و لاتین هم. همان
 * کاشیِ «هم‌مرزها» (P5-D244)، بی پیوند — کشور دیگری صفحه ندارد.
 */
if ( ! function_exists( 'topersia_g_sc_neighbor_names' ) ) {
	function topersia_g_sc_neighbor_names( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		foreach ( topersia_g_map() as $name => $field ) {

			if ( 'neighbors' !== $field['zone'] ) {
				continue;
			}

			$list = array_values( array_filter( array_map( 'trim', (array) preg_split( '/\R|[،,]+/u', topersia_g_text( $pid, $name ) ) ), 'strlen' ) );

			if ( ! $list ) {
				continue;
			}

			$body = '';

			foreach ( $list as $one ) {
				$body .= topersia_g_a_open( '' ) . '<b>' . esc_html( $one ) . '</b></a>';
			}

			return topersia_g_section( 'tp-neighbors', $field['label'], '', '<div class="tp-neighbors">' . $body . '</div>' );
		}

		return '';
	}
}

/**
 * سرِ پیوندِ یک مورد رابطه — یا بی‌`href` اگر آن نوع صفحه ندارد.  P5-D210
 *
 * `href=""` یعنی پیوند به خودِ صفحه: خزنده دنبالش می‌رود و کاربر با کلیک
 * به بالای همین صفحه می‌پرد. `<a>` بی‌`href` پیوند نیست، فوکوس نمی‌گیرد، و
 * سبکش همان سبکِ `a` مرجع است.
 */
if ( ! function_exists( 'topersia_g_a_open' ) ) {
	function topersia_g_a_open( $url, $attrs = '' ) {
		return '' !== (string) $url
			? '<a href="' . esc_url( $url ) . '"' . $attrs . '>'
			: '<a' . $attrs . '>';
	}
}

/**
 * جهتِ یک همسایه از این موجودیت — «شمال» تا «شمال‌غرب».  P5-D219
 *
 * رابطۀ ۱۰۲ (هم‌مرزی) هیچ فیلد جهتی ندارد، ولی هر دو سو
 * `geo_coordinates` دارند. سمتِ مرکز همسایه از مرکز این موجودیت، به هشت
 * بخش ۴۵ درجه‌ای. از دو مختصات واقعی مشتق است، نه حدس؛ اگر یکی نبود،
 * برچسب اصلاً نمی‌آید.
 *
 * @return string برچسب فارسی، یا رشتۀ خالی.
 */
if ( ! function_exists( 'topersia_g_bearing_label' ) ) {
	function topersia_g_bearing_label( $from, $to ) {

		$a = array_map( 'trim', explode( ',', (string) $from ) );
		$b = array_map( 'trim', explode( ',', (string) $to ) );

		if ( count( $a ) < 2 || count( $b ) < 2 || ! is_numeric( $a[0] ) || ! is_numeric( $a[1] )
			|| ! is_numeric( $b[0] ) || ! is_numeric( $b[1] ) ) {
			return '';
		}

		$p1 = deg2rad( (float) $a[0] );
		$p2 = deg2rad( (float) $b[0] );
		$dl = deg2rad( (float) $b[1] - (float) $a[1] );

		$y = sin( $dl ) * cos( $p2 );
		$x = cos( $p1 ) * sin( $p2 ) - sin( $p1 ) * cos( $p2 ) * cos( $dl );

		if ( 0.0 === $x && 0.0 === $y ) {
			return '';
		}

		$deg = fmod( rad2deg( atan2( $y, $x ) ) + 360.0, 360.0 );

		$names = array( 'شمال', 'شمال‌شرق', 'شرق', 'جنوب‌شرق', 'جنوب', 'جنوب‌غرب', 'غرب', 'شمال‌غرب' );

		return topersia_g_t( $names[ (int) floor( ( $deg + 22.5 ) / 45.0 ) % 8 ] );
	}
}

/**
 * شهر یا شهرستانِ یک موجودیت — برچسب کوچکِ کاشیِ هم‌نوع.  P5-D244
 *
 * از رابطه‌های `crumbs` خودِ موجودیت، هر کدام جدا (نه زنجیرۀ پله‌به‌پله):
 * نخستین نیای منتشرشده‌ای که شهر یا شهرستان است. روستا شهرستانش را از
 * ۷۶ می‌گیرد. نوع‌های جاذبه در نقشۀ رابطه مدخل ندارند؛ برای آن‌ها همان
 * چهار رابطۀ جاذبه ← شهر. نبود، رشتۀ خالی — هیچ برچسبی.
 */
if ( ! function_exists( 'topersia_g_tile_place' ) ) {
	function topersia_g_tile_place( $post_id ) {

		$group = topersia_g_group( $post_id, 'crumbs' );
		$specs = empty( $group['rels'] ) ? array() : $group['rels'];

		$specs = array_merge( $specs, array(
			array( 'id' => 84, 'dir' => 'parents' ),
			array( 'id' => 86, 'dir' => 'parents' ),
			array( 'id' => 88, 'dir' => 'parents' ),
			array( 'id' => 93, 'dir' => 'parents' ),
		) );

		foreach ( $specs as $spec ) {
			foreach ( topersia_g_rel_ids( $post_id, array( $spec ) ) as $up ) {

				$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $up ) : '';

				if ( 'tp_city' === $type || 'tp_county' === $type ) {
					return topersia_g_name( topersia_g_view_id( $up ) );
				}
			}
		}

		return '';
	}
}

/**
 * شبکۀ کاشیِ ستون کناری — عیناً کاشی `.neighbors` مرجع.  P5-D244
 *
 * سه ستون: برچسب کوچک بالا، نام پررنگ. جاوااسکریپت با ResizeObserver
 * می‌سنجد و اگر در یک کارت حتی یک کاشی سرریز کند (نامی که ناچار است وسط
 * واژه بشکند)، همان کارت دو ستونه می‌شود. رنگ از توکن‌های کاشی (P5-D245).
 *
 * شش کاشی اول دیده می‌شوند و بقیه در DOM با `hidden` — خزنده همه را
 * می‌بیند. «{N} مورد دیگر» دستۀ بعدی را نشان می‌دهد: N کمینۀ شش و
 * باقی‌مانده است، و پس از آخرین دسته دکمه نیست.
 *
 * «مسیرهای گردشگری» همین کاشی است با خطی **زیر** نام (`sub`: ایستگاه‌ها یا
 * شهر) به‌جای برچسب بالا — همان ستون‌ها، همان وسط‌چین.  P5-D269
 *
 * @param array $tiles هر عضو: label · name · sub · url · raw (نامِ ازپیش‌گریزخورده) · attrs (ویژگی‌های <a> از پیش گریزخورده، مثل rel).
 * @return string
 */
if ( ! function_exists( 'topersia_g_tiles' ) ) {
	function topersia_g_tiles( $tiles, $shown = 6 ) {

		$out = '';
		$n   = 0;

		foreach ( $tiles as $tile ) {

			$n ++;

			$label = isset( $tile['label'] ) ? (string) $tile['label'] : '';
			$sub   = isset( $tile['sub'] ) ? (string) $tile['sub'] : '';
			$name  = isset( $tile['name'] ) ? (string) $tile['name'] : '';
			$name  = empty( $tile['raw'] ) ? esc_html( $name ) : $name;

			$out .= topersia_g_a_open( isset( $tile['url'] ) ? $tile['url'] : '',
					' class="tp-tile"' . ( isset( $tile['attrs'] ) ? (string) $tile['attrs'] : '' ) . ( $shown && $n > $shown ? ' hidden data-tp-rest-item' : '' ) )
				. ( '' !== $label ? '<span class="tp-tile__k">' . esc_html( $label ) . '</span>' : '' )
				. '<b>' . $name . '</b>'
				. ( '' !== $sub ? '<span class="tp-tile__sub">' . esc_html( $sub ) . '</span>' : '' )
				. '</a>';
		}

		$rest = $shown ? max( 0, count( $tiles ) - $shown ) : 0;
		$more = '';

		if ( $rest > 0 ) {
			$more = '<button class="tp-more-rest" type="button" data-tp-more data-tp-step="' . esc_attr( $shown ) . '"'
				. ' data-tp-rest="' . esc_attr( $rest ) . '">'
				. '<span data-tp-more-n>' . topersia_g_fa( min( $shown, $rest ) ) . '</span> '
				. esc_html__( 'مورد دیگر', 'topersia' ) . '</button>';
		}

		return '<div class="tp-tiles" data-tp-tiles>' . $out . '</div>' . $more;
	}
}

/**
 * کارت «اطلاعات سفر» — `.practical-grid` مرجع.  P5-D233 · P5-D279 · P5-D282
 *
 * اول موردهای رابطه (سلامت، آداب، ارز، تقویم، ویزا — با ارث‌بری) با پیکان و
 * پیوند؛ سپس فیلدهای ناحیۀ travel در FIELDMAP به شکل «برچسب: مقدار»، بی
 * پیوند: ایستگاه راه‌آهن و منطقۀ آزاد (وضعیت اداری، نه افتخار) و نام کامل و
 * کد فرودگاه (از کارت آمار بیرون آمد).
 *
 * @param int   $pid    پست.
 * @param array $result خروجی `topersia_g_rel_items( $pid, 'practical', 0 )`.
 * @return string HTML، یا خالی.
 */
if ( ! function_exists( 'topersia_g_practical_section' ) ) {
	function topersia_g_practical_section( $pid, $result ) {

		$map  = topersia_g_map();
		$body = '';

		foreach ( $result['items'] as $item ) {
			$body .= topersia_g_a_open( $item['url'] ) . esc_html( $item['name'] )
				. ' <span>→</span></a>';
		}

		foreach ( topersia_g_zone_fields( $pid, 'travel' ) as $name => $raw ) {

			$value = topersia_g_value( $name, $raw );

			if ( '' === trim( wp_strip_all_tags( (string) $value ) ) ) {
				continue;
			}

			$body .= topersia_g_a_open( '' ) . esc_html( topersia_g_t( $map[ $name ]['label'] ) ) . ': ' . $value . '</a>';
		}

		$group = isset( $result['group'] ) ? (array) $result['group'] : array();

		/* نوعِ بی گروه رابطه (جزیره) همان نام بخش را می‌گیرد — یک منبع، P5-D233 */
		return topersia_g_section(
			'tp-practical',
			! empty( $group['title'] ) ? $group['title'] : topersia_g_rel_zones()['practical'][3],
			'',
			'' !== $body ? '<div class="tp-practical-grid">' . $body . '</div>' : ''
		);
	}
}

/** [tp_relgrid rel="..."] — کاشی‌های ستون کناری با دکمۀ ادامه. */
if ( ! function_exists( 'topersia_g_sc_relgrid' ) ) {
	function topersia_g_sc_relgrid( $atts = array() ) {

		$pid = topersia_g_pid( $atts );
		$key = isset( $atts['rel'] ) ? trim( (string) $atts['rel'] ) : '';

		if ( ! $pid || '' === $key ) {
			return '';
		}

		/*
		 * همه در DOM، بقیه با `hidden`.  P5-D219
		 *
		 * مرجع می‌گفت دکمه «ادامه را همین‌جا بار می‌کند، نه اینکه کاربر
		 * از صفحه برود» و در وردپرس یک درخواست REST می‌زند. تا آن نقطه
		 * ساخته نشده، همه‌شان رندر می‌شوند و بقیه پنهان می‌مانند: خزنده
		 * همه را می‌بیند، کاربر شش‌تای اول را، و دکمه بقیه را نشان
		 * می‌دهد بی‌هیچ رفت‌وبرگشتی.
		 */
		$shown  = isset( $atts['limit'] ) ? absint( $atts['limit'] ) : 6;
		$result = topersia_g_rel_items( $pid, $key, 0 );

		/*
		 * «اطلاعات سفر» = رابطه‌ها + فیلدهای ناحیۀ travel (ایستگاه راه‌آهن، منطقۀ
		 * آزاد، فرودگاه) — حتی بی رابطه، مثل جزیرۀ منطقۀ آزاد.  P5-D279 · P5-D282
		 */
		if ( 'practical' === $key ) {
			return topersia_g_practical_section( $pid, $result );
		}

		if ( empty( $result['items'] ) ) {
			return '';
		}

		$group = $result['group'];
		$title = isset( $group['title'] ) ? $group['title'] : '';
		$kick  = isset( $group['kicker'] ) ? $group['kicker'] : '';

		/*
		 * دو کارت مرجع شکل خودشان را دارند، نه شبکۀ عمومی.  P5-D219
		 *
		 *   #practical   .practical-grid — نام و یک پیکان
		 *   #neighbors   .neighbors      — جهت بالای نام
		 *
		 * تا ۴٫۱٫۶ هر دو با همان `.tp-grid` ساخته می‌شدند و آزمون پذیرش
		 * آنها را جفت نمی‌کرد: کلاس‌ها اصلاً یکی نبودند.
		 *
		 * `#routes` از ۴٫۴٫۱ کاشی P5-D244 است (`topersia_g_tiles`)، نه
		 * `.routes` دوستونۀ مرجع: نام پررنگ، زیرش ایستگاه‌ها یا شهر، وسط‌چین
		 * مثل «استان‌های هم‌مرز»؛ ستون‌ها و «N مورد دیگر» همان.  P5-D269
		 */
		if ( 'neighbors' === $key ) {

			$here = topersia_g_text( $pid, 'geo_coordinates' );
			$body = '';

			foreach ( $result['items'] as $item ) {

				$dir = topersia_g_bearing_label( $here, topersia_g_text( $item['id'], 'geo_coordinates' ) );

				$body .= topersia_g_a_open( $item['url'] )
					. ( '' !== $dir ? '<span class="tp-n-dir">' . esc_html( $dir ) . '</span>' : '' )
					. '<b>' . esc_html( $item['name'] ) . '</b></a>';
			}

			return topersia_g_section( 'tp-neighbors', $title, $kick, '<div class="tp-neighbors">' . $body . '</div>' );
		}

		if ( 'routes' === $key ) {

			/*
			 * کاشی مسیر فقط نام است — سطر دوم (شهر یا ایستگاه) برداشته شد.  P5-D343
			 * نام از همان تابعِ نام (`official_name_{زبان}` · `name_{زبان}` اگر فیلدش را دارد، وگرنه عنوان پست)؛
			 * FIELDMAP برای نوع مسیر فیلد نام کوتاه ندارد، پس عنوان پست.
			 */
			$tiles = array();

			foreach ( $result['items'] as $item ) {
				$tiles[] = array( 'name' => $item['name'], 'url' => $item['url'] );
			}

			return topersia_g_section( 'tp-routes', $title, $kick, topersia_g_tiles( $tiles, $shown ), 'tp-rail-card', '', '',
				topersia_g_rail_more( $group, 'routes', count( $tiles ), $shown ) );
		}

		/*
		 * برچسب کوچک کاشی.  P5-D244
		 *   همه هم‌نوع   نام نوع تکرار نمی‌شود؛ جایش شهر یا شهرستان، نبود، هیچ
		 *   نوع‌ها مختلف  نام نوع
		 * برچسبِ ویژه («مرکز استان») همیشه همان خودش.
		 */
		$types = array();

		foreach ( $result['items'] as $item ) {
			$types[ function_exists( 'get_post_type' ) ? (string) get_post_type( $item['id'] ) : '' ] = true;
		}

		$same  = 1 === count( $types );
		$tiles = array();

		foreach ( $result['items'] as $item ) {

			$kind  = (string) $item['kind'];
			$label = ( $same && $kind === topersia_g_kind_label( $item['id'] ) ) ? topersia_g_tile_place( $item['id'] ) : $kind;

			$tiles[] = array( 'label' => $label, 'name' => $item['name'], 'url' => $item['url'] );
		}

		$group = $result['group'];

		return topersia_g_section(
			'tp-' . $key,
			isset( $group['title'] ) ? $group['title'] : '',
			isset( $group['kicker'] ) ? $group['kicker'] : '',
			topersia_g_tiles( $tiles, $shown ),
			'tp-rail-card', '', '',
			topersia_g_rail_more( $group, $key, count( $tiles ), $shown )
		);
	}
}

/** [tp_gallery] — گالری از featured_image_gallery. */
if ( ! function_exists( 'topersia_g_sc_gallery' ) ) {
	/**
	 * فهرست عکس‌های گالری — یک بار خوانده می‌شود، دو جا مصرف.
	 *
	 * موزاییک و مودال هر دو از همین می‌خوانند، پس هیچ‌وقت از هم واگرا
	 * نمی‌شوند و شمارِ «+N تصویر» با شمارِ مودال یکی است.
	 *
	 * @return array هر عضو: id · img · full · label · place
	 */
	function topersia_g_gallery_shots( $raw ) {

		$out = array();

		if ( is_string( $raw ) ) {
			$raw = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		}

		if ( ! is_array( $raw ) ) {
			return $out;
		}

		foreach ( $raw as $attachment ) {

			$attachment = absint( is_array( $attachment ) && isset( $attachment['id'] ) ? $attachment['id'] : $attachment );

			if ( ! $attachment ) {
				continue;
			}

			$img = function_exists( 'wp_get_attachment_image' )
				? wp_get_attachment_image( $attachment, 'large', false, array( 'loading' => 'lazy' ) )
				: '';

			if ( '' === $img ) {
				continue;
			}

			$caption = function_exists( 'wp_get_attachment_caption' )
				? trim( (string) wp_get_attachment_caption( $attachment ) ) : '';

			/* عنوانِ پیوست برچسب است و توضیحش جای مکان — همان دو سطر مرجع. */
			$title = function_exists( 'get_the_title' ) ? trim( (string) get_the_title( $attachment ) ) : '';
			$label = '' !== $title ? $title : $caption;
			$alt   = function_exists( 'get_post_meta' ) ? (string) get_post_meta( $attachment, '_wp_attachment_image_alt', true ) : '';

			/* ابعاد نسخۀ کامل — `<img>` نمای تمام‌صفحه width و height صریح می‌گیرد.  P5-D246 */
			$full = function_exists( 'wp_get_attachment_image_src' ) ? wp_get_attachment_image_src( $attachment, 'full' ) : false;

			$out[] = array(
				'id'    => $attachment,
				'img'   => $img,
				'w'     => is_array( $full ) && ! empty( $full[1] ) ? (int) $full[1] : 0,
				'h'     => is_array( $full ) && ! empty( $full[2] ) ? (int) $full[2] : 0,
				'full'  => function_exists( 'wp_get_attachment_image_url' )
					? (string) wp_get_attachment_image_url( $attachment, 'full' ) : '',
				/* همان نسخۀ ۱۰۲۴ که کاشی بار کرده — نمای تمام‌صفحه فوراً همین را
				   نشان می‌دهد و نسخۀ کامل پس از decode جایش می‌نشیند.  P5-D226 */
				'large' => function_exists( 'wp_get_attachment_image_url' )
					? (string) wp_get_attachment_image_url( $attachment, 'large' ) : '',
				'label' => $label,
				/*
				 * سطر دوم فقط توضیحِ پیوست است — یا هیچ.  ۴٫۲٫۱ بند ۸
				 * وقتی توضیح همان عنوان بود (وردپرس گاهی هر دو را از نام
				 * پرونده پر می‌کند) کاشی «چهل‌ستون · چهل‌ستون» می‌نوشت.
				 */
				'place' => ( '' !== $caption && $caption !== $label ) ? $caption : '',
				/* متن پیوست به زبانی جز زبان صفحه — topersia_g_gallery_localize جایش را می‌گیرد  P5-D278 */
				'foreign' => topersia_g_foreign_text( $label . ' ' . $caption . ' ' . $alt ),
			);
		}

		return $out;
	}

	/**
	 * عکس‌های گالری یک صفحه: گالری خود، سپس عکس‌های تجمیعیِ ازپیش‌ساخته.  P5-D274
	 *
	 * هیچ محاسبه‌ای در بارگذاری نیست — فقط خواندن متای مشتق (P5-D300). عکس
	 * تجمیعی منبعش را دارد (`src_name` · `src_url`): کپشن و نمای تمام‌صفحه
	 * پیوند صفحۀ منبع را نشان می‌دهند. منبعِ منتشرنشده (P5-D247) در همین‌جا
	 * کنار می‌رود، حتی پیش از بازسازی. سقف کل ۲۴؛ گالری خودِ ۲۴ یا بیشتر = بی
	 * تجمیع. پیوست و منبع به زبان صفحه نگاشت می‌شوند (`wpml_object_id`).
	 *
	 * @return array همان شکل `topersia_g_gallery_shots()` + src_name · src_url
	 */
	function topersia_g_gallery_shots_for( $pid ) {

		$shots = topersia_g_gallery_shots( topersia_g_raw( $pid, 'featured_image_gallery' ) );

		if ( ! function_exists( 'topersia_g_gallery_agg_enabled' ) || ! topersia_g_gallery_agg_enabled() ) {
			return topersia_g_gallery_localize( $shots );
		}

		$room = TOPERSIA_GALLERY_MAX - count( $shots );
		$seen = array();

		foreach ( $shots as $shot ) {
			$seen[ $shot['id'] ] = true;
		}

		foreach ( topersia_g_gallery_agg_stored( $pid ) as $row ) {

			if ( $room <= 0 ) {
				break;
			}

			$src = topersia_g_view_id( $row['src'] );

			if ( ! $src || ! topersia_g_published( array( $src ) ) ) {
				continue;
			}

			$att = function_exists( 'apply_filters' ) ? absint( apply_filters( 'wpml_object_id', $row['id'], 'attachment', true ) ) : absint( $row['id'] );

			if ( ! $att || isset( $seen[ $att ] ) ) {
				continue;
			}

			$one = topersia_g_gallery_shots( array( $att ) );

			if ( ! $one ) {
				continue;
			}

			$one[0]['src_name'] = topersia_g_name( $src );
			$one[0]['src_url']  = topersia_g_public_url( $src );
			$seen[ $att ]       = true;
			$shots[]            = $one[0];
			$room--;
		}

		return topersia_g_gallery_localize( $shots );
	}

	/**
	 * متن پیوستِ زبان دیگر نمی‌آید (P5-D278): عکس سر جایش، برچسبش «تصویر n» به
	 * زبان صفحه و همان در alt؛ توضیح نه. نام منبع تجمیعی به زبان صفحه است و می‌ماند.
	 */
	function topersia_g_gallery_localize( $shots ) {

		foreach ( $shots as $n => $shot ) {

			if ( empty( $shot['foreign'] ) ) {
				continue;
			}

			/* translators: %s: شمارۀ عکس در گالری */
			$label = sprintf( __( 'تصویر %s', 'topersia' ), topersia_g_fa( $n + 1 ) );

			$shots[ $n ]['label'] = $label;
			$shots[ $n ]['place'] = '';
			$shots[ $n ]['img']   = preg_replace( '/\salt="[^"]*"/', ' alt="' . esc_attr( $label ) . '"', (string) $shot['img'], 1 );
		}

		return $shots;
	}

	/** سطر دومِ کاشی گالری: توضیح عکس و، برای عکس تجمیعی، نام موجودیت منبع. */
	function topersia_g_gallery_meta( $shot ) {
		$parts = array_filter( array( isset( $shot['place'] ) ? (string) $shot['place'] : '', isset( $shot['src_name'] ) ? (string) $shot['src_name'] : '' ), 'strlen' );
		return implode( ' · ', $parts );
	}

	function topersia_g_sc_gallery( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		/* گالری خود، سپس تجمیعی از فرزندان (P5-D274) — بی گالری خود هم */
		$shots = topersia_g_gallery_shots_for( $pid );

		if ( empty( $shots ) ) {
			return '';
		}

		/*
		 * موزاییک مرجع: پنج کاشی، ۲fr ۱fr ۱fr در دو ردیف، ۴۲۰ پیکسل.
		 * کاشی اول دو ردیف را می‌گیرد و شمارِ «+N تصویر» رویش می‌نشیند.
		 * P5-D219
		 *
		 * کاشی‌ها `<button>` اند نه `<figure>`: کلیک روی هرکدام همان
		 * عکس را در اندازۀ کامل باز می‌کند.
		 */
		$tiles = '';
		$rest  = count( $shots ) - 5;

		/*
		 * گالری فقط عکس است؛ کاشیِ بزرگ هم.  P5-D227 — جایگزین بخش ویدیوی P5-D222
		 * `video_sample` حالا در کارتِ «روایت صوتی و تصویری» ستون کناری است.
		 */
		foreach ( array_slice( $shots, 0, 5 ) as $n => $shot ) {

			$large = 0 === $n;

			$tiles .= '<button class="tp-gallery-main__item'
				. ( $large ? ' tp-gallery-main__item--large' : '' ) . '" type="button"'
				. ' data-tp-shot="' . esc_attr( $shot['id'] ) . '"'
				. ' aria-label="' . esc_attr( $shot['label'] ) . '">'
				. $shot['img']
				. ( $large && $rest > 0
					? '<span class="tp-gallery-main__count">+' . topersia_g_fa( $rest ) . ' '
						. esc_html__( 'تصویر', 'topersia' ) . '</span>'
					: '' )
				. ( '' !== $shot['label']
					? '<span class="tp-gallery-main__label">' . esc_html( $shot['label'] )
						. ( '' !== topersia_g_gallery_meta( $shot )
							? ' <span class="tp-gallery-main__meta">· ' . esc_html( topersia_g_gallery_meta( $shot ) ) . '</span>'
							: '' )
						. '</span>'
					: '' )
				. '</button>';
		}

		/*
		 * سرفصل مرجع: `h2` + `p.lead` + `.more` متنیِ کوچک.  P5-D218
		 */
		return '<section id="tp-gallery" class="tp-section">'
			. '<div class="tp-section-head"><div>'
			. '<h2>' . esc_html__( 'گالری تصاویر', 'topersia' ) . '</h2>'
			. '<p class="tp-lead">' . esc_html( sprintf(
				/* translators: نام موجودیت */
				__( 'تصاویر منتخب از جاذبه‌های %s', 'topersia' ),
				topersia_g_title( $pid )
			) ) . '</p></div>'
			. '<button class="tp-more" type="button" data-tp-gallery-open>'
			. esc_html__( 'همه تصاویر ←', 'topersia' ) . '</button></div>'
			. '<div class="tp-gallery-main">' . $tiles . '</div></section>';
	}
}

/**
 * تولیدکنندۀ واحد ردیف‌های ریپیتر.
 *
 * ردیف بازنشسته هرگز حذف نمی‌شود؛ وضعیتش عوض می‌شود و هسته آن را فیلتر
 * می‌کند. اگر این فیلتر نباشد، یک ردیف بازنشسته هم در صفحه و هم در
 * JSON-LD منتشر می‌شود.  P5-D121 · P5-D145
 */
if ( ! function_exists( 'topersia_g_rows' ) ) {
	function topersia_g_rows( $post_id, $key ) {

		$rows = topersia_g_raw( $post_id, $key );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		if ( function_exists( 'topersia_active_rows' ) ) {
			return topersia_active_rows( $rows );
		}

		/* هسته نیست — همان قطبیت را دستی نگه می‌داریم. */
		$out = array();

		foreach ( $rows as $row ) {

			if ( ! is_array( $row ) ) {
				continue;
			}

			$status = '';

			foreach ( $row as $sub => $value ) {

				if ( '_row_status' === substr( $sub, -11 ) ) {
					$status = $value;
					break;
				}
			}

			if ( 'not_applicable' !== $status ) {
				$out[] = $row;
			}
		}

		return $out;
	}
}

/**
 * نشانیِ پخشِ درون‌جا برای یوتیوب و آپارات — یا خالی.  P5-D222
 *
 * فقط شناسۀ معتبر از نشانی بیرون کشیده می‌شود و نشانیِ جاسازی از نو
 * ساخته می‌شود؛ هیچ نشانیِ خامی از متا به `iframe` نمی‌رسد. یوتیوب از
 * دامنۀ `youtube-nocookie` — بی‌کوکی تا کاربر پخش نکرده.
 */
if ( ! function_exists( 'topersia_g_embed_url' ) ) {
	function topersia_g_embed_url( $url ) {

		$url = trim( (string) $url );

		if ( preg_match( '~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:[^#]*&)?v=|embed/|shorts/|live/)|youtu\.be/|youtube-nocookie\.com/embed/)([A-Za-z0-9_-]{11})(?:[?&#/].*)?$~i', $url, $m ) ) {
			return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
		}

		if ( preg_match( '~^https?://(?:www\.)?aparat\.com/(?:v/|video/video/embed/videohash/)([A-Za-z0-9]+)(?:[/?#].*)?$~i', $url, $m ) ) {
			return 'https://www.aparat.com/video/video/embed/videohash/' . $m[1] . '/vt/frame';
		}

		return '';
	}
}

/** پسوندِ پروندۀ یک نشانی — بی‌رشتۀ پرس‌وجو، کوچک. */
if ( ! function_exists( 'topersia_g_url_ext' ) ) {
	function topersia_g_url_ext( $url ) {
		$path = (string) parse_url( trim( (string) $url ), PHP_URL_PATH );
		return strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) );
	}
}

/**
 * رسانۀ پخش‌شدنیِ یک نشانی: جاسازی (یوتیوب/آپارات) یا پروندۀ ویدیو.
 *
 * @return array kind (embed|video) · src — یا آرایۀ خالی.
 */
if ( ! function_exists( 'topersia_g_video_media' ) ) {
	function topersia_g_video_media( $url ) {

		$embed = topersia_g_embed_url( $url );

		if ( '' !== $embed ) {
			return array( 'kind' => 'embed', 'src' => $embed );
		}

		if ( in_array( topersia_g_url_ext( $url ), array( 'mp4', 'webm', 'ogv', 'm4v', 'mov' ), true ) ) {
			return array( 'kind' => 'video', 'src' => trim( (string) $url ) );
		}

		return array();
	}
}

/** ویژگی‌های `data-` که شاسیِ پخش می‌خواند. */
if ( ! function_exists( 'topersia_g_media_attrs' ) ) {
	function topersia_g_media_attrs( $media, $title ) {
		return ' data-tp-media="' . esc_url( $media['src'] ) . '"'
			. ' data-tp-media-kind="' . esc_attr( $media['kind'] ) . '"'
			. ' data-tp-media-title="' . esc_attr( $title ) . '"';
	}
}

/**
 * [tp_audio] — «روایت صوتی و تصویری».  P5-D227 — جایگزین بخش ویدیوی P5-D222
 *
 * یک فهرست: همۀ ردیف‌های `audio_narration` و سپس `video_sample` (فیلد
 * تک‌نشانیِ متاباکس مشترک — نه عنوان دارد نه مدت). هر ردیف: آیکون نوع ·
 * عنوان · مدت. آیکون از خودِ رسانه است، نه از نام فیلد: پروندۀ صوتی
 * همین‌جا پخش می‌شود و آیکون صدا دارد؛ یوتیوب و آپارات و پروندۀ ویدیو
 * در شاسیِ تمام‌صفحه پخش می‌شوند و آیکون تصویر دارند.
 *
 * شناسۀ بخش همان `tp-audio` مرجع است تا ترتیب موبایلِ مرجع دست نخورد.
 */
if ( ! function_exists( 'topersia_g_sc_audio' ) ) {
	function topersia_g_sc_audio( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$rows = topersia_g_rows( $pid, 'audio_narration' );
		$body = '';

		foreach ( $rows as $row ) {

			$title = isset( $row['audio_title'] ) ? trim( wp_strip_all_tags( (string) $row['audio_title'] ) ) : '';
			$url   = isset( $row['audio_external_url'] ) ? trim( (string) $row['audio_external_url'] ) : '';
			$time  = isset( $row['audio_duration'] ) ? trim( (string) $row['audio_duration'] ) : '';

			if ( '' === $url && ! empty( $row['audio_file'] ) && function_exists( 'wp_get_attachment_url' ) ) {

				$file = is_array( $row['audio_file'] ) && isset( $row['audio_file']['id'] )
					? $row['audio_file']['id']
					: $row['audio_file'];

				$url = (string) wp_get_attachment_url( absint( $file ) );
			}

			if ( '' === $title || '' === $url ) {
				continue;
			}

			/*
			 * پروندۀ صوتی همین‌جا پخش می‌شود، بی‌رفتن از صفحه.  P5-D222
			 * `preload="none"`: تا کاربر نخواهد، یک بایت هم بار نمی‌شود.
			 * پخش‌کننده درونِ `<a>` نامعتبر است، پس این ردیف `div` است با
			 * همان سبکِ ردیفِ مرجع.
			 */
			if ( in_array( topersia_g_url_ext( $url ), array( 'mp3', 'm4a', 'ogg' ), true ) ) {
				$body .= '<div class="tp-audio__item">' . topersia_g_narration_head( 'audio', $title, $time )
					. '<audio controls preload="none" src="' . esc_url( $url ) . '"></audio></div>';
				continue;
			}

			/* یوتیوب، آپارات و پروندۀ ویدیو در شاسیِ تمام‌صفحه — فقط با کلیک.  P5-D222 */
			$body .= topersia_g_narration_link( $url, $title, $time );
		}

		/* ویدیوی نمونه — عنوانی در متا ندارد؛ برچسبش نام موجودیت است، نه متنِ ساختگی. */
		$video = topersia_g_text( $pid, 'video_sample' );

		if ( '' !== $video ) {
			$body .= topersia_g_narration_link( $video, sprintf(
				/* translators: %s نام موجودیت */
				__( 'ویدیوی %s', 'topersia' ),
				topersia_g_title( $pid )
			), '', 'video' );
		}

		if ( '' === $body ) {
			return '';
		}

		return topersia_g_section( 'tp-audio', 'روایت صوتی و تصویری', 'LISTEN & WATCH', '<div class="tp-audio">' . $body . '</div>' );
	}
}

/**
 * سرِ یک ردیف روایت: آیکون نوع · عنوان · مدت — سه ستونِ شبکۀ مرجع.  P5-D227
 *
 * @param string $kind  audio | video
 */
if ( ! function_exists( 'topersia_g_narration_head' ) ) {
	function topersia_g_narration_head( $kind, $title, $time ) {

		return '<span class="tp-audio__kind" data-tp-kind="' . esc_attr( $kind ) . '">'
			. topersia_g_icon( 'video' === $kind ? 'video' : 'audio' )
			. '<span class="tp-vh">' . esc_html( 'video' === $kind ? __( 'ویدیو', 'topersia' ) : __( 'صدا', 'topersia' ) ) . '</span></span>'
			. '<b>' . esc_html( $title ) . '</b>'
			/* مدت از متاست و بی‌گریز چاپ می‌شد — حالا گریخته.  ۴٫۲٫۱ */
			. ( '' !== (string) $time ? '<span>' . esc_html( topersia_g_fa( $time ) ) . '</span>' : '' );
	}
}

/**
 * ردیفِ پیوندی: اگر پخش‌شدنی است، در شاسیِ تمام‌صفحه با آیکون تصویر؛
 * وگرنه پیوند ساده با نوعِ خودِ فیلد — ویدیوی نمونه‌ای که پخش‌شدنی نیست
 * هنوز ویدیو است.
 *
 * @param string $kind نوع فیلد: audio | video
 */
if ( ! function_exists( 'topersia_g_narration_link' ) ) {
	function topersia_g_narration_link( $url, $title, $time, $kind = 'audio' ) {

		$media = topersia_g_video_media( $url );

		return '<a href="' . esc_url( $url ) . '"'
			. ( $media ? topersia_g_media_attrs( $media, $title ) : ' rel="nofollow noopener"' )
			. '>' . topersia_g_narration_head( $media ? 'video' : $kind, $title, $time ) . '</a>';
	}
}

/** [tp_faq] — همان دادۀ پرسش‌ها که در JSON-LD می‌رود. */
if ( ! function_exists( 'topersia_g_sc_faq' ) ) {
	function topersia_g_sc_faq( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$rows = topersia_g_rows( $pid, 'faq_items' );
		$body = '';
		$n    = 0;

		foreach ( $rows as $row ) {

			$question = isset( $row['faq_question'] ) ? trim( wp_strip_all_tags( (string) $row['faq_question'] ) ) : '';
			$answer   = isset( $row['faq_answer'] ) ? trim( wp_strip_all_tags( (string) $row['faq_answer'] ) ) : '';

			if ( '' === $question || '' === $answer ) {
				continue;
			}

			$n ++;

			/* آکوردئون مرجع: `name` مشترک، پس هر بار یکی باز است. پاسخ تراز دوطرفه.  P5-D270 */
			$body .= '<details name="faq-accordion"' . ( 1 === $n ? ' open' : '' ) . '>'
				. '<summary>' . esc_html( $question ) . '</summary>'
				. '<p class="tp-justify">' . esc_html( $answer ) . '</p></details>';
		}

		if ( '' === $body ) {
			return '';
		}

		return '<section id="tp-faq" class="tp-section tp-faq">'
			. '<div class="tp-section-head"><div><h2>'
			. esc_html__( 'پرسش‌های متداول', 'topersia' ) . '</h2></div></div>'
			. $body . '</section>';
	}
}

/**
 * متنِ غنیِ یک فیلد wysiwyg — کامل، بی‌کوتاه‌سازی، با سرفصل‌های خودش.  P5-D230
 *
 *   پاک‌سازی   `wp_kses` با فهرست بسته: پاراگراف، سرفصل، فهرست، تأکید،
 *              نقل‌قول، جدول ساده، پیوند (فقط `href`). هیچ `class` و `id` و
 *              `style` نمی‌ماند: متنی که از جای دیگر چسبانده شده کلاس‌های
 *              بیگانه و شناسه‌های بی‌پیشوند دارد (P5-D180) — متن اصفهان
 *              یازده `h2` با شناسۀ فارسی و کلاس Tailwind داشت
 *   سرفصل‌ها   یک پله پایین: سرفصل خودِ بخش `h2` است، پس `h1` و `h2`
 *              متن `h3` می‌شوند، `h3` ← `h4` و …
 *   پاراگراف   متنی که هیچ بلوکی ندارد با سطر خالی به `<p>` شکسته می‌شود
 *
 * @param mixed $html مقدار خامِ فیلد.
 * @return string HTML پاک، یا خالی اگر متنی نماند.
 */
if ( ! function_exists( 'topersia_g_rich_text' ) ) {
	function topersia_g_rich_text( $html ) {

		if ( is_array( $html ) || is_object( $html ) || ! function_exists( 'wp_kses' ) ) {
			return '';
		}

		$html = trim( (string) $html );

		if ( '' === $html ) {
			return '';
		}

		$bare    = array();
		$allowed = array( 'a' => array( 'href' => true ) );

		foreach ( array( 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'sup', 'sub', 'ul', 'ol', 'li', 'blockquote', 'hr',
			'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'thead', 'tbody', 'tr', 'th', 'td' ) as $tag ) {
			$allowed[ $tag ] = $bare;
		}

		$html = wp_kses( $html, $allowed );

		$html = preg_replace_callback( '#<(/?)h([1-6])\b#i', function ( $m ) {
			return '<' . $m[1] . 'h' . min( 6, max( 3, (int) $m[2] + 1 ) );
		}, $html );

		if ( ! preg_match( '#<(?:p|h[3-6]|ul|ol|blockquote|table)\b#i', $html ) ) {

			$paras = '';

			foreach ( preg_split( '/\n\s*\n/', $html ) as $part ) {
				$part = trim( $part );
				if ( '' !== $part ) {
					$paras .= '<p>' . nl2br( $part, false ) . '</p>';
				}
			}

			$html = $paras;
		}

		return '' === trim( wp_strip_all_tags( $html ) ) ? '' : $html;
	}
}

/**
 * [tp_overview] — «دربارۀ …» (تا ۴٫۲٫۰ «پاسخ مستقیم»).  P5-D219 · P5-D224
 *
 * متن از `overview_text` می‌آید — «متن اصلی صفحه»، فیلد wysiwyg متاباکس
 * مشترک در export — کامل و با سرفصل‌های خودش، نه از `short_summary`.
 * P5-D230. مرجع نامش را `province_overview_content` گذاشته بود؛ نام
 * واقعی در `Jetengine_Skin.json` همین است.
 *
 * پس از آن هر فیلد ناحیۀ `details` که مقدار دارد، هر کدام یک `h3` با
 * برچسب واژه‌نامه و متنش. اگر هیچ‌کدام نبود، بخش نمی‌آید.
 *
 * این فیلدها تا ۴٫۱٫۶ داخل کارت «اطلاعات جغرافیایی» بودند و آن‌جا جایشان
 * نبود: «مرور اقلیم» و «زمینۀ تاریخی» پاراگراف‌اند، و ستون کناری ۳۴۰
 * پیکسل است.
 *
 * `field-note` مرجع منتقل نشد — یادداشت ساخت بود، نه محتوا.
 */
if ( ! function_exists( 'topersia_g_sc_overview' ) ) {
	function topersia_g_sc_overview( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$map  = topersia_g_map();
		$long = topersia_g_rich_text( topersia_g_raw( $pid, 'overview_text' ) );

		foreach ( topersia_g_zone_fields( $pid, 'details' ) as $name => $raw ) {

			$text = trim( wp_strip_all_tags( topersia_g_value( $name, $raw ) ) );

			if ( '' === $text ) {
				continue;
			}

			$long .= '<h3>' . esc_html( topersia_g_t( $map[ $name ]['label'] ) ) . '</h3>'
				. '<p>' . esc_html( $text ) . '</p>';
		}

		if ( '' === $long ) {
			return '';
		}

		/* `.answer__meta` مرجع: چیپ‌های کوتاه از فیلدهای ناحیۀ `card`. */
		$chips = '';

		foreach ( topersia_g_zone_fields( $pid, 'card', array( 'geo_coordinates' ) ) as $name => $raw ) {

			/*
			 * چیپ‌های مرجع متن خالص‌اند — «مساحت: ۱۰۷٬۰۱۸ km²». در صفحۀ غیرفارسی تکۀ فارسیِ
			 * مقدار (دامنۀ «.ایران») با زبان خودش — همان کاشی‌ها؛ فارسی بی‌تغییر.  P5-D278
			 */
			$chips .= '<span>' . esc_html( topersia_g_t( $map[ $name ]['label'] ) ) . ': '
				. topersia_g_lang_runs( trim( wp_strip_all_tags( topersia_g_value( $name, $raw ) ) ) )
				. '</span>';
		}

		$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $pid ) : '';

		/*
		 * «دربارۀ تالاب گاوخونی» — نه «پاسخ مستقیم»، نه «دربارۀ پهنه آبی تالاب گاوخونی».  P5-D224 · P5-D341
		 * نام از همان تابعِ H1 سربرگ (`topersia_g_heading`) می‌آید، پس دو جا هرگز واگرا نمی‌شوند؛
		 * نام نوعی که خودِ نامِ ذخیره‌شده دارد (مثلاً «استان تهران») بریده نمی‌شود.
		 */
		$about = sprintf(
			/* translators: %s: نام موجودیت، مثل «تالاب گاوخونی» — بی نام نوعی که رندر اضافه می‌کرد  P5-D341 */
			__( 'دربارۀ %s', 'topersia' ),
			topersia_g_heading( $pid )
		);

		/* بدنۀ «درباره» تراز دوطرفه است؛ سرفصل‌های درونش نه.  P5-D270 */
		return '<section id="tp-overview" class="tp-section tp-answer">'
			. '<h2>' . esc_html( $about ) . '</h2>'
			. '<div class="tp-answer__divider"></div>'
			. '<article class="tp-longform tp-justify" data-entity-type="' . esc_attr( $type ) . '"'
			. ' itemscope itemtype="https://schema.org/TouristDestination">' . $long . '</article>'
			. ( '' !== $chips ? '<div class="tp-answer__meta">' . $chips . '</div>' : '' )
			. '</section>';
	}
}

/**
 * [tp_ai] — «این مقصد را در یک نگاه بفهمید».  P5-D219 · ۴٫۲٫۱ بند ۱۰
 *
 * خروجی‌ها از افزونۀ `topersia-ai-summarizer` می‌آیند — همان سه نوعی که
 * خودش دارد (`summary` · `keypoints` · `plan`). گزارشِ ۴٫۲٫۰ که گفته بود
 * افزونه این خروجی‌ها را ندارد، غلط بود؛ نسخۀ ۱٫۱٫۰ هر سه را دارد.
 *
 *   خلاصه        خروجیِ افزونه؛ اگر نبود `ai_summary`
 *   نکات کلیدی   خروجیِ افزونه؛ اگر نبود حقایق کارت آمار و جغرافیا
 *   برنامۀ سفر   فقط خروجیِ افزونه — فیلد JetEngine ندارد و ساخته نمی‌شود
 *
 * اگر هیچ خلاصه‌ای نباشد، بخش نمی‌آید.
 *
 * دکمه‌های موتور پیوند بیرونی‌اند، نه دکمۀ جاوااسکریپتی: کاربر از صفحه
 * می‌رود، و `<a>` کلیک میانی و برگۀ تازه را خودش می‌داند.
 */
if ( ! function_exists( 'topersia_g_ai_engines' ) ) {
	function topersia_g_ai_engines() {

		return array(
			'ChatGPT' => 'https://chatgpt.com/?q=%s',
			'Claude'  => 'https://claude.ai/new?q=%s',
			'Gemini'  => 'https://gemini.google.com/app?q=%s',
			'Copilot' => 'https://copilot.microsoft.com/?q=%s',
		);
	}
}

if ( ! function_exists( 'topersia_g_ai_icons' ) ) {
	function topersia_g_ai_icons() {

		$open = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
			. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';

		return array(
			'ChatGPT' => $open . '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-5"/></svg>',
			'Claude'  => $open . '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>',
			'Gemini'  => $open . '<path d="M12 2l2.4 6.6L21 11l-6.6 2.4L12 20l-2.4-6.6L3 11l6.6-2.4z"/></svg>',
			'Copilot' => $open . '<circle cx="12" cy="12" r="9"/><path d="M9 9h.01M15 9h.01M8.5 14c1 1.5 6 1.5 7 0"/></svg>',
		);
	}
}

/**
 * خروجی‌های ذخیره‌شدۀ افزونۀ `topersia-ai-summarizer` برای یک پست.  ۴٫۲٫۱ بند ۱۰ · ۴٫۳٫۰ بند ۴
 *
 * فقط کش — همان مسیری که پنلِ خودِ افزونه می‌خواند:
 *   زبان     `TP_AI_Localize::for_post()`
 *   خواندن   `tpas_get_summary( $id, $type, $lang )` (خلاصه‌ساز ۱٫۳٫۰؛ فقط خواندنی، بی تولید) — پیش از آن `TP_AI_Summary::get_cached()`
 *            ← بایگانیِ جدولِ خودش، بعد متای `_tpas_ai_{type}_{lang}`
 *
 * «تولید آنی» ۴٫۲٫۱ (`internal_text()` در رندر) برداشته شد.  P5-D235
 * مهمان و ربات هیچ تولیدی نمی‌سازند؛ واردشده با کلیک، سمت سرور و با نانس
 * از نقطۀ خودِ خلاصه‌ساز می‌سازد (سقف‌ها در `topersia-g-ai.php`).
 *
 * متن افزونه ساده است با فهرست‌های «-» و «**»ِ پررنگ؛ بلوک ما متن ساده با
 * `white-space:pre-line` نشان می‌دهد، پس «-» به «•» مرجع می‌رود و «**» حذف.
 *
 * @return array summary · keypoints · plan ← متن
 */
if ( ! function_exists( 'topersia_g_ai_outputs' ) ) {
	function topersia_g_ai_outputs( $pid ) {

		$out = array();

		/* API پایدار خلاصه‌ساز ۱٫۳٫۰ — فقط خواندن، بی تولید و بی صف؛ نسخۀ پیشین آن: get_cached */
		$stable = function_exists( 'tpas_get_summary' );

		if ( ! $stable && ( ! class_exists( 'TP_AI_Summary' ) || ! method_exists( 'TP_AI_Summary', 'get_cached' ) ) ) {
			return $out;
		}

		$lang = topersia_g_ai_lang( $pid );

		foreach ( array( 'summary', 'keypoints', 'plan' ) as $type ) {

			$row  = $stable ? tpas_get_summary( $pid, $type, $lang ) : TP_AI_Summary::get_cached( $pid, $type, $lang );
			$text = ( is_array( $row ) && isset( $row['text'] ) && is_scalar( $row['text'] ) ) ? (string) $row['text'] : '';
			$text = topersia_g_ai_plain( $text );

			if ( '' !== $text ) {
				$out[ $type ] = $text;
			}
		}

		return $out;
	}
}

/** زبان خروجی — همان که خلاصه‌ساز برای این پست می‌سازد. */
if ( ! function_exists( 'topersia_g_ai_lang' ) ) {
	function topersia_g_ai_lang( $pid ) {
		return ( class_exists( 'TP_AI_Localize' ) && method_exists( 'TP_AI_Localize', 'for_post' ) )
			? (string) TP_AI_Localize::for_post( $pid ) : 'fa';
	}
}

/** متن ساده: بی تگ، بی «**»، فهرست «-» به «•». */
if ( ! function_exists( 'topersia_g_ai_plain' ) ) {
	function topersia_g_ai_plain( $text ) {
		$text = str_replace( array( '**', '__' ), '', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) );
		return trim( preg_replace( '/^[\t ]*[-*]\s+/m', '• ', $text ) );
	}
}

if ( ! function_exists( 'topersia_g_sc_ai' ) ) {
	function topersia_g_sc_ai( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$logged = function_exists( 'is_user_logged_in' ) && is_user_logged_in();

		/*
		 * فقط خروجیِ ذخیره‌شده، برای هر بازدیدکننده — مهمان و واردشده.  P5-D358
		 * خروجیِ ذخیره‌شدۀ خلاصه‌ساز اول (`tpas_get_summary`؛ در نبودش `TP_AI_Summary::get_cached`)،
		 * وگرنه `ai_summary` دستی؛ هیچ‌کدام نبود ← بلوک نمی‌آید. نه دکمۀ تولید، نه دعوت به ثبت‌نام برای AI، نه درخواست AJAX:
		 * ویژگی‌های تولید (نانس و نشانی AJAX) چاپ نمی‌شوند، پس جاوااسکریپت صفحه چیزی نمی‌فرستد. تولید فقط مدیر، از پیشخوان.
		 */
		$ai      = topersia_g_ai_outputs( $pid );
		$summary = isset( $ai['summary'] ) ? $ai['summary'] : topersia_g_text( $pid, 'ai_summary' );

		if ( '' === $summary ) {
			return '';
		}

		$name = topersia_g_name( $pid );

		/* برچسب‌ها و کلیدهای `data-ai` عیناً مرجع: summary · bullets · plan. مهمان فقط خلاصه را دارد؛ نکات و برنامۀ ذخیره‌شده برای واردشده. */
		$data = array_filter( array(
			'summary' => $summary,
			'bullets' => $logged && isset( $ai['keypoints'] ) ? $ai['keypoints'] : '',
			'plan'    => $logged && isset( $ai['plan'] ) ? $ai['plan'] : '',
		) );

		/* دکمۀ هر نوع فقط وقتی خروجی ذخیره‌شده‌اش هست — دکمۀ بی خروجی بی‌کار می‌ماند */
		$labels = array(
			'summary' => __( 'خلاصه مقصد', 'topersia' ),
			'bullets' => __( 'نکات کلیدی', 'topersia' ),
			'plan'    => __( 'پیشنهاد برنامه سفر', 'topersia' ),
		);
		$types  = array_intersect_key( $labels, $data );

		/* دکمه‌های نوع خروجی */
		$buttons = '';
		$first   = true;

		foreach ( $types as $key => $label ) {

			$buttons .= '<button class="tp-ai__btn' . ( $first ? ' tp-is-active' : '' ) . '"'
				. ' type="button" data-ai="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</button>';

			$first = false;
		}

		$buttons .= '<button class="tp-ai__btn tp-ai__btn--copy" type="button" data-ai="copy">'
			. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
			. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
			. '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15V5a2 2 0 012-2h10"/></svg>'
			. esc_html__( 'کپی خروجی', 'topersia' ) . '</button>';

		$url     = (string) get_permalink( $pid );
		$engines = '';

		if ( $logged ) {

			/*
			 * پرسشی که موتور با آن باز می‌شود: نام موجودیت و نشانی همین صفحه.
			 * بی‌نشانی، موتور نمی‌داند دربارۀ کدام صفحه حرف می‌زنیم.
			 */
			$prompt = sprintf(
				/* translators: 1: نام موجودیت 2: نشانی صفحه */
				esc_html__( 'دربارۀ %1$s بر پایۀ این صفحه توضیح بده: %2$s', 'topersia' ),
				$name,
				$url
			);

			$icons   = topersia_g_ai_icons();
			$engines = '<span class="tp-ai__engines-label">' . esc_html__( 'موتور:', 'topersia' ) . '</span>';

			foreach ( topersia_g_ai_engines() as $engine => $pattern ) {

				$engines .= '<a class="tp-ai__engine" href="'
					. esc_url( sprintf( $pattern, rawurlencode( $prompt ) ) ) . '"'
					. ' target="_blank" rel="nofollow noopener">'
					. $icons[ $engine ] . esc_html( $engine ) . '</a>';
			}

			$engines = '<div class="tp-ai__engines" role="group" aria-label="'
				. esc_attr__( 'ادامه در موتور هوش مصنوعی', 'topersia' ) . '">' . $engines . '</div>';
		}

		$first_label = reset( $types );

		/*
		 * هر جمله یک بار.  P5-D267
		 *   عنوان «این مقصد را در یک نگاه بفهمید» — کیکرِ تکراریِ بالایش رفت
		 *   جملۀ منشأ — فقط پایینی، کوچک، زیر خروجی
		 */
		$body = '<div class="tp-ai">'
			. '<h2>' . esc_html__( 'این مقصد را در یک نگاه بفهمید', 'topersia' ) . '</h2>'
			. '<div class="tp-ai__actions" role="group" aria-label="'
			. esc_attr__( 'خروجی‌های هوش مصنوعی', 'topersia' ) . '">' . $buttons . '</div>'
			. $engines
			. '<div class="tp-ai__out" data-tp-ai-out'
			. " data-tp-ai='" . esc_attr( wp_json_encode( $data ) ) . "'"
			. ' aria-live="polite"><p class="tp-justify">' . esc_html( $summary ) . '</p>'
			. '<div class="tp-ai__out-meta"><span>' . esc_html__( 'نوع خروجی:', 'topersia' )
			. ' <strong data-tp-ai-type>' . esc_html( $first_label ) . '</strong></span></div></div>'
			/* نیمۀ اولِ جملۀ مرجع برای ما هم راست است؛ نیمۀ دوم یادداشت ساخت بود. */
			. '<p class="tp-ai__disclaimer">' . esc_html__( 'این خروجی صرفاً از دادۀ موجود همین صفحه ساخته شده است.', 'topersia' ) . '</p>'
			. '</div>';
		return '<section id="tp-ai" class="tp-section">' . $body . '</section>';
	}
}

/**
 * [tp_news] — آخرین خبرهای این موجودیت.  ۴٫۳٫۰ بند ۶ · CHANGE-PACKAGE-001 بند ۷٫۷
 *
 * عیناً `#news` مرجع: سرفصل، چهار خبرِ دوازده ماه اخیر، و زیرش
 * `.news-more` با «فهرست اخبار {نام}» به صفحۀ خبرهای همین موجودیت.
 * منبع: رابطۀ مستقیم به‌اضافۀ `_tp_news_ancestor` (تجمیع رو به بالا).
 * بی خبر تازه، بخش نمی‌آید — نه جعبۀ خالی، نه «به‌زودی».
 *
 * نوع `tpc_news` فقط روی نسخۀ محلی ساخته شده؛ جایی که نیست، این بخش
 * هیچ‌وقت چیزی نمی‌سازد.
 */
if ( ! function_exists( 'topersia_g_sc_news' ) ) {
	function topersia_g_sc_news( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid || ! function_exists( 'topersia_g_news_query' ) ) {
			return '';
		}

		$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $pid ) : '';

		if ( ! in_array( $type, topersia_g_news_types(), true ) ) {
			return '';
		}

		$q = topersia_g_news_query( $pid, 4, 12 );

		if ( ! $q['ids'] ) {
			return '';
		}

		$rows = '';

		foreach ( $q['ids'] as $one ) {
			$rows .= topersia_g_news_card( $one );
		}

		$kind = topersia_g_kind_label( $pid );

		return '<section id="tp-news" class="tp-section">'
			. '<div class="tp-section-head"><div><h2>'
			. esc_html( '' !== $kind ? sprintf( /* translators: %s: نوع */ __( 'اخبار %s', 'topersia' ), $kind ) : __( 'اخبار', 'topersia' ) )
			. '</h2><p class="tp-lead">' . esc_html__( 'محتوای زمانی با تاریخ صریح و لینک به منبع اصلی.', 'topersia' ) . '</p></div></div>'
			. '<div class="tp-news">' . $rows . '</div>'
			. '<div class="tp-news-more"><a href="' . esc_url( topersia_g_news_list_url( $pid ) ) . '">'
			. esc_html( sprintf( /* translators: %s: نام کامل */ __( 'فهرست اخبار %s', 'topersia' ), topersia_g_title( $pid ) ) )
			. ' <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>'
			. '</a></div></section>';
	}
}

/**
 * [tp_reviews] — امتیاز و نظر بازدیدکنندگان.  P5-D219
 *
 * داده از JetReviews همین پست می‌آید: میانگین، توزیع یک تا پنج، و دو
 * نظر آخر. بی‌نظر، بخش نمی‌آید.
 *
 * JetReviews جدول خودش را دارد و ما فقط می‌خوانیم؛ اگر افزونه نصب نباشد
 * همان `''` برمی‌گردد و بخش ناپدید می‌شود.
 */
if ( ! function_exists( 'topersia_g_reviews_data' ) ) {
	function topersia_g_reviews_data( $post_id ) {

		$rows  = array();
		$names = array();

		/*
		 * جدول‌های JetReviews روی سایت زنده — از تصویر پایگاه داده.  P5-D219
		 *
		 *   {prefix}jet_reviews        post_id · author · date · content ·
		 *                              rating (int) · approved (varchar)
		 *   {prefix}jet_review_guests  guest_id · name
		 *
		 * فقط خواندن. اگر افزونه یا جدولش نبود، هیچ چیز خوانده نمی‌شود و
		 * بخش ناپدید می‌شود — همان قاعدۀ «بی‌داده، بی‌جعبه».
		 */
		global $wpdb;

		if ( isset( $wpdb ) && is_object( $wpdb ) && method_exists( $wpdb, 'get_results' ) && $post_id ) {

			/*
			 * نام جدول بی‌حساسیت به حروف مقایسه می‌شود.  ۴٫۲٫۳ بند ۱
			 * MySQL روی ویندوز (`lower_case_table_names=1`) نام را کوچک
			 * برمی‌گرداند — `jwwsu9v_jet_reviews` برای پیشوند `jwWSU9v_` —
			 * و `===` جدولی را که هست «نیست» می‌دید.
			 */
			$table = $wpdb->prefix . 'jet_reviews';
			$found = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

			if ( '' !== $found && 0 === strcasecmp( $found, $table ) ) {

				$rows = (array) $wpdb->get_results( $wpdb->prepare(
					"SELECT author, date, content, rating, approved FROM {$table} WHERE post_id = %d ORDER BY date DESC",
					absint( $post_id )
				), ARRAY_A );

				/* نام مهمان — فقط برای دو نظر آخر لازم است */
				$guests = $wpdb->prefix . 'jet_review_guests';
				$ids    = array();

				foreach ( array_slice( $rows, 0, 6 ) as $row ) {
					if ( isset( $row['author'] ) && '' !== (string) $row['author'] && ! ctype_digit( (string) $row['author'] ) ) {
						$ids[] = (string) $row['author'];
					}
				}

				if ( $ids && 0 === strcasecmp( (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $guests ) ) ), $guests ) ) {

					$marks = implode( ',', array_fill( 0, count( $ids ), '%s' ) );

					foreach ( (array) $wpdb->get_results( $wpdb->prepare(
						"SELECT guest_id, name FROM {$guests} WHERE guest_id IN ({$marks})",
						$ids
					), ARRAY_A ) as $g ) {
						$names[ (string) $g['guest_id'] ] = (string) $g['name'];
					}
				}
			}
		}

		$data = topersia_g_reviews_from_rows( $rows, $names );

		if ( function_exists( 'apply_filters' ) ) {
			$data = apply_filters( 'topersia_g_reviews_data', $data, $post_id );
		}

		return $data;
	}
}

/**
 * ردیف‌های خامِ JetReviews ← داده‌ٔ بخش نظرها.  P5-D219
 *
 * تابع خالص است — بی‌پایگاه داده آزمون‌پذیر.
 *
 *   تأییدشده   `approved` برابر `1` یا `true`؛ بقیه کنار می‌روند
 *   امتیاز      اگر بیشینۀ `rating` از ۵ بزرگ‌تر است، درصد است و ÷۲۰ می‌شود
 *               (JetReviews امتیاز را درصدی از بیشینۀ فیلد نگه می‌دارد)؛
 *               وگرنه خودش ستاره است
 *   توزیع       هر نظر در نزدیک‌ترین ستارۀ یک تا پنج
 *   دو نظر آخر  تازه‌ترین‌های بامتن؛ نویسنده از کاربر وردپرس یا جدول مهمان
 *
 * @param array $rows  ردیف‌ها به ترتیب تاریخ نزولی.
 * @param array $names نام مهمان‌ها بر پایۀ guest_id.
 * @return array avg · count · dist · last
 */
if ( ! function_exists( 'topersia_g_reviews_from_rows' ) ) {
	function topersia_g_reviews_from_rows( $rows, $names = array() ) {

		$out = array( 'avg' => 0.0, 'count' => 0, 'dist' => array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 ), 'last' => array() );

		$ok = array();

		foreach ( (array) $rows as $row ) {
			$flag = isset( $row['approved'] ) ? strtolower( trim( (string) $row['approved'] ) ) : '';
			if ( '1' === $flag || 'true' === $flag ) {
				$ok[] = $row;
			}
		}

		if ( ! $ok ) {
			return $out;
		}

		$max = 0;
		foreach ( $ok as $row ) {
			$max = max( $max, (int) $row['rating'] );
		}

		$percent = $max > 5;
		$sum     = 0.0;

		foreach ( $ok as $i => $row ) {

			$score = $percent ? ( (int) $row['rating'] ) / 20 : (float) $row['rating'];
			$score = max( 0.0, min( 5.0, $score ) );

			$ok[ $i ]['_score'] = $score;
			$sum               += $score;

			$star = (int) max( 1, min( 5, round( $score ) ) );
			$out['dist'][ $star ] ++;
		}

		$out['count'] = count( $ok );
		$out['avg']   = round( $sum / $out['count'], 1 );

		foreach ( $ok as $row ) {

			$text = trim( wp_strip_all_tags( isset( $row['content'] ) ? (string) $row['content'] : '' ) );

			if ( '' === $text ) {
				continue;
			}

			$author = isset( $row['author'] ) ? (string) $row['author'] : '';
			$name   = '';

			if ( ctype_digit( $author ) && function_exists( 'get_the_author_meta' ) ) {
				$name = (string) get_the_author_meta( 'display_name', (int) $author );
			} elseif ( isset( $names[ $author ] ) ) {
				$name = $names[ $author ];
			}

			$out['last'][] = array( 'author' => $name, 'score' => $row['_score'], 'text' => $text );

			if ( 2 === count( $out['last'] ) ) {
				break;
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'topersia_g_stars' ) ) {
	function topersia_g_stars( $score ) {

		$full = (int) round( (float) $score );
		$full = max( 0, min( 5, $full ) );

		return str_repeat( '★', $full ) . str_repeat( '☆', 5 - $full );
	}
}

/**
 * شناسۀ زبان اصلی WPML — کلید مشترک نظر، ذخیره و دنبال‌کردن.
 *
 * امتیاز یک مکان در همۀ زبان‌ها یکی است، پس نظر به شناسۀ پست زبان
 * پیش‌فرض بسته می‌شود و همین‌جا خوانده می‌شود.  P5-D234
 */
if ( ! function_exists( 'topersia_g_canonical_id' ) ) {
	function topersia_g_canonical_id( $post_id ) {

		$post_id = absint( $post_id );

		if ( ! $post_id || ! function_exists( 'apply_filters' ) ) {
			return $post_id;
		}

		$default = apply_filters( 'wpml_default_language', null );
		$type    = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';

		if ( ! is_string( $default ) || '' === $default || '' === $type ) {
			return $post_id;
		}

		$canonical = apply_filters( 'wpml_object_id', $post_id, $type, true, $default );

		return is_numeric( $canonical ) && (int) $canonical > 0 ? (int) $canonical : $post_id;
	}
}

/** معیارهای نظرِ این نوع — از user-core؛ خالی یعنی این نوع نظر نمی‌گیرد. */
if ( ! function_exists( 'topersia_g_review_criteria' ) ) {
	function topersia_g_review_criteria( $post_id ) {

		$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';

		if ( '' === $type || ! class_exists( 'ToPersia_Review_Form' ) || ! method_exists( 'ToPersia_Review_Form', 'criteria' ) ) {
			return array();
		}

		return (array) ToPersia_Review_Form::criteria( $type );
	}
}

/**
 * فرم نظر برای واردشده، یا دعوت به ورود برای مهمان.  P5-D234
 *
 * «هستۀ نمایش» فقط فرم می‌سازد؛ نوشتن کارِ user-core است
 * (`topersia_submit_review`، نانس خودش، نویسنده همیشه کاربر جاری، همیشه
 * در انتظار تأیید مدیر).
 */
if ( ! function_exists( 'topersia_g_review_form' ) ) {
	function topersia_g_review_form( $pid, $criteria ) {

		$logged = function_exists( 'is_user_logged_in' ) && is_user_logged_in();
		$url    = (string) get_permalink( $pid );

		if ( ! $logged ) {

			$login = class_exists( 'ToPersia_Admin' ) && method_exists( 'ToPersia_Admin', 'account_url' )
				? ToPersia_Admin::account_url()
				: ( function_exists( 'wp_login_url' ) ? wp_login_url() : home_url( '/' ) );

			return '<p class="tp-review-invite">' . esc_html__( 'برای نوشتن نظر', 'topersia' ) . ' '
				. '<a href="' . esc_url( add_query_arg( 'redirect_to', rawurlencode( $url ), $login ) ) . '" data-tp-login="review" rel="nofollow">'
				. esc_html__( 'وارد شوید یا ثبت‌نام کنید', 'topersia' ) . '</a>.</p>';
		}

		$canonical = topersia_g_canonical_id( $pid );
		$user      = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;

		if ( method_exists( 'ToPersia_Review_Form', 'user_has_review' ) && ToPersia_Review_Form::user_has_review( $user, $canonical ) ) {
			return '<p class="tp-review-invite">' . esc_html__( 'نظر شما برای این مکان ثبت شده است؛ پس از تأیید مدیر نمایش داده می‌شود.', 'topersia' ) . '</p>';
		}

		$rows = '';

		foreach ( array_values( $criteria ) as $i => $label ) {

			$pills = '';

			for ( $v = 1; $v <= 5; $v ++ ) {
				$pills .= '<label class="tp-review-form__pill"><input type="radio" name="scores[' . $i . ']" value="' . $v . '" required>'
					. '<span>' . topersia_g_fa( $v ) . '</span></label>';
			}

			$rows .= '<div class="tp-review-form__row" role="radiogroup" aria-label="' . esc_attr( $label ) . '">'
				. '<span class="tp-review-form__label">' . esc_html( $label ) . '</span>'
				. '<span class="tp-review-form__pills">' . $pills . '</span></div>';
		}

		return '<form class="tp-review-form" data-tp-review-form data-tp-post="' . esc_attr( $pid ) . '" novalidate>'
			. '<h3 class="tp-review-form__title">' . esc_html__( 'نظر شما', 'topersia' ) . '</h3>'
			. '<div class="tp-review-form__crit">' . $rows . '</div>'
			. '<label class="tp-review-form__field"><span>' . esc_html__( 'عنوان (اختیاری)', 'topersia' ) . '</span>'
			. '<input type="text" name="title" maxlength="120"></label>'
			. '<label class="tp-review-form__field"><span>' . esc_html__( 'متن نظر', 'topersia' ) . '</span>'
			. '<textarea name="content" rows="4" minlength="10" maxlength="3000" required></textarea></label>'
			. '<button type="submit" class="tp-review-form__send">' . esc_html__( 'ثبت نظر', 'topersia' ) . '</button>'
			. '<p class="tp-review-form__note" role="status" aria-live="polite"></p>'
			. '</form>';
	}
}

if ( ! function_exists( 'topersia_g_sc_reviews' ) ) {
	function topersia_g_sc_reviews( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		/* نوع‌های بی‌نظر (کشور، منطقه، شهرستان، صنایع دستی، خوراک، اطلاعاتی)
		   بخش ندارند. معیار از user-core می‌آید؛ بی آن، فقط دادۀ موجود.  P5-D234 */
		$criteria = topersia_g_review_criteria( $pid );

		/* خواندن به شناسۀ زبان اصلی — نظر فارسی و انگلیسیِ یک مکان یکی است. */
		$data  = topersia_g_reviews_data( topersia_g_canonical_id( $pid ) );
		$count = isset( $data['count'] ) ? (int) $data['count'] : 0;

		if ( ! $count && ! $criteria ) {
			return '';
		}

		$head = '<div class="tp-section-head"><div><h2>'
			. esc_html__( 'نظر بازدیدکنندگان', 'topersia' ) . '</h2></div></div>';

		$form = $criteria ? topersia_g_review_form( $pid, $criteria ) : '';

		/* بی نظر: عنوان + «نخستین نظر را شما بنویسید» + فرم یا دعوت.  P5-D234 */
		if ( ! $count ) {
			return '<section id="tp-reviews" class="tp-section">' . $head
				. '<div class="tp-rate tp-rate--empty"><p class="tp-rate__first">'
				. esc_html__( 'نخستین نظر را شما بنویسید', 'topersia' ) . '</p>' . $form . '</div></section>';
		}

		$bars = '';

		for ( $star = 5; $star >= 1; $star -- ) {

			$n   = isset( $data['dist'][ $star ] ) ? (int) $data['dist'][ $star ] : 0;
			$pct = $count ? round( $n * 100 / $count ) : 0;

			$bars .= '<div class="tp-bar"><span>' . topersia_g_fa( $star ) . '</span>'
				. '<i style="--tp-w:' . esc_attr( $pct ) . '%"></i>'
				. '<span>' . topersia_g_fa( $n ) . '</span></div>';
		}

		$list = '';

		foreach ( array_slice( (array) $data['last'], 0, 2 ) as $one ) {

			$author = isset( $one['author'] ) ? (string) $one['author'] : '';
			$text   = isset( $one['text'] ) ? (string) $one['text'] : '';
			$score  = isset( $one['score'] ) ? (float) $one['score'] : 0.0;

			if ( '' === $text ) {
				continue;
			}

			$list .= '<div class="tp-cmt">'
				. ( '' !== $author ? '<b>' . esc_html( $author ) . '</b>' : '' )
				. '<span class="tp-rate__stars">' . esc_html( topersia_g_stars( $score ) ) . '</span>'
				. '<p>' . esc_html( $text ) . '</p></div>';
		}

		$avg = topersia_g_fa( number_format_i18n( (float) $data['avg'], 1 ) );

		/* AggregateRating در اسکیما نیست: این نوع‌ها مشمول ستارۀ گوگل نیستند.  P5-D234 */
		return '<section id="tp-reviews" class="tp-section">' . $head
			. '<div class="tp-rate"><div class="tp-rate__top"><div>'
			. '<div class="tp-rate__score">' . esc_html( $avg ) . '</div>'
			. '<div class="tp-rate__stars">' . esc_html( topersia_g_stars( $data['avg'] ) ) . '</div>'
			. '<div class="tp-rate__count">' . esc_html( sprintf(
				/* translators: %s: شمار رأی */
				_n( 'از %s رأی', 'از %s رأی', (int) $count, 'topersia' ),
				topersia_g_fa( $count )
			) ) . '</div></div>'
			. '<div class="tp-rate__bars">' . $bars . '</div></div>'
			. ( '' !== $list ? '<div class="tp-rate__list">' . $list . '</div>' : '' )
			. $form
			. '</div></section>';
	}
}

/**
 * [tp_sources] — منابع، راستی‌آزمایی، وضعیت محتوا، مرجع خارجی.
 *
 * ردیف «شناسۀ موجودیت» رفت.  P5-D229
 * `entity_uid` نشانی‌ای است که صفحه ندارد و ۴۰۴ می‌داد؛ جای درستش فقط
 * `@id` گراف JSON-LD است که افزونۀ `topersia-schema` می‌سازد.
 */
if ( ! function_exists( 'topersia_g_sc_sources' ) ) {
	function topersia_g_sc_sources( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$body = '';
		$list = '';

		foreach ( topersia_g_rows( $pid, 'source_references' ) as $row ) {

			$name = isset( $row['source_name'] ) ? trim( wp_strip_all_tags( (string) $row['source_name'] ) ) : '';
			$url  = isset( $row['source_url'] ) ? trim( (string) $row['source_url'] ) : '';

			if ( '' === $name ) {
				continue;
			}

			/* نام منبع در صفحۀ غیرفارسی — تکه‌های فارسی با زبان خودشان؛ رقم به زبان صفحه  P5-D278 */
			$shown = topersia_g_lang_runs( $name );
			$list .= '<li>' . ( '' !== $url
				? '<a href="' . esc_url( $url ) . '" rel="nofollow noopener">' . $shown . '</a>'
				: $shown ) . '</li>';
		}

		if ( '' !== $list ) {
			$body .= '<ol>' . $list . '</ol>';
		}

		$rows   = '';
		$status = topersia_g_text( $pid, 'content_status' );
		$ref    = topersia_g_text( $pid, 'external_reference_uri' );

		/*
		 * وضعیت راستی‌آزمایی هر زبان جداست (WPML 1.13: content_status «ignore»).
		 * ترجمۀ بی مقدار «نیازمند راستی‌آزمایی» است و نه وضعیت اصل فارسی را
		 * می‌گیرد نه تاریخ راستی‌آزمایی‌اش را (last_verified_date هنوز copy است).  P5-D308
		 */
		$own = '' !== $status;

		if ( ! $own && topersia_g_default_id( $pid ) !== (int) $pid ) {
			$status = 'needs_verification';
		}

		/*
		 * تاریخ راستی‌آزمایی زیر مقدار «وضعیت محتوا»، در همان خانه — نشانِ
		 * جداگانۀ «✓ آخرین راستی‌آزمایی: …» رفت. تاریخ نبود، فقط وضعیت.
		 * `<time datetime>` تا تاریخ ماشین‌خوان هم باشد.  P5-D268
		 */
		if ( '' !== $status ) {

			$raw  = $own ? trim( (string) topersia_g_raw( $pid, 'last_verified_date' ) ) : '';
			$when = topersia_g_date_fa( $raw );
			$iso  = preg_match( '/^\d{4}-\d{2}-\d{2}/', $raw ) ? substr( $raw, 0, 10 )
				: ( preg_match( '/^\d{9,11}$/', $raw ) ? gmdate( 'Y-m-d', (int) $raw ) : '' );

			$rows .= '<div><span>' . esc_html__( 'وضعیت محتوا', 'topersia' ) . '</span>'
				. '<b>' . esc_html( topersia_g_glossary_label( '37', $status ) )
				. ( '' !== $when ? '<time class="tp-uid__date" datetime="' . esc_attr( $iso ) . '">' . esc_html( $when ) . '</time>' : '' )
				. '</b></div>';
		}

		if ( '' !== $ref ) {

			/* مرجع نام میزبان را نشان می‌داد («Wikidata»)، نه نشانی کامل را. */
			$host = (string) parse_url( $ref, PHP_URL_HOST );
			$host = preg_replace( '/^www\./', '', $host );

			$rows .= '<div><span>' . esc_html__( 'مرجع خارجی', 'topersia' ) . '</span>'
				. '<a class="tp-lat" dir="ltr" href="' . esc_url( $ref ) . '" rel="nofollow noopener">'
				. esc_html( '' !== $host ? $host : $ref ) . '</a></div>';
		}

		/*
		 * `.tp-uid` در مرجع **خواهرِ** `.sources-rail` است، نه فرزندش — هر
		 * دو مستقیم زیر بدنۀ کارت. داخلش که بنشیند، رنگ کم‌رنگ و اندازۀ
		 * ریزِ فهرست منابع را ارث می‌برد.  P5-D219
		 */
		$out = '';

		if ( '' !== $body ) {
			$out .= '<div class="tp-sources-rail">' . $body . '</div>';
		}

		if ( '' !== $rows ) {
			$out .= '<div class="tp-uid">' . $rows . '</div>';
		}

		return topersia_g_section( 'tp-sources', 'منابع و راستی‌آزمایی', 'PROVENANCE', $out );
	}
}

/* =========================================================================
   ۵ — آهنگساز صفحه
   =========================================================================

   چرا این شورت‌کد وجود دارد:

   قرار اولیه این بود که المنتور اسکلت چیدمان بسازد و هسته محتوا. سه دور
   آزمون زنده نشان داد این مرز جواب نمی‌دهد: کانتینر المنتور CSS خودش را
   با همان ویژگی ما می‌نویسد و بعد از فایل ما بار می‌شود، سربرگ نمی‌تواند
   از جعبه بیرون بزند، و ترتیب موبایل باید از میان دو لایه پوشش رد شود.
   هر دور، یک ترفند ویژگی تازه لازم داشت — و ترفند روی ۱۹۴٬۰۰۰ صفحه
   یعنی بدهی.

   حالا لایۀ نمایش کل بدنه را می‌سازد و المنتور دقیقاً یک ویجت دارد.
   چیزی از دست نمی‌رود، چون از P5-D179 به بعد چیدمان هم در CSS بود و
   المنتور جز یک درخت کانتینر که با ما می‌جنگید چیزی نمی‌داد.  P5-D183
   ========================================================================= */

/**
 * ماتریس «نوع × ناحیه» — تنها جای تفاوت قالب‌ها.  P5-D275 · SPRINT-4.4 ۲ج٫۱
 *
 * چیدمان صفحۀ استان الگوی هر نوع صفحه‌دار است: همان ناحیه‌ها با همان ترتیب
 * (`topersia_g_layout()`). نوع فقط با سطر خودش در این جدول از الگو جدا
 * می‌شود؛ شرطِ نوع‌محور در تولیدکنندۀ ناحیه‌ها نوشته نمی‌شود. ناحیۀ بی‌داده
 * خودش چیزی برنمی‌گرداند، پس جای خالی نمی‌ماند و ترتیب بقیه همان است.
 *
 *   off    شناسۀ ناحیه‌هایی که این نوع نمی‌گیرد، حتی با داده
 *   on     ناحیه‌های اختیاری (`topersia_g_optin_zones()`) که این نوع می‌گیرد
 *   title  'kind' پیش‌فرض (نوع + نام: «استان اصفهان») · 'name' فقط نام کوتاه
 *   stats  خانه‌های کارت آمار سربرگ به ترتیب اولویت؛ چهار نخستینِ دارای
 *          داده می‌آیند (`topersia_g_stat_cell()`)
 *   scope  'area' پهنه‌ای · 'point' نقطه‌ای — جعبۀ مسیریابی فاز ۳ (P5-D253)
 *
 * نظر بازدیدکنندگان این‌جا نیست: منبعش پیکربندی نوع‌به‌نوع JetReviews در
 * user-core است (P5-D234)؛ نوعی که خانواده ندارد بخش نظر نمی‌گیرد. سند همین
 * جدول در HANDOVER، «ماتریس نوع × ناحیه».
 *
 * @return array نوع پست ← سطر
 */
if ( ! function_exists( 'topersia_g_type_matrix' ) ) {
	function topersia_g_type_matrix() {

		$rows = array(

			/* P5-D281 · P5-D282 — تیتر بی نام نوع؛ بی هوا، انیمیشن هوا، مسیریابی و کیفیت هوا */
			'tp_country' => array(
				'title' => 'name',
				'off'   => array( 'tp-weather', 'tp-routing', 'tp-aqi' ),
				'on'    => array( 'tp-programmes' ),
				/* منطقۀ زمانی («+۰۳:۳۰ UTC (IRST)») در ۳۹۰ فایرفاکس دو خط شد — به کارت حقایق، نه آمار */
				'stats' => array( 'population', 'area_km2', 'count:provinces', 'official_language', 'calling_code' ),
			),

			/* P5-D282 — نام فرودگاه به «اطلاعات سفر»؛ خانۀ آزاد تا کیفیت هوای فاز ۳ ارتفاع */
			'tp_city' => array(
				'stats' => array( 'area_km2', 'population', 'elevation_m', 'founded_year' ),
				'scope' => 'point',
			),

			/* G — همان قاعدۀ P5-D215 (مرکز · کد · مساحت · جمعیت)، هر نوع آنچه دارد */
			'tp_region'          => array( 'stats' => array( 'region_classification', 'count:provinces' ) ),
			'tp_province'        => array( 'stats' => array( 'capital', 'province_code', 'area_km2', 'population', 'climate_type' ) ),
			'tp_district'        => array( 'stats' => array( 'neighborhood_type' ), 'scope' => 'point' ),
			'tp_village'         => array( 'stats' => array( 'population', 'elevation_m' ), 'scope' => 'point' ),
			'tp_island'          => array( 'stats' => array( 'island_type', 'area_km2' ) ),
			'tp_mountain'        => array( 'stats' => array( 'elevation_m', 'mountain_type', 'trekking_difficulty' ) ),
			'tp_water_body'      => array( 'stats' => array( 'water_body_type', 'area_km2', 'length_km', 'max_depth_m', 'salinity_type' ) ),
			'tp_desert'          => array( 'stats' => array( 'desert_type', 'area_km2', 'max_temperature_c' ) ),
			'tp_forest_plain'    => array( 'stats' => array( 'area_type', 'forest_type', 'area_km2' ) ),
			'tp_border_crossing' => array( 'stats' => array( 'crossing_type', 'border_country', 'status' ), 'scope' => 'point' ),

			/* A — جاذبه‌ها؛ Spatial Scope از CA-A (A9 و A13 پهنه، بقیه نقطه) */
			'tp_monument'        => array( 'stats' => array( 'classification', 'construction_year', 'suggested_duration', 'restoration_status', 'architectural_style' ), 'scope' => 'point' ),
			'tp_religious_site'  => array( 'stats' => array( 'classification', 'religion', 'suggested_duration', 'photography_policy' ), 'scope' => 'point' ),
			'tp_ancient_site'    => array( 'stats' => array( 'excavation_status', 'architectural_style', 'suggested_duration' ), 'scope' => 'point' ),
			'tp_natural_feature' => array( 'stats' => array( 'classification', 'height_m', 'depth_m', 'length_km', 'area_km2', 'suggested_duration' ), 'scope' => 'point' ),
			'tp_museum'          => array( 'stats' => array( 'classification', 'founded_year', 'suggested_duration' ), 'scope' => 'point' ),
			'tp_protected_area'  => array( 'stats' => array( 'park_type', 'area_km2', 'suggested_duration' ) ),
			'tp_landscape'       => array( 'stats' => array( 'landscape_type', 'total_area_km2', 'suggested_duration' ) ),

			/* C · D · E · P — مکان نیستند: بی جعبۀ مسیریابی */
			'tp_handicraft'       => array( 'stats' => array( 'classification', 'intangible_heritage_year', 'intangible_heritage_status' ), 'scope' => 'none' ),
			'tp_local_food'       => array( 'stats' => array( 'classification_' ), 'scope' => 'none' ),
			'tp_costume'          => array( 'stats' => array( 'classification' ), 'scope' => 'none' ),
			'tp_music'            => array( 'stats' => array( 'classification' ), 'scope' => 'none' ),
			'tp_dance'            => array( 'stats' => array( 'dance_type' ), 'scope' => 'none' ),
			'tp_language'         => array( 'stats' => array( 'language_family', 'script', 'speaker_estimate_iran', 'name_endonym' ), 'scope' => 'none' ),
			'tp_ethnic_group'     => array( 'stats' => array( 'population_estimate' ), 'scope' => 'none' ),
			'tp_ritual'           => array( 'stats' => array( 'classification', 'spatial_scope', 'intangible_heritage_year', 'intangible_heritage_status' ), 'scope' => 'none' ),
			'tp_figure'           => array( 'stats' => array( 'classification', 'birth_year_approx', 'death_year_approx' ), 'scope' => 'none' ),
			'tp_historical_era'   => array( 'stats' => array( 'start_year_approx', 'end_year_approx' ), 'scope' => 'none' ),
			'tp_designation'      => array( 'stats' => array( 'programme_start_year' ), 'scope' => 'none' ),
			'tp_route'            => array( 'stats' => array( 'total_distance_km', 'estimated_duration_days', 'difficulty_level', 'classification' ), 'scope' => 'none' ),
			'tp_visa'             => array( 'stats' => array( 'max_total_stay_days', 'passport_validity_months', 'free_zone_stay_days' ), 'scope' => 'none' ),
			'tp_currency'         => array( 'stats' => array( 'currency_iso_code', 'currency_symbol', 'international_card_status', 'redenomination_status' ), 'scope' => 'none' ),
			'tp_health_safety'    => array( 'stats' => array( 'emergency_police', 'emergency_ambulance', 'emergency_fire' ), 'scope' => 'none' ),
			'tp_travel_etiquette' => array( 'stats' => array(), 'scope' => 'none' ),   /* همه wysiwyg — بی خانۀ کوتاه */
			'tp_calendar'         => array( 'stats' => array(), 'scope' => 'none' ),
			'tp_transport'        => array( 'stats' => array(), 'scope' => 'none' ),
			/* محل برگزاری متن آزاد و اغلب بلند است — در آمار نه (P5-D282، یافتۀ ۲ج٫۱ در ۳۹۰) */
			'tp_festival'         => array( 'stats' => array( 'event_frequency' ), 'scope' => 'none' ),
			'tp_sports_event'     => array( 'stats' => array( 'sport_type', 'competition_level', 'event_frequency' ), 'scope' => 'none' ),
			'tp_religious_event'  => array( 'stats' => array( 'calendar_system', 'ceremony_duration_days', 'crowd_scale' ), 'scope' => 'none' ),
			'tp_seasonal_event'   => array( 'stats' => array( 'natural_phenomenon_type', 'season', 'weather_dependency' ), 'scope' => 'none' ),
		);

		if ( function_exists( 'apply_filters' ) ) {
			$rows = apply_filters( 'topersia_g_type_matrix', $rows );
		}

		return $rows;
	}
}

/**
 * سطر ماتریس یک نوع (یا یک پست)، با پیش‌فرض‌ها.
 *
 * @param int|string $post_or_type شناسۀ پست یا نام نوع.
 * @return array off · on · title · stats · scope
 */
if ( ! function_exists( 'topersia_g_type_row' ) ) {
	function topersia_g_type_row( $post_or_type ) {

		$type = is_string( $post_or_type ) && ! is_numeric( $post_or_type )
			? $post_or_type
			: ( function_exists( 'get_post_type' ) ? (string) get_post_type( absint( $post_or_type ) ) : '' );

		$rows = topersia_g_type_matrix();
		$row  = isset( $rows[ $type ] ) ? (array) $rows[ $type ] : array();

		return array_merge(
			array( 'off' => array(), 'on' => array(), 'title' => 'kind', 'stats' => null, 'scope' => 'area' ),
			$row
		);
	}
}

/** ناحیه‌هایی که فقط نوعِ صاحب `on` در ماتریس می‌گیرد. */
if ( ! function_exists( 'topersia_g_optin_zones' ) ) {
	function topersia_g_optin_zones() {
		return array( 'tp-programmes' );
	}
}

/** آیا این ناحیه برای این پست روشن است — طبق ماتریس.  P5-D275 */
if ( ! function_exists( 'topersia_g_zone_on' ) ) {
	function topersia_g_zone_on( $post_id, $zone_id ) {

		$row = topersia_g_type_row( $post_id );

		if ( in_array( $zone_id, (array) $row['off'], true ) ) {
			return false;
		}

		return ! in_array( $zone_id, topersia_g_optin_zones(), true ) || in_array( $zone_id, (array) $row['on'], true );
	}
}

/**
 * نمایش هر گروه رابطه: ستون · تولیدکننده · شناسه · نام در نوار بخش‌ها.
 *
 * ترتیب همین آرایه، ترتیب نشستن بخش‌ها در هر ستون است.
 *
 * `capital` و `crumbs` اینجا نیستند چون بخش مستقل نمی‌سازند — اولی در
 * کارت آمار می‌نشیند و دومی در مسیر راهنما.
 */
if ( ! function_exists( 'topersia_g_rel_zones' ) ) {
	function topersia_g_rel_zones() {

		$zones = array(
			/* `#places` مرجع: مرکز استان + شهرستان‌ها در یک اسلایدر. */
			/*
			 * شناسۀ هر ناحیه `tp-` + شناسۀ مرجع است، بی‌استثنا.  P5-D219
			 * قاعدۀ نگاشت مکانیکی است، پس `#places` مرجع `#tp-places`
			 * می‌شود و بلوک `order` موبایلِ مرجع بدون دست‌کاری کار می‌کند.
			 */
			'attractions' => array( 'main', 'topersia_g_sc_slider',  'tp-attractions', 'جاذبه‌ها' ),
			'places'      => array( 'main', 'topersia_g_sc_slider',  'tp-places',      'شهرها' ),   /* P5-D220 */
			'districts'   => array( 'main', 'topersia_g_sc_slider',  'tp-districts',   'محله‌ها' ),
			'provinces'   => array( 'main', 'topersia_g_sc_slider',  'tp-provinces',   'استان‌ها' ),
			'culture'     => array( 'main', 'topersia_g_sc_culture', 'tp-culture',     'فرهنگ و غذا' ),

			/* روستاها در ستون کناری‌اند، میان «باستانی» و «اطلاعات سفر» — پس
			   هم‌شکل همسایه‌هایشان شبکۀ دوستونی‌اند، نه اسلایدر. */
			'villages'   => array( 'rail', 'topersia_g_sc_relgrid', 'tp-villages',   'روستاها' ),
			'nature'     => array( 'rail', 'topersia_g_sc_relgrid', 'tp-nature',     'طبیعت' ),
			'ancient'    => array( 'rail', 'topersia_g_sc_relgrid', 'tp-ancient',    'باستانی' ),
			'practical'  => array( 'rail', 'topersia_g_sc_relgrid', 'tp-practical',  'اطلاعات سفر' ),
			'border'     => array( 'rail', 'topersia_g_sc_relgrid', 'tp-border',     'گذرهای مرزی' ),
			'subranges'  => array( 'rail', 'topersia_g_sc_relgrid', 'tp-subranges',  'زیرشاخه‌ها' ),
			/* «مرکزیت» باکس جدا نیست؛ فقط کاشی درون «اطلاعات جغرافیایی» (`topersia_g_geo_center_tiles`).  P5-D348 */
			'neighbors'  => array( 'rail', 'topersia_g_sc_relgrid', 'tp-neighbors',  'هم‌مرزها' ),
			'routes'     => array( 'rail', 'topersia_g_sc_relgrid', 'tp-routes',     'مسیرها' ),
		);

		if ( function_exists( 'apply_filters' ) ) {
			$zones = apply_filters( 'topersia_g_rel_zones', $zones );
		}

		return $zones;
	}
}

/**
 * ورودی‌های ستون کناری برای `topersia_g_layout` — از ثبت‌نامۀ پنل چپ.  P5-D347
 *
 * @return array<int,array|string>
 */
if ( ! function_exists( 'topersia_g_panel_entries' ) ) {
	function topersia_g_panel_entries() {

		$tiles = array(
			'neighbors',
			array( 'fn' => 'topersia_g_sc_neighbor_names', 'id' => 'tp-neighbors', 'nav' => '' ),   /* کشورهای همسایه از فیلد متنی — جای «هم‌مرزها»؛ هیچ نوعی هر دو را ندارد.  P5-D281 */
			array( 'fn' => 'topersia_g_sc_badges', 'id' => 'tp-badges', 'nav' => '' ),
			'nature',
			'ancient',
			'villages',
			'practical',
			'routes',
			'@rest',
		);

		$out = array();

		foreach ( topersia_g_panel_registry() as $row ) {

			if ( TOPERSIA_G_PANEL_TILES === $row['id'] ) {
				$out = array_merge( $out, $tiles );
				continue;
			}

			/* دکمه‌ها شناسۀ ناحیه ندارند (نوار بخش‌ها و ماتریس نوع‌ها آن را نمی‌شناسند) */
			$out[] = array( 'fn' => $row['fn'], 'id' => 'tp-actions' === $row['id'] ? '' : $row['id'], 'nav' => $row['nav'] );
		}

		return $out;
	}
}
/**
 * فهرست نواحی، به همان ترتیبی که در صفحه می‌نشینند.
 *
 * بخش‌های رابطه‌ای از نقشۀ همین نوع پست ساخته می‌شوند، نه از فهرست ثابت.
 * قالب دستۀ G یکی است ولی سیزده نوع پست ساختار رابطۀ متفاوتی دارند؛ فهرست
 * ثابتِ استان‌شکل یعنی شهر و روستا و کوه بخش‌های خودشان را هرگز نگیرند.
 */
if ( ! function_exists( 'topersia_g_layout' ) ) {
	function topersia_g_layout( $post_id = 0 ) {

		$type = ( $post_id && function_exists( 'get_post_type' ) ) ? get_post_type( $post_id ) : '';
		$map  = topersia_g_relmap();
		$has  = ( $type && isset( $map[ $type ] ) ) ? $map[ $type ] : array();

		$rel = array( 'main' => array(), 'rail' => array() );

		/* «اطلاعات سفر» برای هر نوع — فیلدهای travel بی گروه رابطه هم کارت می‌سازند.  P5-D279 */
		$has['practical'] = true;

		foreach ( topersia_g_rel_zones() as $key => $zone ) {

			if ( empty( $has[ $key ] ) ) {
				continue;
			}

			$rel[ $zone[0] ][ $key ] = array(
				'fn'   => $zone[1],
				'atts' => array( 'rel' => $key ),
				'id'   => $zone[2],
				'nav'  => $zone[3],
			);
		}

		/*
		 * ترتیب دو ستون، صریح و عیناً مرجع.  P5-D219
		 *
		 * هر عضو یا یک ناحیۀ ثابت است یا کلید یک گروه رابطه. پیش از این
		 * ناحیه‌های رابطه‌ای یک‌جا ته صف می‌افتادند و ترتیبشان از جدول
		 * `topersia_g_rel_zones()` می‌آمد، نه از مرجع — برای همین
		 * «فرهنگ» پیش از «اخبار» می‌نشست ولی «جاذبه‌ها» بعد از
		 * «شهرستان‌ها» نه.
		 *
		 * ناحیه‌های رابطه‌ای که مرجع نامشان را نبرده (محله، استان‌ها،
		 * گذر مرزی، …) ته همان ستون می‌آیند، به ترتیب جدول — پس نوع
		 * پستی که ساختار دیگری دارد بی‌صدا بخشش را گم نمی‌کند.
		 */
		$order = array(
			'main' => array(
				array( 'fn' => 'topersia_g_sc_ai',       'id' => 'tp-ai',       'nav' => 'خلاصه هوشمند' ),   /* بلافاصله بالای «درباره …»؛ دسکتاپ و موبایل — P5-D358 */
				array( 'fn' => 'topersia_g_sc_overview', 'id' => 'tp-overview', 'nav' => 'درباره' ),
				array( 'fn' => 'topersia_g_sc_gallery',  'id' => 'tp-gallery',  'nav' => 'گالری' ),
				'attractions',
				'places',
				/* هم‌جایگاهِ `#places`: زیرمجموعۀ سکونتگاهیِ نوع‌های دیگر — استان‌های کشور، محله‌های شهر.  P5-D275 */
				'provinces',
				'districts',
				/* یک اسلایدر برای هر برنامۀ نشان (رکوردهای D) — فقط کشور.  P5-D281 */
				array( 'fn' => 'topersia_g_sc_programmes', 'id' => 'tp-programmes', 'nav' => 'نشان‌ها' ),
				'culture',
				array( 'fn' => 'topersia_g_sc_news',    'id' => 'tp-news',    'nav' => 'اخبار' ),
				array( 'fn' => 'topersia_g_sc_faq',     'id' => 'tp-faq',     'nav' => 'پرسش‌ها' ),
				array( 'fn' => 'topersia_g_sc_reviews', 'id' => 'tp-reviews', 'nav' => 'نظرها' ),
			),
			/*
			 * پنل چپ از ثبت‌نامۀ واحد (`topersia_g_panel_registry`): ترتیب DOM = ترتیب موبایل؛ دسکتاپ فقط با CSS order.
			 * «باکس‌های کاشی هم‌جوار» جای نشانۀ `@tiles` می‌نشینند: هم‌مرزها اول، سپس باقی با ترتیب فعلی‌شان.  P5-D347
			 */
			'rail' => topersia_g_panel_entries(),
		);

		$layout = array( 'main' => array(), 'rail' => array() );

		foreach ( $order as $col => $list ) {

			$named = array();

			foreach ( $list as $entry ) {
				if ( is_string( $entry ) && '@rest' !== $entry ) {
					$named[ $entry ] = true;
				}
			}

			foreach ( $list as $entry ) {

				if ( is_array( $entry ) ) {
					$entry['atts']     = array();
					$layout[ $col ][]  = $entry;
					continue;
				}

				if ( '@rest' === $entry ) {

					/* آنچه نامش در فهرست مرجع نبود (گذر مرزی، زیرشاخه‌ها، …) همین‌جا، پیش از روایت — نه ته ستون */
					foreach ( $rel[ $col ] as $key => $zone ) {
						if ( empty( $named[ $key ] ) ) {
							$named[ $key ]    = true;
							$layout[ $col ][] = $zone;
						}
					}

					continue;
				}

				if ( isset( $rel[ $col ][ $entry ] ) ) {
					$layout[ $col ][] = $rel[ $col ][ $entry ];
				}
			}

			/* آنچه نامش در فهرست مرجع نبود، ته همان ستون. */
			foreach ( $rel[ $col ] as $key => $zone ) {
				if ( empty( $named[ $key ] ) ) {
					$layout[ $col ][] = $zone;
				}
			}

			/* تفاوت نوع‌ها فقط از ماتریس — ترتیب بقیه دست نمی‌خورد.  P5-D275 */
			if ( $post_id ) {
				$layout[ $col ] = array_values( array_filter( $layout[ $col ], function ( $zone ) use ( $post_id ) {
					return '' === $zone['id'] || topersia_g_zone_on( $post_id, $zone['id'] );
				} ) );
			}
		}

		if ( function_exists( 'apply_filters' ) ) {
			$layout = apply_filters( 'topersia_g_layout', $layout, $post_id );
		}

		return $layout;
	}
}

/**
 * یک ناحیه را اجرا می‌کند؛ اگر خطا داد، صفحه بی‌آن ناحیه می‌آید، نه ۵۰۰.
 * ۴٫۲٫۱ بند ۱
 *
 * صفحه از بیست‌وچند ناحیۀ مستقل ساخته می‌شود و پیش از این یک TypeError
 * در هر کدام — مثلاً دادۀ ناجور در یک فیلد — کل درخواست را می‌خواباند.
 * حالا خطا ثبت می‌شود و همان ناحیه نمی‌آید؛ بقیۀ صفحه سالم است.
 *
 * خطا خاموش نمی‌ماند:
 *   ثبت       topersia_log در کانال render — error_log روی این میزبان بسته است
 *   مدیر      یک یادداشت HTML با کلاس خطا، پیام و فایل:خط، ته صفحه — فقط برای
 *             manage_options، چون مسیر فایل نباید به بازدیدکننده برسد
 *
 * خطای مرگبارِ غیرقابل‌گرفتن (پرشدن حافظه یا زمان) از این‌جا رد می‌شود؛
 * آن را فقط debug.log سرور نشان می‌دهد.
 *
 * @param callable $fn      تابع ناحیه.
 * @param array    $args    آرگومان‌ها.
 * @param int      $post_id برای ثبت.
 * @param array    $notes   یادداشت‌های مدیر — با ارجاع.
 * @return string HTML، یا خالی اگر شکست.
 */
if ( ! function_exists( 'topersia_g_safe' ) ) {
	function topersia_g_safe( $fn, $args, $post_id, &$notes ) {

		try {
			return (string) call_user_func_array( $fn, $args );
		} catch ( Throwable $e ) {

			$name  = is_string( $fn ) ? $fn : 'callable';
			$where = basename( $e->getFile() ) . ':' . $e->getLine();
			$type  = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';
			$line  = $name . ' — ' . get_class( $e ) . ': ' . $e->getMessage() . ' @ ' . $where;

			if ( function_exists( 'topersia_log' ) ) {
				topersia_log( $line, 'error', 'render', array( 'post' => $post_id, 'type' => $type ) );
			}

			if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
				/* `--` یادداشت HTML را می‌بندد؛ هیچ پیامی نباید بتواند از آن بیرون بزند. */
				$notes[] = str_replace( '--', '—', $line . ' · ' . $type . ' #' . $post_id );
			}

			return '';
		}
	}
}

/**
 * [tp_page] — کل بدنۀ صفحۀ موجودیت.
 *
 * نوار بخش‌ها از روی نواحی‌ای ساخته می‌شود که واقعاً محتوا داشتند، نه از
 * یک فهرست ثابت. لینک به بخشی که وجود ندارد، بدترین نوع ناوبری است.
 */
if ( ! function_exists( 'topersia_g_sc_page' ) ) {
	function topersia_g_sc_page( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$notes    = array();
		$layout   = topersia_g_layout( $pid );
		$cols     = array( 'main' => '', 'rail' => '' );
		$rendered = array();

		foreach ( $cols as $key => $ignored ) {

			if ( empty( $layout[ $key ] ) ) {
				continue;
			}

			foreach ( $layout[ $key ] as $zone ) {

				if ( ! function_exists( $zone['fn'] ) ) {
					continue;
				}

				$html = topersia_g_safe( $zone['fn'], array( array_merge( $zone['atts'], array( 'id' => $pid ) ) ), $pid, $notes );

				if ( '' === trim( (string) $html ) ) {
					continue;
				}

				$cols[ $key ] .= $html;

				if ( '' !== $zone['id'] ) {
					$rendered[ $zone['id'] ] = array( 'col' => $key, 'nav' => $zone['nav'] );
				}
			}
		}

		/*
		 * نوار بخش‌ها — همان دوازده پیوندِ مرجع با برچسب‌های خودش.  P5-D219
		 *
		 * مرجع کارت‌های ستون کناری را (فصل، جغرافیا، طبیعت، …) در نوار
		 * نمی‌آورد؛ آن‌ها از برگۀ «بخش‌ها»ی موبایل در دسترس‌اند. بخش‌های
		 * ستون اصلی که مرجع نداشت (محله‌ها، استان‌ها) پس از «فرهنگ» می‌آیند.
		 * پیوندی فقط به بخشی که واقعاً رندر شد.  P5-D192
		 */
		$snav_ref = array(
			'tp-ai'          => 'خلاصه هوشمند',
			'tp-overview'    => 'درباره',   /* P5-D224 */
			'tp-gallery'     => 'گالری',
			'tp-attractions' => 'جاذبه‌ها',
			'tp-places'      => 'شهرها',   /* P5-D220 */
			'tp-culture'     => 'فرهنگ و غذا',
			'tp-news'        => 'اخبار',
			'tp-faq'         => 'پرسش‌ها',
			'tp-reviews'     => 'نظرها',
			'tp-map'         => 'نقشه',
			'tp-audio'       => 'روایت‌ها',   /* P5-D227 — نوار و برگۀ بخش‌ها */
			'tp-practical'   => 'اطلاعات سفر',
			'tp-sources'     => 'منابع',
		);

		$nav = array();

		foreach ( $snav_ref as $id => $label ) {

			if ( isset( $rendered[ $id ] ) ) {
				$nav[ $id ] = $label;
			}

			if ( 'tp-culture' === $id ) {
				foreach ( $rendered as $rid => $meta ) {
					if ( 'main' === $meta['col'] && ! isset( $snav_ref[ $rid ] ) && '' !== $meta['nav'] ) {
						$nav[ $rid ] = $meta['nav'];
					}
				}
			}
		}

		$snav = '';

		if ( count( $nav ) > 1 ) {

			$links = '';

			/* هر پیوند آیکون ثابت بخش خودش را دارد، درون‌خط.  P5-D223 */
			foreach ( $nav as $id => $label ) {
				$links .= '<a href="#' . esc_attr( $id ) . '">'
					. topersia_g_icon( topersia_g_section_icon( $id ) ) . esc_html( topersia_g_t( $label ) ) . '</a>';
			}

			$snav = '<nav class="tp-snav" aria-label="' . esc_attr__( 'ناوبری بخش‌های صفحه', 'topersia' ) . '">'
				. '<div class="tp-wrap tp-snav__in">' . $links . '</div></nav>';
		}

		/*
		 * پوستۀ صفحه عیناً مرجع: `main.main > .wrap > .crumb +
		 * .content-grid > .main-col + aside.rail`.  P5-D219
		 *
		 * مسیر راهنما داخل همان `.wrap` است، نه در یک `.wrap` جدا —
		 * وگرنه پرکردنِ `main` دو بار حساب می‌شود.
		 */
		$body = '';

		if ( '' !== $cols['main'] || '' !== $cols['rail'] ) {

			$body = '<main id="tp-main" class="tp-main"><div class="tp-wrap">'
				. topersia_g_safe( 'topersia_g_sc_crumbs', array( array( 'id' => $pid ) ), $pid, $notes )
				. '<div class="tp-content-grid">'
				. '<div class="tp-main-col">' . $cols['main'] . '</div>'
				. '<aside class="tp-rail" aria-label="' . esc_attr__( 'اطلاعات مکمل', 'topersia' ) . '">'
				. $cols['rail'] . '</aside>'
				. '</div></div></main>';
		}

		$hero   = topersia_g_safe( 'topersia_g_sc_hero', array( array( 'id' => $pid ) ), $pid, $notes );
		$stats  = topersia_g_safe( 'topersia_g_sc_stats', array( array( 'id' => $pid ) ), $pid, $notes );
		$mbar   = topersia_g_safe( 'topersia_g_mobilebar', array( $pid, $nav, $rendered ), $pid, $notes );
		$modals = topersia_g_safe( 'topersia_g_modals', array( $pid ), $pid, $notes );

		/*
		 * مودالِ فهرست فقط برای بخشی که واقعاً رندر شد.  P5-D221 · P5-D192
		 * هر اسلایدرِ رابطه (اگر بیش از هشت عضو دارد — P5-D276)، فرهنگ، و هر
		 * برنامۀ نشان.
		 */
		foreach ( array_merge( $layout['main'], $layout['rail'] ) as $zone ) {

			if ( ! isset( $rendered[ $zone['id'] ] ) ) {
				continue;
			}

			if ( in_array( $zone['fn'], array( 'topersia_g_sc_slider', 'topersia_g_sc_culture', 'topersia_g_sc_relgrid' ), true ) ) {
				$modals .= topersia_g_safe( 'topersia_g_list_modal', array( $pid, isset( $zone['atts']['rel'] ) ? $zone['atts']['rel'] : 'culture' ), $pid, $notes );
			} elseif ( 'topersia_g_sc_programmes' === $zone['fn'] ) {
				$modals .= topersia_g_safe( 'topersia_g_programme_modals', array( $pid ), $pid, $notes );
			}
		}

		$note = '';

		foreach ( $notes as $one ) {
			$note .= "\n<!-- tp-render: " . esc_html( $one ) . " -->";
		}

		return '<div class="tp-page">'
			. $hero
			. $stats
			. $snav
			. $body
			. $mbar
			. $modals
			. '</div>'
			. $note;
	}
}

/**
 * مجموعۀ آیکون ناوبری — یکی برای نوار پایین موبایل و نوار بخش‌ها.  P5-D223
 *
 * پنج آیکون اول همان‌های نوار موبایل مرجع‌اند، عیناً. بقیه در همان
 * زبانِ طراحی کشیده شده‌اند — قاب ۲۴، خط ۱٫۷، سرِ گرد، بی‌پُر — تا هر
 * بخش یک آیکون ثابت داشته باشد. دو نسخه نیست: نوار موبایل هم از همین
 * می‌خواند.
 *
 * @param string $key کلید آیکون.
 * @return string SVG، یا خالی اگر کلید ناشناس است.
 */
if ( ! function_exists( 'topersia_g_icon' ) ) {
	function topersia_g_icon( $key ) {

		static $paths = array(
			/* نوار موبایل مرجع */
			'top'     => '<path d="M12 19V5M5 12l7-7 7 7"/>',
			'star'    => '<path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8-5.2-2.7-5.2 2.7 1-5.8L3.5 9.7l5.9-.9z"/>',
			'pin'     => '<path d="M12 21s7-6.4 7-11a7 7 0 1 0-14 0c0 4.6 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
			'save'    => '<path d="M6 3h12v18l-6-4.5L6 21z"/>',
			'list'    => '<path d="M4 6h16M4 12h16M4 18h16"/>',
			/* بخش‌ها — همان قلم */
			'spark'   => '<path d="M12 3l2.1 5.9L20 11l-5.9 2.1L12 19l-2.1-5.9L4 11l5.9-2.1z"/>',
			'info'    => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.6h.01"/>',
			'image'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="M21 16l-5-5L6 19"/>',
			'city'    => '<path d="M3 21h18M5 21V10l5-3v14M10 21V5l9 4v12M13.5 11h2M13.5 15h2"/>',
			'palette' => '<path d="M12 3a9 9 0 1 0 0 18c1.2 0 1.7-1 1.1-1.9-.6-.9-.1-2.1 1.1-2.1H17a4 4 0 0 0 4-4c0-5.5-4-10-9-10z"/><circle cx="7.5" cy="11.5" r="1"/><circle cx="10.5" cy="7.5" r="1"/><circle cx="15.5" cy="8.5" r="1"/>',
			'news'    => '<path d="M4 5h13v13a2 2 0 0 0 2 2H6a2 2 0 0 1-2-2zM17 9h3v9a2 2 0 0 1-2 2M8 9h5M8 13h5M8 17h3"/>',
			'help'    => '<circle cx="12" cy="12" r="9"/><path d="M9.6 9.4a2.5 2.5 0 1 1 3.4 2.4c-.6.3-1 .8-1 1.5v.4M12 17h.01"/>',
			'chat'    => '<path d="M4 5h16v11H9l-5 4z"/>',
			'bag'     => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7"/>',
			'book'    => '<path d="M5 4.5A1.5 1.5 0 0 1 6.5 3H19v15H6.5A1.5 1.5 0 0 0 5 19.5zM5 19.5A1.5 1.5 0 0 0 6.5 21H19v-3"/>',
			'home'    => '<path d="M4 11l8-7 8 7v9H4zM10 20v-5h4v5"/>',
			'leaf'    => '<path d="M5 19C5 11 10 6 19 5c-1 9-6 14-14 14zM5 19l7-7"/>',
			'column'  => '<path d="M4 20h16M6 17h12M7.5 17V9M12 17V9M16.5 17V9M4.5 9h15L12 4z"/>',
			'route'   => '<circle cx="6" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><path d="M8 18h7a3 3 0 0 0 0-6H9a3 3 0 0 1 0-6h7"/>',
			'compass' => '<circle cx="12" cy="12" r="9"/><path d="M15.5 8.5l-2 5-5 2 2-5z"/>',
			'wave'    => '<path d="M5 10v4M9 7v10M13 4v16M17 8v8M21 11v2"/>',
			'globe'   => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/>',
			'cal'     => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
			'cloud'   => '<path d="M6.5 18h10a4 4 0 0 0 .6-7.95A6 6 0 0 0 5.5 11.5 3.3 3.3 0 0 0 6.5 18z"/>',
			'medal'   => '<circle cx="12" cy="9" r="5"/><path d="M8.6 13.4L7 21l5-2.6 5 2.6-1.6-7.6"/>',
			'gate'    => '<path d="M4 21V7l8-4 8 4v14M4 11h16M9 21v-6h6v6"/>',
			'mount'   => '<path d="M3 20l6-10 4 6 2.5-3.5L21 20z"/>',
			/* نوعِ ردیف روایت — صدا و تصویر.  P5-D227 */
			'audio'   => '<path d="M4 15v-2.5a8 8 0 0 1 16 0V15"/><rect x="3.5" y="14" width="4" height="6.5" rx="1.6"/><rect x="16.5" y="14" width="4" height="6.5" rx="1.6"/>',
			'video'   => '<rect x="3" y="5" width="18" height="14" rx="2.2"/><path d="M10 9.3v5.4l4.6-2.7z"/>',
		);

		if ( ! isset( $paths[ $key ] ) ) {
			return '';
		}

		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"'
			. ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
			. $paths[ $key ] . '</svg>';
	}
}

/**
 * آیکونِ ثابتِ هر بخش — یک شناسه، یک آیکون.  P5-D223
 *
 * بخشی که این‌جا نیست `list` می‌گیرد، نه هیچ: نوارِ یکدست بهتر از
 * نواری است که نیمی آیکون دارند.
 */
if ( ! function_exists( 'topersia_g_section_icon' ) ) {
	function topersia_g_section_icon( $id ) {

		$map = array(
			'tp-ai'          => 'spark',
			'tp-overview'    => 'info',
			'tp-gallery'     => 'image',
			'tp-attractions' => 'star',
			'tp-places'      => 'city',
			'tp-districts'   => 'city',
			'tp-provinces'   => 'city',
			'tp-culture'     => 'palette',
			'tp-news'        => 'news',
			'tp-faq'         => 'help',
			'tp-reviews'     => 'chat',
			'tp-map'         => 'pin',
			'tp-practical'   => 'bag',
			'tp-sources'     => 'book',
			'tp-villages'    => 'home',
			'tp-nature'      => 'leaf',
			'tp-ancient'     => 'column',
			'tp-routes'      => 'route',
			'tp-neighbors'   => 'compass',
			'tp-audio'       => 'wave',
			'tp-geo'         => 'globe',
			'tp-season'      => 'cal',
			'tp-weather'     => 'cloud',
			'tp-aqi'         => 'wave',
			'tp-badges'      => 'medal',
			'tp-border'      => 'gate',
			'tp-subranges'   => 'mount',
			'tp-capital-of'  => 'city',
		);

		return isset( $map[ $id ] ) ? $map[ $id ] : 'list';
	}
}

/**
 * نوار پایین موبایل و برگۀ بخش‌ها.
 *
 * نوار افقی دسکتاپ روی موبایل بد کار می‌کند: بیست برچسب در یک خط اسکرولی
 * که باید افقی گشت تا مقصد پیدا شود. جایش چهار میان‌بر ثابت و یک برگۀ
 * شماره‌گذاری‌شده می‌آید.
 *
 * برگه از همان `$nav` تغذیه می‌شود که نوار دسکتاپ می‌سازد، پس هیچ‌وقت به
 * بخشی که رندر نشده لینک نمی‌دهد.  P5-D192
 */
if ( ! function_exists( 'topersia_g_mobilebar' ) ) {
	function topersia_g_mobilebar( $post_id, $nav, $rendered = array() ) {

		if ( ! is_array( $nav ) || count( $nav ) < 2 ) {
			return '';
		}

		/* همان مجموعه‌ای که نوار بخش‌ها می‌خواند.  P5-D223 */
		$svg = 'topersia_g_icon';

		/*
		 * ردیف مرجع: بالا · جاذبه‌ها · نقشه · ذخیره · بخش‌ها.  P5-D219
		 * اگر جاذبه‌ای نبود، دکمۀ دوم به نخستین بخش ستون اصلی می‌رود —
		 * میان‌بری به بخشی که نیست، دکمۀ مرده است.
		 */
		$second = isset( $rendered['tp-attractions'] ) ? 'tp-attractions' : '';

		if ( '' === $second ) {
			foreach ( $nav as $id => $ignored ) {
				if ( 'tp-map' !== $id ) { $second = $id; break; }
			}
		}

		$row = '<button type="button" data-tp-go="top">' . $svg( 'top' )
			. '<span>' . esc_html__( 'بالا', 'topersia' ) . '</span></button>';

		if ( '' !== $second ) {
			$row .= '<button type="button" data-tp-go="' . esc_attr( $second ) . '">' . $svg( 'star' )
				. '<span>' . esc_html( 'tp-attractions' === $second ? __( 'جاذبه‌ها', 'topersia' ) : topersia_g_t( $nav[ $second ] ) ) . '</span></button>';
		}

		if ( isset( $rendered['tp-map'] ) ) {
			$row .= '<button type="button" data-tp-go="tp-map">' . $svg( 'pin' )
				. '<span>' . esc_html__( 'نقشه', 'topersia' ) . '</span></button>';
		}

		$row .= '<button type="button" data-tp-go="save">' . $svg( 'save' )
			. '<span>' . esc_html__( 'ذخیره', 'topersia' ) . '</span></button>'
			. '<button type="button" data-tp-sheet-open>' . $svg( 'list' )
			. '<span>' . esc_html__( 'بخش‌ها', 'topersia' ) . '</span></button>';

		/*
		 * برگۀ «بخش‌ها» — همان چهار گروه مرجع با برچسب‌های خودش.  P5-D219
		 *
		 * بخش‌هایی که مرجع نداشت در گروه ستونشان می‌آیند: ستون اصلی در
		 * «مطالب»، ستون کناری در «پیوندها». گروهی که هیچ عضوِ رندرشده‌ای
		 * ندارد، سرفصلش هم نمی‌آید.
		 */
		$groups = array(
			'مطالب'      => array(
				'tp-ai' => 'خلاصۀ هوشمند', 'tp-overview' => 'درباره', 'tp-gallery' => 'گالری',
				'tp-attractions' => 'جاذبه‌ها', 'tp-places' => 'شهرها', 'tp-culture' => 'فرهنگ و غذا',
				'tp-news' => 'اخبار', 'tp-audio' => 'روایت‌ها',
			),
			'مشخصات'     => array(
				'tp-map' => 'نقشه', 'tp-weather' => 'هوای این روزها', 'tp-season' => 'بهترین زمان سفر',
				'tp-geo' => 'اطلاعات جغرافیایی', 'tp-badges' => 'نشان‌ها',
			),
			'پیوندها'     => array(
				'tp-nature' => 'پهنه‌های طبیعی', 'tp-ancient' => 'محوطه‌های باستانی', 'tp-practical' => 'اطلاعات سفر',
				'tp-neighbors' => 'هم‌مرزها', 'tp-routes' => 'مسیرها',
			),
			'پایان صفحه' => array(
				'tp-faq' => 'پرسش‌های متداول', 'tp-reviews' => 'نظر بازدیدکنندگان', 'tp-sources' => 'منابع',
			),
		);

		$known = array();
		foreach ( $groups as $members ) { $known += $members; }

		foreach ( $rendered as $id => $meta ) {
			if ( isset( $known[ $id ] ) || '' === $meta['nav'] ) { continue; }
			$groups[ 'main' === $meta['col'] ? 'مطالب' : 'پیوندها' ][ $id ] = $meta['nav'];
		}

		$items = '';

		foreach ( $groups as $head => $members ) {

			$li = '';

			/* هر آیتم آیکون ثابت بخش خودش را کنار برچسب دارد — همان مجموعۀ نوار.  P5-D228 */
			foreach ( $members as $id => $label ) {
				if ( isset( $rendered[ $id ] ) ) {
					$li .= '<li><a href="#' . esc_attr( $id ) . '">' . $svg( topersia_g_section_icon( $id ) )
						. '<span>' . esc_html( topersia_g_t( $label ) ) . '</span></a></li>';
				}
			}

			if ( '' !== $li ) {
				$items .= '<li class="tp-sheet__g">' . esc_html( topersia_g_t( $head ) ) . '</li>' . $li;
			}
		}

		return '<nav class="tp-mnav" aria-label="' . esc_attr__( 'ناوبری موبایل', 'topersia' ) . '">'
			. '<div class="tp-mnav__row">' . $row . '</div></nav>'
			. '<div class="tp-sheet" id="tpSheet">'
			. '<div class="tp-sheet__bd" data-tp-close></div>'
			. '<div class="tp-sheet__p" role="dialog" aria-modal="true" aria-label="'
			. esc_attr__( 'بخش‌های صفحه', 'topersia' ) . '">'
			. '<h2>' . esc_html__( 'بخش‌های این صفحه', 'topersia' ) . '</h2>'
			. '<ol>' . $items . '</ol></div></div>';
	}
}

/**
 * [tp_culture] — شبکۀ شش خانوادۀ فرهنگی.
 *
 * گروه `culture` سیزده رابطه دارد و به‌صورت یک اسلایدر تخت، تودۀ بی‌شکلی
 * می‌شود. اینجا به شش خانواده تقسیم می‌شود که هرکدام آیکون و شمار دارد.
 * خانوادۀ هر رابطه در نقشه نشسته (`fam`) و از نوعِ سرِ دیگر مشتق شده.
 *
 * خانوادۀ بی‌نتیجه کارت نمی‌سازد، و اگر هیچ خانواده‌ای پر نشود کل بخش
 * ناپدید می‌شود — همان قاعدۀ همیشگی.  P5-D192
 */
if ( ! function_exists( 'topersia_g_families' ) ) {
	function topersia_g_families() {

		$fam = array(
			'artisanat' => array( 'صنایع دستی', 'ARTISANAT',
				'<path d="M12 2l1.8 5.6H20l-4.7 3.4 1.8 5.6L12 13.2 6.9 16.6 8.7 11 4 7.6h6.2z"/>' ),
			'cuisine'   => array( 'خوراک محلی', 'CUISINE',
				'<path d="M3 11h18M5 11V8a3 3 0 016 0v3M13 11V8a3 3 0 016 0v3M4 11v8M20 11v8"/>' ),
			'music'     => array( 'موسیقی و آیین', 'MUSIC & RITUAL',
				'<path d="M9 18V5l10-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="16" cy="16" r="3"/>' ),
			'festivals' => array( 'جشنواره', 'FESTIVALS',
				'<path d="M4 20l6-14 8 6-14 8z"/><path d="M14 4l1.5 1.5M18 8l1.5 1.5M17 3l.8.8"/>' ),
			'seasonal'  => array( 'رویداد فصلی و مذهبی', 'SEASONAL',
				'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>' ),
			'heritage'  => array( 'زبان و پوشاک', 'HERITAGE',
				'<path d="M12 3l4 2v4a8 8 0 01-4 7 8 8 0 01-4-7V5z"/>' ),
		);

		if ( function_exists( 'apply_filters' ) ) {
			$fam = apply_filters( 'topersia_g_families', $fam );
		}

		return $fam;
	}
}

/**
 * اعضای فرهنگی هر خانواده — یک بار، برای بخش و برای مودالِ فهرستش.
 *
 * @return array کلید خانواده ← شناسه‌ها، به ترتیب `topersia_g_families()`.
 */
if ( ! function_exists( 'topersia_g_culture_ids' ) ) {
	function topersia_g_culture_ids( $pid ) {

		$group = topersia_g_group( $pid, 'culture' );

		if ( empty( $group['rels'] ) ) {
			return array();
		}

		/* رابطه‌ها را به خانواده‌شان تقسیم کن، با حفظ ترتیب نقشه */
		$buckets = array();
		foreach ( $group['rels'] as $spec ) {
			if ( empty( $spec['fam'] ) ) { continue; }
			$buckets[ $spec['fam'] ][] = $spec;
		}

		$out = array();

		foreach ( topersia_g_families() as $key => $meta ) {

			if ( empty( $buckets[ $key ] ) ) {
				continue;
			}

			$ids = topersia_g_rel_ids( $pid, $buckets[ $key ] );

			if ( $ids ) {
				$out[ $key ] = $ids;
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'topersia_g_sc_culture' ) ) {
	function topersia_g_sc_culture( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid ) {
			return '';
		}

		$group = topersia_g_group( $pid, 'culture' );
		$fams  = topersia_g_families();
		$cards = '';

		foreach ( topersia_g_culture_ids( $pid ) as $key => $ids ) {

			$meta = $fams[ $key ];

			$chips = '';
			foreach ( $ids as $rel_id ) {
				$view   = topersia_g_view_id( $rel_id );
				$url    = topersia_g_public_url( $view );
				$chips .= '<li>' . ( '' !== $url
					? '<a href="' . esc_url( $url ) . '">' . esc_html( topersia_g_name( $view ) ) . '</a>'
					: '<span>' . esc_html( topersia_g_name( $view ) ) . '</span>' ) . '</li>';
			}

			$cards .= '<article class="tp-culture-card">'
				. '<span class="tp-culture-card__count">' . topersia_g_fa( count( $ids ) ) . '</span>'
				. '<div class="tp-culture-card__head">'
				. '<span class="tp-culture-card__icon" aria-hidden="true">'
				. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"'
				. ' stroke-linecap="round" stroke-linejoin="round">' . $meta[2] . '</svg></span>'
				. '<div class="tp-culture-card__title">'
				/* کیکر لاتین (ARTISANAT …) رفت؛ در نسخۀ انگلیسی تکرار می‌شد.  P5-D240 */
				. '<h3>' . esc_html( topersia_g_t( $meta[0] ) ) . '</h3>'
				. '</div></div>'
				. '<ul class="tp-culture-card__list">' . $chips . '</ul>'
				. '</article>';
		}

		if ( '' === $cards ) {
			return '';
		}

		/* `#culture` در مرجع بخشِ ستون اصلی است، نه کارت ستون کناری. */
		return '<section id="tp-culture" class="tp-section">'
			. topersia_g_section_head(
				array_merge( $group, array( 'more' => topersia_g_list_more( $group, 'culture' ) ) ),
				topersia_g_title( $pid ),
				'tp-list-culture'
			)
			. '<div class="tp-culture-grid">' . $cards . '</div></section>';
	}
}

/**
 * سطر دوم هر کارت مودالِ فهرست.  ۴٫۳٫۰ بند ۱۰
 *
 * اعضای یک فهرست هم‌نوع‌اند، پس برچسب نوع («شهر» روی هر کارت) چیزی
 * نمی‌گفت. جایش همان که کاربر برای انتخاب لازم دارد:
 *
 *   شهر      جمعیت با رقم فارسی و «نفر»
 *   جاذبه    شهرش
 *   فرهنگ    خانواده‌اش (در شاخۀ فرهنگِ خودِ مودال گذاشته می‌شود)
 *
 * نبود، خالی — نه «—» و نه «نامعلوم».
 */
if ( ! function_exists( 'topersia_g_list_meta' ) ) {
	function topersia_g_list_meta( $key, $item ) {

		if ( 'places' === $key ) {

			$pop = trim( (string) topersia_g_raw( isset( $item['id'] ) ? $item['id'] : 0, 'population' ) );

			if ( '' === $pop || ! preg_match( '/^[0-9۰-۹,٬.\s]+$/u', $pop ) ) {
				return '';
			}

			return topersia_g_fa( topersia_g_sep( preg_replace( '/[^0-9۰-۹]/u', '', $pop ) ) ) . ' ' . __( 'نفر', 'topersia' );
		}

		if ( 'attractions' === $key ) {
			return isset( $item['place'] ) ? (string) $item['place'] : '';
		}

		return '';
	}
}

/**
 * مودالِ فهرست — یک جزء برای «همه شهرها / همه جاذبه‌ها / همه فرهنگ».  P5-D221
 *
 * شاسی همان مودال گالری است (سرِ چسبان با عنوان و شمار و بستن) و رنگ‌هایش
 * همان رنگ‌های ثابتِ شاسیِ مرجع. درونش یک جعبۀ جست‌وجو و کارت‌های فشرده:
 * نام، برچسب نوع، و پیوند — همان پیوندی که کارتِ خودِ بخش دارد؛ نوعی که
 * صفحه ندارد پیوند هم ندارد (P5-D210). فرهنگ به خانواده‌ها گروه می‌شود.
 *
 * پیوندها در HTML سرورند، نه در JSON: خزنده و بی‌جاوااسکریپت هم می‌بینند.
 * جست‌وجو فقط پنهان و پیدا می‌کند؛ چیزی ساخته نمی‌شود.
 *
 * @param int    $post_id پست.
 * @param string $key     places · attractions · culture
 * @return string HTML، یا خالی اگر عضوی نیست.
 */
if ( ! function_exists( 'topersia_g_list_modal' ) ) {
	function topersia_g_list_modal( $post_id, $key ) {

		$group = topersia_g_group( $post_id, $key );

		if ( empty( $group ) ) {
			return '';
		}

		/* هر بلوک: سرفصل (برای فرهنگ) و فهرست کارت‌ها */
		$blocks = array();

		if ( 'culture' === $key ) {

			$fams = topersia_g_families();

			foreach ( topersia_g_culture_ids( $post_id ) as $fam => $ids ) {

				$items = array();

				foreach ( $ids as $one ) {
					$view    = topersia_g_view_id( $one );
					$items[] = array(
						'name' => topersia_g_name( $view ),
						'meta' => topersia_g_t( $fams[ $fam ][0] ),
						'url'  => topersia_g_public_url( $view ),
					);
				}

				$blocks[] = array( 'head' => topersia_g_t( $fams[ $fam ][0] ), 'items' => $items );
			}

		} else {

			$res = topersia_g_rel_items( $post_id, $key, 0 );

			/* هشت یا کمتر در اسلایدر، شش یا کمتر در ستون کناری: همه دیده‌اند — نه پیوند، نه مودال.  P5-D276 · P5-D313 */
			if ( $res['total'] <= topersia_g_list_cap( $key ) ) {
				return '';
			}

			$items = array();
			/* ستون کناری با اعضای ناهم‌نوع: برچسب نوع زیر هر نام؛ اسلایدرها همان رفتار پیشین (جمعیت یا بی برچسب) */
			$mixed = 6 === topersia_g_list_cap( $key )
				&& count( array_unique( array_map( function ( $i ) { return (string) ( isset( $i['kind'] ) ? $i['kind'] : '' ); }, $res['items'] ) ) ) > 1;

			foreach ( $res['items'] as $item ) {
				$meta    = topersia_g_list_meta( $key, $item );
				$meta    = ( '' === $meta && $mixed && ! empty( $item['kind'] ) ) ? (string) $item['kind'] : $meta;
				$items[] = array( 'name' => $item['name'], 'meta' => $meta, 'url' => $item['url'] );
			}

			if ( $items ) {
				$blocks[] = array( 'head' => '', 'items' => $items );
			}
		}

		return topersia_g_list_modal_html( 'tp-list-' . $key, isset( $group['title'] ) ? topersia_g_t( (string) $group['title'] ) : '', $blocks );
	}
}

/**
 * پوستۀ مودالِ فهرست — مشترک گروه‌های رابطه و برنامه‌های نشان.  P5-D221
 *
 * @param string $id     شناسۀ مودال (`tp-list-…`).
 * @param string $title  عنوان ترجمه‌شده.
 * @param array  $blocks هر عضو: head · items (name · meta · url).
 * @return string HTML، یا خالی اگر عضوی نیست.
 */
if ( ! function_exists( 'topersia_g_list_modal_html' ) ) {
	function topersia_g_list_modal_html( $id, $title, $blocks ) {

		$total = 0;
		$body  = '';

		foreach ( $blocks as $block ) {

			$cards = '';

			foreach ( $block['items'] as $item ) {

				if ( '' === (string) $item['name'] ) {
					continue;
				}

				$total ++;

				$inner = '<b>' . esc_html( $item['name'] ) . '</b>'
					. ( '' !== (string) $item['meta'] ? '<span class="tp-list__tag">' . esc_html( $item['meta'] ) . '</span>' : '' );

				/* نامِ تاشده با تابع هسته — پنجره عبارت را با همان جدول قاعده تا می‌کند.  P5-D265 */
				$fold   = function_exists( 'topersia_g_fold' ) ? topersia_g_fold( $item['name'] ) : '';
				$cards .= '<li class="tp-list__item"' . ( '' !== $fold ? ' data-tp-fold="' . esc_attr( $fold ) . '"' : '' ) . '>'
					. ( '' !== (string) $item['url'] && '#' !== (string) $item['url']
						? '<a class="tp-list__card" href="' . esc_url( $item['url'] ) . '">' . $inner . '</a>'
						: '<span class="tp-list__card">' . $inner . '</span>' )
					. '</li>';
			}

			if ( '' === $cards ) {
				continue;
			}

			$body .= '<div class="tp-list__block">'
				. ( '' !== $block['head'] ? '<h4 class="tp-list__fam">' . esc_html( $block['head'] ) . '</h4>' : '' )
				. '<ul class="tp-list__grid">' . $cards . '</ul></div>';
		}

		if ( ! $total ) {
			return '';
		}

		/* translators: %s: شمار اعضا */
		$count = sprintf( _n( '%s مورد', '%s مورد', (int) $total, 'topersia' ), topersia_g_fa( $total ) );
		/* translators: 1: شمار پیداشده، 2: شمار همه */
		$of    = __( '%1$s از %2$s', 'topersia' );

		/* شمار را جاوااسکریپت با جست‌وجو عوض می‌کند — هر دو شکل از همین‌جا، به زبان صفحه  ۴٫۴٫۲ */
		return '<div class="tp-gallery-modal tp-gallery-modal--list" id="' . esc_attr( $id ) . '" role="dialog"'
			. ' aria-modal="true" aria-labelledby="' . esc_attr( $id ) . '-title" data-tp-list data-tp-total="' . esc_attr( $total ) . '">'
			. '<div class="tp-gallery-modal__head">'
			. '<div><h3 class="tp-gallery-modal__title" id="' . esc_attr( $id ) . '-title">' . esc_html( $title )
			. '<small data-tp-list-count data-tp-count-all="' . esc_attr( $count ) . '" data-tp-count-of="' . esc_attr( $of ) . '">'
			. esc_html( $count ) . '</small></h3></div>'
			. '<input class="tp-list__q" type="search" autocomplete="off" enterkeyhint="search"'
			. ' placeholder="' . esc_attr__( 'جست‌وجوی نام…', 'topersia' ) . '"'
			. ' aria-label="' . esc_attr( sprintf( __( 'جست‌وجو در %s', 'topersia' ), $title ) ) . '" data-tp-list-q>'
			. '<button class="tp-gallery-modal__close" type="button" data-tp-close'
			. ' aria-label="' . esc_attr__( 'بستن فهرست', 'topersia' ) . '">&times;</button>'
			. '</div>'
			. '<div class="tp-list__body">' . $body
			. '<p class="tp-list__empty" role="status" hidden>' . esc_html__( 'موردی پیدا نشد', 'topersia' ) . '</p>'
			. '</div></div>';
	}
}

/**
 * مودال نقشه و مودال گالری.
 *
 * هیچ‌کدام دادۀ تازه نمی‌خواهند: نقشه همان مختصاتِ کارت نقشه را بزرگ
 * نشان می‌دهد و گالری همان `featured_image_gallery` را.
 *
 * هر مودال فقط وقتی ساخته می‌شود که ناحیۀ متناظرش محتوا داشته باشد،
 * وگرنه دکمه‌ای که بازش کند روی صفحه نیست.  P5-D192
 */
if ( ! function_exists( 'topersia_g_modals' ) ) {
	function topersia_g_modals( $post_id ) {

		$out    = '';
		$name   = topersia_g_name( $post_id );
		$coords = (string) topersia_g_raw( $post_id, 'geo_coordinates' );
		$arrow  = topersia_g_arrows();

		if ( '' !== (string) $coords ) {

			/* مرجع پرده ندارد: کلیک روی خودِ `.modal` می‌بنددش. */
			$out .= '<div class="tp-modal" id="tpMapModal" role="dialog" aria-modal="true"'
				. ' aria-labelledby="tpMapModalTitle">'
				. '<div class="tp-modal__card">'
				. '<div class="tp-modal__head">'
				. '<h3 id="tpMapModalTitle">' . esc_html( sprintf(
					/* translators: %s نام موجودیت */
					__( 'نقشۀ %s', 'topersia' ), $name ) ) . '</h3>'
				. '<button class="tp-modal__close" type="button" data-tp-close'
				. ' aria-label="' . esc_attr__( 'بستن', 'topersia' ) . '">&times;</button>'
				. '</div>'
				. '<div class="tp-modal__map" role="img" aria-label="'
				. esc_attr( sprintf( __( 'نمای بزرگ نقشۀ %s', 'topersia' ), $name ) ) . '">'
				/* عیناً مرجع: بی‌کلاس (کلاسِ نقشۀ کارت ارتفاع ۲۳۰ و پس‌زمینه
				   می‌آورد) و با همان الگوی شبکۀ ۴۰ پیکسلی. */
				. '<svg viewBox="0 0 800 480" aria-hidden="true">'
				. '<defs><pattern id="tpGridL' . absint( $post_id ) . '" width="40" height="40" patternUnits="userSpaceOnUse">'
				. '<path d="M40 0H0V40" fill="none" stroke="currentColor" stroke-opacity=".18" stroke-width="1"/>'
				. '</pattern></defs>'
				. '<rect width="800" height="480" fill="url(#tpGridL' . absint( $post_id ) . ')"/>'
				. '<path d="M90 130 C 200 80, 340 100, 470 84 C 590 72, 700 130, 720 220'
				. ' C 740 320, 620 400, 500 420 C 380 440, 220 430, 140 390'
				. ' C 70 350, 50 220, 90 130 Z" fill="none" stroke="currentColor"'
				. ' stroke-width="2" stroke-dasharray="6 5" opacity=".6"/></svg>'
				. '<div class="tp-modal__pin"></div>'
				/* نقشۀ تعاملی — فقط با کلیک «باز کردن نقشۀ کامل» بار می‌شود.  P5-D241 */
				. ( '' !== topersia_g_map_attr( $post_id )
					? '<div class="tp-map-live tp-map-live--full" data-tp-map-full data-tp-coords="' . esc_attr( function_exists( 'topersia_geo_attr' ) && '' !== topersia_geo_attr( $coords ) ? topersia_geo_attr( $coords ) : $coords ) . '"' . topersia_g_map_attr( $post_id ) . '></div>'
					: '' )
				. topersia_g_map_style_bar( $post_id )
				. '</div>'
				. '<div class="tp-modal__meta">'
				. esc_html__( 'مختصات مرکز', 'topersia' ) . ': '
				. '<span class="tp-lat" dir="ltr">' . esc_html( $coords ) . '</span>'
				. '</div></div></div>';
		}

		/* همان فهرستی که موزاییک از آن ساخته شد — یک منبع، دو مصرف. */
		$shots = topersia_g_gallery_shots_for( $post_id );

		/*
		 * شاسیِ پخش — همان شاسیِ تمام‌صفحۀ عکس، با صحنۀ خالی.  P5-D222
		 * `iframe` یا `<video>` فقط با کلیک ساخته می‌شود و با بستن برداشته،
		 * پس تا کاربر نخواسته هیچ درخواستی به یوتیوب و آپارات نمی‌رود.
		 * فقط وقتی ساخته می‌شود که چیزی برای پخش هست.  P5-D192
		 *
		 * ویدیو دیگر به گالری بسته نیست: از کارتِ روایت پخش می‌شود، پس صفحۀ
		 * بی‌عکس هم شاسی را می‌گیرد.  P5-D227
		 */
		$playable = (bool) topersia_g_video_media( topersia_g_text( $post_id, 'video_sample' ) );

		foreach ( topersia_g_rows( $post_id, 'audio_narration' ) as $row ) {
			$link = isset( $row['audio_external_url'] ) && is_scalar( $row['audio_external_url'] ) ? (string) $row['audio_external_url'] : '';
			if ( topersia_g_video_media( $link ) ) {
				$playable = true;
			}
		}

		if ( $playable ) {
			$out .= '<div class="tp-gallery-modal tp-gallery-modal--solo tp-gallery-modal--media" id="tpMediaSolo"'
				. ' role="dialog" aria-modal="true" aria-labelledby="tpMediaSoloTitle">'
				. '<div class="tp-gallery-modal__head"><div>'
				. '<h3 class="tp-gallery-modal__title" id="tpMediaSoloTitle" data-tp-media-name></h3></div>'
				. '<button class="tp-gallery-modal__close" type="button" data-tp-close'
				. ' aria-label="' . esc_attr__( 'بستن پخش', 'topersia' ) . '">&times;</button>'
				. '</div>'
				. '<figure class="tp-solo"><div class="tp-solo__stage tp-solo__stage--media" data-tp-media-stage></div></figure>'
				. '</div>';
		}

		if ( empty( $shots ) ) {
			return $out;
		}

		$items = '';
		$srcs  = array();

		foreach ( $shots as $n => $shot ) {

			/* الگوی کاشی پهن در مرجع: نخستین و نهمین. */
			$wide = ( 0 === $n % 8 );

			$items .= '<button class="tp-gallery-modal__item'
				. ( $wide ? ' tp-gallery-modal__item--wide' : '' ) . '" type="button"'
				. ' data-tp-shot="' . esc_attr( $shot['id'] ) . '"'
				. ' aria-label="' . esc_attr( $shot['label'] ) . '">'
				. $shot['img']
				. ( '' !== $shot['label']
					? '<span class="tp-gallery-modal__cap">' . esc_html( $shot['label'] )
						. ( '' !== topersia_g_gallery_meta( $shot ) ? ' <small>' . esc_html( topersia_g_gallery_meta( $shot ) ) . '</small>' : '' )
						. '</span>'
					: '' )
				. '</button>';

			/* t و c زیرِ عکسِ تمام‌صفحه می‌نشینند: عنوان و توضیحِ پیوست.  بند ۸
			   m نسخۀ ۱۰۲۴ کاشی است که فوراً نشان داده می‌شود.  P5-D226 */
			$srcs[] = array(
				'id'  => $shot['id'],
				'src' => $shot['full'],
				'm'   => $shot['large'],
				'alt' => $shot['label'],
				't'   => $shot['label'],
				'c'   => $shot['place'],
				/* عکس تجمیعی: نام و نشانی موجودیت منبع — پیوند در نمای تمام‌صفحه (P5-D274) */
				'n'   => isset( $shot['src_name'] ) ? $shot['src_name'] : '',
				'u'   => isset( $shot['src_url'] ) ? $shot['src_url'] : '',
				/* ابعاد — جاوااسکریپت با هر عکس روی `<img>` می‌نشاند.  P5-D246 */
				'w'   => $shot['w'],
				'h'   => $shot['h'],
			);
		}

		/* `<img>` تمام‌صفحه از آغاز ابعاد صریح دارد — همان عکس نخست.  P5-D246 */
		$solo_wh = ( $shots && $shots[0]['w'] && $shots[0]['h'] )
			? ' width="' . (int) $shots[0]['w'] . '" height="' . (int) $shots[0]['h'] . '"' : '';

		$out .= '<div class="tp-gallery-modal" id="tpGalleryModal" role="dialog" aria-modal="true"'
			. ' aria-labelledby="tpGalleryModalTitle"'
			. " data-tp-shots='" . esc_attr( wp_json_encode( $srcs ) ) . "'>"
			. '<div class="tp-gallery-modal__head"><div>'
			. '<h3 class="tp-gallery-modal__title" id="tpGalleryModalTitle">'
			. esc_html( sprintf(
				/* translators: %s نام موجودیت */
				__( 'گالری کامل %s', 'topersia' ), $name ) )
			. '<small>' . esc_html( sprintf(
				/* translators: %s شمار تصویر */
				_n( '%s تصویر', '%s تصویر', count( $shots ), 'topersia' ), topersia_g_fa( count( $shots ) ) ) ) . '</small></h3>'
			. '</div>'
			. '<button class="tp-gallery-modal__close" type="button" data-tp-close'
			. ' aria-label="' . esc_attr__( 'بستن گالری', 'topersia' ) . '">&times;</button>'
			. '</div>'
			. '<div class="tp-gallery-modal__grid">' . $items . '</div></div>'

			/*
			 * نمای تمام‌صفحه — همان شاسی مودال.  P5-D219 · ۴٫۲٫۱ بند ۸ · P5-D243
			 *
			 * عکس تمام‌عرض و `contain`، بلندی حداکثر ۸۰vh. دو دکمه روی لبه‌ها،
			 * وسطِ عمودیِ عکس. «بعدی» سرِ پایانِ خط (در راست‌به‌چپ لبۀ چپ،
			 * «‹»)، «قبلی» سرِ آغاز (لبۀ راست، «›»)؛ در چپ‌به‌راست برعکس.
			 * کلید فلشی که به «بعدی» اشاره می‌کند بعدی است (راست‌به‌چپ:
			 * ArrowLeft)، و کشیدن انگشت از چپ به راست در راست‌به‌چپ بعدی است.
			 * ترتیب DOM «قبلی» سپس «بعدی»، همان ترتیب خواندن.
			 *
			 * نویسه در `dir="ltr"`، چون این دو در متن راست‌به‌چپ آینه‌ای رسم
			 * می‌شوند.
			 *
			 * زیر عکس، در هر اندازه‌ای، عنوان و توضیحِ پیوست؛ شمار «۳ / ۲۳» در
			 * سرِ مودال. دو سرِ فهرست به هم می‌رسند.
			 */
			. '<div class="tp-gallery-modal tp-gallery-modal--solo" id="tpGallerySolo"'
			. ' role="dialog" aria-modal="true"'
			. ' aria-label="' . esc_attr__( 'نمای کامل تصویر', 'topersia' ) . '">'
			. '<div class="tp-gallery-modal__head"><div>'
			. '<h3 class="tp-gallery-modal__title">' . esc_html( $name )
			. '<small data-tp-solo-count></small></h3></div>'
			. '<button class="tp-gallery-modal__close" type="button" data-tp-close'
			. ' aria-label="' . esc_attr__( 'بستن تصویر', 'topersia' ) . '">&times;</button>'
			. '</div>'
			. '<figure class="tp-solo">'
			. '<div class="tp-solo__stage" data-tp-solo-stage>'
			. '<img class="tp-solo__img" alt="" decoding="async" draggable="false"' . $solo_wh . '>'
			. '<button class="tp-solo__nav tp-solo__nav--prev" type="button" data-tp-shot-prev'
			. ' aria-label="' . esc_attr__( 'قبلی', 'topersia' ) . '"><span dir="ltr">' . $arrow['prev'] . '</span></button>'
			. '<button class="tp-solo__nav tp-solo__nav--next" type="button" data-tp-shot-next'
			. ' aria-label="' . esc_attr__( 'بعدی', 'topersia' ) . '"><span dir="ltr">' . $arrow['next'] . '</span></button>'
			. '</div>'
			. '<figcaption class="tp-solo__cap"><b data-tp-solo-title></b><span data-tp-solo-caption></span></figcaption>'
			. '</figure>'
			. '</div>';

		return $out;
	}
}

/* =========================================================================
   ۶ — ثبت
   ========================================================================= */

if ( ! function_exists( 'topersia_g_register' ) ) {
	function topersia_g_register() {

		$shortcodes = array(
			'tp_page'      => 'topersia_g_sc_page',
			'tp_culture'   => 'topersia_g_sc_culture',
			'tp_hero'      => 'topersia_g_sc_hero',
			'tp_actionbar' => 'topersia_g_sc_actionbar',
			'tp_crumbs'    => 'topersia_g_sc_crumbs',
			'tp_stats'     => 'topersia_g_sc_stats',
			'tp_facts'     => 'topersia_g_sc_facts',
			'tp_badges'    => 'topersia_g_sc_badges',
			'tp_seasons'   => 'topersia_g_sc_seasons',
			'tp_weather'   => 'topersia_g_sc_weather',
			'tp_map'       => 'topersia_g_sc_map',
			'tp_slider'    => 'topersia_g_sc_slider',
			'tp_relgrid'   => 'topersia_g_sc_relgrid',
			'tp_gallery'   => 'topersia_g_sc_gallery',
			'tp_audio'     => 'topersia_g_sc_audio',
			'tp_faq'       => 'topersia_g_sc_faq',
			'tp_sources'   => 'topersia_g_sc_sources',
			'tp_ai'        => 'topersia_g_sc_ai',
			'tp_overview'  => 'topersia_g_sc_overview',
			'tp_news'      => 'topersia_g_sc_news',
			'tp_reviews'   => 'topersia_g_sc_reviews',
		);

		foreach ( $shortcodes as $tag => $callback ) {

			/* پیشوند tp_ کوتاه است؛ گارد لازم است تا افزونۀ دیگری بی‌صدا برنده نشود. */
			if ( function_exists( 'shortcode_exists' ) && shortcode_exists( $tag ) ) {

				if ( function_exists( 'topersia_log' ) ) {
					topersia_log( 'شورت‌کد ' . $tag . ' از قبل ثبت شده بود؛ ثبت نشد.', 'warning', 'render' );
				}

				continue;
			}

			add_shortcode( $tag, $callback );
		}
	}
}

if ( function_exists( 'topersia_render_ready' ) && topersia_render_ready() ) {
	topersia_g_register();
}
