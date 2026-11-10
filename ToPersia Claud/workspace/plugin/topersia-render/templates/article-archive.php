<?php
/**
 * آرشیو مقالات گردشگری — /articles/ · /articles/page/N/ — دستۀ 52 (P5-D567 · P5-D566)
 *
 * همان اجزای صفحه‌های خبر (topersia-g-news-ui): سربرگ تمام‌عرض، تراشۀ موضوع یک‌خطی، کارت یکسان، صفحه‌بندی، بی‌رفرش.
 * نوار ابزار فقط تراشه‌ها + مرتب‌سازی + «نمایش» + شبکه/فهرست — بی ردیف فیلتر (P5-D566).
 * کوئری اصلی را topersia-g-news.php محدود کرده: منتشرشده، هم‌زبان، غیرنمونه (نمایشی فقط در پیش‌نمایش)، موضوع و ترتیب از GET.
 * زیر ۳ مقاله یا پالایش‌شده ← noindex با canonical بی‌پالایش؛ اسکیما CollectionPage + ItemList از Article (topersia-schema).
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

$tp_ids   = array_map( 'absint', wp_list_pluck( (array) $wp_query->posts, 'ID' ) );
$tp_ids   = array_values( array_filter( topersia_g_published( $tp_ids ), function ( $id ) { return ! topersia_g_sample_hidden( $id ); } ) );
$tp_paged = max( 1, (int) get_query_var( 'paged' ) );
$tp_pages = (int) $wp_query->max_num_pages;
$tp_total = (int) $wp_query->found_posts;
$tp_args  = topersia_g_news_args();
$tp_on    = topersia_g_news_filtered();
$tp_title = topersia_g_news_title( 0 );
$tp_base  = topersia_g_news_archive_url();
$tp_part  = topersia_g_news_partial();

$tp_topic_items = array( array( __( 'همه', 'topersia' ), topersia_g_news_archive_link( 1, array( 'tp_topic' => '' ) ), '' === $tp_args['tp_topic'], '' ) );
foreach ( topersia_g_news_topics() as $tp_t ) {
	$tp_topic_items[] = array( $tp_t->name, topersia_g_news_archive_link( 1, array( 'tp_topic' => $tp_t->slug ) ), $tp_t->slug === $tp_args['tp_topic'], $tp_t->slug );
}

$tp_crumbs = array(
	array( 'ToPersia', home_url( '/' ) ),
	array( $tp_title, '' ),
);

if ( $tp_part ) {
	topersia_g_news_partial_headers();
} else {
	get_header();
}

?>
<div class="tp-page tp-news-page tp-news-place tp-article-archive"<?php echo topersia_g_news_root_attrs( $tp_base ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<main id="tp-main" class="tp-main">
		<?php echo topersia_g_news_hero( topersia_g_news_hero_image( 0 ), $tp_crumbs, $tp_title, topersia_g_news_lead( 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="tp-wrap">
			<section id="tp-news" class="tp-section" aria-label="<?php echo esc_attr( $tp_title ); ?>">
				<p class="tp-news-vh" role="status" aria-live="polite" data-tp-news-live></p>

				<div class="tp-news-bar">
					<?php echo topersia_g_news_chips( $tp_topic_items, __( 'موضوع‌ها', 'topersia' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="tp-news-bar__tools">
						<form class="tp-news-filter tp-news-filter--min" method="get" action="<?php echo esc_url( $tp_base ); ?>" data-tp-news-filter>
							<?php if ( '' !== $tp_args['tp_topic'] ) : ?><input type="hidden" name="tp_topic" value="<?php echo esc_attr( $tp_args['tp_topic'] ); ?>"><?php endif; ?>
							<?php echo topersia_g_news_select( 'tp-news-sort', 'tp_sort', __( 'مرتب‌سازی', 'topersia' ), topersia_g_news_sort_options(), $tp_args['tp_sort'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<div class="tp-news-filter__go"><button type="submit"><?php echo esc_html__( 'نمایش', 'topersia' ); ?></button></div>
						</form>
						<?php echo topersia_g_news_view_toggle(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>

				<div class="tp-news-results"<?php echo topersia_g_news_results_attrs( $tp_total ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php if ( $tp_ids ) : ?>
						<section class="tp-news-latest" aria-labelledby="tp-news-latest-h">
							<div class="tp-news-latest__head">
								<h2 id="tp-news-latest-h" tabindex="-1" data-tp-news-focus><?php echo esc_html( $tp_on ? __( 'نتیجۀ پالایش', 'topersia' ) : topersia_g_news_l( 'latest' ) ); ?></h2>
								<?php if ( $tp_on ) : ?>
									<a class="tp-more tp-news-all" href="<?php echo esc_url( $tp_base ); ?>" data-tp-news-nav><?php echo esc_html__( 'مشاهده همه', 'topersia' ); ?></a>
								<?php endif; ?>
							</div>
							<div class="tp-news-grid" data-tp-news-items>
								<?php foreach ( $tp_ids as $tp_one ) { echo topersia_g_news_archive_card( $tp_one ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</section>
					<?php else : ?>
						<p class="tp-lead tp-news-empty" tabindex="-1" data-tp-news-focus><?php echo esc_html( $tp_on ? __( 'موردی با این پالایش نیست.', 'topersia' ) : topersia_g_news_l( 'none' ) ); ?></p>
					<?php endif; ?>

					<?php echo topersia_g_news_pages( $tp_pages, $tp_paged, 'topersia_g_news_archive_link', topersia_g_news_l( 'pages' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		</div>
	</main>
</div>
<?php

if ( ! $tp_part ) {
	get_footer();
}
