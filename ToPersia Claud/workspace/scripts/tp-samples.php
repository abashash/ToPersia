<?php
/**
 * «نمونهٔ کامل» هر ۴۱ نوع — دستهٔ ۳۰ بند ۲. فقط محلی (topersia.local). برای ایرادگیری نمایش، نه داده.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-samples.php create
 *   … list
 *   … seen              ← «فیلد پر ولی دیده‌نشده» روی صفحهٔ fa هر نمونه (جدول md)
 *   … delete            ← فقط فهرست (dry-run): چه پاک می‌شود
 *   … delete confirm    ← پشتیبان بیرون از ریشهٔ وب (C:/wamp64/tp-backups/samples-*.json) ← پاک‌کردن کامل
 *
 * هر نمونه (پست فارسی، ترجمهٔ en و ar، و هر پیوست) فرادادهٔ `_tp_sample = 1` دارد و noindex (rank_math_robots).
 * عنوان نام واقعی یک مورد شناخته‌شده است، بی پیشوند «تست»؛ عدد و متن ساختگی‌اند و منبع هر نمونه «نمونه — حذف در
 * صفر کردن» با نشانی example.org است — هرگز منبع واقعی. همهٔ فیلدهای هر نوع از اسکیمای زندهٔ بسته (topersia_pack_schema)
 * پر می‌شوند (ریپیتر، چندانتخابی، ساعت، بلیت، مختصات، پرسش‌ها، منبع‌ها)؛ تصویرها با GD همین‌جا ساخته می‌شوند
 * (WebP، بی دانلود): سربرگ ۲۴۰۰×۱۲۰۰ و ۶ گالری ۱۶۰۰×۱۰۰۰ با alt سه‌زبانه. هر رابطهٔ نوع با ۲ تا ۳ موجودیت دیگر
 * (پست‌های موجود یا نمونه‌ها)، هر دو سو. سه خروجی «در یک نگاه» در بازهٔ TPAS_* برای هر زبان. نظر نه (دستهٔ ۳۱).
 * تکرارپذیر: نوعی که نمونه دارد دوباره ساخته نمی‌شود (create فقط کم‌ها را می‌سازد).
 */
if ( 'local' !== wp_get_environment_type() || false === strpos( (string) home_url(), 'topersia.local' ) ) { echo "فقط topersia.local\n"; return; }
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/post.php';

$cmd  = (string) ( $args[0] ?? 'list' );
$conf = 'confirm' === (string) ( $args[1] ?? '' );
$bak  = dirname( untrailingslashit( ABSPATH ), 3 ) . '/tp-backups/';
global $wpdb;

