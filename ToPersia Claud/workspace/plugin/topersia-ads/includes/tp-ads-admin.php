<?php
/**
 * پنل «تبلیغات» — دستۀ 54 بند ۱۱.
 *
 *   تبلیغات ← گزارش          پالایش بازه، جایگاه و وضعیت؛ نمایش، کلیک و CTR؛ پایان نزدیک (۷ روز)؛ جایگاه‌های خالی؛ CSV
 *   تبلیغات ← همۀ تبلیغ‌ها   فهرست نوع tp_ad با ستون‌های کمپین؛ اقدام گروهی توقف و تمدید ۷ و ۳۰ روز
 * هر صفحه با توان manage_tp_ads؛ هر نوشتن با nonce. صفحه جهت پیشخوان را می‌گیرد (ویژگی منطقی، بی dir ثابت).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', function () {
	add_menu_page( __( 'تبلیغات', 'topersia-ads' ), __( 'تبلیغات', 'topersia-ads' ), TOPERSIA_ADS_CAP, 'topersia-ads', 'topersia_ads_report_page', 'dashicons-megaphone', 58 );
	add_submenu_page( 'topersia-ads', __( 'گزارش تبلیغات', 'topersia-ads' ), __( 'گزارش', 'topersia-ads' ), TOPERSIA_ADS_CAP, 'topersia-ads', 'topersia_ads_report_page' );
}, 9 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( 'toplevel_page_topersia-ads' === $hook || ( $screen && 'edit-tp_ad' === $screen->id ) ) {
		wp_enqueue_style( 'topersia-ads-admin', topersia_ads_url( 'assets/tp-ads-admin.css' ), array(), TOPERSIA_ADS_VERSION );
	}
} );

if ( ! function_exists( 'topersia_ads_status_label' ) ) {
	function topersia_ads_status_label( $s ) {
		$l = array(
			'active' => __( 'فعال', 'topersia-ads' ),
			'paused' => __( 'متوقف', 'topersia-ads' ),
			'ended'  => __( 'پایان‌یافته', 'topersia-ads' ),
		);
		return isset( $l[ $s ] ) ? $l[ $s ] : $s;
	}
}

if ( ! function_exists( 'topersia_ads_ctr' ) ) {
	/** CTR درصد با یک رقم اعشار؛ بی نمایش ← «—». */
	function topersia_ads_ctr( $imp, $clk ) {
		/* translators: %s: درصد */
		return $imp > 0 ? sprintf( __( '%s٪', 'topersia-ads' ), number_format_i18n( 100 * $clk / $imp, 1 ) ) : '—';
	}
}

if ( ! function_exists( 'topersia_ads_live_status' ) ) {
	/** وضعیت اکنون: فعال ولی هنوز شروع‌نشده یا گذشته یا سقف‌پر هم دیده شود. */
	function topersia_ads_live_status( array $ad, $now ) {
		if ( 'active' !== $ad['status'] ) {
			return topersia_ads_status_label( $ad['status'] );
		}
		if ( $ad['start'] && $now < $ad['start'] ) {
			return __( 'زمان‌بندی‌شده', 'topersia-ads' );
		}
		if ( $ad['end'] && $now >= $ad['end'] ) {
			return __( 'زمانش گذشته', 'topersia-ads' );
		}
		if ( ( $ad['cap_imp'] && $ad['imp'] >= $ad['cap_imp'] ) || ( $ad['cap_clk'] && $ad['clk'] >= $ad['cap_clk'] ) ) {
			return __( 'سقف پر شد', 'topersia-ads' );
		}
		return topersia_ads_status_label( 'active' );
	}
}

if ( ! function_exists( 'topersia_ads_empty_slots' ) ) {
	/** جایگاه‌هایی که اکنون در هیچ زبان و دستگاهی تبلیغ واقعیِ قابل‌نمایش ندارند (نشانۀ فروش). */
	function topersia_ads_empty_slots( $now ) {
		$real = array_filter( topersia_ads_all(), function ( $a ) {
			return ! $a['demo'];
		} );
		$out  = array();
		foreach ( topersia_ads_slots() as $id => $s ) {
			$pages = 'any' === $s['page'] ? topersia_ads_page_types() : array( $s['page'] );
			$found = false;
			foreach ( $real as $ad ) {
				foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
					foreach ( $pages as $p ) {
						foreach ( array( 'desktop', 'mobile' ) as $d ) {
							if ( topersia_ads_eligible( $ad, $id, array( 'now' => $now, 'lang' => $l, 'page' => $p, 'device' => $d, 'demo' => false ) ) ) {
								$found = true;
								break 4;
							}
						}
					}
				}
			}
			if ( ! $found ) {
				$out[ $id ] = $s;
			}
		}
		return $out;
	}
}

