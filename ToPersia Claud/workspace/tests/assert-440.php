<?php
/**
 * ادعاهای ۴٫۴٫۰ — فاز ۱ از `docs/SPRINT-4.4-PHASES.md`.
 *
 * از assert.php پس از assert-430 و پیش از نگهبان سندها بار می‌شود و همان
 * شمارنده‌ها و همان دادهٔ آزمون را دارد. هر دستکاری دادهٔ آزمون در همین
 * پرونده به حال پیشین برمی‌گردد.
 *
 * بند ۱٫۱ (فلش‌ها، P5-D243) در خودِ assert.php است — همان‌جا که ادعاهای
 * P5-D226 بودند و بازنویسی شدند.
 */

echo "\n۴٫۴٫۰ — فاز ۱\n" . str_repeat( '─', 78 ) . "\n";

$root440 = dirname( __DIR__ );
$src440  = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );
$js440   = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-zones.js' );
$css440  = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' );
$tok440  = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-tokens.css' );

/* ── ابزار: پالت سه حالت از خودِ topersia-tokens.css ─────────────────────
   هر بلوکِ سطح بالا که `:root` دارد پایهٔ هر سه حالت است و بلوکِ
   `html[data-tp-mode="…"]` روی آن می‌نشیند (وزنش بیشتر است). بلوک‌های
   @media (کنتراست بالا، کاهش حرکت) انتخاب کاربرند و بیرون می‌مانند.
   var() و color-mix(in srgb) همان‌طور حل می‌شوند که مرورگر حل می‌کند. */

function tp440_palette( $css ) {

	$css = preg_replace( '#/\*.*?\*/#s', '', $css );

	while ( preg_match( '/@[a-z-]+[^{;]*\{/i', $css, $m, PREG_OFFSET_CAPTURE ) ) {
		$at    = $m[0][1];
		$depth = 0;
		for ( $j = $at + strlen( $m[0][0] ) - 1; $j < strlen( $css ); $j ++ ) {
			if ( '{' === $css[ $j ] ) { $depth ++; }
			if ( '}' === $css[ $j ] && 0 === -- $depth ) { break; }
		}
		$css = substr( $css, 0, $at ) . substr( $css, $j + 1 );
	}

	preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css, $blocks, PREG_SET_ORDER );

	$base  = array();
	$modes = array( 'light' => array(), 'evening' => array(), 'dark' => array() );

	foreach ( $blocks as $b ) {

		$sels = array_map( 'trim', explode( ',', $b[1] ) );
		preg_match_all( '/(--[\w-]+)\s*:\s*([^;]+)/', $b[2], $decl, PREG_SET_ORDER );

		foreach ( $decl as $d ) {
			if ( in_array( ':root', $sels, true ) ) {
				$base[ $d[1] ] = trim( $d[2] );
			}
			foreach ( $sels as $s ) {
				if ( preg_match( '/^html\[data-tp-mode="(light|evening|dark)"\]$/', $s, $mm ) ) {
					$modes[ $mm[1] ][ $d[1] ] = trim( $d[2] );
				}
			}
		}
	}

	foreach ( $modes as $k => $v ) {
		$modes[ $k ] = array_merge( $base, $v );
	}

	return $modes;
}

/** رنگ یک مقدار CSS در یک حالت — [r, g, b] یا null. */
function tp440_color( $v, $vars, $depth = 0 ) {

	$v = trim( $v );

	if ( $depth > 12 ) {
		return null;
	}

	if ( preg_match( '/^var\(\s*(--[\w-]+)\s*(?:,\s*(.+))?\)$/s', $v, $m ) ) {
		if ( isset( $vars[ $m[1] ] ) ) {
			return tp440_color( $vars[ $m[1] ], $vars, $depth + 1 );
		}
		return isset( $m[2] ) ? tp440_color( $m[2], $vars, $depth + 1 ) : null;
	}

	if ( preg_match( '/^color-mix\(\s*in srgb\s*,\s*(.+?)\s+([\d.]+)%\s*,\s*(.+)\)$/s', $v, $m ) ) {
		$a = tp440_color( $m[1], $vars, $depth + 1 );
		$b = tp440_color( $m[3], $vars, $depth + 1 );
		$p = (float) $m[2] / 100;
		return ( $a && $b ) ? array_map( function ( $x, $y ) use ( $p ) { return $x * $p + $y * ( 1 - $p ); }, $a, $b ) : null;
	}

	if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $v, $m ) ) {
		$h = 3 === strlen( $m[1] ) ? preg_replace( '/(.)/', '$1$1', $m[1] ) : $m[1];
		return array( hexdec( substr( $h, 0, 2 ) ), hexdec( substr( $h, 2, 2 ) ), hexdec( substr( $h, 4, 2 ) ) );
	}

	return null;
}

/** نسبت کنتراست WCAG 2 — دو رنگ [r, g, b]. */
function tp440_ratio( $a, $b ) {

	$lum = function ( $c ) {
		$l = array_map( function ( $x ) {
			$x = $x / 255;
			return $x <= 0.04045 ? $x / 12.92 : pow( ( $x + 0.055 ) / 1.055, 2.4 );
		}, $c );
		return 0.2126 * $l[0] + 0.7152 * $l[1] + 0.0722 * $l[2];
	};

	$x = $lum( $a );
	$y = $lum( $b );

	return ( max( $x, $y ) + 0.05 ) / ( min( $x, $y ) + 0.05 );
}

/** همهٔ جفت‌ها بالای آستانه؟ — [ok, کمینه، کجا] */
function tp440_pairs( $palette, $pairs, $min ) {

	$worst = array( INF, '' );

	foreach ( $palette as $mode => $vars ) {
		foreach ( $pairs as $name => $pair ) {
			$fg = tp440_color( $pair[0], $vars );
			$bg = tp440_color( $pair[1], $vars );
			$r  = ( $fg && $bg ) ? tp440_ratio( $fg, $bg ) : 0;
			if ( $r < $worst[0] ) {
				$worst = array( $r, $mode . ' · ' . $name );
			}
		}
	}

	return array( $worst[0] >= $min, $worst[0], $worst[1] );
}

