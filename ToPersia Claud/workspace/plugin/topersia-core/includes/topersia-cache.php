<?php
/**
 * لایۀ کش مرکزی — P5-D250 · SPRINT-4.4 بند ۲٫۳ · قاعدۀ ۰-۶.
 *
 *   topersia_cache_remember( $service, array $key_parts, callable $fetch, array $opts )
 *
 * هر فراخوانی سرویس بیرونی از این‌جا می‌گذرد. کلید: سرویس + ارائه‌دهنده +
 * مدل/نسخه + زبان + بخش‌های کلید؛ برای محتوای وابسته به پست، هش محتوا
 * (sha1 عنوان، متاهای FIELDMAP، شناسۀ رابطه‌های منتشرشده و زبان).
 *
 * دو گونه خروجی:
 *   ارزان (هوا، کیفیت هوا، مسیر، …)  هش در خودِ کلید است: محتوا عوض شد،
 *                                     کلید تازه، دریافت تازه.
 *   گران (هوش مصنوعی، پادکست)          هش کنار مقدار: محتوا عوض شد، همان
 *                                     خروجی با نشان «کهنه» برمی‌گردد و فقط
 *                                     با اقدام مجاز (`rebuild`) دوباره ساخته
 *                                     می‌شود. هرگز پاک نمی‌شود.
 *
 * ابطال: save_post · تغییر وضعیت · حذف · تغییر رابطۀ JetEngine · ترجمۀ WPML
 * — هش پست‌های درگیر «کثیف» می‌شود (متای `_tp_content_hash` پاک) و در
 * نخستین نیاز از نو ساخته می‌شود. هزینۀ قلاب یک DELETE است، نه پیمایش.
 *
 * نگهداری: JSON کوچک در transient (بی autoload). مسیریابی و آدرس در جدول
 * کش هسته با TTL — ساختار تازه: فقط محلی تا ممیزی پیوست الف بند ۶؛ بیرون
 * از محیط local همان transient. خروجی هوش مصنوعی در جدول خودِ خلاصه‌ساز
 * (انبارۀ callable)، فایل صوتی در کتابخانۀ رسانه (فاز ۴).
 *
 * موقعیت دقیق کاربر هرگز کلید کش یا لاگ نمی‌شود: فقط سلول گردشده.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TOPERSIA_CONTENT_HASH_META' ) ) {
	define( 'TOPERSIA_CONTENT_HASH_META', '_tp_content_hash' );
}

/* ══ هش محتوا ═══════════════════════════════════════════════════════ */

/**
 * هش محتوای یک پست — از متا اگر هست، وگرنه ساخته و نگه داشته می‌شود.
 *
 * @param int  $post_id شناسه.
 * @param bool $fresh   بی‌توجه به متا، از نو.
 * @return string sha1، یا تهی اگر پست نیست
 */
function topersia_content_hash( $post_id, $fresh = false ) {

	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		return '';
	}

	$stored = get_post_meta( $post_id, TOPERSIA_CONTENT_HASH_META, true );

	if ( ! $fresh && is_string( $stored ) && 40 === strlen( $stored ) ) {
		return $stored;
	}

	$hash = topersia_content_hash_compute( $post_id );

	if ( '' !== $hash ) {
		update_post_meta( $post_id, TOPERSIA_CONTENT_HASH_META, $hash );
	}

	return $hash;
}

/**
 * ساختن هش: عنوان · متاهای FIELDMAP · شناسۀ رابطه‌های منتشرشده · زبان.
 *
 * فهرست متاها از فیلتر `topersia_content_hash_fields` (render نقشۀ FIELDMAP
 * را می‌دهد)؛ بی آن، هر متای بی‌زیرخط. رابطه‌ها: هر رابطۀ فعال JetEngine
 * که نوع این پست یک سرش است؛ فقط سوی مقابلِ منتشرشده و بی‌رمز، مرتب.
 */
