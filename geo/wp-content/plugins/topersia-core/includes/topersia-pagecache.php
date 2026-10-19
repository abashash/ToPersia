<?php
/**
 * لایۀ برچسب کش صفحه — مستقل از میزبان.  SC2 · P5-D301 · P5-D372
 *
 * هر صفحۀ موجودیت (`tp_…`) مجموعۀ برچسب خودش را اعلام می‌کند:
 *   tp-post-{id}  — خودِ صفحه، هر نیای مسیر راهنما (و هر چیزی که render برای پیمودن می‌خواند)،
 *                   و هر موجودیتی که صفحه در کارت‌ها، جعبه‌ها یا گالری تجمیعی نشان می‌دهد
 *   tp-type-{post_type} — نوع صفحه
 * شناسه‌ها از همان پیمایش‌هایی می‌آیند که render انجام می‌دهد: render `topersia_render_seen` را صدا می‌زند
 * (در `topersia_g_published` ← «نشان‌داده‌شده»، در `topersia_g_rel_ids` بی‌دروازه ← «پیموده‌شده»، و در خواندن گالری
 * تجمیعی). این‌جا هیچ پیمایش تازه‌ای نیست.
 *
 * چون هر صفحه‌ای که پستی را نشان می‌دهد همان برچسب `tp-post-{id}` را دارد، پاک‌کردنِ آن برچسب دقیقاً صفحه‌های وابسته را
 * می‌زند، نه همه را. پاک‌کردن روی: ذخیرۀ نوشته، تغییر وضعیت (با همسایه‌های رابطه)، حذف، تغییر رابطۀ جت‌انجین، و ذخیرۀ
 * ترجمۀ WPML — همیشه برای همۀ ترجمه‌های همان پست.
 *
 * رابط عمومی (CDN با Cache-Tag بعداً جایگزین می‌شود، بی‌دست‌زدن به این فایل):
 *   do_action( 'topersia_pagecache_tags',    array $tags, array $ctx )  — برچسب‌های نهایی همین پاسخ
 *   do_action( 'topersia_pagecache_purge',   array $tags )              — برچسب‌هایی که باید پاک شوند
 *   do_action( 'topersia_pagecache_nocache', string $reason )           — این پاسخ نباید کش شود
 * اتصال LiteSpeed Cache پایین همین فایل است و فقط اگر افزونه فعال باشد کار می‌کند؛ بی آن (WAMP) هیچ اثری نیست،
 * جز ثبت در لاگ وقتی `TOPERSIA_PAGECACHE_DEBUG` روشن است.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** حالت درخواست جاری. `topersia_pagecache_state( 'reset' )` برای آزمون. */
function &topersia_pagecache_state( $reset = false ) {

	static $s = null;

	if ( null === $s || $reset ) {
		$s = array( 'on' => false, 'self' => 0, 'type' => '', 'shown' => array(), 'walked' => array(), 'last' => array() );
	}

	return $s;
}

function topersia_pagecache_debug( $message ) {

	if ( defined( 'TOPERSIA_PAGECACHE_DEBUG' ) && TOPERSIA_PAGECACHE_DEBUG && function_exists( 'topersia_log' ) ) {
		topersia_log( $message, 'info', 'pagecache' );
	}
}

function topersia_pagecache_post_tag( $id ) {
	return 'tp-post-' . absint( $id );
}

function topersia_pagecache_type_tag( $type ) {
	return 'tp-type-' . sanitize_key( $type );
}

/** درخواستِ «به‌زودی» برای مهمان (حالت نگهداری المنتور). */
function topersia_pagecache_maintenance() {

	return '' !== (string) get_option( 'elementor_maintenance_mode_mode', '' ) && ! is_user_logged_in();
}

/**
 * آغازِ صفحۀ موجودیت — برچسب‌ها فقط برای همین‌ها جمع می‌شود. بیرونِ کش با فهرست شاهد:
 * کاربر واردشده، «به‌زودی» برای مهمان، صفحۀ رمزدار.
 *
 * @return bool آیا این درخواست برچسب جمع می‌کند.
 */
