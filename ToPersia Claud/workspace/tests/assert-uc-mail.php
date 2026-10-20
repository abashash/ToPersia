<?php
/**
 * ورود ایمیلی و ارسال ایمیل — user-core ۱٫۱۴٫۰ · P5-D391 (دستهٔ ۲۱)
 *
 *   php tests/assert-uc-mail.php        (assert-uc.php هم صدایش می‌زند)
 *
 * بی وردپرس: stubهای کوچک همین پرونده. هر ادعای امنیتی جهش دارد — همان کد با یک شرط برداشته‌شده، زیر نام کلاس تازه،
 * باید بیفتد: هش کد · سقف‌ها · پاسخ یکسان · Origin/Sec-Fetch-Site · ارسال‌نشدن بی ثابت‌های SMTP.
 * هیچ ایمیل، DNS یا درخواست شبکه‌ای از این آزمون نمی‌رود (wp_mail و checkdnsrr ثبت می‌شوند، نه اجرا).
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MINUTE_IN_SECONDS', 60 );

$UC = getenv( 'TP_UC_DIR' ) ? rtrim( getenv( 'TP_UC_DIR' ), '/\\' ) . '/' : dirname( __DIR__ ) . '/plugin/topersia-user-core/';
define( 'TOPERSIA_USER_CORE_DIR', $UC );

/* ── stubs ─────────────────────────────────────────────────────────── */
class WP_Error { public $c; function __construct( $c = '' ) { $this->c = $c; } function get_error_code() { return $this->c; } function get_error_message() { return $this->c; } }
#[AllowDynamicProperties]
class WP_User {
	public $ID; public $user_email; public $user_login; public $display_name = ''; public $caps = array();
	function __construct( $id = 0 ) { $u = $GLOBALS['U'][ $id ] ?? null; if ( $u ) { foreach ( $u as $k => $v ) { $this->$k = $v; } } $this->ID = (int) $id; }
	function set_role( $r ) { $GLOBALS['U'][ $this->ID ]['role'] = $r; }
}
class TP_Json extends Exception { public $ok; public $data; public $status; function __construct( $ok, $data, $status ) { parent::__construct( 'json' ); $this->ok = $ok; $this->data = $data; $this->status = $status; } }

$GLOBALS['F'] = array(); $GLOBALS['T'] = array(); $GLOBALS['M'] = array(); $GLOBALS['U'] = array(); $GLOBALS['LOG'] = array(); $GLOBALS['WPMAIL'] = 0; $GLOBALS['SMS'] = array(); $GLOBALS['ME'] = 0;
function add_filter( $t, $cb, $p = 10, $a = 1 ) { $GLOBALS['F'][ $t ][] = $cb; return true; }
function add_action( $t, $cb, $p = 10, $a = 1 ) { return add_filter( $t, $cb, $p, $a ); }
function remove_filter( $t, $cb = null, $p = 10 ) { return true; }
function remove_action( $t, $cb = null, $p = 10 ) { return true; }
function apply_filters( $t, $v, ...$a ) { foreach ( $GLOBALS['F'][ $t ] ?? array() as $cb ) { if ( is_callable( $cb ) && 0 === strpos( $t, 'topersia_' ) ) { $v = $cb( $v, ...$a ); } } return $v; }
function do_action( ...$a ) {}
function add_shortcode() {}
function register_rest_route() {}
function get_transient( $k ) { return $GLOBALS['T'][ $k ] ?? false; }
function set_transient( $k, $v, $t = 0 ) { $GLOBALS['T'][ $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['T'][ $k ] ); return true; }
function get_option( $k, $d = false ) { return $d; }
function update_option() { return true; }
function wp_salt( $s = 'auth' ) { return 'salt-' . $s . '-TEST'; }
function wp_generate_password( $n = 12, $s = true, $x = false ) { $a = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; $o = ''; for ( $i = 0; $i < $n; $i++ ) { $o .= $a[ random_int( 0, 61 ) ]; } return $o; }
function wp_check_password( $p, $h ) { return false; }
function get_user_by( $f, $v ) { foreach ( $GLOBALS['U'] as $id => $u ) { if ( 'email' === $f && strtolower( $u['user_email'] ) === strtolower( $v ) ) { return new WP_User( $id ); } } return false; }
function get_userdata( $id ) { return isset( $GLOBALS['U'][ $id ] ) ? new WP_User( $id ) : false; }
function get_users( $q ) { foreach ( $GLOBALS['U'] as $id => $u ) { if ( ( $GLOBALS['M'][ $id ][ $q['meta_key'] ] ?? null ) === $q['meta_value'] ) { return array( new WP_User( $id ) ); } } return array(); }
function user_can( $u, $cap ) { return in_array( $cap, (array) ( $GLOBALS['U'][ $u->ID ]['caps'] ?? array() ), true ); }
function get_user_meta( $u, $k, $s = true ) { return $GLOBALS['M'][ $u ][ $k ] ?? ''; }
function update_user_meta( $u, $k, $v ) { $GLOBALS['M'][ $u ][ $k ] = $v; return true; }
function delete_user_meta( $u, $k ) { unset( $GLOBALS['M'][ $u ][ $k ] ); return true; }
function wp_create_user( $l, $p, $e ) { $id = count( $GLOBALS['U'] ) + 100; $GLOBALS['U'][ $id ] = array( 'user_login' => $l, 'user_email' => $e, 'caps' => array() ); return $id; }
function wp_update_user( $d ) { foreach ( $d as $k => $v ) { if ( 'ID' !== $k ) { $GLOBALS['U'][ $d['ID'] ][ $k ] = $v; } } return $d['ID']; }
function username_exists( $l ) { return false; }
function email_exists( $e ) { return (bool) get_user_by( 'email', $e ); }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function is_email( $e ) { return false !== filter_var( $e, FILTER_VALIDATE_EMAIL ); }
function sanitize_email( $e ) { return is_email( $e ) ? (string) $e : ''; }
function sanitize_key( $k ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $k ) ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function wp_unslash( $v ) { return $v; }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $v ) { return esc_html( $v ); }
function esc_url_raw( $v ) { return (string) $v; }
function home_url( $p = '/' ) { return 'https://topersia.com' . $p; }
function site_url( $p = '/' ) { return 'https://topersia.com/geo' . $p; }
function get_current_user_id() { return (int) $GLOBALS['ME']; }
function wp_get_current_user() { return new WP_User( (int) $GLOBALS['ME'] ); }
function wp_verify_nonce( $n, $a ) { return 'good-nonce' === $n; }
function get_locale() { return 'fa_IR'; }
function determine_locale() { return $GLOBALS['LOCALE'] ?? 'fa_IR'; }
function is_ssl() { return true; }
function wp_set_current_user( $id ) { $GLOBALS['ME'] = $id; }
function wp_set_auth_cookie() {}
function wp_validate_redirect( $u, $f ) { return $f; }
function wp_send_json_success( $d = null, $s = 200 ) { throw new TP_Json( true, $d, $s ); }
function wp_send_json_error( $d = null, $s = 400 ) { throw new TP_Json( false, $d, $s ); }
function wp_mail( ...$a ) { $GLOBALS['WPMAIL']++; return true; }
function topersia_log( $m, $l = 'info', $c = '', $ctx = array() ) { $GLOBALS['LOG'][] = $m . ' ' . json_encode( $ctx, JSON_UNESCAPED_UNICODE ); }
function register_shutdown_function_tp() {}

