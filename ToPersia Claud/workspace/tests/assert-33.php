<?php
/**
 * دستهٔ 33 — «پاسخ کوتاه» روادید و ارز (P5-D454، چیدمان ب). از assert.php بار می‌شود؛ PHP خالص با stub.
 *
 * سه پرسش، فقط از متای موجود، HTML سرور؛ نبود ← «—» با aria-label «نامشخص»؛ هیچ مقدار ساختگی. هر ادعا جهش دارد.
 * رفتار روی دادهٔ واقعی و در سه زبان: tests/quick-local.php.
 */

echo "\nدستهٔ 33 — پاسخ کوتاه روادید و ارز  P5-D454\n" . str_repeat( '─', 78 ) . "\n";

/* اسکیمای زندهٔ بسته در این آزمون نیست — فقط گزینه‌های لازم، با همان برچسب‌های JetEngine */
if ( ! function_exists( 'topersia_pack_schema' ) ) {
	function topersia_pack_schema( $type ) {
		$o = function ( $pairs ) { $r = array(); foreach ( $pairs as $v => $l ) { $r[] = array( 'value' => $v, 'label' => $l ); } return $r; };
		return 'tp_visa' === $type
			? array( 'fields' => array( 'visa_policy_matrix' => array( 'fields' => array( 'pc_policy_code' => array( 'options' => $o( array( 'visa_free' => 'بدون نیاز به روادید', 'voa' => 'روادید فرودگاهی', 'e_visa_required' => 'ملزم به روادید الکترونیکی پیشین', 'prohibited' => 'ممنوع‌الورود', 'not_applicable' => 'موضوعیت ندارد' ) ) ) ) ) ) )
			: array( 'fields' => array( 'international_card_status' => array( 'options' => $o( array( 'accepted' => 'پذیرفته می‌شود', 'partially_accepted' => 'پذیرش محدود' ) ) ) ) );
	}
}

$GLOBALS['tp_posts'][9301] = array( 'type' => 'tp_visa', 'title' => 'روادید آزمون', 'url' => 'https://topersia.com/visa/test/' );
$GLOBALS['tp_posts'][9302] = array( 'type' => 'tp_currency', 'title' => 'یورو', 'url' => 'https://topersia.com/currency/test/' );
$GLOBALS['tp_posts'][9303] = array( 'type' => 'tp_city', 'title' => 'شهر آزمون', 'url' => 'https://topersia.com/city/test/' );

/** @return array{0:string,1:string,2:string} dt، dd، و تعداد خانه‌ها */
$q33 = function ( $html ) {
	preg_match_all( '#<dt>(.*?)</dt><dd>(.*?)</dd>#su', $html, $m );
	return array( $m[1], $m[2] );
};
$none33 = '<span class="tp-quick__none" role="img" aria-label="نامشخص">—</span>';

