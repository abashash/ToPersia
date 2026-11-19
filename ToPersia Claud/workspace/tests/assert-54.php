<?php
/**
 * دستۀ 54 — افزونۀ تبلیغ اختصاصی (P5-D621 تا P5-D625). از assert.php بار می‌شود؛ خواندن کد و منطق خالص
 * (plugin/topersia-ads/includes/tp-ads-logic.php بی وردپرس)، هر ادعا با جهش. رفتار روی وردپرس واقعی: tests/w54-local.js.
 *
 *   php tests/assert.php --launch ← هر tp_ad نمایشی در پایگاه‌داده قرمز (scripts/tp-ads-demo.php launch؛ assert-51 هم _tp_demo را می‌گیرد).
 */

echo "\nدستۀ 54 — افزونۀ تبلیغ اختصاصی: جایگاه، تراکم، چرخش، شمارش بی‌نام، پیشخوان، صفحۀ فروش\n" . str_repeat( '─', 78 ) . "\n";

require_once __DIR__ . '/env.php';
$w54_root   = dirname( __DIR__ );
$w54_read   = function ( $p ) use ( $w54_root ) {
	return (string) @file_get_contents( $w54_root . '/' . $p );
};
$w54_main   = $w54_read( 'plugin/topersia-ads/topersia-ads.php' );
$w54_logic  = $w54_read( 'plugin/topersia-ads/includes/tp-ads-logic.php' );
$w54_data   = $w54_read( 'plugin/topersia-ads/includes/tp-ads-data.php' );
$w54_stats  = $w54_read( 'plugin/topersia-ads/includes/tp-ads-stats.php' );
$w54_render = $w54_read( 'plugin/topersia-ads/includes/tp-ads-render.php' );
$w54_track  = $w54_read( 'plugin/topersia-ads/includes/tp-ads-track.php' );
$w54_admin  = $w54_read( 'plugin/topersia-ads/includes/tp-ads-admin.php' );
$w54_pages  = $w54_read( 'plugin/topersia-ads/includes/tp-ads-pages.php' );
$w54_css    = $w54_read( 'plugin/topersia-ads/assets/tp-ads.css' );
$w54_js     = $w54_read( 'plugin/topersia-ads/assets/tp-ads.js' );
$w54_wpml   = $w54_read( 'plugin/topersia-ads/wpml-config.xml' );
$w54_home   = $w54_read( 'plugin/topersia-render/includes/topersia-g-home.php' );
$w54_art    = $w54_read( 'plugin/topersia-render/includes/topersia-g-article.php' );
$w54_foot   = $w54_read( 'plugin/topersia-render/includes/topersia-g-footer.php' );
$w54_frag   = $w54_read( 'plugin/topersia-render/fragment.php' );
$w54_fkey   = $w54_read( 'plugin/topersia-render/includes/topersia-frag-key.php' );
$w54_priv   = $w54_read( 'docs/legal/PRIVACY-DRAFT.md' );
$w54_robots = (string) @file_get_contents( TP_ROOT . '/robots.txt' );
$w54_launch = in_array( '--launch', isset( $argv ) ? (array) $argv : array(), true );
$w54_tpls   = array();
foreach ( array( 'hub-archive', 'news-archive', 'article-archive', 'news-list' ) as $w54_t ) {
	$w54_tpls[ $w54_t ] = $w54_read( 'plugin/topersia-render/templates/' . $w54_t . '.php' );
}

require_once $w54_root . '/plugin/topersia-ads/includes/tp-ads-logic.php';

/* قلاب‌های render (topersia_g_ad_*) در هستۀ render؛ صافی‌هایی که هسته در stub می‌افزاید پس از بار برمی‌گردند تا ادعای بعدی دست‌نخورده بماند */
$w54_fsnap = isset( $GLOBALS['tp_filters'] ) ? $GLOBALS['tp_filters'] : array();
require_once TP_PLUGIN_DIR . 'includes/topersia-g-core.php';
$GLOBALS['tp_filters'] = $w54_fsnap;

/* رکورد آماده برای منطق خالص */
$w54_ad  = function ( $id, array $o = array() ) {
	return topersia_ads_norm( array_merge( array(
		'id'     => $id,
		'url'    => 'https://example.test/x',
		'slots'  => array( 'home-mid', 'single-inline', 'single-rail', 'hub-infeed', 'footer' ),
		'img'    => array( 'wide' => 11, 'mwide' => 12, 'rect' => 13, 'card' => 14 ),
		'alt'    => array( 'fa' => 'آگهی', 'en' => 'Ad', 'ar' => 'إعلان' ),
		'status' => 'active',
	), $o ) );
};
$w54_ctx = function ( array $o = array() ) {
	return array_merge( array( 'now' => 1800000000, 'lang' => 'fa', 'page' => 'home', 'device' => 'desktop', 'demo' => false, 'turn' => 0 ), $o );
};
$w54_q   = function ( $s ) {
	return preg_quote( $s, '/' );
};

/* ── بند ۲ · ۳ — افزونه، دروازۀ محلی، نوع خصوصی ─────────────────────── */
$w54_gate = function ( $s ) {
	return false !== strpos( $s, "'local' === wp_get_environment_type()" ) && false !== strpos( $s, 'if ( topersia_ads_enabled() ) {' );
};
tp441_mut( 'بند ۲: فقط محلی تا ممیزی — topersia_ads_enabled() پیش‌فرض محیط local و همۀ لایه‌ها پشت آن — جهش: همیشه روشن', $w54_gate, $w54_main,
	str_replace( "'local' === wp_get_environment_type()", 'true', $w54_main ) );
$w54_private = function ( $s ) {
	foreach ( array( "'public'\s+=> false", "'publicly_queryable'\s+=> false", "'exclude_from_search'\s+=> true", "'show_in_rest'\s+=> false", "'rewrite'\s+=> false", "'has_archive'\s+=> false", "'query_var'\s+=> false" ) as $n ) {
		if ( ! preg_match( '/' . $n . '/', $s ) ) {
			return false;
		}
	}
	return false !== strpos( $s, "register_post_type( 'tp_ad'" );
};
tp441_mut( 'بند ۳: نوع tp_ad خصوصی — نه عمومی، نه پرس‌وجو، نه جست‌وجو، نه REST، نه نشانی — جهش: public=true', $w54_private, $w54_data,
	preg_replace( "/'public'(\s+)=> false/", "'public'\$1=> true", $w54_data, 1 ) );
