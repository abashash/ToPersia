<?php
/**
 * اثر انگشت پایگاه برای آزمون رفت‌وبرگشت نمونه‌ها — دستهٔ 32 بند 7. با wp eval-file اجرا می‌شود؛ فقط می‌خواند.
 *
 *   wp eval-file tests/lib/db-fingerprint.php <خروجی.json>
 *
 * هر جدول: شمار ردیف؛ جدول‌های محتوا (پست، متا، رابطه، نمایهٔ جغرافیایی، WPML، خلاصهٔ AI، واژه‌ها) + CHECKSUM محتوا.
 * options: فقط فهرست نام‌ها (ترنزینت و کرون بیرون) و چکسام مقدارهای topersia_*. CHECKSUM شمارندهٔ خودکار را نمی‌گیرد.
 */
global $wpdb;
$wpdb->query( 'SET SESSION group_concat_max_len = 1073741824' );   /* وگرنه GROUP_CONCAT پیش‌فرض ۱۰۲۴ بایت می‌بُرد و چکسام بی‌معنی می‌شود */
$out    = array( 'count' => array(), 'sum' => array(), 'options' => array(), 'real' => array() );
$sumtab = array( 'posts', 'term_relationships', 'term_taxonomy', 'terms', 'jet_rel_default', 'topersia_geo', 'icl_translations', 'topersia_ai_summaries', 'comments' );
foreach ( $wpdb->get_col( 'SHOW TABLES' ) as $t ) {
	$short = substr( $t, strlen( $wpdb->prefix ) );
	$out['count'][ $short ] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$t`" );
	if ( in_array( $short, $sumtab, true ) ) {
		$r = $wpdb->get_row( "CHECKSUM TABLE `$t`", ARRAY_N );
		$out['sum'][ $short ] = $r ? (string) $r[1] : '';
	}
}
$names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name NOT LIKE '\\_transient%' AND option_name NOT LIKE '\\_site\\_transient%' AND option_name <> 'cron' ORDER BY option_name" );
$out['options']['names']    = md5( implode( "\n", $names ) );
$out['options']['count']    = count( $names );
$out['options']['topersia'] = md5( (string) $wpdb->get_var( "SELECT GROUP_CONCAT(CONCAT(option_name,'=',MD5(option_value)) ORDER BY option_name SEPARATOR '|') FROM {$wpdb->options} WHERE option_name LIKE 'topersia\\_%' AND option_name NOT LIKE '%stamp%' AND option_name NOT LIKE '%transient%'" ) );
$out['real'] = array(
	'posts'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','auto-draft') AND post_status <> 'auto-draft'" ),
	'rel_total' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}jet_rel_default" ),
	'geo'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}topersia_geo" ),
	'real_hash' => md5( (string) $wpdb->get_var( "SELECT GROUP_CONCAT(CONCAT(ID,':',post_modified_gmt,':',MD5(post_content),':',MD5(post_title)) ORDER BY ID SEPARATOR '|') FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','auto-draft')" ) ),
);
/* تشخیص: متای هر پست (شناسه ← چکسام) و کارهای صف به‌تفکیک hook/وضعیت */
$out['meta_by_post'] = array();
foreach ( $wpdb->get_results( "SELECT post_id, meta_key, MD5(meta_value) h FROM {$wpdb->postmeta} ORDER BY meta_id", ARRAY_A ) as $r ) { $out['meta_by_post'][ (int) $r['post_id'] ][] = $r['meta_key'] . '=' . $r['h']; }
$out['as_by_hook'] = array();
foreach ( $wpdb->get_results( "SELECT CONCAT(hook,'|',status) k, COUNT(*) n FROM {$wpdb->prefix}actionscheduler_actions GROUP BY hook, status", ARRAY_A ) as $r ) { $out['as_by_hook'][ $r['k'] ] = (int) $r['n']; }
$out['as_pending_args'] = $wpdb->get_col( "SELECT CONCAT(hook,' ',args) FROM {$wpdb->prefix}actionscheduler_actions WHERE status='pending' ORDER BY hook, args" );
$out['log_by_channel'] = array();
foreach ( $wpdb->get_results( "SELECT channel k, COUNT(*) n FROM {$wpdb->prefix}topersia_log GROUP BY channel", ARRAY_A ) as $r ) { $out['log_by_channel'][ $r['k'] ] = (int) $r['n']; }
file_put_contents( (string) $args[0], wp_json_encode( $out, JSON_PRETTY_PRINT ) );
echo "ok\n";
