<?php
/**
 * ادعاهای SC1 گام اول — کم‌کردن پرس‌وجوهای صفحۀ موجودیت بی تغییر خروجی (P5-D369).
 *
 *   ۱ پیش‌بارگذاری: کش پست و متای همۀ اعضای هر گروه یک‌جا (`_prime_post_caches` و
 *     `update_meta_cache`)، و هر شناسه در یک درخواست فقط یک‌بار
 *   ۲ حافظۀ درون‌درخواستی `topersia_g_group_ids` با کلید (پست، گروه، زبان): نتیجۀ
 *     دوم از حافظه، زبان دیگر ← پیمایش تازه، `clean_post_cache` ← خالی
 *   ۳ جهش: خاموش‌کردن پرچم (`topersia_g_sc1`) شمار فراخوانی رابطه و پیش‌بارگذاری را بالا می‌برد
 *   ۴ خروجی (ترتیب، شمار، فیلتر انتشار P5-D247، دروازۀ زبان) با پرچم روشن و خاموش یکی
 *   ۵ هیچ transient یا option تازه
 *
 * pure-PHP با stub؛ HTML صفحه‌ها بایت‌به‌بایت در `html-identical-local.php` (محلی).
 * از assert.php پس از assert-parity بار می‌شود.
 */

echo "\nSC1 — کم‌کردن پرس‌وجوی صفحۀ موجودیت (گام اول، بی تغییر خروجی)  P5-D369\n" . str_repeat( '─', 78 ) . "\n";

/* stub ثبت‌کنندۀ فراخوانی کش وردپرس */
$GLOBALS['tp_sc1_prime'] = array();
$GLOBALS['tp_sc1_meta']  = array();
if ( ! function_exists( '_prime_post_caches' ) ) {
	function _prime_post_caches( $ids, $terms = true, $meta = true ) {
		$GLOBALS['tp_sc1_prime'][] = array_map( 'intval', (array) $ids );
	}
}
if ( ! function_exists( 'update_meta_cache' ) ) {
	function update_meta_cache( $type, $ids ) {
		$GLOBALS['tp_sc1_meta'][] = array_map( 'intval', (array) $ids );
		return array();
	}
}

$sc1_flag = function ( $on ) {
	$GLOBALS['tp_filters']['topersia_g_sc1'] = $on ? array() : array( function () { return false; } );
};
$sc1_lang = function ( $lang ) {
	$GLOBALS['tp_filters']['wpml_current_language'] = null === $lang ? array() : array( function () use ( $lang ) { return $lang; } );
};
$sc1_reset = function () {
	topersia_g_memo_reset();
	$GLOBALS['tp_rel_calls'] = 0;
	$GLOBALS['tp_sc1_prime'] = array();
	$GLOBALS['tp_sc1_meta']  = array();
};
$sc1_hits = function ( $log, $id ) {
	$n = 0;
	foreach ( $log as $batch ) {
		$n += count( array_keys( $batch, $id, true ) );
	}
	return $n;
};

$sc1_keep_filters = isset( $GLOBALS['tp_filters'] ) ? $GLOBALS['tp_filters'] : array();
$sc1_options      = isset( $GLOBALS['tp_options'] ) ? $GLOBALS['tp_options'] : null;
$sc1_group        = topersia_g_group( 20, 'places' );

$sc1_flag( true );
$sc1_lang( null );

/* ۱ — پیش‌بارگذاری */
$sc1_reset();
$sc1_items = topersia_g_rel_items( 20, 'places', 8 );
$sc1_all   = topersia_g_group_ids( 20, $sc1_group );

t( 'SC1: مجموعۀ آزمون پرشده — گروه places استان چند عضو دارد',
	count( $sc1_all ) >= 10 && ! empty( $sc1_items['items'] ) );

