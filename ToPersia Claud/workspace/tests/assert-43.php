<?php
/**
 * دستهٔ 43 — موج ۲، بقیه · P5-D485: tp_festival و tp_protected_area.
 * از assert.php بار می‌شود؛ PHP خالص با stub. بسته روی وردپرس واقعی: tests/w43-local.php.
 */

echo "\nدستهٔ 43 — جشنواره و منطقهٔ حفاظت‌شده  P5-D485\n" . str_repeat( '─', 78 ) . "\n";

$keep43 = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], isset( $GLOBALS['tp_rel'] ) ? $GLOBALS['tp_rel'] : null, isset( $GLOBALS['tp_filters'] ) ? $GLOBALS['tp_filters'] : null, isset( $GLOBALS['tp2g_status'] ) ? $GLOBALS['tp2g_status'] : null );
$f2_world( $f2_reg );
$GLOBALS['tp2g_status'] = 'active';

$w43_types = array( 'tp_festival', 'tp_protected_area' );
$w43_map   = array_filter( topersia_g_map(), function ( $f ) { return ! empty( $f['wave2b'] ); } );
$w43_xml   = (string) @file_get_contents( __DIR__ . '/../../project-knowledge/ToPersia-wpml-config-v1_13.xml' );
$w43_lock  = json_decode( (string) @file_get_contents( __DIR__ . '/../docs/ops/STRUCTURE-LOCK.json' ), true );
$w43_cat   = include __DIR__ . '/../scripts/i18n/topersia-render.php';
$w43_skin  = json_decode( (string) @file_get_contents( __DIR__ . '/../reference/Jetengine_Skin.json' ), true );

/* ۱ — ردیف DISPLAY این دو نوع: کلید فیلد (is_recurring از دستهٔ 40). رابطه یا فیلد از tp_freeze_row_is_rel (بازبینی دستهٔ 44) */
require_once dirname( __DIR__ ) . '/scripts/lib/tp-rel-coverage-lib.php';
$w43_csv = array();
foreach ( (array) file( __DIR__ . '/../docs/ops/STRUCTURE-FREEZE-PROPOSAL.csv' ) as $line ) {
	$c = str_getcsv( $line );
	if ( isset( $c[3] ) && 'DISPLAY' === $c[3] && in_array( $c[1], $w43_types, true ) && ! tp_freeze_row_is_rel( $c[2] ) && 'is_recurring' !== $c[2] ) { $w43_csv[ $c[2] ] = $c[1]; }
}
$w43_jet = array();
foreach ( (array) ( $w43_skin['meta_boxes'] ?? array() ) as $box ) {
	foreach ( (array) ( $box['meta_fields'] ?? array() ) as $f ) {
		if ( 'field' === ( $f['object_type'] ?? '' ) ) {
			$n = $f['name'];
			$w43_jet[ $n ] = array( 'type' => $f['type'], 'types' => array_merge( $w43_jet[ $n ]['types'] ?? array(), (array) ( $box['args']['allowed_post_type'] ?? array() ) ) );
		}
	}
}
$w43_cover = function ( $map ) use ( $w43_csv, $w43_jet ) {
	$bad = array();
	foreach ( $w43_csv as $k => $type ) {
		if ( empty( $map[ $k ] ) ) { $bad[] = 'نقشه:' . $k; }
		if ( empty( $w43_jet[ $k ] ) || ! in_array( $type, $w43_jet[ $k ]['types'], true ) ) { $bad[] = 'متاباکس:' . $k; }
	}
	return $bad;
};
$w43_mut = $w43_map; unset( $w43_mut['key_species'] );
t( 'هر کلید DISPLAY دو نوع در نقشه و متاباکس همان نوع؛ ۹ کلید — جهش: کلیدی حذف شود می‌افتد',
	array() === $w43_cover( $w43_map ) && 9 === count( $w43_map ) && 9 === count( $w43_csv ) && array( 'نقشه:key_species' ) === $w43_cover( $w43_mut ), implode( ' · ', $w43_cover( $w43_map ) ) );
t( 'is_recurring تکرار نشد: همان نگاشت دستهٔ 40، بیرون از این موج', ! empty( topersia_g_map()['is_recurring']['p5d470'] ) && empty( topersia_g_map()['is_recurring']['wave2b'] ) );

