<?php
/**
 * ادعاهای بستۀ محتوا — topersia-core ۴٫۱۲٫۰ · SPRINT-4.4 فاز ۲ب · P5-D272 — روی
 * وردپرسِ واقعیِ محلی.
 *
 *   php tests/pack-local.php
 *
 * نگهبان: فقط topersia.local، محیط local، پایگاه topersia_local. هر دادۀ آزمون
 * (پست موقت، یال، ردیف لاگ، گزینۀ آخرین درون‌ریزی) همین‌جا ساخته و در پایان
 * پاک یا برگردانده می‌شود. پست واقعی فقط خوانده می‌شود — رفت‌وبرگشت #399
 * «بی‌تغییر» است و هیچ چیز نمی‌نویسد.
 *
 *   اسکیما      قالب از خودِ جت‌انجین: فیلدها، بیرون‌مانده‌ها، واژه‌نامه، واحد،
 *               کلاس WPML، کمینۀ CA، رابطه با جهت و کاردینالیتی
 *   JSON Schema از همان تعریف‌ها؛ برون‌بری واقعی در برابرش معتبر، جهش نه
 *   رفت‌وبرگشت  برون‌بری #399 ← درون‌ریزی: هیچ تغییر، هیچ نوشتن
 *   نامعتبر     JSON خراب، اسکلت ۱٫۲، schema و نوع و posts نادرست
 *   هوش مصنوعی  گزینۀ نادرست، نوع نادرست، کلید ناشناخته، رسانه، منبع بی نشانی،
 *               رابطۀ پیدانشده و چندپهلو، تکراری
 *   اجرا        پیش‌نویس با needs_verification، زبان، یال، ترجمه با
 *               translation_of و فیلد رونوشت از اصل؛ هرگز در خروجی عمومی
 *   برگرداندن   به‌روزرسانی (متا، Repeater، والدِ یکتا) و ساخت؛ لاگ مستقل از
 *               آستانۀ لاگ؛ هرس ردیف‌های آخرین درون‌ریزی را نگه می‌دارد
 *
 * هر ادعا جهش دارد. کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص
 */

require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

if ( ! function_exists( 'topersia_pack_schema' ) || ! function_exists( 'jet_engine' ) ) {
	fwrite( STDERR, "هستۀ ۴٫۱۲٫۰ یا جت‌انجین روی نسخۀ محلی نیست — اول همگام‌سازی\n" );
	exit( 2 );
}

/* مدیر آزمون — فقط حسابی که نشان _tp_test_account دارد، هرگز مدیر واقعی */
$admin = get_users( array( 'meta_key' => '_tp_test_account', 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( ! $admin ) {
	fwrite( STDERR, "مدیر آزمون نیست — scripts/tp-local-441.php test-admin\n" );
	exit( 2 );
}
wp_set_current_user( (int) $admin[0] );

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function t( $label, $ok, $note = '' ) {
	if ( $ok ) { $GLOBALS['pass'] ++; echo "  ✅ {$label}\n"; }
	else       { $GLOBALS['fail'] ++; echo "  ❌ {$label}" . ( '' !== $note ? "   ← {$note}" : '' ) . "\n"; }
}

function mut( $label, callable $claim, $real, $mutant, $note = '' ) {
	$ok  = (bool) $claim( $real );
	$bad = (bool) $claim( $mutant );
	t( $label, $ok && ! $bad, ( $ok ? '' : 'روی رفتار واقعی افتاد' ) . ( $bad ? ' · جهش را نگرفت' : '' ) . ( '' !== $note ? ' · ' . $note : '' ) );
}

function section( $s ) { echo "\n{$s}\n" . str_repeat( '─', 78 ) . "\n"; }

/* ── پاک‌سازی: هر چه ساخته شد، حتی اگر آزمون وسط راه بیفتد ── */
/* queue: کارهای صفی که پست‌های موقت می‌سازند (گالری تجمیعی ۲ج٫۵) — در پایان اجرا و پاک */
require_once __DIR__ . '/queue-sweep.php';
$GLOBALS['cleanup'] = array( 'posts' => array(), 'runs' => array(), 'log_ids' => array(), 'last_run' => get_option( TOPERSIA_PACK_LAST_RUN, null ), 'queue' => tp_queue_floor() );

register_shutdown_function( function () {
	global $wpdb;
	$c = $GLOBALS['cleanup'];
	foreach ( array_unique( $c['posts'] ) as $id ) {
		foreach ( array_keys( topersia_pack_relations( get_post_type( $id ) ?: 'tp_city' ) ) as $k ) {
			// یال‌ها با حذف پست پاک می‌شوند (cleanup_relation جت‌انجین)
		}
		wp_delete_post( $id, true );
	}
	tp_queue_sweep( $c['queue'] );
	foreach ( array_unique( $c['runs'] ) as $run ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . topersia_log_table() . " WHERE channel = 'import' AND context LIKE %s", '%' . $wpdb->esc_like( '"run":"' . $run . '"' ) . '%' ) );
	}
	foreach ( $c['log_ids'] as $lid ) {
		$wpdb->delete( topersia_log_table(), array( 'id' => (int) $lid ) );
	}
	if ( null === $c['last_run'] ) {
		delete_option( TOPERSIA_PACK_LAST_RUN );
	} else {
		update_option( TOPERSIA_PACK_LAST_RUN, $c['last_run'], false );
	}
} );

function tmp_post( $type, $title, $status = 'publish', $meta = array() ) {
	$id = wp_insert_post( array( 'post_type' => $type, 'post_status' => $status, 'post_title' => $title, 'post_content' => '', 'meta_input' => $meta ), true );
	if ( is_wp_error( $id ) ) {
		fwrite( STDERR, 'tmp_post: ' . $id->get_error_message() . "\n" );
		exit( 2 );
	}
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_' . $type, 'trid' => false, 'language_code' => 'fa' ) );
	$GLOBALS['cleanup']['posts'][] = (int) $id;
	return (int) $id;
}

