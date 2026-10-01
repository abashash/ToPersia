<?php
namespace Jet_Reviews\Export_Import;

// If this file is called directly, abort.
use Jet_Reviews\Reviews\Data as Reviews_Data;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Compatibility Manager
 */
class Manager {

	const EXPORT_ACTION = 'jet_reviews_export_reviews_action';
	const EXPORT_NONCE_ACTION = 'jet_reviews_export_reviews';
	const IMPORT_NONCE_ACTION = 'jet_reviews_import_reviews';

	/**
	 * @var string
	 */
	public $export_reviews_hook = self::EXPORT_ACTION;

	/**
	 * [load_files description]
	 * @return [type] [description]
	 */
	public function load_files() {
		require_once jet_reviews()->plugin_path( 'includes/components/export-import/author-resolver.php' );
	}

	/**
	 * Return the stable CSV column order used by review transfers.
	 *
	 * @return array
	 */
	public static function get_export_headers() {
		return array(
			'source',
			'post_type',
			'post_id',
			'author',
			'author_name',
			'author_mail',
			'date',
			'title',
			'content',
			'type_slug',
			'rating_data',
			'rating',
			'likes',
			'dislikes',
			'approved',
			'pinned',
			'reaction_schema',
			'reaction_uuid',
			'reaction_legacy_id',
			'reaction_origin',
			'reaction_signature',
		);
	}

	/**
	 * Summarize per-row import results without changing their legacy shape.
	 *
	 * @param array $imported_data Per-row import results.
	 * @return array
	 */
	public static function get_import_summary( $imported_data ) {
		$summary = array(
			'inserted'       => 0,
			'already_exists' => 0,
			'failed'         => 0,
		);

		foreach ( (array) $imported_data as $item ) {
			if ( is_array( $item ) && isset( $item['status'] ) && 'already_exists' === $item['status'] ) {
				$summary['already_exists']++;
			} elseif ( is_array( $item ) && ! empty( $item['insert_id'] ) ) {
				$summary['inserted']++;
			} else {
				$summary['failed']++;
			}
		}

		return $summary;
	}

	/**
	 * Build a concise import notice without zero-value counters.
	 *
	 * @param array $summary Import result counters.
	 * @return array
	 */
	public static function get_import_notice( $summary ) {
		$inserted       = isset( $summary['inserted'] ) ? absint( $summary['inserted'] ) : 0;
		$already_exists = isset( $summary['already_exists'] ) ? absint( $summary['already_exists'] ) : 0;
		$failed         = isset( $summary['failed'] ) ? absint( $summary['failed'] ) : 0;

		if ( $failed ) {
			if ( ! $inserted && ! $already_exists ) {
				return array(
					'message' => sprintf(
						/* translators: %d: number of reviews that could not be imported. */
						_n( 'Import failed. %d review couldn\'t be imported.', 'Import failed. %d reviews couldn\'t be imported.', $failed, 'jet-reviews' ),
						$failed
					),
					'type'    => 'error',
				);
			}

			$message_parts = array( __( 'Import completed with issues.', 'jet-reviews' ) );

			if ( $inserted ) {
				$message_parts[] = sprintf(
					/* translators: %d: number of imported reviews. */
					_n( '%d review imported.', '%d reviews imported.', $inserted, 'jet-reviews' ),
					$inserted
				);
			}

			if ( $already_exists ) {
				$message_parts[] = sprintf(
					/* translators: %d: number of existing reviews skipped during import. */
					_n( '%d existing review was skipped.', '%d existing reviews were skipped.', $already_exists, 'jet-reviews' ),
					$already_exists
				);
			}

			$message_parts[] = sprintf(
				/* translators: %d: number of reviews that could not be imported. */
				_n( '%d review couldn\'t be imported.', '%d reviews couldn\'t be imported.', $failed, 'jet-reviews' ),
				$failed
			);

			return array(
				'message' => implode( ' ', $message_parts ),
				'type'    => 'warning',
			);
		}

		if ( ! $inserted && $already_exists ) {
			$message = 1 === $already_exists
				? __( 'This review already exists. Nothing was imported.', 'jet-reviews' )
				: sprintf(
					/* translators: %d: number of reviews that already exist. */
					__( 'All %d reviews already exist. Nothing was imported.', 'jet-reviews' ),
					$already_exists
				);

			return array(
				'message' => $message,
				'type'    => 'info',
			);
		}

		if ( $inserted ) {
			$message_parts = array(
				sprintf(
					/* translators: %d: number of imported reviews. */
					_n( '%d review imported.', '%d reviews imported.', $inserted, 'jet-reviews' ),
					$inserted
				),
			);

			if ( $already_exists ) {
				$message_parts[] = sprintf(
					/* translators: %d: number of existing reviews skipped during import. */
					_n( '%d existing review was skipped.', '%d existing reviews were skipped.', $already_exists, 'jet-reviews' ),
					$already_exists
				);
			}

			return array(
				'message' => implode( ' ', $message_parts ),
				'type'    => 'success',
			);
		}

		return array(
			'message' => __( 'No reviews were imported.', 'jet-reviews' ),
			'type'    => 'info',
		);
	}

