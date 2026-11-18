<?php
/**
 * نوع خصوصی `tp_ad` و فیلدهای آن — دستۀ 54 بند ۳.
 *
 * خصوصی: public=false، publicly_queryable=false، بی rewrite، بی query_var، بی REST (show_in_rest=false)، بیرون از
 * جست‌وجو و نقشۀ سایت (وردپرس و Rank Math). ویرایشگر کلاسیک مدیریت و revision — متای `_tp_ad` با revisions_enabled.
 * ترجمۀ WPML ندارد (wpml-config.xml: translate=0)؛ زبان یک فیلد است، متن جایگزین برای هر زبان جدا.
 *
 * متا (یک کلید، تا revision و برگشت یک‌جا باشد):
 *   _tp_ad   { advertiser, url, slots[], img{wide,mwide,rect,card}, alt{fa,en,ar}, langs[], device, pages[],
 *              start, end (مهر زمانی UTC؛ ۰ = بی مرز), cap_imp, cap_clk, weight, priority, status, note }
 *   _tp_ad_imp · _tp_ad_clk   شمار کل (برای سقف؛ جدا از revision، با هر ثبت آمار بالا می‌رود)
 *   _tp_demo · _tp_sample     تبلیغ نمایشی (scripts/tp-ads-demo.php) — فقط با topersia_demo_on()؛ گیت --launch
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'topersia_ads_caps' ) ) {
	/** توان‌های اولیۀ نوع پست (capability_type tp_ad/tp_ads) و پنل. */
	function topersia_ads_caps() {
		return array( TOPERSIA_ADS_CAP, 'edit_tp_ads', 'edit_others_tp_ads', 'edit_published_tp_ads', 'edit_private_tp_ads', 'publish_tp_ads',
			'read_private_tp_ads', 'delete_tp_ads', 'delete_others_tp_ads', 'delete_published_tp_ads', 'delete_private_tp_ads' );
	}
}

if ( ! function_exists( 'topersia_ads_grant_caps' ) ) {
	/** فقط نقش مدیر؛ نقش فروش را کاربر بعداً با همین توان‌ها می‌سازد. */
	function topersia_ads_grant_caps() {
		$role = get_role( 'administrator' );
		if ( $role ) {
			foreach ( topersia_ads_caps() as $cap ) {
				$role->add_cap( $cap );
			}
		}
		update_option( 'tp_ads_caps', TOPERSIA_ADS_VERSION, false );
	}
}