$pal440 = tp440_palette( $tok440 );

/* ══ ۱٫۲ — کاشی‌های ستون کناری  P5-D244 ═══════════════════════════════ */

$mk440 = function ( $n ) {
	$t = array();
	for ( $i = 1; $i <= $n; $i ++ ) {
		$t[] = array( 'label' => 'ب', 'name' => 'نام ' . $i, 'url' => 'https://topersia.com/t-' . $i . '/' );
	}
	return $t;
};
$t14 = topersia_g_tiles( $mk440( 14 ) );
$t7  = topersia_g_tiles( $mk440( 7 ) );
$t6  = topersia_g_tiles( $mk440( 6 ) );

t( 'کاشی: شش اول پیدا و بقیه در DOM با hidden؛ «{N} مورد دیگر» با N = کمینهٔ شش و باقی‌مانده  P5-D244',
	14 === substr_count( $t14, 'class="tp-tile"' ) && 8 === substr_count( $t14, 'class="tp-tile" hidden data-tp-rest-item>' )
	&& false !== strpos( $t14, '<button class="tp-more-rest" type="button" data-tp-more data-tp-step="6" data-tp-rest="8"><span data-tp-more-n>۶</span> مورد دیگر</button>' )
	&& false !== strpos( $t7, 'data-tp-rest="1"><span data-tp-more-n>۱</span> مورد دیگر</button>' )
	&& false === strpos( $t6, 'hidden' ) && false === strpos( $t6, 'data-tp-more' ) );

t( 'کاشی: برچسب کوچک بالا، نام پررنگ — عیناً کاشی `.neighbors` مرجع؛ ظرف `[data-tp-tiles]`',
	0 === strpos( $t7, '<div class="tp-tiles" data-tp-tiles><a href="https://topersia.com/t-1/" class="tp-tile"><span class="tp-tile__k">ب</span><b>نام 1</b></a>' ) );

$rg_vil = r( 20, 'tp_relgrid', array( 'rel' => 'villages' ) );
$rg_nat = r( 20, 'tp_relgrid', array( 'rel' => 'nature' ) );
$GLOBALS['tp_rel']['93']['parents'][67] = array( 27 );
$rg_anc = r( 20, 'tp_relgrid', array( 'rel' => 'ancient' ) );
unset( $GLOBALS['tp_rel']['93']['parents'][67] );

t( 'کاشی: همه هم‌نوع — نام نوع تکرار نمی‌شود؛ شهر یا شهرستانِ خودِ مورد، و نبودش هیچ برچسب  P5-D244',
	false !== strpos( $rg_vil, '<div class="tp-tiles" data-tp-tiles><a href="https://topersia.com/abyaneh/" class="tp-tile"><b>ابیانه</b></a>' )
	&& false === strpos( $rg_vil, 'tp-tile__k' )
	&& false !== strpos( $rg_anc, '<a href="https://topersia.com/sialk/" class="tp-tile"><span class="tp-tile__k">کاشان</span><b>' ) );

t( 'کاشی: نوع‌های مختلف — برچسب همان نام نوع هر مورد  P5-D244',
	false !== strpos( $rg_nat, '<span class="tp-tile__k">' . topersia_g_kind_label( 24 ) . '</span><b>کرکس</b>' )
	&& false !== strpos( $rg_nat, '<span class="tp-tile__k">' . topersia_g_kind_label( 23 ) . '</span><b>کویر مرنجاب</b>' )
	&& topersia_g_kind_label( 23 ) !== topersia_g_kind_label( 24 ) );

t( 'کاشی: «اطلاعات جغرافیایی» و «نشان‌ها» هم همین کاشی‌اند',
	/* P5-D386: کد استان از آمار رفت، اقلیم به آمار آمد؛ «اطلاعات جغرافیایی» مرکز استان را کاشی می‌کند */
	false !== strpos( r( 20, 'tp_facts' ), 'class="tp-tile"><span class="tp-tile__k">مرکز استان</span><b>شهر اصفهان</b></a>' )
	&& false !== strpos( r( 20, 'tp_badges' ), '<div class="tp-tiles" data-tp-tiles><a class="tp-tile"><b>شهر خلاق یونسکو</b></a>' ) );

t( 'کاشی: سه ستون minmax(0,1fr)؛ دوستونهٔ جایگزین؛ هیچ واژه‌ای وسط نمی‌شکند؛ hidden پنهان  P5-D244',
	false !== strpos( $css440, 'html .tp-page .tp-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}' )
	&& false !== strpos( $css440, 'html .tp-page .tp-tiles.tp-tiles--2{grid-template-columns:repeat(2,minmax(0,1fr))}' )
	&& (bool) preg_match( '/html \.tp-page \.tp-tile\{[^}]*word-break:normal;overflow-wrap:normal;hyphens:none;/', $css440 )
	&& false !== strpos( $css440, 'html .tp-page .tp-tile[hidden]{display:none}' ) );

