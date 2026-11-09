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

/* ══ بخش ۲ — فیلتر مکان (P5-D549) · متن‌ها (P5-D554) · برچسب حریم خصوصی ══ */
$w51_loc   = $w51_read( 'plugin/topersia-render/includes/topersia-g-loc.php' );
$w51_ljs   = $w51_read( 'plugin/topersia-render/assets/topersia-g-loc.js' );
$w51_ncss  = $w51_read( 'plugin/topersia-render/assets/topersia-g-news.css' );
$w51_arch  = $w51_read( 'plugin/topersia-render/templates/news-archive.php' );
$w51_news  = $w51_read( 'plugin/topersia-render/includes/topersia-g-news.php' );
$w51_zones = $w51_read( 'plugin/topersia-render/includes/topersia-g-zones.php' );
$w51_list  = $w51_read( 'plugin/topersia-render/templates/news-list.php' );
$w51_pages = $w51_read( 'docs/legal/PAGES-TEXT-2026-11-04.md' );
$w51_priv  = $w51_read( 'docs/legal/PRIVACY-DRAFT.md' );

$w51_rest = function ( $s ) {
	return false !== strpos( $s, "foreach ( array( 'tp_province', 'tp_city' ) as \$type ) {" )
		&& false !== strpos( $s, "'meta_query'       => array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) )," )
		&& false !== strpos( $s, 'foreach ( topersia_g_published( array_map(' )
		&& false !== strpos( $s, "define( 'TOPERSIA_LOC_MIN', 3 );" ) && false !== strpos( $s, "define( 'TOPERSIA_LOC_MAX', 8 );" )
		&& false !== strpos( $s, "'methods'             => 'GET'," ) && false !== strpos( $s, 'topersia_g_loc_fold(' );
};
tp441_mut( 'P5-D549 REST: فقط استان سپس شهر، منتشرشدۀ هم‌زبان، غیرنمونه، ۳ نویسه، حداکثر ۸، GET فقط‌خواندنی، fold — جهش: بی دروازۀ منتشرشده', $w51_rest, $w51_loc,
	str_replace( 'foreach ( topersia_g_published( array_map(', 'foreach ( ( array_map(', $w51_loc ) );
t( 'P5-D549 REST: جهش — نمونه (بی شرط `_tp_sample`) می‌افتد', ! $w51_rest( str_replace( "array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) )", 'array()', $w51_loc ) ) );

$w51_aria = function ( $s ) {
	return false !== strpos( $s, ' role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="' )
		&& false !== strpos( $s, 'role="listbox"' ) && false !== strpos( $s, 'aria-live="polite" data-tp-loc-live' )
		&& false !== strpos( $s, 'name="tp_loc"' ) && false !== strpos( $s, 'data-tp-news-swap="loc"' ) && false !== strpos( $s, '<input type="hidden" name="tp_place"' );
};
tp441_mut( 'P5-D549 نشانه‌گذاری: combobox (expanded/controls) + listbox + aria-live؛ بی JS فیلد GET `tp_loc`؛ تراشه و tp_place در ناحیۀ جای‌گزینی — جهش: بی role', $w51_aria, $w51_loc,
	str_replace( ' role="combobox"', '', $w51_loc ) );

$w51_kbd = function ( $s ) {
	foreach ( array( "'ArrowDown' === e.key", "'ArrowUp' === e.key", "'Enter' === e.key", "'Escape' === e.key", "setAttribute( 'aria-activedescendant'", "setAttribute( 'aria-expanded', 'true' )", 'var MIN   = 3;', 'f.requestSubmit();' ) as $n ) {
		if ( false === strpos( $s, $n ) ) {
			return false;
		}
	}
	return 0 === preg_match( '/location\.(?:href\s*=(?!=)|reload|assign|replace)/', $s );
};
tp441_mut( 'P5-D549 JS: بالا/پایین/Enter/Esc، activedescendant، ۳ نویسه، انتخاب ← ارسال فرم بی‌رفرش (P5-D546)، بی تغییر location — جهش: بی Esc', $w51_kbd, $w51_ljs,
	str_replace( "'Escape' === e.key", "'Esc_' === e.key", $w51_ljs ) );
t( 'P5-D549 JS: جهش — بارگذاری دوباره با location.reload می‌افتد', ! $w51_kbd( $w51_ljs . "\nlocation.reload();" ) );

