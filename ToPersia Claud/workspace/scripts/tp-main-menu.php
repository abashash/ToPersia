<?php
/**
 * دستۀ 55-ب بند ۴ — منوی اصلی سربرگ (#19 «منوی اصلی»): مقصد واقعی و ترجمۀ WPML. فقط topersia.local.
 * دستۀ 55-ه بند ۴: build ساختار سطح اول و زیرمنو را از TP_MM_TOP می‌سازد، سپس همان apply.
 *
 *   php wp-cli.phar … eval-file scripts/tp-main-menu.php                  ← گزارش (فقط خواندن)
 *   php wp-cli.phar … eval-file scripts/tp-main-menu.php check            ← همان؛ کد ۱ اگر منو با هدف یکی نیست (گیت)
 *   php wp-cli.phar … eval-file scripts/tp-main-menu.php apply <out.json> ← نخست رونوشت کامل در out.json، سپس نوشتن
 *   php wp-cli.phar … eval-file scripts/tp-main-menu.php build <out.json> ← همان، با ساختن درخت هدف در #19 پیش از apply
 *   php wp-cli.phar … eval-file scripts/tp-main-menu.php restore <in.json> ← نشانی آیتم‌های فارسی عیناً همان رونوشت؛
 *                                                                           منوی ترجمه‌ای که در رونوشت نبود پاک می‌شود
 *
 * هدف (OVERNIGHT ب۱۰):
 *   مقصد   آیتمی که در TP_MM_HUBS هاب دارد و هابش به زبان آیتم محتوای واقعی دارد (topersia_g_ft_menu_url — همان قاعدۀ «پیوند
 *          واقعی» رجیستری پاورقی، P5-D643) نشانی همان هاب را می‌گیرد؛ وگرنه «#» و قاعدۀ رندر (includes/topersia-g-nav.php)
 *          پنهانش می‌کند. نشانی دستیِ غیر «#» هرگز بازنویسی نمی‌شود.
 *   ترجمه  هر زبان فعال جز پیش‌فرض یک منوی هم‌trid با #19؛ هر آیتم منتشرشدۀ فارسی یک آیتم هم‌trid، عنوان از
 *          scripts/i18n/main-menu.php (بی عنوان در جدول ← آن زبان ساخته نمی‌شود و check می‌افتد)، فرادادۀ وودمارت رونوشت اصل.
 *          WPML بی منوی ترجمه آیتم‌های اصل را با عنوان فارسی می‌دهد (WPML\Core\Menu\Translate) — نشت همین بود.
 * اجرای دوباره هم‌ارز است (فقط اختلاف نوشته می‌شود). خروجی: ۰ سالم · ۱ ناهمخوان · ۲ محیط/ورودی.
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "فقط با wp eval-file\n" );
	exit( 2 );
}
if ( 'topersia.local' !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	WP_CLI::error( 'فقط topersia.local' );
}
foreach ( array( 'topersia_g_ft_menu_url', 'wpml_object_id_filter' ) as $tp_fn ) {
	if ( ! function_exists( $tp_fn ) ) {
		fwrite( STDERR, "تابع $tp_fn نیست — افزونۀ render یا WPML فعال نیست\n" );
		exit( 2 );
	}
}

$tp_args = isset( $args ) ? (array) $args : array();
$tp_mode = isset( $tp_args[0] ) ? (string) $tp_args[0] : 'report';
$tp_file = isset( $tp_args[1] ) ? (string) $tp_args[1] : '';

const TP_MM_LOCATION = 'main-menu';
/**
 * دستۀ 55-ه بند ۴ (P5-D651) — ساختار منوی اصلی: عنوان سطح اول ← [ هاب خودش یا null، گروه‌های رجیستری پاورقی برای زیرمنو،
 * مرتب‌سازی به بیشترین محتوای واقعی ]. زیرمنو فقط هابی که به فارسی محتوای واقعی دارد (topersia_g_ft_menu_url — همان «پیوند
 * واقعی» P5-D643)، حداکثر TP_MM_CHILD_MAX؛ والد بی هاب «#» است و قاعدۀ رندر آن را دکمه می‌کند، یا اگر هیچ فرزندی به آن زبان
 * مقصد ندارد پنهان. «مجله» ← هاب اخبار.
 */
