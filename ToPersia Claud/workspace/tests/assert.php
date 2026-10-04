<?php
/** آزمون ادعا — هر قاعدهٔ رندر یک ادعا. */

ob_start();
require __DIR__ . '/run.php';
ob_end_clean();

$pass = 0;
$fail = 0;

/* دامنه‌ای که هر قاعدهٔ g-zones.css زیرش می‌نشیند — P5-D201 */
const TP_SCOPE = '.tp-page';

function t( $label, $ok, $note = '' ) {
	global $pass, $fail;
	if ( $ok ) { $pass ++; printf( "  ✅ %s\n", $label ); }
	else       { $fail ++; printf( "  ❌ %s   %s\n", $label, $note ); }
}

/** یک اسکریپت PHP را با همین مفسر اجرا می‌کند و کد خروجش را می‌دهد. */
function tp_run_php( $args ) {

	$cmd = escapeshellarg( PHP_BINARY ) . ' ' . $args . ' 2>&1';

	exec( $cmd, $out, $code );

	return (int) $code;
}

function r( $pid, $tag, $atts = array() ) {
	$GLOBALS['tp_current'] = $pid;
	return tp_do( $tag, $atts );
}

echo "\nقواعد رندر\n" . str_repeat( '─', 78 ) . "\n";

/* عدد */
t( 'عدد: رقم فارسی + جداکنندهٔ ٬', false !== strpos( r( 20, 'tp_stats' ), '۵٬۱۲۰٬۸۵۰' ) );
/* ۲ج٫۱: خانۀ آمار نماد مرجع را می‌گیرد («۱۰۷٬۰۱۸ km²»)، کارت حقایق و چیپ‌ها واحد کامل — هر دو از نقشه */
t( 'عدد: واحد از نقشه می‌آید، نه از عنوان JetEngine — آمار با نماد مرجع km²، حقایق با واحد کامل  P5-D282', false !== strpos( r( 20, 'tp_stats' ), '<i class="tp-u">km²</i>' ) && false === strpos( r( 20, 'tp_stats' ), 'کیلومتر مربع' ) && false !== strpos( topersia_g_value( 'area_km2', '107018' ), 'کیلومتر مربع' ) );
t( 'عدد اعشاری: بخش اعشار جدا نمی‌شود', false !== strpos( r( 40, 'tp_stats' ), '۷۰.۷' ) );

/* سال */
$badges_lut = r( 40, 'tp_badges' );
t( 'سال: بدون جداکننده (۲۰۱۶ نه ۲٬۰۱۶)', false !== strpos( $badges_lut, '۲۰۱۶' ) && false === strpos( $badges_lut, '۲٬۰۱۶' ) );
t( 'نشان با سالش جفت می‌شود', false !== strpos( $badges_lut, 'میراث جهانی' ) && false !== strpos( $badges_lut, '۱۳۸۵' ) );
t( 'سوییچر نادرست نشان نمی‌سازد', false === strpos( $badges_lut, 'ذخیره‌گاه زیست‌کره' ) );
/* کوه کرکس هیچ فیلد نشانی ندارد — نه روشن، نه خاموش. */
t( 'موجودیت بدون سوییچر روشن: بخش نشان‌ها کامل ناپدید', '' === r( 24, 'tp_badges' ) );

/* لاتین */
/* کد فرودگاه از ۲ج در «اطلاعات سفر» است، نه کارت حقایق — P5-D282 */
t( 'لاتین: مونو و چپ‌به‌راست', false !== strpos( r( 30, 'tp_relgrid', array( 'rel' => 'practical' ) ), '<span class="tp-lat" dir="ltr">SYZ</span>' ) );

/* واژه‌نامه — P5-D169 */
$facts_lut = r( 40, 'tp_facts' );
$lut_page = r( 40, 'tp_stats' ) . $facts_lut;
t( 'واژه‌نامه: «نمکی» چاپ می‌شود نه salt', false !== strpos( $lut_page, 'نمکی' ) && false === strpos( $lut_page, '>salt<' ) );
t( 'واژه‌نامه: بهترین فصل «زمستان» نه winter', false !== strpos( r( 40, 'tp_seasons' ), 'زمستان' ) );
t( 'واژه‌نامه: وضعیت محتوا «راستی‌آزمایی‌شده» نه verified', false !== strpos( r( 20, 'tp_sources' ), 'راستی‌آزمایی‌شده' ) );

/* ردیف بازنشسته — P5-D121 */
$faq = r( 20, 'tp_faq' );
t( 'ریپیتر: ردیف بازنشسته در پرسش‌ها نمی‌آید', false === strpos( $faq, 'پرسش بازنشسته' ) );
t( 'ریپیتر: ردیف فعال می‌آید', false !== strpos( $faq, 'بهترین زمان سفر به اصفهان' ) );
t( 'ریپیتر: ردیف بدون وضعیت، فعال شمرده می‌شود', false !== strpos( r( 20, 'tp_sources' ), 'یونسکو' ) );
t( 'ریپیتر: صوت بازنشسته نمی‌آید', false === strpos( r( 20, 'tp_audio' ), 'نقش جهان' ) );

/* ناپدیدشدن */
t( 'بخش بدون داده: گالری شهر ناپدید', '' === r( 30, 'tp_gallery' ) );
t( 'بخش بدون داده: پرسش‌های کویر ناپدید', '' === r( 40, 'tp_faq' ) );
t( 'بخش بدون داده: رابطهٔ بی‌نتیجه ناپدید', '' === r( 40, 'tp_relgrid', array( 'rel' => 'nature' ) ) );

/* تک‌فیلد-تک‌جا */
$stats_p = r( 20, 'tp_stats' );
$facts_p = r( 20, 'tp_facts' );
t( 'هر فیلد فقط یک‌جا: جمعیت در آمار هست و در حقایق نیست', false !== strpos( $stats_p, 'جمعیت' ) && false === strpos( $facts_p, 'جمعیت' ) );
/* اقلیم از حقایق به کارت آمار رفت و کد استان جایش را گرفت.  P5-D208 */
/* ترتیب لایهٔ دادهٔ مرجع: مرکز · کد · مساحت · جمعیت.  P5-D215 */
t( 'هیچ فیلدی بی‌صدا نمی‌افتد: کد استان در آمار و اقلیم در حقایق',
	false !== strpos( $stats_p, 'کد استان' ) && false !== strpos( $facts_p, 'نیمه‌خشک' ) );
t( 'مختصات در آمار و حقایق نیست، زیر نقشه است', false === strpos( $stats_p, '32.6539' ) && false === strpos( $facts_p, '32.6539' ) && false !== strpos( r( 20, 'tp_map' ), '32.6539' ) );

/* برچسب — P5-D175 */
t( 'برچسب: geo_boundary دیگر هم‌نام geo_coordinates نیست', 1 === substr_count( $facts_p . r( 20, 'tp_map' ), 'مختصات' ) );

/* جهت رابطه — P5-D170 */
t( 'رابطه: والدِ استان (کشور) در مسیر راهنما می‌آید', false !== strpos( r( 20, 'tp_crumbs' ), 'ایران' ) );
t( 'رابطه: فرزندِ یک‌به‌یک (مرکز استان) در آمار می‌آید', false !== strpos( $stats_p, 'شهر اصفهان' ) );
t( 'رابطه: شهر بدون پیکربندی، مسیر راهنمای کوتاه می‌گیرد', false === strpos( r( 30, 'tp_crumbs' ), 'ایران' ) );

/* دادهٔ زنده */
$wx = r( 20, 'tp_weather' );
t( 'دادهٔ زنده: هوا در HTML کش‌شده مقدار ندارد', false !== strpos( $wx, 'is-load' ) && false === strpos( $wx, '°' ) );
t( 'دادهٔ زنده: وضعیت ذخیره در HTML نمی‌آید', false !== strpos( r( 20, 'tp_actionbar' ), 'aria-pressed="false"' ) );

/* تاریخ */
t( 'تاریخ: Y-m-d به شمسی (۱۲ خرداد ۱۴۰۴)', false !== strpos( r( 20, 'tp_sources' ), '۱۲ خرداد ۱۴۰۴' ), topersia_g_date_fa( '2025-06-02' ) );
t( 'تاریخ: timestamp هم کار می‌کند', '' !== topersia_g_date_fa( '1749081600' ), topersia_g_date_fa( '1749081600' ) );
t( 'تاریخ: مقدار بی‌معنا چیزی نمی‌سازد', '' === topersia_g_date_fa( 'پارسال' ) );

/* امنیت */
$GLOBALS['tp_meta'][40]['provinces_spanning'] = '<script>alert(1)</script>کرمان';
t( 'گریز: تگ تزریق‌شده اجرا نمی‌شود', false === strpos( r( 40, 'tp_facts' ), '<script>' ) );

/* نوع‌محوری */
t( 'یک رندرکننده برای هر سه: هر سه سربرگ ساختند', '' !== r( 20, 'tp_hero' ) && '' !== r( 30, 'tp_hero' ) && '' !== r( 40, 'tp_hero' ) );
t( 'نوع‌محوری: کویر فیلد فرودگاه ندارد و چیزی نمی‌شکند', false === strpos( $facts_lut, 'فرودگاه' ) );
t( 'نوع‌محوری: شهر فیلد فرودگاه دارد و در «اطلاعات سفر» می‌آید، نه کارت حقایق  P5-D279 · P5-D282', false !== strpos( r( 30, 'tp_relgrid', array( 'rel' => 'practical' ) ), 'فرودگاه' ) && false === strpos( r( 30, 'tp_facts' ), 'فرودگاه' ) );

/* آهنگساز صفحه — P5-D183 */
$page = r( 20, 'tp_page' );
t( 'صفحه: یک شورت‌کد کل بدنه را می‌سازد', strlen( $page ) > 3000, strlen( $page ) . ' بایت' );
t( 'صفحه: ستون اصلی و کناری مارک‌آپ خودمان‌اند', false !== strpos( $page, 'class="tp-main"' ) && false !== strpos( $page, 'class="tp-rail"' ) );
/* اخبار نوع پست ندارد (CHANGE-PACKAGE-001) پس رندر نمی‌شود — و پیوندی هم نمی‌گیرد. */
t( 'صفحه: نوار بخش‌ها فقط بخش‌های پرشده را دارد', false !== strpos( $page, 'href="#tp-map"' ) && false === strpos( $page, 'href="#tp-news"' ) );
t( 'صفحه: کویر بدون رابطه، نوار بخش کوتاه‌تری می‌گیرد', substr_count( r( 40, 'tp_page' ), '<a href="#tp-' ) < substr_count( $page, '<a href="#tp-' ) );
t( 'صفحه: سربرگ و آمار داخل همین خروجی‌اند', false !== strpos( $page, 'tp-hero' ) && false !== strpos( $page, 'tp-stats' ) );

/* پوشش نقشه */
/* G همان ۵۷ فیلد است؛ ناحیۀ stat (۲ج٫۱) فیلدهای کوتاه نوع‌های دیگر برای کارت آمار است */
$g_map     = array_filter( topersia_g_map(), function ( $f ) { return 'stat' !== $f['zone']; } );
$map_count = count( $g_map );
t( 'نقشه: دقیقاً ۵۷ فیلد G (بیرون از ناحیۀ stat)', 57 === $map_count, "شمار: {$map_count}" );
$choice = 0;
foreach ( $g_map as $f ) { if ( 'choice' === $f['kind'] ) { $choice ++; } }
t( 'نقشه: سیزده فیلد choice در G', 13 === $choice, "شمار: {$choice}" );

/* ماتریس نوع × ناحیه — هر کلید آمار یا ویژه است (capital · count:) یا فیلد نقشه؛ choice واژه‌نامۀ همان نوع را دارد  P5-D275 */
$mx_bad = array();
foreach ( topersia_g_type_matrix() as $mx_type => $mx_row ) {
	foreach ( isset( $mx_row['stats'] ) ? $mx_row['stats'] : array() as $mx_key ) {
		if ( 'capital' === $mx_key || 0 === strpos( $mx_key, 'count:' ) ) { continue; }
		$mx_f = isset( topersia_g_map()[ $mx_key ] ) ? topersia_g_map()[ $mx_key ] : null;
		if ( ! $mx_f ) { $mx_bad[] = $mx_type . '.' . $mx_key . ' نیست'; continue; }
		if ( 'flag' === $mx_f['kind'] ) { $mx_bad[] = $mx_type . '.' . $mx_key . ' پرچم است'; }
		if ( 'choice' === $mx_f['kind'] && ( empty( $mx_f['glossary'] ) || ( is_array( $mx_f['glossary'] ) && empty( $mx_f['glossary'][ $mx_type ] ) ) ) ) { $mx_bad[] = $mx_type . '.' . $mx_key . ' بی واژه‌نامه'; }
	}
}
t( 'ماتریس: هر کلید آمارِ هر نوع فیلد نقشه است، نه پرچم، و choice واژه‌نامۀ همان نوع را دارد  P5-D275 · P5-D282',
	count( topersia_g_type_matrix() ) >= 41 && ! $mx_bad, implode( ' · ', $mx_bad ) );

/* نقشهٔ رابطه — گام ۱ · تولیدشده با scripts/tp-relmap-gen.php از Jetengine_Skin.json */
$relmap = topersia_g_relmap();
t( 'رابطه: هر دوازده نوع پست نقشه دارند', 12 === count( $relmap ), 'شمار: ' . count( $relmap ) );

/** جهت یک رابطه در گروهی از یک نوع پست. */
function rd( $pt, $group, $rel_id ) {
	$m = topersia_g_relmap();
	if ( empty( $m[ $pt ][ $group ]['rels'] ) ) { return ''; }
	foreach ( $m[ $pt ][ $group ]['rels'] as $spec ) {
		if ( (int) $rel_id === (int) $spec['id'] ) { return $spec['dir']; }
	}
	return '';
}
t( 'رابطه: ۹۶ برای استان children است  P5-D170', 'children' === rd( 'tp_province', 'capital', 96 ) );
t( 'رابطه: ۹۶ برای شهر نیست، ۹۷ در مرکزیت parents است', 'parents' === rd( 'tp_city', 'capital-of', 97 ) );
t( 'رابطه: ۱۷۳ برای شهر parents است، نه children', 'parents' === rd( 'tp_city', 'routes', 173 ) );
t( 'رابطه: ۱۰۱ کوه خودارجاعِ یک‌به‌چند است، پس زیرشاخه نه هم‌مرز',
	'children' === rd( 'tp_mountain', 'subranges', 101 ) && empty( $relmap['tp_mountain']['neighbors'] ) );
t( 'رابطه: ۱۰۲ استان خودارجاعِ چندبه‌چند است، پس هم‌مرز', 'children' === rd( 'tp_province', 'neighbors', 102 ) );

/* استان عمداً دست‌نخورده ماند — چهار رابطهٔ اسکین کنار گذاشته شد */
$pv_ids = array();
/*
 * فقط رابطه‌های **خودِ استان**.  P5-D210
 *
 * در زنجیره تنها پلهٔ اول از آنِ استان است؛ پله‌های بعدی مال نوع بعدی‌اند
 * (۷۳ شهرستان→شهر است، نه استان→چیزی). شمردنشان این گارد را از معنا
 * می‌انداخت: دیگر «استان چند رابطه دارد» را نمی‌سنجید.
 */
foreach ( $relmap['tp_province'] as $g ) {

	if ( isset( $g['rels'] ) ) {
		foreach ( $g['rels'] as $s ) { $pv_ids[ (int) $s['id'] ] = 1; }
		continue;
	}

	if ( ! empty( $g['chain'] ) ) {
		$first = reset( $g['chain'] );
		$pv_ids[ (int) $first['id'] ] = 1;
	}

	/* `chains` (جمع) از P5-D219 — همان قاعده: فقط پلهٔ اولِ هر زنجیره. */
	foreach ( isset( $g['chains'] ) ? $g['chains'] : array() as $chain ) {
		$first = reset( $chain );
		$pv_ids[ (int) $first['id'] ] = 1;
	}
}
t( 'رابطه: استان ۳۰ رابطه دارد، نه ۳۴', 30 === count( $pv_ids ), 'شمار: ' . count( $pv_ids ) );
t( 'رابطه: ۸۲ و ۸۳ و ۱۲۵ و ۱۲۸ عمداً در استان نیستند',
	! isset( $pv_ids[82] ) && ! isset( $pv_ids[83] ) && ! isset( $pv_ids[125] ) && ! isset( $pv_ids[128] ) );
$pv_nature = array_column( $relmap['tp_province']['nature']['rels'], 'id' );
t( 'رابطه: ۷۵ از طبیعت به روستاها رفت',
	! in_array( 75, $pv_nature, true ) && 'children' === rd( 'tp_province', 'villages', 75 ) );

/* گارد کلید بی‌صدا — دقیقاً همان اشتباهی که فهرست ثابت چیدمان می‌ساخت */
$allowed = array_merge( array_keys( topersia_g_rel_zones() ), array( 'capital', 'crumbs' ) );
$orphan  = array();
foreach ( $relmap as $pt => $groups ) {
	foreach ( $groups as $g => $ignored ) {
		if ( ! in_array( $g, $allowed, true ) ) { $orphan[ $g ] = $g; }
	}
}
t( 'رابطه: هیچ کلید گروهی بی‌صدا نمی‌افتد — یا بخش دارد یا عمداً مستثناست',
	empty( $orphan ), implode( ' · ', $orphan ) );

/* چیدمان نوع‌محور شد */
$pg_prov = r( 20, 'tp_page' );
$pg_city = r( 30, 'tp_page' );
t( 'چیدمان: شهر بخش محله‌ها می‌گیرد — پیش از نقشهٔ نوع‌محور هرگز نمی‌گرفت',
	false !== strpos( $pg_city, 'tp-districts' ) );
t( 'چیدمان: شهر بخش مرکزیت می‌گیرد', false !== strpos( $pg_city, 'tp-capital-of' ) );
t( 'چیدمان: شهر بخش گذر مرزی می‌گیرد', false !== strpos( $pg_city, 'tp-border' ) );
t( 'چیدمان: استان بخش روستاها گرفت', false !== strpos( $pg_prov, 'tp-villages' ) );
/* بخش `#places` مرجع — مرکز استان و شهرستان‌ها با هم.  P5-D215 */
t( 'چیدمان: استان بخش شهرستان‌ها و شهرها می‌گیرد',
	false !== strpos( $pg_prov, 'tp-places' )
	&& false === strpos( $pg_city, 'tp-places' ) );
t( 'چیدمان: کوه بخش زیرشاخه‌ها می‌گیرد', false !== strpos( r( 24, 'tp_page' ), 'tp-subranges' ) );
t( 'چیدمان: کویر هیچ بخش رابطه‌ای نمی‌گیرد',
	false === strpos( r( 40, 'tp_page' ), 'tp-rel-' ) && false === strpos( r( 40, 'tp_page' ), 'tp-subranges' ) );

/* لایهٔ انیمیشن هوا زیر پوشش سربرگ می‌ماند — P5-D197
   اگر بالای پوشش برود، کنتراست متن سربرگ می‌افتد و آزمون کنتراست هم
   آن را می‌گیرد؛ ولی این گارد ارزان‌تر است و علتش را نام می‌برد. */
$hero_css = file_get_contents( dirname( __DIR__ ) . '/plugin/topersia-render/assets/topersia-g-template.css' );
$hero_z   = array();
foreach ( array( 'tp-hero__img' => 0, 'tp-wx-fx' => 0, 'tp-hero__overlay' => 0, 'tp-hero__in' => 0 ) as $sel => $ignored ) {
	if ( preg_match( '/\.' . preg_quote( $sel, '/' ) . '\{[^}]*z-index:\s*(\d+)/s', $hero_css, $zm ) ) {
		$hero_z[ $sel ] = (int) $zm[1];
	}
}
t( 'هوا: هر چهار لایهٔ سربرگ z-index صریح دارند', 4 === count( $hero_z ), implode( ' · ', array_keys( $hero_z ) ) );
t( 'هوا: لایهٔ انیمیشن زیر پوشش سربرگ است  P5-D197',
	isset( $hero_z['tp-wx-fx'], $hero_z['tp-hero__overlay'], $hero_z['tp-hero__in'] )
	&& $hero_z['tp-wx-fx'] < $hero_z['tp-hero__overlay']
	&& $hero_z['tp-hero__overlay'] < $hero_z['tp-hero__in'],
	implode( ' · ', array_map( function ( $k, $v ) { return $k . '=' . $v; }, array_keys( $hero_z ), $hero_z ) ) );

/* واژگان وضعیت هوا — P5-D196 */
$wx_states = topersia_g_wx_states();
t( 'هوا: واژگان دقیقاً نُه وضعیت دارد', 9 === count( $wx_states ), implode( ' · ', $wx_states ) );
t( 'هوا: dust جدا از fog در واژگان هست',
	in_array( 'dust', $wx_states, true ) && in_array( 'fog', $wx_states, true ) );
