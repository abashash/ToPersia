<?php
/**
 * مولد `:where()` — جزء عنصری در `topersia-tokens.css` وزن نگیرد.  P5-D205
 *
 * چرا لازم شد: تیتر سربرگ روی سایت رنگ `--tp-text-strong` می‌گرفت نه سفید.
 * مقصر یک قاعدهٔ خودمان بود، نه افزونهٔ دیگری:
 *
 *   html:root .tp-shell h1 { color:var(--tp-text-strong) }        (۰,۲,۲)
 *   .tp-page .tp-hero h1   { color:var(--tp-on-media) }           (۰,۲,۱)
 *
 * لایهٔ توکن از لایهٔ ناحیه سنگین‌تر بود. `P5-D201` این را نمی‌گرفت چون
 * آن‌جا حریف بیرونی بود؛ این‌جا حریف خودمانیم.
 *
 * درمان: جزء عنصری داخل `:where()` می‌رود و وزنش صفر می‌شود. مقصودِ
 * قاعده — «تیترهای داخل پوسته این‌طور باشند» — دست‌نخورده می‌ماند، ولی
 * دیگر جلوی قاعدهٔ مشخص‌ترِ ناحیه را نمی‌گیرد.
 *
 * `html` پیشرو دست نمی‌خورد: همان است که وزن را از تم بالا می‌برد.
 * شبه‌عنصر (`::before` / `::after`) را نمی‌شود در `:where()` گذاشت و در
 * فهرست استثنا می‌ماند.
 *
 * خودتکرار است: سلکتوری که جزء عنصریِ بی‌پوشش ندارد، دوباره عوض نمی‌شود.
 *
 * اجرا:  php scripts/tp-where-gen.php            ← فقط گزارش و ادعا
 *        php scripts/tp-where-gen.php --write    ← در فایل می‌نویسد
 */

$ROOT = dirname( __DIR__ );
$CSS  = $ROOT . '/plugin/topersia-render/assets/topersia-tokens.css';

/* ── ابزار ──────────────────────────────────────────────────────── */

if ( ! function_exists( 'tp_split_selectors' ) ) {
	/** کاماهای سطح صفر — کامای داخل `:where(a,b)` را نمی‌شکند. */
	function tp_split_selectors( $list ) {

		$parts = array();
		$depth = 0;
		$buf   = '';
		$len   = strlen( $list );

		for ( $i = 0; $i < $len; $i++ ) {

			$ch = $list[ $i ];

			if ( '(' === $ch || '[' === $ch ) { $depth ++; }
			elseif ( ')' === $ch || ']' === $ch ) { $depth --; }

			if ( ',' === $ch && 0 === $depth ) {
				$parts[] = $buf;
				$buf = '';
				continue;
			}

			$buf .= $ch;
		}

		$parts[] = $buf;

		return $parts;
	}
}

/** محتوای `:where()` وزن ندارد — برای سنجش کنارش می‌گذاریم. */
function tp_strip_where( $sel ) {
	$prev = '';
	while ( $prev !== $sel ) {
		$prev = $sel;
		$sel = preg_replace( '/:where\((?:[^()]|\([^()]*\))*\)/', ' ', $sel );
	}
	return $sel;
}

/** وزن سه‌تایی — شناسه، کلاس/صفت/شبه‌کلاس، عنصر/شبه‌عنصر. */
function tp_weigh( $sel ) {

	$t = preg_replace( '/\[[^\]]*\]/', ' [] ', tp_strip_where( $sel ) );

	preg_match_all( '/#[\w-]+/', $t, $m1 );
	preg_match_all( '/\.[\w-]+/', $t, $m2 );
	preg_match_all( '/\[\]/', $t, $m3 );
	preg_match_all( '/:(?!:)[\w-]+/', $t, $m4 );
	preg_match_all( '/(^|[\s>+~])[a-zA-Z][\w-]*/', $t, $m5 );
	preg_match_all( '/::[\w-]+/', $t, $m6 );

	return array(
		count( $m1[0] ),
		count( $m2[0] ) + count( $m3[0] ) + count( $m4[0] ),
		count( $m5[0] ) + count( $m6[0] ),
	);
}

