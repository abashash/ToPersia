<?php
/**
 * آرشیو خبر طبق الگو — P5-D534 · SPRINT-4.4 فاز ۶٫۱ (P5-D260) · دستۀ 49
 *
 * کنار topersia-g-news.php (مدل خبر، نیاها، آرشیو دستۀ 48) فقط آنچه الگو
 * `reference/news-archive-mockup.png` افزود:
 *
 *   موضوع            taxonomy `tpc_news_topic` — سلسله‌مراتبی، WPML Translate؛
 *                    بی آرشیو عمومی (فقط پالایش `?tp_topic=` روی /news/)
 *   زمان مطالعه      `_tp_read_minutes` هنگام ذخیره؛ WPML ignore (هر ترجمه خودش)
 *   پالایش‌ها         مکان · نوع مکان · موضوع · جست‌وجو · ترتیب — فقط GET؛
 *                    پالایش‌شده noindex با canonical به نشانی بی‌پالایش
 *   سربرگ            تصویر شاخص مکان پالایش یا کشور · مسیر راهنما · عنوان · یک جمله
 *   برگزیده          خبر اول با پوشش تیره (P5-D187) + سه خبر کناری؛ فقط صفحۀ ۱ بی‌پالایش
 *   تصویر کارت       خود خبر ← تصویر شاخص مکان مرتبط ← آیکون؛ هرگز نشانی بیرونی
 *
 * ساختار تازه (taxonomy) فقط محلی تا ممیزی NotebookLM — همان دروازۀ
 * `wp_get_environment_type()` جدول کش و تاکردن.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'TP_PLUGIN_DIR' ) ) {
	return;
}

/** آیا taxonomy موضوع خبر ثبت شود — پیش‌فرض فقط نسخۀ محلی. */
if ( ! function_exists( 'topersia_news_topic_enabled' ) ) {
	function topersia_news_topic_enabled() {
		$on = function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type();
		return function_exists( 'apply_filters' ) ? (bool) apply_filters( 'topersia_news_topic_enabled', $on ) : $on;
	}
}

/** زمان مطالعه — دقیقه، از شمار واژۀ متن (۲۰۰ واژه در دقیقه، کمینه ۱؛ بی متن صفر). */
if ( ! function_exists( 'topersia_g_news_read_minutes_calc' ) ) {
	function topersia_g_news_read_minutes_calc( $news_id ) {
		$text  = trim( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $news_id ) ) ) );
		$words = '' === $text ? array() : preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
		return $words ? max( 1, (int) ceil( count( $words ) / 200 ) ) : 0;
	}
}

/** زمان مطالعۀ ذخیره‌شده؛ صفر یعنی نامعلوم و چیزی چاپ نمی‌شود. */
if ( ! function_exists( 'topersia_g_news_read_minutes' ) ) {
	function topersia_g_news_read_minutes( $news_id ) {
		return absint( get_post_meta( $news_id, '_tp_read_minutes', true ) );
	}
}

/** موضوع اول یک خبر (زبان خود خبر) یا null. */
if ( ! function_exists( 'topersia_g_news_topic_of' ) ) {
	function topersia_g_news_topic_of( $news_id ) {
		if ( ! taxonomy_exists( 'tpc_news_topic' ) ) {
			return null;
		}
		$terms = wp_get_post_terms( $news_id, 'tpc_news_topic' );
		return ( ! is_wp_error( $terms ) && $terms ) ? $terms[0] : null;
	}
}

/** موضوع‌های زبان جاری که دست‌کم یک خبر نمایش‌پذیر دارند — تراشه، بی شمار. */
if ( ! function_exists( 'topersia_g_news_topics' ) ) {
	function topersia_g_news_topics() {
		if ( ! taxonomy_exists( 'tpc_news_topic' ) ) {
			return array();
		}
		$ids   = topersia_g_news_archive_ids();
		$terms = get_terms( array( 'taxonomy' => 'tpc_news_topic', 'hide_empty' => true, 'orderby' => 'term_id' ) );
		if ( is_wp_error( $terms ) || ! $ids ) {
			return array();
		}
		$out = array();
		foreach ( $terms as $term ) {
			/* شمار خود وردپرس نمونه و پیش‌نویس را هم می‌شمارد؛ فقط خبر نمایش‌پذیر.  P5-D247 */
			$has = get_objects_in_term( $term->term_id, 'tpc_news_topic' );
			if ( ! is_wp_error( $has ) && array_intersect( array_map( 'absint', $has ), $ids ) ) {
				$out[] = $term;
			}
		}
		return $out;
	}
}

