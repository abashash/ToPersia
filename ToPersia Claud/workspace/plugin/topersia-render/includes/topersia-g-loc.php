<?php
/**
 * فیلتر مکان — combobox متنی (P5-D549 · دستۀ 51). یک جزء برای /news/ و هر هاب.
 *
 *   REST فقط‌خواندنی  GET topersia/v1/places?q=…   (زبان از پیشوند نشانی WPML — rest_url همان زبان صفحه)
 *     پس از ۳ نویسۀ تاخورده (topersia_search_fold: ی/ي · ک/ك · رقم)، حداکثر ۸ نتیجه، استان‌ها پیش از شهرها،
 *     هر کدام با برچسب نوع؛ فقط منتشرشده، هم‌زبان (topersia_g_published) و غیرنمونه (حتی در حالت پیش‌نمایش).
 *   بی JS  فیلد متنی `tp_loc` با GET؛ سرور با همان fold نام را دقیقاً تطبیق می‌دهد (topersia_g_loc_resolve) ← tp_place.
 *   با JS  assets/topersia-g-loc.js: role=combobox · aria-expanded/controls/activedescendant · listbox؛
 *          بالا/پایین/Enter/Esc؛ انتخاب ← tp_place پنهان + ارسال فرم (سازوکار بی‌رفرش P5-D546)؛
 *          تراشۀ حذف‌شدنی و tp_place پنهان در ناحیۀ `data-tp-news-swap="loc"` که با هر بار جای‌گزین می‌شود.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_LOC_MIN' ) ) {
	define( 'TOPERSIA_LOC_MIN', 3 );
}
if ( ! defined( 'TOPERSIA_LOC_MAX' ) ) {
	define( 'TOPERSIA_LOC_MAX', 8 );
}

if ( ! function_exists( 'topersia_g_loc_fold' ) ) {
	/** تاکردن برای تطبیق — یک قاعده از هسته (P5-D265)؛ بی هسته فقط کوچک‌حرف و فاصله. */
	function topersia_g_loc_fold( $text ) {
		$text = trim( (string) preg_replace( '/\s+/u', ' ', (string) $text ) );
		if ( function_exists( 'topersia_search_fold' ) ) {
			return trim( (string) topersia_search_fold( $text ) );
		}
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	}
}

if ( ! function_exists( 'topersia_g_loc_index' ) ) {
	/**
	 * همۀ استان‌ها و شهرهای نمایش‌پذیر زبان جاری: [ [ id, name, type, fold ], … ] — استان‌ها اول، هر گروه الفبایی.
	 * نمونه (و نمایشی) هرگز.
	 */
	function topersia_g_loc_index() {
		static $memo = array();
		$lang = function_exists( 'topersia_g_page_lang' ) ? topersia_g_page_lang() : 'fa';
		if ( isset( $memo[ $lang ] ) ) {
			return $memo[ $lang ];
		}
		$out = array();
		foreach ( array( 'tp_province', 'tp_city' ) as $type ) {
			$ids = get_posts( array(
				'post_type'        => $type,
				'post_status'      => 'publish',
				'has_password'     => false,
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_query'       => array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ),
			) );
			$rows = array();
			foreach ( topersia_g_published( array_map( 'absint', (array) $ids ) ) as $id ) {
				$name = function_exists( 'topersia_g_name' ) ? topersia_g_name( $id ) : (string) get_the_title( $id );
				if ( '' === $name ) {
					continue;
				}
				$rows[] = array( 'id' => $id, 'name' => $name, 'type' => $type, 'fold' => topersia_g_loc_fold( $name ), 'alt' => topersia_g_loc_fold( (string) get_the_title( $id ) ) );
			}
			usort( $rows, function ( $a, $b ) { return strcmp( $a['fold'], $b['fold'] ); } );
			$out = array_merge( $out, $rows );
		}
		return $memo[ $lang ] = $out;
	}
}

if ( ! function_exists( 'topersia_g_loc_search' ) ) {
	/** نتیجۀ جست‌وجو — زیر ۳ نویسۀ تاخورده تهی؛ حداکثر ۸؛ ترتیب نمایه (استان‌ها اول). */
	function topersia_g_loc_search( $q ) {
		$f = topersia_g_loc_fold( $q );
		if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $f, 'UTF-8' ) : strlen( $f ) ) < TOPERSIA_LOC_MIN ) {
			return array();
		}
		$hit = array();
		foreach ( topersia_g_loc_index() as $row ) {
			if ( false !== strpos( $row['fold'], $f ) || false !== strpos( $row['alt'], $f ) ) {
				$hit[] = array(
					'id'   => $row['id'],
					'name' => $row['name'],
					'type' => function_exists( 'topersia_g_kind_label' ) ? topersia_g_kind_label( $row['id'] ) : $row['type'],
				);
				if ( count( $hit ) >= TOPERSIA_LOC_MAX ) {
					break;
				}
			}
		}
		return $hit;
	}
}

