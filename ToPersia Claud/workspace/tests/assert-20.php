<?php
/**
 * ادعاهای ایستای دستهٔ ۲۰ — ایرادهای صفحهٔ موجودیت (P5-D380 … P5-D390). از assert.php بار می‌شود؛
 * PHP خالص، بی وردپرس، هر ادعا با جهش (tp441_mut یا جهش صریح).
 *
 *   P5-D382  بلوک AI برای همه + بی موتور (assert.php) · تولید خودکار در صف (مجموعهٔ خلاصه‌ساز test-14، از assert-440)
 *   P5-D386  کد استان از هر نمایش (assert.php)
 *   P5-D388  تصویر خبر: hero_image ← تصویر شاخص ← آیکون
 */

echo "\nدستهٔ ۲۰ — ایرادهای صفحهٔ موجودیت  P5-D380…P5-D390\n" . str_repeat( '─', 78 ) . "\n";

/* ══ P5-D388 — تصویر خبر ══════════════════════════════════════════════ */

$GLOBALS['tp_posts'][90] = array( 'type' => 'tpc_news', 'title' => 'خبر آزمون', 'url' => 'https://topersia.com/news/test/' );
$GLOBALS['tp_meta'][90]  = array( 'hero_image' => '501', '_thumbnail_id' => '502', 'short_summary' => 'خلاصهٔ خبر.' );
$n20_hero  = topersia_g_news_card( 90 );
unset( $GLOBALS['tp_meta'][90]['hero_image'] );
$n20_thumb = topersia_g_news_card( 90 );
unset( $GLOBALS['tp_meta'][90]['_thumbnail_id'] );
$n20_none  = topersia_g_news_card( 90 );
$GLOBALS['tp_meta'][90]['hero_image']    = '501';
$GLOBALS['tp_meta'][90]['_thumbnail_id'] = '502';
/* رفتار پیشین (فقط تصویر شاخص) — جهش */
$n20_old = (string) get_the_post_thumbnail( 90, 'medium', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 720px) 88px, 112px' ) );
$n20_claim = function ( $hero_html ) use ( $n20_thumb, $n20_none ) {
	return false !== strpos( $hero_html, 'uploads/501.jpg' ) && false === strpos( $hero_html, 'uploads/502.jpg' )
		&& false !== strpos( $hero_html, 'sizes="(max-width: 720px) 88px, 112px"' ) && false !== strpos( $hero_html, 'loading="lazy"' ) && 1 === preg_match( '/<img width="\d+" height="\d+"/', $hero_html )
		&& false !== strpos( $n20_thumb, 'uploads/502.jpg' )
		&& false === strpos( $n20_none, '<img' ) && false !== strpos( $n20_none, '<svg viewBox="0 0 24 24"' );
};
tp441_mut( 'خبر: تصویر از hero_image خودِ خبر، نبود ← تصویر شاخص، نبود ← آیکون؛ همان اندازه‌ها (sizes، lazy، ابعاد صریح)  P5-D388',
	$n20_claim, $n20_hero, $n20_old );
unset( $GLOBALS['tp_posts'][90], $GLOBALS['tp_meta'][90] );

/* ══ P5-D385 — مسیر گردشگری: «استان‌های مسیر» در «اطلاعات جغرافیایی»، نه شهرهای ایستگاه ══════ */

