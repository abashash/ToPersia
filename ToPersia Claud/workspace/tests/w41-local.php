<?php
/**
 * دستهٔ 41 روی وردپرس محلی: بستهٔ محتوا برای ارز و روادید (P5-D492 · P5-D493)، هشدار ISO دوگانه و ماندگاری برق ایران.
 * داده‌ٔ آزمون را می‌سازد و پاک می‌کند؛ روی پست واقعی فقط «آزمایشی» (plan) که چیزی نمی‌نویسد.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/w41-local.php [مسیر فایل v10] [مسیر فایل v11]
 */
global $wpdb;
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };
$v10 = isset( $args[0] ) ? (string) $args[0] : 'C:/Users/AmirAbbas/Downloads/topersia-iran-country-fa-v10.json';
$v11 = isset( $args[1] ) ? (string) $args[1] : 'C:/Users/AmirAbbas/Downloads/topersia-iran-country-fa-v11.json';
$tag = 'w41-' . strtolower( wp_generate_password( 6, false, false ) );

$pack = function ( $type, $post ) {
	$tpl = topersia_pack_template( $type, 'fa' );
	return topersia_pack_parse( wp_json_encode( array( 'meta' => $tpl['meta'], 'posts' => array( $post ) ) ) );
};
$made = array();
$mk   = function ( $type, $title, $meta ) use ( &$made ) {
	$id = (int) wp_insert_post( array( 'post_type' => $type, 'post_status' => 'draft', 'post_title' => $title ) );
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_' . $type, 'trid' => false, 'language_code' => 'fa' ) );
	foreach ( $meta as $k => $v ) { update_post_meta( $id, $k, $v ); }
	update_post_meta( $id, '_tp_test_w41', 1 );
	$made[] = $id;
	return $id;
};