if ( ! function_exists( 'topersia_g_loc_resolve' ) ) {
	/** بی JS: نام دقیق (تاخورده) ← شناسه؛ استان پیش از شهر هم‌نام. نبود ← ۰. */
	function topersia_g_loc_resolve( $text ) {
		$f = topersia_g_loc_fold( $text );
		if ( '' === $f ) {
			return 0;
		}
		foreach ( topersia_g_loc_index() as $row ) {
			if ( $row['fold'] === $f || $row['alt'] === $f ) {
				return (int) $row['id'];
			}
		}
		return 0;
	}
}

if ( ! function_exists( 'topersia_g_loc_rest' ) ) {
	function topersia_g_loc_rest( $request ) {
		$q   = sanitize_text_field( (string) $request->get_param( 'q' ) );
		$res = rest_ensure_response( array( 'items' => topersia_g_loc_search( mb_substr( $q, 0, 60 ) ) ) );
		$res->header( 'Cache-Control', 'public, max-age=300' );
		return $res;
	}
}

if ( ! function_exists( 'topersia_g_loc_field' ) ) {
	/**
	 * نشانه‌گذاری combobox درون فرم پالایش.
	 *
	 * @param string $id       شناسۀ input (پیشوند tp-)
	 * @param int    $place    مکان انتخاب‌شده (tp_place) یا ۰
	 * @param string $remove   نشانی همین صفحه بی پالایش مکان (پیوند تراشه)
	 * @param string $missed   متن tp_loc که پیدا نشد ('' یعنی هیچ)
	 */
	function topersia_g_loc_field( $id, $place, $remove, $missed = '' ) {
		$list  = $id . '-list';
		$hint  = $id . '-hint';
		$name  = $place ? ( function_exists( 'topersia_g_name' ) ? topersia_g_name( $place ) : (string) get_the_title( $place ) ) : '';
		$chip  = $place
			? '<a class="tp-loc__chip" href="' . esc_url( $remove ) . '" data-tp-news-nav aria-label="' . esc_attr( sprintf( /* translators: %s: نام مکان */ __( 'حذف پالایش مکان: %s', 'topersia' ), $name ) ) . '">'
				. '<span>' . esc_html( $name ) . '</span><span class="tp-loc__x" aria-hidden="true">×</span></a>'
				. '<input type="hidden" name="tp_place" value="' . esc_attr( (string) $place ) . '">'
			: '';
		$miss  = '' !== $missed ? '<p class="tp-loc__miss" role="status">' . esc_html( sprintf( /* translators: %s: متن کاربر */ __( 'مکانی با نام «%s» پیدا نشد.', 'topersia' ), $missed ) ) . '</p>' : '';

		return '<div class="tp-news-filter__f tp-loc" data-tp-loc data-tp-loc-api="' . esc_url( rest_url( 'topersia/v1/places' ) ) . '"'
			. ' data-tp-loc-none="' . esc_attr__( 'موردی پیدا نشد', 'topersia' ) . '"'
			. ' data-tp-loc-count="' . esc_attr__( '%s نتیجه', 'topersia' ) . '">'
			. '<label for="' . esc_attr( $id ) . '">' . esc_html__( 'استان یا شهر', 'topersia' ) . '</label>'
			. '<div class="tp-loc__box">'
			. '<input id="' . esc_attr( $id ) . '" class="tp-loc__in" type="text" name="tp_loc" value="" maxlength="60" autocomplete="off" spellcheck="false"'
			. ' role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="' . esc_attr( $list ) . '" aria-describedby="' . esc_attr( $hint ) . '"'
			. ' placeholder="' . esc_attr__( 'نام استان یا شهر', 'topersia' ) . '">'
			. '<ul id="' . esc_attr( $list ) . '" class="tp-loc__list" role="listbox" aria-label="' . esc_attr__( 'استان یا شهر', 'topersia' ) . '" hidden></ul>'
			. '</div>'
			. '<p id="' . esc_attr( $hint ) . '" class="tp-news-vh">' . esc_html__( 'دست‌کم سه نویسه بنویسید؛ با کلیدهای بالا و پایین انتخاب کنید.', 'topersia' ) . '</p>'
			. '<p class="tp-news-vh" role="status" aria-live="polite" data-tp-loc-live></p>'
			. '<div class="tp-loc__chips" data-tp-news-swap="loc">' . $chip . $miss . '</div>'
			. '</div>';
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'rest_api_init', function () {
		register_rest_route( 'topersia/v1', '/places', array(
			'methods'             => 'GET',
			'callback'            => 'topersia_g_loc_rest',
			'permission_callback' => '__return_true',
			'args'                => array(
				'q' => array( 'required' => true, 'type' => 'string' ),
			),
		) );
	} );
}