t( 'هوا: cold جدا از snow در واژگان هست',
	in_array( 'cold', $wx_states, true ) && in_array( 'snow', $wx_states, true ) );

$wx_out  = array();
$wx_bad  = array();
$wx_dust = array();

foreach ( topersia_g_wx_providers() as $wx_provider ) {

	foreach ( call_user_func( array( $wx_provider, 'codes' ) ) as $wx_code => $wx_state ) {

		/* هر کد دقیقاً یک وضعیت می‌دهد و آن وضعیت از همان هشت‌تاست */
		$mapped = call_user_func( array( $wx_provider, 'map' ), $wx_code );

		if ( ! topersia_g_wx_is_state( $mapped ) || $mapped !== $wx_state ) {
			$wx_bad[] = $wx_provider . ':' . $wx_code . '→' . $mapped;
		}

		$wx_out[ $mapped ] = 1;

		/* کد مه هرگز نباید گردوغبار بدهد — P5-D196 */
		if ( 'fog' === $wx_state && 'dust' === $mapped ) {
			$wx_dust[] = $wx_provider . ':' . $wx_code;
		}
	}

	/* کد ناشناخته هم نباید چیزی بیرون از واژگان بسازد */
	foreach ( array( -1, 4, 42, 1000 ) as $wx_unknown ) {
		$fallback = call_user_func( array( $wx_provider, 'map' ), $wx_unknown );
		if ( ! topersia_g_wx_is_state( $fallback ) ) {
			$wx_bad[] = $wx_provider . ':ناشناخته ' . $wx_unknown . '→' . $fallback;
		}
		$wx_out[ $fallback ] = 1;
	}
}

t( 'هوا: هر کد ارائه‌دهنده دقیقاً به یکی از نُه وضعیت می‌رسد  P5-D196',
	empty( $wx_bad ), implode( ' · ', $wx_bad ) );
t( 'هوا: هیچ وضعیتی بیرون از نُه‌تا تولید نمی‌شود',
	empty( array_diff( array_keys( $wx_out ), $wx_states ) ),
	implode( ' · ', array_diff( array_keys( $wx_out ), $wx_states ) ) );
t( 'هوا: کد مه هرگز dust نمی‌دهد', empty( $wx_dust ), implode( ' · ', $wx_dust ) );

/* اولویت‌ها: هوای خطرناک بر ناخوشایند، و گردوغبار بر مه */
t( 'هوا: گردوغبار زیر مه پنهان نمی‌شود',
	'dust' === TP_Wx_Open_Meteo::from( array( 'code' => 45, 'dust_ugm3' => 200 ) ) );
t( 'هوا: باران بر گردوغبار مقدم است',
	'rain' === TP_Wx_Open_Meteo::from( array( 'code' => 61, 'dust_ugm3' => 200 ) ) );
t( 'هوا: hot از دما می‌آید نه از کد',
	'hot' === TP_Wx_Open_Meteo::from( array( 'code' => 0, 'temp_c' => 45 ) )
	&& 'clear' === TP_Wx_Open_Meteo::from( array( 'code' => 0, 'temp_c' => 20 ) ) );
t( 'هوا: تندر بر دمای بالا مقدم است',
	'storm' === TP_Wx_Open_Meteo::from( array( 'code' => 95, 'temp_c' => 45 ) ) );
t( 'هوا: ردیف خالی هم وضعیت معتبر می‌دهد',
	topersia_g_wx_is_state( TP_Wx_Open_Meteo::from( array() ) ) );

/* cold — متقارن hot، و snow فقط بارش را می‌گیرد */
t( 'هوا: سرمای خشک cold می‌دهد نه snow',
	'cold' === TP_Wx_Open_Meteo::from( array( 'code' => 0, 'temp_c' => -12 ) ) );
t( 'هوا: برف بر سرما مقدم است — بارش خبر بیشتری دارد',
	'snow' === TP_Wx_Open_Meteo::from( array( 'code' => 73, 'temp_c' => -12 ) ) );
t( 'هوا: آستانه‌ها همان‌اند که تصمیم شد',
	42.0 === TP_Wx_Open_Meteo::HOT_C
	&& -5.0 === TP_Wx_Open_Meteo::COLD_C
	&& 150.0 === TP_Wx_Open_Meteo::DUST_UGM3,
	TP_Wx_Open_Meteo::HOT_C . ' · ' . TP_Wx_Open_Meteo::COLD_C . ' · ' . TP_Wx_Open_Meteo::DUST_UGM3 );
t( 'هوا: ۳۹ درجه دیگر hot نیست',
	'clear' === TP_Wx_Open_Meteo::from( array( 'code' => 0, 'temp_c' => 39 ) ) );
t( 'هوا: غبار ۸۰ دیگر dust نیست، ۱۶۰ هست',
	'clear' === TP_Wx_Open_Meteo::from( array( 'code' => 0, 'dust_ugm3' => 80 ) )
	&& 'dust' === TP_Wx_Open_Meteo::from( array( 'code' => 0, 'dust_ugm3' => 160 ) ) );

/* شبکه و کش — غبار منطقه‌ای است، پس درشت‌تر خوانده می‌شود */
t( 'هوا: کیفیت هوا شبکهٔ درشت‌تر و کش بلندتر دارد',
	TP_Wx_Open_Meteo::GRID_AIR > TP_Wx_Open_Meteo::GRID_FORECAST
	&& TP_Wx_Open_Meteo::TTL_AIR > TP_Wx_Open_Meteo::TTL_FORECAST );
t( 'هوا: شبکه ۰٫۲۵ و ۰٫۵ · کش سه و شش ساعت',
	0.25 === TP_Wx_Open_Meteo::GRID_FORECAST && 0.5 === TP_Wx_Open_Meteo::GRID_AIR
	&& 10800 === TP_Wx_Open_Meteo::TTL_FORECAST && 21600 === TP_Wx_Open_Meteo::TTL_AIR );

/* دو نقطه که در یک سلول غبار می‌افتند ولی در پیش‌بینی جدا می‌شوند */
$wx_a = array( 35.55, 51.40 );
$wx_b = array( 35.70, 51.40 );
t( 'هوا: دو نقطهٔ نزدیک یک فراخوان کیفیت هوا می‌گیرند',
	TP_Wx_Open_Meteo::cache_key( 'air', $wx_a[0], $wx_a[1] )
	=== TP_Wx_Open_Meteo::cache_key( 'air', $wx_b[0], $wx_b[1] ) );
t( 'هوا: همان دو نقطه پیش‌بینی جداگانه می‌گیرند',
	TP_Wx_Open_Meteo::cache_key( 'forecast', $wx_a[0], $wx_a[1] )
	!== TP_Wx_Open_Meteo::cache_key( 'forecast', $wx_b[0], $wx_b[1] ) );

/* سه ناحیهٔ بدون داده — P5-D192 */
$pg_prov_m = r( 20, 'tp_page' );
$pg_des_m  = r( 40, 'tp_page' );

t( 'موبایل: نوار پایین و برگهٔ بخش‌ها ساخته می‌شوند',
	false !== strpos( $pg_prov_m, 'class="tp-mnav"' ) && false !== strpos( $pg_prov_m, 'id="tpSheet"' ) );
$sheet_links = array();
if ( preg_match( '/<div class="tp-sheet__p".*?<ol>(.*?)<\/ol>/s', $pg_prov_m, $sm ) ) {
	preg_match_all( '/href="#([^"]+)"/', $sm[1], $sheet_links );
}
$snav_links = array();
if ( preg_match( '/<nav class="tp-snav".*?<\/nav>/s', $pg_prov_m, $nm ) ) {
	preg_match_all( '/href="#([^"]+)"/', $nm[0], $snav_links );
}
/*
 * از P5-D219 دو فهرست عمداً یکی نیستند — عیناً مرجع: نوار دسکتاپ همان
 * دوازده پیوند است و برگهٔ موبایل همهٔ بخش‌ها در چهار گروه. قاعده حالا
 * این است که هیچ پیوندِ نوار دسکتاپ در برگه جا نماند، و برگه گروه‌بندی
 * مرجع را داشته باشد. P5-D192 («یک فهرست») جای خودش را به این داد.
 */
$snav_lost = array_diff(
	isset( $snav_links[1] ) ? $snav_links[1] : array(),
	isset( $sheet_links[1] ) ? $sheet_links[1] : array()
);

t( 'موبایل: برگه هر پیوند نوار دسکتاپ را دارد  P5-D219',
	! empty( $sheet_links[1] ) && empty( $snav_lost ),
	'جامانده: ' . implode( ' · ', $snav_lost ) );

t( 'موبایل: برگه گروه‌های مرجع را دارد  P5-D219',
	false !== strpos( $pg_prov_m, '<li class="tp-sheet__g">مطالب</li>' )
	&& false !== strpos( $pg_prov_m, '<li class="tp-sheet__g">پایان صفحه</li>' ),
	'سرگروه نیست' );

t( 'مودال: نقشه ساخته می‌شود و مختصات دارد',
	false !== strpos( $pg_prov_m, 'id="tpMapModal"' ) && false !== strpos( $pg_prov_m, '32.6539' ) );
t( 'مودال: گالری فقط وقتی تصویر هست ساخته می‌شود',
	false !== strpos( $pg_prov_m, 'id="tpGalleryModal"' ) && false === strpos( $pg_des_m, 'id="tpGalleryModal"' ) );
t( 'مودال: دکمهٔ بازکنندهٔ گالری در بخش گالری هست',
	false !== strpos( $pg_prov_m, 'data-tp-gallery-open' ) );

$cult = r( 30, 'tp_culture' );
t( 'فرهنگ: شبکهٔ خانواده ساخته می‌شود', false !== strpos( $cult, 'tp-culture-grid' ) );
t( 'فرهنگ: خانوادهٔ بی‌نتیجه کارت نمی‌سازد',
	1 === substr_count( $cult, 'class="tp-culture-card"' ), substr_count( $cult, 'class="tp-culture-card"' ) . ' کارت' );
t( 'فرهنگ: کویر بدون رابطهٔ فرهنگی، بخش ناپدید', '' === r( 40, 'tp_culture' ) );
$fam_ok = true;
foreach ( topersia_g_relmap() as $pt => $groups ) {
	if ( empty( $groups['culture']['rels'] ) ) { continue; }
	foreach ( $groups['culture']['rels'] as $spec ) {
		if ( empty( $spec['fam'] ) ) { $fam_ok = false; }
	}
}
t( 'فرهنگ: هر رابطهٔ فرهنگی در نقشه خانواده دارد', $fam_ok );
t( 'فرهنگ: هر شش خانواده تعریف شده', 6 === count( topersia_g_families() ) );

/* گارد لنگر — شناسهٔ بخش از کلید گروه ساخته می‌شود ولی لنگر ناوبری از
   جدول چیدمان. اگر این دو از هم بپاشند، نوار بخش‌ها به جایی می‌برد که
   وجود ندارد. مرورگر این را روی tp-capital_of گرفت؛ اینجا قفلش می‌کنیم. */
$dangling = array();
foreach ( array( 20, 24, 30, 40 ) as $anchor_pid ) {
	$doc = r( $anchor_pid, 'tp_page' );
	preg_match_all( '/href="#([^"]+)"/', $doc, $hrefs );
	preg_match_all( '/ id="([^"]+)"/', $doc, $haveids );
	foreach ( array_unique( $hrefs[1] ) as $href ) {
		if ( ! in_array( $href, $haveids[1], true ) ) { $dangling[] = $anchor_pid . ':' . $href; }
	}
}
t( 'چیدمان: هر لنگر نوار بخش‌ها مقصد دارد', empty( $dangling ), implode( ' · ', $dangling ) );

