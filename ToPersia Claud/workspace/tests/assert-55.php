<?php
/**
 * دستۀ 55 (P5-D626 تا P5-D637) — از assert.php بار می‌شود؛ فقط خواندن کد، هر ادعا با جهش.
 * رفتار روی وردپرس واقعی: tests/w55-local.js (Chromium و Firefox × ۱۴۴۰/۷۶۸/۳۹۰).
 */

echo "\nدستۀ 55 — جعبۀ جست‌وجو، نمای فهرستی، شمار هاب، CLS، فصل، تصویر مشترک، پاورقی\n" . str_repeat( '─', 78 ) . "\n";

require_once __DIR__ . '/env.php';
$w55_root = dirname( __DIR__ );
$w55_read = function ( $p ) use ( $w55_root ) {
	return (string) @file_get_contents( $w55_root . '/' . $p );
};
$w55_r      = 'plugin/topersia-render/';
$w55_zjs    = $w55_read( $w55_r . 'assets/topersia-g-zones.js' );
$w55_njs    = $w55_read( $w55_r . 'assets/topersia-g-news.js' );
$w55_ljs    = $w55_read( $w55_r . 'assets/topersia-g-loc.js' );
$w55_hub    = $w55_read( $w55_r . 'templates/hub-archive.php' );
$w55_art    = $w55_read( $w55_r . 'templates/article-archive.php' );

/* ── بند ۱: پشتیبان کامل (format 2) — رازِ پروندۀ سایت با نام و محتوا ─────
 * اجرای نخست با صافی فقط‌نام دو cacert.pem عمومی و قالب wp-config-check.php دوپلیکیتور را انداخت؛ اکنون رفتار روی پروندۀ واقعی سنجیده می‌شود. */
require_once $w55_root . '/scripts/lib/tp-backup-lib.php';
$w55_fx  = rtrim( str_replace( '\\', '/', sys_get_temp_dir() ), '/' ) . '/tp-w55-secret-' . getmypid();
@mkdir( $w55_fx, 0777, true );
$w55_fix = array(   /* نام ← [محتوا، راز است؟] */
	'wp-config.php'         => array( "<?php define( 'DB_NAME', 'x' );", true ),
	'cacert.pem'            => array( "-----BEGIN CERTIFICATE-----\nMIIBszCCAVmgAwIBAgIU\n-----END CERTIFICATE-----\n", false ),
	/* نشان کلید خصوصی در زمان اجرا ساخته می‌شود تا git-guard روی خودِ آزمون خطای مثبت ندهد؛ بدنه ساختگی و کوتاه است */
	'server.pem'            => array( '-----BEGIN ' . implode( ' ', array( 'PRIVATE', 'KEY' ) ) . "-----\nAAAA\n-----END -----\n", true ),
	'wp-config-sample.php'  => array( "<?php define( 'DB_PASSWORD', 'password_here' ); define( 'AUTH_KEY', 'put your unique phrase here' );", false ),
	'wp-config-check.php'   => array( "<?php echo 'wp-config check';", false ),
	'wp-config-old.php'     => array( "<?php define( 'DB_PASSWORD', 'S3cr3t-real-value' );", true ),
	'.gitignore'            => array( "node_modules\n", false ),
	'.env'                  => array( "KEY=1\n", true ),
	'id_rsa'                => array( "x", true ),
);
foreach ( $w55_fix as $w55_n => $w55_v ) { file_put_contents( $w55_fx . '/' . $w55_n, $w55_v[0] ); }
$w55_secret = function ( callable $fn ) use ( $w55_fx, $w55_fix ) {
	foreach ( $w55_fix as $n => $v ) {
		if ( (bool) $fn( 'geo/x/' . $n, $w55_fx . '/' . $n ) !== $v[1] ) {
			return false;
		}
	}
	return true;
};
tp441_mut( 'بند ۱: site.zip — wp-config.php همیشه بیرون؛ cacert.pem عمومی، wp-config-sample/check و .gitignore درون؛ .pem با PRIVATE KEY، wp-config با رمز واقعی، .env و id_rsa بیرون — جهش: صافی فقط‌نام (اجرای نخست)',
	$w55_secret, 'tpb_site_secret', function ( $rel, $abs ) { return tpb_secret_file( basename( $rel ) ); } );
foreach ( array_keys( $w55_fix ) as $w55_n ) { @unlink( $w55_fx . '/' . $w55_n ); }
@rmdir( $w55_fx );

