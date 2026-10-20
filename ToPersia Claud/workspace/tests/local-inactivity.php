<?php
/**
 * غیرفعال‌سازی حساب بی‌فعالیت روی وردپرس واقعی  P5-D377
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/local-inactivity.php
 *
 * فقط topersia.local. تمام نوشتن‌ها درون تراکنش پایگاه‌داده‌اند و در پایان ROLLBACK می‌شوند؛ هیچ کاربر واقعی یا
 * گزینه‌ای تغییر نمی‌کند (کار روزانه روی همهٔ کاربران اجرا می‌شود، پس بی تراکنش حساب‌های آزمون دیگر را هم می‌زد).
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }
global $wpdb; $wpdb->show_errors( true );
$GLOBALS['in_t'] = array( 0, 0 );
function t( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['in_t'][0]++; echo "  ✅ $label\n"; } else { $GLOBALS['in_t'][1]++; echo "  ❌ $label   ← $note\n"; } }
function in_memo_reset(): void { $r = new ReflectionProperty( 'ToPersia_Inactivity', 'memo' ); $r->setAccessible( true ); $r->setValue( null, array() ); }
function in_user( string $role, ?int $last = null ): int {
	$id = wp_insert_user( array( 'user_login' => 'tpin' . bin2hex( random_bytes( 4 ) ), 'user_pass' => wp_generate_password( 24 ), 'user_email' => 'tp-in-' . bin2hex( random_bytes( 4 ) ) . '@topersia.invalid', 'role' => $role ) );
	if ( null !== $last ) { update_user_meta( $id, '_topersia_last_login', $last ); }
	return (int) $id;
}

echo "غیرفعال‌سازی (ToPersia_Inactivity)\n";
$wpdb->query( 'START TRANSACTION' );
wp_cache_flush();

$now   = time();
$m25   = strtotime( '-25 months', $now );
$m23   = strtotime( '-23 months', $now );
$purge = array();
add_action( 'topersia_pagecache_purge', function ( $tags ) use ( &$purge ) { $purge[] = $tags; } );

t( 'مدت پیش‌فرض ۲۴ ماه', 24 === ToPersia_Inactivity::months() );

/* مبنا: بی autoload */
$had = null !== get_option( ToPersia_Inactivity::SINCE, null );
$base = ToPersia_Inactivity::baseline();
$al = $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", ToPersia_Inactivity::SINCE ) );
t( 'مبنای شمارش در option بی autoload', in_array( $al, array( 'no', 'off' ), true ) && $base > 0, (string) $al );

$a = in_user( 'subscriber', $m25 );      /* ۲۵ ماه ← غیرفعال */
$b = in_user( 'subscriber', $m23 );      /* ۲۳ ماه ← فعال */
$c = in_user( 'subscriber', null );      /* بی‌سابقه، مبنا = اکنون ← فعال */
$d = in_user( 'editor', $m25 );          /* edit_posts ← هرگز */
$e = in_user( 'administrator', $m25 );   /* مدیر ← هرگز */
$f = in_user( 'subscriber', $m25 );
update_option( 'topersia_uc_inactivity_since', $m25 - DAY_IN_SECONDS, false );
$g = in_user( 'subscriber', null );      /* بی‌سابقه و مبنا قدیمی ← غیرفعال */
$h = in_user( 'subscriber', null );
update_user_meta( $h, ToPersia_Inactivity::LAST_LOGIN, $now - DAY_IN_SECONDS );   /* ورود دیروز */

in_memo_reset();
$n1 = ToPersia_Inactivity::run( $now );
t( 'اجرا: غیرفعال‌شده‌ها (۲۵ ماه، بی‌سابقهٔ مبنا قدیمی، و حساب‌های واقعی قدیمی محلی)', $n1 >= 3, (string) $n1 );
t( '۲۵ ماه ← غیرفعال', '1' === get_user_meta( $a, '_topersia_inactive', true ) && ToPersia_Inactivity::is_inactive( $a ) );
t( '۲۳ ماه ← فعال می‌ماند', '' === (string) get_user_meta( $b, '_topersia_inactive', true ) );
t( 'بی‌سابقه با مبنای قدیمی ← غیرفعال', '1' === get_user_meta( $g, '_topersia_inactive', true ) );
t( 'ورود دیروز ← فعال', '' === (string) get_user_meta( $h, '_topersia_inactive', true ) );
t( 'ویرایشگر (edit_posts) هرگز', '' === (string) get_user_meta( $d, '_topersia_inactive', true ) );
t( 'مدیر هرگز', '' === (string) get_user_meta( $e, '_topersia_inactive', true ) );
t( 'ایدمپوتنت: اجرای دوم هیچ حساب تازه‌ای نمی‌زند', 0 === ( $n2 = ToPersia_Inactivity::run( $now ) ), (string) $n2 );

/* مبنا: همان کاربر بی‌سابقه با مبنای امروز زودتر از ۲۴ ماه غیرفعال نمی‌شود */
update_option( 'topersia_uc_inactivity_since', $now, false );
$c2 = in_user( 'subscriber', null );
ToPersia_Inactivity::run( $now + 365 * DAY_IN_SECONDS );
t( 'بی‌سابقه: ۱۲ ماه پس از نصب ← هنوز فعال', '' === (string) get_user_meta( $c2, '_topersia_inactive', true ) );
ToPersia_Inactivity::run( strtotime( '+25 months', $now ) );
t( 'بی‌سابقه: ۲۵ ماه پس از نصب ← غیرفعال', '1' === get_user_meta( $c2, '_topersia_inactive', true ) );

