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

tp441_mut( 'خلیج فارس در هر زبان: عارضه با کلید پایدار (name:en · name:latin · name:fa)، نه موقعیت؛ هر دو سبک (آفلاین: فقط لایه‌های water؛ آنلاین: همهٔ نمادها) از gulfWrap؛ شاخهٔ جایگزین format؛ متن‌ها با escape یونیکد  P5-D364',
	function ( $x ) {
		return false !== strpos( $x, "[ '==', [ 'get', 'name:en' ], 'Persian Gulf' ]" ) && false !== strpos( $x, "[ '==', [ 'get', 'name:latin' ], 'Persian Gulf' ]" ) && false !== strpos( $x, "[ '==', [ 'get', 'name:fa' ], GULF_NAME.fa ]" )
			&& false !== strpos( $x, "[ 'format', GULF_NAME[ lang ] || GULF_NAME.en, {} ]," ) && false !== strpos( $x, "gulfWrap( [ 'coalesce', [ 'get', 'name:' + lang ], [ 'get', 'name' ] ], lang, true )" )
			&& false !== strpos( $x, "0 === ( layer.id || '' ).indexOf( 'water' )" ) && false !== strpos( $x, "ar: '\u0627\u0644\u062e\u0644\u064a\u062c \u0627\u0644\u0641\u0627\u0631\u0633\u064a'" );
	},
	$js2m, str_replace( "[ '==', [ 'get', 'name:en' ], 'Persian Gulf' ],", '', $js2m ) );

tp441_mut( 'برگزارکننده و وب‌سایت رسمی (کارت حقایق): شرط نمایش همان تابع‌های اسکیما با function_exists؛ هر کاشی فقط با مقدار؛ خودِ کاشی پیوند با rel=noopener و متن پیوند نام دامنه (نه <a> تودرتو)؛ پس از کاشی‌های تاریخ  P5-D361',
	function ( $x ) {
		return false !== strpos( $x, "function_exists( 'topersia_schema_event_organizer' )" ) && false !== strpos( $x, "function_exists( 'topersia_schema_event_official_url' )" )
			&& false !== strpos( $x, '$org  = topersia_schema_event_organizer( $pid );' ) && false !== strpos( $x, '$url = (string) topersia_schema_event_official_url( $pid );' )
			&& false !== strpos( $x, "'url' => \$url, 'attrs' => ' rel=\"noopener\"' )" ) && false !== strpos( $x, "if ( '' !== \$name ) {" ) && false !== strpos( $x, "if ( '' !== \$url ) {" )
			&& false !== strpos( $x, '$tiles = array_merge( $tiles, topersia_g_event_extra_tiles( $pid ) );' ) && false !== strpos( $x, "isset( \$tile['attrs'] ) ? (string) \$tile['attrs'] : ''" )
			&& false === strpos( $x, '<a class="tp-lat" dir="ltr" href="\' . esc_url( $url )' );
	},
	$zn2m, str_replace( "'url' => \$url, 'attrs' => ' rel=\"noopener\"' )", "'url' => \$url )", $zn2m ) );

tp441_mut( 'خط‌های مرز disputed پنهان در هر دو سبک: آفلاین فیلتر لایه‌های boundaries «بی disputed» (قدیمی‌شکل یا عبارت، بنا بر شکل فیلتر)؛ آنلاین لایهٔ فیلتر disputed == 1 پنهان؛ هر دو مسیر از hideDisputed  P5-D365',
	function ( $x ) {
		return false !== strpos( $x, 'function hideDisputed( layer )' ) && false !== strpos( $x, "'boundaries' === layer[ 'source-layer' ]" ) && false !== strpos( $x, "[ '!=', 'disputed', true ], [ '!=', 'disputed', 1 ]" )
			&& false !== strpos( $x, "[ '!', [ 'to-boolean', [ 'get', 'disputed' ] ] ]" ) && false !== strpos( $x, "'boundary' === layer[ 'source-layer' ]" ) && false !== strpos( $x, "{ visibility: 'none' }" )
			&& 2 === substr_count( $x, 'hideDisputed( ' ) - 1;
	},
	$js2m, str_replace( "[ '!=', 'disputed', true ], [ '!=', 'disputed', 1 ]", "[ '==', 'kind', 'x' ]", $js2m ) );

tp441_mut( 'محل برگزاری (S3): کاشی کنار «برگزارکننده» از venue_name همان پست (همان قاعدهٔ اسکیما: ترجمهٔ بی‌مقدار ← هیچ)، فقط با مقدار؛ برچسب در کاتالوگ  P5-D366',
	function ( $x ) {
		return false !== strpos( $x, "\$venue = get_post_meta( \$pid, 'venue_name', true );" ) && false !== strpos( $x, "__( 'محل برگزاری', 'topersia' )" ) && false !== strpos( $x, "if ( '' !== \$venue ) {" )
			&& strpos( $x, "__( 'محل برگزاری', 'topersia' )" ) < strpos( $x, "__( 'وب‌سایت رسمی', 'topersia' )" ) && strpos( $x, "__( 'برگزارکننده', 'topersia' )" ) < strpos( $x, "__( 'محل برگزاری', 'topersia' )" );
	},
	$zn2m, str_replace( "if ( '' !== \$venue ) {", "if ( true ) {", $zn2m ) );

$rn2m = file_get_contents( TP_PLUGIN_DIR . '/topersia-render.php' );

