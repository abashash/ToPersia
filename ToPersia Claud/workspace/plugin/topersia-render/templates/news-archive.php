<?php
/**
 * آرشیو سراسری خبر — /news/ · /news/page/N/  — SPRINT-4.4 فاز ۶٫۱ (P5-D260) · الگوی P5-D534 · دستۀ 49
 *
 * کوئری اصلی (topersia-g-news.php، pre_get_posts): فقط منتشرشده، هم‌زبان و
 * غیرنمونه، ۱۲ در صفحه؛ پالایش‌های P5-D468 (مکان، نوع، موضوع، جست‌وجو، ترتیب)
 * فقط GET. ترتیب صفحه از الگو:
 *
 *   سربرگ        تصویر شاخص مکان پالایش یا کشور · مسیر راهنما · عنوان · یک جمله
 *   نوار ابزار   تراشه‌های موضوع (بی شمار) · پالایش · ترتیب · شبکه/فهرست
 *   برگزیده      خبر اول با پوشش تیره + سه خبر کناری — فقط صفحۀ ۱ بی‌پالایش با ≥ ۴ خبر
 *   جدیدترین     شبکۀ چهارستونۀ کارت ۱۶:۱۰ · صفحه‌بندی
 *
 * noindex و canonical در topersia-g-news.php؛ اسکیما CollectionPage + ItemList در topersia-schema.
 * کارت دنبال‌کردن ساخته نشد: دکمۀ دنبال‌کردن user-core فقط در نوار کنش صفحۀ موجودیت کار می‌کند.
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
$tp_args  = topersia_g_news_args();
$tp_place = $tp_args['tp_place'];
$tp_on    = topersia_g_news_filtered();
$tp_opts  = topersia_g_news_filter_options();
$tp_kinds = topersia_g_news_kind_options();
$tp_tops  = topersia_g_news_topics();
$tp_city  = topersia_g_news_city_chips( $tp_place );
$tp_title = $tp_place ? topersia_g_news_title( $tp_place ) : __( 'اخبار گردشگری ایران', 'topersia' );
$tp_base  = topersia_g_news_archive_url();
$tp_hero  = topersia_g_news_hero_image( $tp_place );
$tp_feat  = ( ! $tp_on && 1 === $tp_paged && count( $tp_ids ) >= 4 ) ? array_slice( $tp_ids, 0, 4 ) : array();
$tp_grid  = array_slice( $tp_ids, count( $tp_feat ) );
$tp_group = array(
	'tp_province' => __( 'استان‌ها', 'topersia' ),
	'tp_city'     => __( 'شهرها', 'topersia' ),
);

get_header();

?>
<?php /* جهت از زبان صفحه، نه ثابت.  ۲٫۰٫۸ */ ?>
<div class="tp-page tp-news-page tp-news-archive" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
	<main id="tp-main" class="tp-main">
		<header class="tp-news-hero<?php echo '' === $tp_hero ? ' tp-news-hero--art' : ''; ?>">
			<?php echo $tp_hero; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span class="tp-news-hero__scrim" aria-hidden="true"></span>
			<div class="tp-wrap tp-news-hero__in">
				<nav class="tp-crumb" aria-label="<?php echo esc_attr__( 'مسیر راهنما', 'topersia' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">ToPersia</a><span>›</span>
					<?php if ( $tp_place ) : ?>
						<a href="<?php echo esc_url( $tp_base ); ?>"><?php echo esc_html__( 'اخبار', 'topersia' ); ?></a><span>›</span>
						<span aria-current="page"><?php echo esc_html( topersia_g_title( $tp_place ) ); ?></span>
					<?php else : ?>
						<span aria-current="page"><?php echo esc_html__( 'اخبار', 'topersia' ); ?></span>
					<?php endif; ?>
				</nav>
				<h1><?php echo esc_html( $tp_title ); ?></h1>
				<p class="tp-news-hero__lead"><?php echo esc_html__( 'محتوای زمانی با تاریخ صریح و لینک به منبع اصلی.', 'topersia' ); ?></p>
			</div>
		</header>

		<div class="tp-wrap">
			<section id="tp-news" class="tp-section" aria-label="<?php echo esc_attr( $tp_title ); ?>">

				<?php if ( $tp_tops ) : ?>
					<nav class="tp-news-chips" aria-label="<?php echo esc_attr__( 'موضوع‌ها', 'topersia' ); ?>">
						<a href="<?php echo esc_url( topersia_g_news_archive_link( 1, array( 'tp_topic' => '' ) ) ); ?>"<?php echo '' === $tp_args['tp_topic'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html__( 'همه', 'topersia' ); ?></a>
						<?php foreach ( $tp_tops as $tp_t ) : ?>
							<a href="<?php echo esc_url( topersia_g_news_archive_link( 1, array( 'tp_topic' => $tp_t->slug ) ) ); ?>"<?php echo $tp_t->slug === $tp_args['tp_topic'] ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $tp_t->name ); ?></a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>

				<form class="tp-news-filter" method="get" action="<?php echo esc_url( $tp_base ); ?>" role="search" aria-label="<?php echo esc_attr__( 'پالایش اخبار', 'topersia' ); ?>" data-tp-news-filter>
					<?php if ( '' !== $tp_args['tp_topic'] ) : ?>
						<input type="hidden" name="tp_topic" value="<?php echo esc_attr( $tp_args['tp_topic'] ); ?>">
					<?php endif; ?>
					<div class="tp-news-filter__f tp-news-filter__f--q">
						<label for="tp-news-q"><?php echo esc_html__( 'جست‌وجو در اخبار', 'topersia' ); ?></label>
						<input id="tp-news-q" type="search" name="tp_q" value="<?php echo esc_attr( $tp_args['tp_q'] ); ?>" maxlength="80" autocomplete="off">
					</div>
					<?php if ( $tp_opts ) : ?>
						<div class="tp-news-filter__f">
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
						</div>
					<?php endif; ?>
					<?php if ( $tp_kinds ) : ?>
						<div class="tp-news-filter__f">
							<label for="tp-news-kind"><?php echo esc_html__( 'نوع مکان', 'topersia' ); ?></label>
							<select id="tp-news-kind" name="tp_kind">
								<option value=""><?php echo esc_html__( 'همۀ نوع‌ها', 'topersia' ); ?></option>
								<?php foreach ( $tp_kinds as $tp_k => $tp_label ) : ?>
									<option value="<?php echo esc_attr( $tp_k ); ?>"<?php selected( $tp_args['tp_kind'], $tp_k ); ?>><?php echo esc_html( $tp_label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					<?php endif; ?>
					<div class="tp-news-filter__f">
						<label for="tp-news-sort"><?php echo esc_html__( 'مرتب‌سازی', 'topersia' ); ?></label>
						<select id="tp-news-sort" name="tp_sort">
							<option value=""><?php echo esc_html__( 'جدیدترین', 'topersia' ); ?></option>
							<option value="old"<?php selected( $tp_args['tp_sort'], 'old' ); ?>><?php echo esc_html__( 'قدیمی‌ترین', 'topersia' ); ?></option>
						</select>
					</div>
					<div class="tp-news-filter__go">
						<button type="submit"><?php echo esc_html__( 'نمایش', 'topersia' ); ?></button>
						<?php if ( $tp_on ) : ?>
							<a class="tp-news-filter__reset" href="<?php echo esc_url( $tp_base ); ?>"><?php echo esc_html__( 'پاک‌کردن پالایش', 'topersia' ); ?></a>
						<?php endif; ?>
					</div>
				</form>

				<?php if ( $tp_city ) : ?>
					<nav class="tp-news-chips tp-news-chips--city" aria-label="<?php echo esc_attr__( 'شهرهای استان', 'topersia' ); ?>">
						<?php foreach ( $tp_city as $tp_cid => $tp_cname ) : ?>
							<a href="<?php echo esc_url( topersia_g_news_archive_link( 1, array( 'tp_place' => $tp_cid ) ) ); ?>"<?php echo $tp_cid === $tp_place ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $tp_cname ); ?></a>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>

				<?php if ( $tp_feat ) : ?>
					<section class="tp-news-top" aria-labelledby="tp-news-top-h">
						<h2 id="tp-news-top-h" class="tp-news-vh"><?php echo esc_html__( 'خبرهای برگزیده', 'topersia' ); ?></h2>
						<?php echo topersia_g_news_feature( $tp_feat[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<ul class="tp-news-side">
							<?php foreach ( array_slice( $tp_feat, 1 ) as $tp_one ) { echo topersia_g_news_mini( $tp_one ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( $tp_grid ) : ?>
					<section class="tp-news-latest" aria-labelledby="tp-news-latest-h">
						<div class="tp-news-latest__head">
							<h2 id="tp-news-latest-h"><?php echo esc_html( $tp_on ? __( 'نتیجۀ پالایش', 'topersia' ) : __( 'جدیدترین مطالب', 'topersia' ) ); ?></h2>
							<div class="tp-news-view" role="group" aria-label="<?php echo esc_attr__( 'شیوۀ نمایش', 'topersia' ); ?>" hidden data-tp-news-view>
								<button type="button" data-tp-view="grid" aria-pressed="true"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/></svg><span class="tp-news-vh"><?php echo esc_html__( 'نمای شبکه‌ای', 'topersia' ); ?></span></button>
								<button type="button" data-tp-view="list" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="5" cy="6" r="1.2"/><circle cx="5" cy="12" r="1.2"/><circle cx="5" cy="18" r="1.2"/></svg><span class="tp-news-vh"><?php echo esc_html__( 'نمای فهرستی', 'topersia' ); ?></span></button>
							</div>
						</div>
						<div class="tp-news-grid" data-tp-news-items>
							<?php foreach ( $tp_grid as $tp_one ) { echo topersia_g_news_archive_card( $tp_one ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					</section>
				<?php elseif ( ! $tp_feat ) : ?>
					<p class="tp-lead tp-news-empty"><?php echo esc_html( $tp_on ? __( 'خبری با این پالایش نیست.', 'topersia' ) : __( 'هنوز خبری منتشر نشده است.', 'topersia' ) ); ?></p>
				<?php endif; ?>

				<?php if ( $tp_pages > 1 ) : ?>
					<nav class="tp-news-pages" aria-label="<?php echo esc_attr__( 'صفحه‌های خبر', 'topersia' ); ?>">
						<?php for ( $tp_i = 1; $tp_i <= $tp_pages; $tp_i ++ ) : ?>
							<?php if ( $tp_i === $tp_paged ) : ?>
								<span aria-current="page"><?php echo esc_html( topersia_g_fa( $tp_i ) ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( topersia_g_news_archive_link( $tp_i ) ); ?>"><?php echo esc_html( topersia_g_fa( $tp_i ) ); ?></a>
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
