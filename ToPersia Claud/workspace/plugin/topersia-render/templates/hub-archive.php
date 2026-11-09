<?php
/**
 * هاب عمومی نوع و برچسب — /{نامک نوع}/ · /tags/{slug}/ — دستۀ 51 بخش ۴ و الحاقیۀ ج
 *
 * همان اجزای /news/ (P5-D541 · P5-D546 · P5-D557 · P5-D558)، از همان توابع topersia-g-news-ui:
 *   سربرگ   تصویر واقعی تمام‌عرض · مسیر راهنما · H1 و یک جمله وسط‌چین با نام مکان
 *   پالایش  تراشه‌های نخستین فاست واقعی (برچسب: نوع محتوا) · ترتیب؛ زیرش جست‌وجو · مکان · فصل · «نمایش»
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
	$tp_tiles[] = topersia_g_hub_tile( $tp_one );
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
						<div class="tp-news-bar__tools">
							<?php echo topersia_g_news_select( 'tp-hub-sort', 'tp_sort', __( 'مرتب‌سازی', 'topersia' ), topersia_g_hub_sort_options(), $tp_args['tp_sort'], 'tp-hub-form' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					</div>

					<form id="tp-hub-form" class="tp-news-filter" method="get" action="<?php echo esc_url( $tp_base ); ?>" role="search" aria-label="<?php echo esc_attr__( 'پالایش فهرست', 'topersia' ); ?>" data-tp-news-filter>
						<?php if ( '' !== $tp_args['tp_facet'] ) : ?>
							<input type="hidden" name="tp_facet" value="<?php echo esc_attr( $tp_args['tp_facet'] ); ?>">
						<?php endif; ?>
						<div class="tp-news-filter__f tp-news-filter__f--q">
							<label for="tp-hub-q"><?php echo esc_html__( 'جست‌وجو در فهرست', 'topersia' ); ?></label>
							<input id="tp-hub-q" type="search" name="tp_q" value="<?php echo esc_attr( $tp_args['tp_q'] ); ?>" maxlength="80" autocomplete="off">
						</div>
						<?php
						if ( $tp_row['loc'] ) {
							echo topersia_g_loc_field( 'tp-hub-place', $tp_place ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						if ( $tp_seas ) {
							echo topersia_g_news_select( 'tp-hub-season', 'season', topersia_g_t( (string) $tp_map['best_season']['label'] ), array_merge( array( '' => __( 'همۀ فصل‌ها', 'topersia' ) ), $tp_seas ), $tp_args['season'] ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
						<div class="tp-news-filter__go">
							<button type="submit"><?php echo esc_html__( 'نمایش', 'topersia' ); ?></button>
						</div>
					</form>

					<?php
					$tp_loc_raw = is_scalar( get_query_var( 'tp_loc' ) ) ? trim( sanitize_text_field( (string) get_query_var( 'tp_loc' ) ) ) : '';
					echo topersia_g_active_filters( topersia_g_hub_active_items( $tp_args ), $tp_base, ( ! $tp_place && '' !== $tp_loc_raw ) ? mb_substr( $tp_loc_raw, 0, 60 ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>

				<div class="tp-news-results"<?php echo topersia_g_news_results_attrs( $tp_total ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php if ( $tp_tiles ) : ?>
						<section class="tp-hub-list" aria-labelledby="tp-hub-list-h">
							<div class="tp-news-latest__head">
								<h2 id="tp-hub-list-h" tabindex="-1" data-tp-news-focus><?php echo esc_html( $tp_on ? __( 'نتیجۀ پالایش', 'topersia' ) : __( 'همۀ موارد', 'topersia' ) ); ?></h2>
								<?php if ( $tp_on ) : ?>
									<a class="tp-more tp-news-all" href="<?php echo esc_url( $tp_base ); ?>" data-tp-news-nav><?php echo esc_html__( 'مشاهده همه', 'topersia' ); ?></a>
								<?php endif; ?>
							</div>
							<div class="tp-hub-tiles" data-tp-news-items>
								<?php echo topersia_g_tiles( $tp_tiles, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</section>
					<?php else : ?>
						<p class="tp-lead tp-news-empty" tabindex="-1" data-tp-news-focus><?php echo esc_html( $tp_on ? __( 'موردی با این پالایش نیست.', 'topersia' ) : __( 'هنوز موردی منتشر نشده است.', 'topersia' ) ); ?></p>
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