/* نام‌ها: [ fa, en, ar ] — موارد شناخته‌شده؛ اگر اولی از پیش پست واقعی دارد، دومی */
$NAMES = array(
	'tp_country'          => array( array( 'ارمنستان', 'Armenia', 'أرمينيا' ), array( 'گرجستان', 'Georgia', 'جورجيا' ) ),
	'tp_region'           => array( array( 'زاگرس', 'Zagros', 'زاغروس' ), array( 'کرانه‌های خلیج فارس', 'Persian Gulf Coast', 'ساحل الخليج' ) ),
	'tp_province'         => array( array( 'خراسان بزرگ', 'Greater Khorasan', 'خراسان الكبرى' ), array( 'ایالت فارس', 'Pars', 'بارس' ) ),
	'tp_city'             => array( array( 'نائین', 'Nain', 'نائين' ), array( 'میبد', 'Meybod', 'ميبد' ) ),
	'tp_district'         => array( array( 'جلفا', 'Jolfa', 'جلفا' ), array( 'محلهٔ جویباره', 'Juybareh', 'جويباره' ) ),
	'tp_village'          => array( array( 'ماسوله', 'Masuleh', 'ماسوله' ), array( 'کندوان', 'Kandovan', 'كندوان' ) ),
	'tp_island'           => array( array( 'هرمز', 'Hormuz Island', 'جزيرة هرمز' ), array( 'قشم', 'Qeshm', 'قشم' ) ),
	'tp_mountain'         => array( array( 'سبلان', 'Sabalan', 'سبلان' ), array( 'علم‌کوه', 'Alam-Kuh', 'علم كوه' ) ),
	'tp_water_body'       => array( array( 'تالاب انزلی', 'Anzali Lagoon', 'بحيرة أنزلي' ), array( 'دریاچهٔ ارومیه', 'Lake Urmia', 'بحيرة أرومية' ) ),
	'tp_desert'           => array( array( 'کویر مرنجاب', 'Maranjab Desert', 'صحراء مرنجاب' ), array( 'دشت کویر', 'Dasht-e Kavir', 'دشت كوير' ) ),
	'tp_forest_plain'     => array( array( 'جنگل ابر', 'Abr Forest', 'غابة أبر' ), array( 'دشت ارژن', 'Arzhan Plain', 'سهل أرجن' ) ),
	'tp_border_crossing'  => array( array( 'مرز بازرگان', 'Bazargan Border Crossing', 'منفذ بازرگان' ), array( 'مرز آستارا', 'Astara Border Crossing', 'منفذ آستارا' ) ),
	'tp_monument'         => array( array( 'عالی‌قاپو', 'Ali Qapu', 'عالي قابو' ), array( 'برج آزادی', 'Azadi Tower', 'برج آزادي' ) ),
	'tp_religious_site'   => array( array( 'مسجد شیخ لطف‌الله', 'Sheikh Lotfollah Mosque', 'مسجد الشيخ لطف الله' ), array( 'مسجد کبود تبریز', 'Blue Mosque of Tabriz', 'المسجد الأزرق في تبريز' ) ),
	'tp_ancient_site'     => array( array( 'تخت سلیمان', 'Takht-e Soleyman', 'تخت سليمان' ), array( 'چغازنبیل', 'Chogha Zanbil', 'تشغا زنبيل' ) ),
	'tp_museum'           => array( array( 'موزهٔ ملی ایران', 'National Museum of Iran', 'المتحف الوطني الإيراني' ), array( 'موزهٔ فرش ایران', 'Carpet Museum of Iran', 'متحف السجاد الإيراني' ) ),
	'tp_natural_feature'  => array( array( 'غار علی‌صدر', 'Ali-Sadr Cave', 'كهف علي صدر' ), array( 'آبشار شوی', 'Shevi Waterfall', 'شلال شوي' ) ),
	'tp_protected_area'   => array( array( 'پارک ملی گلستان', 'Golestan National Park', 'حديقة غلستان الوطنية' ), array( 'منطقهٔ حفاظت‌شدهٔ ارسباران', 'Arasbaran Protected Area', 'محمية أرسباران' ) ),
	'tp_landscape'        => array( array( 'منظر فرهنگی اورامان', 'Cultural Landscape of Hawraman', 'المشهد الثقافي لهورامان' ), array( 'منظر فرهنگی میمند', 'Maymand Cultural Landscape', 'المشهد الثقافي لميمند' ) ),
	'tp_route'            => array( array( 'جادهٔ چالوس', 'Chalus Road', 'طريق شالوس' ), array( 'راه شاهی هخامنشی', 'Achaemenid Royal Road', 'الطريق الملكي الأخميني' ) ),
	'tp_handicraft'       => array( array( 'فرش دست‌باف', 'Hand-woven Carpet', 'السجاد اليدوي' ), array( 'کاشی‌کاری', 'Tilework', 'فن البلاط' ) ),
	'tp_local_food'       => array( array( 'قورمه‌سبزی', 'Ghormeh Sabzi', 'قورمه سبزي' ), array( 'دیزی', 'Dizi', 'ديزي' ) ),
	'tp_costume'          => array( array( 'لباس کردی', 'Kurdish Costume', 'الزي الكردي' ), array( 'لباس قشقایی', 'Qashqai Costume', 'الزي القشقائي' ) ),
	'tp_music'            => array( array( 'موسیقی بخشی‌های خراسان', 'Music of the Bakhshis of Khorasan', 'موسيقى البخشيين في خراسان' ), array( 'ردیف موسیقی ایرانی', 'Radif of Iranian Music', 'رديف الموسيقى الإيرانية' ) ),
	'tp_dance'            => array( array( 'رقص چوب', 'Chub Bazi Dance', 'رقصة العصا' ), array( 'هلپرکی', 'Halparke', 'هلبركي' ) ),
	'tp_ritual'           => array( array( 'یلدا', 'Yalda Night', 'ليلة يلدا' ), array( 'چهارشنبه‌سوری', 'Chaharshanbe Suri', 'جهارشنبه سوري' ) ),
	'tp_language'         => array( array( 'گیلکی', 'Gilaki', 'الجيلكية' ), array( 'زبان کردی', 'Kurdish', 'الكردية' ) ),
	'tp_ethnic_group'     => array( array( 'ترکمن‌ها', 'Turkmens', 'التركمان' ), array( 'بلوچ‌ها', 'Baloch', 'البلوش' ) ),
	'tp_figure'           => array( array( 'حافظ', 'Hafez', 'حافظ الشيرازي' ), array( 'سعدی', 'Saadi', 'سعدي الشيرازي' ) ),
	'tp_historical_era'   => array( array( 'دورهٔ صفوی', 'Safavid Era', 'العصر الصفوي' ), array( 'دورهٔ هخامنشی', 'Achaemenid Era', 'العصر الأخميني' ) ),
	'tp_designation'      => array( array( 'ثبت ملی آثار تاریخی', 'National Heritage List', 'السجل الوطني للتراث' ), array( 'روستای جهانی گردشگری', 'Best Tourism Villages', 'أفضل القرى السياحية' ) ),
	'tp_festival'         => array( array( 'جشن تیرگان', 'Tirgan Festival', 'مهرجان تيرغان' ), array( 'جشنوارهٔ فیلم فجر', 'Fajr Film Festival', 'مهرجان فجر السينمائي' ) ),
	'tp_sports_event'     => array( array( 'مسابقات کشتی پهلوانی', 'Pahlevani Wrestling Championship', 'بطولة المصارعة البهلوانية' ), array( 'تور دوچرخه‌سواری آذربایجان', 'Tour of Azerbaijan', 'طواف أذربيجان' ) ),
	'tp_religious_event'  => array( array( 'قالی‌شویان مشهد اردهال', 'Qalishuyan Ritual of Mashhad-e Ardehal', 'مراسم قالي شويان' ), array( 'جشن سده', 'Sadeh Feast', 'عيد سده' ) ),
	'tp_seasonal_event'   => array( array( 'شکوفه‌های بادام', 'Almond Blossoms', 'تفتح أزهار اللوز' ), array( 'مهاجرت فلامینگوها', 'Flamingo Migration', 'هجرة طيور النحام' ) ),
	'tp_calendar'         => array( array( 'تقویم قمری', 'Lunar Hijri Calendar', 'التقويم الهجري القمري' ), array( 'تقویم زرتشتی', 'Zoroastrian Calendar', 'التقويم الزرادشتي' ) ),
	'tp_currency'         => array( array( 'یورو', 'Euro', 'اليورو' ), array( 'دلار آمریکا', 'US Dollar', 'الدولار الأمريكي' ) ),
	'tp_visa'             => array( array( 'روادید فرودگاهی ایران', 'Iran Visa on Arrival', 'تأشيرة الوصول إلى إيران' ), array( 'روادید گردشگری ایران', 'Iran Tourist Visa', 'تأشيرة السياحة الإيرانية' ) ),
	'tp_transport'        => array( array( 'متروی تهران', 'Tehran Metro', 'مترو طهران' ), array( 'قطار تهران–مشهد', 'Tehran–Mashhad Train', 'قطار طهران مشهد' ) ),
	'tp_travel_etiquette' => array( array( 'آداب مهمانی ایرانی', 'Iranian Hospitality Etiquette', 'آداب الضيافة الإيرانية' ), array( 'تعارف', 'Taarof', 'التعارف' ) ),
	'tp_health_safety'    => array( array( 'سلامت سفر در تابستان کویر', 'Desert Summer Travel Health', 'الصحة في سفر الصيف الصحراوي' ), array( 'ایمنی کوهنوردی', 'Mountaineering Safety', 'سلامة تسلق الجبال' ) ),
);
$TXT = array(
	'fa' => array( 'متن نمونه برای ایرادگیری نمایش', 'این نمونه ساختگی است و در صفر کردن حذف می‌شود؛ هیچ عدد یا جمله‌ای از آن منبع نیست.' ),
	'en' => array( 'Sample text for display review', 'This sample is fictional and will be removed at reset; none of its numbers or sentences are a source.' ),
	'ar' => array( 'نص تجريبي لمراجعة العرض', 'هذا نموذج متخيَّل وسيُحذف عند إعادة الضبط؛ لا شيء من أرقامه أو جمله مصدرٌ.' ),
);
$SRC = 'نمونه — حذف در صفر کردن';

