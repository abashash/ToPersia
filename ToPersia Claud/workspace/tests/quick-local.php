<?php
/**
 * «پاسخ کوتاه» روادید و ارز روی وردپرس محلی — دستهٔ 33 · P5-D454. فقط می‌خواند (GET).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/quick-local.php
 *
 * روادید (نمونه‌ها، چون پست واقعی روادید هنوز نیست) و ارز (واقعی و نمونه) در fa · en · ar: ۲۰۰؛ بخش tp-quick با سه
 * پرسش dt/dd؛ پاسخ‌ها از دادهٔ همان پست (هیچ مقدار ساختگی)؛ نبود ← «—» با aria-label «نامشخص»؛ en و ar بی متن فارسی
 * در پاسخ‌ها و پرسش‌ها؛ نخستین بخش ستون اصلی؛ صفحهٔ شهر بی بخش.
 */
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) {
	$ok ? $pass++ : $fail++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
$url = function ( $id, $l ) { do_action( 'wpml_switch_language', $l ); $u = get_permalink( $id ); do_action( 'wpml_switch_language', 'fa' ); return $u; };
$get = function ( $u ) {
	$r = wp_remote_get( $u, array( 'timeout' => 90, 'sslverify' => false, 'proxy' => '' ) );
	return is_wp_error( $r ) ? array( 0, '' ) : array( (int) wp_remote_retrieve_response_code( $r ), (string) wp_remote_retrieve_body( $r ) );
};
global $wpdb;
$pick = function ( $type, $sample ) use ( $wpdb ) {
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_tp_sample' WHERE p.post_type=%s AND p.post_status='publish' AND s.post_id IS " . ( $sample ? 'NOT NULL' : 'NULL' ) . ' ORDER BY p.ID LIMIT 1',
		$type
	) );
};
$cases = array(
	'روادید (نمونه)' => $pick( 'tp_visa', true ),
	'ارز (واقعی)'    => $pick( 'tp_currency', false ),
	'ارز (نمونه)'    => $pick( 'tp_currency', true ),
);
$wantq = array(
	'tp_visa'     => array( 'fa' => array( 'روادید لازم است؟', 'چند روز؟', 'هزینه؟' ), 'en' => array( 'Do I need a visa?', 'How many days?', 'How much does it cost?' ) ),
	'tp_currency' => array( 'fa' => array( 'کارت بین‌المللی کار می‌کند؟', 'واحد', 'نماد' ), 'en' => array( 'Do international cards work?', 'Unit', 'Symbol' ) ),
);
$sections = function ( $html ) {
	return preg_match( '#<section id="tp-quick"[^>]*>(.*?)</section>#su', $html, $m ) ? $m[1] : '';
};
foreach ( $cases as $name => $id ) {
	if ( ! $id ) { $t( $name . ': پست هست', false, 'نیست' ); continue; }
	$type = get_post_type( $id );
	foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
		$tid = 'fa' === $l ? $id : (int) apply_filters( 'wpml_object_id', $id, $type, false, $l );
		if ( ! $tid ) { echo "  · $name [$l]: ترجمهٔ منتشرشده نیست — رد شد
"; continue; }
		list( $code, $html ) = $get( $url( $tid, $l ) );
		$sec = $sections( $html );
		preg_match_all( '#<dt>(.*?)</dt><dd>(.*?)</dd>#su', $sec, $m );
		$answers = array_map( function ( $x ) { return trim( wp_strip_all_tags( $x ) ); }, $m[2] );
		$dash    = substr_count( $sec, 'role="img" aria-label="' . ( 'fa' === $l ? 'نامشخص' : ( 'en' === $l ? 'Unknown' : '' ) ) );
		$leak    = false;
		if ( 'fa' !== $l ) {
			foreach ( array_merge( array_map( 'wp_strip_all_tags', $m[1] ), $answers ) as $txt ) {
				if ( function_exists( 'topersia_g_foreign_text' ) ) { $leak = $leak || preg_match( '/[پچژگ]/u', $txt ) || ( 'en' === $l && preg_match( '/\p{Arabic}/u', $txt ) ); }
			}
		}
		$ok = 200 === $code && '' !== $sec && 3 === count( $m[1] ) && 1 === preg_match( '#id="tp-quickTitle"#', $sec ) && false === strpos( $sec, 'Array' );
		$t( "$name [$l]: ۲۰۰، بخش «پاسخ کوتاه» با سه پرسش dt/dd", $ok, "code=$code خانه=" . count( $m[1] ) );
		if ( isset( $wantq[ $type ][ $l ] ) ) { $t( "$name [$l]: متن سه پرسش", $m[1] === $wantq[ $type ][ $l ], implode( ' | ', $m[1] ) ); }
		$t( "$name [$l]: هیچ نشت زبان در پرسش و پاسخ", ! $leak, implode( ' | ', $answers ) );
		/* نخستین بخش ستون اصلی */
		$first = preg_match( '#<div class="tp-main-col">\s*<section id="([a-z-]+)"#u', $html, $f ) ? $f[1] : '';
		$t( "$name [$l]: نخستین بخش ستون اصلی", 'tp-quick' === $first, $first );
		echo '    · ' . implode( ' ‖ ', $answers ) . "\n";
		/* «—» فقط با aria-label؛ پاسخ خالی هرگز بی نشان */
		$empty = 0;
		foreach ( $m[2] as $a ) { if ( '' === trim( wp_strip_all_tags( $a ) ) ) { $empty++; } }
		$t( "$name [$l]: هر پاسخ مقدار یا «—» نامشخص است، نه خالی", 0 === $empty );
	}
}
/* شهر: بی بخش */
$city = $pick( 'tp_city', false );
list( $code, $html ) = $get( $url( $city, 'fa' ) );
$t( 'شهر: «پاسخ کوتاه» نمی‌آید', 200 === $code && false === strpos( $html, 'id="tp-quick"' ), "code=$code" );
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