t( 'بند ۳: WPML — tp_ad ترجمه نمی‌شود (یک پست برای سه زبان) و متاهای آن ignore',
	false !== strpos( $w54_wpml, '<custom-type translate="0">tp_ad</custom-type>' ) && 3 === substr_count( $w54_wpml, '<custom-field action="ignore">_tp_ad' ) );
t( 'بارکردن کاتالوگ: load_plugin_textdomain topersia-ads، سرآیند Text Domain و Domain Path',
	false !== strpos( $w54_main, "load_plugin_textdomain( 'topersia-ads'" ) && false !== strpos( $w54_main, 'Text Domain: topersia-ads' ) && false !== strpos( $w54_main, 'Domain Path: /languages' ) );

/* ── بند ۴ — جدول آمار ۱۳ ماه، مهاجرت نسخه‌دار برگشت‌پذیر ──────────────── */
$w54_mig = function ( $s ) {
	return false !== strpos( $s, "define( 'TOPERSIA_ADS_KEEP_MONTHS', 13 )" )
		&& false !== strpos( $s, "update_option( 'tp_ads_db_version', TOPERSIA_ADS_DB, false )" )
		&& false !== strpos( $s, 'DROP TABLE IF EXISTS' ) && false !== strpos( $s, "delete_option( 'tp_ads_db_version' )" );
};
tp441_mut( 'بند ۴: ۱۳ ماه، نسخه در گزینه، down جدول و نسخه را برمی‌دارد — جهش: ۱۲ ماه', $w54_mig, $w54_stats,
	str_replace( "'TOPERSIA_ADS_KEEP_MONTHS', 13", "'TOPERSIA_ADS_KEEP_MONTHS', 12", $w54_stats ) );
preg_match( '/function topersia_ads_schema_sql.*?\n\t}\n/s', $w54_stats, $w54_m );
$w54_schema = isset( $w54_m[0] ) ? $w54_m[0] : '';
$w54_noip   = function ( $s ) {
	return '' !== $s && 2 === substr_count( $s, 'CREATE TABLE' ) && ! preg_match( '/^\s*(ip|ua|user_id|user|cookie|visitor|hash)\b/mi', $s );
};
tp441_mut( 'بند ۴ · P5-D622: جدول آمار فقط تبلیغ · جایگاه · زبان · روز — هیچ ستون IP، UA، کوکی، هش یا کاربر — جهش: ستون ip', $w54_noip, $w54_schema,
	str_replace( "  ad_id bigint(20) unsigned NOT NULL,\n", "  ip varchar(45) NOT NULL,\n  ad_id bigint(20) unsigned NOT NULL,\n", $w54_schema ) );

/* ── بند ۵ — ثبت جایگاه‌ها ──────────────────────────────────────────── */
$w54_slots = topersia_ads_slots();
t( 'بند ۵: نُه جایگاه، همان شناسه‌ها؛ پاورقی ۱ تا ۴ و بیرون از تراکم؛ ستون‌های کناری و hub-top فقط دسکتاپ',
	array( 'home-mid', 'hub-top', 'hub-infeed', 'single-inline', 'single-rail', 'single-end', 'entity-rail', 'entity-mid', 'footer' ) === array_keys( $w54_slots )
	&& 4 === $w54_slots['footer']['max'] && false === $w54_slots['footer']['density']
	&& '' === $w54_slots['single-rail']['m'] && '' === $w54_slots['entity-rail']['m'] && '' === $w54_slots['hub-top']['m'] );
$w54_excl = function ( $s ) {
	return false !== strpos( $s, 'is_404() || is_search()' ) && false !== strpos( $s, "'topersia_ads_legal_slugs'" ) && false !== strpos( $s, "'ToPersia_Admin', 'account_page_id'" );
};
tp441_mut( 'بند ۵ · P5-D623: هیچ تبلیغ در ۴۰۴، جست‌وجو، صفحۀ حقوقی و حساب — جهش: جست‌وجو بیرون نمی‌ماند', $w54_excl, $w54_render,
	str_replace( 'is_404() || is_search()', 'is_404()', $w54_render ) );
t( 'بند ۵: هیچ جایگاهی در هیرو — افزونه و قلاب‌های render هیچ جایگاه سربرگ ندارند',
	! preg_match( '/topersia_g_ad_slot\([^)]*hero/i', $w54_home . $w54_art . implode( '', $w54_tpls ) ) && false === stripos( $w54_render, 'hero' ) && false === stripos( $w54_logic, "'hero" ) );
$w54_calls = function ( array $tpls ) {
	foreach ( $tpls as $s ) {
		if ( false === strpos( $s, "topersia_g_ad_slot( 'hub-top' )" ) || false === strpos( $s, 'topersia_g_ad_feed( ' ) ) {
			return false;
		}
	}
	return 4 === count( $tpls );
};
tp441_mut( 'بند ۵: چهار قالب هاب و فهرست، هر کدام hub-top و کارت میان فهرست — جهش: news-list بی hub-top', $w54_calls, $w54_tpls,
	array_merge( $w54_tpls, array( 'news-list' => str_replace( "topersia_g_ad_slot( 'hub-top' )", "''", $w54_tpls['news-list'] ) ) ) );
t( 'بند ۵: صفحۀ اول دو home-mid؛ خبر و مقاله inline · end · rail؛ مقصد از topersia_g_layout',
	false !== strpos( $w54_home, "topersia_g_ad_slot( 'home-mid', 0 )" ) && false !== strpos( $w54_home, "topersia_g_ad_slot( 'home-mid', 1 )" )
	&& false !== strpos( $w54_art, 'topersia_g_ad_inline( $content )' ) && false !== strpos( $w54_art, "topersia_g_ad_slot( 'single-end' )" ) && false !== strpos( $w54_art, "topersia_g_ad_slot( 'single-rail' )" )
	&& false !== strpos( $w54_render, "add_filter( 'topersia_g_layout'" ) );
