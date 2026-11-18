<?php
/**
 * پاورقی المنتوری (قالب #6128) — چهار شورت‌کد به جای ویجت‌های ایستا.  دستۀ 53 بند ۱۷
 *
 * [tp_footer_ads] · [tp_footer_brand] · [tp_footer_partners] · [tp_footer_social]
 * بقیۀ ساختار المنتور (ستون‌ها، منوها، کپی‌رایت) دست نمی‌خورد؛ این پرونده فقط محتوای این چهار جا را می‌سازد.
 *
 * داده: option `topersia_footer_blocks` (بی autoload) — جدا از `topersia_footer_credits` پروندۀ legal (P5-D528).
 *   ads[4]{img,url,alt,on} · brand{logo,text_fa,text_en,text_ar} · partners[6]{img,name,url,alt,dark}
 *   social[≤20]{url,label,icon,lib} · demo_ads (۰ تا ۴)
 * بخش خالی پنهان است. نمونۀ نمایشی (تبلیغ و لوگو، بی نام برند واقعی) فقط با topersia_demo_on() و فقط وقتی دادۀ واقعی نیست.
 * رنگ و اندازه فقط از توکن‌ها در assets/topersia-g-footer.css.
 */

if ( ! defined( 'TOPERSIA_FOOTER_ADS' ) ) {
	define( 'TOPERSIA_FOOTER_ADS', 4 );
}
if ( ! defined( 'TOPERSIA_FOOTER_PARTNERS' ) ) {
	define( 'TOPERSIA_FOOTER_PARTNERS', 6 );
}
if ( ! defined( 'TOPERSIA_FOOTER_SOCIAL' ) ) {
	define( 'TOPERSIA_FOOTER_SOCIAL', 20 );
}

if ( ! function_exists( 'topersia_g_ft_url' ) ) {
	/** نشانی بیرونی امن — فقط http و https؛ وگرنه تهی. */
	function topersia_g_ft_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			return '';
		}
		return function_exists( 'esc_url_raw' ) ? (string) esc_url_raw( $url, array( 'http', 'https' ) ) : $url;
	}
}

if ( ! function_exists( 'topersia_g_ft_text' ) ) {
	/** متن یک‌خطی پاک. */
	function topersia_g_ft_text( $v ) {
		return function_exists( 'sanitize_text_field' ) ? sanitize_text_field( (string) $v ) : trim( strip_tags( (string) $v ) );
	}
}

if ( ! function_exists( 'topersia_g_ft_opts' ) ) {
	/**
	 * تنظیمات پاورقی، پاک‌شده و با شکل ثابت.
	 *
	 * @param array|null $raw مقدار خام؛ null یعنی از option.
	 * @return array
	 */
	function topersia_g_ft_opts( $raw = null ) {

		if ( null === $raw ) {
			$raw = function_exists( 'get_option' ) ? get_option( 'topersia_footer_blocks', array() ) : array();
		}
		$raw = is_array( $raw ) ? $raw : array();

		$ads = array();
		for ( $i = 0; $i < TOPERSIA_FOOTER_ADS; $i++ ) {
			$a     = isset( $raw['ads'][ $i ] ) && is_array( $raw['ads'][ $i ] ) ? $raw['ads'][ $i ] : array();
			$ads[] = array(
				'img' => isset( $a['img'] ) ? absint( $a['img'] ) : 0,
				'url' => topersia_g_ft_url( isset( $a['url'] ) ? $a['url'] : '' ),
				'alt' => topersia_g_ft_text( isset( $a['alt'] ) ? $a['alt'] : '' ),
				'on'  => ! empty( $a['on'] ),
			);
		}

		$b     = isset( $raw['brand'] ) && is_array( $raw['brand'] ) ? $raw['brand'] : array();
		$brand = array( 'logo' => isset( $b['logo'] ) ? absint( $b['logo'] ) : 0 );
		foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
			$t                     = isset( $b[ 'text_' . $l ] ) ? (string) $b[ 'text_' . $l ] : '';
			$brand[ 'text_' . $l ] = function_exists( 'sanitize_textarea_field' ) ? sanitize_textarea_field( $t ) : trim( strip_tags( $t ) );
		}

		$partners = array();
		for ( $i = 0; $i < TOPERSIA_FOOTER_PARTNERS; $i++ ) {
			$p          = isset( $raw['partners'][ $i ] ) && is_array( $raw['partners'][ $i ] ) ? $raw['partners'][ $i ] : array();
			$partners[] = array(
				'img'  => isset( $p['img'] ) ? absint( $p['img'] ) : 0,
				'name' => topersia_g_ft_text( isset( $p['name'] ) ? $p['name'] : '' ),
				'url'  => topersia_g_ft_url( isset( $p['url'] ) ? $p['url'] : '' ),
				'alt'  => topersia_g_ft_text( isset( $p['alt'] ) ? $p['alt'] : '' ),
				'dark' => ! empty( $p['dark'] ),
			);
		}

		$social = array();
		foreach ( ( isset( $raw['social'] ) && is_array( $raw['social'] ) ? $raw['social'] : array() ) as $s ) {
			if ( count( $social ) >= TOPERSIA_FOOTER_SOCIAL || ! is_array( $s ) ) {
				continue;
			}
			$url = topersia_g_ft_url( isset( $s['url'] ) ? $s['url'] : '' );
			if ( '' === $url ) {
				continue;
			}
			$social[] = array(
				'url'   => $url,
				'label' => topersia_g_ft_text( isset( $s['label'] ) ? $s['label'] : '' ),
				'icon'  => topersia_g_ft_text( isset( $s['icon'] ) ? $s['icon'] : '' ),
				'lib'   => (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) ( isset( $s['lib'] ) ? $s['lib'] : '' ) ) ),   /* همان قاعدۀ sanitize_key */
			);
		}

		$demo = isset( $raw['demo_ads'] ) ? (int) $raw['demo_ads'] : 2;

		return array(
			'ads'      => $ads,
			'brand'    => $brand,
			'partners' => $partners,
			'social'   => $social,
			'demo_ads' => max( 0, min( TOPERSIA_FOOTER_ADS, $demo ) ),
		);
	}
}

