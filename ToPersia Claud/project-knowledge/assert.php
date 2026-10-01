<?php
/** آزمون ادعا — هر قاعدهٔ رندر یک ادعا. */

ob_start();
require __DIR__ . '/run.php';
ob_end_clean();

$pass = 0;
$fail = 0;

function t( $label, $ok, $note = '' ) {
	global $pass, $fail;
	if ( $ok ) { $pass ++; printf( "  ✅ %s\n", $label ); }
	else       { $fail ++; printf( "  ❌ %s   %s\n", $label, $note ); }
}

function r( $pid, $tag, $atts = array() ) {
	$GLOBALS['tp_current'] = $pid;
	return tp_do( $tag, $atts );
}

echo "\nقواعد رندر\n" . str_repeat( '─', 78 ) . "\n";

/* عدد */
t( 'عدد: رقم فارسی + جداکنندهٔ ٬', false !== strpos( r( 20, 'tp_stats' ), '۵٬۱۲۰٬۸۵۰' ) );
t( 'عدد: واحد از نقشه می‌آید، نه از عنوان JetEngine', false !== strpos( r( 20, 'tp_stats' ), 'کیلومتر مربع' ) );
t( 'عدد اعشاری: بخش اعشار جدا نمی‌شود', false !== strpos( r( 40, 'tp_stats' ), '۷۰.۷' ) );

/* سال */
$badges_lut = r( 40, 'tp_badges' );
t( 'سال: بدون جداکننده (۲۰۱۶ نه ۲٬۰۱۶)', false !== strpos( $badges_lut, '۲۰۱۶' ) && false === strpos( $badges_lut, '۲٬۰۱۶' ) );
t( 'نشان با سالش جفت می‌شود', false !== strpos( $badges_lut, 'میراث جهانی' ) && false !== strpos( $badges_lut, '۱۳۸۵' ) );
t( 'سوییچر نادرست نشان نمی‌سازد', false === strpos( $badges_lut, 'ذخیره‌گاه زیست‌کره' ) );
t( 'استان بدون سوییچر روشن: بخش نشان‌ها کامل ناپدید', '' === r( 20, 'tp_badges' ) );

/* لاتین */
t( 'لاتین: مونو و چپ‌به‌راست', false !== strpos( r( 30, 'tp_facts' ), '<span class="tp-lat" dir="ltr">SYZ</span>' ) );

/* واژه‌نامه — P5-D169 */
$facts_lut = r( 40, 'tp_facts' );
$lut_page = r( 40, 'tp_stats' ) . $facts_lut;
t( 'واژه‌نامه: «نمکی» چاپ می‌شود نه salt', false !== strpos( $lut_page, 'نمکی' ) && false === strpos( $lut_page, '>salt<' ) );
t( 'واژه‌نامه: بهترین فصل «زمستان» نه winter', false !== strpos( r( 40, 'tp_seasons' ), 'زمستان' ) );
t( 'واژه‌نامه: وضعیت محتوا «راستی‌آزمایی‌شده» نه verified', false !== strpos( r( 20, 'tp_sources' ), 'راستی‌آزمایی‌شده' ) );

/* ردیف بازنشسته — P5-D121 */
$faq = r( 20, 'tp_faq' );
t( 'ریپیتر: ردیف بازنشسته در پرسش‌ها نمی‌آید', false === strpos( $faq, 'پرسش بازنشسته' ) );
t( 'ریپیتر: ردیف فعال می‌آید', false !== strpos( $faq, 'بهترین زمان سفر به اصفهان' ) );
t( 'ریپیتر: ردیف بدون وضعیت، فعال شمرده می‌شود', false !== strpos( r( 20, 'tp_sources' ), 'یونسکو' ) );
t( 'ریپیتر: صوت بازنشسته نمی‌آید', false === strpos( r( 20, 'tp_audio' ), 'نقش جهان' ) );

/* ناپدیدشدن */
t( 'بخش بدون داده: گالری شهر ناپدید', '' === r( 30, 'tp_gallery' ) );
t( 'بخش بدون داده: پرسش‌های کویر ناپدید', '' === r( 40, 'tp_faq' ) );
t( 'بخش بدون داده: رابطهٔ بی‌نتیجه ناپدید', '' === r( 40, 'tp_relgrid', array( 'rel' => 'nature' ) ) );

