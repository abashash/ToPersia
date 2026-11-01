<?php
/**
 * دستهٔ 41 — tp_currency و tp_visa · P5-D491 · P5-D492 · P5-D493.
 * از assert.php بار می‌شود؛ PHP خالص با stub. بسته روی وردپرس واقعی: tests/w41-local.php.
 *
 * فقط نگاشت FIELDMAP به رندر عمومی: متن غنی و جدول از پیکربندی `cols`؛ هیچ کد به‌ازای فیلد.
 */

echo "\nدستهٔ 41 — ارز و روادید  P5-D491 · P5-D492 · P5-D493\n" . str_repeat( '─', 78 ) . "\n";

$keep41 = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], isset( $GLOBALS['tp_rel'] ) ? $GLOBALS['tp_rel'] : null, isset( $GLOBALS['tp_filters'] ) ? $GLOBALS['tp_filters'] : null, isset( $GLOBALS['tp2g_status'] ) ? $GLOBALS['tp2g_status'] : null );
$f2_world( $f2_reg );

$w41_map  = array_filter( topersia_g_map(), function ( $f ) { return ! empty( $f['wave1'] ); } );
$w41_xml  = (string) @file_get_contents( __DIR__ . '/../../project-knowledge/ToPersia-wpml-config-v1_13.xml' );
$w41_lock = json_decode( (string) @file_get_contents( __DIR__ . '/../docs/ops/STRUCTURE-LOCK.json' ), true );
$w41_cat  = include __DIR__ . '/../scripts/i18n/topersia-render.php';
$w41_skin = json_decode( (string) @file_get_contents( __DIR__ . '/../reference/Jetengine_Skin.json' ), true );
$w41_core = (string) @file_get_contents( __DIR__ . '/../plugin/topersia-core/includes/topersia-pack.php' );
$w41_gen  = (string) @file_get_contents( __DIR__ . '/../scripts/tp-template-gen.php' );

/* ۱ — ۳۴ کلید از پیشنهاد DISPLAY (۳۵ منهای نسبت که در دستهٔ 40 رفت)، همه در متاباکس همان نوع */
$w41_csv = array();
foreach ( (array) file( __DIR__ . '/../docs/ops/STRUCTURE-FREEZE-PROPOSAL.csv' ) as $line ) {
	$c = str_getcsv( $line );
	if ( isset( $c[3] ) && 'DISPLAY' === $c[3] && in_array( $c[1], array( 'tp_currency', 'tp_visa' ), true ) ) { $w41_csv[ $c[2] ] = $c[1]; }
}
$w41_jet = array();
foreach ( (array) ( $w41_skin['meta_boxes'] ?? array() ) as $box ) {
	foreach ( (array) ( $box['meta_fields'] ?? array() ) as $f ) { if ( 'field' === ( $f['object_type'] ?? '' ) ) { $w41_jet[ $f['name'] ] = array( 'type' => $f['type'], 'types' => (array) ( $box['args']['allowed_post_type'] ?? array() ), 'subs' => array_column( (array) ( $f['repeater-fields'] ?? array() ), 'name' ) ); } }
}
$w41_cover = function ( $map ) use ( $w41_csv, $w41_jet ) {
	$bad = array();
	foreach ( $w41_csv as $k => $type ) {
		if ( 'redenomination_ratio' === $k ) { continue; }   /* دستهٔ 40 */
		if ( empty( $map[ $k ] ) ) { $bad[] = 'نقشه:' . $k; }
		if ( empty( $w41_jet[ $k ] ) || ! in_array( $type, $w41_jet[ $k ]['types'], true ) ) { $bad[] = 'متاباکس:' . $k; }
	}
	return $bad;
};
$w41_mut = $w41_map; unset( $w41_mut['fee_policy_note'] );
t( 'هر کلید DISPLAY ارز و روادید (پیشنهاد ساختار، جز نسبت دستهٔ 40) در نقشه و در متاباکس همان نوع است؛ هیچ فیلد تازه‌ای ساخته نشد — جهش: کلیدی حذف شود می‌افتد',
	array() === $w41_cover( $w41_map ) && 34 === count( $w41_map ) && array( 'نقشه:fee_policy_note' ) === $w41_cover( $w41_mut ), implode( ' · ', $w41_cover( $w41_map ) ) );

