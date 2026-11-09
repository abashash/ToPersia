<?php
/**
 * خبر — CHANGE-PACKAGE-001 بند ۷ · P5-D213 · ۴٫۳٫۰ بند ۶
 *
 * خبر Content Type است نه موجودیت (`tpc_news`). مدل داده‌اش — نوع پست،
 * فیلدهای `news_source_name` و `news_source_url`، چهار رابطۀ چندبه‌چند با
 * استان/شهر/روستا/جزیره — در JetEngine ساخته می‌شود و فعلاً فقط روی
 * نسخۀ محلی هست؛ پیش از سایت زنده ممیزی NotebookLM.
 *
 * این فایل سراسر پشت یک نگهبان است: `tpc_news` ثبت نشده باشد، هیچ هوک،
 * قاعدۀ بازنویسی یا بخشی کار نمی‌کند. پس نصب همین نسخه روی سایت زنده
 * چیزی را عوض نمی‌کند.
 *
 * مالک مسیریابی و محاسبۀ نیا در بسته `topersia-core` است (P5-D014)؛
 * تا ممیزی، این‌جا می‌نشیند و پس از آن جابه‌جا می‌شود.
 *
 *   تجمیع رو به بالا  `_tp_news_ancestor` — هنگام ذخیره، نه هنگام کوئری
 *   بخش #news         ۴ خبر ۱۲ ماه اخیر + «فهرست اخبار {نام}»؛ بی خبر تازه، بی بخش
 *   صفحۀ خبرها       /{rewrite}/{slug}/news/ و /page/N/ — زیر ۳ خبر noindex
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'TP_PLUGIN_DIR' ) ) {
	return;
}

/** آیا مدل خبر روی این سایت هست. */
if ( ! function_exists( 'topersia_g_news_on' ) ) {
	function topersia_g_news_on() {
		return function_exists( 'post_type_exists' ) && post_type_exists( 'tpc_news' );
	}
}

/** نوع‌های مکانی که در نسخۀ نخست خبر می‌گیرند. */
if ( ! function_exists( 'topersia_g_news_types' ) ) {
	function topersia_g_news_types() {
		return array( 'tp_province', 'tp_city', 'tp_village', 'tp_island' );
	}
}

/**
 * شناسۀ چهار رابطۀ خبر، به ازای نوع مکان.
 *
 * شناسه‌ها پیش‌بینی نمی‌شوند (شمارندۀ JetEngine مشترک است)؛ از خودِ
 * رابطه‌های فعال پیدا می‌شوند: والد `posts::tpc_news`، فرزند نوع مکان.
 *
 * @return array نوع مکان ← شناسۀ رابطه
 */
if ( ! function_exists( 'topersia_g_news_relations' ) ) {
	function topersia_g_news_relations() {

		static $found = null;

		if ( null !== $found ) {
			return $found;
		}

		$found = array();

		if ( function_exists( 'jet_engine' ) && jet_engine() && isset( jet_engine()->relations ) && jet_engine()->relations ) {

			foreach ( (array) jet_engine()->relations->get_active_relations() as $id => $rel ) {

				if ( ! is_object( $rel ) || ! method_exists( $rel, 'get_args' ) ) {
					continue;
				}

				$parent = (string) $rel->get_args( 'parent_object' );
				$child  = str_replace( 'posts::', '', (string) $rel->get_args( 'child_object' ) );

				if ( 'posts::tpc_news' === $parent && in_array( $child, topersia_g_news_types(), true ) ) {
					$found[ $child ] = (int) $id;
				}
			}
		}

		if ( function_exists( 'apply_filters' ) ) {
			$found = (array) apply_filters( 'topersia_g_news_relations', $found );
		}

		return $found;
	}
}

/**
 * نیاکانِ مکانیِ یک مکان — برای تجمیع رو به بالا.
 *
 *   شهر     شهرستان (۷۳) ← استان (۷۲)
 *   روستا   استان (۷۵) · نزدیک‌ترین شهر (۹۸)
 *   جزیره   استان (۷۷)
 *
 * @return array<int,int>
 */