function run_id( $tag ) {
	$run = 'imp-test-' . $tag . '-' . strtolower( wp_generate_password( 6, false, false ) );
	$GLOBALS['cleanup']['runs'][] = $run;
	return $run;
}

/** همۀ متای یک پست جز قفل ویرایش — برای «بی‌تغییر». */
function meta_arr( $id ) {
	clean_post_cache( $id );
	wp_cache_delete( $id, 'post_meta' );
	$all = get_post_meta( $id );
	unset( $all['_edit_lock'], $all['_edit_last'] );
	ksort( $all );
	$all['#post'] = array( get_post_field( 'post_title', $id ), get_post_field( 'post_name', $id ), get_post_status( $id ) );
	return $all;
}

function meta_hash( $id ) {
	return md5( serialize( meta_arr( $id ) ) );
}

/** کلیدهایی که میان دو عکس متا فرق دارند — برای یادداشت افتادن. */
function meta_diff( $a, $b ) {
	$keys = array();
	foreach ( array_unique( array_merge( array_keys( $a ), array_keys( $b ) ) ) as $k ) {
		if ( ( $a[ $k ] ?? null ) !== ( $b[ $k ] ?? null ) ) {
			$keys[] = $k . ': ' . topersia_str_cut( wp_json_encode( $a[ $k ] ?? null, JSON_UNESCAPED_UNICODE ), 70 ) . ' ≠ ' . topersia_str_cut( wp_json_encode( $b[ $k ] ?? null, JSON_UNESCAPED_UNICODE ), 70 );
		}
	}
	return implode( ' · ', $keys );
}

/** همۀ یال‌های یک پست در رابطه‌های نوعش. */
function rel_hash( $id ) {
	$out = array();
	foreach ( topersia_pack_relations( get_post_type( $id ) ) as $key => $rel ) {
		$o = topersia_relation( $rel['id'] );
		if ( $o ) {
			$ids = 'parents' === $rel['direction'] ? $o->get_parents( $id, 'ids' ) : $o->get_children( $id, 'ids' );
			$ids = array_map( 'intval', (array) $ids );
			sort( $ids );
			$out[ $key ] = $ids;
		}
	}
	return md5( wp_json_encode( $out ) );
}

function import_rows( $run ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . topersia_log_table() . " WHERE channel = 'import' AND context LIKE %s", '%' . $wpdb->esc_like( '"run":"' . $run . '"' ) . '%' ) );
}

function local_get( $path ) {
	$res = wp_remote_get( TP_BASE_URL . $path, array( 'timeout' => 240, 'redirection' => 0 ) );
	return is_wp_error( $res ) ? array( 'code' => 0, 'body' => '' ) : array( 'code' => (int) wp_remote_retrieve_response_code( $res ), 'body' => (string) wp_remote_retrieve_body( $res ) );
}

/**
 * اعتبارسنج کوچک JSON Schema — فقط کلیدواژه‌هایی که topersia_pack_json_schema
 * می‌سازد. شیء JSON با json_decode( …, false ) شیء می‌ماند تا از فهرست جدا شود.
 */
function jsv( $s, $v, $path, &$err ) {
	if ( isset( $s['const'] ) && $v !== $s['const'] ) { $err[] = "$path const"; return; }
	if ( array_key_exists( 'enum', $s ) && ! in_array( $v, $s['enum'], true ) ) { $err[] = "$path enum «" . ( is_scalar( $v ) ? $v : gettype( $v ) ) . '»'; return; }
	if ( isset( $s['type'] ) ) {
		$types = (array) $s['type'];
		$is    = array(
			'string'  => is_string( $v ),
			'number'  => is_int( $v ) || is_float( $v ),
			'integer' => is_int( $v ),
			'boolean' => is_bool( $v ),
			'null'    => null === $v,
			'array'   => is_array( $v ),
			'object'  => is_object( $v ),
		);
		$hit = false;
		foreach ( $types as $ty ) { if ( ! empty( $is[ $ty ] ) ) { $hit = true; } }
		if ( ! $hit ) { $err[] = "$path type " . implode( '|', $types ); return; }
	}
	if ( is_string( $v ) ) {
		if ( isset( $s['pattern'] ) && ! preg_match( '/' . str_replace( '/', '\/', $s['pattern'] ) . '/u', $v ) ) { $err[] = "$path pattern"; }
		if ( isset( $s['minLength'] ) && mb_strlen( $v ) < $s['minLength'] ) { $err[] = "$path minLength"; }
		if ( isset( $s['maxLength'] ) && mb_strlen( $v ) > $s['maxLength'] ) { $err[] = "$path maxLength"; }
	}
	if ( is_int( $v ) || is_float( $v ) ) {
		if ( isset( $s['minimum'] ) && $v < $s['minimum'] ) { $err[] = "$path minimum"; }
		if ( isset( $s['maximum'] ) && $v > $s['maximum'] ) { $err[] = "$path maximum"; }
	}
	if ( is_array( $v ) ) {
		if ( isset( $s['maxItems'] ) && count( $v ) > $s['maxItems'] ) { $err[] = "$path maxItems"; }
		if ( isset( $s['minItems'] ) && count( $v ) < $s['minItems'] ) { $err[] = "$path minItems"; }
		if ( ! empty( $s['uniqueItems'] ) && count( $v ) !== count( array_unique( array_map( 'serialize', $v ) ) ) ) { $err[] = "$path uniqueItems"; }
		if ( isset( $s['items'] ) ) { foreach ( $v as $i => $item ) { jsv( $s['items'], $item, "$path/$i", $err ); } }
	}
	if ( is_object( $v ) ) {
		$props = get_object_vars( $v );
		foreach ( (array) ( isset( $s['required'] ) ? $s['required'] : array() ) as $r ) {
			if ( ! array_key_exists( $r, $props ) ) { $err[] = "$path required $r"; }
		}
		$known = isset( $s['properties'] ) ? (array) $s['properties'] : array();
		foreach ( $props as $k => $pv ) {
			if ( isset( $known[ $k ] ) ) {
				jsv( $known[ $k ], $pv, "$path/$k", $err );
			} elseif ( isset( $s['additionalProperties'] ) && false === $s['additionalProperties'] ) {
				$err[] = "$path additional $k";
			}
		}
	}
}