/* ۲ — کلاس WPML: کد/عدد/تاریخ/بولی copy، متن translate؛ قفل ساختار */
$w41_want = function ( $f ) { return in_array( $f['kind'], array( 'date', 'flag', 'iso', 'latin', 'number' ), true ) ? 'copy' : 'translate'; };
$w41_wpml = function ( $xml ) use ( $w41_map, $w41_want ) {
	$bad = array();
	foreach ( $w41_map as $k => $f ) { if ( false === strpos( $xml, '<custom-field action="' . $w41_want( $f ) . '">' . $k . '</custom-field>' ) ) { $bad[] = $k; } }
	return $bad;
};
t( 'WPML (TA-I18N): کد، عدد، تاریخ و بولی copy؛ متن و جدول translate، هر ۳۴ فیلد — جهش: کلاسی عوض شود می‌افتد',
	'' !== $w41_xml && array() === $w41_wpml( $w41_xml ) && array( 'fee_currency' ) === $w41_wpml( str_replace( '"copy">fee_currency<', '"translate">fee_currency<', $w41_xml ) ), implode( ' · ', $w41_wpml( $w41_xml ) ) );
$w41_lock_ok = function ( $lock ) use ( $w41_map, $w41_want, $w41_jet ) {
	$bad = array();
	foreach ( $w41_map as $k => $f ) {
		$e = $lock['fields'][ $k ] ?? null;
		if ( ! $e || $e['fieldmap_zone'] !== $f['zone'] || $e['fieldmap_kind'] !== $f['kind'] || $e['wpml'] !== $w41_want( $f ) || $e['jet_type'] !== $w41_jet[ $k ]['type'] ) { $bad[] = $k; }
	}
	return $bad;
};
$w41_lock_mut = $w41_lock; unset( $w41_lock_mut['fields']['denominations'] );
t( 'STRUCTURE-LOCK: نام، نوع، ناحیه و کلاس WPML هر ۳۴ کلید؛ شمار فیلدها با فهرست برابر — جهش: ردیفی حذف شود می‌افتد',
	is_array( $w41_lock ) && array() === $w41_lock_ok( $w41_lock ) && array( 'denominations' ) === $w41_lock_ok( $w41_lock_mut ) && $w41_lock['count']['fields'] === count( $w41_lock['fields'] ), implode( ' · ', $w41_lock_ok( (array) $w41_lock ) ) );

/* ستون‌های جدول باید زیرفیلد واقعی باشند */
$w41_cols = function ( $map ) use ( $w41_jet ) {
	$bad = array();
	foreach ( $map as $k => $f ) {
		if ( 'table' !== $f['kind'] ) { continue; }
		foreach ( array_keys( $f['cols'] ) as $c ) { if ( ! in_array( $c, $w41_jet[ $k ]['subs'], true ) ) { $bad[] = $k . '.' . $c; } }
	}
	return $bad;
};
$w41_mut = $w41_map; $w41_mut['denominations']['cols']['dn_fake'] = array( 'label' => 'x', 'kind' => 'text' );
t( 'جدول: هر ستون `cols` زیرفیلد واقعی همان repeater در جت‌انجین است — جهش: ستون ساختگی می‌افتد',
	array() === $w41_cols( $w41_map ) && array( 'denominations.dn_fake' ) === $w41_cols( $w41_mut ), implode( ' · ', $w41_cols( $w41_map ) ) );

