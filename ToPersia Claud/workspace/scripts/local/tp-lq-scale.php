<?php
/**
 * پرسش‌های LQ-SCALE-* (SCALE.md §۸) — فقط خواندنی  (فقط محلی؛ هیچ نوشتن داده یا تنظیم)
 *
 *   wp eval-file scripts/local/tp-lq-scale.php
 *
 * SAVEQUERIES با `$wpdb->save_queries` در زمان اجرا روشن می‌شود (wp-config دست نمی‌خورد) و در پایان خاموش.
 * چاپ نمی‌شود: مقدار هیچ گزینه، کلید، رمز یا نام کاربر؛ فقط نام گزینه‌ها و اندازه‌ها.
 * اجرا نمی‌شوند (نوشتن دارند): ۱۲ زمان `match` · ۱۳ ویرایش متا · ۱۶ درون‌ریزی · ۱۷ بازسازی نمایه؛ و ۱۰ · ۱۱ که HTTP‌اند (tests/lq-scale-http.sh).
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) { exit; }
global $wpdb;
$P = $wpdb->prefix;
$say = function ( $id, $text ) use ( $P ) { echo $id . ' | ' . str_ireplace( rtrim( $P, '_' ), 'wp', $text ) . "\n"; };   /* پیشوند هرگز چاپ نمی‌شود */

