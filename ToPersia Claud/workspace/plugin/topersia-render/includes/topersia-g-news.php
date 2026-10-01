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
 * @return array ids · total
 */
if ( ! function_exists( 'topersia_g_news_query' ) ) {
	function topersia_g_news_query( $place_id, $limit = 4, $months = 12, $paged = 1 ) {

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
			'order'            => 'DESC',
			'posts_per_page'   => $limit ? (int) $limit : -1,
			'paged'            => max( 1, (int) $paged ),
			'fields'           => 'ids',
			'ignore_sticky_posts' => true,
		);

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
		$thumb = function_exists( 'get_the_post_thumbnail' ) ? (string) get_the_post_thumbnail( $news_id, 'medium', array(
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
		return $q['total'] < 3;
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
		return topersia_g_news_list_url( get_queried_object_id(), max( 1, (int) get_query_var( 'paged' ) ) );
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
}