class ToPersia_DB {
	public static function mobile_hash( $m ) { return substr( hash_hmac( 'sha256', 'tp-otp-mobile|' . $m, wp_salt( 'auth' ) ), 0, 32 ); }
	public static function create_attempt( ...$a ) { return 7; }
	public static function increment_attempt( $id ) {}
	public static function mark_verified( $id ) {}
}
class ToPersia_Mediana_SMS { public function send_otp( $to, $otp, $p ) { $GLOBALS['SMS'][] = array( $to, $otp ); return array( 'success' => true, 'message' => '', 'request_id' => 'r1' ); } }
class ToPersia_Admin { public static function account_url() { return 'https://topersia.com/account/'; } }

require $UC . 'includes/class-topersia-i18n.php';
require $UC . 'includes/class-topersia-privacy.php';
require $UC . 'includes/class-topersia-auth.php';
require $UC . 'includes/class-topersia-mailer.php';
require $UC . 'includes/class-topersia-email-code.php';

$pass = 0; $fail = 0;
function t( $label, $ok, $note = '' ) { global $pass, $fail; if ( $ok ) { $pass++; echo "  ✅ $label\n"; } else { $fail++; echo "  ❌ $label   $note\n"; } }
$src = function ( $rel ) use ( $UC ) { return (string) file_get_contents( $UC . $rel ); };

/* کلاس با یک جهش متنی زیر نام تازه؛ '' اگر جهش جا نیفتاد */
$mutant = function ( $rel, $class, $suffix, $from, $to ) use ( $src ) {
	$code = $src( $rel );
	if ( false === strpos( $code, $from ) ) { return ''; }
	$name = $class . '_' . $suffix;
	$code = str_replace( 'final class ' . $class . ' ', 'final class ' . $name . ' ', str_replace( $from, $to, $code ) );
	// phpcs:ignore Squiz.PHP.Eval -- جهش آزمون؛ فقط همین پروندهٔ مخزن
	eval( '?>' . $code );
	return class_exists( $name ) ? $name : '';
};
$reset = function () { $GLOBALS['T'] = array(); $GLOBALS['LOG'] = array(); $GLOBALS['WPMAIL'] = 0; ToPersia_Mailer::$outbox = array(); $_SERVER = array( 'REMOTE_ADDR' => '203.0.113.' . random_int( 1, 250 ) ); };
$code_of = function ( $mail ) { return preg_match( '/<span dir="ltr"[^>]*>(\d{6})<\/span>/', (string) $mail['html'], $m ) ? $m[1] : ''; };

/* حساب‌ها: ۱ مشترک با ایمیل واقعی · ۲ ویرایشگر · ۳ مشترک پیامکی (ایمیل ساختگی) */
$GLOBALS['U'] = array(
	1 => array( 'user_login' => 'tp_aaaa', 'user_email' => 'sub@example.org', 'caps' => array() ),
	2 => array( 'user_login' => 'editor', 'user_email' => 'ed@example.org', 'caps' => array( 'edit_posts' ) ),
	3 => array( 'user_login' => 'tp_bbbb', 'user_email' => 'uabcdefghijklmnop@users.topersia.com', 'caps' => array() ),
);
$GLOBALS['M'][3]['_topersia_mobile'] = '09120000003';

