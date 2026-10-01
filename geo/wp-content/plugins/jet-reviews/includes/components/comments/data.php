<?php
namespace Jet_Reviews\Comments;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Data {

	/**
	 * A reference to an instance of this class.
	 *
	 * @since 1.0.0
	 * @var   object
	 */
	private static $instance = null;

	/**
	 * Constructor for the class
	 */
	function __construct() {}

	/**
	 * [insert_review description]
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public function get_admin_comments_list_by_page( $id = false, $page = 0, $per_page = 20, $review_id = '', $search = '' ) {

		$table_name = jet_reviews()->db->tables( 'review_comments', 'name' );

		$raw_id        = $id;
		$raw_review_id = $review_id;
		$id            = absint( $raw_id );
		$page          = absint( $page );
		$per_page      = absint( $per_page );
		$review_id     = absint( $raw_review_id );

		if ( ! $per_page ) {
			$per_page = 20;
		}

		$offset = $page * $per_page;

		$where_clauses = array();
		$query_args    = array();

		if ( ! empty( $raw_review_id ) && ! $review_id ) {
			$where_clauses[] = '1=0';
		} elseif ( $review_id ) {
			$where_clauses[] = 'review_id=%d';
			$query_args[]    = $review_id;
		}

		if ( ! empty( $raw_id ) && ! $id ) {
			$where_clauses[] = '1=0';
		} elseif ( $id ) {
			$where_clauses[] = 'id=%d';
			$query_args[]    = $id;
		}

		if ( ! empty( $search ) ) {
			$search_like = jet_reviews()->db->wpdb()->esc_like( $search );

			$where_clauses[] = 'content LIKE %s';
			$query_args[]    = '%' . $search_like . '%';
		}

		$where_sql = '';

		if ( ! empty( $where_clauses ) ) {
			$where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		$count_query = "SELECT COUNT(*) FROM $table_name $where_sql";
		$page_query  = "SELECT * FROM $table_name $where_sql ORDER BY id DESC LIMIT %d, %d";

		if ( ! empty( $query_args ) ) {
			$count_query = jet_reviews()->db->wpdb()->prepare( $count_query, $query_args );
		}

		$page_query_args   = $query_args;
		$page_query_args[] = $offset;
		$page_query_args[] = $per_page;
		$page_query        = jet_reviews()->db->wpdb()->prepare( $page_query, $page_query_args );

		$raw_result = jet_reviews()->db->wpdb()->get_results( $page_query, ARRAY_A );

		$result_count = 0;

		$prepare_data = array();

		if ( ! empty( $raw_result ) ) {

			foreach ( $raw_result as $key => $comment_data ) {

				$user_data = jet_reviews()->user_manager->get_raw_user_data( $comment_data['author'] );

				$prepare_data[] = array(
					'id'        => $comment_data['id'],
					'post'      => array(
						'id'    => $comment_data['post_id'],
						'title' => wp_trim_words( jet_reviews_tools()->decode_plain_text( get_the_title( $comment_data['post_id'] ) ), 3, ' ...' ),
						'link'  => get_permalink( $comment_data['post_id'] ),
						'type'  => get_post_type( $comment_data['post_id'] ),
					),
					'author'    => array(
						'id'     => $user_data['id'],
						'name'   => jet_reviews_tools()->decode_plain_text( $user_data['name'] ),
						'mail'   => jet_reviews_tools()->decode_plain_text( $user_data['mail'] ),
						'avatar' => $user_data['avatar'],
						'roles'  => $user_data['roles'],
						'url'    => add_query_arg( array( 'user_id' => $user_data['id'] ), esc_url( admin_url( 'user-edit.php' ) ) ),
					),
					'date'        => array(
						'raw'        => $comment_data['date'],
						'human_diff' => jet_reviews_tools()->human_time_diff_by_date( $comment_data['date'] ),
					),
					'content'     => jet_reviews_tools()->decode_editor_text( $comment_data['content'] ),
					'approved'    => filter_var( $comment_data['approved'], FILTER_VALIDATE_BOOLEAN ),
					'check'       => false,
				);
			}

			$result_count = jet_reviews()->db->wpdb()->get_var( $count_query );

		}

		return array(
			'page_list'   => $prepare_data,
			'total_count' => $result_count,
		);

	}

	/**
	 * [delete_review_by_id description]
	 * @param  integer $id [description]
	 * @return [type]      [description]
	 */
	public function submit_review_comment( $data = array() ) {

		if ( empty( $data ) ) {
			return false;
		}

		$table_name = jet_reviews()->db->tables( 'review_comments', 'name' );

		$prepare_data = array(
			'source'    => $data[ 'source' ],
			'post_id'   => $data[ 'post_id' ],
			'parent_id' => $data[ 'parent_id' ],
			'review_id' => $data[ 'review_id' ],
			'author'    => $data[ 'author' ],
			'content'   => $data[ 'content' ],
			'date'      => $data[ 'date' ],
			'approved'  => $data[ 'approved' ],
		);

		$query = jet_reviews()->db->wpdb()->insert( $table_name, $prepare_data );

		if ( ! $query ) {
			return false;
		}

		$insert_id = jet_reviews()->db->wpdb()->insert_id;

		$prepare_data = wp_parse_args( $prepare_data, [ 'id' => $insert_id ] );

		// maybe notify moderator
		jet_reviews_tools()->submit_comment_notify_moderator( $prepare_data );

		return $insert_id;
	}

