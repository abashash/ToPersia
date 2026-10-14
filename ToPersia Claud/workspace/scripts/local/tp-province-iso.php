<?php
/**
 * reference/province-iso.csv را از استان‌های منتشرشدهٔ فارسی می‌سازد — P5-D363.  یک‌بار؛ فقط محلی.
 *
 *   wp eval-file scripts/local/tp-province-iso.php
 *
 * province_code پایگاه کد آماری ملی (۰۱…۳۹) است، نه ISO 3166-2؛ جدول زیر ثابت و برای تأیید کاربر در CSV می‌آید.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) { exit; }

$iso = array(
	'east-azerbaijan' => 'IR-01', 'west-azerbaijan' => 'IR-02', 'ardabil' => 'IR-03', 'isfahan' => 'IR-04', 'ilam' => 'IR-05', 'bushehr' => 'IR-06',
	'tehran-province' => 'IR-07', 'chaharmahal-and-bakhtiari' => 'IR-08', 'khuzestan' => 'IR-10', 'zanjan' => 'IR-11', 'semnan' => 'IR-12',
	'sistan-and-baluchestan' => 'IR-13', 'fars' => 'IR-14', 'kerman' => 'IR-15', 'kurdistan' => 'IR-16', 'kermanshah' => 'IR-17',
	'kohgiluyeh-and-boyer-ahmad' => 'IR-18', 'gilan' => 'IR-19', 'lorestan' => 'IR-20', 'mazandaran' => 'IR-21', 'markazi' => 'IR-22',
	'hormozgan' => 'IR-23', 'hamadan' => 'IR-24', 'yazd' => 'IR-25', 'qom' => 'IR-26', 'golestan' => 'IR-27', 'qazvin' => 'IR-28',
	'south-khorasan' => 'IR-29', 'razavi-khorasan' => 'IR-30', 'north-khorasan' => 'IR-31', 'alborz' => 'IR-32',
);

do_action( 'wpml_switch_language', 'fa' );
$ids  = get_posts( array( 'post_type' => 'tp_province', 'post_status' => 'publish', 'numberposts' => -1, 'suppress_filters' => false, 'orderby' => 'ID', 'order' => 'ASC' ) );
$file = dirname( __DIR__, 2 ) . '/reference/province-iso.csv';
$fh   = fopen( $file, 'w' );
fputcsv( $fh, array( 'entity_uid', 'ISO3166-2', 'name_fa', 'province_code_in_db' ), ',', '"', '\\' );
$n = 0; $seen = array();

foreach ( $ids as $p ) {
	$uid  = (string) get_post_meta( $p->ID, 'entity_uid', true );
	$slug = preg_replace( '~^https?://[^/]+/e/province/~', '', $uid );
	if ( ! isset( $iso[ $slug ] ) ) { echo "بی ISO: {$uid}\n"; continue; }
	if ( isset( $seen[ $slug ] ) ) { continue; }
	$seen[ $slug ] = 1;
	fputcsv( $fh, array( $uid, $iso[ $slug ], $p->post_title, get_post_meta( $p->ID, 'province_code', true ) ), ',', '"', '\\' );
	$n++;
}
fclose( $fh );
echo "ردیف: {$n} · ISO یکتا: " . count( array_unique( array_intersect_key( $iso, $seen ) ) ) . "\n";
