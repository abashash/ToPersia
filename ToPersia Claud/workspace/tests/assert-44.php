<?php
/**
 * دستهٔ 44 — مانده‌های 43، عنوان هر رابطه (P5-D504)، پایان موج ۲ (P5-D485) و سند انحراف TA-I18N (P5-D505).
 * از assert.php بار می‌شود؛ PHP خالص با stub. بسته روی وردپرس واقعی: tests/w44-local.php.
 */

echo "\nدستهٔ 44 — مانده‌های 43  ·  عنوان رابطه P5-D504  ·  موج ۲ P5-D485  ·  TA-I18N P5-D505\n" . str_repeat( '─', 78 ) . "\n";

$keep44 = array( $GLOBALS['tp_posts'], $GLOBALS['tp_meta'], isset( $GLOBALS['tp_rel'] ) ? $GLOBALS['tp_rel'] : null, isset( $GLOBALS['tp_filters'] ) ? $GLOBALS['tp_filters'] : null, isset( $GLOBALS['tp2g_status'] ) ? $GLOBALS['tp2g_status'] : null, isset( $GLOBALS['tp_archive'] ) ? $GLOBALS['tp_archive'] : null );

$w44_root = dirname( __DIR__ );
$w44_cat  = include $w44_root . '/scripts/i18n/topersia-render.php';
require_once $w44_root . '/scripts/lib/tp-rel-coverage-lib.php';

/* ── مانده ۲: TOPERSIA_SITE_ID مشتق از مبدأ ثابت ───────────────────────────── */

$w44_core   = (string) file_get_contents( $w44_root . '/plugin/topersia-core/topersia-core.php' );
$w44_schema = (string) file_get_contents( $w44_root . '/plugin/topersia-schema/topersia-schema.php' );

/* بلوک تعریف واقعی را از منبع برمی‌دارد و در فرایند جدا اجرا می‌کند، تا ثابت مبدأ را بشود عوض کرد */
$w44_block = function ( $src, $name ) {
	return preg_match( "/if \( ! defined\( '" . $name . "' \) \) \{\n.*?\n\}\n/s", $src, $m ) ? $m[0] : '';
};
$w44_ids = function ( $code, $origin ) {
	$f = tempnam( sys_get_temp_dir(), 'w44' );
	file_put_contents( $f, "<?php\n" . ( null === $origin ? '' : "define( 'TOPERSIA_CANONICAL_ORIGIN', " . var_export( $origin, true ) . " );\n" ) . $code . "\necho json_encode( array( TOPERSIA_SITE_ID, defined( 'TOPERSIA_UID_BASE' ) ? TOPERSIA_UID_BASE : '' ), JSON_UNESCAPED_SLASHES );\n" );
	$o = (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $f ) );
	@unlink( $f );
	return json_decode( $o, true );
};
$w44_core_ok = function ( $src ) use ( $w44_block, $w44_ids ) {
	$code = $w44_block( $src, 'TOPERSIA_CANONICAL_ORIGIN' ) . $w44_block( $src, 'TOPERSIA_UID_BASE' ) . $w44_block( $src, 'TOPERSIA_SITE_ID' );
	return array( 'https://topersia.com', 'https://topersia.com/e' ) === $w44_ids( $code, null ) && array( 'http://t.test', 'http://t.test/e' ) === $w44_ids( $code, 'http://t.test/' );
};
tp441_mut( 'هسته: SITE_ID از مبدأ ثابت؛ با مبدأ دیگر همراه uid عوض می‌شود و اسلش پایانی ندارد — جهش: نشانی نوشته‌شده در کد', $w44_core_ok, $w44_core,
	str_replace( "define( 'TOPERSIA_SITE_ID', rtrim( TOPERSIA_CANONICAL_ORIGIN, '/' ) );", "define( 'TOPERSIA_SITE_ID', 'https://topersia.com' );", $w44_core ) );

$w44_schema_ok = function ( $src ) use ( $w44_block, $w44_ids ) {
	$code = $w44_block( $src, 'TOPERSIA_SITE_ID' );
	return array( 'http://t.test', '' ) === $w44_ids( $code, 'http://t.test/' ) && array( 'https://topersia.com', '' ) === $w44_ids( $code, null );
};
tp441_mut( 'شِما اگر زودتر بار شود همان مبدأ را می‌گیرد؛ بی هسته همان پیش‌فرض هسته — جهش: بی‌اعتنا به مبدأ', $w44_schema_ok, $w44_schema,
	str_replace( "define( 'TOPERSIA_SITE_ID', defined( 'TOPERSIA_CANONICAL_ORIGIN' ) ? rtrim( TOPERSIA_CANONICAL_ORIGIN, '/' ) : 'https://topersia.com' );", "define( 'TOPERSIA_SITE_ID', 'https://topersia.com' );", $w44_schema ) );
tp441_mut( 'پیش‌فرض شِما با پیش‌فرض هسته یکی می‌ماند — جهش: پیش‌فرض دیگر', $w44_schema_ok, $w44_schema, str_replace( "rtrim( TOPERSIA_CANONICAL_ORIGIN, '/' ) : 'https://topersia.com' );", "rtrim( TOPERSIA_CANONICAL_ORIGIN, '/' ) : 'https://www.topersia.com' );", $w44_schema ) );

