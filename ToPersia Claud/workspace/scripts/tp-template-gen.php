<?php
/**
 * مولد شیوه‌نامۀ قالب — P5-D219
 *
 * `topersia-g-template.css` از روی `<style>`های خود فایل مرجع ساخته می‌شود،
 * نه از روی توصیفش. هیچ قاعده‌ای دستی نوشته نمی‌شود، پس هیچ قاعده‌ای هم
 * جا نمی‌ماند. تبدیل‌ها همه مکانیکی‌اند و هر کدام یک ادعای آزمون دارد.
 *
 *   php scripts/tp-template-gen.php            ← می‌نویسد
 *   php scripts/tp-template-gen.php --check    ← فقط می‌گوید فرق دارد یا نه
 *   php scripts/tp-template-gen.php --map      ← جدول نگاشت متغیرها
 *
 * چه می‌کند:
 *   ۱ پنج بلوک <style> سند اول مرجع، به همان ترتیب
 *   ۲ کلاس‌ها و شناسه‌ها پیشوند `tp-` می‌گیرند
 *   ۳ متغیرهای مرجع به توکن نگاشت می‌شوند (جدول پایین)
 *   ۴ بلوک‌های متغیرِ `:root` و `[data-theme="dark"]` حذف — سه حالت از توکن
 *   ۵ هر قاعده زیر `.tp-page`، جز @font-face و @keyframes
 *   ۶ ناحیه‌های منتقل‌نشده حذف: .top · .foot · .skip
 *   ۷ یک بلوک ریست در ابتدا — تنها چیز افزوده
 */

$ROOT = dirname( __DIR__ );
$REF  = $ROOT . '/reference/ToPersia-G3-Province-Template.html';
$OUT  = $ROOT . '/plugin/topersia-render/assets/topersia-g-template.css';

/* ---------------------------------------------------------------------------
   جدول نگاشت متغیر — مرجع ← توکن
   «هم‌مقدار» به معنای خودِ خواسته: توکنی که مقدارش در حالت روز با مرجع یکی
   است. پالت پروژه از همین مرجع درآمده، و برای بیشترِ متغیرها توکن هم‌مقدار
   نه فقط در روز که در شب هم یکی است:

     --bg --surface --ink --ink-strong --muted --line     روز ✓  شب ✓
     --brand  ← --tp-hover         #177E7A / #2EB8AF      روز ✓  شب ✓
     --gold   ← --tp-accent-strong #B9871A / #D4A63D      روز ✓  شب ✓

   نام نقشِ `--tp-hover` با «برند» یکی نیست، ولی خواسته «هم‌مقدار» بود و
   مقدارش در هر دو حالت دقیقاً رنگ برند مرجع است. `--tp-primary` روشن‌تر است
   (#1C9E9A) و متن سفید رویش ۳٫۲۸ می‌داد.
   --------------------------------------------------------------------------- */
$VARS = array(
	'bg'         => 'tp-bg',
	'bg-2'       => 'tp-bg-2',
	'surface'    => 'tp-surface',
	'surface-2'  => 'tp-surface-2',
	'surface-3'  => 'tp-surface-3',
	'ink'        => 'tp-text',
	'ink-strong' => 'tp-text-strong',
	'muted'      => 'tp-text-2',
	'muted-2'    => 'tp-text-3',
	'line'       => 'tp-border',
	'line-2'     => 'tp-border-2',
	'brand'      => 'tp-hover',
	'brand-2'    => 'tp-brand-2',
	'brand-soft' => 'tp-soft',
	'gold'       => 'tp-accent-strong',
	'gold-2'     => 'tp-accent-soft',
	'gold-ink'   => 'tp-accent-text',
	'ok'         => 'tp-ok',
	'shadow-sm'  => 'tp-g-shadow-sm',
	'shadow'     => 'tp-g-shadow',
	'r-sm'       => 'tp-r-sm',
	'r'          => 'tp-r',
	'r-lg'       => 'tp-r-lg',
	'r-xl'       => 'tp-r-xl',
	'content-w'  => 'tp-content-w',
	'rail-w'     => 'tp-rail-w',

	's-spring'      => 'tp-s-spring',
	's-spring-soft' => 'tp-s-spring-soft',
	's-summer'      => 'tp-s-summer',
	's-summer-soft' => 'tp-s-summer-soft',
	's-autumn'      => 'tp-s-autumn',
	's-autumn-soft' => 'tp-s-autumn-soft',
	's-winter'      => 'tp-s-winter',
	's-winter-soft' => 'tp-s-winter-soft',
	's'             => 'tp-s',
	's-soft'        => 'tp-s-soft',
	'w'             => 'tp-w',
);

/*
 * نگاشتِ ویژۀ «رنگ متن» — فقط وقتی ویژگی دقیقاً `color` است.
 *
 * پروژه برای رنگ‌هایی که هم پر می‌شوند و هم متن می‌گیرند دو توکن دارد:
 * پایه برای پر و مرز، و `-text` برای متن، روی نردبان روشنایی پایین‌تر
 * (P5-D153 · P5-D188). مرجع یک متغیر برای هر دو داشت. همان قاعدۀ
 * پروژه اینجا مکانیکی اعمال می‌شود: `color:var(--brand)` به
 * `--tp-primary-text` می‌رود و `background:var(--brand)` به `--tp-hover`.
 *
 * شبِ این توکن‌های متن عیناً همان مقدار شبِ مرجع است (#7AD4CD · #91C8A4 ·
 * #D4A63D) — مرجع همین جدایی را در شب داشت، فقط در روز نداشت.
 */
$TEXT_VARS = array(
	'brand'   => 'tp-primary-text',
	'ok'      => 'tp-success-text',
	'gold'    => 'tp-accent-text',
	'muted-2' => 'tp-text-2',
	's'       => 'tp-s-text',
);

/*
 * متنِ سفید روی پرِ برند — `--tp-on-hover`.  P5-D191
 *
 * قاعدۀ پروژه: هر پرِ `--tp-X` رنگ متنش را صریح از `--tp-on-X` می‌گیرد.
 * در روز `--tp-on-hover` خودِ #FFFFFF است، پس روز پیکسل‌به‌پیکسل همان
 * مرجع می‌ماند. در شب #11161B است — همان کاری که مرجع خودش برای دو
 * مورد در شب کرده بود (`color:#0b1310`) و برای بقیه جا انداخته بود.
 *
 * درون یک قاعده مکانیکی است: اگر پسِ‌زمینه از `--brand` یا `--brand-2`
 * بیاید، `color:#fff` همان قاعده به `--tp-on-hover` می‌رود. این سه
 * انتخابگر پر را از قاعدۀ دیگری (هاورِ والد) می‌گیرند و نامشان صریح است:
 */
$ON_FILL_DESC = array(
	'.tp-gps-btn:hover .tp-gps-btn__t,.tp-gps-btn:hover .tp-gps-btn__s',
	'.neighbors a:hover .n-dir',
	'.routes a:hover span',
);

/*
 * متن ریزِ کم‌رنگ روی سطح دوم — `--tp-text`.  P5-D184
 *
 * مرجع برچسب‌های ریز (۹٫۶ تا ۱۲ پیکسل) را کم‌رنگ روی `--surface-2`
 * می‌نشاند: #6F6B64 روی #efe9dd، ۴٫۳۸ در روز و ۴٫۱۰ در غروب — زیر ۴٫۵.
 * روی `--surface` همان رنگ ۴٫۹ می‌دهد و درست است؛ فقط این چهار قاعده
 * روی سطح دوم می‌نشینند. قاعدۀ پروژه برای همین حالت هست و همان اعمال
 * می‌شود، نه رنگ تازه.
 */
$SMALL_ON_2 = array(
	'.geo-grid .fact span',
	'.practical-grid a span',
	'.neighbors .n-dir',
	'.routes a span',
);

/*
 * برخوردِ نام — تنها استثنای قاعدۀ «.x ← .tp-x».  P5-D219
 *
 * مرجع دو کلاسِ جدا داشت: `.more` (پیوند متنیِ سرفصل، «همه تصاویر ←») و
 * `.tp-more` (دکمۀ «N مورد دیگر» در کارت‌ها، که نویسندۀ مرجع خودش
 * پیشوندش داده بود). پیشوند مکانیکی هر دو را `.tp-more` می‌کرد و شعاع ۱۰
 * و فاصلۀ بالای دکمۀ کارت روی «همه تصاویر ←» می‌نشست — آزمون پذیرش
 * گرفتش. `.more` همان `.tp-more` می‌ماند (خواستۀ صریح) و کلاسِ ازپیش‌
 * پیشونددارِ مرجع نام تازه می‌گیرد.
 *
 * مولد هر برخوردِ دیگری را خودش پیدا می‌کند و می‌ایستد؛ این جدول تنها
 * جایی است که می‌شود حلش کرد.
 */
$COLLIDE = array(
	'tp-more' => 'tp-more-rest',
);

/* ناحیه‌هایی که منتقل نمی‌شوند — هدر و پاورقی خودِ مرجع. */
$DROP = array( 'top', 'top-in', 'logo', 'icon-btn', 'foot', 'foot__in', 'skip' );

/* ---------------------------------------------------------------------------
   ۲ — تجزیه‌گر کوچک: فهرستی از گره‌ها
   --------------------------------------------------------------------------- */
function tp_css_nodes( $css ) {

	$nodes = array();
	$i     = 0;
	$len   = strlen( $css );
	$buf   = '';
	$par   = 0;   /* ژرفای پرانتز — `;` داخل url(...) جداکننده نیست */
	$q     = '';  /* نقل‌قول باز */

	while ( $i < $len ) {

		$c = $css[ $i ];

		/* داخل نقل‌قول هیچ نشانه‌ای معنا ندارد */
		if ( '' !== $q ) {
			$buf .= $c;
			if ( $c === $q && '\\' !== $css[ $i - 1 ] ) { $q = ''; }
			$i ++;
			continue;
		}

		if ( '"' === $c || "'" === $c ) {
			$q    = $c;
			$buf .= $c;
			$i ++;
			continue;
		}

		if ( '(' === $c ) { $par ++; }
		if ( ')' === $c && $par > 0 ) { $par --; }

		/* توضیح — دست‌نخورده نگه داشته می‌شود */
		if ( '/' === $c && $i + 1 < $len && '*' === $css[ $i + 1 ] ) {
			$end     = strpos( $css, '*/', $i + 2 );
			$end     = ( false === $end ) ? $len : $end + 2;
			$nodes[] = array( 'kind' => 'comment', 'text' => substr( $css, $i, $end - $i ) );
			$i       = $end;
			continue;
		}

		if ( ';' === $c && 0 === $par && '' !== trim( $buf ) ) {
			$nodes[] = array( 'kind' => 'stmt', 'text' => trim( $buf ) . ';' );
			$buf     = '';
			$i ++;
			continue;
		}

		if ( '{' === $c ) {

			$sel   = trim( $buf );
			$buf   = '';
			$depth = 1;
			$j     = $i + 1;

			while ( $j < $len && $depth > 0 ) {
				if ( '{' === $css[ $j ] ) { $depth ++; }
				if ( '}' === $css[ $j ] ) { $depth --; }
				if ( $depth > 0 ) { $j ++; }
			}

			$body = substr( $css, $i + 1, $j - $i - 1 );

			$nodes[] = array(
				'kind' => ( 0 === strpos( $sel, '@' ) ) ? 'at' : 'rule',
				'sel'  => $sel,
				'body' => $body,
			);

			$i = $j + 1;
			continue;
		}

		$buf .= $c;
		$i ++;
	}

	return $nodes;
}

/* ---------------------------------------------------------------------------
   ۳ — تبدیل‌های تک‌به‌تک
   --------------------------------------------------------------------------- */