try {
	/* ── تاریخ بازبینی و منبع اجباری برای پست تازه (P5-D492) ── */
	$sc = topersia_pack_schema( 'tp_currency' );
	$t( 'طرح ارز: last_verified_date و source_references الزامی؛ در نوع‌های دیگر تاریخ همچنان سامانه‌ای',
		! empty( $sc['fields']['last_verified_date']['required'] ) && ! empty( $sc['fields']['source_references']['required'] )
		&& ! empty( topersia_pack_schema( 'tp_visa' )['fields']['last_verified_date']['required'] )
		&& isset( topersia_pack_schema( 'tp_city' )['excluded']['last_verified_date'] ) );

	$iso  = 'zz' . strtolower( substr( $tag, -3 ) );
	$base = array( 'entity_uid' => 'https://topersia.com/e/currency/' . $tag, 'slug' => $tag, 'language' => 'fa', 'title' => 'ارز ' . $tag );
	$req  = function ( $p ) { return array_keys( array_filter( (array) $p['fields'], function ( $r ) { return 'required' === ( $r['status'] ?? '' ); } ) ); };
	$p0   = topersia_pack_plan_post( $pack( 'tp_currency', $base + array( 'fields' => array( 'currency_iso_code' => $iso ) ) ), 0 );
	$p1   = topersia_pack_plan_post( $pack( 'tp_currency', $base + array( 'fields' => array( 'currency_iso_code' => $iso, 'last_verified_date' => '2026-10-20', 'source_references' => array( array( 'source_name' => 'منبع', 'source_url' => 'https://example.org/a', 'source_access_date' => '2026-10-20', 'source_row_status' => 'active' ) ) ) ) ), 0 );
	$t( 'پست تازهٔ ارز بی تاریخ و منبع «الزامی خالی» است؛ با هر دو این دو فیلد دیگر الزامی خالی نیستند — جهش: همان پرونده با دو فیلد',
		in_array( 'last_verified_date', $req( $p0 ), true ) && in_array( 'source_references', $req( $p0 ), true ) && ! array_intersect( array( 'last_verified_date', 'source_references' ), $req( $p1 ) ), wp_json_encode( array( $req( $p0 ), $req( $p1 ) ) ) );

	/* ── ISO دوگانه (غیرمسدودکننده) ── */
	$twin = $mk( 'tp_currency', 'ارز همزاد ' . $tag, array( 'currency_iso_code' => $iso ) );
	$has  = function ( $p, $needle ) { return (bool) array_filter( (array) $p['notes'], function ( $n ) use ( $needle ) { return false !== strpos( $n, $needle ); } ); };
	$p2   = topersia_pack_plan_post( $pack( 'tp_currency', $base + array( 'fields' => array( 'currency_iso_code' => $iso ) ) ), 0 );
	$t( 'هشدار «دو tp_currency با currency_iso_code یکسان» با شناسهٔ دیگری؛ بی همزاد هشدار نیست (پیش از ساختن همزاد)؛ مانع نیست',
		$has( $p2, 'دو tp_currency با currency_iso_code یکسان' ) && $has( $p2, (string) $twin ) && ! $has( $p0, 'دو tp_currency با currency_iso_code یکسان' ) && empty( $p2['problem'] ), wp_json_encode( $p2['notes'] ) );
	$t( '#204 و #1968: هر کدام دیگری را همزاد نشان می‌دهد (فقط‌خواندنی)',
		in_array( 1968, topersia_pack_currency_iso_twins( 'irr', 204 ), true ) && in_array( 204, topersia_pack_currency_iso_twins( 'irr', 1968 ), true ) );
	$own = topersia_pack_currency_iso_twins( $iso, $twin );
	$t( 'خودِ پست همزاد خودش نیست', ! in_array( $twin, $own, true ) );

	/* ── شباهت متنی (غیرمسدودکننده) ── */
	$para = implode( ' ', array_map( function ( $i ) { return 'واژه' . $i; }, range( 1, 30 ) ) );
	$sim  = topersia_pack_plan_post( $pack( 'tp_currency', $base + array( 'fields' => array( 'rate_regime_note' => $para, 'digital_payment_note' => $para . ' پایان' ) ) ), 0 );
	$dif  = topersia_pack_plan_post( $pack( 'tp_currency', $base + array( 'fields' => array( 'rate_regime_note' => $para, 'digital_payment_note' => implode( ' ', array_map( function ( $i ) { return 'دیگر' . $i; }, range( 1, 30 ) ) ) ) ) ), 0 );
	$sho  = topersia_pack_plan_post( $pack( 'tp_currency', $base + array( 'fields' => array( 'rate_regime_note' => 'متن کوتاه یکسان', 'digital_payment_note' => 'متن کوتاه یکسان' ) ) ), 0 );
	$t( 'شباهت بالا بین دو فیلد یک پست هشدار می‌دهد؛ متن متفاوت یا کوتاه نه؛ مسدود نمی‌کند — جهش: دو متن متفاوت',
		$has( $sim, 'هشدار شباهت' ) && ! $has( $dif, 'هشدار شباهت' ) && ! $has( $sho, 'هشدار شباهت' ) && empty( $sim['problem'] ), wp_json_encode( $sim['notes'] ) );

	/* ── برق ایران: v11 با کلیدها؛ v10 بی کلید چیزی را پاک نمی‌کند ── */
	$d10 = is_readable( $v10 ) ? json_decode( (string) file_get_contents( $v10 ), true ) : null;
	$d11 = is_readable( $v11 ) ? json_decode( (string) file_get_contents( $v11 ), true ) : null;
	$t( 'v11 کلیدهای برق را در طرح (fields) و پست ایران دارد؛ v10 ندارد و به آن دست نخورده',
		is_array( $d11 ) && is_array( $d10 ) && isset( $d11['posts'][0]['fields']['electric_voltage'], $d11['posts'][0]['fields']['electric_frequency'], $d11['fields']['electric_voltage'], $d11['fields']['electric_frequency'] )
		&& ! isset( $d10['posts'][0]['fields']['electric_voltage'] ) && ! isset( $d10['fields']['electric_voltage'] ) );

	$c   = $mk( 'tp_country', 'کشور ' . $tag, array( 'entity_uid' => 'https://topersia.com/e/country/' . $tag, 'electric_voltage' => '230', 'electric_frequency' => '50', 'name_fa' => 'کشور ' . $tag ) );
	$cp  = array( 'entity_uid' => 'https://topersia.com/e/country/' . $tag, 'slug' => get_post_field( 'post_name', $c ) ?: $tag, 'language' => 'fa', 'title' => 'کشور ' . $tag );
	$run = 'w41-' . $tag;
	topersia_pack_apply( $pack( 'tp_country', $cp + array( 'fields' => array( 'name_en' => 'Country ' . $tag ) ) ), 0, $run );
	$t( 'به‌روزرسانی با پرونده‌ای بی کلید برق: ولتاژ و بسامد ذخیره‌شده می‌ماند و بقیهٔ فیلد نوشته می‌شود',
		'230' === (string) get_post_meta( $c, 'electric_voltage', true ) && '50' === (string) get_post_meta( $c, 'electric_frequency', true ) && 'Country ' . $tag === get_post_meta( $c, 'name_en', true ) );
	topersia_pack_apply( $pack( 'tp_country', $cp + array( 'fields' => array( 'electric_voltage' => 110 ) ) ), 0, $run . 'b' );
	$t( 'جهش: پرونده‌ای با کلید ولتاژ مقدار را عوض می‌کند (پس آزمون بالا بی‌معنا نیست)', 110 === (int) get_post_meta( $c, 'electric_voltage', true ) );

	if ( is_array( $d11 ) ) {
		$pl = topersia_pack_plan_post( topersia_pack_parse( wp_json_encode( $d11 ) ), 0 );
		$t( 'آزمایشی v11 روی ایران واقعی (بی نوشتن): به‌روزرسانی، و هر دو کلید برق شناخته‌شده در نقشهٔ طرح',
			'update' === $pl['action'] && isset( $pl['fields']['electric_voltage'], $pl['fields']['electric_frequency'] ), wp_json_encode( array( $pl['action'], $pl['problem'] ?? '' ) ) );
	}
} finally {
	foreach ( $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_tp_test_w41'" ) as $left ) { wp_delete_post( (int) $left, true ); }
	$t( 'پاک‌سازی: هیچ پست آزمون نمانده', 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_tp_test_w41'" ) );
}
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
