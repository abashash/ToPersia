<?php
/**
 * دستۀ 51 — از assert.php بار می‌شود؛ خواندن کد و رفتار با stub، هر ادعا با جهش. رفتار روی وردپرس واقعی: tests/w51-local.js.
 *
 * بخش ۱ — حالت پیش‌نمایش نمایشی (P5-D548):
 *   topersia_demo_on() فقط با ثابت true و میزبان محلی؛ میزبان غیرمحلی همیشه خاموش (جهش).
 *   خاموش ← شرط نمونه همان پیشین؛ پست نمایشی هرگز در اسکیما، sitemap، جست‌وجو.
 *   php tests/assert.php --launch ← ثابت در wp-config یا هر پست `_tp_demo`/`_tp_sample` در پایگاه‌داده قرمز با فهرست.
 */

echo "\nدستۀ 51 — پیش‌نمایش نمایشی، فیلتر مکان، نوار پیشرفت، هاب\n" . str_repeat( '─', 78 ) . "\n";

require_once __DIR__ . '/env.php';
$w51_root   = dirname( __DIR__ );
$w51_read   = function ( $p ) use ( $w51_root ) { return (string) @file_get_contents( $w51_root . '/' . $p ); };
$w51_demo   = $w51_read( 'plugin/topersia-render/includes/topersia-g-demo.php' );
$w51_render = $w51_read( 'plugin/topersia-render/topersia-render.php' );
$w51_schema = $w51_read( 'plugin/topersia-schema/topersia-schema.php' );
$w51_search = $w51_read( 'plugin/topersia-render/includes/topersia-g-search.php' );
$w51_home   = $w51_read( 'plugin/topersia-render/includes/topersia-g-home.php' );
$w51_launch = in_array( '--launch', isset( $argv ) ? (array) $argv : array(), true );

/* ── ۱٫۱ دروازه: ثابت و میزبان محلی ─────────────────────────────────── */
$w51_on = function ( $s ) {
	return false !== strpos( $s, "return defined( 'TOPERSIA_DEMO_PREVIEW' ) && true === TOPERSIA_DEMO_PREVIEW && topersia_demo_host_local();" );
};
tp441_mut( 'P5-D548: topersia_demo_on() = ثابت true و میزبان محلی — جهش: بی شرط میزبان', $w51_on, $w51_demo,
	str_replace( ' && topersia_demo_host_local();', ';', $w51_demo ) );

$w51_hosts = function ( $local ) {
	return $local( 'http://topersia.local/' ) && $local( 'http://localhost/x' ) && $local( 'http://127.0.0.1/' ) && $local( 'https://a.test/' ) && $local( 'http://b.localhost/' )
		&& ! $local( 'https://topersia.com/' ) && ! $local( 'https://www.topersia.com/' ) && ! $local( 'https://topersia.local.evil.com/' )
		&& ! $local( 'https://staging.topersia.com/' );
};
$w51_anyhost = function ( $url ) { return '' !== strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) ); };
t( 'P5-D548: میزبان غیرمحلی (topersia.com · staging · local.evil.com) همیشه خاموش؛ محلی (‎.local · localhost · 127.0.0.1 · ‎.test) روشن — جهش: «هر میزبان» گرفته می‌شود',
	function_exists( 'topersia_demo_host_local' ) && $w51_hosts( 'topersia_demo_host_local' ) && ! $w51_hosts( $w51_anyhost ) );

t( 'P5-D548: بی ثابت (پیش‌فرض) خاموش است، شرط نمونه دقیقاً همان پیشین، بی نشان',
	function_exists( 'topersia_demo_on' ) && ! defined( 'TOPERSIA_DEMO_PREVIEW' ) && ! topersia_demo_on()
	&& array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) === topersia_g_sample_mq()
	&& '' === topersia_g_demo_badge( 1 ) );

$w51_mq = function ( $s ) {
	return false !== strpos( $s, "if ( ! topersia_demo_on() ) {\n\t\t\treturn array( \$real );" )
		&& false !== strpos( $s, "array( 'relation' => 'OR', \$real, array( 'key' => '_tp_demo', 'value' => '1' ) )" );
};
tp441_mut( 'P5-D548: روشن ← غیرنمونه یا فقط `_tp_demo=1` (نمونۀ دیگر نه) — جهش: OR با هر `_tp_sample`', $w51_mq, $w51_demo,
	str_replace( "array( 'key' => '_tp_demo', 'value' => '1' )", "array( 'key' => '_tp_sample', 'compare' => 'EXISTS' )", $w51_demo ) );

