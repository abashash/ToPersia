<?php
/**
 * دادۀ محلی ۴٫۴٫۱ (فاز ۲ SPRINT-4.4) — فقط روی topersia.local.
 *
 * هیچ‌چیز از این پرونده به سایت زنده نمی‌رود: نگهبان آغاز بیرون از
 * `topersia.local` با محیط `local` و پایگاه `topersia_local` اجرا نمی‌شود.
 * هر گام جداست و اجرای دوباره‌اش بی‌خطر است (ترجمۀ موجود به‌روز می‌شود نه تکرار).
 *
 *   translations       ترجمۀ انگلیسی و عربی استان #99 و شهر #399 با API ترجمۀ WPML     ۲٫۰٫۸
 *   translations-rel   رابطه‌های همان ترجمه‌ها با همگام‌ساز WPML خود جت‌انجین          ۲٫۰٫۸
 *   test-admin         مدیر آزمونِ tp_test_admin برای آزمون‌های پیشخوان و ثبت‌کنندۀ JS  پذیرش
 *   services-migrate   «انتقال» تنظیم‌های قدیمی به «سرویس‌های خارجی» هسته                ۲٫۲ · ۲٫۷
 *
 *   php scripts/tp-local-441.php translations translations-rel
 *
 * translations-rel: همان روالی که ویرایشگر ترجمۀ WPML پس از پایان هر ترجمه
 * صدا می‌زند (`sync_relations_items` بستۀ WPML جت‌انجین): هر رابطۀ اصل به
 * ترجمه می‌رسد و طرف مقابل به نسخۀ همان زبان، یا اگر نیست به اصل. بی آن،
 * صفحۀ ترجمه هیچ رابطه‌ای ندارد و شرط زبان WPML (`P5-D247`) روی صفحۀ واقعی
 * چیزی برای سنجیدن ندارد. درون تراکنش: اگر حتی یک ردیف موجود حذف شود،
 * همه برمی‌گردد. هیچ محتوای تازه‌ای نمی‌سازد.
 *
 * ترجمه: پست تازه با wp_insert_post در زبان مقصد، سپس
 * `wpml_set_element_language_details` با trid اصل و زبان منبع fa — API
 * ترجمۀ WPML؛ duplicate نه. فقط نام رسمی (عنوان)، یک جملۀ خلاصه
 * (`short_summary`) با منبعش (`source_references`) و `content_status`
 * برابر `needs_verification`. هر جمله با خواندن منبعش نوشته شد
 * (۲۸ سپتامبر ۲۰۲۶)؛ عدد «۳۱ استان» در هر دو منبع استان هست. فاصلۀ
 * اصفهان تا تهران نیامد: ویکی‌پدیای انگلیسی ۴۴۰ و عربی ۳۴۰ کیلومتر می‌گوید.
 *
 * WPML فیلد `content_status` را «copy» دارد: ذخیرۀ دوبارۀ اصل در پیشخوان
 * مقدار اصل را روی ترجمه می‌نویسد. این گام آن را دوباره `needs_verification`
 * می‌کند؛ اجرای دوباره بی‌خطر است.
 */

$_SERVER['HTTP_HOST']      = 'topersia.local';
$_SERVER['SERVER_NAME']    = 'topersia.local';
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

const TP_WP       = 'C:/wamp64/www/topersia/geo/';
const TP_REPORT   = 'C:/wamp64/www/topersia/LOCAL-DATA.txt';
const TP_MARK     = '══════ ۴٫۴٫۱ ══════';
const TP_SEEN     = '2026-09-28';
const TP_STEPS    = array( 'translations', 'translations-rel', 'test-admin', 'services-migrate' );

$steps = array_slice( $argv, 1 );

if ( ! $steps || array_diff( $steps, TP_STEPS ) ) {
	fwrite( STDERR, 'گام؟ ' . implode( ' ', TP_STEPS ) . "\n" );
	exit( 2 );
}

require TP_WP . 'wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی — topersia.local، محیط local، پایگاه topersia_local\n" );
	exit( 2 );
}

if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
	fwrite( STDERR, "WPML بار نشد\n" );
	exit( 2 );
}

wp_set_current_user( 1 );

