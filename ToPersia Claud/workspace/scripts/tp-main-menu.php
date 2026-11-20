<?php
/**
 * دستۀ 55-ب بند ۴ — منوی اصلی سربرگ (#19 «منوی اصلی»): مقصد واقعی و ترجمۀ WPML. فقط topersia.local.
 *
 *   php wp-cli.phar … eval-file scripts/tp-main-menu.php                  ← گزارش (فقط خواندن)
 *   php wp-cli.phar … eval-file scripts/tp-main-menu.php check            ← همان؛ کد ۱ اگر منو با هدف یکی نیست (گیت)
 *   php wp-cli.phar … eval-file scripts/tp-main-menu.php apply <out.json> ← نخست رونوشت کامل در out.json، سپس نوشتن
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
/** عنوان فارسی ← [ نوع، کلید ] برای topersia_g_ft_menu_url. «مجله» ← اخبار: بایگانی مقاله‌ها امروز محتوای واقعی ندارد (ب۱۰). */
const TP_MM_HUBS = array(
	'جاذبه‌ها' => array( 'group', 'attractions' ),
	'رویدادها' => array( 'group', 'events' ),
	'مجله'     => array( 'type', 'tpc_news' ),
);
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

/** نشانی هدف یک آیتم فارسی به یک زبان: آیتم هاب‌دار همیشه از قاعدۀ هاب (نه از نشانی کنونی)، بقیه نشانی خودشان به آن زبان. */
function tp_mm_target_url( array $it, $lang ) {
	$cur = trim( $it['url'] );
	$hub = 'custom' === $it['type'] ? ( TP_MM_HUBS[ $it['title'] ] ?? null ) : null;
	if ( ! $hub ) {
		return '' === $cur || '#' === $cur ? '#' : (string) apply_filters( 'wpml_permalink', $cur, $lang );
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

if ( 'apply' === $tp_mode ) {
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

	$tp_pub = array_values( array_filter( $tp_st['items'], function ( $it ) { return 'publish' === $it['status']; } ) );
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
