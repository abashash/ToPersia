<?php
/**
 * ادعاهای ۴٫۴٫۲ — زبان صفحه (انگلیسی و عربی)، پنجرۀ گالری بی خانۀ خالی،
 * JSON-LD از دروازۀ P5-D247.
 *
 * از assert.php پس از assert-441 بار می‌شود و همان شمارنده‌ها و همان دادۀ
 * آزمون را دارد. هر ادعا جهش دارد (`tp441_mut`): روی خروجی واقعی درست، روی
 * رفتار ۴٫۴٫۱ یا ورودیِ خراب نادرست.
 *
 * زبان با همان سازوکارِ وردپرس عوض می‌شود: صافی `wpml_current_language`،
 * پیام‌های خودِ `languages/topersia-{locale}.l10n.php` در `$GLOBALS['tp_l10n']`
 * (stubs.php همان را برای `__()` · `_x()` · `_n()` می‌خواند) و جهت صفحه.
 * رفتار روی صفحۀ واقعی /en/ و /ar/ در behaviour.js.
 */

echo "\n۴٫۴٫۲ — زبان صفحه · گالری · JSON-LD\n" . str_repeat( '─', 78 ) . "\n";

$root442 = dirname( __DIR__ );
$css442  = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' );
$js442   = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-zones.js' );
$main442 = file_get_contents( TP_PLUGIN_DIR . 'topersia-render.php' );

$GLOBALS['l10n442'] = array(
	'en' => require TP_PLUGIN_DIR . 'languages/topersia-en_US.l10n.php',
	'ar' => require TP_PLUGIN_DIR . 'languages/topersia-ar.l10n.php',
);

$GLOBALS['tp_lang_now'] = null;
add_filter( 'wpml_current_language', function () { return $GLOBALS['tp_lang_now']; } );
add_filter( 'wpml_element_language_code', function ( $code, $args = array() ) {
	$id = is_array( $args ) && isset( $args['element_id'] ) ? (int) $args['element_id'] : 0;
	return isset( $GLOBALS['tp442_post_lang'][ $id ] ) ? $GLOBALS['tp442_post_lang'][ $id ] : $code;
} );

/** یک کار در یک زبان — کاتالوگ، شکل جمع و جهت همان زبان؛ پس از آن فارسی. */
function tp442_lang( $lang, callable $fn, $with_catalog = true ) {

	$keep = array( $GLOBALS['tp_lang_now'], isset( $GLOBALS['tp_l10n'] ) ? $GLOBALS['tp_l10n'] : null,
		isset( $GLOBALS['tp_l10n_plural'] ) ? $GLOBALS['tp_l10n_plural'] : null, ! empty( $GLOBALS['tp_ltr'] ) );

	$GLOBALS['tp_lang_now']    = $lang;
	$GLOBALS['tp_l10n']        = ( 'fa' !== $lang && $with_catalog ) ? $GLOBALS['l10n442'][ $lang ]['messages'] : array();
	$GLOBALS['tp_l10n_plural'] = 'ar' === $lang
		? function ( $n ) { return 0 === $n ? 0 : ( 1 === $n ? 1 : ( 2 === $n ? 2 : ( $n % 100 >= 3 && $n % 100 <= 10 ? 3 : ( $n % 100 >= 11 ? 4 : 5 ) ) ) ); }
		: function ( $n ) { return 1 === $n ? 0 : 1; };
	$GLOBALS['tp_ltr']         = 'en' === $lang;

	try {
		return $fn();
	} finally {
		list( $GLOBALS['tp_lang_now'], $GLOBALS['tp_l10n'], $GLOBALS['tp_l10n_plural'], $GLOBALS['tp_ltr'] ) = $keep;
	}
}

/** رشته‌های رابطِ کاتالوگ — منبع فارسی، بی جانشین (آن‌ها عیناً در خروجی نمی‌آیند). */
$ui442 = array();
foreach ( array_keys( $GLOBALS['l10n442']['en']['messages'] ) as $k ) {
	if ( false !== strpos( $k, "\0" ) ) { continue; }
	$k = false !== strpos( $k, "\4" ) ? substr( $k, strpos( $k, "\4" ) + 1 ) : $k;
	if ( false === strpos( $k, '%' ) && ! preg_match( '/^[\s۰-۹٪]+$/u', $k ) ) { $ui442[ $k ] = true; }
}

