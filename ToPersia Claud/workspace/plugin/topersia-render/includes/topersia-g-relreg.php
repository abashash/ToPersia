<?php
/**
 * ثبت‌نامۀ جایگاه رابطه، زنجیرۀ والد مکانی و باکس شهرهای مسیر — ۲و٫۲ · P5-D320 · P5-D324 · P5-D336 · D338 · P5-D339 · P5-D340
 *
 * ثبت‌نامه از خودِ جت‌انجین ساخته می‌شود (هر رابطۀ فعال، هر دو سو)، نه از فهرست دستی؛
 * رابطۀ تازه‌ای که ساخته شود خودکار در آن می‌آید. هیچ رابطه‌ای این‌جا تعریف نمی‌شود و
 * هیچ مقداری در متا کپی نمی‌شود: همه‌چیز در زمان رندر از رابطه خوانده می‌شود.
 *
 * خروجیِ دیدنی فقط دو چیز است:
 *   ۱ والد مکانی R01 به‌شکل کاشی در کارت «اطلاعات جغرافیایی» ستون کناری (`topersia_g_geo_parent_tiles`) — بی کشور، بی همتا
 *   ۲ باکس «شهرهای مسیر» زیر نقشه در صفحۀ مسیر گردشگری (`topersia_g_sc_route_cities`)
 * و مسیر راهنما (`topersia_g_crumb_places`) که BreadcrumbList هم از همان می‌خواند. کاشی در سربرگ نیست (P5-D339).
 * باکس «ارتباط‌ها» (P5-D322) ساخته نشده و تا دستور کاربر ساخته نمی‌شود.
 *
 * @package ToPersia_Render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ثبت‌نامۀ روابط: شناسه ← نام، نوع، نوع والد و فرزند، برچسب هر سو.
 *
 * منبع: جت‌انجین زنده؛ صافی `topersia_g_relreg` برای آزمون و برای افزودن/اصلاح.
 *
 * @return array<int,array>
 */
if ( ! function_exists( 'topersia_g_relreg' ) ) {
	function topersia_g_relreg() {

		$reg = array();

		if ( function_exists( 'jet_engine' ) && isset( jet_engine()->relations ) ) {

			foreach ( (array) jet_engine()->relations->get_active_relations() as $rel ) {

				if ( ! is_object( $rel ) || ! method_exists( $rel, 'get_args' ) ) {
					continue;
				}

				$args = (array) $rel->get_args();
				$id   = absint( $rel->get_id() );
				$lab  = isset( $args['labels'] ) && is_array( $args['labels'] ) ? $args['labels'] : array();

				if ( ! $id ) {
					continue;
				}

				$reg[ $id ] = array(
					'id'           => $id,
					'name'         => isset( $args['name'] ) ? (string) $args['name'] : '',
					'type'         => isset( $args['type'] ) ? (string) $args['type'] : '',
					'parent'       => isset( $args['parent_object'] ) ? preg_replace( '#^posts::#', '', (string) $args['parent_object'] ) : '',
					'child'        => isset( $args['child_object'] ) ? preg_replace( '#^posts::#', '', (string) $args['child_object'] ) : '',
					'parent_label' => isset( $lab['parent_page_control_title'] ) ? (string) $lab['parent_page_control_title'] : '',
					'child_label'  => isset( $lab['child_page_control_title'] ) ? (string) $lab['child_page_control_title'] : '',
				);
			}
		}

		ksort( $reg );

		return function_exists( 'apply_filters' ) ? (array) apply_filters( 'topersia_g_relreg', $reg ) : $reg;
	}
}

/**
 * سوهای یک نوع پست در ثبت‌نامه: «این نوع فرزند است ← والدش را بگیر» و «والد است ← فرزندانش».
 *
 * @param string $type نوع پست.
 * @return array<int,array{id:int,dir:string,other:string,label:string,type:string}> dir همان واژۀ `topersia_g_rel_ids`
 */
if ( ! function_exists( 'topersia_g_relreg_sides' ) ) {
	function topersia_g_relreg_sides( $type ) {

		$out = array();

		foreach ( topersia_g_relreg() as $r ) {

			/* برچسبِ کنترلِ همان صفحه در جت‌انجین: روی فرزند `child_page_control_title` نام والد را می‌گوید و برعکس */
			if ( $r['child'] === $type ) {
				$out[] = array( 'id' => $r['id'], 'dir' => 'parents', 'other' => $r['parent'], 'label' => $r['child_label'], 'type' => $r['type'] );
			}

			if ( $r['parent'] === $type ) {
				$out[] = array( 'id' => $r['id'], 'dir' => 'children', 'other' => $r['child'], 'label' => $r['parent_label'], 'type' => $r['type'] );
			}
		}

		return $out;
	}
}

