<?php
/**
 * آزمون ادعای topersia-user-core ۱٫۱۰٫۰ — ۴٫۳٫۰
 *
 *   php tests/assert-uc.php
 *
 * کلاس‌ها با همان stubهای آزمون رندر بار می‌شوند؛ هر چه به پایگاه‌داده یا
 * درخواست وابسته است، با جعلی کوچکِ همین فایل یا از روی متن منبع سنجیده
 * می‌شود.
 */

/* stubهای کلاس user-core (برای آزمون رندر) این‌جا کنار می‌روند — کلاس واقعی بار می‌شود. */
define( 'TP_UC_REAL', true );
require __DIR__ . '/stubs.php';

/* TP_UC_DIR فقط برای آزمون جهش: همان ادعاها روی نسخهٔ دست‌کاری‌شده باید بیفتند. */
$UC = getenv( 'TP_UC_DIR' ) ? rtrim( getenv( 'TP_UC_DIR' ), '/\\' ) . '/' : dirname( __DIR__ ) . '/plugin/topersia-user-core/';

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $t ) { return '2026-09-27 10:00:00'; }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $u ) { return (string) $u; }
}
if ( ! function_exists( 'wp_validate_redirect' ) ) {
	/* همان قاعدهٔ هستهٔ وردپرس به زبان ساده: فقط میزبان خودِ سایت. */
	function wp_validate_redirect( $url, $fallback = '' ) {
		$host = parse_url( (string) $url, PHP_URL_HOST );
		return ( ! $host || 'topersia.com' === $host ) ? $url : $fallback;
	}
}
if ( ! class_exists( 'ToPersia_Admin' ) ) {
	class ToPersia_Admin { public static function account_url() { return 'https://topersia.com/account/'; } }
}
if ( ! class_exists( 'ToPersia_I18n' ) ) {
	class ToPersia_I18n {
		public static function t( $k, $d = null ) { return $d ?? $k; }
		public static function current_language() { return 'fa'; }
	}
}

require $UC . 'includes/class-topersia-jetreviews-setup.php';
require $UC . 'includes/class-topersia-review-form.php';
require $UC . 'includes/class-topersia-follow.php';
require $UC . 'includes/class-topersia-auth.php';
require $UC . 'includes/class-topersia-jetengine.php';
require $UC . 'includes/class-topersia-reviews.php';

$pass = 0;
$fail = 0;

function t( $label, $ok, $note = '' ) {
	global $pass, $fail;
	if ( $ok ) { $pass ++; printf( "  ✅ %s\n", $label ); }
	else       { $fail ++; printf( "  ❌ %s   %s\n", $label, $note ); }
}

$src = function ( $rel ) use ( $UC ) { return (string) file_get_contents( $UC . $rel ); };

echo "\ntopersia-user-core ۱٫۱۰٫۰\n" . str_repeat( '─', 78 ) . "\n";

/* ── نسخه ── */
t( 'نسخه: سرآیند و ثابت 1.10.4',
	false !== strpos( $src( 'topersia-user-core.php' ), ' * Version: 1.10.4' )
	&& false !== strpos( $src( 'topersia-user-core.php' ), "define('TOPERSIA_USER_CORE_VERSION', '1.10.4');" ) );

/*
 * ── ۱٫۱۰٫۲ — جهت فرم ورود از زبان صفحه  SPRINT-4.4 ۲٫۰٫۸ ──
 * فرم در صفحۀ فارسی و عربی rtl و در انگلیسی ltr. جهش: همان قالبِ ۱٫۱۰٫۱ با
 * `dir="rtl"` ثابت — در صفحۀ انگلیسی باید بیفتد.
 */
