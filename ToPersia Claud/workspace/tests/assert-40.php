<?php
/**
 * دستهٔ 40 — موج ۱ (کوچک‌شده): سه فیلد «باگ» و برق کشور · P5-D470 · P5-D484 · P5-D485.
 * از assert.php بار می‌شود؛ PHP خالص با stub. رفتار روی صفحهٔ واقعی: گزارش دیداری دسته.
 *
 * رندر داده‌محور است: کلید فیلد به FIELDMAP می‌آید و ناحیه (card · travel · visit) نمایش را می‌دهد؛ هیچ کدی به‌ازای فیلد نیست.
 */

echo "\nدستهٔ 40 — موج ۱: سه فیلد باگ و برق کشور  P5-D470 · P5-D484\n" . str_repeat( '─', 78 ) . "\n";

require_once __DIR__ . '/../scripts/lib/tp-p5d470.php';

$keep40 = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], isset( $GLOBALS['tp_rel'] ) ? $GLOBALS['tp_rel'] : null, isset( $GLOBALS['tp_filters'] ) ? $GLOBALS['tp_filters'] : null, isset( $GLOBALS['tp2g_status'] ) ? $GLOBALS['tp2g_status'] : null );
$f2_world( $f2_reg );

$w40_map = topersia_g_map();
$w40_spec = array(   /* کلید ← ناحیه · نوع · واحد */
	'is_recurring'         => array( 'card', 'flag', '' ),
	'redenomination_ratio' => array( 'travel', 'number', '' ),
	'venue_capacity'       => array( 'visit', 'number', 'نفر' ),
	'electric_voltage'     => array( 'travel', 'number', 'ولت' ),
	'electric_frequency'   => array( 'travel', 'number', 'هرتز' ),
);
$w40_xml  = (string) @file_get_contents( __DIR__ . '/../../project-knowledge/ToPersia-wpml-config-v1_13.xml' );
$w40_lock = json_decode( (string) @file_get_contents( __DIR__ . '/../docs/ops/STRUCTURE-LOCK.json' ), true );
$w40_cat  = include __DIR__ . '/../scripts/i18n/topersia-render.php';

/* ۱ — نقشه: ناحیه، نوع، واحد، نشان */
$w40_map_ok = function ( $map ) use ( $w40_spec ) {
	$bad = array();
	foreach ( $w40_spec as $k => $s ) {
		$m = $map[ $k ] ?? null;
		if ( ! $m || $m['zone'] !== $s[0] || $m['kind'] !== $s[1] || $m['unit'] !== $s[2] || empty( $m['p5d470'] ) ) { $bad[] = $k; }
	}
	return $bad;
};
$w40_mut = $w40_map; unset( $w40_mut['venue_capacity'] ); $w40_mut['electric_voltage']['kind'] = 'text';
t( 'موج ۱: پنج کلید در FIELDMAP با ناحیه و نوع و واحد درست — جهش: کلید حذف یا نوع عوض شود می‌افتد',
	array() === $w40_map_ok( $w40_map ) && array( 'venue_capacity', 'electric_voltage' ) === $w40_map_ok( $w40_mut ), implode( ' · ', $w40_map_ok( $w40_map ) ) );

/* ۲ — نوع عددی ↔ کلاس WPML کپی؛ برق فقط tp_country */
$w40_wpml = function ( $xml ) use ( $w40_spec ) {
	$bad = array();
	foreach ( array_keys( $w40_spec ) as $k ) { if ( false === strpos( $xml, '<custom-field action="copy">' . $k . '</custom-field>' ) ) { $bad[] = $k; } }
	return $bad;
};
t( 'موج ۱: هر پنج فیلد (عدد یا سوییچ) در پیکربندی WPML «کپی» است — جهش: یکی translate شود می‌افتد',
	'' !== $w40_xml && array() === $w40_wpml( $w40_xml ) && array() !== $w40_wpml( str_replace( '"copy">electric_frequency<', '"translate">electric_frequency<', $w40_xml ) ) );

$w40_el = tp_p5d470_fields();
$w40_el_ok = function ( $f ) use ( $w40_map ) {
	return array( 'electric_voltage', 'electric_frequency' ) === array_keys( $f ) && count( $f ) === count( array_filter( $f, function ( $d ) { return 'number' === $d[0] && array( 'tp_country' ) === $d[5]; } ) )
		&& 'travel' === $w40_map['electric_voltage']['zone'] && 'travel' === $w40_map['electric_frequency']['zone'];
};
$w40_el_mut = $w40_el; $w40_el_mut['electric_voltage'][5] = array( 'tp_country', 'tp_city' );
t( 'برق (P5-D470): فقط tp_country، عددی، ناحیهٔ travel — جهش: نوع پست دوم شود می‌افتد', $w40_el_ok( $w40_el ) && ! $w40_el_ok( $w40_el_mut ) );

