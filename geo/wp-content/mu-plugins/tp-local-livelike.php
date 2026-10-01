<?php
/**
 * 4.2.3 — local only, CLI only, and only with TP_LIVELIKE=1.
 *
 * On Windows MySQL stores table names in lower case (lower_case_table_names=1),
 * so JetReviews (dbDelta) and Elementor (e_events) believe their tables are
 * missing and run ALTER/CREATE on every request. On the live Linux/MariaDB
 * server those statements never run. When testing with the live-like php.ini
 * (error_log/mail disabled, no mbstring) that Windows-only artifact would
 * mask everything else, so here — and only here — schema statements are
 * replaced with a harmless SELECT.
 */
if ( 'cli' === PHP_SAPI && getenv( 'TP_LIVELIKE' ) ) {
	add_filter( 'dbdelta_queries', '__return_empty_array', 1 );
	add_filter( 'query', function ( $q ) {
		return preg_match( '/^\s*(ALTER|CREATE)\s+TABLE\b/i', (string) $q ) ? 'SELECT 1' : $q;
	}, 1 );
}
