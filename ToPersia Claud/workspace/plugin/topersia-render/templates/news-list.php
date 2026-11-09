<?php
/**
 * صفحۀ خبرهای یک مکان — /{rewrite}/{slug}/news/ · /page/N/
 * CHANGE-PACKAGE-001 بند ۷٫۸ · ۴٫۳٫۰ بند ۶
 *
 * عنوان «اخبار {نام کامل}»؛ ده خبر در هر صفحه، تازه‌ترین اول، بی مرز
 * زمانی؛ کارت همان کارت بخش `#news`. زیر سه خبر noindex, follow — در
 * topersia-g-news.php. اسکیما: CollectionPage با about به @id موجودیت.
 * دستۀ 49 (P5-D534): تراشۀ موضوع و ترتیب (GET؛ پالایش‌شده noindex با canonical
 * بی‌پالایش) و پیوند «همۀ اخبار» به آرشیو /news/ (با پالایش همین مکان اگر استان یا شهر است).
 * دستۀ 50 (P5-D541): همان اجزای مشترک /news/ — سربرگ، تراشۀ یک‌خطی، کارت یکسان، فیلتر بی‌رفرش
 * (فقط موضوع و ترتیب؛ P5-D546) و «مشاهده همه» روبه‌روی عنوان به آرشیو.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tp_place = (int) get_queried_object_id();
$tp_paged = max( 1, (int) get_query_var( 'paged' ) );
$tp_topic = topersia_g_news_filter_topic();
$tp_sort  = 'old' === get_query_var( 'tp_sort' ) ? 'old' : '';
$tp_q     = topersia_g_news_query( $tp_place, 10, 0, $tp_paged, array( 'topic' => $tp_topic ? $tp_topic->slug : '', 'order' => 'old' === $tp_sort ? 'ASC' : 'DESC' ) );
$tp_tops  = topersia_g_news_place_topics( $tp_place );
$tp_link  = function ( $page, $topic ) use ( $tp_place, $tp_sort ) {
	return add_query_arg( array_filter( array( 'tp_topic' => $topic, 'tp_sort' => $tp_sort ) ), topersia_g_news_list_url( $tp_place, $page ) );
};
$tp_arch  = in_array( (string) get_post_type( $tp_place ), array( 'tp_province', 'tp_city' ), true ) ? add_query_arg( 'tp_place', $tp_place, topersia_g_news_archive_url() ) : topersia_g_news_archive_url();
$tp_pages = (int) ceil( $tp_q['total'] / 10 );
$tp_title = topersia_g_news_title( $tp_place );
$tp_base  = topersia_g_news_list_url( $tp_place );
$tp_part  = topersia_g_news_partial();

$tp_topic_items = array( array( __( 'همه', 'topersia' ), $tp_link( 1, '' ), ! $tp_topic, '' ) );
foreach ( $tp_tops as $tp_t ) {
	$tp_topic_items[] = array( $tp_t->name, $tp_link( 1, $tp_t->slug ), $tp_topic && $tp_topic->term_id === $tp_t->term_id, $tp_t->slug );
}
$tp_page_link = function ( $page ) use ( $tp_link, $tp_topic ) {
	return $tp_link( $page, $tp_topic ? $tp_topic->slug : '' );
};
$tp_crumbs = array(
	array( 'ToPersia', home_url( '/' ) ),
	array( topersia_g_title( $tp_place ), (string) get_permalink( $tp_place ) ),
	array( __( 'اخبار', 'topersia' ), '' ),
);

add_action( 'wp_head', function () use ( $tp_place, $tp_title ) {

	$ld = array(
		'@context' => 'https://schema.org',
		'@type'    => 'CollectionPage',
		'name'     => $tp_title,
		'url'      => topersia_g_news_list_url( $tp_place, max( 1, (int) get_query_var( 'paged' ) ) ),
	);

	$about = function_exists( 'topersia_schema_uid' ) ? (string) topersia_schema_uid( $tp_place ) : trim( (string) get_post_meta( $tp_place, 'entity_uid', true ) );   /* همان @id گراف.  P5-D499 */

	if ( '' !== $about ) {
		$ld['about'] = array( '@id' => $about );
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 30 );

if ( $tp_part ) {
	topersia_g_news_partial_headers();
} else {
	get_header();
}

?>
<?php /* جهت از زبان صفحه، نه ثابت — نسخۀ انگلیسیِ این برگه چپ‌به‌راست است.  ۲٫۰٫۸ */ ?>
<div class="tp-page tp-news-page tp-news-place"<?php echo topersia_g_news_root_attrs( $tp_base ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<main id="tp-main" class="tp-main">
		<?php echo topersia_g_news_hero( topersia_g_news_hero_image( $tp_place ), $tp_crumbs, $tp_title, __( 'محتوای زمانی با تاریخ صریح و لینک به منبع اصلی.', 'topersia' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="tp-wrap">
			<section id="tp-news" class="tp-section" aria-label="<?php echo esc_attr( $tp_title ); ?>">
				<p class="tp-news-vh" role="status" aria-live="polite" data-tp-news-live></p>

				<div class="tp-news-bar">
					<?php echo topersia_g_news_chips( $tp_topic_items, __( 'موضوع‌ها', 'topersia' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div class="tp-news-bar__tools">
						<form class="tp-news-filter tp-news-filter--min" method="get" action="<?php echo esc_url( $tp_base ); ?>" data-tp-news-filter>
							<?php if ( $tp_topic ) : ?><input type="hidden" name="tp_topic" value="<?php echo esc_attr( $tp_topic->slug ); ?>"><?php endif; ?>
							<?php echo topersia_g_news_select( 'tp-news-sort', 'tp_sort', __( 'مرتب‌سازی', 'topersia' ), topersia_g_news_sort_options(), $tp_sort ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<div class="tp-news-filter__go"><button type="submit"><?php echo esc_html__( 'نمایش', 'topersia' ); ?></button></div>
						</form>
						<?php echo topersia_g_news_view_toggle(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>

				<div class="tp-news-results"<?php echo topersia_g_news_results_attrs( $tp_q['total'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php if ( $tp_q['ids'] ) : ?>
						<section class="tp-news-latest" aria-labelledby="tp-news-latest-h">
							<div class="tp-news-latest__head">
								<h2 id="tp-news-latest-h" tabindex="-1" data-tp-news-focus><?php echo esc_html__( 'جدیدترین خبرها', 'topersia' ); ?></h2>
								<a class="tp-more tp-news-all" href="<?php echo esc_url( $tp_arch ); ?>"><?php echo esc_html__( 'همۀ اخبار', 'topersia' ); ?></a>
							</div>
							<div class="tp-news-grid" data-tp-news-items>
								<?php foreach ( $tp_q['ids'] as $tp_one ) { echo topersia_g_news_archive_card( $tp_one ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</section>
					<?php else : ?>
						<p class="tp-lead tp-news-empty" tabindex="-1" data-tp-news-focus><?php echo esc_html__( 'هنوز خبری برای این مکان نیست.', 'topersia' ); ?></p>
					<?php endif; ?>

					<?php echo topersia_g_news_pages( $tp_pages, $tp_paged, $tp_page_link ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		</div>
	</main>
</div>
<?php

if ( ! $tp_part ) {
	get_footer();
}
