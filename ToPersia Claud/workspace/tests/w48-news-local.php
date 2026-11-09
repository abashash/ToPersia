<?php
/**
 * دستۀ 48 بخش ۲ — آرشیو سراسری خبر /news/ روی وردپرس واقعی (SPRINT-4.4 فاز ۶٫۱، P5-D260):
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/w48-news-local.php
 * فقط خواندن: هیچ پستی ساخته یا عوض نمی‌شود. جهش‌ها با صافی در همین فرایند (get_post_metadata،
 * query var) — نه با پایگاه‌داده.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

$GLOBALS['pN'] = 0; $GLOBALS['fN'] = 0;
function tN( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['pN']++; echo "  ✅ $label\n"; } else { $GLOBALS['fN']++; echo "  ❌ $label   " . mb_substr( (string) $note, 0, 400 ) . "\n"; } }
$get = function ( $url ) {
	$ch = curl_init( $url ); curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 90, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_PROXY => '' ) );
	$b = curl_exec( $ch ); $c = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE ); curl_close( $ch ); return array( $c, (string) $b );
};
$ld = function ( $h ) {
	return preg_match( '#<!-- ToPersia Structured Data -->\s*<script type="application/ld\+json">(.*?)</script>#s', $h, $m ) ? json_decode( $m[1], true ) : null;
};

echo "\nدستۀ 48 — آرشیو سراسری خبر /news/\n" . str_repeat( '─', 78 ) . "\n";

if ( ! function_exists( 'topersia_g_news_archive_ids' ) || ! topersia_g_news_on() ) { echo "  ⚠ tpc_news یا render نیست — آزموده نشد\n"; exit( 2 ); }

/* ── منطق: دروازه، نمونه، کمینۀ ۳ ── */
$ids = topersia_g_news_archive_ids();
$bad = array_filter( $ids, function ( $id ) { return 'publish' !== get_post_status( $id ) || post_password_required( $id ) || metadata_exists( 'post', $id, '_tp_sample' ) || 'tpc_news' !== get_post_type( $id ); } );
tN( 'شناسه‌های آرشیو: فقط tpc_news منتشرشده، بی‌گذرواژه، غیرنمونه (' . count( $ids ) . ')', $ids && ! $bad, implode( ',', $bad ) );
$all = get_posts( array( 'post_type' => 'tpc_news', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'suppress_filters' => false ) );
$lang = apply_filters( 'wpml_current_language', null );
$off  = array_filter( $ids, function ( $id ) use ( $lang ) { $d = apply_filters( 'wpml_post_language_details', null, $id ); return is_array( $d ) && $lang && $d['language_code'] !== $lang; } );
tN( "شناسه‌ها هم‌زبان صفحه ($lang)", ! $off, implode( ',', $off ) );

/* جهش: دو خبر «نمونه» شوند ← از آرشیو بیرون، و زیر ۳ ← noindex */
$fake = array_slice( $ids, 0, max( 0, count( $ids ) - 2 ) );
$mut  = function ( $v, $id, $key ) use ( $fake ) { return ( '_tp_sample' === $key && in_array( (int) $id, $fake, true ) ) ? array( '1' ) : $v; };
add_filter( 'get_post_metadata', $mut, 1, 3 );
$ids2  = topersia_g_news_archive_ids();
$thin2 = topersia_g_news_archive_thin();
remove_filter( 'get_post_metadata', $mut, 1 );
tN( 'جهش: خبر با _tp_sample از آرشیو بیرون می‌رود (' . count( $ids2 ) . ' از ' . count( $ids ) . ')', count( $ids2 ) === count( $ids ) - count( $fake ) && ! array_intersect( $ids2, $fake ) );
tN( 'زیر ۳ خبر ← noindex (topersia_g_news_archive_thin)', $thin2 && ( count( $ids ) >= 3 ? ! topersia_g_news_archive_thin() : true ) );

