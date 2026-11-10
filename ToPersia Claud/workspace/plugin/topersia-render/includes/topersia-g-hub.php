<?php
/**
 * هاب عمومی هر نوع — دستۀ 51 بخش ۴ · الحاقیۀ ج (P5-D555 · P5-D557 · P5-D559 · P5-D560)
 *
 * یک ماژول پیکربندی‌محور: سطرهای `topersia_g_hub_registry()` از ماتریس نوع (`topersia_g_type_matrix()`)
 * و FIELDMAP (`topersia_g_map()`) با فیلدهای زندۀ جت‌انجین (`topersia_pack_schema()`) ساخته می‌شوند —
 * نوع تازه = یک سطر ماتریس؛ این‌جا نام فیلدی نوشته نمی‌شود.
 *
 *   نشانی     آرشیو نوع روی همان rewrite base خودش (`has_archive` = نامک موجود؛ نشانی صفحۀ تکی دست نمی‌خورد):
 *             /province/ · /en/province/ · /ar/province/ · /province/page/2/
 *   برچسب     /tags/{slug}/ — حالت تاکسونومی همین هاب (tpc_tag، P5-D555)؛ همۀ نوع‌ها، تراشه = نوع محتوا
 *   فصل       /season/{spring|summer|autumn|winter}/ — «حالت فصل» همین هاب (P5-D564): همۀ نوع‌های دارای best_season که آن فصل
 *             (یا «تمام سال») را دارند؛ تراشه = نوع محتوا. پایه و نامک از topersia_g_season_slugs() (ترجمه‌پذیر با صافی)
 *   اجزا      سربرگ تمام‌عرض وسط‌چین با %s؛ نوار ابزار فقط تراشه‌های یک‌خطی + مرتب‌سازی + «نمایش» + شبکه/فهرست —
 *             بی ردیف فیلتر (P5-D566)؛ کاشی، صفحه‌بندی، بی‌رفرش (topersia-g-news.js)
 *   سئو       زیر ۳ مورد یا پالایش‌شده ← noindex و canonical بی‌پالایش؛ CollectionPage + ItemList + BreadcrumbList (topersia-schema)
 *   داده      فقط منتشرشده، زبان جاری، غیرنمونه (`topersia_g_published()` · `topersia_g_sample_mq()`)؛ نمایشی فقط در حالت پیش‌نمایش
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_HUB_PER_PAGE' ) ) {
	define( 'TOPERSIA_HUB_PER_PAGE', 24 );
}
if ( ! defined( 'TOPERSIA_HUB_MIN_INDEX' ) ) {
	define( 'TOPERSIA_HUB_MIN_INDEX', 3 );   /* کمتر از این ← noindex */
}

/** فیلدهای گزینه‌ای که درباره‌ی بازدیدند، نه گونۀ موجودیت — فاست نمی‌شوند. فصل جدا (پالایش فصل). */
if ( ! function_exists( 'topersia_g_hub_facet_skip' ) ) {
	function topersia_g_hub_facet_skip() {
		return array( 'best_season', 'season', 'wheelchair_access', 'best_time_of_day', 'dress_requirement', 'photo_allowed', 'photography_policy', 'suggested_duration' );
	}
}

/**
 * عنوان هاب هر نوع (جمع، %s = نام مکان) — رشتۀ کاتالوگ. نوع بی سطر ← نام نوع.
 *
 * @return array نوع ← msgid
 */