echo "\nورود ایمیلی — user-core ۱٫۱۴٫۰ · P5-D391\n" . str_repeat( '─', 78 ) . "\n";

/* ── ۱. ارسال نشدن بی ثابت‌ها ─────────────────────────────────────── */
$reset();
$m   = ToPersia_Email_Code::compose( 'code', 'fa', 'new@example.org', '012345', 'login' );
$okS = ToPersia_Mailer::send( $m );
$log = implode( "\n", $GLOBALS['LOG'] );
t( 'بی ثابت‌های SMTP: configured() نه، wp_mail هرگز صدا زده نمی‌شود، نامه فقط در صندوق آزمون (CLI)',
	! ToPersia_Mailer::configured() && $okS && 0 === $GLOBALS['WPMAIL'] && 1 === count( ToPersia_Mailer::$outbox ) );
t( 'لاگ نامهٔ ثبت‌شده: فقط HMAC گیرنده و هش متن — نه نشانی، نه دامنه، نه کد',
	false !== strpos( $log, ToPersia_Mailer::hmac( 'new@example.org' ) ) && false === strpos( $log, '@' ) && false === strpos( $log, 'example.org' ) && false === strpos( $log, '012345' ), $log );
$M1 = $mutant( 'includes/class-topersia-mailer.php', 'ToPersia_Mailer', 'M1', "		if ( ! self::configured() ) {\n			self::capture( \$m, \$to );\n			return true;\n		}", '' );
$reset();
$caught = false;
if ( $M1 ) { try { $M1::send( $m ); } catch ( Throwable $e ) { $caught = true; } }
t( 'جهش: بی دروازهٔ «ثابت تعریف نشده» ارسال به wp_mail می‌رسد (یا می‌شکند) — آزمون می‌گیرد', '' !== $M1 && ( $GLOBALS['WPMAIL'] > 0 || $caught ) );
t( 'پیکربندی ناقص هم ارسال نیست: هر شش ثابت لازم است و گذرواژه فقط از ثابت (هیچ get_option)',
	6 === count( ToPersia_Mailer::REQUIRED ) && false === strpos( $src( 'includes/class-topersia-mailer.php' ), 'get_option' )
	&& false !== strpos( $src( 'includes/class-topersia-mailer.php' ), "\$phpmailer->Password   = (string) TOPERSIA_SMTP_PASS;" ) );
t( 'phpmailer_init فقط در طول ارسال ToPersia: افزوده و بلافاصله برداشته می‌شود',
	1 === substr_count( $src( 'includes/class-topersia-mailer.php' ), "add_action( 'phpmailer_init', \$hook, 999 );" ) && 1 === substr_count( $src( 'includes/class-topersia-mailer.php' ), "remove_action( 'phpmailer_init', \$hook, 999 );" ) );
t( 'نامه به دامنهٔ ساختگی (از هر افزونه) دور ریخته می‌شود؛ گیرندهٔ عادی دست نمی‌خورد',
	false === ToPersia_Mailer::drop_synthetic( null, array( 'to' => 'ToPersia <uabc@users.topersia.com>' ) ) && null === ToPersia_Mailer::drop_synthetic( null, array( 'to' => 'a@example.org' ) ) );
t( 'بازنشانی گذرواژه: مشترک نه، ویرایشگر بله',
	false === ToPersia_Mailer::allow_password_reset( true, 1 ) && true === ToPersia_Mailer::allow_password_reset( true, 2 ) );

/* ── ۲. سقف سایت (۵۰/ساعت · ۲۰۰/روز تا سقف واقعی) ─────────────────── */
t( 'سقف سایت پیش‌فرض ۵۰ در ساعت و ۲۰۰ در روز؛ صافی فقط سخت‌تر می‌کند',
	array( 'hour' => 50, 'day' => 200 ) === ToPersia_Mailer::caps() );
$reset();
$n = 0; for ( $i = 0; $i < 55; $i++ ) { $n += ToPersia_Mailer::reserve() ? 1 : 0; }
t( 'سقف سایت: از ۵۵ درخواست فقط ۵۰ سهم می‌گیرد', 50 === $n, (string) $n );
$M2 = $mutant( 'includes/class-topersia-mailer.php', 'ToPersia_Mailer', 'M2', "if ( (int) \$h['count'] >= \$caps['hour'] || (int) \$d['count'] >= \$caps['day'] ) {", 'if ( false ) {' );
$reset(); $n = 0; for ( $i = 0; $i < 55; $i++ ) { $n += $M2::reserve() ? 1 : 0; }
t( 'جهش: بی شرط سقف همهٔ ۵۵ می‌گذرند — آزمون می‌گیرد', '' !== $M2 && 55 === $n );
$reset();
$GLOBALS['T']['tp_mail_cap_h'] = array( 'count' => 50, 'start' => time() );
$r = ToPersia_Email_Code::request( 'someone@example.org', 'login', 'fa', 0 );
t( 'سقف سایت پر ← ۴۲۹ «بعداً دوباره تلاش کنید»، بی نامه و بی افشای وضعیت حساب',
	429 === $r['status'] && null === $r['mail'] && ToPersia_I18n::in( 'mail_later', 'fa' ) === $r['body']['message'] );