const TP_MM_TOP = array(
	array( 'مقصدها', null, array( 'dest' ), true ),
	array( 'تجربه‌ها', null, array( 'exp', 'events', 'seasons' ), false ),
	array( 'فرهنگ و مردم', null, array( 'culture', 'food' ), false ),
	array( 'راهنمای سفر', null, array( 'guide' ), false ),
	array( 'مجله', array( 'type', 'tpc_news' ), array(), false ),
);
/** زیرمنوی افزوده بیرون از رجیستری پاورقی: «جاذبه‌ها» (هاب گروهی بناها و محوطه‌ها) زیر «مقصدها». */
const TP_MM_EXTRA = array( 'مقصدها' => array( array( 'جاذبه‌ها', 'group', 'attractions' ) ) );
const TP_MM_TOP_MAX   = 6;
const TP_MM_CHILD_MAX = 8;
const TP_MM_LINK_MAX  = 150;
/** آیتم ساختۀ build نشان دارد و هابش «نوع:کلید» در فرادادۀ آیتم است (پیشوند _menu_item_ تا رونوشت و ترجمه همراهش بیاید). */
const TP_MM_META_OWN = '_menu_item_tp_mm';
const TP_MM_META_HUB = '_menu_item_tp_hub';
/** فرادادۀ هسته که wp_update_nav_menu_item خودش می‌نویسد — رونوشت نمی‌شود. */
const TP_MM_CORE_META = array( '_menu_item_type', '_menu_item_menu_item_parent', '_menu_item_object_id', '_menu_item_object', '_menu_item_target', '_menu_item_classes', '_menu_item_xfn', '_menu_item_url' );

$tp_titles = require __DIR__ . '/i18n/main-menu.php';
$tp_fa     = (string) apply_filters( 'wpml_default_language', 'fa' );

/** منوی جای main-menu به زبان پیش‌فرض (نه ترجمه‌ای که WPML جای آن می‌گذارد). */
function tp_mm_menu() {
	$loc = get_theme_mod( 'nav_menu_locations' );
	$id  = is_array( $loc ) && ! empty( $loc[ TP_MM_LOCATION ] ) ? (int) $loc[ TP_MM_LOCATION ] : 0;
	$t   = $id ? get_term( $id, 'nav_menu' ) : null;
	return $t instanceof WP_Term ? $t : null;
}

/** جزئیات زبان WPML یک عنصر. */
function tp_mm_lang( $id, $type ) {
	$d = apply_filters( 'wpml_element_language_details', null, array( 'element_id' => (int) $id, 'element_type' => $type ) );
	return is_object( $d ) ? $d : null;
}

/** ترجمه‌های یک trid: [ زبان => element_id ]. */
function tp_mm_translations( $trid, $type ) {
	$out = array();
	foreach ( (array) apply_filters( 'wpml_get_element_translations', null, (int) $trid, $type ) as $code => $row ) {
		if ( is_object( $row ) && ! empty( $row->element_id ) ) {
			$out[ (string) $code ] = (int) $row->element_id;
		}
	}
	return $out;
}

/** آیتم‌های یک منو (همۀ وضعیت‌ها) با فرادادۀ _menu_item_*، به ترتیب menu_order. */
function tp_mm_items( $menu_id ) {
	$out = array();
	foreach ( (array) wp_get_nav_menu_items( (int) $menu_id, array( 'post_status' => 'any' ) ) as $it ) {
		$meta = array();
		foreach ( (array) get_post_meta( $it->ID ) as $k => $v ) {
			if ( 0 === strpos( (string) $k, '_menu_item_' ) ) {
				$meta[ $k ] = (string) $v[0];
			}
		}
		$out[] = array(
			'ID'     => (int) $it->ID,
			'title'  => (string) $it->post_title,
			'url'    => (string) get_post_meta( $it->ID, '_menu_item_url', true ),
			'status' => (string) $it->post_status,
			'order'  => (int) $it->menu_order,
			'parent' => (int) $it->menu_item_parent,
			'type'   => (string) $it->type,
			'meta'   => $meta,
		);
	}
	return $out;
}

/** هاب آیتم از فرادادۀ build: [ نوع، کلید ] یا null. */
function tp_mm_hub( array $it ) {
	$raw = 'custom' === $it['type'] ? (string) ( $it['meta'][ TP_MM_META_HUB ] ?? '' ) : '';
	return false !== strpos( $raw, ':' ) ? explode( ':', $raw, 2 ) : null;
}

/** واژه‌های «تجربه» (tpc_tag · tp_is_experience) با دست‌کم یک محتوای واقعی به زبان جاری — [ نامک، نام ] به ترتیب tp_sort. */
function tp_mm_exp_tags() {
	if ( ! taxonomy_exists( 'tpc_tag' ) || ! function_exists( 'topersia_g_tag_items' ) ) {
		return array();
	}
	$out = array();
	foreach ( (array) get_terms( array( 'taxonomy' => 'tpc_tag', 'hide_empty' => true, 'meta_query' => array( array( 'key' => 'tp_is_experience', 'value' => '1' ) ) ) ) as $t ) {
		if ( $t instanceof WP_Term && topersia_g_ft_real_count( topersia_g_tag_items( $t->term_id ) ) >= 1 ) {
			$out[] = array( (int) get_term_meta( $t->term_id, 'tp_sort', true ), $t->slug, $t->name );
		}
	}
	usort( $out, function ( $a, $b ) { return $a[0] - $b[0]; } );
	return array_map( function ( $r ) { return array( $r[1], $r[2] ); }, $out );
}

