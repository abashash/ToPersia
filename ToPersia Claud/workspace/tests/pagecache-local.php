<?php
/**
 * برچسب کش صفحه روی وردپرس واقعی — SC2 · P5-D372  (فقط topersia.local؛ فقط خواندنی، هیچ داده‌ای نوشته نمی‌شود)
 *
 *   wp eval-file tests/pagecache-local.php
 *
 * صفحهٔ استان، شهر و یک جاذبه × fa · en · ar: برچسب‌هایی که `[tp_page]` جمع می‌کند؛ سپس
 *   - خودِ صفحه و نوعش هست؛ سقف رعایت می‌شود (همه ≤ ۵۰۰)؛
 *   - ویرایش یک شهر برچسبی را پاک می‌کند که صفحهٔ استانش هم دارد (fa و en)؛
 *   - سقف کوچک: شمار ≤ سقف و برچسب نوعِ اعضا جایگزین؛
 *   - بی LiteSpeed هیچ کار؛ با LiteSpeed (تقلید با شنونده) هر برچسب یک بار؛
 *   - کاربر واردشده و «به‌زودی» هیچ برچسبی جمع نمی‌کنند و nocache می‌خواهند.
 * پاک‌کردن واقعی اجرا نمی‌شود؛ فقط فهرست برچسب‌های آن محاسبه می‌شود.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }
global $wpdb;
$GLOBALS['tpc_t'] = array( 0, 0 );
function t( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['tpc_t'][0]++; echo "  ✅ $label\n"; } else { $GLOBALS['tpc_t'][1]++; echo "  ❌ $label   ← $note\n"; } }

$uid = function ( $u ) use ( $wpdb ) { return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='entity_uid' AND meta_value=%s LIMIT 1", $u ) ); };
$prov = $uid( home_url( '/e/province/isfahan' ) ); $city = $uid( home_url( '/e/city/isfahan' ) );
$mon  = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('tp_monument','tp_ancient_site') AND post_status='publish' ORDER BY ID LIMIT 1" );

/* برچسب‌های یک صفحه */
$tags_of = function ( $pid, $lang ) {
	$id = (int) apply_filters( 'wpml_object_id', $pid, get_post_type( $pid ), false, $lang );
	if ( ! $id ) { return array( 0, array(), false ); }
	do_action( 'wpml_switch_language', $lang );
	$GLOBALS['post'] = get_post( $id ); setup_postdata( $GLOBALS['post'] );
	if ( function_exists( 'topersia_g_rel_memo_reset' ) ) { topersia_g_rel_memo_reset(); }
	wp_set_current_user( 0 );
	topersia_pagecache_begin( $id );
	do_shortcode( '[tp_page id=' . $id . ']' );
	$over = false; $tags = topersia_pagecache_collect_tags( $over );
	do_action( 'wpml_switch_language', 'fa' );
	return array( $id, $tags, $over );
};

echo "\nبرچسب‌های صفحه\n" . str_repeat( '─', 60 ) . "\n";
$sets = array();
foreach ( array( 'استان' => $prov, 'شهر' => $city, 'جاذبه' => $mon ) as $name => $pid ) {
	foreach ( array( 'fa', 'en', 'ar' ) as $lang ) {
		list( $id, $tags, $over ) = $tags_of( $pid, $lang );
		if ( ! $id ) { echo "  · $name $lang: ترجمه نیست\n"; continue; }
		$sets[ $name ][ $lang ] = array( $id, $tags );
		$type = get_post_type( $id );
		t( "$name $lang (#$id): خودِ صفحه و نوعش هست؛ " . count( $tags ) . ' برچسب ≤ ۵۰۰ (حدود ' . strlen( implode( ',', $tags ) ) . ' بایت سرآیند)', in_array( 'tp-post-' . $id, $tags, true ) && in_array( 'tp-type-' . $type, $tags, true ) && count( $tags ) <= 500 && ! $over, implode( ' ', array_slice( $tags, 0, 6 ) ) );
	}
}

echo "\nوابستگی دقیق — ویرایش شهر ← صفحهٔ استان\n" . str_repeat( '─', 60 ) . "\n";
$child = (int) $wpdb->get_var( $wpdb->prepare( "SELECT child_object_id FROM {$wpdb->prefix}jet_rel_default WHERE rel_id=%d LIMIT 1", 233 ) );
foreach ( array( 'fa', 'en' ) as $lang ) {
	if ( empty( $sets['استان'][ $lang ] ) ) { continue; }
	list( $pid, $ptags ) = $sets['استان'][ $lang ];
	/* یک شهرِ نشان‌داده‌شدهٔ همین صفحه: نخستین برچسب tp-post-* غیر از خودِ استان که نوعش tp_city باشد */
	$victim = 0;
	foreach ( $ptags as $tg ) { if ( preg_match( '/^tp-post-(\d+)$/', $tg, $m ) && (int) $m[1] !== $pid && 'tp_city' === get_post_type( (int) $m[1] ) ) { $victim = (int) $m[1]; break; } }
	$purge = $victim ? topersia_pagecache_purge_tags_for( $victim ) : array();
	t( "$lang: ویرایش شهر #$victim برچسبی را پاک می‌کند که صفحهٔ استان #$pid هم دارد (" . implode( ' ', array_intersect( $purge, $ptags ) ) . ')', $victim && array_intersect( $purge, $ptags ), 'شهری در برچسب‌های استان نیست' );
}
$g = topersia_pagecache_purge_tags_for( $city );
t( 'پاک‌کردن شهر اصفهان همهٔ ترجمه‌هایش را هم می‌گیرد (≥ ۳ برچسب: fa · en · ar)', count( $g ) >= 3, implode( ' ', $g ) );
t( 'تغییر وضعیت (با همسایه‌ها) از پاک‌کردنِ ساده بزرگ‌تر یا برابر است', count( topersia_pagecache_purge_tags_for( $city, true ) ) >= count( topersia_pagecache_purge_tags_for( $city, false ) ) );
t( 'تغییر وضعیتِ شهر برچسبی را پاک می‌کند که صفحهٔ استانش دارد (نیای راه‌گذر/والد)', ! empty( $sets['استان']['fa'] ) && array_intersect( topersia_pagecache_purge_tags_for( $city, true ), $sets['استان']['fa'][1] ) );

