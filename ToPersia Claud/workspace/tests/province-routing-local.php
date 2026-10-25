<?php
/**
 * مسیریابی صفحهٔ استان — P5-D399 (دستهٔ ۲۶ بند ۱۱ · F-06). روی وردپرس محلی؛ فقط می‌خواند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/province-routing-local.php
 *
 * نقطهٔ مسیریابی هر استان: رابطهٔ مرکز (مختصات شهر مرکز) ← وگرنه، فقط پس از نوشتن P5-D399
 * (گزینهٔ topersia_p5d399_applied)، مختصات خودِ استان ← وگرنه بی نقطه. پیش از تأیید کاربر استان‌های بی رابطهٔ
 * مرکز «در انتظار نوشتن» شمرده می‌شوند، نه افتاده. جهش: همان استان با گزینهٔ «نوشته‌شده» نقطهٔ خودش را
 * می‌گیرد و بی آن نه. پس از capitals-apply همین آزمون بی تغییر باید سبز بماند و «در انتظار» صفر شود.
 */

$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) {
	$ok ? $pass++ : $fail++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
$pt = function ( $pid ) { return topersia_geo_point( topersia_g_text( $pid, 'geo_coordinates' ) ); };
$same = function ( $a, $b ) { return $a && $b && abs( $a['lat'] - $b['lat'] ) < 1e-6 && abs( $a['lng'] - $b['lng'] ) < 1e-6; };

$applied = array_map( 'intval', (array) get_option( 'topersia_p5d399_applied', array() ) );
$bad = array(); $pending = array(); $n = 0; $probe = 0;
foreach ( get_posts( array( 'post_type' => 'tp_province', 'numberposts' => -1, 'post_status' => 'publish', 'suppress_filters' => false, 'fields' => 'ids' ) ) as $pid ) {
	$n++;
	$cap  = absint( topersia_g_capital_id( $pid ) );
	$want = $cap ? $pt( $cap ) : ( in_array( $pid, $applied, true ) ? $pt( $pid ) : null );
	$got  = topersia_g_routing_point( $pid );
	if ( $want ? ! $same( $want, $got ) : array() !== $got ) { $bad[] = get_the_title( $pid ); }
	if ( ! $cap && ! in_array( $pid, $applied, true ) ) { $pending[] = get_the_title( $pid ); $probe = $probe ?: $pid; }
}
$t( 'هر استان: نقطهٔ مسیریابی = مرکز استان (رابطه) یا، پس از نوشتن P5-D399، مختصات خودِ استان؛ پیش از آن هیچ — ' . $n . ' استان', ! $bad, implode( '، ', $bad ) );

if ( $probe ) {
	$on = function () use ( $probe ) { return array( $probe ); };
	add_filter( 'pre_option_topersia_p5d399_applied', $on );
	$after = topersia_g_routing_point( $probe );
	remove_filter( 'pre_option_topersia_p5d399_applied', $on );
	$before = topersia_g_routing_point( $probe );
	$t( 'جهش: ' . get_the_title( $probe ) . ' (بی رابطهٔ مرکز) با «نوشته‌شده» نقطهٔ خودش را می‌گیرد، بی آن هیچ', $same( $after, $pt( $probe ) ) && array() === $before, wp_json_encode( array( $after, $before ) ) );
}

echo '  · در انتظار نوشتن P5-D399 (بی رابطهٔ مرکز و نوشته‌نشده): ' . count( $pending ) . ( $pending ? ' — ' . implode( '، ', array_slice( $pending, 0, 8 ) ) . ( count( $pending ) > 8 ? ' …' : '' ) : '' ) . "\n";
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
