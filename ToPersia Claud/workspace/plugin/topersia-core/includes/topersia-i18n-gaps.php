<?php
/**
 * ToPersia Core — گزارش شکاف ترجمه (بخش ۱۹-ز · وصلۀ ۲ج و۱ · P5-D305)
 *
 * دروازۀ زبان ۲ج٫۸ (render) فیلدی را که در زبان صفحه مقدار ندارد یا هنوز فارسی
 * است نشان نمی‌دهد — تور ایمنی، نه درمان. این گزارش همان پنهان‌شده‌ها را دیدنی
 * می‌کند، برای هر زبان فعال WPML جز زبان پیش‌فرض:
 *
 *   ۱. پست فارسی منتشرشده بی ترجمه در آن زبان (شمار به تفکیک نوع)
 *   ۲. ترجمۀ موجود با عنوان، متن یا فیلد کلاس «translate» (از تنظیمات واقعی
 *      WPML، همان topersia_pack_tr_keys) که خالی است، با مقدار فارسی برابر است،
 *      یا خطش با زبان نمی‌خواند — همان تشخیص topersia_g_foreign_text با
 *      اصلاح پایان فاز (آرایه: فقط مقدارها، نه کلیدهای لاتین زیرفیلد)
 *
 * در لحظه از داده؛ بی جدول و متای تازه؛ هیچ نوشتنی — نه این‌جا و نه هنگام
 * نمایش صفحه. مقدار فقط با ترجمه پر می‌شود: «برون‌بری قالب ترجمه برای این
 * شکاف‌ها» همان برون‌بری ۲ج٫۹ است، فقط پست‌ها و فیلدهای شکاف‌دار.
 *
 *   wp topersia i18n-gaps [--lang=<code>] [--type=<type>] [--format=table|csv|json]
 *
 * @package topersia-core
 */

defined( 'ABSPATH' ) || exit;

/** متن یک مقدار — آرایه و شیء: فقط مقدارهای نرده‌ای، بی کلید و بی برچسب HTML. */
function topersia_i18n_text( $value ) {

	if ( is_array( $value ) || is_object( $value ) ) {
		$parts = array();
		array_walk_recursive( $value, function ( $v ) use ( &$parts ) {
			if ( is_scalar( $v ) ) {
				$parts[] = (string) $v;
			}
		} );
		$value = implode( ' ', $parts );
	}

	return trim( wp_strip_all_tags( (string) $value ) );
}

/**
 * متن به خطی جز زبان؟ — همان تشخیص topersia_g_foreign_text (render ۲ج٫۸)، با
 * زبان صریح به‌جای زبان صفحه. زبان پیش‌فرض: هرگز. عربی: بیش از سه درصد حروف
 * عربی‌خط ویژۀ فارسی (پ چ ژ گ ی ک). بقیه: حرف عربی‌خط بیش از لاتین.
 */
function topersia_i18n_foreign( $value, $lang ) {

	$text = topersia_i18n_text( $value );

	if ( '' === $text || topersia_pack_default_language() === $lang ) {
		return false;
	}

	$arabic = (int) preg_match_all( '/\p{Arabic}/u', $text );

	if ( ! $arabic ) {
		return false;
	}

	if ( 'ar' === $lang ) {
		return (int) preg_match_all( '/[\x{067E}\x{0686}\x{0698}\x{06AF}\x{06CC}\x{06A9}]/u', $text ) > 0.03 * $arabic;
	}

	return $arabic > (int) preg_match_all( '/\p{Latin}/u', $text );
}

/**
 * چرا یک خانۀ ترجمه شکاف است — '' یعنی نیست.
 *
 * empty  مقدار فارسی هست، ترجمه خالی
 * same   برابر مقدار فارسی — فقط اگر فارسی چیزی برای ترجمه دارد (حرف عربی‌خط)؛
 *        نشانی، عدد و نام لاتینی که در هر زبان همان می‌ماند شکاف نیست
 * script خطش با زبان نمی‌خواند
 */