/* ── ۳. سقف‌های کانال ─────────────────────────────────────────────── */
$reset();
$st = array();
for ( $i = 0; $i < 7; $i++ ) { $r = ToPersia_Email_Code::request( 'cap@example.org', 'login', 'fa', 0 ); $st[] = $r['status']; $k = 'tp_ec_rl_' . ToPersia_Email_Code::key_of( 'cap@example.org' ); $GLOBALS['T'][ $k ]['last'] = 0; }
t( 'هر نشانی ۵ در ساعت: ششمی و هفتمی ۴۲۹', array( 200, 200, 200, 200, 200, 429, 429 ) === $st, implode( ',', $st ) );
$reset();
$a = ToPersia_Email_Code::request( 'cool@example.org', 'login', 'fa', 0 );
$b = ToPersia_Email_Code::request( 'cool@example.org', 'login', 'fa', 0 );
t( 'فاصلهٔ ۶۰ ثانیه برای یک نشانی', 200 === $a['status'] && 429 === $b['status'] && ToPersia_I18n::in( 'email_wait', 'fa' ) === $b['body']['message'] );
$reset();
$st = array();
for ( $i = 0; $i < 7; $i++ ) { $r = ToPersia_Email_Code::request( "rot$i@example.org", 'login', 'fa', 0 ); $st[] = $r['status']; }
t( 'یک IP حداکثر ۵ نشانی متمایز در ساعت (چرخاندن نشانی)', array( 200, 200, 200, 200, 200, 429, 429 ) === $st, implode( ',', $st ) );
$M3 = $mutant( 'includes/class-topersia-email-code.php', 'ToPersia_Email_Code', 'M3', "if ( (int) \$em['h']['count'] >= self::PER_EMAIL_H", 'if ( false && (int) $em[\'h\'][\'count\'] >= self::PER_EMAIL_H' );
$reset(); $st = array();
for ( $i = 0; $i < 7; $i++ ) { $r = $M3::request( 'cap@example.org', 'login', 'fa', 0 ); $st[] = $r['status']; $GLOBALS['T'][ 'tp_ec_rl_' . ToPersia_Email_Code::key_of( 'cap@example.org' ) ]['last'] = 0; }
t( 'جهش: بی سقف هر نشانی هر هفت می‌گذرند — آزمون می‌گیرد', '' !== $M3 && ! in_array( 429, $st, true ) );
$reset();
unset( $GLOBALS['F'] );
$GLOBALS['F'] = array();
t( 'شکست‌بسته: کلید ترنزینت از HMAC نشانی (۴۰ هگز)، نشانی خام هرگز در نام کلید',
	(bool) preg_match( '/^[0-9a-f]{40}$/', ToPersia_Email_Code::key_of( 'x@example.org' ) ) );

/* ── ۴. هش کد، انقضا، حدس، یک‌بارمصرف ─────────────────────────────── */
$reset();
$r     = ToPersia_Email_Code::request( 'New.User@Example.org', 'login', 'en', 0 );
$tok   = $r['body']['token'];
$code  = $code_of( $r['mail'] );
$store = json_encode( $GLOBALS['T'] );
t( 'کد شش رقمی در نامه؛ در ترنزینت فقط HMAC (۶۴ هگز) با توکن — کد خام هیچ‌جا ذخیره نشد',
	6 === strlen( $code ) && false === strpos( $store, '"' . $code . '"' ) && false === strpos( $store, ':' . $code )
	&& ToPersia_Auth::code_hash( $tok, $code ) === $GLOBALS['T'][ 'tp_ec_' . $tok ]['hash'] && (bool) preg_match( '/^[0-9a-f]{64}$/', $GLOBALS['T'][ 'tp_ec_' . $tok ]['hash'] ) );
$M4 = $mutant( 'includes/class-topersia-email-code.php', 'ToPersia_Email_Code', 'M4', "'code' === \$kind ? ToPersia_Auth::code_hash( \$token, \$code ) : ToPersia_Auth::dummy_hash()", "'code' === \$kind ? \$code : ToPersia_Auth::dummy_hash()" );
$reset(); $r4 = $M4::request( 'm4@example.org', 'login', 'fa', 0 );
t( 'جهش: ذخیرهٔ کد خام — ادعای «فقط HMAC» می‌افتد', '' !== $M4 && $GLOBALS['T'][ 'tp_ec_' . $r4['body']['token'] ]['hash'] === $code_of( $r4['mail'] ) );
$M5 = $mutant( 'includes/class-topersia-auth.php', 'ToPersia_Auth', 'M5', "return '' !== \$stored && hash_equals( \$stored, self::code_hash( \$token, \$code ) );", 'return true;' );
t( 'مقایسه ثابت‌زمان با hash_equals؛ کد غلط رد — جهش: «همیشه درست» کد غلط را می‌پذیرد',
	ToPersia_Auth::code_matches( ToPersia_Auth::code_hash( 'T', '123456' ), 'T', '123456' ) && ! ToPersia_Auth::code_matches( ToPersia_Auth::code_hash( 'T', '123456' ), 'T', '123457' )
	&& ! ToPersia_Auth::code_matches( ToPersia_Auth::code_hash( 'T', '123456' ), 'U', '123456' ) && '' !== $M5 && $M5::code_matches( 'x', 'T', '000000' ) );
