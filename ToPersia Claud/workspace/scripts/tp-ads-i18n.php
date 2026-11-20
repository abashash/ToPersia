<?php
/**
 * کاتالوگ ترجمۀ افزونۀ تبلیغ — دامنۀ `topersia-ads`.  دستۀ 54 (P5-D621) · قاعدۀ «هر افزونۀ ما سه‌زبانه»
 *
 *   php scripts/tp-ads-i18n.php            ← می‌سازد: languages/topersia-ads-{en_US,ar,ru_RU}.po · mo · l10n.php
 *   php scripts/tp-ads-i18n.php --check    ← فقط می‌سنجد؛ کد خروج ۱ اگر چیزی کم یا نادرست است
 *   php scripts/tp-ads-i18n.php --list     ← فهرست رشته‌های استخراج‌شده
 *
 * همان روش scripts/tp-i18n.php (render) با دامنۀ دیگر: هر رشتۀ فارسیِ هر پروندۀ PHP افزونه — رابط عمومی و پیشخوان
 * هر دو، چون این افزونه کاتالوگ کامل دارد — توکن‌به‌توکن استخراج می‌شود؛ خطِ دارای «i18n:skip» بیرون است.
 * ترجمه‌ها در scripts/i18n/topersia-ads.php. سنجش: هر msgid انگلیسی و عربی دارد، جانشین‌ها یکی‌اند،
 * انگلیسی بی خط عربی، عربی بی حرف ویژۀ فارسی، جدول بی مدخل بی‌مصرف، خروجی‌ها با جدول یکی.
 * توابع جدا (tpa_) چون tp-i18n.php اسکریپت است نه کتابخانه — بار کردنش کاتالوگ render را می‌سازد.
 */

$ROOT   = dirname( __DIR__ );
$PLUGIN = $ROOT . '/plugin/topersia-ads';
$OUT    = $PLUGIN . '/languages';
/* TP_ADS_I18N_TABLE فقط برای آزمون جهش (assert-54.php) */
$TABLE  = getenv( 'TP_ADS_I18N_TABLE' ) ? getenv( 'TP_ADS_I18N_TABLE' ) : __DIR__ . '/i18n/topersia-ads.php';
$DOMAIN = 'topersia-ads';

$LOCALES = array(
	'en_US' => array( 'key' => 'en', 'plural' => 'nplurals=2; plural=(n != 1);' ),
	'ar'    => array( 'key' => 'ar', 'plural' => 'nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5);' ),
	/* دستۀ 56-الف (P5-D640): روسی جزئی — فقط مدخل‌های دارای 'ru'؛ نبودش خطا نیست، خط عربی در آن خطاست */
	'ru_RU' => array( 'key' => 'ru', 'partial' => true, 'plural' => 'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);' ),
);

function tpa_unquote( $tok ) {
	$q = $tok[0];
	$s = substr( $tok, 1, -1 );
	return "'" === $q ? str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $s ) : stripcslashes( $s );
}

/** هر رشتۀ فارسیِ پرونده ← نام پرونده (بی شمارۀ خط: ویرایش کدی که رشته را عوض نمی‌کند کاتالوگ را کهنه نمی‌کند). بافتِ _x مثل render «بافت\4متن» می‌شود. */
function tpa_extract( $path, $rel ) {
	$toks  = token_get_all( file_get_contents( $path ) );
	$lines = file( $path );
	$n     = count( $toks );
	$out   = array();
	$ctx   = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$t = $toks[ $i ];
		if ( is_array( $t ) && T_STRING === $t[0] && in_array( $t[1], array( '_x', 'esc_html_x', 'esc_attr_x' ), true ) ) {
			$args = array();
			for ( $j = $i + 1; $j < $n && count( $args ) < 2; $j++ ) {
				if ( is_array( $toks[ $j ] ) && T_CONSTANT_ENCAPSED_STRING === $toks[ $j ][0] ) {
					$args[] = array( tpa_unquote( $toks[ $j ][1] ), $j );
				} elseif ( ')' === $toks[ $j ] ) {
					break;
				}
			}
			if ( 2 === count( $args ) ) {
				$out[ $args[1][0] . "\4" . $args[0][0] ] = $rel;
				$ctx[ $args[0][1] ] = $ctx[ $args[1][1] ] = true;
			}
			continue;
		}
		if ( is_array( $t ) && T_CONSTANT_ENCAPSED_STRING === $t[0] && ! isset( $ctx[ $i ] ) ) {
			$s = tpa_unquote( $t[1] );
			if ( preg_match( '/[\x{0600}-\x{06FF}]/u', $s ) && false === strpos( (string) ( $lines[ $t[2] - 1 ] ?? '' ), 'i18n:skip' ) && ! isset( $out[ $s ] ) ) {
				$out[ $s ] = $rel;
			}
		}
	}
	return $out;
}

