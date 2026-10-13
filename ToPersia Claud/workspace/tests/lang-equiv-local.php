<?php
/**
 * هم‌ارزی زبان روی وردپرس واقعی — دستهٔ ۴ · P5-D349  (فقط خواندن؛ هیچ نوشتنی)
 *
 *   php tests/lang-equiv-local.php [--record]     (--record: خط پایهٔ شکاف را بازنویسی می‌کند)
 *
 *   ۱ ایران: ترجمهٔ en و ar منتشرشده با content_status = needs_verification؛ برای نوع کشور شکافی نیست
 *   ۲ گزارش هم‌ارزی (بی ترجمه · منتشرنشده) به تفکیک زبان و نوع؛ نسبت به خط پایهٔ tests/lang-equiv-baseline.json: هر شمار بیشتر = پست فارسیِ تازه بی ترجمهٔ منتشرشده ← رد
 *   ۳ زنجیره: /en/ و /ar/ شهر و استان اصفهان — مسیر راهنمای HTML بی کشور؛ JSON-LD: BreadcrumbList بی کشور و containedInPlace زنجیره تا ایران با نام همان زبان؛ hreflang
 */
require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST'] = TP_HOST; $_SERVER['SERVER_NAME'] = TP_HOST; $_SERVER['SERVER_PORT'] = 80; $_SERVER['REQUEST_URI'] = '/'; $_SERVER['REMOTE_ADDR'] = '127.0.0.1'; $_SERVER['REQUEST_METHOD'] = 'GET';
require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$pass = 0; $fail = 0;
function t( $label, $ok, $note = '' ) { global $pass, $fail; if ( $ok ) { $pass++; echo "  ✅ {$label}\n"; } else { $fail++; echo "  ❌ {$label}" . ( '' !== $note ? "   ← {$note}" : '' ) . "\n"; } }

echo 'START ' . gmdate( 'c' ) . "\n";

/* ── ۱ ایران ───────────────────────────────────────────────────────── */
do_action( 'wpml_switch_language', 'fa' );
$iran = (int) get_posts( array( 'post_type' => 'tp_country', 'post_status' => 'publish', 'name' => 'islamic-republic-of-iran', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) )[0];
$trs  = array();
foreach ( array( 'en', 'ar' ) as $lang ) {
	$id = $iran ? (int) apply_filters( 'wpml_object_id', $iran, 'tp_country', false, $lang ) : 0;
	$trs[ $lang ] = array( 'id' => $id, 'status' => $id ? get_post_status( $id ) : '', 'cs' => $id ? (string) get_post_meta( $id, 'content_status', true ) : '' );
}
t( 'ایران: ترجمهٔ en و ar منتشرشده با content_status = needs_verification', $iran > 0 && 'publish' === $trs['en']['status'] && 'publish' === $trs['ar']['status'] && 'needs_verification' === $trs['en']['cs'] && 'needs_verification' === $trs['ar']['cs'], json_encode( $trs ) );

$eq = topersia_i18n_equivalence();
t( 'ایران (نوع کشور): هیچ شکاف هم‌ارزی در هیچ زبانی', ! isset( $eq['en']['tp_country'] ) && ! isset( $eq['ar']['tp_country'] ), json_encode( array( isset( $eq['en']['tp_country'] ), isset( $eq['ar']['tp_country'] ) ) ) );

/* ── ۲ هم‌ارزی نسبت به خط پایه ───────────────────────────────────── */
$counts = array();
foreach ( topersia_i18n_equivalence_counts( $eq ) as $lang => $c ) {
	foreach ( $eq[ $lang ] as $type => $row ) { $counts[ $lang ][ $type ] = array( 'missing' => count( $row['missing'] ), 'unpublished' => count( $row['unpublished'] ) ); }
}
$base_file = __DIR__ . '/lang-equiv-baseline.json';
echo "  ℹ︎ شکاف هم‌ارزی فعلی: " . json_encode( topersia_i18n_equivalence_counts( $eq ), JSON_UNESCAPED_UNICODE ) . "\n";
if ( in_array( '--record', $argv, true ) ) {
	file_put_contents( $base_file, wp_json_encode( $counts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n" );
	echo "  ℹ︎ خط پایه نوشته شد: tests/lang-equiv-baseline.json\n";
}
$base = is_file( $base_file ) ? json_decode( (string) file_get_contents( $base_file ), true ) : null;
$worse = array();
foreach ( $counts as $lang => $types ) {
	foreach ( $types as $type => $c ) {
		foreach ( array( 'missing', 'unpublished' ) as $k ) {
			$b = isset( $base[ $lang ][ $type ][ $k ] ) ? (int) $base[ $lang ][ $type ][ $k ] : 0;
			if ( $c[ $k ] > $b ) { $worse[] = "{$lang}/{$type}/{$k}: {$c[$k]} > {$b}"; }
		}
	}
}
t( 'هم‌ارزی: هیچ زبان و نوعی بدتر از خط پایه نشد (پست فارسیِ تازهٔ بی ترجمهٔ منتشرشده ← رد)', is_array( $base ) && ! $worse, $base ? implode( ' | ', array_slice( $worse, 0, 4 ) ) : 'خط پایه نیست — --record' );

/* ── ۳ زنجیره در /en/ و /ar/ ───────────────────────────────────── */
/* curl مستقیم (نه wp_remote_get درون همین فرایند): صفحه را بی‌کوکی و بی‌فیلتر زبان این فرایند همان‌طور می‌گیرد که مهمان می‌گیرد */
$fetch = function ( $path ) {
	$ch = curl_init( rtrim( TP_BASE_URL, '/' ) . $path );
	curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_PROXY => '', CURLOPT_USERAGENT => 'ToPersia-lang-equiv/1.0' ) );
	$body = (string) curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	curl_close( $ch );
	return array( $code, $body );
};
$graph = function ( $html ) {
	$nodes = array();
	if ( preg_match_all( '#<script[^>]*application/ld\+json[^>]*>(.*?)</script>#su', $html, $m ) ) {
		foreach ( $m[1] as $blob ) {
			$j = json_decode( $blob, true );
			if ( isset( $j['@graph'] ) ) { foreach ( $j['@graph'] as $n ) { $nodes[] = $n; } } elseif ( is_array( $j ) ) { $nodes[] = $j; }
		}
	}
	return $nodes;
};
$iran_name = array( 'en' => 'Iran', 'ar' => 'إيران' );