/* ۳ — کاتالوگ */
$w41_labels = array( 'آخرین بازبینی', 'منبع' );
foreach ( $w41_map as $f ) { $w41_labels[] = $f['label']; foreach ( (array) ( $f['cols'] ?? array() ) as $c ) { $w41_labels[] = $c['label']; } }
$w41_miss = function ( $cat ) use ( $w41_labels ) {
	return array_values( array_filter( array_unique( $w41_labels ), function ( $fa ) use ( $cat ) { return empty( $cat[ $fa ]['en'] ) || empty( $cat[ $fa ]['ar'] ) || preg_match( '/[پچژگکی]/u', $cat[ $fa ]['ar'] ); } ) );
};
$w41_cat_mut = $w41_cat; unset( $w41_cat_mut['اسکناس و مسکوک'] );
t( 'برچسب هر فیلد و ستون و مهر بازبینی در کاتالوگ انگلیسی و عربی (عربی بی حرف ویژهٔ فارسی) — جهش: برچسبی حذف شود می‌افتد',
	array() === $w41_miss( $w41_cat ) && array( 'اسکناس و مسکوک' ) === $w41_miss( $w41_cat_mut ), implode( ' · ', $w41_miss( $w41_cat ) ) );

/* ۴ — رندر پر */
$GLOBALS['tp2g_status'] = 'active';
$f2_post( 8101, 'tp_currency', 'ارز آزمون' );
$f2_post( 8102, 'tp_visa', 'روادید آزمون' );
$f2_post( 8103, 'tp_currency', 'ارز خالی' );
$f2_post( 8104, 'tp_province', 'استان بی‌مهر' );
$w41_full_cur = array(
	'unit_confusion_explainer' => '<p>هر تومان <strong>ده ریال</strong> است.</p>',
	'price_reading_rule'       => 'قیمت‌ها را به تومان بخوانید.',
	'redenomination_end_date'  => '2030-03-20',
	'dual_pricing_active'      => '1',
	'tourist_card_available'   => '1',
	'denominations'            => array(
		array( 'dn_face_value' => '100000', 'dn_printed_unit' => 'not_applicable', 'dn_instrument_type' => 'not_applicable', 'dn_toman_equivalent' => '10000', 'dn_in_circulation' => '1', 'dn_note' => '', 'dn_row_status' => 'active' ),
		array( 'dn_face_value' => '999', 'dn_printed_unit' => '', 'dn_instrument_type' => '', 'dn_toman_equivalent' => '', 'dn_in_circulation' => '', 'dn_note' => 'ردیف کنارگذاشته', 'dn_row_status' => 'not_applicable' ),
	),
	'regional_notes'           => array( array( 'rn_g3_id' => '7', 'rn_g3_name' => 'کیش', 'rn_note' => 'کارت پذیرفته است', 'rn_row_status' => 'active' ) ),
	'content_status'           => 'needs_verification',
	'last_verified_date'       => '2026-10-20',
	'source_references'        => array( array( 'source_name' => 'بانک مرکزی', 'source_url' => 'https://cbi.ir/', 'source_row_status' => 'active' ) ),
);
$GLOBALS['tp_meta'][8101] = $w41_full_cur;
$GLOBALS['tp_meta'][8102] = array( 'free_zone_excluded_iso' => 'ZZ', 'voa_entry_points' => array( array( 'ep_code' => 'IKA', 'ep_type' => 'unknown', 'ep_name' => 'فرودگاه امام', 'ep_row_status' => 'active' ) ), 'insurance_required' => '1', 'fee_currency' => 'EUR', 'entry_points_reference' => 'گذرهای زمینی', 'content_status' => 'verified', 'last_verified_date' => '2026-10-21' );
$GLOBALS['tp_meta'][8103] = array();
$GLOBALS['tp_meta'][8104] = array( 'last_verified_date' => '2026-10-20', 'content_status' => 'verified', 'overview_text' => '<p>متن</p>' );
$w41_o1 = r( 8101, 'tp_overview' );
$w41_p1 = r( 8101, 'tp_relgrid', array( 'rel' => 'practical' ) );
$w41_o2 = r( 8102, 'tp_overview' );
$w41_p2 = r( 8102, 'tp_relgrid', array( 'rel' => 'practical' ) );
$w41_full_ok = function ( $x ) {
	list( $o1, $p1, $o2, $p2 ) = $x;
	return false !== strpos( $o1, '<h3>ریال و تومان</h3>' ) && false !== strpos( $o1, 'ده ریال' )
		&& false !== strpos( $o1, 'قیمت‌ها را به تومان بخوانید.' ) && false !== strpos( $o1, 'پایان دورۀ گذار' )
		&& false !== strpos( $p1, 'قیمت دوگانه' ) && false !== strpos( $p1, 'کارت گردشگری' )
		&& false !== strpos( $o1, '<div class="tp-data-table" tabindex="0"' ) && false !== strpos( $o1, '<caption class="tp-vh">اسکناس و مسکوک</caption>' ) && false !== strpos( $o1, '<th scope="col">ارزش اسمی</th>' )
		&& false !== strpos( $o1, '۱۰۰٬۰۰۰' ) && false !== strpos( $o1, 'کیش' )
		&& false !== strpos( $o2, 'گذرهای زمینی' ) && false !== strpos( $o2, 'فرودگاه امام' ) && false !== strpos( $o2, 'dir="ltr"' )
		&& false !== strpos( $o2, 'کشورهای مستثنا از منطقۀ آزاد' ) && false !== strpos( $p2, 'بیمۀ اجباری' ) && false !== strpos( $p2, 'EUR' );
};
t( 'رندر عمومی: متن غنی زیر h3، کاشی پرچم، جدول با caption و th و کشش افقی، کد لاتین با dir — همه بی کد ویژه', $w41_full_ok( array( $w41_o1, $w41_p1, $w41_o2, $w41_p2 ) ) );
t( 'جدول: ردیف غیرفعال نمی‌آید؛ «موضوعیت ندارد» و ستون تماماً خالی نه — همان رندر',
	false === strpos( $w41_o1, 'ردیف کنارگذاشته' ) && false === strpos( $w41_o1, 'واحد چاپ‌شده' ) && false === strpos( $w41_o1, 'not_applicable' ) && false === strpos( $w41_o2, 'نوع مبدأ' ) && false === strpos( $w41_o1, '۹۹۹' ) );