/* دادۀ آزمون — نام و متنِ خودِ موجودیت‌ها رابط نیست؛ در هر زبانی همان است که ذخیره شده */
$data442 = array();
foreach ( $GLOBALS['tp_posts'] as $p ) { if ( ! empty( $p['title'] ) ) { $data442[ $p['title'] ] = true; } }
array_walk_recursive( $GLOBALS['tp_meta'], function ( $v ) use ( &$data442 ) { if ( is_string( $v ) ) { $data442[ trim( $v ) ] = true; } } );

/**
 * رشتۀ رابطِ فارسی که هنوز در خروجی مانده — متن گره یا مقدار ویژگی، عیناً.
 * واژه‌ای که ترجمه‌اش در آن زبان همان است («مارس» · «آبان» در عربی) شمرده نمی‌شود.
 */
function tp442_ui_left( $html, $ui, $data, $lang = 'en' ) {
	$left = array();
	$msgs = $GLOBALS['l10n442'][ $lang ]['messages'];
	foreach ( array_keys( $ui ) as $m ) {
		if ( isset( $data[ $m ] ) ) { continue; }
		if ( ( isset( $msgs[ $m ] ) && $msgs[ $m ] === $m ) || in_array( $m, array_map( function ( $k ) use ( $msgs ) { return $msgs[ $k ]; }, preg_grep( '/\x04' . preg_quote( $m, '/' ) . '$/u', array_keys( $msgs ) ) ), true ) ) { continue; }
		$e = htmlspecialchars( $m, ENT_QUOTES, 'UTF-8' );
		if ( false !== strpos( $html, '>' . $e . '<' ) || false !== strpos( $html, '="' . $e . '"' ) ) { $left[] = $m; }
	}
	return $left;
}

/* ══ کاتالوگ — هر رشتۀ فارسیِ رابط انگلیسی و عربی دارد  P5-D146 ══════════ */

$i18n442 = escapeshellarg( $root442 . '/scripts/tp-i18n.php' ) . ' --check';
$tmp442  = tempnam( sys_get_temp_dir(), 'tp442' ) . '.php';
$tab442  = require $root442 . '/scripts/i18n/topersia-render.php';
unset( $tab442['نقشه'] );
file_put_contents( $tmp442, '<?php return ' . var_export( $tab442, true ) . ';' );

$code_real442 = tp_run_php( $i18n442 );
putenv( 'TP_I18N_TABLE=' . $tmp442 );
$code_mut442 = tp_run_php( $i18n442 );
putenv( 'TP_I18N_TABLE' );
@unlink( $tmp442 );

t( 'کاتالوگ: هر رشتۀ فارسیِ پرونده‌های عمومی ترجمۀ انگلیسی و عربی دارد؛ جانشین‌ها همان؛ خروجی‌ها با جدول یکی (scripts/tp-i18n.php --check)؛ جهش — یک مدخل کم — می‌افتد  P5-D146',
	0 === $code_real442 && 1 === $code_mut442, "واقعی {$code_real442} · جهش {$code_mut442}" );

t( 'کاتالوگ: افزونه کاتالوگ خودش را روی `init` بار می‌کند — load_plugin_textdomain( \'topersia\' ) از languages/',
	false !== strpos( $main442, "load_plugin_textdomain( 'topersia', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );" )
	&& false !== strpos( $main442, "add_action( 'init', 'topersia_render_textdomain', 0 );" )
	&& is_file( TP_PLUGIN_DIR . 'languages/topersia-en_US.mo' ) && is_file( TP_PLUGIN_DIR . 'languages/topersia-ar.mo' ) );

/* ══ صفحۀ انگلیسی و عربی — استان ۲۰ ═══════════════════════════════════════ */

$pageEN442 = tp442_lang( 'en', function () { return r( 20, 'tp_page' ); } );
$pageAR442 = tp442_lang( 'ar', function () { return r( 20, 'tp_page' ); } );
$pageRaw442 = tp442_lang( 'en', function () { return r( 20, 'tp_page' ); }, false );   /* جهش: بی کاتالوگ — همان ۴٫۴٫۱ */

tp441_mut( 'زبان: صفحۀ انگلیسی هیچ رشتۀ رابط فارسی ندارد — عنوان بخش، برچسب فیلد و واحد، واژه‌نامه، نوار بخش‌ها، برگۀ «بخش‌ها»، دکمه‌ها، aria  P5-D146',
	function ( $h ) use ( $ui442, $data442 ) { return strlen( $h ) > 3000 && ! tp442_ui_left( $h, $ui442, $data442 ); },
	$pageEN442, $pageRaw442, implode( ' · ', array_slice( tp442_ui_left( $pageEN442, $ui442, $data442 ), 0, 8 ) ) );

