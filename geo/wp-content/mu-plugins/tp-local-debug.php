<?php
/**
 * فقط نسخهٔ محلی — اشکال‌زداییِ درخواست در CLI، فقط وقتی TP_DEBUG_REQ هست.  ۴٫۲٫۲
 * روی سایتِ زنده نیست و نباید باشد.
 */
if ( getenv( 'TP_DEBUG_REQ' ) && 'cli' === PHP_SAPI ) {
	add_action( 'wp', function () {
		global $wp, $wp_query;
		fwrite( STDERR, 'REQ ' . json_encode( array(
			'request'      => $wp->request,
			'matched_rule' => $wp->matched_rule,
			'query_vars'   => $wp->query_vars,
			'sql'          => $wp_query->request,
			'found'        => $wp_query->found_posts,
			'is_404'       => $wp_query->is_404(),
			'lang'         => apply_filters( 'wpml_current_language', null ),
		), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
	}, 999 );
}

if ( getenv( 'TP_DEBUG_REQ' ) && 'cli' === PHP_SAPI ) {
	add_filter( 'wp_redirect', function ( $location, $status ) {
		fwrite( STDERR, 'REDIRECT ' . $status . ' ' . $location . "\n  " . wp_debug_backtrace_summary() . "\n" );
		return $location;
	}, 999, 2 );
}

if ( getenv( 'TP_DEBUG_REQ' ) && 'cli' === PHP_SAPI ) {
	foreach ( array( 'auth_cookie_malformed', 'auth_cookie_expired', 'auth_cookie_bad_username', 'auth_cookie_bad_hash', 'auth_cookie_bad_session_token', 'auth_cookie_valid' ) as $tp_hook ) {
		add_action( $tp_hook, function () use ( $tp_hook ) {
			fwrite( STDERR, 'AUTH ' . $tp_hook . "\n" );
		} );
	}
}
