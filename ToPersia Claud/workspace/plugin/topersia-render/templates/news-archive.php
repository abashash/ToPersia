<?php
/**
 * آرشیو سراسری خبر — /news/ · /news/page/N/  — SPRINT-4.4 فاز ۶٫۱ (P5-D260) · الگوی P5-D534 · دستۀ 49 · دستۀ 50
 *
 * کوئری اصلی (topersia-g-news.php، pre_get_posts): فقط منتشرشده، هم‌زبان و
 * غیرنمونه، ۱۲ در صفحه؛ پالایش‌های P5-D468 (مکان، نوع، موضوع، جست‌وجو، ترتیب)
 * و بازۀ زمانی P5-D542 — همه GET. ساختار از الگو (P5-D540؛ فقط ساختار، رنگ و
 * اندازه از توکن سایت):
 *
 *   سربرگ        تصویر واقعی تمام‌عرض · مسیر راهنما (آغاز خط) · H1 و یک جمله وسط‌چین با نام مکان (P5-D558) — بی فاصله از هدر (P5-D541)
 *   بالا         خبر برگزیده + دو ستون کناری، هر ستون ۳ (P5-D556) — فقط صفحۀ ۱ بی پالایش؛ تکراری در «جدیدترین» نمی‌آید
 *   پالایش       تراشه‌های موضوع در یک خط اسکرولی · ترتیب · شبکه/فهرست؛ زیرش جست‌وجو · مکان (combobox) · نوع · بازۀ زمانی · «نمایش»
 *   فعال         ردیف جدای تراشه‌های پالایش فعال + «پاک‌کردن همه» (P5-D557)؛ بی پالایش نمی‌آید
 *   جدیدترین     «مشاهده همه» روبه‌روی عنوان · شبکۀ چهارستونۀ کارت یکسان · صفحه‌بندی
 *
 * بی‌رفرش (P5-D546): topersia-g-news.js همین نشانی را با `tp_partial=1` می‌گیرد و فقط
 * ناحیه‌های `data-tp-news-swap` را جای‌گزین می‌کند. پاسخ قطعه همین قالب است بی سربرگ و
 * پاورقی سایت؛ حالت پالایش‌شدۀ رندر سرور همان noindex و canonical بی‌پالایش را دارد.
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
$tp_place = $tp_args['tp_place'];
$tp_on    = topersia_g_news_filtered();
$tp_kinds = topersia_g_news_kind_options();
$tp_tops  = topersia_g_news_topics();
$tp_city  = topersia_g_news_city_chips( $tp_place );
$tp_title = topersia_g_news_title( $tp_place );
$tp_base  = topersia_g_news_archive_url();
$tp_feat  = ( ! $tp_on && 1 === $tp_paged && count( $tp_ids ) >= 2 ) ? array_slice( $tp_ids, 0, topersia_g_news_top_max() ) : array();
$tp_grid  = array_slice( $tp_ids, count( $tp_feat ) );
$tp_part  = topersia_g_news_partial();

$tp_crumbs = array( array( 'ToPersia', home_url( '/' ) ) );
if ( $tp_place ) {
	$tp_crumbs[] = array( __( 'اخبار', 'topersia' ), $tp_base );
	$tp_crumbs[] = array( topersia_g_title( $tp_place ), '' );
} else {
	$tp_crumbs[] = array( __( 'اخبار', 'topersia' ), '' );
}

/* تراشه و فهرست کناری موضوع — یک منبع: [ نام, نشانی, فعال, نامک ] */
$tp_topic_items = array( array( __( 'همه', 'topersia' ), topersia_g_news_archive_link( 1, array( 'tp_topic' => '' ) ), '' === $tp_args['tp_topic'], '' ) );
foreach ( $tp_tops as $tp_t ) {
	$tp_topic_items[] = array( $tp_t->name, topersia_g_news_archive_link( 1, array( 'tp_topic' => $tp_t->slug ) ), $tp_t->slug === $tp_args['tp_topic'], $tp_t->slug );
}
$tp_city_items = array();
foreach ( $tp_city as $tp_cid => $tp_cname ) {
	$tp_city_items[] = array( $tp_cname, topersia_g_news_archive_link( 1, array( 'tp_place' => $tp_cid ) ), $tp_cid === $tp_place, '' );
}

if ( $tp_part ) {
	topersia_g_news_partial_headers();
} else {
	get_header();
}