if ( ! function_exists( 'topersia_ads_register_type' ) ) {
	function topersia_ads_register_type() {
		register_post_type( 'tp_ad', array(
			'labels'              => array(
				'name'          => __( 'تبلیغ‌ها', 'topersia-ads' ),
				'singular_name' => __( 'تبلیغ', 'topersia-ads' ),
				'add_new'       => __( 'کمپین تازه', 'topersia-ads' ),
				'add_new_item'  => __( 'کمپین تازه', 'topersia-ads' ),
				'edit_item'     => __( 'ویرایش کمپین', 'topersia-ads' ),
				'all_items'     => __( 'کمپین‌ها', 'topersia-ads' ),
				'search_items'  => __( 'جست‌وجوی کمپین', 'topersia-ads' ),
				'not_found'     => __( 'کمپینی نیست.', 'topersia-ads' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'topersia-ads',
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => false,
			'supports'            => array( 'title', 'revisions' ),
			'capability_type'     => array( 'tp_ad', 'tp_ads' ),
			'map_meta_cap'        => true,
		) );

		register_post_meta( 'tp_ad', '_tp_ad', array(
			'type'              => 'object',
			'single'            => true,
			'show_in_rest'      => false,
			'revisions_enabled' => true,
			'auth_callback'     => function () {
				return current_user_can( TOPERSIA_ADS_CAP );
			},
		) );
	}
}

add_action( 'init', 'topersia_ads_register_type', 5 );

/* توان مدیر پس از به‌روزرسانی افزونه (فعال‌سازی دوباره لازم نباشد) */
add_action( 'admin_init', function () {
	if ( TOPERSIA_ADS_VERSION !== get_option( 'tp_ads_caps' ) ) {
		topersia_ads_grant_caps();
	}
} );

/* نقشۀ سایت هرگز: وردپرس (نوع غیرعمومی خودش بیرون است) و Rank Math */
add_filter( 'rank_math/sitemap/exclude_post_type', function ( $exclude, $type ) {
	return 'tp_ad' === $type ? true : $exclude;
}, 10, 2 );

if ( ! function_exists( 'topersia_ads_raw' ) ) {
	/** متای خام یک تبلیغ با شناسه، عنوان، شمارها و نشان نمایشی. */
	function topersia_ads_raw( $id ) {
		$raw = get_post_meta( (int) $id, '_tp_ad', true );
		$raw = is_array( $raw ) ? $raw : array();
		return array_merge( $raw, array(
			'id'    => (int) $id,
			'title' => (string) get_the_title( (int) $id ),
			'imp'   => (int) get_post_meta( (int) $id, '_tp_ad_imp', true ),
			'clk'   => (int) get_post_meta( (int) $id, '_tp_ad_clk', true ),
			'demo'  => '1' === (string) get_post_meta( (int) $id, '_tp_demo', true ),
		) );
	}
}

if ( ! function_exists( 'topersia_ads_all' ) ) {
	/**
	 * همۀ تبلیغ‌های منتشرشده، نرمال — یک بار در هر درخواست (دو پرس‌وجو: شناسه‌ها و کش متا).
	 * پیش‌نویس، زباله و خصوصی هرگز. انتخاب و فیلتر در topersia_ads_pick().
	 */
	function topersia_ads_all( $fresh = false ) {
		static $all = null;
		if ( null !== $all && ! $fresh ) {
			return $all;
		}
		$ids = array_map( 'absint', (array) get_posts( array(
			'post_type'        => 'tp_ad',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		) ) );
		if ( $ids ) {
			update_meta_cache( 'post', $ids );
		}
		$all = array();
		foreach ( $ids as $id ) {
			$all[ $id ] = topersia_ads_norm( topersia_ads_raw( $id ) );
		}
		return $all;
	}
}

if ( ! function_exists( 'topersia_ads_bump' ) ) {
	/** هر تغییر تبلیغ ← دوره تازه (کش قطعۀ نتایج هم با ذخیرۀ نوع `tp_` باطل می‌شود). */
	function topersia_ads_bump() {
		update_option( 'tp_ads_epoch', (int) get_option( 'tp_ads_epoch', 1 ) + 1, false );
	}
}

if ( ! function_exists( 'topersia_ads_ts' ) ) {
	/** «YYYY-MM-DDTHH:MM» به وقت سایت ← مهر UTC؛ تهی یا نادرست ← ۰. */
	function topersia_ads_ts( $local ) {
		$local = trim( (string) $local );
		if ( '' === $local || ! preg_match( '/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/', $local ) ) {
			return 0;
		}
		try {
			$dt = new DateTimeImmutable( str_replace( 'T', ' ', $local ) . ( strlen( $local ) === 10 ? ' 00:00' : '' ), wp_timezone() );
			return (int) $dt->getTimestamp();
		} catch ( Exception $e ) {
			return 0;
		}
	}
}

if ( ! function_exists( 'topersia_ads_local' ) ) {
	/** مهر UTC ← «YYYY-MM-DDTHH:MM» به وقت سایت (برای input datetime-local). */
	function topersia_ads_local( $ts ) {
		return $ts ? wp_date( 'Y-m-d\TH:i', (int) $ts ) : '';
	}
}

if ( ! function_exists( 'topersia_ads_missing' ) ) {
	/**
	 * چه چیزی مانع فعال‌شدن است — برای هر جایگاه و زبان انتخاب‌شده تصویر نسبت لازم و متن جایگزین.
	 *
	 * @return string[] پیام‌های ترجمه‌شده
	 */
	function topersia_ads_missing( array $ad ) {
		$out    = array();
		$ratios = topersia_ads_ratios();
		$slots  = topersia_ads_slots();
		$langs  = in_array( 'all', $ad['langs'], true ) ? array( 'fa', 'en', 'ar' ) : $ad['langs'];

		if ( '' === $ad['url'] ) {
			$out[] = __( 'نشانی مقصد (http یا https)', 'topersia-ads' );
		}
		if ( ! $ad['slots'] ) {
			$out[] = __( 'دست‌کم یک جایگاه', 'topersia-ads' );
		}
		foreach ( $ad['slots'] as $sl ) {
			$need = array( $slots[ $sl ]['d'] );
			if ( '' !== $slots[ $sl ]['m'] && 'desktop' !== $ad['device'] ) {
				$need[] = $slots[ $sl ]['m'];
			}
			foreach ( array_unique( $need ) as $k ) {
				if ( empty( $ad['img'][ $k ] ) ) {
					/* translators: 1: نسبت تصویر، 2: نام جایگاه */
					$out[] = sprintf( __( 'تصویر %1$s برای «%2$s»', 'topersia-ads' ), $ratios[ $k ][1] . '×' . $ratios[ $k ][2], __( $slots[ $sl ]['name'], 'topersia-ads' ) );   // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
				}
			}
		}
		foreach ( $langs as $l ) {
			if ( '' === $ad['alt'][ $l ] ) {
				/* translators: %s: کد زبان */
				$out[] = sprintf( __( 'متن جایگزین تصویر (%s)', 'topersia-ads' ), $l );
			}
		}
		if ( $ad['start'] && $ad['end'] && $ad['end'] <= $ad['start'] ) {
			$out[] = __( 'پایان باید پس از شروع باشد', 'topersia-ads' );
		}
		return array_values( array_unique( $out ) );
	}
}

/* ── فرم ویرایش ─────────────────────────────────────────────────────────── */

add_action( 'add_meta_boxes_tp_ad', function () {
	add_meta_box( 'tp-ad-box', __( 'تنظیم کمپین', 'topersia-ads' ), 'topersia_ads_box', 'tp_ad', 'normal', 'high' );
} );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'tp_ad' === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		wp_enqueue_media();
		wp_enqueue_style( 'topersia-ads-admin', topersia_ads_url( 'assets/tp-ads-admin.css' ), array(), TOPERSIA_ADS_VERSION );
		wp_enqueue_script( 'topersia-ads-admin', topersia_ads_url( 'assets/tp-ads-admin.js' ), array( 'jquery' ), TOPERSIA_ADS_VERSION, true );
		wp_localize_script( 'topersia-ads-admin', 'TopersiaAdsAdmin', array( 'pick' => __( 'انتخاب تصویر', 'topersia-ads' ) ) );
	}
} );