$w54_none = isset( $GLOBALS['tp_filters']['topersia_ad_slot'] ) || isset( $GLOBALS['tp_filters']['topersia_ad_feed'] ) || isset( $GLOBALS['tp_filters']['topersia_ad_inline'] );
t( 'بند ۵: بی افزونه render دست‌نخورده — جایگاه تهی، متن و کارت‌ها همان',
	! $w54_none && '' === topersia_g_ad_slot( 'home-mid' ) && '' === topersia_g_ad_slot( 'home-mid', 0, true ) && '<p>a</p>' === topersia_g_ad_inline( '<p>a</p>' ) && array( 'x', 'y' ) === topersia_g_ad_feed( array( 'x', 'y' ) ) );
$w54_ftpre = function ( $s ) use ( $w54_q ) {
	return (bool) preg_match( '/function topersia_g_sc_footer_ads\(\)\s*\{\s*(?:\/\*.*?\*\/\s*)?'
		. $w54_q( "\$pre = function_exists( 'apply_filters' ) ? apply_filters( 'topersia_g_footer_ads_pre', null ) : null;" ) . '\s*'
		. $w54_q( 'if ( null !== $pre ) {' ) . '\s*' . $w54_q( 'return (string) $pre;' ) . '/su', $s );
};
tp441_mut( 'بند ۵: پاورقی — پیش‌فیلتر نخستین دستور؛ null ← همان رفتار گزینه و نمونۀ پیشین (footer-gate) — جهش: بی پیش‌فیلتر', $w54_ftpre, $w54_foot,
	str_replace( "apply_filters( 'topersia_g_footer_ads_pre', null )", 'null', $w54_foot ) );

/* ── بند ۶ — فیلتر، اولویت، وزن، چرخش قطعی ۱۰ دقیقه، جایگاه خالی ─────── */
$w54_a    = $w54_ad( 1, array( 'weight' => 50 ) );
$w54_b    = $w54_ad( 2, array( 'weight' => 50 ) );
$w54_p    = $w54_ad( 3, array( 'priority' => 9 ) );
$w54_pick = function ( $ads, $ctx ) {
	$r = topersia_ads_pick( $ads, 'home-mid', $ctx );
	return $r ? $r[0]['id'] : 0;
};
t( 'بند ۶: اولویت بالاتر (اسپانسر انحصاری) همیشه برنده، هر بازه',
	3 === $w54_pick( array( $w54_a, $w54_b, $w54_p ), $w54_ctx() ) && 3 === $w54_pick( array( $w54_a, $w54_b, $w54_p ), $w54_ctx( array( 'now' => 1800000000 + 7 * 600 ) ) ) );
$w54_same = $w54_pick( array( $w54_a, $w54_b ), $w54_ctx( array( 'now' => 1800000000 ) ) ) === $w54_pick( array( $w54_a, $w54_b ), $w54_ctx( array( 'now' => 1800000000 + 599 ) ) )
	&& $w54_pick( array( $w54_a, $w54_b ), $w54_ctx() ) === $w54_pick( array( $w54_b, $w54_a ), $w54_ctx() );
$w54_seen = array();
for ( $w54_i = 0; $w54_i < 40; $w54_i++ ) {
	$w54_seen[ $w54_pick( array( $w54_a, $w54_b ), $w54_ctx( array( 'now' => 1800000000 + $w54_i * 600 ) ) ) ] = true;
}
t( 'بند ۶ · P5-D622: چرخش قطعی — در یک بازۀ ۱۰ دقیقه و هر ترتیب ورودی یکی؛ در بازه‌های پیاپی هر دو وزن برابر دیده می‌شوند',
	600 === TOPERSIA_ADS_WINDOW && $w54_same && isset( $w54_seen[1], $w54_seen[2] ) );
t( 'بند ۶: فیلتر — متوقف، پیش از شروع، پس از پایان، سقف پر، زبان دیگر، بی متن جایگزین همان زبان ← رد؛ کامل ← پذیرفته',
	! topersia_ads_eligible( $w54_ad( 1, array( 'status' => 'paused' ) ), 'home-mid', $w54_ctx() )
	&& ! topersia_ads_eligible( $w54_ad( 1, array( 'start' => 1800000001 ) ), 'home-mid', $w54_ctx() )
	&& ! topersia_ads_eligible( $w54_ad( 1, array( 'end' => 1800000000 ) ), 'home-mid', $w54_ctx() )
	&& ! topersia_ads_eligible( $w54_ad( 1, array( 'cap_clk' => 5, 'clk' => 5 ) ), 'home-mid', $w54_ctx() )
	&& ! topersia_ads_eligible( $w54_ad( 1, array( 'langs' => array( 'en' ) ) ), 'home-mid', $w54_ctx() )
	&& ! topersia_ads_eligible( $w54_ad( 1, array( 'alt' => array( 'fa' => '', 'en' => 'x', 'ar' => 'x' ) ) ), 'home-mid', $w54_ctx() )
	&& topersia_ads_eligible( $w54_ad( 1 ), 'home-mid', $w54_ctx() ) );
t( 'بند ۵ · ۶: جایگاه در صفحۀ نوع دیگر ← رد؛ موبایلِ جایگاه فقط‌دسکتاپ ← رد (بی چسبان موبایل)',
	! topersia_ads_eligible( $w54_ad( 1 ), 'home-mid', $w54_ctx( array( 'page' => 'hub' ) ) )
	&& ! topersia_ads_eligible( $w54_ad( 1 ), 'single-rail', $w54_ctx( array( 'page' => 'single', 'device' => 'mobile' ) ) )
	&& topersia_ads_eligible( $w54_ad( 1 ), 'single-rail', $w54_ctx( array( 'page' => 'single' ) ) ) );
$w54_empty = function ( $s ) use ( $w54_q ) {
	return (bool) preg_match( '/function topersia_ads_empty\( \$slot \) \{\s*' . $w54_q( 'if ( ! current_user_can( TOPERSIA_ADS_CAP ) ) {' ) . "\s*return '';/", $s );
};
tp441_mut( 'بند ۶ · P5-D624: جایگاه خالی برای بازدیدکننده هیچ؛ نشانۀ نقطه‌چین فقط با توان manage_tp_ads — جهش: بی شرط توان', $w54_empty, $w54_render,
	str_replace( 'if ( ! current_user_can( TOPERSIA_ADS_CAP ) ) {', 'if ( false ) {', $w54_render ) );

/* بند ۶: دو تبلیغ هم‌رده و دو نوبت «کارت میان فهرست» — هر نوبت یک واحد برای هر دو دستگاه، در هر بازۀ ۱۰ دقیقه؛
   بذر دستگاه نباید هر دو را در نوبت نخست مصرف کند (هاب ۳۹۰ دستۀ 54). ردۀ بالاتر موبایل برنده می‌ماند. */
