<?php
/**
 * بخش ۱۹ — بستۀ محتوای هر نوع پست — P5-D272 · SPRINT-4.4 فاز ۲ب.
 *
 * برای هر نوع پست یک پروندۀ قالب که به هوش مصنوعی داده می‌شود («دربارۀ اصفهان
 * بنویس و این پرونده را پر کن»)، و درون‌ریزی همان پروندۀ پرشده.
 *
 * گسترش واردکنندۀ بخش ۱۴، نه سامانۀ موازی: همان خانوادۀ پرونده
 * (`topersia-geo-import`، نسخۀ ۲٫۰ — ۱٫x همان اسکلت جغرافیایی است)، همان
 * قاعده‌ها — پست تازه همیشه پیش‌نویس، نامک و entity_uid هرگز بازنویسی
 * نمی‌شوند، یال با API رابطۀ جت‌انجین (`topersia_relation_connect()`)، و
 * «آزمایشی» پیش از «اجرا».
 *
 *   ۱۹-الف  اسکیمای زنده   فیلدها، واژه‌نامه‌ها و رابطه‌های هر نوع مستقیم از
 *                          جت‌انجین (topersia_diag_meta_boxes · _glossaries) و
 *                          کلاس ترجمۀ WPML — هیچ فهرست دست‌نویسی از فیلدها.
 *                          تنها جدول دستی: کمینۀ واژۀ اسناد CA (سند قابل
 *                          خواندن ماشین نیست) با منبع هر عدد.
 *   ۱۹-ب   قالب و برون‌بری  قالب JSON با بلوک دستور به زبان محتوا، JSON Schema
 *                          (draft 2020-12)، و برون‌بری پست موجود به همان شکل.
 *   ۱۹-ج   سنجش           هر فیلد یک وضعیت؛ «آزمایشی» هیچ چیز نمی‌نویسد.
 *   ۱۹-د   اجرا و برگرداندن  دسته‌دسته با AJAX. مقدار پیشین هر پست **پیش از**
 *                          نوشتن در کانال `import` لاگ هسته — آخرین درون‌ریزی
 *                          برگشت‌پذیر است.
 *   ۱۹-ه   پیشخوان        زبانۀ «بستۀ محتوا».
 *
 * هیچ جدول و هیچ نوع پست تازه‌ای ساخته نمی‌شود (قاعدۀ ۰-۱۲).
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'TOPERSIA_PACK_SCHEMA' ) ) {
	define( 'TOPERSIA_PACK_SCHEMA', 'topersia-geo-import/2.0' );
}

/** گزینۀ آخرین درون‌ریزی — برای «برگرداندن» و استثنای هرس لاگ. */
if ( ! defined( 'TOPERSIA_PACK_LAST_RUN' ) ) {
	define( 'TOPERSIA_PACK_LAST_RUN', 'topersia_pack_last_run' );
}

/** سقف حجم پرونده — ده مگابایت؛ یک استان کامل با همۀ شهرها زیر یک مگابایت است. */
if ( ! defined( 'TOPERSIA_PACK_MAX_BYTES' ) ) {
	define( 'TOPERSIA_PACK_MAX_BYTES', 10 * 1024 * 1024 );
}

/* ═══════════════════════════════════════════════════════════════════════
 *  ۱۹-الف — اسکیمای زنده
 * ═══════════════════════════════════════════════════════════════════════ */

/** زبان‌های فعال WPML — پیش‌فرض نخست. بی WPML فقط فارسی. */
function topersia_pack_languages() {

	$default = has_filter( 'wpml_default_language' ) ? (string) apply_filters( 'wpml_default_language', null ) : '';
	$default = '' !== $default ? $default : 'fa';
	$langs   = array( $default );

	if ( has_filter( 'wpml_active_languages' ) ) {

		$active = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );

		foreach ( array_keys( (array) $active ) as $code ) {
			if ( is_string( $code ) && '' !== $code && ! in_array( $code, $langs, true ) ) {
				$langs[] = $code;
			}
		}
	}

	return $langs;
}

/** زبان پیش‌فرض سایت. */
function topersia_pack_default_language() {

	$langs = topersia_pack_languages();

	return $langs[0];
}

/** زبان یک پست در WPML؛ بی WPML یا بی زبان، زبان پیش‌فرض. */
function topersia_pack_post_language( $post_id ) {

	if ( has_filter( 'wpml_post_language_details' ) ) {

		$details = apply_filters( 'wpml_post_language_details', null, $post_id );

		if ( is_array( $details ) && ! empty( $details['language_code'] ) ) {
			return (string) $details['language_code'];
		}
	}

	return topersia_pack_default_language();
}

/** نوع‌های پستِ موجودیت که الان ثبت شده‌اند. */
function topersia_pack_types() {

	$out = array();

	foreach ( get_post_types( array(), 'objects' ) as $slug => $object ) {
		if ( topersia_is_entity( $slug ) ) {
			$out[ $slug ] = isset( $object->labels->singular_name ) && '' !== $object->labels->singular_name ? $object->labels->singular_name : $object->label;
		}
	}

	ksort( $out );

	return $out;
}

/** گزینه‌های یک واژه‌نامۀ جت‌انجین: کلید ← برچسب. */
function topersia_pack_glossary( $glossary_id ) {

	static $cache = null;

	if ( null === $cache ) {

		$cache = array();

		foreach ( topersia_diag_glossaries() as $glossary ) {

			$options = array();

			foreach ( (array) $glossary['fields'] as $row ) {

				$row = is_object( $row ) ? get_object_vars( $row ) : $row;

				if ( is_array( $row ) && isset( $row['value'] ) && '' !== (string) $row['value'] ) {
					$options[ (string) $row['value'] ] = isset( $row['label'] ) ? (string) $row['label'] : (string) $row['value'];
				}
			}

			$cache[ (string) $glossary['id'] ] = $options;
		}
	}

	return isset( $cache[ (string) $glossary_id ] ) ? $cache[ (string) $glossary_id ] : array();
}

/**
 * کلاس ترجمۀ WPML هر متاکلید — ۰ نادیده · ۱ رونوشت · ۲ ترجمه · ۳ رونوشت یک‌باره.
 * از خودِ تنظیمات WPML (Translation Management)، نه از حدس.
 */
function topersia_pack_wpml_classes() {

	static $classes = null;

	if ( null === $classes ) {

		$classes = array();

		if ( has_filter( 'wpml_setting' ) ) {

			$tm = apply_filters( 'wpml_setting', array(), 'translation-management' );

			if ( is_array( $tm ) && ! empty( $tm['custom_fields_translation'] ) && is_array( $tm['custom_fields_translation'] ) ) {
				$classes = array_map( 'intval', $tm['custom_fields_translation'] );
			}
		}
	}

	return $classes;
}

/** نام خوانای کلاس WPML. */
function topersia_pack_wpml_label( $class ) {

	$labels = array( 0 => 'ignore', 1 => 'copy', 2 => 'translate', 3 => 'copy-once' );

	return isset( $labels[ $class ] ) ? $labels[ $class ] : '';
}

/**
 * چرا یک فیلد در بسته نیست — رشتۀ خالی یعنی هست.
 *
 * قاعده بر پایۀ نوع است، نه فهرست نام‌ها: رسانه (media · gallery) و Repeaterی
 * که رسانه دارد — «هیچ نشانی تصویر». فهرست بسته برای بقیه: video_sample
 * متن است ولی نشانی رسانه است؛ entity_uid را بخش ۳ می‌سازد، content_status
 * پست تازه همیشه needs_verification است، و last_verified_date تاریخ
 * راستی‌آزمایی ویراستار است — هیچ‌کدام کار نویسندۀ متن نیست.
 */
function topersia_pack_excluded( $field ) {

	$name = isset( $field['name'] ) ? (string) $field['name'] : '';
	$type = isset( $field['type'] ) ? (string) $field['type'] : '';

	if ( ! preg_match( '/^[a-z][a-z0-9_]*$/', $name ) ) {
		return 'کلید نامعتبر در متاباکس';
	}

	if ( in_array( $type, array( 'media', 'gallery' ), true ) ) {
		return 'رسانه';
	}

	if ( 'repeater' === $type ) {
		foreach ( (array) ( isset( $field['repeater-fields'] ) ? $field['repeater-fields'] : array() ) as $sub ) {
			if ( isset( $sub['type'] ) && in_array( $sub['type'], array( 'media', 'gallery' ), true ) ) {
				return 'Repeater رسانه‌دار';
			}
		}
	}

	if ( 'video_sample' === $name ) {
		return 'نشانی رسانه';
	}

	if ( in_array( $name, array( 'entity_uid', 'content_status', 'last_verified_date' ), true ) ) {
		return 'سامانه‌ای';
	}

	return '';
}

/** واحد فیلد عددی — از پرانتز برچسب خودِ جت‌انجین: «مساحت (کیلومتر مربع)». */
function topersia_pack_unit( $label, $type ) {

	if ( 'number' === $type && preg_match( '/\(([^()]+)\)\s*$/u', (string) $label, $m ) ) {
		return trim( $m[1] );
	}

	return '';
}

/**
 * کمینه و بیشینۀ طول — از اسناد CA. تنها جدول دستی بخش ۱۹: اسناد CA قابل
 * خواندن ماشین نیستند. هر عدد منبع دارد؛ عدد بی منبع نمی‌آید.
 */
function topersia_pack_length_rules() {

	$over = array(
		'tp_country'          => array( 800, 'CA-G G1' ),
		'tp_region'           => array( 400, 'CA-G G2' ),
		'tp_province'         => array( 600, 'CA-G G3' ),
		'tp_city'             => array( 800, 'CA-G G5' ),
		'tp_district'         => array( 300, 'CA-G G6' ),
		'tp_village'          => array( 250, 'CA-G G7' ),
		'tp_island'           => array( 500, 'CA-G G8' ),
		'tp_mountain'         => array( 400, 'CA-G G9' ),
		'tp_water_body'       => array( 400, 'CA-G G10' ),
		'tp_desert'           => array( 500, 'CA-G G11' ),
		'tp_forest_plain'     => array( 400, 'CA-G G12' ),
		'tp_border_crossing'  => array( 200, 'CA-G G13' ),
		'tp_monument'         => array( 600, 'CA-A A2' ),
		'tp_religious_site'   => array( 500, 'CA-A A3' ),
		'tp_museum'           => array( 400, 'CA-A A7' ),
		'tp_protected_area'   => array( 500, 'CA-A A9' ),
		'tp_natural_feature'  => array( 400, 'CA-A A10' ),
		'tp_ancient_site'     => array( 600, 'CA-A A12' ),
		'tp_landscape'        => array( 700, 'CA-A A13' ),
		'tp_handicraft'       => array( 500, 'CA-C C1' ),
		'tp_local_food'       => array( 400, 'CA-C C2' ),
		'tp_costume'          => array( 400, 'CA-C C5' ),
		'tp_music'            => array( 400, 'CA-C C6' ),
		'tp_dance'            => array( 400, 'CA-C C7' ),
		'tp_language'         => array( 400, 'CA-C C8' ),
		'tp_ethnic_group'     => array( 600, 'CA-C C9' ),
		'tp_ritual'           => array( 600, 'CA-C C10' ),
		'tp_figure'           => array( 700, 'CA-C C12' ),
		'tp_historical_era'   => array( 700, 'CA-C C13' ),
		'tp_designation'      => array( 400, 'CA-D D5 · D6 — D1 تا D4: ۵۰۰' ),
		'tp_festival'         => array( 400, 'CA-E E1' ),
		'tp_sports_event'     => array( 400, 'CA-E E3' ),
		'tp_religious_event'  => array( 500, 'CA-E E5' ),
		'tp_seasonal_event'   => array( 400, 'CA-E E6' ),
		'tp_visa'             => array( 500, 'CA-P P1 · CA-P-AMENDMENT-002' ),
		'tp_currency'         => array( 400, 'CA-P P2 · CA-P-AMENDMENT-003' ),
		'tp_health_safety'    => array( 400, 'CA-P P4' ),
		'tp_travel_etiquette' => array( 400, 'CA-P P5' ),
		'tp_calendar'         => array( 500, 'CA-P P6' ),
		'tp_transport'        => array( 400, 'CA-P P7' ),
		'tp_route'            => array( 500, 'CA-P P9' ),
	);

	$rules = array();

	foreach ( $over as $type => $rule ) {
		$rules[ $type ]['overview_text'] = array( 'min_words' => $rule[0], 'source' => $rule[1] );
	}

	/* currency_note — خلاصۀ ارجاعی، نه رقیب صفحۀ P2  CA-G-AMENDMENT-003 · P5-D110 */
	$rules['*']['currency_note'] = array( 'max_words' => 50, 'max_chars' => 300, 'source' => 'CA-G-AMENDMENT-003 · P5-D110' );

	return $rules;
}

/** قاعدۀ طول یک فیلد برای یک نوع. */
function topersia_pack_length( $post_type, $key ) {

	$rules = topersia_pack_length_rules();

	if ( isset( $rules[ $post_type ][ $key ] ) ) {
		return $rules[ $post_type ][ $key ];
	}

	return isset( $rules['*'][ $key ] ) ? $rules['*'][ $key ] : array();
}