t( 'کاشی: بیش از دو خط (از ارتفاع واقعی ÷ ارتفاع خط) یا واژهٔ ناشکستنیِ سرریز ← کل همان کارت دوستونه، با ResizeObserver (فقط تغییر پهنا) و پس از رسیدن قلم؛ «بیشتر» دستهٔ بعدی و پس از آخرین دسته برداشته  P5-D244 · P5-D344',
	false !== strpos( $js440, 'if ( lineCount( texts[ j ] ) > 2 ) {' ) && false !== strpos( $js440, 'return Math.round( el.getBoundingClientRect().height / lh );' )
	&& false !== strpos( $js440, "if ( tile.scrollWidth > tile.clientWidth + 1 ) {\n\t\t\t\tgrid.classList.add( 'tp-tiles--2' );" )
	&& false !== strpos( $js440, 'new ResizeObserver(' ) && false !== strpos( $js440, 'if ( w && seen[ i ] !== w ) {' )
	&& false !== strpos( $js440, 'document.fonts.ready.then( all );' )
	&& false !== strpos( $js440, "var step  = parseInt( btn.getAttribute( 'data-tp-step' ), 10 ) || Infinity;" )
	&& false !== strpos( $js440, "if ( ! left ) {\n\t\t\t\tbtn.remove();" )
	&& false !== strpos( $js440, 'n.textContent = digits( Math.min( step, left ) );' ) );   /* ۴٫۴٫۲: رقمِ زبان صفحه */

$rg_prac = r( 20, 'tp_relgrid', array( 'rel' => 'practical' ) );
t( '«اطلاعات سفر» دو ستون می‌ماند و فقط توکن کاشی می‌گیرد  P5-D244',
	false !== strpos( $rg_prac, '<div class="tp-practical-grid">' ) && false === strpos( $rg_prac, 'tp-tiles' )
	&& false !== strpos( $css440, 'html .tp-page .tp-practical-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px}' )
	&& (bool) preg_match( '/html body \.tp-page \.tp-practical-grid a\{\s*background:var\(--tp-tile-bg\)/', $css440 ) );

/* ══ ۱٫۳ — توکن کاشی  P5-D245 ══════════════════════════════════════════ */

t( 'توکن کاشی: شش توکن در :root، هر کدام همان توکن پالتی که کاشی روز هوا در هر سه حالت دارد  P5-D245',
	(bool) preg_match( '/:root\{\s*--tp-tile-bg:var\(--tp-bg\);\s*--tp-tile-border:var\(--tp-border\);\s*--tp-tile-text:var\(--tp-text\);\s*--tp-tile-muted:var\(--tp-text-2\);\s*--tp-tile-hover-bg:var\(--tp-soft\);\s*--tp-tile-radius:12px;\s*\}/', $tok440 )
	&& (bool) preg_match( '/html \.tp-page \.tp-wx__day\{[^}]*border-radius:12px;\s*border:1px solid var\(--tp-border\);background:var\(--tp-bg\);/', $css440 ) );

/* هر کاشی و دکمه‌ای که P5-D245 نام برده — پس‌زمینه، قاب و متن فقط از توکن کاشی */
$tile_sel440 = array(
	'html .tp-page .tp-tile{'                                                     => 'کاشی ستون کناری',
	'html body .tp-page .tp-practical-grid a{'                                    => 'اطلاعات سفر',
	'html body .tp-page .tp-culture-card .tp-culture-card__list a{'                => 'خانواده‌های فرهنگ',
	'.tp-page .tp-list__q{'                                                       => 'جست‌وجوی پنجرهٔ فهرست',
	'.tp-page .tp-list__card{'                                                    => 'عضو پنجرهٔ فهرست',
	'html .tp-page .tp-gallery-modal--list .tp-gallery-modal__head > .tp-gallery-modal__close{' => 'دکمهٔ بستن پنجرهٔ فهرست',
);
$tile_bad440 = array();
foreach ( $tile_sel440 as $sel => $what ) {
	$at   = strpos( $css440, $sel );
	$body = false === $at ? '' : substr( $css440, $at, strpos( $css440, '}', $at ) - $at );
	/* جعبهٔ جست‌وجو قابش --tp-tile-muted است — روی پردهٔ تیره؛ ادعای بعدی */
	$edge = '.tp-page .tp-list__q{' === $sel ? 'border:1px solid var(--tp-tile-muted)' : 'var(--tp-tile-border)';
	if ( false === strpos( $body, 'background:var(--tp-tile-bg)' ) || false === strpos( $body, $edge )
		|| false === strpos( $body, 'color:var(--tp-tile-text)' ) ) {
		$tile_bad440[] = $what;
	}
}
t( 'توکن کاشی: کاشی‌ها، اطلاعات سفر، اعضا و دکمهٔ پنجرهٔ فهرست، جست‌وجو و خانواده‌های فرهنگ — همه از --tp-tile-*  P5-D245',
	array() === $tile_bad440, implode( ' · ', $tile_bad440 ) );

/* مرز فیلد جست‌وجو روی پردهٔ پنجره (rgba(6,12,11,.94) روی زمینهٔ صفحه) — بیشینهٔ پر و قاب ≥ ۳ */
$edge440 = array( INF, '' );
if ( preg_match( '/html \.tp-page \.tp-gallery-modal\{[^}]*background:rgba\((\d+),(\d+),(\d+),([\d.]+)\)/', $css440, $cm ) ) {
	foreach ( $pal440 as $mode => $vars ) {
		$page    = tp440_color( 'var(--tp-bg)', $vars );
		$curtain = array_map( function ( $c, $p ) use ( $cm ) { return $c * (float) $cm[4] + $p * ( 1 - (float) $cm[4] ); }, array( $cm[1], $cm[2], $cm[3] ), $page );
		$r       = max( tp440_ratio( tp440_color( 'var(--tp-tile-bg)', $vars ), $curtain ), tp440_ratio( tp440_color( 'var(--tp-tile-muted)', $vars ), $curtain ) );
		if ( $r < $edge440[0] ) { $edge440 = array( $r, $mode ); }
	}
}
t( 'توکن کاشی: جعبهٔ جست‌وجوی پنجرهٔ فهرست روی پرده دیده می‌شود — پر یا قاب ≥ ۳ در هر سه حالت (شب با قابِ کاشی ۱٫۷۳ بود)  P5-D245',
	$edge440[0] >= 3.0, sprintf( '%.2f در %s', $edge440[0], $edge440[1] ) );

