<?php
/**
 * ۲ه٫۳ · P5-D331 ب — آزمون WPML برای best_season چندانتخابی: پس از ذخیرهٔ پست فارسی، مقدار en و ar برابر fa می‌شود؟
 * اجرا: php wp-cli.phar … eval-file tests/wpml-checkbox-local.php
 * پست‌های موقت نشان `_tp_test_marker` می‌گیرند و در پایان (حتی با خطا) فقط همین‌ها پاک می‌شوند. فقط محلی.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }
global $sitepress;
$made = array(); $res = array();
$mk = function ( $title, $lang, $trid = null, $src = null ) use ( &$made, $sitepress ) {
	$id = wp_insert_post( array( 'post_type' => 'tp_country', 'post_status' => 'draft', 'post_title' => $title ) );
	update_post_meta( $id, '_tp_test_marker', 'wpml-checkbox-2e3' ); $made[] = $id;
	$sitepress->set_element_language_details( $id, 'post_tp_country', $trid, $lang, $src );
	return $id;
};
try {
	foreach ( array( 'array' => array( 'spring', 'autumn' ), 'truefalse' => array( 'spring' => 'true', 'summer' => 'false', 'autumn' => 'true', 'winter' => 'false' ) ) as $shape => $val ) {
		$fa = $mk( "tp-test best_season {$shape} fa", 'fa' );
		$trid = $sitepress->get_element_trid( $fa, 'post_tp_country' );
		$en = $mk( "tp-test best_season {$shape} en", 'en', $trid, 'fa' );
		$ar = $mk( "tp-test best_season {$shape} ar", 'ar', $trid, 'fa' );
		update_post_meta( $fa, 'best_season', $val );
		/* ذخیرهٔ پست فارسی ← قلاب‌های WPML */
		wp_update_post( array( 'ID' => $fa, 'post_title' => "tp-test best_season {$shape} fa v2" ) );
		do_action( 'wpml_sync_all_custom_fields', $fa );
		$res[ $shape ] = array( 'fa' => get_post_meta( $fa, 'best_season', true ), 'en' => get_post_meta( $en, 'best_season', true ), 'ar' => get_post_meta( $ar, 'best_season', true ) );
	}
} finally {
	foreach ( $made as $id ) { if ( 'wpml-checkbox-2e3' === get_post_meta( $id, '_tp_test_marker', true ) ) { wp_delete_post( $id, true ); } }
}
foreach ( $res as $shape => $r ) {
	echo $shape, ': fa=', wp_json_encode( $r['fa'] ), ' en=', wp_json_encode( $r['en'] ), ' ar=', wp_json_encode( $r['ar'] ),
		' → ', ( $r['en'] === $r['fa'] && $r['ar'] === $r['fa'] ) ? 'برابر' : 'نابرابر', "\n";
}
echo 'پست‌های موقت ساخته‌شده و پاک‌شده: ', count( $made ), "\n";