/**
 * جزءهای عنصریِ بی‌پوشش یک سلکتور — جز `html` پیشرو و شبه‌عنصرها.
 *
 * همین تابع هم در مولد کار می‌کند هم در `assert.php`؛ دو پیاده‌سازی از
 * یک قاعده یعنی روزی یکی عوض می‌شود و آن یکی نه.
 */
function tp_bare_elements( $sel ) {

	/*
	 * محتوای شبه‌کلاس‌های تابعی کنار گذاشته می‌شود — نه فقط `:where()`.
	 *
	 * `:is(input[type="search"],input[type="text"])` وزنِ مشخص‌ترین عضوش
	 * را می‌گیرد و نمی‌شود عنصرِ داخلش را جدا پیچید بی‌آنکه وزن صفت هم
	 * صفر شود. پس «بی‌پوشش» یعنی عنصرِ بیرون از این‌ها.
	 */
	$t    = $sel;
	$prev = '';
	while ( $prev !== $t ) {
		$prev = $t;
		$t = preg_replace( '/:(?:where|is|not|has)\((?:[^()]|\([^()]*\))*\)/i', ' ', $t );
	}

	$t = preg_replace( '/::[\w-]+/', ' ', $t );      /* شبه‌عنصر استثناست */
	$t = preg_replace( '/\[[^\]]*\]/', ' ', $t );
	$t = preg_replace( '/^\s*html\b/', ' ', $t );    /* html پیشرو می‌ماند */

	preg_match_all( '/(^|[\s>+~])([a-zA-Z][\w-]*)/', $t, $m );

	return $m[2];
}

/**
 * پوششِ `:where()` را برمی‌دارد — برای مقایسه با اصل.
 *
 * ادعای اصلیِ مولد روی همین می‌نشیند: اگر پوشش‌ها را برداریم باید دقیقاً
 * به سلکتور اولیه برسیم. یعنی فقط پوشش اضافه شده، نه چیز دیگری.
 */
function tp_unwrap_marker( $sel ) {
	$prev = '';
	while ( $prev !== $sel ) {
		$prev = $sel;
		$sel = preg_replace( '/:where\(([a-zA-Z][\w-]*)\)/', '$1', $sel );
	}
	return $sel;
}

/**
 * جزء عنصری را در `:where()` می‌پیچد.
 *
 * فقط نامِ عنصر پیچیده می‌شود، نه بقیهٔ جزء: `button[aria-pressed]` به
 * `:where(button)[aria-pressed]` می‌رود، نه به `:where(button[aria-pressed])`
 * — وگرنه وزنِ صفت هم صفر می‌شد و رفتار عوض می‌شد.
 */
function tp_wrap_elements( $sel ) {

	$out   = '';
	$len   = strlen( $sel );
	$i     = 0;
	$first = true;

	while ( $i < $len ) {

		/* `:where(...)` و `:is(...)` و `[...]` دست‌نخورده رد می‌شوند */
		if ( ':' === $sel[ $i ] && preg_match( '/^:(where|is|not|has)\(/i', substr( $sel, $i ), $mm ) ) {
			$depth = 0;
			$start = $i;
			while ( $i < $len ) {
				if ( '(' === $sel[ $i ] ) { $depth ++; }
				elseif ( ')' === $sel[ $i ] ) { $depth --; if ( 0 === $depth ) { $i ++; break; } }
				$i ++;
			}
			$out .= substr( $sel, $start, $i - $start );
			$first = false;
			continue;
		}

		if ( '[' === $sel[ $i ] ) {
			$end = strpos( $sel, ']', $i );
			$end = ( false === $end ) ? $len : $end + 1;
			$out .= substr( $sel, $i, $end - $i );
			$i = $end;
			continue;
		}

		/* شبه‌عنصر — نمی‌شود در :where() گذاشت */
		if ( ':' === $sel[ $i ] && $i + 1 < $len && ':' === $sel[ $i + 1 ] ) {
			preg_match( '/^::[\w-]+/', substr( $sel, $i ), $pm );
			$out .= $pm[0];
			$i += strlen( $pm[0] );
			continue;
		}

		/* شبه‌کلاس */
		if ( ':' === $sel[ $i ] ) {
			preg_match( '/^:[\w-]+/', substr( $sel, $i ), $pm );
			$out .= $pm[0];
			$i += strlen( $pm[0] );
			continue;
		}

		/* آغازِ یک جزء: آیا با نام عنصر شروع می‌شود؟ */
		$at_start = $first || preg_match( '/[\s>+~]$/', $out );

		if ( $at_start && preg_match( '/^[a-zA-Z][\w-]*/', substr( $sel, $i ), $em ) ) {

			$name = $em[0];
			$i   += strlen( $name );

			/* `html` پیشرو می‌ماند — وزنش عمدی است */
			if ( $first && 'html' === strtolower( $name ) ) {
				$out .= $name;
			} else {
				$out .= ':where(' . $name . ')';
			}

			$first = false;
			continue;
		}

		$out  .= $sel[ $i ];
		if ( ! preg_match( '/\s/', $sel[ $i ] ) ) { $first = false; }
		$i ++;
	}

	return $out;
}