$reset();
$r    = ToPersia_Email_Code::request( 'flow@example.org', 'login', 'fa', 0 );
$tok  = $r['body']['token']; $code = $code_of( $r['mail'] );
$bad  = (string) ( ( (int) $code + 1 ) % 1000000 ); $bad = str_pad( $bad, 6, '0', STR_PAD_LEFT );
$w    = ToPersia_Email_Code::check( $tok, 'flow@example.org', $bad, 0 );
$other = ToPersia_Email_Code::check( $tok, 'other@example.org', $code, 0 );
$ok   = ToPersia_Email_Code::check( $tok, 'FLOW@example.org', $code, 0 );
$again = ToPersia_Email_Code::check( $tok, 'flow@example.org', $code, 0 );
t( 'کد غلط ۴۲۲ با شمار باقی؛ نشانی دیگر با همان توکن ۴۲۲؛ کد درست ← حساب تازهٔ مشترک؛ دوباره ← ۴۱۰ (یک‌بارمصرف)',
	422 === $w['status'] && 4 === $w['body']['left'] && 422 === $other['status'] && 200 === $ok['status'] && $ok['user'] instanceof WP_User
	&& 'subscriber' === ( $GLOBALS['U'][ $ok['user']->ID ]['role'] ?? '' ) && 410 === $again['status'] );
$nu = $GLOBALS['U'][ $ok['user']->ID ];
t( 'حساب ایمیلی: ایمیل فقط در user_email؛ نام ورود و نامک تصادفی، نام نمایشی خنثی — ایمیل هرگز در نام عمومی (P5-D354)',
	'flow@example.org' === $nu['user_email'] && 0 === strpos( $nu['user_login'], 'tp_' ) && false === strpos( $nu['user_login'] . $nu['user_nicename'] . $nu['display_name'], 'flow' )
	&& ToPersia_Privacy::is_neutral( $nu['display_name'] ) && $GLOBALS['M'][ $ok['user']->ID ]['_topersia_email_verified'] > 0 );
t( 'نام عمومی: رشتهٔ دارای ایمیل ← نام خنثی (public_name)؛ جهش در privacy می‌افتد',
	ToPersia_Privacy::is_neutral( ToPersia_Privacy::public_name( 'Ali a.b@example.org', 5, 'en' ) ) && 'Ali' === ToPersia_Privacy::public_name( 'Ali', 5, 'en' )
	&& ( function () use ( $mutant ) { $c = $mutant( 'includes/class-topersia-privacy.php', 'ToPersia_Privacy', 'M6', '|| self::looks_like_email( $name ) ', '' ); return '' !== $c && 'x@example.org' === $c::public_name( 'x@example.org', 5, 'en' ); } )() );
$reset();
$r = ToPersia_Email_Code::request( 'tries@example.org', 'login', 'fa', 0 ); $tok = $r['body']['token']; $code = $code_of( $r['mail'] );
for ( $i = 0; $i < 5; $i++ ) { ToPersia_Email_Code::check( $tok, 'tries@example.org', '999999' === $code ? '000000' : '999999', 0 ); }
$six = ToPersia_Email_Code::check( $tok, 'tries@example.org', $code, 0 );
t( 'پنج حدس در همان ترنزینت؛ ششمی حتی با کد درست ۴۲۹ و کد حذف', 429 === $six['status'] && false === get_transient( 'tp_ec_' . $tok ) );
$reset();
$r1 = ToPersia_Email_Code::request( 'twice@example.org', 'login', 'fa', 0 );
$GLOBALS['T'][ 'tp_ec_rl_' . ToPersia_Email_Code::key_of( 'twice@example.org' ) ]['last'] = 0;
$r2 = ToPersia_Email_Code::request( 'twice@example.org', 'login', 'fa', 0 );
$old = ToPersia_Email_Code::check( $r1['body']['token'], 'twice@example.org', $code_of( $r1['mail'] ), 0 );
t( 'کد تازه کد قبلی همان نشانی را باطل می‌کند (۴۱۰)', 410 === $old['status'] );
$reset();
$r = ToPersia_Email_Code::request( 'exp@example.org', 'login', 'fa', 0 ); $tok = $r['body']['token'];
unset( $GLOBALS['T'][ 'tp_ec_' . $tok ] );
t( 'منقضی (ترنزینت ده‌دقیقه‌ای رفته) ← ۴۱۰؛ عمر ۶۰۰ ثانیه', 410 === ToPersia_Email_Code::check( $tok, 'exp@example.org', $code_of( $r['mail'] ), 0 )['status'] && 600 === ToPersia_Email_Code::TTL );

/* ── ۵. پاسخ یکسان ────────────────────────────────────────────────── */
$shape = function ( $r ) { return array( $r['status'], array_keys( $r['body'] ), $r['body']['message'] ?? '', $r['body']['expiresIn'] ?? 0, strlen( $r['body']['token'] ?? '' ) ); };
$reset(); $none = ToPersia_Email_Code::request( 'nobody@example.org', 'login', 'fa', 0 );
$reset(); $sub  = ToPersia_Email_Code::request( 'sub@example.org', 'login', 'fa', 0 );
$reset(); $ed   = ToPersia_Email_Code::request( 'ed@example.org', 'login', 'fa', 0 );
$ed_any = ToPersia_Email_Code::check( $ed['body']['token'], 'ed@example.org', '000000', 0 )['status'];
$GLOBALS['ME'] = 3;
$reset(); $conf = ToPersia_Email_Code::request( 'sub@example.org', 'link', 'fa', 3 );
$GLOBALS['ME'] = 0;
t( 'پاسخ یکسان: نشانی بی حساب ≡ حساب مشترک ≡ حساب ویرایشگر ≡ پیوند به نشانیِ حساب دیگر (وضعیت، کلیدها، متن، عمر، طول توکن)',
	$shape( $none ) === $shape( $sub ) && $shape( $sub ) === $shape( $ed ) && $shape( $ed ) === $shape( $conf ) && 200 === $none['status'],
	json_encode( array( $shape( $none ), $shape( $ed ), $shape( $conf ) ), JSON_UNESCAPED_UNICODE ) );