if ( ! function_exists( 'topersia_g_hub_titles' ) ) {
	function topersia_g_hub_titles() {
		return array(
			'tp_country'          => __( 'کشورها', 'topersia' ),
			'tp_region'           => __( 'مناطق جغرافیایی %s', 'topersia' ),
			'tp_province'         => __( 'استان‌های %s', 'topersia' ),
			'tp_city'             => __( 'شهرهای %s', 'topersia' ),
			'tp_district'         => __( 'محله‌های %s', 'topersia' ),
			'tp_village'          => __( 'روستاهای %s', 'topersia' ),
			'tp_island'           => __( 'جزیره‌های %s', 'topersia' ),
			'tp_mountain'         => __( 'کوه‌های %s', 'topersia' ),
			'tp_water_body'       => __( 'پهنه‌های آبی %s', 'topersia' ),
			'tp_desert'           => __( 'کویرهای %s', 'topersia' ),
			'tp_forest_plain'     => __( 'جنگل‌ها و دشت‌های %s', 'topersia' ),
			'tp_border_crossing'  => __( 'گذرگاه‌های مرزی %s', 'topersia' ),
			'tp_monument'         => __( 'بناهای تاریخی %s', 'topersia' ),
			'tp_religious_site'   => __( 'مکان‌های مذهبی %s', 'topersia' ),
			'tp_ancient_site'     => __( 'محوطه‌های باستانی %s', 'topersia' ),
			'tp_natural_feature'  => __( 'عارضه‌های طبیعی %s', 'topersia' ),
			'tp_museum'           => __( 'موزه‌های %s', 'topersia' ),
			'tp_protected_area'   => __( 'پارک‌های ملی و مناطق حفاظت‌شدۀ %s', 'topersia' ),
			'tp_landscape'        => __( 'منظرهای فرهنگی %s', 'topersia' ),
			'tp_handicraft'       => __( 'صنایع دستی %s', 'topersia' ),
			'tp_local_food'       => __( 'غذاهای محلی %s', 'topersia' ),
			'tp_costume'          => __( 'لباس‌های سنتی %s', 'topersia' ),
			'tp_music'            => __( 'موسیقی محلی %s', 'topersia' ),
			'tp_dance'            => __( 'رقص‌های محلی %s', 'topersia' ),
			'tp_language'         => __( 'زبان‌ها و گویش‌های %s', 'topersia' ),
			'tp_ethnic_group'     => __( 'اقوام و فرهنگ‌های %s', 'topersia' ),
			'tp_ritual'           => __( 'آیین‌ها و مراسم سنتی %s', 'topersia' ),
			'tp_figure'           => __( 'شخصیت‌های تاریخی %s', 'topersia' ),
			'tp_historical_era'   => __( 'دوره‌ها و سلسله‌های تاریخی %s', 'topersia' ),
			'tp_designation'      => __( 'ثبت‌ها و تعیین‌های رسمی %s', 'topersia' ),
			'tp_route'            => __( 'مسیرهای گردشگری %s', 'topersia' ),
			'tp_visa'             => __( 'ویزا و شرایط ورود به %s', 'topersia' ),
			'tp_currency'         => __( 'ارز و روش‌های پرداخت در %s', 'topersia' ),
			'tp_health_safety'    => __( 'سلامت و ایمنی در %s', 'topersia' ),
			'tp_travel_etiquette' => __( 'فرهنگ سفر و آداب %s', 'topersia' ),
			'tp_calendar'         => __( 'تقویم و مناسبت‌های رسمی %s', 'topersia' ),
			'tp_transport'        => __( 'حمل‌ونقل عمومی %s', 'topersia' ),
			'tp_festival'         => __( 'جشنواره‌ها و رویدادهای %s', 'topersia' ),
			'tp_sports_event'     => __( 'رویدادهای ورزشی %s', 'topersia' ),
			'tp_religious_event'  => __( 'مراسم مذهبی عمومی %s', 'topersia' ),
			'tp_seasonal_event'   => __( 'رویدادهای طبیعی و فصلی %s', 'topersia' ),
		);
	}
}

/**
 * REGISTRY — یک سطر برای هر نوع عمومی دارای صفحه. همه از دادۀ زنده:
 *   slug    rewrite base موجود نوع (نشانی هاب)
 *   facet   نخستین فیلد گزینه‌ای نوع (بیرون از topersia_g_hub_facet_skip) — تراشه‌ها؛ نبود ← بی تراشه
 *   season  'best_season' اگر نوع آن را دارد — پالایش ?season=
 *   metric  نخستین فیلد عددی — ترتیب «بیشترین …» و مقدار جلوی نام (P5-D466)
 *   loc     پالایش مکان (combobox) — نه برای کشور، منطقه و استان
 *
 * @return array نوع ← سطر
 */
if ( ! function_exists( 'topersia_g_hub_registry' ) ) {
	function topersia_g_hub_registry() {

		static $rows = null;

		if ( null !== $rows ) {
			return $rows;
		}

		$rows = array();
		$map  = function_exists( 'topersia_g_map' ) ? topersia_g_map() : array();
		$skip = topersia_g_hub_facet_skip();

		foreach ( array_keys( topersia_g_type_matrix() ) as $type ) {

			$obj = function_exists( 'get_post_type_object' ) ? get_post_type_object( $type ) : null;

			if ( ! $obj || empty( $obj->public ) || empty( $obj->rewrite['slug'] ) ) {
				continue;
			}

			$def    = function_exists( 'topersia_pack_schema' ) ? topersia_pack_schema( $type ) : array();
			$fields = array_keys( (array) ( isset( $def['fields'] ) ? $def['fields'] : array() ) );
			$row    = array( 'slug' => trim( (string) $obj->rewrite['slug'], '/' ), 'facet' => '', 'season' => '', 'metric' => '', 'loc' => ! in_array( $type, array( 'tp_country', 'tp_region', 'tp_province' ), true ) );

			foreach ( $fields as $f ) {
				$kind = isset( $map[ $f ]['kind'] ) ? $map[ $f ]['kind'] : '';
				if ( 'choice' === $kind && '' === $row['facet'] && ! in_array( $f, $skip, true ) ) {
					$row['facet'] = $f;
				}
				if ( 'best_season' === $f ) {
					$row['season'] = $f;
				}
				if ( 'number' === $kind && '' === $row['metric'] ) {
					$row['metric'] = $f;
				}
			}

			$rows[ $type ] = $row;
		}

		$rows = function_exists( 'apply_filters' ) ? (array) apply_filters( 'topersia_g_hub_registry', $rows ) : $rows;

		return $rows;
	}
}

/**
 * پایه و نامک صفحه‌های فصل به زبان صفحه — کلید «base» و چهار کلید فصل. پیش‌فرض همه‌جا لاتین؛
 * ترجمه با صافی `topersia_g_season_slugs` (زبان ← آرایه) بی دست‌زدن به قاعدۀ بازنویسی (قاعده هر نامک را می‌پذیرد).
 *
 * @return array زبان ← [ base, spring, summer, autumn, winter ]
 */