$pageRawAR442 = tp442_lang( 'ar', function () { return r( 20, 'tp_page' ); }, false );
tp441_mut( 'زبان: صفحۀ عربی هیچ رشتۀ رابط فارسی ندارد  P5-D146',
	function ( $h ) use ( $ui442, $data442 ) { return strlen( $h ) > 3000 && ! tp442_ui_left( $h, $ui442, $data442, 'ar' ); },
	$pageAR442, $pageRawAR442, implode( ' · ', array_slice( tp442_ui_left( $pageAR442, $ui442, $data442, 'ar' ), 0, 8 ) ) );

$pageFA442 = r( 20, 'tp_page' );
$hero442 = function ( $h ) { return preg_match( '#<p class="tp-eyebrow">([^<]*)</p><h1>([^<]*)</h1>#u', $h, $m ) ? array( $m[1], $m[2] ) : array( '', '' ); };

tp441_mut( 'زبان: تیتر انگلیسی همان نام رسمی — «Isfahan Province»، بی پیشوند «استان»؛ eyebrow «Province» (تا ۴٫۴٫۱ «استان Isfahan Province»)  P5-D224 · P5-D218',
	function ( $h ) use ( $hero442 ) { return array( 'Province', 'Isfahan Province' ) === $hero442( $h ); },
	$pageEN442, $pageRaw442, implode( ' | ', $hero442( $pageEN442 ) ) );

tp441_mut( 'زبان: تیتر عربی «محافظة أصفهان» و eyebrow «محافظة»؛ فارسی همان «استان اصفهان» با eyebrow «استان»  P5-D224',
	function ( $pair ) use ( $hero442 ) { return array( 'محافظة', 'محافظة أصفهان' ) === $hero442( $pair[0] ) && array( 'استان', 'استان اصفهان' ) === $hero442( $pair[1] ); },
	array( $pageAR442, $pageFA442 ), array( $pageRawAR442, $pageFA442 ), implode( ' | ', $hero442( $pageAR442 ) ) );

tp441_mut( 'زبان: نام‌های دو زبانِ دیگر در سربرگ انگلیسی — فارسی و عربی، نه همان عنوانِ انگلیسی؛ جداکنندۀ «, »  ۱٫۶ · P5-D240',
	function ( $h ) { return false !== strpos( $h, '<p class="tp-hero__alt"><span lang="fa">استان اصفهان</span>, <em lang="ar">محافظة أصفهان</em></p>' ); },
	$pageEN442, str_replace( '<span lang="fa">استان اصفهان</span>', '<span lang="fa">Isfahan Province</span>', $pageEN442 ) );

tp441_mut( 'زبان: رقم به خطِ صفحه — انگلیسی «5,120,850»، عربی «٥٬١٢٠٬٨٥٠»، فارسی «۵٬۱۲۰٬۸۵۰»',
	function ( $pair ) { return false !== strpos( $pair[0], '5,120,850' ) && false !== strpos( $pair[1], '٥٬١٢٠٬٨٥٠' ) && false !== strpos( $pair[2], '۵٬۱۲۰٬۸۵۰' ); },
	array( $pageEN442, $pageAR442, $pageFA442 ), array( $pageEN442, $pageFA442, $pageFA442 ) );   /* جهش: رقم فارسی روی صفحۀ عربی — رفتار ۴٫۴٫۱ */

tp441_mut( 'زبان: تاریخ راستی‌آزمایی — فارسی شمسی «۱۲ خرداد ۱۴۰۴»، انگلیسی «June 2, 2025»، عربی «٢ يونيو ٢٠٢٥»  P5-D268',
	function ( $pair ) { return false !== strpos( $pair[0], 'June 2, 2025' ) && false !== strpos( $pair[1], '٢ يونيو ٢٠٢٥' ) && false !== strpos( $pair[2], '۱۲ خرداد ۱۴۰۴' ); },
	array( $pageEN442, $pageAR442, $pageFA442 ), array( $pageRaw442, $pageAR442, $pageFA442 ) );