/* پالایش: استان اصفهان */
$prov = (int) ( get_page_by_path( 'isfahan', OBJECT, 'tp_province' )->ID ?? 0 );
set_query_var( 'tp_place', $prov );
$fp = topersia_g_news_filter_place();
$tf = topersia_g_news_archive_thin();
set_query_var( 'tp_place', 999999999 );
$fx = topersia_g_news_filter_place();
set_query_var( 'tp_place', 0 );
tN( "پالایش ?tp_place=$prov معتبر؛ پالایش‌شده noindex؛ شناسۀ ناموجود صفر", $prov && $prov === $fp && $tf && 0 === $fx );
$opts = topersia_g_news_filter_options();
tN( 'گزینه‌های پالایش: استان‌ها پیش از شهرها، فقط منتشرشده', $opts && 'tp_province' === array_key_first( $opts ) && ! array_diff( array_keys( $opts ), array( 'tp_province', 'tp_city' ) ) );

/* ── HTML واقعی ── */
$base = home_url( '/news/' );
list( $c, $h ) = $get( $base );
/* دستۀ 49 (P5-D534): برگزیده + سه کنار + شبکه — هر سه «کارت» شمرده می‌شوند */
$items = function ( $html ) {
	preg_match_all( '#<article class="tp-news-(feat|mini|card)">(.*?)</article>#s', $html, $m, PREG_SET_ORDER );
	return $m;
};
$cards = $items( $h );
$cards = array( array_column( $cards, 0 ), array_column( $cards, 2 ), array_column( $cards, 1 ) );
tN( "/news/ ۲۰۰ و با قالب ما (" . count( $cards[1] ) . ' مورد: ' . implode( ',', array_count_values( $cards[2] ) ? array_map( function ( $k, $v ) { return "{$k}×{$v}"; }, array_keys( array_count_values( $cards[2] ) ), array_count_values( $cards[2] ) ) : array() ) . ')',
	200 === $c && false !== strpos( $h, 'tp-news-archive' ) && false === strpos( $h, 'blog-post-loop' ) && count( $cards[1] ) === min( count( $ids ), TOPERSIA_NEWS_PER_PAGE )
	&& ( count( $ids ) < 4 || ( 1 === count( array_keys( $cards[2], 'feat', true ) ) && 3 === count( array_keys( $cards[2], 'mini', true ) ) ) ) );
$cok = true; $why = '';
foreach ( $cards[1] as $i => $k ) {
	$t  = $cards[2][ $i ];
	$ok = preg_match( '#<h3 class="tp-news-' . $t . '__title"><a href="http[^"]+">[^<]+</a></h3>#', $k )
		&& preg_match( '#<time datetime="\d{4}-\d{2}-\d{2}">[۰-۹]+ [^<]+ [۰-۹]{4}</time><span class="tp-news-read">[۰-۹]+ دقیقه مطالعه</span>#u', $k )
		&& preg_match( '#<p class="tp-news-' . $t . '__sum[^"]*">[^<]+</p>#', $k )
		&& preg_match( '#<span class="tp-news-topic">[^<]+</span>#u', $k )
		&& ! preg_match( '#<img[^>]+src="(?!' . preg_quote( content_url(), '#' ) . '|' . preg_quote( home_url( '/assets/' ), '#' ) . ')#', $k );
	if ( ! $ok ) { $cok = false; $why = mb_substr( wp_strip_all_tags( $k ), 0, 120 ); }
}
tN( 'هر مورد: تیتر با پیوند، نشان موضوع، خلاصه، تاریخ خورشیدی، زمان مطالعه؛ تصویر فقط از همین سایت', $cards[1] && $cok, $why );
/* تصویر هرگز نمونه (P5-D535): شناسۀ پیوست هر <img> از کلاس wp-image-N یا نشانی */
preg_match_all( '#<img[^>]+src="([^"]+)"#', preg_match( '#<div class="tp-page tp-news-page tp-news-archive".*?</main>#s', $h, $bm0 ) ? $bm0[0] : '', $imgs );
$smp = array();
foreach ( array_unique( $imgs[1] ) as $u ) {
	$att = attachment_url_to_postid( preg_replace( '#-\d+x\d+(?=\.\w+$)#', '', $u ) );
	if ( $att && metadata_exists( 'post', $att, '_tp_sample' ) ) { $smp[] = $u; }
}
tN( 'هیچ تصویر صفحه (سربرگ، برگزیده، کارت) پیوست نمونه نیست (' . count( array_unique( $imgs[1] ) ) . ' تصویر)', $imgs[1] && ! $smp, implode( ' ', $smp ) );