if ( ! function_exists( 'topersia_g_ft_ads' ) ) {
	/** تبلیغ‌های نمایش‌پذیر: فعال، با تصویر و نشانی؛ به ترتیب شکاف. */
	function topersia_g_ft_ads( $opts = null ) {
		$opts = null === $opts ? topersia_g_ft_opts() : $opts;
		return array_values( array_filter( $opts['ads'], function ( $a ) {
			return $a['on'] && $a['img'] > 0 && '' !== $a['url'];
		} ) );
	}
}

if ( ! function_exists( 'topersia_g_ft_demo' ) ) {
	/** نمونۀ نمایشی پاورقی روشن است؟ فقط حالت پیش‌نمایش (P5-D495). */
	function topersia_g_ft_demo() {
		return function_exists( 'topersia_demo_on' ) && topersia_demo_on();
	}
}

if ( ! function_exists( 'topersia_g_ft_img' ) ) {
	/** تصویر پیوست با ابعاد صریح (P5-D246). */
	function topersia_g_ft_img( $id, $alt, $class ) {
		if ( ! function_exists( 'wp_get_attachment_image' ) ) {
			return '';
		}
		return (string) wp_get_attachment_image( (int) $id, 'medium', false, array(
			'class'    => $class,
			'alt'      => $alt,
			'loading'  => 'lazy',
			'decoding' => 'async',
		) );
	}
}

if ( ! function_exists( 'topersia_g_sc_footer_ads' ) ) {
	/** [tp_footer_ads] — ۱ تا ۴ تبلیغ؛ صفر ← هیچ خروجی. */
	function topersia_g_sc_footer_ads() {

		$opts  = topersia_g_ft_opts();
		$ads   = topersia_g_ft_ads( $opts );
		$tag   = esc_html__( 'تبلیغ', 'topersia' );
		$items = '';

		foreach ( $ads as $a ) {
			$alt    = '' !== $a['alt'] ? $a['alt'] : __( 'تبلیغ', 'topersia' );
			$items .= '<a class="tp-ft-ad" href="' . esc_url( $a['url'] ) . '" target="_blank" rel="sponsored noopener">'
				. topersia_g_ft_img( $a['img'], $alt, 'tp-ft-ad__img' )
				. '<span class="tp-ft-ad__tag">' . $tag . '</span></a>';
		}

		$n = count( $ads );

		if ( 0 === $n && topersia_g_ft_demo() ) {
			$n = $opts['demo_ads'];
			for ( $i = 0; $i < $n; $i++ ) {
				$items .= '<div class="tp-ft-ad tp-ft-ad--demo"><span class="tp-ft-ad__ph">' . esc_html__( 'جای تبلیغ', 'topersia' ) . '</span>'
					. '<span class="tp-ft-ad__tag">' . esc_html__( 'نمونۀ نمایشی', 'topersia' ) . '</span></div>';
			}
		}

		if ( 0 === $n ) {
			return '';
		}

		return '<section class="tp-ft tp-ft-ads tp-ft-ads--n' . (int) $n . '" aria-label="' . esc_attr__( 'تبلیغ', 'topersia' ) . '">' . $items . '</section>';
	}
}

