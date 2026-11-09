<?php
/**
 * حالت پیش‌نمایش نمایشی — P5-D548 (دستۀ 51)
 *
 * فقط محلی و پیش‌فرض خاموش: روشن فقط وقتی ثابت TOPERSIA_DEMO_PREVIEW در wp-config برابر true باشد و
 * میزبان سایت محلی باشد (localhost · 127.0.0.1 · *.local · *.localhost · *.test — همان قاعدۀ
 * topersia_pack_uid_is_local هسته). روی هر میزبان دیگر همیشه خاموش.
 *
 * پست نمایشی = متای `_tp_demo` = '1' (همیشه همراه `_tp_sample` = '1'، ساختۀ scripts/tp-demo-seed.php).
 *   خاموش: رفتار پیشین بی تغییر؛ پست نمایشی مثل هر نمونه بیرون است و صفحۀ تکی‌اش ۴۰۴.
 *   روشن : کوئری‌های عمومی که نمونه را کنار می‌گذارند، پست نمایشی را (و فقط آن را) هم می‌آورند؛
 *          نشان «نمونۀ نمایشی» روی کارت و صفحه؛ همۀ صفحه‌ها noindex.
 * هرگز: اسکیما، sitemap، جست‌وجوی عمومی، SearchAction.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'topersia_demo_host_local' ) ) {
	/** میزبان محلی؟ همان قاعدۀ topersia_pack_uid_is_local (P5-D495). */
	function topersia_demo_host_local( $url = '' ) {
		$url  = '' !== (string) $url ? (string) $url : (string) home_url( '/' );
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		return '' !== $host && ( 'localhost' === $host || '127.0.0.1' === $host || (bool) preg_match( '/\.(local|localhost|test)$/', $host ) );
	}
}

if ( ! function_exists( 'topersia_demo_on' ) ) {
	/** حالت پیش‌نمایش نمایشی روشن است؟ ثابت true و میزبان محلی — هر دو. */
	function topersia_demo_on() {
		return defined( 'TOPERSIA_DEMO_PREVIEW' ) && true === TOPERSIA_DEMO_PREVIEW && topersia_demo_host_local();
	}
}

if ( ! function_exists( 'topersia_g_is_demo' ) ) {
	/** این پست نمایشی است (متای _tp_demo)؟ مستقل از روشن بودن حالت. */
	function topersia_g_is_demo( $id ) {
		return '1' === (string) get_post_meta( (int) $id, '_tp_demo', true );
	}
}

if ( ! function_exists( 'topersia_g_sample_mq' ) ) {
	/**
	 * meta_query «غیرنمونه» — یک منبع برای همۀ کوئری‌های عمومی.
	 * خاموش: دقیقاً همان قاعدۀ پیشین (_tp_sample NOT EXISTS). روشن: یا غیرنمونه، یا نمایشی.
	 */
	function topersia_g_sample_mq() {
		$real = array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' );
		if ( ! topersia_demo_on() ) {
			return array( $real );
		}
		return array( array( 'relation' => 'OR', $real, array( 'key' => '_tp_demo', 'value' => '1' ) ) );
	}
}

if ( ! function_exists( 'topersia_g_sample_hidden' ) ) {
	/** پست از دید عموم پنهان است؟ نمونه پنهان است مگر نمایشی با حالت روشن. */
	function topersia_g_sample_hidden( $id ) {
		if ( ! metadata_exists( 'post', (int) $id, '_tp_sample' ) ) {
			return false;
		}
		return ! ( topersia_demo_on() && topersia_g_is_demo( $id ) );
	}
}

if ( ! function_exists( 'topersia_g_demo_badge' ) ) {
	/** نشان کوچک «نمونۀ نمایشی» — فقط برای پست نمایشی با حالت روشن؛ وگرنه رشتۀ تهی. */
	function topersia_g_demo_badge( $id ) {
		if ( ! topersia_demo_on() || ! topersia_g_is_demo( $id ) ) {
			return '';
		}
		return '<span class="tp-demo-badge">' . esc_html__( 'نمونۀ نمایشی', 'topersia' ) . '</span>';
	}
}