/* مصرف‌کننده‌ها: جز تعریف و توضیح، فقط هشت جا و همه در شِما */
$w44_uses = function ( array $extra = array() ) use ( $w44_root ) {
	$hits = array();
	foreach ( array( 'plugin', 'scripts' ) as $dir ) {
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $w44_root . '/' . $dir, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $file ) {
			if ( 'php' !== $file->getExtension() ) { continue; }
			foreach ( (array) file( $file->getPathname() ) as $line ) {
				if ( false === strpos( $line, 'TOPERSIA_SITE_ID' ) || preg_match( '/^\s*\*/', $line ) || preg_match( "/defined\(\s*'TOPERSIA_SITE_ID'/", $line ) || preg_match( "/define\(\s*'TOPERSIA_SITE_ID'/", $line ) ) { continue; }
				$b = basename( $file->getPathname() );
				$hits[ $b ] = ( $hits[ $b ] ?? 0 ) + substr_count( $line, 'TOPERSIA_SITE_ID' );
			}
		}
	}
	foreach ( $extra as $b => $n ) { $hits[ $b ] = ( $hits[ $b ] ?? 0 ) + $n; }
	return $hits;
};
tp441_mut( 'مصرف‌کنندهٔ SITE_ID: هشت جا، همه در شِما — جهش: مصرف تازه در افزونهٔ دیگر', function ( $h ) { return array( 'topersia-schema.php' => 8 ) === $h; }, $w44_uses(), $w44_uses( array( 'topersia-g-zones.php' => 1 ) ), wp_json_encode( $w44_uses() ) );

/* ── مانده ۴: uid نمونهٔ شهرستان ─────────────────────────────────────────────── */

$w44_samples = (string) file_get_contents( $w44_root . '/scripts/tp-samples.php' );
$w44_county  = function ( $s ) { return false !== strpos( $s, "'post_name' => 'sample-county'" ) && false !== strpos( $s, "topersia_uid_build( 'county/sample-county' )" ); };
tp441_mut( 'نمونهٔ شهرستان نامک لاتین و uid یکسان برای هر زبان دارد — جهش: بی نامک', $w44_county, $w44_samples, str_replace( "'post_name' => 'sample-county'", "'post_title' => 'x'", $w44_samples ) );

/* ── مانده ۵: آزمون بازیابی با میزبان uid، پس از P5-D499 ───────────────────── */

$w44_restore = (string) file_get_contents( $w44_root . '/scripts/tp-restore-test.php' );
$w44_rt_ok   = function ( $s ) { return false !== strpos( $s, '$uid_before === $uid_after' ) && false !== strpos( $s, "SUBSTRING_INDEX( meta_value, '/e/', -1 )" ); };
tp441_mut( 'آزمون بازیابی مسیر uid را پیش و پس از جابه‌جایی میزبان می‌سنجد — جهش: بی آن سنجش', $w44_rt_ok, $w44_restore, str_replace( '$uid_before === $uid_after', 'true', $w44_restore ) );
$w44_rt_key = function ( $core ) use ( $p499_run ) {
	return true === $p499_run( $core, 'https://topersia.com', "topersia_uid_key( str_replace( 'https://topersia.com', 'http://restore-test.topersia.local', 'https://topersia.com/e/city/x' ) ) === topersia_uid_key( 'https://topersia.com/e/city/x' )" );
};
tp441_mut( 'تطبیق با مسیر است: عوض‌شدن میزبان روی کپی موقت uid را نمی‌شکند — جهش: کلید از نشانی کامل', $w44_rt_key, $w44_core, str_replace( "return '' === \$path ? trim( (string) \$uid ) : \$path;", 'return trim( (string) $uid );', $w44_core ) );

/* ── P5-D504: عنوان هر رابطه ─────────────────────────────────────────────── */

$w44_relmap = topersia_g_relmap();

/* عنوان هر ردیف گروه «مرتبط» و ترجمهٔ سه‌زبانه‌اش؛ عدد و جای‌نگهدار ندارد تا با تعداد صفر نشکند */
$w44_titles = function ( $map, $cat ) {
	$bad = array();
	foreach ( $map as $type => $groups ) {
		foreach ( (array) ( $groups['related']['rels'] ?? array() ) as $r ) {
			$fa = (string) ( $r['title'] ?? '' );
			if ( '' === $fa || preg_match( '/[%0-9۰-۹]/u', $fa ) ) { $bad[] = $type . ':' . $r['id'] . ':عنوان'; continue; }
			if ( empty( $cat[ $fa ]['en'] ) || empty( $cat[ $fa ]['ar'] ) || preg_match( '/[پچژگکی]/u', $cat[ $fa ]['ar'] ) ) { $bad[] = $type . ':' . $r['id'] . ':کاتالوگ'; }
		}
	}
	return $bad;
};
$w44_rels_total = 0;
foreach ( $w44_relmap as $g ) { $w44_rels_total += count( (array) ( $g['related']['rels'] ?? array() ) ); }
$w44_mm = $w44_relmap; unset( $w44_mm['tp_protected_area']['related']['rels'][0]['title'] );
$w44_cm = $w44_cat; unset( $w44_cm[ $w44_relmap['tp_protected_area']['related']['rels'][0]['title'] ] );
t( 'هر ردیف «مرتبط» عنوان فارسی و ترجمهٔ انگلیسی و عربی دارد، بی عدد — جهش: حذف عنوان یا ترجمه می‌افتد',
	$w44_rels_total > 80 && array() === $w44_titles( $w44_relmap, $w44_cat ) && array( 'tp_protected_area:103:عنوان' ) === $w44_titles( $w44_mm, $w44_cat ) && array() !== $w44_titles( $w44_relmap, $w44_cm ), implode( ' · ', array_slice( $w44_titles( $w44_relmap, $w44_cat ), 0, 6 ) ) );