/**
 * کلاس و شناسه پیشوند می‌گیرند — آنچه از قبل `tp-` دارد دوباره نمی‌گیرد.
 *
 * کلاسِ ازپیش‌پیشونددارِ مرجع که با پیشوندِ کلاسی دیگر برخورد می‌کند، از
 * `$COLLIDE` نام تازه‌اش را می‌گیرد.
 */
function tp_prefix_selector( $sel ) {

	$collide = isset( $GLOBALS['COLLIDE'] ) ? $GLOBALS['COLLIDE'] : array();

	return preg_replace_callback( '/([.#])([A-Za-z_][A-Za-z0-9_-]*)/', function ( $m ) use ( $collide ) {
		if ( '.' === $m[1] && isset( $collide[ $m[2] ] ) ) {
			return '.' . $collide[ $m[2] ];
		}
		return ( 0 === strpos( $m[2], 'tp-' ) ) ? $m[0] : $m[1] . 'tp-' . $m[2];
	}, $sel );
}

/**
 * برخوردهای پیشوند را پیدا می‌کند: کلاسِ `x` که `tp-x`اش هم در مرجع هست.
 *
 * @return string[] برخوردهایی که در `$COLLIDE` نیامده‌اند.
 */
function tp_unresolved_collisions( $css, $collide, $drop = array() ) {

	$body = preg_replace( '#/\*.*?\*/#s', '', $css );

	preg_match_all( '/([^{}]+)\{/', $body, $heads );

	$names = array();

	foreach ( $heads[1] as $head ) {

		/* قاعده‌ای که منتقل نمی‌شود (هدر و پاورقیِ مرجع) برخوردی نمی‌سازد:
		   `.top .actions` هرگز به صفحۀ ما نمی‌رسد، پس با `.tp-actions` نمی‌جنگد. */
		if ( $drop && tp_dropped( trim( $head ), $drop ) ) {
			continue;
		}
		if ( preg_match_all( '/\.([A-Za-z_][A-Za-z0-9_-]*)/', $head, $cc ) ) {
			foreach ( $cc[1] as $one ) { $names[ $one ] = true; }
		}
	}

	$left = array();

	foreach ( array_keys( $names ) as $one ) {
		if ( 0 !== strpos( $one, 'tp-' ) && isset( $names[ 'tp-' . $one ] ) && ! isset( $collide[ 'tp-' . $one ] ) ) {
			$left[] = '.' . $one . ' ↔ .tp-' . $one;
		}
	}

	return $left;
}

/** متغیرهای مرجع به توکن. هم `var(--x)` هم اعلانِ `--x:`. */
function tp_map_vars( $text, $VARS ) {

	return preg_replace_callback( '/--([A-Za-z][A-Za-z0-9-]*)/', function ( $m ) use ( $VARS ) {

		$name = $m[1];

		if ( 0 === strpos( $name, 'tp-' ) || 0 === strpos( $name, 'wd-' ) ) {
			return $m[0];
		}

		if ( isset( $VARS[ $name ] ) ) {
			return '--' . $VARS[ $name ];
		}

		return $m[0];
	}, $text );
}

/**
 * نگاشت رنگ متن — فقط در اعلانی که ویژگی‌اش دقیقاً `color` است.
 *
 * `border-color` و `background-color` زیررشتۀ `color` را دارند؛ نام
 * ویژگی کامل سنجیده می‌شود، نه زیررشته.
 */
function tp_map_text_vars( $body, $TEXT_VARS ) {

	$decls = explode( ';', $body );

	foreach ( $decls as $i => $decl ) {

		$colon = strpos( $decl, ':' );

		if ( false === $colon ) {
			continue;
		}

		if ( 'color' !== strtolower( trim( substr( $decl, 0, $colon ) ) ) ) {
			continue;
		}

		$decls[ $i ] = preg_replace_callback( '/var\(\s*--([A-Za-z][A-Za-z0-9-]*)/', function ( $m ) use ( $TEXT_VARS ) {
			return isset( $TEXT_VARS[ $m[1] ] ) ? 'var(--' . $TEXT_VARS[ $m[1] ] : $m[0];
		}, $decl );
	}

	return implode( ';', $decls );
}

/**
 * متن سفید روی پرِ برند ← `--tp-on-hover`.  P5-D191
 *
 * پیش از نگاشت متغیرها اجرا می‌شود، چون نامِ مرجعِ `--brand` را می‌خواند.
 *
 * @param string $sel  انتخابگرِ خامِ مرجع، پیش از پیشوند
 */
function tp_map_on_fill( $sel, $body, $ON_FILL_DESC ) {

	$sel_norm = trim( preg_replace( '/\s+/', ' ', $sel ) );
	$sel_norm = preg_replace( '/\s*,\s*/', ',', $sel_norm );

	$on_brand = (bool) preg_match( '/background(-color|-image)?\s*:[^;]*var\(\s*--brand(-2)?\s*\)/', $body );
	$named    = in_array( $sel_norm, $ON_FILL_DESC, true );

	if ( ! $on_brand && ! $named ) {
		return $body;
	}

	$decls = explode( ';', $body );

	foreach ( $decls as $i => $decl ) {

		$colon = strpos( $decl, ':' );

		if ( false === $colon || 'color' !== strtolower( trim( substr( $decl, 0, $colon ) ) ) ) {
			continue;
		}

		$value = strtolower( trim( substr( $decl, $colon + 1 ) ) );

		/* پرِ هم‌قاعده: فقط سفیدِ کامل. نام‌برده: هر سفیدِ نیمه‌شفاف هم. */
		$white = in_array( $value, array( '#fff', '#ffffff' ), true )
			|| ( $named && 0 === strpos( $value, 'rgba(255,255,255' ) );

		if ( $white ) {
			$decls[ $i ] = substr( $decl, 0, $colon + 1 ) . 'var(--tp-on-hover)';
		}
	}

	return implode( ';', $decls );
}

/** متن ریزِ کم‌رنگ روی سطح دوم ← `--tp-text`.  P5-D184 */
function tp_map_small_on_2( $sel, $body, $SMALL_ON_2 ) {

	$sel_norm = trim( preg_replace( '/\s+/', ' ', $sel ) );

	if ( ! in_array( $sel_norm, $SMALL_ON_2, true ) ) {
		return $body;
	}

	return preg_replace( '/(^|;)(\s*color\s*:)\s*var\(\s*--(muted|muted-2)\s*\)/', '$1$2var(--tp-text)', $body );
}

/** آیا این انتخابگر دامنۀ سند است؟ */
function tp_doc_scope( $sel ) {
	return (bool) preg_match( '/^\s*(html|:root)\b/', $sel );
}

/**
 * لنگر زدن یک فهرست انتخابگر زیر `.tp-page`.
 *
 * در مرجع `body` همان ظرف صفحه بود؛ اینجا `.tp-page` است. اگر `body` را
 * `body` می‌گذاشتیم، پس‌زمینه و قلم کل سایت عوض می‌شد.
 */
function tp_anchor( $sel, $scope ) {

	$out = array();

	foreach ( explode( ',', $sel ) as $one ) {

		$one = trim( $one );

		if ( '' === $one ) {
			continue;
		}

		/* حالت شبِ مرجع → کلید سه‌حالتۀ پروژه */
		$one = str_replace(
			array( 'html[data-theme="dark"]', '[data-theme="dark"]' ),
			'html[data-tp-mode="dark"]',
			$one
		);

		if ( preg_match( '/^html\[data-tp-mode="dark"\]\s+(.+)$/', $one, $m ) ) {
			$out[] = 'html[data-tp-mode="dark"] ' . $scope . ' ' . $m[1];
			continue;
		}

		if ( tp_doc_scope( $one ) ) {
			$out[] = $one;
			continue;
		}

		/*
		 * لنگر `html .tp-page` است، نه فقط `.tp-page`.  P5-D219
		 *
		 * وودمارت **بعد از** ما بار می‌شود و دو قاعدۀ هاورِ همه‌جایی دارد:
		 *   :is(button,…):hover{color;background-color}   (۰,۲,۰)
		 *   a:hover{color:var(--wd-link-color-hover)}      (۰,۱,۱)
		 * با لنگرِ تنها، قاعده‌های مرجع دقیقاً هم‌وزن این‌ها می‌شدند و
		 * دیرتر یعنی وودمارت: متن پیوندِ مرکز استان روی هاور رنگ پیوندِ
		 * قالب را می‌گرفت. `html` یک پلۀ عنصری اضافه می‌کند — نه بیشتر،
		 * پس ترتیب درونیِ خودِ قاعده‌های مرجع دست نمی‌خورد.
		 */
		if ( 'body' === $one ) {
			$out[] = 'html ' . $scope;
			continue;
		}

		$out[] = 'html ' . $scope . ' ' . $one;
	}

	return implode( ',', $out );
}

/** آیا کل این فهرست انتخابگر به ناحیه‌های منتقل‌نشده اشاره می‌کند؟ */
function tp_dropped( $sel, $DROP ) {

	$parts = array_filter( array_map( 'trim', explode( ',', $sel ) ) );

	if ( empty( $parts ) ) {
		return false;
	}

	foreach ( $parts as $one ) {

		$hit = false;

		foreach ( $DROP as $cls ) {
			if ( preg_match( '/(^|\s|>)\.' . preg_quote( $cls, '/' ) . '($|[\s>:.\[])/', $one . ' ' ) ) {
				$hit = true;
				break;
			}
		}

		if ( ! $hit ) {
			return false;
		}
	}

	return true;
}

/** آیا بدنۀ این قاعده فقط اعلان متغیر است؟ (بلوک پالت مرجع) */
function tp_only_vars( $body ) {

	$decls = array_filter( array_map( 'trim', explode( ';', $body ) ) );

	if ( empty( $decls ) ) {
		return false;
	}

	foreach ( $decls as $d ) {
		if ( 0 !== strpos( $d, '--' ) && 0 !== strpos( $d, 'color-scheme' ) ) {
			return false;
		}
	}

	return true;
}

/**
 * قاعده‌هایی که زیر دامنه نرفته‌اند — گاردِ `assert.php` از همین می‌خواند.
 *
 * تابع از خودِ مولد می‌آید، نه کپی: دو پیاده‌سازی از یک قاعده یعنی روزی
 * یکی عوض می‌شود و آن یکی نه.  P5-D201
 *
 * `@font-face` و `@keyframes` و انتخابگرهای سطح سند از قاعده مستثنا
 * هستند — هیچ‌کدام را نمی‌شود زیر `.tp-page` برد.
 */
function tp_unanchored( $css, $scope = '.tp-page' ) {

	$left = array();
	$body = preg_replace( '#/\*.*?\*/#s', '', $css );

	/* بدنۀ @media ها هم سلکتور دارند؛ خودِ شرط سلکتور نیست. */
	$body = preg_replace( '/@(media|supports)[^{]*\{/', '', $body );

	preg_match_all( '/([^{}]+)\{[^{}]*\}/', $body, $m, PREG_SET_ORDER );

	foreach ( $m as $one ) {

		$sel = trim( preg_replace( '/\s+/', ' ', $one[1] ) );

		if ( '' === $sel || 0 === strpos( $sel, '@' ) || 0 === strpos( $sel, '}' ) ) {
			continue;
		}

		foreach ( explode( ',', $sel ) as $part ) {

			$part = trim( $part );

			if ( '' === $part ) {
				continue;
			}

			/* درصدهای @keyframes */
			if ( preg_match( '/^[0-9.%,\s]+$/', $part ) || 'from' === $part || 'to' === $part ) {
				continue;
			}

			if ( preg_match( '/^(html|:root)\b/', $part ) ) {
				continue;
			}

			if ( false === strpos( $part, $scope ) ) {
				$left[] = $part;
			}
		}
	}

	return array_values( array_unique( $left ) );
}

