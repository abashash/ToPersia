<?php
/**
 * ToPersia — گالری تجمیعی (P5-D274 · P5-D300 · SPRINT-4.4 ۲ج٫۵)
 *
 * گالری کشور، استان و شهر از عکس‌های فرزندانشان پر می‌شود تا یک عکس بارها
 * درون‌ریزی نشود. این فایل فقط لایۀ مشتق را می‌سازد و می‌خواند؛ نمایش در
 * `topersia_g_sc_gallery()`.
 *
 *   زنجیره   کشور ← استان ← شهر ← روستا، محله، جاذبه‌ها و طبیعتِ درون شهر —
 *            از گروه‌های همان relmap، رابطۀ تازه‌ای نیست
 *   ترکیب    گالری خود اول؛ سپس به نوبت از فرزندان منتشرشده به ترتیب P5-D211:
 *            دور اول یک عکس از هر فرزند، دور دوم عکس دوم — حداکثر ۲ از هر
 *            فرزند، از گالری تجمیعیِ همان فرزند؛ سقف کل ۲۴؛ تکراری با شناسۀ
 *            پیوست حذف؛ گالری خودِ ۲۴ یا بیشتر = بی تجمیع
 *   منبع     هر عکس تجمیعی شناسۀ موجودیتی را که عکس از گالری خودش آمده نگه
 *            می‌دارد (کشور عکس شهر را از استان می‌گیرد، منبعش همان شهر است)
 *   ذخیره    متای پنهان `_tp_gallery_agg` روی پست زبان اصلی، `ignore` در
 *            wpml-config. هرگز منبع حقیقت نیست؛ با بازسازی بی افت داده برمی‌گردد.
 *            در بارگذاری صفحه محاسبه نمی‌شود — P5-D300
 *   بازسازی  ذخیره، تغییر وضعیت، حذف، تغییر گالری یا ردیف رابطه ← کار پس‌زمینۀ Action
 *            Scheduler هسته (P5-D290) برای خود و والدها تا کشور، با ادغام
 *            تکراری‌ها؛ از پایین به بالا. WP-CLI و دکمۀ «بازسازی همه».
 *   دروازه   تا ممیزی NotebookLM فقط محلی (`topersia_g_gallery_agg_enabled()`)
 *
 * @package topersia-render
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** سقف کل گالری و سهم هر فرزند.  P5-D274 */
if ( ! defined( 'TOPERSIA_GALLERY_MAX' ) ) {
	define( 'TOPERSIA_GALLERY_MAX', 24 );
}
if ( ! defined( 'TOPERSIA_GALLERY_PER_CHILD' ) ) {
	define( 'TOPERSIA_GALLERY_PER_CHILD', 2 );
}

/** کلید متای مشتق — P5-D300. */
if ( ! defined( 'TOPERSIA_GALLERY_AGG_META' ) ) {
	define( 'TOPERSIA_GALLERY_AGG_META', '_tp_gallery_agg' );
}

/**
 * لایۀ مشتق روشن است؟ تا ممیزی NotebookLM فقط محلی، مثل جدول کش هسته و
 * متای `_tp_search_fold` (پیوست الف بند ۶).
 */
if ( ! function_exists( 'topersia_g_gallery_agg_enabled' ) ) {
	function topersia_g_gallery_agg_enabled() {
		$on = function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type();
		return function_exists( 'apply_filters' ) ? (bool) apply_filters( 'topersia_gallery_agg_enabled', $on ) : $on;
	}
}

/**
 * گروه‌های relmap که فرزندان تجمیعِ هر نوع‌اند — رابطۀ تازه ساخته نمی‌شود.
 *
 * @return array نوع ← کلیدهای گروه
 */
if ( ! function_exists( 'topersia_g_gallery_agg_groups' ) ) {
	function topersia_g_gallery_agg_groups() {
		$groups = array(
			'tp_country'  => array( 'provinces' ),
			'tp_province' => array( 'places' ),
			'tp_city'     => array( 'villages', 'districts', 'attractions', 'nature' ),
		);
		return function_exists( 'apply_filters' ) ? (array) apply_filters( 'topersia_gallery_agg_groups', $groups ) : $groups;
	}
}

