<?php
/**
 * ماتریس «دقیقاً ۴ خانه» زیر سربرگ — P5-D390 (فقط خواندنی؛ فقط topersia.local)
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/stats4-matrix.php > /tmp/stats4.json
 *
 * برای هر نوع ماتریس: همهٔ پست‌های منتشرشدهٔ زبان پیش‌فرض، و برای هر خانهٔ نامزد پر یا خالی (با همان
 * `topersia_g_stat_cell()` رندر — طول و نوع و پرچم همان). نامزدها: خانه‌های فعلی (بی کد استان، P5-D386)،
 * هر فیلد ناحیهٔ card نوع (بی پرچم، مختصات و فصل)، و سه خانهٔ مشتق پیشنهادی که در رندر هنوز نیستند:
 *   parent:province · parent:county · parent:city  (والد R01 از آن نوع)   children  (شمار زیرمجموعهٔ R01، غیرصفر)
 *   distance:capital  (فاصلهٔ خط مستقیم تا مرکز استان، وقتی هر دو مختصات دارند)
 * تکراری ممنوع (پاسخ معمار ۲۰): نام نوع (در eyebrow هست) و بهترین فصل (جدول فصل‌ها دارد) نامزد نیستند.
 * ترتیب پیشنهادی: خانه‌های فعلی، سپس بقیهٔ نامزدها به ترتیب پرشدگی. خروجی JSON؛ هیچ چیز نوشته نمی‌شود.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$matrix = topersia_g_type_matrix();
$map    = topersia_g_map();
$out    = array();

$derived = function ( $pid, $key ) {
	if ( 'distance:capital' === $key ) {
		$me = topersia_geo_point( topersia_g_text( $pid, 'geo_coordinates' ) );
		if ( ! $me ) { return false; }
		foreach ( topersia_g_place_chain( $pid ) as $up ) {
			if ( 'tp_province' === get_post_type( $up ) ) {
				$cap = topersia_g_capital_id( $up );
				$cp  = $cap && (int) $cap !== (int) $pid ? topersia_geo_point( topersia_g_text( $cap, 'geo_coordinates' ) ) : null;
				return (bool) $cp;
			}
		}
		return false;
	}
	if ( 'children' === $key ) {
		/* شمار فرزندان مکانی R01 منتشرشده (مثلاً جاذبه‌ها و روستاهای یک شهر) */
		$n = 0;
		foreach ( topersia_g_relreg_sides( (string) get_post_type( $pid ) ) as $side ) {
			if ( 'children' === $side['dir'] && 'one_to_many' === $side['type'] ) {
				$n += count( topersia_g_rel_ids( $pid, array( array( 'id' => $side['id'], 'dir' => 'children' ) ) ) );
			}
		}
		return $n > 0;
	}
	if ( 'parent:county' === $key ) {
		foreach ( topersia_g_place_chain( $pid ) as $up ) {}
		foreach ( topersia_g_place_parents( $pid, 0, true ) as $up ) {
			if ( 'tp_county' === get_post_type( $up['id'] ) ) { return true; }
		}
		return false;
	}
	if ( 0 === strpos( $key, 'parent:' ) ) {
		$want = 'tp_' . substr( $key, 7 );
		foreach ( topersia_g_crumb_places( $pid ) as $up ) {
			if ( $want === get_post_type( $up ) && (int) $up !== (int) $pid ) {
				return true;
			}
		}
		return false;
	}
	return null;
};