t( 'ویرایشگر: نامهٔ «ورود عادی» بی کد؛ پیوندِ برخوردی: نامهٔ «کسی خواست وصل کند» بی کد؛ هر کدی برای هر دو رد',
	'privileged' === $ed['mail']['kind'] && '' === $code_of( $ed['mail'] ) && 'conflict' === $conf['mail']['kind'] && '' === $code_of( $conf['mail'] )
	&& 422 === $ed_any && 422 === ToPersia_Email_Code::check( $conf['body']['token'], 'sub@example.org', '000000', 3 )['status'] );
$M7 = $mutant( 'includes/class-topersia-email-code.php', 'ToPersia_Email_Code', 'M7', "			\$kind = 'privileged';", "			return \$err( 403, 'use_normal_login', 'x' );" );
$reset(); $ed7 = $M7::request( 'ed@example.org', 'login', 'fa', 0 );
t( 'جهش: ۴۰۳ برای حساب دارای دسترسی (رفتار پیامک تا ۱٫۱۳) — ادعای پاسخ یکسان می‌افتد', '' !== $M7 && $shape( $ed7 ) !== $shape( $none ) );
$reset();
t( 'قالب نامعتبر ۴۲۲ (تنها تفاوت مجاز)؛ دامنهٔ ساختگی حساب‌ها و دامنهٔ یک‌بارمصرف هم ۴۲۲ با همان متن',
	422 === ToPersia_Email_Code::request( 'not-an-email', 'login', 'fa', 0 )['status']
	&& 422 === ToPersia_Email_Code::request( 'uxyz@users.topersia.com', 'login', 'fa', 0 )['status']
	&& 422 === ToPersia_Email_Code::request( 'a@mailinator.com', 'login', 'fa', 0 )['status'] );

/* پیامک: همان پاسخ برای شمارهٔ حساب دارای دسترسی (P5-D391 بند ۳؛ تا ۱٫۱۳ ۴۰۳) */
$sms = function ( $class, $mobile ) {
	$GLOBALS['T'] = array(); $GLOBALS['SMS'] = array();
	$_POST = array( 'nonce' => 'good-nonce', 'mobile' => $mobile ); $_SERVER = array( 'REMOTE_ADDR' => '198.51.100.' . random_int( 1, 250 ) );
	try { $class::instance()->send_otp(); } catch ( TP_Json $j ) { return array( $j->ok, $j->status, array_keys( (array) $j->data ), $j->data['message'] ?? '', count( $GLOBALS['SMS'] ) ); }
	return array();
};
$GLOBALS['U'][4] = array( 'user_login' => 'ed2', 'user_email' => 'ed2@example.org', 'caps' => array( 'edit_posts' ) );
$GLOBALS['M'][4]['_topersia_mobile'] = '09120000004';
$s_new = $sms( 'ToPersia_Auth', '09129999999' );
$s_ed  = $sms( 'ToPersia_Auth', '09120000004' );
t( 'پیامک: شمارهٔ حساب دارای دسترسی همان پاسخ ۲۰۰ «کد فرستاده شد» — ولی هیچ پیامکی نمی‌رود',
	array( $s_new[0], $s_new[1], $s_new[2], $s_new[3] ) === array( $s_ed[0], $s_ed[1], $s_ed[2], $s_ed[3] ) && true === $s_ed[0] && 1 === $s_new[4] && 0 === $s_ed[4],
	json_encode( array( $s_new, $s_ed ), JSON_UNESCAPED_UNICODE ) );
$M8 = $mutant( 'includes/class-topersia-auth.php', 'ToPersia_Auth', 'M8', "		if ( \$silent ) {\n			\$this->log(", "		if ( \$silent ) {\n			wp_send_json_error( array( 'message' => 'x' ), 403 );\n			\$this->log(" );
$s_m8 = '' !== $M8 ? $sms( $M8, '09120000004' ) : array();
t( 'جهش پیامک: ۴۰۳ برای حساب دارای دسترسی — آزمون می‌گیرد', '' !== $M8 && $s_m8 && false === $s_m8[0] );
t( 'پیامک: کد با random_int و صفر پیشین، هش HMAC (نه wp_hash_password)',
	false !== strpos( $src( 'includes/class-topersia-auth.php' ), '$hash = self::code_hash( $token, $otp );' ) && false === strpos( $src( 'includes/class-topersia-auth.php' ), 'wp_hash_password( $otp )' )
	&& false === strpos( $src( 'includes/class-topersia-auth.php' ), 'wp_rand( 100000, 999999 )' ) );

