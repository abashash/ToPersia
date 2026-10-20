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