$w54_plan_ok = function ( $plan ) use ( $w54_ad, $w54_ctx ) {
	$two = array( 21 => $w54_ad( 21 ), 22 => $w54_ad( 22 ) );
	for ( $k = 0; $k < 60; $k++ ) {
		$t   = 1800000000 + $k * 600;
		$p0  = $plan( $two, 'hub-infeed', $w54_ctx( array( 'now' => $t, 'page' => 'hub' ) ), $w54_ctx( array( 'now' => $t, 'page' => 'hub', 'device' => 'mobile' ) ), true, array() );
		$p1  = $plan( $two, 'hub-infeed', $w54_ctx( array( 'now' => $t, 'page' => 'hub', 'turn' => 1 ) ), $w54_ctx( array( 'now' => $t, 'page' => 'hub', 'device' => 'mobile', 'turn' => 1 ) ), true,
			array_map( function ( $r ) { return (int) $r[0]['id']; }, $p0 ) );
		if ( 1 !== count( $p0 ) || 'both' !== $p0[0][1] || 1 !== count( $p1 ) || 'both' !== $p1[0][1] ) {
			return false;
		}
	}
	$mob = array( 31 => $w54_ad( 31 ), 32 => $w54_ad( 32, array( 'device' => 'mobile', 'priority' => 5 ) ) );
	$pm  = $plan( $mob, 'hub-infeed', $w54_ctx( array( 'page' => 'hub' ) ), $w54_ctx( array( 'page' => 'hub', 'device' => 'mobile' ) ), true, array() );
	return 2 === count( $pm ) && 31 === (int) $pm[0][0]['id'] && 'd' === $pm[0][1] && 32 === (int) $pm[1][0]['id'] && 'm' === $pm[1][1];
};
$w54_plan_old = function ( $all, $slot, $cd, $cm, $mobile, $used ) {
	$pd = topersia_ads_pick( $all, $slot, $cd, 1, $used );
	$pm = $mobile ? topersia_ads_pick( $all, $slot, $cm, 1, $used ) : array();
	return ( $pd && $pm && $pd[0]['id'] === $pm[0]['id'] ) ? array( array( $pd[0], 'both' ) ) : array_merge( $pd ? array( array( $pd[0], 'd' ) ) : array(), $pm ? array( array( $pm[0], 'm' ) ) : array() );
};
$w54_plan_share = function ( $all, $slot, $cd, $cm, $mobile, $used ) use ( $w54_plan_old ) {
	$pd = topersia_ads_pick( $all, $slot, $cd, 1, $used );
	return $pd && topersia_ads_eligible( $pd[0], $slot, $cm ) ? array( array( $pd[0], 'both' ) ) : $w54_plan_old( $all, $slot, $cd, $cm, $mobile, $used );
};
tp441_mut( 'بند ۶: نوبت دوم «کارت میان فهرست» در هر بازه تبلیغ دارد — دستگاه‌ها هم‌رده را شریک می‌شوند — جهش: بذر دستگاه جدا (پیش از رفع)', $w54_plan_ok, 'topersia_ads_plan', $w54_plan_old );
tp441_mut( 'بند ۶: اسپانسر فقط‌موبایل با اولویت بالاتر در موبایل برنده می‌ماند — جهش: شریک کردن بی شرط رده', $w54_plan_ok, 'topersia_ads_plan', $w54_plan_share );
$w54_plan_src = function ( $s ) {
	return false !== strpos( $s, "\$plan = topersia_ads_plan( \$all, \$slot, topersia_ads_ctx( 'desktop', \$i ), topersia_ads_ctx( 'mobile', \$i ), '' !== \$s['m'], \$st['used'] );" );
};
tp441_mut( 'بند ۶: جایگاه‌ساز از topersia_ads_plan می‌گذرد — جهش: انتخاب جدای دستگاه‌ها در render', $w54_plan_src, $w54_render,
	str_replace( "\$plan = topersia_ads_plan(", "\$plan = topersia_ads_plan_x(", $w54_render ) );

/* ── بند ۷ — تراکم ─────────────────────────────────────────────────── */
$w54_b7  = topersia_ads_budget();
$w54_got = array();
for ( $w54_i = 0; $w54_i < 5; $w54_i++ ) {
	$w54_got[] = topersia_ads_take( $w54_b7, 'both' );
}
t( 'بند ۷ · P5-D623: بودجه ۳ دسکتاپ و ۲ موبایل — دو «هر دو»، سپس فقط دسکتاپ، سپس هیچ',
	array( 'both', 'both', 'd', '', '' ) === $w54_got && 3 === TOPERSIA_ADS_MAX_DESK && 2 === TOPERSIA_ADS_MAX_MOB );
$w54_fbud = function ( $s ) {
	return false !== strpos( $s, "'footer' === \$slot || 'none' === \$page" );
};
tp441_mut( 'بند ۷: پاورقی بیرون از بودجۀ تراکم (جایگاه‌ساز صفحه پاورقی نمی‌سازد) — جهش: پاورقی هم از بودجه', $w54_fbud, $w54_render,
	str_replace( "'footer' === \$slot || 'none' === \$page", "'none' === \$page", $w54_render ) );
$w54_p4 = '<p>1</p><blockquote><p>q</p></blockquote><p>2</p><ul><li><p>l</p></li></ul><p>3</p><p>4</p>';
t( 'بند ۵ · ۷: میان متن پس از بند سوم سطح بالا (بند نقل‌قول و فهرست شمرده نمی‌شود)؛ کمتر از سه بند ← بی تبلیغ',
	false !== strpos( topersia_ads_inline_insert( $w54_p4, '<X>' ), '<p>3</p><X><p>4</p>' ) && '<p>1</p><p>2</p>' === topersia_ads_inline_insert( '<p>1</p><p>2</p>', '<X>' ) );
t( 'بند ۵: کارت میان فهرست پس از ۴ کارت و سپس هر ۸، نه ته فهرست',
	array( 4, 12 ) === topersia_ads_feed_positions( 20 ) && array() === topersia_ads_feed_positions( 4 ) && array( 4 ) === topersia_ads_feed_positions( 5 ) );