$login_dir = function ( $file ) {
	$out = array();
	foreach ( array( 'rtl' => false, 'ltr' => true ) as $want => $ltr ) {
		$GLOBALS['tp_ltr'] = $ltr;
		ob_start();
		include $file;
		$h = ob_get_clean();
		$out[ $want ] = preg_match( '#<div class="tp-auth-wrap" dir="([a-z]+)">#', $h, $m ) ? $m[1] : '';
	}
	unset( $GLOBALS['tp_ltr'] );
	return array( 'rtl' => 'rtl', 'ltr' => 'ltr' ) === $out;
};
$login_mut = tempnam( sys_get_temp_dir(), 'tp-uc-login-' );
file_put_contents( $login_mut, preg_replace( '#<div class="tp-auth-wrap" dir="<\?php[^>]*\?>">#', '<div class="tp-auth-wrap" dir="rtl">', $src( 'templates/login.php' ) ) );
$login_ok  = $login_dir( $UC . 'templates/login.php' );
$login_bad = $login_dir( $login_mut );
@unlink( $login_mut );
t( 'فرم ورود: جهت از زبان صفحه — فارسی rtl، انگلیسی ltr؛ جهشِ `dir="rtl"` ثابت می‌افتد  ۲٫۰٫۸',
	$login_ok && ! $login_bad, ( $login_ok ? '' : 'قالب واقعی نادرست' ) . ( $login_bad ? ' · جهش را نگرفت' : '' ) );

/*
 * ── ۱٫۱۰٫۳ — فرم ورود و پیام‌هایش به زبان صفحه ──
 * واژه‌نامۀ سه‌زبانۀ واقعی از خودِ کلاس خوانده می‌شود (این‌جا stub آن بار است).
 * جهش: قالب و کلاس احراز هویتِ ۱٫۱۰٫۲ (dist/topersia-user-core-1.10.2.zip) — فارسیِ ثابت و `__()` بی‌کاتالوگ.
 */
$dict = preg_match( '/private array \$strings = \[(.*?)\n    \];/su', $src( 'includes/class-topersia-i18n.php' ), $dm ) ? eval( 'return [' . $dm[1] . '];' ) : array();

$uc_login = function ( $tpl ) use ( $dict ) {
	$bare = preg_replace( '/<\?php.*?\?>/su', '', $tpl );
	$bare = preg_replace( '#/\*.*?\*/#su', '', $bare );
	preg_match_all( "/\\\$tp_t\\('([a-z_]+)'\\)/", $tpl, $km );
	$keys_ok = $km[1] && ! array_filter( $km[1], function ( $k ) use ( $dict ) { return empty( $dict[ $k ]['fa'] ) || empty( $dict[ $k ]['en'] ) || empty( $dict[ $k ]['ar'] ); } );
	return ! preg_match( '/[\x{0600}-\x{06FF}]/u', $bare ) && $keys_ok && 6 === count( array_unique( $km[1] ) );
};
/* ۱٫۱۰٫۲ (همراه v4.4.1) از زیپ dist/ — برچسب‌های پیش از درون‌ریزی ۱ اکتبر در مخزن تازه نیستند (۰ب٫۲، P5-D284) */
$uc_old = function ( $rel ) {
	$zip = new ZipArchive();
	if ( true !== $zip->open( dirname( __DIR__ ) . '/dist/topersia-user-core-1.10.2.zip' ) ) {
		return '';
	}
	$s = $zip->getFromName( 'topersia-user-core/' . $rel );
	$zip->close();
	return false === $s ? '' : (string) $s;
};
$old_login = $uc_old( 'templates/login.php' );
t( 'فرم ورود: هیچ متن فارسیِ ثابت — شش برچسب و دکمه از واژه‌نامۀ سه‌زبانه (fa · en · ar)؛ جهش — قالب ۱٫۱۰٫۲ — می‌افتد  ۱٫۱۰٫۳',
	$uc_login( $src( 'templates/login.php' ) ) && false !== strpos( $old_login, 'tp-auth-card' ) && ! $uc_login( $old_login ) );

