<?php
/**
 * موضوع‌های خبر — P5-D534 · دستۀ 49 (SPRINT-4.4 فاز ۶٫۱). فقط topersia.local.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-news-topics.php [report|apply]
 *
 * ۱. پیکربندی WPML از نو (همان کاری که بازکردن «افزونه‌ها» در پیشخوان می‌کند) تا
 *    `tpc_news_topic` در wpml-config.xml افزونۀ render «Translate» شود.
 * ۲. هشت موضوع فارسی با ترجمۀ انگلیسی و عربی در یک trid — فقط اگر نیست (دوباره‌اجراپذیر).
 * ۳. برچسب چهار خبر فارسی — فقط از متن واقعی هر خبر؛ جدول پایین با دلیل.
 * ۴. `_tp_read_minutes` هر خبر از متن خودش (همان تابع ذخیره).
 *
 * پیش از apply: `php scripts/tp-backup.php`. چیزی پاک نمی‌شود؛ برچسب موجود جایگزین نمی‌شود.
 */

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) ) {
	echo "فقط topersia.local\n";
	exit( 2 );
}

$apply = in_array( 'apply', (array) ( $args ?? array() ), true );
$tax   = 'tpc_news_topic';
$el    = 'tax_' . $tax;

/* کلید ← fa · en · ar (عربی بی حرف ویژۀ فارسی). ترتیب = ترتیب تراشه‌ها (شناسۀ term). */
$topics = array(
	'destinations'       => array( 'مقاصد', 'Destinations', 'الوجهات' ),
	'attractions'        => array( 'جاذبه‌ها', 'Attractions', 'المعالم' ),
	'history-culture'    => array( 'تاریخ و فرهنگ', 'History and culture', 'التاريخ والثقافة' ),
	'stay'               => array( 'اقامت', 'Accommodation', 'الإقامة' ),
	'food'               => array( 'غذا', 'Food', 'الطعام' ),
	'events'             => array( 'رویدادها', 'Events', 'الفعاليات' ),
	'travel-tips'        => array( 'نکات سفر', 'Travel tips', 'نصائح السفر' ),
	'sustainable-travel' => array( 'سفر پایدار', 'Sustainable travel', 'السفر المستدام' ),
);

/* برچسب از متن واقعی هر خبر (خلاصه و متن در گزارش دستۀ 49). */
$assign = array(
	1973 => array( 'travel-tips', 'ازسرگیری پرواز مستقیم اصفهان–استانبول: راه رسیدن مسافر، نه رویداد' ),
	1974 => array( 'history-culture', 'دیجیتال‌سازی اسناد میراث فرهنگی استان' ),
	1975 => array( 'events', 'برگزاری جشنوارۀ فیلم کودکان و نوجوانان در اصفهان' ),
	1976 => array( 'events', 'برگزاری نشست جشنوارۀ جهانی فیلم گردشگری در اصفهان' ),
);

echo ( $apply ? 'APPLY' : 'REPORT' ) . "\n";

if ( ! taxonomy_exists( $tax ) ) {
	echo "  ✗ taxonomy ثبت نشده (render همگام نیست یا دروازۀ محلی خاموش)\n";
	exit( 1 );
}

/* ۱ — WPML */
if ( $apply && class_exists( 'WPML_Config' ) ) {
	WPML_Config::load_config_run();
}
$translated = (bool) apply_filters( 'wpml_is_translated_taxonomy', null, $tax );
echo '  WPML Translate: ' . ( $translated ? 'بله' : 'نه' ) . "\n";
if ( $apply && ! $translated ) {
	echo "  ✗ WPML آن را ترجمه‌پذیر نمی‌شناسد — توقف\n";
	exit( 1 );
}

/* ۲ — موضوع‌ها */
$find = function ( $slug, $lang ) use ( $tax ) {
	do_action( 'wpml_switch_language', $lang );
	$t = get_term_by( 'slug', $slug, $tax );
	do_action( 'wpml_switch_language', 'fa' );
	return ( $t && ! is_wp_error( $t ) ) ? $t : null;
};
$made = 0;
foreach ( $topics as $key => $names ) {
	$fa = $find( $key, 'fa' );
	if ( ! $fa && $apply ) {
		do_action( 'wpml_switch_language', 'fa' );
		$r  = wp_insert_term( $names[0], $tax, array( 'slug' => $key ) );
		$fa = is_wp_error( $r ) ? null : get_term( $r['term_id'], $tax );
		$made++;
	}
	if ( ! $fa ) {
		echo "  · $key: نیست\n";
		continue;
	}
	$trid = apply_filters( 'wpml_element_trid', null, $fa->term_taxonomy_id, $el );
	$line = "  $key: fa #{$fa->term_id}";
	foreach ( array( 'en' => 1, 'ar' => 2 ) as $lang => $i ) {
		$slug = $key . '-' . $lang;
		$tr   = $find( $slug, $lang );
		if ( ! $tr && $apply && $trid ) {
			do_action( 'wpml_switch_language', $lang );
			$r = wp_insert_term( $names[ $i ], $tax, array( 'slug' => $slug ) );
			do_action( 'wpml_switch_language', 'fa' );
			if ( ! is_wp_error( $r ) ) {
				$tr = get_term( $r['term_id'], $tax );
				do_action( 'wpml_set_element_language_details', array(
					'element_id'           => $tr->term_taxonomy_id,
					'element_type'         => $el,
					'trid'                 => $trid,
					'language_code'        => $lang,
					'source_language_code' => 'fa',
				) );
				$made++;
			}
		}
		$line .= $tr ? " · $lang #{$tr->term_id} «{$tr->name}»" : " · $lang نیست";
	}
	echo $line . "\n";
}

/* ۳ — برچسب خبرها */
foreach ( $assign as $id => $row ) {
	$now = wp_get_post_terms( $id, $tax, array( 'fields' => 'slugs' ) );
	$lng = apply_filters( 'wpml_post_language_details', null, $id );
	if ( 'tpc_news' !== get_post_type( $id ) || ! is_array( $lng ) || 'fa' !== $lng['language_code'] ) {
		echo "  ✗ #$id خبر فارسی نیست — رد شد\n";
		continue;
	}
	if ( ! is_wp_error( $now ) && $now ) {
		echo "  #$id: از پیش " . implode( ',', $now ) . " (دست نخورد)\n";
		continue;
	}
	if ( $apply ) {
		$t = $find( $row[0], 'fa' );
		if ( $t ) {
			wp_set_object_terms( $id, array( (int) $t->term_id ), $tax, false );
			$made++;
		}
	}
	echo "  #$id ← {$row[0]} — {$row[1]}\n";
}

/* ۴ — زمان مطالعه */
foreach ( get_posts( array( 'post_type' => 'tpc_news', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'suppress_filters' => true ) ) as $id ) {
	$m = topersia_g_news_read_minutes_calc( $id );
	if ( $apply && (string) $m !== (string) get_post_meta( $id, '_tp_read_minutes', true ) ) {
		update_post_meta( $id, '_tp_read_minutes', $m );
		$made++;
	}
	echo "  #$id زمان مطالعه: $m دقیقه\n";
}

echo "  نوشته: $made\n";
