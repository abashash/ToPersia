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