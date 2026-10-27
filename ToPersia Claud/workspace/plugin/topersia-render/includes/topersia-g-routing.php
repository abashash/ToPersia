<?php
/**
 * باکس «مسیریابی» — دستۀ ۲ پرامت یکپارچه · P5-D350
 *
 * جایگزین محدودیت «فقط نقطه‌ای» (P5-D253): همۀ نوع‌ها جز کشور (ماتریس نوع × ناحیه: `tp-routing` برای کشور خاموش است).
 *   نوع نقطه‌ای (scope = point) با مختصات معتبرِ نقطه ← مقصد = مختصات.
 *   نوع پهنه‌ای (scope = area) یا هر نوعی که مختصات ندارد ← مقصد = نام فارسی + نام والد مکانی («تالاب گاوخونی، استان اصفهان»)
 *   تا اپ مقصد خودش مکان را پیدا کند — هرگز مرکز هندسی پهنه. بی مختصات و بی والد مکانی ← باکس نمی‌آید.
 * دکمه‌ها از `topersia_api( 'navigation' )` در مدیریت مرکزی سرویس‌ها می‌آیند (الگوی مختصات `pattern`، متن `text`)، نه ثابت در کد؛
 * ارائه‌دهنده‌ای که الگوی همان حالت را ندارد در آن حالت نمی‌آید. مبدأ = مکان دستگاه که اپ مقصد خودش می‌گیرد؛ سایت موقعیت
 * کاربر را نمی‌خواهد و نگه نمی‌دارد. آخرین انتخاب فقط در localStorage مرورگر (در JS) و آن دکمه اول. نشان‌واره نه، فقط نام.
 *
 * @package ToPersia_Render
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'TP_PLUGIN_DIR' ) ) {
	return;
}

/**
 * نام و پیشوند نوعِ «فارسی» برای متن مقصد — همیشه فارسی، نه زبان صفحه: اپ‌های مسیریابی نام فارسی را می‌شناسند.
 */
if ( ! function_exists( 'topersia_g_routing_fa_title' ) ) {
	function topersia_g_routing_fa_title( $post_id ) {

		$post_id = absint( $post_id );
		$name    = topersia_g_name_in( $post_id, 'fa' );

		if ( '' === $name ) {
			return '';
		}

		$type   = function_exists( 'get_post_type' ) ? (string) get_post_type( $post_id ) : '';
		$labels = topersia_g_kind_labels();
		$kind   = isset( $labels[ $type ] ) ? (string) $labels[ $type ] : '';

		if ( '' !== $kind && 0 !== strpos( $name, $kind ) ) {
			return $kind . ' ' . $name;
		}

		return $name;
	}
}

/**
 * نقطۀ نمایندۀ یک پست برای ارائه‌دهنده‌ای که فقط مختصات می‌پذیرد (نشان).  P5-D383
 *
 * نشان جست‌وجوی متنی ندارد (Neshan-MCP فقط پیوند نقطه دارد)، پس پهنه هم باید نقطه بدهد:
 *   نوع نقطه‌ای      مختصات خودش
 *   شهر              مختصات شهر (نوع نقطه‌ای است)
 *   استان            مختصات مرکز استان (رابطۀ ۹۶، `topersia_g_capital_id`)
 *   پهنۀ دیگر        مختصات نماینده اگر در `geo_coordinates` خودش نقطه است؛ وگرنه هیچ — بی نشان
 * هرگز مرکز هندسی پهنه حساب نمی‌شود.
 *
 * @return array{lat:float,lng:float}|array{}
 */