if ( ! function_exists( 'topersia_g_season_slugs' ) ) {
	function topersia_g_season_slugs() {
		$one = array( 'base' => 'season', 'spring' => 'spring', 'summer' => 'summer', 'autumn' => 'autumn', 'winter' => 'winter' );
		$all = array( 'fa' => $one, 'en' => $one, 'ar' => $one );
		return function_exists( 'apply_filters' ) ? (array) apply_filters( 'topersia_g_season_slugs', $all ) : $all;
	}
}

/** نشانی صفحۀ یک فصل به زبان جاری، صفحۀ N. */
if ( ! function_exists( 'topersia_g_season_url' ) ) {
	function topersia_g_season_url( $season, $page = 1 ) {
		$all  = topersia_g_season_slugs();
		$lang = function_exists( 'topersia_g_page_lang' ) ? (string) topersia_g_page_lang() : 'fa';
		$map  = isset( $all[ $lang ] ) ? $all[ $lang ] : reset( $all );
		if ( empty( $map[ $season ] ) || 'base' === $season ) {
			return '';
		}
		return home_url( user_trailingslashit( $map['base'] . '/' . $map[ $season ] . ( $page > 1 ? '/page/' . (int) $page : '' ) ) );
	}
}

/** فصل هاب جاری («حالت فصل») یا تهی — نامک نشانی به کلید فصل، در هر زبان. */
if ( ! function_exists( 'topersia_g_hub_season' ) ) {
	function topersia_g_hub_season() {
		$slug = function_exists( 'get_query_var' ) && is_scalar( get_query_var( 'tp_season' ) ) ? sanitize_title( (string) get_query_var( 'tp_season' ) ) : '';
		if ( '' === $slug || ( function_exists( 'is_feed' ) && is_feed() ) ) {
			return '';
		}
		foreach ( topersia_g_season_slugs() as $map ) {
			$key = array_search( $slug, (array) $map, true );
			if ( false !== $key && 'base' !== $key ) {
				return (string) $key;
			}
		}
		return '';
	}
}

/** نوع هاب جاری (آرشیو نوع) یا تهی. */
if ( ! function_exists( 'topersia_g_hub_type' ) ) {
	function topersia_g_hub_type() {
		if ( ! function_exists( 'is_post_type_archive' ) || is_feed() ) {
			return '';
		}
		if ( '' !== topersia_g_hub_season() ) {
			return '';
		}
		foreach ( array_keys( topersia_g_hub_registry() ) as $type ) {
			if ( is_post_type_archive( $type ) ) {
				return $type;
			}
		}
		return '';
	}
}

/** برچسب tpc_tag جاری (حالت تاکسونومی) یا null. */
if ( ! function_exists( 'topersia_g_hub_term' ) ) {
	function topersia_g_hub_term() {
		if ( ! function_exists( 'is_tax' ) || ! is_tax( 'tpc_tag' ) || is_feed() ) {
			return null;
		}
		$t = get_queried_object();
		return ( $t instanceof WP_Term ) ? $t : null;
	}
}

/** آیا این درخواست هاب است (نوع، برچسب یا فصل). */
if ( ! function_exists( 'topersia_g_hub_is' ) ) {
	function topersia_g_hub_is() {
		return '' !== topersia_g_hub_type() || null !== topersia_g_hub_term() || '' !== topersia_g_hub_season();
	}
}

/** نوع‌هایی که هاب جاری پوشش می‌دهد. */
if ( ! function_exists( 'topersia_g_hub_types' ) ) {
	function topersia_g_hub_types() {
		$type = topersia_g_hub_type();
		if ( '' !== $type ) {
			return array( $type );
		}
		$reg = topersia_g_hub_registry();
		/* حالت فصل: فقط نوع‌های دارای فیلد «بهترین فصل سفر» */
		if ( '' !== topersia_g_hub_season() ) {
			return array_keys( array_filter( $reg, function ( $row ) { return '' !== $row['season']; } ) );
		}
		return array_keys( $reg );
	}
}

/** سطر REGISTRY هاب جاری؛ برچسب: فاست = نوع محتوا، بی فصل/معیار، مکان روشن. */
if ( ! function_exists( 'topersia_g_hub_row' ) ) {
	function topersia_g_hub_row() {
		$type = topersia_g_hub_type();
		$reg  = topersia_g_hub_registry();
		if ( '' !== $type && isset( $reg[ $type ] ) ) {
			return $reg[ $type ];
		}
		return array( 'slug' => '', 'facet' => 'post_type', 'season' => '', 'metric' => '', 'loc' => true );
	}
}

/**
 * همۀ شناسه‌های نمایش‌پذیر هاب — زبان جاری، منتشرشده، غیرنمونه (نمایشی فقط در پیش‌نمایش). یک بار در هر درخواست.
 *
 * @return int[]
 */
if ( ! function_exists( 'topersia_g_hub_pool' ) ) {
	function topersia_g_hub_pool() {

		static $memo = array();

		$term = topersia_g_hub_term();
		$seas = topersia_g_hub_season();
		$key  = $term ? 't' . $term->term_id : ( '' !== $seas ? 's' . $seas : implode( ',', topersia_g_hub_types() ) );

		if ( isset( $memo[ $key ] ) ) {
			return $memo[ $key ];
		}

		$args = array(
			'post_type'        => topersia_g_hub_types(),
			'post_status'      => 'publish',
			'has_password'     => false,
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_query'       => topersia_g_sample_mq(),
		);
		if ( $term ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'tpc_tag', 'field' => 'term_id', 'terms' => array( (int) $term->term_id ) ) );
		}

		$memo[ $key ] = topersia_g_published( array_map( 'absint', (array) get_posts( $args ) ) );

		if ( '' !== $seas ) {
			$memo[ $key ] = array_values( array_filter( $memo[ $key ], function ( $id ) use ( $seas ) {
				return topersia_g_hub_in_season( $id, $seas );
			} ) );
		}

		return $memo[ $key ];
	}
}

