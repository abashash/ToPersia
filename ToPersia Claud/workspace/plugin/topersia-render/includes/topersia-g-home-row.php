<?php
/**
 * ردیف سه‌ستونی صفحۀ اول — دستۀ 51 الحاقیۀ پ · P5-D560
 *
 *   (راست‌به‌چپ) رویدادها | سفرهای آماده | فصل‌ها — هر ستون پنل با سرفصل و «مشاهدۀ همه»؛ ستون فصل پهن‌تر (۱ : ۱ : ۱٫۵ — P5-D562).
 *   رویداد: فقط با event_start_date، نزدیک‌ترین آینده نخست، زیرعنوان بازۀ ماه.
 *   سفر: tp_route، زیرعنوان «برنامۀ سفر پیشنهادی».
 *   هر ردیف فهرست بندانگشتی دارد: عکس خود مورد ← عکس نخستین مکان مرتبط (مسیر راهنما) ← آیکون روی گرادیان توکن؛ هرگز نشانی بیرونی.
 *   فصل: چهار کارت بلند به ترتیب بهار، تابستان، پاییز، زمستان از آغاز سطر (P5-D564)، تصویر SVG خود افزونه
 *        (assets/home/seasons/، رنگ از توکن فصل)، پیوند فقط به /season/{فصل}/؛
 *        فصل بی مورد کم‌رنگ و بی پیوند؛ کمتر از دو فصل دارای مورد ← ستون پنهان.
 *   بیش از دو ستون خالی (هر سه) ← کل ردیف پنهان؛ ستون خالی رندر نمی‌شود و بقیه پهن می‌شوند.
 *
 * فهرست‌ها از topersia_g_home_real (دروازۀ انتشار + نمونۀ نمایشی فقط در پیش‌نمایش، P5-D548)؛ نشان نمونه با topersia_g_demo_badge.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_HOME_ROW_ITEMS' ) ) {
	define( 'TOPERSIA_HOME_ROW_ITEMS', 3 );        /* ردیف هر فهرست — P5-D562 */
}
if ( ! defined( 'TOPERSIA_HOME_PHOTOS_MAX' ) ) {
	define( 'TOPERSIA_HOME_PHOTOS_MAX', 12 );      /* عکس‌های «ایران در تصویر» — سه اسلاید چهارتایی (P5-D569) */
	define( 'TOPERSIA_HOME_PHOTOS_PER_OWNER', 3 ); /* دست‌بالا از هر موجودیت */
	define( 'TOPERSIA_HOME_EXP_MAX', 8 );          /* کارت‌های «کارها و تجربیات» — دو ردیف چهارتایی (P5-D563) */
}
if ( ! defined( 'TOPERSIA_HOME_SEASONS_MIN' ) ) {
	define( 'TOPERSIA_HOME_SEASONS_MIN', 2 );      /* فصل دارای مورد کمتر ← ستون فصل پنهان */
}

/** نوع‌های رویداد صفحۀ اول. */
if ( ! function_exists( 'topersia_g_home_event_types' ) ) {
	function topersia_g_home_event_types() {
		return array( 'tp_festival', 'tp_sports_event', 'tp_religious_event', 'tp_seasonal_event' );
	}
}

/** [ سال، ماه ] تقویم صفحه — fa خورشیدی، بقیه میلادی. */
if ( ! function_exists( 'topersia_g_home_ym' ) ) {
	function topersia_g_home_ym( $iso ) {
		$gy = (int) substr( $iso, 0, 4 );
		$gm = (int) substr( $iso, 5, 2 );
		if ( 'fa' !== topersia_g_page_lang() ) {
			return array( $gy, $gm );
		}
		list( $jy, $jm ) = topersia_g_jalali( $gy, $gm, (int) substr( $iso, 8, 2 ) );
		return array( $jy, $jm );
	}
}

/**
 * بازۀ ماه رویداد: «آذر ۱۴۰۵» · «آذر – دی ۱۴۰۵» · «اسفند ۱۴۰۵ – فروردین ۱۴۰۶» (میلادی در en/ar).
 *
 * @param string $start Y-m-d.
 * @param string $end   Y-m-d یا تهی.
 */
if ( ! function_exists( 'topersia_g_home_month_range' ) ) {
	function topersia_g_home_month_range( $start, $end = '' ) {

		if ( '' === $start ) {
			return '';
		}
		$names = 'fa' === topersia_g_page_lang()
			? array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' )
			: topersia_g_greg_months();

		list( $y1, $m1 ) = topersia_g_home_ym( $start );
		$a = $names[ $m1 - 1 ];

		if ( '' === $end || $end <= $start ) {
			return $a . ' ' . topersia_g_fa( $y1 );
		}
		list( $y2, $m2 ) = topersia_g_home_ym( $end );
		if ( $y1 === $y2 && $m1 === $m2 ) {
			return $a . ' ' . topersia_g_fa( $y1 );
		}
		if ( $y1 === $y2 ) {
			return $a . ' – ' . $names[ $m2 - 1 ] . ' ' . topersia_g_fa( $y1 );
		}
		return $a . ' ' . topersia_g_fa( $y1 ) . ' – ' . $names[ $m2 - 1 ] . ' ' . topersia_g_fa( $y2 );
	}
}

