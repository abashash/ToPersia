<?php
/**
 * ۲و٫۱ — ممیزی فقط‌خواندنی رابطه‌ها و متاها در قالب (P5-D324 · P5-D325).
 *
 * هیچ چیز نمی‌نویسد: فقط خواندن از وردپرس محلی (جت‌انجین زنده) و کد render.
 * اجرا:
 *   php wp-cli.phar --path=C:/wamp64/www/topersia/geo --url=http://topersia.local eval-file scripts/audit-relations-2f.php
 * خروجی: docs/AUDIT-RELATIONS-2F.csv (روابط) · AUDIT-META-2F.csv · AUDIT-QUERIES-2F.csv · AUDIT-SAMPLES-2F.json
 * (متن گزارش را `docs/AUDIT-RELATIONS-2F.md` از روی همین خروجی‌ها می‌نویسد.)
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; return; }

$root = dirname( __DIR__ );
$docs = $root . '/docs';
$src  = '';
foreach ( glob( $root . '/plugin/topersia-render/includes/*.php' ) as $f ) { $src .= file_get_contents( $f ); }

/* ── ۱. روابط ──────────────────────────────────────────────────────── */
$shown = array();   /* "نوع|شناسه|سو" ← عنوان گروه‌ها */
foreach ( topersia_g_relmap() as $type => $groups ) {
	foreach ( $groups as $gkey => $g ) {
		foreach ( (array) ( isset( $g['rels'] ) ? $g['rels'] : array() ) as $spec ) {
			$shown[ $type . '|' . $spec['id'] . '|' . ( isset( $spec['dir'] ) ? $spec['dir'] : 'children' ) ][] = $gkey;
		}
	}
}
$schema_parents = array();
if ( function_exists( 'topersia_schema_map' ) ) {
	foreach ( topersia_schema_map() as $type => $cfg ) {
		foreach ( (array) ( isset( $cfg['parents'] ) ? $cfg['parents'] : array() ) as $step ) {
			$schema_parents[ $type . '|' . $step['relation'] ] = 1;
		}
	}
}
global $wpdb;
$counts = array();
foreach ( $wpdb->get_results( "SELECT rel_id, COUNT(*) n FROM {$wpdb->prefix}jet_rel_default GROUP BY rel_id", ARRAY_A ) as $r ) { $counts[ (int) $r['rel_id'] ] = (int) $r['n']; }

$rows = array(); $rel_by_type = array();
foreach ( jet_engine()->relations->get_active_relations() as $rel ) {
	$a   = $rel->get_args();
	$id  = (int) $rel->get_id();
	$pt  = preg_replace( '#^posts::#', '', (string) $a['parent_object'] );
	$ct  = preg_replace( '#^posts::#', '', (string) $a['child_object'] );
	$lab = isset( $a['labels'] ) && is_array( $a['labels'] ) ? $a['labels'] : array();
	$p_in_child = isset( $shown[ $ct . '|' . $id . '|parents' ] );     /* والد در صفحهٔ فرزند */
	$c_in_par   = isset( $shown[ $pt . '|' . $id . '|children' ] );    /* فرزندان در صفحهٔ والد */
	$rows[]     = array(
		'id' => $id, 'name' => isset( $a['name'] ) ? $a['name'] : '', 'type' => $a['type'], 'parent' => $pt, 'child' => $ct,
		'parent_label' => isset( $lab['parent_page_control_title'] ) ? $lab['parent_page_control_title'] : '',
		'child_label'  => isset( $lab['child_page_control_title'] ) ? $lab['child_page_control_title'] : '',
		'rows' => isset( $counts[ $id ] ) ? $counts[ $id ] : 0,
		'parent_in_child_page' => $p_in_child ? implode( '+', array_unique( $shown[ $ct . '|' . $id . '|parents' ] ) ) : '',
		'children_in_parent_page' => $c_in_par ? implode( '+', array_unique( $shown[ $pt . '|' . $id . '|children' ] ) ) : '',
		'in_schema_parents' => ( isset( $schema_parents[ $ct . '|' . $id ] ) ? 'child' : '' ) . ( isset( $schema_parents[ $pt . '|' . $id ] ) ? 'parent' : '' ),
		'parent_type_has_relmap' => isset( topersia_g_relmap()[ $pt ] ) ? 'y' : 'n',
		'child_type_has_relmap'  => isset( topersia_g_relmap()[ $ct ] ) ? 'y' : 'n',
	);
}
usort( $rows, function ( $x, $y ) { return $x['id'] <=> $y['id']; } );
$fh = fopen( $docs . '/AUDIT-RELATIONS-2F.csv', 'w' ); fwrite( $fh, "\xEF\xBB\xBF" );
fputcsv( $fh, array_keys( $rows[0] ) ); foreach ( $rows as $r ) { fputcsv( $fh, $r ); } fclose( $fh );