/** مقدار خام فاست یک پست (نوع محتوا در حالت برچسب). */
if ( ! function_exists( 'topersia_g_hub_facet_of' ) ) {
	function topersia_g_hub_facet_of( $id, $facet ) {
		if ( 'post_type' === $facet ) {
			return (string) get_post_type( $id );
		}
		$v = topersia_g_raw( $id, $facet );
		return is_scalar( $v ) ? trim( (string) $v ) : '';
	}
}

/** برچسب یک مقدار فاست به زبان صفحه — واژه‌نامۀ هر نوع؛ نوع محتوا ← نام نوع. */
if ( ! function_exists( 'topersia_g_hub_facet_label' ) ) {
	function topersia_g_hub_facet_label( $facet, $value, $type ) {
		if ( 'post_type' === $facet ) {
			$ref = topersia_g_kind_labels();
			return isset( $ref[ $value ] ) ? topersia_g_t( $ref[ $value ] ) : $value;
		}
		$map = topersia_g_map();
		$g   = isset( $map[ $facet ]['glossary'] ) ? $map[ $facet ]['glossary'] : '';
		$g   = is_array( $g ) ? ( isset( $g[ $type ] ) ? $g[ $type ] : '' ) : $g;
		return 'unknown' === $value ? '' : (string) topersia_g_glossary_label( $g, $value );
	}
}

/**
 * گزینه‌های فاست — فقط مقدارهایی که دست‌کم یک مورد نمایش‌پذیر دارند. کمتر از دو مقدار ← هیچ (بی تراشه).
 *
 * @return array مقدار ← برچسب
 */
if ( ! function_exists( 'topersia_g_hub_facet_options' ) ) {
	function topersia_g_hub_facet_options() {

		$row = topersia_g_hub_row();

		if ( '' === $row['facet'] ) {
			return array();
		}

		$type = topersia_g_hub_type();
		$out  = array();

		foreach ( topersia_g_hub_pool() as $id ) {
			$v = topersia_g_hub_facet_of( $id, $row['facet'] );
			if ( '' !== $v && ! isset( $out[ $v ] ) ) {
				$lab = topersia_g_hub_facet_label( $row['facet'], $v, '' !== $type ? $type : (string) get_post_type( $id ) );
				if ( '' !== $lab ) {
					$out[ $v ] = $lab;
				}
			}
		}

		asort( $out );

		return count( $out ) >= 2 ? $out : array();
	}
}

/** گزینه‌های فصل (واژه‌نامۀ ۳۴ بی «تمام سال») — فقط اگر نوع فیلد فصل دارد. */
if ( ! function_exists( 'topersia_g_hub_season_options' ) ) {
	function topersia_g_hub_season_options() {
		$row = topersia_g_hub_row();
		if ( '' === $row['season'] ) {
			return array();
		}
		$out = array();
		foreach ( array( 'spring', 'summer', 'autumn', 'winter' ) as $s ) {
			$out[ $s ] = (string) topersia_g_glossary_label( '34', $s );
		}
		return $out;
	}
}

/** پالایش‌های هاب از GET — مقدار نامعتبر تهی می‌شود. */
if ( ! function_exists( 'topersia_g_hub_args' ) ) {
	function topersia_g_hub_args() {

		$row    = topersia_g_hub_row();
		$facet  = is_scalar( get_query_var( 'tp_facet' ) ) ? sanitize_key( (string) get_query_var( 'tp_facet' ) ) : '';
		$season = is_scalar( get_query_var( 'season' ) ) ? sanitize_key( (string) get_query_var( 'season' ) ) : '';
		$q      = trim( (string) preg_replace( '/\s+/u', ' ', sanitize_text_field( (string) ( is_scalar( get_query_var( 'tp_q' ) ) ? get_query_var( 'tp_q' ) : '' ) ) ) );
		$sort   = is_scalar( get_query_var( 'tp_sort' ) ) ? (string) get_query_var( 'tp_sort' ) : '';

		/*
		 * P5-D566: هاب ردیف فیلتر ندارد — مکان، فصل و جست‌وجو دیگر از رابط نمی‌آیند. پارامترشان در نشانی هنوز پذیرفته می‌شود
		 * (پیوندهای کهنه نشکنند) و صفحه را noindex می‌کند؛ فصل جای خودش را دارد: /season/{فصل}/.
		 */
		return array(
			'tp_place' => $row['loc'] && function_exists( 'topersia_g_news_filter_place' ) ? topersia_g_news_filter_place() : 0,
			'tp_facet' => isset( topersia_g_hub_facet_options()[ $facet ] ) ? $facet : '',
			'season'   => isset( topersia_g_hub_season_options()[ $season ] ) ? $season : '',
			'tp_q'     => function_exists( 'mb_substr' ) ? mb_substr( $q, 0, 80 ) : substr( $q, 0, 80 ),
			'tp_sort'  => isset( topersia_g_hub_sort_options()[ $sort ] ) && '' !== $sort ? $sort : '',
		);
	}
}