/** تعریف یکدستِ یک فیلد (یا زیرفیلد) جت‌انجین. */
function topersia_pack_field_def( $field, $post_type, $classes, $is_sub = false ) {

	$type  = isset( $field['type'] ) ? (string) $field['type'] : 'text';
	$label = isset( $field['title'] ) ? (string) $field['title'] : (string) $field['name'];

	$def = array(
		'label'       => $label,
		'type'        => $type,
		'required'    => ! empty( $field['is_required'] ),
		'description' => isset( $field['description'] ) ? (string) $field['description'] : '',
	);

	$unit = topersia_pack_unit( $label, $type );

	if ( '' !== $unit ) {
		$def['unit'] = $unit;
	}

	if ( ! $is_sub ) {
		$class      = isset( $classes[ $field['name'] ] ) ? $classes[ $field['name'] ] : null;
		$def['wpml'] = null === $class ? '' : topersia_pack_wpml_label( $class );
	}

	if ( in_array( $type, array( 'select', 'checkbox', 'radio' ), true ) ) {

		$options = array();

		if ( isset( $field['options_source'] ) && 'glossary' === $field['options_source'] && ! empty( $field['glossary_id'] ) ) {
			$options          = topersia_pack_glossary( $field['glossary_id'] );
			$def['glossary']  = (int) $field['glossary_id'];
		} else {
			foreach ( (array) ( isset( $field['options'] ) ? $field['options'] : array() ) as $opt ) {
				$opt = is_object( $opt ) ? get_object_vars( $opt ) : $opt;
				if ( is_array( $opt ) && isset( $opt['key'] ) && '' !== (string) $opt['key'] ) {
					$options[ (string) $opt['key'] ] = isset( $opt['value'] ) ? (string) $opt['value'] : (string) $opt['key'];
				}
			}
		}

		$def['options']  = array();
		foreach ( $options as $value => $opt_label ) {
			$def['options'][] = array( 'value' => (string) $value, 'label' => (string) $opt_label );
		}
		$def['multiple'] = 'checkbox' === $type || ! empty( $field['is_multiple'] );
	}

	if ( 'number' === $type ) {
		foreach ( array( 'min_value' => 'min', 'max_value' => 'max', 'step_value' => 'step' ) as $from => $to ) {
			if ( isset( $field[ $from ] ) && '' !== $field[ $from ] && is_numeric( $field[ $from ] ) ) {
				$def[ $to ] = 0 + $field[ $from ];
			}
		}
	}

	if ( 'textarea' === $type && ! empty( $field['max_length'] ) && is_numeric( $field['max_length'] ) ) {
		$def['max_chars'] = (int) $field['max_length'];
	}

	if ( ! $is_sub ) {
		$length = topersia_pack_length( $post_type, (string) $field['name'] );
		foreach ( array( 'min_words', 'max_words', 'max_chars' ) as $k ) {
			if ( isset( $length[ $k ] ) ) {
				$def[ $k ] = $length[ $k ];
			}
		}
		if ( isset( $length['source'] ) ) {
			$def['length_source'] = $length['source'];
		}
	}

	if ( 'repeater' === $type ) {
		$def['fields'] = array();
		foreach ( (array) ( isset( $field['repeater-fields'] ) ? $field['repeater-fields'] : array() ) as $sub ) {
			if ( ! empty( $sub['name'] ) && preg_match( '/^[a-z][a-z0-9_]*$/', (string) $sub['name'] ) ) {
				$def['fields'][ (string) $sub['name'] ] = topersia_pack_field_def( $sub, $post_type, $classes, true );
			}
		}
	}

	return $def;
}

/**
 * اسکیمای بستۀ یک نوع — فیلدها، رابطه‌ها، فیلدهای بیرون از بسته، و هش.
 *
 * هر متاباکسی که نوع در allowed_post_type آن است، به همان ترتیب جت‌انجین.
 * هش از تعریف‌ها ساخته می‌شود؛ اگر پس از ساخت قالب اسکیمای جت‌انجین عوض شود،
 * درون‌ریزی می‌گوید.
 */
function topersia_pack_schema( $post_type ) {

	static $cache = array();

	if ( isset( $cache[ $post_type ] ) ) {
		return $cache[ $post_type ];
	}

	$classes  = topersia_pack_wpml_classes();
	$fields   = array();
	$excluded = array();

	foreach ( topersia_diag_meta_boxes() as $box ) {

		$args = isset( $box['args'] ) ? $box['args'] : array();
		$args = is_string( $args ) ? json_decode( $args, true ) : $args;

		if ( ! is_array( $args ) || ( isset( $args['object_type'] ) && 'post' !== $args['object_type'] ) ) {
			continue;
		}

		$allowed = isset( $args['allowed_post_type'] ) ? (array) $args['allowed_post_type'] : array();

		if ( ! in_array( $post_type, $allowed, true ) ) {
			continue;
		}

		$list = isset( $box['meta_fields'] ) ? $box['meta_fields'] : array();
		$list = is_string( $list ) ? json_decode( $list, true ) : $list;

		foreach ( (array) $list as $field ) {

			if ( ! is_array( $field ) || empty( $field['name'] ) || ( isset( $field['object_type'] ) && 'field' !== $field['object_type'] ) ) {
				continue;
			}

			$why = topersia_pack_excluded( $field );

			if ( '' !== $why ) {
				$excluded[ (string) $field['name'] ] = $why;
				continue;
			}

			if ( ! isset( $fields[ $field['name'] ] ) ) {
				$fields[ (string) $field['name'] ] = topersia_pack_field_def( $field, $post_type, $classes );
			}
		}
	}

	$relations = topersia_pack_relations( $post_type );

	$cache[ $post_type ] = array(
		'post_type' => $post_type,
		'fields'    => $fields,
		'relations' => $relations,
		'excluded'  => $excluded,
		'hash'      => substr( sha1( wp_json_encode( array( $fields, $relations ) ) ), 0, 16 ),
	);

	return $cache[ $post_type ];
}

/**
 * رابطه‌های یک نوع از دید همان نوع.
 *
 * direction «parents»: مقصد والدِ این پست است (پست فرزند است)؛ «children»:
 * مقصد فرزند. max از کاردینالیتی: والدِ یکتا (one_to_many و one_to_one از سمت
 * فرزند) و فرزندِ یکتا (one_to_one از سمت والد). رابطۀ خودارجاع (هم‌مرز،
 * رشته‌کوه) دو کلید می‌گیرد: «{id}:children» و «{id}:parents».
 */
function topersia_pack_relations( $post_type ) {

	$out = array();

	if ( ! function_exists( 'jet_engine' ) || ! isset( jet_engine()->relations ) || ! method_exists( jet_engine()->relations, 'get_active_relations' ) ) {
		return $out;
	}

	$me = 'posts::' . $post_type;

	foreach ( (array) jet_engine()->relations->get_active_relations() as $id => $rel ) {

		if ( ! is_object( $rel ) || ! method_exists( $rel, 'get_args' ) ) {
			continue;
		}

		$parent = (string) $rel->get_args( 'parent_object' );
		$child  = (string) $rel->get_args( 'child_object' );

		if ( $me !== $parent && $me !== $child ) {
			continue;
		}

		if ( 0 !== strpos( $parent, 'posts::' ) || 0 !== strpos( $child, 'posts::' ) ) {
			continue;
		}

		$type   = (string) $rel->get_args( 'type' );
		$labels = topersia_diag_decode( $rel->get_args( 'labels' ) );
		$self   = $parent === $child;

		foreach ( array( 'children', 'parents' ) as $direction ) {

			if ( 'children' === $direction && $me !== $parent ) {
				continue;
			}
			if ( 'parents' === $direction && $me !== $child ) {
				continue;
			}

			$target = str_replace( 'posts::', '', 'children' === $direction ? $child : $parent );
			$max    = 0;

			if ( 'parents' === $direction && in_array( $type, array( 'one_to_many', 'one_to_one' ), true ) ) {
				$max = 1;
			}
			if ( 'children' === $direction && 'one_to_one' === $type ) {
				$max = 1;
			}

			$label = 'children' === $direction
				? ( isset( $labels['parent_page_control_title'] ) ? $labels['parent_page_control_title'] : '' )
				: ( isset( $labels['child_page_control_title'] ) ? $labels['child_page_control_title'] : '' );

			$key = $self ? $id . ':' . $direction : (string) $id;

			$out[ $key ] = array(
				'id'          => (int) $id,
				'name'        => isset( $labels['name'] ) ? (string) $labels['name'] : '',
				'label'       => (string) $label,
				'target'      => $target,
				'direction'   => $direction,
				'cardinality' => $type,
				'max'         => $max,
			);
		}
	}

	uksort( $out, 'strnatcmp' );

	return $out;
}

/* ═══════════════════════════════════════════════════════════════════════
 *  ۱۹-ب — قالب، JSON Schema، برون‌بری پست
 * ═══════════════════════════════════════════════════════════════════════ */

/**
 * بلوک دستور ثابت — به زبان محتوا. SPRINT-4.4 ۲ب٫۱: فقط واقعیت با منبع،
 * نمی‌دانی خالی، کلید مجاز واژه‌نامه، رابطه با نام دقیق و نوع مقصد، بی نشانی
 * تصویر، لحن مرجع و کمینۀ طول اسناد CA.
 */
function topersia_pack_instructions( $lang, $post_type = '' ) {

	$min = topersia_pack_length( $post_type, 'overview_text' );
	$min = isset( $min['min_words'] ) ? (int) $min['min_words'] : 0;

	$fa = array(
		'فقط واقعیتِ دارای منبع بنویس. هر عدد، تاریخ و نام خاص یک ردیف در source_references با نشانی (source_url) دارد.',
		'اگر چیزی را نمی‌دانی یا منبعی برایش نداری، خالی بگذار. خالی یعنی «نامعلوم» و هیچ مقدار موجودی را پاک نمی‌کند.',
		'فیلدی که options دارد فقط یکی از value های همان فیلد را می‌گیرد — کلید، نه برچسب؛ فیلد چندگزینه‌ای فهرستی از کلیدها.',
		'رابطه را با نام دقیق مقصد و نوع پست مقصد بنویس: { "name": "…", "type": "…" }. پیوند هنگام درون‌ریزی ساخته می‌شود.',
		'هیچ نشانی تصویر، ویدیو یا پرونده‌ای ننویس؛ فیلدهای رسانه در این بسته نیستند.',
		'لحن دانشنامه‌ای و بی‌طرف، نه تبلیغاتی. کمینۀ طول هر فیلد min_words آن است (اسناد CA)' . ( $min ? '؛ overview_text دست‌کم ' . $min . ' واژه' : '' ) . '. پاسخ پرسش‌های اصلی در ۱۵۰ واژۀ نخست متن.',
		'ساختار پرونده را عوض نکن و کلید تازه نساز؛ فقط posts را پر کن — برای هر موجودیت یک شیء.',
		'برای موجودیت تازه entity_uid و slug را خالی بگذار. language زبان همین متن است؛ ترجمه در translation_of شناسه یا نامک اصل را دارد.',
	);

	$en = array(
		'Write only sourced facts. Every number, date and proper name has a row in source_references with a URL (source_url).',
		'If you do not know something or have no source for it, leave it empty. Empty means "unknown" and never erases an existing value.',
		'A field with options takes only one of that field\'s option values — the value, not the label; a multiple field takes a list of values.',
		'Write each relation with the exact target name and target post type: { "name": "…", "type": "…" }. The link is made on import.',
		'Do not write any image, video or file URL; media fields are not part of this pack.',
		'Encyclopedic, neutral tone — not promotional. The minimum length of each field is its min_words (CA documents)' . ( $min ? '; overview_text at least ' . $min . ' words' : '' ) . '. Answer the main questions within the first 150 words.',
		'Do not change the file structure or add keys; fill posts only — one object per entity.',
		'For a new entity leave entity_uid and slug empty. language is the language of this text; a translation puts the original\'s uid or slug in translation_of.',
	);

	$ar = array(
		'اكتب الحقائق الموثّقة فقط. لكل رقم وتاريخ واسم علم صفٌّ في source_references مع الرابط (source_url).',
		'إذا لم تعرف شيئاً أو لم يكن لديك مصدر له فاتركه فارغاً. الفارغ يعني «غير معروف» ولا يمحو أي قيمة موجودة.',
		'الحقل الذي له options يأخذ إحدى قيم value الخاصة به فقط — القيمة لا التسمية؛ والحقل المتعدد قائمةً من القيم.',
		'اكتب كل علاقة بالاسم الدقيق للهدف ونوع منشوره: { "name": "…", "type": "…" }. يُنشأ الربط عند الاستيراد.',
		'لا تكتب أي رابط لصورة أو فيديو أو ملف؛ حقول الوسائط ليست في هذه الحزمة.',
		'أسلوب موسوعي محايد لا ترويجي. الحد الأدنى لطول كل حقل هو min_words (وثائق CA)' . ( $min ? '؛ overview_text لا يقل عن ' . $min . ' كلمة' : '' ) . '. أجب عن الأسئلة الرئيسية في أول 150 كلمة.',
		'لا تغيّر بنية الملف ولا تُضف مفاتيح؛ املأ posts فقط — كائن واحد لكل كيان.',
		'للكيان الجديد اترك entity_uid و slug فارغين. language هي لغة هذا النص؛ والترجمة تضع في translation_of معرّف الأصل أو اسمه اللطيف.',
	);

	$sets = array( 'fa' => $fa, 'en' => $en, 'ar' => $ar );

	return isset( $sets[ $lang ] ) ? $sets[ $lang ] : $en;
}

/** مقدار خالیِ یک فیلد در قالب. */
function topersia_pack_blank( $def ) {

	switch ( $def['type'] ) {
		case 'number':
		case 'switcher':
			return null;
		case 'repeater':
			return array();
		case 'select':
		case 'checkbox':
		case 'radio':
			return ! empty( $def['multiple'] ) ? array() : '';
		default:
			return '';
	}
}

/** یک پست خالی با همۀ کلیدها — شکلی که هوش مصنوعی پر می‌کند. */
function topersia_pack_blank_post( $schema, $lang ) {

	$fields = array();

	foreach ( $schema['fields'] as $key => $def ) {
		$fields[ $key ] = topersia_pack_blank( $def );
	}

	$relations = array();

	foreach ( array_keys( $schema['relations'] ) as $key ) {
		$relations[ $key ] = array();
	}

	return array(
		'entity_uid'     => '',
		'slug'           => '',
		'language'       => $lang,
		'translation_of' => '',
		'title'          => '',
		'fields'         => (object) $fields,
		'relations'      => (object) $relations,
	);
}

/** سرِ پرونده — مشترکِ قالب و برون‌بری. */
function topersia_pack_meta( $post_type, $lang, $kind ) {

	$schema = topersia_pack_schema( $post_type );
	$types  = topersia_pack_types();

	return array(
		'schema'          => TOPERSIA_PACK_SCHEMA,
		'kind'            => $kind,
		'post_type'       => $post_type,
		'post_type_label' => isset( $types[ $post_type ] ) ? $types[ $post_type ] : $post_type,
		'language'        => $lang,
		'languages'       => topersia_pack_languages(),
		'generated'       => gmdate( 'Y-m-d' ),
		'generator'       => 'topersia-core ' . ( defined( 'TOPERSIA_CORE_VERSION' ) ? TOPERSIA_CORE_VERSION : '' ),
		'schema_hash'     => $schema['hash'],
		'excluded'        => (object) $schema['excluded'],
	);
}

/** قالب یک نوع به زبان محتوا. */
function topersia_pack_template( $post_type, $lang ) {

	$schema = topersia_pack_schema( $post_type );

	return array(
		'meta'         => topersia_pack_meta( $post_type, $lang, 'template' ),
		'instructions' => topersia_pack_instructions( $lang, $post_type ),
		'fields'       => (object) $schema['fields'],
		'relations'    => (object) $schema['relations'],
		'posts'        => array( topersia_pack_blank_post( $schema, $lang ) ),
	);
}