if ( ! function_exists( 'topersia_g_ft_brand_text' ) ) {
	/** متن سئوی پاورقی به زبان صفحه: مقدار تنظیمات، وگرنه پیش‌فرض کاتالوگ. */
	function topersia_g_ft_brand_text( $opts = null, $lang = null ) {
		$opts = null === $opts ? topersia_g_ft_opts() : $opts;
		$lang = null === $lang ? ( function_exists( 'topersia_g_page_lang' ) ? topersia_g_page_lang() : 'fa' ) : $lang;
		$own  = isset( $opts['brand'][ 'text_' . $lang ] ) ? $opts['brand'][ 'text_' . $lang ] : '';
		if ( '' !== $own ) {
			return $own;
		}
		return __( 'توپرشیا مرجع جامع اطلاعات ایران و گردشگری ایران است؛ راهنمای استان‌ها، شهرها، روستاها، جزیره‌ها، جاذبه‌ها، رویدادها و برنامه‌ریزی سفر به ایران، به چند زبان و با ذکر منبع و به‌روزرسانی منظم.', 'topersia' );
	}
}

if ( ! function_exists( 'topersia_g_sc_footer_brand' ) ) {
	/** [tp_footer_brand] — لوگو، نام و متن سئو. */
	function topersia_g_sc_footer_brand() {

		$opts = topersia_g_ft_opts();
		$name = function_exists( 'topersia_g_brand' ) ? topersia_g_brand() : 'ToPersia';
		$home = function_exists( 'home_url' ) ? home_url( '/' ) : '/';
		$logo = $opts['brand']['logo'] > 0 ? topersia_g_ft_img( $opts['brand']['logo'], $name, 'tp-ft-brand__logo' ) : '';

		return '<div class="tp-ft tp-ft-brand">'
			. ( '' !== $logo ? '<a class="tp-ft-brand__home" href="' . esc_url( $home ) . '">' . $logo . '</a>' : '' )
			. '<p class="tp-ft-brand__name">' . esc_html( $name ) . '</p>'
			. '<p class="tp-ft-brand__text">' . esc_html( topersia_g_ft_brand_text( $opts ) ) . '</p>'
			. '</div>';
	}
}

if ( ! function_exists( 'topersia_g_sc_footer_partners' ) ) {
	/** [tp_footer_partners] — «همکاران ما»، تا ۶ لوگو؛ بی لوگو ← هیچ خروجی. */
	function topersia_g_sc_footer_partners() {

		$opts  = topersia_g_ft_opts();
		$items = '';

		foreach ( $opts['partners'] as $p ) {
			if ( $p['img'] <= 0 ) {
				continue;
			}
			$alt   = '' !== $p['alt'] ? $p['alt'] : $p['name'];
			$img   = topersia_g_ft_img( $p['img'], $alt, 'tp-ft-logo__img' );
			$cls   = 'tp-ft-logo' . ( $p['dark'] ? ' tp-ft-logo--dark' : '' );
			$sty   = ' style="--tp-lh:' . esc_attr( (string) topersia_g_ft_logo_scale( $p['img'] ) ) . '"';   /* اندازۀ اپتیکال — P5-D609 */
			$inner = '' !== $p['url']
				? '<a class="' . $cls . '"' . $sty . ' href="' . esc_url( $p['url'] ) . '" target="_blank" rel="noopener nofollow">' . $img . '</a>'
				: '<span class="' . $cls . '"' . $sty . '>' . $img . '</span>';
			$items .= '<li class="tp-ft-row__item">' . $inner . '</li>';
		}

		if ( '' === $items && topersia_g_ft_demo() ) {
			for ( $i = 0; $i < 3; $i++ ) {
				$items .= '<li class="tp-ft-row__item"><span class="tp-ft-logo tp-ft-logo--demo">' . esc_html__( 'لوگوی نمونه', 'topersia' ) . '</span></li>';
			}
		}

		if ( '' === $items ) {
			return '';
		}

		return '<section class="tp-ft tp-ft-partners"><h2 class="tp-ft-h">' . esc_html__( 'همکاران ما', 'topersia' ) . '</h2>'
			. '<ul class="tp-ft-row" role="list">' . $items . '</ul></section>';
	}
}

