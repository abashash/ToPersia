<?php
/**
 * صفحۀ اول — شورت‌کد `[tp_home]`.  دستۀ 45 · بازطراحی دستۀ 46 (P5-D513 تا P5-D515)
 *
 * چیدمان «مجلۀ گردشگری» بر پایۀ نمونۀ ۱ `docs/design/home/` (فقط الهام چیدمان؛ هیچ تصویر و عددی از آن نیامده).
 * همه چیز از پایگاه‌داده: پست‌های واقعی، منتشرشده و هم‌زبان، از دروازۀ مرکزی؛ نمونه (`_tp_sample`) هرگز.
 * هر بخش بی‌داده اصلاً چاپ نمی‌شود. هیچ عدد بی‌منبع؛ نوار شمار فقط شمارش همین پایگاه است.
 * جست‌وجوی سراسری (P5-D516) ساخته نشده، پس کادر جست‌وجو و SearchAction هم نیست.
 * شیوه‌نامه: `assets/topersia-g-home.css` فقط روی صفحۀ اول (topersia_g_home_enqueue).
 */

if ( ! function_exists( 'topersia_g_home_real' ) ) {
	/**
	 * پست‌های واقعی (نه نمونه)، منتشرشده و به زبان جاری.
	 *
	 * @param string[] $types نوع‌ها.
	 * @param int      $limit -1 همه.
	 * @return int[]
	 */
	function topersia_g_home_real( array $types, $limit = -1 ) {

		$ids = get_posts( array(
			'post_type'        => $types,
			'post_status'      => 'publish',
			'has_password'     => false,
			'posts_per_page'   => $limit,
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_query'       => array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ),
		) );

		return topersia_g_published( array_map( 'absint', (array) $ids ) );
	}
}

if ( ! function_exists( 'topersia_g_home_first' ) ) {
	/** اولین پست واقعی از نخستین نوعی که پست دارد (ترتیب آرایه، سپس شناسه). */
	function topersia_g_home_first( array $types ) {

		foreach ( $types as $type ) {

			$ids = topersia_g_home_real( array( $type ), 5 );

			if ( $ids ) {
				return (int) reset( $ids );
			}
		}

		return 0;
	}
}

if ( ! function_exists( 'topersia_g_home_by_title' ) ) {
	/** مرتب‌سازی شناسه‌ها بر عنوان، با Collator زبان صفحه اگر باشد. */
	function topersia_g_home_by_title( array $ids ) {

		$lang = function_exists( 'topersia_g_page_lang' ) ? (string) topersia_g_page_lang() : 'fa';
		$col  = class_exists( 'Collator' ) ? new Collator( $lang ) : null;
		$name = array();

		foreach ( $ids as $id ) {
			$name[ $id ] = (string) get_the_title( $id );
		}

		uasort( $name, function ( $a, $b ) use ( $col ) {
			return $col ? (int) $col->compare( $a, $b ) : strcmp( $a, $b );
		} );

		return array_keys( $name );
	}
}

if ( ! function_exists( 'topersia_g_home_with_image_first' ) ) {
	/** دارای تصویر نخست، سپس شناسۀ کوچک‌تر (display_priority هنوز نیست). */
	function topersia_g_home_with_image_first( array $ids ) {

		$with = array();
		$bare = array();

		foreach ( $ids as $id ) {
			if ( topersia_g_card_image( $id ) ) {
				$with[] = $id;
			} else {
				$bare[] = $id;
			}
		}

		sort( $with );
		sort( $bare );

		return array_merge( $with, $bare );
	}
}

