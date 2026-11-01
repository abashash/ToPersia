<?php
/**
 * دستهٔ 42 — موج ۲ · P5-D498: tp_health_safety، tp_transport، tp_calendar.
 * از assert.php بار می‌شود؛ PHP خالص با stub. بسته روی وردپرس واقعی: tests/w42-local.php.
 *
 * مثل دستهٔ 41 فقط نگاشت FIELDMAP به رندر عمومی؛ هیچ کد به‌ازای فیلد.
 */

echo "\nدستهٔ 42 — سلامت، حمل‌ونقل، تقویم  P5-D498\n" . str_repeat( '─', 78 ) . "\n";

$keep42 = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], isset( $GLOBALS['tp_rel'] ) ? $GLOBALS['tp_rel'] : null, isset( $GLOBALS['tp_filters'] ) ? $GLOBALS['tp_filters'] : null, isset( $GLOBALS['tp2g_status'] ) ? $GLOBALS['tp2g_status'] : null );
$f2_world( $f2_reg );
$GLOBALS['tp2g_status'] = 'active';

$w42_types = array( 'tp_health_safety', 'tp_transport', 'tp_calendar' );
$w42_map   = array_filter( topersia_g_map(), function ( $f ) { return ! empty( $f['wave2'] ); } );
$w42_xml   = (string) @file_get_contents( __DIR__ . '/../../project-knowledge/ToPersia-wpml-config-v1_13.xml' );
$w42_lock  = json_decode( (string) @file_get_contents( __DIR__ . '/../docs/ops/STRUCTURE-LOCK.json' ), true );
$w42_cat   = include __DIR__ . '/../scripts/i18n/topersia-render.php';
$w42_skin  = json_decode( (string) @file_get_contents( __DIR__ . '/../reference/Jetengine_Skin.json' ), true );
$w42_core  = (string) @file_get_contents( __DIR__ . '/../plugin/topersia-core/includes/topersia-pack.php' );

/* ۱ — ۱۸ ردیف DISPLAY: ۱۶ کلید فیلد + دو رابطه که ساخته نشد */
$w42_csv = array(); $w42_rels = array();
foreach ( (array) file( __DIR__ . '/../docs/ops/STRUCTURE-FREEZE-PROPOSAL.csv' ) as $line ) {
	$c = str_getcsv( $line );
	if ( isset( $c[3] ) && 'DISPLAY' === $c[3] && in_array( $c[1], $w42_types, true ) ) {
		if ( 0 === strpos( $c[2], 'rel' ) ) { $w42_rels[] = $c[2]; } else { $w42_csv[ $c[2] ] = $c[1]; }
	}
}
$w42_jet = array();
foreach ( (array) ( $w42_skin['meta_boxes'] ?? array() ) as $box ) {
	foreach ( (array) ( $box['meta_fields'] ?? array() ) as $f ) { if ( 'field' === ( $f['object_type'] ?? '' ) ) { $w42_jet[ $f['name'] ] = array( 'type' => $f['type'], 'types' => (array) ( $box['args']['allowed_post_type'] ?? array() ) ); } }
}
$w42_cover = function ( $map ) use ( $w42_csv, $w42_jet ) {
	$bad = array();
	foreach ( $w42_csv as $k => $type ) {
		if ( empty( $map[ $k ] ) ) { $bad[] = 'نقشه:' . $k; }
		if ( empty( $w42_jet[ $k ] ) || ! in_array( $type, $w42_jet[ $k ]['types'], true ) ) { $bad[] = 'متاباکس:' . $k; }
	}
	return $bad;
};
$w42_mut = $w42_map; unset( $w42_mut['air_travel_note'] );
t( 'هر کلید DISPLAY سه نوع در نقشه و متاباکس همان نوع؛ ۱۶ فیلد و دو رابطهٔ ساخته‌نشده؛ مجموع زیر سقف ۴۵ — جهش: کلیدی حذف شود می‌افتد',
	array() === $w42_cover( $w42_map ) && 16 === count( $w42_map ) && 16 === count( $w42_csv ) && 2 === count( $w42_rels ) && array( 'نقشه:air_travel_note' ) === $w42_cover( $w42_mut ), implode( ' · ', $w42_cover( $w42_map ) ) );

/* ۲ — WPML و قفل */
$w42_wpml = function ( $xml ) use ( $w42_map ) {
	$bad = array();
	foreach ( array_keys( $w42_map ) as $k ) { if ( false === strpos( $xml, '<custom-field action="translate">' . $k . '</custom-field>' ) ) { $bad[] = $k; } }
	return $bad;
};
t( 'WPML (TA-I18N): هر ۱۶ فیلد متنی translate — جهش: کلاسی عوض شود می‌افتد',
	'' !== $w42_xml && array() === $w42_wpml( $w42_xml ) && array( 'water_safety_note' ) === $w42_wpml( str_replace( '"translate">water_safety_note<', '"copy">water_safety_note<', $w42_xml ) ), implode( ' · ', $w42_wpml( $w42_xml ) ) );
