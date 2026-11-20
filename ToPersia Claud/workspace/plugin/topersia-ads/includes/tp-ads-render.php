<?php
/**
 * نمایش جایگاه‌ها — دستۀ 54 بند ۵ تا ۸ (P5-D622 · D623 · D624 · D625).
 *
 * render فقط قلاب‌ها را صدا می‌زند؛ این‌جا تصمیم گرفته می‌شود:
 *   topersia_ad_slot (slot, i)       خانه، هاب، خبر و مقاله ← HTML یک جایگاه یا تهی
 *   topersia_ad_feed (cards, slot)   کارت میان فهرست هاب، پس از ۴ کارت و سپس هر ۸
 *   topersia_g_layout                صفحۀ مقصد: entity-mid در ستون اصلی، entity-rail در ستون کناری
 *   topersia_g_footer_ads_pre        پاورقی ۱ تا ۴ با همان نشانه‌گذاری پیشین؛ بی تبلیغ واقعی ← رفتار پیشین render
 *
 * HTML میان همۀ بازدیدکننده‌ها یکی است (کش قطعه): برای دسکتاپ و موبایل جدا انتخاب می‌شود؛ اگر هر دو یک تبلیغ
 * شد یک واحد، وگرنه دو واحد با `tp-ad--desk` و `tp-ad--mob` که CSS یکی را پنهان می‌کند. بودجۀ تراکم ۳/۲ به ترتیب رندر.
 * جایگاه خالی برای بازدیدکننده هیچ HTML ندارد؛ برای دارندۀ توان `manage_tp_ads` نشانۀ نقطه‌چین «جایگاه خالی: X».
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'topersia_ads_lang' ) ) {
	/** زبان صفحه: fa · en · ar. */
	function topersia_ads_lang() {
		$l = function_exists( 'topersia_g_page_lang' ) ? (string) topersia_g_page_lang() : (string) apply_filters( 'wpml_current_language', 'fa' );
		return in_array( $l, array( 'fa', 'en', 'ar' ), true ) ? $l : 'fa';
	}
}

if ( ! function_exists( 'topersia_ads_original_id' ) ) {
	/** شناسۀ نسخۀ زبان پیش‌فرض یک برگه (WPML)؛ بی WPML همان. */
	function topersia_ads_original_id( $id ) {
		$def = apply_filters( 'wpml_default_language', null );
		$o   = is_string( $def ) && '' !== $def ? apply_filters( 'wpml_object_id', (int) $id, get_post_type( (int) $id ), true, $def ) : $id;
		return (int) ( $o ? $o : $id );
	}
}

