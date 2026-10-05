<?php
/**
 * ادعاهای ایستای فاز ۲ج SPRINT-4.4 — از assert.php پس از assert-2b.php بار می‌شود؛
 * PHP خالص، بی وردپرس. هر کدام با جهش (tp441_mut).
 *
 *   ۲ج٫۵  گالری تجمیعی (P5-D274 · P5-D300) و صف واحد هسته (P5-D290)
 *
 * رفتار واقعی روی وردپرس محلی: tests/phase2c-local.php (صف، منبع، دروازه‌ها،
 * پاک‌سازی) و tests/phase2c.js (پیوند منبع در نمای تمام‌صفحه، کروم و فایرفاکس).
 */

$core2c  = dirname( TP_PLUGIN_DIR ) . '/topersia-core';
$main2c  = is_file( $core2c . '/topersia-core.php' ) ? file_get_contents( $core2c . '/topersia-core.php' ) : '';
$queue2c = is_file( $core2c . '/includes/topersia-queue.php' ) ? file_get_contents( $core2c . '/includes/topersia-queue.php' ) : '';
$as2c    = is_file( $core2c . '/vendor/action-scheduler/action-scheduler.php' ) ? file_get_contents( $core2c . '/vendor/action-scheduler/action-scheduler.php' ) : '';
$agg2c   = is_file( TP_PLUGIN_DIR . '/includes/topersia-g-gallery-agg.php' ) ? file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-gallery-agg.php' ) : '';
$zones2c = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );
$wpml2c  = is_file( TP_PLUGIN_DIR . '/wpml-config.xml' ) ? file_get_contents( TP_PLUGIN_DIR . '/wpml-config.xml' ) : '';
$rmain2c = file_get_contents( TP_PLUGIN_DIR . '/topersia-render.php' );
$schema2c = dirname( TP_PLUGIN_DIR ) . '/topersia-schema/topersia-schema.php';
$schema2c = is_file( $schema2c ) ? file_get_contents( $schema2c ) : '';

echo "\nفاز ۲ج SPRINT-4.4 — گالری تجمیعی و صف هسته  P5-D274 · P5-D300 · P5-D290\n" . str_repeat( '─', 78 ) . "\n";

/**
 * بدنۀ یک تابع (تورفته یا نه) — از امضا تا آکولادِ بسته‌ای که هم‌تورفتگیِ خودِ
 * `function` است. گاردِ `function_exists` درون بدنه پایانش نیست.
 */
function tp2c_body( $src, $name ) {
	$at = strpos( $src, 'function ' . $name . '(' );
	if ( false === $at ) {
		return '';
	}
	$bol    = strrpos( substr( $src, 0, $at ), "\n" );
	$bol    = false === $bol ? 0 : $bol + 1;
	$indent = substr( $src, $bol, $at - $bol );
	$end    = strpos( $src, "\n" . $indent . '}', $at );
	return false === $end ? substr( $src, $at ) : substr( $src, $at, $end + strlen( $indent ) + 2 - $at );
}

/* ── صف واحد هسته — P5-D290 ─────────────────────────────────────── */

tp441_mut( 'هسته Action Scheduler بسته‌بندی‌شدۀ خودش را پیش از صف بار می‌کند — vendor/action-scheduler سپس includes/topersia-queue.php؛ vendor جاافتاده سایت را نمی‌خواباند (is_readable)',
	function ( $s ) {
		$g = strpos( $s, "if ( is_readable( __DIR__ . '/vendor/action-scheduler/action-scheduler.php' ) ) {" );
		$a = strpos( $s, "require_once __DIR__ . '/vendor/action-scheduler/action-scheduler.php';" );
		$q = strpos( $s, "require_once __DIR__ . '/includes/topersia-queue.php';" );
		return false !== $g && false !== $a && false !== $q && $g < $a && $a < $q;
	},
	$main2c, str_replace( "if ( is_readable( __DIR__ . '/vendor/action-scheduler/action-scheduler.php' ) ) {", 'if ( true ) {', $main2c ) );

tp441_mut( 'Action Scheduler همان 3.9.3 (نسخۀ Rank Math) — نه 4.x که ستون و نمایۀ یکتای جدول مشترک را می‌افزاید (قاعدۀ ۱۲)',
	function ( $s ) { return (bool) preg_match( '/^\s*\*\s*Version:\s*3\.9\.3\s*$/m', $s ) && false !== strpos( $s, 'action_scheduler_register_3_dot_9_dot_3' ); },
	$as2c, preg_replace( '/Version:\s*3\.9\.3/', 'Version: 4.2.0', $as2c ) );

tp441_mut( 'ادغام فقط با کارِ «در انتظار» — کارِ در حال اجرا شاید دادۀ پیش از تغییر را خوانده باشد (as_has_scheduled_action در حال اجرا را هم می‌شمارد)',
	function ( $b ) { return false !== strpos( $b, 'topersia_queue_pending( $hook, $args )' ) && false === strpos( $b, 'as_has_scheduled_action(' ); },
	tp2c_body( $queue2c, 'topersia_queue_add' ),
	str_replace( 'topersia_queue_pending( $hook, $args )', 'as_has_scheduled_action( $hook, $args, TOPERSIA_QUEUE_GROUP )', tp2c_body( $queue2c, 'topersia_queue_add' ) ) );

