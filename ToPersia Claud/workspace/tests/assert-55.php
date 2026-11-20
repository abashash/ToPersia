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

/* ── بند ۵: نمای فهرستی تحریریه — همۀ هاب‌ها و فهرست‌های خبر/مقاله ─────────
 * رفتار و بلندی ردیف روی نسخۀ محلی: w55-local --only=list */
$w55_tok  = $w55_read( $w55_r . 'assets/topersia-tokens.css' );
$w55_ncss = $w55_read( $w55_r . 'assets/topersia-g-news.css' );
$w55_hcss = $w55_read( $w55_r . 'assets/topersia-g-hub.css' );
$w55_toks = function ( $c ) {
	return (bool) preg_match( '/:root\{[^}]*--tp-list-thumb-w:120px;[^}]*--tp-list-thumb-h:90px;[^}]*--tp-list-gap:[^}]*--tp-list-pad:/s', $c )
		&& (bool) preg_match( '/@media \(max-width:720px\)\{\s*:root\{[^}]*--tp-list-thumb-w:88px;[^}]*--tp-list-thumb-h:66px;/s', $c );
};
tp441_mut( 'بند ۵: توکن بندانگشتی ۱۲۰×۹۰ دسکتاپ و ۸۸×۶۶ زیر ۷۲۰، با فاصله‌های ردیف — جهش: موبایل همان ۱۲۰', $w55_toks, $w55_tok,
	str_replace( '--tp-list-thumb-w:88px;', '--tp-list-thumb-w:120px;', $w55_tok ) );

$w55_rows = function ( $c ) use ( $w55_hcss ) {
	$r = 'html .tp-page.tp-news-page .tp-news-grid';
	return false !== strpos( $c, $r . '.tp-news-grid--rows{grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:auto;gap:var(--tp-list-gap)}' )
		&& false !== strpos( $c, $r . '--rows .tp-news-card__media{inline-size:var(--tp-list-thumb-w);block-size:var(--tp-list-thumb-h);' )
		&& false !== strpos( $c, $r . '--rows .tp-news-card{flex-direction:row;align-items:flex-start;gap:var(--tp-list-inner);padding:var(--tp-list-pad)}' )
		&& false !== strpos( $c, $r . '--rows .tp-news-card__title a{-webkit-line-clamp:1}' )
		&& (bool) preg_match( '/@media \(max-width:720px\)\{\s*html \.tp-page\.tp-news-page \.tp-news-grid\.tp-news-grid--rows\{grid-template-columns:minmax\(0,1fr\)\}/', $c )
		/* ردیف در موبایل افقی می‌ماند (قاعدۀ ستونی ۶۰۰px پیشین برداشته شد) و هیچ لایۀ دیگری (هاب، مقاله) 1fr یا بندانگشتی جدا ندارد */
		&& ! preg_match( '/--rows \.tp-news-card\{flex-direction:column\}/', $c )
		&& ! preg_match( '/news-grid--rows[^{]*\{[^}]*grid-auto-rows:1fr/', $c . $w55_hcss )
		&& ! preg_match( '/news-grid--rows \.tp-news-card__top\{flex:0 0 clamp/', $c . $w55_hcss );
};
tp441_mut( 'بند ۵: یک قاعدۀ ردیف برای همۀ فهرست‌ها — دو ستون، ≤۷۲۰ یک ستون، بندانگشتی از توکن، عنوان یک‌سطری، بی 1fr — جهش: بازگشت grid-auto-rows:1fr', $w55_rows, $w55_ncss,
	str_replace( 'grid-auto-rows:auto;gap:var(--tp-list-gap)}', 'grid-auto-rows:1fr;gap:var(--tp-list-gap)}', $w55_ncss ) );

/* نشان «نمونۀ نمایشی» (پیش‌نمایش) در فهرست روی بندانگشتی — اجرای اول w55 list: زیرش ردیف را ۱۴۲٫۴px می‌کرد */
$w55_badge = function ( $c ) use ( $w55_tok ) {
	return false !== strpos( $c, '.tp-news-grid--rows .tp-news-card__top{position:relative}' )
		&& false !== strpos( $c, '.tp-news-grid--rows .tp-news-card__top .tp-demo-badge{position:absolute;inset-block-end:var(--tp-list-badge-inset);inset-inline-start:var(--tp-list-badge-inset);' )
		&& false !== strpos( $w55_tok, '--tp-list-badge-inset:' );
};
tp441_mut( 'بند ۵: نشان «نمونۀ نمایشی» در نمای فهرست روی گوشۀ بندانگشتی (بلندی ردیف را نمی‌افزاید)، فاصله از توکن — جهش: نشان در جریان عادی', $w55_badge, $w55_ncss,
	str_replace( '.tp-demo-badge{position:absolute;', '.tp-demo-badge{position:static;', $w55_ncss ) );

/* سطر «نوع · در X» یک‌سطری و خلاصه حداکثر دو سطر (قاعدۀ پایۀ کارت) در هر دو کارت؛ خلاصۀ هاب فقط در فهرست */
$w55_hubsrc = $w55_read( $w55_r . 'includes/topersia-g-hub.php' );
$w55_nwsrc  = $w55_read( $w55_r . 'includes/topersia-g-news.php' );
$w55_cards  = function ( $h ) use ( $w55_nwsrc, $w55_ncss ) {
	return false !== strpos( $h, "topersia_g_list_clip( topersia_g_text( \$id, 'short_summary' ) )" )
		&& false !== strpos( $h, "'<p class=\"tp-news-card__sum\">' . esc_html( \$sum ) . '</p>'" )
		&& false !== strpos( $w55_nwsrc, "topersia_g_news_card_sub( \$news_id )" )
		&& false !== strpos( $w55_ncss, '.tp-news-card__sum{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;' )
		&& false !== strpos( $w55_ncss, ':is(.tp-hub-card__sub,.tp-news-card__sub){display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;' )
		&& false !== strpos( $w55_ncss, '.tp-news-grid:not(.tp-news-grid--rows) :is(.tp-news-card__sub,.tp-hub-card .tp-news-card__sum){display:none}' );
};
tp441_mut( 'بند ۵: کاشی هاب «خلاصۀ کوتاه» (دروازۀ زبان، بریده) و کارت خبر «موضوع · در مکان» دارد؛ دو سطر خلاصه، یک سطر زیرعنوان — جهش: خواندن مستقیم متا (دور زدن دروازه)', $w55_cards, $w55_hubsrc,
	str_replace( "topersia_g_text( \$id, 'short_summary' )", "get_post_meta( \$id, 'short_summary', true )", $w55_hubsrc ) );

