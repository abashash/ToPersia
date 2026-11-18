<?php
/**
 * پذیرش قالب — راننده.  P5-D219
 *
 *   php tests/acceptance.php
 *
 * صفحهٔ استان را با همان پیش‌نمایش آزمون می‌سازد، سند اولِ فایل مرجع را
 * کنارش می‌گذارد، و `acceptance.js` را روی هر دو اجرا می‌کند: کروم ۱۴۴۰،
 * کروم ۳۹۰، فایرفاکس ۱۴۴۰.
 *
 * Playwright فقط برای همین آزمون است — خواستهٔ صریح بند ۶. هارنس کنتراست
 * روی CDP می‌ماند. بسته از کش `npx` پیدا می‌شود؛ اگر نبود:
 *   npx.cmd playwright install chromium firefox
 *
 * کد خروج: ۰ جدول ناهمخوانی خالی · ۱ ناهمخوانی · ۲ محیط ناقص
 */

$ROOT = dirname( __DIR__ );
$DEST = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tp-acceptance';

if ( is_dir( $DEST ) ) {
	foreach ( glob( $DEST . '/*' ) as $f ) { @unlink( $f ); }
} else {
	mkdir( $DEST, 0777, true );
}

/* ۱ — صفحهٔ ما، با همان سازندهٔ پیش‌نمایش */
passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/preview.php' ) . ' ' . escapeshellarg( $DEST ) . ' > ' . ( '\\' === DIRECTORY_SEPARATOR ? 'NUL' : '/dev/null' ), $code );

if ( 0 !== $code || ! is_file( $DEST . '/province.html' ) ) {
	fwrite( STDERR, "صفحهٔ استان ساخته نشد\n" );
	exit( 2 );
}

/* ۲ — مرجع: سند اولِ فایل. دومی همان است منهای لایهٔ داده. */
$ref = file_get_contents( $ROOT . '/reference/ToPersia-G3-Province-Template.html' );
$end = strpos( $ref, '</body>' );
$ref = substr( $ref, 0, $end ) . "</body>\n</html>\n";

/*
 * یک چیز از میزبانِ زنده به مرجع اضافه می‌شود: ظرفِ اسکرولِ `html`.
 *
 * وودمارت روی **همهٔ** صفحه‌های سایت `html{overflow-y:scroll;
 * scrollbar-gutter:stable}` دارد، پس جای نوار اسکرول همیشه رزرو است و
 * پهنای در دسترس ۱۵ پیکسل کمتر. مرجعِ تنها این را نداشت و هر `.wrap`
 * ۷٫۵ پیکسل پهن‌تر درمی‌آمد — اختلافی که مال میزبان است، نه مال قالب.
 * بی‌این، آزمون محیط را می‌سنجد نه انتقال را.
 */
$host = "<style>/* میزبان زنده — woodmart-base-rtl.css */"
	. "html{overflow-x:hidden;overflow-y:scroll;scrollbar-gutter:stable}</style>\n";
$ref  = preg_replace( '/<head>/', "<head>\n" . $host, $ref, 1 );

file_put_contents( $DEST . '/reference.html', $ref );

/*
 * ۳ — Playwright — P5-D620: کش npx ممکن است چند نسخه داشته باشد و نخستین آن (به ترتیب نام پوشه) لزوماً نسخه‌ای نیست
 * که مرورگرش نصب است؛ ۵۳-پ با کش 1.66 بی مرورگر کد ۲ گرفت. پس هر نامزد را با browsers.json خودش می‌سنجیم: بازنگری
 * (revision) کرومیوم، کرومیوم بی‌سر و فایرفاکس باید در پوشۀ مرورگرهای Playwright (PLAYWRIGHT_BROWSERS_PATH، وگرنه
 * %LOCALAPPDATA%\ms-playwright) باشد؛ از نامزدهای کامل تازه‌ترین نسخه. TP_PLAYWRIGHT هنوز اگر داده شود برنده است.
 */
