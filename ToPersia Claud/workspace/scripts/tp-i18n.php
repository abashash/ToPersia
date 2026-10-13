<?php
/**
 * کاتالوگ ترجمۀ «هستۀ نمایش» — دامنۀ `topersia`.  ۴٫۴٫۲ · P5-D146
 *
 *   php scripts/tp-i18n.php            ← می‌سازد: languages/topersia.pot و برای هر زبان po · mo · l10n.php
 *   php scripts/tp-i18n.php --check    ← فقط می‌سنجد؛ کد خروج ۱ اگر چیزی کم یا نادرست است
 *   php scripts/tp-i18n.php --list     ← فهرست رشته‌های استخراج‌شده
 *
 * منبع هر رشته فارسی است و در خودِ کد نشسته. استخراج توکن‌به‌توکن است، نه
 * با regex:
 *   ۱ هر رشتۀ فارسیِ پرونده‌های عمومی افزونه — چه در `__()` و `esc_html__()`
 *     باشد، چه در جدولی که در خروجی از `topersia_g_t()` می‌گذرد (نقشۀ
 *     فیلد، نقشۀ رابطه، نوار بخش‌ها، واژه‌نامه، ماه‌ها). پس رشتۀ تازه‌ای که
 *     ترجمه ندارد، هر جا نوشته شود، `--check` را قرمز می‌کند
 *   ۲ `_n()` جدا — msgid و msgid_plural (در فارسی یکی‌اند؛ عدد مفرد می‌خواهد)
 *   بیرون می‌مانند: regex، رقم‌ها، جداکننده‌ها، و پیام‌های لاگ پیشخوان
 *
 * ترجمه‌ها در `scripts/i18n/topersia-render.php` — جدولِ fa ← en · ar، کنار هم
 * تا بازبینی‌شان یک‌جا باشد. po · mo · l10n.php خروجی‌اند و در افزونه می‌روند
 * (وردپرس ۶٫۵+ اول l10n.php را می‌خواند).
 *
 * سنجش `--check`:
 *   هر msgid ترجمۀ انگلیسی و عربی دارد · جانشین‌ها (%s · %1$s) در ترجمه همان‌اند
 *   ترجمۀ انگلیسی هیچ نویسۀ عربی‌خط ندارد · ترجمۀ عربی هیچ حرف ویژۀ فارسی
 *   (پ چ ژ گ ک ی) ندارد · جدولِ ترجمه مدخل بی‌مصرف ندارد
 *   خروجی‌های ساخته‌شده با جدول یکی‌اند (کسی po را دستی ویرایش نکرده)
 */

$ROOT   = dirname( __DIR__ );
$PLUGIN = $ROOT . '/plugin/topersia-render';
$OUT    = $PLUGIN . '/languages';
/* TP_I18N_TABLE فقط برای آزمون جهش (assert-442.php) — جدولِ ناقص باید `--check` را بیندازد */
$TABLE  = getenv( 'TP_I18N_TABLE' ) ? getenv( 'TP_I18N_TABLE' ) : __DIR__ . '/i18n/topersia-render.php';
$DOMAIN = 'topersia';

/* پرونده‌های عمومی — پیشخوان (topersia-settings.php) فارسی می‌ماند */
$FILES = array(
	'topersia-render.php',
	'includes/topersia-g-core.php',
	'includes/topersia-g-map.php',
	'includes/topersia-g-weather.php',
	'includes/topersia-g-zones.php',
	'includes/topersia-g-ai.php',
	'includes/topersia-g-news.php',
	'includes/topersia-g-gallery-agg.php',
	'includes/topersia-g-relreg.php',
	'includes/topersia-g-panel.php',
	'includes/topersia-g-routing.php',
	'includes/topersia-g-air.php',
	'includes/topersia-g-mappoints.php',
	'templates/news-list.php',
);

$LOCALES = array(
	'en_US' => array( 'key' => 'en', 'lang' => 'en', 'plural' => 'nplurals=2; plural=(n != 1);', 'n' => 2 ),
	'ar'    => array( 'key' => 'ar', 'lang' => 'ar', 'plural' => 'nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 ? 4 : 5);', 'n' => 6 ),
);

/* ── استخراج ─────────────────────────────────────────────────────────── */

/**
 * رشته‌ای که کلید ترجمه نیست — regex، رقم، جداکننده، پیام لاگ.
 */
function tpi_skip( $s ) {
	if ( preg_match( '#^/.*/[a-z]*$#su', $s ) ) { return true; }                 // regex
	if ( preg_match( '/^[\s،٬٫,.·۰-۹٠-٩]+$/u', $s ) ) { return true; }          // رقم و جداکننده
	if ( preg_match( '/^شورت‌کد $| از قبل ثبت شده بود؛ ثبت نشد\.$/u', $s ) ) { return true; } // لاگ
	return false;
}