/** آیا هر پالایشی (از جمله ترتیب، یا مقدار نامعتبر) روشن است. */
if ( ! function_exists( 'topersia_g_hub_filtered' ) ) {
	function topersia_g_hub_filtered() {
		if ( array_filter( topersia_g_hub_args() ) ) {
			return true;
		}
		foreach ( array( 'tp_place', 'tp_loc', 'tp_facet', 'season', 'tp_q', 'tp_sort' ) as $qv ) {
			$v = get_query_var( $qv );
			if ( ! is_scalar( $v ) || ( '' !== trim( (string) $v ) && '0' !== (string) $v ) ) {
				return true;
			}
		}
		return false;
	}
}

/** گزینه‌های ترتیب: نام (پیش‌فرض) · تازه‌ترین · معیار عددی نوع اگر هست. */
if ( ! function_exists( 'topersia_g_hub_sort_options' ) ) {
	function topersia_g_hub_sort_options() {
		$row = topersia_g_hub_row();
		$out = array( '' => __( 'الفبا', 'topersia' ), 'new' => __( 'جدیدترین', 'topersia' ) );
		if ( '' !== $row['metric'] ) {
			$map           = topersia_g_map();
			$out['metric'] = isset( $map[ $row['metric'] ]['label'] ) ? topersia_g_t( (string) $map[ $row['metric'] ]['label'] ) : $row['metric'];
		}
		return $out;
	}
}

/** نشانی پایۀ هاب جاری، صفحۀ N (بی پالایش). */
if ( ! function_exists( 'topersia_g_hub_url' ) ) {
	function topersia_g_hub_url( $page = 1 ) {
		$term = topersia_g_hub_term();
		$type = topersia_g_hub_type();
		$seas = topersia_g_hub_season();
		if ( '' !== $seas ) {
			return topersia_g_season_url( $seas, $page );
		}
		$url  = $term ? get_term_link( $term ) : ( '' !== $type ? (string) get_post_type_archive_link( $type ) : '' );
		$url  = is_wp_error( $url ) ? '' : (string) $url;
		return ( $page > 1 && '' !== $url ) ? trailingslashit( $url ) . 'page/' . (int) $page . '/' : $url;
	}
}

/** نشانی هاب با پالایش جاری و جای‌گذاری‌ها. */
if ( ! function_exists( 'topersia_g_hub_link' ) ) {
	function topersia_g_hub_link( $page = 1, $over = array() ) {
		$args = array_filter( array_merge( topersia_g_hub_args(), $over ) );
		return add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $args ) ), topersia_g_hub_url( $page ) );
	}
}

/** آیا مورد در مکان پالایش است: خودش یا یکی از نیاکان مسیر راهنما (زبان جاری). */
if ( ! function_exists( 'topersia_g_hub_in_place' ) ) {
	function topersia_g_hub_in_place( $id, $place ) {
		if ( (int) $id === (int) $place ) {
			return true;
		}
		return in_array( (int) $place, array_map( 'absint', (array) topersia_g_crumb_chain( $id ) ), true );
	}
}

/** آیا فصل مورد با فصل پالایش می‌خواند — «تمام سال» با هر فصل. */
if ( ! function_exists( 'topersia_g_hub_in_season' ) ) {
	function topersia_g_hub_in_season( $id, $season ) {
		$v = topersia_g_raw( $id, 'best_season' );
		$v = is_array( $v ) ? array_keys( array_filter( $v, function ( $x ) { return true === $x || 'true' === $x; } ) ) : array( (string) $v );
		return in_array( $season, $v, true ) || in_array( 'all_year', $v, true );
	}
}

/** مقدار عددی معیار یا null. */
if ( ! function_exists( 'topersia_g_hub_metric' ) ) {
	function topersia_g_hub_metric( $id, $field ) {
		$v = '' !== $field ? topersia_g_raw( $id, $field ) : '';
		return ( is_scalar( $v ) && is_numeric( $v ) ) ? (float) $v : null;
	}
}

/**
 * شناسه‌های پالایش‌شده و مرتب — منبع کوئری اصلی و شمار صفحه‌ها.
 *
 * @return int[]
 */
