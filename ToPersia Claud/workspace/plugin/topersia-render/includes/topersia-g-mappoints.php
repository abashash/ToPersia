<?php
/**
 * نشانگرهای نقشه — REST `topersia/v1/map-points?context={post_id}`.  P5-D334 · P5-D346
 *
 * از نمایۀ مشتق مکانی هسته (`topersia_geo` — استان و شهر از زنجیرۀ R01) می‌خواند، نه از متا و نه از رابطۀ زنده:
 *   P5-D387 — فقط یک سطح پایین‌تر:
 *     استان ← شهرها      شهر ← جاذبه‌ها و طبیعتِ درون شهر (نه آنچه زیر روستای همان شهر است)
 *     محله و روستا ← جاذبه‌های خودش (والد R01 نزدیکِ جاذبه همان محله یا روستاست)
 *   (پیش از آن: استان ← شهرها · جاذبه‌ها · طبیعت؛ شهر ← جاذبه‌ها · محله‌ها · روستاها — P5-D334)
 * فقط منتشرشده‌ها، نام و نشانی و برچسب نوع به زبان صفحه (شناسۀ نسخۀ همان زبان)، با کش مرکزی (کلید: زمینه، زبان، گواه تازگی
 * نمایه؛ ابطال خودکار با هر نوشتن در نمایه). خروجی GeoJSON برای خوشه‌بندی داخلی MapLibre — کتابخانۀ تازه‌ای نیست.
 * مسیریابی، نشان و «نزدیک من» در این دسته ساخته نمی‌شوند.
 *
 * @package ToPersia_Render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * خانوادۀ هر نوع پست روی نقشه (کلید ← نوع‌ها).
 *
 * @return array<string,string[]>
 */
if ( ! function_exists( 'topersia_g_map_families' ) ) {
	function topersia_g_map_families() {

		return (array) apply_filters( 'topersia_g_map_families', array(
			'city'       => array( 'tp_city' ),
			'attraction' => array( 'tp_monument', 'tp_religious_site', 'tp_ancient_site', 'tp_museum', 'tp_landscape' ),
			'nature'     => array( 'tp_mountain', 'tp_water_body', 'tp_desert', 'tp_forest_plain', 'tp_island', 'tp_protected_area', 'tp_natural_feature' ),
			'district'   => array( 'tp_district' ),
			'village'    => array( 'tp_village' ),
		) );
	}
}

/** خانواده‌های هر زمینه: استان و شهر؛ هر نوع دیگر نشانگری ندارد. */
if ( ! function_exists( 'topersia_g_map_context_families' ) ) {
	function topersia_g_map_context_families( $type ) {

		/* P5-D387: یک سطح پایین‌تر. `own` = فقط آنچه والد R01 نزدیکش خودِ زمینه است؛ `inside` = بیرون‌گذاشتن آنچه زیر روستاست */
		$map = array(
			'tp_province' => array( 'scope' => 'province', 'families' => array( 'city' ) ),
			'tp_city'     => array( 'scope' => 'city', 'families' => array( 'attraction', 'nature' ), 'inside' => true ),
			'tp_district' => array( 'scope' => 'up', 'families' => array( 'attraction' ), 'own' => true ),
			'tp_village'  => array( 'scope' => 'up', 'families' => array( 'attraction' ), 'own' => true ),
		);

		return isset( $map[ $type ] ) ? $map[ $type ] : null;
	}
}

/** آیا نمایه و زمینه‌ای برای نشانگر هست — بر پایۀ سرور، برای افزودن نشانی به پیکربندی نقشه. */
if ( ! function_exists( 'topersia_g_map_points_on' ) ) {
	function topersia_g_map_points_on( $post_id ) {

		return function_exists( 'topersia_geo_index_enabled' ) && topersia_geo_index_enabled()
			&& null !== topersia_g_map_context_families( (string) get_post_type( $post_id ) );
	}
}

/**
 * داده: FeatureCollection.
 *
 * @param int $ctx شناسۀ پست زمینه (هر زبانی).
 * @return array|WP_Error
 */