/* بریدن متن ردیف: کوتاه دست نمی‌خورد؛ بلند ≤ سقف + «…»، در مرز واژه */
$w55_clip = function ( callable $f ) {
	$long = str_repeat( 'واژۀ آزمون ', 40 );
	$out  = (string) $f( $long, 180 );
	return 'متن کوتاه' === $f( 'متن   کوتاه', 180 )
		&& '…' === mb_substr( $out, -1 ) && mb_strlen( $out ) <= 181
		&& ' ' !== mb_substr( $out, -2, 1 ) && 0 === mb_strpos( $long, mb_substr( $out, 0, -1 ) );
};
/* news-ui در این فرایند بار نمی‌شود: بدنۀ خودِ تابع از سورس بیرون کشیده و به یک بستار تبدیل می‌شود (همان کد، نه رونوشت) */
$w55_clipfn = function_exists( 'topersia_g_list_clip' ) ? 'topersia_g_list_clip' : null;
if ( null === $w55_clipfn ) {
	$w55_nui = $w55_read( $w55_r . 'includes/topersia-g-news-ui.php' );
	if ( preg_match( '/function topersia_g_list_clip\( \$text, \$max = 180 \) \{\n(.*?)\n\t\}\n\}/s', $w55_nui, $w55_m ) ) {
		$w55_clipfn = eval( 'return function ( $text, $max = 180 ) {' . $w55_m[1] . '};' );   // phpcs:ignore Squiz.PHP.Eval
	}
}
tp441_mut( 'بند ۵: topersia_g_list_clip — متن کوتاه بی تغییر (فاصله‌ها یکی)، بلند حداکثر ۱۸۰ نویسه + «…» در مرز واژه — جهش: بریدن خام بی «…»', $w55_clip,
	$w55_clipfn ? $w55_clipfn : function () { return ''; },
	function ( $t, $m ) { return mb_substr( $t, 0, $m ); } );

/* ── بند ۶: شمار هاب — استان همه (۳۱) بی صفحه‌بندی؛ بقیه ۴۸ با «نمایش بیشتر» = پیوند واقعی صفحۀ بعد ──
 * hub.php در این فرایند بار نمی‌شود: تابع و ثابت‌ها از سورس خوانده و همان کد اجرا می‌شود (نه رونوشت). */
$w55_hub_per = function ( $src ) {
	if ( ! preg_match( "/define\( 'TOPERSIA_HUB_PER_PAGE', (\d+) \)/", $src, $pp ) || ! preg_match( "/define\( 'TOPERSIA_HUB_ALL_TYPES', '([a-z_,]+)' \)/", $src, $at )
		|| ! preg_match( '/function topersia_g_hub_per_page\( \$type \) \{\n(.*?)\n\t\}\n\}/s', $src, $fb ) ) {
		return null;
	}
	$body = str_replace( array( 'TOPERSIA_HUB_ALL_TYPES', 'TOPERSIA_HUB_PER_PAGE' ), array( var_export( $at[1], true ), $pp[1] ), $fb[1] );
	return eval( 'return function ( $type ) {' . $body . '};' );   // phpcs:ignore Squiz.PHP.Eval
};
$w55_per = function ( $src ) use ( $w55_hub_per ) {
	$f = $w55_hub_per( $src );
	return $f && -1 === $f( 'tp_province' ) && 48 === $f( 'tp_city' ) && 48 === $f( 'tp_attraction' ) && 48 === $f( '' )
		&& false !== strpos( $src, "\$q->set( 'posts_per_page', topersia_g_hub_per_page( '' === \$seas && '' === \$grp && \$q->is_post_type_archive() ? \$pt : '' ) );" );
};
tp441_mut( 'بند ۶: هاب استان همۀ موارد (-1، بی صفحه‌بندی)؛ هر هاب دیگر (نوع، گروه، فصل، برچسب) ۴۸ در صفحه؛ کوئری اصلی از همان تابع — جهش: استان هم ۴۸', $w55_per, $w55_hubsrc,
	str_replace( "define( 'TOPERSIA_HUB_ALL_TYPES', 'tp_province' )", "define( 'TOPERSIA_HUB_ALL_TYPES', 'tp_none' )", $w55_hubsrc ) );

$w55_redir = function ( $src ) {
	return false !== strpos( $src, "add_action( 'template_redirect', function () {\n\t\tif ( ! topersia_g_hub_is() || topersia_g_news_partial() ) {\n\t\t\treturn;\n\t\t}\n\t\t\$want = isset( \$_GET['page'] ) && is_scalar( \$_GET['page'] ) ? absint( wp_unslash( \$_GET['page'] ) ) : 0;" )
		&& false !== strpos( $src, "\$to   = \$all ? 1 : ( \$want ? \$want : \$now );" )
		&& false !== strpos( $src, "if ( \$want || ( \$all && \$now > 1 ) ) {\n\t\t\twp_safe_redirect( topersia_g_hub_link( \$to ), 301 );\n\t\t\texit;" );
};
tp441_mut( 'بند ۶: ?page=N ← ۳۰۱ به نشانی خزنده‌پذیر /page/N/ با پالایش جاری؛ هاب استان /page/N/ ← صفحۀ ۱ (بی نشانی دوم)؛ درخواست قطعه دست نمی‌خورد — جهش: /province/page/2/ بی بازگردانی', $w55_redir, $w55_hubsrc,
	str_replace( 'if ( $want || ( $all && $now > 1 ) ) {', 'if ( $want ) {', $w55_hubsrc ) );