list( $ok440, $min440, $at440 ) = tp440_pairs( $pal440, array(
	'متن روی کاشی'             => array( 'var(--tp-tile-text)', 'var(--tp-tile-bg)' ),
	'متن فرعی روی کاشی'        => array( 'var(--tp-tile-muted)', 'var(--tp-tile-bg)' ),
	'متن هاور روی هاور'        => array( 'var(--tp-primary-text)', 'var(--tp-tile-hover-bg)' ),
), 4.5 );
t( 'توکن کاشی: متن و متن فرعی روی کاشی، و متن هاور روی پرِ هاور ≥ ۴٫۵ در هر سه حالت — از خودِ پالت  P5-D245',
	$ok440, sprintf( '%.2f در %s', $min440, $at440 ) );

/* متن فرعی روی پرِ هاور در حالت روز ۴٫۴۹ است؛ هر متنِ فرعیِ کاشی روی هاور رنگ نام را می‌گیرد */
$muted_hover440 = tp440_pairs( array( 'light' => $pal440['light'] ), array( 'x' => array( 'var(--tp-tile-muted)', 'var(--tp-tile-hover-bg)' ) ), 4.5 );
t( 'توکن کاشی: متن فرعیِ کاشی روی هاور هرگز --tp-tile-muted نیست (روز ۴٫۴۹) — --tp-primary-text  P5-D245',
	! $muted_hover440[0]
	&& false !== strpos( $css440, 'html .tp-page a.tp-tile[href]:hover .tp-tile__k{color:var(--tp-primary-text)}' )
	/* ۴٫۴٫۱: «مسیرها» کاشی `.tp-tile` است و خطِ زیرِ نامش روی هاور رنگ نام را می‌گیرد  P5-D269 */
	&& false !== strpos( $css440, "html body .tp-page .tp-neighbors a:hover .tp-n-dir,\nhtml body .tp-page .tp-practical-grid a:hover span{color:var(--tp-primary-text)}" )
	&& false !== strpos( $css440, 'html .tp-page a.tp-tile[href]:hover .tp-tile__sub{color:var(--tp-primary-text)}' )
	&& false !== strpos( $css440, ".tp-page a.tp-list__card:hover .tp-list__tag,\n.tp-page a.tp-list__card:focus-visible .tp-list__tag{color:var(--tp-primary-text)}" ),
	sprintf( 'روز %.2f', $muted_hover440[1] ) );

t( 'توکن کاشی: متن ریزِ کاشی — برچسب، جهت، شهرهای مسیر، پیکان اطلاعات سفر، نوعِ عضو فهرست — --tp-tile-text، نه متن فرعی  P5-D184 · P5-D245',
	false !== strpos( $css440, 'html .tp-page .tp-tile__k{font-size:calc(.6 * var(--tp-rem));color:var(--tp-tile-text);' )
	&& false !== strpos( $css440, "html body .tp-page .tp-neighbors a .tp-n-dir,\nhtml body .tp-page .tp-practical-grid a span{color:var(--tp-tile-text);letter-spacing:normal}" )
	&& false !== strpos( $css440, 'html .tp-page .tp-tile__sub{font-size:calc(.6 * var(--tp-rem));color:var(--tp-tile-text);' )   /* شهرهای مسیر  P5-D269 */
	&& false !== strpos( $css440, '.tp-page .tp-list__tag{flex:none;color:var(--tp-tile-text);' )
	&& 2 === substr_count( $css440, 'var(--tp-tile-muted)' ) );   /* فقط placeholder و قاب فیلد جست‌وجو */

/* ══ ۱٫۴ — یکسانی کارت‌ها  P5-D246 ════════════════════════════════════ */

/* ۴٫۴٫۱: هنر ۲۰۰/۱۸۰ در جریان عادی و رفتنِ توضیح — P5-D266، در assert-441.php */
t( 'کارت: تصویر cover؛ عنوان دو خط با جای ثابت؛ ردیف‌های فرهنگ و فهرست هم‌قد  P5-D246',
	false !== strpos( $css440, 'html body .tp-page .tp-card-slide__art > .tp-card-slide__img{position:absolute;inset:0;inline-size:100%;block-size:100%;object-fit:cover;display:block}' )
	&& (bool) preg_match( '/html body \.tp-page \.tp-card-slide__title\{\s*display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2;overflow:hidden;\s*min-block-size:2lh;/', $css440 )
	&& false !== strpos( $css440, 'html body .tp-page .tp-culture-grid{grid-auto-rows:1fr}' )
	&& (bool) preg_match( '/\.tp-page \.tp-list__grid\{[^}]*grid-auto-rows:1fr;/', $css440 )
	&& false !== strpos( $css440, '.tp-page .tp-list__card b{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2;overflow:hidden}' )
	/* ۴٫۴٫۲: همان ردیف‌های 1fr، با `dense` که خانۀ خالی را پر می‌کند (ادعایش در assert-442.php) */
	&& false !== strpos( $css440, "html body .tp-page .tp-gallery-modal__grid{grid-auto-rows:1fr;grid-auto-flow:row dense}\nhtml body .tp-page .tp-gallery-modal__grid > .tp-gallery-modal__item{align-self:stretch}" ) );

$GLOBALS['tp_meta'][60]['hero_image'] = 520;
$att440 = r( 20, 'tp_slider', array( 'rel' => 'attractions' ) );
unset( $GLOBALS['tp_meta'][60]['hero_image'] );
t( 'کارت: با تصویر — `<img>` با ابعاد صریح، lazy، sizes، درون هنرِ همان کارت؛ بی‌تصویر گرادیان مرجع  P5-D246',
	(bool) preg_match( '#<div class="tp-card-slide__art tp-card-slide__art--img"[^>]*>(?:(?!</div>).)*<img width="\d+" height="\d+" class="tp-card-slide__img" src="[^"]+" alt="" loading="lazy" decoding="async" srcset="[^"]+" sizes="\(max-width: 720px\) 82vw, 300px">#su', $att440 )
	&& 1 === substr_count( $att440, 'tp-card-slide__art--img' ) );

