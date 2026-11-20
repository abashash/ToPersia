<?php
/**
 * فیلتر مکان (P5-D549 · دستۀ 51؛ دستۀ 55-ب P5-D642: دو فیلد). یک جزء برای /news/، /articles/ و هر هاب.
 *
 *   استان   select بومی `tp_prov` (فقط استان‌های نمایش‌پذیر زبان جاری). با JS نشانی فقط tp_place می‌گیرد (news.js formUrl).
 *   شهر     combobox متنی `tp_loc`، فقط شهر و با استان برگزیده فقط شهرهای همان استان.
 *   REST فقط‌خواندنی  GET topersia/v1/places?q=…&prov=…   (زبان از پیشوند نشانی WPML — rest_url همان زبان صفحه)
 *     پس از ۳ نویسۀ تاخورده (topersia_search_fold: ی/ي · ک/ك · رقم)، حداکثر ۸ شهر، هر کدام با برچسب نوع؛
 *     فقط منتشرشده، هم‌زبان (topersia_g_published) و غیرنمونه (حتی در حالت پیش‌نمایش).
 *   بی JS  GET با tp_prov و tp_loc؛ سرور نام شهر را با همان fold دقیقاً تطبیق می‌دهد (topersia_g_loc_resolve) ← tp_place؛
 *          شهرِ بیرون از استان برگزیده ← خودِ استان (topersia_g_news_filter_place).
 *   با JS  assets/topersia-g-loc.js: role=combobox · aria-expanded/controls/activedescendant · listbox؛
 *          بالا/پایین/Enter/Esc؛ انتخاب ← tp_place پنهان + ارسال فرم (سازوکار بی‌رفرش P5-D546)؛
 *          tp_place پنهان در ناحیۀ `data-tp-news-swap="loc"`؛ تراشۀ حذف‌شدنی در ردیف پالایش‌های فعال (P5-D557).
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

if ( ! function_exists( 'topersia_g_loc_rows' ) ) {
	/**
	 * استان‌ها یا شهرهای نمایش‌پذیر زبان جاری: [ [ id, name, type, fold, alt ], … ] — الفبایی. نمونه (و نمایشی) هرگز.
	 * دستۀ 55-ب (P5-D642): جدا برای هر نوع — select استان فقط استان‌ها را می‌خواهد و هر بار بار صفحه شهرها را نمی‌سازد.
	 *
	 * @param string $type tp_province | tp_city
	 */
	function topersia_g_loc_rows( $type ) {
		static $memo = array();
		$lang = function_exists( 'topersia_g_page_lang' ) ? topersia_g_page_lang() : 'fa';
		if ( isset( $memo[ $lang ][ $type ] ) ) {
			return $memo[ $lang ][ $type ];
		}
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
		return $memo[ $lang ][ $type ] = $rows;
	}
}

if ( ! function_exists( 'topersia_g_loc_index' ) ) {
	/** همۀ استان‌ها و شهرهای نمایش‌پذیر زبان جاری — استان‌ها اول، هر گروه الفبایی. */
	function topersia_g_loc_index() {
		return array_merge( topersia_g_loc_rows( 'tp_province' ), topersia_g_loc_rows( 'tp_city' ) );
	}
}

if ( ! function_exists( 'topersia_g_loc_prov_of' ) ) {
	/** استانِ یک مکان: خودِ استان، یا نخستین استان زنجیرۀ نیای R01 (همان BreadcrumbList)؛ نبود ← ۰. */
	function topersia_g_loc_prov_of( $id ) {
		$id = absint( $id );
		if ( ! $id ) {
			return 0;
		}
		if ( 'tp_province' === get_post_type( $id ) ) {
			return $id;
		}
		foreach ( function_exists( 'topersia_g_crumb_chain' ) ? (array) topersia_g_crumb_chain( $id ) : array() as $up ) {
			if ( 'tp_province' === get_post_type( $up ) ) {
				return (int) $up;
			}
		}
		return 0;
	}
}