function tp441_fail( $msg ) {
	fwrite( STDERR, $msg . "\n" );
	exit( 1 );
}

/* ── translations ──────────────────────────────────────────────────── */

/**
 * نام رسمی، یک جمله و منبعش — هر جمله بازنویسیِ کوتاهِ همان واقعیتِ منبع.
 */
function tp441_translations_data() {
	return array(
		99  => array(
			'en' => array(
				'title'   => 'Isfahan Province',
				'summary' => 'Isfahan Province is one of the 31 provinces of Iran, and its capital is the city of Isfahan.',
				'source'  => array( 'Wikipedia — Isfahan province', 'https://en.wikipedia.org/wiki/Isfahan_province' ),
			),
			'ar' => array(
				'title'   => 'محافظة أصفهان',
				'summary' => 'محافظة أصفهان إحدى محافظات إيران الإحدى والثلاثين، وعاصمتها مدينة أصفهان.',
				'source'  => array( 'ويكيبيديا — أصفهان (محافظة)', 'https://ar.wikipedia.org/wiki/' . rawurlencode( 'أصفهان_(محافظة)' ) ),
			),
		),
		399 => array(
			'en' => array(
				'title'   => 'Isfahan',
				'summary' => 'Isfahan is a city in Iran and the capital of Isfahan Province, Isfahan County and its Central District.',
				'source'  => array( 'Wikipedia — Isfahan', 'https://en.wikipedia.org/wiki/Isfahan' ),
			),
			'ar' => array(
				'title'   => 'أصفهان',
				'summary' => 'أصفهان إحدى مدن إيران، وهي مركز محافظة أصفهان.',
				'source'  => array( 'ويكيبيديا — أصفهان', 'https://ar.wikipedia.org/wiki/' . rawurlencode( 'أصفهان' ) ),
			),
		),
	);
}

function tp_step_translations() {

	$lines = array();

	foreach ( tp441_translations_data() as $orig_id => $langs ) {

		$orig = get_post( $orig_id );

		if ( ! $orig || 'publish' !== $orig->post_status ) {
			tp441_fail( "translations: #{$orig_id} نیست یا منتشرشده نیست" );
		}

		$etype   = 'post_' . $orig->post_type;
		$details = apply_filters( 'wpml_element_language_details', null, array( 'element_id' => $orig_id, 'element_type' => $etype ) );

		if ( ! $details || 'fa' !== $details->language_code || $details->source_language_code ) {
			tp441_fail( "translations: #{$orig_id} اصلِ فارسی نیست" );
		}

		$trid = (int) $details->trid;

		foreach ( $langs as $lang => $d ) {

			$have = apply_filters( 'wpml_get_element_translations', null, $trid, $etype );
			$id   = isset( $have[ $lang ] ) ? (int) $have[ $lang ]->element_id : 0;

			/* زبان جاری = زبان مقصد: صافی نامک WPML یکتایی را فقط در همان زبان می‌سنجد */
			do_action( 'wpml_switch_language', $lang );

			$post = array(
				'post_type'    => $orig->post_type,
				'post_status'  => 'publish',
				'post_title'   => $d['title'],
				'post_name'    => $orig->post_name,
				'post_content' => '',
				'post_author'  => (int) $orig->post_author,
			);

			if ( $id ) {
				$post['ID'] = $id;
				$res        = wp_update_post( wp_slash( $post ), true );
			} else {
				$res = wp_insert_post( wp_slash( $post ), true );
			}

			if ( is_wp_error( $res ) || ! $res ) {
				tp441_fail( "translations: #{$orig_id} {$lang} — " . ( is_wp_error( $res ) ? $res->get_error_message() : 'insert' ) );
			}

			$new = ! $id;
			$id  = (int) $res;

			do_action( 'wpml_set_element_language_details', array(
				'element_id'           => $id,
				'element_type'         => $etype,
				'trid'                 => $trid,
				'language_code'        => $lang,
				'source_language_code' => 'fa',
			) );

			update_post_meta( $id, 'short_summary', $d['summary'] );
			update_post_meta( $id, 'source_references', array(
				'item-0' => array(
					'source_name'        => $d['source'][0],
					'source_url'         => $d['source'][1],
					'source_access_date' => TP_SEEN,
					'source_row_status'  => 'active',
				),
			) );
			update_post_meta( $id, 'content_status', 'needs_verification' );

			$url = get_permalink( $id );

			do_action( 'wpml_switch_language', 'fa' );

			/* assert: همان trid، زبان و منبع درست، همان نامک، منتشرشده */
			clean_post_cache( $id );
			$chk = apply_filters( 'wpml_element_language_details', null, array( 'element_id' => $id, 'element_type' => $etype ) );
			$p   = get_post( $id );

			if ( ! $chk || (int) $chk->trid !== $trid || $lang !== $chk->language_code || 'fa' !== $chk->source_language_code ) {
				tp441_fail( "translations: #{$id} پیوند WPML نادرست — " . wp_json_encode( $chk ) );
			}
			if ( 'publish' !== $p->post_status || $orig->post_name !== $p->post_name || $d['title'] !== $p->post_title ) {
				tp441_fail( "translations: #{$id} پست نادرست — {$p->post_status} {$p->post_name}" );
			}
			if ( 'needs_verification' !== get_post_meta( $id, 'content_status', true ) ) {
				tp441_fail( "translations: #{$id} content_status" );
			}

			$lines[] = sprintf( '#%d %s %s ← #%d (%s) · %s · %s', $id, $lang, $orig->post_type, $orig_id, $new ? 'تازه' : 'به‌روز', $d['title'], $url );
			$lines[] = '    ' . $d['summary'];
			$lines[] = '    منبع: ' . $d['source'][0] . ' — ' . $d['source'][1] . ' · ' . TP_SEEN;
		}

		$all = apply_filters( 'wpml_get_element_translations', null, $trid, $etype );
		ksort( $all );
		$lines[] = sprintf( 'trid %d: %s', $trid, implode( ' · ', array_map( function ( $t ) {
			return $t->language_code . ' #' . $t->element_id;
		}, $all ) ) );
	}

	return array( 'ترجمه‌های ۲٫۰٫۸ — API ترجمۀ WPML، content_status = needs_verification' => $lines );
}

