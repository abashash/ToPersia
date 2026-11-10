<?php
/**
 * دستۀ 51 الحاقیۀ ث — P5-D555: برچسب مشترک tpc_tag روی نسخهٔ محلی.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-local-51-tags.php [report|apply|rollback <پشتیبان>]
 *
 * بی apply فقط گزارش. کارها:
 *   ۱. WPML: tpc_tag «ترجمه‌پذیر» (taxonomies_sync_option = 1 و ردیف <taxonomy translate="1"> در wpml-tm-custom-xml)
 *   ۲. هشت واژۀ تجربه در fa · en · ar (tp_is_experience = 1، tp_icon، tp_sort)؛ نامک هر زبان جدا
 *   ۳. نگاشت فقط با تطبیق بی‌ابهام: نوع پست یا واژۀ صریح در عنوان فارسی؛ ترجمه‌ها واژۀ همان زبان
 * پیش از apply پشتیبان JSON بیرون از ریشۀ وب (C:/wamp64/tp-backups/)؛ برگشت: rollback <پشتیبان>.
 * فقط پایگاه‌دادۀ محلی — زنده پس از ممیزی NotebookLM.
 */
if ( 'local' !== wp_get_environment_type() || 'topersia.local' !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	echo "فقط محلی\n";
	return;
}
if ( ! taxonomy_exists( 'tpc_tag' ) ) {
	echo "tpc_tag ثبت نشده (هسته)\n";
	return;
}

global $wpdb, $sitepress;
$cmd   = isset( $args[0] ) ? (string) $args[0] : 'report';
$apply = 'apply' === $cmd;
$bdir  = dirname( untrailingslashit( ABSPATH ), 3 ) . '/tp-backups/';
$log   = function ( $s ) { echo '  ' . $s . "\n"; };
$langs = array( 'fa', 'en', 'ar' );

/* واژه‌ها — [ نامک, آیکون, ترتیب, fa, en, ar ] */
$terms = array(
	array( 'skiing', 'cloud', 10, 'اسکی', 'Skiing', 'التزلج' ),
	array( 'mountaineering', 'mount', 20, 'کوهنوردی', 'Mountaineering', 'تسلق الجبال' ),
	array( 'camping', 'tent', 30, 'کمپینگ', 'Camping', 'التخييم' ),
	array( 'nature-tourism', 'leaf', 40, 'طبیعت‌گردی', 'Nature tourism', 'السياحة الطبيعية' ),
	array( 'local-cuisine', 'heart', 50, 'غذاهای محلی', 'Local food', 'الأطعمة المحلية' ),
	array( 'bazaar-tours', 'bag', 60, 'بازارگردی', 'Bazaar tours', 'جولات الأسواق' ),
	array( 'photography', 'image', 70, 'عکاسی', 'Photography', 'التصوير الفوتوغرافي' ),
	array( 'desert-tours', 'compass', 80, 'کویرگردی', 'Desert tours', 'رحلات الصحراء' ),
);

/* نگاشت بی‌ابهام: نوع پست ← نامک؛ واژۀ عنوان فارسی ← نامک. کمپینگ و عکاسی قاعدۀ بی‌ابهام ندارند. */
$by_type  = array(
	'tp_desert'          => 'desert-tours',
	'tp_mountain'        => 'mountaineering',
	'tp_local_food'      => 'local-cuisine',
	'tp_forest_plain'    => 'nature-tourism',
	'tp_protected_area'  => 'nature-tourism',
	'tp_natural_feature' => 'nature-tourism',
	'tp_water_body'      => 'nature-tourism',
	'tp_island'          => 'nature-tourism',
);
$by_title = array(
	'بازار' => 'bazaar-tours',
	'اسکی'  => 'skiing',
);