/* تک‌فیلد-تک‌جا */
$stats_p = r( 20, 'tp_stats' );
$facts_p = r( 20, 'tp_facts' );
t( 'هر فیلد فقط یک‌جا: جمعیت در آمار هست و در حقایق نیست', false !== strpos( $stats_p, 'جمعیت' ) && false === strpos( $facts_p, 'جمعیت' ) );
t( 'هیچ فیلدی بی‌صدا نمی‌افتد: نوع اقلیم در حقایق هست', false !== strpos( $facts_p, 'نیمه‌خشک' ) );
t( 'مختصات در آمار و حقایق نیست، زیر نقشه است', false === strpos( $stats_p, '32.6539' ) && false === strpos( $facts_p, '32.6539' ) && false !== strpos( r( 20, 'tp_map' ), '32.6539' ) );

/* برچسب — P5-D175 */
t( 'برچسب: geo_boundary دیگر هم‌نام geo_coordinates نیست', 1 === substr_count( $facts_p . r( 20, 'tp_map' ), 'مختصات' ) );

/* جهت رابطه — P5-D170 */
t( 'رابطه: والدِ استان (کشور) در مسیر راهنما می‌آید', false !== strpos( r( 20, 'tp_crumbs' ), 'ایران' ) );
t( 'رابطه: فرزندِ یک‌به‌یک (مرکز استان) در آمار می‌آید', false !== strpos( $stats_p, 'شهر اصفهان' ) );
t( 'رابطه: شهر بدون پیکربندی، مسیر راهنمای کوتاه می‌گیرد', false === strpos( r( 30, 'tp_crumbs' ), 'ایران' ) );

/* دادهٔ زنده */
$wx = r( 20, 'tp_weather' );
t( 'دادهٔ زنده: هوا در HTML کش‌شده مقدار ندارد', false !== strpos( $wx, 'is-load' ) && false === strpos( $wx, '°' ) );
t( 'دادهٔ زنده: وضعیت ذخیره در HTML نمی‌آید', false !== strpos( r( 20, 'tp_actionbar' ), 'aria-pressed="false"' ) );

/* تاریخ */
t( 'تاریخ: Y-m-d به شمسی (۱۲ خرداد ۱۴۰۴)', false !== strpos( r( 20, 'tp_sources' ), '۱۲ خرداد ۱۴۰۴' ), topersia_g_date_fa( '2025-06-02' ) );
t( 'تاریخ: timestamp هم کار می‌کند', '' !== topersia_g_date_fa( '1749081600' ), topersia_g_date_fa( '1749081600' ) );
t( 'تاریخ: مقدار بی‌معنا چیزی نمی‌سازد', '' === topersia_g_date_fa( 'پارسال' ) );

/* امنیت */
$GLOBALS['tp_meta'][40]['provinces_spanning'] = '<script>alert(1)</script>کرمان';
t( 'گریز: تگ تزریق‌شده اجرا نمی‌شود', false === strpos( r( 40, 'tp_facts' ), '<script>' ) );

/* نوع‌محوری */
t( 'یک رندرکننده برای هر سه: هر سه سربرگ ساختند', '' !== r( 20, 'tp_hero' ) && '' !== r( 30, 'tp_hero' ) && '' !== r( 40, 'tp_hero' ) );
t( 'نوع‌محوری: کویر فیلد فرودگاه ندارد و چیزی نمی‌شکند', false === strpos( $facts_lut, 'فرودگاه' ) );
t( 'نوع‌محوری: شهر فیلد فرودگاه دارد و می‌آید', false !== strpos( r( 30, 'tp_facts' ), 'فرودگاه' ) );

/* آهنگساز صفحه — P5-D183 */
$page = r( 20, 'tp_page' );
t( 'صفحه: یک شورت‌کد کل بدنه را می‌سازد', strlen( $page ) > 3000, strlen( $page ) . ' بایت' );
t( 'صفحه: ستون اصلی و کناری مارک‌آپ خودمان‌اند', false !== strpos( $page, 'class="tp-main"' ) && false !== strpos( $page, 'class="tp-rail"' ) );
t( 'صفحه: نوار بخش‌ها فقط بخش‌های پرشده را دارد', false !== strpos( $page, 'href="#tp-map"' ) && false === strpos( $page, 'href="#tp-ancient"' ) );
t( 'صفحه: کویر بدون رابطه، نوار بخش کوتاه‌تری می‌گیرد', substr_count( r( 40, 'tp_page' ), '<a href="#tp-' ) < substr_count( $page, '<a href="#tp-' ) );
t( 'صفحه: سربرگ و آمار داخل همین خروجی‌اند', false !== strpos( $page, 'tp-hero' ) && false !== strpos( $page, 'tp-stats' ) );

