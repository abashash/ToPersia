<?php
/**
 * تاریخ رویداد روی صفحهٔ E1 · E3 · E5 — داده و پاک‌سازی برای tests/event-dates.js  (P5-D357 · فقط topersia.local)
 *
 *   wp eval-file tests/event-dates-local.php setup            ← چاپ JSON شناسه‌ها
 *   wp eval-file tests/event-dates-local.php cleanup <ids,…>  ← پاک‌کردن پست‌های موقت
 *
 * setup: برای هر یک از tp_festival (E1) · tp_sports_event (E3) · tp_religious_event (E5) یک پست فارسی با بازهٔ تاریخ
 * و ترجمهٔ en و ar (منتشرشده؛ تاریخ copy از اصل)، به‌علاوهٔ یک E1 بی‌تاریخ، یک E1 فقط با تاریخ آغاز و یک E1 فقط با تاریخ پایان.
 * تاریخ‌ها ثابت: 2027-09-21 تا 2027-09-24 (E1)، 2027-03-05 تا 2027-03-06 (E3)، 2027-06-10 (E5، آغاز و پایان یکی).
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$act = isset( $args[0] ) ? $args[0] : '';

if ( 'cleanup' === $act ) {
	$ids = array_filter( array_map( 'absint', explode( ',', isset( $args[1] ) ? $args[1] : '' ) ) );
	foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
	$left = 0;
	foreach ( $ids as $id ) { if ( get_post( $id ) ) { $left++; } }
	echo wp_json_encode( array( 'deleted' => count( $ids ), 'left' => $left ) ) . "\n";
	return;
}

if ( 'setup' !== $act ) { fwrite( STDERR, "act?\n" ); exit( 2 ); }

$mk = function ( $type, $lang, $title, $trid, $meta ) {
	$id = wp_insert_post( array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_name' => sanitize_title( $title ), 'post_content' => 'متن آزمایشی رویداد.' ), true );
	if ( is_wp_error( $id ) ) { return array( 0, 0 ); }
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_' . $type, 'trid' => $trid ?: false, 'language_code' => $lang, 'source_language_code' => $trid ? 'fa' : null ) );
	foreach ( $meta as $k => $v ) { update_post_meta( $id, $k, $v ); }
	$d = apply_filters( 'wpml_element_language_details', null, array( 'element_id' => $id, 'element_type' => $type ) );
	return array( $id, $d ? (int) $d->trid : 0 );
};

$cases = array(
	'e1'      => array( 'tp_festival', 'Temp Event Dates E1', array( 'event_start_date' => '2027-09-21', 'event_end_date' => '2027-09-24' ), true ),
	'e3'      => array( 'tp_sports_event', 'Temp Event Dates E3', array( 'event_start_date' => '2027-03-05', 'event_end_date' => '2027-03-06' ), true ),
	'e5'      => array( 'tp_religious_event', 'Temp Event Dates E5', array( 'event_start_date' => '2027-06-10', 'event_end_date' => '2027-06-10' ), true ),
	'none'    => array( 'tp_festival', 'Temp Event Dates None', array(), false ),
	'only_s'  => array( 'tp_festival', 'Temp Event Dates OnlyStart', array( 'event_start_date' => '2027-09-21' ), false ),
	'only_e'  => array( 'tp_festival', 'Temp Event Dates OnlyEnd', array( 'event_end_date' => '2027-09-24' ), false ),
	/* P5-D361 — برگزارکننده و وب‌سایت رسمی؛ تاریخ آینده تا گرهٔ Event بیاید. پنجم: متای هر زبان */
	'org'     => array( 'tp_festival', 'Temp Event Org Full', array( 'event_start_date' => '2027-09-21', 'event_end_date' => '2027-09-24', 'official_event_url' => 'https://www.example.org/events/2027' ), true,
		array( 'fa' => array( 'organizer_name' => 'سازمان آزمون' ), 'en' => array( 'organizer_name' => 'Test Organizer' ), 'ar' => array( 'organizer_name' => 'منظمة الاختبار' ) ) ),
	'org_gap' => array( 'tp_festival', 'Temp Event Org Gap', array( 'event_start_date' => '2027-09-21', 'event_end_date' => '2027-09-24' ), true,
		array( 'fa' => array( 'organizer_name' => 'سازمان آزمون', 'official_event_url' => 'https://gap.example.org/' ), 'ar' => array( 'organizer_name' => 'منظمة الاختبار' ) ), array( 'official_event_url' => 'translation-empty' ) ),
	'url_http' => array( 'tp_festival', 'Temp Event Url Http', array( 'event_start_date' => '2027-09-21', 'event_end_date' => '2027-09-24', 'official_event_url' => 'http://insecure.example.org/' ), true ),
	'url_own'  => array( 'tp_festival', 'Temp Event Url Own', array( 'event_start_date' => '2027-09-21', 'event_end_date' => '2027-09-24', 'official_event_url' => 'https://www.topersia.com/festival/x/' ), true ),
	'org_thing' => array( 'tp_religious_event', 'Temp Event Org Thing', array( 'event_start_date' => '2020-03-01', 'event_end_date' => '2020-03-02', 'official_event_url' => 'https://past.example.org/' ), true,
		array( 'fa' => array( 'organizer_name' => 'سازمان گذشته' ) ) ),
);

$out = array();
foreach ( $cases as $key => $c ) {
	$ex = isset( $c[4] ) ? $c[4] : array();
	list( $fa, $trid ) = $mk( $c[0], 'fa', $c[1] . ' fa', 0, array_merge( $c[2], isset( $ex['fa'] ) ? $ex['fa'] : array() ) );
	$out[ $key ] = array( 'fa' => $fa );
	if ( $c[3] ) {
		foreach ( array( 'en', 'ar' ) as $lang ) {
			list( $id ) = $mk( $c[0], $lang, $c[1] . ' ' . $lang, $trid, array() );
			$out[ $key ][ $lang ] = $id;
			/* copy: WPML فیلد را از اصل می‌نشاند؛ همین‌جا هم صریح، تا آزمون به همگام‌ساز وابسته نباشد. «ترجمهٔ بی نشانی»: نشانی رسمی در ترجمه خالی می‌ماند (خواندن از اصل) */
			$skip = isset( $c[5] ) ? array_keys( $c[5] ) : array();
			foreach ( $c[2] as $k => $v ) { if ( ! in_array( $k, $skip, true ) ) { update_post_meta( $id, $k, $v ); } }
			foreach ( ( isset( $ex[ $lang ] ) ? $ex[ $lang ] : array() ) as $k => $v ) { update_post_meta( $id, $k, $v ); }
			if ( in_array( 'official_event_url', $skip, true ) ) { delete_post_meta( $id, 'official_event_url' ); }
		}
	}
}
clean_post_cache( 0 );
foreach ( $out as $key => &$by ) { foreach ( $by as $lang => $id ) { $by[ $lang ] = array( 'id' => $id, 'url' => apply_filters( 'wpml_permalink', get_permalink( $id ), $lang ) ); } }
echo wp_json_encode( $out ) . "\n";