$w55_bk  = $w55_read( 'scripts/tp-backup.php' );
$w55_rt  = $w55_read( 'scripts/tp-restore-test.php' );
$w55_pkg = function ( $b ) use ( $w55_rt ) {
	return false !== strpos( $b, "tpb_zip_map( \$dir . '/site.zip', \$sm['map'] )" )
		&& false !== strpos( $b, "'contents'     => array( 'file' => 'contents.tsv'" )
		&& (bool) preg_match( '/\|pem\|crt\)\|\\\\\.htaccess\)\$\/i/', $b )
		&& false !== strpos( $b, "\$sm['map']['env/wp-config.names.txt']" )
		&& false !== strpos( $w55_rt, "is_file( \$x . '/geo/wp-config.php' )" )
		&& false !== strpos( $w55_rt, "rt_unzip_check( \$dir . '/site.zip', \$x, \$sf['files'], \$contents['site.zip'] )" );
};
tp441_mut( 'بند ۱: بسته site.zip و contents.tsv (sha256 هر پرونده) دارد، .pem و .crt پویش کلید می‌شوند، فقط نام ثابت‌های wp-config؛ آزمون بازگردانی sha256 هر پرونده و نبود wp-config را می‌سنجد — جهش: بی پویش pem',
	$w55_pkg, $w55_bk, str_replace( '|pem|crt)', ')', $w55_bk ) );

/* ── بند ۴: جعبۀ جست‌وجو با کلیک ناپدید نمی‌شود ──────────────────────────
 * ریشه: ظرف فیلتر هاب (data-tp-more) با گزینشگر دکمۀ «N مورد دیگر» در zones.js برخورد داشت؛ کلیک ورودی
 * (bubbling) به شنوندۀ آن دکمه می‌رسید و ظرف — با جعبۀ جست‌وجو و combobox مکان — برداشته می‌شد. */

/* هر عنصری که data-tp-more دارد در همۀ قالب‌ها و تولیدکننده‌های PHP افزونۀ render فقط <button> است */
$w55_php = array();
foreach ( array_merge( (array) glob( $w55_root . '/' . $w55_r . 'templates/*.php' ), (array) glob( $w55_root . '/' . $w55_r . 'includes/*.php' ) ) as $w55_f ) {
	$w55_php[ basename( $w55_f ) ] = (string) file_get_contents( $w55_f );
}
$w55_more_tag = function ( array $files ) {
	$n = 0;
	foreach ( $files as $src ) {
		if ( ! preg_match_all( '/<([a-z][a-z0-9]*)\b[^<>]*?\sdata-tp-more(?=[\s>=\'"])/i', $src, $m ) ) {
			continue;
		}
		foreach ( $m[1] as $tag ) {
			if ( 'button' !== strtolower( $tag ) ) {
				return false;
			}
			$n ++;
		}
	}
	return $n > 0;
};
$w55_php_mut = $w55_php;
$w55_php_mut['hub-archive.php'] = str_replace( 'class="tp-hub-more" data-tp-filter-panel', 'class="tp-hub-more" data-tp-more', $w55_php['hub-archive.php'] );
tp441_mut( 'بند ۴: data-tp-more فقط روی <button> «N مورد دیگر» است، در هیچ قالب و تولیدکننده‌ای روی ظرف — جهش: ظرف فیلتر هاب با data-tp-more (ریشۀ باگ)',
	$w55_more_tag, $w55_php, $w55_php_mut );

$w55_zsel = function ( $j ) {
	return false !== strpos( $j, "page.querySelectorAll( 'button[data-tp-more]' )" ) && false === strpos( $j, "querySelectorAll( '[data-tp-more]' )" );
};
tp441_mut( 'بند ۴: zones.js شنوندۀ «N مورد دیگر» را فقط به button[data-tp-more] می‌بندد — جهش: گزینشگر باز [data-tp-more]', $w55_zsel, $w55_zjs,
	str_replace( "'button[data-tp-more]'", "'[data-tp-more]'", $w55_zjs ) );

$w55_panel = function ( $s ) use ( $w55_hub, $w55_art ) {
	return false !== strpos( $s, "root.querySelector( '[data-tp-filter-panel]' )" )
		&& false !== strpos( $w55_hub, 'id="tp-hub-more" class="tp-hub-more" data-tp-filter-panel>' )
		&& false !== strpos( $w55_art, 'id="tp-hub-more" class="tp-hub-more" data-tp-filter-panel>' );
};
tp441_mut( 'بند ۴: ظرف فیلتر هاب و مقاله‌ها data-tp-filter-panel است و news.js همان را می‌خواند — جهش: news.js با [data-tp-more]', $w55_panel, $w55_njs,
	str_replace( "'[data-tp-filter-panel]'", "'[data-tp-more]'", $w55_njs ) );