/** شناسه‌های پیوستِ گالری خودِ یک پست، به ترتیب و بی تکرار. */
if ( ! function_exists( 'topersia_g_gallery_own_ids' ) ) {
	function topersia_g_gallery_own_ids( $post_id ) {

		$raw = topersia_g_raw( $post_id, 'featured_image_gallery' );

		if ( is_string( $raw ) ) {
			$raw = explode( ',', $raw );
		}

		$ids = array();

		foreach ( (array) $raw as $one ) {
			$one = absint( is_array( $one ) && isset( $one['id'] ) ? $one['id'] : $one );
			if ( $one && ! in_array( $one, $ids, true ) ) {
				$ids[] = $one;
			}
		}

		return $ids;
	}
}

/**
 * کار بازسازی همیشه به زبان اصلی WPML — فرزندان، دروازۀ انتشار و فهرست پست‌ها از
 * همان زبان، در هر بافتی که کار صف اجرا شود (کرون، درخواست ناهمگام، WP-CLI).
 * بی آن، کاری که در بافت انگلیسی اجرا شود فرزندان را به ترجمه‌ها نگاشت می‌کند و
 * منبعِ نادرست ذخیره می‌شود.
 */
if ( ! function_exists( 'topersia_g_gallery_agg_in_default_lang' ) ) {
	function topersia_g_gallery_agg_in_default_lang( callable $fn ) {
		return topersia_g_in_default_lang( $fn );   /* یک پیاده‌سازی، با آمار حقیقی P5-D306 — zones */
	}
}

/** پست زبان اصلی WPML (یا خودش). متای مشتق فقط آن‌جا. */
if ( ! function_exists( 'topersia_g_gallery_agg_primary' ) ) {
	function topersia_g_gallery_agg_primary( $post_id ) {
		return topersia_g_default_id( $post_id );
	}
}

/**
 * فرزندان منتشرشدۀ تجمیع یک پست، به ترتیب P5-D211 — بی تکرار.
 *
 * @return int[]
 */
if ( ! function_exists( 'topersia_g_gallery_agg_children' ) ) {
	function topersia_g_gallery_agg_children( $post_id ) {

		$type   = (string) get_post_type( $post_id );
		$groups = topersia_g_gallery_agg_groups();
		$ids    = array();

		foreach ( isset( $groups[ $type ] ) ? $groups[ $type ] : array() as $key ) {

			/* «شهرها»ی استان مرکز را جدا از زنجیره دارد (رابطۀ ۹۶) — همان شهرها */
			$group = topersia_g_group( $post_id, $key );

			if ( 'places' === $key && $type === 'tp_province' ) {
				$capital = topersia_g_capital_id( $post_id );
				if ( $capital ) {
					$ids[] = absint( $capital );
				}
			}

			foreach ( topersia_g_group_ids( $post_id, $group ) as $one ) {
				$ids[] = absint( $one );
			}
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );

		/* فقط منتشرشده (P5-D247)؛ ترتیب یک قاعده برای همه (P5-D211) */
		return topersia_g_sort_entities( topersia_g_published( $ids ) );
	}
}

/**
 * گالری تجمیعیِ کامل یک پست — خود اول، سپس افزوده‌های ذخیره‌شده.
 *
 * @return array هر عضو: id (پیوست) · src (موجودیت صاحب عکس)
 */
if ( ! function_exists( 'topersia_g_gallery_agg_full' ) ) {
	function topersia_g_gallery_agg_full( $post_id ) {

		$out = array();

		foreach ( topersia_g_gallery_own_ids( $post_id ) as $att ) {
			$out[] = array( 'id' => $att, 'src' => absint( $post_id ) );
		}

		foreach ( topersia_g_gallery_agg_stored( $post_id ) as $row ) {
			$out[] = $row;
		}

		return $out;
	}
}

/**
 * افزوده‌های تجمیعی ذخیره‌شده — همان که بازسازی نوشته؛ هیچ محاسبه‌ای.
 *
 * @return array هر عضو: id · src
 */