/** بندانگشتی ردیف: عکس خود مورد ← عکس نخستین مکان مرتبط ← آیکون روی گرادیان توکن (P5-D562). */
if ( ! function_exists( 'topersia_g_home_thumb' ) ) {
	function topersia_g_home_thumb( $id, $icon ) {

		$owner = $id;
		$att   = topersia_g_card_image( $id );

		if ( ! $att && function_exists( 'topersia_g_crumb_chain' ) ) {
			foreach ( (array) topersia_g_crumb_chain( $id ) as $place ) {
				$att = topersia_g_card_image( $place );
				if ( $att ) {
					$owner = $place;
					break;
				}
			}
		}

		$img = $att ? topersia_g_home_img( $att, $owner, 'medium', '96px', 'tp-home-event__img' ) : '';

		return '<span class="tp-home-event__thumb"' . ( '' !== $img ? '' : ' aria-hidden="true"' ) . '>' . ( '' !== $img ? $img : topersia_g_icon( $icon ) ) . '</span>';
	}
}

/** یک ردیف فهرست ستون: بندانگشتی، نام (+ نشان نمونه) و زیرعنوان. */
if ( ! function_exists( 'topersia_g_home_row_li' ) ) {
	function topersia_g_home_row_li( $id, $sub, $icon = 'cal' ) {
		$badge = function_exists( 'topersia_g_demo_badge' ) ? topersia_g_demo_badge( $id ) : '';
		return '<li><a class="tp-home-event" href="' . esc_url( get_permalink( $id ) ) . '">' . topersia_g_home_thumb( $id, $icon )
			. '<span class="tp-home-event__txt"><b>' . esc_html( get_the_title( $id ) ) . '</b>' . $badge
			. '<span class="tp-home-event__when">' . esc_html( $sub ) . '</span></span></a></li>';
	}
}

/** ستون رویدادها — فقط با تاریخ آغاز؛ پایان‌یافته نه؛ نزدیک‌ترین نخست. */
if ( ! function_exists( 'topersia_g_home_events_col' ) ) {
	function topersia_g_home_events_col() {

		$today  = function_exists( 'current_time' ) ? current_time( 'Y-m-d' ) : gmdate( 'Y-m-d' );
		$events = array();

		foreach ( topersia_g_home_real( topersia_g_home_event_types() ) as $id ) {
			$start = topersia_g_date_iso( topersia_g_raw( $id, 'event_start_date' ) );
			$end   = topersia_g_date_iso( topersia_g_raw( $id, 'event_end_date' ) );
			if ( '' === $start || ( '' !== $end ? $end : $start ) < $today ) {
				continue;
			}
			$events[ $id ] = array( $start, $end );
		}

		uasort( $events, function ( $a, $b ) {
			return strcmp( $a[0], $b[0] );
		} );

		$rows = '';
		foreach ( array_slice( $events, 0, TOPERSIA_HOME_ROW_ITEMS, true ) as $id => $d ) {
			$rows .= topersia_g_home_row_li( $id, topersia_g_home_month_range( $d[0], $d[1] ) );
		}
		if ( '' === $rows ) {
			return '';
		}

		$all = topersia_g_hub_link_for( 'tp_festival' );
		return topersia_g_home_section( 'tp-home-events', __( 'رویدادها و جشنواره‌ها', 'topersia' ), '<ul class="tp-home-events">' . $rows . '</ul>',
			'' !== $all ? array( $all, __( 'مشاهدۀ همه', 'topersia' ) ) : array(), 'tp-home-col' );
	}
}

/** ستون سفرهای آماده — مسیرهای گردشگری. */
if ( ! function_exists( 'topersia_g_home_trips_col' ) ) {
	function topersia_g_home_trips_col() {

		$rows = '';
		foreach ( topersia_g_home_real( array( 'tp_route' ), TOPERSIA_HOME_ROW_ITEMS ) as $id ) {
			$rows .= topersia_g_home_row_li( $id, __( 'برنامۀ سفر پیشنهادی', 'topersia' ), 'route' );
		}
		if ( '' === $rows ) {
			return '';
		}

		$all = topersia_g_hub_link_for( 'tp_route' );
		return topersia_g_home_section( 'tp-home-trips', __( 'سفرهای آماده', 'topersia' ), '<ul class="tp-home-events tp-home-trips">' . $rows . '</ul>',
			'' !== $all ? array( $all, __( 'مشاهدۀ همه', 'topersia' ) ) : array(), 'tp-home-col' );
	}
}