function topersia_content_hash_compute( $post_id ) {

	$post = get_post( $post_id );

	if ( ! $post ) {
		return '';
	}

	$all    = get_post_meta( $post->ID );
	$keys   = apply_filters( 'topersia_content_hash_fields', null, $post );
	$fields = array();

	foreach ( (array) $all as $k => $values ) {

		if ( is_array( $keys ) ? ! in_array( $k, $keys, true ) : ( '' === $k || '_' === $k[0] ) ) {
			continue;
		}

		$fields[ $k ] = array_map( 'maybe_unserialize', (array) $values );
	}

	ksort( $fields );

	$lang = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $post->ID, 'element_type' => 'post_' . $post->post_type ) );

	return sha1( wp_json_encode( array(
		'title'  => (string) $post->post_title,
		'fields' => $fields,
		'rel'    => topersia_content_hash_relations( $post ),
		'lang'   => is_string( $lang ) ? $lang : '',
	) ) );
}

/** شناسه‌های منتشرشدۀ طرف مقابل هر رابطۀ فعال. */
function topersia_content_hash_relations( $post ) {

	$out = array();

	if ( ! function_exists( 'jet_engine' ) || ! jet_engine()->relations || ! method_exists( jet_engine()->relations, 'get_active_relations' ) ) {
		return $out;
	}

	$type = 'posts::' . $post->post_type;

	foreach ( (array) jet_engine()->relations->get_active_relations() as $rid => $rel ) {

		$ids = array();

		if ( $type === $rel->get_args( 'parent_object' ) ) {
			$ids = array_merge( $ids, (array) $rel->get_children( $post->ID, 'ids' ) );
		}

		if ( $type === $rel->get_args( 'child_object' ) ) {
			$ids = array_merge( $ids, (array) $rel->get_parents( $post->ID, 'ids' ) );
		}

		$ids = array_values( array_filter( array_unique( array_map( 'absint', $ids ) ), function ( $id ) {
			$p = get_post( $id );
			return $p && 'publish' === $p->post_status && '' === (string) $p->post_password;
		} ) );

		if ( $ids ) {
			sort( $ids );
			$out[ (string) $rid ] = $ids;
		}
	}

	ksort( $out );

	return $out;
}

/** هش یک یا چند پست کثیف — در نخستین نیاز از نو. */
function topersia_content_hash_dirty( $ids ) {

	foreach ( (array) $ids as $id ) {

		$id = absint( $id );

		if ( $id ) {
			delete_post_meta( $id, TOPERSIA_CONTENT_HASH_META );
		}
	}
}

/** همۀ طرف‌های مقابل یک پست در رابطه‌های فعال — هر وضعیتی. */
function topersia_content_hash_neighbours( $post_id ) {

	$post = get_post( $post_id );

	if ( ! $post || ! function_exists( 'jet_engine' ) || ! jet_engine()->relations || ! method_exists( jet_engine()->relations, 'get_active_relations' ) ) {
		return array();
	}

	$type = 'posts::' . $post->post_type;
	$ids  = array();

	foreach ( (array) jet_engine()->relations->get_active_relations() as $rel ) {
		if ( $type === $rel->get_args( 'parent_object' ) ) {
			$ids = array_merge( $ids, (array) $rel->get_children( $post->ID, 'ids' ) );
		}
		if ( $type === $rel->get_args( 'child_object' ) ) {
			$ids = array_merge( $ids, (array) $rel->get_parents( $post->ID, 'ids' ) );
		}
	}

	return array_values( array_unique( array_map( 'absint', $ids ) ) );
}

/* ── قلاب‌های ابطال ── */

/* ذخیره و متای FIELDMAP: فقط خودِ پست. عنوان و متا در هش همسایه‌ها نیست. */
add_action( 'save_post', function ( $post_id ) {
	if ( ! wp_is_post_revision( $post_id ) && ! wp_is_post_autosave( $post_id ) ) {
		topersia_content_hash_dirty( $post_id );
	}
}, 5 );

foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $topersia_hook ) {
	add_action( $topersia_hook, function ( $meta_id, $post_id, $meta_key ) {
		if ( is_string( $meta_key ) && '' !== $meta_key && '_' !== $meta_key[0] ) {
			topersia_content_hash_dirty( $post_id );
		}
	}, 10, 3 );
}
unset( $topersia_hook );

/* وضعیت و حذف: خودِ پست و هر همسایه — شناسۀ منتشرشده در هش آن‌هاست. */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( $new !== $old && is_object( $post ) && ! wp_is_post_revision( $post->ID ) ) {
		topersia_content_hash_dirty( array_merge( array( $post->ID ), topersia_content_hash_neighbours( $post->ID ) ) );
	}
}, 10, 3 );