if ( ! function_exists( 'topersia_ads_box' ) ) {
	function topersia_ads_box( $post ) {

		$ad     = topersia_ads_norm( topersia_ads_raw( $post->ID ) );
		$raw    = get_post_meta( $post->ID, '_tp_ad', true );
		$note   = is_array( $raw ) && isset( $raw['note'] ) ? (string) $raw['note'] : '';
		$slots  = topersia_ads_slots();
		$ratios = topersia_ads_ratios();
		$chk    = function ( $on ) {
			return $on ? ' checked' : '';
		};

		wp_nonce_field( 'tp_ad_save', 'tp_ad_nonce' );

		echo '<div class="tp-ads-form">';

		echo '<p><label for="tp-ad-advertiser">' . esc_html__( 'تبلیغ‌دهنده', 'topersia-ads' ) . '</label>'
			. '<input type="text" id="tp-ad-advertiser" name="tp_ad[advertiser]" value="' . esc_attr( $ad['advertiser'] ) . '" class="widefat"></p>';
		echo '<p><label for="tp-ad-url">' . esc_html__( 'مقصد (نشانی کامل)', 'topersia-ads' ) . '</label>'
			. '<input type="url" id="tp-ad-url" name="tp_ad[url]" value="' . esc_attr( $ad['url'] ) . '" class="widefat" dir="ltr" placeholder="https://"></p>';

		echo '<fieldset><legend>' . esc_html__( 'جایگاه‌ها', 'topersia-ads' ) . '</legend><div class="tp-ads-grid">';
		foreach ( $slots as $id => $s ) {
			$size = $ratios[ $s['d'] ][1] . '×' . $ratios[ $s['d'] ][2] . ( '' !== $s['m'] && $s['m'] !== $s['d'] ? ' · ' . $ratios[ $s['m'] ][1] . '×' . $ratios[ $s['m'] ][2] : '' );
			echo '<label><input type="checkbox" name="tp_ad[slots][]" value="' . esc_attr( $id ) . '"' . $chk( in_array( $id, $ad['slots'], true ) ) . '> '
				. esc_html( __( $s['name'], 'topersia-ads' ) ) . ' <bdi dir="ltr" class="tp-ads-dim">' . esc_html( $id . ' · ' . $size ) . '</bdi></label>';   // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}
		echo '</div></fieldset>';

		echo '<fieldset><legend>' . esc_html__( 'تصویرها (به‌ازای نسبت هر جایگاه)', 'topersia-ads' ) . '</legend><div class="tp-ads-grid">';
		foreach ( $ratios as $k => $r ) {
			$src = $ad['img'][ $k ] ? wp_get_attachment_image_url( $ad['img'][ $k ], 'thumbnail' ) : '';
			echo '<div class="tp-ads-img" data-tp-ads-img><span>' . esc_html( $r[1] . '×' . $r[2] ) . ' <bdi dir="ltr">(' . esc_html( str_replace( ' ', '', $r[0] ) ) . ')</bdi></span>'
				. '<input type="number" min="0" name="tp_ad[img][' . esc_attr( $k ) . ']" value="' . (int) $ad['img'][ $k ] . '" aria-label="' . esc_attr__( 'شناسۀ پیوست', 'topersia-ads' ) . '">'
				. '<button type="button" class="button" data-tp-ads-pick>' . esc_html__( 'انتخاب تصویر', 'topersia-ads' ) . '</button>'
				. ( $src ? '<img src="' . esc_url( $src ) . '" alt="" width="60" height="60">' : '' ) . '</div>';
		}
		echo '</div></fieldset>';

		echo '<fieldset><legend>' . esc_html__( 'متن جایگزین تصویر (الزامی برای هر زبان هدف)', 'topersia-ads' ) . '</legend>';
		foreach ( array( 'fa' => 'rtl', 'en' => 'ltr', 'ar' => 'rtl' ) as $l => $dir ) {
			echo '<p><label for="tp-ad-alt-' . esc_attr( $l ) . '">' . esc_html( strtoupper( $l ) ) . '</label>'
				. '<input type="text" id="tp-ad-alt-' . esc_attr( $l ) . '" name="tp_ad[alt][' . esc_attr( $l ) . ']" value="' . esc_attr( $ad['alt'][ $l ] ) . '" class="widefat" dir="' . esc_attr( $dir ) . '" lang="' . esc_attr( $l ) . '"></p>';
		}
		echo '</fieldset>';

		echo '<div class="tp-ads-grid">';
		echo '<fieldset><legend>' . esc_html__( 'زبان‌ها', 'topersia-ads' ) . '</legend>';
		foreach ( array( 'all' => __( 'همه', 'topersia-ads' ), 'fa' => 'فارسی', 'en' => 'English', 'ar' => 'العربية' ) as $l => $lab ) {
			echo '<label><input type="checkbox" name="tp_ad[langs][]" value="' . esc_attr( $l ) . '"' . $chk( in_array( $l, $ad['langs'], true ) ) . '> ' . esc_html( $lab ) . '</label> ';
		}
		echo '</fieldset><fieldset><legend>' . esc_html__( 'دستگاه', 'topersia-ads' ) . '</legend>';
		foreach ( array( 'all' => __( 'همه', 'topersia-ads' ), 'desktop' => __( 'دسکتاپ', 'topersia-ads' ), 'mobile' => __( 'موبایل', 'topersia-ads' ) ) as $d => $lab ) {
			echo '<label><input type="radio" name="tp_ad[device]" value="' . esc_attr( $d ) . '"' . $chk( $d === $ad['device'] ) . '> ' . esc_html( $lab ) . '</label> ';
		}
		echo '</fieldset><fieldset><legend>' . esc_html__( 'نوع صفحه', 'topersia-ads' ) . '</legend>';
		foreach ( array( 'all' => __( 'همه', 'topersia-ads' ), 'home' => __( 'صفحۀ اول', 'topersia-ads' ), 'hub' => __( 'هاب', 'topersia-ads' ), 'entity' => __( 'مقصد', 'topersia-ads' ), 'single' => __( 'خبر و مقاله', 'topersia-ads' ) ) as $p => $lab ) {
			echo '<label><input type="checkbox" name="tp_ad[pages][]" value="' . esc_attr( $p ) . '"' . $chk( in_array( $p, $ad['pages'], true ) ) . '> ' . esc_html( $lab ) . '</label> ';
		}
		echo '</fieldset></div>';

		echo '<div class="tp-ads-grid">'
			. '<p><label for="tp-ad-start">' . esc_html__( 'شروع', 'topersia-ads' ) . '</label><input type="datetime-local" id="tp-ad-start" name="tp_ad[start]" value="' . esc_attr( topersia_ads_local( $ad['start'] ) ) . '"></p>'
			. '<p><label for="tp-ad-end">' . esc_html__( 'پایان', 'topersia-ads' ) . '</label><input type="datetime-local" id="tp-ad-end" name="tp_ad[end]" value="' . esc_attr( topersia_ads_local( $ad['end'] ) ) . '"></p>'
			. '<p><label for="tp-ad-cap-imp">' . esc_html__( 'سقف نمایش (۰ = بی سقف)', 'topersia-ads' ) . '</label><input type="number" min="0" id="tp-ad-cap-imp" name="tp_ad[cap_imp]" value="' . (int) $ad['cap_imp'] . '"></p>'
			. '<p><label for="tp-ad-cap-clk">' . esc_html__( 'سقف کلیک (۰ = بی سقف)', 'topersia-ads' ) . '</label><input type="number" min="0" id="tp-ad-cap-clk" name="tp_ad[cap_clk]" value="' . (int) $ad['cap_clk'] . '"></p>'
			. '<p><label for="tp-ad-weight">' . esc_html__( 'وزن (۱ تا ۱۰۰)', 'topersia-ads' ) . '</label><input type="number" min="1" max="100" id="tp-ad-weight" name="tp_ad[weight]" value="' . (int) $ad['weight'] . '"></p>'
			. '<p><label for="tp-ad-priority">' . esc_html__( 'اولویت (۰ عادی تا ۹ اسپانسر انحصاری)', 'topersia-ads' ) . '</label><input type="number" min="0" max="9" id="tp-ad-priority" name="tp_ad[priority]" value="' . (int) $ad['priority'] . '"></p>'
			. '</div>';

		echo '<p><label for="tp-ad-status">' . esc_html__( 'وضعیت', 'topersia-ads' ) . '</label><select id="tp-ad-status" name="tp_ad[status]">';
		foreach ( array( 'active' => __( 'فعال', 'topersia-ads' ), 'paused' => __( 'متوقف', 'topersia-ads' ), 'ended' => __( 'پایان‌یافته', 'topersia-ads' ) ) as $s => $lab ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( $s, $ad['status'], false ) . '>' . esc_html( $lab ) . '</option>';
		}
		echo '</select></p>';

		echo '<p><label for="tp-ad-note">' . esc_html__( 'یادداشت داخلی', 'topersia-ads' ) . '</label><textarea id="tp-ad-note" name="tp_ad[note]" rows="3" class="widefat">' . esc_textarea( $note ) . '</textarea></p>';

		if ( $ad['imp'] || $ad['clk'] ) {
			/* translators: 1: شمار نمایش، 2: شمار کلیک */
			echo '<p class="tp-ads-dim">' . esc_html( sprintf( __( 'تاکنون: %1$s نمایش · %2$s کلیک', 'topersia-ads' ), number_format_i18n( $ad['imp'] ), number_format_i18n( $ad['clk'] ) ) ) . '</p>';
		}
		if ( $ad['demo'] ) {
			echo '<p class="tp-ads-dim">' . esc_html__( 'نمونۀ نمایشی — فقط در حالت پیش‌نمایش دیده می‌شود.', 'topersia-ads' ) . '</p>';
		}

		echo '</div>';
	}
}

if ( ! function_exists( 'topersia_ads_sanitize' ) ) {
	/** ورودی فرم ← متای `_tp_ad` (همان شکل topersia_ads_norm، به‌اضافۀ یادداشت). */
	function topersia_ads_sanitize( array $in ) {
		$out = array(
			'advertiser' => sanitize_text_field( isset( $in['advertiser'] ) ? wp_unslash( $in['advertiser'] ) : '' ),
			'url'        => esc_url_raw( isset( $in['url'] ) ? trim( wp_unslash( $in['url'] ) ) : '', array( 'http', 'https' ) ),
			'slots'      => array_map( 'sanitize_key', (array) ( isset( $in['slots'] ) ? $in['slots'] : array() ) ),
			'img'        => array(),
			'alt'        => array(),
			'langs'      => array_map( 'sanitize_key', (array) ( isset( $in['langs'] ) ? $in['langs'] : array() ) ),
			'device'     => sanitize_key( isset( $in['device'] ) ? $in['device'] : 'all' ),
			'pages'      => array_map( 'sanitize_key', (array) ( isset( $in['pages'] ) ? $in['pages'] : array() ) ),
			'start'      => topersia_ads_ts( isset( $in['start'] ) ? wp_unslash( $in['start'] ) : '' ),
			'end'        => topersia_ads_ts( isset( $in['end'] ) ? wp_unslash( $in['end'] ) : '' ),
			'cap_imp'    => absint( isset( $in['cap_imp'] ) ? $in['cap_imp'] : 0 ),
			'cap_clk'    => absint( isset( $in['cap_clk'] ) ? $in['cap_clk'] : 0 ),
			'weight'     => absint( isset( $in['weight'] ) ? $in['weight'] : 10 ),
			'priority'   => absint( isset( $in['priority'] ) ? $in['priority'] : 0 ),
			'status'     => sanitize_key( isset( $in['status'] ) ? $in['status'] : 'paused' ),
			'note'       => sanitize_textarea_field( isset( $in['note'] ) ? wp_unslash( $in['note'] ) : '' ),
		);
		foreach ( array_keys( topersia_ads_ratios() ) as $k ) {
			$id               = isset( $in['img'][ $k ] ) ? absint( $in['img'][ $k ] ) : 0;
			$out['img'][ $k ] = ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
		}
		foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
			$out['alt'][ $l ] = sanitize_text_field( isset( $in['alt'][ $l ] ) ? wp_unslash( $in['alt'][ $l ] ) : '' );
		}
		$n = topersia_ads_norm( $out );
		foreach ( array( 'slots', 'langs', 'pages', 'device', 'status', 'weight', 'priority' ) as $k ) {
			$out[ $k ] = $n[ $k ];   /* فقط مقدار مجاز می‌ماند */
		}
		$out['url'] = $n['url'];
		return $out;
	}
}