function topersia_i18n_reason( $source, $value, $lang ) {

	$src = topersia_i18n_text( $source );

	if ( '' === $src ) {
		return '';
	}

	if ( '' === topersia_i18n_text( $value ) ) {
		return 'empty';
	}

	if ( topersia_pack_same( $source, $value ) && preg_match( '/\p{Arabic}/u', $src ) ) {
		return 'same';
	}

	return topersia_i18n_foreign( $value, $lang ) ? 'script' : '';
}

/** برچسب خوانای دلیل. */
function topersia_i18n_reason_label( $reason ) {

	$labels = array(
		'missing' => __( 'بی ترجمه', 'topersia-core' ),
		'empty'   => __( 'خالی', 'topersia-core' ),
		'same'    => __( 'برابر فارسی', 'topersia-core' ),
		'script'  => __( 'خط دیگر', 'topersia-core' ),
	);

	return isset( $labels[ $reason ] ) ? $labels[ $reason ] : $reason;
}

/**
 * $fn در زبان دیگری از WPML، و بازگرداندن زبان پیشین — حتی با خطا.
 * فقط قلاب‌های مستند WPML (wpml_current_language · wpml_switch_language).
 */
function topersia_i18n_in_lang( $lang, callable $fn ) {

	$current = has_filter( 'wpml_current_language' ) ? apply_filters( 'wpml_current_language', null ) : null;

	if ( ! $current || $current === $lang ) {
		return $fn();
	}

	do_action( 'wpml_switch_language', $lang );

	try {
		return $fn();
	} finally {
		do_action( 'wpml_switch_language', $current );
	}
}

/** شناسۀ پست‌های فارسیِ منتشرشدۀ یک نوع — در زبان پیش‌فرض، هر زبانی که درخواست دارد. */
function topersia_i18n_originals( $type ) {

	return topersia_i18n_in_lang( topersia_pack_default_language(), function () use ( $type ) {
		return array_map( 'intval', get_posts( array(
			'post_type'        => $type,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'suppress_filters' => false,
		) ) );
	} );
}

/** خانه‌های شکاف‌دار یک ترجمه: کلید ← دلیل (عنوان post_title · متن post_content · فیلدهای translate). */
function topersia_i18n_post_gaps( $original, $translation, $lang ) {

	$type   = get_post_type( $original );
	$schema = topersia_pack_schema( $type );
	$out    = array();

	foreach ( array( 'post_title', 'post_content' ) as $col ) {
		$why = topersia_i18n_reason( get_post_field( $col, $original ), get_post_field( $col, $translation ), $lang );
		if ( '' !== $why ) {
			$out[ $col ] = $why;
		}
	}

	foreach ( topersia_pack_tr_keys( $type ) as $key ) {
		$def = $schema['fields'][ $key ];
		$why = topersia_i18n_reason( topersia_pack_current( $original, $key, $def ), topersia_pack_current( $translation, $key, $def ), $lang );
		if ( '' !== $why ) {
			$out[ $key ] = $why;
		}
	}

	return $out;
}

/**
 * گزارش شکاف — زبان ← نوع ← { missing: [شناسۀ فارسی], partial: [ { original, translation, status, fields } ] }.
 *
 * ترجمۀ پیش‌نویس هم «ترجمۀ موجود» است (هنوز منتشر نشده، ولی کسی آن را ساخته)؛
 * ترجمۀ در زباله «بی ترجمه».
 *
 * @param string $lang زبان؛ خالی = همۀ زبان‌های ترجمه.
 * @param string $type نوع؛ خالی = همۀ نوع‌های موجودیت.
 */
