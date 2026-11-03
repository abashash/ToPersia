<?php
/**
 * P5-D504: جدول عنوان هر رابطه از هر سو، سه‌زبانه. جز پروندهٔ TSV چیزی نمی‌نویسد.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-rel-headings.php [check]
 *
 * بی آرگومان: docs/ops/REL-HEADINGS-2026-11.tsv را می‌نویسد. check: فقط می‌سنجد؛ تفاوت پرونده با کد یا شکاف عنوان ← کد خروج ۱.
 * دو ستون شمار یال عکس لحظهٔ نوشتن‌اند و در check سنجیده نمی‌شوند (tp_rel_heading_shape)؛ اگر فقط آن‌ها عوض شده باشند، یادداشت چاپ می‌شود.
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

require_once __DIR__ . '/lib/tp-rel-coverage-lib.php';

$cat  = include __DIR__ . '/i18n/topersia-render.php';
$rows = tp_rel_heading_rows( $cat );
$gaps = tp_rel_heading_gaps( $rows );
$file = dirname( __DIR__ ) . '/docs/ops/REL-HEADINGS-2026-11.tsv';
$tsv  = tp_rel_heading_tsv( $rows );

printf( "ردیف: %d · گروه «مرتبط»: %d · شکاف عنوان: %d\n", count( $rows ), count( array_filter( $rows, function ( $r ) { return 'related' === $r['group']; } ) ), count( $gaps ) );

if ( in_array( 'check', isset( $args ) ? (array) $args : array(), true ) ) {
	$have = is_readable( $file ) ? (string) file_get_contents( $file ) : null;
	$same = null !== $have && tp_rel_heading_shape( $have ) === tp_rel_heading_shape( $tsv );
	echo $same ? "پرونده با کد یکی است\n" : "پرونده با کد فرق دارد\n";
	if ( $same && $have !== $tsv ) { echo "یادداشت: فقط شمار یال‌ها از زمان نوشتن پرونده عوض شده؛ سنجیده نمی‌شود\n"; }
	exit( $same && ! $gaps ? 0 : 1 );
}

if ( false === file_put_contents( $file, $tsv ) ) { echo "نوشته نشد\n"; exit( 1 ); }
echo "نوشته شد: docs/ops/REL-HEADINGS-2026-11.tsv\n";
exit( $gaps ? 1 : 0 );
