<?php
/**
 * صفحۀ اول — شورت‌کد `[tp_home]`.  دستۀ 45
 *
 * عنوان و جملۀ معرفی از خودِ صفحۀ وردپرس می‌آیند (عنوان و گزیده، هر زبان در ترجمۀ WPML خودش)؛
 * بقیه فقط از پایگاه‌داده: پست‌های واقعی و منتشرشدۀ همان زبان، از دروازۀ مرکزی. نمونه‌ها (`_tp_sample`)
 * هرگز نمی‌آیند. هیچ عدد و آماری نوشته نمی‌شود. بخشِ بی‌داده اصلاً چاپ نمی‌شود.
 * جست‌وجوی سراسری ندارد، پس کادر جست‌وجو و SearchAction هم نیست.
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

if ( ! function_exists( 'topersia_g_home_section' ) ) {
	function topersia_g_home_section( $id, $title, $body ) {

		if ( '' === trim( (string) $body ) ) {
			return '';
		}

		return '<section id="' . esc_attr( $id ) . '" class="tp-section" aria-labelledby="' . esc_attr( $id ) . 'Title">'
			. '<div class="tp-section-head"><div><h2 id="' . esc_attr( $id ) . 'Title">' . esc_html( $title ) . '</h2></div></div>'
			. $body . '</section>';
	}
}

if ( ! function_exists( 'topersia_g_sc_home' ) ) {
	function topersia_g_sc_home( $atts = array() ) {

		$pid = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;

		/* فقط درون صفحۀ وردپرس (post_type=page)؛ روی موجودیت‌ها هیچ. */
		if ( 'page' !== get_post_type( $pid ) ) {
			return '';
		}

		/* ۱ — هیرو: عنوان و جملۀ معرفی از صفحه. */
		$title = $pid ? (string) get_the_title( $pid ) : '';
		$intro = $pid ? trim( (string) get_post_field( 'post_excerpt', $pid ) ) : '';

		$hero = '<section class="tp-section tp-home-hero">'
			. ( '' !== $title ? '<h1>' . esc_html( $title ) . '</h1>' : '' )
			. ( '' !== $intro ? '<p class="tp-lead">' . esc_html( $intro ) . '</p>' : '' )
			. '</section>';

		/* ۲ — شش دستۀ اصلی؛ پیوند به هاب همین صفحه یا نخستین نمونۀ واقعی. دستۀ بی‌داده نمی‌آید. */
		$provinces = topersia_g_home_by_title( topersia_g_home_real( array( 'tp_province' ) ) );
		$trip_types = array(
			'tp_currency'      => __( 'ارز', 'topersia' ),
			'tp_visa'          => __( 'روادید', 'topersia' ),
			'tp_health_safety' => __( 'سلامت و ایمنی', 'topersia' ),
		);
		$practical = array();

		foreach ( $trip_types as $type => $label ) {

			$one = topersia_g_home_first( array( $type ) );

			if ( $one ) {
				$practical[] = array( 'label' => $label, 'name' => get_the_title( $one ), 'url' => get_permalink( $one ) );
			}
		}

		$cats = array(
			array( __( 'استان‌ها', 'topersia' ), $provinces ? '#tp-home-provinces' : '' ),
			array( __( 'شهرها', 'topersia' ), topersia_g_home_first( array( 'tp_city' ) ) ),
			array( __( 'جاذبه‌ها', 'topersia' ), topersia_g_home_first( array( 'tp_monument', 'tp_ancient_site', 'tp_museum', 'tp_religious_site', 'tp_natural_feature', 'tp_landscape', 'tp_protected_area' ) ) ),
			array( __( 'غذا و صنایع‌دستی', 'topersia' ), topersia_g_home_first( array( 'tp_local_food', 'tp_handicraft' ) ) ),
			array( __( 'فرهنگ و تاریخ', 'topersia' ), topersia_g_home_first( array( 'tp_historical_era', 'tp_figure', 'tp_ritual', 'tp_ethnic_group', 'tp_language', 'tp_dance', 'tp_music', 'tp_costume', 'tp_festival' ) ) ),
			array( __( 'اطلاعات کاربردی', 'topersia' ), $practical ? '#tp-home-practical' : '' ),
		);
		$tiles = array();

		foreach ( $cats as $cat ) {

			$url = is_int( $cat[1] ) ? ( $cat[1] ? get_permalink( $cat[1] ) : '' ) : $cat[1];

			if ( '' !== (string) $url ) {
				$tiles[] = array( 'name' => $cat[0], 'url' => $url );
			}
		}

		$cat_html = $tiles ? topersia_g_tiles( $tiles, 0 ) : '';

		/* ۳ — فهرست استان‌ها. */
		$prov_tiles = array();

		foreach ( $provinces as $id ) {
			$prov_tiles[] = array( 'name' => get_the_title( $id ), 'url' => get_permalink( $id ) );
		}

		/* ۵ — تازه‌ترین اخبار فقط اگر خبر منتشرشده هست. */
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
			) ) ) );

			if ( $ids ) {

				$rows = '';

				foreach ( $ids as $one ) {
					$rows .= topersia_g_news_card( $one );
				}

				$news = '<div class="tp-news">' . $rows . '</div>';
			}
		}

		/* ۶ — دعوت به ورود، فقط وقتی صفحۀ پنل به این زبان هست. */
		$panel = get_page_by_path( 'account' );
		$panel = $panel ? (int) apply_filters( 'wpml_object_id', $panel->ID, 'page', false ) : 0;
		$invite = '';

		if ( $panel && 'publish' === get_post_status( $panel ) ) {
			$invite = '<p class="tp-lead">' . esc_html__( 'با حساب کاربری می‌توانید مکان‌ها را ذخیره و دنبال کنید و نظر بدهید.', 'topersia' ) . '</p>'
				. topersia_g_tiles( array( array( 'name' => __( 'ورود به پنل کاربری', 'topersia' ), 'url' => get_permalink( $panel ) ) ), 0 );
		}

		$body = topersia_g_home_section( 'tp-home-categories', __( 'دسته‌های اصلی', 'topersia' ), $cat_html )
			. topersia_g_home_section( 'tp-home-provinces', __( 'استان‌ها', 'topersia' ), $prov_tiles ? topersia_g_tiles( $prov_tiles, 0 ) : '' )
			. topersia_g_home_section( 'tp-home-practical', __( 'اطلاعات کاربردی', 'topersia' ), $practical ? topersia_g_tiles( $practical, 0 ) : '' )
			. topersia_g_home_section( 'tp-home-news', __( 'تازه‌ترین اخبار', 'topersia' ), $news )
			. topersia_g_home_section( 'tp-home-account', __( 'حساب کاربری', 'topersia' ), $invite );

		return '<div class="tp-page"><main id="tp-main" class="tp-main"><div class="tp-wrap">' . $hero . $body . '</div></main></div>';
	}
}