/** نشانی واژۀ تجربه به یک زبان: ترجمۀ WPML همان واژه با دست‌کم یک محتوای واقعی، وگرنه «». */
function tp_mm_tag_url( $slug, $lang, $fa ) {
	do_action( 'wpml_switch_language', $fa );
	$t   = get_term_by( 'slug', $slug, 'tpc_tag' );
	$tid = $t instanceof WP_Term ? (int) apply_filters( 'wpml_object_id', $t->term_id, 'tpc_tag', false, $lang ) : 0;
	do_action( 'wpml_switch_language', $lang );
	if ( ! $tid || topersia_g_ft_real_count( topersia_g_tag_items( $tid ) ) < 1 ) {
		return '';
	}
	$url = get_term_link( $tid, 'tpc_tag' );
	return is_wp_error( $url ) ? '' : (string) $url;
}

/** شمار محتوای واقعی فارسی یک هاب — ترتیب زیرمنوی «مقصدها». */
function tp_mm_count( $kind, $key ) {
	if ( 'type' === $kind ) {
		return topersia_g_ft_type_count( $key );
	}
	$groups = 'group' === $kind && function_exists( 'topersia_g_hub_groups' ) ? topersia_g_hub_groups() : array();
	return array_sum( array_map( 'topersia_g_ft_type_count', isset( $groups[ $key ] ) ? array_values( array_filter( (array) $groups[ $key ]['types'], 'post_type_exists' ) ) : array() ) );
}

/**
 * درخت هدف به فارسی: [ [ عنوان، هاب|null، [ [ عنوان، هاب ] … ] ] … ] — زیرمنو فقط هاب واقعی فارسی، یکتا به عنوان (واژۀ تجربه‌ای
 * که هم‌نام سطری از رجیستری است کنار می‌رود تا نوع جای خودش بماند)، حداکثر TP_MM_CHILD_MAX.
 */
function tp_mm_tree( $fa ) {
	do_action( 'wpml_switch_language', $fa );
	$rows   = topersia_g_ft_registry();
	$labels = array_map( function ( $r ) { return (string) $r['label']; }, $rows );
	$tree   = array();
	foreach ( TP_MM_TOP as $top ) {
		list( $title, $hub, $groups, $by_count ) = $top;
		$kids = array();
		foreach ( $rows as $r ) {
			if ( ! in_array( $r['group'], $groups, true ) ) {
				continue;
			}
			if ( 'exp' === $r['kind'] ) {
				foreach ( tp_mm_exp_tags() as $tag ) {
					if ( ! in_array( $tag[1], $labels, true ) ) {
						$kids[] = array( $tag[1], array( 'tag', $tag[0] ), 0, count( $kids ) );
					}
				}
				continue;
			}
			if ( '' !== (string) topersia_g_ft_menu_url( $r['kind'], $r['key'], $r['min'] ) ) {
				$kids[] = array( (string) $r['label'], array( $r['kind'], $r['key'] ), $by_count ? tp_mm_count( $r['kind'], $r['key'] ) : 0, count( $kids ) );
			}
		}
		foreach ( TP_MM_EXTRA[ $title ] ?? array() as $x ) {
			if ( '' !== (string) topersia_g_ft_menu_url( $x[1], $x[2] ) ) {
				$kids[] = array( $x[0], array( $x[1], $x[2] ), $by_count ? tp_mm_count( $x[1], $x[2] ) : 0, count( $kids ) );
			}
		}
		if ( $by_count ) {
			usort( $kids, function ( $a, $b ) { return $b[2] - $a[2] ?: $a[3] - $b[3]; } );
		}
		$kids   = array_map( function ( $k ) { return array( $k[0], $k[1] ); }, array_slice( $kids, 0, TP_MM_CHILD_MAX ) );
		$tree[] = array( $title, $hub, $kids );
	}
	return $tree;
}

/** نشانی هدف یک آیتم فارسی به یک زبان: آیتم هاب‌دار همیشه از قاعدۀ هاب (نه از نشانی کنونی)، بقیه نشانی خودشان به آن زبان. */
function tp_mm_target_url( array $it, $lang ) {
	$cur = trim( $it['url'] );
	$hub = tp_mm_hub( $it );
	if ( ! $hub ) {
		return '' === $cur || '#' === $cur ? '#' : (string) apply_filters( 'wpml_permalink', $cur, $lang );
	}
	if ( 'tag' === $hub[0] ) {
		$url = tp_mm_tag_url( $hub[1], $lang, (string) apply_filters( 'wpml_default_language', 'fa' ) );
		return '' === $url ? '#' : $url;
	}
	do_action( 'wpml_switch_language', $lang );
	$url = (string) topersia_g_ft_menu_url( $hub[0], $hub[1] );
	return '' === $url ? '#' : $url;
}

