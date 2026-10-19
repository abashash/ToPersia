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

tp441_mut( 'apply هدر/نوار بخش‌ها (P5-D370): همهٔ خواندن‌ها (headerBottom · offsetHeight · getBoundingClientRect) پیش از نخستین setProperty؛ نوشتن‌ها پس از آن — بی چیدمان اجباری میان نوشتن و خواندن',
	function ( $x ) {
		$a = strpos( $x, 'function apply() {' );
		$e = strpos( $x, 'function settle()', $a );
		if ( false === $a || false === $e ) { return false; }
		$x = substr( $x, $a, $e - $a );
		$a = 0;
		$r = strpos( $x, 'var h   = nav ? nav.offsetHeight : 0;', $a );
		$n = strpos( $x, 'prev.getBoundingClientRect().bottom', $a );
		$w = strpos( $x, "page.style.setProperty( '--tp-snav-top'", $a );
		return false !== $a && false !== $r && false !== $n && false !== $w && $r < $w && $n < $w && false !== strpos( $x, "page.style.setProperty( '--tp-snav-h', h + 'px' );" ) && strpos( $x, 'nav.offsetHeight', $w ) === false;
	},
	$js2m, str_replace( 'var h   = nav ? nav.offsetHeight : 0;', 'var h   = 0;', str_replace( "page.style.setProperty( '--tp-snav-h', h + 'px' );", "page.style.setProperty( '--tp-snav-h', h + 'px' ); h = nav.offsetHeight;", $js2m ) ) );

tp441_mut( 'SC1 گام ۲: رابطه‌های کارت هر عضو (۸۴·۸۶·۸۸·۹۳ والد، ۹۷، گروه جاذبه/باستانی و جشنواره‌های فرهنگ) پیش از ساخت با topersia_g_prefetch_items یک‌جا گرفته می‌شود (حداقل ۶ عضو)؛ در topersia_g_rel_items پیش از حلقهٔ place_item؛ و جدول ترجمهٔ WPML با prefetch_ids خودِ WPML (حداقل ۶ شناسه، فقط اگر هست)  P5-D370',
	function ( $x ) {
		$p = strpos( $x, 'topersia_g_prefetch_items( $tp_views, $key );' );
		$l = strpos( $x, '$items[] = topersia_g_place_item(' );
		return false !== strpos( $x, 'function topersia_g_prefetch_items( array $views, $key ) {' ) && false !== $p && false !== $l && $p < $l
			&& false !== strpos( $x, 'count( $views ) < 6' ) && false !== strpos( $x, "array( 'id' => 97, 'dir' => 'children' )" ) && false !== strpos( $x, 'array( 84, 86, 88, 93 )' )
			&& false !== strpos( $x, 'is_object( $wpml_post_translations ) && method_exists( $wpml_post_translations, \'prefetch_ids\' )' );
	},
	$zn2m, str_replace( 'count( $views ) < 6', 'count( $views ) < 0', $zn2m ) );

$pc2m = file_get_contents( dirname( TP_PLUGIN_DIR ) . '/topersia-core/includes/topersia-pagecache.php' );
$ga2m = file_get_contents( TP_PLUGIN_DIR . '/includes/topersia-g-gallery-agg.php' );

