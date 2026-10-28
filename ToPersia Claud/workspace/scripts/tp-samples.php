<?php
/**
 * «نمونهٔ کامل» هر ۴۱ نوع — دستهٔ ۳۰ بند ۲. فقط محلی (topersia.local). برای ایرادگیری نمایش، نه داده.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-samples.php create
 *   … list
 *   … seen              ← «فیلد پر ولی دیده‌نشده» روی صفحهٔ fa هر نمونه (جدول md)
 *   … remove            ← پیش‌فرض dry-run: چه پاک می‌شود (نوشته، پیوست، رابطه از هر دو سو، trid، نمایهٔ جغرافیایی)
 *   … remove confirm    ← پشتیبان بیرون از ریشهٔ وب (C:/wamp64/tp-backups/samples-*.json) ← پاک‌کردن کامل  (delete = همان)
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
	'tp_border_crossing'  => array( array( 'مرز بازرگان', 'Bazargan Border Crossing', 'منفذ بازارغان' ), array( 'مرز آستارا', 'Astara Border Crossing', 'منفذ آستارا' ) ),
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

/* نشانی زبانِ خودِ پست — get_permalink در wp-cli پیشوند زبان WPML را نمی‌گذارد */
$tp_url = function ( $id, $l ) { do_action( 'wpml_switch_language', $l ); $u = get_permalink( $id ); do_action( 'wpml_switch_language', 'fa' ); return $u; };

/* ══ list ══ */
if ( 'list' === $cmd ) {
	$s = $samples();
	foreach ( array_keys( topersia_g_stat_cells() ) as $t ) {
		$row = $s[ $t ] ?? array();
		echo str_pad( $t, 22 ) . ' ' . implode( ' · ', array_map( function ( $l ) use ( $row, $tp_url ) { return $l . '=' . ( isset( $row[ $l ] ) ? $row[ $l ] . ' ' . $tp_url( $row[ $l ], $l ) : '—' ); }, array( 'fa', 'en', 'ar' ) ) ) . "\n";
	}
	return;
}

/* ══ remove (delete = همان) — پاک‌کردن کامل نمونه‌ها؛ پیش‌فرض dry-run، با «confirm» اجرا ══
 * نوشته‌ها و ترجمه‌ها، پیوست‌ها (و پرونده‌شان)، ردیف رابطه از هر دو سو، trid های WPML، نمایهٔ جغرافیایی و خلاصه‌های هوش
 * مصنوعی. پیش از پاک‌کردن پشتیبان JSON بیرون ریشهٔ وب (TP_SAMPLES_BACKUP_DIR یا tp-backups). دستهٔ 32 بند 6 · P5-D457. */