/**
 * رتبۀ مکانی هر نوع: عدد بزرگ‌تر = جزئی‌تر. شهرستان بی‌صفحه است و در زنجیره نمی‌آید.
 *
 * @return array<string,int>
 */
if ( ! function_exists( 'topersia_g_place_rank' ) ) {
	function topersia_g_place_rank() {

		return array(
			'tp_country'  => 1,
			'tp_region'   => 2,
			'tp_province' => 3,
			'tp_county'   => 4,
			'tp_city'     => 5,
			'tp_district' => 6,
			'tp_village'  => 6,
		);
	}
}

/**
 * والدهای مکانی یک پست از ثبت‌نامه، جزئی‌ترین اول — فقط منتشرشده و صفحه‌دار، به زبان صفحه.
 *
 * @param int  $pid         پست.
 * @param int  $depth       عمق گذر از والد بی‌صفحه (داخلی).
 * @param bool $with_nopage والد بی‌صفحه (شهرستان) خودش در فهرست بیاید، نه والدهایش (زنجیرۀ مسیر راهنما).
 * @return array<int,array{id:int,rel:int}>
 */
if ( ! function_exists( 'topersia_g_place_parents' ) ) {
	function topersia_g_place_parents( $pid, $depth = 0, $with_nopage = false ) {

		$type = function_exists( 'get_post_type' ) ? (string) get_post_type( $pid ) : '';
		$rank = topersia_g_place_rank();
		$out  = array();

		foreach ( topersia_g_relreg_sides( $type ) as $side ) {

			/* R01 = رابطۀ شمول: یک‌به‌چند با والد مکانی. «رایج در …» و «برگزار در …» (چندبه‌چند) و مرکزیت (یک‌به‌یک) زنجیره نیستند */
			if ( 'parents' !== $side['dir'] || 'one_to_many' !== $side['type'] || ! isset( $rank[ $side['other'] ] ) ) {
				continue;
			}

			$spec  = array( array( 'id' => $side['id'], 'dir' => 'parents' ) );
			$found = topersia_g_rel_ids( $pid, $spec, false );

			/*
			 * ترجمه یال رابطۀ خودش را ندارد (JetEngine یال را روی پست فارسی می‌گذارد): والد مکانی از پست اصلی، و دروازۀ منتشرشده
			 * شناسۀ نسخۀ زبان جاری را برمی‌گرداند — وگرنه شهر و روستا در /en/ و /ar/ بی استان و بی زنجیره می‌ماندند.  P5-D349
			 */
			if ( ! $found && function_exists( 'topersia_g_default_id' ) && topersia_g_default_id( $pid ) !== $pid ) {
				$found = topersia_g_rel_ids( topersia_g_default_id( $pid ), $spec, false );
			}

			foreach ( $found as $parent ) {

				$ptype = (string) get_post_type( $parent );

				if ( ! isset( $rank[ $ptype ] ) ) {
					continue;
				}

				/* والد بی‌صفحه (شهرستان): از آن می‌گذرد و والدهای مکانی‌اش را می‌گیرد — مثل مسیر راهنما  P5-D247 */
				if ( '' === topersia_g_public_url( $parent ) ) {

					if ( $with_nopage ) {
						$out[ $parent ] = array( 'id' => (int) $parent, 'rel' => (int) $side['id'], 'rank' => $rank[ $ptype ] );
						continue;
					}

					if ( $depth < 3 ) {
						foreach ( topersia_g_place_parents( $parent, $depth + 1 ) as $up ) {
							$out[ $up['id'] ] = $up;
						}
					}

					continue;
				}

				/* والد صفحه‌دار: فقط اگر نسخۀ منتشرشدۀ زبان جاری هست (پیمودن بی دروازه بود تا شهرستان بی ترجمه راه را نبندد)  P5-D247 · P5-D349 */
				if ( function_exists( 'topersia_g_published' ) && ! topersia_g_published( array( $parent ) ) ) {
					continue;
				}

				$out[ $parent ] = array( 'id' => (int) $parent, 'rel' => (int) $side['id'], 'rank' => $rank[ $ptype ] );
			}
		}

		uasort( $out, function ( $a, $b ) {
			return $b['rank'] <=> $a['rank'];
		} );

		return array_values( $out );
	}
}

