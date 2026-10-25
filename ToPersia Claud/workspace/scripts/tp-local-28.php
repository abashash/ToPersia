<?php
/**
 * دستهٔ ۲۸ — P5-D450 (فیلدهای تأییدشده) و P5-D451 (روابط) روی نسخهٔ محلی، به همان شکلی که رابط جت‌انجین ذخیره می‌کند
 * (همان روش tp-local-430.php): واژه‌نامه و رابطه ردیف‌های jet_post_types، فیلدها در jet_engine_meta_boxes.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-local-28.php [apply] [fields|relations|all]
 *
 * بی apply فقط گزارش. بخش: fields (واژه‌نامه، فیلد، WPML) · relations (روابط تازه و ادغام) · all. تکرارپذیر: هر چه هست دوباره ساخته نمی‌شود. تعریف‌ها: scripts/lib/tp-p5d450.php.
 * پیش از apply از گزینهٔ jet_engine_meta_boxes و ردیف‌های jet_post_types پشتیبان بیرون از ریشهٔ وب
 * (C:/wamp64/tp-backups/) نوشته می‌شود؛ برگشت: rollback <پشتیبان>.
 * ادغام روابط فقط وقتی رابطهٔ حذفی صفر ردیف دارد؛ وگرنه توقف.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }
require_once __DIR__ . '/lib/tp-p5d450.php';

global $wpdb;
$T     = $wpdb->prefix . 'jet_post_types';
$cmd   = isset( $args[0] ) ? (string) $args[0] : 'report';
$apply = 'apply' === $cmd;
$bdir  = dirname( untrailingslashit( ABSPATH ), 3 ) . '/tp-backups/';
$log   = function ( $s ) { echo '  ' . $s . "\n"; };
$part  = isset( $args[1] ) ? (string) $args[1] : 'all';   /* fields · relations · all */
$do_f  = in_array( $part, array( 'fields', 'all' ), true );
$do_r  = in_array( $part, array( 'relations', 'all' ), true );

if ( 'rollback' === $cmd ) {
	$b = json_decode( (string) @file_get_contents( (string) ( $args[1] ?? '' ) ), true );
	if ( ! is_array( $b ) || ! isset( $b['meta_boxes'], $b['rows'] ) ) { echo "پشتیبان نامعتبر\n"; return; }
	update_option( 'jet_engine_meta_boxes', $b['meta_boxes'] );
	$keep = array_map( 'intval', array_column( $b['rows'], 'id' ) );
	foreach ( $wpdb->get_col( "SELECT id FROM {$T} WHERE status IN ('glossary','relation')" ) as $id ) {
		if ( ! in_array( (int) $id, $keep, true ) ) { $wpdb->delete( $T, array( 'id' => (int) $id ) ); }
	}
	foreach ( $b['rows'] as $r ) { $wpdb->replace( $T, $r ); }
	if ( isset( $b['wpml'] ) ) { update_option( 'icl_sitepress_settings', $b['wpml'] ); }
	echo "برگشت از پشتیبان\n";
	return;
}

if ( $apply ) {
	wp_mkdir_p( $bdir );
	$bak = $bdir . 'p5d450-' . gmdate( 'Ymd-His' ) . '.json';
	$ok  = file_put_contents( $bak, wp_json_encode( array(
		'at'         => gmdate( 'c' ),
		'meta_boxes' => get_option( 'jet_engine_meta_boxes', array() ),
		'rows'       => $wpdb->get_results( "SELECT * FROM {$T} WHERE status IN ('glossary','relation')", ARRAY_A ),
		'wpml'       => get_option( 'icl_sitepress_settings' ),
	), JSON_UNESCAPED_UNICODE ) );
	if ( ! $ok ) { echo "پشتیبان نوشته نشد — توقف\n"; return; }
	echo "پشتیبان: {$bak}\n";
}

