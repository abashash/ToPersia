<?php
/**
 * ادعاهای پنل چپ (P5-D347 · P5-D348) — از assert.php پس از assert-2h.php بار می‌شود؛ PHP خالص، هر کدام با جهش.
 * رفتار در مرورگر (ترتیب دیداری دسکتاپ و موبایل، بی اسکرول افقی، همهٔ ۴۲ نوع): tests/panel-order-2i.js.
 */

$panel2i = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-panel.php' );
$css2i   = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-template.css' );
$zones2i = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );

echo "\nدستهٔ ۱ (۱۳ اکتبر) — پنل چپ: ثبت‌نامهٔ واحد، ترتیب دسکتاپ و موبایل، اطلاعات جغرافیایی  P5-D347 · P5-D348\n" . str_repeat( '─', 78 ) . "\n";

/* ── ترتیب از ثبت‌نامه ─────────────────────────────────────────────── */
$seq = function ( array $reg, $key ) {
	$rows = array();
	foreach ( $reg as $i => $row ) {
		if ( null === $row[ $key ] ) { continue; }
		$rows[] = array( $row[ $key ], $i, $row['id'] );
	}
	usort( $rows, function ( $a, $b ) { return $a[0] <=> $b[0] ?: $a[1] <=> $b[1]; } );
	return array_map( function ( $r ) { return $r[2]; }, $rows );
};
$reg2i = topersia_g_panel_registry();
$bad2i = $reg2i;
foreach ( $bad2i as $i => $row ) {
	if ( 'tp-season' === $row['id'] ) { $bad2i[ $i ]['d'] = 7; }
	if ( 'tp-weather' === $row['id'] ) { $bad2i[ $i ]['d'] = 6; }
}

tp441_mut( 'پنل چپ، دسکتاپ: دکمه‌ها ← ToPersia AI ← نقشه ← مسیریابی ← شهرهای مسیر ← بهترین زمان سفر ← هوای این روزها ← کیفیت هوا ← اطلاعات جغرافیایی ← کاشی‌های هم‌جوار ← روایت ← منابع',
	function ( $x ) use ( $seq ) {
		return array( 'tp-actions', 'tp-ai', 'tp-map', 'tp-routing', 'tp-route-cities', 'tp-season', 'tp-weather', 'tp-aqi', 'tp-geo', TOPERSIA_G_PANEL_TILES, 'tp-audio', 'tp-sources' ) === $seq( $x, 'd' );
	},
	$reg2i, $bad2i );

$bad2i = $reg2i;
foreach ( $bad2i as $i => $row ) {
	if ( 'tp-audio' === $row['id'] ) { $bad2i[ $i ]['m'] = 30; }
}

tp441_mut( 'پنل چپ، موبایل: AI بالا (جای پیشین)؛ نقشه ← مسیریابی ← شهرهای مسیر ← هوای این روزها ← کیفیت هوا ← بهترین زمان سفر ← اطلاعات جغرافیایی ← کاشی‌های هم‌جوار ← روایت ← منابع',
	function ( $x ) use ( $seq ) {
		return array( 'tp-ai', 'tp-map', 'tp-routing', 'tp-route-cities', 'tp-weather', 'tp-aqi', 'tp-season', 'tp-geo', TOPERSIA_G_PANEL_TILES, 'tp-audio', 'tp-sources' ) === $seq( $x, 'm' );
	},
	$reg2i, $bad2i );

tp441_mut( 'ترتیب DOM پنل چپ = ترتیب موبایل: هیچ باکسی در DOM پس از باکسی با order موبایل بزرگ‌تر نمی‌آید',
	function ( $x ) {
		$last = -1;
		foreach ( $x as $row ) {
			if ( null === $row['m'] ) { continue; }
			if ( $row['m'] < $last ) { return false; }
			$last = $row['m'];
		}
		return true;
	},
	$reg2i, array_merge( array_slice( $reg2i, 3 ), array_slice( $reg2i, 0, 3 ) ) );

$main2i = topersia_g_panel_main_mobile();
tp441_mut( 'موبایل: ستون راست کامل پیش از پنل چپ (پرسش‌ها پیش از نقشه)، و «نظر بازدیدکنندگان» پس از منابع آخرین است',
	function ( $x ) use ( $reg2i ) {
		$m = array();
		foreach ( $reg2i as $r ) { if ( null !== $r['m'] && 'tp-ai' !== $r['id'] ) { $m[ $r['id'] ] = $r['m']; } }
		return $x['tp-faq'] < $m['tp-map'] && $x['tp-reviews'] > $m['tp-sources'] && $x['tp-news'] < $x['tp-faq'];
	},
	$main2i, array_merge( $main2i, array( 'tp-reviews' => 70 ) ) );

/* ── شیوه‌نامهٔ مولد از همان ثبت‌نامه ──────────────────────────────── */
$gen2i = function ( $css ) use ( $reg2i ) {
	if ( ! preg_match( '/Left panel order from the single registry.*?@media\(max-width:720px\)\{(.*?)\n\}\n@media\(min-width:721px\)\{(.*?)\n\}\n/su', $css, $m ) ) {
		return false;
	}
	foreach ( $reg2i as $r ) {
		if ( TOPERSIA_G_PANEL_TILES === $r['id'] ) { continue; }
		if ( null !== $r['m'] && false === strpos( $m[1], '#' . $r['id'] . '{order:' . $r['m'] . '}' ) ) { return false; }
		if ( false === strpos( $m[2], '#' . $r['id'] . '{order:' . $r['d'] . '}' ) ) { return false; }
	}
	return false !== strpos( $m[2], '.tp-rail>*{order:11}' ) && false !== strpos( $m[1], '#tp-reviews{order:99}' ) && false !== strpos( $m[1], '#tp-faq{order:10}' )
		&& false !== strpos( $m[1], '.tp-main-col>*{order:12}' );
};
tp441_mut( 'مولد قالب: بلوک order موبایل و دسکتاپ هر ردیف ثبت‌نامه را دارد (فقط CSS order؛ DOM تکراری نیست) و وزنش از بلوک order مرجع بیشتر است',
	function ( $x ) use ( $gen2i ) { return $gen2i( $x[0] ) && false !== strpos( $x[0], 'html .tp-page.tp-page #tp-ai{order:' ); },
	array( $css2i ), array( str_replace( '#tp-weather{order:7}', '#tp-weather{order:5}', $css2i ) ) );

