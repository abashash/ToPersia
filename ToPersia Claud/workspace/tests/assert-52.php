<?php
/**
 * دستۀ 52 — از assert.php بار می‌شود؛ خواندن کد و رفتار تابع‌های خالص، هر ادعا با جهش. رفتار روی وردپرس واقعی: tests/w52-local.js.
 *
 * بندها: ۱ تا ۱۰ صفحۀ اول (P5-D562 تا P5-D565 · D569 · D570)، ۱۱ مقاله (P5-D567)، ۱۲ هاب بی ردیف فیلتر (P5-D566)،
 * ۱۳ مسیر سبک فیلتر اخبار (P5-D571)، ۱۴ تا ۱۸ صفحۀ تکی (P5-D568).
 */

echo "\nدستۀ 52 — ردیف‌های صفحۀ اول، فصل، مقاله، هاب بی فیلتر، مسیر سبک، صفحۀ تکی\n" . str_repeat( '─', 78 ) . "\n";

$w52_root = dirname( __DIR__ );
$w52_read = function ( $p ) use ( $w52_root ) { return (string) @file_get_contents( $w52_root . '/' . $p ); };
$w52_home = $w52_read( 'plugin/topersia-render/includes/topersia-g-home.php' );
$w52_row  = $w52_read( 'plugin/topersia-render/includes/topersia-g-home-row.php' );
$w52_hcss = $w52_read( 'plugin/topersia-render/assets/topersia-g-home.css' );
$w52_hub  = $w52_read( 'plugin/topersia-render/includes/topersia-g-hub.php' );
$w52_htpl = $w52_read( 'plugin/topersia-render/templates/hub-archive.php' );
$w52_news = $w52_read( 'plugin/topersia-render/includes/topersia-g-news.php' );
$w52_nui  = $w52_read( 'plugin/topersia-render/includes/topersia-g-news-ui.php' );
$w52_art  = $w52_read( 'plugin/topersia-render/includes/topersia-g-article.php' );
$w52_atpl = $w52_read( 'plugin/topersia-render/templates/article-archive.php' );
$w52_stpl = $w52_read( 'plugin/topersia-render/templates/news-single.php' );
$w52_ncss = $w52_read( 'plugin/topersia-render/assets/topersia-g-news.css' );
$w52_njs  = $w52_read( 'plugin/topersia-render/assets/topersia-g-news.js' );
$w52_frag = $w52_read( 'plugin/topersia-render/fragment.php' );
$w52_fkey = $w52_read( 'plugin/topersia-render/includes/topersia-frag-key.php' );
$w52_sch  = $w52_read( 'plugin/topersia-schema/topersia-schema.php' );
$w52_tags = $w52_read( 'plugin/topersia-render/includes/topersia-g-tags.php' );

/* ── بند ۱ و ۷: ردیف‌ها با واحد ستون برابر و span ─────────────────────── */
$w52_rows = function ( $s ) {
	return false !== strpos( $s, "'<div class=\"tp-home-trio tp-home-duo tp-home-lazy\">' . \$duo . '</div>'" )
		&& strpos( $s, "topersia_g_home_section( 'tp-home-exp'" ) < strpos( $s, "topersia_g_home_section( 'tp-home-cities'" )          /* تجربه‌ها نخست (آغاز سطر) */
		&& false !== strpos( $s, "'<div class=\"tp-home-trio tp-home-mag tp-home-lazy\">' . \$mag . '</div>'" )
		&& strpos( $s, "topersia_g_home_section( 'tp-home-gallery'" ) < strpos( $s, "topersia_g_home_section( 'tp-home-news'" )
		&& strpos( $s, "topersia_g_home_section( 'tp-home-news'" ) < strpos( $s, 'topersia_g_home_articles_col()' );
};
tp441_mut( 'بند ۱ و ۷: ردیف [تجربه‌ها | مقاصد] و [تصویر | اخبار | مقالات] به همین ترتیب — جهش: مقاصد پیش از تجربه‌ها', $w52_rows, $w52_home,
	str_replace( array( "'tp-home-exp', __( 'کارها", "'tp-home-cities', __( 'محبوب" ), array( "'tp-home-cities', __( 'کارها", "'tp-home-exp', __( 'محبوب" ), $w52_home ) );
$w52_span = function ( $c ) {
	return false !== strpos( $c, '.tp-home-duo > #tp-home-cities{grid-column:span 2}' )                                               /* ۱+۲ */
		&& false !== strpos( $c, '.tp-home-row3 > .tp-home-col{grid-column:span 2}' ) && false !== strpos( $c, '.tp-home-row3 > #tp-home-seasons{grid-column:span 3}' )   /* ۱ : ۱ : ۱٫۵ */
		&& false !== strpos( $c, '.tp-home-mag > #tp-home-gallery{grid-column:span 2}' )                                              /* ۲+۱+۱ */
		&& false !== strpos( $c, '.tp-home-trio{display:flex;flex-direction:column;align-items:stretch}' );                           /* زیر ۹۰۰ عمودی */
};
tp441_mut( 'بند ۱، ۳ و ۷ CSS: نسبت ۱+۲ · ۱:۱:۱٫۵ · ۲+۱+۱ با span، زیر ۹۰۰px عمودی — جهش: فصل هم‌پهنا', $w52_span, $w52_hcss,
	str_replace( '#tp-home-seasons{grid-column:span 3}', '#tp-home-seasons{grid-column:span 2}', $w52_hcss ) );

/* ── بند ۲: هشت تجربه، دو ردیف چهارتایی، آستانه یک ─────────────────────── */
$w52_exp = function ( $s ) use ( $w52_row, $w52_tags, $w52_hcss ) {
	return false !== strpos( $s, 'topersia_g_tag_experiences() : array(), 0, TOPERSIA_HOME_EXP_MAX ) as $e' )
		&& false !== strpos( $w52_row, "define( 'TOPERSIA_HOME_EXP_MAX', 8 );" ) && false !== strpos( $w52_tags, "define( 'TOPERSIA_TAG_MIN_ITEMS', 1 );" )
		&& false !== strpos( $w52_hcss, '.tp-home-exp{grid-template-columns:repeat(4,minmax(0,1fr));grid-auto-rows:minmax(0,1fr);' )
		&& (bool) preg_match( '/@media \(max-width:600px\)\{\s*html \.tp-page\.tp-home \.tp-home-exp\{grid-template-columns:repeat\(2,minmax\(0,1fr\)\)\}/', $w52_hcss );
};
tp441_mut( 'بند ۲: تا ۸ کارت تجربه، ۴ ستون (موبایل ۲)، آستانه ۱ مورد — جهش: بی سقف', $w52_exp, $w52_home,
	str_replace( ', 0, TOPERSIA_HOME_EXP_MAX ) as $e', ', 0 ) as $e', $w52_home ) );

/* ── بند ۳: سه مورد با بندانگشتی ─────────────────────────────────────── */
$w52_thumb = function ( $s ) {
	return false !== strpos( $s, "define( 'TOPERSIA_HOME_ROW_ITEMS', 3 );" )
		&& false !== strpos( $s, '$att   = topersia_g_card_image( $id );' )                                                           /* عکس خود مورد */
		&& false !== strpos( $s, 'foreach ( (array) topersia_g_crumb_chain( $id ) as $place ) {' )                                    /* سپس مکان مرتبط */
		&& false !== strpos( $s, "( '' !== \$img ? \$img : topersia_g_icon( \$icon ) )" )                                             /* سپس آیکون */
		&& 0 === preg_match( '#https?://#', (string) substr( $s, (int) strpos( $s, 'function topersia_g_home_thumb' ), 900 ) );        /* هرگز نشانی بیرونی */
};
tp441_mut( 'بند ۳: هر فهرست ۳ مورد، بندانگشتی = عکس مورد ← مکان مرتبط ← آیکون — جهش: بی مکان مرتبط', $w52_thumb, $w52_row,
	str_replace( 'foreach ( (array) topersia_g_crumb_chain( $id ) as $place ) {', 'foreach ( array() as $place ) {', $w52_row ) );