if ( ! function_exists( 'topersia_g_home_section' ) ) {
	/**
	 * یک بخش؛ بدنۀ خالی ← هیچ.  $more: [ نشانی، متن ] پیوند «همه …» سرفصل.
	 * $lazy: بخش زیر تاخیر — content-visibility در شیوه‌نامه.
	 */
	function topersia_g_home_section( $id, $title, $body, $more = array(), $class = '' ) {

		if ( '' === trim( (string) $body ) ) {
			return '';
		}

		$link = ( ! empty( $more[0] ) && ! empty( $more[1] ) ) ? '<a class="tp-more" href="' . esc_url( $more[0] ) . '">' . esc_html( $more[1] ) . '</a>' : '';

		return '<section id="' . esc_attr( $id ) . '" class="tp-section tp-home-sec' . ( '' !== $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-labelledby="' . esc_attr( $id ) . 'Title">'
			. '<div class="tp-section-head"><div><h2 id="' . esc_attr( $id ) . 'Title">' . esc_html( $title ) . '</h2></div>' . $link . '</div>'
			. $body . '</section>';
	}
}

if ( ! function_exists( 'topersia_g_home_alt' ) ) {
	/** alt واقعی پیوست؛ خالی یا به زبان دیگر ← نام موجودیت به زبان صفحه (P5-D278). */
	function topersia_g_home_alt( $att, $pid ) {

		$alt = trim( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ) );

		if ( '' === $alt || ( function_exists( 'topersia_g_foreign_text' ) && topersia_g_foreign_text( $alt ) ) ) {
			$alt = (string) get_the_title( $pid );
		}

		return $alt;
	}
}

if ( ! function_exists( 'topersia_g_home_img' ) ) {
	/**
	 * <img> تنبل با width/height و srcset اندازه‌های موجود (webp از خودِ کتابخانه).  P5-D515
	 * شمار تصویرهای صفحه در $GLOBALS['tp_home_imgs'] — سقف نزدیک چهل.
	 */
	function topersia_g_home_img( $att, $pid, $size, $sizes, $class = '' ) {

		$att = absint( $att );

		if ( ! $att || ! function_exists( 'wp_get_attachment_image' ) ) {
			return '';
		}

		$GLOBALS['tp_home_imgs'] = isset( $GLOBALS['tp_home_imgs'] ) ? $GLOBALS['tp_home_imgs'] + 1 : 1;

		return (string) wp_get_attachment_image( $att, $size, false, array(
			'class'    => $class,
			'loading'  => 'lazy',
			'decoding' => 'async',
			'sizes'    => $sizes,
			'alt'      => topersia_g_home_alt( $att, $pid ),
		) );
	}
}

if ( ! function_exists( 'topersia_g_home_card' ) ) {
	/** کارت تصویری: تصویر (یا نقش‌مایۀ CSS)، نام، برچسب. پیوند فقط با نشانی. */
	function topersia_g_home_card( $pid, $label = '', $url = '', $name = '' ) {

		$att  = topersia_g_card_image( $pid );
		$name = '' !== $name ? $name : (string) get_the_title( $pid );
		$url  = '' !== $url ? $url : (string) get_permalink( $pid );
		$img  = $att ? topersia_g_home_img( $att, $pid, 'medium_large', '(max-width: 600px) 46vw, 300px', 'tp-home-card__img' ) : '';

		return '<a class="tp-home-card" href="' . esc_url( $url ) . '">'
			. '<span class="tp-home-card__media">' . ( '' !== $img ? $img : '<span class="tp-home-card__ph" aria-hidden="true"></span>' ) . '</span>'
			. '<span class="tp-home-card__txt"><b class="tp-home-card__name">' . esc_html( $name ) . '</b>'
			. ( '' !== $label ? '<span class="tp-home-card__k">' . esc_html( $label ) . '</span>' : '' ) . '</span></a>';
	}
}

