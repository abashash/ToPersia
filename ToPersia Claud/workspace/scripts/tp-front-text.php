<?php
/**
 * جملۀ معرفی صفحۀ اول از متن تأییدشده — دستۀ 51 بخش ۲ (P5-D554).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-front-text.php     (TP_DRY=1 فقط گزارش)
 *
 * فقط محلی. منبع یکتا: بخش «۴. صفحهٔ اول» در `docs/legal/PAGES-TEXT-2026-11-04.md` (عنوان · جمله، هر سه زبان).
 * جمله در `post_excerpt` (سربرگ صفحۀ اول و description اسکیمای WebSite از همان می‌خوانند) و در
 * `rank_math_description` (توضیح متا و og/twitter) صفحۀ page_on_front و ترجمه‌های WPML آن نوشته می‌شود.
 * عنوان دست نمی‌خورد. ایدمپوتنت.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

$dry = (bool) getenv( 'TP_DRY' );
$txt = str_replace( "\r\n", "\n", (string) file_get_contents( dirname( __DIR__ ) . '/docs/legal/PAGES-TEXT-2026-11-04.md' ) );
$sec = preg_match( '/^## ۴\. صفحهٔ اول[^\n]*\n(.*?)(?=^## |\z)/msu', $txt, $m ) ? $m[1] : '';

$want = array();
foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
	if ( preg_match( '/^\*\*' . $l . '\*\* — .*· (?:جمله: )?«([^»]+)»\s*$/mu', $sec, $mm ) ) {
		$want[ $l ] = trim( $mm[1] );
	}
}
if ( 3 !== count( $want ) ) { echo "بخش ۴ متن کامل نیست\n"; exit( 1 ); }

$fa   = (int) get_option( 'page_on_front' );
$trid = $fa ? apply_filters( 'wpml_element_trid', null, $fa, 'post_page' ) : null;
$tr   = $trid ? (array) apply_filters( 'wpml_get_element_translations', null, $trid, 'post_page' ) : array();
if ( ! $fa ) { echo "page_on_front نیست\n"; exit( 1 ); }

foreach ( $want as $l => $line ) {
	$id = 'fa' === $l ? $fa : ( isset( $tr[ $l ] ) ? (int) $tr[ $l ]->element_id : 0 );
	if ( ! $id ) { echo "$l: ترجمۀ صفحۀ اول نیست\n"; continue; }
	$old = (string) get_post_field( 'post_excerpt', $id );
	printf( "%s #%d  %s\n", $l, $id, $old === $line && get_post_meta( $id, 'rank_math_description', true ) === $line ? 'بی تغییر' : ( $dry ? 'تغییر می‌کند' : 'نوشته شد' ) );
	if ( $dry ) { continue; }
	wp_update_post( wp_slash( array( 'ID' => $id, 'post_excerpt' => $line ) ) );
	update_post_meta( $id, 'rank_math_description', $line );
	clean_post_cache( $id );
}