tp441_mut( 'زبان: شمار با شکل جمعِ هر زبان — «14 items» · «١٤ عنصرًا» (عربی ۱۱ تا ۹۹) · «۱۴ مورد»؛ دو شکلِ شمارِ پنجرۀ فهرست روی خودش',
	function ( $pair ) {
		return false !== strpos( $pair[0], 'data-tp-count-all="14 items" data-tp-count-of="%1$s of %2$s">14 items<' )
			&& false !== strpos( $pair[1], 'data-tp-count-all="١٤ عنصرًا" data-tp-count-of="%1$s من %2$s">١٤ عنصرًا<' )
			&& false !== strpos( $pair[2], '>۱۴ مورد<' );
	},
	array( $pageEN442, $pageAR442, $pageFA442 ), array( $pageRaw442, $pageAR442, $pageFA442 ) );

$seasonEN442 = tp442_lang( 'en', function () { return r( 40, 'tp_seasons' ); } );
tp441_mut( 'زبان: جدول فصل‌ها در انگلیسی ماه میلادیِ همان فصل (زمستان ← December · January · February)، نه نام ماه شمسی',
	function ( $h ) { return false !== strpos( $h, '<div class="tp-season__h">Winter</div><div class="tp-month tp-is-on">December</div><div class="tp-month tp-is-on">January</div><div class="tp-month tp-is-on">February</div>' ); },
	$seasonEN442, r( 40, 'tp_seasons' ) );

/* ══ نام به زبان صفحه — کاشیِ مسیرِ صفحۀ انگلیسی (باگ ۴٫۴٫۱) ══════════════ */

$GLOBALS['tp_posts'][90] = array( 'type' => 'tp_route', 'title' => 'The Silk Road in Iran', 'url' => 'https://topersia.com/en/route/silk-road-in-iran/' );
$GLOBALS['tp_meta'][90]  = array( 'official_name_fa' => 'جادۀ ابریشم در ایران' );   /* WPML روی ترجمه رونوشت کرده */
$GLOBALS['tp442_post_lang'][90] = 'en';

tp441_mut( 'نام: ترجمۀ انگلیسی با `official_name_fa` رونوشت‌شده در صفحۀ انگلیسی نام انگلیسی خودش را می‌گیرد، نه نام فارسی (کاشی مسیر /en/ «جادۀ ابریشم در ایران» بود)',
	function ( $name ) { return 'The Silk Road in Iran' === $name; },
	tp442_lang( 'en', function () { return topersia_g_name( 90 ); } ), topersia_g_name( 90 ) );

unset( $GLOBALS['tp_posts'][90], $GLOBALS['tp_meta'][90], $GLOBALS['tp442_post_lang'][90] );

/* ══ هوا — برچسب پس از کش، به زبان درخواست ════════════════════════════════ */

$wx442 = array( 'days' => array( array( 'd' => 'امروز', 't' => 20, 'n' => 9, 'i' => 'rain', 's' => 'آفتابی', 'k' => 'rain' ), array( 'd' => 'فردا', 's' => 'آفتابی', 'k' => 'clear' ) ), 'note' => 'به‌روزرسانی: چند دقیقه پیش', 'src' => 'MET Norway' );
tp441_mut( 'هوا: پاسخ کش‌شده (یک بار برای همۀ زبان‌ها) برچسب روز، وضعیت و یادداشت را از کاتالوگ و از کلیدِ وضعیت (`k`) می‌گیرد — Today · Rainy · Tomorrow · Sunny',
	function ( $o ) { return 'Today' === $o['days'][0]['d'] && 'Rainy' === $o['days'][0]['s'] && 'Tomorrow' === $o['days'][1]['d'] && 'Sunny' === $o['days'][1]['s'] && 'Updated a few minutes ago' === $o['note'] && 20 === $o['days'][0]['t']; },
	tp442_lang( 'en', function () use ( $wx442 ) { return topersia_g_wx_localize( $wx442 ); } ), $wx442 );

/* ══ جاوااسکریپت — پیام‌ها با t()، رقم با خطِ صفحه ════════════════════════ */

