<?php
/**
 * نشانی fa · en · ar هر صفحه برای scripts/visual-report.js — فقط می‌خواند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/visual-report-urls.php <base64-json>
 *
 * ورودی: [ "http://topersia.local/…", … ]  ·  خروجی: [ { "fa": …, "en": …|"", "ar": …|"", "id": n }, … ]
 * پست ترجمه‌نشده یا منتشرنشده ← رشتهٔ خالی (گزارش «ترجمه نیست» می‌نویسد، نه عکس صفحهٔ دیگر).
 */

$in  = json_decode( base64_decode( (string) ( $args[0] ?? '' ) ), true );
$out = array();
foreach ( (array) $in as $url ) {
	$id  = url_to_postid( $url );
	$row = array( 'fa' => $url, 'en' => '', 'ar' => '', 'id' => $id );
	if ( $id ) {
		$type = get_post_type( $id );
		foreach ( array( 'en', 'ar' ) as $l ) {
			$t = (int) apply_filters( 'wpml_object_id', $id, $type, false, $l );
			if ( $t && $t !== $id && 'publish' === get_post_status( $t ) ) {
				do_action( 'wpml_switch_language', $l );
				$row[ $l ] = get_permalink( $t );
				do_action( 'wpml_switch_language', null );
			}
		}
	}
	$out[] = $row;
}
echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