/** موضوع‌های خبرهای یک مکان (صفحۀ خبرهای موجودیت) — بی شمار. */
if ( ! function_exists( 'topersia_g_news_place_topics' ) ) {
	function topersia_g_news_place_topics( $place_id ) {
		if ( ! taxonomy_exists( 'tpc_news_topic' ) ) {
			return array();
		}
		$q   = topersia_g_news_query( $place_id, 0, 0 );
		$out = array();
		foreach ( $q['ids'] as $id ) {
			$terms = wp_get_post_terms( $id, 'tpc_news_topic' );
			foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
				$out[ $term->term_id ] = $term;
			}
		}
		ksort( $out );
		return array_values( $out );
	}
}

/** موضوع پالایش (شیء term زبان جاری) یا null. */
if ( ! function_exists( 'topersia_g_news_filter_topic' ) ) {
	function topersia_g_news_filter_topic() {
		$slug = sanitize_title( (string) get_query_var( 'tp_topic' ) );
		if ( '' === $slug || ! taxonomy_exists( 'tpc_news_topic' ) ) {
			return null;
		}
		$term = get_term_by( 'slug', $slug, 'tpc_news_topic' );
		return ( $term && ! is_wp_error( $term ) ) ? $term : null;
	}
}

/** حالت پالایش جاری — یک منبع برای کوئری، قالب، noindex و canonical. */
if ( ! function_exists( 'topersia_g_news_args' ) ) {
	function topersia_g_news_args() {
		$kind  = (string) get_query_var( 'tp_kind' );
		$topic = topersia_g_news_filter_topic();
		$q     = trim( (string) preg_replace( '/\s+/u', ' ', sanitize_text_field( (string) get_query_var( 'tp_q' ) ) ) );
		return array(
			'tp_place' => topersia_g_news_filter_place(),
			'tp_kind'  => in_array( $kind, array_keys( topersia_g_news_relations() ), true ) ? $kind : '',
			'tp_topic' => $topic ? $topic->slug : '',
			'tp_q'     => function_exists( 'mb_substr' ) ? mb_substr( $q, 0, 80 ) : substr( $q, 0, 80 ),
			'tp_sort'  => 'old' === get_query_var( 'tp_sort' ) ? 'old' : '',
		);
	}
}

/** آیا هر پالایشی (از جمله ترتیب) روشن است. */
if ( ! function_exists( 'topersia_g_news_filtered' ) ) {
	function topersia_g_news_filtered() {
		if ( array_filter( topersia_g_news_args() ) ) {
			return true;
		}
		/* مقدار نامعتبر (موضوع یا مکان ناموجود) هم پالایش است: نتیجۀ تهی، noindex، پیام «با این پالایش». */
		foreach ( array( 'tp_place', 'tp_kind', 'tp_topic', 'tp_q', 'tp_sort' ) as $qv ) {
			$v = get_query_var( $qv );
			if ( ! is_scalar( $v ) || ( '' !== trim( (string) $v ) && '0' !== (string) $v ) ) {
				return true;
			}
		}
		return false;
	}
}

/** نشانی آرشیو با پالایش جاری، صفحۀ N و جای‌گذاری‌ها (مقدار تهی یعنی بی آن پالایش). */
if ( ! function_exists( 'topersia_g_news_archive_link' ) ) {
	function topersia_g_news_archive_link( $page = 1, $over = array() ) {
		$args = array_filter( array_merge( topersia_g_news_args(), $over ) );
		return add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $args ) ), topersia_g_news_archive_url( $page ) );
	}
}

/**
 * شناسۀ خبرهایی که مستقیم به نوعی مکان پیوند دارند — جدول رابطۀ JetEngine.
 * رابطه‌های خبر جدول اختصاصی ندارند (`jet_rel_default`)؛ صافی برای روز دیگر.
 */
