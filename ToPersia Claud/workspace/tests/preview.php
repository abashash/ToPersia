<?php
/**
 * ساخت صفحهٔ مستقل برای آزمون چیدمان در مرورگر.
 *
 * منطق PHP را assert.php می‌گیرد؛ این یکی چیزی را می‌سنجد که فقط موتور
 * چیدمان می‌داند: عرض واقعی ستون، سرریز، ترتیب بلوک‌ها، کنتراست.
 *
 * اجرا:  php tests/preview.php <پوشهٔ مقصد>
 */

ob_start();
require __DIR__ . '/run.php';
ob_end_clean();

/*
 * ۴٫۴٫۱ — تاکردن جست‌وجوی پنجرۀ فهرست از هسته است (P5-D265): جدول قاعدۀ
 * `topersia_search_fold_rules()` و نام تاشدۀ هر عضو (`data-tp-fold`). سایت
 * همیشه هسته را دارد؛ پیش‌نمایش هم همان را بار می‌کند و `TopersiaG` را همان‌طور
 * می‌نویسد که `wp_localize_script` — وگرنه جست‌وجوی فهرست این‌جا فقط حروف را
 * کوچک می‌کرد و snav-check صفحۀ بی‌هسته را می‌سنجید، نه آنچه کاربر می‌بیند.
 * run.php بی هسته می‌ماند: assert.php خروجی بی هسته را هم می‌پاید.
 */