t( 'بند ۷: شمار واحد هر صفحه برای مدیر تبلیغ در یک توضیح HTML (page · d · m · footer)',
	false !== strpos( $w54_render, '<!-- tp-ads page=' ) && false !== strpos( $w54_render, "' footer=' . (int) \$st['units']['footer']" ) );
/* جای واحد: هر جایگاه جز «زیر نوار ابزار هاب» بسته رندر می‌شود و tp-ads.js فقط یکسره زیر صفحه‌نمایش و دو صفحه‌نمایش دور از واحد پیشین بازش می‌کند */
$w54_gate_css = function ( $css ) {
	$c = preg_replace( '#/\*.*?\*/#s', '', $css );
	return false !== strpos( $c, '.tp-ad-slot:not(.tp-ad-on):not(.tp-ad-slot--hub-top){display:none}' )
		&& false !== strpos( $c, '.tp-news-grid--rows > .tp-ad-slot--hub-infeed{max-inline-size:400px}' );
};
tp441_mut( 'بند ۷: جایگاه بسته تا tp-ads.js بازش کند (جز «زیر نوار ابزار هاب»)؛ کارت میان فهرست هم‌اندازۀ کارت — جهش: جایگاه از آغاز باز', $w54_gate_css, $w54_css,
	str_replace( '.tp-ad-slot:not(.tp-ad-on):not(.tp-ad-slot--hub-top){display:none}', '', $w54_css ) );
$w54_gate_js = function ( $s ) {
	return false !== strpos( $s, 'GAP = 2' ) && false !== strpos( $s, 'EDGE = 32' ) && false !== strpos( $s, "why = y < sy + vh + EDGE ? 'screen' : ( ! o.side && null !== last && y - last < GAP * vh ? 'gap' : '' );" )
		&& false !== strpos( $s, "s.setAttribute( 'data-tp-ad-off', why );" ) && false !== strpos( $s, 's.classList.add( ON );' )
		&& false !== strpos( $s, '0.6 * window.innerWidth' ) && preg_match( '/MutationObserver\(.*?place\(\);\s*scan\(\);/s', $s );
};
tp441_mut( 'بند ۷: tp-ads.js — باز فقط یکسره زیر صفحه‌نمایش جاری (نخستین صفحه‌نمایش موبایل بی تبلیغ، بی CLS) و ≥ دو صفحه‌نمایش از واحد پیشین؛ ستون کناری بیرون؛ قطعۀ تازه هم — جهش: فاصلۀ یک صفحه‌نمایش', $w54_gate_js, $w54_js,
	str_replace( 'GAP = 2', 'GAP = 1', $w54_js ) );
tp441_mut( '  جهش دوم: بی شرط «زیر صفحه‌نمایش» ← باید بیفتد', $w54_gate_js, $w54_js,
	str_replace( "why = y < sy + vh + EDGE ? 'screen' : ", 'why = ', $w54_js ) );
/* دستۀ 55 بند ۷: لبۀ «زیر صفحه‌نمایش» حاشیۀ سایۀ کارت را می‌گیرد (کارت ۲px زیر لبه، سایه ۱۶px بالاتر ← CLS) */
tp441_mut( '  جهش سوم: بی حاشیۀ EDGE (لبۀ خام صفحه‌نمایش) ← باید بیفتد', $w54_gate_js, $w54_js,
	str_replace( "why = y < sy + vh + EDGE ? 'screen'", "why = y < sy + vh ? 'screen'", $w54_js ) );
$w54_eager = function ( $s ) {
	return false !== strpos( $s, "'lazy'  => 'hub-top' !== \$slot," );
};
tp441_mut( 'بند ۸: تصویر lazy مگر نخستین صفحه‌نمایش — «زیر نوار ابزار هاب» تنها جایگاه نخستین صفحه‌نمایش (fetchpriority=low)، بقیه lazy — جهش: همه lazy', $w54_eager, $w54_render,
	str_replace( "'lazy'  => 'hub-top' !== \$slot,", "'lazy'  => true,", $w54_render ) );
$w54_eg = topersia_ads_unit_html( array( 'ad' => $w54_ad( 7 ), 'slot' => 'hub-top', 'vis' => 'd', 'lang' => 'fa', 'href' => 'http://topersia.local/ad-go/7/?s=hub-top&l=fa',
	'img_d' => array( '/d.jpg', 1200, 300 ), 'lazy' => false, 't' => array( 'tag' => 'تبلیغ', 'demo' => 'نمونۀ نمایشی', 'ph' => 'جای تبلیغ', 'aria' => 'تبلیغ' ) ) );
t( 'بند ۸: واحد بی lazy ← fetchpriority="low" (نه loading)', false !== strpos( $w54_eg, 'fetchpriority="low"' ) && false === strpos( $w54_eg, 'loading=' ) );
$w54_ftns = function ( $s ) {
	return false !== strpos( $s, '\'" target="_blank" rel="sponsored noopener" data-nosnippet>\'' ) && false !== strpos( $s, '<div class="tp-ft-ad tp-ft-ad--demo" data-nosnippet>' );
};
tp441_mut( 'بند ۸: پاورقی پیشین render (بی تبلیغ واقعی topersia-ads) هم data-nosnippet — پیوند و نمونۀ نمایشی — جهش: بی نشان', $w54_ftns, $w54_foot,
	str_replace( ' data-nosnippet>', '>', $w54_foot ) );

/* ── بند ۸ — HTML واحد و شیوه‌نامه ─────────────────────────────────── */
$w54_t  = array( 'tag' => 'تبلیغ', 'demo' => 'نمونۀ نمایشی', 'ph' => 'جای تبلیغ', 'aria' => 'تبلیغ' );
$w54_u  = topersia_ads_unit_html( array( 'ad' => $w54_ad( 7 ), 'slot' => 'home-mid', 'vis' => 'both', 'lang' => 'fa', 'href' => 'http://topersia.local/ad-go/7/?s=home-mid&l=fa',
	'img_d' => array( '/d.jpg', 1200, 300 ), 'img_m' => array( '/m.jpg', 800, 400 ), 'lazy' => true, 't' => $w54_t ) );