/* روادید با داده */
$GLOBALS['tp_meta'][9301] = array(
	'max_total_stay_days' => 30,
	'visa_policy_matrix'  => array(
		'item-0' => array( 'pc_policy_code' => 'voa', 'pc_fee_min_eur' => '0', 'pc_fee_max_eur' => '75' ),
		'item-1' => array( 'pc_policy_code' => 'visa_free', 'pc_fee_min_eur' => '', 'pc_fee_max_eur' => '' ),
		'item-2' => array( 'pc_policy_code' => 'voa' ),
		'item-3' => array( 'pc_policy_code' => 'not_applicable' ),
		'item-4' => array( 'pc_policy_code' => 'e_visa_required' ),
		'item-5' => array( 'pc_policy_code' => 'prohibited' ),
	),
);
$v_full = r( 9301, 'tp_quick' );
list( $vq, $va ) = $q33( $v_full );
$v_claim = function ( $x ) use ( $q33, $none33 ) {
	list( $q, $a ) = $q33( $x );
	return 3 === count( $q ) && 'روادید لازم است؟' === $q[0] && 'چند روز؟' === $q[1] && 'هزینه؟' === $q[2]
		&& 'روادید فرودگاهی، بدون نیاز به روادید، ملزم به روادید الکترونیکی پیشین …' === $a[0]   /* برچسب متمایز، بی not_applicable، سه‌تا + «…» */
		&& '۳۰ روز' === $a[1] && 'از ۰ تا ۷۵ یورو' === $a[2]
		&& false !== strpos( $x, '<dl class="tp-quick">' ) && false !== strpos( $x, '<h2 id="tp-quickTitle">پاسخ کوتاه</h2>' )
		&& false !== strpos( $x, 'aria-labelledby="tp-quickTitle"' ) && false === strpos( $x, $none33 );
};
tp441_mut( 'روادید: «لازم است؟» برچسب‌های متمایز سیاست (تا سه + …)، «چند روز؟» حداکثر اقامت، «هزینه؟» کمینه تا بیشینهٔ ماتریس — dl/dt/dd با h2 — جهش: بی ردیف تکراری‌زدایی یا بی «…» می‌افتد',
	$v_claim, $v_full, str_replace( 'روادید فرودگاهی، بدون نیاز به روادید، ملزم به روادید الکترونیکی پیشین …', 'روادید فرودگاهی، روادید فرودگاهی، بدون نیاز به روادید', $v_full ) );

/* روادید بی داده: سه «—» با aria-label «نامشخص»، هیچ پاسخ ساختگی */
$GLOBALS['tp_meta'][9301] = array();
$v_none = r( 9301, 'tp_quick' );
tp441_mut( 'روادید بی داده: هر سه پاسخ «—» با aria-label «نامشخص» (role=img)؛ هیچ عدد یا متن ساختگی — جهش: برچسب دسترس‌پذیر خالی می‌افتد',
	function ( $x ) use ( $none33 ) { return 3 === substr_count( $x, $none33 ) && 3 === substr_count( $x, '<dt>' ) && 0 === preg_match( '#<dd>[^<]#u', $x ); },
	$v_none, str_replace( 'aria-label="نامشخص"', 'aria-label=""', $v_none ) );

/* هزینه: همه صفر ← «رایگان»؛ یک مقدار ← «N یورو»؛ ماتریس بی هزینه ← «—» */
$GLOBALS['tp_meta'][9301] = array( 'visa_policy_matrix' => array( 'item-0' => array( 'pc_policy_code' => 'visa_free', 'pc_fee_min_eur' => '0', 'pc_fee_max_eur' => '0' ) ) );
$fee_free = $q33( r( 9301, 'tp_quick' ) )[1][2];
$GLOBALS['tp_meta'][9301] = array( 'visa_policy_matrix' => array( 'item-0' => array( 'pc_policy_code' => 'voa', 'pc_fee_min_eur' => '50', 'pc_fee_max_eur' => '50' ) ) );
$fee_one = $q33( r( 9301, 'tp_quick' ) )[1][2];
$GLOBALS['tp_meta'][9301] = array( 'visa_policy_matrix' => array( 'item-0' => array( 'pc_policy_code' => 'voa' ) ) );
$fee_none = $q33( r( 9301, 'tp_quick' ) )[1][2];
tp441_mut( 'هزینه: همه صفر ← «رایگان»؛ یک مقدار ← «۵۰ یورو»؛ بی هزینه ← نامشخص — جهش: صفر به «—» بیفتد، آزمون می‌افتد',
	function ( $x ) use ( $none33 ) { return 'رایگان' === $x[0] && '۵۰ یورو' === $x[1] && $none33 === $x[2]; },
	array( $fee_free, $fee_one, $fee_none ), array( $none33, $fee_one, $fee_none ) );

