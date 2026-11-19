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
		$when  = is_scalar( get_query_var( 'tp_when' ) ) ? (string) get_query_var( 'tp_when' ) : '';
		return array(
			'tp_place' => topersia_g_news_filter_place(),
			'tp_kind'  => in_array( $kind, array_keys( topersia_g_news_relations() ), true ) ? $kind : '',
			'tp_topic' => $topic ? $topic->slug : '',
			'tp_q'     => function_exists( 'mb_substr' ) ? mb_substr( $q, 0, 80 ) : substr( $q, 0, 80 ),
			'tp_sort'  => 'old' === get_query_var( 'tp_sort' ) ? 'old' : '',
			'tp_when'  => isset( topersia_g_news_when_map()[ $when ] ) ? $when : '',   /* P5-D542 */
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
		foreach ( array( 'tp_place', 'tp_loc', 'tp_kind', 'tp_topic', 'tp_q', 'tp_sort', 'tp_when' ) as $qv ) {
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

/** متن کوتاه ردیف فهرست — حداکثر $max نویسه، بریده در مرز واژه با «…» (CSS دو سطر می‌بندد؛ این وزن HTML را کم نگه می‌دارد). دستۀ 55 بند ۵ */
if ( ! function_exists( 'topersia_g_list_clip' ) ) {
	function topersia_g_list_clip( $text, $max = 180 ) {
		$text = trim( (string) preg_replace( '/\s+/u', ' ', (string) $text ) );
		if ( mb_strlen( $text ) <= $max ) {
			return $text;
		}
		$cut = mb_substr( $text, 0, $max );
		$sp  = mb_strrpos( $cut, ' ' );
		/* نشانه‌گذاری پایانی با regex یونیکد (U+060C ویرگول · U+061B نقطه‌ویرگول) — rtrim بایتی است و نویسۀ چندبایتی را می‌شکند */
		$cut = ( false !== $sp && $sp > $max / 2 ) ? mb_substr( $cut, 0, $sp ) : $cut;
		return (string) preg_replace( '/[\s,.:\x{060C}\x{061B}]+$/u', '', $cut ) . '…';
	}
}

/** سطر «موضوع · در مکان» ردیف فهرست خبر/مقاله — همان الگوی «نوع · در استان X» کاشی هاب؛ نمای شبکه پنهانش می‌کند (موضوع روی عکس است). دستۀ 55 بند ۵ */
if ( ! function_exists( 'topersia_g_news_card_sub' ) ) {
	function topersia_g_news_card_sub( $news_id ) {
		$parts = array();
		$term  = topersia_g_news_topic_of( $news_id );
		if ( $term ) {
			$parts[] = esc_html( $term->name );
		}
		$places = topersia_g_published( topersia_g_news_places( $news_id ) );
		if ( $places ) {
			$pid = (int) reset( $places );
			/* translators: %s: نام کامل مکان (پیوند) */
			$parts[] = sprintf( esc_html__( 'در %s', 'topersia' ), '<a href="' . esc_url( (string) get_permalink( $pid ) ) . '">' . esc_html( topersia_g_title( $pid ) ) . '</a>' );
		}
		return $parts ? '<p class="tp-news-card__sub">' . implode( '<span aria-hidden="true"> · </span>', $parts ) . '</p>' : '';
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
		/* P5-D548: نشان «نمونۀ نمایشی» کنار موضوع (فقط حالت پیش‌نمایش) */
		return ( $term ? '<span class="tp-news-topic">' . esc_html( $term->name ) . '</span>' : '' ) . topersia_g_demo_badge( $news_id );
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
		return topersia_g_news_hero_img( $img );
	}
}


/* ── اجزای مشترک همۀ صفحه‌های خبر — P5-D541 · دستۀ 50 ───────────────────
 * /news/ · /{نوع}/{نامک}/news/ · تک‌خبر tpc_news همه از همین توابع: سربرگ،
 * تراشه، فهرست کشویی، دکمۀ نما، صفحه‌بندی. بی کد تکراری در قالب‌ها.
 */

/** پیوست سربرگ تمام‌عرض؛ نبود ← تهی (سربرگ گرادیانی). */
if ( ! function_exists( 'topersia_g_news_hero_img' ) ) {
	function topersia_g_news_hero_img( $img ) {
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

/**
 * سربرگ مشترک: تصویر واقعی + پوشش تیره (P5-D187)، مسیر راهنما، H1، یک جمله، و افزودۀ اختیاری.
 *
 * @param string $img    خروجی topersia_g_news_hero_img() یا تهی
 * @param array  $crumbs [ [ برچسب, نشانی یا تهی برای صفحۀ جاری ], … ]
 */
if ( ! function_exists( 'topersia_g_news_hero' ) ) {
	function topersia_g_news_hero( $img, $crumbs, $title, $lead = '', $extra = '' ) {
		$c = array();
		foreach ( $crumbs as $cr ) {
			$c[] = '' !== (string) $cr[1]
				? '<a href="' . esc_url( $cr[1] ) . '">' . esc_html( $cr[0] ) . '</a>'
				: '<span aria-current="page">' . esc_html( $cr[0] ) . '</span>';
		}
		return '<header class="tp-news-hero' . ( '' === $img ? ' tp-news-hero--art' : '' ) . '" data-tp-news-swap="hero">'
			. $img
			. '<span class="tp-news-hero__scrim" aria-hidden="true"></span>'
			. '<div class="tp-wrap tp-news-hero__in">'
			. '<nav class="tp-crumb" aria-label="' . esc_attr__( 'مسیر راهنما', 'topersia' ) . '">' . implode( "<span>›</span>\n", $c ) . '</nav>'
			. '<h1>' . esc_html( $title ) . '</h1>'
			. ( '' !== $lead ? '<p class="tp-news-hero__lead">' . esc_html( $lead ) . '</p>' : '' )
			. $extra
			. '</div></header>';
	}
}

/**
 * تراشه‌ها — یک خط افقی اسکرول‌شونده، هرگز دو خط (P5-D542)؛ بی شمار (P5-D544).
 * پیکان‌های دسکتاپ پنهان‌اند تا JS سرریز را ببیند؛ بی JS خودِ نوار اسکرول می‌خورد.
 *
 * @param array  $items [ [ نام, نشانی, فعال؟, نامک ], … ] — کمتر از دو مورد ← هیچ
 * @param string $swap  کلید ناحیۀ تعویض بی‌رفرش (تهی ← درون ناحیۀ دیگری است)
 */
if ( ! function_exists( 'topersia_g_news_chips' ) ) {
	function topersia_g_news_chips( $items, $label, $swap = 'chips', $mod = '' ) {
		if ( count( $items ) < 2 ) {
			return '';
		}
		$a   = topersia_g_arrows();
		$out = '';
		foreach ( $items as $it ) {
			$out .= '<li><a data-tp-topic="' . esc_attr( (string) $it[3] ) . '" href="' . esc_url( $it[1] ) . '"' . ( $it[2] ? ' aria-current="page"' : '' ) . '>' . esc_html( $it[0] ) . '</a></li>';
		}
		$btn = function ( $k, $txt, $aria ) {
			return '<button type="button" class="tp-news-chips__nav tp-news-chips__nav--' . $k . '" data-tp-chips="' . $k . '" hidden aria-label="' . esc_attr( $aria ) . '"><span aria-hidden="true" dir="ltr">' . esc_html( $txt ) . '</span></button>';   /* dir=ltr: نویسۀ ‹ › در متن راست‌به‌چپ آینه می‌شد و جهت فلش وارونه دیده می‌شد (P5-D579) */
		};
		return '<nav class="tp-news-chips' . ( '' !== $mod ? ' tp-news-chips--' . esc_attr( $mod ) : '' ) . '" aria-label="' . esc_attr( $label ) . '" data-tp-news-chips data-tp-news-nav'
			. ( '' !== $swap ? ' data-tp-news-swap="' . esc_attr( $swap ) . '"' : '' ) . '>'
			. $btn( 'prev', $a['prev'], __( 'موضوع‌های قبلی', 'topersia' ) )
			. '<ul class="tp-news-chips__list" data-tp-prog>' . $out . '</ul>'   /* P5-D579: همان نوار پیشرفت سفارشی زیر ردیف تراشه (جایگزین استثنای P5-D559) */
			. $btn( 'next', $a['next'], __( 'موضوع‌های بعدی', 'topersia' ) )
			. '</nav>';
	}
}

/**
 * فهرست کشویی با برچسب — رنگ و پیکان از توکن (P5-D542).
 *
 * @param array $opts مقدار ← برچسب؛ یا برچسب گروه ← [ مقدار ← برچسب ]
 */
if ( ! function_exists( 'topersia_g_news_select' ) ) {
	function topersia_g_news_select( $id, $name, $label, $opts, $value, $form = '' ) {
		$opt = function ( $k, $v ) use ( $value ) {
			return '<option value="' . esc_attr( (string) $k ) . '"' . selected( (string) $value, (string) $k, false ) . '>' . esc_html( $v ) . '</option>';
		};
		$o = '';
		foreach ( $opts as $k => $v ) {
			if ( is_array( $v ) ) {
				$o .= '<optgroup label="' . esc_attr( (string) $k ) . '">';
				foreach ( $v as $k2 => $v2 ) {
					$o .= $opt( $k2, $v2 );
				}
				$o .= '</optgroup>';
			} else {
				$o .= $opt( $k, $v );
			}
		}
		return '<div class="tp-news-filter__f"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>'
			. '<span class="tp-news-sel"><select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . ( '' !== $form ? ' form="' . esc_attr( $form ) . '"' : '' ) . '>' . $o . '</select></span></div>';
	}
}

/** گزینه‌های ترتیب — یک منبع برای دو قالب. */
if ( ! function_exists( 'topersia_g_news_sort_options' ) ) {
	function topersia_g_news_sort_options() {
		return array( '' => __( 'جدیدترین', 'topersia' ), 'old' => __( 'قدیمی‌ترین', 'topersia' ) );
	}
}

/** بازۀ زمانی مجاز ← عبارت date_query روی post_date (P5-D542). */
if ( ! function_exists( 'topersia_g_news_when_map' ) ) {
	function topersia_g_news_when_map() {
		return array( '7d' => '7 days ago', '30d' => '30 days ago', '12m' => '12 months ago' );
	}
}

/** برچسب‌های بازۀ زمانی به زبان صفحه. */
if ( ! function_exists( 'topersia_g_news_when_options' ) ) {
	function topersia_g_news_when_options() {
		return array(
			''    => __( 'همۀ زمان‌ها', 'topersia' ),
			'7d'  => __( '۷ روز گذشته', 'topersia' ),
			'30d' => __( '۳۰ روز گذشته', 'topersia' ),
			'12m' => __( '۱۲ ماه گذشته', 'topersia' ),
		);
	}
}

/** خبرهای بالای آرشیو: یک برگزیده + دو ستون کناری، هر ستون TOPERSIA_NEWS_TOP_COL خبر (P5-D556). */
if ( ! defined( 'TOPERSIA_NEWS_TOP_COL' ) ) {
	define( 'TOPERSIA_NEWS_TOP_COL', 3 );
}
if ( ! defined( 'TOPERSIA_NEWS_TOP_COLS' ) ) {
	define( 'TOPERSIA_NEWS_TOP_COLS', 2 );
}

/** بیشینۀ خبرهای بالا (برگزیده + کناری‌ها). */
if ( ! function_exists( 'topersia_g_news_top_max' ) ) {
	function topersia_g_news_top_max() {
		return 1 + TOPERSIA_NEWS_TOP_COL * TOPERSIA_NEWS_TOP_COLS;
	}
}

/**
 * بلوک بالا — برگزیده و ستون‌های کناری؛ ستون بی‌خبر اصلاً ساخته نمی‌شود (بی جای خالی).
 * ظرف `data-tp-news-swap="top"` همیشه هست تا پالایش بی‌رفرش آن را خالی یا پر کند.
 *
 * @param int[] $ids خبرهای بالا به ترتیب (اولی برگزیده)؛ کمتر از دو ← بلوک تهی
 */
if ( ! function_exists( 'topersia_g_news_top' ) ) {
	function topersia_g_news_top( $ids ) {
		$ids = array_values( array_map( 'absint', (array) $ids ) );
		if ( count( $ids ) < 2 ) {
			return '<div class="tp-news-topwrap" data-tp-news-swap="top"></div>';
		}
		$cols = array_chunk( array_slice( $ids, 1, TOPERSIA_NEWS_TOP_COL * TOPERSIA_NEWS_TOP_COLS ), TOPERSIA_NEWS_TOP_COL );
		$side = '';
		foreach ( $cols as $col ) {
			$side .= '<ul class="tp-news-side">';
			foreach ( $col as $one ) {
				$side .= topersia_g_news_mini( $one );
			}
			$side .= '</ul>';
		}
		return '<div class="tp-news-topwrap" data-tp-news-swap="top">'
			. '<section class="tp-news-top tp-news-top--' . count( $cols ) . '" aria-labelledby="tp-news-top-h">'
			. '<h2 id="tp-news-top-h" class="tp-news-vh">' . esc_html__( 'خبرهای برگزیده', 'topersia' ) . '</h2>'
			. topersia_g_news_feature( $ids[0] ) . $side
			. '</section></div>';
	}
}

/**
 * ردیف پالایش‌های فعال — خطی جدا زیر کل بلوک پالایش؛ هر پالایش یک تراشۀ حذف‌شدنی + «پاک‌کردن همه» (P5-D557).
 * ظرف `data-tp-news-swap="active"` همیشه هست؛ بی پالایش ← پنهان و تهی. بی JS هم پیوندهای GET ساده‌اند.
 *
 * @param array  $items  [ [ برچسب, نشانی بی همین پالایش ], … ]
 * @param string $clear  نشانی بی هیچ پالایش
 * @param string $missed متن مکانی که پیدا نشد ('' هیچ)
 */
if ( ! function_exists( 'topersia_g_active_filters' ) ) {
	function topersia_g_active_filters( $items, $clear, $missed = '' ) {
		$out = '';
		foreach ( (array) $items as $it ) {
			$out .= '<li><a class="tp-actf__chip" href="' . esc_url( $it[1] ) . '" data-tp-news-nav aria-label="' . esc_attr( sprintf( /* translators: %s: پالایش */ __( 'حذف پالایش: %s', 'topersia' ), $it[0] ) ) . '">'
				. '<span>' . esc_html( $it[0] ) . '</span><span class="tp-actf__x" aria-hidden="true">×</span></a></li>';
		}
		$miss = '' !== $missed ? '<p class="tp-actf__miss" role="status">' . esc_html( sprintf( /* translators: %s: متن کاربر */ __( 'مکانی با نام «%s» پیدا نشد.', 'topersia' ), $missed ) ) . '</p>' : '';
		if ( '' === $out && '' === $miss ) {
			return '<div class="tp-actf" data-tp-news-swap="active" hidden></div>';
		}
		return '<div class="tp-actf" data-tp-news-swap="active">'
			. ( '' !== $out ? '<ul class="tp-actf__list" aria-label="' . esc_attr__( 'پالایش‌های فعال', 'topersia' ) . '">' . $out . '</ul>' : '' )
			. $miss
			. '<a class="tp-actf__clear" href="' . esc_url( $clear ) . '" data-tp-news-nav>' . esc_html__( 'پاک‌کردن همه', 'topersia' ) . '</a>'
			. '</div>';
	}
}

/** تراشه‌های پالایش فعال آرشیو خبر — مکان، نوع مکان، بازۀ زمانی، موضوع، جست‌وجو (ترتیب پالایش نیست). */
if ( ! function_exists( 'topersia_g_news_active_items' ) ) {
	function topersia_g_news_active_items( $args, $kinds, $topics ) {
		$items = array();
		$off   = array( 'tp_place' => '', 'tp_loc' => '' );
		if ( $args['tp_place'] ) {
			$items[] = array( topersia_g_title( $args['tp_place'] ), topersia_g_news_archive_link( 1, $off ) );
		}
		if ( '' !== $args['tp_kind'] && isset( $kinds[ $args['tp_kind'] ] ) ) {
			$items[] = array( $kinds[ $args['tp_kind'] ], topersia_g_news_archive_link( 1, array( 'tp_kind' => '' ) ) );
		}
		$when = topersia_g_news_when_options();
		if ( '' !== $args['tp_when'] && isset( $when[ $args['tp_when'] ] ) ) {
			$items[] = array( $when[ $args['tp_when'] ], topersia_g_news_archive_link( 1, array( 'tp_when' => '' ) ) );
		}
		foreach ( (array) $topics as $t ) {
			if ( $t->slug === $args['tp_topic'] ) {
				$items[] = array( $t->name, topersia_g_news_archive_link( 1, array( 'tp_topic' => '' ) ) );
			}
		}
		if ( '' !== $args['tp_q'] ) {
			$items[] = array( sprintf( /* translators: %s: عبارت جست‌وجو */ __( 'جست‌وجو: %s', 'topersia' ), $args['tp_q'] ), topersia_g_news_archive_link( 1, array( 'tp_q' => '' ) ) );
		}
		return $items;
	}
}

/** دکمۀ شبکه/فهرست — پنهان تا JS، و بی شبکه هم پنهان. */
if ( ! function_exists( 'topersia_g_news_view_toggle' ) ) {
	function topersia_g_news_view_toggle() {
		return '<div class="tp-news-view" role="group" aria-label="' . esc_attr__( 'شیوۀ نمایش', 'topersia' ) . '" hidden data-tp-news-view>'
			. '<button type="button" data-tp-view="grid" aria-pressed="true"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/></svg><span class="tp-news-vh">' . esc_html__( 'نمای شبکه‌ای', 'topersia' ) . '</span></button>'
			. '<button type="button" data-tp-view="list" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="5" cy="6" r="1.2"/><circle cx="5" cy="12" r="1.2"/><circle cx="5" cy="18" r="1.2"/></svg><span class="tp-news-vh">' . esc_html__( 'نمای فهرستی', 'topersia' ) . '</span></button>'
			. '</div>';
	}
}

/** صفحه‌بندی؛ $link( صفحه ) ← نشانی با پالایش جاری. */
if ( ! function_exists( 'topersia_g_news_pages' ) ) {
	function topersia_g_news_pages( $pages, $paged, $link, $label = '' ) {
		if ( $pages < 2 ) {
			return '';
		}
		$out = '';
		for ( $i = 1; $i <= $pages; $i ++ ) {
			$out .= $i === $paged
				? '<span aria-current="page">' . esc_html( topersia_g_fa( $i ) ) . '</span>'
				: '<a href="' . esc_url( $link( $i ) ) . '">' . esc_html( topersia_g_fa( $i ) ) . '</a>';
		}
		return '<nav class="tp-news-pages" aria-label="' . esc_attr( '' !== $label ? $label : __( 'صفحه‌های خبر', 'topersia' ) ) . '" data-tp-news-nav>' . $out . '</nav>';   /* هاب برچسب خودش را می‌دهد */
	}
}

/** حالت قطعه (P5-D546): پاسخ فقط ناحیه‌های تعویض‌پذیر، بی سربرگ و پاورقی سایت. */
if ( ! function_exists( 'topersia_g_news_partial' ) ) {
	function topersia_g_news_partial() {
		return '1' === (string) get_query_var( 'tp_partial' );
	}
}

/** سرآیند پاسخ قطعه — هرگز نمایه نشود، هرگز در کش مشترک نماند. همین‌جا پاسخ برای مسیر سبک ذخیره می‌شود (P5-D571). */
if ( ! function_exists( 'topersia_g_news_partial_headers' ) ) {
	function topersia_g_news_partial_headers() {
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow' );
			header( 'Cache-Control: no-store' );
			header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		}
		topersia_frag_store_begin();
	}
}

/* ── کش قطعۀ نتایج — P5-D571 (دستۀ 52 بند ۱۳) ─────────────────────────────
 * بالا آمدن وردپرس با همۀ افزونه‌ها خودش چند ثانیه است؛ پس پاسخ `?tp_partial=1` یک بار ساخته و در پرونده گذاشته می‌شود
 * و `fragment.php` (بی وردپرس) دفعۀ بعد همان را می‌دهد. کلید: میزبان + مسیر (زبان در مسیر است) + پارامترهای مجاز
 * (`includes/topersia-frag-key.php`). ابطال: ذخیره/حذف/تغییر وضعیت هر پست `tp_`/`tpc_`، تغییر رابطۀ جت‌انجین،
 * ویرایش واژه‌های موضوع و برچسب؛ و سقف عمر TOPERSIA_FRAG_TTL. فقط برای مهمان و فقط وقتی سایت پشت «به‌زودی» نیست.
 */
if ( ! function_exists( 'topersia_frag_dir' ) && is_readable( __DIR__ . '/topersia-frag-key.php' ) ) {
	require_once __DIR__ . '/topersia-frag-key.php';
}

/** نشانی مسیر سبک، یا تهی اگر پرونده‌اش نیست. */
if ( ! function_exists( 'topersia_frag_url' ) ) {
	function topersia_frag_url() {
		return ( function_exists( 'topersia_render_url' ) && function_exists( 'topersia_render_path' ) && is_file( topersia_render_path() . 'fragment.php' ) ) ? topersia_render_url() . 'fragment.php' : '';
	}
}

/** آیا پاسخ این درخواست کش‌پذیر است: مهمان، سایت عمومی، نه ۴۰۴. */
if ( ! function_exists( 'topersia_frag_cacheable' ) ) {
	function topersia_frag_cacheable() {
		if ( ! function_exists( 'topersia_frag_key' ) || ! defined( 'WP_CONTENT_DIR' ) ) {
			return false;
		}
		if ( is_user_logged_in() || is_404() || '' !== (string) get_option( 'elementor_maintenance_mode_mode', '' ) ) {
			return false;
		}
		return (bool) apply_filters( 'topersia_frag_cacheable', true );
	}
}

/** آغاز ضبط پاسخ قطعه — در پایان، اگر ناحیۀ نتیجه دارد، در پرونده می‌رود. */
if ( ! function_exists( 'topersia_frag_store_begin' ) ) {
	function topersia_frag_store_begin() {

		if ( ! topersia_frag_cacheable() ) {
			return;
		}

		/*
		 * کلید باید از همان ورودی‌هایی ساخته شود که رندر خوانده است (بازبینی امنیتی دستۀ 52): فقط GET، بی بدنۀ POST،
		 * و هر متغیر پالایش که وردپرس دیده (get_query_var — از هر منبع) باید عیناً همان مقدار `$_GET` باشد؛
		 * جست‌وجوی آزاد و نام مکان، از هر راهی آمده باشند، کش نمی‌شوند.
		 */
		if ( 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '' ) || ! empty( $_POST ) ) {   // phpcs:ignore WordPress.Security
			return;
		}
		foreach ( array( 'tp_q', 'tp_loc' ) as $qv ) {
			$v = get_query_var( $qv );
			if ( ! is_scalar( $v ) || '' !== trim( (string) $v ) ) {
				return;
			}
		}
		foreach ( topersia_frag_params() as $qv ) {
			$v   = get_query_var( $qv );
			$raw = isset( $_GET[ $qv ] ) ? wp_unslash( $_GET[ $qv ] ) : '';   // phpcs:ignore WordPress.Security
			if ( ! is_scalar( $v ) || ! is_scalar( $raw ) || trim( (string) $v ) !== trim( (string) $raw ) ) {
				return;
			}
		}

		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$key = topersia_frag_key( isset( $_SERVER['HTTP_HOST'] ) ? (string) wp_unslash( $_SERVER['HTTP_HOST'] ) : '', (string) strtok( $uri, '?' ), wp_unslash( $_GET ) );   // phpcs:ignore WordPress.Security

		if ( '' === $key ) {
			return;
		}

		/* شمار ساخت قطعۀ هر مکان در کش برای گرم‌کردن (بند ۱۹؛ شمارندۀ بازدید نیست): فقط پس از نوشتن واقعی قطعه، و نه برای
		   درخواست خود گرم‌کن (سرآیند X-TP-Warm فقط می‌تواند شمارش را حذف کند، نه بیفزاید). اعتبار مکان در topersia_frag_built. */
		$place = ( isset( $_SERVER['HTTP_X_TP_WARM'] ) || ! function_exists( 'topersia_frag_built' ) ) ? 0 : absint( get_query_var( 'tp_place' ) );

		ob_start( function ( $html ) use ( $key, $place ) {
			if ( false !== strpos( $html, 'data-tp-news-swap="results"' ) ) {
				$dir = topersia_frag_dir( WP_CONTENT_DIR );
				if ( wp_mkdir_p( $dir ) ) {
					$all = glob( $dir . '/*.html' );
					if ( is_array( $all ) && count( $all ) > TOPERSIA_FRAG_MAX ) {
						array_map( 'unlink', $all );
					}
					if ( ! is_file( $dir . '/index.html' ) ) {
						file_put_contents( $dir . '/index.html', '' );
					}
					if ( is_file( $dir . '/off' ) ) {
						return $html;   /* سایت در این میان پشت «به‌زودی» رفته */
					}
					if ( false !== file_put_contents( $dir . '/' . $key . '.html', $html, LOCK_EX ) && $place ) {
						topersia_frag_built( $place );
					}
				}
			}
			return $html;
		} );
	}
}

/**
 * نشان «خاموش» برای خواننده (fragment.php وردپرس ندارد و گزینه را نمی‌بیند): سایت پشت «به‌زودی» ← پروندۀ `off`
 * و پاک‌شدن همۀ قطعه‌ها؛ سایت عمومی ← برداشتن نشان. هنگام تغییر گزینۀ حالت نگهداری صدا می‌شود.
 */
if ( ! function_exists( 'topersia_frag_gate' ) ) {
	function topersia_frag_gate( $mode = null ) {
		if ( ! function_exists( 'topersia_frag_dir' ) || ! defined( 'WP_CONTENT_DIR' ) ) {
			return;
		}
		$mode = null === $mode ? (string) get_option( 'elementor_maintenance_mode_mode', '' ) : (string) $mode;
		$dir  = topersia_frag_dir( WP_CONTENT_DIR );
		topersia_frag_purge();
		if ( '' !== $mode ) {
			if ( wp_mkdir_p( $dir ) ) {
				file_put_contents( $dir . '/off', '1' );
			}
		} elseif ( is_file( $dir . '/off' ) ) {
			@unlink( $dir . '/off' );   // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
	}
}

/** امضای کد سازندۀ قطعه — نسخۀ افزونه + زمان و اندازۀ قالب‌ها و includeهای فهرست (stat ارزان؛ بی هش محتوا در هر درخواست). */
if ( ! function_exists( 'topersia_frag_code_sig' ) ) {
	function topersia_frag_code_sig() {
		if ( ! function_exists( 'topersia_render_path' ) || '' === (string) topersia_render_path() ) {
			return '';
		}
		$base = topersia_render_path();
		$sig  = defined( 'TOPERSIA_RENDER_VERSION' ) ? TOPERSIA_RENDER_VERSION : '';
		foreach ( array( 'templates/hub-archive.php', 'templates/news-archive.php', 'templates/article-archive.php', 'templates/news-list.php', 'includes/topersia-g-hub.php', 'includes/topersia-g-news-ui.php', 'includes/topersia-g-news.php' ) as $rel ) {
			$file = $base . $rel;
			$sig .= '|' . ( is_file( $file ) ? filemtime( $file ) . ':' . filesize( $file ) : '-' );
		}
		return md5( $sig );
	}
}

/** پاک‌کردن همۀ قطعه‌های کش‌شده (جز index.html). */
if ( ! function_exists( 'topersia_frag_purge' ) ) {
	function topersia_frag_purge() {
		if ( ! function_exists( 'topersia_frag_dir' ) || ! defined( 'WP_CONTENT_DIR' ) ) {
			return 0;
		}
		$n = 0;
		foreach ( (array) glob( topersia_frag_dir( WP_CONTENT_DIR ) . '/*.html' ) as $file ) {
			if ( 'index.html' !== basename( $file ) && @unlink( $file ) ) {   // phpcs:ignore WordPress.PHP.NoSilencedErrors
				$n ++;
			}
		}
		if ( function_exists( 'topersia_frag_warm_schedule' ) ) {
			topersia_frag_warm_schedule();
		}
		return $n;
	}
}

/* ── گرم‌کردن کش قطعه پس از ابطال — دستۀ 53 بند ۱۹ ──────────────────────────
 * ابطال (topersia_frag_purge) یک رویداد تکی WP-Cron سی ثانیه بعد می‌گذارد (تکراری‌ها یکی می‌شوند)؛ رویداد ترکیب‌های رایج را
 * پشت‌سرهم و مهمان‌وار درخواست می‌کند تا نخستین بازدیدکننده قطعۀ ساخته‌شده بگیرد:
 *   بی پالایش (هر سه زبان) · هر موضوع · ده مکانی که قطعه‌شان بیش از همه ساخته شده (topersia_frag_builds؛ بی آمار ← پرخبرترین‌ها).
 * سایت پشت «به‌زودی» ← هیچ (قطعه آن‌جا کش نمی‌شود). هیچ درخواستی بیرون از home_url نمی‌رود.
 */
if ( ! defined( 'TOPERSIA_FRAG_WARM_MAX' ) ) {
	define( 'TOPERSIA_FRAG_WARM_MAX', 30 );   /* سقف درخواست هر دور */
}

if ( ! function_exists( 'topersia_frag_warm_schedule' ) ) {
	/** رویداد تکی گرم‌کردن، اگر هنوز در صف نیست. */
	function topersia_frag_warm_schedule() {
		if ( ! function_exists( 'wp_next_scheduled' ) || '' !== (string) get_option( 'elementor_maintenance_mode_mode', '' ) ) {
			return false;
		}
		if ( wp_next_scheduled( 'topersia_frag_warm' ) ) {
			return true;
		}
		return (bool) wp_schedule_single_event( time() + 30, 'topersia_frag_warm' );
	}
}

if ( ! function_exists( 'topersia_frag_built' ) ) {
	/** یک ساخت قطعه برای مکان؛ فقط هنگام نبود پرونده صدا می‌شود، پس نوشتن option نادر است. سقف ۵۰ مکان. */
	function topersia_frag_built( $place_id ) {
		$place_id = absint( $place_id );
		if ( ! $place_id || ! function_exists( 'topersia_g_news_filter_options' ) ) {
			return;
		}
		/* فقط مکانی که در پالایش هست (منتشرشده، با خبر) — شناسۀ دلخواه نشانی چیزی نمی‌نویسد */
		$valid = false;
		foreach ( (array) topersia_g_news_filter_options() as $list ) {
			if ( is_array( $list ) && isset( $list[ $place_id ] ) ) {
				$valid = true;
				break;
			}
		}
		if ( ! $valid ) {
			return;
		}
		$builds = get_option( 'topersia_frag_builds', array() );
		$builds = is_array( $builds ) ? array_map( 'absint', $builds ) : array();
		$builds[ $place_id ] = ( isset( $builds[ $place_id ] ) ? $builds[ $place_id ] : 0 ) + 1;
		arsort( $builds );
		update_option( 'topersia_frag_builds', array_slice( $builds, 0, 50, true ), false );
	}
}

if ( ! function_exists( 'topersia_frag_warm_places' ) ) {
	/** ده مکان رایج: از آمار ساخت قطعه، وگرنه مکان‌های پالایش به ترتیب شمار خبر. فقط منتشرشده. */
	function topersia_frag_warm_places( $limit = 10 ) {
		$builds = get_option( 'topersia_frag_builds', array() );
		$ids  = is_array( $builds ) ? array_keys( $builds ) : array();
		if ( count( $ids ) < $limit && function_exists( 'topersia_g_news_filter_options' ) ) {
			$count = array();
			foreach ( topersia_g_news_filter_options() as $list ) {
				foreach ( array_keys( $list ) as $pid ) {
					$count[ $pid ] = count( topersia_g_news_archive_ids( $pid ) );
				}
			}
			arsort( $count );
			$ids = array_merge( $ids, array_keys( $count ) );
		}
		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
		return array_slice( function_exists( 'topersia_g_published' ) ? topersia_g_published( $ids ) : $ids, 0, $limit );
	}
}

if ( ! function_exists( 'topersia_frag_warm_urls' ) ) {
	/** نشانی‌های گرم‌کردن، به ترتیب اولویت، با tp_partial=1. */
	function topersia_frag_warm_urls() {
		if ( ! function_exists( 'topersia_g_news_archive_url' ) ) {
			return array();
		}
		$base = (string) topersia_g_news_archive_url();
		if ( '' === $base ) {
			return array();
		}
		$urls = array( add_query_arg( 'tp_partial', '1', $base ) );
		foreach ( array( 'en', 'ar' ) as $lang ) {
			$tr = (string) apply_filters( 'wpml_permalink', $base, $lang, true );
			if ( '' !== $tr && $tr !== $base ) {
				$urls[] = add_query_arg( 'tp_partial', '1', $tr );
			}
		}
		if ( taxonomy_exists( 'tpc_news_topic' ) ) {
			$terms = get_terms( array( 'taxonomy' => 'tpc_news_topic', 'hide_empty' => true, 'fields' => 'slugs' ) );
			foreach ( is_array( $terms ) ? $terms : array() as $slug ) {
				$urls[] = add_query_arg( array( 'tp_partial' => '1', 'tp_topic' => rawurlencode( (string) $slug ) ), $base );
			}
		}
		foreach ( topersia_frag_warm_places() as $pid ) {
			$urls[] = add_query_arg( array( 'tp_partial' => '1', 'tp_place' => (int) $pid ), $base );
		}
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		return array_slice( array_values( array_filter( array_unique( $urls ), function ( $u ) use ( $home ) {
			return $home === (string) wp_parse_url( $u, PHP_URL_HOST );
		} ) ), 0, TOPERSIA_FRAG_WARM_MAX );
	}
}

if ( ! function_exists( 'topersia_frag_warm_run' ) ) {
	/** دور گرم‌کردن — پشت‌سرهم، مهمان (بی کوکی)؛ برمی‌گرداند چند نشانی ۲۰۰ داد. */
	function topersia_frag_warm_run() {
		if ( '' !== (string) get_option( 'elementor_maintenance_mode_mode', '' ) ) {
			return 0;
		}
		$ok = 0;
		foreach ( topersia_frag_warm_urls() as $url ) {
			$r = wp_remote_get( $url, array( 'timeout' => 20, 'redirection' => 0, 'sslverify' => false, 'headers' => array( 'X-TP-Warm' => '1' ) ) );
			if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
				$ok ++;
			}
		}
		return $ok;
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'topersia_frag_warm', 'topersia_frag_warm_run' );
}

/** ویژگی‌های ناحیۀ نتیجه: پیام aria-live و عنوان سند، هر دو به زبان صفحه. */
if ( ! function_exists( 'topersia_g_news_results_attrs' ) ) {
	function topersia_g_news_results_attrs( $total ) {
		/* translators: %s: شمار خبر */
		$msg = sprintf( __( 'نتیجه: %s', 'topersia' ), topersia_g_fa( (int) $total ) );
		return ' data-tp-news-swap="results" data-tp-news-msg="' . esc_attr( $msg ) . '" data-tp-news-title="' . esc_attr( wp_get_document_title() ) . '"';
	}
}

/** ریشۀ صفحه: نشانی پایه برای JS، پیام خطای شبکه، جهت از زبان صفحه. */
if ( ! function_exists( 'topersia_g_news_root_attrs' ) ) {
	function topersia_g_news_root_attrs( $base ) {
		$frag = ( function_exists( 'topersia_frag_url' ) && function_exists( 'topersia_frag_cacheable' ) && topersia_frag_cacheable() ) ? topersia_frag_url() : '';
		return ' dir="' . esc_attr( is_rtl() ? 'rtl' : 'ltr' ) . '" data-tp-news-base="' . esc_attr( (string) wp_parse_url( $base, PHP_URL_PATH ) ) . '"'
			. ( '' !== $frag ? ' data-tp-news-frag="' . esc_url( $frag ) . '"' : '' )   /* مسیر سبک قطعۀ نتایج — P5-D571 */
			. ' data-tp-news-err="' . esc_attr__( 'بارگیری نشد؛ صفحه دوباره بار می‌شود.', 'topersia' ) . '"';
	}
}

/** خبرهای مرتبط با یک خبر: از مکان‌های منتشرشدۀ آن، بی خودش، تا $limit. */
if ( ! function_exists( 'topersia_g_news_related' ) ) {
	function topersia_g_news_related( $news_id, $limit = 4 ) {
		$out = array();
		foreach ( topersia_g_published( topersia_g_news_places( $news_id ) ) as $pid ) {
			$q = topersia_g_news_query( $pid, $limit + 1, 0 );
			foreach ( $q['ids'] as $id ) {
				if ( (int) $id !== (int) $news_id && ! in_array( (int) $id, $out, true ) ) {
					$out[] = (int) $id;
				}
			}
			if ( count( $out ) >= $limit ) {
				break;
			}
		}
		return array_slice( $out, 0, $limit );
	}
}

/* ── هوک‌ها ─────────────────────────────────────────────────────────── */

if ( function_exists( 'add_action' ) ) {

	add_action( 'init', function () {
		if ( ! topersia_news_topic_enabled() || ! function_exists( 'register_taxonomy' ) ) {
			return;
		}
		/* موضوع مشترک خبر و مقاله — P5-D567 */
		register_taxonomy( 'tpc_news_topic', topersia_g_content_types(), array(
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
	foreach ( topersia_g_content_types() as $topersia_ct ) {
		add_action( 'save_post_' . $topersia_ct, function ( $post_id ) {
			if ( ! wp_is_post_revision( $post_id ) ) {
				update_post_meta( $post_id, '_tp_read_minutes', topersia_g_news_read_minutes_calc( $post_id ) );
			}
		}, 98 );
	}

	add_filter( 'query_vars', function ( $vars ) {
		return array_merge( $vars, array( 'tp_kind', 'tp_topic', 'tp_q', 'tp_sort', 'tp_when', 'tp_partial' ) );
	} );

	/* ابطال کش قطعه (P5-D571): هر تغییر محتوای موجودیت یا خبر/مقاله، رابطه، و واژه‌های موضوع/برچسب */
	$topersia_frag_post = function ( $post_id ) {
		$type = (string) get_post_type( $post_id );
		if ( 0 === strpos( $type, 'tp_' ) || 0 === strpos( $type, 'tpc_' ) ) {
			topersia_frag_purge();
		}
	};
	/* حالت نگهداری («به‌زودی») عوض شد ← قطعه‌ها پاک و نشان خاموش/روشن برای fragment.php */
	add_action( 'update_option_elementor_maintenance_mode_mode', function ( $old, $new ) {
		topersia_frag_gate( is_scalar( $new ) ? (string) $new : '' );
	}, 10, 2 );
	add_action( 'add_option_elementor_maintenance_mode_mode', function ( $name, $value ) {
		topersia_frag_gate( is_scalar( $value ) ? (string) $value : '' );
	}, 10, 2 );
	add_action( 'delete_option_elementor_maintenance_mode_mode', function () {
		topersia_frag_gate( '' );
	} );
	/* نخستین بار پس از نصب، یا اگر گزینه بیرون از این قلاب‌ها عوض شده: وضعیت نشان با گزینه یکی شود */
	add_action( 'init', function () {
		if ( ! function_exists( 'topersia_frag_dir' ) || ! defined( 'WP_CONTENT_DIR' ) ) {
			return;
		}
		$on = '' !== (string) get_option( 'elementor_maintenance_mode_mode', '' );
		if ( $on !== is_file( topersia_frag_dir( WP_CONTENT_DIR ) . '/off' ) ) {
			topersia_frag_gate();
		}
	}, 50 );
	/* دستۀ 55 بند ۶: قطعۀ کش‌شده نشانه‌گذاری کد پیشین را دارد و با ذخیرۀ محتوا فقط باطل می‌شد، نه با استقرار کد تازه —
	   امضای پرونده‌های سازندۀ قطعه (topersia_frag_code_sig) عوض شد ← یک بار همه پاک و گرم‌کردن در صف (همان topersia_frag_purge) */
	add_action( 'init', function () {
		$sig = topersia_frag_code_sig();
		if ( '' === $sig || $sig === (string) get_option( 'topersia_frag_ver', '' ) ) {
			return;
		}
		update_option( 'topersia_frag_ver', $sig, true );
		topersia_frag_purge();
	}, 51 );

	add_action( 'save_post', $topersia_frag_post, 99 );
	add_action( 'before_delete_post', $topersia_frag_post, 99 );
	add_action( 'transition_post_status', function ( $new, $old, $post ) use ( $topersia_frag_post ) {
		if ( $new !== $old && is_object( $post ) ) {
			$topersia_frag_post( $post->ID );
		}
	}, 99, 3 );
	foreach ( array( 'jet-engine/relation/update/after', 'jet-engine/relation/delete/after', 'edited_tpc_news_topic', 'created_tpc_news_topic', 'delete_tpc_news_topic', 'edited_tpc_tag', 'created_tpc_tag', 'delete_tpc_tag' ) as $topersia_frag_hook ) {
		add_action( $topersia_frag_hook, 'topersia_frag_purge', 99, 0 );
	}
	add_action( 'set_object_terms', function ( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( in_array( $taxonomy, array( 'tpc_news_topic', 'tpc_tag' ), true ) ) {
			topersia_frag_purge();
		}
	}, 99, 4 );

	add_action( 'wp_enqueue_scripts', function () {
		$list = topersia_g_news_list_is() && is_singular( topersia_g_news_types() );
		$one  = is_singular( topersia_g_content_types() );   /* تک‌خبر و تک‌مقاله — همان سبک (P5-D541 · P5-D567) */
		if ( ! ( topersia_g_news_is_archive() || $list || $one ) || ! function_exists( 'topersia_render_url' ) ) {
			return;
		}
		if ( $list || $one || topersia_g_news_is_article() ) {
			wp_enqueue_style( 'topersia-g-news', topersia_render_url() . 'assets/topersia-g-news.css', array( 'topersia-g-template' ), topersia_render_asset_version( 'assets/topersia-g-news.css' ) );
		}
		if ( $one ) {
			return;   /* تک‌خبر پالایش ندارد */
		}
		wp_enqueue_script( 'topersia-g-news', topersia_render_url() . 'assets/topersia-g-news.js', array(), topersia_render_asset_version( 'assets/topersia-g-news.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		if ( topersia_g_news_is_archive() && ! topersia_g_news_is_article() ) {
			/* P5-D549: combobox مکان — فقط جایی که فیلد هست */
			wp_enqueue_script( 'topersia-g-loc', topersia_render_url() . 'assets/topersia-g-loc.js', array(), topersia_render_asset_version( 'assets/topersia-g-loc.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
	}, 31 );
}
