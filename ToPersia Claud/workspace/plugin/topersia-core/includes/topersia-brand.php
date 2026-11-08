<?php
/**
 * نام برند به زبان صفحه — P5-D518 (دستۀ 47). تنها منبع؛ هیچ جای دیگری نام فارسی یا عربی را ننویسد.
 *
 *   fa «توپرشیا» · ar «توبرشيا» (املای قطعی کاربر، ۸ نوامبر) · هر زبان دیگر «ToPersia».
 *
 * فقط برای متن دیدنی (رشتۀ رابط، عنوان و متای صفحه، og:site_name). نامک، لوگو، نام کد، دامنه،
 * نام افزونه و تنظیمات وودمارت دست نمی‌خورند. اسکیما: name همیشه «ToPersia»، alternateName
 * از topersia_brand_alternates().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_BRAND' ) ) {
	define( 'TOPERSIA_BRAND', 'ToPersia' );
}

if ( ! function_exists( 'topersia_brand_names' ) ) {
	/** زبان ← نام. عربی بی «ي» میانی. */
	function topersia_brand_names() {
		return array(
			'fa' => 'توپرشیا',
			'ar' => 'توبرشيا',
		);
	}
}

if ( ! function_exists( 'topersia_brand_name' ) ) {
	/**
	 * @param string|null $lang کد زبان (fa · en · ar …)؛ تهی ← زبان جاری WPML، وگرنه locale.
	 * @return string
	 */
	function topersia_brand_name( $lang = null ) {

		if ( null === $lang || '' === $lang ) {
			$lang = function_exists( 'apply_filters' ) ? (string) apply_filters( 'wpml_current_language', null ) : '';
			if ( '' === $lang && function_exists( 'determine_locale' ) ) {
				$lang = substr( (string) determine_locale(), 0, 2 );
			}
		}

		$names = topersia_brand_names();
		$lang  = strtolower( substr( (string) $lang, 0, 2 ) );

		return isset( $names[ $lang ] ) ? $names[ $lang ] : TOPERSIA_BRAND;
	}
}

if ( ! function_exists( 'topersia_brand_localize_title' ) ) {
	/**
	 * پسوند برند در عنوان و og/twitter به زبان صفحه: «… - ToPersia» ← «… - توپرشیا».
	 * فقط پسوندِ پس از جداکننده؛ نام درون متن (مثل «© … ToPersia (…)») دست نمی‌خورد.
	 * جداکننده‌ها: «-» تنظیم Rank Math و «—» پسوند هسته (seo_brand_suffix).
	 */
	function topersia_brand_localize_title( $title ) {

		$brand = topersia_brand_name();

		if ( ! is_string( $title ) || '' === $title || TOPERSIA_BRAND === $brand ) {
			return $title;
		}

		$out = preg_replace( '/(\s[-—–|·]\s)' . preg_quote( TOPERSIA_BRAND, '/' ) . '\s*$/u', '${1}' . $brand, $title );

		return is_string( $out ) ? $out : $title;
	}
}

if ( ! function_exists( 'topersia_brand_site_name' ) ) {
	/** og:site_name به زبان صفحه. */
	function topersia_brand_site_name( $name ) {
		return TOPERSIA_BRAND === trim( (string) $name ) ? topersia_brand_name() : $name;
	}
}

if ( function_exists( 'add_filter' ) ) {
	/* ۹۹: پس از topersia_seo_title (۲۰) و صافی‌های render. */
	add_filter( 'rank_math/frontend/title', 'topersia_brand_localize_title', 99 );
	add_filter( 'rank_math/opengraph/facebook/og_title', 'topersia_brand_localize_title', 99 );
	add_filter( 'rank_math/opengraph/twitter/twitter_title', 'topersia_brand_localize_title', 99 );
	add_filter( 'rank_math/opengraph/facebook/og_site_name', 'topersia_brand_site_name', 99 );
}

if ( ! function_exists( 'topersia_brand_alternates' ) ) {
	/** alternateName اسکیما — همۀ نام‌های غیرلاتین. */
	function topersia_brand_alternates() {
		return array_values( topersia_brand_names() );
	}
}
