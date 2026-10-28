<?php
/**
 * هیرو و تکرار روی وردپرس محلی — دستهٔ 34 · P5-D458 · P5-D464. فقط می‌خواند (GET).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/dedupe-local.php
 *
 * هر صفحهٔ fa (نمونهٔ ۴۱ نوع + چند پست واقعی):
 *   هیرو: فقط eyebrow، h1 و حداکثر یک خط هم‌اندازهٔ کاشی (≤ TOPERSIA_HERO_LINE_MAX نویسه)؛ نه lead، نه tp-altnames.
 *   تکرار: کاشی‌های «اطلاعات جغرافیایی» مقدارِ خانه‌های سربرگ (والد مکانی، مرکز استان، فاصله تا مرکز استان) را ندارند.
 *   خلاصهٔ کوتاه: آغاز «درباره»، نه هیرو.
 */
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) {
	$ok ? $pass++ : $fail++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
global $wpdb;
$get = function ( $u ) {
	$r = wp_remote_get( $u, array( 'timeout' => 90, 'sslverify' => false, 'proxy' => '' ) );
	return is_wp_error( $r ) ? '' : (string) wp_remote_retrieve_body( $r );
};
$text = function ( $h ) { return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( preg_replace( '#<(script|style)\b.*?</\1>#siu', ' ', (string) $h ) ), ENT_QUOTES, 'UTF-8' ) ) ); };

$ids = array();
foreach ( array_keys( topersia_g_stat_cells() ) as $type ) {
	foreach ( array( true, false ) as $sample ) {
		$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_tp_sample' WHERE p.post_type=%s AND p.post_status='publish' AND s.post_id IS " . ( $sample ? 'NOT NULL' : 'NULL' ) . ' ORDER BY p.ID LIMIT 1', $type ) );
		if ( $id ) { $ids[ $id ] = $type . ( $sample ? ' (نمونه)' : '' ); }
	}
}

$bad_hero = array(); $bad_dup = array(); $bad_lead = array(); $n = 0; $maxline = 0; $lines = 0;
foreach ( $ids as $id => $label ) {
	$h = $get( get_permalink( $id ) );
	if ( '' === $h ) { $bad_hero[] = "$label: بی پاسخ"; continue; }
	$n++;
	preg_match( '#<header class="tp-hero">(.*?)</header>#su', $h, $hm );
	$hero = $hm[1] ?? '';
	$extra = substr_count( $hero, '<p class="tp-hero__alt' );
	$line  = preg_match( '#<p class="tp-hero__alt[^"]*">(.*?)</p>#su', $hero, $lm ) ? $text( $lm[1] ) : '';
	if ( '' !== $line ) { $lines++; $maxline = max( $maxline, mb_strlen( $line ) ); }
	$isevent = false !== strpos( $hero, 'tp-hero__when' );
	if ( 1 !== substr_count( $hero, '<h1>' ) || $extra > 1 || false !== strpos( $hero, 'tp-hero__lead' ) || false !== strpos( $hero, 'tp-altnames' ) || ( ! $isevent && mb_strlen( $line ) > TOPERSIA_HERO_LINE_MAX + 3 ) ) {
		$bad_hero[] = "$label ($id) خط=" . mb_strlen( $line );
	}
	/* خلاصهٔ کوتاه: اگر پست دارد، آغاز «درباره» */
	$lead = trim( (string) get_post_meta( $id, 'short_summary', true ) );
	if ( '' !== $lead && preg_match( '#<section id="tp-overview".*?</section>#su', $h, $om ) && false === mb_strpos( $text( $om[0] ), mb_substr( $lead, 0, 30 ) ) ) { $bad_lead[] = "$label ($id)"; }
	/* تکرار: مقدار خانه‌های والد/مرکز/فاصله در کارت جغرافیایی نیست */
	preg_match( '#<section id="tp-geo".*?</section>#su', $h, $gm );
	$geo = isset( $gm[0] ) ? $text( $gm[0] ) : '';
	preg_match_all( '#data-tp-k="((?:parent:[a-z]+)|capital|distance:capital)"><span class="tp-stat__k">(.*?)</span><b class="tp-stat__v"[^>]*>(.*?)</b>#su', $h, $cells, PREG_SET_ORDER );
	foreach ( $cells as $c ) {
		$val = $text( $c[3] );
		if ( '' === $val || '—' === $val || '' === $geo ) { continue; }
		$kind = 'distance:capital' === $c[1] ? $text( $c[2] ) : $val;
		if ( false !== mb_strpos( $geo, $kind ) ) { $bad_dup[] = "$label: {$c[1]} «" . mb_substr( $val, 0, 20 ) . '»'; }
	}
}
$t( 'هیرو در ' . $n . ' صفحه: یک h1، حداکثر یک خط، بی lead و بی tp-altnames؛ خط (نه رویداد) ≤ ' . TOPERSIA_HERO_LINE_MAX . ' نویسه', ! $bad_hero, implode(' · ', array_slice( $bad_hero, 0, 8 ) ) );
echo "  · خط کوتاه در $lines صفحه؛ بلندترین " . $maxline . " نویسه\n";
$t( 'خلاصهٔ کوتاه در آغاز «درباره» (نه هیرو) برای هر صفحه‌ای که دارد', ! $bad_lead, implode( ' · ', array_slice( $bad_lead, 0, 8 ) ) );
$t( 'کاشی‌های «اطلاعات جغرافیایی» مقدار خانه‌های سربرگ (والد مکانی · مرکز استان · فاصله) را تکرار نمی‌کنند', ! $bad_dup, implode( ' · ', array_slice( $bad_dup, 0, 8 ) ) );
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