foreach ( $matrix as $type => $row ) {

	$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false, 'lang' => apply_filters( 'wpml_default_language', 'fa' ) ) );

	$current = array_values( array_diff( (array) ( $row['stats'] ?? array() ), array( 'province_code' ) ) );
	$cands   = $current;

	foreach ( $map as $name => $f ) {
		if ( in_array( $name, $cands, true ) || 'card' !== $f['zone'] || 'flag' === $f['kind'] || in_array( $name, array( 'geo_coordinates', 'best_season', 'province_code' ), true ) ) {
			continue;
		}
		$cands[] = $name;
	}

	foreach ( array( 'parent:province', 'parent:county', 'parent:city', 'children', 'distance:capital' ) as $d ) {
		if ( ! in_array( $d, $cands, true ) ) {
			$cands[] = $d;
		}
	}

	$fill  = array_fill_keys( $cands, 0 );
	$posts = array();

	foreach ( $ids as $pid ) {
		$have = array();
		foreach ( $cands as $key ) {
			$d  = $derived( $pid, $key );
			$ok = null !== $d ? $d : null !== topersia_g_stat_cell( $pid, $key );
			if ( $ok ) {
				$have[] = $key;
				$fill[ $key ]++;
			}
		}
		$posts[ $pid ] = $have;
	}

	/* ترتیب: فعلی‌ها، سپس بقیه به ترتیب پرشدگی (بیشترین اول) */
	$rest = array_values( array_diff( $cands, $current ) );
	usort( $rest, function ( $a, $b ) use ( $fill ) { return $fill[ $b ] <=> $fill[ $a ]; } );
	$order = array_merge( $current, $rest );

	/* فقط آنچه دست‌کم یک پست را پر می‌کند (یا خانهٔ فعلی است) */
	$order = array_values( array_filter( $order, function ( $k ) use ( $fill, $current ) { return $fill[ $k ] > 0 || in_array( $k, $current, true ); } ) );

	$reach = 0; $short = array(); $first4 = array_slice( $order, 0, 4 );
	foreach ( $posts as $pid => $have ) {
		$got = array_values( array_intersect( $order, $have ) );
		if ( count( array_intersect( $first4, $have ) ) < 4 && count( $got ) >= 4 ) {
			$reach++;
		}
		if ( count( $got ) < 4 ) {
			$short[ $pid ] = array( 'title' => get_the_title( $pid ), 'have' => $got );
		}
	}

	$derived_keys = array( 'parent:province', 'parent:county', 'parent:city', 'children', 'distance:capital' );
	$always       = array_values( array_filter( $derived_keys, function ( $k ) use ( $fill, $ids ) { return $ids && isset( $fill[ $k ] ) && $fill[ $k ] === count( $ids ); } ) );
	$fields_only  = array_values( array_filter( $order, function ( $k ) use ( $derived_keys ) { return ! in_array( $k, $derived_keys, true ) && 'capital' !== $k && 0 !== strpos( $k, 'count:' ); } ) );
	$need         = max( 0, 4 - count( $always ) );
	$required     = array_slice( $fields_only, 0, $need );
	/* پس از مشتق‌ها: کمتر از ۴ با همهٔ نامزدها (همان short) */
	$out[ $type ] = array(
		'always'   => $always,
		'required' => $required,
		'guaranteed' => $ids ? ( count( $always ) >= 4 ) : null,
		'guaranteed_with_required' => $ids ? ( count( $always ) + count( $required ) >= 4 ) : null,
		'posts'   => count( $ids ),
		'current' => $current,
		'order'   => $order,
		'fill'    => array_intersect_key( $fill, array_flip( $order ) ),
		'labels'  => array_map( function ( $k ) use ( $map ) {
			if ( 'capital' === $k ) { return 'مرکز'; }
			if ( 'distance:capital' === $k ) { return 'فاصله تا مرکز استان'; }
			if ( 'parent:province' === $k ) { return 'استان (والد R01)'; }
			if ( 'parent:city' === $k ) { return 'شهر (والد R01)'; }
			if ( 'parent:county' === $k ) { return 'شهرستان (والد R01)'; }
			if ( 'children' === $k ) { return 'شمار مکان‌های زیرمجموعه'; }
			if ( 0 === strpos( $k, 'count:' ) ) { return 'شمار ' . substr( $k, 6 ); }
			return isset( $map[ $k ] ) ? $map[ $k ]['label'] : $k;
		}, array_combine( $order, $order ) ),
		'reach_fallback' => $reach,
		'short'   => $short,
	);
}

echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