/** اسکیمای JSON یک فیلد. */
function topersia_pack_json_field( $def ) {

	$desc = trim( $def['label'] . ( ! empty( $def['unit'] ) ? ' — ' . $def['unit'] : '' ) . ( '' !== $def['description'] ? ' — ' . $def['description'] : '' ) );
	$out  = array( 'description' => $desc );

	switch ( $def['type'] ) {

		case 'number':
			$out['type'] = array( 'number', 'null' );
			if ( isset( $def['min'] ) ) {
				$out['minimum'] = $def['min'];
			}
			if ( isset( $def['max'] ) ) {
				$out['maximum'] = $def['max'];
			}
			break;

		case 'switcher':
			$out['type'] = array( 'boolean', 'null' );
			break;

		case 'date':
			$out['type']    = 'string';
			$out['pattern'] = '^([0-9]{4}-[0-9]{2}-[0-9]{2})?$';
			break;

		case 'select':
		case 'checkbox':
		case 'radio':
			$values = array_map( function ( $o ) { return $o['value']; }, $def['options'] );
			if ( ! empty( $def['multiple'] ) ) {
				$out['type']        = 'array';
				$out['items']       = array( 'enum' => $values );
				$out['uniqueItems'] = true;
			} else {
				$out['enum'] = array_merge( $values, array( '', null ) );
			}
			break;

		case 'repeater':
			$props = array();
			foreach ( $def['fields'] as $key => $sub ) {
				$props[ $key ] = topersia_pack_json_field( $sub );
			}
			$out['type']  = 'array';
			$out['items'] = array( 'type' => 'object', 'additionalProperties' => false, 'properties' => (object) $props );
			break;

		default:
			$out['type'] = 'string';
			if ( ! empty( $def['max_chars'] ) ) {
				$out['maxLength'] = (int) $def['max_chars'];
			}
	}

	return $out;
}

/**
 * JSON Schema (draft 2020-12) — از همان تعریف‌هایی که سنجش درون‌ریزی می‌خواند،
 * پس این دو هرگز از هم جدا نمی‌شوند. خالی همه‌جا مجاز است («نمی‌دانی، خالی
 * بگذار»)؛ «required» یعنی کلید باید باشد.
 */
function topersia_pack_json_schema( $post_type ) {

	$schema = topersia_pack_schema( $post_type );
	$langs  = topersia_pack_languages();
	$props  = array();
	$req    = array();

	foreach ( $schema['fields'] as $key => $def ) {
		$props[ $key ] = topersia_pack_json_field( $def );
		if ( $def['required'] ) {
			$req[] = $key;
		}
	}

	$rels = array();

	foreach ( $schema['relations'] as $key => $rel ) {

		$rels[ $key ] = array(
			'description' => $rel['label'] . ' — ' . $rel['target'] . ' · ' . $rel['direction'] . ' · ' . $rel['cardinality'],
			'type'        => 'array',
			'items'       => array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => array( 'name', 'type' ),
				'properties'           => array(
					'name' => array( 'type' => 'string', 'minLength' => 1 ),
					'type' => array( 'const' => $rel['target'] ),
					'uid'  => array( 'type' => 'string' ),
					'slug' => array( 'type' => 'string' ),
				),
			),
		);

		if ( $rel['max'] ) {
			$rels[ $key ]['maxItems'] = $rel['max'];
		}
	}

	return array(
		'$schema'    => 'https://json-schema.org/draft/2020-12/schema',
		'$id'        => 'urn:topersia:geo-import:2.0:' . $post_type,
		'title'      => 'ToPersia content pack — ' . $post_type,
		'type'       => 'object',
		'required'   => array( 'meta', 'posts' ),
		'properties' => array(
			'meta'         => array(
				'type'       => 'object',
				'required'   => array( 'schema', 'post_type' ),
				'properties' => array(
					'schema'    => array( 'const' => TOPERSIA_PACK_SCHEMA ),
					'post_type' => array( 'const' => $post_type ),
					'language'  => array( 'enum' => $langs ),
				),
			),
			'instructions' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
			'fields'       => array( 'type' => 'object' ),
			'relations'    => array( 'type' => 'object' ),
			'posts'        => array(
				'type'     => 'array',
				'minItems' => 1,
				'items'    => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => array( 'fields' ),
					'properties'           => array(
						'entity_uid'     => array( 'type' => 'string' ),
						'slug'           => array( 'type' => 'string', 'pattern' => '^([a-z0-9]+(-[a-z0-9]+)*)?$' ),
						'language'       => array( 'enum' => $langs ),
						'translation_of' => array( 'type' => 'string' ),
						'title'          => array( 'type' => 'string' ),
						'fields'         => array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'required'             => $req,
							'properties'           => (object) $props,
						),
						'relations'      => array(
							'type'                 => 'object',
							'additionalProperties' => false,
							'properties'           => (object) $rels,
						),
					),
				),
			),
		),
	);
}

/**
 * مقدار ذخیره‌شده ← مقدار بسته.
 *
 * عدد عدد، کلید سوییچ true/false، چک‌باکس فهرست کلیدهای روشن، Repeater
 * فهرست ردیف‌ها (بی item-N). همین تابع مقایسۀ «بی‌تغییر» را هم می‌سازد.
 */
function topersia_pack_value_out( $def, $raw ) {

	switch ( $def['type'] ) {

		case 'number':
			if ( is_array( $raw ) || '' === trim( (string) $raw ) || ! is_numeric( $raw ) ) {
				return null;
			}
			return 0 + $raw;

		case 'switcher':
			if ( true === $raw || 'true' === $raw || '1' === (string) $raw ) {
				return true;
			}
			if ( false === $raw || 'false' === $raw || '0' === (string) $raw ) {
				return false;
			}
			return null;

		case 'select':
		case 'checkbox':
		case 'radio':
			if ( ! empty( $def['multiple'] ) ) {
				$list = array();
				if ( is_array( $raw ) ) {
					$assoc = array_keys( $raw ) !== range( 0, count( $raw ) - 1 );
					foreach ( $raw as $k => $v ) {
						if ( $assoc ) {
							if ( true === $v || 'true' === $v || '1' === (string) $v ) {
								$list[] = (string) $k;
							}
						} elseif ( '' !== (string) $v ) {
							$list[] = (string) $v;
						}
					}
				} elseif ( '' !== trim( (string) $raw ) ) {
					$list[] = (string) $raw;
				}
				sort( $list, SORT_STRING );
				return array_values( array_unique( $list ) );
			}
			return is_array( $raw ) ? (string) reset( $raw ) : (string) $raw;

		case 'repeater':
			$rows = array();
			foreach ( ( is_array( $raw ) ? $raw : array() ) as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$out = array();
				foreach ( $def['fields'] as $key => $sub ) {
					$out[ $key ] = topersia_pack_value_out( $sub, isset( $row[ $key ] ) ? $row[ $key ] : '' );
				}
				$rows[] = $out;
			}
			return $rows;

		default:
			return is_array( $raw ) || is_object( $raw ) ? '' : (string) $raw;
	}
}

/** آیا مقدار بسته «خالی» است — یعنی نامعلوم. */
function topersia_pack_is_empty( $value ) {

	if ( null === $value || '' === $value || array() === $value ) {
		return true;
	}

	if ( is_string( $value ) && '' === trim( $value ) ) {
		return true;
	}

	return false;
}

/** رقم فارسی و عربی ← لاتین؛ جداکنندۀ هزارگان و ممیز فارسی. */
function topersia_pack_number_text( $value ) {

	$text = strtr( (string) $value, array(
		'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
		'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		'٬' => ',', '٫' => '.',
	) );

	$text = trim( $text );

	if ( preg_match( '/^-?\d{1,3}(,\d{3})+(\.\d+)?$/', $text ) ) {
		$text = str_replace( ',', '', $text );
	}

	return $text;
}

/** شمار واژه — فاصله جداکننده است، نیم‌فاصله نه. */
function topersia_pack_words( $html ) {

	$text = trim( wp_strip_all_tags( (string) $html ) );

	return '' === $text ? 0 : count( preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY ) );
}

/**
 * مقدار بسته ← مقدار ذخیره، با سنجش.
 *
 * @return array status · stored · cmp (مقدار بستۀ یکدست برای مقایسه) · note
 *               status: ok · empty · option · type · short
 */
function topersia_pack_value_in( $def, $value, $key = '' ) {

	if ( topersia_pack_is_empty( $value ) ) {
		return array( 'status' => 'empty', 'stored' => null, 'cmp' => null, 'note' => '' );
	}

	switch ( $def['type'] ) {

		case 'number':
			if ( is_bool( $value ) || is_array( $value ) ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'عدد می‌خواهد' );
			}
			$text = topersia_pack_number_text( $value );
			if ( ! is_numeric( $text ) ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'عدد نیست: «' . topersia_str_cut( (string) $value, 40 ) . '»' );
			}
			$num = 0 + $text;
			if ( isset( $def['min'] ) && $num < $def['min'] ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'کمتر از کمینۀ ' . $def['min'] );
			}
			if ( isset( $def['max'] ) && $num > $def['max'] ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'بیشتر از بیشینۀ ' . $def['max'] );
			}
			return array( 'status' => 'ok', 'stored' => (string) $text, 'cmp' => $num, 'note' => '' );

		case 'switcher':
			if ( true === $value || 'true' === $value || 1 === $value || '1' === $value ) {
				return array( 'status' => 'ok', 'stored' => 'true', 'cmp' => true, 'note' => '' );
			}
			if ( false === $value || 'false' === $value || 0 === $value || '0' === $value ) {
				return array( 'status' => 'ok', 'stored' => 'false', 'cmp' => false, 'note' => '' );
			}
			return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'true یا false' );

		case 'date':
			if ( ! is_string( $value ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', trim( $value ), $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'تاریخ YYYY-MM-DD' );
			}
			return array( 'status' => 'ok', 'stored' => trim( $value ), 'cmp' => trim( $value ), 'note' => '' );

		case 'select':
		case 'checkbox':
		case 'radio':
			$allowed = array();
			$by_label = array();
			foreach ( $def['options'] as $opt ) {
				$allowed[ $opt['value'] ] = true;
				$by_label[ $opt['label'] ] = $opt['value'];
			}
			$list = is_array( $value ) ? $value : array( $value );
			if ( empty( $def['multiple'] ) && count( $list ) > 1 ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'یک گزینه می‌گیرد' );
			}
			$clean = array();
			foreach ( $list as $item ) {
				if ( ! is_string( $item ) && ! is_int( $item ) ) {
					return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'کلید گزینه متن است' );
				}
				$item = (string) $item;
				if ( ! isset( $allowed[ $item ] ) ) {
					$hint = isset( $by_label[ $item ] ) ? ' — برچسب است؛ کلیدش «' . $by_label[ $item ] . '»' : '';
					return array( 'status' => 'option', 'stored' => null, 'cmp' => null, 'note' => '«' . topersia_str_cut( $item, 40 ) . '» در گزینه‌ها نیست' . $hint );
				}
				$clean[] = $item;
			}
			if ( empty( $def['multiple'] ) ) {
				return array( 'status' => 'ok', 'stored' => $clean[0], 'cmp' => $clean[0], 'note' => '' );
			}
			$clean = array_values( array_unique( $clean ) );
			sort( $clean, SORT_STRING );
			if ( 'checkbox' === $def['type'] ) {
				$map = array();
				foreach ( $def['options'] as $opt ) {
					$map[ $opt['value'] ] = in_array( $opt['value'], $clean, true ) ? 'true' : 'false';
				}
				return array( 'status' => 'ok', 'stored' => $map, 'cmp' => $clean, 'note' => '' );
			}
			return array( 'status' => 'ok', 'stored' => $clean, 'cmp' => $clean, 'note' => '' );

		case 'repeater':
			if ( ! is_array( $value ) ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'فهرست ردیف‌ها می‌خواهد' );
			}
			$stored = array();
			$cmp    = array();
			$i      = 0;
			foreach ( $value as $row ) {
				if ( is_object( $row ) ) {
					$row = get_object_vars( $row );
				}
				if ( ! is_array( $row ) ) {
					return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'ردیف ' . ( $i + 1 ) . ' شیء نیست' );
				}
				$unknown = array_diff( array_keys( $row ), array_keys( $def['fields'] ) );
				if ( $unknown ) {
					return array( 'status' => 'unknown', 'stored' => null, 'cmp' => null, 'note' => 'ردیف ' . ( $i + 1 ) . ': ' . implode( '، ', $unknown ) );
				}
				$srow = array();
				$crow = array();
				foreach ( $def['fields'] as $sub_key => $sub_def ) {
					$in = topersia_pack_value_in( $sub_def, isset( $row[ $sub_key ] ) ? $row[ $sub_key ] : null, $sub_key );
					if ( in_array( $in['status'], array( 'type', 'option', 'unknown' ), true ) ) {
						return array( 'status' => $in['status'], 'stored' => null, 'cmp' => null, 'note' => 'ردیف ' . ( $i + 1 ) . ' · ' . $sub_key . ': ' . $in['note'] );
					}
					if ( 'empty' === $in['status'] ) {
						$srow[ $sub_key ] = 'checkbox' === $sub_def['type'] ? array() : '';
						$crow[ $sub_key ] = topersia_pack_value_out( $sub_def, '' );
					} else {
						$srow[ $sub_key ] = $in['stored'];
						$crow[ $sub_key ] = $in['cmp'];
					}
				}
				/* هر منبع نشانی می‌خواهد — «هر عدد، تاریخ و نام یک ردیف با نشانی» */
				if ( 'source_references' === $key && isset( $def['fields']['source_url'] ) && ! preg_match( '#^https?://#i', (string) $srow['source_url'] ) ) {
					return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'ردیف ' . ( $i + 1 ) . ': منبع بی نشانی' );
				}
				$stored[ 'item-' . $i ] = $srow;
				$cmp[]                  = $crow;
				$i++;
			}
			return array( 'status' => 'ok', 'stored' => $stored, 'cmp' => $cmp, 'note' => '' );

		default:
			if ( is_array( $value ) || is_object( $value ) || is_bool( $value ) ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'متن می‌خواهد' );
			}
			$text   = (string) $value;
			$is_url = 'text' === $def['type'] && preg_match( '/(_url|_uri|_link)$/', $key );
			if ( $is_url && ! preg_match( '#^https?://\S+$#i', trim( $text ) ) ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'نشانی http(s) می‌خواهد' );
			}
			if ( ! empty( $def['max_chars'] ) && mb_strlen( wp_strip_all_tags( $text ) ) > $def['max_chars'] ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'بیش از ' . $def['max_chars'] . ' نویسه' );
			}
			if ( ! empty( $def['max_words'] ) && topersia_pack_words( $text ) > $def['max_words'] ) {
				return array( 'status' => 'type', 'stored' => null, 'cmp' => null, 'note' => 'بیش از ' . $def['max_words'] . ' واژه' );
			}
			if ( 'wysiwyg' === $def['type'] ) {
				$stored = wp_kses_post( $text );
			} elseif ( 'textarea' === $def['type'] ) {
				$stored = sanitize_textarea_field( $text );
			} elseif ( $is_url ) {
				$stored = esc_url_raw( trim( $text ) );
			} else {
				$stored = sanitize_text_field( $text );
			}
			$status = 'ok';
			$note   = '';
			if ( ! empty( $def['min_words'] ) ) {
				$words = topersia_pack_words( $text );
				if ( $words < $def['min_words'] ) {
					$status = 'short';
					$note   = $words . ' واژه از کمینۀ ' . $def['min_words'];
				}
			}
			return array( 'status' => $status, 'stored' => $stored, 'cmp' => $text, 'note' => $note );
	}
}

