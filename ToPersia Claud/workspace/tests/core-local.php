<?php
/**
 * ادعاهای topersia-core ۴٫۱۱٫۰ روی وردپرسِ واقعیِ محلی — SPRINT-4.4 فاز ۲.
 *
 *   php tests/core-local.php
 *
 * نگهبان: فقط topersia.local، محیط local، پایگاه topersia_local. هر دادۀ
 * آزمون همین‌جا ساخته و در پایان برگردانده یا پاک می‌شود — پست موقت، متا،
 * ردیف رابطه، گزینه‌های سرویس‌ها. هر ادعای تازه جهش دارد: یک بار روی رفتار
 * واقعی و یک بار روی نسخۀ جهش‌یافته (همان باگ یا رفتار پیشین) که باید بیفتد.
 *
 *   ۲٫۲ سرویس‌ها   رمزنگاری، autoload=no، ثابت wp-config برنده، نشت کلید با
 *                  کلید ساختگی، پرچم تجاری، انتقال، آزمون اتصال
 *   ۲٫۳ کش         hit/miss، انقضا، کهنه به‌جای خطا، هش محتوا، ابطال با متا،
 *                  خروجی گرانِ خلاصه‌ساز کهنه علامت می‌خورد، جدول (فقط محلی)
 *   ۲٫۴ شهرستان    ۳۰۱ به استانِ منتشرشده، صفحۀ استثنا، والدِ پیش‌نویس
 *   ۲٫۵ لاگ        منبع واقعی ctx، شناسۀ عنصر المنتور
 *   ۲٫۶ تاکردن     قاعده، متای _tp_search_fold، جست‌وجوی سایت
 *   ۲٫۸ نسخه       هر <link>/<script> چهار افزونه با ?ver= برابر زمان پرونده
 *
 * کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص
 */

require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

if ( ! function_exists( 'topersia_api' ) || ! function_exists( 'topersia_cache_remember' ) || ! function_exists( 'topersia_search_fold' ) ) {
	fwrite( STDERR, "هستۀ ۴٫۱۱٫۰ روی نسخۀ محلی نیست — اول همگام‌سازی\n" );
	exit( 2 );
}

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function t( $label, $ok, $note = '' ) {
	if ( $ok ) { $GLOBALS['pass'] ++; echo "  ✅ {$label}\n"; }
	else       { $GLOBALS['fail'] ++; echo "  ❌ {$label}" . ( '' !== $note ? "   ← {$note}" : '' ) . "\n"; }
}

/** ادعا روی واقعی درست، روی جهش نادرست. */
function mut( $label, callable $claim, $real, $mutant, $note = '' ) {
	$ok  = (bool) $claim( $real );
	$bad = (bool) $claim( $mutant );
	t( $label, $ok && ! $bad, ( $ok ? '' : 'روی رفتار واقعی افتاد' ) . ( $bad ? ' · جهش را نگرفت' : '' ) . ( '' !== $note ? ' · ' . $note : '' ) );
}

function info( $s ) { echo "  · {$s}\n"; }

function section( $s ) { echo "\n{$s}\n" . str_repeat( '─', 78 ) . "\n"; }

/* ── آنچه پس از آزمون برمی‌گردد ── */
$restore_options = array();
foreach ( array( TOPERSIA_SERVICES_OPTION, TOPERSIA_SERVICES_USAGE, TOPERSIA_COMMERCIAL_OPTION, 'topersia_services_migrated' ) as $o ) {
	$restore_options[ $o ] = get_option( $o, null );
}
$cleanup = array( 'posts' => array(), 'meta' => array(), 'rel' => array() );

/* کارهای صفی که پست‌های موقت می‌سازند (گالری تجمیعی ۲ج٫۵) — در پایان اجرا و پاک */
require_once __DIR__ . '/queue-sweep.php';
$cleanup['queue'] = tp_queue_floor();

register_shutdown_function( function () use ( &$restore_options, &$cleanup ) {
	foreach ( $restore_options as $o => $v ) {
		if ( null === $v ) { delete_option( $o ); } else { update_option( $o, $v, false ); }
	}
	foreach ( $cleanup['rel'] as $r ) {
		$rel = topersia_relation( $r[0] );
		if ( $rel ) { $rel->delete_rows( $r[1], $r[2] ); }
	}
	foreach ( $cleanup['meta'] as $m ) {
		delete_post_meta( $m[0], $m[1] );
	}
	foreach ( $cleanup['posts'] as $p ) {
		wp_delete_post( $p, true );
	}
	tp_queue_sweep( $cleanup['queue'] );
	topersia_api_flush();
} );

/** پست موقت منتشرشده. */
function tmp_post( $type, $title, $status = 'publish', array $meta = array() ) {
	$id = wp_insert_post( array( 'post_type' => $type, 'post_status' => $status, 'post_title' => $title, 'post_content' => '' ), true );
	if ( is_wp_error( $id ) ) {
		fwrite( STDERR, 'tmp_post: ' . $id->get_error_message() . "\n" );
		exit( 2 );
	}
	foreach ( $meta as $k => $v ) { update_post_meta( $id, $k, $v ); }
	$GLOBALS['cleanup']['posts'][] = $id;
	return (int) $id;
}

