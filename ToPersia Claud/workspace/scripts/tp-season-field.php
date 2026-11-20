<?php
/**
 * دستۀ 55-ب بند ۲ (P5-D644) — فیلد «بهترین فصل» در پیکربندی JetEngine: گزینۀ خالی «—»، غیراجباری، پیش‌فرض خالی. فقط topersia.local.
 *
 *   php wp-cli.phar … eval-file scripts/tp-season-field.php                  ← گزارش (فقط خواندن): تعریف best_season هر متاباکس
 *                                                                              + فهرست همۀ select/radio تک‌انتخابی اجباری بی گزینۀ خالی
 *   php wp-cli.phar … eval-file scripts/tp-season-field.php check             ← همان گزارش؛ کد ۱ اگر یک best_season بی هدف P5-D644 است (گیت)
 *   php wp-cli.phar … eval-file scripts/tp-season-field.php apply <out.json>  ← نخست تعریف پیشین هر best_season در out.json، سپس نوشتن
 *   php wp-cli.phar … eval-file scripts/tp-season-field.php restore <in.json> ← برگرداندن عیناً همان تعریف‌ها
 *
 * چرا: select تک‌انتخابی JetEngine بی گزینۀ خالی در فرم ویرایش نخستین گزینه (بهار) را برگزیده نشان می‌دهد و «به‌روزرسانی»
 * همان را می‌نویسد (دستۀ 55 بند ۸ — ۵۲ ردیف بهار ساختگی). JetEngine 3.8 برای select تک‌انتخابی با `placeholder` گزینۀ
 * `'' => placeholder` را نخست می‌گذارد (components/meta-boxes/post.php، prepare_select_options)؛ چندانتخابی (کشور) گزینۀ
 * خالی نمی‌خواهد — بی انتخاب همان تهی است — و فقط غیراجباری می‌شود.
 * تعریف‌ها در گزینۀ `jet_engine_meta_boxes` (متاباکس‌های JetEngine) است؛ جدول jet_post_types هیچ best_season ندارد (گزارش می‌سنجد).
 * فقط ردیف‌های best_season دست می‌خورند؛ بقیۀ گزینه عیناً. خروجی: ۰ سالم · ۱ ناسازگاری · ۲ محیط/ورودی.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "فقط با wp eval-file\n" );
	exit( 2 );
}
if ( 'topersia.local' !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	WP_CLI::error( 'فقط topersia.local' );
}

$tp_args = isset( $args ) ? (array) $args : array();
$tp_mode = isset( $tp_args[0] ) ? (string) $tp_args[0] : 'report';
$tp_file = isset( $tp_args[1] ) ? (string) $tp_args[1] : '';

const TP_SF_OPTION      = 'jet_engine_meta_boxes';
const TP_SF_PLACEHOLDER = '—';

/** آیا فیلد چندانتخابی است (همان قاعدۀ JetEngine: is_multiple با FILTER_VALIDATE_BOOLEAN). */
function tp_sf_multi( array $f ) {
	return filter_var( $f['is_multiple'] ?? false, FILTER_VALIDATE_BOOLEAN );
}

