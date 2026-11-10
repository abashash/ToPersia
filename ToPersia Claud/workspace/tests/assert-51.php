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
	return (bool) preg_match( '/\.tp-loc__opt,\.tp-loc__none\{[^}]*min-block-size:44px/', $c ) && (bool) preg_match( '/\.tp-actf__chip\{[^}]*min-block-size:44px/', $c )
		&& (bool) preg_match( '/:is\([^)]*\.tp-loc__in\)\{[^}]*min-block-size:44px/', $c );
};
tp441_mut( 'P5-D549 CSS: فیلد، گزینه و تراشه ≥ ۴۴px — جهش: گزینۀ ۳۲px', $w51_tgt, $w51_ncss,
	str_replace( 'gap:12px;min-block-size:44px;padding-inline:12px', 'gap:12px;min-block-size:32px;padding-inline:12px', $w51_ncss ) );
t( 'P5-D549 قالب: /news/ مکان را از combobox متنی می‌گیرد، نه فهرست کشویی',
	false !== strpos( $w51_arch, "topersia_g_loc_field( 'tp-news-place', \$tp_place )" ) && false === strpos( $w51_arch, "topersia_g_news_select( 'tp-news-place'" ) );

/* متن‌ها — P5-D554 */
$w51_old_lead = 'محتوای زمانی با تاریخ صریح و لینک به منبع اصلی.';
$w51_lead = function ( $n, $z, $a, $l ) use ( $w51_old_lead ) {
	return false !== strpos( $n, "__( 'آخرین اخبار گردشگری، رویدادها و جشنواره‌های %s.', 'topersia' ), topersia_g_news_place_name( \$place_id ) );" )   /* P5-D558 */
		&& false !== strpos( $n, "add_filter( 'rank_math/frontend/description'" ) && false !== strpos( $n, 'return topersia_g_news_lead( topersia_g_news_filter_place() );' )
		&& false !== strpos( $z, 'topersia_g_news_lead( $pid )' ) && false !== strpos( $a, 'topersia_g_news_lead( $tp_place )' ) && false !== strpos( $l, 'topersia_g_news_lead( $tp_place )' )
		&& false === strpos( $z . $a . $l . $n, $w51_old_lead ) && 0 === preg_match( '/با ذکر تاریخ و منبع/u', $z . $a . $l . $n );
};
t( 'P5-D558: زیرعنوان خبرها با نام مکان (%s) از یک تابع — سربرگ، قالب، فهرست مکان، بخش خبر موجودیت، متا Rank Math، اسکیما؛ بی «با ذکر تاریخ و منبع»',
	$w51_lead( $w51_news, $w51_zones, $w51_arch, $w51_list ) && false !== strpos( $w51_schema, "'description' => function_exists( 'topersia_g_news_lead' ) ? wp_strip_all_tags( topersia_g_news_lead( function_exists( 'topersia_g_news_filter_place' ) ? topersia_g_news_filter_place() : 0 ) ) : ''," ) );
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

/* ══ بخش ۲ — نوار پیشرفت اسکرول (P5-D550) ══════════════════════════ */
$w51_zjs  = $w51_read( 'plugin/topersia-render/assets/topersia-g-zones.js' );
$w51_pcss = $w51_read( 'plugin/topersia-render/assets/topersia-g-prog.css' );
$w51_prog = function ( $s ) {
	foreach ( array(
		"page.querySelectorAll( '.tp-slider, .tp-home-slider, [data-tp-prog]' )",   /* هر اسلایدر + اسلایدر تازه با ویژگی */
		"bar.setAttribute( 'aria-hidden', 'true' );",
		"el.classList.add( 'tp-has-prog' );",                                         /* نوار بومی فقط با JS پنهان */
		'bar.hidden = d.max <= 1;',                                                     /* همه جا شد ← پنهان */
		'Math.abs( el.scrollLeft ) / d.max',                                            /* جای کاربر مثل فلش‌ها (P5-D243) */
		"'translateX(' + ( rtl() ? -1 : 1 ) *",                                         /* قرینه در راست‌به‌چپ */
		"'rtl' === window.getComputedStyle( el ).direction",                            /* جهت از خود اسلایدر */
		"el.addEventListener( 'scroll', later, { passive: true } );",
		"el.classList.add( 'tp-is-dragging' );",                                        /* کشیدن شست = چسبیدن خاموش، همان کلاس P5-D277 */
	) as $n ) {
		if ( false === strpos( $s, $n ) ) {
			return false;
		}
	}
	return true;
};
tp441_mut( 'P5-D550 JS: نوار زیر هر اسلایدر، همگام با اسکرول، جهت از اسلایدر، پنهان وقتی همه جا شد، aria-hidden — جهش: نوار همیشه دیده', $w51_prog, $w51_zjs,
	str_replace( 'bar.hidden = d.max <= 1;', 'bar.hidden = false;', $w51_zjs ) );