foreach ( array( 'en', 'ar' ) as $lang ) {
	foreach ( array( 'city' => '/city/isfahan/', 'province' => '/province/isfahan/' ) as $kind => $p ) {
		list( $code, $html ) = $fetch( '/' . $lang . $p );
		$nodes = $graph( $html );
		$crumb = array();
		if ( preg_match( '#<p class="tp-crumb"[^>]*>(.*?)</p>#su', $html, $cm ) ) { $crumb = array_values( array_filter( array_map( 'trim', explode( '|', preg_replace( '#\s*<[^>]+>\s*#u', '|', $cm[1] ) ) ) ) ); }
		$bl = null; $place = null;
		foreach ( $nodes as $n ) {
			$ty = isset( $n['@type'] ) ? (array) $n['@type'] : array();
			if ( in_array( 'BreadcrumbList', $ty, true ) ) { $bl = $n; }
			if ( array_intersect( $ty, array( 'City', 'State', 'AdministrativeArea', 'Place' ) ) && isset( $n['containedInPlace'] ) ) { $place = $n; }
		}
		$bl_names = $bl ? array_map( function ( $i ) { return isset( $i['name'] ) ? $i['name'] : ( isset( $i['item']['name'] ) ? $i['item']['name'] : '' ); }, (array) $bl['itemListElement'] ) : array();
		$want_in  = 'city' === $kind ? '/e/province/isfahan' : '/e/country/islamic-republic-of-iran';
		$ctx      = $place ? wp_json_encode( $place['containedInPlace'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '';
		$fa_text  = preg_grep( '/[\x{0600}-\x{06FF}]/u', array_merge( $bl_names, $crumb ) );
		if ( 'en' === $lang ) { $fa_text = preg_grep( '/[\x{0600}-\x{06FF}]/u', array_merge( $bl_names, $crumb ) ); } else { $fa_text = preg_grep( '/[\x{06A9}\x{06CC}\x{067E}\x{0686}\x{0698}\x{06AF}]/u', array_merge( $bl_names, $crumb ) ); }
		t( "/{$lang}{$p}: HTTP {$code}؛ مسیر راهنمای HTML و BreadcrumbList به زبان صفحه، بی کشور؛ containedInPlace ← " . ( 'city' === $kind ? 'استان' : 'ایران' ),
			200 === $code && $bl && count( $bl_names ) >= 2 && ! $fa_text && ! in_array( $iran_name[ $lang ], $bl_names, true ) && ! in_array( $iran_name[ $lang ], $crumb, true ) && false !== strpos( $ctx, $want_in ),
			json_encode( array( 'code' => $code, 'crumb' => $crumb, 'bl' => $bl_names, 'ctx' => mb_substr( $ctx, 0, 140 ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	}
}

foreach ( array( 'en' => 'Iran', 'ar' => 'إيران' ) as $lang => $name ) {
	list( $code, $html ) = $fetch( '/' . $lang . '/country/islamic-republic-of-iran/' );
	$ok = 200 === $code && preg_match( '#<title>' . preg_quote( $name, '#' ) . '#u', $html ) && 1 === preg_match( '#<html[^>]*lang="' . $lang . '#', $html ) && false !== strpos( $html, 'tp-uid' );
	t( "صفحهٔ ایران /{$lang}/: HTTP {$code}، عنوان «{$name}»، lang={$lang}، نیازمند راستی‌آزمایی در منابع", $ok, (string) $code );
}
echo "  ℹ︎ سایت محلی `noindex, nofollow` است (پیش از راه‌اندازی)؛ پس Rank Math canonical و `<link rel=alternate hreflang>` نمی‌گذارد — فقط hreflang کلیدهای تعویض زبان هست. سنجش آن‌ها پیش‌شرط فاز ۱۰ است.\n";

echo "
END " . gmdate( 'c' ) . " — گذشته: {$pass}   افتاده: {$fail}
";
exit( $fail ? 1 : 0 );