tp441_mut( 'صف موازی نیست: گالری تجمیعی فقط از topersia_queue_add — هیچ wp_schedule_* یا as_* مستقیم، هیچ کرون تازه',
	function ( $s ) { return '' !== $s && false !== strpos( $s, "topersia_queue_add( 'topersia_gallery_agg_rebuild'" ) && ! preg_match( '/wp_schedule_(?:single_)?event\s*\(|\bas_(?:schedule|enqueue)_[a-z_]+\s*\(/', $s ); },
	$agg2c, $agg2c . "\nwp_schedule_single_event( time(), 'topersia_gallery_agg_rebuild' );" );

/* ── لایۀ مشتق — P5-D300 ──────────────────────────────────────────── */

$shots2c = tp2c_body( $zones2c, 'topersia_g_gallery_shots_for' );
tp441_mut( 'بارگذاری صفحه هیچ چیزی نمی‌سازد: topersia_g_gallery_shots_for فقط متای ذخیره را می‌خواند — نه build، نه rebuild، نه پیمودن فرزندان',
	function ( $b ) { return false !== strpos( $b, 'topersia_g_gallery_agg_stored( $pid )' ) && ! preg_match( '/topersia_g_gallery_agg_(?:build|rebuild|children|full)\s*\(/', $b ); },
	$shots2c, str_replace( 'topersia_g_gallery_agg_stored( $pid )', 'topersia_g_gallery_agg_build( $pid )', $shots2c ) );

tp441_mut( 'دروازۀ محلی تا ممیزی: پیش‌فرض فقط local با صافی topersia_gallery_agg_enabled؛ نمایش و صف هر دو از آن می‌گذرند',
	function ( $x ) {
		return false !== strpos( $x[0], "'local' === wp_get_environment_type()" ) && false !== strpos( $x[0], "'topersia_gallery_agg_enabled'" )
			&& false !== strpos( $x[1], 'topersia_g_gallery_agg_enabled()' ) && false !== strpos( $x[2], 'topersia_g_gallery_agg_enabled()' );
	},
	array( tp2c_body( $agg2c, 'topersia_g_gallery_agg_enabled' ), $shots2c, tp2c_body( $agg2c, 'topersia_g_gallery_agg_schedule' ) ),
	array( str_replace( "'local' === wp_get_environment_type()", 'true', tp2c_body( $agg2c, 'topersia_g_gallery_agg_enabled' ) ), $shots2c, tp2c_body( $agg2c, 'topersia_g_gallery_agg_schedule' ) ) );

tp441_mut( 'متای مشتق _tp_gallery_agg فقط روی زبان اصلی و در wpml-config «ignore» — نه copy، نه translate؛ wpml-config در render است',
	function ( $x ) {
		return (bool) preg_match( '~<custom-field action="ignore">_tp_gallery_agg</custom-field>~', $x[0] )
			&& false !== strpos( $x[1], "define( 'TOPERSIA_GALLERY_AGG_META', '_tp_gallery_agg' )" )
			&& false !== strpos( tp2c_body( $x[1], 'topersia_g_gallery_agg_stored' ), 'topersia_g_gallery_agg_primary( $post_id )' );
	},
	array( $wpml2c, $agg2c ), array( str_replace( 'action="ignore"', 'action="copy"', $wpml2c ), $agg2c ) );

tp441_mut( 'گالری در اسکیما نیست: topersia-schema نه featured_image_gallery می‌خواند نه _tp_gallery_agg — image فقط hero_image',
	function ( $s ) { return '' !== $s && false === strpos( $s, 'featured_image_gallery' ) && false === strpos( $s, '_tp_gallery_agg' ) && false !== strpos( $s, "'image' => 'hero_image'" ); },
	$schema2c, $schema2c . "\n// get_post_meta( \$id, '_tp_gallery_agg', true )" );

/* ── ساخت، بازسازی، قلاب‌ها ───────────────────────────────────────── */

$build2c = tp2c_body( $agg2c, 'topersia_g_gallery_agg_build' );
tp441_mut( 'ساخت به نوبت: $row پیش از هر فرزند از صفر — فرزندِ بی‌عکس عکس فرزند پیشین را دوباره نمی‌افزاید (یافتۀ نخستین «بازسازی همه»)',
	function ( $b ) { return (bool) preg_match( '/\$row = null;\s*\n\s*while \( \$list \)/', $b ) && false !== strpos( $b, 'TOPERSIA_GALLERY_PER_CHILD' ) && false !== strpos( $b, 'TOPERSIA_GALLERY_MAX' ); },
	$build2c, preg_replace( '/\$row = null;(\s*\n\s*while \( \$list \))/', '$1', $build2c ) );

$rebuild2c = tp2c_body( $agg2c, 'topersia_g_gallery_agg_rebuild' );
tp441_mut( 'آبشار: بازسازیِ تغییرکرده نیاکان را دوباره در صف می‌گذارد؛ «بازسازی همه» و --post بی آبشار',
	function ( $x ) {
		return false !== strpos( $x[0], 'if ( $cascade && $rows !== $old )' ) && false !== strpos( $x[0], 'topersia_g_gallery_agg_schedule( $post_id, false )' )
			&& false !== strpos( $x[1], 'topersia_g_gallery_agg_rebuild( $id, false )' ) && false !== strpos( $x[2], "topersia_g_gallery_agg_rebuild( absint( \$assoc['post'] ), false )" );
	},
	array( $rebuild2c, tp2c_body( $agg2c, 'topersia_g_gallery_agg_rebuild_all' ), $agg2c ),
	array( str_replace( 'topersia_g_gallery_agg_schedule( $post_id, false )', '', $rebuild2c ), tp2c_body( $agg2c, 'topersia_g_gallery_agg_rebuild_all' ), $agg2c ) );

