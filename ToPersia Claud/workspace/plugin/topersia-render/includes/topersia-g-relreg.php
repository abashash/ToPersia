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

			foreach ( topersia_g_rel_ids( $pid, array( array( 'id' => $side['id'], 'dir' => 'parents' ) ) ) as $parent ) {

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

			$tiles[] = array( 'label' => $kind, 'name' => topersia_g_name( $id ), 'url' => topersia_g_public_url( $id ) );
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

			$tiles[] = array( 'label' => topersia_g_fa( count( $tiles ) + 1 ), 'name' => topersia_g_name( $id ), 'url' => $url );
		}

		if ( ! $tiles ) {
			return '';
		}

		return topersia_g_section( 'tp-route-cities', __( 'شهرهای مسیر', 'topersia' ), '', topersia_g_tiles( $tiles, 0 ) );
	}
}

/** باکس شهرهای مسیر، بلافاصله پس از نقشه (فقط نوع مسیر). */
if ( ! function_exists( 'topersia_g_relreg_layout' ) ) {
	function topersia_g_relreg_layout( $layout, $post_id = 0 ) {

		if ( ! $post_id || 'tp_route' !== get_post_type( $post_id ) || ! is_array( $layout ) ) {
			return $layout;
		}

		$rail = isset( $layout['rail'] ) ? (array) $layout['rail'] : array();
		$cut  = count( $rail );

		foreach ( $rail as $i => $zone ) {
			if ( isset( $zone['id'] ) && 'tp-map' === $zone['id'] ) {
				$cut = $i + 1;
				break;
			}
		}

		array_splice( $rail, $cut, 0, array( array( 'fn' => 'topersia_g_sc_route_cities', 'atts' => array(), 'id' => 'tp-route-cities', 'nav' => '' ) ) );
		$layout['rail'] = $rail;

		return $layout;
	}
}

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'topersia_g_layout', 'topersia_g_relreg_layout', 20, 2 );
}

if ( function_exists( 'add_shortcode' ) ) {
	add_shortcode( 'tp_route_cities', 'topersia_g_sc_route_cities' );
}