$samples = function () use ( $wpdb ) {
	$out = array();
	foreach ( $wpdb->get_results( "SELECT p.ID, p.post_type FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_tp_sample' WHERE p.post_type <> 'attachment' AND p.post_status <> 'trash'", ARRAY_A ) as $r ) {
		$lang = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => (int) $r['ID'], 'element_type' => $r['post_type'] ) );
		$out[ $r['post_type'] ][ $lang ?: 'fa' ] = (int) $r['ID'];
	}
	return $out;
};

/* ══ list ══ */
if ( 'list' === $cmd ) {
	$s = $samples();
	foreach ( array_keys( topersia_g_stat_cells() ) as $t ) {
		$row = $s[ $t ] ?? array();
		echo str_pad( $t, 22 ) . ' ' . implode( ' · ', array_map( function ( $l ) use ( $row ) { return $l . '=' . ( isset( $row[ $l ] ) ? $row[ $l ] . ' ' . get_permalink( $row[ $l ] ) : '—' ); }, array( 'fa', 'en', 'ar' ) ) ) . "\n";
	}
	return;
}

/* ══ delete ══ */
if ( 'delete' === $cmd ) {
	$ids = array_map( 'intval', $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tp_sample'" ) );
	$att = array_values( array_filter( $ids, function ( $i ) { return 'attachment' === get_post_type( $i ); } ) );
	$pst = array_values( array_diff( $ids, $att ) );
	echo 'پست: ' . count( $pst ) . ' · پیوست: ' . count( $att ) . "\n";
	if ( ! $conf ) { echo "dry-run — با «delete confirm» پشتیبان و پاک‌کردن\n"; return; }
	wp_mkdir_p( $bak );
	$dump = array( 'at' => gmdate( 'c' ), 'posts' => array(), 'rel' => array() );
	foreach ( $ids as $i ) {
		$dump['posts'][ $i ] = array( 'post' => get_post( $i, ARRAY_A ), 'meta' => get_post_meta( $i ), 'file' => get_post_meta( $i, '_wp_attached_file', true ) );
	}
	if ( $ids ) {
		$in = implode( ',', $ids );
		$dump['rel'] = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}jet_rel_default WHERE parent_object_id IN ({$in}) OR child_object_id IN ({$in})", ARRAY_A );
	}
	$file = $bak . 'samples-' . gmdate( 'Ymd-His' ) . '.json';
	if ( false === file_put_contents( $file, wp_json_encode( $dump, JSON_UNESCAPED_UNICODE ) ) ) { echo "پشتیبان نوشته نشد — توقف\n"; return; }
	if ( $ids ) {
		$wpdb->query( "DELETE FROM {$wpdb->prefix}jet_rel_default WHERE parent_object_id IN ({$in}) OR child_object_id IN ({$in})" );
	}
	foreach ( $pst as $i ) { wp_delete_post( $i, true ); }
	foreach ( $att as $i ) {
		$shared = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s AND post_id <> %d", get_post_meta( $i, '_wp_attached_file', true ), $i ) );
		if ( $shared ) { wp_delete_post( $i, true ); } else { wp_delete_attachment( $i, true ); }   /* ترجمهٔ پیوست همان پرونده را دارد */
	}
	echo "پاک شد · پشتیبان: {$file}\n";
	return;
}

/* ══ seen — «فیلد پر ولی دیده‌نشده» روی صفحهٔ fa هر نمونه (فقط GET) ══
 * مقدار هر فیلد غیرفنی از پایگاه‌داده ← رشته‌های جست‌وجو (متن: بخش پس از «—»؛ عدد: رقم فارسی با و بی جداکننده؛ گزینه: برچسب؛
 * نشانی: مسیر example.org؛ تاریخ/ساعت: ISO یا رقم فارسی؛ ریپیتر: هر زیرفیلد ردیف اول؛ سوییچر: برچسب فیلد). نبود ← دیده‌نشده.
 * پذیرفته = سطر META-DISPLAY.csv (دستهٔ ۲۷) با پیشنهاد «لازم نیست…» یا نمایش «FIELDMAP: skip» — هر چیز دیگر یافتهٔ رندر است.
 * ابتکاری است: عدد کوچک یا برچسب پرتکرار ممکن است «دیده» شمرده شود بی آنکه خودِ فیلد آمده باشد. */