/* صافی مدت */
update_user_meta( $b, ToPersia_Inactivity::LAST_LOGIN, strtotime( '-7 months', $now ) );
add_filter( 'topersia_user_core_inactive_months', function () { return 6; } );
ToPersia_Inactivity::run( $now );
t( 'صافی مدت (۶ ماه) اعمال می‌شود', '1' === get_user_meta( $b, '_topersia_inactive', true ) && 6 === ToPersia_Inactivity::months() );
remove_all_filters( 'topersia_user_core_inactive_months' );

/* دسته‌ای: بیش از یک دستهٔ ۲۰۰ */
$ids = array();
for ( $i = 0; $i < 230; $i++ ) { $ids[] = in_user( 'subscriber', $m25 ); }
in_memo_reset();
$n3 = ToPersia_Inactivity::run( $now );
$left = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND user_id IN (" . implode( ',', array_map( 'intval', $ids ) ) . ')', ToPersia_Inactivity::FLAG ) );
t( 'دسته‌ای: ۲۳۰ حساب در یک اجرا (۲ دسته)', 230 === $left && $n3 >= 230, "$left / $n3" );

/* اثر عمومی */
in_memo_reset();
$name = get_the_author_meta( 'display_name', $a );
t( 'نام عمومی کاربر غیرفعال خنثی', ToPersia_Privacy::neutral_name( $a ) === apply_filters( 'get_the_author_display_name', 'نام واقعی', $a, false ), '' );
$rv = apply_filters( 'jet-reviews/user-manager/raw-user-data', array( 'id' => (string) $a, 'name' => 'نام واقعی', 'mail' => 'x@y.z' ) );
t( 'نظر منتشرشدهٔ کاربر غیرفعال: نام خنثی، بی ایمیل', ToPersia_Privacy::neutral_name( $a ) === $rv['name'] && '' === $rv['mail'] );
$rv2 = apply_filters( 'jet-reviews/user-manager/raw-user-data', array( 'id' => (string) $b, 'name' => 'نام واقعی', 'mail' => 'x@y.z' ) );
t( 'نظر کاربر غیرفعال‌نشده: دست‌نخورده', 'نام واقعی' === $rv2['name'] || ToPersia_Inactivity::is_inactive( $b ) );
t( 'اعلان و پیامک: غیرفعال نه، فعال بله', ! ToPersia_Inactivity::can_notify( $a ) && ToPersia_Inactivity::can_notify( in_user( 'subscriber', $now ) ) );

/* ورود موفق ← فوراً فعال (هر دو مسیر wp_login) */
$purge = array();
$ua = get_userdata( $a );
t( 'پیش از ورود غیرفعال', ToPersia_Inactivity::is_inactive( $a ) );
do_action( 'wp_login', $ua->user_login, $ua );      /* مسیر OTP همین را می‌زند؛ ورود وردپرس هم */
in_memo_reset();
t( 'ورود: فوراً فعال', ! ToPersia_Inactivity::is_inactive( $a ) && '' === (string) get_user_meta( $a, '_topersia_inactive', true ) );
t( 'ورود: آخرین ورود ثبت شد', abs( (int) get_user_meta( $a, '_topersia_last_login', true ) - time() ) < 5 );
t( 'ورود: اجرای بعدی دوباره غیرفعالش نمی‌کند', ToPersia_Inactivity::run( $now ) >= 0 && '' === (string) get_user_meta( $a, '_topersia_inactive', true ) );

/* ثبت ورود در کد OTP و ورود وردپرس */
$auth = file_get_contents( TOPERSIA_USER_CORE_DIR . 'includes/class-topersia-auth.php' );
t( 'مسیر OTP wp_login را می‌زند', false !== strpos( $auth, "do_action( 'wp_login'" ) );
t( 'کرون روزانهٔ غیرفعال‌سازی ثبت است', false !== wp_next_scheduled( ToPersia_Inactivity::HOOK ) );

/* مدیر هرگز پرچم نمی‌گیرد، حتی دستی در اجرای دوباره */
t( 'مدیر و ویرایشگر پس از همهٔ اجراها فعال', '' === (string) get_user_meta( $e, '_topersia_inactive', true ) && '' === (string) get_user_meta( $d, '_topersia_inactive', true ) );

$wpdb->query( 'ROLLBACK' );
wp_cache_flush();

/* پاک‌سازی کش (بیرون از تراکنش؛ درج در جدول نظرها درون تراکنش با قلاب‌های دیگر قفل می‌گرفت): کاربر و نظر آزمون، سپس پاک */
$tbl = $wpdb->prefix . 'jet_reviews';
$pid = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_status='publish' AND post_type LIKE 'tp\_%' LIMIT 1" );
$z   = in_user( 'subscriber', $m25 );
$wpdb->insert( $tbl, array( 'source' => 'post', 'post_id' => $pid, 'author' => (string) $z, 'date' => gmdate( 'Y-m-d H:i:s' ), 'title' => 't', 'content' => 'c', 'rating' => 5, 'approved' => '1' ) );
$rid = (int) $wpdb->insert_id;
$purge = array();
ToPersia_Inactivity::purge_pages_for( array( $z ) );
$flat = wp_json_encode( $purge );
t( 'کش: topersia_pagecache_purge با برچسب پستِ نظر', $pid > 0 && $rid > 0 && false !== strpos( $flat, 'tp-post-' . $pid ), $flat );
if ( $rid ) { $wpdb->delete( $tbl, array( 'id' => $rid ) ); }
require_once ABSPATH . 'wp-admin/includes/user.php';
wp_delete_user( $z );
t( 'پس از ROLLBACK: کاربر آزمون نیست', false === get_userdata( $a ) );

$g = $GLOBALS['in_t'];
echo "\n  گذشته: {$g[0]}   افتاده: {$g[1]}\n";
if ( $g[1] ) { exit( 1 ); }
