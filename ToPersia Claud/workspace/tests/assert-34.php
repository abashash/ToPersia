<?php
/**
 * دستهٔ 34 — هیرو و تکرار (P5-D458 · P5-D464). از assert.php بار می‌شود؛ PHP خالص با stub.
 *
 * بند 1: هیرو فقط برچسب نوع، عنوان و حداکثر یک خط هم‌اندازهٔ کاشی؛ سقف یک ثابت قالب (TOPERSIA_HERO_LINE_MAX).
 * بند 2: هر مقدارِ خانه‌های سربرگ در کاشی‌های ستون چپ تکرار نمی‌شود (topersia_g_stat_claims).
 * رفتار روی صفحهٔ واقعی: tests/dedupe-local.php.
 */

echo "\nدستهٔ 34 — هیرو و تکرار  P5-D458 · P5-D464\n" . str_repeat( '─', 78 ) . "\n";

/* آزمون‌های سازوکار کاشیِ پیشین (assert.php و …) بی تکرارزدایی نوشته شده‌اند؛ از این‌جا قاعدهٔ P5-D464 روشن است */
unset( $GLOBALS['tp_filters']['topersia_g_stat_claims'] );

$z34 = (string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );
$r34 = (string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-relreg.php' );

/* ── بند 1: سقف و نام‌ها ── */
t( 'هیرو: سقف خط کوتاه یک ثابت است (۴۰، هم‌اندازهٔ مقدار کاشی)؛ هیچ عدد خام در تابع هیرو',
	defined( 'TOPERSIA_HERO_LINE_MAX' ) && TOPERSIA_HERO_LINE_MAX === 40 && false !== strpos( $z34, "define( 'TOPERSIA_HERO_LINE_MAX', 40 );" )
	&& 1 === preg_match( '/function topersia_g_sc_hero\(.*?\n\t}\n}/s', preg_replace( '#/\*.*?\*/#s', '', $z34 ), $h34 ) && false === strpos( $h34[0], 'short_summary' ) && false === strpos( $h34[0], 'tp-altnames' ) && false === strpos( $h34[0], 'tp-hero__lead' ) );

/* نام‌ها: بخش هیرو تا سقف، بقیه برای «درباره»؛ هر نام فقط در یکی */
$GLOBALS['tp_posts'][9401] = array( 'type' => 'tp_city', 'title' => 'شهر آزمون', 'url' => 'https://topersia.com/city/t/' );
$GLOBALS['tp_meta'][9401]  = array( 'name_fa' => 'شهر آزمون', 'name_en' => 'Test City', 'name_ar' => 'مدينة الاختبار', 'name_local' => 'نام محلی بلندِ بسیار بلند آزمون', 'alternative_names' => "نام یک\nنام دو" );
$hn_all = topersia_g_hero_names( 9401 );
$plain  = function ( $a ) { return array_map( function ( $x ) { return trim( wp_strip_all_tags( $x ) ); }, $a ); };
$len_ok = function ( $hn ) use ( $plain ) { return mb_strlen( implode( '، ', $plain( $hn['hero'] ) ) ) <= TOPERSIA_HERO_LINE_MAX && ! array_intersect( $plain( $hn['hero'] ), $plain( $hn['rest'] ) ); };
$GLOBALS['tp_meta'][9401]['name_local'] = 'کوتاه';
$hn_short = topersia_g_hero_names( 9401 );
t( 'نام‌ها: خط هیرو از سقف بلندتر نمی‌شود و هیچ نام در هر دو بخش نیست؛ نام‌های بلند به «درباره» می‌روند (ترتیب: رسمی، محلی، دیگر)',
	$len_ok( $hn_all ) && $len_ok( $hn_short ) && 1 <= count( $hn_all['rest'] ) && count( $hn_all['hero'] ) >= 1
	&& count( $hn_all['hero'] ) + count( $hn_all['rest'] ) === count( $hn_short['hero'] ) + count( $hn_short['rest'] ) );
unset( $GLOBALS['tp_posts'][9401], $GLOBALS['tp_meta'][9401] );

/* ── بند 2: تکرار ── */
$claims_ok = function ( $src ) {
	preg_match( '/function topersia_g_stat_claims\(.*?\n\t}\n}/s', $src, $c );
	return isset( $c[0] ) && false !== strpos( $c[0], 'topersia_g_stat_plan( $pid )' ) && false !== strpos( $c[0], 'null === topersia_g_stat_cell( $pid, $cell )' )
		&& false !== strpos( $c[0], "preg_match( '/^(?:num|text|rows|domain|list):([a-z0-9_]+)/', \$cell, \$m )" );
};
$use_ok = function ( $z, $r ) {
	return false !== strpos( $r, "topersia_g_stat_claims( \$pid )[ 'parent:' . substr( (string) get_post_type( \$id ), 3 ) ]" )   /* کاشی والد مکانی */
		&& false !== strpos( $r, "empty( topersia_g_stat_claims( \$pid )['capital'] )" )                                              /* مرکز استان */
		&& false !== strpos( $z, "empty( topersia_g_stat_claims( \$pid )['distance:capital'] )" )                                    /* فاصله تا مرکز استان */
		&& false !== strpos( $z, "! empty( topersia_g_stat_claims( \$pid )[ 'parent:' . substr( \$t, 3 ) ] )" )                      /* والد زنجیره در پرکردن کارت */
		&& false !== strpos( $z, "\$travel_skip = array_keys( topersia_g_stat_claims( \$pid ) );" );        /* فیلد اطلاعات سفر */
};
t( 'تکرار (P5-D464): هر مقدار خانه‌های سربرگ (والد مکانی · مرکز استان · فاصله تا مرکز · فیلد اطلاعات سفر) از کاشی‌های ستون چپ برداشته می‌شود — جهش: هر مصرف‌کننده بی نگهبان می‌افتد',
	$claims_ok( $z34 ) && $use_ok( $z34, $r34 )
	&& ! $use_ok( str_replace( "empty( topersia_g_stat_claims( \$pid )['distance:capital'] )", 'true', $z34 ), $r34 )
	&& ! $use_ok( $z34, str_replace( "empty( topersia_g_stat_claims( \$pid )['capital'] )", 'true', $r34 ) )
	&& ! $use_ok( $z34, str_replace( "! empty( topersia_g_stat_claims( \$pid )[ 'parent:' . substr( (string) get_post_type( \$id ), 3 ) ] )", 'false', str_replace( "topersia_g_stat_claims( \$pid )[ 'parent:' . substr( (string) get_post_type( \$id ), 3 ) ]", 'x', $r34 ) ) )
	&& ! $use_ok( str_replace( "\$travel_skip = array_keys( topersia_g_stat_claims( \$pid ) );", "\$travel_skip = array();", $z34 ), $r34 )
	&& ! $claims_ok( str_replace( 'null === topersia_g_stat_cell( $pid, $cell )', 'false', $z34 ) ) );

/* ادعای رفتاری با stub: خانهٔ دارای مقدار ← claim؛ خالی ← نه */
$GLOBALS['tp_posts'][9402] = array( 'type' => 'tp_village', 'title' => 'روستای آزمون', 'url' => 'https://topersia.com/village/t/' );
$GLOBALS['tp_meta'][9402]  = array( 'population' => 1200, 'elevation_m' => 1500 );
$cl_full = topersia_g_stat_claims( 9402 );
$GLOBALS['tp_meta'][9402]  = array( 'population' => 1200 );
$cl_part = topersia_g_stat_claims( 9402 );
tp441_mut( 'تکرار: خانه با مقدار ← کلید فیلد در claim (جمعیت · ارتفاع)؛ خانهٔ بی‌مقدار هیچ claim نمی‌سازد',
	function ( $x ) { return ! empty( $x[0]['population'] ) && ! empty( $x[0]['elevation_m'] ) && ! empty( $x[1]['population'] ) && empty( $x[1]['elevation_m'] ); },
	array( $cl_full, $cl_part ), array( array( 'population' => true ), $cl_part ) );
unset( $GLOBALS['tp_posts'][9402], $GLOBALS['tp_meta'][9402] );

/* ── بند 3: غذای محلی ── */
$GLOBALS['tp_posts'][9403] = array( 'type' => 'tp_local_food', 'title' => 'غذای آزمون', 'url' => 'https://topersia.com/food/t/' );
$cells34 = topersia_g_stat_cells();
$food_ok = function ( $x ) {
	return 'خاستگاه' === $x[0] && 'مواد اصلی' === $x[1] && 'اطلاعات جغرافیایی' === $x[2] && 'اطلاعات سفر' === $x[3]
		&& ! in_array( 'text:ritual_context_reference', array_column( $x[4], 'key' ), true ) && ! in_array( 'text:recipe_content', array_column( $x[4], 'key' ), true ) && 4 === count( $x[4] )   /* هیچ متن بلند (دستور پخت، کاربرد آیینی) در سربرگ */
		&& array( 'classification_', 'text:main_ingredients', 'origin', 'dietary_flags' ) === array_column( $x[4], 'key' )
		&& isset( $x[5]['ritual_context_reference'] ) && 'details' === $x[5]['ritual_context_reference']['zone'];
};
$food_claim = array(
	topersia_g_card_title( 9403, 'tp-geo', 'اطلاعات جغرافیایی' ), topersia_g_card_title( 9403, 'tp-practical', 'اطلاعات سفر' ),
	topersia_g_card_title( 9401, 'tp-geo', 'اطلاعات جغرافیایی' ), topersia_g_card_title( 9401, 'tp-practical', 'اطلاعات سفر' ),
	$cells34['tp_local_food'], topersia_g_map(),
);
$GLOBALS['tp_posts'][9401] = array( 'type' => 'tp_city', 'title' => 'شهر', 'url' => 'https://topersia.com/c/' );
$food_claim[2] = topersia_g_card_title( 9401, 'tp-geo', 'اطلاعات جغرافیایی' );
$food_claim[3] = topersia_g_card_title( 9401, 'tp-practical', 'اطلاعات سفر' );
t( 'غذای محلی (P5-D467): عنوان کارت‌ها «خاستگاه» و «مواد اصلی»، دیگر نوع‌ها بی تغییر؛ «کاربرد آیینی» از چهار خانهٔ سربرگ بیرون و در «درباره» (FIELDMAP details)؛ همچنان چهار خانه — جهش: خانهٔ آیینی برگردد می‌افتد',
	$food_ok( $food_claim ) && ! $food_ok( array( $food_claim[0], $food_claim[1], $food_claim[2], $food_claim[3], array_merge( array_slice( $food_claim[4], 0, 3 ), array( array( 'key' => 'text:ritual_context_reference', 'req' => false ) ) ), $food_claim[5] ) ) );

/* ── بند 4: بنا — ساعت و بلیت جدول دوستونهٔ معنایی ── */
$GLOBALS['tp_posts'][9404] = array( 'type' => 'tp_monument', 'title' => 'بنای آزمون', 'url' => 'https://topersia.com/m/t/' );
$GLOBALS['tp_meta'][9404]  = array( 'opening_hours_spec' => array( 'item-0' => array( 'oh_day' => 'saturday', 'oh_from' => '09:00', 'oh_to' => '17:00', 'oh_closed' => 'false' ) ) );
$hours34 = topersia_g_hours_html( 9404 );
$tbl_ok  = function ( $h ) { return false !== strpos( $h, '<table class="tp-hours"' ) && false !== strpos( $h, '<caption class="tp-vh">' ) && 1 === preg_match( '#<tr><th scope="row">[^<]+</th><td>#u', $h ); };
$css34 = (string) file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' );
tp441_mut( 'بنا (P5-D458): ساعت بازدید جدول دوستونهٔ معنایی (caption، th scope=row | td)؛ CSS: ستون ثابت، شکستن متن، بی سرریز موبایل — جهش: th بی scope یا بی table-layout می‌افتد',
	function ( $x ) use ( $tbl_ok ) { return $tbl_ok( $x[0] ) && false !== strpos( $x[1], 'html body .tp-page .tp-hours{table-layout:fixed;max-inline-size:100%}' ) && false !== strpos( $x[1], 'overflow-wrap:anywhere;vertical-align:top' ); },
	array( $hours34, $css34 ), array( str_replace( ' scope="row"', '', $hours34 ), str_replace( 'table-layout:fixed', 'table-layout:auto', $css34 ) ) );

/* ── بند 5: محله‌ها در باکس چپ، کاشی ── */
$rz34 = topersia_g_rel_zones();
tp441_mut( 'شهر (P5-D458): محله‌ها ناحیهٔ ستون چپ و شبکهٔ کاشی (relgrid) است، نه اسلایدر ستون اصلی — جهش: به اسلایدر برگردد می‌افتد',
	function ( $x ) { return 'rail' === $x['districts'][0] && 'topersia_g_sc_relgrid' === $x['districts'][1] && 'tp-districts' === $x['districts'][2]; },
	$rz34, array_merge( $rz34, array( 'districts' => array( 'main', 'topersia_g_sc_slider', 'tp-districts', 'محله‌ها' ) ) ) );

/* ── بند 6: استان — منطقهٔ آزاد زیر اطلاعات جغرافیایی ── */
$GLOBALS['tp_posts'][9405] = array( 'type' => 'tp_province', 'title' => 'استان آزمون', 'url' => 'https://topersia.com/p/t/' );
$GLOBALS['tp_meta'][9405]  = array( 'free_zone' => 'true' );
$geo34 = r( 9405, 'tp_facts' );
tp441_mut( 'استان (P5-D458): «منطقهٔ آزاد» کاشی «اطلاعات جغرافیایی» است؛ در «اطلاعات سفر» (کد) برای استان کنار گذاشته می‌شود — جهش: نگهبان برداشته شود می‌افتد',
	function ( $x ) { return false !== strpos( $x[0], 'منطقۀ آزاد' ) && false !== strpos( $x[1], "\$travel_skip[] = 'free_zone';" ); },
	array( $geo34, $z34 ), array( $geo34, str_replace( "\$travel_skip[] = 'free_zone';", '', $z34 ) ) );

/* ── بند 7: کشور — واحد پول فقط نام ── */
$GLOBALS['tp_posts'][9406] = array( 'type' => 'tp_country', 'title' => 'کشور آزمون', 'url' => 'https://topersia.com/c/t/' );
$GLOBALS['tp_meta'][9406]  = array( 'currency_note' => 'واحد پول رسمی ایران ریال (IRR) است. «تومان» نیز رایج است.', 'currency_symbol' => '﷼' );
$cu_name = topersia_g_stat_derived( 9406, 'currency:unit' );
$GLOBALS['tp_meta'][9406]['currency_note'] = 'یادداشت بی کد ارز و بی نام.';
$cu_none = topersia_g_stat_derived( 9406, 'currency:unit' );
$cu_ok   = function ( $x ) { return is_array( $x[0] ) && 'ریال' === wp_strip_all_tags( $x[0][1] ) && false === strpos( $x[0][1], 'IRR' ) && false === strpos( $x[0][1], '﷼' ) && null === $x[1] && in_array( 'currency:unit', array_column( topersia_g_stat_cells()['tp_country'], 'key' ), true ); };
t( 'کشور (P5-D458): خانهٔ «واحد پول» فقط نام («ریال»)، نه نماد و کد؛ نبود ← هیچ — جهش: کد ISO در مقدار بیاید می‌افتد',
	$cu_ok( array( $cu_name, $cu_none ) ) && ! $cu_ok( array( array( 'x', 'ریال (IRR)' ), null ) ) );
unset( $GLOBALS['tp_posts'][9401], $GLOBALS['tp_posts'][9403], $GLOBALS['tp_posts'][9404], $GLOBALS['tp_posts'][9405], $GLOBALS['tp_posts'][9406], $GLOBALS['tp_meta'][9404], $GLOBALS['tp_meta'][9405], $GLOBALS['tp_meta'][9406] );

/* ── چهار خانهٔ سربرگ همیشه پر (P5-D464) ── */
$GLOBALS['tp_posts'][9407] = array( 'type' => 'tp_village', 'title' => 'روستای پر', 'url' => 'https://topersia.com/village/f/' );
$GLOBALS['tp_meta'][9407]  = array( 'population' => 1200, 'elevation_m' => 1500, 'area_km2' => 3 );
$plan_full = topersia_g_stat_plan( 9407 );                              /* parent:province و distance:capital مقدار ندارند ← نامزد */
$GLOBALS['tp_meta'][9407]  = array( 'population' => 1200, 'elevation_m' => 1500, 'area_km2' => 3, 'water_body_type' => 'x', 'island_type' => 'y' );
$GLOBALS['tp_meta'][9407]  = array();
$plan_none = topersia_g_stat_plan( 9407 );                              /* هیچ نامزدی ← همان کلیدهای اصلی («—») */
$GLOBALS['tp_meta'][9407]  = array( 'population' => 1, 'elevation_m' => 2 );
$plan_two  = topersia_g_stat_plan( 9407 );                              /* نامزد هم ندارد (area_km2 خالی) */
$fill_ok = function ( $x ) {
	return 4 === count( $x[0] ) && ! in_array( 'parent:province', $x[0], true ) && in_array( 'population', $x[0], true ) && in_array( 'elevation_m', $x[0], true ) && in_array( 'area_km2', $x[0], true )   /* جای خالی ← نامزد پر */
		&& array( 'parent:province', 'distance:capital', 'population', 'elevation_m' ) === $x[1]                                                                                                       /* بی نامزد: همان اصلی‌ها */
		&& array( 'parent:province', 'distance:capital', 'population', 'elevation_m' ) === $x[2];
};
$fill_src = function ( $z ) { return false !== strpos( $z, "return topersia_g_stat_fill( \$post_id, array_column( \$cells[ \$ptype ], 'key' ) );" ) && false !== strpos( $z, "'flag' !== \$f['kind'] && ! in_array( \$name, array( 'best_season', 'province_code', 'geo_coordinates' ), true )" ); };
t( 'سربرگ (P5-D464): خانهٔ اصلیِ بی‌مقدار از نامزدِ واقعیِ همان پست پر می‌شود (جای خانه‌های پر ثابت)؛ بی نامزد «—»؛ نامزد پرچم/بهترین فصل/کد نیست — جهش: بی پر کردن می‌افتد',
	$fill_ok( array( $plan_full, $plan_none, $plan_two ) ) && $fill_src( $z34 )
	&& ! $fill_ok( array( array( 'parent:province', 'distance:capital', 'population', 'elevation_m' ), $plan_none, $plan_two ) )
	&& ! $fill_src( str_replace( "return topersia_g_stat_fill( \$post_id, array_column( \$cells[ \$ptype ], 'key' ) );", "return array_column( \$cells[ \$ptype ], 'key' );", $z34 ) ) );
unset( $GLOBALS['tp_posts'][9407], $GLOBALS['tp_meta'][9407] );
