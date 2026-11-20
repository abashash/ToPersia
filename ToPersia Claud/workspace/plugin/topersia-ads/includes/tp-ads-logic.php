<?php
/**
 * منطق خالص تبلیغ — بی وردپرس، تا assert-54 بی بارکردن سایت بسنجد (دستۀ 54 · P5-D621…D625).
 *
 *   topersia_ads_slots()          ثبت جایگاه‌ها: نام، نسبت تصویر دسکتاپ/موبایل، نوع صفحه، سقف هم‌زمان
 *   topersia_ads_eligible()       فیلتر وضعیت · زمان · سقف · زبان · دستگاه · نوع صفحه · نمایشی
 *   topersia_ads_pick()           اولویت (اسپانسر انحصاری) سپس وزن؛ چرخش قطعی با بذر بازۀ ۱۰ دقیقه
 *   topersia_ads_take()           تراکم: ۳ واحد دسکتاپ، ۲ موبایل، به‌جز فوتر (P5-D623)
 *   topersia_ads_unit_html()      HTML یک واحد: نسبت ثابت، <picture>، sponsored، data-nosnippet، برچسب
 *   topersia_ads_is_bot() · topersia_ads_day_hash() · topersia_ads_click_key() · topersia_ads_imp_token() — شمارش بی‌نام
 *
 * هیچ تابعی این‌جا به پایگاه‌داده، کوکی یا IP خام نمی‌رسد؛ ورودی‌ها از لایۀ وردپرس می‌آیند.
 */

if ( ! defined( 'TOPERSIA_ADS_WINDOW' ) ) {
	define( 'TOPERSIA_ADS_WINDOW', 600 );         /* ثانیه — بازۀ چرخش قطعی (P5-D622) */
}
if ( ! defined( 'TOPERSIA_ADS_MAX_DESK' ) ) {
	define( 'TOPERSIA_ADS_MAX_DESK', 3 );         /* P5-D623 */
}
if ( ! defined( 'TOPERSIA_ADS_MAX_MOB' ) ) {
	define( 'TOPERSIA_ADS_MAX_MOB', 2 );          /* P5-D623 */
}
if ( ! defined( 'TOPERSIA_ADS_DUP_TTL' ) ) {
	define( 'TOPERSIA_ADS_DUP_TTL', 1800 );       /* کلیک تکراری ۳۰ دقیقه */
}
if ( ! defined( 'TOPERSIA_ADS_MOBILE_MAX' ) ) {
	define( 'TOPERSIA_ADS_MOBILE_MAX', 767 );     /* پهنای مرز موبایل (px) — همان مرز <picture> و CSS */
}

if ( ! function_exists( 'topersia_ads_ratios' ) ) {
	/** نسبت‌های تصویر: کلید ← [ نسبت CSS، پهنا، ارتفاع پیشنهادی ]. */
	function topersia_ads_ratios() {
		return array(
			'wide'  => array( '4 / 1', 1200, 300 ),
			'mwide' => array( '2 / 1', 800, 400 ),
			'rect'  => array( '6 / 5', 600, 500 ),
			'card'  => array( '4 / 3', 800, 600 ),
		);
	}
}

