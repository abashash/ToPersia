<?php
/**
 * ادعاهای فاز ۲ه SPRINT-4.4 (درخواست‌های ۶ اکتبر) — از assert.php پس از assert-2c.php بار
 * می‌شود؛ PHP خالص با stub. هر کدام با جهش (tp441_mut).
 *
 *   ۲ه٫۶  نام نوع یک بار: eyebrow نوع، H1 فقط نام (P5-D315)
 */

$zones2e = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );
$css2e   = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-template.css' );

echo "\nفاز ۲ه — ۲ه٫۶ نام نوع یک بار  P5-D315\n" . str_repeat( '─', 78 ) . "\n";

$hero2e = function ( $name_fa ) {
	$keep = $GLOBALS['tp_meta'][20]['name_fa'] ?? null;
	$GLOBALS['tp_meta'][20]['name_fa'] = $name_fa;
	$h = r( 20, 'tp_hero' );
	$GLOBALS['tp_meta'][20]['name_fa'] = $keep;
	preg_match( '~<p class="tp-eyebrow">(.*?)</p>~u', $h, $e );
	preg_match( '~<h1>(.*?)</h1>~u', $h, $t );
	return array( 'eyebrow' => $e[1] ?? '', 'h1' => $t[1] ?? '' );
};
$short2e = $hero2e( 'اصفهان' );
tp441_mut( 'سربرگ استان با نام کوتاه: eyebrow «استان»، H1 فقط «اصفهان» — نام نوع یک بار (جهش: پیشوند نوع topersia_g_title)',
	function ( $x ) { return 'استان' === $x['eyebrow'] && 'اصفهان' === $x['h1'] && false === mb_strpos( $x['h1'], $x['eyebrow'] ); },
	$short2e, array( 'eyebrow' => 'استان', 'h1' => 'استان اصفهان' ) );

$data2e = $hero2e( 'استان اصفهان' );
t( 'نام نوعی که جزو خود داده است بریده نمی‌شود («استان اصفهان» در name_fa) — فهرست در گزارش، تصمیم با کاربر',
	'استان اصفهان' === $data2e['h1'] );

tp441_mut( 'H1 سربرگ از topersia_g_heading (فقط نام)، نه topersia_g_title؛ topersia_g_title برای سرفصل، خبر، breadcrumb فهرست خبر و alt دست‌نخورده',
	function ( $s ) {
		$h = tp2c_body( $s, 'topersia_g_sc_hero' );
		return false !== strpos( $h, '$title = topersia_g_heading( $pid );' ) && false === strpos( $h, '$title = topersia_g_title( $pid );' )
			&& false !== strpos( tp2c_body( $s, 'topersia_g_title' ), "return \$kind . ' ' . \$name;" );
	},
	$zones2e, str_replace( '$title = topersia_g_heading( $pid );', '$title = topersia_g_title( $pid );', $zones2e ) );

tp441_mut( 'eyebrow حدود ۱۲٪ بزرگ‌تر از مرجع (.74 ← .83 rem، موبایل به همان نسبت) و در fa و ar بی فاصلۀ حروف؛ en همان .14em مرجع',
	function ( $c ) {
		return false !== strpos( $c, 'html body .tp-page .tp-eyebrow{font-size:calc(.83 * var(--tp-rem))}' )
			&& false !== strpos( $c, "html .tp-page .tp-eyebrow:lang(fa),\nhtml .tp-page .tp-eyebrow:lang(ar){letter-spacing:0}" )
			&& false !== strpos( $c, 'letter-spacing:.14em;text-transform:uppercase' );
	},
	$css2e, str_replace( 'calc(.83 * var(--tp-rem))', 'calc(.74 * var(--tp-rem))', $css2e ) );

/* ══ ۲ه٫۱ — نوار بخش‌ها نرم (P5-D310) ═══════════════════════════════════ */

echo "\nفاز ۲ه — ۲ه٫۱ نوار بخش‌ها نرم  P5-D310\n" . str_repeat( '─', 78 ) . "\n";

$js2e = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-zones.js' );