/* ── ۲. نوع × متا ──────────────────────────────────────────────────── */
$map    = json_decode( file_get_contents( $root . '/reference/TP-G-FIELDMAP.json' ), true );
$mapped = isset( $map['fields'] ) ? $map['fields'] : array();
$internal = function ( $n, $t ) {
	if ( preg_match( '/^(entity_uid|row_status|meta_|_)/', $n ) ) { return 'فنی/داخلی'; }
	if ( in_array( $t, array( 'html', 'tab', 'accordion', 'repeater-hidden' ), true ) ) { return 'ساختاری'; }
	return '';
};
$meta = array(); $per_type = array();
foreach ( (array) get_option( 'jet_engine_meta_boxes' ) as $box ) {
	$types = isset( $box['args']['allowed_post_type'] ) ? (array) $box['args']['allowed_post_type'] : array();
	foreach ( (array) $box['meta_fields'] as $f ) {
		if ( empty( $f['name'] ) ) { continue; }
		$n = $f['name']; $t = isset( $f['type'] ) ? $f['type'] : '';
		$where = '';
		if ( isset( $mapped[ $n ] ) ) { $where = 'fieldmap:' . $mapped[ $n ]['zone']; }
		elseif ( preg_match( "/['\"]" . preg_quote( $n, '/' ) . "['\"]/", $src ) ) { $where = 'render-code'; }
		$int = $internal( $n, $t );
		foreach ( $types as $pt ) {
			$state = $where ? 'جا دارد' : ( $int ? $int : 'بی‌جا' );
			$meta[] = array( 'type' => $pt, 'field' => $n, 'field_type' => $t, 'title' => isset( $f['title'] ) ? $f['title'] : '', 'place' => $where, 'state' => $state );
			$per_type[ $pt ][ $state ] = ( isset( $per_type[ $pt ][ $state ] ) ? $per_type[ $pt ][ $state ] : 0 ) + 1;
		}
	}
}
$fh = fopen( $docs . '/AUDIT-META-2F.csv', 'w' ); fwrite( $fh, "\xEF\xBB\xBF" );
fputcsv( $fh, array_keys( $meta[0] ) ); foreach ( $meta as $r ) { fputcsv( $fh, $r ); } fclose( $fh );

/* ── ۳. Queryهای Query Builder ─────────────────────────────────────── */
$qrows = array();
foreach ( $wpdb->get_results( "SELECT id, labels, args FROM {$wpdb->prefix}jet_post_types WHERE status='query'", ARRAY_A ) as $q ) {
	$lab = maybe_unserialize( $q['labels'] ); $arg = maybe_unserialize( $q['args'] ); $id = (int) $q['id'];
	$use_el   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_elementor_data' AND (meta_value LIKE %s OR meta_value LIKE %s)", '%"query_id":"' . $id . '"%', '%"jet_query_id":"' . $id . '"%' ) );
	$use_post = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status<>'trash' AND post_content LIKE %s", '%query_id%' . $id . '%' ) );
	$use_code = ( false !== strpos( $src, "'query_id' => " . $id ) || false !== strpos( $src, 'jet_engine()->query' ) ) ? 1 : 0;
	$ptypes   = isset( $arg['posts']['post_type'] ) ? implode( '+', (array) $arg['posts']['post_type'] ) : '';
	$qrows[]  = array( 'id' => $id, 'name' => isset( $lab['name'] ) ? $lab['name'] : '', 'query_type' => isset( $arg['query_type'] ) ? $arg['query_type'] : '', 'post_type' => $ptypes,
		'used_in_elementor_data' => $use_el, 'used_in_post_content' => $use_post, 'used_in_render_code' => $use_code );
}
$fh = fopen( $docs . '/AUDIT-QUERIES-2F.csv', 'w' ); fwrite( $fh, "\xEF\xBB\xBF" );
fputcsv( $fh, array_keys( $qrows[0] ) ); foreach ( $qrows as $r ) { fputcsv( $fh, $r ); } fclose( $fh );