t( 'P5-D550 JS: جهش — جهت ثابت (بی قرینۀ راست‌به‌چپ) می‌افتد', ! $w51_prog( str_replace( "'translateX(' + ( rtl() ? -1 : 1 ) *", "'translateX(' + 1 *", $w51_zjs ) ) );

$w51_pc = function ( $c, $r ) {
	return false !== strpos( $c, 'html .tp-page :is(.tp-has-prog,#tp-prog-none){scrollbar-width:none;' ) && false !== strpos( $c, ':is(.tp-has-prog,#tp-prog-none)::-webkit-scrollbar{display:none;' )
		&& (bool) preg_match( '/\.tp-prog__thumb\{[^}]*inset-inline-start:0/', $c ) && (bool) preg_match( '/\.tp-prog__thumb\{[^}]*background:var\(--tp-primary\)/', $c )
		&& 0 === preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(/i', preg_replace( '#/\*.*?\*/#s', '', $c ) )
		&& false !== strpos( $r, "\$url . 'assets/topersia-g-prog.css'," );
};
tp441_mut( 'P5-D550 CSS: نوار بومی فقط با tp-has-prog پنهان، شست منطقی (inset-inline-start)، رنگ فقط از توکن، بار با قالب — جهش: رنگ خام', function ( $c ) use ( $w51_pc, $w51_render ) { return $w51_pc( $c, $w51_render ); }, $w51_pcss,
	str_replace( 'background:var(--tp-primary);', 'background:#0a6b5e;', $w51_pcss ) );

/* ══ الحاقیه ب — یک نوار برای هر اسلایدر (P5-D559) ═══════════════════ */
$w51_d559 = function ( $c, $js, $nui, $z ) {
	return false !== strpos( $c, 'html .tp-page :is(.tp-has-prog,#tp-prog-none){scrollbar-width:none;' )   /* وزن شناسه: قاعدۀ ویژۀ صفحه (home thin) نمی‌چربد */
		&& false !== strpos( $c, ':is(.tp-has-prog,#tp-prog-none)::-webkit-scrollbar{display:none;' )
		&& false !== strpos( $js, "if ( el.hasAttribute( 'data-tp-no-prog' ) ||" )                            /* مستثنا نوار نمی‌گیرد */
		&& false !== strpos( $nui, '<ul class="tp-news-chips__list" data-tp-no-prog>' )                         /* تراشه‌های اخبار مستثنا (خواستۀ کاربر) */
		&& false !== strpos( $z, '<div class="tp-wrap tp-snav__in" data-tp-no-prog>' );                        /* ناوبری چسبان، نه اسلایدر */
};
$w51_zphp = $w51_read( 'plugin/topersia-render/includes/topersia-g-zones.php' );
$w51_nui  = $w51_read( 'plugin/topersia-render/includes/topersia-g-news-ui.php' );
tp441_mut( 'P5-D559: نوار بومی با وزن شناسه پنهان؛ تراشه‌های اخبار و ناوبری بخش‌ها مستثنا — جهش: وزن شناسه برداشته (دو نوار در ۱۴۴۰ صفحۀ اول)',
	function ( $c ) use ( $w51_d559, $w51_zjs, $w51_nui, $w51_zphp ) { return $w51_d559( $c, $w51_zjs, $w51_nui, $w51_zphp ); }, $w51_pcss,
	str_replace( ':is(.tp-has-prog,#tp-prog-none)', '.tp-has-prog', $w51_pcss ) );
t( 'P5-D559: جهش — تراشه‌های اخبار بی data-tp-no-prog می‌افتد', ! $w51_d559( $w51_pcss, $w51_zjs, str_replace( ' data-tp-no-prog>', '>', $w51_read( 'plugin/topersia-render/includes/topersia-g-news-ui.php' ) ), $w51_zphp ) );
t( 'P5-D559: جهش — JS مستثنا را نادیده بگیرد می‌افتد', ! $w51_d559( $w51_pcss, str_replace( "el.hasAttribute( 'data-tp-no-prog' ) ||", '', $w51_zjs ), $w51_read( 'plugin/topersia-render/includes/topersia-g-news-ui.php' ), $w51_zphp ) );

/* ══ الحاقیه الف — اخبار (P5-D556 · P5-D557 · P5-D558) ══════════════ */
$w51_nui = $w51_read( 'plugin/topersia-render/includes/topersia-g-news-ui.php' );
$w51_top = function ( $u, $a ) {
	return false !== strpos( $u, "define( 'TOPERSIA_NEWS_TOP_COL', 3 );" ) && false !== strpos( $u, "define( 'TOPERSIA_NEWS_TOP_COLS', 2 );" )
		&& false !== strpos( $u, 'array_chunk( array_slice( $ids, 1, TOPERSIA_NEWS_TOP_COL * TOPERSIA_NEWS_TOP_COLS ), TOPERSIA_NEWS_TOP_COL )' )   /* ستون بی‌خبر ساخته نمی‌شود */
		&& false !== strpos( $a, 'array_slice( $tp_ids, 0, topersia_g_news_top_max() )' ) && false !== strpos( $a, '$tp_grid  = array_slice( $tp_ids, count( $tp_feat ) );' )   /* بی تکرار در «جدیدترین» */
		&& strpos( $a, 'topersia_g_news_top( $tp_feat )' ) < strpos( $a, '<div class="tp-news-filters">' )
		&& strpos( $a, '<div class="tp-news-filters">' ) < strpos( $a, '<div class="tp-news-results"' );
};
tp441_mut( 'P5-D556: بالا = برگزیده + دو ستون × ۳ (ثابت)، بی تکرار در «جدیدترین»؛ ترتیب هیرو ← بالا ← پالایش ← نتایج — جهش: سه خبر', function ( $u ) use ( $w51_top, $w51_arch ) { return $w51_top( $u, $w51_arch ); }, $w51_nui,
	str_replace( "define( 'TOPERSIA_NEWS_TOP_COLS', 2 );", "define( 'TOPERSIA_NEWS_TOP_COLS', 1 );", $w51_nui ) );
t( 'P5-D556: جهش — پالایش پیش از بلوک بالا می‌افتد', ! $w51_top( $w51_nui, str_replace( '<?php echo topersia_g_news_top( $tp_feat ); // phpcs:ignore WordPress.Security.EscapeOutput ?>', '', $w51_arch ) . '<?php echo topersia_g_news_top( $tp_feat ); ?>' ) );

if ( function_exists( 'topersia_g_news_top' ) ) {
	$w51_t7 = topersia_g_news_top( array() );
	t( 'P5-D556 رفتار: زیر دو خبر ظرف تهی (برای تعویض بی‌رفرش) و بی section', false !== strpos( $w51_t7, 'data-tp-news-swap="top"' ) && false === strpos( $w51_t7, '<section' ) );
}

$w51_actf = function ( $u, $a, $l ) {
	return false !== strpos( $u, '<div class="tp-actf" data-tp-news-swap="active" hidden></div>' )   /* بی پالایش ← نیاید */
		&& false !== strpos( $u, "esc_html__( 'پاک‌کردن همه', 'topersia' )" ) && false !== strpos( $u, 'data-tp-news-nav aria-label=' )
		&& false !== strpos( $a, 'echo topersia_g_active_filters( topersia_g_news_active_items( $tp_args, $tp_kinds, $tp_tops ), $tp_base,' )
		&& strpos( $a, '</form>' ) < strpos( $a, 'topersia_g_active_filters(' )   /* خطی جدا زیر کل بلوک پالایش */
		&& false !== strpos( $l, "'<div class=\"tp-loc__keep\" data-tp-news-swap=\"loc\">' . \$keep . '</div>'" ) && false === strpos( $l, 'tp-loc__chip' );   /* combobox خالی و هم‌اندازه */
};
tp441_mut( 'P5-D557: ردیف پالایش‌های فعال جدا زیر کل بلوک، تراشۀ حذف‌شدنی + «پاک‌کردن همه»، بی پالایش پنهان؛ تراشه دیگر زیر combobox نیست — جهش: تراشه درون فیلد', function ( $l ) use ( $w51_actf, $w51_nui, $w51_arch ) { return $w51_actf( $w51_nui, $w51_arch, $l ); }, $w51_loc,
	str_replace( "' . \$keep . '</div>'", "' . \$keep . '<a class=\"tp-loc__chip\"></a></div>'", $w51_loc ) );
if ( function_exists( 'topersia_g_active_filters' ) ) {
	$w51_af = topersia_g_active_filters( array( array( 'استان اصفهان', 'https://x.local/news/' ) ), 'https://x.local/news/' );
	t( 'P5-D557 رفتار: یک پالایش ← یک تراشه + «پاک‌کردن همه»؛ هیچ ← پنهان',
		1 === substr_count( $w51_af, 'class="tp-actf__chip"' ) && false !== strpos( $w51_af, 'tp-actf__clear' ) && false !== strpos( topersia_g_active_filters( array(), '/' ), ' hidden>' ) );
}
$w51_js_loc = function ( $s ) { return false !== strpos( $s, "el.value = el.name === 'tp_loc' ? '' : ( p.get( el.name ) || '' );" ); };
tp441_mut( 'P5-D557 JS: پس از تعویض بی‌رفرش combobox خالی می‌ماند — جهش: tp_loc از نشانی پر می‌شود', $w51_js_loc, $w51_read( 'plugin/topersia-render/assets/topersia-g-news.js' ),
	str_replace( "el.value = el.name === 'tp_loc' ? '' : ( p.get( el.name ) || '' );", "el.value = p.get( el.name ) || '';", $w51_read( 'plugin/topersia-render/assets/topersia-g-news.js' ) ) );

$w51_h1 = function ( $n, $c ) {
	return false !== strpos( $n, "__( 'اخبار گردشگری %s', 'topersia' ), topersia_g_news_place_name( \$place_id ) );" )
		&& false !== strpos( $n, "return '' !== \$name ? \$name : __( 'ایران', 'topersia' );" )
		&& (bool) preg_match( '/\.tp-news-hero h1\{[^}]*text-align:center/', $c ) && (bool) preg_match( '/\.tp-news-hero__lead\{[^}]*margin:10px auto 0;[^}]*text-align:center/', $c )
		&& 0 === preg_match( '/\.tp-crumb\{[^}]*text-align:center/', $c );   /* مسیر راهنما آغاز خط می‌ماند */
};
tp441_mut( 'P5-D558: H1 «اخبار گردشگری %s» (پیش‌فرض ایران)، H1 و جمله وسط‌چین، مسیر راهنما آغاز خط — جهش: H1 آغازچین', function ( $c ) use ( $w51_h1, $w51_news ) { return $w51_h1( $w51_news, $c ); }, $w51_ncss,
	str_replace( '.tp-news-hero h1{margin:0;text-align:center;', '.tp-news-hero h1{margin:0;', $w51_ncss ) );

/* ══ الحاقیه ج — هاب عمومی نوع (بخش ۴) ══════════════════════════════ */
$w51_hub  = $w51_read( 'plugin/topersia-render/includes/topersia-g-hub.php' );
$w51_htpl = $w51_read( 'plugin/topersia-render/templates/hub-archive.php' );
$w51_njs  = $w51_read( 'plugin/topersia-render/assets/topersia-g-news.js' );
$w51_hcss = $w51_read( 'plugin/topersia-render/assets/topersia-g-hub.css' );

t( 'هاب: ماژول در فهرست بارگذاری render', false !== strpos( $w51_render, "'includes/topersia-g-hub.php'" ) );

/* رجیستری از ماتریس — نوع تازه = یک سطر؛ بی فهرست نام فیلد دستی */
$w51_reg = function ( $s ) {
	return false !== strpos( $s, 'foreach ( array_keys( topersia_g_type_matrix() ) as $type )' )
		&& false !== strpos( $s, "topersia_pack_schema( \$type )" ) && false !== strpos( $s, "apply_filters( 'topersia_g_hub_registry', \$rows )" );
};
tp441_mut( 'هاب: رجیستری از ماتریس نوع و اسکیمای زنده — جهش: فهرست دستی', $w51_reg, $w51_hub,
	str_replace( 'foreach ( array_keys( topersia_g_type_matrix() ) as $type )', "foreach ( array( 'tp_city', 'tp_province' ) as \$type )", $w51_hub ) );

/* آرشیو روی همان نامک بازنویسی؛ نشانی صفحۀ تکی دست نمی‌خورد */
$w51_arc = function ( $s ) { return false !== strpos( $s, "\$args['has_archive'] = \$slug;" ) && false === strpos( $s, "\$args['rewrite'] =" ); };
tp441_mut( 'هاب: has_archive = نامک موجود، rewrite تکی بی تغییر — جهش: has_archive = true (نشانی /tp_city/)', $w51_arc, $w51_hub,
	str_replace( "\$args['has_archive'] = \$slug;", "\$args['has_archive'] = true;", $w51_hub ) );

/* noindex زیر ۳ مورد و هر حالت پالایش‌شده؛ canonical بی‌پالایش */
$w51_thin = function ( $s ) {
	return false !== strpos( $s, "define( 'TOPERSIA_HUB_MIN_INDEX', 3 )" )
		&& false !== strpos( $s, 'return topersia_g_hub_filtered() || count( topersia_g_hub_pool() ) < TOPERSIA_HUB_MIN_INDEX;' )
		&& false !== strpos( $s, 'topersia_g_hub_url( topersia_g_hub_filtered() ? 1 :' );
};
tp441_mut( 'هاب: noindex زیر ۳ و پالایش‌شده، canonical بی‌پالایش — جهش: پالایش‌شده نمایه‌پذیر', $w51_thin, $w51_hub,
	str_replace( 'return topersia_g_hub_filtered() || count(', 'return count(', $w51_hub ) );

/* JSON-LD: CollectionPage + ItemList + Breadcrumb فقط نمایه‌پذیر، بی نمایشی */
$w51_hsch = function ( $s ) {
	$b = (string) strstr( $s, "if ( function_exists( 'topersia_g_hub_is' ) && topersia_g_hub_is() ) {" );
	$b = substr( $b, 0, (int) strpos( $b, 'return;' ) );
	return '' !== $b && false !== strpos( $b, '! topersia_g_hub_thin()' ) && false !== strpos( $b, 'topersia_g_demo_strip( $ids )' )
		&& false !== strpos( $s, 'function topersia_schema_hub_json(' );
};
tp441_mut( 'هاب: اسکیما فقط نمایه‌پذیر و بی پست نمایشی (P5-D548) — جهش: بی demo_strip', $w51_hsch, $w51_schema,
	str_replace( "topersia_g_demo_strip( \$ids ) : \$ids;   /* P5-D548 */\n\t\t\t\$per", "\$ids : \$ids;\n\t\t\t\$per", $w51_schema ) );

/* قالب: همان اجزای /news/ — تراشۀ فاست، ترتیب، فرم، combobox فقط با loc، ردیف فعال زیر فرم، صفحه‌بندی */
$w51_htp = function ( $s ) {
	return false !== strpos( $s, 'data-tp-news-chipvar="tp_facet"' ) && false !== strpos( $s, 'topersia_g_news_hero(' )
		&& false !== strpos( $s, 'topersia_g_news_chips(' ) && false !== strpos( $s, "if ( \$tp_row['loc'] ) {" )
		&& strpos( $s, '</form>' ) < strpos( $s, 'topersia_g_active_filters(' )
		&& false !== strpos( $s, "topersia_g_news_pages( \$tp_pages, \$tp_paged, 'topersia_g_hub_link'" )
		&& false !== strpos( $s, 'topersia_g_sample_hidden( $id )' );
};
tp441_mut( 'هاب: قالب با اجزای /news/ و ردیف فعال زیر فرم، نمایشی پنهان — جهش: combobox همیشه', $w51_htp, $w51_htpl,
	str_replace( "if ( \$tp_row['loc'] ) {", 'if ( true ) {', $w51_htpl ) );

/* JS: نام متغیر تراشه از ریشه، نه tp_topic ثابت */
$w51_cv = function ( $s ) { return false !== strpos( $s, "root.getAttribute( 'data-tp-news-chipvar' ) || 'tp_topic'" ); };
tp441_mut( 'هاب: تعویض بی‌رفرش متغیر تراشه را از ریشه می‌خواند — جهش: tp_topic ثابت', $w51_cv, $w51_njs,
	str_replace( "root.getAttribute( 'data-tp-news-chipvar' ) || 'tp_topic'", "'tp_topic'", $w51_njs ) );

/* H1 با %s برای هر نوع جز کشور؛ مقدار عددی جلوی نام (P5-D466) */
$w51_ht = function ( $s ) {
	preg_match_all( "/'(tp_[a-z_]+)'\s*=>\s*__\( '([^']*)', 'topersia' \)/u", (string) strstr( (string) strstr( $s, 'function topersia_g_hub_titles' ), 'function topersia_g_hub_registry', true ), $m, PREG_SET_ORDER );
	$miss = 0;
	foreach ( $m as $r ) {
		$miss += ( 'tp_country' !== $r[1] && false === strpos( $r[2], '%s' ) ) ? 1 : 0;
	}
	return count( $m ) >= 41 && 0 === $miss;
};
tp441_mut( 'هاب: H1 هر نوع با %s (نام مکان) — جهش: «شهرهای ایران» ثابت', $w51_ht, $w51_hub,
	preg_replace( "/'tp_city'(\s*)=> __\( '[^']*'/u", "'tp_city'\$1=> __( 'شهرهای ایران'", $w51_hub, 1 ) );
t( 'هاب: در ترتیب معیار، مقدار عددی برچسب کاشی (P5-D466)', false !== strpos( $w51_hub, "if ( 'metric' === \$args['tp_sort'] && null !== topersia_g_hub_metric( \$id, \$row['metric'] ) )" ) );
t( 'هاب: CSS فقط توکن رنگ', 0 === preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(/i', $w51_hcss ) && false === strpos( $w51_hcss, 'letter-spacing' ) );

/* ══ الحاقیه ث — برچسب مشترک tpc_tag (P5-D555) ═══════════════════════ */
$w51_tagc = $w51_read( 'plugin/topersia-core/includes/topersia-tags.php' );
$w51_tagr = $w51_read( 'plugin/topersia-render/includes/topersia-g-tags.php' );
$w51_core = $w51_read( 'plugin/topersia-core/topersia-core.php' );
$w51_tcss = $w51_read( 'plugin/topersia-render/assets/topersia-g-tags.css' );

t( 'P5-D555: هسته و render هر دو پروندۀ برچسب را بار می‌کنند', false !== strpos( $w51_core, "require_once __DIR__ . '/includes/topersia-tags.php';" ) && false !== strpos( $w51_render, "'includes/topersia-g-tags.php'" ) );

$w51_tax = function ( $s ) {
	return false !== strpos( $s, "'hierarchical'      => false," )
		&& false !== strpos( $s, "'rewrite'           => array( 'slug' => 'tags', 'with_front' => false" )   /* نه /tag/ وردپرس */
		&& 4 === substr_count( $s, "=> 'edit_others_posts'," )                                                 /* فقط ویرایشگر و بالاتر */
		&& false !== strpos( $s, "'local' === wp_get_environment_type()" )                                    /* فقط محلی تا ممیزی */
		&& 0 === preg_match( "/'sanitize_callback' => '(intval|floatval|boolval)'/", $s );                    /* درونی PHP با ۴ آرگومان خطا */
};
tp441_mut( 'P5-D555: tpc_tag ناسلسله‌مراتبی، /tags/، فقط ویرایشگر، فقط محلی، پاک‌ساز بستار — جهش: نامک tag', $w51_tax, $w51_tagc,
	str_replace( "array( 'slug' => 'tags',", "array( 'slug' => 'tag',", $w51_tagc ) );
t( 'P5-D555: جهش — نویسنده هم بچسباند می‌افتد', ! $w51_tax( str_replace( "'assign_terms' => 'edit_others_posts',", "'assign_terms' => 'edit_posts',", $w51_tagc ) ) );
t( 'P5-D555: جهش — پاک‌ساز intval می‌افتد', ! $w51_tax( str_replace( "'sanitize_callback' => function ( \$v ) { return (int) \$v; }", "'sanitize_callback' => 'intval'", $w51_tagc ) ) );
t( 'P5-D555: سه متای واژه ثبت', false !== strpos( $w51_tagc, "'tp_is_experience'" ) && false !== strpos( $w51_tagc, "'tp_icon'" ) && false !== strpos( $w51_tagc, "'tp_sort'" ) && false !== strpos( $w51_tagc, "wp_verify_nonce(" ) );

$w51_texp = function ( $s ) {
	return false !== strpos( $s, "define( 'TOPERSIA_TAG_MIN_ITEMS', 2 )" ) && false !== strpos( $s, 'if ( $n < TOPERSIA_TAG_MIN_ITEMS ) {' )
		&& false !== strpos( $s, 'topersia_g_published( array_map(' ) && false !== strpos( $s, '! topersia_g_sample_hidden( $id )' );
};
tp441_mut( 'P5-D555: تجربه فقط با دست‌کم دو مورد دیدنی (منتشرشده، نمونۀ پنهان نه) — جهش: بی دروازۀ نمونه', $w51_texp, $w51_tagr,
	str_replace( '! topersia_g_sample_hidden( $id )', 'true', $w51_tagr ) );
t( 'P5-D555: بلوک «برچسب‌ها» پایان کارت منابع، بی برچسب هیچ', false !== strpos( $w51_zphp = $w51_read( 'plugin/topersia-render/includes/topersia-g-zones.php' ), '$out .= topersia_g_tags_html( $pid );' )
	&& false !== strpos( $w51_tagr, "if ( is_wp_error( \$terms ) || ! \$terms ) {\n\t\t\treturn '';" ) );
if ( function_exists( 'topersia_g_tags_html' ) ) {
	t( 'P5-D555 رفتار: بی وردپرس/طبقه‌بندی ← تهی', '' === topersia_g_tags_html( 1 ) );
}
t( 'P5-D555: صفحۀ اول «کارها و تجربیات» از topersia_g_tag_experiences، تهی ← بخش نه', false !== strpos( $w51_home, "topersia_g_tag_experiences() : array() as \$e" ) && false !== strpos( $w51_home, "'' !== \$exp_html ?" ) );
t( 'P5-D553: آیکون راهنما به هاب نوع، بی هاب ← پست', false !== strpos( $w51_home, "'' !== \$hub ? \$hub : get_permalink( \$one )" ) );
t( 'P5-D551: شیشه روی پنل راهنما و تجربیات، ۳بعدی فقط بی کاهش حرکت', false !== strpos( $w51_home, '<nav class="tp-home-guide tp-home-glass"' ) && (bool) preg_match( '/prefers-reduced-motion:no-preference\)\{\s*html \.tp-page\.tp-home \.tp-home-guide\{perspective/', $w51_read( 'plugin/topersia-render/assets/topersia-g-home.css' ) ) );
t( 'P5-D555: CSS برچسب فقط توکن', 0 === preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(/i', $w51_tcss ) && false === strpos( $w51_tcss, 'letter-spacing' ) );

/* ── الحاقیۀ پ: ردیف سه‌ستونی صفحۀ اول (P5-D560) ─────────────────── */
$w51_row  = $w51_read( 'plugin/topersia-render/includes/topersia-g-home-row.php' );
$w51_hcs  = $w51_read( 'plugin/topersia-render/assets/topersia-g-home.css' );
t( 'P5-D560: render پروندۀ ردیف را بار می‌کند و صفحۀ اول آن را جای بخش رویداد تنها می‌نشاند', false !== strpos( $w51_render, "'includes/topersia-g-home-row.php'" )
	&& false !== strpos( $w51_home, "\$row3 = function_exists( 'topersia_g_home_row3' ) ? topersia_g_home_row3() : '';" )
	&& false === strpos( $w51_home, "topersia_g_home_section( 'tp-home-events'" ) );

$w51_cols = function ( $s ) {
	$e = strpos( $s, 'topersia_g_home_events_col(), topersia_g_home_trips_col(), topersia_g_home_seasons_col()' );   /* راست‌به‌چپ: رویداد | سفر | فصل */
	return false !== $e && false !== strpos( $s, "if ( ! \$cols ) {\n\t\t\treturn '';" )                                  /* هر سه خالی ← ردیف نه */
		&& 3 === substr_count( $s, "array( \$all, __( 'مشاهدۀ همه', 'topersia' ) )" )                                   /* هر پنل «مشاهدۀ همه» */
		&& 3 === substr_count( $s, ", 'tp-home-col' );" );
};
tp441_mut( 'P5-D560: سه ستون رویداد | سفر | فصل، هر کدام پنل با «مشاهدۀ همه»، هر سه خالی ← ردیف پنهان — جهش: ترتیب فصل نخست', $w51_cols, $w51_row,
	str_replace( 'topersia_g_home_events_col(), topersia_g_home_trips_col(), topersia_g_home_seasons_col()', 'topersia_g_home_seasons_col(), topersia_g_home_events_col(), topersia_g_home_trips_col()', $w51_row ) );
t( 'P5-D560: جهش — ردیف با یک ستون خالی پنهان می‌افتد', ! $w51_cols( str_replace( 'if ( ! $cols ) {', 'if ( count( $cols ) < 3 ) {', $w51_row ) ) );

$w51_ev = function ( $s ) {
	return false !== strpos( $s, "if ( '' === \$start || ( '' !== \$end ? \$end : \$start ) < \$today ) {" )           /* بی تاریخ آغاز یا گذشته ← نه */
		&& false !== strpos( $s, 'return strcmp( $a[0], $b[0] );' )                                                   /* نزدیک‌ترین نخست */
		&& false !== strpos( $s, 'array_slice( $events, 0, TOPERSIA_HOME_ROW_ITEMS, true )' )
		&& false !== strpos( $s, "define( 'TOPERSIA_HOME_ROW_ITEMS', 4 )" )
		&& false !== strpos( $s, 'topersia_g_home_month_range( $d[0], $d[1] )' );
};
tp441_mut( 'P5-D560 رویداد: فقط event_start_date، گذشته نه، نزدیک‌ترین نخست، ۴ ردیف، زیرعنوان بازۀ ماه — جهش: رویداد گذشته هم', $w51_ev, $w51_row,
	str_replace( " || ( '' !== \$end ? \$end : \$start ) < \$today", '', $w51_row ) );
t( 'P5-D560 سفر: tp_route با «برنامۀ سفر پیشنهادی»، نشان نمونه در هر ردیف', false !== strpos( $w51_row, "topersia_g_home_real( array( 'tp_route' ), TOPERSIA_HOME_ROW_ITEMS )" )
	&& false !== strpos( $w51_row, "__( 'برنامۀ سفر پیشنهادی', 'topersia' )" ) && false !== strpos( $w51_row, 'topersia_g_demo_badge( $id )' ) );

$w51_sea = function ( $s ) {
	return false !== strpos( $s, "foreach ( array( 'winter', 'autumn', 'summer', 'spring' ) as \$s ) {" )               /* از آغاز سطر: زمستان، پاییز، تابستان، بهار */
		&& false !== strpos( $s, "if ( \$live < TOPERSIA_HOME_SEASONS_MIN ) {" ) && false !== strpos( $s, "define( 'TOPERSIA_HOME_SEASONS_MIN', 2 )" )
		&& false !== strpos( $s, "topersia_g_hub_link_for( (string) key( \$st[1] ), array( 'season' => \$s ) )" )        /* هاب نوع چیره با ?season= */
		&& false !== strpos( $s, 'tp-is-empty" aria-disabled="true">' ) && false === strpos( $s, 'tp-is-empty" href' )      /* فصل خالی بی پیوند */
		&& false !== strpos( $s, "if ( ! topersia_g_hub_in_season( \$id, \$s ) ) {" )                                       /* همان قاعدۀ هاب (تمام سال در هر فصل) */
		&& false !== strpos( $s, '$att  = $st[2] ? topersia_g_card_image( $st[2] ) : 0;' );                               /* عکس نخستین مورد، وگرنه گرادیان */
};
tp441_mut( 'P5-D560 فصل: ترتیب زمستان→بهار، هاب نوع چیره با ?season=، خالی کم‌رنگ و بی پیوند، کمتر از ۲ فصل ← ستون نه — جهش: ترتیب بهار نخست', $w51_sea, $w51_row,
	str_replace( "array( 'winter', 'autumn', 'summer', 'spring' ) as \$s ) {", "array( 'spring', 'summer', 'autumn', 'winter' ) as \$s ) {", $w51_row ) );
t( 'P5-D560 فصل: جهش — حد ستون برداشته می‌افتد', ! $w51_sea( str_replace( 'if ( $live < TOPERSIA_HOME_SEASONS_MIN ) {', 'if ( false ) {', $w51_row ) ) );

$w51_rcss = function ( $c ) {
	$a = strpos( $c, '/* ۶ب ردیف سه‌ستونی' );
	$b = strpos( $c, '/* ۷ راهنمای سفر' );
	$blk = ( false !== $a && false !== $b ) ? substr( $c, $a, $b - $a ) : '';
	return '' !== $blk && 0 === preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(/i', $blk ) && false === strpos( $blk, 'letter-spacing' )
		&& 4 === substr_count( $blk, '__ph{background:linear-gradient(180deg,var(--tp-s-' )                             /* گرادیان هر فصل از توکن */
		&& false !== strpos( $blk, '.tp-home-season__cap{' ) && false !== strpos( $blk, 'background:var(--tp-bg);color:var(--tp-text-strong)' )   /* نام روی نوار سطح، نه روی عکس */
		&& false !== strpos( $blk, '.tp-home-season.tp-is-empty{opacity:' )
		&& false !== strpos( $c, 'grid-template-columns:repeat(4,minmax(0,1fr))' );
};
tp441_mut( 'P5-D560 CSS: فقط توکن، گرادیان فصل از --tp-s-*، نام روی نوار سطح، خالی کم‌رنگ — جهش: رنگ خام', $w51_rcss, $w51_hcs,
	str_replace( '.tp-home-season.tp-is-empty{opacity:.55;', '.tp-home-season.tp-is-empty{opacity:.55;color:#777;', $w51_hcs ) );

if ( function_exists( 'topersia_g_jalali' ) && function_exists( 'topersia_g_greg_months' ) ) {
	require_once $w51_root . '/plugin/topersia-render/includes/topersia-g-home-row.php';
	t( 'P5-D560 رفتار: بازۀ ماه — یک ماه', 'آذر ' . topersia_g_fa( 1405 ) === topersia_g_home_month_range( '2026-11-23', '' ) );
	t( 'P5-D560 رفتار: بازۀ ماه — دو ماه یک سال', 'آذر – دی ' . topersia_g_fa( 1405 ) === topersia_g_home_month_range( '2026-11-23', '2026-12-25' ) );
	t( 'P5-D560 رفتار: بازۀ ماه — دو سال', 'اسفند ' . topersia_g_fa( 1405 ) . ' – فروردین ' . topersia_g_fa( 1406 ) === topersia_g_home_month_range( '2027-03-01', '2027-04-05' ) );
	t( 'P5-D560 رفتار: پایان پیش از آغاز ← فقط آغاز', 'آذر ' . topersia_g_fa( 1405 ) === topersia_g_home_month_range( '2026-11-23', '2026-11-01' ) );
}