/* ۳ — قفل ساختار و کاتالوگ */
$w40_lock_ok = function ( $lock ) use ( $w40_spec ) {
	$bad = array();
	foreach ( $w40_spec as $k => $s ) {
		$e = $lock['fields'][ $k ] ?? null;
		if ( ! $e || ( $e['fieldmap_zone'] ?? '' ) !== $s[0] || ( $e['fieldmap_kind'] ?? '' ) !== $s[1] || ( $e['wpml'] ?? '' ) !== 'copy' || empty( $e['jet_type'] ) ) { $bad[] = $k; }
	}
	return $bad;
};
$w40_lock_mut = $w40_lock; unset( $w40_lock_mut['fields']['venue_capacity'] );
t( 'STRUCTURE-LOCK: نام، نوع و کلاس WPML هر پنج کلید قفل است — جهش: ردیفی حذف شود می‌افتد',
	is_array( $w40_lock ) && array() === $w40_lock_ok( $w40_lock ) && array( 'venue_capacity' ) === $w40_lock_ok( $w40_lock_mut ) && $w40_lock['count']['fields'] === count( $w40_lock['fields'] ), implode( ' · ', is_array( $w40_lock ) ? $w40_lock_ok( $w40_lock ) : array( 'lock' ) ) );

$w40_labels = array();
foreach ( $w40_spec as $k => $s ) { $w40_labels[] = $w40_map[ $k ]['label']; if ( '' !== $s[2] ) { $w40_labels[] = $s[2]; } }
$w40_cat_miss = function ( $cat ) use ( $w40_labels ) {
	return array_values( array_filter( array_unique( $w40_labels ), function ( $fa ) use ( $cat ) { return empty( $cat[ $fa ]['en'] ) || empty( $cat[ $fa ]['ar'] ) || preg_match( '/[پچژگکی]/u', (string) $cat[ $fa ]['ar'] ); } ) );
};
$w40_cat_mut = $w40_cat; unset( $w40_cat_mut['ولتاژ برق'] );
t( 'برچسب و واحد هر پنج فیلد در کاتالوگ با ترجمهٔ انگلیسی و عربی — جهش: برچسبی حذف شود می‌افتد',
	array() === $w40_cat_miss( $w40_cat ) && array( 'ولتاژ برق' ) === $w40_cat_miss( $w40_cat_mut ), implode( ' · ', $w40_cat_miss( $w40_cat ) ) );