/* ── ۴. نمونهٔ زنده ───────────────────────────────────────────────── */
$samples = array( 3267 => 'G', 3759 => 'G', 1981 => 'G', 1944 => 'G', 2095 => 'G', 1962 => 'A', 1954 => 'A', 1947 => 'A', 1965 => 'C', 1967 => 'E', 1968 => 'P' );
$live    = array();
foreach ( jet_engine()->relations->get_active_relations() as $rel ) { $live[ (int) $rel->get_id() ] = $rel; }
$out = array();
foreach ( $samples as $pid => $cls ) {
	$post = get_post( $pid ); if ( ! $post ) { continue; }
	$url  = get_permalink( $pid );
	$ctx  = stream_context_create( array( 'http' => array( 'timeout' => 90, 'ignore_errors' => true ) ) );
	$html = (string) file_get_contents( $url, false, $ctx );
	$rel_found = array(); $linked = 0; $total = 0;
	foreach ( $rows as $r ) {
		foreach ( array( 'children' => $r['parent'], 'parents' => $r['child'] ) as $dir => $mine ) {
			if ( $mine !== $post->post_type ) { continue; }
			$ids = 'children' === $dir ? $live[ $r['id'] ]->get_children( $pid, 'ids' ) : $live[ $r['id'] ]->get_parents( $pid, 'ids' );
			$ids = array_filter( array_map( 'absint', (array) $ids ), function ( $i ) { return 'publish' === get_post_status( $i ); } );
			if ( ! $ids ) { continue; }
			$hit = 0; foreach ( $ids as $i ) { if ( false !== strpos( $html, 'href="' . get_permalink( $i ) . '"' ) ) { $hit++; } }
			$rel_found[] = array( 'rel' => $r['id'], 'name' => $r['name'], 'dir' => $dir, 'published' => count( $ids ), 'linked_in_html' => $hit );
			$total += count( $ids ); $linked += $hit;
		}
	}
	$out[] = array( 'id' => $pid, 'class' => $cls, 'type' => $post->post_type, 'title' => $post->post_title, 'http_len' => strlen( $html ),
		'related_published' => $total, 'linked' => $linked, 'relations' => $rel_found );
}
file_put_contents( $docs . '/AUDIT-SAMPLES-2F.json', wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );

/* ── خلاصهٔ عددی ─────────────────────────────────────────────────── */
$relmap_types = array_keys( topersia_g_relmap() );
$ptypes_pub   = array_filter( get_post_types( array( 'public' => true ) ), function ( $t ) { return 0 === strpos( $t, 'tp_' ); } );
$rel_p_miss = 0; $rel_c_miss = 0; $rel_empty = 0;
foreach ( $rows as $r ) {
	if ( '' === $r['parent_in_child_page'] ) { $rel_p_miss++; }
	if ( '' === $r['children_in_parent_page'] ) { $rel_c_miss++; }
	if ( ! $r['rows'] ) { $rel_empty++; }
}
$st = array(); foreach ( $meta as $m ) { $st[ $m['state'] ] = ( isset( $st[ $m['state'] ] ) ? $st[ $m['state'] ] : 0 ) + 1; }
echo wp_json_encode( array(
	'public_tp_types' => count( $ptypes_pub ), 'types_with_relmap' => count( $relmap_types ), 'relmap_types' => $relmap_types,
	'relations' => count( $rows ), 'relations_without_parent_side' => $rel_p_miss, 'relations_without_children_side' => $rel_c_miss, 'relations_with_zero_rows' => $rel_empty,
	'meta_rows_by_state' => $st, 'meta_by_type' => $per_type, 'queries' => $qrows,
	'samples' => array_map( function ( $s ) { return array( $s['id'], $s['type'], $s['related_published'], $s['linked'] ); }, $out ),
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ), "\n";