if ( ! function_exists( 'topersia_g_demo_strip' ) ) {
	/** شناسه‌های نمایشی را بیرون می‌گذارد — برای اسکیما و هر خروجی ماشین‌خوان. */
	function topersia_g_demo_strip( $ids ) {
		return array_values( array_filter( array_map( 'absint', (array) $ids ), function ( $id ) {
			return $id && ! topersia_g_is_demo( $id );
		} ) );
	}
}

if ( function_exists( 'add_action' ) ) {

	/* صفحۀ تکی نمایشی: حالت خاموش ← ۴۰۴ (هیچ پست نمایشی در هیچ صفحه). */
	add_action( 'template_redirect', function () {
		if ( is_singular() && topersia_g_is_demo( get_queried_object_id() ) && ! topersia_demo_on() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
		}
	}, 1 );

	/* روشن ← همۀ صفحه‌ها noindex؛ پست نمایشی همیشه noindex (هستۀ وردپرس و Rank Math). */
	$tp_demo_noindex = function () {
		return topersia_demo_on() || ( is_singular() && topersia_g_is_demo( get_queried_object_id() ) );
	};
	add_filter( 'wp_robots', function ( $robots ) use ( $tp_demo_noindex ) {
		if ( $tp_demo_noindex() ) {
			unset( $robots['index'], $robots['max-image-preview'] );
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}
		return $robots;
	}, 99 );
	add_filter( 'rank_math/frontend/robots', function ( $robots ) use ( $tp_demo_noindex ) {
		if ( $tp_demo_noindex() ) {
			$robots['index']  = 'noindex';
			$robots['follow'] = 'nofollow';
		}
		return $robots;
	}, 99 );

	/* sitemap هرگز: Rank Math و sitemap هستۀ وردپرس. */
	add_filter( 'rank_math/sitemap/posts_to_exclude', function ( $ids ) {
		global $wpdb;
		$demo = array_map( 'absint', (array) $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tp_demo'" ) );
		return array_values( array_unique( array_merge( array_map( 'absint', (array) $ids ), $demo ) ) );
	} );
	add_filter( 'rank_math/sitemap/entry', function ( $url, $type, $obj ) {
		return ( 'post' === $type && is_object( $obj ) && isset( $obj->ID ) && topersia_g_is_demo( $obj->ID ) ) ? false : $url;
	}, 10, 3 );
	add_filter( 'wp_sitemaps_posts_query_args', function ( $args ) {
		$args['meta_query'] = array( array( 'key' => '_tp_demo', 'compare' => 'NOT EXISTS' ) );
		return $args;
	} );

	/* نشان صفحه: نوار کوچک بالای صفحۀ تکی نمایشی. */
	add_action( 'wp_body_open', function () {
		if ( is_singular() && '' !== topersia_g_demo_badge( get_queried_object_id() ) ) {
			echo '<div class="tp-demo-flag" role="note">' . topersia_g_demo_badge( get_queried_object_id() ) . '</div>';
		}
	} );

	/* شیوۀ نشان — فقط با حالت روشن، از توکن‌ها. */
	add_action( 'wp_head', function () {
		if ( ! topersia_demo_on() ) {
			return;
		}
		echo '<style id="tp-demo-css">'
			. '.tp-demo-badge{display:inline-flex;align-items:center;min-block-size:22px;padding:2px 10px;border-radius:999px;'
			. 'background:var(--tp-surface);color:var(--tp-text-strong);border:1px dashed var(--tp-text);font-size:12px;font-weight:700;line-height:1.4}'
			. '.tp-demo-flag{display:flex;justify-content:center;padding:6px 16px;background:var(--tp-bg)}'
			. '</style>';
	}, 30 );
}