/* پالایش‌ها روی HTML واقعی (D468) */
$terms = get_terms( array( 'taxonomy' => 'tpc_news_topic', 'hide_empty' => true ) );
$tsl   = ( $terms && ! is_wp_error( $terms ) ) ? reset( $terms ) : null;
if ( $tsl ) {
	$want = get_posts( array( 'post_type' => 'tpc_news', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'post__in' => $ids, 'tax_query' => array( array( 'taxonomy' => 'tpc_news_topic', 'field' => 'slug', 'terms' => $tsl->slug ) ) ) );
	list( $ct, $ht ) = $get( add_query_arg( 'tp_topic', $tsl->slug, $base ) );
	$it = $items( $ht );
	$bad = array_filter( $it, function ( $m ) use ( $tsl ) { return false === strpos( $m[2], '<span class="tp-news-topic">' . esc_html( $tsl->name ) . '</span>' ); } );
	tN( "پالایش موضوع «{$tsl->slug}»: " . count( $it ) . ' از ' . count( $want ) . '، همه همان نشان، بی برگزیده، تراشۀ فعال aria-current',
		200 === $ct && count( $it ) === count( $want ) && ! $bad && false === strpos( $ht, 'tp-news-feat' ) && false !== strpos( $ht, "tp_topic=" . $tsl->slug . "\" aria-current=\"page\"" ) );
} else {
	tN( 'موضوع خبر هست (tp-news-topics.php apply)', false );
}
list( $co, $ho ) = $get( add_query_arg( array( 'tp_kind' => 'tp_city', 'tp_sort' => 'old' ), $base ) );
preg_match_all( '#<time datetime="([^"]+)"#', implode( '', array_column( $items( $ho ), 0 ) ), $dt );
$asc = $dt[1]; sort( $asc );
list( , $hn ) = $get( add_query_arg( array( 'tp_kind' => 'tp_city' ), $base ) );
preg_match_all( '#<time datetime="([^"]+)"#', implode( '', array_column( $items( $hn ), 0 ) ), $dn );
$desc = $dn[1]; rsort( $desc );
tN( 'ترتیب: «قدیمی‌ترین» صعودی و پیش‌فرض نزولی (' . count( $dt[1] ) . ')', 200 === $co && count( $dt[1] ) > 1 && $dt[1] === $asc && $dn[1] === $desc && $dt[1] !== $dn[1] );
$word = '';
foreach ( $ids as $nid ) { if ( preg_match( '/[\x{0600}-\x{06FF}]{5,}/u', get_the_title( $nid ), $wm ) ) { $word = $wm[0]; $hit = $nid; break; } }
list( $cq, $hq ) = $get( add_query_arg( 'tp_q', $word, $base ) );
tN( "جست‌وجو «{$word}»: خبر خودش در نتیجه", 200 === $cq && '' !== $word && false !== strpos( implode( '', array_column( $items( $hq ), 0 ) ), get_permalink( $hit ) ) );
list( $cz, $hz ) = $get( add_query_arg( 'tp_topic', 'no-such-topic', $base ) );
tN( 'موضوع ناموجود: صفر مورد و پیام آرام', 200 === $cz && ! $items( $hz ) && false !== strpos( $hz, 'خبری با این پالایش نیست.' ) );
/* noindex و canonical پالایش‌شده: robots از صافی خود ما؛ canonical از تابع، چون Rank Math روی noindex آن را نمی‌نویسد */
set_query_var( 'tp_sort', 'old' );
$f1 = topersia_g_news_filtered() && topersia_g_news_archive_thin();
set_query_var( 'tp_sort', '' );
$f0 = topersia_g_news_filtered();
tN( 'پالایش‌شده = noindex؛ بی‌پالایش نه؛ GET فقط', $f1 && ! $f0 && false !== strpos( $h, '<form id="tp-news-form" class="tp-news-filter" method="get"' ) );
foreach ( array( 'tp_topic', 'tp_sort', 'tp_q', 'tp_kind' ) as $qv ) { $hasq = $hasq ?? false; $hasq = $hasq || false !== strpos( (string) topersia_g_news_archive_url( 1 ), $qv ); }
tN( 'canonical پالایش‌شده = نشانی بی‌پالایش ' . topersia_g_news_archive_url( 1 ), ! $hasq && untrailingslashit( topersia_g_news_archive_url( 1 ) ) === untrailingslashit( $base ) );
tN( 'مسیر راهنما: ToPersia › اخبار', 1 === preg_match( '#<nav class="tp-crumb"[^>]*>\s*<a href="' . preg_quote( home_url( '/' ), '#' ) . '">ToPersia</a><span>›</span>\s*<span aria-current="page">اخبار</span>#u', $h ) );
tN( 'بی نمونه و بی متن قالب وودمارت («Posted by»، «Continue reading»)', false === stripos( $h, 'Posted by' ) && false === stripos( $h, 'Continue reading' ) && ! preg_match( '#\bsample\b|lorem#i', wp_strip_all_tags( preg_replace( '#<script.*?</script>#s', '', $h ) ) ) );
$j = $ld( $h );
$types = $j ? array_column( $j['@graph'], '@type' ) : array();
$list  = $j ? array_values( array_filter( $j['@graph'], function ( $n ) { return 'ItemList' === $n['@type']; } ) ) : array();
tN( 'JSON-LD: CollectionPage + ItemList از NewsArticle + BreadcrumbList، شمار = کارت‌ها', $j && ! array_diff( array( 'Organization', 'WebSite', 'CollectionPage', 'ItemList', 'BreadcrumbList' ), $types ) && $list && count( $list[0]['itemListElement'] ) === count( $cards[1] ) && 'NewsArticle' === $list[0]['itemListElement'][0]['item']['@type'] );