$uc_auth = function ( $php ) use ( $dict ) {
	preg_match_all( "/self::msg\\( '([a-z_]+)', '([^']+)' \\)/u", $php, $mm, PREG_SET_ORDER );
	$bad = array();
	foreach ( $mm as $m ) {
		if ( empty( $dict[ $m[1] ] ) || $dict[ $m[1] ]['fa'] !== $m[2] || '' === $dict[ $m[1] ]['en'] || '' === $dict[ $m[1] ]['ar'] ) { $bad[] = $m[1]; }
	}
	return count( $mm ) >= 15 && ! $bad && ! preg_match( "/__\\( '[^']*[\\x{0600}-\\x{06FF}]/u", $php );
};
$old_auth = $uc_old( 'includes/class-topersia-auth.php' );
t( 'ورود پیامکی: هر پیام مسیر ورود از `ToPersia_Auth::msg()` — کلید در واژه‌نامه با انگلیسی و عربی، جانشینِ فارسی همان مقدار واژه‌نامه؛ هیچ `__()` فارسی؛ جهش — کلاس ۱٫۱۰٫۲ — می‌افتد  ۱٫۱۰٫۳',
	$uc_auth( $src( 'includes/class-topersia-auth.php' ) ) && false !== strpos( $old_auth, 'final class ToPersia_Auth' ) && ! $uc_auth( $old_auth ) );

$uc_fixed = function ( $php ) {
	return ! preg_match( "/'message'\\s*=>\\s*'[^']*[\\x{0600}-\\x{06FF}][^']*'/u", $php ) && ! preg_match( "/__\\(\\s*'[^']*[\\x{0600}-\\x{06FF}]/u", $php );
};
$uc_left = $uc_caught = array();
foreach ( array( 'class-topersia-follow.php', 'class-topersia-jetengine.php', 'class-topersia-display-prefs.php', 'class-topersia-profile.php', 'class-topersia-mediana-sms.php' ) as $f ) {
	if ( ! $uc_fixed( $src( 'includes/' . $f ) ) ) { $uc_left[] = $f; }
	$old = $uc_old( 'includes/' . $f );
	if ( false !== strpos( $old, 'class ToPersia_' ) && ! $uc_fixed( $old ) ) { $uc_caught[] = $f; }
}
t( 'پیام‌های AJAX صفحۀ عمومی (دنبال‌کردن، ذخیره، ترجیحات، نمایه، پیامک) از واژه‌نامه — هیچ پیام فارسیِ ثابت؛ جهش — هر پنج کلاس ۱٫۱۰٫۲ — می‌افتد  ۱٫۱۰٫۳',
	! $uc_left && 5 === count( $uc_caught ), implode( ' · ', $uc_left ) . ( 5 === count( $uc_caught ) ? '' : ' · جهش فقط ' . count( $uc_caught ) ) );

$bad_php = array();
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $UC, FilesystemIterator::SKIP_DOTS ) ) as $f ) {
	if ( 'php' === $f->getExtension() ) {
		exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $f->getPathname() ) . ' 2>&1', $o, $code );
		if ( 0 !== $code ) { $bad_php[] = basename( $f->getPathname() ); }
	}
}
t( 'نحو: همهٔ پرونده‌های PHP', ! $bad_php, implode( ' · ', $bad_php ) );

/* ── نظر — خانواده‌ها  P5-D234 ── */
$fam = ToPersia_JetReviews_Setup::families();
t( 'نظر: پنج خانواده با معیارهای مشخصات',
	array( 'destination', 'nature', 'heritage', 'event', 'border' ) === array_keys( $fam )
	&& array( 'جاذبه‌ها', 'دسترسی', 'اقامت و خدمات', 'مهمان‌نوازی' ) === $fam['destination']['criteria']
	&& array( 'چشم‌انداز', 'دسترسی', 'ایمنی', 'امکانات' ) === $fam['nature']['criteria']
	&& array( 'ارزش دیدن', 'نگهداری', 'دسترسی', 'خدمات بازدید' ) === $fam['heritage']['criteria']
	&& array( 'برگزاری', 'فضا', 'ارزش حضور' ) === $fam['event']['criteria']
	&& array( 'سرعت تردد', 'امکانات', 'برخورد کارکنان' ) === $fam['border']['criteria'] );

$none = array( 'tp_country', 'tp_region', 'tp_county', 'tp_handicraft', 'tp_local_food', 'tp_visa', 'tp_currency', 'tp_health_safety', 'tp_travel_etiquette', 'tp_calendar', 'tp_transport', 'tp_route' );
$leak = array_filter( $none, function ( $t ) { return '' !== ToPersia_JetReviews_Setup::family_of( $t ); } );
t( 'نظر: کشور، منطقه، شهرستان، صنایع دستی، خوراک و نوع‌های اطلاعاتی بی‌نظر', ! $leak, implode( ' · ', $leak ) );