if ( ! function_exists( 'topersia_g_news_ancestors_of' ) ) {
	function topersia_g_news_ancestors_of( $place_id ) {

		$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $place_id ) : '';
		$out  = array();

		/* داده است نه نمایش: نیای منتشرنشده هم در زنجیره می‌ماند.  P5-D247 */
		if ( 'tp_city' === $type ) {
			foreach ( topersia_g_rel_ids( $place_id, array( array( 'id' => 73, 'dir' => 'parents' ) ), false ) as $county ) {
				$out[] = $county;
				foreach ( topersia_g_rel_ids( $county, array( array( 'id' => 72, 'dir' => 'parents' ) ), false ) as $prov ) {
					$out[] = $prov;
				}
			}
		} elseif ( 'tp_village' === $type ) {
			$out = topersia_g_rel_ids( $place_id, array( array( 'id' => 75, 'dir' => 'parents' ), array( 'id' => 98, 'dir' => 'parents' ) ), false );
		} elseif ( 'tp_island' === $type ) {
			$out = topersia_g_rel_ids( $place_id, array( array( 'id' => 77, 'dir' => 'parents' ) ), false );
		}

		return array_values( array_unique( array_map( 'absint', $out ) ) );
	}
}

/** مکان‌های مستقیمِ یک خبر — فرزندانش در چهار رابطه. */
if ( ! function_exists( 'topersia_g_news_places' ) ) {
	function topersia_g_news_places( $news_id ) {

		$rels = array();

		foreach ( topersia_g_news_relations() as $rel_id ) {
			$rels[] = array( 'id' => $rel_id, 'dir' => 'children' );
		}

		/* برای نیاهای ذخیره‌شده — داده، نه نمایش.  P5-D247 */
		return $rels ? topersia_g_rel_ids( $news_id, $rels, false ) : array();
	}
}

/**
 * `_tp_news_ancestor` یک خبر را از نو می‌سازد — یک ردیف برای هر نیا.
 *
 * WPML برای این متا ignore است: هر ترجمه هنگام ذخیرۀ خودش نیاهای زبان
 * خودش را حساب می‌کند.
 */
if ( ! function_exists( 'topersia_g_news_sync' ) ) {
	function topersia_g_news_sync( $news_id ) {

		$news_id = absint( $news_id );

		if ( ! $news_id || 'tpc_news' !== get_post_type( $news_id ) ) {
			return array();
		}

		$places = topersia_g_news_places( $news_id );
		$anc    = array();

		foreach ( $places as $place ) {
			foreach ( topersia_g_news_ancestors_of( $place ) as $one ) {
				if ( ! in_array( $one, $places, true ) ) {
					$anc[ $one ] = $one;
				}
			}
		}

		delete_post_meta( $news_id, '_tp_news_ancestor' );

		foreach ( $anc as $one ) {
			add_post_meta( $news_id, '_tp_news_ancestor', $one );
		}

		return array_values( $anc );
	}
}

/**
 * شناسۀ خبرهای یک مکان — رابطۀ مستقیم به‌اضافۀ `_tp_news_ancestor`.
 *
 * @param int $place_id مکان (شناسۀ زبان جاری).
 * @param int $limit    شمار؛ صفر یعنی همه.
 * @param int $months   بازۀ تازگی؛ صفر یعنی بی‌مرز.
 * @param int $paged    صفحه.
 * @param array $opts   دستۀ 49: topic (نامک موضوع زبان جاری) · order (DESC|ASC).
 * @return array ids · total
 */
if ( ! function_exists( 'topersia_g_news_query' ) ) {
	function topersia_g_news_query( $place_id, $limit = 4, $months = 12, $paged = 1, $opts = array() ) {

		$empty = array( 'ids' => array(), 'total' => 0 );

		/* راه کوتاه — برای افزونۀ دیگر یا آزمون: آرایۀ ids · total. */
		$pre = function_exists( 'apply_filters' ) ? apply_filters( 'topersia_g_news_pre_query', null, $place_id, $limit, $months, $paged ) : null;

		/* راه کوتاه هم از دروازه می‌گذرد — هیچ خروجی عمومی بی آن.  P5-D247 */
		if ( is_array( $pre ) ) {
			$pre         += $empty;
			$was          = count( (array) $pre['ids'] );
			$pre['ids']   = topersia_g_published( (array) $pre['ids'] );
			$pre['total'] = max( 0, (int) $pre['total'] - ( $was - count( $pre['ids'] ) ) );
			return $pre;
		}

		if ( ! topersia_g_news_on() || ! $place_id ) {
			return $empty;
		}

		$type = (string) get_post_type( $place_id );
		$rels = topersia_g_news_relations();
		$pool = array();

		if ( isset( $rels[ $type ] ) ) {
			$pool = topersia_g_rel_ids( $place_id, array( array( 'id' => $rels[ $type ], 'dir' => 'parents' ) ) );
		}

		$by_meta = get_posts( array(
			'post_type'        => 'tpc_news',
			'post_status'      => 'publish',
			'has_password'     => false,
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'meta_key'         => '_tp_news_ancestor',
			'meta_value'       => (string) absint( $place_id ),
		) );

		/* فقط منتشرشده، بی‌رمز، با نسخۀ زبان جاری — دروازۀ مرکزی.  P5-D247 */
		$pool = topersia_g_published( array_merge( $pool, (array) $by_meta ) );

		if ( ! $pool ) {
			return $empty;
		}

		$args = array(
			'post_type'        => 'tpc_news',
			'post_status'      => 'publish',
			'has_password'     => false,
			'post__in'         => $pool,
			'orderby'          => 'date',
			'order'            => ( isset( $opts['order'] ) && 'ASC' === $opts['order'] ) ? 'ASC' : 'DESC',
			'posts_per_page'   => $limit ? (int) $limit : -1,
			'paged'            => max( 1, (int) $paged ),
			'fields'           => 'ids',
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $opts['topic'] ) && taxonomy_exists( 'tpc_news_topic' ) ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'tpc_news_topic', 'field' => 'slug', 'terms' => array( (string) $opts['topic'] ) ) );
		}

		if ( $months ) {
			$args['date_query'] = array( array( 'after' => (int) $months . ' months ago', 'inclusive' => true ) );
		}

		$q = new WP_Query( $args );

		return array( 'ids' => array_map( 'absint', (array) $q->posts ), 'total' => (int) $q->found_posts );
	}
}