tp441_mut( 'ساختن همیشه به زبان اصلی WPML — بازسازی و فهرست «همه» در topersia_g_gallery_agg_in_default_lang (همان topersia_g_in_default_lang)؛ زبان پیشین در finally برمی‌گردد',
	function ( $x ) {
		return false !== strpos( $x[0], 'return topersia_g_gallery_agg_in_default_lang( function () use ( $post_id, $cascade ) {' )
			&& false !== strpos( $x[1], 'return topersia_g_gallery_agg_in_default_lang( function () {' )
			&& (bool) preg_match( "/finally \{\s*do_action\( 'wpml_switch_language', \\\$current \);/", $x[2] );
	},
	array( $rebuild2c, tp2c_body( $agg2c, 'topersia_g_gallery_agg_all_ids' ), tp2c_body( $zones2c, 'topersia_g_in_default_lang' ) . tp2c_body( $agg2c, 'topersia_g_gallery_agg_in_default_lang' ) ),
	array( str_replace( 'return topersia_g_gallery_agg_in_default_lang( function () use ( $post_id, $cascade ) {', 'return ( function () use ( $post_id, $cascade ) {', $rebuild2c ), tp2c_body( $agg2c, 'topersia_g_gallery_agg_all_ids' ), tp2c_body( $zones2c, 'topersia_g_in_default_lang' ) . tp2c_body( $agg2c, 'topersia_g_gallery_agg_in_default_lang' ) ) );

tp441_mut( '«بازسازی همه» از صف پخش می‌شود — یک کار برای هر پست، نه یک کار بلند؛ هم‌زمان فقط WP-CLI',
	function ( $s ) {
		return false !== strpos( $s, "add_action( 'topersia_gallery_agg_rebuild_all', 'topersia_g_gallery_agg_queue_all' );" )
			&& false !== strpos( tp2c_body( $s, 'topersia_g_gallery_agg_queue_all' ), "topersia_queue_add( 'topersia_gallery_agg_rebuild', array( 'post' => \$id )" );
	},
	$agg2c, str_replace( "add_action( 'topersia_gallery_agg_rebuild_all', 'topersia_g_gallery_agg_queue_all' );", "add_action( 'topersia_gallery_agg_rebuild_all', 'topersia_g_gallery_agg_rebuild_all' );", $agg2c ) );

$hooks2c = array( "'save_post'", "'transition_post_status'", "'before_delete_post'", "'added_post_meta'", "'updated_post_meta'", "'deleted_post_meta'",
	"'jet-engine/relation/update/before'", "'jet-engine/relation/update/after'", "'jet-engine/relation/delete/after'" );
tp441_mut( 'هر راه تغییر بازسازی را در صف می‌گذارد: ذخیره، وضعیت، حذف، متای گالری، ردیف رابطه (پیش و پس از نوشتن، و حذف) — فقط سوی posts::',
	function ( $s ) use ( $hooks2c ) {
		foreach ( $hooks2c as $h ) { if ( false === strpos( $s, 'add_action( ' . $h ) ) { return false; } }
		/* پست منتشرنشده و پستِ در حال حذف فقط نیاکان را در صف می‌گذارند */
		return false !== strpos( $s, "0 === strpos( (string) \$rel->get_args( \$side ), 'posts::' )" )
			&& false !== strpos( $s, '$tp_agg_touch( $post_id, false );' )
			&& false !== strpos( tp2c_body( $s, 'topersia_g_gallery_agg_schedule' ), "'publish' === get_post_status( \$primary )" );
	},
	$agg2c, str_replace( "add_action( 'jet-engine/relation/delete/after'", "add_action( 'jet-engine/relation/delete/never'", $agg2c ) );

tp441_mut( '«بازسازی همه» در پیشخوان: manage_options و check_admin_referer پیش از صف؛ کارت فقط برای manage_options و در دروازه',
	function ( $s ) {
		$h = substr( $s, (int) strpos( $s, "add_action( 'admin_post_topersia_gallery_agg_rebuild_all'" ), 700 );
		$c = strpos( $h, "current_user_can( 'manage_options' )" );
		$n = strpos( $h, "check_admin_referer( 'topersia_gallery_agg_rebuild_all' )" );
		$q = strpos( $h, "topersia_queue_add( 'topersia_gallery_agg_rebuild_all' )" );
		$card = tp2c_body( $s, 'topersia_g_gallery_agg_admin_card' );
		return false !== $c && false !== $n && false !== $q && $c < $n && $n < $q
			&& false !== strpos( $card, "! current_user_can( 'manage_options' )" ) && false !== strpos( $card, "wp_nonce_field( 'topersia_gallery_agg_rebuild_all' )" );
	},
	$agg2c, str_replace( "check_admin_referer( 'topersia_gallery_agg_rebuild_all' );", '', $agg2c ) );

tp441_mut( 'render پروندۀ گالری تجمیعی را در فهرست بارگذاری‌اش دارد',
	function ( $s ) { return (bool) preg_match( "~foreach \( array\([^)]*'includes/topersia-g-gallery-agg\.php'[^)]*\) as \\\$topersia_g_file \)~", $s ); },
	$rmain2c, str_replace( ", 'includes/topersia-g-gallery-agg.php'", '', $rmain2c ) );

/* ── ۲ج٫۹ — درون‌ریزی: فارسی پیش‌فرض، ترجمه جدا (P5-D283) ──────────────── */

echo "\nفاز ۲ج SPRINT-4.4 — ۲ج٫۹ درون‌ریزی ترجمه  P5-D283\n" . str_repeat( '─', 78 ) . "\n";

$pack2c = is_file( $core2c . '/includes/topersia-pack.php' ) ? file_get_contents( $core2c . '/includes/topersia-pack.php' ) : '';
$tr_msg = 'برای ترجمه از بخش درون‌ریزی ترجمه استفاده کنید.';