if ( ! function_exists( 'topersia_g_ft_svg_icons' ) ) {
	/**
	 * آیکون‌های درون‌خطی شبکه‌هایی که Font Awesome ندارد (بله، روبیکا) با رنگ اصلی برند — کتابخانۀ «tp-svg»؛ پاورقی به مجموعۀ
	 * قلم «javis-fa» المنتور وابسته نیست. شکل بله همان گلیف icon-10 آن مجموعه (مسیر در مختصات قلم، y رو به بالا) با تیک سفید؛
	 * روبیکا شش‌ضلعی ۱۸ مثلث رنگی و مکعب سفید (نمونۀ رنگ از لوگوی اصلی که کاربر داد). رنگ‌ها رنگ خود لوگوست، مثل تصویر — نه توکن.
	 * نام ← [ viewBox ، نشانه‌گذاری درونی ثابت ].
	 */
	function topersia_g_ft_svg_icons() {
		return array(
			'bale'   => array( '7.8 -504.1 489.8 489.8', '<circle cx="253" cy="-255" r="185" fill="#FFFFFF"/><path transform="scale(1 -1)" fill="#0DCB9D" d="M497 242c-1-8-2-18-3-26-1-8-4-16-6-24-2-10-6-18-10-28-3-7-7-15-11-22-4-7-8-13-12-20-4-7-9-13-14-20-6-6-11-12-18-18-7-7-16-15-25-22-7-5-15-10-24-15-8-5-18-10-28-14-11-4-22-8-32-11-10-2-20-4-29-5-19-3-37-3-55-1-16 1-33 5-48 10l-1 0c-76 22-139 84-162 160-5 16-9 33-9 51-3 17-2 33-2 49 0 14 0 27 0 40 0 14 0 28 0 41 0 12 0 24 0 36 0 12 0 24 0 35 0 12 0 24 0 35-1 9 2 18 9 24 7 6 17 9 26 5 6-1 11-4 15-8 18-11 35-24 52-36 1 1 3 2 4 3 7 5 14 9 21 13 8 4 15 8 23 11 8 3 16 6 24 9 9 2 18 4 28 6 9 2 19 3 30 3 12 1 25 1 37-1 9 0 19-1 28-4 63-13 120-52 154-107 15-24 27-50 33-78 2-11 4-21 4-31 2-14 2-27 1-40z m-91 107c-5 12-16 21-28 26-10 3-20 4-31 1-7-2-14-7-20-13-6-6-12-11-17-17-6-5-11-10-16-16-6-5-12-11-18-17-3-4-6-7-10-11-6-5-11-11-16-16-6-6-12-12-18-17-4-4-8-9-12-13-14 14-29 28-42 42-8 8-17 12-28 14-9 1-20-1-28-5-8-5-16-11-20-19-6-10-8-23-5-34 2-9 6-17 13-24 25-25 51-51 76-76 9-8 20-12 32-13 10-1 20 2 28 7 7 6 12 12 19 18 6 6 13 12 19 19 6 7 13 13 19 20 7 7 14 13 21 20 6 6 12 12 18 18 6 6 11 11 16 17 6 6 12 11 18 17 5 6 11 11 16 17 6 5 12 12 14 20 5 11 4 24 0 35z"/>' ),
			'rubika' => array( '15 15 1050 1050', '<polygon fill="#B9CF06" points="309,138 540,15 540,272"/><polygon fill="#82B52A" points="540,15 771,138 540,272"/><polygon fill="#F3A928" points="309,138 540,272 309,406"/><polygon fill="#3AAE9A" points="771,138 540,272 771,406"/><polygon fill="#EE7518" points="309,138 77,282 309,406"/><polygon fill="#5DD4BE" points="771,138 1003,282 771,406"/><polygon fill="#E74F53" points="77,282 309,406 77,540"/><polygon fill="#7B4686" points="1003,282 771,406 1003,540"/><polygon fill="#7B4686" points="77,540 309,406 309,673"/><polygon fill="#4F3881" points="1003,540 771,406 771,673"/><polygon fill="#4F3881" points="77,540 309,673 77,800"/><polygon fill="#E74F53" points="1003,540 771,673 1003,800"/><polygon fill="#5DD4BE" points="77,800 309,673 309,940"/><polygon fill="#EE7518" points="1003,800 771,673 771,940"/><polygon fill="#3AAE9A" points="309,673 540,807 309,940"/><polygon fill="#F3A928" points="771,673 540,807 771,940"/><polygon fill="#82B52A" points="309,940 540,807 540,1065"/><polygon fill="#B9CF06" points="771,940 540,807 540,1065"/><polygon fill="#FCFCFC" points="540,272 771,406 540,540 309,406"/><polygon fill="#E2E2E2" points="309,406 540,540 540,807 309,673"/><polygon fill="#EEEEEE" points="540,540 771,406 771,673 540,807"/>' ),
		);
	}
}

