<?php
/**
 * P5-D500: نشانی نمونهٔ فارسی نوع‌های تازه برای tests/overflow-h.js. فقط می‌خواند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/overflow-h-urls.php
 */
if ( 'local' !== wp_get_environment_type() ) { echo "{}"; exit( 2 ); }
$out = array();
foreach ( array( 'tp_currency', 'tp_visa', 'tp_health_safety', 'tp_transport', 'tp_calendar', 'tp_festival', 'tp_protected_area' ) as $type ) {
	$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true, 'meta_key' => '_tp_sample', 'meta_value' => 1 ) );
	foreach ( $ids as $id ) {
		if ( ! get_post_meta( $id, '_tp_sample_empty', true ) && 'fa' === apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'post_' . $type ) ) ) {
			$out[ $type ] = wp_parse_url( get_permalink( $id ), PHP_URL_PATH );
			break;
		}
	}
}
echo wp_json_encode( $out );