/* ── ۱. واژه‌نامه‌ها ── */
if ( $do_f ) {
echo "واژه‌نامه‌ها\n";
$gid = array();
foreach ( $wpdb->get_results( "SELECT id, labels FROM {$T} WHERE status = 'glossary'", ARRAY_A ) as $r ) {
	$l = maybe_unserialize( $r['labels'] );
	if ( isset( $l['name'] ) ) { $gid[ $l['name'] ] = (int) $r['id']; }
}
foreach ( tp_p5d450_glossaries() as $slug => $g ) {
	if ( isset( $gid[ $slug ] ) ) { $log( "#{$gid[ $slug ]} {$slug} بود" ); continue; }
	$opts = array();
	foreach ( $g[3] as $v => $lab ) { $opts[] = array( 'value' => $v, 'label' => $lab[0], 'is_checked' => false ); }
	if ( $apply ) {
		$wpdb->insert( $T, array( 'slug' => null, 'status' => 'glossary', 'labels' => maybe_serialize( array( 'name' => $slug ) ), 'args' => maybe_serialize( array( 'source' => 'manual' ) ), 'meta_fields' => maybe_serialize( $opts ) ) );
		$gid[ $slug ] = (int) $wpdb->insert_id;
	}
	$log( ( $apply ? '#' . $gid[ $slug ] . ' ' : '[ساخته می‌شود] ' ) . $slug . ' — ' . count( $opts ) . ' گزینه' );
}

/* ── ۲. فیلدها ── */
echo "فیلدها\n";
$boxes = get_option( 'jet_engine_meta_boxes', array() );
$own   = array();   /* نوع ← کلید متاباکس تک‌نوعی */
foreach ( $boxes as $k => $b ) {
	$at = array_values( (array) ( $b['args']['allowed_post_type'] ?? array() ) );
	if ( 1 === count( $at ) ) { $own[ $at[0] ] = $k; }
}
$used_ids = array();
array_walk_recursive( $boxes, function ( $v, $k ) use ( &$used_ids ) { if ( 'id' === $k && is_numeric( $v ) ) { $used_ids[ (int) $v ] = 1; } } );
$next_id = function () use ( &$used_ids ) { $i = 9500; while ( isset( $used_ids[ $i ] ) ) { $i++; } $used_ids[ $i ] = 1; return $i; };

$visit_key = '';
foreach ( $boxes as $k => $b ) { if ( 'بازدید (P5-D450)' === ( $b['args']['name'] ?? '' ) ) { $visit_key = $k; } }
$visit_types = tp_p5d450_fields()['opening_hours_spec'][5];
if ( '' === $visit_key ) {
	$n = 1; while ( isset( $boxes[ 'meta-' . $n ] ) ) { $n++; }
	$visit_key = 'meta-' . $n;
	$boxes[ $visit_key ] = array(
		'args'        => array( 'object_type' => 'post', 'allowed_tax' => array(), 'allowed_post_type' => $visit_types, 'allowed_posts' => array(), 'excluded_posts' => array(),
			'active_conditions' => array(), 'position' => 'normal', 'priority' => 'high', 'name' => 'بازدید (P5-D450)', 'hide_field_names' => true ),
		'meta_fields' => array(),
		'id'          => $visit_key,
	);
	$log( "{$visit_key} «بازدید (P5-D450)» برای " . implode( '، ', $visit_types ) );
}

$has = function ( $box, $name ) { foreach ( (array) $box['meta_fields'] as $f ) { if ( ( $f['name'] ?? '' ) === $name ) { return true; } } return false; };
$build = function ( $name, $d ) use ( $next_id, &$gid, $apply ) {
	list( $kind, $fa, , , $desc ) = $d;
	$type = array( 'url' => 'text', 'textarea' => 'textarea' )[ $kind ] ?? $kind;
	$f = array( 'type' => $type, 'options' => array(), 'repeater-fields' => array(), 'id' => $next_id(), 'title' => $fa, 'name' => $name, 'object_type' => 'field',
		'width' => in_array( $kind, array( 'repeater', 'textarea' ), true ) ? '100%' : '33.33333%', 'isNested' => false, 'description' => $desc, 'show_in_rest' => true );
	if ( in_array( $kind, array( 'select', 'checkbox' ), true ) ) {
		$f['options_source'] = 'glossary';
		$f['glossary_id']    = (string) ( $gid[ $d[7] ] ?? 0 );
		if ( 'checkbox' === $kind ) { $f['is_array'] = true; }
	}
	if ( 'repeater' === $kind ) {
		$f['repeater_collapsed']   = false;
		$f['repeater_title_field'] = array_key_first( $d[7] );
		foreach ( $d[7] as $sub => $s ) {
			$rf = array( 'title' => $s[1], 'name' => $sub, 'type' => $s[0], 'options_source' => 'manual', 'id' => $next_id() );
			if ( 'select' === $s[0] ) { $rf['options_source'] = 'glossary'; $rf['options'] = array(); $rf['glossary_id'] = (string) ( $gid[ $s[2] ] ?? 0 ); }
			if ( 'time' === $s[0] ) { $rf['input_type'] = 'time'; }
			$f['repeater-fields'][] = $rf;
		}
	}
	return $f;
};
$added = 0;
foreach ( tp_p5d450_fields() as $name => $d ) {
	$shared = $d[5] === $visit_types;   /* گروه «بازدید»: یک متاباکس مشترک برای هفت نوع */
	$targets = $shared ? array( $visit_key ) : array_map( function ( $t ) use ( $own ) { return $own[ $t ] ?? ''; }, $d[5] );
	foreach ( $targets as $bk ) {
		if ( '' === $bk || ! isset( $boxes[ $bk ] ) ) { $log( "✖ {$name}: متاباکس نوع " . implode( ',', $d[5] ) . ' نیست' ); continue; }
		if ( $has( $boxes[ $bk ], $name ) ) { continue; }
		$boxes[ $bk ]['meta_fields'][] = $build( $name, $d );
		$added++;
		$log( "{$bk} «" . ( $boxes[ $bk ]['args']['name'] ?? '' ) . "» + {$name} ({$d[0]}" . ( $d[6] ? ' ★' : '' ) . ')' );
	}
}
if ( $apply ) { update_option( 'jet_engine_meta_boxes', $boxes ); }
$log( "فیلد افزوده: {$added}" );
}