if ( ! function_exists( 'topersia_g_gallery_agg_stored' ) ) {
	function topersia_g_gallery_agg_stored( $post_id ) {

		$rows = get_post_meta( topersia_g_gallery_agg_primary( $post_id ), TOPERSIA_GALLERY_AGG_META, true );
		$out  = array();

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( is_array( $row ) && ! empty( $row['id'] ) && ! empty( $row['src'] ) ) {
				$out[] = array( 'id' => absint( $row['id'] ), 'src' => absint( $row['src'] ) );
			}
		}

		/* صاحبان عکس‌ها در صفحه دیده می‌شوند، ولی گالریِ ذخیره‌شده پیمایشی ندارد؛ برچسب کش از همین‌جا.  P5-D372 */
		topersia_g_seen( array_column( $out, 'src' ), 'shown' );

		return $out;
	}
}

/**
 * ساختن افزوده‌های تجمیعی یک پست — فقط در کار پس‌زمینه، WP-CLI یا دکمه.
 *
 * @return array هر عضو: id · src
 */
if ( ! function_exists( 'topersia_g_gallery_agg_build' ) ) {
	function topersia_g_gallery_agg_build( $post_id ) {

		$own  = topersia_g_gallery_own_ids( $post_id );
		$room = TOPERSIA_GALLERY_MAX - count( $own );

		if ( $room <= 0 ) {
			return array();
		}

		$seen  = array_fill_keys( $own, true );
		$lists = array();

		foreach ( topersia_g_gallery_agg_children( $post_id ) as $child ) {
			$lists[] = topersia_g_gallery_agg_full( $child );
		}

		$out = array();

		for ( $round = 0; $round < TOPERSIA_GALLERY_PER_CHILD && $room > 0; $round++ ) {

			foreach ( $lists as $n => $list ) {

				/*
				 * عکسِ «دور»ِ این فرزند: نخستین عکسِ تازه‌ای که هنوز برنداشته‌ایم.
				 * $row هر بار از صفر — وگرنه فرزندِ بی‌عکس عکسِ فرزندِ پیشین را
				 * دوباره می‌افزاید (یافتۀ نخستین «بازسازی همه»: یک عکس ده بار).
				 */
				$row = null;

				while ( $list ) {
					$row = array_shift( $list );
					if ( ! isset( $seen[ $row['id'] ] ) && topersia_g_published( array( $row['src'] ) ) ) {
						break;
					}
					$row = null;
				}

				$lists[ $n ] = $list;

				if ( empty( $row ) ) {
					continue;
				}

				$seen[ $row['id'] ] = true;
				$out[]              = $row;
				$room--;

				if ( $room <= 0 ) {
					break;
				}
			}
		}

		return $out;
	}
}

/**
 * والدهای تجمیعِ یک پست — هر پستی که این پست در گروه تجمیعش است — بی دروازه،
 * چون فرزندِ پیش‌نویس‌شده هم باید عکسش را از والدها بردارد. فقط پیمودن.
 *
 * @return int[]
 */
if ( ! function_exists( 'topersia_g_gallery_agg_parents' ) ) {
	function topersia_g_gallery_agg_parents( $post_id ) {

		$map = topersia_g_relmap();
		$out = array();

		$flip = function ( $spec ) {
			return array( 'id' => (int) $spec['id'], 'dir' => 'children' === $spec['dir'] ? 'parents' : 'children' );
		};

		foreach ( topersia_g_gallery_agg_groups() as $ptype => $keys ) {

			foreach ( $keys as $key ) {

				$group = isset( $map[ $ptype ][ $key ] ) ? $map[ $ptype ][ $key ] : array();
				$specs = isset( $group['rels'] ) ? $group['rels'] : array();

				/* مرکزِ استان جزو «شهرها»ست (P5-D215) */
				if ( 'places' === $key && ! empty( $map[ $ptype ]['capital']['rels'] ) ) {
					$specs = array_merge( $specs, $map[ $ptype ]['capital']['rels'] );
				}

				foreach ( $specs as $spec ) {
					foreach ( topersia_g_rel_ids( $post_id, array( $flip( $spec ) ), false ) as $p ) {
						if ( $ptype === get_post_type( $p ) ) {
							$out[] = absint( $p );
						}
					}
				}

				foreach ( isset( $group['chains'] ) ? $group['chains'] : array() as $chain ) {

					$cur = array( absint( $post_id ) );

					foreach ( array_reverse( $chain ) as $spec ) {
						$next = array();
						foreach ( $cur as $c ) {
							$next = array_merge( $next, topersia_g_rel_ids( $c, array( $flip( $spec ) ), false ) );
						}
						$cur = array_values( array_unique( array_map( 'absint', $next ) ) );
					}

					foreach ( $cur as $p ) {
						if ( $ptype === get_post_type( $p ) ) {
							$out[] = $p;
						}
					}
				}
			}
		}

		return array_values( array_unique( array_filter( $out ) ) );
	}
}

