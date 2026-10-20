<?php
/**
 * ادعاهای ۴٫۳٫۰ — از assert.php پیش از نگهبان سندها بار می‌شود و
 * همان شمارنده‌ها و همان داده‌ٔ آزمون را دارد.
 */

echo "\n۴٫۳٫۰\n" . str_repeat( '─', 78 ) . "\n";

$src_zones = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );
$src_js    = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-zones.js' );

/* ── بند ۰ — گارد پیوند ───────────────────────────────────────────── */

t( 'پیوند: جاذبه صفحه دارد — کارت `<a>` نه `<article>`  ۴٫۳٫۰ بند ۰',
	'https://topersia.com/naqsh-e-jahan/' === topersia_g_public_url( 60 )
	&& 'https://topersia.com/persepolis/' === topersia_g_public_url( 33 )
	&& 'https://topersia.com/silk-road/' === topersia_g_public_url( 66 )
	&& 'https://topersia.com/khatam/' === topersia_g_public_url( 32 ) );

t( 'پیوند: شهرستان صفحه ندارد، حتی با publicly_queryable  P5-D172',
	'' === topersia_g_public_url( 22 ) && in_array( 'tp_county', topersia_g_no_page_types(), true ) );

t( 'پیوند: پست پیش‌نویس پیوند نمی‌گیرد',
	'' === topersia_g_public_url( 72 ) );

$GLOBALS['tp_pt_private']['tp_museum'] = true;
t( 'پیوند: نوعی که publicly_queryable ندارد پیوند نمی‌گیرد',
	'' === topersia_g_public_url( 62 ) );
unset( $GLOBALS['tp_pt_private']['tp_museum'] );

t( 'پیوند: مبنا دیگر نقشهٔ رابطه نیست',
	false === strpos( $src_zones, "! isset( \$map[ \$type ] )" )
	&& false !== strpos( $src_zones, "empty( \$object->publicly_queryable )" ) );

$att20 = r( 20, 'tp_slider', array( 'rel' => 'attractions' ) );
t( 'پیوند: کارت اسلایدر جاذبه `<a class="tp-card-slide" href>`',
	false !== strpos( $att20, '<a class="tp-card-slide" href="https://topersia.com/naqsh-e-jahan/">' ) );

$GLOBALS['tp_posts'][10]['status'] = 'draft';
$crumb_draft = r( 20, 'tp_crumbs' );
unset( $GLOBALS['tp_posts'][10]['status'] );
/* ۴٫۳٫۰ نیای پیش‌نویس را نام بی‌پیوند نشان می‌داد؛ ۴٫۴٫۰ اصلاً نمی‌آوردش.  P5-D247 */
/* ۲و: کشور از مسیر راهنما رفته (D338)؛ دروازهٔ پیش‌نویس روی نیای صفحه‌دار در assert-2f.php سنجیده می‌شود (مسیر ثبت‌نامه) */
t( 'پیوند: کشور (منتشر یا پیش‌نویس) در مسیر راهنمای استان نمی‌آید و بقیهٔ مسیر می‌ماند  P5-D247 · D338',
	false === strpos( $crumb_draft, 'ایران' ) && false !== strpos( $crumb_draft, 'aria-current="page"' )
	&& false === strpos( r( 20, 'tp_crumbs' ), 'ایران' ) );

/* ── بند ۸ — اطلاعات سفر ─────────────────────────────────────────── */

t( 'اطلاعات سفر: استان — خودش ۱۵۷ و ۲۲۷، ارث ۱۵۹ و ۲۲۸ از کشور، پیش‌نویسِ ۲۲۹ نادیده  P5-D233',
	array( 68, 69, 70, 71 ) === ( function () { $a = topersia_g_practical_ids( 20 ); sort( $a ); return $a; } )() );

t( 'اطلاعات سفر: شهر از شهرستان به استان و از استان به کشور ارث می‌برد',
	array( 27 => array( 20, 10 ) ) === array( 27 => topersia_g_ancestors( 27 ) )
	&& array( 68, 69, 70, 71 ) === ( function () { $a = topersia_g_practical_ids( 27 ); sort( $a ); return $a; } )() );

t( 'اطلاعات سفر: کشور ارث نمی‌برد — همان گروه خودش',
	array( 70, 71 ) === ( function () { $a = topersia_g_practical_ids( 10 ); sort( $a ); return $a; } )() );

