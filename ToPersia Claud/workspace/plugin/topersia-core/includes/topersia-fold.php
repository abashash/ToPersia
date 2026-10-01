<?php
/**
 * تاکردن متن برای جست‌وجو — P5-D265 · SPRINT-4.4 بند ۲٫۶ گام ۱.
 *
 * یک قاعده، یک جدول. «ۀ» (U+06C0)، «ه» با همزۀ بالا (U+0647 U+0654) و «ه» یکی می‌شوند؛
 * «ي» و «ى» ← «ی»؛ «ك» ← «ک»؛ اعراب می‌رود؛ رقم فارسی و عربی ← لاتین؛
 * نیم‌فاصله، نویسه‌های بی‌عرض و فاصله می‌روند؛ حروف لاتین کوچک.
 *
 * چرا جدول و نه یک regex: همین جدول دو مصرف دارد — `topersia_search_fold()`
 * در PHP (متای `_tp_search_fold` و عبارت جست‌وجوی سایت) و پنجرۀ فهرستِ
 * render در مرورگر، که عبارتِ تایپ‌شده را با همین قاعده‌ها تا می‌کند و
 * اعضایش را از `data-tp-fold` ساخته‌شده با همین تابع می‌خواند. قاعده‌ها
 * نقطۀ کد نگه می‌دارند تا هر سو الگوی خودش را از همان داده بسازد (PHP
 * `\x{…}`، جاوااسکریپت `\u…`). دو پیاده‌سازیِ قاعده نه.
 *
 * ذخیره: متای مکمل `_tp_search_fold` روی عنوان و خلاصه، در save_post.
 * ساختار تازه نیست جز این متا — و همین متا فقط محلی تا ممیزی پیوست الف
 * بند ۶ (`topersia_search_fold_enabled()`، مثل جدول کش هسته). بیرون از
 * local نه متا نوشته می‌شود نه جست‌وجوی سایت به آن نگاه می‌کند؛ تاکردن
 * پنجرۀ فهرست در مرورگر است و چیزی ذخیره نمی‌کند، پس همه‌جا هست.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TOPERSIA_SEARCH_FOLD_META' ) ) {
	define( 'TOPERSIA_SEARCH_FOLD_META', '_tp_search_fold' );
}

/**
 * جدول قاعده‌ها، به ترتیب اجرا.
 *
 *   chars   نقطه‌های کد تکی ← to
 *   ranges  بازه‌های [از، تا] ← to
 *   digits  نقطۀ کد «صفر» یک دستگاه رقم ← «0» تا «9»
 *   space   فاصله‌های سفید هم (\s) در همان کلاس
 *   lower   کوچک‌کردن حروف
 *
 * @return array<int,array>
 */
function topersia_search_fold_rules() {

	return array(
		array( 'chars' => array( 0x064A, 0x0649 ), 'to' => "\u{06CC}" ),                            /* ي ى ← ی */
		array( 'chars' => array( 0x0643 ), 'to' => "\u{06A9}" ),                                    /* ك ← ک */
		array( 'chars' => array( 0x06C0 ), 'to' => "\u{0647}" ),                                    /* ۀ ← ه */
		array( 'ranges' => array( array( 0x064B, 0x065F ), array( 0x0670, 0x0670 ), array( 0x06D6, 0x06ED ) ), 'to' => '' ), /* اعراب؛ همزۀ بالای U+0654 هم */
		array( 'digits' => 0x06F0 ),                                                                 /* ۰–۹ */
		array( 'digits' => 0x0660 ),                                                                 /* ٠–٩ */
		array( 'ranges' => array( array( 0x200B, 0x200F ) ), 'space' => true, 'to' => '' ),        /* نیم‌فاصله و فاصله */
		array( 'lower' => true ),
	);
}

/** کلاس نویسه‌ای PCRE یک قاعده. */
function topersia_search_fold_class( $rule ) {

	$in = '';

	foreach ( isset( $rule['chars'] ) ? $rule['chars'] : array() as $cp ) {
		$in .= sprintf( '\x{%04X}', $cp );
	}

	foreach ( isset( $rule['ranges'] ) ? $rule['ranges'] : array() as $r ) {
		$in .= sprintf( '\x{%04X}', $r[0] ) . ( $r[1] !== $r[0] ? sprintf( '-\x{%04X}', $r[1] ) : '' );
	}

	if ( ! empty( $rule['space'] ) ) {
		$in .= '\s';
	}

	return '[' . $in . ']' . ( ! empty( $rule['space'] ) ? '+' : '' );
}

/**
 * متن تاشده.
 *
 * @param string $text متن.
 * @return string
 */
function topersia_search_fold( $text ) {

	$text = (string) $text;

	if ( '' === $text ) {
		return '';
	}

	foreach ( topersia_search_fold_rules() as $rule ) {

		if ( ! empty( $rule['lower'] ) ) {
			$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
			continue;
		}

		if ( isset( $rule['digits'] ) ) {
			$zero = (int) $rule['digits'];
			$text = preg_replace_callback(
				'/[' . sprintf( '\x{%04X}-\x{%04X}', $zero, $zero + 9 ) . ']/u',
				function ( $m ) use ( $zero ) {
					return (string) ( mb_ord( $m[0], 'UTF-8' ) - $zero );
				},
				$text
			);
			continue;
		}

		$text = preg_replace( '/' . topersia_search_fold_class( $rule ) . '/u', $rule['to'], $text );
	}

	return (string) $text;
}