if ( defined( 'TP_TEMPLATE_LIB' ) ) {
	return;
}

/* ---------------------------------------------------------------------------
   ۱ — بیرون کشیدن بلوک‌های <style> از سند اول
   فایل مرجع دو سند پشت سر هم دارد؛ دومی همان اولی است منهای لایۀ داده.
   --------------------------------------------------------------------------- */
$html = file_get_contents( $REF );
$doc1 = substr( $html, 0, strpos( $html, '</body>' ) );

preg_match_all( '#<style>(.*?)</style>#s', $doc1, $m );

if ( count( $m[1] ) < 5 ) {
	fwrite( STDERR, 'مرجع کمتر از پنج بلوک <style> دارد — ' . count( $m[1] ) . "\n" );
	exit( 2 );
}

$src = implode( "\n", $m[1] );

/* برخوردِ پیشوندِ حل‌نشده یعنی دو کلاس مرجع یکی می‌شوند — نمی‌سازیم. */
$clash = tp_unresolved_collisions( $src, $COLLIDE, $DROP );

if ( $clash ) {
	fwrite( STDERR, "برخوردِ نام حل‌نشده — در \$COLLIDE بیاورش:\n  " . implode( "\n  ", $clash ) . "\n" );
	exit( 2 );
}

/* ---------------------------------------------------------------------------
   ۴ — پیمایش
   --------------------------------------------------------------------------- */
function tp_walk( $nodes, $SCOPE, $VARS, $DROP, $TEXT_VARS = array() ) {

	$out = '';

	foreach ( $nodes as $n ) {

		if ( 'comment' === $n['kind'] ) {
			$out .= $n['text'] . "\n";
			continue;
		}

		if ( 'stmt' === $n['kind'] ) {
			$out .= $n['text'] . "\n";
			continue;
		}

		if ( 'at' === $n['kind'] ) {

			$sel = $n['sel'];

			/*
			 * @font-face بیرون نمی‌آید.  ۴٫۳٫۰ بند ۱۳
			 * مرجع قلم را از نشانی ثابت topersia.com می‌خواند؛ قلم فقط از
			 * topersia-tokens (PHP) می‌آید، با نشانیِ همان میزبان.
			 */
			if ( preg_match( '/^@font-face\b/', $sel ) ) {
				continue;
			}

			/* @keyframes دست‌نخورده — لنگر نمی‌خورد */
			if ( preg_match( '/^@(keyframes|-webkit-keyframes)\b/', $sel ) ) {
				$out .= $sel . '{' . tp_map_vars( $n['body'], $VARS ) . "}\n";
				continue;
			}

			/* @media و @supports — شرط دست‌نخورده، درونش لنگر می‌خورد */
			if ( preg_match( '/^@(media|supports)\b/', $sel ) ) {

				$inner = tp_walk( tp_css_nodes( $n["body"] ), $SCOPE, $VARS, $DROP, $TEXT_VARS );

				if ( '' === trim( preg_replace( '#/\*.*?\*/#s', '', $inner ) ) ) {
					continue;
				}

				$out .= $sel . "{\n" . $inner . "}\n";
				continue;
			}

			$out .= $sel . '{' . $n['body'] . "}\n";
			continue;
		}

		/* قاعدۀ معمولی */
		$sel  = $n['sel'];
		$body = $n['body'];

		if ( tp_dropped( $sel, $DROP ) ) {
			continue;
		}

		/*
		 * حرفِ اولِ بزرگ منتقل نمی‌شود — هیچ `::first-letter`.  P5-D230
		 * روی فارسی «ا» را از واژه جدا می‌کرد و به شکل یک خط درمی‌آورد.
		 */
		if ( false !== stripos( $sel, 'first-letter' ) ) {
			continue;
		}

		/* بلوک پالت مرجع — سه حالت از توکن می‌آید، نه از اینجا */
		if ( tp_only_vars( $body ) && preg_match( '/^\s*(:root|html\[data-theme|\[data-theme)/', $sel ) ) {
			continue;
		}

		/* ترتیب مهم است: پرِ برند و رنگ متن هر دو نامِ مرجع را می‌خوانند،
		   پس پیش از نگاشت کلی متغیرها. */
		$body = tp_map_on_fill( $sel, $body, $GLOBALS['ON_FILL_DESC'] );
		$body = tp_map_small_on_2( $sel, $body, $GLOBALS['SMALL_ON_2'] );
		$body = tp_map_text_vars( $body, $TEXT_VARS );
		$body = tp_map_vars( $body, $VARS );

		$sel  = tp_prefix_selector( $sel );
		$sel  = tp_map_vars( $sel, $VARS );
		$sel  = tp_anchor( $sel, $SCOPE );

		$out .= $sel . '{' . trim( $body ) . "}\n";
	}

	return $out;
}

$css = tp_walk( tp_css_nodes( $src ), ".tp-page", $VARS, $DROP, $TEXT_VARS );

/* ---------------------------------------------------------------------------
   ۴ب — دو عددی که در مرجع ثابت بودند و اینجا نمی‌توانند باشند
   مرجع هدر چسبان خودش را داشت با ارتفاع ثابت ۶۴ (و ۵۶ روی موبایل). در سایت
   واقعی هدر مال وودمارت است و ارتفاعش با تنظیمات عوض می‌شود. پس همان عدد از
   `--tp-header-h` می‌آید که جاوااسکریپت اندازه‌گیری‌اش می‌کند.

   مقدار پیش‌فرضِ متغیر عیناً همان ۶۴ و ۵۶ مرجع است، پس در صفحۀ آزمون —
   که هدر چسبانی ندارد — عدد محاسبه‌شده دقیقاً با مرجع یکی درمی‌آید.
   --------------------------------------------------------------------------- */
$FIX = array(
	/*
	 * .snav — درست زیر پایینِ واقعیِ هدر چسبانِ وودمارت.  P5-D223
	 * عدد را جاوااسکریپت اندازه می‌گیرد (`.whb-sticked` · `.whb-clone`)،
	 * بی‌فاصله؛ پیش‌فرضش همان هدر ۶۴/۵۶ مرجع است.
	 */
	'.tp-page .tp-snav{position:sticky;top:64px;' => '.tp-page .tp-snav{position:sticky;top:var(--tp-snav-top);',
	'.tp-page .tp-snav{top:56px;'                 => '.tp-page .tp-snav{top:var(--tp-snav-top);',
	/*
	 * لنگر بخش‌ها = هدر + نوار.  P5-D223
	 * مرجع ۱۳۰ = ۶۴ + ۶۶ و ۱۲۰ = ۵۶ + ۶۴ داشت؛ حالا هر دو اندازه‌گیری
	 * می‌شوند و پیش‌فرضشان همان عددهای مرجع است.
	 */
	'scroll-margin-top:130px'                     => 'scroll-margin-top:calc(var(--tp-snav-top) + var(--tp-snav-h))',
	'scroll-margin-top:120px'                     => 'scroll-margin-top:calc(var(--tp-snav-top) + var(--tp-snav-h))',
	/* ستون کناری چسبان — ۱۲۰ = ۶۴ + ۵۶ */
	'.tp-page .tp-rail{display:grid;gap:16px;position:sticky;top:120px;'
		=> '.tp-page .tp-rail{display:grid;gap:16px;position:sticky;top:calc(var(--tp-header-h) + 56px);',
);

foreach ( $FIX as $from => $to ) {
	if ( false === strpos( $css, $from ) ) {
		fwrite( STDERR, "وصلۀ ثابت پیدا نشد — مرجع عوض شده؟\n  «{$from}»\n" );
		exit( 2 );
	}
	$css = str_replace( $from, $to, $css );
}

/* ---------------------------------------------------------------------------
   ۴ج — `rem` مرجع روی ریشۀ ۱۶ پیکسلی کشیده شده بود.  P5-D219

   افزونه خودش `html:root{font-size:calc(17px * var(--tp-scale))}` می‌گذارد
   (تنظیم «اندازۀ پایه»، پیش‌فرض ۱۷). متن بدنۀ مرجع ثابت `16px` است ولی
   سرفصل‌ها و برچسب‌ها و فاصله‌ها `rem` اند؛ روی ریشۀ ۱۷ همه‌شان ۶٫۲۵٪
   بزرگ‌تر می‌شدند و بدنه نه — نسبت‌های طرح به هم می‌ریخت، بی‌صدا.

   هر `rem` به `calc(n * var(--tp-rem))` می‌رود و `--tp-rem` خودش
   `calc(16px * var(--tp-scale))` است: در مقیاس ۱ عیناً مرجع، مستقل از
   تنظیم اندازۀ پایۀ سایت، و اندازۀ متنِ انتخابیِ خواننده همچنان همه
   را با هم بزرگ می‌کند — متن بدنه هم، که `16px` ثابت آن را منجمد می‌کرد.
   --------------------------------------------------------------------------- */
$rem_count = 0;

$css = preg_replace_callback( '/(?<![\w.-])(\d*\.?\d+)rem\b/', function ( $m ) use ( &$rem_count ) {
	$rem_count ++;
	return 'calc(' . $m[1] . ' * var(--tp-rem))';
}, $css );

$page_font = 'html .tp-page{margin:0;background:var(--tp-bg);color:var(--tp-text);';

if ( false === strpos( $css, $page_font ) || false === strpos( $css, 'line-height:1.9;font-size:16px;' ) ) {
	fwrite( STDERR, "قاعدۀ بدنۀ مرجع پیدا نشد — مرجع عوض شده؟\n" );
	exit( 2 );
}

$css = str_replace( 'line-height:1.9;font-size:16px;', 'line-height:1.9;font-size:var(--tp-rem);', $css );

/* ---------------------------------------------------------------------------
   ۵ — تنها چیز افزوده: بلوک ریست
   --------------------------------------------------------------------------- */
$reset = <<<'CSS'

/* ═══ TP-ADDED-START ═══════════════════════════════════════════════
   هرچه میان این دو نشان است، در مرجع نبود و ما نوشتیم. گاردهای رنگِ
   پروژه (P5-D184 · P5-D188 · P5-D191) فقط همین بخش را می‌سنجند؛ بخشِ
   منتقل‌شده طرح تأییدشده است و گارد خودش را دارد: هیچ مقداری در آن
   نباید باشد که در مرجع نیست.  P5-D219
   ═══════════════════════════════════════════════════════════════════ */

/* ═══ آزادسازیِ ظرف — بیرون از .tp-page، بی‌پیشوند  ۴٫۲٫۱ بند ۲ ═══════
   سربرگ مرجع تمام‌عرض است و به زیر هدر می‌چسبد. دو ظرفِ قالب میان هدر
   و `.tp-page` می‌نشینند و هر کدام پهنا یا پرکردنِ خودش را می‌افزاید:

     .wd-content-layout   وودمارت — بیشینۀ پهنا و پرکردن بالا و پایین
     .e-con               ظرف المنتور — پرکردن و فاصله از متغیرهای کیت

   ۴٫۱٫۶ این‌ها را داشت و انتقالِ ۴٫۲٫۰ جا انداختشان: سربرگ در جعبه ماند
   و بالایش فاصله افتاد. سلکتورها عمداً بی‌پیشوند و بیرون از `.tp-page`
   اند، چون خودِ ظرف‌ها بیرون‌اند؛ `:has(.tp-page)` مهارشان می‌کند، پس
   فقط صفحه‌ای که خودمان می‌سازیم اثر می‌گیرد. پهنای محتوا را `.tp-wrap`
   می‌دهد (۱۳۴۰)، نه ظرف. */
.wd-content-layout:has(.tp-page){max-width:none;padding:0}
.e-con:has(.tp-page){--padding-top:0px;--padding-right:0px;--padding-bottom:0px;--padding-left:0px;--gap:0px}