/* پوشش نقشه */
$map_count = count( topersia_g_map() );
t( 'نقشه: دقیقاً ۵۷ فیلد', 57 === $map_count, "شمار: {$map_count}" );
$choice = 0;
foreach ( topersia_g_map() as $f ) { if ( 'choice' === $f['kind'] ) { $choice ++; } }
t( 'نقشه: سیزده فیلد choice', 13 === $choice, "شمار: {$choice}" );

/* گارد بازگشت — مسیر افزونه، شکست چیدمان فضای کاری ۱۴۰۵/۰۶/۲۴ */
t(
	'مسیر: افزونه پیدا شد و هر سه فایلش خواندنی‌اند',
	defined( 'TP_PLUGIN_DIR' )
		&& is_readable( TP_PLUGIN_DIR . 'includes/topersia-g-map.php' )
		&& is_readable( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' )
		&& is_readable( TP_PLUGIN_DIR . 'assets/topersia-g-zones.css' ),
	defined( 'TP_PLUGIN_DIR' ) ? TP_PLUGIN_DIR : 'تعریف نشده'
);

/* گارد بازگشت — کلاس بی‌پیشوند، آزمون زندهٔ ۴٫۰٫۱
   یک مقدار پیش‌فرض پارامتر از قلم افتاده بود و شش بخش را بی‌استایل کرد.
   این گارد کل HTML خروجی را می‌گردد، نه فقط فایل را. */
$all = '';
foreach ( array( 20, 30, 40 ) as $case_pid ) {
	$all .= r( $case_pid, 'tp_page' );
}
preg_match_all( '/class="([^"]+)"/', $all, $m );
$bad = array();
foreach ( $m[1] as $attr ) {
	foreach ( explode( ' ', $attr ) as $one ) {
		$one = trim( $one );
		if ( '' === $one ) { continue; }
		if ( 0 === strpos( $one, 'tp-' ) || 0 === strpos( $one, 'is-' ) || 0 === strpos( $one, 'has-' ) ) { continue; }
		$bad[ $one ] = $one;
	}
}
t( 'HTML: هیچ کلاس بی‌پیشوندی تولید نمی‌شود  P5-D180', empty( $bad ), implode( ' · ', $bad ) );

preg_match_all( '/ id="([^"]+)"/', $all, $mi );
$badid = array_filter( $mi[1], function ( $one ) { return 0 !== strpos( $one, 'tp' ); } );
t( 'HTML: هیچ شناسهٔ بی‌پیشوندی تولید نمی‌شود', empty( $badid ), implode( ' · ', $badid ) );

/* گارد بازگشت — باگ سرریز اسلایدر، آزمون زندهٔ ۴٫۰٫۰ */
$css = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-zones.css' );
t( 'CSS: فرزندان گرید اجازهٔ کوچک‌شدن دارند', false !== strpos( $css, '.tp-slider-wrap,.tp-slider,' ) && false !== strpos( $css, 'min-inline-size:0}' ) );
t( 'CSS: پوستهٔ اسلایدر سرریز را مهار می‌کند', false !== strpos( $css, '.tp-slider-wrap{inline-size:100%;max-inline-size:100%;overflow:hidden}' ) );
t( 'CSS: کارت آمار با هر تعداد خانه پر می‌شود', false !== strpos( $css, 'repeat(auto-fit,minmax(170px,1fr))' ) );
t( 'CSS: هیچ رنگ ثابت شش‌رقمی بیرون از گرادیان نیست', 0 === preg_match( '/color:\s*#[0-9a-fA-F]{3,6}\b/', $css ), 'یافت شد' );
$braces = substr_count( $css, '{' ) - substr_count( $css, '}' );
t( 'CSS: آکولادها متوازن‌اند', 0 === $braces, "اختلاف: {$braces}" );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