/* گارد بازگشت — مسیر افزونه، شکست چیدمان فضای کاری ۱۴۰۵/۰۶/۲۴ */
t(
	'مسیر: افزونه پیدا شد و هر سه فایلش خواندنی‌اند',
	defined( 'TP_PLUGIN_DIR' )
		&& is_readable( TP_PLUGIN_DIR . 'includes/topersia-g-map.php' )
		&& is_readable( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' )
		&& is_readable( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' ),
	defined( 'TP_PLUGIN_DIR' ) ? TP_PLUGIN_DIR : 'تعریف نشده'
);

/* گارد بازگشت — کلاس بی‌پیشوند، آزمون زندهٔ ۴٫۰٫۱
   یک مقدار پیش‌فرض پارامتر از قلم افتاده بود و شش بخش را بی‌استایل کرد.
   این گارد کل HTML خروجی را می‌گردد، نه فقط فایل را. */
$all = '';
foreach ( array( 20, 30, 40 ) as $case_pid ) {
	$all .= r( $case_pid, 'tp_page' );
}
preg_match_all( '/class="([^"]+)"/', $all, $m );
$bad = array();
foreach ( $m[1] as $attr ) {
	foreach ( explode( ' ', $attr ) as $one ) {
		$one = trim( $one );
		if ( '' === $one ) { continue; }
		if ( 0 === strpos( $one, 'tp-' ) || 0 === strpos( $one, 'is-' ) || 0 === strpos( $one, 'has-' ) ) { continue; }
		/* تنها استثنا: مجموعهٔ آیکون شبکه‌های اجتماعی وودمارت در پنل اشتراک —
		   ۴٫۳٫۰ بند ۵ صریحاً همان کلاس‌ها را خواسته (el-social-icons.css).  P5-D239 */
		if ( in_array( $one, array( 'wd-social-icons', 'wd-icon', 'social-tg', 'social-whatsapp', 'social-twitter', 'social-facebook', 'social-linkedin', 'social-email' ), true ) ) { continue; }
		$bad[ $one ] = $one;
	}
}
t( 'HTML: هیچ کلاس بی‌پیشوندی تولید نمی‌شود  P5-D180', empty( $bad ), implode( ' · ', $bad ) );

preg_match_all( '/ id="([^"]+)"/', $all, $mi );
$badid = array_filter( $mi[1], function ( $one ) { return 0 !== strpos( $one, 'tp' ); } );
t( 'HTML: هیچ شناسهٔ بی‌پیشوندی تولید نمی‌شود', empty( $badid ), implode( ' · ', $badid ) );

/* گارد بازگشت — باگ سرریز اسلایدر، آزمون زندهٔ ۴٫۰٫۰ */
$css = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' );

/*
 * نسخهٔ بی‌لنگر — برای گاردهایی که به **شکل** سلکتور کار دارند نه به دامنه‌اش.
 *
 * از `P5-D201` هر قاعده زیر `.tp-page` است. گاردهایی که سلکتور را با
 * رشتهٔ ثابت می‌سنجیدند همان روز قرمز شدند، نه چون چیزی خراب شده بود،
 * بلکه چون متن سلکتور عوض شده بود. سنجش روی نسخهٔ بی‌لنگر انجام می‌شود
 * تا دامنه و شکل با هم قاتی نشوند.
 */
$css_bare = str_replace( TP_SCOPE . ' ', '', $css );

t( 'CSS: ستون اصلی اجازهٔ کوچک‌شدن دارد', false !== strpos( $css_bare, '.tp-main-col{min-width:0}' ) );
t( 'CSS: اسلایدر افقی اسکرول می‌شود', false !== strpos( $css_bare, 'scroll-snap-type:x mandatory' ) );
t( 'CSS: موزاییک گالری همان شبکهٔ مرجع است', false !== strpos( $css_bare, '.tp-gallery-main{display:grid;grid-template-columns:2fr 1fr 1fr' ) );

$braces = substr_count( $css, '{' ) - substr_count( $css, '}' );
t( 'CSS: آکولادها متوازن‌اند', 0 === $braces, "اختلاف: {$braces}" );

/*
 * آزادسازیِ ظرف — ۴٫۲٫۱ بند ۲.
 *
 * انتقالِ ۴٫۲٫۰ این دو قاعدهٔ ۴٫۱٫۶ را انداخت و هیچ گاردی نگرفت، چون
 * لنگرِ `.tp-page` جلوی هر قاعده می‌نشست و قاعدهٔ «بیرون از صفحه» جایی
 * در گاردها نداشت. حالا شکلِ دقیقش در فایل نهایی سنجیده می‌شود: سرِ سطر،
 * بی `.tp-page ` و بی `html ` جلویش — لنگرخورده یعنی به ظرف نمی‌رسد.
 */
t( 'CSS: ظرف وودمارت آزاد است — بی‌پیشوند، بیرون از .tp-page',
	(bool) preg_match( '/(^|\n)\.wd-content-layout:has\(\.tp-page\)\{max-width:none;padding:0\}/', $css ) );

t( 'CSS: ظرف المنتور آزاد است — بی‌پیشوند، بیرون از .tp-page',
	(bool) preg_match( '/(^|\n)\.e-con:has\(\.tp-page\)\{--padding-top:0px;--padding-right:0px;--padding-bottom:0px;--padding-left:0px;--gap:0px\}/', $css ) );

t( 'CSS: هیچ‌کدام از دو قاعدهٔ ظرف لنگر نخورده',
	false === strpos( $css, '.tp-page .wd-content-layout' ) && false === strpos( $css, '.tp-page .e-con' )
	&& 1 === substr_count( $css, '.wd-content-layout:has(.tp-page)' ) && 1 === substr_count( $css, '.e-con:has(.tp-page)' ) );

/* ---------------------------------------------------------------------------
   شیوه‌نامهٔ قالب، دو نیمه — P5-D219

   نیمهٔ منتقل‌شده طرح تأییدشدهٔ مرجع است و رنگ‌های ثابتش عمداً دست‌نخورده
   ماندند. نیمهٔ افزوده را ما نوشتیم و همان قواعد همیشگی رویش‌اند.
   مرز میان دو نیمه با دو نشان در خودِ فایل علامت خورده، پس هیچ قاعده‌ای
   نمی‌تواند بی‌صدا از این طرف به آن طرف برود.
   --------------------------------------------------------------------------- */
$added_at  = strpos( $css, 'TP-ADDED-START' );
$added_end = strpos( $css, 'TP-ADDED-END' );

t( 'شیوه‌نامه: مرز «افزوده» و «منتقل‌شده» علامت دارد  P5-D219',
	false !== $added_at && false !== $added_end && $added_end > $added_at );

$css_added = ( false !== $added_at && false !== $added_end )
	? substr( $css, $added_at, $added_end - $added_at ) : '';

$css_port  = ( false !== $added_end ) ? substr( $css, $added_end ) : $css;

/*
 * هیچ مقدار تازه‌ای در نیمهٔ منتقل‌شده — همان مقادیر مرجع.  P5-D219
 *
 * این جای قاعدهٔ «بدون هگز» را می‌گیرد، که برای فایلِ دست‌نویس بود.
 * اینجا پرسش دیگری است: آیا چیزی در فایل هست که در مرجع نیست؟
 */
$ref_html = dirname( __DIR__ ) . '/reference/ToPersia-G3-Province-Template.html';
$ref_src  = is_readable( $ref_html ) ? file_get_contents( $ref_html ) : '';
$ref_doc1 = ( '' !== $ref_src ) ? substr( $ref_src, 0, (int) strpos( $ref_src, '</body>' ) ) : '';

preg_match_all( '#<style>(.*?)</style>#s', $ref_doc1, $ref_styles );
$ref_css = implode( "\n", $ref_styles[1] );

preg_match_all( '/#[0-9a-fA-F]{3,8}\b|rgba?\([^)]*\)/', $css_port, $lit );

$alien = array();

foreach ( array_unique( $lit[0] ) as $one ) {
	if ( false === stripos( $ref_css, $one ) ) {
		$alien[] = $one;
	}
}

t( 'شیوه‌نامه: هیچ رنگی که در مرجع نیست، در نیمهٔ منتقل‌شده نیست  P5-D219',
	empty( $alien ), implode( ' · ', $alien ) );

/* مولد و فایل باید یکی باشند — وگرنه کسی فایل را دستی دست زده. */
$gen = dirname( __DIR__ ) . '/scripts/tp-template-gen.php';

t( 'شیوه‌نامه: فایل دقیقاً خروجی مولد است  P5-D219',
	is_readable( $gen ) && 0 === tp_run_php( escapeshellarg( $gen ) . ' --check' ),
	'php scripts/tp-template-gen.php' );

/* P5-D184 — متن ریز رنگ کم‌رنگ نمی‌گیرد.
   ‏--tp-text-2 روی ۱۴ پیکسل به بالا کنتراست کافی دارد، زیر آن نه.
   پالت دست‌نخورده می‌ماند؛ فقط انتخاب توکن عوض می‌شود. */
$dim_small = array();

/*
 * دامنهٔ این سه گارد از ۴٫۲٫۰ نیمهٔ **افزوده** است، نه کل فایل.  P5-D219
 *
 * نیمهٔ منتقل‌شده طرح تأییدشدهٔ مرجع است و انتخاب رنگش تصمیم ما نبوده؛
 * گاردِ آن «هیچ مقدار تازه» است و عددِ واقعی‌اش را `contrast.php` در هر
 * سه حالت می‌سنجد. اگر همین گاردها را روی نیمهٔ منتقل‌شده هم می‌گذاشتیم،
 * یا باید طرح را عوض می‌کردیم یا گارد را — و هیچ‌کدام درست نبود.
 */
$css_guard = str_replace( TP_SCOPE . ' ', '', $css_added ) . "\n"
	. str_replace( TP_SCOPE . ' ', '', file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-theme-bridge.css' ) );

/*
 * ۴٫۴٫۰: گارد دو کور داشت. اندازهٔ `calc(N * var(--tp-rem))` را — شکلِ همهٔ
 * اندازه‌های قالب — نمی‌خواند، و نامِ دیگرِ همان رنگ (`--tp-tile-muted`، P5-D245)
 * را نمی‌شناخت. برچسب ۰٫۶rem کاشی و دو متن فرم نظرِ ۴٫۳٫۰ از همین‌جا رد شده
 * بودند. سنجه‌ها سه مورد کالیبراسیون دارند: اگر هر کدام را نگیرد، گارد کور است.
 */
$tp_d184 = function ( $css ) {
	$hits = array();
	preg_match_all( '/([^{}]+)\{([^{}]*)\}/', preg_replace( '!/\*.*?\*/!s', '', $css ), $rs, PREG_SET_ORDER );
	foreach ( $rs as $rule ) {
		if ( ! preg_match( '/(?<![-\w])color:\s*var\(\s*--tp-(?:text-2|tile-muted)\s*[,)]/', $rule[2] ) ) { continue; }
		if ( ! preg_match( '/font-size:\s*(?:calc\(\s*([0-9.]+)\s*\*\s*var\(--tp-rem\)\s*\)|([0-9.]*[0-9])(rem|px))/', $rule[2], $fs ) ) { continue; }
		$size_px = isset( $fs[1] ) && '' !== $fs[1] ? $fs[1] * 16 : ( 'rem' === $fs[3] ? $fs[2] * 16 : (float) $fs[2] );
		if ( $size_px < 14 ) {
			$hits[] = trim( preg_replace( '/\s+/', ' ', $rule[1] ) ) . ' → ' . $size_px . 'px';
		}
	}
	return $hits;
};
$d184_cal = $tp_d184( '.a{color:var(--tp-text-2);font-size:.7rem}.b{font-size:calc(.84 * var(--tp-rem));color:var(--tp-text-2)}'
	. '.c{color:var(--tp-tile-muted);font-size:calc(.6 * var(--tp-rem))}.d{color:var(--tp-text-2);font-size:calc(.9 * var(--tp-rem))}'
	. '.e{border-color:var(--tp-text-2);font-size:.7rem}' );
preg_match_all( '/([^{}]+)\{([^{}]*)\}/', preg_replace( '!/\*.*?\*/!s', '', $css_guard ), $rules, PREG_SET_ORDER );
$dim_small = $tp_d184( $css_guard );
t( 'CSS: متن زیر ۱۴ پیکسل با --tp-text-2 یا نام دیگرش (--tp-tile-muted) جمع نمی‌شود — اندازهٔ rem، px و calc(… * var(--tp-rem))  P5-D184',
	3 === count( $d184_cal ) && empty( $dim_small ), 3 !== count( $d184_cal ) ? 'کالیبراسیون: ' . implode( ' · ', $d184_cal ) : implode( ' · ', $dim_small ) );

/* P5-D188 — --tp-primary رنگِ پر و مرز است، نه رنگِ متنِ ریز.
   برای متن --tp-primary-text هست که روی نردبان روشنایی پایین‌تر نشسته.
   الگو مقید است: border-color هم زیررشتهٔ color را دارد و نباید بگیردش. */
$primary_small = array();
foreach ( $rules as $rule ) {
	if ( ! preg_match( '/(?<![-\w])color:\s*var\(\s*--tp-primary\s*[,)]/', $rule[2] ) ) { continue; }
	if ( ! preg_match( '/font-size:\s*([0-9.]*[0-9])(rem|px)/', $rule[2], $fs2 ) ) { continue; }
	$size2 = 'rem' === $fs2[2] ? $fs2[1] * 16 : (float) $fs2[1];
	if ( $size2 < 16 ) {
		$primary_small[] = trim( preg_replace( '/\s+/', ' ', $rule[1] ) ) . ' → ' . $size2 . 'px';
	}
}
t( 'CSS: متن زیر ۱rem با --tp-primary جمع نمی‌شود  P5-D188',
	empty( $primary_small ), implode( ' · ', $primary_small ) );

/* پالت دو نسخه دارد: توکن‌های CSS، و آینهٔ جاوااسکریپتی در صفحهٔ تنظیمات
   که سواچ‌ها را می‌کشد. `P5-D189` فقط یکی را عوض کرد و دومی جا ماند.
   این گارد می‌گوید هر سه حالت در هر دو فایل یکی‌اند. */
$tokens_src   = file_get_contents( dirname( __DIR__ ) . '/plugin/topersia-render/assets/topersia-tokens.css' );
$settings_src = file_get_contents( dirname( __DIR__ ) . '/plugin/topersia-render/includes/topersia-settings.php' );
$drift        = array();
foreach ( array( 'light', 'evening', 'dark' ) as $mode ) {
	$block = 'light' === $mode
		? '/:root,\s*html\[data-tp-mode="light"\]\{(.*?)\n\}/s'
		: '/html\[data-tp-mode="' . $mode . '"\]\{(.*?)\n\}/s';
	if ( ! preg_match( $block, $tokens_src, $bm )
		|| ! preg_match( '/--tp-primary:\s*(#[0-9A-Fa-f]{6})/', $bm[1], $tm )
		|| ! preg_match( '/' . $mode . ':\s*\{[^}]*sw:\[\s*\'(#[0-9A-Fa-f]{6})/s', $settings_src, $sm ) ) {
		$drift[] = $mode . ': خوانده نشد';
		continue;
	}
	if ( 0 !== strcasecmp( $tm[1], $sm[1] ) ) {
		$drift[] = $mode . ': توکن ' . $tm[1] . ' ≠ سواچ ' . $sm[1];
	}
}
t( 'پالت: سواچ صفحهٔ تنظیمات با توکن CSS یکی است  P5-D189',
	empty( $drift ), implode( ' · ', $drift ) );

/*
 * رنگ متن روی پرِ رنگی هرگز ارثی نمی‌ماند.
 *
 * هر قاعده‌ای که background را از --tp-X می‌گیرد و پالت --tp-on-X دارد،
 * باید color را هم صریح از --tp-on-X بگیرد. سه بار یک توکنِ on-* موجود
 * بی‌مصرف ماند و رنگ متن از جای دیگری ارث رسید؛ آخرینش
 * `.tp-map-btn:hover` بود که در حالت روز ۳٫۷۳ می‌داد.
 *
 * این ایراد را در سطح قاعده می‌گیرد، پیش از آنکه به سنجش پیکسلی برسد.
 */
$on_pairs = array( 'primary', 'hover', 'accent', 'warning', 'secondary', 'media', 'selected' );

/*
 * پرِ رنگی که هرگز متن نمی‌گیرد از این قاعده مستثناست.
 * هر کدام راستی‌آزمایی شده، نه فرض:
 *   .tp-map-pin        <button></button> کاملاً خالی، فقط aria-label
 *   .tp-eyebrow::after نوار تزئینی ۴۴×۲ پیکسل، content:""
 * اگر روزی متن گرفتند، همین‌جا از فهرست بیرون بیایند.
 */
$on_textless = array( '.tp-map-pin', '.tp-eyebrow::after' );

$on_miss = array();
foreach ( $rules as $rule ) {
	$sel  = trim( preg_replace( '/\s+/', ' ', $rule[1] ) );
	$body = $rule[2];
	if ( in_array( $sel, $on_textless, true ) ) { continue; }
	foreach ( $on_pairs as $pair ) {
		if ( ! preg_match( '/background(-color)?:\s*var\(\s*--tp-' . $pair . '\s*[,)]/', $body ) ) { continue; }
		if ( false === strpos( $css, '--tp-on-' . $pair ) && false === strpos( $tokens_src, '--tp-on-' . $pair ) ) { continue; }
		if ( preg_match( '/(?<![-\w])color:\s*var\(\s*--tp-on-' . $pair . '\s*[,)]/', $body ) ) { continue; }
		$on_miss[] = $sel . ' → --tp-' . $pair . ' بدون --tp-on-' . $pair;
	}
}
t( 'رنگ: پرِ --tp-X رنگ متنش را صریح از --tp-on-X می‌گیرد  P5-D191',
	empty( $on_miss ), implode( ' · ', $on_miss ) );

/* ── لایهٔ هوا: سیم‌کشی نباید نیمه‌کاره بماند ────────────────────── */

$plugin_src  = file_get_contents( dirname( __DIR__ ) . '/plugin/topersia-render/topersia-render.php' );
$contrast_src = file_get_contents( __DIR__ . '/contrast.php' );
$fixture_src  = file_get_contents( __DIR__ . '/wx-fixture.html' );

/*
 * ترتیب دو اسکریپت هوا بار معنایی دارد: `-fx` خودش را روی
 * `TopersiaWeatherFx` می‌نشاند و اگر پایه نرسیده باشد بی‌صدا برمی‌گردد.
 * وردپرس ترتیب را فقط وقتی تضمین می‌کند که وابستگی اعلام شده باشد.
 */
$wx_base = (bool) preg_match( "/wp_enqueue_script\(\s*'topersia-g-weather'/", $plugin_src );
$wx_dep  = (bool) preg_match(
	"/wp_enqueue_script\(\s*'topersia-g-weather-fx'[^;]*?array\(\s*'topersia-g-weather'\s*\)/s",
	$plugin_src
);

t( 'هوا: هر دو اسکریپت در صف‌اند و نمایش به پایه وابسته است  P5-D197',
	$wx_base && $wx_dep,
	! $wx_base ? 'پایهٔ هوا در صف نیست' : 'وابستگی نمایش به پایه اعلام نشده' );

/*
 * سنجش عمیق باید از مسیر آزمون قابل صدا زدن باشد. اگر قلاب برداشته شود،
 * هیچ‌چیز قرمز نمی‌شود — فقط دیگر سنجیده نمی‌شود، که بدتر است.
 *
 * دنبالِ خودِ فراخوانی می‌گردد، نه دنبال نام فایل: نسخهٔ اول این گارد
 * فقط رشته را می‌جست و وقتی سیم‌کشی را به‌عمد بریدم سبز ماند، چون نام
 * در توضیحات هم آمده بود. گاردی که شرحِ کار را با کار اشتباه بگیرد،
 * گارد نیست.
 */
$deep_hooks = array(
	'دروازهٔ حرکت' => "/tp_run_driver\(\s*'wx-motion-check\.js'/",
	'سنجش عمیق'   => "/tp_run_driver\(\s*'wx-contrast-check\.js'/",
	'خواندن --deep' => "/in_array\(\s*'--deep',\s*\\\$argv/",
);

$deep_missing = array();
foreach ( $deep_hooks as $what => $pattern ) {
	if ( ! preg_match( $pattern, $contrast_src ) ) { $deep_missing[] = $what; }
}

t( 'هوا: دروازهٔ حرکت و سنجش --deep از contrast.php صدا زده می‌شوند  P5-D198',
	empty( $deep_missing ), implode( ' · ', $deep_missing ) );

/*
 * و هر دارایی‌ای که صحنه صدا می‌زند باید کنارش کپی شود.
 *
 * این گارد سبزِ دروغین را می‌گیرد: صحنهٔ بی‌شیوه‌نامه سربرگِ سفیدِ روشن
 * می‌شود و کنتراستش عالی درمی‌آید — عددِ بالا، بی‌آنکه چیزی از تولید
 * سنجیده شده باشد.  P5-D198
 */
$copied = array();

/* دو فهرست: دارایی‌های افزونه، و شیوه‌نامه‌های بیگانه از `reference/` */
foreach ( array( 'wx_assets', 'wx_refs' ) as $list ) {
	if ( preg_match( '/\$' . $list . '\s*=\s*array\((.*?)\);/s', $contrast_src, $wm ) ) {
		preg_match_all( "/'([^']+)'/", $wm[1], $cm );
		$copied = array_merge( $copied, $cm[1] );
	}
}

preg_match_all( '/(?:href|src)="([^"\/:]+\.(?:css|js))"/', $fixture_src, $fm );
$wx_missing = array_values( array_diff( array_unique( $fm[1] ), $copied ) );

t( 'هوا: هر دارایی‌ای که صحنهٔ آزمون صدا می‌زند کنارش کپی می‌شود  P5-D198',
	$copied && $fm[1] && empty( $wx_missing ),
	$copied ? implode( ' · ', $wx_missing ) : 'فهرست کپی در contrast.php خوانده نشد' );

/*
 * ── فریمِ بدترین‌حالت: یک منبع برای رنگ و سقف آلفا ───────────────
 *
 * دروازهٔ `--deep` لایه را با رنگ ذره در بیشینهٔ آلفا پر می‌کند. اگر آن
 * عددها دو نسخه شوند — یکی در رندرکننده، یکی در پایه — روزی یکی عوض
 * می‌شود و سقف دیگر سقف نیست: آزمون بی‌صدا خوش‌بین می‌ماند.  P5-D199
 */
$wx_base_src = file_get_contents( dirname( __DIR__ ) . '/plugin/topersia-render/assets/topersia-g-weather.js' );
$wx_fx_src   = file_get_contents( dirname( __DIR__ ) . '/plugin/topersia-render/assets/topersia-g-weather-fx.js' );

$raw_in_fx = array();
if ( preg_match( '/\b255,255,255\b/', $wx_fx_src ) ) { $raw_in_fx[] = 'رنگ سفید خام'; }
if ( preg_match( '/\b198,176,138\b/', $wx_fx_src ) ) { $raw_in_fx[] = 'رنگ غبار خام'; }
if ( preg_match( '/rand\(\s*0\.6,\s*[\d.]/', $wx_fx_src ) ) { $raw_in_fx[] = 'سقف پراکندگی خام'; }

t( 'هوا: رندرکننده رنگ و سقف آلفا را از پایه می‌گیرد، نه عدد خام  P5-D199',
	empty( $raw_in_fx ), implode( ' · ', $raw_in_fx ) );

/*
 * رنگ مه تنها جایی که کشیده می‌شود CSS است، ولی `peak()` هم باید همان
 * را بگوید وگرنه فریم سقف رنگ دیگری می‌سازد.
 */
$fog_js  = preg_match( "/fog:\s*\{\s*rgb:\s*'([\d,]+)'/", $wx_base_src, $fj ) ? $fj[1] : '';
$fog_css = preg_match( '/\.tp-wx-fx--fog\{(.*?)\n\}/s', $css, $fc )
	&& preg_match( '/rgba\(\s*([\d]+,\s*[\d]+,\s*[\d]+)\s*,/', $fc[1], $fc2 )
	? preg_replace( '/\s+/', '', $fc2[1] ) : '';

t( 'هوا: رنگ مه در پایه و در CSS یکی است  P5-D199',
	'' !== $fog_js && $fog_js === $fog_css,
	'پایه ' . ( $fog_js ? $fog_js : '—' ) . ' ≠ CSS ' . ( $fog_css ? $fog_css : '—' ) );

/* و دروازهٔ عمیق باید واقعاً فریم سقف را بسازد، نه نمونهٔ تصادفی را */
$deep_src = file_get_contents( __DIR__ . '/wx-contrast-check.js' );

t( 'هوا: دروازهٔ --deep فریم سقف را می‌سنجد نه نمونه  P5-D199',
	false !== strpos( $deep_src, '__worst()' )
	&& preg_match( '/row\.gate/', $deep_src )
	&& false !== strpos( $fixture_src, 'TopersiaWeatherFx.peak' ),
	'قلاب فریم سقف نیست' );

/*
 * کروم باید محترمانه بسته شود، وگرنه فرایندهایش جا می‌مانند.
 *
 * `child.kill()` کافی نیست: فرایندی که spawn می‌شود خودش زود می‌میرد و
 * بچه‌هایش به والد دیگری می‌چسبند، پس `taskkill /T` روی آن PID به
 * هیچ‌کس نمی‌خورد. یک نشست آزمون ۱۰۶ درخت یتیم جا گذاشت — ۸۳۱ فرایند —
 * و مجموعه را دو تا پنج برابر کند کرد.  P5-D199
 */
$cdp_src = file_get_contents( __DIR__ . '/cdp.js' );

t( 'آزمون: کروم با Browser.close بسته می‌شود، نه فقط kill  P5-D199',
	false !== strpos( $cdp_src, "'Browser.close'" ),
	'cdp.js مرورگر را محترمانه نمی‌بندد' );

/*
 * ── دامنه: هر قاعده دقیقاً یک بار زیر .tp-page ─────────────────
 *
 * `topersia-user-core` بعد از ما بار می‌شود و هفت کلاس مشترک دارد. با
 * وزن برابر، دیرتر یعنی برنده. لنگرِ `.tp-page` وزن ما را یک پله بالا
 * می‌برد و رقابت را تمام می‌کند.
 *
 * تابع‌ها از خودِ مولد می‌آیند، نه کپی: دو پیاده‌سازی از یک قاعده یعنی
 * روزی یکی عوض می‌شود و آن یکی نه.  P5-D201
 */
define( 'TP_TEMPLATE_LIB', 1 );
require_once dirname( __DIR__ ) . '/scripts/tp-template-gen.php';

$scope_left = tp_unanchored( $css );

t( 'دامنه: هر قاعدهٔ g-zones زیر ' . TP_SCOPE . ' است  P5-D201',
	empty( $scope_left ),
	implode( ' · ', array_slice( $scope_left, 0, 4 ) ) );

t( 'دامنه: هیچ قاعده‌ای دو بار لنگر نخورده  P5-D201',
	false === strpos( $css, TP_SCOPE . ' ' . TP_SCOPE ),
	'لنگر دوتایی' );

/*
 * و لنگر روی سلکتور سطح سند نرفته باشد.
 *
 * `.tp-page :root` به هیچ‌چیز نمی‌خورد و بی‌صدا رد می‌شود. اجرای اولِ
 * مولد همین را ساخت و `--tp-on-media` را کشت — که در همین فایل تعریف
 * شده، نه در `topersia-tokens.css`. متن سربرگ سیاهِ پیش‌فرض شد و
 * کنتراست از ۱۴ به ۱٫۴۶ افتاد، بی‌آنکه چیزی خطا بدهد.
 */
$impossible = array();
foreach ( array( ':root', 'html', 'body' ) as $doc ) {
	if ( false !== strpos( $css, TP_SCOPE . ' ' . $doc ) ) { $impossible[] = TP_SCOPE . ' ' . $doc; }
}

t( 'دامنه: سلکتور ناممکن ساخته نشده  P5-D201',
	empty( $impossible ), implode( ' · ', $impossible ) );

/*
 * پرِ پیش‌فرضِ قالب روی دکمه خنثی می‌شود.
 *
 * وودمارت `:is(.btn,.button,button,…){background-color:var(--btn-bgcolor,#F3F3F3)}`
 * دارد — وزن (۰,۱,۰)، چون `:is()` وزنِ مشخص‌ترین عضوش را می‌گیرد. هر
 * دکمه‌ای که رنگ صریح نگیرد مستطیل روشن می‌شود. یک قاعده جلوی کل دسته
 * را می‌گیرد؛ قاعده‌های رنگ‌دهنده بعدش می‌آیند و برنده‌اند.  P5-D201
 */
/* از ۴٫۲٫۰ «خنثی» یعنی `revert` — برگشت به دکمهٔ مرورگر، که مرجع فرضش
   کرده بود. `transparent` هم پذیرفته است؛ هر دو پرِ وودمارت را می‌کُشند.  P5-D219 */
t( 'دکمه: پرِ پیش‌فرض قالب خنثی شده  P5-D201',
	(bool) preg_match(
		'/\.tp-page\s+button\s*(,[^{}]*)?\{[^}]*background(-color)?\s*:\s*(transparent|revert)/',
		$css
	),
	'خنثی‌سازی پرِ دکمه نیست' );

/*
 * ── اشتراک نام کلاس با user-core صفر است ───────────────────────
 *
 * لنگر مشکل را حل می‌کند ولی تصادم را نگه می‌دارد؛ هر کلاس تازه‌ای که
 * هم‌نام دربیاید، دوباره همان دعوا را می‌سازد. فهرست مجاز صریح است تا
 * هفت موردِ موجود ثبت بماند و هشتمی بی‌صدا اضافه نشود.  P5-D202
 */
$uc_file = dirname( __DIR__ ) . '/reference/user-core.css';

/* هفت موردِ شناخته‌شده — همه با لنگر مهار شده‌اند */
$uc_allow = array( 'tp-hero', 'tp-main', 'tp-stat', 'tp-stats', 'tp-eyebrow', 'tp-badge' );

if ( ! is_file( $uc_file ) ) {

	t( 'تصادم: اشتراک نام کلاس با user-core فقط فهرست مجاز  P5-D202',
		false, 'reference/user-core.css نیست' );

} else {

	$class_of = function ( $text ) {
		$text = preg_replace( '!/\*.*?\*/!s', '', $text );
		$out = array();
		if ( preg_match_all( '/([^{}]*)\{/', $text, $mm ) ) {
			foreach ( $mm[1] as $head ) {
				$head = trim( $head );
				if ( '' === $head || '@' === $head[0] ) { continue; }
				if ( preg_match_all( '/\.([A-Za-z0-9_-]+)/', $head, $cc ) ) {
					foreach ( $cc[1] as $one ) { $out[ $one ] = $one; }
				}
			}
		}
		return $out;
	};

	$ours   = $class_of( $css_bare );
	$theirs = $class_of( file_get_contents( $uc_file ) );

	$shared = array_values( array_intersect_key( $ours, $theirs ) );
	$extra  = array_values( array_diff( $shared, $uc_allow ) );
	$gone   = array_values( array_diff( $uc_allow, $shared ) );

	$note = array();
	if ( $extra ) { $note[] = 'تازه: ' . implode( ' · ', $extra ); }
	if ( $gone )  { $note[] = 'دیگر مشترک نیست: ' . implode( ' · ', $gone ); }

	t( 'تصادم: اشتراک نام کلاس با user-core فقط فهرست مجاز  P5-D202',
		empty( $extra ) && empty( $gone ), implode( '   ', $note ) );
}

/*
 * ── صفحهٔ آزمون کلاس‌های بدنهٔ واقعی را بگیرد ─────────────────────
 *
 * بخش بزرگی از `topersia-tokens.css` زیر `.tp-shell` است. بی‌آن، آن لایه
 * در آزمون خاموش می‌ماند و ایرادی که تیتر سربرگ را تیره می‌کرد اصلاً
 * دیده نمی‌شد. صفحهٔ آزمونی که کلاس بدنه ندارد، صفحهٔ دیگری است.  P5-D206
 */
require_once __DIR__ . '/live-body.php';
$live_body = tp_live_body_class( dirname( __DIR__ ) );

t( 'آزمون: صفحه‌ها کلاس‌های بدنهٔ زنده را می‌گیرند  P5-D206',
	false !== strpos( $live_body, 'tp-shell' )
	&& false !== strpos( $live_body, 'tp-own-type' )
	&& false !== strpos( $contrast_src, '$body_class' )
	&& false !== strpos( file_get_contents( __DIR__ . '/preview.php' ), '$body_class' ),
	'کلاس بدنه وصل نیست: ' . $live_body );

/*
 * ── لایهٔ توکن جلوی لایهٔ ناحیه را نگیرد ─────────────────────────
 *
 * `html:root .tp-shell h1` وزنش (۰,۲,۲) بود و از `.tp-page .tp-hero h1`
 * با (۰,۲,۱) سنگین‌تر — تیتر سربرگ روی سایت رنگ توکن می‌گرفت نه سفید.
 * با `:where()` جزء عنصری وزن صفر می‌گیرد و رقابت تمام می‌شود.
 *
 * تابع از خودِ مولد می‌آید، نه کپی.  P5-D205
 */
define( 'TP_WHERE_LIB', 1 );
require_once dirname( __DIR__ ) . '/scripts/tp-where-gen.php';

$tokens_bare = tp_unwrapped( $tokens_src );

t( 'توکن: جزء عنصری وزن نمی‌گیرد (:where)  P5-D205',
	empty( $tokens_bare ), implode( ' · ', array_slice( $tokens_bare, 0, 4 ) ) );

/* و همان قاعده‌ای که باعثش شد، صریح */
t( 'توکن: قاعدهٔ تیترها داخل :where است  P5-D205',
	(bool) preg_match( '/\.tp-shell\s+:where\(\s*h1\s*,\s*h2\s*,\s*h3\s*,\s*h4\s*\)/', $tokens_src ),
	'قاعدهٔ h1..h4 بی‌پوشش است' );

/*
 * ── کارت رابطه: توضیح · پاورقی · نوار اسلایدر ───────────────────
 *
 * مرجع بصری `.card-slide` سه چیز دارد که ما نداشتیم: توضیح کوتاه،
 * پاورقیِ «والد مکانی · یک واقعیت»، و نوار زیر اسلایدر. هر سه با قاعدهٔ
 * ناپدیدشدن می‌آیند — نبودِ داده یعنی نبودِ جعبه، نه جعبهٔ خالی.  P5-D204
 */
$rel_html = r( 20, 'tp_slider', array( 'rel' => 'villages' ) );

/* P5-D266 توضیح را از همۀ کارت‌های اسلایدر برداشت؛ این ادعا جای «توضیح کوتاه از short_summary» (P5-D204) را گرفت. */
t( 'کارت اسلایدر: توضیح نمی‌آید — نه `card-slide__desc` و نه short_summary؛ عنوان هست  P5-D266',
	'' !== $rel_html && false === strpos( $rel_html, 'tp-card-slide__desc' )
	&& false === strpos( $rel_html, 'روستای سرخ‌رنگ' ) && false !== strpos( $rel_html, 'tp-card-slide__title' ),
	'توضیح هنوز رندر می‌شود' );

/* پاورقی مرجع دو `span` بی‌کلاس داخل `.card-slide__foot` است.  P5-D219 */
t( 'کارت: پاورقی والد مکانی و واقعیت کلیدی دارد  P5-D204',
	(bool) preg_match(
		'#<div class="tp-card-slide__foot"><span>استان اصفهان</span><span>[^<]+</span></div>#u',
		$rel_html
	),
	'پاورقی کارت ناقص است' );

/* نبودِ داده ⇒ نبودِ پاورقی. شهرستان نقشهٔ رابطه ندارد — P5-D172 */
t( 'کارت: پاورقیِ بی‌داده اصلاً رندر نمی‌شود  P5-D204',
	'' === topersia_g_card_foot( array( 'place' => '', 'fact' => '' ) )
	&& '' === topersia_g_card_foot( array() ),
	'پاورقی خالی رندر شد' );

/* دکمه‌ها `data-target`/`data-dir` مرجع را دارند، نه صفت اختصاصی ما. */
t( 'اسلایدر: نوار راهنما و دکمه‌های قبلی/بعدی دارد  P5-D204',
	false !== strpos( $rel_html, 'tp-slider-bar' )
	&& false !== strpos( $rel_html, 'data-dir="1"' )
	&& false !== strpos( $rel_html, 'data-dir="-1"' )
	&& false !== strpos( $rel_html, 'tp-slider-hint' ),
	'نوار اسلایدر ناقص است' );

/* یک کارت جایی برای رفتن ندارد */
t( 'اسلایدر: با یک کارت نوار نمی‌آید  P5-D204',
	'' === topersia_g_slider_bar( 'x', 1 ) && '' !== topersia_g_slider_bar( 'x', 2 ),
	'نوار با یک کارت هم آمد' );

/* دکمه باید بداند کدام اسلایدر را بچرخاند */
t( 'اسلایدر: هر دکمه به شناسهٔ اسلایدرِ خودش وصل است  P5-D204',
	preg_match( '/id="(tpsl[^"]+)"/', $rel_html, $sid )
	&& false !== strpos( $rel_html, 'data-target="' . $sid[1] . '"' ),
	'دکمه به اسلایدر وصل نیست' );

/*
 * ── هر دارایی نسخه می‌گیرد ──────────────────────────────────────
 *
 * سمتِ ما درست است و باید درست بماند: هر `wp_enqueue_*` پارامتر نسخه را
 * از `topersia_render_asset_version()` می‌گیرد که `filemtime` برمی‌گرداند.
 *
 * که روی سایت زنده `?ver=` دیده نمی‌شود، کارِ ما نیست — چیزی بیرون از
 * این افزونه آن را از **نشانیِ افزونه‌ها** برمی‌دارد. جزئیات در گزارش.
 */
$enqueue_calls = preg_match_all(
	'/wp_enqueue_(?:style|script)\(\s*\n\s*\'[^\']+\',\s*\n\s*\$url[^;]*?;/s',
	$plugin_src,
	$eq
);

$no_version = 0;
foreach ( $eq[0] as $call ) {
	if ( false === strpos( $call, 'topersia_render_asset_version(' ) ) { $no_version ++; }
}

t( 'دارایی: هر enqueue نسخه را از filemtime می‌گیرد',
	$enqueue_calls >= 6 && 0 === $no_version,
	'بدون نسخه: ' . $no_version . ' از ' . $enqueue_calls );

/*
 * ── بخش #places — شهرها  P5-D220 ────────────────────────────────
 *
 * تا ۴٫۲٫۰ شهرستان‌ها بودند (P5-D215)؛ حالا شهرهای استان از زنجیرهٔ
 * ۷۲→۷۳، کارت اول مرکز استان (۹۶). دادهٔ آزمون سیزده شهر دارد به‌اضافهٔ
 * مرکز — بیش از هشت، تا سقف اسلایدر (P5-D276) واقعاً چیزی را ببُرد.
 */
$cities_html = r( 20, 'tp_slider', array( 'rel' => 'places' ) );

t( 'places: کارت اول مرکز استان است، با برچسب «مرکز استان»  P5-D220',
	(bool) preg_match( '#<a class="tp-card-slide" href="https://topersia\.com/isfahan/"><div class="tp-card-slide__art" aria-hidden="true"><span class="tp-card-slide__no">01</span></div><div class="tp-card-slide__body"><span class="tp-card-slide__tag">مرکز استان</span>#u', $cities_html ),
	'کارت مرکز نیست' );

t( 'places: شماره لاتینِ دورقمی است، نه فارسی  P5-D215',
	false === strpos( $cities_html, '<span class="tp-card-slide__no">۱</span>' ),
	'شماره فارسی است' );

t( 'places: بقیه «شهر»اند و به صفحهٔ خودِ شهر پیوند دارند — نه شهرستان  P5-D220',
	7 === substr_count( $cities_html, '<span class="tp-card-slide__tag">شهر</span>' )
	&& false !== strpos( $cities_html, '<a class="tp-card-slide" href="https://topersia.com/kashan/">' )
	&& false === strpos( $cities_html, '<article class="tp-card-slide">' )
	&& false === strpos( $cities_html, 'شهرستان' ),
	substr_count( $cities_html, 'tp-card-slide__tag">شهر<' ) . ' کارت شهر' );

/* ترتیب P5-D211 — همان تابعِ مرتب‌سازی، روی همهٔ شهرهای زنجیره بی‌مرکز */
preg_match_all( '#<h3 class="tp-card-slide__title">(.*?)</h3>#u', $cities_html, $city_titles );
$city_order = array_map( function ( $id ) { return topersia_g_name( $id ); },
	array_merge( array( 21 ), array_slice( topersia_g_sort_entities( array( 27, 28, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 29 ) ), 0, 7 ) ) );

t( 'places: ترتیب P5-D211 — مرکز، بعد اولویت و جمعیت و الفبا  P5-D220',
	$city_order === $city_titles[1], implode( '،', $city_titles[1] ) );

t( 'places: اسلایدر هشت‌تای اول را نشان می‌دهد — سقف همهٔ اسلایدرها  P5-D276',
	8 === substr_count( $cities_html, 'tp-card-slide__title' ), substr_count( $cities_html, 'tp-card-slide__title' ) . ' کارت' );

t( 'places: سرفصل «شهرها» و «مرکز استان و شهرهای استان اصفهان»  P5-D220',
	false !== strpos( $cities_html, '<h2>شهرها</h2>' )
	&& false !== strpos( $cities_html, '<p class="tp-lead">مرکز استان و شهرهای استان اصفهان</p>' ) );

t( 'places: پاپ‌آپ نقشه شهرها را می‌شمارد، نه شهرستان‌ها  P5-D220',
	false !== strpos( r( 20, 'tp_map' ), '<div class="tp-map-pop" role="note"><b>استان اصفهان</b><span>مرکز: شهر اصفهان · ۱۳ شهر</span></div>' ),
	strip_tags( substr( r( 20, 'tp_map' ), strpos( r( 20, 'tp_map' ), 'tp-map-pop' ), 200 ) ) );

/*
 * هیچ پیوندی به نوعی که صفحهٔ عمومی ندارد.
 *
 * شهرستان `Infrastructure Entity` است و قالب ندارد (`P5-D172`). پیوند
 * به آن ۴۰۴ می‌دهد و برای خزنده پیوندِ شکسته است.
 */
t( 'پیوند: نوع بی‌صفحه نشانی نمی‌گیرد  P5-D210',
	'' === topersia_g_public_url( 22 ) && '' !== topersia_g_public_url( 27 ),
	'گارد نشانی کار نمی‌کند' );

/* و همان گارد روی خروجی واقعی: هیچ href به نامک شهرستان */
$page_all = r( 20, 'tp_page' );

t( 'پیوند: خروجی استان هیچ href به شهرستان ندارد  P5-D210',
	false === strpos( $page_all, 'kashan-county' ),
	'پیوند شکسته تولید شد' );

/*
 * ترتیب: اولویت نمایش، سپس جانشین نوع، سپس الفبای فارسی.  P5-D211
 *
 * `display_priority` هنوز فیلد نیست و صفر حساب می‌شود؛ این‌جا با متای
 * دستی سنجیده می‌شود تا روزی که در JetEngine ساخته شد، کد آماده باشد.
 */
$GLOBALS['tp_meta'][28]['display_priority'] = 5;
$ordered = topersia_g_sort_entities( array( 27, 28 ) );
unset( $GLOBALS['tp_meta'][28]['display_priority'] );

t( 'ترتیب: display_priority بر جمعیت مقدم است  P5-D211',
	array( 28, 27 ) === $ordered, 'ترتیب: ' . implode( ',', $ordered ) );

t( 'ترتیب: بی‌اولویت، جمعیت نزولی  P5-D211',
	array( 27, 28 ) === topersia_g_sort_entities( array( 28, 27 ) ),
	'جمعیت نزولی نشد' );

t( 'ترتیب: بی‌اولویت و بی‌جمعیت، الفبای فارسی  P5-D211',
	array( 25, 26 ) === topersia_g_sort_entities( array( 26, 25 ) ),
	'الفبا: ' . implode( ',', topersia_g_sort_entities( array( 26, 25 ) ) ) );

/*
 * آنچه در مرجع نیست، ساخته نمی‌شود.  P5-D215
 *
 * سه چیز از پرامت پیشین آمده بود و هیچ‌کدام در `.card-slide` یا
 * `.section-head` مرجع وجود ندارند.
 */
t( 'مرجع: دکمهٔ «مشاهدهٔ همه» ساخته نمی‌شود  P5-D215',
	false === strpos( $cities_html, 'tp-all-btn' ), 'هنوز هست' );

t( 'مرجع: فهرست فشردهٔ زیر اسلایدر ساخته نمی‌شود  P5-D215',
	false === strpos( $cities_html, 'class="tp-all"' ), 'هنوز هست' );

t( 'مرجع: شمارندهٔ «N مورد» در سرفصل نیست  P5-D215',
	false === strpos( $cities_html, 'tp-count' ), 'هنوز هست' );

/* سرفصل مرجع: h2 + p.lead، و a.more فقط با هاب */
t( 'مرجع: سرفصل h2 و p.lead دارد  P5-D215',
	(bool) preg_match( '#<div class="tp-section-head"><div><h2>[^<]+</h2><p class="tp-lead">[^<]+</p></div>#u', $cities_html ),
	'سرفصل مطابق مرجع نیست' );

/* از P5-D221 `.more` مودالِ فهرست را باز می‌کند — هنوز هیچ پیوندی به هابِ نبوده نیست. */
t( 'مرجع: a.more پیوند به هابِ نبوده نیست — فقط بازکنندهٔ مودالِ فهرست  P5-D215 · P5-D221',
	1 === preg_match_all( '#<a class="tp-more"#', $cities_html )
	&& false !== strpos( $cities_html, '<a class="tp-more" href="#tp-list-places" role="button"' ),
	'پیوند به هاب نبوده تولید شد' );

/* ── P5-D221 — مودالِ فهرست با جست‌وجو ───────────────────────────── */
$p20_all = r( 20, 'tp_page' );

$list_open = array(
	'places'      => 'همه شهرها ←',
	'attractions' => 'همه جاذبه‌ها ←',
	'culture'     => 'همه فرهنگ ←',
);

/*
 * P5-D276: اسلایدرِ بیش از هشت عضو (شهرها — چهارده) پیوند و مودال می‌گیرد،
 * هشت یا کمتر (جاذبه‌ها — سه) هیچ‌کدام؛ فرهنگ شبکه است و همیشه دارد.
 */
$list_bad = array();
foreach ( $list_open as $lk => $label ) {
	$want   = 'attractions' === $lk ? 0 : 1;
	$opener = '<a class="tp-more" href="#tp-list-' . $lk . '" role="button" aria-haspopup="dialog" aria-controls="tp-list-' . $lk . '" data-tp-list-open>' . $label . '</a>';
	if ( $want !== substr_count( $p20_all, $opener ) ) { $list_bad[] = $lk . ': بازکننده'; }
	if ( $want !== substr_count( $p20_all, '<div class="tp-gallery-modal tp-gallery-modal--list" id="tp-list-' . $lk . '" role="dialog" aria-modal="true"' ) ) { $list_bad[] = $lk . ': مودال'; }
}

t( 'فهرست: «همه شهرها / فرهنگ ←» هر کدام یک مودال؛ جاذبه‌های سه‌تایی نه پیوند نه مودال — شاسیِ گالری  P5-D221 · P5-D276',
	! $list_bad, implode( ' · ', $list_bad ) );

$list_places = preg_match( '#<div class="tp-gallery-modal tp-gallery-modal--list" id="tp-list-places".*?</div></div>(?=<div class="tp-gallery-modal|$)#su', $p20_all, $lpm ) ? $lpm[0] : '';

t( 'فهرست: سرِ چسبان با عنوان، شمار، جست‌وجو و بستن  P5-D221',
	/* ۴٫۴٫۲: شمار هر دو شکلش را (همه · پیداشده از همه) به زبان صفحه روی خودش دارد */
	(bool) preg_match( '#<div class="tp-gallery-modal__head"><div><h3 class="tp-gallery-modal__title" id="tp-list-places-title">شهرها<small data-tp-list-count data-tp-count-all="۱۴ مورد" data-tp-count-of="%1\$s از %2\$s">۱۴ مورد</small></h3></div><input class="tp-list__q" type="search"[^>]* data-tp-list-q><button class="tp-gallery-modal__close" type="button" data-tp-close#u', $list_places ),
	substr( strip_tags( $list_places ), 0, 120 ) );

/* ۴٫۳٫۰ بند ۱۰: برچسب نوعِ هم‌نوع‌ها رفت؛ شهر جمعیتش را با رقم فارسی و «نفر» می‌گیرد. */
t( 'فهرست: همهٔ چهارده شهر — بیش از هشتِ اسلایدر — با پیوندِ HTML، نه JSON  P5-D221 · P5-D276',
	14 === substr_count( $list_places, '<li class="tp-list__item">' )
	&& false !== strpos( $list_places, '<a class="tp-list__card" href="https://topersia.com/kashan/"><b>کاشان</b><span class="tp-list__tag">۳۰۴٬۴۸۷ نفر</span></a>' )
	&& false === strpos( $list_places, '<span class="tp-list__tag">شهر</span>' )
	&& false === strpos( $list_places, '<span class="tp-list__tag">مرکز استان</span>' )
	&& false === strpos( $list_places, 'data-tp-shots' ) && ! preg_match( '/data-tp-list="\s*[\[{]/', $list_places ),
	substr_count( $list_places, '<li class="tp-list__item">' ) . ' عضو' );

t( 'فهرست: فرهنگ به خانواده‌ها گروه می‌شود  P5-D221',
	(bool) preg_match( '#id="tp-list-culture".*?<div class="tp-list__block"><h4 class="tp-list__fam">صنایع دستی</h4><ul class="tp-list__grid"><li class="tp-list__item"><a class="tp-list__card" href="https://topersia.com/mina/"><b>میناکاری</b>#su', $p20_all ) );

t( 'فهرست: پیام «موردی پیدا نشد» آماده و پنهان — یکی در هر مودال (شهرها، فرهنگ)  P5-D221',
	2 === substr_count( $p20_all, '<p class="tp-list__empty" role="status" hidden>موردی پیدا نشد</p>' ) );

t( 'فهرست: بی‌بخش، بی‌مودال — شهر بخشِ شهرها ندارد، مودالش هم نه  P5-D221 · P5-D192',
	false === strpos( r( 30, 'tp_page' ), 'id="tp-list-places"' ) );

t( 'فهرست: بالای نوار بخش‌ها و نوار موبایل — همان z-index ۶۰۰ شاسی  P5-D221',
	false !== strpos( $css, 'display:none;z-index:600;' ) && false !== strpos( $css, '.tp-snav{position:sticky;top:var(--tp-snav-top);z-index:110;' )
	&& false !== strpos( $css, '.tp-mnav{position:fixed;inset-inline:0;inset-block-end:0;z-index:70;' ) );

/* اسکریپت همین‌جا خوانده می‌شود؛ بخش P5-D223 پایین‌تر خودش دوباره می‌خواند. */
$js_zones = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-zones.js' );
$js_list  = preg_match( '#/\* ══ مودالِ فهرست — P5-D221.*?/\* ══ بلوک هوش مصنوعی#s', $js_zones, $jl ) ? $jl[0] : '';

t( 'فهرست: قفل بدنه، Esc، دام فوکوس و فوکوس نخست روی جست‌وجو — همان مدیر مودال  P5-D221',
	false !== strpos( $js_list, 'trapFocus( box );' ) && false !== strpos( $js_list, 'openModal( box, q );' )
	&& false !== strpos( $js_list, "if ( e.target === box ) { closeModal( box ); }" ) );

/*
 * یکدست‌سازی — خودِ تابع جاوااسکریپت اجرا می‌شود، نه بازنویسی‌اش.
 * هر جفت باید یکی شود و جفتِ شاهد نباید.
 */
/*
 * ۴٫۴٫۱: قاعده‌ها جدولِ هسته‌اند (`topersia_search_fold_js_rules()` · P5-D265) و
 * tpNorm فقط اجراکنندۀ آن است؛ پس جدول از خودِ هسته به همان صفحۀ ساختگی
 * می‌رسد که مرورگر از `TopersiaG.fold` می‌گیرد.
 */
if ( ! function_exists( 'topersia_search_fold_js_rules' ) ) {
	require_once dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-fold.php';
}
$norm_fn = preg_match( '#function tpHex\( n \).*?\n\tfunction tpNorm\( s \) \{.*?\n\t\}\n#s', $js_zones, $nf )
	? 'var window={TopersiaG:{fold:' . json_encode( topersia_search_fold_js_rules() ) . '}};' . $nf[0] : '';
$norm_cases = array(
	array( 'اِصفَهان', 'اصفهان', true ),
	array( 'كاشان', 'کاشان', true ),
	array( 'علي', 'علی', true ),
	array( 'نوش‌آباد', 'نوش آباد', true ),
	array( 'نوش‌آباد', 'نوشآباد', true ),
	array( 'همهٔ', 'همه', true ),
	array( '۱۲', '١٢', true ),
	array( '۱۲', '12', true ),
	array( 'کاشان', 'کرمان', false ),
);
$norm_js  = $norm_fn . 'const C=' . json_encode( $norm_cases, JSON_UNESCAPED_UNICODE ) . ';'
	. 'process.stdout.write(JSON.stringify(C.map(c=>(tpNorm(c[0])===tpNorm(c[1]))===c[2])));';
$norm_tmp = tempnam( sys_get_temp_dir(), 'tpn' ) . '.js';
file_put_contents( $norm_tmp, $norm_js );
$norm_out = json_decode( (string) shell_exec( 'node ' . escapeshellarg( $norm_tmp ) ), true );
@unlink( $norm_tmp );

t( 'فهرست: یکدست‌سازی — ي/ى←ی · ك←ک · نیم‌فاصله · اِعراب · رقم فارسی/عربی/لاتین  P5-D221',
	'' !== $norm_fn && is_array( $norm_out ) && count( $norm_cases ) === count( array_filter( $norm_out ) ),
	is_array( $norm_out ) ? implode( ',', array_map( 'json_encode', $norm_out ) ) : 'node اجرا نشد' );

/* همهٔ موارد در DOM‌اند، بی‌پنهان‌سازی — مگر گروه سقف داشته باشد (شهرها، P5-D220) */
t( 'مرجع: گروهِ بی‌سقف همهٔ موارد را در اسلایدر می‌آورد  P5-D215',
	3 === substr_count( r( 20, 'tp_slider', array( 'rel' => 'attractions' ) ), 'tp-card-slide__title' ), 'شمار کارت' );

/*
 * ── هر ویژگی‌ای که user-core تعیین می‌کند، ما صریح اعلام کنیم ────
 *
 * لنگرِ `P5-D201` وزن را بالا می‌برد ولی فقط برای ویژگی‌هایی که **اعلام
 * کرده‌ایم**. هرچه اعلام نکنیم، قاعدهٔ آن‌ها بی‌رقیب می‌نشیند:
 *
 *   .tp-card{ padding:21px; margin-bottom:16px }
 *
 * و ما هیچ‌کدام را نداشتیم — بدنهٔ کارت از لبه فاصله می‌گرفت. همین الگو
 * روی `.tp-hero` بیست‌وچهار پیکسل padding گذاشته بود.
 *
 * مقایسه روی «فاعل» سلکتور است: قاعده‌ای که خودِ آن کلاس را هدف می‌گیرد،
 * نه فرزندش.  P5-D204
 */
$uc_props_file = dirname( __DIR__ ) . '/reference/user-core.css';

if ( ! is_file( $uc_props_file ) ) {

	t( 'تصادم: هر ویژگیِ user-core را صریح اعلام می‌کنیم  P5-D204',
		false, 'reference/user-core.css نیست' );

} else {

	/**
	 * ویژگی‌های سرشاخه — `padding-bottom` را `padding` پوشش می‌دهد.
	 *
	 * ضمناً نام منطقی و فیزیکی یکی شمرده می‌شوند: `min-inline-size` همان
	 * `min-width` است در نوشتارِ افقی، و ما همه‌جا نام منطقی می‌نویسیم.
	 * بی‌این نگاشت، گارد اعلامِ درست را کمبود می‌بیند و آدم را به نوشتنِ
	 * اعلانِ تکراری می‌کشاند.
	 */
	$root_prop = function ( $p ) {

		$same = array(
			'inline-size'     => 'width',
			'min-inline-size' => 'min-width',
			'max-inline-size' => 'max-width',
			'block-size'      => 'height',
			'min-block-size'  => 'min-height',
			'max-block-size'  => 'max-height',
		);

		if ( isset( $same[ $p ] ) ) { return $same[ $p ]; }

		/* `border-radius` پیش از `border` بررسی می‌شود */
		foreach ( array( 'padding', 'margin', 'border-radius', 'border', 'background', 'font', 'gap', 'inset', 'overflow' ) as $head ) {
			if ( 0 === strpos( $p, $head ) ) { return $head; }
		}

		return $p;
	};

	/** فاعلِ سلکتور: آخرین جزء. */
	$subject_of = function ( $sel ) {
		$parts = preg_split( '/[\s>+~]+/', trim( $sel ) );
		return (string) end( $parts );
	};

	/*
	 * کلاس‌هایی که واقعاً رندر می‌کنیم.
	 *
	 * قاعده‌ای مثل `.tp-body > .tp-main` نیایی می‌خواهد که ما نمی‌سازیم،
	 * پس هرگز روی صفحهٔ ما نمی‌نشیند و اعلام‌کردنش فقط نویز است.
	 */
	$rendered = array();
	if ( preg_match_all( '/class="([^"]+)"/', $all, $cm ) ) {
		foreach ( $cm[1] as $attr ) {
			foreach ( preg_split( '/\s+/', trim( $attr ) ) as $one ) {
				if ( '' !== $one ) { $rendered[ $one ] = true; }
			}
		}
	}

	$collect = function ( $css_text, $cls, $strip_scope ) use ( $root_prop, $subject_of, $rendered ) {

		if ( $strip_scope ) { $css_text = str_replace( TP_SCOPE . ' ', '', $css_text ); }
		$css_text = preg_replace( '!/\*.*?\*/!s', '', $css_text );

		$found = array();

		if ( ! preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css_text, $rr, PREG_SET_ORDER ) ) { return $found; }

		foreach ( $rr as $rule ) {
			foreach ( explode( ',', $rule[1] ) as $sel ) {

				$subject = $subject_of( $sel );

				/* فاعل باید دقیقاً همین کلاس باشد، نه حالت و نه فرزند */
				if ( ! preg_match( '/^\.' . preg_quote( $cls, '/' ) . '(?![\w-])/', ltrim( $subject, ' ' ) ) ) { continue; }
				if ( preg_match( '/:(hover|focus|active|focus-visible)/', $subject ) ) { continue; }

				/* نیایی که نمی‌سازیم ⇒ این قاعده هرگز روی صفحهٔ ما نمی‌نشیند */
				$reach = true;
				if ( preg_match_all( '/\.([\w-]+)/', $sel, $cc ) ) {
					foreach ( $cc[1] as $one ) {
						if ( $one === $cls ) { continue; }
						if ( ! isset( $rendered[ $one ] ) ) { $reach = false; break; }
					}
				}
				if ( ! $reach ) { continue; }

				foreach ( explode( ';', $rule[2] ) as $decl ) {
					$decl = trim( $decl );
					if ( '' === $decl || false === strpos( $decl, ':' ) ) { continue; }
					$p = strtolower( trim( substr( $decl, 0, strpos( $decl, ':' ) ) ) );
					if ( '' === $p || 0 === strpos( $p, '--' ) ) { continue; }
					$found[ $root_prop( $p ) ] = $root_prop( $p );
				}
			}
		}

		return $found;
	};

	$uc_text = file_get_contents( $uc_props_file );
	$gaps    = array();

	foreach ( $uc_allow as $cls ) {

		$theirs = $collect( $uc_text, $cls, false );
		$ours   = $collect( $css, $cls, true );

		$miss = array_values( array_diff_key( $theirs, $ours ) );

		if ( $miss ) { $gaps[] = '.' . $cls . ' ← ' . implode( ' ', $miss ); }
	}

	t( 'تصادم: هر ویژگیِ user-core را صریح اعلام می‌کنیم  P5-D204',
		empty( $gaps ), implode( '   ', $gaps ) );
}

/* =========================================================================
   ۴٫۲٫۰ — انتقال مستقیم قالب مرجع.  P5-D219
   ========================================================================= */

echo "\nانتقال مرجع — P5-D219\n" . str_repeat( '─', 78 ) . "\n";

/*
 * `rem` مرجع روی ریشهٔ ۱۶ کشیده شده؛ سایت ریشه را ۱۷ می‌کند. هیچ `rem`
 * خامی نباید در نیمهٔ منتقل‌شده بماند، وگرنه نسبت سرفصل به بدنه به هم
 * می‌خورد. آزمون پذیرش با ریشهٔ ۱۷ این را ۸۵۹ ناهمخوانی نشان داد.
 */
preg_match_all( '/(?<![\w.-])\d*\.?\d+rem\b/', preg_replace( '#/\*.*?\*/#s', '', $css_port ), $raw_rem );

t( 'قالب: هیچ `rem` خامی در نیمهٔ منتقل‌شده نیست  P5-D219',
	empty( $raw_rem[0] ), implode( ' · ', array_slice( $raw_rem[0], 0, 5 ) ) );

t( 'قالب: `--tp-rem` ریشهٔ ۱۶ ضربدر اندازهٔ انتخابی خواننده است  P5-D219',
	false !== strpos( $css, '--tp-rem:calc(16px * var(--tp-scale,1))' ) );

/* برخورد نام — مولد خودش می‌ایستد؛ این گارد همان را از بیرون می‌سنجد. */
t( 'قالب: هیچ برخوردِ پیشوندِ حل‌نشده‌ای نیست  P5-D219',
	empty( tp_unresolved_collisions( $ref_css, $COLLIDE, $DROP ) ),
	implode( ' · ', tp_unresolved_collisions( $ref_css, $COLLIDE, $DROP ) ) );

/* شیوه‌نامهٔ کهنه رفته و هیچ‌جا صدا زده نمی‌شود */
t( 'قالب: topersia-g-zones.css حذف شده و بارگذاری نمی‌شود  P5-D219',
	! is_file( TP_PLUGIN_DIR . 'assets/topersia-g-zones.css' )
	&& false === strpos( $plugin_src, 'assets/topersia-g-zones.css' )
	&& false !== strpos( $plugin_src, 'topersia-g-template.css' ) );

/* ── ترتیب دو ستون، عیناً بند ۲ ───────────────────────────────── */
$p20 = r( 20, 'tp_page' );

$sec_order = function ( $html, $col ) {
	$re = 'main' === $col
		? '#<div class="tp-main-col">(.*?)</div><aside#s'
		: '#<aside class="tp-rail"[^>]*>(.*?)</aside>#s';
	if ( ! preg_match( $re, $html, $m ) ) { return array(); }
	preg_match_all( '#<section id="tp-([a-z-]+)"#', $m[1], $ids );
	return $ids[1];
};

t( 'چیدمان: ستون اصلی ai · overview · gallery · attractions · places · culture · faq · reviews',
	array( 'ai', 'overview', 'gallery', 'attractions', 'places', 'culture', 'faq', 'reviews' ) === $sec_order( $p20, 'main' ),
	implode( ' · ', $sec_order( $p20, 'main' ) ) );

t( 'چیدمان: ستون کناری به ترتیب بند ۲',
	array( 'actions', 'map', 'season', 'weather', 'audio', 'geo', 'badges', 'nature', 'ancient', 'villages', 'practical', 'neighbors', 'routes', 'sources' ) === $sec_order( $p20, 'rail' ),
	implode( ' · ', $sec_order( $p20, 'rail' ) ) );

/* ── پوسته و سه کارتِ شکل‌دار ─────────────────────────────────── */
t( 'پوسته: نوار آمار داخل `.tp-wrap` است، مثل مرجع',
	false !== strpos( $p20, '<div class="tp-wrap tp-stats-wrap">' ) );

t( 'پوسته: main > wrap > crumb + content-grid > main-col + rail',
	(bool) preg_match( '#<main id="tp-main" class="tp-main"><div class="tp-wrap"><p class="tp-crumb".*?<div class="tp-content-grid"><div class="tp-main-col">#s', $p20 ) );

/* ۴٫۳٫۰: نام «اطلاعات سفر»، پیوند واقعی (بند ۰) و ارث‌بری از کشور (بند ۸) — ترتیب P5-D211 الفبایی */
t( 'کارت: اطلاعات سفر `.tp-practical-grid` با پیکان و پیوند — خودِ استان + ارث از کشور  P5-D233',
	false !== strpos( $p20, '<h3 id="tp-practicalTitle">اطلاعات سفر</h3>' )
	&& false !== strpos( $p20, '<div class="tp-practical-grid">'
		. '<a href="https://topersia.com/dress-code/">پوشش در اماکن عمومی <span>→</span></a>'
		. '<a href="https://topersia.com/calendar/">تقویم رسمی ایران <span>→</span></a>'
		. '<a href="https://topersia.com/health/">سلامت و ایمنی <span>→</span></a>'
		. '<a href="https://topersia.com/currency/">واحد پول <span>→</span></a></div>' ) );

t( 'کارت: هم‌مرزها `.tp-neighbors` با جهتِ مشتق از مختصات',
	false !== strpos( $p20, '<div class="tp-neighbors"><a href="https://topersia.com/yazd-province/"><span class="tp-n-dir">شرق</span><b>یزد</b></a>' ) );

/* ۴٫۳٫۰ بند ۰: مسیر صفحه دارد و پیوند می‌گیرد. ۴٫۴٫۱: کاشی P5-D244 — نام، زیرش شهرهای همین استان.  P5-D269 */
t( 'کارت: مسیرها کاشی `.tp-tile` با شهرهای همین استان زیر نام، با پیوند  P5-D269',
	false !== strpos( $p20, '<div class="tp-tiles" data-tp-tiles><a href="https://topersia.com/silk-road/" class="tp-tile"><b>راه ابریشم</b><span class="tp-tile__sub">کاشان · نطنز</span></a>' )
	&& false === strpos( $p20, 'class="tp-routes"' ),
	preg_match( '#<section id="tp-routes".*?</section>#su', $p20, $rtm ) ? mb_substr( $rtm[0], 0, 400 ) : 'بخش مسیرها نیست' );

t( 'جهت: شمال و جنوب از دو مختصات درست درمی‌آیند',
	'شمال' === topersia_g_bearing_label( '30, 50', '32, 50' )
	&& 'جنوب' === topersia_g_bearing_label( '32, 50', '30, 50' )
	&& '' === topersia_g_bearing_label( '32, 50', '' ) );

t( 'پیوند: هیچ `href=""` در خروجی نیست — نوع بی‌صفحه `<a>` بی‌نشانی است  P5-D210',
	false === strpos( $p20, 'href=""' ) );

/* ── نوار کنش ─────────────────────────────────────────────────── */
t( 'نوار کنش: `section#tp-actions` با کلاس کارتیِ مرجع',
	false !== strpos( $p20, '<section id="tp-actions" class="tp-rail-card tp-actionbar tp-actionbar--rail"' ) );

t( 'نوار کنش: سه کنش با آیکون، بعد منبع ترجیحی، بعد پیام — ترتیب مرجع',
	(bool) preg_match( '#data-tp-share aria-expanded="false" aria-controls="tp-share-panel"><svg.*?data-tp-save aria-pressed="false"><svg.*?data-tp-follow aria-pressed="false"><svg.*?</div><a class="tp-gps-btn".*?<p class="tp-act__note"#s', $p20 ) );

/* ── شش بخشی که ساخته نشده بود ────────────────────────────────── */
/* ── P5-D230 — «دربارهٔ …» از متن اصلی صفحه ──────────────────────────── */
$ov20      = preg_match( '#<section id="tp-overview".*?</section>#su', $p20, $ovm ) ? $ovm[0] : '';
/* ۴٫۴٫۱: بدنۀ «درباره» تراز دوطرفه است — کلاس tp-justify روی خودِ article.  P5-D270 */
$ov20_body = preg_match( '#<article class="tp-longform tp-justify"[^>]*>(.*?)</article>#su', $ov20, $ovb ) ? $ovb[1] : '';

t( 'درباره: متن از overview_text — کامل، با سرفصل‌های خودش یک پله پایین؛ بعد فیلدهای details  P5-D230',
	0 === strpos( $ov20_body, '<p>استان اصفهان در <strong>مرکز فلات ایران</strong> است.</p><h3>جغرافیا و تقسیمات</h3><p>مرکز استان شهر اصفهان است.</p><ul><li>کاشان</li><li>نطنز</li></ul>' )
	&& (bool) preg_match( "#</ul><h3>محدودۀ جغرافیایی</h3><p>از شمال#u", $ov20_body ),
	mb_substr( strip_tags( $ov20_body ), 0, 60 ) );

t( 'درباره: short_summary دیگر در این بخش نیست  P5-D230',
	'' !== $ov20 && false === strpos( $ov20, 'سومین استان پهناور' ) && false === strpos( $ov20, 'itemprop="description"' ) );

t( 'درباره: هیچ class و id و style از متنِ چسبانده نمی‌ماند؛ سرفصل h2 متن در بخشِ h2 نمی‌نشیند  P5-D230 · P5-D180',
	'' !== $ov20_body && ! preg_match( '#\s(class|id|style)=#', $ov20_body ) && false === strpos( $ov20_body, '<h2' )
	&& false === strpos( $ov20_body, '<div' ) );

$ov_keep = $GLOBALS['tp_meta'][20]['overview_text'];
$ov_long = str_repeat( 'متنِ بلند بی‌کوتاه‌سازی. ', 900 );
$GLOBALS['tp_meta'][20]['overview_text'] = "بند یکم با <em>تأکید</em>.\n\n" . $ov_long;
$ov_plain = r( 20, 'tp_overview' );
$GLOBALS['tp_meta'][20]['overview_text'] = '<script>alert(1)</script><a href="javascript:alert(1)">پیوند</a>';
$ov_evil = r( 20, 'tp_overview' );
$GLOBALS['tp_meta'][20]['overview_text'] = $ov_keep;

t( 'درباره: متنِ بی‌بلوک با سطر خالی پاراگراف می‌شود و هیچ‌جا کوتاه نمی‌شود  P5-D230',
	false !== strpos( $ov_plain, '<p>بند یکم با <em>تأکید</em>.</p><p>' . trim( $ov_long ) . '</p>' ) );

t( 'درباره: script برداشته می‌شود و پیوند javascript نشانی نمی‌گیرد',
	false === stripos( $ov_evil, '<script' ) && false === stripos( $ov_evil, 'javascript:' ) );

$ov30_details = r( 30, 'tp_overview' );
$ta_keep      = $GLOBALS['tp_meta'][30]['transport_access'];
unset( $GLOBALS['tp_meta'][30]['transport_access'] );
$ov30_none    = r( 30, 'tp_overview' );
$GLOBALS['tp_meta'][30]['transport_access'] = $ta_keep;

t( 'درباره: بی‌متن اصلی، فقط details اگر پر است؛ هیچ‌کدام — بخش نمی‌آید  P5-D230 · P5-D192',
	(bool) preg_match( '#<article class="tp-longform tp-justify"[^>]*><h3>دسترسی و حمل‌ونقل</h3><p>فرودگاه#u', $ov30_details )
	&& '' === $ov30_none );

t( 'درباره: هیچ ::first-letter در شیوه‌نامه — حرفِ اولِ بزرگ رفت  P5-D230',
	false === stripos( $css, 'first-letter' ) && false !== strpos( $gen_src_all = file_get_contents( dirname( __DIR__ ) . '/scripts/tp-template-gen.php' ), "stripos( \$sel, 'first-letter' )" ) );

t( 'geo: کارت جغرافیا دیگر فیلدهای details را ندارد',
	false === strpos( r( 20, 'tp_facts' ), 'محدودهٔ جغرافیایی' )
	&& false === strpos( r( 20, 'tp_facts' ), 'tp-details' ) );

t( 'ai: بی‌ai_summary بخش نمی‌آید',
	'' === r( 40, 'tp_ai' ) && '' !== r( 20, 'tp_ai' ) );

/* ۴٫۳٫۰ بند ۴: موتورها فقط برای واردشده؛ مهمان جایشان دعوت به ثبت‌نام می‌بیند.  P5-D235 */
$GLOBALS['tp_user'] = 7;
$ai_in = r( 20, 'tp_ai' );
$GLOBALS['tp_user'] = 0;
t( 'ai: دکمهٔ موتور — فقط واردشده — همان موتور را با نشانی همین صفحه باز می‌کند؛ مهمان نه',
	false !== strpos( $ai_in, 'https://claude.ai/new?q=' )
	&& false !== strpos( $ai_in, rawurlencode( 'https://topersia.com/isfahan-province/' ) )
	&& false === strpos( $p20, 'https://claude.ai/new?q=' ) && false === strpos( $p20, 'class="tp-ai__engine" href="https://chatgpt' ) );

/* «برنامهٔ سفر فقط اگر فیلدش هست» — و در export هیچ فیلد برنامه‌ای نیست. */
$skin_src = file_get_contents( dirname( __DIR__ ) . '/reference/Jetengine_Skin.json' );

t( 'ai: بی‌خروجیِ افزونهٔ خلاصه‌ساز، برنامهٔ سفر ساخته نمی‌شود — JetEngine فیلدش را ندارد',
	false === strpos( r( 20, 'tp_ai' ), 'data-ai="plan"' )
	&& ! preg_match( '/"name":"[a-z_]*(plan|itinerar)[a-z_]*"/', $skin_src ) );

t( 'ai: کلید خلاصه در JetEngine هست — نه نامِ حدسی',
	false !== strpos( $skin_src, '"name":"ai_summary"' ) );

t( 'news: تا نوع tpc_news نیست، رندر نمی‌شود',
	'' === r( 20, 'tp_news' ) );

t( 'reviews: میانگین، توزیع پنج‌تایی و دو نظر آخر',
	(bool) preg_match( '#<div class="tp-rate__score">۴\.۶</div>#u', $p20 )
	&& 5 === substr_count( $p20, '<div class="tp-bar">' )
	&& 2 === substr_count( $p20, '<div class="tp-cmt">' ) );

t( 'reviews: بی‌نظر، بخش نمی‌آید', '' === r( 30, 'tp_reviews' ) );

/*
 * خواندن JetReviews — تابع خالص، با ردیف‌هایی به شکل جدول زنده.
 * امتیاز در JetReviews درصد است (۱۰۰ = پنج ستاره)؛ اگر روزی ستاره
 * ذخیره شد، همان تابع باید درست بخواند.
 */
$rv = topersia_g_reviews_from_rows( array(
	array( 'author' => 'g-17', 'date' => '2026-09-20', 'content' => 'عالی بود.',  'rating' => 100, 'approved' => '1' ),
	array( 'author' => 'g-18', 'date' => '2026-09-19', 'content' => '',            'rating' => 80,  'approved' => 'true' ),
	array( 'author' => 'g-19', 'date' => '2026-09-18', 'content' => 'نباید بیاید', 'rating' => 20,  'approved' => '0' ),
	array( 'author' => 'g-20', 'date' => '2026-09-17', 'content' => 'خوب بود.',   'rating' => 80,  'approved' => '1' ),
), array( 'g-17' => 'مریم ک.', 'g-20' => 'سهیل ر.' ) );

t( 'reviews: فقط تأییدشده‌ها شمرده می‌شوند (1 و true)', 3 === $rv['count'], (string) $rv['count'] );
t( 'reviews: امتیاز درصدی به ستاره می‌رسد — ۱۰۰·۸۰·۸۰ ← ۴٫۳', 4.3 === $rv['avg'], (string) $rv['avg'] );
t( 'reviews: توزیع — یک پنج‌ستاره، دو چهارستاره', 1 === $rv['dist'][5] && 2 === $rv['dist'][4] && 0 === $rv['dist'][1] );
t( 'reviews: دو نظر آخرِ بامتن، با نام مهمان',
	2 === count( $rv['last'] ) && 'مریم ک.' === $rv['last'][0]['author'] && 'سهیل ر.' === $rv['last'][1]['author'] );
t( 'reviews: امتیازِ ستاره‌ای هم درست خوانده می‌شود',
	4.5 === topersia_g_reviews_from_rows( array(
		array( 'author' => '', 'content' => 'x', 'rating' => 5, 'approved' => '1' ),
		array( 'author' => '', 'content' => 'y', 'rating' => 4, 'approved' => '1' ),
	) )['avg'] );
t( 'reviews: بی‌تأییدشده، هیچ',
	0 === topersia_g_reviews_from_rows( array( array( 'rating' => 100, 'approved' => '0', 'content' => 'z' ) ) )['count'] );

t( 'attractions: زنجیرهٔ ۷۲→۷۳→۸۴/۸۶/۸۸ جاذبه‌های شهرهای استان را می‌آورد',
	false !== strpos( $p20, 'میدان نقش جهان' ) && false !== strpos( $p20, 'موزهٔ آران' ) );

t( 'attractions: پاورقی شهر · سال ثبت',
	(bool) preg_match( '#<div class="tp-card-slide__foot"><span>کاشان</span><span>ثبت ۱۹۷۹</span></div>#u', $p20 ) );

/* ── گالری عیناً مرجع ─────────────────────────────────────────── */
t( 'گالری: پنج کاشی، اولی بزرگ، «+N تصویر» روی آن',
	5 === substr_count( $p20, 'class="tp-gallery-main__item' )
	&& false !== strpos( $p20, 'tp-gallery-main__item tp-gallery-main__item--large' )
	&& false !== strpos( $p20, '<span class="tp-gallery-main__count">+۱۳ تصویر</span>' ) );

t( 'گالری: `.more` متنیِ کوچک، نه دکمهٔ پهن',
	false !== strpos( $p20, '<button class="tp-more" type="button" data-tp-gallery-open>همه تصاویر ←</button>' ) );

t( 'گالری: الگوی کاشی پهن مودال — اولی و نهمی',
	(bool) preg_match_all( '#tp-gallery-modal__item tp-gallery-modal__item--wide#', $p20, $wide ) && 3 === count( $wide[0] ) );

t( 'گالری: هر کاشی شناسهٔ پیوستش را دارد و فهرست اندازهٔ کامل روی مودال است',
	false !== strpos( $p20, 'data-tp-shot="601"' ) && false !== strpos( $p20, '601-full.jpg' ) );

/* ── ۴٫۲٫۱ بند ۸ — عکس تمام‌صفحه ─────────────────────────────────── */
t( 'گالری: سطر دوم کاشی فقط توضیحِ پیوست است — عنوان تکرار نمی‌شود  بند ۸',
	false !== strpos( $p20, '<span class="tp-gallery-main__label">میدان نقش جهان <span class="tp-gallery-main__meta">· میدان امام، ۱۶۰۲</span></span>' )
	&& false !== strpos( $p20, '<span class="tp-gallery-main__label">چهل‌ستون</span>' )
	&& false !== strpos( $p20, '<span class="tp-gallery-main__label">تصویر 603</span>' )
	&& false === strpos( $p20, 'چهل‌ستون <span class="tp-gallery-main__meta">' ) );

$solo = preg_match( '#<div class="tp-gallery-modal tp-gallery-modal--solo" id="tpGallerySolo".*?</figure></div>#su', $p20, $sm8 ) ? $sm8[0] : '';

t( 'تمام‌صفحه: «قبلی» سپس «بعدی» در DOM؛ راست‌به‌چپ بعدی «‹» و قبلی «›»، هر نویسه در dir=ltr؛ زیرنویس زیر عکس  P5-D243 · بند ۸',
	false !== strpos( $solo, '<img class="tp-solo__img" alt="" decoding="async" draggable="false" width="1600" height="1067">'
		. '<button class="tp-solo__nav tp-solo__nav--prev" type="button" data-tp-shot-prev aria-label="قبلی"><span dir="ltr">›</span></button>'
		. '<button class="tp-solo__nav tp-solo__nav--next" type="button" data-tp-shot-next aria-label="بعدی"><span dir="ltr">‹</span></button>' )
	&& false !== strpos( $solo, '<figcaption class="tp-solo__cap"><b data-tp-solo-title></b><span data-tp-solo-caption></span></figcaption>' )
	&& false !== strpos( $solo, '<small data-tp-solo-count></small>' ),
	substr( strip_tags( $solo ), 0, 80 ) );

/* هیچ نویسهٔ پیکانِ خامی — «‹» یا «›» بیرون از span[dir=ltr] — در دکمه‌ها نیست؛
   در متن راست‌به‌چپ همان نویسه برعکس دیده می‌شود.  P5-D226 · P5-D243 */
t( 'فلش‌ها: هر «‹» و «›» دکمه‌های اسلایدر و تمام‌صفحه درون span[dir=ltr] است  P5-D243',
	(bool) preg_match_all( '#<button[^>]*(?:data-dir|data-tp-shot-(?:next|prev))[^>]*>(.*?)</button>#su', $p20, $arrow_btn )
	&& count( $arrow_btn[1] ) >= 4
	&& count( $arrow_btn[1] ) === count( array_filter( $arrow_btn[1], function ( $in ) {
		return (bool) preg_match( '#^<span dir="ltr">[‹›]</span>$#u', $in );
	} ) ) );

t( 'تمام‌صفحه: «بعدی» سرِ پایانِ خط (راست‌به‌چپ: لبهٔ چپ)، «قبلی» سرِ آغاز — contain، تمام‌عرض، ۸۰vh؛ زیرنویس در موبایل هم  P5-D243 · بند ۸',
	(bool) preg_match( '/\.tp-page \.tp-solo__img\{\s*display:block;inline-size:100%;block-size:auto;max-block-size:80vh;\s*object-fit:contain;/', $css )
	&& (bool) preg_match( '/\.tp-page \.tp-solo__nav\{\s*position:absolute;top:50%;transform:translateY\(-50%\);/', $css )
	&& false !== strpos( $css, '.tp-page .tp-solo__nav--next{inset-inline-end:12px}' )
	&& false !== strpos( $css, '.tp-page .tp-solo__nav--prev{inset-inline-start:12px}' )
	&& false !== strpos( $css, '.tp-page .tp-solo__nav--next{inset-inline-end:6px}' )
	&& false !== strpos( $css, '.tp-page .tp-solo__nav--prev{inset-inline-start:6px}' )
	/* فقط توضیحِ خالی پنهان می‌شود؛ خودِ زیرنویس و عنوانش هرگز */
	&& ! preg_match( '/\.tp-solo__cap(\s+b)?\{[^}]*display:none/', $css ) );

t( 'تمام‌صفحه: فهرست عکس‌ها عنوان و توضیح هر پیوست را دارد  بند ۸',
	false !== strpos( $p20, '&quot;t&quot;:&quot;میدان نقش جهان&quot;,&quot;c&quot;:&quot;میدان امام، ۱۶۰۲&quot;' )
	&& false !== strpos( $p20, '&quot;t&quot;:&quot;چهل‌ستون&quot;,&quot;c&quot;:&quot;&quot;' ) );

/* ── P5-D222 — صوت و ویدیو ───────────────────────────────────────── */
$audio20 = preg_match( '#<div class="tp-audio">.*?</div></div>#su', $p20, $am ) ? $am[0] : '';

t( 'صوت: پروندهٔ mp3 همین‌جا پخش می‌شود — آیکون صدا · عنوان · مدت · <audio preload="none">  P5-D227 · P5-D222',
	(bool) preg_match( '#<div class="tp-audio__item"><span class="tp-audio__kind" data-tp-kind="audio"><svg[^>]*>.*?</svg><span class="tp-vh">صدا</span></span><b>روایت کوتاه استان</b><span>۴:۱۲</span><audio controls preload="none" src="https://topersia.com/a/1.mp3"></audio></div>#su', $audio20 )
	&& false === strpos( $audio20, '<a href="https://topersia.com/a/1.mp3"' ) );

$aud_card = r( 20, 'tp_audio' );

/* کیکر LISTEN & WATCH رفت.  ۴٫۳٫۰ بند ۹ · P5-D240 */
t( 'روایت: کارت «روایت صوتی و تصویری» بی‌کیکر لاتین، شناسهٔ مرجع tp-audio  P5-D227 · P5-D240',
	false !== strpos( $aud_card, '<section id="tp-audio" class="tp-rail-card" aria-labelledby="tp-audioTitle"><div class="tp-rail-card__head"><h3 id="tp-audioTitle">روایت صوتی و تصویری</h3></div>' ) );

t( 'روایت: ردیف‌های audio_narration و سپس video_sample در یک فهرست؛ نوع از خودِ رسانه  P5-D227',
	(bool) preg_match( '#data-tp-kind="audio".*?روایت کوتاه استان.*?data-tp-kind="video".*?روایت تصویری میدان.*?<a href="https://www\.aparat\.com/v/Qw7kd" data-tp-media="https://www\.aparat\.com/video/video/embed/videohash/Qw7kd/vt/frame" data-tp-media-kind="embed" data-tp-media-title="ویدیوی استان اصفهان"><span class="tp-audio__kind" data-tp-kind="video">#su', $aud_card )
	&& 3 === substr_count( $aud_card, 'class="tp-audio__kind"' )
	&& false === strpos( $aud_card, 'راهنمای نقش جهان' ) );

t( 'روایت: آیکون صدا و تصویر هر دو از مجموعهٔ ثابتِ آیکون‌اند، نه SVG جدا  P5-D227',
	'' !== topersia_g_icon( 'audio' ) && '' !== topersia_g_icon( 'video' )
	&& false !== strpos( $aud_card, topersia_g_icon( 'audio' ) ) && false !== strpos( $aud_card, topersia_g_icon( 'video' ) ) );

t( 'صوت: mp3 · m4a · ogg پخش‌کننده می‌گیرند، بقیه نه — پسوند بی‌رشتهٔ پرس‌وجو  P5-D222',
	'mp3' === topersia_g_url_ext( 'https://x.test/a/b.MP3?v=2' ) && 'm4a' === topersia_g_url_ext( 'https://x.test/n.m4a#t=3' )
	&& 'ogg' === topersia_g_url_ext( 'https://x.test/n.ogg' ) && '' === topersia_g_url_ext( 'https://x.test/podcast/' ) );

t( 'ویدیو: ردیفِ یوتیوب همان <a> مرجع است و در شاسیِ پخش باز می‌شود — youtube-nocookie  P5-D222',
	false !== strpos( $audio20, '<a href="https://www.youtube.com/watch?v=Zm9vYmFyMTI" data-tp-media="https://www.youtube-nocookie.com/embed/Zm9vYmFyMTI?autoplay=1&amp;rel=0" data-tp-media-kind="embed" data-tp-media-title="روایت تصویری میدان">' ) );

$embed_ok = array(
	'https://www.youtube.com/watch?v=dQw4w9WgXcQ'          => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0',
	'https://youtu.be/dQw4w9WgXcQ?t=42'                    => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0',
	'https://m.youtube.com/shorts/dQw4w9WgXcQ'             => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0',
	'https://www.youtube.com/watch?feature=share&v=dQw4w9WgXcQ' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&rel=0',
	'https://www.aparat.com/v/Qw7kd'                       => 'https://www.aparat.com/video/video/embed/videohash/Qw7kd/vt/frame',
	'https://aparat.com/v/Qw7kd/slug'                      => 'https://www.aparat.com/video/video/embed/videohash/Qw7kd/vt/frame',
	/* هیچ نشانیِ خامی به iframe نمی‌رسد */
	'https://evil.test/?v=dQw4w9WgXcQ'                     => '',
	'javascript:alert(1)//youtube.com/watch?v=dQw4w9WgXcQ' => '',
	'https://www.youtube.com/watch?v=short'                => '',
	'https://www.youtube.com.evil.test/watch?v=dQw4w9WgXcQ' => '',
);
$embed_bad = array();
foreach ( $embed_ok as $in => $want ) {
	if ( $want !== topersia_g_embed_url( $in ) ) { $embed_bad[] = $in . ' → ' . topersia_g_embed_url( $in ); }
}

t( 'ویدیو: نشانیِ جاسازی فقط از شناسهٔ معتبر ساخته می‌شود — نشانیِ ناشناس، هیچ  P5-D222',
	! $embed_bad, implode( ' · ', $embed_bad ) );

$gal20 = r( 20, 'tp_gallery' );

t( 'گالری: فقط عکس — کاشیِ بزرگ دوباره عکس است، بی نشان پخش و بی data-tp-media  P5-D227',
	false !== strpos( $gal20, '<button class="tp-gallery-main__item tp-gallery-main__item--large" type="button" data-tp-shot="601"' )
	&& false === strpos( $gal20, 'data-tp-media' ) && false === strpos( $gal20, 'tp-gallery-main__play' )
	&& 5 === substr_count( $gal20, 'class="tp-gallery-main__item' )
	&& false !== strpos( $gal20, '<span class="tp-gallery-main__count">+۱۳ تصویر</span>' )
	&& false === strpos( $css, 'tp-gallery-main__play' ) );

$vs_keep = $GLOBALS['tp_meta'][20]['video_sample'];
$GLOBALS['tp_meta'][20]['video_sample'] = 'https://topersia.com/video/isfahan-overview.mp4';
$vs_mp4 = r( 20, 'tp_audio' );
$GLOBALS['tp_meta'][20]['video_sample'] = 'https://example.test/some-page';
$vs_odd = r( 20, 'tp_audio' );
$GLOBALS['tp_meta'][20]['video_sample'] = $vs_keep;

t( 'روایت: ویدیوی mp4 با <video> در شاسی؛ نشانیِ پخش‌نشدنی پیوندِ ساده است و هنوز نوعِ تصویر  P5-D227',
	false !== strpos( $vs_mp4, 'data-tp-media="https://topersia.com/video/isfahan-overview.mp4" data-tp-media-kind="video"' )
	&& false !== strpos( $vs_odd, '<a href="https://example.test/some-page" rel="nofollow noopener"><span class="tp-audio__kind" data-tp-kind="video">' )
	&& false === strpos( $vs_odd, 'data-tp-media="https://example.test' ) );

/* ویدیو دیگر به گالری بسته نیست: صفحهٔ بی‌عکس با ویدیوی پخش‌شدنی شاسی می‌گیرد. */
$fg_keep = $GLOBALS['tp_meta'][20]['featured_image_gallery'];
$GLOBALS['tp_meta'][20]['featured_image_gallery'] = '';
$nogal = r( 20, 'tp_page' );
$GLOBALS['tp_meta'][20]['featured_image_gallery'] = $fg_keep;

t( 'روایت: صفحهٔ بی‌گالری با ویدیوی پخش‌شدنی شاسیِ پخش دارد  P5-D227',
	false !== strpos( $nogal, 'id="tpMediaSolo"' ) && false === strpos( $nogal, 'id="tpGalleryModal"' ) );

t( 'ویدیو: شاسیِ پخش فقط وقتی چیزی برای پخش هست — صحنهٔ خالی، هیچ iframe در HTML  P5-D222',
	false !== strpos( $p20, '<div class="tp-solo__stage tp-solo__stage--media" data-tp-media-stage></div>' )
	&& false === stripos( $p20, '<iframe' ) && false === strpos( r( 30, 'tp_page' ), 'tpMediaSolo' ) );

/* ردیفِ پروندهٔ صوتی `div` است ولی باید دقیقاً ردیفِ `a`ِ مرجع باشد. */
$aud_ref = preg_match( '/html \.tp-page \.tp-audio a\{([^}]*)\}/', $css, $ar ) ? preg_replace( '/\s+/', '', str_replace( 'text-decoration:none;', '', $ar[1] ) ) : 'x';
$aud_new = preg_match( '/\.tp-page \.tp-audio__item\{([^}]*)\}/', $css, $an ) ? preg_replace( '/\s+/', '', $an[1] ) : 'y';

t( 'صوت: ردیفِ پخش‌کننده همان اعلانِ ردیفِ مرجع است — نه تقریب  P5-D222',
	$aud_ref === $aud_new, $aud_ref . ' ≠ ' . $aud_new );

$GLOBALS['tp_meta'][20]['audio_narration']['item-1']['audio_duration'] = '<b>4:12</b>';
$aud_esc = r( 20, 'tp_audio' );
$GLOBALS['tp_meta'][20]['audio_narration']['item-1']['audio_duration'] = '4:12';

t( 'صوت: مدت از متا گریخته چاپ می‌شود — پیش از ۴٫۲٫۱ خام بود',
	false === strpos( $aud_esc, '<b>۴:۱۲</b>' ) && false !== strpos( $aud_esc, '&lt;b&gt;۴:۱۲&lt;/b&gt;' ) );

/* ── ۴٫۲٫۱ بند ۱۰ — بلوک AI از افزونهٔ topersia-ai-summarizer ─────────
 *
 * خلاصه‌سازِ ساختگی درست همان API نسخهٔ ۱٫۱٫۰ را دارد که `topersia_g_ai_outputs()`
 * صدا می‌زند: `TP_AI_Summary::get_cached / engine_is_internal / internal_text`
 * و `TP_AI_Localize::for_post`. تعریفش شرطی و در زمان اجراست، پس آزمون‌های
 * بالاتر افزونه را ندیدند — همان حالتِ «افزونه نیست».
 */
if ( ! class_exists( 'TP_AI_Summary' ) ) {
	class TP_AI_Summary {
		public static function get_cached( $post_id, $type, $lang ) {
			$GLOBALS['tp_ai_calls'][] = $post_id . '/' . $type . '/' . $lang;
			return isset( $GLOBALS['tp_ai_fake'][ $post_id ][ $type ] ) ? array( 'text' => $GLOBALS['tp_ai_fake'][ $post_id ][ $type ] ) : null;
		}
		public static function engine_is_internal() { return ! empty( $GLOBALS['tp_ai_internal'] ); }
		public static function internal_text( $post_id, $type, $lang ) {
			$GLOBALS['tp_ai_generated'][] = $post_id . '/' . $type;
			return 'خلاصهٔ موتور داخلی';
		}
	}
}
if ( ! class_exists( 'TP_AI_Localize' ) ) {
	class TP_AI_Localize {
		public static function for_post( $post_id ) { return 'fa'; }
	}
}

$ai_block = function () {
	$h = r( 20, 'tp_ai' );
	preg_match( "#data-tp-ai='([^']*)'#", $h, $m );
	return array( $h, isset( $m[1] ) ? json_decode( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ), true ) : array() );
};

/* ۱ — افزونه هست ولی چیزی ندارد: همان دادهٔ صفحه */
$GLOBALS['tp_ai_fake'] = array(); $GLOBALS['tp_ai_calls'] = array(); $GLOBALS['tp_ai_internal'] = false;
list( $ai0, $ai0d ) = $ai_block();

/* ۴٫۳٫۰: نکاتِ جایگزین از حقایق برداشته شد — «نکات» فقط خروجی هوش مصنوعی است. مهمان فقط خلاصه. */
t( 'AI: مهمان، بی‌خروجیِ افزونه — خلاصه از ai_summary، بی‌نکات و بی‌برنامه، با دعوت به ثبت‌نام  بند ۱۰ · P5-D235',
	$ai0d['summary'] === topersia_g_text( 20, 'ai_summary' ) && ! isset( $ai0d['bullets'] )
	&& ! isset( $ai0d['plan'] ) && false === strpos( $ai0, 'data-ai="plan"' ) && false === strpos( $ai0, 'data-ai="bullets"' )
	&& false !== strpos( $ai0, 'برای دسترسی به امکانات بیشتر هوش مصنوعی ثبت‌نام کنید' )
	&& array( '20/summary/fa', '20/keypoints/fa', '20/plan/fa' ) === $GLOBALS['tp_ai_calls'],
	implode( ',', $GLOBALS['tp_ai_calls'] ) );

/* ۲ — افزونه هر سه را دارد: همه از افزونه، با «-» به «•» و بی‌«**» */
$GLOBALS['tp_ai_fake'] = array( 20 => array(
	'summary'   => 'خلاصهٔ افزونه دربارهٔ **اصفهان**.',
	'keypoints' => "- میدان نقش جهان\n- سی‌وسه‌پل",
	'plan'      => "روز اول: میدان\nروز دوم: کاخ‌ها",
) );
$GLOBALS['tp_user'] = 7;
list( $ai2, $ai2d ) = $ai_block();
$GLOBALS['tp_user'] = 0;

t( 'AI: واردشده — خروجیِ افزونه جای هر سه دکمه می‌نشیند — خلاصه، نکات، برنامه  بند ۱۰',
	'خلاصهٔ افزونه دربارهٔ اصفهان.' === $ai2d['summary']
	&& "• میدان نقش جهان\n• سی‌وسه‌پل" === $ai2d['bullets'] && "روز اول: میدان\nروز دوم: کاخ‌ها" === $ai2d['plan']
	&& false !== strpos( $ai2, '<button class="tp-ai__btn" type="button" data-ai="plan">پیشنهاد برنامه سفر</button>' )
	&& false !== strpos( $ai2, '<p class="tp-justify">خلاصهٔ افزونه دربارهٔ اصفهان.</p>' ),
	json_encode( $ai2d, JSON_UNESCAPED_UNICODE ) );

/* ۳ — افزونه خلاصه ندارد و موتور داخلی روشن است: فقط خلاصه ساخته می‌شود */
$GLOBALS['tp_ai_fake'] = array( 20 => array( 'keypoints' => '- فقط نکته' ) );
$GLOBALS['tp_ai_internal'] = true; $GLOBALS['tp_ai_generated'] = array();
list( $ai3, $ai3d ) = $ai_block();

/* ۴٫۳٫۰ بند ۴: «تولید آنی» در رندر برداشته شد — نه برای مهمان، نه برای واردشده.  P5-D235 */
$GLOBALS['tp_user'] = 7;
list( $ai3u, $ai3ud ) = $ai_block();
$GLOBALS['tp_user'] = 0;
t( 'AI: رندر هرگز تولید نمی‌کند — موتور داخلی روشن، بی‌خلاصهٔ ذخیره: مهمان ai_summary، واردشده دکمهٔ ساختن  P5-D235',
	$ai3d['summary'] === topersia_g_text( 20, 'ai_summary' ) && ! isset( $ai3d['bullets'] )
	&& '• فقط نکته' === $ai3ud['bullets'] && false !== strpos( $ai3u, 'data-tp-ai-gen="' ) && false === strpos( $ai3, 'data-tp-ai-gen' )
	&& array() === $GLOBALS['tp_ai_generated'],
	implode( ',', $GLOBALS['tp_ai_generated'] ) );

/* ۴ — نه افزونه، نه ai_summary: بخش نمی‌آید */
$GLOBALS['tp_ai_fake'] = array(); $GLOBALS['tp_ai_internal'] = false;
$ai_keep = $GLOBALS['tp_meta'][20]['ai_summary'];
$GLOBALS['tp_meta'][20]['ai_summary'] = '';
$ai4 = r( 20, 'tp_ai' );
$GLOBALS['tp_meta'][20]['ai_summary'] = $ai_keep;

t( 'AI: بی‌خلاصه — نه از افزونه نه از ai_summary — بخش نمی‌آید  بند ۱۰', '' === $ai4 );

$js_media = preg_match( '#/\* ══ صوت و ویدیو — P5-D222.*?/\* ══ مودالِ فهرست#s', $js_zones, $jm9 ) ? $jm9[0] : '';

t( 'ویدیو: iframe فقط در کلیک ساخته و با بستن برداشته می‌شود  P5-D222',
	(bool) preg_match( "/page\.addEventListener\( 'click'.*document\.createElement\( 'iframe' \)/s", $js_media )
	&& false !== strpos( $js_media, "box.tpOnClose = function () {\n\t\t\tstage.innerHTML = '';" )
	&& false !== strpos( $js_zones, "if ( 'function' === typeof modal.tpOnClose ) {" ) );

$js_solo = preg_match( '#/\* ══ گالری — نمای تک‌عکس.*?/\* ══ مودالِ فهرست#s', $js_zones, $js8 ) ? $js8[0] : '';

t( 'تمام‌صفحه: چرخهٔ دوسر، شمار «۳ / ۲۳»، پیش‌بارگذاری همسایه‌ها، کشیدن هم‌سوی «بعدی» = بعدی  بند ۸ · P5-D243',
	false !== strpos( $js_solo, 'at = ( ( index % n ) + n ) % n;' )
	&& false !== strpos( $js_solo, "counter.textContent = fa( at + 1 ) + ' / ' + fa( n );" )
	&& false !== strpos( $js_solo, 'preload( at + 1 );' ) && false !== strpos( $js_solo, 'preload( at - 1 );' )
	&& false !== strpos( $js_solo, 'show( ( rtl() ? dx > 0 : dx < 0 ) ? at + 1 : at - 1 );' ) );

/* ── نوار بخش‌ها و برگهٔ موبایل ────────────────────────────────── */
/* فقط خودِ نوار — برگهٔ موبایل عمداً همهٔ بخش‌ها را دارد. */
$snav20 = preg_match( '#<nav class="tp-snav".*?</nav>#s', $p20, $sn ) ? $sn[0] : '';

/* آیکونِ درون‌خط از P5-D223 — برچسب بی‌آن سنجیده می‌شود. */
$snav20_txt = preg_replace( '#<svg\b.*?</svg>#s', '', $snav20 );

t( 'نوار بخش‌ها: همان دوازده پیوند مرجع با برچسب خودش',
	false !== strpos( $snav20_txt, '<a href="#tp-places">شهرها</a>' )
	&& false === strpos( $snav20, 'href="#tp-season"' )
	&& false === strpos( $snav20, 'href="#tp-nature"' ) );

/* ── P5-D223 — هدر چسبان و نوار بخش‌ها ───────────────────────────── */
preg_match_all( '#<a href="\#(tp-[a-z-]+)">(<svg\b.*?</svg>)#s', $snav20, $snav_icons, PREG_SET_ORDER );

$icon_bad = array();
foreach ( $snav_icons as $one ) {
	if ( topersia_g_icon( topersia_g_section_icon( $one[1] ) ) !== $one[2] ) { $icon_bad[] = $one[1]; }
}

t( 'نوار بخش‌ها: هر پیوند دقیقاً یک آیکون دارد — همانِ ثابتِ بخشش  P5-D223',
	$snav_icons && count( $snav_icons ) === substr_count( $snav20, '<a href=' )
	&& count( $snav_icons ) === substr_count( $snav20, '<svg' ) && ! $icon_bad,
	implode( ' · ', $icon_bad ) );

$mbar20 = preg_match( '#<nav class="tp-mnav".*?</nav>#s', $p20, $mb ) ? $mb[0] : '';

t( 'نوار بخش‌ها: نوار موبایل از همان مجموعهٔ آیکون می‌خواند — یک منبع  P5-D223',
	false !== strpos( $mbar20, topersia_g_icon( 'top' ) ) && false !== strpos( $mbar20, topersia_g_icon( 'list' ) )
	&& false !== strpos( $snav20, topersia_g_icon( 'pin' ) )
	&& 1 === substr_count( file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' ), 'M12 3.5l2.6 5.3' ) );

$js_src  = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-zones.js' );
$js_snav = preg_match( '#/\* ══ حالت فعال نوار بخش‌ها.*?/\* ══ اسلایدرها#s', $js_src, $jm ) ? $jm[0] : '';

t( 'نوار بخش‌ها: همگام‌سازی هیچ scrollIntoView عمودی ندارد — فقط لغزش افقیِ .tp-snav__in  ۴٫۲٫۱ بند ۳',
	'' !== $js_snav
	&& ! preg_match( '/scrollIntoView\s*\(/', preg_replace( '#/\*.*?\*/#s', '', $js_snav ) )
	&& false !== strpos( $js_snav, "querySelector( '.tp-snav__in' )" )
	&& (bool) preg_match( '/strip\.scrollTo\(\s*\{\s*left:/', $js_snav ) );

t( 'نوار بخش‌ها: بالای نوار پایینِ اندازه‌گرفتهٔ هدر است و لنگر هر بخش هدر + نوار  P5-D223',
	false !== strpos( $css, '.tp-page .tp-snav{position:sticky;top:var(--tp-snav-top);' )
	&& false === strpos( $css, 'scroll-margin-top:calc(var(--tp-header-h)' )
	&& 2 === substr_count( $css, '{scroll-margin-top:calc(var(--tp-snav-top) + var(--tp-snav-h))}' ) - 1
	&& false !== strpos( $css, '.tp-page section[id]{scroll-margin-top:calc(var(--tp-snav-top) + var(--tp-snav-h))}' ) );

t( 'نوار بخش‌ها: اندازه از .whb-sticked و .whb-clone وودمارت، بی‌فاصله، در هر فریم اسکرول و گذار  P5-D223',
	false !== strpos( $js_src, '.whb-clone' ) && false !== strpos( $js_src, "attributeFilter: [ 'class', 'style' ]" )
	&& false !== strpos( $js_src, "page.style.setProperty( '--tp-snav-top', top + 'px' )" )
	&& false !== strpos( $js_src, "page.style.setProperty( '--tp-snav-h', h + 'px' )" )
	&& false !== strpos( $js_src, "window.addEventListener( 'scroll', settle, { passive: true } )" ) );

$bridge_src = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-theme-bridge.css' );

t( 'نوار بخش‌ها: چسبیده، هم نوار هم هدر وودمارت سطح --tp-surface و مرز و سایه می‌گیرند  P5-D223',
	(bool) preg_match( '/html \.tp-page \.tp-snav\.tp-is-stuck\{\s*background:var\(--tp-surface\);\s*border-bottom:1px solid var\(--tp-border\);\s*box-shadow:var\(--tp-g-shadow-sm\);/', $css )
	&& false !== strpos( $bridge_src, 'html:root .whb-clone.whb-sticked:not(.tp-keep-bg)' )
	&& (bool) preg_match( '/html:root \.whb-sticked \.whb-main-header:not\(\.tp-keep-bg\),\s*html:root \.whb-clone\.whb-sticked:not\(\.tp-keep-bg\)\{\s*border-bottom:1px solid var\(--tp-border\) !important;\s*box-shadow:var\(--tp-g-shadow-sm\);/', $bridge_src )
	&& false !== strpos( $js_src, "nav.classList.toggle( 'tp-is-stuck', now )" ) );

t( 'موبایل: نوار بخش‌های بالا پنهان — قاعدهٔ مرجع بی‌رقیب؛ نوار پایین ناوبری است  P5-D228',
	false === strpos( $css, 'nav.tp-snav{display:block}' )
	&& (bool) preg_match( '/@media\(max-width:720px\)\{[^@]*?html \.tp-page \.tp-snav\{display:none\}/s', $css )
	&& false !== strpos( $css, 'html .tp-page .tp-mnav{display:block}' )
	&& false !== strpos( $css, '@media(max-width:720px){.tp-page{--tp-snav-h:0px}}' )
	&& (bool) preg_match( '/(^|\n)@media\(max-width:720px\)\{body:has\(\.tp-page\) \.scrollToTop\{display:none\}\}/', $css ) );

t( 'موبایل: نوار پایین، برگهٔ بخش‌ها و همهٔ مودال‌ها با JS به ظرفی در body می‌روند — tp-page و display:contents  P5-D228',
	false !== strpos( $js_src, "portal.className = 'tp-page tp-portal';" )
	&& false !== strpos( $js_src, "page.querySelectorAll( '.tp-mnav, .tp-sheet, .tp-modal, .tp-gallery-modal' )" )
	&& false !== strpos( $js_src, 'document.body.appendChild( portal );' )
	&& false !== strpos( $css, 'html .tp-page.tp-portal{display:contents}' )
	/* هر چه پس از جابه‌جایی دنبالشان می‌گردد، در ظرف می‌گردد نه در صفحه */
	&& false !== strpos( $js_src, "portal.querySelector( '.tp-mnav' )" )
	&& false !== strpos( $js_src, "portal.querySelectorAll( '[data-tp-list]' )" )
	&& false === strpos( $js_src, "page.querySelector( '.tp-mnav' )" ) );

t( 'موبایل: پایینِ صفحه جای نوار را با ناحیهٔ امنِ آیفون نگه می‌دارد؛ خودِ نوار هم  P5-D228',
	false !== strpos( $css, '@media(max-width:720px){html body .tp-page{padding-block-end:calc(64px + env(safe-area-inset-bottom))}}' )
	&& (bool) preg_match( '/html \.tp-page \.tp-mnav\{position:fixed;[^}]*env\(safe-area-inset-bottom\)/', $css ) );

t( 'موبایل: هر آیتم برگهٔ «بخش‌ها» آیکون ثابت بخشش را کنار برچسب دارد  P5-D228',
	(bool) preg_match_all( '#<li><a href="\#(tp-[a-z-]+)">(<svg[^>]*>.*?</svg>)<span>[^<]+</span></a></li>#su', $p20, $sheet_items, PREG_SET_ORDER )
	&& count( $sheet_items ) >= 8
	&& count( $sheet_items ) === count( array_filter( $sheet_items, function ( $one ) {
		return $one[2] === topersia_g_icon( topersia_g_section_icon( $one[1] ) );
	} ) )
	&& false !== strpos( $css, '.tp-page .tp-sheet__p a > svg{' ) );

/* ── P5-D224 — «دربارهٔ …» ─────────────────────────────────────────── */
$h1_20 = preg_match( '#<h1>(.*?)</h1>#u', $p20, $h1m ) ? $h1m[1] : '';

t( 'درباره: سرفصل «دربارهٔ» + همان نامِ تیتر سربرگ — «دربارهٔ استان اصفهان»  P5-D224',
	'استان اصفهان' === $h1_20
	&& false !== strpos( $p20, "<section id=\"tp-overview\" class=\"tp-section tp-answer\"><h2>دربارۀ استان اصفهان</h2>" )
	&& false === strpos( $p20, 'پاسخ مستقیم' ) );

t( 'درباره: در نوار و برگهٔ موبایل «درباره»  P5-D224',
	false !== strpos( $snav20_txt, '<a href="#tp-overview">درباره</a>' )
	&& (bool) preg_match( '#<li><a href="\#tp-overview"><svg[^>]*>.*?</svg><span>درباره</span></a></li>#su', $p20 ) );

t( 'روایت: در نوار و برگهٔ بخش‌ها «روایت‌ها»  P5-D227',
	false !== strpos( $snav20_txt, '<a href="#tp-audio">روایت‌ها</a>' )
	&& (bool) preg_match( '#<li><a href="\#tp-audio"><svg[^>]*>.*?</svg><span>روایت‌ها</span></a></li>#su', $p20 ) );

t( 'دکمهٔ «N مورد دیگر»: کلاس تازه، نه `.tp-more`ِ سرفصل',
	false !== strpos( $css, '.tp-more-rest{' )
	&& ! preg_match( '#<button class="tp-more" type="button" data-tp-more#', $p20 ) );

/* ── ۴٫۲٫۳ ────────────────────────────────────────────────────────── */

/*
 * هیچ پروندهٔ PHP افزونه پیش از `<?php` چیزی ندارد.  ۴٫۲٫۳ بند ۱
 * سه بایت BOM سرِ topersia-render.php پیش از هر هدری فرستاده می‌شد؛ ثبت
 * هستهٔ سایت زنده ۲۳۲ بار «headers already sent … topersia-render.php:1»
 * داشت. `?>` پایانی با فاصلهٔ بعدش همان خطا را از سوی دیگر می‌سازد.
 */
$bom_bad = array();
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( TP_PLUGIN_DIR, FilesystemIterator::SKIP_DOTS ) ) as $php_file ) {
	if ( 'php' !== strtolower( $php_file->getExtension() ) ) { continue; }
	$php_src = file_get_contents( (string) $php_file );
	if ( 0 !== strpos( $php_src, '<?php' ) || preg_match( '/\?>\s+$/', $php_src ) ) {
		$bom_bad[] = str_replace( TP_PLUGIN_DIR, '', (string) $php_file );
	}
}

t( 'افزونه: هیچ پروندهٔ PHP پیش از <?php چیزی ندارد — نه BOM نه فاصله — و پس از ?> هم نه  ۴٫۲٫۳ بند ۱',
	! $bom_bad, implode( ' · ', $bom_bad ) );

/*
 * جدولِ JetReviews بی‌حساسیت به حروف پیدا می‌شود.  ۴٫۲٫۳ بند ۱
 * MySQL ویندوز نام را کوچک برمی‌گرداند (`jwwsu9v_jet_reviews`)؛ با `===`
 * جدولی که بود «نبود» و بخش نظرها بی‌صدا می‌افتاد. پایگاه‌دادهٔ ساختگی همان
 * پاسخِ نسخهٔ محلی را می‌دهد.
 */
$wpdb_keep = isset( $GLOBALS['wpdb'] ) ? $GLOBALS['wpdb'] : null;
$GLOBALS['wpdb'] = new class() {
	public $prefix = 'jwWSU9v_';
	public function esc_like( $s ) { return $s; }
	public function prepare( $q ) { return $q; }
	public function get_var( $q ) { return false !== strpos( $q, 'SHOW TABLES' ) ? strtolower( $this->prefix ) . 'jet_reviews' : null; }
	public function get_results( $q, $o = null ) {
		return array( array( 'author' => '', 'date' => '2026-09-20', 'content' => 'خوب', 'rating' => 100, 'approved' => '1' ) );
	}
};
$rv_ci = topersia_g_reviews_data( 91 );
$GLOBALS['wpdb'] = $wpdb_keep;

t( 'نظرها: جدول JetReviews بی‌حساسیت به حروف پیدا می‌شود — نام کوچکِ MySQL ویندوز  ۴٫۲٫۳ بند ۱',
	1 === $rv_ci['count'] && 5.0 === (float) $rv_ci['avg'], json_encode( $rv_ci ) );

t( 'منابع: ردیف «شناسهٔ موجودیت» نیست و entity_uid در HTML کارت چاپ نمی‌شود  P5-D229',
	false === strpos( $p20, 'شناسهٔ موجودیت' )
	&& false === strpos( r( 20, 'tp_sources' ), 'tp:province:isfahan' )
	&& false !== strpos( r( 20, 'tp_sources' ), 'وضعیت محتوا' ) );

/* اسلایدر — دکمهٔ بی‌اثر غیرفعال.  P5-D243 (جایگزین P5-D226) */
t( 'اسلایدر: «قبلی» سپس «بعدی» در DOM؛ راست‌به‌چپ بعدی = فلش چپ «‹»، قبلی = «›» و در آغاز غیرفعال  P5-D243',
	(bool) preg_match( '#<div class="tp-slider-nav"><button type="button" data-target="(tpsl[^"]+)" data-dir="-1" disabled aria-label="قبلی"><span dir="ltr">›</span></button><button type="button" data-target="\1" data-dir="1" aria-label="بعدی"><span dir="ltr">‹</span></button></div>#u', $p20 ) );

/* چپ‌به‌راست: همان ترتیب DOM، نویسه‌ها برعکس — جهت از صفحه، نه زبان ثابت.  P5-D243 */
$GLOBALS['tp_ltr'] = true;
$ltr_bar  = topersia_g_slider_bar( 'tpslX', 3 );
$ltr_page = r( 20, 'tp_page' );
unset( $GLOBALS['tp_ltr'] );
t( 'اسلایدر و تمام‌صفحه در چپ‌به‌راست: بعدی = فلش راست «›»، قبلی = «‹»  P5-D243',
	false !== strpos( $ltr_bar, 'data-dir="-1" disabled aria-label="قبلی"><span dir="ltr">‹</span></button><button type="button" data-target="tpslX" data-dir="1" aria-label="بعدی"><span dir="ltr">›</span>' )
	&& false !== strpos( $ltr_page, 'data-tp-shot-prev aria-label="قبلی"><span dir="ltr">‹</span></button><button class="tp-solo__nav tp-solo__nav--next" type="button" data-tp-shot-next aria-label="بعدی"><span dir="ltr">›</span>' ) );

t( 'اسلایدر: جهت پیمایش از direction فهرست — «بعدی» در راست‌به‌چپ منفی، در چپ‌به‌راست مثبت؛ پیکان «بکشید» در چپ‌به‌راست «→»  P5-D243',
	false !== strpos( $js_src, "var rtl    = 'rtl' === window.getComputedStyle( el ).direction;" )
	&& false !== strpos( $js_src, 'var amount = Math.round( el.clientWidth * 0.85 ) * ( next === rtl ? -1 : 1 );' )
	&& false !== strpos( $css, 'html:not([dir="rtl"]) .tp-page .tp-slider-hint::before{content:"→"}' ) );

t( 'اسلایدر: هر اسکرول وضع دو دکمه را از نو می‌سنجد — آغاز و پایان و «جا شد»، با Math.abs در RTL  P5-D226',
	false !== strpos( $js_src, "var off  = max <= 1 || ( next ? pos >= max - 1 : pos <= 1 );" )
	&& false !== strpos( $js_src, 'var pos = Math.abs( el.scrollLeft );' )
	&& false !== strpos( $js_src, "el.addEventListener( 'scroll', later, { passive: true } );" )
	&& false !== strpos( $css, 'html .tp-page .tp-slider-nav button:disabled{' ) );

/* تمام‌صفحه — کلید، و تعویضِ بی‌درنگ.  P5-D243 (جایگزین P5-D226) */
t( 'تمام‌صفحه: راست‌به‌چپ ArrowLeft = بعدی و ArrowRight = قبلی، چپ‌به‌راست برعکس؛ کشیدن از چپ به راست در راست‌به‌چپ = بعدی  P5-D243',
	false !== strpos( $js_src, "var forward = rtl() ? 'ArrowLeft' : 'ArrowRight';" )
	&& false !== strpos( $js_src, "var back    = rtl() ? 'ArrowRight' : 'ArrowLeft';" )
	&& false !== strpos( $js_src, 'show( ( rtl() ? dx > 0 : dx < 0 ) ? at + 1 : at - 1 );' )
	&& false !== strpos( $js_src, "return 'rtl' === window.getComputedStyle( soloBox ).direction;" ) );

t( 'تمام‌صفحه: نسخهٔ ۱۰۲۴ همان لحظه، نسخهٔ کامل پس از decode()؛ همسایه‌ها هر دو نسخه از پیش  P5-D226',
	(bool) preg_match( '#&quot;src&quot;:&quot;[^&]*601[^&]*&quot;,&quot;m&quot;:&quot;[^&]*601[^&]*large[^&]*&quot;#', $p20 )
	&& false !== strpos( $js_src, 'var fast = shot.m || full;' )
	&& false !== strpos( $js_src, 'big.decode().then( swap, function () {} );' )
	&& false !== strpos( $js_src, 'if ( mine === token ) { img.setAttribute( \'src\', full ); }' )
	&& false !== strpos( $js_src, '[ one.m, one.src ].forEach(' ) );

/*
 * هیچ گذار یا پویانماییِ گالری بیش از ۱۵۰ms نیست.  P5-D226
 * هر قاعدهٔ گالری/تک‌عکس که بیش از ۱۵۰ms دارد باید در نیمهٔ افزوده
 * همتایی سنگین‌تر با ۱۵۰ms داشته باشد. رفتارِ واقعی را snav-check می‌سنجد.
 */
$slow_bad = array();
$css_nc   = preg_replace( '#/\*.*?\*/#s', '', $css );
preg_match_all( '/([^{}]+)\{([^{}]*)\}/', $css_nc, $slow_rules, PREG_SET_ORDER );
foreach ( $slow_rules as $rule ) {
	$sel = trim( $rule[1] );
	if ( ! preg_match( '/tp-gallery|tp-solo/', $sel ) || false !== strpos( $rule[2], '150ms' ) ) { continue; }
	if ( ! preg_match_all( '/(?:transition|animation)[^:;]*:\s*([^;]+)/', $rule[2], $tv ) ) { continue; }
	foreach ( $tv[1] as $v ) {
		if ( ! preg_match_all( '/(\d*\.?\d+)(ms|s)\b/', $v, $dv, PREG_SET_ORDER ) ) { continue; }
		foreach ( $dv as $d ) {
			$ms = 's' === $d[2] ? $d[1] * 1000 : (float) $d[1];
			if ( $ms <= 150 ) { continue; }
			foreach ( explode( ',', $sel ) as $one ) {
				$tail = preg_replace( '/^.*\s/', '', trim( $one ) );   /* آخرین جزء: .tp-gallery-modal__cap، ::after، … */
				$tail = preg_quote( $tail, '/' );
				if ( ! preg_match( '/[>\s][a-z]*' . $tail . '(?:,|\{)[^{}]*?\{?[^{}]*(?:transition|animation)-duration:150ms/', $css_nc ) ) {
					$slow_bad[] = trim( $one ) . ' (' . $ms . 'ms)';
				}
			}
			break;
		}
	}
}

t( 'گالری: هر گذار و پویانماییِ بیش از ۱۵۰ms همتایی ۱۵۰ms در نیمهٔ افزوده دارد  P5-D226',
	! $slow_bad && false !== strpos( $css, 'html .tp-page div.tp-gallery-modal.tp-show{animation-duration:150ms}' ),
	implode( ' · ', array_unique( $slow_bad ) ) );

/* موبایل — سرفصل و فاصلهٔ کارت‌ها.  ۴٫۲٫۳ بند ۳ و ۴ — رفتار را snav-check می‌سنجد */
t( 'موبایل: `.more` در ردیف عنوان — h2 راست، .more چپ، راهنما زیرشان  ۴٫۲٫۳ بند ۴',
	(bool) preg_match( '/@media\(max-width:720px\)\{\s*html \.tp-page div\.tp-section-head\{\s*display:grid;grid-template-columns:minmax\(0,1fr\) auto;/', $css )
	&& false !== strpos( $css, 'html .tp-page div.tp-section-head > div{display:contents}' )
	&& false !== strpos( $css, 'html .tp-page div.tp-section-head .tp-more{grid-column:2;grid-row:1;justify-self:end}' )
	&& false !== strpos( $css, 'html .tp-page div.tp-section-head .tp-lead{grid-column:1/-1;grid-row:2}' ) );

t( 'موبایل: فاصلهٔ ردیفی ۲۸ روی شبکه و margin پایینِ بخش صفر — دو برابر نمی‌شود  ۴٫۲٫۳ بند ۳',
	(bool) preg_match( '/@media\(max-width:720px\)\{\s*html \.tp-page div\.tp-content-grid\{row-gap:28px\}\s*html \.tp-page section\.tp-section\{margin-bottom:0\}\s*\}/', $css ) );

/*
 * فهرستِ «پذیرفته»ی کنتراست بسته است: فقط دو ناحیهٔ ۴٫۲٫۲ بند ۴.
 * هر ناحیهٔ تازه تصمیمی تازه است، نه یک خط کد — اگر این گارد گرفتت، اول تصمیم.
 */
$probe_src = file_get_contents( __DIR__ . '/contrast-probe.js' );
$zone_sels = preg_match( '/function zoneOf\(el\) \{(.*?)\n\t\}/s', $probe_src, $zone_m )
	&& preg_match_all( "/el\\.closest\\('([^']+)'\\)/", $zone_m[1], $zone_all ) ? array_values( array_unique( $zone_all[1] ) ) : array();

t( 'کنتراست: فهرستِ پذیرفته فقط بلوک AI و شمارهٔ کارت است — ۴٫۲٫۲ بند ۴',
	array( '#tp-ai', '.tp-card-slide__no' ) === $zone_sels
	&& 4 === substr_count( $probe_src, 'zone: zoneOf(' )
	&& false !== strpos( file_get_contents( __DIR__ . '/contrast.php' ), "if ( ! empty( \$f['zone'] ) ) {" ),
	implode( ' · ', $zone_sels ) );

/*
 * هاور هم فقط قالب خودمان را می‌سنجد — همان دامنهٔ متن و رابط (`.tp-page *`).
 * روی صفحهٔ واقعیِ نسخهٔ محلی هاورِ دکمه‌های وودمارت را هم می‌شمرد. داوری و
 * کالیبراسیون دست نخورده‌اند: کالیبراسیون `hoverProbes()` را بی‌صافی می‌خواند.
 */
t( 'کنتراست: هاور فقط قالب خودمان را می‌سنجد؛ نقشهٔ نیاکان کامل می‌ماند؛ کالیبراسیون بی‌صافی',
	false !== strpos( $probe_src, "var probes = all.filter(function (p) { return p.el.closest && p.el.closest('.tp-page'); });" )
	&& false !== strpos( $probe_src, 'probes.byEl = all.byEl;' )
	&& false !== strpos( $probe_src, 'hoverProbes().forEach(function (p) { hoverMap.set(p.el, p); });' ) );

/* ── هوا ──────────────────────────────────────────────────────── */
$weather_src = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-weather.php' );

t( 'هوا: نقطهٔ REST topersia/v1/weather ثبت می‌شود',
	false !== strpos( $weather_src, "register_rest_route( 'topersia/v1', '/weather'" ) );

t( 'هوا: کش نیم‌ساعته',
	(bool) preg_match( "/define\\( 'TP_WX_REST_TTL', 1800 \\)/", $weather_src ) );

t( 'هوا: نشانی نقطه به اسکریپت داده می‌شود',
	false !== strpos( $plugin_src, "rest_url( 'topersia/v1/weather' )" ) );

/* ── همهٔ نوع‌ها — ۴٫۲٫۱ بند ۱ ─────────────────────────────────────── */

/*
 * «هر نوع عمومی، حتی با متا و رابطهٔ خالی، بی‌خطا رندر می‌شود.»
 *
 * در ۴٫۲٫۰ همهٔ نوع‌ها جز استان روی سایت ۵۰۰ گزارش شدند و این مجموعه سبز
 * بود: چند پستِ پرداده رندر می‌شد، و وردپرسِ آزمون از وردپرسِ سایت
 * مهربان‌تر بود — رابطه‌ای که نبود false می‌داد نه شیء، برچسب نوع فقط
 * برای نُه نوع بود، esc_url آرایه را می‌بخشید.
 *
 * tests/alltypes.php هر نوع عمومی اسکین را در پنج حالت داده رندر می‌کند
 * (خالی · پر · ناجور · شیء · آرایه). در فرایند جدا اجرا می‌شود چون جهانِ
 * پست‌ها و رابطه‌ها را از نو می‌سازد و این مجموعه به جهان خودش تکیه دارد.
 */
$at_json = shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/alltypes.php' ) . ' --json' );
$at      = json_decode( (string) $at_json, true );

/* شمار نوع‌های عمومی مستقیم از اسکین — پوشش خودِ مهار هم سنجیده شود. */
$at_public = 0;
foreach ( json_decode( $skin_src, true )['post_types'] as $at_pt ) {
	$at_args = @unserialize( $at_pt['args'] );
	if ( ! empty( $at_args['public'] ) && 0 === strpos( $at_pt['slug'], 'tp_' ) ) {
		$at_public++;
	}
}

t( 'همهٔ نوع‌ها: مهار هر نوع عمومی tp_ اسکین را پوشش می‌دهد، در هر پنج حالت',
	is_array( $at ) && $at_public === $at['types']
	&& array( 'empty', 'rich', 'odd', 'arr', 'obj' ) === $at['modes'],
	is_array( $at ) ? $at['types'] . '/' . $at_public . ' · ' . implode( ',', $at['modes'] ) : substr( (string) $at_json, 0, 300 ) );

t( 'همهٔ نوع‌ها: هر نوع عمومی، حتی با متا و رابطهٔ خالی، بی‌خطا رندر می‌شود',
	is_array( $at ) && array() === $at['problems'],
	is_array( $at ) && $at['problems']
		? $at['problems'][0]['mode'] . ' · ' . $at['problems'][0]['type'] . ' · ' . $at['problems'][0]['tag']
			. ' — ' . $at['problems'][0]['kind'] . ': ' . $at['problems'][0]['msg'] . ' @ ' . $at['problems'][0]['at']
		: '' );

/* best_season کشور در اسکین `is_multiple` است — JetEngine آرایه نگه می‌دارد. */
$at_keep = isset( $GLOBALS['tp_meta'][10]['best_season'] ) ? $GLOBALS['tp_meta'][10]['best_season'] : null;
$GLOBALS['tp_meta'][10]['best_season'] = array( 'spring', 'autumn' );
$at_season = r( 10, 'tp_seasons' );
$GLOBALS['tp_meta'][10]['best_season'] = $at_keep;

t( 'همهٔ نوع‌ها: فصلِ چندگزینه‌ایِ کشور هر دو فصل را روشن می‌کند، نه «Array»',
	false !== strpos( $at_season, 'tp-season--spring tp-has-on' )
	&& false !== strpos( $at_season, 'tp-season--autumn tp-has-on' )
	&& false === strpos( $at_season, 'tp-season--summer tp-has-on' )
	&& false !== strpos( $at_season, '<p class="tp-seasons__cap">بهار، پاییز</p>' ),
	substr( strip_tags( $at_season ), 0, 200 ) );

/*
 * نگهبان ناحیه — خطای یک ناحیه صفحه را نمی‌خواباند.
 *
 * ناحیه‌ای که TypeError می‌اندازد از راه همان فیلتر چیدمان تزریق می‌شود
 * که افزونهٔ دیگری هم می‌تواند بزند.
 */
function tp_test_throwing_zone() {
	throw new TypeError( 'آزمون نگهبان -- پایان' );
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) { return ! empty( $GLOBALS['tp_is_admin'] ) && 'manage_options' === $cap; }
}