/** درخواست HTTP به خودِ سایت محلی. */
function local_get( $path, array $args = array() ) {
	$res = wp_remote_get( home_url( $path ), array_merge( array( 'timeout' => 240, 'redirection' => 0 ), $args ) );
	return is_wp_error( $res ) ? array( 'code' => 0, 'body' => '', 'headers' => array(), 'err' => $res->get_error_message() )
		: array( 'code' => (int) wp_remote_retrieve_response_code( $res ), 'body' => (string) wp_remote_retrieve_body( $res ), 'headers' => wp_remote_retrieve_headers( $res ) );
}

$GLOBALS['cleanup'] = &$cleanup;

echo "topersia-core " . TOPERSIA_CORE_VERSION . " — روی topersia.local\n";

/* ══ ۲٫۶ تاکردن  P5-D265 ═══════════════════════════════════════════════ */
section( '۲٫۶ — تاکردن «ۀ»، «هٔ» و «ه»  P5-D265' );

$heh  = "\u{06C0}";
$hehh = "\u{0647}\u{0654}";
$fold_claim = function ( callable $f ) use ( $heh, $hehh ) {
	return $f( 'هم' . $heh ) === $f( 'هم' . $hehh ) && $f( 'هم' . $heh ) === $f( 'همه' )
		&& $f( 'علي' ) === $f( 'علی' ) && $f( 'كاشان' ) === $f( 'کاشان' )
		&& $f( 'اِصفَهان' ) === $f( 'اصفهان' ) && $f( '۱۲' ) === '12' && $f( '١٢' ) === '12'
		&& $f( "نوش\u{200C}آباد" ) === $f( 'نوش آباد' ) && $f( 'ABC' ) === 'abc' && $f( 'کاشان' ) !== $f( 'کرمان' );
};
$old_fold = function ( $s ) { return mb_strtolower( (string) $s ); };   /* بی تاکردن: جست‌وجوی پیش از ۴٫۱۱٫۰ */
mut( 'قاعده: «ۀ»، «هٔ» و «ه» یکی؛ ي/ى←ی · ك←ک · بی اعراب · رقم فارسی و عربی ← لاتین · بی نیم‌فاصله و فاصله · حروف کوچک',
	$fold_claim, 'topersia_search_fold', $old_fold );

$pf = tmp_post( 'post', 'هم' . $heh . ' آزمون TPFOLD', 'publish' );
$pf_meta = (string) get_post_meta( $pf, TOPERSIA_SEARCH_FOLD_META, true );
t( 'متای _tp_search_fold در save_post: عنوان و خلاصۀ تاشده',
	'' !== $pf_meta && 0 === strpos( $pf_meta, topersia_search_fold( 'هم' . $heh . ' آزمون TPFOLD' ) . '|' ), $pf_meta );

$search = function ( $with_filter ) use ( $hehh ) {
	if ( ! $with_filter ) { remove_filter( 'posts_search', 'topersia_search_fold_posts_search', 20 ); }
	$q = new WP_Query( array( 's' => 'هم' . $hehh . ' آزمون TPFOLD', 'post_type' => 'post', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 5, 'suppress_filters' => false ) );
	if ( ! $with_filter ) { add_filter( 'posts_search', 'topersia_search_fold_posts_search', 20, 2 ); }
	return $q->posts;
};
mut( 'جست‌وجوی سایت: «همهٔ» (دونویسه) پستِ «همۀ» (یک‌نویسه) را پیدا می‌کند؛ بی صافی posts_search نه',
	function ( $ids ) use ( $pf ) { return in_array( $pf, (array) $ids, true ); }, $search( true ), $search( false ) );

/* فقط محلی تا ممیزی پیوست الف بند ۶ — دروازۀ خاموش همان «بیرون از local» است */
$fold_gate = function ( $enabled ) {
	$f = function () use ( $enabled ) { return $enabled; };
	add_filter( 'topersia_search_fold_enabled', $f );
	$id   = tmp_post( 'post', 'آزمون دروازۀ تاکردن TPGATE', 'publish' );
	$q    = new WP_Query();
	$q->parse_query( array( 's' => 'TPGATE' ) );
	$in   = " AND ((wp_posts.post_title LIKE '%TPGATE%'))";
	$out  = array( 'meta' => get_post_meta( $id, TOPERSIA_SEARCH_FOLD_META, true ), 'search' => topersia_search_fold_posts_search( $in, $q ) === $in );
	remove_filter( 'topersia_search_fold_enabled', $f );
	return $out;
};
mut( 'پیوست الف بند ۶: بیرون از local نه متای _tp_search_fold نوشته می‌شود نه جست‌وجوی سایت به آن نگاه می‌کند؛ جهش: دروازۀ همیشه‌روشن',
	function ( $r ) { return '' === $r['meta'] && $r['search']; }, $fold_gate( false ), $fold_gate( true ) );
$cache_gate = function ( $enabled ) {
	$f = function () use ( $enabled ) { return $enabled; };
	add_filter( 'topersia_cache_table_enabled', $f );
	$store = topersia_cache_store_for( 'geocoding' );
	remove_filter( 'topersia_cache_table_enabled', $f );
	return $store;
};
mut( 'پیوست الف بند ۶: بیرون از local آدرس در transient است، نه جدول کش هسته؛ جهش: دروازۀ همیشه‌روشن',
	function ( $store ) { return 'table' !== $store; }, $cache_gate( false ), $cache_gate( true ) );
