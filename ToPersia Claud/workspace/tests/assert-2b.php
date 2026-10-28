<?php
/**
 * ادعاهای ایستای فاز ۲ب SPRINT-4.4 — بستۀ محتوا (P5-D272)، topersia-core ۴٫۱۲٫۰.
 * از assert.php پس از assert-442.php بار می‌شود؛ PHP خالص، بی وردپرس.
 *
 * رفتار واقعی (اسکیمای زندۀ جت‌انجین، رفت‌وبرگشت، اجرا و برگرداندن) در
 * tests/pack-local.php و مسیر پیشخوان در tests/pack-admin.js است؛ این‌جا فقط
 * قیدهایی که با خواندن کد ثابت می‌شوند — هر کدام با جهش (tp441_mut).
 */

$pack2b = dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-pack.php';
$core2b = dirname( TP_PLUGIN_DIR ) . '/topersia-core/topersia-core.php';
$src2b  = is_file( $pack2b ) ? file_get_contents( $pack2b ) : '';
$main2b = is_file( $core2b ) ? file_get_contents( $core2b ) : '';

echo "\nفاز ۲ب SPRINT-4.4 — بستۀ محتوا  P5-D272\n" . str_repeat( '─', 78 ) . "\n";

/** بدنۀ یک تابع — از امضا تا تابع بعدی در همان پرونده. */
function tp2b_body( $src, $name ) {
	if ( ! preg_match( '/^function ' . preg_quote( $name, '/' ) . '\(.*?(?=^function |\z)/ms', $src, $m ) ) {
		return '';
	}
	return $m[0];
}

tp441_mut( 'هسته بخش ۱۹ را بار می‌کند و فهرست بخش‌ها آن را دارد — includes/topersia-pack.php',
	function ( $s ) { return false !== strpos( $s, "require_once __DIR__ . '/includes/topersia-pack.php';" ) && false !== strpos( $s, '۱۹ — بستۀ محتوای هر نوع پست' ); },
	$main2b, str_replace( "require_once __DIR__ . '/includes/topersia-pack.php';", '', $main2b ) );

tp441_mut( 'هیچ جدول و هیچ نوع پست یا taxonomy تازه — نه CREATE TABLE، نه dbDelta، نه register_post_type/taxonomy  ۰-۱۲',
	function ( $s ) { return '' !== $s && ! preg_match( '/CREATE\s+TABLE|dbDelta\s*\(|register_post_type\s*\(|register_taxonomy\s*\(/i', $s ); },
	$src2b, $src2b . "\nregister_post_type( 'tp_pack', array() );" );

$apply2b = tp2b_body( $src2b, 'topersia_pack_apply' );
tp441_mut( 'پست تازه: تنها wp_insert_post بخش ۱۹ با post_status «draft» و content_status «needs_verification» — هیچ publish  P5-D247',
	function ( $b ) use ( $src2b ) {
		return 1 === preg_match_all( '/wp_insert_post\s*\(/', $b ) && false !== strpos( $b, "'post_status' => 'draft'" )
			&& false !== strpos( $b, "\$meta_input['content_status'] = 'needs_verification';" )
			&& ! preg_match( "/'publish'\s*\)|wp_publish_post|'post_status'\s*=>\s*'publish'/", $src2b );
	},
	$apply2b, str_replace( "'post_status' => 'draft'", "'post_status' => 'publish'", $apply2b ) );

tp441_mut( 'نامک و entity_uid پست موجود هرگز بازنویسی نمی‌شوند — به‌روزرسانی post_name نمی‌نویسد و entity_uid بیرون از بسته است  بخش ۱۴',
	function ( $s ) {
		$apply = tp2b_body( $s, 'topersia_pack_apply' );
		return ! preg_match( "/wp_update_post\(\s*array\([^)]*'post_name'/", $apply ) && false !== strpos( tp2b_body( $s, 'topersia_pack_excluded' ), "'entity_uid'" );
	},
	$src2b, str_replace( "wp_update_post( array( 'ID' => \$post_id, 'post_title' => \$plan['title']['value'] ) );", "wp_update_post( array( 'ID' => \$post_id, 'post_name' => \$plan['slug'] ) );", $src2b ) );