if ( ! function_exists( 'topersia_ads_slots' ) ) {
	/**
	 * ثبت جایگاه‌ها — تنها منبع. هیچ جایگاهی در هیرو، صفحۀ حقوقی، ۴۰۴، جست‌وجو و حساب کاربری نیست؛
	 * بی پنجرۀ تمام‌صفحه و بی چسبان موبایل (فقط single-rail روی دسکتاپ چسبان است).
	 *
	 *   d / m    کلید نسبت تصویر دسکتاپ و موبایل (m = '' ← فقط دسکتاپ)
	 *   page     نوع صفحه‌ای که جایگاه در آن است (footer: any)
	 *   max      حداکثر تبلیغ هم‌زمان در یک صفحه
	 *   density  در سقف تراکم شمرده می‌شود (فوتر نه)
	 */
	function topersia_ads_slots() {
		return array(
			'home-mid'      => array( 'name' => 'میان صفحۀ اول',          'd' => 'wide', 'm' => 'mwide', 'page' => 'home',   'max' => 2, 'density' => true ),
			'hub-top'       => array( 'name' => 'زیر نوار ابزار هاب',       'd' => 'wide', 'm' => '',      'page' => 'hub',    'max' => 1, 'density' => true ),
			'hub-infeed'    => array( 'name' => 'کارت میان فهرست هاب',      'd' => 'card', 'm' => 'card',  'page' => 'hub',    'max' => 4, 'density' => false ),
			'single-inline' => array( 'name' => 'میان متن خبر و مقاله',     'd' => 'wide', 'm' => 'mwide', 'page' => 'single', 'max' => 1, 'density' => true ),
			'single-rail'   => array( 'name' => 'ستون کناری خبر و مقاله',   'd' => 'rect', 'm' => '',      'page' => 'single', 'max' => 1, 'density' => true ),
			'single-end'    => array( 'name' => 'پیش از مرتبط‌ها',           'd' => 'wide', 'm' => 'mwide', 'page' => 'single', 'max' => 1, 'density' => true ),
			'entity-rail'   => array( 'name' => 'ستون کناری مقصد',          'd' => 'rect', 'm' => '',      'page' => 'entity', 'max' => 1, 'density' => true ),
			'entity-mid'    => array( 'name' => 'میان بخش‌های مقصد',        'd' => 'wide', 'm' => 'mwide', 'page' => 'entity', 'max' => 1, 'density' => true ),
			'footer'        => array( 'name' => 'پاورقی',                    'd' => 'wide', 'm' => 'wide',  'page' => 'any',    'max' => 4, 'density' => false ),
		);
	}
}

if ( ! function_exists( 'topersia_ads_page_types' ) ) {
	/** نوع صفحه‌های قابل انتخاب در فرم تبلیغ. */
	function topersia_ads_page_types() {
		return array( 'home', 'hub', 'entity', 'single' );
	}
}

if ( ! function_exists( 'topersia_ads_window' ) ) {
	/** شمارۀ بازۀ چرخش — HTML در یک بازه برای همه یکی است. */
	function topersia_ads_window( $now ) {
		return (int) floor( (int) $now / TOPERSIA_ADS_WINDOW );
	}
}