if ( ! function_exists( 'topersia_g_news_kind_ids' ) ) {
	function topersia_g_news_kind_ids( $type ) {
		global $wpdb;
		$rels = topersia_g_news_relations();
		if ( ! isset( $rels[ $type ] ) || ! is_object( $wpdb ) ) {
			return array();
		}
		$table = (string) apply_filters( 'topersia_g_news_rel_table', $wpdb->prefix . 'jet_rel_default', $rels[ $type ] );
		$ids   = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT parent_object_id FROM {$table} WHERE rel_id = %d", $rels[ $type ] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array_values( array_intersect( topersia_g_news_archive_ids(), array_map( 'absint', (array) $ids ) ) );
	}
}

/** گزینه‌های «نوع مکان»: نوع‌هایی که دست‌کم یک خبر نمایش‌پذیر دارند ← برچسب به زبان صفحه. */
if ( ! function_exists( 'topersia_g_news_kind_options' ) ) {
	function topersia_g_news_kind_options() {
		$ref = topersia_g_kind_labels();
		$out = array();
		foreach ( array_keys( topersia_g_news_relations() ) as $type ) {
			if ( isset( $ref[ $type ] ) && topersia_g_news_kind_ids( $type ) ) {
				$out[ $type ] = topersia_g_t( $ref[ $type ] );
			}
		}
		return $out;
	}
}

/**
 * تراشۀ «شهرهای استان» — از `_tp_news_ancestor`: شهرهای دارای خبری که
 * استانِ مکان پالایش (یا استانِ شهر پالایش) نیای آن‌هاست.
 *
 * @return array شناسۀ شهر ← نام
 */
if ( ! function_exists( 'topersia_g_news_city_chips' ) ) {
	function topersia_g_news_city_chips( $place_id ) {
		if ( ! $place_id ) {
			return array();
		}
		$type  = (string) get_post_type( $place_id );
		$provs = 'tp_province' === $type ? array( $place_id ) : ( 'tp_city' === $type ? topersia_g_news_ancestors_of( $place_id ) : array() );
		$opts  = topersia_g_news_filter_options();
		$out   = array();
		foreach ( isset( $opts['tp_city'] ) ? $opts['tp_city'] : array() as $cid => $name ) {
			if ( array_intersect( $provs, topersia_g_news_ancestors_of( $cid ) ) ) {
				$out[ $cid ] = $name;
			}
		}
		return $out;
	}
}

/** پیوست تصویر واقعی: تصویر کتابخانۀ همین سایت و بی نشان نمونه (P5-D535)؛ وگرنه صفر. */
if ( ! function_exists( 'topersia_g_news_real_img' ) ) {
	function topersia_g_news_real_img( $att ) {
		$att = absint( $att );
		return ( $att && wp_attachment_is_image( $att ) && ! metadata_exists( 'post', $att, '_tp_sample' ) ) ? $att : 0;
	}
}

/**
 * شناسۀ پیوست تصویر کارت: خود خبر ← تصویر شاخص نخستین مکان منتشرشده و غیرنمونۀ مرتبط.
 * فقط پیوست کتابخانۀ همین سایت — هرگز نشانی بیرونی.
 */
if ( ! function_exists( 'topersia_g_news_image' ) ) {
	function topersia_g_news_image( $news_id ) {
		$img = topersia_g_news_real_img( topersia_g_card_image( $news_id ) );
		if ( $img ) {
			return $img;
		}
		foreach ( topersia_g_published( topersia_g_news_places( $news_id ) ) as $pid ) {
			$img = metadata_exists( 'post', $pid, '_tp_sample' ) ? 0 : topersia_g_news_real_img( topersia_g_card_image( $pid ) );
			if ( $img ) {
				return $img;
			}
		}
		return 0;
	}
}

/** آیکون روزنامه — جای تصویر وقتی هیچ نیست. */
if ( ! function_exists( 'topersia_g_news_icon' ) ) {
	function topersia_g_news_icon() {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">'
			. '<rect x="3" y="4" width="15" height="16" rx="2"/><path d="M18 8h3v10a2 2 0 0 1-2 2M7 8h7M7 12h7M7 16h4"/></svg>';
	}
}

