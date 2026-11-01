<?php
/**
 * شمارش میزبان entity_uid — فقط‌خواندنی، بی هیچ نوشتن.  P5-D495
 *
 *   wp eval-file scripts/tp-uid-hosts.php            گزارش: topersia.com · محلی · دیگر · خالی، و فهرست غیرنمونهٔ محلی
 *   wp eval-file scripts/tp-uid-hosts.php count     فقط شمار غیرنمونهٔ محلی (هشدار preflight)
 *   wp eval-file scripts/tp-uid-hosts.php strict   LAUNCH H-11: اگر غیرنمونهٔ محلی بود کد خروج ۱
 *
 * همهٔ نوع‌های tp_، همهٔ وضعیت‌ها جز auto-draft، همهٔ ترجمه‌ها.
 */
global $wpdb;

$mode = isset( $args[0] ) ? (string) $args[0] : '';
$rows = $wpdb->get_results(
	"SELECT p.ID, p.post_type, p.post_status, u.meta_value AS uid, COALESCE( t.language_code, '-' ) AS lang, s.meta_value AS sample
	FROM {$wpdb->posts} p
	LEFT JOIN {$wpdb->postmeta} u ON u.post_id = p.ID AND u.meta_key = 'entity_uid'
	LEFT JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = '_tp_sample'
	LEFT JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = CONCAT( 'post_', p.post_type )
	WHERE p.post_type LIKE 'tp\\_%' AND p.post_status <> 'auto-draft'
	GROUP BY p.ID
	ORDER BY p.post_type, p.ID"
);

$bucket = function ( $uid ) {
	if ( '' === (string) $uid ) { return 'خالی'; }
	$host = strtolower( (string) wp_parse_url( (string) $uid, PHP_URL_HOST ) );
	if ( 'topersia.com' === $host ) { return 'topersia.com'; }
	return ( 'localhost' === $host || '127.0.0.1' === $host || preg_match( '/\.(local|localhost|test)$/', $host ) ) ? 'محلی' : 'دیگر';
};

$count = array();
$local = array();
foreach ( $rows as $r ) {
	$b = $bucket( $r->uid );
	$k = $r->sample ? 'نمونه' : 'غیرنمونه';
	$count[ $b ][ $k ]                        = ( $count[ $b ][ $k ] ?? 0 ) + 1;
	$count[ $b ][ 'وضعیت ' . $r->post_status ] = ( $count[ $b ][ 'وضعیت ' . $r->post_status ] ?? 0 ) + 1;
	$count[ $b ][ 'زبان ' . $r->lang ]         = ( $count[ $b ][ 'زبان ' . $r->lang ] ?? 0 ) + 1;
	if ( 'محلی' === $b && ! $r->sample ) { $local[] = $r; }
}

if ( 'count' === $mode ) {
	echo count( $local ) . "\n";
	return;
}

echo 'همهٔ پست‌ها: ' . count( $rows ) . "\n";
foreach ( $count as $b => $c ) {
	ksort( $c );
	$parts = array();
	foreach ( $c as $k => $n ) { $parts[] = "{$k}={$n}"; }
	echo "{$b}: " . implode( ' · ', $parts ) . "\n";
}
echo 'غیرنمونه با میزبان محلی: ' . count( $local ) . "\n";
foreach ( $local as $r ) {
	echo "#{$r->ID} {$r->post_type} {$r->post_status} {$r->lang} {$r->uid}\n";
}

if ( 'strict' === $mode && $local ) {
	exit( 1 );
}