/* تکراری در یک صفحه: میان عنوان‌های یک نوع و با سرفصل گروه‌های دیگر همان نوع، در هر زبان */
$w44_dups = function ( $map, $cat ) {
	$bad = array();
	foreach ( $map as $type => $groups ) {
		if ( empty( $groups['related']['rels'] ) ) { continue; }
		foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
			$tr   = function ( $fa ) use ( $l, $cat ) { return 'fa' === $l ? $fa : (string) ( $cat[ $fa ][ $l ] ?? $fa ); };
			$seen = array();
			foreach ( $groups as $key => $g ) {
				if ( 'related' !== $key && isset( $g['title'] ) && '' !== (string) $g['title'] ) { $seen[ $tr( $g['title'] ) ] = true; }
			}
			foreach ( $groups['related']['rels'] as $r ) {
				$h = $tr( (string) ( $r['title'] ?? '' ) );
				if ( isset( $seen[ $h ] ) ) { $bad[] = "{$type}:{$l}:{$h}"; }
				$seen[ $h ] = true;
			}
		}
	}
	return $bad;
};
$w44_dm = $w44_relmap; $w44_dm['tp_protected_area']['related']['rels'][1]['title'] = $w44_dm['tp_protected_area']['related']['rels'][0]['title'];
t( 'هیچ عنوانی در یک نوع، در هیچ زبانی، دو بار نیست — جهش: عنوان تکراری می‌افتد',
	array() === $w44_dups( $w44_relmap, $w44_cat ) && array() !== $w44_dups( $w44_dm, $w44_cat ), implode( ' · ', array_slice( $w44_dups( $w44_relmap, $w44_cat ), 0, 6 ) ) );

/* رندر: منطقهٔ حفاظت‌شده با سه رابطه (#103 · #238 · #251) */
$f2_world( $f2_reg + array(
	103 => array( 'id' => 103, 'name' => 'pa_natural', 'type' => 'many_to_many', 'parent' => 'tp_protected_area', 'child' => 'tp_natural_feature', 'parent_label' => '', 'child_label' => '' ),
	238 => array( 'id' => 238, 'name' => 'pa_festival', 'type' => 'many_to_many', 'parent' => 'tp_protected_area', 'child' => 'tp_festival', 'parent_label' => '', 'child_label' => '' ),
	251 => array( 'id' => 251, 'name' => 'pa_sports', 'type' => 'many_to_many', 'parent' => 'tp_protected_area', 'child' => 'tp_sports_event', 'parent_label' => '', 'child_label' => '' ),
) );
$GLOBALS['tp2g_status'] = 'active';
$f2_post( 8500, 'tp_protected_area', 'منطقهٔ آزمون' );
$f2_post( 8501, 'tp_natural_feature', 'عارضهٔ آزمون' );
$f2_post( 8502, 'tp_sports_event', 'مسابقهٔ آزمون' );
$w44_fest = array();
for ( $i = 0; $i < 15; $i++ ) { $f2_post( 8510 + $i, 'tp_festival', 'جشنوارهٔ آزمون ' . $i ); $w44_fest[] = 8510 + $i; }
$GLOBALS['tp_rel'][103] = array( 'children' => array( 8500 => array( 8501 ) ), 'parents' => array( 8501 => array( 8500 ) ) );
$GLOBALS['tp_rel'][238] = array( 'children' => array( 8500 => $w44_fest ), 'parents' => array() );
$GLOBALS['tp_rel'][251] = array( 'children' => array( 8500 => array( 8502 ) ), 'parents' => array( 8502 => array( 8500 ) ) );
unset( $GLOBALS['tp_archive'] );

$w44_cards = r( 8500, 'tp_relgrid', array( 'rel' => 'related' ) );
$w44_h3    = function ( $html ) { preg_match_all( '#<section id="(tp-related[^"]*)"[^>]*>.*?<h3[^>]*>(.*?)</h3>#us', $html, $m, PREG_SET_ORDER ); $o = array(); foreach ( $m as $x ) { $o[ $x[1] ] = trim( wp_strip_all_tags( $x[2] ) ); } return $o; };
$w44_want  = array();
foreach ( $w44_relmap['tp_protected_area']['related']['rels'] as $n => $r ) { $w44_want[ 0 === $n ? 'tp-related' : 'tp-related-' . ( $n + 1 ) ] = $r['title']; }
t( 'هر رابطه کارت و سرفصل خودش را دارد؛ نخستین شناسهٔ tp-related — جهش: یک سرفصل یکسان می‌افتد', ( function () use ( $w44_h3, $w44_cards, $w44_want ) {
	$one = '<section id="tp-related"><h3>مرتبط</h3></section>';
	return $w44_want === $w44_h3( $w44_cards ) && $w44_want !== $w44_h3( $one );
} )(), wp_json_encode( $w44_h3( $w44_cards ), JSON_UNESCAPED_UNICODE ) );

/* کاشی اضافه پنهان؛ پیوند «همه» فقط با بایگانی و فقط وقتی اعضا بیش از سقف‌اند */
$w44_section = function ( $html, $id ) { return preg_match( '#<section id="' . preg_quote( $id, '#' ) . '".*?</section>#us', $html, $m ) ? $m[0] : ''; };
$w44_fest_h  = $w44_section( $w44_cards, 'tp-related-2' );
t( 'سقف ۱۲ کاشی برای هر عنوان: ۱۵ عضو ← ۳ پنهان با دکمه؛ عنوان کم‌عضو دکمه ندارد', 3 === substr_count( $w44_fest_h, 'data-tp-rest-item' ) && 15 === substr_count( $w44_fest_h, 'جشنوارهٔ آزمون' ) && false !== strpos( $w44_fest_h, 'tp-more' ) && false === strpos( $w44_section( $w44_cards, 'tp-related' ), 'data-tp-rest-item' ), (string) substr_count( $w44_fest_h, 'data-tp-rest-item' ) );
t( 'پیوند «همه» بی بایگانی نیست', false === strpos( $w44_cards, 'tp-more--rail' ) );
$GLOBALS['tp_archive']['tp_festival'] = 'https://topersia.com/festivals/';
$w44_arch = r( 8500, 'tp_relgrid', array( 'rel' => 'related' ) );
t( 'با بایگانی، پیوند «همه» فقط برای همان عنوان — جهش: بایگانی نوع کم‌عضو پیوندی نمی‌سازد', false !== strpos( $w44_section( $w44_arch, 'tp-related-2' ), 'tp-more--rail' ) && false !== strpos( $w44_section( $w44_arch, 'tp-related-2' ), 'https://topersia.com/festivals/' ) && false === strpos( $w44_section( $w44_arch, 'tp-related' ), 'tp-more--rail' ) );
$GLOBALS['tp_archive']['tp_natural_feature'] = 'https://topersia.com/nature/';
t( 'عنوان کم‌عضو حتی با بایگانی پیوند ندارد', false === strpos( $w44_section( r( 8500, 'tp_relgrid', array( 'rel' => 'related' ) ), 'tp-related' ), 'tp-more--rail' ) );
unset( $GLOBALS['tp_archive'] );