/** خلاصۀ کارت: short_summary ← چکیده. */
if ( ! function_exists( 'topersia_g_news_sum' ) ) {
	function topersia_g_news_sum( $news_id ) {
		$sum = topersia_g_text( $news_id, 'short_summary' );
		if ( '' === $sum && function_exists( 'get_the_excerpt' ) ) {
			$sum = trim( wp_strip_all_tags( (string) get_the_excerpt( $news_id ) ) );
		}
		return $sum;
	}
}

/** سطر فراداده: تاریخ به زبان صفحه · زمان مطالعه. */
if ( ! function_exists( 'topersia_g_news_meta' ) ) {
	function topersia_g_news_meta( $news_id, $class ) {
		$date = (string) get_post_time( 'Y-m-d', false, $news_id );
		$min  = topersia_g_news_read_minutes( $news_id );
		$out  = ( '' !== $date ? '<time datetime="' . esc_attr( $date ) . '">' . esc_html( topersia_g_date_fa( $date ) ) . '</time>' : '' )
			/* translators: %s: شمار دقیقه */
			. ( $min ? '<span class="tp-news-read">' . esc_html( sprintf( __( '%s دقیقه مطالعه', 'topersia' ), topersia_g_fa( $min ) ) ) . '</span>' : '' );
		return '' !== $out ? '<p class="' . esc_attr( $class ) . '">' . $out . '</p>' : '';
	}
}

/** نشان موضوع یک خبر (متن، نه پیوند — کارت خودش پیوند دارد). */
if ( ! function_exists( 'topersia_g_news_topic_badge' ) ) {
	function topersia_g_news_topic_badge( $news_id ) {
		$term = topersia_g_news_topic_of( $news_id );
		return $term ? '<span class="tp-news-topic">' . esc_html( $term->name ) . '</span>' : '';
	}
}

/** تصویر یک کارت با اندازه‌های پاسخ‌گو؛ نبود ← رشتۀ تهی. */
if ( ! function_exists( 'topersia_g_news_pic' ) ) {
	function topersia_g_news_pic( $news_id, $size, $sizes, $eager = false ) {
		$img = topersia_g_news_image( $news_id );
		return ( $img && function_exists( 'wp_get_attachment_image' ) ) ? (string) wp_get_attachment_image( $img, $size, false, array(
			'alt'           => '',
			'loading'       => $eager ? 'eager' : 'lazy',
			'decoding'      => 'async',
			'sizes'         => $sizes,
			'fetchpriority' => $eager ? 'high' : 'auto',
		) ) : '';
	}
}

/** خبر برگزیده: تصویر تمام‌قد با پوشش تیره، موضوع، تیتر، خلاصه، فراداده. */
if ( ! function_exists( 'topersia_g_news_feature' ) ) {
	function topersia_g_news_feature( $news_id ) {
		$url = (string) get_permalink( $news_id );
		$pic = topersia_g_news_pic( $news_id, 'large', '(max-width: 1024px) 100vw, 60vw', true );
		$sum = topersia_g_news_sum( $news_id );
		return '<article class="tp-news-feat">'
			. ( '' !== $pic ? $pic : '<span class="tp-news-feat__art" aria-hidden="true">' . topersia_g_news_icon() . '</span>' )
			. '<span class="tp-news-feat__scrim" aria-hidden="true"></span>'
			. '<div class="tp-news-feat__body">'
			. topersia_g_news_topic_badge( $news_id )
			. '<h3 class="tp-news-feat__title"><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $news_id ) ) . '</a></h3>'
			. ( '' !== $sum ? '<p class="tp-news-feat__sum">' . esc_html( $sum ) . '</p>' : '' )
			. topersia_g_news_meta( $news_id, 'tp-news-feat__meta' )
			. '</div></article>';
	}
}

