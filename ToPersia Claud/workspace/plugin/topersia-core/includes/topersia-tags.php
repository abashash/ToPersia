<?php
/**
 * ToPersia — برچسب مشترک محتوا `tpc_tag` — دستۀ 51 الحاقیۀ ث · P5-D555
 *
 * طبقه‌بندی ناسلسله‌مراتبی، مشترک میان همۀ نوع‌های عمومی `tp_`؛ فقط ویرایشگر و بالاتر می‌سازد و می‌چسباند.
 * نامک بایگانی `tags` (نه `tag` وردپرس): /tags/{slug}/ · /en/tags/{slug}/ · /ar/tags/{slug}/.
 * WPML: «ترجمه‌پذیر» (wpml-tm-custom-xml محلی، scripts/tp-local-51-tags.php)؛ نامک هر زبان جدا.
 * متای واژه: tp_is_experience (۰/۱) · tp_icon (کلید topersia_g_icon) · tp_sort (عدد).
 *
 * ساختار تازه ← فقط محلی تا ممیزی NotebookLM (همان دروازۀ topersia_search_fold_enabled).
 *
 * @package topersia-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_TAG_TAX' ) ) {
	define( 'TOPERSIA_TAG_TAX', 'tpc_tag' );
}

/** دروازه: پیش‌فرض فقط محیط local. */
function topersia_tags_enabled() {
	return (bool) apply_filters( 'topersia_tags_enabled', 'local' === wp_get_environment_type() );
}

/** نوع‌های عمومی ما که برچسب می‌گیرند — هر نوع عمومی با پیشوند tp_. */
function topersia_tags_types() {
	$types = array();
	foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
		if ( 0 === strpos( $type, 'tp_' ) ) {
			$types[] = $type;
		}
	}
	return (array) apply_filters( 'topersia_tags_types', $types );
}

/** ثبت پس از نوع‌های جت‌انجین (init ۱۰). */
function topersia_tags_register() {

	if ( ! topersia_tags_enabled() ) {
		return;
	}

	register_taxonomy(
		TOPERSIA_TAG_TAX,
		topersia_tags_types(),
		array(
			'labels'            => array(
				'name'          => 'برچسب‌های محتوا',
				'singular_name' => 'برچسب محتوا',
				'menu_name'     => 'برچسب‌های محتوا',
				'all_items'     => 'همۀ برچسب‌ها',
				'edit_item'     => 'ویرایش برچسب',
				'add_new_item'  => 'افزودن برچسب',
				'search_items'  => 'جست‌وجوی برچسب',
				'not_found'     => 'برچسبی نیست.',
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'show_tagcloud'     => false,
			'query_var'         => 'tpc_tag',
			'rewrite'           => array( 'slug' => 'tags', 'with_front' => false, 'hierarchical' => false ),
			/* فقط ویرایشگر و بالاتر — نویسنده نه می‌سازد نه می‌چسباند */
			'capabilities'      => array(
				'manage_terms' => 'edit_others_posts',
				'edit_terms'   => 'edit_others_posts',
				'delete_terms' => 'edit_others_posts',
				'assign_terms' => 'edit_others_posts',
			),
		)
	);

	/* پاک‌ساز چهار آرگومان می‌گیرد — تابع درونی PHP (intval) با آرگومان اضافه خطا می‌دهد؛ پس بستار. */
	register_term_meta( TOPERSIA_TAG_TAX, 'tp_is_experience', array( 'type' => 'integer', 'single' => true, 'default' => 0, 'show_in_rest' => true, 'sanitize_callback' => function ( $v ) { return $v ? 1 : 0; } ) );
	register_term_meta( TOPERSIA_TAG_TAX, 'tp_icon', array( 'type' => 'string', 'single' => true, 'default' => '', 'show_in_rest' => true, 'sanitize_callback' => function ( $v ) { return sanitize_key( (string) $v ); } ) );
	register_term_meta( TOPERSIA_TAG_TAX, 'tp_sort', array( 'type' => 'integer', 'single' => true, 'default' => 0, 'show_in_rest' => true, 'sanitize_callback' => function ( $v ) { return (int) $v; } ) );
}
add_action( 'init', 'topersia_tags_register', 20 );

/** فرم واژه در پیشخوان: سه متا. */
function topersia_tags_meta_fields( $term = null ) {

	$is_edit = is_object( $term );
	$exp     = $is_edit ? (int) get_term_meta( $term->term_id, 'tp_is_experience', true ) : 0;
	$icon    = $is_edit ? (string) get_term_meta( $term->term_id, 'tp_icon', true ) : '';
	$sort    = $is_edit ? (int) get_term_meta( $term->term_id, 'tp_sort', true ) : 0;

	wp_nonce_field( 'topersia_tag_meta', 'topersia_tag_meta_nonce' );

	$rows = array(
		array( 'tp_is_experience', 'تجربه (در «کارها و تجربیات» صفحۀ اول)', '<input type="checkbox" id="tp_is_experience" name="tp_is_experience" value="1"' . checked( 1, $exp, false ) . '>' ),
		array( 'tp_icon', 'کلید آیکون', '<input type="text" id="tp_icon" name="tp_icon" value="' . esc_attr( $icon ) . '" dir="ltr">' ),
		array( 'tp_sort', 'ترتیب', '<input type="number" id="tp_sort" name="tp_sort" value="' . esc_attr( (string) $sort ) . '" step="1">' ),
	);

	foreach ( $rows as $r ) {
		if ( $is_edit ) {
			echo '<tr class="form-field"><th scope="row"><label for="' . esc_attr( $r[0] ) . '">' . esc_html( $r[1] ) . '</label></th><td>' . $r[2] . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			echo '<div class="form-field"><label for="' . esc_attr( $r[0] ) . '">' . esc_html( $r[1] ) . '</label>' . $r[2] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
}
add_action( TOPERSIA_TAG_TAX . '_add_form_fields', 'topersia_tags_meta_fields' );
add_action( TOPERSIA_TAG_TAX . '_edit_form_fields', 'topersia_tags_meta_fields' );

/** ذخیرۀ متا — نانس و توانایی. */
function topersia_tags_meta_save( $term_id ) {

	if ( ! isset( $_POST['topersia_tag_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['topersia_tag_meta_nonce'] ) ), 'topersia_tag_meta' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}

	update_term_meta( $term_id, 'tp_is_experience', isset( $_POST['tp_is_experience'] ) ? 1 : 0 );
	update_term_meta( $term_id, 'tp_icon', isset( $_POST['tp_icon'] ) ? sanitize_key( wp_unslash( $_POST['tp_icon'] ) ) : '' );
	update_term_meta( $term_id, 'tp_sort', isset( $_POST['tp_sort'] ) ? intval( wp_unslash( $_POST['tp_sort'] ) ) : 0 );
}
add_action( 'created_' . TOPERSIA_TAG_TAX, 'topersia_tags_meta_save' );
add_action( 'edited_' . TOPERSIA_TAG_TAX, 'topersia_tags_meta_save' );