if ( ! function_exists( 'topersia_g_map_points_build' ) ) {
	function topersia_g_map_points_build( $ctx ) {

		$type = (string) get_post_type( $ctx );
		$def  = topersia_g_map_context_families( $type );

		if ( ! $def ) {
			return array( 'type' => 'FeatureCollection', 'features' => array() );
		}

		/* ستون‌های نمایه به شناسۀ زبان پیش‌فرض‌اند */
		$default = apply_filters( 'wpml_default_language', null );
		$base    = $default ? absint( apply_filters( 'wpml_object_id', $ctx, $type, true, $default ) ) : absint( $ctx );

		$fams     = topersia_g_map_families();
		$features = array();
		$cap      = 400;

		/* محله و روستا ستون خودشان را در نمایه ندارند: نامزدها از شهرِ بالادستشان (وگرنه استان)، سپس صافی والد نزدیک */
		$scope = $def['scope'];
		$at    = $base;

		if ( 'up' === $scope ) {
			$scope = '';
			foreach ( array_reverse( topersia_g_place_chain( $base ) ) as $up ) {
				$ut = (string) get_post_type( $up );
				if ( 'tp_city' === $ut || 'tp_province' === $ut ) {
					$scope = 'tp_city' === $ut ? 'city' : 'province';
					$at    = function_exists( 'topersia_g_default_id' ) ? (int) topersia_g_default_id( $up ) : (int) $up;
					break;
				}
			}
			if ( '' === $scope ) {
				return array( 'type' => 'FeatureCollection', 'features' => array() );
			}
		}

		foreach ( $def['families'] as $fam ) {

			$rows = topersia_geo_index_context( $scope, $at, isset( $fams[ $fam ] ) ? $fams[ $fam ] : array(), ! empty( $def['own'] ) || ! empty( $def['inside'] ) ? 2000 : $cap );

			foreach ( $rows as $row ) {

				if ( count( $features ) >= $cap ) {
					break 2;
				}

				$live = topersia_g_published( array( (int) $row['post_id'] ) );

				if ( ! $live ) {
					continue;
				}

				/* والد R01 نزدیک (جزئی‌ترین) — P5-D387 */
				if ( ! empty( $def['own'] ) || ! empty( $def['inside'] ) ) {
					$near = topersia_g_place_parents( (int) $row['post_id'], 0, true );
					$near = $near ? reset( $near ) : null;
					$nid  = $near ? ( function_exists( 'topersia_g_default_id' ) ? (int) topersia_g_default_id( $near['id'] ) : (int) $near['id'] ) : 0;

					if ( ! empty( $def['own'] ) && $nid !== (int) $base ) {
						continue;
					}

					if ( ! empty( $def['inside'] ) && $nid && 'tp_village' === get_post_type( $nid ) ) {
						continue;
					}
				}

				$view = (int) $live[0];
				$img  = function_exists( 'get_the_post_thumbnail_url' ) ? (string) get_the_post_thumbnail_url( $view, 'thumbnail' ) : '';

				$features[] = array(
					'type'       => 'Feature',
					'geometry'   => array( 'type' => 'Point', 'coordinates' => array( (float) $row['lng'], (float) $row['lat'] ) ),
					'properties' => array(
						'id' => $view,
						'k'  => $fam,
						'n'  => topersia_g_name( $view ),
						't'  => topersia_g_kind_label( $view ),
						'u'  => topersia_g_public_url( $view ),
						'i'  => $img ? esc_url_raw( $img ) : '',
					),
				);
			}
		}

		return array( 'type' => 'FeatureCollection', 'features' => $features );
	}
}

/** REST — با کش مرکزی؛ کلید شامل زبان صفحه و گواه نمایه. */
if ( ! function_exists( 'topersia_g_map_points_rest' ) ) {
	function topersia_g_map_points_rest( $request ) {

		$ctx = absint( $request->get_param( 'context' ) );

		if ( ! $ctx || 'publish' !== get_post_status( $ctx ) || '' === topersia_g_public_url( $ctx ) ) {
			return new WP_Error( 'tp_mp_context', __( 'زمینۀ نقشه نامعتبر است.', 'topersia' ), array( 'status' => 400 ) );
		}

		if ( ! function_exists( 'topersia_geo_index_context' ) || ! topersia_geo_index_enabled() ) {
			return rest_ensure_response( array( 'type' => 'FeatureCollection', 'features' => array() ) );
		}

		if ( ! function_exists( 'topersia_cache_remember' ) ) {
			$out = topersia_g_map_points_build( $ctx );
			return is_wp_error( $out ) ? $out : rest_ensure_response( $out );
		}

		$out = topersia_cache_remember(
			'map.points',
			array( $ctx, topersia_g_page_lang(), topersia_geo_index_stamp() ),
			function () use ( $ctx ) {
				$data = topersia_g_map_points_build( $ctx );
				return is_wp_error( $data ) ? $data : array( 'value' => $data, 'ttl' => 15 * MINUTE_IN_SECONDS );
			},
			array( 'provider' => 'topersia-geo-index', 'lang' => topersia_g_page_lang(), 'keep' => HOUR_IN_SECONDS )
		);

		return is_wp_error( $out ) ? $out : rest_ensure_response( $out );
	}
}

if ( function_exists( 'add_action' ) ) {

	add_action( 'rest_api_init', function () {

		register_rest_route( 'topersia/v1', '/map-points', array(
			'methods'             => 'GET',
			'callback'            => 'topersia_g_map_points_rest',
			'permission_callback' => '__return_true',
			'args'                => array(
				'context' => array( 'required' => true, 'type' => 'integer' ),
			),
		) );
	} );
}
