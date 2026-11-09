<?php
/**
 * صفحۀ نتیجۀ جست‌وجوی سراسری — P5-D536 · دستۀ 49 بند ۷.
 *
 * کوئری اصلی در topersia-g-search.php: نوع‌های tp_*، منتشرشده، غیرنمونه، زبان جاری،
 * تطبیق با نمایۀ تاشده (عنوان و نام جایگزین). اینجا دوباره از دروازۀ انتشار و
 * نمونه می‌گذرد. کاشی‌های موجود با برچسب نوع؛ خالی ← «نتیجه‌ای نیست.». noindex.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

$tp_q     = trim( (string) get_search_query( false ) );
$tp_ids   = array_map( 'absint', wp_list_pluck( (array) $wp_query->posts, 'ID' ) );
$tp_ids   = array_values( array_filter( topersia_g_published( $tp_ids ), function ( $id ) { return ! metadata_exists( 'post', $id, '_tp_sample' ); } ) );
$tp_paged = max( 1, (int) get_query_var( 'paged' ) );
$tp_pages = '' !== $tp_q ? (int) $wp_query->max_num_pages : 0;
$tp_title = '' !== $tp_q
	/* translators: %s: عبارت جست‌وجو */
	? sprintf( __( 'نتایج جست‌وجو برای «%s»', 'topersia' ), $tp_q )
	: __( 'نتایج جست‌وجو', 'topersia' );

get_header();

?>
<div class="tp-page tp-search-page" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
	<main id="tp-main" class="tp-main">
		<div class="tp-wrap">
			<nav class="tp-crumb" aria-label="<?php echo esc_attr__( 'مسیر راهنما', 'topersia' ); ?>">
				<a href="<?php echo esc_url( (string) apply_filters( 'wpml_home_url', home_url( '/' ) ) ); ?>">ToPersia</a><span>›</span>
				<span aria-current="page"><?php echo esc_html__( 'جست‌وجو', 'topersia' ); ?></span>
			</nav>

			<section id="tp-search" class="tp-section" aria-labelledby="tp-search-title">
				<div class="tp-section-head"><div>
					<h1 id="tp-search-title"><?php echo esc_html( $tp_title ); ?></h1>
				</div></div>

				<?php echo topersia_g_search_form( 'tp-search-q', $tp_q, 'tp-search--page' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

				<?php if ( '' === $tp_q ) : ?>
					<p class="tp-lead tp-search-empty"><?php echo esc_html__( 'عبارتی برای جست‌وجو بنویسید.', 'topersia' ); ?></p>
				<?php elseif ( $tp_ids ) : ?>
					<?php echo topersia_g_search_tiles( $tp_ids ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<p class="tp-lead tp-search-empty"><?php echo esc_html__( 'نتیجه‌ای نیست.', 'topersia' ); ?></p>
				<?php endif; ?>

				<?php if ( $tp_pages > 1 ) : ?>
					<nav class="tp-search-pages" aria-label="<?php echo esc_attr__( 'صفحه‌های نتیجه', 'topersia' ); ?>">
						<?php for ( $tp_i = 1; $tp_i <= $tp_pages; $tp_i ++ ) : ?>
							<?php if ( $tp_i === $tp_paged ) : ?>
								<span aria-current="page"><?php echo esc_html( topersia_g_fa( $tp_i ) ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( get_pagenum_link( $tp_i ) ); ?>"><?php echo esc_html( topersia_g_fa( $tp_i ) ); ?></a>
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