$img_bad440 = array();
foreach ( array( 20, 27, 30, 40 ) as $pid440 ) {
	preg_match_all( '#<img\b[^>]*>#', r( $pid440, 'tp_page' ), $im440 );
	foreach ( $im440[0] as $one ) {
		if ( ! preg_match( '# width="\d+"#', $one ) || ! preg_match( '# height="\d+"#', $one ) ) {
			$img_bad440[] = $pid440 . ': ' . $one;
		}
	}
}
t( 'کارت: هر `<img>` صفحه — سربرگ، گالری، کارت، تمام‌صفحه — width و height صریح دارد  P5-D246',
	array() === $img_bad440, implode( ' | ', array_slice( $img_bad440, 0, 3 ) ) );

/* ══ ۱٫۵ — تصویر بندانگشتی اخبار ════════════════════════════════════════ */

$GLOBALS['tp_meta'][901]['_thumbnail_id'] = 530;
$GLOBALS['tp_filters']['topersia_g_news_pre_query'] = array( function ( $pre, $place ) {
	return 20 === (int) $place ? array( 'ids' => array( 901, 902 ), 'total' => 2 ) : $pre;
} );
$news440 = r( 20, 'tp_news' );
unset( $GLOBALS['tp_filters']['topersia_g_news_pre_query'], $GLOBALS['tp_meta'][901]['_thumbnail_id'] );

t( 'خبر: تصویر شاخص ۴:۳ — ابعاد صریح، srcset، sizes «88px/112px»، lazy؛ بی‌تصویر همان جعبه با آیکون  ۱٫۵',
	(bool) preg_match( '#<div class="tp-thumb"><img width="\d+" height="\d+" class="" src="[^"]+" alt="" loading="lazy" decoding="async" srcset="[^"]+" sizes="\(max-width: 720px\) 88px, 112px"></div>#', $news440 )
	&& 1 === substr_count( $news440, '<div class="tp-thumb"><svg' ) );

t( 'خبر: جعبه ۱۱۲×۸۴ و در موبایل ۸۸×۶۶، نسبت ۴:۳، تصویر cover — با تصویر و بی‌تصویر یک اندازه  ۱٫۵',
	false !== strpos( $css440, 'html body .tp-page .tp-news .tp-thumb{inline-size:112px;block-size:84px;aspect-ratio:4/3}' )
	&& false !== strpos( $css440, 'html body .tp-page .tp-news a{grid-template-columns:112px 1fr}' )
	&& (bool) preg_match( '/@media\(max-width:720px\)\{\s*html body \.tp-page \.tp-news a\{grid-template-columns:88px 1fr\}\s*html body \.tp-page \.tp-news \.tp-thumb\{inline-size:88px;block-size:66px\}/', $css440 )
	&& false !== strpos( $css440, 'html .tp-page .tp-news .tp-thumb img{inline-size:100%;block-size:100%;object-fit:cover;display:block}' ) );

/* ══ ۱٫۶ — جداکنندهٔ نام‌ها ═════════════════════════════════════════════ */

$p440 = r( 20, 'tp_page' );
$GLOBALS['tp_ltr'] = true;
$p440_ltr = r( 20, 'tp_page' );
unset( $GLOBALS['tp_ltr'] );

t( 'نام‌ها: راست‌به‌چپ «، » — hero__alt و tp-altnames، هر سطر یا «·» نام جدا  ۱٫۶',
	false !== strpos( $p440, '<p class="tp-hero__alt"><span lang="en" dir="ltr">Isfahan Province</span>، <em lang="ar">محافظة أصفهان</em></p>' )
	&& false !== strpos( $p440, '<p class="tp-altnames">ایالت اصفهان، سپاهان، نصف جهان</p>' ) );

t( 'نام‌ها: چپ‌به‌راست «, » — از جهت صفحه، نه زبان ثابت  ۱٫۶',
	false !== strpos( $p440_ltr, '</span>, <em lang="ar">' ) && false !== strpos( $p440_ltr, '<p class="tp-altnames">ایالت اصفهان, سپاهان, نصف جهان</p>' ) );

$GLOBALS['tp_meta'][20]['alternative_names'] = "اِصفَهان\nاِسپَهان";
$alt440 = r( 20, 'tp_hero' );
$GLOBALS['tp_meta'][20]['alternative_names'] = 'سپاهان · نصف جهان';
t( 'نام‌ها: هر سطر alternative_names یک نام — «اِصفَهان، اِسپَهان»  ۱٫۶',
	false !== strpos( $alt440, '<p class="tp-altnames">ایالت اصفهان، اِصفَهان، اِسپَهان</p>' ) );

/* ══ ۱٫۷ — دعوت هوش مصنوعی ══════════════════════════════════════════════ */

/* ۴٫۴٫۱: دعوت مهمان یک دکمۀ «ثبت‌نام / ورود» در ردیف دکمه‌ها شد و «ورود» متنی رفت — P5-D267، در assert-441.php */

/* CSS دعوت هم — P5-D267، در assert-441.php */