	/**
	 * [insert_review description]
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public function get_review_comments_by_post_id( $source = 'post', $source_type = 'post', $source_id = 0 ) {
		$table_name = jet_reviews()->db->tables( 'review_comments', 'name' );
		$query = jet_reviews()->db->wpdb()->prepare(
			"SELECT * FROM $table_name WHERE source = %s AND post_id = %d AND approved=1 ORDER BY date DESC",
			$source,
			$source_id
		);
		$raw_result = jet_reviews()->db->wpdb()->get_results( $query, ARRAY_A );
		$prepare_data = array();

		if ( ! empty( $raw_result ) ) {
			$review_type_slug = jet_reviews()->reviews_manager->types->get_review_type_slug_by_source_type( $source, $source_type );
			$review_type_settings = jet_reviews()->reviews_manager->types->get_review_type_data( $review_type_slug, true );
			$verifications = $review_type_settings['comment_verifications'];

			foreach ( $raw_result as $key => $data ) {
				$user_data = get_user_by( 'id', $data['author'] );
				$user_data = jet_reviews()->user_manager->get_raw_user_data( $data['author'] );

				$review_verification_data = jet_reviews()->user_manager->get_verification_data(
					$verifications,
					array(
						'user_id' => $user_data['id'],
						'post_id' => $source_id,
					)
				);

				$prepare_data[] = array(
					'id'      => $data['id'],
					'post_id' => $data['post_id'],
					'parent_id' => $data['parent_id'],
					'review_id' => $data['review_id'],
					'author' => array(
						'id'     => $user_data['id'],
						'name'   => jet_reviews_tools()->decode_plain_text( $user_data['name'] ),
						'mail'   => jet_reviews_tools()->decode_plain_text( $user_data['mail'] ),
						'avatar' => $user_data['avatar'],
					),
					'date'          => array(
						'raw'        => $data['date'],
						'human_diff' => jet_reviews_tools()->human_time_diff_by_date( $data['date'] ),
					),
					'content'       => $data['content'],
					'approved'      => filter_var( $data['approved'], FILTER_VALIDATE_BOOLEAN ),
					'verifications' => $review_verification_data,
				);

			}
		}

		return $prepare_data;
	}

	/**
	 * [get_review_comments description]
	 * @return [type] [description]
	 */
	public function get_comments_count_by_reviews() {

		$table_name = jet_reviews()->db->tables( 'review_comments', 'name' );

		$query = "SELECT review_id AS review, COUNT(*) AS comments FROM $table_name GROUP BY review_id";

		$raw_result = jet_reviews()->db->wpdb()->get_results( $query, OBJECT_K );

		return $raw_result;
	}

	/**
	 * [update_review_type description]
	 * @param  array  $data [description]
	 * @return [type]       [description]
	 */
	public function update_comment( $data = array() ) {

		if ( empty( $data ) ) {
			return false;
		}

		$table_name = jet_reviews()->db->tables( 'review_comments', 'name' );

		$prepared_data = array(
			'content'     => jet_reviews_tools()->encode_editor_text( $data['content'], true ),
			'approved'    => filter_var( $data['approved'], FILTER_VALIDATE_BOOLEAN ) ? 1 : 0,
		);

		$query = jet_reviews()->db->wpdb()->update(
			$table_name,
			$prepared_data,
			array(
				'id' => $data['id'],
			)
		);

		return $query;
	}

	/**
	 * [delete_review_by_id description]
	 * @param  integer $id [description]
	 * @return [type]      [description]
	 */
	public function delete_comment_by_id( $id = 0 ) {

		$table_name = jet_reviews()->db->tables( 'review_comments', 'name' );

		$deleted_comment = jet_reviews()->db->wpdb()->delete( $table_name, array( 'id' => $id ) );
		$deleted_childs = jet_reviews()->db->wpdb()->delete( $table_name, array( 'parent_id' => $id ) );

		return $deleted_comment;
	}

	/**
	 * [get_review_count description]
	 * @return [type] [description]
	 */
	public function get_comment_count() {
		$table_name = jet_reviews()->db->tables( 'review_comments', 'name' );

		$count_query = "SELECT COUNT(*) FROM $table_name";

		$result_count = jet_reviews()->db->wpdb()->get_var( $count_query );

		return $result_count;
	}

	/**
	 * [get_approved_review_count description]
	 * @return [type] [description]
	 */
	public function get_approved_comment_count() {
		$table_name = jet_reviews()->db->tables( 'review_comments', 'name' );

		$count_query = "SELECT COUNT(*) FROM $table_name WHERE approved = 1";

		$result_count = jet_reviews()->db->wpdb()->get_var( $count_query );

		return $result_count;
	}

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @return object
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
}