list( $c2, $h2 ) = $get( add_query_arg( 'tp_place', $prov, $base ) );
tN( 'پالایش‌شده: مسیر راهنما با نام مکان، عنوان «اخبار …»، بی JSON-LD', 200 === $c2 && false !== strpos( $h2, '<a href="' . esc_url( $base ) . '">اخبار</a>' ) && false !== strpos( $h2, '<h1>' . esc_html( topersia_g_news_title( $prov ) ) . '</h1>' ) && null === $ld( $h2 ) );
list( $c3 ) = $get( $base . 'page/' . ( (int) ceil( max( 1, count( $ids ) ) / TOPERSIA_NEWS_PER_PAGE ) + 1 ) . '/' );
tN( "صفحۀ بیرون از بازه ۴۰۴ ($c3)", 404 === $c3 );

/* سه زبان: خالی امن، رشتۀ رابط به زبان صفحه */
foreach ( array( 'en' => 'No news has been published yet.', 'ar' => 'لم تُنشر أي أخبار بعد.' ) as $l => $empty ) {
	list( $cl, $hl ) = $get( home_url( "/$l/news/" ) );
	$n = preg_match_all( '#<article class="tp-news-card">#', $hl );
	$body = preg_match( '#<div class="tp-page tp-news-page tp-news-archive".*?</main>#s', $hl, $bm ) ? $bm[0] : '';
	tN( "/$l/news/: ۲۰۰، dir درست، " . ( $n ? "$n کارت" : 'خالی امن با متن همان زبان' ) . '، بی رشتۀ رابط فارسی', 200 === $cl && false !== strpos( $body, 'dir="' . ( 'ar' === $l ? 'rtl' : 'ltr' ) . '"' )
		&& ( $n || false !== strpos( $body, $empty ) ) && ! preg_match( '#اخبار|مسیر راهنما|استان یا شهر|هنوز خبری#u', $body ) );
}

echo str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['pN']}   افتاده: {$GLOBALS['fN']}\n";
exit( $GLOBALS['fN'] ? 1 : 0 );