/** مقدار کنونی یک فیلد، به شکل بسته. */
function topersia_pack_current( $post_id, $key, $def ) {
	return topersia_pack_value_out( $def, get_post_meta( $post_id, $key, true ) );
}

/** دو مقدار بسته برابرند؟ */
function topersia_pack_same( $a, $b ) {
	return wp_json_encode( $a ) === wp_json_encode( $b );
}

/** عنوان پست برای زبانش — از فیلدهای نام. */
function topersia_pack_title_from( $post, $lang ) {

	if ( isset( $post['title'] ) && is_string( $post['title'] ) && '' !== trim( $post['title'] ) ) {
		return trim( $post['title'] );
	}

	$fields = isset( $post['fields'] ) && is_array( $post['fields'] ) ? $post['fields'] : array();

	foreach ( array( 'name_' . $lang, 'official_name_' . $lang, 'name_fa' ) as $key ) {
		if ( isset( $fields[ $key ] ) && is_string( $fields[ $key ] ) && '' !== trim( $fields[ $key ] ) ) {
			return trim( $fields[ $key ] );
		}
	}

	return '';
}

/** برون‌بری یک پست موجود — همان شکل قالب. */
function topersia_pack_export_post( $post_id ) {

	$post = get_post( $post_id );

	if ( ! $post || ! topersia_is_entity( $post->post_type ) ) {
		return null;
	}

	$schema = topersia_pack_schema( $post->post_type );
	$lang   = topersia_pack_post_language( $post->ID );
	$fields = array();

	foreach ( $schema['fields'] as $key => $def ) {
		$fields[ $key ] = topersia_pack_current( $post->ID, $key, $def );
	}

	$relations = array();

	foreach ( $schema['relations'] as $key => $rel ) {

		$relations[ $key ] = array();
		$object            = topersia_relation( $rel['id'] );

		if ( ! $object ) {
			continue;
		}

		$ids = 'parents' === $rel['direction'] ? $object->get_parents( $post->ID, 'ids' ) : $object->get_children( $post->ID, 'ids' );

		foreach ( array_map( 'absint', (array) $ids ) as $target ) {

			$t = get_post( $target );

			if ( ! $t || 'trash' === $t->post_status ) {
				continue;
			}

			$relations[ $key ][] = array(
				'name' => $t->post_title,
				'type' => $t->post_type,
				'uid'  => (string) get_post_meta( $t->ID, 'entity_uid', true ),
				'slug' => $t->post_name,
			);
		}
	}

	$original = '';
	$default  = topersia_pack_default_language();

	if ( $lang !== $default && has_filter( 'wpml_object_id' ) ) {
		$orig = (int) apply_filters( 'wpml_object_id', $post->ID, $post->post_type, false, $default );
		if ( $orig && $orig !== $post->ID ) {
			$uid      = (string) get_post_meta( $orig, 'entity_uid', true );
			$original = '' !== $uid ? $uid : get_post_field( 'post_name', $orig );
		}
	}

	return array(
		'entity_uid'     => (string) get_post_meta( $post->ID, 'entity_uid', true ),
		'slug'           => $post->post_name,
		'language'       => $lang,
		'translation_of' => $original,
		'title'          => $post->post_title,
		'fields'         => (object) $fields,
		'relations'      => (object) $relations,
	);
}

/** پروندۀ برون‌بری چند پست هم‌نوع. */
function topersia_pack_export( $post_ids ) {

	$posts = array();
	$type  = '';
	$lang  = '';

	foreach ( (array) $post_ids as $id ) {

		$item = topersia_pack_export_post( (int) $id );

		if ( ! $item ) {
			continue;
		}

		$ptype = get_post_type( (int) $id );

		if ( '' === $type ) {
			$type = $ptype;
			$lang = $item['language'];
		}

		if ( $ptype !== $type ) {
			continue;
		}

		$posts[] = $item;
	}

	if ( ! $posts ) {
		return null;
	}

	$schema = topersia_pack_schema( $type );

	return array(
		'meta'         => topersia_pack_meta( $type, $lang, 'export' ),
		'instructions' => topersia_pack_instructions( $lang, $type ),
		'fields'       => (object) $schema['fields'],
		'relations'    => (object) $schema['relations'],
		'posts'        => $posts,
	);
}

/* ═══════════════════════════════════════════════════════════════════════
 *  ۱۹-ج — خواندن و سنجش («آزمایشی»)
 * ═══════════════════════════════════════════════════════════════════════ */

/** برچسب هر وضعیت — فهرست SPRINT-4.4 ۲ب٫۱ به‌علاوۀ سه وضعیت آگاهی‌دهنده. */
function topersia_pack_statuses() {

	return array(
		'ok'            => array( 'درست', '#00a32a' ),
		'same'          => array( 'بی‌تغییر', '#646970' ),
		'empty'         => array( 'خالی — نوشته نمی‌شود', '#646970' ),
		'short'         => array( 'کوتاه‌تر از کمینه', '#dba617' ),
		'original'      => array( 'از اصل — WPML', '#646970' ),
		'unknown'       => array( 'کلید ناشناخته', '#d63638' ),
		'option'        => array( 'گزینۀ نامعتبر واژه‌نامه', '#d63638' ),
		'type'          => array( 'نوع نادرست', '#d63638' ),
		'required'      => array( 'الزامی خالی', '#dba617' ),
		'rel_missing'   => array( 'رابطۀ پیدانشده', '#d63638' ),
		'rel_ambiguous' => array( 'رابطۀ چندپهلو', '#d63638' ),
		'duplicate'     => array( 'تکراری', '#d63638' ),
	);
}

/**
 * پرونده را می‌خواند و سرِ آن را می‌سنجد. ساختار هر پست را نه — آن کار
 * «آزمایشی» است و به‌ازای هر پست گزارش می‌دهد.
 *
 * @return array|WP_Error
 */
function topersia_pack_parse( $raw ) {

	if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
		return new WP_Error( 'pack_empty', 'پرونده خالی است.' );
	}

	if ( strlen( $raw ) > TOPERSIA_PACK_MAX_BYTES ) {
		return new WP_Error( 'pack_size', 'پرونده از ده مگابایت بزرگ‌تر است.' );
	}

	$raw  = preg_replace( '/^\xEF\xBB\xBF/', '', $raw );
	$data = json_decode( $raw, true );

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'pack_json', 'JSON معتبر نیست: ' . json_last_error_msg() );
	}

	$schema = isset( $data['meta']['schema'] ) ? (string) $data['meta']['schema'] : '';

	if ( 0 === strpos( $schema, 'topersia-geo-import/1.' ) ) {
		return new WP_Error( 'pack_v1', 'این پروندۀ اسکلت جغرافیایی ' . $schema . ' است — زبانۀ «اسکلت جغرافیایی».' );
	}

	if ( TOPERSIA_PACK_SCHEMA !== $schema ) {
		return new WP_Error( 'pack_schema', 'meta.schema باید ' . TOPERSIA_PACK_SCHEMA . ' باشد' . ( '' !== $schema ? '، نه ' . $schema : '' ) . '.' );
	}

	$type = isset( $data['meta']['post_type'] ) ? (string) $data['meta']['post_type'] : '';

	if ( ! topersia_is_entity( $type ) || ! post_type_exists( $type ) ) {
		return new WP_Error( 'pack_type', 'meta.post_type نوع پست موجودیتِ ثبت‌شده نیست: ' . ( '' !== $type ? $type : '—' ) );
	}

	if ( empty( $data['posts'] ) || ! is_array( $data['posts'] ) || array_keys( $data['posts'] ) !== range( 0, count( $data['posts'] ) - 1 ) ) {
		return new WP_Error( 'pack_posts', 'posts باید فهرستی ناخالی از پست‌ها باشد.' );
	}

	$langs = topersia_pack_languages();
	$lang  = isset( $data['meta']['language'] ) && in_array( $data['meta']['language'], $langs, true ) ? $data['meta']['language'] : $langs[0];

	$data['meta']['language'] = $lang;

	/* تکراری درون پرونده — شناسه، نامک یا نام در یک زبان */
	$seen = array();

	foreach ( $data['posts'] as $i => $post ) {

		if ( ! is_array( $post ) ) {
			continue;
		}

		$plang = isset( $post['language'] ) && is_string( $post['language'] ) && '' !== $post['language'] ? $post['language'] : $lang;
		$keys  = array();

		if ( ! empty( $post['entity_uid'] ) && is_string( $post['entity_uid'] ) ) {
			$keys[] = 'uid:' . $plang . ':' . $post['entity_uid'];
		}
		if ( ! empty( $post['slug'] ) && is_string( $post['slug'] ) ) {
			$keys[] = 'slug:' . $plang . ':' . $post['slug'];
		}
		if ( ! $keys ) {
			$title = topersia_pack_title_from( $post, $plang );
			if ( '' !== $title ) {
				$keys[] = 'name:' . $plang . ':' . $title;
			}
		}

		foreach ( $keys as $k ) {
			if ( isset( $seen[ $k ] ) ) {
				$data['posts'][ $i ]['_dup_of'] = $seen[ $k ];
				break;
			}
		}

		foreach ( $keys as $k ) {
			if ( ! isset( $seen[ $k ] ) ) {
				$seen[ $k ] = $i;
			}
		}
	}

	return $data;
}

/** وضعیت‌هایی که از پست واقعی خوانده می‌شوند: زنده و پیش‌نویس، نه زباله. */
function topersia_pack_live_statuses() {
	return array( 'publish', 'draft', 'pending', 'private', 'future' );
}

/** پست‌هایی از یک نوع با شناسه یا نامک، در یک زبان. */
function topersia_pack_find( $post_type, $uid, $slug, $lang ) {

	$ids = array();

	if ( is_string( $uid ) && '' !== $uid ) {
		$ids = get_posts( array(
			'post_type'        => $post_type,
			'post_status'      => topersia_pack_live_statuses(),
			'numberposts'      => 20,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'meta_key'         => 'entity_uid', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'       => $uid,         // phpcs:ignore WordPress.DB.SlowDBQuery
		) );
	}

	if ( ! $ids && is_string( $slug ) && '' !== $slug ) {
		$ids = get_posts( array(
			'post_type'        => $post_type,
			'post_status'      => topersia_pack_live_statuses(),
			'numberposts'      => 20,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'name'             => $slug,
		) );
	}

	return array_values( array_filter( array_map( 'absint', (array) $ids ), function ( $id ) use ( $lang ) {
		return topersia_pack_post_language( $id ) === $lang;
	} ) );
}

/** گونه‌های یک نام برای جست‌وجوی «دقیق»: «ۀ» و «ه» با همزۀ بالا، ی و ك عربی. */
function topersia_pack_name_variants( $name ) {

	$name = trim( preg_replace( '/\s+/u', ' ', (string) $name ) );
	$heh  = "\u{06C0}";
	$pair = "\u{0647}\u{0654}";

	$variants = array(
		$name,
		str_replace( $heh, $pair, $name ),
		str_replace( $pair, $heh, $name ),
		strtr( $name, array( 'ي' => 'ی', 'ك' => 'ک' ) ),
	);

	return array_values( array_unique( array_filter( $variants, 'strlen' ) ) );
}

/** پست‌هایی از یک نوع با نام دقیق — عنوان، نام همان زبان یا نام رسمی — در یک زبان. */
function topersia_pack_by_name( $post_type, $name, $lang ) {

	global $wpdb;

	$variants = topersia_pack_name_variants( $name );

	if ( ! $variants ) {
		return array();
	}

	$statuses = topersia_pack_live_statuses();
	$in_st    = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
	$in_nm    = implode( ',', array_fill( 0, count( $variants ), '%s' ) );

	// phpcs:disable WordPress.DB.PreparedSQL
	$by_title = $wpdb->get_col( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ({$in_st}) AND post_title IN ({$in_nm})",
		array_merge( array( $post_type ), $statuses, $variants )
	) );

	$keys  = array_values( array_unique( array( 'name_' . $lang, 'official_name_' . $lang ) ) );
	$in_k  = implode( ',', array_fill( 0, count( $keys ), '%s' ) );

	$by_meta = $wpdb->get_col( $wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
		 WHERE p.post_type = %s AND p.post_status IN ({$in_st}) AND m.meta_key IN ({$in_k}) AND m.meta_value IN ({$in_nm})",
		array_merge( array( $post_type ), $statuses, $keys, $variants )
	) );
	// phpcs:enable

	$ids = array_values( array_unique( array_map( 'absint', array_merge( (array) $by_title, (array) $by_meta ) ) ) );

	return array_values( array_filter( $ids, function ( $id ) use ( $lang ) {
		return topersia_pack_post_language( $id ) === $lang;
	} ) );
}

/**
 * یک مقصدِ رابطه را حل می‌کند — شناسه، نامک، سپس نام دقیق؛ همه در زبان پست.
 *
 * @return array status (ok · rel_missing · rel_ambiguous · type) · target · note
 */