?>
<div class="tp-page tp-news-page tp-news-archive"<?php echo topersia_g_news_root_attrs( $tp_base ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<main id="tp-main" class="tp-main">
		<?php echo topersia_g_news_hero( topersia_g_news_hero_image( $tp_place ), $tp_crumbs, $tp_title, topersia_g_news_lead( $tp_place ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="tp-wrap">
			<section id="tp-news" class="tp-section" aria-label="<?php echo esc_attr( $tp_title ); ?>">
				<p class="tp-news-vh" role="status" aria-live="polite" data-tp-news-live></p>

				<?php echo topersia_g_news_top( $tp_feat ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

				<div class="tp-news-filters">
					<div class="tp-news-bar">
						<?php echo topersia_g_news_chips( $tp_topic_items, __( 'موضوع‌ها', 'topersia' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<div class="tp-news-bar__tools">
							<?php echo topersia_g_news_select( 'tp-news-sort', 'tp_sort', __( 'مرتب‌سازی', 'topersia' ), topersia_g_news_sort_options(), $tp_args['tp_sort'], 'tp-news-form' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php echo topersia_g_news_view_toggle(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					</div>

					<form id="tp-news-form" class="tp-news-filter" method="get" action="<?php echo esc_url( $tp_base ); ?>" role="search" aria-label="<?php echo esc_attr__( 'پالایش اخبار', 'topersia' ); ?>" data-tp-news-filter>
						<?php if ( '' !== $tp_args['tp_topic'] ) : ?>
							<input type="hidden" name="tp_topic" value="<?php echo esc_attr( $tp_args['tp_topic'] ); ?>">
						<?php endif; ?>
						<div class="tp-news-filter__f tp-news-filter__f--q">
							<label for="tp-news-q"><?php echo esc_html__( 'جست‌وجو در اخبار', 'topersia' ); ?></label>
							<input id="tp-news-q" type="search" name="tp_q" value="<?php echo esc_attr( $tp_args['tp_q'] ); ?>" maxlength="80" autocomplete="off">
						</div>
						<?php
						/* P5-D549: combobox متنی؛ پس از انتخاب خالی و هم‌اندازه می‌ماند — تراشه در ردیف فعال (P5-D557) */
						echo topersia_g_loc_field( 'tp-news-place', $tp_place ); // phpcs:ignore WordPress.Security.EscapeOutput
						if ( $tp_kinds ) {
							echo topersia_g_news_select( 'tp-news-kind', 'tp_kind', __( 'نوع مکان', 'topersia' ), array_merge( array( '' => __( 'همۀ نوع‌ها', 'topersia' ) ), $tp_kinds ), $tp_args['tp_kind'] ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						echo topersia_g_news_select( 'tp-news-when', 'tp_when', __( 'بازۀ زمانی', 'topersia' ), topersia_g_news_when_options(), $tp_args['tp_when'] ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
						<div class="tp-news-filter__go">
							<button type="submit"><?php echo esc_html__( 'نمایش', 'topersia' ); ?></button>
						</div>
					</form>

					<?php
					$tp_loc_raw = is_scalar( get_query_var( 'tp_loc' ) ) ? trim( sanitize_text_field( (string) get_query_var( 'tp_loc' ) ) ) : '';
					echo topersia_g_active_filters( topersia_g_news_active_items( $tp_args, $tp_kinds, $tp_tops ), $tp_base, ( ! $tp_place && '' !== $tp_loc_raw ) ? mb_substr( $tp_loc_raw, 0, 60 ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>

				<div class="tp-news-results"<?php echo topersia_g_news_results_attrs( $tp_total ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php echo topersia_g_news_chips( $tp_city_items, __( 'شهرهای استان', 'topersia' ), '', 'city' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

					<?php if ( $tp_grid ) : ?>
						<section class="tp-news-latest" aria-labelledby="tp-news-latest-h">
							<div class="tp-news-latest__head">
								<h2 id="tp-news-latest-h" tabindex="-1" data-tp-news-focus><?php echo esc_html( $tp_on ? __( 'نتیجۀ پالایش', 'topersia' ) : __( 'جدیدترین خبرها', 'topersia' ) ); ?></h2>
								<?php if ( $tp_on ) : ?>
									<a class="tp-more tp-news-all" href="<?php echo esc_url( $tp_base ); ?>" data-tp-news-nav><?php echo esc_html__( 'مشاهده همه', 'topersia' ); ?></a>
								<?php endif; ?>
							</div>
							<div class="tp-news-grid" data-tp-news-items>
								<?php foreach ( $tp_grid as $tp_one ) { echo topersia_g_news_archive_card( $tp_one ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</section>
					<?php elseif ( ! $tp_feat ) : ?>
						<p class="tp-lead tp-news-empty" tabindex="-1" data-tp-news-focus><?php echo esc_html( $tp_on ? __( 'خبری با این پالایش نیست.', 'topersia' ) : __( 'هنوز خبری منتشر نشده است.', 'topersia' ) ); ?></p>
					<?php endif; ?>

					<?php echo topersia_g_news_pages( $tp_pages, $tp_paged, 'topersia_g_news_archive_link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		</div>
	</main>
</div>
<?php

if ( ! $tp_part ) {
	get_footer();
}