if ( in_array( $cmd, array( 'remove', 'delete' ), true ) ) {
	$apply = $conf && ! in_array( '--dry-run', (array) $args, true );
	$bdir  = getenv( 'TP_SAMPLES_BACKUP_DIR' ) ? trailingslashit( getenv( 'TP_SAMPLES_BACKUP_DIR' ) ) : $bak;
	$flag  = array_map( 'intval', $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tp_sample'" ) );
	$ids   = $flag;
	if ( $flag ) {   /* پیوست‌هایی که پدرشان نمونه است ولی نشان ندارند */
		$ids = array_merge( $ids, array_map( 'intval', $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_parent IN (" . implode( ',', $flag ) . ')' ) ) );
	}
	$ids = array_values( array_unique( $ids ) );
	if ( ! $ids ) { echo "نمونه‌ای نیست\n"; return; }
	$in  = implode( ',', $ids );
	$tbl = array( 'rel' => $wpdb->prefix . 'jet_rel_default', 'icl' => $wpdb->prefix . 'icl_translations', 'geo' => $wpdb->prefix . 'topersia_geo', 'ai' => $wpdb->prefix . 'topersia_ai_summaries' );
	$type_of = $wpdb->get_results( "SELECT ID, post_type, post_title FROM {$wpdb->posts} WHERE ID IN ($in)", OBJECT_K );
	$att = array_values( array_filter( $ids, function ( $i ) use ( $type_of ) { return isset( $type_of[ $i ] ) && 'attachment' === $type_of[ $i ]->post_type; } ) );
	$pst = array_values( array_diff( $ids, $att ) );
	$by  = array();
	foreach ( $pst as $i ) { $by[ $type_of[ $i ]->post_type ] = ( $by[ $type_of[ $i ]->post_type ] ?? 0 ) + 1; }
	$rels_rows = $wpdb->get_results( "SELECT * FROM {$tbl['rel']} WHERE parent_object_id IN ($in) OR child_object_id IN ($in)", ARRAY_A );
	$real_touch = array(); $viol = array();
	$active = function_exists( 'jet_engine' ) ? jet_engine()->relations->get_active_relations() : array();
	foreach ( $rels_rows as $r ) {
		$p = (int) $r['parent_object_id']; $c = (int) $r['child_object_id'];
		$ps = in_array( $p, $ids, true ); $cs = in_array( $c, $ids, true );
		if ( $ps && $cs ) { continue; }
		$real = $ps ? $c : $p;
		$real_touch[ $real ] = 1;
		$ty = isset( $active[ (int) $r['rel_id'] ] ) ? (string) $active[ (int) $r['rel_id'] ]->get_args( 'type' ) : '?';
		if ( 'many_to_many' !== $ty && ! ( 'one_to_many' === $ty && $cs ) ) { $viol[] = $r['rel_id'] . ':' . $p . '→' . $c . ' (' . $ty . ')'; }   /* P5-D456 */
	}
	$files = array(); $bytes = 0;
	foreach ( $att as $i ) { $f = (string) get_post_meta( $i, '_wp_attached_file', true ); if ( '' !== $f ) { $files[ $f ] = 1; } }
	$ud = wp_upload_dir();
	foreach ( array_keys( $files ) as $f ) { $p = trailingslashit( $ud['basedir'] ) . $f; if ( is_file( $p ) ) { $bytes += filesize( $p ); } }
	$icl = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl['icl']} WHERE element_id IN ($in) AND element_type LIKE 'post\_%'" );
	$geo = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl['geo']} WHERE post_id IN ($in)" );
	$aic = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tbl['ai']} WHERE post_id IN ($in)" );
	echo 'پست (همهٔ زبان‌ها): ' . count( $pst ) . ' · پیوست: ' . count( $att ) . ' (پروندهٔ یکتا ' . count( $files ) . '، ' . round( $bytes / 1048576, 1 ) . " MB)\n";
	echo 'نوع‌ها: ' . count( $by ) . ' · ردیف رابطه: ' . count( $rels_rows ) . ' (با پست واقعی: ' . count( $real_touch ) . ' پست) · trid WPML: ' . $icl . ' · نمایهٔ جغرافیایی: ' . $geo . ' · خلاصهٔ هوش مصنوعی: ' . $aic . "\n";
	if ( $viol ) { echo 'هشدار P5-D456 — یال تک‌والد/یک‌به‌یک با پست واقعی: ' . implode( ' ', array_slice( $viol, 0, 10 ) ) . "\n"; }
	if ( ! $apply ) { echo "dry-run — چیزی پاک نشد؛ اجرا: remove confirm\n"; return; }

	wp_mkdir_p( $bdir );
	$dump = array( 'at' => gmdate( 'c' ), 'posts' => array(), 'rel' => $rels_rows, 'icl' => array(), 'geo' => array(), 'ai' => array(), 'files' => array_keys( $files ) );
	foreach ( $ids as $i ) { $dump['posts'][ $i ] = array( 'post' => get_post( $i, ARRAY_A ), 'meta' => get_post_meta( $i ) ); }
	$dump['icl'] = $wpdb->get_results( "SELECT * FROM {$tbl['icl']} WHERE element_id IN ($in) AND element_type LIKE 'post\_%'", ARRAY_A );
	$dump['geo'] = $wpdb->get_results( "SELECT * FROM {$tbl['geo']} WHERE post_id IN ($in)", ARRAY_A );
	$dump['ai']  = $wpdb->get_results( "SELECT * FROM {$tbl['ai']} WHERE post_id IN ($in)", ARRAY_A );
	$file = $bdir . 'samples-' . gmdate( 'Ymd-His' ) . '.json';
	if ( false === file_put_contents( $file, wp_json_encode( $dump, JSON_UNESCAPED_UNICODE ) ) ) { echo "پشتیبان نوشته نشد — توقف\n"; return; }

	$wpdb->query( "DELETE FROM {$tbl['rel']} WHERE parent_object_id IN ($in) OR child_object_id IN ($in)" );
	$wpdb->query( "DELETE FROM {$tbl['ai']} WHERE post_id IN ($in)" );
	$wpdb->query( "DELETE FROM {$tbl['geo']} WHERE post_id IN ($in)" );
	foreach ( $pst as $i ) { wp_delete_post( $i, true ); }
	foreach ( $att as $i ) {
		$shared = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s AND post_id <> %d", get_post_meta( $i, '_wp_attached_file', true ), $i ) );
		if ( $shared ) { wp_delete_post( $i, true ); } else { wp_delete_attachment( $i, true ); }   /* ترجمهٔ پیوست همان پرونده را دارد */
	}
	$wpdb->query( "DELETE FROM {$tbl['icl']} WHERE element_id IN ($in) AND element_type LIKE 'post\_%'" );   /* trid بازمانده */
	foreach ( array_keys( $real_touch ) as $rid ) { if ( function_exists( 'topersia_geo_index_put' ) && get_post( $rid ) ) { topersia_geo_index_put( $rid ); } }
	/* کارهای صفِ خودِ نمونه‌ها (گالری تجمیعی) — نمونه رفته، کارش بی‌هدف است */
	if ( function_exists( 'as_unschedule_all_actions' ) ) { foreach ( $pst as $i ) { as_unschedule_all_actions( 'topersia_gallery_agg_rebuild', array( 'post' => $i ), defined( 'TOPERSIA_QUEUE_GROUP' ) ? TOPERSIA_QUEUE_GROUP : '' ); } }
	/* مشتق‌های پست واقعیِ درگیر: گالری تجمیعی (بازسازی همگام از شهر تا کشور، و کارهای صفِ همان پست‌ها برداشته) و هش محتوا (به رابطه‌ها وابسته است) */
	if ( function_exists( 'topersia_g_gallery_agg_affected' ) ) {
		$aff = array();
		foreach ( array_keys( $real_touch ) as $rid ) { if ( get_post( $rid ) ) { foreach ( topersia_g_gallery_agg_affected( $rid ) as $a ) { $aff[ (int) $a ] = 1; } } }
		$aff = array_keys( $aff );
		usort( $aff, function ( $x, $y ) { return topersia_g_gallery_agg_rank( $x ) <=> topersia_g_gallery_agg_rank( $y ); } );
		foreach ( $aff as $a ) { topersia_g_gallery_agg_rebuild( $a, false ); }
		if ( function_exists( 'as_unschedule_all_actions' ) ) { foreach ( $aff as $a ) { as_unschedule_all_actions( 'topersia_gallery_agg_rebuild', array( 'post' => $a ), defined( 'TOPERSIA_QUEUE_GROUP' ) ? TOPERSIA_QUEUE_GROUP : '' ); } }
	}
	if ( function_exists( 'topersia_content_hash' ) ) { foreach ( array_keys( $real_touch ) as $rid ) { if ( get_post( $rid ) ) { topersia_content_hash( $rid, true ); } } }
	if ( function_exists( 'topersia_geo_index_stamp' ) ) { topersia_geo_index_stamp( true ); }
	clean_post_cache( 0 );
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
	$rows = array(); $acc = 0; $new = 0; $def = 0;
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
			/* پذیرفته فقط فنی یا FIELDMAP: skip؛ «لازم نیست تا داده بیاید» (دستهٔ ۲۷) با دادهٔ نمونه دیگر دلیل نیست — جدا، «جا ندارد» */
			$ok    = 0 === mb_strpos( $c['why'], 'لازم نیست — فنی' ) || 0 === mb_strpos( $c['display'], 'نه (FIELDMAP: skip)' );
			$wait  = ! $ok && 0 === mb_strpos( $c['why'], 'لازم نیست تا داده بیاید' );
			$ok ? $acc++ : ( $wait ? $def++ : $new++ );
			$rows[] = array( $type, $k, $d['label'] ?? '', $d['type'], $ok ? 'پذیرفته — ' . $c['why'] : ( $wait ? 'جا ندارد — دستهٔ ۲۷: «تا داده بیاید»' : 'یافته — ' . ( '' !== $c['display'] ? 'META-DISPLAY: ' . $c['display'] : 'نه در META-DISPLAY' ) ) );
		}
	}
	echo "| نوع | فیلد | برچسب | گونه | وضع |\n|---|---|---|---|---|\n";
	foreach ( $rows as $r ) { echo '| `' . $r[0] . '` | `' . $r[1] . '` | ' . $r[2] . ' | ' . $r[3] . ' | ' . $r[4] . " |\n"; }
	echo "\nدیده‌نشده: " . count( $rows ) . ' · پذیرفته: ' . $acc . ' · جا ندارد (دستهٔ ۲۷): ' . $def . ' · یافته: ' . $new . "\n";
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

