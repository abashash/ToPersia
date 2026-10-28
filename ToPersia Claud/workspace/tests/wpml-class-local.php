<?php
/**
 * دستهٔ 32 بند 4 — هر فیلد هر ۴۱ نوع کلاس WPML دارد (copy · translate · ignore). روی وردپرس محلی؛ فقط می‌خواند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/wpml-class-local.php
 *
 * منبع: اسکیمای زندهٔ بسته (topersia_pack_schema) در برابر project-knowledge/ToPersia-wpml-config-v1_13.xml و گزینهٔ
 * wpml-tm-custom-xml. فیلد بی کلاس = WPML آن را ترجمه/کپی نمی‌کند و دروازهٔ زبان (P5-D278) خانهٔ سربرگ را «—» می‌کند.
 * جهش: با برداشتن یک کلید از XML آزمون باید بیفتد.
 */
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) {
	$ok ? $pass++ : $fail++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
$classes = function ( $xml ) { preg_match_all( '#<custom-field action="(copy|translate|ignore)">([^<]+)</custom-field>#', (string) $xml, $m ); return array_combine( $m[2], $m[1] ); };
$file = $classes( file_get_contents( dirname( ABSPATH ) . '/ToPersia Claud/project-knowledge/ToPersia-wpml-config-v1_13.xml' ) );
$opt  = $classes( get_option( 'wpml-tm-custom-xml', '' ) );

$types = array_values( array_filter( get_post_types( array(), 'names' ), function ( $x ) { return 0 === strpos( $x, 'tp_' ) || 'tpc_news' === $x; } ) );
$keys  = array();
foreach ( $types as $ty ) {
	foreach ( array_keys( (array) ( topersia_pack_schema( $ty )['fields'] ?? array() ) ) as $k ) { $keys[ $k ] = 1; }
}
$keys = array_keys( $keys );
$none_f = array_values( array_diff( $keys, array_keys( $file ) ) );
$none_o = array_values( array_diff( $keys, array_keys( $opt ) ) );
$t( 'هر فیلد اسکیمای ' . count( $types ) . ' نوع (' . count( $keys ) . ' کلید) کلاس WPML در پرونده دارد', ! $none_f, implode( ' ', $none_f ) );
$t( 'همان در گزینهٔ wpml-tm-custom-xml نسخهٔ محلی (پرونده و گزینه یکی)', ! $none_o && $file == $opt, implode( ' ', $none_o ) );

$mut = $file; unset( $mut[ $keys[0] ] );
$t( 'جهش: برداشتن یک کلید از XML آزمون را می‌اندازد', (bool) array_diff( $keys, array_keys( $mut ) ) );

/* قاعدهٔ کلاس: متن خوانا (text/textarea/wysiwyg) ترجمه یا کپی دارد، هرگز بی کلاس؛ select/number/switcher/date کپی */
$bad = array();
foreach ( $types as $ty ) {
	foreach ( (array) ( topersia_pack_schema( $ty )['fields'] ?? array() ) as $k => $f ) {
		if ( in_array( $f['type'] ?? '', array( 'select', 'number', 'switcher', 'date', 'checkbox' ), true ) && 'translate' === ( $file[ $k ] ?? '' ) ) { $bad[ $k ] = $f['type']; }
	}
}
$t( 'فیلد انتخابی/عددی/سوییچ/تاریخ هرگز translate نیست', ! $bad, wp_json_encode( $bad ) );
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