$w41_txt = topersia_g_value( 'denominations', $w41_full_cur['denominations'], 8101 );
t( 'مصرف‌کنندهٔ متنی (خلاصه‌ساز): جدول به متن خالص بی «Array»؛ متن غنی بی تگ',
	false === strpos( $w41_txt, 'Array' ) && false !== strpos( $w41_txt, '۱۰۰٬۰۰۰' ) && false === strpos( $w41_txt, '<' ) && false === strpos( topersia_g_value( 'unit_confusion_explainer', $w41_full_cur['unit_confusion_explainer'], 8101 ), '&lt;' ) );
t( 'کد ISO: هر کد نام به زبان صفحه یا اگر نامی نبود همان کد (کد ناشناس هرگز «ناحیهٔ نامشخص» نمی‌شود)',
	'ZZ' === topersia_g_value( 'free_zone_excluded_iso', 'ZZ', 8102 ) && '' !== topersia_g_value( 'free_zone_excluded_iso', "AF, IQ", 8102 ) && 2 === count( explode( topersia_g_list_sep(), topersia_g_value( 'free_zone_excluded_iso', "AF, IQ", 8102 ) ) ) );

/* ۵ — خالی‌امن */
$w41_e_o = r( 8103, 'tp_overview' );
$w41_e_p = r( 8103, 'tp_relgrid', array( 'rel' => 'practical' ) );
$GLOBALS['tp_meta'][8103] = array_fill_keys( array_keys( $w41_map ), '' ) + array( 'denominations' => array(), 'dual_pricing_active' => '0' );
$w41_e2 = r( 8103, 'tp_overview' ) . r( 8103, 'tp_relgrid', array( 'rel' => 'practical' ) );
$w41_empty_ok = function ( $html ) use ( $w41_map ) {
	foreach ( $w41_map as $f ) { if ( false !== strpos( $html, $f['label'] ) ) { return false; } }
	return false === strpos( $html, 'tp-data-table' ) && false === strpos( $html, 'tp-verified' ) && false === strpos( $html, 'نامعلوم' );
};
tp441_mut( 'خالی‌امن: پست بی مقدار، با مقدار تهی یا پرچم خاموش هیچ برچسب، جدول، مهر و «—» از این ۳۴ فیلد نمی‌سازد — جهش: پست پر می‌افتد',
	$w41_empty_ok, $w41_e_o . $w41_e_p . $w41_e2, $w41_o1 . $w41_p1 );

