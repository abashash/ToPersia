<?php
/**
 * ادعاهای پس از پرامت یکپارچه (۱۳ اکتبر) — P5-D358 (بلوک AI) · P5-D359 (میزبان‌های بیرونی) · P5-D357 (تاریخ رویداد)
 * از assert.php پس از assert-2l.php بار می‌شود؛ PHP خالص، هر کدام با جهش. رفتار در مرورگر: tests/ext-hosts.js · tests/event-dates.js · tests/ai-admin-local.js.
 */

$core2m = file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-core/topersia-core.php' );
$zn2m   = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-zones.php' );
$map2m  = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-map.php' );
$js2m   = file_get_contents( TP_PLUGIN_DIR . '/assets/topersia-g-zones.js' );

echo "\nپس از پرامت یکپارچه — میزبان‌های بیرونی · تاریخ رویداد · بلوک AI  P5-D359 · P5-D357 · P5-D358\n" . str_repeat( '─', 78 ) . "\n";

tp441_mut( 'Google Fonts در صفحهٔ عمومی خاموش است: صافی المنتور print_google_fonts، برداشتن برچسب <link> هر شیوه‌نامه از fonts.googleapis/gstatic و نکتهٔ preconnect؛ پیشخوان و ویرایشگر دست‌نخورده؛ قابل‌خاموش با topersia_block_google_fonts  P5-D359',
	function ( $x ) {
		return false !== strpos( $x, 'function topersia_block_google_fonts()' ) && false !== strpos( $x, 'return ! is_admin() && (bool) apply_filters( \'topersia_block_google_fonts\', true );' )
			&& false !== strpos( $x, "add_filter( 'elementor/frontend/print_google_fonts', function ( \$print ) {
	return topersia_block_google_fonts() ? false : \$print;" ) && false !== strpos( $x, "add_filter( 'style_loader_tag'" )
			&& false !== strpos( $x, 'fonts\.(googleapis|gstatic)\.com' ) && false !== strpos( $x, "add_filter( 'wp_resource_hints'" ) && false !== strpos( $x, "'preconnect', 'dns-prefetch'" );
	},
	$core2m, str_replace( "add_filter( 'elementor/frontend/print_google_fonts', function ( \$print ) {\n\treturn topersia_block_google_fonts() ? false : \$print;", "add_filter( 'elementor/frontend/print_google_fonts', function ( \$print ) {\n\treturn \$print;", $core2m ) );

tp441_mut( 'تاریخ رویداد E1 · E3 · E5: سطر «بازه برگزاری» سربرگ فقط برای این سه نوع و فقط با تاریخ؛ از … تا … / فقط آغاز / فقط پایان؛ قالب از topersia_g_date_fa (زبان صفحه) داخل <time datetime>؛ بی تاریخ ← چیزی نمی‌آید  P5-D357',
	function ( $x ) {
		return false !== strpos( $x, "array( 'tp_festival', 'tp_sports_event', 'tp_religious_event' ), true ) ) {\n\t\t\treturn '';" )
			&& false !== strpos( $x, "topersia_g_date_html( topersia_g_raw( \$pid, 'event_start_date' ) )" ) && false !== strpos( $x, "topersia_g_date_html( topersia_g_raw( \$pid, 'event_end_date' ) )" )
			&& false !== strpos( $x, "if ( '' === \$from && '' === \$to ) {\n\t\t\treturn '';" ) && false !== strpos( $x, "esc_html__( 'از %1\$s تا %2\$s', 'topersia' )" ) && false !== strpos( $x, "esc_html__( 'تا %s', 'topersia' )" )
			&& false !== strpos( $x, '<time datetime="\' . esc_attr( $iso )' ) && false !== strpos( $x, '$out .= topersia_g_event_when( $pid );' );
	},
	$zn2m, str_replace( "if ( '' === \$from && '' === \$to ) {\n\t\t\treturn '';", "if ( false ) {\n\t\t\treturn '';", $zn2m ) );

tp441_mut( 'تاریخ رویداد در بلوک هویت: event_start_date و event_end_date کلید kind=date در ناحیهٔ card (کارت حقایق)؛ مقدار با topersia_g_date_html',
	function ( $x ) use ( $zn2m ) {
		return 1 === preg_match( "/'event_start_date'\s*=> array\( 'label' => 'تاریخ آغاز', 'unit' => '', 'kind' => 'date', 'zone' => 'card' \)/u", $x )
			&& 1 === preg_match( "/'event_end_date'\s*=> array\( 'label' => 'تاریخ پایان', 'unit' => '', 'kind' => 'date', 'zone' => 'card' \)/u", $x )
			&& false !== strpos( $zn2m, "case 'date':\n\t\t\t\t/* تاریخ رویداد: متن به زبان صفحه + <time datetime> ماشین‌خوان؛ نامعتبر ← خالی.  P5-D357 */\n\t\t\t\treturn topersia_g_date_html( \$raw );" );
	},
	$map2m, str_replace( "'kind' => 'date', 'zone' => 'card' ),\n\t\t'event_end_date'", "'kind' => 'text', 'zone' => 'card' ),\n\t\t'event_end_date'", $map2m ) );

tp441_mut( 'بلوک AI: در صفحه شاخهٔ تولید نیست — جاوااسکریپت هیچ tpas_ai_generate و هیچ fetch به admin-ajax برای AI نمی‌فرستد؛ بلوک از tpas_get_summary می‌خواند (در نبودش get_cached)  P5-D358',
	function ( $x ) use ( $zn2m ) {
		return false === strpos( $x, 'tpas_ai_generate' ) && false === strpos( $x, 'data-tp-ai-gen' ) && false === strpos( $x, 'data-tp-ai-ajax' )
			&& false !== strpos( $zn2m, "\$stable = function_exists( 'tpas_get_summary' );" );
	},
	$js2m, $js2m . "\nform.set( 'action', 'tpas_ai_generate' );" );
