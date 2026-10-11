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
			&& false !== strpos( $s, 'Math.abs( y - aim ) > 2' ) && false !== strpos( $s, "\t\t\t\tgoTo( el );\n" )
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

tp441_mut( 'goTo: هر ورودی کاربر (wheel · touchstart · keydown · mousedown) اسکرولِ در جریان را رها می‌کند و پس از رسیدن و چند فریم ماندگاری پایان می‌گیرد — صفحه به بخش برنمی‌گردد',
	function ( $s ) {
		return false !== strpos( $s, "[ 'wheel', 'touchstart', 'keydown', 'mousedown' ].forEach( function ( type ) {" )
			&& false !== strpos( $s, "window.addEventListener( type, function () { goingTo ++; }, { passive: true } );" )
			&& false !== strpos( $s, 'if ( steady < 8 && Date.now() - began < 1800 ) {' );
	},
	$js2e, str_replace( 'if ( steady < 8 && Date.now() - began < 1800 ) {', 'if ( Date.now() - began < 1800 ) {', $js2e ) );

tp441_mut( 'pick در قفلِ پس از کلیک خودش را برای پایان قفل زمان‌بندی می‌کند — بخش فعال با IntersectionObserver کهنه نمی‌ماند',
	function ( $s ) { return false !== strpos( $s, 'lockTimer = window.setTimeout( later, lockUntil - Date.now() + 40 );' ); },
	$js2e, str_replace( 'lockTimer = window.setTimeout( later, lockUntil - Date.now() + 40 );', '', $js2e ) );