function jsv_errors( $schema, $json ) {
	$err = array();
	jsv( json_decode( wp_json_encode( $schema ), true ), json_decode( $json, false ), '', $err );
	return $err;
}

/** رمزگشایی مستقل یک واژه‌نامۀ جت‌انجین — نه از کد بسته. */
function raw_glossary( $gid ) {
	foreach ( jet_engine()->glossaries->data->get_items() as $item ) {
		$item = (array) $item;
		if ( (string) $item['id'] !== (string) $gid ) { continue; }
		$f = maybe_unserialize( $item['meta_fields'] );
		$f = is_string( $f ) ? json_decode( $f, true ) : $f;
		return array_map( function ( $r ) { return (string) $r['value']; }, (array) $f );
	}
	return array();
}

echo 'topersia-core ' . TOPERSIA_CORE_VERSION . " — بستۀ محتوا روی topersia.local\n";

/* ══ اسکیمای زنده ════════════════════════════════════════════════════════ */
section( 'اسکیمای زنده — از خودِ جت‌انجین  P5-D272 ۲ب٫۱' );

/* انتظار مستقل: هر فیلد متاباکس‌هایی که tp_city در allowed_post_type آن‌هاست */
$expect_all = array();
foreach ( jet_engine()->meta_boxes->get_items() as $box ) {
	$args = is_string( $box['args'] ) ? json_decode( $box['args'], true ) : $box['args'];
	if ( ! in_array( 'tp_city', (array) ( $args['allowed_post_type'] ?? array() ), true ) ) { continue; }
	$list = is_string( $box['meta_fields'] ) ? json_decode( $box['meta_fields'], true ) : $box['meta_fields'];
	foreach ( (array) $list as $f ) {
		if ( ! empty( $f['name'] ) && 'field' === ( $f['object_type'] ?? 'field' ) ) { $expect_all[ $f['name'] ] = $f; }
	}
}
$s_city = topersia_pack_schema( 'tp_city' );
$got    = array_merge( array_keys( $s_city['fields'] ), array_keys( $s_city['excluded'] ) );
sort( $got );
$want = array_keys( $expect_all );
sort( $want );

mut( 'tp_city: فیلدها + بیرون‌مانده‌ها دقیقاً همۀ فیلدهای متاباکس‌های زنده — هیچ فیلدی بی‌صدا نمی‌افتد و هیچ فیلد دست‌نویسی نیست',
	function ( $g ) use ( $want ) { return $g === $want; },
	$got, array_values( array_diff( $got, array( 'transport_access' ) ) ), count( $want ) . ' فیلد' );

$ex = $s_city['excluded'];
mut( 'بیرون از بسته: رسانه (hero_image · featured_image_gallery)، Repeater رسانه‌دار (audio_narration)، video_sample و سه سامانه‌ای — «هیچ نشانی تصویر»',
	function ( $e ) { return array( 'audio_narration', 'content_status', 'entity_uid', 'featured_image_gallery', 'hero_image', 'last_verified_date', 'video_sample' ) === ( function ( $k ) { sort( $k ); return $k; } )( array_keys( $e ) ); },
	$ex, array_merge( $ex, array( 'overview_text' => 'x' ) ) );

$bs  = $s_city['fields']['best_season'];
$raw = raw_glossary( $bs['glossary'] ?? 0 );
mut( 'واژه‌نامه: گزینه‌های best_season همان کلیدهای واژه‌نامۀ زندۀ ' . ( $bs['glossary'] ?? '?' ) . ' — با برچسب',
	function ( $o ) use ( $raw ) { return $raw && array_map( function ( $x ) { return $x['value']; }, $o ) === $raw && '' !== $o[0]['label']; },
	$bs['options'], array_slice( $bs['options'], 1 ) );

mut( 'واحد از برچسب خودِ جت‌انجین: area_km2 «کیلومتر مربع» · elevation_m «متر»؛ متن واحد ندارد',
	function ( $f ) { return 'کیلومتر مربع' === ( $f['area_km2']['unit'] ?? '' ) && 'متر' === ( $f['elevation_m']['unit'] ?? '' ) && ! isset( $f['name_fa']['unit'] ); },
	$s_city['fields'], array_merge( $s_city['fields'], array( 'area_km2' => array_diff_key( $s_city['fields']['area_km2'], array( 'unit' => 1 ) ) ) ) );

$cls = topersia_pack_wpml_classes();
mut( 'کلاس WPML از تنظیمات خودِ WPML: name_en و population «copy»، overview_text «translate»',
	function ( $f ) { return 'copy' === $f['name_en']['wpml'] && 'copy' === $f['population']['wpml'] && 'translate' === $f['overview_text']['wpml']; },
	$s_city['fields'], array_merge( $s_city['fields'], array( 'overview_text' => array_merge( $s_city['fields']['overview_text'], array( 'wpml' => 'copy' ) ) ) ) );

mut( 'کمینۀ CA: overview_text شهر ۸۰۰ واژه (CA-G G5)، استان ۶۰۰ (G3)، روستا ۲۵۰ (G7)؛ currency_note کشور بیشینۀ ۵۰ واژه (CA-G-AMENDMENT-003)',
	function ( $r ) { return array( 800, 'CA-G G5', 600, 250, 50 ) === $r; },
	array(
		$s_city['fields']['overview_text']['min_words'] ?? 0,
		$s_city['fields']['overview_text']['length_source'] ?? '',
		topersia_pack_schema( 'tp_province' )['fields']['overview_text']['min_words'] ?? 0,
		topersia_pack_schema( 'tp_village' )['fields']['overview_text']['min_words'] ?? 0,
		topersia_pack_schema( 'tp_country' )['fields']['currency_note']['max_words'] ?? 0,
	),
	array( 600, 'CA-G G5', 600, 250, 50 ) );