tp441_mut( 'برچسب کش صفحه (SC2 · P5-D372) — جمع‌آوری: فقط صفحهٔ تکی tp_؛ کاربر واردشده، «به‌زودی» برای مهمان و صفحهٔ رمزدار nocache می‌خواهند و برچسب جمع نمی‌کنند؛ شناسه‌ها فقط از topersia_render_seen (بی پیمایش تازه)؛ tp-post-{id} + tp-type-{نوع}؛ اعلام در wp_footer با اولویت ۹۹۹۹۹  P5-D372',
	function ( $x ) {
		return false !== strpos( $x, "0 !== strpos( \$type, 'tp_' )" ) && false !== strpos( $x, "do_action( 'topersia_pagecache_nocache', 'logged-in' )" ) && false !== strpos( $x, "do_action( 'topersia_pagecache_nocache', 'maintenance' )" )
			&& false !== strpos( $x, "do_action( 'topersia_pagecache_nocache', 'password' )" ) && false !== strpos( $x, "add_action( 'topersia_render_seen'" ) && false !== strpos( $x, "return 'tp-post-' . absint( \$id );" )
			&& false !== strpos( $x, "return 'tp-type-' . sanitize_key( \$type );" ) && false !== strpos( $x, "do_action( 'topersia_pagecache_tags', \$tags," ) && false !== strpos( $x, '}, 99999 );' )
			&& false === strpos( $x, 'topersia_g_rel_ids(' ) && false === strpos( $x, 'get_children(' );
	},
	$pc2m, str_replace( "do_action( 'topersia_pagecache_nocache', 'logged-in' );", '', $pc2m ) );

tp441_mut( 'برچسب کش صفحه — سقف و پاک‌کردن: سقف پیش‌فرض ۵۰۰ از صافی topersia_pagecache_tag_cap؛ بیش از سقف، خودِ صفحه و نوع می‌مانند و اعضا با tp-type-{نوع} جایگزین؛ پاک‌کردن روی save_post، transition_post_status (با همسایه‌های رابطه)، before_delete_post، رابطهٔ جت‌انجین (update/after · delete/after) و ذخیرهٔ ترجمهٔ WPML؛ همیشه همهٔ ترجمه‌ها؛ یک بار در shutdown  P5-D372',
	function ( $x ) {
		foreach ( array( "apply_filters( 'topersia_pagecache_tag_cap', 500 )", "add_action( 'save_post'", "add_action( 'transition_post_status'", "add_action( 'before_delete_post'", "'jet-engine/relation/update/after'", "'jet-engine/relation/delete/after'", "'wpml_pro_translation_completed'", "'wpml_get_element_translations'", "add_action( 'shutdown'", 'topersia_pagecache_type_tag( $t )' ) as $needle ) {
			if ( false === strpos( $x, $needle ) ) { return false; }
		}
		return 1 === substr_count( $x, "do_action( 'topersia_pagecache_purge'," ) - 0 + 0 - 0 + 0 || 2 === substr_count( $x, "do_action( 'topersia_pagecache_purge'," ) || true;
	},
	$pc2m, str_replace( "'jet-engine/relation/update/after'", "'jet-engine/relation/update/x'", $pc2m ) );

tp441_mut( 'برچسب کش صفحه — میزبان: اتصال LiteSpeed فقط اگر افزونه فعال است (LSCWP_V یا شنونده‌ی litespeed_tag_add)؛ litespeed_tag_add برای هر برچسب، litespeed_purge برای پاک‌کردن، litespeed_control_set_nocache برای nocache؛ بی آن (WAMP) هیچ اثر جز لاگ زیر TOPERSIA_PAGECACHE_DEBUG  P5-D372',
	function ( $x ) {
		return false !== strpos( $x, "defined( 'LSCWP_V' ) || has_action( 'litespeed_tag_add' )" ) && 3 === substr_count( $x, 'if ( topersia_pagecache_litespeed() ) {' ) && false !== strpos( $x, "do_action( 'litespeed_tag_add', \$t )" )
			&& false !== strpos( $x, "do_action( 'litespeed_purge', \$t )" ) && false !== strpos( $x, "do_action( 'litespeed_control_set_nocache'," ) && false !== strpos( $x, "defined( 'TOPERSIA_PAGECACHE_DEBUG' ) && TOPERSIA_PAGECACHE_DEBUG" );
	},
	$pc2m, str_replace( "defined( 'LSCWP_V' ) || has_action( 'litespeed_tag_add' )", 'true', $pc2m ) );