/* خالی‌امن: رابطهٔ بی عضو کارت نمی‌سازد؛ بی عضو هیچ */
$w44_keep_rel = $GLOBALS['tp_rel'];
unset( $GLOBALS['tp_rel'][103] );
$w44_part = r( 8500, 'tp_relgrid', array( 'rel' => 'related' ) );
t( 'رابطهٔ بی عضو سرفصل نمی‌گذارد؛ شناسهٔ tp-related به نخستین کارت می‌رسد', false === strpos( $w44_part, $w44_relmap['tp_protected_area']['related']['rels'][0]['title'] ) && false !== strpos( $w44_part, 'id="tp-related"' ) && false === strpos( $w44_part, 'id="tp-related-3"' ) );
unset( $GLOBALS['tp_rel'][238], $GLOBALS['tp_rel'][251] );
t( 'بی هیچ عضو: رشتهٔ خالی، نه سرفصل بی‌کاشی', '' === r( 8500, 'tp_relgrid', array( 'rel' => 'related' ) ) );
$GLOBALS['tp_rel'] = $w44_keep_rel;

/* دو رابطه با یک عنوان: یک کارت، اعضای هر دو */
$w44_same = $w44_relmap;
$w44_same['tp_protected_area']['related']['rels'][2]['title'] = $w44_same['tp_protected_area']['related']['rels'][0]['title'];
$GLOBALS['tp_filters']['topersia_g_relmap'] = array( function () use ( $w44_same ) { return $w44_same; } );
$w44_merged = r( 8500, 'tp_relgrid', array( 'rel' => 'related' ) );
unset( $GLOBALS['tp_filters']['topersia_g_relmap'] );
t( 'دو رابطهٔ هم‌عنوان یک کارت می‌شوند با اعضای هر دو', 2 === count( $w44_h3( $w44_merged ) ) && false !== strpos( $w44_section( $w44_merged, 'tp-related' ), 'عارضهٔ آزمون' ) && false !== strpos( $w44_section( $w44_merged, 'tp-related' ), 'مسابقهٔ آزمون' ), wp_json_encode( $w44_h3( $w44_merged ) ) );

/* کارت‌ها فرزند مستقیم ستون‌اند: بی لایهٔ بسته‌بند، چون چیدمان موبایل display:contents دارد */
t( 'کارت‌ها بی بسته‌بند کنار هم‌اند؛ مودال فهرست نمی‌سازند', 0 === strpos( trim( $w44_cards ), '<section id="tp-related"' ) && '' === topersia_g_list_modal( 8500, 'related' ) );

/* شهرِ کاشی: جاذبه فقط رابطهٔ شهر ← نوع خودش را می‌پرسد و نوع دیگر هیچ‌کدام از چهار را (بازبینی دستهٔ 44) */
$f2_post( 8541, 'tp_city', 'شهر کاشی' );
$f2_post( 8546, 'tp_city', 'شهر طعمه' );
$f2_post( 8542, 'tp_handicraft', 'صنایع‌دستی کاشی' );
$f2_post( 8543, 'tp_monument', 'بنای کاشی' );
$f2_post( 8544, 'tp_religious_site', 'زیارتگاه کاشی' );
$f2_post( 8545, 'tp_museum', 'موزهٔ کاشی' );
$f2_post( 8547, 'tp_ancient_site', 'محوطهٔ کاشی' );
$w44_tile_rel = $GLOBALS['tp_rel'];
/* هر یک از چهار رابطه: نوع خودش ← «شهر کاشی»، هر کاشی دیگر ← «شهر طعمه». پرسیدن رابطهٔ نوع دیگر طعمه را برمی‌گرداند */
foreach ( array( 84 => 8543, 86 => 8544, 88 => 8545, 93 => 8547 ) as $w44_rid => $w44_own ) {
	$w44_rest = array_values( array_diff( array( 8542, 8543, 8544, 8545, 8547 ), array( $w44_own ) ) );
	$GLOBALS['tp_rel'][ $w44_rid ] = array( 'children' => array( 8541 => array( $w44_own ), 8546 => $w44_rest ), 'parents' => array( $w44_own => array( 8541 ) ) + array_fill_keys( $w44_rest, array( 8546 ) ) );
}
$w44_tile_want = array( 8542 => '', 8543 => 'شهر کاشی', 8544 => 'شهر کاشی', 8545 => 'شهر کاشی', 8547 => 'شهر کاشی' );
$w44_tile_src  = preg_match( '/\tfunction topersia_g_tile_place\( \$post_id \) \{\n.*?\n\t\}\n/s', (string) file_get_contents( $w44_root . '/plugin/topersia-render/includes/topersia-g-zones.php' ), $w44_m ) ? $w44_m[0] : '';
/* eval فقط روی متن خودِ تابع از پروندهٔ مخزن، با نام تازه (همان روش assert-496)؛ ورودی بیرونی ندارد */
$w44_tile_ok   = function ( $src ) use ( $w44_tile_want ) {
	static $n = 0;
	if ( '' === $src ) { return false; }
	$fn = 'topersia_g_tile_place_w44_' . ( ++$n );
	eval( str_replace( 'function topersia_g_tile_place(', 'function ' . $fn . '(', $src ) );
	$o = array();
	foreach ( array_keys( $w44_tile_want ) as $id ) { topersia_g_rel_memo_reset(); $o[ $id ] = $fn( $id ); }
	return $w44_tile_want === $o;
};
tp441_mut( 'کاشی مرتبط: هر چهار جاذبه فقط رابطهٔ شهر نوع خودش را می‌پرسد، نوع دیگر هیچ — جهش: هر چهار رابطه برای هر کاشی', $w44_tile_ok, $w44_tile_src,
	str_replace( "if ( isset( \$attraction[ \$own ] ) ) {\n\t\t\t\$specs[] = array( 'id' => \$attraction[ \$own ], 'dir' => 'parents' );\n\t\t}", "foreach ( \$attraction as \$rid ) { \$specs[] = array( 'id' => \$rid, 'dir' => 'parents' ); }", $w44_tile_src ) );