/* رابطه‌ها — انتظار از شیء رابطۀ زنده */
$live = jet_engine()->relations->get_active_relations();
$r73  = $s_city['relations']['73'] ?? array();
$r84  = $s_city['relations']['84'] ?? array();
$r96  = $s_city['relations']['96'] ?? array();
$ok73 = isset( $live[73] ) && 'posts::tp_county' === $live[73]->get_args( 'parent_object' ) && 'posts::tp_city' === $live[73]->get_args( 'child_object' );
mut( 'رابطه: ۷۳ county_city از دید شهر parents · tp_county · one_to_many · یک والد؛ ۸۴ children · tp_monument · بی سقف؛ ۹۶ one_to_one · یک',
	function ( $r ) use ( $ok73 ) { return $ok73 && 'parents' === $r[0]['direction'] && 'tp_county' === $r[0]['target'] && 'one_to_many' === $r[0]['cardinality'] && 1 === $r[0]['max'] && 'children' === $r[1]['direction'] && 'tp_monument' === $r[1]['target'] && 0 === $r[1]['max'] && 1 === $r[2]['max'] && 'one_to_one' === $r[2]['cardinality']; },
	array( $r73, $r84, $r96 ), array( array_merge( $r73, array( 'max' => 0 ) ), $r84, $r96 ) );

$prov_rel = topersia_pack_schema( 'tp_province' )['relations'];
mut( 'رابطۀ خودارجاع (۱۰۲ هم‌مرز استان) دو کلید دارد: «102:children» و «102:parents»',
	function ( $r ) { return isset( $r['102:children'], $r['102:parents'] ) && ! isset( $r['102'] ) && 'tp_province' === $r['102:parents']['target']; },
	$prov_rel, array_diff_key( $prov_rel, array( '102:parents' => 1 ) ) );

$tpl = topersia_pack_template( 'tp_city', 'en' );
mut( 'قالب: meta.schema topersia-geo-import/2.0، بلوک دستور به زبان محتوا (en)، یک پست خالی با همۀ کلیدها و همۀ رابطه‌ها',
	function ( $t ) use ( $s_city ) {
		return TOPERSIA_PACK_SCHEMA === $t['meta']['schema'] && 'en' === $t['meta']['language'] && 8 === count( $t['instructions'] )
			&& false !== strpos( $t['instructions'][0], 'source_references' ) && ! preg_match( '/[\x{0600}-\x{06FF}]/u', implode( ' ', $t['instructions'] ) )
			&& array_keys( get_object_vars( $t['posts'][0]['fields'] ) ) === array_keys( $s_city['fields'] )
			&& array_keys( get_object_vars( $t['posts'][0]['relations'] ) ) === array_keys( $s_city['relations'] );
	},
	$tpl, array_merge( $tpl, array( 'instructions' => topersia_pack_instructions( 'fa', 'tp_city' ) ) ) );

mut( 'دستور فارسی کمینۀ همان نوع را می‌گوید: «overview_text دست‌کم ۸۰۰ واژه» برای شهر',
	function ( $i ) { return false !== strpos( implode( ' ', $i ), 'دست‌کم 800 واژه' ); },
	topersia_pack_instructions( 'fa', 'tp_city' ), topersia_pack_instructions( 'fa', 'tp_province' ) );

/* ══ JSON Schema ═══════════════════════════════════════════════════════════ */
section( 'JSON Schema — از همان تعریف‌ها' );

$js       = topersia_pack_json_schema( 'tp_city' );
$js_post  = $js['properties']['posts']['items']['properties'];
$js_bs    = $js_post['fields']['properties']->best_season;
$req_live = array_keys( array_filter( $expect_all, function ( $f ) use ( $ex ) { return ! empty( $f['is_required'] ) && ! isset( $ex[ $f['name'] ] ); } ) );
sort( $req_live );
$req_js = $js_post['fields']['required'];
sort( $req_js );

mut( 'JSON Schema: best_season enum = کلیدهای واژه‌نامه + خالی؛ required = فیلدهای الزامی زنده (بی رسانه)؛ رابطۀ ۷۳ maxItems ۱؛ draft 2020-12',
	function ( $x ) use ( $raw, $req_live ) {
		return 'https://json-schema.org/draft/2020-12/schema' === $x[3] && array_merge( $raw, array( '', null ) ) === $x[0]['enum'] && $req_live === $x[1] && 1 === $x[2]['maxItems'];
	},
	array( $js_bs, $req_js, $js_post['relations']['properties']->{'73'}, $js['$schema'] ),
	array( $js_bs, array_slice( $req_js, 1 ), $js_post['relations']['properties']->{'73'}, $js['$schema'] ) );

$exp399 = topersia_pack_export( array( 399 ) );
$json   = wp_json_encode( $exp399 );
$bad    = json_decode( $json, true );
$bad['posts'][0]['fields']['best_season'] = 'late-spring';
$bad['posts'][0]['fields']['population']  = 'many';

mut( 'برون‌بری واقعی #399 در برابر JSON Schema معتبر است؛ جهش (گزینۀ نادرست، عدد متنی) دو خطا',
	function ( $j ) use ( $js ) { return array() === jsv_errors( $js, $j ); },
	$json, wp_json_encode( $bad ), implode( ' · ', array_slice( jsv_errors( $js, $json ), 0, 4 ) ) );