if ( ! function_exists( 'topersia_ads_excluded' ) ) {
	/**
	 * صفحه‌هایی که هیچ تبلیغی ندارند، حتی پاورقی (P5-D623 و بند ۵): ۴۰۴، جست‌وجو، صفحه‌های حقوقی (شرایط و حریم خصوصی)،
	 * حساب کاربری. هیرو جایگاه ندارد (ثبت جایگاه‌ها).
	 */
	function topersia_ads_excluded() {
		if ( is_404() || is_search() ) {
			return true;
		}
		if ( ! is_page() ) {
			return false;
		}
		$id   = (int) get_queried_object_id();
		$orig = topersia_ads_original_id( $id );
		$slug = (string) get_post_field( 'post_name', $orig );

		if ( in_array( $slug, (array) apply_filters( 'topersia_ads_legal_slugs', array( 'terms', 'privacy-policy' ) ), true ) ) {
			return true;
		}
		if ( class_exists( 'ToPersia_Admin' ) && method_exists( 'ToPersia_Admin', 'account_page_id' ) ) {
			$acc = array( (int) ToPersia_Admin::account_page_id() );
			if ( method_exists( 'ToPersia_Admin', 'account_page_id_canonical' ) ) {
				$acc[] = (int) ToPersia_Admin::account_page_id_canonical();
			}
			if ( array_intersect( array( $id, $orig ), array_filter( $acc ) ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'topersia_ads_page' ) ) {
	/**
	 * نوع صفحۀ جاری: home · hub · single · entity · other (فقط پاورقی) · none (بی تبلیغ). یک بار در هر درخواست.
	 */
	function topersia_ads_page() {
		static $p = null;
		if ( null !== $p && did_action( 'wp' ) ) {
			return $p;
		}
		if ( is_admin() || is_feed() || is_robots() || is_embed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! did_action( 'wp' ) ) {
			return 'none';
		}
		if ( topersia_ads_excluded() ) {
			$p = 'none';
		} elseif ( is_front_page() ) {
			$p = 'home';
		} elseif ( is_singular( array( 'tpc_news', 'tpc_article' ) ) ) {
			$p = 'single';
		} elseif ( ( function_exists( 'topersia_g_hub_is' ) && topersia_g_hub_is() )
			|| ( function_exists( 'topersia_g_news_list_is' ) && topersia_g_news_list_is() )
			|| is_post_type_archive( array( 'tpc_news', 'tpc_article' ) ) ) {
			$p = 'hub';
		} elseif ( is_singular() && 0 === strpos( (string) get_post_type(), 'tp_' ) && 'tp_ad' !== get_post_type() ) {
			$p = 'entity';
		} else {
			$p = 'other';
		}
		return $p;
	}
}

if ( ! function_exists( 'topersia_ads_demo' ) ) {
	/** حالت پیش‌نمایش — تنها جایی که تبلیغ نمایشی دیده می‌شود (بند ۱۰). */
	function topersia_ads_demo() {
		return function_exists( 'topersia_demo_on' ) && topersia_demo_on();
	}
}

if ( ! function_exists( 'topersia_ads_state' ) ) {
	/** وضعیت یک صفحه: بودجۀ تراکم، شناسه‌های آمده، شمار واحدها (گزارش بند ۷). */
	function &topersia_ads_state() {
		if ( ! isset( $GLOBALS['tp_ads_state'] ) || ! is_array( $GLOBALS['tp_ads_state'] ) ) {
			$GLOBALS['tp_ads_state'] = array( 'budget' => topersia_ads_budget(), 'used' => array(), 'units' => array( 'd' => 0, 'm' => 0, 'footer' => 0 ), 'assets' => false );
		}
		return $GLOBALS['tp_ads_state'];
	}
}

if ( ! function_exists( 'topersia_ads_ctx' ) ) {
	function topersia_ads_ctx( $device, $turn = 0 ) {
		return array(
			'now'    => time(),
			'lang'   => topersia_ads_lang(),
			'page'   => topersia_ads_page(),
			'device' => $device,
			'demo'   => topersia_ads_demo(),
			'turn'   => (int) $turn,
		);
	}
}

if ( ! function_exists( 'topersia_ads_img' ) ) {
	/** [ src، پهنا، بلندی ] پیوست یا null. */
	function topersia_ads_img( $id ) {
		$s = $id ? wp_get_attachment_image_src( (int) $id, 'full' ) : false;
		return is_array( $s ) && ! empty( $s[0] ) ? array( (string) $s[0], (int) $s[1], (int) $s[2] ) : null;
	}
}

if ( ! function_exists( 'topersia_ads_go' ) ) {
	/** نشانی کلیک: /ad-go/{id}/ بی پیشوند زبان (یک مسیر، یک قاعدۀ robots) با جایگاه و زبان برای آمار. */
	function topersia_ads_go( $id, $slot, $lang ) {
		return untrailingslashit( (string) get_option( 'home' ) ) . '/ad-go/' . (int) $id . '/?s=' . rawurlencode( $slot ) . '&l=' . rawurlencode( $lang );
	}
}

if ( ! function_exists( 'topersia_ads_t' ) ) {
	/** متن‌های واحد به زبان صفحه. */
	function topersia_ads_t() {
		return array(
			'tag'  => __( 'تبلیغ', 'topersia-ads' ),
			'demo' => __( 'نمونۀ نمایشی', 'topersia-ads' ),
			'ph'   => __( 'جای تبلیغ', 'topersia-ads' ),
			'aria' => __( 'تبلیغ', 'topersia-ads' ),
		);
	}
}

if ( ! function_exists( 'topersia_ads_unit' ) ) {
	/** یک واحد از رکورد و دید (both · d · m). */
	function topersia_ads_unit( array $ad, $slot, $vis, $lang ) {
		$s     = topersia_ads_slots()[ $slot ];
		$img_d = null;
		$img_m = null;

		if ( ! $ad['demo'] ) {
			if ( 'm' === $vis ) {
				$img_d = topersia_ads_img( $ad['img'][ $s['m'] ] );
			} else {
				$img_d = topersia_ads_img( $ad['img'][ $s['d'] ] );
				if ( 'both' === $vis && '' !== $s['m'] && $s['m'] !== $s['d'] ) {
					$img_m = topersia_ads_img( $ad['img'][ $s['m'] ] );
				}
			}
			if ( ! $img_d ) {
				return '';
			}
		}

		return topersia_ads_unit_html( array(
			'ad'    => $ad,
			'slot'  => $slot,
			'vis'   => $vis,
			'lang'  => $lang,
			'href'  => topersia_ads_go( $ad['id'], $slot, $lang ),
			'img_d' => $img_d,
			'img_m' => $img_m,
			'lazy'  => 'hub-top' !== $slot,   /* بند ۸: تنها جایگاه نخستین صفحه‌نمایش (دسکتاپ) — بی lazy */
			't'     => topersia_ads_t(),
		) );
	}
}

if ( ! function_exists( 'topersia_ads_empty' ) ) {
	/** نشانۀ «جایگاه خالی» — فقط برای مدیر تبلیغ، هرگز برای بازدیدکننده (P5-D624). */
	function topersia_ads_empty( $slot ) {
		$s = topersia_ads_slots()[ $slot ];
		/* P5-D650 (دستۀ 56-الف): کارت میان‌فهرستی خالی — در پیش‌نمایش برای همه، در عمومی فقط مدیر وارد‌شده؛ نشانگر خط‌چین
		   «جایگاه تبلیغ · خالی» در همان خانه (ظرف tp-ad-slot تا موتور مکان‌یابی و شبکه آن را خانه بشمارند). عموم: هیچ HTML، هیچ فاصله. */
		if ( 'hub-infeed' === $slot ) {
			if ( ! topersia_ads_demo() && ! current_user_can( TOPERSIA_ADS_CAP ) ) {
				return '';
			}
			return '<div class="tp-ad-slot tp-ad-slot--hub-infeed tp-ad-slot--empty" data-nosnippet>'
				. '<div class="tp-ad-empty tp-ad-empty--hub-infeed" role="note">' . esc_html__( 'جایگاه تبلیغ · خالی', 'topersia-ads' ) . '</div></div>';
		}
		if ( ! current_user_can( TOPERSIA_ADS_CAP ) ) {
			return '';
		}
		/* translators: %s: نام جایگاه */
		return '<div class="tp-ad-empty tp-ad-empty--' . esc_attr( $slot ) . ( '' === $s['m'] ? ' tp-ad--desk' : '' ) . '" data-nosnippet role="note">'
			. esc_html( sprintf( __( 'جایگاه خالی: %s', 'topersia-ads' ), __( $s['name'], 'topersia-ads' ) ) ) . '</div>';   // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
	}
}

if ( ! function_exists( 'topersia_ads_slot_html' ) ) {
	/**
	 * HTML یک جایگاه (نوبت $i در همان صفحه). جایگاه نه از این نوع صفحه، صفحۀ بی تبلیغ یا بی بودجه ← تهی.
	 */
	function topersia_ads_slot_html( $slot, $i = 0 ) {

		$slots = topersia_ads_slots();
		$page  = topersia_ads_page();

		if ( ! isset( $slots[ $slot ] ) || 'footer' === $slot || 'none' === $page || $page !== $slots[ $slot ]['page'] || (int) $i >= $slots[ $slot ]['max'] ) {
			return '';
		}

		$st   = &topersia_ads_state();
		$s    = $slots[ $slot ];
		$lang = topersia_ads_lang();
		$all  = topersia_ads_all();
		$html = '';

		/* هر دو دستگاه یک تبلیغ ← یک واحد (هم‌رده‌ها شریک می‌شوند)؛ وگرنه هر کدام واحد خودش، هر کدام از بودجۀ همان دستگاه */
		$plan = topersia_ads_plan( $all, $slot, topersia_ads_ctx( 'desktop', $i ), topersia_ads_ctx( 'mobile', $i ), '' !== $s['m'], $st['used'] );

		foreach ( $plan as $row ) {
			/* P5-D641: میان‌فهرستی سقف خودش را دارد (۴) و از بودجۀ D623 نمی‌خورد (D639) */
			$vis = empty( $s['density'] ) ? $row[1] : topersia_ads_take( $st['budget'], $row[1] );
			if ( '' === $vis ) {
				continue;
			}
			$unit = topersia_ads_unit( $row[0], $slot, $vis, $lang );
			if ( '' === $unit ) {
				/* تصویر پیوست پاک شده — بودجه برمی‌گردد */
				if ( ! empty( $s['density'] ) ) {
					$st['budget']['d'] += 'm' === $vis ? 0 : 1;
					$st['budget']['m'] += 'd' === $vis ? 0 : 1;
				}
				continue;
			}
			$html               .= $unit;
			$st['used'][]        = (int) $row[0]['id'];
			$st['units']['d']   += 'm' === $vis ? 0 : 1;
			$st['units']['m']   += 'd' === $vis ? 0 : 1;
		}

		if ( '' === $html ) {
			return topersia_ads_empty( $slot );
		}

		$st['assets'] = true;

		return '<div class="tp-ad-slot tp-ad-slot--' . esc_attr( $slot ) . '" data-tp-adw="' . (int) topersia_ads_window( time() ) . '">' . $html . '</div>';
	}
}

add_filter( 'topersia_ad_slot', function ( $html, $slot, $i = 0 ) {
	return (string) $html . topersia_ads_slot_html( (string) $slot, (int) $i );
}, 10, 3 );

/* میان متن خبر و مقاله — فقط با ≥۳ بند سطح بالا؛ بودجه پیش از دانستن جای درج گرفته نمی‌شود */
add_filter( 'topersia_ad_inline', function ( $html, $slot = 'single-inline' ) {
	$html = (string) $html;
	if ( 'single' !== topersia_ads_page() || topersia_ads_inline_insert( $html, '<!--tp-ad-probe-->', 3 ) === $html ) {
		return $html;
	}
	return topersia_ads_inline_insert( $html, topersia_ads_slot_html( (string) $slot, 0 ), 3 );
}, 10, 2 );

add_filter( 'topersia_ad_feed', function ( $cards, $slot = 'hub-infeed' ) {
	$cards = array_values( (array) $cards );
	if ( 'hub' !== topersia_ads_page() || ! isset( topersia_ads_slots()[ $slot ] ) ) {
		return $cards;
	}
	$out = array();
	$pos = array_flip( topersia_ads_feed_positions( count( $cards ) ) );
	$n   = 0;
	foreach ( $cards as $k => $card ) {
		if ( isset( $pos[ $k ] ) ) {
			$unit = topersia_ads_slot_html( $slot, $n++ );
			if ( '' !== $unit ) {
				$out[] = $unit;
			}
		}
		$out[] = $card;
	}
	return $out;
}, 10, 2 );

if ( ! function_exists( 'topersia_ads_zone' ) ) {
	/** ناحیۀ صفحۀ مقصد (topersia_g_layout): atts slot. */
	function topersia_ads_zone( $atts = array() ) {
		return topersia_ads_slot_html( isset( $atts['slot'] ) ? (string) $atts['slot'] : '', 0 );
	}
}

add_filter( 'topersia_g_layout', function ( $layout, $post_id ) {
	if ( ! is_array( $layout ) || ! $post_id || 0 !== strpos( (string) get_post_type( $post_id ), 'tp_' ) ) {
		return $layout;
	}
	$mid  = array( 'fn' => 'topersia_ads_zone', 'id' => '', 'nav' => '', 'atts' => array( 'slot' => 'entity-mid' ) );
	$rail = array( 'fn' => 'topersia_ads_zone', 'id' => '', 'nav' => '', 'atts' => array( 'slot' => 'entity-rail' ) );

	/* میان ستون اصلی: پس از «جاذبه‌ها» اگر هست، وگرنه میانۀ ناحیه‌ها */
	$main = isset( $layout['main'] ) ? array_values( (array) $layout['main'] ) : array();
	$at   = (int) ceil( count( $main ) / 2 );
	foreach ( $main as $k => $z ) {
		if ( isset( $z['id'] ) && 'tp-attractions' === $z['id'] ) {
			$at = $k + 1;
			break;
		}
	}
	array_splice( $main, min( $at, count( $main ) ), 0, array( $mid ) );
	$layout['main'] = $main;

	/* ستون کناری: پس از دومین کارت (دسکتاپ؛ ترتیب دیداری با CSS) */
	$r = isset( $layout['rail'] ) ? array_values( (array) $layout['rail'] ) : array();
	array_splice( $r, min( 2, count( $r ) ), 0, array( $rail ) );
	$layout['rail'] = $r;

	return $layout;
}, 20, 2 );

if ( ! function_exists( 'topersia_ads_footer_html' ) ) {
	/**
	 * پاورقی از tp_ad با همان نشانه‌گذاری بخش پیشین (`tp-ft-ads--nN`، `a.tp-ft-ad`) — شمار ۱ تا ۴ پهنا را تعیین می‌کند.
	 * null ← تبلیغ واقعی پاورقی نیست؛ render رفتار پیشین را دارد (گزینه و جای نمونۀ پیش‌نمایش با demo_ads).
	 */
	function topersia_ads_footer_html() {

		if ( 'none' === topersia_ads_page() ) {
			return '';
		}

		$lang = topersia_ads_lang();
		$all  = array_filter( topersia_ads_all(), function ( $a ) {
			return ! $a['demo'];
		} );
		$pick = topersia_ads_pick( $all, 'footer', topersia_ads_ctx( 'desktop' ), topersia_ads_slots()['footer']['max'] );

		if ( ! $pick ) {
			return null;
		}

		$t     = topersia_ads_t();
		$items = '';
		$n     = 0;
		foreach ( $pick as $ad ) {
			$img = topersia_ads_img( $ad['img']['wide'] );
			if ( ! $img ) {
				continue;
			}
			$alt    = $ad['alt'][ $lang ];
			$items .= '<a class="tp-ft-ad" href="' . esc_url( topersia_ads_go( $ad['id'], 'footer', $lang ) ) . '" target="_blank" rel="sponsored noopener" data-nosnippet'
				. ' data-tp-ad="' . (int) $ad['id'] . '|footer|' . esc_attr( $lang ) . '" aria-label="' . esc_attr( $t['aria'] . ': ' . $alt ) . '">'
				. '<img class="tp-ft-ad__img" src="' . esc_url( $img[0] ) . '" width="' . (int) $img[1] . '" height="' . (int) $img[2] . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">'
				. '<span class="tp-ft-ad__tag">' . esc_html( $t['tag'] ) . '</span></a>';
			$n++;
		}
		if ( ! $n ) {
			return null;
		}

		$st                     = &topersia_ads_state();
		$st['units']['footer'] += $n;
		$st['assets']           = true;

		return '<section class="tp-ft tp-ft-ads tp-ft-ads--n' . $n . '" aria-label="' . esc_attr( $t['tag'] ) . '" data-tp-adw="' . (int) topersia_ads_window( time() ) . '">' . $items . '</section>';
	}
}

add_filter( 'topersia_g_footer_ads_pre', function ( $pre ) {
	if ( null !== $pre ) {
		return $pre;
	}
	$html = topersia_ads_footer_html();
	if ( null === $html && 'none' !== topersia_ads_page() && current_user_can( TOPERSIA_ADS_CAP ) && ! topersia_ads_demo() && ! ( function_exists( 'topersia_g_ft_ads' ) && topersia_g_ft_ads() ) ) {
		return topersia_ads_empty( 'footer' );
	}
	return $html;
} );

/*
 * شیوه‌نامه در head برای صفحه‌های جایگاه‌دار (پاورقی شیوه‌نامۀ خودِ render را دارد)؛ اسکریپت شمارش نمایش
 * (کمتر از ۲ کیلوبایت gzip، بی JS بیرونی) فقط وقتی واحدی آمد — پیش از چاپ اسکریپت‌های پایان صفحه.
 * صفحه‌ای که بعداً قطعۀ نتایج با واحد می‌گیرد (هاب) اسکریپت را از همان آغاز دارد.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( in_array( topersia_ads_page(), array( 'home', 'hub', 'single', 'entity' ), true ) ) {
		wp_enqueue_style( 'topersia-ads', topersia_ads_url( 'assets/tp-ads.css' ), array(), TOPERSIA_ADS_VERSION );
	}
}, 30 );

add_action( 'wp_footer', function () {
	$st = &topersia_ads_state();
	if ( ! $st['assets'] && 'hub' !== topersia_ads_page() ) {
		return;
	}
	wp_enqueue_script( 'topersia-ads', topersia_ads_url( 'assets/tp-ads.js' ), array(), TOPERSIA_ADS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script( 'topersia-ads', 'TopersiaAds', array(
		'u' => esc_url_raw( rest_url( 'topersia-ads/v1/imp' ) ),
		't' => topersia_ads_imp_token( (int) floor( time() / 86400 ), wp_salt( 'nonce' ) ),
	) );
	/* P5-D641 (دستۀ 56-الف بند ۳): بازچینی کارت میان‌فهرستی پس از پالایش، «نمایش بیشتر» و پنهان‌شدن کارت — پروندۀ جدا
	   تا شمارندۀ نمایش زیر بودجۀ ۲ کیلوبایت بماند؛ همان اعداد PHP */
	if ( 'hub' === topersia_ads_page() ) {
		wp_enqueue_script( 'topersia-ads-feed', topersia_ads_url( 'assets/tp-ads-feed.js' ), array(), TOPERSIA_ADS_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_localize_script( 'topersia-ads-feed', 'TopersiaAdsFeed', topersia_ads_feed_rule() );
	}
}, 5 );

/* نشانۀ شمار واحدها برای آزمون و گزارش بند ۷ — فقط برای مدیر تبلیغ، در یادداشت HTML پایان صفحه */
add_action( 'wp_footer', function () {
	if ( current_user_can( TOPERSIA_ADS_CAP ) ) {
		$st = &topersia_ads_state();
		echo "\n<!-- tp-ads page=" . esc_html( topersia_ads_page() ) . ' d=' . (int) $st['units']['d'] . ' m=' . (int) $st['units']['m'] . ' footer=' . (int) $st['units']['footer'] . " -->\n";
	}
}, 99 );