add_action( 'before_delete_post', function ( $post_id ) {
	topersia_content_hash_dirty( array_merge( array( $post_id ), topersia_content_hash_neighbours( $post_id ) ) );
} );

/* رابطۀ JetEngine: دو سرِ یال. */
foreach ( array( 'jet-engine/relation/update/after', 'jet-engine/relation/delete/after' ) as $topersia_hook ) {
	add_action( $topersia_hook, function ( $parent, $child ) {
		topersia_content_hash_dirty( array( $parent, $child ) );
	}, 10, 2 );
}
unset( $topersia_hook );

/* ترجمۀ WPML: پایان ترجمه در ویرایشگر WPML (پست ترجمه‌شده). */
add_action( 'icl_pro_translation_completed', function ( $post_id ) {
	topersia_content_hash_dirty( $post_id );
}, 10, 1 );

/* ══ TTL ═══════════════════════════════════════════════════════════════ */

/**
 * پیش‌فرض‌های جدول ۲٫۳. عدد = ثانیه · 0 = دائمی · رشته = قاعده:
 *   expires  از سرآیند Expires پاسخ (fetch ثانیه را برمی‌گرداند)
 *   hour     تا پایان ساعت جاری
 *   hash     تا تغییر هش محتوا (خروجی گران)
 */
function topersia_cache_ttl_defaults() {
	return array(
		'weather'         => 'expires',
		'air_quality'     => 'hour',
		'ai.text'         => 'hash',
		'ai.tts'          => 'hash',
		'routing'         => 30 * DAY_IN_SECONDS,
		'distance_matrix' => 30 * DAY_IN_SECONDS,
		'geocoding'       => 0,
		'nearby'          => 10 * MINUTE_IN_SECONDS,
	);
}

/** TTL یک سرویس — تنظیم مدیریت سرویس‌ها، وگرنه پیش‌فرض جدول. */
function topersia_cache_ttl( $service ) {

	if ( function_exists( 'topersia_api' ) ) {
		$cfg = topersia_api( $service );
		if ( isset( $cfg['ttl'] ) && '' !== $cfg['ttl'] && null !== $cfg['ttl'] ) {
			return is_numeric( $cfg['ttl'] ) ? (int) $cfg['ttl'] : $cfg['ttl'];
		}
	}

	$d = topersia_cache_ttl_defaults();

	return isset( $d[ $service ] ) ? $d[ $service ] : HOUR_IN_SECONDS;
}

/** «تا پایان ساعت جاری» به ثانیه. */
function topersia_cache_until_hour_end( $now = null ) {
	$now = null === $now ? time() : (int) $now;
	return max( 60, 3600 - ( $now % 3600 ) );
}

/* ══ کلید و انباره ═════════════════════════════════════════════════════ */

/**
 * کلید: sha1 از سرویس، ارائه‌دهنده، مدل/نسخه، زبان، بخش‌ها و (برای ارزان)
 * هش محتوا. ترتیب بخش‌ها معنا دارد و حفظ می‌شود.
 */
function topersia_cache_key( $service, array $parts, array $opts ) {

	$cfg = function_exists( 'topersia_api' ) ? topersia_api( $service ) : array();

	return sha1( wp_json_encode( array(
		's' => (string) $service,
		'p' => isset( $opts['provider'] ) ? (string) $opts['provider'] : ( isset( $cfg['provider'] ) ? (string) $cfg['provider'] : '' ),
		'm' => isset( $opts['model'] ) ? (string) $opts['model'] : ( isset( $cfg['model'] ) ? (string) $cfg['model'] : '' ),
		'v' => isset( $cfg['version'] ) ? (string) $cfg['version'] : '',
		'l' => isset( $opts['lang'] ) ? (string) $opts['lang'] : '',
		'k' => array_values( $parts ),
		'h' => ( ! empty( $opts['post'] ) && empty( $opts['expensive'] ) ) ? topersia_content_hash( $opts['post'] ) : '',
	) ) );
}

/** جدول کش هسته ساخته شود؟ فقط محلی تا ممیزی پیوست الف بند ۶. */
function topersia_cache_table_enabled() {
	return (bool) apply_filters( 'topersia_cache_table_enabled', 'local' === wp_get_environment_type() );
}