$core_inc = dirname( ( new ReflectionFunction( 'topersia_search_fold' ) )->getFileName() );
t( 'پیش‌فرض هر دو دروازه دقیقاً «محیط local» است — نه ثابت، نه گزینۀ پیشخوان',
	false !== strpos( (string) file_get_contents( $core_inc . '/topersia-fold.php' ), "apply_filters( 'topersia_search_fold_enabled', 'local' === wp_get_environment_type() )" )
	&& false !== strpos( (string) file_get_contents( $core_inc . '/topersia-cache.php' ), "apply_filters( 'topersia_cache_table_enabled', 'local' === wp_get_environment_type() )" ) );

/* ══ ۲٫۳ کش مرکزی  P5-D250 ═══════════════════════════════════════════ */
section( '۲٫۳ — کش مرکزی  P5-D250' );

$calls = 0;
$fetch = function () use ( &$calls ) { $calls ++; return array( 'value' => 'v' . $calls, 'ttl' => 1 ); };
$k     = array( 'tp-test', wp_generate_password( 8, false ) );
$a     = topersia_cache_remember( 'nearby', $k, $fetch );
$s1    = topersia_cache_last()['status'];
$b     = topersia_cache_remember( 'nearby', $k, $fetch );
$s2    = topersia_cache_last()['status'];
sleep( 2 );
$c     = topersia_cache_remember( 'nearby', $k, $fetch );
$s3    = topersia_cache_last()['status'];
$d     = topersia_cache_remember( 'nearby', $k, function () { return new WP_Error( 'x', 'down' ); }, array( 'ttl' => 0, 'rebuild' => true ) );
$s4    = topersia_cache_last()['status'];
t( 'کش: نخست miss، سپس hit بی دریافت؛ پس از TTL دریافت تازه؛ خطای بالادست ← همان مقدار پیشین (کهنه به‌جای خطا)',
	'v1' === $a && 'miss' === $s1 && 'v1' === $b && 'hit' === $s2 && 'v2' === $c && 'miss' === $s3 && 'v2' === $d && 'stale-error' === $s4 && 2 === $calls,
	wp_json_encode( array( $a, $s1, $b, $s2, $c, $s3, $d, $s4, $calls ) ) );

/* کدام متا هش را عوض می‌کند: FIELDMAP و ورودی خلاصه‌ساز آری؛ سئو و شمارنده نه */
$ph = tmp_post( 'tp_city', 'شهر آزمون TPHASH', 'publish', array( 'population' => '1000', 'overview_text' => 'مرور نخست', 'rank_math_seo_score' => '10' ) );
$changes = function ( $key, $value ) use ( $ph ) {
	$before = topersia_content_hash( $ph, true );   /* همان فهرست فیلدی که «پس» با آن ساخته می‌شود */
	update_post_meta( $ph, $key, $value );
	return $before !== topersia_content_hash( $ph );
};
t( 'هش: متای FIELDMAP (population) هش را عوض می‌کند؛ متای سئو (rank_math_seo_score) نه — فقط محتوا، نه هر متایی',
	$changes( 'population', '2000' ) && ! $changes( 'rank_math_seo_score', '55' ) );
$overview_claim = function ( $with_summarizer ) use ( $changes ) {
	if ( ! $with_summarizer ) { remove_filter( 'topersia_content_hash_fields', array( 'TP_AI_Core_Bridge', 'hash_fields' ), 20 ); }
	$r = $changes( 'overview_text', 'مرور ' . wp_generate_password( 6, false ) );
	if ( ! $with_summarizer ) { add_filter( 'topersia_content_hash_fields', array( 'TP_AI_Core_Bridge', 'hash_fields' ), 20 ); }
	return $r;
};
mut( 'هش: متن مرور (ورودی خلاصه‌ساز، بیرون از FIELDMAP) هش را عوض می‌کند — خلاصه‌ساز ورودی‌هایش را به فهرست می‌افزاید؛ جهش: بی آن صافی، ویرایش مرور خلاصه را کهنه نمی‌کرد',
	function ( $changed ) { return $changed; }, $overview_claim( true ), $overview_claim( false ) );

/* هش محتوا و ابطال: تغییر یک متای ورودی ← هش تازه ← خروجی گران «کهنه» */
$pc   = tmp_post( 'tp_city', 'شهر آزمون TPCACHE', 'publish', array( 'short_summary' => 'خلاصۀ نخست' ) );
$h0   = topersia_content_hash( $pc );
$gen  = 0;
$ai   = function () use ( &$gen ) { $gen ++; return array( 'value' => 'خروجی ' . $gen ); };
$o1   = topersia_cache_remember( 'ai.text', array( $pc, 'summary' ), $ai, array( 'post' => $pc, 'lang' => 'fa', 'expensive' => true ) );
update_post_meta( $pc, 'short_summary', 'خلاصۀ دوم' );
$h1   = topersia_content_hash( $pc );
$o2   = topersia_cache_remember( 'ai.text', array( $pc, 'summary' ), $ai, array( 'post' => $pc, 'lang' => 'fa', 'expensive' => true ) );
$s_o2 = topersia_cache_last()['status'];
$o3   = topersia_cache_remember( 'ai.text', array( $pc, 'summary' ), $ai, array( 'post' => $pc, 'lang' => 'fa', 'expensive' => true, 'rebuild' => true ) );
$s_o3 = topersia_cache_last()['status'];
t( 'هش: تغییر یک متای ورودی (خلاصۀ کوتاه) هش را عوض می‌کند؛ خروجی گران پاک نمی‌شود و «کهنه» برمی‌گردد بی دریافت؛ فقط با اقدام مجاز (rebuild) از نو',
	$h0 !== $h1 && 'خروجی 1' === $o1 && 'خروجی 1' === $o2 && 'stale' === $s_o2 && 'خروجی 2' === $o3 && 'miss' === $s_o3 && 2 === $gen,
	wp_json_encode( array( substr( $h0, 0, 8 ), substr( $h1, 0, 8 ), $o1, $o2, $s_o2, $o3, $s_o3, $gen ) ) );

