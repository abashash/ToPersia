<?php
/**
 * تک‌خبر tpc_news — دستۀ 50 (P5-D541): همان سبک همۀ صفحه‌های خبر.
 *
 *   سربرگ     تصویر خود خبر ← تصویر مکان مرتبط (هرگز نمونه) ← گرادیان · مسیر راهنما · H1
 *             · موضوع، تاریخ، زمان مطالعه، منبع با پیوند بیرونی
 *   نشان‌ها    مکان‌های مرتبطِ منتشرشده، هر کدام پیوند
 *   متن       the_content — صافی همان پیوند منبع ترجیحی گوگل را می‌افزاید (منبع اینجا در سربرگ است)
 *   مرتبط     «خبرهای مرتبط» با همان کارت آرشیو؛ نبود ← پنهان
 *
 * اسکیما (NewsArticle) دست نخورد: همان wp_head موتور topersia-schema.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_NEWS_SINGLE_TPL' ) ) {
	define( 'TOPERSIA_NEWS_SINGLE_TPL', true );
}

$tp_id     = (int) get_queried_object_id();
$tp_places = array_values( array_filter( topersia_g_published( topersia_g_news_places( $tp_id ) ), function ( $pid ) { return ! metadata_exists( 'post', $pid, '_tp_sample' ); } ) );
$tp_rel    = topersia_g_news_related( $tp_id, 4 );
$tp_src_n  = topersia_g_text( $tp_id, 'news_source_name' );
$tp_src_u  = topersia_g_text( $tp_id, 'news_source_url' );
$tp_crumbs = array(
	array( 'ToPersia', home_url( '/' ) ),
	array( __( 'اخبار', 'topersia' ), topersia_g_news_archive_url() ),
	array( get_the_title( $tp_id ), '' ),
);

$tp_extra = '<div class="tp-news-hero__meta">' . topersia_g_news_topic_badge( $tp_id ) . topersia_g_news_meta( $tp_id, 'tp-news-hero__when' )
	. ( '' !== $tp_src_n && '' !== $tp_src_u && wp_http_validate_url( $tp_src_u )
		? '<p class="tp-news-source">' . esc_html__( 'منبع اصلی:', 'topersia' ) . ' <a href="' . esc_url( $tp_src_u ) . '" rel="noopener nofollow" target="_blank">' . esc_html( $tp_src_n ) . '</a></p>'
		: '' )
	. '</div>';

get_header();

?>
<div class="tp-page tp-news-page tp-news-single" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
	<main id="tp-main" class="tp-main">
		<?php echo topersia_g_news_hero( topersia_g_news_hero_img( topersia_g_news_image( $tp_id ) ), $tp_crumbs, get_the_title( $tp_id ), topersia_g_news_sum( $tp_id ), $tp_extra ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="tp-wrap">
			<?php if ( $tp_places ) : ?>
				<nav class="tp-news-places" aria-label="<?php echo esc_attr__( 'مکان‌های مرتبط', 'topersia' ); ?>">
					<ul>
						<?php foreach ( $tp_places as $tp_pid ) : ?>
							<li><a href="<?php echo esc_url( (string) get_permalink( $tp_pid ) ); ?>"><?php echo esc_html( topersia_g_title( $tp_pid ) ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<article class="tp-news-body">
				<?php
				while ( have_posts() ) {
					the_post();
					the_content();
				}
				?>
			</article>

			<?php if ( $tp_rel ) : ?>
				<section class="tp-news-latest tp-news-related" aria-labelledby="tp-news-rel-h">
					<div class="tp-news-latest__head">
						<h2 id="tp-news-rel-h"><?php echo esc_html__( 'خبرهای مرتبط', 'topersia' ); ?></h2>
						<a class="tp-more tp-news-all" href="<?php echo esc_url( topersia_g_news_archive_url() ); ?>"><?php echo esc_html__( 'همۀ اخبار', 'topersia' ); ?></a>
					</div>
					<div class="tp-news-grid">
						<?php foreach ( $tp_rel as $tp_one ) { echo topersia_g_news_archive_card( $tp_one ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</main>
</div>
<?php

get_footer();