if ( ! function_exists( 'topersia_g_home_groups' ) ) {
	/** گروه‌های جاذبه — فقط نوع‌های دارای صفحه؛ گروه بی‌عضو پنهان. */
	function topersia_g_home_groups() {
		return array(
			'heritage' => array( __( 'بناها و میراث تاریخی', 'topersia' ), array( 'tp_monument', 'tp_ancient_site' ) ),
			'religion' => array( __( 'مکان‌های مذهبی', 'topersia' ), array( 'tp_religious_site' ) ),
			'museum'   => array( __( 'موزه‌ها', 'topersia' ), array( 'tp_museum' ) ),
			'nature'   => array( __( 'طبیعت', 'topersia' ), array( 'tp_natural_feature', 'tp_landscape', 'tp_protected_area', 'tp_mountain', 'tp_desert', 'tp_water_body', 'tp_forest_plain', 'tp_island' ) ),
		);
	}
}

if ( ! function_exists( 'topersia_g_home_counts' ) ) {
	/**
	 * نوار شمار (P5-D514): فقط شمارش پست‌های واقعی منتشرشدۀ همین زبان؛ صفر پنهان؛
	 * کمتر از سه عدد ≥ ۱ ← هیچ (آرایۀ خالی).
	 *
	 * @return array[] هر عضو: [ عدد، برچسب، آیکون ]
	 */
	function topersia_g_home_counts() {

		$groups = topersia_g_home_groups();
		$attr   = array();

		foreach ( $groups as $g ) {
			$attr = array_merge( $attr, $g[1] );
		}

		$rows = array(
			array( count( topersia_g_home_real( array( 'tp_province' ) ) ), __( 'استان', 'topersia' ), 'column' ),
			array( count( topersia_g_home_real( array( 'tp_city' ) ) ), __( 'شهر', 'topersia' ), 'city' ),
			array( count( topersia_g_home_real( array( 'tp_village' ) ) ), __( 'روستا', 'topersia' ), 'home' ),
			array( count( topersia_g_home_real( $attr ) ), __( 'جاذبه', 'topersia' ), 'gate' ),
			array( count( topersia_g_home_real( array( 'tp_festival', 'tp_sports_event', 'tp_religious_event', 'tp_seasonal_event' ) ) ), __( 'رویداد', 'topersia' ), 'cal' ),
			array( count( topersia_g_home_real( array( 'tp_route' ) ) ), __( 'مسیر گردشگری', 'topersia' ), 'route' ),
		);

		$rows = array_values( array_filter( $rows, function ( $r ) { return $r[0] >= 1; } ) );

		return count( $rows ) >= 3 ? $rows : array();
	}
}

if ( ! function_exists( 'topersia_g_home_hero_att' ) ) {
	/** تصویر هیرو: تنظیم `home_hero_id` نمایش، وگرنه hero_image نخستین استان واقعی که دارد؛ هیچ ← ۰. */
	function topersia_g_home_hero_att() {

		$set = function_exists( 'topersia_render_option' ) ? absint( topersia_render_option( 'home_hero_id', 0 ) ) : 0;

		if ( $set && wp_attachment_is_image( $set ) ) {
			return array( $set, 0 );
		}

		foreach ( topersia_g_home_real( array( 'tp_province' ) ) as $pid ) {

			$hero = topersia_g_raw( $pid, 'hero_image' );
			$hero = is_array( $hero ) ? ( isset( $hero['id'] ) ? $hero['id'] : reset( $hero ) ) : $hero;
			$att  = is_scalar( $hero ) ? absint( $hero ) : 0;

			if ( $att && wp_attachment_is_image( $att ) ) {
				return array( $att, $pid );
			}
		}

		return array( 0, 0 );
	}
}