function topersia_pack_resolve( $rel, $item, $lang, $pending = array() ) {

	if ( is_object( $item ) ) {
		$item = get_object_vars( $item );
	}

	if ( is_string( $item ) ) {
		$item = array( 'name' => $item, 'type' => $rel['target'] );
	}

	if ( ! is_array( $item ) ) {
		return array( 'status' => 'type', 'target' => 0, 'note' => 'هر مقصد { "name": …, "type": … } است', 'name' => '' );
	}

	$name = isset( $item['name'] ) && is_string( $item['name'] ) ? trim( $item['name'] ) : '';
	$type = isset( $item['type'] ) && is_string( $item['type'] ) ? $item['type'] : $rel['target'];

	if ( $type !== $rel['target'] ) {
		return array( 'status' => 'type', 'target' => 0, 'note' => 'نوع مقصد ' . $rel['target'] . ' است، نه ' . $type, 'name' => $name );
	}

	$uid  = isset( $item['uid'] ) && is_string( $item['uid'] ) ? trim( $item['uid'] ) : '';
	$slug = isset( $item['slug'] ) && is_string( $item['slug'] ) ? trim( $item['slug'] ) : '';

	if ( '' !== $uid || '' !== $slug ) {
		$ids = topersia_pack_find( $rel['target'], $uid, $slug, $lang );
		if ( 1 === count( $ids ) ) {
			return array( 'status' => 'ok', 'target' => $ids[0], 'note' => '', 'name' => $name );
		}
	}

	if ( '' === $name ) {
		return array( 'status' => 'rel_missing', 'target' => 0, 'note' => 'نام مقصد خالی است', 'name' => '' );
	}

	$ids = topersia_pack_by_name( $rel['target'], $name, $lang );

	if ( 1 === count( $ids ) ) {
		return array( 'status' => 'ok', 'target' => $ids[0], 'note' => '', 'name' => $name );
	}

	if ( count( $ids ) > 1 ) {
		return array( 'status' => 'rel_ambiguous', 'target' => 0, 'note' => count( $ids ) . ' پست با این نام: #' . implode( ' · #', array_slice( $ids, 0, 5 ) ) . ' — uid یا slug بنویس', 'name' => $name );
	}

	/* مقصدی که همین پرونده پیش‌تر می‌سازد — در «اجرا» پیدا می‌شود */
	if ( isset( $pending[ $rel['target'] . '|' . $lang . '|' . $name ] ) ) {
		return array( 'status' => 'ok', 'target' => 0, 'note' => 'در همین پرونده ساخته می‌شود', 'name' => $name );
	}

	return array( 'status' => 'rel_missing', 'target' => 0, 'note' => 'هیچ ' . $rel['target'] . ' با این نام در زبان ' . $lang, 'name' => $name );
}

/**
 * نقشۀ یک پست — «آزمایشی». هیچ چیز نمی‌نویسد.
 *
 * @param array $data    پروندۀ خوانده‌شده (topersia_pack_parse)
 * @param int   $index   شمارۀ پست در posts
 * @param array $pending پست‌هایی که پیش از این در همین پرونده ساخته می‌شوند:
 *                       «نوع|زبان|نام» و «نوع|زبان|slug:…»
 */
function topersia_pack_plan_post( $data, $index, $pending = array() ) {

	$type   = $data['meta']['post_type'];
	$schema = topersia_pack_schema( $type );
	$post   = isset( $data['posts'][ $index ] ) ? $data['posts'][ $index ] : null;
	$plan   = array(
		'index'     => $index,
		'action'    => 'skip',
		'target'    => 0,
		'lang'      => $data['meta']['language'],
		'label'     => '',
		'notes'     => array(),
		'problem'   => '',
		'title'     => array( 'status' => 'empty', 'value' => '' ),
		'slug'      => '',
		'original'  => 0,
		'fields'    => array(),
		'relations' => array(),
		'published' => false,
	);

	if ( ! is_array( $post ) ) {
		$plan['problem'] = 'type';
		$plan['notes'][] = 'پست شیء نیست';
		return $plan;
	}

	$default = topersia_pack_default_language();
	$lang    = isset( $post['language'] ) && is_string( $post['language'] ) && '' !== $post['language'] ? $post['language'] : $data['meta']['language'];

	$plan['lang']  = $lang;
	$plan['label'] = topersia_pack_title_from( $post, $lang );

	if ( ! in_array( $lang, topersia_pack_languages(), true ) ) {
		$plan['problem'] = 'type';
		$plan['notes'][] = 'زبان «' . $lang . '» فعال نیست';
		return $plan;
	}

	if ( isset( $post['post_type'] ) && $post['post_type'] !== $type ) {
		$plan['problem'] = 'type';
		$plan['notes'][] = 'نوع پست ' . $type . ' است، نه ' . $post['post_type'];
		return $plan;
	}

	$allowed = array( 'entity_uid', 'slug', 'language', 'translation_of', 'title', 'fields', 'relations', 'post_type', '_dup_of' );
	$extra   = array_diff( array_keys( $post ), $allowed );

	if ( $extra ) {
		$plan['notes'][] = 'کلید ناشناختۀ پست نادیده ماند: ' . implode( '، ', $extra );
	}

	if ( isset( $post['_dup_of'] ) ) {
		$plan['problem'] = 'duplicate';
		$plan['notes'][] = 'همان موجودیتِ پست ' . ( (int) $post['_dup_of'] + 1 ) . ' همین پرونده';
		return $plan;
	}

	$fields = isset( $post['fields'] ) ? $post['fields'] : array();
	$fields = is_object( $fields ) ? get_object_vars( $fields ) : $fields;

	if ( ! is_array( $fields ) ) {
		$plan['problem'] = 'type';
		$plan['notes'][] = 'fields شیء نیست';
		return $plan;
	}

	$uid  = isset( $post['entity_uid'] ) && is_string( $post['entity_uid'] ) ? trim( $post['entity_uid'] ) : '';
	$slug = isset( $post['slug'] ) && is_string( $post['slug'] ) ? trim( $post['slug'] ) : '';

	if ( '' !== $slug && ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug ) ) {
		$plan['notes'][] = 'نامک «' . $slug . '» لاتینِ خط‌تیره‌دار نیست — نادیده؛ نامک از name_en ساخته می‌شود';
		$slug            = '';
	}

	/* ترجمه — اصل در زبان پیش‌فرض */
	$original = 0;
	$orig_ref = isset( $post['translation_of'] ) && is_string( $post['translation_of'] ) ? trim( $post['translation_of'] ) : '';

	if ( $lang !== $default ) {

		if ( '' === $orig_ref ) {
			$plan['problem'] = 'required';
			$plan['notes'][] = 'ترجمه بی translation_of — اصلِ ' . $default . ' لازم است';
			return $plan;
		}

		$found = topersia_pack_find( $type, $orig_ref, $orig_ref, $default );

		if ( 1 === count( $found ) ) {
			$original = $found[0];
		} elseif ( isset( $pending[ $type . '|' . $default . '|slug:' . $orig_ref ] ) ) {
			$plan['notes'][] = 'اصل در همین پرونده ساخته می‌شود';
		} else {
			$plan['problem'] = 'rel_missing';
			$plan['notes'][] = 'اصلِ «' . $orig_ref . '» در ' . $default . ' پیدا نشد';
			return $plan;
		}

		$plan['original'] = $original;
	}

	/* تطبیق: شناسه یا نامک در همین زبان؛ ترجمه‌ای که اصلش در این زبان دارد */
	$matches = topersia_pack_find( $type, $uid, $slug, $lang );

	if ( ! $matches && $original && has_filter( 'wpml_object_id' ) ) {
		$tr = (int) apply_filters( 'wpml_object_id', $original, $type, false, $lang );
		if ( $tr && $tr !== $original ) {
			$matches = array( $tr );
		}
	}

	if ( count( $matches ) > 1 ) {
		$plan['problem'] = 'rel_ambiguous';
		$plan['notes'][] = count( $matches ) . ' پست با همین شناسه یا نامک: #' . implode( ' · #', $matches );
		return $plan;
	}

	$title = topersia_pack_title_from( $post, $lang );

	if ( $matches ) {

		$target            = $matches[0];
		$plan['action']    = 'update';
		$plan['target']    = $target;
		$plan['published'] = 'publish' === get_post_status( $target );

		if ( '' !== $slug && get_post_field( 'post_name', $target ) !== $slug ) {
			$plan['notes'][] = 'نامک عوض نمی‌شود — بخش ۱۴: نامک منتشرشده ثابت است';
		}

		if ( $plan['published'] ) {
			$plan['notes'][] = 'منتشرشده — هر تغییر بی‌درنگ دیده می‌شود';
		}

		$plan['title'] = '' === $title
			? array( 'status' => 'empty', 'value' => '' )
			: array( 'status' => get_the_title( $target ) === $title ? 'same' : 'ok', 'value' => $title );

	} else {

		if ( '' === $title ) {
			$plan['problem'] = 'required';
			$plan['notes'][] = 'بی عنوان — title یا name_' . $lang . ' لازم است';
			return $plan;
		}

		/* تکراری در سایت: همین نام در همین نوع و زبان، و پرونده نامکی نداده */
		if ( '' === $slug ) {
			$same = topersia_pack_by_name( $type, $title, $lang );
			if ( $same ) {
				$plan['problem'] = 'duplicate';
				$plan['notes'][] = 'همین نام در سایت: #' . implode( ' · #', array_slice( $same, 0, 5 ) ) . ' — برای به‌روزرسانی uid یا slug آن را بنویس؛ برای موجودیت تازۀ هم‌نام، slug تازه';
				return $plan;
			}
		}

		$plan['action'] = 'create';
		$plan['title']  = array( 'status' => 'ok', 'value' => $title );
		$plan['slug']   = $slug;
	}

	/* فیلدها */
	$classes = topersia_pack_wpml_classes();

	foreach ( $fields as $key => $value ) {

		$key = (string) $key;

		if ( ! isset( $schema['fields'][ $key ] ) ) {
			$why = isset( $schema['excluded'][ $key ] ) ? 'بیرون از بسته: ' . $schema['excluded'][ $key ] : '';
			$plan['fields'][ $key ] = array( 'status' => 'unknown', 'shown' => topersia_pack_shown( $value ), 'note' => $why );
			continue;
		}

		$def = $schema['fields'][ $key ];

		/* ترجمه: فیلد «رونوشت» WPML از اصل می‌آید، نه از پرونده */
		if ( $lang !== $default && isset( $classes[ $key ] ) && in_array( $classes[ $key ], array( 1, 3 ), true ) ) {
			$plan['fields'][ $key ] = array( 'status' => 'original', 'shown' => topersia_pack_shown( $value ), 'note' => 'WPML: ' . topersia_pack_wpml_label( $classes[ $key ] ) );
			continue;
		}

		$in  = topersia_pack_value_in( $def, $value, $key );
		$row = array( 'status' => $in['status'], 'shown' => topersia_pack_shown( $value ), 'note' => $in['note'] );

		if ( 'empty' === $in['status'] ) {
			if ( 'create' === $plan['action'] && $def['required'] ) {
				$row['status'] = 'required';
			}
		} elseif ( in_array( $in['status'], array( 'ok', 'short' ), true ) ) {
			if ( 'update' === $plan['action'] && topersia_pack_same( topersia_pack_current( $plan['target'], $key, $def ), $in['cmp'] ) ) {
				$row['status'] = 'same';
			} else {
				$row['stored'] = $in['stored'];
			}
		}

		$plan['fields'][ $key ] = $row;
	}

	if ( 'create' === $plan['action'] ) {
		foreach ( $schema['fields'] as $key => $def ) {
			if ( $def['required'] && ! isset( $plan['fields'][ $key ] ) && ! ( $lang !== $default && isset( $classes[ $key ] ) && in_array( $classes[ $key ], array( 1, 3 ), true ) ) ) {
				$plan['fields'][ $key ] = array( 'status' => 'required', 'shown' => '', 'note' => 'کلید در پرونده نیست' );
			}
		}
	}

	/* رابطه‌ها */
	$relations = isset( $post['relations'] ) ? $post['relations'] : array();
	$relations = is_object( $relations ) ? get_object_vars( $relations ) : $relations;

	foreach ( ( is_array( $relations ) ? $relations : array() ) as $key => $items ) {

		$key = (string) $key;

		if ( ! isset( $schema['relations'][ $key ] ) ) {
			$plan['relations'][ $key ] = array( array( 'status' => 'unknown', 'target' => 0, 'name' => '', 'note' => 'رابطۀ ' . $key . ' برای ' . $type . ' تعریف نشده' ) );
			continue;
		}

		$rel   = $schema['relations'][ $key ];
		$items = is_object( $items ) ? get_object_vars( $items ) : $items;
		$rows  = array();

		foreach ( ( is_array( $items ) ? array_values( $items ) : array( $items ) ) as $n => $item ) {

			$res = topersia_pack_resolve( $rel, $item, $lang, $pending );

			if ( 'ok' === $res['status'] && $rel['max'] && $n >= $rel['max'] ) {
				$res = array( 'status' => 'type', 'target' => 0, 'name' => $res['name'], 'note' => 'این رابطه ' . $rel['max'] . ' مقصد می‌گیرد' );
			}

			if ( 'ok' === $res['status'] && $res['target'] && 'update' === $plan['action'] ) {
				$object = topersia_relation( $rel['id'] );
				if ( $object ) {
					$now = 'parents' === $rel['direction'] ? $object->get_parents( $plan['target'], 'ids' ) : $object->get_children( $plan['target'], 'ids' );
					if ( in_array( $res['target'], array_map( 'absint', (array) $now ), true ) ) {
						$res['status'] = 'same';
					}
				}
			}

			$rows[] = $res;
		}

		$plan['relations'][ $key ] = $rows;
	}

	return $plan;
}

/** نمایش کوتاه یک مقدار در گزارش. */
function topersia_pack_shown( $value ) {

	if ( is_array( $value ) || is_object( $value ) ) {
		$json = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		return topersia_str_cut( (string) $json, 120 );
	}

	if ( is_bool( $value ) ) {
		return $value ? 'true' : 'false';
	}

	return topersia_str_cut( wp_strip_all_tags( (string) $value ), 120 );
}

/** شمار وضعیت‌های یک نقشه. */
function topersia_pack_plan_counts( $plan ) {

	$counts = array();

	foreach ( $plan['fields'] as $row ) {
		$counts[ $row['status'] ] = isset( $counts[ $row['status'] ] ) ? $counts[ $row['status'] ] + 1 : 1;
	}

	foreach ( $plan['relations'] as $rows ) {
		foreach ( $rows as $row ) {
			$counts[ $row['status'] ] = isset( $counts[ $row['status'] ] ) ? $counts[ $row['status'] ] + 1 : 1;
		}
	}

	if ( 'ok' === $plan['title']['status'] ) {
		$counts['ok'] = isset( $counts['ok'] ) ? $counts['ok'] + 1 : 1;
	}

	return $counts;
}

/** «در همین پرونده ساخته می‌شود» — نام و نامک پست‌های پیش از این شماره. */
function topersia_pack_pending( $data, $until ) {

	$pending = array();
	$type    = $data['meta']['post_type'];

	for ( $i = 0; $i < $until && $i < count( $data['posts'] ); $i++ ) {

		$post = $data['posts'][ $i ];

		if ( ! is_array( $post ) || isset( $post['_dup_of'] ) ) {
			continue;
		}

		$lang  = isset( $post['language'] ) && is_string( $post['language'] ) && '' !== $post['language'] ? $post['language'] : $data['meta']['language'];
		$title = topersia_pack_title_from( $post, $lang );

		if ( '' !== $title ) {
			$pending[ $type . '|' . $lang . '|' . $title ] = $i;
		}
		if ( ! empty( $post['slug'] ) && is_string( $post['slug'] ) ) {
			$pending[ $type . '|' . $lang . '|slug:' . $post['slug'] ] = $i;
		}
	}

	return $pending;
}

