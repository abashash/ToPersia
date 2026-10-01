<?php
/**
 * آزمون کنتراست — هر متن، هر صفحه، هر سه حالت.  P5-D191
 *
 * منطق رندر را assert.php می‌گیرد؛ این یکی چیزی را می‌سنجد که فقط موتور
 * مرورگر می‌داند: رنگِ واقعیِ رندرشده پس از حل توکن‌ها، color-mix،
 * ارث‌بری، و لایه‌های نیمه‌شفاف.
 *
 * صفحه‌ها با همان رندرکنندهٔ زنده ساخته می‌شوند، کاوشگر داخلشان کار
 * می‌کند، و کروم بی‌سر نتیجه را بیرون می‌دهد.
 *
 * اجرا:   php tests/contrast.php
 *         php tests/contrast.php --deep    ← سنجش پیکسلی زیر انیمیشن هوا هم
 *         php tests/contrast.php --keep    ← صفحه‌های موقت را نگه می‌دارد
 *
 * `--deep` چه چیزی را اضافه می‌کند و کِی لازم است: بالای `wx-contrast-check.js`
 * نوشته شده و در `CLAUDE.md` خلاصه است. کوتاهش: کاوشگر معمولی زمینه را از
 * **نیاکان** می‌سازد و لایهٔ انیمیشن خواهرِ متن است، نه نیایش — پس آن را
 * اصلاً نمی‌بیند.  P5-D198
 *
 * کد خروج ۰ یعنی همه‌چیز بالای آستانه · ۱ یعنی افتادن · ۲ یعنی محیط ناقص
 *
 * مرورگر: اگر کروم جای دیگری است، TP_CHROME را تنظیم کن.
 */

$ROOT = dirname( __DIR__ );

/* ── یافتن مرورگر ───────────────────────────────────────────────── */
$candidates = array();
if ( getenv( 'TP_CHROME' ) ) { $candidates[] = getenv( 'TP_CHROME' ); }
$candidates = array_merge( $candidates, array(
	'C:\Program Files\Google\Chrome\Application\chrome.exe',
	'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
	'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
	'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
	'/usr/bin/google-chrome', '/usr/bin/chromium', '/usr/bin/chromium-browser',
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
) );
$chrome = '';
foreach ( $candidates as $c ) {
	if ( is_file( $c ) ) { $chrome = $c; break; }
}
if ( '' === $chrome ) {
	fwrite( STDERR, "مرورگری پیدا نشد. مسیر کروم یا اِج را در TP_CHROME بگذار.\n" );
	exit( 2 );
}

$probe_file = __DIR__ . '/contrast-probe.js';
if ( ! is_file( $probe_file ) ) {
	fwrite( STDERR, "کاوشگر پیدا نشد: {$probe_file}\n" );
	exit( 2 );
}
$probe = file_get_contents( $probe_file );

/* ── ساخت صفحه‌ها با همان رندرکنندهٔ زنده ───────────────────────── */
ob_start();
require __DIR__ . '/run.php';
ob_end_clean();

/*
 * ۴٫۴٫۱ — تاکردن جست‌وجوی پنجرۀ فهرست از هسته است (P5-D265). سایت همیشه
 * هسته را دارد؛ صحنۀ نوار بخش‌ها هم همان را بار می‌کند و `TopersiaG` را همان‌طور
 * می‌نویسد که `wp_localize_script` — همان کار tests/preview.php.
 */
