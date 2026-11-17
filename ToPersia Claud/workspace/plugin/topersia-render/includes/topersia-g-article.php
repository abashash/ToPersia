<?php
/**
 * مقالۀ گردشگری — `tpc_article` · دستۀ 52 بند ۱۱ · P5-D567
 *
 * مقاله Content Type هم‌مدل خبر است و کد جدا ندارد: همۀ توابع `topersia_g_news_*` با «سوییچ نوع»
 * (`topersia_g_news_pt()` · `topersia_g_content_as()` در topersia-g-news.php) روی مقاله هم کار می‌کنند —
 * رابطه با استان/شهر/روستا/جزیره، تجمیع نیا (`_tp_article_ancestor`)، موضوع مشترک `tpc_news_topic`،
 * برچسب `tpc_tag`، زمان مطالعه، کارت‌ها، فهرست هر مکان (/…/articles/) و صفحۀ تکی.
 * تفاوت‌ها: بی فیلدهای منبع خبر؛ `article_author_name` (نویسنده/ناشر) و `article_updated_date` (به‌روزرسانی).
 *
 * این‌جا فقط آنچه ویژۀ مقاله است: برچسب‌های رابط به‌ازای نوع، سطر «به‌روزرسانی · نویسنده»، و ستون صفحۀ اول.
 * مدل داده فقط محلی است (scripts/tp-local-52-articles.php)؛ `tpc_article` ثبت نشده باشد هیچ‌چیز این‌جا کار نمی‌کند.
 * پیش از زنده: ممیزی NotebookLM.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'TP_PLUGIN_DIR' ) ) {
	return;
}

/** آیا مدل مقاله روی این سایت هست. */
if ( ! function_exists( 'topersia_g_article_on' ) ) {
	function topersia_g_article_on() {
		return function_exists( 'post_type_exists' ) && post_type_exists( 'tpc_article' );
	}
}

/**
 * برچسب رابط به‌ازای نوع محتوای جاری (خبر یا مقاله) — یک منبع برای قالب‌های مشترک.
 *
 * @param string $key crumb · latest · all · empty · none · related · pages · side
 */
if ( ! function_exists( 'topersia_g_news_l' ) ) {
	function topersia_g_news_l( $key ) {

		$article = function_exists( 'topersia_g_news_is_article' ) && topersia_g_news_is_article();

		$map = $article ? array(
			'crumb'   => __( 'مقالات', 'topersia' ),
			'latest'  => __( 'جدیدترین مقاله‌ها', 'topersia' ),
			'all'     => __( 'همۀ مقالات', 'topersia' ),
			'empty'   => __( 'هنوز مقاله‌ای برای این مکان نیست.', 'topersia' ),
			'none'    => __( 'هنوز مقاله‌ای منتشر نشده است.', 'topersia' ),
			'related' => __( 'مقاله‌های مرتبط', 'topersia' ),
			'pages'   => __( 'صفحه‌های مقاله', 'topersia' ),
			'side'    => __( 'تازه‌ترین مقاله‌ها', 'topersia' ),
		) : array(
			'crumb'   => __( 'اخبار', 'topersia' ),
			'latest'  => __( 'جدیدترین خبرها', 'topersia' ),
			'all'     => __( 'همۀ اخبار', 'topersia' ),
			'empty'   => __( 'هنوز خبری برای این مکان نیست.', 'topersia' ),
			'none'    => __( 'هنوز خبری منتشر نشده است.', 'topersia' ),
			'related' => __( 'خبرهای مرتبط', 'topersia' ),
			'pages'   => __( 'صفحه‌های خبر', 'topersia' ),
			'side'    => __( 'تازه‌ترین خبرها', 'topersia' ),
		);

		return isset( $map[ $key ] ) ? $map[ $key ] : '';
	}
}