tp441_mut( 'درون‌ریزی اصلی فقط زبان پیش‌فرض: پرونده و پستِ زبان دیگر با پیام «' . $tr_msg . '» رد؛ JSON Schema اصلی هم فقط همان زبان؛ پروندۀ ترجمه در بخش اصلی رد',
	function ( $s ) use ( $tr_msg ) {
		$parse = tp2c_body( $s, 'topersia_pack_parse' );
		$plan  = tp2c_body( $s, 'topersia_pack_plan_post' );
		return false !== strpos( $parse, "new WP_Error( 'pack_lang', '" . $tr_msg . "' )" ) && false !== strpos( $parse, "TOPERSIA_PACK_TR_SCHEMA === \$schema" )
			&& false !== strpos( $plan, "\$plan['problem'] = 'lang';" ) && false !== strpos( $plan, "'" . $tr_msg . "'" )
			&& false !== strpos( tp2c_body( $s, 'topersia_pack_json_schema' ), '$langs  = array( topersia_pack_default_language() );' )
			&& false === strpos( $plan, 'translation_of' . "'] ) && is_string( \$post['translation_of'] ) ? trim" );
	},
	$pack2c, str_replace( "\$plan['problem'] = 'lang';", "\$plan['problem'] = '';", $pack2c ) );

$trplan2c  = tp2c_body( $pack2c, 'topersia_pack_tr_plan_post' );
$trapply2c = tp2c_body( $pack2c, 'topersia_pack_tr_apply' );
tp441_mut( 'ترجمه: فقط کلاس «translate» WPML از پرونده نوشته می‌شود — بقیه «فیلد غیرترجمه‌ای در فایل ← نادیده» پیش از هر خواندن مقدار؛ رابطه از پرونده هرگز',
	function ( $b ) {
		$ig = strpos( $b, "if ( ! isset( \$classes[ \$key ] ) || 2 !== \$classes[ \$key ] ) {" );
		$in = strpos( $b, 'topersia_pack_value_in( $def, $value, $key )' );
		return false !== $ig && false !== $in && $ig < $in && false !== strpos( $b, "'status' => 'tr_ignored'" )
			&& false === strpos( $b, "\$post['relations']" ) && false !== strpos( $b, "array( 'translation_of', 'title', 'content', 'fields', '_dup_of' )" );
	},
	$trplan2c, str_replace( "if ( ! isset( \$classes[ \$key ] ) || 2 !== \$classes[ \$key ] ) {", 'if ( false ) {', $trplan2c ) );

tp441_mut( 'ترجمه: پیوند با API ترجمۀ WPML (trid پست فارسی)، پیش‌نویس، copy با wpml_sync_custom_field؛ needs_verification پس از ذخیرۀ پایانی (WPML با هر ذخیره copy را از اصل می‌نشاند)',
	function ( $b ) {
		$save = strpos( $b, "wp_update_post( array( 'ID' => \$post_id ) );" );
		$st   = strpos( $b, "delete_post_meta( \$post_id, 'content_status' );" );
		return false !== strpos( $b, "'trid'                 => apply_filters( 'wpml_element_trid', null, \$original, \$element )" )
			&& false !== strpos( $b, "'post_status'  => 'draft'" ) && false !== strpos( $b, "do_action( 'wpml_sync_custom_field', \$original, \$key );" )
			&& false !== $save && false !== $st && $save < $st && false !== strpos( $b, "array( 'needs_verification' )" );
	},
	$trapply2c, str_replace( "delete_post_meta( \$post_id, 'content_status' );", '', $trapply2c ) );

tp441_mut( 'رابطۀ ترجمه از پست فارسی با گارد: طرفِ بی‌ترجمه در رابطه‌ای که آن طرف یک همتا می‌گیرد پیوند نمی‌خورد (یال فارسی دست نمی‌خورد)',
	function ( $b ) {
		return false !== strpos( $b, "\$single = 'parent' === \$role ? \$relation->is_single_parent() : \$relation->is_single_child();" )
			&& false !== strpos( $b, 'if ( ! $mapped || ( $single && $mapped === $item ) ) {' ) && false !== strpos( $b, 'topersia_relation_connect(' );
	},
	tp2c_body( $pack2c, 'topersia_pack_tr_relations' ),
	str_replace( '( $single && $mapped === $item )', '( false )', tp2c_body( $pack2c, 'topersia_pack_tr_relations' ) ) );

/* ── ۲ج٫۸ — همه‌چیز به زبان صفحه (P5-D278) ─────────────────────────── */

echo "\nفاز ۲ج SPRINT-4.4 — ۲ج٫۸ همه‌چیز به زبان صفحه  P5-D278\n" . str_repeat( '─', 78 ) . "\n";

/* رفتار، با همان سازوکار زبانِ assert-442 (صافی wpml_current_language و کاتالوگ ساخته‌شده) */
$GLOBALS['tp_meta'][990801] = array(
	'overview_text' => '<p>استان اصفهان در مرکز ایران است و از شمال به استان قم می‌رسد.</p>',
	'short_summary' => 'Isfahan is a province in central Iran (اصفهان).',
	'name_local'    => 'اِصفَهان',
);
$probe8 = function () {
	return array(
		'overview' => topersia_g_raw( 990801, 'overview_text' ),
		'summary'  => topersia_g_raw( 990801, 'short_summary' ),
		'local'    => topersia_g_raw( 990801, 'name_local' ),
		'code'     => topersia_g_value( 'calling_code', '+۹۸' ),
		'tld'      => topersia_g_value( 'internet_tld', '.ir .ایران' ),
	);
};
$lang8 = array( 'fa' => tp442_lang( 'fa', $probe8 ), 'en' => tp442_lang( 'en', $probe8 ), 'ar' => tp442_lang( 'ar', $probe8 ) );
unset( $GLOBALS['tp_meta'][990801] );