if ( 'seen' === $cmd ) {
	$fad   = function ( $s ) { return strtr( (string) $s, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) ); };
	$csv   = array();
	$fh    = fopen( dirname( __DIR__ ) . '/docs/AUDIT-2026-10/META-DISPLAY.csv', 'r' );
	fgetcsv( $fh );
	while ( $r = fgetcsv( $fh ) ) { $csv[ ltrim( $r[0], "\xEF\xBB\xBF" ) . '.' . $r[2] ] = array( 'display' => $r[9], 'why' => $r[12] ); }
	$needles = function ( $k, $d, $v ) use ( &$needles, $fad ) {
		$t = $d['type'];
		if ( 'repeater' === $t ) {
			$row = is_array( $v ) ? (array) reset( $v ) : array();
			$out = array();
			foreach ( (array) ( $d['fields'] ?? array() ) as $sk => $sd ) { if ( ! preg_match( '/row_status$/', $sk ) && isset( $row[ $sk ] ) ) { $out = array_merge( $out, $needles( $sk, $sd, $row[ $sk ] ) ); } }
			return $out;
		}
		$lab = array();
		foreach ( (array) ( $d['options'] ?? array() ) as $o ) { $lab[ (string) ( $o['value'] ?? '' ) ] = (string) ( $o['label'] ?? '' ); }
		if ( 'select' === $t || 'radio' === $t ) { return array( $lab[ (string) $v ] ?? (string) $v ); }
		if ( 'checkbox' === $t ) { $p = array_keys( array_filter( (array) $v ) ); $p = isset( $p[0] ) && is_int( $p[0] ) ? array_values( (array) $v ) : $p; return array_map( function ( $x ) use ( $lab ) { return $lab[ $x ] ?? $x; }, $p ); }
		if ( 'switcher' === $t ) { return array( (string) ( $d['label'] ?? $k ) ); }
		$v = (string) $v;
		if ( preg_match( '#^https?://example\.org(/\S+)#', $v, $m ) ) { return array( 'example.org' . $m[1], 'example.org' ); }
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) { return array( $v, $fad( $v ), $fad( (string) (int) substr( $v, 8, 2 ) ) ); }
		if ( preg_match( '/^\d{2}:\d{2}$/', $v ) ) { return array( $v, $fad( $v ), $fad( ltrim( $v, '0' ) ) ); }
		if ( 'route_stops_order' === $k ) { return array_map( 'get_the_title', array_filter( array_map( 'intval', explode( ',', $v ) ) ) ); }   /* کاشی «شهرهای مسیر» */
		if ( is_numeric( $v ) ) { return array( $fad( $v ), $fad( number_format( (float) $v ) ), str_replace( ',', '٬', $fad( number_format( (float) $v ) ) ) ); }
		if ( preg_match( '/^([\d.]+), ([\d.]+)$/', $v, $m ) ) { return array( $m[1], $fad( $m[1] ), rtrim( rtrim( $m[1], '0' ), '.' ) ); }
		$p = explode( ' — ', $v, 2 );
		return array( trim( preg_replace( '/\..*$/su', '', $p[1] ?? $p[0] ) ) );
	};
	$s = $samples();
	$rows = array(); $acc = 0; $new = 0;
	foreach ( array_keys( topersia_g_stat_cells() ) as $type ) {
		if ( empty( $s[ $type ]['fa'] ) ) { continue; }
		$pid  = $s[ $type ]['fa'];
		$res  = wp_remote_get( get_permalink( $pid ), array( 'timeout' => 90, 'redirection' => 0 ) );
		$raw  = is_wp_error( $res ) ? '' : wp_remote_retrieve_body( $res );
		$raw  = preg_replace( '#<script(?![^>]*ld\+json)[^>]*>.*?</script>#s', '', $raw );
		$html = html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( '/\s+/u', ' ', wp_strip_all_tags( preg_replace( '#<[^>]+\btitle="([^"]*)"[^>]*>#u', ' $1 $0', $html ) ) );
		preg_match_all( '#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $raw, $lj );   /* JSON-LD با گریز یونیکد */
		foreach ( $lj[1] as $j ) { $text .= ' ' . wp_json_encode( json_decode( $j, true ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); }
		$heh  = array( "\u{0647}\u{0654}" => "\u{06C0}" );   /* «ۀ» دونویسۀ اسکیما ⇄ «ۀ» رندر */
		$text = strtr( $text, $heh ); $html = strtr( $html, $heh );
		$sc   = topersia_pack_schema( $type );
		$tech = array_merge( (array) ( $sc['excluded'] ?? array() ), array( 'external_reference_uri' ) );
		foreach ( $sc['fields'] as $k => $d ) {
			if ( in_array( $k, $tech, true ) ) { continue; }
			$v  = get_post_meta( $pid, $k, true );
			$nd = array_filter( $needles( $k, $d, $v ), 'strlen' );
			$hit = false;
			foreach ( $nd as $x ) { $x = strtr( $x, $heh ); if ( false !== mb_strpos( $text, $x ) || false !== mb_strpos( $html, $x ) ) { $hit = true; break; } }
			if ( $hit ) { continue; }
			$c  = $csv[ $type . '.' . $k ] ?? array( 'display' => '', 'why' => '' );
			$ok = 0 === mb_strpos( $c['why'], 'لازم نیست' ) || 0 === mb_strpos( $c['display'], 'نه (FIELDMAP: skip)' );
			$ok ? $acc++ : $new++;
			$rows[] = array( $type, $k, $d['label'] ?? '', $d['type'], $ok ? 'پذیرفته — ' . $c['why'] : 'یافته — ' . ( '' !== $c['display'] ? 'META-DISPLAY: ' . $c['display'] : 'نه در META-DISPLAY' ) );
		}
	}
	echo "| نوع | فیلد | برچسب | گونه | وضع |\n|---|---|---|---|---|\n";
	foreach ( $rows as $r ) { echo '| `' . $r[0] . '` | `' . $r[1] . '` | ' . $r[2] . ' | ' . $r[3] . ' | ' . $r[4] . " |\n"; }
	echo "\nدیده‌نشده: " . count( $rows ) . ' · پذیرفته: ' . $acc . ' · یافته: ' . $new . "\n";
	return;
}

if ( 'create' !== $cmd ) { echo "create · list · seen · delete [confirm]\n"; return; }

/* ══ create ══ */
$have  = $samples();
$types = array_keys( topersia_g_stat_cells() );
$made  = array();
$idx   = 0;

/* تصویر WebP با GD */
$font  = 'C:/Windows/Fonts/arial.ttf';
$image = function ( $w, $h, $label, $sub, $seed, $alt, $parent ) use ( $font ) {
	$im = imagecreatetruecolor( $w, $h );
	$h1 = ( $seed * 47 ) % 360;
	$hsl = function ( $hue, $l ) { $c = ( 1 - abs( 2 * $l - 1 ) ) * 0.55; $x = $c * ( 1 - abs( fmod( $hue / 60, 2 ) - 1 ) ); $m = $l - $c / 2;
		$rgb = $hue < 60 ? array( $c, $x, 0 ) : ( $hue < 120 ? array( $x, $c, 0 ) : ( $hue < 180 ? array( 0, $c, $x ) : ( $hue < 240 ? array( 0, $x, $c ) : ( $hue < 300 ? array( $x, 0, $c ) : array( $c, 0, $x ) ) ) ) );
		return array_map( function ( $v ) use ( $m ) { return (int) round( ( $v + $m ) * 255 ); }, $rgb ); };
	$a = $hsl( $h1, 0.35 ); $b = $hsl( ( $h1 + 60 ) % 360, 0.6 );
	for ( $y = 0; $y < $h; $y++ ) {
		$t = $y / max( 1, $h - 1 );
		imageline( $im, 0, $y, $w, $y, imagecolorallocate( $im, (int) ( $a[0] + ( $b[0] - $a[0] ) * $t ), (int) ( $a[1] + ( $b[1] - $a[1] ) * $t ), (int) ( $a[2] + ( $b[2] - $a[2] ) * $t ) ) );
	}
	$white = imagecolorallocate( $im, 255, 255, 255 );
	imagettftext( $im, (int) ( $h / 12 ), 0, (int) ( $w * 0.06 ), (int) ( $h * 0.55 ), $white, $font, $label );
	imagettftext( $im, (int) ( $h / 28 ), 0, (int) ( $w * 0.06 ), (int) ( $h * 0.68 ), $white, $font, $sub . ' — SAMPLE' );
	$up   = wp_upload_dir();
	$name = sanitize_file_name( 'tp-sample-' . strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $label . '-' . $sub ) ) . '-' . $seed . '.webp' );
	$path = trailingslashit( $up['path'] ) . $name;
	imagewebp( $im, $path, 82 );
	imagedestroy( $im );
	$aid = wp_insert_attachment( array( 'post_mime_type' => 'image/webp', 'post_title' => $alt['fa'], 'post_status' => 'inherit', 'post_parent' => $parent ), $path, $parent );
	wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $path ) );
	update_post_meta( $aid, '_wp_attachment_image_alt', $alt['fa'] );
	update_post_meta( $aid, '_tp_sample', 1 );
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $aid, 'element_type' => 'post_attachment', 'trid' => false, 'language_code' => 'fa' ) );
	$trid = apply_filters( 'wpml_element_trid', null, $aid, 'post_attachment' );
	foreach ( array( 'en', 'ar' ) as $l ) {   /* ترجمهٔ پیوست: همان پرونده، alt همان زبان */
		$tid = wp_insert_post( array( 'post_type' => 'attachment', 'post_mime_type' => 'image/webp', 'post_title' => $alt[ $l ], 'post_status' => 'inherit', 'post_parent' => 0, 'guid' => get_post_field( 'guid', $aid ) ) );
		update_post_meta( $tid, '_wp_attached_file', get_post_meta( $aid, '_wp_attached_file', true ) );
		update_post_meta( $tid, '_wp_attachment_metadata', wp_get_attachment_metadata( $aid ) );
		update_post_meta( $tid, '_wp_attachment_image_alt', $alt[ $l ] );
		update_post_meta( $tid, '_tp_sample', 1 );
		do_action( 'wpml_set_element_language_details', array( 'element_id' => $tid, 'element_type' => 'post_attachment', 'trid' => $trid, 'language_code' => $l, 'source_language_code' => 'fa' ) );
	}
	return $aid;
};