if ( ! function_exists( 'topersia_g_hub_ids' ) ) {
	function topersia_g_hub_ids() {

		$row  = topersia_g_hub_row();
		$args = topersia_g_hub_args();
		$ids  = topersia_g_hub_pool();

		/* مکان ناموجود یا نامی که پیدا نشد: هیچ، نه همه (P5-D549) */
		$raw_loc = is_scalar( get_query_var( 'tp_loc' ) ) ? trim( (string) get_query_var( 'tp_loc' ) ) : 'x';
		if ( $row['loc'] && ! $args['tp_place'] && ( absint( get_query_var( 'tp_place' ) ) || '' !== $raw_loc ) ) {
			return array();
		}

		$ids = array_values( array_filter( $ids, function ( $id ) use ( $row, $args ) {
			if ( $args['tp_place'] && ! topersia_g_hub_in_place( $id, $args['tp_place'] ) ) {
				return false;
			}
			if ( '' !== $args['tp_facet'] && topersia_g_hub_facet_of( $id, $row['facet'] ) !== $args['tp_facet'] ) {
				return false;
			}
			if ( '' !== $args['season'] && ! topersia_g_hub_in_season( $id, $args['season'] ) ) {
				return false;
			}
			return true;
		} ) );

		if ( '' !== $args['tp_q'] && $ids ) {
			$term = 'fa' === topersia_g_page_lang() ? strtr( $args['tp_q'], array( "ي" => "ی", "ى" => "ی", "ك" => "ک" ) ) : $args['tp_q'];   /* i18n:skip — حرف عربی، نه رشتۀ رابط */
			$hit  = get_posts( array( 'post_type' => topersia_g_hub_types(), 'post__in' => $ids, 's' => $term, 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true, 'suppress_filters' => false ) );
			$ids  = array_values( array_intersect( $ids, array_map( 'absint', (array) $hit ) ) );
		}

		if ( 'new' === $args['tp_sort'] ) {
			usort( $ids, function ( $a, $b ) {
				return strcmp( (string) get_post_field( 'post_date', $b ), (string) get_post_field( 'post_date', $a ) ) ?: $b - $a;
			} );
		} elseif ( 'metric' === $args['tp_sort'] ) {
			/* بیشترین اول؛ بی مقدار آخر، به ترتیب نام */
			$pos = array_flip( topersia_g_home_by_title( $ids ) );
			usort( $ids, function ( $a, $b ) use ( $row, $pos ) {
				$x = topersia_g_hub_metric( $a, $row['metric'] );
				$y = topersia_g_hub_metric( $b, $row['metric'] );
				if ( ( null === $x ) !== ( null === $y ) ) {
					return null === $x ? 1 : -1;
				}
				return ( null === $x || $x === $y ) ? $pos[ $a ] - $pos[ $b ] : ( $y < $x ? -1 : 1 );
			} );
		} else {
			$ids = topersia_g_home_by_title( $ids );
		}

		return array_map( 'absint', $ids );
	}
}

/** noindex: پالایش‌شده یا کمتر از TOPERSIA_HUB_MIN_INDEX مورد. */
if ( ! function_exists( 'topersia_g_hub_thin' ) ) {
	function topersia_g_hub_thin() {
		return topersia_g_hub_filtered() || count( topersia_g_hub_pool() ) < TOPERSIA_HUB_MIN_INDEX;
	}
}

/** H1 هاب — جمع نوع با نام مکان (P5-D558)؛ برچسب ← نام برچسب. */
if ( ! function_exists( 'topersia_g_hub_title' ) ) {
	function topersia_g_hub_title( $place_id = 0 ) {
		$term = topersia_g_hub_term();
		if ( $term ) {
			return (string) $term->name;
		}
		$seas = topersia_g_hub_season();
		if ( '' !== $seas ) {
			/* translators: %s: نام فصل */
			return sprintf( __( '%s در ایران', 'topersia' ), (string) topersia_g_glossary_label( '34', $seas ) );
		}
		$type   = topersia_g_hub_type();
		$titles = topersia_g_hub_titles();
		$kinds  = topersia_g_kind_labels();
		$tpl    = isset( $titles[ $type ] ) ? $titles[ $type ] : topersia_g_t( isset( $kinds[ $type ] ) ? $kinds[ $type ] : $type );
		return sprintf( $tpl, topersia_g_news_place_name( $place_id ) );
	}
}

/** جملۀ زیر H1 — با %s همان عنوان. */
if ( ! function_exists( 'topersia_g_hub_lead' ) ) {
	function topersia_g_hub_lead( $place_id = 0 ) {
		if ( topersia_g_hub_term() ) {
			/* translators: %s: نام برچسب */
			return sprintf( __( 'همۀ مکان‌ها و مطالب «%s» در یک جا.', 'topersia' ), topersia_g_hub_title() );
		}
		$seas = topersia_g_hub_season();
		if ( '' !== $seas ) {
			/* translators: %s: نام فصل */
			return sprintf( __( 'جاها و تجربه‌هایی که بهترین زمان سفرشان %s است.', 'topersia' ), (string) topersia_g_glossary_label( '34', $seas ) );
		}
		/* translators: %s: عنوان هاب */
		return sprintf( __( 'فهرست کامل %s.', 'topersia' ), topersia_g_hub_title( $place_id ) );
	}
}

/**
 * کاشی هاب — همان `.tp-tile` (توکن کاشی، P5-D245): برچسب بالا = مقدار فاست یا شهر؛ در ترتیب معیار، مقدار عددی جلوی نام (P5-D466).
 *
 * @return array ورودی topersia_g_tiles()
 */
if ( ! function_exists( 'topersia_g_hub_tile' ) ) {
	function topersia_g_hub_tile( $id ) {

		$row   = topersia_g_hub_row();
		$args  = topersia_g_hub_args();
		$label = '';

		if ( 'metric' === $args['tp_sort'] && null !== topersia_g_hub_metric( $id, $row['metric'] ) ) {
			$label = wp_strip_all_tags( (string) topersia_g_value( $row['metric'], topersia_g_raw( $id, $row['metric'] ), $id ) );
		} elseif ( '' !== $row['facet'] ) {
			$label = topersia_g_hub_facet_label( $row['facet'], topersia_g_hub_facet_of( $id, $row['facet'] ), (string) get_post_type( $id ) );
		}
		if ( '' === $label && 'post_type' !== $row['facet'] ) {
			$label = topersia_g_tile_place( $id );
		}

		$badge = topersia_g_demo_badge( $id );

		return array(
			'label' => $label,
			'name'  => ( '' !== $badge ? $badge . ' ' : '' ) . esc_html( topersia_g_name( $id ) ),
			'raw'   => true,
			'url'   => (string) get_permalink( $id ),
		);
	}
}

