<?php
/**
 * صفحۀ تکی خبر `tpc_news` و مقاله `tpc_article` — دستۀ 50 (P5-D541) · دستۀ 52 (P5-D568 · P5-D567)
 *
 *   سربرگ        تصویر خود مطلب ← تصویر مکان مرتبط (هرگز نمونه) ← گرادیان · مسیر راهنما · H1 · خلاصه
 *                · سطر وسط‌چین «موضوع · تاریخ · زمان مطالعه» با جداکننده (مقاله: به‌روزرسانی و نویسنده زیر آن)
 *   دو ستون      ستون اصلی در آغاز سطر (راست در راست‌به‌چپ): متن، سپس نظرات — همان جزء صفحه‌های موجودیت
 *                (`topersia_g_sc_reviews`؛ بی AggregateRating و Review — P5-D510 · P5-D234)
 *                ستون کناری (`topersia_g_news_aside`): اشتراک/ذخیره/دنبال‌کردن · موضوع و برچسب‌ها · مکان‌های مرتبط ·
 *                تازه‌ترین‌ها · «منبع و پیوند اصلی» در انتها. موبایل: ستون کناری زیر متن.
 *   مرتبط        «خبرهای مرتبط» / «مقاله‌های مرتبط» — یک ردیف تمام‌عرض زیر هر دو ستون با همان کارت آرشیو؛ نبود ← پنهان
 *
 * «منبع اصلی» دیگر بالای متن نیست؛ فقط در انتهای ستون کناری. اسکیما: مقاله Article (topersia-schema)؛ خبر دست نخورد.
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
$tp_rel    = topersia_g_news_related( $tp_id, 4 );
$tp_crumbs = array(
	array( 'ToPersia', home_url( '/' ) ),
	array( topersia_g_news_l( 'crumb' ), topersia_g_news_archive_url() ),
	array( get_the_title( $tp_id ), '' ),
);

$tp_extra = '<div class="tp-news-hero__meta">' . topersia_g_news_metaline( $tp_id ) . topersia_g_article_byline( $tp_id ) . '</div>';
$tp_aside = topersia_g_news_aside( $tp_id );
$tp_cmts  = function_exists( 'topersia_g_sc_reviews' ) ? topersia_g_sc_reviews( array( 'id' => $tp_id ) ) : '';

get_header();

?>
<div class="tp-page tp-news-page tp-news-single" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
	<main id="tp-main" class="tp-main">
		<?php echo topersia_g_news_hero( topersia_g_news_hero_img( topersia_g_news_image( $tp_id ) ), $tp_crumbs, get_the_title( $tp_id ), topersia_g_news_sum( $tp_id ), $tp_extra ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="tp-wrap">
			<div class="tp-news-cols<?php echo '' === $tp_aside ? ' tp-news-cols--solo' : ''; ?>">
				<div class="tp-news-main">
					<article class="tp-news-body">
						<?php
						while ( have_posts() ) {
							the_post();
							the_content();
						}
						?>
					</article>

					<?php if ( '' !== $tp_cmts ) : ?>
						<div class="tp-news-comments"><?php echo $tp_cmts; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<?php endif; ?>
				</div>

				<?php echo $tp_aside; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

			<?php if ( $tp_rel ) : ?>
				<section class="tp-news-latest tp-news-related" aria-labelledby="tp-news-rel-h">
					<div class="tp-news-latest__head">
						<h2 id="tp-news-rel-h"><?php echo esc_html( topersia_g_news_l( 'related' ) ); ?></h2>
						<a class="tp-more tp-news-all" href="<?php echo esc_url( topersia_g_news_archive_url() ); ?>"><?php echo esc_html( topersia_g_news_l( 'all' ) ); ?></a>
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