function topersia_pagecache_begin( $post_id = 0 ) {

	$state = &topersia_pagecache_state( true );
	$post_id = $post_id ? absint( $post_id ) : (int) get_queried_object_id();
	$type    = $post_id ? (string) get_post_type( $post_id ) : '';

	if ( ! $post_id || 0 !== strpos( $type, 'tp_' ) ) {
		return false;
	}

	if ( is_user_logged_in() ) {
		do_action( 'topersia_pagecache_nocache', 'logged-in' );
		return false;
	}

	if ( topersia_pagecache_maintenance() ) {
		do_action( 'topersia_pagecache_nocache', 'maintenance' );
		return false;
	}

	if ( post_password_required( $post_id ) ) {
		do_action( 'topersia_pagecache_nocache', 'password' );
		return false;
	}

	$state['on']   = true;
	$state['self'] = $post_id;
	$state['type'] = $type;

	return true;
}

add_action( 'template_redirect', function () {

	if ( is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || is_feed() || is_embed() || is_preview() || ! is_singular() ) {
		return;
	}

	topersia_pagecache_begin();
}, 1 );

/** مجموعۀ شناسه‌هایی که render دید: `$kind` = shown | walked. */
add_action( 'topersia_render_seen', function ( $ids, $kind = 'shown' ) {

	$state = &topersia_pagecache_state();

	if ( empty( $state['on'] ) ) {
		return;
	}

	$kind = ( 'walked' === $kind ) ? 'walked' : 'shown';

	foreach ( (array) $ids as $one ) {

		$one = absint( $one );

		if ( $one ) {
			$state[ $kind ][ $one ] = $one;
		}
	}
}, 10, 2 );

/**
 * برچسب‌های نهایی این درخواست، با سقف.
 *
 * بیش از سقف (پیش‌فرض ۵۰۰، صافی `topersia_pagecache_tag_cap`): خودِ صفحه و نوعش می‌مانند؛ پیموده‌شده‌ها تا جایی که جا باشد؛
 * و اعضای نشان‌داده‌شده با برچسب نوعشان جایگزین می‌شوند (`tp-type-{نوع}`، یک بار برای هر نوع).
 *
 * @return string[]
 */
function topersia_pagecache_collect_tags( &$overflow = false ) {

	$state    = topersia_pagecache_state();
	$overflow = false;

	if ( empty( $state['on'] ) ) {
		return array();
	}

	$cap    = max( 3, (int) apply_filters( 'topersia_pagecache_tag_cap', 500 ) );
	$self   = $state['self'];
	$base   = array( topersia_pagecache_post_tag( $self ), topersia_pagecache_type_tag( $state['type'] ) );
	$walked = array_diff_key( $state['walked'], array( $self => 1 ) );
	$shown  = array_diff_key( $state['shown'], array( $self => 1 ), $walked );

	$total = count( $base ) + count( $walked ) + count( $shown );

	if ( $total <= $cap ) {
		$tags = $base;
		foreach ( $walked as $id ) { $tags[] = topersia_pagecache_post_tag( $id ); }
		foreach ( $shown as $id ) { $tags[] = topersia_pagecache_post_tag( $id ); }
		return array_values( array_unique( $tags ) );
	}

	$overflow = true;
	$types    = array();

	foreach ( $shown as $id ) {
		$t = (string) get_post_type( $id );
		if ( '' !== $t ) { $types[ topersia_pagecache_type_tag( $t ) ] = true; }
	}

	/* پیموده‌شده‌ها تا جایی که جا باشد؛ بقیه با برچسب نوعشان (که همیشه جا دارد: پیش از آن‌ها می‌آید) */
	$room = max( 0, $cap - count( $base ) - count( $types ) - 4 );
	$kept = array();

	foreach ( $walked as $id ) {
		if ( count( $kept ) < $room ) { $kept[] = topersia_pagecache_post_tag( $id ); continue; }
		$t = (string) get_post_type( $id );
		if ( '' !== $t ) { $types[ topersia_pagecache_type_tag( $t ) ] = true; }
	}

	$tags = array_merge( $base, array_keys( $types ), $kept );

	return array_slice( array_values( array_unique( $tags ) ), 0, $cap );
}

