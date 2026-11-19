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
