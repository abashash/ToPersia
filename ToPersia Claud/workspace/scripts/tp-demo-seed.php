<?php
/**
 * دادۀ نمایشی برای حالت پیش‌نمایش — دستۀ 51 بخش ۱ (P5-D548). فقط محلی (topersia.local).
 *
 *   php scripts/tp-backup.php --no-prune                      ← پیش از اجرا (قاعدۀ دسته)
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-demo-seed.php          ← dry-run: چه ساخته می‌شود
 *   … eval-file scripts/tp-demo-seed.php confirm                                                    ← ساختن
 *
 * دیده می‌شود فقط با `define( 'TOPERSIA_DEMO_PREVIEW', true );` در wp-config محلی (topersia_demo_on())؛
 * بی آن، هر پست این‌جا مثل هر نمونه بیرون از صفحۀ عمومی است و صفحۀ تکی‌اش ۴۰۴.
 *
 * هر پست: `_tp_demo = 1` و `_tp_sample = 1` و `_tp_demo_key` (ایدمپوتنت: کلید موجود دوباره ساخته نمی‌شود)، noindex.
 *   • ۱۰ خبر `tpc_news` روی همۀ ۸ موضوع، تاریخ در ۶۰ روز گذشته؛ متن کوتاه بی ادعای دقیق؛ منبع «نمونه»، بی نشانی.
 *   • هر نوع عمومی (جز شهرستان که صفحه ندارد) با کمتر از ۸ پست واقعی: تا ۸ پست نمایشی همان نوع.
 *     رویدادها `event_start_date` آینده (میلادی Y-m-d، قالب فیلد date)؛ `best_season` گردان روی چهار فصل.
 *   • بی تصویر (کارت نقش‌مایۀ CSS خودش را دارد + نشان «نمونۀ نمایشی»)؛ بی رابطه با هیچ پست.
 *   • چند ترجمۀ en/ar (دو خبر و یک مورد از چند نوع) فقط برای دیدن ساختار.
 * پاک‌کردن: scripts/tp-demo-purge.php
 */
if ( 'local' !== wp_get_environment_type() || false === strpos( (string) home_url(), 'topersia.local' ) ) { echo "فقط topersia.local\n"; return; }

global $wpdb;
$confirm = 'confirm' === (string) ( $args[0] ?? '' );
$TARGET  = 8;

/* موضوع خبر از خودِ تاکسونومی (fa) */
$topics = get_terms( array( 'taxonomy' => 'tpc_news_topic', 'hide_empty' => false, 'lang' => 'fa', 'orderby' => 'term_id' ) );
$topics = is_array( $topics ) ? array_values( $topics ) : array();

$have = function ( $key ) use ( $wpdb ) {
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tp_demo_key' AND meta_value = %s LIMIT 1", $key ) );
};

$real_count = function ( $type ) use ( $wpdb ) {
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = CONCAT( 'post_', p.post_type ) AND t.language_code = 'fa'
		 WHERE p.post_type = %s AND p.post_status = 'publish' AND p.post_password = ''
		 AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = '_tp_sample' )", $type ) );
};

$fa_digits = function ( $n ) {
	return str_replace( range( 0, 9 ), array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ), (string) $n );
};

$made = array();

$make = function ( $key, $type, $lang, $title, $body, array $meta, $date = '', $trid_of = 0 ) use ( $have, $confirm, &$made ) {
	$old = $have( $key );
	if ( $old ) {
		return $old;
	}
	$made[] = $key;
	if ( ! $confirm ) {
		return 0;
	}
	$arr = array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_content' => $body, 'post_name' => 'demo-' . sanitize_title( $key ) );
	if ( '' !== $date ) {
		$arr['post_date']     = $date;
		$arr['post_date_gmt'] = get_gmt_from_date( $date );
	}
	$id = wp_insert_post( $arr, true );
	if ( ! $id || is_wp_error( $id ) ) {
		echo "خطا: $key\n";
		return 0;
	}
	$id   = (int) $id;
	$trid = $trid_of ? apply_filters( 'wpml_element_trid', null, $trid_of, 'post_' . $type ) : false;
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_' . $type, 'trid' => $trid, 'language_code' => $lang, 'source_language_code' => $trid_of ? 'fa' : null ) );
	update_post_meta( $id, '_tp_demo', '1' );
	update_post_meta( $id, '_tp_sample', '1' );
	update_post_meta( $id, '_tp_demo_key', $key );
	update_post_meta( $id, 'rank_math_robots', array( 'noindex', 'nofollow' ) );
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return $id;
};