$log2b = tp2b_body( $src2b, 'topersia_pack_log_prev' );
tp441_mut( 'ردیف برگشت مستقیم در جدول لاگ، کانال import — بی topersia_log() و بی تنظیم لاگ (خاموشی، آستانه، سقف پنجاه)',
	function ( $b ) { return false !== strpos( $b, '$wpdb->insert(' ) && false !== strpos( $b, "'channel'    => 'import'" ) && ! preg_match( '/topersia_log\s*\(|topersia_setting\s*\(/', $b ); },
	$log2b, str_replace( '$ok = $wpdb->insert(', "if ( ! topersia_setting( 'log_enabled' ) ) { return 0; }\n\t\$ok = \$wpdb->insert(", $log2b ) );

tp441_mut( 'مقدار پیشین پیش از نوشتن ثبت می‌شود — در به‌روزرسانی topersia_pack_log_prev پیش از نخستین update_post_meta',
	function ( $b ) { $l = strpos( $b, "topersia_pack_log_prev( \$run, \$post_id, 'update'" ); $w = strpos( $b, 'update_post_meta( $post_id, $key' ); return false !== $l && false !== $w && $l < $w; },
	$apply2b, preg_replace( "/(\t\ttopersia_pack_log_prev\( \\\$run, \\\$post_id, 'update', \\\$prev \);\n)(.*?)(\t\tforeach \( \\\$writes as \\\$key => \\\$value \) \{\n\t\t\tupdate_post_meta\( \\\$post_id, \\\$key, wp_slash\( \\\$value \) \);\n\t\t\})/s", '$3$2$1', $apply2b ) );

$prune2b = tp2b_body( $main2b, 'topersia_log_prune' );
tp441_mut( 'هرس لاگ ردیف‌های آخرین درون‌ریزی را نگه می‌دارد — هر دو DELETE با topersia_pack_log_keep_sql()؛ «پاک‌کردن لاگ» هم',
	function ( $x ) { return 2 === substr_count( $x[0], '. $keep );' ) && false !== strpos( $x[0], 'topersia_pack_log_keep_sql()' ) && false !== strpos( $x[1], '"DELETE FROM {$table} WHERE 1=1" . $keep' ); },
	array( $prune2b, $main2b ), array( str_replace( ' . $keep );', ' );', $prune2b ), $main2b ) );

tp441_mut( 'هر نقطۀ AJAX بخش ۱۹ گارد دارد (manage_options و نانس) و برون‌بری current_user_can + check_admin_referer',
	function ( $s ) {
		foreach ( array( 'topersia_pack_ajax_plan', 'topersia_pack_ajax_run', 'topersia_pack_ajax_undo' ) as $fn ) {
			if ( false === strpos( tp2b_body( $s, $fn ), 'topersia_pack_ajax_guard();' ) ) { return false; }
		}
		$g = tp2b_body( $s, 'topersia_pack_ajax_guard' );
		$d = tp2b_body( $s, 'topersia_pack_download' );
		return false !== strpos( $g, "current_user_can( 'manage_options' )" ) && false !== strpos( $g, "check_ajax_referer( 'topersia_pack'" )
			&& false !== strpos( $d, "current_user_can( 'manage_options' )" ) && false !== strpos( $d, "check_admin_referer( 'topersia_pack' )" )
			&& ! preg_match( '/wp_ajax_nopriv_topersia_pack/', $s );
	},
	$src2b, str_replace( "function topersia_pack_ajax_undo() {\n\n\ttopersia_pack_ajax_guard();", "function topersia_pack_ajax_undo() {\n", $src2b ) );

tp441_mut( 'JSON Schema و سنجش درون‌ریزی از یک منبع — topersia_pack_json_schema و topersia_pack_plan_post هر دو از topersia_pack_schema()',
	function ( $s ) { return false !== strpos( tp2b_body( $s, 'topersia_pack_json_schema' ), 'topersia_pack_schema( $post_type )' ) && false !== strpos( tp2b_body( $s, 'topersia_pack_plan_post' ), 'topersia_pack_schema( $type )' ); },
	$src2b, str_replace( '$schema = topersia_pack_schema( $post_type );' . "\n\t\$langs", '$schema = array();' . "\n\t\$langs", $src2b ) );