/* جهش: بی قلاب ابطال (هش پیشین سر جایش) ← «کهنه» دیده نمی‌شود */
$stale_claim = function ( $post ) use ( $ai ) {
	topersia_cache_remember( 'ai.text', array( $post, 'summary' ), $ai, array( 'post' => $post, 'lang' => 'fa', 'expensive' => true, 'rebuild' => true ) );
	$before = get_post_meta( $post, TOPERSIA_CONTENT_HASH_META, true );
	update_post_meta( $post, 'short_summary', 'خلاصۀ ' . wp_generate_password( 6, false ) );
	if ( ! empty( $GLOBALS['tp_mut_no_invalidate'] ) ) { update_post_meta( $post, TOPERSIA_CONTENT_HASH_META, $before ); }
	topersia_cache_remember( 'ai.text', array( $post, 'summary' ), $ai, array( 'post' => $post, 'lang' => 'fa', 'expensive' => true ) );
	return 'stale' === topersia_cache_last()['status'];
};
$real_stale = $stale_claim( $pc );
$GLOBALS['tp_mut_no_invalidate'] = true;
$mut_stale  = $stale_claim( $pc );
unset( $GLOBALS['tp_mut_no_invalidate'] );
t( 'ابطال: قلاب متا هش را کثیف می‌کند — جهشِ بی ابطال (هش پیشین می‌ماند) خروجی را تازه می‌پندارد و ادعا می‌افتد',
	$real_stale && ! $mut_stale );

/* خلاصه‌ساز: انبارۀ جدول خودش؛ get_cached نشان «کهنه» دارد — پذیرش فاز ۲ */
if ( class_exists( 'TP_AI_Core_Bridge' ) ) {
	$store = TP_AI_Core_Bridge::store( $pc, 'summary', 'fa' );
	$txt   = topersia_cache_remember( 'ai.text', array( $pc, 'summary', 'tpas' ), function () { return array( 'value' => 'خلاصۀ هوش مصنوعی آزمون', 'meta' => array( 'provider' => 'test', 'model' => 'test' ) ); }, array( 'post' => $pc, 'lang' => 'fa', 'expensive' => true, 'rebuild' => true, 'store' => $store ) );
	$fresh = TP_AI_Summary::get_cached( $pc, 'summary', 'fa' );
	update_post_meta( $pc, 'short_summary', 'خلاصۀ سوم' );
	$stale = TP_AI_Summary::get_cached( $pc, 'summary', 'fa' );
	t( 'خلاصه‌ساز: خروجی در جدول خودش با هش؛ تغییر یک متا ← get_cached همان متن را با stale=true می‌دهد — پاک نشد  (پذیرش فاز ۲)',
		'خلاصۀ هوش مصنوعی آزمون' === $txt && is_array( $fresh ) && false === $fresh['stale'] && is_array( $stale ) && 'خلاصۀ هوش مصنوعی آزمون' === $stale['text'] && true === $stale['stale'],
		wp_json_encode( array( $fresh ? $fresh['stale'] : null, $stale ? $stale['stale'] : null ) ) );
	TP_AI_DB::delete_post_summaries( $pc );
} else {
	t( 'خلاصه‌ساز ۱٫۲٫۰ روی نسخۀ محلی فعال است', false, 'TP_AI_Core_Bridge نیست' );
}

/* جدول کش هسته — فقط محلی تا ممیزی پیوست الف بند ۶ */
topersia_cache_install();
global $wpdb;
$has_table = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', topersia_cache_table() ) );
$geo_calls = 0;
$gk        = array( 'tp-test-geo', wp_generate_password( 6, false ) );
topersia_cache_remember( 'geocoding', $gk, function () use ( &$geo_calls ) { $geo_calls ++; return array( 'value' => array( 'lat' => 1, 'lng' => 2 ) ); } );
$gv = topersia_cache_remember( 'geocoding', $gk, function () use ( &$geo_calls ) { $geo_calls ++; return array( 'value' => 'nope' ); } );
$row = $wpdb->get_row( $wpdb->prepare( 'SELECT fresh_until FROM ' . topersia_cache_table() . ' WHERE ckey = %s', topersia_cache_last()['key'] ) );
t( 'جدول کش هسته (محلی): آدرس در جدول، دائمی (fresh_until = ۰)، بار دوم بی دریافت؛ بیرون از local جدول ساخته نمی‌شود',
	$has_table && 'local' === wp_get_environment_type() && topersia_cache_table_enabled() && is_array( $gv ) && 1 === $geo_calls && $row && 0 === (int) $row->fresh_until );
