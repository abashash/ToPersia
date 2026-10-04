<?php
/**
 * فاز ۲ج SPRINT-4.4 — دادۀ محلی: قالب «ToPersia — G Entity Single» برای هر نوع صفحه‌دار.
 *
 *   php scripts/tp-local-2c.php --report    فقط گزارش
 *   php scripts/tp-local-2c.php --apply     افزودن شرط نمایش نوع‌های جاافتاده
 *
 * چرا: قالب تک‌صفحۀ المنتور (`[tp_page]`) فقط سیزده نوع G را شرط نمایش داشت؛ ۲۹
 * نوع دیگر (بنا، موزه، فرهنگ، رویداد، اطلاعات سفر) با تک‌صفحۀ پیش‌فرض پوسته
 * رندر می‌شدند و چیدمان استان (P5-D275) اصلاً به آن‌ها نمی‌رسید. شرط از راه API
 * خودِ المنتور پرو (`Conditions_Manager::save_conditions`) نوشته می‌شود، همان که
 * پنجرۀ Display Conditions می‌نویسد؛ شرط‌های موجود دست نمی‌خورند.
 *
 * فقط topersia.local. روی زنده همین کار در Theme Builder با دست (HANDOVER بخش ۷).
 */

$_SERVER['HTTP_HOST']   = 'topersia.local';
$_SERVER['REQUEST_URI'] = '/';

require 'C:/wamp64/www/topersia/geo/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

$apply = in_array( '--apply', $argv, true );

if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) || ! function_exists( 'topersia_g_kind_labels' ) ) {
	fwrite( STDERR, "المنتور پرو یا render نیست\n" );
	exit( 2 );
}

$module  = \ElementorPro\Modules\ThemeBuilder\Module::instance();
$all     = (array) get_option( 'elementor_pro_theme_builder_conditions', array() );
$tpl     = 0;

/* قالبی که دست‌کم یک نوع tp_ را دارد و [tp_page] را می‌سازد */
foreach ( isset( $all['single'] ) ? (array) $all['single'] : array() as $id => $conds ) {
	if ( 'publish' === get_post_status( $id ) && preg_grep( '~^include/singular/tp_~', (array) $conds ) ) {
		$tpl = (int) $id;
	}
}

if ( ! $tpl ) {
	fwrite( STDERR, "قالب تک‌صفحۀ ToPersia پیدا نشد\n" );
	exit( 1 );
}

$have = (array) get_post_meta( $tpl, '_elementor_conditions', true );
$want = array();

foreach ( array_keys( topersia_g_kind_labels() ) as $type ) {
	if ( ! in_array( $type, topersia_g_no_page_types(), true ) && post_type_exists( $type ) ) {
		$want[] = 'include/singular/' . $type;
	}
}

$missing = array_values( array_diff( $want, $have ) );

echo 'قالب #' . $tpl . ' «' . get_the_title( $tpl ) . '» — ' . count( $have ) . ' شرط، جاافتاده: ' . count( $missing ) . "\n";
echo $missing ? '  ' . implode( "\n  ", $missing ) . "\n" : '';

if ( ! $apply || ! $missing ) {
	exit( 0 );
}

$conditions = array();

foreach ( array_merge( $have, $missing ) as $line ) {
	$parts        = explode( '/', $line );
	$conditions[] = array(
		'type'     => isset( $parts[0] ) ? $parts[0] : 'include',
		'name'     => isset( $parts[1] ) ? $parts[1] : '',
		'sub_name' => isset( $parts[2] ) ? $parts[2] : '',
		'sub_id'   => isset( $parts[3] ) ? $parts[3] : '',
	);
}

$ok = $module->get_conditions_manager()->save_conditions( $tpl, $conditions );

echo $ok ? 'نوشته شد — ' . count( (array) get_post_meta( $tpl, '_elementor_conditions', true ) ) . " شرط\n" : "نوشتن نشد\n";
exit( $ok ? 0 : 1 );