tp441_mut( 'فیلد به زبان دیگر: در /en/ و /ar/ متن چیره‌فارسی خالی، متن انگلیسیِ دارای نام فارسی و نام محلی سر جایشان؛ کد «+۹۸» ← «+98» (/ar/ «+٩٨»)، «.ایران» با bdi lang="fa"؛ صفحۀ فارسی دست‌نخورده',
	function ( $x ) {
		return '' === $x['en']['overview'] && false !== strpos( $x['en']['summary'], 'Isfahan' ) && 'اِصفَهان' === $x['en']['local']
			&& false !== strpos( $x['en']['code'], '+98' ) && false !== strpos( $x['en']['tld'], '<bdi lang="fa" dir="rtl">ایران</bdi>' )
			&& false !== strpos( $x['ar']['code'], '+٩٨' ) && '' === $x['ar']['overview'] && false !== strpos( $x['ar']['tld'], '<bdi lang="fa" dir="rtl">ایران</bdi>' )
			&& false !== strpos( $x['fa']['overview'], 'استان اصفهان' ) && false !== strpos( $x['fa']['code'], '+۹۸' ) && false === strpos( $x['fa']['tld'], '<bdi' );
	},
	$lang8, array_replace_recursive( $lang8, array( 'en' => array( 'overview' => $lang8['fa']['overview'] ) ) ) );

$shots8 = array( array( 'id' => 7, 'img' => '<img src="x.jpg" alt="کاروانسرای سعدالسلطنه">', 'label' => 'کاروانسرای سعدالسلطنه', 'place' => 'قزوین', 'foreign' => true ) );
$loc8   = tp442_lang( 'en', function () use ( $shots8 ) { return topersia_g_gallery_localize( $shots8 ); } );
tp441_mut( 'گالری: پیوستی که متنش به زبان صفحه نیست — برچسب «Image 1» از کاتالوگ، همان در alt، بی توضیح؛ عکس می‌ماند',
	function ( $s ) { return 'Image 1' === $s[0]['label'] && '' === $s[0]['place'] && false !== strpos( $s[0]['img'], 'alt="Image 1"' ) && false !== strpos( $s[0]['img'], 'src="x.jpg"' ); },
	$loc8, $shots8 );

$jsz2c = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-zones.js' );
tp441_mut( 'نقشه: برچسب کاشی با lang سبک Protomaps، رابط MapLibre با locale از کاتالوگ سرور (دکمه‌ها و aria-label نقشه و نشانگر)، انتساب ترجمه‌پذیر',
	function ( $x ) {
		$loc = tp2c_body( $x[1], 'topersia_g_map_locale' );
		foreach ( array( 'Map.Title', 'Marker.Title', 'NavigationControl.ZoomIn', 'NavigationControl.ZoomOut', 'AttributionControl.ToggleAttribution' ) as $k ) {
			if ( ! preg_match( "/'" . preg_quote( $k, '/' ) . "'\s*=> __\(/", $loc ) ) { return false; }
		}
		return false !== strpos( $x[0], 'locale: cfg.locale || {}' ) && false !== strpos( $x[0], "{ lang: cfg.lang || 'fa' }" )
			&& false !== strpos( $x[1], "\$cfg['locale'] = topersia_g_map_locale();" ) && false !== strpos( $x[1], "'attribution' => __( '© مشارکت‌کنندگان OpenStreetMap', 'topersia' )" );
	},
	array( $jsz2c, $zones2c ), array( str_replace( 'locale: cfg.locale || {}', 'locale: {}', $jsz2c ), $zones2c ) );

tp441_mut( 'یک دروازه: topersia_g_raw هر مقدار را از topersia_g_untranslated می‌گذراند؛ نام‌ها، منبع‌ها و کد latin بیرون از آن',
	function ( $s ) {
		$raw  = tp2c_body( $s, 'topersia_g_raw' );
		$gate = tp2c_body( $s, 'topersia_g_untranslated' );
		return false !== strpos( $raw, 'if ( topersia_g_untranslated( $key, $value ) ) {' ) && false !== strpos( $gate, "'source_references'" )
			&& false !== strpos( $gate, "'name_local'" ) && false !== strpos( $gate, "'latin' === \$map[ \$key ]['kind']" );
	},
	$zones2c, str_replace( 'if ( topersia_g_untranslated( $key, $value ) ) {', 'if ( false ) {', $zones2c ) );

/* ══ ۲ج٫۱۱ صفحۀ سرویس‌های خارجی ═══════════════════════════════════════ */

echo "\nفاز ۲ج SPRINT-4.4 — ۲ج٫۱۱ صفحۀ سرویس‌های خارجی\n" . str_repeat( '─', 78 ) . "\n";

$svc2c   = is_file( $core2c . '/includes/topersia-services.php' ) ? file_get_contents( $core2c . '/includes/topersia-services.php' ) : '';
$page11  = tp2c_body( $svc2c, 'topersia_services_page' );
$row11   = tp2c_body( $svc2c, 'topersia_services_row_html' );
$css11   = preg_match( "/echo '<style>'\n(.*?)\. '<\/style>';/s", $page11, $m11 ) ? $m11[1] : '';