/* ویدیوی نمونه: یک پروندهٔ webm کوچک محلی (scripts/samples/sample-video.webm)، یک پیوند مشترک برای همهٔ نمونه‌ها — دستهٔ 32 بند 5 */
$video = function () {
	static $url = null;
	if ( null !== $url ) { return $url; }
	$have = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true, 'meta_query' => array( array( 'key' => '_tp_sample_video', 'value' => '1' ) ) ) );
	if ( $have ) { return $url = (string) wp_get_attachment_url( $have[0] ); }
	$src = __DIR__ . '/samples/sample-video.webm';
	if ( ! is_file( $src ) ) { return $url = ''; }
	$up   = wp_upload_dir();
	$path = trailingslashit( $up['path'] ) . 'tp-sample-video.webm';
	copy( $src, $path );
	$aid = wp_insert_attachment( array( 'post_mime_type' => 'video/webm', 'post_title' => 'Sample video', 'post_status' => 'inherit', 'guid' => trailingslashit( $up['url'] ) . 'tp-sample-video.webm' ), $path );
	update_attached_file( $aid, $path );
	update_post_meta( $aid, '_tp_sample', 1 );
	update_post_meta( $aid, '_tp_sample_video', 1 );
	return $url = (string) wp_get_attachment_url( $aid );
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
	if ( 'currency_symbol' === $k ) { return '€'; }
	if ( false !== mb_strpos( (string) ( $d['label'] ?? '' ), 'لاتین' ) ) { return 'Sample ' . ucwords( str_replace( '_', ' ', $k ) ); }   /* فیلد لاتین (page_title) — دروازهٔ زبان P5-D278 لاتین را در هر سه زبان نگه می‌دارد */
	/* برچسب فارسی فقط در متن فارسی؛ en و ar بی واژهٔ فارسی، وگرنه دروازهٔ زبان (topersia_g_foreign_text) خالی‌اش می‌کند */
	$base = $TXT[ $lang ][0] . ' — ' . ( 'fa' === $lang ? ( $d['label'] ?? $key ) : ( 'en' === $lang ? ucfirst( str_replace( '_', ' ', $k ) ) : $TXT['ar'][0] ) );
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
		foreach ( array( $pid, $have[ $type ]['en'] ?? 0, $have[ $type ]['ar'] ?? 0 ) as $vp ) {   /* ویدیوی نمونهٔ پیشین (example.org) ← پروندهٔ محلی؛ دستهٔ 32 بند 5 */
			if ( $vp && false !== strpos( (string) get_post_meta( $vp, 'video_sample', true ), 'example.org' ) && $video() ) { update_post_meta( $vp, 'video_sample', $video() ); echo "~ {$type}.video_sample
"; }
		}
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
	update_post_meta( $pid, 'video_sample', $video() );
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
		$rtype = $obj->get_args( 'type' );
		foreach ( $targets as $tid ) {
			/* پست واقعی فقط در چندبه‌چند، یا والد واقعی برای فرزند نمونه در یک‌به‌چند؛
			   نمونه هرگز والد تکی پست واقعی نمی‌شود و یک‌به‌یکِ واقعی دست نمی‌خورد (دستهٔ ۳۰: آلودگی ۱۵ یال) */
			$is_sample = (bool) get_post_meta( $tid, '_tp_sample', true );
			if ( ! $is_sample && 'many_to_many' !== $rtype && ! ( 'parents' === $r['direction'] && 'one_to_many' === $rtype ) ) { continue; }
			/* سوی دیگر یک‌به‌چند: والدِ تکی هدف را جابه‌جا نکن */
			if ( 'children' === $r['direction'] && in_array( $rtype, array( 'one_to_many', 'one_to_one' ), true ) && $obj->get_parents( $tid, 'ids' ) ) { continue; }
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
		if ( get_post_field( 'post_name', $tr ) !== get_post_field( 'post_name', $pid ) ) {
			wp_update_post( array( 'ID' => $tr ) );   /* topersia_sync_translated_slug هسته: نامک ترجمه = نامک اصل (بی «-2») — پیش از نوشتن متا، چون WPML با ذخیره فیلدهای copy را از اصل می‌نشاند */
		}
		update_post_meta( $tr, '_tp_sample', 1 );
		update_post_meta( $tr, 'rank_math_robots', array( 'noindex', 'nofollow' ) );
		foreach ( $schema['fields'] as $key => $d ) {
			if ( in_array( $key, array( 'external_reference_uri', 'route_stops_order' ), true ) ) { update_post_meta( $tr, $key, get_post_meta( $pid, $key, true ) ); continue; }
			$own = 'translate' === ( $d['wpml'] ?? '' ) || ( '' === ( $d['wpml'] ?? '' ) && in_array( $d['type'], array( 'text', 'textarea', 'wysiwyg', 'repeater' ), true ) );
			if ( $own ) {   /* translate، یا متنِ بی کلاس WPML = هر زبان مقدار خودش */
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