/* ۴ — رندر داده‌محور و خالی‌امن: کشور ۸۰۰۱ (برق) و بنا/جشن (ظرفیت · تکرار) در جهان stub */
$GLOBALS['tp2g_status'] = 'active';
$f2_post( 8010, 'tp_monument', 'بنای آزمون' );
$f2_post( 8011, 'tp_festival', 'جشن آزمون' );
$w40_set = function ( $pid, $meta ) { $GLOBALS['tp_meta'][ $pid ] = array_merge( isset( $GLOBALS['tp_meta'][ $pid ] ) ? $GLOBALS['tp_meta'][ $pid ] : array(), $meta ); };
$w40_set( 8001, array( 'electric_voltage' => '230', 'electric_frequency' => '50' ) );
$w40_set( 8010, array( 'venue_capacity' => '1200' ) );
$w40_set( 8011, array( 'is_recurring' => '1' ) );
$w40_full = array( r( 8001, 'tp_relgrid', array( 'rel' => 'practical' ) ), r( 8010, 'tp_relgrid', array( 'rel' => 'practical' ) ), r( 8011, 'tp_overview' ) . r( 8011, 'tp_facts' ) . r( 8011, 'tp_relgrid', array( 'rel' => 'practical' ) ) );
$w40_set( 8001, array( 'electric_voltage' => '', 'electric_frequency' => '0' ) );
$w40_set( 8010, array( 'venue_capacity' => '' ) );
$w40_set( 8011, array( 'is_recurring' => '0' ) );
$w40_empty = array( r( 8001, 'tp_relgrid', array( 'rel' => 'practical' ) ), r( 8010, 'tp_relgrid', array( 'rel' => 'practical' ) ), r( 8011, 'tp_overview' ) . r( 8011, 'tp_facts' ) . r( 8011, 'tp_relgrid', array( 'rel' => 'practical' ) ) );
$f2_post( 8012, 'tp_currency', 'ارز آزمون' );
$w40_set( 8012, array( 'redenomination_ratio' => '10000' ) );
$w40_cur_full = r( 8012, 'tp_relgrid', array( 'rel' => 'practical' ) );
$w40_set( 8012, array( 'redenomination_ratio' => '' ) );
$w40_cur_empty = r( 8012, 'tp_relgrid', array( 'rel' => 'practical' ) );
$w40_render_ok = function ( $x ) {
	list( $full, $cur ) = $x;
	$all = preg_replace( '/[\s\x{00A0}\x{202F}]+/u', ' ', wp_strip_all_tags( implode( ' ', $full ) ) );   /* متن دیدنی، بی برچسب */
	return false !== strpos( $all, '۲۳۰ ولت' ) && false !== strpos( $all, 'ولتاژ برق' ) && false !== strpos( $all, '۵۰ هرتز' ) && false !== strpos( $all, 'بسامد برق' )
		&& false !== strpos( $all, '۱٬۲۰۰ نفر' ) && false !== strpos( $all, 'ظرفیت مکان' ) && false !== strpos( $all, 'تکرارشونده' )
		&& false !== strpos( $cur, 'نسبت حذف صفر' ) && false !== strpos( $cur, '۱۰٬۰۰۰' );
};
$w40_hidden_ok = function ( $x ) {
	list( $empty, $cur ) = $x;
	$all = preg_replace( '/[\s\x{00A0}\x{202F}]+/u', ' ', wp_strip_all_tags( implode( ' ', $empty ) . $cur ) );
	foreach ( array( 'ولتاژ برق', 'بسامد برق', 'ظرفیت مکان', 'تکرارشونده', 'نسبت حذف صفر' ) as $lab ) { if ( false !== strpos( $all, $lab ) ) { return false; } }
	return false === strpos( $all, '۰ هرتز' ) && false === strpos( $all, 'نامعلوم' );
};
tp441_mut( 'رندر داده‌محور (P5-D485): برق کشور، ظرفیت مکان، تکرارشونده و نسبت حذف صفر در کاشی ناحیه با واحد — جهش: خروجی خالی می‌افتد',
	$w40_render_ok, array( $w40_full, $w40_cur_full ), array( $w40_empty, $w40_cur_empty ) );
tp441_mut( 'خالی‌امن: مقدار خالی، صفر و سوییچ خاموش هیچ کاشی و هیچ «—» برای این پنج فیلد نمی‌سازد — جهش: خروجی پر می‌افتد',
	$w40_hidden_ok, array( $w40_empty, $w40_cur_empty ), array( $w40_full, $w40_cur_full ) );

/* بی کد به‌ازای فیلد: نام این پنج کلید جز در FIELDMAP در هیچ پروندهٔ render نیست — جهش: نام در رندر بیاید می‌افتد */
$w40_code = function ( $extra ) use ( $w40_spec ) {
	$hits = array();
	foreach ( glob( TP_PLUGIN_DIR . 'includes/*.php' ) as $f ) {
		if ( 'topersia-g-map.php' === basename( $f ) ) { continue; }
		$src = (string) file_get_contents( $f ) . $extra;
		foreach ( array_keys( $w40_spec ) as $k ) { if ( false !== strpos( $src, "'" . $k . "'" ) ) { $hits[] = basename( $f ) . ':' . $k; } }
	}
	return $hits;
};
t( 'بی کد به‌ازای فیلد: هیچ‌یک از پنج کلید در رندر نامی ندارد؛ فقط کلید FIELDMAP — جهش: شرطی با نام فیلد می‌افتد',
	array() === $w40_code( '' ) && array() !== $w40_code( "if ( 'venue_capacity' === \$name ) {}" ), implode( ' · ', $w40_code( '' ) ) );

/* جهان آزمون‌های پس از این فایل برمی‌گردد */
$GLOBALS['tp_posts'] = $keep40[0]; $GLOBALS['tp_meta'] = $keep40[1];
if ( null === $keep40[2] ) { unset( $GLOBALS['tp_rel'] ); } else { $GLOBALS['tp_rel'] = $keep40[2]; }
if ( null === $keep40[3] ) { unset( $GLOBALS['tp_filters'] ); } else { $GLOBALS['tp_filters'] = $keep40[3]; }
if ( null === $keep40[4] ) { unset( $GLOBALS['tp2g_status'] ); } else { $GLOBALS['tp2g_status'] = $keep40[4]; }
