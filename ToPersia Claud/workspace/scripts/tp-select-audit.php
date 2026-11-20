<?php
/**
 * ممیزی فقط‌خواندنی select/radio اجباری JetEngine بی گزینهٔ خالی — دستهٔ 55-د بند ۳ · P5-D647. هیچ چیز را عوض نمی‌کند.
 *
 *   wp --url=http://topersia.local eval-file scripts/tp-select-audit.php <خروجی.md>
 *
 * چرا: فرم پیشخوان برای select اجباریِ بی گزینهٔ خالی (و بی placeholder) همیشه یک گزینه را برگزیده نشان می‌دهد؛ موجودیتی که مقدارش
 * خالی است با ذخیرهٔ مجدد (حتی برای ویرایش یک فیلد دیگر) بی‌صدا گزینهٔ پیش‌فرض یا نخست را می‌گیرد. معیار فیلد همان tp_sf_audit
 * در tp-season-field.php است (select/radio، اجباری، تک‌انتخابی، بی placeholder، بی گزینهٔ کلید خالی). شمار: موجودیت‌های غیر زبالهٔ
 * نوع‌های مجاز که متای نام فیلد را ندارند یا '' دارند؛ جدا: منتشرشده و نمونهٔ نمایشی (_tp_sample).
 */
global $wpdb;
if ( 'topersia.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { WP_CLI::error( 'فقط topersia.local' ); }
$boxes = (array) get_option( 'jet_engine_meta_boxes' );
$sets  = array();
foreach ( $boxes as $k => $b ) {
	$sets[] = array( 'متاباکس ' . $k . ( isset( $b['args']['name'] ) ? ' «' . $b['args']['name'] . '»' : '' ), (array) ( $b['args']['allowed_post_type'] ?? array() ), (array) ( $b['meta_fields'] ?? array() ) );
}
$tpt = $wpdb->prefix . 'jet_post_types';
if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tpt ) ) ) {
	foreach ( (array) $wpdb->get_results( "SELECT id, slug, meta_fields FROM {$tpt}", ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB.PreparedSQL
		$m = maybe_unserialize( $r['meta_fields'] );
		if ( is_array( $m ) ) { $sets[] = array( 'نوع پست ' . $r['slug'], array( (string) $r['slug'] ), $m ); }
	}
}
$rows = array();
$walk = function ( array $fields, $src, $types, $path ) use ( &$walk, &$rows ) {
	foreach ( $fields as $f ) {
		if ( ! is_array( $f ) ) { continue; }
		if ( ! empty( $f['repeater-fields'] ) && is_array( $f['repeater-fields'] ) ) { $walk( $f['repeater-fields'], $src, $types, $path . ( $f['name'] ?? '?' ) . '/' ); }
		$type = (string) ( $f['type'] ?? '' );
		if ( ! in_array( $type, array( 'select', 'radio' ), true ) || ! filter_var( $f['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) { continue; }
		if ( 'select' === $type && ( filter_var( $f['is_multiple'] ?? false, FILTER_VALIDATE_BOOLEAN ) || '' !== (string) ( $f['placeholder'] ?? '' ) ) ) { continue; }
		foreach ( (array) ( $f['options'] ?? array() ) as $o ) { if ( is_array( $o ) && '' === (string) ( $o['key'] ?? 'x' ) ) { continue 2; } }
		$rows[] = array( 'src' => $src, 'types' => $types, 'name' => (string) ( $f['name'] ?? '' ), 'title' => (string) ( $f['title'] ?? '' ), 'type' => $type, 'path' => $path,
			'default' => (string) ( $f['default_val'] ?? '' ), 'gloss' => 'glossary' === ( $f['options_source'] ?? '' ) ? 'واژه‌نامه ' . ( $f['glossary_id'] ?? '?' ) : 'گزینهٔ دستی', 'opts' => $f['options'] ?? array(), 'f' => $f );
	}
};
foreach ( $sets as $s ) { $walk( $s[2], $s[0], $s[1], '' ); }

$first_opt = function ( array $r ) {
	if ( '' !== $r['default'] ) { return 'پیش‌فرض «' . $r['default'] . '»'; }
	foreach ( (array) $r['opts'] as $o ) { if ( is_array( $o ) ) { return 'گزینهٔ نخست «' . ( $o['value'] ?? $o['key'] ?? '' ) . '»'; } }
	return 'گزینهٔ نخست واژه‌نامه';
};
$tot = array( 'all' => 0, 'pub' => 0 );
$md  = "| # | منبع | نوع محتوا | فیلد (نام) | عنوان | نوع | منبع گزینه‌ها | موجودیت خالی (کل) | منتشرشده | نمونه | اگر ذخیره شود |\n|---|---|---|---|---|---|---|---|---|---|---|\n";
foreach ( $rows as $i => $r ) {
	$types = array_values( array_filter( $r['types'] ) );
	if ( '' !== $r['path'] || ! $types ) { $c = array( 'n/a (فیلد تکرارشونده یا بی نوع)', '—', '—' ); }
	else {
		$in  = "'" . implode( "','", array_map( 'esc_sql', $types ) ) . "'";
		$key = esc_sql( $r['name'] );
		$sql = "SELECT COUNT(*) a, SUM(p.post_status='publish') pb, SUM(s.meta_id IS NOT NULL) sm FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='{$key}'
			LEFT JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_tp_sample'
			WHERE p.post_type IN ({$in}) AND p.post_status NOT IN ('trash','auto-draft') AND ( m.meta_id IS NULL OR m.meta_value='' )";
		$x   = $wpdb->get_row( $sql, ARRAY_A );   // phpcs:ignore WordPress.DB.PreparedSQL
		$c   = array( (int) $x['a'], (int) $x['pb'], (int) $x['sm'] );
		$tot['all'] += (int) $x['a']; $tot['pub'] += (int) $x['pb'];
	}
	$md .= '| ' . ( $i + 1 ) . ' | ' . $r['src'] . ' | ' . implode( '، ', $types ) . ' | `' . $r['path'] . $r['name'] . '` | ' . $r['title'] . ' | ' . $r['type'] . ' | ' . $r['gloss'] . ' | ' . $c[0] . ' | ' . $c[1] . ' | ' . $c[2] . ' | ' . $first_opt( $r ) . " |\n";
}
file_put_contents( (string) $args[0], "COUNT=" . count( $rows ) . "\nTOTAL=" . $tot['all'] . "\nPUBLISHED=" . $tot['pub'] . "\n" . $md );
echo count( $rows ) . " rows\n";