/**
 * پست‌هایی که با تغییرِ این پست باید بازساخته شوند — خودش (اگر نوعِ والد است)
 * و همۀ نیاکان تجمیعی تا کشور. به زبان اصلی.
 *
 * @return int[]
 */
if ( ! function_exists( 'topersia_g_gallery_agg_affected' ) ) {
	function topersia_g_gallery_agg_affected( $post_id ) {

		$groups = topersia_g_gallery_agg_groups();
		$start  = topersia_g_gallery_agg_primary( $post_id );
		$out    = isset( $groups[ (string) get_post_type( $start ) ] ) ? array( $start ) : array();
		$queue  = array( $start );
		$seen   = array( $start => true );

		while ( $queue ) {
			foreach ( topersia_g_gallery_agg_parents( array_shift( $queue ) ) as $p ) {
				$p = topersia_g_gallery_agg_primary( $p );
				if ( ! isset( $seen[ $p ] ) ) {
					$seen[ $p ] = true;
					$out[]      = $p;
					$queue[]    = $p;
				}
			}
		}

		return $out;
	}
}

/** پلۀ هر نوع — بازسازی از پایین به بالا: شهر، سپس استان، سپس کشور. */
if ( ! function_exists( 'topersia_g_gallery_agg_rank' ) ) {
	function topersia_g_gallery_agg_rank( $post_id ) {
		$rank = array( 'tp_city' => 0, 'tp_province' => 1, 'tp_country' => 2 );
		$type = (string) get_post_type( $post_id );
		return isset( $rank[ $type ] ) ? $rank[ $type ] : 0;
	}
}

/**
 * کارهای بازسازی برای این پست و نیاکانش — در صف واحد هسته (P5-D290)، با ادغام
 * تکراری. فاصلۀ سی ثانیه‌ای هر پله ترتیبِ پایین به بالا را نگه می‌دارد.
 *
 * @param bool $self خودِ پست هم؟ `false` فقط نیاکان — پس از بازسازیِ خودش و هنگام
 *                   حذف. پستِ منتشرنشده هم فقط نیاکان: افزوده‌هایش نه نمایش داده
 *                   می‌شوند نه والدی می‌خواندشان؛ انتشار دوباره در صفش می‌گذارد.
 * @return int شمار کارهای تازه
 */
if ( ! function_exists( 'topersia_g_gallery_agg_schedule' ) ) {
	function topersia_g_gallery_agg_schedule( $post_id, $self = true ) {

		if ( ! topersia_g_gallery_agg_enabled() || ! function_exists( 'topersia_queue_add' ) ) {
			return 0;
		}

		$n       = 0;
		$primary = topersia_g_gallery_agg_primary( $post_id );
		$skip    = ( $self && 'publish' === get_post_status( $primary ) ) ? 0 : $primary;

		foreach ( topersia_g_gallery_agg_affected( $post_id ) as $one ) {
			if ( $one !== $skip && topersia_queue_add( 'topersia_gallery_agg_rebuild', array( 'post' => (int) $one ), 30 * topersia_g_gallery_agg_rank( $one ) ) ) {
				$n++;
			}
		}

		return $n;
	}
}

/**
 * بازسازی یک پست — نوشتن متای مشتق روی پست زبان اصلی.
 *
 * فاصلۀ پله‌ها به‌تنهایی ترتیب را تضمین نمی‌کند: کارِ والدی که از تغییر پیشین
 * در صف مانده (ادغام) ممکن است پیش از کارِ فرزند اجرا شود و دادۀ کهنۀ فرزند را
 * بخواند. پس اگر افزوده‌های این پست عوض شد، نیاکانش دوباره در صف می‌روند.
 * ساختن همیشه به زبان اصلی (`topersia_g_gallery_agg_in_default_lang()`).
 *
 * @param bool $cascade نیاکان پس از تغییر؟ «بازسازی همه» و `--post` نه.
 * @return array همان افزوده‌ها
 */
