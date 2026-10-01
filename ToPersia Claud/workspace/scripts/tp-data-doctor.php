<?php
/**
 * tp-data-doctor — تشخیص و اصلاح ایرادهای دادهٔ دستهٔ G
 *
 * اجرا:
 *   wp eval-file scripts/tp-data-doctor.php -- --report
 *   wp eval-file scripts/tp-data-doctor.php -- --fix-titles --dry
 *   wp eval-file scripts/tp-data-doctor.php -- --fix-titles
 *   wp eval-file scripts/tp-data-doctor.php -- --relations 71,96
 *
 * ── چرا این فایل وجود دارد ────────────────────────────────────────────
 * سه ایراد باز، هر سه در دیتابیس‌اند نه در کد، و هیچ‌کدام از بیرون
 * قابل دیدن نبودند تا اینکه سورس رندرشدهٔ صفحه خوانده شد.
 *
 * ── قطبیت ─────────────────────────────────────────────────────────────
 * پیش‌فرض، فقط گزارش. هیچ نوشتنی بدون پرچم صریح. و --dry همیشه پیش از
 * اجرای واقعی، چون ۱۹۴٬۰۰۰ صفحه جای «بیا امتحان کنیم» نیست.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "این فایل فقط با wp eval-file اجرا می‌شود.\n" );
}

$args      = $args ?? array();
$do_report = in_array( '--report', $args, true );
$fix_title = in_array( '--fix-titles', $args, true );
$dry       = in_array( '--dry', $args, true );

$rel_arg = '';
foreach ( $args as $i => $one ) {
	if ( '--relations' === $one && isset( $args[ $i + 1 ] ) ) {
		$rel_arg = $args[ $i + 1 ];
	}
}

if ( ! $do_report && ! $fix_title && '' === $rel_arg ) {
	$do_report = true;
}

/** سیزده نوع پست دستهٔ G. شهرستان صفحه ندارد ولی داده‌اش هست. P5-D172 */
$G = array(
	'tp_country', 'tp_region', 'tp_province', 'tp_county', 'tp_city',
	'tp_district', 'tp_village', 'tp_island', 'tp_mountain',
	'tp_water_body', 'tp_desert', 'tp_forest_plain', 'tp_border_crossing',
);

/**
 * نام درست یک رکورد: نام رسمی فارسی، وگرنه نام مشترک فارسی.
 * هیچ‌وقت از عنوان پست — چون همان چیزی است که خراب است.
 */
function tp_doctor_name( $post_id ) {

	foreach ( array( 'official_name_fa', 'name_fa' ) as $key ) {

		$value = get_post_meta( $post_id, $key, true );
		$value = is_string( $value ) ? trim( wp_strip_all_tags( $value ) ) : '';

		if ( '' !== $value ) {
			return $value;
		}
	}

	return '';
}

/** آیا این عنوان مشکوک است؟ */
function tp_doctor_bad_title( $title, $name ) {

	$title = trim( (string) $title );

	if ( '' === $title ) {
		return 'خالی';
	}

	/* فقط رقم و صفر پیشوند — مثل 04 که کد استان است، نه نام. */
	if ( preg_match( '/^[0-9]{1,4}$/', $title ) ) {
		return 'فقط عدد';
	}

	/* هیچ حرف فارسی ندارد ولی نام فارسی موجود است. */
	if ( '' !== $name && ! preg_match( '/[\x{0600}-\x{06FF}]/u', $title ) ) {
		return 'بدون حرف فارسی';
	}

	/* خط مخلوط — مثل «شهرستان Meymeh and Vazvan». */
	if ( preg_match( '/[\x{0600}-\x{06FF}]/u', $title ) && preg_match( '/[A-Za-z]{3,}/', $title ) ) {
		return 'خط مخلوط';
	}

	return '';
}

/* =========================================================================
   گزارش
   ========================================================================= */

