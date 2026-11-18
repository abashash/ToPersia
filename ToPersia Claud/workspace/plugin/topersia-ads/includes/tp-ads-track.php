<?php
/**
 * شمارش بی‌نام — دستۀ 54 بند ۹ (P5-D622).
 *
 *   کلیک   /ad-go/{id}/?s={slot}&l={lang} ← ۳۰۲ به مقصد؛ noindex و no-store؛ ربات، پیش‌واکشی، مدیر تبلیغ و کلیک تکراری
 *          (همان هش روزانه و همان تبلیغ تا ۳۰ دقیقه، transient) شمرده نمی‌شوند. IP خام هرگز ذخیره نمی‌شود.
 *   نمایش  POST /wp-json/topersia-ads/v1/imp  بدنۀ text/plain (sendBeacon) {t, a:["id|slot|lang", …]} — ≥ ۵۰٪ دیده‌شدن
 *          به‌مدت ۱ ثانیه (IntersectionObserver در tp-ads.js). توکن روزانه به‌جای nonce کاربر (HTML کش‌شده و مهمان)،
 *          سقف نرخ به‌ازای هش روزانه، حداکثر ۲۰ واحد در هر درخواست.
 *   robots /ad-go/ در هر گروه robots.txt فیزیکی ریشه (و فیلتر robots_txt وقتی پرونده نیست).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_ADS_RATE' ) ) {
	define( 'TOPERSIA_ADS_RATE', 120 );        /* بیشینۀ نمایش پذیرفته از یک هش روزانه در هر ۱۰ دقیقه */
}
if ( ! defined( 'TOPERSIA_ADS_BATCH' ) ) {
	define( 'TOPERSIA_ADS_BATCH', 20 );        /* بیشینۀ واحد در یک درخواست */
}

if ( ! function_exists( 'topersia_ads_rewrite' ) ) {
	/** مسیر /ad-go/{id}/ — فعال‌سازی هم همین را صدا می‌زند. */
	function topersia_ads_rewrite() {
		add_rewrite_rule( '^ad-go/([0-9]+)/?$', 'index.php?tp_ad_go=$matches[1]', 'top' );
	}
}
add_action( 'init', 'topersia_ads_rewrite' );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'tp_ad_go';
	return $vars;
} );

if ( ! function_exists( 'topersia_ads_visitor_hash' ) ) {
	/** هش روزانۀ بازدیدکننده — فقط REMOTE_ADDR (هیچ سرآیند قابل‌جعل)، نمک وردپرس. */
	function topersia_ads_visitor_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		return topersia_ads_day_hash( $ip, $ua, (int) floor( time() / 86400 ), wp_salt( 'auth' ) );
	}
}

if ( ! function_exists( 'topersia_ads_ua' ) ) {
	function topersia_ads_ua() {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	}
}

