<?php
/**
 * صفحۀ تکی خبر `tpc_news` و مقاله `tpc_article` — دستۀ 50 (P5-D541) · دستۀ 52 (P5-D567) · دستۀ 53 (P5-D573 · P5-D574)
 *
 * اسکلت صفحه همان صفحۀ استان است (`topersia_g_sc_page()`)، فقط «محتوا» متن خبر/مقاله است — P5-D573، جایگزین P5-D568:
 *   سربرگ        دست‌نخورده: تصویر خود مطلب ← تصویر مکان مرتبط (هرگز نمونه) ← گرادیان · مسیر راهنما · H1 · خلاصه
 *                · سطر وسط‌چین «موضوع · تاریخ · زمان مطالعه» (مقاله: به‌روزرسانی و نویسنده زیر آن)
 *   نوار بخش‌ها   `nav.tp-snav` چسبان: متن · مکان‌های مرتبط · منبع · نظرات · خبرهای مرتبط — فقط بخش دارای داده
 *   دو ستون      `.tp-content-grid > .tp-main-col + aside.tp-rail` همان قالب موجودیت:
 *                ستون اصلی: متن ← نظرات (همان جزء `topersia_g_sc_reviews`، بی ستاره و بی AggregateRating/Review — P5-D574) ← مرتبط‌ها
 *                ستون کناری: اشتراک/ذخیره/دنبال‌کردن (`#tp-actions`) · موضوع و برچسب‌ها · مکان‌های مرتبط · تازه‌ترین‌ها · منبع
 *   موبایل       همان صفحۀ استان: جعبۀ اشتراک بالای محتوا (اسکریپت «نوار کنش»)، ستون‌ها زیر هم، نوار پایین و برگۀ «بخش‌ها»
 *
 * همۀ ساخت در `topersia_g_news_single()` (topersia-g-article.php). اسکیما: مقاله Article (topersia-schema)؛ خبر دست نخورد.
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
$tp_crumbs = array(
	array( 'ToPersia', home_url( '/' ) ),
	array( topersia_g_news_l( 'crumb' ), topersia_g_news_archive_url() ),
	array( get_the_title( $tp_id ), '' ),
);

$tp_extra = '<div class="tp-news-hero__meta">' . topersia_g_news_metaline( $tp_id ) . topersia_g_article_byline( $tp_id ) . '</div>';

/* متن — پیش از get_header تا خطای ناحیه صفحه را نیمه‌کاره نگذارد */
ob_start();
while ( have_posts() ) {
	the_post();
	the_content();
}
$tp_text = (string) ob_get_clean();

get_header();

?>
<div class="tp-page tp-news-page tp-news-single" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
	<?php
	echo topersia_g_news_hero( topersia_g_news_hero_img( topersia_g_news_image( $tp_id ) ), $tp_crumbs, get_the_title( $tp_id ), topersia_g_news_sum( $tp_id ), $tp_extra ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo topersia_g_news_single( $tp_id, $tp_text ); // phpcs:ignore WordPress.Security.EscapeOutput
	?>
</div>
<?php

get_footer();