$wpdb->delete( topersia_cache_table(), array( 'ckey' => topersia_cache_last()['key'] ) );

/* ══ ۲٫۲ سرویس‌ها  P5-D249 ════════════════════════════════════════════ */
section( '۲٫۲ — مدیریت مرکزی سرویس‌های خارجی  P5-D249' );

$fake = 'TPFAKEKEY-' . wp_generate_password( 24, false ) . '-Q7W3';
$blob = topersia_services_encrypt( $fake );
$tamp = substr( $blob, 0, -4 ) . ( 'AAAA' === substr( $blob, -4 ) ? 'BBBB' : 'AAAA' );
t( 'رمز: sodium_crypto_secretbox با کلیدِ مشتق از wp_salt(auth) — رفت‌وبرگشت درست، متن آشکار در پوشه نیست، دست‌کاری ← باز نمی‌شود',
	0 === strpos( $blob, 'v1:' ) && false === strpos( $blob, $fake ) && $fake === topersia_services_decrypt( $blob ) && false === topersia_services_decrypt( $tamp ) );

topersia_services_save_row( 'routing', array( 'provider' => 'neshan-routing', 'enabled' => 1, 'key' => $fake ) );
$raw      = get_option( TOPERSIA_SERVICES_OPTION );
$autoload = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", TOPERSIA_SERVICES_OPTION ) );
$cfg      = topersia_api( 'routing' );
t( 'ذخیره: option با autoload خاموش؛ کلید رمزشده، فقط چهار نویسۀ آخر آشکار؛ topersia_api کلید را فقط سمت سرور می‌دهد',
	in_array( $autoload, array( 'no', 'off' ), true ) && false === strpos( wp_json_encode( $raw ), $fake ) && 'Q7W3' === $raw['routing']['key_tail']
	&& $fake === $cfg['key'] && 'panel' === $cfg['key_source'] && 'active' === $cfg['status'], 'autoload=' . $autoload );

/* ثابت wp-config برنده است و قفل */
add_filter( 'topersia_service_adapters', function ( $a ) {
	$a['tp-test-const'] = array( 'service' => 'routing', 'label' => 'آزمون ثابت', 'needs_key' => true, 'key_const' => 'TP_TEST_SERVICE_CONST_KEY', 'endpoint' => 'https://example.invalid/', 'free_commercial' => true );
	return $a;
}, 50 );
define( 'TP_TEST_SERVICE_CONST_KEY', 'CONSTKEY-' . wp_generate_password( 12, false ) . '-C0NS' );
topersia_services_save_row( 'routing', array( 'provider' => 'tp-test-const', 'enabled' => 1, 'key' => 'PANEL-KEY-SHOULD-NOT-SAVE' ) );
$cc  = topersia_api( 'routing' );
$raw = get_option( TOPERSIA_SERVICES_OPTION );
t( 'اولویت: ثابت wp-config بر پیشخوان برنده است؛ کلید پیشخوان کنار ثابت ذخیره نمی‌شود',
	TP_TEST_SERVICE_CONST_KEY === $cc['key'] && 'config' === $cc['key_source'] && 'C0NS' === $cc['key_tail']
	&& false === strpos( wp_json_encode( $raw ), 'PANEL-KEY-SHOULD-NOT-SAVE' ) );
topersia_services_save_row( 'routing', array( 'provider' => 'neshan-routing', 'enabled' => 1 ) );

/* نشت کلید: کلید ساختگی در هیچ خروجی — HTML، REST، پیشخوان، پاسخ آزمون، لاگ */
$test_res = topersia_service_test( 'routing' );   /* نشان در محلی بسته است — خطای شبکه؛ مهم پیامِ بی کلید است */
$admin    = get_user_by( 'login', 'tp_test_admin' );
wp_set_current_user( $admin ? $admin->ID : 0 );
ob_start();
topersia_services_page();
$admin_html = ob_get_clean();
wp_set_current_user( 0 );
$outputs = array(
	'صفحۀ استان'      => local_get( '/province/isfahan/' )['body'],
	'صفحۀ انگلیسی'    => local_get( '/en/province/isfahan/' )['body'],
	'REST هوا'        => local_get( '/wp-json/topersia/v1/weather?coords=32.6539,51.6660' )['body'],
	'REST ریشه'       => local_get( '/wp-json/' )['body'],
	'پیشخوان سرویس‌ها' => $admin_html,
	'پاسخ آزمون اتصال' => wp_json_encode( $test_res ),
	'مصرف و خطا'      => wp_json_encode( get_option( TOPERSIA_SERVICES_USAGE ) ),
	'جدول لاگ'        => (string) $wpdb->get_var( "SELECT GROUP_CONCAT( CONCAT( message, context ) SEPARATOR '\n' ) FROM " . topersia_log_table() . ' WHERE logged_at > NOW() - INTERVAL 1 HOUR' ),
	'debug.log'       => (string) @file_get_contents( WP_CONTENT_DIR . '/debug.log', false, null, max( 0, (int) @filesize( WP_CONTENT_DIR . '/debug.log' ) - 2000000 ) ),
	'http-probe.log'  => (string) @file_get_contents( TP_ROOT . '/http-probe.log' ),
);
$leaks = function ( $outs ) use ( $fake ) {
	return array_keys( array_filter( $outs, function ( $h ) use ( $fake ) { return false !== strpos( (string) $h, $fake ); } ) );
};
mut( 'نشت: کلید ساختگی در هیچ خروجی نیست — HTML فارسی و انگلیسی، REST، پیشخوان، پاسخ آزمون، ردیف مصرف، جدول لاگ، debug.log، http-probe.log؛ پیشخوان فقط ••••Q7W3. جهش: کلیدی که در یک خروجی کاشته شود دیده می‌شود',
	function ( $outs ) use ( $leaks ) { return array() === $leaks( $outs ); },
	$outputs, array_merge( $outputs, array( 'صفحۀ استان' => $outputs['صفحۀ استان'] . '<!-- ' . $fake . ' -->' ) ),
	'پیشخوان ••••Q7W3: ' . ( false !== strpos( $admin_html, '••••Q7W3' ) ? 'هست' : 'نیست' ) . ' · آزمون اتصال: ' . $test_res['message'] );