function tpa_po_str( $s ) {
	return '"' . str_replace( array( '\\', '"', "\n", "\t" ), array( '\\\\', '\\"', '\\n', '\\t' ), $s ) . '"';
}

function tpa_header( $locale, $cfg ) {
	return "Project-Id-Version: ToPersia Ads\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\n"
		. "Language: {$locale}\nPlural-Forms: {$cfg['plural']}\nX-Generator: scripts/tp-ads-i18n.php\nX-Domain: topersia-ads\n";
}

function tpa_po( $locale, $cfg, $plain, $msgs ) {
	$out = 'msgid ""' . "\n" . 'msgstr ""' . "\n";
	foreach ( explode( "\n", rtrim( tpa_header( $locale, $cfg ) ) ) as $h ) {
		$out .= tpa_po_str( $h . "\n" ) . "\n";
	}
	foreach ( $plain as $s => $where ) {
		$out .= "\n#: {$where}\n";
		if ( preg_match( '/%(?:\d+\$)?[sd]/', $s ) ) { $out .= "#, php-format\n"; }
		$parts = explode( "\4", $s, 2 );
		if ( 2 === count( $parts ) ) { $out .= 'msgctxt ' . tpa_po_str( $parts[0] ) . "\n"; }
		$out .= 'msgid ' . tpa_po_str( end( $parts ) ) . "\n" . 'msgstr ' . tpa_po_str( isset( $msgs[ $s ] ) ? $msgs[ $s ] : '' ) . "\n";
	}
	return $out;
}

/** MO گنو با جدول هش خالی — همان قالب tp-i18n.php. */
function tpa_mo( $header, $msgs ) {
	$entries = array( '' => $header ) + $msgs;
	ksort( $entries, SORT_STRING );
	$n     = count( $entries );
	$o_tab = 28;
	$t_tab = $o_tab + 8 * $n;
	$start = $t_tab + 8 * $n;
	$ids   = '';
	$strs  = '';
	$om    = array();
	$tm    = array();
	foreach ( $entries as $id => $str ) { $om[] = array( strlen( $id ), strlen( $ids ) ); $ids .= $id . "\0"; }
	foreach ( $entries as $id => $str ) { $tm[] = array( strlen( $str ), strlen( $strs ) ); $strs .= $str . "\0"; }
	$bin = pack( 'V7', 0x950412de, 0, $n, $o_tab, $t_tab, 0, $start );
	foreach ( $om as $m ) { $bin .= pack( 'VV', $m[0], $start + $m[1] ); }
	foreach ( $tm as $m ) { $bin .= pack( 'VV', $m[0], $start + strlen( $ids ) + $m[1] ); }
	return $bin . $ids . $strs;
}

function tpa_l10n( $locale, $cfg, $msgs ) {
	$data = array(
		'project-id-version' => 'ToPersia Ads',
		'language'           => $locale,
		'plural-forms'       => $cfg['plural'],
		'x-generator'        => 'scripts/tp-ads-i18n.php',
		'messages'           => $msgs,
	);
	return "<?php\n// ساختۀ scripts/tp-ads-i18n.php — دستی ویرایش نشود.\nreturn " . var_export( $data, true ) . ";\n";
}

/* ── استخراج ── */
$plain = array();
$it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $PLUGIN, FilesystemIterator::SKIP_DOTS ) );
$files = array();
foreach ( $it as $f ) {
	$rel = str_replace( '\\', '/', substr( $f->getPathname(), strlen( $PLUGIN ) + 1 ) );
	if ( 'php' === $f->getExtension() && 0 !== strpos( $rel, 'languages/' ) ) { $files[] = $rel; }
}
sort( $files );
foreach ( $files as $rel ) {
	foreach ( tpa_extract( $PLUGIN . '/' . $rel, $rel ) as $s => $w ) {
		if ( ! isset( $plain[ $s ] ) ) { $plain[ $s ] = $w; }
	}
}
ksort( $plain, SORT_STRING );