tp441_mut( 'کاشی مرتبط — جهش: موزه رابطهٔ زیارتگاه (۸۶) را بپرسد', $w44_tile_ok, $w44_tile_src, str_replace( "'tp_museum' => 88", "'tp_museum' => 86", $w44_tile_src ) );
t( 'تابع واقعی همان را می‌دهد: هر چهار جاذبه ← شهر خودش، صنایع‌دستی ← هیچ', ( function () use ( $w44_tile_want ) { $o = array(); foreach ( array_keys( $w44_tile_want ) as $id ) { topersia_g_rel_memo_reset(); $o[ $id ] = topersia_g_tile_place( $id ); } return $w44_tile_want === $o; } )() );
$GLOBALS['tp_rel'] = $w44_tile_rel;
topersia_g_rel_memo_reset();

/* ── P5-D485: موج ۲، پایان ─────────────────────────────────────────────── */

$f2_world( $f2_reg );
$GLOBALS['tp2g_status'] = 'active';
$w44_types = array( 'tp_sports_event', 'tp_seasonal_event', 'tp_religious_event' );
$w44_map   = array_filter( topersia_g_map(), function ( $f ) { return ! empty( $f['wave2c'] ); } );
$w44_xml   = (string) @file_get_contents( __DIR__ . '/../../project-knowledge/ToPersia-wpml-config-v1_13.xml' );
$w44_lock  = json_decode( (string) @file_get_contents( $w44_root . '/docs/ops/STRUCTURE-LOCK.json' ), true );
$w44_skin  = json_decode( (string) @file_get_contents( $w44_root . '/reference/Jetengine_Skin.json' ), true );

$w44_csv = array(); $w44_rows = array();
foreach ( (array) file( $w44_root . '/docs/ops/STRUCTURE-FREEZE-PROPOSAL.csv' ) as $line ) {
	$c = str_getcsv( $line );
	if ( ! isset( $c[3] ) || ! in_array( $c[1], $w44_types, true ) ) { continue; }
	$w44_rows[ $c[1] ][ $c[3] ] = ( $w44_rows[ $c[1] ][ $c[3] ] ?? 0 ) + 1;
	/* ردیف رابطه «rel92 parents (#92)» است؛ «religious_site_etiquette» و «related_museum» فیلدند (بازبینی دستهٔ 44) */
	if ( 'DISPLAY' === $c[3] && ! tp_freeze_row_is_rel( $c[2] ) && 'is_recurring' !== $c[2] ) { $w44_csv[ $c[2] ] = $c[1]; }
}
$w44_jet = array();
foreach ( (array) ( $w44_skin['meta_boxes'] ?? array() ) as $box ) {
	foreach ( (array) ( $box['meta_fields'] ?? array() ) as $f ) {
		if ( 'field' === ( $f['object_type'] ?? '' ) ) {
			$n = $f['name'];
			$w44_jet[ $n ] = array( 'type' => $f['type'], 'types' => array_merge( $w44_jet[ $n ]['types'] ?? array(), (array) ( $box['args']['allowed_post_type'] ?? array() ) ) );
		}
	}
}
t( 'ردیف‌های CSV همان جدول بریف: ورزشی ۶، فصلی ۴، مذهبی ۱۰؛ STORE-ONLY هر کدام ۱', array( 6, 4, 10, 1, 1, 1 ) === array( $w44_rows['tp_sports_event']['DISPLAY'] ?? 0, $w44_rows['tp_seasonal_event']['DISPLAY'] ?? 0, $w44_rows['tp_religious_event']['DISPLAY'] ?? 0, $w44_rows['tp_sports_event']['STORE-ONLY'] ?? 0, $w44_rows['tp_seasonal_event']['STORE-ONLY'] ?? 0, $w44_rows['tp_religious_event']['STORE-ONLY'] ?? 0 ), wp_json_encode( $w44_rows ) );
$w44_cover = function ( $map ) use ( $w44_csv, $w44_jet ) {
	$bad = array();
	foreach ( $w44_csv as $k => $type ) {
		if ( empty( $map[ $k ] ) ) { $bad[] = 'نقشه:' . $k; }
		if ( empty( $w44_jet[ $k ] ) || ! in_array( $type, $w44_jet[ $k ]['types'], true ) ) { $bad[] = 'متاباکس:' . $k; }
	}
	return $bad;
};
$w44_mut = $w44_map; unset( $w44_mut['optimal_conditions'] );
t( 'هر کلید DISPLAY سه نوع در نقشه و متاباکس همان نوع؛ ۱۰ کلید — جهش: کلیدی حذف شود می‌افتد',
	array() === $w44_cover( $w44_map ) && 10 === count( $w44_map ) && 10 === count( $w44_csv ) && array( 'نقشه:optimal_conditions' ) === $w44_cover( $w44_mut ), implode( ' · ', $w44_cover( $w44_map ) ) );
t( 'is_recurring تکرار نشد: همان نگاشت دستهٔ 40، بیرون از این موج', ! empty( topersia_g_map()['is_recurring']['p5d470'] ) && empty( topersia_g_map()['is_recurring']['wave2c'] ) && ! isset( $w44_map['is_recurring'] ) );