function topersia_i18n_gaps( $lang = '', $type = '' ) {

	$langs = topersia_pack_tr_languages();
	$langs = '' !== $lang ? array_values( array_intersect( $langs, array( $lang ) ) ) : $langs;
	$types = '' !== $type ? array_intersect_key( topersia_pack_types(), array( $type => 1 ) ) : topersia_pack_types();
	$out   = array();

	foreach ( $langs as $code ) {

		$out[ $code ] = array();

		foreach ( array_keys( $types ) as $ptype ) {

			$row = array( 'missing' => array(), 'partial' => array() );

			foreach ( topersia_i18n_originals( $ptype ) as $id ) {

				$tr = topersia_pack_tr_of( $id, $code );

				if ( ! $tr || 'trash' === get_post_status( $tr ) ) {
					$row['missing'][] = $id;
					continue;
				}

				$fields = topersia_i18n_post_gaps( $id, $tr, $code );

				if ( $fields ) {
					$row['partial'][] = array( 'original' => $id, 'translation' => $tr, 'status' => (string) get_post_status( $tr ), 'fields' => $fields );
				}
			}

			if ( $row['missing'] || $row['partial'] ) {
				$out[ $code ][ $ptype ] = $row;
			}
		}
	}

	return $out;
}

/** شمار شکاف هر زبان: پست بی ترجمه · ترجمۀ شکاف‌دار · خانۀ شکاف‌دار. */
function topersia_i18n_gap_counts( $gaps ) {

	$out = array();

	foreach ( $gaps as $lang => $types ) {
		$out[ $lang ] = array( 'missing' => 0, 'partial' => 0, 'cells' => 0 );
		foreach ( $types as $row ) {
			$out[ $lang ]['missing'] += count( $row['missing'] );
			$out[ $lang ]['partial'] += count( $row['partial'] );
			foreach ( $row['partial'] as $p ) {
				$out[ $lang ]['cells'] += count( $p['fields'] );
			}
		}
	}

	return $out;
}

/**
 * قالب ترجمۀ ۲ج٫۹ فقط برای شکاف‌های یک زبان و یک نوع: پست‌های بی ترجمه با همۀ
 * فیلدهای translate که فارسی‌شان مقدار دارد، و ترجمه‌های شکاف‌دار فقط با همان
 * فیلدهای شکاف‌دار. عنوان و متن همیشه هستند — درون‌ریز «بی‌تغییر» را رد می‌کند.
 */
function topersia_i18n_gap_export( $lang, $type ) {

	$gaps = topersia_i18n_gaps( $lang, $type );

	if ( empty( $gaps[ $lang ][ $type ] ) ) {
		return null;
	}

	$row  = $gaps[ $lang ][ $type ];
	$keep = array();

	foreach ( $row['missing'] as $id ) {
		$keep[ $id ] = null;   /* همۀ فیلدهای دارای مقدار فارسی */
	}
	foreach ( $row['partial'] as $p ) {
		$keep[ $p['original'] ] = array_keys( $p['fields'] );
	}

	$body = topersia_pack_tr_export( array_keys( $keep ), $lang );

	if ( ! $body ) {
		return null;
	}

	$union = array();

	foreach ( $body['posts'] as $i => $post ) {

		$id     = (int) topersia_i18n_original_of( $post['translation_of'], $type );
		$fields = get_object_vars( $post['fields'] );
		$only   = isset( $keep[ $id ] ) ? $keep[ $id ] : null;

		foreach ( $fields as $key => $pair ) {
			$wanted = null === $only ? '' !== topersia_i18n_text( $pair['source'] ) : in_array( $key, $only, true );
			if ( ! $wanted ) {
				unset( $fields[ $key ] );
			} else {
				$union[ $key ] = true;
			}
		}

		$body['posts'][ $i ]['fields'] = (object) $fields;
	}

	$body['fields']        = (object) array_intersect_key( get_object_vars( $body['fields'] ), $union );
	$body['meta']['scope'] = 'gaps';

	return $body;
}