/* ── translations-rel ──────────────────────────────────────────────── */

function tp441_rel_rows() {
	global $wpdb;
	$out = array();
	foreach ( $wpdb->get_results( "SELECT rel_id, parent_object_id p, child_object_id c FROM {$wpdb->prefix}jet_rel_default", ARRAY_A ) as $r ) {
		$out[ $r['rel_id'] . ':' . $r['p'] . '>' . $r['c'] ] = true;
	}
	return $out;
}

function tp_step_translations_rel() {

	global $wpdb;

	$cls = '\Jet_Engine\Compatibility\Packages\Jet_Engine_WPML_Package\Relations\Manager';

	if ( ! function_exists( 'jet_engine' ) || ! jet_engine()->relations || ! class_exists( $cls ) ) {
		tp441_fail( 'translations-rel: بستۀ WPML جت‌انجین بار نشد' );
	}

	$jobs = array();
	foreach ( array_keys( tp441_translations_data() ) as $orig_id ) {
		$etype = 'post_' . get_post_type( $orig_id );
		$trid  = apply_filters( 'wpml_element_trid', null, $orig_id, $etype );
		foreach ( (array) apply_filters( 'wpml_get_element_translations', null, $trid, $etype ) as $lang => $t ) {
			if ( 'fa' !== $lang ) {
				$jobs[] = array( $orig_id, (int) $t->element_id, $lang );
			}
		}
	}

	if ( 4 !== count( $jobs ) ) {
		tp441_fail( 'translations-rel: چهار ترجمه لازم است — اول گام translations' );
	}

	$types = array();
	foreach ( jet_engine()->relations->get_active_relations() as $rid => $rel ) {
		$types[ $rid ] = $rel->get_args( 'parent_object' ) . '→' . $rel->get_args( 'child_object' );
	}

	$before = tp441_rel_rows();

	$wpdb->query( 'SET autocommit = 0' );
	$wpdb->query( 'START TRANSACTION' );

	foreach ( $jobs as $j ) {
		$cls::instance()->sync_relations_items( $j[0], $j[1], $j[2] );
	}

	$after   = tp441_rel_rows();
	$removed = array_diff_key( $before, $after );
	$added   = array_diff_key( $after, $before );

	if ( $removed ) {
		$wpdb->query( 'ROLLBACK' );
		$wpdb->query( 'SET autocommit = 1' );
		tp441_fail( 'translations-rel: ' . count( $removed ) . ' ردیف موجود حذف می‌شد — برگشت خورد: ' . implode( ' ', array_slice( array_keys( $removed ), 0, 12 ) ) );
	}

	$wpdb->query( 'COMMIT' );
	$wpdb->query( 'SET autocommit = 1' );

	$lines = array( sprintf( '%s — +%d ردیف، −۰ (ردیف‌های پیشین همه ماندند)', implode( ' · ', array_map( function ( $j ) {
		return '#' . $j[0] . '→#' . $j[1] . ' ' . $j[2];
	}, $jobs ) ), count( $added ) ) );

	$by = array();
	foreach ( array_keys( $added ) as $k ) {
		list( $rid, $pc ) = explode( ':', $k );
		$by[ $rid ][] = $pc;
	}
	ksort( $by );
	foreach ( $by as $rid => $list ) {
		$lines[] = sprintf( 'رابطۀ %s (%s): %s', $rid, $types[ $rid ] ?? '?', implode( ' ', $list ) );
	}

	return array( 'رابطه‌های ترجمه‌ها ۲٫۰٫۸ — همگام‌ساز WPML جت‌انجین' => $lines );
}