tp441_mut( 'جهت صفحه همان جهت پیشخوان: wrap بی dir ثابت (پیشخوان انگلیسی چپ‌به‌راست، فارسی راست‌به‌چپ)',
	function ( $p ) { return false !== strpos( $p, '<div class="wrap tp-admin tp-services-page"><h1>' ) && false === strpos( $p, 'tp-services-page" dir=' ); },
	$page11, str_replace( 'tp-services-page"><h1>', 'tp-services-page" dir="rtl"><h1>', $page11 ) );

tp441_mut( 'شیوه‌نامۀ صفحه: همۀ خانه‌ها vertical-align:middle و text-align:start، ستون کوتاه وسط‌چین؛ فقط ویژگی منطقی — هیچ left/right ثابت؛ بی min-width ۱۱۸۰ (بی اسکرول افقی از ۱۰۲۴)',
	function ( $c ) {
		return '' !== $c && false !== strpos( $c, ':is(th,td){vertical-align:middle;text-align:start;' ) && false !== strpos( $c, ':is(th,td).tp-c-short{text-align:center}' )
			&& ! preg_match( '/(margin|padding|border)-(left|right)\s*:|[{;]\s*(left|right)\s*:|text-align\s*:\s*(left|right)|float\s*:\s*(left|right)/', $c )
			&& false === strpos( $c, '1180px' );
	},
	$css11, str_replace( '.tp-services-page.wrap{margin-block:10px 24px;margin-inline:20px}', '.tp-services-page.wrap{margin:10px 20px 24px;margin-left:2px}', $css11 ) );

tp441_mut( 'ستون‌های کوتاه (روشن · وضعیت · TTL · آزمون) در سر و در هر سطر tp-c-short؛ هیچ سرستون خالی',
	function ( $x ) {
		return 4 === substr_count( $x[0], '<th scope="col" class="tp-c-short">' ) && 4 === substr_count( $x[1], '<td class="tp-c-short">' )
			&& false === strpos( $x[0], '<th></th>' ) && false === strpos( $x[0], '<th scope="col"></th>' );
	},
	array( $page11, $row11 ), array( $page11, str_replace( '<td class="tp-c-short"><button', '<td><button', $row11 ) ) );

tp441_mut( 'مقدار ناپیدا: ارائه‌دهنده رادیو نه select (نام بلند بریده نمی‌شد)، نشانی و مدل در textarea خودبلندشونده، پیش‌فرض TTL نوشته می‌شود، extra بی فیلد (مسیر PMTiles) پیدا',
	function ( $r ) {
		return false === strpos( $r, '<select' ) && false !== strpos( $r, 'type="radio"' ) && 2 <= substr_count( $r, '<textarea class="tp-svc-grow"' )
			&& false !== strpos( $r, "topersia_services_pair( 'پیش‌فرض', \$ttl_l )" ) && false !== strpos( $r, "topersia_services_bdi( \$k . ': ' . \$v )" );
	},
	$row11, str_replace( 'type="radio"', 'type="select"', $row11 ) . '<select' );

tp441_mut( 'textarea هم‌قد متن یا نگه‌دارش — در بارگذاری، تغییر اندازه و نمایش سطر دوم («+»)',
	function ( $p ) { return false !== strpos( $p, 'function grow(t){var v=t.value;if(!v&&t.placeholder){t.value=t.placeholder}' ) && false !== strpos( $p, 'r.hidden=false;growAll(r);' ) && false !== strpos( $p, 'window.addEventListener("resize",function(){growAll(document)});' ); },
	$page11, str_replace( 'r.hidden=false;growAll(r);', 'r.hidden=false;', $page11 ) );

if ( ! function_exists( 'topersia_services_bdi' ) && '' !== $svc2c ) {
	eval( tp2c_body( $svc2c, 'topersia_services_bdi' ) . "\n" . tp2c_body( $svc2c, 'topersia_services_pair' ) ); // phpcs:ignore Squiz.PHP.Eval -- فقط دو تابع خالص همین فایل
}
$bdi11 = function_exists( 'topersia_services_bdi' ) ? array(
	'id'    => topersia_services_bdi( 'ai.tts' ),
	'head'  => topersia_services_bdi( 'endpoint · مدل · نسخه' ),
	'empty' => topersia_services_pair( 'آخرین موفق', '' ),
	'pair'  => topersia_services_pair( 'آخرین موفق', '2026-10-05 11:09' ),
) : array();
tp441_mut( 'جداساز: مقدار لاتین bdi[dir=ltr]؛ عبارت فارسی — حتی آغازشده با واژۀ لاتین — bdi[dir=rtl] (dir خودکار «endpoint · مدل» را چپ‌به‌راست می‌کرد)؛ «برچسب: مقدار» یک جداساز فارسی با مقدار ltr درونش، خالی «—»',
	function ( $b ) {
		return $b && '<bdi dir="ltr">ai.tts</bdi>' === $b['id'] && '<bdi dir="rtl">endpoint · مدل · نسخه</bdi>' === $b['head']
			&& '<bdi dir="rtl">آخرین موفق: —</bdi>' === $b['empty'] && '<bdi dir="rtl">آخرین موفق: <bdi dir="ltr">2026-10-05 11:09</bdi></bdi>' === $b['pair'];
	},
	$bdi11, str_replace( '<bdi dir="rtl">endpoint', '<bdi>endpoint', $bdi11 ) );