/* ── بند ۴: چهار فصل — ترتیب و چهار SVG خود افزونه ─────────────────────── */
$w52_svg = true;
foreach ( array( 'spring', 'summer', 'autumn', 'winter' ) as $w52_s ) {
	$w52_f   = $w52_root . '/plugin/topersia-render/assets/home/seasons/' . $w52_s . '.svg';
	$w52_x   = (string) @file_get_contents( $w52_f );
	$w52_svg = $w52_svg && '' !== $w52_x && strlen( $w52_x ) <= 20 * 1024
		&& false !== strpos( $w52_x, 'class="tp-home-season__art"' ) && false !== strpos( $w52_x, 'class="tp-ssn-sky"' )
		&& 0 === preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|<image|href=|url\(|<script|fill="(?!none)/i', $w52_x )                        /* بی رنگ خام، بی عکس و پیوند بیرونی */
		&& 0 === preg_match_all( '/class="(?!tp-)/', $w52_x );                                                                        /* P5-D180 */
}
t( 'بند ۴: چهار SVG فصل — هر کدام ≤ ۲۰KB، فقط کلاس tp-، بی رنگ خام و بی منبع بیرونی', $w52_svg );
$w52_ssn = function ( $s ) use ( $w52_hcss ) {
	return false !== strpos( $s, "return array( 'spring', 'summer', 'autumn', 'winter' );" )
		&& false !== strpos( $s, "dirname( __DIR__ ) . '/assets/home/seasons/' . \$s . '.svg'" ) && false !== strpos( $s, 'in_array( $s, topersia_g_season_keys(), true )' )
		&& 4 === preg_match_all( '/\.tp-home-season--(spring|summer|autumn|winter)\{--tp-ssn:var\(--tp-s-\1\);/', $w52_hcss )
		&& false !== strpos( $w52_hcss, '.tp-ssn-sky{fill:var(--tp-ssn-soft)}' );
};
tp441_mut( 'بند ۴: ترتیب بهار، تابستان، پاییز، زمستان از آغاز سطر؛ تصویر از پروندۀ خود افزونه، رنگ از توکن فصل — جهش: نام پرونده بی فهرست بسته', $w52_ssn, $w52_row,
	str_replace( 'in_array( $s, topersia_g_season_keys(), true ) && ', '', $w52_row ) );

/* ── بند ۵: صفحۀ فصل ───────────────────────────────────────────────────── */
$w52_sea = function ( $s ) use ( $w52_row ) {
	return false !== strpos( $s, "add_rewrite_rule( '^(?:' . \$bases . ')/([^/]+)/?\$', 'index.php?tp_season=\$matches[1]', 'top' );" )
		&& false !== strpos( $s, "apply_filters( 'topersia_g_season_slugs', \$all )" )                                                /* پایه و نامک ترجمه‌پذیر */
		&& false !== strpos( $s, "return array_keys( array_filter( \$reg, function ( \$row ) { return '' !== \$row['season']; } ) );" )   /* همۀ نوع‌های دارای فیلد فصل */
		&& false !== strpos( $s, 'return topersia_g_hub_in_season( $id, $seas );' )
		&& false !== strpos( $s, "|| '' !== topersia_g_hub_season();" )                                                               /* هاب است ← noindex زیر ۳ و اسکیما از همان مسیر */
		&& false !== strpos( $s, '$wp_query->set_404();' )                                                                            /* نامک ناشناس */
		&& false !== strpos( $w52_row, "topersia_g_season_url( \$s ) : '';" ) && false === strpos( $w52_row, "array( 'season' => \$s )" );
};
tp441_mut( 'بند ۵: /season/{فصل}/ حالت فصل هاب — همۀ نوع‌های دارای best_season، نامک ترجمه‌پذیر، ناشناس ۴۰۴، کارت فقط به همین صفحه — جهش: بی صافی فصل', $w52_sea, $w52_hub,
	str_replace( 'return topersia_g_hub_in_season( $id, $seas );', 'return true;', $w52_hub ) );
if ( function_exists( 'topersia_g_season_slugs' ) ) {
	$w52_sl = topersia_g_season_slugs();
	t( 'بند ۵ رفتار: نامک هر سه زبان، چهار فصل و پایه', isset( $w52_sl['fa']['spring'], $w52_sl['en']['winter'], $w52_sl['ar']['autumn'], $w52_sl['fa']['base'] ) && 'season' === $w52_sl['en']['base'] );
}

/* ── بند ۶: نُه آیکون راهنما ─────────────────────────────────────────── */
$w52_guide = function ( $s ) use ( $w52_hcss ) {
	$a   = strpos( $s, 'function topersia_g_home_guide_types() {' );
	$blk = false !== $a ? substr( $s, $a, (int) strpos( $s, "\n\t}\n", $a ) - $a ) : '';
	return 9 === preg_match_all( "/^\t\t\t'tp_[a-z_]+'\s+=> array\( __\(/m", $blk )
		&& false !== strpos( $s, '$guide[] = array( $g[0], $g[1], $hub );' ) && false === strpos( $s, 'emergency_police' )
		&& false !== strpos( $w52_hcss, '.tp-home-guide--nine{grid-template-columns:repeat(9,minmax(0,1fr))}' )
		&& (bool) preg_match( '/@media \(max-width:768px\)\{\s*html \.tp-page\.tp-home \.tp-home-guide--nine\{grid-template-columns:repeat\(3,minmax\(0,1fr\)\)\}/', $w52_hcss );
};
tp441_mut( 'بند ۶: دقیقاً ۹ نوع راهنما، هر کدام فقط به هاب نوع؛ یک ردیف ۹تایی، موبایل ۳×۳ — جهش: نوع دهم', $w52_guide, $w52_home,
	str_replace( "\t\t\t'tp_local_food'       => array(", "\t\t\t'tp_route'            => array( __( 'x', 'topersia' ), 'route' ),\n\t\t\t'tp_local_food'       => array(", $w52_home ) );
if ( function_exists( 'topersia_g_type_matrix' ) && function_exists( 'topersia_g_home_guide_types' ) ) {
	t( 'بند ۶ رفتار: هر ۹ نوع راهنما در ماتریس نوع هست (نوع تازه ساخته نشده)', ! array_diff( array_keys( topersia_g_home_guide_types() ), array_keys( topersia_g_type_matrix() ) ) && 9 === count( topersia_g_home_guide_types() ) );
}

/* ── بند ۸: ایران در تصویر — یکتا، چرخش روزانه، بی نقطه ─────────────────── */
$w52_ph = function ( $s ) {
	return false !== strpos( $s, 'if ( $att && ! isset( $own[ $att ] ) && wp_attachment_is_image( $att ) ) {' )                       /* مشترک فقط یک بار */
		&& false !== strpos( $s, "return strcmp( md5( \$date . ':' . \$a ), md5( \$date . ':' . \$b ) );" )                           /* بذر تاریخ */
		&& false !== strpos( $s, "\$key  = 'tp_home_ph_' . topersia_g_page_lang() . '_' . str_replace( '-', '', \$date );" )           /* کش به‌ازای زبان و روز */
		&& false !== strpos( $s, 'set_transient( $key, $hit, topersia_g_home_photo_ttl() );' ) && false !== strpos( $s, 'DAY_IN_SECONDS - ( $now % DAY_IN_SECONDS )' )   /* تا نیمه‌شب */
		&& false !== strpos( $s, "apply_filters( 'topersia_g_home_today', \$d )" )
		&& false === strpos( $s, 'tp-home-dots' );
};
tp441_mut( 'بند ۸: عکس یکتا، بذر تاریخ روز، کش تا نیمه‌شب به‌ازای زبان، بی نقطه — جهش: بذر بی تاریخ', $w52_ph, $w52_home,
	str_replace( "md5( \$date . ':' . \$a ), md5( \$date . ':' . \$b )", "md5( ':' . \$a ), md5( ':' . \$b )", $w52_home ) );
if ( ! function_exists( 'topersia_g_home_photo_order' ) ) {
	require_once $w52_root . '/plugin/topersia-render/includes/topersia-g-home.php';   /* فقط تعریف تابع؛ قلاب‌ها پشت function_exists */
}
if ( function_exists( 'topersia_g_home_photo_order' ) ) {
	$w52_own = array();
	for ( $w52_i = 1; $w52_i <= 40; $w52_i++ ) {
		$w52_own[ 100 + $w52_i ] = 1 + ( $w52_i % 10 );
	}
	$w52_d1  = topersia_g_home_photo_order( $w52_own, '2026-11-10', 12, 3 );
	$w52_d1b = topersia_g_home_photo_order( $w52_own, '2026-11-10', 12, 3 );
	$w52_d2  = topersia_g_home_photo_order( $w52_own, '2026-11-11', 12, 3 );
	$w52_cnt = array_count_values( array_column( $w52_d1, 0 ) );
	t( 'بند ۸ رفتار: همان روز همان ترکیب', $w52_d1 === $w52_d1b && 12 === count( $w52_d1 ) );
	t( 'بند ۸ رفتار (جهش تاریخ): روز بعد ترکیب دیگر', $w52_d1 !== $w52_d2 && 12 === count( $w52_d2 ) );
	t( 'بند ۸ رفتار: هر عکس یک بار، از هر موجودیت دست‌بالا ۳', 12 === count( array_unique( array_column( $w52_d1, 1 ) ) ) && max( $w52_cnt ) <= 3 );
} else {
	t( 'بند ۸ رفتار: topersia_g_home_photo_order بار شده', false );
}

/* ── بند ۹: بی tilt ───────────────────────────────────────────────────── */
$w52_tilt = function ( $c ) {
	return false === strpos( $c, 'rotateX' ) && false === strpos( $c, 'rotateY' ) && false === strpos( $c, 'perspective' ) && false === strpos( $c, 'skew' )
		&& 0 === preg_match( '/tp-home-guide__a[^{]*:hover\{[^}]*transform/', $c )
		&& false !== strpos( $c, '.tp-home-guide__a:hover{border-color:var(--tp-primary);color:var(--tp-primary-text);box-shadow:var(--tp-shadow-soft)}' );
};
tp441_mut( 'بند ۹: راهنما و تجربیات بی کج‌شدن و بی جابه‌جایی؛ هاور فقط رنگ و سایه — جهش: rotateX برگردد', $w52_tilt, $w52_hcss,
	$w52_hcss . "\nhtml .tp-page.tp-home .tp-home-guide .tp-home-guide__a:hover{transform:rotateX(5deg)}" );

/* ── بند ۱۰: «با پالایش و جست‌وجو.» هیچ‌جا ─────────────────────────────── */
$w52_gone = true;
$w52_hit  = array();
foreach ( array( 'plugin/topersia-render/includes/topersia-g-hub.php', 'plugin/topersia-render/templates/hub-archive.php', 'scripts/i18n/topersia-render.php',
	'plugin/topersia-render/languages/topersia.pot', 'plugin/topersia-render/languages/topersia-en_US.po', 'plugin/topersia-render/languages/topersia-ar.po',
	'plugin/topersia-render/languages/topersia-en_US.l10n.php', 'plugin/topersia-render/languages/topersia-ar.l10n.php',
	'plugin/topersia-render/languages/topersia-en_US.mo', 'plugin/topersia-render/languages/topersia-ar.mo' ) as $w52_p ) {
	$w52_x = $w52_read( $w52_p );
	if ( '' === $w52_x || false !== strpos( $w52_x, 'با پالایش و جست' ) || false !== strpos( $w52_x, 'with filters and search' ) || false !== strpos( $w52_x, 'مع التصفية والبحث' ) ) {
		$w52_gone  = false;
		$w52_hit[] = $w52_p;
	}
}
t( 'بند ۱۰: «با پالایش و جست‌وجو.» در قالب، کاتالوگ، .po و .mo نیست (fa · en · ar)', $w52_gone, implode( ' · ', $w52_hit ) );
$w52_lead = function ( $s ) { return false !== strpos( $s, "__( 'فهرست کامل %s.', 'topersia' )" ) && false === strpos( $s, 'با پالایش و جست' ); };
tp441_mut( 'بند ۱۰: جملۀ زیر H1 هاب «فهرست کامل %s.» — جهش: متن کهنه', $w52_lead, $w52_hub, str_replace( "'فهرست کامل %s.'", "'فهرست کامل %s، با پالایش و جست‌وجو.'", $w52_hub ) );

/* ── بند ۱۱: مقاله هم‌مدل خبر ─────────────────────────────────────────── */
$w52_kind = function ( $s ) {
	return false !== strpos( $s, "return array( 'tpc_news', 'tpc_article' );" )
		&& false !== strpos( $s, "return topersia_g_news_is_article() ? '_tp_article_ancestor' : '_tp_news_ancestor';" )
		&& false !== strpos( $s, "if ( 'posts::' . \$pt === \$parent && in_array( \$child, topersia_g_news_types(), true ) ) {" )      /* همان چهار رابطه با مکان */
		&& false !== strpos( $s, "'post_type'        => topersia_g_news_pt()," ) && false === strpos( $s, "'post_type'        => 'tpc_news'," )
		&& false !== strpos( $s, "'/([^/]+)/articles(?:/page/([0-9]{1,}))?/?\$'," )                                                    /* فهرست مقاله‌های هر مکان */
		&& false !== strpos( $s, "topersia_g_news_is_article() ? 'templates/article-archive.php' : 'templates/news-archive.php'" )
		&& false !== strpos( $s, '$GLOBALS[\'tp_content_kind\'] = $old;' );                                                           /* سوییچ همیشه برمی‌گردد */
};
tp441_mut( 'بند ۱۱: tpc_article با همان توابع خبر (سوییچ نوع) — رابطه‌ها، نیای خودش، فهرست هر مکان، آرشیو — جهش: نیای مقاله در متای خبر', $w52_kind, $w52_news,
	str_replace( "? '_tp_article_ancestor' : '_tp_news_ancestor';", "? '_tp_news_ancestor' : '_tp_news_ancestor';", $w52_news ) );
t( 'بند ۱۱: موضوع مشترک tpc_news_topic و زمان مطالعه برای هر دو نوع', false !== strpos( $w52_nui, "register_taxonomy( 'tpc_news_topic', topersia_g_content_types(), array(" )
	&& false !== strpos( $w52_nui, "add_action( 'save_post_' . \$topersia_ct, function ( \$post_id ) {" ) );
$w52_asch = function ( $s ) {
	$a   = strpos( $s, 'function topersia_schema_article_json(' );
	$blk = false !== $a ? substr( $s, $a, (int) strpos( $s, "\n}\n", $a ) - $a ) : '';
	return '' !== $blk && false !== strpos( $blk, "'@type'            => 'Article'," ) && false !== strpos( $blk, "'dateModified'     => '' !== \$upd ? \$upd :" )
		&& false === strpos( $blk, 'AggregateRating' ) && false === strpos( $blk, "'Review'" ) && false === strpos( $blk, 'news_source' )   /* P5-D510؛ بی فیلد منبع خبر */
		&& false !== strpos( $s, "if ( 'tpc_article' === get_post_type( \$post_id ) ) {" )
		&& strpos( $s, "if ( '1' === (string) get_post_meta( \$post_id, '_tp_demo', true ) ) {" ) < strpos( $s, "if ( 'tpc_article' === get_post_type( \$post_id ) ) {" );   /* نمایشی هرگز در اسکیما */
};
tp441_mut( 'بند ۱۱: اسکیمای Article با dateModified از «به‌روزرسانی»، بی امتیاز و بی منبع خبر، پس از دروازۀ نمایشی — جهش: AggregateRating', $w52_asch, $w52_sch,
	str_replace( "'@type'            => 'Article',", "'@type'            => 'Article', 'aggregateRating' => array( '@type' => 'AggregateRating' ),", $w52_sch ) );
t( 'بند ۱۱: فقط محلی — اسکریپت مدل دروازۀ محیط دارد و کد بی نوع کاری نمی‌کند', false !== strpos( $w52_read( 'scripts/tp-local-52-articles.php' ), "if ( 'local' !== wp_get_environment_type() ||" )
	&& false !== strpos( $w52_art, "if ( ! topersia_g_article_on() || ! function_exists( 'topersia_g_home_section' ) ) {" )
	&& false !== strpos( $w52_read( 'docs/relay/CURRENT.md' ), 'پیش از زنده — ممیزی NotebookLM لازم (P5-D567)' )
	&& false !== strpos( $w52_read( 'docs/ops/STRUCTURE-LOCK.json' ), 'tpc_article (فقط محلی' ) );

/* ── بند ۱۲: هاب، برچسب، فصل و مقالات بی ردیف فیلتر ─────────────────────── */
$w52_bar = function ( $s ) {
	return false === strpos( $s, 'topersia_g_loc_field(' ) && false === strpos( $s, 'name="tp_q"' ) && false === strpos( $s, "'season'," )   /* بی مکان، جست‌وجو، فصل */
		&& false !== strpos( $s, 'topersia_g_news_chips(' ) && false !== strpos( $s, "'tp_sort'" )
		&& false !== strpos( $s, "esc_html__( 'نمایش', 'topersia' )" ) && false !== strpos( $s, 'topersia_g_news_view_toggle()' )
		&& false !== strpos( $s, 'class="tp-news-filter tp-news-filter--min"' );
};
tp441_mut( 'بند ۱۲: هاب نوع/برچسب/فصل — فقط تراشه، مرتب‌سازی، «نمایش»، شبکه/فهرست — جهش: فیلد جست‌وجو برگردد', $w52_bar, $w52_htpl,
	str_replace( '<div class="tp-news-filter__go">', '<input type="search" name="tp_q"><div class="tp-news-filter__go">', $w52_htpl ) );
tp441_mut( 'بند ۱۲: /articles/ همان نوار ابزار — جهش: combobox مکان', $w52_bar, $w52_atpl,
	str_replace( '<div class="tp-news-filter__go">', '<?php echo topersia_g_loc_field( "x", 0 ); ?><div class="tp-news-filter__go">', $w52_atpl ) );
t( 'بند ۱۲: /news/ سراسری فیلترهای خودش را نگه داشته', false !== strpos( $w52_read( 'plugin/topersia-render/templates/news-archive.php' ), 'topersia_g_loc_field(' ) );

/* ── بند ۱۳: مسیر سبک و کش قطعه ───────────────────────────────────────── */
$w52_fr = function ( $s ) {
	return false === strpos( $s, 'wp-load' ) && false === strpos( $s, 'wp-blog-header' ) && false === strpos( $s, 'wp-config' )            /* بی وردپرس */
		&& false !== strpos( $s, "\$tp_file = \$tp_dir . '/' . \$tp_key . '.html';" )                                                 /* فقط md5 به مسیر می‌رسد */
		&& false !== strpos( $s, "if ( is_file( \$tp_dir . '/off' ) || 'GET' !== " )                                                  /* نشان خاموش، فقط GET */
		&& false !== strpos( $s, "'wordpress_logged_in_'" ) && false !== strpos( $s, 'filemtime( $tp_file ) < time() - TOPERSIA_FRAG_TTL' )
		&& false !== strpos( $s, "header( 'Cache-Control: no-store' );" ) && false !== strpos( $s, "header( 'X-Robots-Tag: noindex, nofollow' );" )
		&& 0 === preg_match( '/file_put_contents|fopen|unlink|eval\(|include\s+\$|require\s+\$/', $s );                               /* هیچ نوشتن */
};
tp441_mut( 'بند ۱۳: fragment.php بی وردپرس، فقط خواندن پروندۀ md5، نشان خاموش، مهمان، TTL، no-store — جهش: بی نشان خاموش', $w52_fr, $w52_frag,
	str_replace( "is_file( \$tp_dir . '/off' ) || ", '', $w52_frag ) );
$w52_fw = function ( $s ) {
	return false !== strpos( $s, "if ( is_user_logged_in() || is_404() || '' !== (string) get_option( 'elementor_maintenance_mode_mode', '' ) ) {" )
		&& false !== strpos( $s, "if ( 'GET' !== ( isset( \$_SERVER['REQUEST_METHOD'] ) ? (string) \$_SERVER['REQUEST_METHOD'] : '' ) || ! empty( \$_POST ) ) {" )
		&& false !== strpos( $s, 'trim( (string) $v ) !== trim( (string) $raw )' )                                                    /* کلید = ورودی رندر */
		&& false !== strpos( $s, "add_action( 'save_post', \$topersia_frag_post, 99 );" )                                             /* ابطال با ذخیره */
		&& false !== strpos( $s, "add_action( 'update_option_elementor_maintenance_mode_mode'," );
};
tp441_mut( 'بند ۱۳: نویسندۀ کش — فقط مهمان و سایت عمومی، فقط GET، کلید از همان ورودی رندر، ابطال با ذخیره و تغییر حالت نگهداری — جهش: بی ابطال', $w52_fw, $w52_nui,
	str_replace( "add_action( 'save_post', \$topersia_frag_post, 99 );", '', $w52_nui ) );
$w52_fj = function ( $j ) {
	return false !== strpos( $j, "root.getAttribute( 'data-tp-news-frag' )" ) && false !== strpos( $j, 'WAIT = 250' )
		&& false !== strpos( $j, "root.addEventListener( 'mouseover', warm, { passive: true } );" ) && false !== strpos( $j, "root.addEventListener( 'touchstart', warm, { passive: true } );" )
		&& false !== strpos( $j, 'ctrl.abort();' ) && false !== strpos( $j, 'new window.AbortController()' )
		&& false !== strpos( $j, "el.name !== 'tp_q'" );
};
tp441_mut( 'بند ۱۳ JS: مسیر سبک نخست، پیش‌واکشی با hover/touchstart، درنگ ۲۵۰ms، AbortController — جهش: درنگ ۱۰۰۰', $w52_fj, $w52_njs, str_replace( 'WAIT = 250', 'WAIT = 1000', $w52_njs ) );
if ( ! function_exists( 'topersia_frag_key' ) ) {
	require_once $w52_root . '/plugin/topersia-render/includes/topersia-frag-key.php';
}
t( 'بند ۱۳ رفتار: کلید به میزبان، مسیر (زبان) و پارامتر بسته است؛ tp_partial و ترتیب پارامترها بی اثر',
	topersia_frag_key( 'A.local', '/news/', array( 'tp_topic' => 'x', 'tp_sort' => 'old' ) ) === topersia_frag_key( 'a.local', 'news', array( 'tp_partial' => '1', 'tp_sort' => 'old', 'tp_topic' => 'x' ) )
	&& topersia_frag_key( 'a.local', '/news/', array() ) !== topersia_frag_key( 'a.local', '/en/news/', array() )
	&& topersia_frag_key( 'a.local', '/news/', array( 'tp_topic' => 'x' ) ) !== topersia_frag_key( 'a.local', '/news/', array( 'tp_topic' => 'y' ) ) );
t( 'بند ۱۳ رفتار: جست‌وجوی آزاد، نام مکان، پارامتر ناشناس، آرایه و مسیر با «..» کش نمی‌شوند',
	'' === topersia_frag_key( 'a.local', '/news/', array( 'tp_q' => 'x' ) ) && '' === topersia_frag_key( 'a.local', '/news/', array( 'tp_loc' => 'x' ) )
	&& '' === topersia_frag_key( 'a.local', '/news/', array( 'zzz' => '1' ) ) && '' === topersia_frag_key( 'a.local', '/news/', array( 'tp_topic' => array( 'x' ) ) )
	&& '' === topersia_frag_key( 'a.local', '/../x/', array() ) && '' === topersia_frag_key( '', '/news/', array() ) );

/* ── بندهای ۱۴ تا ۱۸: صفحۀ تکی ─────────────────────────────────────────── */
$w52_one = function ( $s ) {
	$main = strpos( $s, '<div class="tp-news-main">' );
	$body = strpos( $s, '<article class="tp-news-body">' );
	$cmts = strpos( $s, '<div class="tp-news-comments">' );
	$side = strpos( $s, '<?php echo $tp_aside;' );
	$end  = strpos( $s, '<?php if ( $tp_rel ) : ?>' );
	return false !== $main && false !== $end && $main < $body && $body < $cmts && $cmts < $side && $side < $end                           /* اصلی (متن ← نظرات) ← کناری ← مرتبط تمام‌عرض */
		&& false !== strpos( $s, "topersia_g_sc_reviews( array( 'id' => \$tp_id ) )" )                                                 /* همان جزء نظرات */
		&& false !== strpos( $s, 'topersia_g_news_metaline( $tp_id )' ) && false === strpos( $s, 'منبع اصلی:' ) && false === strpos( $s, 'news_source' )   /* منبع بالای متن نیست */
		&& false !== strpos( $s, "topersia_g_news_l( 'related' )" ) && false !== strpos( $s, 'topersia_g_news_archive_card( $tp_one )' );
};
tp441_mut( 'بند ۱۴، ۱۶، ۱۷ و ۱۸: ستون اصلی (متن، نظرات) ← ستون کناری ← مرتبط‌ها تمام‌عرض؛ منبع بالای متن نیست — جهش: مرتبط‌ها پیش از ستون کناری', $w52_one, $w52_stpl,
	str_replace( '<?php echo $tp_aside;', '<?php if ( $tp_rel ) : ?><?php endif; ?><?php echo $tp_aside;', $w52_stpl ) );
$w52_side = function ( $s ) {
	$p = array();
	foreach ( array( "topersia_g_sc_actionbar( array( 'id' => \$id ) )", 'topersia_g_news_box_topics( $id )', 'topersia_g_news_box_places( $id )', 'topersia_g_news_box_latest( $id )', 'topersia_g_news_box_source( $id )' ) as $k ) {
		$p[] = strpos( $s, $k, (int) strpos( $s, 'function topersia_g_news_aside(' ) );
	}
	$ok = ! in_array( false, $p, true );
	for ( $i = 1; $ok && $i < count( $p ); $i++ ) {
		$ok = $p[ $i ] > $p[ $i - 1 ];
	}
	return $ok && false !== strpos( $s, "if ( '' === trim( (string) \$body ) ) {\n\t\t\treturn '';" )                                    /* جعبۀ بی‌داده پنهان */
		&& false !== strpos( $s, "define( 'TOPERSIA_NEWS_ASIDE_LATEST', 5 );" )
		&& false !== strpos( $s, 'rel="nofollow noopener" target="_blank"' ) && false !== strpos( $s, 'wp_http_validate_url( $r[1] )' )
		&& false !== strpos( $s, "implode( '<span class=\"tp-news-metaline__sep\" aria-hidden=\"true\">·</span>', \$parts )" );           /* جداکننده میان تاریخ و زمان مطالعه */
};
tp441_mut( 'بند ۱۵ و ۱۶: ستون کناری به ترتیب اشتراک · موضوع و برچسب · مکان‌ها · تازه‌ترین (۵) · منبع؛ جعبۀ تهی پنهان؛ سطر فراداده با جداکننده — جهش: منبع پیش از مکان‌ها', $w52_side, $w52_art,
	str_replace( array( "\t\t\t. topersia_g_news_box_places( \$id )\n", "\t\t\t. topersia_g_news_box_source( \$id );" ), array( "\t\t\t. topersia_g_news_box_source( \$id )\n", "\t\t\t. topersia_g_news_box_places( \$id );" ), $w52_art ) );
t( 'بند ۱۵ و ۱۶: جهش — بی جداکننده می‌افتد', ! $w52_side( str_replace( 'aria-hidden="true">·</span>\', $parts )', 'aria-hidden="true"></span>\', $parts )', $w52_art ) ) );
$w52_scss = function ( $c ) {
	$a   = strpos( $c, '/* ══ صفحۀ تکی خبر و مقاله — دو ستونه' );
	$blk = false !== $a ? substr( $c, $a ) : '';
	return '' !== $blk && 0 === preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|letter-spacing/i', (string) preg_replace( '/#tp-actions/', '', $blk ) )
		&& false !== strpos( $blk, '.tp-news-cols{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,340px);' )
		&& (bool) preg_match( '/@media \(max-width:900px\)\{\s*html \.tp-page\.tp-news-single \.tp-news-cols\{grid-template-columns:minmax\(0,1fr\)\}/', $blk )   /* موبایل: کناری زیر متن */
		&& false !== strpos( $blk, '.tp-news-metaline{display:flex;flex-wrap:wrap;justify-content:center;' )
		&& false !== strpos( $blk, '.tp-news-aside > #tp-actions{order:-1}' );
};
tp441_mut( 'بند ۱۴ و ۱۶ CSS: دو ستون (اصلی در آغاز سطر)، زیر ۹۰۰px یک ستون، سطر فراداده وسط‌چین، فقط توکن — جهش: رنگ خام', $w52_scss, $w52_ncss,
	str_replace( '.tp-news-box__h{margin:0 0 12px;', '.tp-news-box__h{color:#333;margin:0 0 12px;', $w52_ncss ) );