t( 'P5-D548: ماژول در فهرست بار render (پس از هسته)', false !== strpos( $w51_render, "'includes/topersia-g-core.php', 'includes/topersia-g-demo.php'," ) );

/* ── ۱٫۲ هرگز در اسکیما، sitemap، جست‌وجو ─────────────────────────── */
$w51_sch = function ( $s ) {
	return false !== strpos( $s, "if ( '1' === (string) get_post_meta( \$post_id, '_tp_demo', true ) ) {\n\t\treturn;" )
		&& false !== strpos( $s, 'topersia_g_demo_strip( $ids )' );
};
tp441_mut( 'P5-D548: اسکیما — صفحۀ تکی نمایشی بی گراف؛ ItemList آرشیو بی نمایشی — جهش: بی پرش', $w51_sch, $w51_schema,
	str_replace( "'_tp_demo', true ) ) {\n\t\treturn;", "'_tp_demo', true ) ) {\n\t\t// x", $w51_schema ) );
$w51_sm = function ( $s ) {
	return false !== strpos( $s, "add_filter( 'rank_math/sitemap/posts_to_exclude'" ) && false !== strpos( $s, "add_filter( 'rank_math/sitemap/entry'" )
		&& false !== strpos( $s, "add_filter( 'wp_sitemaps_posts_query_args'" ) && false !== strpos( $s, "WHERE meta_key = '_tp_demo'" );
};
tp441_mut( 'P5-D548: sitemap — Rank Math (فهرست و هر ورودی) و هستۀ وردپرس بی نمایشی — جهش: بی صافی Rank Math', $w51_sm, $w51_demo,
	str_replace( "add_filter( 'rank_math/sitemap/posts_to_exclude'", "add_filter( 'x'", $w51_demo ) );
$w51_srch = function ( $s ) {
	return false !== strpos( $s, "\$q->set( 'meta_query', array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) );" ) && false === strpos( $s, 'topersia_g_sample_mq' );
};
tp441_mut( 'P5-D548: جست‌وجوی عمومی همیشه بی نمونه (نمایشی هم `_tp_sample` دارد) — جهش: جست‌وجو از sample_mq', $w51_srch, $w51_search,
	str_replace( "\$q->set( 'meta_query', array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) );", "\$q->set( 'meta_query', topersia_g_sample_mq() );", $w51_search ) );
$w51_rob = function ( $s ) {
	return false !== strpos( $s, 'return topersia_demo_on() || ( is_singular() && topersia_g_is_demo( get_queried_object_id() ) );' )
		&& false !== strpos( $s, "add_filter( 'wp_robots'" ) && false !== strpos( $s, "add_filter( 'rank_math/frontend/robots'" )
		&& false !== strpos( $s, "&& ! topersia_demo_on() ) {\n\t\t\tglobal \$wp_query;\n\t\t\t\$wp_query->set_404();" );
};
tp441_mut( 'P5-D548: روشن ← همۀ صفحه‌ها noindex؛ خاموش ← صفحۀ تکی نمایشی ۴۰۴ — جهش: بی noindex سراسری', $w51_rob, $w51_demo,
	str_replace( 'return topersia_demo_on() || ( is_singular()', 'return ( is_singular()', $w51_demo ) );
$w51_stats = function ( $s ) {
	return 6 === preg_match_all( '/count\( topersia_g_home_real\( [^;]*?, -1, false \) \)/', $s );
};
tp441_mut( 'P5-D548: شمار نوار آمار همیشه واقعی (بی نمایشی) — جهش: شمار با نمایشی', $w51_stats, $w51_home,
	str_replace( "array( 'tp_route' ), -1, false )", "array( 'tp_route' ) )", $w51_home ) );