$tjson = wp_json_encode( topersia_pack_template( 'tp_city', 'fa' ) );
t( 'قالب خالی هم در برابر JSON Schema خودش معتبر است — «خالی بگذار» مجاز است', array() === jsv_errors( $js, $tjson ), implode( ' · ', array_slice( jsv_errors( $js, $tjson ), 0, 4 ) ) );

/* ══ رفت‌وبرگشت ════════════════════════════════════════════════════════════ */
section( 'رفت‌وبرگشت — برون‌بری ← درون‌ریزی بی تغییر' );

$before_m = meta_hash( 399 );
$before_r = rel_hash( 399 );
$data     = topersia_pack_parse( $json );
$plan     = topersia_pack_plan_post( $data, 0 );
$counts   = topersia_pack_plan_counts( $plan );

$bad_data = topersia_pack_parse( wp_json_encode( ( function ( $e ) { $e['posts'][0]['fields']->population = ( $e['posts'][0]['fields']->population ?? 0 ) + 1; return $e; } )( $exp399 ) ) );
$bad_plan = topersia_pack_plan_post( $bad_data, 0 );

mut( 'آزمایشی #399: به‌روزرسانی همان پست، هر فیلد «بی‌تغییر» یا «خالی» و هر یال «بی‌تغییر»؛ جهش (جمعیت +۱) دقیقاً یک «درست»',
	function ( $p ) { $c = topersia_pack_plan_counts( $p ); return 'update' === $p['action'] && 399 === $p['target'] && ! array_diff( array_keys( $c ), array( 'same', 'empty' ) ) && 'same' === $p['title']['status']; },
	$plan, $bad_plan, wp_json_encode( $counts ) );

t( 'جهش رفت‌وبرگشت: فقط population «درست» است و مقدار ذخیره‌اش ۱۹۶۱۲۶۱',
	1 === ( topersia_pack_plan_counts( $bad_plan )['ok'] ?? 0 ) && 'ok' === $bad_plan['fields']['population']['status'] && '1961261' === $bad_plan['fields']['population']['stored'] );

$run0   = run_id( 'rt' );
$result = topersia_pack_apply( $data, 0, $run0 );
mut( 'اجرای رفت‌وبرگشت: «بی‌تغییر» — همۀ متا و عنوان و نامک و وضعیت و همۀ یال‌های #399 بایت‌به‌بایت همان؛ هیچ ردیف برگشت',
	function ( $r ) use ( $before_m, $before_r, $run0 ) { return 'unchanged' === $r[0] && $before_m === $r[1] && $before_r === $r[2] && 0 === import_rows( $run0 ); },
	array( $result['result'], meta_hash( 399 ), rel_hash( 399 ) ), array( 'updated', meta_hash( 399 ), rel_hash( 399 ) ) );

/* ══ پروندۀ نامعتبر ═══════════════════════════════════════════════════════ */
section( 'پروندۀ نامعتبر — رد با پیام روشن' );

$invalid = array(
	'pack_json'   => '{"meta": {"schema": "topersia-geo-import/2.0"',
	'pack_v1'     => wp_json_encode( array( 'meta' => array( 'schema' => 'topersia-geo-import/1.2' ), 'country' => array() ) ),
	'pack_schema' => wp_json_encode( array( 'meta' => array( 'schema' => 'other/1.0', 'post_type' => 'tp_city' ), 'posts' => array( array() ) ) ),
	'pack_type'   => wp_json_encode( array( 'meta' => array( 'schema' => TOPERSIA_PACK_SCHEMA, 'post_type' => 'post' ), 'posts' => array( array() ) ) ),
	'pack_posts'  => wp_json_encode( array( 'meta' => array( 'schema' => TOPERSIA_PACK_SCHEMA, 'post_type' => 'tp_city' ), 'posts' => array( 'a' => array() ) ) ),
	'pack_empty'  => '   ',
);
$codes = array();
foreach ( $invalid as $want_code => $raw_file ) {
	$r       = topersia_pack_parse( $raw_file );
	$codes[] = is_wp_error( $r ) ? $r->get_error_code() : 'accepted';
}
mut( 'رد: JSON خراب · اسکلت ۱٫۲ (با نشانی زبانۀ خودش) · schema دیگر · نوع غیرموجودیت · posts غیرفهرست · پروندۀ خالی — هر کدام کد خودش؛ پروندۀ درست پذیرفته',
	function ( $c ) use ( $invalid ) { return array_keys( $invalid ) === $c[0] && is_array( $c[1] ); },
	array( $codes, topersia_pack_parse( $json ) ), array( array_slice( $codes, 1 ), topersia_pack_parse( $json ) ), implode( ' · ', $codes ) );

/* ══ پروندۀ هوش مصنوعیِ شبیه‌سازی‌شده ═══════════════════════════════════════ */
section( 'پروندۀ هوش مصنوعیِ شبیه‌سازی‌شده — هر خطا با وضعیت خودش' );

$county_a = tmp_post( 'tp_county', 'شهرستان آزمون ۲ب الف' );
$county_b = tmp_post( 'tp_county', 'شهرستان آزمون ۲ب ب' );
$mon1     = tmp_post( 'tp_monument', 'بنای هم‌نام آزمون ۲ب', 'draft' );
$mon2     = tmp_post( 'tp_monument', 'بنای هم‌نام آزمون ۲ب', 'draft' );

