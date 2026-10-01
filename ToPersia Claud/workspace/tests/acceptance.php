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

/* ۳ — Playwright */
$pw = getenv( 'TP_PLAYWRIGHT' );

if ( ! $pw ) {
	$cache = getenv( 'LOCALAPPDATA' ) . '/npm-cache/_npx';
	foreach ( (array) glob( $cache . '/*/node_modules/playwright/package.json' ) as $pkg ) {
		$pw = dirname( dirname( $pkg ) );
		break;
	}
}

if ( ! $pw || ! is_dir( $pw . '/playwright' ) ) {
	fwrite( STDERR, "Playwright پیدا نشد. TP_PLAYWRIGHT را به پوشهٔ node_modules بده.\n" );
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