/* ── ۳. روابط تازه ── */
if ( $do_r ) {
echo "روابط تازه\n";
$rels = array();
foreach ( $wpdb->get_results( "SELECT id, labels, args FROM {$T} WHERE status = 'relation'", ARRAY_A ) as $r ) {
	$rels[ (int) $r['id'] ] = array( 'labels' => maybe_unserialize( $r['labels'] ), 'args' => maybe_unserialize( $r['args'] ) );
}
$by_name = array();
foreach ( $rels as $id => $r ) { if ( ! empty( $r['labels']['name'] ) ) { $by_name[ $r['labels']['name'] ] = $id; } }
foreach ( tp_p5d450_relations() as $key => $d ) {
	if ( isset( $by_name[ $key ] ) ) { $log( "#{$by_name[ $key ]} {$key} بود" ); continue; }
	list( $p, $c, $type, $pl, $cl ) = $d;
	$labels = array( 'name' => $key, 'parent_page_control_title' => $pl, 'parent_page_control_connect' => 'افزودن', 'parent_page_control_select' => 'انتخاب',
		'child_page_control_title' => $cl, 'child_page_control_connect' => 'افزودن', 'child_page_control_select' => 'انتخاب' );
	$a = array( 'db_table' => false, 'parent_control' => true, 'child_control' => true, 'parent_manager' => false, 'child_manager' => false,
		'parent_allow_delete' => false, 'child_allow_delete' => false, 'is_legacy' => false, 'rest_get_enabled' => false, 'rest_post_enabled' => false,
		'parents_required' => false, 'children_required' => false, 'name' => '', 'parent_object' => 'posts::' . $p, 'child_object' => 'posts::' . $c,
		'parent_rel' => null, 'type' => $type, 'legacy_id' => '', 'rest_get_access' => '', 'rest_post_access' => 'manage_options' );
	if ( $apply ) {
		$wpdb->insert( $T, array( 'slug' => '', 'status' => 'relation', 'labels' => maybe_serialize( $labels ), 'args' => maybe_serialize( $a ), 'meta_fields' => maybe_serialize( array() ) ) );
		$by_name[ $key ] = (int) $wpdb->insert_id;
	}
	$log( ( $apply ? '#' . $by_name[ $key ] . ' ' : '[ساخته می‌شود] ' ) . "{$key}: {$p} → {$c} ({$type})" . ( $d[7] ? ' ★' : '' ) );
}

/* ── ۴. ادغام P5-D451 ── */
echo "ادغام روابط\n";
foreach ( tp_p5d451_merges() as $drop => $keep ) {
	if ( ! isset( $rels[ $drop ] ) ) { $log( "#{$drop} دیگر نیست (ادغام شده)" ); continue; }
	$rows = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default WHERE rel_id = %d", $drop ) );
	if ( $rows ) { $log( "✖ #{$drop} {$rows} ردیف دارد — ادغام نشد (توقف این رابطه)" ); continue; }
	$dl = $rels[ $drop ]['labels'];
	/* برچسب دقیق‌تر «محل برگزاری» به رابطهٔ ماندنی می‌رود (روستا ↔ رویداد) */
	foreach ( $keep as $k ) {
		if ( ! isset( $rels[ $k ] ) ) { continue; }
		$kl = $rels[ $k ]['labels'];
		if ( false !== mb_strpos( (string) ( $dl['parent_page_control_title'] ?? '' ), 'محل برگزاری' ) ) {
			foreach ( array( 'parent_page_control_title', 'child_page_control_title' ) as $lk ) { if ( ! empty( $dl[ $lk ] ) ) { $kl[ $lk ] = $dl[ $lk ]; } }
			if ( $apply ) { $wpdb->update( $T, array( 'labels' => maybe_serialize( $kl ) ), array( 'id' => $k ) ); }
			$log( "#{$k} برچسب «" . $kl['parent_page_control_title'] . '» از #' . $drop );
		}
	}
	if ( $apply ) { $wpdb->delete( $T, array( 'id' => $drop, 'status' => 'relation' ) ); }
	$log( "#{$drop} «" . ( $dl['parent_page_control_title'] ?? '' ) . '» ← #' . implode( '/#', $keep ) . ' (۰ ردیف)' . ( $apply ? ' حذف شد' : ' [حذف می‌شود]' ) );
}

}