/* ── چیدمان صفحه ────────────────────────────────────────────────────── */
$lay2i = topersia_g_layout( 20 );
tp441_mut( 'ToPersia AI در پنل چپ، بلافاصله پس از دکمه‌ها (نه در ستون راست)؛ باکس «مرکزیت» در هیچ ستونی نیست',
	function ( $x ) {
		$rail = array_map( function ( $z ) { return $z['id']; }, $x['rail'] );
		$main = array_map( function ( $z ) { return $z['id']; }, $x['main'] );
		return array( '', 'tp-ai' ) === array_slice( $rail, 0, 2 ) && ! in_array( 'tp-ai', $main, true ) && ! in_array( 'tp-capital-of', array_merge( $rail, $main ), true );
	},
	$lay2i, array( 'main' => array( array( 'id' => 'tp-ai' ) ), 'rail' => array( array( 'id' => '' ), array( 'id' => 'tp-map' ), array( 'id' => 'tp-capital-of' ) ) ) );

/* ── مکان‌های مرتبط برای نوع غیرمکانی ──────────────────────────────── */
$rel2i = $f2_reg;
$rel2i[7101] = array( 'id' => 7101, 'name' => 'province_festival', 'type' => 'many_to_many', 'parent' => 'tp_province', 'child' => 'tp_festival', 'parent_label' => 'جشن', 'child_label' => 'استان رایج' );
$rel2i[7102] = array( 'id' => 7102, 'name' => 'city_festival', 'type' => 'many_to_many', 'parent' => 'tp_city', 'child' => 'tp_festival', 'parent_label' => 'جشن', 'child_label' => 'شهر برگزاری' );
$f2_world( $rel2i );
$f2_post( 8400, 'tp_festival', 'جشن آزمون' );
$f2_post( 8401, 'tp_festival', 'جشن بی‌مکان' );
$GLOBALS['tp_rel']['7101'] = array( 'children' => array( 8002 => array( 8400 ) ), 'parents' => array( 8400 => array( 8002 ) ) );
$GLOBALS['tp_rel']['7102'] = array( 'children' => array( 8004 => array( 8400 ) ), 'parents' => array( 8400 => array( 8004 ) ) );

$tiles2i = function ( $pid ) {
	return array_map( function ( $t ) { return $t['label'] . ':' . $t['name']; }, topersia_g_geo_related_tiles( $pid ) );
};
$page2i = r( 8400, 'tp_page' );
$none2i = r( 8401, 'tp_page' );

tp441_mut( 'نوع غیرمکانی (جشن): کاشی مکان‌های مرتبط از رابطه‌های موجود — استان، سپس شهر، با پیوند — در باکس «اطلاعات جغرافیایی»؛ بی رابطه ← بی کاشی و بی باکس؛ نوع مکانی (شهر) این مسیر را نمی‌گیرد',
	function ( $x ) {
		return array( 'استان:اصفهان', 'شهر:شهر آزمون' ) === $x[0] && array() === $x[1] && array() === $x[2]
			&& false !== strpos( $x[3], 'id="tp-geo"' ) && false !== strpos( $x[3], 'href="https://topersia.com/p8002/"' ) && false === strpos( $x[4], 'id="tp-geo"' );
	},
	array( $tiles2i( 8400 ), $tiles2i( 8401 ), $tiles2i( 8004 ), $page2i, $none2i ),
	array( array( 'شهر:شهر آزمون', 'استان:اصفهان' ), array( 'استان:اصفهان' ), array( 'شهر:x' ), str_replace( 'id="tp-geo"', 'id="x"', $page2i ), $page2i ) );

/* ── مرکزیت فقط درون باکس جغرافیایی ─────────────────────────────────── */
tp441_mut( 'مرکزیت باکس جدا نیست: «capital-of» در جدول ناحیه‌ها نیست؛ کاشی «مرکز استان» درون «اطلاعات جغرافیایی»',
	function ( $x ) {
		return false === strpos( $x[0], "'capital-of' => array( 'rail'" ) && false !== strpos( $x[1], 'topersia_g_geo_center_tiles' );
	},
	array( $zones2i, $zones2i ), array( $zones2i . "\n'capital-of' => array( 'rail', 'topersia_g_sc_relgrid', 'tp-capital-of', 'مرکزیت' )", str_replace( 'topersia_g_geo_center_tiles', 'x', $zones2i ) ) );

/* برگرداندن دنیای آزمون برای فایل‌های بعدی */
$GLOBALS['tp_posts'] = $f2_keep_posts;
$GLOBALS['tp_rel']   = $f2_keep_rel;
$GLOBALS['tp_meta']  = $f2_keep_meta;
if ( null === $f2_keep_flt ) { unset( $GLOBALS['tp_filters']['topersia_g_relreg'] ); } else { $GLOBALS['tp_filters']['topersia_g_relreg'] = $f2_keep_flt; }