$GLOBALS['tp_filters']['topersia_g_layout'][] = function ( $layout ) {
	$layout['main'][] = array( 'fn' => 'tp_test_throwing_zone', 'id' => 'tp-boom', 'nav' => 'انفجار', 'atts' => array() );
	return $layout;
};

$g_log     = count( $GLOBALS['tp_log'] );
$g_visitor = r( 20, 'tp_page' );
$g_entries = array_slice( $GLOBALS['tp_log'], $g_log );

$GLOBALS['tp_is_admin'] = true;
$g_admin = r( 20, 'tp_page' );
$GLOBALS['tp_is_admin'] = false;

unset( $GLOBALS['tp_filters']['topersia_g_layout'] );

t( 'نگهبان: ناحیهٔ خطادار صفحه را نمی‌خواباند — بقیهٔ صفحه می‌آید',
	0 === strpos( $g_visitor, '<div class="tp-page">' )
	&& false !== strpos( $g_visitor, 'id="tp-gallery"' )
	&& false !== strpos( $g_visitor, 'id="tp-map"' ) );

t( 'نگهبان: ناحیهٔ خطادار نه در صفحه است نه در نوار بخش‌ها',
	false === strpos( $g_visitor, 'tp-boom' ) && false === strpos( $g_visitor, 'انفجار' ) );