/* ── ۶. Origin و Sec-Fetch-Site ──────────────────────────────────── */
$orig = function ( $class, $h ) { $_SERVER = $h; return $class::origin_ok(); };
$M9 = $mutant( 'includes/class-topersia-auth.php', 'ToPersia_Auth', 'M9', "		if ( '' !== \$sfs && 'same-origin' !== \$sfs ) {\n			return false;\n		}", '' );
$M10 = $mutant( 'includes/class-topersia-auth.php', 'ToPersia_Auth', 'M10', "		foreach ( array_unique( array( home_url( '/' ), function_exists( 'site_url' ) ? site_url( '/' ) : home_url( '/' ) ) ) as \$base ) {", "		return true;\n		foreach ( array() as \$base ) {" );
$cases = array(
	array( array(), true ),
	array( array( 'HTTP_ORIGIN' => 'https://topersia.com' ), true ),
	array( array( 'HTTP_ORIGIN' => 'https://topersia.com:443', 'HTTP_SEC_FETCH_SITE' => 'same-origin' ), true ),
	array( array( 'HTTP_ORIGIN' => 'https://evil.example' ), false ),
	array( array( 'HTTP_ORIGIN' => 'http://topersia.com' ), false ),
	array( array( 'HTTP_ORIGIN' => 'https://topersia.com.evil.example' ), false ),
	array( array( 'HTTP_ORIGIN' => 'null' ), false ),
	array( array( 'HTTP_SEC_FETCH_SITE' => 'cross-site' ), false ),
	array( array( 'HTTP_SEC_FETCH_SITE' => 'same-site' ), false ),
	array( array( 'HTTP_SEC_FETCH_SITE' => 'same-origin' ), true ),
);
$bad = array(); foreach ( $cases as $i => $c ) { if ( $orig( 'ToPersia_Auth', $c[0] ) !== $c[1] ) { $bad[] = $i; } }
t( 'Origin/Sec-Fetch-Site: همین میزبان بله؛ بیگانه، طرح دیگر، زیردامنهٔ جعلی، null، cross-site و same-site نه؛ بی سرآیند ← فقط نانس', ! $bad, implode( ',', $bad ) );
t( 'جهش: بی بررسی Sec-Fetch-Site درخواست cross-site می‌گذرد؛ بی تطبیق میزبان Origin بیگانه می‌گذرد — آزمون هر دو را می‌گیرد',
	'' !== $M9 && true === $orig( $M9, array( 'HTTP_SEC_FETCH_SITE' => 'cross-site' ) ) && '' !== $M10 && true === $orig( $M10, array( 'HTTP_ORIGIN' => 'https://evil.example' ) ) );
$_POST = array( 'nonce' => 'good-nonce', 'email' => 'sub@example.org' ); $_SERVER = array( 'HTTP_ORIGIN' => 'https://evil.example', 'REMOTE_ADDR' => '1.2.3.4' );
$st_e = null; try { ToPersia_Email_Code::instance()->ajax_send(); } catch ( TP_Json $j ) { $st_e = $j->status; }
$_POST = array( 'nonce' => 'good-nonce', 'mobile' => '09129999999' );
$st_s = null; try { ToPersia_Auth::instance()->send_otp(); } catch ( TP_Json $j ) { $st_s = $j->status; }
$_POST = array( 'nonce' => 'good-nonce', 'token' => str_repeat( 'a', 32 ), 'otp' => '123456', 'mobile' => '09129999999' );
$st_v = null; try { ToPersia_Auth::instance()->verify_otp(); } catch ( TP_Json $j ) { $st_v = $j->status; }
$_POST = array( 'nonce' => 'good-nonce', 'token' => str_repeat( 'a', 32 ), 'otp' => '123456', 'email' => 'sub@example.org' );
$st_ev = null; try { ToPersia_Email_Code::instance()->ajax_verify(); } catch ( TP_Json $j ) { $st_ev = $j->status; }
t( 'Origin بیگانه روی send و verify هر دو کانال ← ۴۰۳ (با نانس درست)', array( 403, 403, 403, 403 ) === array( $st_e, $st_s, $st_v, $st_ev ), json_encode( array( $st_e, $st_s, $st_v, $st_ev ) ) );

/* ── ۷. پیوند شناسه و ۳۰ روز ─────────────────────────────────────── */
$reset();
$GLOBALS['ME'] = 3;
$r = ToPersia_Email_Code::request( 'mine@example.org', 'link', 'fa', 3 );
$v = ToPersia_Email_Code::check( $r['body']['token'], 'mine@example.org', $code_of( $r['mail'] ), 3 );
t( 'پیوند ایمیل به حساب پیامکی: کد به نشانی تازه، user_email عوض و زمان تأیید ثبت؛ موبایل همان می‌ماند',
	200 === $v['status'] && 'mine@example.org' === $GLOBALS['U'][3]['user_email'] && $GLOBALS['M'][3]['_topersia_email_verified'] > 0 && '09120000003' === $GLOBALS['M'][3]['_topersia_mobile'] );