function topersia_cache_table() {
	global $wpdb;
	return $wpdb->prefix . 'topersia_cache';
}

/** ساخت جدول — تکرارپذیر، فقط وقتی مجاز. */
function topersia_cache_install() {

	if ( ! topersia_cache_table_enabled() || '1' === get_option( 'topersia_cache_schema' ) ) {
		return;
	}

	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( 'CREATE TABLE ' . topersia_cache_table() . " (
		ckey CHAR(40) NOT NULL,
		service VARCHAR(40) NOT NULL DEFAULT '',
		value LONGTEXT NOT NULL,
		hash CHAR(40) NOT NULL DEFAULT '',
		fresh_until BIGINT UNSIGNED NOT NULL DEFAULT 0,
		created BIGINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY  (ckey),
		KEY service (service),
		KEY fresh_until (fresh_until)
	) " . $wpdb->get_charset_collate() . ';' );

	update_option( 'topersia_cache_schema', '1', false );
}
add_action( 'admin_init', 'topersia_cache_install' );

/**
 * انبارۀ پیش‌فرض یک سرویس: مسیریابی، ماتریس و آدرس در جدول؛ بقیه transient.
 */
function topersia_cache_store_for( $service ) {
	return in_array( $service, array( 'routing', 'distance_matrix', 'geocoding' ), true ) && topersia_cache_table_enabled() ? 'table' : 'transient';
}

/** خواندن پوشه (envelope). */
function topersia_cache_read( $key, $store ) {

	if ( is_array( $store ) && isset( $store['get'] ) && is_callable( $store['get'] ) ) {
		$e = call_user_func( $store['get'], $key );
		return is_array( $e ) ? $e : null;
	}

	if ( 'table' === $store ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT value, hash, fresh_until, created FROM ' . topersia_cache_table() . ' WHERE ckey = %s', $key ) );
		if ( ! $row ) {
			return null;
		}
		$v = json_decode( $row->value, true );
		return is_array( $v ) ? array_merge( $v, array( 'hash' => $row->hash, 'fresh_until' => (int) $row->fresh_until, 'created' => (int) $row->created ) ) : null;
	}

	$e = get_transient( 'tpc_' . $key );

	return is_array( $e ) ? $e : null;
}

/** نوشتن پوشه. */
function topersia_cache_write( $key, $store, array $envelope, $keep, $service ) {

	if ( is_array( $store ) && isset( $store['put'] ) && is_callable( $store['put'] ) ) {
		call_user_func( $store['put'], $key, $envelope );
		return;
	}

	if ( 'table' === $store ) {
		global $wpdb;
		$wpdb->replace( topersia_cache_table(), array(
			'ckey'        => $key,
			'service'     => substr( (string) $service, 0, 40 ),
			'value'       => wp_json_encode( array( 'value' => $envelope['value'], 'meta' => $envelope['meta'] ) ),
			'hash'        => (string) $envelope['hash'],
			'fresh_until' => (int) $envelope['fresh_until'],
			'created'     => (int) $envelope['created'],
		) );
		return;
	}

	/* transient بی‌انقضا autoload می‌شود؛ پس همیشه با انقضا */
	set_transient( 'tpc_' . $key, $envelope, max( 60, (int) $keep ) );
}

/* ══ remember ═════════════════════════════════════════════════════════ */

/**
 * آخرین وضعیت — برای آزمون و گزارش: hit · miss · stale · stale-error · error.
 */
function topersia_cache_last( $set = null ) {
	static $last = array();
	if ( null !== $set ) {
		$last = $set;
	}
	return $last;
}

/**
 * کش مرکزی.
 *
 * @param string   $service   شناسۀ سرویس (weather · ai.text · …).
 * @param array    $key_parts بخش‌های کلید (مختصات گردشده، شناسۀ پست، نوع خروجی …).
 * @param callable $fetch     fn( $prev ) → array( 'value', 'ttl'?, 'meta'? ) | مقدار خام | WP_Error.
 *                            $prev پوشۀ پیشین است (برای درخواست شرطی) یا null.
 * @param array    $opts      post · lang · provider · model · ttl · store · keep ·
 *                            stale_on_error · expensive · rebuild
 * @return mixed مقدار، یا WP_Error
 */
