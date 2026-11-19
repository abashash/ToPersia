<?php
/**
 * هاب عمومی نوع، برچسب و فصل — /{نامک نوع}/ · /tags/{slug}/ · /season/{فصل}/ — دستۀ 51 بخش ۴ و الحاقیۀ ج · دستۀ 52
 *
 * همان اجزای /news/ (P5-D541 · P5-D546 · P5-D557 · P5-D558)، از همان توابع topersia-g-news-ui:
 *   سربرگ   تصویر واقعی تمام‌عرض · مسیر راهنما · H1 و یک جمله وسط‌چین با نام مکان
 *   نوار    تراشه‌های یک‌خطی نخستین فاست واقعی (برچسب و فصل: نوع محتوا) · مرتب‌سازی · «نمایش» · شبکه/فهرست — بی ردیف فیلتر (P5-D566)
 *   فعال    ردیف جدای پالایش‌های فعال + «پاک‌کردن همه»
 *   نتیجه   کاشی‌های `.tp-tile` (توکن کاشی) · صفحه‌بندی؛ خالی‌امن
 * کوئری اصلی (topersia-g-hub.php): شناسه‌های پالایش‌شده به ترتیب خودشان؛ نمایشی فقط در پیش‌نمایش.
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
$tp_row   = topersia_g_hub_row();
$tp_args  = topersia_g_hub_args();
$tp_place = $tp_args['tp_place'];
$tp_on    = topersia_g_hub_filtered();
$tp_facet = topersia_g_hub_facet_options();
$tp_seas  = topersia_g_hub_season_options();
$tp_title = topersia_g_hub_title( $tp_place );
$tp_base  = topersia_g_hub_url();
$tp_part  = topersia_g_news_partial();
$tp_loc_on = ! empty( $tp_row['loc'] ) && function_exists( 'topersia_g_loc_field' );

$tp_crumbs = array( array( 'ToPersia', home_url( '/' ) ) );
if ( $tp_place ) {
	$tp_crumbs[] = array( topersia_g_hub_title( 0 ), $tp_base );
	$tp_crumbs[] = array( topersia_g_title( $tp_place ), '' );
} else {
	$tp_crumbs[] = array( $tp_title, '' );
}

/* تراشه‌های فاست — [ نام, نشانی, فعال, مقدار ] */
$tp_chip_items = array();
if ( $tp_facet ) {
	$tp_chip_items[] = array( __( 'همه', 'topersia' ), topersia_g_hub_link( 1, array( 'tp_facet' => '' ) ), '' === $tp_args['tp_facet'], '' );
	foreach ( $tp_facet as $tp_v => $tp_l ) {
		$tp_chip_items[] = array( $tp_l, topersia_g_hub_link( 1, array( 'tp_facet' => $tp_v ) ), $tp_v === $tp_args['tp_facet'], $tp_v );
	}
}
$tp_map       = topersia_g_map();
$tp_facet_lab = ( '' !== $tp_row['facet'] && isset( $tp_map[ $tp_row['facet'] ]['label'] ) ) ? topersia_g_t( (string) $tp_map[ $tp_row['facet'] ]['label'] ) : __( 'نوع', 'topersia' );

$tp_tiles = array();
foreach ( $tp_ids as $tp_one ) {
	$tp_tiles[] = topersia_g_hub_card( $tp_one );   /* کاشی با عکس — P5-D576 */
}

if ( $tp_part ) {
	topersia_g_news_partial_headers();
} else {
	get_header();
}

