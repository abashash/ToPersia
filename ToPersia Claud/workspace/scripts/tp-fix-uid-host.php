<?php
/**
 * P5-D499: برگرداندن میزبان entity_uid به مبدأ ثابت؛ مسیر دست نمی‌خورد.
 *
 * پیش‌فرض آزمایشی است و چیزی نمی‌نویسد. فقط با آرگومان apply و فقط روی محلی می‌نویسد؛ پیش از آن پشتیبان بگیر:
 *   php scripts/tp-backup.php --no-prune
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-fix-uid-host.php          # آزمایشی
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-fix-uid-host.php apply    # نوشتن
 *
 * همهٔ نوع‌ها، وضعیت‌ها و ترجمه‌ها، نمونه‌ها هم. تکرارش بی‌اثر است. دو پست از دو خانوادهٔ ترجمه با یک مسیر «تعارض» است:
 * شمرده و فهرست می‌شود ولی نوشته نمی‌شود. نوشتن مستقیم روی متاست: تاریخ ویرایش پست و قلاب‌ها اجرا نمی‌شوند.
 */
global $wpdb;

$apply = in_array( 'apply', isset( $args ) ? (array) $args : array(), true );

if ( $apply && 'local' !== wp_get_environment_type() ) {
	echo "نوشتن فقط روی محلی\n";
	exit( 2 );
}

$host_of = function ( $uid ) {
	$host = strtolower( (string) wp_parse_url( (string) $uid, PHP_URL_HOST ) );
	return '' === $host ? '(بی میزبان)' : $host;
};

$rows = $wpdb->get_results(
	"SELECT m.meta_id, m.post_id, m.meta_value, p.post_type, p.post_status, t.trid
	FROM {$wpdb->postmeta} m
	JOIN {$wpdb->posts} p ON p.ID = m.post_id
	LEFT JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = CONCAT( 'post_', p.post_type )
	WHERE m.meta_key = 'entity_uid' AND m.meta_value <> ''
	GROUP BY m.meta_id"
);

$by_path = array();
$hosts   = array();
$unparse = array();

foreach ( $rows as $r ) {
	$hosts[ $host_of( $r->meta_value ) ] = ( $hosts[ $host_of( $r->meta_value ) ] ?? 0 ) + 1;
	$path = topersia_uid_path( $r->meta_value );
	if ( '' === $path ) {
		$unparse[] = $r;
		continue;
	}
	$by_path[ $path ][] = $r;
}

/* یک مسیر در بیش از یک خانوادهٔ ترجمه، یا بیش از یک نوع پست */
$conflict = array();
$todo     = array();

foreach ( $by_path as $path => $list ) {
	$owners = array();
	foreach ( $list as $r ) {
		$owners[ $r->post_type . ':' . ( $r->trid ? 't' . $r->trid : 'p' . $r->post_id ) ] = 1;
	}
	if ( count( $owners ) > 1 ) {
		$conflict[ $path ] = $list;
		continue;
	}
	$want = topersia_uid_build( $path );
	foreach ( $list as $r ) {
		if ( $r->meta_value !== $want ) {
			$todo[] = array( $r, $want );
		}
	}
}

ksort( $hosts );
echo '== ' . ( $apply ? 'نوشتن' : 'آزمایشی' ) . " ==\n";
echo 'مبدأ ثابت: ' . TOPERSIA_CANONICAL_ORIGIN . "\n";
echo 'پیش: ' . wp_json_encode( $hosts, JSON_UNESCAPED_UNICODE ) . "\n";
echo 'نیازمند تغییر: ' . count( $todo ) . ' · مسیر در تعارض: ' . count( $conflict ) . ' · نشانی بیرون از /e/: ' . count( $unparse ) . "\n";

foreach ( $conflict as $path => $list ) {
	echo "  تعارض {$path}: " . implode( ' · ', array_map( function ( $r ) { return "#{$r->post_id} {$r->post_type} {$r->post_status} {$r->meta_value}"; }, $list ) ) . "\n";
}
foreach ( $unparse as $r ) {
	echo "  بیرون از /e/: #{$r->post_id} {$r->post_type} {$r->meta_value}\n";
}

$blank = $wpdb->get_results(
	"SELECT p.ID, p.post_type, p.post_status FROM {$wpdb->posts} p
	LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = 'entity_uid'
	WHERE p.post_type LIKE 'tp\\_%' AND p.post_status <> 'auto-draft' AND ( m.meta_value IS NULL OR m.meta_value = '' )"
);
echo 'uid خالی (دست نمی‌خورد): ' . count( $blank ) . "\n";
foreach ( $blank as $b ) {
	echo "  #{$b->ID} {$b->post_type} {$b->post_status}\n";
}

if ( ! $apply ) {
	echo "چیزی نوشته نشد.\n";
	return;
}

/* پیش از نوشتن: نقشهٔ برگشت بیرون از مخزن */
$undo = trailingslashit( get_temp_dir() ) . 'tp-uid-host-undo-' . gmdate( 'Ymd-His' ) . '.json';
file_put_contents( $undo, wp_json_encode( array_map( function ( $x ) { return array( 'meta_id' => (int) $x[0]->meta_id, 'post' => (int) $x[0]->post_id, 'old' => $x[0]->meta_value ); }, $todo ), JSON_UNESCAPED_SLASHES ) );
echo "نقشهٔ برگشت: {$undo}\n";

$done = 0;
foreach ( $todo as $x ) {
	if ( false !== $wpdb->update( $wpdb->postmeta, array( 'meta_value' => $x[1] ), array( 'meta_id' => (int) $x[0]->meta_id ) ) ) {
		$done++;
		wp_cache_delete( (int) $x[0]->post_id, 'post_meta' );
	}
}

$after = array();
foreach ( $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'entity_uid' AND meta_value <> ''" ) as $v ) {
	$after[ $host_of( $v ) ] = ( $after[ $host_of( $v ) ] ?? 0 ) + 1;
}
ksort( $after );
echo "نوشته شد: {$done}\n";
echo 'پس: ' . wp_json_encode( $after, JSON_UNESCAPED_UNICODE ) . "\n";
