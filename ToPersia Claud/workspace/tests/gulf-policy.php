<?php
/**
 * گیت سیاست خلیج فارس — P5-D526 · P5-D527 (دستۀ 48 بخش ۳).
 *
 *   php tests/gulf-policy.php                     ← ایستا: رجیستری، کد و کاتالوگ، مصرف‌کننده‌ها، با جهش (از assert-48 هم اجرا می‌شود)
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/gulf-policy.php
 *                                                 ← محلی: پست‌های پایگاه‌داده در همۀ زبان‌ها + HTML رندرشده
 *
 * فقط گزارش؛ هیچ چیز را عوض نمی‌کند. نام ممنوع در هر جا ← قرمز، با فهرست کامل (شناسۀ پست، نوع، زبان، فیلد)
 * برای تأیید کاربر. منبع نام‌ها و نام‌های ممنوع فقط رجیستری هسته است (topersia-geo-names.php).
 * کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص.
 */

$gp_root = dirname( __DIR__ );
$gp_wp   = defined( 'ABSPATH' ) && function_exists( 'add_action' );
if ( ! $gp_wp && ! defined( 'TP_GULF_POLICY' ) ) {
	define( 'TP_GULF_POLICY', true );
}
$gp_reg = $gp_root . '/plugin/topersia-core/includes/topersia-geo-names.php';
if ( ! function_exists( 'topersia_geo_features' ) ) {
	if ( ! is_file( $gp_reg ) ) { echo "  ⚠ رجیستری نیست: $gp_reg\n"; exit( 2 ); }
	require $gp_reg;
}

$GLOBALS['gpP'] = 0; $GLOBALS['gpF'] = 0;
function gp_t( $label, $ok, $note = '' ) {
	if ( $ok ) { $GLOBALS['gpP']++; echo "  ✅ $label\n"; return; }
	$GLOBALS['gpF']++; echo "  ❌ $label   " . mb_substr( (string) $note, 0, 600 ) . "\n";
}
/* جهش: بررسی باید روی ورودی سالم سبز و روی ورودی جهش‌یافته قرمز شود */
function gp_mut( $label, $check, $good, $bad ) {
	$g = (bool) $check( $good ); $b = (bool) $check( $bad );
	gp_t( $label, $g, 'روی ورودی سالم قرمز' );
	gp_t( "  ↳ جهش می‌افتد: $label", ! $b, 'جهش گرفته نشد' );
}
$gp_re = topersia_geo_forbidden_regex( 'persian_gulf' );
function gp_hits( $re, $text ) {
	return preg_match_all( $re, (string) $text, $m ) ? array_values( array_unique( $m[0] ) ) : array();
}

echo "\nسیاست خلیج فارس (P5-D526) — " . ( $gp_wp ? 'محلی: پایگاه‌داده و HTML' : 'ایستا: رجیستری، کد، کاتالوگ' ) . "\n" . str_repeat( '─', 78 ) . "\n";