$w42_lock_ok = function ( $lock ) use ( $w42_map, $w42_jet ) {
	$bad = array();
	foreach ( $w42_map as $k => $f ) {
		$e = $lock['fields'][ $k ] ?? null;
		if ( ! $e || $e['fieldmap_zone'] !== $f['zone'] || $e['fieldmap_kind'] !== $f['kind'] || 'translate' !== $e['wpml'] || $e['jet_type'] !== $w42_jet[ $k ]['type'] ) { $bad[] = $k; }
	}
	return $bad;
};
$w42_lm = $w42_lock; unset( $w42_lm['fields']['mode_choice_guide'] );
t( 'STRUCTURE-LOCK: نام، نوع، ناحیه و کلاس هر ۱۶ کلید؛ شمار برابر — جهش: ردیفی حذف شود می‌افتد',
	is_array( $w42_lock ) && array() === $w42_lock_ok( $w42_lock ) && array( 'mode_choice_guide' ) === $w42_lock_ok( $w42_lm ) && $w42_lock['count']['fields'] === count( $w42_lock['fields'] ), implode( ' · ', $w42_lock_ok( (array) $w42_lock ) ) );

/* ۳ — کاتالوگ */
$w42_miss = function ( $cat ) use ( $w42_map ) {
	$bad = array();
	foreach ( $w42_map as $f ) { $fa = $f['label']; if ( empty( $cat[ $fa ]['en'] ) || empty( $cat[ $fa ]['ar'] ) || preg_match( '/[پچژگکی]/u', $cat[ $fa ]['ar'] ) ) { $bad[] = $fa; } }
	return $bad;
};
$w42_cm = $w42_cat; unset( $w42_cm['پرواز داخلی'] );
t( 'برچسب هر فیلد در کاتالوگ انگلیسی و عربی (عربی بی حرف ویژهٔ فارسی) — جهش: برچسبی حذف شود می‌افتد',
	array() === $w42_miss( $w42_cat ) && array( 'پرواز داخلی' ) === $w42_miss( $w42_cm ), implode( ' · ', $w42_miss( $w42_cat ) ) );

/* ۴ — رندر پر و خالی برای هر نوع */
$w42_ids = array( 'tp_health_safety' => 8301, 'tp_transport' => 8302, 'tp_calendar' => 8303 );
$w42_all = array();
foreach ( $w42_ids as $type => $id ) {
	$f2_post( $id, $type, 'نمونهٔ ' . $type );
	$meta = array( 'content_status' => 'verified', 'last_verified_date' => '2026-10-20', 'source_references' => array( array( 'source_name' => 'منبع آزمون', 'source_url' => 'https://example.org/', 'source_row_status' => 'active' ) ) );
	foreach ( $w42_csv as $k => $t2 ) { if ( $t2 === $type ) { $meta[ $k ] = 'rich' === $w42_map[ $k ]['kind'] ? '<p>مقدار <strong>' . $k . '</strong></p>' : 'مقدار ' . $k; } }
	$GLOBALS['tp_meta'][ $id ] = $meta;
	$w42_all[ $type ] = r( $id, 'tp_overview' );
	$f2_post( $id + 10, $type, 'خالی ' . $type );
	$GLOBALS['tp_meta'][ $id + 10 ] = array();
}
$w42_full = function ( $pages ) use ( $w42_csv, $w42_map ) {
	foreach ( $w42_csv as $k => $type ) {
		if ( false === strpos( $pages[ $type ], $w42_map[ $k ]['label'] ) || false === strpos( $pages[ $type ], $k ) ) { return $k; }
	}
	return true;
};
$w42_pm = $w42_all; $w42_pm['tp_transport'] = str_replace( 'پرواز داخلی', 'x', $w42_pm['tp_transport'] );
t( 'رندر عمومی: هر ۱۶ فیلد با برچسب و مقدار در صفحهٔ نوع خودش، بی کد ویژه — جهش: برچسبی نیاید می‌افتد',
	true === $w42_full( $w42_all ) && 'air_travel_note' === $w42_full( $w42_pm ), (string) $w42_full( $w42_all ) );