tp441_mut( 'کلیک نوار بخش‌ها: goTo() هدف را با هدر چسبان و نوار حساب می‌کند و تا پایان اسکرول دوباره نشانه می‌گیرد؛ scrollIntoView و لنگر بومی دیگر نیست (جهش: بازگشت به scrollIntoView)',
	function ( $s ) {
		return false !== strpos( $s, 'function goTo( el ) {' ) && false !== strpos( $s, "window.scrollTo( { top: y, behavior: calm() ? 'auto' : 'smooth' } );" )
			&& false !== strpos( $s, 'Math.abs( y - aim ) > 1' ) && false !== strpos( $s, "\t\t\t\tgoTo( el );\n" )
			&& false === strpos( $s, "el.scrollIntoView( { behavior: calm() ? 'auto' : 'smooth', block: 'start' } );" );
	},
	$js2e, str_replace( "\t\t\t\tgoTo( el );\n", "\t\t\t\tel.scrollIntoView( { behavior: calm() ? 'auto' : 'smooth', block: 'start' } );\n", $js2e ) );

tp441_mut( 'بخش فعال با IntersectionObserver روی خط زیر نوار، تجمیع در requestAnimationFrame؛ بی IntersectionObserver همان شنوندۀ اسکرول',
	function ( $s ) {
		return false !== strpos( $s, "io = new IntersectionObserver( later, { rootMargin: '-' + top + 'px 0px -' + bottom + 'px 0px', threshold: 0 } );" )
			&& false !== strpos( $s, 'window.requestAnimationFrame( function () { queued = false; pick(); } );' )
			&& false !== strpos( $s, "window.addEventListener( 'scroll', later, { passive: true } );" );
	},
	$js2e, str_replace( 'new IntersectionObserver( later,', 'new IntersectionObserver( function () {},', $js2e ) );

tp441_mut( 'نشانگر فعال می‌لغزد: قرص هم‌شکل با transform از قاب پیشین به تازه؛ نوار افقی با scrollTo نرم؛ هر دو با prefers-reduced-motion فوری',
	function ( $s ) {
		$b = tp2c_body( $s, 'slide' );
		return false !== strpos( $s, "ghost.style.transition = 'transform .28s cubic-bezier(.2,.7,.2,1)';" ) && false !== strpos( $b, 'if ( calm() ||' )
			&& false !== strpos( $s, "strip.scrollTo( { left: strip.scrollLeft + delta, behavior: calm() ? 'auto' : 'smooth' } );" )
			&& false !== strpos( $s, "var calmQuery = window.matchMedia ? window.matchMedia( '(prefers-reduced-motion: reduce)' ) : null;" );
	},
	$js2e, str_replace( 'if ( calm() ||', 'if (', $js2e ) );

tp441_mut( 'نوار پایین موبایل: ظاهر و پنهان‌شدن با transform و opacity (بی CLS)؛ prefers-reduced-motion ← بی گذار و html:root{scroll-behavior:auto}؛ دکمه‌های نوار هم با کاهش حرکت بی اسکرول نرم',
	function ( $x ) {
		return false !== strpos( $x[0], 'transition:transform .24s ease-out,opacity .24s ease-out,visibility 0s linear .24s}' )
			&& false !== strpos( $x[0], 'html:root{scroll-behavior:auto}' )
			&& false !== strpos( $x[1], "var how = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth';" )
			&& false !== strpos( $x[1], 'window.scrollTo( { top: 0, behavior: how } );' );
	},
	array( $css2e, $js2e ), array( str_replace( 'html:root{scroll-behavior:auto}', '', $css2e ), $js2e ) );

tp441_mut( 'goTo: هر ورودی کاربر (wheel · touchstart · keydown · mousedown) اسکرولِ در جریان را رها می‌کند و پس از رسیدن و حدود ۶۰۰ms آرامش پایان می‌گیرد — صفحه به بخش برنمی‌گردد',
	function ( $s ) {
		return false !== strpos( $s, "[ 'wheel', 'touchstart', 'keydown', 'mousedown' ].forEach( function ( type ) {" )
			&& false !== strpos( $s, "window.addEventListener( type, function () { goingTo ++; }, { passive: true } );" )
			&& false !== strpos( $s, 'if ( ( ! steadyAt || Date.now() - steadyAt <= 600 ) && Date.now() - began < 1800 ) {' );
	},
	$js2e, str_replace( 'if ( ( ! steadyAt || Date.now() - steadyAt <= 600 ) && Date.now() - began < 1800 ) {', 'if ( Date.now() - began < 1800 ) {', $js2e ) );

tp441_mut( 'pick در قفلِ پس از کلیک خودش را برای پایان قفل زمان‌بندی می‌کند — بخش فعال با IntersectionObserver کهنه نمی‌ماند',
	function ( $s ) { return false !== strpos( $s, 'lockTimer = window.setTimeout( later, lockUntil - Date.now() + 40 );' ); },
	$js2e, str_replace( 'lockTimer = window.setTimeout( later, lockUntil - Date.now() + 40 );', '', $js2e ) );