$ai_stops440 = array( '#0e5a55', '#0f4b47', '#0b3a37' );   /* گرادیان ثابتِ بلوک AI در هر سه حالت */
list( $okb440, $minb440, $atb440 ) = tp440_pairs( $pal440, array(
	'ثبت‌نام'       => array( 'var(--tp-on-accent)', 'var(--tp-accent)' ),
	'ثبت‌نام هاور'  => array( 'var(--tp-on-accent)', 'var(--tp-accent-strong)' ),
), 4.5 );
$ai_min440 = array( INF, '' );
foreach ( $ai_stops440 as $stop ) {
	foreach ( array( 'متن دعوت' => array( '#d3e7e3', 4.5 ), 'ورود' => array( '#fff', 4.5 ), 'حلقهٔ فوکوس' => array( '#fff', 3.0 ) ) as $what => $spec ) {
		$r = tp440_ratio( tp440_color( $spec[0], array() ), tp440_color( $stop, array() ) ) / $spec[1];
		if ( $r < $ai_min440[0] ) { $ai_min440 = array( $r, $what . ' روی ' . $stop ); }
	}
	foreach ( $pal440 as $mode => $vars ) {
		$r = tp440_ratio( tp440_color( 'var(--tp-accent)', $vars ), tp440_color( $stop, array() ) ) / 3.0;
		if ( $r < $ai_min440[0] ) { $ai_min440 = array( $r, 'مرز دکمه ' . $mode . ' روی ' . $stop ); }
	}
}
t( 'AI: متن دکمه، متن دعوت و «ورود» ≥ ۴٫۵، حلقهٔ فوکوس و خودِ دکمه روی بلوک ≥ ۳ — هر سه حالت  ۱٫۷',
	$okb440 && $ai_min440[0] >= 1, sprintf( 'دکمه %.2f (%s) · بدترین نسبت به آستانه %.2f (%s)', $minb440, $atb440, $ai_min440[0], $ai_min440[1] ) );

/* ══ ۱٫۸ — فقط منتشرشده  P5-D247 ══════════════════════════════════════ */

t( 'منتشرشده: یک دروازهٔ مرکزی — publish، بی‌رمز، و نسخهٔ زبان جاری WPML که خودش منتشرشده است  P5-D247',
	function_exists( 'topersia_g_published' )
	&& false !== strpos( $src440, "return \$published ? topersia_g_published( array_values( \$ids ) ) : array_values( \$ids );" )
	&& false !== strpos( $src440, "apply_filters( 'wpml_object_id', \$one, \$type, false, \$lang )" ) );

$keep440 = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], $GLOBALS['tp_rel'] );
$hid440  = array();
$n440    = 1100;
$states440 = array( 'draft' => array( 'draft', '' ), 'private' => array( 'private', '' ), 'pending' => array( 'pending', '' ), 'password' => array( 'publish', 'رمز' ) );

/* هر رابطه‌ای که صفحهٔ استان، شهر یا شهرِ مرکز می‌خواند، چهار عضو پنهان */
foreach ( array(
	array( '75', 'children', 20, 'tp_village' ),       /* روستاها */
	array( '80', 'children', 20, 'tp_desert' ),        /* پهنه‌های طبیعی */
	array( '78', 'children', 20, 'tp_mountain' ),
	array( '92', 'children', 20, 'tp_ancient_site' ),  /* محوطه‌ها */
	array( '96', 'children', 20, 'tp_city' ),          /* مرکز استان */
	array( '72', 'children', 20, 'tp_county' ),        /* پلهٔ میانیِ شهرها */
	array( '73', 'children', 22, 'tp_city' ),          /* شهرها — اسلایدر و پنجرهٔ فهرست */
	array( '84', 'children', 27, 'tp_monument' ),      /* جاذبه‌ها */
	array( '107', 'children', 20, 'tp_handicraft' ),   /* فرهنگ */
	array( '110', 'children', 20, 'tp_local_food' ),
	array( '157', 'children', 20, 'tp_health_safety' ),/* اطلاعات سفر */
	array( '102', 'children', 20, 'tp_province' ),     /* هم‌مرز */
	array( '172', 'parents', 20, 'tp_route' ),         /* مسیرها */
	array( '93', 'children', 30, 'tp_ancient_site' ),  /* شهر */
	array( '74', 'children', 30, 'tp_district' ),
) as $spec ) {
	list( $rel, $dir, $owner, $type ) = $spec;
	foreach ( $states440 as $why => $st ) {
		$id = $n440 ++;
		$GLOBALS['tp_posts'][ $id ] = array( 'type' => $type, 'title' => "TPHIDDEN {$rel} {$why}", 'url' => "https://topersia.com/hidden-{$id}/", 'status' => $st[0], 'password' => $st[1] );
		$GLOBALS['tp_meta'][ $id ]  = array( 'name_fa' => "TPHIDDEN {$rel} {$why}", 'short_summary' => 'TPHIDDEN', 'hero_image' => 540 );
		$GLOBALS['tp_rel'][ $rel ][ $dir ][ $owner ] = array_merge( array( $id ), isset( $GLOBALS['tp_rel'][ $rel ][ $dir ][ $owner ] ) ? $GLOBALS['tp_rel'][ $rel ][ $dir ][ $owner ] : array() );
		$hid440[ $why ][] = $id;
	}
}
/* نیای پنهان پیش از ایران — مسیر راهنما باید از آن بگذرد و نامش را نیاورد */
$GLOBALS['tp_posts'][1199] = array( 'type' => 'tp_country', 'title' => 'TPHIDDEN country', 'url' => 'https://topersia.com/hidden-country/', 'status' => 'draft' );
$GLOBALS['tp_rel']['71']['parents'][20] = array( 1199, 10 );

$leak440 = array();
foreach ( array( 20, 27, 30 ) as $pid440 ) {
	$html = r( $pid440, 'tp_page' );
	if ( false !== strpos( $html, 'TPHIDDEN' ) || false !== strpos( $html, 'hidden-' ) ) {
		preg_match( '/.{0,60}TPHIDDEN.{0,30}/u', $html, $lm );
		$leak440[] = $pid440 . ': ' . ( isset( $lm[0] ) ? strip_tags( $lm[0] ) : 'hidden-url' );
	}
}
t( 'منتشرشده: پیش‌نویس، خصوصی، در انتظار و رمزدار در هیچ ناحیه، مودال، اسلایدر، فهرست یا مسیر راهنما نمی‌آید — استان، شهر و مرکز  P5-D247',
	array() === $leak440 && false === strpos( r( 20, 'tp_crumbs' ), 'ایران' ),
	implode( ' | ', $leak440 ) );