t( 'پیشخوان: کلید فقط «••••» و چهار نویسۀ آخر؛ فیلد کلید خالی (فقط‌نوشتنی)',
	false !== strpos( $admin_html, '••••Q7W3' ) && false === strpos( $admin_html, 'value="' . $fake ) );

/* پرچم تجاری */
topersia_services_save_row( 'weather', array( 'provider' => 'open-meteo', 'enabled' => 1 ) );
update_option( TOPERSIA_COMMERCIAL_OPTION, false );
topersia_api_flush();
$off0 = topersia_api( 'weather' )['status'];
update_option( TOPERSIA_COMMERCIAL_OPTION, true );
topersia_api_flush();
$om  = topersia_api( 'weather' );
topersia_services_save_row( 'weather', array( 'provider' => 'met-norway', 'enabled' => 1 ) );
$met = topersia_api( 'weather' )['status'];
update_option( TOPERSIA_COMMERCIAL_OPTION, false );
topersia_api_flush();
t( 'پرچم تجاری: Open-Meteo بی کلید پولی با پرچم روشن خاموش و با دلیل؛ MET Norway (CC BY 4.0) فعال می‌ماند؛ پرچم خاموش ← فعال',
	'active' === $off0 && 'off' === $om['status'] && false !== strpos( $om['reason'], 'تجاری' ) && 'active' === $met,
	wp_json_encode( array( $off0, $om['status'], $met ) ) );

/* انتقال یک‌باره — تنظیم قدیمی پاک نمی‌شود، بار دوم دست نمی‌زند */
update_option( TOPERSIA_SERVICES_OPTION, array(), false );
topersia_api_flush();
$legacy_before = get_option( 'tpas_settings' );
$m1 = topersia_services_migrate();
$m2 = topersia_services_migrate();
$after_rows = topersia_services_saved();
t( 'انتقال: هوا و کاشی از تنظیم قدیمی منتقل (تولید متن هوش مصنوعی دیگر در این لایه نیست)؛ بار دوم «پیش‌تر در لایه بود»؛ تنظیم خلاصه‌ساز دست نخورد',
	isset( $after_rows['weather'], $after_rows['map.tiles'] ) && ! isset( $after_rows['ai.text'] ) && 'منتقل شد' === $m1['weather'] && false !== strpos( (string) $m2['weather'], 'پیش‌تر' )
	&& get_option( 'tpas_settings' ) === $legacy_before && 'tiles/iran.pmtiles' === topersia_api( 'map.tiles' )['extra']['path'],
	wp_json_encode( $m1, JSON_UNESCAPED_UNICODE ) );

/* آزمون اتصال — سرویس‌های بی‌کلید واقعاً فراخوانده می‌شوند؛ کلیددار بی کلید «آزموده نشد» */
$report = array();
foreach ( array_keys( topersia_services_catalog() ) as $sv ) {
	$r             = topersia_service_test( $sv );
	$report[ $sv ] = ( $r['tested'] ? ( $r['ok'] ? '✓ ' : '✗ ' ) : '— ' ) . $r['message'];
}
foreach ( $report as $sv => $r ) {
	info( 'آزمون اتصال ' . $sv . ': ' . $r );
}
t( 'آزمون اتصال: هوا (MET Norway) و کاشی (PMTiles) واقعاً پاسخ دادند؛ هر سرویس کلیددار بی کلید «آزموده نشد: کلید ندارد»، نه PASS ساختگی',
	0 === strpos( $report['weather'], '✓' ) && 0 === strpos( $report['map.tiles'], '✓' )
	&& false !== strpos( $report['ai.tts'], 'آزموده نشد' ) && false !== strpos( $report['geocoding'], 'آزموده نشد' ) );

