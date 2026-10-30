<?php
/**
 * واقعیت‌های بسته برای manifest — دستهٔ 37 · P5-D475. با `wp eval-file` اجرا می‌شود؛ فقط می‌خواند.
 *
 *   wp --skip-plugins --skip-themes eval-file scripts/lib/tp-backup-facts.php <خروجی.json>
 *
 * نسخهٔ WPML (سربرگ افزونه و گزینهٔ پایگاه‌داده)، شمار پست هر نوع (کل · واقعی · نمونه)، شمار ردیف رابطه
 * (هر جدول jet_rel* و هر rel_id)، نسخهٔ پایگاه‌داده و وردپرس. بی هیچ گزینه‌ای که کلید باشد.
 */
global $wpdb;
$out = array();
$out['wp_version'] = get_bloginfo( 'version' );
$out['db_version'] = (string) $wpdb->get_var( 'SELECT VERSION()' );
$out['db_name_is_local'] = ( 'topersia_local' === DB_NAME );

$hdr = WP_PLUGIN_DIR . '/sitepress-multilingual-cms/sitepress.php';
$ver = '';
if ( is_file( $hdr ) && preg_match( '/^\s*\*?\s*Version:\s*(\S+)/mi', (string) file_get_contents( $hdr, false, null, 0, 4096 ), $m ) ) { $ver = $m[1]; }
$out['wpml'] = array( 'plugin_header_version' => $ver, 'db_option_version' => (string) get_option( 'icl_sitepress_version', '' ) );

$rows = $wpdb->get_results(
	"SELECT p.post_type t, COUNT(*) n, SUM(s.post_id IS NULL) real_n, SUM(s.post_id IS NOT NULL) sample_n
	 FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_tp_sample'
	 WHERE p.post_status NOT IN ('auto-draft','trash','inherit') GROUP BY p.post_type ORDER BY p.post_type", ARRAY_A );
$out['posts_by_type'] = array();
foreach ( $rows as $r ) { $out['posts_by_type'][ $r['t'] ] = array( 'total' => (int) $r['n'], 'real' => (int) $r['real_n'], 'sample' => (int) $r['sample_n'] ); }

$out['relation_rows'] = array();
foreach ( $wpdb->get_col( "SHOW TABLES LIKE '%jet\\_rel%'" ) as $t ) {
	$short = substr( $t, strlen( $wpdb->prefix ) );
	$out['relation_rows'][ $short ] = array( 'total' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$t`" ) );
	$cols = $wpdb->get_col( "SHOW COLUMNS FROM `$t`" );
	if ( in_array( 'rel_id', $cols, true ) ) {
		$by = array();
		foreach ( $wpdb->get_results( "SELECT rel_id, COUNT(*) n FROM `$t` GROUP BY rel_id ORDER BY rel_id", ARRAY_A ) as $r ) { $by[ (string) $r['rel_id'] ] = (int) $r['n']; }
		$out['relation_rows'][ $short ]['by_rel_id'] = $by;
	}
}
$out['tables'] = (int) count( $wpdb->get_col( 'SHOW TABLES' ) );
$out['home'] = (string) get_option( 'home' );
file_put_contents( (string) $args[0], wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
echo "ok\n";