echo "\nسقف\n" . str_repeat( '─', 60 ) . "\n";
add_filter( 'topersia_pagecache_tag_cap', function () { return 20; } );
list( $id, $small, $over ) = $tags_of( $prov, 'fa' );
remove_all_filters( 'topersia_pagecache_tag_cap' );
t( 'سقف ۲۰: شمار ≤ ۲۰، سرریز اعلام می‌شود، خودِ صفحه و نوعش می‌مانند و برچسب نوعِ اعضا (tp-type-tp_city) جایگزین است', count( $small ) <= 20 && $over && in_array( 'tp-post-' . $id, $small, true ) && in_array( 'tp-type-tp_city', $small, true ), implode( ' ', $small ) );

echo "\nمیزبان\n" . str_repeat( '─', 60 ) . "\n";
$calls = array( 'tag' => 0, 'purge' => 0, 'nocache' => 0 );
t( 'بی LiteSpeed (WAMP): اتصال غیرفعال و فراخوانی رابط عمومی بی هیچ اثر', ! topersia_pagecache_litespeed() && null === do_action( 'topersia_pagecache_tags', array( 'tp-post-1' ), array() ) );
$spy_tag = function ( $t ) use ( &$calls ) { $calls['tag']++; };
$spy_pg  = function ( $t ) use ( &$calls ) { $calls['purge']++; };
$spy_nc  = function ( $r ) use ( &$calls ) { $calls['nocache']++; };
add_action( 'litespeed_tag_add', $spy_tag ); add_action( 'litespeed_purge', $spy_pg ); add_action( 'litespeed_control_set_nocache', $spy_nc );
do_action( 'topersia_pagecache_tags', array( 'a', 'b', 'c' ), array() ); do_action( 'topersia_pagecache_purge', array( 'a', 'b' ) ); do_action( 'topersia_pagecache_nocache', 'x' );
t( 'با LiteSpeed (تقلید): هر برچسب یک litespeed_tag_add، هر پاک‌کردن یک litespeed_purge، nocache یک بار', 3 === $calls['tag'] && 2 === $calls['purge'] && 1 === $calls['nocache'], json_encode( $calls ) );
remove_action( 'litespeed_tag_add', $spy_tag ); remove_action( 'litespeed_purge', $spy_pg ); remove_action( 'litespeed_control_set_nocache', $spy_nc );
t( 'پس از برداشتن شنونده‌ها دوباره بی‌اثر', ! topersia_pagecache_litespeed() );

echo "\nبیرون از کش\n" . str_repeat( '─', 60 ) . "\n";
$nc = 0; $spy = function () use ( &$nc ) { $nc++; }; add_action( 'topersia_pagecache_nocache', $spy );
$u = (int) $wpdb->get_var( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='_tp_test_account' LIMIT 1" );
if ( $u ) { wp_set_current_user( $u ); $r = topersia_pagecache_begin( $city ); t( 'کاربر واردشده: برچسب جمع نمی‌شود و nocache می‌خواهد', ! $r && 1 === $nc && empty( topersia_pagecache_state()['on'] ) ); wp_set_current_user( 0 ); }
else { echo "  · حساب آزمون نیست؛ بند کاربر واردشده رد شد\n"; }
$nc = 0; $old = get_option( 'elementor_maintenance_mode_mode', '' ); $had = false !== get_option( 'elementor_maintenance_mode_mode', false );
add_filter( 'pre_option_elementor_maintenance_mode_mode', function () { return 'coming_soon'; } );
$r = topersia_pagecache_begin( $city );
t( '«به‌زودی» برای مهمان: برچسب جمع نمی‌شود و nocache می‌خواهد (گزینه‌ای نوشته نشد)', ! $r && 1 === $nc && empty( topersia_pagecache_state()['on'] ) );
remove_all_filters( 'pre_option_elementor_maintenance_mode_mode' );
remove_action( 'topersia_pagecache_nocache', $spy );

echo "\nگذشته: {$GLOBALS['tpc_t'][0]}   افتاده: {$GLOBALS['tpc_t'][1]}\n";
exit( $GLOBALS['tpc_t'][1] ? 1 : 0 );
