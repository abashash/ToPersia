<?php
/**
 * گزارش F-05 (دستهٔ ۲۶ بند ۶): شمار ردیف کارت «اطلاعات جغرافیایی» هر پست منتشرشده، به تفکیک نوع — فقط می‌خواند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-geo-rows.php [md]
 *
 * «بی کارت» = نوع غیرجغرافیایی (نه کاشی، نه مختصات، نه زنجیرهٔ مکانی)؛ «<۳» = کارت هست و با ردیف‌های مشتق هم به سه نرسید.
 */
$by = array();
foreach ( get_post_types( array( 'public' => true ) ) as $t ) {
	if ( 0 !== strpos( $t, 'tp_' ) || 'tp_county' === $t ) { continue; }
	foreach ( get_posts( array( 'post_type' => $t, 'numberposts' => -1, 'post_status' => 'publish', 'suppress_filters' => false, 'fields' => 'ids' ) ) as $id ) {
		$GLOBALS['post'] = get_post( $id );
		$h = topersia_g_sc_facts( array( 'id' => $id ) );
		$n = substr_count( $h, 'class="tp-tile"' );
		$by[ $t ]['posts'] = ( $by[ $t ]['posts'] ?? 0 ) + 1;
		$k = '' === $h || 0 === $n ? 'none' : ( $n < 3 ? 'lt3' : 'ok' );
		$by[ $t ][ $k ] = ( $by[ $t ][ $k ] ?? 0 ) + 1;
		if ( false !== strpos( $h, '>—<' ) ) { $by[ $t ]['dash'] = ( $by[ $t ]['dash'] ?? 0 ) + 1; }
	}
}
ksort( $by );
echo "| نوع | پست | ≥۳ | <۳ | بی کارت | «—» |\n|---|---|---|---|---|---|\n";
foreach ( $by as $t => $c ) {
	printf( "| `%s` | %d | %d | %d | %d | %d |\n", $t, $c['posts'], $c['ok'] ?? 0, $c['lt3'] ?? 0, $c['none'] ?? 0, $c['dash'] ?? 0 );
}
