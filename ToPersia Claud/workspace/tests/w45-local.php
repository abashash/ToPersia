<?php
/**
 * دستهٔ 45 — صفحهٔ اول روی وردپرس واقعی (fa · en · ar): php wp-cli.phar … eval-file tests/w45-local.php
 * فقط خواندن (سه درخواست GET به صفحه‌های اول + پیوندهای کاشی‌ها). متن از docs/legal/PAGES-TEXT-2026-11-04.md.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }
$GLOBALS['p45'] = 0; $GLOBALS['f45'] = 0;
function t45( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['p45']++; echo "  ✅ $label\n"; } else { $GLOBALS['f45']++; echo "  ❌ $label   $note\n"; } }
$txt = (string) file_get_contents( dirname( __DIR__ ) . '/docs/legal/PAGES-TEXT-2026-11-04.md' );
$want = array(
	'fa' => array( '/', 'ToPersia — دانشنامهٔ ایران', 'جغرافیا، تاریخ، فرهنگ و سفر ایران، با منبع و به چند زبان.' ),
	'en' => array( '/en/', 'ToPersia — The Encyclopedia of Iran', "Iran's geography, history, culture and travel — sourced and multilingual." ),
	'ar' => array( '/ar/', 'ToPersia — موسوعة إيران', 'جغرافيا إيران وتاريخها وثقافتها والسفر إليها، بمصادر وبلغات متعددة.' ),
);
$sample = array_flip( array_map( function ( $id ) { return rtrim( get_permalink( $id ), '/' ); }, get_posts( array( 'post_type' => 'any', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_tp_sample', 'suppress_filters' => true ) ) ) );
$get = function ( $url ) {
	$ch = curl_init( $url ); curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_FOLLOWLOCATION => 0 ) );
	$b = curl_exec( $ch ); $c = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE ); curl_close( $ch ); return array( $c, (string) $b );
};
foreach ( $want as $l => $w ) {
	echo "\n[$l]\n";
	list( $code, $h ) = $get( 'http://topersia.local' . $w[0] );
	t45( "$l: پاسخ ۲۰۰", 200 === $code, (string) $code );
	t45( "$l: عنوان و توضیح متا عین فایل متن", false !== strpos( $txt, $w[1] ) && false !== strpos( $txt, $w[2] ) && 1 === preg_match( '#<title>' . preg_quote( $w[1], '#' ) . '</title>#u', $h ) && false !== strpos( $h, '<meta name="description" content="' . esc_attr( $w[2] ) ) );
	t45( "$l: یک h1 با همان عنوان", 1 === preg_match_all( '#<h1[^>]*>#', $h ) && false !== strpos( $h, '<h1>' . $w[1] . '</h1>' ) );
	t45( "$l: جملهٔ معرفی در صفحه", false !== strpos( $h, esc_html( $w[2] ) ) || false !== strpos( $h, htmlspecialchars( $w[2], ENT_NOQUOTES ) ) );
	t45( "$l: بخش دسته‌های اصلی و استان‌ها", false !== strpos( $h, 'id="tp-home-categories"' ) && false !== strpos( $h, 'id="tp-home-provinces"' ) );
	preg_match_all( '#<a href="([^"]+)" class="tp-tile"#', $h, $m );
	$bad = array(); foreach ( $m[1] as $u ) { if ( isset( $sample[ rtrim( $u, '/' ) ] ) ) { $bad[] = $u; } }
	t45( "$l: هیچ کاشی به پست نمونه نمی‌رود (" . count( $m[1] ) . " کاشی)", ! $bad, implode( ' ', $bad ) );
	$dead = array(); foreach ( array_unique( $m[1] ) as $u ) { if ( 0 === strpos( $u, '#' ) ) { if ( false === strpos( $h, 'id="' . substr( $u, 1 ) . '"' ) ) { $dead[] = $u; } continue; } list( $c ) = $get( $u ); if ( 200 !== $c ) { $dead[] = "$c $u"; } }
	t45( "$l: هر پیوند کاشی ۲۰۰ است یا لنگرش در صفحه هست", ! $dead, implode( ' | ', $dead ) );
	$body = preg_match( '#<div class="tp-page">.*?</main>#s', $h, $bm ) ? $bm[0] : '';
	t45( "$l: در بدنهٔ صفحهٔ اول کادر جست‌وجو و فرم نیست و SearchAction در اسکیما نیست", '' !== $body && false === stripos( $body, '<form' ) && false === stripos( $body, 'role="search"' ) && false === stripos( $h, 'SearchAction' ) );
	preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $h, $j );
	$g = isset( $j[1] ) ? json_decode( $j[1], true ) : null; $types = $g ? array_column( $g['@graph'], '@type' ) : array();
	t45( "$l: JSON-LD معتبر با Organization · WebSite · WebPage و inLanguage=$l", $g && array( 'Organization', 'WebSite', 'WebPage' ) === $types && $g['@graph'][1]['inLanguage'] === $l && $g['@graph'][2]['inLanguage'] === $l );
}
echo "\n" . str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['p45']}   افتاده: {$GLOBALS['f45']}\n";
exit( $GLOBALS['f45'] ? 1 : 0 );