if ( ! function_exists( 'topersia_g_routing_point' ) ) {
	function topersia_g_routing_point( $pid ) {

		$pid = absint( $pid );

		if ( ! $pid || ! function_exists( 'topersia_geo_parse' ) ) {
			return array();
		}

		$from = $pid;

		if ( 'tp_province' === ( function_exists( 'get_post_type' ) ? get_post_type( $pid ) : '' ) ) {
			$from = function_exists( 'topersia_g_capital_id' ) ? absint( topersia_g_capital_id( $pid ) ) : 0;

			/*
			 * P5-D399 (دستۀ ۲۶ · F-06): مختصات استان = مختصات مرکز استان از ویکی‌داده. استانی که مختصاتش پس از تأیید
			 * کاربر با `tp-facts.php capitals-apply` نوشته شده (گزینۀ topersia_p5d399_applied) و رابطۀ مرکز ندارد،
			 * نقطۀ خودش را می‌گیرد. پیش از نوشتن، مختصات تقریبی استان هرگز مقصد مسیریابی نمی‌شود.
			 */
			if ( ! $from && in_array( $pid, array_map( 'absint', (array) ( function_exists( 'get_option' ) ? get_option( 'topersia_p5d399_applied', array() ) : array() ) ), true ) ) {
				$from = $pid;
			}
		}

		if ( ! $from ) {
			return array();
		}

		$geo = topersia_geo_parse( topersia_g_text( $from, 'geo_coordinates' ) );

		if ( is_array( $geo ) && isset( $geo['status'], $geo['kind'], $geo['lat'], $geo['lng'] ) && 'valid' === $geo['status'] && 'point' === $geo['kind'] ) {
			return array( 'lat' => (float) $geo['lat'], 'lng' => (float) $geo['lng'] );
		}

		return array();
	}
}

/**
 * مقصد این پست: `array( 'mode' => 'coords', 'lat' => , 'lng' => )` یا `array( 'mode' => 'text', 'q' => )` یا خالی (باکس نمی‌آید).
 *
 * @param int $pid پست.
 * @return array
 */
if ( ! function_exists( 'topersia_g_routing_target' ) ) {
	function topersia_g_routing_target( $pid ) {

		$pid   = absint( $pid );
		$scope = topersia_g_type_row( $pid )['scope'];

		if ( 'point' === $scope && function_exists( 'topersia_geo_parse' ) ) {

			$geo = topersia_geo_parse( topersia_g_text( $pid, 'geo_coordinates' ) );

			if ( is_array( $geo ) && isset( $geo['status'], $geo['kind'] ) && 'valid' === $geo['status'] && 'point' === $geo['kind'] && isset( $geo['lat'], $geo['lng'] ) ) {
				return array( 'mode' => 'coords', 'lat' => (float) $geo['lat'], 'lng' => (float) $geo['lng'] );
			}
		}

		/*
		 * P5-D455 (دستۀ ۳۱ الف): نوع «استان» مسیریابی ندارد — جعبه نمی‌آید (باطل‌کنندۀ P5-D453 و جعبۀ دستۀ ۲۸ برای استان).
		 * مختصات مرکز استان (P5-D399) در داده می‌ماند: نقشه و JSON-LD لازم دارند.
		 */
		if ( 'tp_province' === ( function_exists( 'get_post_type' ) ? (string) get_post_type( $pid ) : '' ) ) {
			return array();
		}

		/* پهنه‌ای یا بی مختصات: نام فارسی + نام والد مکانی (نزدیک‌ترین والد R01، بی کشور) */
		$self    = topersia_g_routing_fa_title( $pid );
		$parents = function_exists( 'topersia_g_crumb_places' ) ? topersia_g_crumb_places( $pid ) : array();
		$parent  = $parents ? topersia_g_routing_fa_title( end( $parents ) ) : '';

		$point = topersia_g_routing_point( $pid );

		if ( '' === $self || '' === $parent ) {
			/* بی متن: فقط ارائه‌دهندۀ مختصاتی (نشان) با نقطۀ نماینده، اگر هست.  P5-D383 */
			return $point ? array( 'mode' => 'text', 'q' => '', 'point' => $point ) : array();
		}

		/* Google و Waze همان متن (P5-D350)؛ نشان نقطۀ نماینده (P5-D383) */
		return array( 'mode' => 'text', 'q' => $self . '، ' . $parent, 'point' => $point );
	}
}

/** برچسب هر دکمه: فقط نام (بی نشان‌واره). */
if ( ! function_exists( 'topersia_g_routing_labels' ) ) {
	function topersia_g_routing_labels() {

		return array(
			'google' => __( 'نقشه گوگل', 'topersia' ),
			'waze'   => __( 'ویز', 'topersia' ),
			'neshan' => __( 'نشان', 'topersia' ),
			'balad'  => __( 'بلد', 'topersia' ),
		);
	}
}