tp441_mut( 'اسکیمای بسته از جت‌انجینِ زنده — topersia_diag_meta_boxes و get_active_relations؛ هیچ فهرست نام فیلد در کد جز بیرون‌مانده‌های بسته',
	function ( $s ) {
		$schema = tp2b_body( $s, 'topersia_pack_schema' );
		return false !== strpos( $schema, 'topersia_diag_meta_boxes()' ) && false !== strpos( tp2b_body( $s, 'topersia_pack_relations' ), 'get_active_relations()' )
			&& ! preg_match( "/'(population|area_km2|overview_text|name_en)'/", $schema );
	},
	$src2b, str_replace( 'foreach ( topersia_diag_meta_boxes() as $box ) {' . "\n\n\t\t\$args = isset( \$box['args'] )", "\$fields['population'] = array();\n\tforeach ( topersia_diag_meta_boxes() as \$box ) {\n\n\t\t\$args = isset( \$box['args'] )", $src2b ) );

tp441_mut( 'کمینۀ طول هر نوع با منبع CA — همۀ چهل‌ویک نوع دارای صفحه عدد و سند دارند (tp_county گراف‌فقط است)',
	function ( $s ) {
		$rules = tp2b_body( $s, 'topersia_pack_length_rules' );
		preg_match_all( "/'(tp_[a-z_]+)'\s*=>\s*array\(\s*(\d+),\s*'(CA-[^']+)'\s*\)/u", $rules, $m );
		return 41 === count( array_unique( $m[1] ) ) && ! in_array( 'tp_county', $m[1], true ) && ! array_filter( $m[2], function ( $n ) { return (int) $n < 200; } );
	},
	$src2b, preg_replace( "/\t\t'tp_route'\s*=>\s*array\( 500, 'CA-P P9' \),\n/", '', $src2b ) );

tp441_mut( 'دستور ثابت به سه زبان محتوا با شش قاعدۀ SPRINT-4.4 ۲ب٫۱ — منبع، خالی، کلید واژه‌نامه، رابطه با نام و نوع، بی نشانی تصویر، لحن و کمینه',
	function ( $s ) {
		$b = tp2b_body( $s, 'topersia_pack_instructions' );
		return false !== strpos( $b, 'source_references' ) && false !== strpos( $b, 'خالی بگذار' ) && false !== strpos( $b, 'کلید، نه برچسب' )
			&& false !== strpos( $b, '"name": "…", "type": "…"' ) && false !== strpos( $b, 'هیچ نشانی تصویر' ) && false !== strpos( $b, 'نه تبلیغاتی' )
			&& false !== strpos( $b, "'en' => \$en" ) && false !== strpos( $b, "'ar' => \$ar" );
	},
	$src2b, str_replace( 'هیچ نشانی تصویر، ویدیو یا پرونده‌ای ننویس', 'نشانی تصویر را بنویس', $src2b ) );

/* دستهٔ 32 بند 8 · P5-D456 — درون‌ریز یال والد-یگانه/یک‌به‌یکِ پست دیگر را جابه‌جا نمی‌کند: مقصد دارای والد دیگر رد می‌شود (وضعیت type) */
tp441_mut( 'درون‌ریز: مقصدِ فرزندِ والد-یگانه که والد دیگری دارد رد می‌شود، نه جابه‌جا — جهش: بی نگهبان می‌افتد  P5-D456',
	function ( $s ) {
		$b = tp2b_body( $s, 'topersia_pack_plan' );
		if ( '' === $b ) { $b = $s; }
		return false !== strpos( $s, 'P5-D456' ) && false !== strpos( $s, "is_single_parent() ) {\n\t\t\t\t\t\$owners = array_map( 'absint', (array) \$single->get_parents( \$res['target'], 'ids' ) );" )
			&& false !== strpos( $s, 'این فرزند والد دیگری دارد' );
	},
	$src2b, str_replace( 'این فرزند والد دیگری دارد', 'x', $src2b ) );