/* ═══════════════════════════════════════════════════════════════════════
 *  ۱۹-د — اجرا و برگرداندن
 * ═══════════════════════════════════════════════════════════════════════ */

/**
 * ردیف برگشت در کانال import لاگ هسته — مستقیم در جدول، نه از topersia_log().
 *
 * چرا مستقیم: topersia_log() با «لاگ خاموش»، آستانۀ سطح یا سقف پنجاه رویداد در
 * هر درخواست چیزی نمی‌نویسد؛ برگشت‌پذیری نباید به تنظیم لاگ بسته باشد.
 */
function topersia_pack_log_prev( $run, $post_id, $action, $prev ) {

	global $wpdb;

	if ( get_option( 'topersia_log_schema' ) !== TOPERSIA_LOG_SCHEMA && function_exists( 'topersia_log_install' ) ) {
		topersia_log_install();
	}

	$ok = $wpdb->insert(
		topersia_log_table(),
		array(
			'logged_at'  => current_time( 'mysql' ),
			'level'      => 'info',
			'channel'    => 'import',
			'message'    => sprintf( 'درون‌ریزی %s — %s #%d', $run, 'create' === $action ? 'ساخت' : 'به‌روزرسانی', $post_id ),
			'context'    => wp_json_encode( array( 'run' => $run, 'post' => (int) $post_id, 'action' => $action, 'prev' => $prev ), JSON_UNESCAPED_UNICODE ),
			'request_id' => function_exists( 'topersia_request_id' ) ? topersia_request_id() : '',
			'user_id'    => get_current_user_id(),
			'url'        => isset( $_SERVER['REQUEST_URI'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 0, 255 ) : '',
			'source'     => 'topersia-pack.php',
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
	);

	return $ok ? (int) $wpdb->insert_id : 0;
}

/** شرط SQL که ردیف‌های برگشتِ آخرین درون‌ریزی را از هرس و پاک‌کردن لاگ کنار می‌گذارد. */
function topersia_pack_log_keep_sql() {

	global $wpdb;

	$last = get_option( TOPERSIA_PACK_LAST_RUN );

	if ( ! is_array( $last ) || empty( $last['run'] ) || ! empty( $last['undone'] ) ) {
		return '';
	}

	return $wpdb->prepare( " AND NOT ( channel = 'import' AND context LIKE %s )", '%' . $wpdb->esc_like( '"run":"' . $last['run'] . '"' ) . '%' );
}

/** یال‌های یک پست در یک رابطه — جفت‌های [والد، فرزند]. */
function topersia_pack_rel_pairs( $object, $post_id, $direction ) {

	$pairs = array();

	if ( 'parents' === $direction ) {
		foreach ( array_map( 'absint', (array) $object->get_parents( $post_id, 'ids' ) ) as $parent ) {
			$pairs[] = array( $parent, (int) $post_id );
		}
	} else {
		foreach ( array_map( 'absint', (array) $object->get_children( $post_id, 'ids' ) ) as $child ) {
			$pairs[] = array( (int) $post_id, $child );
		}
	}

	return $pairs;
}

/**
 * یک پست را اجرا می‌کند — نقشه تازه از نو ساخته می‌شود (پایگاه‌داده میان
 * «آزمایشی» و «اجرا» ممکن است عوض شده باشد)، سپس مقدار پیشین ثبت، سپس نوشتن.
 *
 * @return array نقشه با result: created · updated · unchanged · skipped
 */
function topersia_pack_apply( $data, $index, $run ) {

	$plan    = topersia_pack_plan_post( $data, $index, topersia_pack_pending( $data, $index ) );
	$type    = $data['meta']['post_type'];
	$schema  = topersia_pack_schema( $type );
	$default = topersia_pack_default_language();

	$plan['result'] = 'skipped';

	if ( 'skip' === $plan['action'] ) {
		return $plan;
	}

	/* مقصدی که همین پرونده پیش‌تر ساخت — حالا پیدا می‌شود */
	foreach ( $plan['relations'] as $key => $rows ) {
		foreach ( $rows as $n => $row ) {
			if ( 'ok' === $row['status'] && ! $row['target'] ) {
				$again = topersia_pack_resolve( $schema['relations'][ $key ], array( 'name' => $row['name'], 'type' => $schema['relations'][ $key ]['target'] ), $plan['lang'] );
				$plan['relations'][ $key ][ $n ] = $again;
			}
		}
	}

	if ( $plan['lang'] !== $default && ! $plan['original'] ) {
		$post     = $data['posts'][ $index ];
		$orig_ref = trim( (string) $post['translation_of'] );
		$found    = topersia_pack_find( $type, $orig_ref, $orig_ref, $default );
		if ( 1 !== count( $found ) ) {
			$plan['result']  = 'skipped';
			$plan['notes'][] = 'اصل پیدا نشد';
			return $plan;
		}
		$plan['original'] = $found[0];
	}

	$writes = array();

	foreach ( $plan['fields'] as $key => $row ) {
		if ( array_key_exists( 'stored', $row ) ) {
			$writes[ $key ] = $row['stored'];
		}
	}

	$links = array();

	foreach ( $plan['relations'] as $key => $rows ) {
		foreach ( $rows as $row ) {
			if ( 'ok' === $row['status'] && $row['target'] ) {
				$links[ $key ][] = (int) $row['target'];
			}
		}
	}

	$title_change = 'ok' === $plan['title']['status'];

	if ( 'update' === $plan['action'] && ! $writes && ! $links && ! $title_change ) {
		$plan['result'] = 'unchanged';
		return $plan;
	}

	$post_id = (int) $plan['target'];

	if ( 'create' === $plan['action'] ) {

		$meta_input = $writes;

		/* پست تازه: همیشه پیش‌نویس، همیشه needs_verification — P5-D247 */
		$meta_input['content_status'] = 'needs_verification';

		$post_id = wp_insert_post(
			array(
				'post_type'   => $type,
				'post_status' => 'draft',
				'post_title'  => $plan['title']['value'],
				'post_name'   => $plan['slug'],
				'meta_input'  => wp_slash( $meta_input ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$plan['result']  = 'skipped';
			$plan['notes'][] = 'ساخت شکست خورد: ' . $post_id->get_error_message();
			topersia_log( 'درون‌ریزی: ساخت شکست خورد', 'error', 'import', array( 'run' => $run, 'index' => $index, 'error' => $post_id->get_error_message() ) );
			return $plan;
		}

		$post_id = (int) $post_id;

		topersia_pack_log_prev( $run, $post_id, 'create', array( 'lang' => $plan['lang'] ) );

		/* زبان WPML — ترجمه با trid اصل */
		if ( has_action( 'wpml_set_element_language_details' ) ) {
			$element = apply_filters( 'wpml_element_type', $type );
			$args    = array( 'element_id' => $post_id, 'element_type' => $element, 'trid' => false, 'language_code' => $plan['lang'] );
			if ( $plan['original'] ) {
				$args['trid']                 = apply_filters( 'wpml_element_trid', null, $plan['original'], $element );
				$args['source_language_code'] = $default;
			}
			do_action( 'wpml_set_element_language_details', $args );
		}

		$plan['target'] = $post_id;
		$plan['result'] = 'created';

	} else {

		$prev = array( 'lang' => $plan['lang'], 'meta' => array() );

		if ( $title_change ) {
			$prev['title'] = get_post_field( 'post_title', $post_id );
		}

		foreach ( array_keys( $writes ) as $key ) {
			$prev['meta'][ $key ] = get_post_meta( $post_id, $key, false );
		}

		$prev['rel'] = topersia_pack_rel_snapshot( $schema, $links, $post_id );

		topersia_pack_log_prev( $run, $post_id, 'update', $prev );

		foreach ( $writes as $key => $value ) {
			update_post_meta( $post_id, $key, wp_slash( $value ) );
		}

		if ( $title_change ) {
			wp_update_post( array( 'ID' => $post_id, 'post_title' => $plan['title']['value'] ) );
		}

		$plan['result'] = 'updated';
	}

	/* یال‌ها — با API جت‌انجین */
	if ( $links ) {

		if ( 'created' === $plan['result'] ) {
			$before = topersia_pack_rel_snapshot( $schema, $links, $post_id );
		}

		foreach ( $links as $key => $targets ) {

			$rel    = $schema['relations'][ $key ];
			$object = topersia_relation( $rel['id'] );

			if ( ! $object ) {
				continue;
			}

			foreach ( $targets as $target ) {
				if ( 'parents' === $rel['direction'] ) {
					topersia_relation_connect( $object, $target, $post_id );
				} else {
					topersia_relation_connect( $object, $post_id, $target );
				}
			}
		}

		if ( 'created' === $plan['result'] ) {
			/* پست تازه: یال‌هایی که «برگرداندن» باید بردارد — و اگر رابطۀ والدِ یکتا فرزندی را از والد دیگر گرفت، آن یال */
			topersia_pack_log_prev( $run, $post_id, 'create', array( 'lang' => $plan['lang'], 'rel' => $before, 'links' => true ) );
		}
	}

	/* ترجمه: فیلدهای رونوشتِ WPML از اصل — با API خود WPML */
	if ( $plan['original'] && has_action( 'wpml_sync_custom_field' ) ) {
		$classes = topersia_pack_wpml_classes();
		foreach ( array_keys( $schema['fields'] ) as $key ) {
			if ( isset( $classes[ $key ] ) && in_array( $classes[ $key ], array( 1, 3 ), true ) ) {
				do_action( 'wpml_sync_custom_field', $plan['original'], $key );
			}
		}
		if ( 'created' === $plan['result'] ) {
			update_post_meta( $post_id, 'content_status', 'needs_verification' );
			$uid = get_post_meta( $plan['original'], 'entity_uid', true );
			if ( '' !== (string) $uid ) {
				update_post_meta( $post_id, 'entity_uid', $uid );
			}
		}
	}

	/* ذخیرۀ پایانی: قلاب‌های save_post هسته (نامک از name_en، entity_uid، هش محتوا، تاکردن) روی مقدار تازه */
	wp_update_post( array( 'ID' => $post_id ) );

	return $plan;
}

/**
 * عکس یال‌هایی که این اجرا ممکن است عوض کند.
 *
 * هم یال‌های خودِ پست در هر رابطۀ لمس‌شده، هم — در رابطۀ والدِ یکتا از سمت
 * والد — والدهای کنونیِ هر مقصد، چون update() جت‌انجین آن فرزند را از والد
 * پیشین جدا می‌کند.
 */
function topersia_pack_rel_snapshot( $schema, $links, $post_id ) {

	$snap = array();

	foreach ( array_keys( $links ) as $key ) {

		$rel    = $schema['relations'][ $key ];
		$object = topersia_relation( $rel['id'] );

		if ( ! $object ) {
			continue;
		}

		$pairs = topersia_pack_rel_pairs( $object, $post_id, $rel['direction'] );

		if ( 'children' === $rel['direction'] && method_exists( $object, 'is_single_parent' ) && $object->is_single_parent() ) {
			foreach ( $links[ $key ] as $target ) {
				$pairs = array_merge( $pairs, topersia_pack_rel_pairs( $object, $target, 'parents' ) );
			}
		}

		$snap[] = array(
			'id'      => $rel['id'],
			'post'    => (int) $post_id,
			'dir'     => $rel['direction'],
			'targets' => array_values( array_map( 'intval', $links[ $key ] ) ),
			'pairs'   => array_values( array_unique( $pairs, SORT_REGULAR ) ),
		);
	}

	return $snap;
}

/** یال‌های کنونی در همان دامنۀ یک عکس. */
function topersia_pack_rel_scope_now( $object, $snap ) {

	$pairs = topersia_pack_rel_pairs( $object, $snap['post'], $snap['dir'] );

	if ( 'children' === $snap['dir'] && method_exists( $object, 'is_single_parent' ) && $object->is_single_parent() ) {
		foreach ( $snap['targets'] as $target ) {
			$pairs = array_merge( $pairs, topersia_pack_rel_pairs( $object, $target, 'parents' ) );
		}
	}

	return array_values( array_unique( $pairs, SORT_REGULAR ) );
}

/** یک دسته از پست‌های پرونده را اجرا می‌کند. */
function topersia_pack_run_batch( $data, $offset, $size, $run ) {

	$results = array();
	$total   = count( $data['posts'] );

	for ( $i = $offset; $i < min( $total, $offset + $size ); $i++ ) {
		$results[] = topersia_pack_apply( $data, $i, $run );
	}

	return array(
		'results' => $results,
		'next'    => min( $total, $offset + $size ),
		'total'   => $total,
		'done'    => $offset + $size >= $total,
	);
}

/** ردیف‌های برگشتِ یک اجرا، تازه‌ترین نخست. */
function topersia_pack_undo_rows( $run ) {

	global $wpdb;

	// phpcs:ignore WordPress.DB.PreparedSQL
	$rows = $wpdb->get_results( $wpdb->prepare(
		'SELECT id, context FROM ' . topersia_log_table() . " WHERE channel = 'import' AND context LIKE %s ORDER BY id DESC",
		'%' . $wpdb->esc_like( '"run":"' . $run . '"' ) . '%'
	) );

	$out = array();

	/* فقط ردیف‌های برگشت — رویدادهای خلاصۀ همان اجرا هم run دارند ولی post و action نه */
	foreach ( (array) $rows as $row ) {
		$ctx = json_decode( (string) $row->context, true );
		if ( is_array( $ctx ) && isset( $ctx['run'], $ctx['post'], $ctx['action'] ) && $ctx['run'] === $run && in_array( $ctx['action'], array( 'create', 'update' ), true ) ) {
			$out[] = $ctx;
		}
	}

	return $out;
}

/** یک عکس یال را برمی‌گرداند: یال افزوده برداشته، یال برداشته دوباره. */
function topersia_pack_undo_rel( $snap ) {

	$object = topersia_relation( $snap['id'] );

	if ( ! $object ) {
		return;
	}

	$before = array_map( function ( $p ) { return array( (int) $p[0], (int) $p[1] ); }, (array) $snap['pairs'] );
	$now    = topersia_pack_rel_scope_now( $object, $snap );

	foreach ( $now as $pair ) {
		if ( ! in_array( $pair, $before, true ) ) {
			topersia_relation_disconnect( $object, $pair[0], $pair[1] );
		}
	}

	foreach ( $before as $pair ) {
		if ( ! in_array( $pair, $now, true ) ) {
			topersia_relation_connect( $object, $pair[0], $pair[1] );
		}
	}
}

/**
 * یک دسته از ردیف‌های برگشت. پست ساخته‌شده به زباله می‌رود (برگشت‌پذیر)؛
 * پست به‌روزشده مقدار پیشین هر متاکلید و عنوانش را پس می‌گیرد؛ یال‌ها به عکس
 * پیش از اجرا برمی‌گردند.
 */
function topersia_pack_undo_batch( $run, $offset, $size ) {

	$rows  = topersia_pack_undo_rows( $run );
	$total = count( $rows );
	$done  = array();

	for ( $i = $offset; $i < min( $total, $offset + $size ); $i++ ) {

		$ctx     = $rows[ $i ];
		$post_id = (int) $ctx['post'];
		$prev    = isset( $ctx['prev'] ) && is_array( $ctx['prev'] ) ? $ctx['prev'] : array();

		foreach ( (array) ( isset( $prev['rel'] ) ? $prev['rel'] : array() ) as $snap ) {
			topersia_pack_undo_rel( $snap );
		}

		if ( 'create' === $ctx['action'] ) {

			if ( empty( $prev['links'] ) && get_post( $post_id ) && 'trash' !== get_post_status( $post_id ) ) {
				wp_trash_post( $post_id );
			}

		} else {

			/*
			 * مقدار پیشین عیناً — بی دو محافظ ردیف (بخش ۸-ج). محافظ حذفِ کامل
			 * Repeater را به بازنشستگی تبدیل می‌کند و ردیف تازه کنار ردیف
			 * بازنشسته می‌نشیند؛ برگرداندن باید همان مقدار پیش از درون‌ریزی را
			 * بگذارد، نه ادغام. محافظ‌ها فقط برای همین نوشتن کنار می‌روند.
			 */
			$guards = array(
				array( 'update_post_metadata', 'topersia_guard_row_delete', 10, 5 ),
				array( 'delete_post_metadata', 'topersia_guard_row_wipe', 10, 5 ),
			);
			$removed = array();
			foreach ( $guards as $g ) {
				if ( has_filter( $g[0], $g[1] ) === $g[2] ) {
					remove_filter( $g[0], $g[1], $g[2] );
					$removed[] = $g;
				}
			}

			try {
				foreach ( (array) ( isset( $prev['meta'] ) ? $prev['meta'] : array() ) as $key => $values ) {
					delete_post_meta( $post_id, $key );
					foreach ( (array) $values as $value ) {
						add_post_meta( $post_id, $key, wp_slash( $value ) );
					}
				}
			} finally {
				foreach ( $removed as $g ) {
					add_filter( $g[0], $g[1], $g[2], $g[3] );
				}
			}

			if ( isset( $prev['title'] ) ) {
				wp_update_post( array( 'ID' => $post_id, 'post_title' => $prev['title'] ) );
			} else {
				wp_update_post( array( 'ID' => $post_id ) );
			}
		}

		$done[] = array( 'post' => $post_id, 'action' => $ctx['action'] );
	}

	$finished = $offset + $size >= $total;

	if ( $finished ) {
		$last = get_option( TOPERSIA_PACK_LAST_RUN );
		if ( is_array( $last ) && isset( $last['run'] ) && $last['run'] === $run ) {
			$last['undone'] = time();
			update_option( TOPERSIA_PACK_LAST_RUN, $last, false );
		}
		topersia_log( 'درون‌ریزی برگردانده شد', 'notice', 'import', array( 'run' => $run, 'rows' => $total ) );
	}

	return array( 'done' => $finished, 'next' => min( $total, $offset + $size ), 'total' => $total, 'rows' => $done );
}

/* ═══════════════════════════════════════════════════════════════════════
 *  ۱۹-ه — پیشخوان
 * ═══════════════════════════════════════════════════════════════════════ */

add_filter( 'topersia_admin_tabs', 'topersia_pack_admin_tab', 11 );

function topersia_pack_admin_tab( $tabs ) {

	$tabs['pack'] = array(
		'label'    => 'بستۀ محتوا',
		'callback' => 'topersia_pack_admin_render',
	);

	return $tabs;
}

/** کلید transient یک بارگذاری. */
function topersia_pack_token_key( $token ) {
	return 'topersia_pack_' . preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $token ) );
}

/** پروندۀ بارگذاری‌شدۀ یک توکن — فقط برای همان کاربر. */
function topersia_pack_token_data( $token ) {

	$stored = get_transient( topersia_pack_token_key( $token ) );

	if ( ! is_array( $stored ) || (int) $stored['user'] !== get_current_user_id() ) {
		return null;
	}

	return $stored;
}

/** یک پست از گزارش — HTML. */
function topersia_pack_render_plan( $plan, $mode = 'plan' ) {

	$statuses = topersia_pack_statuses();
	$actions  = array( 'create' => 'ساخت', 'update' => 'به‌روزرسانی', 'skip' => 'رد' );
	$results  = array( 'created' => 'ساخته شد', 'updated' => 'به‌روز شد', 'unchanged' => 'بی‌تغییر', 'skipped' => 'رد شد' );
	$counts   = topersia_pack_plan_counts( $plan );
	$bad      = array_intersect_key( $counts, array_flip( array( 'unknown', 'option', 'type', 'rel_missing', 'rel_ambiguous', 'duplicate' ) ) );

	$head = sprintf(
		'#%d · %s · <strong>%s</strong> · %s%s',
		(int) $plan['index'] + 1,
		esc_html( $plan['lang'] ),
		esc_html( '' !== $plan['label'] ? $plan['label'] : '—' ),
		esc_html( 'result' === $mode && isset( $plan['result'] ) ? $results[ $plan['result'] ] : $actions[ $plan['action'] ] ),
		$plan['target'] ? ' <a href="' . esc_url( get_edit_post_link( $plan['target'], 'raw' ) ) . '">#' . (int) $plan['target'] . '</a>' : ''
	);

	$chips = '';

	foreach ( $counts as $status => $n ) {
		$chips .= sprintf( ' <span class="tp-pack-chip" style="color:%s">%s %d</span>', esc_attr( $statuses[ $status ][1] ), esc_html( $statuses[ $status ][0] ), (int) $n );
	}

	if ( '' !== $plan['problem'] ) {
		$chips .= sprintf( ' <span class="tp-pack-chip" style="color:%s">%s</span>', esc_attr( $statuses[ $plan['problem'] ][1] ), esc_html( $statuses[ $plan['problem'] ][0] ) );
	}

	$html  = '<details class="tp-pack-post' . ( $bad || '' !== $plan['problem'] ? ' has-bad' : '' ) . '" data-action="' . esc_attr( $plan['action'] ) . '"' . ( $bad || '' !== $plan['problem'] ? ' open' : '' ) . '>';
	$html .= '<summary>' . $head . $chips . '</summary>';

	foreach ( $plan['notes'] as $note ) {
		$html .= '<p class="tp-pack-note">' . esc_html( $note ) . '</p>';
	}

	$rows = '';

	if ( 'empty' !== $plan['title']['status'] ) {
		$rows .= topersia_pack_render_row( 'عنوان', $plan['title']['status'], $plan['title']['value'], '' );
	}

	foreach ( $plan['fields'] as $key => $row ) {
		$rows .= topersia_pack_render_row( $key, $row['status'], $row['shown'], $row['note'] );
	}

	foreach ( $plan['relations'] as $key => $list ) {
		foreach ( $list as $row ) {
			$rows .= topersia_pack_render_row( 'رابطۀ ' . $key, $row['status'], $row['name'] . ( $row['target'] ? ' → #' . $row['target'] : '' ), $row['note'] );
		}
	}

	if ( '' !== $rows ) {
		$html .= '<table class="widefat striped tp-pack-table"><thead><tr><th>فیلد</th><th>مقدار</th><th>وضعیت</th></tr></thead><tbody>' . $rows . '</tbody></table>';
	}

	return $html . '</details>';
}

function topersia_pack_render_row( $key, $status, $shown, $note ) {

	$statuses = topersia_pack_statuses();
	$label    = isset( $statuses[ $status ] ) ? $statuses[ $status ] : array( $status, '#646970' );

	return sprintf(
		'<tr><td><code>%s</code></td><td>%s</td><td style="color:%s">%s%s</td></tr>',
		esc_html( $key ),
		esc_html( $shown ),
		esc_attr( $label[1] ),
		esc_html( $label[0] ),
		'' !== $note ? '<br><small>' . esc_html( $note ) . '</small>' : ''
	);
}

/** زبانۀ «بستۀ محتوا». */
function topersia_pack_admin_render() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$types   = topersia_pack_types();
	$langs   = topersia_pack_languages();
	$nonce   = wp_create_nonce( 'topersia_pack' );
	$upload  = null;
	$error   = null;

	/* بارگذاری */
	if ( isset( $_POST['topersia_pack_upload'] ) ) {

		check_admin_referer( 'topersia_pack_upload' );

		$file = isset( $_FILES['topersia_pack_file'] ) ? $_FILES['topersia_pack_file'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		if ( ! $file || ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			$error = new WP_Error( 'pack_upload', 'پرونده‌ای بارگذاری نشد.' );
		} elseif ( (int) $file['size'] > TOPERSIA_PACK_MAX_BYTES ) {
			$error = new WP_Error( 'pack_size', 'پرونده از ده مگابایت بزرگ‌تر است.' );
		} else {
			$raw  = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			$data = topersia_pack_parse( $raw );
			if ( is_wp_error( $data ) ) {
				$error = $data;
			} else {
				$token = strtolower( wp_generate_password( 20, false, false ) );
				set_transient( topersia_pack_token_key( $token ), array(
					'user' => get_current_user_id(),
					'data' => $data,
					'name' => sanitize_file_name( $file['name'] ),
					'sha1' => sha1( $raw ),
					'time' => time(),
					'run'  => '',
				), 12 * HOUR_IN_SECONDS );
				$upload = array( 'token' => $token, 'data' => $data, 'name' => sanitize_file_name( $file['name'] ) );
			}
		}
	}

	echo '<style>
.tp-pack-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(20rem,1fr));gap:1rem;max-width:72rem}
.tp-pack-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:1rem 1.2rem}
.tp-pack-card h3{margin-top:0}
.tp-pack-post{background:#fff;border:1px solid #dcdcde;border-radius:8px;margin:.5rem 0;padding:.4rem .8rem;max-width:72rem}
.tp-pack-post.has-bad{border-color:#d63638}
.tp-pack-post summary{cursor:pointer;padding:.3rem 0}
.tp-pack-chip{font-size:12px;margin-inline-start:.4rem}
.tp-pack-note{margin:.3rem 0;color:#50575e}
.tp-pack-table td{vertical-align:top;word-break:break-word}
.tp-pack-bar{height:8px;background:#e5e5e5;border-radius:4px;max-width:40rem;overflow:hidden}
.tp-pack-bar i{display:block;height:100%;width:0;background:#1D6F6A;transition:width .3s}
</style>';

	echo '<p style="max-width:62em">برای هر نوع پست یک پروندۀ قالب — ساخته از اسکیمای زندۀ جت‌انجین — که به هوش مصنوعی داده می‌شود، و درون‌ریزی همان پروندۀ پرشده. گسترش واردکنندۀ «اسکلت جغرافیایی»: پست تازه همیشه <strong>پیش‌نویس</strong> با وضعیت <code>needs_verification</code>؛ نامک و <code>entity_uid</code> هرگز بازنویسی نمی‌شوند؛ مقدار پیشین هر پست در کانال <code>import</code> لاگ می‌ماند و آخرین درون‌ریزی برگشت‌پذیر است. P5-D272</p>';

	$export = admin_url( 'admin-post.php' );

	echo '<div class="tp-pack-grid">';

	/* قالب */
	echo '<form class="tp-pack-card" method="get" action="' . esc_url( $export ) . '">';
	echo '<h3>قالب برای هوش مصنوعی</h3>';
	echo '<input type="hidden" name="action" value="topersia_pack_export"><input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
	echo '<p><label>نوع پست<br><select name="type">';
	foreach ( $types as $slug => $label ) {
		echo '<option value="' . esc_attr( $slug ) . '">' . esc_html( $label . ' — ' . $slug ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><label>زبان محتوا<br><select name="lang">';
	foreach ( $langs as $code ) {
		echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $code ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><button class="button button-primary" name="what" value="template">پروندۀ قالب</button> <button class="button" name="what" value="schema">JSON Schema</button></p>';
	echo '</form>';

	/* برون‌بری پست */
	echo '<form class="tp-pack-card" method="get" action="' . esc_url( $export ) . '">';
	echo '<h3>برون‌بری پست موجود</h3>';
	echo '<input type="hidden" name="action" value="topersia_pack_export"><input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '"><input type="hidden" name="what" value="post">';
	echo '<p><label>شناسۀ پست — چند شناسۀ هم‌نوع با ویرگول<br><input type="text" name="post" class="regular-text" dir="ltr" placeholder="399, 400"></label></p>';
	echo '<p class="description">برای ویرایش یا ترجمه — همان شکل قالب. درون‌ریزیِ بی‌دست‌کاری هیچ چیز را عوض نمی‌کند.</p>';
	echo '<p><button class="button button-primary">برون‌بری</button></p>';
	echo '</form>';

	/* بارگذاری */
	echo '<form class="tp-pack-card" method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin.php?page=topersia&tab=pack' ) ) . '">';
	echo '<h3>درون‌ریزی</h3>';
	wp_nonce_field( 'topersia_pack_upload' );
	echo '<p><input type="file" name="topersia_pack_file" accept=".json,application/json"></p>';
	echo '<p class="description">نخست گزارش آزمایشی؛ تا «اجرا» نزنی هیچ چیز نوشته نمی‌شود.</p>';
	echo '<p><button class="button button-primary" name="topersia_pack_upload" value="1">گزارش آزمایشی</button></p>';
	echo '</form>';

	echo '</div>';

	if ( $error ) {
		echo '<div class="notice notice-error inline"><p>' . esc_html( $error->get_error_message() ) . '</p></div>';
	}

	/* گزارش و اجرا */
	if ( $upload ) {

		$type   = $upload['data']['meta']['post_type'];
		$schema = topersia_pack_schema( $type );
		$status = topersia_pack_glossary_has( $schema, 'needs_verification' );

		echo '<h2>' . esc_html( 'گزارش آزمایشی — ' . $upload['name'] ) . '</h2>';
		printf(
			'<p>%s · %s · %s پست</p>',
			esc_html( $type ),
			esc_html( $upload['data']['meta']['language'] ),
			esc_html( number_format_i18n( count( $upload['data']['posts'] ) ) )
		);

		if ( isset( $upload['data']['meta']['schema_hash'] ) && $upload['data']['meta']['schema_hash'] !== $schema['hash'] ) {
			echo '<div class="notice notice-warning inline"><p>اسکیمای جت‌انجین پس از ساخت این پرونده عوض شده — فیلدهای تازه یا برداشته در گزارش پیداست.</p></div>';
		}

		if ( ! $status ) {
			echo '<div class="notice notice-warning inline"><p>واژه‌نامۀ وضعیت محتوا گزینۀ <code>needs_verification</code> ندارد — پست تازه همین کلید را می‌گیرد؛ نخست گزینه را در جت‌انجین بیفزا (HANDOVER بخش ۷).</p></div>';
		}

		echo '<div id="tp-pack-summary"></div><div class="tp-pack-bar"><i id="tp-pack-bar"></i></div><div id="tp-pack-report"></div>';
		echo '<p><button type="button" class="button button-primary" id="tp-pack-run" disabled>اجرا</button> <span id="tp-pack-state"></span></p>';

		topersia_pack_admin_script( $upload['token'], $nonce );
	}

	/* آخرین درون‌ریزی */
	$last = get_option( TOPERSIA_PACK_LAST_RUN );

	echo '<h2>آخرین درون‌ریزی</h2>';

	if ( ! is_array( $last ) || empty( $last['run'] ) ) {
		echo '<p>هنوز درون‌ریزی‌ای اجرا نشده.</p>';
	} else {
		printf(
			'<p><code>%s</code> · %s · %s · %s ساخت · %s به‌روزرسانی · %s بی‌تغییر · %s رد%s</p>',
			esc_html( $last['run'] ),
			esc_html( isset( $last['file'] ) ? $last['file'] : '' ),
			esc_html( wp_date( 'Y-m-d H:i', (int) $last['time'] ) ),
			esc_html( number_format_i18n( (int) $last['created'] ) ),
			esc_html( number_format_i18n( (int) $last['updated'] ) ),
			esc_html( number_format_i18n( (int) $last['unchanged'] ) ),
			esc_html( number_format_i18n( (int) $last['skipped'] ) ),
			! empty( $last['undone'] ) ? ' · <strong>برگردانده شد ' . esc_html( wp_date( 'Y-m-d H:i', (int) $last['undone'] ) ) . '</strong>' : ''
		);
		if ( empty( $last['undone'] ) ) {
			echo '<p><button type="button" class="button" id="tp-pack-undo" data-run="' . esc_attr( $last['run'] ) . '">برگرداندن این درون‌ریزی</button> <span id="tp-pack-undo-state"></span></p>';
			echo '<p class="description">پست‌های ساخته‌شده به زباله می‌روند؛ پست‌های به‌روزشده مقدار و عنوان پیشین و یال‌های پیشین را پس می‌گیرند. داده از کانال <code>import</code> لاگ هسته — هرس و «پاک‌کردن لاگ» ردیف‌های همین درون‌ریزی را نگه می‌دارند.</p>';
			if ( ! $upload ) {
				topersia_pack_admin_script( '', $nonce );
			}
		}
	}
}

/** آیا گزینۀ کلیدی در واژه‌نامۀ content_status هست — از متاباکس زنده. */
function topersia_pack_glossary_has( $schema, $value ) {

	foreach ( topersia_diag_meta_boxes() as $box ) {
		$list = isset( $box['meta_fields'] ) ? $box['meta_fields'] : array();
		$list = is_string( $list ) ? json_decode( $list, true ) : $list;
		foreach ( (array) $list as $field ) {
			if ( is_array( $field ) && isset( $field['name'] ) && 'content_status' === $field['name'] && ! empty( $field['glossary_id'] ) ) {
				return isset( topersia_pack_glossary( $field['glossary_id'] )[ $value ] );
			}
		}
	}

	return false;
}

/** اسکریپت گزارش، اجرا و برگرداندن — دسته‌دسته با AJAX. */
function topersia_pack_admin_script( $token, $nonce ) {

	$cfg = array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => $nonce,
		'token' => $token,
		'plan'  => 4,
		'run'   => 3,
		'undo'  => 10,
	);

	?>
<script>
( function () {
	var C = <?php echo wp_json_encode( $cfg ); ?>;
	function post( action, data ) {
		var f = new FormData();
		f.append( 'action', action );
		f.append( '_wpnonce', C.nonce );
		Object.keys( data ).forEach( function ( k ) { f.append( k, data[ k ] ); } );
		return fetch( C.ajax, { method: 'POST', body: f, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } );
	}
	var report = document.getElementById( 'tp-pack-report' );
	var bar = document.getElementById( 'tp-pack-bar' );
	var run = document.getElementById( 'tp-pack-run' );
	var state = document.getElementById( 'tp-pack-state' );
	var summary = document.getElementById( 'tp-pack-summary' );
	function progress( next, total ) { if ( bar ) { bar.style.width = ( total ? Math.round( 100 * next / total ) : 100 ) + '%'; } }
	function fail( el, r ) { el.textContent = ( r && r.data && r.data.message ) ? r.data.message : 'خطا'; }
	function plan( offset ) {
		post( 'topersia_pack_plan', { token: C.token, offset: offset, size: C.plan } ).then( function ( r ) {
			if ( ! r || ! r.success ) { fail( state, r ); return; }
			report.insertAdjacentHTML( 'beforeend', r.data.html );
			progress( r.data.next, r.data.total );
			if ( r.data.done ) {
				var posts = report.querySelectorAll( '.tp-pack-post' );
				var n = { create: 0, update: 0, skip: 0 };
				posts.forEach( function ( p ) { n[ p.dataset.action ] = ( n[ p.dataset.action ] || 0 ) + 1; } );
				summary.textContent = 'ساخت ' + n.create + ' · به‌روزرسانی ' + n.update + ' · رد ' + n.skip;
				run.disabled = ! ( n.create + n.update );
				state.textContent = 'گزارش کامل شد — هیچ چیز نوشته نشده.';
			} else { plan( r.data.next ); }
		} ).catch( function () { state.textContent = 'خطای شبکه'; } );
	}
	function execute( offset ) {
		post( 'topersia_pack_run', { token: C.token, offset: offset, size: C.run } ).then( function ( r ) {
			if ( ! r || ! r.success ) { fail( state, r ); return; }
			if ( 0 === offset ) { report.innerHTML = ''; }
			report.insertAdjacentHTML( 'beforeend', r.data.html );
			progress( r.data.next, r.data.total );
			state.textContent = r.data.next + ' از ' + r.data.total;
			if ( r.data.done ) { state.textContent = 'اجرا تمام شد — ' + r.data.summary; } else { execute( r.data.next ); }
		} ).catch( function () { state.textContent = 'خطای شبکه'; } );
	}
	if ( C.token && report ) {
		plan( 0 );
		run.addEventListener( 'click', function () {
			if ( ! window.confirm( 'اجرا؟ پست‌های تازه پیش‌نویس ساخته می‌شوند و مقدار پیشینِ به‌روزشده‌ها در لاگ می‌ماند.' ) ) { return; }
			run.disabled = true;
			progress( 0, 1 );
			execute( 0 );
		} );
	}
	var undo = document.getElementById( 'tp-pack-undo' );
	if ( undo ) {
		var ustate = document.getElementById( 'tp-pack-undo-state' );
		function back( offset ) {
			post( 'topersia_pack_undo', { run: undo.dataset.run, offset: offset, size: C.undo } ).then( function ( r ) {
				if ( ! r || ! r.success ) { fail( ustate, r ); return; }
				ustate.textContent = r.data.next + ' از ' + r.data.total;
				if ( r.data.done ) { ustate.textContent = 'برگردانده شد.'; } else { back( r.data.next ); }
			} ).catch( function () { ustate.textContent = 'خطای شبکه'; } );
		}
		undo.addEventListener( 'click', function () {
			if ( ! window.confirm( 'آخرین درون‌ریزی برگردانده شود؟' ) ) { return; }
			undo.disabled = true;
			back( 0 );
		} );
	}
} )();
</script>
	<?php
}

/** گارد مشترک نقطه‌های AJAX. */
function topersia_pack_ajax_guard() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'دسترسی ندارید.' ), 403 );
	}

	if ( ! check_ajax_referer( 'topersia_pack', '_wpnonce', false ) ) {
		wp_send_json_error( array( 'message' => 'نشست منقضی شده — صفحه را تازه کن.' ), 403 );
	}
}