/**
 * زنجیرۀ کامل والدهای مکانی از R01 (کشور هم)، از ریشه به پایین.  P5-D320 · D338
 *
 * هر پله جزئی‌ترین والد مکانیِ پلۀ پیشین است؛ سطحِ بی‌رابطه حذف می‌شود، چیزی حدس زده نمی‌شود.
 * والد بی‌صفحه (شهرستان) در زنجیره نمی‌آید ولی راه از آن می‌گذرد (مثل `skip` در گروه crumbs دستی).
 * کشور این‌جا هست تا `containedInPlace` اسکیما از آن بخواند؛ آنچه کاربر می‌بیند `topersia_g_crumb_places` است.
 *
 * @param int $pid پست.
 * @return array<int,int> شناسۀ نسخۀ زبان جاری، ریشه اول؛ خالی اگر ثبت‌نامه یا رابطه‌ای نیست.
 */
if ( ! function_exists( 'topersia_g_place_chain' ) ) {
	function topersia_g_place_chain( $pid ) {

		$chain = array();
		$cur   = absint( $pid );
		$seen  = array( $cur => 1 );

		for ( $guard = 0; $guard < 6 && $cur; $guard++ ) {

			$ups = topersia_g_place_parents( $cur, 0, true );

			if ( ! $ups ) {
				break;
			}

			$cur = (int) $ups[0]['id'];

			if ( isset( $seen[ $cur ] ) ) {
				break;
			}

			$seen[ $cur ] = 1;

			/* شهرستان صفحه ندارد: راه از آن می‌گذرد ولی سطحی در مسیر راهنما نیست (مثل گروه crumbs دستی با skip) */
			if ( '' !== topersia_g_public_url( $cur ) ) {
				$chain[] = $cur;
			}
		}

		return array_map( 'topersia_g_view_id', array_reverse( $chain ) );
	}
}

/**
 * سطح‌های دیدنیِ مسیر راهنما و کاشی‌های جغرافیایی: همان زنجیره بی کشور.  D338 · P5-D339
 *
 * @param int $pid پست.
 * @return array<int,int> ریشه (استان) اول.
 */
if ( ! function_exists( 'topersia_g_crumb_places' ) ) {
	function topersia_g_crumb_places( $pid ) {

		return array_values( array_filter( topersia_g_place_chain( $pid ), function ( $id ) {
			return 'tp_country' !== get_post_type( $id );
		} ) );
	}
}

/**
 * کاشی‌های والد مکانی برای کارت «اطلاعات جغرافیایی» — فقط زنجیرۀ R01 (استان، شهر، روستا)؛ کشور نه، همتا نه.  P5-D339
 * شهرِ مرکز استان کاشی استان را «مرکز استان» برچسب می‌زند.
 *
 * @param int $pid پست.
 * @return array<int,array{label:string,name:string,url:string}> قالب `topersia_g_tiles`.
 */
if ( ! function_exists( 'topersia_g_geo_parent_tiles' ) ) {
	function topersia_g_geo_parent_tiles( $pid ) {

		$pid   = absint( $pid );
		$type  = (string) get_post_type( $pid );
		$tiles = array();

		foreach ( topersia_g_crumb_places( $pid ) as $id ) {

			$kind = topersia_g_kind_label( $id );

			if ( 'tp_city' === $type && 'tp_province' === get_post_type( $id ) && absint( topersia_g_capital_id( $id ) ) === $pid ) {
				$kind = __( 'مرکز استان', 'topersia' );
			}

			/* همین والد در یکی از خانه‌های سربرگ است ← در کاشی‌های چپ تکرار نمی‌شود.  P5-D464 */
			if ( ! empty( topersia_g_stat_claims( $pid )[ 'parent:' . substr( (string) get_post_type( $id ), 3 ) ] ) ) {
				continue;
			}

			$tiles[] = array( 'label' => $kind, 'name' => topersia_g_name( $id ), 'url' => topersia_g_public_url( $id ) );
		}

		return $tiles;
	}
}

/**
 * کاشی‌های مکان‌های مرتبط برای نوع‌های غیرمکانی (رویداد، جشن، شخصیت، غذا، …) در کارت «اطلاعات جغرافیایی».  P5-D348
 *
 * فقط وقتی نوع در زنجیرۀ مکانی نیست (استان/شهر/روستا… خودشان والد مکانی دارند) و والد مکانی R01 هم ندارد: هر رابطۀ زندۀ جت‌انجین
 * که یک سویش استان، شهر یا روستا باشد (از ثبت‌نامه، هر دو سو)، منتشرشده و صفحه‌دار، به زبان صفحه. برچسب کاشی نوع مکان است
 * (استان/شهر/روستا)؛ کشور و همتا نه. چیزی کپی یا حدس زده نمی‌شود — بی رابطه ← بی کاشی.
 *
 * @param int $pid پست.
 * @return array<int,array{label:string,name:string,url:string}>
 */