/** وضعیت کامل: منو، آیتم‌ها، trid، ترجمه‌ها و هدف هر زبان. */
function tp_mm_state( array $titles, $fa ) {
	$menu = tp_mm_menu();
	if ( ! $menu ) {
		return null;
	}
	$md    = tp_mm_lang( $menu->term_taxonomy_id, 'tax_nav_menu' );
	$items = tp_mm_items( $menu->term_id );
	$langs = array_values( array_diff( array_keys( (array) apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) ) ), array( $fa ) ) );
	$st    = array( 'menu' => $menu, 'trid' => $md ? (int) $md->trid : 0, 'mlang' => $md ? (string) $md->language_code : '', 'items' => $items, 'langs' => $langs, 'tr' => array(), 'target' => array() );
	foreach ( $items as $it ) {
		if ( 'publish' !== $it['status'] ) {
			continue;
		}
		$st['target'][ $fa ][ $it['ID'] ] = tp_mm_target_url( $it, $fa );
		foreach ( $langs as $l ) {
			$st['target'][ $l ][ $it['ID'] ] = tp_mm_target_url( $it, $l );
		}
	}
	do_action( 'wpml_switch_language', $fa );
	foreach ( $st['trid'] ? tp_mm_translations( $st['trid'], 'tax_nav_menu' ) : array() as $code => $tt ) {
		if ( $code === $fa ) {
			continue;
		}
		do_action( 'wpml_switch_language', $code ); // WPML واژه و آیتم را به زبان جاری برمی‌گرداند (adjust id)
		$term = get_term_by( 'term_taxonomy_id', $tt, 'nav_menu' );
		if ( $term instanceof WP_Term && (int) $term->term_taxonomy_id === (int) $tt ) {
			$st['tr'][ $code ] = array( 'term' => $term, 'items' => tp_mm_items( $term->term_id ) );
		}
		do_action( 'wpml_switch_language', $fa );
	}
	return $st;
}