$r20_keep = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], $GLOBALS['tp_rel'], isset( $GLOBALS['tp_filters']['topersia_g_relreg'] ) ? $GLOBALS['tp_filters']['topersia_g_relreg'] : null, isset( $GLOBALS['tp442_stops'] ) ? $GLOBALS['tp442_stops'] : null );
$f2_world( $f2_reg );
$f2_post( 8300, 'tp_route', 'مسیر آزمون' );
$f2_post( 8302, 'tp_city', 'شهر دوم' );
$f2_post( 8303, 'tp_city', 'شهر چهارم' );
$f2_post( 8304, 'tp_county', 'شهرستان همجوار' );
$GLOBALS['tp_rel']['7002']['children'][8007] = array( 8304 );
$GLOBALS['tp_rel']['7002']['parents'][8304]  = array( 8007 );
$GLOBALS['tp_rel']['7003']['children'][8304] = array( 8303 );
$GLOBALS['tp_rel']['7003']['parents'][8303]  = array( 8304 );
$GLOBALS['tp442_stops'] = array(
	array( 'position' => 1, 'id' => 8004, 'title' => 'شهر آزمون', 'url' => '', 'note' => '' ),
	array( 'position' => 2, 'id' => 8303, 'title' => 'شهر چهارم', 'url' => '', 'note' => '' ),
	array( 'position' => 3, 'id' => 8302, 'title' => 'شهر دوم', 'url' => '', 'note' => '' ),
	array( 'position' => 4, 'id' => 8004, 'title' => 'شهر آزمون', 'url' => '', 'note' => '' ),
);
$r20_geo  = $f2_geo( 8300 );
$r20_box  = r( 8300, 'tp_route_cities' );
$r20_old  = '<section id="tp-geo"><a href="https://topersia.com/p8004/" class="tp-tile"><span class="tp-tile__k">شهر</span><b>شهر آزمون</b></a></section>';
tp441_mut( 'مسیر گردشگری: «اطلاعات جغرافیایی» = «استان‌های مسیر» (والد R01 ایستگاه‌ها، یکتا، به ترتیب مسیر)، بی نام شهر ایستگاه؛ شهرها فقط در «شهرهای مسیر»  P5-D385',
	function ( $x ) use ( $f2_tile_names, $r20_box ) {
		return array( 'استان مسیر:اصفهان:p8002', 'استان مسیر:استان همجوار:p8007' ) === $f2_tile_names( $x )
			&& false === strpos( $x, 'شهر آزمون' ) && false === strpos( $x, 'شهر چهارم' ) && false === strpos( $x, 'شهر دوم' )
			&& false !== strpos( $r20_box, '<b>شهر آزمون</b>' ) && false !== strpos( $r20_box, '<b>شهر چهارم</b>' );
	},
	$r20_geo, $r20_old, implode( ' | ', $f2_tile_names( $r20_geo ) ) );
list( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], $GLOBALS['tp_rel'] ) = $r20_keep;
if ( null === $r20_keep[3] ) { unset( $GLOBALS['tp_filters']['topersia_g_relreg'] ); } else { $GLOBALS['tp_filters']['topersia_g_relreg'] = $r20_keep[3]; }
if ( null === $r20_keep[4] ) { unset( $GLOBALS['tp442_stops'] ); } else { $GLOBALS['tp442_stops'] = $r20_keep[4]; }

/* ══ P5-D383 — نشان فقط مختصات: نقطه‌ای ← خودش؛ استان ← مرکز استان؛ پهنهٔ دیگر ← نماینده یا هیچ ══════ */

$s20_keep = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], $GLOBALS['tp_rel'], isset( $GLOBALS['tp_filters']['topersia_g_relreg'] ) ? $GLOBALS['tp_filters']['topersia_g_relreg'] : null );
$f2_world( $f2_reg );
$GLOBALS['tp_meta'][8004]['geo_coordinates'] = '32.6539, 51.6660';   /* شهر آزمون — مرکز استان 8002 با رابطهٔ ۹۶ */
$GLOBALS['tp_meta'][8002]['geo_coordinates'] = '';                   /* استان: نقطهٔ خودش هیچ */
$GLOBALS['tp_meta'][8005]['geo_coordinates'] = '';                   /* پهنهٔ آبی بی نقطه */
$nav20 = array( 'enabled' => true, 'extra' => array(
	'google' => array( 'on' => true, 'pattern' => 'https://www.google.com/maps/dir/?api=1&destination={lat},{lng}', 'text' => 'https://www.google.com/maps/dir/?api=1&destination={q}' ),
	'waze'   => array( 'on' => true, 'pattern' => 'https://waze.com/ul?ll={lat},{lng}&navigate=yes', 'text' => 'https://waze.com/ul?q={q}&navigate=yes' ),
	'neshan' => array( 'on' => true, 'pattern' => 'https://nshn.ir/?lat={lat}&lng={lng}', 'text' => '' ),
) );
$l20_prov = topersia_g_routing_links( $nav20['extra'], topersia_g_routing_target( 8002 ) );
$l20_area = topersia_g_routing_links( $nav20['extra'], topersia_g_routing_target( 8005 ) );
$l20_city = topersia_g_routing_links( $nav20['extra'], topersia_g_routing_target( 8004 ) );
tp441_mut( 'نشان فقط مختصات: استان ← هیچ (P5-D455)؛ شهر ← مختصات شهر؛ پهنهٔ بی نقطهٔ نماینده ← بی نشان؛ Google و Waze برای پهنه همان متن  P5-D383',
	function ( $x ) {
		/* P5-D455 (دستهٔ ۳۱ الف): استان مسیریابی ندارد — هیچ دکمه‌ای، نه گوگل، نه ویز، نه نشان */
		return array() === $x[0]
			&& ! isset( $x[1]['neshan'] ) && isset( $x[1]['google'], $x[1]['waze'] )
			&& 'https://nshn.ir/?lat=32.6539&lng=51.666' === $x[2]['neshan'];
	},
	array( $l20_prov, $l20_area, $l20_city ), array( $l20_city, $l20_area + array( 'neshan' => 'https://nshn.ir/?lat=32&lng=52' ), $l20_city ) );
list( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], $GLOBALS['tp_rel'] ) = $s20_keep;
if ( null === $s20_keep[3] ) { unset( $GLOBALS['tp_filters']['topersia_g_relreg'] ); } else { $GLOBALS['tp_filters']['topersia_g_relreg'] = $s20_keep[3]; }

/* ══ P5-D384 — «همه …» برای هر اسلایدر کارتی، هر شماری ══════════════════════════════════ */
$a20_page = r( 20, 'tp_page' );
$a20_rail = r( 20, 'tp_relgrid', array( 'rel' => 'villages' ) );
tp441_mut( 'اسلایدر کارتی سه‌عضوی (جاذبه‌ها) دکمهٔ «همه …» و پنجرهٔ فهرست با جست‌وجو دارد؛ کاشی ستون کناری بی تغییر (شش یا کمتر ← بی پیوند)  P5-D384',
	function ( $x ) {
		return 3 === topersia_g_rel_items( 20, 'attractions', 0 )['total']
			&& false !== strpos( $x[0], 'href="#tp-list-attractions" role="button" aria-haspopup="dialog" aria-controls="tp-list-attractions" data-tp-list-open' )
			&& 1 === preg_match( '#id="tp-list-attractions"[^>]*>.*?data-tp-list-q#su', $x[0] )
			&& false === strpos( $x[1], 'data-tp-list-open' );
	},
	array( $a20_page, $a20_rail ), array( str_replace( 'aria-controls="tp-list-attractions" data-tp-list-open', 'x', $a20_page ), $a20_rail ) );

/* ══ P5-D392 — هیچ پیوند دستیار بیرونی در خروجی عمومی هیچ افزونه‌ای ══════════════════════ */
$x20_hosts = '~https?://(?:www\.)?(?:chatgpt\.com|chat\.openai\.com|claude\.ai/new|gemini\.google\.com/app|copilot\.microsoft\.com|perplexity\.ai/search|grok\.com|chat\.deepseek\.com)~';
$x20_scan  = function ( $extra = '' ) use ( $x20_hosts ) {
	$hit = array();
	$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( dirname( TP_PLUGIN_DIR ), FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		$p = str_replace( chr( 92 ), '/', (string) $file );
		if ( ! preg_match( '/\.(php|js)$/', $p ) || preg_match( '~/(tests|vendor|node_modules|languages)/~', $p ) ) { continue; }
		if ( preg_match( $x20_hosts, (string) file_get_contents( $p ) ) ) { $hit[] = basename( $p ); }
	}
	if ( '' !== $extra && preg_match( $x20_hosts, $extra ) ) { $hit[] = 'mutant'; }
	return $hit;
};
tp441_mut( 'هیچ پیوند دستیار بیرونی (ChatGPT · Claude · Gemini · Copilot · Perplexity · Grok · DeepSeek) در کد هیچ افزونهٔ ToPersia (render · user-core · خلاصه‌ساز · core · schema)؛ فهرست دستیار خلاصه‌ساز خالی و بی صافی  P5-D392',
	function ( $hits ) { return array() === $hits && array() === TP_AI_Services_get_all_safe(); },
	$x20_scan(), $x20_scan( '<a href="https://chatgpt.com/?q=x">' ) );
function TP_AI_Services_get_all_safe() {
	$f = dirname( TP_PLUGIN_DIR ) . '/topersia-ai-summarizer/includes/class-tp-ai-services.php';
	$s = is_file( $f ) ? (string) file_get_contents( $f ) : '';
	return ( false !== strpos( $s, "public static function get_all() {" ) && 1 === preg_match( '/public static function get_all\(\) \{.*?return array\(\);\s*\}/s', $s ) && false === strpos( $s, "apply_filters( 'tpas_services'" ) ) ? array() : array( 'x' );
}