/* ── ۱٫۳ اسکریپت‌ها ───────────────────────────────────────────────── */
$w51_seed  = $w51_read( 'scripts/tp-demo-seed.php' );
$w51_purge = $w51_read( 'scripts/tp-demo-purge.php' );
$w51_sd = function ( $s ) {
	$code = preg_replace( '#/\*.*?\*/#s', '', $s );
	return false !== strpos( $s, "update_post_meta( \$id, '_tp_demo', '1' );" ) && false !== strpos( $s, "update_post_meta( \$id, '_tp_sample', '1' );" )
		&& false !== strpos( $s, '$old = $have( $key );' ) && false !== strpos( $s, "if ( 'local' !== wp_get_environment_type() || false === strpos( (string) home_url(), 'topersia.local' ) )" )
		&& ! preg_match( '#https?://(?!topersia\.local)#', $code ) && false === strpos( $code, 'jet_rel' ) && false === strpos( $code, '_thumbnail_id' );
};
tp441_mut( 'P5-D548: seed — `_tp_demo` و `_tp_sample`، ایدمپوتنت، فقط محلی، بی نشانی بیرونی، بی رابطه، بی تصویر — جهش: بی `_tp_sample`', $w51_sd, $w51_seed,
	str_replace( "update_post_meta( \$id, '_tp_sample', '1' );", '', $w51_seed ) );
$w51_pg = function ( $s ) {
	return false !== strpos( $s, "WHERE meta_key = '_tp_demo'\" ) );" ) && false === strpos( $s, "meta_key = '_tp_sample'" ) && false !== strpos( $s, "'confirm' === (string)" );
};
tp441_mut( 'P5-D548: purge — فقط `_tp_demo` (نمونه‌های دیگر دست نمی‌خورند)، پیش‌فرض dry-run — جهش: پاک‌کردن هر `_tp_sample`', $w51_pg, $w51_purge,
	str_replace( "WHERE meta_key = '_tp_demo'\" ) );", "WHERE meta_key = '_tp_sample'\" ) );", $w51_purge ) );

/* ── ۱٫۴ گیت --launch ─────────────────────────────────────────────── */
$w51_cfgf = function ( $c ) { return (bool) preg_match( "/^\s*define\(\s*['\"]TOPERSIA_DEMO_PREVIEW['\"]/m", $c ); };
t( 'گیت --launch: کاشف ثابت کار می‌کند — جهش: define دیده می‌شود، توضیح نه', $w51_cfgf( "<?php\ndefine( 'TOPERSIA_DEMO_PREVIEW', true );" ) && ! $w51_cfgf( "<?php\n// define( 'TOPERSIA_DEMO_PREVIEW', true );" ) );
$w51_bad = array();
if ( $w51_cfgf( (string) @file_get_contents( TP_WP_DIR . '/wp-config.php' ) ) ) {
	$w51_bad[] = 'TOPERSIA_DEMO_PREVIEW در wp-config';
}
if ( $w51_launch && is_file( TP_WP_CLI ) ) {
	/* پرسش در پروندۀ موقت (escapeshellarg ویندوز " را می‌اندازد) */
	$w51_f = sys_get_temp_dir() . '/tp-w51-launch-' . getmypid() . '.php';
	file_put_contents( $w51_f, <<<'TPQ'
<?php global $wpdb; foreach ( $wpdb->get_results( "SELECT p.post_type t, COUNT( DISTINCT p.ID ) n, COUNT( DISTINCT d.post_id ) d FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_tp_sample' LEFT JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = '_tp_demo' WHERE p.post_status <> 'trash' GROUP BY p.post_type ORDER BY p.post_type" ) as $r ) { echo $r->t, ':', (int) $r->n, ( (int) $r->d ? '(demo ' . (int) $r->d . ')' : '' ), ' '; }
TPQ
	);
	$w51_db = trim( (string) shell_exec( '"' . TP_PHP . '" "' . TP_WP_CLI . '" --path="' . TP_WP_DIR . '" --url=' . TP_BASE_URL . ' eval-file "' . $w51_f . '" 2>&1' ) );
	@unlink( $w51_f );
	if ( '' !== $w51_db ) {
		$w51_bad[] = 'نمونه/نمایشی در پایگاه‌داده: ' . $w51_db;
	}
}
if ( $w51_launch ) {
	t( 'گیت --launch (P5-D548): نه ثابت پیش‌نمایش در wp-config، نه هیچ پست `_tp_demo`/`_tp_sample`', array() === $w51_bad, implode( ' · ', $w51_bad ) );
} elseif ( $w51_bad ) {
	echo '  ⚠ هشدار (بی --launch قرمز نیست): ' . implode( ' · ', $w51_bad ) . " — پایگاه‌داده فقط با --launch سنجیده می‌شود\n";
}