if ( ! function_exists( 'topersia_ads_report_args' ) ) {
	/** پالایش گزارش از GET، پاک‌شده. پیش‌فرض ۳۰ روز گذشته. */
	function topersia_ads_report_args() {
		$date = function ( $k, $def ) {
			$v = isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : '';   // phpcs:ignore WordPress.Security.NonceVerification
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ? $v : $def;
		};
		$slot   = isset( $_GET['slot'] ) ? sanitize_key( wp_unslash( $_GET['slot'] ) ) : '';   // phpcs:ignore WordPress.Security.NonceVerification
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';   // phpcs:ignore WordPress.Security.NonceVerification
		$from   = $date( 'from', wp_date( 'Y-m-d', time() - 29 * DAY_IN_SECONDS ) );
		$to     = $date( 'to', wp_date( 'Y-m-d' ) );
		return array(
			'from'   => min( $from, $to ),
			'to'     => max( $from, $to ),
			'slot'   => isset( topersia_ads_slots()[ $slot ] ) ? $slot : '',
			'status' => in_array( $status, array( 'active', 'paused', 'ended' ), true ) ? $status : '',
		);
	}
}

if ( ! function_exists( 'topersia_ads_report_rows' ) ) {
	/** ردیف‌های گزارش: هر تبلیغ (و جایگاهِ دارای آمار) با جمع بازه. */
	function topersia_ads_report_rows( array $args ) {
		$ads  = topersia_ads_all();
		$stat = array();
		foreach ( topersia_ads_report( $args['from'], $args['to'], $args['slot'] ) as $r ) {
			if ( ! isset( $stat[ $r['ad_id'] ] ) ) {
				$stat[ $r['ad_id'] ] = array( 'imp' => 0, 'clk' => 0 );
			}
			$stat[ $r['ad_id'] ]['imp'] += $r['imp'];
			$stat[ $r['ad_id'] ]['clk'] += $r['clk'];
		}
		$rows = array();
		foreach ( $ads as $id => $ad ) {
			if ( ( '' !== $args['status'] && $ad['status'] !== $args['status'] ) || ( '' !== $args['slot'] && ! in_array( $args['slot'], $ad['slots'], true ) ) ) {
				continue;
			}
			$rows[] = array(
				'ad'  => $ad,
				'imp' => isset( $stat[ $id ] ) ? $stat[ $id ]['imp'] : 0,
				'clk' => isset( $stat[ $id ] ) ? $stat[ $id ]['clk'] : 0,
			);
		}
		return $rows;
	}
}

