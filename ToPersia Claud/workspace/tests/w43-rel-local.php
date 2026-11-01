<?php
/**
 * P5-D503 روی وردپرس محلی: هر رابطهٔ با یال، از هر سو، گروه نمایش یا دلیل مکتوب دارد؛ صفحهٔ نمونه گروه «مرتبط» را نشان می‌دهد،
 * صفحهٔ واقعی نه. فقط‌خواندنی.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/w43-rel-local.php
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }
require_once dirname( __DIR__ ) . '/scripts/lib/tp-rel-coverage-lib.php';
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };

$map  = topersia_g_relmap();
$gaps = tp_rel_cov_gaps( tp_rel_cov_rows( $map ) );
$t( 'هر رابطهٔ فعال با یال، از هر سو گروه یا دلیل دارد', ! $gaps, wp_json_encode( array_slice( $gaps, 0, 8 ) ) );

$mut = $map;
foreach ( $mut['tp_calendar']['related']['rels'] as $i => $r ) { if ( 231 === $r['id'] ) { unset( $mut['tp_calendar']['related']['rels'][ $i ] ); } }
$after = tp_rel_cov_gaps( tp_rel_cov_rows( $mut ) );
$t( 'جهش: برداشتن #231 از گروه تقویم شکاف می‌سازد', in_array( array( 231, 'tp_calendar', 'children' ), $after, true ), wp_json_encode( $after ) );

$rows = tp_rel_cov_rows( $map );
$t( '#231 و #244 در گروه «مرتبط» تقویم، از سوی آیین و رویداد هم', '' !== $rows[231]['gp'] && '' !== $rows[244]['gp'] && '' !== $rows[231]['gc'] && '' !== $rows[244]['gc'] );

$dirs_ok = true; $reg = topersia_g_relreg();
foreach ( $map as $type => $groups ) {
	foreach ( (array) ( $groups['related']['rels'] ?? array() ) as $r ) {
		$want = 'children' === $r['dir'] ? ( $reg[ $r['id'] ]['parent'] ?? '' ) : ( $reg[ $r['id'] ]['child'] ?? '' );
		if ( $want !== $type ) { $dirs_ok = false; echo "    جهت نادرست: {$type} #{$r['id']} {$r['dir']}\n"; }
	}
}
$t( 'جهت هر ردیف «مرتبط» با ثبت‌نامهٔ جت‌انجین می‌خواند', $dirs_ok );

/* رندر: یال نمونه روی نمونه، صفحهٔ واقعی بی باکس */
global $wpdb;
$sample_cal = (int) $wpdb->get_var( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->prefix}jet_rel_default r ON r.parent_object_id = p.ID AND r.rel_id = 244 JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = 'post_tp_calendar' AND t.language_code = 'fa' WHERE p.post_type = 'tp_calendar' AND p.post_status = 'publish' LIMIT 1" );
$get = function ( $path ) { return (string) wp_remote_retrieve_body( wp_remote_get( $path, array( 'timeout' => 120, 'sslverify' => false ) ) ); };
$html = $sample_cal ? $get( get_permalink( $sample_cal ) ) : '';
$t( 'تقویم نمونه باکس «مرتبط» را با کاشی نشان می‌دهد', false !== strpos( $html, 'id="tp-related"' ), "#{$sample_cal}" );
foreach ( array( '/city/isfahan/', '/province/isfahan/', '/monument/naqsh-e-jahan-square/', '/country/iran/', '/calendar/iran-official-calendar/' ) as $path ) {
	$h = $get( home_url( $path ) );
	$t( "صفحهٔ واقعی {$path} باکس «مرتبط» ندارد", '' !== $h && false === strpos( $h, 'id="tp-related"' ) );
}

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