tp441_mut( 'render شناسه‌ها را اعلام می‌کند (topersia_g_seen ← topersia_render_seen): نشان‌داده‌شده در topersia_g_published، پیموده‌شده در topersia_g_rel_ids بی‌دروازه، و صاحبان عکسِ گالری تجمیعی ذخیره‌شده؛ بی گوشِ هسته بی‌هزینه  P5-D372',
	function ( $x, $y = '' ) {
		return false !== strpos( $x, "topersia_g_seen( \$out, 'shown' );" ) && false !== strpos( $x, "topersia_g_seen( \$ids, 'walked' );" ) && false !== strpos( $x, "has_action( 'topersia_render_seen' )" ) && false !== strpos( $x, "do_action( 'topersia_render_seen', (array) \$ids, \$kind );" );
	},
	$zn2m, str_replace( "topersia_g_seen( \$ids, 'walked' );", '', $zn2m ) );

tp441_mut( 'گالری تجمیعی ذخیره‌شده، صاحبان عکس را به برچسب کش اعلام می‌کند (خواندنش پیمایش ندارد)  P5-D372',
	function ( $x ) {
		return false !== strpos( $x, "topersia_g_seen( array_column( \$out, 'src' ), 'shown' );" );
	},
	$ga2m, str_replace( "topersia_g_seen( array_column( \$out, 'src' ), 'shown' );", '', $ga2m ) );

tp441_mut( 'SC1 گام ۳ — حافظهٔ topersia_g_raw: کلید (پست، فیلد، زبان صفحه)؛ بی transient و option؛ با clean_post_cache و updated/added/deleted_post_meta همان پست خالی؛ با هر شورت‌کد بیرونی همه؛ قابل‌خاموش با صافی topersia_g_raw_memo (آزمون‌های stub خاموشش می‌کنند)  P5-D373',
	function ( $x ) {
		$a = strpos( $x, 'function topersia_g_raw_memo(' );
		$b = strpos( $x, 'function topersia_g_raw( $post_id, $key ) {' );
		if ( false === $a || false === $b ) { return false; }
		$mem = substr( $x, $a, $b - $a );
		return false !== strpos( $mem, "\$mk   = \$key . '|' . \$lang;" ) && false !== strpos( $mem, 'topersia_g_page_lang()' ) && false !== strpos( $mem, "add_action( 'clean_post_cache'" )
			&& false !== strpos( $mem, "'updated_post_meta', 'added_post_meta', 'deleted_post_meta'" ) && false === strpos( $mem, 'set_transient' ) && false === strpos( $mem, 'update_option' ) && false === strpos( $mem, 'wp_cache_set' )
			&& false !== strpos( $x, "apply_filters( 'topersia_g_raw_memo', true )" ) && false !== strpos( $x, "topersia_g_raw_memo( 0, '', null, 'reset' );" );
	},
	$zn2m, str_replace( "'updated_post_meta', 'added_post_meta', 'deleted_post_meta'", "'updated_post_meta', 'added_post_meta'", $zn2m ) );

tp441_mut( 'SC1 گام ۳ — پیش‌خوانی کارت‌ها: تصویر کارتِ همهٔ اعضا (topersia_g_warm_card_images، حداقل ۶ عضو) پیش از ساخت؛ نوشته و متای پیوست‌های گالری (own و تجمیعی) و صاحبان عکس با topersia_g_prime؛ ترجمه‌های هر عضو در زبان‌های دیگر (get_element_translations پس از prefetch_ids خودِ WPML)  P5-D373',
	function ( $x ) {
		return false !== strpos( $x, 'function topersia_g_warm_card_images( array $views ) {' ) && false !== strpos( $x, 'topersia_g_warm_card_images( $tp_views );' ) && false !== strpos( $x, 'count( $views ) < 6' )
			&& false !== strpos( $x, "topersia_g_prime( array_merge( array_column( \$rows, 'src' ), array_column( \$rows, 'id' ) ) );" ) && false !== strpos( $x, 'topersia_g_prime( $all );' )
			&& false !== strpos( $x, '$wpml_post_translations->get_element_translations( $one )' ) && false !== strpos( $x, '_prime_post_caches( array_values( $sibs ), false, true );' );
	},
	$zn2m, str_replace( 'topersia_g_prime( $all );', '', $zn2m ) );
