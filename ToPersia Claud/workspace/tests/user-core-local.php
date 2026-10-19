<?php
/**
 * user-core روی وردپرس واقعی — OTP هش‌شده، درخواست حذف/صدور، صدورکننده و پاک‌کننده  P5-D375
 *
 *   wp eval-file tests/user-core-local.php
 *
 * فقط topersia.local. SMS واقعی نمی‌رود (فیلتر pre_http_request پاسخ ساختگی مدیانا می‌دهد و کد را از بدنهٔ همان درخواست
 * برمی‌دارد). حساب آزمون با شمارهٔ ساختگی ساخته و در پایان با خودِ پاک‌کننده پاک می‌شود؛ ردیف نظر و درخواست آزمون هم
 * برداشته می‌شود. هیچ دادهٔ واقعی لمس نمی‌شود.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }
if ( ! defined( 'DOING_AJAX' ) ) { define( 'DOING_AJAX', true ); }
global $wpdb;
$GLOBALS['uc_t'] = array( 0, 0 );
function t( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['uc_t'][0]++; echo "  ✅ $label\n"; } else { $GLOBALS['uc_t'][1]++; echo "  ❌ $label   ← $note\n"; } }

/* فراخوانی AJAX وردپرس بی مردن */
class UC_Done extends Exception {}
set_error_handler( function ( $no, $str ) { return false !== strpos( $str, 'headers already sent' ); } );   /* کوکی ورود در CLI */
add_filter( 'wp_die_ajax_handler', function () { return function () { throw new UC_Done(); }; } );
function uc_call( callable $fn, array $post ): array {
	$_POST = $post; $_REQUEST = $post;
	ob_start();
	try { $fn(); } catch ( UC_Done $e ) { /* wp_send_json */ }
	$raw = ob_get_clean();
	$j = json_decode( $raw, true );
	return is_array( $j ) ? $j : array( 'success' => false, 'raw' => $raw );
}

$mobile = '09000000017';                       /* ساختگی */
$other  = '09000000018';
$sent   = array();
add_filter( 'pre_option_topersia_mediana_api_key', function () { return 'test-key'; } );
add_filter( 'pre_option_topersia_mediana_pattern_code', function () { return 'p'; } );
add_filter( 'pre_http_request', function ( $pre, $args, $url ) use ( &$sent ) {
	if ( false === strpos( $url, 'mediana.ir' ) ) { return $pre; }
	$b = json_decode( (string) $args['body'], true ); $sent[] = $b;
	return array( 'headers' => array(), 'body' => wp_json_encode( array( 'data' => array( 'Succeed' => true, 'RequestId' => 'rq1' ) ) ), 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array(), 'filename' => null );
}, 10, 3 );

wp_set_current_user( 0 );
$auth = ToPersia_Auth::instance();
$nonce = wp_create_nonce( 'topersia_user_core' );
$tbl = ToPersia_DB::table_name();
$H = ToPersia_DB::mobile_hash( $mobile );

echo "\nOTP — شماره فقط HMAC\n" . str_repeat( '─', 60 ) . "\n";
$r = uc_call( array( $auth, 'send_otp' ), array( 'nonce' => $nonce, 'mobile' => $mobile ) );
t( 'ارسال کد موفق؛ پیامک مدیانا همان شمارهٔ ورودی را می‌گیرد', ! empty( $r['success'] ) && 1 === count( $sent ) && $mobile === $sent[0]['recipient'], json_encode( $r ) . json_encode( $sent ) );
$token = isset( $r['data']['token'] ) ? $r['data']['token'] : '';
$otp = isset( $sent[0]['otpCode'] ) ? (string) $sent[0]['otpCode'] : '';
$row = $wpdb->get_row( "SELECT * FROM {$tbl} ORDER BY id DESC LIMIT 1", ARRAY_A );
t( 'جدول OTP: ستون mobile هشِ ۳۲ نویسهٔ هگز است، نه شماره', $row && $row['mobile'] === $H && 1 === preg_match( '/^[0-9a-f]{32}$/', $row['mobile'] ), json_encode( $row ) );
t( 'هیچ ردیفی با شمارهٔ متن ساده در جدول نیست', 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl} WHERE mobile REGEXP '^09[0-9]{9}$' OR mobile = '{$mobile}'" ) );
t( 'شمارهٔ متن ساده در ترنزینت OTP هم نیست', false === strpos( wp_json_encode( get_transient( 'tp_otp_' . $token ) ), $mobile ) && false === strpos( wp_json_encode( get_transient( 'tp_otp_' . $token ) ), ltrim( $mobile, '0' ) ) );
t( 'latest_for_mobile با ورودی متن ساده ردیف هش‌شده را می‌یابد (ورودی برای تطبیق هش می‌شود)', null !== ToPersia_DB::latest_for_mobile( $mobile, 'login' ) && null === ToPersia_DB::latest_for_mobile( $H, 'login' ) );