/** تاریخ به‌روزرسانی مقاله (Y-m-d) — فقط اگر معتبر و دیرتر از انتشار است؛ وگرنه تهی. */
if ( ! function_exists( 'topersia_g_article_updated' ) ) {
	function topersia_g_article_updated( $id ) {
		$raw = function_exists( 'topersia_g_raw' ) ? topersia_g_raw( $id, 'article_updated_date' ) : '';
		$iso = ( is_scalar( $raw ) && function_exists( 'topersia_g_date_iso' ) ) ? (string) topersia_g_date_iso( $raw ) : '';
		$pub = (string) get_post_time( 'Y-m-d', false, $id );
		return ( '' !== $iso && $iso > $pub ) ? $iso : '';
	}
}

/** سطر «به‌روزرسانی: … · نویسنده: …» زیر فرادادۀ مقاله؛ بی هر دو ← تهی. */
if ( ! function_exists( 'topersia_g_article_byline' ) ) {
	function topersia_g_article_byline( $id ) {

		if ( 'tpc_article' !== get_post_type( $id ) ) {
			return '';
		}

		$upd  = topersia_g_article_updated( $id );
		$auth = function_exists( 'topersia_g_text' ) ? trim( wp_strip_all_tags( (string) topersia_g_text( $id, 'article_author_name' ) ) ) : '';
		$out  = '';

		if ( '' !== $upd ) {
			/* translators: %s: تاریخ */
			$out .= '<span class="tp-news-upd">' . sprintf( esc_html__( 'به‌روزرسانی: %s', 'topersia' ), '<time datetime="' . esc_attr( $upd ) . '">' . esc_html( topersia_g_date_fa( $upd ) ) . '</time>' ) . '</span>';
		}
		if ( '' !== $auth ) {
			/* translators: %s: نام نویسنده یا ناشر */
			$out .= '<span class="tp-news-by">' . esc_html( sprintf( __( 'نویسنده: %s', 'topersia' ), $auth ) ) . '</span>';
		}

		return '' !== $out ? '<p class="tp-news-hero__when tp-news-byline">' . $out . '</p>' : '';
	}
}

/** تازه‌ترین شناسه‌های نمایش‌پذیر یک نوع محتوا (زبان جاری، غیرنمونه؛ نمایشی فقط در پیش‌نمایش). */
if ( ! function_exists( 'topersia_g_content_latest' ) ) {
	function topersia_g_content_latest( $type, $limit, $skip = 0 ) {

		if ( ! function_exists( 'post_type_exists' ) || ! post_type_exists( $type ) ) {
			return array();
		}

		$ids = get_posts( array(
			'post_type'        => $type,
			'post_status'      => 'publish',
			'has_password'     => false,
			'posts_per_page'   => (int) $limit + ( $skip ? 1 : 0 ),
			'fields'           => 'ids',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_query'       => topersia_g_sample_mq(),
		) );

		$ids = array_values( array_diff( topersia_g_published( array_map( 'absint', (array) $ids ) ), array( (int) $skip ) ) );

		return array_slice( $ids, 0, (int) $limit );
	}
}

/* ── صفحۀ تکی خبر و مقاله — اسکلت صفحۀ استان (P5-D573، جایگزین چیدمان P5-D568) ──────────────
 * ستون کناری (`aside.tp-rail`) به ترتیب: جعبۀ اشتراک/ذخیره/دنبال‌کردن همان صفحه‌های موجودیت (`topersia_g_sc_actionbar`، بی کد تازه) ·
 * موضوع و برچسب‌ها · مکان‌های مرتبط با کارت کوچک · تازه‌ترین‌ها (۵ مورد) · «منبع و پیوند اصلی». جعبۀ بی‌داده ساخته نمی‌شود.
 */

if ( ! defined( 'TOPERSIA_NEWS_ASIDE_LATEST' ) ) {
	define( 'TOPERSIA_NEWS_ASIDE_LATEST', 5 );
}

