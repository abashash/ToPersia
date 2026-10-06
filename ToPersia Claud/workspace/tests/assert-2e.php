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
