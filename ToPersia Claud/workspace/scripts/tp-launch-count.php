<?php
/**
 * شمارشگر راه‌اندازی — دستۀ 55 بندهای ۸ و ۹ (P5-D633 · P5-D634). فقط خواندن؛ فقط topersia.local.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-launch-count.php
 *
 * موجودیت = پست منتشرشدۀ نوع‌های هاب (topersia_g_hub_registry)، هر سه زبان، بی نمایشی (_tp_demo) و بی نمونه (_tp_sample).
 *   NO_SEASON n {نوع: n}      موجودیت بی «بهترین فصل» در نوع‌های دارای این فیلد (بند ۸ — هشدار، نه شکست)
 *   SHARED n {پیوست: n}       تصویر خود موجودیت (hero_image ← تصویر شاخص؛ topersia_g_card_image) که در بیش از ۵ موجودیت آمده (بند ۹)
 *   NO_ALT n [پیوست…]         تصویر خود موجودیت بی متن جایگزین (_wp_attachment_image_alt خالی) (بند ۹)
 * assert-55 با --launch همین را اجرا و گزارش می‌کند. خروجی همیشه ۰ (شمارشگر است، حکم نمی‌دهد).
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "فقط با wp eval-file\n" );
	exit( 2 );
}
if ( 'topersia.local' !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) || ! function_exists( 'topersia_g_hub_registry' ) ) {
	echo "SKIP\n";
	exit( 0 );
}

const TP_LC_SHARED_MAX = 5;   /* بند ۹: «تصویر استفاده‌شده در بیش از ۵ موجودیت» */

$tp_reg   = topersia_g_hub_registry();
$tp_types = array_keys( $tp_reg );
$tp_ids   = $tp_types ? get_posts( array(
	'post_type'        => $tp_types,
	'post_status'      => 'publish',
	'posts_per_page'   => -1,
	'fields'           => 'ids',
	'no_found_rows'    => true,
	'suppress_filters' => true,   /* هر سه زبان */
	'meta_query'       => array(
		array( 'key' => '_tp_demo', 'compare' => 'NOT EXISTS' ),
		array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ),
	),
) ) : array();

$no_season = array();
$uses      = array();
foreach ( $tp_ids as $id ) {
	$type = (string) get_post_type( $id );
	if ( '' !== $tp_reg[ $type ]['season'] ) {
		$v     = get_post_meta( $id, 'best_season', true );
		$empty = is_array( $v ) ? ! array_filter( $v, function ( $x ) { return '' !== (string) $x && 'false' !== (string) $x; } ) : '' === trim( (string) $v );
		if ( $empty ) {
			$no_season[ $type ] = isset( $no_season[ $type ] ) ? $no_season[ $type ] + 1 : 1;
		}
	}
	$att = absint( topersia_g_card_image( $id ) );
	if ( $att ) {
		$uses[ $att ] = isset( $uses[ $att ] ) ? $uses[ $att ] + 1 : 1;
	}
}

ksort( $no_season );
$shared = array_filter( $uses, function ( $n ) { return $n > TP_LC_SHARED_MAX; } );
arsort( $shared );
$no_alt = array_values( array_filter( array_keys( $uses ), function ( $att ) {
	return '' === trim( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ) );
} ) );
sort( $no_alt );

echo 'NO_SEASON ', array_sum( $no_season ), ' ', wp_json_encode( $no_season ), "\n";
echo 'SHARED ', count( $shared ), ' ', wp_json_encode( $shared ), "\n";
echo 'NO_ALT ', count( $no_alt ), ' ', wp_json_encode( array_slice( $no_alt, 0, 20 ) ), "\n";
exit( 0 );
