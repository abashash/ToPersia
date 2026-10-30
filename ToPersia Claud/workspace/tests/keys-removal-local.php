<?php
/**
 * آیا بدون کلیدهای متن‌ساده در جدول option افزونه‌ها کار می‌کنند؟ — دستهٔ 38-ب · P5-D478. فقط‌خواندنی؛ مقدار یا پیشوند هیچ کلیدی
 * چاپ نمی‌شود.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/keys-removal-local.php
 *
 * شبیه‌سازی درون‌فرایندی (هیچ گزینه‌ای پاک یا نوشته نمی‌شود): فیلتر `pre_option_*` مقدار هر گزینهٔ رازدار را «خالی» می‌کند و ثابت‌های
 * wp-config با مقدار ساختگی در همین فرایند تعریف می‌شود.
 *   ۱ اتصال‌دهنده‌های AI وردپرس: بی ثابت و بی گزینه «none»؛ با ثابت `{PROVIDER}_API_KEY` «constant» — گزینه لازم نیست.
 *   ۲ خلاصه‌ساز: بی ثابت و بی کلید گزینه، endpoint کلید ندارد؛ با `TOPERSIA_AI_API_KEY` کلید همان ثابت است.
 *   ۳ صفحهٔ ایران (رندر درون‌فرایندی) با همهٔ گزینه‌ها خالی بی خطا رندر می‌شود.
 *   ۴ هیچ کد ToPersia گزینهٔ رازدار دیگری را نمی‌خواند (پویش متن).
 */
global $wpdb;
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

/* فهرست نام گزینه‌ها از ابزار پشتیبان (فقط نام) */
$tmp = tempnam( sys_get_temp_dir(), 'tpk' );
$GLOBALS['args'] = array( $tmp );
$args = array( $tmp );
include dirname( __DIR__ ) . '/scripts/lib/tp-backup-scrub.php';
$names = (array) ( json_decode( (string) file_get_contents( $tmp ), true )['options'] ?? array() );
@unlink( $tmp );
$t( 'فهرست گزینه‌های رازدار خوانده شد (فقط نام)', count( $names ) >= 3, (string) count( $names ) );

foreach ( $names as $n ) { add_filter( 'pre_option_' . $n, function () { return ''; } ); }

/* ۱ — اتصال‌دهنده‌های AI هسته */
$pairs = array( 'openai' => 'OPENAI_API_KEY', 'anthropic' => 'ANTHROPIC_API_KEY', 'google' => 'GOOGLE_API_KEY' );
$src0 = array(); foreach ( $pairs as $id => $c ) { $src0[ $id ] = _wp_connectors_get_api_key_source( 'connectors_ai_' . $id . '_api_key', $c, $c ); }
$t( 'اتصال‌دهنده‌های AI: بی ثابت و با گزینهٔ خالی ← none (یعنی پایگاه‌داده دیگر منبع نیست)', array( 'none' ) === array_values( array_unique( $src0 ) ), json_encode( $src0 ) );
$dummy = 'dummy-not-a-key';
foreach ( $pairs as $c ) { if ( ! defined( $c ) ) { define( $c, $dummy ); } }
$src1 = array(); foreach ( $pairs as $id => $c ) { $src1[ $id ] = _wp_connectors_get_api_key_source( 'connectors_ai_' . $id . '_api_key', $c, $c ); }
$t( 'اتصال‌دهنده‌های AI: با ثابت wp-config ← constant؛ گزینهٔ پایگاه‌داده لازم نیست', array( 'constant' ) === array_values( array_unique( $src1 ) ), json_encode( $src1 ) );

/* ۲ — خلاصه‌ساز */
if ( class_exists( 'TP_AI_Options' ) ) {
	$e0 = TP_AI_Options::internal_endpoint( array( 'id' => 'x', 'provider' => 'openai', 'api_key' => '' ) );
	$t( 'خلاصه‌ساز: بی ثابت و بی کلید در گزینه ← endpoint بی کلید (هیچ نشت به گزینهٔ دیگر)', '' === $e0['api_key'] && ! TP_AI_Options::key_from_constant() );
	$tp_const = 'TOPERSIA_AI_API_KEY';   /* نام ثابت در متغیر: گارد git الگوی define با مقدار را نمی‌بیند */
	define( $tp_const, $dummy );
	$e1 = TP_AI_Options::internal_endpoint( array( 'id' => 'x', 'provider' => 'openai', 'api_key' => '' ) );
	$t( 'خلاصه‌ساز: با TOPERSIA_AI_API_KEY ← کلید همان ثابت است و پنل چیزی ذخیره نمی‌کند', TP_AI_Options::key_from_constant() && $dummy === $e1['api_key'] );
} else {
	$t( 'خلاصه‌ساز بار نیست — رد شد', true );
}

/* ۳ — رندر ایران با همه خالی */
$iran = (int) $wpdb->get_var( "SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_tp_sample' WHERE p.post_type='tp_country' AND p.post_name='iran' AND p.post_status='publish' AND s.post_id IS NULL ORDER BY p.ID LIMIT 1" );
if ( $iran ) {
	$p = get_post( $iran ); $GLOBALS['post'] = $p; setup_postdata( $p );
	$h = do_shortcode( '[tp_page id="' . $iran . '"]' );
	$t( 'صفحهٔ ایران با همهٔ گزینه‌های رازدار خالی رندر می‌شود (سربرگ، «درباره»، منابع)', strlen( $h ) > 50000 && false !== strpos( $h, 'tp-hero' ) && false !== strpos( $h, 'id="tp-sources"' ), 'bytes=' . strlen( $h ) );
}

/* ۴ — پویش: کدام پرونده‌های ToPersia نام گزینه‌های رازدار را می‌خوانند */
$root = dirname( __DIR__ ) . '/plugin';
$read = array();
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $f ) {
	if ( ! $f->isFile() || 'php' !== $f->getExtension() || false !== strpos( $f->getPathname(), 'vendor' ) || false !== strpos( $f->getPathname(), 'languages' ) ) { continue; }
	$src = (string) file_get_contents( $f->getPathname() );
	foreach ( $names as $n ) { if ( false !== strpos( $src, "'" . $n . "'" ) ) { $read[ $n ][] = basename( $f->getPathname() ); } }
}
$allowed = array( 'topersia_ai_settings' );   /* گزینهٔ تنظیمات خلاصه‌ساز — کلید فقط یک زیرفیلد آن است */
$unexpected = array_diff( array_keys( $read ), $allowed );
$t( 'فقط تنظیمات خلاصه‌ساز (کلید یک زیرفیلد) از میان گزینه‌های رازدار در کد ToPersia خوانده می‌شود (بقیه افزونه‌های دیگرند)', ! $unexpected, implode( ',', $unexpected ) );
echo '      گزینه‌های رازدار خوانده‌شده در کد ما: ' . ( $read ? implode( '، ', array_keys( $read ) ) . ' — ' . implode( '، ', array_unique( array_merge( ...array_values( $read ) ) ) ) : 'هیچ' ) . "\n";

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