$w42_rich = function ( $h ) { return false !== strpos( $h, '<h3>مخاطرات طبیعی</h3>' ) && false !== strpos( $h, '<strong>natural_hazard_summary</strong>' ) && false === strpos( $h, '&lt;strong' ); };
tp441_mut( 'متن غنی زیر h3 با برچسب؛ HTML گریز نمی‌خورد — جهش: گریز بخورد می‌افتد', $w42_rich, $w42_all['tp_health_safety'], htmlspecialchars( $w42_all['tp_health_safety'] ) );
$w42_stamp = function ( $pages ) { foreach ( $pages as $h ) { if ( 1 !== substr_count( $h, '<p class="tp-verified">' ) || false === strpos( $h, 'منبع آزمون' ) ) { return false; } } return true; };
$w42_sm = $w42_all; $w42_sm['tp_calendar'] = str_replace( 'tp-verified', 'x', $w42_sm['tp_calendar'] );
tp441_mut( 'مهر بازبینی و منبع کنار بلوک هر سه نوع — جهش: مهر یک نوع حذف شود می‌افتد', $w42_stamp, $w42_all, $w42_sm );
$w42_empty = '';
foreach ( $w42_ids as $id ) { $w42_empty .= r( $id + 10, 'tp_overview' ) . r( $id + 10, 'tp_relgrid', array( 'rel' => 'practical' ) ); }
$w42_empty_ok = function ( $html ) use ( $w42_map ) {
	foreach ( $w42_map as $f ) { if ( false !== strpos( $html, $f['label'] ) ) { return false; } }
	return false === strpos( $html, 'tp-verified' ) && false === strpos( $html, 'نامعلوم' );
};
tp441_mut( 'خالی‌امن: نمونهٔ خالی هر نوع هیچ برچسب، مهر و «نامعلوم» نمی‌سازد — جهش: پست پر می‌افتد', $w42_empty_ok, $w42_empty, implode( '', $w42_all ) );

/* ۵ — آستانه P5-D502: این سه نوع پیش‌فرض دارند؛ با تاریخ کهنه هشدار، و مرزها در assert-502 */
$GLOBALS['tp_meta'][8301]['last_verified_date'] = '2001-01-01';
t( 'P5-D502: تاریخ کهنه روی این نوع هشدار می‌دهد', false !== strpos( r( 8301, 'tp_overview' ), 'ممکن است قدیمی باشد' ) );

/* ۶ — سقف ۶ آکاردئون: این دسته آکاردئون نساخت؛ شمار روی خروجی واقعی */
$w42_acc = function ( $h ) { return ( substr_count( $h, '<details' ) + substr_count( $h, 'tp-accordion' ) ) <= 6; };
$w42_acc_n = 0;
foreach ( $w42_all as $h ) { $w42_acc_n = max( $w42_acc_n, substr_count( $h, 'tp-accordion' ) ); }
tp441_mut( 'سقف ۶ آکاردئون: هیچ فیلد این دسته آکاردئون نیست (شمار صفر) — جهش: هفت آکاردئون می‌افتد', $w42_acc, $w42_all['tp_transport'], str_repeat( '<details></details>', 7 ) );
t( 'شمار tp-accordion در خروجی این سه نوع صفر است', 0 === $w42_acc_n );

/* ۷ — بی کد به‌ازای فیلد */
$w42_code = function ( $extra ) use ( $w42_map ) {
	$hits = array();
	foreach ( glob( TP_PLUGIN_DIR . 'includes/*.php' ) as $f ) {
		if ( 'topersia-g-map.php' === basename( $f ) ) { continue; }
		$src = (string) file_get_contents( $f ) . $extra;
		foreach ( array_keys( $w42_map ) as $k ) { if ( false !== strpos( $src, "'" . $k . "'" ) ) { $hits[] = basename( $f ) . ':' . $k; } }
	}
	return $hits;
};
t( 'بی کد به‌ازای فیلد: هیچ کلیدی در رندر نامی ندارد — جهش: شرطی با نام فیلد می‌افتد',
	array() === $w42_code( '' ) && array() !== $w42_code( "if ( 'air_travel_note' === \$name ) {}" ), implode( ' · ', $w42_code( '' ) ) );

/* ۸ — بسته: فهرست گسترش یافته و هم‌خوان */
$w42_five = "array( 'tp_currency', 'tp_visa', 'tp_health_safety', 'tp_transport', 'tp_calendar', 'tp_festival', 'tp_protected_area' )";
$w42_pack = function ( $s ) use ( $w42_five ) { return false !== strpos( $s, 'return ' . $w42_five . ';' ); };
tp441_mut( 'بسته: تاریخ بازبینی و منبع برای این سه نوع هم اجباری؛ یک فهرست با رندر — جهش: نوعی حذف شود می‌افتد', $w42_pack, $w42_core, str_replace( "'tp_transport', ", '', $w42_core ) );
t( 'فهرست رندر با هسته یکی است', topersia_g_verified_types() === array( 'tp_currency', 'tp_visa', 'tp_health_safety', 'tp_transport', 'tp_calendar', 'tp_festival', 'tp_protected_area' ) );

$GLOBALS['tp_posts'] = $keep42[0]; $GLOBALS['tp_meta'] = $keep42[1];
if ( null === $keep42[2] ) { unset( $GLOBALS['tp_rel'] ); } else { $GLOBALS['tp_rel'] = $keep42[2]; }
if ( null === $keep42[3] ) { unset( $GLOBALS['tp_filters'] ); } else { $GLOBALS['tp_filters'] = $keep42[3]; }
if ( null === $keep42[4] ) { unset( $GLOBALS['tp2g_status'] ); } else { $GLOBALS['tp2g_status'] = $keep42[4]; }