$bad = uc_call( array( $auth, 'verify_otp' ), array( 'nonce' => $nonce, 'token' => $token, 'otp' => $otp, 'mobile' => $other ) );
t( 'تأیید با شمارهٔ دیگر رد می‌شود (تطبیق هش)', empty( $bad['success'] ), json_encode( $bad ) );
$none = uc_call( array( $auth, 'verify_otp' ), array( 'nonce' => $nonce, 'token' => $token, 'otp' => $otp ) );
t( 'تأیید بی شماره رد می‌شود', empty( $none['success'] ), json_encode( $none ) );
$ok = uc_call( array( $auth, 'verify_otp' ), array( 'nonce' => $nonce, 'token' => $token, 'otp' => $otp, 'mobile' => $mobile, 'redirect' => '' ) );
t( 'ورود با کد و شمارهٔ درست موفق است', ! empty( $ok['success'] ), json_encode( $ok ) );
$users = get_users( array( 'meta_key' => '_topersia_mobile', 'meta_value' => $mobile, 'number' => 1 ) );
$user = $users ? $users[0] : null;
t( 'حساب ساخته شد (شمارهٔ حساب در متای کاربر؛ نام ورود و ایمیل تصادفی)', $user && false === strpos( $user->user_login, '9000000017' ) && ToPersia_Privacy::synthetic_email( $user->user_email ) );
if ( ! $user ) { echo "\nگذشته: {$GLOBALS['uc_t'][0]}   افتاده: " . ( $GLOBALS['uc_t'][1] + 1 ) . "\n"; exit( 1 ); }
update_user_meta( $user->ID, '_tp_test_account', '1' );
t( 'پس از ورود هنوز ردیف متن ساده نیست', 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl} WHERE mobile REGEXP '^09[0-9]{9}$'" ) );