/* ── test-admin ─────────────────────────────────────────────────────
 * مدیر آزمون برای آزمون‌هایی که فقط مدیر می‌بیند (ثبت‌کنندۀ JS، سرویس‌های خارجی،
 * نشت کلید در پیشخوان). گذرواژه تصادفی است و همین‌جا دور ریخته می‌شود — هیچ‌جا
 * نوشته یا تایپ نمی‌شود؛ ورود فقط با نشستِ tests/local-login.php. نشان
 * `_tp_test_account` تنها چیزی است که local-login برای نقش مدیر می‌پذیرد.
 */
function tp_step_test_admin() {

	$login = 'tp_test_admin';
	$user  = get_user_by( 'login', $login );
	$new   = false;

	if ( ! $user ) {
		$id = wp_insert_user( array(
			'user_login'   => $login,
			'user_pass'    => wp_generate_password( 32, true, true ),
			'user_email'   => 'tp-test-admin@topersia.local',
			'display_name' => 'مدیر آزمون',
			'role'         => 'administrator',
		) );
		if ( is_wp_error( $id ) ) {
			tp441_fail( 'test-admin: ' . $id->get_error_message() );
		}
		$user = get_user_by( 'id', $id );
		$new  = true;
	}

	update_user_meta( $user->ID, '_tp_test_account', '1' );

	return array( 'مدیر آزمون' => array( sprintf( '#%d %s (%s) — administrator، گذرواژۀ دورریخته، ورود فقط با local-login', $user->ID, $login, $new ? 'تازه' : 'موجود' ) ) );
}

/* ── services-migrate ─────────────────────────────────────────────────
 *
 * همان دکمۀ «انتقال» پیشخوان: topersia_services_migrate(). پس از آن هر
 * سطر لایه با وضعیتش. سنجیده می‌شود که تنظیم قدیمی خلاصه‌ساز و هستۀ نمایش
 * دست نخورد، موتور خلاصه‌ساز همان پیشین ماند (کلیدی نبود، پس موتور
 * داخلی)، و دمای ai.text اگر منتقل شد عدد اعشاری ۰ تا ۲ است، بی تقسیم (۲٫۷).
 * اجرای دوباره بی‌خطر است: سطر ذخیره‌شده دست نمی‌خورد.
 */
