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
	function topersia_g_home_real( array $types, $limit = -1, $demo = true ) {

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
			/* P5-D548: نمایشی فقط با حالت پیش‌نمایش؛ شمار آمار ($demo = false) همیشه واقعی */
			'meta_query'       => $demo ? topersia_g_sample_mq() : array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ),
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

		/* P5-D533: دکمۀ بخش (پیوند «مشاهدۀ همه» یا دکمۀ پنجره، HTML ساختۀ همین پرونده) همیشه در ردیف عنوان، روبروی آن. */
		if ( is_string( $more ) ) {
			$link = $more;
		} else {
			$link = ( ! empty( $more[0] ) && ! empty( $more[1] ) ) ? '<a class="tp-more" href="' . esc_url( $more[0] ) . '">' . esc_html( $more[1] ) . '</a>' : '';
		}

		return '<section id="' . esc_attr( $id ) . '" class="tp-section tp-home-sec' . ( '' !== $class ? ' ' . esc_attr( $class ) : '' ) . '" aria-labelledby="' . esc_attr( $id ) . 'Title">'
			. '<div class="tp-section-head"><div><h2 id="' . esc_attr( $id ) . 'Title">' . esc_html( $title ) . '</h2></div>' . $link . '</div>'
			. $body . '</section>';
	}
}

if ( ! function_exists( 'topersia_g_home_alt' ) ) {
	/** alt واقعی پیوست؛ خالی یا به زبان دیگر ← نام موجودیت به زبان صفحه (P5-D278 · P5-D561). */
	function topersia_g_home_alt( $att, $pid ) {

		$alt = trim( (string) get_post_meta( $att, '_wp_attachment_image_alt', true ) );

		/* P5-D561: در صفحۀ fa/ar، alt تماماً لاتین (متن انگلیسی کتابخانه) هم به زبان دیگر است */
		$latin = 'en' !== topersia_g_page_lang() && ! preg_match( '/\p{Arabic}/u', $alt );

		if ( '' === $alt || $latin || ( function_exists( 'topersia_g_foreign_text' ) && topersia_g_foreign_text( $alt ) ) ) {
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

if ( ! function_exists( 'topersia_g_home_slider' ) ) {
	/**
	 * اسلایدر یک‌ردیفۀ صفحۀ اول — P5-D529. نوار و فلش‌ها همان topersia_g_slider_bar() صفحۀ موجودیت و همان
	 * دستگیرۀ topersia-g-zones.js (P5-D243): نماد از topersia_g_arrows()، اسکرول از direction فهرست، قبلی/بعدی در آغاز/پایان غیرفعال.
	 */
	function topersia_g_home_slider( $id, $label, $cards, $count ) {
		if ( '' === $cards ) {
			return '';
		}

		return '<div id="' . esc_attr( $id ) . '" class="tp-home-slider" tabindex="0" role="group" aria-label="' . esc_attr( $label ) . '">' . $cards . '</div>'
			. ( function_exists( 'topersia_g_slider_bar' ) ? topersia_g_slider_bar( $id, $count ) : '' );
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
			. '<span class="tp-home-card__txt">' . topersia_g_demo_badge( $pid ) . '<b class="tp-home-card__name">' . esc_html( $name ) . '</b>'
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
			array( count( topersia_g_home_real( array( 'tp_province' ), -1, false ) ), __( 'استان', 'topersia' ), 'column' ),
			array( count( topersia_g_home_real( array( 'tp_city' ), -1, false ) ), __( 'شهر', 'topersia' ), 'city' ),
			array( count( topersia_g_home_real( array( 'tp_village' ), -1, false ) ), __( 'روستا', 'topersia' ), 'home' ),
			array( count( topersia_g_home_real( $attr, -1, false ) ), __( 'جاذبه', 'topersia' ), 'gate' ),
			array( count( topersia_g_home_real( array( 'tp_festival', 'tp_sports_event', 'tp_religious_event', 'tp_seasonal_event' ), -1, false ) ), __( 'رویداد', 'topersia' ), 'cal' ),
			array( count( topersia_g_home_real( array( 'tp_route' ), -1, false ) ), __( 'مسیر گردشگری', 'topersia' ), 'route' ),
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
	 * @param array $tips    شناسه ← متن راهنما («نام — N شهر · M جاذبه»)؛ دستۀ 47.
	 */
	function topersia_g_home_map( array $by_slug, array $tips = array() ) {

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
				$tip   = isset( $tips[ $pid ] ) ? $tips[ $pid ] : $name;
				$out  .= '<a class="tp-home-map__a" href="' . esc_url( get_permalink( $pid ) ) . '" aria-label="' . esc_attr( $tip ) . '" data-tp-tip="' . esc_attr( $tip ) . '"><title>' . esc_html( $name ) . '</title><path d="' . esc_attr( $d ) . '"/></a>';
				$links ++;
			} else {
				$out .= '<path class="tp-home-map__off" d="' . esc_attr( $d ) . '"/>';
			}
		}

		if ( ! $links ) {
			return '';
		}

		return '<figure class="tp-home-map"><svg class="tp-home-map__svg" viewBox="' . esc_attr( $vb[1] ) . '" role="group" aria-label="' . esc_attr__( 'نقشۀ استان‌های ایران', 'topersia' ) . '" focusable="false">' . $out . '</svg>'
			. '<span class="tp-home-map__tip" role="tooltip" hidden></span>'
			. '<figcaption class="tp-home-map__src">' . esc_html__( 'مرزها: OpenStreetMap contributors (ODbL)', 'topersia' ) . '</figcaption></figure>';
	}
}

if ( ! function_exists( 'topersia_g_home_prov_counts' ) ) {
	/**
	 * شمار شهر و جاذبۀ منتشرشدۀ یک استان — همان گروه‌های «places» و «attractions» صفحۀ استان
	 * (topersia_g_group_ids، زنجیرۀ ۷۲→۷۳→…)، پس عدد کاشی و صفحۀ استان هرگز واگرا نمی‌شوند.  دستۀ 47
	 *
	 * @return int[] [ شهر، جاذبه ]
	 */
	function topersia_g_home_prov_counts( $pid ) {

		$out = array();

		foreach ( array( 'places', 'attractions' ) as $key ) {
			$group = function_exists( 'topersia_g_group' ) ? topersia_g_group( $pid, $key ) : array();
			$out[] = ( $group && function_exists( 'topersia_g_group_ids' ) ) ? count( topersia_g_group_ids( $pid, $group ) ) : 0;
		}

		return $out;
	}
}

if ( ! function_exists( 'topersia_g_home_prov_label' ) ) {
	/** «N شهر · M جاذبه» — صفر پنهان؛ هر دو صفر ← ''. */
	function topersia_g_home_prov_label( array $c ) {

		$parts = array();

		if ( $c[0] > 0 ) {
			/* translators: %s: شمار شهر */
			$parts[] = sprintf( _n( '%s شهر', '%s شهر', $c[0], 'topersia' ), topersia_g_fa( $c[0] ) );
		}
		if ( $c[1] > 0 ) {
			/* translators: %s: شمار جاذبه */
			$parts[] = sprintf( _n( '%s جاذبه', '%s جاذبه', $c[1], 'topersia' ), topersia_g_fa( $c[1] ) );
		}

		return implode( ' · ', $parts );
	}
}

if ( ! function_exists( 'topersia_g_home_prov_all' ) ) {
	/**
	 * همۀ ۳۱ استان نقشه (نامک‌های `assets/home/iran-provinces.svg`) برای پنجرۀ «همۀ استان‌ها».
	 * منتشرشده به زبان صفحه ← نام و پیوند؛ وگرنه نام پست فارسی منتشرشده با lang="fa"، کم‌رنگ و بی پیوند
	 * (P5-D278: نام به زبان دیگر فقط با lang). مرتب با Collator زبان صفحه (فارسی: الفبای فارسی).
	 *
	 * @param array $by_slug نامک ← شناسۀ زبان جاری.
	 * @return array[] هر عضو [ نام، نشانی یا ''، lang ]
	 */
	function topersia_g_home_prov_all( array $by_slug ) {

		$file = dirname( __DIR__ ) . '/assets/home/iran-provinces.svg';
		$svg  = is_readable( $file ) ? (string) file_get_contents( $file ) : '';

		if ( ! preg_match_all( '#<path id="tp-prov-([a-z0-9-]+)"#', $svg, $m ) ) {
			return array();
		}

		$lang = topersia_g_page_lang();
		$rows = array();

		foreach ( array_unique( $m[1] ) as $slug ) {

			if ( isset( $by_slug[ $slug ] ) ) {
				$rows[] = array( (string) get_the_title( $by_slug[ $slug ] ), (string) get_permalink( $by_slug[ $slug ] ), $lang );
				continue;
			}

			$fa = topersia_g_in_default_lang( function () use ( $slug ) {
				return topersia_g_published( get_posts( array( 'post_type' => 'tp_province', 'name' => $slug, 'post_status' => 'publish', 'has_password' => false, 'posts_per_page' => 1, 'fields' => 'ids', 'suppress_filters' => true, 'meta_query' => array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) ) ) ) );
			} );

			if ( $fa ) {
				$rows[] = array( (string) get_the_title( (int) $fa[0] ), '', 'fa' );
			}
		}

		$col = class_exists( 'Collator' ) ? new Collator( $lang ) : null;

		usort( $rows, function ( $a, $b ) use ( $col ) {
			return $col ? (int) $col->compare( $a[0], $b[0] ) : strcmp( $a[0], $b[0] );
		} );

		return $rows;
	}
}

if ( ! function_exists( 'topersia_g_home_rating' ) ) {
	/** امتیاز واقعی JetReviews (topersia_g_reviews_data — همان بخش نظرها) فقط اگر نظر تأییدشده هست؛ وگرنه ''. */
	function topersia_g_home_rating( $pid ) {

		$r = function_exists( 'topersia_g_reviews_data' ) ? topersia_g_reviews_data( $pid ) : array();

		if ( empty( $r['count'] ) || empty( $r['avg'] ) ) {
			return '';
		}

		return '<span class="tp-home-dest__rate">' . topersia_g_icon( 'star' ) . '<span>'
			/* translators: 1: میانگین امتیاز، 2: شمار نظر */
			. esc_html( sprintf( _n( '%1$s از %2$s نظر', '%1$s از %2$s نظر', (int) $r['count'], 'topersia' ), topersia_g_fa( number_format_i18n( (float) $r['avg'], 1 ) ), topersia_g_fa( (int) $r['count'] ) ) )
			. '</span></span>';
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

			/* P5-D553: آیکون راهنما ← هاب همان نوع؛ بی هاب ← همان پست */
			$hub     = function_exists( 'topersia_g_hub_link_for' ) ? topersia_g_hub_link_for( $type ) : '';
			$guide[] = array( $g[0], $g[1], '' !== $hub ? $hub : get_permalink( $one ) );

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

		/* ── تازه‌ترین اخبار (پیش از پیوندهای سریع، تا پیوند «اخبار» فقط با بخش دیدنی بیاید) ── */
		$news = '';

		if ( function_exists( 'topersia_g_news_on' ) && topersia_g_news_on() ) {

			$ids = topersia_g_published( array_map( 'absint', (array) get_posts( array(
				'post_type'        => 'tpc_news',
				'post_status'      => 'publish',
				'has_password'     => false,
				'posts_per_page'   => 3,
				'fields'           => 'ids',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'meta_query'       => topersia_g_sample_mq(),
			) ) ) );

			foreach ( $ids as $one ) {
				$news .= topersia_g_news_card( $one );
			}

			$news = '' !== $news ? '<div class="tp-news">' . $news . '</div>' : '';
		}

		$news_all  = ( '' !== $news && function_exists( 'get_post_type_archive_link' ) ) ? get_post_type_archive_link( 'tpc_news' ) : false;
		$news_more = $news_all ? array( $news_all, __( 'مشاهدۀ همه', 'topersia' ) ) : array();

		$tp_quick_news = '' !== $news;

		$quick = array();

		if ( $provinces ) {
			$quick[] = array( '#tp-home-provinces', __( 'استان‌ها', 'topersia' ), 'column' );
		}
		/* مقاصد: تا ۱۲ شهر دارای تصویر، فقط اگر دست‌کم ۴ — همان شرطِ چاپ بخش، تا پیوند سریع به بخش پنهان نرود */
		$dest = array_slice( array_values( array_filter( $cities, 'topersia_g_card_image' ) ), 0, 12 );
		$dest = count( $dest ) >= 4 ? $dest : array();

		/* P5-D540: ترتیب نمونه — استان‌ها · جاذبه‌ها · شهرها · اخبار (راهنمای سفر پس از آن‌ها) */
		if ( $groups ) {
			$quick[] = array( '#tp-home-attractions', __( 'جاذبه‌ها', 'topersia' ), 'gate' );
		}
		if ( $dest ) {
			$quick[] = array( '#tp-home-cities', __( 'شهرها', 'topersia' ), 'city' );
		}
		if ( $tp_quick_news ) {
			$quick[] = array( '#tp-home-news', __( 'اخبار', 'topersia' ), 'news' );
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
			/* P5-D536: جعبۀ جست‌وجوی سراسری زیر زیرعنوان، مانند مرجع (docs/design/home/reference-image.png). */
			. ( function_exists( 'topersia_g_search_form' ) ? topersia_g_search_form( 'tp-home-q', '', 'tp-home-search' ) : '' )
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
			$stats = '<section id="tp-home-stats" class="tp-home-stats tp-home-glass" aria-label="' . esc_attr( sprintf( /* translators: %s: نام برند (P5-D518) */ __( '%s در یک نگاه', 'topersia' ), topersia_g_brand() ) ) . '"><ul>' . $cells . '</ul></section>';
		}

		/* ── ۳ استان‌ها + نقشه ── */
		$by_slug = array();

		foreach ( $provinces as $id ) {
			$fa_id = (int) apply_filters( 'wpml_object_id', $id, 'tp_province', true, 'fa' );
			$by_slug[ (string) get_post_field( 'post_name', $fa_id ? $fa_id : $id ) ] = $id;
		}

		/* کاشی هر استان منتشرشده در اسلایدر scroll-snap؛ «N شهر · M جاذبه» از رابطه‌های منتشرشده، صفر پنهان.  دستۀ 47 ب */
		$prov_cards = '';
		$tips       = array();

		foreach ( $provinces as $id ) {
			$label        = topersia_g_home_prov_label( topersia_g_home_prov_counts( $id ) );
			$tips[ $id ]  = (string) get_the_title( $id ) . ( '' !== $label ? ' — ' . $label : '' );
			$prov_cards  .= topersia_g_home_card( $id, $label );
		}

		$map       = $provinces ? topersia_g_home_map( $by_slug, $tips ) : '';
		$all       = topersia_g_home_prov_all( $by_slug );
		$all_li    = '';

		foreach ( $all as $row ) {
			$fold    = function_exists( 'topersia_search_fold' ) ? ' data-tp-fold="' . esc_attr( topersia_search_fold( $row[0] ) ) . '"' : '';
			$all_li .= '' !== $row[1]
				? '<li' . $fold . '><a href="' . esc_url( $row[1] ) . '">' . esc_html( $row[0] ) . '</a></li>'
				: '<li class="tp-home-provall__off"' . $fold . '><span lang="' . esc_attr( $row[2] ) . '">' . esc_html( $row[0] ) . '</span></li>';
		}

		$dlg = '';

		if ( '' !== $all_li ) {
			/* translators: %s: شمار استان‌های یافته (JS جای‌گذاری می‌کند) */
			$found = __( 'یافته: %s', 'topersia' );
			$dlg   = '<dialog id="tp-home-provdlg" class="tp-home-dlg" aria-labelledby="tp-home-provdlgTitle">'
				. '<div class="tp-home-dlg__head"><h2 id="tp-home-provdlgTitle">' . esc_html__( 'همۀ استان‌ها', 'topersia' ) . '</h2>'
				. '<button type="button" class="tp-home-dlg__x" data-tp-close aria-label="' . esc_attr__( 'بستن', 'topersia' ) . '">×</button></div>'
				. '<label class="tp-home-dlg__lab" for="tp-home-provq">' . esc_html__( 'جست‌وجوی استان', 'topersia' ) . '</label>'
				. '<input id="tp-home-provq" class="tp-home-dlg__q" type="text" inputmode="search" autocomplete="off" aria-controls="tp-home-provlist">'
				. '<p class="tp-home-dlg__n" aria-live="polite" data-tp-n="' . esc_attr( $found ) . '"></p>'
				. '<ul id="tp-home-provlist" class="tp-home-provall">' . $all_li . '</ul></dialog>'
				. '<noscript><ul class="tp-home-provall tp-home-provall--ns">' . $all_li . '</ul></noscript>';
		}

		$prov_body = '' !== $prov_cards ? '<div class="tp-home-prov' . ( '' !== $map ? '' : ' tp-home-prov--solo' ) . '">'
			. '<div class="tp-home-prov__tiles">' . topersia_g_home_slider( 'tp-home-provslide', __( 'استان‌های ایران', 'topersia' ), $prov_cards, count( $provinces ) ) . '</div>'
			/* P5-D540: مثل نمونه — کاشی‌ها در آغاز سطر، نقشه در پایان */
			. ( '' !== $map ? '<div class="tp-home-prov__map"><h3 class="tp-home-sub">' . esc_html__( 'ایران روی نقشه', 'topersia' ) . '</h3>' . $map . '</div>' : '' )
			. '</div>' . $dlg : '';
		/* P5-D533: دکمۀ پنجرۀ «همۀ استان‌ها» در ردیف عنوان (بی JS پنهان؛ فهرست noscript جایش). */
		$prov_more = ( '' !== $prov_body && '' !== $dlg ) ? '<button type="button" class="tp-home-slider__all" data-tp-provdlg aria-haspopup="dialog" aria-controls="tp-home-provdlg" hidden>' . esc_html__( 'همۀ استان‌ها', 'topersia' ) . '</button>' : array();

		/*
		 * ── ۴ محبوب‌ترین مقاصد — دستۀ 47 پ: تا ۱۲ شهر منتشرشدۀ دارای تصویر (ترتیب: دارای تصویر، سپس شناسۀ کوچک‌تر —
		 * display_priority دستۀ 48)، اسلایدر یک‌ردیفه (P5-D529). کارت: تصویر، نام، short_summary
		 * (FIELDMAP، از topersia_g_text و دروازۀ زبان)، امتیاز فقط با نظر واقعی JetReviews. کمتر از ۴ ← پنهان.
		 */
		$city_cards = '';

		foreach ( $dest as $id ) {

			$att  = topersia_g_card_image( $id );
			$sum  = function_exists( 'topersia_g_text' ) ? trim( wp_strip_all_tags( topersia_g_text( $id, 'short_summary' ) ) ) : '';
			$city_cards .= '<a class="tp-home-dest" href="' . esc_url( get_permalink( $id ) ) . '">'
				. '<span class="tp-home-dest__media">' . topersia_g_home_img( $att, $id, 'medium_large', '(max-width: 600px) 70vw, 280px', 'tp-home-dest__img' ) . '</span>'
				. '<span class="tp-home-dest__txt"><b class="tp-home-dest__name">' . esc_html( get_the_title( $id ) ) . '</b>'
				. ( '' !== $sum ? '<span class="tp-home-dest__sum">' . esc_html( $sum ) . '</span>' : '' )
				. topersia_g_home_rating( $id ) . '</span></a>';
		}

		$city_all  = function_exists( 'get_post_type_archive_link' ) ? get_post_type_archive_link( 'tp_city' ) : false;
		$city_more = ( '' !== $city_cards && $city_all ) ? array( $city_all, __( 'مشاهدۀ همه', 'topersia' ) ) : array();

		/* ── ۵ جاذبه‌ها ── */
		$attr_cards = '';

		foreach ( $groups as $g ) {
			$attr_cards .= topersia_g_home_card( $g[1][0], get_the_title( $g[1][0] ), get_permalink( $g[1][0] ), $g[0] );
		}

		/* ── ۶ ردیف سه‌ستونی رویدادها | سفرهای آماده | فصل‌ها — topersia-g-home-row.php (P5-D560) ── */
		$row3 = function_exists( 'topersia_g_home_row3' ) ? topersia_g_home_row3() : '';

		/* ── ۷ راهنمای سفر ── */
		$guide_html = '';

		foreach ( $guide as $g ) {
			$guide_html .= '<a class="tp-home-guide__a" href="' . esc_url( $g[2] ) . '">' . topersia_g_icon( $g[1] ) . '<span>' . esc_html( $g[0] ) . '</span></a>';
		}

		/* ── ۷ب کارها و تجربیات — واژه‌های tp_is_experience با دست‌کم دو مورد دیدنی (P5-D555)؛ هیچ ← بخش ساخته نمی‌شود ── */
		$exp_html = '';

		foreach ( function_exists( 'topersia_g_tag_experiences' ) ? topersia_g_tag_experiences() : array() as $e ) {
			$exp_html .= '<a class="tp-home-guide__a" href="' . esc_url( $e[1] ) . '">' . ( '' !== $e[2] ? topersia_g_icon( $e[2] ) : '' ) . '<span>' . esc_html( $e[0] ) . '</span>'
				. '<small class="tp-home-exp__n">' . esc_html( topersia_g_fa( (int) $e[3] ) ) . '</small></a>';
		}

		/*
		 * ── ۹ ایران در تصویر — کلاژ (P5-D561): عکس‌های یکتای واقعی — hero_image، سپس featured_image_gallery — از هر موجودیت
		 * منتشرشدۀ غیرنمونه با صفحه؛ هر عکس پیوند به نخستین موجودیتی که آن را دارد (hero بر گالری مقدم)، عکسِ تک‌موجودیت پیش از مشترک،
		 * بی تکرار تصویر هیرو صفحه.
		 * هر اسلاید $per عکس (یک بزرگ + سه کوچک)؛ اسلایدها = کف(n / $per) تا سه: کمتر از $per ← پنهان، سپس ۱، ۲، ۳.
		 * پیش‌نمایش نمایشی: جای خالی تا سه اسلاید با جای‌نگهدار SVG برچسب‌دار (هرگز عکس بیرونی) — P5-D548.
		 */
		$per   = 4;
		$real  = topersia_g_home_real( array_keys( function_exists( 'topersia_g_hub_registry' ) ? topersia_g_hub_registry() : array() ), -1, false );
		$own   = array();   /* پیوست ← موجودیت‌های دارندۀ آن، hero پیش از گالری */
		$picks = array();

		if ( $real && function_exists( 'update_meta_cache' ) ) {
			update_meta_cache( 'post', $real );
		}

		foreach ( array( 'hero', 'gallery' ) as $pass ) {
			foreach ( $real as $id ) {

				if ( 'hero' === $pass ) {
					$hv   = topersia_g_raw( $id, 'hero_image' );
					$hv   = is_array( $hv ) ? ( isset( $hv['id'] ) ? $hv['id'] : reset( $hv ) ) : $hv;
					$list = is_scalar( $hv ) ? array( absint( $hv ) ) : array();
				} else {
					$list = function_exists( 'topersia_g_gallery_own_ids' ) ? topersia_g_gallery_own_ids( $id ) : array();
				}

				foreach ( $list as $att ) {
					if ( $att && $att !== $hero_att && ! in_array( $id, isset( $own[ $att ] ) ? $own[ $att ] : array(), true ) ) {
						$own[ $att ][] = $id;
					}
				}
			}
		}

		/*
		 * عکسِ یک موجودیت پیش از عکسِ مشترک — پیوند و نامِ زیر عکس بی‌ابهام؛ مشترک فقط برای پرکردن اسلاید.
		 * درون هر دسته دورگردان: نخستین عکس هر موجودیت، سپس دومین… — نه همۀ گالری یک شهر پشت هم.
		 */
		$turn = array();
		$key  = array();

		foreach ( $own as $att => $ids ) {
			$turn[ $ids[0] ] = isset( $turn[ $ids[0] ] ) ? $turn[ $ids[0] ] + 1 : 0;
			$key[ $att ]     = array( count( $ids ) > 1 ? 1 : 0, $turn[ $ids[0] ], count( $key ) );
		}

		uksort( $own, function ( $a, $b ) use ( $key ) {
			return $key[ $a ] <=> $key[ $b ];
		} );
		$seen = array( $hero_att => true );

		foreach ( $own as $att => $ids ) {

			if ( count( $picks ) >= 12 ) {
				break;
			}

			if ( ! $att || isset( $seen[ $att ] ) || ! wp_attachment_is_image( $att ) ) {
				continue;
			}

			$seen[ $att ] = true;
			$picks[]      = array( (int) $ids[0], (int) $att );
		}

		$demo  = function_exists( 'topersia_demo_on' ) && topersia_demo_on();
		$keep  = $demo ? count( $picks ) : min( 3, intdiv( count( $picks ), $per ) ) * $per;
		$cells = array();

		foreach ( array_slice( $picks, 0, $keep ) as $pick ) {

			list( $id, $att ) = $pick;
			$full    = wp_get_attachment_image_src( $att, 'large' );
			$desc    = function_exists( 'topersia_g_text' ) ? trim( wp_strip_all_tags( topersia_g_text( $id, 'short_summary' ) ) ) : '';
			$cells[] = '<a class="tp-home-photo" href="' . esc_url( get_permalink( $id ) ) . '" data-tp-title="' . esc_attr( get_the_title( $id ) ) . '" data-tp-desc="' . esc_attr( $desc ) . '"'
				. ( $full ? ' data-tp-full="' . esc_url( $full[0] ) . '" data-tp-w="' . absint( $full[1] ) . '" data-tp-h="' . absint( $full[2] ) . '"' : '' ) . '>'
				. topersia_g_home_img( $att, $id, 'medium_large', '(max-width: 600px) 90vw, 420px', 'tp-home-photo__img' )
				. '<span class="tp-home-photo__cap">' . esc_html( get_the_title( $id ) ) . '</span></a>';
		}

		while ( $demo && count( $cells ) < 3 * $per ) {
			$cells[] = '<span class="tp-home-photo tp-home-photo--ph" role="img" aria-label="' . esc_attr__( 'نمونۀ نمایشی', 'topersia' ) . '">'
				. '<svg class="tp-home-photo__svg" viewBox="0 0 4 3" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 3 1.4 1.4 2.3 2.3 3 1.6 4 2.6V3Z"/><circle cx="3" cy=".8" r=".4"/></svg>'
				. '<span class="tp-home-photo__cap">' . esc_html__( 'نمونۀ نمایشی', 'topersia' ) . '</span></span>';
		}

		/* دستۀ 47 ث: کمتر از ۴ عکس یکتا ← بخش پنهان؛ پنجرۀ بومی <dialog> (پنجرۀ g-gallery به درگاه صفحۀ موجودیت بسته است). */
		$n      = count( $cells );
		$slides = intdiv( $n, $per );
		$photos = '';
		$dots   = '';

		foreach ( array_chunk( array_slice( $cells, 0, $slides * $per ), $per ) as $k => $chunk ) {
			/* translators: %s شمارۀ اسلاید */
			$lbl     = sprintf( __( 'اسلاید %s', 'topersia' ), topersia_g_fa( $k + 1 ) );
			$photos .= '<div class="tp-home-collage" role="group" aria-label="' . esc_attr( $lbl ) . '">' . implode( '', $chunk ) . '</div>';
			$dots   .= '<button type="button" aria-label="' . esc_attr( $lbl ) . '" aria-current="' . ( 0 === $k ? 'true' : 'false' ) . '"></button>';
		}

		/* نقطه‌ها فقط با دو اسلاید به بالا؛ بی JS پنهان (topersia-g-home.js نشان می‌دهد) — فلش و نوار از topersia_g_slider_bar */
		$dots = $slides > 1 ? '<div class="tp-home-dots" data-tp-dots="tp-home-photoslide" role="group" aria-label="' . esc_attr__( 'ایران در تصویر', 'topersia' ) . '" hidden>' . $dots . '</div>' : '';

		$photo_dlg = '';

		if ( $n < 4 ) {
			$photos = '';
		} else {
			$photo_dlg = '<dialog id="tp-home-photodlg" class="tp-home-dlg tp-home-photodlg" aria-labelledby="tp-home-photodlgTitle">'
				. '<div class="tp-home-dlg__head"><h2 id="tp-home-photodlgTitle"></h2><button type="button" class="tp-home-dlg__x" data-tp-close aria-label="' . esc_attr__( 'بستن', 'topersia' ) . '">×</button></div>'
				. '<img class="tp-home-photodlg__img" src="data:," alt="" width="1" height="1" loading="lazy" decoding="async"><p class="tp-home-photodlg__desc"></p>'
				. '<a class="tp-home-photodlg__go">' . esc_html__( 'مشاهدۀ صفحه', 'topersia' ) . '</a></dialog>';
		}

		/* «پرسش‌های متداول» و FAQPage صفحۀ اول برداشته شد — P5-D539. */

		/* کادر حساب کاربری انتهای صفحه برداشته شد — P5-D531. */

		/*
		 * P5-D540 — ترتیب و جای بخش‌ها مثل نمونه (فقط ساختار): آمار · [کاشی استان | نقشه] · مقاصد · جاذبه‌ها ·
		 * [رویدادها | سفرهای آماده | فصل‌ها] (P5-D560) · راهنمای سفر تمام‌عرض · کارها و تجربیات · [ایران در تصویر | تازه‌ترین اخبار].
		 * ستون خالی رندر نمی‌شود و دیگری پهن می‌شود.
		 */
		$pair = topersia_g_home_section( 'tp-home-gallery', __( 'ایران در تصویر', 'topersia' ), '' !== $photos ? topersia_g_home_slider( 'tp-home-photoslide', __( 'ایران در تصویر', 'topersia' ), $photos, $slides ) . $dots : '', array(), 'tp-home-col' )
			. topersia_g_home_section( 'tp-home-news', __( 'تازه‌ترین اخبار', 'topersia' ), $news, $news_more, 'tp-home-col' );
		$pair = '' !== $pair ? '<div class="tp-home-trio tp-home-lazy">' . $pair . '</div>' : '';

		$body = $stats
			. topersia_g_home_section( 'tp-home-provinces', __( 'استان‌های ایران', 'topersia' ), $prov_body, $prov_more )
			. topersia_g_home_section( 'tp-home-cities', __( 'محبوب‌ترین مقاصد', 'topersia' ), topersia_g_home_slider( 'tp-home-destslide', __( 'محبوب‌ترین مقاصد', 'topersia' ), $city_cards, count( $dest ) ), $city_more, 'tp-home-lazy' )
			. topersia_g_home_section( 'tp-home-attractions', __( 'جاذبه‌ها', 'topersia' ), topersia_g_home_slider( 'tp-home-attrslide', __( 'جاذبه‌ها', 'topersia' ), $attr_cards, count( $groups ) ), array(), 'tp-home-lazy' )
			. $row3   /* 'tp-home-events' نخستین ستون ردیف سه‌ستونی — P5-D560 */
			. topersia_g_home_section( 'tp-home-guide', __( 'راهنمای سفر به ایران', 'topersia' ), '' !== $guide_html ? '<nav class="tp-home-guide tp-home-glass" aria-label="' . esc_attr__( 'راهنمای سفر به ایران', 'topersia' ) . '">' . $guide_html . '</nav>' : '', array(), 'tp-home-lazy' )
			. topersia_g_home_section( 'tp-home-exp', __( 'کارها و تجربیات', 'topersia' ), '' !== $exp_html ? '<nav class="tp-home-guide tp-home-exp tp-home-glass" aria-label="' . esc_attr__( 'کارها و تجربیات', 'topersia' ) . '">' . $exp_html . '</nav>' : '', array(), 'tp-home-lazy' )
			. $pair
			. ( '' !== $photos ? $photo_dlg : '' );

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
		/* فرم جست‌وجوی هیرو — P5-D536 */
		wp_enqueue_style( 'topersia-g-search', topersia_render_url() . 'assets/topersia-g-search.css', array( 'topersia-g-template' ), topersia_render_asset_version( 'assets/topersia-g-search.css' ) );
		/* دستۀ 47 — اسلایدر، پنجرۀ استان‌ها، راهنمای نقشه، پنجرۀ عکس؛ فقط صفحۀ اول، پاورقی، سقف ۴KB (assert-47). */
		wp_enqueue_script( 'topersia-g-home', topersia_render_url() . 'assets/topersia-g-home.js', array( 'topersia-g-zones' ), topersia_render_asset_version( 'assets/topersia-g-home.js' ), true );
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
