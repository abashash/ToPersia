<?php
/**
 * پرسش‌های پایگاه‌دادهٔ LQ-PERF-* و LQ-SCH-* — فقط خواندنی  (فقط محلی؛ هیچ نوشتنی)
 *
 *   wp eval-file scripts/local/tp-lq-perf-sch.php
 *
 * بخش HTTP همان پرسش‌ها (LQ-PERF-01 · 02 · 06 · 07 · LQ-SCH-01 · 02 و canonical) در tests/lq-http.js است.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) { exit; }
global $wpdb;
$out = array();

/* LQ-PERF-03 */
$out['LQ-PERF-03'] = 'elementor_experiment-e_font_icon_svg = ' . var_export( get_option( 'elementor_experiment-e_font_icon_svg', '(unset)' ), true );

/* LQ-PERF-04 — فقط نام کلید */
$rs = (array) get_option( 'topersia_render_settings', array() );
$fw = array();
foreach ( $rs as $k => $v ) { if ( 0 === strpos( (string) $k, 'font_' ) ) { $fw[] = $k . ':' . ( '' !== $v && null !== $v && array() !== $v ? 'set' : '-' ); } }
$out['LQ-PERF-04'] = $fw ? implode( ' · ', $fw ) : '(هیچ کلید font_*)';

/* LQ-PERF-05 */
$dir = WP_CONTENT_DIR . '/uploads/elementor/css';
$files = is_dir( $dir ) ? glob( $dir . '/*.css' ) : array();
$tot = 0; foreach ( (array) $files as $f ) { $tot += filesize( $f ); }
$kit = (int) get_option( 'elementor_active_kit' );
$out['LQ-PERF-05'] = count( (array) $files ) . ' پرونده · ' . $tot . ' بایت؛ post-' . $kit . '.css ' . ( is_file( $dir . '/post-' . $kit . '.css' ) ? filesize( $dir . '/post-' . $kit . '.css' ) . ' بایت' : 'نیست (پس از پاک‌سازی کش)' );

/* LQ-PERF-08 */
$th = wp_get_theme();
$xo = (array) get_option( 'xts-woodmart-options', array() );
$keys = array();
foreach ( $xo as $k => $v ) { if ( preg_match( '/^(combined_[a-z_]*|font_awesome_css|styles_always_use|jquery_migrate|lazy_loading|light_bootstrap_version)$/', (string) $k ) ) { $keys[] = $k . '=' . ( is_scalar( $v ) ? var_export( $v, true ) : json_encode( $v ) ); } }
$out['LQ-PERF-08'] = 'پوسته: ' . $th->get( 'Name' ) . ' ' . $th->get( 'Version' ) . ' (stylesheet=' . get_stylesheet() . ') · ' . ( $keys ? implode( ' · ', $keys ) : 'کلید عملکرد در xts-woodmart-options نیست' );

/* LQ-SCH-03 — منتشرشده بی entity_uid، به تفکیک نوع tp_ */
$rows = $wpdb->get_results( "SELECT p.post_type t, COUNT(*) c FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'entity_uid' AND m.meta_value <> '' WHERE p.post_status = 'publish' AND p.post_type LIKE 'tp\\_%' AND m.post_id IS NULL GROUP BY p.post_type ORDER BY c DESC" );
$tot = 0; $by = array(); foreach ( $rows as $r ) { $tot += (int) $r->c; $by[] = $r->t . '=' . $r->c; }
$all = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type LIKE 'tp\\_%'" );
$out['LQ-SCH-03'] = "منتشرشدهٔ tp_ بی entity_uid: {$tot} از {$all}" . ( $by ? ' (' . implode( ' · ', array_slice( $by, 0, 8 ) ) . ')' : '' );

/* LQ-SCH-04 */
$ext = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE m.meta_key='external_reference_uri' AND m.meta_value<>'' AND p.post_status='publish'" );
$wd  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE m.meta_key='external_reference_uri' AND m.meta_value LIKE '%wikidata.org%' AND p.post_status='publish'" );
$out['LQ-SCH-04'] = "external_reference_uri پرشده: {$ext} از {$all} منتشرشدهٔ tp_ (ویکی‌داده: {$wd})";

/* LQ-SCH-05 — population و area_km2 پرشده به تفکیک نوع */
$parts = array();
foreach ( array( 'population', 'area_km2' ) as $k ) {
	$r = $wpdb->get_results( $wpdb->prepare( "SELECT p.post_type t, COUNT(*) c FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE m.meta_key=%s AND m.meta_value<>'' AND p.post_status='publish' GROUP BY p.post_type", $k ) );
	$parts[] = $k . ': ' . ( $r ? implode( ' · ', array_map( function ( $x ) { return $x->t . '=' . $x->c; }, $r ) ) : 'هیچ' );
}
$out['LQ-SCH-05'] = implode( ' | ', $parts ) . ' (دیدنی‌بودن در نوع‌های غیر کشور/شهر: بر پایهٔ topersia_g_type_matrix — کشور/شهر ماتریس آمار دارند)';

/* LQ-SCH-06 */
$md = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT m.post_id) FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID=m.post_id WHERE m.meta_key='meta_description' AND m.meta_value<>'' AND p.post_status='publish'" );
$both = 0;
$tbl = $wpdb->prefix . 'topersia_ai_summaries';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$tbl}'" ) ) {
	$both = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT s.post_id) FROM {$tbl} s JOIN {$wpdb->postmeta} m ON m.post_id=s.post_id AND m.meta_key='meta_description' AND m.meta_value<>'' JOIN {$wpdb->posts} p ON p.ID=s.post_id AND p.post_status='publish'" );
}
$out['LQ-SCH-06'] = "پست منتشرشدهٔ دارای meta_description: {$md}؛ دارای هر دو (meta_description و خروجی خلاصه‌ساز): {$both}";

foreach ( $out as $k => $v ) { echo $k . ' | ' . $v . "\n"; }