/* مقدار ساختگی یک فیلد (یا زیرفیلد) از تعریف اسکیمای بسته */
$isarr = array();   /* چک‌باکس جت‌انجین: is_array ← فهرست ساده، وگرنه value => 'true' */
foreach ( (array) get_option( 'jet_engine_meta_boxes', array() ) as $box ) { foreach ( (array) ( $box['meta_fields'] ?? array() ) as $f ) { if ( 'checkbox' === ( $f['type'] ?? '' ) ) { $isarr[ $f['name'] ] = ! empty( $f['is_array'] ); } } }
$value = function ( $key, $d, $lang, $n, $type ) use ( &$value, $TXT, $SRC, $isarr ) {
	$k = strtolower( $key );
	$t = $d['type'];
	if ( 'repeater' === $t ) {
		$rows = array();
		for ( $r = 0; $r < 3; $r++ ) {
			$row = array();
			foreach ( (array) ( $d['fields'] ?? array() ) as $sk => $sd ) { $row[ $sk ] = $value( $sk, $sd, $lang, $n + $r, $type ); }
			if ( isset( $row['stop_id'] ) ) {   /* یادداشت توقف به همان شهرهای route_stops_order */
				$stops = get_posts( array( 'post_type' => 'tp_city', 'numberposts' => 3, 'post_status' => 'publish', 'fields' => 'ids', 'suppress_filters' => false ) );
				$row['stop_id'] = (string) ( $stops[ $r ] ?? '' );
			}
			$rows[ 'item-' . $r ] = $row;
		}
		return $rows;
	}
	$opts = array_values( array_filter( array_column( (array) ( $d['options'] ?? array() ), 'value' ), function ( $v ) { return 'unknown' !== $v && 'not_applicable' !== $v; } ) );
	if ( preg_match( '/row_status$/', $k ) ) { return 'active'; }
	if ( 'select' === $t || 'radio' === $t ) { return $opts ? $opts[ $n % count( $opts ) ] : ''; }
	if ( 'checkbox' === $t ) { $pick = $opts ? array_values( array_unique( array( $opts[0], $opts[ min( 1, count( $opts ) - 1 ) ] ) ) ) : array(); return ( $isarr[ $key ] ?? true ) ? $pick : array_fill_keys( $pick, 'true' ); }
	if ( 'switcher' === $t ) { return 'true'; }
	if ( 'date' === $t ) { return preg_match( '/end|until|checked|access|verified|observed/', $k ) ? '2026-' . sprintf( '%02d', 6 + $n % 5 ) . '-15' : '2026-11-' . sprintf( '%02d', 1 + $n % 20 ); }
	if ( 'time' === $t ) { return preg_match( '/to$|_to|end/', $k ) ? '17:30' : '09:00'; }
	if ( 'number' === $t ) {
		if ( preg_match( '/year/', $k ) ) { return (string) ( 1500 + ( $n * 37 ) % 520 ); }
		if ( preg_match( '/population|speaker/', $k ) ) { return (string) ( 120000 + $n * 7310 ); }
		if ( preg_match( '/price|fee|ratio/', $k ) ) { return (string) ( 500000 * ( 1 + $n % 9 ) ); }
		if ( preg_match( '/area/', $k ) ) { return (string) ( 350 + $n * 13 ); }
		if ( preg_match( '/elevation|height|depth/', $k ) ) { return (string) ( 900 + $n * 41 ); }
		if ( preg_match( '/month/', $k ) ) { return (string) ( 1 + $n % 12 ); }
		return (string) ( 3 + $n % 9 );
	}
	if ( 'text' === $t && preg_match( '/(^|_)year(_|$)/', $k ) ) { return (string) ( 1500 + ( $n * 37 ) % 520 ); }   /* سال در فیلد متنی (founded_year) — خانهٔ سربرگ عدد می‌خواهد */
	if ( 'world_heritage_ref_number' === $k ) { return (string) ( 1000 + $n ); }   /* نشان «شمارۀ ثبت N» عدد می‌خواهد */
	if ( 'source_name' === $k ) { return $SRC; }
	if ( preg_match( '/(_url|_link|portal|website|uri)$|^url$/', $k ) || 'source_url' === $k ) { return 'https://example.org/sample/' . str_replace( 'tp_', '', $type ) . '/' . $k; }
	if ( 'geo_coordinates' === $k ) { return sprintf( '%.4f, %.4f', 29.5 + fmod( $n * 0.73, 8 ), 48.5 + fmod( $n * 1.17, 12 ) ); }
	if ( preg_match( '/iso|code$/', $k ) && 'programme_code' !== $k ) { return 'SMP'; }
	if ( preg_match( '/^name_(fa|en|ar)$/', $k ) ) { return ''; }   /* از جدول نام */
	$base = $TXT[ $lang ][0] . ' — ' . ( $d['label'] ?? $key );
	if ( in_array( $t, array( 'textarea', 'wysiwyg' ), true ) ) { return $base . '. ' . $TXT[ $lang ][1]; }
	return $base;
};