t( 'نگهبان: خطا در کانال render ثبت می‌شود، با نوع خطا و فایل:خط',
	1 === count( preg_grep( '/^\[error\/render\] tp_test_throwing_zone — TypeError: آزمون نگهبان .* @ assert\.php:\d+$/u', $g_entries ) ),
	implode( ' | ', $g_entries ) );

t( 'نگهبان: یادداشت خطا فقط برای مدیر — بازدیدکننده پیام و مسیر فایل را نمی‌بیند',
	false === strpos( $g_visitor, 'tp-render:' )
	&& 1 === substr_count( $g_admin, '<!-- tp-render: tp_test_throwing_zone — TypeError' ) );

t( 'نگهبان: پیام خطا نمی‌تواند یادداشت HTML را زودتر ببندد',
	(bool) preg_match( '/<!-- tp-render: [^\n]*آزمون نگهبان — پایان[^\n]* -->$/u', $g_admin )
	&& 1 === substr_count( $g_admin, '-->' ) );

/* ادعاهای ۴٫۳٫۰، ۴٫۴٫۰، ۴٫۴٫۱ و ۴٫۴٫۲ — فایل جدا، همان شمارنده‌ها */
require __DIR__ . '/assert-430.php';
require __DIR__ . '/assert-440.php';
require __DIR__ . '/assert-441.php';
require __DIR__ . '/assert-442.php';
require __DIR__ . '/assert-2b.php';