/** آیا فیلد اجباری است. */
function tp_sf_required( array $f ) {
	return filter_var( $f['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN );
}

/** آیا تعریف همان هدف P5-D644 است: غیراجباری، پیش‌فرض خالی، و (تک‌انتخابی) گزینۀ خالی «—». */
function tp_sf_ok( array $f ) {
	if ( tp_sf_required( $f ) || '' !== (string) ( $f['default_val'] ?? '' ) ) {
		return false;
	}
	return tp_sf_multi( $f ) || TP_SF_PLACEHOLDER === (string) ( $f['placeholder'] ?? '' );
}

/** [ کلید متاباکس => [ نمایه => تعریف ] ] برای همۀ best_season. */
function tp_sf_season_defs( array $boxes ) {
	$out = array();
	foreach ( $boxes as $k => $b ) {
		foreach ( (array) ( $b['meta_fields'] ?? array() ) as $i => $f ) {
			if ( is_array( $f ) && 'best_season' === ( $f['name'] ?? '' ) ) {
				$out[ $k ][ $i ] = $f;
			}
		}
	}
	return $out;
}

/** برچسب نوع پست‌های یک متاباکس. */
function tp_sf_types( array $b ) {
	return implode( ',', (array) ( $b['args']['allowed_post_type'] ?? array() ) );
}

/**
 * همۀ select/radio تک‌انتخابی اجباری بی گزینۀ خالی — در متاباکس‌ها و در meta_fields جدول jet_post_types.
 * radio گزینۀ خالی ندارد مگر خودِ گزینه‌ها یکی با مقدار '' داشته باشند.
 *
 * @return array [ [ منبع ، نوع پست ، نام فیلد ، عنوان ، نوع فیلد ] … ]
 */
function tp_sf_audit( array $boxes ) {
	global $wpdb;
	$sets = array();
	foreach ( $boxes as $k => $b ) {
		$sets[] = array( 'meta_box:' . $k, tp_sf_types( (array) $b ), (array) ( $b['meta_fields'] ?? array() ) );
	}
	$t = $wpdb->prefix . 'jet_post_types';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t ) ) ) {
		foreach ( (array) $wpdb->get_results( "SELECT id, slug, meta_fields FROM {$t}", ARRAY_A ) as $r ) { // phpcs:ignore WordPress.DB.PreparedSQL
			$m = maybe_unserialize( $r['meta_fields'] );
			if ( is_array( $m ) ) {
				$sets[] = array( 'jet_post_types:' . $r['id'], (string) $r['slug'], $m );
			}
		}
	}
	$rows = array();
	$walk = function ( array $fields, $src, $types ) use ( &$walk, &$rows ) {
		foreach ( $fields as $f ) {
			if ( ! is_array( $f ) ) {
				continue;
			}
			if ( ! empty( $f['repeater-fields'] ) && is_array( $f['repeater-fields'] ) ) {
				$walk( $f['repeater-fields'], $src . '/' . ( $f['name'] ?? '?' ), $types );
			}
			$type = (string) ( $f['type'] ?? '' );
			if ( ! in_array( $type, array( 'select', 'radio' ), true ) || ! tp_sf_required( $f ) ) {
				continue;
			}
			if ( 'select' === $type && ( tp_sf_multi( $f ) || '' !== (string) ( $f['placeholder'] ?? '' ) ) ) {
				continue;
			}
			$empty = false;
			foreach ( (array) ( $f['options'] ?? array() ) as $o ) {
				if ( is_array( $o ) && '' === (string) ( $o['key'] ?? 'x' ) ) {
					$empty = true;
				}
			}
			if ( ! $empty ) {
				$rows[] = array( $src, $types, (string) ( $f['name'] ?? '' ), (string) ( $f['title'] ?? '' ), $type . ( 'glossary' === ( $f['options_source'] ?? '' ) ? ' (glossary ' . ( $f['glossary_id'] ?? '?' ) . ')' : '' ) );
			}
		}
	};
	foreach ( $sets as $s ) {
		$walk( $s[2], $s[0], $s[1] );
	}
	return $rows;
}

$boxes = get_option( TP_SF_OPTION );
if ( ! is_array( $boxes ) || ! $boxes ) {
	WP_CLI::error( 'گزینۀ ' . TP_SF_OPTION . ' نیست' );
}
$defs = tp_sf_season_defs( $boxes );
if ( ! $defs ) {
	WP_CLI::error( 'هیچ فیلد best_season در متاباکس‌ها نیست' );
}

if ( 'report' === $tp_mode || 'check' === $tp_mode ) {
	$bad = 0;
	foreach ( $defs as $k => $set ) {
		foreach ( $set as $i => $f ) {
			$ok   = tp_sf_ok( $f );
			$bad += $ok ? 0 : 1;
			printf( "%s %-8s %-22s اجباری=%s چند=%s خالی=«%s» پیش‌فرض=«%s»\n", $ok ? 'OK ' : '-- ', $k, tp_sf_types( (array) $boxes[ $k ] ), tp_sf_required( $f ) ? 'بله' : 'نه', tp_sf_multi( $f ) ? 'بله' : 'نه', (string) ( $f['placeholder'] ?? '' ), (string) ( $f['default_val'] ?? '' ) );
		}
	}
	echo "\nselect/radio تک‌انتخابی اجباری بی گزینۀ خالی (بی best_season — بی تغییر):\n";
	$aud = array_values( array_filter( tp_sf_audit( $boxes ), function ( $r ) { return 'best_season' !== $r[2]; } ) );
	foreach ( $aud as $r ) {
		printf( "  %s\t%s\t%s\t%s\t%s\n", $r[0], $r[1], $r[2], $r[3], $r[4] );
	}
	printf( "\nbest_season: %d تعریف، %d هنوز بی هدف P5-D644 · دیگر فیلدهای در معرض: %d\n", array_sum( array_map( 'count', $defs ) ), $bad, count( $aud ) );
	/* check: گیت زنجیره — هر best_season بی هدف P5-D644 قرمز است؛ report فقط گزارش می‌دهد */
	exit( 'check' === $tp_mode && $bad ? 1 : 0 );
}

