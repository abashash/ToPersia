<?php
/**
 * ردیف سه‌ستونی صفحۀ اول — دستۀ 51 الحاقیۀ پ · P5-D560
 *
 *   (راست‌به‌چپ) رویدادها | سفرهای آماده | فصل‌ها — هر ستون پنل با سرفصل و «مشاهدۀ همه».
 *   رویداد: فقط با event_start_date، نزدیک‌ترین آینده نخست، زیرعنوان بازۀ ماه.
 *   سفر: tp_route، زیرعنوان «برنامۀ سفر پیشنهادی».
 *   فصل: چهار کارت بلند به ترتیب زمستان، پاییز، تابستان، بهار (از آغاز سطر)، پیوند به هاب با ?season=؛
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
	define( 'TOPERSIA_HOME_ROW_ITEMS', 4 );        /* ردیف هر فهرست و کارت فصل */
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

/** یک ردیف فهرست ستون: نام (+ نشان نمونه) و زیرعنوان. */
if ( ! function_exists( 'topersia_g_home_row_li' ) ) {
	function topersia_g_home_row_li( $id, $sub, $sub_html = '' ) {
		$badge = function_exists( 'topersia_g_demo_badge' ) ? topersia_g_demo_badge( $id ) : '';
		return '<li><a class="tp-home-event" href="' . esc_url( get_permalink( $id ) ) . '"><b>' . esc_html( get_the_title( $id ) ) . '</b>' . $badge
			. '<span class="tp-home-event__when">' . ( '' !== $sub_html ? $sub_html : esc_html( $sub ) ) . '</span></a></li>';
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
			$rows .= topersia_g_home_row_li( $id, __( 'برنامۀ سفر پیشنهادی', 'topersia' ) );
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
 * @return array فصل ← [ شمار, نوع ← شمار, نخستین مورد ]
 */
if ( ! function_exists( 'topersia_g_home_season_stats' ) ) {
	function topersia_g_home_season_stats() {

		$out = array();
		foreach ( array( 'winter', 'autumn', 'summer', 'spring' ) as $s ) {
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

/** ستون فصل‌ها — چهار کارت بلند؛ عکس از نخستین مورد واقعی، وگرنه گرادیان توکن فصل + آیکون. */
if ( ! function_exists( 'topersia_g_home_seasons_col' ) ) {
	function topersia_g_home_seasons_col() {

		$stats = topersia_g_home_season_stats();
		$live  = count( array_filter( $stats, function ( $s ) {
			return $s[0] > 0;
		} ) );
		if ( $live < TOPERSIA_HOME_SEASONS_MIN ) {
			return '';
		}

		$icons = array( 'winter' => 'cloud', 'autumn' => 'leaf', 'summer' => 'spark', 'spring' => 'tp-season' );
		$cards = '';

		foreach ( $stats as $s => $st ) {

			$name = (string) topersia_g_glossary_label( '34', $s );
			$att  = $st[2] ? topersia_g_card_image( $st[2] ) : 0;
			$art  = $att ? topersia_g_home_img( $att, $st[2], 'medium_large', '(max-width: 600px) 45vw, 160px', 'tp-home-season__img' )
				: '<span class="tp-home-season__ph" aria-hidden="true">' . topersia_g_icon( $icons[ $s ] ) . '</span>';
			$in   = '<span class="tp-home-season__media">' . $art . '</span><span class="tp-home-season__cap"><b>' . esc_html( $name ) . '</b>'
				. ( $st[0] ? '<small>' . esc_html( topersia_g_fa( $st[0] ) ) . '</small>' : '' ) . '</span>';

			if ( ! $st[0] ) {
				$cards .= '<li><span class="tp-home-season tp-home-season--' . esc_attr( $s ) . ' tp-is-empty" aria-disabled="true">' . $in . '</span></li>';
				continue;
			}
			arsort( $st[1] );
			$url    = topersia_g_hub_link_for( (string) key( $st[1] ), array( 'season' => $s ) );
			$cards .= '<li>' . ( '' !== $url
				? '<a class="tp-home-season tp-home-season--' . esc_attr( $s ) . '" href="' . esc_url( $url ) . '">' . $in . '</a>'
				: '<span class="tp-home-season tp-home-season--' . esc_attr( $s ) . '">' . $in . '</span>' ) . '</li>';
		}

		$all = topersia_g_hub_link_for( 'tp_seasonal_event' );
		return topersia_g_home_section( 'tp-home-seasons', __( 'ایران در چهار فصل', 'topersia' ), '<ul class="tp-home-seasons">' . $cards . '</ul>',
			'' !== $all ? array( $all, __( 'مشاهدۀ همه', 'topersia' ) ) : array(), 'tp-home-col' );
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