if ( ! function_exists( 'topersia_g_geo_related_tiles' ) ) {
	function topersia_g_geo_related_tiles( $pid ) {

		$pid  = absint( $pid );
		$type = (string) get_post_type( $pid );
		$rank = topersia_g_place_rank();

		if ( isset( $rank[ $type ] ) ) {
			return array();
		}

		$want  = array( 'tp_province' => 1, 'tp_city' => 2, 'tp_village' => 3 );
		$found = array();

		foreach ( topersia_g_relreg_sides( $type ) as $side ) {

			if ( ! isset( $want[ $side['other'] ] ) ) {
				continue;
			}

			foreach ( topersia_g_rel_ids( $pid, array( array( 'id' => $side['id'], 'dir' => $side['dir'] ) ) ) as $other ) {

				$view = topersia_g_view_id( $other );
				$url  = topersia_g_public_url( $view );

				if ( '' === $url || isset( $found[ $view ] ) || ( 'tp_province' === $side['other'] && ! empty( topersia_g_stat_claims( $pid )['origin'] ) ) ) {   /* خانۀ «خاستگاه» سربرگ استان‌ها را دارد (P5-D464) */
					continue;
				}

				$found[ $view ] = array( 'rank' => $want[ $side['other'] ], 'tile' => array( 'label' => topersia_g_kind_label( $view ), 'name' => topersia_g_name( $view ), 'url' => $url ) );
			}
		}

		/* استان، سپس شهر، سپس روستا؛ هر دسته به ترتیب یافتن */
		uasort( $found, function ( $a, $b ) {
			return $a['rank'] <=> $b['rank'];
		} );

		$tiles = array();

		foreach ( $found as $one ) {
			$tiles[] = $one['tile'];
		}

		return $tiles;
	}
}
/**
 * کاشی‌های مرکزیت برای کارت «اطلاعات جغرافیایی».  P5-D342
 *
 * استان: «مرکز استان: {شهر}» از رابطۀ مرکز (۹۶، همان گروه `capital`). شهر: «مرکز شهرستان: {شهرستان}» از رابطۀ ۹۷ فقط وقتی داده دارد
 * (شهرستان صفحه ندارد ← کاشی بی پیوند). «شهر مرکز استان است» روی کاشی استان برچسب می‌شود (`topersia_g_geo_parent_tiles`).
 *
 * @param int $pid پست.
 * @return array<int,array{label:string,name:string,url:string}>
 */
if ( ! function_exists( 'topersia_g_geo_center_tiles' ) ) {
	function topersia_g_geo_center_tiles( $pid ) {

		$pid   = absint( $pid );
		$type  = (string) get_post_type( $pid );
		$tiles = array();

		if ( 'tp_province' === $type ) {

			$cap = absint( topersia_g_capital_id( $pid ) );

			if ( $cap && empty( topersia_g_stat_claims( $pid )['capital'] ) ) {   /* خانۀ «مرکز» سربرگ همین است — تکرار نه (P5-D464) */
				$view    = topersia_g_view_id( $cap );
				$tiles[] = array( 'label' => __( 'مرکز استان', 'topersia' ), 'name' => topersia_g_name( $view ), 'url' => topersia_g_public_url( $view ) );
			}
		}

		if ( 'tp_city' === $type ) {

			foreach ( topersia_g_rel_ids( $pid, array( array( 'id' => 97, 'dir' => 'parents' ) ) ) as $county ) {
				$tiles[] = array( 'label' => __( 'مرکز شهرستان', 'topersia' ), 'name' => topersia_g_name( $county ), 'url' => topersia_g_public_url( $county ) );
				break;
			}
		}

		return $tiles;
	}
}

/**
 * [tp_route_cities] — باکس «شهرهای مسیر» زیر نقشه: پیوند ایستگاه‌ها به ترتیب مسیر، فقط منتشرشده‌ها.  P5-D340
 *
 * ترتیب و فیلتر از `topersia_p9_stops()` هسته (`route_stops_order`، منتشرشده در زبان جاری). رابطۀ ۱۷۳ فراداده
 * (`sequence_order`) ندارد؛ ترتیب در همان فیلد مسیر است. کارت شماره‌دار و باکس در ستون اصلی ساخته نمی‌شود.
 */