t( 'نظر: یک ردیف review type به ازای هر نوع — نامک {type}-review-type، ۱ تا ۵',
	'tp_mountain-review-type' === ToPersia_JetReviews_Setup::type_slug( 'tp_mountain' )
	&& array( array( 'label' => 'سرعت تردد', 'step' => 1, 'max' => 5 ), array( 'label' => 'امکانات', 'step' => 1, 'max' => 5 ), array( 'label' => 'برخورد کارکنان', 'step' => 1, 'max' => 5 ) ) === ToPersia_JetReviews_Setup::type_fields( 'tp_border_crossing' ) );

$ts = ToPersia_JetReviews_Setup::type_settings( 'tp_city', true );
t( 'نظر: تأیید مدیر، نقش مشترک مجاز، بی دادهٔ ساختاریافته (بی AggregateRating)',
	true === $ts['need_approve'] && in_array( 'subscriber', $ts['allowed_roles'], true ) && false === $ts['structuredata'] && false === $ts['metadata']
	&& false !== strpos( $src( 'includes/class-topersia-jetreviews-setup.php' ), '$count = self::run( true );' ) );

/* ── نظر — ثبت ── */
t( 'ثبت نظر: امتیاز همان فرمول JetReviews — میانگین درصد',
	80 === ToPersia_Review_Form::rating( array( 5, 4, 3, 4 ) ) && 100 === ToPersia_Review_Form::rating( array( 5, 5, 5 ) ) && 0 === ToPersia_Review_Form::rating( array() ) );

$ok = ToPersia_Review_Form::prepare( 'tp_province', 99, 7, array( 5, 4, 3, 4 ), 'عنوان', 'متن آزمونی دربارهٔ این استان.' );
t( 'ثبت نظر: ردیف آماده — نویسنده کاربر جاری، شناسهٔ زبان اصلی، در انتظار تأیید، معیارها با برچسب',
	$ok['ok'] && '7' === $ok['row']['author'] && 99 === $ok['row']['post_id'] && 0 === $ok['row']['approved']
	&& 'tp_province-review-type' === $ok['row']['type_slug'] && 80 === $ok['row']['rating']
	&& 'جاذبه‌ها' === unserialize( $ok['row']['rating_data'] )[0]['field_label'] );

t( 'ثبت نظر: امتیاز بیرون از ۱ تا ۵، معیار کم، متن کوتاه یا بلند — رد',
	! ToPersia_Review_Form::prepare( 'tp_province', 99, 7, array( 5, 6, 3, 4 ), '', 'متن کافی برای آزمون' )['ok']
	&& ! ToPersia_Review_Form::prepare( 'tp_province', 99, 7, array( 5, 4, 3 ), '', 'متن کافی برای آزمون' )['ok']
	&& ! ToPersia_Review_Form::prepare( 'tp_province', 99, 7, array( 5, 4, 3, 4 ), '', 'کوتاه' )['ok']
	&& ! ToPersia_Review_Form::prepare( 'tp_province', 99, 7, array( 5, 4, 3, 4 ), '', str_repeat( 'ب', 3001 ) )['ok']
	&& 'review_not_allowed' === ToPersia_Review_Form::prepare( 'tp_county', 99, 7, array( 5 ), '', 'متن کافی برای آزمون' )['error'] );

$rf = $src( 'includes/class-topersia-review-form.php' );
t( 'ثبت نظر: نانس، فقط واردشده، API دادهٔ JetReviews بی اعلان ایمیلی، شمار نویسه بی mbstring',
	false !== strpos( $rf, "wp_verify_nonce( \$nonce, 'topersia_user_core' )" ) && false !== strpos( $rf, '! is_user_logged_in()' )
	&& false !== strpos( $rf, '\Jet_Reviews\Reviews\Data::get_instance()->add_new_review( $prep[\'row\'], false );' )
	&& false !== strpos( $rf, "preg_match_all( '/./us', \$content )" ) && false === strpos( $rf, 'mb_strlen(' ) );

