<?php
/**
 * وضعیت کاربر آزمون و خلاصه‌ها — فقط topersia.local.  ۴٫۳٫۰ پذیرش
 *
 *   php tests/local-state.php [post_id …]      → JSON روی stdout
 *
 * خودش چیزی نمی‌نویسد (`get_cached()` خلاصه‌ساز کشِ منقضیِ متا را خودش
 * برمی‌دارد — همان کاری که هر بار صفحه می‌کند). `tests/user-flows.js` پیش و
 * پس از هر جریان صدایش می‌زند و فرق را می‌سنجد:
 *   saved      ردیف‌های جدول ذخیرهٔ user-core برای کاربر آزمون
 *   followed   متای `_topersia_followed`
 *   reviews    ردیف‌های JetReviews که نویسنده‌شان کاربر آزمون است
 *   ai         مصرف امروزِ کاربر و سایت، و سقف‌ها
 *   summaries  کش خلاصه‌ساز در هر دو جایش: شمار و آخرین به‌روزرسانیِ جدول،
 *              شمار و بزرگ‌ترین شناسهٔ متای `_tpas_ai_*`، و نوع‌های ذخیرهٔ
 *              هر پست خواسته‌شده — از راه `TP_AI_Summary::get_cached()`
 *              خودِ خلاصه‌ساز، همان که صفحه می‌خواند.
 *
 * چرا هر دو جا: در جدولِ نسخهٔ زنده (و رونوشت محلی‌اش) کلید یکتای کهنهٔ
 * `post_language (post_id, language)` مانده، پس نکات و برنامه در جدول
 * نمی‌نشینند و خلاصه‌ساز آن‌ها را فقط در متای پست نگه می‌دارد.
 */

require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) {
	fwrite( STDERR, "فقط نسخهٔ محلی\n" );
	exit( 2 );
}

global $wpdb;

$user = get_user_by( 'login', 'tp_test_430' );
$uid  = $user ? (int) $user->ID : 0;
$out  = array( 'user' => $uid );

$saved        = $wpdb->prefix . 'topersia_saved';
$out['saved'] = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$saved} WHERE user_id = %d ORDER BY post_id", $uid ) ) );

$followed        = get_user_meta( $uid, '_topersia_followed', true );
$out['followed'] = array_map( 'intval', is_array( $followed ) ? array_values( $followed ) : array() );

$reviews        = $wpdb->prefix . 'jet_reviews';
$out['reviews'] = array();

foreach ( (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, post_id, post_type, author, approved, rating, type_slug, date FROM {$reviews} WHERE author = %d ORDER BY id", $uid ), ARRAY_A ) as $r ) {
	$out['reviews'][] = array(
		'id'        => (int) $r['id'],
		'post_id'   => (int) $r['post_id'],
		'post_type' => (string) $r['post_type'],
		'approved'  => (int) $r['approved'],
		'rating'    => (float) $r['rating'],
		'type_slug' => (string) $r['type_slug'],
		'date'      => (string) $r['date'],
	);
}

$out['ai'] = array(
	'user_used' => function_exists( 'topersia_g_ai_user_used' ) ? topersia_g_ai_user_used( $uid ) : null,
	'site_used' => function_exists( 'topersia_g_ai_site_used' ) ? topersia_g_ai_site_used() : null,
	'caps'      => function_exists( 'topersia_g_ai_caps' ) ? topersia_g_ai_caps() : null,
	'internal'  => class_exists( 'TP_AI_Summary' ) ? TP_AI_Summary::engine_is_internal() : null,
);

$sum              = $wpdb->prefix . 'topersia_ai_summaries';
$like             = $wpdb->esc_like( '_tpas_ai_' ) . '%';
$out['summaries'] = array(
	'rows'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sum}" ),
	'updated'  => (string) $wpdb->get_var( "SELECT MAX(updated_at) FROM {$sum}" ),
	'meta'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $like ) ),
	'meta_max' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(meta_id) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $like ) ),
	'posts'    => array(),
);

foreach ( array_slice( $argv, 1 ) as $pid ) {
	$pid = absint( $pid );
	if ( $pid && class_exists( 'TP_AI_Summary' ) ) {
		$out['summaries']['posts'][ $pid ] = array();
		foreach ( array( 'summary', 'keypoints', 'plan' ) as $type ) {
			if ( TP_AI_Summary::get_cached( $pid, $type, 'fa' ) ) {
				$out['summaries']['posts'][ $pid ][] = $type . ':fa';
			}
		}
	}
}

echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE );
