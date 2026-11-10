<?php
/**
 * دستۀ 52 بند ۱۱ — P5-D567: نوع محتوای «مقالۀ گردشگری» `tpc_article`، هم‌مدل `tpc_news`. فقط محلی (topersia.local).
 *
 *   php scripts/tp-backup.php --no-prune
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-local-52-articles.php [report|model|demo]
 *
 * model — همان ذخیره‌ای که رابط JetEngine می‌کند (همان روش scripts/tp-local-430.php برای خبر):
 *   • نوع پست tpc_article (نامک بازنویسی articles، آرشیو روشن، Title · Editor · Thumbnail · Excerpt)
 *   • متاباکس مشترک meta-1 (short_summary · ai_summary · source_references) + متاباکس «مقاله»:
 *       article_author_name   نویسنده/ناشر (text)        WPML Translate
 *       article_updated_date  تاریخ به‌روزرسانی (date)   WPML Copy
 *     بی فیلدهای منبع خبر (news_source_*).
 *   • چهار رابطۀ چندبه‌چند tpc_article → استان · شهر · روستا · جزیره (بی متای یال)
 *   • WPML: نوع ترجمه‌پذیر (فقط ترجمه‌شده‌ها)؛ `_tp_article_ancestor` Ignore
 *   موضوع مشترک tpc_news_topic و برچسب tpc_tag را کد ثبت می‌کند (render/core)، نه این‌جا.
 * demo  — ۸ مقالۀ نمایشی fa (`_tp_demo` · `_tp_sample` · `_tp_demo_key` = article-N) روی موضوع‌ها؛ پاک‌کردن با scripts/tp-demo-purge.php.
 *
 * نام‌گذاری هم‌راستا با docs/ops/STRUCTURE-LOCK.json: پیشوند `tpc_` برای نوع محتوا، فیلد snake_case با پیشوند نوع، متای مشتق `_tp_*`.
 * پیش از زنده: ممیزی NotebookLM.
 */
if ( 'local' !== wp_get_environment_type() || 'topersia.local' !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	echo "فقط محلی\n";
	return;
}

global $wpdb;
$cmd   = isset( $args[0] ) ? (string) $args[0] : 'report';
$table = $wpdb->prefix . 'jet_post_types';
$line  = function ( $s ) { echo '  ' . $s . "\n"; };

$cpt_id = (int) $wpdb->get_var( "SELECT id FROM {$table} WHERE slug = 'tpc_article' AND status = 'publish'" );
$places = array(
	'tp_province' => array( 'article_g3', 'استان‌های مرتبط', 'انتخاب استان' ),
	'tp_city'     => array( 'article_g5', 'شهرهای مرتبط', 'انتخاب شهر' ),
	'tp_village'  => array( 'article_g7', 'روستاهای مرتبط', 'انتخاب روستا' ),
	'tp_island'   => array( 'article_g8', 'جزیره‌های مرتبط', 'انتخاب جزیره' ),
);
$rel_of = function ( $type ) use ( $wpdb, $table ) {
	foreach ( (array) $wpdb->get_results( "SELECT id, args FROM {$table} WHERE status = 'relation'", ARRAY_A ) as $row ) {
		$a = maybe_unserialize( $row['args'] );
		if ( is_array( $a ) && 'posts::tpc_article' === ( $a['parent_object'] ?? '' ) && 'posts::' . $type === ( $a['child_object'] ?? '' ) ) {
			return (int) $row['id'];
		}
	}
	return 0;
};

