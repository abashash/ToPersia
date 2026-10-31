<?php
/**
 * مسیریابی روی وردپرس محلی — دستهٔ 39 · P5-D462. فقط می‌خواند (رندر درون‌فرایندی).
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/routing-local.php
 * بنا (نقطه‌ای) و شهر: فقط نشان · گوگل · ویز با https و target=_blank rel=noopener؛ بلد نه (حتی اگر در سرویس روشن باشد)؛ استان و کشور: جعبه نه.
 */
global $wpdb;
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };
$pick = function ( $type ) use ( $wpdb ) { return (int) $wpdb->get_var( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_tp_sample' WHERE p.post_type=%s AND p.post_status='publish' AND s.post_id IS NULL ORDER BY p.ID LIMIT 1", $type ) ); };
$svc = topersia_api( 'navigation' );
$t( 'سرویس navigation روشن و دارای ارائه‌دهنده', is_array( $svc ) && ! empty( $svc['enabled'] ) && ! empty( $svc['extra'] ) );
$has_balad = is_array( $svc ) && ! empty( $svc['extra']['balad']['on'] );
echo '  · بلد در تنظیمات سرویس: ' . ( $has_balad ? 'روشن (آزمونِ حذف معنادار است)' : 'خاموش' ) . "\n";
foreach ( array( 'tp_monument' => 'بنا', 'tp_city' => 'شهر' ) as $type => $name ) {
	$id = $pick( $type );
	if ( ! $id ) { echo "  · $name: پست واقعی نیست — رد شد\n"; continue; }
	$h = do_shortcode( '[tp_routing id="' . $id . '"]' );
	preg_match_all( '#<a class="tp-tile" href="([^"]+)" target="_blank" rel="noopener nofollow" data-tp-nav="([a-z]+)"#', $h, $m );
	$keys = $m[2];
	$t( "$name #$id: فقط ارائه‌دهنده‌های نشان/گوگل/ویز (بلد نه)", $keys && ! array_diff( $keys, array( 'google', 'waze', 'neshan' ) ) && ! in_array( 'balad', $keys, true ), implode( ',', $keys ) );
	$t( "$name #$id: همهٔ لینک‌ها https و rel=noopener", $m[1] && ! array_filter( $m[1], function ( $u ) { return 0 !== strpos( html_entity_decode( $u ), 'https://' ); } ) && substr_count( $h, 'rel="noopener' ) === count( $keys ) );
}
foreach ( array( 'tp_province' => 'استان', 'tp_country' => 'کشور' ) as $type => $name ) {
	$id = $pick( $type );
	if ( $id ) { $t( "$name #$id: جعبهٔ مسیریابی نمی‌آید (ناحیهٔ tp-routing خاموش یا شورت‌کد خالی)", ! topersia_g_zone_on( $id, 'tp-routing' ) || '' === do_shortcode( '[tp_routing id="' . $id . '"]' ) ); }
}
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
