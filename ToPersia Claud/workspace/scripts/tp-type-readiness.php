<?php
/**
 * دستهٔ 44 گام ۴: آمادگی هر نوع پست tp_ در یک جدول. فقط گزارش؛ جز docs/ops/TYPE-READINESS.md چیزی نمی‌نویسد.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-type-readiness.php [<لاگ گیت>]
 *
 * لاگ گیت: سطرهای «نام کد_خروج» یک یا چند زنجیرهٔ گیت پشت هم؛ ستون «آزمون مرتبط» آخرین کد هر نام. بی لاگ، «اجرا نشد».
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

require_once __DIR__ . '/lib/tp-rel-coverage-lib.php';
global $wpdb;

$ws  = dirname( __DIR__ );
$cat = include __DIR__ . '/i18n/topersia-render.php';
$fa  = function ( $s ) { return strtr( (string) $s, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) ); };

/* لاگ گیت: «نام کد» در هر سطر */
$log  = isset( $args[0] ) ? (string) $args[0] : '';
$exit = array();
if ( '' !== $log && is_readable( $log ) ) {
	foreach ( file( $log ) as $line ) {
		if ( preg_match( '/^([a-z0-9][a-z0-9\-]*) (\d+)$/', trim( $line ), $m ) ) { $exit[ $m[1] ] = (int) $m[2]; }
	}
}

/* CSV پیشنهاد انجماد: DISPLAY به فیلد و رابطه، STORE-ONLY */
$csv = array();
foreach ( (array) file( $ws . '/docs/ops/STRUCTURE-FREEZE-PROPOSAL.csv' ) as $i => $line ) {
	$c = str_getcsv( $line );
	if ( ! $i || ! isset( $c[3] ) ) { continue; }
	$part = 'DISPLAY' === $c[3] ? ( tp_freeze_row_is_rel( $c[2] ) ? 'dr' : 'df' ) : ( 'STORE-ONLY' === $c[3] ? 'so' : '' );
	if ( '' !== $part ) { $csv[ $c[1] ][ $part ] = ( $csv[ $c[1] ][ $part ] ?? 0 ) + 1; }
}

/* موج هر کلید FIELDMAP و آزمونش */
$waves = array( 'p5d470' => '40', 'wave1' => '41', 'wave2' => '42', 'wave2b' => '43', 'wave2c' => '44' );
$wtest = array( '40' => 'assert', '41' => 'w41-local', '42' => 'w42-local', '43' => 'w43-local', '44' => 'w44-local' );
$map   = topersia_g_map();

/* سوی هر رابطه روی صفحهٔ هر نوع */
$sides = array();
foreach ( tp_rel_heading_rows( $cat ) as $r ) {
	if ( ! $r['real'] && ! $r['sample'] ) { continue; }
	$s = &$sides[ $r['page'] ];
	$s['edge'][ $r['id'] . ':' . $r['dir'] ] = true;
	if ( '' !== $r['fa'] && '' !== $r['en'] && '' !== $r['ar'] ) { $s['title'][ $r['id'] . ':' . $r['dir'] ] = true; }
	if ( ! in_array( $r['group'], array( '', 'crumbs' ), true ) ) { $s['shown'][ $r['id'] . ':' . $r['dir'] ] = true; }
	if ( '' !== $r['why'] ) { $s['why'][ $r['id'] . ':' . $r['dir'] ] = true; }
	unset( $s );
}