$ai_post = function ( $over = array() ) {
	$f = array(
		'name_fa'           => 'شهر آزمون ۲ب',
		'name_en'           => 'Test City Twob',
		'name_ar'           => 'مدينة اختبار',
		'short_summary'     => 'شهری ساختگی برای آزمون درون‌ریزی.',
		'overview_text'     => '<p>' . str_repeat( 'واژه ', 820 ) . '</p>',
		'population'        => 12345,
		'area_km2'          => 12.5,
		'best_season'       => 'spring',
		'railway_station'   => true,
		'meta_title'        => 'شهر آزمون ۲ب',
		'meta_description'  => 'توضیح آزمون',
		'transport_access'  => 'جادۀ آزمون',
		'geo_coordinates'   => '32.1, 51.2',
		'source_references' => array( array( 'source_name' => 'Wikipedia', 'source_url' => 'https://en.wikipedia.org/wiki/Isfahan', 'source_access_date' => '2026-09-29', 'source_row_status' => '' ) ),
	);
	return array(
		'entity_uid' => '', 'slug' => 'test-city-twob', 'language' => 'fa', 'translation_of' => '', 'title' => '',
		'fields'     => array_merge( $f, isset( $over['fields'] ) ? $over['fields'] : array() ),
		'relations'  => isset( $over['relations'] ) ? $over['relations'] : array( '73' => array( array( 'name' => 'شهرستان آزمون ۲ب الف', 'type' => 'tp_county' ) ) ),
	);
};

$file = function ( $posts ) {
	return wp_json_encode( array( 'meta' => array( 'schema' => TOPERSIA_PACK_SCHEMA, 'post_type' => 'tp_city', 'language' => 'fa' ), 'posts' => $posts ) );
};

$messy = $ai_post( array(
	'fields'    => array(
		'best_season'       => 'late-spring',
		'population'        => 'many',
		'foo'               => 'bar',
		'hero_image'        => 'https://example.org/a.jpg',
		'area_km2'          => '',
		'overview_text'     => '<p>کوتاه</p>',
		'source_references' => array( array( 'source_name' => 'بی نشانی', 'source_url' => '' ) ),
		'railway_station'   => 'yes',
	),
	'relations' => array(
		'73'  => array( array( 'name' => 'شهرستانی که نیست', 'type' => 'tp_county' ) ),
		'84'  => array( array( 'name' => 'بنای هم‌نام آزمون ۲ب', 'type' => 'tp_monument' ), array( 'name' => 'x', 'type' => 'tp_city' ) ),
		'999' => array( array( 'name' => 'x', 'type' => 'tp_city' ) ),
	),
) );

$d_messy = topersia_pack_parse( $file( array( $messy, $messy ) ) );
$p_messy = topersia_pack_plan_post( $d_messy, 0 );
$p_dup   = topersia_pack_plan_post( $d_messy, 1 );
$st      = function ( $p, $k ) { return $p['fields'][ $k ]['status'] ?? '—'; };
$rs      = function ( $p, $k, $n = 0 ) { return $p['relations'][ $k ][ $n ]['status'] ?? '—'; };

$d_clean = topersia_pack_parse( $file( array( $ai_post() ) ) );
$p_clean = topersia_pack_plan_post( $d_clean, 0 );

$ai_claim = function ( $p ) use ( $st, $rs ) {
	return 'create' === $p['action']
		&& 'option' === $st( $p, 'best_season' ) && false !== strpos( $p['fields']['best_season']['note'], 'late-spring' )
		&& 'type' === $st( $p, 'population' ) && 'type' === $st( $p, 'railway_station' )
		&& 'unknown' === $st( $p, 'foo' ) && 'unknown' === $st( $p, 'hero_image' ) && false !== strpos( $p['fields']['hero_image']['note'], 'رسانه' )
		&& 'required' === $st( $p, 'area_km2' ) && 'short' === $st( $p, 'overview_text' ) && 'type' === $st( $p, 'source_references' )
		&& 'rel_missing' === $rs( $p, '73' ) && 'rel_ambiguous' === $rs( $p, '84' ) && 'type' === $rs( $p, '84', 1 ) && 'unknown' === $rs( $p, '999' );
};
mut( 'آزمایشی: گزینۀ نادرست · عدد متنی · سوییچ «yes» · کلید ناشناخته · نشانی تصویر (بیرون از بسته) · الزامی خالی · کوتاه‌تر از ۸۰۰ · منبع بی نشانی · رابطۀ پیدانشده · چندپهلو (دو بنای هم‌نام) · نوع مقصد نادرست · رابطۀ تعریف‌نشده؛ جهش — پروندۀ پاکیزه — هیچ‌کدام',
	$ai_claim, $p_messy, $p_clean, wp_json_encode( topersia_pack_plan_counts( $p_messy ) ) );

mut( 'تکراری درون پرونده: پست دوم همان موجودیت «تکراری» و رد؛ نخستین نه',
	function ( $p ) { return 'skip' === $p[1]['action'] && 'duplicate' === $p[1]['problem'] && 'create' === $p[0]['action']; },
	array( $p_messy, $p_dup ), array( $p_messy, $p_messy ) );

$isf      = $ai_post( array( 'fields' => array( 'name_fa' => 'اصفهان' ) ) );
$isf['slug'] = '';
$p_isf    = topersia_pack_plan_post( topersia_pack_parse( $file( array( $isf ) ) ), 0 );
$isf2     = $isf;
$isf2['slug'] = 'isfahan-new-test';
$p_isf2   = topersia_pack_plan_post( topersia_pack_parse( $file( array( $isf2 ) ) ), 0 );
mut( 'تکراری در سایت: پست تازۀ «اصفهان» بی نامک رد با شناسۀ پست موجود؛ با نامک تازه (قصد صریح) ساخت',
	function ( $p ) { return 'duplicate' === $p['problem'] && false !== strpos( implode( ' ', $p['notes'] ), '#399' ); },
	$p_isf, $p_isf2 );

/* ══ اجرا ══════════════════════════════════════════════════════════════════ */
section( 'اجرا — پیش‌نویس، زبان، یال، ترجمه' );