/** تراشه‌های فعال هاب — مکان، فاست، فصل، جست‌وجو. */
if ( ! function_exists( 'topersia_g_hub_active_items' ) ) {
	function topersia_g_hub_active_items( $args ) {
		$items = array();
		if ( $args['tp_place'] ) {
			$items[] = array( topersia_g_title( $args['tp_place'] ), topersia_g_hub_link( 1, array( 'tp_place' => '', 'tp_loc' => '' ) ) );
		}
		$f = topersia_g_hub_facet_options();
		if ( '' !== $args['tp_facet'] && isset( $f[ $args['tp_facet'] ] ) ) {
			$items[] = array( $f[ $args['tp_facet'] ], topersia_g_hub_link( 1, array( 'tp_facet' => '' ) ) );
		}
		$s = topersia_g_hub_season_options();
		if ( '' !== $args['season'] && isset( $s[ $args['season'] ] ) ) {
			$items[] = array( $s[ $args['season'] ], topersia_g_hub_link( 1, array( 'season' => '' ) ) );
		}
		if ( '' !== $args['tp_q'] ) {
			$items[] = array( sprintf( /* translators: %s: عبارت جست‌وجو */ __( 'جست‌وجو: %s', 'topersia' ), $args['tp_q'] ), topersia_g_hub_link( 1, array( 'tp_q' => '' ) ) );
		}
		return $items;
	}
}

/** نشانی هاب یک نوع (برای «مشاهده همه» و کارت فصل) — تهی اگر نوع هاب ندارد. */
if ( ! function_exists( 'topersia_g_hub_link_for' ) ) {
	function topersia_g_hub_link_for( $type, $args = array() ) {
		if ( ! isset( topersia_g_hub_registry()[ $type ] ) || ! function_exists( 'get_post_type_archive_link' ) ) {
			return '';
		}
		$url = (string) get_post_type_archive_link( $type );
		return '' !== $url ? add_query_arg( array_map( 'rawurlencode', array_filter( $args ) ), $url ) : '';
	}
}

/* ── هوک‌ها ─────────────────────────────────────────────────────────── */

