<?php
/**
 * ToPersia Core — مختصات: یک تابع مرکزی برای هوا و نقشه  (۲ه٫۵ · P5-D314)
 *
 *   topersia_geo_point( $raw )      ← array( 'lat' => float, 'lng' => float ) یا null
 *   topersia_geo_post_point( $id )  ← همان، از فیلد `geo_coordinates` پست
 *   topersia_geo_attr( $raw )       ← «lat, lng» دهدهی برای data-attribute، یا ''
 *
 * قالب‌هایی که در داده هست پذیرفته می‌شوند:
 *   · دهدهی «32.6539, 51.6660»، با رقم فارسی یا عربی و ممیز «٫» هم
 *   · درجه-دقیقه-ثانیه «32°20′N, 52°47′E» · «32° 20' 15" N 52° 47' E»، حتی با جهت‌های جابه‌جا
 *   · JSON/GeoJSON: {"lat":..,"lng":..} · Point · LineString · Polygon · MultiPolygon ← مرکز هندسی
 *   · فهرست نقطه‌ها «lat,lng; lat,lng; …» ← مرکز هندسی شکل
 *
 * نامعتبر یا بیرون از ایران ← null: کارت هوا نمی‌آید و نقشه نقطه نمی‌گذارد (خطا هم نه).
 * هیچ نوشتنی ندارد؛ نتیجه را نگه نمی‌دارد.
 *
 * @package topersia-core
 */

defined( 'ABSPATH' ) || exit;

/** کادر ایران — همان کادر مسیر REST هوا (44 تا 63.5 شرقی، 25 تا 40.5 شمالی)، آب‌های خزر، خلیج فارس و دریای عمان هم. */
function topersia_geo_in_iran( $lat, $lng ) {
	return $lat >= 25.0 && $lat <= 40.5 && $lng >= 44.0 && $lng <= 63.5;
}

/** رقم فارسی و عربی به لاتین؛ ممیز «٫» به نقطه و ویرگول فارسی «،» به ویرگول. */
function topersia_geo_digits( $s ) {
	return strtr( (string) $s, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		'٫' => '.', '،' => ',',
	) );
}

/**
 * مرکز هندسی یک حلقه (فرمول ناحیه‌ای)؛ برای خط یا حلقۀ تباه، میانگین نقطه‌ها.
 *
 * @param array $pts نقطه‌ها، هر کدام [lng, lat]
 * @return array|null [lng, lat]
 */
function topersia_geo_centroid( array $pts ) {

	$n = count( $pts );

	if ( ! $n ) {
		return null;
	}

	$area = 0.0;
	$cx   = 0.0;
	$cy   = 0.0;

	for ( $i = 0; $i < $n; $i++ ) {
		list( $x0, $y0 ) = $pts[ $i ];
		list( $x1, $y1 ) = $pts[ ( $i + 1 ) % $n ];
		$f     = $x0 * $y1 - $x1 * $y0;
		$area += $f;
		$cx   += ( $x0 + $x1 ) * $f;
		$cy   += ( $y0 + $y1 ) * $f;
	}

	if ( abs( $area ) < 1e-12 ) {
		return array( array_sum( array_column( $pts, 0 ) ) / $n, array_sum( array_column( $pts, 1 ) ) / $n );
	}

	$area *= 0.5;

	return array( $cx / ( 6 * $area ), $cy / ( 6 * $area ) );
}

/**
 * یک مختصه: دهدهی یا درجه-دقیقه-ثانیه، با جهت اختیاری.
 *
 * @return array|null [ مقدار علامت‌دار, جهت N|S|E|W یا '' ]
 */
function topersia_geo_dms( $s ) {

	$ok = preg_match( '/^\s*(-?\d+(?:\.\d+)?)\s*(?:°|º|d)?\s*(?:(\d+(?:\.\d+)?)\s*(?:′|\'|’|m))?\s*(?:(\d+(?:\.\d+)?)\s*(?:″|"|”|\'\'|s))?\s*([NSEWnsew])?\s*$/u', $s, $m );

	if ( ! $ok ) {
		return null;
	}

	$value = abs( (float) $m[1] )
		+ ( isset( $m[2] ) && '' !== $m[2] ? (float) $m[2] / 60 : 0 )
		+ ( isset( $m[3] ) && '' !== $m[3] ? (float) $m[3] / 3600 : 0 );
	$dir   = isset( $m[4] ) ? strtoupper( $m[4] ) : '';
	$neg   = 0 === strpos( trim( $m[1] ), '-' ) || in_array( $dir, array( 'S', 'W' ), true );

	return array( $neg ? -$value : $value, $dir );
}

/**
 * @param mixed $raw مقدار خام geo_coordinates (رشته، آرایه یا JSON)
 * @return array|null array( 'lat' => float, 'lng' => float )
 */
function topersia_geo_point( $raw ) {

	$p = topersia_geo_parse( $raw );

	return 'valid' === $p['status'] ? array( 'lat' => $p['lat'], 'lng' => $p['lng'] ) : null;
}

/**
 * تجزیۀ کامل: وضعیت (valid · absent · outside · invalid)، نقطه و نوع شکل (point · polygon · line · list).  P5-D334
 *
 * absent = تهی؛ invalid = متنی که هیچ قالبی نمی‌خواندش؛ outside = خوانده شد ولی بیرون از ایران.
 *
 * @param mixed $raw مقدار خام geo_coordinates.
 * @return array status · kind · lat · lng
 */