$fa_post = $ai_post();
$en_post = array(
	'entity_uid' => '', 'slug' => '', 'language' => 'en', 'translation_of' => 'test-city-twob', 'title' => 'Test City Twob',
	'fields'     => array( 'name_en' => 'WRONG NAME', 'population' => 99, 'short_summary' => 'A made-up city for the import test.', 'overview_text' => '<p>' . str_repeat( 'word ', 810 ) . '</p>' ),
	'relations'  => array(),
);
$d_run  = topersia_pack_parse( $file( array( $fa_post, $en_post ) ) );
$p_en   = topersia_pack_plan_post( $d_run, 1, topersia_pack_pending( $d_run, 1 ) );
t( 'آزمایشی ترجمه: اصل در همین پرونده ساخته می‌شود؛ name_en و population (رونوشت WPML) «از اصل»، نه از پرونده',
	'create' === $p_en['action'] && 'original' === $p_en['fields']['name_en']['status'] && 'original' === $p_en['fields']['population']['status'] && 'ok' === $p_en['fields']['short_summary']['status'],
	wp_json_encode( topersia_pack_plan_counts( $p_en ) ) . ' ' . implode( ' · ', $p_en['notes'] ) );

$run1  = run_id( 'create' );
$batch = topersia_pack_run_batch( $d_run, 0, 1, $run1 );
$b2    = topersia_pack_run_batch( $d_run, 1, 1, $run1 );
$fa_id = (int) $batch['results'][0]['target'];
$en_id = (int) $b2['results'][0]['target'];
$GLOBALS['cleanup']['posts'][] = $fa_id;
$GLOBALS['cleanup']['posts'][] = $en_id;

t( 'دسته‌دسته: دو فراخوان یک‌پستی — «بعدی» ۱ سپس ۲ از ۲، done فقط در پایان',
	1 === $batch['next'] && ! $batch['done'] && 2 === $b2['next'] && $b2['done'] && 2 === $b2['total'] );

$fa_ok = function ( $id ) {
	return $id && 'draft' === get_post_status( $id ) && 'needs_verification' === get_post_meta( $id, 'content_status', true )
		&& 'fa' === topersia_pack_post_language( $id ) && '12345' === get_post_meta( $id, 'population', true )
		&& 'test-city-twob' === get_post_field( 'post_name', $id ) && '' !== (string) get_post_meta( $id, 'entity_uid', true );
};
mut( 'ساخت: پیش‌نویس، content_status needs_verification، زبان fa، متا از پرونده، نامک پرونده، entity_uid از بخش ۳',
	$fa_ok, $fa_id, 399 );

$county_now = array_map( 'intval', (array) topersia_relation( 73 )->get_parents( $fa_id, 'ids' ) );
mut( 'یال با API جت‌انجین: شهر تازه فرزند «شهرستان آزمون ۲ب الف» در رابطۀ ۷۳',
	function ( $p ) use ( $county_a ) { return array( $county_a ) === $p; }, $county_now, array() );

$trid_fa = apply_filters( 'wpml_element_trid', null, $fa_id, 'post_tp_city' );
$trid_en = apply_filters( 'wpml_element_trid', null, $en_id, 'post_tp_city' );
mut( 'ترجمه: پست en با trid اصل، پیش‌نویس، needs_verification؛ name_en و population از اصل (Test City Twob · 12345)، نه «WRONG NAME» و 99؛ entity_uid اصل',
	function ( $id ) use ( $trid_fa, $fa_id ) {
		return $id && 'en' === topersia_pack_post_language( $id ) && $trid_fa && (int) $trid_fa === (int) apply_filters( 'wpml_element_trid', null, $id, 'post_tp_city' )
			&& 'draft' === get_post_status( $id ) && 'needs_verification' === get_post_meta( $id, 'content_status', true )
			&& 'Test City Twob' === get_post_meta( $id, 'name_en', true ) && '12345' === get_post_meta( $id, 'population', true )
			&& get_post_meta( $fa_id, 'entity_uid', true ) === get_post_meta( $id, 'entity_uid', true );
	},
	$en_id, $fa_id );

/* هرگز در خروجی عمومی — مهمان */
wp_set_current_user( 0 );
$own   = local_get( '/?p=' . $fa_id );
$rest  = local_get( '/wp-json/wp/v2/tp_city?search=' . rawurlencode( 'آزمون ۲ب' ) . '&status=any' );
$page  = local_get( '/province/isfahan/' );
wp_set_current_user( (int) $admin[0] );
t( 'پیش‌نویس در خروجی عمومی نیست: نشانی خودش برای مهمان ۴۰۴، جست‌وجوی REST مهمان خالی، صفحۀ استان اصفهان بی نامش  P5-D247',
	404 === $own['code'] && false === strpos( $rest['body'], 'آزمون ۲ب' ) && false === strpos( $page['body'], 'شهر آزمون ۲ب' ) && 200 === $page['code'],
	$own['code'] . ' · ' . topersia_str_cut( $rest['body'], 80 ) );

/* جهش همان سنجش: منتشرش کن — نشانی خودش باید دیده شود */
wp_update_post( array( 'ID' => $fa_id, 'post_status' => 'publish' ) );
wp_set_current_user( 0 );
$own_pub = local_get( '/?p=' . $fa_id );
wp_set_current_user( (int) $admin[0] );
wp_update_post( array( 'ID' => $fa_id, 'post_status' => 'draft' ) );
t( 'جهش همان سنجش: منتشرشده، نشانی خودش برای مهمان ۲۰۰ یا ۳۰۱ به نشانی شهر — سنجش کور نیست',
	in_array( $own_pub['code'], array( 200, 301 ), true ), (string) $own_pub['code'] );

/* ══ برگرداندن ═════════════════════════════════════════════════════════════ */
section( 'برگرداندن — آخرین درون‌ریزی' );

$before_upd_a = meta_arr( $fa_id );
$before_upd_m = md5( serialize( $before_upd_a ) );
$before_upd_r = rel_hash( $fa_id );
$exp_fa       = topersia_pack_export( array( $fa_id ) );
$exp_fa['posts'][0]['fields']->population        = 55555;
$exp_fa['posts'][0]['fields']->source_references = array( array( 'source_name' => 'Britannica', 'source_url' => 'https://www.britannica.com/place/Iran', 'source_access_date' => '2026-09-29', 'source_row_status' => '' ) );
$exp_fa['posts'][0]['relations']->{'73'}         = array( array( 'name' => 'شهرستان آزمون ۲ب ب', 'type' => 'tp_county' ) );
$d_upd = topersia_pack_parse( wp_json_encode( $exp_fa ) );
$run2  = run_id( 'update' );