/** سطر وسط‌چین «موضوع · تاریخ · زمان مطالعه» زیر عنوان — هر بخش فقط اگر هست، با جداکنندۀ صریح میان‌شان. */
if ( ! function_exists( 'topersia_g_news_metaline' ) ) {
	function topersia_g_news_metaline( $id ) {

		$parts = array();
		$term  = topersia_g_news_topic_of( $id );
		$date  = (string) get_post_time( 'Y-m-d', false, $id );
		$min   = topersia_g_news_read_minutes( $id );

		if ( $term ) {
			$parts[] = '<span class="tp-news-metaline__topic">' . esc_html( $term->name ) . '</span>';
		}
		if ( '' !== $date ) {
			$parts[] = '<time datetime="' . esc_attr( $date ) . '">' . esc_html( topersia_g_date_fa( $date ) ) . '</time>';
		}
		if ( $min ) {
			/* translators: %s: شمار دقیقه */
			$parts[] = '<span class="tp-news-read">' . esc_html( sprintf( __( '%s دقیقه مطالعه', 'topersia' ), topersia_g_fa( $min ) ) ) . '</span>';
		}

		if ( ! $parts ) {
			return '';
		}

		return '<p class="tp-news-metaline">' . implode( '<span class="tp-news-metaline__sep" aria-hidden="true">·</span>', $parts ) . '</p>'
			. topersia_g_demo_badge( $id );
	}
}

/**
 * یک کارت ستون کناری — همان پوستۀ کارت صفحۀ موجودیت (`topersia_g_section`: section.tp-rail-card با سرفصل h3)؛
 * شناسه `tp-{key}` لنگر نوار بخش‌هاست. بدنۀ تهی ← هیچ.  P5-D573
 */
if ( ! function_exists( 'topersia_g_news_box' ) ) {
	function topersia_g_news_box( $key, $title, $body ) {
		if ( '' === trim( (string) $body ) ) {
			return '';
		}
		return topersia_g_section( 'tp-' . $key, $title, '', $body, 'tp-rail-card tp-news-box tp-news-box--' . $key );
	}
}

/** جعبۀ «موضوع و برچسب‌ها» — موضوع پیوند به آرشیو همان نوع با همان موضوع؛ برچسب‌ها همان بلوک صفحه‌های موجودیت. */
if ( ! function_exists( 'topersia_g_news_box_topics' ) ) {
	function topersia_g_news_box_topics( $id ) {

		$out   = '';
		$terms = taxonomy_exists( 'tpc_news_topic' ) ? wp_get_post_terms( $id, 'tpc_news_topic' ) : array();

		foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
			$out .= '<li><a class="tp-tags__a" href="' . esc_url( add_query_arg( 'tp_topic', rawurlencode( $t->slug ), topersia_g_news_archive_url() ) ) . '">' . esc_html( $t->name ) . '</a></li>';
		}

		$out  = '' !== $out ? '<ul class="tp-tags__list tp-news-box__topics">' . $out . '</ul>' : '';
		$out .= function_exists( 'topersia_g_tags_html' ) ? topersia_g_tags_html( $id ) : '';

		return topersia_g_news_box( 'topics', __( 'موضوع و برچسب‌ها', 'topersia' ), $out );
	}
}

/** جعبۀ «مکان‌های مرتبط» — کارت کوچک: بندانگشتی (یا آیکون)، نام، نوع؛ فقط منتشرشده و غیرنمونه. */
if ( ! function_exists( 'topersia_g_news_box_places' ) ) {
	function topersia_g_news_box_places( $id ) {

		$ref = function_exists( 'topersia_g_kind_labels' ) ? topersia_g_kind_labels() : array();
		$li  = '';

		foreach ( topersia_g_published( topersia_g_news_places( $id ) ) as $pid ) {

			if ( topersia_g_sample_hidden( $pid ) ) {
				continue;
			}

			$att  = topersia_g_news_real_img( topersia_g_card_image( $pid ) );
			$type = (string) get_post_type( $pid );
			$img  = $att ? (string) wp_get_attachment_image( $att, 'thumbnail', false, array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '56px' ) ) : '';

			$li .= '<li><a class="tp-news-place" href="' . esc_url( (string) get_permalink( $pid ) ) . '">'
				. '<span class="tp-news-place__media"' . ( '' !== $img ? '' : ' aria-hidden="true"' ) . '>' . ( '' !== $img ? $img : topersia_g_icon( 'pin' ) ) . '</span>'
				. '<span class="tp-news-place__txt"><b>' . esc_html( topersia_g_title( $pid ) ) . '</b>'
				. ( isset( $ref[ $type ] ) ? '<small>' . esc_html( topersia_g_t( $ref[ $type ] ) ) . '</small>' : '' ) . '</span></a></li>';
		}

		return topersia_g_news_box( 'rel-places', __( 'مکان‌های مرتبط', 'topersia' ), '' !== $li ? '<ul class="tp-news-box__places">' . $li . '</ul>' : '' );
	}
}

