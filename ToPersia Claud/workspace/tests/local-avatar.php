<?php
/**
 * آواتار محلی روی وردپرس واقعی — بی gravatar، بازکدگذاری، EXIF، پاک‌سازی  P5-D377
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/local-avatar.php
 *
 * فقط topersia.local. کاربر و فایل آزمون خودش ساخته و در پایان پاک می‌کند (با خودِ پاک‌کنندهٔ حریم خصوصی).
 * بارگذاری مرورگری (is_uploaded_file) در tests/user-flows.js سنجیده می‌شود.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }
if ( ! defined( 'DOING_AJAX' ) ) { define( 'DOING_AJAX', true ); }
$GLOBALS['av_t'] = array( 0, 0 );
function t( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['av_t'][0]++; echo "  ✅ $label\n"; } else { $GLOBALS['av_t'][1]++; echo "  ❌ $label   ← $note\n"; } }
class AV_Done extends Exception {}
set_error_handler( function ( $no, $str ) { return false !== strpos( $str, 'headers already sent' ); } );
add_filter( 'wp_die_ajax_handler', function () { return function () { throw new AV_Done(); }; } );
function av_call( callable $fn, array $post, array $files = array() ): array {
	$_POST = $post; $_REQUEST = $post; $_FILES = $files;
	ob_start();
	try { $fn(); } catch ( AV_Done $e ) {}
	$j = json_decode( (string) ob_get_clean(), true );
	return is_array( $j ) ? $j : array( 'success' => false );
}

echo "آواتار محلی (ToPersia_Avatar)\n";
t( 'کلاس‌ها بار شده', class_exists( 'ToPersia_Avatar' ) && class_exists( 'ToPersia_Inactivity' ) );
$GLOBALS['av_show'] = get_option( 'show_avatars', 1 );
echo '  · show_avatars محلی = ' . var_export( $GLOBALS['av_show'], true ) . " (افزونه آن را عوض نمی‌کند؛ برای آزمونِ برچسب موقتاً روشن فرض می‌شود)
";
add_filter( 'pre_option_show_avatars', '__return_true' );
t( 'کد افزونه show_avatars را لمس نمی‌کند', false === strpos( file_get_contents( TOPERSIA_USER_CORE_DIR . 'includes/class-topersia-avatar.php' ), 'show_avatars' ) || 1 === preg_match( '/^\s*\*.*show_avatars/m', file_get_contents( TOPERSIA_USER_CORE_DIR . 'includes/class-topersia-avatar.php' ) ) );

$tmp   = sys_get_temp_dir() . '/tp-av-' . bin2hex( random_bytes( 4 ) );
mkdir( $tmp );
$email = 'tp-av-' . bin2hex( random_bytes( 3 ) ) . '@topersia.invalid';
$uid   = wp_insert_user( array( 'user_login' => 'tpavtest' . bin2hex( random_bytes( 3 ) ), 'user_pass' => wp_generate_password( 24 ), 'user_email' => $email, 'role' => 'subscriber' ) );
t( 'کاربر آزمون ساخته شد', is_int( $uid ) && $uid > 0 );
$md5 = md5( strtolower( $email ) );

/* ── ۱) بی gravatar، در هر شکل ورودی ─────────────────────────────────── */
$comment = (object) array( 'comment_ID' => 1, 'user_id' => 0, 'comment_author_email' => $email );
$all = array(
	get_avatar_url( $uid ), get_avatar_url( $email ), get_avatar_url( 0 ), get_avatar_url( 'who@example.com' ), get_avatar_url( get_userdata( $uid ) ),
	get_avatar( $uid, 96 ), get_avatar( $email, 48 ), get_avatar( 'who@example.com', 32 ),
);
$bad = array_filter( $all, function ( $x ) use ( $md5 ) { return false !== stripos( (string) $x, 'gravatar' ) || false !== strpos( (string) $x, $md5 ) || false !== strpos( (string) $x, 'secure.' ); } );
t( 'هیچ خروجی آواتار gravatar یا md5 ایمیل ندارد (۸ شکل)', ! $bad, implode( ' | ', $bad ) );
$img = get_avatar( $uid, 96 );
t( 'برچسب img: width و height صریح و src از افزونه', false !== strpos( $img, 'width=\'96\'' ) && false !== strpos( $img, 'height=\'96\'' ) && false !== strpos( $img, 'assets/img/avatar-' ), $img );
t( 'شمایل خنثی پایدار برای یک شناسه', get_avatar_url( $uid ) === get_avatar_url( $uid ) );
$set = array(); for ( $i = 1; $i <= 40; $i++ ) { $set[ ToPersia_Avatar::default_url( $i ) ] = 1; }
t( 'رنگ مشتق از هش شناسه: چند تُن مختلف', count( $set ) >= 6, (string) count( $set ) );
foreach ( glob( TOPERSIA_USER_CORE_DIR . 'assets/img/avatar-*.svg' ) as $f ) { $ok = true; }
t( 'سیزده شمایل ایستا هست', 13 === count( glob( TOPERSIA_USER_CORE_DIR . 'assets/img/avatar-*.svg' ) ) );

