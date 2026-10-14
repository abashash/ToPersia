<?php
/**
 * کاوشگر Overpass (فقط محلی، فقط خواندن) — کدام پرس‌وجو و کدام آینه پاسخ می‌دهد؟
 *   php scripts/local/overpass-probe.php
 */
$ca = 'C:/wamp64/www/topersia/geo/wp-includes/certificates/ca-bundle.crt';
$bb = '[bbox:25,43.9,40.1,63.4]';
$qs = array(
	'status'      => null,
	'prov4'       => "[out:json][timeout:120]{$bb};rel[\"boundary\"=\"administrative\"][\"admin_level\"=\"4\"];out tags center;",
	'admin8-count' => "[out:json][timeout:200]{$bb};rel[\"boundary\"=\"administrative\"][\"admin_level\"=\"8\"];out count;",
	'place-rel'   => "[out:json][timeout:200]{$bb};rel[\"place\"~\"^(city|town)$\"];out count;",
	'place-way'   => "[out:json][timeout:200]{$bb};way[\"place\"~\"^(city|town)$\"];out count;",
);
$mirrors = array( 'https://overpass-api.de/api/interpreter', 'https://lz4.overpass-api.de/api/interpreter', 'https://overpass.private.coffee/api/interpreter', 'https://overpass.kumi.systems/api/interpreter' );
foreach ( $qs as $name => $q ) {
	foreach ( $mirrors as $m ) {
		$t  = microtime( true );
		$ch = curl_init( null === $q ? str_replace( 'interpreter', 'status', $m ) : $m );
		$o  = array( CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 240, CURLOPT_USERAGENT => 'ToPersia-local-boundary-script/1.1', CURLOPT_CAINFO => $ca );
		if ( null !== $q ) { $o[ CURLOPT_POST ] = true; $o[ CURLOPT_POSTFIELDS ] = http_build_query( array( 'data' => $q ) ); }
		curl_setopt_array( $ch, $o );
		$b = (string) curl_exec( $ch );
		$code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$j = json_decode( $b, true );
		$info = is_array( $j ) ? ( isset( $j['elements'] ) ? count( $j['elements'] ) . ' el ' . json_encode( isset( $j['elements'][0]['tags'] ) ? $j['elements'][0]['tags'] : '', JSON_UNESCAPED_UNICODE ) : '' ) : trim( preg_replace( '/\s+/', ' ', strip_tags( substr( $b, 0, 300 ) ) ) );
		printf( "%-12s %-45s HTTP %s %5.1fs %s\n", $name, str_replace( 'https://', '', $m ), $code, microtime( true ) - $t, substr( $info, 0, 140 ) );
	}
}