$w51_tgt = function ( $c ) {
	return (bool) preg_match( '/\.tp-loc__opt,\.tp-loc__none\{[^}]*min-block-size:44px/', $c ) && (bool) preg_match( '/\.tp-loc__chip\{[^}]*min-block-size:44px/', $c )
		&& (bool) preg_match( '/:is\([^)]*\.tp-loc__in\)\{[^}]*min-block-size:44px/', $c );
};
tp441_mut( 'P5-D549 CSS: فیلد، گزینه و تراشه ≥ ۴۴px — جهش: گزینۀ ۳۲px', $w51_tgt, $w51_ncss,
	str_replace( 'gap:12px;min-block-size:44px;padding-inline:12px', 'gap:12px;min-block-size:32px;padding-inline:12px', $w51_ncss ) );
t( 'P5-D549 قالب: /news/ مکان را از combobox متنی می‌گیرد، نه فهرست کشویی',
	false !== strpos( $w51_arch, "topersia_g_loc_field( 'tp-news-place', \$tp_place," ) && false === strpos( $w51_arch, "topersia_g_news_select( 'tp-news-place'" ) );

/* متن‌ها — P5-D554 */
$w51_old_lead = 'محتوای زمانی با تاریخ صریح و لینک به منبع اصلی.';
$w51_lead = function ( $n, $z, $a, $l ) use ( $w51_old_lead ) {
	return false !== strpos( $n, "return __( 'آخرین اخبار گردشگری، رویدادها و جشنواره‌های ایران، با ذکر تاریخ و منبع.', 'topersia' );" )
		&& false !== strpos( $n, "add_filter( 'rank_math/frontend/description'" )
		&& false !== strpos( $z, 'topersia_g_news_lead()' ) && false !== strpos( $a, 'topersia_g_news_lead()' ) && false !== strpos( $l, 'topersia_g_news_lead()' )
		&& false === strpos( $z . $a . $l . $n, $w51_old_lead );
};
t( 'P5-D554: زیرعنوان /news/ از یک تابع (سربرگ، قالب، فهرست، متا Rank Math، اسکیما) و بی متن قدیم',
	$w51_lead( $w51_news, $w51_zones, $w51_arch, $w51_list ) && false !== strpos( $w51_schema, "'description' => function_exists( 'topersia_g_news_lead' ) ? wp_strip_all_tags( topersia_g_news_lead() ) : ''," ) );
t( 'P5-D554: جهش — متن قدیم در یک جا می‌افتد', ! $w51_lead( $w51_news, $w51_zones . $w51_old_lead, $w51_arch, $w51_list ) );

$w51_home_t = function ( $s ) {
	return false !== strpos( $s, '«مرجع جامع گردشگری ایران؛ از جاذبه‌های تاریخی و طبیعی تا اطلاعات کاربردی سفر.»' )
		&& false !== strpos( $s, '«The comprehensive reference for travelling in Iran: provinces, cities, attractions, events and practical travel information.»' )
		&& false !== strpos( $s, '«المرجع الشامل للسفر إلى إيران: المحافظات والمدن والمعالم والفعاليات ومعلومات السفر العملية.»' );
};
tp441_mut( 'P5-D554: جملۀ صفحۀ اول در منبع یکتا (PAGES-TEXT بخش ۴، سه زبان؛ tp-front-text.php می‌نویسد) — جهش: متن انگلیسی دیگر', $w51_home_t, $w51_pages,
	str_replace( 'The comprehensive reference for travelling in Iran', 'Discover Iran', $w51_pages ) );

$w51_pl = function ( $s ) {
	return false !== strpos( $s, '- نشانی IP و اطلاعات درخواست:' ) && false !== strpos( $s, '- IP address and request information:' ) && false !== strpos( $s, '- عنوان IP ومعلومات الطلب:' )
		&& false === strpos( $s, '- گزارش‌های فنی (فقط شناسهٔ کاربر' ) && false === strpos( $s, '- Technical logs (user ID only' ) && false === strpos( $s, '- السجلات التقنية (معرّف المستخدم فقط' );
};
tp441_mut( 'حریم خصوصی: برچسب «نشانی IP و اطلاعات درخواست» در سه زبان، برچسب قدیم هیچ‌جا — جهش: برچسب قدیم', $w51_pl, $w51_priv,
	str_replace( '- IP address and request information:', '- Technical logs (user ID only, never the number):', $w51_priv ) );