if ( ! function_exists( 'topersia_g_loc_search' ) ) {
	/**
	 * نتیجۀ combobox شهر — زیر ۳ نویسۀ تاخورده تهی؛ حداکثر ۸؛ فقط شهر (P5-D642).
	 *
	 * @param string $q    متن
	 * @param int    $prov استان برگزیده یا ۰ — فقط شهرهای همان استان
	 */
	function topersia_g_loc_search( $q, $prov = 0 ) {
		$f = topersia_g_loc_fold( $q );
		if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $f, 'UTF-8' ) : strlen( $f ) ) < TOPERSIA_LOC_MIN ) {
			return array();
		}
		$prov = absint( $prov );
		$hit  = array();
		foreach ( topersia_g_loc_rows( 'tp_city' ) as $row ) {
			if ( false === strpos( $row['fold'], $f ) && false === strpos( $row['alt'], $f ) ) {
				continue;
			}
			if ( $prov && topersia_g_loc_prov_of( $row['id'] ) !== $prov ) {
				continue;
			}
			$hit[] = array(
				'id'   => $row['id'],
				'name' => $row['name'],
				'type' => function_exists( 'topersia_g_kind_label' ) ? topersia_g_kind_label( $row['id'] ) : $row['type'],
			);
			if ( count( $hit ) >= TOPERSIA_LOC_MAX ) {
				break;
			}
		}
		return $hit;
	}
}

if ( ! function_exists( 'topersia_g_loc_resolve' ) ) {
	/**
	 * بی JS: نام دقیق (تاخورده) در فیلد شهر ← شناسه. شهر اول (فیلد «شهر» است؛ «اصفهان» یعنی شهر)، با استان برگزیده فقط شهرهای همان؛
	 * بی استان، اگر شهری نبود، استان هم‌نام (پیوند کهنۀ «استان یا شهر»). نبود ← ۰.
	 */
	function topersia_g_loc_resolve( $text, $prov = 0 ) {
		$f    = topersia_g_loc_fold( $text );
		$prov = absint( $prov );
		if ( '' === $f ) {
			return 0;
		}
		foreach ( topersia_g_loc_rows( 'tp_city' ) as $row ) {
			if ( ( $row['fold'] === $f || $row['alt'] === $f ) && ( ! $prov || topersia_g_loc_prov_of( $row['id'] ) === $prov ) ) {
				return (int) $row['id'];
			}
		}
		if ( ! $prov ) {
			foreach ( topersia_g_loc_rows( 'tp_province' ) as $row ) {
				if ( $row['fold'] === $f || $row['alt'] === $f ) {
					return (int) $row['id'];
				}
			}
		}
		return 0;
	}
}

if ( ! function_exists( 'topersia_g_loc_prov_param' ) ) {
	/** استانِ select (`tp_prov`، فقط بی JS در نشانی — با JS همان tp_place می‌شود): شناسۀ استان نمایش‌پذیر یا ۰. */
	function topersia_g_loc_prov_param() {
		$id = absint( function_exists( 'get_query_var' ) && is_scalar( get_query_var( 'tp_prov' ) ) ? get_query_var( 'tp_prov' ) : 0 );
		if ( ! $id || 'tp_province' !== get_post_type( $id ) ) {
			return 0;
		}
		return topersia_g_published( array( $id ) ) ? $id : 0;
	}
}

if ( ! function_exists( 'topersia_g_loc_rest' ) ) {
	function topersia_g_loc_rest( $request ) {
		$q    = sanitize_text_field( (string) $request->get_param( 'q' ) );
		$prov = absint( $request->get_param( 'prov' ) );
		if ( $prov && ( 'tp_province' !== get_post_type( $prov ) || ! topersia_g_published( array( $prov ) ) ) ) {
			$prov = 0;
		}
		$res = rest_ensure_response( array( 'items' => topersia_g_loc_search( mb_substr( $q, 0, 60 ), $prov ) ) );
		$res->header( 'Cache-Control', 'public, max-age=300' );
		return $res;
	}
}