/* ۴٫۱۲٫۱ — تولید متن بیرون (خواستۀ کاربر)، ElevenLabs در صدای هوش مصنوعی، شکاف دوم با بازگشت خودکار */
$ads = topersia_service_adapters();
t( 'سرویس‌ها: ai.text در فهرست نیست (کلیدش در خلاصه‌ساز)؛ ElevenLabs آداپتور ai.tts با آزمون GET v1/models و شناسۀ صدا؛ بی کلید «آزموده نشد»',
	! isset( topersia_services_catalog()['ai.text'] ) && isset( $ads['elevenlabs'] ) && 'ai.tts' === $ads['elevenlabs']['service']
	&& 'topersia_service_test_elevenlabs' === $ads['elevenlabs']['test'] && isset( $ads['elevenlabs']['fields']['voice_id'] )
	&& 2 === count( topersia_services_providers_for( 'ai.tts' ) ) && 2 === count( topersia_services_providers_for( 'weather' ) ) );
topersia_services_save_row( 'weather', array( 'provider' => 'met-norway', 'enabled' => 0 ) );
topersia_services_save_row( 'weather' . TOPERSIA_SERVICES_ALT, array( 'provider' => 'open-meteo', 'enabled' => 1, 'use' => '1' ) );
topersia_services_save_row( 'ai.tts' . TOPERSIA_SERVICES_ALT, array( 'provider' => 'elevenlabs', 'enabled' => 1 ) );   /* پنهان‌مانده، بی use ← ساخته نمی‌شود */
mut( 'شکاف دوم: اولی خاموش ← مصرف‌کننده پشتیبان (Open-Meteo) را می‌گیرد و پیشخوان همان اولی را؛ سطر دومِ پنهان‌مانده ساخته نمی‌شود؛ جهش: بی بازگشت',
	function ( $c ) { return 'open-meteo' === $c['provider'] && ! empty( $c['fallback'] ) && ! isset( topersia_services_saved()['ai.tts' . TOPERSIA_SERVICES_ALT] ); },
	topersia_api( 'weather' ), topersia_api( 'weather', false, true ) );
topersia_services_save_row( 'weather' . TOPERSIA_SERVICES_ALT, array( 'remove' => 1 ) );
topersia_services_save_row( 'weather', array( 'provider' => 'met-norway', 'enabled' => 1 ) );

/* میزبان بسته با WP_HTTP_BLOCK_EXTERNAL (محلی: فقط api.open-meteo.com و api.met.no) */
$aq_cfg = topersia_api( 'air_quality' );
$aq     = topersia_service_test( 'air_quality' );
$aq_old = topersia_service_test_open_meteo_aq( $aq_cfg ) + array( 'tested' => true );   /* ۴٫۱۱٫۰ پیش از این بند: خودِ آزمون */
mut( 'آزمون اتصال: میزبانی که WP_HTTP_BLOCK_EXTERNAL بسته «آزموده نشد» با نام میزبان است، نه «✗ خطای شبکه»؛ جهش: فراخوانی مستقیم',
	function ( $r ) { return false === $r['tested'] && false !== strpos( $r['message'], 'WP_HTTP_BLOCK_EXTERNAL' ); }, $aq, $aq_old, $aq['message'] );

/* ══ ۲٫۴ شهرستان  CA-G G4 ═════════════════════════════════════════════ */
section( '۲٫۴ — شهرستان ← استان  CA-G G4' );

$county = get_page_by_path( 'tiran-and-karvan-county', OBJECT, 'tp_county' );
$prov   = get_page_by_path( 'isfahan', OBJECT, 'tp_province' );

if ( $county && $prov ) {
	$r301 = local_get( '/county/tiran-and-karvan-county/' );
	update_post_meta( $county->ID, TOPERSIA_G4_EXCEPTION_META, '1' );
	$cleanup['meta'][] = array( $county->ID, TOPERSIA_G4_EXCEPTION_META );
	$r200 = local_get( '/county/tiran-and-karvan-county/' );
	delete_post_meta( $county->ID, TOPERSIA_G4_EXCEPTION_META );
	$noidx = function ( $r ) { return false !== stripos( (string) ( isset( $r['headers']['x-robots-tag'] ) ? $r['headers']['x-robots-tag'] : '' ), 'noindex' ); };
	t( 'حالت ۲: /county/{slug}/ ← ۳۰۱ به استان منتشرشده، با noindex',
		301 === $r301['code'] && get_permalink( $prov->ID ) === (string) $r301['headers']['location'] && $noidx( $r301 ),
		$r301['code'] . ' ' . ( isset( $r301['headers']['location'] ) ? $r301['headers']['location'] : '' ) );
	mut( 'حالت ۳: شهرستان با متای «صفحۀ استثنا» ریدایرکت نمی‌شود و noindex نمی‌گیرد؛ بی متا همان ۳۰۱',
		function ( $r ) use ( $noidx ) { return 200 === $r['code'] && ! $noidx( $r ); }, $r200, $r301, 'کد ' . $r200['code'] );
} else {
	t( 'شهرستان و استان آزمون روی نسخۀ محلی هست', false, 'tiran-and-karvan-county یا isfahan پیدا نشد' );
}