$aistore = function ( $pid, $lang, $name ) {
	$texts = array(
		'fa' => array( "{$name} یکی از نمونه‌های ساختگی این سایت برای ایرادگیری نمایش است. این خلاصه فقط برای سنجیدن بلوک «در یک نگاه» نوشته شده و هیچ عدد یا جمله‌ای از آن منبع نیست. نمونه‌ها در صفر کردن پیش از راه‌اندازی حذف می‌شوند؛ پس هر چه در این متن می‌خوانید را داده ندانید و برای سفر واقعی به منبع رسمی رجوع کنید.", 'نکتهٔ نمونه برای سنجش طول هر مورد در فهرست نکات کلیدی بلوک', 'برنامهٔ نمونه: روز اول بازدید از بخش مرکزی و آشنایی با پیشینه؛ روز دوم گشت در اطراف و دیدار با مردم محلی؛ روز سوم بازگشت. این برنامه ساختگی است و در صفر کردن حذف می‌شود؛ برای سفر واقعی برنامه را از منبع رسمی و راهنمای محلی بپرسید و زمان‌ها را دوباره بسنجید.' ),
		'en' => array( "{$name} is one of this site's fictional samples for display review. This summary exists only to exercise the at-a-glance block; none of its numbers or sentences are a source. Samples are removed at the pre-launch reset, so treat nothing here as data and check an official source before any real trip planning.", 'Sample key point written to test the length of each list item', 'Sample plan: day one, the central quarter and its background; day two, the surroundings and a visit with local people; day three, the way back. This plan is fictional and is removed at reset; ask an official source and a local guide for a real itinerary and check all times again.' ),
		'ar' => array( "{$name} أحد النماذج المتخيَّلة في هذا الموقع لمراجعة العرض. كُتب هذا الملخص فقط لاختبار كتلة «لمحة سريعة» ولا شيء من أرقامه أو جمله مصدرٌ. تُحذف النماذج عند إعادة الضبط قبل الإطلاق، فلا تعدّ أي شيء هنا بيانات وراجع مصدرًا رسميًا قبل أي رحلة فعلية تخطط لها.", 'نقطة نموذجية كُتبت لاختبار طول كل عنصر في القائمة', 'خطة نموذجية: اليوم الأول جولة في الحي المركزي والتعرّف إلى تاريخه؛ اليوم الثاني زيارة الأطراف ولقاء السكان المحليين؛ اليوم الثالث العودة. هذه الخطة متخيَّلة وتُحذف عند إعادة الضبط؛ اسأل مصدرًا رسميًا ودليلًا محليًا عن برنامج حقيقي.' ),
	);
	$t  = $texts[ $lang ];
	$kp = implode( "\n", array_map( function ( $i ) use ( $t ) { return '- ' . $t[1] . ' ' . $i; }, range( 1, 5 ) ) );
	foreach ( array( 'summary' => $t[0], 'keypoints' => $kp, 'plan' => $t[2] ) as $type => $text ) {
		if ( class_exists( 'TP_AI_DB' ) ) { TP_AI_DB::save_summary( $pid, $type, $lang, $text, 'sample', '', 'internal' ); }
		if ( class_exists( 'TP_AI_Core_Bridge' ) && method_exists( 'TP_AI_Core_Bridge', 'stamp' ) && TP_AI_Core_Bridge::cache_available() ) { TP_AI_Core_Bridge::stamp( $pid, $type, $lang ); }
		delete_post_meta( $pid, '_tpas_review_' . $type . '_' . $lang );
	}
};

