<?php
/**
 * P5-D455 (دستهٔ ۳۱ الف) — نوع «استان» جعبهٔ مسیریابی ندارد. روی وردپرس محلی؛ فقط می‌خواند (GET).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/province-no-routing-local.php [nohttp]
 *
 * هر استان (fa · en · ar): در HTML صفحه `id="tp-routing"` و `data-tp-nav-box` نیست؛ مختصات مرکز استان در داده می‌ماند
 * (topersia_g_routing_point). شهر و بنای تاریخی جعبه را نگه می‌دارند. جهش: اگر نگهبان استان از تابع مقصد برداشته شود،
 * همان تابع برای استان مقصد می‌دهد — پس آزمون «تهی بودن» به همان شرط بند است.
 */
$http = ! in_array( 'nohttp', (array) ( $args ?? array() ), true );
$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) {
	$ok ? $pass++ : $fail++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
$url = function ( $id, $l ) { do_action( 'wpml_switch_language', $l ); $u = get_permalink( $id ); do_action( 'wpml_switch_language', 'fa' ); return $u; };
$get = function ( $u ) {
	$r = wp_remote_get( $u, array( 'timeout' => 90, 'sslverify' => false, 'proxy' => '' ) );
	return is_wp_error( $r ) ? array( 0, '' ) : array( (int) wp_remote_retrieve_response_code( $r ), (string) wp_remote_retrieve_body( $r ) );
};
$has = function ( $h ) { return false !== strpos( $h, 'id="tp-routing"' ) || false !== strpos( $h, 'data-tp-nav-box' ); };

$provs = get_posts( array( 'post_type' => 'tp_province', 'numberposts' => -1, 'post_status' => 'publish', 'fields' => 'ids', 'suppress_filters' => false, 'meta_query' => array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) ) );

$target_bad = array(); $point_miss = 0;
foreach ( $provs as $p ) {
	if ( array() !== topersia_g_routing_target( $p ) ) { $target_bad[] = $p; }
	if ( array() === topersia_g_routing_point( $p ) ) { $point_miss++; }
}
$t( 'مقصد مسیریابی هر استان تهی — ' . count( $provs ) . ' استان', ! $target_bad, implode( ',', $target_bad ) );
echo '  · استان با نقطهٔ مرکز در داده: ' . ( count( $provs ) - $point_miss ) . ' از ' . count( $provs ) . " (بقیه در انتظار P5-D399)\n";

/* جهش: همان تابع بی نگهبان استان */
$src = file_get_contents( WP_PLUGIN_DIR . '/topersia-render/includes/topersia-g-routing.php' );
$a   = strpos( $src, 'function topersia_g_routing_target(' ); $b = strpos( $src, "/** برچسب هر دکمه", $a );
$fn  = substr( $src, $a, $b - $a );
$g1 = strpos( $fn, "if ( 'tp_province' ===" ); $g2 = false === $g1 ? false : strpos( $fn, "return array();", $g1 );
$n = ( false !== $g1 && false !== $g2 ) ? 1 : 0;
$cut = $n ? substr( $fn, 0, $g1 ) . substr( $fn, strpos( $fn, "}", $g2 ) + 1 ) : $fn;
$fn  = substr( $fn, 0, strrpos( $fn, '}' ) ); /* بستن if(!function_exists) */
$cut = substr( $cut, 0, strrpos( $cut, '}' ) );
$t( 'جهش: نگهبان استان در کد پیدا و برداشته می‌شود', 1 === $n );
if ( 1 === $n ) {
	eval( str_replace( 'function topersia_g_routing_target(', 'function topersia_g_routing_target_mut(', $cut ) );
	$mut = array_filter( array_map( 'topersia_g_routing_target_mut', array_slice( $provs, 0, 31 ) ) );
	$t( 'جهش: بی نگهبان دست‌کم یک استان مقصد می‌گیرد (آزمون می‌افتد)', (bool) $mut, count( $mut ) . ' استان' );
}

if ( $http ) {
	$bad = array(); $n200 = 0; $tot = 0;
	foreach ( $provs as $p ) {
		foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
			$tid = 'fa' === $l ? $p : (int) apply_filters( 'wpml_object_id', $p, 'tp_province', false, $l );
			if ( ! $tid ) { continue; }
			$tot++;
			list( $code, $h ) = $get( $url( $tid, $l ) );
			if ( 200 === $code ) { $n200++; }
			if ( 200 !== $code || $has( $h ) ) { $bad[] = "$l:$tid($code)"; }
		}
	}
	$t( 'هر استان در fa · en · ar: ۲۰۰ و بی جعبهٔ مسیریابی — ' . $tot . ' صفحه', ! $bad && $n200 === $tot, implode( ' ', array_slice( $bad, 0, 12 ) ) );

	foreach ( array( 'tp_city' => 'isfahan', 'tp_monument' => 'naqsh-e-jahan-square' ) as $type => $slug ) {
		$ids = get_posts( array( 'post_type' => $type, 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids', 'name' => $slug, 'suppress_filters' => false ) );
		$id  = $ids ? $ids[0] : 0;
		if ( ! $id ) {
			$ids = get_posts( array( 'post_type' => $type, 'numberposts' => 1, 'post_status' => 'publish', 'fields' => 'ids', 'suppress_filters' => false, 'meta_query' => array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) ) );
			$id  = $ids ? $ids[0] : 0;
		}
		list( $code, $h ) = $id ? $get( $url( $id, 'fa' ) ) : array( 0, '' );
		$t( $type . ' (#' . $id . '): جعبهٔ مسیریابی هست', 200 === $code && $has( $h ), "code=$code" );
	}
}
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