function topersia_geo_parse( $raw ) {

	$none = array( 'status' => 'absent', 'kind' => '', 'lat' => null, 'lng' => null );

	if ( is_array( $raw ) || is_object( $raw ) ) {
		$raw = wp_json_encode( $raw );
	}

	$s = trim( topersia_geo_digits( wp_strip_all_tags( (string) $raw ) ) );

	if ( '' === $s ) {
		return $none;
	}

	$point = null;
	$kind  = 'point';

	if ( '{' === $s[0] || '[' === $s[0] ) {

		/* JSON: {"lat","lng"} یا GeoJSON */
		$j = json_decode( $s, true );

		if ( is_array( $j ) ) {

			if ( isset( $j['lat'] ) && ( isset( $j['lng'] ) || isset( $j['lon'] ) ) ) {
				$kind  = 'point';
				$point = array( (float) $j['lat'], (float) ( isset( $j['lng'] ) ? $j['lng'] : $j['lon'] ) );
			} else {

				$geo = isset( $j['geometry'] ) ? $j['geometry'] : $j;
				$c   = isset( $geo['coordinates'] ) ? $geo['coordinates'] : null;
				$t   = isset( $geo['type'] ) ? $geo['type'] : '';

				if ( 'Point' === $t && is_array( $c ) && count( $c ) >= 2 ) {
					$kind  = 'point';
					$point = array( (float) $c[1], (float) $c[0] );
				} elseif ( is_array( $c ) ) {

					$kind = ( false !== strpos( (string) $t, 'Polygon' ) ) ? 'polygon' : 'line';

					/* Polygon ← حلقۀ بیرونی؛ MultiPolygon ← نخستین چندضلعی؛ LineString ← خودش */
					while ( is_array( $c ) && isset( $c[0] ) && is_array( $c[0] ) && isset( $c[0][0] ) && is_array( $c[0][0] ) ) {
						$c = $c[0];
					}

					$pts = array();

					foreach ( (array) $c as $p ) {
						if ( is_array( $p ) && count( $p ) >= 2 ) {
							$pts[] = array( (float) $p[0], (float) $p[1] );
						}
					}

					$cc    = topersia_geo_centroid( $pts );
					$point = $cc ? array( $cc[1], $cc[0] ) : null;
				}
			}
		}

	} elseif ( false !== strpos( $s, ';' ) || ( substr_count( $s, ',' ) > 1 && false === strpos( $s, '°' ) ) ) {

		/* فهرست نقطه‌ها «lat,lng; lat,lng» ← مرکز هندسی شکل */
		$kind = 'list';
		$pts  = array();

		foreach ( preg_split( '/\s*;\s*|\s*\n\s*/', $s ) as $pair ) {

			$xy = array_map( 'trim', explode( ',', $pair ) );

			if ( 2 === count( $xy ) && is_numeric( $xy[0] ) && is_numeric( $xy[1] ) ) {
				$pts[] = array( (float) $xy[1], (float) $xy[0] );
			}
		}

		$cc    = count( $pts ) >= 2 ? topersia_geo_centroid( $pts ) : null;
		$point = $cc ? array( $cc[1], $cc[0] ) : null;

	} else {

		/* یک جفت: ویرگول، یا فاصله پس از حرف جهت («32°20′N 52°47′E») */
		$parts = preg_split( '/\s*,\s*|(?<=[NSns])\s+(?=-?\d)/u', $s );

		if ( 2 === count( $parts ) ) {

			$a = topersia_geo_dms( $parts[0] );
			$b = topersia_geo_dms( $parts[1] );

			if ( $a && $b ) {

				/* جهت‌ها جابه‌جا آمده: «52°47′E, 32°20′N» */
				if ( in_array( $a[1], array( 'E', 'W' ), true ) && in_array( $b[1], array( 'N', 'S' ), true ) ) {
					list( $a, $b ) = array( $b, $a );
				}

				$point = array( $a[0], $b[0] );
			}
		}
	}

	if ( ! $point || ! is_finite( $point[0] ) || ! is_finite( $point[1] ) ) {
		return array( 'status' => 'invalid', 'kind' => '', 'lat' => null, 'lng' => null );
	}

	if ( ! topersia_geo_in_iran( $point[0], $point[1] ) ) {
		return array( 'status' => 'outside', 'kind' => $kind, 'lat' => round( $point[0], 6 ), 'lng' => round( $point[1], 6 ) );
	}

	return array( 'status' => 'valid', 'kind' => $kind, 'lat' => round( $point[0], 6 ), 'lng' => round( $point[1], 6 ) );
}

/** نقطۀ یک پست از `geo_coordinates`. */
function topersia_geo_post_point( $post_id ) {
	return topersia_geo_point( get_post_meta( (int) $post_id, 'geo_coordinates', true ) );
}

/** «lat, lng» دهدهی برای data-attribute نقشه و هوا؛ نامعتبر ← ''. */
function topersia_geo_attr( $raw ) {

	$p = topersia_geo_point( $raw );

	return $p ? $p['lat'] . ', ' . $p['lng'] : '';
}