if ( ! function_exists( 'topersia_g_gallery_agg_rebuild' ) ) {
	function topersia_g_gallery_agg_rebuild( $post_id, $cascade = true ) {

		$post_id = topersia_g_gallery_agg_primary( $post_id );
		$groups  = topersia_g_gallery_agg_groups();

		if ( ! $post_id || ! isset( $groups[ (string) get_post_type( $post_id ) ] ) ) {
			return array();
		}

		return topersia_g_gallery_agg_in_default_lang( function () use ( $post_id, $cascade ) {

			$old  = topersia_g_gallery_agg_stored( $post_id );
			$rows = topersia_g_gallery_agg_build( $post_id );

			if ( $rows ) {
				update_post_meta( $post_id, TOPERSIA_GALLERY_AGG_META, $rows );
			} else {
				delete_post_meta( $post_id, TOPERSIA_GALLERY_AGG_META );
			}

			if ( $cascade && $rows !== $old ) {
				topersia_g_gallery_agg_schedule( $post_id, false );
			}

			return $rows;
		} );
	}
}

/**
 * پست‌های منتشرشدۀ زبان اصلی که گالری تجمیعی دارند — به ترتیب بازسازی: شهرها،
 * استان‌ها، کشور. منتشرنشده نه: افزوده‌هایش خوانده نمی‌شود و انتشار در صفش می‌گذارد.
 *
 * @return array نوع ← شناسه‌ها
 */
if ( ! function_exists( 'topersia_g_gallery_agg_all_ids' ) ) {
	function topersia_g_gallery_agg_all_ids() {

		return topersia_g_gallery_agg_in_default_lang( function () {

			$out = array();

			foreach ( array( 'tp_city', 'tp_province', 'tp_country' ) as $type ) {

				$ids = get_posts( array(
					'post_type'        => $type,
					'post_status'      => 'publish',
					'numberposts'      => -1,
					'fields'           => 'ids',
					'suppress_filters' => false,
				) );

				$out[ $type ] = array_values( array_filter( array_map( 'absint', $ids ), function ( $id ) {
					return $id === topersia_g_gallery_agg_primary( $id );
				} ) );
			}

			return $out;
		} );
	}
}

/**
 * «بازسازی همه» هم‌زمان — فقط WP-CLI (`--all`)، بی سقف زمان PHP.
 *
 * @return array نوع ← شمار
 */
if ( ! function_exists( 'topersia_g_gallery_agg_rebuild_all' ) ) {
	function topersia_g_gallery_agg_rebuild_all() {

		$done = array();

		foreach ( topersia_g_gallery_agg_all_ids() as $type => $ids ) {
			foreach ( $ids as $id ) {
				topersia_g_gallery_agg_rebuild( $id, false );
			}
			$done[ $type ] = count( $ids );
		}

		update_option( 'topersia_gallery_agg_last', array( 'time' => time(), 'done' => $done ), false );

		return $done;
	}
}

/**
 * «بازسازی همه» از صف (دکمۀ پیشخوان): یک کار برای هر پست، پله‌به‌پله — نه یک کار
 * بلند که روی میزبانِ کُند به سقف زمان PHP بخورد و نیمه‌کاره بماند.
 *
 * @return int شمار کارهای تازه
 */
if ( ! function_exists( 'topersia_g_gallery_agg_queue_all' ) ) {
	function topersia_g_gallery_agg_queue_all() {

		$n = 0;

		foreach ( topersia_g_gallery_agg_all_ids() as $ids ) {
			foreach ( $ids as $id ) {
				if ( topersia_queue_add( 'topersia_gallery_agg_rebuild', array( 'post' => $id ), 30 * topersia_g_gallery_agg_rank( $id ) ) ) {
					$n++;
				}
			}
		}

		update_option( 'topersia_gallery_agg_last', array( 'time' => time(), 'queued' => $n ), false );

		return $n;
	}
}