$NOTE = array(
	'fa' => 'این یک نمونۀ نمایشی برای دیدن ساختار صفحه است و هیچ ادعای واقعی ندارد. پیش از انتشار سایت پاک می‌شود.',
	'en' => 'This is a demo sample to preview the page structure; it makes no factual claim and is removed before launch.',
	'ar' => 'هذا نموذج للعرض لمعاينة بنية الصفحة، ولا يتضمن أي معلومة حقيقية، ويُحذف قبل الإطلاق.',
);

/* ── ۱۰ خبر روی ۸ موضوع، ۶۰ روز گذشته ── */
$news_fa = array();
for ( $i = 1; $i <= 10; $i++ ) {
	$t     = $topics ? $topics[ ( $i - 1 ) % count( $topics ) ] : null;
	$date  = gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( 2 + ( $i - 1 ) * 6 ) . ' days 10:00' ) );
	$title = 'خبر نمونۀ ' . $fa_digits( $i ) . ( $t ? ' — ' . $t->name : '' );
	$id    = $make( 'news-' . $i, 'tpc_news', 'fa', $title, '<p>' . $NOTE['fa'] . '</p>', array(
		'short_summary'    => 'خلاصۀ کوتاه نمونه برای دیدن چیدمان کارت خبر.',
		'news_source_name' => 'نمونه',
	), $date );
	if ( $id && $t && $confirm ) {
		wp_set_object_terms( $id, array( (int) $t->term_id ), 'tpc_news_topic' );
	}
	$news_fa[ $i ] = $id;
}
/* ترجمۀ دو خبر — فقط ساختار */
foreach ( array( 1, 2 ) as $i ) {
	$src = $news_fa[ $i ];
	if ( ! $src ) {
		continue;
	}
	foreach ( array( 'en' => 'Sample news ', 'ar' => 'خبر نموذجي ' ) as $l => $p ) {
		$make( 'news-' . $i . '-' . $l, 'tpc_news', $l, $p . $i, '<p>' . $NOTE[ $l ] . '</p>', array( 'short_summary' => $NOTE[ $l ], 'news_source_name' => 'en' === $l ? 'Sample' : 'نموذج' ), (string) get_post_field( 'post_date', $src ), $src );
	}
}

/* ── هر نوع عمومی با کمتر از ۸ پست واقعی ── */
$SEASONS = array( 'spring', 'summer', 'autumn', 'winter' );
$EVENTS  = array( 'tp_festival', 'tp_sports_event', 'tp_religious_event', 'tp_seasonal_event' );
$types   = array_filter( get_post_types( array( 'public' => true ), 'objects' ), function ( $o ) {
	return 0 === strpos( $o->name, 'tp_' ) && 'tp_county' !== $o->name;
} );
ksort( $types );
$report = array();
foreach ( $types as $type => $obj ) {
	$real  = $real_count( $type );
	$need  = max( 0, $TARGET - $real );
	$label = (string) $obj->labels->singular_name;
	$report[] = sprintf( '%-22s real %3d  demo %d', $type, $real, $need );
	for ( $i = 1; $i <= $need; $i++ ) {
		$meta = array( 'best_season' => $SEASONS[ ( $i - 1 ) % 4 ], 'short_summary' => 'نمونۀ نمایشی ' . $label . ' برای دیدن ساختار.' );
		if ( in_array( $type, $EVENTS, true ) ) {
			$meta['event_start_date'] = gmdate( 'Y-m-d', strtotime( '+' . ( 5 + $i * 9 + (int) array_search( $type, $EVENTS, true ) * 3 ) . ' days' ) );
		}
		$id = $make( $type . '-' . $i, $type, 'fa', $label . ' نمونۀ ' . $fa_digits( $i ), '<p>' . $NOTE['fa'] . '</p>', $meta );
		/* یک ترجمه برای چند نوع (رویداد، مسیر، استان، شهر) — فقط ساختار */
		if ( 1 === $i && $id && in_array( $type, array( 'tp_festival', 'tp_route', 'tp_province', 'tp_city' ), true ) ) {
			foreach ( array( 'en' => 'Sample ', 'ar' => 'نموذج ' ) as $l => $p ) {
				$make( $type . '-1-' . $l, $type, $l, $p . $obj->name, '<p>' . $NOTE[ $l ] . '</p>', array_merge( $meta, array( 'short_summary' => $NOTE[ $l ] ) ), '', $id );
			}
		}
	}
}

echo implode( "\n", $report ), "\n";
echo ( $confirm ? 'created: ' : 'would create (dry-run): ' ), count( $made ), "\n";
