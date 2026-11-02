<?php
/**
 * P5-D503: پوشش نمایش رابطه‌ها. فقط‌خواندنی؛ مشترک میان گزارش و آزمون.
 *
 * هر سوی هر رابطه یکی از این‌هاست: گروه در topersia_g_relmap() یا سطح دیگری با دلیل. رابطهٔ با یال نباید بی‌دلیل بماند.
 */
if ( ! function_exists( 'tp_rel_cov_reason' ) ) {

	/** سوی بی گروه چرا در صفحه دیده می‌شود یا نباید دیده شود؛ خالی یعنی شکاف واقعی. */
	function tp_rel_cov_reason( $id, $me, $other, array $place_rank ) {

		if ( 'tp_county' === $me ) { return 'شهرستان صفحه ندارد (P5-D220)'; }
		if ( 'tpc_news' === $me ) { return 'خبر قالب خودش را دارد'; }
		if ( 'tp_county' === $other ) { return 'سوی دیگر شهرستان است و صفحه ندارد'; }
		if ( 'tpc_news' === $other ) { return 'سطح اخبار مکان (topersia_g_news)'; }
		if ( 'tp_country' === $other ) { return 'کشور عمداً کاشی نمی‌شود (P5-D348)'; }
		if ( 'tp_province' === $me && in_array( (int) $id, array( 82, 83, 125, 128 ), true ) ) { return 'عمداً در استان نیست (P5-D170)'; }
		if ( ! isset( $place_rank[ $me ] ) && in_array( $other, array( 'tp_province', 'tp_city', 'tp_village' ), true ) ) { return 'کاشی مکان‌های مرتبط (topersia_g_geo_related_tiles)'; }
		return '';
	}

	/** ردیف هر رابطه با شمار یال و گروه و دلیل هر سو. $relmap برای جهش قابل جایگزینی است. */
	function tp_rel_cov_rows( $relmap = null ) {

		global $wpdb;

		$relmap  = null === $relmap ? topersia_g_relmap() : $relmap;
		$rank    = topersia_g_place_rank();
		$samples = array_flip( array_map( 'intval', $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tp_sample'" ) ) );
		$edges   = array();

		foreach ( $wpdb->get_results( "SELECT rel_id, parent_object_id p, child_object_id c FROM {$wpdb->prefix}jet_rel_default" ) as $r ) {
			$k = isset( $samples[ (int) $r->p ] ) || isset( $samples[ (int) $r->c ] ) ? 'sample' : 'real';
			$edges[ (int) $r->rel_id ][ $k ] = ( $edges[ (int) $r->rel_id ][ $k ] ?? 0 ) + 1;
		}

		$groups = array();
		foreach ( $relmap as $type => $map ) {
			foreach ( $map as $key => $g ) {
				$rels = (array) ( $g['rels'] ?? array() );
				foreach ( (array) ( $g['chains'] ?? array() ) as $chain ) { $rels = array_merge( $rels, (array) $chain ); }
				foreach ( $rels as $r ) {
					if ( isset( $r['id'] ) ) { $groups[ (int) $r['id'] ][ $type ][ $key ][] = $r['dir'] ?? ''; }
				}
			}
		}

		$rows = array();
		foreach ( topersia_g_relreg() as $id => $r ) {
			$p = (string) $r['parent'];
			$c = (string) $r['child'];
			$rows[ (int) $id ] = array(
				'id'     => (int) $id,
				'p'      => $p,
				'c'      => $c,
				'real'   => $edges[ $id ]['real'] ?? 0,
				'sample' => $edges[ $id ]['sample'] ?? 0,
				/* رابطهٔ هم‌نوع: هر سو فقط با جهت خودش پوشیده است */
				'gp'     => implode( '+', array_keys( array_filter( $groups[ $id ][ $p ] ?? array(), function ( $d ) use ( $p, $c ) { return $p !== $c || array_intersect( array( 'children', '' ), $d ); } ) ) ),
				'gc'     => implode( '+', array_keys( array_filter( $groups[ $id ][ $c ] ?? array(), function ( $d ) use ( $p, $c ) { return $p !== $c || array_intersect( array( 'parents', '' ), $d ); } ) ) ),
				'wp'     => tp_rel_cov_reason( $id, $p, $c, $rank ),
				'wc'     => tp_rel_cov_reason( $id, $c, $p, $rank ),
			);
		}
		ksort( $rows );

		return $rows;
	}

	/** رابطهٔ با یال که سویی بی گروه و بی دلیل دارد: [ [ id, نوع, جهت ], … ] */
	function tp_rel_cov_gaps( array $rows ) {

		$gaps = array();
		foreach ( $rows as $r ) {
			if ( ! $r['real'] && ! $r['sample'] ) { continue; }
			if ( '' === $r['gp'] && '' === $r['wp'] ) { $gaps[] = array( $r['id'], $r['p'], 'children' ); }
			if ( '' === $r['gc'] && '' === $r['wc'] ) { $gaps[] = array( $r['id'], $r['c'], 'parents' ); }
		}
		return $gaps;
	}
}