if ( 'rollback' === $cmd ) {
	$b = json_decode( (string) @file_get_contents( (string) ( $args[1] ?? '' ) ), true );
	if ( ! is_array( $b ) || ! array_key_exists( 'xml', $b ) ) {
		echo "پشتیبان نامعتبر\n";
		return;
	}
	foreach ( (array) $b['created'] as $tid ) {
		wp_delete_term( (int) $tid, 'tpc_tag' );
	}
	update_option( 'wpml-tm-custom-xml', (string) $b['xml'] );
	if ( $sitepress && array_key_exists( 'sync', $b ) ) {
		$s = (array) $sitepress->get_setting( 'taxonomies_sync_option', array() );
		if ( null === $b['sync'] ) {
			unset( $s['tpc_tag'] );
		} else {
			$s['tpc_tag'] = $b['sync'];
		}
		$sitepress->set_setting( 'taxonomies_sync_option', $s, true );
	}
	echo 'برگشت: ' . count( (array) $b['created'] ) . " واژه پاک شد\n";
	return;
}

/* ── ۱. WPML ── */
$xml   = (string) get_option( 'wpml-tm-custom-xml', '' );
$sync  = $sitepress ? (array) $sitepress->get_setting( 'taxonomies_sync_option', array() ) : array();
$bak   = array( 'at' => gmdate( 'c' ), 'xml' => $xml, 'sync' => isset( $sync['tpc_tag'] ) ? $sync['tpc_tag'] : null, 'created' => array() );
$bfile = '';

if ( $apply ) {
	wp_mkdir_p( $bdir );
	$bfile = $bdir . 'tags51-' . gmdate( 'Ymd-His' ) . '.json';
	if ( ! file_put_contents( $bfile, wp_json_encode( $bak ) ) ) {
		echo "پشتیبان نوشته نشد — توقف\n";
		return;
	}
	$log( 'پشتیبان: ' . $bfile );
}

if ( false === strpos( $xml, '>tpc_tag</taxonomy>' ) ) {
	$nl  = false !== strpos( $xml, "\r\n" ) ? "\r\n" : "\n";
	$row = '    <taxonomy translate="1">tpc_tag</taxonomy>' . $nl;
	if ( false !== strpos( $xml, '</taxonomies>' ) ) {
		$new = str_replace( '  </taxonomies>', $row . '  </taxonomies>', $xml );
	} elseif ( false !== strpos( $xml, '</wpml-config>' ) ) {
		$new = str_replace( '</wpml-config>', '  <taxonomies>' . $nl . $row . '  </taxonomies>' . $nl . '</wpml-config>', $xml );
	} else {
		$new = $xml;
	}
	if ( $new !== $xml ) {
		if ( $apply ) {
			update_option( 'wpml-tm-custom-xml', $new );
		}
		$log( 'wpml-tm-custom-xml: tpc_tag ترجمه‌پذیر' . ( $apply ? '' : ' [افزوده می‌شود]' ) );
	} else {
		$log( 'wpml-tm-custom-xml: ساختار ناشناخته — دست نخورد' );
	}
} else {
	$log( 'wpml-tm-custom-xml: هست' );
}
if ( $sitepress && 1 !== (int) ( $sync['tpc_tag'] ?? 0 ) ) {
	if ( $apply ) {
		$sync['tpc_tag'] = 1;
		$sitepress->set_setting( 'taxonomies_sync_option', $sync, true );
	}
	$log( 'taxonomies_sync_option[tpc_tag] = 1' . ( $apply ? '' : ' [تنظیم می‌شود]' ) );
}

