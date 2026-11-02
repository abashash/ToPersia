<?php
/**
 * P5-D499 روی وردپرس محلی: تطبیق uid فقط با مسیر، در هر دو جهت؛ @id با مبدأ ثابت؛ پس از مهاجرت هیچ uid محلی نمی‌ماند.
 * پست نمی‌سازد و چیزی نمی‌نویسد؛ درون‌ریزی فقط «آزمایشی» (plan).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/w43-uid-local.php [مسیر v10] [مسیر v11]
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }
global $wpdb;
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };

$v10 = isset( $args[0] ) ? (string) $args[0] : 'C:/Users/AmirAbbas/Downloads/topersia-iran-country-fa-v10.json';
$v11 = isset( $args[1] ) ? (string) $args[1] : 'C:/Users/AmirAbbas/Downloads/topersia-iran-country-fa-v11.json';

/* پس از مهاجرت */
$local = 0;
foreach ( $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'entity_uid' AND meta_value <> ''" ) as $v ) {
	$host = strtolower( (string) wp_parse_url( $v, PHP_URL_HOST ) );
	if ( parse_url( TOPERSIA_CANONICAL_ORIGIN, PHP_URL_HOST ) !== $host ) { $local++; }
}
$t( 'همهٔ entity_uid ها میزبان مبدأ ثابت دارند', 0 === $local, "دیگر: {$local}" );

/* یافتن با مسیر، هر دو جهت */
$iran = $wpdb->get_var( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'entity_uid' WHERE p.post_type = 'tp_country' AND p.post_name = 'iran' AND m.meta_value LIKE '%/e/country/iran' ORDER BY p.ID LIMIT 1" );
$iran = (int) $iran;
$find = function ( $uid ) { return topersia_pack_find( 'tp_country', $uid, '', 'fa' ); };
$t( 'پست ایران پیدا شد', $iran > 0 );
$t( 'پست موجود با میزبان محلی و با مبدأ ثابت و با مسیر خالی همان است — جهش: مسیر دیگر پیدا نمی‌شود',
	array( $iran ) === $find( 'http://topersia.local/e/country/iran' ) && array( $iran ) === $find( 'https://topersia.com/e/country/iran' ) && array( $iran ) === $find( 'https://staging.example.org/e/country/iran/' ) && array( $iran ) === $find( 'country/iran' ) && array() === $find( 'http://topersia.local/e/country/iran-x' ) && array() === $find( 'http://topersia.local/e/country/ira' ) );
$t( 'نوع نادرست پیدا نمی‌شود، و نشانی بیرون از /e/ پیدا نمی‌شود', array() === topersia_pack_find( 'tp_province', 'https://topersia.com/e/country/iran', '', 'fa' ) && array() === $find( 'https://topersia.com/id/country/iran' ) );

/* uid دو جهت با فایل ایران v10 و v11: آزمایشی، بی نوشتن */
$before = (string) get_post_field( 'post_modified_gmt', $iran );
foreach ( array( 'v10' => $v10, 'v11' => $v11 ) as $name => $file ) {
	$d = is_readable( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
	if ( ! is_array( $d ) || empty( $d['posts'][0] ) ) { $t( "{$name}: پرونده خوانده شد", false, $file ); continue; }
	foreach ( array( 'http://topersia.local/e/country/iran', 'https://topersia.com/e/country/iran' ) as $uid ) {
		$raw = $d;
		$raw['posts'][0]['entity_uid'] = $uid;
		$raw['posts'][0]['slug']       = 'no-such-slug-w43';   /* نامک عمداً نادرست: فقط uid باید پیدا کند */
		$plan = topersia_pack_plan_post( topersia_pack_parse( wp_json_encode( $raw ) ), 0 );
		$t( "{$name} با {$uid}: پست موجود را می‌یابد، نمی‌سازد — جهش: مسیر نادرست ساخت تازه است", (int) ( $plan['target'] ?? 0 ) === $iran, wp_json_encode( array_intersect_key( $plan, array_flip( array( 'action', 'target', 'problem' ) ) ), JSON_UNESCAPED_UNICODE ) );
	}
	$raw = $d;
	$raw['posts'][0]['entity_uid'] = 'https://topersia.com/e/country/no-such-w43';
	$raw['posts'][0]['slug']       = 'no-such-slug-w43';
	$plan = topersia_pack_plan_post( topersia_pack_parse( wp_json_encode( $raw ) ), 0 );
	$t( "{$name} جهش: مسیر و نامک ناموجود پست ایران را نمی‌یابد", $iran !== (int) ( $plan['target'] ?? 0 ) );
}
clean_post_cache( $iran );
$t( 'آزمایشی چیزی روی ایران واقعی ننوشت', $before === (string) get_post_field( 'post_modified_gmt', $iran ) );

/* @id */
$force = function ( $uid ) use ( $iran ) {
	$cb = function ( $v, $id, $key ) use ( $iran, $uid ) { return ( (int) $id === $iran && 'entity_uid' === $key ) ? array( $uid ) : $v; };
	add_filter( 'get_post_metadata', $cb, 10, 3 );
	$out = topersia_schema_uid( $iran );
	remove_filter( 'get_post_metadata', $cb, 10 );
	return $out;
};
$t( '@id از uid محلی و از uid مبدأ ثابت یکی است؛ مسیر دست نخورده — جهش: مسیر دیگر @id دیگر',
	'https://topersia.com/e/country/iran' === $force( 'http://topersia.local/e/country/iran' ) && 'https://topersia.com/e/country/iran' === $force( 'https://topersia.com/e/country/iran' ) && 'https://topersia.com/e/country/other' === $force( 'http://topersia.local/e/country/other' ) );
$t( 'نشانی بیرون از /e/ در @id دست نمی‌خورد', 'https://example.org/id/x' === $force( 'https://example.org/id/x' ) );

$html = wp_remote_retrieve_body( wp_remote_get( home_url( '/province/isfahan/' ), array( 'timeout' => 120, 'sslverify' => false ) ) );
$t( 'JSON-LD صفحهٔ محلی @id با topersia.com می‌دهد و هیچ @id با /e/ محلی ندارد',
	false !== strpos( $html, '"@id":"https:\/\/topersia.com\/e\/province\/isfahan"' ) || false !== strpos( $html, '"@id": "https://topersia.com/e/province/isfahan"' ) || false !== strpos( $html, '"https:\/\/topersia.com\/e\/province\/isfahan"' ) );
$t( 'هیچ نشانی /e/ با میزبان محلی در HTML صفحه نیست', ! preg_match( '~topersia\.local(\\\\?/)e\\\\?/~', $html ) );

/* تکراری پروندهٔ ترجمه بی میزبان */
$tp = array( 'meta' => array( 'schema' => TOPERSIA_PACK_TR_SCHEMA, 'post_type' => 'tp_city', 'language' => 'en' ), 'posts' => array( array( 'translation_of' => 'http://topersia.local/e/city/x', 'title' => 'a' ), array( 'translation_of' => 'https://topersia.com/e/city/x', 'title' => 'b' ) ) );
$tr = topersia_pack_tr_parse( wp_json_encode( $tp ) );
$t( 'پروندهٔ ترجمه: دو میزبان یک مسیر تکراری است', is_array( $tr ) && isset( $tr['posts'][1]['_dup_of'] ), wp_json_encode( is_wp_error( $tr ) ? $tr->get_error_message() : $tr ) );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