foreach ( $types as $type ) {
	$idx++;
	if ( isset( $have[ $type ]['fa'] ) ) {   /* موجود: فقط فیلد غیرفنیِ خالی پر می‌شود (اسکیمای تازه‌تر) */
		$pid = $made[ $type ] = $have[ $type ]['fa'];
		foreach ( topersia_pack_schema( $type )['fields'] as $key => $d ) {
			if ( in_array( $key, array( 'external_reference_uri', 'name_fa', 'name_en', 'name_ar', 'meta_title', 'route_stops_order' ), true ) || in_array( $key, (array) ( topersia_pack_schema( $type )['excluded'] ?? array() ), true ) ) { continue; }
			$cur = get_post_meta( $pid, $key, true );
			if ( '' === $cur || array() === $cur ) { update_post_meta( $pid, $key, $value( $key, $d, 'fa', $idx, $type ) ); echo "~ {$type}.{$key}
"; }
		}
		continue;
	}
	$cands = $NAMES[ $type ] ?? array( array( 'نمونهٔ ' . $type, 'Sample ' . $type, 'نموذج ' . $type ) );
	$nm    = $cands[0];
	foreach ( $cands as $c ) {
		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_title = %s AND post_status <> 'trash' LIMIT 1", $type, $c[0] ) );
		if ( ! $exists ) { $nm = $c; break; }
	}
	$schema = topersia_pack_schema( $type );
	$slug   = sanitize_title( $nm[1] );
	$pid    = wp_insert_post( array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $nm[0], 'post_name' => $slug,
		'post_content' => '<p>' . $TXT['fa'][1] . '</p>', 'post_excerpt' => $TXT['fa'][0] ), true );
	if ( is_wp_error( $pid ) ) { echo "✖ {$type}: " . $pid->get_error_message() . "\n"; continue; }
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $pid, 'element_type' => 'post_' . $type, 'trid' => false, 'language_code' => 'fa' ) );
	update_post_meta( $pid, '_tp_sample', 1 );
	update_post_meta( $pid, 'rank_math_robots', array( 'noindex', 'nofollow' ) );
	foreach ( $schema['fields'] as $key => $d ) {
		$v = $value( $key, $d, 'fa', $idx, $type );
		if ( 'name_fa' === $key ) { $v = $nm[0]; } elseif ( 'name_en' === $key ) { $v = $nm[1]; } elseif ( 'name_ar' === $key ) { $v = $nm[2]; }
		if ( 'meta_title' === $key ) { $v = $nm[0] . ' — نمونه'; }
		if ( 'external_reference_uri' === $key ) { continue; }   /* sameAs به ویکی‌دادهٔ واقعی گمراه‌کننده است */
		if ( 'route_stops_order' === $key ) { $v = implode( ',', get_posts( array( 'post_type' => 'tp_city', 'numberposts' => 3, 'post_status' => 'publish', 'fields' => 'ids', 'suppress_filters' => false ) ) ); }
		update_post_meta( $pid, $key, $v );
	}
	update_post_meta( $pid, 'content_status', 'needs_verification' );
	update_post_meta( $pid, 'last_verified_date', '2026-10-26' );
	/* تصویرها */
	$alt  = function ( $i ) use ( $nm ) { return array( 'fa' => $nm[0] . ' — تصویر نمونهٔ ' . $i, 'en' => $nm[1] . ' — sample image ' . $i, 'ar' => $nm[2] . ' — صورة نموذجية ' . $i ); };
	$hero = $image( 2400, 1200, $nm[1], str_replace( 'tp_', '', $type ), $idx * 10, $alt( 0 ), $pid );
	update_post_meta( $pid, 'hero_image', $hero );
	set_post_thumbnail( $pid, $hero );
	$gal = array();
	for ( $g = 1; $g <= 6; $g++ ) { $gal[] = $image( 1600, 1000, $nm[1] . ' ' . $g, str_replace( 'tp_', '', $type ), $idx * 10 + $g, $alt( $g ), $pid ); }
	update_post_meta( $pid, 'featured_image_gallery', implode( ',', $gal ) );
	update_post_meta( $pid, 'video_sample', 'https://example.org/sample/video.mp4' );
	update_post_meta( $pid, 'audio_narration', array( 'item-0' => array( 'audio_title' => $TXT['fa'][0], 'audio_source_type' => 'external_url', 'audio_external_url' => 'https://example.org/sample/audio.mp3', 'audio_duration' => '03:20', 'audio_transcript' => $TXT['fa'][1], 'audio_row_status' => 'active' ) ) );
	wp_update_post( array( 'ID' => $pid ) );   /* entity_uid و قلاب‌های ذخیره */
	if ( 'tp_route' === $type ) { update_post_meta( $pid, 'route_stop_notes', $value( 'route_stop_notes', $schema['fields']['route_stop_notes'], 'fa', $idx, $type ) ); }   /* هسته با ذخیره یادداشتِ توقفِ بیرون از ترتیب را بازنشسته می‌کند */
	$made[ $type ] = $pid;
	echo "+ {$type} #{$pid} {$nm[0]}\n";
}