add_action( 'wp_ajax_topersia_pack_plan', 'topersia_pack_ajax_plan' );

function topersia_pack_ajax_plan() {

	topersia_pack_ajax_guard();

	$stored = topersia_pack_token_data( isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '' );

	if ( ! $stored ) {
		wp_send_json_error( array( 'message' => 'بارگذاری پیدا نشد — دوباره بارگذاری کن.' ) );
	}

	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$size   = isset( $_POST['size'] ) ? max( 1, min( 20, absint( $_POST['size'] ) ) ) : 4;
	$data   = $stored['data'];
	$total  = count( $data['posts'] );
	$html   = '';

	for ( $i = $offset; $i < min( $total, $offset + $size ); $i++ ) {
		$html .= topersia_pack_render_plan( topersia_pack_plan_post( $data, $i, topersia_pack_pending( $data, $i ) ) );
	}

	wp_send_json_success( array( 'html' => $html, 'next' => min( $total, $offset + $size ), 'total' => $total, 'done' => $offset + $size >= $total ) );
}

add_action( 'wp_ajax_topersia_pack_run', 'topersia_pack_ajax_run' );

function topersia_pack_ajax_run() {

	topersia_pack_ajax_guard();

	$token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
	$stored = topersia_pack_token_data( $token );

	if ( ! $stored ) {
		wp_send_json_error( array( 'message' => 'بارگذاری پیدا نشد — دوباره بارگذاری کن.' ) );
	}

	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$size   = isset( $_POST['size'] ) ? max( 1, min( 10, absint( $_POST['size'] ) ) ) : 3;

	/* شناسۀ اجرا یک بار، در نخستین دسته */
	if ( 0 === $offset || '' === $stored['run'] ) {
		$stored['run']    = 'imp-' . gmdate( 'Ymd-His' ) . '-' . strtolower( wp_generate_password( 4, false, false ) );
		$stored['counts'] = array( 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0 );
		set_transient( topersia_pack_token_key( $token ), $stored, 12 * HOUR_IN_SECONDS );
		update_option( TOPERSIA_PACK_LAST_RUN, array(
			'run'       => $stored['run'],
			'file'      => $stored['name'],
			'sha1'      => $stored['sha1'],
			'time'      => time(),
			'user'      => get_current_user_id(),
			'created'   => 0,
			'updated'   => 0,
			'unchanged' => 0,
			'skipped'   => 0,
			'undone'    => 0,
		), false );
	}

	$batch = topersia_pack_run_batch( $stored['data'], $offset, $size, $stored['run'] );
	$html  = '';

	foreach ( $batch['results'] as $plan ) {
		$stored['counts'][ $plan['result'] ]++;
		$html .= topersia_pack_render_plan( $plan, 'result' );
	}

	set_transient( topersia_pack_token_key( $token ), $stored, 12 * HOUR_IN_SECONDS );

	$last = get_option( TOPERSIA_PACK_LAST_RUN );

	if ( is_array( $last ) && $last['run'] === $stored['run'] ) {
		$last = array_merge( $last, $stored['counts'] );
		update_option( TOPERSIA_PACK_LAST_RUN, $last, false );
	}

	$summary = sprintf( '%d ساخت · %d به‌روزرسانی · %d بی‌تغییر · %d رد', $stored['counts']['created'], $stored['counts']['updated'], $stored['counts']['unchanged'], $stored['counts']['skipped'] );

	if ( $batch['done'] ) {
		topersia_log( 'درون‌ریزی بستۀ محتوا تمام شد', 'notice', 'import', array( 'run' => $stored['run'], 'file' => $stored['name'], 'counts' => $stored['counts'] ) );
	}

	wp_send_json_success( array( 'html' => $html, 'next' => $batch['next'], 'total' => $batch['total'], 'done' => $batch['done'], 'summary' => $summary, 'run' => $stored['run'] ) );
}