if ( ! function_exists( 'topersia_g_ft_social_icon' ) ) {
	/** آیکون شبکه با Icons_Manager المنتور؛ بی المنتور نخستین حرف نام. */
	function topersia_g_ft_social_icon( $s, $label ) {

		$svg = topersia_g_ft_svg_icons();
		if ( 'tp-svg' === $s['lib'] && isset( $svg[ $s['icon'] ] ) ) {
			return '<svg class="tp-ft-social__svg tp-ft-social__svg--brand" viewBox="' . esc_attr( $svg[ $s['icon'] ][0] ) . '" aria-hidden="true" focusable="false">' . $svg[ $s['icon'] ][1] . '</svg>'   /* نشانه‌گذاری ثابت همین پرونده، نه ورودی */;
		}

		if ( '' !== $s['icon'] && class_exists( '\Elementor\Icons_Manager' ) ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon( array( 'value' => $s['icon'], 'library' => $s['lib'] ), array( 'aria-hidden' => 'true' ) );
			$html = trim( (string) ob_get_clean() );
			if ( '' !== $html ) {
				return $html;
			}
		}

		$first = function_exists( 'mb_substr' ) ? mb_substr( $label, 0, 1 ) : substr( $label, 0, 1 );

		return '<span class="tp-ft-social__ch" aria-hidden="true">' . esc_html( $first ) . '</span>';
	}
}

if ( ! function_exists( 'topersia_g_ft_social_label' ) ) {
	/** برچسب دسترسی‌پذیر: برچسب تنظیمات، وگرنه نام میزبان. */
	function topersia_g_ft_social_label( $s ) {
		if ( '' !== $s['label'] ) {
			return $s['label'];
		}
		$host = (string) wp_parse_url( $s['url'], PHP_URL_HOST );
		return preg_replace( '/^www\./', '', $host );
	}
}

if ( ! function_exists( 'topersia_g_ft_brand_fill' ) ) {
	/**
	 * رنگ اصلی هر شبکه (P5-D610) — نام میزبان ← [ رنگ برند ، کاشی ]. رنگ نشانه همیشه رنگ خود برند است؛ اگر روی کاشی روشن
	 * مشترک (#FFFFFF) کمتر از ۳:۱ دارد، کاشی تیره (--tp-ft-tile-dark) می‌گیرد — رنگ کاشی عوض می‌شود نه رنگ برند.
	 * کنتراست هر جفت را گیت پاورقی در هر سه حالت می‌سنجد. بله و روبیکا (tp-svg) رنگ را در خود SVG دارند؛ رنگ این‌جا نمایندۀ
	 * سنجش است (روبیکا چندرنگ؛ سبز چیرۀ لوگو).
	 */
	function topersia_g_ft_brand_fill( $s ) {
		$map  = array(
			'instagram.com'   => array( '#E4405F', 'l' ),
			'facebook.com'    => array( '#1877F2', 'l' ),
			'x.com'           => array( '#000000', 'l' ),
			'twitter.com'     => array( '#000000', 'l' ),
			'youtube.com'     => array( '#FF0000', 'l' ),
			't.me'            => array( '#26A5E4', 'd' ),
			'whatsapp.com'    => array( '#25D366', 'd' ),
			'wa.me'           => array( '#25D366', 'd' ),
			'linkedin.com'    => array( '#0A66C2', 'l' ),
			'pinterest.com'   => array( '#E60023', 'l' ),
			'tiktok.com'      => array( '#000000', 'l' ),
			'medium.com'      => array( '#000000', 'l' ),
			'tripadvisor.com' => array( '#34E0A1', 'd' ),
			'tumblr.com'      => array( '#36465D', 'l' ),
			'ble.ir'          => array( '#0DCB9D', 'd' ),
			'rubika.ir'       => array( '#82B52A', 'd' ),
		);
		$host = preg_replace( '/^(www|m)\./', '', (string) wp_parse_url( $s['url'], PHP_URL_HOST ) );
		foreach ( $map as $h => $c ) {
			if ( $host === $h || substr( $host, -strlen( '.' . $h ) ) === '.' . $h ) {
				return array( $c[0], 'd' === $c[1] ? 'var(--tp-ft-tile-dark)' : 'var(--tp-ft-tile)' );
			}
		}
		return null;
	}
}