/** خبر کناری: بندانگشتی، موضوع، تیتر، خلاصۀ کوتاه، فراداده. */
if ( ! function_exists( 'topersia_g_news_mini' ) ) {
	function topersia_g_news_mini( $news_id ) {
		$url = (string) get_permalink( $news_id );
		$pic = topersia_g_news_pic( $news_id, 'medium', '128px' );
		$sum = topersia_g_news_sum( $news_id );
		return '<li><article class="tp-news-mini">'
			. '<a class="tp-news-mini__media' . ( '' === $pic ? ' tp-news-mini__media--icon' : '' ) . '" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">' . ( '' !== $pic ? $pic : topersia_g_news_icon() ) . '</a>'
			. '<div class="tp-news-mini__body">'
			. topersia_g_news_topic_badge( $news_id )
			. '<h3 class="tp-news-mini__title"><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $news_id ) ) . '</a></h3>'
			. ( '' !== $sum ? '<p class="tp-news-mini__sum">' . esc_html( $sum ) . '</p>' : '' )
			. topersia_g_news_meta( $news_id, 'tp-news-mini__meta' )
			. '</div></article></li>';
	}
}

/**
 * تصویر سربرگ آرشیو: تصویر شاخص مکان پالایش (غیرنمونه)؛ بی‌پالایش همان تصویر سربرگ
 * صفحۀ اول (`topersia_g_home_hero_att()` — تنظیم یا نخستین استان واقعی)؛ نبود ← تهی.
 */
if ( ! function_exists( 'topersia_g_news_hero_image' ) ) {
	function topersia_g_news_hero_image( $place_id ) {
		if ( $place_id ) {
			$img = metadata_exists( 'post', $place_id, '_tp_sample' ) ? 0 : topersia_g_news_real_img( topersia_g_card_image( $place_id ) );
		} else {
			$img = function_exists( 'topersia_g_home_hero_att' ) ? topersia_g_news_real_img( topersia_g_home_hero_att()[0] ) : 0;
		}
		return $img ? (string) wp_get_attachment_image( $img, 'full', false, array(
			'alt'           => '',
			'class'         => 'tp-news-hero__img',
			'loading'       => 'eager',
			'decoding'      => 'async',
			'fetchpriority' => 'high',
			'sizes'         => '100vw',
		) ) : '';
	}
}

/* ── هوک‌ها ─────────────────────────────────────────────────────────── */

if ( function_exists( 'add_action' ) ) {

	add_action( 'init', function () {
		if ( ! topersia_news_topic_enabled() || ! function_exists( 'register_taxonomy' ) ) {
			return;
		}
		register_taxonomy( 'tpc_news_topic', array( 'tpc_news' ), array(
			'labels'             => array(
				'name'          => __( 'موضوع‌های خبر', 'topersia' ),
				'singular_name' => __( 'موضوع خبر', 'topersia' ),
			),
			'hierarchical'       => true,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_rest'       => true,
			'show_admin_column'  => true,
			'query_var'          => false,
			'rewrite'            => false,
		) );
	}, 20 );

	/* زمان مطالعه هنگام ذخیره — پیش از نیاها (۹۹). */
	add_action( 'save_post_tpc_news', function ( $post_id ) {
		if ( ! wp_is_post_revision( $post_id ) ) {
			update_post_meta( $post_id, '_tp_read_minutes', topersia_g_news_read_minutes_calc( $post_id ) );
		}
	}, 98 );

	add_filter( 'query_vars', function ( $vars ) {
		return array_merge( $vars, array( 'tp_kind', 'tp_topic', 'tp_q', 'tp_sort' ) );
	} );

	add_action( 'wp_enqueue_scripts', function () {
		$list = get_query_var( 'tp_news' ) && is_singular( topersia_g_news_types() );
		if ( ! ( topersia_g_news_is_archive() || $list ) || ! function_exists( 'topersia_render_url' ) ) {
			return;
		}
		if ( $list ) {
			wp_enqueue_style( 'topersia-g-news', topersia_render_url() . 'assets/topersia-g-news.css', array( 'topersia-g-template' ), topersia_render_asset_version( 'assets/topersia-g-news.css' ) );
		}
		wp_enqueue_script( 'topersia-g-news', topersia_render_url() . 'assets/topersia-g-news.js', array(), topersia_render_asset_version( 'assets/topersia-g-news.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}, 31 );
}
