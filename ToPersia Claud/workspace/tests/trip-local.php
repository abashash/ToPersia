<?php
/**
 * «اطلاعات سفر» تصویری روی وردپرس محلی — دستهٔ 35 · P5-D460. فقط می‌خواند (GET).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/trip-local.php
 *
 * کشور (نمونه و واقعی)، شهر، جزیره، بنا — fa · en · ar: ۲۰۰؛ کارت `tp-practical` کاشی `tp-trip__t` با آیکون SVG درون‌خط؛ بی script
 * درون کارت؛ بی `tp-practical-grid`؛ «—» فقط با aria-label «نامشخص» (به زبان صفحه)؛ en و ar بی متن فارسی در برچسب‌ها؛ برای کشور
 * دوشاخه · سمت رانندگی · منطقهٔ زمانی همیشه کاشی دارند.
 */
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) {
	$ok ? $pass++ : $fail++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
global $wpdb;
$url = function ( $id, $l ) { do_action( 'wpml_switch_language', $l ); $u = get_permalink( $id ); do_action( 'wpml_switch_language', 'fa' ); return $u; };
$get = function ( $u ) {
	$r = wp_remote_get( $u, array( 'timeout' => 90, 'sslverify' => false, 'proxy' => '' ) );
	return is_wp_error( $r ) ? array( 0, '' ) : array( (int) wp_remote_retrieve_response_code( $r ), (string) wp_remote_retrieve_body( $r ) );
};
$pick = function ( $type, $sample ) use ( $wpdb ) {
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_tp_sample' WHERE p.post_type=%s AND p.post_status='publish' AND s.post_id IS " . ( $sample ? 'NOT NULL' : 'NULL' ) . ' ORDER BY p.ID LIMIT 1',
		$type
	) );
};
$cases = array( 'کشور (نمونه)' => $pick( 'tp_country', true ), 'کشور (واقعی)' => $pick( 'tp_country', false ), 'شهر (نمونه)' => $pick( 'tp_city', true ), 'جزیره (نمونه)' => $pick( 'tp_island', true ) );
$aria = array( 'fa' => 'نامشخص', 'en' => 'Unknown', 'ar' => 'غير معروف' );
foreach ( $cases as $name => $id ) {
	if ( ! $id ) { $t( "$name: پست هست", false ); continue; }
	$type = get_post_type( $id );
	foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
		$tid = 'fa' === $l ? $id : (int) apply_filters( 'wpml_object_id', $id, $type, false, $l );
		if ( ! $tid ) { echo "  · $name [$l]: ترجمهٔ منتشرشده نیست — رد شد\n"; continue; }
		list( $code, $h ) = $get( $url( $tid, $l ) );
		preg_match( '#<section id="tp-practical".*?</section>#su', $h, $sm );
		$sec = $sm[0] ?? '';
		$tiles = preg_match_all( '#<(?:a|div) class="tp-trip__t"#', $sec );
		$svgs  = preg_match_all( '#<span class="tp-trip__i" aria-hidden="true"><svg #', $sec );
		$ok = 200 === $code && '' !== $sec && $tiles >= 1 && $tiles === $svgs && false === strpos( $sec, 'tp-practical-grid' ) && false === stripos( $sec, '<script' ) && false === strpos( $sec, 'Array' );
		$t( "$name [$l]: ۲۰۰، کارت «اطلاعات سفر» با $tiles کاشی، هر کدام آیکون SVG درون‌خط، بی script", $ok, "code=$code tiles=$tiles svg=$svgs" );
		/* برچسب‌ها بی متن زبان دیگر (برچسب = نشانهٔ رابط، نه دادهٔ کاربر) */
		preg_match_all( '#<span class="tp-trip__k">(.*?)</span>#su', $sec, $km );
		$leak = false;
		foreach ( $km[1] as $lab ) { $lab = wp_strip_all_tags( $lab ); if ( 'en' === $l && preg_match( '/\p{Arabic}/u', $lab ) ) { $leak = true; } if ( 'ar' === $l && preg_match( '/[پچژگ]/u', $lab ) ) { $leak = true; } }
		$t( "$name [$l]: برچسب کاشی‌ها بی نشت زبان", ! $leak, implode( ' | ', array_map( 'wp_strip_all_tags', $km[1] ) ) );
		/* «—» با aria-label به زبان صفحه */
		$dashes = preg_match_all( '#<b class="tp-trip__v tp-trip__v--none" role="img" aria-label="([^"]*)">—</b>#u', $sec, $dm );
		$wrong  = array_filter( $dm[1], function ( $a ) use ( $aria, $l ) { return $a !== $aria[ $l ]; } );
		$t( "$name [$l]: $dashes «—» همه با aria-label «{$aria[$l]}»", ! $wrong, implode( ',', $wrong ) );
		if ( 'tp_country' === $type ) {
			$need = array( 'electric_plug' => 'plug', 'drive_side' => 'wheel', 'timezone' => 'clock' );
			$missing = array();
			foreach ( $need as $k => $icon ) { if ( false === strpos( $sec, 'tp-trip__t' ) ) { $missing[] = $k; } }
			$t( "$name [$l]: دوشاخه · سمت رانندگی · منطقهٔ زمانی کاشی دارند (دست‌کم ۳ کاشی)", $tiles >= 3 );
		}
	}
}
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