/* ۲ — WPML (کلاس هر کلید از v1_13) و قفل */
$w43_class = array( 'program_highlights' => 'translate', 'event_travel_impact' => 'copy', 'admission_note' => 'translate', 'tourist_suitability_note' => 'translate', 'managed_area' => 'copy', 'entry_permit_required' => 'copy', 'key_species' => 'translate', 'geopark' => 'copy', 'activities' => 'translate' );
$w43_wpml = function ( $xml ) use ( $w43_class ) {
	$bad = array();
	foreach ( $w43_class as $k => $c ) { if ( false === strpos( $xml, '<custom-field action="' . $c . '">' . $k . '</custom-field>' ) ) { $bad[] = $k; } }
	return $bad;
};
t( 'WPML: متن translate و سوییچ copy، هر کدام کلید به کلید — جهش: کلاسی عوض شود می‌افتد',
	'' !== $w43_xml && array() === $w43_wpml( $w43_xml ) && array( 'geopark' ) === $w43_wpml( str_replace( '"copy">geopark<', '"translate">geopark<', $w43_xml ) ), implode( ' · ', $w43_wpml( $w43_xml ) ) );
$w43_lock_ok = function ( $lock ) use ( $w43_map, $w43_jet, $w43_class ) {
	$bad = array();
	foreach ( $w43_map as $k => $f ) {
		$e = $lock['fields'][ $k ] ?? null;
		if ( ! $e || $e['fieldmap_zone'] !== $f['zone'] || $e['fieldmap_kind'] !== $f['kind'] || $w43_class[ $k ] !== $e['wpml'] || $e['jet_type'] !== $w43_jet[ $k ]['type'] ) { $bad[] = $k; }
	}
	return $bad;
};
$w43_lm = $w43_lock; unset( $w43_lm['fields']['admission_note'] );
t( 'STRUCTURE-LOCK: نام، نوع، ناحیه و کلاس هر ۹ کلید؛ شمار برابر؛ activities با هر دو نوع — جهش: ردیفی حذف شود می‌افتد',
	is_array( $w43_lock ) && array() === $w43_lock_ok( $w43_lock ) && array( 'admission_note' ) === $w43_lock_ok( $w43_lm ) && $w43_lock['count']['fields'] === count( $w43_lock['fields'] )
	&& array( 'tp_natural_feature', 'tp_protected_area' ) === $w43_lock['fields']['activities']['post_types'], implode( ' · ', $w43_lock_ok( (array) $w43_lock ) ) );

/* ۳ — کاتالوگ، با ستون‌های جدول */
$w43_labels = array( 'بخش', 'توضیح' );
foreach ( $w43_map as $f ) { $w43_labels[] = $f['label']; }
$w43_miss = function ( $cat ) use ( $w43_labels ) {
	$bad = array();
	foreach ( $w43_labels as $fa ) { if ( empty( $cat[ $fa ]['en'] ) || empty( $cat[ $fa ]['ar'] ) || preg_match( '/[پچژگکی]/u', $cat[ $fa ]['ar'] ) ) { $bad[] = $fa; } }
	return $bad;
};
$w43_cm = $w43_cat; unset( $w43_cm['گونه‌های شاخص'] );
t( 'برچسب هر فیلد و ستون در کاتالوگ انگلیسی و عربی — جهش: برچسبی حذف شود می‌افتد',
	array() === $w43_miss( $w43_cat ) && array( 'گونه‌های شاخص' ) === $w43_miss( $w43_cm ), implode( ' · ', $w43_miss( $w43_cat ) ) );

/* ۴ — رندر پر و خالی */
$w43_ids = array( 'tp_festival' => 8401, 'tp_protected_area' => 8402 );
$w43_all = array();
foreach ( $w43_ids as $type => $id ) {
	$f2_post( $id, $type, 'نمونهٔ ' . $type );
	$meta = array( 'content_status' => 'verified', 'last_verified_date' => '2026-10-20', 'source_references' => array( array( 'source_name' => 'منبع آزمون', 'source_url' => 'https://example.org/', 'source_row_status' => 'active' ) ) );
	foreach ( $w43_csv as $k => $t2 ) {
		if ( $t2 !== $type ) { continue; }
		$kind = $w43_map[ $k ]['kind'];
		$meta[ $k ] = 'flag' === $kind ? 'true' : ( 'table' === $kind ? array( array( 'ph_title' => 'مقدار ph_title', 'ph_description' => 'مقدار ph_description' ) ) : 'مقدار ' . $k );
	}
	$GLOBALS['tp_meta'][ $id ] = $meta;
	$w43_all[ $type ] = r( $id, 'tp_overview' ) . r( $id, 'tp_badges' ) . topersia_g_visit_block( $id );
	$f2_post( $id + 10, $type, 'خالی ' . $type );
	$GLOBALS['tp_meta'][ $id + 10 ] = array();
}
$w43_full = function ( $pages ) use ( $w43_csv, $w43_map ) {
	foreach ( $w43_csv as $k => $type ) {
		if ( false === strpos( $pages[ $type ], $w43_map[ $k ]['label'] ) ) { return $k; }
		if ( 'text' === $w43_map[ $k ]['kind'] && false === strpos( $pages[ $type ], 'مقدار ' . $k ) ) { return $k; }
	}
	return true;
};
$w43_pm = $w43_all; $w43_pm['tp_festival'] = str_replace( 'شرایط ورود و بلیت', 'x', $w43_pm['tp_festival'] );
t( 'رندر عمومی: هر ۹ فیلد با برچسب و مقدار در صفحهٔ نوع خودش، بی کد ویژه — جهش: برچسبی نیاید می‌افتد',
	true === $w43_full( $w43_all ) && 'admission_note' === $w43_full( $w43_pm ), (string) $w43_full( $w43_all ) );