/* LQ-SCALE-01 — جدول‌های بزرگ */
$rows = $wpdb->get_results( $wpdb->prepare( "SELECT table_name n, table_rows r, ROUND((data_length+index_length)/1048576, 1) mb FROM information_schema.tables WHERE table_schema = %s ORDER BY (data_length+index_length) DESC LIMIT 12", DB_NAME ) );
$say( 'LQ-SCALE-01', implode( ' · ', array_map( function ( $r ) { return "{$r->n}: {$r->r} ردیف/{$r->mb}MB"; }, $rows ) ) );
$cnt = function ( $sql ) use ( $wpdb ) { return (int) $wpdb->get_var( $sql ); };
$say( 'LQ-SCALE-01', 'posts کل=' . $cnt( "SELECT COUNT(*) FROM {$wpdb->posts}" ) . ' · منتشرشدهٔ tp_=' . $cnt( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type LIKE 'tp\\_%'" ) . ' · postmeta کل=' . $cnt( "SELECT COUNT(*) FROM {$wpdb->postmeta}" ) . ' · options=' . $cnt( "SELECT COUNT(*) FROM {$wpdb->options}" ) );

/* LQ-SCALE-02 — ردیف رابطه به ازای rel_id */
foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT table_name FROM information_schema.tables WHERE table_schema = %s AND table_name LIKE %s", DB_NAME, $wpdb->esc_like( $P . 'jet_rel_' ) . '%' ) ) as $t ) {
	$tot = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$t}`" );
	$by  = $wpdb->get_results( "SELECT rel_id, COUNT(*) c FROM `{$t}` GROUP BY rel_id ORDER BY c DESC LIMIT 6" );
	$say( 'LQ-SCALE-02', "{$t}: {$tot} ردیف؛ بیشینه به ازای rel_id: " . implode( ' · ', array_map( function ( $x ) { return "{$x->rel_id}={$x->c}"; }, $by ) ) );
}

/* LQ-SCALE-03 · 04 — پرس‌وجو، زمان، اندازهٔ HTML + تکراری/کند (SAVEQUERIES) */
$uid_post = function ( $uid ) use ( $wpdb ) { return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='entity_uid' AND meta_value=%s LIMIT 1", $uid ) ); };
$targets = array( 'استان اصفهان' => $uid_post( home_url( '/e/province/isfahan' ) ), 'شهر اصفهان' => $uid_post( home_url( '/e/city/isfahan' ) ) );
/* SAVEQUERIES ثابت است و در هر پرس‌وجو خوانده می‌شود؛ تعریف در زمان اجرا فقط در همین فرایند، wp-config دست نمی‌خورد */
if ( ! defined( 'SAVEQUERIES' ) ) { define( 'SAVEQUERIES', true ); }
$wpdb->save_queries = true;
foreach ( $targets as $label => $pid ) {
	if ( ! $pid ) { $say( 'LQ-SCALE-03', "$label: پست یافت نشد" ); continue; }
	foreach ( array( 'fa', 'en' ) as $lang ) {
		$id = (int) apply_filters( 'wpml_object_id', $pid, get_post_type( $pid ), false, $lang );
		if ( ! $id ) { $say( 'LQ-SCALE-03', "$label $lang: ترجمه نیست" ); continue; }
		do_action( 'wpml_switch_language', $lang );
		$GLOBALS['post'] = get_post( $id ); setup_postdata( $GLOBALS['post'] );
		if ( ! wp_using_ext_object_cache() ) { wp_cache_flush(); }                                  /* سرد: کش شیء خالی، مثل نخستین بازدید */
		$wpdb->queries = array();
		$n0 = $wpdb->num_queries; $t0 = microtime( true );
		$h  = do_shortcode( '[tp_page id=' . $id . ']' );
		$dt = microtime( true ) - $t0; $nq = $wpdb->num_queries - $n0;
		$qs = $wpdb->queries; $sum = 0; $by = array(); $slow = array();
		foreach ( $qs as $q ) {
			$sum += $q[1]; $norm = preg_replace( array( "/'[^']*'/", '/\b\d+\b/' ), array( "'?'", 'N' ), $q[0] ); $by[ $norm ] = ( isset( $by[ $norm ] ) ? $by[ $norm ] : 0 ) + 1; $slow[] = array( $q[1], $q[0] );
		}
		arsort( $by ); usort( $slow, function ( $a, $b ) { return $b[0] <=> $a[0]; } );
		$dups = 0; foreach ( $by as $c ) { if ( $c > 1 ) { $dups += $c - 1; } }
		$say( 'LQ-SCALE-03', sprintf( '%s %s (#%d): %d پرس‌وجو · %.2f s کل · %.2f s در پایگاه‌داده · %d KB HTML', $label, $lang, $id, $nq, $dt, $sum, strlen( $h ) / 1024 ) );
		$say( 'LQ-SCALE-04', sprintf( '%s %s: %d الگوی یکتا، %d تکرار اضافه؛ پرتکرارترین: %s', $label, $lang, count( $by ), $dups, implode( ' ‖ ', array_map( function ( $s, $c ) { return $c . '× ' . mb_substr( preg_replace( '/\s+/', ' ', $s ), 0, 100 ); }, array_slice( array_keys( $by ), 0, 3 ), array_slice( array_values( $by ), 0, 3 ) ) ) ) );
		$say( 'LQ-SCALE-04', sprintf( '%s %s: کندترین: %s', $label, $lang, implode( ' ‖ ', array_map( function ( $s ) { return sprintf( '%.0fms ', $s[0] * 1000 ) . mb_substr( preg_replace( '/\s+/', ' ', $s[1] ), 0, 90 ); }, array_slice( $slow, 0, 3 ) ) ) ) );
	}
}
do_action( 'wpml_switch_language', 'fa' );
$wpdb->save_queries = false; $wpdb->queries = array();

/* LQ-SCALE-05 — کش شیء */
global $wp_object_cache;
$say( 'LQ-SCALE-05', 'کلاس کش شیء: ' . get_class( $wp_object_cache ) . ' · wp_using_ext_object_cache=' . var_export( wp_using_ext_object_cache(), true ) );

/* LQ-SCALE-06 — autoload (فقط نام و اندازه) */
$tot = $wpdb->get_row( "SELECT COUNT(*) c, ROUND(SUM(LENGTH(option_value))/1024) kb FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto-on','auto')" );
$top = $wpdb->get_results( "SELECT option_name n, ROUND(LENGTH(option_value)/1024, 1) kb FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto-on','auto') ORDER BY LENGTH(option_value) DESC LIMIT 10" );
$say( 'LQ-SCALE-06', "autoload: {$tot->c} گزینه · {$tot->kb} KB؛ ده بزرگ: " . implode( ' · ', array_map( function ( $r ) { return "{$r->n}={$r->kb}KB"; }, $top ) ) );

/* LQ-SCALE-07 — EXPLAIN چهار الگوی meta_value + شمار ردیف هر کلید */
$sample_uid  = (string) $wpdb->get_var( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='entity_uid' LIMIT 1" );
$app_key     = (string) $wpdb->get_var( "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_value='true' AND meta_key NOT LIKE '\\_%' GROUP BY meta_key ORDER BY COUNT(*) DESC LIMIT 1" );
$explain = function ( $sql, $args ) use ( $wpdb ) { $r = $wpdb->get_row( $wpdb->prepare( 'EXPLAIN ' . $sql, $args ), ARRAY_A ); return $r ? "type={$r['type']} key=" . ( $r['key'] ?: 'NULL' ) . " rows={$r['rows']} extra=" . ( isset( $r['Extra'] ) ? $r['Extra'] : '' ) : '(بی نتیجه)'; };
$say( 'LQ-SCALE-07', 'entity_uid=? → ' . $explain( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", array( 'entity_uid', $sample_uid ) ) );
$say( 'LQ-SCALE-07', 'name_fa IN (…) → ' . $explain( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value IN (%s,%s)", array( 'name_fa', 'اصفهان', 'شیراز' ) ) );
$say( 'LQ-SCALE-07', '_tp_news_ancestor=? → ' . $explain( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", array( '_tp_news_ancestor', '1' ) ) );
$say( 'LQ-SCALE-07', "کلید برنامه ({$app_key})='true' → " . $explain( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", array( $app_key, 'true' ) ) );
$kc = $wpdb->get_results( "SELECT meta_key k, COUNT(*) c FROM {$wpdb->postmeta} WHERE meta_key IN ('entity_uid','name_fa','name_en','name_ar','official_name_fa','_tp_news_ancestor','_tp_search_fold','_tp_content_hash') GROUP BY meta_key" );
$say( 'LQ-SCALE-07', 'ردیف هر کلید: ' . implode( ' · ', array_map( function ( $r ) { return "{$r->k}={$r->c}"; }, $kc ) ) );

/* LQ-SCALE-08 — هزینهٔ جستجو */
$t0 = microtime( true ); $q = new WP_Query( array( 's' => 'اصفهان', 'posts_per_page' => 10, 'no_found_rows' => false ) ); $dt = microtime( true ) - $t0;
$say( 'LQ-SCALE-08', sprintf( 'جستجوی «اصفهان»: %.3f s · %d یافته؛ SQL: %s', $dt, $q->found_posts, mb_substr( preg_replace( '/\s+/', ' ', $q->request ), 0, 220 ) ) );
$say( 'LQ-SCALE-08', 'EXPLAIN: ' . implode( ' ‖ ', array_map( function ( $r ) { return "{$r['table']}:{$r['type']}/" . ( $r['key'] ?: 'NULL' ) . "/rows={$r['rows']}"; }, (array) $wpdb->get_results( 'EXPLAIN ' . $q->request, ARRAY_A ) ) ) );

/* LQ-SCALE-09 — FULLTEXT */
$say( 'LQ-SCALE-09', 'نسخه=' . $wpdb->db_version() . ' ' . $wpdb->get_var( 'SELECT VERSION()' ) . ' · innodb_ft_min_token_size=' . $wpdb->get_row( "SHOW VARIABLES LIKE 'innodb_ft_min_token_size'" )->Value . ' · ngram=' . ( $wpdb->get_var( "SELECT COUNT(*) FROM information_schema.plugins WHERE plugin_name='ngram'" ) ? 'هست' : 'نیست' ) );

/* LQ-SCALE-14 — هش‌های کثیف: منتشرشده بی _tp_content_hash */
$say( 'LQ-SCALE-14', 'منتشرشدهٔ tp_ بی _tp_content_hash: ' . $cnt( "SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type LIKE 'tp\\_%' AND p.post_status='publish' AND NOT EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id=p.ID AND m.meta_key='_tp_content_hash')" ) );

/* LQ-SCALE-15 — transientهای کش مرکزی */
$tr = $wpdb->get_row( "SELECT COUNT(*) c, ROUND(SUM(LENGTH(option_value))/1024) kb FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_tpc\\_%'" );
$say( 'LQ-SCALE-15', "transient کش مرکزی (tpc_): {$tr->c} ردیف · {$tr->kb} KB؛ کل transient: " . $cnt( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_%'" ) );

/* LQ-SCALE-18 — صف AS */
$ast = $wpdb->get_results( "SELECT status, COUNT(*) c FROM {$P}actionscheduler_actions GROUP BY status" );
$say( 'LQ-SCALE-18', 'AS: ' . ( $ast ? implode( ' · ', array_map( function ( $r ) { return "{$r->status}={$r->c}"; }, $ast ) ) : 'خالی' ) . ' · logs=' . $cnt( "SELECT COUNT(*) FROM {$P}actionscheduler_logs" ) );