require_once dirname( __DIR__ ) . '/plugin/topersia-core/includes/topersia-fold.php';
require_once TP_PLUGIN_DIR . 'includes/topersia-g-core.php';
$tp_g_js = '<script>var TopersiaG = ' . json_encode( array( 'rest' => '', 'fold' => topersia_search_fold_js_rules() ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ";</script>\n";

$dest = isset( $argv[1] ) ? rtrim( $argv[1], '/\\' ) : sys_get_temp_dir() . '/tp-preview';
if ( ! is_dir( $dest ) ) { mkdir( $dest, 0777, true ); }

$assets = dirname( __DIR__ ) . '/plugin/topersia-render/assets/';
$refs   = dirname( __DIR__ ) . '/reference/';

/* اسکریپت‌های هوا هم کپی می‌شوند گرچه صفحه‌های زیر صدایشان نمی‌زنند:
   صفحه‌های دستیِ همین پوشه از آن‌ها استفاده می‌کنند و نسخهٔ دستیِ عقب‌مانده
   بدترین حالت است — چیزی را نشان می‌دهد که دیگر تولیدی نیست.  P5-D198 */
foreach ( array(
	'topersia-tokens.css',
	'topersia-theme-bridge.css',
	'topersia-g-template.css',
	'topersia-g-zones.js',
	'topersia-g-weather.js',
	'topersia-g-weather-fx.js',
) as $f ) {
	copy( $assets . $f, $dest . '/' . $f );
}

/*
 * CSS زندهٔ قالب و افزونهٔ کاربر — از `reference/`.  P5-D202
 *
 * بی‌این‌ها پیش‌نمایش دروغ می‌گوید: هر چیزی در انزوا درست به نظر می‌رسد.
 * همان ترتیبی که در `<head>` سایت است، چون با وزن برابر دیرتر یعنی برنده.
 */
$foreign = array( 'woodmart-base-rtl.css', 'user-core.css' );

foreach ( $foreign as $f ) {
	if ( is_file( $refs . $f ) ) { copy( $refs . $f, $dest . '/' . $f ); }
	else { fwrite( STDERR, "⚠ مرجع نیست: {$f}\n" ); }
}

/* کلاس‌های `<body>` زنده — قاعده‌های tokens.css با `.tp-shell` فعال
   می‌شوند و بی‌آن، پیش‌نمایش لایهٔ توکن را خاموش نشان می‌دهد.  P5-D206 */
require_once __DIR__ . '/live-body.php';
$body_class = tp_live_body_class( dirname( __DIR__ ) );

/*
 * «province-ltr» — همان استان در صفحهٔ چپ‌به‌راست (`is_rtl()` نادرست).  P5-D243
 * نسخهٔ محلی فقط فارسی دارد (/en/ و /ar/ به فارسی برمی‌گردند)، پس رفتار
 * فلش و کشیدن در چپ‌به‌راست همین‌جا سنجیده می‌شود: <html dir="ltr">، بی
 * کلاس `rtl` بدنه و بی شیوه‌نامهٔ راست‌به‌چپِ وودمارت.
 */
$cases = array(
	'province'     => array( 20, 'استان اصفهان' ),
	'city'         => array( 30, 'شیراز' ),
	'desert'       => array( 40, 'دشت لوت' ),
	'mountain'     => array( 24, 'کرکس' ),
	'province-ltr' => array( 20, 'Isfahan Province (LTR)', 'ltr' ),
	/*
	 * «province-more» — همان استان با چهارده روستا، فقط برای behaviour.js.  P5-D244
	 * هیچ کارت صفحهٔ محلی بیش از شش کاشی ندارد، پس «{N} مورد دیگر» در مرورگر
	 * جز این‌جا سنجیده نمی‌شد. دادهٔ آزمون برای بقیهٔ صفحه‌ها دست نمی‌خورد.
	 */
	'province-more' => array( 20, 'استان اصفهان — چهارده روستا', 'more' ),
);

foreach ( $cases as $slug => $case ) {

	$ltr  = isset( $case[2] ) && 'ltr' === $case[2];
	$more = isset( $case[2] ) && 'more' === $case[2];
	$keep = array( $GLOBALS['tp_posts'], $GLOBALS['tp_rel'] );

	if ( $more ) {
		$vil = array();
		foreach ( array( 'ابیانه', 'نیاسر', 'برزک', 'قمصر', 'جوشقان', 'کامو', 'ورکان', 'طرق', 'نشلج', 'مشکات', 'آزوار', 'بیدهند', 'هنجن', 'طامه' ) as $i => $name ) {
			$id                         = 3000 + $i;
			$GLOBALS['tp_posts'][ $id ] = array( 'type' => 'tp_village', 'title' => $name, 'url' => 'https://topersia.com/village-' . $id . '/' );
			$vil[]                      = $id;
		}
		$GLOBALS['tp_rel']['75']['children'][20] = $vil;
	}

	$GLOBALS['tp_current'] = $case[0];
	if ( $ltr ) { $GLOBALS['tp_ltr'] = true; }
	$body = tp_do( 'tp_page', array() );
	unset( $GLOBALS['tp_ltr'] );
	list( $GLOBALS['tp_posts'], $GLOBALS['tp_rel'] ) = $keep;

	$html  = "<!DOCTYPE html>\n";
	$html .= $ltr
		? "<html lang=\"en\" dir=\"ltr\" data-tp-mode=\"light\">\n<head>\n"
		: "<html lang=\"fa\" dir=\"rtl\" data-tp-mode=\"light\">\n<head>\n";
	$html .= "<meta charset=\"utf-8\">\n";
	$html .= "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
	$html .= "<title>" . $case[1] . " — آزمون چیدمان</title>\n";
	$html .= "<link rel=\"stylesheet\" href=\"topersia-tokens.css\">\n";

	/*
	 * CSS درون‌خطیِ خودِ افزونه با تنظیم‌های پیش‌فرض — همان که
	 * `wp_add_inline_style` بعد از توکن‌ها می‌گذارد.  P5-D219
	 *
	 * مهم‌ترینش ریشهٔ ۱۷ پیکسلی است. بی‌این، آزمون پذیرش روی ریشهٔ ۱۶
	 * مرورگر اجرا می‌شد و نمی‌دید که روی سایت هر `rem` مرجع ۶٪ بزرگ‌تر
	 * درمی‌آید.
	 */
	$html .= "<style>:root{--tp-base:17px}html:root{font-size:calc(17px * var(--tp-scale,1))}"
		. "html:root{--wd-text-font-size:17px}</style>\n";
	$html .= "<link rel=\"stylesheet\" href=\"topersia-theme-bridge.css\">\n";
	$html .= "<link rel=\"stylesheet\" href=\"topersia-g-template.css\">\n";

	/* بعد از ما می‌آیند، همان‌طور که در سایت — P5-D202 */
	foreach ( $foreign as $f ) {
		if ( is_file( $dest . '/' . $f ) && ! ( $ltr && false !== strpos( $f, '-rtl' ) ) ) {
			$html .= "<link rel=\"stylesheet\" href=\"{$f}\">\n";
		}
	}
	$classes = $ltr ? trim( preg_replace( '/(^|\s)rtl(?=\s|$)/', ' ', $body_class ) ) : $body_class;
	/* JS هم می‌آید، وگرنه مودال و برگهٔ بخش‌ها آزمون‌پذیر نیستند */
	$html .= "</head>\n<body class=\"" . $classes . "\">\n" . $body
		. "\n" . $tp_g_js . "<script src=\"topersia-g-zones.js\" defer></script>\n</body>\n</html>\n";

	file_put_contents( $dest . '/' . $slug . '.html', $html );
	printf( "%-10s pid %-3d %7d بایت\n", $slug, $case[0], strlen( $html ) );
}

echo "\nمقصد: ", $dest, "\n";