preg_match_all( "/\\b(?:t|tr)\\( '([^']*[\\x{0600}-\\x{06FF}][^']*)' \\)/u", $js442, $jsm442 );
$jsList442 = preg_match( '/function topersia_render_js_strings\(\) \{\s*return array\((.*?)\);/su', $main442, $jm ) && preg_match_all( "/'([^']+)'/u", $jm[1], $jl ) ? $jl[1] : array();
$jsBare442 = array();
foreach ( preg_split( '/\R/', $js442 ) as $ln => $line ) {
	if ( preg_match( '#^\s*(//|\*|/\*)#', $line ) ) { continue; }
	if ( preg_match_all( "/'([^']*[\\x{0600}-\\x{06FF}][^']*)'/u", $line, $bm ) ) {
		foreach ( $bm[1] as $s ) {
			if ( ! preg_match( "/\\b(?:t|tr)\\( '" . preg_quote( $s, '/' ) . "' \\)/u", $line ) && ! preg_match( '/^[۰-۹٠-٩]+$/u', $s ) ) { $jsBare442[] = ( $ln + 1 ) . ': ' . $s; }
		}
	}
}
$jsUsed442 = array_values( array_unique( $jsm442[1] ) );
sort( $jsUsed442 );
$jsDecl442 = array_values( array_unique( $jsList442 ) );
sort( $jsDecl442 );

t( 'جاوااسکریپت: هر پیام با t( \'…\' ) و همه در `topersia_render_js_strings()`، و برعکس؛ هیچ رشتۀ فارسیِ دیگری در کد؛ رقم فقط از `digits()` با `TopersiaG.digits`',
	$jsUsed442 && $jsUsed442 === $jsDecl442 && ! $jsBare442
	&& false === strpos( $js442, "'۰۱۲۳۴۵۶۷۸۹'[ d ]" ) && false !== strpos( $js442, "var DIGITS = { fa: '۰۱۲۳۴۵۶۷۸۹', ar: '٠١٢٣٤٥٦٧٨٩' }[ CFG.digits || 'fa' ] || '';" )
	&& false !== strpos( $main442, "'i18n'   => topersia_render_js_i18n()," ) && false !== strpos( $main442, "'digits' => topersia_render_js_digits()," ),
	implode( ' · ', array_merge( array_diff( $jsUsed442, $jsDecl442 ), array_diff( $jsDecl442, $jsUsed442 ), array_slice( $jsBare442, 0, 4 ) ) ) );

/* ══ پنجرۀ گالری — بی خانۀ خالی ═══════════════════════════════════════════ */

tp441_mut( 'گالری: شبکه `grid-auto-flow:row dense` — کاشی پهن که در ستون آخر جا نمی‌شود دیگر خانۀ سیاه جا نمی‌گذارد (۱۹۲۰ پنج ستون، ۳۹۰ دو ستون)؛ بی‌خانه بودن در هر پهنا در behaviour.js',
	function ( $css ) { return (bool) preg_match( '/html body \.tp-page \.tp-gallery-modal__grid\{grid-auto-rows:1fr;grid-auto-flow:row dense\}/', tp441_added( $css ) ); },
	$css442, str_replace( ';grid-auto-flow:row dense', '', $css442 ) );

/* ══ JSON-LD از دروازه — P5-D247 · P5-D145 ═════════════════════════════════ */

if ( ! function_exists( 'topersia_schema_uid' ) ) {
	function topersia_schema_uid( $id ) { return 'https://topersia.com/entity/' . (int) $id; }
}
if ( ! function_exists( 'topersia_p9_stops' ) ) {
	function topersia_p9_stops( $route_id = null ) { return isset( $GLOBALS['tp442_stops'] ) ? $GLOBALS['tp442_stops'] : array(); }
}