/* ── ۵. WPML — کپی برای عدد/گزینه/تاریخ/نشانی، ترجمه برای متن و ریپیتر متنی ── */
echo "WPML\n";
$s = get_option( 'icl_sitepress_settings' );
if ( $do_f && is_array( $s ) ) {
	foreach ( tp_p5d450_fields() as $name => $d ) {
		$mode = in_array( $d[0], array( 'textarea' ), true ) || in_array( $name, array( 'ferry_ports', 'viewpoints', 'gesture_warnings' ), true ) ? 2 : 1;
		$s['translation-management']['custom_fields_translation'][ $name ] = $mode;
	}
	if ( $apply ) { update_option( 'icl_sitepress_settings', $s ); }
	$log( 'custom_fields_translation: ' . count( tp_p5d450_fields() ) . ' فیلد (۱ کپی · ۲ ترجمه)' );
}

/* ── ۶. پیکربندی WPML (wpml-config 1.13، گزینهٔ wpml-tm-custom-xml) — همان حالت‌های بالا ── */
if ( $do_f ) {
	$xml   = (string) get_option( 'wpml-tm-custom-xml', '' );
	$lines = '';
	foreach ( tp_p5d450_fields() as $name => $d ) {
		if ( false !== strpos( $xml, '>' . $name . '</custom-field>' ) ) { continue; }
		$mode   = in_array( $d[0], array( 'textarea' ), true ) || in_array( $name, array( 'ferry_ports', 'viewpoints', 'gesture_warnings' ), true ) ? 'translate' : 'copy';
		$lines .= '    <custom-field action="' . $mode . '">' . $name . "</custom-field>\n";
	}
	if ( '' !== $lines && false !== strpos( $xml, '</custom-fields>' ) ) {
		$nl  = false !== strpos( $xml, "\r\n" ) ? "\r\n" : "\n";
		$xml = str_replace( '  </custom-fields>', '    <!-- P5-D450 (دستهٔ ۲۸) -->' . $nl . str_replace( "\n", $nl, $lines ) . '  </custom-fields>', $xml );
		if ( $apply ) { update_option( 'wpml-tm-custom-xml', $xml ); }
		$log( 'wpml-tm-custom-xml: ' . substr_count( $lines, "\n" ) . ' فیلد' . ( $apply ? '' : ' [افزوده می‌شود]' ) );
	} else {
		$log( 'wpml-tm-custom-xml: همه هست' );
	}
}

echo $apply ? "اعمال شد.\n" : "فقط گزارش — با apply اعمال می‌شود.\n";