if ( ! function_exists( 'topersia_g_ft_logo_scale' ) ) {
	/**
	 * اندازۀ اپتیکال هم‌وزن لوگوی همکار (P5-D609): لوگوی پهن کوتاه‌تر می‌نشیند تا سطح دیداری نزدیک لوگوی مربعی شود —
	 * بلندی = بلندی کاشی × (۱ / نسبت)^۰٫۳۵، سقف ۱؛ پهنا را max-width:100% در CSS می‌بندد. نسبت از ابعاد پیوست.
	 */
	function topersia_g_ft_logo_scale( $id ) {
		$m  = function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( $id ) : array();
		$ar = ( is_array( $m ) && ! empty( $m['width'] ) && ! empty( $m['height'] ) ) ? $m['width'] / $m['height'] : 1;
		return round( min( 1, pow( 1 / max( .2, $ar ), .35 ) ), 3 );
	}
}

if ( ! function_exists( 'topersia_g_sc_footer_social' ) ) {
	/** [tp_footer_social] — شبکه‌ها از option؛ بی شبکه ← هیچ خروجی. */
	function topersia_g_sc_footer_social() {

		$items = '';

		$list = topersia_g_ft_opts()['social'];

		foreach ( $list as $s ) {
			$label  = topersia_g_ft_social_label( $s );
			$fill   = topersia_g_ft_brand_fill( $s );
			$style  = $fill ? ' style="--tp-soc:' . esc_attr( $fill[0] ) . ';--tp-soc-tile:' . esc_attr( $fill[1] ) . '"' : '';
			$items .= '<li class="tp-ft-row__item"><a class="tp-ft-social__a' . ( $fill ? ' tp-ft-social__a--brand' : '' ) . '"' . $style . ' href="' . esc_url( $s['url'] ) . '" target="_blank" rel="noopener me" aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '">'
				. topersia_g_ft_social_icon( $s, $label ) . '</a></li>';
		}

		if ( '' === $items ) {
			return '';
		}

		return '<section class="tp-ft tp-ft-social"><h2 class="tp-ft-h">' . esc_html__( 'شبکه‌های اجتماعی', 'topersia' ) . '</h2>'
			. '<ul class="tp-ft-row tp-ft-social__grid" role="list" style="--tp-ft-soc-cols:' . (int) ceil( count( $list ) / 2 ) . ';--tp-ft-soc-cols-s:' . (int) ceil( count( $list ) / 3 ) . '">' . $items . '</ul></section>';   /* دو ردیف هم‌شمار */
	}
}

if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'tp_footer_ads', 'topersia_g_sc_footer_ads' );
	add_shortcode( 'tp_footer_brand', 'topersia_g_sc_footer_brand' );
	add_shortcode( 'tp_footer_partners', 'topersia_g_sc_footer_partners' );
	add_shortcode( 'tp_footer_social', 'topersia_g_sc_footer_social' );
}