require_once $ROOT . '/plugin/topersia-core/includes/topersia-fold.php';
require_once TP_PLUGIN_DIR . 'includes/topersia-g-core.php';
$tp_g_js = '<script>var TopersiaG = ' . json_encode( array( 'rest' => '', 'fold' => topersia_search_fold_js_rules() ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ";</script>\n";

$work = sys_get_temp_dir() . '/tp-contrast-' . getmypid();
if ( ! is_dir( $work ) ) { mkdir( $work, 0777, true ); }

/*
 * CSS درون صفحه می‌نشیند، نه با <link>.
 *
 * کروم شیتِ بارشده از file:// را مبدأ-متفاوت می‌شمارد و خواندن cssRules
 * را مسدود می‌کند؛ آن‌وقت کاوشگر قاعده‌های :hover را نمی‌بیند و بی‌صدا
 * صفر گزارش می‌دهد.
 */
$assets = $ROOT . '/plugin/topersia-render/assets/';

/*
 * ترتیب دقیقاً همان است که در `<head>` سایت زنده دیده شد — و ترتیب
 * اهمیت دارد، چون با وزن برابر، دیرتر یعنی برنده.  P5-D202
 *
 * دو فایل آخر مالِ ما نیستند و از `reference/` می‌آیند، از روی CSS زندهٔ
 * `topersia.com`. بی‌آن‌ها صفحهٔ آزمون خوش‌بین است: دکمهٔ نقشه در انزوا
 * تمیز درمی‌آید و آن مستطیل روشن اصلاً دیده نمی‌شود.
 *
 * `user-core` آخر می‌نشیند چون روی همهٔ صفحه‌ها بعد از ما بار می‌شود.
 */
$sheets = array(
	array( $assets . 'topersia-tokens.css',       'ما' ),
	array( $assets . 'topersia-theme-bridge.css', 'ما' ),
	array( $assets . 'topersia-g-template.css',      'ما' ),
	array( $ROOT . '/reference/woodmart-base-rtl.css', 'قالب' ),
	array( $ROOT . '/reference/user-core.css',         'افزونهٔ دیگر' ),
);

$style = '';

foreach ( $sheets as $one ) {

	list( $path, $who ) = $one;

	if ( ! is_file( $path ) ) {
		fwrite( STDERR, "شیوه‌نامه پیدا نشد: {$path}\n" );
		exit( 2 );
	}

	/* `@charset` وسط یک <style> بی‌اعتبار است — بی‌صدا رد می‌شود ولی نماند */
	$text = preg_replace( '/@charset\s+"[^"]*";\s*/i', '', file_get_contents( $path ) );

	$style .= "/* ── " . basename( $path ) . "  ({$who}) ── */\n" . $text . "\n";
}

require_once __DIR__ . '/live-body.php';
$body_class = tp_live_body_class( $ROOT );

if ( false === strpos( $body_class, 'tp-shell' ) ) {
	fwrite( STDERR, "کلاس‌های بدنه tp-shell ندارند — لایهٔ توکن خاموش می‌ماند.\n" );
	exit( 2 );
}

$cases = array(
	'province' => array( 20, 'استان اصفهان' ),
	'city'     => array( 30, 'شیراز' ),
	'desert'   => array( 40, 'دشت لوت' ),
	'mountain' => array( 24, 'کرکس' ),
);

$pages = array();
foreach ( $cases as $slug => $case ) {
	$GLOBALS['tp_current'] = $case[0];
	$body = tp_do( 'tp_page', array() );

	$html  = "<!DOCTYPE html>\n<html lang=\"fa\" dir=\"rtl\" data-tp-mode=\"light\">\n<head>\n";
	$html .= "<meta charset=\"utf-8\">\n<title>" . $case[1] . "</title>\n";
	$html .= "<style>\n" . $style . "\n</style>\n";
	$html .= "</head>\n<body class=\"" . $body_class . "\">\n" . $body
		. "\n<script>\n" . $probe . "\n</script>\n</body>\n</html>\n";

	$path = $work . '/' . $slug . '.html';
	file_put_contents( $path, $html );
	$pages[ $slug ] = $path;
}

/* ── صحنهٔ سربرگ برای آزمون‌های لایهٔ هوا ─────────────────────────
 *
 * دارایی‌ها هر بار تازه از پوشهٔ افزونه کپی می‌شوند، نه از `.preview/`.
 * آن‌جا نسخهٔ دستی است و می‌تواند عقب بماند؛ آن‌وقت آزمون بی‌صدا چیزی را
 * می‌سنجد که دیگر تولیدی نیست — و سبزش هیچ معنایی ندارد.  P5-D198
 */
$wx_dir = $work . '/wx';
if ( ! is_dir( $wx_dir ) ) { mkdir( $wx_dir, 0777, true ); }

$wx_assets = array(
	'topersia-tokens.css',
	'topersia-theme-bridge.css',
	'topersia-g-template.css',
	'topersia-g-weather.js',
	'topersia-g-weather-fx.js',
);

foreach ( $wx_assets as $f ) {
	if ( ! is_file( $assets . $f ) ) {
		fwrite( STDERR, "دارایی پیدا نشد: {$f}\n" );
		exit( 2 );
	}
	copy( $assets . $f, $wx_dir . '/' . $f );
}

/*
 * شیوه‌نامه‌های بیگانه این‌جا هم می‌آیند، وگرنه سنجشِ عمیق سربرگی را
 * می‌سنجد که در تولید وجود ندارد: `user-core` دقیقاً `.tp-hero h1` را
 * هدف می‌گیرد.  P5-D202
 */
$wx_refs = array( 'woodmart-base-rtl.css', 'user-core.css' );

foreach ( $wx_refs as $f ) {
	if ( ! is_file( $ROOT . '/reference/' . $f ) ) {
		fwrite( STDERR, "مرجع پیدا نشد: {$f}\n" );
		exit( 2 );
	}
	copy( $ROOT . '/reference/' . $f, $wx_dir . '/' . $f );
}

$wx_fixture = __DIR__ . '/wx-fixture.html';

if ( ! is_file( $wx_fixture ) ) {
	fwrite( STDERR, "صحنهٔ سربرگ پیدا نشد: {$wx_fixture}\n" );
	exit( 2 );
}

/* کلاس‌های بدنهٔ زنده در صحنه هم می‌نشینند — همان دلیل.  P5-D206 */
$wx_html = file_get_contents( $wx_fixture );
$wx_html = str_replace( '<body class="tp-shell">', '<body class="' . $body_class . '">', $wx_html );

if ( false === strpos( $wx_html, $body_class ) ) {
	fwrite( STDERR, "کلاس بدنه در صحنهٔ سربرگ تزریق نشد.\n" );
	exit( 2 );
}

file_put_contents( $wx_dir . '/wx-fixture.html', $wx_html );

/* ── صحنهٔ نوار بخش‌ها — P5-D223 · ۴٫۲٫۱ بند ۳ ─────────────────────
 *
 * صفحه‌های کنتراست اسکریپتِ خودِ صفحه را ندارند (کاوشگر جایش است)؛ این
 * یکی دارد، چون آنچه سنجیده می‌شود رفتار است: اندازه‌گیریِ هدر، چسبیدن،
 * لنگر، و اینکه صفحه عمودی نپرد. اسکریپت هم تازه از پوشهٔ افزونه می‌آید. */
$snav_dir = $work . '/snav';
if ( ! is_dir( $snav_dir ) ) { mkdir( $snav_dir, 0777, true ); }
copy( $assets . 'topersia-g-zones.js', $snav_dir . '/topersia-g-zones.js' );

$GLOBALS['tp_current'] = 20;

file_put_contents( $snav_dir . '/province.html',
	"<!DOCTYPE html>\n<html lang=\"fa\" dir=\"rtl\" data-tp-mode=\"light\">\n<head>\n<meta charset=\"utf-8\">\n"
	/* بی‌این، کرومِ موبایل‌نما پهنای ۹۸۰ فرض می‌کند و هیچ قاعدهٔ موبایلی نمی‌خورد */
	. "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
	. "<title>نوار بخش‌ها</title>\n<style>\n" . $style . "\n</style>\n</head>\n"
	. "<body class=\"" . $body_class . "\">\n" . tp_do( 'tp_page', array() )
	. "\n" . $tp_g_js . "<script src=\"topersia-g-zones.js\"></script>\n</body>\n</html>\n" );

/* ── اجرای مرورگر ───────────────────────────────────────────────── */
function tp_file_url( $path ) {
	$p = str_replace( '\\', '/', $path );
	if ( ':' === substr( $p, 1, 1 ) ) { $p = '/' . $p; }
	return 'file://' . $p;
}

$profile = $work . '/profile';

/**
 * چند صفحه را در یک اجرای کروم می‌خواند و نتیجهٔ کاوشگر هرکدام را
 * برمی‌گرداند — هم‌ترتیب با ورودی.
 *
 * تا کروم ۱۵۲ از `--dump-dom` استفاده می‌شد. کروم ۱۵۳ آن پرچم را حذف
 * کرد و آزمون بی‌صدا مرد: کد خروج صفر، خروجی خالی. حالا از CDP رد
 * می‌شود که قرارداد پایدار خود مرورگر است.  P5-D194
 */
function tp_run_pages( $chrome, $paths ) {

	$runner = __DIR__ . '/contrast-run.js';

	if ( ! is_file( $runner ) ) {
		return array( 'error' => 'رانندهٔ CDP پیدا نشد' );
	}

	$cmd = 'node ' . escapeshellarg( $runner ) . ' ' . escapeshellarg( $chrome );

	foreach ( $paths as $path ) {
		$cmd .= ' ' . escapeshellarg( preg_match( '#^https?://#', $path ) ? $path : tp_file_url( $path ) );
	}

	$cmd .= ' 2>' . ( DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null' );

	$raw = shell_exec( $cmd );

	if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
		return array( 'error' => 'رانندهٔ CDP خروجی نداد — node نصب است؟' );
	}

	$data = json_decode( trim( $raw ), true );

	return is_array( $data ) ? $data : array( 'error' => 'نتیجه خوانده نشد' );
}

/** یک صفحه. */
function tp_run_page( $chrome, $profile, $path ) {

	$out = tp_run_pages( $chrome, array( $path ) );

	if ( isset( $out['error'] ) ) { return $out; }

	return isset( $out[0] ) && is_array( $out[0] ) ? $out[0] : array( 'error' => 'نتیجه خوانده نشد' );
}

/**
 * یک رانندهٔ node را اجرا می‌کند و گزارش خودش را دست‌نخورده برمی‌گرداند.
 *
 * برخلاف `tp_run_pages` این‌جا JSON خوانده نمی‌شود: راننده گزارشش را
 * خودش می‌نویسد و کد خروج می‌دهد. یک‌جا چاپ می‌شود تا با گزارش اصلی
 * قاتی نشود.
 *
 * @return array  code · out
 */
function tp_run_driver( $script, $chrome, $url ) {

	$file = __DIR__ . '/' . $script;

	if ( ! is_file( $file ) ) {
		return array( 'code' => 2, 'out' => "  ⚠ راننده پیدا نشد: {$script}" );
	}

	$cmd = 'node ' . escapeshellarg( $file )
		. ' ' . escapeshellarg( $chrome )
		. ' ' . escapeshellarg( $url )
		. ' 2>&1';

	$lines = array();
	$code = 0;

	exec( $cmd, $lines, $code );

	if ( ! $lines ) {
		return array( 'code' => 2, 'out' => "  ⚠ {$script} خروجی نداد — node نصب است؟" );
	}

	return array( 'code' => $code, 'out' => implode( "\n", $lines ) );
}

/**
 * نسبت کنتراست — پیاده‌سازی مستقل در PHP.
 *
 * عمداً از روی فرمول WCAG دوباره نوشته شده، نه ترجمهٔ کد کاوشگر. هدف
 * همین است: دو پیاده‌سازی جدا که اگر بخوانند یعنی فرمول درست است.
 */
function tp_ratio_php( $fg, $bg ) {

	$chan = function ( $v ) {
		$v = $v / 255;
		return $v <= 0.03928 ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 );
	};
	$lum = function ( $c ) use ( $chan ) {
		return 0.2126 * $chan( $c[0] ) + 0.7152 * $chan( $c[1] ) + 0.0722 * $chan( $c[2] );
	};

	$a = $lum( $fg );
	$b = $lum( $bg );

	return ( max( $a, $b ) + 0.05 ) / ( min( $a, $b ) + 0.05 );
}

/* ── کالیبراسیون: اول کاوشگر را بسنج، بعد صفحه‌های واقعی را ────────
   اگر ابزار سنجش خودش غلط باشد، سبزِ صفحه‌های واقعی هیچ معنایی ندارد. */

$fixture_src = __DIR__ . '/contrast-fixture.html';

if ( ! is_file( $fixture_src ) ) {
	fwrite( STDERR, "صفحهٔ کالیبراسیون پیدا نشد: {$fixture_src}\n" );
	exit( 2 );
}

$fixture = file_get_contents( $fixture_src );
$fixture = str_replace( '</body>', "<script>\n" . $probe . "\n</script>\n</body>", $fixture );
$fixture_path = $work . '/fixture.html';
file_put_contents( $fixture_path, $fixture );

$cal = tp_run_page( $chrome, $profile, $fixture_path );

echo "\nکالیبراسیون کاوشگر — P5-D193\n", str_repeat( '─', 78 ), "\n";

if ( isset( $cal['error'] ) || empty( $cal['calibration'] ) ) {
	fwrite( STDERR, "  ❌ کالیبراسیون اجرا نشد: "
		. ( isset( $cal['error'] ) ? $cal['error'] : 'موردی برنگشت' ) . "\n" );
	exit( 2 );
}

$cal_bad = 0;

foreach ( $cal['calibration'] as $case ) {

	$want_fg = array_map( 'floatval', explode( ',', $case['expectFg'] ) );
	$want_bg = array_map( 'floatval', explode( ',', $case['expectBg'] ) );
	$got_bg  = array_map( 'floatval', explode( ',', $case['gotBg'] ) );
	$got_fg  = array_map( 'floatval', explode( ',', $case['gotFg'] ) );

	/* زمینه‌ای که کاوشگر حل کرد باید همانی باشد که مورد اعلام کرده */
	$bg_off = 0;
	$fg_off = 0;
	for ( $i = 0; $i < 3; $i++ ) {
		$bg_off = max( $bg_off, abs( $want_bg[ $i ] - ( isset( $got_bg[ $i ] ) ? $got_bg[ $i ] : -999 ) ) );
		$fg_off = max( $fg_off, abs( $want_fg[ $i ] - ( isset( $got_fg[ $i ] ) ? $got_fg[ $i ] : -999 ) ) );
	}

	/* و نسبتش باید با حساب مستقل PHP بخواند */
	$php_ratio  = tp_ratio_php( $want_fg, $want_bg );
	$ratio_off  = abs( $php_ratio - (float) $case['gotRatio'] );
	$stated_off = abs( $php_ratio - (float) $case['expect'] );

	/*
	 * رواداری نسبی است نه ثابت: کانواس رنگ را به هشت بیت گرد می‌کند و
	 * یک پلهٔ ۱/۲۵۵ روی نسبت‌های بالا اثر بزرگ‌تری می‌گذارد. یک درصد،
	 * با کفِ ۰٫۰۵، هم گردکردن را می‌پذیرد هم باگ‌های واقعی را می‌گیرد —
	 * سه موردی که کالیبراسیون اولین بار گرفت ۰٫۰۹ تا ۰٫۲۵ خطا داشتند.
	 */
	$tol = max( 0.05, $php_ratio * 0.01 );

	$ok = $bg_off <= 1.0 && $fg_off <= 1.0 && $ratio_off <= $tol && $stated_off <= $tol;

	/* موردی که «برگِ متنی» اعلام شده باید واقعاً سنجیده شود.  P5-D225 */
	if ( ! empty( $case['expectLeaf'] ) && empty( $case['gotLeaf'] ) ) {
		$ok = false;
	}

	if ( ! $ok ) { $cal_bad ++; }

	printf(
		"  %s %-18s نسبت %-6s (PHP %-6s · اعلام %-6s)   زمینه %s\n",
		$ok ? '✅' : '❌',
		$case['name'],
		number_format( (float) $case['gotRatio'], 2 ),
		number_format( $php_ratio, 2 ),
		number_format( (float) $case['expect'], 2 ),
		$bg_off <= 1.0 ? 'درست' : $case['gotBg'] . ' ≠ ' . $case['expectBg']
	);
}

if ( $cal_bad ) {
	echo str_repeat( '─', 78 ), "\n";
	fwrite( STDERR, "  کاوشگر کالیبره نیست — {$cal_bad} مورد. سنجش صفحه‌های واقعی انجام نشد.\n" );
	exit( 2 );
}

printf( "  هر %d مورد کالیبره است\n", count( $cal['calibration'] ) );

$reports = array();
$failed_pages = array();

/*
 * هر چهار صفحه در **یک** اجرای کروم.
 *
 * `P5-D194` همین را گفته بود، ولی این حلقه `tp_run_page` را تک‌تک صدا
 * می‌زد و هر بار یک کروم تازه بالا می‌آمد. بالاآمدن کروم گران‌ترین بخش
 * اجراست، نه سنجش.
 */
/*
 * نسخهٔ محلیِ سایت — ۴٫۲٫۳ بند ۱ · P5-D203.
 *   php tests/contrast.php --url=http://topersia.local/province/isfahan/ --url=…
 * به‌جای چهار صفحهٔ هارنس، همان صفحه‌های واقعی سنجیده می‌شوند و
 * snav-check روی هر کدام، در حالت «صفحهٔ واقعی» (هدر واقعی وودمارت،
 * اسکرول واقعی چرخ، دادهٔ خودِ صفحه). کالیبراسیون همان است.
 */
$live_urls = array();
foreach ( $argv as $arg ) {
	if ( 0 === strpos( $arg, '--url=' ) ) { $live_urls[] = substr( $arg, 6 ); }
}
if ( $live_urls ) {
	$pages = array();
	foreach ( $live_urls as $u ) {
		$pages[ trim( preg_replace( '#[^a-z0-9]+#i', '-', (string) parse_url( $u, PHP_URL_PATH ) ), '-' ) ] = $u;
	}
}

$slugs = array_keys( $pages );
$batch = tp_run_pages( $chrome, array_values( $pages ) );

if ( isset( $batch['error'] ) ) {
	$failed_pages[] = $batch['error'];
} else {
	foreach ( $slugs as $i => $slug ) {

		$data = isset( $batch[ $i ] ) && is_array( $batch[ $i ] )
			? $batch[ $i ]
			: array( 'error' => 'نتیجه خوانده نشد' );

		if ( isset( $data['error'] ) ) {
			$failed_pages[] = $slug . ': ' . $data['error'];
			continue;
		}

		$reports[ $slug ] = $data;
	}
}

/* ── لایهٔ هوا روی سربرگ ─────────────────────────────────────────
 *
 * دو سنجه‌اند و عمداً در دو مسیر:
 *
 *   دروازهٔ حرکت   هر اجرا. پنج بار بارشدن صفحه، حدود چهار ثانیه. آنچه
 *                  می‌گیرد وعده‌ای است که نباید هیچ‌وقت بشکند.
 *   کنتراست عمیق   فقط با `--deep`. هجده ترکیب × شش نمونه، نزدیک هشتاد
 *                  ثانیه. هر commit بهایش را نمی‌ارزد.
 */
$wx_url  = tp_file_url( $wx_dir . '/wx-fixture.html' );
$deep    = in_array( '--deep', $argv, true );
$wx_out  = array();
$wx_fail = 0;
$wx_env  = 0;

$motion = tp_run_driver( 'wx-motion-check.js', $chrome, $wx_url );
$wx_out[] = $motion['out'];
if ( 2 === $motion['code'] ) { $wx_env ++; } elseif ( $motion['code'] ) { $wx_fail ++; }

/* نوار بخش‌ها — هر اجرا، مثل دروازهٔ حرکت.  P5-D223 · روی نسخهٔ محلی: هر صفحه */
foreach ( $live_urls ? $live_urls : array( tp_file_url( $snav_dir . '/province.html' ) ) as $snav_url ) {
	$snav_run = tp_run_driver( 'snav-check.js', $chrome, $snav_url );
	$wx_out[] = ( $live_urls ? "\n  ▸ " . $snav_url : '' ) . $snav_run['out'];
	if ( 2 === $snav_run['code'] ) { $wx_env ++; } elseif ( $snav_run['code'] ) { $wx_fail ++; }
}

if ( $deep ) {
	$deep_run = tp_run_driver( 'wx-contrast-check.js', $chrome, $wx_url );
	$wx_out[] = $deep_run['out'];
	if ( 2 === $deep_run['code'] ) { $wx_env ++; } elseif ( $deep_run['code'] ) { $wx_fail ++; }
} else {
	$wx_out[] = "\nکنتراست زیر انیمیشن هوا — اجرا نشد\n" . str_repeat( '─', 78 ) . "\n"
		. "  با --deep اجرا می‌شود. لازم است وقتی سربرگ، لایهٔ انیمیشن،\n"
		. "  پوشش، یا هر چیزی که پشت متن می‌نشیند عوض شده باشد.  P5-D198";
}

/* ── پاک‌سازی ───────────────────────────────────────────────────── */
if ( ! in_array( '--keep', $argv, true ) ) {
	$it = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $work, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $it as $f ) { $f->isDir() ? @rmdir( $f ) : @unlink( $f ); }
	@rmdir( $work );
} else {
	echo "صفحه‌های موقت: {$work}\n";
}

