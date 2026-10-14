<?php
/**
 * توابع خالص مرز OSM — P5-D363 · بی وردپرس، بی شبکه (آزمون‌پذیر آفلاین: tests/boundaries-unit.php)
 *
 * هر تابع فقط آرایه می‌گیرد و آرایه می‌دهد؛ scripts/tp-boundaries.php آن‌ها را به وردپرس و Overpass وصل می‌کند.
 *   • tpb_norm / tpb_key               نام و کلید پرونده
 *   • tpb_ring                         ساخت حلقه از راه‌های یک رابطه — با تشخیص علت شکست
 *   • tpb_dp / tpb_geojson             ساده‌سازی
 *   • tpb_point_in_geojson             نقطه در چندضلعی (حلقهٔ بیرونی؛ سوراخ ندارد)
 *   • tpb_candidates                   نامزدهای یک شهر از کش Overpass — فقط مرز اداری یا چندضلعی place=city|town
 *   • tpb_classify_city                auto · suggested · none
 *   • tpb_province_match               استان با ISO3166-2
 */
if ( ! defined( 'TPB_LIB' ) ) {
	define( 'TPB_LIB', 1 );

	function tpb_key( $uid ) {
		$path = (string) preg_replace( '~^https?://[^/]+/e/~', '', trim( (string) $uid ) );
		return preg_match( '~^[a-z0-9-]+(/[a-z0-9-]+)*$~', $path ) ? str_replace( '/', '__', $path ) : '';
	}

	function tpb_norm( $s ) {
		$s = str_replace( array( 'ي', 'ك', "\u{200C}", 'ۀ', 'ة' ), array( 'ی', 'ک', ' ', 'ه', 'ه' ), (string) $s );
		$s = preg_replace( '/^(استان|شهرستان|شهر|دریاچه|تالاب|کویر|رشته‌?کوه|جزیره|منطقه حفاظت شده|پارک ملی)\s+/u', '', trim( $s ) );
		return trim( preg_replace( '/\s+/u', ' ', $s ) );
	}

	/** نام فارسی یک عنصر OSM: name:fa، وگرنه name — هر دو نرمال. */
	function tpb_el_names( $el ) {
		$t = isset( $el['tags'] ) ? $el['tags'] : array();
		$o = array();
		foreach ( array( 'name:fa', 'name' ) as $k ) { if ( ! empty( $t[ $k ] ) ) { $o[] = tpb_norm( $t[ $k ] ); } }
		return array_values( array_unique( array_filter( $o ) ) );
	}

	/* ── حلقه‌ها ─────────────────────────────────────────────────────── */

	function tpb_pt_key( $p ) { return sprintf( '%.7f,%.7f', $p[0], $p[1] ); }

	/**
	 * راه‌های یک رابطه ← حلقه‌های بسته.
	 *
	 * پیش‌تر: هر راه فقط به راه بعدیِ با سرِ برابر وصل می‌شد، با جست‌وجوی خطی و بی نمایه، و حلقهٔ باز بی‌صدا دور ریخته می‌شد.
	 * اکنون: نمایه از سر و ته هر راه؛ هر راه در هر جهت؛ حلقهٔ باز با شکاف کمتر از $tol درجه (پیش‌فرض ≈ ۵۰ متر) بسته می‌شود؛
	 * بقیه علت می‌گیرند. $why: no_member_ways · short_way:N · open_ring:gap_m=… · dropped_ring:points=N
	 *
	 * @param array[] $ways آرایه‌ای از آرایهٔ نقطه‌های [lon, lat]
	 * @param array   $why  علت‌های شکست (خروجی)
	 * @return array[] حلقه‌ها (هر حلقه بسته: نخست = آخر)
	 */
	function tpb_ring( $ways, &$why = null, $tol = 0.0005 ) {
		$why   = array();
		$clean = array();
		foreach ( $ways as $w ) {
			if ( count( $w ) > 1 ) { $clean[] = array_values( $w ); } else { $why[ 'short_way' ] = isset( $why['short_way'] ) ? $why['short_way'] + 1 : 1; }
		}
		if ( ! $clean ) { $why[] = 'no_member_ways'; return array(); }

		$rings = array();
		$used  = array_fill( 0, count( $clean ), false );
		$ends  = array();
		foreach ( $clean as $i => $w ) { $ends[ tpb_pt_key( $w[0] ) ][] = $i; $ends[ tpb_pt_key( $w[ count( $w ) - 1 ] ) ][] = $i; }

		foreach ( $clean as $i => $w0 ) {
			if ( $used[ $i ] ) { continue; }
			$used[ $i ] = true;
			$ring = $w0;
			$guard = 0;
			while ( tpb_pt_key( $ring[0] ) !== tpb_pt_key( $ring[ count( $ring ) - 1 ] ) && $guard++ < 100000 ) {
				$tail = tpb_pt_key( $ring[ count( $ring ) - 1 ] );
				$next = null;
				foreach ( isset( $ends[ $tail ] ) ? $ends[ $tail ] : array() as $j ) { if ( ! $used[ $j ] ) { $next = $j; break; } }
				if ( null === $next ) { break; }
				$used[ $next ] = true;
				$w = $clean[ $next ];
				if ( tpb_pt_key( $w[0] ) !== $tail ) { $w = array_reverse( $w ); }
				$ring = array_merge( $ring, array_slice( $w, 1 ) );
			}
			$first = $ring[0]; $last = $ring[ count( $ring ) - 1 ];
			if ( tpb_pt_key( $first ) !== tpb_pt_key( $last ) ) {
				$gap = max( abs( $first[0] - $last[0] ), abs( $first[1] - $last[1] ) );
				if ( $gap <= $tol && count( $ring ) > 3 ) { $ring[] = $first; }
				else { $why[] = sprintf( 'open_ring:gap_m=%d:points=%d', (int) round( $gap * 111000 ), count( $ring ) ); continue; }
			}
			if ( count( $ring ) > 3 ) { $rings[] = $ring; } else { $why[] = 'dropped_ring:points=' . count( $ring ); }
		}
		return $rings;
	}

	/** نقطه‌های یک عنصر ← راه‌های [lon,lat]؛ عضو بی geometry شمرده و در $missing برمی‌گردد. */
	function tpb_element_ways( $el, &$missing = 0 ) {
		$missing = 0;
		$to = function ( $geom ) { return array_map( function ( $g ) { return array( $g['lon'], $g['lat'] ); }, $geom ); };
		if ( isset( $el['type'] ) && 'way' === $el['type'] ) {
			return ! empty( $el['geometry'] ) ? array( $to( $el['geometry'] ) ) : array();
		}
		$ways = array();
		foreach ( (array) ( isset( $el['members'] ) ? $el['members'] : array() ) as $m ) {
			if ( 'way' !== ( isset( $m['type'] ) ? $m['type'] : '' ) ) { continue; }
			$role = isset( $m['role'] ) ? $m['role'] : '';
			if ( 'inner' === $role ) { continue; }
			if ( empty( $m['geometry'] ) ) { $missing++; continue; }
			$ways[] = $to( $m['geometry'] );
		}
		return $ways;
	}

	function tpb_dp( $pts, $eps ) {
		$n = count( $pts );
		if ( $n < 3 ) { return $pts; }
		$keep = array_fill( 0, $n, false );
		$keep[0] = $keep[ $n - 1 ] = true;
		$stack = array( array( 0, $n - 1 ) );
		while ( $stack ) {
			list( $a, $b ) = array_pop( $stack );
			$dmax = 0; $idx = 0;
			$dx = $pts[ $b ][0] - $pts[ $a ][0]; $dy = $pts[ $b ][1] - $pts[ $a ][1]; $len = sqrt( $dx * $dx + $dy * $dy );
			for ( $i = $a + 1; $i < $b; $i++ ) {
				$d = $len > 0 ? abs( $dy * $pts[ $i ][0] - $dx * $pts[ $i ][1] + $pts[ $b ][0] * $pts[ $a ][1] - $pts[ $b ][1] * $pts[ $a ][0] ) / $len : sqrt( pow( $pts[ $i ][0] - $pts[ $a ][0], 2 ) + pow( $pts[ $i ][1] - $pts[ $a ][1], 2 ) );
				if ( $d > $dmax ) { $dmax = $d; $idx = $i; }
			}
			if ( $dmax > $eps ) { $keep[ $idx ] = true; $stack[] = array( $a, $idx ); $stack[] = array( $idx, $b ); }
		}
		$o = array();
		foreach ( $pts as $i => $p ) { if ( $keep[ $i ] ) { $o[] = $p; } }
		return $o;
	}

	function tpb_geojson( $rings, $limit = 50000 ) {
		$eps = 0.0005;
		for ( $k = 0; $k < 14; $k++ ) {
			$polys = array();
			foreach ( $rings as $r ) {
				$s = tpb_dp( $r, $eps );
				if ( count( $s ) >= 4 ) { $polys[] = array( array_map( function ( $p ) { return array( round( $p[0], 4 ), round( $p[1], 4 ) ); }, $s ) ); }
			}
			$js = json_encode( array( 'type' => 'Feature', 'properties' => new stdClass(), 'geometry' => array( 'type' => 'MultiPolygon', 'coordinates' => $polys ) ) );
			if ( strlen( $js ) <= $limit || 13 === $k ) { return array( $js, count( $polys ), $eps ); }
			$eps *= 1.7;
		}
	}

	/** نقطه (lon,lat) درون یکی از حلقه‌های بیرونی یک Feature(MultiPolygon) — پرتو. */
	function tpb_point_in_geojson( $lon, $lat, $feature ) {
		$g = is_string( $feature ) ? json_decode( $feature, true ) : $feature;
		if ( ! $g ) { return false; }
		$geom = isset( $g['geometry'] ) ? $g['geometry'] : $g;
		$polys = 'Polygon' === ( isset( $geom['type'] ) ? $geom['type'] : '' ) ? array( $geom['coordinates'] ) : ( isset( $geom['coordinates'] ) ? $geom['coordinates'] : array() );
		foreach ( $polys as $poly ) {
			$ring = $poly[0];
			$in = false;
			for ( $i = 0, $j = count( $ring ) - 1; $i < count( $ring ); $j = $i++ ) {
				if ( ( ( $ring[ $i ][1] > $lat ) !== ( $ring[ $j ][1] > $lat ) ) && ( $lon < ( $ring[ $j ][0] - $ring[ $i ][0] ) * ( $lat - $ring[ $i ][1] ) / ( $ring[ $j ][1] - $ring[ $i ][1] ) + $ring[ $i ][0] ) ) { $in = ! $in; }
			}
			if ( $in ) { return true; }
		}
		return false;
	}

	/* ── تطبیق ───────────────────────────────────────────────────────── */

	/**
	 * استان: ISO3166-2 ← عنصر admin_level=4 با همان برچسب؛ یکتا ← auto.
	 * کدِ بی عنصر (برچسب OSM گاه با فهرست رسمی نمی‌خواند) و نام داده شد ← تطبیق دقیق نام میان رابطه‌های admin_level=4 با ISO3166-2 ایرانی؛
	 * یکتا ← suggested (برای تأیید کاربر، نه auto)؛ وگرنه none.
	 */
	function tpb_province_match( $iso, $elements, $name = '' ) {
		$hit = array(); $lvl4 = array();
		foreach ( $elements as $e ) {
			$t = isset( $e['tags'] ) ? $e['tags'] : array();
			if ( 'relation' !== $e['type'] || '4' !== ( isset( $t['admin_level'] ) ? $t['admin_level'] : '' ) ) { continue; }
			if ( $iso === ( isset( $t['ISO3166-2'] ) ? $t['ISO3166-2'] : '' ) ) { $hit[] = $e; }
			if ( 0 === strpos( isset( $t['ISO3166-2'] ) ? $t['ISO3166-2'] : '', 'IR-' ) ) { $lvl4[] = $e; }
		}
		if ( 1 === count( $hit ) ) { return array( 'auto', $hit[0], 'ISO3166-2=' . $iso ); }
		if ( ! $hit && '' !== $name ) {
			$n = tpb_norm( $name ); $by = array();
			foreach ( $lvl4 as $e ) { if ( in_array( $n, tpb_el_names( $e ), true ) ) { $by[] = $e; } }
			if ( 1 === count( $by ) ) { return array( 'suggested', $by[0], 'ISO3166-2=' . $iso . ' not in OSM; exact-name match has ISO3166-2=' . $by[0]['tags']['ISO3166-2'] ); }
		}
		return array( 'none', null, 'ISO3166-2=' . $iso . ' (' . count( $hit ) . ' hits)' );
	}

	/** مرکز عنصر: out center ← center.lat/lon؛ نقطه ← lat/lon؛ نبود ← null. */
	function tpb_el_center( $e ) {
		if ( isset( $e['center'] ) ) { return array( (float) $e['center']['lon'], (float) $e['center']['lat'] ); }
		if ( isset( $e['lat'], $e['lon'] ) ) { return array( (float) $e['lon'], (float) $e['lat'] ); }
		return null;
	}

	/**
	 * نامزدهای یک شهر در کش: هم‌نام (name:fa یا name، نرمال)، و فقط
	 *   مرز اداری (boundary=administrative، admin_level جز ۴) یا چندضلعی place=city|town (relation یا way)؛
	 * هیچ عارضهٔ دیگری — بستر رودِ هم‌نام (waterway · natural=water) · نقطهٔ place · استان.
	 *
	 * @return array[] هر یک {el, class: admin|place, center}
	 */
	function tpb_candidates( $name, $elements ) {
		$n = tpb_norm( $name );
		$o = array();
		foreach ( $elements as $e ) {
			if ( ! in_array( $e['type'], array( 'relation', 'way' ), true ) || ! in_array( $n, tpb_el_names( $e ), true ) ) { continue; }
			$t   = isset( $e['tags'] ) ? $e['tags'] : array();
			$adm = 'administrative' === ( isset( $t['boundary'] ) ? $t['boundary'] : '' ) && '4' !== ( isset( $t['admin_level'] ) ? $t['admin_level'] : '' ) && 'relation' === $e['type'];
			$plc = in_array( isset( $t['place'] ) ? $t['place'] : '', array( 'city', 'town' ), true );
			if ( ! $adm && ! $plc ) { continue; }
			$c = tpb_el_center( $e );
			if ( ! $c ) { continue; }
			$o[] = array( 'el' => $e, 'class' => $adm ? 'admin' : 'place', 'center' => $c );
		}
		return $o;
	}

	/**
	 * شهر ← [وضعیت، عنصر، روش].
	 *   مرکز نامزد باید درون GeoJSON استان والد باشد؛ GeoJSON والد نبود ← فقط suggested (راستی‌آزمایی نشد)
	 *   یک نامزد ← auto · چند ← suggested · صفر ← none
	 *   گره‌گشا: اگر میان نامزدهای درون استان دقیقاً یکی مرز اداری است، همان؛ (چندضلعی place هم‌شهر با همان نام نامزد جدا نمی‌شود)
	 */
	function tpb_classify_city( $name, $elements, $parent_geojson ) {
		$cands = tpb_candidates( $name, $elements );
		if ( ! $cands ) { return array( 'none', null, 'no-candidate' ); }
		if ( ! $parent_geojson ) { return array( 'suggested', $cands[0]['el'], 'parent-province-geojson-missing (' . count( $cands ) . ' name matches)' ); }
		$in = array_values( array_filter( $cands, function ( $c ) use ( $parent_geojson ) { return tpb_point_in_geojson( $c['center'][0], $c['center'][1], $parent_geojson ); } ) );
		if ( ! $in ) { return array( 'none', null, 'no-candidate-inside-parent (' . count( $cands ) . ' elsewhere)' ); }
		if ( 1 === count( $in ) ) { return array( 'auto', $in[0]['el'], 'exact-name+' . $in[0]['class'] . '+inside-parent' ); }
		$adm = array_values( array_filter( $in, function ( $c ) { return 'admin' === $c['class']; } ) );
		if ( 1 === count( $adm ) ) { return array( 'auto', $adm[0]['el'], 'exact-name+admin+inside-parent (' . count( $in ) . ' in parent; one administrative)' ); }
		return array( 'suggested', $in[0]['el'], count( $in ) . ' candidates inside parent' );
	}
}
