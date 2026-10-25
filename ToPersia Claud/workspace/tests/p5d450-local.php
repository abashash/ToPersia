<?php
/**
 * P5-D450 · P5-D451 (دستهٔ ۲۸) روی جت‌انجین زندهٔ محلی — فقط می‌خواند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/p5d450-local.php
 *
 * هر فیلد scripts/lib/tp-p5d450.php در متاباکسی که همهٔ نوع‌هایش را دارد، با همان نوع و واژه‌نامه (با نام)؛
 * هر ★ گزینهٔ «unknown» دارد؛ WPML کپی/ترجمه؛ رابطه‌های تازه با نام، والد، فرزند و نوع درست؛ ادغام‌ها: حذفی‌ها
 * نیستند، ماندنی‌ها هستند و هیچ ردیف jet_rel_default به رابطهٔ حذفی اشاره نمی‌کند (بی تلفات)؛ هر شناسهٔ رابطهٔ
 * نقشهٔ render (`topersia_g_relmap`) در جت‌انجین هست. هر ادعا جهش دارد.
 */
require_once dirname( __DIR__ ) . '/scripts/lib/tp-p5d450.php';
global $wpdb;
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) {
	$ok ? $pass++ : $fail++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
$T = $wpdb->prefix . 'jet_post_types';

$gl = array();
foreach ( $wpdb->get_results( "SELECT id, labels, meta_fields FROM {$T} WHERE status = 'glossary'", ARRAY_A ) as $r ) {
	$l = maybe_unserialize( $r['labels'] );
	$gl[ (string) ( $l['name'] ?? '' ) ] = array( 'id' => (string) $r['id'], 'values' => array_column( (array) maybe_unserialize( $r['meta_fields'] ), 'value' ) );
}

$fields_bad = function ( $boxes ) use ( $gl ) {
	$bad = array();
	foreach ( tp_p5d450_fields() as $name => $d ) {
		$kind = array( 'url' => 'text' )[ $d[0] ] ?? $d[0];
		foreach ( $d[5] as $type ) {
			$hit = null;
			foreach ( $boxes as $b ) {
				if ( ! in_array( $type, (array) ( $b['args']['allowed_post_type'] ?? array() ), true ) ) { continue; }
				foreach ( (array) $b['meta_fields'] as $f ) { if ( ( $f['name'] ?? '' ) === $name ) { $hit = $f; } }
			}
			if ( ! $hit || $hit['type'] !== $kind ) { $bad[] = $type . '.' . $name; continue; }
			if ( isset( $d[7] ) && is_string( $d[7] ) ) {
				$g = $gl[ $d[7] ] ?? null;
				if ( ! $g || (string) ( $hit['glossary_id'] ?? '' ) !== $g['id'] || array_keys( tp_p5d450_glossaries()[ $d[7] ][3] ) !== $g['values'] ) { $bad[] = 'glossary:' . $name; }
				if ( $d[6] && ! in_array( 'unknown', $g['values'] ?? array(), true ) ) { $bad[] = 'unknown:' . $name; }
			}
		}
	}
	return array_values( array_unique( $bad ) );
};
$boxes = get_option( 'jet_engine_meta_boxes', array() );
$mut   = $boxes;
foreach ( $mut as $k => $b ) { $mut[ $k ]['meta_fields'] = array_values( array_filter( (array) $b['meta_fields'], function ( $f ) { return 'tap_water_safe' !== ( $f['name'] ?? '' ); } ) ); }
$fb = $fields_bad( $boxes );
$t( 'فیلدها: هر ۴۲ فیلد در متاباکس هر نوعش، با همان نوع و واژه‌نامه (نام و گزینه‌ها)؛ هر ★ گزینهٔ «unknown» دارد — جهش: فیلد برداشته می‌افتد', array() === $fb && array() !== $fields_bad( $mut ), implode( ' · ', $fb ) );

$wp  = get_option( 'icl_sitepress_settings' );
$cft = (array) ( $wp['translation-management']['custom_fields_translation'] ?? array() );
$xml = (string) get_option( 'wpml-tm-custom-xml', '' );
$wbad = array();
foreach ( tp_p5d450_fields() as $name => $d ) {
	$mode = 'textarea' === $d[0] || in_array( $name, array( 'ferry_ports', 'viewpoints', 'gesture_warnings' ), true ) ? 2 : 1;
	if ( (int) ( $cft[ $name ] ?? -1 ) !== $mode || false === strpos( $xml, '>' . $name . '</custom-field>' ) ) { $wbad[] = $name; }
}
$t( 'WPML: هر فیلد در تنظیم ترجمهٔ فیلدها (۱ کپی · ۲ ترجمه) و در wpml-tm-custom-xml', array() === $wbad, implode( ' · ', $wbad ) );

$rels = array();
foreach ( $wpdb->get_results( "SELECT id, labels, args FROM {$T} WHERE status = 'relation'", ARRAY_A ) as $r ) {
	$rels[ (int) $r['id'] ] = array( 'l' => maybe_unserialize( $r['labels'] ), 'a' => maybe_unserialize( $r['args'] ) );
}
$rbad = array();
foreach ( tp_p5d450_relations() as $key => $d ) {
	$hit = array_filter( $rels, function ( $r ) use ( $key ) { return ( $r['l']['name'] ?? '' ) === $key; } );
	$r   = reset( $hit );
	if ( 1 !== count( $hit ) || 'posts::' . $d[0] !== $r['a']['parent_object'] || 'posts::' . $d[1] !== $r['a']['child_object'] || $d[2] !== $r['a']['type'] ) { $rbad[] = $key; }
}
$t( 'روابط تازه: ۱۲ رابطه، هر کدام یک بار، با نام، والد، فرزند و نوع درست (قوم ↔ استان = main_provinces_reference)', array() === $rbad, implode( ' · ', $rbad ) );

$mbad = array();
foreach ( tp_p5d451_merges() as $drop => $keep ) {
	if ( isset( $rels[ $drop ] ) ) { $mbad[] = 'باقی:' . $drop; }
	foreach ( $keep as $k ) { if ( ! isset( $rels[ $k ] ) ) { $mbad[] = 'نیست:' . $k; } }
	if ( (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default WHERE rel_id = %d", $drop ) ) ) { $mbad[] = 'ردیف یتیم:' . $drop; }
}
$t( 'ادغام P5-D451: ۸ رابطهٔ حذفی نیستند، ماندنی‌ها هستند، هیچ ردیف داده به حذفی اشاره نمی‌کند (بی تلفات)', array() === $mbad, implode( ' · ', $mbad ) );

$rm_bad = array();
foreach ( topersia_g_relmap() as $type => $groups ) {
	foreach ( $groups as $g ) { foreach ( (array) ( $g['rels'] ?? array() ) as $spec ) { if ( ! isset( $rels[ (int) $spec['id'] ] ) ) { $rm_bad[] = $type . ':' . $spec['id']; } } }
}
$t( 'نقشهٔ رابطهٔ render: هر شناسه در جت‌انجین هست (رابطهٔ ادغام‌شده از نقشه هم رفت) — جهش: شناسهٔ حذفی ۱۵۱ پیدا می‌شود', array() === $rm_bad && ! isset( $rels[151] ), implode( ' · ', $rm_bad ) );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
