<?php
/**
 * ترمیم یال‌های گمشدهٔ یک پستِ جایگزین‌شده — دستهٔ 38-ب · P5-D481. فقط topersia.local. idempotent؛ پیش‌فرض dry-run.
 *
 *   wp eval-file scripts/tp-repair-iran-edges.php <edges.json> [apply]
 *
 * edges.json خروجی `scripts/tp-edges-from-backup.php` است (یال‌های پست قدیمی در بستهٔ پشتیبان). این ابزار **بازگردانی خام
 * نیست**: هر یال گمشده را با API خود جت‌انجین (`relation->update`) دوباره می‌سازد، آن هم فقط اگر
 *   · سرِ دیگر هنوز هست، همان نوع و همان entity_uid پشتیبان را دارد؛
 *   · یال همین‌الان نیست؛
 *   · برای رابطهٔ والد-یگانه فرزند والد دیگری ندارد (وگرنه `update` جت‌انجین والد موجود را جابه‌جا می‌کرد)، و برای
 *     یک‌به‌یک هیچ‌کدام از دو سر یال دیگری ندارد.
 * هیچ یال موجودی دست نمی‌خورد. هر یال ساخته‌شده در گزارش چاپ و (در apply) در `<edges.json>.created.json` می‌نشیند. یال
 * ترجمه‌ها (پست قدیمی en/ar) فقط وقتی ساخته می‌شود که ترجمهٔ همان پست جدید هست؛ وگرنه «ترجمهٔ پست جدید نیست».
 * در پایان: استان و شهرهای منتشرشدهٔ بی‌والد فهرست می‌شوند (فقط گزارش) و با apply گالری تجمیعی پست جدید بازسازی می‌شود.
 */
global $wpdb;

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { echo "فقط نسخهٔ محلی\n"; return; }

$file  = isset( $args[0] ) ? (string) $args[0] : '';
$apply = in_array( 'apply', $args, true );   /* موضعی: WP-CLI گزینه‌های -- را از $args جدا می‌کند */
$data  = is_file( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
if ( ! is_array( $data ) || empty( $data['edges'] ) ) { echo "edges.json خوانده نشد\n"; return; }

$new  = (int) $data['new_id'];
$olds = array_map( 'intval', $data['old_ids'] );
$post = get_post( $new );
if ( ! $post || 'tp_country' !== $post->post_type ) { echo "پست جدید #{$new} tp_country نیست\n"; return; }
$lang_of = function ( $id ) { $d = apply_filters( 'wpml_post_language_details', null, $id ); return is_array( $d ) && ! empty( $d['language_code'] ) ? $d['language_code'] : 'fa'; };
echo ( $apply ? 'حالت: اجرای واقعی' : 'حالت: dry-run (چیزی نوشته نمی‌شود)' ) . " — پست جدید #{$new} «" . $post->post_title . "»، بسته {$data['package']}\n\n";

$rel_edges_now = function ( $rel_id, $pid ) use ( $wpdb ) {
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default WHERE rel_id=%d AND (parent_object_id=%d OR child_object_id=%d)", $rel_id, $pid, $pid ) );
};

/* ── جدول: رابطه · پیش · اکنون · گمشده ── */
$before = array(); foreach ( $data['edges'] as $e ) { if ( $olds[0] === (int) $e['old_owner'] ) { $before[ $e['rel_id'] ][] = $e; } }
$plan   = array(); $rows = array();
foreach ( $before as $rel_id => $list ) {
	$object = topersia_relation( $rel_id );
	$now    = $rel_edges_now( $rel_id, $new );
	$miss   = 0;
	foreach ( $list as $e ) {
		$other = (int) $e['other_id'];
		$st    = 'buildable'; $why = '';
		$op    = get_post( $other );
		if ( ! $object ) { $st = 'no-relation'; }
		elseif ( ! $op ) { $st = 'target-gone'; }
		elseif ( $op->post_type !== $e['other']['type'] ) { $st = 'identity-mismatch'; $why = 'نوع ' . $op->post_type; }
		elseif ( '' !== (string) $e['other']['uid'] && (string) get_post_meta( $other, 'entity_uid', true ) !== (string) $e['other']['uid'] ) { $st = 'identity-mismatch'; $why = 'entity_uid فرق دارد'; }
		else {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default WHERE rel_id=%d AND parent_object_id=%d AND child_object_id=%d", $rel_id, $new, $other ) );
			if ( $exists ) { $st = 'exists'; }
			else {
				$miss++;
				if ( $object->is_single_parent() ) {
					$ps = array_map( 'absint', (array) $object->get_parents( $other, 'ids' ) );
					if ( $ps ) { $st = 'conflict'; $why = 'فرزند والد دیگری دارد: #' . implode( ',#', $ps ); }
				}
				if ( 'buildable' === $st && $object->is_single_child() && ! empty( $object->get_children( $new, 'ids' ) ) ) { $st = 'conflict'; $why = 'پست جدید همین رابطه را با فرزند دیگری دارد'; }
			}
		}
		$plan[] = array( 'rel' => $rel_id, 'other' => $other, 'title' => $e['other']['title'], 'type' => $e['other']['type'], 'status' => $st, 'why' => $why, 'draft' => $op && 'publish' !== $op->post_status ? $op->post_status : '' );
	}
	$rows[ $rel_id ] = array( count( $list ), $now, $miss );
}
$trans = array_filter( $data['edges'], function ( $e ) use ( $olds ) { return $olds[0] !== (int) $e['old_owner']; } );
echo "رابطه | پیش (پست قدیمی fa) | اکنون (#{$new}) | گمشده\n";
foreach ( $rows as $rid => $r ) { echo "  #{$rid} | {$r[0]} | {$r[1]} | {$r[2]}\n"; }
$dang = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'jet_rel_default WHERE parent_object_id IN (' . implode( ',', $olds ) . ') OR child_object_id IN (' . implode( ',', $olds ) . ')' );
echo '  یال‌هایی که اکنون به شناسه‌های قدیمی (' . implode( '،', $olds ) . ") اشاره می‌کنند: {$dang}\n";
echo '  یال‌های ترجمه‌های قدیمی (en/ar) در پشتیبان: ' . count( $trans ) . " — " . ( apply_filters( 'wpml_object_id', $new, 'tp_country', false, 'en' ) ? 'ترجمهٔ پست جدید هست' : 'ترجمهٔ پست جدید نیست؛ ساخته نمی‌شود' ) . "\n\n";

