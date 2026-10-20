<?php
/**
 * کتابخانهٔ apply / rollback عددهای پایه — P5-D394 (فقط محلی).
 *
 * قواعد (متن پرامت دستهٔ ۲۲ب، با شمار تأییدشدهٔ کاربر: ۲۷ + ۳۵۴ + ۲ = ۳۸۳):
 *   approved = yes          ردیف fill (فیلد خالی ← مقدار ویکی‌داده)
 *   approved = yes-replace  ردیف diff با استثنای صریح کاربر (۲۷ QID استان + مساحت تهران و یزد)
 * apply فقط ردیف approved را می‌نویسد؛ ردیف fill فقط وقتی فیلد در لحظهٔ نوشتن هنوز خالی است (غیرخالی هرگز تغییر نمی‌کند)؛
 * ردیف replace فقط همان استثنای صریح. نوشتن: update_post_meta روی پست زبان پیش‌فرض و API رابطهٔ جت‌انجین؛ سپس
 * wpml_sync_custom_field برای فیلدهای copy؛ منبع هر عدد در source_references (بی نسخهٔ تکراری)؛ لاگ هر نوشتن با مقدار پیشین؛
 * rollback از همان لاگ. بی فیلد، جدول یا ستون تازه.
 */
if ( ! defined( 'TPF_LIB' ) ) {
	define( 'TPF_LIB', 1 );

	/**
	 * جای پشتیبان و لاگ — بیرون از ریشهٔ وب (پشتیبان کل postmeta است).
	 * ثابت TOPERSIA_PRIVATE_DIR برنده؛ وگرنه کنار ریشهٔ وب: ABSPATH = …/public_html/geo/ ← …/tp-private/ (محلی C:/wamp64/www/tp-private/).
	 * هر زیرپوشه .htaccess و web.config «ردِ همه» دارد، برای روزی که پوشه زیر ریشهٔ وبِ دیگری بیفتد؛ پرونده‌ها 0600.
	 */
	function tpf_private( $sub ) {
		$base = defined( 'TOPERSIA_PRIVATE_DIR' ) ? (string) TOPERSIA_PRIVATE_DIR : dirname( untrailingslashit( ABSPATH ), 2 ) . '/tp-private';
		$dir  = trailingslashit( $base ) . $sub . '/';
		wp_mkdir_p( $dir );
		if ( ! is_file( $dir . '.htaccess' ) ) { file_put_contents( $dir . '.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); }
		if ( ! is_file( $dir . 'web.config' ) ) { file_put_contents( $dir . 'web.config', '<?xml version="1.0"?><configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>' ); }
		if ( ! is_file( $dir . 'index.php' ) ) { file_put_contents( $dir . 'index.php', "<?php\n// Silence is golden.\n" ); }
		return $dir;
	}

	/** نام پرونده: زمان + ۳۲ هگز تصادفی (حدس‌ناپذیر). */
	function tpf_fname( $prefix, $ext ) {
		return $prefix . gmdate( 'Ymd-His' ) . '-' . bin2hex( random_bytes( 16 ) ) . '.' . $ext;
	}

	/** CSV ← آرایهٔ ردیف‌ها با کلید ستون (BOM برداشته). */
	function tpf_csv_load( $file ) {
		$fh = fopen( $file, 'r' );
		$h  = fgetcsv( $fh );
		$h[0] = preg_replace( '/^\xEF\xBB\xBF/', '', $h[0] );
		$rows = array();
		while ( $x = fgetcsv( $fh ) ) { $rows[] = array_combine( $h, array_pad( $x, count( $h ), '' ) ); }
		fclose( $fh );
		return array( $h, $rows );
	}

	function tpf_csv_save( $file, $h, $rows ) {
		$fh = fopen( $file, 'w' );
		fwrite( $fh, "\xEF\xBB\xBF" );
		fputcsv( $fh, $h );
		foreach ( $rows as $r ) { fputcsv( $fh, array_map( function ( $k ) use ( $r ) { return $r[ $k ]; }, $h ) ); }
		fclose( $fh );
	}

	function tpf_act( $r ) { return preg_replace( '/ \(.*/u', '', (string) $r['action'] ); }

	/**
	 * قاعدهٔ تأیید کاربر (دستهٔ ۲۲ب) — خالص.
	 * @return string '' | 'yes' | 'yes-replace'
	 */
	function tpf_rule( $r ) {
		$act = tpf_act( $r );
		if ( 'external_reference_uri' === $r['field'] && 'auto' === $r['status'] && 'diff' === $act && 'tp_province' === $r['post_type'] ) { return 'yes-replace'; }
		if ( 'auto' === $r['status'] && 'fill' === $act && '' !== $r['field'] ) {
			if ( 'population' === $r['field'] && '2016' !== (string) $r['year'] ) { return ''; }
			return 'yes';
		}
		if ( 'auto' === $r['status'] && 'diff' === $act && 'area_km2' === $r['field']
			&& ( ( 'استان تهران' === $r['name_fa'] && '18814' === (string) $r['proposed'] ) || ( 'یزد' === $r['name_fa'] && '129285' === (string) $r['proposed'] ) ) ) { return 'yes-replace'; }
		return '';
	}

	/** ستون approved را با قاعده پر می‌کند؛ شمار ردیف‌ها را برمی‌گرداند. */
	function tpf_mark( &$rows ) {
		$n = array( 'yes' => 0, 'yes-replace' => 0 );
		foreach ( $rows as &$r ) { $r['approved'] = tpf_rule( $r ); if ( '' !== $r['approved'] ) { $n[ $r['approved'] ]++; } }
		unset( $r );
		return $n;
	}

	/** پست زبان پیش‌فرض یک entity_uid. */
	function tpf_post_id( $uid ) {
		static $memo = array();
		if ( isset( $memo[ $uid ] ) ) { return $memo[ $uid ]; }
		$def = apply_filters( 'wpml_default_language', null );
		$ids = get_posts( array( 'post_type' => 'any', 'post_status' => 'any', 'numberposts' => 5, 'fields' => 'ids', 'meta_key' => 'entity_uid', 'meta_value' => $uid, 'suppress_filters' => false, 'lang' => $def ? $def : 'fa' ) );
		return $memo[ $uid ] = $ids ? (int) $ids[0] : 0;
	}

	/** فهرست نوشتن‌ها از ردیف‌های approved (فقط ردیف approved وارد می‌شود). */
	function tpf_plan( $rows ) {
		$ops = array();
		foreach ( $rows as $r ) {
			if ( ! in_array( $r['approved'], array( 'yes', 'yes-replace' ), true ) ) { continue; }
			$ops[] = array(
				'uid' => $r['entity_uid'], 'type' => $r['post_type'], 'name' => $r['name_fa'], 'qid' => $r['qid'], 'field' => $r['field'], 'new' => (string) $r['proposed'],
				'mode' => 'yes-replace' === $r['approved'] ? 'replace' : 'fill', 'prop' => $r['property'], 'year' => $r['year'], 'ref' => $r['reference'], 'retrieved' => $r['retrieved'],
				'is_rel' => 0 === strpos( $r['field'], 'rel:96' ),
			);
		}
		return $ops;
	}

	function tpf_is_empty( $v ) { return '' === trim( (string) $v ); }

	function tpf_source_label( $field ) {
		$l = array( 'geo_coordinates' => 'مختصات', 'population' => 'جمعیت', 'area_km2' => 'مساحت', 'total_area_km2' => 'مساحت', 'elevation_m' => 'ارتفاع' );
		return isset( $l[ $field ] ) ? $l[ $field ] : '';
	}

	/** ردیف‌های منبع یک نوشتن: ویکی‌داده + (جمعیت) مرجع amar.org.ir اگر در CSV هست. */
	function tpf_source_rows( $op ) {
		$lab = tpf_source_label( $op['field'] );
		if ( '' === $lab || '' === $op['qid'] ) { return array(); }
		$tail = $op['prop'] . ( '' !== $op['year'] ? '، ' . $op['year'] : '' );
		$out  = array( array( 'source_name' => 'ویکی‌داده — ' . $lab . ' (' . $tail . ')', 'source_url' => 'https://www.wikidata.org/wiki/' . $op['qid'], 'source_access_date' => $op['retrieved'], 'source_row_status' => 'active' ) );
		if ( 'population' === $op['field'] && false !== strpos( (string) $op['ref'], 'amar.org.ir' ) ) {
			$out[] = array( 'source_name' => 'مرکز آمار ایران — جمعیت (' . $op['year'] . ')', 'source_url' => $op['ref'], 'source_access_date' => $op['retrieved'], 'source_row_status' => 'active' );
		}
		return $out;
	}

	/** افزودن ردیف منبع بی تکرار (نام + نشانی)؛ آرایهٔ تازه برمی‌گرداند. */
	function tpf_merge_sources( $cur, $add ) {
		$cur  = is_array( $cur ) ? $cur : array();
		$have = array();
		$max  = -1;
		foreach ( $cur as $k => $row ) {
			if ( is_array( $row ) ) { $have[ ( $row['source_name'] ?? '' ) . '|' . ( $row['source_url'] ?? '' ) ] = 1; }
			if ( preg_match( '/^item-(\d+)$/', (string) $k, $m ) ) { $max = max( $max, (int) $m[1] ); }
		}
		foreach ( $add as $row ) {
			$key = $row['source_name'] . '|' . $row['source_url'];
			if ( isset( $have[ $key ] ) ) { continue; }
			$cur[ 'item-' . ( ++$max ) ] = $row;
			$have[ $key ] = 1;
		}
		return $cur;
	}

	/**
	 * یک نوشتن. dry: فقط تصمیم. خروجی: [نتیجه، مقدار پیشین، پیام].
	 * نتیجه: write · skip-notempty (fill روی فیلد غیرخالی) · skip-same · skip-nopost · skip-norel
	 */
	function tpf_do_op( $op, $dry, &$log ) {
		$pid = tpf_post_id( $op['uid'] );
		if ( ! $pid ) { return array( 'skip-nopost', '', '' ); }

		if ( $op['is_rel'] ) {
			$city = tpf_post_id( $op['new'] );
			if ( ! $city ) { return array( 'skip-norel', '', '' ); }
			$rel = topersia_relation( 96 );
			$have = array_map( 'intval', (array) $rel->get_children( $pid, 'ids' ) );
			if ( in_array( $city, $have, true ) ) { return array( 'skip-same', '', '' ); }
			if ( $have ) { return array( 'skip-notempty', implode( ',', $have ), '' ); }   /* مرکز استان دیگری هست: fill نیست */
			if ( ! $dry ) { $rel->update( $pid, $city ); $log[] = array( 'kind' => 'rel', 'rel' => 96, 'parent' => $pid, 'child' => $city, 'uid' => $op['uid'] ); }
			return array( 'write', '', 'rel96 ' . $pid . ' → ' . $city );
		}

		$key = $op['field'];
		$cur = (string) get_post_meta( $pid, $key, true );
		if ( $cur === $op['new'] ) { return array( 'skip-same', $cur, '' ); }
		if ( 'fill' === $op['mode'] && ! tpf_is_empty( $cur ) ) { return array( 'skip-notempty', $cur, '' ); }   /* فیلد غیرخالی تغییر نمی‌کند */
		if ( 'replace' === $op['mode'] && 'yes-replace' !== $op['approved_flag'] ) { return array( 'skip-notempty', $cur, '' ); }
		if ( ! $dry ) {
			$existed = metadata_exists( 'post', $pid, $key );
			update_post_meta( $pid, $key, wp_slash( $op['new'] ) );
			$log[] = array( 'kind' => 'meta', 'post' => $pid, 'key' => $key, 'existed' => $existed, 'old' => $cur, 'new' => $op['new'], 'mode' => $op['mode'], 'uid' => $op['uid'] );
		}
		return array( 'write', $cur, $key . ' «' . $cur . '» ← «' . $op['new'] . '»' );
	}

	/** پشتیبان SQL: کل postmeta و کل جدول رابطهٔ جت‌انجین — بیرون از git. */
	function tpf_backup() {
		global $wpdb;
		$f  = tpf_private( 'facts-backup' ) . tpf_fname( 'facts-backup-', 'sql' );
		$fh = fopen( $f, 'w' );
		@chmod( $f, 0600 );   // phpcs:ignore WordPress.PHP.NoSilencedErrors -- ویندوز chmod را نادیده می‌گیرد
		fwrite( $fh, "-- ToPersia facts backup (P5-D394) " . gmdate( 'c' ) . "\n-- بازیابی: wp db query < این پرونده — یا scripts/tp-facts.php rollback <log> (دقیق‌تر)\nSET NAMES utf8mb4;\n" );
		foreach ( array( $wpdb->postmeta => 'meta_id', $wpdb->prefix . 'jet_rel_default' => '_ID' ) as $table => $pk ) {
			fwrite( $fh, "\n-- {$table}\n" );
			$last = 0;
			do {
				$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `{$pk}` > %d ORDER BY `{$pk}` LIMIT 500", $last ), ARRAY_A );
				foreach ( $rows as $r ) {
					$last = (int) $r[ $pk ];
					fwrite( $fh, "REPLACE INTO `{$table}` (`" . implode( '`,`', array_keys( $r ) ) . "`) VALUES (" . implode( ',', array_map( function ( $v ) use ( $wpdb ) { return null === $v ? 'NULL' : "'" . esc_sql( $v ) . "'"; }, $r ) ) . ");\n" );
				}
			} while ( $rows );
		}
		fclose( $fh );
		return $f;
	}

	/** apply. $opts: dry, backup(bool), expect(int|null). */
	function tpf_apply( $rows, $opts = array() ) {
		$dry = ! empty( $opts['dry'] );
		$ops = tpf_plan( $rows );
		if ( isset( $opts['expect'] ) && count( $ops ) !== (int) $opts['expect'] ) {
			return array( 'error' => 'شمار ردیف approved ' . count( $ops ) . ' با انتظار ' . $opts['expect'] . ' برابر نیست — توقف' );
		}
		/* approved_flag برای نگهبان replace */
		$flag = array();
		foreach ( $rows as $r ) { if ( '' !== $r['approved'] ) { $flag[ $r['entity_uid'] . '|' . $r['field'] ] = $r['approved']; } }
		foreach ( $ops as &$o ) { $o['approved_flag'] = $flag[ $o['uid'] . '|' . $o['field'] ] ?? ''; }
		unset( $o );

		$res = array( 'by' => array(), 'lines' => array(), 'log' => array(), 'backup' => '', 'posts' => array() );
		if ( ! $dry && ! empty( $opts['backup'] ) ) { $res['backup'] = tpf_backup(); }

		$src_old = array();
		foreach ( $ops as $op ) {
			list( $st, $old, $msg ) = tpf_do_op( $op, $dry, $res['log'] );
			$k = ( $op['is_rel'] ? 'rel96' : $op['field'] ) . ' · ' . $op['mode'] . ' · ' . $st;
			$res['by'][ $k ] = ( $res['by'][ $k ] ?? 0 ) + 1;
			if ( 'write' === $st ) {
				$res['lines'][] = $op['name'] . ' — ' . $msg;
				$pid = tpf_post_id( $op['uid'] );
				$res['posts'][ $pid ] = 1;
				/* منبع هر عدد (فقط فیلد عددی؛ idempotent) */
				$add = tpf_source_rows( $op );
				if ( $add ) {
					$cur = get_post_meta( $pid, 'source_references', true );
					if ( ! isset( $src_old[ $pid ] ) ) { $src_old[ $pid ] = array( metadata_exists( 'post', $pid, 'source_references' ), $cur ); }
					$new = tpf_merge_sources( $cur, $add );
					if ( $new !== ( is_array( $cur ) ? $cur : array() ) && ! $dry ) { update_post_meta( $pid, 'source_references', $new ); }
					elseif ( $dry && $new !== ( is_array( $cur ) ? $cur : array() ) ) { $res['by']['source_references · rows'] = ( $res['by']['source_references · rows'] ?? 0 ) + count( $add ); }
				}
			}
		}
		if ( ! $dry ) {
			foreach ( $src_old as $pid => $x ) {
				$new  = get_post_meta( $pid, 'source_references', true );
				$had  = array();
				foreach ( is_array( $x[1] ) ? $x[1] : array() as $row ) { if ( is_array( $row ) ) { $had[ ( $row['source_name'] ?? '' ) . '|' . ( $row['source_url'] ?? '' ) ] = 1; } }
				$added = array();
				foreach ( is_array( $new ) ? $new : array() as $row ) { if ( is_array( $row ) && ! isset( $had[ ( $row['source_name'] ?? '' ) . '|' . ( $row['source_url'] ?? '' ) ] ) ) { $added[] = array( $row['source_name'], $row['source_url'] ); } }
				$res['log'][] = array( 'kind' => 'source', 'post' => $pid, 'existed' => $x[0], 'old' => $x[1], 'added' => $added );
			}
			tpf_after( array_keys( $res['posts'] ), $res['log'] );
			$res['logfile'] = tpf_private( 'facts-logs' ) . tpf_fname( 'facts-apply-', 'json' );   /* یکتا و حدس‌ناپذیر */
			file_put_contents( $res['logfile'], wp_json_encode( array( 'when' => gmdate( 'c' ), 'backup' => $res['backup'], 'writes' => $res['log'] ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
			@chmod( $res['logfile'], 0600 );   // phpcs:ignore WordPress.PHP.NoSilencedErrors
			if ( function_exists( 'topersia_log' ) ) { topersia_log( 'عددهای پایهٔ ویکی‌داده نوشته شد (P5-D394)', 'notice', 'import', array( 'writes' => count( $res['log'] ), 'posts' => count( $res['posts'] ), 'log' => basename( $res['logfile'] ) ) ); }
		}
		return $res;
	}

	/** پس از نوشتن/برگشت: همگام WPML برای copy، پاک‌کردن کش صفحه، بازسازی نمایهٔ مکانی. */
	function tpf_after( $post_ids, $writes ) {
		$copy = array( 'geo_coordinates', 'population', 'area_km2', 'total_area_km2', 'elevation_m', 'external_reference_uri' );
		foreach ( $writes as $w ) {
			if ( 'meta' === $w['kind'] && in_array( $w['key'], $copy, true ) && has_action( 'wpml_sync_custom_field' ) ) { do_action( 'wpml_sync_custom_field', (int) $w['post'], $w['key'] ); }
		}
		$tags = array();
		foreach ( $post_ids as $pid ) { foreach ( function_exists( 'topersia_pagecache_purge_tags_for' ) ? topersia_pagecache_purge_tags_for( (int) $pid, true ) : array() as $t ) { $tags[ $t ] = $t; } }
		if ( $tags ) { do_action( 'topersia_pagecache_purge', array_slice( array_values( $tags ), 0, 500 ) ); }
		if ( function_exists( 'topersia_geo_index_rebuild' ) ) { topersia_geo_index_rebuild(); }
	}

	/** rollback از لاگ — به ترتیب معکوس. */
	function tpf_rollback( $logfile ) {
		$j = json_decode( (string) file_get_contents( $logfile ), true );
		if ( ! $j || empty( $j['writes'] ) ) { return array( 'error' => 'لاگ نامعتبر' ); }
		$n = array( 'meta' => 0, 'rel' => 0, 'source' => 0 );
		$posts = array();
		foreach ( array_reverse( $j['writes'] ) as $w ) {
			if ( 'meta' === $w['kind'] ) {
				if ( $w['existed'] ) { update_post_meta( (int) $w['post'], $w['key'], wp_slash( $w['old'] ) ); } else { delete_post_meta( (int) $w['post'], $w['key'] ); }
				$posts[ (int) $w['post'] ] = 1; $n['meta']++;
			} elseif ( 'rel' === $w['kind'] ) {
				topersia_relation( (int) $w['rel'] )->delete_rows( (int) $w['parent'], (int) $w['child'], true );
				$posts[ (int) $w['parent'] ] = 1; $n['rel']++;
			} elseif ( 'source' === $w['kind'] ) {
				/* قاعدهٔ پروژه (P5-D109 · محافظ ردیف هسته): ردیف مکرر هرگز حذف نمی‌شود، بازنشسته می‌شود — پس برگشت، ردیف‌هایی را که apply افزود
				   «بازنشسته» می‌کند (از خروجی عمومی بیرون می‌رود) و چیز دیگری را دست نمی‌زند. */
				$cur  = get_post_meta( (int) $w['post'], 'source_references', true );
				$mine = array();
				foreach ( (array) $w['added'] as $a ) { $mine[ $a[0] . '|' . $a[1] ] = 1; }
				if ( is_array( $cur ) ) {
					foreach ( $cur as $k => $row ) { if ( is_array( $row ) && isset( $mine[ ( $row['source_name'] ?? '' ) . '|' . ( $row['source_url'] ?? '' ) ] ) ) { $cur[ $k ]['source_row_status'] = 'not_applicable'; } }
					update_post_meta( (int) $w['post'], 'source_references', $cur );
				}
				$posts[ (int) $w['post'] ] = 1; $n['source']++;
			}
		}
		$sync = array();
		foreach ( $j['writes'] as $w ) { if ( 'meta' === $w['kind'] ) { $sync[] = $w; } }
		tpf_after( array_keys( $posts ), $sync );
		if ( function_exists( 'topersia_log' ) ) { topersia_log( 'عددهای پایهٔ ویکی‌داده برگشت شد (P5-D394)', 'notice', 'import', array( 'meta' => $n['meta'], 'rel' => $n['rel'], 'source' => $n['source'], 'log' => basename( $logfile ) ) ); }
		return $n;
	}
}