t( 'اطلاعات سفر: نام در عنوان، نوار بخش‌ها و برگهٔ بخش‌ها؛ «سفر عملی» هیچ‌جا',
	false === strpos( $src_zones, 'سفر عملی' )
	&& false === strpos( file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-map.php' ), 'سفر عملی' )
	&& 3 === substr_count( $src_zones, "'اطلاعات سفر'" ) );

/* ── بند ۹ — واژه‌های انگلیسی ───────────────────────────────────── */

$p20_430 = r( 20, 'tp_page' );
t( 'انگلیسی: هیچ کیکر لاتین سرکارت (tp-k) و کیکر کارت فرهنگ نیست  P5-D240',
	false === strpos( $p20_430, 'class="tp-k"' ) && false === strpos( $p20_430, 'tp-culture-card__kicker' )
	&& ! preg_match( '/>(MAP|FORECAST|WHEN|LISTEN &amp; WATCH|GEO|NATURE|HERITAGE|VILLAGES|PROVENANCE|ARTISANAT|CUISINE|PRACTICAL|NEIGHBORS|ROUTES|PROVINCES)</', $p20_430 ) );

$meta20_keep = $GLOBALS['tp_meta'][20];
$GLOBALS['tp_meta'][20]['official_name_en'] = 'Isfahan Province';
$GLOBALS['tp_meta'][20]['official_name_ar'] = 'محافظة أصفهان';
$alt_of = function ( $lang ) {
	$GLOBALS['tp_filters']['wpml_current_language'] = array( function () use ( $lang ) { return $lang; } );
	$h = r( 20, 'tp_hero' );
	unset( $GLOBALS['tp_filters']['wpml_current_language'] );
	return preg_match( '#<p class="tp-hero__alt">(.*?)</p>#u', $h, $m ) ? $m[1] : '';
};
$alt_fa = $alt_of( 'fa' );
$alt_en = $alt_of( 'en' );
$alt_ar = $alt_of( 'ar' );
/* جداکننده از ۴٫۴٫۰ ویرگول است، نه «·» — SPRINT-4.4 ۱٫۶ */
t( 'hero__alt: دو زبانِ دیگر، نه زبان صفحه — fa: en·ar · en: fa·ar · ar: fa·en  P5-D240',
	'<span lang="en" dir="ltr">Isfahan Province</span>، <em lang="ar">محافظة أصفهان</em>' === $alt_fa
	&& false !== strpos( $alt_en, '<span lang="fa">' ) && false !== strpos( $alt_en, '<em lang="ar">' ) && false === strpos( $alt_en, 'lang="en"' )
	&& false !== strpos( $alt_ar, '<span lang="fa">' ) && false !== strpos( $alt_ar, 'lang="en"' ) && false === strpos( $alt_ar, 'lang="ar"' ),
	$alt_en . ' | ' . $alt_ar );
$GLOBALS['tp_meta'][20] = $meta20_keep;

/* ── بند ۱۰ — مودال فهرست ───────────────────────────────────────── */

$list_att = preg_match( '#<div class="tp-gallery-modal tp-gallery-modal--list" id="tp-list-attractions".*?</div></div>(?=<div class="tp-gallery-modal|$)#su', $p20_430, $mm ) ? $mm[0] : '';
$list_cul = preg_match( '#<div class="tp-gallery-modal tp-gallery-modal--list" id="tp-list-culture".*?</div></div>(?=<div class="tp-gallery-modal|$)#su', $p20_430, $mm ) ? $mm[0] : '';

/* سه جاذبۀ دادۀ آزمون — از P5-D384 هر شماری مودال دارد؛ برچسب کارت فهرست از همان تابعی است که مودال می‌خواند. */
$att_tags = array();
foreach ( topersia_g_rel_items( 20, 'attractions', 0 )['items'] as $one ) {
	$att_tags[ $one['name'] ] = topersia_g_list_meta( 'attractions', $one );
}

t( 'فهرست: جاذبه ← شهرش، نه برچسب نوع  ۴٫۳٫۰ بند ۱۰',
	false !== strpos( $list_att, '<b>میدان نقش جهان</b><span class="tp-list__tag">کاشان</span>' ) && isset( $att_tags['میدان نقش جهان'] ) && 'کاشان' === $att_tags['میدان نقش جهان']
	&& ! in_array( 'بنای تاریخی', $att_tags, true ), wp_json_encode( $att_tags, JSON_UNESCAPED_UNICODE ) );

t( 'فهرست: فرهنگ ← خانواده‌اش',
	false !== strpos( $list_cul, '<b>میناکاری</b><span class="tp-list__tag">صنایع دستی</span>' ) );

t( 'فهرست: جمعیت نبود ← خالی، نه «—»',
	'' === topersia_g_list_meta( 'places', array( 'id' => 50 ) ) && '' === topersia_g_list_meta( 'attractions', array( 'place' => '' ) ) );

/* ── بند ۱۳ — واژه‌نامهٔ ۳۷ ──────────────────────────────────────── */

t( 'واژه‌نامهٔ ۳۷: needs_verification «نیازمند راستی‌آزمایی» در جدول پشتیبان  ۴٫۳٫۰ بند ۱۳',
	'نیازمند راستی‌آزمایی' === topersia_g_glossary_label( '37', 'needs_verification' ) );

/* ── بند ۲ — هوا از MET Norway  P5-D242 ─────────────────────────── */

$met_map = array(
	'rainshowersandthunder_day' => 'storm', 'lightssnowshowersandthunder' => 'storm', 'sleetandthunder' => 'storm',
	'lightsleet' => 'snow', 'heavysnowshowers_night' => 'snow', 'sleet' => 'snow',
	'rain' => 'rain', 'lightrainshowers_polartwilight' => 'rain',
	'fog' => 'fog', 'partlycloudy_day' => 'cloudy', 'cloudy' => 'cloudy',
	'clearsky_night' => 'clear', 'fair_day' => 'clear', 'نامعلوم' => 'clear',
);
$met_bad = array();
foreach ( $met_map as $sym => $want ) {
	if ( TP_Wx_Met_Norway::map( $sym ) !== $want ) { $met_bad[] = $sym . '→' . TP_Wx_Met_Norway::map( $sym ); }
}
t( 'MET: نماد ← وضعیت — thunder·snow/sleet·rain·fog·cloudy·clear، پسوند روز/شب بی‌اثر  P5-D242', ! $met_bad, implode( ' · ', $met_bad ) );

t( 'MET: گرم و سرد از دما با همان آستانه‌ها؛ بارش بر دما مقدم؛ dust هرگز',
	'hot' === TP_Wx_Met_Norway::from( array( 'symbol' => 'clearsky_day', 'temp_c' => 45 ) )
	&& 'cold' === TP_Wx_Met_Norway::from( array( 'symbol' => 'fair_night', 'temp_c' => -8 ) )
	&& 'rain' === TP_Wx_Met_Norway::from( array( 'symbol' => 'rain', 'temp_c' => 45 ) )
	&& ! in_array( 'dust', TP_Wx_Met_Norway::codes(), true ) );

t( 'MET: کلید کش = مختصات گردشده به دو رقم',
	'tp-wx-met-32.65,51.67' === TP_Wx_Met_Norway::cache_key( 32.6539, 51.6660 )
	&& TP_Wx_Met_Norway::cache_key( 32.6539, 51.6660 ) === TP_Wx_Met_Norway::cache_key( 32.6461, 51.6749 ) );

$GLOBALS['tp_options']['admin_email'] = 'admin@topersia.com';
t( 'MET: User-Agent شناسا «ToPersia/1.0 (+https://topersia.com; <admin_email>)»',
	'ToPersia/1.0 (+https://topersia.com; admin@topersia.com)' === TP_Wx_Met_Norway::user_agent() );

$now = 1790000000;
t( 'MET: تازگی از Expires — بی آن کمینه، دور بیشینه',
	1200 === TP_Wx_Met_Norway::ttl_from_expires( gmdate( 'D, d M Y H:i:s', $now + 1200 ) . ' GMT', $now )
	&& TP_Wx_Met_Norway::TTL_MIN === TP_Wx_Met_Norway::ttl_from_expires( '', $now )
	&& TP_Wx_Met_Norway::TTL_MAX === TP_Wx_Met_Norway::ttl_from_expires( gmdate( 'D, d M Y H:i:s', $now + 999999 ) . ' GMT', $now ) );

$wx_src = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-weather.php' );
/* ۴٫۴٫۱: دریافت در topersia_g_wx_met_fetch() جدا شد تا کش مرکزی هسته هم همان را صدا بزند (P5-D250) */
t( 'MET: درخواست شرطی If-Modified-Since، ۳۰۴ همان کش، Last-Modified نگه داشته می‌شود',
	false !== strpos( $wx_src, "\$headers['If-Modified-Since'] = (string) \$prev['meta']['last_modified'];" )
	&& false !== strpos( $wx_src, "if ( 304 === \$code && is_array( \$prev ) && array_key_exists( 'value', \$prev ) )" )
	&& false !== strpos( $wx_src, "'meta'  => array( 'last_modified' => (string) wp_remote_retrieve_header( \$response, 'last-modified' ) )," ) );

/* پاسخ ساختگی: سه روز به وقت تهران، ساعتی */
$tz  = new DateTimeZone( 'Asia/Tehran' );
$t0  = strtotime( '2026-09-27T00:00:00+03:30' );
$ser = array();
for ( $h = 0; $h < 72; $h ++ ) {
	$at    = $t0 + $h * 3600;
	$local = (int) ( new DateTime( '@' . $at ) )->setTimezone( $tz )->format( 'G' );
	$sym   = ( 1 === intdiv( $h, 24 ) && 14 === $local ) ? 'rainandthunder_day' : ( $local >= 6 && $local < 18 ? 'clearsky_day' : 'clearsky_night' );
	$ser[] = array(
		'time' => gmdate( 'Y-m-d\TH:i:s\Z', $at ),
		'data' => array(
			'instant'      => array( 'details' => array( 'air_temperature' => 15 + ( $local % 12 ), 'wind_from_direction' => 270, 'wind_speed' => 5 ) ),
			'next_1_hours' => array( 'summary' => array( 'symbol_code' => $sym ), 'details' => array( 'precipitation_amount' => 0.4 ) ),
		),
	);
}
$parsed = TP_Wx_Met_Norway::parse( array( 'properties' => array( 'timeseries' => $ser ) ), $tz, $t0 + 3600 );
t( 'MET: سه روز به وقت محلی — بیشینه/کمینه، شدیدترین نماد روزِ روشن، fx از ساعت جاری',
	is_array( $parsed ) && 3 === count( $parsed['days'] ) && 'امروز' === $parsed['days'][0]['d']
	&& 'clear' === $parsed['days'][0]['k'] && 'storm' === $parsed['days'][1]['k'] && 26 === $parsed['days'][0]['t'] && 15 === $parsed['days'][0]['n']
	&& 'clear' === $parsed['fx']['state'] && 18.0 === $parsed['fx']['windKph'] && 270.0 === $parsed['fx']['windDeg'] && 0 === $parsed['fx']['isDay'],
	json_encode( $parsed, JSON_UNESCAPED_UNICODE ) );

t( 'MET: ارائه‌دهندهٔ پیش‌فرض MET؛ آداپتور Open-Meteo مانده',
	'met-norway' === topersia_g_wx_provider() && in_array( 'TP_Wx_Open_Meteo', topersia_g_wx_providers(), true )
	&& function_exists( 'topersia_g_wx_open_meteo' ) );

t( 'MET: زیر کارت «داده‌های هوا: MET Norway»',
	false !== strpos( r( 20, 'tp_weather' ), '<p class="tp-wx__src tp-wx__credit">داده‌های هوا: MET Norway</p>' ) );

t( 'MET: پراکسی فقط درون کادر ایران — بیرونش رد',
	topersia_g_wx_in_bbox( 35.69, 51.39 ) && ! topersia_g_wx_in_bbox( 48.85, 2.35 ) );

/* ── بند ۳ — انیمیشن سربرگ ───────────────────────────────────────── */

t( 'انیمیشن: از همان پاسخ /weather روی سربرگ سوار می‌شود  ۴٫۳٫۰ بند ۳',
	(bool) preg_match( '/if \( data\.fx && hero && fx && \'function\' === typeof fx\.mount \) \{\s*try \{ fx\.mount\( hero, data\.fx \); \}/', $src_js ) );

$uc_dir = dirname( TP_PLUGIN_DIR ) . '/topersia-user-core/';
$uc_dp  = is_file( $uc_dir . 'includes/class-topersia-display-prefs.php' ) ? file_get_contents( $uc_dir . 'includes/class-topersia-display-prefs.php' ) : '';
$uc_au  = is_file( $uc_dir . 'includes/class-topersia-auth.php' ) ? file_get_contents( $uc_dir . 'includes/class-topersia-auth.php' ) : '';
t( 'حرکت: user-core پیش‌فرض off نمی‌گذارد — فقط انتخاب خودِ کاربر ذخیره می‌شود؛ مهمان و کاربر تازه پیرو سیستم',
	'' !== $uc_dp && 1 === substr_count( $uc_dp, 'update_user_meta(' ) && false === strpos( $uc_au, '_topersia_display_prefs' )
	&& false !== strpos( $uc_dp, 'if (!is_user_logged_in()) return;' )
	&& false !== strpos( file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-weather.js' ), "matchMedia( '(prefers-reduced-motion: reduce)' )" ) );

/* ── بند ۴ — هوش مصنوعی  P5-D235 ─────────────────────────────────── */

$caps = array( 'user' => 10, 'site' => 200 );
t( 'AI: سقف روزانه — کاربر و کل سایت؛ صفر یعنی بسته',
	'' === topersia_g_ai_cap_check( 9, 199, $caps ) && 'user' === topersia_g_ai_cap_check( 10, 0, $caps )
	&& 'site' === topersia_g_ai_cap_check( 0, 200, $caps ) && 'site' === topersia_g_ai_cap_check( 0, 0, array( 'user' => 5, 'site' => 0 ) ) );

$ai_caps = topersia_g_ai_caps();
t( 'AI: پیش‌فرض سقف هر کاربر ۱۰؛ هر دو در تنظیمات',
	10 === $ai_caps['user'] && 200 === $ai_caps['site'] );

$ai_src = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-ai.php' );
t( 'AI: رندر هیچ‌جا internal_text صدا نمی‌زند؛ دروازه پیش از خلاصه‌ساز (اولویت ۱) با نانس tpas_ai',
	! preg_match( '/TP_AI_Summary::internal_text\s*\(/', $src_zones )
	&& false !== strpos( $ai_src, "add_action( 'wp_ajax_tpas_ai_generate', 'topersia_g_ai_gate', 1 );" )
	&& false !== strpos( $ai_src, "check_ajax_referer( 'tpas_ai', 'nonce', false )" )
	&& false !== strpos( $ai_src, 'TP_AI_Summary::get_cached( $post, $type, $lang )' ) );

$GLOBALS['tp_user'] = 0;
$ai_g = r( 20, 'tp_ai' );
t( 'AI: مهمان — بی دعوت به ثبت‌نام و بی دکمهٔ ورود (P5-D358)؛ بی نانس و بی موتور',
	false === strpos( $ai_g, 'امکانات بیشتر' )
	&& false === strpos( $ai_g, 'data-tp-login="ai"' ) && false === strpos( $ai_g, 'data-tp-ai-gen' )
	&& false === strpos( $ai_g, 'tp-ai__engine" href="https://chatgpt' ) );

/* ── بند ۵ — نوار کنش  P5-D237 · P5-D238 · P5-D239 ───────────────── */

$bar = r( 20, 'tp_actionbar' );
preg_match_all( '/data-tp-share-to="([a-z]+)"/', $bar, $nets );
t( 'اشتراک: تلگرام · واتس‌اپ · X · فیسبوک · لینکدین · ایمیل — به همین ترتیب، آیکون وودمارت',
	array( 'telegram', 'whatsapp', 'x', 'facebook', 'linkedin', 'email' ) === $nets[1]
	&& false !== strpos( $bar, '<div class="wd-social-icons tp-share__icons"><a class="social-tg" href="https://t.me/share/url?url=' )
	&& 6 === substr_count( $bar, '<span class="wd-icon" aria-hidden="true"></span>' ) );

t( 'اشتراک: نشانی‌های استاندارد با noopener؛ بی اسکریپت شخص ثالث',
	5 === substr_count( $bar, ' target="_blank" rel="noopener nofollow"' )
	&& false !== strpos( $bar, 'https://www.facebook.com/sharer/sharer.php?u=' ) && false !== strpos( $bar, 'https://www.linkedin.com/sharing/share-offsite/?url=' )
	&& false !== strpos( $bar, 'https://wa.me/?text=' ) && false !== strpos( $bar, 'https://x.com/intent/tweet?url=' ) && false !== strpos( $bar, 'mailto:?subject=' )
	&& false === stripos( $bar, '<script' ) );

t( 'اشتراک: کپی نشانی، و «بیشتر» پنهان تا اسکریپت navigator.share را در لمس ببیند',
	false !== strpos( $bar, 'data-tp-share-copy data-tp-url="https://topersia.com/isfahan-province/"' )
	&& false !== strpos( $bar, 'data-tp-share-more hidden' )
	&& false !== strpos( $src_js, 'if ( more && navigator.share && coarse ) {' ) );

t( 'دنبال‌کردن: دکمه است نه پیوند RSS دیدگاه‌ها  P5-D238',
	false !== strpos( $bar, 'data-tp-follow aria-pressed="false"' ) && false === strpos( $bar, '/feed' )
	&& false === strpos( $src_zones, 'get_post_comments_feed_link' ) );

$GLOBALS['tp_filters']['topersia_g_user_core'] = array( function () { return false; } );
$bar_no = r( 20, 'tp_actionbar' );
unset( $GLOBALS['tp_filters']['topersia_g_user_core'] );
t( 'ذخیره و دنبال: بی user-core دکمه‌ای نیست — سامانهٔ موازی نه',
	false === strpos( $bar_no, 'data-tp-save' ) && false === strpos( $bar_no, 'data-tp-follow' ) && false !== strpos( $bar_no, 'data-tp-share' ) );

t( 'ذخیره و دنبال: API user-core؛ مهمان ← پنجرهٔ ورود؛ وضعیت واردشده پس از بارگذاری',
	false !== strpos( $src_js, "call( on ? 'topersia_remove_saved_place' : 'topersia_save_place'" )
	&& false !== strpos( $src_js, "call( 'topersia_follow_entity', { post_id: post, post_type: ptype, mode: on ? 'unfollow' : 'follow' } )" )
	&& false !== strpos( $src_js, "call( 'topersia_entity_state'" )
	&& false !== strpos( $src_js, "needLogin( 'save' );" ) && false !== strpos( $src_js, "needLogin( 'follow' );" )
	&& false !== strpos( $bar, 'data-tp-post="20" data-tp-type="tp_province"' ) );

/* ── بند ۱۲ — نوار پایین موبایل ─────────────────────────────────── */

$css430 = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' );
t( 'نوار پایین: بالای صفحه پنهان، با نخستین اسکرولِ بیش از 24px پیدا و ماندگار  ۴٫۳٫۰ بند ۱۲',
	false !== strpos( $css430, 'html.tp-js .tp-page .tp-mnav{transform:translateY(calc(100% + 8px));opacity:0;visibility:hidden;' )   /* ۲ه٫۱: transform و opacity */
	&& false !== strpos( $css430, 'html.tp-js .tp-page .tp-mnav.tp-mnav--on{transform:none;opacity:1;visibility:visible;' )
	&& (bool) preg_match( "/> 24 \) \{\s*mnav\.classList\.add\( 'tp-mnav--on' \);\s*window\.removeEventListener\( 'scroll', onScroll \);/", $src_js )
	&& false !== strpos( file_get_contents( TP_PLUGIN_DIR . 'topersia-render.php' ), "document.documentElement.classList.add('tp-js')" ) );

/* ── بند ۱۳ — قلم‌ها ─────────────────────────────────────────────── */

t( 'قلم: قالب هیچ @font-face و @import گوگل ندارد — فقط توکن‌ها با content_url  ۴٫۳٫۰ بند ۱۳',
	false === strpos( $css430, '@font-face' ) && false === strpos( $css430, '@import' )
	&& false === strpos( $css430, 'fonts.googleapis' ) && false === strpos( $css430, 'topersia.com/assets' )
	&& 2 === substr_count( file_get_contents( TP_PLUGIN_DIR . 'topersia-render.php' ), 'esc_url( topersia_render_font_url( $file ) )' ) );

/* ── بند ۱ — نقشه  P5-D241 · P5-D195 ───────────────────────────── */

$map_plain = r( 20, 'tp_map' );
t( 'نقشه: بی پروندهٔ کاشی، فقط همان SVG مرجع — نه لایهٔ زنده نه پیکربندی',
	false !== strpos( $map_plain, '<svg class="tp-map-svg"' ) && false === strpos( $map_plain, 'data-tp-map-cfg' ) && false === strpos( $map_plain, 'data-tp-map-live' ) );

$GLOBALS['tp_filters']['topersia_g_map_provider'] = array( function () {
	return array( 'name' => 'maplibre-pmtiles', 'base' => 'https://topersia.com/assets/plugins/topersia-render/assets/map/', 'tiles' => 'https://topersia.com/assets/uploads/tiles/iran.pmtiles', 'attribution' => '© OpenStreetMap' );
} );
$map_live = r( 20, 'tp_map' );
$mod_live = topersia_g_modals( 20 );
unset( $GLOBALS['tp_filters']['topersia_g_map_provider'] );

t( 'نقشه: با پیکربندی، لایهٔ زنده روی همان SVG — زبان صفحه، بزرگنمایی نوع، کاشی خودمیزبان',
	(bool) preg_match( '#<div class="tp-map-inner" data-tp-map data-tp-coords="32\.6539, 51\.6660" data-tp-map-cfg=\'[^\']*&quot;lang&quot;:&quot;fa&quot;[^\']*\'><div class="tp-map-live" data-tp-map-live aria-hidden="true"></div><svg class="tp-map-svg"#', $map_live )
	&& false !== strpos( $map_live, 'iran.pmtiles' ) && false !== strpos( $map_live, '&quot;zoom&quot;:6' ) );

t( 'نقشه: مودال ظرف تعاملیِ خودش را دارد — بار فقط با کلیک',
	false !== strpos( $mod_live, '<div class="tp-map-live tp-map-live--full" data-tp-map-full data-tp-coords="32.6539, 51.6660"' )
	&& (bool) preg_match( "/closest\( '\[data-tp-map-open\]' \)/", $src_js ) && false !== strpos( $src_js, 'TopersiaMap.mount( full, cfg, ll, true )' ) );

t( 'نقشه: MapLibre نه در head — فقط در load() آداپتور، کارت با IntersectionObserver',
	(bool) preg_match( "/ready = Promise\.all\( \[ css\( b \+ 'maplibre-gl\.css' \), js\( b \+ 'maplibre-gl\.js' \), js\( b \+ 'pmtiles\.js' \), js\( b \+ 'basemaps\.js' \) \] \)/", $src_js )
	&& false !== strpos( $src_js, 'new IntersectionObserver(' ) && false !== strpos( $src_js, "TopersiaMap.mount( box, cfg, ll, false )" )
	&& false === strpos( file_get_contents( TP_PLUGIN_DIR . 'topersia-render.php' ), 'maplibre' ) );

t( 'نقشه: برچسب به زبان صفحه با fallback به name، متن راست‌به‌چپ، انتساب «© OpenStreetMap»',
	false !== strpos( $src_js, "window.basemaps.layers( 'protomaps', flavor, { lang: lang } )" ) && false !== strpos( $src_js, 'var lay  = mapLayers( lang );' )
	&& false !== strpos( $src_js, "ml.setRTLTextPlugin( b + 'mapbox-gl-rtl-text.js', true )" )
	&& false !== strpos( $src_js, "url: 'pmtiles://' + cfg.tiles" ) && false !== strpos( $src_js, "'© OpenStreetMap'" ) );

$map_dir = TP_PLUGIN_DIR . 'assets/map/';
$pbf     = glob( $map_dir . 'fonts/*/*.pbf' );
t( 'نقشه: خودمیزبان — کتابخانه، قلم‌ها (با بازهٔ عربی)، اسپرایت و مجوزها در افزونه؛ هیچ CDN',
	is_file( $map_dir . 'maplibre-gl.js' ) && is_file( $map_dir . 'maplibre-gl.css' ) && is_file( $map_dir . 'pmtiles.js' )
	&& is_file( $map_dir . 'basemaps.js' ) && is_file( $map_dir . 'mapbox-gl-rtl-text.js' ) && is_file( $map_dir . 'sprites/light.json' )
	&& 45 === count( $pbf ) && is_file( $map_dir . 'fonts/Noto Sans Regular/1536-1791.pbf' )
	&& is_file( $map_dir . 'LICENSE-maplibre-gl.txt' ) && is_file( $map_dir . 'LICENSE-pmtiles.txt' )
	&& ! preg_match( '/unpkg\.com|jsdelivr|cdnjs|api\.maptiler|tile\.openstreetmap/', $src_js ) );

/* ── بند ۶ — خبر  CHANGE-PACKAGE-001 بند ۷ ─────────────────────── */

$news_src = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-news.php' );

t( 'خبر: بی tpc_news هیچ بخشی — روی سایت زنده بی‌اثر',
	'' === r( 20, 'tp_news' ) && false !== strpos( $news_src, "post_type_exists( 'tpc_news' )" ) );

t( 'خبر: تجمیع رو به بالا — شهر ← شهرستان (۷۳) ← استان (۷۲)',
	array( 22, 20 ) === topersia_g_news_ancestors_of( 27 ) );

foreach ( array( 901 => '2026-09-08', 902 => '2026-08-09', 903 => '2026-08-08', 904 => '2026-06-17' ) as $nid => $nd ) {
	$GLOBALS['tp_posts'][ $nid ] = array( 'type' => 'tpc_news', 'title' => 'خبر ' . $nid, 'url' => 'https://topersia.com/news/n-' . $nid . '/' );
	$GLOBALS['tp_meta'][ $nid ]  = array( 'short_summary' => 'خلاصهٔ کوتاه ' . $nid );
}
$GLOBALS['tp_filters']['topersia_g_news_pre_query'] = array( function ( $pre, $place, $limit, $months ) {
	return 20 === (int) $place && 4 === (int) $limit && 12 === (int) $months ? array( 'ids' => array( 901, 902, 903, 904 ), 'total' => 4 ) : $pre;
} );
$news20 = r( 20, 'tp_news' );
unset( $GLOBALS['tp_filters']['topersia_g_news_pre_query'] );

t( 'خبر: #news عیناً مرجع — ۴ خبر ۱۲ ماه اخیر و «فهرست اخبار استان اصفهان»  بند ۷٫۷',
	0 === strpos( $news20, '<section id="tp-news" class="tp-section"><div class="tp-section-head"><div><h2>اخبار استان</h2>' )
	&& 4 === substr_count( $news20, '<a href="https://topersia.com/news/n-' )
	&& false !== strpos( $news20, '<div class="tp-news-more"><a href="https://topersia.com/isfahan-province/news/">فهرست اخبار استان اصفهان <svg' ) );

t( 'خبر: جای «NEWS» لاتین، تصویر شاخص یا آیکون  P5-D240',
	false === strpos( $news20, 'NEWS' ) && 4 === substr_count( $news20, '<div class="tp-thumb"><svg' ) );

t( 'خبر: _tp_news_ancestor یک ردیف برای هر نیا، هنگام ذخیره و تغییر رابطه — WPML ignore',
	false !== strpos( $news_src, "add_post_meta( \$news_id, '_tp_news_ancestor', \$one );" )
	&& false !== strpos( $news_src, "add_action( 'save_post_tpc_news'," )
	&& false !== strpos( $news_src, "add_action( 'jet-engine/relation/update/after', \$topersia_news_rel_changed, 10, 4 );" )
	&& false !== strpos( $news_src, "add_action( 'jet-engine/relation/delete/after', \$topersia_news_rel_changed, 10, 4 );" ) );

t( 'خبر: /{rewrite}/{slug}/news/ و /page/N/ پیش از قاعدهٔ نوع؛ زیر ۳ خبر noindex؛ canonical خودِ صفحه  بند ۷٫۸',
	false !== strpos( $news_src, "'^' . preg_quote( \$slug, '#' ) . '/([^/]+)/news(?:/page/([0-9]{1,}))?/?$'," )
	&& false !== strpos( $news_src, "'top'" ) && false !== strpos( $news_src, "return \$q['total'] < 3;" )
	&& false !== strpos( $news_src, "add_filter( 'rank_math/frontend/robots'" ) && false !== strpos( $news_src, "add_filter( 'get_canonical_url'" )
	&& false !== strpos( $news_src, "add_filter( 'redirect_canonical'" ) && is_file( TP_PLUGIN_DIR . 'templates/news-list.php' ) );

/* ── بند ۷ — نظرها  P5-D234 ───────────────────────────────────────── */

if ( ! class_exists( 'ToPersia_Review_Form' ) ) {
	class ToPersia_Review_Form {
		public static function criteria( $type ) {
			return 'tp_province' === $type ? array( 'جاذبه‌ها', 'دسترسی', 'اقامت و خدمات', 'مهمان‌نوازی' ) : array();
		}
		public static function user_has_review( $u, $p ) { return ! empty( $GLOBALS['tp_reviewed'] ); }
	}
}

$GLOBALS['tp_user'] = 0;
$rev_full = r( 20, 'tp_reviews' );
$rev_keep = isset( $GLOBALS['tp_filters']['topersia_g_reviews_data'] ) ? $GLOBALS['tp_filters']['topersia_g_reviews_data'] : null;
$GLOBALS['tp_filters']['topersia_g_reviews_data'] = array( function () {
	return array( 'avg' => 0.0, 'count' => 0, 'dist' => array( 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0 ), 'last' => array() );
} );
$rev_g = r( 20, 'tp_reviews' );
t( 'نظر: با نظر — همان جعبهٔ مرجع، و دعوت به ورود زیرش',
	false !== strpos( $rev_full, '<div class="tp-rate__score">' ) && false !== strpos( $rev_full, '</div><p class="tp-review-invite">' ) );
t( 'نظر: بی نظر — عنوان، «نخستین نظر را شما بنویسید»، دعوت به ورود برای مهمان',
	0 === strpos( $rev_g, '<section id="tp-reviews" class="tp-section"><div class="tp-section-head"><div><h2>نظر بازدیدکنندگان</h2></div></div>' )
	&& false !== strpos( $rev_g, '<p class="tp-rate__first">نخستین نظر را شما بنویسید</p>' )
	&& false !== strpos( $rev_g, 'data-tp-login="review"' ) && false !== strpos( $rev_g, 'redirect_to=' . rawurlencode( 'https://topersia.com/isfahan-province/' ) )
	&& false === strpos( $rev_g, '<form' ) );

$GLOBALS['tp_user'] = 7;
$rev_u = r( 20, 'tp_reviews' );
$GLOBALS['tp_reviewed'] = true;
$rev_done = r( 20, 'tp_reviews' );
$GLOBALS['tp_reviewed'] = false;
$GLOBALS['tp_user'] = 0;
if ( null === $rev_keep ) {
	unset( $GLOBALS['tp_filters']['topersia_g_reviews_data'] );
} else {
	$GLOBALS['tp_filters']['topersia_g_reviews_data'] = $rev_keep;
}
t( 'نظر: واردشده — فرم با معیارهای خانواده (۴ × ۵)، متن، ارسال؛ اگر پیش‌تر نوشته، پیام',
	false !== strpos( $rev_u, '<form class="tp-review-form" data-tp-review-form data-tp-post="20" novalidate>' )
	&& 4 === substr_count( $rev_u, 'class="tp-review-form__row" role="radiogroup"' ) && 20 === substr_count( $rev_u, 'type="radio" name="scores[' )
	&& false !== strpos( $rev_u, 'aria-label="مهمان‌نوازی"' ) && false !== strpos( $rev_u, '<textarea name="content"' )
	&& false === strpos( $rev_done, '<form' ) && false !== strpos( $rev_done, 'پس از تأیید مدیر نمایش داده می‌شود' ) );

t( 'نظر: نوع بی‌نظر (شهرستان) بخش ندارد',
	'' === r( 22, 'tp_reviews' ) );

$GLOBALS['tp_filters']['wpml_default_language'] = array( function () { return 'fa'; } );
$GLOBALS['tp_filters']['wpml_object_id']        = array( function ( $id ) { return 21 === (int) $id ? 20 : $id; } );
$canon = topersia_g_canonical_id( 21 );
unset( $GLOBALS['tp_filters']['wpml_default_language'], $GLOBALS['tp_filters']['wpml_object_id'] );
t( 'نظر: خواندن و نوشتن به شناسهٔ زبان اصلی WPML',
	20 === $canon && false !== strpos( $src_zones, '$data  = topersia_g_reviews_data( topersia_g_canonical_id( $pid ) );' ) );

t( 'نظر: بی AggregateRating در خروجی؛ ارسال از API user-core',
	false === stripos( r( 20, 'tp_page' ), 'AggregateRating' ) && false !== strpos( $src_js, "c.call( 'topersia_submit_review'" ) );

/* ── نسخه — دو افزونه، سرِ پرونده و ثابت یکی ───────────────────────── */

$main_r = file_get_contents( TP_PLUGIN_DIR . 'topersia-render.php' );
$main_u = file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-user-core/topersia-user-core.php' );
preg_match( '/^ \* Version:\s*(\S+)/m', $main_r, $ver_r );
preg_match( "/define\( 'TOPERSIA_RENDER_VERSION', '([^']+)' \)/", $main_r, $const_r );
preg_match( '/^ \* Version:\s*(\S+)/m', $main_u, $ver_u );
preg_match( "/define\('TOPERSIA_USER_CORE_VERSION', '([^']+)'\)/", $main_u, $const_u );
/* شمارهٔ خودِ نسخه از ۴٫۴٫۰ در assert-440.php است؛ این‌جا فقط یکی‌بودن. */
t( 'نسخه: topersia-render و topersia-user-core — سرِ پرونده و ثابت یکی',
	isset( $ver_r[1], $const_r[1], $ver_u[1], $const_u[1] )
	&& $ver_r[1] === $const_r[1] && $ver_u[1] === $const_u[1] );