/* ══ ۲ه٫۴ — «همه …» برای هر بخش کارتی (P5-D313) ═════════════════════════ */

echo "\nفاز ۲ه — ۲ه٫۴ «همه …» برای هر بخش کارتی  P5-D313\n" . str_repeat( '─', 78 ) . "\n";

/* استان ۲۰ با N روستا — رابطۀ ۷۵ (استان←روستا) */
$v24_rel   = $GLOBALS['tp_rel']['75'];
$v24_posts = $GLOBALS['tp_posts'];
$v24_set   = function ( $n ) use ( $v24_rel ) {
	$kids = array( 25, 26 );
	for ( $i = 0; $i < $n - 2; $i++ ) {
		$id = 930 + $i;
		$GLOBALS['tp_posts'][ $id ] = array( 'type' => 'tp_village', 'title' => 'روستای آزمون ' . ( $i + 1 ), 'url' => 'https://topersia.com/v' . $id . '/' );
		$kids[] = $id;
	}
	$GLOBALS['tp_rel']['75'] = array( 'children' => array( 20 => $kids ), 'parents' => $v24_rel['parents'] );
};
$v24_page = function ( $n ) use ( $v24_set ) {
	$v24_set( $n );
	return array( 'grid' => r( 20, 'tp_relgrid', array( 'rel' => 'villages' ) ), 'page' => r( 20, 'tp_page' ) );
};
$v24_nine = $v24_page( 9 );
$v24_six  = $v24_page( 6 );
$v24_three = $v24_page( 3 );
$GLOBALS['tp_rel']['75']   = $v24_rel;
$GLOBALS['tp_posts']       = $v24_posts;

tp441_mut( 'بخش کاشی ستون کناری با نُه عضو (بیش از شش کاشی): پیوند «همه روستاها ←» پس از عنوان (data-tp-list-open، aria-controls) و پنجرۀ فهرست tp-list-villages با نُه عضو در HTML صفحه؛ شش یا کمتر: نه پیوند نه پنجره',
	function ( $x ) {
		$link = '<a class="tp-more tp-more--rail" href="#tp-list-villages" role="button" aria-haspopup="dialog" aria-controls="tp-list-villages" data-tp-list-open>همه روستاها ←</a>';
		return false !== strpos( $x[0]['grid'], $link ) && 1 === preg_match( '#<div class="tp-rail-card__head"><h3 id="tp-villagesTitle">[^<]*</h3><a class="tp-more tp-more--rail"[^>]*>[^<]*</a></div>#u', $x[0]['grid'] )
			&& (bool) preg_match( '#id="tp-list-villages"[^>]*data-tp-total="9"#', $x[0]['page'] )
			&& false === strpos( $x[1]['grid'], 'tp-more--rail' ) && false === strpos( $x[1]['page'], 'id="tp-list-villages"' )
			&& false === strpos( $x[2]['grid'], 'tp-more--rail' ) && false === strpos( $x[2]['page'], 'id="tp-list-villages"' );
	},
	array( $v24_nine, $v24_six, $v24_three ),
	array( array( 'grid' => $v24_six['grid'], 'page' => $v24_six['page'] ), $v24_nine, $v24_nine ) );

$zones24 = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );

tp441_mut( 'سقف هر بخش از یک تابع: اسلایدر هشت، کاشی‌های ستون کناری شش؛ پنجرۀ فهرست از ستون اصلی و ستون کناری هر دو جمع می‌شود',
	function ( $s ) {
		return false !== strpos( tp2c_body( $s, 'topersia_g_list_cap' ), "array( 'villages', 'nature', 'ancient', 'routes', 'subranges', 'border' )" )
			&& false !== strpos( tp2c_body( $s, 'topersia_g_list_cap' ), ': topersia_g_slider_max();' )
			&& false !== strpos( $s, "foreach ( array_merge( \$layout['main'], \$layout['rail'] ) as \$zone ) {" )
			&& false !== strpos( tp2c_body( $s, 'topersia_g_list_modal' ), "\$res['total'] <= topersia_g_list_cap( \$key )" );
	},
	$zones24, str_replace( "foreach ( array_merge( \$layout['main'], \$layout['rail'] ) as \$zone ) {", "foreach ( \$layout['main'] as \$zone ) {", $zones24 ) );