/** ناهمخوانی‌ها با هدف — آرایۀ پیام. */
function tp_mm_problems( array $st, array $titles, $fa ) {
	$bad = array();
	if ( $st['mlang'] !== $fa || ! $st['trid'] ) {
		$bad[] = "منوی {$st['menu']->term_id} زبان $fa یا trid ندارد";
	}
	$pub = array_values( array_filter( $st['items'], function ( $it ) { return 'publish' === $it['status']; } ) );
	/* ساختار (دستۀ 55-ه بند ۴): درخت آیتم‌های build همان درخت هدف، به همان ترتیب و همان هاب؛ سقف‌ها در هر منو */
	$byid = array();
	foreach ( $pub as $it ) {
		$byid[ $it['ID'] ] = $it;
	}
	$have = array();
	foreach ( $pub as $it ) {
		if ( '1' === ( $it['meta'][ TP_MM_META_OWN ] ?? '' ) ) {
			$p      = $it['parent'] && isset( $byid[ $it['parent'] ] ) ? $byid[ $it['parent'] ]['title'] : '';
			$have[] = $p . ' › ' . $it['title'] . ' = ' . ( $it['meta'][ TP_MM_META_HUB ] ?? '' );
		}
	}
	$want = array();
	foreach ( tp_mm_tree( $fa ) as $top ) {
		$want[] = ' › ' . $top[0] . ' = ' . ( $top[1] ? implode( ':', $top[1] ) : '' );
		foreach ( $top[2] as $k ) {
			$want[] = $top[0] . ' › ' . $k[0] . ' = ' . implode( ':', $k[1] );
		}
	}
	do_action( 'wpml_switch_language', $fa );
	if ( $have !== $want ) {
		$bad[] = 'ساختار ≠ هدف: کم [' . implode( ' | ', array_diff( $want, $have ) ) . '] · اضافه [' . implode( ' | ', array_diff( $have, $want ) ) . ']' . ( array_diff( $want, $have ) || array_diff( $have, $want ) ? '' : ' · ترتیب' );
	}
	$caps = array( $fa => $pub );
	foreach ( $st['tr'] as $l => $tr ) {
		$caps[ $l ] = array_values( array_filter( $tr['items'], function ( $ti ) { return 'publish' === $ti['status']; } ) );
	}
	foreach ( $caps as $l => $list ) {
		$kids = array_count_values( array_filter( array_column( $list, 'parent' ) ) );
		$top  = count( array_filter( $list, function ( $x ) { return ! $x['parent']; } ) );
		if ( $top > TP_MM_TOP_MAX || ( $kids && max( $kids ) > TP_MM_CHILD_MAX ) || count( $list ) > TP_MM_LINK_MAX ) {
			$bad[] = "$l: سقف — سطح اول $top (≤ " . TP_MM_TOP_MAX . '), بیشینۀ زیرمنو ' . ( $kids ? max( $kids ) : 0 ) . ' (≤ ' . TP_MM_CHILD_MAX . '), کل ' . count( $list ) . ' (≤ ' . TP_MM_LINK_MAX . ')';
		}
	}
	foreach ( $pub as $it ) {
		$want = $st['target'][ $fa ][ $it['ID'] ];
		if ( trim( $it['url'] ) !== $want && '#' !== $want ) {
			$bad[] = "fa {$it['ID']} «{$it['title']}» نشانی {$it['url']} ≠ هدف $want";
		}
	}
	foreach ( $st['langs'] as $l ) {
		$name = $titles['_menu'][ $l ] ?? '';
		if ( '' === $name ) {
			$bad[] = "$l: نام منو در جدول نیست";
			continue;
		}
		if ( empty( $st['tr'][ $l ] ) ) {
			$bad[] = "$l: منوی ترجمه نیست";
			continue;
		}
		$tr  = $st['tr'][ $l ];
		$byid = array();
		foreach ( $tr['items'] as $ti ) {
			$byid[ $ti['ID'] ] = $ti;
		}
		$seen = array();
		foreach ( $pub as $it ) {
			if ( 'custom' !== $it['type'] ) {
				continue; // آیتم صفحه/نوع را WPML خودش ترجمه می‌کند — apply هم ردش می‌کند
			}
			$t = $titles[ $it['title'] ][ $l ] ?? '';
			if ( '' === $t ) {
				$bad[] = "$l: «{$it['title']}» عنوان در جدول ندارد";
				continue;
			}
			$trid = (int) apply_filters( 'wpml_element_trid', null, $it['ID'], 'post_nav_menu_item' );
			$tid  = $trid ? ( tp_mm_translations( $trid, 'post_nav_menu_item' )[ $l ] ?? 0 ) : 0;
			if ( ! $tid || ! isset( $byid[ $tid ] ) ) {
				$bad[] = "$l: «{$it['title']}» آیتم هم‌trid در منوی {$tr['term']->term_id} ندارد";
				continue;
			}
			$seen[ $tid ] = $it['ID'];
			$ti = $byid[ $tid ];
			if ( $ti['title'] !== $t || trim( $ti['url'] ) !== $st['target'][ $l ][ $it['ID'] ] || 'publish' !== $ti['status'] ) {
				$bad[] = "$l: «{$it['title']}» ← «{$ti['title']}» {$ti['url']} {$ti['status']}؛ هدف «{$t}» {$st['target'][ $l ][ $it['ID'] ]} publish";
			}
		}
		/* ترتیب نسبی همان اصل — menu_order را wp_get_nav_menu_items از نو می‌شمارد (پیش‌نویس اصل هم جا می‌گیرد) */
		$seq = array();
		foreach ( $tr['items'] as $ti ) {
			if ( isset( $seen[ $ti['ID'] ] ) ) {
				$seq[] = $seen[ $ti['ID'] ];
			}
		}
		$want = array_values( array_intersect( array_column( $pub, 'ID' ), $seq ) );
		if ( $seq !== $want ) {
			$bad[] = "$l: ترتیب " . implode( ',', $seq ) . " ≠ اصل " . implode( ',', $want );
		}
		foreach ( $byid as $tid => $ti ) {
			if ( ! isset( $seen[ $tid ] ) && 'publish' === $ti['status'] ) {
				$bad[] = "$l: آیتم اضافۀ {$tid} «{$ti['title']}»";
			}
		}
	}
	return $bad;
}

function tp_mm_print( array $st, array $bad ) {
	echo 'منو ', $st['menu']->term_id, ' «', $st['menu']->name, '» trid ', $st['trid'], ' زبان ', $st['mlang'], ' · زبان‌های دیگر ', implode( ',', $st['langs'] ), "\n";
	foreach ( $st['items'] as $it ) {
		echo '  ', $it['ID'], ' «', $it['title'], '» ', $it['status'], ' ', $it['url'], ' parent ', $it['parent'];
		foreach ( $st['target'] as $l => $t ) {
			if ( isset( $t[ $it['ID'] ] ) ) {
				echo ' · ', $l, ' ', $t[ $it['ID'] ];
			}
		}
		echo "\n";
	}
	foreach ( $st['tr'] as $l => $tr ) {
		echo '  ', $l, ': منو ', $tr['term']->term_id, ' «', $tr['term']->name, '»';
		foreach ( $tr['items'] as $ti ) {
			echo ' · ', $ti['ID'], ' «', $ti['title'], '» ', $ti['url'];
		}
		echo "\n";
	}
	echo $bad ? "ناهمخوان:\n  " . implode( "\n  ", $bad ) . "\n" : "هم‌خوان با هدف\n";
}

$tp_st = tp_mm_state( $tp_titles, $tp_fa );
if ( ! $tp_st ) {
	fwrite( STDERR, "جای main-menu منو ندارد\n" );
	exit( 2 );
}