/* ── ۲) نمونه‌ها ────────────────────────────────────────────────────── */
function av_img( string $path, string $type, int $w = 600, int $h = 400 ): void {
	$im = imagecreatetruecolor( $w, $h ); imagefilledrectangle( $im, 0, 0, $w, $h, imagecolorallocate( $im, 200, 80, 40 ) ); imagefilledellipse( $im, (int) ( $w / 2 ), (int) ( $h / 2 ), (int) ( $h / 2 ), (int) ( $h / 2 ), imagecolorallocate( $im, 20, 90, 200 ) );
	( 'png' === $type ) ? imagepng( $im, $path ) : ( 'webp' === $type ? imagewebp( $im, $path ) : imagejpeg( $im, $path, 90 ) );
}
/* JPEG با APP1 Exif و GPS (نشانگرهای متنی قابل‌جستجو) */
$jpg = $tmp . '/photo.jpg'; av_img( $jpg, 'jpeg' );
$raw = file_get_contents( $jpg );
$exif = "Exif\0\0" . 'MM' . "\0*\0\0\0\10" . 'GPSLatitude=35.6892N GPSLongitude=51.3890E Make=TOPERSIA-SECRET-CAM XMP:Creator=Someone';
$app1 = "\xFF\xE1" . pack( 'n', strlen( $exif ) + 2 ) . $exif;
file_put_contents( $jpg, substr( $raw, 0, 2 ) . $app1 . substr( $raw, 2 ) );
t( 'نمونهٔ JPEG دارای نشانگر GPS و سازنده', false !== strpos( file_get_contents( $jpg ), 'GPSLatitude' ) && false !== strpos( file_get_contents( $jpg ), 'TOPERSIA-SECRET-CAM' ) );