/* ── ۲. واژه‌ها ── */
$ids = array();   /* [ نامک ][ زبان ] = term_id */
foreach ( $terms as $t ) {
	$trid = null;
	foreach ( $langs as $i => $lang ) {
		$name = $t[ 3 + $i ];
		do_action( 'wpml_switch_language', $lang );
		$have = get_terms( array( 'taxonomy' => 'tpc_tag', 'hide_empty' => false, 'name' => $name, 'fields' => 'ids' ) );
		$tid  = $have ? (int) $have[0] : 0;
		if ( ! $tid && $apply ) {
			$r = wp_insert_term( $name, 'tpc_tag', array( 'slug' => $t[0] ) );
			if ( is_wp_error( $r ) ) {
				$log( 'خطا ' . $name . ': ' . $r->get_error_message() );
				continue;
			}
			$tid = (int) $r['term_id'];
			$bak['created'][] = $tid;
			file_put_contents( $bfile, wp_json_encode( $bak ) );   /* پس از هر ساخت — اجرای نیمه‌کاره هم برمی‌گردد */
			do_action( 'wpml_set_element_language_details', array(
				'element_id'           => (int) $r['term_taxonomy_id'],
				'element_type'         => 'tax_tpc_tag',
				'trid'                 => $trid,
				'language_code'        => $lang,
				'source_language_code' => 'fa' === $lang ? null : 'fa',
			) );
		}
		if ( $tid && $apply ) {   /* واژۀ موجود هم — اجرای نیمه‌کاره متا را کامل می‌کند */
			update_term_meta( $tid, 'tp_is_experience', 1 );
			update_term_meta( $tid, 'tp_icon', $t[1] );
			update_term_meta( $tid, 'tp_sort', $t[2] );
		}
		if ( $tid && null === $trid ) {
			$tt   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'tpc_tag'", $tid ) );
			$trid = apply_filters( 'wpml_element_trid', null, $tt, 'tax_tpc_tag' );
		}
		$ids[ $t[0] ][ $lang ] = $tid;
		$slug = $tid ? (string) get_term( $tid, 'tpc_tag' )->slug : $t[0];
		$log( sprintf( '%s · %s · %s%s', $lang, $name, rawurldecode( $slug ), $tid ? '' : ' [ساخته می‌شود]' ) );
	}
}
do_action( 'wpml_switch_language', 'fa' );

/* ── ۳. نگاشت ── */
$fa = $wpdb->get_results(
	"SELECT p.ID, p.post_type, p.post_title FROM {$wpdb->posts} p
	 JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = CONCAT( 'post_', p.post_type )
	 WHERE p.post_status = 'publish' AND p.post_type LIKE 'tp\\_%' AND t.language_code = 'fa'"
);
$table = array();
foreach ( $fa as $p ) {
	$slugs = array();
	if ( isset( $by_type[ $p->post_type ] ) ) {
		$slugs[] = $by_type[ $p->post_type ];
	}
	foreach ( $by_title as $word => $slug ) {
		if ( false !== mb_strpos( $p->post_title, $word ) ) {
			$slugs[] = $slug;
		}
	}
	if ( ! $slugs ) {
		continue;
	}
	$kind = metadata_exists( 'post', (int) $p->ID, '_tp_sample' ) ? ( '1' === get_post_meta( (int) $p->ID, '_tp_demo', true ) ? 'demo' : 'sample' ) : 'real';
	if ( 'sample' === $kind ) {
		continue;   /* نمونۀ بی پرچم نمایشی هرگز دیده نمی‌شود */
	}
	$tr = (array) apply_filters( 'wpml_get_element_translations', null, apply_filters( 'wpml_element_trid', null, (int) $p->ID, 'post_' . $p->post_type ), 'post_' . $p->post_type );
	foreach ( $langs as $lang ) {
		$pid = 'fa' === $lang ? (int) $p->ID : ( isset( $tr[ $lang ] ) ? (int) $tr[ $lang ]->element_id : 0 );
		if ( ! $pid ) {
			continue;
		}
		$tids = array();
		foreach ( array_unique( $slugs ) as $s ) {
			if ( ! empty( $ids[ $s ][ $lang ] ) ) {
				$tids[] = (int) $ids[ $s ][ $lang ];
			}
		}
		if ( $apply && $tids ) {
			wp_set_object_terms( $pid, $tids, 'tpc_tag', true );
		}
		$table[] = array( $kind, $lang, $pid, $p->post_type, get_the_title( $pid ), implode( ',', array_unique( $slugs ) ) );
	}
}
echo "\nنگاشت واقعی (زبان · شناسه · نوع پست · عنوان · برچسب):\n";
foreach ( $table as $r ) {
	if ( 'real' === $r[0] ) {
		$log( implode( "\t", array_slice( $r, 1 ) ) );
	}
}
$demo = count( array_filter( $table, function ( $r ) { return 'demo' === $r[0]; } ) );
$log( 'نمایشی (فقط در پیش‌نمایش): ' . $demo . ' ردیف' );

if ( $apply ) {
	file_put_contents( $bfile, wp_json_encode( $bak ) );
	flush_rewrite_rules( false );
}
echo $apply ? "اعمال شد.\n" : "فقط گزارش — با apply اعمال می‌شود.\n";
