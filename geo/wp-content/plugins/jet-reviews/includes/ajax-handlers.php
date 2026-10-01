<?php
/**
 * Class description
 *
 * @package   package_name
 * @author    Cherry Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Reviews_Ajax_Handlers' ) ) {

	/**
	 * Define Jet_Reviews_Ajax_Handlers class
	 */
	class Jet_Reviews_Ajax_Handlers {

		/**
		 * A reference to an instance of this class.
		 *
		 * @since  1.0.0
		 * @access private
		 * @var    object
		 */
		private static $instance = null;

		/**
		 * System messages
		 * 
		 * @var array
		 */
		private $sys_messages;

		/**
		 * Init Handler
		 */
		public function init() {

			$this->sys_messages = [
				'internal'       => esc_html__( 'Internal error. Please, try again later', 'jet-reviews' ),
				'server_error'   => esc_html__( 'Server error. Please, try again later', 'jet-reviews' ),
				'no_data'        => esc_html__( 'No Data Found', 'jet-reviews' ),
				'review_success' => esc_html__( 'Success. Review Has Been Added', 'jet-reviews' ),
				'review_removed' => esc_html__( 'Success. Review Has Been Removed', 'jet-reviews' ),
				'demo_mode'      => esc_html__( 'Demo Mode is active. Only logged users can leave review.', 'jet-reviews' ),
			];

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				add_action( 'wp_ajax_jet_reviews_add_meta_review', [ $this, 'add_new_meta_review' ] );
				add_action( 'wp_ajax_nopriv_jet_reviews_add_meta_review', [ $this, 'add_new_meta_review' ] );

				add_action( 'wp_ajax_jet_reviews_remove_review', [ $this, 'remove_review' ] );
				add_action( 'wp_ajax_nopriv_jet_reviews_remove_review', [ $this, 'remove_review' ] );
			}
		}

		/**
		 * Proccesing subscribe form ajax
		 *
		 * @return void
		 */
		public function add_new_meta_review() {
			$this->verify_ajax_request();

			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verify_ajax_request() checks the nonce; each array field is sanitized below.
			$data = ( ! empty( $_POST['data'] ) ) ? wp_unslash( $_POST['data'] ) : false;

			if ( ! $data || ! is_array( $data ) ) {
				wp_send_json( array( 'type' => 'error', 'message' => $this->sys_messages['server_error'] ) );
			}

			if ( jet_reviews_tools()->is_demo_mode() ) {
				wp_send_json( array( 'type' => 'error', 'message' => $this->sys_messages['demo_mode'] ) );
			}

			$data = $this->sanitize_review_request_data( $data );

			$post_id = $data['post_id'];

			if ( ! $post_id || ! get_post( $post_id ) ) {
				wp_send_json( array( 'type' => 'error', 'message' => $this->sys_messages['server_error'] ) );
			}

			$preview_data = $this->generate_preview_data( $data );

			if ( ! $preview_data ) {
				wp_send_json( array( 'type' => 'error', 'message' => $this->sys_messages['internal'] ) );
			}

			$this->update_meta_preview_data( $post_id, $preview_data );

			wp_send_json( array( 'type' => 'success', 'message' => $this->sys_messages['review_success'] ) );
		}

		/**
		 * Proccesing subscribe form ajax
		 *
		 * @return void
		 */
		public function remove_review() {
			$this->verify_ajax_request();

			// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verify_ajax_request() checks the nonce; the IDs are sanitized below.
			$data = ( ! empty( $_POST['data'] ) ) ? wp_unslash( $_POST['data'] ) : false;

			if ( ! $data || ! is_array( $data ) ) {
				wp_send_json( array( 'type' => 'error', 'message' => $this->sys_messages['server_error'] ) );
			}

			$post_id = isset( $data['post_id'] ) ? absint( $data['post_id'] ) : 0;

			if ( ! $post_id || ! get_post( $post_id ) ) {
				wp_send_json( array( 'type' => 'error', 'message' => $this->sys_messages['server_error'] ) );
			}

			$current_user_id = $this->get_curent_user_id();
			$review_user_id  = isset( $data['user_id'] ) ? absint( $data['user_id'] ) : $current_user_id;

			if ( empty( $current_user_id ) ) {
				return false;
			}

			if ( $review_user_id !== $current_user_id && ! current_user_can( 'manage_options' ) ) {
				wp_send_json( array( 'type' => 'error', 'message' => $this->sys_messages['server_error'] ) );
			}

			$current = get_post_meta( $post_id, 'jet-reviews-data', true );

			if ( ! empty( $current ) && isset( $current[ $review_user_id ] ) ) {
				unset( $current[ $review_user_id ] );

				update_post_meta( $post_id, 'jet-reviews-data', $current );

				wp_send_json( array( 'type' => 'success', 'message' => $this->sys_messages['review_removed'] ) );
			}
		}

		/**
		 * [update_meta_preview_data description]
		 * @param  [type] $post_id      [description]
		 * @param  [type] $preview_data [description]
		 * @return [type]               [description]
		 */
		public function update_meta_preview_data( $post_id, $preview_data ) {

			$current_user_id = $this->get_curent_user_id();
			$author_id = (int)get_post_field('post_author', $post_id );

			if ( empty( $current_user_id ) ) {
				return false;
			}

			$current = get_post_meta( $post_id, 'jet-reviews-data', true );

			if ( empty( $current ) ) {
				$current = [];
			}

			if ( $current_user_id === $author_id ) {

				$current_raw_fields = get_post_meta( $post_id, 'jet-review-items', true );

				if ( ! empty( $current_raw_fields ) ) {
					foreach ( $current_raw_fields as $field_id => $field_data ) {
						$current_raw_fields[$field_id]['field_value'] = $preview_data['review_fields'][$field_id]['field_value'];
					}
				}

				update_post_meta( $post_id, 'jet-review-items', $current_raw_fields );
			}

			$current[ $current_user_id ] = $preview_data;

			update_post_meta( $post_id, 'jet-reviews-data', $current );
		}

		/**
		 * [generate_preview_data description]
		 * @param  [type] $data [description]
		 * @return [type]       [description]
		 */
		public function generate_preview_data( $data ) {

			$current_user_id = $this->get_curent_user_id();

			if ( ! $current_user_id ) {
				return false;
			}

			$new_preview = [
				'user_id'        => $current_user_id,
				'review_time'    => $data['review_time'],
				'review_date'    => $data['review_date'],
				'review_fields'  => $data['review_fields'],
				'summary_title'  => $data['summary_title'],
				'summary_text'   => $data['summary_text'],
				'summary_legend' => $data['summary_legend'],
			];

			return $new_preview;
		}

		/**
		 * Verify AJAX request nonce.
		 *
		 * @return void
		 */
		private function verify_ajax_request() {
			if ( ! check_ajax_referer( 'jet-reviews-ajax', '_ajax_nonce', false ) ) {
				wp_send_json( array( 'type' => 'error', 'message' => $this->sys_messages['server_error'] ) );
			}
		}

		/**
		 * Sanitize incoming review request data.
		 *
		 * @param array $data Raw request data.
		 * @return array
		 */
		private function sanitize_review_request_data( $data ) {
			return array(
				'post_id'        => isset( $data['post_id'] ) ? absint( $data['post_id'] ) : 0,
				'review_time'    => isset( $data['review_time'] ) ? absint( $data['review_time'] ) : 0,
				'review_date'    => isset( $data['review_date'] ) ? sanitize_text_field( $data['review_date'] ) : '',
				'review_fields'  => $this->sanitize_review_fields( isset( $data['review_fields'] ) ? $data['review_fields'] : array() ),
				'summary_title'  => isset( $data['summary_title'] ) ? sanitize_text_field( $data['summary_title'] ) : '',
				'summary_text'   => isset( $data['summary_text'] ) ? sanitize_textarea_field( $data['summary_text'] ) : '',
				'summary_legend' => isset( $data['summary_legend'] ) ? sanitize_text_field( $data['summary_legend'] ) : '',
			);
		}

		/**
		 * Sanitize review fields payload.
		 *
		 * @param array $review_fields Raw review fields.
		 * @return array
		 */
		private function sanitize_review_fields( $review_fields ) {
			if ( empty( $review_fields ) || ! is_array( $review_fields ) ) {
				return array();
			}

			$sanitized = array();

			foreach ( $review_fields as $field_id => $field_data ) {
				if ( ! is_array( $field_data ) ) {
					continue;
				}

				$field_max   = isset( $field_data['field_max'] ) ? floatval( $field_data['field_max'] ) : 0;
				$field_value = isset( $field_data['field_value'] ) ? floatval( $field_data['field_value'] ) : 0;

				if ( 0 < $field_max && $field_value > $field_max ) {
					$field_value = $field_max;
				}

				if ( 0 > $field_value ) {
					$field_value = 0;
				}

				$sanitized[ sanitize_key( $field_id ) ] = array(
					'field_label' => isset( $field_data['field_label'] ) ? sanitize_text_field( $field_data['field_label'] ) : '',
					'field_value' => $field_value,
					'field_max'   => $field_max,
				);

				if ( isset( $field_data['field_step'] ) ) {
					$sanitized[ sanitize_key( $field_id ) ]['field_step'] = floatval( $field_data['field_step'] );
				}
			}

			return $sanitized;
		}

		/**
		 * [get_curent_user_id description]
		 * @return [type] [description]
		 */
		public function get_curent_user_id() {

			$current_user = wp_get_current_user();

			if ( 0 == $current_user->ID ) {
				return false;
			}

			return $current_user->ID;
		}

		/**
		 * Returns the instance.
		 *
		 * @since  1.0.0
		 * @access public
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
}

/**
 * Returns instance of Jet_Reviews_Ajax_Handlers
 *
 * @return object
 */
function jet_reviews_ajax_handlers() {
	return Jet_Reviews_Ajax_Handlers::get_instance();
}