if ( ! function_exists( 'topersia_ads_report_page' ) ) {
	function topersia_ads_report_page() {

		if ( ! current_user_can( TOPERSIA_ADS_CAP ) ) {
			wp_die( esc_html__( 'دسترسی ندارید.', 'topersia-ads' ), 403 );
		}

		$now   = time();
		$args  = topersia_ads_report_args();
		$rows  = topersia_ads_report_rows( $args );
		$slots = topersia_ads_slots();
		$soon  = array_filter( topersia_ads_all(), function ( $a ) use ( $now ) {
			return 'active' === $a['status'] && ! $a['demo'] && $a['end'] && $a['end'] > $now && $a['end'] <= $now + 7 * DAY_IN_SECONDS;
		} );
		$empty = topersia_ads_empty_slots( $now );
		$sum   = array( 'imp' => 0, 'clk' => 0 );

		echo '<div class="wrap tp-ads-wrap"><h1 class="wp-heading-inline">' . esc_html__( 'گزارش تبلیغات', 'topersia-ads' ) . '</h1> '
			. '<a class="page-title-action" href="' . esc_url( admin_url( 'post-new.php?post_type=tp_ad' ) ) . '">' . esc_html__( 'تبلیغ تازه', 'topersia-ads' ) . '</a>'
			. '<hr class="wp-header-end">';

		echo '<p class="tp-ads-note">' . esc_html__( 'آمار بی‌نام است: فقط جمع روزانۀ نمایش و کلیک به‌ازای تبلیغ، جایگاه و زبان؛ هیچ IP، کوکی یا شناسۀ کاربر نگه داشته نمی‌شود. نمایش یعنی دست‌کم نیمی از واحد یک ثانیه دیده شد.', 'topersia-ads' ) . '</p>';

		/* پالایش */
		echo '<form method="get" class="tp-ads-filter"><input type="hidden" name="page" value="topersia-ads">'
			. '<label>' . esc_html__( 'از', 'topersia-ads' ) . ' <input type="date" name="from" value="' . esc_attr( $args['from'] ) . '"></label>'
			. '<label>' . esc_html__( 'تا', 'topersia-ads' ) . ' <input type="date" name="to" value="' . esc_attr( $args['to'] ) . '"></label>'
			. '<label>' . esc_html__( 'جایگاه', 'topersia-ads' ) . ' <select name="slot"><option value="">' . esc_html__( 'همه', 'topersia-ads' ) . '</option>';
		foreach ( $slots as $id => $s ) {
			echo '<option value="' . esc_attr( $id ) . '"' . selected( $args['slot'], $id, false ) . '>' . esc_html( __( $s['name'], 'topersia-ads' ) ) . '</option>';   // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
		}
		echo '</select></label><label>' . esc_html__( 'وضعیت', 'topersia-ads' ) . ' <select name="status"><option value="">' . esc_html__( 'همه', 'topersia-ads' ) . '</option>';
		foreach ( array( 'active', 'paused', 'ended' ) as $s ) {
			echo '<option value="' . esc_attr( $s ) . '"' . selected( $args['status'], $s, false ) . '>' . esc_html( topersia_ads_status_label( $s ) ) . '</option>';
		}
		echo '</select></label> <button class="button">' . esc_html__( 'پالایش', 'topersia-ads' ) . '</button> '
			. '<a class="button" href="' . esc_url( wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'tp_ads_csv' ), $args ), admin_url( 'admin-post.php' ) ), 'tp_ads_csv' ) ) . '">' . esc_html__( 'دریافت CSV', 'topersia-ads' ) . '</a>'
			. '</form>';

		/* جدول */
		echo '<table class="widefat striped tp-ads-table"><thead><tr>'
			. '<th scope="col">' . esc_html__( 'تبلیغ', 'topersia-ads' ) . '</th><th scope="col">' . esc_html__( 'تبلیغ‌دهنده', 'topersia-ads' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'وضعیت', 'topersia-ads' ) . '</th><th scope="col">' . esc_html__( 'جایگاه‌ها', 'topersia-ads' ) . '</th>'
			. '<th scope="col">' . esc_html__( 'پایان', 'topersia-ads' ) . '</th><th scope="col" class="tp-ads-num">' . esc_html__( 'نمایش', 'topersia-ads' ) . '</th>'
			. '<th scope="col" class="tp-ads-num">' . esc_html__( 'کلیک', 'topersia-ads' ) . '</th><th scope="col" class="tp-ads-num">CTR</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="8">' . esc_html__( 'هیچ تبلیغی با این پالایش نیست.', 'topersia-ads' ) . '</td></tr>';
		}
		foreach ( $rows as $r ) {
			$ad          = $r['ad'];
			$sum['imp'] += $r['imp'];
			$sum['clk'] += $r['clk'];
			$names       = array_map( function ( $s ) use ( $slots ) {
				return __( $slots[ $s ]['name'], 'topersia-ads' );   // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
			}, $ad['slots'] );
			echo '<tr><td><a href="' . esc_url( get_edit_post_link( $ad['id'] ) ) . '">' . esc_html( '' !== $ad['title'] ? $ad['title'] : '#' . $ad['id'] ) . '</a>'
				. ( $ad['demo'] ? ' <span class="tp-ads-badge">' . esc_html__( 'نمونۀ نمایشی', 'topersia-ads' ) . '</span>' : '' ) . '</td>'
				. '<td>' . esc_html( $ad['advertiser'] ) . '</td><td>' . esc_html( topersia_ads_live_status( $ad, $now ) ) . '</td>'
				. '<td>' . esc_html( implode( '، ', $names ) ) . '</td>'
				. '<td>' . ( $ad['end'] ? esc_html( wp_date( get_option( 'date_format' ), $ad['end'] ) ) : '—' ) . '</td>'
				. '<td class="tp-ads-num">' . esc_html( number_format_i18n( $r['imp'] ) ) . '</td><td class="tp-ads-num">' . esc_html( number_format_i18n( $r['clk'] ) ) . '</td>'
				. '<td class="tp-ads-num">' . esc_html( topersia_ads_ctr( $r['imp'], $r['clk'] ) ) . '</td></tr>';
		}
		echo '</tbody><tfoot><tr><th scope="row" colspan="5">' . esc_html__( 'جمع بازه', 'topersia-ads' ) . '</th>'
			. '<td class="tp-ads-num">' . esc_html( number_format_i18n( $sum['imp'] ) ) . '</td><td class="tp-ads-num">' . esc_html( number_format_i18n( $sum['clk'] ) ) . '</td>'
			. '<td class="tp-ads-num">' . esc_html( topersia_ads_ctr( $sum['imp'], $sum['clk'] ) ) . '</td></tr></tfoot></table>';

		/* پایان نزدیک */
		echo '<h2>' . esc_html__( 'پایان نزدیک (۷ روز آینده)', 'topersia-ads' ) . '</h2>';
		if ( ! $soon ) {
			echo '<p>' . esc_html__( 'هیچ کمپین فعالی تا ۷ روز آینده تمام نمی‌شود.', 'topersia-ads' ) . '</p>';
		} else {
			echo '<ul class="tp-ads-list">';
			foreach ( $soon as $ad ) {
				echo '<li><a href="' . esc_url( get_edit_post_link( $ad['id'] ) ) . '">' . esc_html( $ad['title'] ) . '</a> — ' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ad['end'] ) ) . '</li>';
			}
			echo '</ul>';
		}

		/* جایگاه‌های خالی */
		echo '<h2>' . esc_html__( 'جایگاه‌های خالی (برای فروش)', 'topersia-ads' ) . '</h2>';
		if ( ! $empty ) {
			echo '<p>' . esc_html__( 'همۀ جایگاه‌ها اکنون تبلیغ دارند.', 'topersia-ads' ) . '</p>';
		} else {
			echo '<ul class="tp-ads-list">';
			foreach ( $empty as $id => $s ) {
				echo '<li>' . esc_html( __( $s['name'], 'topersia-ads' ) ) . ' <bdi dir="ltr" class="tp-ads-dim">' . esc_html( $id ) . '</bdi></li>';   // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
			}
			echo '</ul>';
		}

		echo '</div>';
	}
}

