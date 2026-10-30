<?php
/**
 * دستهٔ 36 — مرتب‌سازی پنجرهٔ فهرست (P5-D465) و برگه‌های «در یک نگاه» (P5-D466). از assert.php بار می‌شود؛ PHP خالص با stub.
 *
 * فهرست کامل در HTML سرور می‌ماند و فقط ترتیب را جاوااسکریپت عوض می‌کند؛ مقدار ناموجود آخر؛ دکمه‌ها aria-pressed.
 * برگه‌ها: هر سه متن در HTML سرور، الگوی tablist. رفتار روی صفحهٔ واقعی: tests/behaviour.js · tests/sort-tabs-local.js.
 */

echo "\nدستهٔ 36 — مرتب‌سازی فهرست و برگه‌های در یک نگاه  P5-D465 · P5-D466\n" . str_repeat( '─', 78 ) . "\n";

$js36  = (string) file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-zones.js' );
$css36 = (string) file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' );

/* ── عدد مرتب‌سازی: فقط ارقام، لاتین؛ ناموجود ← '' ── */
$GLOBALS['tp_meta'][9601] = array( 'population' => '۱٬۲۳۴٬۵۶۷', 'area_km2' => '105.5' );
$GLOBALS['tp_meta'][9602] = array( 'population' => 'نامعلوم', 'area_km2' => '' );
$num_ok = function ( $x ) {
	return '1234567' === $x[0] && '105.5' === $x[1] && '' === $x[2] && '' === $x[3];
};
$num36 = array( topersia_g_sort_num( 9601, 'population' ), topersia_g_sort_num( 9601, 'area_km2' ), topersia_g_sort_num( 9602, 'population' ), topersia_g_sort_num( 9602, 'area_km2' ) );
tp441_mut( 'مرتب‌سازی: عدد جمعیت/وسعت از فیلد موجود (ارقام فارسی و جداکننده ← لاتین)؛ متن غیرعددی و خالی ← «» (آخر فهرست) — جهش: متن غیرعددی عدد شود می‌افتد',
	$num_ok, $num36, array( $num36[0], $num36[1], '0', $num36[3] ) );

/* ── پوسته: نوار فقط وقتی sortable؛ li داده‌صفت می‌گیرد؛ فهرست کامل در HTML ── */
$blocks36 = array( array( 'head' => '', 'items' => array(
	array( 'name' => 'الف', 'meta' => '', 'url' => 'https://topersia.com/a/', 'pop' => '300', 'area' => '10' ),
	array( 'name' => 'ب', 'meta' => '', 'url' => 'https://topersia.com/b/', 'pop' => '', 'area' => '20' ),
	array( 'name' => 'پ', 'meta' => '', 'url' => 'https://topersia.com/c/', 'pop' => '100', 'area' => '' ),
) ) );
$plain36 = topersia_g_list_modal_html( 'tp-list-x', 'استان‌ها', $blocks36 );
$sort36  = topersia_g_list_modal_html( 'tp-list-x', 'استان‌ها', $blocks36, true );
$bar_ok = function ( $x ) {
	return false === strpos( $x[0], 'data-tp-sort' ) && false === strpos( $x[0], 'data-tp-pop' )
		&& 1 === substr_count( $x[1], '<div class="tp-list__sort" role="group"' )
		&& 4 === substr_count( $x[1], 'data-tp-sort-by="' ) && 4 === substr_count( $x[1], 'aria-pressed="' )
		&& 1 === substr_count( $x[1], 'aria-pressed="true"' )
		&& false !== strpos( $x[1], 'data-tp-sort-by="alpha"' ) && false !== strpos( $x[1], 'data-tp-sort-by="pop"' ) && false !== strpos( $x[1], 'data-tp-sort-by="area"' )
		&& 3 === substr_count( $x[1], '<li class="tp-list__item"' ) && 3 === substr_count( $x[1], 'href="https://topersia.com/' )   /* همهٔ عضوها و پیوندها در HTML سرور */
		&& false !== strpos( $x[1], '<li class="tp-list__item" data-tp-pop="300" data-tp-area="10">' )
		&& false !== strpos( $x[1], '<li class="tp-list__item" data-tp-area="20">' )                                                  /* ناموجود ← صفت نیامده */
		&& false !== strpos( $x[1], '<li class="tp-list__item" data-tp-pop="100">' )
		&& false === strpos( $x[1], 'data-tp-pop=""' ) && false === strpos( $x[1], 'data-tp-area=""' );
};
tp441_mut( 'پنجرهٔ فهرست: نوار مرتب‌سازی (پیش‌فرض · الفبا · جمعیت · وسعت، aria-pressed) فقط برای sortable؛ هر عضو با پیوند در HTML؛ مقدار ناموجود صفت ندارد — جهش: نوار بی aria-pressed می‌افتد',
	$bar_ok, array( $plain36, $sort36 ), array( $plain36, str_replace( ' aria-pressed="false"', '', $sort36 ) ) );

/* ── کشور و استان ← sortable؛ شهر/جاذبه/فرهنگ نه (از منبع) ── */
$z36 = (string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );
$gate_ok = function ( $src ) {
	return false !== strpos( $src, "( 'provinces' === \$key && 'tp_country' === \$ptype ) || ( 'places' === \$key && 'tp_province' === \$ptype )" );
};
t( 'مرتب‌سازی فقط برای کشور←استان‌ها و استان←شهرها (از منبع) — جهش: برای همهٔ کلیدها باز شود می‌افتد',
	$gate_ok( $z36 ) && ! $gate_ok( str_replace( "'places' === \$key && 'tp_province' === \$ptype", "'places' === \$key", $z36 ) ) );