/** نشانی صفحۀ خبرهای یک مکان. */
if ( ! function_exists( 'topersia_g_news_list_url' ) ) {
	function topersia_g_news_list_url( $place_id, $page = 1 ) {
		$url = trailingslashit( (string) get_permalink( $place_id ) ) . 'news/';
		return $page > 1 ? $url . 'page/' . (int) $page . '/' : $url;
	}
}

/**
 * یک کارت خبر — عیناً `.news > a` مرجع: تصویر، تیتر، تاریخ، خلاصه.
 *
 * جای «NEWS» لاتینِ مرجع، تصویر شاخص خبر؛ نبود، یک آیکون روزنامه.  P5-D240
 */
if ( ! function_exists( 'topersia_g_news_card' ) ) {
	function topersia_g_news_card( $news_id ) {

		$date  = (string) get_post_time( 'Y-m-d', false, $news_id );
		$sum   = topersia_g_text( $news_id, 'short_summary' );
		/*
		 * تصویر شاخص خبر، ۴:۳ — ۱۱۲×۸۴ در دسکتاپ و ۸۸×۶۶ در موبایل (اندازه از
		 * CSS، ابعاد صریحِ پرونده از وردپرس)؛ srcset خودِ وردپرس و `sizes`
		 * همان دو پهنا. بی‌تصویر، همان جعبۀ آیکون با همان اندازه.  SPRINT-4.4 ۱٫۵
		 */
		/* P5-D388: `hero_image` خودِ خبر ← تصویر شاخص ← آیکون؛ همان اندازه‌ها (همان دروازۀ کارت‌ها، `topersia_g_card_image()`) */
		$img   = topersia_g_card_image( $news_id );
		$thumb = ( $img && function_exists( 'wp_get_attachment_image' ) ) ? (string) wp_get_attachment_image( $img, 'medium', false, array(
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => '(max-width: 720px) 88px, 112px',
		) ) : '';

		if ( '' === $sum && function_exists( 'get_the_excerpt' ) ) {
			$sum = trim( wp_strip_all_tags( (string) get_the_excerpt( $news_id ) ) );
		}

		if ( '' === $thumb ) {
			$thumb = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">'
				. '<rect x="3" y="4" width="15" height="16" rx="2"/><path d="M18 8h3v10a2 2 0 0 1-2 2M7 8h7M7 12h7M7 16h4"/></svg>';
		}

		return '<a href="' . esc_url( (string) get_permalink( $news_id ) ) . '">'
			. '<div class="tp-thumb">' . $thumb . '</div><div>'
			. '<h3>' . esc_html( get_the_title( $news_id ) ) . '</h3>'
			. ( '' !== $date ? '<time datetime="' . esc_attr( $date ) . '">' . esc_html( topersia_g_date_fa( $date ) ) . '</time>' : '' )
			/* متن خبر تراز دوطرفه.  P5-D270 */
			. ( '' !== $sum ? '<p class="tp-justify">' . esc_html( $sum ) . '</p>' : '' )
			. '</div></a>';
	}
}

