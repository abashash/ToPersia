<?php
/**
 * ۲ه٫۳ · P5-D331 ب — کمک‌کنندهٔ آزمون «ویرایشگر واقعی پیشخوان» برای best_season (با `tests/wpml-editor-local.js`).
 *
 *   wp eval-file tests/wpml-editor-local.php setup    ← کشور موقت fa + ترجمه‌های en/ar + (برای نوع چک‌باکس) جعبهٔ متای موقت و تنظیم copy
 *   wp eval-file tests/wpml-editor-local.php verify   ← مقدار متا در fa · en · ar برای هر دو فیلد
 *   wp eval-file tests/wpml-editor-local.php cleanup  ← پاک‌کردن فقط پست‌های موقت و برگرداندن دو تنظیم موقت؛ سنجش با عکسِ پیش از آزمون
 *
 * فقط محلی. پست‌ها نشان `_tp_test_marker = wpml-editor-2e3` می‌گیرند؛ تنظیم‌های موقت (جعبهٔ متا و تنظیم copy
 * فیلد آزمون) در `scratch` عکس می‌گیرند و در `cleanup` برگردانده و با عکس مقایسه می‌شوند. داده‌های واقعی دست نمی‌خورند.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }

global $sitepress;
$act   = isset( $args[0] ) ? $args[0] : '';
$mark  = 'wpml-editor-2e3';
$box   = 'meta-tmp-2e3';
$cbkey = 'tp_tmp_best_season_cb';
$snap  = dirname( __FILE__ ) . '/.screens/2f-editor/snapshot.json';

if ( 'setup' === $act ) {

	if ( ! is_dir( dirname( $snap ) ) ) { mkdir( dirname( $snap ), 0777, true ); }

	$boxes = get_option( 'jet_engine_meta_boxes' );
	$tm    = $sitepress->get_setting( 'translation-management' );
	file_put_contents( $snap, wp_json_encode( array( 'boxes_md5' => md5( serialize( $boxes ) ), 'boxes_has_tmp' => isset( $boxes[ $box ] ), 'cft' => isset( $tm['custom_fields_translation'][ $cbkey ] ) ? $tm['custom_fields_translation'][ $cbkey ] : null ) ) );

	/* جعبهٔ موقت: یک checkbox جت‌انجین با چهار فصل (ذخیره به‌صورت نگاشت true/false — همان شکل خطرناک #1663) */
	$boxes[ $box ] = array(
		'args'        => array( 'object_type' => 'post', 'allowed_post_type' => array( 'tp_country' ), 'allowed_tax' => array(), 'allowed_posts' => array(), 'excluded_posts' => array(), 'active_conditions' => array(), 'position' => 'normal', 'priority' => 'high', 'name' => 'آزمون موقت best_season (۲ه٫۳)', 'hide_field_names' => false ),
		'meta_fields' => array( array(
			'id' => 9990123, 'title' => 'بهترین فصل (چک‌باکس موقت)', 'name' => $cbkey, 'object_type' => 'field', 'width' => '100%', 'type' => 'checkbox', 'is_array' => false, 'save_as_array' => false,
			'options' => array(
				array( 'key' => 'spring', 'value' => 'spring', 'label' => 'بهار' ), array( 'key' => 'summer', 'value' => 'summer', 'label' => 'تابستان' ),
				array( 'key' => 'autumn', 'value' => 'autumn', 'label' => 'پاییز' ), array( 'key' => 'winter', 'value' => 'winter', 'label' => 'زمستان' ),
			),
			'show_in_rest' => true,
		) ),
	);
	update_option( 'jet_engine_meta_boxes', $boxes );

	/* WPML: فیلد آزمون مثل best_season «copy» */
	$tm['custom_fields_translation'][ $cbkey ] = 1;
	$sitepress->set_setting( 'translation-management', $tm, true );

	$ids = array();
	$mk  = function ( $lang, $trid = null, $src = null ) use ( $sitepress, $mark ) {
		$id = wp_insert_post( array( 'post_type' => 'tp_country', 'post_status' => 'draft', 'post_title' => 'tp-test editor best_season ' . $lang ) );
		update_post_meta( $id, '_tp_test_marker', $mark );
		$sitepress->set_element_language_details( $id, 'post_tp_country', $trid, $lang, $src );
		return $id;
	};
	$ids['fa']   = $mk( 'fa' );
	$trid        = $sitepress->get_element_trid( $ids['fa'], 'post_tp_country' );
	$ids['en']   = $mk( 'en', $trid, 'fa' );
	$ids['ar']   = $mk( 'ar', $trid, 'fa' );
	echo wp_json_encode( $ids ), "\n";
	return;
}

$ids = get_posts( array( 'post_type' => 'tp_country', 'post_status' => 'any', 'numberposts' => 10, 'fields' => 'ids', 'meta_key' => '_tp_test_marker', 'meta_value' => $mark, 'suppress_filters' => true ) );

if ( 'verify' === $act ) {
	$out = array();
	foreach ( $ids as $id ) {
		$lang = $sitepress->get_language_for_element( $id, 'post_tp_country' );
		$out[ $lang ] = array( 'id' => $id, 'best_season' => get_post_meta( $id, 'best_season', true ), $cbkey => get_post_meta( $id, $cbkey, true ) );
	}
	echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE ), "\n";
	return;
}

if ( 'cleanup' === $act ) {
	foreach ( $ids as $id ) { if ( $mark === get_post_meta( $id, '_tp_test_marker', true ) ) { wp_delete_post( $id, true ); } }
	$boxes = get_option( 'jet_engine_meta_boxes' ); unset( $boxes[ $box ] ); update_option( 'jet_engine_meta_boxes', $boxes );
	$tm = $sitepress->get_setting( 'translation-management' ); unset( $tm['custom_fields_translation'][ $cbkey ] ); $sitepress->set_setting( 'translation-management', $tm, true );
	$was = json_decode( (string) @file_get_contents( $snap ), true );
	$now = md5( serialize( get_option( 'jet_engine_meta_boxes' ) ) );
	$tm2 = $sitepress->get_setting( 'translation-management' );
	echo 'deleted=', count( $ids ), ' boxes_restored=', ( $was && $was['boxes_md5'] === $now ) ? 'yes' : 'NO', ' cft_clean=', isset( $tm2['custom_fields_translation'][ $cbkey ] ) ? 'NO' : 'yes', "\n";
}
