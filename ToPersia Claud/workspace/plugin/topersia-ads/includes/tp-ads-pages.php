<?php
/**
 * صفحۀ فروش /advertise/ — دستۀ 54 بند ۱۲.
 *
 * [tp_advertise] در سه برگۀ fa · en · ar (ساختۀ scripts/tp-ads-pages.php، ترجمه‌های WPML یک برگه):
 *   نقشۀ جایگاه‌ها (طرح‌وارۀ هر نوع صفحه، از همان ثبت جایگاه‌ها) · فقط عدد واقعی (شمار صفحه‌ها از پایگاه‌داده؛ نمایش
 *   فقط اگر آمار هست) · مدل‌های فروش · پیوند تماس · بی قیمت («تماس بگیرید»).
 * noindex تا کاربر تأیید کند: گزینۀ tp_ads_advertise_index (پیش‌فرض خاموش).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'topersia_ads_is_advertise' ) ) {
	/** برگۀ جاری همان صفحۀ فروش است؟ (نامک اصلی advertise یا شورت‌کد در متن) */
	function topersia_ads_is_advertise() {
		if ( ! is_page() ) {
			return false;
		}
		$id = (int) get_queried_object_id();
		return 'advertise' === get_post_field( 'post_name', topersia_ads_original_id( $id ) ) || has_shortcode( (string) get_post_field( 'post_content', $id ), 'tp_advertise' );
	}
}

/* noindex تا تأیید — هم هستۀ وردپرس و هم Rank Math */
add_filter( 'wp_robots', function ( $r ) {
	if ( topersia_ads_is_advertise() && ! get_option( 'tp_ads_advertise_index' ) ) {
		$r['noindex'] = true;
		unset( $r['index'] );
	}
	return $r;
}, 99 );

add_filter( 'rank_math/frontend/robots', function ( $r ) {
	if ( topersia_ads_is_advertise() && ! get_option( 'tp_ads_advertise_index' ) ) {
		$r['index'] = 'noindex';
	}
	return $r;
}, 99 );

if ( ! function_exists( 'topersia_ads_figures' ) ) {
	/**
	 * عددهای واقعی صفحۀ فروش — هر کدام فقط اگر بیش از صفر. شمار به زبان جاری (WPML)؛ کش ۱۲ ساعت به‌ازای زبان.
	 *
	 * @return array[] [ label، n ]
	 */
	function topersia_ads_figures() {
		$lang = topersia_ads_lang();
		$key  = 'tp_ads_fig_' . $lang;
		$fig  = get_transient( $key );

		if ( ! is_array( $fig ) ) {
			$types = array_values( array_filter( get_post_types( array( 'public' => true ) ), function ( $t ) {
				return 0 === strpos( $t, 'tp_' ) && 'tp_ad' !== $t;
			} ) );
			$count = function ( $pt ) {
				if ( ! $pt ) {
					return 0;
				}
				$q = new WP_Query( array( 'post_type' => $pt, 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => false, 'has_password' => false ) );
				return (int) $q->found_posts;
			};
			$imp = 0;
			foreach ( topersia_ads_report( wp_date( 'Y-m-d', time() - 29 * DAY_IN_SECONDS ), wp_date( 'Y-m-d' ) ) as $r ) {
				$imp += $r['imp'];
			}
			$fig = array(
				'places' => $count( $types ),
				'news'   => $count( array_values( array_filter( array( 'tpc_news', 'tpc_article' ), 'post_type_exists' ) ) ),
				'imp'    => $imp,
			);
			set_transient( $key, $fig, 12 * HOUR_IN_SECONDS );
		}

		$out = array();
		if ( $fig['places'] > 0 ) {
			$out[] = array( __( 'صفحۀ مقصد (استان، شهر، جاذبه و …)', 'topersia-ads' ), $fig['places'] );
		}
		if ( $fig['news'] > 0 ) {
			$out[] = array( __( 'خبر و مقاله', 'topersia-ads' ), $fig['news'] );
		}
		$out[] = array( __( 'زبان (فارسی، انگلیسی، عربی)', 'topersia-ads' ), 3 );
		if ( $fig['imp'] > 0 ) {
			$out[] = array( __( 'نمایش دیده‌شدۀ تبلیغ در ۳۰ روز گذشته', 'topersia-ads' ), $fig['imp'] );
		}
		return $out;
	}
}