t( 'SC1: پیش از مرتب‌سازی، کش پست و متای همۀ اعضا گرفته می‌شود — هر عضو دقیقاً یک بار در `_prime_post_caches` و یک بار در `update_meta_cache`',
	array_reduce( $sc1_all, function ( $ok, $one ) use ( $sc1_hits ) {
		return $ok && 1 === $sc1_hits( $GLOBALS['tp_sc1_prime'], $one ) && 1 === $sc1_hits( $GLOBALS['tp_sc1_meta'], $one );
	}, true ),
	json_encode( array( $GLOBALS['tp_sc1_prime'], $GLOBALS['tp_sc1_meta'] ) ) );

$sc1_n = count( $GLOBALS['tp_sc1_prime'] );
topersia_g_rel_items( 20, 'places', 8 );
t( 'SC1: بار دوم همان گروه، پیش‌بارگذاری دوباره صدا زده نمی‌شود',
	count( $GLOBALS['tp_sc1_prime'] ) === $sc1_n );

/* ۲ — حافظۀ پیمایش */
$sc1_reset();
$sc1_a = topersia_g_group_ids( 20, $sc1_group );
$sc1_c = $GLOBALS['tp_rel_calls'];
$sc1_b = topersia_g_group_ids( 20, $sc1_group );
t( 'SC1: بار نخست گروه می‌پیماید و بار دوم از حافظه می‌دهد (صفر فراخوانی رابطه) با نتیجۀ یکسان',
	$sc1_c > 0 && $GLOBALS['tp_rel_calls'] === $sc1_c && $sc1_a === $sc1_b,
	$sc1_c . ' / ' . $GLOBALS['tp_rel_calls'] );

$sc1_reset();
for ( $i = 0; $i < 4; $i ++ ) {
	topersia_g_group_ids( 20, $sc1_group );
	if ( 0 === $i ) { $sc1_one = $GLOBALS['tp_rel_calls']; }
}
t( 'SC1: گروهی که تا ۴ بار در یک درخواست پیموده می‌شد، یک بار پیموده می‌شود',
	$sc1_one > 0 && $GLOBALS['tp_rel_calls'] === $sc1_one );

$sc1_reset();
topersia_g_group_ids( 20, $sc1_group );
$sc1_c = $GLOBALS['tp_rel_calls'];
topersia_g_group_ids( 20, topersia_g_group( 20, 'mountains' ) ?: array( 'rels' => array( array( 'id' => 78, 'dir' => 'children' ) ) ) );
$sc1_d = $GLOBALS['tp_rel_calls'];
t( 'SC1: کلید شامل گروه است — گروه دیگر همان پست حافظۀ گروه اول را نمی‌گیرد',
	$sc1_d > $sc1_c );

$sc1_reset();
topersia_g_group_ids( 20, $sc1_group );
$sc1_c = $GLOBALS['tp_rel_calls'];
topersia_g_group_ids( 21, $sc1_group );
t( 'SC1: کلید شامل پست است — پست دیگر با همان گروه پیمایش تازه می‌کند',
	$GLOBALS['tp_rel_calls'] > $sc1_c );

$sc1_reset();
topersia_g_group_ids( 20, $sc1_group );
$sc1_c = $GLOBALS['tp_rel_calls'];
$sc1_lang( 'en' );
topersia_g_group_ids( 20, $sc1_group );
$sc1_e = $GLOBALS['tp_rel_calls'];
topersia_g_group_ids( 20, $sc1_group );
t( 'SC1: کلید شامل زبان است — زبان دیگر پیمایش تازه می‌کند و بعد خودش از حافظه می‌آید',
	$sc1_e > $sc1_c && $GLOBALS['tp_rel_calls'] === $sc1_e );
$sc1_lang( null );

$sc1_reset();
topersia_g_group_ids( 20, $sc1_group );
$sc1_c = $GLOBALS['tp_rel_calls'];
topersia_g_memo_reset();
topersia_g_group_ids( 20, $sc1_group );
t( 'SC1: خالی‌شدن حافظه (clean_post_cache) پیمایش تازه می‌خواهد — دادۀ کهنه نمی‌ماند',
	$GLOBALS['tp_rel_calls'] > $sc1_c );