if ( $do_report ) {

	WP_CLI::log( '' );
	WP_CLI::log( '══ عنوان پست ══════════════════════════════════════════' );

	$total = 0;
	$bad   = 0;

	foreach ( $G as $type ) {

		$ids = get_posts( array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		if ( empty( $ids ) ) {
			continue;
		}

		$type_bad = array();

		foreach ( $ids as $id ) {

			$total ++;
			$name   = tp_doctor_name( $id );
			$reason = tp_doctor_bad_title( get_post_field( 'post_title', $id ), $name );

			if ( '' !== $reason ) {
				$bad ++;
				$type_bad[] = sprintf(
					'    #%d  «%s»  → %s  (نام موجود: %s)',
					$id,
					get_post_field( 'post_title', $id ),
					$reason,
					'' !== $name ? $name : '— ندارد —'
				);
			}
		}

		WP_CLI::log( sprintf( '  %-20s %4d رکورد، %d مشکوک', $type, count( $ids ), count( $type_bad ) ) );

		foreach ( array_slice( $type_bad, 0, 5 ) as $line ) {
			WP_CLI::log( $line );
		}

		if ( count( $type_bad ) > 5 ) {
			WP_CLI::log( sprintf( '    … و %d مورد دیگر', count( $type_bad ) - 5 ) );
		}
	}

	WP_CLI::log( sprintf( '  جمع: %d رکورد، %d عنوان مشکوک', $total, $bad ) );

	WP_CLI::log( '' );
	WP_CLI::log( '══ روابط ══════════════════════════════════════════════' );

	if ( ! function_exists( 'topersia_relation' ) ) {

		WP_CLI::warning( 'topersia_relation در دسترس نیست — افزونهٔ هسته فعال است؟' );

	} else {

		/* ۷۱ کشور↔استان · ۹۶ مرکز استان · ۷۲ استان↔شهرستان (شاهد سالم) */
		foreach ( array( 71 => 'کشور ↔ استان', 96 => 'مرکز استان', 72 => 'استان ↔ شهرستان' ) as $rid => $label ) {

			$relation = topersia_relation( $rid );

			if ( ! $relation ) {
				WP_CLI::log( sprintf( '  رابطهٔ %-4d %-22s ⛔ ثبت نشده یا غیرفعال', $rid, $label ) );
				continue;
			}

			$sample = get_posts( array(
				'post_type'      => 'tp_province',
				'post_status'    => 'any',
				'posts_per_page' => 5,
				'fields'         => 'ids',
			) );

			$kids    = 0;
			$parents = 0;

			foreach ( $sample as $one ) {
				$c = $relation->get_children( $one, 'ids' );
				$p = $relation->get_parents( $one, 'ids' );
				$kids    += is_array( $c ) ? count( $c ) : 0;
				$parents += is_array( $p ) ? count( $p ) : 0;
			}

			WP_CLI::log( sprintf(
				'  رابطهٔ %-4d %-22s فرزند: %-4d  والد: %-4d  (نمونهٔ %d استان)',
				$rid, $label, $kids, $parents, count( $sample )
			) );
		}
	}

	WP_CLI::log( '' );
	WP_CLI::log( '══ متاهای خالی که ناحیه را ناپدید می‌کنند ═════════════' );

	$watch = array( 'short_summary', 'climate_type', 'geo_boundary', 'hero_image', 'faq_items', 'source_references' );
	$prov  = get_posts( array( 'post_type' => 'tp_province', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );

	foreach ( $watch as $key ) {

		$empty = 0;

		foreach ( $prov as $one ) {
			$v = get_post_meta( $one, $key, true );
			if ( '' === $v || array() === $v || null === $v ) {
				$empty ++;
			}
		}

		WP_CLI::log( sprintf( '  %-20s %d از %d استان خالی', $key, $empty, count( $prov ) ) );
	}

	WP_CLI::log( '' );
}

/* =========================================================================
   اصلاح عنوان
   ========================================================================= */

if ( $fix_title ) {

	WP_CLI::log( '' );
	WP_CLI::log( $dry ? '══ آزمایشی — هیچ نوشتنی ══════════════════════' : '══ نوشتن واقعی ══════════════════════════════' );

	$changed = 0;
	$skipped = 0;

	foreach ( $G as $type ) {

		$ids = get_posts( array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		foreach ( $ids as $id ) {

			$old    = get_post_field( 'post_title', $id );
			$name   = tp_doctor_name( $id );
			$reason = tp_doctor_bad_title( $old, $name );

			if ( '' === $reason ) {
				continue;
			}

			/* نام درست نداریم — دست نمی‌زنیم. عنوان بد بهتر از عنوان خالی است. */
			if ( '' === $name ) {
				$skipped ++;
				WP_CLI::log( sprintf( '  ⏭  #%d «%s» — نام فارسی ندارد، رد شد', $id, $old ) );
				continue;
			}

			if ( $name === $old ) {
				continue;
			}

			WP_CLI::log( sprintf( '  ✏  #%-6d «%s»  →  «%s»', $id, $old, $name ) );

			if ( ! $dry ) {

				$result = wp_update_post( array(
					'ID'         => $id,
					'post_title' => $name,
					/* نامک عمداً دست نمی‌خورد — نشانی‌ها نباید عوض شوند. */
				), true );

				if ( is_wp_error( $result ) ) {
					WP_CLI::warning( sprintf( '#%d نوشته نشد: %s', $id, $result->get_error_message() ) );
					continue;
				}
			}

			$changed ++;
		}
	}

	WP_CLI::log( '' );
	WP_CLI::log( sprintf( '  %s: %d عنوان · %d رد شده', $dry ? 'آزمایشی' : 'نوشته شد', $changed, $skipped ) );

	if ( $dry ) {
		WP_CLI::log( '  برای اجرای واقعی، --dry را بردار.' );
	} else {
		WP_CLI::log( '  یادآوری: کش رنک‌مث و کش صفحه را پاک کن.' );
	}

	WP_CLI::log( '' );
}

/* =========================================================================
   بررسی رابطه
   ========================================================================= */

if ( '' !== $rel_arg ) {

	WP_CLI::log( '' );

	foreach ( array_map( 'trim', explode( ',', $rel_arg ) ) as $rid ) {

		$rid      = absint( $rid );
		$relation = topersia_relation( $rid );

		if ( ! $relation ) {
			WP_CLI::warning( sprintf( 'رابطهٔ %d ثبت نشده است.', $rid ) );
			continue;
		}

		WP_CLI::log( sprintf( '══ رابطهٔ %d ══════════════════════════════', $rid ) );
		WP_CLI::log( '  متدهای در دسترس: ' . implode( ' · ', array_slice( get_class_methods( $relation ), 0, 12 ) ) );

		/* برقرارکردن پیوند، امضای نسخهٔ نصب‌شدهٔ JetEngine را می‌خواهد.
		   حدس‌زدنش روی دادهٔ زنده قابل قبول نیست — اول این را ببین، بعد
		   اسکریپت پرکردن را بنویس. */
		if ( method_exists( $relation, 'update' ) ) {
			$ref = new ReflectionMethod( $relation, 'update' );
			$sig = array();
			foreach ( $ref->getParameters() as $p ) {
				$sig[] = '$' . $p->getName() . ( $p->isOptional() ? '?' : '' );
			}
			WP_CLI::log( '  update( ' . implode( ', ', $sig ) . ' )' );
		}
	}

	WP_CLI::log( '' );
}
