<?php
/**
 * apply / rollback عددهای پایه روی وردپرس واقعی — P5-D394 (فقط topersia.local)
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/facts-apply-local.php
 *
 * پست‌های موقت خودش می‌سازد (استان و شهر با entity_uid ساختگی) و در پایان، حتی با خطا، پاک می‌کند؛ هیچ داده یا رابطهٔ واقعی لمس نمی‌شود.
 * ادعاها: apply فقط ردیف approved را می‌نویسد · فیلد غیرخالی با fill تغییر نمی‌کند · replace فقط با استثنای صریح · idempotent ·
 * شمار انتظار ناسازگار ← توقف بی نوشتن · rollback ← مقدارها برابر پیش از نوشتن. هر ادعا با جهش (حذف شرط ← باید بیفتد).
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }
require_once dirname( __DIR__ ) . '/scripts/lib/tp-facts-lib.php';
$GLOBALS['fa_t'] = array( 0, 0 );
function t( $label, $ok, $note = '' ) { if ( $ok ) { $GLOBALS['fa_t'][0]++; echo "  ✅ $label\n"; } else { $GLOBALS['fa_t'][1]++; echo "  ❌ $label   ← $note\n"; } }

echo "apply · rollback — P5-D394\n";
$GLOBALS['tpft_created'] = array(); $rels = array();
$tpft_files = function () { return array_merge( (array) glob( tpf_private( 'facts-logs' ) . '*.json' ), (array) glob( tpf_private( 'facts-backup' ) . '*.sql' ) ); };
$tpft_before = $tpft_files();   /* هر لاگ و پشتیبانی که آزمون (و جهش‌ها) می‌سازد در پایان پاک می‌شود */   /* eval-file در دامنهٔ تابع اجرا می‌شود — global ساده به متغیر دیگری می‌رسید و پاک‌سازی هیچ نمی‌کرد */
function mk( $type, $uid, $meta ) {
	$id = wp_insert_post( array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => 'tp-test facts ' . $uid ) );
	$GLOBALS['tpft_created'][] = $id;
	update_post_meta( $id, '_tp_test_marker', 'facts' );
	update_post_meta( $id, 'entity_uid', $uid );
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_' . $type, 'trid' => false, 'language_code' => 'fa', 'source_language_code' => null ) );
	foreach ( $meta as $k => $v ) { update_post_meta( $id, $k, $v ); }
	return $id;
}
function row( $uid, $type, $field, $new, $approved, $extra = array() ) {
	return array_merge( array( 'entity_uid' => $uid, 'post_type' => $type, 'name_fa' => 'نام ' . $uid, 'qid' => 'Q1', 'qid_method' => '', 'status' => 'auto', 'guards' => '', 'field' => $field, 'current' => '', 'proposed' => $new,
		'action' => 'fill', 'property' => 'P1082', 'year' => '2016', 'reference' => 'https://www.amar.org.ir/x.xlsx', 'retrieved' => '2026-10-20', 'approved' => $approved ), $extra );
}
function snap( $id, $keys ) { $o = array(); foreach ( $keys as $k ) { $o[ $k ] = metadata_exists( 'post', $id, $k ) ? get_post_meta( $id, $k, true ) : '<none>'; } return $o; }
$keys = array( 'population', 'geo_coordinates', 'area_km2', 'elevation_m', 'external_reference_uri', 'source_references' );

