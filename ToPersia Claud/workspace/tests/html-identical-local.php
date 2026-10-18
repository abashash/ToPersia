<?php
/**
 * SC1 گام اول (P5-D369): HTML صفحه‌ها پیش و پس از تغییر بایت‌به‌بایت یکی است — فقط محلی.
 *
 *   php tests/html-identical-local.php
 *
 * «پیش» = پرچم SC1 خاموش (صافی `topersia_g_sc1` ← false: بی حافظۀ پیمایش و بی پیش‌بارگذاری،
 * یعنی رفتار پیش از تغییر). «پس» = پرچم روشن. هر صفحه در fa، en و ar، هر بار با کش شیء خالی
 * (سرد) و حافظۀ درون‌درخواستی خالی؛ HTML دو حالت با `===` مقایسه می‌شود و شمار پرس‌وجو
 * (SAVEQUERIES) و زمان کنار هم چاپ می‌شود (LQ-SCALE-03 · 04).
 *
 * صفحه‌ها: استان، شهر و یک جاذبه (tp_monument) — خودکار از محتوای منتشرشده، یا با
 *   --province=ID --city=ID --attraction=ID
 *
 * فقط خواندنی: هیچ پست، متا، گزینه یا transient نوشته نمی‌شود. مقدار گزینه، کلید و نام
 * کاربر چاپ نمی‌شود؛ فقط شمار، اندازه و ✅/❌. کد خروج: ۰ یکی · ۱ ناهمخوان · ۲ محیط ناقص.
 * پرونده‌های HTML دو حالت فقط هنگام ناهمخوانی در پوشۀ موقت سیستم (نه مخزن) می‌نشینند.
 */

require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

if ( ! defined( 'SAVEQUERIES' ) ) { define( 'SAVEQUERIES', true ); }

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

if ( ! function_exists( 'topersia_g_sc1_on' ) || ! function_exists( 'topersia_g_memo_reset' ) ) {
	fwrite( STDERR, "render با SC1 روی نسخۀ محلی نیست — اول همگام‌سازی\n" );
	exit( 2 );
}

global $wpdb;
$wpdb->save_queries = true;

$args = array();
foreach ( array_slice( $argv, 1 ) as $a ) {
	if ( preg_match( '/^--(province|city|attraction)=(\d+)$/', $a, $m ) ) { $args[ $m[1] ] = (int) $m[2]; }
}

/** نخستین پست منتشرشدۀ فارسی (زبان پیش‌فرض) از یک نوع؛ ترجیحاً با عنوان/نام مشخص. */
$pick = function ( $type, $prefer = '' ) {
	do_action( 'wpml_switch_language', 'fa' );
	$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids', 'suppress_filters' => false ) );
	if ( '' !== $prefer ) {
		foreach ( $ids as $id ) {
			if ( false !== mb_strpos( (string) get_the_title( $id ), $prefer ) ) { return (int) $id; }
		}
	}
	return $ids ? (int) $ids[0] : 0;
};

$pages = array(
	'استان'  => isset( $args['province'] ) ? $args['province'] : $pick( 'tp_province', 'اصفهان' ),
	'شهر'    => isset( $args['city'] ) ? $args['city'] : $pick( 'tp_city', 'اصفهان' ),
	'جاذبه'  => isset( $args['attraction'] ) ? $args['attraction'] : ($pick( 'tp_monument', 'اصفهان' ) ?: $pick( 'tp_museum' )),
);

$render = function ( $id, $lang, $on ) use ( $wpdb ) {

	remove_all_filters( 'topersia_g_sc1' );
	if ( ! $on ) { add_filter( 'topersia_g_sc1', '__return_false' ); }

	do_action( 'wpml_switch_language', $lang );
	$view = (int) apply_filters( 'wpml_object_id', $id, get_post_type( $id ), false, $lang );
	if ( ! $view ) { return null; }

	$GLOBALS['post'] = get_post( $view );
	setup_postdata( $GLOBALS['post'] );

	topersia_g_memo_reset();
	wp_cache_flush();                       /* سرد، مثل نخستین بازدید */
	$wpdb->queries = array();
	$n0 = $wpdb->num_queries;
	$t0 = microtime( true );
	$html = do_shortcode( '[tp_page id=' . $view . ']' );

	return array(
		'html' => $html,
		'q'    => $wpdb->num_queries - $n0,
		's'    => microtime( true ) - $t0,
	);
};

$bad = 0;
$rows = 0;

echo "\nSC1 — HTML پیش و پس بایت‌به‌بایت یکی؟ (پرچم خاموش = پیش · روشن = پس)\n" . str_repeat( '─', 78 ) . "\n";

foreach ( $pages as $label => $id ) {

	if ( ! $id ) { echo "  ⚠️  {$label}: پستی منتشر نشده — از --province / --city / --attraction بده\n"; $bad ++; continue; }

	foreach ( array( 'fa', 'en', 'ar' ) as $lang ) {

		$off = $render( $id, $lang, false );
		$on  = $render( $id, $lang, true );

		if ( null === $off || null === $on ) { echo "  ➖ {$label} · {$lang}: ترجمه نیست\n"; continue; }

		$rows ++;
		$same = $off['html'] === $on['html'];

		if ( ! $same ) {
			$bad ++;
			$dir = sys_get_temp_dir();
			file_put_contents( "{$dir}/tp-sc1-{$id}-{$lang}-off.html", $off['html'] );
			file_put_contents( "{$dir}/tp-sc1-{$id}-{$lang}-on.html", $on['html'] );
		}

		printf(
			"  %s %s · %s · %d KB · پرس‌وجو %d ← %d (%s%.0f%%) · زمان %.2f ← %.2f s%s\n",
			$same ? '✅' : '❌', $label, $lang, strlen( $on['html'] ) / 1024,
			$off['q'], $on['q'], $on['q'] <= $off['q'] ? '−' : '+', $off['q'] ? abs( 100 * ( $off['q'] - $on['q'] ) / $off['q'] ) : 0,
			$off['s'], $on['s'], $same ? '' : '   ← ناهمخوان (فایل‌ها در پوشۀ موقت)'
		);
	}
}

remove_all_filters( 'topersia_g_sc1' );

echo "\n  " . ( $bad ? "❌ ناهمخوان یا ناقص: {$bad}" : "✅ همۀ {$rows} جفت بایت‌به‌بایت یکی" ) . "\n";
exit( $bad ? 1 : 0 );