function topersia_cache_remember( $service, array $key_parts, callable $fetch, array $opts = array() ) {

	$opts = array_merge( array(
		'post'           => 0,
		'lang'           => '',
		'ttl'            => null,
		'store'          => null,
		'keep'           => 7 * DAY_IN_SECONDS,
		'stale_on_error' => true,
		'expensive'      => false,
		'rebuild'        => false,
	), $opts );

	$store = null === $opts['store'] ? topersia_cache_store_for( $service ) : $opts['store'];
	$key   = topersia_cache_key( $service, $key_parts, $opts );
	$now   = time();
	$prev  = topersia_cache_read( $key, $store );
	$hash  = $opts['post'] ? topersia_content_hash( $opts['post'] ) : '';

	if ( $prev && array_key_exists( 'value', $prev ) ) {

		if ( $opts['expensive'] ) {

			/* گران: همان هش ← همان خروجی. هش دیگر ← کهنه، مگر اقدام مجاز (rebuild) */
			if ( '' === $hash || (string) ( isset( $prev['hash'] ) ? $prev['hash'] : '' ) === $hash ) {
				topersia_cache_last( array( 'status' => 'hit', 'key' => $key, 'service' => $service ) );
				return $prev['value'];
			}

			if ( ! $opts['rebuild'] ) {
				topersia_cache_last( array( 'status' => 'stale', 'key' => $key, 'service' => $service ) );
				return $prev['value'];
			}

		} elseif ( ( 0 === (int) $prev['fresh_until'] || (int) $prev['fresh_until'] > $now ) && ! $opts['rebuild'] ) {
			topersia_cache_last( array( 'status' => 'hit', 'key' => $key, 'service' => $service ) );
			return $prev['value'];
		}
	}

	$res = call_user_func( $fetch, $prev );

	if ( is_wp_error( $res ) || null === $res ) {

		if ( function_exists( 'topersia_api_record' ) ) {
			topersia_api_record( $service, false, is_wp_error( $res ) ? $res->get_error_message() : 'بی پاسخ' );
		}

		if ( $prev && array_key_exists( 'value', $prev ) && $opts['stale_on_error'] ) {
			topersia_cache_last( array( 'status' => 'stale-error', 'key' => $key, 'service' => $service ) );
			return $prev['value'];
		}

		topersia_cache_last( array( 'status' => 'error', 'key' => $key, 'service' => $service ) );
		return $res;
	}

	$value = ( is_array( $res ) && array_key_exists( 'value', $res ) ) ? $res['value'] : $res;
	$meta  = ( is_array( $res ) && isset( $res['meta'] ) && is_array( $res['meta'] ) ) ? $res['meta'] : array();
	$ttl   = ( is_array( $res ) && isset( $res['ttl'] ) ) ? $res['ttl'] : ( null !== $opts['ttl'] ? $opts['ttl'] : topersia_cache_ttl( $service ) );

	if ( 'hour' === $ttl ) {
		$ttl = topersia_cache_until_hour_end( $now );
	} elseif ( 'hash' === $ttl ) {
		$ttl = 0;
	} elseif ( ! is_numeric( $ttl ) ) {
		$ttl = HOUR_IN_SECONDS;
	}

	$ttl      = (int) $ttl;
	$envelope = array(
		'value'       => $value,
		'meta'        => $meta,
		'hash'        => $hash,
		'fresh_until' => 0 === $ttl ? 0 : $now + $ttl,
		'created'     => $now,
	);

	topersia_cache_write( $key, $store, $envelope, 0 === $ttl ? YEAR_IN_SECONDS : max( $ttl, (int) $opts['keep'] ), $service );

	if ( function_exists( 'topersia_api_record' ) && empty( $res['from_cache'] ) ) {
		topersia_api_record( $service, true );
	}

	topersia_cache_last( array( 'status' => 'miss', 'key' => $key, 'service' => $service ) );

	return $value;
}

/**
 * آیا خروجی گرانِ یک پست کهنه است؟ هش ذخیره‌شده در برابر هش امروز.
 *
 * @param int    $post_id شناسه.
 * @param string $hash    هشی که خروجی با آن ساخته شد.
 * @return bool
 */
function topersia_cache_is_stale( $post_id, $hash ) {
	$hash = (string) $hash;
	return '' !== $hash && topersia_content_hash( $post_id ) !== $hash;
}