/* دکمۀ «بالا»ی وودمارت روی موبایلِ صفحه‌های ما — نوار پایین خودش «بالا»
   دارد و این دکمه رویش می‌افتاد. همان مهارِ `:has(.tp-page)`.  P5-D223 */
@media(max-width:720px){body:has(.tp-page) .scrollToTop{display:none}}

/* ═══ هدر چسبان و نوار بخش‌ها — P5-D223 ══════════════════════════════
   دو عدد را جاوااسکریپت اندازه می‌گیرد و روی `.tp-page` می‌گذارد:
     --tp-snav-top   پایینِ واقعیِ هدر چسبان (با نوار مدیریت، اگر هست)
     --tp-snav-h     بلندیِ خودِ نوار
   لنگر هر بخش جمع این دو است. پیش‌فرض‌ها همان عددهای مرجع‌اند، پس بی
   جاوااسکریپت چیزی از مرجع عوض نمی‌شود. */
.tp-page{--tp-snav-top:var(--tp-header-h);--tp-snav-h:66px}
/* موبایل نوار بالا ندارد، پس لنگر فقط هدر است.  P5-D228 */
@media(max-width:720px){.tp-page{--tp-snav-h:0px}}

/* فهرست لنگرِ مرجع فقط بخش‌های خودش را دارد؛ روستاها، فصل، هوا، محله‌ها
   و بقیه از برگۀ موبایل هم پرش می‌خورند و زیر نوار می‌رفتند. */
.tp-page section[id]{scroll-margin-top:calc(var(--tp-snav-top) + var(--tp-snav-h))}

/* چسبیده: سطحِ متمایز، مرز پایین، سایه — هر سه از توکن، پس در هر سه حالت.
   هدرِ چسبیدۀ وودمارت همین را از پل تم می‌گیرد؛ دو نیمۀ یک واحدند. */
html .tp-page .tp-snav.tp-is-stuck{
  background:var(--tp-surface);
  border-bottom:1px solid var(--tp-border);
  box-shadow:var(--tp-g-shadow-sm);
}

/* ═══ موبایل — ناوبری  P5-D228 (جایگزین بخش موبایل P5-D223) ════════════
   روی موبایل نوار بخش‌های بالا نیست — همان مرجع، که زیر ۷۲۰ پنهانش می‌کند.
   نوار پایین ناوبری موبایل است و همیشه روی صفحه ثابت می‌ماند: خودش، برگۀ
   بخش‌ها و همۀ مودال‌ها را جاوااسکریپت به یک ظرف در body می‌برد، چون
   `position:fixed` زیر نیایی با transform یا filter یا contain (کانتینر
   المنتور) نسبت به همان نیا حساب می‌شود و با صفحه می‌لغزد.

   ظرف همان کلاس `tp-page` را دارد تا هر قاعدۀ `.tp-page …` بخورد؛ خودش
   `display:contents` است — جعبه نمی‌سازد، padding صفحه را نمی‌گیرد و مبنای
   `fixed` نمی‌شود. وزن (۰,۲,۱) و بی‌رقیب. */
html .tp-page.tp-portal{display:contents}

/* پایینِ صفحه جای نوار پایین را نگه می‌دارد، به‌اضافۀ نوار خانۀ آیفون.
   وزن (۰,۱,۲) از قاعدۀ مرجع (۰,۱,۱) سنگین‌تر؛ بی ناحیۀ امن همان ۶۴. */
@media(max-width:720px){html body .tp-page{padding-block-end:calc(64px + env(safe-area-inset-bottom))}}

/* ═══ نوار کنش — پنل اشتراک  ۴٫۳٫۰ بند ۵ · P5-D239 ═══════════════════════
   دکمۀ «اشتراک» در ظرفِ .tp-share است که خودش خانۀ شبکۀ سه‌تایی است؛
   grid تا دکمه همان پهنای پیشین را بگیرد. پنل زیر دکمه: روی دستگاه hover
   با :hover، با صفحه‌کلید با :focus-within، و با ضربه با `tp-is-open`.
   آیکون‌ها کلاس‌های خودِ وودمارت‌اند (.wd-social-icons · social-*) — فقط
   متغیرهای اندازه و رنگ‌شان از توکن‌های ما می‌آید. */
html .tp-page .tp-share{position:relative;display:grid}
html .tp-page .tp-share__panel{display:none;position:absolute;inset-block-start:calc(100% + 6px);inset-inline-start:0;z-index:120;min-inline-size:236px;padding:12px;background:var(--tp-surface);border:1px solid var(--tp-border);border-radius:12px;box-shadow:var(--tp-g-shadow-sm)}
@media(hover:hover){html .tp-page .tp-share:hover .tp-share__panel{display:block}}
html .tp-page .tp-share:focus-within .tp-share__panel,html .tp-page .tp-share.tp-is-open .tp-share__panel{display:block}
html .tp-page .tp-share__icons{--wd-social-size:36px;--wd-social-color:var(--tp-text);--wd-social-bg:var(--tp-soft);--wd-social-color-hover:var(--tp-primary-text);--wd-social-radius:50%;gap:6px;justify-content:flex-start}
html .tp-page .tp-share__row{display:flex;flex-wrap:wrap;gap:6px;margin-block-start:10px}
html .tp-page .tp-share__btn{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid var(--tp-border);border-radius:999px;background:transparent;color:var(--tp-text);font:inherit;font-size:.8rem;cursor:pointer}
html .tp-page .tp-share__btn svg{inline-size:16px;block-size:16px}

/* ═══ نظرها — حالت خالی، فرم، دعوت به ورود  ۴٫۳٫۰ بند ۷ · P5-D234 ════════
   بالای فرم همان جعبۀ .tp-rate مرجع است؛ فرم زیرش می‌نشیند و فقط از
   توکن‌ها رنگ می‌گیرد. معیار با قرص‌های یک تا پنج انتخاب می‌شود — دکمۀ
   رادیویی واقعی، پس صفحه‌کلید و صفحه‌خوان همان رفتار بومی را دارند. */
html .tp-page .tp-rate__first{margin:0 0 12px;font-weight:700;color:var(--tp-text-strong)}
html .tp-page .tp-review-invite{margin:12px 0 0;font-size:calc(.9 * var(--tp-rem));color:var(--tp-text-2)}
html .tp-page .tp-review-invite a{color:var(--tp-primary-text);font-weight:700}
html .tp-page .tp-review-form{display:grid;gap:10px;margin-block-start:14px;padding-block-start:14px;border-block-start:1px solid var(--tp-border-2)}
html .tp-page .tp-rate--empty .tp-review-form{margin-block-start:0;padding-block-start:0;border:0}
html .tp-page .tp-review-form__title{margin:0;font-size:calc(1 * var(--tp-rem));color:var(--tp-text-strong)}
html .tp-page .tp-review-form__row{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px}
html .tp-page .tp-review-form__label{font-size:calc(.86 * var(--tp-rem));color:var(--tp-text)}
html .tp-page .tp-review-form__pills{display:inline-flex;gap:4px}
html .tp-page .tp-review-form__pill{position:relative;cursor:pointer}
html .tp-page .tp-review-form__pill input{position:absolute;opacity:0;inset:0;margin:0;cursor:pointer}
html .tp-page .tp-review-form__pill span{display:grid;place-items:center;inline-size:32px;block-size:32px;border:1px solid var(--tp-border);border-radius:999px;color:var(--tp-text);font-size:calc(.84 * var(--tp-rem))}
html .tp-page .tp-review-form__pill input:checked + span{background:var(--tp-soft);border-color:var(--tp-primary-text);color:var(--tp-primary-text);font-weight:700}
html .tp-page .tp-review-form__pill input:focus-visible + span{outline:2px solid var(--tp-primary-text);outline-offset:2px}
html .tp-page .tp-review-form__field{display:grid;gap:4px;font-size:calc(.86 * var(--tp-rem));color:var(--tp-text)}
html .tp-page .tp-review-form__field input,html .tp-page .tp-review-form__field textarea{inline-size:100%;box-sizing:border-box;padding:8px 10px;border:1px solid var(--tp-border);border-radius:10px;background:var(--tp-surface);color:var(--tp-text);font:inherit}
html .tp-page .tp-review-form__send{justify-self:start;padding:8px 18px;border:0;border-radius:999px;background:var(--tp-primary);color:var(--tp-on-primary);font:inherit;font-weight:700;cursor:pointer}
html .tp-page .tp-review-form__send:disabled{opacity:.6;cursor:default}
html .tp-page .tp-review-form__note{margin:0;min-block-size:1.2em;font-size:calc(.84 * var(--tp-rem));color:var(--tp-text)}

/* ═══ خبر — تصویر کارت، صفحۀ خبرها  ۴٫۳٫۰ بند ۶ ═════════════════════════
   جای «NEWS» لاتینِ مرجع در کاشیِ کارت، تصویر شاخص خبر یا آیکون روزنامه
   می‌نشیند (P5-D240). صفحۀ /news/ همان کارت‌ها را دارد و شمارۀ صفحه‌ها. */
html .tp-page .tp-news .tp-thumb{overflow:hidden}
html .tp-page .tp-news .tp-thumb img{inline-size:100%;block-size:100%;object-fit:cover;display:block}
html .tp-page .tp-news .tp-thumb svg{inline-size:28px;block-size:28px}
html .tp-page.tp-news-page .tp-section-head h1{margin:0;font-size:calc(1.5 * var(--tp-rem));color:var(--tp-text-strong)}
html .tp-page .tp-news-pages{display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-block-start:16px}
html .tp-page .tp-news-pages a,html .tp-page .tp-news-pages span{display:grid;place-items:center;min-inline-size:36px;block-size:36px;padding:0 8px;border:1px solid var(--tp-border);border-radius:999px;color:var(--tp-text);text-decoration:none}
html .tp-page .tp-news-pages span[aria-current]{background:var(--tp-soft);border-color:var(--tp-primary-text);color:var(--tp-primary-text);font-weight:700}
html .tp-page.tp-news-extra{display:grid;gap:10px;margin-block-start:16px}

/* ═══ نقشۀ زنده  ۴٫۳٫۰ بند ۱ · P5-D241 ══════════════════════════════════
   لایۀ زنده روی همان SVG مرجع می‌نشیند و تا بار شدنش شفاف است؛ SVG نه
   پنهان می‌شود نه جابه‌جا، پس چیدمان مرجع دست نمی‌خورد. پس از بار شدن،
   نشانگر ایستای مرجع جای خودش را به نشانگر نقشه می‌دهد. پاپ‌آپ نام بالا
   می‌ماند. */
html .tp-page .tp-map-live{position:absolute;inset:0;z-index:1;opacity:0;transition:opacity .25s ease-out}
html .tp-page .tp-map-inner--live .tp-map-live,html .tp-page .tp-modal__map--live .tp-map-live{opacity:1}
html .tp-page .tp-map-inner--live .tp-map-pin,html .tp-page .tp-modal__map--live .tp-modal__pin{visibility:hidden}
html .tp-page .tp-map-inner .tp-map-pop{z-index:2}
html .tp-page .tp-map-live .maplibregl-ctrl-attrib{font-size:11px}

/* ═══ نوار پایین موبایل — آشکارشدن با نخستین اسکرول  ۴٫۳٫۰ بند ۱۲ ═══════
   بالای صفحه پنهان است؛ با نخستین اسکرولِ بیش از 24px جاوااسکریپت
   `tp-mnav--on` را می‌گذارد و دیگر برنمی‌دارد — حتی اگر کاربر دوباره بالا
   برود. پنهان یعنی visibility هم، تا فوکوس و کلیک نگیرد. فقط زیر `tp-js`
   که اسکریپت راه‌انداز در head می‌گذارد: بی جاوااسکریپت نوار از آغاز پیداست
   و در نخستین رنگ‌آمیزی هم پرشی نیست. وزن (۰,۳,۱) بالاتر از (۰,۲,۱). */