if ( ! function_exists( 'topersia_g_sc_route_cities' ) ) {
	function topersia_g_sc_route_cities( $atts = array() ) {

		$pid = topersia_g_pid( $atts );

		if ( ! $pid || 'tp_route' !== get_post_type( $pid ) || ! function_exists( 'topersia_p9_stops' ) ) {
			return '';
		}

		$tiles = array();

		foreach ( (array) topersia_p9_stops( $pid ) as $stop ) {

			$id  = isset( $stop['id'] ) ? absint( $stop['id'] ) : 0;
			$url = $id ? topersia_g_public_url( $id ) : '';

			if ( '' === $url ) {
				continue;
			}

			/* دستۀ ۲۹ بند ۶: یادداشت توقف (route_stop_notes) زیر نام — پیش‌تر فقط در JSON-LD بود */
			$note    = isset( $stop['note'] ) ? trim( wp_strip_all_tags( (string) $stop['note'] ) ) : '';
			$tiles[] = array( 'label' => topersia_g_fa( count( $tiles ) + 1 ), 'name' => topersia_g_name( $id ), 'url' => $url,
				'sub' => '' !== $note ? ( mb_strlen( $note ) > 60 ? rtrim( mb_substr( $note, 0, 60 ) ) . '…' : $note ) : '' );
		}

		if ( ! $tiles ) {
			return '';
		}

		return topersia_g_section( 'tp-route-cities', __( 'شهرهای مسیر', 'topersia' ), '', topersia_g_tiles( $tiles, 0 ) );
	}
}

/**
 * کاشی‌های «استان‌های مسیر» برای کارت «اطلاعات جغرافیایی» نوع مسیر گردشگری.  P5-D385
 *
 * والد R01 (استان) هر ایستگاه، یکتا، به ترتیب مسیر (`topersia_p9_stops()` هسته). نام شهرهای ایستگاه این‌جا
 * نمی‌آید — شهرها فقط در باکس «شهرهای مسیر» (`topersia_g_sc_route_cities`). استانِ بی‌صفحه یا منتشرنشده نه.
 *
 * @param int $pid پست مسیر.
 * @return array<int,array{label:string,name:string,url:string}>
 */
if ( ! function_exists( 'topersia_g_route_province_tiles' ) ) {
	function topersia_g_route_province_tiles( $pid ) {

		$pid = absint( $pid );

		if ( ! $pid || 'tp_route' !== get_post_type( $pid ) || ! function_exists( 'topersia_p9_stops' ) ) {
			return array();
		}

		$seen  = array();
		$tiles = array();

		foreach ( (array) topersia_p9_stops( $pid ) as $stop ) {

			$id = isset( $stop['id'] ) ? absint( $stop['id'] ) : 0;

			if ( ! $id ) {
				continue;
			}

			$prov = 0;

			/* خود ایستگاه استان است، یا نخستین استان زنجیرۀ R01 آن */
			if ( 'tp_province' === get_post_type( $id ) ) {
				$prov = $id;
			} else {
				foreach ( topersia_g_place_chain( $id ) as $up ) {
					if ( 'tp_province' === get_post_type( $up ) ) {
						$prov = (int) $up;
						break;
					}
				}
			}

			$prov = $prov ? (int) topersia_g_view_id( $prov ) : 0;
			$url  = $prov ? topersia_g_public_url( $prov ) : '';

			if ( ! $prov || '' === $url || isset( $seen[ $prov ] ) ) {
				continue;
			}

			$seen[ $prov ] = 1;
			$tiles[]       = array( 'label' => __( 'استان مسیر', 'topersia' ), 'name' => topersia_g_name( $prov ), 'url' => $url );
		}

		return $tiles;
	}
}

/* باکس شهرهای مسیر در ثبت‌نامۀ پنل چپ (`includes/topersia-g-panel.php`) است، بلافاصله پس از مسیریابی؛ فقط نوع مسیر محتوا دارد.  P5-D347 */

if ( function_exists( 'add_filter' ) ) {
	/* زنجیرۀ R01 برای نمایۀ مشتق مکانی هسته (استان و شهر هر پست) — P5-D334 */
	add_filter( 'topersia_geo_index_chain', function ( $chain, $pid ) {
		return function_exists( 'topersia_g_place_chain' ) ? topersia_g_place_chain( $pid ) : $chain;
	}, 10, 2 );
}

if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'tp_route_cities', 'topersia_g_sc_route_cities' );
}