/* ۳ — جهش: پرچم خاموش */
$sc1_flag( false );
$sc1_reset();
topersia_g_group_ids( 20, $sc1_group );
$sc1_c = $GLOBALS['tp_rel_calls'];
topersia_g_group_ids( 20, $sc1_group );
topersia_g_rel_items( 20, 'places', 8 );
t( 'SC1 جهش: خاموش‌کردن حافظه، شمار فراخوانی رابطه را بالا می‌برد (بار دوم هم می‌پیماید)',
	$GLOBALS['tp_rel_calls'] > 2 * $sc1_c - 1 && $GLOBALS['tp_rel_calls'] >= 2 * $sc1_c,
	$sc1_c . ' / ' . $GLOBALS['tp_rel_calls'] );
t( 'SC1 جهش: خاموش‌کردن پیش‌بارگذاری، هیچ `_prime_post_caches` و `update_meta_cache` صدا نمی‌زند',
	array() === $GLOBALS['tp_sc1_prime'] && array() === $GLOBALS['tp_sc1_meta'] );

/* ۴ — خروجی یکی */
$sc1_snap = function () {
	$out = array();
	foreach ( array( 20, 21, 10, 22, 27 ) as $pid ) {
		foreach ( array( 'places', 'mountains', 'cities', 'events', 'practical', 'neighbors', 'ancient', 'deserts' ) as $key ) {
			$out[ $pid . ':' . $key ] = topersia_g_rel_items( $pid, $key, 8 );
		}
		$out[ $pid . ':ids' ] = topersia_g_group_ids( $pid, topersia_g_group( $pid, 'places' ) );
	}
	return $out;
};
$sc1_pub_sets = array(
	'پایه'           => function () {},
	'پیش‌نویس در زنجیره' => function () { $GLOBALS['tp_posts'][50]['status'] = 'draft'; },
);
$sc1_same = true;
$sc1_nonempty = 0;
foreach ( $sc1_pub_sets as $name => $mutate ) {
	$keep_posts = $GLOBALS['tp_posts'];
	$mutate();
	foreach ( array( null, 'en', 'ar' ) as $lang ) {
		$sc1_lang( $lang );
		$sc1_flag( true );  $sc1_reset(); $on  = $sc1_snap();
		$sc1_flag( false ); $sc1_reset(); $off = $sc1_snap();
		if ( $on !== $off ) { $sc1_same = false; echo "    ≠ $name / " . ( $lang ?: 'fa' ) . "\n"; }
		foreach ( $on as $v ) { if ( ! empty( $v['items'] ) ) { $sc1_nonempty ++; } }
	}
	$GLOBALS['tp_posts'] = $keep_posts;
}
$sc1_lang( null );
t( 'SC1: ترتیب، شمار، فیلتر انتشار و دروازۀ زبان — خروجی همۀ بخش‌های رابطه با پرچم روشن و خاموش یکی (fa · en · ar، با پیش‌نویس در زنجیره)',
	$sc1_same && $sc1_nonempty > 10, 'غیرتهی: ' . $sc1_nonempty );

/* ۵ — هیچ ماندگاری تازه */
$sc1_src = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );
$sc1_blk = substr( $sc1_src, strpos( $sc1_src, 'function &topersia_g_memo_store' ), strpos( $sc1_src, 'function topersia_g_is_live' ) - strpos( $sc1_src, 'function &topersia_g_memo_store' ) );
$sc1_grp = substr( $sc1_src, strpos( $sc1_src, 'function topersia_g_group_ids' ), 1800 );
t( 'SC1: حافظه فقط در حافظۀ فرایند — در کد حافظه و پیش‌بارگذاری هیچ transient، option یا کش شیء پایدار نیست',
	! preg_match( '/(set_transient|update_option|add_option|wp_cache_set|set_site_transient)/', $sc1_blk . $sc1_grp ) );

$GLOBALS['tp_filters'] = $sc1_keep_filters;
$sc1_reset();