try {
	$u = 'https://topersia.local/e/test/facts-';
	$p1 = mk( 'tp_city', $u . 'a', array( 'area_km2' => '500', 'external_reference_uri' => 'https://www.wikidata.org/wiki/Q999' ) );   /* خالی: جمعیت، مختصات، ارتفاع · پر: مساحت، QID */
	$prov = mk( 'tp_province', $u . 'prov', array() );
	$city = mk( 'tp_city', $u . 'cap', array() );
	$rows = array(
		row( $u . 'a', 'tp_city', 'population', '12345', 'yes' ),                                          /* A fill خالی ← نوشته */
		row( $u . 'a', 'tp_city', 'area_km2', '777', 'yes' ),                                              /* B fill روی غیرخالی ← نه */
		row( $u . 'a', 'tp_city', 'elevation_m', '1500', '', array( 'property' => 'P2044', 'year' => '' ) ),  /* C تأییدنشده ← نه */
		row( $u . 'a', 'tp_city', 'external_reference_uri', 'https://www.wikidata.org/wiki/Q42', 'yes-replace', array( 'action' => 'diff', 'property' => 'QID', 'year' => '', 'reference' => '' ) ),   /* D replace با استثنا ← نوشته */
		row( $u . 'a', 'tp_city', 'geo_coordinates', '35.5, 51.5', 'yes', array( 'property' => 'P625', 'year' => '', 'reference' => '' ) ),
		row( $u . 'prov', 'tp_province', 'rel:96 (مرکز استان)', $u . 'cap', 'yes', array( 'property' => 'P36', 'year' => '', 'reference' => '' ) ),
	);
	$expect = 5;
	$before = snap( $p1, $keys );

	/* شمار انتظار ناسازگار ← توقف بی نوشتن */
	$bad = tpf_apply( $rows, array( 'expect' => 99, 'backup' => false ) );
	t( 'شمار approved با انتظار برابر نیست ← خطا و هیچ نوشتن', isset( $bad['error'] ) && $before === snap( $p1, $keys ) );

	/* dry-run: هیچ نوشتن */
	$dry = tpf_apply( $rows, array( 'dry' => true, 'expect' => $expect ) );
	t( 'dry-run: فهرست «از ← به» دارد و هیچ نمی‌نویسد', 4 === count( $dry['lines'] ) && $before === snap( $p1, $keys ) && ! in_array( $city, array_map( 'intval', (array) topersia_relation( 96 )->get_children( $prov, 'ids' ) ), true ), wp_json_encode( $dry['by'], JSON_UNESCAPED_UNICODE ) );

	/* اجرای واقعی */
	$run = tpf_apply( $rows, array( 'expect' => $expect, 'backup' => true ) );
	$rels[] = array( $prov, $city );
	$after  = snap( $p1, $keys );
	t( 'پشتیبان SQL ساخته شد (postmeta و جدول رابطه) و لاگ نوشته شد', is_file( $run['backup'] ) && false !== strpos( (string) file_get_contents( $run['backup'] ), 'jet_rel_default' ) && is_file( $run['logfile'] ) );
	t( 'A: fill روی فیلد خالی نوشته شد', '12345' === $after['population'] );
	t( 'B: fill روی فیلد غیرخالی تغییر نکرد', '500' === $after['area_km2'], $after['area_km2'] );
	t( 'C: ردیف تأییدنشده نوشته نشد', '<none>' === $after['elevation_m'] );
	t( 'D: replace با استثنای صریح انجام شد', 'https://www.wikidata.org/wiki/Q42' === $after['external_reference_uri'] );
	t( 'رابطهٔ ۹۶ با API جت‌انجین نوشته شد', in_array( $city, array_map( 'intval', (array) topersia_relation( 96 )->get_children( $prov, 'ids' ) ), true ) );
	$src = $after['source_references'];
	$names = is_array( $src ) ? array_column( $src, 'source_name' ) : array();
	t( 'منبع هر عدد: ردیف ویکی‌داده برای جمعیت و مختصات + مرجع مرکز آمار برای جمعیت؛ نه برای QID و ارتفاع‌نوشته‌نشده',
		in_array( 'ویکی‌داده — جمعیت (P1082، 2016)', $names, true ) && in_array( 'ویکی‌داده — مختصات (P625)', $names, true ) && in_array( 'مرکز آمار ایران — جمعیت (2016)', $names, true ) && 3 === count( $names ), wp_json_encode( $names, JSON_UNESCAPED_UNICODE ) );
	t( 'منبع: تاریخ مراجعه و وضعیت active', '2026-10-20' === reset( $src )['source_access_date'] && 'active' === reset( $src )['source_row_status'] );

	/* idempotent */
	$run2 = tpf_apply( $rows, array( 'expect' => $expect, 'backup' => false ) );
	t( 'اجرای دوباره: هیچ نوشتن تازه‌ای و هیچ ردیف منبع تکراری', 0 === count( $run2['log'] ) && snap( $p1, $keys ) === $after, wp_json_encode( $run2['by'], JSON_UNESCAPED_UNICODE ) );

	/* rollback */
	$rb = tpf_rollback( $run['logfile'] );
	$nk = array_diff( $keys, array( 'source_references' ) );
	t( 'rollback: مقدارهای فیلدها برابر پیش از نوشتن (فیلدی که نبود دوباره نیست)', snap( $p1, $nk ) === array_intersect_key( $before, array_flip( $nk ) ), wp_json_encode( array( snap( $p1, $nk ), $before ), JSON_UNESCAPED_UNICODE ) );
	$sr = get_post_meta( $p1, 'source_references', true );
	t( 'rollback: ردیف‌های منبعِ افزوده‌شده بازنشسته شد (نه حذف — قاعدهٔ محافظ ردیف)؛ هیچ ردیف فعالی نمانده',
		is_array( $sr ) && 3 === count( $sr ) && 0 === count( array_filter( $sr, function ( $r ) { return 'not_applicable' !== $r['source_row_status']; } ) ), wp_json_encode( $sr, JSON_UNESCAPED_UNICODE ) );
	t( 'rollback: رابطهٔ ۹۶ برداشته شد', ! in_array( $city, array_map( 'intval', (array) topersia_relation( 96 )->get_children( $prov, 'ids' ) ), true ), wp_json_encode( $rb ) );

	/* جهش‌ها */
	$lib = (string) file_get_contents( dirname( __DIR__ ) . '/scripts/lib/tp-facts-lib.php' );
	/* جهش: همین کتابخانه با یک شرط برداشته‌شده، زیر نام‌های تازه (tpfmX_) — تابع apply جهش‌یافته برگردانده می‌شود */
	$mut = function ( $from, $to, $tag ) use ( $lib ) {
		if ( false === strpos( $lib, $from ) ) { return false; }
		$src = str_replace( array( 'TPF_LIB', 'tpf_' ), array( 'TPFM_' . $tag, 'tpfm' . $tag . '_' ), str_replace( $from, $to, $lib ) );
		// phpcs:ignore Squiz.PHP.Eval -- جهش آزمون؛ فقط محلی، فقط همین پروندهٔ مخزن
		eval( '?>' . $src );
		return 'tpfm' . $tag . '_apply';
	};
	$m1 = $mut( "if ( ! in_array( \$r['approved'], array( 'yes', 'yes-replace' ), true ) ) { continue; }", '', 'A' );
	$pm = mk( 'tp_city', $u . 'ma', array() );
	$f1 = $m1 ? $m1( array( row( $u . 'ma', 'tp_city', 'population', '555', '' ) ), array( 'expect' => 1 ) ) : array( 'error' => 'جهش اعمال نشد' );
	t( 'جهش: بی شرط approved، ردیف تأییدنشده نوشته می‌شود (آزمون باید این را بگیرد)', $m1 && '555' === (string) get_post_meta( $pm, 'population', true ), $m1 ? '' : 'جهش اعمال نشد' );
	tpf_apply( array( row( $u . 'ma', 'tp_city', 'population', '556', '' ) ), array( 'expect' => 1 ) );
	t( 'با کد واقعی ردیف تأییدنشده نوشته نمی‌شود (مقدار جهش ۵۵۵ می‌ماند، ۵۵۶ نیامد)', '556' !== (string) get_post_meta( $pm, 'population', true ) );

	$m2 = $mut( "if ( 'fill' === \$op['mode'] && ! tpf_is_empty( \$cur ) ) { return array( 'skip-notempty', \$cur, '' ); }", '', 'B' );
	$pn = mk( 'tp_city', $u . 'mb', array( 'area_km2' => '500' ) );
	if ( $m2 ) { $m2( array( row( $u . 'mb', 'tp_city', 'area_km2', '777', 'yes', array( 'property' => 'P2046', 'year' => '' ) ) ), array( 'expect' => 1 ) ); }
	t( 'جهش: بی شرط «غیرخالی»، فیلد پر دوباره‌نویسی می‌شود (آزمون باید بگیرد)', $m2 && '777' === (string) get_post_meta( $pn, 'area_km2', true ) );
	update_post_meta( $pn, 'area_km2', '500' );
	tpf_apply( array( row( $u . 'mb', 'tp_city', 'area_km2', '777', 'yes', array( 'property' => 'P2046', 'year' => '' ) ) ), array( 'expect' => 1 ) );
	t( 'با کد واقعی فیلد پر تغییر نمی‌کند', '500' === (string) get_post_meta( $pn, 'area_km2', true ) );

	$m3 = $mut( "if ( 'replace' === \$op['mode'] && 'yes-replace' !== \$op['approved_flag'] ) { return array( 'skip-notempty', \$cur, '' ); }", '', 'C' );
	t( 'جهش «replace بی استثنا» اعمال شد (شرط موجود است)', (bool) $m3 );
	t( 'قاعدهٔ تأیید: شمار برابر ۲۷ + ۳۵۴ + ۲ = ۳۸۳ روی CSV واقعی', ( function () { list( , $r ) = tpf_csv_load( dirname( __DIR__ ) . '/reference/facts-proposed.csv' ); $n = tpf_mark( $r ); return 27 + 2 === $n['yes-replace'] && 354 === $n['yes']; } )() );
} finally {
	foreach ( array_diff( $tpft_files(), $tpft_before ) as $tmpf ) { if ( is_file( $tmpf ) ) { unlink( $tmpf ); } }   /* پشتیبان و لاگ آزمون؛ مال apply واقعی نیست */
	foreach ( $rels as $r ) { topersia_relation( 96 )->delete_rows( $r[0], $r[1], true ); }
	foreach ( $GLOBALS['tpft_created'] as $id ) { if ( 'facts' === get_post_meta( $id, '_tp_test_marker', true ) ) { wp_delete_post( $id, true ); } }
	if ( function_exists( 'topersia_geo_index_rebuild' ) ) { topersia_geo_index_rebuild(); }
}
t( 'پاک‌سازی: هیچ پست آزمون (نشان facts) نماند', 0 === (int) $GLOBALS['wpdb']->get_var( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->postmeta} WHERE meta_key='_tp_test_marker' AND meta_value='facts'" ) );
t( 'پشتیبان و لاگ بیرون از ریشهٔ وب (نه زیر پوشهٔ والد ABSPATH)، با .htaccess ردِ همه', 0 !== strpos( wp_normalize_path( tpf_private( 'facts-backup' ) ), wp_normalize_path( dirname( untrailingslashit( ABSPATH ) ) ) . '/' ) && is_file( tpf_private( 'facts-backup' ) . '.htaccess' ) );
t( 'پاک‌سازی: هیچ لاگ یا پشتیبان آزمون نماند', array() === array_diff( $tpft_files(), $tpft_before ) );
$g = $GLOBALS['fa_t'];
echo "\n  گذشته: {$g[0]}   افتاده: {$g[1]}\n";
if ( $g[1] ) { exit( 1 ); }