/* والدِ پیش‌نویس: مقصد نیست — P5-D247. رابطۀ واقعی ۷۲ با دو پست موقت */
$rel = topersia_get_g3_g4_relation();
if ( $rel ) {
	$dp = tmp_post( 'tp_province', 'استان پیش‌نویس TPG4', 'draft' );
	$tc = tmp_post( 'tp_county', 'شهرستان آزمون TPG4', 'publish' );
	$rel->update( $dp, $tc );
	$cleanup['rel'][] = array( TOPERSIA_REL_PROVINCE_COUNTY, $dp, $tc );
	$target = topersia_g4_target( $tc );
	$old_target = function ( $cid ) use ( $rel ) {   /* ۴٫۱۰٫۰: نخستین والد، بی نگاه به وضعیت */
		$p = $rel->get_parents( $cid, 'ids' );
		$p = is_array( $p ) ? reset( $p ) : $p;
		return $p ? (string) get_permalink( $p ) : home_url( '/' );
	};
	mut( 'والدِ پیش‌نویس مقصد ریدایرکت نیست — خانه؛ جهشِ ۴٫۱۰٫۰ (نخستین والد) به پیش‌نویس می‌برد  P5-D247',
		function ( $url ) { return home_url( '/' ) === $url; }, $target, $old_target( $tc ), $target );
}

/* ══ ۲٫۵ لاگ ══════════════════════════════════════════════════════════ */
section( '۲٫۵ — لاگ' );

$row_js  = (object) array( 'source' => 'topersia-core.php:1623', 'context' => wp_json_encode( array( 'file' => 'https://topersia.com/assets/plugins/x/y.js', 'line' => 12, 'col' => 3 ) ) );
$row_php = (object) array( 'source' => 'topersia-core.php:1222', 'context' => wp_json_encode( array( 'file' => 'wp-content/plugins/elementor/includes/base/controls-stack.php', 'line' => 1499 ) ) );
$row_own = (object) array( 'source' => 'topersia-render.php:88', 'context' => '' );
$where_claim = function ( callable $w ) use ( $row_js, $row_php, $row_own ) {
	return 'https://topersia.com/assets/plugins/x/y.js:12' === $w( $row_js ) && 'controls-stack.php:1499' === $w( $row_php ) && 'topersia-render.php:88' === $w( $row_own );
};
mut( 'ج — ستون «↳» منبع واقعی را نشان می‌دهد (ctx.file:ctx.line)، نه خط ثبت‌کننده؛ جهش: ستون source خام',
	$where_claim, 'topersia_log_where', function ( $r ) { return (string) $r->source; } );

if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->elements_manager ) ) {
	$el = \Elementor\Plugin::$instance->elements_manager->create_element_instance( array( 'elType' => 'widget', 'widgetType' => 'heading', 'id' => 'tp1a2b3', 'settings' => array() ) );
	$got = topersia_log_elementor_element( array( array( 'function' => 'x' ), array( 'object' => $el ) ) );
	mut( 'ب — هشدار دادۀ المنتور شناسه و نام عنصر را هم ثبت می‌کند؛ پشتۀ بی عنصر المنتور هیچ',
		function ( $g ) { return is_array( $g ) && isset( $g['element_id'], $g['element'] ) && 'tp1a2b3' === $g['element_id'] && 'heading' === $g['element']; },
		$got, topersia_log_elementor_element( array( array( 'object' => new stdClass() ) ) ), wp_json_encode( $got ) );
}

/* ══ ۲٫۸ نسخه در نشانی دارایی‌ها ══════════════════════════════════════ */
section( '۲٫۸ — نسخه در نشانی دارایی‌ها' );

$assets = array();
foreach ( array( '/province/isfahan/', '/city/isfahan/', '/en/province/isfahan/' ) as $p ) {
	preg_match_all( '#<(?:link|script)\b[^>]*?(?:href|src)=(["\'])([^"\']*/plugins/topersia-(?:render|user-core|ai-summarizer|core)/[^"\']*)\1#', local_get( $p )['body'], $m );
	$assets = array_merge( $assets, $m[2] );
}
$assets = array_values( array_unique( array_map( 'html_entity_decode', $assets ) ) );
$ver_claim = function ( $list ) {
	if ( count( $list ) < 5 ) { return false; }
	foreach ( $list as $src ) {
		$file = topersia_asset_file( $src );
		if ( ! preg_match( '/[?&]ver=(\d+)/', $src, $v ) || '' === $file || ! is_file( $file ) || (int) $v[1] !== filemtime( $file ) ) {
			return false;
		}
	}
	return true;
};
/* جهش: همان نگاشت ۴٫۱۰٫۰ — هر «/assets/» نشانی ← «/wp-content/» و ver برداشته */
$old_mtime = function ( $src ) {
	$home  = untrailingslashit( (string) get_option( 'home' ) );
	$clean = strtok( str_replace( $home, '', $src ), '?' );
	$path  = str_replace( array( '/' . TOPERSIA_ASSETS_PATH . '/', '/' . TOPERSIA_CORE_PATH . '/' ), array( '/wp-content/', '/wp-includes/' ), $clean );
	return file_exists( untrailingslashit( ABSPATH ) . $path ) ? $src : remove_query_arg( 'ver', $src );
};
mut( 'هر <link> و <script> افزونه‌های ما در HTML استان، شهر و صفحۀ انگلیسی ?ver= دارد برابر زمان تغییر پرونده؛ جهش ۴٫۱۰٫۰ نسخه را برمی‌دارد',
	$ver_claim, $assets, array_map( $old_mtime, $assets ), count( $assets ) . ' دارایی' );

echo "\n" . str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['pass']}   افتاده: {$GLOBALS['fail']}\n";
exit( $GLOBALS['fail'] ? 1 : 0 );
