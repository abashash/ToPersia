<?php
/**
 * canary: «پست نمونه به پست واقعی یال دارد» — P5-D456 · مطلق از P5-D488. روی وردپرس محلی؛ داده‌ٔ آزمون را می‌سازد و پاک می‌کند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/canary-sample-edges-local.php
 *
 * بی یال ← سالم. هر یال نمونه↔واقعی ← خطا با sample-real-edges، هر نوع رابطه، هر سو، و حتی اگر پیش از عکس `save` بوده؛
 * برداشتنش (جهش) ← سالم. یال نمونه↔نمونه ← سالم.
 */
global $wpdb;
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

$tag  = 'آزمون canary ' . wp_generate_password( 4, false, false );
$mk   = function ( $type, $title, $sample ) { $id = (int) wp_insert_post( array( 'post_type' => $type, 'post_status' => 'draft', 'post_title' => $title ) ); update_post_meta( $id, '_tp_test_canary', 1 ); if ( $sample ) { update_post_meta( $id, '_tp_sample', 1 ); } return $id; };
$real  = $mk( 'tp_country', $tag, false );
$rprv  = $mk( 'tp_province', $tag . ' استان واقعی', false );
$scnt  = $mk( 'tp_country', $tag . ' کشور نمونه', true );
$sprv  = $mk( 'tp_province', $tag . ' نمونه', true );
$scur  = $mk( 'tp_currency', $tag . ' ارز نمونه', true );
$file = tempnam( sys_get_temp_dir(), 'tpcan' );
$run  = function ( $cmd ) use ( $file ) {
	$out = array(); $code = 0;
	exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( rtrim( ABSPATH, '/\\' ) ) . '/ToPersia Claud/workspace/tests/canary-posts.php' ) . ' ' . $cmd . ' ' . escapeshellarg( $file ) . ' 2>&1', $out, $code );
	return array( $code, implode( "\n", $out ) );
};
$edge = function ( $rid, $p, $c, $label ) use ( $run, $t ) {
	$r = topersia_relation( $rid ); $r->update( $p, $c );
	list( $c1, $o1 ) = $run( 'check' );
	$t( $label . ': canary خطا (۱) با sample-real-edges', 1 === $c1 && false !== strpos( $o1, 'sample-real-edges: ' ) && false !== strpos( $o1, $rid . ':' . $p . '→' . $c ), substr( $o1, 0, 240 ) );
	$r->delete_rows( $p, $c );
	list( $c2 ) = $run( 'check' );
	$t( 'جهش: همان یال برداشته شود ← canary دوباره سالم', 0 === $c2 );
};
try {
	$run( 'save' );
	list( $c0 ) = $run( 'check' );
	$t( 'بی یال نمونه↔واقعی: canary سالم (۰)', 0 === $c0 );

	$edge( 71, $real, $sprv, 'یک‌به‌چند با فرزند نمونه زیر والد واقعی (۷۱؛ قاعدهٔ پیشین فقط یال تازه را می‌گرفت)' );
	$edge( 71, $scnt, $rprv, 'یک‌به‌چند با والد نمونه و فرزند واقعی (۷۱)' );
	$edge( 226, $real, $scur, 'چندبه‌چند با فرزند نمونه (۲۲۶؛ قاعدهٔ P5-D456 پیشین این نوع را مجاز می‌شمرد)' );

	/* مطلق: یالی که پیش از عکس بوده هم می‌افتد — همان چیزی که با مقایسه با عکس می‌گذشت. */
	$r71 = topersia_relation( 71 ); $r71->update( $real, $sprv );
	$run( 'save' );
	list( $c5, $o5 ) = $run( 'check' );
	$t( 'یال موجود پیش از عکس save: باز خطا (بی مقایسه با عکس)', 1 === $c5 && false !== strpos( $o5, '71:' . $real . '→' . $sprv ), substr( $o5, 0, 240 ) );
	$r71->delete_rows( $real, $sprv );
	$run( 'save' );

	$r71->update( $scnt, $sprv );
	list( $c6, $o6 ) = $run( 'check' );
	$t( 'یال نمونه↔نمونه مجاز است: canary سالم', 0 === $c6, substr( $o6, 0, 240 ) );
	$r71->delete_rows( $scnt, $sprv );
} finally {
	foreach ( array( array( 71, $real, $sprv ), array( 71, $scnt, $rprv ), array( 71, $scnt, $sprv ), array( 226, $real, $scur ) ) as $e ) { $o = topersia_relation( $e[0] ); if ( $o ) { $o->delete_rows( $e[1], $e[2] ); } }
	foreach ( $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_tp_test_canary'" ) as $left ) { wp_delete_post( (int) $left, true ); }
	@unlink( $file );
	$t( 'پاک‌سازی: هیچ پستِ آزمون نمانده', 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_tp_test_canary'" ) );
}
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