$w44_class = array( 'spectator_access_note' => 'translate', 'peak_start_week' => 'copy', 'peak_end_week' => 'copy', 'optimal_conditions' => 'translate', 'predictability_note' => 'translate', 'annual_date_rule' => 'translate', 'public_participation_note' => 'translate', 'visitor_etiquette_note' => 'translate', 'photography_note' => 'translate', 'dress_code_note' => 'translate' );
$w44_wpml  = function ( $xml ) use ( $w44_class ) {
	$bad = array();
	foreach ( $w44_class as $k => $c ) { if ( false === strpos( $xml, '<custom-field action="' . $c . '">' . $k . '</custom-field>' ) ) { $bad[] = $k; } }
	return $bad;
};
t( 'WPML: متن translate و عدد copy، کلید به کلید با v1_13 — جهش: کلاسی عوض شود می‌افتد',
	'' !== $w44_xml && array() === $w44_wpml( $w44_xml ) && array( 'peak_end_week' ) === $w44_wpml( str_replace( '"copy">peak_end_week<', '"translate">peak_end_week<', $w44_xml ) ), implode( ' · ', $w44_wpml( $w44_xml ) ) );
$w44_lock_ok = function ( $lock ) use ( $w44_map, $w44_jet, $w44_class ) {
	$bad = array();
	foreach ( $w44_map as $k => $f ) {
		$e = $lock['fields'][ $k ] ?? null;
		if ( ! $e || $e['fieldmap_zone'] !== $f['zone'] || $e['fieldmap_kind'] !== $f['kind'] || $w44_class[ $k ] !== $e['wpml'] || $e['jet_type'] !== $w44_jet[ $k ]['type'] ) { $bad[] = $k; }
	}
	return $bad;
};
$w44_lm = $w44_lock; unset( $w44_lm['fields']['photography_note'] );
t( 'STRUCTURE-LOCK: نوع، ناحیه و کلاس هر ۱۰ کلید؛ شمار برابر — جهش: ردیفی حذف شود می‌افتد',
	is_array( $w44_lock ) && array() === $w44_lock_ok( $w44_lock ) && array( 'photography_note' ) === $w44_lock_ok( $w44_lm ) && $w44_lock['count']['fields'] === count( $w44_lock['fields'] ), implode( ' · ', $w44_lock_ok( (array) $w44_lock ) ) );

$w44_labels = array_column( $w44_map, 'label' );
$w44_miss   = function ( $cat ) use ( $w44_labels ) {
	$bad = array();
	foreach ( $w44_labels as $fa ) { if ( empty( $cat[ $fa ]['en'] ) || empty( $cat[ $fa ]['ar'] ) || preg_match( '/[پچژگکی]/u', $cat[ $fa ]['ar'] ) ) { $bad[] = $fa; } }
	return $bad;
};
$w44_cm2 = $w44_cat; unset( $w44_cm2['آداب حضور'] );
t( 'برچسب هر فیلد در کاتالوگ انگلیسی و عربی — جهش: برچسبی حذف شود می‌افتد', array() === $w44_miss( $w44_cat ) && array( 'آداب حضور' ) === $w44_miss( $w44_cm2 ), implode( ' · ', $w44_miss( $w44_cat ) ) );

$w44_ids2 = array( 'tp_sports_event' => 8531, 'tp_seasonal_event' => 8532, 'tp_religious_event' => 8533 );
$w44_all  = array();
/* مقدار هر کلید یکتاست: دو عدد متفاوت و متن غنی با نشانه‌گذاری، تا جابه‌جایی یا افتادن مقدار دیده شود (بازبینی دستهٔ 44) */
$w44_num  = array( 'peak_start_week' => '47', 'peak_end_week' => '51' );
foreach ( $w44_ids2 as $type => $id ) {
	$f2_post( $id, $type, 'نمونهٔ ' . $type );
	$meta = array( 'content_status' => 'verified', 'last_verified_date' => '2026-10-20', 'source_references' => array( array( 'source_name' => 'منبع آزمون', 'source_url' => 'https://example.org/', 'source_row_status' => 'active' ) ) );
	foreach ( $w44_csv as $k => $t2 ) {
		if ( $t2 === $type ) { $meta[ $k ] = $w44_num[ $k ] ?? ( 'rich' === $w44_map[ $k ]['kind'] ? '<p>مقدار <strong>' . $k . '</strong></p>' : 'مقدار ' . $k ); }
	}
	$GLOBALS['tp_meta'][ $id ] = $meta;
	$w44_all[ $type ] = r( $id, 'tp_overview' ) . r( $id, 'tp_badges' ) . topersia_g_visit_block( $id );
	$f2_post( $id + 10, $type, 'خالی ' . $type );
	$GLOBALS['tp_meta'][ $id + 10 ] = array();
}
/* هر برچسب با مقدار خودش: چیپ «برچسب: مقدار» پیوسته؛ در «درباره» مقدار پس از h3 همان برچسب و پیش از h3 بعدی یا پایان مقاله (بازبینی دوم دستهٔ 44) */
$w44_full = function ( $pages ) use ( $w44_csv, $w44_map, $w44_num ) {
	foreach ( $w44_csv as $k => $type ) {
		$f = $w44_map[ $k ];
		$h = $pages[ $type ];
		$v = 'number' === $f['kind'] ? topersia_g_fa( $w44_num[ $k ] ) : ( 'rich' === $f['kind'] ? '<strong>' . $k . '</strong>' : 'مقدار ' . $k );
		if ( 'card' === $f['zone'] ) {
			if ( false === strpos( $h, '<span>' . $f['label'] . ': ' . $v . '</span>' ) ) { return $k; }
			continue;
		}
		$h3 = '<h3>' . $f['label'] . '</h3>';
		$at = strpos( $h, $h3 );
		if ( false === $at ) { return $k; }
		$at  += strlen( $h3 );
		$ends = array_filter( array( strpos( $h, '<h3', $at ), strpos( $h, '</article>', $at ) ), 'is_int' );
		$body = $ends ? substr( $h, $at, min( $ends ) - $at ) : substr( $h, $at );
		if ( false === strpos( $body, $v ) ) { return $k; }
	}
	return true;
};
$w44_pm = $w44_all; $w44_pm['tp_religious_event'] = str_replace( 'قاعدۀ تاریخ سالانه', 'x', $w44_pm['tp_religious_event'] );
$w44_pr = $w44_all; $w44_pr['tp_religious_event'] = str_replace( '<strong>visitor_etiquette_note</strong>', '', $w44_pr['tp_religious_event'] );
$w44_pn = $w44_all; $w44_pn['tp_seasonal_event'] = str_replace( topersia_g_fa( '47' ), topersia_g_fa( '48' ), $w44_pn['tp_seasonal_event'] );
$w44_ps = $w44_all; $w44_ps['tp_religious_event'] = strtr( $w44_ps['tp_religious_event'], array( 'مقدار photography_note' => 'مقدار dress_code_note', 'مقدار dress_code_note' => 'مقدار photography_note' ) );
$w44_pv = $w44_all; $w44_pv['tp_religious_event'] = strtr( $w44_pv['tp_religious_event'], array( '<strong>public_participation_note</strong>' => '<strong>visitor_etiquette_note</strong>', '<strong>visitor_etiquette_note</strong>' => '<strong>public_participation_note</strong>' ) );
t( 'رندر عمومی: هر ۱۰ فیلد با برچسب و مقدار خودش در صفحهٔ نوع خودش، بی کد ویژه — جهش: برچسب نیاید، مقدار غنی بیفتد، عدد عوض شود یا مقدار دو فیلد جابه‌جا شود',
	true === $w44_full( $w44_all ) && 'annual_date_rule' === $w44_full( $w44_pm ) && 'visitor_etiquette_note' === $w44_full( $w44_pr ) && 'peak_start_week' === $w44_full( $w44_pn )
	&& in_array( $w44_full( $w44_ps ), array( 'photography_note', 'dress_code_note' ), true ) && in_array( $w44_full( $w44_pv ), array( 'public_participation_note', 'visitor_etiquette_note' ), true ),
	(string) $w44_full( $w44_all ) . ' · ' . (string) $w44_full( $w44_ps ) . ' · ' . (string) $w44_full( $w44_pv ) );