if ( 'apply' === $tp_mode ) {
	if ( '' === $tp_file || file_exists( $tp_file ) ) {
		WP_CLI::error( 'apply <out.json> — پرونده نباید از پیش باشد', false );
		exit( 2 );
	}
	$dump = array(
		'what'        => 'P5-D644 — تعریف پیشین best_season در ' . TP_SF_OPTION,
		'taken'       => gmdate( 'c' ),
		'sha1_option' => sha1( maybe_serialize( $boxes ) ),
		'defs'        => $defs,
	);
	if ( false === file_put_contents( $tp_file, wp_json_encode( $dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" ) ) {
		WP_CLI::error( 'نوشتن پشتیبان نشد — هیچ چیز تغییر نکرد', false );
		exit( 2 );
	}
	$back = json_decode( (string) file_get_contents( $tp_file ), true );
	if ( ! is_array( $back ) || $back['defs'] != json_decode( wp_json_encode( $defs ), true ) ) { // phpcs:ignore Universal.Operators.StrictComparisons
		WP_CLI::error( 'پشتیبان با تعریف‌ها یکی نیست — هیچ چیز تغییر نکرد', false );
		exit( 2 );
	}
	foreach ( $defs as $k => $set ) {
		foreach ( $set as $i => $f ) {
			$f['is_required'] = false;
			$f['default_val'] = '';
			if ( ! tp_sf_multi( $f ) ) {
				$f['placeholder'] = TP_SF_PLACEHOLDER;
			}
			$boxes[ $k ]['meta_fields'][ $i ] = $f;
		}
	}
	update_option( TP_SF_OPTION, $boxes );
	wp_cache_delete( TP_SF_OPTION, 'options' );
	$after = tp_sf_season_defs( (array) get_option( TP_SF_OPTION ) );
	$bad   = 0;
	foreach ( $after as $set ) {
		foreach ( $set as $f ) {
			$bad += tp_sf_ok( $f ) ? 0 : 1;
		}
	}
	printf( "نوشته شد: %d تعریف · ناسازگار پس از نوشتن: %d · پشتیبان %s\n", array_sum( array_map( 'count', $after ) ), $bad, $tp_file );
	exit( $bad ? 1 : 0 );
}

if ( 'restore' === $tp_mode ) {
	$back = '' !== $tp_file && is_file( $tp_file ) ? json_decode( (string) file_get_contents( $tp_file ), true ) : null;
	if ( ! is_array( $back ) || empty( $back['defs'] ) || ! is_array( $back['defs'] ) ) {
		WP_CLI::error( 'restore <in.json> — پشتیبان نامعتبر', false );
		exit( 2 );
	}
	$miss = 0;
	foreach ( $back['defs'] as $k => $set ) {
		foreach ( (array) $set as $i => $f ) {
			if ( ! isset( $boxes[ $k ]['meta_fields'][ $i ] ) || 'best_season' !== ( $boxes[ $k ]['meta_fields'][ $i ]['name'] ?? '' ) ) {
				WP_CLI::warning( "جای {$k}#{$i} دیگر best_season نیست — دست نخورد" );
				++$miss;
				continue;
			}
			$boxes[ $k ]['meta_fields'][ $i ] = $f;
		}
	}
	update_option( TP_SF_OPTION, $boxes );
	wp_cache_delete( TP_SF_OPTION, 'options' );
	$now  = json_decode( wp_json_encode( tp_sf_season_defs( (array) get_option( TP_SF_OPTION ) ) ), true );
	$same = $now == $back['defs']; // phpcs:ignore Universal.Operators.StrictComparisons
	printf( "برگردانده شد: %s · جاافتاده %d\n", $same ? 'عیناً' : 'ناقص', $miss );
	exit( $same && ! $miss ? 0 : 1 );
}

WP_CLI::error( 'حالت: report | apply <out.json> | restore <in.json>', false );
exit( 2 );
