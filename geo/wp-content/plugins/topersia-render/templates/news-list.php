<?php
/**
 * صفحۀ خبرهای یک مکان — /{rewrite}/{slug}/news/ · /page/N/
 * CHANGE-PACKAGE-001 بند ۷٫۸ · ۴٫۳٫۰ بند ۶
 *
 * عنوان «اخبار {نام کامل}»؛ ده خبر در هر صفحه، تازه‌ترین اول، بی مرز
 * زمانی؛ کارت همان کارت بخش `#news`. زیر سه خبر noindex, follow — در
 * topersia-g-news.php. اسکیما: CollectionPage با about به @id موجودیت.
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tp_place = (int) get_queried_object_id();
$tp_paged = max( 1, (int) get_query_var( 'paged' ) );
$tp_q     = topersia_g_news_query( $tp_place, 10, 0, $tp_paged );
$tp_pages = (int) ceil( $tp_q['total'] / 10 );
$tp_title = topersia_g_news_title( $tp_place );

add_action( 'wp_head', function () use ( $tp_place, $tp_title ) {

	$ld = array(
		'@context' => 'https://schema.org',
		'@type'    => 'CollectionPage',
		'name'     => $tp_title,
		'url'      => topersia_g_news_list_url( $tp_place, max( 1, (int) get_query_var( 'paged' ) ) ),
	);

	$about = trim( (string) get_post_meta( $tp_place, 'entity_uid', true ) );

	if ( '' !== $about ) {
		$ld['about'] = array( '@id' => $about );
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 30 );

get_header();

?>
<?php /* جهت از زبان صفحه، نه ثابت — نسخۀ انگلیسیِ این برگه چپ‌به‌راست است.  ۲٫۰٫۸ */ ?>
<div class="tp-page tp-news-page" dir="<?php echo esc_attr( is_rtl() ? 'rtl' : 'ltr' ); ?>">
	<main id="tp-main" class="tp-main">
		<div class="tp-wrap">
			<p class="tp-crumb" aria-label="<?php echo esc_attr__( 'مسیر راهنما', 'topersia' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">ToPersia</a><span>›</span>
				<a href="<?php echo esc_url( (string) get_permalink( $tp_place ) ); ?>"><?php echo esc_html( topersia_g_title( $tp_place ) ); ?></a><span>›</span>
				<span aria-current="page"><?php echo esc_html__( 'اخبار', 'topersia' ); ?></span>
			</p>

			<section id="tp-news" class="tp-section">
				<div class="tp-section-head"><div>
					<h1><?php echo esc_html( $tp_title ); ?></h1>
					<p class="tp-lead"><?php echo esc_html__( 'محتوای زمانی با تاریخ صریح و لینک به منبع اصلی.', 'topersia' ); ?></p>
				</div></div>

				<?php if ( $tp_q['ids'] ) : ?>
					<div class="tp-news">
						<?php foreach ( $tp_q['ids'] as $tp_one ) { echo topersia_g_news_card( $tp_one ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php else : ?>
					<p class="tp-lead"><?php echo esc_html__( 'هنوز خبری برای این مکان نیست.', 'topersia' ); ?></p>
				<?php endif; ?>

				<?php if ( $tp_pages > 1 ) : ?>
					<nav class="tp-news-pages" aria-label="<?php echo esc_attr__( 'صفحه‌های خبر', 'topersia' ); ?>">
						<?php for ( $tp_i = 1; $tp_i <= $tp_pages; $tp_i ++ ) : ?>
							<?php if ( $tp_i === $tp_paged ) : ?>
								<span aria-current="page"><?php echo esc_html( topersia_g_fa( $tp_i ) ); ?></span>
							<?php else : ?>
								<a href="<?php echo esc_url( topersia_g_news_list_url( $tp_place, $tp_i ) ); ?>"><?php echo esc_html( topersia_g_fa( $tp_i ) ); ?></a>
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