if ( ! function_exists( 'topersia_ads_norm' ) ) {
	/**
	 * رکورد خام (متا) ← شکل ثابت. هر مقدار ناشناس به پیش‌فرض امن می‌رود.
	 *
	 * @param array $r
	 * @return array
	 */
	function topersia_ads_norm( array $r ) {

		$slots = array_values( array_intersect( array_map( 'strval', (array) ( isset( $r['slots'] ) ? $r['slots'] : array() ) ), array_keys( topersia_ads_slots() ) ) );
		$langs = array_values( array_intersect( array_map( 'strval', (array) ( isset( $r['langs'] ) ? $r['langs'] : array( 'all' ) ) ), array( 'all', 'fa', 'en', 'ar' ) ) );
		$pages = array_values( array_intersect( array_map( 'strval', (array) ( isset( $r['pages'] ) ? $r['pages'] : array( 'all' ) ) ), array_merge( array( 'all' ), topersia_ads_page_types() ) ) );
		$dev   = isset( $r['device'] ) && in_array( $r['device'], array( 'all', 'desktop', 'mobile' ), true ) ? $r['device'] : 'all';
		$st    = isset( $r['status'] ) && in_array( $r['status'], array( 'active', 'paused', 'ended' ), true ) ? $r['status'] : 'paused';
		$img   = array();
		$alt   = array();

		foreach ( array_keys( topersia_ads_ratios() ) as $k ) {
			$img[ $k ] = isset( $r['img'][ $k ] ) ? max( 0, (int) $r['img'][ $k ] ) : 0;
		}
		foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
			$alt[ $l ] = isset( $r['alt'][ $l ] ) ? trim( (string) $r['alt'][ $l ] ) : '';
		}

		return array(
			'id'         => isset( $r['id'] ) ? (int) $r['id'] : 0,
			'title'      => isset( $r['title'] ) ? (string) $r['title'] : '',
			'advertiser' => isset( $r['advertiser'] ) ? (string) $r['advertiser'] : '',
			'url'        => isset( $r['url'] ) && preg_match( '#^https?://[^\s"\'<>]+$#i', (string) $r['url'] ) ? (string) $r['url'] : '',
			'slots'      => $slots,
			'img'        => $img,
			'alt'        => $alt,
			'langs'      => $langs ? $langs : array( 'all' ),
			'device'     => $dev,
			'pages'      => $pages ? $pages : array( 'all' ),
			'start'      => isset( $r['start'] ) ? max( 0, (int) $r['start'] ) : 0,
			'end'        => isset( $r['end'] ) ? max( 0, (int) $r['end'] ) : 0,
			'cap_imp'    => isset( $r['cap_imp'] ) ? max( 0, (int) $r['cap_imp'] ) : 0,
			'cap_clk'    => isset( $r['cap_clk'] ) ? max( 0, (int) $r['cap_clk'] ) : 0,
			'imp'        => isset( $r['imp'] ) ? max( 0, (int) $r['imp'] ) : 0,
			'clk'        => isset( $r['clk'] ) ? max( 0, (int) $r['clk'] ) : 0,
			'weight'     => isset( $r['weight'] ) ? min( 100, max( 1, (int) $r['weight'] ) ) : 10,
			'priority'   => isset( $r['priority'] ) ? min( 9, max( 0, (int) $r['priority'] ) ) : 0,
			'status'     => $st,
			'demo'       => ! empty( $r['demo'] ),
		);
	}
}