$by = array(); foreach ( $plan as $p ) { $by[ $p['status'] ][] = $p; }
foreach ( array( 'buildable', 'exists', 'conflict', 'identity-mismatch', 'target-gone', 'no-relation' ) as $s ) { echo str_pad( $s, 18 ) . count( isset( $by[ $s ] ) ? $by[ $s ] : array() ) . "\n"; }
echo "\nیال‌های ساختنی به #{$new}:\n";
foreach ( isset( $by['buildable'] ) ? $by['buildable'] : array() as $p ) { echo "  #{$p['rel']}: #{$p['other']} «{$p['title']}» ({$p['type']})" . ( $p['draft'] ? " [{$p['draft']}]" : '' ) . "\n"; }
foreach ( array( 'conflict', 'identity-mismatch', 'target-gone' ) as $s ) { foreach ( isset( $by[ $s ] ) ? $by[ $s ] : array() as $p ) { echo "  ⚠ {$s} #{$p['rel']}: #{$p['other']} «{$p['title']}» — {$p['why']}\n"; } }

/* ── استان‌ها و شهرهای منتشرشدهٔ بی‌والد (فقط گزارش) ── */
$real = function ( $type ) use ( $wpdb ) { return $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_title FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_tp_sample' WHERE p.post_type=%s AND p.post_status='publish' AND s.post_id IS NULL ORDER BY p.ID", $type ), ARRAY_A ); };
$no_par = array();
foreach ( $real( 'tp_province' ) as $r ) {
	if ( 'fa' !== $lang_of( $r['ID'] ) ) { continue; }
	$has = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default WHERE rel_id=71 AND child_object_id=%d", $r['ID'] ) );
	$will = false; foreach ( isset( $by['buildable'] ) ? $by['buildable'] : array() as $p ) { if ( 71 === $p['rel'] && (int) $p['other'] === (int) $r['ID'] ) { $will = true; } }
	if ( ! $has ) { $no_par[] = '#' . $r['ID'] . ' ' . $r['post_title'] . ( $will ? ' (با این ترمیم والد می‌گیرد)' : '' ); }
}
echo "\nاستان‌های منتشرشده بی‌والدِ کشور (رابطهٔ ۷۱): " . count( $no_par ) . "\n"; foreach ( $no_par as $l ) { echo "  $l\n"; }
$nc = array();
foreach ( $real( 'tp_city' ) as $r ) {
	if ( 'fa' !== $lang_of( $r['ID'] ) ) { continue; }
	if ( function_exists( 'topersia_g_place_chain' ) && ! topersia_g_place_chain( $r['ID'] ) ) { $nc[] = '#' . $r['ID'] . ' ' . $r['post_title']; }
}
echo 'شهرهای منتشرشدهٔ بی‌زنجیرهٔ مکانی (بی استان/شهرستان والد): ' . count( $nc ) . "\n"; foreach ( array_slice( $nc, 0, 40 ) as $l ) { echo "  $l\n"; } if ( count( $nc ) > 40 ) { echo '  … و ' . ( count( $nc ) - 40 ) . " دیگر\n"; }

if ( ! $apply ) { echo "\n(dry-run — برای ساخت: آرگومان apply، پس از پشتیبان)\n"; return; }

/* ── اجرای واقعی: فقط buildable ── */
$created = array();
foreach ( isset( $by['buildable'] ) ? $by['buildable'] : array() as $p ) {
	$object = topersia_relation( $p['rel'] );
	$res    = $object->update( $new, $p['other'] );
	$ok     = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default WHERE rel_id=%d AND parent_object_id=%d AND child_object_id=%d", $p['rel'], $new, $p['other'] ) );
	$created[] = array( 'rel' => $p['rel'], 'parent' => $new, 'child' => $p['other'], 'ok' => $ok );
	echo ( $ok ? '  ✓' : '  ✗' ) . " #{$p['rel']} {$new}→{$p['other']}\n";
}
file_put_contents( $file . '.created.json', wp_json_encode( $created ) );
if ( function_exists( 'topersia_g_gallery_agg_rebuild' ) ) {
	$r = topersia_g_gallery_agg_rebuild( $new, true );
	echo 'گالری تجمیعی بازسازی شد — افزوده‌ها: ' . count( topersia_g_gallery_agg_stored( $new ) ) . "\n";
}
echo 'ساخته شد: ' . count( array_filter( $created, function ( $c ) { return $c['ok']; } ) ) . ' از ' . count( $created ) . "\n";
