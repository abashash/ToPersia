<?php
/**
 * دستهٔ 43 روی وردپرس محلی: اجبار بسته برای جشنواره و منطقهٔ حفاظت‌شده، و خروجی واقعی نمونه‌ها (P5-D485).
 * پست نمی‌سازد؛ فقط «آزمایشی» (plan) که چیزی نمی‌نویسد.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/w43-local.php
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };
$tag = 'w43-' . strtolower( wp_generate_password( 6, false, false ) );

$pack = function ( $type, $post ) {
	$tpl = topersia_pack_template( $type, 'fa' );
	return topersia_pack_parse( wp_json_encode( array( 'meta' => $tpl['meta'], 'posts' => array( $post ) ) ) );
};
$req = function ( $p ) { return array_keys( array_filter( (array) $p['fields'], function ( $r ) { return 'required' === ( $r['status'] ?? '' ); } ) ); };
$src = array( array( 'source_name' => 'منبع', 'source_url' => 'https://example.org/a', 'source_access_date' => '2026-10-20', 'source_row_status' => 'active' ) );

foreach ( array( 'tp_festival' => 'festival', 'tp_protected_area' => 'protected-area' ) as $type => $seg ) {
	$sc = topersia_pack_schema( $type );
	$t( "{$type}: طرح last_verified_date و source_references را الزامی می‌داند", ! empty( $sc['fields']['last_verified_date']['required'] ) && ! empty( $sc['fields']['source_references']['required'] ) );
	$base = array( 'entity_uid' => topersia_uid_build( $seg . '/' . $tag ), 'slug' => $tag, 'language' => 'fa', 'title' => 'آزمون ' . $tag );
	$p0   = topersia_pack_plan_post( $pack( $type, $base + array( 'fields' => array() ) ), 0 );
	$p1   = topersia_pack_plan_post( $pack( $type, $base + array( 'fields' => array( 'last_verified_date' => '2026-10-20', 'source_references' => $src ) ) ), 0 );
	$t( "{$type}: پست تازه بی تاریخ و منبع «الزامی خالی»؛ با هر دو نه — جهش: همان پرونده با دو فیلد",
		in_array( 'last_verified_date', $req( $p0 ), true ) && in_array( 'source_references', $req( $p0 ), true ) && ! array_intersect( array( 'last_verified_date', 'source_references' ), $req( $p1 ) ), wp_json_encode( array( $req( $p0 ), $req( $p1 ) ) ) );
}
$t( 'نوع دیگر (رویداد ورزشی، دستهٔ 44): تاریخ بازبینی همچنان سامانه‌ای', isset( topersia_pack_schema( 'tp_sports_event' )['excluded']['last_verified_date'] ) );

/* نمونه‌ها: پر، خالی، و خروجی نقشه روی پست واقعی */
$map = array_filter( topersia_g_map(), function ( $f ) { return ! empty( $f['wave2b'] ); } );
$sample = function ( $type, $empty ) {
	$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true, 'meta_key' => '_tp_sample', 'meta_value' => 1 ) );
	foreach ( $ids as $id ) {
		if ( $empty === (bool) get_post_meta( $id, '_tp_sample_empty', true ) && 'fa' === apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'post_' . $type ) ) ) { return (int) $id; }
	}
	return 0;
};
$fields = array( 'tp_festival' => array( 'program_highlights', 'event_travel_impact', 'admission_note', 'tourist_suitability_note' ), 'tp_protected_area' => array( 'managed_area', 'entry_permit_required', 'key_species', 'geopark', 'activities' ) );
foreach ( $fields as $type => $keys ) {
	$full  = $sample( $type, false );
	$empty = $sample( $type, true );
	$t( "{$type}: نمونهٔ کامل و نمونهٔ خالی هست", $full && $empty, "{$full} · {$empty}" );
	foreach ( $keys as $k ) {
		$t( "{$type}: {$k} در نمونهٔ کامل مقدار دارد", '' !== topersia_g_text( $full, $k ) || array() !== topersia_g_rows( $full, $k ), (string) $full );
		$t( "{$type}: {$k} در نمونهٔ خالی نیست", '' === (string) get_post_meta( $empty, $k, true ) );
	}
}
$prot = $sample( 'tp_protected_area', false );
$t( 'بلوک بازدید نمونهٔ حفاظت‌شده مجوز ورود را با برچسب نقشه دارد', false !== strpos( topersia_g_visit_block( $prot ), $map['entry_permit_required']['label'] ) );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