if ( 'report' === $cmd ) {
	$line( 'نوع پست tpc_article: ' . ( $cpt_id ? '#' . $cpt_id : 'نیست' ) );
	foreach ( $places as $type => $l ) {
		$line( "رابطه → {$type}: " . ( $rel_of( $type ) ? '#' . $rel_of( $type ) : 'نیست' ) );
	}
	$line( 'مقالۀ نمایشی: ' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_tp_demo_key' AND meta_value LIKE 'article-%'" ) );
	echo "فقط گزارش — model سپس demo.\n";
	return;
}

if ( 'model' === $cmd ) {

	/* ── نوع پست ── */
	if ( ! $cpt_id ) {
		$labels = array(
			'name' => 'مقاله', 'singular_name' => 'مقاله', 'menu_name' => 'مقاله‌ها', 'name_admin_bar' => 'مقاله',
			'add_new' => 'افزودن تازه', 'add_new_item' => 'افزودن مقالۀ تازه', 'new_item' => 'مقالۀ تازه',
			'edit_item' => 'ویرایش مقاله', 'view_item' => 'نمایش مقاله', 'all_items' => 'همۀ مقاله‌ها',
			'search_items' => 'جست‌وجوی مقاله', 'not_found' => 'مقاله‌ای یافت نشد', 'not_found_in_trash' => 'مقاله‌ای در زباله‌دان یافت نشد',
			'archives' => 'بایگانی مقاله‌ها',
		);
		$cargs = array(
			'public' => true, 'publicly_queryable' => true, 'show_ui' => true, 'show_in_menu' => true,
			'show_in_nav_menus' => true, 'show_in_rest' => true, 'query_var' => true, 'rewrite' => true,
			'map_meta_cap' => false, 'has_archive' => true, 'hierarchical' => false, 'exclude_from_search' => false,
			'with_front' => false, 'show_edit_link' => false, 'custom_storage' => false, 'hide_field_names' => false,
			'delete_metadata' => false, 'rewrite_slug' => 'articles', 'capability_type' => 'post', 'menu_position' => -1,
			'menu_icon' => 'dashicons-media-document', 'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'admin_columns' => array(), 'admin_filters' => array(),
		);
		$wpdb->insert( $table, array( 'slug' => 'tpc_article', 'status' => 'publish', 'labels' => maybe_serialize( $labels ), 'args' => maybe_serialize( $cargs ), 'meta_fields' => maybe_serialize( array() ) ) );
		$line( '#' . (int) $wpdb->insert_id . ' نوع پست tpc_article — نامک articles، آرشیو روشن' );
	} else {
		$line( "#{$cpt_id} نوع پست tpc_article بود" );
	}

	/* ── متاباکس‌ها ── */
	$boxes = get_option( 'jet_engine_meta_boxes', array() );

	if ( isset( $boxes['meta-1']['args']['allowed_post_type'] ) && ! in_array( 'tpc_article', $boxes['meta-1']['args']['allowed_post_type'], true ) ) {
		$boxes['meta-1']['args']['allowed_post_type'][] = 'tpc_article';
		$line( 'متاباکس مشترک (meta-1) به tpc_article وصل شد — short_summary · ai_summary · source_references' );
	}

	$found = '';
	foreach ( $boxes as $key => $box ) {
		if ( isset( $box['args']['allowed_post_type'] ) && array( 'tpc_article' ) === array_values( (array) $box['args']['allowed_post_type'] ) ) {
			$found = $key;
		}
	}
	if ( '' === $found ) {
		$n = 1;
		while ( isset( $boxes[ 'meta-' . $n ] ) ) { $n ++; }
		$found           = 'meta-' . $n;
		$boxes[ $found ] = array(
			'args'        => array(
				'object_type' => 'post', 'allowed_tax' => array(), 'allowed_post_type' => array( 'tpc_article' ),
				'allowed_posts' => array(), 'excluded_posts' => array(), 'active_conditions' => array(),
				'position' => 'normal', 'priority' => 'high', 'name' => 'مقاله — نویسنده و به‌روزرسانی', 'hide_field_names' => true,
			),
			'meta_fields' => array(
				array( 'type' => 'text', 'options' => array(), 'repeater-fields' => array(), 'id' => 9201, 'title' => 'نویسنده / ناشر',
					'name' => 'article_author_name', 'object_type' => 'field', 'width' => '50%', 'isNested' => false, 'is_required' => false, 'show_in_rest' => true ),
				array( 'type' => 'date', 'options' => array(), 'repeater-fields' => array(), 'id' => 9202, 'title' => 'تاریخ به‌روزرسانی',
					'name' => 'article_updated_date', 'object_type' => 'field', 'width' => '50%', 'isNested' => false, 'is_required' => false, 'show_in_rest' => true ),
			),
			'id'          => $found,
		);
		$line( "{$found} «مقاله — نویسنده و به‌روزرسانی»: article_author_name · article_updated_date" );
	} else {
		$line( "{$found} متاباکس مقاله بود" );
	}
	update_option( 'jet_engine_meta_boxes', $boxes );

	/* ── چهار رابطه ── */
	foreach ( $places as $type => $l ) {
		$have = $rel_of( $type );
		if ( $have ) {
			$line( "رابطۀ #{$have} tpc_article → {$type} بود" );
			continue;
		}
		$labels = array(
			'name' => $l[0], 'parent_page_control_title' => $l[1], 'parent_page_control_connect' => 'افزودن ' . str_replace( 'انتخاب ', '', $l[2] ),
			'parent_page_control_select' => $l[2], 'child_page_control_title' => 'مقاله', 'child_page_control_connect' => 'افزودن مقاله',
			'child_page_control_select' => 'انتخاب مقاله',
		);
		$rargs = array(
			'db_table' => false, 'parent_control' => true, 'child_control' => true, 'parent_manager' => false, 'child_manager' => false,
			'parent_allow_delete' => false, 'child_allow_delete' => false, 'is_legacy' => false, 'rest_get_enabled' => false,
			'rest_post_enabled' => false, 'parents_required' => false, 'children_required' => false, 'name' => '',
			'parent_object' => 'posts::tpc_article', 'child_object' => 'posts::' . $type, 'parent_rel' => null,
			'type' => 'many_to_many', 'legacy_id' => '', 'rest_get_access' => '', 'rest_post_access' => 'manage_options',
		);
		$wpdb->insert( $table, array( 'slug' => '', 'status' => 'relation', 'labels' => maybe_serialize( $labels ), 'args' => maybe_serialize( $rargs ), 'meta_fields' => maybe_serialize( array() ) ) );
		$line( 'رابطۀ #' . (int) $wpdb->insert_id . " tpc_article → {$type} (چندبه‌چند، {$l[0]})" );
	}

	/* ── WPML — مثل خبر ── */
	$s = get_option( 'icl_sitepress_settings' );
	if ( is_array( $s ) ) {
		$s['custom_posts_sync_option']['tpc_article']     = 1;   /* Translatable — only show translated */
		$s['custom_posts_unlocked_option']['tpc_article'] = 0;
		$s['translation-management']['custom_fields_translation']['article_author_name']  = 2;   /* Translate */
		$s['translation-management']['custom_fields_translation']['article_updated_date'] = 1;   /* Copy */
		$s['translation-management']['custom_fields_translation']['_tp_article_ancestor'] = 0;   /* Ignore */
		update_option( 'icl_sitepress_settings', $s );
		$line( 'WPML: tpc_article ترجمه‌پذیر · article_author_name Translate · article_updated_date Copy · _tp_article_ancestor Ignore' );
	}

	delete_option( 'topersia_news_rules' );   /* قاعده‌های بازنویسی از نو در بارگذاری بعد */
	delete_option( 'topersia_hub_rules' );
	echo "مدل ساخته شد. یک بار صفحه‌ای را باز کن (قاعده‌های بازنویسی)، سپس demo.\n";
	return;
}

if ( 'demo' === $cmd ) {

	if ( ! post_type_exists( 'tpc_article' ) ) {
		echo "tpc_article ثبت نشده — نخست model.\n";
		return;
	}

	$topics = get_terms( array( 'taxonomy' => 'tpc_news_topic', 'hide_empty' => false, 'lang' => 'fa', 'orderby' => 'term_id' ) );
	$topics = is_array( $topics ) ? array_values( $topics ) : array();
	$fa     = function ( $n ) { return str_replace( range( 0, 9 ), array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ), (string) $n ); };
	$note   = 'این یک نمونۀ نمایشی برای دیدن ساختار صفحه است و هیچ ادعای واقعی ندارد. پیش از انتشار سایت پاک می‌شود.';
	$made   = 0;

	for ( $i = 1; $i <= 8; $i++ ) {
		$key = 'article-' . $i;
		if ( (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tp_demo_key' AND meta_value = %s LIMIT 1", $key ) ) ) {
			continue;
		}
		$t    = $topics ? $topics[ ( $i - 1 ) % count( $topics ) ] : null;
		$date = gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( 3 + ( $i - 1 ) * 9 ) . ' days 09:00' ) );
		$id   = wp_insert_post( array(
			'post_type'     => 'tpc_article',
			'post_status'   => 'publish',
			'post_title'    => 'مقالۀ نمونۀ ' . $fa( $i ) . ( $t ? ' — ' . $t->name : '' ),
			'post_content'  => '<p>' . $note . '</p><p>' . $note . '</p>',
			'post_name'     => 'demo-' . $key,
			'post_date'     => $date,
			'post_date_gmt' => get_gmt_from_date( $date ),
		), true );
		if ( ! $id || is_wp_error( $id ) ) {
			echo "خطا: {$key}\n";
			continue;
		}
		$id = (int) $id;
		do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_tpc_article', 'trid' => false, 'language_code' => 'fa', 'source_language_code' => null ) );
		update_post_meta( $id, '_tp_demo', '1' );
		update_post_meta( $id, '_tp_sample', '1' );
		update_post_meta( $id, '_tp_demo_key', $key );
		update_post_meta( $id, 'rank_math_robots', array( 'noindex', 'nofollow' ) );
		update_post_meta( $id, 'short_summary', 'خلاصۀ کوتاه نمونه برای دیدن چیدمان کارت مقاله.' );
		update_post_meta( $id, 'article_author_name', 'نمونه' );
		update_post_meta( $id, 'article_updated_date', gmdate( 'Y-m-d', strtotime( $date . ' +2 days' ) ) );
		if ( $t ) {
			wp_set_object_terms( $id, array( (int) $t->term_id ), 'tpc_news_topic' );
		}
		$made ++;
		$line( "#{$id} {$key}" );
	}
	echo "ساخته شد: {$made} مقالۀ نمایشی.\n";
	return;
}

echo "دستور ناشناس — report | model | demo\n";