/** جعبۀ «تازه‌ترین خبرها/مقاله‌ها» — تا TOPERSIA_NEWS_ASIDE_LATEST مورد همان نوع، بی خود این صفحه. */
if ( ! function_exists( 'topersia_g_news_box_latest' ) ) {
	function topersia_g_news_box_latest( $id ) {

		$li = '';

		foreach ( topersia_g_content_latest( topersia_g_news_pt(), TOPERSIA_NEWS_ASIDE_LATEST, $id ) as $one ) {
			$date = (string) get_post_time( 'Y-m-d', false, $one );
			$li  .= '<li><a href="' . esc_url( (string) get_permalink( $one ) ) . '"><b>' . esc_html( get_the_title( $one ) ) . '</b>'
				. ( '' !== $date ? '<time datetime="' . esc_attr( $date ) . '">' . esc_html( topersia_g_date_fa( $date ) ) . '</time>' : '' ) . '</a></li>';
		}

		return topersia_g_news_box( 'latest', topersia_g_news_l( 'side' ), '' !== $li ? '<ul class="tp-news-box__latest">' . $li . '</ul>' : '' );
	}
}

/**
 * جعبۀ «منبع و پیوند اصلی» — انتهای ستون کناری، همان الگوی جعبۀ منابع صفحۀ استان/شهر (فهرست شماره‌دار، پیوند خروجی
 * با rel="nofollow noopener"): منبع اصلی خبر (news_source_name · news_source_url) و ردیف‌های source_references.
 */
if ( ! function_exists( 'topersia_g_news_box_source' ) ) {
	function topersia_g_news_box_source( $id ) {

		$items = array();
		$seen  = array();
		$rows  = array();

		$name = trim( wp_strip_all_tags( (string) topersia_g_text( $id, 'news_source_name' ) ) );
		$url  = trim( (string) topersia_g_text( $id, 'news_source_url' ) );

		if ( '' !== $name ) {
			$rows[] = array( $name, $url );
		}
		foreach ( array_slice( (array) topersia_g_rows( $id, 'source_references' ), 0, TOPERSIA_SOURCES_MAX ) as $row ) {
			$rows[] = array(
				isset( $row['source_name'] ) ? trim( wp_strip_all_tags( (string) $row['source_name'] ) ) : '',
				isset( $row['source_url'] ) ? trim( (string) $row['source_url'] ) : '',
			);
		}

		foreach ( $rows as $r ) {
			if ( '' === $r[0] || isset( $seen[ $r[0] . '|' . $r[1] ] ) ) {
				continue;
			}
			$seen[ $r[0] . '|' . $r[1] ] = true;
			$shown   = function_exists( 'topersia_g_lang_runs' ) ? topersia_g_lang_runs( $r[0] ) : esc_html( $r[0] );
			$ok      = '' !== $r[1] && wp_http_validate_url( $r[1] );
			$items[] = '<li>' . ( $ok ? '<a href="' . esc_url( $r[1] ) . '" rel="nofollow noopener" target="_blank">' . $shown . '</a>' : $shown ) . '</li>';
		}

		return topersia_g_news_box( 'sources', __( 'منبع و پیوند اصلی', 'topersia' ), $items ? '<div class="tp-sources-rail"><ol>' . implode( '', $items ) . '</ol></div>' : '' );
	}
}

