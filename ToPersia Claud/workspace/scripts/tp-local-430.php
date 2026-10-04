<?php
/**
 * دادهٔ محلی ۴٫۳٫۰ — فقط روی topersia.local.
 *
 * هیچ‌چیز از این پرونده به سایت زنده نمی‌رود: نگهبانِ آغاز بیرون از
 * `topersia.local` با محیط `local` اجرا نمی‌شود. هر گام جداست و اجرای
 * دوباره‌اش بی‌خطر است (پستِ هم‌نامک به‌روز می‌شود نه تکرار).
 *
 *   glossary   واژه‌نامهٔ ۳۷ + needs_verification؛ ۲۴ پستِ ۴٫۲٫۳ به آن    ۱۳
 *   header     هدر وودمارت به حالت clone، همان سایت زنده                 ۱۳
 *   rel71      ایران ← ۳۱ استان با رابطهٔ ۷۱                             ۸
 *   practical  یک نمونهٔ منبع‌دار برای هر دستهٔ «اطلاعات سفر» که نوع دارد  ۸
 *   testuser   کاربر آزمونِ مشترک                                          پذیرش
 *   config     api.met.no در WP_ACCESSIBLE_HOSTS                          ۲
 *   news-model نوع tpc_news، متاباکس، چهار رابطه، WPML                     ۶
 *   news       خبرهای واقعیِ اخیرِ استان اصفهان با منبع                   ۶
 *   reviews    review type هر خانواده از user-core                         ۷
 *
 *   php scripts/tp-local-430.php <گام>… | all
 *
 * قاعده‌ها همان ۴٫۲٫۳: هر واقعیت منبع دارد یا نوشته نمی‌شود؛ رابطه‌ها با
 * API جت‌انجین؛ فقط فارسی؛ بی‌تصویر. وضعیت محتوا `needs_verification`.
 */

require_once dirname( __DIR__ ) . '/tests/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

const TP_WP     = TP_WP_DIR . '/';
const TP_REPORT = TP_ROOT . '/LOCAL-DATA.txt';
const TP_MARK   = '══════ ۴٫۳٫۰ ══════';

$steps = array_slice( $argv, 1 );

if ( ! $steps ) {
	fwrite( STDERR, "گام؟ glossary header rel71 practical testuser config news-model news reviews | all\n" );
	exit( 2 );
}

/* config پیش از بارگذاری وردپرس: ثابت‌ها بعد از بار شدن عوض نمی‌شوند. */
if ( in_array( 'config', $steps, true ) || in_array( 'all', $steps, true ) ) {
	tp_step_config();
}

require TP_WP . 'wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) {
	fwrite( STDERR, "فقط نسخهٔ محلی — topersia.local با محیط local\n" );
	exit( 2 );
}

if ( ! function_exists( 'jet_engine' ) || ! jet_engine()->relations ) {
	fwrite( STDERR, "جت‌انجین بار نشد\n" );
	exit( 2 );
}

wp_set_current_user( 1 );

const TP_DAY    = '2026-09-27';
const TP_STATUS = 'needs_verification';
const TP_IRAN   = 670;          /* کشور ایران — پیش‌نویسِ خودِ سایت زنده */
const TP_ISF    = 99;           /* استان اصفهان */

$R = array();                   /* گزارش هر گام: عنوان ← سطرها */

/* ── منابع ─────────────────────────────────────────────────────────── */

$S = array(
	'rial'      => array( 'Wikipedia — Iranian rial (کد ISO، نماد، تومان)', 'https://en.wikipedia.org/wiki/Iranian_rial' ),
	'cbi'       => array( 'Central Bank of the Islamic Republic of Iran — ناشر اسکناس و مسکوک', 'https://www.cbi.ir/' ),
	'shcal'     => array( 'Wikipedia — Solar Hijri calendar (تقویم رسمی، آغاز سال در اعتدال بهاری)', 'https://en.wikipedia.org/wiki/Solar_Hijri_calendar' ),
	'holidays'  => array( 'Wikipedia — Public holidays in Iran (تعطیلات خورشیدی و قمری)', 'https://en.wikipedia.org/wiki/Public_holidays_in_Iran' ),
	'utcal'     => array( 'مرکز تقویم مؤسسهٔ ژئوفیزیک دانشگاه تهران — مرجع رسمی تقویم', 'https://calendar.ut.ac.ir/' ),
	'emergency' => array( 'Wikipedia — List of emergency telephone numbers (ایران: پلیس ۱۱۰، اورژانس ۱۱۵، آتش‌نشانی ۱۲۵)', 'https://en.wikipedia.org/wiki/List_of_emergency_telephone_numbers' ),
	'fcdo'      => array( 'GOV.UK — Iran travel advice: Local laws and customs (پوشش در اماکن عمومی)', 'https://www.gov.uk/foreign-travel-advice/iran/local-laws-and-customs' ),
	'raja'      => array( 'Wikipedia — Islamic Republic of Iran Railways (رجا)', 'https://en.wikipedia.org/wiki/Islamic_Republic_of_Iran_Railways' ),
	'isfmetro'  => array( 'Wikipedia — Isfahan Metro', 'https://en.wikipedia.org/wiki/Isfahan_Metro' ),
);

