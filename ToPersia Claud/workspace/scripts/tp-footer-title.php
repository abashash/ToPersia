<?php
/**
 * عنوان ستون پنجم پاورقی #6128 — دستهٔ 55-د بند ۶ · P5-D649. فقط topersia.local.
 *
 *   wp eval-file scripts/tp-footer-title.php dump    <رونوشت.json>   رونوشت خام _elementor_data (فایل نباید باشد)
 *   wp eval-file scripts/tp-footer-title.php apply   <رونوشت.json>   فقط اگر دادهٔ کنونی عیناً همان رونوشت است
 *   wp eval-file scripts/tp-footer-title.php restore <رونوشت.json>   رونوشت را در _elementor_data برمی‌گرداند
 *   wp eval-file scripts/tp-footer-title.php check
 *
 * toggle ستون «season» (شورت‌کد [tp_footer_menu col="season"]) از «ایران در چهار فصل» ← «کاوش در ایران». عنوان نمایشی به زبان صفحه
 * از کاتالوگ می‌آید (topersia_g_ft_toggle_titles)؛ داده فارسی می‌ماند. پهنا و ساختار ستون‌ها دست نمی‌خورد.
 */
if ( 'topersia.local' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) { WP_CLI::error( 'فقط topersia.local' ); }
const TP_FT_OLD = 'ایران در چهار فصل';
const TP_FT_NEW = 'کاوش در ایران';
$mode = (string) ( $args[0] ?? 'check' );
$file = (string) ( $args[1] ?? '' );
$raw  = (string) get_post_meta( 6128, '_elementor_data', true );
$d    = json_decode( $raw, true );
if ( ! is_array( $d ) ) { WP_CLI::error( 'دادهٔ #6128 خوانا نیست' ); }
/* toggleهایی که شورت‌کد col="season" دارند: [ مسیر ] */
$find = function ( array $els, $path = '' ) use ( &$find ) {
	$o = array();
	foreach ( $els as $i => $e ) {
		if ( 'wd_toggle' === ( $e['widgetType'] ?? '' ) && false !== strpos( wp_json_encode( $e['elements'] ?? array() ), 'col=\"season\"' ) ) { $o[] = $path . '/' . $i; }
		$o = array_merge( $o, $find( $e['elements'] ?? array(), $path . '/' . $i ) );
	}
	return $o;
};
$ref = function &( array &$root, $path ) {
	$idx = array_values( array_filter( explode( '/', $path ), 'strlen' ) );
	$cur = &$root[ $idx[0] ];
	foreach ( array_slice( $idx, 1 ) as $i ) { $cur = &$cur['elements'][ $i ]; }
	return $cur;
};
$paths = $find( $d );
if ( 'check' === $mode ) {
	foreach ( $paths as $p ) { $e = &$ref( $d, $p ); echo $p . ' ' . ( $e['id'] ?? '' ) . ' «' . ( $e['settings']['element_title'] ?? '' ) . "»\n"; }
	exit( 0 );
}
if ( '' === $file ) { WP_CLI::error( 'مسیر رونوشت لازم است' ); }
if ( 'dump' === $mode ) {
	if ( file_exists( $file ) ) { WP_CLI::error( 'رونوشت از پیش هست' ); }
	file_put_contents( $file, $raw );
	WP_CLI::success( 'dump ' . strlen( $raw ) . ' بایت sha1 ' . sha1( $raw ) . ' فایل ' . sha1_file( $file ) );
	exit( 0 );
}
if ( 'apply' === $mode ) {
	if ( 1 !== count( $paths ) ) { WP_CLI::error( 'یک toggle ستون season انتظار می‌رفت: ' . count( $paths ) ); }
	if ( ! is_file( $file ) || file_get_contents( $file ) !== $raw ) { WP_CLI::error( 'رونوشت نیست یا با دادهٔ کنونی یکی نیست — دست نمی‌زنم' ); }
	if ( $raw !== wp_json_encode( $d ) ) { WP_CLI::error( 'رفت‌وبرگشت json بایت‌به‌بایت یکی نیست' ); }
	$e = &$ref( $d, $paths[0] );
	if ( TP_FT_OLD !== trim( (string) ( $e['settings']['element_title'] ?? '' ) ) ) { WP_CLI::error( 'عنوان کنونی «' . ( $e['settings']['element_title'] ?? '' ) . '» است، نه «' . TP_FT_OLD . '»' ); }
	$e['settings']['element_title'] = TP_FT_NEW;
	unset( $e );
	$out = wp_json_encode( $d );
	update_post_meta( 6128, '_elementor_data', wp_slash( $out ) );
	if ( (string) get_post_meta( 6128, '_elementor_data', true ) !== $out ) { WP_CLI::error( 'نوشته‌شده با خوانده‌شده یکی نیست' ); }
	delete_post_meta( 6128, '_elementor_css' );
	delete_post_meta( 6128, '_elementor_element_cache' );
	WP_CLI::success( 'عنوان ستون season ← «' . TP_FT_NEW . '» · بایت ' . strlen( $raw ) . ' ← ' . strlen( $out ) );
	exit( 0 );
}
if ( 'restore' === $mode ) {
	if ( ! is_file( $file ) || null === json_decode( (string) file_get_contents( $file ), true ) ) { WP_CLI::error( 'رونوشت نیست یا خراب است' ); }
	update_post_meta( 6128, '_elementor_data', wp_slash( (string) file_get_contents( $file ) ) );
	delete_post_meta( 6128, '_elementor_css' );
	delete_post_meta( 6128, '_elementor_element_cache' );
	WP_CLI::success( 'بازگردانده شد' );
	exit( 0 );
}
WP_CLI::error( 'حالت: dump | apply | restore | check' );