/* ── جاوااسکریپت: Intl.Collator با زبان صفحه، ناموجود آخر، ترتیب پیش‌فرض برمی‌گردد، DOM فقط جابه‌جا می‌شود ── */
$js_sort_ok = function ( $j ) {
	return false !== strpos( $j, 'new Intl.Collator( lang )' ) && false !== strpos( $j, "document.documentElement.lang" )
		&& false !== strpos( $j, 'isNaN( a.n )' ) && false !== strpos( $j, 'an ? 1 : -1' )
		&& false !== strpos( $j, "box.tpSort( 'default' )" ) && false !== strpos( $j, "'aria-pressed'" )
		&& false !== strpos( $j, 'base: [].slice.call( ul.children )' )
		&& 0 === preg_match( '/tp-list__grid[^;]*innerHTML\s*=/', $j );
};
t( 'مرتب‌سازی (جاوااسکریپت): Intl.Collator با زبان صفحه · مقدار ناموجود آخر · هر بازشدن به ترتیب پیش‌فرض · بی innerHTML (چیزی ساخته نمی‌شود) — جهش: collator حذف شود می‌افتد',
	$js_sort_ok( $js36 ) && ! $js_sort_ok( str_replace( 'new Intl.Collator( lang )', 'null', $js36 ) )
	&& ! $js_sort_ok( str_replace( "if ( box.tpSort ) { box.tpSort( 'default' ); }", '', $js36 ) ) );

/* ── CSS: هدف لمسی ≥۴۴ و فقط توکن ── */
$sortcss_ok = function ( $css ) {
	return 1 === preg_match( '/html body \.tp-page \.tp-list__sortbtn\{[^}]*min-block-size:44px[^}]*var\(--tp-tile-bg\)[^}]*var\(--tp-tile-text\)/s', $css )
		&& 0 === preg_match( '/\.tp-list__sort[^{]*\{[^}]*(#[0-9a-fA-F]{3,6}|rgb\()/', $css )
		&& 1 === preg_match( '/\.tp-ai__btn\{min-block-size:44px\}/', $css );
};
t( 'مرتب‌سازی/برگه‌ها (CSS): دکمه‌ها ≥۴۴px، توکن کاشی، بی رنگ خام — جهش: ۴۴ → ۳۰ می‌افتد',
	$sortcss_ok( $css36 ) && ! $sortcss_ok( str_replace( '.tp-list__sortbtn{
  min-block-size:44px', '.tp-list__sortbtn{
  min-block-size:30px', $css36 ) ) );

/* ── برگه‌های «در یک نگاه»: tablist کامل؛ هر سه متن در HTML سرور؛ فقط یکی دیده می‌شود ── */
$ai36 = isset( $ai2 ) ? $ai2 : '';
$tabs_ok = function ( $h ) {
	return 1 === substr_count( $h, 'role="tablist"' ) && 3 === substr_count( $h, 'role="tab"' ) && 3 === substr_count( $h, 'role="tabpanel"' )
		&& 1 === substr_count( $h, 'aria-selected="true"' ) && 2 === substr_count( $h, 'aria-selected="false"' )
		&& 1 === substr_count( $h, 'tabindex="0" data-ai=' ) && 2 === substr_count( $h, 'tabindex="-1" data-ai=' )
		&& 2 === substr_count( $h, ' hidden>' )
		&& false !== strpos( $h, 'aria-controls="tp-ai-panel-plan"' ) && false !== strpos( $h, 'aria-labelledby="tp-ai-tab-plan"' )
		&& false !== strpos( $h, 'خلاصهٔ افزونه دربارهٔ اصفهان.' ) && false !== strpos( $h, 'سی‌وسه‌پل' ) && false !== strpos( $h, 'روز دوم: کاخ‌ها' );
};
tp441_mut( 'در یک نگاه: tablist (سه tab با aria-selected/tabindex گردان، سه tabpanel با aria-labelledby)، «کپی» برگه نیست، هر سه متن در HTML سرور — جهش: همهٔ aria-selected درست شود می‌افتد',
	$tabs_ok, $ai36, str_replace( 'aria-selected="false"', 'aria-selected="true"', $ai36 ) );

$key_ok = function ( $j ) {
	return false !== strpos( $j, ".tp-ai__tabs[role=\"tablist\"]" ) && false !== strpos( $j, "'ArrowRight' === e.key" ) && false !== strpos( $j, "'ArrowLeft' === e.key" )
		&& false !== strpos( $j, "'Home' === e.key" ) && false !== strpos( $j, "'End' === e.key" ) && false !== strpos( $j, "'rtl' === window.getComputedStyle( list ).direction" )
		&& false !== strpos( $j, "b.setAttribute( 'aria-selected'" ) && false !== strpos( $j, "b.setAttribute( 'tabindex'" );
};
t( 'در یک نگاه (جاوااسکریپت): کلید جهت از جهت واقعی صفحه، Home/End، aria-selected و tabindex با انتخاب عوض می‌شوند — جهش: جهت ثابت می‌افتد',
	$key_ok( $js36 ) && ! $key_ok( str_replace( "'rtl' === window.getComputedStyle( list ).direction", 'true', $js36 ) ) );

unset( $GLOBALS['tp_meta'][9601], $GLOBALS['tp_meta'][9602] );