if ( 'report' === $tp_mode || 'check' === $tp_mode ) {
	$tp_bad = tp_mm_problems( $tp_st, $tp_titles, $tp_fa );
	tp_mm_print( $tp_st, $tp_bad );
	exit( 'check' === $tp_mode && $tp_bad ? 1 : 0 );
}

/** رونوشت: منو، آیتم‌ها و منوهای ترجمۀ کنونی. */
function tp_mm_snapshot( array $st ) {
	$tr = array();
	foreach ( $st['tr'] as $l => $row ) {
		$tr[ $l ] = array( 'term_id' => (int) $row['term']->term_id, 'name' => $row['term']->name, 'items' => $row['items'] );
	}
	return array(
		'made'         => gmdate( 'c' ),
		'menu'         => array( 'term_id' => (int) $st['menu']->term_id, 'tt_id' => (int) $st['menu']->term_taxonomy_id, 'name' => $st['menu']->name, 'trid' => $st['trid'], 'lang' => $st['mlang'] ),
		'items'        => $st['items'],
		'translations' => $tr,
		'locations'    => get_theme_mod( 'nav_menu_locations' ),
	);
}

if ( 'apply' === $tp_mode || 'build' === $tp_mode ) {
	if ( 'build' === $tp_mode ) {
		/* پیش از هر نوشتن: هر عنوان درخت هدف در جدول هر سه زبان — وگرنه هیچ چیز نوشته نمی‌شود */
		$tp_tree = tp_mm_tree( $tp_fa );
		$tp_miss = array();
		foreach ( $tp_tree as $tp_top ) {
			foreach ( array_merge( array( $tp_top[0] ), array_column( $tp_top[2], 0 ) ) as $tp_t ) {
				foreach ( array( 'en', 'ar', 'ru' ) as $tp_l ) {
					if ( '' === ( $tp_titles[ $tp_t ][ $tp_l ] ?? '' ) ) {
						$tp_miss[] = "«{$tp_t}» $tp_l";
					}
				}
			}
		}
		if ( $tp_miss ) {
			fwrite( STDERR, 'عنوان در scripts/i18n/main-menu.php نیست: ' . implode( '، ', $tp_miss ) . " — چیزی نوشته نشد\n" );
			exit( 2 );
		}
	}
	if ( '' === $tp_file || file_exists( $tp_file ) ) {
		fwrite( STDERR, "پروندۀ رونوشت تهی یا موجود\n" );
		exit( 2 );
	}
	$tp_snap = wp_json_encode( tp_mm_snapshot( $tp_st ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( ! $tp_snap || false === file_put_contents( $tp_file, $tp_snap ) || file_get_contents( $tp_file ) !== $tp_snap ) {
		fwrite( STDERR, "رونوشت نوشته نشد — دست نمی‌زنم\n" );
		exit( 2 );
	}
	echo 'رونوشت ', $tp_file, ' sha1 ', sha1_file( $tp_file ), "\n";
	if ( $tp_st['mlang'] !== $tp_fa || ! $tp_st['trid'] ) {
		fwrite( STDERR, "منوی اصلی زبان پیش‌فرض یا trid ندارد\n" );
		exit( 1 );
	}

	if ( 'build' === $tp_mode ) {
		/* درخت هدف در #19: آیتم build هم‌نام به‌روز می‌شود، کم‌بوده ساخته (نشانی «#»؛ apply پایین نشانی هاب را می‌نشاند)، build
		 * بیرون از هدف با ترجمه‌هایش پاک؛ آیتم دستی (بی نشان) دست نمی‌خورد و پیش از درخت می‌ماند */
		$tp_mid  = (int) $tp_st['menu']->term_id;
		$tp_own  = array();
		$tp_last = 0;
		foreach ( $tp_st['items'] as $it ) {
			if ( '1' === ( $it['meta'][ TP_MM_META_OWN ] ?? '' ) ) {
				$tp_own[ $it['title'] ] = $it;
			} else {
				$tp_last = max( $tp_last, $it['order'] );
			}
		}
		$tp_keep = array();
		$tp_pos  = $tp_last;
		$tp_put  = function ( $title, $hub, $parent ) use ( &$tp_own, &$tp_keep, &$tp_pos, $tp_mid ) {
			$old  = $tp_own[ $title ] ?? null;
			$args = array(
				'menu-item-title'     => $title,
				'menu-item-url'       => '#', // نشانی هاب را apply پایین از topersia_g_ft_menu_url می‌نشاند
				'menu-item-type'      => 'custom',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => ++$tp_pos,
				'menu-item-parent-id' => $parent,
			);
			$db = wp_update_nav_menu_item( $tp_mid, $old ? (int) $old['ID'] : 0, $args );
			if ( is_wp_error( $db ) || ! $db ) {
				fwrite( STDERR, "fa: آیتم «{$title}» نوشته نشد\n" );
				exit( 1 );
			}
			update_post_meta( (int) $db, TP_MM_META_OWN, '1' );
			update_post_meta( (int) $db, TP_MM_META_HUB, $hub ? implode( ':', $hub ) : '' );
			$tp_keep[ (int) $db ] = true;
			echo 'fa: ', $old ? 'به‌روز' : 'تازه', " $db «{$title}»", $parent ? " زیر $parent" : '', $hub ? ' ← ' . implode( ':', $hub ) : '', "\n";
			return (int) $db;
		};
		foreach ( $tp_tree as $tp_top ) {
			$tp_pid = $tp_put( $tp_top[0], $tp_top[1], 0 );
			foreach ( $tp_top[2] as $tp_k ) {
				$tp_put( $tp_k[0], $tp_k[1], $tp_pid );
			}
		}
		foreach ( $tp_own as $it ) {
			if ( isset( $tp_keep[ $it['ID'] ] ) ) {
				continue;
			}
			$trid = (int) apply_filters( 'wpml_element_trid', null, $it['ID'], 'post_nav_menu_item' );
			foreach ( $trid ? tp_mm_translations( $trid, 'post_nav_menu_item' ) : array( $tp_fa => $it['ID'] ) as $code => $eid ) {
				wp_delete_post( (int) $eid, true );
				echo "$code: آیتم بیرون از هدف $eid «{$it['title']}» پاک شد\n";
			}
		}
		$tp_st = tp_mm_state( $tp_titles, $tp_fa );
	}

	$tp_pub =array_values( array_filter( $tp_st['items'], function ( $it ) { return 'publish' === $it['status']; } ) );
	foreach ( $tp_pub as $it ) {
		$want = $tp_st['target'][ $tp_fa ][ $it['ID'] ];
		if ( '#' !== $want && in_array( trim( $it['url'] ), array( '', '#' ), true ) ) {
			update_post_meta( $it['ID'], '_menu_item_url', esc_url_raw( $want ) );
			echo "fa {$it['ID']} «{$it['title']}» ← $want\n";
		}
		if ( ! apply_filters( 'wpml_element_trid', null, $it['ID'], 'post_nav_menu_item' ) ) {
			do_action( 'wpml_set_element_language_details', array( 'element_id' => $it['ID'], 'element_type' => 'post_nav_menu_item', 'trid' => false, 'language_code' => $tp_fa, 'source_language_code' => null ) );
		}
	}

	foreach ( $tp_st['langs'] as $l ) {
		$name = $tp_titles['_menu'][ $l ] ?? '';
		$miss = array_filter( $tp_pub, function ( $it ) use ( $tp_titles, $l ) { return '' === ( $tp_titles[ $it['title'] ][ $l ] ?? '' ); } );
		if ( '' === $name || $miss ) {
			echo "$l: جدول عنوان کامل نیست — ساخته نشد\n";
			continue;
		}
		/* دستۀ 55-ه: در بافت fa، WPML شناسۀ منوی ترجمه (77/78) را به #19 برمی‌گرداند (adjust id) و آیتم ترجمه در منوی فارسی
		 * می‌نشست — پیش از نوشتن به زبان همان منو، و پس از هر نوشتن عضویت آیتم در همان منو سنجیده می‌شود */
		do_action( 'wpml_switch_language', $l );
		if ( empty( $tp_st['tr'][ $l ] ) ) {
			$mid = wp_create_nav_menu( $name );
			if ( is_wp_error( $mid ) ) {
				fwrite( STDERR, "$l: منو ساخته نشد — " . $mid->get_error_message() . "\n" );
				exit( 1 );
			}
			$tt = (int) get_term( (int) $mid, 'nav_menu' )->term_taxonomy_id;
			do_action( 'wpml_set_element_language_details', array( 'element_id' => $tt, 'element_type' => 'tax_nav_menu', 'trid' => $tp_st['trid'], 'language_code' => $l, 'source_language_code' => $tp_fa ) );
			echo "$l: منوی $mid «{$name}»\n";
		} else {
			$mid = (int) $tp_st['tr'][ $l ]['term']->term_id;
		}
		$map = array();
		foreach ( $tp_pub as $it ) {
			if ( 'custom' !== $it['type'] ) {
				echo "$l: «{$it['title']}» از نوع {$it['type']} است — WPML خودش ترجمه می‌کند؛ رد شد\n";
				continue;
			}
			$trid = (int) apply_filters( 'wpml_element_trid', null, $it['ID'], 'post_nav_menu_item' );
			$tid  = tp_mm_translations( $trid, 'post_nav_menu_item' )[ $l ] ?? 0;
			$args = array(
				'menu-item-title'     => $tp_titles[ $it['title'] ][ $l ],
				'menu-item-url'       => $tp_st['target'][ $l ][ $it['ID'] ],
				'menu-item-type'      => 'custom',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $it['order'],
				'menu-item-parent-id' => $it['parent'] ? (int) ( $map[ $it['parent'] ] ?? 0 ) : 0,
			);
			$db = wp_update_nav_menu_item( $mid, (int) $tid, $args );
			if ( is_wp_error( $db ) || ! $db ) {
				fwrite( STDERR, "$l: آیتم «{$it['title']}» نوشته نشد\n" );
				exit( 1 );
			}
			$in = array_map( 'intval', (array) wp_get_object_terms( (int) $db, 'nav_menu', array( 'fields' => 'ids' ) ) );
			if ( array( (int) $mid ) !== $in ) {
				fwrite( STDERR, "$l: آیتم $db «{$it['title']}» در منوی " . implode( ',', $in ) . " نشست، نه $mid — توقف\n" );
				exit( 1 );
			}
			do_action( 'wpml_set_element_language_details', array( 'element_id' => (int) $db, 'element_type' => 'post_nav_menu_item', 'trid' => $trid, 'language_code' => $l, 'source_language_code' => $tp_fa ) );
			foreach ( $it['meta'] as $k => $v ) {
				if ( ! in_array( $k, TP_MM_CORE_META, true ) ) {
					update_post_meta( (int) $db, $k, $v );
				}
			}
			$map[ $it['ID'] ] = (int) $db;
			echo "$l: ", $tid ? 'به‌روز' : 'تازه', " $db «{$args['menu-item-title']}» {$args['menu-item-url']}\n";
		}
	}
	do_action( 'wpml_switch_language', $tp_fa );
	wp_cache_flush(); // ترجمه‌های همین اجرا در حافظۀ WPML کهنه می‌ماند و سنجش پایانی «هم‌trid ندارد» می‌دید
	$tp_after = tp_mm_state( $tp_titles, $tp_fa );
	$tp_bad   = tp_mm_problems( $tp_after, $tp_titles, $tp_fa );
	tp_mm_print( $tp_after, $tp_bad );
	exit( $tp_bad ? 1 : 0 );
}

if ( 'restore' === $tp_mode ) {
	$tp_in = '' !== $tp_file && is_file( $tp_file ) ? json_decode( (string) file_get_contents( $tp_file ), true ) : null;
	if ( ! is_array( $tp_in ) || empty( $tp_in['items'] ) || (int) ( $tp_in['menu']['term_id'] ?? 0 ) !== (int) $tp_st['menu']->term_id ) {
		fwrite( STDERR, "رونوشت خوانا نیست یا از این منو نیست\n" );
		exit( 2 );
	}
	/* آیتم ساختۀ build که در رونوشت نبود، با ترجمه‌هایش (آیتم build پاک‌شده را فقط پشتیبان کامل پایگاه‌داده برمی‌گرداند) */
	$tp_was = array();
	foreach ( array_merge( array( $tp_in['items'] ), array_column( (array) ( $tp_in['translations'] ?? array() ), 'items' ) ) as $tp_list ) {
		foreach ( (array) $tp_list as $it ) {
			$tp_was[ (int) $it['ID'] ] = true;
		}
	}
	foreach ( $tp_st['items'] as $it ) {
		if ( '1' !== ( $it['meta'][ TP_MM_META_OWN ] ?? '' ) || isset( $tp_was[ $it['ID'] ] ) ) {
			continue;
		}
		$trid = (int) apply_filters( 'wpml_element_trid', null, $it['ID'], 'post_nav_menu_item' );
		foreach ( $trid ? tp_mm_translations( $trid, 'post_nav_menu_item' ) : array( $tp_fa => $it['ID'] ) as $code => $eid ) {
			if ( ! isset( $tp_was[ (int) $eid ] ) ) {
				wp_delete_post( (int) $eid, true );
				echo "$code: آیتم build $eid «{$it['title']}» پاک شد\n";
			}
		}
	}
	foreach ( $tp_in['items'] as $it ) {
		if ( get_post( (int) $it['ID'] ) && (string) get_post_meta( (int) $it['ID'], '_menu_item_url', true ) !== (string) $it['url'] ) {
			update_post_meta( (int) $it['ID'], '_menu_item_url', (string) $it['url'] );
			echo "fa {$it['ID']} ← {$it['url']}\n";
		}
	}
	foreach ( $tp_st['tr'] as $l => $tr ) {
		if ( ! isset( $tp_in['translations'][ $l ] ) ) {
			wp_delete_nav_menu( (int) $tr['term']->term_id );
			echo "$l: منوی {$tr['term']->term_id} (ساختۀ apply) پاک شد\n";
		}
	}
	$tp_after = tp_mm_state( $tp_titles, $tp_fa );
	tp_mm_print( $tp_after, tp_mm_problems( $tp_after, $tp_titles, $tp_fa ) );
	exit( 0 );
}

fwrite( STDERR, "حالت ناشناخته: $tp_mode\n" );
exit( 2 );