/* هر نوعِ بخش کارتی که کاشی‌اش بریده می‌شود برچسب «همه …» دارد — نه «همه موارد» عمومی */
tp441_mut( 'برچسب «همه …» هر بخش کارتی از جدول: روستاها · پهنه‌های طبیعی · محوطه‌های باستانی · مسیرها · زیرشاخه‌ها · گذرهای مرزی (کاتالوگ سه‌زبانه)',
	function ( $s ) {
		$b = tp2c_body( $s, 'topersia_g_list_more' );
		foreach ( array( 'همه روستاها ←', 'همه پهنه‌های طبیعی ←', 'همه محوطه‌های باستانی ←', 'همه مسیرها ←', 'همه زیرشاخه‌ها ←', 'همه گذرهای مرزی ←' ) as $l ) {
			if ( false === strpos( $b, $l ) ) { return false; }
		}
		return true;
	},
	$zones24, str_replace( "'villages'    => 'همه روستاها ←',", '', $zones24 ) );

/* ══ ۲ه٫۵ — مختصات: یک تابع مرکزی (P5-D314) ═════════════════════════════ */

echo "\nفاز ۲ه — ۲ه٫۵ هوا از مختصات  P5-D314\n" . str_repeat( '─', 78 ) . "\n";

$geo_file = dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-geo.php';
$geo_src  = is_file( $geo_file ) ? file_get_contents( $geo_file ) : '';
require_once $geo_file;

$geo_cases = array(
	'dec'    => topersia_geo_point( '32.6539, 51.6660' ),
	'dms'    => topersia_geo_point( '32°20′N, 52°47′E' ),
	'dms3'   => topersia_geo_point( "32° 20' 15\" N 52° 47' E" ),
	'fa'     => topersia_geo_point( '۳۲٫۶۵۳۹، ۵۱٫۶۶۶۰' ),
	'swap'   => topersia_geo_point( '52°47′E, 32°20′N' ),
	'poly'   => topersia_geo_point( '{"type":"Polygon","coordinates":[[[52.0,32.0],[53.0,32.0],[53.0,33.0],[52.0,33.0],[52.0,32.0]]]}' ),
	'json'   => topersia_geo_point( '{"lat":35.7,"lng":51.4}' ),
	'list'   => topersia_geo_point( '32.1,52.1; 32.3,52.1; 32.3,52.5' ),
	'paris'  => topersia_geo_point( '48.8566, 2.3522' ),
	'empty'  => topersia_geo_point( '' ),
	'junk'   => topersia_geo_point( 'نامشخص' ),
	'attr'   => topersia_geo_attr( '32°20′N, 52°47′E' ),
);

tp441_mut( 'topersia_geo_point: دهدهی، درجه-دقیقه-ثانیه (گاوخونی «32°20′N, 52°47′E»)، رقم فارسی، جهت‌های جابه‌جا، GeoJSON و فهرست ← مرکز هندسی؛ بیرون از ایران و نامعتبر ← null',
	function ( $c ) {
		return array( 'lat' => 32.6539, 'lng' => 51.666 ) === $c['dec'] && array( 'lat' => 32.333333, 'lng' => 52.783333 ) === $c['dms']
			&& array( 'lat' => 32.3375, 'lng' => 52.783333 ) === $c['dms3'] && $c['fa'] === $c['dec'] && $c['swap'] === $c['dms']
			&& array( 'lat' => 32.5, 'lng' => 52.5 ) === $c['poly'] && array( 'lat' => 35.7, 'lng' => 51.4 ) === $c['json']
			&& array( 'lat' => 32.233333, 'lng' => 52.233333 ) === $c['list']
			&& null === $c['paris'] && null === $c['empty'] && null === $c['junk'] && '32.333333, 52.783333' === $c['attr'];
	},
	$geo_cases, array_merge( $geo_cases, array( 'dms' => null ) ) );

tp441_mut( 'هوا و نقشه از همین تابع: کارت هوا و data-tp-coords نقشه «lat, lng» دهدهیِ نرمال‌شده‌اند؛ REST هوا هر قالبی را می‌پذیرد؛ نقطۀ نامعتبر ← کارت هوا نه',
	function ( $x ) {
		$wx  = tp2c_body( $x[0], 'topersia_g_sc_weather' );
		$map = tp2c_body( $x[0], 'topersia_g_sc_map' );
		$rst = tp2c_body( $x[1], 'topersia_g_wx_rest' );
		return false !== strpos( $wx, 'topersia_geo_attr(' ) && false !== strpos( $map, 'topersia_geo_attr(' ) && false !== strpos( $rst, 'topersia_geo_point(' );
	},
	array( $zones2c, file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-weather.php' ) ),
	array( str_replace( 'topersia_geo_attr(', 'esc_attr(', $zones2c ), file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-weather.php' ) ) );