$w55_more = function ( $t ) {
	$a = strpos( $t, 'if ( $tp_tiles && $tp_paged < $tp_pages ) :' );
	$b = strpos( $t, '<a class="tp-hub-loadmore__a" href="<?php echo esc_url( topersia_g_hub_link( $tp_paged + 1 ) ); ?>" rel="next" data-tp-load-more data-tp-load-msg="<?php echo esc_attr__( \'موارد بیشتری به فهرست افزوده شد\', \'topersia\' ); ?>"><?php echo esc_html__( \'نمایش بیشتر\', \'topersia\' ); ?></a>' );
	$c = strpos( $t, "topersia_g_news_pages( \$tp_pages, \$tp_paged, 'topersia_g_hub_link'" );
	return false !== $a && false !== $b && false !== $c && $a < $b && $b < $c && false !== strpos( $t, 'data-tp-load-more-box' );
};
tp441_mut( 'بند ۶: «نمایش بیشتر» یک <a href> واقعی به صفحۀ بعد با rel="next" (بی JS و برای خزنده همان صفحۀ N)، فقط وقتی صفحۀ بعد هست، و صفحه‌بندی پیوندی هم می‌ماند — جهش: دکمۀ بی href', $w55_more, $w55_hub,
	str_replace( '<a class="tp-hub-loadmore__a" href="<?php echo esc_url( topersia_g_hub_link( $tp_paged + 1 ) ); ?>" rel="next"', '<a class="tp-hub-loadmore__a" role="button"', $w55_hub ) );

$w55_lm_js = function ( $j ) {
	return 1 === preg_match( "/function loadMore\( a \) \{.*?getPart\( url \).*?if \( n\.matches\( '\.tp-news-card' \) \) \{\n\t+var c = items\.appendChild\( document\.importNode\( n, true \) \);.*?\[ '\[data-tp-load-more-box\]', '\.tp-news-pages' \]\.forEach.*?live\.textContent = a\.getAttribute\( 'data-tp-load-msg' \).*?\.catch\( function \(\) \{\n\t+window\.location\.assign\( url\.href \);/s", $j )
		&& false !== strpos( $j, "a.hasAttribute( 'data-tp-load-more' ) && same( new URL( a.href, window.location.href ) )" );
};
tp441_mut( 'بند ۶: news.js «نمایش بیشتر» کارت‌های صفحۀ بعد را از مسیر قطعه به همان شبکه می‌افزاید (جایگاه تبلیغ میان‌فهرستی صفحۀ بعد نه)، دکمه و صفحه‌بندی را با نسخۀ تازه عوض می‌کند، اعلان زنده دارد و با خطا به همان نشانی می‌رود — جهش: افزودن هر فرزند', $w55_lm_js, $w55_njs,
	str_replace( "if ( n.matches( '.tp-news-card' ) ) {", "if ( n.nodeType === 1 ) {", $w55_njs ) );