/**
 * همان جدول برای مرورگر — به‌صورت داده، نه کد.  پنجرۀ فهرست render.
 *
 * هر قاعده: { c: [نقطۀ کد…], r: [[از، تا]…], s: bool, t: جایگزین } یا
 * { d: صفر } یا { l: 1 }.
 *
 * @return array
 */
function topersia_search_fold_js_rules() {

	$out = array();

	foreach ( topersia_search_fold_rules() as $rule ) {

		if ( ! empty( $rule['lower'] ) ) {
			$out[] = array( 'l' => 1 );
			continue;
		}

		if ( isset( $rule['digits'] ) ) {
			$out[] = array( 'd' => (int) $rule['digits'] );
			continue;
		}

		$out[] = array(
			'c' => isset( $rule['chars'] ) ? array_values( $rule['chars'] ) : array(),
			'r' => isset( $rule['ranges'] ) ? array_values( $rule['ranges'] ) : array(),
			's' => ! empty( $rule['space'] ),
			't' => (string) $rule['to'],
		);
	}

	return $out;
}

/**
 * متن جست‌وجوپذیر یک پست: عنوان و خلاصه.
 *
 * خلاصه: `short_summary` موجودیت، و در نبودش چکیده. جداکنندۀ «|» پس از
 * تاکردن هم می‌ماند، تا تکه‌ای از پایان عنوان با آغاز خلاصه یک واژه نشود.
 */
function topersia_search_fold_post( $post_id ) {

	$post = get_post( $post_id );

	if ( ! $post ) {
		return '';
	}

	$summary = (string) get_post_meta( $post->ID, 'short_summary', true );
	$summary = '' !== trim( $summary ) ? $summary : (string) $post->post_excerpt;

	return topersia_search_fold( wp_strip_all_tags( $post->post_title ) ) . '|' . topersia_search_fold( wp_strip_all_tags( $summary ) );
}

/** متای تاشده نوشته و خوانده شود؟ فقط محلی تا ممیزی پیوست الف بند ۶. */
function topersia_search_fold_enabled() {
	return (bool) apply_filters( 'topersia_search_fold_enabled', 'local' === wp_get_environment_type() );
}

/** نوع‌هایی که متای تاشده می‌گیرند: هر نوع عمومیِ جست‌وجوپذیر، جز پیوست (رسانه در جست‌وجوی سایت نیست). */
function topersia_search_fold_types() {
	return (array) apply_filters( 'topersia_search_fold_types', array_values( array_diff( get_post_types( array( 'public' => true, 'exclude_from_search' => false ) ), array( 'attachment' ) ) ) );
}

/** save_post — متای تاشده از نو. بازبینی و ذخیرۀ خودکار نه. */
function topersia_search_fold_save( $post_id, $post = null ) {

	if ( ! topersia_search_fold_enabled() || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$post = $post ? $post : get_post( $post_id );

	if ( ! $post || ! in_array( $post->post_type, topersia_search_fold_types(), true ) ) {
		return;
	}

	update_post_meta( $post_id, TOPERSIA_SEARCH_FOLD_META, topersia_search_fold_post( $post_id ) );
}
add_action( 'save_post', 'topersia_search_fold_save', 30, 2 );

/*
 * خلاصه متاست و با save_post همیشه همگام نیست (JetEngine متا را پس از
 * save_post هم می‌نویسد). تغییر خودِ short_summary هم متای تاشده را از نو
 * می‌سازد.
 */
add_action( 'updated_post_meta', 'topersia_search_fold_meta_changed', 10, 3 );
add_action( 'added_post_meta', 'topersia_search_fold_meta_changed', 10, 3 );

function topersia_search_fold_meta_changed( $meta_id, $post_id, $meta_key ) {
	if ( 'short_summary' === $meta_key ) {
		topersia_search_fold_save( (int) $post_id );
	}
}

/**
 * جست‌وجوی سایت: عبارتِ تاشده با متای تاشده هم مقایسه می‌شود.
 *
 * بند نخستِ جست‌وجوی وردپرس (« AND (…)») با «EXISTS(…) OR» آغاز می‌شود؛
 * بند رمز (« AND (post_password = '')») دست نمی‌خورد، پس پست رمزدار از
 * این راه هم بیرون نمی‌آید. عبارت خام همچنان مثل پیش سنجیده می‌شود.
 */
function topersia_search_fold_posts_search( $search, $query ) {

	if ( ! topersia_search_fold_enabled() || ! is_string( $search ) || '' === trim( $search ) || ! is_object( $query ) || ! method_exists( $query, 'is_search' ) || ! $query->is_search() ) {
		return $search;
	}

	$fold = topersia_search_fold( (string) $query->get( 's' ) );

	if ( '' === str_replace( '|', '', $fold ) ) {
		return $search;
	}

	global $wpdb;

	$exists = $wpdb->prepare(
		"EXISTS ( SELECT 1 FROM {$wpdb->postmeta} tp_fold WHERE tp_fold.post_id = {$wpdb->posts}.ID AND tp_fold.meta_key = %s AND tp_fold.meta_value LIKE %s )",
		TOPERSIA_SEARCH_FOLD_META,
		'%' . $wpdb->esc_like( $fold ) . '%'
	);

	return preg_replace_callback(
		'/^\s*AND\s*\(/',
		function () use ( $exists ) {
			return ' AND ( ' . $exists . ' OR ';
		},
		$search,
		1
	);
}
add_filter( 'posts_search', 'topersia_search_fold_posts_search', 20, 2 );
