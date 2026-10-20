<?php
/**
 * ورود ایمیلی روی وردپرس واقعی — user-core ۱٫۱۴٫۰ · P5-D391 (فقط topersia.local)
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/local-email.php
 *
 * حساب‌های موقت خودش را می‌سازد و در پایان، حتی با خطا، پاک می‌کند. هیچ ایمیلی نمی‌رود: هر فراخوانی wp_mail و
 * phpmailer_init در طول آزمون شمرده می‌شود و باید صفر بماند (ثابت‌های SMTP محلی تعریف نشده‌اند).
 * ادعاها: ثبت‌نام و ورود با ایمیل · پیوند ایمیل به حساب پیامکی · صدورکننده ایمیل واقعی و زمان تأیید را دارد، ساختگی را نه ·
 * پاک‌کننده ترنزینت‌های ایمیل را پاک می‌کند · بازنشانی گذرواژه برای مشترک خاموش · نام عمومی بی ایمیل ·
 * فرم ورود صفحهٔ واقعی fa ← موبایل، en ← ایمیل، بی نانس در HTML مهمان.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$GLOBALS['le_t']    = array( 0, 0 );
$GLOBALS['le_mail'] = 0;
$GLOBALS['le_made'] = array();
function le_t( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['le_t'][0]++; echo "  ✅ $label\n"; } else { $GLOBALS['le_t'][1]++; echo "  ❌ $label   ← $note\n"; } }
add_filter( 'pre_wp_mail', function ( $s ) { $GLOBALS['le_mail']++; return false; }, 1 );   /* اگر چیزی به wp_mail برسد، همین‌جا می‌ایستد و شمرده می‌شود */
add_action( 'phpmailer_init', function () { $GLOBALS['le_mail']++; }, 1 );
$code_of = function ( $m ) { return preg_match( '/<span dir="ltr"[^>]*>(\d{6})<\/span>/', (string) ( $m['html'] ?? '' ), $x ) ? $x[1] : ''; };
$cool    = function ( $e ) { $k = 'tp_ec_rl_' . ToPersia_Email_Code::key_of( strtolower( $e ) ); $v = get_transient( $k ); if ( is_array( $v ) ) { $v['last'] = 0; set_transient( $k, $v, DAY_IN_SECONDS ); } };   /* فاصلهٔ ۶۰ ثانیه برای تکرار عمدی */
$uniq    = 'tpe' . substr( md5( uniqid( '', true ) ), 0, 8 );
$mk_mail = function ( $s ) use ( $uniq ) { return $uniq . '-' . $s . '@example.org'; };

echo "ورود ایمیلی روی وردپرس واقعی — P5-D391\n";