@media(max-width:720px){
  html.tp-js .tp-page .tp-mnav{transform:translateY(calc(100% + 8px));visibility:hidden;transition:transform .2s ease-out,visibility 0s linear .2s}
  html.tp-js .tp-page .tp-mnav.tp-mnav--on{transform:none;visibility:visible;transition:transform .2s ease-out,visibility 0s}
}

/* ═══ کاشی‌های ستون کناری — P5-D244 ════════════════════════════════════
   «پهنه‌های طبیعی»، «محوطه‌های باستانی»، «روستاها»، «اطلاعات جغرافیایی»،
   «نشان‌ها» و هر شبکۀ رابطه‌ای ستون کناری: عیناً کاشی `.neighbors` مرجع —
   سه ستون، برچسب کوچک بالا، نام پررنگ، وسط‌چین. ستون‌ها `minmax(0,1fr)`
   تا نامِ بلند به‌جای پهن‌کردن ستون در کاشی سرریز کند و جاوااسکریپت ببیند
   (`tp-tiles--2`). هیچ واژه‌ای وسط نمی‌شکند: `word-break:normal`، بی
   خط‌تیره. کاشیِ `hidden` پنهان می‌ماند — `display` نویسنده بر `[hidden]`
   مرورگر می‌برد. letter-spacing ندارد: برچسب فارسی است. */
html .tp-page .tp-tiles{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px}
html .tp-page .tp-tiles.tp-tiles--2{grid-template-columns:repeat(2,minmax(0,1fr))}
html .tp-page .tp-tile{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;
 padding:10px 6px;background:var(--tp-tile-bg);border:1px solid var(--tp-tile-border);border-radius:var(--tp-tile-radius);
 color:var(--tp-tile-text);font-weight:700;text-align:center;min-inline-size:0;
 word-break:normal;overflow-wrap:normal;hyphens:none;transition:background-color .16s,border-color .16s}
html .tp-page .tp-tile[hidden]{display:none}
/* برچسب ۰٫۶rem است: متن ریز رنگ متن می‌گیرد، نه متن فرعی (P5-D184) — همان
   `.n-dir` مرجع که مولد به --tp-text نگاشته. */
html .tp-page .tp-tile__k{font-size:calc(.6 * var(--tp-rem));color:var(--tp-tile-text);font-weight:700;line-height:1.2}
html .tp-page .tp-tile b{font-size:calc(.78 * var(--tp-rem));font-weight:800;color:inherit;line-height:1.3}
html .tp-page a.tp-tile[href]:hover{background:var(--tp-tile-hover-bg);border-color:var(--tp-hover);color:var(--tp-primary-text)}
/* برچسب کوچک روی هاور همان رنگ نام — --tp-primary-text روی پرِ هاور ۵٫۹ است. */
html .tp-page a.tp-tile[href]:hover .tp-tile__k{color:var(--tp-primary-text)}
html .tp-page a.tp-tile[href]:focus-visible{outline:2px solid var(--tp-primary-text);outline-offset:2px}
/* خطِ زیرِ نام — «مسیرهای گردشگری»: ایستگاه‌ها یا شهر.  P5-D269
   همان اندازه، رنگ و بلندی خط برچسب کوچک (متن ریز ← --tp-tile-text، P5-D184؛
   بلندی خط ۱٫۲ مثل `.n-dir` مرجع — پذیرش با آن جفتش می‌کند)؛ روی هاور همان رنگ نام. */
html .tp-page .tp-tile__sub{font-size:calc(.6 * var(--tp-rem));color:var(--tp-tile-text);font-weight:700;line-height:1.2}
html .tp-page a.tp-tile[href]:hover .tp-tile__sub{color:var(--tp-primary-text)}

/* ═══ توکن کاشی برای همۀ کاشی‌ها — P5-D245 ═══════════════════════════════
   پس‌زمینه، قاب، متن، متن فرعی، هاور و شعاع همه از `--tp-tile-*` (کاشی روز
   هوا). نیمۀ منتقل‌شده این کاشی‌ها را روی `--tp-surface-2` بی‌قاب می‌نشاند
   و در شب یکی تیره و دیگری زیتونی بود. وزن‌ها یک پله از قاعدۀ مرجع
   سنگین‌ترند: (۰,۲,۳) در برابر (۰,۲,۲)، و برای تراشۀ فرهنگ (۰,۳,۳) در
   برابر قاعدۀ شبِ مرجع (۰,۳,۲). «اطلاعات سفر» دو ستون می‌ماند، فقط رنگ
   می‌گیرد. */
html body .tp-page .tp-neighbors a,
html body .tp-page .tp-practical-grid a{
  background:var(--tp-tile-bg);border:1px solid var(--tp-tile-border);
  border-radius:var(--tp-tile-radius);color:var(--tp-tile-text);
}
html body .tp-page .tp-neighbors a .tp-n-dir,
html body .tp-page .tp-practical-grid a span{color:var(--tp-tile-text);letter-spacing:normal}
html body .tp-page .tp-neighbors a:hover,
html body .tp-page .tp-practical-grid a:hover{
  background:var(--tp-tile-hover-bg);border-color:var(--tp-hover);color:var(--tp-primary-text);
}
/* متن ریزِ کاشی روی هاور همان رنگ نام: --tp-primary-text روی --tp-tile-hover-bg
   در حالت روز ۵٫۹. --tp-tile-muted هرگز روی پرِ هاور نمی‌آید — آن‌جا ۴٫۴۹ است.
   «مسیرها» از ۴٫۴٫۱ کاشی `.tp-tile` است (P5-D269) و قاعدۀ خودش را از بلوک
   P5-D244 می‌گیرد. */
html body .tp-page .tp-neighbors a:hover .tp-n-dir,
html body .tp-page .tp-practical-grid a:hover span{color:var(--tp-primary-text)}

/* خانواده‌های فرهنگ — هر عضو کاشی است، نه قرص گرادیانی.  P5-D245 */
html body .tp-page .tp-culture-card .tp-culture-card__list a{
  background:var(--tp-tile-bg);border:1px solid var(--tp-tile-border);
  border-radius:var(--tp-tile-radius);color:var(--tp-tile-text);box-shadow:none;
}
html body .tp-page .tp-culture-card .tp-culture-card__list a:hover{
  background:var(--tp-tile-hover-bg);border-color:var(--tp-hover);color:var(--tp-primary-text);box-shadow:none;
}
/* نقطۀ برند روی هاورِ روشن هم دیده شود — سفیدِ مرجع روی پرِ نرم گم می‌شد.
   نقطه متنی ندارد؛ color فقط قاعدۀ P5-D191 را صریح نگه می‌دارد. */
html body .tp-page .tp-culture-card .tp-culture-card__list a:hover::before{
  background:var(--tp-hover);color:var(--tp-on-hover);
  box-shadow:0 0 0 3px color-mix(in srgb,var(--tp-hover) 14%,transparent);transform:none;
}

/* ═══ یکسانی کارت‌ها — P5-D246 ═════════════════════════════════════════
   کارت اسلایدر: تصویر با `object-fit:cover` (راه‌راهِ تزئینی مرجع روی عکس
   نمی‌آید)؛ عنوان دو خط — جایش را حتی با یک خط نگه می‌دارد. هنر، بدنه و
   توضیحِ رفته در بلوک P5-D266 پایین. ردیف شبکه همۀ کارت‌ها را
   هم‌قد می‌کند. فرهنگ و اعضای پنجرۀ فهرست: ردیف‌های هم‌قد (`1fr`)، نام عضو
   حداکثر دو خط.
   شبکۀ پنجرۀ گالری: نسبت مرجع ۴:۳ و کاشی پهنِ ۸:۳ است، ولی کاشی پهن دو ستون
   به‌اضافۀ فاصله است و ۰٫۳۷۵ فاصله بلندتر درمی‌آمد (۴٫۵ پیکسل در ۱۲). ردیف‌ها
   `1fr` می‌شوند و کاشی کشیده — همۀ کاشی‌ها یک بلندی، همان بلندیِ ردیف پهن. */
html body .tp-page .tp-card-slide__art > .tp-card-slide__img{position:absolute;inset:0;inline-size:100%;block-size:100%;object-fit:cover;display:block}
html body .tp-page .tp-card-slide__art--img::before{display:none}
html body .tp-page .tp-card-slide__title{
  display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2;overflow:hidden;
  min-block-size:2lh;
}

/* ═══ کارت اسلایدر — P5-D266 (جایگزین بخشی از P5-D246) ═════════════════
   مرجع هنر را absolute با ۱۶۲ پیکسل می‌نشاند و بدنه از پایین رویش می‌آمد؛
   کارتی که بدنه‌اش بلندتر بود عکس کمتری نشان می‌داد — در یک اسلایدر ۸۶ و
   ۱۳۶ و ۱۴۹ پیکسل، با هنرِ همه ۱۶۲. حالا هنر در جریان عادی کارت است، ۲۰۰
   پیکسل (موبایل ۱۸۰)، پس همه‌اش دیده می‌شود. توضیح نمی‌آید. بدنه ارتفاع
   ثابت دارد: یک خط برچسب، دو خط عنوان، یک خط پاورقی؛ ارتفاع خط هر کدام
   صریح است (همان مقدارهای محاسبه‌شدۀ پیشین: ۱٫۹ · ۱٫۴ · ۱٫۹) تا جمع دقیق
   باشد — ۶۰ پیکسل فاصله و حاشیه + ۵٫۵۶۲rem خط. برچسب و پاورقی تک‌خطی‌اند؛
   پاورقی ته بدنه. */
html body .tp-page .tp-card-slide{justify-content:flex-start;min-block-size:0}
html body .tp-page .tp-card-slide__art{position:relative;inset:auto;flex:none;block-size:200px}
html body .tp-page .tp-card-slide__body{display:flex;flex-direction:column;flex:none;min-block-size:0;
  block-size:calc(60px + 5.562 * var(--tp-rem));overflow:hidden}