function tp_step_services_migrate() {

	if ( ! function_exists( 'topersia_services_migrate' ) ) {
		tp441_fail( 'services-migrate: هستۀ ۴٫۱۱٫۰ روی نسخۀ محلی نیست' );
	}

	$legacy_before = array( get_option( 'tpas_settings' ), get_option( 'topersia_render_settings' ) );
	$engine_before = class_exists( 'TP_AI_Options' ) ? TP_AI_Options::default_engine() : null;
	$temp_before   = class_exists( 'TP_AI_Core_Bridge' ) ? TP_AI_Core_Bridge::temperature() : null;
	$report        = topersia_services_migrate();
	topersia_api_flush();
	$engine_after  = class_exists( 'TP_AI_Options' ) ? TP_AI_Options::default_engine() : null;

	if ( array( get_option( 'tpas_settings' ), get_option( 'topersia_render_settings' ) ) !== $legacy_before ) {
		tp441_fail( 'services-migrate: تنظیم قدیمی عوض شد' );
	}
	if ( $engine_after !== $engine_before ) {
		tp441_fail( 'services-migrate: موتور خلاصه‌ساز عوض شد' );
	}

	$lines = array();

	foreach ( $report as $service => $r ) {
		$cfg     = topersia_api( $service );
		$lines[] = sprintf( '%s: %s — %s · %s%s', $service, $r, $cfg['provider'], $cfg['status'], '' !== $cfg['reason'] && 'active' !== $cfg['status'] ? ' (' . $cfg['reason'] . ')' : '' );
	}

	$ai = topersia_api( 'ai.text' );
	if ( ! empty( $ai['configured'] ) && isset( $ai['extra']['temperature'] ) && '' !== (string) $ai['extra']['temperature'] ) {
		$t = TP_AI_Core_Bridge::temperature();
		if ( ! is_float( $t ) || $t < 0 || $t > 2 || $t !== $temp_before ) {
			tp441_fail( 'services-migrate: دما ' . var_export( $t, true ) . ' — پیش از انتقال ' . var_export( $temp_before, true ) );
		}
		$lines[] = 'دمای ai.text: ' . $t . ' — همان پیش از انتقال، بی تقسیم';
	} else {
		$lines[] = 'دمای ai.text منتقل نشد: خلاصه‌ساز موتور کلیددار نداشت؛ دما همان تنظیم خودش (' . var_export( $temp_before, true ) . ')';
	}

	$lines[] = 'موتور خلاصه‌ساز: ' . ( $engine_after ? $engine_after['id'] : 'داخلی (بی موتور بیرونی)' ) . ' — همان پیش از انتقال';
	$lines[] = 'تنظیم قدیمی خلاصه‌ساز و هستۀ نمایش دست نخورد';

	return array( 'انتقال سرویس‌ها' => $lines );
}

/* ── اجرا و گزارش ─────────────────────────────────────────────────── */

$R = array();

foreach ( $steps as $step ) {
	$fn = 'tp_step_' . str_replace( '-', '_', $step );
	$R  = array_merge( $R, $fn() );
}

$old  = is_file( TP_REPORT ) ? (string) file_get_contents( TP_REPORT ) : '';
$keep = false !== strpos( $old, TP_MARK ) ? substr( $old, 0, strpos( $old, TP_MARK ) ) : rtrim( $old ) . "\n\n";

/* بخش‌های پیشین همین نسخه می‌مانند؛ گام تازه جای همان عنوان را می‌گیرد */
$prev = array();
if ( false !== strpos( $old, TP_MARK ) ) {
	$tail = substr( $old, strpos( $old, TP_MARK ) + strlen( TP_MARK ) );
	foreach ( preg_split( '/\n(?=── )/u', $tail ) as $chunk ) {
		if ( preg_match( '/^── (.+?) ──\n(.*)$/su', trim( $chunk, "\n" ), $mm ) ) {
			$prev[ $mm[1] ] = array_values( array_filter( explode( "\n", rtrim( $mm[2] ) ), function ( $l ) { return '' !== trim( $l ); } ) );
		}
	}
}
foreach ( $R as $title => $lines ) {
	$prev[ $title ] = array_map( function ( $l ) { return '  ' . $l; }, $lines );
}

$out = $keep . TP_MARK . "\n"
	. 'فقط روی topersia_local. ساخته با workspace/scripts/tp-local-441.php · آخرین اجرا ' . gmdate( 'Y-m-d H:i' ) . " UTC\n";

foreach ( $prev as $title => $lines ) {
	$out .= "\n── {$title} ──\n" . implode( "\n", $lines ) . "\n";
}

file_put_contents( TP_REPORT, $out );

foreach ( $R as $title => $lines ) {
	echo "── {$title} ──\n  " . implode( "\n  ", $lines ) . "\n";
}