/**
 * شمار هر فصل از نوع‌های دارای best_season (هاب) — «تمام سال» در هر فصل.
 *
 * @return array فصل ← [ شمار, نوع ← شمار, نخستین مورد ] به ترتیب topersia_g_season_keys()
 */
if ( ! function_exists( 'topersia_g_home_season_stats' ) ) {
	function topersia_g_home_season_stats() {

		$out = array();
		foreach ( topersia_g_season_keys() as $s ) {
			$out[ $s ] = array( 0, array(), 0 );
		}

		$types = array();
		foreach ( topersia_g_hub_registry() as $type => $row ) {
			if ( '' !== $row['season'] ) {
				$types[] = $type;
			}
		}
		if ( ! $types ) {
			return $out;
		}

		foreach ( topersia_g_home_real( $types ) as $id ) {
			$type = get_post_type( $id );
			foreach ( array_keys( $out ) as $s ) {
				if ( ! topersia_g_hub_in_season( $id, $s ) ) {
					continue;
				}
				$out[ $s ][0]++;
				$out[ $s ][1][ $type ] = isset( $out[ $s ][1][ $type ] ) ? $out[ $s ][1][ $type ] + 1 : 1;
				if ( ! $out[ $s ][2] ) {
					$out[ $s ][2] = $id;
				}
			}
		}

		return $out;
	}
}

/** کلید فصل‌ها به ترتیب نمایش از آغاز سطر — بهار، تابستان، پاییز، زمستان (P5-D564). */
if ( ! function_exists( 'topersia_g_season_keys' ) ) {
	function topersia_g_season_keys() {
		return array( 'spring', 'summer', 'autumn', 'winter' );
	}
}

/** تصویر برداری یک فصل — پروندۀ خود افزونه، درون‌خطی تا رنگ از توکن فصل در هر سه حالت بیاید. */
if ( ! function_exists( 'topersia_g_season_art' ) ) {
	function topersia_g_season_art( $s ) {
		static $memo = array();
		if ( ! isset( $memo[ $s ] ) ) {
			$file       = dirname( __DIR__ ) . '/assets/home/seasons/' . $s . '.svg';
			$memo[ $s ] = ( in_array( $s, topersia_g_season_keys(), true ) && is_readable( $file ) ) ? trim( (string) file_get_contents( $file ) ) : '';
		}
		return $memo[ $s ];
	}
}

/** ستون فصل‌ها — چهار کارت بلند با تصویر SVG خود افزونه؛ پیوند فقط به صفحۀ همان فصل. */
if ( ! function_exists( 'topersia_g_home_seasons_col' ) ) {
	function topersia_g_home_seasons_col() {

		$stats = topersia_g_home_season_stats();
		$live  = count( array_filter( $stats, function ( $s ) {
			return $s[0] > 0;
		} ) );
		if ( $live < TOPERSIA_HOME_SEASONS_MIN ) {
			return '';
		}

		$cards = '';

		foreach ( $stats as $s => $st ) {

			$name = (string) topersia_g_glossary_label( '34', $s );
			$in   = '<span class="tp-home-season__media">' . topersia_g_season_art( $s ) . '</span><span class="tp-home-season__cap"><b>' . esc_html( $name ) . '</b>'
				. ( $st[0] ? '<small>' . esc_html( topersia_g_fa( $st[0] ) ) . '</small>' : '' ) . '</span>';
			$url  = ( $st[0] && function_exists( 'topersia_g_season_url' ) ) ? topersia_g_season_url( $s ) : '';

			$cards .= '<li>' . ( '' !== $url
				? '<a class="tp-home-season tp-home-season--' . esc_attr( $s ) . '" href="' . esc_url( $url ) . '">' . $in . '</a>'
				: '<span class="tp-home-season tp-home-season--' . esc_attr( $s ) . ' tp-is-empty" aria-disabled="true">' . $in . '</span>' ) . '</li>';
		}

		return topersia_g_home_section( 'tp-home-seasons', __( 'ایران در چهار فصل', 'topersia' ), '<ul class="tp-home-seasons">' . $cards . '</ul>', array(), 'tp-home-col' );
	}
}

/** کل ردیف — هر سه خالی ← هیچ. */
if ( ! function_exists( 'topersia_g_home_row3' ) ) {
	function topersia_g_home_row3() {

		$cols = array_filter( array( topersia_g_home_events_col(), topersia_g_home_trips_col(), topersia_g_home_seasons_col() ) );
		if ( ! $cols ) {
			return '';
		}
		return '<div class="tp-home-trio tp-home-row3 tp-home-lazy">' . implode( '', $cols ) . '</div>';
	}
}
