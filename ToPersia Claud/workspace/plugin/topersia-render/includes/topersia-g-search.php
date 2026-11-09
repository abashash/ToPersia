<?php
/**
 * جست‌وجوی سراسری ساده — P5-D536 · دستۀ 49 بند ۷.
 *
 * فرم GET (`s`) زیر زیرعنوان هیروی صفحۀ اول و همان فرم در صفحۀ نتیجه.
 * کوئری اصلی جست‌وجو: فقط نوع‌های عمومی `tp_*`، منتشرشده، بی گذرواژه، غیرنمونه
 * (`_tp_sample`)، زبان جاری (فیلتر خودِ WPML روی کوئری اصلی + دروازۀ
 * topersia_g_published در قالب). تطبیق فقط با نمایۀ تاشدۀ هسته
 * (`_tp_search_fold`: عنوان و نام‌های جایگزین) — `tp_fold_only`. نمایه بیرون
 * از local خاموش است (پیوست الف بند ۶)؛ آن‌جا جست‌وجوی پیش‌فرض وردپرس می‌ماند
 * با همان صافی نوع و نمونه.
 *
 * فیلدهای نام: ناحیۀ «names» FIELDMAP (`topersia_g_map()`) و دو فیلد
 * `name_local` و `alternative_names` که در FIELDMAP نیستند (zones.php همین دو را
 * جدا می‌خواند).
 *
 * صفحۀ نتیجه: کاشی‌های موجود (`topersia_g_tiles`)، برچسب نوع، noindex,follow،
 * خالی ← «نتیجه‌ای نیست.».
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_SEARCH_PER_PAGE' ) ) {
	define( 'TOPERSIA_SEARCH_PER_PAGE', 24 );
}

if ( ! function_exists( 'topersia_g_search_name_fields' ) ) {
	/** فیلدهای نام جست‌وجوپذیر: ناحیۀ names در FIELDMAP + نام محلی و نام‌های جایگزین. */
	function topersia_g_search_name_fields() {

		$keys = array();

		foreach ( function_exists( 'topersia_g_map' ) ? (array) topersia_g_map() : array() as $key => $def ) {
			if ( is_array( $def ) && isset( $def['zone'] ) && 'names' === $def['zone'] ) {
				$keys[] = (string) $key;
			}
		}

		return array_values( array_unique( array_merge( $keys, array( 'name_local', 'alternative_names' ) ) ) );
	}
}

if ( ! function_exists( 'topersia_g_search_types' ) ) {
	/** نوع‌های جست‌وجو: هر نوع عمومی tp_* (پیوست و صفحه نه). */
	function topersia_g_search_types() {

		$out = array();

		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
			if ( 0 === strpos( (string) $type, 'tp_' ) ) {
				$out[] = (string) $type;
			}
		}

		sort( $out );

		return $out;
	}
}

if ( ! function_exists( 'topersia_g_search_is' ) ) {
	/** این درخواست صفحۀ نتیجۀ جست‌وجوی سایت است؟ */
	function topersia_g_search_is() {
		return function_exists( 'is_search' ) && is_search() && ! is_admin() && ! is_feed();
	}
}

if ( ! function_exists( 'topersia_g_search_form' ) ) {
	/** فرم GET جست‌وجو — بی JS؛ نشانی خانۀ زبان جاری (WPML). */
	function topersia_g_search_form( $id, $value = '', $class = '' ) {

		$action = (string) apply_filters( 'wpml_home_url', home_url( '/' ) );

		return '<form class="tp-search' . ( '' !== $class ? ' ' . esc_attr( $class ) : '' ) . '" role="search" method="get" action="' . esc_url( $action ) . '">'
			. '<label class="tp-search__lab" for="' . esc_attr( $id ) . '">' . esc_html__( 'جست‌وجو در سایت', 'topersia' ) . '</label>'
			. '<input id="' . esc_attr( $id ) . '" class="tp-search__q" type="search" name="s" value="' . esc_attr( $value ) . '" autocomplete="off" enterkeyhint="search" maxlength="120"'
			. ' placeholder="' . esc_attr__( 'جست‌وجو در استان‌ها، شهرها، جاذبه‌ها…', 'topersia' ) . '">'
			. '<button class="tp-search__go" type="submit" aria-label="' . esc_attr__( 'جست‌وجو', 'topersia' ) . '">' . topersia_g_icon( 'search' ) . '</button></form>';
	}
}

if ( ! function_exists( 'topersia_g_search_tiles' ) ) {
	/** کاشی هر نتیجۀ نمایش‌پذیر: برچسب نوع، نام، پیوند. */
	function topersia_g_search_tiles( $ids ) {

		$tiles = array();

		foreach ( $ids as $id ) {
			$tiles[] = array(
				'label' => topersia_g_kind_label( $id ),
				'name'  => (string) get_the_title( $id ),
				'url'   => (string) get_permalink( $id ),
			);
		}

		return $tiles ? topersia_g_tiles( $tiles, 0 ) : '';
	}
}

if ( function_exists( 'add_action' ) ) {

	/* نمایۀ تاشدۀ هسته: عنوان + فیلدهای نام (P5-D536). */
	add_filter( 'topersia_search_fold_fields', function ( $fields ) {
		return array_values( array_unique( array_merge( (array) $fields, topersia_g_search_name_fields() ) ) );
	} );

	add_action( 'pre_get_posts', function ( $q ) {

		if ( is_admin() || ! $q->is_main_query() || ! $q->is_search() || $q->is_feed() ) {
			return;
		}

		$types = topersia_g_search_types();

		$q->set( 'post_type', $types ? $types : array( 'tp_none' ) );
		$q->set( 'post_status', 'publish' );
		$q->set( 'has_password', false );
		$q->set( 'posts_per_page', TOPERSIA_SEARCH_PER_PAGE );
		$q->set( 'meta_query', array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) );
		$q->set( 'tp_fold_only', 1 );

		/* عبارت تهی (یا فقط فاصله) ← هیچ نتیجه، نه همۀ پست‌ها. */
		if ( '' === trim( (string) $q->get( 's' ) ) ) {
			$q->set( 'post__in', array( 0 ) );
		}
	}, 20 );

	add_filter( 'template_include', function ( $template ) {

		if ( ! topersia_g_search_is() ) {
			return $template;
		}

		$file = topersia_render_path() . 'templates/search-results.php';

		return is_file( $file ) ? $file : $template;
	}, 99 );

	add_action( 'wp_enqueue_scripts', function () {
		if ( topersia_g_search_is() && function_exists( 'topersia_render_url' ) ) {
			wp_enqueue_style( 'topersia-g-search', topersia_render_url() . 'assets/topersia-g-search.css', array( 'topersia-g-template' ), topersia_render_asset_version( 'assets/topersia-g-search.css' ) );
		}
	}, 30 );

	/* نتیجۀ جست‌وجو هرگز نمایه نمی‌شود: noindex, follow — هستۀ وردپرس و Rank Math. */
	add_filter( 'wp_robots', function ( $robots ) {
		if ( topersia_g_search_is() ) {
			unset( $robots['index'], $robots['max-image-preview'] );
			$robots['noindex'] = true;
			if ( empty( $robots['nofollow'] ) ) {
				$robots['follow'] = true;
			}
		}
		return $robots;
	}, 99 );

	add_filter( 'rank_math/frontend/robots', function ( $robots ) {
		if ( topersia_g_search_is() ) {
			$robots['index'] = 'noindex';
			if ( ! isset( $robots['follow'] ) || 'nofollow' !== $robots['follow'] ) {
				$robots['follow'] = 'follow';
			}
		}
		return $robots;
	}, 99 );
}
