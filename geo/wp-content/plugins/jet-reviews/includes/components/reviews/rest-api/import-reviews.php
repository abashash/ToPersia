<?php
namespace Jet_Reviews\Endpoints;

use Jet_Reviews\Export_Import\Author_Resolver;
use Jet_Reviews\Export_Import\Manager as Export_Import_Manager;
use Jet_Reviews\Reviews\Data as Reviews_Data;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Posts class
 */
class Import_Reviews extends Base {

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
		return 'import-reviews';
	}

	/**
	 * Returns arguments config
	 *
	 * @return [type] [description]
	 */
	public function get_args() {
		return array(
			'list' => array(
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

		if ( empty( $args['list'] ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'message' => __( 'Error', 'jet-reviews' ),
			) );
		}

		$review_list = $args['list'];
		$imported_review_data = [];
		$author_resolver = new Author_Resolver();
		$identity_helper = jet_reviews()->reviews_manager->reaction_identity;

		if ( ! empty( $review_list ) ) {

			foreach ( $review_list as $key => $row_data ) {
				if ( ! is_array( $row_data ) ) {
					$imported_review_data[] = false;
					continue;
				}

				$reaction_identity = $identity_helper->verify_import_fields( $row_data );
				$row_data = $this->prepare_import_row( $row_data, $author_resolver );

				if ( ! $reaction_identity ) {
					$imported_review_data[] = Reviews_Data::get_instance()->add_new_review( $row_data, true );
					continue;
				}

				$uuid_lock = $identity_helper->get_lock_name( 'identity-uuid-' . $reaction_identity['uuid'] );

				if ( ! $identity_helper->acquire_lock( $uuid_lock ) ) {
					$imported_review_data[] = false;
					continue;
				}

				try {
					$existing_review_id = $identity_helper->find_live_review_id( $reaction_identity['uuid'] );

					if ( $existing_review_id ) {
						$identity_helper->cancel_cleanup( $reaction_identity['uuid'] );
						$imported_review_data[] = array(
							'status'      => 'already_exists',
							'review_id'   => $existing_review_id,
							'reaction_uuid' => $reaction_identity['uuid'],
						);
						continue;
					}

					$insert_data = Reviews_Data::get_instance()->add_new_review( $row_data, true, $reaction_identity );

					if ( $insert_data ) {
						$identity_helper->cancel_cleanup( $reaction_identity['uuid'] );
					}

					$imported_review_data[] = $insert_data;
				} finally {
					$identity_helper->release_lock( $uuid_lock );
				}
			}
		}

		$import_summary = Export_Import_Manager::get_import_summary( $imported_review_data );
		$import_notice  = Export_Import_Manager::get_import_notice( $import_summary );
		$reviews_query = Reviews_Data::get_instance()->get_admin_reviews_list_by_page( false, 0, 20 );

		return rest_ensure_response( [
			'success' => true,
			'message' => __( 'The reviews have been imported', 'jet-reviews' ),
			'data'    => [
				'imported_data'  => $imported_review_data,
				'import_summary'  => $import_summary,
				'summary_message' => $import_notice['message'],
				'summary_type'    => $import_notice['type'],
				'page_list'      => $reviews_query['page_list'],
				'total_count'    => $reviews_query['total_count'],
			],
		] );
	}

	/**
	 * Sanitize one legacy or new-format CSV row without assuming optional keys.
	 *
	 * @param array           $row_data Raw CSV row.
	 * @param Author_Resolver $author_resolver Author resolver.
	 * @return array
	 */
	private function prepare_import_row( $row_data, $author_resolver ) {
		$rating_data = isset( $row_data['rating_data'] ) ? $row_data['rating_data'] : '';
		$type_slug   = isset( $row_data['type_slug'] ) && is_scalar( $row_data['type_slug'] )
			? sanitize_text_field( (string) $row_data['type_slug'] )
			: '';

		if ( is_array( $rating_data ) ) {
			$rating_data = maybe_serialize( $rating_data );
		} elseif ( ! is_scalar( $rating_data ) ) {
			$rating_data = '';
		}

		return array(
			'source'      => isset( $row_data['source'] ) ? sanitize_key( $row_data['source'] ) : 'post',
			'post_id'     => isset( $row_data['post_id'] ) ? absint( $row_data['post_id'] ) : 0,
			'post_type'   => isset( $row_data['post_type'] ) ? sanitize_key( $row_data['post_type'] ) : 'post',
			'author'      => $author_resolver->resolve_import_author( $row_data ),
			'date'        => isset( $row_data['date'] ) ? sanitize_text_field( $row_data['date'] ) : current_time( 'mysql' ),
			'title'       => isset( $row_data['title'] ) ? sanitize_text_field( $row_data['title'] ) : '',
			'content'     => isset( $row_data['content'] ) ? wp_kses_post( $row_data['content'] ) : '',
			// Existing review-type slugs are immutable identifiers and may contain legacy characters.
			'type_slug'   => $type_slug,
			'rating_data' => (string) $rating_data,
			'rating'      => isset( $row_data['rating'] ) ? absint( $row_data['rating'] ) : 0,
			'likes'       => isset( $row_data['likes'] ) ? max( 0, (int) $row_data['likes'] ) : 0,
			'dislikes'    => isset( $row_data['dislikes'] ) ? max( 0, (int) $row_data['dislikes'] ) : 0,
			'approved'    => isset( $row_data['approved'] ) && filter_var( $row_data['approved'], FILTER_VALIDATE_BOOLEAN ) ? 1 : 0,
			'pinned'      => isset( $row_data['pinned'] ) && filter_var( $row_data['pinned'], FILTER_VALIDATE_BOOLEAN ) ? 1 : 0,
		);
	}

}