/* ── ۳) پردازش ───────────────────────────────────────────────────────── */
$r = ToPersia_Avatar::process( $jpg, $uid );
t( 'JPEG پذیرفته شد', true === $r, is_wp_error( $r ) ? $r->get_error_message() : '' );
$rec = ToPersia_Avatar::record( $uid );
t( 'فقط user meta: توکن ۲۴ هگز تصادفی', $rec && 1 === preg_match( '/^[a-f0-9]{24}$/', $rec['t'] ) );
$f256 = ToPersia_Avatar::dir() . $rec['t'] . '-256.webp'; $f96 = ToPersia_Avatar::dir() . $rec['t'] . '-96.webp';
t( 'هر دو فایل ساخته شد', is_file( $f256 ) && is_file( $f96 ) );
$i256 = getimagesize( $f256 ); $i96 = getimagesize( $f96 );
t( 'WebP مربع ۲۵۶ و ۹۶', 'image/webp' === $i256['mime'] && 256 === $i256[0] && 256 === $i256[1] && 'image/webp' === $i96['mime'] && 96 === $i96[0] && 96 === $i96[1], wp_json_encode( array( $i256[0] ?? 0, $i256[1] ?? 0, $i96[0] ?? 0 ) ) );
$bin = file_get_contents( $f256 ) . file_get_contents( $f96 );
t( 'EXIF و GPS و XMP و سازنده در خروجی نیست', false === stripos( $bin, 'GPSLat' ) && false === stripos( $bin, 'TOPERSIA-SECRET' ) && false === strpos( $bin, 'Exif' ) && false === stripos( $bin, 'XMP' ) && false === strpos( $bin, 'EXIF' ), '' );
t( 'پوشه index.php خالی دارد و بیرون از کتابخانهٔ رسانه (بی پیوست)', is_file( ToPersia_Avatar::dir() . 'index.php' ) && 0 === count( get_posts( array( 'post_type' => 'attachment', 'post_status' => 'any', 'author' => $uid, 'numberposts' => 1 ) ) ) );
$u1 = get_avatar_url( $uid, array( 'size' => 96 ) ); $u2 = get_avatar_url( $uid, array( 'size' => 200 ) );
t( 'نشانی عکس: ۹۶ ← فایل ۹۶، ۲۰۰ ← فایل ۲۵۶، بی gravatar', false !== strpos( $u1, '-96.webp' ) && false !== strpos( $u2, '-256.webp' ) && false === stripos( $u1 . $u2, 'gravatar' ), $u1 . ' ' . $u2 );
t( 'برچسب get_avatar با عکس: srcset دوبرابر', false !== strpos( get_avatar( $uid, 96 ), '-256.webp' ), get_avatar( $uid, 96 ) );

/* جایگزینی: فایل قبلی پاک */
$png = $tmp . '/p.png'; av_img( $png, 'png', 300, 500 );
$old = $rec['t'];
t( 'PNG جایگزین شد', true === ToPersia_Avatar::process( $png, $uid ) );
$rec2 = ToPersia_Avatar::record( $uid );
t( 'توکن عوض شد و فایل قبلی پاک شد', $rec2['t'] !== $old && ! is_file( $f256 ) && ! is_file( $f96 ) && is_file( ToPersia_Avatar::dir() . $rec2['t'] . '-256.webp' ) );
$wp = $tmp . '/w.webp'; av_img( $wp, 'webp', 200, 200 );
t( 'WebP پذیرفته', true === ToPersia_Avatar::process( $wp, $uid ) );

/* ── ۴) رد ──────────────────────────────────────────────────────────── */
$cur = ToPersia_Avatar::record( $uid )['t'];
$gif = $tmp . '/g.gif'; $im = imagecreatetruecolor( 40, 40 ); imagegif( $im, $gif );
$svg = $tmp . '/s.svg'; file_put_contents( $svg, '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"><script>alert(1)</script></svg>' );
$php = $tmp . '/x.jpg'; file_put_contents( $php, "<?php echo 1;" );
$big = $tmp . '/big.jpg'; file_put_contents( $big, substr( file_get_contents( $jpg ), 0, 200000 ) . str_repeat( "\0", 2200000 ) );
$tiny = $tmp . '/t.png'; av_img( $tiny, 'png', 8, 8 );
$huge = $tmp . '/h.png'; $im = imagecreatetruecolor( 9000, 20 ); imagepng( $im, $huge, 9 );
foreach ( array( 'GIF' => $gif, 'SVG' => $svg, 'PHP با پسوند jpg' => $php, 'بیش از ۲MB' => $big, 'کوچک‌تر از ۱۶' => $tiny, 'عرض بیش از ۸۰۰۰' => $huge, 'فایل ناموجود' => $tmp . '/none' ) as $label => $p ) {
	$res = ToPersia_Avatar::process( $p, $uid );
	t( "رد شد: $label", is_wp_error( $res ), 'پذیرفته شد' );
}
t( 'پس از ردها، عکس فعلی دست‌نخورده', ToPersia_Avatar::record( $uid )['t'] === $cur && is_file( ToPersia_Avatar::dir() . $cur . '-256.webp' ) );

