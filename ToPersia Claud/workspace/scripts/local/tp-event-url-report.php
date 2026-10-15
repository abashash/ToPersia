<?php
/**
 * گزارش فقط‌خواندنی official_event_url — P5-D361  (فقط محلی؛ هیچ نوشتنی)
 *
 *   wp eval-file scripts/local/tp-event-url-report.php
 *
 * شمار مقدارهای غیرخالی، شمار ردشده با قاعدهٔ اسکیما (topersia_schema_event_official_url)، و از ردشده‌ها شمار آنکه علتش نویسهٔ غیر ASCII است.
 * «رد» یعنی filter_var(FILTER_VALIDATE_URL) رد کند، یا https نباشد، یا دامنهٔ topersia.com، یا نام‌کاربری داشته باشد، یا میزبان بی نقطه.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) { exit; }

global $wpdb;
$rows = $wpdb->get_results( "SELECT m.post_id, p.post_type, p.post_status, m.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = 'official_event_url' AND TRIM(m.meta_value) <> ''" );
$total = count( $rows ); $rejected = 0; $nonascii = 0; $nonascii_idn = 0; $reasons = array();

foreach ( $rows as $r ) {
	$url = trim( (string) $r->meta_value );
	$why = '';
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) { $why = 'filter_var'; }
	else {
		$p    = parse_url( $url );
		$host = isset( $p['host'] ) ? strtolower( rtrim( $p['host'], '.' ) ) : '';
		if ( ! is_array( $p ) || 'https' !== strtolower( (string) ( isset( $p['scheme'] ) ? $p['scheme'] : '' ) ) ) { $why = 'not-https'; }
		elseif ( '' === $host || false === strpos( $host, '.' ) ) { $why = 'bad-host'; }
		elseif ( isset( $p['user'] ) || isset( $p['pass'] ) ) { $why = 'credentials'; }
		elseif ( 'topersia.com' === $host || '.topersia.com' === substr( $host, -13 ) ) { $why = 'own-domain'; }
	}
	if ( '' === $why ) { continue; }
	$rejected++;
	$reasons[ $why ] = ( isset( $reasons[ $why ] ) ? $reasons[ $why ] : 0 ) + 1;
	if ( 'filter_var' === $why && preg_match( '/[^\x20-\x7E]/', $url ) ) {
		$nonascii++;
		/* آیا با تبدیل میزبان به punycode معتبر می‌شد؟ فقط شمارش، بی تغییر */
		if ( function_exists( 'idn_to_ascii' ) ) {
			$p = parse_url( $url );
			if ( ! empty( $p['host'] ) ) { $ascii = idn_to_ascii( $p['host'], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 ); if ( $ascii && filter_var( str_replace( $p['host'], $ascii, $url ), FILTER_VALIDATE_URL ) ) { $nonascii_idn++; } }
		}
	}
}

echo "official_event_url: غیرخالی={$total} · ردشده با قاعدهٔ اسکیما={$rejected} · ردشده به‌علت نویسهٔ غیر ASCII={$nonascii}" . ( $nonascii ? " (از آن‌ها با punycode معتبر می‌شد: {$nonascii_idn})" : '' ) . "\n";
echo 'علت‌ها: ' . json_encode( $reasons ) . "\n";