/* ۶ — مهر بازبینی و منبع */
$w41_stamp_ok = function ( $x ) {
	list( $o1, $p1, $o2 ) = $x;
	return 1 === substr_count( $o1, '<p class="tp-verified">' ) && 1 === substr_count( $p1, '<p class="tp-verified">' )
		&& false !== strpos( $o1, 'آخرین بازبینی' ) && false !== strpos( $o1, '<time datetime="2026-10-20">' ) && false !== strpos( $o1, 'منبع' ) && false !== strpos( $o1, 'href="https://cbi.ir/"' )
		&& false !== strpos( $o2, '<time datetime="2026-10-21">' ) && false === strpos( $o2, 'منبع:' );
};
tp441_mut( 'P5-D492: تاریخ بازبینی و منبع کنار هر دو بلوک ارز و روادید؛ بی منبع فقط تاریخ — جهش: مهر حذف شود می‌افتد',
	$w41_stamp_ok, array( $w41_o1, $w41_p1, $w41_o2 ), array( str_replace( 'tp-verified', 'x', $w41_o1 ), $w41_p1, $w41_o2 ) );
$GLOBALS['tp_meta'][8101]['content_status'] = '';
t( 'P5-D492 · P5-D308: ترجمهٔ بی وضعیت همین زبان تاریخ اصل را نشان نمی‌دهد؛ منبع می‌ماند',
	false === strpos( r( 8101, 'tp_overview' ), '<time datetime="2026-10-20">' ) && false !== strpos( r( 8101, 'tp_overview' ), 'href="https://cbi.ir/"' ) );
t( 'P5-D492: نوع دیگر (استان) با همان تاریخ مهر نمی‌گیرد', false === strpos( r( 8104, 'tp_overview' ), 'tp-verified' ) );
$w41_vol = function ( $src ) { return false === strpos( $src, 'volatility_flag' ); };
tp441_mut( 'volatility_flag در طرح نیست، پس هشدار «ممکن است قدیمی باشد» هم ساخته نشد (P5-D492؛ پیشنهاد ۳۰ روز در گزارش) — جهش: کدی که نامش را بیاورد می‌افتد',
	$w41_vol, file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' ), "'volatility_flag'" );

/* ۷ — بی کد به‌ازای فیلد */
$w41_code = function ( $extra ) use ( $w41_map ) {
	$hits = array();
	foreach ( glob( TP_PLUGIN_DIR . 'includes/*.php' ) as $f ) {
		if ( 'topersia-g-map.php' === basename( $f ) ) { continue; }
		$src = (string) file_get_contents( $f ) . $extra;
		foreach ( $w41_map as $k => $d ) {
			foreach ( array_merge( array( $k ), array_keys( (array) ( $d['cols'] ?? array() ) ) ) as $n ) { if ( false !== strpos( $src, "'" . $n . "'" ) ) { $hits[] = basename( $f ) . ':' . $n; } }
		}
	}
	return $hits;
};
t( 'بی کد به‌ازای فیلد: هیچ کلید یا ستونی در رندر نامی ندارد؛ فقط FIELDMAP — جهش: شرطی با نام فیلد می‌افتد',
	array() === $w41_code( '' ) && array() !== $w41_code( "if ( 'atm_usage_note' === \$name ) {}" ), implode( ' · ', $w41_code( '' ) ) );

/* ۸ — P5-D464: ارز کشور فقط نام */
$w41_d464 = function ( $s ) { return false !== strpos( $s, "topersia_g_trip_tile( topersia_g_trip_icon_for( \$ptyp ), \$tid ? topersia_g_kind_label( \$tid ) : '', esc_html( \$item['name'] ), (string) \$item['url'] )" ); };
$w41_zones = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );
tp441_mut( 'P5-D464 دست نخورد: کاشی ارز در صفحهٔ کشور فقط نام و پیوند هدف است، نه فیلدهای ارز — جهش: مقدار فیلد به کاشی بیاید می‌افتد',
	$w41_d464, $w41_zones, str_replace( "esc_html( \$item['name'] ), (string) \$item['url'] )", "esc_html( topersia_g_text( \$tid, 'currency_iso_code' ) ), (string) \$item['url'] )", $w41_zones ) );

