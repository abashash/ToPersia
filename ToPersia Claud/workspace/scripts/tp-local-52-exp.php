<?php
/**
 * دستۀ 52 بند ۲ — P5-D563: هر هشت واژۀ تجربه (tp_is_experience) دست‌کم TP52_MIN مورد نمایشی داشته باشد.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-local-52-exp.php [report|apply|rollback <پشتیبان>]
 *
 * فقط پست نمایشی (`_tp_demo` = 1) برچسب می‌گیرد — پست واقعی هرگز؛ پس بیرون از حالت پیش‌نمایش هیچ چیز عوض نمی‌شود.
 * گزینش: نخست نوع‌های هم‌معنای هر واژه، سپس هر پست نمایشی همان زبان (به ترتیب شناسه، قطعی).
 * پیش از apply پشتیبان JSON بیرون از ریشۀ وب (C:/wamp64/tp-backups/)؛ برگشت: rollback <پشتیبان>.
 * فقط پایگاه‌دادۀ محلی.
 */
if ( 'local' !== wp_get_environment_type() || 'topersia.local' !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) {
	echo "فقط محلی\n";
	return;
}
if ( ! taxonomy_exists( 'tpc_tag' ) ) {
	echo "tpc_tag ثبت نشده (هسته)\n";
	return;
}

const TP52_MIN = 2;

global $wpdb;
$cmd   = isset( $args[0] ) ? (string) $args[0] : 'report';
$apply = 'apply' === $cmd;
$bdir  = dirname( untrailingslashit( ABSPATH ), 3 ) . '/tp-backups/';

if ( 'rollback' === $cmd ) {
	$b = json_decode( (string) @file_get_contents( (string) ( $args[1] ?? '' ) ), true );
	if ( ! is_array( $b ) || ! isset( $b['added'] ) ) {
		echo "پشتیبان نامعتبر\n";
		return;
	}
	foreach ( $b['added'] as $row ) {
		wp_remove_object_terms( (int) $row[0], array( (int) $row[1] ), 'tpc_tag' );
	}
	echo 'برگشت: ' . count( $b['added'] ) . " پیوند برداشته شد.\n";
	return;
}

/* نامک فارسی واژه ← نوع‌های هم‌معنا (نخست از این‌ها) */
$prefer = array(
	'skiing'         => array( 'tp_mountain', 'tp_sports_event', 'tp_seasonal_event' ),
	'mountaineering' => array( 'tp_mountain', 'tp_natural_feature' ),
	'camping'        => array( 'tp_forest_plain', 'tp_protected_area', 'tp_desert', 'tp_water_body' ),
	'nature-tourism' => array( 'tp_forest_plain', 'tp_protected_area', 'tp_natural_feature', 'tp_water_body', 'tp_island' ),
	'local-cuisine'  => array( 'tp_local_food', 'tp_festival' ),
	'bazaar-tours'   => array( 'tp_district', 'tp_handicraft', 'tp_monument' ),
	'photography'    => array( 'tp_landscape', 'tp_natural_feature', 'tp_monument', 'tp_desert' ),
	'desert-tours'   => array( 'tp_desert', 'tp_route' ),
);

$added = array();

foreach ( array( 'fa', 'en', 'ar' ) as $lang ) {

	do_action( 'wpml_switch_language', $lang );

	$terms = get_terms( array( 'taxonomy' => 'tpc_tag', 'hide_empty' => false, 'meta_query' => array( array( 'key' => 'tp_is_experience', 'value' => '1' ) ) ) );
	$demo  = array_map( 'absint', (array) get_posts( array(
		'post_type'        => array_keys( topersia_g_type_matrix() ),
		'post_status'      => 'publish',
		'posts_per_page'   => -1,
		'fields'           => 'ids',
		'orderby'          => 'ID',
		'order'            => 'ASC',
		'suppress_filters' => false,
		'meta_query'       => array( array( 'key' => '_tp_demo', 'value' => '1' ) ),
	) ) );

	echo "\n── {$lang}: " . count( $demo ) . " پست نمایشی ──\n";

	foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {

		$fa_tid = (int) apply_filters( 'wpml_object_id', $t->term_id, 'tpc_tag', true, 'fa' );
		$fa     = get_term( $fa_tid, 'tpc_tag' );
		$slug   = ( $fa && ! is_wp_error( $fa ) ) ? rawurldecode( (string) $fa->slug ) : rawurldecode( (string) $t->slug );
		$slug   = (string) preg_replace( '/-(en|ar|fa)$/', '', $slug );

		$have = array_map( 'absint', (array) get_objects_in_term( $t->term_id, 'tpc_tag' ) );
		$vis  = count( array_intersect( $have, $demo ) );
		$real = count( array_filter( $have, function ( $id ) { return 'publish' === get_post_status( $id ) && ! metadata_exists( 'post', $id, '_tp_sample' ); } ) );
		$need = max( 0, TP52_MIN - $vis - $real );

		$pool = array();
		foreach ( isset( $prefer[ $slug ] ) ? $prefer[ $slug ] : array() as $type ) {
			foreach ( $demo as $id ) {
				if ( $type === get_post_type( $id ) && ! in_array( $id, $have, true ) && ! in_array( $id, $pool, true ) ) {
					$pool[] = $id;
				}
			}
		}
		foreach ( $demo as $id ) {
			if ( ! in_array( $id, $have, true ) && ! in_array( $id, $pool, true ) ) {
				$pool[] = $id;
			}
		}

		$pick = array_slice( $pool, 0, $need );

		foreach ( $pick as $id ) {
			if ( $apply ) {
				wp_set_object_terms( $id, array( (int) $t->term_id ), 'tpc_tag', true );
			}
			$added[] = array( $id, (int) $t->term_id );
		}

		printf( "  %-16s %-22s واقعی %d · نمایشی %d · افزوده %d%s\n", $slug, $t->name, $real, $vis, count( $pick ), $pick ? ' (' . implode( ',', $pick ) . ')' : '' );
	}
}

do_action( 'wpml_switch_language', 'fa' );

if ( $apply ) {
	$bfile = $bdir . 'exp52-' . gmdate( 'Ymd-His' ) . '.json';
	file_put_contents( $bfile, wp_json_encode( array( 'added' => $added ) ) );
	echo "\nپشتیبان: {$bfile}\nاعمال شد: " . count( $added ) . " پیوند.\n";
} else {
	echo "\nفقط گزارش — با apply اعمال می‌شود (" . count( $added ) . " پیوند).\n";
}