?>
<div class="tp-page tp-news-page tp-hub"<?php echo topersia_g_news_root_attrs( $tp_base ); // phpcs:ignore WordPress.Security.EscapeOutput ?> data-tp-news-chipvar="tp_facet">
	<main id="tp-main" class="tp-main">
		<?php echo topersia_g_news_hero( topersia_g_news_hero_image( $tp_place ), $tp_crumbs, $tp_title, topersia_g_hub_lead( $tp_place ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="tp-wrap">
			<section id="tp-hub" class="tp-section" aria-label="<?php echo esc_attr( $tp_title ); ?>">
				<p class="tp-news-vh" role="status" aria-live="polite" data-tp-news-live></p>

				<div class="tp-news-filters">
					<div class="tp-news-bar">
						<?php echo topersia_g_news_chips( $tp_chip_items, $tp_facet_lab ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php /* P5-D575: نوار ابزار یک‌خطی — تراشه‌ها، جست‌وجو، ترکیب‌باکس مکان، مرتب‌سازی، «نمایش»، شبکه/فهرست؛ بی ردیف فیلتر چندلایه */ ?>
						<div class="tp-news-bar__tools">
							<form id="tp-hub-form" class="tp-news-filter tp-news-filter--min" method="get" action="<?php echo esc_url( $tp_base ); ?>" data-tp-news-filter>
								<?php if ( '' !== $tp_args['tp_facet'] ) : ?><input type="hidden" name="tp_facet" value="<?php echo esc_attr( $tp_args['tp_facet'] ); ?>"><?php endif; ?>
								<?php /* P5-D575: جست‌وجو و مکان در همان خط؛ موبایل زیر دکمۀ «فیلتر» (بی JS همیشه دیدنی).
								   دستۀ 55 بند ۴: ویژگی ظرف data-tp-filter-panel است، نه data-tp-more — آن نشان دکمۀ «N مورد دیگر» در topersia-g-zones.js است
								   که با کلیک روی خودِ عنصر (و هر فرزندش، از راه bubbling) آن را برمی‌دارد؛ برخورد نام، جعبۀ جست‌وجو و مکان را با نخستین کلیک حذف می‌کرد. */ ?>
								<?php /* دستۀ 55 بند ۷: data-tp-pre ← موبایل با html.tp-js همان وضعیت پس از news.js از نخستین رنگ‌آمیزی (دکمه دیدنی، ظرف بسته مگر پُر)؛
								   پنهان‌شدن ظرف پس از رنگ‌آمیزی نتایج را ۹۱px بالا می‌کشید. «پُر» را CSS از ویژگی value سرور می‌خواند = همان filled() news.js. */ ?>
								<button type="button" class="tp-hub-more__btn" data-tp-more-btn aria-expanded="false" aria-controls="tp-hub-more" hidden data-tp-pre><?php echo esc_html__( 'فیلتر', 'topersia' ); ?></button>
								<div data-tp-pre id="tp-hub-more" class="tp-hub-more" data-tp-filter-panel>
									<div class="tp-news-filter__f tp-news-filter__f--q">
										<label for="tp-hub-q"><?php echo esc_html__( 'جست‌وجو', 'topersia' ); ?></label>
										<input id="tp-hub-q" type="search" name="tp_q" value="<?php echo esc_attr( $tp_args['tp_q'] ); ?>" maxlength="80" autocomplete="off">
									</div>
									<?php if ( $tp_loc_on ) { echo topersia_g_loc_field( 'tp-hub-place', $tp_args['tp_place'] ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</div>
								<?php echo topersia_g_news_select( 'tp-hub-sort', 'tp_sort', __( 'مرتب‌سازی', 'topersia' ), topersia_g_hub_sort_options(), $tp_args['tp_sort'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<div class="tp-news-filter__go"><button type="submit"><?php echo esc_html__( 'نمایش', 'topersia' ); ?></button></div>
							</form>
							<?php echo topersia_g_news_view_toggle(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					</div>

					<?php
					$tp_loc_raw = is_scalar( get_query_var( 'tp_loc' ) ) ? trim( sanitize_text_field( (string) get_query_var( 'tp_loc' ) ) ) : '';
					echo topersia_g_active_filters( topersia_g_hub_active_items( $tp_args ), $tp_base, ( ! $tp_place && '' !== $tp_loc_raw ) ? mb_substr( $tp_loc_raw, 0, 60 ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>

				<div class="tp-news-results"<?php echo topersia_g_news_results_attrs( $tp_total ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php echo topersia_g_ad_slot( 'hub-top' ); // phpcs:ignore WordPress.Security.EscapeOutput -- دستۀ 54 ?>
					<?php if ( $tp_tiles ) : ?>
						<section class="tp-hub-list" aria-labelledby="tp-hub-list-h">
							<div class="tp-news-latest__head">
								<h2 id="tp-hub-list-h" tabindex="-1" data-tp-news-focus><?php echo esc_html( $tp_on ? __( 'نتیجۀ پالایش', 'topersia' ) : __( 'همۀ موارد', 'topersia' ) ); ?></h2>
								<?php if ( $tp_on ) : ?>
									<a class="tp-more tp-news-all" href="<?php echo esc_url( $tp_base ); ?>" data-tp-news-nav><?php echo esc_html__( 'مشاهده همه', 'topersia' ); ?></a>
								<?php endif; ?>
							</div>
							<div class="tp-news-grid tp-hub-grid" data-tp-news-items>
								<?php echo implode( '', topersia_g_ad_feed( $tp_tiles ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</section>
					<?php else : ?>
						<p class="tp-lead tp-news-empty" tabindex="-1" data-tp-news-focus><?php echo esc_html( $tp_on ? __( 'موردی با این پالایش نیست.', 'topersia' ) : __( 'هنوز موردی منتشر نشده است.', 'topersia' ) ); ?></p>
					<?php endif; ?>

					<?php
					/* دستۀ 55 بند ۶: «نمایش بیشتر» = پیوند واقعی صفحۀ بعد (بی JS و برای خزنده همان صفحۀ N)؛ news.js کاشی‌های آن را به همین شبکه می‌افزاید */
					if ( $tp_tiles && $tp_paged < $tp_pages ) :
						?>
						<p class="tp-hub-loadmore" data-tp-load-more-box><a class="tp-hub-loadmore__a" href="<?php echo esc_url( topersia_g_hub_link( $tp_paged + 1 ) ); ?>" rel="next" data-tp-load-more data-tp-load-msg="<?php echo esc_attr__( 'موارد بیشتری به فهرست افزوده شد', 'topersia' ); ?>"><?php echo esc_html__( 'نمایش بیشتر', 'topersia' ); ?></a></p>
					<?php endif; ?>
					<?php echo topersia_g_news_pages( $tp_pages, $tp_paged, 'topersia_g_hub_link', __( 'صفحه‌های فهرست', 'topersia' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		</div>
	</main>
</div>
<?php

if ( ! $tp_part ) {
	get_footer();
}