/** شناسۀ پست فارسی از translation_of قالب (entity_uid یا نامک). */
function topersia_i18n_original_of( $ref, $type ) {

	$found = topersia_pack_find( $type, $ref, $ref, topersia_pack_default_language() );

	return 1 === count( $found ) ? (int) $found[0] : 0;
}

/** کارت «گزارش شکاف ترجمه» در بخش درون‌ریزی ترجمۀ زبانۀ بستۀ محتوا — فقط با درخواست. */
function topersia_i18n_gaps_card( $export, $nonce ) {

	$show = isset( $_GET['gaps'] ) && '1' === $_GET['gaps']; // phpcs:ignore WordPress.Security.NonceVerification -- فقط خواندن
	$url  = add_query_arg( array( 'page' => 'topersia', 'tab' => 'pack', 'gaps' => '1' ), admin_url( 'admin.php' ) ) . '#tp-pack-gaps';

	echo '<h2 id="tp-pack-gaps">' . esc_html__( 'گزارش شکاف ترجمه', 'topersia-core' ) . '</h2>';
	echo '<p style="max-width:62em">' . esc_html__( 'آنچه صفحۀ انگلیسی و عربی پنهان می‌کند: پست فارسی منتشرشده بی ترجمه، و ترجمه‌ای که عنوان، متن یا فیلد کلاس translate آن خالی است، با فارسی برابر است یا خطش با زبان نمی‌خواند. در لحظه از داده؛ هیچ چیز نوشته نمی‌شود. مقدار فقط با ترجمه پر می‌شود.', 'topersia-core' ) . ' <code>wp topersia i18n-gaps</code></p>';

	if ( ! $show ) {
		echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'ساختن گزارش', 'topersia-core' ) . '</a></p>';
		return;
	}

	$gaps   = topersia_i18n_gaps();
	$counts = topersia_i18n_gap_counts( $gaps );
	$types  = topersia_pack_types();

	foreach ( $gaps as $lang => $rows ) {

		/* translators: 1: زبان، 2: پست بی ترجمه، 3: ترجمۀ شکاف‌دار، 4: خانۀ شکاف‌دار */
		echo '<h3>' . esc_html( sprintf( __( '%1$s — %2$s پست بی ترجمه · %3$s ترجمۀ شکاف‌دار (%4$s خانه)', 'topersia-core' ), $lang, number_format_i18n( $counts[ $lang ]['missing'] ), number_format_i18n( $counts[ $lang ]['partial'] ), number_format_i18n( $counts[ $lang ]['cells'] ) ) ) . '</h3>';

		if ( ! $rows ) {
			echo '<p>' . esc_html__( 'شکافی نیست.', 'topersia-core' ) . '</p>';
			continue;
		}

		echo '<table class="widefat striped" style="max-width:72rem"><thead><tr><th>' . esc_html__( 'نوع', 'topersia-core' ) . '</th><th>' . esc_html__( 'بی ترجمه', 'topersia-core' ) . '</th><th>' . esc_html__( 'ترجمۀ شکاف‌دار', 'topersia-core' ) . '</th><th></th></tr></thead><tbody>';

		foreach ( $rows as $type => $row ) {

			$detail = array();
			foreach ( $row['partial'] as $p ) {
				$cells = array();
				foreach ( $p['fields'] as $key => $why ) {
					$cells[] = $key . ' (' . topersia_i18n_reason_label( $why ) . ')';
				}
				$detail[] = '<a href="' . esc_url( (string) get_edit_post_link( $p['translation'], 'raw' ) ) . '">#' . (int) $p['translation'] . '</a> ' . esc_html( get_the_title( $p['original'] ) . ' · ' . $p['status'] . ' · ' . implode( '، ', $cells ) );
			}

			$link = add_query_arg( array( 'action' => 'topersia_pack_export', '_wpnonce' => $nonce, 'what' => 'gaps', 'tlang' => $lang, 'type' => $type ), $export );

			printf(
				'<tr><td>%s<br><code>%s</code></td><td>%s</td><td>%s</td><td><a class="button" href="%s">%s</a></td></tr>',
				esc_html( isset( $types[ $type ] ) ? $types[ $type ] : $type ),
				esc_html( $type ),
				esc_html( number_format_i18n( count( $row['missing'] ) ) ),
				$detail ? implode( '<br>', $detail ) : '—', // phpcs:ignore WordPress.Security.EscapeOutput -- هر تکه گریخته
				esc_url( $link ),
				esc_html__( 'برون‌بری قالب ترجمه برای این شکاف‌ها', 'topersia-core' )
			);
		}

		echo '</tbody></table>';
	}
}