/* نمونه‌ها: پست فارسی و ترجمه‌های منتشرشده */
$fa_samples = $wpdb->get_results( "SELECT p.ID, p.post_type, ( e.meta_value = '1' ) em FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = '_tp_sample' AND s.meta_value = '1' LEFT JOIN {$wpdb->postmeta} e ON e.post_id = p.ID AND e.meta_key = '_tp_sample_empty' JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = CONCAT( 'post_', p.post_type ) AND t.language_code = 'fa' WHERE p.post_status = 'publish' AND p.post_type LIKE 'tp\\_%'" );
$smp = array();
foreach ( $fa_samples as $row ) {
	/* شهرستان نمونه هم `_tp_sample_empty` دارد (بیرون از فهرست نمونه)، ولی نمونهٔ خالی نیست؛ مثل بقیه به ازای هر زبان */
	$kind = tp_sample_kind( get_post_meta( (int) $row->ID, '_tp_sample_county', true ), $row->em );
	foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
		$tid = 'fa' === $l ? (int) $row->ID : (int) apply_filters( 'wpml_object_id', (int) $row->ID, $row->post_type, false, $l );
		if ( $tid && 'publish' === get_post_status( $tid ) ) { $smp[ $row->post_type ][ $kind ][ $l ] = true; }
	}
}
$ticks = function ( $have ) { $o = ''; foreach ( array( 'fa', 'en', 'ar' ) as $l ) { $o .= empty( $have[ $l ] ) ? '✗' : '✓'; } return $o; };

$types = array_filter( get_post_types( array(), 'objects' ), function ( $o ) { return 0 === strpos( $o->name, 'tp_' ); } );
ksort( $types );
$verified = topersia_g_verified_types();
$pages    = topersia_g_stat_cells();

$out  = "# آمادگی نوع‌ها — دستهٔ 44\n\n";
$out .= "> فقط گزارش؛ از `scripts/tp-type-readiness.php` روی پایگاه محلی (" . gmdate( 'Y-m-d' ) . "). هیچ چیز ساخته یا عوض نشد.\n";
$out .= "> ستون‌ها: DISPLAY و STORE-ONLY از `docs/ops/STRUCTURE-FREEZE-PROPOSAL.csv` (DISPLAY = فیلد + رابطه)؛ «نگاشت» شمار کلید FIELDMAP در اسکیمای زندهٔ نوع؛ «رابطه» سوهای با یال (واقعی یا نمونه) روی صفحهٔ نوع، از آن‌ها با عنوان سه‌زبانه، در بخشی نمایش‌داده (بی مسیر راهنما) و با دلیل مکتوب (`docs/ops/REL-HEADINGS-2026-11.tsv`)؛ نمونه‌ها به ترتیب fa·en·ar؛ «آزمون مرتبط» آخرین کد خروج همان آزمون در لاگ گیت" . ( $exit ? ' (`' . basename( $log ) . '`، بیرون از مخزن؛ زنجیره‌ها و بازاجراها در بخش «گیت‌ها»ی لاگ دسته)' : '' ) . ".\n";
$out .= "> علامت‌ها: ⚠ بی‌نگاشت = هیچ کلید FIELDMAP ندارد · ⚠ بی‌رابطهٔ نمایش‌داده = هیچ سوی با یالش در بخشی از صفحه نمی‌آید · بی صفحه = نوع صفحهٔ عمومی ندارد.\n\n";
$out .= "| نوع | DISPLAY (فیلد + رابطه) | STORE-ONLY | نگاشت | موج | مهر بازبینی | رابطهٔ با یال · با عنوان · نمایش · دلیل | نمونهٔ کامل | نمونهٔ خالی | آزمون مرتبط | علامت |\n|---|---|---|---|---|---|---|---|---|---|---|\n";