function tp_acc_playwright() {
	$own = getenv( 'TP_PLAYWRIGHT' );
	if ( $own ) {
		return array( $own, '' );
	}
	$bdir  = getenv( 'PLAYWRIGHT_BROWSERS_PATH' );
	$bdir  = $bdir && '0' !== $bdir ? $bdir : getenv( 'LOCALAPPDATA' ) . '/ms-playwright';
	$cands = (array) glob( getenv( 'LOCALAPPDATA' ) . '/npm-cache/_npx/*/node_modules/playwright/package.json' );
	foreach ( array_filter( explode( PATH_SEPARATOR, (string) getenv( 'NODE_PATH' ) ) ) as $np ) {
		$cands[] = rtrim( $np, '/\\' ) . '/playwright/package.json';
	}
	$best = null;
	$log  = array();
	foreach ( array_unique( $cands ) as $pkg ) {
		$nm  = dirname( dirname( $pkg ) );
		$bj  = $nm . '/playwright-core/browsers.json';
		$ver = is_file( $nm . '/playwright-core/package.json' ) ? (string) ( json_decode( (string) file_get_contents( $nm . '/playwright-core/package.json' ), true )['version'] ?? '' ) : '';
		if ( ! is_file( $pkg ) || ! is_file( $bj ) || '' === $ver ) {
			continue;
		}
		$miss = array();
		foreach ( (array) ( json_decode( (string) file_get_contents( $bj ), true )['browsers'] ?? array() ) as $b ) {
			if ( ! in_array( $b['name'] ?? '', array( 'chromium', 'chromium-headless-shell', 'firefox' ), true ) ) {
				continue;
			}
			$rev = $b['revisionOverrides']['win64'] ?? $b['revision'];
			$dir = str_replace( '-', '_', $b['name'] ) . '-' . $rev;
			if ( ! is_dir( $bdir . '/' . $dir ) ) {
				$miss[] = $dir;
			}
		}
		$log[] = $ver . ( $miss ? ' — نیست: ' . implode( '، ', $miss ) : ' — کامل' ) . ' (' . $nm . ')';
		if ( ! $miss && ( null === $best || version_compare( $ver, $best[1], '>' ) ) ) {
			$best = array( $nm, $ver );
		}
	}
	return $best ? $best : array( '', implode( "\n  ", $log ) );
}

list( $pw, $pw_why ) = tp_acc_playwright();

if ( ! $pw || ! is_dir( $pw . '/playwright' ) ) {
	fwrite( STDERR, "Playwright با مرورگر نصب‌شده پیدا نشد. نامزدها:\n  " . ( '' !== $pw_why ? $pw_why : '(هیچ)' ) . "\nنصب: npx.cmd playwright install chromium firefox — یا TP_PLAYWRIGHT را به پوشۀ node_modules بده.\n" );
	exit( 2 );
}

putenv( 'NODE_PATH=' . $pw );

/*
 * نسخهٔ محلیِ سایت — ۴٫۲٫۳ بند ۱ · P5-D203.
 *   php tests/acceptance.php --url=http://topersia.local/province/isfahan/ --url=…
 * هر نشانی جدا در برابر همان مرجع، در همان سه ترکیب.
 */
$urls = array();
foreach ( $argv as $arg ) {
	if ( 0 === strpos( $arg, '--url=' ) ) { $urls[] = substr( $arg, 6 ); }
}

if ( ! $urls ) {
	passthru( 'node ' . escapeshellarg( __DIR__ . '/acceptance.js' ) . ' ' . escapeshellarg( $DEST ), $code );
	echo "\nجزئیات: {$DEST}" . DIRECTORY_SEPARATOR . "acceptance.json\n";
	exit( $code );
}

$worst = 0;
foreach ( $urls as $u ) {
	echo "\n▸ {$u}\n";
	passthru( 'node ' . escapeshellarg( __DIR__ . '/acceptance.js' ) . ' ' . escapeshellarg( $DEST ) . ' ' . escapeshellarg( $u ), $code );
	$worst = max( $worst, $code );
}
echo "\nجزئیات: {$DEST}" . DIRECTORY_SEPARATOR . "acceptance-*.json\n";
exit( $worst );