/* ── قلاب‌ها ────────────────────────────────────────────────────────── */
if ( function_exists( 'add_action' ) ) {

	add_action( 'topersia_gallery_agg_rebuild', function ( $post ) {
		topersia_g_gallery_agg_rebuild( absint( $post ) );
	} );

	/* کار «بازسازی همه» در صف فقط پخش می‌کند — هر پست کار خودش (topersia_g_gallery_agg_queue_all) */
	add_action( 'topersia_gallery_agg_rebuild_all', 'topersia_g_gallery_agg_queue_all' );

	/* ذخیره و تغییر وضعیت (انتشار، پیش‌نویس، زباله‌دان) */
	$tp_agg_touch = function ( $post_id, $self = true ) {
		$type = (string) get_post_type( $post_id );
		if ( 0 === strpos( $type, 'tp_' ) && ! wp_is_post_revision( $post_id ) && ! wp_is_post_autosave( $post_id ) ) {
			topersia_g_gallery_agg_schedule( $post_id, $self );
		}
	};

	add_action( 'save_post', $tp_agg_touch, 20 );
	add_action( 'transition_post_status', function ( $new, $old, $post ) use ( $tp_agg_touch ) {
		if ( $new !== $old && is_object( $post ) ) {
			$tp_agg_touch( $post->ID );
		}
	}, 20, 3 );

	/* حذف — پیش از رفتن ردیف‌های رابطه، تا والدها هنوز پیدا شوند؛ خودِ پست نه (متایش با آن می‌رود) */
	add_action( 'before_delete_post', function ( $post_id ) use ( $tp_agg_touch ) {
		$tp_agg_touch( $post_id, false );
	}, 5 );

	/* تغییر گالری بی ذخیرۀ پست (درون‌ریزی، WP-CLI) */
	$tp_agg_meta = function ( $meta_id, $post_id, $key ) use ( $tp_agg_touch ) {
		unset( $meta_id );
		if ( 'featured_image_gallery' === $key ) {
			$tp_agg_touch( $post_id );
		}
	};
	add_action( 'added_post_meta', $tp_agg_meta, 20, 3 );
	add_action( 'updated_post_meta', $tp_agg_meta, 20, 3 );
	add_action( 'deleted_post_meta', $tp_agg_meta, 20, 3 );

	/*
	 * ردیف رابطۀ تازه یا حذف‌شده بی ذخیرۀ پست (کنترل رابطۀ ویرایشگر، درون‌ریزی
	 * ۲ب) — هر دو سو. «پیش از» هم: رابطۀ تک‌والد والدِ پیشین را جای‌گزین می‌کند
	 * و آن والد باید عکس فرزند را بردارد. فقط سوی پست (`posts::`)، نه کاربر یا
	 * واژه — شناسۀ آن‌ها پست نیست.
	 */
	$tp_agg_rel = function ( $parent, $child, $rel ) use ( $tp_agg_touch ) {
		if ( ! is_object( $rel ) || ! method_exists( $rel, 'get_args' ) ) {
			return;
		}
		foreach ( array( 'parent_object' => $parent, 'child_object' => $child ) as $side => $one ) {
			if ( $one && is_numeric( $one ) && 0 === strpos( (string) $rel->get_args( $side ), 'posts::' ) ) {
				$tp_agg_touch( absint( $one ) );
			}
		}
	};
	add_action( 'jet-engine/relation/update/before', function ( $parent, $child, $rel ) use ( $tp_agg_rel ) {
		$tp_agg_rel( $parent, $child, $rel );
	}, 20, 3 );
	add_action( 'jet-engine/relation/update/after', function ( $parent, $child, $item, $rel ) use ( $tp_agg_rel ) {
		unset( $item );
		$tp_agg_rel( $parent, $child, $rel );
	}, 20, 4 );
	add_action( 'jet-engine/relation/delete/after', function ( $parent, $child, $clear, $rel ) use ( $tp_agg_rel ) {
		unset( $clear );
		$tp_agg_rel( $parent, $child, $rel );
	}, 20, 4 );

	/* دکمۀ پیشخوان «بازسازی همه» — یک کار در صف، نه اجرای هم‌زمان */
	add_action( 'admin_post_topersia_gallery_agg_rebuild_all', function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی ندارید.', 'topersia' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'topersia_gallery_agg_rebuild_all' );
		$queued = function_exists( 'topersia_queue_add' ) ? topersia_queue_add( 'topersia_gallery_agg_rebuild_all' ) : 0;
		wp_safe_redirect( add_query_arg( 'tp_agg', $queued ? 'queued' : 'pending', wp_get_referer() ? wp_get_referer() : admin_url() ) );
		exit;
	} );
}

