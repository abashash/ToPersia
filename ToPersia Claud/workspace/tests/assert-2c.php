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

tp441_mut( 'ساختن همیشه به زبان اصلی WPML — بازسازی و فهرست «همه» در topersia_g_gallery_agg_in_default_lang؛ زبان پیشین در finally برمی‌گردد',
	function ( $x ) {
		return false !== strpos( $x[0], 'return topersia_g_gallery_agg_in_default_lang( function () use ( $post_id, $cascade ) {' )
			&& false !== strpos( $x[1], 'return topersia_g_gallery_agg_in_default_lang( function () {' )
			&& (bool) preg_match( "/finally \{\s*do_action\( 'wpml_switch_language', \\\$current \);/", $x[2] );
	},
	array( $rebuild2c, tp2c_body( $agg2c, 'topersia_g_gallery_agg_all_ids' ), tp2c_body( $agg2c, 'topersia_g_gallery_agg_in_default_lang' ) ),
	array( str_replace( 'return topersia_g_gallery_agg_in_default_lang( function () use ( $post_id, $cascade ) {', 'return ( function () use ( $post_id, $cascade ) {', $rebuild2c ), tp2c_body( $agg2c, 'topersia_g_gallery_agg_all_ids' ), tp2c_body( $agg2c, 'topersia_g_gallery_agg_in_default_lang' ) ) );

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
