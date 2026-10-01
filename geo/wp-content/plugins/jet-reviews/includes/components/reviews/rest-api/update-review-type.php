<?php
namespace Jet_Reviews\Endpoints;

use Jet_Reviews\Reviews\Data as Reviews_Data;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Define Posts class
 */
class Update_Review_Type extends Base {

	/**
	 * [get_method description]
	 * @return [type] [description]
	 */
	public function get_method() {
		return 'POST';
	}

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'update-review-type';
	}

	/**
	 * Returns arguments config
	 *
	 * @return [type] [description]
	 */
	public function get_args() {

		return array(
			'name' => array(
				'default'    => '',
				'required'   => false,
			),
			'slug' => array(
				'default'    => '',
				'required'   => false,
			),
			'settings' => array(
				'default'    => [],
				'required'   => false,
			),
		);
	}

	/**
	 * Check user access to current end-popint
	 *
	 * @return string|bool
	 */
	public function permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * [callback description]
	 * @param  [type]   $request [description]
	 * @return function          [description]
	 */
	public function callback( $request ) {
		$args = $request->get_params();
		$name = isset( $args['name'] ) && is_scalar( $args['name'] ) ? sanitize_text_field( trim( (string) $args['name'] ) ) : '';
		$slug = isset( $args['slug'] ) && is_scalar( $args['slug'] ) ? trim( (string) $args['slug'] ) : '';
		$settings = isset( $args['settings'] ) && is_array( $args['settings'] ) ? $args['settings'] : array();
		$source = isset( $settings['source'] ) && is_scalar( $settings['source'] ) ? sanitize_key( $settings['source'] ) : '';
		$source_type = isset( $settings['source_type'] ) && is_scalar( $settings['source_type'] ) ? sanitize_key( $settings['source_type'] ) : '';

		if ( '' === $name ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => __( 'Review type name cannot be empty', 'jet-reviews' ),
			) );
		}

		if ( '' === $slug ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => __( 'Review type slug cannot be empty', 'jet-reviews' ),
			) );
		}

		if ( '' === $source || '' === $source_type ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => __( 'Review type source settings are invalid', 'jet-reviews' ),
			) );
		}

		$settings['source'] = $source;
		$settings['source_type'] = $source_type;

		$prepared_data = array(
			'name' => $name,
			'slug' => $slug,
			'source' => $source,
			'source_type' => $source_type,
			'fields' => maybe_serialize( isset( $settings['fields'] ) && is_array( $settings['fields'] ) ? $settings['fields'] : array() ),
			'settings' => maybe_serialize( $settings ),
		);

		$query = Reviews_Data::get_instance()->update_review_type( $prepared_data );
		do_action( 'jet-reviews/endpoints/reviews/update-review-type', $args );

		if ( false === $query ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => __( 'Server Error', 'jet-reviews' ),
			) );
		}

		if ( 0 === $query ) {
			if ( ! Reviews_Data::get_instance()->get_review_type_by_slug( $slug ) ) {
				return rest_ensure_response( array(
					'success' => false,
					'message' => __( 'Review type was not found', 'jet-reviews' ),
				) );
			}

			return rest_ensure_response( array(
				'success' => true,
				'message' => __( 'Review type is already up to date', 'jet-reviews' ),
			) );
		}

		return rest_ensure_response( array(
			'success'  => true,
			'message' => __( 'Review type has been updated', 'jet-reviews' ),
		) );
	}

}
