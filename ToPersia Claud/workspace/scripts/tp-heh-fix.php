<?php
/**
 * «ۀ» یکپارچه در کدِ افزونه‌ها — P5-D248
 *
 *   php scripts/tp-heh-fix.php --report   هر رخدادِ «هٔ» دونویسه‌ای (U+0647 U+0654) با دسته‌اش
 *   php scripts/tp-heh-fix.php --apply    جای‌گزینی با «ۀ» (U+06C0) — همه جز استثناها
 *   php scripts/tp-heh-fix.php --check    فقط شمار: هر رخدادِ بیرون از استثنا کد خروج ۱
 *
 * دامنه: PHP · JS · CSS هر افزونهٔ workspace/plugin (render · user-core ·
 * خلاصه‌ساز؛ هسته از فاز ۲ که به workspace بیاید)، و کاتالوگ ترجمهٔ .po با
 * .mo همتایش. سند (.md) و نسخهٔ ساختار JetEngine (.json) کد نیستند.
 *
 * پروندهٔ ساخته‌شده دست نمی‌خورد: `topersia-g-template.css` خروجی مولد است،
 * پس به‌جایش خودِ `scripts/tp-template-gen.php` تبدیل می‌شود و مولد در پایان
 * هر «هٔ» دونویسه‌ای را که از توضیح‌های مرجع می‌آید یکی می‌کند. --check
 * خروجی ساخته‌شده را هم می‌شمارد — هر رخداد آنجا یعنی مولد اجرا نشده.
 *
 * دسته‌ها — هر رخداد پیش از جای‌گزینی سنجیده می‌شود:
 *   string · comment · html · css · po      جای‌گزینی
 *   regex      آرگومان نخستِ preg_* یا لفظِ RegExp در JS            استثنا
 *   key        کلید آرایه (`'…' =>`)، دسترسی کلید (`[ '…' ]`)، کلید شیء JS  استثنا
 *   compare    سوی `===`/`==`/`!==`/`!=`، `case '…':`، یا آرگومانِ تابعِ
 *              مقایسه (strpos · in_array · str_replace · indexOf …)   استثنا
 *
 * استثنا یعنی رشته‌ای که ممکن است با دادهٔ پایگاه‌داده یا ورودی کاربر
 * سنجیده شود؛ دادهٔ پایگاه‌داده دست نمی‌خورد، پس آن رشته هم نه. فهرست
 * استثناها در --report چاپ می‌شود و گاردِ `tests/assert-440.php` همان را
 * بسته نگه می‌دارد.
 */

$ROOT    = dirname( __DIR__ );
$PLUGINS = $ROOT . '/plugin';
$TWO     = "\u{0647}\u{0654}";
$ONE     = "\u{06C0}";
$MODE    = in_array( '--apply', $argv, true ) ? 'apply' : ( in_array( '--check', $argv, true ) ? 'check' : 'report' );

/* خروجیِ ساخته‌شده => مولدش. خودِ خروجی تبدیل نمی‌شود؛ مولد تبدیل می‌شود. */
const TP_HEH_GENERATED = array(
	'plugin/topersia-render/assets/topersia-g-template.css' => 'scripts/tp-template-gen.php',
);

/* ── پیمایش ────────────────────────────────────────────────────────── */

function tp_heh_files( $root ) {

	$out = array();
	$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );

	foreach ( $it as $f ) {

		if ( ! $f->isFile() ) {
			continue;
		}

		$path = strtr( $f->getPathname(), array( '\\' => '/' ) );

		if ( preg_match( '#\.(php|js|css|po)$#', $path ) ) {
			$out[] = $path;
		}
	}

	sort( $out, SORT_STRING );

	return $out;
}

/* ── PHP: توکن‌به‌توکن ─────────────────────────────────────────────── */

const TP_HEH_COMPARE_FN = array(
	'strpos', 'stripos', 'strrpos', 'str_contains', 'str_starts_with', 'str_ends_with', 'mb_strpos', 'mb_stripos',
	'in_array', 'array_search', 'array_keys', 'strcmp', 'strcasecmp', 'strncmp', 'substr_count', 'str_replace',
	'str_ireplace', 'strtr', 'array_key_exists', 'preg_match', 'preg_match_all', 'preg_replace', 'preg_split',
	'preg_replace_callback', 'preg_quote',
);