/** عنوان صفحۀ خبرها — «اخبار {نام کامل}». */
if ( ! function_exists( 'topersia_g_news_title' ) ) {
	function topersia_g_news_title( $place_id ) {
		return sprintf( /* translators: %s: نام کامل */ __( 'اخبار %s', 'topersia' ), topersia_g_title( $place_id ) );
	}
}

/* ── آرشیو سراسری /news/ — SPRINT-4.4 فاز ۶٫۱ (P5-D260) · دستۀ 48 ──────────
 *
 * فقط خبر منتشرشده، بی‌گذرواژه، هم‌زبان و غیرنمونه (`_tp_sample`)؛ کوئری اصلی
 * وردپرس خودش صفحه‌بندی و ۴۰۴ بیرون از بازه را می‌دهد. پالایش `?tp_place=`
 * (استان یا شهر) همان تجمیع رو به نیای `topersia_g_news_query()` است، با
 * noindex و canonical به نشانی بی‌پالایش. زیر ۳ خبر noindex, follow.
 * دستۀ 49 (P5-D534): موضوع، نوع مکان، جست‌وجو و ترتیب — topersia-g-news-ui.php.
 */

/** شمار خبر در هر صفحۀ آرشیو. */
if ( ! defined( 'TOPERSIA_NEWS_PER_PAGE' ) ) {
	define( 'TOPERSIA_NEWS_PER_PAGE', 12 );
}

/** آیا این درخواست آرشیو سراسری خبر است. */
if ( ! function_exists( 'topersia_g_news_is_archive' ) ) {
	function topersia_g_news_is_archive() {
		return topersia_g_news_on() && function_exists( 'is_post_type_archive' ) && is_post_type_archive( 'tpc_news' ) && ! is_feed();
	}
}

/** مکان پالایش (شناسۀ منتشرشدۀ استان یا شهر) یا صفر. */
if ( ! function_exists( 'topersia_g_news_filter_place' ) ) {
	function topersia_g_news_filter_place() {
		$id = absint( function_exists( 'get_query_var' ) ? get_query_var( 'tp_place' ) : 0 );
		if ( ! $id || ! in_array( (string) get_post_type( $id ), array( 'tp_province', 'tp_city' ), true ) ) {
			return 0;
		}
		return topersia_g_published( array( $id ) ) ? $id : 0;
	}
}

/** شناسۀ همۀ خبرهای نمایش‌پذیر زبان جاری (اختیاری: فقط یک مکان) — منتشرشده و غیرنمونه. */
if ( ! function_exists( 'topersia_g_news_archive_ids' ) ) {
	function topersia_g_news_archive_ids( $place_id = 0 ) {

		if ( $place_id ) {
			$ids = topersia_g_news_query( $place_id, 0, 0 );
			$ids = $ids['ids'];
		} else {
			$ids = get_posts( array(
				'post_type'        => 'tpc_news',
				'post_status'      => 'publish',
				'has_password'     => false,
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			) );
		}

		$out = array();
		foreach ( topersia_g_published( array_map( 'absint', (array) $ids ) ) as $id ) {
			if ( ! metadata_exists( 'post', $id, '_tp_sample' ) ) {
				$out[] = $id;
			}
		}

		return $out;
	}
}

/** نشان مکان‌های مرتبط یک خبر با پیوند — از دروازۀ انتشار، به زبان جاری. */
if ( ! function_exists( 'topersia_g_news_badges' ) ) {
	function topersia_g_news_badges( $news_id ) {

		$out = '';
		foreach ( topersia_g_published( topersia_g_news_places( $news_id ) ) as $pid ) {
			$out .= '<li><a href="' . esc_url( (string) get_permalink( $pid ) ) . '">' . esc_html( topersia_g_title( $pid ) ) . '</a></li>';
		}

		return '' !== $out ? '<ul class="tp-news-card__places" aria-label="' . esc_attr__( 'مکان‌های مرتبط', 'topersia' ) . '">' . $out . '</ul>' : '';
	}
}

/**
 * کارت «جدیدترین مطالب» — الگوی P5-D534: بندانگشتی ۱۶:۱۰ با نشان موضوع، تیتر
 * دوخطی، خلاصۀ دوخطی، تاریخ به زبان صفحه (`topersia_g_date_fa()`) و زمان مطالعه.
 * تصویر: خود خبر ← تصویر شاخص مکان مرتبط ← آیکون (`topersia_g_news_image()`).
 */