if ( ! function_exists( 'topersia_g_home_map' ) ) {
	/**
	 * «ایران روی نقشه» — SVG ایستای `assets/home/iran-provinces.svg` (scripts/tp-home-map-svg.php).
	 * هر استان با صفحۀ منتشرشدۀ همین زبان پیوند می‌شود (aria-label نام)، بقیه بی‌پیوند و کم‌رنگ.
	 *
	 * @param array $by_slug نامک فارسی ← شناسۀ پست زبان جاری.
	 */
	function topersia_g_home_map( array $by_slug ) {

		$file = dirname( __DIR__ ) . '/assets/home/iran-provinces.svg';
		$svg  = is_readable( $file ) ? (string) file_get_contents( $file ) : '';

		if ( ! preg_match( '#viewBox="([0-9 ]+)"#', $svg, $vb ) || ! preg_match_all( '#<path id="tp-prov-([a-z0-9-]+)" data-tp-prov="[a-z0-9-]+" d="([^"]+)"/>#', $svg, $m, PREG_SET_ORDER ) ) {
			return '';
		}

		$out   = '';
		$links = 0;

		foreach ( $m as $p ) {

			$slug = $p[1];
			$d    = $p[2];

			if ( isset( $by_slug[ $slug ] ) ) {
				$pid   = $by_slug[ $slug ];
				$name  = (string) get_the_title( $pid );
				$out  .= '<a class="tp-home-map__a" href="' . esc_url( get_permalink( $pid ) ) . '" aria-label="' . esc_attr( $name ) . '"><title>' . esc_html( $name ) . '</title><path d="' . esc_attr( $d ) . '"/></a>';
				$links ++;
			} else {
				$out .= '<path class="tp-home-map__off" d="' . esc_attr( $d ) . '"/>';
			}
		}

		if ( ! $links ) {
			return '';
		}

		return '<figure class="tp-home-map"><svg class="tp-home-map__svg" viewBox="' . esc_attr( $vb[1] ) . '" role="group" aria-label="' . esc_attr__( 'نقشۀ استان‌های ایران', 'topersia' ) . '" focusable="false">' . $out . '</svg>'
			. '<figcaption class="tp-home-map__src">' . esc_html__( 'مرزها: OpenStreetMap contributors (ODbL)', 'topersia' ) . '</figcaption></figure>';
	}
}

if ( ! function_exists( 'topersia_g_home_faq_items' ) ) {
	/**
	 * پرسش‌های صفحۀ اول — همان ردیف‌های faq_items پست‌های واقعی ایران، روادید، ارز، سلامت و ایمنی
	 * (به همین ترتیب)، سقف شش. همان آرایه به اسکیما می‌رود: FAQPage فقط برای آیتم‌های دیده‌شده.
	 *
	 * @return array[] هر عضو [ 'q' => …, 'a' => … ]
	 */
	function topersia_g_home_faq_items() {

		static $cache = array();
		$lang = function_exists( 'topersia_g_page_lang' ) ? (string) topersia_g_page_lang() : '';

		if ( isset( $cache[ $lang ] ) ) {
			return $cache[ $lang ];
		}

		$items = array();

		foreach ( array( 'tp_country', 'tp_visa', 'tp_currency', 'tp_health_safety' ) as $type ) {
			foreach ( topersia_g_home_real( array( $type ), 3 ) as $pid ) {
				foreach ( topersia_g_rows( $pid, 'faq_items' ) as $row ) {

					$q = isset( $row['faq_question'] ) ? trim( wp_strip_all_tags( (string) $row['faq_question'] ) ) : '';
					$a = isset( $row['faq_answer'] ) ? trim( wp_strip_all_tags( (string) $row['faq_answer'] ) ) : '';

					if ( '' !== $q && '' !== $a && count( $items ) < 6 ) {
						$items[] = array( 'q' => $q, 'a' => $a );
					}
				}
			}
		}

		return $cache[ $lang ] = $items;
	}
}

