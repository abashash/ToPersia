<?php
/**
 * کلید کش قطعۀ نتایج — P5-D571 (دستۀ 52 بند ۱۳). PHP خالص، بی وردپرس.
 *
 * دو مصرف‌کننده باید دقیقاً یک کلید بسازند:
 *   • نویسنده   topersia-g-news-ui.php — پاسخ `?tp_partial=1` را پس از رندر در پرونده می‌گذارد
 *   • خواننده   fragment.php — بی بارکردن وردپرس همان پرونده را برمی‌گرداند
 * کلید = md5( میزبان | مسیر (زبان در خود مسیر است: /en/…) | پارامترهای مجاز به ترتیب ثابت ).
 * هر پارامتر بیرون از فهرست (جست‌وجوی آزاد `tp_q`، نام مکان `tp_loc`، هر چیز ناشناس) ← کلید تهی = کش نمی‌شود.
 */

if ( ! defined( 'TOPERSIA_FRAG_TTL' ) ) {
	define( 'TOPERSIA_FRAG_TTL', 21600 );  /* ثانیه (شش ساعت) — سقف عمر هر پرونده، جدا از ابطال هنگام ذخیره */
}
if ( ! defined( 'TOPERSIA_FRAG_MAX' ) ) {
	define( 'TOPERSIA_FRAG_MAX', 400 );    /* بیش از این پرونده ← همه پاک */
}

if ( ! function_exists( 'topersia_frag_params' ) ) {
	/** پارامترهای GET که در کلید می‌آیند — فهرست بسته، ترتیب ثابت. */
	function topersia_frag_params() {
		return array( 'season', 'tp_facet', 'tp_kind', 'tp_place', 'tp_sort', 'tp_topic', 'tp_when' );
	}
}

if ( ! function_exists( 'topersia_frag_key' ) ) {
	/**
	 * @param string $host میزبان درخواست.
	 * @param string $path مسیر نشانی (بی query).
	 * @param array  $get  پارامترهای GET (بی نیاز به پاک‌سازی؛ فقط مقدار عددی/متنی کوتاه پذیرفته می‌شود).
	 * @return string md5 یا تهی (کش‌ناپذیر).
	 */
	function topersia_frag_key( $host, $path, array $get ) {

		$host = strtolower( trim( (string) $host ) );
		$path = '/' . trim( (string) preg_replace( '#/+#', '/', (string) $path ), '/' ) . '/';
		$ok   = topersia_frag_params();
		$q    = array();

		if ( '' === $host || strlen( $path ) > 300 || false !== strpos( $path, '..' ) ) {
			return '';
		}

		foreach ( $get as $k => $v ) {
			if ( 'tp_partial' === $k ) {
				continue;
			}
			if ( ! in_array( $k, $ok, true ) || ! is_scalar( $v ) || strlen( (string) $v ) > 80 ) {
				return '';
			}
		}

		foreach ( $ok as $k ) {
			if ( isset( $get[ $k ] ) && '' !== trim( (string) $get[ $k ] ) ) {
				$q[ $k ] = trim( (string) $get[ $k ] );
			}
		}

		return md5( $host . '|' . $path . '|' . http_build_query( $q ) );
	}
}

if ( ! function_exists( 'topersia_frag_dir' ) ) {
	/** پوشۀ کش — زیر wp-content/uploads؛ هر دو مصرف‌کننده از wp-content می‌سازند. */
	function topersia_frag_dir( $content_dir ) {
		return rtrim( str_replace( '\\', '/', (string) $content_dir ), '/' ) . '/uploads/topersia-cache/frag';
	}
}