function tpi_unquote( $tok ) {
	$q = $tok[0];
	$s = substr( $tok, 1, -1 );
	return "'" === $q ? str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $s ) : stripcslashes( $s );
}

function tpi_extract( $path ) {

	$toks  = token_get_all( file_get_contents( $path ) );
	$n     = count( $toks );
	$plain = array();
	$plur  = array();
	$in_n  = array();   // اندیس توکن‌هایی که آرگومان _n یا بافتِ _x اند

	for ( $i = 0; $i < $n; $i++ ) {

		$t = $toks[ $i ];

		/* _x( 'متن', 'بافت', … ) ← کلید «بافت\4متن»، همان شکلی که mo و l10n.php می‌خوانند */
		if ( is_array( $t ) && T_STRING === $t[0] && in_array( $t[1], array( '_x', 'esc_html_x', 'esc_attr_x' ), true ) ) {
			$args = array();
			$idx  = array();
			for ( $j = $i + 1; $j < $n && count( $args ) < 2; $j++ ) {
				if ( is_array( $toks[ $j ] ) && T_CONSTANT_ENCAPSED_STRING === $toks[ $j ][0] ) {
					$args[] = tpi_unquote( $toks[ $j ][1] );
					$idx[]  = $j;
				} elseif ( ')' === $toks[ $j ] ) {
					break;
				}
			}
			if ( 2 === count( $args ) ) {
				$plain[ $args[1] . "\4" . $args[0] ] = $t[2];
				foreach ( $idx as $j ) { $in_n[ $j ] = true; }
			}
			continue;
		}

		/* _n( 'مفرد', 'جمع', … ) */
		if ( is_array( $t ) && T_STRING === $t[0] && in_array( $t[1], array( '_n', '_nx' ), true ) ) {
			$args = array();
			$idx  = array();
			for ( $j = $i + 1; $j < $n && count( $args ) < 2; $j++ ) {
				if ( is_array( $toks[ $j ] ) && T_CONSTANT_ENCAPSED_STRING === $toks[ $j ][0] ) {
					$args[] = tpi_unquote( $toks[ $j ][1] );
					$idx[]  = $j;
				} elseif ( ')' === $toks[ $j ] ) {
					break;
				}
			}
			if ( 2 === count( $args ) ) {
				$plur[ $args[0] ] = array( $args[0], $args[1], $t[2] );
				foreach ( $idx as $j ) { $in_n[ $j ] = true; }
			}
			continue;
		}

		if ( is_array( $t ) && T_CONSTANT_ENCAPSED_STRING === $t[0] && ! isset( $in_n[ $i ] ) ) {
			$s = tpi_unquote( $t[1] );
			if ( preg_match( '/[\x{0600}-\x{06FF}]/u', $s ) && ! tpi_skip( $s ) ) {
				$plain[ $s ] = isset( $plain[ $s ] ) ? $plain[ $s ] : $t[2];
			}
		}
	}

	return array( $plain, $plur );
}

$plain = array();   // msgid ← «پرونده:خط»
$plur  = array();   // msgid ← [مفرد، جمع، «پرونده:خط»]

foreach ( $FILES as $rel ) {
	list( $p, $q ) = tpi_extract( $PLUGIN . '/' . $rel );
	foreach ( $p as $s => $line ) { if ( ! isset( $plain[ $s ] ) ) { $plain[ $s ] = $rel; } }
	foreach ( $q as $s => $row ) { if ( ! isset( $plur[ $s ] ) ) { $plur[ $s ] = array( $row[0], $row[1], $rel ); } }
}

/* رشته‌ای که هم ساده آمده هم در _n، فقط جمع است */
foreach ( array_keys( $plur ) as $s ) { unset( $plain[ $s ] ); }

ksort( $plain );
ksort( $plur );

if ( in_array( '--list', $argv, true ) ) {
	foreach ( $plain as $s => $where ) { echo $s, "\t", $where, "\n"; }
	foreach ( $plur as $s => $row ) { echo '[n] ', $s, "\t", $row[2], "\n"; }
	echo count( $plain ), ' + ', count( $plur ), " plural\n";
	exit( 0 );
}

/* ── جدول ترجمه ──────────────────────────────────────────────────────── */

$table = is_file( $TABLE ) ? require $TABLE : array();
$errors = array();

$ARABIC_SCRIPT = '/[\x{0600}-\x{06FF}]/u';
$PERSIAN_ONLY  = '/[پچژگکی]/u';

function tpi_placeholders( $s ) {
	preg_match_all( '/%(?:\d+\$)?[sd]/', $s, $m );
	$p = $m[0];
	sort( $p );
	return $p;
}

$messages = array();   // locale ← [ msgid(+\0plural) ← msgstr(\0 forms) ]