$reset();
$c30 = ToPersia_Email_Code::request( 'again@example.org', 'link', 'fa', 3 );
$GLOBALS['M'][3]['_topersia_email_verified'] = time() - 31 * DAY_IN_SECONDS;
$reset();
$c31 = ToPersia_Email_Code::request( 'again@example.org', 'link', 'fa', 3 );
$v31 = ToPersia_Email_Code::check( $c31['body']['token'], 'again@example.org', $code_of( $c31['mail'] ), 3 );
ToPersia_Mailer::flush();
$last = ToPersia_Mailer::$outbox ? end( ToPersia_Mailer::$outbox ) : array( 'kind' => '', 'to' => '' );
t( 'فاصلهٔ ۳۰ روز: تغییر دوباره زودتر ← ۴۲۹؛ پس از ۳۱ روز ← ۲۰۰ و اعلان امنیتی «ایمیل عوض شد» به نشانی پیشین',
	429 === $c30['status'] && 200 === $c31['status'] && 200 === $v31['status'] && 'changed' === $last['kind'] && 'mine@example.org' === $last['to'] && 'again@example.org' === $GLOBALS['U'][3]['user_email'] );
$GLOBALS['ME'] = 0;
$reset();
t( 'پیوند بی ورود ← ۴۰۱؛ ویرایشگر پیوند نمی‌دهد ← ۴۰۳', 401 === ToPersia_Email_Code::request( 'z@example.org', 'link', 'fa', 0 )['status'] && 403 === ToPersia_Email_Code::request( 'z@example.org', 'link', 'fa', 2 )['status'] );

/* ── ۸. قالب نامه ─────────────────────────────────────────────────── */
$bad = array();
foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
	foreach ( array( 'code', 'privileged', 'conflict', 'changed', 'mobile_changed' ) as $k ) {
		$mm = ToPersia_Email_Code::compose( $k, $l, 'x@example.org', 'code' === $k ? '004321' : '', 'login' );
		$want = 'en' === $l ? 'ltr' : 'rtl';
		if ( preg_match( '/<img|<a\s|href=|https?:\/\/|src=|url\(/i', $mm['html'] . $mm['text'] ) || false === strpos( $mm['html'], 'dir="' . $want . '"' )
			|| false !== strpos( $mm['subject'], '004321' ) || ( 'code' === $k && ( false === strpos( $mm['text'], '004321' ) || false === strpos( $mm['html'], '<span dir="ltr"' ) ) )
			|| ( 'code' !== $k && preg_match( '/\d{6}/', $mm['text'] ) ) || '' === trim( $mm['subject'] ) || false !== strpos( $mm['html'] . $mm['text'], 'x@example.org' ) ) {
			$bad[] = "$l/$k";
		}
	}
}
t( 'نامه fa · en · ar: بی تصویر، بی پیوند و بی نشانی http؛ fa و ar راست‌چین؛ موضوع بی کد؛ کد فقط در نامهٔ کد (رقم لاتین، dir=ltr)؛ نشانی گیرنده در متن نیست', ! $bad, implode( ' ', $bad ) );

/* ── ۹. واژه‌نامه ─────────────────────────────────────────────────── */
$dict = preg_match( '/private array \$strings = \[(.*?)\n    \];/su', $src( 'includes/class-topersia-i18n.php' ), $dm ) ? eval( 'return [' . $dm[1] . '];' ) : array();
$used = array();
foreach ( array( 'includes/class-topersia-email-code.php', 'includes/class-topersia-auth.php', 'templates/login.php' ) as $f ) {
	preg_match_all( '/(?:msg\( \'|\$tp_t\(\'|\$tp\(\')([a-z_]+)\'/', $src( $f ), $mm );
	$used = array_merge( $used, $mm[1] );
}
preg_match_all( "/'(mail_[a-z_]+|identity_[a-z_]+|email_[a-z_]+|login_tab_[a-z]+)'/", $src( 'includes/class-topersia-email-code.php' ) . $src( 'templates/dashboard.php' ) . $src( 'templates/login.php' ), $mm );
$used = array_diff( array_unique( array_merge( $used, $mm[1] ) ), array( 'email_h' ) );   /* email_h فیلد ترنزینت است، نه کلید واژه‌نامه */
$miss = array_filter( $used, function ( $k ) use ( $dict ) { return empty( $dict[ $k ]['fa'] ) || empty( $dict[ $k ]['en'] ) || empty( $dict[ $k ]['ar'] ); } );
$arfa = array_filter( array_keys( $dict ), function ( $k ) use ( $dict ) { return preg_match( '/[\x{06CC}\x{06A9}\x{067E}\x{0686}\x{0698}\x{06AF}]/u', (string) ( $dict[ $k ]['ar'] ?? '' ) ); } );
t( 'واژه‌نامه: هر کلید تازه fa · en · ar دارد؛ عربی بی حرف ویژهٔ فارسی (ی ک پ چ ژ گ)', count( $used ) > 40 && ! $miss && ! $arfa, implode( ',', $miss ) . ' | ' . implode( ',', $arfa ) );

/* ── ۱۰. لاگ کانال ایمیل ──────────────────────────────────────────── */
$reset();
$r = ToPersia_Email_Code::request( 'logme@example.org', 'login', 'fa', 0 );
ToPersia_Email_Code::check( $r['body']['token'], 'logme@example.org', $code_of( $r['mail'] ), 0 );
$log = implode( "\n", $GLOBALS['LOG'] );
t( 'لاگ مسیر کد ایمیلی: نه نشانی، نه دامنه، نه کد، نه توکن', false === strpos( $log, 'logme' ) && false === strpos( $log, 'example.org' ) && false === strpos( $log, $code_of( $r['mail'] ) ) && false === strpos( $log, $r['body']['token'] ), $log );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
