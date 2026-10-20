<?php
/**
 * برآورد توکن هر تولید خلاصه‌ساز — P5-D382 (فقط خواندنی؛ فقط topersia.local؛ هیچ فراخوانی موتور)
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/ai-cost.php
 *
 * برای نمونه‌ای از پست‌های واقعی (استان، شهر، جاذبه)، همان پیامی ساخته می‌شود که `TP_AI_Summary::produce()` به موتور
 * می‌فرستد (system + پرامت + بافت صفحه)، برای هر سه نوع، و نویسه‌ها شمرده می‌شوند. توکن ≈ نویسه ÷ ۳ برای متن فارسی
 * (برآورد محافظه‌کارانهٔ رایج؛ توکن‌ساز هر موتور فرق دارد) و ÷ ۴ برای لاتین. خروجی: سقف max_tokens تنظیم موتور.
 */
if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) { fwrite( STDERR, "فقط نسخهٔ محلی\n" ); exit( 2 ); }

$ctx = new ReflectionMethod( 'TP_AI_Summary', 'page_context' );
$ctx->setAccessible( true );
$tok = function ( $s ) {
	$s   = (string) $s;
	$all = function_exists( 'mb_strlen' ) ? mb_strlen( $s, 'UTF-8' ) : strlen( $s );
	$fa  = preg_match_all( '/[\x{0600}-\x{06FF}]/u', $s );
	return (int) ceil( $fa / 3 + ( $all - $fa ) / 4 );
};
$rows = array();
foreach ( array( 'tp_province', 'tp_city', 'tp_monument' ) as $type ) {
	foreach ( get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => 3, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) ) as $pid ) {
		$lang = TP_AI_Localize::for_post( $pid );
		$url  = TP_AI_Summary::post_url( $pid, $lang );
		$sys  = TP_AI_Localize::context_system( $lang );
		$page = (string) $ctx->invoke( null, $pid, $url );
		foreach ( TP_AI_Options::panel_types() as $t ) {
			$user = TP_AI_Render::build_prompt( TP_AI_Prompt_Manager::get( $lang, $t ), $url, TP_AI_Render::current_title( $pid ), $pid, $lang ) . "\n\n" . $page;
			$rows[] = array( 'type' => $type, 'post' => $pid, 'out' => $t, 'in_tokens' => $tok( $sys ) + $tok( $user ), 'chars' => mb_strlen( $sys . $user, 'UTF-8' ) );
		}
	}
}
$eng = TP_AI_Options::get_field( 'internal_engines', array() );
$max = array();
foreach ( (array) $eng as $e ) { $max[] = ( $e['provider'] ?? '' ) . ':' . ( $e['model'] ?? '' ); }
echo wp_json_encode( array( 'rows' => $rows, 'engines' => $max, 'max_tokens' => TP_AI_Options::get_field( 'max_tokens', null ), 'temperature' => TP_AI_Options::get_field( 'temperature', null ) ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