add_action( 'save_post_tp_ad', function ( $post_id, $post ) {

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['tp_ad_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tp_ad_nonce'] ) ), 'tp_ad_save' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) || ! current_user_can( TOPERSIA_ADS_CAP ) ) {
		return;
	}

	$in  = isset( $_POST['tp_ad'] ) && is_array( $_POST['tp_ad'] ) ? $_POST['tp_ad'] : array();   // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- پاک‌سازی در topersia_ads_sanitize
	$val = topersia_ads_sanitize( $in );
	$why = topersia_ads_missing( topersia_ads_norm( $val ) );

	/* فعال بی آمادگی ← متوقف، با پیام؛ هیچ تبلیغ ناقصی بیرون نمی‌رود */
	if ( 'active' === $val['status'] && $why ) {
		$val['status'] = 'paused';
		set_transient( 'tp_ads_notice_' . get_current_user_id(), $why, 120 );
	}

	update_post_meta( $post_id, '_tp_ad', $val );
	topersia_ads_bump();
}, 10, 2 );

add_action( 'admin_notices', function () {
	$why = get_transient( 'tp_ads_notice_' . get_current_user_id() );
	if ( ! is_array( $why ) || ! $why ) {
		return;
	}
	delete_transient( 'tp_ads_notice_' . get_current_user_id() );
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'کمپین متوقف ماند؛ برای فعال‌شدن لازم است:', 'topersia-ads' ) . '</p><ul>';
	foreach ( $why as $w ) {
		echo '<li>' . esc_html( $w ) . '</li>';
	}
	echo '</ul></div>';
} );

/* حذف یا زباله ← دورۀ تازه */
add_action( 'trashed_post', function ( $id ) {
	if ( 'tp_ad' === get_post_type( $id ) ) {
		topersia_ads_bump();
	}
} );
add_action( 'deleted_post', function ( $id, $post = null ) {
	if ( $post && 'tp_ad' === $post->post_type ) {
		topersia_ads_bump();
	}
}, 10, 2 );