t( 'جدول بخش‌های برنامه: هر دو ستون و مقدار', false !== strpos( $w43_all['tp_festival'], 'مقدار ph_title' ) && false !== strpos( $w43_all['tp_festival'], 'مقدار ph_description' ) );
$w43_stamp = function ( $pages ) { foreach ( $pages as $h ) { if ( 1 !== substr_count( $h, '<p class="tp-verified">' ) || false === strpos( $h, 'منبع آزمون' ) ) { return false; } } return true; };
$w43_sm = $w43_all; $w43_sm['tp_festival'] = str_replace( 'tp-verified', 'x', $w43_sm['tp_festival'] );
tp441_mut( 'مهر بازبینی و منبع کنار بلوک هر دو نوع — جهش: مهر یک نوع حذف شود می‌افتد', $w43_stamp, $w43_all, $w43_sm );
$w43_empty = '';
foreach ( $w43_ids as $id ) { $w43_empty .= r( $id + 10, 'tp_overview' ) . r( $id + 10, 'tp_badges' ) . topersia_g_visit_block( $id + 10 ); }
$w43_empty_ok = function ( $html ) use ( $w43_map ) {
	foreach ( $w43_map as $f ) { if ( false !== strpos( $html, $f['label'] ) ) { return false; } }
	return false === strpos( $html, 'tp-verified' ) && false === strpos( $html, 'نامعلوم' );
};
tp441_mut( 'خالی‌امن: نمونهٔ خالی هر نوع هیچ برچسب، مهر و «نامعلوم» نمی‌سازد — جهش: پست پر می‌افتد', $w43_empty_ok, $w43_empty, implode( '', $w43_all ) );
t( 'سوییچ خاموش هیچ نشان و برچسبی نمی‌سازد', ( function () use ( $f2_post ) {
	$f2_post( 8420, 'tp_protected_area', 'خاموش' );
	$GLOBALS['tp_meta'][8420] = array( 'managed_area' => 'false', 'geopark' => 'false', 'entry_permit_required' => 'false' );
	$h = r( 8420, 'tp_badges' ) . topersia_g_visit_block( 8420 );
	return false === strpos( $h, 'مدیریت‌شده' ) && false === strpos( $h, 'ژئوپارک' ) && false === strpos( $h, 'مجوز ورود' );
} )() );

/* ۵ — بی کد به‌ازای فیلد؛ geopark مصرف‌کنندهٔ پرس‌وجوی برنامه را دارد و کلیدش در رندر نام نیست */
$w43_code = function ( $extra ) use ( $w43_map ) {
	$hits = array();
	foreach ( glob( TP_PLUGIN_DIR . 'includes/*.php' ) as $f ) {
		if ( 'topersia-g-map.php' === basename( $f ) ) { continue; }
		$src = (string) file_get_contents( $f ) . $extra;
		foreach ( array_keys( $w43_map ) as $k ) { if ( false !== strpos( $src, "'" . $k . "'" ) ) { $hits[] = basename( $f ) . ':' . $k; } }
	}
	return $hits;
};
t( 'بی کد به‌ازای فیلد: هیچ کلیدی در رندر نامی ندارد — جهش: شرطی با نام فیلد می‌افتد',
	array() === $w43_code( '' ) && array() !== $w43_code( "if ( 'key_species' === \$name ) {}" ), implode( ' · ', $w43_code( '' ) ) );

/* ۶ — فهرست نوع در رندر و هسته */
t( 'مهر و اجبار بسته برای این دو نوع هم؛ آستانهٔ تازگی نه', in_array( 'tp_festival', topersia_g_verified_types(), true ) && in_array( 'tp_protected_area', topersia_g_verified_types(), true ) && ! isset( topersia_render_stale_types()['tp_festival'] ) && ! isset( topersia_render_stale_types()['tp_protected_area'] ) );

$GLOBALS['tp_posts'] = $keep43[0]; $GLOBALS['tp_meta'] = $keep43[1];
if ( null === $keep43[2] ) { unset( $GLOBALS['tp_rel'] ); } else { $GLOBALS['tp_rel'] = $keep43[2]; }
if ( null === $keep43[3] ) { unset( $GLOBALS['tp_filters'] ); } else { $GLOBALS['tp_filters'] = $keep43[3]; }
if ( null === $keep43[4] ) { unset( $GLOBALS['tp2g_status'] ); } else { $GLOBALS['tp2g_status'] = $keep43[4]; }