$w44_rich = function ( $h ) { return false !== strpos( $h, '<h3>شرایط بهینۀ مشاهده</h3>' ) && false !== strpos( $h, '<strong>optimal_conditions</strong>' ) && false === strpos( $h, '&lt;strong' ); };
tp441_mut( 'متن غنی زیر h3 با برچسب؛ HTML گریز نمی‌خورد — جهش: گریز بخورد می‌افتد', $w44_rich, $w44_all['tp_seasonal_event'], htmlspecialchars( $w44_all['tp_seasonal_event'] ) );
$w44_stamp = function ( $pages ) { foreach ( $pages as $h ) { if ( 1 !== substr_count( $h, '<p class="tp-verified">' ) || false === strpos( $h, 'منبع آزمون' ) ) { return false; } } return true; };
$w44_sm = $w44_all; $w44_sm['tp_seasonal_event'] = str_replace( 'tp-verified', 'x', $w44_sm['tp_seasonal_event'] );
tp441_mut( 'مهر بازبینی و منبع کنار بلوک هر سه نوع — جهش: مهر یک نوع حذف شود می‌افتد', $w44_stamp, $w44_all, $w44_sm );
$w44_empty = '';
foreach ( $w44_ids2 as $id ) { $w44_empty .= r( $id + 10, 'tp_overview' ) . r( $id + 10, 'tp_badges' ) . topersia_g_visit_block( $id + 10 ); }
$w44_empty_ok = function ( $html ) use ( $w44_map ) {
	foreach ( $w44_map as $f ) { if ( false !== strpos( $html, $f['label'] ) ) { return false; } }
	return false === strpos( $html, 'tp-verified' ) && false === strpos( $html, 'نامعلوم' );
};
tp441_mut( 'خالی‌امن: نمونهٔ خالی هر نوع هیچ برچسب، مهر و «نامعلوم» نمی‌سازد — جهش: پست پر می‌افتد', $w44_empty_ok, $w44_empty, implode( '', $w44_all ) );

$w44_code = function ( $extra ) use ( $w44_map ) {
	$hits = array();
	foreach ( glob( TP_PLUGIN_DIR . 'includes/*.php' ) as $f ) {
		if ( 'topersia-g-map.php' === basename( $f ) ) { continue; }
		$src = (string) file_get_contents( $f ) . $extra;
		foreach ( array_keys( $w44_map ) as $k ) { if ( false !== strpos( $src, "'" . $k . "'" ) ) { $hits[] = basename( $f ) . ':' . $k; } }
	}
	return $hits;
};
t( 'بی کد به‌ازای فیلد: هیچ کلیدی در رندر نامی ندارد — جهش: شرطی با نام فیلد می‌افتد',
	array() === $w44_code( '' ) && array() !== $w44_code( "if ( 'dress_code_note' === \$name ) {}" ), implode( ' · ', $w44_code( '' ) ) );
t( 'مهر و اجبار بسته برای این سه نوع هم؛ آستانهٔ تازگی نه', ! array_diff( $w44_types, topersia_g_verified_types() ) && ! array_intersect_key( topersia_render_stale_types(), array_flip( $w44_types ) ) );
$w44_pack_src = (string) file_get_contents( $w44_root . '/plugin/topersia-core/includes/topersia-pack.php' );
$w44_pack_ok  = function ( $s ) { return 1 === preg_match( "/return array\( [^)]*'tp_sports_event', 'tp_seasonal_event', 'tp_religious_event' \);/", $s ); };
tp441_mut( 'بسته: تاریخ بازبینی و منبع برای این سه نوع هم اجباری؛ یک فهرست با رندر — جهش: نوعی حذف شود می‌افتد', $w44_pack_ok, $w44_pack_src, str_replace( ", 'tp_religious_event' );", ' );', $w44_pack_src ) );
$w44_empties = function ( $s ) use ( $w44_types ) { foreach ( $w44_types as $type ) { if ( false === strpos( $s, "'{$type}' => 'نمونهٔ خالی " ) ) { return false; } } return true; };
tp441_mut( 'نمونهٔ خالی هر سه نوع ساخته می‌شود — جهش: نوعی نباشد می‌افتد', $w44_empties, $w44_samples, str_replace( "'tp_seasonal_event' => 'نمونهٔ خالی", "'x' => 'نمونهٔ خالی", $w44_samples ) );