/* ── ۵) AJAX: nonce و ورود و فقط خود کاربر ───────────────────────────── */
$a = ToPersia_Avatar::instance();
wp_set_current_user( 0 );
$j = av_call( array( $a, 'ajax_upload' ), array( 'nonce' => wp_create_nonce( 'topersia_user_core' ) ) );
t( 'مهمان: ردّ بارگذاری', empty( $j['success'] ) );
wp_set_current_user( $uid );
$j = av_call( array( $a, 'ajax_upload' ), array( 'nonce' => 'bad' ) );
t( 'nonce نادرست: ردّ', empty( $j['success'] ) );
$j = av_call( array( $a, 'ajax_upload' ), array( 'nonce' => wp_create_nonce( 'topersia_user_core' ) ) );
t( 'بی فایل: ردّ', empty( $j['success'] ) );
$j = av_call( array( $a, 'ajax_upload' ), array( 'nonce' => wp_create_nonce( 'topersia_user_core' ) ), array( 'avatar' => array( 'tmp_name' => $jpg, 'error' => 0, 'size' => 1000, 'name' => 'a.jpg' ) ) );
t( 'فایلی که از راه HTTP بارگذاری نشده (is_uploaded_file): ردّ', empty( $j['success'] ) );
$j = av_call( array( $a, 'ajax_delete' ), array( 'nonce' => wp_create_nonce( 'topersia_user_core' ) ) );
t( 'حذف عکس: فایل و متا پاک و شمایل خنثی', ! empty( $j['success'] ) && ! ToPersia_Avatar::record( $uid ) && ! is_file( ToPersia_Avatar::dir() . $cur . '-256.webp' ) && false !== strpos( (string) ( $j['data']['url'] ?? '' ), 'assets/img/avatar-' ) );
delete_transient( 'tp_av_rl_' . $uid );

/* ── ۶) غیرفعال ← شمایل خنثی ─────────────────────────────────────────── */
ToPersia_Avatar::process( $jpg, $uid );
t( 'پیش از غیرفعال: عکس', false !== strpos( get_avatar_url( $uid ), '.webp' ) );
update_user_meta( $uid, '_topersia_inactive', '1' );
$ref = new ReflectionProperty( 'ToPersia_Inactivity', 'memo' ); $ref->setAccessible( true ); $ref->setValue( null, array() );
t( 'کاربر غیرفعال: عکسش نشان داده نمی‌شود', false === strpos( get_avatar_url( $uid ), '.webp' ) );
delete_user_meta( $uid, '_topersia_inactive' ); $ref->setValue( null, array() );

/* ── ۷) برون‌بر و پاک‌کننده ──────────────────────────────────────────── */
$ex = ToPersia_Data_Requests::instance()->export( $email );
$flat = wp_json_encode( $ex, JSON_UNESCAPED_UNICODE );
t( 'برون‌بر: آواتار هست', false !== strpos( $flat, 'avatar' ) && false !== strpos( $flat, '.webp' ), substr( $flat, 0, 200 ) );
$tok = ToPersia_Avatar::record( $uid )['t'];
$er = ToPersia_Data_Requests::instance()->erase( $email );
t( 'پاک‌کننده: حساب پاک شد', ! empty( $er['items_removed'] ) && false === get_userdata( $uid ) );
t( 'پاک‌کننده: فایل‌های آواتار پاک شد', ! is_file( ToPersia_Avatar::dir() . $tok . '-256.webp' ) && ! is_file( ToPersia_Avatar::dir() . $tok . '-96.webp' ) );

/* ── پاک‌سازی ─────────────────────────────────────────────────────────── */
foreach ( (array) glob( $tmp . '/*' ) as $f ) { @unlink( $f ); }
@rmdir( $tmp );
if ( get_userdata( $uid ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; wp_delete_user( $uid ); }

$g = $GLOBALS['av_t'];
echo "\n  گذشته: {$g[0]}   افتاده: {$g[1]}\n";
if ( $g[1] ) { exit( 1 ); }