/* ══ P5-D334 بازبینی (map-local) — حافظهٔ «تهی» رابطه با نوشتن/پاک‌کردن رابطهٔ جت‌انجین باطل می‌شود ══════ */
topersia_g_rel_memo_reset();
topersia_g_rel_known_empty( 84, 'parents', 9999, true );
$m20_before = topersia_g_rel_known_empty( 84, 'parents', 9999 );
topersia_g_rel_memo_reset();   /* همان callback ثبت‌شده روی jet-engine/relation/update/after (stub رویداد ندارد) */
$m20_after_up = topersia_g_rel_known_empty( 84, 'parents', 9999 );
topersia_g_rel_known_empty( 84, 'parents', 9999, true );
topersia_g_rel_memo_reset();   /* همان callback ثبت‌شده روی jet-engine/relation/delete/after */
$m20_after_del = topersia_g_rel_known_empty( 84, 'parents', 9999 );
$m20_src = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );
tp441_mut( 'حافظهٔ «رابطهٔ تهی» درون درخواست با jet-engine/relation/update/after و delete/after پاک می‌شود — زنجیرهٔ مکانیِ پستِ تازه پس از نوشتن رابطه درست است (علت افتادن map-local)',
	function ( $x ) { return true === $x[0] && false === $x[1] && false === $x[2]
		&& false !== strpos( $x[3], "add_action( 'jet-engine/relation/update/after', 'topersia_g_rel_memo_reset', 1, 0 );" ) && false !== strpos( $x[3], "add_action( 'jet-engine/relation/delete/after', 'topersia_g_rel_memo_reset', 1, 0 );" ); },
	array( $m20_before, $m20_after_up, $m20_after_del, $m20_src ), array( true, true, true, str_replace( "'jet-engine/relation/update/after', 'topersia_g_rel_memo_reset'", "'x'", $m20_src ) ) );
topersia_g_rel_memo_reset();

/* ══ P5-D381 — کیفیت هوا: میانگین ۲۴ ساعت، ساعت ایران، نام نقطه، استان ← مرکز، صافی خاموشی ══════ */
$q20_t = array(); $q20_v = array();
for ( $i = 0; $i < 30; $i++ ) { $q20_t[] = gmdate( 'Y-m-d\TH:00', strtotime( '2026-10-19 00:00 UTC' ) + $i * 3600 ); $q20_v[] = $i < 6 ? 1000 : 10; }
$q20_mean = topersia_g_aq_mean24( $q20_t, $q20_v, '2026-10-20T05:00', 99 );         /* ۲۴ ساعت تا ۰۵:۰۰ = ساعت ۶…۲۹ → همه ۱۰ */
$q20_few  = topersia_g_aq_mean24( array_slice( $q20_t, 0, 5 ), array_slice( $q20_v, 0, 5 ), '2026-10-19T04:00', 41.6 );
$q20_ir   = topersia_g_aq_time_ir( '2026-10-20T05:00' );                             /* +۳:۳۰ */
$q20_keep = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'] );
$GLOBALS['tp_meta'][ topersia_g_capital_id( 20 ) ]['geo_coordinates'] = '32.6539, 51.6660';   /* مرکز استان با مختصات؛ بی آن جعبه نمی‌آید (درست) */
$q20_prov = r( 20, 'tp_air' );
$q20_want = array( topersia_geo_attr( topersia_g_text( topersia_g_capital_id( 20 ), 'geo_coordinates' ) ), topersia_g_name( topersia_g_capital_id( 20 ) ) );
$GLOBALS['tp_filters']['topersia_g_air_enabled'][] = function ( $on, $pid, $type ) { return 'tp_province' === $type ? false : $on; };
$q20_off  = r( 20, 'tp_air' );
array_pop( $GLOBALS['tp_filters']['topersia_g_air_enabled'] );
list( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'] ) = $q20_keep;
tp441_mut( 'کیفیت هوا: میانگین ۲۴ مقدار ساعتیِ تا ساعت جاری، بی اعشار (کمتر از ۱۲ ساعت ← مقدار لحظه‌ای)؛ ساعت به وقت ایران؛ استان ← مختصات و نام مرکز استان («برای شهر …»)؛ صافی topersia_g_air_enabled جعبه را خاموش می‌کند  P5-D381',
	function ( $x ) {
		return 10 === $x[0] && 42 === $x[1] && '۰۸:۳۰' === $x[2]
			&& false !== strpos( $x[3], 'data-tp-coords="' . $x[5][0] . '"' )
			/* «برای شهر …»؛ نامی که خودش «شهر» دارد دوباره پیشوند نمی‌گیرد */
			&& false !== strpos( $x[3], '<span data-tp-air-for>برای ' . ( 0 === strpos( $x[5][1], 'شهر' ) ? '' : 'شهر ' ) . $x[5][1] . '</span>' ) && false === strpos( $x[3], 'شهر شهر' )
			&& '' === $x[4];
	},
	array( $q20_mean, $q20_few, $q20_ir, $q20_prov, $q20_off, $q20_want ), array( 207, 42, '۰۵:۰۰', $q20_prov, $q20_prov, $q20_want ) );