/* ۹ — بسته: کد؛ رفتار در w41-local */
$w41_pack_ok = function ( $s ) {
	return false !== strpos( $s, "return array( 'tp_currency', 'tp_visa' );" )
		&& false !== strpos( $s, "foreach ( array( 'last_verified_date', 'source_references' ) as \$req ) {" )
		&& false !== strpos( $s, "'last_verified_date' === (string) \$field['name'] && in_array( \$post_type, topersia_pack_verified_types(), true )" )
		&& false !== strpos( $s, 'topersia_pack_similarity_notes( $tp_texts )' )
		&& false !== strpos( $s, 'دو tp_currency با currency_iso_code یکسان' );
};
tp441_mut( 'بسته: تاریخ بازبینی و منبع برای پست تازهٔ ارز و روادید اجباری؛ هشدار شباهت و ISO دوگانه غیرمسدودکننده — جهش: هشدار حذف شود می‌افتد',
	$w41_pack_ok, $w41_core, str_replace( 'دو tp_currency با currency_iso_code یکسان', 'x', $w41_core ) );
t( 'یک فهرست نوع در رندر و هسته (مهر و اجبار بسته هم‌خوان)', topersia_g_verified_types() === array( 'tp_currency', 'tp_visa' ) && false !== strpos( $w41_core, "array( 'tp_currency', 'tp_visa' )" ) );

/* ۱۰ — شیوه‌نامه: فقط توکن */
$w41_css = function ( $s ) {
	if ( ! preg_match_all( '/^html body \.tp-page \.tp-(?:data-table|verified)[^\n]*$/m', $s, $m ) || count( $m[0] ) < 6 ) { return false; }
	foreach ( $m[0] as $line ) { if ( preg_match( '/#[0-9a-fA-F]{3,8}\b|rgba?\(|letter-spacing/', $line ) ) { return false; } }
	return true;
};
tp441_mut( 'شیوه‌نامهٔ جدول و مهر فقط از توکن؛ بی رنگ خام — جهش: رنگ خام می‌افتد',
	$w41_css, $w41_gen, str_replace( 'html body .tp-page .tp-verified{', 'html body .tp-page .tp-verified{color:#123456;', $w41_gen ) );

/* جهان آزمون‌های پس از این فایل برمی‌گردد */
$GLOBALS['tp_posts'] = $keep41[0]; $GLOBALS['tp_meta'] = $keep41[1];
if ( null === $keep41[2] ) { unset( $GLOBALS['tp_rel'] ); } else { $GLOBALS['tp_rel'] = $keep41[2]; }
if ( null === $keep41[3] ) { unset( $GLOBALS['tp_filters'] ); } else { $GLOBALS['tp_filters'] = $keep41[3]; }
if ( null === $keep41[4] ) { unset( $GLOBALS['tp2g_status'] ); } else { $GLOBALS['tp2g_status'] = $keep41[4]; }