if ( function_exists( 'add_action' ) ) {

	/* آرشیو روی همان نامک موجود — نشانی صفحۀ تکی بی تغییر. فقط نوع‌های ماتریس. */
	add_filter( 'register_post_type_args', function ( $args, $type ) {
		if ( ! function_exists( 'topersia_g_type_matrix' ) || ! isset( topersia_g_type_matrix()[ $type ] ) ) {
			return $args;
		}
		$slug = isset( $args['rewrite']['slug'] ) ? trim( (string) $args['rewrite']['slug'], '/' ) : '';
		if ( '' !== $slug && ! empty( $args['public'] ) && empty( $args['has_archive'] ) ) {
			$args['has_archive'] = $slug;
		}
		return $args;
	}, 20, 2 );

	add_filter( 'query_vars', function ( $vars ) {
		return array_merge( $vars, array( 'tp_facet', 'season', 'tp_season' ) );
	} );

	/* یک‌بار بازسازی قاعده‌ها، فقط اگر امضای نامک‌ها عوض شود. */
	add_action( 'init', function () {
		$sig = array();
		foreach ( array_keys( topersia_g_type_matrix() ) as $type ) {
			$o = get_post_type_object( $type );
			if ( $o && ! empty( $o->has_archive ) ) {
				$sig[] = $type . '=' . $o->has_archive;
			}
		}
		$sig[] = taxonomy_exists( 'tpc_tag' ) ? 'tpc_tag' : '';
		/* صفحه‌های فصل (P5-D564): قاعده هر نامک را می‌پذیرد؛ نامک ناشناس ← ۴۰۴ در pre_get_posts */
		$bases = array();
		foreach ( topersia_g_season_slugs() as $map ) {
			$bases[] = preg_quote( (string) $map['base'], '#' );
		}
		$bases = implode( '|', array_unique( $bases ) );
		add_rewrite_rule( '^(?:' . $bases . ')/([^/]+)/page/([0-9]+)/?$', 'index.php?tp_season=$matches[1]&paged=$matches[2]', 'top' );
		add_rewrite_rule( '^(?:' . $bases . ')/([^/]+)/?$', 'index.php?tp_season=$matches[1]', 'top' );

		$sig   = md5( implode( '|', $sig ) . '|' . $bases . '|hub-v2' );
		if ( get_option( 'topersia_hub_rules' ) !== $sig ) {
			flush_rewrite_rules( false );
			update_option( 'topersia_hub_rules', $sig, false );
		}
	}, 40 );

	/* کوئری اصلی: شناسه‌های پالایش‌شده به ترتیب خودشان. */
	add_action( 'pre_get_posts', function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() || $q->is_feed() ) {
			return;
		}
		$types = array_keys( topersia_g_hub_registry() );
		$raw   = (string) $q->get( 'tp_season' );
		$seas  = topersia_g_hub_season();
		if ( '' !== $raw && '' === $seas ) {
			$q->set( 'post__in', array( 0 ) );   /* نامک فصل ناشناس ← ۴۰۴ در template_redirect پایین */
			return;
		}
		if ( '' === $seas && ! $q->is_post_type_archive( $types ) && ! $q->is_tax( 'tpc_tag' ) ) {
			return;
		}
		$ids = topersia_g_hub_ids();
		if ( '' !== $seas ) {
			$q->set( 'post_type', topersia_g_hub_types() );
			$q->is_home    = false;
			$q->is_archive = true;
		}
		if ( $q->is_tax( 'tpc_tag' ) ) {
			$q->set( 'post_type', $types );
		}
		$q->set( 'post_status', 'publish' );
		$q->set( 'posts_per_page', TOPERSIA_HUB_PER_PAGE );
		$q->set( 'post__in', $ids ? $ids : array( 0 ) );
		$q->set( 'orderby', 'post__in' );
		$q->set( 'meta_query', array() );
		$q->set( 's', '' );
	}, 20 );

	/* نامک فصل ناشناس: کوئری خانه خودش ۴۰۴ نمی‌شود — این‌جا صریح. */
	add_action( 'template_redirect', function () {
		$raw = is_scalar( get_query_var( 'tp_season' ) ) ? (string) get_query_var( 'tp_season' ) : '';
		if ( '' !== $raw && '' === topersia_g_hub_season() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}, 1 );

	/* هاب خالی (هیچ مورد) ← ۲۰۰ خالی‌امن، نه ۴۰۴؛ صفحۀ بیرون از بازه ← ۴۰۴ از خود وردپرس. */
	add_filter( 'pre_handle_404', function ( $pre, $query ) {
		if ( $pre || ! $query->is_main_query() || $query->get( 'paged' ) > 1 ) {
			return $pre;
		}
		if ( $query->is_post_type_archive( array_keys( topersia_g_hub_registry() ) ) || $query->is_tax( 'tpc_tag' ) || '' !== topersia_g_hub_season() ) {
			status_header( 200 );
			return true;
		}
		return $pre;
	}, 10, 2 );

	add_filter( 'template_include', function ( $template ) {
		if ( ! topersia_g_hub_is() ) {
			return $template;
		}
		$file = topersia_render_path() . 'templates/hub-archive.php';
		return is_file( $file ) ? $file : $template;
	}, 99 );

	add_action( 'wp_enqueue_scripts', function () {
		if ( ! topersia_g_hub_is() || ! function_exists( 'topersia_render_url' ) ) {
			return;
		}
		wp_enqueue_style( 'topersia-g-news', topersia_render_url() . 'assets/topersia-g-news.css', array( 'topersia-g-template' ), topersia_render_asset_version( 'assets/topersia-g-news.css' ) );
		wp_enqueue_style( 'topersia-g-hub', topersia_render_url() . 'assets/topersia-g-hub.css', array( 'topersia-g-news' ), topersia_render_asset_version( 'assets/topersia-g-hub.css' ) );
		wp_enqueue_script( 'topersia-g-news', topersia_render_url() . 'assets/topersia-g-news.js', array(), topersia_render_asset_version( 'assets/topersia-g-news.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}, 31 );

	add_filter( 'wp_robots', function ( $r ) {
		if ( topersia_g_hub_is() && topersia_g_hub_thin() ) {
			unset( $r['index'], $r['max-image-preview'] );
			$r['noindex'] = true;
			if ( empty( $r['nofollow'] ) ) {
				$r['follow'] = true;
			}
		}
		return $r;
	}, 99 );
	add_filter( 'rank_math/frontend/robots', function ( $r ) {
		if ( topersia_g_hub_is() && topersia_g_hub_thin() ) {
			$r['index'] = 'noindex';
			if ( ! isset( $r['follow'] ) || 'nofollow' !== $r['follow'] ) {
				$r['follow'] = 'follow';
			}
		}
		return $r;
	}, 99 );

	/* canonical: صفحۀ N خودش؛ هر حالت پالایش‌شده ← نشانی بی‌پالایش. */
	$tp_hub_canon = function ( $url ) {
		return topersia_g_hub_is() ? topersia_g_hub_url( topersia_g_hub_filtered() ? 1 : max( 1, (int) get_query_var( 'paged' ) ) ) : $url;
	};
	add_filter( 'get_canonical_url', $tp_hub_canon, 99 );
	add_filter( 'rank_math/frontend/canonical', $tp_hub_canon, 99 );

	$tp_hub_doc_title = function () {
		return topersia_g_hub_title( topersia_g_hub_args()['tp_place'] );
	};
	add_filter( 'document_title_parts', function ( $parts ) use ( $tp_hub_doc_title ) {
		if ( topersia_g_hub_is() ) {
			$parts['title'] = $tp_hub_doc_title();
		}
		return $parts;
	}, 99 );
	add_filter( 'rank_math/frontend/title', function ( $t ) use ( $tp_hub_doc_title ) {
		return topersia_g_hub_is() ? $tp_hub_doc_title() . ' | ' . get_bloginfo( 'name' ) : $t;
	}, 99 );
	add_filter( 'rank_math/frontend/description', function ( $d ) {
		return topersia_g_hub_is() ? topersia_g_hub_lead( topersia_g_hub_args()['tp_place'] ) : $d;
	}, 99 );
}
