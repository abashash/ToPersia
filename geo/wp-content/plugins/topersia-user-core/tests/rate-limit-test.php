<?php
/**
 * هارنس آزمون محدودسازی نرخ ورود — سناریوهای ۱ تا ۴ و ۷ سند SECURITY.md
 *
 * روش: ترنزینت‌ها در حافظه استاب می‌شوند و متدهای خصوصی با Reflection
 * صدا زده می‌شوند. هیچ نصب وردپرسی لازم نیست.
 *
 * اجرا:  php tests/rate-limit-test.php
 * خروج ۰ یعنی همه گذشتند؛ خروج ۱ یعنی دست‌کم یکی شکست.
 *
 * سناریوهای ۵ و ۶ (شمارندۀ حدس روی توکن) در این هارنس نیستند، چون
 * `verify_otp()` به `$wpdb` و `wp_send_json_*` وابسته است که جریان اجرا را
 * قطع می‌کنند. آن مسیر در ۱٫۹٫۱۲ دست‌نخورده مانده است.
 */

define( 'HOUR_IN_SECONDS', 3600 );
define( 'ABSPATH', '/tmp/fakewp/' );

$GLOBALS['T'] = array();

function get_transient( $k ) { return $GLOBALS['T'][ $k ] ?? false; }
function set_transient( $k, $v, $e = 0 ) { $GLOBALS['T'][ $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['T'][ $k ] ); return true; }
function wp_salt( $s = '' ) { return 'test-salt-' . $s; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function wp_unslash( $s ) { return $s; }
function __( $s, $d = '' ) { return $s; }
function esc_html__( $s, $d = '' ) { return $s; }
function esc_attr__( $s, $d = '' ) { return $s; }
function esc_html( $s ) { return $s; }
function esc_attr( $s ) { return $s; }
function add_action() {}
function add_shortcode() {}
function apply_filters( $t, $v ) { return $v; }
function do_action() {}
function wp_verify_nonce() { return true; }
function is_user_logged_in() { return false; }
function get_current_user_id() { return 0; }
function wp_json_encode( $v ) { return json_encode( $v ); }
function plugin_dir_path( $f ) { return dirname( $f ) . '/'; }

class WP_User {}
class ToPersia_SMS_Provider {}

$_SERVER['REMOTE_ADDR'] = '203.0.113.9';

require dirname( __DIR__ ) . '/includes/class-topersia-auth.php';

$auth = ( new ReflectionClass( 'ToPersia_Auth' ) )->newInstanceWithoutConstructor();
$rl   = new ReflectionMethod( 'ToPersia_Auth', 'rate_limited' );
$rl->setAccessible( true );
$rf = new ReflectionMethod( 'ToPersia_Auth', 'refund_rate_limit' );
$rf->setAccessible( true );

function call_rl( $auth, $rl, $mobile ) { return $rl->invoke( $auth, $mobile ); }

function mkey( $mobile ) { return 'tp_otp_rl_' . hash( 'sha256', $mobile . wp_salt( 'nonce' ) ); }

/** سردشدن ۴۵ ثانیه‌ای را با عقب‌بردن last رد می‌کنیم (بدون دست‌زدن به count). */
function skip_cooldown( $mobile ) {
	$k = mkey( $mobile );
	if ( isset( $GLOBALS['T'][ $k ] ) ) {
		$GLOBALS['T'][ $k ]['last'] = time() - 100;
	}
}

function reset_all() { $GLOBALS['T'] = array(); }

$pass = 0; $fail = 0;
function check( $label, $ok, $detail = '' ) {
	global $pass, $fail;
	if ( $ok ) { $pass++; echo "PASS  $label\n"; }
	else { $fail++; echo "FAIL  $label   $detail\n"; }
}

/* ── سناریو ۱: شش شمارۀ متفاوت از یک نشانی → از چهارم رد ── */
reset_all();
$results = array();
foreach ( array( '09120000001', '09120000002', '09120000003', '09120000004', '09120000005', '09120000006' ) as $m ) {
	$results[] = call_rl( $auth, $rl, $m );
}
check( 'س۱ سه شمارۀ اول مجاز', '' === $results[0] && '' === $results[1] && '' === $results[2] );
check( 'س۱ شمارۀ چهارم تا ششم رد', '' !== $results[3] && '' !== $results[4] && '' !== $results[5],
	json_encode( $results, JSON_UNESCAPED_UNICODE ) );

/* ── سناریو ۲: دو درخواست پشت‌سرهم یک شماره → دومی با ثانیه‌شمار ── */
reset_all();
$a = call_rl( $auth, $rl, '09121111111' );
$b = call_rl( $auth, $rl, '09121111111' );
check( 'س۲ اولی مجاز', '' === $a );
check( 'س۲ دومی رد با ثانیه‌شمار', '' !== $b && false !== strpos( $b, 'ثانیه' ), $b );

/* ── سناریو ۳: هفت درخواست یک شماره با عبور از کول‌داون → از ششم رد ── */
reset_all();
$seq = array();
for ( $i = 0; $i < 7; $i++ ) {
	$seq[] = call_rl( $auth, $rl, '09122222222' );
	skip_cooldown( '09122222222' );
}
$allowed = 0;
foreach ( $seq as $r ) {
	if ( '' === $r ) { $allowed++; }
}
check( 'س۳ دقیقاً ۵ درخواست مجاز', 5 === $allowed, "allowed=$allowed" );
check( 'س۳ ششم و هفتم رد', '' !== $seq[5] && '' !== $seq[6] );

/* ── سناریو ۴: سقف کل سایت پر → همه رد ── */
reset_all();
$GLOBALS['T']['tp_otp_global'] = array( 'count' => 120, 'start' => time() );
$r = call_rl( $auth, $rl, '09123333333' );
check( 'س۴ سقف سایت همه را رد می‌کند', '' !== $r, $r );
check( 'س۴ شمارندۀ شماره مصرف نشد', ! isset( $GLOBALS['T'][ mkey( '09123333333' ) ] ) );

/* ── سناریو ۷ (جدید ۱٫۹٫۱۲): خطای درگاه → سهمیه پس، ولی کول‌داون پابرجا ── */
reset_all();
$m     = '09124444444';
$first = call_rl( $auth, $rl, $m );
check( 'س۷ درخواست اول مجاز', '' === $first );
check( 'س۷ پیش از پس‌دادن count=1', 1 === (int) $GLOBALS['T'][ mkey( $m ) ]['count'] );
$last_before = (int) $GLOBALS['T'][ mkey( $m ) ]['last'];

$rf->invoke( $auth, $m );

check( 'س۷ count شماره صفر شد', 0 === (int) $GLOBALS['T'][ mkey( $m ) ]['count'] );
check( 'س۷ last دست‌نخورده ماند', $last_before === (int) $GLOBALS['T'][ mkey( $m ) ]['last'] );
check( 'س۷ count کل سایت صفر شد', 0 === (int) $GLOBALS['T']['tp_otp_global']['count'] );
$ip_key = 'tp_otp_ip_' . hash_hmac( 'sha256', '203.0.113.9', wp_salt( 'auth' ) );
check( 'س۷ count نشانی صفر شد', 0 === (int) $GLOBALS['T'][ $ip_key ]['count'] );
check( 'س۷ فهرست mobiles پاک نشد', 1 === count( $GLOBALS['T'][ $ip_key ]['mobiles'] ) );

$second = call_rl( $auth, $rl, $m );
check( 'س۷ درخواست فوری بعدی همچنان با ثانیه‌شمار رد', '' !== $second && false !== strpos( $second, 'ثانیه' ), $second );

/* حالت مرزی: پس‌دادن بی‌مصرفِ قبلی نباید count را منفی کند */
reset_all();
$rf->invoke( $auth, '09125555555' );
check( 'مرزی: پس‌دادن بی‌مصرف قبلی بی‌اثر است', ! isset( $GLOBALS['T'][ mkey( '09125555555' ) ] ) );

echo "\n--- $pass موفق، $fail ناموفق ---\n";
exit( $fail > 0 ? 1 : 0 );
