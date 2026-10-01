<?php
namespace Jet_Reviews\Export_Import;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Resolves portable CSV author data to the local review author identifier.
 */
class Author_Resolver {

	/**
	 * @var \Jet_Reviews\User\Manager
	 */
	private $user_manager;

	/**
	 * @param \Jet_Reviews\User\Manager|null $user_manager User manager instance.
	 */
	public function __construct( $user_manager = null ) {
		$this->user_manager = $user_manager ? $user_manager : jet_reviews()->user_manager;
	}

	/**
	 * Return real author data for CSV export without using the guest fallback.
	 *
	 * @param string|int $author_id Stored review author identifier.
	 *
	 * @return array
	 */
	public function get_export_author_data( $author_id ) {
		$author_data = $this->user_manager->get_raw_user_data( $author_id );

		if ( empty( $author_data['id'] ) || (string) $author_data['id'] !== (string) $author_id ) {
			return array(
				'name' => '',
				'mail' => '',
			);
		}

		return array(
			'name' => isset( $author_data['name'] ) ? $author_data['name'] : '',
			'mail' => isset( $author_data['mail'] ) ? $author_data['mail'] : '',
		);
	}

	/**
	 * Resolve imported author data to an existing local user or guest.
	 *
	 * Invalid or absent e-mail values deliberately retain the legacy author ID.
	 *
	 * @param array $row_data Parsed CSV row.
	 *
	 * @return string|int
	 */
	public function resolve_import_author( $row_data ) {
		$legacy_author = isset( $row_data['author'] ) ? $row_data['author'] : '';

		if ( ! array_key_exists( 'author_mail', $row_data ) ) {
			return $legacy_author;
		}

		$author_mail = sanitize_email( $row_data['author_mail'] );

		if ( empty( $author_mail ) || ! is_email( $author_mail ) ) {
			return $legacy_author;
		}

		$author = get_user_by( 'email', $author_mail );

		if ( $author && ! empty( $author->ID ) ) {
			return $author->ID;
		}

		$guest = $this->user_manager->get_guest_by_email( $author_mail );

		if ( $guest && ! empty( $guest['guest_id'] ) ) {
			return $guest['guest_id'];
		}

		$guest_id = 'guest_' . jet_reviews_tools()->generate_rand_string( 9 );
		$guest_name = isset( $row_data['author_name'] ) ? sanitize_text_field( $row_data['author_name'] ) : '';
		$created = $this->user_manager->add_new_guest( array(
			'guest_id' => $guest_id,
			'name'     => $guest_name,
			'mail'     => $author_mail,
		) );

		if ( $created ) {
			return $guest_id;
		}

		// A concurrent import can create the guest between the lookup and insert.
		$guest = $this->user_manager->get_guest_by_email( $author_mail );

		return ( $guest && ! empty( $guest['guest_id'] ) ) ? $guest['guest_id'] : $legacy_author;
	}

}