/* ── گزارش ──────────────────────────────────────────────────────── */
echo "\nکنتراست — متن ۴٫۵ · عنصر رابط ۳٫۰\n", str_repeat( '─', 78 ), "\n";

if ( $failed_pages ) {
	foreach ( $failed_pages as $f ) { printf( "  ⚠ %s\n", $f ); }
}
if ( ! $reports ) {
	fwrite( STDERR, "هیچ صفحه‌ای سنجیده نشد.\n" );
	exit( 2 );
}

$total_fail = 0;
$total_text = 0;
$total_ui   = 0;
$total_hov  = 0;
$total_st   = 0;
$accepted   = array();

foreach ( $reports as $slug => $rep ) {

	/*
	 * رنگ‌های ثابتِ مرجع که کاربر نگه داشت — ۴٫۲٫۲ بند ۴.
	 * «رنگ‌های محافظت‌شدهٔ بلوک AI و شمارهٔ کارت مثل مرجع می‌مانند.»
	 * کاوشگر برچسب `zone` می‌زند؛ این‌ها جدا شمرده می‌شوند و شکست نیستند.
	 * هر چیز دیگری — حتی یک مورد — همچنان آزمون را قرمز می‌کند.
	 */
	$real = array();
	foreach ( $rep['fails'] as $f ) {
		if ( ! empty( $f['zone'] ) ) {
			$accepted[ $f['zone'] ] = ( isset( $accepted[ $f['zone'] ] ) ? $accepted[ $f['zone'] ] : 0 ) + 1;
			continue;
		}
		$real[] = $f;
	}
	$rep['fails'] = $real;

	$n = count( $rep['fails'] );
	$total_fail += $n;
	$total_text += $rep['counts']['text'];
	$total_ui   += $rep['counts']['ui'];
	$total_hov  += $rep['counts']['hover'];
	$total_st   += $rep['counts']['state'];

	printf(
		"  %s %-10s %-14s متن %-4d رابط %-3d حالت %-3d هاور %-3d\n",
		$n ? '❌' : '✅',
		$slug,
		$rep['title'],
		$rep['counts']['text'],
		$rep['counts']['ui'],
		$rep['counts']['state'],
		$rep['counts']['hover']
	);

	foreach ( $rep['fails'] as $f ) {
		printf(
			"       ↳ %s · %s · %s   %s%s  %s در برابر %s   «%s»\n",
			$f['mode'],
			$f['state'],
			$f['kind'],
			$f['sel'],
			$f['size'] ? ' ' . $f['size'] . 'px' : '',
			number_format( $f['ratio'], 2 ),
			number_format( $f['need'], 1 ),
			$f['sample']
		);
	}
}

echo str_repeat( '─', 78 ) . "\n";
printf(
	"  صفحه: %d   سنجش: %d متن · %d رابط · %d حالت · %d هاور   افتاده: %d\n",
	count( $reports ),
	$total_text,
	$total_ui,
	$total_st,
	$total_hov,
	$total_fail
);

foreach ( $accepted as $zone => $count ) {
	printf( "  ☑ پذیرفته به حکم ۴٫۲٫۲ بند ۴ — رنگ ثابتِ مرجع، %s: %d سنجه\n", $zone, $count );
}

foreach ( $wx_out as $block ) { echo $block, "\n"; }

if ( $failed_pages || $wx_env ) { exit( 2 ); }
exit( $total_fail || $wx_fail ? 1 : 0 );