if ( ! function_exists( 'topersia_ads_map' ) ) {
	/** طرح‌وارۀ هر نوع صفحه — بلوک‌های محتوا و جای هر جایگاه، از ثبت جایگاه‌ها (نه فهرست دستی دوم). */
	function topersia_ads_map() {
		$slots  = topersia_ads_slots();
		$ratios = topersia_ads_ratios();
		$block  = function ( $label, $slot = '' ) use ( $slots, $ratios ) {
			if ( '' === $slot ) {
				return '<li class="tp-adv-map__b">' . esc_html( $label ) . '</li>';
			}
			$s    = $slots[ $slot ];
			$size = $ratios[ $s['d'] ][1] . '×' . $ratios[ $s['d'] ][2] . ( '' !== $s['m'] && $s['m'] !== $s['d'] ? ' · ' . $ratios[ $s['m'] ][1] . '×' . $ratios[ $s['m'] ][2] : '' );
			$dev  = '' === $s['m'] ? __( 'فقط دسکتاپ', 'topersia-ads' ) : __( 'دسکتاپ و موبایل', 'topersia-ads' );
			return '<li class="tp-adv-map__b tp-adv-map__b--ad"><strong>' . esc_html( __( $s['name'], 'topersia-ads' ) ) . '</strong>'   // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				. '<span>' . esc_html( $dev ) . ' · <bdi dir="ltr">' . esc_html( $size ) . '</bdi></span></li>';
		};
		$pages = array(
			array( __( 'صفحۀ اول', 'topersia-ads' ), array(
				$block( __( 'سربرگ (بی تبلیغ)', 'topersia-ads' ) ), $block( __( 'استان‌ها و پیشنهادها', 'topersia-ads' ) ), $block( '', 'home-mid' ),
				$block( __( 'جاذبه‌ها و راهنما', 'topersia-ads' ) ), $block( '', 'home-mid' ), $block( __( 'مجله', 'topersia-ads' ) ),
			) ),
			array( __( 'هاب و فهرست خبر', 'topersia-ads' ), array(
				$block( __( 'سربرگ و نوار ابزار', 'topersia-ads' ) ), $block( '', 'hub-top' ), $block( __( '۴ کارت', 'topersia-ads' ) ), $block( '', 'hub-infeed' ), $block( __( 'کارت‌های بعدی (هر ۸ کارت یک جایگاه)', 'topersia-ads' ) ),
			) ),
			array( __( 'خبر و مقاله', 'topersia-ads' ), array(
				$block( __( 'سربرگ و ۳ بند نخست', 'topersia-ads' ) ), $block( '', 'single-inline' ), $block( __( 'ادامۀ متن', 'topersia-ads' ) ), $block( '', 'single-end' ), $block( __( 'مرتبط‌ها', 'topersia-ads' ) ), $block( '', 'single-rail' ),
			) ),
			array( __( 'صفحۀ مقصد', 'topersia-ads' ), array(
				$block( __( 'سربرگ و درباره', 'topersia-ads' ) ), $block( __( 'جاذبه‌ها', 'topersia-ads' ) ), $block( '', 'entity-mid' ), $block( __( 'بخش‌های بعدی', 'topersia-ads' ) ), $block( '', 'entity-rail' ),
			) ),
			array( __( 'پاورقی همۀ صفحه‌ها', 'topersia-ads' ), array(
				$block( '', 'footer' ), $block( __( '۱ تا ۴ تبلیغ کنار هم', 'topersia-ads' ) ),
			) ),
		);
		$html = '<div class="tp-adv-map">';
		foreach ( $pages as $p ) {
			$html .= '<figure class="tp-adv-map__p"><figcaption>' . esc_html( $p[0] ) . '</figcaption><ol>' . implode( '', $p[1] ) . '</ol></figure>';
		}
		return $html . '</div>';
	}
}