t( 'منتشرشده: خواندن رابطه به‌طور پیش‌فرض دروازه دارد؛ پیمودن (`false`) از پنهان می‌گذرد بی‌آنکه نشانش دهد  P5-D247',
	array( 25, 26 ) === topersia_g_rel_ids( 20, array( array( 'id' => 75, 'dir' => 'children' ) ) )
	&& 6 === count( topersia_g_rel_ids( 20, array( array( 'id' => 75, 'dir' => 'children' ) ), false ) ) );

/* خبر: راه کوتاهِ آزمون هم از دروازه می‌گذرد */
foreach ( array( 1201 => array( 'draft', '' ), 1202 => array( 'publish', 'رمز' ) ) as $nid => $st ) {
	$GLOBALS['tp_posts'][ $nid ] = array( 'type' => 'tpc_news', 'title' => 'TPHIDDEN news', 'url' => 'https://topersia.com/news/hidden-' . $nid . '/', 'status' => $st[0], 'password' => $st[1] );
}
$GLOBALS['tp_filters']['topersia_g_news_pre_query'] = array( function ( $pre, $place ) {
	return 20 === (int) $place ? array( 'ids' => array( 1201, 901, 1202 ), 'total' => 3 ) : $pre;
} );
$nq440   = topersia_g_news_query( 20, 10, 0, 1 );
$nh440   = r( 20, 'tp_news' );
unset( $GLOBALS['tp_filters']['topersia_g_news_pre_query'] );
t( 'منتشرشده: خبر پیش‌نویس یا رمزدار نه در بخش خبر، نه در صفحهٔ خبرها و شمارش آن  P5-D247',
	array( 901 ) === $nq440['ids'] && 1 === $nq440['total'] && false === strpos( $nh440, 'TPHIDDEN' ) && false !== strpos( $nh440, 'news/n-901/' ) );

/* WPML: نسخهٔ زبان جاری باید خودش منتشرشده باشد؛ بی نسخه، نه */
$GLOBALS['tp_posts'][1127] = array( 'type' => 'tp_city', 'title' => 'Kashan', 'url' => 'https://topersia.com/en/kashan/' );
$GLOBALS['tp_posts'][1150] = array( 'type' => 'tp_city', 'title' => 'TPHIDDEN en', 'url' => 'https://topersia.com/en/natanz/', 'status' => 'draft' );
$GLOBALS['tp_filters']['wpml_current_language'] = array( function () { return 'en'; } );
$GLOBALS['tp_filters']['wpml_object_id']        = array( function ( $id ) {
	$map = array( 27 => 1127, 50 => 1150, 28 => 0 );
	return array_key_exists( (int) $id, $map ) ? $map[ (int) $id ] : $id;
} );
$wpml440 = topersia_g_published( array( 27, 50, 28 ) );
unset( $GLOBALS['tp_filters']['wpml_current_language'], $GLOBALS['tp_filters']['wpml_object_id'] );
/* ۴٫۴٫۱: دروازه شناسۀ نسخۀ زبان جاری را برمی‌گرداند (۲۷ ← ۱۱۲۷)، هر موجودیت یک بار — assert-441.php */
t( 'منتشرشده: با WPML فقط موردی که نسخهٔ منتشرشدهٔ زبان جاری دارد — ترجمهٔ پیش‌نویس یا بی‌ترجمه، نه؛ شناسهٔ همان نسخه  P5-D247',
	array( 1127 ) === $wpml440 && array( 27, 50, 28 ) === topersia_g_published( array( 27, 50, 28 ) ) );

/* هوش مصنوعی: شناسهٔ پست منتشرنشده پیش از هر کش و سقف ۴۰۴ می‌گیرد */
if ( ! class_exists( 'TP440_Json_Exit' ) ) {
	class TP440_Json_Exit extends Exception {
		public $payload;
		public function __construct( $payload ) {
			parent::__construct( 'wp_send_json' );
			$this->payload = $payload;
		}
	}
}
if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) { return 1; }
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
	/* وردپرس پس از فرستادن می‌میرد؛ این‌جا استثنا همان پایان را می‌سازد */
	function wp_send_json_error( $data = null, $status_code = null, $flags = 0 ) {
		throw new TP440_Json_Exit( array( 'ok' => false, 'data' => $data, 'status' => $status_code ) );
	}
}
$gate440 = array();
$GLOBALS['tp_user'] = 7;
$GLOBALS['tp_is_admin'] = true;   /* P5-D358: تولید فقط مدیر؛ دروازهٔ «منتشرشده» برای مدیر سنجیده می‌شود */
foreach ( array( 'draft', 'private', 'pending', 'password' ) as $why ) {
	$_POST = array( 'post' => (string) $hid440[ $why ][0], 'nonce' => 'x' );
	try {
		topersia_g_ai_gate();
		$gate440[ $why ] = 'رد نشد';
	} catch ( TP440_Json_Exit $e ) {
		$gate440[ $why ] = $e->payload['status'];
	}
}
$GLOBALS['tp_is_admin'] = false;
$gate440_na = array();
foreach ( array( 'draft', 'published' ) as $why ) {
	$_POST = array( 'post' => (string) ( 'draft' === $why ? $hid440['draft'][0] : 20 ), 'nonce' => 'x' );
	try {
		topersia_g_ai_gate();
		$gate440_na[ $why ] = 'رد نشد';
	} catch ( TP440_Json_Exit $e ) {
		$gate440_na[ $why ] = $e->payload['status'];
	}
}
$_POST = array();
$GLOBALS['tp_user'] = 0;
t( 'AI: تولید برای غیرمدیر بسته است — کاربر واردشدهٔ عادی ۴۰۳ می‌گیرد (پست منتشرشده هم)، پیش از کش و سقف  P5-D358',
	array( 'draft' => 403, 'published' => 403 ) === $gate440_na, wp_json_encode( $gate440_na ) );