$w55_lm_css = function ( $c ) {
	if ( ! preg_match( '/\.tp-hub-loadmore__a\{([^}]*)\}/', $c, $m ) ) {
		return false;
	}
	return false !== strpos( $m[1], 'min-block-size:44px' ) && false !== strpos( $m[1], 'border:1px solid var(--tp-tile-border)' )
		&& false !== strpos( $m[1], 'border-radius:var(--tp-tile-radius,12px)' ) && false !== strpos( $m[1], 'background:var(--tp-tile-bg)' )
		&& false !== strpos( $m[1], 'color:var(--tp-primary-text)' ) && ! preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(/i', $m[1] )
		&& false !== strpos( $c, '.tp-hub-loadmore__a:hover{background:var(--tp-tile-hover-bg);color:var(--tp-primary-text)}' );
};
tp441_mut( 'بند ۶: «نمایش بیشتر» ظاهر کاشی (P5-D245) — پس‌زمینه، قاب، شعاع از --tp-tile-*، متن --tp-primary-text، هدف لمسی ۴۴px، بی رنگ خام — جهش: رنگ خام', $w55_lm_css, $w55_hcss,
	str_replace( 'background:var(--tp-tile-bg);color:var(--tp-primary-text);font-weight:700;text-decoration:none}', 'background:#fff;color:#0a6;font-weight:700;text-decoration:none}', $w55_hcss ) );

/* کش قطعه: با ذخیرۀ محتوا باطل می‌شد، نه با استقرار کد — حالا امضای پرونده‌های سازنده؛ گرم‌کردن (همان purge) دست‌نخورده */
$w55_nui6 = $w55_read( $w55_r . 'includes/topersia-g-news-ui.php' );
$w55_fver = function ( $s ) {
	return false !== strpos( $s, "add_action( 'init', function () {\n\t\t\$sig = topersia_frag_code_sig();\n\t\tif ( '' === \$sig || \$sig === (string) get_option( 'topersia_frag_ver', '' ) ) {\n\t\t\treturn;\n\t\t}\n\t\tupdate_option( 'topersia_frag_ver', \$sig, true );\n\t\ttopersia_frag_purge();\n\t}, 51 );" )
		&& 1 === preg_match( '/function topersia_frag_code_sig\(\) \{.*?\'templates\/hub-archive\.php\'.*?\'includes\/topersia-g-hub\.php\'.*?\'includes\/topersia-g-news-ui\.php\'.*?filemtime\( \$file \) \. \':\' \. filesize\( \$file \)/s', $s )
		&& 1 === preg_match( '/function topersia_frag_purge\(\) \{.*?topersia_frag_warm_schedule\(\);/s', $s );
};
tp441_mut( 'بند ۶: قطعۀ کش‌شده با تغییر کد سازنده (نسخه + زمان و اندازۀ قالب‌ها و includeها) یک بار پاک و گرم‌کردن در صف می‌شود — جهش: بی پاک‌کردن (نشانه‌گذاری کهنه پس از استقرار)', $w55_fver, $w55_nui6,
	str_replace( "update_option( 'topersia_frag_ver', \$sig, true );\n\t\ttopersia_frag_purge();", "update_option( 'topersia_frag_ver', \$sig, true );", $w55_nui6 ) );

/* ── بند ۷: CLS — نخستین رنگ‌آمیزی (پیش از اسکریپت‌های defer و zones.js) همان چیدمان پس از آن‌هاست ────────────────
 * منشأها با مدرک در w55-local --only=cls (درنگ ۳ثانیه‌ای اسکریپت‌ها بدترین حالت را قطعی بازمی‌سازد). رفتار را آن آزمون می‌سنجد؛
 * این‌جا فقط اینکه هر رفع سر جایش است و یک منبع دارد. */
$w55_boot = $w55_read( $w55_r . 'topersia-render.php' );
$w55_pcss = $w55_read( $w55_r . 'assets/topersia-g-prog.css' );
$w55_c7_list = function ( $s ) {
	return false !== strpos( $s, "if(g('news-view')==='list'&&window.MutationObserver){var q=function(){var e=document.querySelectorAll('[data-tp-news-items]:not(.tp-news-grid--rows)');" )
		&& false !== strpos( $s, "o=new MutationObserver(q);o.observe(h,{childList:true,subtree:true});document.addEventListener('DOMContentLoaded',function(){q();o.disconnect()})}" )
		&& 1 === preg_match( "/function topersia_render_boot_script\(\) \{.*?var h=document\.documentElement,g=function\(k\)\{return localStorage\.getItem\('tp-'\+k\)\};.*?g\('news-view'\)/s", $s );   /* همان کلید tp-news-view در news.js */
};
tp441_mut( 'بند ۷: نمای فهرستی پیش از رنگ‌آمیزی — اسکریپت <head> کلاس tp-news-grid--rows را با ناظر جهش همان لحظۀ درج پارسر می‌گذارد (همان کلید news.js) — جهش: بی observe (کلاس پس از رنگ‌آمیزی، CLS ۰٫۴۶)', $w55_c7_list, $w55_boot,
	str_replace( 'o.observe(h,{childList:true,subtree:true});', '', $w55_boot ) );

$w55_nui7 = $w55_read( $w55_r . 'includes/topersia-g-news-ui.php' );
$w55_c7_view = function ( $j ) use ( $w55_nui7, $w55_ncss ) {
	return false !== strpos( $w55_nui7, '" data-tp-pre hidden data-tp-news-view>' )
		&& false !== strpos( $w55_ncss, 'html.tp-js .tp-page.tp-news-page:has([data-tp-news-items]) .tp-news-view[data-tp-pre]{display:inline-flex}' )
		&& 1 === preg_match( "/function applyView\(\) \{.*?box\.hidden = ! items;\n\t\tbox\.removeAttribute\( 'data-tp-pre' \);/s", $j );
};
tp441_mut( 'بند ۷: دکمۀ شبکه/فهرست با html.tp-js و شبکه از نخستین رنگ‌آمیزی دیدنی؛ applyView نشان پیش را برمی‌دارد تا hidden حاکم شود — جهش: نشان می‌ماند (بی شبکه پس از پالایش هم دیده می‌شد)', $w55_c7_view, $w55_njs,
	str_replace( "\t\tbox.removeAttribute( 'data-tp-pre' );", '', $w55_njs ) );

/* دستۀ 55-ب بند ۱ (P5-D642): لایۀ شناور — همیشه بسته آغاز می‌شود (استثنای «پُر» دستۀ 55 بند ۷ برداشته شد)؛ /news/ هم همان دکمه و ظرف */
$w55_narch = $w55_read( $w55_r . 'templates/news-archive.php' );
$w55_c7_panel = function ( $j ) use ( $w55_hub, $w55_art, $w55_narch, $w55_hcss ) {
	foreach ( array( array( $w55_hub, 'tp-hub-more' ), array( $w55_art, 'tp-hub-more' ), array( $w55_narch, 'tp-news-more' ) ) as $t ) {
		if ( false === strpos( $t[0], 'aria-controls="' . $t[1] . '" hidden data-tp-pre>' ) || false === strpos( $t[0], '<div data-tp-pre id="' . $t[1] . '" class="tp-hub-more" data-tp-filter-panel>' ) ) {
			return false;
		}
	}
	return (bool) preg_match( '/@media \(max-width:720px\)\{[^@]*html\.tp-js \.tp-page\.tp-news-page \.tp-hub-more__btn\[data-tp-pre\]\{display:inline-flex\}\s*html\.tp-js \.tp-page\.tp-news-page \.tp-hub-more\[data-tp-pre\]\{display:none\}/', $w55_hcss )
		&& 1 === preg_match( "/syncMore\(\);\n.*?\n\t\t\tmoreBtn\.removeAttribute\( 'data-tp-pre' \);\n\t\t\tmorePanel\.removeAttribute\( 'data-tp-pre' \);/", $j )
		&& false === strpos( $w55_hcss, ':not(:has(input:not([type="hidden"]):not([value=""])))' )
		&& false !== strpos( $j, 'morePanel.hidden = narrow.matches && ! isOpen();' );   /* بسته یعنی پنهان، بی استثنای «پُر» */
};
tp441_mut( 'بند ۷ · P5-D642: پنل فیلتر موبایل (هاب، /articles/، /news/) از نخستین رنگ‌آمیزی در وضعیت پس از news.js (دکمه دیدنی، لایه بسته)؛ news.js پس از syncMore نشان را برمی‌دارد — جهش: نشان ظرف می‌ماند (ظرف هرگز باز نمی‌شد)', $w55_c7_panel, $w55_njs,
	str_replace( "\t\t\tmorePanel.removeAttribute( 'data-tp-pre' );", '', $w55_njs ) );

$w55_c7_prog = function ( $c ) use ( $w55_tok, $w55_pcss ) {
	return false !== strpos( $w55_tok, '--tp-prog-h:20px;' ) && false !== strpos( $w55_tok, '--tp-chips-prog-gap:6px;' )
		&& false !== strpos( $w55_pcss, 'html .tp-page .tp-prog{position:relative;box-sizing:border-box;block-size:var(--tp-prog-h);' )
		&& false !== strpos( $c, '.tp-news-chips > .tp-prog{grid-column:1 / -1;grid-row:2;margin-block-start:var(--tp-chips-prog-gap)}' )
		&& false !== strpos( $c, 'html.tp-js .tp-page.tp-news-page .tp-news-chips:not(:has(> .tp-prog))::after{content:"";grid-column:1 / -1;grid-row:2;block-size:var(--tp-prog-h);margin-block-start:var(--tp-chips-prog-gap)}' );
};
tp441_mut( 'بند ۷: ردیف نوار پیشرفت تراشه پیش از ساخته‌شدنش در موبایل نگه داشته می‌شود؛ بلندی و فاصله یک منبع (توکن) برای نوار و جای آن — جهش: جای‌نگهدار با عدد خام', $w55_c7_prog, $w55_ncss,
	str_replace( 'grid-row:2;block-size:var(--tp-prog-h);margin-block-start', 'grid-row:2;block-size:24px;margin-block-start', $w55_ncss ) );

$w55_c7_bar = function ( $j ) use ( $w55_ncss ) {
	return 1 === preg_match( "/function place\(\) \{\n.*?if \( crumb && crumb\.hasAttribute\( 'data-tp-actionbar-slot' \) \) \{\n\t+return;\n\t+\}\n\n\t+if \( narrow\.matches \) \{/s", $j )
		&& false !== strpos( $j, "var crumb  = page.querySelector( '[data-tp-actionbar-slot]' ) || page.querySelector( '.tp-crumb' );" )   /* صفحۀ موجودیت همان */
		&& (bool) preg_match( '/@media \(max-width:720px\)\{[^@]*html \.tp-page\.tp-page\.tp-news-single \.tp-rail > #tp-actions\{background:none;border:0;border-radius:0;overflow:visible;box-shadow:none;padding:0;margin:0 0 calc\(var\(--tp-news-bar-gap\) - var\(--tp-grid-row-gap-m\)\)\}/', $w55_ncss )
		&& false !== strpos( $w55_ncss, 'html .tp-page.tp-news-single .tp-content-grid:has(> .tp-rail > #tp-actions){margin-block-start:0}' )
		&& false !== strpos( $w55_ncss, '.tp-news-single .tp-rail > #tp-actions{order:-1}' );
};
tp441_mut( 'بند ۷: تک‌خبر/تک‌مقاله — zones.js نوار کنش را جابه‌جا و کلاسش را عوض نمی‌کند؛ جا (order:-1) و ظاهر موبایل از CSS — جهش: جابه‌جایی پس از رنگ‌آمیزی (CLS ۰٫۱۰۳ در ۳۹۰)', $w55_c7_bar, $w55_zjs,
	str_replace( "if ( crumb && crumb.hasAttribute( 'data-tp-actionbar-slot' ) ) {\n\t\t\t\treturn;\n\t\t\t}\n\n", '', $w55_zjs ) );

/* فاصلۀ نوار تا نخستین بخش = margin شبکه (همان پیش از بند ۷)؛ row-gap شبکه زیر خانۀ نوار با توکنی جبران می‌شود که باید
   عیناً row-gap موبایل قالب مولد باشد — اگر یکی عوض شود و دیگری نه، بخش نخست بی‌صدا جابه‌جا می‌شود (۳۹۰: ۲۸px). */
$w55_tmpl = $w55_read( $w55_r . 'assets/topersia-g-template.css' );
$w55_c7_gap = function ( $t ) use ( $w55_tmpl, $w55_ncss ) {
	return 1 === preg_match( '/@media\(max-width:720px\)\{\s*html \.tp-page div\.tp-content-grid\{row-gap:([0-9.]+px)\}/', $w55_tmpl, $m )
		&& 1 === preg_match( '/--tp-grid-row-gap-m:([0-9.]+px);/', $t, $n ) && $m[1] === $n[1]
		&& 1 === preg_match( '/--tp-news-bar-gap:(clamp\([^;]+\));/', $t, $g )
		&& false !== strpos( $w55_ncss, 'html .tp-page.tp-news-single .tp-content-grid{margin-block-start:' . $g[1] . '}' );
};
tp441_mut( 'بند ۷: جبران row-gap زیر نوار کنش موبایل = همان row-gap قالب مولد (یک عدد در دو جا، برابری پاییده) — جهش: توکن ۲۴px', $w55_c7_gap, $w55_tok,
	str_replace( '--tp-grid-row-gap-m:28px;', '--tp-grid-row-gap-m:24px;', $w55_tok ) );

/* ── بند ۸: «بهترین فصل» تهی (P5-D633) ─────────────────────────────────
 * ۵۲ ردیف پیش‌فرض «بهار» (۴۸ fa · ۲ en · ۲ ar؛ نه نمایشی، نه نمونه) با scripts/tp-season-null.php تهی شد؛ مقدار پیشین در
 * docs/ops/backups/best-season-spring-pre-null-20261119.json. رفتار با تهی: نه فصلی جور، نه جدول فصل، نه «نامعلوم». */
require_once TP_PLUGIN_DIR . 'includes/topersia-g-hub.php';   /* تابع‌ها زیر function_exists؛ قلاب‌ها فقط ثبت می‌شوند */
$w55_c8_ok = true;
foreach ( array( 99801 => null, 99802 => '', 99803 => array() ) as $w55_id => $w55_v ) {
	if ( null !== $w55_v ) {
		$GLOBALS['tp_meta'][ $w55_id ]['best_season'] = $w55_v;
	}
	foreach ( array( 'spring', 'summer', 'autumn', 'winter' ) as $w55_s ) {
		$w55_c8_ok = $w55_c8_ok && function_exists( 'topersia_g_hub_in_season' ) && ! topersia_g_hub_in_season( $w55_id, $w55_s );
	}
	$w55_c8_ok = $w55_c8_ok && function_exists( 'topersia_g_sc_seasons' ) && '' === topersia_g_sc_seasons( array( 'id' => $w55_id ) );
}
$GLOBALS['tp_meta'][99804]['best_season'] = 'autumn';
t( 'بند ۸: فصل تهی (نبود متا · رشتۀ خالی · آرایۀ خالی) در هیچ فصلی نمی‌آید و [tp_seasons] هیچ خروجی ندارد (پنهان، نه «نامعلوم»)؛ «پاییز» هنوز پاییز است',
	$w55_c8_ok && topersia_g_hub_in_season( 99804, 'autumn' ) && ! topersia_g_hub_in_season( 99804, 'spring' ) && '' !== topersia_g_sc_seasons( array( 'id' => 99804 ) ) );

$w55_sn  = $w55_read( 'scripts/tp-season-null.php' );
$w55_c8s = function ( $s ) {
	$w = strpos( $s, "file_put_contents( \$tp_file, wp_json_encode( \$out" );
	$d = strpos( $s, "delete_metadata_by_mid( 'post', (int) \$r->meta_id );" );
	return false !== $w && false !== $d && $w < $d && false !== strpos( $s, "count( \$back['rows'] ) !== count( \$rows )" )
		&& false !== strpos( $s, '! (int) $r->demo && ! (int) $r->sample && tp_sn_is_spring( $r->meta_value )' )
		&& false !== strpos( $s, "'topersia.local' !== (string) wp_parse_url( home_url(), PHP_URL_HOST )" )
		&& false !== strpos( $s, "metadata_exists( 'post', \$pid, 'best_season' )" );
};
tp441_mut( 'بند ۸: tp-season-null.php — پروندۀ پشتیبان پیش از هر حذف نوشته و بازخوانی می‌شود؛ نمایشی و نمونه بیرون؛ فقط topersia.local؛ برگرداندن مقدار تازه را بازنویسی نمی‌کند — جهش: نمونه هم تهی می‌شد',
	$w55_c8s, $w55_sn, str_replace( '! (int) $r->demo && ! (int) $r->sample && ', '! (int) $r->demo && ', $w55_sn ) );

/* ── بند ۹: تصویر مشترک (P5-D634) ─────────────────────────────────────
 * منشأ: پیوست #50 (درون‌ریزی نخست، دادۀ واقعی) تصویر خود ۴۲ موجودیت است؛ و جایگزین زنجیرۀ نیا (کاشی هاب، بندانگشتی ردیف خانه)
 * همان عکس استان را روی شهرهای بی عکس هم می‌نشاند. جایگزین مشترک فقط در پیش‌نمایش؛ بیرون از آن آیکون خنثی. داده دست نخورد. */
$w55_demo = $w55_read( $w55_r . 'includes/topersia-g-demo.php' );
$w55_hrow = $w55_read( $w55_r . 'includes/topersia-g-home-row.php' );
$w55_c9   = function ( $hub ) use ( $w55_demo, $w55_hrow ) {
	$g = strpos( $w55_demo, 'function topersia_g_shared_fallback_on() {' );
	$a = strpos( $hub, 'function topersia_g_hub_card_image( $id ) {' );
	$b = false !== $a ? strpos( $hub, 'topersia_g_crumb_chain( $id )', $a ) : false;
	$c = false !== $a ? strpos( $hub, 'if ( $img || ! topersia_g_shared_fallback_on() ) {', $a ) : false;
	return false !== $g && false !== strpos( substr( $w55_demo, $g, 80 ), 'return topersia_demo_on();' )
		&& false !== $b && false !== $c && $c < $b
		&& false !== strpos( $w55_hrow, "if ( ! \$att && function_exists( 'topersia_g_crumb_chain' ) && topersia_g_shared_fallback_on() ) {" )
		&& 1 === substr_count( $w55_hrow, 'topersia_g_crumb_chain(' );   /* تنها فراخوانی زنجیره همان شاخۀ دروازه‌دار */
};
tp441_mut( 'بند ۹: جایگزین تصویر از زنجیرۀ نیا (کاشی هاب و بندانگشتی ردیف) فقط از دروازۀ topersia_g_shared_fallback_on() = topersia_demo_on() می‌گذرد؛ بیرون از پیش‌نمایش آیکون — جهش: کاشی هاب بی دروازه (عکس استان روی شهرها)',
	$w55_c9, $w55_hubsrc, str_replace( 'if ( $img || ! topersia_g_shared_fallback_on() ) {', 'if ( $img ) {', $w55_hubsrc ) );
t( 'بند ۹: بی ثابت پیش‌نمایش، جایگزین مشترک خاموش است (همان مقدار topersia_demo_on)',
	function_exists( 'topersia_g_shared_fallback_on' ) && false === topersia_g_shared_fallback_on() && topersia_g_shared_fallback_on() === topersia_demo_on() );

/* ── بند ۱۰: منوی پاورقی (۶٫۱) — بی تکرار کاشی صفحۀ اول؛ «دربارۀ توپرشیا»؛ حقوقی دست‌نخورده ──────────
 * رفتار روی وردپرس واقعی (هر زبان، ستون بی پیوند پنهان): tests/footer-gate.js. */
require_once TP_PLUGIN_DIR . 'includes/topersia-g-home.php';     /* topersia_g_home_guide_types — فقط تعریف تابع */
require_once TP_PLUGIN_DIR . 'includes/topersia-g-footer.php';
$w55_ft   = $w55_read( $w55_r . 'includes/topersia-g-footer.php' );
$w55_c10  = function ( $s ) {
	if ( ! preg_match( '/function topersia_g_ft_menu_cols\(\) \{.*?\n\t}\n/s', $s, $b ) ) {
		return false;
	}
	preg_match_all( "/array\( __\( '[^']+', 'topersia' \), '([a-z]+)', '([a-z_-]+)' \)/u", $b[0], $m, PREG_SET_ORDER );
	$tiles = topersia_g_ft_home_tiles();
	foreach ( $m as $it ) {
		if ( in_array( $it[1] . ':' . $it[2], $tiles, true ) ) {
			return false;
		}
	}
	return count( $m ) > 0 && false !== strpos( $b[0], "return ! in_array( \$it[1] . ':' . \$it[2], \$tiles, true );" )
		&& false !== strpos( $b[0], "\t\t\t'legal' => array( \$t['حقوقی'], array(\n\t\t\t\tarray( __( 'قوانین و مقررات', 'topersia' ), 'page', 'terms' ),\n\t\t\t\tarray( __( 'حریم خصوصی', 'topersia' ), 'page', 'privacy-policy' ),\n\t\t\t\tarray( __( 'حق نشر', 'topersia' ), 'page', 'copyright' ),\n\t\t\t) )," );
};
tp441_mut( 'بند ۱۰: هیچ آیتم منوی پاورقی مقصد کاشی صفحۀ اول نیست (استان، شهر، /attractions/، نُه نوع راهنما) و صافی زمان اجرا هم همین را می‌گیرد؛ ستون حقوقی (و حق نشر) عیناً همان — جهش: «روادید» به ستون راهنما برگردد',
	$w55_c10, $w55_ft, str_replace( "array( __( 'رویدادها', 'topersia' ), 'group', 'events' ),", "array( __( 'روادید', 'topersia' ), 'type', 'tp_visa' ),\n\t\t\t\tarray( __( 'رویدادها', 'topersia' ), 'group', 'events' ),", $w55_ft ) );

$w55_cols = topersia_g_ft_menu_cols();
$w55_keys = array();
foreach ( $w55_cols as $w55_k => $w55_c ) {
	$w55_keys[ $w55_k ] = array_map( function ( $it ) { return $it[1] . ':' . $it[2]; }, $w55_c[1] );
}
$w55_tl = topersia_g_ft_home_tiles();
t( 'بند ۱۰: ستون‌ها — مقصدها: روستاها · جزیره‌ها؛ راهنمای سفر: رویدادها · فصل‌ها؛ دربارۀ توپرشیا: درباره ما · تماس · اخبار · مقالات؛ حقوقی بی تغییر — و فهرست کاشی‌ها نُه نوع راهنما را دارد',
	array( 'type:tp_village', 'type:tp_island' ) === $w55_keys['dest'] && array( 'group:events', 'season:now' ) === $w55_keys['guide']
	&& array( 'page:about', 'page:contact', 'type:tpc_news', 'type:tpc_article' ) === $w55_keys['about']
	&& array( 'page:terms', 'page:privacy-policy', 'page:copyright' ) === $w55_keys['legal']
	&& 'دربارۀ توپرشیا' === $w55_cols['about'][0] && 'دربارۀ توپرشیا' === topersia_g_ft_toggle_titles()['توپرشیا']
	&& 12 === count( $w55_tl ) && in_array( 'type:tp_visa', $w55_tl, true ) && in_array( 'type:tp_local_food', $w55_tl, true ),
	wp_json_encode( $w55_keys ) );

/* فصل امروز به تقویم شمسی — مرز هر فصل (۱ فروردین · ۱ تیر · ۱ مهر · ۱ دی ۱۴۰۵) و روز پیش از آن */
$w55_today_prev = $GLOBALS['tp_filters']['topersia_g_home_today'] ?? null;
$w55_sn_ok      = true;
foreach ( array( '2026-03-20' => 'winter', '2026-03-21' => 'spring', '2026-06-21' => 'spring', '2026-06-22' => 'summer', '2026-09-22' => 'summer', '2026-09-23' => 'autumn', '2026-11-19' => 'autumn', '2026-12-21' => 'autumn', '2026-12-22' => 'winter' ) as $w55_d => $w55_want ) {
	$GLOBALS['tp_filters']['topersia_g_home_today'] = array( function () use ( $w55_d ) { return $w55_d; } );
	$w55_sn_ok = $w55_sn_ok && $w55_want === topersia_g_ft_season_now();
}
if ( null === $w55_today_prev ) {
	unset( $GLOBALS['tp_filters']['topersia_g_home_today'] );
} else {
	$GLOBALS['tp_filters']['topersia_g_home_today'] = $w55_today_prev;
}
t( 'بند ۱۰: «فصل‌ها» به صفحۀ فصل جاری شمسی می‌رود — مرز ۱ فروردین، ۱ تیر، ۱ مهر و ۱ دی درست', $w55_sn_ok );
tp441_mut( 'بند ۱۰: پیوند فصل فقط وقتی دست‌کم یک موجودیت منتشرشدۀ همین زبان در آن فصل (یا تمام سال) هست — نمایشی فقط در پیش‌نمایش؛ وگرنه ستون بی آن — جهش: بی سنجش فصل',
	function ( $s ) {
		return false !== strpos( $s, "if ( 'season' === \$kind ) {\n\t\t\treturn topersia_g_ft_season_url();" )
			&& false !== strpos( $s, "'meta_query'       => array_merge( topersia_g_sample_mq(), array( array(" )
			&& false !== strpos( $s, "foreach ( topersia_g_published( array_map( 'absint', (array) \$ids ) ) as \$id ) {\n\t\t\tif ( topersia_g_hub_in_season( \$id, \$s ) ) {" );
	}, $w55_ft, str_replace( 'if ( topersia_g_hub_in_season( $id, $s ) ) {', 'if ( true ) {', $w55_ft ) );

/* toggle جعبۀ برند (عنوانش در ذخیرۀ کاربر تهی شد) هم پویاست — وگرنه از کش عنصر می‌آید و شیوه‌نامۀ el-toggle دو بار چاپ می‌شود */
$w55_tg_brand = array( 'widgetType' => 'wd_toggle', 'settings' => array( 'element_title' => '' ), 'elements' => array(
	array( 'elType' => 'container', 'elements' => array( array( 'widgetType' => 'shortcode', 'settings' => array( 'shortcode' => '[tp_footer_brand]' ) ) ) ),
) );
$w55_tg_plain = array( 'widgetType' => 'wd_toggle', 'settings' => array( 'element_title' => 'دیگر' ), 'elements' => array(
	array( 'widgetType' => 'shortcode', 'settings' => array( 'shortcode' => '[gallery]' ) ),
	array( 'widgetType' => 'text-editor', 'settings' => array( 'editor' => '[tp_footer_brand]' ) ),
) );
t( 'بند ۱۰: topersia_g_ft_toggle_has_sc — شورت‌کد [tp_footer_…] در ویجت shortcode، حتی تودرتو، آری؛ شورت‌کد دیگر یا متن در ویجت دیگر، نه',
	true === topersia_g_ft_toggle_has_sc( $w55_tg_brand ) && false === topersia_g_ft_toggle_has_sc( $w55_tg_plain ) && false === topersia_g_ft_toggle_has_sc( array() ) );
tp441_mut( 'بند ۱۰: صافی is_dynamic_content هر toggle دارای شورت‌کد پاورقی را پویا می‌کند، نه فقط عنوان‌های نقشه — جهش: فقط نقشۀ عنوان (کد پیشین؛ دو برچسب el-toggle)',
	function ( $s ) {
		return false !== strpos( $s, "return topersia_g_ft_toggle_has_sc( \$raw ) ? true : \$is;\n\t}, 10, 2 );" );
	}, $w55_ft, str_replace( 'return topersia_g_ft_toggle_has_sc( $raw ) ? true : $is;', 'return $is;', $w55_ft ) );

/* بند ۱۳ (یافتۀ عکس جست‌وجوی فوکوس ۳۹۰) — ردیف ابزار هاب و /articles/؛ رفتار در w55-local --only=toolbar (کروم و فایرفاکس، با کالیبراسیون) */
$w55_c13_btn = function ( $c ) use ( $w55_ncss ) {
	$p   = 'html .tp-page.tp-news-page .tp-news-filter .tp-hub-more__btn';
	$hid = strpos( $c, $p . '[hidden]{display:none}' );
	$pre = strpos( $c, 'html.tp-js .tp-page.tp-news-page .tp-hub-more__btn[data-tp-pre]{display:inline-flex}' );
	return false !== strpos( $w55_ncss, 'html .tp-page.tp-news-page .tp-news-filter button{' )   /* رقیب: قاعدۀ دکمۀ ارسال */
		&& false !== strpos( $c, $p . '{display:inline-flex;' ) && false !== strpos( $c, 'background:var(--tp-tile-bg);color:var(--tp-text-strong);' )
		&& false !== strpos( $c, $p . '[aria-expanded="true"]{border-color:var(--tp-primary);color:var(--tp-primary-text)}' )
		&& false !== strpos( $c, $p . ':hover{background:var(--tp-tile-hover-bg);border-color:var(--tp-primary);color:var(--tp-primary-text)}' )
		&& false !== $hid && false !== $pre && $hid < $pre   /* هم‌وزن‌اند؛ نشان پیش ≤۷۲۰ باید پس از [hidden] بیاید */
		&& ! preg_match( '/^html \.tp-page\.tp-news-page \.tp-hub-more__btn[\[{:]/m', $c );   /* قاعدۀ بی .tp-news-filter به دکمۀ ارسال می‌بازد */
};
tp441_mut( 'بند ۱۳: دکمۀ «فیلتر» ظاهر کاشی خودش را دارد (بسته · باز · هاور) و قاعدۀ دکمۀ ارسال بر آن نمی‌چربد؛ نشان پیش ≤۷۲۰ پس از [hidden] — جهش: گزینشگر بی .tp-news-filter (کد پیشین؛ متن حالت باز نامرئی)',
	$w55_c13_btn, $w55_hcss, str_replace( '.tp-news-filter .tp-hub-more__btn', '.tp-hub-more__btn', $w55_hcss ) );
$w55_c13_row = function ( $c ) {
	/* دستۀ 55-ب بند ۱ (P5-D642): ظرف لایۀ شناور است (absolute، دوستونه)؛ فرم همچنان پایۀ صفر و شکستن سطر تا شبکه/فهرست کنار «فیلتر» بماند */
	return 1 === preg_match( '/@media \(max-width:720px\)\{[^@]*html \.tp-page\.tp-news-page :is\(\.tp-news-bar__tools,\.tp-news-filterrow\) > form\.tp-news-filter:has\(\[data-tp-filter-panel\]\)\{flex:1 1 0;min-inline-size:0;flex-wrap:wrap\}\s*html \.tp-page\.tp-news-page \.tp-hub-more\{display:grid;grid-template-columns:repeat\(2,minmax\(0,1fr\)\);/', $c );
};
tp441_mut( 'بند ۱۳ · P5-D642: ≤۷۲۰ فرم ابزار پایۀ صفر و شکستن سطر دارد و ظرف شبکۀ دوستونه است — جهش: بی شکستن سطر (کد پیشین؛ «نمایش» و شبکه/فهرست بیرون از لبه)',
	$w55_c13_row, $w55_hcss, str_replace( 'min-inline-size:0;flex-wrap:wrap}', 'min-inline-size:0}', $w55_hcss ) );

/* شمارشگر --launch (بند ۸ هشدار؛ بند ۹ شمار تصویر مشترک و بی alt) — شمارشگر است، حکم نمی‌دهد */
$w55_lc  = $w55_read( 'scripts/tp-launch-count.php' );
$w55_c8l = function ( $s ) {
	return false !== strpos( $s, 'const TP_LC_SHARED_MAX = 5;' ) && false !== strpos( $s, 'return $n > TP_LC_SHARED_MAX;' )
		&& false !== strpos( $s, "array( 'key' => '_tp_demo', 'compare' => 'NOT EXISTS' )" ) && false !== strpos( $s, "array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' )" )
		&& false !== strpos( $s, "'suppress_filters' => true," ) && false !== strpos( $s, "echo 'NO_SEASON '" ) && false !== strpos( $s, "echo 'SHARED '" ) && false !== strpos( $s, "echo 'NO_ALT '" );
};
tp441_mut( 'بند ۸ و ۹: tp-launch-count.php — موجودیت بی فصل، تصویر خود موجودیت در بیش از ۵ موجودیت، تصویر بی alt؛ هر سه زبان، بی نمایشی و نمونه — جهش: «۵ یا بیشتر» به جای «بیش از ۵»',
	$w55_c8l, $w55_lc, str_replace( 'return $n > TP_LC_SHARED_MAX;', 'return $n >= TP_LC_SHARED_MAX;', $w55_lc ) );
$w55_launch = in_array( '--launch', isset( $argv ) ? (array) $argv : array(), true );
if ( $w55_launch && is_file( TP_WP_CLI ) ) {
	$w55_lout = array();
	exec( escapeshellarg( TP_PHP ) . ' ' . escapeshellarg( TP_WP_CLI ) . ' --path=' . escapeshellarg( TP_WP_DIR ) . ' --url=' . escapeshellarg( TP_BASE_URL ) . ' eval-file ' . escapeshellarg( $w55_root . '/scripts/tp-launch-count.php' ) . ' 2>&1', $w55_lout, $w55_lcode );
	$w55_lout = array_values( preg_grep( '/^(NO_SEASON|SHARED|NO_ALT|SKIP)\b/', $w55_lout ) );
	t( 'گیت --launch: شمارشگر بند ۸ و ۹ اجرا شد', 0 === (int) $w55_lcode && $w55_lout, implode( ' · ', $w55_lout ) );
	foreach ( $w55_lout as $w55_l ) {
		if ( preg_match( '/^(NO_SEASON|SHARED|NO_ALT) ([1-9][0-9]*) (.*)$/', $w55_l, $w55_m ) ) {
			$w55_lbl = array( 'NO_SEASON' => 'موجودیت بی «بهترین فصل»', 'SHARED' => 'تصویر خود موجودیت در بیش از ۵ موجودیت', 'NO_ALT' => 'تصویر موجودیت بی alt' );
			echo '  ⚠ هشدار --launch (شکست نیست): ' . $w55_lbl[ $w55_m[1] ] . ': ' . $w55_m[2] . ' — ' . $w55_m[3] . "\n";
		}
	}
}