/** پایان رندر: برچسب‌ها اعلام می‌شوند (یک بار). */
add_action( 'wp_footer', function () {

	$state = &topersia_pagecache_state();

	if ( empty( $state['on'] ) || ! empty( $state['last'] ) ) {
		return;
	}

	$overflow = false;
	$tags     = topersia_pagecache_collect_tags( $overflow );

	$state['last'] = $tags;

	do_action( 'topersia_pagecache_tags', $tags, array( 'post' => $state['self'], 'type' => $state['type'], 'overflow' => $overflow ) );
	topersia_pagecache_debug( 'tags #' . $state['self'] . ' ' . count( $tags ) . ( $overflow ? ' (سرریز ← برچسب نوع)' : '' ) );
}, 99999 );

/* ── پاک‌کردن ─────────────────────────────────────────────────────────── */

/** شناسۀ پست و همۀ ترجمه‌هایش (WPML)؛ بی WPML فقط خودش. */
function topersia_pagecache_group_ids( $post_id ) {

	$post_id = absint( $post_id );
	$ids     = array( $post_id => $post_id );
	$type    = $post_id ? (string) get_post_type( $post_id ) : '';

	if ( '' !== $type && has_filter( 'wpml_element_trid' ) ) {

		$trid = apply_filters( 'wpml_element_trid', null, $post_id, 'post_' . $type );
		$tr   = $trid ? apply_filters( 'wpml_get_element_translations', null, $trid, 'post_' . $type ) : array();

		foreach ( (array) $tr as $row ) {
			$one = is_object( $row ) ? ( isset( $row->element_id ) ? absint( $row->element_id ) : 0 ) : ( isset( $row['element_id'] ) ? absint( $row['element_id'] ) : 0 );
			if ( $one ) { $ids[ $one ] = $one; }
		}
	}

	return array_values( $ids );
}

/** همسایه‌های رابطۀ جت‌انجین (والد و فرزند)، با یک پرس‌وجوی آماده. */
function topersia_pagecache_neighbour_ids( $post_id ) {

	global $wpdb;

	$post_id = absint( $post_id );
	$table   = $wpdb->prefix . 'jet_rel_default';

	if ( ! $post_id || $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
		return array();
	}

	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT parent_object_id p, child_object_id c FROM {$table} WHERE parent_object_id = %d OR child_object_id = %d LIMIT 2000", $post_id, $post_id ) );
	$out  = array();

	foreach ( (array) $rows as $r ) {
		foreach ( array( absint( $r->p ), absint( $r->c ) ) as $one ) {
			if ( $one && $one !== $post_id ) { $out[ $one ] = $one; }
		}
	}

	return array_values( $out );
}

/** برچسب‌هایی که با تغییر یک پست پاک می‌شوند: همۀ ترجمه‌ها (و، اگر خواسته شد، همسایه‌های رابطه با ترجمه‌هایشان). */
function topersia_pagecache_purge_tags_for( $post_id, $with_neighbours = false ) {

	$ids = topersia_pagecache_group_ids( $post_id );

	if ( $with_neighbours ) {
		foreach ( topersia_pagecache_neighbour_ids( $post_id ) as $n ) {
			foreach ( topersia_pagecache_group_ids( $n ) as $one ) { $ids[] = $one; }
		}
	}

	$tags = array();

	foreach ( array_unique( $ids ) as $one ) { $tags[] = topersia_pagecache_post_tag( $one ); }

	return $tags;
}