/* ── جدول JetReviews بی‌حساسیت به حروف ── */
$rv = $src( 'includes/class-topersia-reviews.php' );
t( 'نظرها: SHOW TABLES بی‌حساسیت به حروف (lower_case_table_names=1)',
	false !== strpos( $rv, 'strcasecmp($found, $table) === 0' ) && false === strpos( $rv, '($found === $table)' ) );

/* ── دنبال‌کردن  P5-D238 ── */
$GLOBALS['tp_umeta'][5]['_topersia_followed'] = array( '99', 99, 0, 'x', 399, 399 );
t( 'دنبال‌کردن: متای کاربر، شناسه‌های یکتا و مثبت',
	array( 99, 399 ) === ToPersia_Follow::ids( 5 ) && array() === ToPersia_Follow::ids( 0 ) && 500 === ToPersia_Follow::MAX );

$fo = $src( 'includes/class-topersia-follow.php' );
t( 'دنبال‌کردن: API با نانس، شناسهٔ زبان اصلی، وضعیت پس از بارگذاری، خبر از فیلتر هستهٔ نمایش',
	false !== strpos( $fo, "add_action( 'wp_ajax_topersia_follow_entity'," ) && false !== strpos( $fo, "add_action( 'wp_ajax_topersia_entity_state'," )
	&& false !== strpos( $fo, "wp_verify_nonce( \$nonce, 'topersia_user_core' )" ) && false !== strpos( $fo, 'ToPersia_JetEngine::canonical_id(' )
	&& false !== strpos( $fo, "apply_filters( 'topersia_entity_latest_news', array(), \$view, \$news_per_item )" ) );

$dash = $src( 'templates/dashboard.php' );
t( 'پنل: تب «دنبال‌شده‌ها» با لغو و آخرین خبر هر مورد',
	false !== strpos( $src( 'includes/class-topersia-admin.php' ), "'following'     => array( 'type' => 'builtin', 'enabled' => 1, 'order' => 35, 'icon' => 'follow',  'fa' => 'دنبال‌شده‌ها'" )
	&& false !== strpos( $dash, 'data-tp-panel="following"' ) && false !== strpos( $dash, 'data-tp-unfollow' ) && false !== strpos( $dash, "\$item['news']" ) );

t( 'پنل: فهرست ذخیره‌ها سر جایش — تب saved با کارت و حذف',
	false !== strpos( $dash, 'data-tp-panel="saved"' ) && false !== strpos( $dash, 'class="tp-save-entity is-saved"' ) );

/* ── ورود ── */
t( 'ورود: بازگشت امن — همین میزبان بله، میزبان بیگانه به برگهٔ حساب',
	'https://topersia.com/province/isfahan/' === ToPersia_Auth::safe_redirect( 'https://topersia.com/province/isfahan/' )
	&& 'https://topersia.com/account/' === ToPersia_Auth::safe_redirect( 'https://evil.example/x' )
	&& 'https://topersia.com/account/' === ToPersia_Auth::safe_redirect( '' ) );

$js = $src( 'assets/js/user-core.js' );
t( 'ورود: فرم پیامکی دستگیره دارد — تا ۱٫۹٫۱۲ نداشت',
	false !== strpos( $js, "ajax('topersia_send_otp'" ) && false !== strpos( $js, "ajax('topersia_verify_otp',{token:token,otp:code?code.value:'',redirect:back})" )
	&& false !== strpos( $js, "document.querySelectorAll('[data-tp-auth]').forEach(bindAuth);" ) );

t( 'ورود: پنجرهٔ ورود/ثبت‌نام برای مهمان، با رویداد topersia:login-required و بازگشت به همین صفحه',
	false !== strpos( $js, "document.addEventListener('topersia:login-required',function(e){open(e.detail);});" )
	&& false !== strpos( $js, 'cfg.openLogin=open;' ) && false !== strpos( $src( 'includes/class-topersia-auth.php' ), "add_action( 'wp_footer', array( \$this, 'login_modal' ), 20 );" )
	&& false !== strpos( $src( 'includes/class-topersia-auth.php' ), 'if ( is_user_logged_in() || ! function_exists( \'wp_script_is\' )' ) );