if ( ! function_exists( 'topersia_ads_ready' ) ) {
	/**
	 * تبلیغ واقعی برای این جایگاه و زبان آماده است؟ تصویر نسبت دسکتاپ (و موبایل اگر جایگاه دارد و دستگاه موبایل را می‌خواهد)
	 * و متن جایگزین همان زبان — الزامی. نمایشی تصویر ندارد و همیشه آماده است.
	 */
	function topersia_ads_ready( array $ad, $slot, $lang ) {

		$s = topersia_ads_slots();

		if ( ! isset( $s[ $slot ] ) ) {
			return false;
		}
		if ( $ad['demo'] ) {
			return true;
		}
		if ( '' === $ad['url'] || empty( $ad['img'][ $s[ $slot ]['d'] ] ) || '' === ( isset( $ad['alt'][ $lang ] ) ? $ad['alt'][ $lang ] : '' ) ) {
			return false;
		}
		if ( '' !== $s[ $slot ]['m'] && 'desktop' !== $ad['device'] && empty( $ad['img'][ $s[ $slot ]['m'] ] ) ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'topersia_ads_eligible' ) ) {
	/**
	 * @param array  $ad   خروجی topersia_ads_norm()
	 * @param string $slot شناسۀ جایگاه
	 * @param array  $ctx  now · lang · page · device (desktop|mobile) · demo (حالت پیش‌نمایش روشن)
	 */
	function topersia_ads_eligible( array $ad, $slot, array $ctx ) {

		$s    = topersia_ads_slots();
		$now  = (int) $ctx['now'];
		$lang = (string) $ctx['lang'];
		$page = (string) $ctx['page'];
		$dev  = isset( $ctx['device'] ) ? (string) $ctx['device'] : 'desktop';

		if ( ! isset( $s[ $slot ] ) || 'active' !== $ad['status'] || ! in_array( $slot, $ad['slots'], true ) ) {
			return false;
		}
		if ( $ad['demo'] && empty( $ctx['demo'] ) ) {
			return false;
		}
		if ( ( $ad['start'] && $now < $ad['start'] ) || ( $ad['end'] && $now >= $ad['end'] ) ) {
			return false;
		}
		if ( ( $ad['cap_imp'] && $ad['imp'] >= $ad['cap_imp'] ) || ( $ad['cap_clk'] && $ad['clk'] >= $ad['cap_clk'] ) ) {
			return false;
		}
		if ( ! in_array( 'all', $ad['langs'], true ) && ! in_array( $lang, $ad['langs'], true ) ) {
			return false;
		}
		if ( 'any' !== $s[ $slot ]['page'] && $page !== $s[ $slot ]['page'] ) {
			return false;
		}
		if ( ! in_array( 'all', $ad['pages'], true ) && ! in_array( $page, $ad['pages'], true ) ) {
			return false;
		}
		if ( 'all' !== $ad['device'] && $dev !== $ad['device'] ) {
			return false;
		}
		if ( 'mobile' === $dev && '' === $s[ $slot ]['m'] ) {
			return false;
		}
		return topersia_ads_ready( $ad, $slot, $lang );
	}
}

if ( ! function_exists( 'topersia_ads_pick' ) ) {
	/**
	 * تا $n تبلیغ متمایز برای جایگاه. واقعی بر نمایشی مقدم است؛ بالاترین اولویت حاضر بقیه را کنار می‌گذارد
	 * (اسپانسر انحصاری)؛ میان هم‌اولویت‌ها قرعۀ وزنی با بذر قطعی (بازۀ ۱۰ دقیقه · جایگاه · زبان · نوع صفحه · دستگاه · نوبت).
	 *
	 * @param array  $ads     رکوردهای نرمال
	 * @param string $slot
	 * @param array  $ctx
	 * @param int    $n
	 * @param int[]  $exclude شناسه‌هایی که در همین صفحه آمده‌اند
	 * @return array[]
	 */
	function topersia_ads_pick( array $ads, $slot, array $ctx, $n = 1, array $exclude = array() ) {

		$cand = array();

		foreach ( $ads as $ad ) {
			if ( ! in_array( (int) $ad['id'], $exclude, true ) && topersia_ads_eligible( $ad, $slot, $ctx ) ) {
				$cand[ (int) $ad['id'] ] = $ad;
			}
		}

		$real = array_filter( $cand, function ( $a ) {
			return ! $a['demo'];
		} );
		$cand = $real ? $real : $cand;

		if ( ! $cand ) {
			return array();
		}

		$top  = max( array_map( function ( $a ) {
			return $a['priority'];
		}, $cand ) );
		$cand = array_filter( $cand, function ( $a ) use ( $top ) {
			return $a['priority'] === $top;
		} );
		ksort( $cand );

		$seed = topersia_ads_window( $ctx['now'] ) . '|' . $slot . '|' . $ctx['lang'] . '|' . $ctx['page'] . '|' . ( isset( $ctx['device'] ) ? $ctx['device'] : '' ) . '|' . ( isset( $ctx['turn'] ) ? (int) $ctx['turn'] : 0 );
		$out  = array();

		for ( $k = 0; $k < $n && $cand; $k++ ) {
			$total = array_sum( array_map( function ( $a ) {
				return $a['weight'];
			}, $cand ) );
			$r     = (int) ( sprintf( '%u', crc32( $seed . '|' . $k ) ) % $total );
			foreach ( $cand as $id => $a ) {
				$r -= $a['weight'];
				if ( $r < 0 ) {
					$out[] = $a;
					unset( $cand[ $id ] );
					break;
				}
			}
		}

		return $out;
	}
}

if ( ! function_exists( 'topersia_ads_plan' ) ) {
	/**
	 * نوبت $i یک جایگاه برای دو دستگاه: [ [رکورد، both · d · m], … ]. بذر چرخش دستگاه را دارد، پس دسکتاپ و موبایل
	 * از یک ردۀ هم‌سان (همان اولویت، هر دو واقعی یا هر دو نمایشی) دو تبلیغ متفاوت می‌گرفتند، هر دو مصرف می‌شد و نوبت بعدی
	 * همان جایگاه بی تبلیغ می‌ماند (کارت دوم میان فهرست هاب ۳۹۰، بسته به بازۀ ۱۰ دقیقه). حالا برگزیدۀ دسکتاپ اگر
	 * برای موبایل هم واجد و هم‌رده است، یک واحد برای هر دو می‌شود؛ ردۀ بالاتر موبایل (مثلاً اسپانسر فقط‌موبایل) برنده می‌ماند.
	 *
	 * @param bool  $mobile جایگاه نسخۀ موبایل دارد
	 * @param int[] $used   شناسه‌های همین صفحه
	 */
	function topersia_ads_plan( array $all, $slot, array $ctx_d, array $ctx_m, $mobile, array $used ) {

		$pd = topersia_ads_pick( $all, $slot, $ctx_d, 1, $used );
		$pm = $mobile ? topersia_ads_pick( $all, $slot, $ctx_m, 1, $used ) : array();

		if ( $pd && $pm && $pd[0]['id'] !== $pm[0]['id'] && $pd[0]['priority'] === $pm[0]['priority'] && $pd[0]['demo'] === $pm[0]['demo']
			&& topersia_ads_eligible( $pd[0], $slot, $ctx_m ) ) {
			$pm = $pd;
		}
		if ( $pd && $pm && $pd[0]['id'] === $pm[0]['id'] ) {
			return array( array( $pd[0], 'both' ) );
		}
		return array_merge( $pd ? array( array( $pd[0], 'd' ) ) : array(), $pm ? array( array( $pm[0], 'm' ) ) : array() );
	}
}

if ( ! function_exists( 'topersia_ads_budget' ) ) {
	/** بودجۀ تازۀ تراکم یک صفحه. */
	function topersia_ads_budget() {
		return array( 'd' => TOPERSIA_ADS_MAX_DESK, 'm' => TOPERSIA_ADS_MAX_MOB );
	}
}

if ( ! function_exists( 'topersia_ads_take' ) ) {
	/**
	 * گرفتن یک واحد از بودجه. $want: both · d · m. خروجی: آنچه واقعاً دیده می‌شود (both · d · m) یا تهی.
	 * واحدی که موبایل جایش را ندارد فقط دسکتاپ می‌شود، و برعکس — همان HTML، با کلاس پنهان‌کننده.
	 */
	function topersia_ads_take( array &$b, $want ) {

		$d = ( 'both' === $want || 'd' === $want ) && $b['d'] > 0;
		$m = ( 'both' === $want || 'm' === $want ) && $b['m'] > 0;

		if ( $d ) {
			$b['d']--;
		}
		if ( $m ) {
			$b['m']--;
		}
		return $d && $m ? 'both' : ( $d ? 'd' : ( $m ? 'm' : '' ) );
	}
}

if ( ! function_exists( 'topersia_ads_is_bot' ) ) {
	/** کارگزار کاربر ربات است؟ تهی هم ربات شمرده می‌شود. */
	function topersia_ads_is_bot( $ua ) {
		$ua = trim( (string) $ua );
		return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|embedly|quora link|outbrain|pinterest|vkshare|whatsapp|telegram|skype|preview|headless|lighthouse|pagespeed|gtmetrix|curl|wget|python|httpclient|okhttp|java\/|go-http|axios|node-fetch|scrapy|phantom|selenium|playwright|puppeteer/i', $ua );
	}
}

if ( ! function_exists( 'topersia_ads_day_hash' ) ) {
	/** هش روزانۀ بی‌نام IP+UA — IP خام هرگز نگه داشته نمی‌شود؛ نمک هر روز عوض می‌شود. */
	function topersia_ads_day_hash( $ip, $ua, $day, $salt ) {
		return hash_hmac( 'sha256', (string) $ip . '|' . (string) $ua . '|' . (int) $day, (string) $salt );
	}
}

if ( ! function_exists( 'topersia_ads_click_key' ) ) {
	/** کلید transient حذف کلیک تکراری (۳۰ دقیقه) — فقط از هش روزانه و شناسۀ تبلیغ. */
	function topersia_ads_click_key( $hash, $ad_id ) {
		return 'tp_adc_' . substr( md5( (string) $hash . '|' . (int) $ad_id ), 0, 24 );
	}
}

if ( ! function_exists( 'topersia_ads_imp_token' ) ) {
	/** توکن روزانۀ ارسال نمایش — در HTML کش‌شده هم تا دو روز معتبر. */
	function topersia_ads_imp_token( $day, $salt ) {
		return substr( hash_hmac( 'sha256', 'tp-ads-imp|' . (int) $day, (string) $salt ), 0, 20 );
	}
}

if ( ! function_exists( 'topersia_ads_token_ok' ) ) {
	/** توکن امروز، دیروز یا پریروز. */
	function topersia_ads_token_ok( $token, $now, $salt ) {
		$day = (int) floor( (int) $now / 86400 );
		foreach ( array( 0, 1, 2 ) as $back ) {
			if ( hash_equals( topersia_ads_imp_token( $day - $back, $salt ), (string) $token ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'topersia_ads_inline_insert' ) ) {
	/**
	 * $unit را پس از پاراگراف $after‌ام متن می‌گذارد؛ با کمتر از $after پاراگراف هیچ (تبلیغ ته متن کوتاه نمی‌نشیند).
	 * فقط `</p>` سطح بالا شمرده می‌شود — پاراگرافِ درون نقل‌قول، جدول، فهرست یا شکل نه.
	 */
	function topersia_ads_inline_insert( $html, $unit, $after = 3 ) {

		$html = (string) $html;

		if ( '' === (string) $unit || ! preg_match_all( '#</p>#i', $html, $m, PREG_OFFSET_CAPTURE ) ) {
			return $html;
		}

		$n = 0;
		foreach ( $m[0] as $hit ) {
			$pos    = $hit[1];
			$before = substr( $html, 0, $pos );
			$open   = preg_match_all( '#<(blockquote|table|ul|ol|figure|aside|div)\b#i', $before ) - preg_match_all( '#</(blockquote|table|ul|ol|figure|aside|div)>#i', $before );
			if ( $open > 0 ) {
				continue;
			}
			if ( ++$n === (int) $after ) {
				$at = $pos + 4;
				return substr( $html, 0, $at ) . $unit . substr( $html, $at );
			}
		}
		return $html;
	}
}

if ( ! function_exists( 'topersia_ads_feed_positions' ) ) {
	/**
	 * موتور مکان‌یابی میان‌فهرستی — P5-D641 (دستۀ 56-الف بند ۳؛ D639 را در اعداد جایگزین می‌کند).
	 * آگهی در خانه‌های ۶، ۱۸، ۳۰، ۴۲ (شمارش از ۱، آگهی‌ها هم خانه‌اند؛ دورۀ ۱۲ هم‌تراز با ۲/۳/۴ ستون)؛
	 * سقف ۴ در هر صفحه؛ هرگز آخرین خانه (پس از هر آگهی دست‌کم یک کارت)؛ صفحۀ زیر ۶ کارت بی آگهی.
	 * خروجی: شمارۀ کارتی (از ۰) که آگهی پیش از آن می‌نشیند. tp-ads.js همین قاعده را روی کارت‌های دیده‌شده تکرار می‌کند
	 * (assets/tp-ads-feed.js، TopersiaAdsFeed از topersia_ads_feed_rule()) — قاعدۀ دوم ننویس.
	 */
	function topersia_ads_feed_positions( $count ) {
		$rule = topersia_ads_feed_rule();
		$out  = array();
		if ( (int) $count < $rule['min'] ) {
			return $out;
		}
		foreach ( $rule['cells'] as $m => $cell ) {
			$before = (int) $cell - 1 - $m;   /* کارت‌های پیش از این خانه */
			if ( $before >= (int) $count ) {
				break;
			}
			$out[] = $before;
		}
		return $out;
	}
}

if ( ! function_exists( 'topersia_ads_feed_rule' ) ) {
	/** تنها منبع اعداد P5-D641 — PHP و tp-ads-feed.js (TopersiaAdsFeed) هر دو از همین. */
	function topersia_ads_feed_rule() {
		return array( 'cells' => array( 6, 18, 30, 42 ), 'min' => 6 );
	}
}

if ( ! function_exists( 'topersia_ads_attr' ) ) {
	/** گریز ویژگی HTML — با وردپرس esc_attr، بی آن همان قاعده. */
	function topersia_ads_attr( $s ) {
		return function_exists( 'esc_attr' ) ? esc_attr( (string) $s ) : htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'topersia_ads_unit_html' ) ) {
	/**
	 * HTML یک واحد. $u:
	 *   ad (رکورد نرمال) · slot · vis (both|d|m) · lang · href · img_d [src,w,h] · img_m [src,w,h]|null
	 *   lazy (bool) · t [ tag، demo، ph، aria ] (متن ترجمه‌شده)
	 * نسبت از CSS جایگاه (`--tp-ad-r`) و ابعاد صریح img — بی CLS. هیچ‌جا گرادیان و سایه نیست (tp-ads.css).
	 */
	function topersia_ads_unit_html( array $u ) {

		$ad   = $u['ad'];
		$slot = $u['slot'];
		$s    = topersia_ads_slots();
		$cls  = 'tp-ad tp-ad--' . $slot . ' tp-ad--r-' . $s[ $slot ]['d'] . ( '' !== $s[ $slot ]['m'] ? ' tp-ad--rm-' . $s[ $slot ]['m'] : '' )
			. ( 'd' === $u['vis'] ? ' tp-ad--desk' : ( 'm' === $u['vis'] ? ' tp-ad--mob' : '' ) );
		$t    = $u['t'];
		$tag  = '<span class="tp-ad__tag">' . topersia_ads_attr( $ad['demo'] ? $t['demo'] : $t['tag'] ) . '</span>';

		if ( $ad['demo'] ) {
			return '<div class="' . $cls . ' tp-ad--demo" data-nosnippet role="note" aria-label="' . topersia_ads_attr( $t['demo'] ) . '">'
				. '<div class="tp-ad__a tp-ad__ph"><span>' . topersia_ads_attr( $t['ph'] ) . '</span></div>' . $tag . '</div>';
		}

		$d    = $u['img_d'];
		$m    = isset( $u['img_m'] ) ? $u['img_m'] : null;
		$alt  = $ad['alt'][ $u['lang'] ];
		$load = ! empty( $u['lazy'] ) ? ' loading="lazy"' : ' fetchpriority="low"';
		$pic  = '<picture>'
			. ( $m ? '<source media="(max-width: ' . TOPERSIA_ADS_MOBILE_MAX . 'px)" srcset="' . topersia_ads_attr( $m[0] ) . '" width="' . (int) $m[1] . '" height="' . (int) $m[2] . '">' : '' )
			. '<img class="tp-ad__img" src="' . topersia_ads_attr( $d[0] ) . '" width="' . (int) $d[1] . '" height="' . (int) $d[2] . '" alt="' . topersia_ads_attr( $alt ) . '"' . $load . ' decoding="async">'
			. '</picture>';

		return '<div class="' . $cls . '" data-nosnippet data-tp-ad="' . (int) $ad['id'] . '|' . topersia_ads_attr( $slot ) . '|' . topersia_ads_attr( $u['lang'] ) . '">'
			. '<a class="tp-ad__a" href="' . topersia_ads_attr( $u['href'] ) . '" target="_blank" rel="sponsored noopener" aria-label="' . topersia_ads_attr( $t['aria'] . ': ' . $alt ) . '">' . $pic . '</a>'
			. $tag . '</div>';
	}
}