if ( ! function_exists( 'topersia_g_news_archive_card' ) ) {
	function topersia_g_news_archive_card( $news_id ) {

		$url = (string) get_permalink( $news_id );
		$sum = topersia_g_news_sum( $news_id );
		$pic = topersia_g_news_pic( $news_id, 'medium_large', '(max-width: 600px) 100vw, (max-width: 1024px) 50vw, 25vw' );
		$cls = 'tp-news-card__media' . ( '' === $pic ? ' tp-news-card__media--icon' : '' );

		return '<article class="tp-news-card">'
			. '<div class="tp-news-card__top">'
			. '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . ( '' !== $pic ? $pic : topersia_g_news_icon() ) . '</a>'
			. topersia_g_news_topic_badge( $news_id )
			. '</div>'
			. '<div class="tp-news-card__body">'
			. '<h3 class="tp-news-card__title"><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $news_id ) ) . '</a></h3>'
			. ( '' !== $sum ? '<p class="tp-news-card__sum">' . esc_html( $sum ) . '</p>' : '' )
			. topersia_g_news_meta( $news_id, 'tp-news-card__meta' )
			. '</div></article>';
	}
}

/** گزینه‌های پالایش: استان‌ها و شهرهای منتشرشده‌ای که دست‌کم یک خبر نمایش‌پذیر دارند. */
if ( ! function_exists( 'topersia_g_news_filter_options' ) ) {
	function topersia_g_news_filter_options() {

		$pool = array( 'tp_province' => array(), 'tp_city' => array() );   /* استان‌ها پیش از شهرها */
		foreach ( topersia_g_news_archive_ids() as $nid ) {
			$ids = array_merge( topersia_g_news_places( $nid ), array_map( 'absint', (array) get_post_meta( $nid, '_tp_news_ancestor' ) ) );
			foreach ( topersia_g_published( array_values( array_unique( array_filter( $ids ) ) ) ) as $pid ) {
				$type = (string) get_post_type( $pid );
				if ( in_array( $type, array( 'tp_province', 'tp_city' ), true ) ) {
					$pool[ $type ][ $pid ] = topersia_g_title( $pid );
				}
			}
		}
		foreach ( $pool as $type => $list ) {
			asort( $list );
			$pool[ $type ] = $list;
		}

		return array_filter( $pool );
	}
}

/** نشانی آرشیو، صفحۀ N (بی پالایش). */
if ( ! function_exists( 'topersia_g_news_archive_url' ) ) {
	function topersia_g_news_archive_url( $page = 1 ) {
		$url = (string) get_post_type_archive_link( 'tpc_news' );
		return ( $page > 1 && '' !== $url ) ? trailingslashit( $url ) . 'page/' . (int) $page . '/' : $url;
	}
}

/** آیا آرشیو noindex است: هر پالایش (مکان، نوع، موضوع، جست‌وجو، ترتیب) یا کمتر از ۳ خبر نمایش‌پذیر. */
if ( ! function_exists( 'topersia_g_news_archive_thin' ) ) {
	function topersia_g_news_archive_thin() {
		return topersia_g_news_filtered() || count( topersia_g_news_archive_ids() ) < 3;
	}
}

/* ── هوک‌ها — فقط وقتی tpc_news هست ──────────────────────────────── */

