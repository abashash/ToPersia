<?php
/**
 * صفحهٔ ایران روی وردپرس محلی — دستهٔ 36-ج · P5-D479. فقط می‌خواند (رندر درون‌فرایندی، بی درخواست HTTP).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/iran-local.php
 *
 * پست `tp_country` با نامک `iran` (واقعی، نه نمونه). اگر نیست («وارد نشده») رد می‌شود، افتادن نیست.
 *   هیرو: چهار خانه؛ اگر currency_note فقط «ریال» است، خانهٔ «واحد پول» = «ریال»؛
 *   «اطلاعات سفر»: بی «منطقهٔ زمانی»؛ «اطلاعات جغرافیایی»: «منطقهٔ زمانی» هست؛
 *   «درباره»: خلاصهٔ کوتاه کامل؛ نقشه: cfg.bound به country__iran.geojson با فایل موجود و نامعتبر نبودن GeoJSON.
 */
global $wpdb;
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) {
	$ok ? $pass++ : $fail++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
$id = (int) $wpdb->get_var( "SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_tp_sample' WHERE p.post_type='tp_country' AND p.post_name='iran' AND p.post_status='publish' AND s.post_id IS NULL ORDER BY p.ID LIMIT 1" );
if ( ! $id ) { echo "  · پست ایران (tp_country، نامک iran، منتشرشده) در پایگاه نیست — رد شد\n"; exit( 0 ); }

$p = get_post( $id ); $GLOBALS['post'] = $p; setup_postdata( $p );
$h = do_shortcode( '[tp_page id="' . $id . '"]' );
preg_match_all( '#<section[^>]*id="([^"]+)"#', $h, $m, PREG_OFFSET_CAPTURE );
$part = function ( $sid ) use ( $h, $m ) {
	foreach ( $m[1] as $i => $x ) {
		if ( $x[0] === $sid ) {
			$end = isset( $m[1][ $i + 1 ] ) ? $m[1][ $i + 1 ][1] : strlen( $h );
			return substr( $h, $x[1], $end - $x[1] );
		}
	}
	return '';
};
$hero = substr( $h, 0, isset( $m[1][0] ) ? $m[1][0][1] : 0 );
$txt  = function ( $x ) { return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $x ) ) ); };
$note = trim( (string) get_post_meta( $id, 'currency_note', true ) );
$sum  = trim( (string) get_post_meta( $id, 'short_summary', true ) );

if ( preg_match( '/^[^\s()،,.;:؛!؟?]+(\s[^\s()،,.;:؛!؟?]+)?$/u', $note ) ) {
	$t( 'هیرو: خانهٔ «واحد پول» = «' . $note . '» (فقط نام)', false !== strpos( $txt( $hero ), 'واحد پول ' . $note ) || false !== strpos( $txt( $hero ), $note ), $txt( $hero ) );
}
$t( 'هیرو: چهار خانه', 4 === substr_count( $hero, 'class="tp-stat"' ) || 4 === preg_match_all( '#data-tp-k="#', $hero ), (string) preg_match_all( '#data-tp-k="#', $hero ) );
$tz_prac = false !== strpos( $txt( $part( 'tp-practical' ) ), 'منطقۀ زمانی' ) || false !== strpos( $txt( $part( 'tp-practical' ) ), 'منطقهٔ زمانی' );
$tz_geo  = false !== strpos( $txt( $part( 'tp-geo' ) ), 'منطقۀ زمانی' ) || false !== strpos( $txt( $part( 'tp-geo' ) ), 'منطقهٔ زمانی' );
$t( '«اطلاعات سفر»: منطقهٔ زمانی نیست', ! $tz_prac );
$t( '«اطلاعات جغرافیایی»: منطقهٔ زمانی هست (فیلد timezone یا ردیف مشتق)', $tz_geo );
$t( '«درباره»: خلاصهٔ کوتاه کامل (' . mb_strlen( $sum, 'UTF-8' ) . ' نویسه) بی «…»', '' !== $sum && false !== strpos( $part( 'tp-overview' ), esc_html( $sum ) ) );

$cfg = topersia_g_map_attr( $id );
preg_match( "/data-tp-map-cfg='([^']*)'/", $cfg, $cm );
$c   = $cm ? json_decode( html_entity_decode( $cm[1] ), true ) : array();
$up  = wp_get_upload_dir();
$bf  = trailingslashit( $up['basedir'] ) . 'tiles/boundaries/country__iran.geojson';
$g   = is_file( $bf ) ? json_decode( (string) file_get_contents( $bf ), true ) : null;
$t( 'نقشه: cfg.bound به country__iran.geojson با ?v=', ! empty( $c['bound'] ) && false !== strpos( $c['bound'], 'country__iran.geojson?v=' ), $cfg ? substr( $cfg, 0, 120 ) : 'بی cfg' );
$t( 'نقشه: فایل مرز کشور موجود و GeoJSON معتبر (MultiPolygon، حلقهٔ بسته، bbox ایران)', is_array( $g ) && 'MultiPolygon' === ( $g['geometry']['type'] ?? '' ) && ( function () use ( $g ) {
	$b = array( INF, INF, -INF, -INF );
	foreach ( $g['geometry']['coordinates'] as $poly ) { $r = $poly[0]; if ( $r[0] !== $r[ count( $r ) - 1 ] ) { return false; } foreach ( $r as $pt ) { $b = array( min( $b[0], $pt[0] ), min( $b[1], $pt[1] ), max( $b[2], $pt[0] ), max( $b[3], $pt[1] ) ); } }
	return $b[0] > 43.5 && $b[0] < 44.5 && $b[2] > 63 && $b[2] < 64 && $b[1] > 24 && $b[1] < 26 && $b[3] > 39.5 && $b[3] < 40.2;
} )() );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