/**
 * کارت «گالری تجمیعی» در تنظیمات هستۀ نمایش — «بازسازی همه» یک کار در صف واحد
 * هسته (P5-D290)؛ تا ممیزی فقط محلی (P5-D300). فرم جدا از فرم تنظیمات.
 */
if ( ! function_exists( 'topersia_g_gallery_agg_admin_card' ) ) {
	function topersia_g_gallery_agg_admin_card() {

		if ( ! topersia_g_gallery_agg_enabled() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$last  = get_option( 'topersia_gallery_agg_last' );
		$state = isset( $_GET['tp_agg'] ) ? sanitize_key( wp_unslash( $_GET['tp_agg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- فقط پیام

		echo '<form class="tp-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:16px">'
			. '<h2>' . esc_html__( 'گالری تجمیعی', 'topersia' ) . '</h2>'
			. '<p class="hint">' . esc_html__( 'گالری کشور، استان و شهر از عکس‌های فرزندانشان پر می‌شود و پس از هر تغییر خودکار بازسازی می‌شود. این دکمه همه را از نو می‌سازد.', 'topersia' ) . '</p>';

		if ( '' !== $state ) {
			echo '<p class="note">' . esc_html( 'queued' === $state ? __( 'در صف قرار گرفت.', 'topersia' ) : __( 'از پیش در صف است.', 'topersia' ) ) . '</p>';
		}

		if ( is_array( $last ) && ! empty( $last['time'] ) ) {
			/* translators: %s: تاریخ و ساعت */
			echo '<p class="note">' . esc_html( sprintf( __( 'آخرین بازسازی همه: %s', 'topersia' ), wp_date( 'Y-m-d H:i', (int) $last['time'] ) ) ) . '</p>';
		}

		echo '<input type="hidden" name="action" value="topersia_gallery_agg_rebuild_all">';
		wp_nonce_field( 'topersia_gallery_agg_rebuild_all' );
		submit_button( __( 'بازسازی همه', 'topersia' ), 'secondary', 'submit', false );
		echo '</form>';
	}
}

/* ── WP-CLI ────────────────────────────────────────────────────────── */
if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {

	/**
	 * گالری تجمیعی (P5-D274): بازسازی.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<id>]
	 * : فقط این پست (و نه نیاکانش).
	 *
	 * [--all]
	 * : همۀ شهرها، سپس استان‌ها، سپس کشور.
	 *
	 * [--queue]
	 * : کارهای در انتظار صف را همین حالا اجرا کن — «بازسازی همه»ی در صف، سپس
	 *   بازسازی‌ها تا صف خالی شود (آبشار کار تازه می‌سازد).
	 */
	WP_CLI::add_command( 'topersia gallery-agg', function ( $args, $assoc ) {

		unset( $args );

		if ( ! topersia_g_gallery_agg_enabled() ) {
			WP_CLI::error( __( 'گالری تجمیعی تا ممیزی فقط محلی است (topersia_gallery_agg_enabled).', 'topersia' ) );
		}

		if ( ! empty( $assoc['queue'] ) ) {
			$ran = topersia_queue_run_now( 'topersia_gallery_agg_rebuild_all' );
			for ( $i = 0; $i < 8; $i++ ) {
				$one = topersia_queue_run_now( 'topersia_gallery_agg_rebuild' );
				if ( ! $one ) {
					break;
				}
				$ran += $one;
			}
			/* translators: %d: شمار کارهای اجراشده */
			WP_CLI::success( sprintf( __( 'اجرا: %d', 'topersia' ), $ran ) );
			return;
		}

		if ( ! empty( $assoc['post'] ) ) {
			$rows = topersia_g_gallery_agg_rebuild( absint( $assoc['post'] ), false );
			/* translators: 1: شناسۀ پست، 2: شمار عکس تجمیعی */
			WP_CLI::success( sprintf( __( '#%1$d: %2$d عکس تجمیعی', 'topersia' ), absint( $assoc['post'] ), count( $rows ) ) );
			return;
		}

		if ( ! empty( $assoc['all'] ) ) {
			foreach ( topersia_g_gallery_agg_rebuild_all() as $type => $n ) {
				WP_CLI::log( $type . ': ' . $n );
			}
			WP_CLI::success( __( 'بازسازی همه', 'topersia' ) );
			return;
		}

		WP_CLI::error( __( '--post=<id> یا --all یا --queue', 'topersia' ) );
	} );
}
