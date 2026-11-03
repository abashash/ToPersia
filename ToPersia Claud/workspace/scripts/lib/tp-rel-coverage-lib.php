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

	/**
	 * P5-D504: عنوان هر رابطه از هر سو، برای هر گروهی که نشانش می‌دهد. گروه «مرتبط» عنوان ردیف خودش را دارد و بقیه عنوان گروه.
	 * $relmap و $cat برای جهش قابل جایگزینی‌اند. ستون why فقط برای سوی بی عنوان: دلیل مکتوب، یا خالی یعنی شکاف.
	 */
	function tp_rel_heading_rows( array $cat, $relmap = null ) {

		$relmap = null === $relmap ? topersia_g_relmap() : $relmap;
		$rows   = array();

		foreach ( tp_rel_cov_rows( $relmap ) as $id => $r ) {
			foreach ( array( 'children' => array( $r['p'], $r['gp'], $r['wp'] ), 'parents' => array( $r['c'], $r['gc'], $r['wc'] ) ) as $dir => $side ) {
				list( $page, $groups, $why ) = $side;
				foreach ( '' === $groups ? array( '' ) : explode( '+', $groups ) as $key ) {
					$fa = '';
					if ( 'related' === $key ) {
						foreach ( (array) $relmap[ $page ]['related']['rels'] as $rel ) {
							if ( (int) $rel['id'] === (int) $id && $rel['dir'] === $dir ) { $fa = (string) ( $rel['title'] ?? '' ); }
						}
					} elseif ( '' !== $key ) {
						$fa = (string) ( $relmap[ $page ][ $key ]['title'] ?? '' );
					}
					$rows[] = array(
						'id' => (int) $id, 'p' => $r['p'], 'c' => $r['c'], 'page' => $page, 'dir' => $dir, 'group' => $key,
						'fa' => $fa, 'en' => '' === $fa ? '' : (string) ( $cat[ $fa ]['en'] ?? '' ), 'ar' => '' === $fa ? '' : (string) ( $cat[ $fa ]['ar'] ?? '' ),
						'real' => $r['real'], 'sample' => $r['sample'],
						'why' => 'crumbs' === $key ? 'مسیر راهنما، بی سرفصل' : ( '' === $key ? $why : '' ),
					);
				}
			}
		}

		return $rows;
	}

	/** متن پروندهٔ docs/ops/REL-HEADINGS-2026-11.tsv از ردیف‌ها؛ یک منبع برای نوشتن و سنجیدن. */
	function tp_rel_heading_tsv( array $rows ) {

		$tsv = "رابطه\tوالد\tفرزند\tصفحه\tگروه\tfa\ten\tar\tیال واقعی\tیال نمونه\tبی عنوان چون\n";
		foreach ( $rows as $r ) {
			$tsv .= implode( "\t", array( $r['id'], $r['p'], $r['c'], $r['page'], '' === $r['group'] ? '—' : $r['group'], '' === $r['fa'] ? '—' : $r['fa'], '' === $r['en'] ? '—' : $r['en'], '' === $r['ar'] ? '—' : $r['ar'], $r['real'], $r['sample'], $r['why'] ) ) . "\n";
		}
		return $tsv;
	}

	/**
	 * همان جدول بی دو ستون شمار یال (نهم و دهم). شمارها عکس لحظهٔ نوشتن‌اند و با هر یال محلی عوض می‌شوند؛
	 * گیت فقط آنچه از کد می‌آید را می‌سنجد: سوها، گروه، عنوان سه‌زبانه و دلیل (بازبینی دستهٔ 44).
	 */
	function tp_rel_heading_shape( $tsv ) {

		$out = array();
		foreach ( explode( "\n", (string) $tsv ) as $line ) {
			$c = explode( "\t", $line );
			array_splice( $c, 8, 2 );
			$out[] = implode( "\t", $c );
		}
		return implode( "\n", $out );
	}

	/** سوی با یال که نه عنوان کامل سه‌زبانه دارد و نه دلیل مکتوب. */
	function tp_rel_heading_gaps( array $rows ) {

		$gaps = array();
		foreach ( $rows as $r ) {
			if ( ! $r['real'] && ! $r['sample'] ) { continue; }
			if ( '' !== $r['why'] ) { continue; }
			if ( '' === $r['fa'] || '' === $r['en'] || '' === $r['ar'] ) { $gaps[] = $r['id'] . ':' . $r['page'] . ':' . $r['dir']; }
		}
		return $gaps;
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

/* بازبینی دستهٔ 44: یک تعریف برای گزارش آمادگی و آزمون‌ها. */
if ( ! function_exists( 'tp_freeze_row_is_rel' ) ) {

	/** ردیف DISPLAY در CSV انجماد فقط وقتی رابطه است که نامش «relNN» باشد (`rel92 parents (#92)`)؛ `related_museum` و `religious_site_etiquette` فیلدند. */
	function tp_freeze_row_is_rel( $name ) {

		return 1 === preg_match( '/^rel\d+\b/', (string) $name );
	}

	/** گونهٔ پست نمونه: شهرستان نمونه (`_tp_sample_county`) ستون خودش را دارد، هرچند نشان `_tp_sample_empty` هم دارد. */
	function tp_sample_kind( $county, $empty ) {

		return $county ? 'county' : ( $empty ? 'empty' : 'full' );
	}
}