if ( in_array( '--list', $argv, true ) ) {
	foreach ( $plain as $s => $w ) { echo str_replace( "\4", ' | ', $s ), "\t", $w, "\n"; }
	echo count( $plain ), " strings\n";
	exit( 0 );
}

/* ── سنجش جدول ── */
$table    = is_file( $TABLE ) ? require $TABLE : array();
$errors   = array();
$messages = array( 'en_US' => array(), 'ar' => array(), 'ru_RU' => array() );

$ph = function ( $s ) {
	preg_match_all( '/%(?:\d+\$)?[sd]/', $s, $m );
	$m = $m[0];
	sort( $m );
	return $m;
};

foreach ( $plain as $s => $w ) {
	$parts = explode( "\4", $s, 2 );
	$src   = end( $parts );
	if ( ! isset( $table[ $s ] ) ) {
		$errors[] = "بی ترجمه ({$w}): " . str_replace( "\4", ' | ', $s );
		continue;
	}
	foreach ( $LOCALES as $loc => $cfg ) {
		$tr = isset( $table[ $s ][ $cfg['key'] ] ) ? (string) $table[ $s ][ $cfg['key'] ] : '';
		if ( '' === trim( $tr ) && ! empty( $cfg['partial'] ) ) {
			continue;
		}
		if ( 'ru' === $cfg['key'] && preg_match( '/[\x{0600}-\x{06FF}]/u', $tr ) ) {
			$errors[] = "روسی با خط عربی: {$src}";
		}
		if ( '' === trim( $tr ) ) {
			$errors[] = "ترجمۀ {$cfg['key']} خالی: {$src}";
			continue;
		}
		if ( $ph( str_replace( '%%', '', $src ) ) !== $ph( str_replace( '%%', '', $tr ) ) ) {
			$errors[] = "جانشین ناهمخوان {$cfg['key']}: {$src}";
		}
		if ( 'en' === $cfg['key'] && preg_match( '/[\x{0600}-\x{06FF}]/u', $tr ) ) {
			$errors[] = "انگلیسی با خط عربی: {$src}";
		}
		if ( 'ar' === $cfg['key'] && preg_match( '/[پچژگکی]/u', $tr ) ) {
			$errors[] = "عربی با حرف ویژۀ فارسی: {$src}";
		}
		$messages[ $loc ][ $s ] = $tr;
	}
}
foreach ( array_keys( $table ) as $s ) {
	if ( ! isset( $plain[ $s ] ) ) {
		$errors[] = 'مدخل بی‌مصرف در جدول: ' . str_replace( "\4", ' | ', $s );
	}
}

$built = array();
foreach ( $LOCALES as $loc => $cfg ) {
	ksort( $messages[ $loc ], SORT_STRING );
	$built[ "{$OUT}/{$DOMAIN}-{$loc}.po" ]       = tpa_po( $loc, $cfg, $plain, $messages[ $loc ] );
	$built[ "{$OUT}/{$DOMAIN}-{$loc}.mo" ]       = tpa_mo( tpa_header( $loc, $cfg ), $messages[ $loc ] );
	$built[ "{$OUT}/{$DOMAIN}-{$loc}.l10n.php" ] = tpa_l10n( $loc, $cfg, $messages[ $loc ] );
}

if ( in_array( '--check', $argv, true ) ) {
	foreach ( $built as $file => $content ) {
		if ( ! is_file( $file ) || file_get_contents( $file ) !== $content ) {
			$errors[] = 'خروجی با جدول یکی نیست (php scripts/tp-ads-i18n.php): ' . basename( $file );
		}
	}
	if ( $errors ) {
		echo implode( "\n", $errors ), "\n";
		exit( 1 );
	}
	printf( "کاتالوگ topersia-ads سالم: %d رشته، هر کدام انگلیسی و عربی\n", count( $plain ) );
	exit( 0 );
}

if ( $errors ) {
	fwrite( STDERR, implode( "\n", $errors ) . "\n" );
	exit( 1 );
}
if ( ! is_dir( $OUT ) ) {
	mkdir( $OUT, 0755, true );
}
foreach ( $built as $file => $content ) {
	file_put_contents( $file, $content );
}
printf( "نوشته شد: plugin/topersia-ads/languages/ — %d رشته\n", count( $plain ) );
exit( 0 );