try {
	le_t( 'ثابت‌های SMTP محلی تعریف نشده‌اند (ارسال واقعی خاموش)؛ نسخهٔ افزونه 1.14.1', ! ToPersia_Mailer::configured() && '1.14.1' === TOPERSIA_USER_CORE_VERSION );

	/* ── ثبت‌نام = ورود ── */
	$e1 = $mk_mail( 'new' );
	$r  = ToPersia_Email_Code::request( $e1, 'login', 'en', 0 );
	$v  = ToPersia_Email_Code::check( $r['body']['token'], $e1, $code_of( $r['mail'] ), 0 );
	$u  = $v['user'] instanceof WP_User ? get_userdata( $v['user']->ID ) : null;
	if ( $u ) { $GLOBALS['le_made'][] = $u->ID; }
	le_t( 'ثبت‌نام با ایمیل: حساب مشترک تازه، ایمیل در user_email، نام ورود و نامک تصادفی، نام نمایشی خنثی، زمان تأیید',
		200 === $r['status'] && 200 === $v['status'] && $u && in_array( 'subscriber', $u->roles, true ) && $e1 === $u->user_email
		&& 0 === strpos( $u->user_login, 'tp_' ) && false === strpos( $u->user_login . $u->user_nicename . $u->display_name, $uniq ) && ToPersia_Privacy::is_neutral( $u->display_name )
		&& (int) get_user_meta( $u->ID, '_topersia_email_verified', true ) > 0, wp_json_encode( array( $r['status'], $v['status'] ) ) );

	$cool( $e1 );
	$r2 = ToPersia_Email_Code::request( strtoupper( $e1 ), 'login', 'fa', 0 );
	$v2 = ToPersia_Email_Code::check( $r2['body']['token'], $e1, $code_of( $r2['mail'] ), 0 );
	le_t( 'ورود دوباره با همان ایمیل (حروف بزرگ) ← همان حساب، نه حساب دوم', 200 === $v2['status'] && $u && $v2['user']->ID === $u->ID );

	$wrong = ToPersia_Email_Code::request( $mk_mail( 'wrong' ), 'login', 'fa', 0 );
	$w     = ToPersia_Email_Code::check( $wrong['body']['token'], $mk_mail( 'wrong' ), '999999' === $code_of( $wrong['mail'] ) ? '000000' : '999999', 0 );
	le_t( 'کد غلط ۴۲۲ و هیچ حسابی ساخته نشد', 422 === $w['status'] && ! get_user_by( 'email', $mk_mail( 'wrong' ) ) );

	/* ── پیوند ایمیل به حساب پیامکی ── */
	$sid = wp_insert_user( array( 'user_login' => ToPersia_Privacy::random_login(), 'user_pass' => wp_generate_password( 32 ), 'user_email' => ToPersia_Privacy::random_email(), 'role' => 'subscriber' ) );
	$GLOBALS['le_made'][] = $sid;
	update_user_meta( $sid, '_topersia_mobile', '0912' . substr( (string) random_int( 1000000, 9999999 ), 0, 7 ) );
	$e2 = $mk_mail( 'link' );
	wp_set_current_user( $sid );
	$rl = ToPersia_Email_Code::request( $e2, 'link', 'fa', $sid );
	$vl = ToPersia_Email_Code::check( $rl['body']['token'], $e2, $code_of( $rl['mail'] ), $sid );
	clean_user_cache( $sid );
	$su = get_userdata( $sid );
	le_t( 'پیوند ایمیل به حساب پیامکی: user_email عوض، زمان تأیید، موبایل همان؛ هیچ نامهٔ «ایمیل عوض شد» وردپرس',
		200 === $vl['status'] && $e2 === $su->user_email && (int) get_user_meta( $sid, '_topersia_email_verified', true ) > 0 && '' !== get_user_meta( $sid, '_topersia_mobile', true ) );
	$soon = ToPersia_Email_Code::request( $mk_mail( 'soon' ), 'link', 'fa', $sid );
	le_t( 'تغییر دوبارهٔ ایمیل زودتر از ۳۰ روز ← ۴۲۹', 429 === $soon['status'] );
	update_user_meta( $sid, '_topersia_email_verified', time() - 31 * DAY_IN_SECONDS );   /* برای آزمون برخورد */
	$cool( $e1 );
	$rc = ToPersia_Email_Code::request( $e1, 'link', 'fa', $sid );
	$vc = ToPersia_Email_Code::check( $rc['body']['token'], $e1, '000000', $sid );
	le_t( 'پیوند به ایمیلِ حساب دیگر: همان پاسخ ۲۰۰، نامهٔ «کسی خواست وصل کند» بی کد، و هر کدی رد — حساب دیگر دست نخورد',
		200 === $rc['status'] && 'conflict' === $rc['mail']['kind'] && '' === $code_of( $rc['mail'] ) && 422 === $vc['status'] && $e1 === get_userdata( $u->ID )->user_email );
	wp_set_current_user( 0 );

	/* ── صدورکننده و پاک‌کننده ── */
	$ex   = ToPersia_Data_Requests::instance()->export( $e2, 1 );
	$flat = wp_json_encode( $ex['data'], JSON_UNESCAPED_UNICODE );
	le_t( 'صدورکننده: ایمیل واقعی و زمان تأیید ایمیل را دارد', false !== strpos( $flat, '"email","value":"' . $e2 . '"' ) && false !== strpos( $flat, 'email_verified' ), $flat );
	$tid = wp_insert_user( array( 'user_login' => ToPersia_Privacy::random_login(), 'user_pass' => wp_generate_password( 32 ), 'user_email' => ToPersia_Privacy::random_email(), 'role' => 'subscriber' ) );
	$GLOBALS['le_made'][] = $tid;
	$ex2 = wp_json_encode( ToPersia_Data_Requests::instance()->export( get_userdata( $tid )->user_email, 1 )['data'], JSON_UNESCAPED_UNICODE );
	le_t( 'صدورکننده: ایمیل ساختگی حساب پیامکی را نمی‌آورد', false === strpos( $ex2, '@users.topersia.com' ), $ex2 );

	$e3 = $mk_mail( 'erase' );
	$re = ToPersia_Email_Code::request( $e3, 'login', 'fa', 0 );
	$ve = ToPersia_Email_Code::check( $re['body']['token'], $e3, $code_of( $re['mail'] ), 0 );
	$eid = $ve['user'] ? $ve['user']->ID : 0;
	$GLOBALS['le_made'][] = $eid;
	$k   = ToPersia_Email_Code::key_of( $e3 );
	$GLOBALS['T_before'] = array( (bool) get_transient( 'tp_ec_rl_' . $k ) );
	$cool( $e3 );
	ToPersia_Email_Code::request( $e3, 'login', 'fa', 0 );   /* سهمیه و کد جاری برای همین نشانی */
	$had = (bool) get_transient( 'tp_ec_rl_' . $k ) && (bool) get_transient( 'tp_ec_latest_' . $k );
	$er  = ToPersia_Data_Requests::instance()->erase( $e3, 1 );
	le_t( 'پاک‌کننده: ترنزینت‌های کانال ایمیل (سهمیه، کد جاری) و حساب پاک شد',
		$had && ! get_transient( 'tp_ec_rl_' . $k ) && ! get_transient( 'tp_ec_latest_' . $k ) && ! empty( $er['items_removed'] ) && ! get_userdata( $eid ) );

	/* ── بازنشانی گذرواژه و نام عمومی ── */
	le_t( 'بازنشانی گذرواژه: مشترک خاموش (allow_password_reset)؛ مدیر دست نمی‌خورد',
		false === apply_filters( 'allow_password_reset', true, $u->ID ) && true === apply_filters( 'allow_password_reset', true, (int) ( get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0] ?? 0 ) ) );
	le_t( 'نام عمومی: get_the_author_display_name با ایمیل ← نام خنثی',
		ToPersia_Privacy::is_neutral( (string) apply_filters( 'get_the_author_display_name', 'Sara ' . $e1, $u->ID, $u->ID ) ) );

	/* ── صفحهٔ واقعی مهمان ── */
	$get = function ( $url ) { $c = curl_init( $url ); curl_setopt_array( $c, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_PROXY => '' ) ); $h = (string) curl_exec( $c ); curl_close( $c ); return $h; };
	$fa = $get( home_url( '/province/isfahan/' ) );
	$en = $get( home_url( '/en/province/isfahan/' ) );
	le_t( 'صفحهٔ واقعی مهمان: پنجرهٔ ورود دو زبانه دارد؛ fa ← موبایل، en ← ایمیل؛ ورودی ایمیل dir=ltr؛ بی نانس کد ما در HTML',
		false !== strpos( $fa, 'data-tp-default-method="mobile"' ) && false !== strpos( $en, 'data-tp-default-method="email"' ) && false !== strpos( $fa, 'id="tp-auth-tab-email"' )
		&& false !== strpos( $fa, 'id="tp-email" type="email" dir="ltr"' ) && false !== strpos( $fa, 'ToPersiaUserCore = {"ajaxUrl":"' ) && (bool) preg_match( '/ToPersiaUserCore = \{"ajaxUrl":"[^"]*","nonce":""/', $fa ), strlen( $fa ) . '/' . strlen( $en ) );   /* نانس المنتور پرو مال ما نیست */
	le_t( 'صفحهٔ واقعی: ایمیل حساب آزمون در HTML نیست', false === strpos( $fa . $en, $uniq ) );

	le_t( 'در طول آزمون هیچ wp_mail یا phpmailer_init (هیچ نامه‌ای از این ماشین نرفت)', 0 === $GLOBALS['le_mail'] && ToPersia_Mailer::flush() >= 0 && 0 === $GLOBALS['le_mail'], (string) $GLOBALS['le_mail'] );
} finally {
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( array_filter( array_unique( $GLOBALS['le_made'] ) ) as $id ) {
		if ( get_userdata( $id ) && ! user_can( $id, 'edit_posts' ) ) { wp_delete_user( $id ); }
	}
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_tp\\_ec\\_%' OR option_name LIKE '\\_transient\\_timeout\\_tp\\_ec\\_%' OR option_name LIKE '\\_transient\\_tp\\_mail\\_cap\\_%' OR option_name LIKE '\\_transient\\_timeout\\_tp\\_mail\\_cap\\_%'" );
}
le_t( 'پاک‌سازی: هیچ حساب آزمون نماند', 0 === count( get_users( array( 'search' => '*' . $uniq . '*', 'search_columns' => array( 'user_email' ), 'fields' => 'ID' ) ) ) );
$g = $GLOBALS['le_t'];
echo "\n  گذشته: {$g[0]}   افتاده: {$g[1]}\n";
if ( $g[1] ) { exit( 1 ); }