if ( ! function_exists( 'topersia_ads_csv_cell' ) ) {
	/** خانۀ CSV بی تزریق فرمول (= + - @ در آغاز). */
	function topersia_ads_csv_cell( $v ) {
		$v = (string) $v;
		return preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v;
	}
}

add_action( 'admin_post_tp_ads_csv', function () {
	if ( ! current_user_can( TOPERSIA_ADS_CAP ) ) {
		wp_die( esc_html__( 'دسترسی ندارید.', 'topersia-ads' ), 403 );
	}
	check_admin_referer( 'tp_ads_csv' );

	$args = topersia_ads_report_args();
	$ads  = topersia_ads_all();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="topersia-ads-' . $args['from'] . '-' . $args['to'] . '.csv"' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'ad_id', 'title', 'advertiser', 'slot', 'from', 'to', 'impressions', 'clicks', 'ctr_percent' ) );
	foreach ( topersia_ads_report( $args['from'], $args['to'], $args['slot'] ) as $r ) {
		$ad = isset( $ads[ $r['ad_id'] ] ) ? $ads[ $r['ad_id'] ] : array( 'title' => '', 'advertiser' => '', 'status' => '' );
		if ( '' !== $args['status'] && $ad['status'] !== $args['status'] ) {
			continue;
		}
		fputcsv( $out, array_map( 'topersia_ads_csv_cell', array(
			$r['ad_id'], $ad['title'], $ad['advertiser'], $r['slot'], $args['from'], $args['to'], $r['imp'], $r['clk'],
			$r['imp'] > 0 ? round( 100 * $r['clk'] / $r['imp'], 2 ) : '',
		) ) );
	}
	fclose( $out );
	exit;
} );

/* ── فهرست tp_ad: ستون‌ها و اقدام گروهی ─────────────────────────────── */

add_filter( 'manage_tp_ad_posts_columns', function ( $cols ) {
	$out = array();
	foreach ( $cols as $k => $v ) {
		$out[ $k ] = $v;
		if ( 'title' === $k ) {
			$out['tp_ad_adv']    = __( 'تبلیغ‌دهنده', 'topersia-ads' );
			$out['tp_ad_status'] = __( 'وضعیت', 'topersia-ads' );
			$out['tp_ad_slots']  = __( 'جایگاه‌ها', 'topersia-ads' );
			$out['tp_ad_end']    = __( 'پایان', 'topersia-ads' );
			$out['tp_ad_imp']    = __( 'نمایش', 'topersia-ads' );
			$out['tp_ad_clk']    = __( 'کلیک', 'topersia-ads' );
			$out['tp_ad_ctr']    = 'CTR';
		}
	}
	unset( $out['date'] );
	return $out;
} );