html body .tp-page .tp-card-slide__tag{line-height:1.9;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
html body .tp-page .tp-card-slide__title{line-height:1.4}
html body .tp-page .tp-card-slide__foot{margin-block-start:auto;line-height:1.9;white-space:nowrap;overflow:hidden}
html body .tp-page .tp-card-slide__foot > span{overflow:hidden;text-overflow:ellipsis}
@media(max-width:720px){
  html body .tp-page .tp-card-slide__art{block-size:180px}
}
html body .tp-page .tp-culture-grid{grid-auto-rows:1fr}
.tp-page .tp-list__card b{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;line-clamp:2;overflow:hidden}
/* پنجرۀ گالری بی خانۀ خالی — ۴٫۴٫۲. کاشی پهن (نخستین و نهمین، الگوی مرجع)
   دو ستون می‌گیرد و ستون‌ها `auto-fill` است. در پهنایی که شمار ستون فرد است
   (۱۹۲۰ ← پنج ستون) کاشی پهن در ستون آخر جا نمی‌شد، به ردیف بعد می‌رفت و
   خانۀ آخر ردیف سیاه می‌ماند. `dense` نخستین کاشی تکی بعدی را همان‌جا
   می‌نشاند؛ فقط ردیف پایانی ممکن است ناتمام بماند. */
html body .tp-page .tp-gallery-modal__grid{grid-auto-rows:1fr;grid-auto-flow:row dense}
html body .tp-page .tp-gallery-modal__grid > .tp-gallery-modal__item{align-self:stretch}
/* تیترِ سربرگ در چپ‌به‌راست — ۴٫۴٫۲. فاصلۀ منفیِ مرجع (-.07em) برای تیتر
   فارسی است؛ در لاتین، با فاصلۀ باریکِ قلم، فاصلۀ دو واژه دو پیکسل می‌شد و
   «Isfahan Province» یک واژه دیده می‌شد. فقط `:dir(ltr)`؛ فارسی و عربی
   همان مرجع. */
html body .tp-page .tp-hero h1:dir(ltr){letter-spacing:-.02em;word-spacing:.15em}

/* ═══ تصویر بندانگشتی اخبار — SPRINT-4.4 ۱٫۵ ═══════════════════════════
   ۴:۳ — ۱۱۲×۸۴ در دسکتاپ، ۸۸×۶۶ در موبایل. بی‌تصویر، همان جعبۀ آیکون با
   همان اندازه. مرجع ۹۶×۶۴ و ۷۲ پهنا داشت. */
html body .tp-page .tp-news a{grid-template-columns:112px 1fr}
html body .tp-page .tp-news .tp-thumb{inline-size:112px;block-size:84px;aspect-ratio:4/3}
@media(max-width:720px){
  html body .tp-page .tp-news a{grid-template-columns:88px 1fr}
  html body .tp-page .tp-news .tp-thumb{inline-size:88px;block-size:66px}
}

/* ═══ تاریخ راستی‌آزمایی — P5-D268 ═════════════════════════════════════
   زیر مقدار «وضعیت محتوا»، در همان خانه؛ نشان جداگانۀ «✓ آخرین
   راستی‌آزمایی» رفت. مقدار و تاریخ هر دو به لبۀ پایانی ردیف. تاریخ متن
   ریز است (۰٫۷۲rem)، پس --tp-text، نه متن فرعی (P5-D184). */
html .tp-page .tp-uid b{text-align:end}
html .tp-page .tp-uid__date{display:block;margin-block-start:2px;font-size:calc(.72 * var(--tp-rem));font-weight:600;color:var(--tp-text)}

/* ═══ تراز دوطرفۀ متن چندخطی بدنه — P5-D270 ════════════════════════════
   یک کلاس، نه قاعدۀ سراسری روی `p`: بدنۀ «درباره» · پاسخ پرسش‌ها · خروجی
   هوش مصنوعی · متن خبر · (فاز ۴) متن گفت‌وگوی پادکست. خط آخر از آغاز.
   سرفصل‌های درون بدنۀ «درباره» دوطرفه نمی‌شوند. عنوان، برچسب، دکمه،
   کاشی، تراشه، عدد و متن لاتین مونو این کلاس را نمی‌گیرند. */
html .tp-page .tp-justify{text-align:justify;text-align-last:start;text-justify:inter-word}
/* ۴٫۴٫۳ — تیتر سربرگ عربی: همزه و سرکش «أ» در بلندی خط ۱٫۰۲ به برچسب نوعِ بالای تیتر
   می‌رسید (صفحۀ عربی شهر). عربی بالای خط جای بیشتری می‌خواهد؛ فارسی و انگلیسی همان. */
html .tp-page .tp-hero h1:lang(ar){line-height:1.3}
html .tp-page .tp-justify h2,
html .tp-page .tp-justify h3,
html .tp-page .tp-justify h4,
html .tp-page .tp-justify h5,
html .tp-page .tp-justify h6{text-align:start;text-align-last:auto}

/* ═══ کارت آمار با کمتر از چهار خانه — SPRINT-4.4 ۲ج٫۱ · P5-D275 · P5-D282 ═
   خانه‌ها از ماتریس نوع می‌آیند و خانۀ بی‌داده نمی‌آید؛ شبکۀ چهارستونۀ مرجع
   با سه خانه یک چهارمِ خالی می‌گذاشت. ستون‌ها به شمار خانه‌ها (`data-tp-n`)؛
   زیر ۹۰۰ دو ستون مرجع، و خانۀ تنهای ردیف آخر تمام‌پهنا. چهار خانه همان مرجع. */
html .tp-page .tp-stats[data-tp-n="3"]{grid-template-columns:repeat(3,1fr)}
html .tp-page .tp-stats[data-tp-n="2"]{grid-template-columns:repeat(2,1fr)}
html .tp-page .tp-stats[data-tp-n="1"]{grid-template-columns:1fr}
@media(max-width:900px){
  html .tp-page .tp-stats[data-tp-n="3"]{grid-template-columns:1fr 1fr}
  html .tp-page .tp-stats[data-tp-n="3"] .tp-stat:nth-child(3){grid-column:1/-1}
}

/* ═══ دعوت به ثبت‌نام در بلوک هوش مصنوعی — SPRINT-4.4 ۱٫۷ · P5-D267 ═══════
   دکمۀ دعوت با --tp-accent و --tp-on-accent، تا با دکمه‌های ثانویۀ
   فیروزه‌ای بلوک اشتباه نشود. بلوک در هر سه حالت همان سبزِ تیرۀ ثابتِ
   مرجع است، پس سفید و #d3e7e3 همان رنگ‌های خودِ بلوک‌اند؛ حلقۀ فوکوس
   سفید. متن دعوت فارسی است و letter-spacing ندارد. */
/* P5-D267 — مهمان: جملۀ دعوت زیر عنوان (یک بار)، و **یک** دکمۀ «ثبت‌نام /
   ورود» در ردیف دکمه‌ها کنار «کپی خروجی»؛ پیوند متنیِ «ورود» رفت. دکمه
   همان قالب دکمه‌های ردیف است (شعاع ۱۱، بلندی ۴۰، همان قلم) و فقط رنگ
   accent دارد. وزن جمله (۰,۳,۲) از `.tp-ai p` منتقل‌شده (۰,۲,۲) بیشتر. */
html .tp-page .tp-ai p.tp-ai__cta-text{margin:4px 0 0;color:#d3e7e3;font-size:calc(.86 * var(--tp-rem));font-weight:700;line-height:1.7;letter-spacing:normal}
html .tp-page .tp-ai__signup{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-block-size:40px;padding:10px 16px;
 border:1px solid var(--tp-accent);border-radius:11px;background:var(--tp-accent);color:var(--tp-on-accent);
 font-weight:800;font-size:calc(.78 * var(--tp-rem));text-decoration:none;letter-spacing:normal}
html .tp-page .tp-ai__signup:hover{background:var(--tp-accent-strong);border-color:var(--tp-accent-strong);color:var(--tp-on-accent)}
html .tp-page .tp-ai__signup:focus-visible{outline:2px solid #fff;outline-offset:2px}

/* هر آیتمِ برگۀ «بخش‌ها» آیکون ثابت بخش خودش را کنار برچسب دارد — همان
   مجموعه‌ای که نوار می‌خواند. رنگ از خودِ پیوند. */
.tp-page .tp-sheet__p a > svg{inline-size:18px;block-size:18px;flex:none}

/* آیکون هر بخش، درون‌خط — پیوند همان جعبۀ مرجع را نگه می‌دارد. */
.tp-page .tp-snav a > svg{
  inline-size:15px;block-size:15px;
  vertical-align:-3px;
  margin-inline-end:6px;
}

/* ═══ موبایل — سرفصل‌ها  ۴٫۲٫۳ بند ۴ ══════════════════════════════════
   «همه شهرها ←» و هر `.more` در همان ردیفِ عنوان می‌ماند: `h2` راست،
   `.more` چپ، متن راهنما زیر هر دو. مرجع زیر ۷۲۰ ستونی‌اش می‌کرد.
   جعبۀ درونی (`h2` + راهنما) `display:contents` می‌شود تا هر دو آیتمِ
   همین شبکه باشند. وزن (۰,۲,۲) از ستونیِ مرجع (۰,۲,۱) سنگین‌تر. */
@media(max-width:720px){
  html .tp-page div.tp-section-head{
    display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;
    column-gap:12px;row-gap:4px;
  }
  html .tp-page div.tp-section-head > div{display:contents}
  html .tp-page div.tp-section-head h2{grid-column:1;grid-row:1;min-inline-size:0}
  html .tp-page div.tp-section-head .tp-more{grid-column:2;grid-row:1;justify-self:end}
  html .tp-page div.tp-section-head .tp-lead{grid-column:1/-1;grid-row:2}
}

/* ═══ موبایل — فاصلۀ کارت‌ها  ۴٫۲٫۳ بند ۳ ════════════════════════════
   دو ستون `display:contents` اند و هر بخش و کارتِ کناری آیتمِ همین شبکه.
   مرجع `gap:0` گذاشته و فاصله را روی `.content-grid > .rail-card` — ولی
   کارت کناری فرزندِ `.rail` است، پس آن انتخاب‌گر هرگز نمی‌خورد و کارت‌ها
   به هم می‌چسبیدند (در خودِ مرجع هم). حالا فاصلۀ ردیف ۲۸ و margin پایینِ
   بخش صفر، تا دو برابر نشود. */
@media(max-width:720px){
  html .tp-page div.tp-content-grid{row-gap:28px}
  html .tp-page section.tp-section{margin-bottom:0}
}

/* ═══ ریست — تنها چیزی که در مرجع نبود ══════════════════════════════
   مرجع دکمه و پیوندِ بی‌سبکِ مرورگر را فرض کرده. وودمارت روی هر <button>
   و هر <a> در کل سایت قاعده می‌گذارد. اگر اینها خنثی نشوند، هر دکمۀ
   متنیِ قالب — «همه تصاویر ←»، «N مورد دیگر»، کنترل اسلایدر — قاب و
   ارتفاع ۴۲ و حروف‌فاصلۀ وودمارت را می‌گیرد.

   دامنه عمداً `.tp-page` است؛ بقیۀ سایت دست‌نخورده می‌ماند. */
/*
 * «خنثی» یعنی برگشت به دکمۀ مرورگر، نه صفر.
 *
 * `revert` هر ویژگی را به مقدار پیش‌فرض مرورگر برمی‌گرداند — دقیقاً همان
 * چیزی که مرجع فرض کرده. نسخۀ اول صفر می‌گذاشت و آزمون پذیرش گرفتش:
 * دکمه‌های اسلایدر در مرجع پرکردن ۱×۶ مرورگر را دارند و در ما صفر
 * داشتند؛ کاشی گالری رنگ و `gap:5px` وودمارت را.
 *
 * وزن (۰,۱,۱) است: از `:is(button,…)` وودمارت (۰,۱,۰) سنگین‌تر، از هر
 * قاعدۀ مرجع (۰,۲,۱) سبک‌تر — پس هر جا مرجع مقداری دارد، همان برنده است.
 */
.tp-page button,
.tp-page input[type="button"],
.tp-page input[type="submit"]{
  background:revert;
  border:revert;
  border-radius:revert;
  min-height:revert;
  min-width:revert;
  padding:revert;
  box-shadow:revert;
  text-transform:revert;
  text-decoration:revert;
  text-shadow:revert;
  letter-spacing:revert;
  color:revert;
  display:revert;
  gap:revert;
  align-items:revert;
  justify-content:revert;
  text-align:revert;
  vertical-align:revert;
  position:revert;
  cursor:revert;
  transition:revert;
  outline:revert;
  font:inherit;
}

/* هاورِ وودمارت (۰,۲,۰) از ریست سنگین‌تر است و از متغیرهای خودش می‌خواند؛
   همان‌ها به مقدار مرورگر برمی‌گردند. متغیر وزن ندارد، پس با هیچ قاعدۀ
   مرجعی رقابت نمی‌کند. `--li-mb` همان فاصلۀ ۱۰ پیکسلیِ زیر هر `li` است. */
.tp-page{
  --btn-color:buttontext;
  --btn-color-hover:buttontext;
  --btn-bgcolor:buttonface;
  --btn-bgcolor-hover:buttonface;
  --btn-box-shadow:none;
  --btn-box-shadow-hover:none;
}

/* وودمارت متغیر فاصلۀ فهرست را روی خودِ `ul/ol` می‌گذارد (`--li-mb:10px`)
   و زیر هر `li` جز آخری ۱۰ پیکسل می‌نشاند. مرورگر صفر می‌دهد. */
.tp-page li{margin-bottom:revert}

/* وودمارت `strong,b{font-weight:600}` دارد؛ مرورگر `bolder` می‌دهد (۷۰۰). */
.tp-page b,
.tp-page strong{font-weight:revert}

/* ─── شش نامِ مشترک با user-core ────────────────────────────────────
   `topersia-user-core.css` بعد از ما بار می‌شود و شش کلاس هم‌نام دارد.
   لنگرِ `.tp-page` وزن ما را بالاتر می‌برد، ولی فقط برای ویژگی‌هایی که
   **اعلام کرده‌ایم**؛ هرچه اعلام نکنیم قاعدۀ آن‌ها بی‌رقیب می‌نشیند.
   یک بار `.tp-card{padding:21px}` همین‌طور از آن‌ها رسید.

   این بلوک اول فایل است، پس هر جا مرجع خودش مقداری دارد همان برنده
   می‌شود و این‌ها فقط شکافِ بی‌رقیب را می‌بندند.  P5-D204 */
.tp-page .tp-hero{
  display:block;justify-content:normal;align-items:normal;gap:normal;
  flex-direction:row;padding:0;margin:0;max-width:none;
}
.tp-page .tp-main{width:auto;margin:0;min-width:0;padding:0}
.tp-page .tp-stats{gap:normal;margin:0}
.tp-page .tp-stat{
  border-radius:0;background:transparent;text-align:start;cursor:auto;
  color:inherit;font:inherit;transition:none;
}
.tp-page .tp-badge{align-self:auto;background:transparent}
.tp-page a{
  text-decoration:none;
  box-shadow:none;
  border-bottom:0;
  letter-spacing:normal;
}
.tp-page a:hover{text-decoration:none}

/* ارتفاع هدر چسبانِ قالب. جاوااسکریپت اندازۀ واقعی را می‌گذارد؛ این عدد
   فقط وقتی به کار می‌آید که هدر چسبانی در کار نباشد — همان ۶۴ مرجع. */
.tp-page{--tp-header-h:64px}
@media(max-width:720px){.tp-page{--tp-header-h:56px}}

/* واحدِ `rem`ِ مرجع — ریشۀ ۱۶ پیکسلی که مرجع رویش کشیده شده، ضربدر
   اندازۀ متنی که خواننده برگزیده. به تنظیم «اندازۀ پایه»ی سایت بسته
   نیست؛ آن تنظیم ریشۀ `html` را ۱۷ می‌کند و نسبت‌های طرح را می‌شکست. */
.tp-page{--tp-rem:calc(16px * var(--tp-scale,1))}

/* عکسِ واقعی در کاشی — مرجع عکس نداشت و کاشی‌هایش گرادیان بودند.
   عکسِ افقی در کاشیِ بزرگِ ۴۲۰ پیکسلی به ارتفاع طبیعی‌اش می‌نشست و پایینش
   گرادیان پیدا بود. عکس کلِ کاشی را می‌پوشاند؛ سایۀ پایینِ `::after` و
   برچسب همچنان رویش‌اند، چون در ترتیبِ رسم بعد از آن‌اند. */
.tp-page .tp-gallery-main__item > img,
.tp-page .tp-gallery-modal__item > img{
  position:absolute;inset:0;width:100%;height:100%;object-fit:cover;
}

/* نمای تمام‌صفحۀ عکس — شاسی همان مودال گالری است.
   مرجع کلیک روی کاشی را فقط با `scale` جواب می‌داد چون عکس واقعی نداشت.
   ۴٫۲٫۱ بند ۸ — تمام‌عرض و `contain`، بلندی حداکثر ۸۰vh. دکمه‌ها وسطِ
   عمودیِ عکس روی لبه‌ها.  P5-D243 (جایگزین P5-D226) — «بعدی» سرِ پایانِ
   خط، «قبلی» سرِ آغاز: در راست‌به‌چپ «بعدی» لبۀ چپ با «‹» و «قبلی» لبۀ
   راست با «›»، در چپ‌به‌راست برعکس؛ ویژگی منطقی خودش هر دو جهت را
   می‌دهد. پرِ دکمه همان تیرگیِ ۸۲٪ سایۀ کاشیِ مرجع است، چون روی هر عکسی
   می‌نشیند و پیکانِ سفید باید رویش خوانا بماند. زیرنویس در هر اندازه. */
.tp-page .tp-solo{margin:0 auto;max-inline-size:1400px}
.tp-page .tp-solo__stage{position:relative}
.tp-page .tp-solo__img{
  display:block;inline-size:100%;block-size:auto;max-block-size:80vh;
  object-fit:contain;border-radius:var(--tp-r);touch-action:pan-y;
  /* تا عکسِ کامل بار شود، جعبه فرو نمی‌ریزد و دکمه‌ها جا نمی‌پرند؛ با
     `contain` عکس وسطِ همین جعبه می‌نشیند، پس دکمه‌ها وسطِ عکس می‌مانند. */
  min-block-size:30vh;
}
.tp-page .tp-solo__nav{
  position:absolute;top:50%;transform:translateY(-50%);z-index:2;
  inline-size:48px;block-size:48px;border-radius:50%;cursor:pointer;
  display:grid;place-items:center;font-size:calc(1.6 * var(--tp-rem));line-height:1;
  border:1px solid rgba(255,255,255,.22);background:rgba(5,12,10,.82);color:#fff;
}
.tp-page .tp-solo__nav:hover{background:rgba(6,12,11,.94)}
.tp-page .tp-solo__nav--next{inset-inline-end:12px}
.tp-page .tp-solo__nav--prev{inset-inline-start:12px}
.tp-page .tp-solo__cap{display:grid;gap:4px;padding:14px 8px 0;text-align:center;color:#fff}
.tp-page .tp-solo__cap b{font-size:calc(.95 * var(--tp-rem));font-weight:800}
.tp-page .tp-solo__cap span{color:rgba(255,255,255,.72);font-size:calc(.82 * var(--tp-rem))}
.tp-page .tp-solo__cap span:empty{display:none}
@media(max-width:720px){
  .tp-page .tp-solo__nav{inline-size:40px;block-size:40px;font-size:calc(1.3 * var(--tp-rem))}
  .tp-page .tp-solo__nav--next{inset-inline-end:6px}
  .tp-page .tp-solo__nav--prev{inset-inline-start:6px}
}

/* ═══ اسلایدر و گالری — P5-D243 ═════════════════════════════════════
   دکمۀ اسلایدری که کاری نمی‌کند غیرفعال است (در آغاز «قبلی»، در پایان
   «بعدی») و کم‌رنگ دیده می‌شود. وزن (۰,۳,۲) از هاورِ مرجع (۰,۳,۲) پیش‌تر
   نیست، پس رویداد اشاره هم بسته است تا هاور رویش ننشیند. */
html .tp-page .tp-slider-nav button:disabled{opacity:.38;cursor:default;pointer-events:none}

/* پیکانِ «بکشید» مرجع «←» است — در راست‌به‌چپ همان سمتی که کارت‌های
   پنهان هستند. در صفحۀ چپ‌به‌راست آن سمت راست است. «←» آینه‌ای رسم
   نمی‌شود، پس نویسه عوض می‌شود. وردپرس فقط برای راست‌به‌چپ `dir` می‌گذارد.
   وزن (۰,۳,۲) از قاعدۀ مرجع (۰,۲,۲) سنگین‌تر.  P5-D243 */
html:not([dir="rtl"]) .tp-page .tp-slider-hint::before{content:"→"}

/* هیچ گذاری در گالری بیش از ۱۵۰ms نیست: تعویض و باز شدن بی‌درنگ.
   هفت قاعدۀ مرجع ۱۸۰ تا ۳۰۰ داشتند؛ این‌ها یک پله سنگین‌ترند. */
html .tp-page .tp-gallery-main > .tp-gallery-main__item,
html .tp-page .tp-gallery-main > .tp-gallery-main__item::after,
html .tp-page .tp-gallery-modal__grid > .tp-gallery-modal__item,
html .tp-page .tp-gallery-modal__grid > .tp-gallery-modal__item::after,
html .tp-page .tp-gallery-modal__item > .tp-gallery-modal__cap,
html .tp-page .tp-gallery-modal__head > .tp-gallery-modal__close{transition-duration:150ms}
html .tp-page div.tp-gallery-modal.tp-show{animation-duration:150ms}

/* ═══ مودالِ فهرست — P5-D221 · P5-D245 ══════════════════════════════
   شاسی همان مودال گالری است (پردۀ تیره، سرِ چسبان، عنوان و شمار و بستن).
   جعبۀ جست‌وجو، دکمۀ بستن و کارتِ فشردۀ اعضا از ۴٫۴٫۰ توکن کاشی می‌گیرند
   (P5-D245) — همان کاشی روز هوا در هر سه حالت، روی پردۀ تیره. z-index
   همان ۶۰۰ مودال گالری: بالای نوار بخش‌ها (۱۱۰) و نوار پایین موبایل (۷۰). */
.tp-page .tp-gallery-modal--list .tp-gallery-modal__head{flex-wrap:wrap}
/* جعبۀ جست‌وجو روی پردۀ تیره نشسته: پر و متن از کاشی، ولی قابش --tp-tile-muted —
   در شب پرِ کاشی (#11161B) با پرده یکی است و قابِ کاشی (#313B46) فقط ۱٫۷۳؛
   مرز فیلد باید ۳ باشد (WCAG 1.4.11). متن فرعی آن‌جا ۹٫۱ است. */
.tp-page .tp-list__q{
  flex:1 1 260px;max-inline-size:520px;min-block-size:44px;
  padding:0 16px;border-radius:var(--tp-tile-radius);
  border:1px solid var(--tp-tile-muted);background:var(--tp-tile-bg);
  color:var(--tp-tile-text);font:inherit;font-size:calc(.9 * var(--tp-rem));
}
.tp-page .tp-list__q::placeholder{color:var(--tp-tile-muted)}
.tp-page .tp-list__q:focus-visible{outline:3px solid var(--tp-accent-strong);outline-offset:2px}
html .tp-page .tp-gallery-modal--list .tp-gallery-modal__head > .tp-gallery-modal__close{
  background:var(--tp-tile-bg);border:1px solid var(--tp-tile-border);color:var(--tp-tile-text);
}
html .tp-page .tp-gallery-modal--list .tp-gallery-modal__head > .tp-gallery-modal__close:hover{
  background:var(--tp-tile-hover-bg);border-color:var(--tp-hover);color:var(--tp-primary-text);
}
.tp-page .tp-list__body{max-inline-size:1400px;margin-inline:auto;padding-bottom:30px}
/* لنگرِ `html` مثل نیمۀ منتقل‌شده: قاعدۀ سرفصلِ tokens.css
   (`html:root .tp-shell :where(h1…h4)`، وزن ۰,۲,۱) وگرنه رنگِ تیره می‌داد
   و سرفصل روی پردۀ تیره ۱٫۲۳ درمی‌آمد — سنجۀ کنتراست گرفتش. */
html .tp-page .tp-list__fam{color:#fff;font-size:calc(.9 * var(--tp-rem));font-weight:800;margin:18px 0 10px}
html .tp-page .tp-list__block:first-child .tp-list__fam{margin-top:0}
.tp-page .tp-list__grid{
  list-style:none;margin:0;padding:0;display:grid;
  grid-template-columns:repeat(auto-fill,minmax(220px,1fr));grid-auto-rows:1fr;gap:8px;
}
.tp-page .tp-list__card{
  display:flex;align-items:center;justify-content:space-between;gap:10px;
  min-block-size:48px;padding:10px 14px;border-radius:var(--tp-tile-radius);
  border:1px solid var(--tp-tile-border);background:var(--tp-tile-bg);color:var(--tp-tile-text);
}
.tp-page a.tp-list__card:hover,
.tp-page a.tp-list__card:focus-visible{background:var(--tp-tile-hover-bg);border-color:var(--tp-hover);color:var(--tp-primary-text)}
.tp-page .tp-list__card b{font-weight:700;font-size:calc(.9 * var(--tp-rem))}
.tp-page .tp-list__tag{flex:none;color:var(--tp-tile-text);font-size:calc(.72 * var(--tp-rem))}
.tp-page a.tp-list__card:hover .tp-list__tag,
.tp-page a.tp-list__card:focus-visible .tp-list__tag{color:var(--tp-primary-text)}
.tp-page .tp-list__empty{color:rgba(255,255,255,.72);text-align:center;padding:40px 0;margin:0}
@media(max-width:720px){
  .tp-page .tp-list__q{order:3;flex-basis:100%;max-inline-size:none}
  .tp-page .tp-list__grid{grid-template-columns:1fr}
}

/* ═══ صوت و ویدیو — P5-D222 ═════════════════════════════════════════
   ردیفِ پروندۀ صوتی `div` است (پخش‌کننده درونِ `<a>` نامعتبر است)، با همان
   مقادیرِ ردیفِ `a`ِ مرجع — گارد آزمون برابری دو اعلان را می‌پاید. پخش‌کننده
   زیر همان سه ستون، تمام‌عرض. */
.tp-page .tp-audio__item{display:grid;grid-template-columns:auto 1fr auto;gap:calc(.5 * var(--tp-rem));align-items:center;
 padding:calc(.45 * var(--tp-rem)) calc(.6 * var(--tp-rem));border:1px solid var(--tp-border);border-radius:12px;background:var(--tp-bg);
 color:var(--tp-text);font-size:calc(.84 * var(--tp-rem))}
.tp-page .tp-audio__item audio{grid-column:1/-1;inline-size:100%;block-size:36px;margin-block-start:4px}

/* آیکونِ نوعِ ردیف روایت — صدا یا تصویر — در همان ستونِ نخستِ مرجع.  P5-D227
   (نشانِ پخشِ کاشیِ بزرگِ گالری رفت: گالری دوباره فقط عکس است.) */
.tp-page .tp-audio__kind{display:grid;place-items:center}

/* صحنۀ پخش: ۱۶:۹، هرگز بلندتر از ۸۰vh. */
.tp-page .tp-solo__stage--media{
  inline-size:min(100%, calc(80vh * 16 / 9));aspect-ratio:16/9;margin-inline:auto;
}
.tp-page .tp-solo__stage--media > iframe,
.tp-page .tp-solo__stage--media > video{
  position:absolute;inset:0;inline-size:100%;block-size:100%;border:0;border-radius:var(--tp-r);
}

/* بخش‌هایی که مرجع نداشت (روستاها، محله‌ها، استان‌ها، گذرهای مرزی…) روی
   موبایل بی‌`order` می‌مانند و `order:0` یعنی می‌پرند اول صفحه. این پیش‌فرض
   آنها را میان «روابط» (۴۰) و «همیشه انتها» (۹۰) می‌نشاند. قاعده‌های
   شناسه‌دارِ مرجع وزن بیشتری دارند، پس دست‌نخورده برنده می‌مانند. */
@media(max-width:720px){.tp-page .tp-main-col>*,.tp-page .tp-rail>*{order:50}}

/* ═══ لایۀ انیمیشن هوا — کار خودِ ToPersia، در مرجع نبود  P5-D197 ═══
   این بخش از `topersia-g-zones.css` منتقل شد، دست‌نخورده. مرجع هوا نداشت،
   پس اگر فقط مرجع منتقل می‌شد، انیمیشن سربرگ بی‌صدا می‌افتاد.

   نردبان z-index صریح است و ناچار: در مرجع هیچ‌کدام از لایه‌های سربرگ
   جز `.hero__in` عدد نداشتند، و یک لایۀ `z-index:1` میانشان بالای
   پوشش می‌نشست — یعنی ذره روی متن. ترتیب همان ترتیب مرجع می‌ماند؛
   فقط صریح می‌شود.  P5-D197 */
.tp-page .tp-hero__fallback{z-index:0}
.tp-page .tp-hero__img{z-index:0}
.tp-page .tp-wx-fx{z-index:1}
.tp-page .tp-hero__overlay{z-index:2}
/* مرجع خودش به `.hero__in` عدد ۲ داده؛ این یکی باید از آن سنگین‌تر باشد
   تا بالای پوشش بنشیند، پس فرزندِ مستقیمِ سربرگ نامیده می‌شود. */
html .tp-page .tp-hero > .tp-hero__in{z-index:3}

/* ─── متنِ فصل ───────────────────────────────────────────────────────
   مرجع `--s` را هم برای پر به کار می‌برد و هم برای متن؛ نگاشتِ رنگ متن
   آن را به `--tp-s-text` می‌برد و این‌جا هر فصل نسخۀ متنِ خودش را
   می‌گیرد. پرها همان `--tp-s` مرجع می‌مانند.  P5-D153 */
.tp-page .tp-season--spring{--tp-s-text:var(--tp-s-spring-text)}
.tp-page .tp-season--summer{--tp-s-text:var(--tp-s-summer-text)}
.tp-page .tp-season--autumn{--tp-s-text:var(--tp-s-autumn-text)}
.tp-page .tp-season--winter{--tp-s-text:var(--tp-s-winter-text)}

/* ─── یک ایرادِ خودِ مرجع ────────────────────────────────────────────
   لایۀ دادۀ مرجع `.tp-altnames` را زیر `.hero__alt` در سربرگ می‌گذاشت،
   ولی شیوه‌نامه‌اش `color:var(--muted)` به آن داده بود — خاکستریِ تیره
   روی عکسِ تیره. خودِ مرجع هم ۲٫۵۹ می‌دهد.  P5-D187
   مقدار تازه‌ای نیست: همان سفیدِ ۷۶٪ که `.hero__alt` مرجع دارد. */
.tp-page .tp-hero .tp-altnames{color:rgba(255,255,255,.76)}

.tp-page .tp-wx-fx{
  position:absolute;inset:0;pointer-events:none;
  opacity:0;transition:opacity .6s linear;
  /* ذره هرگز پشت متن سربرگ نمی‌رود.
     زیرِ overlay بودن لازم است ولی کافی نیست: overlay در بخش بزرگی از
     پهنایش فقط ۲۰ تا ۵۵ درصد کدر است، پس یک ذرۀ روشن از پشتش رد
     می‌شود و کنتراست متن ریز را می‌اندازد. سنجش پیکسلی نشان داد
     «زیر overlay» تنها، یازده ترکیب از پانزده را رد می‌کند.  P5-D197 */
  -webkit-mask-image:linear-gradient(to top,transparent 0%,transparent 42%,#000 66%,#000 100%);
          mask-image:linear-gradient(to top,transparent 0%,transparent 42%,#000 66%,#000 100%);
}
.tp-page .tp-wx-fx.is-on{opacity:1}
.tp-page .tp-wx-fx canvas{display:block;inline-size:100%;block-size:100%}

/* مه ذره ندارد — شیب ماتی که آرام نفس می‌کشد */
.tp-page .tp-wx-fx--fog{
  background:
    linear-gradient(0deg,
      rgba(226,222,214,var(--tp-fog-a,.22)) 0%,
      rgba(226,222,214,calc(var(--tp-fog-a,.22) * .45)) 38%,
      rgba(226,222,214,0) 72%);
  animation:tp-fog-breathe var(--tp-fog-ms,4200ms) ease-in-out infinite;
}
@keyframes tp-fog-breathe{
  0%,100%{opacity:1;transform:translate3d(0,0,0) scale(1)}
  50%    {opacity:.72;transform:translate3d(0,-2%,0) scale(1.04)}
}

/* برق — فلاش CSS، نه ذره. زمان‌بندی‌اش از جاوااسکریپت می‌آید تا
   نامنظم باشد؛ برق دوره‌ای فوراً مصنوعی دیده می‌شود. */
.tp-page .tp-wx-fx__flash{
  position:absolute;inset:0;
  background:radial-gradient(120% 80% at 50% 0%,
    rgba(255,255,255,.55) 0%, rgba(255,255,255,.18) 34%, rgba(255,255,255,0) 68%);
  opacity:0;transition:opacity 90ms linear;
}
.tp-page .tp-wx-fx__flash.is-lit{opacity:1}

@media(prefers-reduced-motion:reduce){
  .tp-page .tp-wx-fx{display:none}
}

/* ═══ TP-ADDED-END ═════════════════════════════════════════════════ */

CSS;

$head = "@charset \"UTF-8\";\n"
	. "/* ==========================================================================\n"
	. "   ToPersia — شیوه‌نامۀ قالب دستۀ G\n"
	. "   نسخه: 3.0.0 — انتقال مستقیم از reference/ToPersia-G3-Province-Template.html\n"
	. "\n"
	. "   ⚠ این فایل با دست نوشته نمی‌شود.  P5-D219\n"
	. "     php scripts/tp-template-gen.php\n"
	. "\n"
	. "   تبدیل‌ها همه مکانیکی‌اند: کلاس‌ها پیشوند tp- گرفته‌اند، متغیرهای مرجع\n"
	. "   به توکن نگاشت شده‌اند، و هر قاعده زیر .tp-page نشسته. رنگ‌های ثابتِ\n"
	. "   مرجع دست‌نخورده‌اند — گرادیان سربرگ، بلوک هوش مصنوعی، پس‌زمینۀ کاشی‌ها،\n"
	. "   سفیدِ روی رسانه. گارد آزمون می‌گوید هیچ مقدار تازه‌ای در فایل نیست:\n"
	. "   هر هگز باید عیناً در مرجع هم باشد.\n"
	. "   ========================================================================== */\n";

/*
 * @import بیرون نمی‌آید.  ۴٫۳٫۰ بند ۱۳
 * تنها @import مرجع Vazirmatn گوگل بود؛ گوگل‌فونت در ایران فیلتر است و
 * رندر را تا پایان مهلت شبکه معطل می‌کند.
 */
$imports = '';

$css = preg_replace( '/^@import.*;$/m', '', $css );

$final = $head . $imports . $reset . "\n" . trim( $css ) . "\n";

/*
 * «ۀ» یکپارچه — P5-D248
 * توضیح‌هایی که از مرجع منتقل می‌شوند «ۀ» دونویسه (U+0647 U+0654) دارند؛
 * خروجی همه U+06C0 می‌شود. خودِ مرجع دست نمی‌خورد.
 */
$final = str_replace( "\u{0647}\u{0654}", "\u{06C0}", $final );

if ( in_array( '--check', $argv, true ) ) {

	$old = is_file( $OUT ) ? file_get_contents( $OUT ) : '';

	if ( $old === $final ) {
		echo "یکی است.\n";
		exit( 0 );
	}

	echo "فرق دارد.\n";
	exit( 1 );
}

if ( in_array( '--map', $argv, true ) ) {
	echo "مرجع              توکن\n";
	foreach ( $VARS as $from => $to ) {
		printf( "--%-16s --%s\n", $from, $to );
	}
	exit( 0 );
}

file_put_contents( $OUT, $final );

printf( "نوشته شد: %s  (%d بایت · %d خط)\n", basename( $OUT ), strlen( $final ), substr_count( $final, "\n" ) );