if ( ! $gp_wp ) {

	/* ── ۱. رجیستری: ده زبان، عیناً متن کاربر ── */
	$want = array(
		'fa' => 'خلیج فارس', 'en' => 'Persian Gulf', 'ar' => 'الخليج الفارسي', 'zh' => '波斯湾', 'fr' => 'Golfe Persique',
		'de' => 'Persischer Golf', 'it' => 'Golfo Persico', 'ru' => 'Персидский залив', 'es' => 'Golfo Pérsico', 'tr' => 'Fars Körfezi',
	);
	$f   = topersia_geo_features();
	$got = isset( $f['persian_gulf']['names'] ) ? $f['persian_gulf']['names'] : array();
	gp_mut( 'رجیستری: persian_gulf با نام ده زبان، عیناً', function ( $n ) use ( $want ) { return $n === $want; }, $got, array_merge( $got, array( 'ar' => 'الخليج' ) ) );
	gp_t( 'topersia_geo_name: ar_SA ← عربی، en-US ← انگلیسی، زبان ناشناخته ← انگلیسی، شناسۀ ناشناخته ← خالی',
		'الخليج الفارسي' === topersia_geo_name( 'persian_gulf', 'ar_SA' ) && 'Persian Gulf' === topersia_geo_name( 'persian_gulf', 'en-US' )
		&& 'Persian Gulf' === topersia_geo_name( 'persian_gulf', 'xx' ) && '' === topersia_geo_name( 'nope', 'fa' ) && 'خلیج فارس' === topersia_geo_name( 'persian_gulf', 'fa_IR' ) );
	$ml = topersia_geo_map_labels();
	gp_t( 'topersia_geo_map_labels: fa · en · ar از همان نام‌ها + کلید پایدار name:en / name:latin / name:fa',
		isset( $ml['persian_gulf'] ) && $ml['persian_gulf']['names'] === array( 'fa' => $want['fa'], 'en' => $want['en'], 'ar' => $want['ar'] )
		&& array( 'name:en', 'name:latin', 'name:fa' ) === array_keys( $ml['persian_gulf']['match'] ) );

	/* ── ۲. regex نام ممنوع: هر نام ممنوع و گونه‌اش گرفته شود، هیچ نام رسمی نه ── */
	$miss = array();
	foreach ( topersia_geo_forbidden() as $n ) {
		foreach ( array( $n, mb_strtoupper( $n ), str_replace( ' ', "\u{200C}", $n ), str_replace( ' ', '  ', $n ) ) as $v ) {
			if ( ! gp_hits( $gp_re, "x $v y" ) ) { $miss[] = $v; }
		}
	}
	gp_t( 'regex ممنوع: هر ' . count( topersia_geo_forbidden() ) . ' نام ممنوع با بزرگی حرف، نیم‌فاصله و فاصلۀ دوتایی گرفته می‌شود', ! $miss, implode( ' | ', $miss ) );
	$false = array_filter( $want, function ( $n ) use ( $gp_re ) { return (bool) gp_hits( $gp_re, $n ); } );
	gp_t( 'regex ممنوع هیچ نام رسمی (ده زبان) را نمی‌گیرد', ! $false, implode( ' | ', $false ) );
	gp_t( 'رجیستری فقط خواندنی: بی apply_filters، بی update_option', false === strpos( (string) file_get_contents( $gp_reg ), 'apply_filters' ) && false === strpos( (string) file_get_contents( $gp_reg ), 'update_option' ) );

	/* ── ۳. کد و کاتالوگ: هیچ نام ممنوع در افزونه‌ها و اسکریپت‌ها ── */
	$files = array();
	foreach ( array( 'plugin', 'scripts' ) as $dir ) {
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $gp_root . '/' . $dir, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $file ) {
			$p = str_replace( '\\', '/', $file->getPathname() );
			if ( preg_match( '#\.(php|js|css|json|po|pot|svg|txt|md|xml|html)$#i', $p ) && false === strpos( $p, '/node_modules/' ) && $p !== str_replace( '\\', '/', $gp_reg ) ) {
				$files[] = $p;
			}
		}
	}
	$found = array();
	foreach ( $files as $p ) {
		$h = gp_hits( $gp_re, (string) file_get_contents( $p ) );
		if ( $h ) { $found[] = substr( $p, strlen( $gp_root ) + 1 ) . ' ← ' . implode( ', ', $h ); }
	}
	gp_t( 'نام ممنوع در کد، رشته‌ها، کاتالوگ PO/l10n، FIELDMAP و SVG: صفر (' . count( $files ) . ' پرونده در plugin/ و scripts/)', count( $files ) > 50 && ! $found, implode( ' || ', $found ) );
	gp_mut( 'جهش اسکنر: رشتۀ رابط با نام ممنوع گرفته می‌شود', function ( $s ) use ( $gp_re ) { return ! gp_hits( $gp_re, $s ); },
		"__( 'کرانه‌های خلیج فارس', 'topersia' )", "__( 'Arabian  Gulf coast', 'topersia' )" );

	/* ── ۴. کاتالوگ برابر رجیستری ── */
	$cat = include $gp_root . '/scripts/i18n/topersia-render.php';
	$cat_ok = function ( $c ) use ( $want ) { return isset( $c['خلیج فارس'] ) && $want['en'] === $c['خلیج فارس']['en'] && $want['ar'] === $c['خلیج فارس']['ar']; };
	gp_mut( 'کاتالوگ render: «خلیج فارس» ← en و ar همان رجیستری', $cat_ok, $cat, array_merge( $cat, array( 'خلیج فارس' => array( 'en' => 'Gulf', 'ar' => 'الخليج' ) ) ) );
	$po_bad = array();
	foreach ( array( 'en_US' => 'en', 'ar' => 'ar' ) as $loc => $l ) {
		$po = (string) @file_get_contents( $gp_root . "/plugin/topersia-render/languages/topersia-$loc.po" );
		if ( ! preg_match( '/msgid "خلیج فارس"\s*\nmsgstr "([^"]*)"/u', $po, $m ) || $want[ $l ] !== $m[1] ) { $po_bad[] = "$loc: " . ( $m[1] ?? 'نیست' ); }
		$l10n = (string) @file_get_contents( $gp_root . "/plugin/topersia-render/languages/topersia-$loc.l10n.php" );
		if ( false === strpos( $l10n, "'خلیج فارس'=>'" . $want[ $l ] . "'" ) && false === strpos( $l10n, "'خلیج فارس' => '" . $want[ $l ] . "'" ) ) { $po_bad[] = "$loc.l10n.php"; }
	}
	gp_t( 'PO و l10n.php (en_US · ar): msgstr «خلیج فارس» همان رجیستری', ! $po_bad, implode( ' | ', $po_bad ) );

	/* ── ۵. مصرف‌کننده‌ها فقط از رجیستری ── */
	$zjs  = (string) file_get_contents( $gp_root . '/plugin/topersia-render/assets/topersia-g-zones.js' );
	$rend = (string) file_get_contents( $gp_root . '/plugin/topersia-render/topersia-render.php' );
	$gmap = (string) file_get_contents( $gp_root . '/plugin/topersia-render/includes/topersia-g-map.php' );
	$core = (string) file_get_contents( $gp_root . '/plugin/topersia-core/topersia-core.php' );
	$js_ok = function ( $s ) {
		return false !== strpos( $s, "var GEO = ( CFG.geo && 'object' === typeof CFG.geo ) ? CFG.geo : {};" ) && false === strpos( $s, 'GULF_NAME' )
			&& ! preg_match( '/Persian Gulf|الخليج الفارسي|\\\\u0627\\\\u0644\\\\u062e\\\\u0644\\\\u064a\\\\u062c/u', $s ) && 2 === substr_count( $s, 'gulfWrap( ' ) - 1;
	};
	gp_mut( 'نقشه (zones.js): برچسب از CFG.geo، بی نام نوشته‌شده، هر دو سبک از gulfWrap', $js_ok, $zjs, $zjs . "\nvar GULF_NAME = { en: 'Persian Gulf' };" );
	gp_mut( 'render: TopersiaG.geo ← topersia_geo_map_labels()', function ( $s ) { return false !== strpos( $s, "'geo'    => function_exists( 'topersia_geo_map_labels' ) ? topersia_geo_map_labels() : array()," ); },
		$rend, str_replace( "'geo'    => function_exists", "'geox'   => function_exists", $rend ) );
	gp_mut( 'واژه‌نامۀ ۴۰ (نوع جزیره): مقدار persian_gulf نامش را از topersia_geo_name به زبان صفحه می‌گیرد', function ( $s ) {
		return false !== strpos( $s, "\$geo = topersia_geo_name( \$value, function_exists( 'topersia_g_page_lang' ) ? topersia_g_page_lang() : 'fa' );" );
	}, $gmap, str_replace( '$geo = topersia_geo_name(', '$geo = (', $gmap ) );
	gp_t( 'هسته رجیستری را بار می‌کند (پس از topersia-geo.php)', 1 === preg_match( "#require_once __DIR__ \. '/includes/topersia-geo\.php';\s*\nrequire_once __DIR__ \. '/includes/topersia-geo-names\.php';#", $core ) );
	$lit = array();
	foreach ( $files as $p ) {
		if ( preg_match( '#/plugin/[^/]+/(includes|assets|templates)/.*\.(php|js)$#', $p ) && ! preg_match( '#/assets/map/#', $p ) ) {
			$s = (string) file_get_contents( $p );
			if ( preg_match( '/Persian Gulf|الخليج الفارسي|Golfe Persique|波斯湾/u', $s ) ) { $lit[] = substr( $p, strlen( $gp_root ) + 1 ); }
		}
	}
	gp_t( 'هیچ کد نمایشی (includes · assets · templates) نام رسمی زبان دیگر را خودش نمی‌نویسد — فقط رجیستری و کاتالوگ', ! $lit, implode( ', ', $lit ) );

} else {

	if ( 'local' !== wp_get_environment_type() ) { echo "  فقط محلی\n"; exit( 2 ); }
	global $wpdb;
	$lang_of = function ( $id, $type ) {
		$d = apply_filters( 'wpml_post_language_details', null, $id );
		return is_array( $d ) && ! empty( $d['language_code'] ) ? $d['language_code'] : '—';
	};
	$snip = function ( $text, $hit ) {
		$t = wp_strip_all_tags( (string) $text ); $i = mb_stripos( $t, $hit );
		return false === $i ? '' : mb_substr( $t, max( 0, $i - 40 ), mb_strlen( $hit ) + 80 );
	};
	$scan_row = function ( $row ) use ( $gp_re ) { return gp_hits( $gp_re, $row ); };
	gp_t( 'جهش اسکنر پایگاه‌داده: ردیف ساختگی با نام ممنوع گرفته می‌شود و ردیف سالم نه', $scan_row( 'ساحل الخليج  العربي' ) && ! $scan_row( 'ساحل خلیج فارس' ) );

	/* ── پایگاه‌داده: هر پست (هر نوع، هر زبان) — عنوان، متن، چکیده، همۀ متاها (Rank Math هم)؛ alt پیوست ── */
	$rows = $wpdb->get_results( "SELECT ID, post_type, post_status, post_title, post_content, post_excerpt FROM {$wpdb->posts} WHERE post_type NOT IN ('revision','auto-draft') AND post_status NOT IN ('trash','auto-draft')" );
	$hits = array(); $info = array(); $wrong = array(); $n_pub = 0;
	foreach ( $rows as $r ) {
		$pub = 'publish' === $r->post_status || ( 'attachment' === $r->post_type && 'inherit' === $r->post_status );
		$n_pub += $pub ? 1 : 0;
		$fields = array( 'post_title' => $r->post_title, 'post_content' => $r->post_content, 'post_excerpt' => $r->post_excerpt );
		foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $r->ID ) ) as $m ) {
			$fields[ 'meta:' . $m->meta_key ] = $m->meta_value;
		}
		$lang = null;
		foreach ( $fields as $k => $v ) {
			if ( '' === (string) $v ) { continue; }
			$h = gp_hits( $gp_re, $v );
			$lang = $lang ?? $lang_of( (int) $r->ID, $r->post_type );
			if ( $h ) {
				$line = sprintf( '#%d %s %s [%s] %s ← %s «%s»', $r->ID, $r->post_type, $r->post_status, $lang, $k, implode( ', ', $h ), $snip( $v, $h[0] ) );
				if ( $pub ) { $hits[] = $line; } else { $info[] = $line; }
			}
			/* نام رسمی زبانی دیگر در پست این زبان (مثلاً «خلیج فارس» فارسی در پست عربی) — گزارش، نه قرمز */
			if ( $pub && in_array( $lang, array( 'en', 'ar' ), true ) && false === strpos( $k, 'meta:_' ) ) {
				foreach ( array( 'fa', 'en', 'ar' ) as $o ) {
					if ( $o !== $lang && false !== mb_strpos( (string) $v, topersia_geo_name( 'persian_gulf', $o ) ) && ! ( 'en' === $o && 'ar' === $lang ) ) {
						$wrong[] = sprintf( '#%d %s [%s] %s ← «%s» (زبان %s)', $r->ID, $r->post_type, $lang, $k, topersia_geo_name( 'persian_gulf', $o ), $o );
					}
				}
			}
		}
	}
	$terms = $wpdb->get_results( "SELECT t.term_id, t.name, tt.taxonomy, tt.description FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id" );
	foreach ( $terms as $t ) {
		$h = gp_hits( $gp_re, $t->name . ' ' . $t->description );
		if ( $h ) { $hits[] = sprintf( 'term #%d %s ← %s', $t->term_id, $t->taxonomy, implode( ', ', $h ) ); }
	}
	gp_t( "پایگاه‌داده: نام ممنوع در محتوای منتشرشده (همۀ زبان‌ها؛ عنوان، متن، چکیده، متا و Rank Math، alt؛ واژه‌ها): صفر — $n_pub پست منتشرشده/پیوست، " . count( $terms ) . ' واژه', ! $hits, "\n      " . implode( "\n      ", $hits ) );
	echo '  ℹ پیش‌نویس و خصوصی با نام ممنوع (قرمز نمی‌کند، برای تأیید): ' . ( $info ? "\n      " . implode( "\n      ", $info ) : 'هیچ' ) . "\n";
	echo '  ℹ نام رسمی زبانی دیگر در پست en/ar (قرمز نمی‌کند، برای تأیید): ' . ( $wrong ? "\n      " . implode( "\n      ", array_slice( array_unique( $wrong ), 0, 60 ) ) : 'هیچ' ) . "\n";

	/* ── HTML رندرشده: سه زبان × صفحه‌ها؛ JSON-LD هم درون همین HTML است ── */
	$get = function ( $url ) {
		$ch = curl_init( $url ); curl_setopt_array( $ch, array( CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 120, CURLOPT_FOLLOWLOCATION => 0, CURLOPT_PROXY => '' ) );
		$b = curl_exec( $ch ); $c = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE ); curl_close( $ch ); return array( $c, (string) $b );
	};
	$island = get_posts( array( 'post_type' => 'tp_island', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) );
	$paths  = array( '/', '/province/isfahan/', '/city/isfahan/', '/news/' );
	if ( $island ) { $paths[] = wp_make_link_relative( get_permalink( $island[0] ) ); }
	$bad = array(); $geo_bad = array(); $seen = 0; $geo_n = 0;
	foreach ( array( '' => 'fa', 'en' => 'en', 'ar' => 'ar' ) as $pre => $l ) {
		foreach ( $paths as $p ) {
			$u = home_url( ( $pre ? "/$pre" : '' ) . $p );
			list( $c, $h ) = $get( $u );
			if ( 200 !== $c ) { echo "  ℹ $u ← $c (سنجیده نشد)\n"; continue; }
			$seen++;
			$hh = gp_hits( $gp_re, $h );
			if ( $hh ) { $bad[] = "$u ← " . implode( ', ', $hh ); }
			if ( preg_match( '/var TopersiaG = (\{.*?\});\s*\n/s', $h, $m ) ) {
				$j = json_decode( $m[1], true ); $geo_n++;
				if ( ! isset( $j['geo']['persian_gulf']['names'][ $l ] ) || topersia_geo_name( 'persian_gulf', $l ) !== $j['geo']['persian_gulf']['names'][ $l ] ) { $geo_bad[] = $u; }
			}
		}
	}
	gp_t( "HTML (اول، استان، شهر، /news/، جزیره؛ fa · en · ar؛ $seen صفحه): بی نام ممنوع، JSON-LD هم", $seen >= 10 && ! $bad, implode( ' | ', $bad ) );
	gp_t( "TopersiaG.geo روی صفحه‌های نقشه‌دار: نام همان زبان از رجیستری ($geo_n صفحه)", $geo_n >= 3 && ! $geo_bad, implode( ' ', $geo_bad ) );
}

echo str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['gpP']}   افتاده: {$GLOBALS['gpF']}\n";
if ( ! $gp_wp ) { exit( $GLOBALS['gpF'] ? 1 : 0 ); }
exit( $GLOBALS['gpF'] ? 1 : 0 );