/* ── P5-D505: سند انحراف TA-I18N ─────────────────────────────────────────── */

$w44_ta_src = (string) @file_get_contents( $w44_root . '/docs/ops/TA-I18N-DEVIATIONS.md' );
$w44_ta_ok  = function ( $s ) {
	foreach ( array( 'event_travel_impact', 'key_species', 'activities', 'program_highlights', 'annual_date_rule', 'optimal_conditions', 'peak_start_week', 'public_participation_note', 'activity_specific_warnings', 'local_emergency_facilities', 'entry_points_reference' ) as $k ) {
		if ( false === strpos( $s, '`' . $k . '`' ) ) { return $k; }
	}
	/* `regional_notes`: نوع و کلاس متمم، زیرفیلد `rn_g3_id`، P5-D114 و منبع یال‌های `applies_g3_p2` (بازبینی دوم و سوم دستهٔ 44) */
	foreach ( array( '| کلید | نوع پست | TA چه می‌گوید | پایگاه چه دارد | wpml-config چه دارد | چه نمایش داده شد |', 'خطر انجماد', 'D484', 'P5-D505', 'متمم: `regional_notes` repeater · translate', 'applies_g3_p2', '`rn_g3_id`', 'P5-D114' ) as $need ) {
		if ( false === strpos( $s, $need ) ) { return $need; }
	}
	return true;
};
t( 'سند انحراف: ستون‌های بریف، هر کلید لازم، خطر انجماد و جانشین `regional_notes` — جهش: کلید، زیرفیلد `rn_g3_id` یا P5-D114 نباشد می‌افتد', true === $w44_ta_ok( $w44_ta_src ) && 'key_species' === $w44_ta_ok( str_replace( '`key_species`', '', $w44_ta_src ) ) && '`rn_g3_id`' === $w44_ta_ok( str_replace( '`rn_g3_id`', '', $w44_ta_src ) ) && 'P5-D114' === $w44_ta_ok( str_replace( 'P5-D114', '', $w44_ta_src ) ), (string) $w44_ta_ok( $w44_ta_src ) );

/* ── گام ۴: گزارش آمادگی نوع‌ها (بازبینی دوم دستهٔ 44) ─────────────────────── */

tp441_mut( 'CSV انجماد: فقط «relNN» رابطه است؛ related_museum و religious_site_etiquette فیلدند — جهش: هر نامِ آغازشده با rel', function ( $f ) {
	return array( true, true, false, false, false ) === array_map( $f, array( 'rel92 parents (#92)', 'rel244', 'related_museum', 'religious_site_etiquette', 'release_rel12' ) );
}, 'tp_freeze_row_is_rel', function ( $n ) { return 0 === strpos( $n, 'rel' ); } );
tp441_mut( 'نمونه: شهرستان نمونه ستون خودش را دارد حتی با نشان خالی؛ بقیه کامل یا خالی — جهش: نشان شهرستان نادیده', function ( $f ) {
	return array( 'county', 'county', 'empty', 'full' ) === array( $f( '1', '1' ), $f( 1, 0 ), $f( '', '1' ), $f( '', null ) );
}, 'tp_sample_kind', function ( $county, $empty ) { return $empty ? 'empty' : 'full'; } );
$w44_ready_src = (string) @file_get_contents( $w44_root . '/scripts/tp-type-readiness.php' );
$w44_ready_ok  = function ( $s ) {
	foreach ( array( 'tp_freeze_row_is_rel( $c[2] )', "tp_sample_kind( get_post_meta( (int) \$row->ID, '_tp_sample_county', true ), \$row->em )", "\$ticks( \$smp[ \$name ]['county'] )" ) as $need ) {
		if ( false === strpos( $s, $need ) ) { return false; }
	}
	return ! preg_match( '/preg_match\(\s*\'\/\^rel|strpos\(\s*\$c\[2\]|_tp_sample_county\'[^;]*\)\s*\{\s*continue/', $s );
};
tp441_mut( 'گزارش آمادگی: رابطه و گونهٔ نمونه از همان دو تابع؛ شهرستان نمونه به ازای هر زبان — جهش: regex کپی‌شده', $w44_ready_ok, $w44_ready_src, str_replace( 'tp_freeze_row_is_rel( $c[2] )', "preg_match( '/^rel\\d+\\b/', \$c[2] )", $w44_ready_src ) );
tp441_mut( 'گزارش آمادگی — جهش: شهرستان نمونه پیش از حلقهٔ زبان کنار گذاشته شود', $w44_ready_ok, $w44_ready_src, str_replace( 'foreach ( $fa_samples as $row ) {', "foreach ( \$fa_samples as \$row ) {\n\tif ( get_post_meta( (int) \$row->ID, '_tp_sample_county', true ) ) { continue; }", $w44_ready_src ) );

$GLOBALS['tp_posts'] = $keep44[0]; $GLOBALS['tp_meta'] = $keep44[1];
if ( null === $keep44[2] ) { unset( $GLOBALS['tp_rel'] ); } else { $GLOBALS['tp_rel'] = $keep44[2]; }
if ( null === $keep44[3] ) { unset( $GLOBALS['tp_filters'] ); } else { $GLOBALS['tp_filters'] = $keep44[3]; }
if ( null === $keep44[4] ) { unset( $GLOBALS['tp2g_status'] ); } else { $GLOBALS['tp2g_status'] = $keep44[4]; }
if ( null === $keep44[5] ) { unset( $GLOBALS['tp_archive'] ); } else { $GLOBALS['tp_archive'] = $keep44[5]; }