t( 'ورود: OTP برای حساب دارای دسترسی همچنان بسته',
	false !== strpos( $src( 'includes/class-topersia-auth.php' ), "foreach ( array( 'manage_options', 'edit_posts', 'edit_pages', 'upload_files' ) as \$cap )" ) );

/* ── حرکت ── */
$dp = $src( 'includes/class-topersia-display-prefs.php' );
t( 'حرکت: پیش‌فرضی نوشته نمی‌شود؛ فقط انتخاب خودِ کاربر — مهمان پیرو سیستم',
	1 === substr_count( $dp, 'update_user_meta(' ) && false !== strpos( $dp, "'motion'   => ['off', '']," ) );

t( 'حذف افزونه: متای دنبال‌شده‌ها هم پاک می‌شود',
	false !== strpos( $src( 'uninstall.php' ), "'_topersia_followed'," ) );

/* ── پنل کاربر فقط منتشرشده — P5-D247 (۱٫۱۰٫۱) ──────────────────────
   «نظرهای من» عنوان و پیوند پستِ منتشرنشده را نشان می‌داد. ردیف نظر
   می‌ماند، ولی پستی که دیگر منتشرشده نیست — پیش‌نویس، خصوصی، در انتظار،
   رمزدار، زباله یا حذف‌شده — بی عنوان و بی پیوند با «این مطلب دیگر در
   دسترس نیست». ذخیره‌ها و دنبال‌شده‌ها رمزدار را نمی‌آورند و ترجمهٔ
   منتشرنشده هرگز جای اصل نمی‌نشیند. */

if ( ! class_exists( 'TP_UC_WPDB' ) ) {
	/* جعلیِ کوچکِ $wpdb — فقط همان سه کوئریِ ToPersia_Reviews */
	class TP_UC_WPDB {
		public $prefix = 'wp_';
		public $rows   = array();
		public function esc_like( $t ) { return addcslashes( (string) $t, '_%\\' ); }
		public function prepare( $q, ...$a ) { return (string) $q; }
		public function get_var( $q ) { return false !== stripos( $q, 'SHOW TABLES' ) ? 'WP_JET_REVIEWS' : null; }
		public function get_results( $q, $o = null ) { return false !== strpos( $q, 'FROM wp_jet_reviews' ) ? $this->rows : array(); }
	}
}

$keep_uc = array( $GLOBALS['tp_posts'] ?? array(), $GLOBALS['wpdb'] ?? null, $GLOBALS['tp_filters'] ?? array() );

$GLOBALS['tp_posts'][601] = array( 'type' => 'tp_city', 'title' => 'کاشان', 'url' => 'https://topersia.com/kashan/' );
foreach ( array( 602 => array( 'draft', '' ), 603 => array( 'private', '' ), 604 => array( 'pending', '' ), 605 => array( 'publish', 'رمز' ), 606 => array( 'trash', '' ) ) as $pid => $st ) {
	$GLOBALS['tp_posts'][ $pid ] = array( 'type' => 'tp_city', 'title' => 'TPHIDDEN ' . $st[0], 'url' => 'https://topersia.com/hidden-' . $pid . '/', 'status' => $st[0], 'password' => $st[1] );
}
/* ۶۰۷ اصلاً نیست — پستِ حذف‌شده */
$GLOBALS['wpdb']       = new TP_UC_WPDB();
$GLOBALS['wpdb']->rows = array();
foreach ( range( 601, 607 ) as $n => $pid ) {
	$GLOBALS['wpdb']->rows[] = array( 'id' => 900 + $n, 'post_id' => $pid, 'post_type' => 'tp_city', 'title' => 'نظر ' . $pid, 'content' => 'متن نظر ' . $pid, 'rating' => 4, 'approved' => 1, 'date' => '2026-09-2' . $n );
}