/* ── پیمایش ─────────────────────────────────────────────────────── */

function tp_where_css( $src ) {

	$out       = '';
	$i         = 0;
	$len       = strlen( $src );
	$depth     = 0;
	$in_frames = 0;
	$prelude   = '';
	$stat      = array( 'wrapped' => 0, 'clean' => 0, 'at_rules' => 0 );

	while ( $i < $len ) {

		$ch = $src[ $i ];

		if ( '/' === $ch && $i + 1 < $len && '*' === $src[ $i + 1 ] ) {
			$end = strpos( $src, '*/', $i + 2 );
			$end = ( false === $end ) ? $len : $end + 2;
			$prelude .= substr( $src, $i, $end - $i );
			$i = $end;
			continue;
		}

		if ( '{' === $ch ) {

			$cut    = strrpos( $prelude, '*/' );
			$before = ( false === $cut ) ? '' : substr( $prelude, 0, $cut + 2 );
			$sels   = ( false === $cut ) ? $prelude : substr( $prelude, $cut + 2 );
			$head   = trim( $sels );

			if ( $in_frames > 0 ) {
				$out .= $prelude . '{';
			} elseif ( '' !== $head && '@' === $head[0] ) {
				if ( 0 === stripos( $head, '@keyframes' ) ) { $in_frames = $depth + 1; }
				$out .= $prelude . '{';
				$stat['at_rules'] ++;
			} else {

				$parts = tp_split_selectors( $sels );

				foreach ( $parts as $k => $sel ) {
					if ( '' === trim( $sel ) ) { continue; }
					if ( tp_bare_elements( $sel ) ) {
						$lead = '';
						if ( preg_match( '/^(\s*)/', $sel, $lm ) ) { $lead = $lm[1]; }
						$parts[ $k ] = $lead . tp_wrap_elements( ltrim( $sel ) );
						$stat['wrapped'] ++;
					} else {
						$stat['clean'] ++;
					}
				}

				$out .= $before . implode( ',', $parts ) . '{';
			}

			$depth ++;
			$prelude = '';
			$i ++;
			continue;
		}

		if ( '}' === $ch ) {
			$out .= $prelude . '}';
			$prelude = '';
			$depth --;
			if ( $in_frames > 0 && $depth < $in_frames ) { $in_frames = 0; }
			$i ++;
			continue;
		}

		$prelude .= $ch;
		$i ++;
	}

	$out .= $prelude;

	return array( $out, $stat );
}

/** هر سلکتوری که هنوز جزء عنصریِ بی‌پوشش دارد. */
function tp_unwrapped( $css ) {

	$found = array();

	$probe = preg_replace( '/\/\*[\s\S]*?\*\//', '', $css );
	$probe = preg_replace( '/@keyframes[^{]*\{(?:[^{}]|\{[^{}]*\})*\}/i', '', $probe );

	if ( ! preg_match_all( '/([^{}]*)\{/', $probe, $mm ) ) { return $found; }

	foreach ( $mm[1] as $head ) {
		$head = trim( $head );
		if ( '' === $head || '@' === $head[0] ) { continue; }
		foreach ( tp_split_selectors( $head ) as $sel ) {
			if ( '' === trim( $sel ) ) { continue; }
			if ( tp_bare_elements( $sel ) ) { $found[] = trim( $sel ); }
		}
	}

	return $found;
}

