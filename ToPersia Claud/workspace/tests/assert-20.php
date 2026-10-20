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
tp441_mut( 'نشان فقط مختصات: استان ← مختصات مرکز استان (رابطهٔ ۹۶)؛ شهر ← مختصات شهر؛ پهنهٔ بی نقطهٔ نماینده ← بی نشان؛ Google و Waze برای پهنه همان متن  P5-D383',
	function ( $x ) {
		return isset( $x[0]['neshan'] ) && 'https://nshn.ir/?lat=32.6539&lng=51.666' === $x[0]['neshan'] && ! isset( $x[0]['google'] ) /* استانِ بی والد R01 (جز کشور) متن ندارد ← Google و Waze مثل P5-D350 نیستند */
			&& ! isset( $x[1]['neshan'] ) && isset( $x[1]['google'], $x[1]['waze'] )
			&& 'https://nshn.ir/?lat=32.6539&lng=51.666' === $x[2]['neshan'];
	},
	array( $l20_prov, $l20_area, $l20_city ), array( array_diff_key( $l20_prov, array( 'neshan' => 1 ) ), $l20_area + array( 'neshan' => 'https://nshn.ir/?lat=32&lng=52' ), $l20_city ) );
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
