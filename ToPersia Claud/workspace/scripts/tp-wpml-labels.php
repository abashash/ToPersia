<?php
/**
 * برچسب‌های JetEngine در WPML — فارسی ← انگلیسی · عربی.  ۴٫۴٫۳ — فقط روی topersia.local.
 *
 *   php scripts/tp-wpml-labels.php --report   شمار رشته و ترجمۀ هر بافت
 *   php scripts/tp-wpml-labels.php --apply    زبان منبع ← fa، سپس ترجمۀ en و ar از جدول‌ها
 *
 * JetEngine برچسب نوع پست، فیلد متا و رابطه را در WPML String Translation ثبت
 * می‌کند، ولی با زبان منبع en در حالی که متن فارسی است — پس افزودن ترجمۀ
 * انگلیسی ممکن نبود. این اسکریپت زبان منبعِ رشتۀ فارسی را fa می‌کند و ترجمه
 * را فقط از جدول‌های بازبینی‌شده می‌گذارد، نه ترجمۀ ماشینی:
 *   scripts/i18n/jetengine-cpt.php       نام نوع‌های پست
 *   scripts/i18n/topersia-render.php     همان جدول کاتالوگ هستۀ نمایش (برچسب فیلد،
 *                                        واژه‌نامه، نام رابطه) — هر رشتۀ هم‌متن
 * رشتۀ بی ترجمه در جدول دست نمی‌خورد و در گزارش می‌آید. ترجمۀ موجود بازنویسی
 * نمی‌شود. تکرار بی‌خطر است.
 *
 * کد خروج: ۰ سالم · ۲ محیط ناقص
 */

require_once dirname( __DIR__ ) . '/tests/env.php';

$_SERVER['HTTP_HOST'] = TP_HOST; $_SERVER['SERVER_NAME'] = TP_HOST; $_SERVER['REQUEST_URI'] = '/';

$mode = isset( $argv[1] ) ? $argv[1] : '';
if ( ! in_array( $mode, array( '--report', '--apply' ), true ) ) { fwrite( STDERR, "--report یا --apply\n" ); exit( 2 ); }

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || 'topersia_local' !== DB_NAME || ! function_exists( 'icl_add_string_translation' ) ) {
	fwrite( STDERR, "فقط نسخۀ محلی با WPML String Translation\n" );
	exit( 2 );
}

global $wpdb;
$CTX   = array( 'Jet Engine CPT Labels', 'Jet Engine Admin Labels', 'Jet Engine Relations Labels' );
$in    = "'" . implode( "','", array_map( 'esc_sql', $CTX ) ) . "'";
$heh   = function ( $s ) { return str_replace( "\u{0647}\u{0654}", "\u{06C0}", trim( (string) $s ) ); };
$arabic = function ( $s ) { return strtr( (string) $s, array( "\u{06CC}" => "\u{064A}", "\u{06A9}" => "\u{0643}" ) ); };

$table = array();
foreach ( (array) include __DIR__ . '/i18n/topersia-render.php' as $fa => $tr ) {
	if ( is_array( $tr ) && isset( $tr['en'], $tr['ar'] ) && is_string( $tr['en'] ) && is_string( $tr['ar'] ) ) {
		$table[ $heh( $fa ) ] = array( $tr['en'], $tr['ar'] );
	} elseif ( is_array( $tr ) && isset( $tr[0], $tr[1] ) && is_string( $tr[0] ) ) {
		$table[ $heh( $fa ) ] = array( $tr[0], $tr[1] );
	}
}
foreach ( (array) include __DIR__ . '/i18n/jetengine-cpt.php' as $fa => $tr ) {
	$table[ $heh( $fa ) ] = $tr;
}

$report = function ( $title ) use ( $wpdb, $in ) {
	echo "\n{$title}\n";
	foreach ( $wpdb->get_results( "SELECT s.context, s.language, COUNT(*) n,
		SUM( EXISTS( SELECT 1 FROM {$wpdb->prefix}icl_string_translations t WHERE t.string_id = s.id AND t.language = 'en' AND t.status = 10 ) ) en,
		SUM( EXISTS( SELECT 1 FROM {$wpdb->prefix}icl_string_translations t WHERE t.string_id = s.id AND t.language = 'ar' AND t.status = 10 ) ) ar
		FROM {$wpdb->prefix}icl_strings s WHERE s.context IN ( {$in} ) GROUP BY s.context, s.language" ) as $r ) {
		printf( "  %-30s منبع %-3s %5d رشته · en %5d · ar %5d\n", $r->context, $r->language, $r->n, $r->en, $r->ar );
	}
};

$report( 'پیش' );
if ( '--report' === $mode ) { exit( 0 ); }

/* ۱ زبان منبعِ رشتۀ فارسی ← fa */
$fixed = $wpdb->query( "UPDATE {$wpdb->prefix}icl_strings SET language = 'fa' WHERE context IN ( {$in} ) AND language <> 'fa' AND value REGEXP '[ا-ی]'" );

/* ۲ ترجمه از جدول‌ها — موجود دست نمی‌خورد */
$added = 0; $missing = 0;
foreach ( $wpdb->get_results( "SELECT id, value FROM {$wpdb->prefix}icl_strings WHERE context IN ( {$in} ) AND language = 'fa'" ) as $row ) {
	$key = $heh( $row->value );
	if ( ! isset( $table[ $key ] ) ) { $missing ++; continue; }
	foreach ( array( 'en' => $table[ $key ][0], 'ar' => $arabic( $table[ $key ][1] ) ) as $lang => $text ) {
		$has = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}icl_string_translations WHERE string_id = %d AND language = %s AND status = 10", $row->id, $lang ) );
		if ( ! $has && '' !== trim( $text ) ) {
			icl_add_string_translation( (int) $row->id, $lang, $text, 10 );
			$added ++;
		}
	}
}
if ( function_exists( 'icl_cache_clear' ) ) { icl_cache_clear(); }
wp_cache_flush();

echo "\n  زبان منبع ← fa: {$fixed} · ترجمۀ تازه: {$added} · بی مدخل در جدول: {$missing}\n";
$report( 'پس' );

/* نمونه — از همان مسیرِ ترجمۀ WPML */
$probe = apply_filters( 'wpml_translate_single_string', 'G3 — استان', 'Jet Engine CPT Labels', 'Jet Engine CPT Name (G3 — استان)', 'en' );
echo "\n  نمونه: «G3 — استان» ← en «{$probe}»\n";