foreach ( $LOCALES as $locale => $cfg ) {
	$messages[ $locale ] = array();
}

foreach ( $plain as $s => $where ) {

	$row = isset( $table[ $s ] ) ? $table[ $s ] : null;

	foreach ( $LOCALES as $locale => $cfg ) {

		$tr = ( is_array( $row ) && isset( $row[ $cfg['key'] ] ) ) ? $row[ $cfg['key'] ] : '';

		if ( ! is_string( $tr ) || '' === trim( $tr ) ) {
			$errors[] = "بی ترجمۀ {$cfg['key']}: «{$s}»  ({$where})";
			continue;
		}
		if ( tpi_placeholders( $s ) !== tpi_placeholders( $tr ) ) {
			$errors[] = "جانشینِ ناهمخوان {$cfg['key']}: «{$s}» ← «{$tr}»";
		}
		if ( 'en' === $cfg['key'] && preg_match( $ARABIC_SCRIPT, $tr ) ) {
			$errors[] = "نویسۀ عربی‌خط در ترجمۀ انگلیسی: «{$s}» ← «{$tr}»";
		}
		if ( 'ar' === $cfg['key'] && preg_match( $PERSIAN_ONLY, $tr ) ) {
			$errors[] = "حرف ویژۀ فارسی در ترجمۀ عربی: «{$s}» ← «{$tr}»";
		}

		$messages[ $locale ][ $s ] = $tr;
	}
}

foreach ( $plur as $s => $pair ) {

	$row = isset( $table[ $s ] ) ? $table[ $s ] : null;

	foreach ( $LOCALES as $locale => $cfg ) {

		$forms = ( is_array( $row ) && isset( $row[ $cfg['key'] ] ) ) ? $row[ $cfg['key'] ] : null;

		if ( ! is_array( $forms ) || count( $forms ) !== $cfg['n'] ) {
			$errors[] = "جمعِ {$cfg['key']} باید {$cfg['n']} شکل داشته باشد: «{$s}»  ({$pair[2]})";
			continue;
		}
		/* شکل مفرد و مثنای عربی عدد نمی‌خواهد («صورة واحدة» · «صورتان»)؛ جانشینِ بیگانه نه */
		foreach ( $forms as $f ) {
			if ( ! is_string( $f ) || '' === trim( $f ) || array_diff( tpi_placeholders( $f ), tpi_placeholders( $s ) )
				|| ( 'en' === $cfg['key'] && tpi_placeholders( $s ) !== tpi_placeholders( $f ) )
				|| ( 'en' === $cfg['key'] && preg_match( $ARABIC_SCRIPT, $f ) )
				|| ( 'ar' === $cfg['key'] && preg_match( $PERSIAN_ONLY, $f ) ) ) {
				$errors[] = "شکل جمعِ نادرست {$cfg['key']}: «{$s}» ← «{$f}»";
			}
		}

		$messages[ $locale ][ $pair[0] . "\0" . $pair[1] ] = implode( "\0", $forms );
	}
}

foreach ( array_keys( $table ) as $s ) {
	if ( ! isset( $plain[ $s ] ) && ! isset( $plur[ $s ] ) ) {
		$errors[] = "مدخلِ بی‌مصرف در جدول ترجمه: «{$s}»";
	}
}

/* ── ساخت ────────────────────────────────────────────────────────────── */

function tpi_po_str( $s ) {
	$s = str_replace( array( '\\', '"', "\n", "\t" ), array( '\\\\', '\\"', '\\n', '\\t' ), $s );
	return '"' . $s . '"';
}

function tpi_header( $locale, $cfg ) {
	return "Project-Id-Version: ToPersia Render\n"
		. "MIME-Version: 1.0\n"
		. "Content-Type: text/plain; charset=UTF-8\n"
		. "Content-Transfer-Encoding: 8bit\n"
		. ( $cfg ? "Language: {$locale}\nPlural-Forms: {$cfg['plural']}\n" : "Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\n" )
		. "X-Generator: scripts/tp-i18n.php\n"
		. "X-Domain: topersia\n";
}