/**
 * بخش‌های صفحۀ تکی به همان شکل `topersia_g_layout()` صفحۀ موجودیت (P5-D573): دو ستون `main` و `rail`،
 * هر عضو [ id، nav (برچسب نوار بخش‌ها یا تهی)، html ]. فقط بخش دارای داده می‌آید.
 *   main  متن (`#tp-text`) · نظرات (`#tp-reviews`، همان جزء صفحۀ استان، بی ستاره) · مرتبط‌ها (`#tp-rel-news`)
 *   rail  اشتراک/ذخیره/دنبال‌کردن (`#tp-actions`) · موضوع و برچسب‌ها · مکان‌های مرتبط (`#tp-rel-places`) ·
 *         تازه‌ترین‌ها · منبع (`#tp-sources`)
 *
 * @param int    $id      شناسۀ خبر یا مقاله
 * @param string $content HTML متن (خروجی the_content)
 */
if ( ! function_exists( 'topersia_g_news_single_zones' ) ) {
	function topersia_g_news_single_zones( $id, $content ) {

		$rel   = topersia_g_news_related( $id, 4 );
		$cards = '';

		foreach ( $rel as $one ) {
			$cards .= topersia_g_news_archive_card( $one );
		}

		$text = '' !== trim( (string) $content )
			? '<section id="tp-text" class="tp-section tp-news-text" aria-label="' . esc_attr__( 'متن', 'topersia' ) . '"><article class="tp-news-body">' . $content . '</article></section>'
			: '';

		$related = '' !== $cards
			? '<section id="tp-rel-news" class="tp-section tp-news-related" aria-labelledby="tp-rel-newsTitle">'
				. '<div class="tp-section-head"><div><h2 id="tp-rel-newsTitle">' . esc_html( topersia_g_news_l( 'related' ) ) . '</h2></div>'
				. '<a class="tp-more tp-news-all" href="' . esc_url( topersia_g_news_archive_url() ) . '">' . esc_html( topersia_g_news_l( 'all' ) ) . '</a></div>'
				. '<div class="tp-news-grid">' . $cards . '</div></section>'
			: '';

		$zones = array(
			'main' => array(
				array( 'tp-text', __( 'متن', 'topersia' ), $text ),
				array( 'tp-reviews', __( 'نظرات', 'topersia' ), function_exists( 'topersia_g_sc_reviews' ) ? topersia_g_sc_reviews( array( 'id' => $id ) ) : '' ),
				array( 'tp-rel-news', topersia_g_news_l( 'related' ), $related ),
			),
			'rail' => array(
				array( 'tp-actions', '', function_exists( 'topersia_g_sc_actionbar' ) ? topersia_g_sc_actionbar( array( 'id' => $id ) ) : '' ),
				array( 'tp-topics', '', topersia_g_news_box_topics( $id ) ),
				array( 'tp-rel-places', __( 'مکان‌های مرتبط', 'topersia' ), topersia_g_news_box_places( $id ) ),
				array( 'tp-latest', '', topersia_g_news_box_latest( $id ) ),
				array( 'tp-sources', __( 'منبع', 'topersia' ), topersia_g_news_box_source( $id ) ),
			),
		);

		foreach ( $zones as $col => $list ) {
			$zones[ $col ] = array_values( array_filter( $list, function ( $z ) {
				return '' !== trim( (string) $z[2] );
			} ) );
		}

		return $zones;
	}
}

/** ترتیب نوار بخش‌های صفحۀ تکی (P5-D573 بند ۱۱): متن · مکان‌های مرتبط · منبع · نظرات · مرتبط‌ها. */
if ( ! function_exists( 'topersia_g_news_single_nav_order' ) ) {
	function topersia_g_news_single_nav_order() {
		return array( 'tp-text', 'tp-rel-places', 'tp-sources', 'tp-reviews', 'tp-rel-news' );
	}
}