if ( ! function_exists( 'topersia_ads_prefetch' ) ) {
	/** درخواست پیش‌واکشی یا پیش‌رندر مرورگر — کلیک نیست. */
	function topersia_ads_prefetch() {
		foreach ( array( 'HTTP_SEC_PURPOSE', 'HTTP_PURPOSE', 'HTTP_X_PURPOSE', 'HTTP_X_MOZ' ) as $h ) {
			if ( isset( $_SERVER[ $h ] ) && preg_match( '/prefetch|prerender|preview/i', (string) $_SERVER[ $h ] ) ) {   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'topersia_ads_click' ) ) {
	/**
	 * پردازش کلیک: [ مقصد یا تهی، شمرده شد؟ ]. جدا از خروج تا آزمون بی exit بسنجد.
	 *
	 * @return array{0:string,1:bool}
	 */
	function topersia_ads_click( $id, $slot, $lang ) {

		$all = topersia_ads_all();
		$ad  = isset( $all[ (int) $id ] ) ? $all[ (int) $id ] : null;

		if ( ! $ad || '' === $ad['url'] || $ad['demo'] ) {
			return array( '', false );
		}

		$slot = isset( topersia_ads_slots()[ $slot ] ) && in_array( $slot, $ad['slots'], true ) ? $slot : ( $ad['slots'] ? $ad['slots'][0] : '' );
		$lang = in_array( $lang, array( 'fa', 'en', 'ar' ), true ) ? $lang : 'fa';

		if ( 'active' !== $ad['status'] || '' === $slot || topersia_ads_is_bot( topersia_ads_ua() ) || topersia_ads_prefetch() || current_user_can( TOPERSIA_ADS_CAP ) ) {
			return array( $ad['url'], false );
		}

		$key = topersia_ads_click_key( topersia_ads_visitor_hash(), $ad['id'] );
		if ( get_transient( $key ) ) {
			return array( $ad['url'], false );
		}
		set_transient( $key, 1, TOPERSIA_ADS_DUP_TTL );

		return array( $ad['url'], (bool) topersia_ads_record( $ad['id'], $slot, $lang, 0, 1 ) );
	}
}

add_action( 'parse_request', function ( $wp ) {
	if ( empty( $wp->query_vars['tp_ad_go'] ) ) {
		return;
	}

	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );

	$slot = isset( $_GET['s'] ) ? sanitize_key( wp_unslash( $_GET['s'] ) ) : '';   // phpcs:ignore WordPress.Security.NonceVerification
	$lang = isset( $_GET['l'] ) ? sanitize_key( wp_unslash( $_GET['l'] ) ) : '';   // phpcs:ignore WordPress.Security.NonceVerification
	$res  = topersia_ads_click( absint( $wp->query_vars['tp_ad_go'] ), $slot, $lang );

	if ( '' === $res[0] ) {
		status_header( 404 );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		echo 'Not found';
		exit;
	}

	/* مقصد بیرونی است — wp_safe_redirect میزبان بیرونی را رد می‌کند؛ نشانی در ذخیره و norm اعتبارسنجی شده */
	wp_redirect( esc_url_raw( $res[0] ), 302, 'ToPersia' );   // phpcs:ignore WordPress.Security.SafeRedirect
	exit;
}, 0 );

if ( ! function_exists( 'topersia_ads_imp' ) ) {
	/**
	 * پذیرش گروه نمایش. ورودی خام بدنه؛ خروجی شمار پذیرفته (۰ برای هر ورودی ناجور، ربات یا بیش از سقف نرخ).
	 */
	function topersia_ads_imp( $body ) {

		$in = json_decode( (string) $body, true );

		if ( ! is_array( $in ) || empty( $in['t'] ) || empty( $in['a'] ) || ! is_array( $in['a'] ) ) {
			return 0;
		}
		if ( ! topersia_ads_token_ok( (string) $in['t'], time(), wp_salt( 'nonce' ) ) || topersia_ads_is_bot( topersia_ads_ua() ) ) {
			return 0;
		}

		$rate = 'tp_adr_' . substr( md5( topersia_ads_visitor_hash() . '|' . topersia_ads_window( time() ) ), 0, 24 );
		$used = (int) get_transient( $rate );
		$room = max( 0, TOPERSIA_ADS_RATE - $used );

		if ( ! $room ) {
			return 0;
		}

		$all   = topersia_ads_all();
		$slots = topersia_ads_slots();
		$sum   = array();

		foreach ( array_slice( array_unique( array_map( 'strval', $in['a'] ) ), 0, min( TOPERSIA_ADS_BATCH, $room ) ) as $item ) {
			$p = explode( '|', $item );
			if ( 3 !== count( $p ) ) {
				continue;
			}
			$id = absint( $p[0] );
			if ( ! isset( $all[ $id ], $slots[ $p[1] ] ) || $all[ $id ]['demo'] || 'active' !== $all[ $id ]['status'] || ! in_array( $p[1], $all[ $id ]['slots'], true ) || ! in_array( $p[2], array( 'fa', 'en', 'ar' ), true ) ) {
				continue;
			}
			$k         = $id . '|' . $p[1] . '|' . $p[2];
			$sum[ $k ] = isset( $sum[ $k ] ) ? $sum[ $k ] + 1 : 1;
		}

		$n = 0;
		foreach ( $sum as $k => $c ) {
			list( $id, $slot, $lang ) = explode( '|', $k );
			if ( topersia_ads_record( (int) $id, $slot, $lang, $c, 0 ) ) {
				$n += $c;
			}
		}

		if ( $n ) {
			set_transient( $rate, $used + $n, TOPERSIA_ADS_WINDOW );
		}
		return $n;
	}
}

add_action( 'rest_api_init', function () {
	register_rest_route( 'topersia-ads/v1', '/imp', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',   /* بی‌نام عمداً؛ توکن روزانه، سقف نرخ و اعتبارسنجی هر واحد در topersia_ads_imp() */
		'callback'            => function ( WP_REST_Request $req ) {
			$body = (string) $req->get_body();
			if ( strlen( $body ) > 4096 ) {
				return new WP_REST_Response( null, 413 );
			}
			topersia_ads_imp( $body );
			$res = new WP_REST_Response( null, 204 );
			$res->header( 'Cache-Control', 'no-store' );
			$res->header( 'X-Robots-Tag', 'noindex' );
			return $res;
		},
	) );
} );

add_filter( 'robots_txt', function ( $out ) {
	return rtrim( (string) $out ) . "\nDisallow: /ad-go/\n";
}, 20 );
