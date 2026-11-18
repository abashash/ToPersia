<?php
/**
 * صفحۀ فروش /advertise/ سه‌زبانه — دستۀ 54 بند ۱۲ (P5-D621).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-ads-pages.php      (TP_DRY=1 فقط گزارش)
 *
 * فقط محلی. هر زبان یک برگه با نامک `advertise` و محتوای `[tp_advertise]`؛ فارسی اصل، en و ar ترجمۀ همان trid.
 * متن صفحه از خود شورت‌کد و کاتالوگ topersia-ads می‌آید (هیچ متنی این‌جا نیست جز عنوان). noindex را افزونه می‌گذارد
 * تا گزینۀ tp_ads_advertise_index (با تأیید کاربر). ایدمپوتنت: برگه با نامک و زبان پیدا و به‌روز می‌شود.
 */

if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( (string) home_url(), 'topersia.local' ) ) {
	echo "فقط topersia.local\n";
	exit( 2 );
}
if ( ! shortcode_exists( 'tp_advertise' ) ) {
	echo "شورت‌کد tp_advertise نیست — افزونۀ topersia-ads فعال و روشن است؟\n";
	exit( 2 );
}

global $wpdb;
$dry   = (bool) getenv( 'TP_DRY' );
$slug  = 'advertise';
$pages = array(
	'fa' => 'تبلیغ در توپرشیا',
	'en' => 'Advertise on ToPersia',
	'ar' => 'أعلن في ToPersia',
);

$find = function ( $lang ) use ( $wpdb, $slug ) {
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = 'post_page'
		 WHERE p.post_type = 'page' AND p.post_name = %s AND t.language_code = %s AND p.post_status IN ('publish','draft','pending','private') ORDER BY p.ID LIMIT 1",
		$slug,
		$lang
	) );
};

$trid   = null;
$report = array();

foreach ( $pages as $lang => $title ) {

	$id  = $find( $lang );
	$arr = array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'post_title'     => $title,
		'post_name'      => $slug,
		'post_content'   => '[tp_advertise]',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	);

	if ( $dry ) {
		$report[] = sprintf( '%s %s %s', $lang, $id ? '#' . $id . ' به‌روز' : 'تازه', $title );
		continue;
	}

	if ( $id ) {
		$arr['ID'] = $id;
		$res       = wp_update_post( wp_slash( $arr ), true );
	} else {
		$res = wp_insert_post( wp_slash( $arr ), true );
		$id  = is_wp_error( $res ) ? 0 : (int) $res;
	}
	if ( is_wp_error( $res ) || ! $id ) {
		echo "شکست {$lang}: ", is_wp_error( $res ) ? $res->get_error_message() : '', "\n";
		exit( 1 );
	}

	if ( 'fa' === $lang ) {
		$trid = apply_filters( 'wpml_element_trid', null, $id, 'post_page' );
		if ( ! $trid ) {
			do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_page', 'trid' => false, 'language_code' => 'fa' ) );
			$trid = apply_filters( 'wpml_element_trid', null, $id, 'post_page' );
		}
	} else {
		do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_page', 'trid' => $trid, 'language_code' => $lang, 'source_language_code' => 'fa' ) );
	}

	/* WPML نامک تکراری را «-2» می‌کند — برمی‌گردانیم؛ نشانی هر زبان پیشوند خودش را دارد */
	if ( get_post_field( 'post_name', $id ) !== $slug ) {
		wp_update_post( array( 'ID' => $id, 'post_name' => $slug ) );
	}

	$report[] = sprintf( '%s #%d %s %s', $lang, $id, get_permalink( $id ), get_post_field( 'post_name', $id ) );
}

echo implode( "\n", $report ), "\n";
exit( 0 );
