<?php
/**
 * دستهٔ 42 روی وردپرس محلی: اجبار بسته برای سلامت، حمل‌ونقل و تقویم (P5-D498)، هشدار uid محلی (P5-D495)، ver هسته (P5-D496).
 * پست نمی‌سازد؛ فقط «آزمایشی» (plan) که چیزی نمی‌نویسد.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/w42-local.php
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };
$tag = 'w42-' . strtolower( wp_generate_password( 6, false, false ) );

$pack = function ( $type, $post ) {
	$tpl = topersia_pack_template( $type, 'fa' );
	return topersia_pack_parse( wp_json_encode( array( 'meta' => $tpl['meta'], 'posts' => array( $post ) ) ) );
};
$req = function ( $p ) { return array_keys( array_filter( (array) $p['fields'], function ( $r ) { return 'required' === ( $r['status'] ?? '' ); } ) ); };
$has = function ( $p, $needle ) { return (bool) array_filter( (array) $p['notes'], function ( $n ) use ( $needle ) { return false !== strpos( $n, $needle ); } ); };
$src = array( array( 'source_name' => 'منبع', 'source_url' => 'https://example.org/a', 'source_access_date' => '2026-10-20', 'source_row_status' => 'active' ) );

foreach ( array( 'tp_health_safety' => 'health-safety', 'tp_transport' => 'transport', 'tp_calendar' => 'calendar' ) as $type => $seg ) {
	$sc = topersia_pack_schema( $type );
	$t( "{$type}: طرح last_verified_date و source_references را الزامی می‌داند", ! empty( $sc['fields']['last_verified_date']['required'] ) && ! empty( $sc['fields']['source_references']['required'] ) );
	$base = array( 'entity_uid' => 'https://topersia.com/e/' . $seg . '/' . $tag, 'slug' => $tag, 'language' => 'fa', 'title' => 'آزمون ' . $tag );
	$p0   = topersia_pack_plan_post( $pack( $type, $base + array( 'fields' => array() ) ), 0 );
	$p1   = topersia_pack_plan_post( $pack( $type, $base + array( 'fields' => array( 'last_verified_date' => '2026-10-20', 'source_references' => $src ) ) ), 0 );
	$t( "{$type}: پست تازه بی تاریخ و منبع «الزامی خالی»؛ با هر دو نه — جهش: همان پرونده با دو فیلد",
		in_array( 'last_verified_date', $req( $p0 ), true ) && in_array( 'source_references', $req( $p0 ), true ) && ! array_intersect( array( 'last_verified_date', 'source_references' ), $req( $p1 ) ), wp_json_encode( array( $req( $p0 ), $req( $p1 ) ) ) );
	$t( "{$type}: هیچ رابطهٔ نمونه↔واقعی از این طرح ساخته نمی‌شود (پرونده بی relation)", empty( $p1['relations'] ) );
}
$t( 'نوع دیگر (شهر): تاریخ بازبینی همچنان سامانه‌ای', isset( topersia_pack_schema( 'tp_city' )['excluded']['last_verified_date'] ) );

/* P5-D495 */
$base = array( 'slug' => $tag, 'language' => 'fa', 'title' => 'آزمون ' . $tag, 'fields' => array() );
$lo   = topersia_pack_plan_post( $pack( 'tp_transport', $base + array( 'entity_uid' => 'http://topersia.local/e/transport/' . $tag ) ), 0 );
$co   = topersia_pack_plan_post( $pack( 'tp_transport', $base + array( 'entity_uid' => 'https://topersia.com/e/transport/' . $tag ) ), 0 );
$t( 'هشدار «پست غیرنمونه با entity_uid محلی» برای uid محلی، نه برای topersia.com، و غیرمسدودکننده — جهش: uid دامنهٔ اصلی',
	$has( $lo, 'پست غیرنمونه با entity_uid محلی' ) && ! $has( $co, 'پست غیرنمونه با entity_uid محلی' ) && empty( $lo['problem'] ), wp_json_encode( $lo['notes'] ) );
$t( 'تشخیص میزبان: محلی، localhost و .test بله؛ دامنهٔ اصلی و خالی نه',
	topersia_pack_uid_is_local( 'http://topersia.local/e/x' ) && topersia_pack_uid_is_local( 'http://localhost/e/x' ) && topersia_pack_uid_is_local( 'https://a.test/e/x' ) && ! topersia_pack_uid_is_local( 'https://topersia.com/e/x' ) && ! topersia_pack_uid_is_local( '' ) );

/* P5-D496 — نسخهٔ واقعی هسته */
$dir = trailingslashit( get_temp_dir() ) . $tag;
wp_mkdir_p( $dir );
try {
	file_put_contents( "{$dir}/a.css", 'body{}' );
	$v1 = topersia_asset_hash_ver( "{$dir}/a.css" );
	copy( "{$dir}/a.css", "{$dir}/b.css" ); touch( "{$dir}/b.css", time() + 9000 );
	$v2 = topersia_asset_hash_ver( "{$dir}/b.css" );
	file_put_contents( "{$dir}/c.css", 'body{ }' );
	$v3 = topersia_asset_hash_ver( "{$dir}/c.css" );
	$t( 'هسته: همان محتوا با mtime دیگر همان ver؛ یک بایت دیگر ver دیگر؛ بی فایل نسخهٔ هسته',
		$v1 === $v2 && $v1 !== $v3 && 10 === strlen( $v1 ) && TOPERSIA_CORE_VERSION === topersia_asset_hash_ver( "{$dir}/none.css" ) );
} finally {
	array_map( 'unlink', (array) glob( "{$dir}/*" ) );
	@rmdir( $dir );
}

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