echo "\nمهاجرت و پاک‌سازی\n" . str_repeat( '─', 60 ) . "\n";
$wpdb->insert( $tbl, array( 'mobile' => $other, 'purpose' => 'login', 'created_at' => gmdate( 'Y-m-d H:i:s' ), 'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 300 ), 'request_id' => 'legacy', 'attempts' => 0, 'verified' => 0, 'ip_hash' => '' ) );
delete_option( 'topersia_user_core_otp_hashed' );
ToPersia_DB::maybe_hash_otp_rows();
t( 'ردیف قدیمیِ متن ساده یک‌بار هش شد و پرچم نشست', ToPersia_DB::mobile_hash( $other ) === $wpdb->get_var( "SELECT mobile FROM {$tbl} WHERE request_id='legacy'" ) && '1' === get_option( 'topersia_user_core_otp_hashed' ) );
$wpdb->insert( $tbl, array( 'mobile' => $H, 'purpose' => 'login', 'created_at' => gmdate( 'Y-m-d H:i:s', time() - 3 * HOUR_IN_SECONDS ), 'expires_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ), 'request_id' => 'expired', 'attempts' => 0, 'verified' => 1, 'ip_hash' => '' ) );
ToPersia_DB::prune();
t( 'کرون روزانه: ردیف منقضی (حتی تأییدشده) پاک می‌شود، ردیف زنده می‌ماند', 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl} WHERE request_id='expired'" ) && 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl} WHERE request_id='legacy'" ) );
$wpdb->delete( $tbl, array( 'request_id' => 'legacy' ) );
ToPersia_DB::ensure_cron();
t( 'رویداد روزانهٔ topersia_user_core_prune برقرار است', false !== wp_next_scheduled( 'topersia_user_core_prune' ) );

echo "\nداده‌های کاربر\n" . str_repeat( '─', 60 ) . "\n";
$review_post = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type='tp_province' AND post_status='publish' LIMIT 1" );
update_user_meta( $user->ID, '_topersia_travel_style', 'culture' );
update_user_meta( $user->ID, '_topersia_country', 'IR' );
update_user_meta( $user->ID, '_topersia_display_prefs', array( 'mode' => 'dark' ) );
update_user_meta( $user->ID, '_topersia_ai_daily', array( 'd' => gmdate( 'Y-m-d' ), 'n' => 2 ) );
ToPersia_DB::saved_add( $user->ID, $review_post, 'tp_province' );
$rt = $wpdb->prefix . 'jet_reviews';
$wpdb->insert( $rt, array( 'source' => 'post', 'post_id' => $review_post, 'post_type' => 'tp_province', 'author' => (string) $user->ID, 'date' => gmdate( 'Y-m-d H:i:s' ), 'title' => 'آزمون حریم خصوصی', 'content' => 'متن آزمون که باید بماند', 'type_slug' => 'tp_province-review-type', 'rating_data' => 'a:0:{}', 'rating' => 80, 'likes' => 0, 'dislikes' => 0, 'approved' => '1', 'pinned' => '0' ) );
$rid = (int) $wpdb->insert_id;
$wpdb->insert( $wpdb->prefix . 'jet_review_reactions', array( 'review_uuid' => '00000000-0000-4000-8000-000000000017', 'user_id' => $user->ID, 'reaction' => 1, 'created_at' => gmdate( 'Y-m-d H:i:s' ), 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ) );
$wpdb->insert( $tbl, array( 'mobile' => $H, 'purpose' => 'login', 'created_at' => gmdate( 'Y-m-d H:i:s' ), 'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 300 ), 'request_id' => 'mine', 'attempts' => 0, 'verified' => 1, 'ip_hash' => '' ) );

echo "\nدرخواست از پنل — تأیید دومرحله‌ای\n" . str_repeat( '─', 60 ) . "\n";
wp_set_current_user( $user->ID );
$n2 = wp_create_nonce( 'topersia_user_core' );
$dr = ToPersia_Data_Requests::instance();
$bad = uc_call( array( $dr, 'ajax' ), array( 'nonce' => 'x', 'type' => 'erase', 'step' => 'start' ) );
t( 'بی نانس درست رد می‌شود', empty( $bad['success'] ) );
$s1 = uc_call( array( $dr, 'ajax' ), array( 'nonce' => $n2, 'type' => 'erase', 'step' => 'start' ) );
t( 'گام اول: توکن یک‌بارمصرف و متن تأیید برمی‌گردد، هنوز درخواستی نیست', ! empty( $s1['success'] ) && ! empty( $s1['data']['token'] ) && ! ToPersia_Data_Requests::active_request( $user, 'erase' ) );
$no = uc_call( array( $dr, 'ajax' ), array( 'nonce' => $n2, 'type' => 'erase', 'step' => 'confirm', 'token' => 'wrong' ) );
t( 'گام دوم با توکن نادرست رد می‌شود', empty( $no['success'] ) && ! ToPersia_Data_Requests::active_request( $user, 'erase' ) );
$s2 = uc_call( array( $dr, 'ajax' ), array( 'nonce' => $n2, 'type' => 'erase', 'step' => 'confirm', 'token' => $s1['data']['token'] ) );
t( 'گام دوم با توکن درست: درخواست حذف ثبت و در حالت «تأییدشده» (نوشتهٔ user_request هسته)', ! empty( $s2['success'] ) && ToPersia_Data_Requests::active_request( $user, 'erase' ) );
$dup = uc_call( array( $dr, 'ajax' ), array( 'nonce' => $n2, 'type' => 'erase', 'step' => 'start' ) );
t( 'درخواست دوباره تا اجرا ممکن نیست', empty( $dup['success'] ) );
$e1 = uc_call( array( $dr, 'ajax' ), array( 'nonce' => $n2, 'type' => 'export', 'step' => 'start' ) );
$e2 = uc_call( array( $dr, 'ajax' ), array( 'nonce' => $n2, 'type' => 'export', 'step' => 'confirm', 'token' => isset( $e1['data']['token'] ) ? $e1['data']['token'] : '' ) );
t( 'درخواست صدور داده هم ثبت می‌شود', ! empty( $e2['success'] ) && ToPersia_Data_Requests::active_request( $user, 'export' ) );
$admin_u = (int) $wpdb->get_var( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='{$wpdb->prefix}capabilities' AND meta_value LIKE '%administrator%' LIMIT 1" );
$ad = uc_call( array( $dr, 'ajax' ), array( 'nonce' => ( function () use ( $admin_u ) { wp_set_current_user( $admin_u ); return wp_create_nonce( 'topersia_user_core' ); } )(), 'type' => 'erase', 'step' => 'start' ) );
t( 'حساب مدیر از این مسیر حذف نمی‌شود', empty( $ad['success'] ) );
wp_set_current_user( 0 );

echo "\nصدورکننده\n" . str_repeat( '─', 60 ) . "\n";
$ex = $dr->export( $user->user_email, 1 );
$groups = array_unique( array_column( $ex['data'], 'group_id' ) );
t( 'صدور: نمایه · ذخیره‌ها · OTP · نظرها در خروجی', ! array_diff( array( 'topersia-profile', 'topersia-saved', 'topersia-otp', 'topersia-reviews' ), $groups ), implode( ',', $groups ) );
$flat = wp_json_encode( $ex['data'], JSON_UNESCAPED_UNICODE );
t( 'خروجی شمارهٔ خودِ کاربر و سبک سفر را دارد؛ هش OTP و IP هش‌شده را ندارد', false !== strpos( $flat, $mobile ) && false !== strpos( $flat, 'culture' ) && false === strpos( $flat, $H ) );
t( 'ایمیل ساختگی در خروجی نیست', false === strpos( $flat, $user->user_email ) );
t( 'ایمیلی که کاربر ندارد ← خروجی تهی', array() === $dr->export( 'nobody@invalid.test', 1 )['data'] );
$reg = apply_filters( 'wp_privacy_personal_data_exporters', array() );
t( 'صدورکننده و پاک‌کننده در فهرست هسته ثبت‌اند', isset( $reg['topersia-user-core'] ) && isset( apply_filters( 'wp_privacy_personal_data_erasers', array() )['topersia-user-core'] ) );

echo "\nپاک‌کننده — نظرها ناشناس، حساب پاک\n" . str_repeat( '─', 60 ) . "\n";
$guest_before = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_review_guests WHERE guest_id=%s", ToPersia_Data_Requests::ANON ) );
$er = $dr->erase( $user->user_email, 1 );
t( 'پاک‌کننده: items_removed و done', ! empty( $er['items_removed'] ) && ! empty( $er['done'] ), json_encode( $er ) );
t( 'حساب پاک شد', false === get_userdata( $user->ID ) );
$rv = $wpdb->get_row( $wpdb->prepare( "SELECT author, content FROM {$rt} WHERE id=%d", $rid ), ARRAY_A );
t( 'نظر: متن مانده، نویسنده ← tp-deleted (هیچ user_id)', $rv && 'tp-deleted' === $rv['author'] && 'متن آزمون که باید بماند' === $rv['content'], json_encode( $rv ) );
t( 'هیچ ردیف نظر با شناسهٔ کاربر نمانده؛ واکنش‌ها پاک', 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$rt} WHERE author=%s", (string) $user->ID ) ) && 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_review_reactions WHERE user_id=%d", $user->ID ) ) );
t( 'OTP، ذخیره‌ها و متاها پاک', 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tbl} WHERE mobile=%s", $H ) ) && 0 === count( ToPersia_DB::saved_rows( $user->ID ) ) && 0 === (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id=%d", $user->ID ) ) );
$guest = $wpdb->get_row( $wpdb->prepare( "SELECT name, mail, phone FROM {$wpdb->prefix}jet_review_guests WHERE guest_id=%s", ToPersia_Data_Requests::ANON ), ARRAY_A );
t( 'مهمان مشترک بی ایمیل و شماره (user_id · ایمیل · IP · شماره پاک)', $guest && '' === $guest['mail'] && '' === $guest['phone'] );
$names = array();
foreach ( array( 'fa', 'en', 'ar', 'tr', 'ru', 'de', 'fr', 'es', 'zh' ) as $lg ) { $names[ $lg ] = ToPersia_Data_Requests::deleted_label( $lg ); }
t( '«کاربر حذف‌شده» در نُه زبان، همه متفاوت و ناتهی', 9 === count( array_unique( $names ) ) && ! in_array( '', $names, true ), json_encode( $names, JSON_UNESCAPED_UNICODE ) );
$dd = apply_filters( 'jet-reviews/user-manager/raw-user-data', array( 'id' => 'tp-deleted', 'name' => 'x', 'mail' => 'a@b.c' ) );
t( 'خروجی JetReviews برای tp-deleted: نام برچسب، ایمیل خالی', '' === $dd['mail'] && '' !== $dd['name'] && 'x' !== $dd['name'] );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$rt} WHERE id=%d", $rid ) );
foreach ( get_posts( array( 'post_type' => 'user_request', 'post_status' => 'any', 'numberposts' => 20, 'title' => $user->user_email ) ) as $p ) { wp_delete_post( $p->ID, true ); }
if ( ! $guest_before ) { $wpdb->delete( $wpdb->prefix . 'jet_review_guests', array( 'guest_id' => ToPersia_Data_Requests::ANON ) ); }

echo "\nگذشته: {$GLOBALS['uc_t'][0]}   افتاده: {$GLOBALS['uc_t'][1]}\n";
exit( $GLOBALS['uc_t'][1] ? 1 : 0 );
