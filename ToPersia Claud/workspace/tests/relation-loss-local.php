<?php
/**
 * هشدار حذف/جایگزینی والدِ رابطه — دستهٔ 38-ب · P5-D481. روی وردپرس محلی؛ داده‌ٔ آزمون را خودش می‌سازد و پاک می‌کند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/relation-loss-local.php
 *
 * کشور و استان آزمون (پیش‌نویس) با یال ۷۱ ← (۱) درون‌ریز «ساخت» با همان نام: مسدود (duplicate) و هشدار P5-D481 با شمار یال؛
 * (۲) کشور حذف می‌شود ← ردیف لاگ `relations` با شمار و عکس یال‌ها؛ (۳) درون‌ریز «ساخت» با همان نام ← هشدار «پیش‌تر یال داشت و حذف
 * شده»، مانع نیست؛ (۴) جهش: بی ردیف لاگ یا بی یال هشدار نیست. هر مورد ساختهٔ آزمون در پایان برمی‌گردد.
 */
global $wpdb;
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

$tag   = 'آزمون یال ۳۸ب ' . wp_generate_password( 4, false, false );
$mk    = function ( $type, $title ) { $id = wp_insert_post( array( 'post_type' => $type, 'post_status' => 'draft', 'post_title' => $title ), true ); update_post_meta( $id, '_tp_test_38b', 1 ); return (int) $id; };
$cid   = $mk( 'tp_country', $tag );
$pid   = $mk( 'tp_province', $tag . ' استان' );
$rel   = topersia_relation( 71 );
$rel->update( $cid, $pid );
$edges = count( topersia_pack_edge_rows( $cid ) );
$t( 'آماده‌سازی: کشور و استان آزمون با یال ۷۱', 1 === $edges, "edges=$edges" );

$mkdata = function ( $title ) { return array( 'meta' => array( 'post_type' => 'tp_country', 'language' => 'fa' ), 'posts' => array( array( 'entity_uid' => '', 'slug' => '', 'language' => 'fa', 'title' => $title, 'fields' => array( 'name_fa' => $title ), 'relations' => array() ) ) ); };
$notes  = function ( $plan ) { return implode( ' | ', (array) $plan['notes'] ); };

/* ۱) هم‌نامِ موجود با یال */
$p1 = topersia_pack_plan_post( $mkdata( $tag ), 0 );
$t( 'ساخت با نام پستی که یال دارد: مسدود (duplicate) + هشدار P5-D481 با «۱ یال»', 'duplicate' === $p1['problem'] && false !== strpos( $notes( $p1 ), 'P5-D481' ) && false !== strpos( $notes( $p1 ), '1 یال' ), $notes( $p1 ) );

/* جهش ۱: بی یال هشدار نیست */
$rel->delete_rows( $cid, $pid );
$p1b = topersia_pack_plan_post( $mkdata( $tag ), 0 );
$t( 'جهش: پستِ هم‌نام بی یال ← هشدار P5-D481 نیست (ولی duplicate می‌ماند)', 'duplicate' === $p1b['problem'] && false === strpos( $notes( $p1b ), 'P5-D481' ) );
$rel->update( $cid, $pid );

/* ۲) حذف ← ردیف لاگ */
$before_rows = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . topersia_log_table() . " WHERE channel='relations' AND message LIKE 'حذف پستِ دارای یال%'" );
wp_delete_post( $cid, true );
$after_rows = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . topersia_log_table() . " WHERE channel='relations' AND message LIKE 'حذف پستِ دارای یال%'" );
$ctx = json_decode( (string) $wpdb->get_var( 'SELECT context FROM ' . topersia_log_table() . " WHERE channel='relations' AND message LIKE 'حذف پستِ دارای یال%' ORDER BY id DESC LIMIT 1" ), true );
$t( 'حذف پستِ دارای یال: یک ردیف هشدار در کانال relations با شمار و عکس یال‌ها', $after_rows === $before_rows + 1 && 1 === (int) ( $ctx['count'] ?? 0 ) && $cid === (int) ( $ctx['post'] ?? 0 ) && ! empty( $ctx['edges'][0] ), json_encode( $ctx, JSON_UNESCAPED_UNICODE ) );

/* ۳) ساخت بعد از حذف */
$p3 = topersia_pack_plan_post( $mkdata( $tag ), 0 );
$t( 'ساخت با نام پستِ حذف‌شدهٔ دارای یال: هشدار «پیش‌تر ۱ یال داشت و حذف شده» + کار ساخت مانع نیست', 'create' === $p3['action'] && '' === $p3['problem'] && false !== strpos( $notes( $p3 ), 'حذف شده' ) && false !== strpos( $notes( $p3 ), '1 یال' ), $notes( $p3 ) );

/* جهش ۳: ردیف لاگ نباشد ← هشدار نیست */
$wpdb->query( 'DELETE FROM ' . topersia_log_table() . " WHERE channel='relations' AND message LIKE 'حذف پستِ دارای یال رابطه #" . (int) $cid . "%'" );
$p3b = topersia_pack_plan_post( $mkdata( $tag ), 0 );
$t( 'جهش: بی ردیف لاگ حذف ← هشدار نیست', 'create' === $p3b['action'] && false === strpos( $notes( $p3b ), 'P5-D481' ) );

/* پاک‌سازی */
wp_delete_post( $pid, true );
foreach ( $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_tp_test_38b'" ) as $left ) { wp_delete_post( (int) $left, true ); }
$wpdb->query( 'DELETE FROM ' . topersia_log_table() . " WHERE channel='relations' AND context LIKE '%" . esc_sql( $tag ) . "%'" );
$t( 'پاک‌سازی: هیچ پستِ آزمون نمانده', 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_tp_test_38b'" ) );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