global $wpdb;
$filtered_info = topersia_log( 'آزمون: رویداد info از topersia_log', 'info', 'import', array( 'run' => $run2 ) );
$r2    = topersia_pack_apply( $d_upd, 0, $run2 );
update_option( TOPERSIA_PACK_LAST_RUN, array( 'run' => $run2, 'file' => 'test', 'time' => time(), 'user' => (int) $admin[0], 'created' => 0, 'updated' => 1, 'unchanged' => 0, 'skipped' => 0, 'undone' => 0 ), false );

$after_county = array_map( 'intval', (array) topersia_relation( 73 )->get_parents( $fa_id, 'ids' ) );
t( 'به‌روزرسانی: population ۵۵۵۵۵، منبع Britannica، والدِ یکتای ۷۳ از «الف» به «ب» (جت‌انجین والد پیشین را برداشت)',
	'updated' === $r2['result'] && '55555' === get_post_meta( $fa_id, 'population', true ) && array( $county_b ) === $after_county
	&& false !== strpos( wp_json_encode( get_post_meta( $fa_id, 'source_references', true ) ), 'britannica' ) );

mut( 'ردیف برگشت مستقل از تنظیم لاگ: آستانۀ «' . topersia_setting( 'log_level' ) . '» رویداد info را از topersia_log() نمی‌نویسد، ولی ردیف برگشت نوشته شد',
	function ( $x ) { return null === $x[0] && $x[1] >= 1; },
	array( $filtered_info, import_rows( $run2 ) ), array( $filtered_info, 0 ), 'ردیف: ' . import_rows( $run2 ) );

/* هرس: ردیف‌های آخرین درون‌ریزی می‌مانند، ردیف کهنۀ دیگر نه */
$wpdb->insert( topersia_log_table(), array( 'logged_at' => gmdate( 'Y-m-d H:i:s', time() - 400 * DAY_IN_SECONDS ), 'level' => 'warning', 'channel' => 'test', 'message' => 'ردیف کهنۀ آزمون ۲ب', 'context' => '', 'request_id' => '', 'user_id' => 0, 'url' => '', 'source' => 'pack-local' ) );
$old_id = (int) $wpdb->insert_id;
$GLOBALS['cleanup']['log_ids'][] = $old_id;
$wpdb->query( $wpdb->prepare( 'UPDATE ' . topersia_log_table() . " SET logged_at = %s WHERE channel = 'import' AND context LIKE %s", gmdate( 'Y-m-d H:i:s', time() - 400 * DAY_IN_SECONDS ), '%' . $wpdb->esc_like( '"run":"' . $run2 . '"' ) . '%' ) );
$rows_before = import_rows( $run2 );
topersia_log_prune();
$old_left = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . topersia_log_table() . ' WHERE id = %d', $old_id ) );
t( 'هرس (نگه‌داری ' . topersia_setting( 'log_retain_days' ) . ' روز): ردیف‌های ۴۰۰روزۀ آخرین درون‌ریزی ماندند، ردیف ۴۰۰روزۀ دیگر پاک شد',
	$rows_before >= 1 && import_rows( $run2 ) === $rows_before && 0 === $old_left, $rows_before . ' ← ' . import_rows( $run2 ) . ' · کهنه ' . $old_left );

/* برگرداندن به‌روزرسانی */
$u = topersia_pack_undo_batch( $run2, 0, 10 );
mut( 'برگرداندن به‌روزرسانی: همۀ متا (جمعیت، Repeater منبع)، عنوان و همۀ یال‌ها بایت‌به‌بایت همان پیش از اجرا — والد «الف» برگشت، «ب» رفت',
	function ( $x ) use ( $before_upd_m, $before_upd_r ) { return $x[0] && $before_upd_m === $x[1] && $before_upd_r === $x[2]; },
	array( $u['done'], meta_hash( $fa_id ), rel_hash( $fa_id ) ), array( $u['done'], $before_upd_m, 'x' ),
	meta_diff( $before_upd_a, meta_arr( $fa_id ) ) );

t( 'پس از برگرداندن: والد ۷۳ همان «الف»، گزینۀ آخرین درون‌ریزی «برگردانده شد»',
	array( $county_a ) === array_map( 'intval', (array) topersia_relation( 73 )->get_parents( $fa_id, 'ids' ) ) && ! empty( get_option( TOPERSIA_PACK_LAST_RUN )['undone'] ) );

/* جهش هرس: درون‌ریزیِ برگردانده دیگر استثنا نیست */
topersia_log_prune();
t( 'جهش هرس: پس از برگرداندن، ردیف‌های ۴۰۰روزۀ همان اجرا هرس می‌شوند — استثنا فقط برای آخرین درون‌ریزیِ برگشت‌پذیر',
	0 === import_rows( $run2 ) );

/* برگرداندن ساخت */
$u1 = topersia_pack_undo_batch( $run1, 0, 10 );
t( 'برگرداندن ساخت: هر دو پست تازه (fa و en) به زباله رفتند و یال ۷۳ شهر تازه برداشته شد',
	$u1['done'] && 'trash' === get_post_status( $fa_id ) && 'trash' === get_post_status( $en_id ) && array() === array_map( 'intval', (array) topersia_relation( 73 )->get_parents( $fa_id, 'ids' ) ),
	get_post_status( $fa_id ) . ' · ' . get_post_status( $en_id ) );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $GLOBALS['pass'], $GLOBALS['fail'] );
exit( $GLOBALS['fail'] ? 1 : 0 );
