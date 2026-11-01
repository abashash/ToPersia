<?php
/**
 * canary: «پست نمونه به والد/فرزند واقعی وصل شد» — دستهٔ 39-ب · P5-D456. روی وردپرس محلی؛ داده‌ٔ آزمون را می‌سازد و پاک می‌کند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/canary-sample-edges-local.php
 *
 * کشور واقعی، استان نمونه و ارز نمونهٔ موقت: بی یال ← canary سالم؛ یال تک‌والد (۷۱: نمونه زیر والد واقعی) ← خطا با sample-real-new؛
 * برداشتنش (جهش) ← سالم؛ یال چندبه‌چند (۲۲۶) ← خطا؛ برداشتنش ← سالم.
 */
global $wpdb;
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

$tag  = 'آزمون canary ' . wp_generate_password( 4, false, false );
$mk   = function ( $type, $title, $sample ) { $id = (int) wp_insert_post( array( 'post_type' => $type, 'post_status' => 'draft', 'post_title' => $title ) ); update_post_meta( $id, '_tp_test_canary', 1 ); if ( $sample ) { update_post_meta( $id, '_tp_sample', 1 ); } return $id; };
$real = $mk( 'tp_country', $tag, false );
$sprv = $mk( 'tp_province', $tag . ' نمونه', true );
$scur = $mk( 'tp_currency', $tag . ' ارز نمونه', true );
$file = tempnam( sys_get_temp_dir(), 'tpcan' );
$run  = function ( $cmd ) use ( $file ) {
	$out = array(); $code = 0;
	exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( dirname( rtrim( ABSPATH, '/\\' ) ) . '/ToPersia Claud/workspace/tests/canary-posts.php' ) . ' ' . $cmd . ' ' . escapeshellarg( $file ) . ' 2>&1', $out, $code );
	return array( $code, implode( "\n", $out ) );
};
try {
	$run( 'save' );
	list( $c0 ) = $run( 'check' );
	$t( 'بی یال نمونه↔واقعی: canary سالم (۰)', 0 === $c0 );

	$r71 = topersia_relation( 71 ); $r71->update( $real, $sprv );
	list( $c1, $o1 ) = $run( 'check' );
	$t( 'نمونه زیر والد واقعی (۷۱ تک‌والد): canary خطا (۱) با sample-real-new', 1 === $c1 && false !== strpos( $o1, 'sample-real-new: 71|' . $real . '|' . $sprv ), substr( $o1, 0, 200 ) );
	$r71->delete_rows( $real, $sprv );
	list( $c2 ) = $run( 'check' );
	$t( 'جهش: یال برداشته شود ← canary دوباره سالم', 0 === $c2 );

	$r226 = topersia_relation( 226 ); $r226->update( $real, $scur );
	list( $c3, $o3 ) = $run( 'check' );
	$t( 'نمونه در چندبه‌چند به والد واقعی (۲۲۶): canary خطا (۱) با sample-real-new', 1 === $c3 && false !== strpos( $o3, 'sample-real-new: 226|' . $real . '|' . $scur ), substr( $o3, 0, 200 ) );
	$r226->delete_rows( $real, $scur );
	list( $c4 ) = $run( 'check' );
	$t( 'جهش: یال برداشته شود ← canary دوباره سالم', 0 === $c4 );
} finally {
	foreach ( array( 71 => array( $real, $sprv ), 226 => array( $real, $scur ) ) as $rid => $pair ) { $o = topersia_relation( $rid ); if ( $o ) { $o->delete_rows( $pair[0], $pair[1] ); } }
	foreach ( $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_tp_test_canary'" ) as $left ) { wp_delete_post( (int) $left, true ); }
	@unlink( $file );
	$t( 'پاک‌سازی: هیچ پستِ آزمون نمانده', 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_tp_test_canary'" ) );
}
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