if ( ! function_exists( 'topersia_ads_sc_advertise' ) ) {
	/** [tp_advertise] */
	function topersia_ads_sc_advertise() {

		$contact = function_exists( 'topersia_g_legal_page' ) ? (int) topersia_g_legal_page( 'contact' ) : 0;
		$href    = $contact ? get_permalink( $contact ) : '';
		$models  = array(
			array( __( 'زمانی', 'topersia-ads' ), __( 'یک یا چند جایگاه برای بازه‌ای مشخص؛ شروع و پایان دقیق.', 'topersia-ads' ) ),
			array( __( 'نمایشی', 'topersia-ads' ), __( 'تا رسیدن به شمار نمایش دیده‌شده (دست‌کم نیمی از تبلیغ، یک ثانیه).', 'topersia-ads' ) ),
			array( __( 'کلیکی', 'topersia-ads' ), __( 'تا رسیدن به شمار کلیک؛ کلیک ربات و تکراری شمرده نمی‌شود.', 'topersia-ads' ) ),
			array( __( 'اسپانسر انحصاری', 'topersia-ads' ), __( 'یک جایگاه در یک بازه فقط برای یک برند.', 'topersia-ads' ) ),
		);

		wp_enqueue_style( 'topersia-ads', topersia_ads_url( 'assets/tp-ads.css' ), array(), TOPERSIA_ADS_VERSION );

		$html  = '<div class="tp-adv">';
		$html .= '<p class="tp-adv__lead">' . esc_html__( 'تبلیغ مستقیم در توپرشیا: جایگاه‌های ثابت در صفحه‌های مقصد، خبر، هاب و صفحۀ اول؛ بی پنجرۀ بازشو، بی تبلیغ چسبان در موبایل و حداکثر سه تبلیغ در هر صفحه. هر تبلیغ برچسب «تبلیغ» دارد و آمار نمایش و کلیک بی‌نام گزارش می‌شود.', 'topersia-ads' ) . '</p>';

		$html .= '<section class="tp-adv__s"><h2>' . esc_html__( 'عددها', 'topersia-ads' ) . '</h2><ul class="tp-adv__fig">';
		foreach ( topersia_ads_figures() as $f ) {
			$html .= '<li><strong>' . esc_html( number_format_i18n( $f[1] ) ) . '</strong><span>' . esc_html( $f[0] ) . '</span></li>';
		}
		$html .= '</ul><p class="tp-adv__note">' . esc_html__( 'فقط عدد واقعی: شمار صفحه‌ها از خود سایت است؛ آمار بازدید پس از راه‌اندازی و فقط از سامانۀ شمارش بی‌نام همین سایت.', 'topersia-ads' ) . '</p></section>';

		$html .= '<section class="tp-adv__s"><h2>' . esc_html__( 'جایگاه‌ها', 'topersia-ads' ) . '</h2>' . topersia_ads_map() . '</section>';

		$html .= '<section class="tp-adv__s"><h2>' . esc_html__( 'مدل‌های فروش', 'topersia-ads' ) . '</h2><dl class="tp-adv__models">';
		foreach ( $models as $m ) {
			$html .= '<div><dt>' . esc_html( $m[0] ) . '</dt><dd>' . esc_html( $m[1] ) . '</dd></div>';
		}
		$html .= '</dl><p class="tp-adv__price"><strong>' . esc_html__( 'قیمت:', 'topersia-ads' ) . '</strong> ' . esc_html__( 'تماس بگیرید.', 'topersia-ads' ) . '</p></section>';

		$html .= '<section class="tp-adv__s"><h2>' . esc_html__( 'تماس', 'topersia-ads' ) . '</h2><p>' . esc_html__( 'برای جایگاه آزاد، زمان‌بندی و پیشنهاد با ما تماس بگیرید.', 'topersia-ads' ) . '</p>'
			. ( $href ? '<p><a class="tp-adv__cta" href="' . esc_url( $href ) . '">' . esc_html__( 'تماس برای تبلیغ', 'topersia-ads' ) . '</a></p>' : '' )
			. '</section>';

		return $html . '</div>';
	}
}

add_action( 'init', function () {
	add_shortcode( 'tp_advertise', 'topersia_ads_sc_advertise' );
} );