$tot = array( 'types' => 0, 'stamp' => 0, 'nomap' => array(), 'norel' => array(), 'nopage' => array() );
foreach ( $types as $name => $obj ) {
	$tot['types']++;
	$fields = array_keys( (array) ( topersia_pack_schema( $name )['fields'] ?? array() ) );
	$mapped = array_values( array_intersect( array_keys( $map ), $fields ) );
	$wv     = array();
	foreach ( $mapped as $k ) { foreach ( $waves as $flag => $b ) { if ( ! empty( $map[ $k ][ $flag ] ) ) { $wv[ $b ] = true; } } }
	ksort( $wv );
	$c     = $csv[ $name ] ?? array();
	$disp  = isset( $csv[ $name ] ) ? $fa( ( $c['df'] ?? 0 ) + ( $c['dr'] ?? 0 ) ) . ' (' . $fa( $c['df'] ?? 0 ) . ' + ' . $fa( $c['dr'] ?? 0 ) . ')' : '—';
	$s     = $sides[ $name ] ?? array();
	$rel   = $fa( count( $s['edge'] ?? array() ) ) . ' · ' . $fa( count( $s['title'] ?? array() ) ) . ' · ' . $fa( count( $s['shown'] ?? array() ) ) . ' · ' . $fa( count( $s['why'] ?? array() ) );
	$page  = isset( $pages[ $name ] );
	$stamp = in_array( $name, $verified, true );
	$tests = array( 'alltypes' );
	foreach ( array_keys( $wv ) as $b ) { $tests[] = $wtest[ $b ]; }
	$tcell = implode( ' · ', array_map( function ( $n ) use ( $exit, $fa ) { return $n . ' ' . ( isset( $exit[ $n ] ) ? $fa( $exit[ $n ] ) : 'اجرا نشد' ); }, array_unique( $tests ) ) );
	$marks = array();
	if ( ! $mapped ) { $marks[] = '⚠ بی‌نگاشت'; $tot['nomap'][] = $name; }
	if ( $page && empty( $s['shown'] ) ) { $marks[] = '⚠ بی‌رابطهٔ نمایش‌داده'; $tot['norel'][] = $name; }
	if ( ! $page ) { $marks[] = 'بی صفحه'; $tot['nopage'][] = $name; }
	if ( $stamp ) { $tot['stamp']++; }
	$full  = 'tp_county' === $name ? ( isset( $smp[ $name ]['county'] ) ? 'شهرستان نمونه ' . $ticks( $smp[ $name ]['county'] ) : '—' ) : ( isset( $smp[ $name ]['full'] ) ? $ticks( $smp[ $name ]['full'] ) : '—' );
	$empty = isset( $smp[ $name ]['empty'] ) ? $ticks( $smp[ $name ]['empty'] ) : '—';
	$out  .= '| `' . $name . '` ' . esc_html( $obj->labels->singular_name ) . ' | ' . $disp . ' | ' . ( isset( $csv[ $name ] ) ? $fa( $c['so'] ?? 0 ) : '—' ) . ' | ' . $fa( count( $mapped ) ) . ' | ' . ( $wv ? $fa( implode( '، ', array_keys( $wv ) ) ) : '—' ) . ' | ' . ( $stamp ? 'دارد' : '—' ) . ' | ' . $rel . ' | ' . $full . ' | ' . $empty . ' | ' . $tcell . ' | ' . ( $marks ? implode( ' · ', $marks ) : '' ) . " |\n";
}

$list = function ( $ks ) { return $ks ? '`' . implode( '`، `', $ks ) . '`' : '—'; };
$out .= "\n## خلاصه\n\n";
$out .= '- نوع‌ها: ' . $fa( $tot['types'] ) . '؛ با مهر بازبینی: ' . $fa( $tot['stamp'] ) . ".\n";
$out .= '- ⚠ بی‌نگاشت: ' . $list( $tot['nomap'] ) . ".\n";
$out .= '- ⚠ بی‌رابطهٔ نمایش‌داده: ' . $list( $tot['norel'] ) . ".\n";
$out .= '- بی صفحه: ' . $list( $tot['nopage'] ) . ".\n";
$out .= "- «نگاشت» فقط FIELDMAP را می‌شمارد؛ فیلدهایی که ناحیهٔ ویژهٔ خودشان را دارند (سربرگ، آمار، نقشه، گالری، هوا) در این ستون نیستند.\n";

$path = $ws . '/docs/ops/TYPE-READINESS.md';
if ( false === file_put_contents( $path, $out ) ) { echo "نوشته نشد\n"; exit( 1 ); }
printf( "نوشته شد: docs/ops/TYPE-READINESS.md · نوع: %d · بی‌نگاشت: %d · بی‌رابطهٔ نمایش‌داده: %d · لاگ: %s\n", $tot['types'], count( $tot['nomap'] ), count( $tot['norel'] ), $exit ? basename( $log ) : '—' );