/* چند روز: بی max_total_stay_days، بازهٔ vt_stay_days انواع روادید */
$GLOBALS['tp_meta'][9301] = array( 'visa_types' => array( 'item-0' => array( 'vt_stay_days' => 30 ), 'item-1' => array( 'vt_stay_days' => 90 ), 'item-2' => array( 'vt_stay_days' => '' ) ) );
$stay_range = $q33( r( 9301, 'tp_quick' ) )[1][1];
$GLOBALS['tp_meta'][9301] = array( 'visa_types' => array( 'item-0' => array( 'vt_stay_days' => 30 ) ) );
$stay_one = $q33( r( 9301, 'tp_quick' ) )[1][1];
tp441_mut( 'چند روز: بی حداکثر اقامت، بازهٔ روزهای انواع روادید («از ۳۰ روز تا ۹۰ روز»)؛ یک نوع ← همان یک عدد',
	function ( $x ) { return 'از ۳۰ روز تا ۹۰ روز' === $x[0] && '۳۰ روز' === $x[1]; },
	array( $stay_range, $stay_one ), array( '۳۰ روز', $stay_one ) );

/* ارز */
$GLOBALS['tp_meta'][9302] = array(
	'international_card_status' => 'partially_accepted',
	'currency_symbol'           => '€',
	'currency_units'            => array(
		'item-0' => array( 'cu_role' => 'colloquial', 'cu_name' => 'تومان' ),
		'item-1' => array( 'cu_role' => 'legal_current', 'cu_name' => 'ریال' ),
	),
);
$c_full = r( 9302, 'tp_quick' );
tp441_mut( 'ارز: «کارت بین‌المللی کار می‌کند؟» وضعیت کلی، «واحد» نام واحد رسمی جاری، «نماد» currency_symbol — جهش: واحد غیررسمی (تومان) نمی‌افتد',
	function ( $x ) use ( $q33 ) {
		list( $q, $a ) = $q33( $x );
		return array( 'کارت بین‌المللی کار می‌کند؟', 'واحد', 'نماد' ) === $q && 'پذیرش محدود' === $a[0] && 'ریال' === $a[1] && '€' === $a[2];
	},
	$c_full, str_replace( '<dd>ریال</dd>', '<dd>تومان</dd>', $c_full ) );

/* واحد: بی ردیف رسمی جاری ← نام خود پست؛ بی وضعیت کارت و نماد ← «—» */
$GLOBALS['tp_meta'][9302] = array();
$c_min = r( 9302, 'tp_quick' );
tp441_mut( 'ارز بی داده: واحد = نام خود پست (دادهٔ واقعی، نه ساختگی)؛ کارت و نماد ← «—» با «نامشخص»',
	function ( $x ) use ( $q33, $none33 ) { $a = $q33( $x )[1]; return $none33 === $a[0] && 'یورو' === $a[1] && $none33 === $a[2]; },
	$c_min, str_replace( '<dd>یورو</dd>', '<dd>—</dd>', $c_min ) );

/* انواع دیگر: هیچ؛ و بخش در چیدمان فقط برای روادید و ارز، اول ستون اصلی و بی ورودی نوار بخش‌ها */
$GLOBALS['tp_meta'][9303] = array( 'currency_symbol' => '€', 'max_total_stay_days' => 30 );
$other = r( 9303, 'tp_quick' );
$lay_v = topersia_g_layout( 9301 );
$lay_c = topersia_g_layout( 9302 );
$lay_o = topersia_g_layout( 9303 );
tp441_mut( 'سایر انواع: «پاسخ کوتاه» نمی‌آید؛ برای روادید و ارز نخستین ناحیهٔ ستون اصلی است، بی پیوند در نوار بخش‌ها (nav تهی) — جهش: ناحیه برای شهر هم بیاید می‌افتد',
	function ( $x ) { return '' === trim( $x[0] ) && 'tp-quick' === $x[1]['main'][0]['id'] && '' === $x[1]['main'][0]['nav'] && 'tp-quick' === $x[2]['main'][0]['id'] && 'tp-quick' !== $x[3]['main'][0]['id']; },
	array( $other, $lay_v, $lay_c, $lay_o ), array( $other, $lay_v, $lay_c, $lay_v ) );

