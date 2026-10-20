<?php
/**
 * Uninstall routine for ToPersia AI Summarizer.
 *
 * Removes every setting, transient, post meta and click statistic the plugin
 * may have created.
 *
 * @package TP_AI_Summarizer
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'tpas_settings' );
delete_option( 'tpas_stats' );
delete_option( 'tpas_auto_on_140' );   // 1.4.0 · P5-D382

// Per-user metadata: the daily AI quota counter (1.3.1, P5-D375).
delete_metadata( 'user', 0, '_topersia_ai_daily', '', true );

// Per-post metadata: visibility flags, click counts and AI caches.
delete_post_meta_by_key( '_tpas_clicks' );
delete_post_meta_by_key( '_tpas_hide_buttons' );
delete_post_meta_by_key( '_tpas_hide_panel' );

$types = array( 'summary', 'keypoints', 'plan' );
$langs = array( 'fa', 'en', 'ar', 'fr', 'de', 'es', 'ru', 'tr', 'zh' );

foreach ( $types as $type ) {
	foreach ( $langs as $lang ) {
		delete_post_meta_by_key( '_tpas_ai_' . $type . '_' . $lang );
	}
}

// Rate-limit transients.
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tpas_rt_%' OR option_name LIKE '_transient_timeout_tpas_rt_%'"
);