	/**
	 * @return void
	 */
	public function export_reviews_action() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You don\'t have permissions to do this', 'jet-reviews' ),
				'',
				array(
					'response' => 403,
				)
			);
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, self::EXPORT_NONCE_ACTION ) ) {
			wp_die(
				esc_html__( 'Invalid export request', 'jet-reviews' ),
				'',
				array(
					'response' => 403,
				)
			);
		}

		$request_args = $this->get_export_request_args();

		if ( is_wp_error( $request_args ) ) {
			wp_die(
				esc_html( $request_args->get_error_message() ),
				'',
				array(
					'response' => 400,
				)
			);
		}

		$items = Reviews_Data::get_instance()->get_reviews_items_by_source( $request_args['source'], $request_args['source_type'] );
		$this->send_items( $items );
	}

	/**
	 * Validate and normalize export request params before querying review data.
	 *
	 * @return array|\WP_Error
	 */
	public function get_export_request_args() {

		// The export nonce is verified in export_reviews_action() before this helper runs.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['source'] ) || ! isset( $_GET['sourceType'] ) ) {
			return new \WP_Error( 'jet_reviews_export_missing_args', __( 'Export source is missing', 'jet-reviews' ) );
		}

		$source      = sanitize_key( wp_unslash( $_GET['source'] ) );
		$source_type = sanitize_key( wp_unslash( $_GET['sourceType'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( empty( $source ) || empty( $source_type ) ) {
			return new \WP_Error( 'jet_reviews_export_empty_args', __( 'Export source is invalid', 'jet-reviews' ) );
		}

		$source_options = jet_reviews()->reviews_manager->sources->get_source_type_options();

		if ( empty( $source_options[ $source ]['types'] ) ) {
			return new \WP_Error( 'jet_reviews_export_invalid_source', __( 'Export source is invalid', 'jet-reviews' ) );
		}

		$allowed_source_types = wp_list_pluck( $source_options[ $source ]['types'], 'value' );

		if ( ! in_array( $source_type, $allowed_source_types, true ) ) {
			return new \WP_Error( 'jet_reviews_export_invalid_source_type', __( 'Export source type is invalid', 'jet-reviews' ) );
		}

		return array(
			'source'      => $source,
			'source_type' => $source_type,
		);
	}

	/**
	 * @param $items
	 * @param $content_type
	 *
	 * @return void
	 */
	public function send_items( $items = array(), $content_type = null ) {

		$filename = 'export-jet-reviews-' . date( 'd-m-Y' ) . '.csv';
		$headers = self::get_export_headers();
		$author_resolver = new Author_Resolver();
		$identity_helper = jet_reviews()->reviews_manager->reaction_identity;
		$review_ids = array_values( array_filter( array_map( function ( $item ) {
			return isset( $item['id'] ) ? absint( $item['id'] ) : 0;
		}, $items ) ) );
		$identities = $identity_helper->get_many( $review_ids, true );

		$separator = apply_filters( 'jet-reviews/export-reviews/cvs-separator', ',' );

		$file = implode( $separator, $headers ) . PHP_EOL;

		foreach ( $items as $item ) {

			$preapred_item = array();
			$author_data = $author_resolver->get_export_author_data( isset( $item['author'] ) ? $item['author'] : '' );
			$item['author_name'] = $author_data['name'];
			$item['author_mail'] = $author_data['mail'];
			$review_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
			$identity_fields = isset( $identities[ $review_id ] )
				? $identity_helper->get_export_fields( $identities[ $review_id ] )
				: array_fill_keys( $identity_helper->get_csv_headers(), '' );
			$item = array_merge( $item, $identity_fields );

			foreach ( $headers as $key ) {
				$value = isset( $item[ $key ] ) ? $item[ $key ] : '';

				if ( $value ) {
					// Escaping a double quote.
					$value = str_replace( '"', '""', $value );

				} elseif ( is_array( $value ) && empty( $value ) ) {
					$value = null;
				}

				$preapred_item[] = $value;
			}

			$file .= '"' . implode( '"' . $separator . '"', $preapred_item ) . '"' . PHP_EOL;
		}

		$this->file_download( $filename, $file, 'text/csv' );

	}

	/**
	 * Process
	 * @param  [type] $filename [description]
	 * @param  string $file     [description]
	 * @return [type]           [description]
	 */
	public static function file_download( $filename = null, $file = '', $type = 'application/json' ) {

		if ( false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}

		@session_write_close();

		if( function_exists( 'apache_setenv' ) ) {
			$variable = 'no-gzip';
			$value = 1;
			@apache_setenv($variable, $value);
		}

		@ini_set( 'zlib.output_compression', 'Off' );

		nocache_headers();

		$file = apply_filters( 'jet-reviews/export-reviews/file-download', $file, $type, $filename );

		header( "Robots: none" );
		header( "Content-Type: " . $type );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=\"" . $filename . "\";" );
		header( "Content-Transfer-Encoding: binary" );

		// Set the file size header
		header( "Content-Length: " . strlen( $file ) );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a nonce- and capability-protected file download; escaping would corrupt the CSV bytes.
		echo $file;
		die();
	}

	/**
	 * [import_popup_preset description]
	 * @return [type] [description]
	 */
	public function parse_import_file_action() {

		if ( ! current_user_can( 'import' ) ) {
			wp_send_json_error( [
				'message' => __( 'You don\'t have permissions to do this', 'jet-reviews' ),
			] );
		}

		if ( ! check_ajax_referer( self::IMPORT_NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error( [
				'message' => __( 'Invalid import request', 'jet-reviews' ),
			] );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload metadata is validated and normalized field-by-field below.
		$file = isset( $_FILES['_file'] ) && is_array( $_FILES['_file'] ) ? $_FILES['_file'] : array();

		if ( empty( $file ) ) {
			wp_send_json_error( [
				'message' => __( 'File not passed', 'jet-reviews' ),
			] );
		}

		$file_name = isset( $file['name'] ) && is_scalar( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '';
		$tmp_name  = isset( $file['tmp_name'] ) && is_scalar( $file['tmp_name'] ) ? sanitize_text_field( wp_unslash( $file['tmp_name'] ) ) : '';
		$file_error = isset( $file['error'] ) && is_scalar( $file['error'] ) ? absint( $file['error'] ) : UPLOAD_ERR_NO_FILE;

		if ( UPLOAD_ERR_OK !== $file_error || empty( $file_name ) || empty( $tmp_name ) || ! is_uploaded_file( $tmp_name ) ) {
			wp_send_json_error( [
				'message' => __( 'File not passed', 'jet-reviews' ),
			] );
		}

		$file_type = wp_check_filetype_and_ext(
			$tmp_name,
			$file_name,
			array( 'csv' => 'text/csv' )
		);

		if ( 'csv' !== $file_type['ext'] || 'text/csv' !== $file_type['type'] ) {
			wp_send_json_error( [
				'message' => __( 'Format not allowed', 'jet-reviews' ),
			] );
		}

		$handle = fopen( $tmp_name, 'r' );
		$row = 1;
		$headers = [];
		$raw_parsed_data = [];

		if ( ! empty( $handle ) ) {

			while ( FALSE !== ( $data = fgetcsv( $handle, 1000, ',' ) ) ) {
				$length = count( $data );

				$fields_data = [];

				for ( $field = 0; $field < $length; $field++ ) {

					if ( 1 == $row ) {
						$headers[] = $data[ $field ];
					} else {
						$fields_data[] = $data[ $field ];
					}
				}

				if ( ! empty( $headers ) && ! empty( $fields_data ) ) {
					$row_data = [];

					foreach ( $headers as $key => $header_item ) {
						$row_data[ $header_item ] = isset( $fields_data[ $key ] ) ? $fields_data[ $key ] : '';
					}

					$raw_parsed_data[] = $row_data;
				}

				$row++;
			}

			fclose( $handle );
		} else {
			wp_send_json_error( [
				'message'       => __( 'No data found in file', 'jet-reviews' ),
			] );
		}

		$raw_parsed_data = apply_filters( 'jet-reviews/import-reviews/raw-parsed-data', $raw_parsed_data, $headers );
		$headers = apply_filters( 'jet-reviews/import-reviews/raw-parsed-headers', $headers );

		wp_send_json_success( [
			'headers'       => $headers,
			'rawParsedData' => $raw_parsed_data,
		] );
	}

	/**
	 * [__construct description]
	 */
	public function __construct() {
		$this->load_files();

		add_action( 'wp_ajax_' . self::EXPORT_ACTION, [ $this, 'export_reviews_action' ] );
		add_action( 'admin_post_' . self::EXPORT_ACTION, [ $this, 'export_reviews_action' ] );
		add_action( 'wp_ajax_jet_reviews_parse_import_file', [ $this, 'parse_import_file_action' ] );
	}

}