/* بازبینی پایان فاز: آرایه — فقط مقدارها؛ کلید لاتین زیرفیلد چیرگی متن فارسی را نمی‌پوشاند */
$GLOBALS['tp_meta'][990802] = array(
	'tp_rep'   => array( array( 'fact_label_text' => 'جمعیت', 'fact_value_text' => 'زیاد' ) ),
	'tp_slugs' => array( 'late-spring', 'early-autumn' ),
);
$rep8 = tp442_lang( 'en', function () {
	return array( 'rep' => topersia_g_raw( 990802, 'tp_rep' ), 'slugs' => topersia_g_raw( 990802, 'tp_slugs' ) );
} );
unset( $GLOBALS['tp_meta'][990802] );
tp441_mut( 'دروازهٔ زبان برای آرایه: فقط مقدارها سنجیده می‌شوند — repeater فارسی با کلیدهای لاتین در /en/ خالی، گزینه‌های نامکی می‌مانند',
	function ( $x ) { return array() === $x['rep'] && array( 'late-spring', 'early-autumn' ) === $x['slugs']; },
	$rep8, array_merge( $rep8, array( 'rep' => array( array( 'fact_label_text' => 'جمعیت', 'fact_value_text' => 'زیاد' ) ) ) ) );

/* ══ وصلۀ ۲ج و۱ — گزارش شکاف ترجمه (P5-D305) ════════════════════════════ */

echo "\nوصلۀ ۲ج — و۱ گزارش شکاف ترجمه  P5-D305\n" . str_repeat( '─', 78 ) . "\n";

$gap2c  = is_file( $core2c . '/includes/topersia-i18n-gaps.php' ) ? file_get_contents( $core2c . '/includes/topersia-i18n-gaps.php' ) : '';
$pack2c = is_file( $core2c . '/includes/topersia-pack.php' ) ? file_get_contents( $core2c . '/includes/topersia-pack.php' ) : '';

tp441_mut( 'گزارش هیچ نمی‌نویسد: در پروندۀ گزارش هیچ نوشتن متا، پست، گزینه، transient یا پرس‌وجوی نوشتنی نیست (در لحظه از داده؛ بی جدول و متای تازه)',
	function ( $s ) { return '' !== $s && ! preg_match( '/\b(update_post_meta|add_post_meta|delete_post_meta|wp_insert_post|wp_update_post|wp_delete_post|update_option|add_option|set_transient|wp_cache_set)\s*\(|\$wpdb->(insert|update|delete|query|replace)\s*\(/', $s ); },
	$gap2c, $gap2c . "\nupdate_post_meta( 1, 'x', 'y' );" );

tp441_mut( 'کلیدهای شکاف از کلاس translate خودِ تنظیمات WPML (topersia_pack_tr_keys)، نه فهرست دستی؛ عنوان و متن هم',
	function ( $s ) { $b = tp2c_body( $s, 'topersia_i18n_post_gaps' ); return false !== strpos( $b, 'foreach ( topersia_pack_tr_keys( $type ) as $key )' ) && false !== strpos( $b, "array( 'post_title', 'post_content' )" ); },
	$gap2c, str_replace( 'foreach ( topersia_pack_tr_keys( $type ) as $key )', "foreach ( array( 'official_language' ) as \$key )", $gap2c ) );

tp441_mut( 'تشخیص خط همان render ۲ج٫۸: آستانۀ سه درصد حرف ویژۀ فارسی در عربی، عربی‌خط بیش از لاتین در بقیه، آرایه فقط با مقدار',
	function ( $x ) {
		$c = tp2c_body( $x[0], 'topersia_i18n_foreign' );
		$r = tp2c_body( $x[1], 'topersia_g_foreign_text' );
		$t = tp2c_body( $x[0], 'topersia_i18n_text' );
		foreach ( array( '[\x{067E}\x{0686}\x{0698}\x{06AF}\x{06CC}\x{06A9}]', '0.03 * $arabic', "preg_match_all( '/\p{Latin}/u', \$text )" ) as $needle ) {
			if ( false === strpos( $c, $needle ) || false === strpos( $r, $needle ) ) { return false; }
		}
		return false !== strpos( $t, 'array_walk_recursive' );
	},
	array( $gap2c, $zones2c ), array( str_replace( '0.03 * $arabic', '0.3 * $arabic', $gap2c ), $zones2c ) );

tp441_mut( 'WP-CLI `wp topersia i18n-gaps` با --lang · --type · --format=table|csv|json؛ برون‌بری «what=gaps» همان قالب ۲ج٫۹ (topersia_i18n_gap_export)؛ کارت پیشخوان فقط با درخواست (gaps=1)',
	function ( $x ) {
		return false !== strpos( $x[0], "WP_CLI::add_command( 'topersia i18n-gaps'" ) && false !== strpos( $x[0], "\$assoc['lang']" ) && false !== strpos( $x[0], "\$assoc['type']" ) && false !== strpos( $x[0], "'json' === \$format" )
			&& false !== strpos( $x[1], "if ( 'gaps' === \$what ) {" ) && false !== strpos( $x[1], 'topersia_i18n_gap_export( $tlang, $type )' )
			&& false !== strpos( tp2c_body( $x[0], 'topersia_i18n_gaps_card' ), "'1' === \$_GET['gaps']" ) && false !== strpos( tp2c_body( $x[0], 'topersia_i18n_gap_export' ), 'topersia_pack_tr_export(' );
	},
	array( $gap2c, $pack2c ), array( $gap2c, str_replace( "if ( 'gaps' === \$what ) {", "if ( false ) {", $pack2c ) ) );

/* ══ وصلۀ ۲ج و۲ — آمار حقیقی مستقل از زبان (P5-D306) ══════════════════ */

echo "\nوصلۀ ۲ج — و۲ آمار حقیقی  P5-D306\n" . str_repeat( '─', 78 ) . "\n";