$w54_unit = function ( $h ) {
	return false !== strpos( $h, 'rel="sponsored noopener"' ) && false !== strpos( $h, 'target="_blank"' ) && false !== strpos( $h, 'data-nosnippet' )
		&& false !== strpos( $h, '<picture><source media="(max-width: 767px)" srcset="/m.jpg" width="800" height="400">' )
		&& false !== strpos( $h, 'width="1200" height="300" alt="آگهی" loading="lazy"' ) && false !== strpos( $h, '<span class="tp-ad__tag">تبلیغ</span>' )
		&& false !== strpos( $h, 'data-tp-ad="7|home-mid|fa"' ) && false !== strpos( $h, 'tp-ad--r-wide tp-ad--rm-mwide' );
};
tp441_mut( 'بند ۸: واحد — sponsored noopener، برگۀ تازه، data-nosnippet، <picture> با ابعاد صریح، lazy، alt، برچسب «تبلیغ» — جهش: بی sponsored', $w54_unit, $w54_u,
	str_replace( 'sponsored ', '', $w54_u ) );
$w54_dm = topersia_ads_unit_html( array( 'ad' => $w54_ad( 8, array( 'demo' => true ) ), 'slot' => 'home-mid', 'vis' => 'both', 'lang' => 'fa', 'href' => '', 'img_d' => null, 'lazy' => true, 't' => $w54_t ) );
t( 'بند ۱۰: نمایشی — برچسب «نمونۀ نمایشی»، بی پیوند، بی data-tp-ad (شمرده نمی‌شود)',
	false !== strpos( $w54_dm, 'نمونۀ نمایشی' ) && false === strpos( $w54_dm, '<a ' ) && false === strpos( $w54_dm, 'data-tp-ad=' ) && false !== strpos( $w54_dm, 'tp-ad--demo' ) );
preg_match_all( '/class="([^"]*)"/', $w54_u . $w54_dm, $w54_cm );
t( 'P5-D180: هر کلاس واحد رندرشده پیشوند tp- دارد',
	(bool) $w54_cm[1] && array() === array_filter( preg_split( '/\s+/', trim( implode( ' ', $w54_cm[1] ) ) ), function ( $c ) {
		return 0 !== strpos( $c, 'tp-' );
	} ) );