/**
 * نشانی هر دکمه برای یک مقصد، از الگوی سرویس `navigation` — خالص (آزمون‌پذیر).
 *
 * @param array $extra  `extra` سرویس: کلید ارائه‌دهنده ← `on` · `pattern` · `text`.
 * @param array $target خروجی `topersia_g_routing_target`.
 * @return array<string,string> ارائه‌دهنده ← نشانی، فقط آنچه در این حالت الگو دارد و روشن است.
 */
if ( ! function_exists( 'topersia_g_routing_links' ) ) {
	function topersia_g_routing_links( $extra, $target ) {

		$out = array();

		if ( empty( $target['mode'] ) || ! is_array( $extra ) ) {
			return $out;
		}

		foreach ( $extra as $key => $row ) {

			if ( ! is_array( $row ) || empty( $row['on'] ) ) {
				continue;
			}

			if ( 'coords' === $target['mode'] ) {
				$pattern = isset( $row['pattern'] ) ? (string) $row['pattern'] : '';
				$url     = str_replace( array( '{lat}', '{lng}' ), array( rtrim( rtrim( sprintf( '%.7F', $target['lat'] ), '0' ), '.' ), rtrim( rtrim( sprintf( '%.7F', $target['lng'] ), '0' ), '.' ) ), $pattern );
			} else {
				$pattern = isset( $row['text'] ) ? (string) $row['text'] : '';
				$url     = '' !== (string) $target['q'] ? str_replace( '{q}', rawurlencode( (string) $target['q'] ), $pattern ) : '';

				/* ارائه‌دهنده‌ای که فقط مختصات می‌پذیرد (نشان) ← نقطۀ نماینده، اگر هست.  P5-D383 */
				if ( '' === $pattern && ! empty( $target['point'] ) && ! empty( $row['pattern'] ) ) {
					$pattern = (string) $row['pattern'];
					$url     = str_replace( array( '{lat}', '{lng}' ), array( rtrim( rtrim( sprintf( '%.7F', $target['point']['lat'] ), '0' ), '.' ), rtrim( rtrim( sprintf( '%.7F', $target['point']['lng'] ), '0' ), '.' ) ), $pattern );
				} elseif ( '' === $url ) {
					$pattern = '';
				}
			}

			if ( '' !== $pattern && 0 === strpos( $url, 'https://' ) ) {
				$out[ (string) $key ] = $url;
			}
		}

		return $out;
	}
}

/**
 * کلید فایل مرز از entity_uid: بخش پس از `/e/` (میزبان محلی و زنده فرق دارد، مسیر نه) با «/» ← «__» — `province/isfahan` ← `province__isfahan`.  P5-D351
 * همان قاعده در scripts/tp-boundaries.php.
 */
if ( ! function_exists( 'topersia_g_boundary_key' ) ) {
	function topersia_g_boundary_key( $uid ) {

		$path = (string) preg_replace( '~^https?://[^/]+/e/~', '', trim( (string) $uid ) );

		return preg_match( '~^[a-z0-9-]+(/[a-z0-9-]+)*$~', $path ) ? str_replace( '/', '__', $path ) : '';
	}
}

/** [tp_routing] — باکس «مسیریابی». */
if ( ! function_exists( 'topersia_g_sc_routing' ) ) {
	function topersia_g_sc_routing( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid || ! function_exists( 'topersia_api' ) ) {
			return '';
		}

		$svc = topersia_api( 'navigation' );

		if ( ! is_array( $svc ) || empty( $svc['enabled'] ) || empty( $svc['extra'] ) ) {
			return '';
		}

		$links = topersia_g_routing_links( $svc['extra'], topersia_g_routing_target( $pid ) );

		if ( ! $links ) {
			return '';
		}

		$labels = topersia_g_routing_labels();
		$tiles  = '';

		foreach ( $links as $key => $url ) {

			$tiles .= '<a class="tp-tile" href="' . esc_url( $url, array( 'https' ) ) . '" target="_blank" rel="noopener nofollow" data-tp-nav="' . esc_attr( $key ) . '">'
				. '<b>' . esc_html( isset( $labels[ $key ] ) ? $labels[ $key ] : $key ) . '</b></a>';
		}

		return topersia_g_section( 'tp-routing', __( 'مسیریابی', 'topersia' ), 'ROUTE',
			'<div class="tp-tiles" data-tp-tiles data-tp-nav-box>' . $tiles . '</div>' );
	}
}

if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'tp_routing', 'topersia_g_sc_routing' );
}