/*
 * ── هیچ سندی عدد آزمون را صریح ننویسد ──────────────────────────
 *
 * شمار آزمون یک جا زندگی می‌کند: `tests/RESULT-assert.txt`. سندها
 * می‌گویند «همه سبز» و به آن ارجاع می‌دهند.
 *
 * چرا قاعده شد: هفت بار جای‌گزینی دسته‌ای روی عدد آزمون سرریز کرد.
 * آخرینش شمارهٔ رابطهٔ `range_parent` را هم عوض کرد، چون `۱۰۱` هم شمار
 * آزمون بود هم شناسهٔ رابطه. ریشه نبودِ گارد نبود — ریشه این بود که یک
 * عدد در پنج جا تکرار شده بود. گاردِ درست، تکرار را ممنوع می‌کند نه
 * سرریز را.  P5-D200
 *
 * همان اجرای اول یک سندِ دست‌نخورده را گرفت: `PROMPT-CHAT-BAADI.md`
 * هنوز «۴۹ ادعا» داشت، از زمانی که واقعاً ۴۹ بود.
 */
$count_docs = array(
	'README.md',
	'CLAUDE.md',
	'docs/CODEX-BRIEF.md',
	'docs/HANDOVER-Template-G-v2_0.md',
	'docs/PROMPT-CHAT-BAADI.md',
);

$num_re  = '[۰-۹0-9]{1,4}';
$word_re = '(?:آزمون|ادعا|سبز|گارد)';
$counted = array();

foreach ( $count_docs as $doc ) {

	$path = dirname( __DIR__ ) . '/' . $doc;
	if ( ! is_file( $path ) ) { continue; }

	foreach ( file( $path ) as $i => $line ) {
		if ( preg_match( '/' . $num_re . '\s*' . $word_re . '/u', $line, $m )
			|| preg_match( '/' . $word_re . '\s*' . $num_re . '/u', $line, $m ) ) {
			$counted[] = $doc . ':' . ( $i + 1 ) . ' «' . $m[0] . '»';
		}
	}
}

t( 'سند: هیچ‌جا عدد آزمون صریح نوشته نشده  P5-D200',
	empty( $counted ), implode( ' · ', $counted ) );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
