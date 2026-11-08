<?php
/**
 * آرشیو سراسری خبر — /news/ · /news/page/N/ · ?tp_place=  — SPRINT-4.4 فاز ۶٫۱ (P5-D260) · دستۀ 48
 *
 * کوئری اصلی (topersia-g-news.php، pre_get_posts): فقط منتشرشده، هم‌زبان و
 * غیرنمونه، ۱۲ در صفحه. کارت: تصویر، تیتر، تاریخ به زبان صفحه، منبع، خلاصه و
 * نشان مکان‌های مرتبط. پالایش استان/شهر با فرم GET. noindex و canonical در
 * topersia-g-news.php؛ اسکیما CollectionPage + ItemList در topersia-schema.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

$tp_ids   = array_map( 'absint', wp_list_pluck( (array) $wp_query->posts, 'ID' ) );
$tp_ids   = array_values( array_filter( topersia_g_published( $tp_ids ), function ( $id ) { return ! metadata_exists( 'post', $id, '_tp_sample' ); } ) );
$tp_paged = max( 1, (int) get_query_var( 'paged' ) );
$tp_pages = (int) $wp_query->max_num_pages;
$tp_place = topersia_g_news_filter_place();
$tp_opts  = topersia_g_news_filter_options();
$tp_title = $tp_place ? topersia_g_news_title( $tp_place ) : __( 'اخبار گردشگری ایران', 'topersia' );
$tp_base  = topersia_g_news_archive_url();
$tp_page  = function ( $n ) use ( $tp_place ) {
	$url = topersia_g_news_archive_url( $n );
	return $tp_place ? add_query_arg( 'tp_place', $tp_place, $url ) : $url;
};
$tp_group = array(
	'tp_province' => __( 'استان‌ها', 'topersia' ),
	'tp_city'     => __( 'شهرها', 'topersia' ),
);

get_header();

?>
<?php /* جهت از زبان صفحه، نه ثابت.  ۲٫۰٫۸ */ ?>
<div class="tp-page tp-news-page tp-news-archive" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
	<main id="tp-main" class="tp-main">
		<div class="tp-wrap">
			<nav class="tp-crumb" aria-label="<?php echo esc_attr__( 'مسیر راهنما', 'topersia' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">ToPersia</a><span>›</span>
				<?php if ( $tp_place ) : ?>
					<a href="<?php echo esc_url( $tp_base ); ?>"><?php echo esc_html__( 'اخبار', 'topersia' ); ?></a><span>›</span>
					<span aria-current="page"><?php echo esc_html( topersia_g_title( $tp_place ) ); ?></span>
				<?php else : ?>
					<span aria-current="page"><?php echo esc_html__( 'اخبار', 'topersia' ); ?></span>
				<?php endif; ?>
			</nav>

			<section id="tp-news" class="tp-section">
				<div class="tp-section-head"><div>
					<h1><?php echo esc_html( $tp_title ); ?></h1>
					<p class="tp-lead"><?php echo esc_html__( 'محتوای زمانی با تاریخ صریح و لینک به منبع اصلی.', 'topersia' ); ?></p>
				</div></div>

				<?php if ( $tp_opts ) : ?>
					<form class="tp-news-filter" method="get" action="<?php echo esc_url( $tp_base ); ?>" role="search">
						<label for="tp-news-place"><?php echo esc_html__( 'استان یا شهر', 'topersia' ); ?></label>
						<select id="tp-news-place" name="tp_place">
							<option value=""><?php echo esc_html__( 'همۀ مکان‌ها', 'topersia' ); ?></option>
							<?php foreach ( $tp_opts as $tp_type => $tp_list ) : ?>
								<optgroup label="<?php echo esc_attr( $tp_group[ $tp_type ] ); ?>">
									<?php foreach ( $tp_list as $tp_pid => $tp_name ) : ?>
										<option value="<?php echo esc_attr( (string) $tp_pid ); ?>"<?php selected( $tp_place, $tp_pid ); ?>><?php echo esc_html( $tp_name ); ?></option>
									<?php endforeach; ?>
								</optgroup>
							<?php endforeach; ?>
						</select>
						<button type="submit"><?php echo esc_html__( 'نمایش', 'topersia' ); ?></button>
					</form>
				<?php endif; ?>

				<?php if ( $tp_ids ) : ?>
					<div class="tp-news-grid">
						<?php foreach ( $tp_ids as $tp_one ) { echo topersia_g_news_archive_card( $tp_one ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php else : ?>
					<p class="tp-lead tp-news-empty"><?php echo esc_html( $tp_place ? __( 'هنوز خبری برای این مکان نیست.', 'topersia' ) : __( 'هنوز خبری منتشر نشده است.', 'topersia' ) ); ?></p>
				<?php endif; ?>

				<?php if ( $tp_pages > 1 ) : ?>
					<nav class="tp-news-pages" aria-label="<?php echo esc_attr__( 'صفحه‌های خبر', 'topersia' ); ?>">
						<?php for ( $tp_i = 1; $tp_i <= $tp_pages; $tp_i ++ ) : ?>
							<?php if ( $tp_i === $tp_paged ) : ?>
								<span aria-current="page"><?php echo esc_html( topersia_g_fa( $tp_i ) ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( $tp_page( $tp_i ) ); ?>"><?php echo esc_html( topersia_g_fa( $tp_i ) ); ?></a>
							<?php endif; ?>
						<?php endfor; ?>
					</nav>
				<?php endif; ?>
			</section>
		</div>
	</main>
</div>
<?php

get_footer();