if ( ! function_exists( 'topersia_g_sc_home' ) ) {
	function topersia_g_sc_home( $atts = array() ) {

		$pid = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;

		/* فقط درون صفحۀ وردپرس (post_type=page)؛ روی موجودیت‌ها هیچ. */
		if ( 'page' !== get_post_type( $pid ) ) {
			return '';
		}

		$GLOBALS['tp_home_imgs'] = 0;

		/* ── داده ── */
		$provinces = topersia_g_home_by_title( topersia_g_home_real( array( 'tp_province' ) ) );
		$cities    = topersia_g_home_with_image_first( topersia_g_home_real( array( 'tp_city' ) ) );
		$country   = topersia_g_home_first( array( 'tp_country' ) );
		$groups    = array();

		foreach ( topersia_g_home_groups() as $key => $g ) {

			$ids = topersia_g_home_with_image_first( topersia_g_home_real( $g[1] ) );

			if ( $ids ) {
				$groups[ $key ] = array( $g[0], $ids );
			}
		}

		$guide_types = array(
			'tp_currency'         => array( __( 'ارز و پرداخت', 'topersia' ), 'coins' ),
			'tp_visa'             => array( __( 'روادید', 'topersia' ), 'passport' ),
			'tp_health_safety'    => array( __( 'سلامت و ایمنی', 'topersia' ), 'shield' ),
			'tp_transport'        => array( __( 'حمل‌ونقل', 'topersia' ), 'car' ),
			'tp_calendar'         => array( __( 'تقویم', 'topersia' ), 'cal' ),
			'tp_travel_etiquette' => array( __( 'آداب سفر', 'topersia' ), 'heart' ),
		);
		$guide = array();

		foreach ( $guide_types as $type => $g ) {

			$one = topersia_g_home_first( array( $type ) );

			if ( ! $one ) {
				continue;
			}

			$guide[] = array( $g[0], $g[1], get_permalink( $one ) );

			/* شماره‌های اضطراری در همان پست سلامت و ایمنی؛ فقط اگر دست‌کم یکی پر است. */
			if ( 'tp_health_safety' === $type && ( '' !== trim( (string) topersia_g_raw( $one, 'emergency_police' ) ) || '' !== trim( (string) topersia_g_raw( $one, 'emergency_ambulance' ) ) ) ) {
				$guide[] = array( __( 'شماره‌های اضطراری', 'topersia' ), 'phone', get_permalink( $one ) . '#tp-practical' );
			}
		}

		/* ── ۱ هیرو ── */
		$intro = trim( (string) get_post_field( 'post_excerpt', $pid ) );
		list( $hero_att, $hero_pid ) = topersia_g_home_hero_att();
		$hero_img = '';

		if ( $hero_att ) {
			$hero_img = (string) wp_get_attachment_image( $hero_att, 'full', false, array(
				'class'         => 'tp-home-hero__img',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'decoding'      => 'async',
				'sizes'         => '100vw',
				'alt'           => $hero_pid ? topersia_g_home_alt( $hero_att, $hero_pid ) : trim( (string) get_post_meta( $hero_att, '_wp_attachment_image_alt', true ) ),
			) );
		}

		$quick = array();

		if ( $provinces ) {
			$quick[] = array( '#tp-home-provinces', __( 'استان‌ها', 'topersia' ), 'column' );
		}
		if ( $cities ) {
			$quick[] = array( '#tp-home-cities', __( 'شهرها', 'topersia' ), 'city' );
		}
		if ( $groups ) {
			$quick[] = array( '#tp-home-attractions', __( 'جاذبه‌ها', 'topersia' ), 'gate' );
		}
		if ( $guide ) {
			$quick[] = array( '#tp-home-guide', __( 'راهنمای سفر', 'topersia' ), 'compass' );
		}

		$quick_html = '';

		foreach ( $quick as $q ) {
			$quick_html .= '<a class="tp-home-quick__a" href="' . esc_attr( $q[0] ) . '">' . topersia_g_icon( $q[2] ) . '<span>' . esc_html( $q[1] ) . '</span></a>';
		}

		$hero = '<section class="tp-home-hero' . ( '' !== $hero_img ? '' : ' tp-home-hero--art' ) . '" aria-labelledby="tp-home-h1">'
			. ( '' !== $hero_img ? $hero_img : '<span class="tp-home-hero__art" aria-hidden="true"></span>' )
			. '<span class="tp-home-hero__scrim" aria-hidden="true"></span>'
			. '<div class="tp-wrap tp-home-hero__in"><div class="tp-home-hero__card tp-home-glass">'
			. '<h1 id="tp-home-h1">' . esc_html__( 'ایران را کشف کنید', 'topersia' ) . '</h1>'
			. ( '' !== $intro ? '<p class="tp-home-hero__lead">' . esc_html( $intro ) . '</p>' : '' )
			. ( '' !== $quick_html ? '<nav class="tp-home-quick" aria-label="' . esc_attr__( 'دسترسی سریع', 'topersia' ) . '">' . $quick_html . '</nav>' : '' )
			. '</div></div></section>';

		/* ── ۲ نوار شمار ── */
		$counts = topersia_g_home_counts();
		$stats  = '';

		if ( $counts ) {
			$cells = '';
			foreach ( $counts as $c ) {
				$cells .= '<li class="tp-home-stats__i">' . topersia_g_icon( $c[2] ) . '<b>' . esc_html( topersia_g_fa( number_format_i18n( $c[0] ) ) ) . '</b><span>' . esc_html( $c[1] ) . '</span></li>';
			}
			$stats = '<section id="tp-home-stats" class="tp-home-stats tp-home-glass" aria-label="' . esc_attr__( 'ToPersia در یک نگاه', 'topersia' ) . '"><ul>' . $cells . '</ul></section>';
		}

		/* ── ۳ استان‌ها + نقشه ── */
		$by_slug = array();

		foreach ( $provinces as $id ) {
			$fa_id = (int) apply_filters( 'wpml_object_id', $id, 'tp_province', true, 'fa' );
			$by_slug[ (string) get_post_field( 'post_name', $fa_id ? $fa_id : $id ) ] = $id;
		}

		$prov_cards = '';
		$shown      = array_slice( topersia_g_home_with_image_first( $provinces ), 0, 8 );

		foreach ( topersia_g_home_by_title( $shown ) as $id ) {
			$prov_cards .= topersia_g_home_card( $id );
		}

		$map       = $provinces ? topersia_g_home_map( $by_slug ) : '';
		$prov_body = '' !== $prov_cards ? '<div class="tp-home-prov' . ( '' !== $map ? '' : ' tp-home-prov--solo' ) . '">'
			. ( '' !== $map ? '<div class="tp-home-prov__map"><h3 class="tp-home-sub">' . esc_html__( 'ایران روی نقشه', 'topersia' ) . '</h3>' . $map . '</div>' : '' )
			. '<div class="tp-home-grid tp-home-grid--4">' . $prov_cards . '</div></div>' : '';
		$prov_more = ( $country && count( $provinces ) > 8 ) ? array( get_permalink( $country ) . '#tp-provinces', __( 'همۀ استان‌ها', 'topersia' ) ) : array();

		/* ── ۴ محبوب‌ترین مقاصد ── */
		$city_cards = '';

		foreach ( array_slice( $cities, 0, 8 ) as $id ) {
			$city_cards .= topersia_g_home_card( $id );
		}

		/* ── ۵ جاذبه‌ها ── */
		$attr_cards = '';

		foreach ( $groups as $g ) {
			$attr_cards .= topersia_g_home_card( $g[1][0], get_the_title( $g[1][0] ), get_permalink( $g[1][0] ), $g[0] );
		}

		/* ── ۶ رویدادها — فقط با تاریخ آغاز معتبر، نزدیک‌ترین نخست ── */
		$events = array();

		foreach ( topersia_g_home_real( array( 'tp_festival', 'tp_sports_event', 'tp_religious_event', 'tp_seasonal_event' ) ) as $id ) {
			$iso = topersia_g_date_iso( topersia_g_raw( $id, 'event_start_date' ) );
			if ( '' !== $iso ) {
				$events[ $id ] = $iso;
			}
		}

		asort( $events );
		$event_rows = '';

		foreach ( array_slice( array_keys( $events ), 0, 4 ) as $id ) {
			$event_rows .= '<li><a class="tp-home-event" href="' . esc_url( get_permalink( $id ) ) . '"><b>' . esc_html( get_the_title( $id ) ) . '</b>'
				. '<span class="tp-home-event__when">' . topersia_g_date_html( $events[ $id ] ) . '</span></a></li>';
		}

		/* ── ۷ راهنمای سفر ── */
		$guide_html = '';

		foreach ( $guide as $g ) {
			$guide_html .= '<a class="tp-home-guide__a" href="' . esc_url( $g[2] ) . '">' . topersia_g_icon( $g[1] ) . '<span>' . esc_html( $g[0] ) . '</span></a>';
		}

		/* ── ۹ ایران در تصویر — hero_image موجودیت‌ها، هر عکس پیوند به همان؛ بی تکرار تصویر هیرو ── */
		$photos = '';
		$seen   = array( $hero_att => true );
		$pool   = array_merge( $provinces, $cities );

		foreach ( $groups as $g ) {
			$pool = array_merge( $pool, $g[1] );
		}

		$n = 0;

		foreach ( $pool as $id ) {

			if ( $n >= 8 ) {
				break;
			}

			$hv   = topersia_g_raw( $id, 'hero_image' );
			$hv   = is_array( $hv ) ? ( isset( $hv['id'] ) ? $hv['id'] : reset( $hv ) ) : $hv;
			$att  = is_scalar( $hv ) ? absint( $hv ) : 0;

			if ( ! $att || isset( $seen[ $att ] ) || ! wp_attachment_is_image( $att ) ) {
				continue;
			}

			$seen[ $att ] = true;
			$n ++;
			$photos .= '<a class="tp-home-photo" href="' . esc_url( get_permalink( $id ) ) . '">'
				. topersia_g_home_img( $att, $id, 'medium_large', '(max-width: 600px) 50vw, 300px', 'tp-home-photo__img' )
				. '<span class="tp-home-photo__cap">' . esc_html( get_the_title( $id ) ) . '</span></a>';
		}

		/* ── ۱۰ پرسش‌های متداول ── */
		$faq = '';

		foreach ( topersia_g_home_faq_items() as $i => $f ) {
			$faq .= '<details class="tp-home-faq__i"' . ( 0 === $i ? ' open' : '' ) . '><summary>' . esc_html( $f['q'] ) . '</summary><p>' . esc_html( $f['a'] ) . '</p></details>';
		}

		/* ── ۱۱ تازه‌ترین اخبار ── */
		$news = '';

		if ( function_exists( 'topersia_g_news_on' ) && topersia_g_news_on() ) {

			$ids = topersia_g_published( array_map( 'absint', (array) get_posts( array(
				'post_type'        => 'tpc_news',
				'post_status'      => 'publish',
				'has_password'     => false,
				'posts_per_page'   => 4,
				'fields'           => 'ids',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_query'       => array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ),
			) ) ) );

			foreach ( $ids as $one ) {
				$news .= topersia_g_news_card( $one );
			}

			$news = '' !== $news ? '<div class="tp-news">' . $news . '</div>' : '';
		}

		/* ── ۱۲ دعوت شیشه‌ای به پنل ── */
		$panel = get_page_by_path( 'account' );
		$panel = $panel ? (int) apply_filters( 'wpml_object_id', $panel->ID, 'page', false ) : 0;
		$cta   = '';

		if ( $panel && 'publish' === get_post_status( $panel ) ) {
			$cta = '<section id="tp-home-account" class="tp-home-cta tp-home-glass" aria-labelledby="tp-home-accountTitle">'
				. '<h2 id="tp-home-accountTitle">' . esc_html__( 'حساب کاربری', 'topersia' ) . '</h2>'
				. '<p>' . esc_html__( 'با حساب کاربری می‌توانید مکان‌ها را ذخیره و دنبال کنید و نظر بدهید.', 'topersia' ) . '</p>'
				. '<a class="tp-home-cta__btn" href="' . esc_url( get_permalink( $panel ) ) . '">' . esc_html__( 'ورود / ثبت‌نام', 'topersia' ) . '</a></section>';
		}

		$body = $stats
			. topersia_g_home_section( 'tp-home-provinces', __( 'استان‌های ایران', 'topersia' ), $prov_body, $prov_more )
			. topersia_g_home_section( 'tp-home-cities', __( 'محبوب‌ترین مقاصد', 'topersia' ), '' !== $city_cards ? '<div class="tp-home-rail">' . $city_cards . '</div>' : '', array(), 'tp-home-lazy' )
			. topersia_g_home_section( 'tp-home-attractions', __( 'جاذبه‌ها', 'topersia' ), '' !== $attr_cards ? '<div class="tp-home-grid tp-home-grid--4">' . $attr_cards . '</div>' : '', array(), 'tp-home-lazy' )
			. topersia_g_home_section( 'tp-home-events', __( 'رویدادها و جشنواره‌ها', 'topersia' ), '' !== $event_rows ? '<ul class="tp-home-events">' . $event_rows . '</ul>' : '', array(), 'tp-home-lazy' )
			. topersia_g_home_section( 'tp-home-guide', __( 'راهنمای سفر به ایران', 'topersia' ), '' !== $guide_html ? '<nav class="tp-home-guide" aria-label="' . esc_attr__( 'راهنمای سفر به ایران', 'topersia' ) . '">' . $guide_html . '</nav>' : '', array(), 'tp-home-lazy' )
			. topersia_g_home_section( 'tp-home-gallery', __( 'ایران در تصویر', 'topersia' ), '' !== $photos ? '<div class="tp-home-photos">' . $photos . '</div>' : '', array(), 'tp-home-lazy' )
			. topersia_g_home_section( 'tp-home-faq', __( 'پرسش‌های متداول', 'topersia' ), '' !== $faq ? '<div class="tp-home-faq">' . $faq . '</div>' : '', array(), 'tp-home-lazy' )
			. topersia_g_home_section( 'tp-home-news', __( 'تازه‌ترین اخبار', 'topersia' ), $news, array(), 'tp-home-lazy' )
			. $cta;

		return '<div class="tp-page tp-home"><main id="tp-main" class="tp-main">' . $hero . '<div class="tp-wrap tp-home-body">' . $body . '</div></main></div>';
	}
}