/* ── کمکی‌ها ──────────────────────────────────────────────────────── */

function tp_line( $title, $text ) {
	global $R;
	$R[ $title ][] = $text;
}

function tp_sources( $keys ) {
	global $S;
	$rows = array();
	foreach ( array_values( $keys ) as $i => $k ) {
		$rows[ 'item-' . $i ] = array(
			'source_name'        => $S[ $k ][0],
			'source_url'         => $S[ $k ][1],
			'source_access_date' => TP_DAY,
			'source_row_status'  => 'active',
		);
	}
	return $rows;
}

function tp_lang_fa( $post_id, $type ) {
	do_action( 'wpml_set_element_language_details', array(
		'element_id' => $post_id, 'element_type' => 'post_' . $type, 'trid' => false, 'language_code' => 'fa',
	) );
}

/** پست بساز یا اگر همین نامک هست به‌روزش کن. */
function tp_upsert( $section, $type, $slug, $title, $meta, $source_keys, $extra = array() ) {

	$found = get_posts( array( 'post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'suppress_filters' => true ) );

	$post = array_merge( array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_author' => 1 ), $extra );

	if ( $found ) {
		$id = (int) $found[0]->ID;
		wp_update_post( array_merge( $post, array( 'ID' => $id ) ) );
		tp_line( $section, sprintf( '#%-5d %-20s %s  (به‌روز)', $id, $type, $title ) );
	} else {
		$id = wp_insert_post( $post, true );
		if ( is_wp_error( $id ) ) {
			fwrite( STDERR, "ساخته نشد: {$type} {$slug} — " . $id->get_error_message() . "\n" );
			exit( 1 );
		}
		tp_lang_fa( $id, $type );
		tp_line( $section, sprintf( '#%-5d %-20s %s  (%s)', $id, $type, $title, $slug ) );
	}

	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	update_post_meta( $id, 'content_status', TP_STATUS );
	update_post_meta( $id, 'source_references', tp_sources( $source_keys ) );

	return $id;
}

/** رابطه از راه API جت‌انجین، با راستی‌آزمایی. */
function tp_rel( $section, $rel_id, $parent, $child ) {
	$rel = jet_engine()->relations->get_active_relations( $rel_id );
	if ( ! $rel ) {
		fwrite( STDERR, "رابطهٔ {$rel_id} پیدا نشد\n" );
		exit( 1 );
	}
	$had = in_array( (int) $child, array_map( 'intval', (array) $rel->get_children( $parent, 'ids' ) ), true );
	if ( ! $had ) {
		$rel->set_update_context( 'child' );
		$rel->update( $parent, $child );
	}
	if ( ! in_array( (int) $child, array_map( 'intval', (array) $rel->get_children( $parent, 'ids' ) ), true ) ) {
		fwrite( STDERR, "رابطهٔ {$rel_id} {$parent}→{$child} ثبت نشد\n" );
		exit( 1 );
	}
	tp_line( $section, sprintf( '%-4d #%d %s → #%d %s  [%s]', $rel_id, $parent, get_the_title( $parent ), $child, get_the_title( $child ), $had ? 'بود' : 'تازه' ) );
}

/** یک ردیف از جدول جت‌انجین (نوع پست، واژه‌نامه، رابطه). */
function tp_jet_row( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}jet_post_types WHERE id = %d", $id ), ARRAY_A );
}

/* ══ گام‌ها ═══════════════════════════════════════════════════════ */

/** واژه‌نامهٔ ۳۷ — «نیازمند راستی‌آزمایی».  ۱۳ */
function tp_step_glossary() {
	global $wpdb;

	$sec = 'واژه‌نامهٔ ۳۷ و وضعیت محتوا';
	$row = tp_jet_row( 37 );

	if ( ! $row || 'glossary' !== $row['status'] ) {
		fwrite( STDERR, "واژه‌نامهٔ ۳۷ نیست\n" );
		exit( 1 );
	}

	$fields = maybe_unserialize( $row['meta_fields'] );
	$has    = false;

	foreach ( (array) $fields as $f ) {
		if ( isset( $f['value'] ) && 'needs_verification' === $f['value'] ) { $has = true; }
	}

	if ( ! $has ) {
		$fields[] = array( 'value' => 'needs_verification', 'label' => 'نیازمند راستی‌آزمایی', 'is_checked' => false );
		$wpdb->update( $wpdb->prefix . 'jet_post_types', array( 'meta_fields' => maybe_serialize( $fields ) ), array( 'id' => 37 ) );
	}

	tp_line( $sec, 'گزینهٔ needs_verification «نیازمند راستی‌آزمایی» ' . ( $has ? 'بود' : 'افزوده شد' )
		. ' — WPML برای content_status: ' . tp_wpml_field_mode( 'content_status' ) );

	/* ۲۴ پستِ ساخته‌شدهٔ ۴٫۲٫۳ — شناسه‌های ۱۹۴۴ تا ۱۹۶۷ */
	$n = 0;
	for ( $id = 1944; $id <= 1967; $id ++ ) {
		if ( get_post( $id ) ) {
			update_post_meta( $id, 'content_status', TP_STATUS );
			$n ++;
		}
	}
	tp_line( $sec, $n . ' پستِ ۱۹۴۴ تا ۱۹۶۷: content_status = needs_verification' );
}

/** حالت ترجمهٔ یک فیلد در WPML به زبان آدم. */
function tp_wpml_field_mode( $key ) {
	$s    = get_option( 'icl_sitepress_settings' );
	$mode = isset( $s['translation-management']['custom_fields_translation'][ $key ] ) ? (int) $s['translation-management']['custom_fields_translation'][ $key ] : -1;
	$map  = array( -1 => 'تعریف‌نشده', 0 => 'Ignore', 1 => 'Copy', 2 => 'Translate', 3 => 'Copy once' );
	return isset( $map[ $mode ] ) ? $map[ $mode ] : (string) $mode;
}

/** هدر وودمارت — همان حالت clone سایت زنده.  ۱۳ */
function tp_step_header() {
	$sec    = 'هدر وودمارت';
	$id     = get_option( 'whb_main_header' );
	$header = get_option( 'whb_' . $id );

	if ( ! is_array( $header ) || ! isset( $header['settings']['sticky_clone'] ) ) {
		fwrite( STDERR, "هدر {$id} پیدا نشد\n" );
		exit( 1 );
	}

	/*
	 * وودمارت کلاس `whb-sticky-clone` را فقط با هر دو می‌گذارد:
	 * `sticky_clone` روشن و `sticky_effect` برابر `slide`
	 * (inc/modules/header-builder/functions.php). بکاپ `stick` و خاموش دارد.
	 */
	$was_clone  = ! empty( $header['settings']['sticky_clone']['value'] );
	$was_effect = (string) $header['settings']['sticky_effect']['value'];

	$header['settings']['sticky_clone']['value']  = true;
	$header['settings']['sticky_effect']['value'] = 'slide';
	update_option( 'whb_' . $id, $header );

	/*
	 * CSS هدر جدا در `xts-{id}-css-data` ذخیره می‌شود و با update_option
	 * بالا عوض نمی‌شود — `--wd-header-clone-h` روی ‎.00001px‎ می‌ماند و clone
	 * یک پیکسل ارتفاع می‌گیرد. همان ذخیره‌ای که سازندهٔ هدر در پیشخوان می‌کند.
	 */
	if ( function_exists( 'whb_get_builder' ) && whb_get_builder() && whb_get_builder()->factory ) {
		whb_get_builder()->factory->get_header( $id )->save();
		$css = (string) get_option( 'xts-' . $id . '-css-data' );
		tp_line( $sec, 'CSS هدر دوباره ساخته شد — ' . ( preg_match( '/--wd-header-clone-h:\s*([^;]+);/', $css, $cm ) ? '--wd-header-clone-h: ' . trim( $cm[1] ) : 'متغیر clone پیدا نشد' ) );
	} else {
		tp_line( $sec, 'سازندهٔ هدر وودمارت در دسترس نبود — CSS دوباره ساخته نشد' );
	}

	tp_line( $sec, "whb_{$id}: sticky_clone " . ( $was_clone ? 'بود' : 'false ← true' )
		. ' · sticky_effect ' . ( 'slide' === $was_effect ? 'slide بود' : $was_effect . ' ← slide' )
		. ' · sticky_height ' . $header['settings']['sticky_height']['value'] );
}

/** ایران ← استان‌ها با رابطهٔ ۷۱.  ۸ */
function tp_step_rel71() {
	$sec   = 'رابطهٔ ۷۱ کشور ← استان';
	$provs = get_posts( array( 'post_type' => 'tp_province', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true, 'orderby' => 'ID', 'order' => 'ASC' ) );
	foreach ( $provs as $p ) {
		tp_rel( $sec, 71, TP_IRAN, (int) $p );
	}
}

/**
 * «اطلاعات سفر» — یک نمونهٔ منبع‌دار برای هر دسته‌ای که نوع پست دارد.  ۸
 *
 * از هشت دستهٔ مرجع پنج تا نوع دارند. نمونه‌ها به کشور ایران وصل می‌شوند
 * نه به استان: صفحهٔ استان اصفهان باید آن‌ها را از نزدیک‌ترین نیا ارث ببرد.
 * حمل‌ونقل هیچ رابطه‌ای در JetEngine ندارد؛ ساخته می‌شود ولی وصل نمی‌شود.
 */
function tp_step_practical() {
	$sec = 'اطلاعات سفر';

	$cur = tp_upsert( $sec, 'tp_currency', 'iran-rial', 'ریال ایران', array(
		'name_fa'           => 'ریال ایران',
		'short_summary'     => 'واحد پول رسمی ایران ریال است و در گفت‌وگوی روزمره قیمت‌ها بیشتر به تومان گفته می‌شود؛ هر تومان ده ریال است.',
		'currency_iso_code' => 'irr',
		'currency_symbol'   => '﷼',
	), array( 'rial', 'cbi' ) );
	tp_rel( $sec, 226, TP_IRAN, $cur );

	$cal = tp_upsert( $sec, 'tp_calendar', 'iran-official-calendar', 'تقویم رسمی ایران', array(
		'name_fa'                => 'تقویم رسمی ایران',
		'short_summary'          => 'تقویم رسمی ایران هجری خورشیدی است و سال با نوروز در اعتدال بهاری آغاز می‌شود. بخشی از تعطیلات رسمی بر پایهٔ تقویم هجری قمری است و هر سال جابه‌جا می‌شود.',
		'official_source_link'   => 'https://calendar.ut.ac.ir/',
		'lunar_date_disclaimer'  => 'تاریخ تعطیلات قمری هر سال نسبت به تقویم خورشیدی جابه‌جا می‌شود.',
	), array( 'shcal', 'holidays', 'utcal' ) );
	tp_rel( $sec, 228, TP_IRAN, $cal );

	$hs = tp_upsert( $sec, 'tp_health_safety', 'iran-emergency-numbers', 'شماره‌های اضطراری ایران', array(
		'name_fa'             => 'شماره‌های اضطراری ایران',
		'short_summary'       => 'شمارهٔ پلیس در ایران ۱۱۰، اورژانس ۱۱۵ و آتش‌نشانی ۱۲۵ است.',
		'emergency_police'    => '110',
		'emergency_ambulance' => '115',
		'emergency_fire'      => '125',
	), array( 'emergency' ) );
	tp_rel( $sec, 156, TP_IRAN, $hs );

	$et = tp_upsert( $sec, 'tp_travel_etiquette', 'iran-dress-code', 'پوشش در اماکن عمومی ایران', array(
		'name_fa'            => 'پوشش در اماکن عمومی ایران',
		'short_summary'      => 'در اماکن عمومی ایران پوشش اسلامی الزامی است و زنان باید موی سر را بپوشانند.',
		'dress_code_summary' => '<p>در اماکن عمومی ایران پوشش اسلامی الزامی است و زنان باید موی سر را بپوشانند.</p>',
	), array( 'fcdo' ) );
	tp_rel( $sec, 159, TP_IRAN, $et );

	$tr = tp_upsert( $sec, 'tp_transport', 'iran-rail-network', 'شبکهٔ ریلی ایران', array(
		'name_fa'           => 'شبکهٔ ریلی ایران',
		'short_summary'     => 'شبکهٔ ریلی ایران را شرکت راه‌آهن جمهوری اسلامی ایران (رجا) اداره می‌کند. اصفهان از شهرهای دارای متروست.',
		'rail_network_note' => '<p>شبکهٔ ریلی ایران را شرکت راه‌آهن جمهوری اسلامی ایران (رجا) اداره می‌کند.</p>',
	), array( 'raja', 'isfmetro' ) );
	tp_line( $sec, "حمل‌ونقل #{$tr}: در خروجی JetEngine هیچ رابطه‌ای برای tp_transport نیست — وصل نشد" );
}

/** کاربر آزمونِ مشترک — مشترک، بی‌گذرواژهٔ دانسته.  پذیرش */
function tp_step_testuser() {
	$sec   = 'کاربر آزمون';
	$login = 'tp_test_430';
	$user  = get_user_by( 'login', $login );

	if ( ! $user ) {
		$id = wp_insert_user( array(
			'user_login'   => $login,
			'user_pass'    => wp_generate_password( 40, true, true ),
			'user_email'   => 'tp_test_430@topersia.local',
			'display_name' => 'کاربر آزمون',
			'role'         => 'subscriber',
		) );
		if ( is_wp_error( $id ) ) {
			fwrite( STDERR, 'کاربر ساخته نشد: ' . $id->get_error_message() . "\n" );
			exit( 1 );
		}
		$user = get_user_by( 'id', $id );
		tp_line( $sec, "#{$id} {$login} ساخته شد — نقش subscriber" );
	} else {
		tp_line( $sec, "#{$user->ID} {$login} بود — نقش " . implode( ',', $user->roles ) );
	}

	tp_line( $sec, 'ورود آزمون با کوکی ساخته‌شده از tests/local-login.php — هیچ گذرواژه‌ای جایی نوشته نمی‌شود' );
}

/**
 * مدل خبر در JetEngine — CHANGE-PACKAGE-001 بند ۷٫۳ تا ۷٫۵.  ۶
 *
 * همان ذخیره‌ای که رابط JetEngine می‌کند: یک ردیف نوع پست و چهار ردیف
 * رابطه در جدول jet_post_types، یک متاباکس در jet_engine_meta_boxes، و
 * افزودن tpc_news به متاباکس مشترک. شناسه‌ها پیش‌بینی نمی‌شوند؛ همان
 * شمارندهٔ خود جدول. رابطه‌ها بی متای یال (P5-D011 · درس WPML).
 */
function tp_step_news_model() {
	global $wpdb;

	$sec   = 'خبر — مدل داده';
	$table = $wpdb->prefix . 'jet_post_types';

	/* ── نوع پست ── */
	$cpt = (int) $wpdb->get_var( "SELECT id FROM {$table} WHERE slug = 'tpc_news' AND status = 'publish'" );

	if ( ! $cpt ) {
		$labels = array(
			'name' => 'خبر', 'singular_name' => 'خبر', 'menu_name' => 'خبرها', 'name_admin_bar' => 'خبر',
			'add_new' => 'افزودن تازه', 'add_new_item' => 'افزودن خبر تازه', 'new_item' => 'خبر تازه',
			'edit_item' => 'ویرایش خبر', 'view_item' => 'نمایش خبر', 'all_items' => 'همهٔ خبرها',
			'search_items' => 'جست‌وجوی خبر', 'not_found' => 'خبری یافت نشد', 'not_found_in_trash' => 'خبری در زباله‌دان یافت نشد',
			'archives' => 'بایگانی خبرها',
		);
		$args = array(
			'public' => true, 'publicly_queryable' => true, 'show_ui' => true, 'show_in_menu' => true,
			'show_in_nav_menus' => true, 'show_in_rest' => true, 'query_var' => true, 'rewrite' => true,
			'map_meta_cap' => false, 'has_archive' => true, 'hierarchical' => false, 'exclude_from_search' => false,
			'with_front' => false, 'show_edit_link' => false, 'custom_storage' => false, 'hide_field_names' => false,
			'delete_metadata' => false, 'rewrite_slug' => 'news', 'capability_type' => 'post', 'menu_position' => -1,
			'menu_icon' => 'dashicons-megaphone', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'admin_columns' => array(), 'admin_filters' => array(),
		);
		$wpdb->insert( $table, array( 'slug' => 'tpc_news', 'status' => 'publish', 'labels' => maybe_serialize( $labels ), 'args' => maybe_serialize( $args ), 'meta_fields' => maybe_serialize( array() ) ) );
		$cpt = (int) $wpdb->insert_id;
		tp_line( $sec, "#{$cpt} نوع پست tpc_news — نامک بازنویسی news، آرشیو روشن، Title · Editor · Thumbnail · Excerpt" );
	} else {
		tp_line( $sec, "#{$cpt} نوع پست tpc_news بود" );
	}

	/* ── متاباکس‌ها ── */
	$boxes = get_option( 'jet_engine_meta_boxes', array() );

	if ( isset( $boxes['meta-1']['args']['allowed_post_type'] ) && ! in_array( 'tpc_news', $boxes['meta-1']['args']['allowed_post_type'], true ) ) {
		$boxes['meta-1']['args']['allowed_post_type'][] = 'tpc_news';
		tp_line( $sec, 'متاباکس مشترک (meta-1) به tpc_news وصل شد — short_summary · ai_summary · source_references' );
	}

	$found = '';
	foreach ( $boxes as $key => $box ) {
		if ( isset( $box['args']['allowed_post_type'] ) && array( 'tpc_news' ) === array_values( (array) $box['args']['allowed_post_type'] ) ) {
			$found = $key;
		}
	}

	if ( '' === $found ) {
		$n = 1;
		while ( isset( $boxes[ 'meta-' . $n ] ) ) { $n ++; }
		$found = 'meta-' . $n;
		$boxes[ $found ] = array(
			'args'        => array(
				'object_type' => 'post', 'allowed_tax' => array(), 'allowed_post_type' => array( 'tpc_news' ),
				'allowed_posts' => array(), 'excluded_posts' => array(), 'active_conditions' => array(),
				'position' => 'normal', 'priority' => 'high', 'name' => 'خبر — منبع اصلی', 'hide_field_names' => true,
			),
			'meta_fields' => array(
				array( 'type' => 'text', 'options' => array(), 'repeater-fields' => array(), 'id' => 9101, 'title' => 'نام منبع اصلی',
					'name' => 'news_source_name', 'object_type' => 'field', 'width' => '50%', 'isNested' => false, 'is_required' => true, 'show_in_rest' => true ),
				array( 'type' => 'text', 'options' => array(), 'repeater-fields' => array(), 'id' => 9102, 'title' => 'پیوند منبع اصلی',
					'name' => 'news_source_url', 'object_type' => 'field', 'width' => '50%', 'isNested' => false, 'is_required' => true, 'show_in_rest' => true ),
			),
			'id'          => $found,
		);
		tp_line( $sec, "{$found} «خبر — منبع اصلی»: news_source_name (الزامی، ۵۰٪) · news_source_url (الزامی، ۵۰٪)" );
	} else {
		tp_line( $sec, "{$found} متاباکس خبر بود" );
	}

	update_option( 'jet_engine_meta_boxes', $boxes );

	/* ── چهار رابطه ── */
	$places = array(
		'tp_province' => array( 'news_g3', 'استان‌های مرتبط', 'انتخاب استان' ),
		'tp_city'     => array( 'news_g5', 'شهرهای مرتبط', 'انتخاب شهر' ),
		'tp_village'  => array( 'news_g7', 'روستاهای مرتبط', 'انتخاب روستا' ),
		'tp_island'   => array( 'news_g8', 'جزیره‌های مرتبط', 'انتخاب جزیره' ),
	);

	foreach ( $places as $type => $l ) {

		$have = 0;
		foreach ( (array) $wpdb->get_results( "SELECT id, args FROM {$table} WHERE status = 'relation'", ARRAY_A ) as $row ) {
			$a = maybe_unserialize( $row['args'] );
			if ( is_array( $a ) && 'posts::tpc_news' === ( $a['parent_object'] ?? '' ) && 'posts::' . $type === ( $a['child_object'] ?? '' ) ) {
				$have = (int) $row['id'];
			}
		}

		if ( $have ) {
			tp_line( $sec, "رابطهٔ #{$have} tpc_news → {$type} بود" );
			continue;
		}

		$labels = array(
			'name' => $l[0], 'parent_page_control_title' => $l[1], 'parent_page_control_connect' => 'افزودن ' . str_replace( 'انتخاب ', '', $l[2] ),
			'parent_page_control_select' => $l[2], 'child_page_control_title' => 'خبر', 'child_page_control_connect' => 'افزودن خبر',
			'child_page_control_select' => 'انتخاب خبر',
		);
		$args = array(
			'db_table' => false, 'parent_control' => true, 'child_control' => true, 'parent_manager' => false, 'child_manager' => false,
			'parent_allow_delete' => false, 'child_allow_delete' => false, 'is_legacy' => false, 'rest_get_enabled' => false,
			'rest_post_enabled' => false, 'parents_required' => false, 'children_required' => false, 'name' => '',
			'parent_object' => 'posts::tpc_news', 'child_object' => 'posts::' . $type, 'parent_rel' => null,
			'type' => 'many_to_many', 'legacy_id' => '', 'rest_get_access' => '', 'rest_post_access' => 'manage_options',
		);
		$wpdb->insert( $table, array( 'slug' => '', 'status' => 'relation', 'labels' => maybe_serialize( $labels ), 'args' => maybe_serialize( $args ), 'meta_fields' => maybe_serialize( array() ) ) );
		tp_line( $sec, 'رابطهٔ #' . (int) $wpdb->insert_id . " tpc_news → {$type} (چندبه‌چند، {$l[0]})" );
	}

	/* ── WPML — بند ۷٫۳ و ۷٫۶ ── */
	$s = get_option( 'icl_sitepress_settings' );
	if ( is_array( $s ) ) {
		$s['custom_posts_sync_option']['tpc_news']     = 1;   /* Translatable — only show translated */
		$s['custom_posts_unlocked_option']['tpc_news'] = 0;
		$s['translation-management']['custom_fields_translation']['news_source_name']  = 2;   /* Translate */
		$s['translation-management']['custom_fields_translation']['news_source_url']   = 1;   /* Copy */
		$s['translation-management']['custom_fields_translation']['_tp_news_ancestor'] = 0;   /* Ignore */
		update_option( 'icl_sitepress_settings', $s );
		tp_line( $sec, 'WPML: tpc_news ترجمه‌پذیر (فقط ترجمه‌شده‌ها) · news_source_name Translate · news_source_url Copy · _tp_news_ancestor Ignore' );
	}
}

/**
 * خبرهای واقعیِ اخیرِ استان اصفهان با منبع.  ۶
 *
 * هر چهار از Tehran Times، در دوازده ماه اخیر. خلاصه‌ها کوتاه و به واژهٔ
 * خودمان‌اند، نه ترجمهٔ متن خبر. تاریخ انتشار همان تاریخ منبع است.
 * مدل (گام news-model) باید در اجرای جدا پیش از این ساخته شده باشد تا
 * JetEngine نوع و رابطه‌ها را در این درخواست بشناسد.
 */
function tp_step_news() {
	global $S;

	$sec  = 'خبر — دادهٔ آزمون';
	$rels = function_exists( 'topersia_g_news_relations' ) ? topersia_g_news_relations() : array();

	if ( ! post_type_exists( 'tpc_news' ) || empty( $rels['tp_province'] ) || empty( $rels['tp_city'] ) ) {
		fwrite( STDERR, "مدل خبر هنوز بار نشده — اول گام news-model را جدا اجرا کن\n" );
		exit( 1 );
	}

	$S['n_flight']  = array( 'Tehran Times — Isfahan-Istanbul flights resume as city seeks wider air connectivity (2026-08-09)', 'https://www.tehrantimes.com/news/528972/Isfahan-Istanbul-flights-resume-as-city-seeks-wider-air-connectivity' );
	$S['n_archive'] = array( 'Tehran Times — Isfahan heritage records undergo digital preservation (2026-08-08)', 'https://www.tehrantimes.com/news/528945/Isfahan-heritage-records-undergo-digital-preservation' );
	$S['n_kids']    = array( 'Tehran Times — Isfahan to host 38th children’s film festival (2026-09-08)', 'https://www.tehrantimes.com/news/529825/Isfahan-to-host-38th-children-s-film-festival' );
	$S['n_cinetour']= array( 'Tehran Times — Isfahan to host world tourism film festival meeting (2026-06-17)', 'https://www.tehrantimes.com/news/527435/Isfahan-to-host-world-tourism-film-festival-meeting' );

	$items = array(
		array( 'isfahan-istanbul-flights-resume', 'ازسرگیری پرواز مستقیم اصفهان–استانبول', '2026-08-09 12:00:00', 'n_flight', 399,
			'پرواز مستقیم اصفهان و استانبول دوباره برقرار شد و شمار پروازها به سه پرواز در هفته رسید؛ مسئولان گردشگری استان آن را زمینهٔ گشودن مسیرهای هوایی تازه می‌دانند.' ),
		array( 'isfahan-heritage-records-digitized', 'دیجیتال‌سازی اسناد میراث فرهنگی استان اصفهان', '2026-08-08 12:00:00', 'n_archive', 99,
			'ادارهٔ کل میراث فرهنگی استان اصفهان حدود دو هزار سند مرکز اسناد میراث استان، از جمله پرونده‌های مرمت و حفاظت، را دیجیتال می‌کند تا در دسترس پژوهشگران قرار گیرد.' ),
		array( 'isfahan-children-film-festival-38', 'اصفهان میزبان سی‌وهشتمین جشنوارهٔ فیلم کودکان و نوجوانان', '2026-09-08 12:00:00', 'n_kids', 399,
			'سی‌وهشتمین جشنوارهٔ بین‌المللی فیلم کودکان و نوجوانان با همکاری بنیاد سینمایی فارابی و شهرداری اصفهان در این شهر برگزار می‌شود.' ),
		array( 'isfahan-cinetour-meeting', 'اصفهان میزبان نشست جشنوارهٔ جهانی فیلم گردشگری', '2026-06-17 12:00:00', 'n_cinetour', 399,
			'با تأیید کشورهای عضو، نشست بعدی جشنوارهٔ جهانی فیلم گردشگری (سینه‌تور) در اصفهان برگزار می‌شود.' ),
	);

	foreach ( $items as $it ) {

		list( $slug, $title, $date, $src, $place, $sum ) = $it;

		$id = tp_upsert( $sec, 'tpc_news', $slug, $title, array(
			'short_summary'    => $sum,
			'news_source_name' => 'Tehran Times',
			'news_source_url'  => $S[ $src ][1],
		), array( $src ), array(
			'post_date'     => $date,
			'post_date_gmt' => get_gmt_from_date( $date ),
			'post_content'  => '<p>' . $sum . '</p>',
			'post_excerpt'  => $sum,
		) );

		$type = get_post_type( $place );
		tp_rel( $sec, $rels[ $type ], $id, $place );

		$anc = topersia_g_news_sync( $id );
		tp_line( $sec, "  _tp_news_ancestor #{$id}: " . ( $anc ? implode( ' · ', array_map( function ( $a ) { return '#' . $a . ' ' . get_the_title( $a ); }, $anc ) ) : '—' ) );
	}
}

/**
 * JetReviews — یک review type به ازای هر نوع‌پستِ پنج خانواده.  ۷ · P5-D234
 *
 * خودِ کار در user-core است (`ToPersia_JetReviews_Setup::run`)، همان
 * دکمهٔ پیشخوان؛ این گام فقط آن را صدا می‌زند و نتیجه را گزارش می‌کند.
 */
function tp_step_reviews() {
	global $wpdb;

	$sec = 'نظرها — JetReviews';

	if ( ! class_exists( 'ToPersia_JetReviews_Setup' ) || ! function_exists( 'jet_reviews' ) ) {
		fwrite( STDERR, "user-core ۱٫۱۰٫۰ یا JetReviews بار نشد\n" );
		exit( 1 );
	}

	$n = ToPersia_JetReviews_Setup::run( true );
	tp_line( $sec, $n . ' نوع‌پست نظر گرفت؛ تأیید مدیر الزامی؛ structuredata خاموش' );

	$table = $wpdb->prefix . 'jet_review_types';
	foreach ( (array) $wpdb->get_results( "SELECT slug, name, source_type, fields FROM {$table} WHERE slug LIKE 'tp\\_%' ORDER BY id", ARRAY_A ) as $row ) {
		$labels = array();
		foreach ( (array) maybe_unserialize( $row['fields'] ) as $f ) {
			$labels[] = isset( $f['label'] ) ? $f['label'] : '';
		}
		tp_line( $sec, sprintf( '%-34s %s — %s', $row['slug'], $row['name'], implode( ' · ', $labels ) ) );
	}

	$set = get_option( 'jet-reviews-settings' );
	$off = array();
	foreach ( (array) ( $set['allowed-post-types'] ?? array() ) as $pt => $on ) {
		if ( 0 === strpos( $pt, 'tp_' ) && ! filter_var( $on, FILTER_VALIDATE_BOOLEAN ) ) {
			$off[] = $pt;
		}
	}
	tp_line( $sec, 'بی‌نظر: ' . implode( ' · ', $off ) );
}

/** api.met.no در فهرست استثنای WP_HTTP_BLOCK_EXTERNAL.  ۲ */
function tp_step_config() {
	$file = TP_WP . 'wp-config.php';
	$src  = file_get_contents( $file );

	if ( false === $src || ! preg_match( "/define\\(\\s*'WP_ACCESSIBLE_HOSTS',\\s*'([^']*)'\\s*\\);/", $src, $m ) ) {
		fwrite( STDERR, "WP_ACCESSIBLE_HOSTS در wp-config نیست\n" );
		exit( 1 );
	}

	$hosts = array_filter( array_map( 'trim', explode( ',', $m[1] ) ) );

	if ( ! in_array( 'api.met.no', $hosts, true ) ) {
		$hosts[] = 'api.met.no';
		$src     = str_replace( $m[0], "define( 'WP_ACCESSIBLE_HOSTS', '" . implode( ',', $hosts ) . "' );", $src );
		file_put_contents( $file, $src );
	}

	$GLOBALS['tp_config_line'] = 'WP_ACCESSIBLE_HOSTS = ' . implode( ',', $hosts );
}

/* ══ اجرا ════════════════════════════════════════════════════════ */

$all = in_array( 'all', $steps, true );

if ( isset( $GLOBALS['tp_config_line'] ) ) {
	tp_line( 'پیکربندی', $GLOBALS['tp_config_line'] );
}

foreach ( array( 'glossary', 'header', 'rel71', 'practical', 'testuser', 'news-model', 'news', 'reviews' ) as $step ) {
	if ( $all || in_array( $step, $steps, true ) ) {
		$fn = 'tp_step_' . str_replace( '-', '_', $step );
		if ( function_exists( $fn ) ) {
			$fn();
		}
	}
}

/* ── گزارش: بخش ۴٫۳٫۰ به LOCAL-DATA.txt ─────────────────────────── */

$old  = is_file( TP_REPORT ) ? (string) file_get_contents( TP_REPORT ) : '';
$keep = false !== strpos( $old, TP_MARK ) ? substr( $old, 0, strpos( $old, TP_MARK ) ) : rtrim( $old ) . "\n\n";

/* بخش‌های پیشین همین نسخه نگه داشته می‌شوند؛ گامِ تازه جای همان عنوان را می‌گیرد. */
$prev = array();
if ( false !== strpos( $old, TP_MARK ) ) {
	$tail = substr( $old, strpos( $old, TP_MARK ) + strlen( TP_MARK ) );
	foreach ( preg_split( '/\n(?=── )/u', $tail ) as $chunk ) {
		if ( preg_match( '/^── (.+?) ──\n(.*)$/su', trim( $chunk, "\n" ), $mm ) ) {
			$prev[ $mm[1] ] = array_filter( explode( "\n", rtrim( $mm[2] ) ), function ( $l ) { return '' !== trim( $l ); } );
		}
	}
}
foreach ( $R as $title => $lines ) {
	$prev[ $title ] = array_map( function ( $l ) { return '  ' . $l; }, $lines );
}
unset( $prev['منابع ۴٫۳٫۰'] );   /* همیشه از نو، چون گام خبر منبع می‌افزاید */

$out = $keep . TP_MARK . "\n"
	. 'فقط روی topersia_local. ساخته با workspace/scripts/tp-local-430.php · آخرین اجرا ' . gmdate( 'Y-m-d H:i' ) . " UTC\n"
	. "content_status همهٔ پست‌های ساخته‌شده: needs_verification («نیازمند راستی‌آزمایی»).\n";

foreach ( $prev as $title => $lines ) {
	$out .= "\n── {$title} ──\n" . implode( "\n", array_map( function ( $l ) { return 0 === strpos( $l, '  ' ) ? $l : '  ' . ltrim( $l ); }, $lines ) ) . "\n";
}

$srcs = "\n── منابع ۴٫۳٫۰ ──\n";
foreach ( $S as $s ) {
	$srcs .= '  ' . $s[0] . "\n    " . $s[1] . "\n";
}
$out .= $srcs;

file_put_contents( TP_REPORT, $out );

foreach ( $R as $title => $lines ) {
	echo "── {$title} ──\n  " . implode( "\n  ", $lines ) . "\n";
}