/* ══ WP-CLI ═════════════════════════════════════════════════════════════ */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * گزارش شکاف ترجمه — P5-D305.
	 *
	 * [--lang=<code>]
	 * : فقط این زبان (پیش‌فرض: همۀ زبان‌های ترجمۀ فعال WPML).
	 *
	 * [--type=<type>]
	 * : فقط این نوع، با فهرست پست به پست.
	 *
	 * [--format=<format>]
	 * : table · csv · json
	 * ---
	 * default: table
	 * ---
	 */
	WP_CLI::add_command( 'topersia i18n-gaps', function ( $args, $assoc ) {

		unset( $args );

		$lang   = isset( $assoc['lang'] ) ? sanitize_key( $assoc['lang'] ) : '';
		$type   = isset( $assoc['type'] ) ? sanitize_key( $assoc['type'] ) : '';
		$format = isset( $assoc['format'] ) ? (string) $assoc['format'] : 'table';

		if ( '' !== $lang && ! in_array( $lang, topersia_pack_tr_languages(), true ) ) {
			WP_CLI::error( 'زبان ترجمۀ فعال WPML نیست: ' . $lang . ' (' . implode( '، ', topersia_pack_tr_languages() ) . ')' );
		}

		if ( '' !== $type && ! isset( topersia_pack_types()[ $type ] ) ) {
			WP_CLI::error( 'نوع موجودیت نیست: ' . $type );
		}

		$gaps = topersia_i18n_gaps( $lang, $type );

		if ( 'json' === $format ) {
			WP_CLI::line( wp_json_encode( array( 'counts' => topersia_i18n_gap_counts( $gaps ), 'gaps' => $gaps ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
			return;
		}

		$items = array();

		foreach ( $gaps as $code => $rows ) {
			foreach ( $rows as $ptype => $row ) {
				if ( '' === $type ) {
					$cells = 0;
					foreach ( $row['partial'] as $p ) {
						$cells += count( $p['fields'] );
					}
					$items[] = array( 'lang' => $code, 'type' => $ptype, 'missing' => count( $row['missing'] ), 'partial' => count( $row['partial'] ), 'cells' => $cells );
					continue;
				}
				foreach ( $row['missing'] as $id ) {
					$items[] = array( 'lang' => $code, 'original' => $id, 'title' => get_the_title( $id ), 'translation' => '', 'status' => '', 'gaps' => 'missing' );
				}
				foreach ( $row['partial'] as $p ) {
					$cells = array();
					foreach ( $p['fields'] as $key => $why ) {
						$cells[] = $key . ':' . $why;
					}
					$items[] = array( 'lang' => $code, 'original' => $p['original'], 'title' => get_the_title( $p['original'] ), 'translation' => $p['translation'], 'status' => $p['status'], 'gaps' => implode( ' ', $cells ) );
				}
			}
		}

		$columns = '' === $type ? array( 'lang', 'type', 'missing', 'partial', 'cells' ) : array( 'lang', 'original', 'title', 'translation', 'status', 'gaps' );

		if ( ! $items ) {
			WP_CLI::success( 'شکافی نیست.' );
			return;
		}

		WP_CLI\Utils\format_items( in_array( $format, array( 'table', 'csv' ), true ) ? $format : 'table', $items, $columns );
	} );
}