if ( defined( 'TP_WHERE_LIB' ) ) { return; }

/* ── اجرا ───────────────────────────────────────────────────────── */

if ( ! is_file( $CSS ) ) {
	fwrite( STDERR, "شیوه‌نامه پیدا نشد: {$CSS}\n" );
	exit( 2 );
}

$src = file_get_contents( $CSS );
list( $out, $stat ) = tp_where_css( $src );

$bad = array();

if ( substr_count( $out, '{' ) !== substr_count( $src, '{' ) ) {
	$bad[] = 'شمار قاعده عوض شد';
}
if ( substr_count( $out, '{' ) !== substr_count( $out, '}' ) ) {
	$bad[] = 'آکولاد نامتوازن';
}
/*
 * برداشتنِ پوشش باید دقیقاً به فایل اولیه برگرداند.
 *
 * قوی‌ترین ادعای این مولد: ثابت می‌کند تنها کاری که کرده افزودنِ
 * `:where()` بوده — نه جابه‌جایی، نه حذف، نه بازنویسیِ اعلان.
 */
if ( tp_unwrap_marker( $out ) !== tp_unwrap_marker( $src ) ) {
	$bad[] = 'برداشتن پوشش، دو طرف را یکی نمی‌کند';
}

$left = tp_unwrapped( $out );
if ( $left ) {
	$bad[] = 'بی‌پوشش ماند: ' . implode( ' · ', array_slice( $left, 0, 4 ) );
}

list( $twice, ) = tp_where_css( $out );
if ( $twice !== $out ) { $bad[] = 'خودتکرار نیست'; }

/* هیچ سلکتوری نباید وزن عنصری بیش از یک بگیرد (همان `html` پیشرو) */
$heavy = array();
foreach ( tp_split_selectors( '' ) ? array() : array() as $x ) { /* no-op */ }
$probe = preg_replace( '/\/\*[\s\S]*?\*\//', '', $out );
if ( preg_match_all( '/([^{}]*)\{/', $probe, $mm ) ) {
	foreach ( $mm[1] as $head ) {
		$head = trim( $head );
		if ( '' === $head || '@' === $head[0] ) { continue; }
		foreach ( tp_split_selectors( $head ) as $sel ) {
			if ( '' === trim( $sel ) ) { continue; }
			$w = tp_weigh( $sel );
			if ( $w[2] > 1 ) { $heavy[] = trim( $sel ) . ' ' . implode( ',', $w ); }
		}
	}
}

echo "\n:where() روی جزء عنصریِ tokens.css — P5-D205\n", str_repeat( '─', 78 ), "\n";
printf( "  پیچیده شد: %d   از قبل تمیز: %d   at-rule: %d\n",
	$stat['wrapped'], $stat['clean'], $stat['at_rules'] );
printf( "  اندازه: %s → %s بایت\n", number_format( strlen( $src ) ), number_format( strlen( $out ) ) );

if ( $heavy ) {
	printf( "  وزن عنصریِ بیش از ۱ (شبه‌عنصر): %d\n", count( $heavy ) );
	foreach ( array_slice( $heavy, 0, 4 ) as $h ) { printf( "    · %s\n", $h ); }
}

if ( $bad ) {
	echo str_repeat( '─', 78 ), "\n";
	foreach ( $bad as $b ) { printf( "  ❌ %s\n", $b ); }
	fwrite( STDERR, "ادعاها رد شدند — چیزی نوشته نشد.\n" );
	exit( 1 );
}

echo "  ✅ همهٔ ادعاها\n";

if ( in_array( '--write', $argv, true ) ) {
	file_put_contents( $CSS, $out );
	echo "  نوشته شد: ", basename( $CSS ), "\n";
} else {
	echo "  (چیزی نوشته نشد — با --write بنویس)\n";
}

exit( 0 );