/* ظرف موبایل: بستن فقط با کلیک بیرون (pointerdown بیرون از ظرف و دکمه) یا Escape؛ هیچ focusout/blur آن را نمی‌بندد */
$w55_nclose = function ( $j ) {
	$a = strpos( $j, "root.querySelector( '[data-tp-filter-panel]' )" );
	$b = false === $a ? false : strpos( $j, 'narrow.addEventListener', $a );
	if ( false === $a || false === $b ) {
		return false;
	}
	$blk = substr( $j, $a, $b - $a );
	return false !== strpos( $blk, "document.addEventListener( 'pointerdown'" )
		&& false !== strpos( $blk, '! morePanel.contains( e.target ) && ! moreBtn.contains( e.target )' )
		&& false !== strpos( $blk, "'Escape' !== e.key" )
		&& false !== strpos( $blk, ".tp-loc__list:not([hidden])" )
		&& ! preg_match( "/addEventListener\( '(focusout|blur)'/", $blk );
};
tp441_mut( 'بند ۴: ظرف فیلتر موبایل فقط با کلیک بیرون یا Escape بسته می‌شود (Escape فهرست باز مکان مال combobox است) — جهش: بستن با focusout', $w55_nclose, $w55_njs,
	str_replace( "/* کلیک یا لمس بیرون از ظرف و دکمه */", "morePanel.addEventListener( 'focusout', shut );\n\t\t\t/* کلیک یا لمس بیرون از ظرف و دکمه */", $w55_njs ) );

/* combobox مکان: فهرست با pointerdown بیرون از ویجت یا Escape بسته می‌شود؛ focusout فقط وقتی مقصد فوکوس بیرون ویجت است، نه blur بی مقصد */
$w55_lclose = function ( $j ) {
	return false !== strpos( $j, "document.addEventListener( 'pointerdown'" )
		&& false !== strpos( $j, '! p.list.hidden && ! w.contains( e.target )' )
		&& false !== strpos( $j, "e.relatedTarget && ! w.contains( e.relatedTarget )" )
		&& false === strpos( $j, 'w.contains( document.activeElement )' )
		&& false !== strpos( $j, "'Escape' === e.key" );
};
tp441_mut( 'بند ۴: combobox مکان — بستن با کلیک بیرون یا Escape؛ blur بی مقصد (لمس جای خالی) فهرست را نمی‌بندد — جهش: focusout با مهلت ۱۲۰ms (کد پیشین)', $w55_lclose, $w55_ljs,
	str_replace( "e.relatedTarget && ! w.contains( e.relatedTarget )", "! w.contains( document.activeElement )", $w55_ljs ) );

/* پاسخ دیررس «جست‌وجو هنگام نوشتن»: syncForms فیلد فوکوس‌دار را دست نمی‌زند (متن در حال نوشتن مکان و نویسۀ تازۀ جست‌وجو
 * پاک می‌شد — w55-local در کروم ۱۴۴۰ گرفت)؛ فقط در مسیر نوشتن (push بی فوکوس)، نه «بازگشت» یا تراشه */
$w55_keep = function ( $j ) {
	return false !== strpos( $j, 'function syncForms( url, keep )' )
		&& false !== strpos( $j, "( keep && el === document.activeElement )" )
		&& false !== strpos( $j, 'syncForms( url, push && false === focus );' );
};
tp441_mut( 'بند ۴: پاسخ دیررس جست‌وجو فیلد فوکوس‌دار (مکان در حال نوشتن، نویسۀ تازه) را بازنویسی نمی‌کند — جهش: syncForms همه را از نشانی می‌نشاند (کد پیشین)', $w55_keep, $w55_njs,
	str_replace( ' || ( keep && el === document.activeElement )', '', $w55_njs ) );

/* فیلد به وضعیت نشانی برگشت (پاک شد) ← درخواست در راه لغو؛ وگرنه پاسخش متن پیشین را برمی‌گرداند و ظرف موبایل «پر» می‌ماند (w55-local ۳۹۰) */
$w55_abort = function ( $j ) {
	$a = strpos( $j, 'typing = window.setTimeout(' );
	$blk = false === $a ? '' : substr( $j, $a, 700 );
	return (bool) preg_match( '/if \( url\.href !== window\.location\.href \) \{\s*load\( url\.href, true, false \);\s*\} else if \( ctrl \) \{.*?ctrl\.abort\(\);\s*ctrl = null;\s*busy\( false \);/s', $blk );
};
tp441_mut( 'بند ۴: پاک‌کردن فیلد هنگامی که درخواست پیشین در راه است آن را لغو می‌کند — جهش: بی لغو (کد پیشین)', $w55_abort, $w55_njs,
	preg_replace( '/\} else if \( ctrl \) \{.*?busy\( false \);\s*\}/s', '}', $w55_njs, 1 ) );