if ( function_exists( 'add_action' ) ) {

	/* شیوه‌نامۀ پاورقی روی همۀ صفحه‌ها — پاورقی همه‌جا هست؛ پس از توکن‌ها. */
	add_action( 'wp_enqueue_scripts', function () {
		if ( ! function_exists( 'topersia_render_url' ) || ( function_exists( 'topersia_render_ready' ) && ! topersia_render_ready() ) ) {
			return;
		}
		wp_enqueue_style( 'topersia-g-footer', topersia_render_url() . 'assets/topersia-g-footer.css', array( 'topersia-tokens' ), topersia_render_asset_version( 'assets/topersia-g-footer.css' ) );
	}, 22 );

	add_action( 'admin_menu', function () {
		$parent = function_exists( 'topersia_admin_page' ) ? 'topersia' : 'topersia-render';
		add_submenu_page( $parent, esc_html__( 'پاورقی', 'topersia' ), esc_html__( 'پاورقی', 'topersia' ), 'manage_options', 'topersia-footer', 'topersia_g_ft_admin_page' );
	}, 20 );

	add_action( 'admin_post_topersia_footer_save', 'topersia_g_ft_admin_save' );
}

if ( ! function_exists( 'topersia_g_ft_admin_save' ) ) {
	/** ذخیرۀ پیشخوان — مدیر، با nonce؛ همه از پاک‌کن topersia_g_ft_opts. */
	function topersia_g_ft_admin_save() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی ندارید.', 'topersia' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'topersia_footer_save' );

		$in   = isset( $_POST['tp_ft'] ) && is_array( $_POST['tp_ft'] ) ? wp_unslash( $_POST['tp_ft'] ) : array();
		$opts = topersia_g_ft_opts( $in );

		update_option( 'topersia_footer_blocks', $opts, false );

		wp_safe_redirect( add_query_arg( array( 'page' => 'topersia-footer', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}

if ( ! function_exists( 'topersia_g_ft_admin_media' ) ) {
	/** فیلد انتخاب تصویر از کتابخانۀ رسانه. */
	function topersia_g_ft_admin_media( $name, $id ) {
		$prev = $id > 0 ? wp_get_attachment_image( $id, array( 120, 60 ), false, array( 'class' => 'tp-ft-admin__prev' ) ) : '';
		return '<span class="tp-ft-admin__media"><input type="hidden" name="' . esc_attr( $name ) . '" value="' . (int) $id . '">'
			. '<span class="tp-ft-admin__box">' . $prev . '</span> '
			. '<button type="button" class="button tp-ft-admin__pick">' . esc_html__( 'انتخاب تصویر', 'topersia' ) . '</button> '
			. '<button type="button" class="button-link tp-ft-admin__clear">' . esc_html__( 'برداشتن', 'topersia' ) . '</button></span>';
	}
}

if ( ! function_exists( 'topersia_g_ft_admin_page' ) ) {
	/** صفحۀ «پاورقی» در پیشخوان. */
	function topersia_g_ft_admin_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_enqueue_media();

		$o  = topersia_g_ft_opts();
		$in = function ( $name, $val, $type = 'text' ) {
			return '<input type="' . esc_attr( $type ) . '" class="regular-text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '">';
		};
		$cb = function ( $name, $on ) {
			return '<input type="checkbox" name="' . esc_attr( $name ) . '" value="1"' . ( $on ? ' checked' : '' ) . '>';
		};

		echo '<div class="wrap tp-ft-admin"><h1>' . esc_html__( 'پاورقی', 'topersia' ) . '</h1>';
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'ذخیره شد.', 'topersia' ) . '</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'topersia_footer_save' );
		echo '<input type="hidden" name="action" value="topersia_footer_save">';

		echo '<h2>' . esc_html__( 'تبلیغ', 'topersia' ) . '</h2><table class="widefat striped"><tbody>';
		foreach ( $o['ads'] as $i => $a ) {
			$k = 'tp_ft[ads][' . $i . ']';
			echo '<tr><th>' . (int) ( $i + 1 ) . '</th><td>' . topersia_g_ft_admin_media( $k . '[img]', $a['img'] ) . '</td>'
				. '<td><label>' . esc_html__( 'نشانی پیوند', 'topersia' ) . ' ' . $in( $k . '[url]', $a['url'], 'url' ) . '</label></td>'
				. '<td><label>' . esc_html__( 'متن جایگزین', 'topersia' ) . ' ' . $in( $k . '[alt]', $a['alt'] ) . '</label></td>'
				. '<td><label>' . $cb( $k . '[on]', $a['on'] ) . ' ' . esc_html__( 'فعال', 'topersia' ) . '</label></td></tr>';
		}
		echo '</tbody></table>';
		echo '<p><label>' . esc_html__( 'شمار تبلیغ نمونه در حالت پیش‌نمایش', 'topersia' ) . ' <input type="number" min="0" max="4" name="tp_ft[demo_ads]" value="' . (int) $o['demo_ads'] . '"></label></p>';

		echo '<h2>' . esc_html__( 'برند', 'topersia' ) . '</h2><p>' . topersia_g_ft_admin_media( 'tp_ft[brand][logo]', $o['brand']['logo'] ) . '</p>';
		foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
			echo '<p><label lang="en">' . esc_html( strtoupper( $l ) ) . '<br><textarea class="large-text" rows="3" dir="auto" name="tp_ft[brand][text_' . esc_attr( $l ) . ']">' . esc_textarea( $o['brand'][ 'text_' . $l ] ) . '</textarea></label></p>';
		}

		echo '<h2>' . esc_html__( 'همکاران ما', 'topersia' ) . '</h2><table class="widefat striped"><tbody>';
		foreach ( $o['partners'] as $i => $p ) {
			$k = 'tp_ft[partners][' . $i . ']';
			echo '<tr><th>' . (int) ( $i + 1 ) . '</th><td>' . topersia_g_ft_admin_media( $k . '[img]', $p['img'] ) . '</td>'
				. '<td><label>' . esc_html__( 'نام', 'topersia' ) . ' ' . $in( $k . '[name]', $p['name'] ) . '</label></td>'
				. '<td><label>' . esc_html__( 'نشانی پیوند', 'topersia' ) . ' ' . $in( $k . '[url]', $p['url'], 'url' ) . '</label></td>'
				. '<td><label>' . esc_html__( 'متن جایگزین', 'topersia' ) . ' ' . $in( $k . '[alt]', $p['alt'] ) . '</label></td>'
				. '<td><label>' . $cb( $k . '[dark]', $p['dark'] ) . ' ' . esc_html__( 'لوگوی روشن (زمینۀ تیره)', 'topersia' ) . '</label></td></tr>';
		}
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'شبکه‌های اجتماعی', 'topersia' ) . '</h2><table class="widefat striped"><tbody>';
		$rows = array_merge( $o['social'], array_fill( 0, max( 0, min( 3, TOPERSIA_FOOTER_SOCIAL - count( $o['social'] ) ) ), array( 'url' => '', 'label' => '', 'icon' => '', 'lib' => '' ) ) );
		foreach ( $rows as $i => $s ) {
			$k = 'tp_ft[social][' . $i . ']';
			echo '<tr><td><label>' . esc_html__( 'نشانی پیوند', 'topersia' ) . ' ' . $in( $k . '[url]', $s['url'], 'url' ) . '</label></td>'
				. '<td><label>' . esc_html__( 'نام', 'topersia' ) . ' ' . $in( $k . '[label]', $s['label'] ) . '</label></td>'
				. '<td><label>' . esc_html__( 'آیکون', 'topersia' ) . ' ' . $in( $k . '[icon]', $s['icon'] ) . '</label></td>'
				. '<td><label>' . esc_html__( 'کتابخانه', 'topersia' ) . ' ' . $in( $k . '[lib]', $s['lib'] ) . '</label></td></tr>';
		}
		echo '</tbody></table>';

		submit_button();
		echo '</form></div>';
		?>
<script>
( function () {
	document.addEventListener( 'click', function ( e ) {
		var pick = e.target.closest( '.tp-ft-admin__pick' ), clear = e.target.closest( '.tp-ft-admin__clear' );
		if ( ! pick && ! clear ) { return; }
		var box = ( pick || clear ).closest( '.tp-ft-admin__media' ), input = box.querySelector( 'input' ), prev = box.querySelector( '.tp-ft-admin__box' );
		if ( clear ) { input.value = '0'; prev.textContent = ''; return; }
		var frame = wp.media( { multiple: false, library: { type: 'image' } } );
		frame.on( 'select', function () {
			var a = frame.state().get( 'selection' ).first().toJSON(), img = document.createElement( 'img' );
			input.value = a.id; img.src = ( a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url ); img.className = 'tp-ft-admin__prev';
			prev.textContent = ''; prev.appendChild( img );
		} );
		frame.open();
	} );
} )();
</script>
<style>.tp-ft-admin__prev{max-height:48px;width:auto;vertical-align:middle}.tp-ft-admin td{vertical-align:middle}</style>
		<?php
	}
}