t( 'منتشرشده: دروازهٔ هوش مصنوعی شناسهٔ پیش‌نویس، خصوصی، در انتظار و رمزدار را ۴۰۴ می‌دهد — پیش از کش و سقف  P5-D247',
	array( 'draft' => 404, 'private' => 404, 'pending' => 404, 'password' => 404 ) === $gate440, wp_json_encode( $gate440 ) );

list( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], $GLOBALS['tp_rel'] ) = $keep440;

$rest440 = array();
foreach ( glob( TP_PLUGIN_DIR . 'includes/*.php' ) as $f ) {
	$rest440 = array_merge( $rest440, preg_match_all( "/register_rest_route\( 'topersia\/v1', '([^']+)'/", file_get_contents( $f ), $rm ) ? $rm[1] : array() );
}
t( 'منتشرشده: تنها REST‌های هستهٔ نمایش `/weather` و `/air` (مختصات) و `/map-points` (شناسهٔ پستِ منتشرشده؛ فقط منتشرشده برمی‌گرداند) است — شناسهٔ پستی نیست که نشت کند  P5-D247 · P5-D345',
	array( '/air', '/map-points', '/weather' ) === ( function () use ( $rest440 ) { $r = $rest440; sort( $r ); return $r; } )()
	&& (bool) preg_match( "/'args'\s*=> array\(\s*'coords' => array\( 'required' => true, 'type' => 'string' \),\s*\),/", file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-weather.php' ) )
	&& (bool) preg_match( "/'args'\s*=> array\(\s*'lat' => array\( 'required' => true, 'type' => 'string' \),\s*'lng' => array\( 'required' => true, 'type' => 'string' \),\s*\),/", file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-air.php' ) ) );

/* ══ ۱٫۹ — «ۀ» یکپارچه  P5-D248 ═════════════════════════════════════════ */

exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $root440 . '/scripts/tp-heh-fix.php' ) . ' --check 2>&1', $heh_out, $heh_code );
$heh440 = json_decode( (string) end( $heh_out ), true );
t( 'ۀ: هیچ «هٔ» دونویسه در کدِ هیچ افزونه، مولد، خروجی ساخته‌شده یا کاتالوگ ترجمه نمانده؛ فهرست استثنا بسته و خالی  P5-D248',
	0 === $heh_code && is_array( $heh440 ) && 0 === $heh440['pending'] && array() === $heh440['excepted'],
	implode( ' ', $heh_out ) );

$gen440 = file_get_contents( $root440 . '/scripts/tp-template-gen.php' );
t( 'ۀ: مولد در پایان «هٔ»ِ توضیح‌های منتقل‌شده از مرجع را یکی می‌کند؛ خودِ مرجع دست نمی‌خورد  P5-D248',
	false !== strpos( $gen440, '$final = str_replace( "\u{0647}\u{0654}", "\u{06C0}", $final );' )
	&& false === strpos( $css440, "\u{0647}\u{0654}" ) );

/* ۴٫۴٫۱: جست‌وجوی پنجرۀ فهرست از جدول قاعدۀ هسته می‌خواند (P5-D265) — ادعای تاکردن «ۀ» و «هٔ» در assert-441.php */

/* ══ ۱٫۱۰ — خلاصه‌ساز، پروژهٔ سوم ═══════════════════════════════════════
   مجموعهٔ خودش اجرا می‌شود. افتادن‌های پیش از ورود به workspace فهرست
   بسته‌اند و فقط گزارش می‌شوند؛ هر افتادنِ تازه، یا هر افتادن در گروه
   فاز ۱، این ادعا را می‌اندازد. */
$ai_known440 = array();   /* ۱٫۲٫۳: مجموعهٔ خلاصه‌ساز کاملاً سبز است؛ فهرست افتادن‌های شناخته‌شده خالی */
exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $root440 . '/plugin/topersia-ai-summarizer/tests/run.php' ) . ' 2>&1', $ai_out440 );
$ai_group440 = '';
$ai_new440   = array();
$ai_p1_ok440 = 0;
foreach ( $ai_out440 as $line ) {
	if ( preg_match( '/^== (.+) ==$/', $line, $gm ) ) {
		$ai_group440 = $gm[1];
		continue;
	}
	if ( 'Phase 1 / 4.4.0' === $ai_group440 && 0 === strpos( $line, '  OK  ' ) ) {
		$ai_p1_ok440 ++;
	}
	if ( 0 === strpos( $line, '  FAIL ' ) ) {
		$label = preg_replace( '/\s{3}\[.*\]$/s', '', substr( $line, 7 ) );
		if ( 'Phase 1 / 4.4.0' === $ai_group440 || ! in_array( $label, $ai_known440, true ) ) {
			$ai_new440[] = $label;
		}
	}
}
t( 'خلاصه‌ساز: meta_facts فقط فیلد card/details با برچسب واژه‌نامه — «Copied Media Ids: a:0:{}» ناممکن؛ فقط منتشرشده؛ نمایهٔ post_language یک‌بار حذف  ۱٫۱۰',
	$ai_p1_ok440 > 0 && array() === $ai_new440, implode( ' · ', $ai_new440 ) );

/* ══ نسخه‌ها ═══ ۴٫۴٫۱: ادعای نسخه — هر چهار پروژه، هسته در سه جا — در assert-441.php */