if ( ! function_exists( 'topersia_g_home_is' ) ) {
	/** صفحۀ اول؟ (صفحۀ ایستای جلو یا ترجمۀ WPML آن). */
	function topersia_g_home_is() {
		return function_exists( 'is_front_page' ) && is_front_page() && is_page();
	}
}

/* شیوه‌نامۀ صفحۀ اول فقط روی صفحۀ اول؛ پیش‌بارگذاری تنها تصویر LCP. */
if ( function_exists( 'add_action' ) ) {

	add_action( 'wp_enqueue_scripts', function () {

		if ( ! topersia_g_home_is() || ! function_exists( 'topersia_render_url' ) ) {
			return;
		}

		wp_enqueue_style( 'topersia-g-home', topersia_render_url() . 'assets/topersia-g-home.css', array( 'topersia-g-template' ), topersia_render_asset_version( 'assets/topersia-g-home.css' ) );
	}, 22 );

	add_action( 'wp_head', function () {

		if ( ! topersia_g_home_is() ) {
			return;
		}

		list( $att ) = topersia_g_home_hero_att();
		$src = $att ? wp_get_attachment_image_src( $att, 'full' ) : false;

		if ( ! $src ) {
			return;
		}

		$set = (string) wp_get_attachment_image_srcset( $att, 'full' );

		echo '<link rel="preload" as="image" href="' . esc_url( $src[0] ) . '"' . ( '' !== $set ? ' imagesrcset="' . esc_attr( $set ) . '" imagesizes="100vw"' : '' ) . ' fetchpriority="high">' . "\n";
	}, 2 );
}