$w54_tok = function ( $css ) {
	$c = preg_replace( '#/\*.*?\*/#s', '', $css );
	return ! preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(|gradient|letter-spacing|text-shadow/i', $c )
		&& ! preg_match( '/box-shadow:(?!var\(--tp-shadow-none,none\))/', $c )
		&& false !== strpos( $c, '.tp-ad__a:focus-visible{outline:2px solid var(--tp-primary-text)' ) && false !== strpos( $c, 'aspect-ratio:var(--tp-ad-r)' );
};
tp441_mut( 'بند ۸: tp-ads.css فقط توکن --tp-*، بی گرادیان و سایه و letter-spacing؛ focus-visible؛ نسبت ثابت — جهش: رنگ خام', $w54_tok, $w54_css,
	str_replace( 'background:var(--tp-tile-bg)', 'background:#fafafa', $w54_css ) );
t( 'بند ۸: نسبت جایگاه با ابعاد پیشنهادی همان نسبت (۴:۱ · ۲:۱ · ۶:۵ · ۴:۳)',
	array_reduce( topersia_ads_ratios(), function ( $ok, $r ) {
		list( $a, $b ) = array_map( 'intval', explode( ' / ', $r[0] ) );
		return $ok && $r[1] * $b === $r[2] * $a;
	}, true ) );
$w54_save = function ( $s ) use ( $w54_q ) {
	return (bool) preg_match( '/' . $w54_q( "if ( 'active' === \$val['status'] && \$why ) {" ) . '\s*' . $w54_q( "\$val['status'] = 'paused';" ) . '/', $s )
		&& false !== strpos( $s, "wp_verify_nonce( sanitize_text_field( wp_unslash( \$_POST['tp_ad_nonce'] ) ), 'tp_ad_save' )" );
};
tp441_mut( 'بند ۸ · ۱۱: ذخیره با nonce؛ فعالِ ناقص (بی تصویر، بی alt) متوقف می‌ماند — جهش: ناقص فعال', $w54_save, $w54_data,
	str_replace( "\$val['status'] = 'paused';", "\$val['status'] = 'active';", $w54_data ) );

/* ── بند ۹ — کلیک و نمایش بی‌نام ───────────────────────────────────── */
$w54_go = function ( $s ) {
	return false !== strpos( $s, "add_rewrite_rule( '^ad-go/([0-9]+)/?\$', 'index.php?tp_ad_go=\$matches[1]', 'top' )" )
		&& false !== strpos( $s, "wp_redirect( esc_url_raw( \$res[0] ), 302, 'ToPersia' )" ) && false !== strpos( $s, "header( 'X-Robots-Tag: noindex, nofollow' )" );
};
tp441_mut( 'بند ۹: /ad-go/{id}/ با ۳۰۲ و noindex — جهش: ۳۰۱', $w54_go, $w54_track, str_replace( ', 302, ', ', 301, ', $w54_track ) );
$w54_rb = function ( $txt ) {
	$n = 0;
	foreach ( preg_split( '/\n(?=User-agent:)/i', trim( str_replace( "\r\n", "\n", $txt ) ) ) as $g ) {
		if ( ! preg_match( '/^User-agent:/i', ltrim( $g ) ) || preg_match( '#^Disallow:\s*/\s*$#mi', $g ) ) {
			continue;
		}
		if ( ! preg_match( '#^Disallow: /ad-go/$#m', $g ) || ! preg_match( '#^Disallow: /en/ad-go/$#m', $g ) || ! preg_match( '#^Disallow: /ar/ad-go/$#m', $g ) ) {
			return false;
		}
		$n++;
	}
	return $n > 0;
};
tp441_mut( 'بند ۹: robots.txt — /ad-go/ (و /en/ · /ar/) در هر گروهِ باز (ربات‌های بسته بیرون) — جهش: یک گروه بی آن', $w54_rb, $w54_robots,
	preg_replace( '#Disallow: /ad-go/\r?\n#', '', $w54_robots, 1 ) );
t( 'بند ۹: ربات — Googlebot · curl · Lighthouse · تهی ربات؛ فایرفاکس و کروم نه',
	topersia_ads_is_bot( 'Mozilla/5.0 (compatible; Googlebot/2.1)' ) && topersia_ads_is_bot( 'curl/8.4' ) && topersia_ads_is_bot( 'Chrome-Lighthouse' ) && topersia_ads_is_bot( '' )
	&& ! topersia_ads_is_bot( 'Mozilla/5.0 (Windows NT 10.0; rv:156.0) Gecko/20100101 Firefox/156.0' ) && ! topersia_ads_is_bot( 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/141.0 Safari/537.36' ) );
t( 'بند ۹: کلیک تکراری ۳۰ دقیقه، کلید فقط از هش روزانه و شناسه (بی IP خام)؛ هش هر روز عوض می‌شود',
	1800 === TOPERSIA_ADS_DUP_TTL && topersia_ads_day_hash( '1.2.3.4', 'ua', 1, 's' ) !== topersia_ads_day_hash( '1.2.3.4', 'ua', 2, 's' )
	&& false === strpos( topersia_ads_click_key( topersia_ads_day_hash( '1.2.3.4', 'ua', 1, 's' ), 5 ), '1.2.3.4' )
	&& false !== strpos( $w54_track, 'set_transient( $key, 1, TOPERSIA_ADS_DUP_TTL )' ) );
t( 'بند ۹: توکن نمایش — امروز تا پریروز معتبر، روز چهارم و نمک دیگر نه',
	topersia_ads_token_ok( topersia_ads_imp_token( 20000, 's' ), 20002 * 86400 + 5, 's' ) && ! topersia_ads_token_ok( topersia_ads_imp_token( 20000, 's' ), 20003 * 86400 + 5, 's' )
	&& ! topersia_ads_token_ok( topersia_ads_imp_token( 20000, 'x' ), 20000 * 86400, 's' ) );
$w54_rest = function ( $s ) {
	return false !== strpos( $s, 'strlen( $body ) > 4096' ) && false !== strpos( $s, "define( 'TOPERSIA_ADS_RATE', 120 )" )
		&& false !== strpos( $s, "topersia_ads_token_ok( (string) \$in['t'], time(), wp_salt( 'nonce' ) )" ) && false !== strpos( $s, "register_rest_route( 'topersia-ads/v1', '/imp'" );
};
tp441_mut( 'بند ۹: REST نمایش بی‌نام — بدنه ≤ ۴ کیلوبایت، سقف نرخ، توکن روزانه — جهش: بی سقف بدنه', $w54_rest, $w54_track,
	str_replace( 'strlen( $body ) > 4096', 'false', $w54_track ) );
$w54_jsok = function ( $s ) {
	return false !== strpos( $s, 'IntersectionObserver' ) && false !== strpos( $s, 'sendBeacon' ) && false !== strpos( $s, 'intersectionRatio >= 0.5' ) && false !== strpos( $s, '}, 1000 );' )
		&& ! preg_match( '#https?://|//[a-z0-9.-]+\.[a-z]{2,}/#i', $s ) && strlen( gzencode( $s, 9 ) ) < 2048;
};
tp441_mut( 'بند ۹ · ۱۶: tp-ads.js — ≥۵۰٪ یک ثانیه، sendBeacon، بی نشانی بیرونی، زیر ۲ کیلوبایت gzip — جهش: اسکریپت بیرونی', $w54_jsok, $w54_js,
	$w54_js . "\nvar s=document.createElement('script');s.src='https://cdn.example.com/x.js';" );

/* ── بند ۱۰ — نمایشی فقط پیش‌نمایش ─────────────────────────────────── */
$w54_dad = $w54_ad( 9, array( 'demo' => true, 'url' => '', 'img' => array(), 'alt' => array() ) );
t( 'بند ۱۰: تبلیغ نمایشی فقط در حالت پیش‌نمایش؛ تبلیغ واقعی همیشه بر نمایشی مقدم',
	! topersia_ads_eligible( $w54_dad, 'home-mid', $w54_ctx() ) && topersia_ads_eligible( $w54_dad, 'home-mid', $w54_ctx( array( 'demo' => true ) ) )
	&& 1 === $w54_pick( array( $w54_dad, $w54_ad( 1 ) ), $w54_ctx( array( 'demo' => true ) ) ) );
$w54_demo_src = $w54_read( 'scripts/tp-ads-demo.php' );
$w54_seed     = function ( $s ) {
	return false !== strpos( $s, "if ( ! function_exists( 'topersia_demo_on' ) || ! topersia_demo_on() ) {" ) && false !== strpos( $s, "update_post_meta( \$id, '_tp_demo', '1' );" )
		&& false !== strpos( $s, "if ( 'footer' === \$slot ) {" ) && false !== strpos( $s, 'exit( $ids ? 1 : 0 );' );
};
tp441_mut( 'بند ۱۰: tp-ads-demo.php — seed فقط با topersia_demo_on()، نشان _tp_demo (purge و گیت assert-51 می‌بینند)، پاورقی نه؛ launch کد ۱ اگر مانده — جهش: بی دروازۀ پیش‌نمایش', $w54_seed, $w54_demo_src,
	str_replace( "if ( ! function_exists( 'topersia_demo_on' ) || ! topersia_demo_on() ) {", 'if ( false ) {', $w54_demo_src ) );
if ( $w54_launch ) {
	$w54_out = array();
	exec( escapeshellarg( TP_PHP ) . ' ' . escapeshellarg( TP_WP_CLI ) . ' --path=' . escapeshellarg( TP_WP_DIR ) . ' --url=' . escapeshellarg( TP_BASE_URL ) . ' eval-file ' . escapeshellarg( $w54_root . '/scripts/tp-ads-demo.php' ) . ' launch 2>&1', $w54_out, $w54_code );
	t( 'بند ۱۰ گیت --launch: هیچ tp_ad نمایشی در پایگاه‌داده (۲ = افزونه فعال نیست؛ assert-51 همان را از _tp_demo می‌گیرد)', in_array( (int) $w54_code, array( 0, 2 ), true ), implode( ' ', array_slice( $w54_out, -2 ) ) );
}

/* ── بند ۱۱ — پیشخوان ──────────────────────────────────────────────── */
$w54_adm = function ( $s ) {
	return false !== strpos( $s, "check_admin_referer( 'tp_ads_csv' )" ) && substr_count( $s, 'current_user_can( TOPERSIA_ADS_CAP )' ) >= 3
		&& false !== strpos( $s, "current_user_can( 'edit_post', \$id )" )
		&& false !== strpos( $s, "preg_match( '/^[=+\\-@\\t\\r]/', \$v ) ? \"'\" . \$v : \$v" );
};
tp441_mut( 'بند ۱۱: پیشخوان — توان در گزارش، CSV و کار گروهی؛ nonce در CSV؛ CSV با گارد فرمول — جهش: بی check_admin_referer', $w54_adm, $w54_admin,
	str_replace( "check_admin_referer( 'tp_ads_csv' )", '1', $w54_admin ) );
t( 'بند ۱۱: گزارش — نمایش، کلیک، CTR، «پایان نزدیک» ۷ روز، جایگاه‌های خالی، CSV، توقف و تمدید ۷ و ۳۰',
	false !== strpos( $w54_admin, 'topersia_ads_ctr(' ) && false !== strpos( $w54_admin, 'topersia_ads_empty_slots(' ) && false !== strpos( $w54_admin, "'tp_ads_extend7'" )
	&& false !== strpos( $w54_admin, "'tp_ads_extend30'" ) && false !== strpos( $w54_admin, "'tp_ads_pause'" ) && false !== strpos( $w54_admin, '$now + 7 * DAY_IN_SECONDS' ) );

/* ── بند ۱۲ — صفحۀ فروش ────────────────────────────────────────────── */
$w54_adv = function ( $s ) {
	return 2 === substr_count( $s, "if ( topersia_ads_is_advertise() && ! get_option( 'tp_ads_advertise_index' ) )" ) && false !== strpos( $s, "'rank_math/frontend/robots'" )
		&& false !== strpos( $s, "esc_html__( 'تماس بگیرید.', 'topersia-ads' )" ) && ! preg_match( '/تومان|ریال|دلار|\bUSD\b|€/u', $s );
};
tp441_mut( 'بند ۱۲: /advertise/ noindex تا تأیید کاربر (وردپرس و Rank Math)؛ قیمت «تماس بگیرید»، هیچ قیمت — جهش: قیمت عددی', $w54_adv, $w54_pages,
	str_replace( "esc_html__( 'تماس بگیرید.', 'topersia-ads' )", "esc_html( '۵۰۰ هزار تومان' )", $w54_pages ) );
$w54_real = function ( $s ) {
	return 3 === preg_match_all( "/if \( \\\$fig\['\w+'\] > 0 \)/", $s ) && false !== strpos( $s, 'found_posts' ) && false !== strpos( $s, 'topersia_ads_report(' );
};
tp441_mut( 'بند ۱۲: عدد فقط واقعی — هر شمار فقط اگر بزرگ‌تر از صفر، از خود سایت — جهش: شمار صفر هم نشان داده می‌شود', $w54_real, $w54_pages,
	str_replace( "if ( \$fig['imp'] > 0 )", 'if ( true )', $w54_pages ) );

/* ── بند ۱۳ — حریم خصوصی ───────────────────────────────────────────── */
t( 'بند ۱۳: بند «تبلیغات اختصاصی؛ آمار بی‌نام» در هر سه زبان پیش‌نویس، با یادداشت مشاور حقوقی و Q15',
	false !== strpos( $w54_priv, '**تبلیغات اختصاصی؛ آمار بی‌نام:**' ) && false !== strpos( $w54_priv, '**Our own ads; anonymous statistics:**' )
	&& false !== strpos( $w54_priv, '**إعلاناتنا الخاصة؛ إحصاءات مجهولة الهوية:**' ) && 3 === substr_count( $w54_priv, '(P5-D622): ' ) && false !== strpos( $w54_priv, '| Q15 |' ) );

/* ── کش قطعۀ نتایج و پنجرۀ چرخش ────────────────────────────────────── */
preg_match( "/define\( 'TOPERSIA_FRAG_AD_WINDOW', (\d+) \)/", $w54_fkey, $w54_fw );
t( 'بند ۶ · ۱۶: پنجرۀ قطعۀ کش‌شده همان TOPERSIA_ADS_WINDOW (دو ثابت، یک عدد)', isset( $w54_fw[1] ) && (int) $w54_fw[1] === TOPERSIA_ADS_WINDOW );
$w54_frg = function ( $s ) use ( $w54_q ) {
	return false !== strpos( $s, 'data-tp-adw="(\d+)"' )
		&& (bool) preg_match( '/' . $w54_q( '!== (int) floor( time() / TOPERSIA_FRAG_AD_WINDOW ) ) {' ) . '\s*' . $w54_q( '$tp_miss();' ) . '/', $s );
};
tp441_mut( 'بند ۶: fragment.php قطعۀ پنجرۀ دیگر را «نیست» می‌دهد — چرخش در کش یخ نمی‌زند — جهش: بی سنجش پنجره', $w54_frg, $w54_frag,
	str_replace( '!== (int) floor( time() / TOPERSIA_FRAG_AD_WINDOW )', '!== (int) $tp_m[1]', $w54_frag ) );
t( 'بند ۶: هر ظرف جایگاه و پاورقی پنجره را با خود دارد (data-tp-adw)',
	2 === substr_count( $w54_render, 'data-tp-adw="\' . (int) topersia_ads_window( time() ) . \'"' ) );

/* ── قاعدۀ سه‌زبانه ─────────────────────────────────────────────────── */
$w54_i18n = escapeshellarg( $w54_root . '/scripts/tp-ads-i18n.php' ) . ' --check';
t( 'قاعدۀ سه‌زبانه: کاتالوگ topersia-ads (en_US · ar) کامل، جانشین‌ها همان، عربی بی حرف ویژۀ فارسی، خروجی با جدول یکی', 0 === tp_run_php( $w54_i18n ) );
$w54_prev = getenv( 'TP_ADS_I18N_TABLE' );
$w54_tmp  = sys_get_temp_dir() . '/tp-ads-i18n-mut-' . getmypid() . '.php';
file_put_contents( $w54_tmp, str_replace( "'ar' => 'إعلان' )", "'ar' => 'یک آگهی' )", $w54_read( 'scripts/i18n/topersia-ads.php' ) ) );
putenv( 'TP_ADS_I18N_TABLE=' . $w54_tmp );
$w54_mut_code = tp_run_php( $w54_i18n );
putenv( false === $w54_prev ? 'TP_ADS_I18N_TABLE' : 'TP_ADS_I18N_TABLE=' . $w54_prev );
@unlink( $w54_tmp );
t( 'قاعدۀ سه‌زبانه — جهش: «ی» و «ک» فارسی در ترجمۀ عربی را --check می‌گیرد', 0 !== $w54_mut_code );
