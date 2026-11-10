<?php
/**
 * برچسب مشترک محتوا (tpc_tag، ثبت در هسته) — نمایش — دستۀ 51 الحاقیۀ ث · P5-D555
 *
 *   topersia_g_tags_html( $pid )     بلوک «برچسب‌ها» پایان کارت منابع هر صفحۀ موجودیت؛ بی برچسب ← هیچ
 *   topersia_g_tag_experiences()     واژه‌های tp_is_experience با دست‌کم TOPERSIA_TAG_MIN_ITEMS مورد دیدنی (صفحۀ اول)
 *
 * «دیدنی» = منتشرشده از دروازۀ topersia_g_published و نمونۀ پنهان نه (P5-D247 · P5-D548).
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_TAG_MIN_ITEMS' ) ) {
	define( 'TOPERSIA_TAG_MIN_ITEMS', 2 );   /* کمتر ← کارت تجربه ساخته نمی‌شود */
}

/** شناسه‌های دیدنی یک واژه به زبان جاری. */
if ( ! function_exists( 'topersia_g_tag_items' ) ) {
	function topersia_g_tag_items( $term_id ) {
		static $memo = array();
		$term_id = (int) $term_id;
		if ( isset( $memo[ $term_id ] ) ) {
			return $memo[ $term_id ];
		}
		$ids = get_posts( array(
			'post_type'        => 'any',
			'post_status'      => 'publish',
			'numberposts'      => 200,
			'fields'           => 'ids',
			'suppress_filters' => false,
			'no_found_rows'    => true,
			'tax_query'        => array( array( 'taxonomy' => 'tpc_tag', 'field' => 'term_id', 'terms' => $term_id ) ),
		) );
		$ids = topersia_g_published( array_map( 'absint', $ids ) );
		$ids = array_values( array_filter( $ids, function ( $id ) {
			return ! topersia_g_sample_hidden( $id );
		} ) );
		return $memo[ $term_id ] = $ids;
	}
}

/** بلوک «برچسب‌ها» — فهرست پیوند به /tags/{slug}/ به ترتیب tp_sort. */
if ( ! function_exists( 'topersia_g_tags_html' ) ) {
	function topersia_g_tags_html( $pid ) {

		if ( ! function_exists( 'taxonomy_exists' ) || ! taxonomy_exists( 'tpc_tag' ) ) {
			return '';
		}
		$terms = wp_get_post_terms( (int) $pid, 'tpc_tag' );
		if ( is_wp_error( $terms ) || ! $terms ) {
			return '';
		}
		usort( $terms, function ( $a, $b ) {
			return (int) get_term_meta( $a->term_id, 'tp_sort', true ) - (int) get_term_meta( $b->term_id, 'tp_sort', true );
		} );

		$li = '';
		foreach ( $terms as $t ) {
			$url = get_term_link( $t );
			if ( is_wp_error( $url ) ) {
				continue;
			}
			$li .= '<li><a class="tp-tags__a" href="' . esc_url( $url ) . '">' . esc_html( $t->name ) . '</a></li>';
		}
		if ( '' === $li ) {
			return '';
		}

		wp_enqueue_style( 'topersia-g-tags', topersia_render_url() . 'assets/topersia-g-tags.css', array( 'topersia-g-template' ), topersia_render_asset_version( 'assets/topersia-g-tags.css' ) );

		return '<nav class="tp-tags" aria-labelledby="tp-tags-h"><h3 id="tp-tags-h" class="tp-tags__h">' . esc_html__( 'برچسب‌ها', 'topersia' ) . '</h3>'
			. '<ul class="tp-tags__list">' . $li . '</ul></nav>';
	}
}

/**
 * کارت‌های «کارها و تجربیات» — [ نام, نشانی, آیکون, شمار, ترتیب ]؛ فقط واژه‌های با دست‌کم TOPERSIA_TAG_MIN_ITEMS مورد دیدنی.
 *
 * @return array<int,array>
 */
if ( ! function_exists( 'topersia_g_tag_experiences' ) ) {
	function topersia_g_tag_experiences() {

		if ( ! function_exists( 'taxonomy_exists' ) || ! taxonomy_exists( 'tpc_tag' ) ) {
			return array();
		}
		$terms = get_terms( array(
			'taxonomy'   => 'tpc_tag',
			'hide_empty' => true,
			'meta_query' => array( array( 'key' => 'tp_is_experience', 'value' => '1' ) ),
		) );
		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$out = array();
		foreach ( $terms as $t ) {
			$n = count( topersia_g_tag_items( $t->term_id ) );
			if ( $n < TOPERSIA_TAG_MIN_ITEMS ) {
				continue;
			}
			$url = get_term_link( $t );
			if ( is_wp_error( $url ) ) {
				continue;
			}
			$out[] = array( (string) $t->name, (string) $url, (string) get_term_meta( $t->term_id, 'tp_icon', true ), $n, (int) get_term_meta( $t->term_id, 'tp_sort', true ) );
		}
		usort( $out, function ( $a, $b ) {
			return $a[4] - $b[4];
		} );
		return $out;
	}
}