/** صف پاک‌کردن: هر درخواست یک بار، در `shutdown`. */
function topersia_pagecache_queue( array $tags ) {

	static $queue = array(), $armed = false;

	foreach ( $tags as $t ) { $queue[ $t ] = $t; }

	if ( ! $armed ) {
		$armed = true;
		add_action( 'shutdown', function () use ( &$queue ) {
			if ( $queue ) {
				$tags  = array_values( $queue );
				$queue = array();
				do_action( 'topersia_pagecache_purge', $tags );
				topersia_pagecache_debug( 'purge ' . count( $tags ) . ' برچسب' );
			}
		}, 0 );
	}
}

function topersia_pagecache_is_entity_type( $type ) {
	return 0 === strpos( (string) $type, 'tp' );
}

add_action( 'save_post', function ( $post_id, $post = null ) {

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! topersia_pagecache_is_entity_type( get_post_type( $post_id ) ) ) {
		return;
	}

	topersia_pagecache_queue( topersia_pagecache_purge_tags_for( $post_id ) );
}, 99, 2 );

/* انتشار یا خروج از انتشار: فهرست‌های والد هم عوض می‌شود، پس همسایه‌ها هم. */
add_action( 'transition_post_status', function ( $new, $old, $post ) {

	if ( ! is_object( $post ) || ( 'publish' === $new ) === ( 'publish' === $old ) || ! topersia_pagecache_is_entity_type( $post->post_type ) ) {
		return;
	}

	topersia_pagecache_queue( topersia_pagecache_purge_tags_for( $post->ID, true ) );
}, 99, 3 );

add_action( 'before_delete_post', function ( $post_id ) {

	if ( topersia_pagecache_is_entity_type( get_post_type( $post_id ) ) ) {
		topersia_pagecache_queue( topersia_pagecache_purge_tags_for( $post_id, true ) );
	}
}, 99 );

/* رابطۀ جت‌انجین: دو سرِ یال */
$tp_pc_rel = function ( $parent, $child ) {

	foreach ( array( $parent, $child ) as $one ) {
		if ( is_numeric( $one ) && absint( $one ) ) {
			topersia_pagecache_queue( topersia_pagecache_purge_tags_for( absint( $one ) ) );
		}
	}
};

add_action( 'jet-engine/relation/update/after', function ( $parent, $child ) use ( $tp_pc_rel ) { $tp_pc_rel( $parent, $child ); }, 99, 2 );
add_action( 'jet-engine/relation/delete/after', function ( $parent, $child ) use ( $tp_pc_rel ) { $tp_pc_rel( $parent, $child ); }, 99, 2 );

/* ذخیرۀ ترجمۀ WPML */
add_action( 'wpml_pro_translation_completed', function ( $new_post_id ) {

	if ( absint( $new_post_id ) ) {
		topersia_pagecache_queue( topersia_pagecache_purge_tags_for( absint( $new_post_id ) ) );
	}
}, 99 );

/* ── اتصال LiteSpeed Cache — فقط اگر فعال باشد ────────────────────────── */

function topersia_pagecache_litespeed() {
	return defined( 'LSCWP_V' ) || has_action( 'litespeed_tag_add' );
}

add_action( 'topersia_pagecache_tags', function ( $tags ) {

	if ( topersia_pagecache_litespeed() ) {
		foreach ( (array) $tags as $t ) { do_action( 'litespeed_tag_add', $t ); }
	}
} );

add_action( 'topersia_pagecache_purge', function ( $tags ) {

	if ( topersia_pagecache_litespeed() ) {
		foreach ( (array) $tags as $t ) { do_action( 'litespeed_purge', $t ); }
	}
} );

add_action( 'topersia_pagecache_nocache', function ( $reason ) {

	if ( topersia_pagecache_litespeed() ) {
		do_action( 'litespeed_control_set_nocache', 'topersia: ' . $reason );
	}
} );