/* اسکیمای JSON-LD و داده دست نمی‌خورد؛ فقط لایهٔ نمایش (FIELDMAP تازه ندارد): هیچ کلید تازه‌ای نیامده */
tp441_mut( 'بی فیلد تازه: «پاسخ کوتاه» فقط از کلیدهای موجود می‌خواند (هیچ get_post_meta مستقیم، هیچ کلید ساختگی)',
	function ( $src ) {
		$a = strpos( $src, 'function topersia_g_quick_cells' );
		$b = strpos( $src, 'function topersia_g_sc_quick' );
		$body = substr( $src, $a, $b - $a );
		return false === strpos( $body, 'get_post_meta' ) && false === strpos( $body, 'update_post_meta' )
			&& false !== strpos( $body, "'max_total_stay_days'" ) && false !== strpos( $body, "'international_card_status'" ) && false !== strpos( $body, "'currency_symbol'" );
	},
	(string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' ),
	str_replace( "topersia_g_raw( \$pid, 'max_total_stay_days' )", "get_post_meta( \$pid, 'max_total_stay_days', true )", (string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' ) ) );

/* زبان صفحه: en و ar — جداکنندهٔ فهرست، هزارگان و رقم به زبان صفحه، متن از کاتالوگ؛ هیچ نویسهٔ فارسی در en */
$GLOBALS['tp_meta'][9301] = array(
	'max_total_stay_days' => 30,
	'visa_policy_matrix'  => array(
		'item-0' => array( 'pc_policy_code' => 'voa', 'pc_fee_min_eur' => '0', 'pc_fee_max_eur' => '3000000' ),
		'item-1' => array( 'pc_policy_code' => 'visa_free' ),
	),
);
$v_en = tp442_lang( 'en', function () { return r( 9301, 'tp_quick' ); } );
$v_ar = tp442_lang( 'ar', function () { return r( 9301, 'tp_quick' ); } );
tp441_mut( 'پاسخ کوتاه در en: «Do I need a visa?» · «Visa on arrival, No visa required» (جداکنندهٔ ,) · «30 days» · «From 0 to 3,000,000 EUR» — بی نویسهٔ عربی‌خط؛ جهش: جداکنندهٔ فارسی می‌افتد',
	function ( $x ) { return false !== strpos( $x, '<dt>Do I need a visa?</dt><dd>Visa on arrival, No visa required</dd>' ) && false !== strpos( $x, '<dd>30 days</dd>' ) && false !== strpos( $x, '<dd>From 0 to 3,000,000 EUR</dd>' ) && 0 === preg_match( '/\p{Arabic}/u', wp_strip_all_tags( $x ) ); },
	$v_en, str_replace( 'Visa on arrival, No', 'Visa on arrival، No', $v_en ) );
tp441_mut( 'پاسخ کوتاه در ar: برچسب‌ها و «٣٠ يومًا» و «من ٠ إلى ٣٬٠٠٠٬٠٠٠ يورو» از کاتالوگ، رقم عربی، بی حرف ویژهٔ فارسی؛ جهش: رقم فارسی می‌افتد',
	function ( $x ) { return false !== strpos( $x, 'تأشيرة عند الوصول، لا تأشيرة مطلوبة' ) && false !== strpos( $x, '٣٠ يومًا' ) && false !== strpos( $x, 'من ٠ إلى ٣٬٠٠٠٬٠٠٠ يورو' ) && 0 === preg_match( '/[پچژگکی]|[۰-۹]/u', wp_strip_all_tags( $x ) ); },
	$v_ar, str_replace( '٣٠ يومًا', '۳۰ يومًا', $v_ar ) );

unset( $GLOBALS['tp_posts'][9301], $GLOBALS['tp_posts'][9302], $GLOBALS['tp_posts'][9303], $GLOBALS['tp_meta'][9301], $GLOBALS['tp_meta'][9302], $GLOBALS['tp_meta'][9303] );