/** همان شکلی که موتور شِما می‌سازد — نیاکان بی دروازه. */
$ld442 = function ( $pid, $ancestors ) {
	$items = array( array( '@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => 'https://topersia.com' ) );
	foreach ( $ancestors as $a ) {
		$items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => get_the_title( $a ), 'item' => get_permalink( $a ) );
	}
	$items[] = array( '@type' => 'ListItem', 'position' => count( $items ) + 1, 'name' => get_the_title( $pid ) );
	return array(
		array( '@type' => 'BreadcrumbList', '@id' => get_permalink( $pid ) . '#breadcrumb', 'itemListElement' => $items ),
		array( '@type' => 'TouristDestination', '@id' => topersia_schema_uid( $pid ), 'containedInPlace' => array( '@id' => topersia_schema_uid( end( $ancestors ) ) ) ),
	);
};

/* پیکربندی موتور برای استان: یک پله، رابطۀ ۷۱ کشور ← استان */
$cfg442 = array( 'parents' => array( array( 'relation' => 71 ) ) );

$ldPub442 = topersia_g_schema_graph( $ld442( 20, array( 10 ) ), 20, $cfg442 );
$GLOBALS['tp_posts'][10]['status'] = 'draft';
$ldRaw442   = $ld442( 20, array( 10 ) );
$ldDraft442 = topersia_g_schema_graph( $ldRaw442, 20, $cfg442 );
unset( $GLOBALS['tp_posts'][10]['status'] );

tp441_mut( 'JSON-LD: والدِ پیش‌نویس (کشورِ استان) نه در BreadcrumbList می‌آید نه در containedInPlace — صافی `topersia_schema_graph` با همان دروازه (موتور شِما بی دروازه می‌پیمود)  P5-D247',
	function ( $g ) { $j = wp_json_encode( $g ); return false === strpos( $j, 'ایران' ) && false === strpos( $j, '/iran/' ) && ! isset( $g[1]['containedInPlace'] ) && 2 === count( $g[0]['itemListElement'] ); },
	$ldDraft442, $ldRaw442 );

tp441_mut( 'JSON-LD: BreadcrumbList بی کشور (خانه › استان اصفهان؛ D338) ولی containedInPlace همان والد (کشور) می‌ماند — نامرئی؛ شماره‌ها از نو',
	function ( $g ) {
		$names = array_map( function ( $i ) { return $i['name']; }, $g[0]['itemListElement'] );
		return array( 'خانه', 'استان اصفهان' ) === $names && false === strpos( wp_json_encode( $g[0] ), '/iran/' )
			&& isset( $g[1]['containedInPlace'] ) && array( '@id' => topersia_schema_uid( 10 ) ) === $g[1]['containedInPlace'] && array( 1, 2 ) === array_column( $g[0]['itemListElement'], 'position' );
	},
	$ldPub442, $ldDraft442 );

/* پلۀ skip (شهرستان بی‌صفحه) — حتی منتشرشده در BreadcrumbList نمی‌آید، ولی پیمودن از آن می‌گذرد تا استان */
$GLOBALS['tp_rel']['73']['parents'][27] = array( 22 );   /* کاشان ← شهرستان کاشان ← استان (۷۲) */
$ldCity442 = topersia_g_schema_graph( $ld442( 27, array( 20 ) ), 27, array( 'parents' => array( array( 'relation' => 73, 'skip' => true ), array( 'relation' => 72 ) ) ) );
t( 'JSON-LD: پلۀ `skip` موتور (شهرستان) در BreadcrumbList نمی‌آید و هر ListItem جز آخری نشانی دارد؛ containedInPlace استان',
	array( 'خانه', 'استان اصفهان', 'شهر کاشان' ) === array_column( $ldCity442[0]['itemListElement'], 'name' )
	&& ! empty( $ldCity442[0]['itemListElement'][1]['item'] ) && array( '@id' => topersia_schema_uid( 20 ) ) === $ldCity442[1]['containedInPlace'],
	wp_json_encode( $ldCity442[0]['itemListElement'] ) );

$GLOBALS['tp442_stops'] = array(
	array( 'position' => 1, 'id' => 60, 'title' => 'میدان نقش جهان', 'url' => 'https://topersia.com/naqsh-e-jahan/', 'note' => '' ),
	array( 'position' => 2, 'id' => 72, 'title' => 'تقویم پیش‌نویس استان', 'url' => 'https://topersia.com/calendar-draft/', 'note' => '' ),
);
$trip442 = array( array( '@type' => 'TouristTrip', '@id' => topersia_schema_uid( 66 ), 'itinerary' => array(
	'@type' => 'ItemList', 'numberOfItems' => 2, 'itemListElement' => array_map( function ( $s ) {
		return array( '@type' => 'ListItem', 'position' => $s['position'], 'item' => array( '@type' => 'TouristDestination', '@id' => topersia_schema_uid( $s['id'] ), 'name' => $s['title'], 'url' => $s['url'] ) );
	}, $GLOBALS['tp442_stops'] ) ) ) );

tp441_mut( 'JSON-LD: ایستگاهِ پیش‌نویسِ مسیر از itinerary برداشته می‌شود و شماره و numberOfItems از نو  P5-D247',
	function ( $g ) { $l = $g[0]['itinerary']; return 1 === $l['numberOfItems'] && 1 === count( $l['itemListElement'] ) && 1 === $l['itemListElement'][0]['position'] && false === strpos( wp_json_encode( $g ), 'پیش‌نویس' ); },
	topersia_g_schema_graph( $trip442, 66 ), $trip442 );

unset( $GLOBALS['tp442_stops'] );