/* روابط: هر رابطهٔ هر نوع با ۲ تا ۳ موجودیت (فقط نسخهٔ فارسی؛ ترجمه با نگاشت بستهٔ WPML) */
$rels = jet_engine()->relations->get_active_relations();
$linked = 0;
foreach ( $made as $type => $pid ) {
	foreach ( topersia_pack_schema( $type )['relations'] as $rk => $r ) {
		$obj = $rels[ $r['id'] ] ?? null;
		if ( ! $obj ) { continue; }
		$have_now = 'parents' === $r['direction'] ? $obj->get_parents( $pid, 'ids' ) : $obj->get_children( $pid, 'ids' );
		if ( count( (array) $have_now ) >= 2 || ( $r['max'] && count( (array) $have_now ) >= $r['max'] ) ) { continue; }
		$want = $r['max'] ? $r['max'] : 3;
		$targets = get_posts( array( 'post_type' => $r['target'], 'numberposts' => -1, 'post_status' => 'publish', 'fields' => 'ids', 'suppress_filters' => false, 'orderby' => 'ID', 'order' => 'ASC' ) );
		$single  = 'children' === $r['direction'] && in_array( $obj->get_args( 'type' ), array( 'one_to_many', 'one_to_one' ), true );
		if ( $single ) { $targets = array_values( array_filter( $targets, function ( $x ) use ( $obj ) { return ! $obj->get_parents( $x, 'ids' ); } ) ); }   /* هدف بی والد اول */
		if ( isset( $made[ $r['target'] ] ) && $made[ $r['target'] ] !== $pid ) { array_unshift( $targets, $made[ $r['target'] ] ); }
		$targets = array_slice( array_values( array_unique( array_diff( array_map( 'intval', $targets ), array( $pid ) ) ) ), 0, $want );
		foreach ( $targets as $tid ) {
			/* سوی دیگر یک‌به‌چند: والدِ تکی هدف را جابه‌جا نکن */
			if ( 'children' === $r['direction'] && in_array( $obj->get_args( 'type' ), array( 'one_to_many', 'one_to_one' ), true ) && $obj->get_parents( $tid, 'ids' ) ) { continue; }
			'parents' === $r['direction'] ? $obj->update( $tid, $pid ) : $obj->update( $pid, $tid );
			$linked++;
		}
	}
}
echo "روابط: {$linked} یال\n";

/* ترجمه‌ها */
foreach ( $made as $type => $pid ) {
	$trid   = apply_filters( 'wpml_element_trid', null, $pid, 'post_' . $type );
	$schema = topersia_pack_schema( $type );
	foreach ( array( 'en', 'ar' ) as $i => $l ) {
		$tr = (int) apply_filters( 'wpml_object_id', $pid, $type, false, $l );
		if ( ! $tr ) {
			$name = (string) get_post_meta( $pid, 'name_' . $l, true );
			$tr   = wp_insert_post( array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $name, 'post_name' => get_post_field( 'post_name', $pid ),
				'post_content' => '<p>' . $TXT[ $l ][1] . '</p>', 'post_excerpt' => $TXT[ $l ][0] ) );
			do_action( 'wpml_set_element_language_details', array( 'element_id' => $tr, 'element_type' => 'post_' . $type, 'trid' => $trid, 'language_code' => $l, 'source_language_code' => 'fa' ) );
		}
		update_post_meta( $tr, '_tp_sample', 1 );
		update_post_meta( $tr, 'rank_math_robots', array( 'noindex', 'nofollow' ) );
		foreach ( $schema['fields'] as $key => $d ) {
			if ( 'translate' === ( $d['wpml'] ?? '' ) ) {
				$v = $value( $key, $d, $l, $i + 1, $type );
				if ( in_array( $key, array( 'name_fa', 'name_en', 'name_ar' ), true ) ) { $v = get_post_meta( $pid, $key, true ); }
				if ( 'meta_title' === $key ) { $v = get_post_meta( $pid, 'name_' . $l, true ) . ( 'en' === $l ? ' — sample' : ' — نموذج' ); }
				update_post_meta( $tr, $key, $v );
			} else {
				update_post_meta( $tr, $key, get_post_meta( $pid, $key, true ) );   /* کپی */
			}
		}
		foreach ( array( 'hero_image', 'featured_image_gallery', 'video_sample', 'audio_narration', 'content_status', 'last_verified_date', 'entity_uid' ) as $k ) { update_post_meta( $tr, $k, get_post_meta( $pid, $k, true ) ); }
		set_post_thumbnail( $tr, (int) get_post_meta( $pid, 'hero_image', true ) );
		if ( function_exists( 'topersia_pack_tr_relations' ) ) { topersia_pack_tr_relations( $pid, $tr, $l ); }
		$aistore( $tr, $l, (string) get_post_meta( $pid, 'name_' . $l, true ) );
	}
	$aistore( $pid, 'fa', (string) get_post_meta( $pid, 'name_fa', true ) );
}
echo 'نمونه‌ها: ' . count( $made ) . " نوع (fa · en · ar)\n";
