<?php
/**
 * پاورقی همۀ صفحه‌ها — کپی‌رایت، اعتبار و پیوند صفحه‌های ثابت.  دستۀ 45 بخش الف·۲
 *
 * متن عیناً از `docs/legal/PAGES-TEXT-2026-11-04.md` بند ۰ (منبع فارسی؛ en و ar در کاتالوگ).
 * سال از ساعت سرور، میلادی در هر سه زبان، رقم به خطِ زبان صفحه.
 * پیوند اعتبار `target="_blank" rel="noopener nofollow"`، لنگر فقط نام.
 *
 * فقط ستون نخست کپی‌رایت وودمارت (`copyrights`) پر می‌شود؛ ستون دوم (`copyrights2`) دست نمی‌خورد.
 * پیوند صفحه‌ها فقط وقتی صفحه به زبان جاری منتشر شده است؛ عنوانش از خود صفحه.
 */

if ( ! function_exists( 'topersia_g_legal_slugs' ) ) {
	/** نامک لاتین ثابت صفحه‌های ثابت، به ترتیب پیوند پاورقی. */
	function topersia_g_legal_slugs() {
		return array( 'about', 'contact', 'privacy-policy', 'terms' );
	}
}

if ( ! function_exists( 'topersia_g_legal_page' ) ) {
	/** شناسۀ صفحۀ ثابت به زبان جاری، یا ۰ اگر منتشرشده نیست. */
	function topersia_g_legal_page( $slug ) {

		$fa = get_posts( array(
			'post_type'        => 'page',
			'name'             => $slug,
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		) );

		if ( ! $fa ) {
			return 0;
		}

		$id  = (int) apply_filters( 'wpml_object_id', (int) $fa[0], 'page', false );
		$ids = $id ? topersia_g_published( array( $id ) ) : array();

		return $ids ? (int) reset( $ids ) : 0;
	}
}

if ( ! function_exists( 'topersia_g_footer_credits_on' ) ) {
	/**
	 * خط اعتبار پاورقی («خدمات وب: … | طراحی و اجرا: …») — P5-D520 · دستۀ 47.
	 * option `topersia_footer_credits` (پیش‌فرض روشن)؛ «0» یا false فقط همین یک خط را برمی‌دارد،
	 * کپی‌رایت و پیوند صفحه‌ها و ساختار پاورقی وودمارت دست نمی‌خورند.
	 */
	function topersia_g_footer_credits_on() {

		$v = function_exists( 'get_option' ) ? get_option( 'topersia_footer_credits', '1' ) : '1';

		return ! in_array( $v, array( '0', 0, false, 'off', 'no' ), true );
	}
}

if ( ! function_exists( 'topersia_g_footer_html' ) ) {
	function topersia_g_footer_html() {

		$year = topersia_g_fa( wp_date( 'Y' ) );
		$ext  = ' target="_blank" rel="noopener nofollow"';

		/* translators: 1: سال میلادی، 2: نام برند به زبان صفحه (topersia_g_brand — P5-D518) */
		$copy = sprintf( __( '© %1$s ToPersia (%2$s). کلیۀ حقوق این سرویس (وب‌سایت و اپلیکیشن‌های موبایل) محفوظ و متعلق به %2$s می‌باشد. کپی‌برداری، بازنشر، اسکرپ یا هر استفادۀ دیگر از متن‌ها و محتوای این سرویس، در هر جا و به هر شکل، بدون مجوز کتبی ممنوع است و پیگرد قانونی دارد.', 'topersia' ), $year, topersia_g_brand() );

		$credit = esc_html__( 'خدمات وب:', 'topersia' ) . ' <a href="https://www.amesterida.com/"' . $ext . '>' . esc_html__( 'آمستریدا', 'topersia' ) . '</a>'
			. ' | ' . esc_html__( 'طراحی و اجرا:', 'topersia' ) . ' <a href="https://www.bashash.ir/"' . $ext . '>' . esc_html__( 'امیرعباس بشاش', 'topersia' ) . '</a>';

		$links = '';

		foreach ( topersia_g_legal_slugs() as $slug ) {

			$id = topersia_g_legal_page( $slug );

			if ( $id ) {
				$links .= '<li><a href="' . esc_url( get_permalink( $id ) ) . '">' . esc_html( get_the_title( $id ) ) . '</a></li>';
			}
		}

		return '<div class="tp-footer">'
			. ( '' !== $links ? '<nav class="tp-footer__nav" aria-label="' . esc_attr__( 'صفحه‌های سایت', 'topersia' ) . '"><ul>' . $links . '</ul></nav>' : '' )
			. '<p class="tp-footer__copy">' . esc_html( $copy ) . '</p>'
			. ( topersia_g_footer_credits_on() ? '<p class="tp-footer__credit">' . $credit . '</p>' : '' )
			. '</div>';
	}
}

/* ستون نخست کپی‌رایت وودمارت؛ نوار کپی‌رایت همیشه روشن. ستون دوم دست نمی‌خورد. */
add_filter( 'woodmart_option', function ( $value, $slug ) {

	if ( is_admin() ) {
		return $value;
	}

	if ( 'disable_copyrights' === $slug ) {
		return true;
	}

	if ( 'copyrights' === $slug ) {
		return topersia_g_footer_html();
	}

	return $value;
}, 10, 2 );

/* چیدمان پاورقی — فقط توکن؛ بی پروندۀ تازه. هدف لمسی پیوند صفحه‌ها ≥ ۴۴px. */
add_action( 'wp_enqueue_scripts', function () {

	if ( ! wp_style_is( 'topersia-tokens', 'enqueued' ) ) {
		return;
	}

	wp_add_inline_style( 'topersia-tokens', 'html:root .tp-footer{display:grid;gap:6px;padding-block:8px;font-size:calc(.86 * var(--tp-rem,16px));line-height:1.9;color:var(--tp-text)}'
		. 'html:root .tp-footer p{margin:0;color:var(--tp-text)}'
		. 'html:root .tp-footer a{color:var(--tp-primary-text);text-decoration:underline;text-underline-offset:2px}'
		. 'html:root .tp-footer a:hover{color:var(--tp-text-strong)}'
		. 'html:root .tp-footer__nav ul{display:flex;flex-wrap:wrap;gap:0 20px;margin:0;padding:0;list-style:none}'
		. 'html:root .tp-footer__nav li{margin:0}'
		. 'html:root .tp-footer__nav a{display:inline-flex;align-items:center;min-block-size:44px;font-weight:700}' );
}, 21 );
