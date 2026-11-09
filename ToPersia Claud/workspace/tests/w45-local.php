<?php
/**
 * دستهٔ 45 · بازطراحی دستۀ 46 — صفحهٔ اول روی وردپرس واقعی (fa · en · ar): php wp-cli.phar … eval-file tests/w45-local.php
 * فقط خواندن (سه درخواست GET به صفحه‌های اول + پیوندهای کاشی‌ها). متن از docs/legal/PAGES-TEXT-2026-11-04.md.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }
$GLOBALS['p45'] = 0; $GLOBALS['f45'] = 0;
function t45( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['p45']++; echo "  ✅ $label\n"; } else { $GLOBALS['f45']++; echo "  ❌ $label   $note\n"; } }
$txt = (string) file_get_contents( dirname( __DIR__ ) . '/docs/legal/PAGES-TEXT-2026-11-04.md' );
$want = array(
	'fa' => array( '/', 'توپرشیا — دانشنامهٔ ایران', 'جغرافیا، تاریخ، فرهنگ و سفر ایران، با منبع و به چند زبان.' ),
	'en' => array( '/en/', 'ToPersia — The Encyclopedia of Iran', "Iran's geography, history, culture and travel — sourced and multilingual." ),
	'ar' => array( '/ar/', 'توبيرشيا — موسوعة إيران', 'جغرافيا إيران وتاريخها وثقافتها والسفر إليها، بمصادر وبلغات متعددة.' ),
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
	$h1w = array( 'fa' => 'ایران را کشف کنید', 'en' => 'Discover Iran', 'ar' => 'اكتشف إيران' );   /* دستۀ 46 · P5-D513 */
	t45( "$l: یک h1 «{$h1w[$l]}» در کارت هیرو", 1 === preg_match_all( '#<h1[^>]*>#', $h ) && false !== strpos( $h, '<h1 id="tp-home-h1">' . $h1w[$l] . '</h1>' ) && false !== strpos( $h, 'class="tp-home-hero__card tp-home-glass"' ) );
	t45( "$l: جملۀ معرفی در صفحه", false !== strpos( $h, esc_html( $w[2] ) ) || false !== strpos( $h, htmlspecialchars( $w[2], ENT_NOQUOTES ) ) );
	$body = preg_match( '#<div class="tp-page tp-home">.*?</main>#s', $h, $bm ) ? $bm[0] : '';
	t45( "$l: شیوه‌نامۀ صفحۀ اول بار شده و پیش‌بارگذاری تنها تصویر هیرو", false !== strpos( $h, 'topersia-g-home.css' ) && ( 1 === substr_count( $h, 'rel="preload" as="image"' ) ) === ( false !== strpos( $body, 'tp-home-hero__img' ) ) );
	t45( "$l: بخش استان‌ها", false !== strpos( $body, 'id="tp-home-provinces"' ) );
	/* هر بخش چاپ‌شده بدنه دارد (سرفصل تنها نیست) */
	$empty = array(); if ( preg_match_all( '#<section id="(tp-home-[a-z]+)" class="tp-section tp-home-sec[^"]*"[^>]*>(.*?)</section>#s', $body, $sm, PREG_SET_ORDER ) ) { foreach ( $sm as $s ) { if ( strlen( trim( strip_tags( preg_replace( '#<div class="tp-section-head"><div><h2[^>]*>.*?</h2></div>(?:<a [^>]*>[^<]*</a>)?</div>#s', '', $s[2] ), '<img><a><path>' ) ) ) < 5 ) { $empty[] = $s[1]; } } }
	t45( "$l: هیچ بخش بی‌داده (فقط سرفصل) چاپ نشده — " . count( $sm ) . " بخش", ! $empty, implode( ' ', $empty ) );
	/* نوار شمار: فقط عدد ≥ ۱ و دست‌کم سه، یا هیچ */
	$nums = preg_match_all( '#<li class="tp-home-stats__i">.*?<b>([^<]+)</b>#s', $body, $nm ) ? $nm[1] : array();
	$zero = array_filter( $nums, function ( $n ) { return 0 === (int) strtr( $n, array( '۰' => 0, '۱' => 1, '۲' => 2, '۳' => 3, '۴' => 4, '۵' => 5, '۶' => 6, '۷' => 7, '۸' => 8, '۹' => 9, '٠' => 0, '١' => 1, '٢' => 2, '٣' => 3, '٤' => 4, '٥' => 5, '٦' => 6, '٧' => 7, '٨' => 8, '٩' => 9, ',' => '', '٬' => '' ) ); } );
	t45( "$l: نوار شمار بی صفر و یا ≥ ۳ عدد یا هیچ (" . count( $nums ) . " عدد)", ! $zero && ( 0 === count( $nums ) || count( $nums ) >= 3 ) && ( 0 === count( $nums ) ) === ( false === strpos( $body, 'id="tp-home-stats"' ) ) );
	/* نقشه: پیوند فقط برای استان منتشرشدۀ همین زبان */
	do_action( 'wpml_switch_language', $l );
	$np = count( get_posts( array( 'post_type' => 'tp_province', 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => -1, 'fields' => 'ids', 'suppress_filters' => false, 'meta_query' => array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) ) ) );
	$nl = substr_count( $body, '<a class="tp-home-map__a"' ); $no = substr_count( $body, 'tp-home-map__off' );
	t45( "$l: نقشه — $nl پیوند برای $np استان منتشرشده، $no کم‌رنگ", $nl === $np && ( 0 === $nl || 31 === $nl + $no ) );
	/* همۀ پیوندهای بدنه: نه به نمونه، ۲۰۰ یا لنگر موجود */
	preg_match_all( '#<a [^>]*href="([^"]+)"#', $body, $m );
	$bad = array(); foreach ( $m[1] as $u ) { if ( isset( $sample[ rtrim( preg_replace( '/#.*$/', '', $u ), '/' ) ] ) ) { $bad[] = $u; } }
	t45( "$l: هیچ پیوندی به پست نمونه نمی‌رود (" . count( $m[1] ) . " پیوند)", ! $bad, implode( ' ', $bad ) );
	$dead = array(); foreach ( array_unique( $m[1] ) as $u ) { if ( 0 === strpos( $u, '#' ) ) { if ( false === strpos( $h, 'id="' . substr( $u, 1 ) . '"' ) ) { $dead[] = $u; } continue; } list( $c ) = $get( preg_replace( '/#.*$/', '', html_entity_decode( $u ) ) ); if ( 200 !== $c ) { $dead[] = "$c $u"; } }
	t45( "$l: هر پیوند (کارت، نقشه، راهنما، عکس، دعوت) ۲۰۰ است یا لنگرش در صفحه هست", ! $dead, implode( ' | ', $dead ) );
	/* تصویرها: هر کدام ابعاد صریح، جز هیرو همه lazy. سقف از هر بخش (دستۀ 47 بخش ۲، جای «≤ ۴۰» دستۀ 46): هیرو ۱ + استان ۳۱ + گروه جاذبه ۴ + مقصد ۱۲ + گالری ۸ + تصویر پنجره ۱ = ۵۷ */
	$cap = array( 'tp-home-hero__img' => 1, 'tp-home-dest__img' => 12, 'tp-home-photo__img' => 12, 'tp-home-photodlg__img' => 1 ); $over = array();
	preg_match_all( '#<img [^>]*class="([^"]*)"#', $body, $ic ); foreach ( array_count_values( $ic[1] ) as $k => $v ) { if ( isset( $cap[ $k ] ) && $v > $cap[ $k ] ) { $over[] = "$k=$v"; } }
	preg_match_all( '#<img [^>]*>#', $body, $im ); $noimg = array();
	foreach ( $im[0] as $t ) { $hero = false !== strpos( $t, 'tp-home-hero__img' ); if ( ! preg_match( '/ width="\d+"/', $t ) || ! preg_match( '/ height="\d+"/', $t ) || $hero !== ( false !== strpos( $t, 'fetchpriority="high"' ) ) || ( ! $hero && false === strpos( $t, 'loading="lazy"' ) ) ) { $noimg[] = substr( $t, 0, 90 ); } }
	t45( "$l: " . count( $im[0] ) . " تصویر (≤ ۶۱، هر بخش زیر سقف خودش)، همه با ابعاد؛ فقط هیرو fetchpriority، بقیه lazy", count( $im[0] ) <= 61 && ! $over && ! $noimg, implode( ' | ', array_merge( $over, $noimg ) ) );
	/* P5-D536 (دستۀ 49): یک فرم جست‌وجوی GET (s) در هیرو و SearchAction در اسکیما — جای حکم «بی جست‌وجو»ی دستۀ 45 */
	t45( "$l: بدنۀ صفحۀ اول فقط یک فرم جست‌وجوی GET (name=s) دارد و SearchAction در اسکیما هست", 1 === substr_count( $body, '<form' ) && 1 === substr_count( $body, 'role="search"' ) && false !== strpos( $body, 'name="s"' ) && false !== stripos( $h, 'SearchAction' ) );
	preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $h, $j );
	$g = isset( $j[1] ) ? json_decode( $j[1], true ) : null; $types = $g ? array_column( $g['@graph'], '@type' ) : array();
	$nfaq = substr_count( $body, '<details class="tp-home-faq__i"' );
	$want_t = $nfaq ? array( 'Organization', 'WebSite', 'WebPage', 'FAQPage' ) : array( 'Organization', 'WebSite', 'WebPage' );
	$qn = ( $g && $nfaq ) ? count( $g['@graph'][3]['mainEntity'] ) : 0;
	t45( "$l: JSON-LD معتبر — " . implode( ' · ', $want_t ) . " و inLanguage={$l}؛ FAQPage فقط برای $nfaq پرسش دیده‌شده", $g && $want_t === $types && $g['@graph'][1]['inLanguage'] === $l && $g['@graph'][2]['inLanguage'] === $l && $qn === $nfaq );
}
echo "\n" . str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['p45']}   افتاده: {$GLOBALS['f45']}\n";
exit( $GLOBALS['f45'] ? 1 : 0 );