tp441_mut( 'حذف دارایی‌های بی‌مصرف صفحهٔ موجودیت (A1): دسته‌های jet-reviews · photoswipe · jet-engine-frontend · elementor-icons در سه قلاب (enqueue، wp_footer و print_footer_scripts)، فقط روی صفحهٔ تکی tp_، قابل‌خاموش با صافی  P5-D366',
	function ( $x ) {
		return false !== strpos( $x, "add_action( 'wp_enqueue_scripts', 'topersia_drop_unused_entity_assets', PHP_INT_MAX );" ) && false !== strpos( $x, "add_action( 'wp_footer', 'topersia_drop_unused_entity_assets', 1 );" )
			&& false !== strpos( $x, "add_action( 'wp_print_footer_scripts', 'topersia_drop_unused_entity_assets', 1 );" ) && false !== strpos( $x, "'topersia_drop_unused_assets'" ) && false !== strpos( $x, "'topersia_unused_entity_assets'" );
	},
	$core2m, str_replace( "add_action( 'wp_print_footer_scripts', 'topersia_drop_unused_entity_assets', 1 );", '', $core2m ) );

tp441_mut( 'سربرگ تمام‌عرض (A3): sizes صریح از topersia_g_hero_sizes() (پیش‌فرض 100vw، صافی‌پذیر) در <img>  P5-D366',
	function ( $x ) {
		return false !== strpos( $x, "'sizes'         => topersia_g_hero_sizes()," ) && false !== strpos( $x, "apply_filters( 'topersia_hero_sizes', '100vw' )" );
	},
	$zn2m, str_replace( "'sizes'         => topersia_g_hero_sizes(),", '', $zn2m ) );

tp441_mut( 'اندازه‌های میانی WebP (A3): فقط image/jpeg ← image/webp با wp_image_editor_supports؛ کیفیت فقط برای image/webp و برابر ۷۰ (پیش‌فرض ۸۲ سنگین‌تر از JPEG درمی‌آید)؛ هر دو قابل‌خاموش  P5-D366',
	function ( $x ) {
		return false !== strpos( $x, "'image/jpeg' !== \$mime_type" ) && false !== strpos( $x, "array( 'mime_type' => 'image/webp' )" ) && false !== strpos( $x, "\$formats['image/jpeg'] = 'image/webp';" )
			&& false !== strpos( $x, "'image/webp' === \$mime_type ? (int) apply_filters( 'topersia_webp_quality', 70 )" ) && false !== strpos( $x, "apply_filters( 'topersia_webp_subsizes', true )" );
	},
	$core2m, str_replace( "apply_filters( 'topersia_webp_quality', 70 )", "apply_filters( 'topersia_webp_quality', 82 )", $core2m ) );

tp441_mut( 'preload وزن متن قلم (A4): فقط یک وزن (۴۰۰، یا پروندهٔ قلم متغیر)، type=font/woff2 و crossorigin، نشانی از topersia_render_font_url، بی تنظیم قلم هیچ؛ در wp_head  P5-D366',
	function ( $x ) {
		return false !== strpos( $x, "'font_variable', 0 ) ? 'font_woff2' : 'font_w400'" ) && false !== strpos( $x, 'as="font" type="font/woff2"' ) && false !== strpos( $x, ' crossorigin data-tp-preload="font"' )
			&& false !== strpos( $x, "\$href = topersia_render_font_url( \$file );" ) && false !== strpos( $x, "add_action( 'wp_head', 'topersia_render_preload_font', 3 );" ) && 1 === substr_count( $x, 'data-tp-preload="font"' );
	},
	$rn2m, str_replace( ' crossorigin data-tp-preload="font"', ' data-tp-preload="font"', $rn2m ) );

tp441_mut( 'پیش‌بارگذاری اعضا (SC1 گام ۱): topersia_g_published ورودی و خروجی را با _prime_post_caches (فقط ناموجودها؛ بی term) پر می‌کند؛ پله‌های زنجیره با یک پرس‌وجوی IN کش خودِ JetEngine را پر می‌کنند؛ رابطهٔ تهیِ شناخته‌شده در همین درخواست دوباره پرسیده نمی‌شود، مگر کسی فیلتر JetEngine را بگیرد؛ ابطال با save_post · deleted_post  P5-D369',
	function ( $x ) {
		return false !== strpos( $x, '_prime_post_caches( $ids, false, true )' ) && false !== strpos( $x, "topersia_g_prime( \$out );" ) && false !== strpos( $x, "topersia_g_prime( \$ids );" )
			&& false !== strpos( $x, 'topersia_g_rel_prefetch( $cur, $spec );' ) && false !== strpos( $x, "'operator' => 'IN'" ) && false !== strpos( $x, "wp_cache_set( \$key, \$by[ \$one ], 'jet_engine_rel' )" )
			&& false !== strpos( $x, "has_filter( 'jet-engine/relations/get-children' )" ) && false !== strpos( $x, "add_action( 'save_post', 'topersia_g_rel_memo_reset' );" ) && false !== strpos( $x, 'add_shortcode( $tag, topersia_g_sc_scope( $callback ) );' ) && false !== strpos( $x, 'if ( 0 === topersia_g_sc_depth( 0 ) ) {' );
	},
	$zn2m, str_replace( "topersia_g_prime( \$out );\n", '', $zn2m ) );
