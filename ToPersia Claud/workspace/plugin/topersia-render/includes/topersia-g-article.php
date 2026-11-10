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
