<?php
/**
 * هم‌ارزی بایت‌به‌بایت خروجی صفحه پیش و پس از بهینه‌سازی — SC1 گام ۱ · P5-D369  (فقط محلی؛ فقط خواندنی)
 *
 *   wp eval-file tests/sc1-parity-local.php snapshot   ← خروجی `[tp_page]` را در پوشهٔ مبنا می‌نویسد
 *   wp eval-file tests/sc1-parity-local.php compare    ← همان را دوباره می‌سازد و بایت‌به‌بایت با مبنا می‌سنجد
 *   wp eval-file tests/sc1-parity-local.php count      ← فقط شمار پرس‌وجو و زمان (کش شیء سرد؛ SAVEQUERIES فقط در همین فرایند)
 *
 * صفحه‌ها: استان اصفهان، شهر اصفهان و یک جاذبه (بنای تاریخی) × fa · en · ar. پوشهٔ مبنا:
 * %LOCALAPPDATA%\topersia-tools\sc1\ (بیرون از git). کش شیء پیش از هر صفحه خالی می‌شود تا هر سنجش سرد باشد.
 * هیچ داده یا تنظیمی نوشته نمی‌شود.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$mode = isset( $args[0] ) ? $args[0] : 'compare';
$dir  = getenv( 'LOCALAPPDATA' ) . '/topersia-tools/sc1';
if ( ! is_dir( $dir ) ) { mkdir( $dir, 0777, true ); }
global $wpdb;
if ( ! defined( 'SAVEQUERIES' ) ) { define( 'SAVEQUERIES', true ); }

$uid = function ( $u ) use ( $wpdb ) { return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='entity_uid' AND meta_value=%s LIMIT 1", $u ) ); };
/* جاذبه‌ای که هر سه زبان را دارد؛ وگرنه نخستین */
$mon = 0;
foreach ( (array) $wpdb->get_col( "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type IN ('tp_monument','tp_ancient_site') AND p.post_status='publish' ORDER BY p.ID" ) as $cand ) {
	$have = 0; foreach ( array( 'fa', 'en', 'ar' ) as $lg ) { if ( apply_filters( 'wpml_object_id', (int) $cand, get_post_type( (int) $cand ), false, $lg ) ) { $have++; } }
	if ( 3 === $have ) { $mon = (int) $cand; break; }
	if ( ! $mon ) { $mon = (int) $cand; }
}
$pages = array( 'province' => $uid( home_url( '/e/province/isfahan' ) ), 'city' => $uid( home_url( '/e/city/isfahan' ) ), 'monument' => $mon );

$ok = 0; $bad = 0; $rows = array();
foreach ( $pages as $name => $pid ) {
	if ( ! $pid ) { echo "  ✗ $name: پست یافت نشد\n"; $bad++; continue; }
	foreach ( array( 'fa', 'en', 'ar' ) as $lang ) {
		$id = (int) apply_filters( 'wpml_object_id', $pid, get_post_type( $pid ), false, $lang );
		if ( ! $id ) { echo "  · $name $lang: ترجمه نیست\n"; continue; }
		do_action( 'wpml_switch_language', $lang );
		$GLOBALS['post'] = get_post( $id ); setup_postdata( $GLOBALS['post'] );
		if ( ! wp_using_ext_object_cache() ) { wp_cache_flush(); }
		if ( function_exists( 'topersia_g_rel_memo_reset' ) ) { topersia_g_rel_memo_reset(); }
		/* کش درون‌فرایندیِ خودِ JetEngine هم خالی: بی آن، هر صفحه از رابطه‌هایی بهره می‌برد که صفحهٔ پیشین پر کرده (سنجش سرد نمی‌ماند) */
		if ( function_exists( 'jet_engine' ) && isset( jet_engine()->relations ) ) { foreach ( (array) jet_engine()->relations->get_active_relations() as $jr ) { if ( is_object( $jr ) && method_exists( $jr, 'reset_db_cache' ) ) { $jr->reset_db_cache(); } } }
		$wpdb->queries = array(); $n0 = $wpdb->num_queries; $t0 = microtime( true );
		$html = do_shortcode( '[tp_page id=' . $id . ']' );
		$dt = microtime( true ) - $t0; $nq = $wpdb->num_queries - $n0; $db = 0; foreach ( $wpdb->queries as $q ) { $db += $q[1]; }
		$file = "$dir/$name-$lang.html";
		$rows[] = sprintf( '%-9s %s #%-5d %5d پرس‌وجو · %.2f s (پایگاه %.2f s) · %d KB', $name, $lang, $id, $nq, $dt, $db, strlen( $html ) / 1024 );
		if ( 'snapshot' === $mode ) { file_put_contents( $file, $html ); $ok++; }
		elseif ( 'compare' === $mode ) {
			$base = is_file( $file ) ? file_get_contents( $file ) : null;
			if ( null === $base ) { echo "  ✗ $name $lang: مبنا نیست\n"; $bad++; }
			elseif ( $base === $html ) { echo "  ✅ $name $lang: بایت‌به‌بایت یکی (" . strlen( $html ) . " بایت · md5 " . substr( md5( $html ), 0, 8 ) . ")\n"; $ok++; }
			else { $i = 0; $l = min( strlen( $base ), strlen( $html ) ); while ( $i < $l && $base[ $i ] === $html[ $i ] ) { $i++; } echo "  ❌ $name $lang: تفاوت از بایت $i — مبنا «" . substr( $base, max( 0, $i - 40 ), 90 ) . "» ← تازه «" . substr( $html, max( 0, $i - 40 ), 90 ) . "»\n"; $bad++; }
		} else { $ok++; }
	}
}
do_action( 'wpml_switch_language', 'fa' );
echo "\n" . implode( "\n", $rows ) . "\n";
echo "\nگذشته: $ok   افتاده: $bad\n";
exit( $bad ? 1 : 0 );
