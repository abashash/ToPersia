<?php
/**
 * ادعاهای دستهٔ ۴ (۱۳ اکتبر) — هم‌ارزی زبان  P5-D349
 * از assert.php پس از assert-2k.php بار می‌شود؛ PHP خالص، هر کدام با جهش. رفتار روی وردپرس واقعی: tests/lang-equiv-local.php.
 */

$gaps2l  = file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-i18n-gaps.php' );
$rr2l    = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-relreg.php' );
$sch2l   = file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-schema/topersia-schema.php' );
$test2l  = file_get_contents( __DIR__ . '/lang-equiv-local.php' );

echo "\nدستهٔ ۴ (۱۳ اکتبر) — هم‌ارزی زبان: هر پست منتشرشدهٔ فارسی در همهٔ زبان‌های فعال ترجمهٔ منتشرشده  P5-D349\n" . str_repeat( '─', 78 ) . "\n";

tp441_mut( 'قاعدهٔ دائمی P5-D349: topersia_i18n_equivalence ترجمهٔ بی‌پست (missing) و منتشرنشده (unpublished: پیش‌نویس، در انتظار، خصوصی) را جدا می‌شمارد؛ فقط خواندن؛ CLI `wp topersia i18n-equiv`؛ زباله بی‌ترجمه است',
	function ( $x ) {
		return false !== strpos( $x, 'function topersia_i18n_equivalence( $lang = \'\', $type = \'\' )' ) && false !== strpos( $x, "\$row['missing'][] = \$id;" )
			&& false !== strpos( $x, "'publish' !== get_post_status( \$tr )" ) && false !== strpos( $x, "\$row['unpublished'][] = \$id;" ) && false !== strpos( $x, "'trash' === get_post_status( \$tr )" )
			&& false !== strpos( $x, "WP_CLI::add_command( 'topersia i18n-equiv'" ) && false !== strpos( $x, 'function topersia_i18n_equivalence_counts( $eq )' );
	},
	$gaps2l, str_replace( "'publish' !== get_post_status( \$tr )", "'x' === get_post_status( \$tr )", $gaps2l ) );

tp441_mut( 'زنجیرهٔ مکانی در /en/ و /ar/: والد R01 ترجمه‌ای که یال ندارد از پست زبان اصلی؛ پیمودن بی دروازه (شهرستانِ بی ترجمه راه را نمی‌بندد) و والد صفحه‌دار فقط با نسخهٔ منتشرشدهٔ زبان جاری',
	function ( $x ) {
		return false !== strpos( $x, '$found = topersia_g_rel_ids( $pid, $spec, false );' ) && false !== strpos( $x, 'topersia_g_default_id( $pid ) !== $pid' )
			&& false !== strpos( $x, '$found = topersia_g_rel_ids( topersia_g_default_id( $pid ), $spec, false );' )
			&& false !== strpos( $x, "if ( function_exists( 'topersia_g_published' ) && ! topersia_g_published( array( \$parent ) ) ) {" );
	},
	$rr2l, str_replace( 'topersia_g_default_id( $pid ) !== $pid', 'false', $rr2l ) );

tp441_mut( 'containedInPlace شِمای ترجمه: اگر ترجمه یال رابطه ندارد والد از پست زبان اصلی (topersia_schema_source_id)',
	function ( $x ) {
		return false !== strpos( $x, '$origin = topersia_schema_source_id( $current );' ) && false !== strpos( $x, "\$parents = \$relation->get_parents( \$origin, 'ids' );" );
	},
	$sch2l, str_replace( "\$parents = \$relation->get_parents( \$origin, 'ids' );", '', $sch2l ) );

tp441_mut( 'آزمون محلی هم‌ارزی: ایران en و ar منتشرشده با needs_verification؛ بدتر نشدن از خط پایه؛ مسیر راهنما و BreadcrumbList بی کشور؛ containedInPlace؛ خط پایه در مخزن',
	function ( $x ) {
		return false !== strpos( $x, "'needs_verification' === \$trs['en']['cs']" ) && false !== strpos( $x, 'lang-equiv-baseline.json' ) && false !== strpos( $x, "'BreadcrumbList'" ) && false !== strpos( $x, "'containedInPlace'" )
			&& is_file( dirname( __FILE__ ) . '/lang-equiv-baseline.json' );
	},
	$test2l, str_replace( "'needs_verification' === \$trs['en']['cs']", 'true', $test2l ) );
