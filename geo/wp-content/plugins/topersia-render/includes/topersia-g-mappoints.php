<?php
/**
 * نشانگرهای نقشه — REST `topersia/v1/map-points?context={post_id}`.  P5-D334 · P5-D346
 *
 * از نمایۀ مشتق مکانی هسته (`topersia_geo` — استان و شهر از زنجیرۀ R01) می‌خواند، نه از متا و نه از رابطۀ زنده:
 *   زمینۀ استان ← شهرها · جاذبه‌ها · طبیعت      زمینۀ شهر ← جاذبه‌ها · محله‌ها · روستاها
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

		$map = array(
			'tp_province' => array( 'scope' => 'province', 'families' => array( 'city', 'attraction', 'nature' ) ),
			'tp_city'     => array( 'scope' => 'city', 'families' => array( 'attraction', 'district', 'village' ) ),
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

		foreach ( $def['families'] as $fam ) {

			$rows = topersia_geo_index_context( $def['scope'], $base, isset( $fams[ $fam ] ) ? $fams[ $fam ] : array(), $cap );

			foreach ( $rows as $row ) {

				if ( count( $features ) >= $cap ) {
					break 2;
				}

				$live = topersia_g_published( array( (int) $row['post_id'] ) );

				if ( ! $live ) {
					continue;
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