tp441_mut( 'شمار گروه کارت آمار (count:provinces کشور و منطقه) روی گراف زبان پیش‌فرض: topersia_g_fact_count — پست اصل (topersia_g_default_id) در topersia_g_in_default_lang؛ گالری تجمیعی همان یاورها',
	function ( $x ) {
		$cell = tp2c_body( $x[0], 'topersia_g_stat_cell' );
		$fact = tp2c_body( $x[0], 'topersia_g_fact_count' );
		return false !== strpos( $cell, 'topersia_g_fact_count( $post_id, $group )' ) && false === strpos( $cell, 'topersia_g_group_ids(' )
			&& false !== strpos( $fact, 'topersia_g_default_id( $post_id )' ) && false !== strpos( $fact, 'topersia_g_in_default_lang( function () use ( $origin, $group_key )' )
			&& false !== strpos( tp2c_body( $x[1], 'topersia_g_gallery_agg_primary' ), 'return topersia_g_default_id( $post_id );' );
	},
	array( $zones2c, $agg2c ), array( str_replace( 'topersia_g_fact_count( $post_id, $group )', "count( topersia_g_group_ids( \$post_id, topersia_g_group( \$post_id, \$group ) ) )", $zones2c ), $agg2c ) );

tp441_mut( 'فقط کارت آمار: فهرست، اسلایدر و پنجرۀ استان‌ها همچنان صفحه‌های همان زبان (P5-D247) — topersia_g_fact_count فقط یک بار صدا زده می‌شود؛ ماتریس جز count:provinces شمار رابطه‌ای ندارد',
	function ( $s ) {
		return 1 === substr_count( $s, 'topersia_g_fact_count( $post_id, $group )' )
			&& 2 === preg_match_all( "/'count:[a-z_]+'/", $s, $m ) && array( "'count:provinces'" ) === array_values( array_unique( $m[0] ) );
	},
	$zones2c, $zones2c . "\n// topersia_g_fact_count( \$post_id, \$group )" );
/* ══ وصلۀ ۲ج و۳ — وضعیت راستی‌آزمایی هر زبان (P5-D308) ═════════════════ */

echo "\nوصلۀ ۲ج — و۳ وضعیت راستی‌آزمایی هر زبان  P5-D308\n" . str_repeat( '─', 78 ) . "\n";

/* اصل فارسی verified با تاریخ؛ ترجمۀ انگلیسی بی content_status ولی با تاریخ رونوشت‌شده (last_verified_date هنوز copy) */
$GLOBALS['tp_posts'][990803] = array( 'type' => 'tp_country', 'title' => 'کشور آزمون وضعیت' );
$GLOBALS['tp_posts'][990804] = array( 'type' => 'tp_country', 'title' => 'Status Test Country' );
$GLOBALS['tp_meta'][990803]  = array( 'content_status' => 'verified', 'last_verified_date' => '2026-09-20' );
$GLOBALS['tp_meta'][990804]  = array( 'last_verified_date' => '2026-09-20' );
$GLOBALS['tp_filters']['wpml_default_language'] = array( function () { return 'fa'; } );
$GLOBALS['tp_filters']['wpml_object_id']        = array( function ( $id ) { return 990804 === (int) $id ? 990803 : $id; } );
$src3 = function () {
	return array(
		'en' => tp442_lang( 'en', function () { return topersia_g_sc_sources( array( 'id' => 990804 ) ); } ),
		'fa' => topersia_g_sc_sources( array( 'id' => 990803 ) ),
	);
};
$v3_real = $src3();
$GLOBALS['tp_meta'][990804]['content_status'] = 'verified';   /* جهش: مقدار اصل روی ترجمه — همان رونوشت copy پیکربندی 1.12 */
$v3_mut  = $src3();
unset( $GLOBALS['tp_posts'][990803], $GLOBALS['tp_posts'][990804], $GLOBALS['tp_meta'][990803], $GLOBALS['tp_meta'][990804], $GLOBALS['tp_filters']['wpml_default_language'], $GLOBALS['tp_filters']['wpml_object_id'] );
tp441_mut( 'ترجمۀ بی content_status در /en/: «Needs verification» بی «Verified» و بی تاریخ راستی‌آزمایی اصل؛ اصل فارسی همان «راستی‌آزمایی‌شده» با تاریخ خودش',
	function ( $x ) {
		return false !== strpos( $x['en'], 'Needs verification' ) && false === strpos( $x['en'], 'Verified' ) && false === strpos( $x['en'], 'tp-uid__date' )
			&& false !== strpos( $x['fa'], 'راستی‌آزمایی‌شده' ) && false !== strpos( $x['fa'], 'datetime="2026-09-20"' );
	},
	$v3_real, $v3_mut );

tp441_mut( 'خواننده: خالی فقط در ترجمه (topersia_g_default_id ≠ خود پست) needs_verification می‌شود و تاریخ فقط کنار وضعیت خودِ همان زبان می‌آید',
	function ( $s ) {
		$b = tp2c_body( $s, 'topersia_g_sc_sources' );
		return false !== strpos( $b, "if ( ! \$own && topersia_g_default_id( \$pid ) !== (int) \$pid ) {\n\t\t\t\$status = 'needs_verification';" )
			&& false !== strpos( $b, "\$raw  = \$own ? trim( (string) topersia_g_raw( \$pid, 'last_verified_date' ) ) : '';" );
	},
	$zones2c, str_replace( "\$raw  = \$own ? trim( (string) topersia_g_raw( \$pid, 'last_verified_date' ) ) : '';", "\$raw  = trim( (string) topersia_g_raw( \$pid, 'last_verified_date' ) );", $zones2c ) );