add_action( 'wp_ajax_topersia_pack_undo', 'topersia_pack_ajax_undo' );

function topersia_pack_ajax_undo() {

	topersia_pack_ajax_guard();

	$run  = isset( $_POST['run'] ) ? sanitize_text_field( wp_unslash( $_POST['run'] ) ) : '';
	$last = get_option( TOPERSIA_PACK_LAST_RUN );

	/* فقط آخرین درون‌ریزی — برگرداندن کهنه‌تر روی دادۀ بعدی می‌نشیند */
	if ( ! is_array( $last ) || $last['run'] !== $run || ! empty( $last['undone'] ) ) {
		wp_send_json_error( array( 'message' => 'فقط آخرین درون‌ریزیِ برنگشته برگرداندنی است.' ) );
	}

	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$size   = isset( $_POST['size'] ) ? max( 1, min( 50, absint( $_POST['size'] ) ) ) : 10;

	wp_send_json_success( topersia_pack_undo_batch( $run, $offset, $size ) );
}

add_action( 'admin_post_topersia_pack_export', 'topersia_pack_download' );

/** برون‌بری: قالب، JSON Schema یا پست — پروندۀ پیوست. */
function topersia_pack_download() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'دسترسی ندارید.' );
	}

	check_admin_referer( 'topersia_pack' );

	$what = isset( $_GET['what'] ) ? sanitize_key( $_GET['what'] ) : '';
	$type = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';
	$lang = isset( $_GET['lang'] ) ? sanitize_key( $_GET['lang'] ) : topersia_pack_default_language();

	if ( ! in_array( $lang, topersia_pack_languages(), true ) ) {
		$lang = topersia_pack_default_language();
	}

	if ( 'post' === $what ) {
		$ids  = array_filter( array_map( 'absint', preg_split( '/[\s,،]+/u', isset( $_GET['post'] ) ? sanitize_text_field( wp_unslash( $_GET['post'] ) ) : '' ) ) );
		$body = topersia_pack_export( $ids );
		if ( ! $body ) {
			wp_die( 'پستِ موجودیتی با این شناسه پیدا نشد.' );
		}
		$name = 'topersia-pack-' . $body['meta']['post_type'] . '-' . implode( '-', array_slice( $ids, 0, 5 ) ) . '.json';
	} else {
		if ( ! topersia_is_entity( $type ) || ! post_type_exists( $type ) ) {
			wp_die( 'نوع پست نامعتبر است.' );
		}
		$body = 'schema' === $what ? topersia_pack_json_schema( $type ) : topersia_pack_template( $type, $lang );
		$name = 'schema' === $what ? 'topersia-pack-' . $type . '.schema.json' : 'topersia-pack-' . $type . '-' . $lang . '.json';
	}

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $name . '"' );

	echo wp_json_encode( $body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}