function tpi_po( $locale, $cfg, $plain, $plur, $msgs ) {

	$out = 'msgid ""' . "\n" . 'msgstr ""' . "\n";
	foreach ( explode( "\n", rtrim( tpi_header( $locale, $cfg ) ) ) as $h ) {
		$out .= tpi_po_str( $h . "\n" ) . "\n";
	}

	foreach ( $plain as $s => $where ) {
		$out .= "\n#: {$where}\n";
		if ( preg_match( '/%(?:\d+\$)?[sd]/', $s ) ) { $out .= "#, php-format\n"; }
		$parts = explode( "\4", $s, 2 );
		if ( 2 === count( $parts ) ) {
			$out .= 'msgctxt ' . tpi_po_str( $parts[0] ) . "\n";
		}
		$out .= 'msgid ' . tpi_po_str( end( $parts ) ) . "\n" . 'msgstr ' . tpi_po_str( $cfg && isset( $msgs[ $s ] ) ? $msgs[ $s ] : '' ) . "\n";
	}

	foreach ( $plur as $s => $pair ) {
		$out .= "\n#: {$pair[2]}\n#, php-format\n";
		$out .= 'msgid ' . tpi_po_str( $pair[0] ) . "\n" . 'msgid_plural ' . tpi_po_str( $pair[1] ) . "\n";
		$forms = $cfg && isset( $msgs[ $pair[0] . "\0" . $pair[1] ] ) ? explode( "\0", $msgs[ $pair[0] . "\0" . $pair[1] ] ) : array( '', '' );
		foreach ( $forms as $k => $f ) {
			$out .= 'msgstr[' . $k . '] ' . tpi_po_str( $f ) . "\n";
		}
	}

	return $out;
}

/** قالب MO گنو — جدول هش خالی؛ وردپرس و gettext همین را می‌خوانند. */
function tpi_mo( $header, $msgs ) {

	$entries = array( '' => $header ) + $msgs;
	ksort( $entries, SORT_STRING );

	$n      = count( $entries );
	$o_tab  = 28;
	$t_tab  = $o_tab + 8 * $n;
	$start  = $t_tab + 8 * $n;
	$ids    = '';
	$strs   = '';
	$o_meta = array();
	$t_meta = array();

	foreach ( $entries as $id => $str ) {
		$o_meta[] = array( strlen( $id ), strlen( $ids ) );
		$ids     .= $id . "\0";
	}
	foreach ( $entries as $id => $str ) {
		$t_meta[] = array( strlen( $str ), strlen( $strs ) );
		$strs    .= $str . "\0";
	}

	$bin = pack( 'V7', 0x950412de, 0, $n, $o_tab, $t_tab, 0, $start );
	foreach ( $o_meta as $m ) { $bin .= pack( 'VV', $m[0], $start + $m[1] ); }
	foreach ( $t_meta as $m ) { $bin .= pack( 'VV', $m[0], $start + strlen( $ids ) + $m[1] ); }

	return $bin . $ids . $strs;
}

/** قالب PHP وردپرس ۶٫۵+ (WP_Translation_File_PHP). */
function tpi_l10n( $locale, $cfg, $msgs ) {
	$data = array(
		'project-id-version' => 'ToPersia Render',
		'language'           => $locale,
		'plural-forms'       => $cfg['plural'],
		'x-generator'        => 'scripts/tp-i18n.php',
		'messages'           => $msgs,
	);
	return "<?php\n// ساختۀ scripts/tp-i18n.php — دستی ویرایش نشود.\nreturn " . var_export( $data, true ) . ";\n";
}

$built = array( $OUT . '/topersia.pot' => tpi_po( '', null, $plain, $plur, array() ) );

foreach ( $LOCALES as $locale => $cfg ) {
	ksort( $messages[ $locale ], SORT_STRING );
	$built[ "{$OUT}/topersia-{$locale}.po" ]       = tpi_po( $locale, $cfg, $plain, $plur, $messages[ $locale ] );
	$built[ "{$OUT}/topersia-{$locale}.mo" ]       = tpi_mo( tpi_header( $locale, $cfg ), $messages[ $locale ] );
	$built[ "{$OUT}/topersia-{$locale}.l10n.php" ] = tpi_l10n( $locale, $cfg, $messages[ $locale ] );
}

if ( in_array( '--check', $argv, true ) ) {

	foreach ( $built as $file => $content ) {
		if ( ! is_file( $file ) || file_get_contents( $file ) !== $content ) {
			$errors[] = 'خروجی با جدول یکی نیست (php scripts/tp-i18n.php): ' . basename( $file );
		}
	}

	if ( $errors ) {
		echo implode( "\n", $errors ), "\n";
		exit( 1 );
	}

	printf( "کاتالوگ سالم: %d رشته + %d جمع، هر کدام انگلیسی و عربی\n", count( $plain ), count( $plur ) );
	exit( 0 );
}

if ( $errors ) {
	fwrite( STDERR, implode( "\n", $errors ) . "\n" );
}

if ( ! is_dir( $OUT ) ) {
	mkdir( $OUT, 0755, true );
}

foreach ( $built as $file => $content ) {
	file_put_contents( $file, $content );
}

printf( "نوشته شد: %s — %d رشته + %d جمع%s\n", 'languages/', count( $plain ), count( $plur ), $errors ? '، با ' . count( $errors ) . ' ایراد (بالا)' : '' );
exit( $errors ? 1 : 0 );