function tp_heh_sig( $tokens, $i, $step ) {

	for ( $j = $i + $step; $j >= 0 && $j < count( $tokens ); $j += $step ) {

		$t = $tokens[ $j ];

		if ( is_array( $t ) && in_array( $t[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}

		return array( $j, $t );
	}

	return array( -1, null );
}

/** نام تابعی که این آرگومان داخل آن است — یا رشتهٔ خالی. */
function tp_heh_call( $tokens, $i ) {

	$depth = 0;

	for ( $j = $i - 1; $j >= 0; $j -- ) {

		$t = $tokens[ $j ];

		if ( ')' === $t || ']' === $t ) { $depth ++; continue; }
		if ( '[' === $t ) { if ( $depth ) { $depth --; continue; } return ''; }
		if ( '(' === $t ) {
			if ( $depth ) { $depth --; continue; }
			list( , $name ) = tp_heh_sig( $tokens, $j, -1 );
			return is_array( $name ) && T_STRING === $name[0] ? strtolower( $name[1] ) : '';
		}
		if ( ';' === $t || '{' === $t || '}' === $t ) {
			return '';
		}
	}

	return '';
}

function tp_heh_php_class( $tokens, $i ) {

	$t = $tokens[ $i ];

	if ( T_COMMENT === $t[0] || T_DOC_COMMENT === $t[0] ) {
		return 'comment';
	}

	if ( T_INLINE_HTML === $t[0] ) {
		return 'html';
	}

	list( , $prev ) = tp_heh_sig( $tokens, $i, -1 );
	list( , $next ) = tp_heh_sig( $tokens, $i, 1 );

	if ( is_array( $next ) && T_DOUBLE_ARROW === $next[0] ) {
		return 'key';
	}

	if ( '[' === $prev && ']' === $next ) {
		return 'key';
	}

	$eq = array( T_IS_IDENTICAL, T_IS_NOT_IDENTICAL, T_IS_EQUAL, T_IS_NOT_EQUAL );

	if ( ( is_array( $prev ) && ( in_array( $prev[0], $eq, true ) || T_CASE === $prev[0] ) )
		|| ( is_array( $next ) && in_array( $next[0], $eq, true ) ) ) {
		return 'compare';
	}

	$fn = tp_heh_call( $tokens, $i );

	if ( '' !== $fn && 0 === strpos( $fn, 'preg_' ) ) {
		/* فقط الگو regex است؛ رشتهٔ جای‌گزینی و موضوع، مقایسه‌ای. */
		return 'regex';
	}

	if ( in_array( $fn, TP_HEH_COMPARE_FN, true ) ) {
		return 'compare';
	}

	return 'string';
}

function tp_heh_php( $path, $src, $TWO ) {

	$hits = array();

	foreach ( token_get_all( $src ) as $i => $t ) {
		$hits[] = $t;
	}

	$tokens = $hits;
	$hits   = array();
	$line   = 1;

	foreach ( $tokens as $i => $t ) {

		if ( is_array( $t ) ) {

			$line = $t[2];

			if ( false !== strpos( $t[1], $TWO ) ) {
				$hits[] = array( 'i' => $i, 'line' => $line, 'class' => tp_heh_php_class( $tokens, $i ), 'text' => $t[1] );
			}
		}
	}

	return array( $tokens, $hits );
}

/* ── JS و CSS: واژه‌کاوِ کوچک ─────────────────────────────────────────
   رشته ('…' "…" `…`)، توضیح، لفظ regex (پس از یکی از «( , = : [ ! & | ? { } ; return typeof»)
   و بقیه. برای هر تکه‌ای که «هٔ» دارد دسته برمی‌گردد. */
function tp_heh_lex( $src, $js ) {

	$out = array();
	$n   = strlen( $src );
	$i   = 0;
	$buf = '';
	$last = '';   /* آخرین نویسهٔ معنادار بیرون از رشته/توضیح */

	$flush = function () use ( &$buf, &$out ) {
		if ( '' !== $buf ) { $out[] = array( 'code', $buf ); $buf = ''; }
	};

	while ( $i < $n ) {

		$c  = $src[ $i ];
		$c2 = $i + 1 < $n ? $src[ $i + 1 ] : '';

		if ( '/' === $c && '*' === $c2 ) {
			$flush();
			$end   = strpos( $src, '*/', $i + 2 );
			$end   = false === $end ? $n : $end + 2;
			$out[] = array( 'comment', substr( $src, $i, $end - $i ) );
			$i     = $end;
			continue;
		}

		if ( $js && '/' === $c && '/' === $c2 ) {
			$flush();
			$end   = strpos( $src, "\n", $i );
			$end   = false === $end ? $n : $end;
			$out[] = array( 'comment', substr( $src, $i, $end - $i ) );
			$i     = $end;
			continue;
		}

		if ( '"' === $c || "'" === $c || ( $js && '`' === $c ) ) {
			$flush();
			$j = $i + 1;
			while ( $j < $n && $src[ $j ] !== $c ) {
				$j += ( '\\' === $src[ $j ] ) ? 2 : 1;
			}
			$out[] = array( 'str', substr( $src, $i, $j + 1 - $i ) );
			$i     = $j + 1;
			$last  = 'x';
			continue;
		}

		if ( $js && '/' === $c && ( '' === $last || false !== strpos( '(,=:[!&|?{};', $last ) || preg_match( '/(return|typeof)\s*$/', $buf ) ) ) {
			$flush();
			$j = $i + 1;
			$in_class = false;
			while ( $j < $n && ( '/' !== $src[ $j ] || $in_class ) && "\n" !== $src[ $j ] ) {
				if ( '\\' === $src[ $j ] ) { $j += 2; continue; }
				if ( '[' === $src[ $j ] ) { $in_class = true; }
				if ( ']' === $src[ $j ] ) { $in_class = false; }
				$j ++;
			}
			$j ++;
			while ( $j < $n && ctype_alpha( $src[ $j ] ) ) { $j ++; }
			$out[] = array( 'regex', substr( $src, $i, $j - $i ) );
			$i     = $j;
			$last  = 'x';
			continue;
		}

		$buf .= $c;

		if ( ! ctype_space( $c ) ) {
			$last = $c;
		}

		$i ++;
	}

	$flush();

	return $out;
}

function tp_heh_js_class( $parts, $k ) {

	list( $kind, $text ) = $parts[ $k ];

	if ( 'comment' === $kind ) {
		return 'comment';
	}

	if ( 'regex' === $kind ) {
		return 'regex';
	}

	$before = $k > 0 && 'code' === $parts[ $k - 1 ][0] ? rtrim( $parts[ $k - 1 ][1] ) : '';
	$after  = isset( $parts[ $k + 1 ] ) && 'code' === $parts[ $k + 1 ][0] ? ltrim( $parts[ $k + 1 ][1] ) : '';

	if ( preg_match( '/(===|!==|==|!=|case)$/', $before ) || preg_match( '/^(===|!==|==|!=)/', $after ) ) {
		return 'compare';
	}

	if ( preg_match( '/(indexOf|includes|test|replace|split|startsWith|endsWith|RegExp|match|search)\s*\(\s*$/', $before ) ) {
		return preg_match( '/RegExp\s*\(\s*$/', $before ) ? 'regex' : 'compare';
	}

	if ( preg_match( '/^:/', $after ) && preg_match( '/[{,]$/', $before ) ) {
		return 'key';
	}

	return 'string';
}

/* ── نوشتن .mo — همان قالب گنو، بی جدول درهم‌سازی ─────────────────────
   ورودی مدخل‌ها از خودِ .mo پیشین می‌آید (tp_heh_mo_rewrite)، نه از خواندن .po:
   خوانندهٔ .po نسخهٔ اول `preg_split( '/\R/' )` بی u داشت و بایت 0x85 را — نیمهٔ دومِ
   «م» در UTF-8 — سطر تازه می‌گرفت. برداشته شد. */
function tp_heh_mo( $entries ) {

	$pairs = array();

	foreach ( $entries as $e ) {

		$key = ( isset( $e['msgctxt'] ) ? $e['msgctxt'] . "\x04" : '' ) . $e['msgid'];

		if ( isset( $e['msgid_plural'] ) ) {
			$key .= "\0" . $e['msgid_plural'];
			$strs = array();
			for ( $n = 0; isset( $e[ 'msgstr[' . $n . ']' ] ); $n ++ ) { $strs[] = $e[ 'msgstr[' . $n . ']' ]; }
			$val = implode( "\0", $strs );
		} else {
			$val = isset( $e['msgstr'] ) ? $e['msgstr'] : '';
		}

		if ( '' === $val && '' !== $e['msgid'] ) {
			continue;   /* ترجمه‌نشده در .mo نمی‌آید — همان رفتار msgfmt */
		}

		$pairs[ $key ] = $val;
	}

	ksort( $pairs, SORT_STRING );

	$n      = count( $pairs );
	$o_tab  = 28;
	$t_tab  = $o_tab + 8 * $n;
	$data   = $t_tab + 8 * $n;
	$ids    = '';
	$strs   = '';
	$o_rows = '';
	$t_rows = '';

	foreach ( $pairs as $k => $v ) {
		$o_rows .= pack( 'VV', strlen( $k ), $data + strlen( $ids ) );
		$ids    .= $k . "\0";
	}

	$sbase = $data + strlen( $ids );

	foreach ( $pairs as $k => $v ) {
		$t_rows .= pack( 'VV', strlen( $v ), $sbase + strlen( $strs ) );
		$strs   .= $v . "\0";
	}

	/* جدول درهم‌سازی خالی است ولی نشانی‌اش باید درست پس از جدول ترجمه‌ها
	   باشد — خوانندهٔ وردپرس (pomo/mo.php) طول جدول ترجمه را از همین
	   تفاضل می‌سنجد و با نشانی صفر پرونده را رد می‌کند. */
	return pack( 'VVVVVVV', 0x950412de, 0, $n, $o_tab, $t_tab, 0, $data ) . $o_rows . $t_rows . $ids . $strs;
}

/**
 * جفت‌های یک .mo موجود — تا بازنویسی فقط همان «هٔ» را عوض کند و هیچ
 * مدخلی (مبهم، حذف‌شده، ترتیب) از راه ساختِ دوباره از .po جابه‌جا نشود.
 */
function tp_heh_mo_read( $bin ) {

	$magic = unpack( 'V', substr( $bin, 0, 4 ) )[1];
	$f     = 0x950412de === $magic ? 'V' : 'N';
	$h     = unpack( $f . 'rev/' . $f . 'n/' . $f . 'o/' . $f . 't', substr( $bin, 4, 16 ) );
	$pairs = array();

	for ( $k = 0; $k < $h['n']; $k ++ ) {
		$o = unpack( $f . 'len/' . $f . 'at', substr( $bin, $h['o'] + 8 * $k, 8 ) );
		$t = unpack( $f . 'len/' . $f . 'at', substr( $bin, $h['t'] + 8 * $k, 8 ) );
		$pairs[ substr( $bin, $o['at'], $o['len'] ) ] = substr( $bin, $t['at'], $t['len'] );
	}

	return $pairs;
}

/** همان جفت‌ها با «ۀ» — کلید و مقدار — در قالب .mo */
function tp_heh_mo_rewrite( $bin, $TWO, $ONE ) {

	$entries = array();

	foreach ( tp_heh_mo_read( $bin ) as $k => $v ) {

		$k    = str_replace( $TWO, $ONE, $k );
		$ctx  = strpos( $k, "\x04" );
		$e    = false === $ctx ? array() : array( 'msgctxt' => substr( $k, 0, $ctx ) );
		$id   = false === $ctx ? $k : substr( $k, $ctx + 1 );
		$v    = str_replace( $TWO, $ONE, $v );

		if ( false !== strpos( $id, "\0" ) ) {
			list( $e['msgid'], $e['msgid_plural'] ) = explode( "\0", $id, 2 );
			foreach ( explode( "\0", $v ) as $n => $s ) {
				$e[ 'msgstr[' . $n . ']' ] = $s;
			}
		} else {
			$e['msgid']  = $id;
			$e['msgstr'] = $v;
		}

		$entries[] = $e;
	}

	return tp_heh_mo( $entries );
}

/* ── اجرا ───────────────────────────────────────────────────────────── */

$counts    = array();
$excepted  = array();
$left      = 0;
$changed   = array();

$targets = tp_heh_files( $PLUGINS );
foreach ( array_unique( array_values( TP_HEH_GENERATED ) ) as $gen ) {
	$targets[] = strtr( $ROOT, array( '\\' => '/' ) ) . '/' . $gen;
}

$generated = array();

foreach ( $targets as $path ) {

	$rel = substr( $path, strlen( $ROOT ) + 1 );
	$src = file_get_contents( $path );

	if ( false === strpos( $src, $TWO ) ) {
		continue;
	}

	if ( isset( TP_HEH_GENERATED[ $rel ] ) ) {
		/* پس از اجرای مولد باید صفر باشد؛ --check آن را باقی‌مانده می‌شمارد */
		$generated[ $rel ] = substr_count( $src, $TWO );
		continue;
	}

	$ext = pathinfo( $path, PATHINFO_EXTENSION );
	$new = $src;

	if ( 'php' === $ext ) {

		list( $tokens, $hits ) = tp_heh_php( $path, $src, $TWO );

		foreach ( $hits as $h ) {

			$n = substr_count( $h['text'], $TWO );
			$counts[ $h['class'] ] = ( isset( $counts[ $h['class'] ] ) ? $counts[ $h['class'] ] : 0 ) + $n;

			if ( in_array( $h['class'], array( 'regex', 'key', 'compare' ), true ) ) {
				$excepted[] = array( $rel, $h['line'], $h['class'], trim( preg_replace( '/\s+/u', ' ', mb_substr( $h['text'], 0, 90 ) ) ) );
				continue;
			}

			$tokens[ $h['i'] ][1] = str_replace( $TWO, $ONE, $h['text'] );
		}

		$new = '';
		foreach ( $tokens as $t ) {
			$new .= is_array( $t ) ? $t[1] : $t;
		}

	} elseif ( 'po' === $ext ) {

		$counts['po'] = ( isset( $counts['po'] ) ? $counts['po'] : 0 ) + substr_count( $src, $TWO );
		$new          = str_replace( $TWO, $ONE, $src );

	} else {

		$parts = tp_heh_lex( $src, 'js' === $ext );
		$new   = '';

		foreach ( $parts as $k => $part ) {

			if ( false === strpos( $part[1], $TWO ) ) {
				$new .= $part[1];
				continue;
			}

			$class = 'css' === $ext ? ( 'comment' === $part[0] ? 'comment' : 'css' ) : tp_heh_js_class( $parts, $k );
			$n     = substr_count( $part[1], $TWO );

			$counts[ $class ] = ( isset( $counts[ $class ] ) ? $counts[ $class ] : 0 ) + $n;

			if ( in_array( $class, array( 'regex', 'key', 'compare' ), true ) ) {
				$line       = substr_count( $new, "\n" ) + 1;
				$excepted[] = array( $rel, $line, $class, trim( preg_replace( '/\s+/u', ' ', mb_substr( $part[1], 0, 90 ) ) ) );
				$new       .= $part[1];
				continue;
			}

			$new .= str_replace( $TWO, $ONE, $part[1] );
		}
	}

	$remain = substr_count( $new, $TWO );
	$left  += $remain;

	if ( $new !== $src ) {
		$changed[ $rel ] = substr_count( $src, $TWO ) - $remain;
		if ( 'apply' === $MODE ) {
			file_put_contents( $path, $new );
			/* کاتالوگ ترجمه: .mo همتا از خودش بازنویسی می‌شود — همان مدخل‌ها */
			$mo = substr( $path, 0, -3 ) . '.mo';
			if ( 'po' === $ext && is_file( $mo ) ) {
				file_put_contents( $mo, tp_heh_mo_rewrite( file_get_contents( $mo ), $TWO, $ONE ) );
				$changed[ substr( $rel, 0, -3 ) . '.mo' ] = 'همتای .po';
			}
		}
	}
}

/* .mo بی‌همتای .po هم کاتالوگ است — فقط شمرده می‌شود */
foreach ( glob( $PLUGINS . '/*/languages/*.mo' ) as $mo ) {
	$rel = substr( strtr( $mo, array( '\\' => '/' ) ), strlen( strtr( $ROOT, array( '\\' => '/' ) ) ) + 1 );
	$p   = tp_heh_mo_read( file_get_contents( $mo ) );
	$n   = substr_count( implode( "\n", array_keys( $p ) ) . "\n" . implode( "\n", $p ), $TWO );
	if ( $n && ! isset( $changed[ $rel ] ) ) {
		$generated[ $rel ] = $n;
	}
}

if ( 'check' === $MODE ) {
	/* باقی‌ماندهٔ بیرون از استثنا — یعنی آنچه --apply یا مولد می‌بایست عوض کند */
	$pending = array_sum( array_map( function ( $v ) { return is_int( $v ) ? $v : 0; }, $changed ) ) + array_sum( $generated );
	echo json_encode( array( 'pending' => $pending, 'excepted' => $excepted, 'files' => array_merge( array_keys( $changed ), array_keys( $generated ) ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
	exit( $pending ? 1 : 0 );
}

echo ( 'apply' === $MODE ? "جای‌گزین شد" : "گزارش — هیچ پرونده‌ای عوض نشد" ), "\n\n";
echo "دسته‌ها:\n";
ksort( $counts );
foreach ( $counts as $k => $v ) {
	printf( "  %-8s %d\n", $k, $v );
}
echo "\nپرونده‌ها:\n";
foreach ( $changed as $f => $n ) {
	printf( "  %s  %s\n", $f, $n );
}
echo "\nساخته‌شده یا کاتالوگ — اینجا عوض نمی‌شود (مولد را اجرا کن):\n";
foreach ( $generated as $f => $n ) {
	printf( "  %s  %d\n", $f, $n );
}
echo "\nاستثنا (" . count( $excepted ) . "):\n";
foreach ( $excepted as $e ) {
	printf( "  %s:%d  %s  %s\n", $e[0], $e[1], $e[2], $e[3] );
}
echo "\nباقی‌مانده پس از جای‌گزینی: {$left}\n";