if ( ! function_exists( 'topersia_g_loc_field' ) ) {
	/**
	 * دو فیلد درون فرم پالایش (دستۀ 55-ب · P5-D642): استان select بومی (`tp_prov`) و شهر combobox (`tp_loc`) فقط شهرهای همان استان.
	 * فیلد شهر پس از انتخاب خالی و هم‌اندازه می‌ماند (P5-D557): تراشۀ مکان و «پیدا نشد» در ردیف پالایش‌های فعال‌اند.
	 * ناحیۀ swap `loc` (از پاسخ سرور جای‌گزین می‌شود): tp_place پنهان فقط برای شهر، و نشان استانِ مکان جاری
	 * (`data-tp-loc-prov`) که news.js پس از هر بار select را با آن هم‌گام می‌کند — نشانی فقط tp_place می‌گیرد.
	 *
	 * @param string $id    شناسۀ input شهر (پیشوند tp-)؛ select استان `{id}-prov`
	 * @param int    $place مکان انتخاب‌شده (tp_place) یا ۰
	 */
	function topersia_g_loc_field( $id, $place ) {
		$list  = $id . '-list';
		$hint  = $id . '-hint';
		$sid   = $id . '-prov';
		$place = absint( $place );
		$prov  = topersia_g_loc_prov_of( $place );
		$keep  = ( $place && 'tp_city' === get_post_type( $place ) ) ? '<input type="hidden" name="tp_place" value="' . esc_attr( (string) $place ) . '">' : '';
		$keep .= '<span hidden data-tp-loc-prov="' . esc_attr( $prov ? (string) $prov : '' ) . '"></span>';

		$o = '<option value="">' . esc_html__( 'همۀ استان‌ها', 'topersia' ) . '</option>';
		foreach ( topersia_g_loc_rows( 'tp_province' ) as $row ) {
			$o .= '<option value="' . esc_attr( (string) $row['id'] ) . '"' . selected( $prov, (int) $row['id'], false ) . '>' . esc_html( $row['name'] ) . '</option>';
		}

		return '<div class="tp-news-filter__f tp-loc-prov"><label for="' . esc_attr( $sid ) . '">' . esc_html__( 'استان', 'topersia' ) . '</label>'
			. '<span class="tp-news-sel"><select id="' . esc_attr( $sid ) . '" name="tp_prov" data-tp-loc-prov-sel>' . $o . '</select></span></div>'
			. '<div class="tp-news-filter__f tp-loc" data-tp-loc data-tp-loc-api="' . esc_url( rest_url( 'topersia/v1/places' ) ) . '"'
			. ' data-tp-loc-none="' . esc_attr__( 'موردی پیدا نشد', 'topersia' ) . '"'
			. ' data-tp-loc-count="' . esc_attr__( '%s نتیجه', 'topersia' ) . '">'
			. '<label for="' . esc_attr( $id ) . '">' . esc_html__( 'شهر', 'topersia' ) . '</label>'
			. '<div class="tp-loc__box">'
			. '<input id="' . esc_attr( $id ) . '" class="tp-loc__in" type="text" name="tp_loc" value="" maxlength="60" autocomplete="off" spellcheck="false"'
			. ' role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="' . esc_attr( $list ) . '" aria-describedby="' . esc_attr( $hint ) . '"'
			. ' placeholder="' . esc_attr__( 'نام شهر', 'topersia' ) . '">'
			. '<ul id="' . esc_attr( $list ) . '" class="tp-loc__list" role="listbox" aria-label="' . esc_attr__( 'شهر', 'topersia' ) . '" hidden></ul>'
			. '</div>'
			. '<p id="' . esc_attr( $hint ) . '" class="tp-news-vh">' . esc_html__( 'دست‌کم سه نویسه بنویسید؛ با کلیدهای بالا و پایین انتخاب کنید.', 'topersia' ) . '</p>'
			. '<p class="tp-news-vh" role="status" aria-live="polite" data-tp-loc-live></p>'
			. '<div class="tp-loc__keep" data-tp-news-swap="loc">' . $keep . '</div>'
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
				'q'    => array( 'required' => true, 'type' => 'string' ),
				'prov' => array( 'required' => false, 'type' => 'integer', 'default' => 0 ),   /* P5-D642: فقط شهرهای همین استان */
			),
		) );
	} );
}