/**
 * بدنۀ صفحۀ تکی خبر و مقاله زیر سربرگ — همان اسکلت `topersia_g_sc_page()` صفحۀ استان (P5-D573):
 * `nav.tp-snav` ← `main.tp-main > .tp-wrap > .tp-content-grid > .tp-main-col + aside.tp-rail` ← نوار پایین موبایل و برگۀ بخش‌ها
 * (`topersia_g_mobilebar`، همان تابع). جعبۀ اشتراک در موبایل با همان اسکریپت «نوار کنش» به آغاز `main` می‌آید
 * (جای‌نگهدار `data-tp-actionbar-slot`؛ مسیر راهنمای این صفحه در سربرگ است و سربرگ دست نمی‌خورد).
 */
if ( ! function_exists( 'topersia_g_news_single' ) ) {
	function topersia_g_news_single( $id, $content ) {

		$zones    = topersia_g_news_single_zones( $id, $content );
		$cols     = array( 'main' => '', 'rail' => '' );
		$rendered = array();

		foreach ( $zones as $col => $list ) {
			foreach ( $list as $z ) {
				$cols[ $col ]     .= $z[2];
				$rendered[ $z[0] ] = array( 'col' => $col, 'nav' => $z[1] );
			}
		}

		$nav = array();

		foreach ( topersia_g_news_single_nav_order() as $zid ) {
			if ( isset( $rendered[ $zid ] ) && '' !== $rendered[ $zid ]['nav'] ) {
				$nav[ $zid ] = $rendered[ $zid ]['nav'];
			}
		}

		$snav = '';

		if ( count( $nav ) > 1 ) {
			$links = '';
			foreach ( $nav as $zid => $label ) {
				$links .= '<a href="#' . esc_attr( $zid ) . '">' . topersia_g_icon( topersia_g_section_icon( $zid ) ) . esc_html( $label ) . '</a>';
			}
			$snav = '<nav class="tp-snav" aria-label="' . esc_attr__( 'ناوبری بخش‌های صفحه', 'topersia' ) . '">'
				. '<div class="tp-wrap tp-snav__in" data-tp-no-prog>' . $links . '</div></nav>';
		}

		$body = '<main id="tp-main" class="tp-main"><div class="tp-wrap">'
			. '<div class="tp-actionbar-slot" data-tp-actionbar-slot></div>'
			. '<div class="tp-content-grid">'
			. '<div class="tp-main-col">' . $cols['main'] . '</div>'
			. ( '' !== $cols['rail'] ? '<aside class="tp-rail" aria-label="' . esc_attr__( 'اطلاعات مکمل', 'topersia' ) . '">' . $cols['rail'] . '</aside>' : '' )
			. '</div></div></main>';

		return $snav . $body . ( function_exists( 'topersia_g_mobilebar' ) ? topersia_g_mobilebar( $id, $nav, $rendered ) : '' );
	}
}

/** ستون «مقالات گردشگری» صفحۀ اول — سه مقالۀ تازه با همان کارت خبر و «مشاهدۀ همه» به /articles/ (P5-D562). */
if ( ! function_exists( 'topersia_g_home_articles_col' ) ) {
	function topersia_g_home_articles_col() {

		if ( ! topersia_g_article_on() || ! function_exists( 'topersia_g_home_section' ) ) {
			return '';
		}

		return topersia_g_content_as( 'tpc_article', function () {

			$cards = '';
			foreach ( topersia_g_content_latest( 'tpc_article', TOPERSIA_HOME_ROW_ITEMS ) as $id ) {
				$cards .= topersia_g_news_card( $id );
			}
			if ( '' === $cards ) {
				return '';
			}

			$all = (string) get_post_type_archive_link( 'tpc_article' );

			return topersia_g_home_section( 'tp-home-articles', __( 'مقالات گردشگری', 'topersia' ), '<div class="tp-news">' . $cards . '</div>',
				'' !== $all ? array( $all, __( 'مشاهدۀ همه', 'topersia' ) ) : array(), 'tp-home-col' );
		} );
	}
}