$mine    = ToPersia_Reviews::instance()->my_reviews( 5 );
$gone    = array_filter( $mine, function ( $r ) { return 601 !== $r['post_id']; } );
$gone_ok = 6 === count( $gone );
foreach ( $gone as $r ) {
	$gone_ok = $gone_ok && 'این مطلب دیگر در دسترس نیست' === $r['post_title'] && '' === $r['post_url'] && 'متن نظر ' . $r['post_id'] === $r['content'];
}
t( 'P5-D247 نظرهای من: ردیف هر نظر می‌ماند؛ پست پیش‌نویس، خصوصی، در انتظار، رمزدار، زباله و حذف‌شده بی عنوان و بی پیوند — «این مطلب دیگر در دسترس نیست»',
	7 === count( $mine ) && $gone_ok && false === strpos( wp_json_encode( $mine ), 'TPHIDDEN' ) && false === strpos( wp_json_encode( $mine ), 'hidden-' ),
	wp_json_encode( array_map( function ( $r ) { return array( $r['post_id'], $r['post_title'], $r['post_url'] ); }, $mine ) ) );

t( 'P5-D247 نظرهای من: پست منتشرشده همان عنوان و پیوند خودش',
	isset( $mine[0] ) && 601 === $mine[0]['post_id'] && 'کاشان' === $mine[0]['post_title'] && 'https://topersia.com/kashan/' === $mine[0]['post_url'] );

t( 'P5-D247 پنل: قالب نظرِ بی‌پیوند را span می‌کند نه a',
	false !== strpos( $dash, "<?php if (\$review['post_url']) : ?>" ) && false !== strpos( $dash, '<span class="tp-review-entity tp-muted"><?php echo esc_html($review[\'post_title\']); ?></span>' ) );

t( 'P5-D247 منتشرشده: is_live فقط publish بی‌گذرواژه',
	ToPersia_JetEngine::is_live( 601 ) && ! ToPersia_JetEngine::is_live( 602 ) && ! ToPersia_JetEngine::is_live( 603 )
	&& ! ToPersia_JetEngine::is_live( 604 ) && ! ToPersia_JetEngine::is_live( 605 ) && ! ToPersia_JetEngine::is_live( 606 ) && ! ToPersia_JetEngine::is_live( 607 ) );

/* ترجمهٔ منتشرنشده جای اصل نمی‌نشیند */
$GLOBALS['tp_posts'][1601] = array( 'type' => 'tp_city', 'title' => 'Kashan', 'url' => 'https://topersia.com/en/kashan/' );
$GLOBALS['tp_posts'][1602] = array( 'type' => 'tp_city', 'title' => 'TPHIDDEN en', 'url' => 'https://topersia.com/en/hidden/', 'status' => 'draft' );
$GLOBALS['tp_filters']['wpml_current_language'] = array( function () { return 'en'; } );
$GLOBALS['tp_filters']['wpml_object_id']        = array( function ( $id ) { $m = array( 601 => 1601, 608 => 1602 ); return $m[ (int) $id ] ?? $id; } );
t( 'P5-D247 پنل: نسخهٔ زبان جاری اگر منتشرشده است؛ ترجمهٔ پیش‌نویس هرگز — اصل جایش',
	1601 === ToPersia_JetEngine::localized_id( 601, 'tp_city' ) && 608 === ToPersia_JetEngine::localized_id( 608, 'tp_city' ) );

$je = $src( 'includes/class-topersia-jetengine.php' );
t( 'P5-D247 پنل: ذخیره‌ها و دنبال‌شده‌ها بی رمزدار؛ ذخیره، دنبال‌کردن و ثبت نظر فقط روی منتشرشدهٔ بی‌گذرواژه',
	(bool) preg_match( "/'post_status'\s*=> 'publish',\s*'has_password'\s*=> false,/", $je )
	&& (bool) preg_match( "/'post_status'\s*=> 'publish',\s*'has_password'\s*=> false,/", $fo )
	&& 2 === substr_count( $je, '!self::is_live($post_id)' )
	&& false !== strpos( $fo, "'' !== (string) \$post->post_password" )
	&& false !== strpos( $src( 'includes/class-topersia-review-form.php' ), "'' !== (string) \$post->post_password" ) );

list( $GLOBALS['tp_posts'], $GLOBALS['wpdb'], $GLOBALS['tp_filters'] ) = $keep_uc;

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