if ( function_exists( 'add_action' ) ) {

	/* ذخیرۀ خبر: نیاها از نو.  دیرتر از JetEngine تا رابطه‌های فرم هم نشسته باشند. */
	add_action( 'save_post_tpc_news', function ( $post_id ) {
		if ( ! wp_is_post_revision( $post_id ) ) {
			topersia_g_news_sync( $post_id );
		}
	}, 99 );

	/*
	 * تغییر رابطه:
	 *   رابطۀ خبر            همان خبر از نو
	 *   رابطۀ سلسله‌مراتب    خبرهای مستقیمِ مکانِ فرزند از نو (بازمحاسبه وقتی
	 *                         والد یک مکان عوض شود — بند ۷٫۶)
	 */
	$topersia_news_rel_changed = function ( $parent, $child, $x = null, $relation = null ) {

		if ( ! topersia_g_news_on() || ! is_object( $relation ) || ! method_exists( $relation, 'get_id' ) ) {
			return;
		}

		$rel_id = (int) $relation->get_id();

		if ( in_array( $rel_id, array_map( 'intval', topersia_g_news_relations() ), true ) ) {
			topersia_g_news_sync( $parent );
			return;
		}

		if ( in_array( $rel_id, array( 72, 73, 75, 77, 98 ), true ) ) {

			$type = (string) get_post_type( $child );
			$rels = topersia_g_news_relations();

			if ( isset( $rels[ $type ] ) ) {
				foreach ( topersia_g_rel_ids( $child, array( array( 'id' => $rels[ $type ], 'dir' => 'parents' ) ) ) as $news ) {
					topersia_g_news_sync( $news );
				}
			}
		}
	};

	add_action( 'jet-engine/relation/update/after', $topersia_news_rel_changed, 10, 4 );
	add_action( 'jet-engine/relation/delete/after', $topersia_news_rel_changed, 10, 4 );

	/* ── صفحۀ خبرهای هر مکان — /{rewrite}/{slug}/news/ ── */

	add_filter( 'query_vars', function ( $vars ) {
		$vars[] = 'tp_news';
		return $vars;
	} );

	add_action( 'init', function () {

		if ( ! topersia_g_news_on() ) {
			return;
		}

		$sig = array();

		foreach ( topersia_g_news_types() as $type ) {

			$obj = get_post_type_object( $type );

			if ( ! $obj || empty( $obj->rewrite['slug'] ) ) {
				continue;
			}

			$slug  = trim( (string) $obj->rewrite['slug'], '/' );
			$sig[] = $slug;

			/* پیش از قاعدۀ خودِ نوع (`top`)، وگرنه «news» نامک فرزند شمرده می‌شد. */
			add_rewrite_rule(
				'^' . preg_quote( $slug, '#' ) . '/([^/]+)/news(?:/page/([0-9]{1,}))?/?$',
				'index.php?post_type=' . $type . '&' . $type . '=$matches[1]&name=$matches[1]&tp_news=1&paged=$matches[2]',
				'top'
			);
		}

		/* یک‌بار بازسازی، فقط اگر امضای قاعده‌ها عوض شده باشد. */
		$sig = md5( implode( '|', $sig ) . '|v1' );

		if ( get_option( 'topersia_news_rules' ) !== $sig ) {
			flush_rewrite_rules( false );
			update_option( 'topersia_news_rules', $sig, false );
		}
	}, 30 );

	add_filter( 'template_include', function ( $template ) {

		if ( ! get_query_var( 'tp_news' ) || ! is_singular( topersia_g_news_types() ) ) {
			return $template;
		}

		$file = topersia_render_path() . 'templates/news-list.php';

		return is_file( $file ) ? $file : $template;
	}, 99 );

	/* زیر ۳ خبر: noindex, follow — هم هستۀ وردپرس، هم Rank Math.  بند ۷٫۸ */
	$topersia_news_thin = function () {
		if ( ! get_query_var( 'tp_news' ) || ! is_singular( topersia_g_news_types() ) ) {
			return false;
		}
		$q = topersia_g_news_query( get_queried_object_id(), 0, 0 );
		return $q['total'] < 3 || '' !== (string) get_query_var( 'tp_topic' ) || '' !== (string) get_query_var( 'tp_sort' );   /* پالایش‌شده هم (دستۀ 49) */
	};

	/* فقط noindex افزوده می‌شود؛ nofollow سراسری (مثلاً «موتورهای جست‌وجو را
	   دلسرد کن») دست نمی‌خورد و شل نمی‌شود. */
	add_filter( 'wp_robots', function ( $robots ) use ( $topersia_news_thin ) {
		if ( $topersia_news_thin() ) {
			unset( $robots['index'], $robots['max-image-preview'] );
			$robots['noindex'] = true;
			if ( empty( $robots['nofollow'] ) ) {
				$robots['follow'] = true;
			}
		}
		return $robots;
	}, 99 );

	add_filter( 'rank_math/frontend/robots', function ( $robots ) use ( $topersia_news_thin ) {
		if ( $topersia_news_thin() ) {
			$robots['index'] = 'noindex';
			if ( ! isset( $robots['follow'] ) || 'nofollow' !== $robots['follow'] ) {
				$robots['follow'] = 'follow';
			}
		}
		return $robots;
	}, 99 );

	/*
	 * صفحه‌بندی: وردپرس برای درخواستِ تکی `/page/N/` را همیشه برمی‌دارد و به
	 * صفحۀ اول ۳۰۱ می‌کند — صفحۀ دوم هرگز باز نمی‌شد. بیرون از بازه را
	 * خودمان به صفحۀ آخر می‌بریم.
	 */
	add_filter( 'redirect_canonical', function ( $redirect ) {

		if ( ! get_query_var( 'tp_news' ) ) {
			return $redirect;
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );

		if ( $paged > 1 ) {
			$q    = topersia_g_news_query( get_queried_object_id(), 10, 0, 1 );
			$last = max( 1, (int) ceil( $q['total'] / 10 ) );
			if ( $paged > $last ) {
				return topersia_g_news_list_url( get_queried_object_id(), $last );
			}
		}

		return false;
	}, 99 );

	/* عنوان و canonical خودِ صفحه — هر صفحۀ صفحه‌بندی canonical خودش را دارد. */
	$topersia_news_url = function () {
		$on = '' !== (string) get_query_var( 'tp_topic' ) || '' !== (string) get_query_var( 'tp_sort' );
		return topersia_g_news_list_url( get_queried_object_id(), $on ? 1 : max( 1, (int) get_query_var( 'paged' ) ) );   /* پالایش‌شده ← بی‌پالایش */
	};

	add_filter( 'get_canonical_url', function ( $url ) use ( $topersia_news_url ) {
		return ( get_query_var( 'tp_news' ) && is_singular( topersia_g_news_types() ) ) ? $topersia_news_url() : $url;
	}, 99 );

	add_filter( 'rank_math/frontend/canonical', function ( $url ) use ( $topersia_news_url ) {
		return ( get_query_var( 'tp_news' ) && is_singular( topersia_g_news_types() ) ) ? $topersia_news_url() : $url;
	}, 99 );

	add_filter( 'document_title_parts', function ( $parts ) {
		if ( get_query_var( 'tp_news' ) && is_singular( topersia_g_news_types() ) ) {
			$parts['title'] = topersia_g_news_title( get_queried_object_id() );
		}
		return $parts;
	}, 99 );

	add_filter( 'rank_math/frontend/title', function ( $title ) {
		return ( get_query_var( 'tp_news' ) && is_singular( topersia_g_news_types() ) )
			? topersia_g_news_title( get_queried_object_id() ) . ' | ' . get_bloginfo( 'name' )
			: $title;
	}, 99 );

	/* خبرهای یک مکان برای پنل «دنبال‌شده‌ها»ی user-core.  P5-D238 */
	add_filter( 'topersia_entity_latest_news', function ( $items, $place_id, $limit ) {

		$q = topersia_g_news_query( $place_id, max( 1, (int) $limit ), 12 );

		foreach ( $q['ids'] as $id ) {
			$date    = (string) get_post_time( 'Y-m-d', false, $id );
			$items[] = array(
				'title'   => get_the_title( $id ),
				'url'     => (string) get_permalink( $id ),
				'date'    => $date,
				'date_fa' => topersia_g_date_fa( $date ),
			);
		}

		return $items;
	}, 10, 3 );

	/* صفحۀ هر خبر: منبع اصلی و دکمۀ منبع ترجیحی گوگل.  بند ۷٫۹ · P5-D207 */
	add_filter( 'the_content', function ( $content ) {

		if ( ! is_singular( 'tpc_news' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$id   = get_the_ID();
		$name = trim( (string) get_post_meta( $id, 'news_source_name', true ) );
		$url  = trim( (string) get_post_meta( $id, 'news_source_url', true ) );
		$src  = '';

		if ( '' !== $name && '' !== $url ) {
			$src = '<p class="tp-news-source">' . esc_html__( 'منبع اصلی:', 'topersia' ) . ' '
				. '<a href="' . esc_url( $url ) . '" rel="noopener nofollow" target="_blank">' . esc_html( $name ) . '</a></p>';
		}

		return $content . '<div class="tp-page tp-news-extra">' . $src . topersia_g_gps_link() . '</div>';
	}, 20 );

	/* ── آرشیو سراسری /news/ (دستۀ 48) ── */
	add_filter( 'query_vars', function ( $vars ) {
		$vars[] = 'tp_place';
		return $vars;
	} );

	/* کوئری اصلی: فقط نمایش‌پذیر، ۱۲ در صفحه؛ پالایش مکان و نوع با post__in، موضوع با tax_query. ۴۰۴ بیرون از بازه از خود وردپرس. */
	add_action( 'pre_get_posts', function ( $q ) {
		if ( is_admin() || ! $q->is_main_query() || ! $q->is_post_type_archive( 'tpc_news' ) || $q->is_feed() || ! topersia_g_news_on() ) {
			return;
		}
		$q->set( 'posts_per_page', TOPERSIA_NEWS_PER_PAGE );
		$q->set( 'post_status', 'publish' );
		$q->set( 'has_password', false );
		$q->set( 'orderby', 'date' );
		$q->set( 'order', 'DESC' );
		$q->set( 'meta_query', array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) );
		/* پالایش‌های P5-D468 — همه از یک منبع (topersia_g_news_args)، فقط GET. */
		$args = topersia_g_news_args();
		$pool = null;
		if ( $args['tp_place'] ) {
			$pool = topersia_g_news_archive_ids( $args['tp_place'] );
		} elseif ( absint( $q->get( 'tp_place' ) ) ) {
			$pool = array();   /* مکان ناموجود یا منتشرنشده: هیچ، نه همه */
		}
		if ( '' !== $args['tp_kind'] ) {
			$kind = topersia_g_news_kind_ids( $args['tp_kind'] );
			$pool = null === $pool ? $kind : array_values( array_intersect( $pool, $kind ) );
		}
		if ( null !== $pool ) {
			$q->set( 'post__in', $pool ? $pool : array( 0 ) );
		}
		if ( '' !== $args['tp_topic'] ) {
			$q->set( 'tax_query', array( array( 'taxonomy' => 'tpc_news_topic', 'field' => 'slug', 'terms' => array( $args['tp_topic'] ) ) ) );
		} elseif ( '' !== (string) $q->get( 'tp_topic' ) ) {
			$q->set( 'post__in', array( 0 ) );   /* موضوع ناموجود در این زبان */
		}
		if ( '' !== $args['tp_q'] ) {
			/* جست‌وجوی ساده در عنوان، چکیده و متن؛ ی/ک عربی در صفحۀ فارسی یکی می‌شود. is_search روشن نمی‌شود. */
			$term = 'fa' === topersia_g_page_lang() ? strtr( $args['tp_q'], array( "ي" => "ی", "ى" => "ی", "ك" => "ک" ) ) : $args['tp_q'];   /* i18n:skip — حرف عربی، نه رشتۀ رابط */
			$q->set( 's', $term );
		}
		if ( 'old' === $args['tp_sort'] ) {
			$q->set( 'order', 'ASC' );
		}
	} );

	add_filter( 'template_include', function ( $template ) {
		if ( ! topersia_g_news_is_archive() ) {
			return $template;
		}
		$file = topersia_render_path() . 'templates/news-archive.php';
		return is_file( $file ) ? $file : $template;
	}, 99 );

	add_action( 'wp_enqueue_scripts', function () {
		if ( topersia_g_news_is_archive() && function_exists( 'topersia_render_url' ) ) {
			wp_enqueue_style( 'topersia-g-news', topersia_render_url() . 'assets/topersia-g-news.css', array( 'topersia-g-template' ), topersia_render_asset_version( 'assets/topersia-g-news.css' ) );
		}
	}, 30 );

	add_filter( 'wp_robots', function ( $robots ) {
		if ( topersia_g_news_is_archive() && topersia_g_news_archive_thin() ) {
			unset( $robots['index'], $robots['max-image-preview'] );
			$robots['noindex'] = true;
			if ( empty( $robots['nofollow'] ) ) {
				$robots['follow'] = true;
			}
		}
		return $robots;
	}, 99 );

	add_filter( 'rank_math/frontend/robots', function ( $robots ) {
		if ( topersia_g_news_is_archive() && topersia_g_news_archive_thin() ) {
			$robots['index'] = 'noindex';
			if ( ! isset( $robots['follow'] ) || 'nofollow' !== $robots['follow'] ) {
				$robots['follow'] = 'follow';
			}
		}
		return $robots;
	}, 99 );

	/* canonical: صفحۀ N خودش؛ هر حالت پالایش‌شده به نشانی بی‌پالایش.  P5-D468 */
	$topersia_news_arch_url = function ( $url ) {
		return topersia_g_news_is_archive() ? topersia_g_news_archive_url( topersia_g_news_filtered() ? 1 : max( 1, (int) get_query_var( 'paged' ) ) ) : $url;
	};
	add_filter( 'get_canonical_url', $topersia_news_arch_url, 99 );
	add_filter( 'rank_math/frontend/canonical', $topersia_news_arch_url, 99 );

	$topersia_news_arch_title = function () {
		$place = topersia_g_news_filter_place();
		return $place ? topersia_g_news_title( $place ) : __( 'اخبار گردشگری ایران', 'topersia' );
	};
	add_filter( 'document_title_parts', function ( $parts ) use ( $topersia_news_arch_title ) {
		if ( topersia_g_news_is_archive() ) {
			$parts['title'] = $topersia_news_arch_title();
		}
		return $parts;
	}, 99 );
	add_filter( 'rank_math/frontend/title', function ( $title ) use ( $topersia_news_arch_title ) {
		return topersia_g_news_is_archive() ? $topersia_news_arch_title() . ' | ' . get_bloginfo( 'name' ) : $title;
	}, 99 );
}