add_action( 'manage_tp_ad_posts_custom_column', function ( $col, $id ) {
	$ad    = topersia_ads_norm( topersia_ads_raw( $id ) );
	$slots = topersia_ads_slots();
	switch ( $col ) {
		case 'tp_ad_adv':
			echo esc_html( $ad['advertiser'] );
			break;
		case 'tp_ad_status':
			echo esc_html( topersia_ads_live_status( $ad, time() ) ) . ( $ad['demo'] ? ' <span class="tp-ads-badge">' . esc_html__( 'نمونۀ نمایشی', 'topersia-ads' ) . '</span>' : '' );
			break;
		case 'tp_ad_slots':
			echo esc_html( implode( '، ', array_map( function ( $s ) use ( $slots ) {
				return __( $slots[ $s ]['name'], 'topersia-ads' );   // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
			}, $ad['slots'] ) ) );
			break;
		case 'tp_ad_end':
			echo $ad['end'] ? esc_html( wp_date( get_option( 'date_format' ), $ad['end'] ) ) : '—';
			break;
		case 'tp_ad_imp':
			echo esc_html( number_format_i18n( $ad['imp'] ) );
			break;
		case 'tp_ad_clk':
			echo esc_html( number_format_i18n( $ad['clk'] ) );
			break;
		case 'tp_ad_ctr':
			echo esc_html( topersia_ads_ctr( $ad['imp'], $ad['clk'] ) );
			break;
	}
}, 10, 2 );

add_filter( 'bulk_actions-edit-tp_ad', function ( $a ) {
	$a['tp_ads_pause']    = __( 'توقف', 'topersia-ads' );
	$a['tp_ads_extend7']  = __( 'تمدید ۷ روز', 'topersia-ads' );
	$a['tp_ads_extend30'] = __( 'تمدید ۳۰ روز', 'topersia-ads' );
	return $a;
} );

if ( ! function_exists( 'topersia_ads_bulk' ) ) {
	/**
	 * اقدام گروهی روی متای کمپین. تمدید از پایانِ فعلی یا اکنون (هر کدام دیرتر)؛ کمپین بی پایان بی‌تغییر می‌ماند.
	 *
	 * @return int شمار تغییرکرده
	 */
	function topersia_ads_bulk( $action, array $ids, $now = null ) {
		$now  = null === $now ? time() : (int) $now;
		$days = array( 'tp_ads_extend7' => 7, 'tp_ads_extend30' => 30 );
		$n    = 0;
		foreach ( array_map( 'absint', $ids ) as $id ) {
			if ( 'tp_ad' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}
			$raw = get_post_meta( $id, '_tp_ad', true );
			$raw = is_array( $raw ) ? $raw : array();
			if ( 'tp_ads_pause' === $action ) {
				$raw['status'] = 'paused';
			} elseif ( isset( $days[ $action ] ) && ! empty( $raw['end'] ) ) {
				$raw['end'] = max( (int) $raw['end'], $now ) + $days[ $action ] * DAY_IN_SECONDS;
				if ( isset( $raw['status'] ) && 'ended' === $raw['status'] ) {
					$raw['status'] = 'paused';   /* تمدیدِ پایان‌یافته دوباره بررسی می‌خواهد — فعال‌کردن دستی */
				}
			} else {
				continue;
			}
			update_post_meta( $id, '_tp_ad', $raw );
			$n++;
		}
		if ( $n ) {
			topersia_ads_bump();
		}
		return $n;
	}
}

add_filter( 'handle_bulk_actions-edit-tp_ad', function ( $redirect, $action, $ids ) {
	if ( ! in_array( $action, array( 'tp_ads_pause', 'tp_ads_extend7', 'tp_ads_extend30' ), true ) || ! current_user_can( TOPERSIA_ADS_CAP ) ) {
		return $redirect;
	}
	/* nonce فهرست را خودِ وردپرس (bulk-posts) پیش از این صافی سنجیده است */
	return add_query_arg( 'tp_ads_done', topersia_ads_bulk( $action, (array) $ids ), $redirect );
}, 10, 3 );

add_action( 'admin_notices', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'edit-tp_ad' === $screen->id && isset( $_GET['tp_ads_done'] ) ) {   // phpcs:ignore WordPress.Security.NonceVerification
		/* translators: %s: شمار */
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( __( 'انجام شد: %s تبلیغ.', 'topersia-ads' ), number_format_i18n( absint( $_GET['tp_ads_done'] ) ) ) ) . '</p></div>';   // phpcs:ignore WordPress.Security.NonceVerification
	}
} );
