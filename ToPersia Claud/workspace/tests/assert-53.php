<?php
/**
 * دستۀ 53 — از assert.php بار می‌شود؛ خواندن کد و رفتار تابع‌های خالص، هر ادعا با جهش.
 *
 * بندها: ۲ تا ۸ هاب‌ها (P5-D575 تا P5-D579)، ۶ب شهرستان روستا، ۹ «مشاهدۀ همه» و هاب گروهی،
 * ۱۰ تا ۱۳ صفحۀ تکی خبر و مقاله = اسکلت صفحۀ استان (P5-D573)، ۱۲ نظرِ بی ستاره (P5-D574)، ۱۴ نویسندۀ اسکیمای مقاله.
 */

echo "\nدستۀ 53 — هاب‌ها، «مشاهدۀ همه»، صفحۀ تکی = اسکلت صفحۀ استان، نظر بی ستاره، نویسندۀ اسکیما\n" . str_repeat( '─', 78 ) . "\n";

$w53_root = dirname( __DIR__ );
$w53_read = function ( $p ) use ( $w53_root ) { return (string) @file_get_contents( $w53_root . '/' . $p ); };
$w53_hub  = $w53_read( 'plugin/topersia-render/includes/topersia-g-hub.php' );
$w53_htpl = $w53_read( 'plugin/topersia-render/templates/hub-archive.php' );
$w53_atpl = $w53_read( 'plugin/topersia-render/templates/article-archive.php' );
$w53_hcss = $w53_read( 'plugin/topersia-render/assets/topersia-g-hub.css' );
$w53_ncss = $w53_read( 'plugin/topersia-render/assets/topersia-g-news.css' );
$w53_njs  = $w53_read( 'plugin/topersia-render/assets/topersia-g-news.js' );
$w53_nui  = $w53_read( 'plugin/topersia-render/includes/topersia-g-news-ui.php' );
$w53_zjs  = $w53_read( 'plugin/topersia-render/assets/topersia-g-zones.js' );
$w53_zon  = $w53_read( 'plugin/topersia-render/includes/topersia-g-zones.php' );
$w53_home = $w53_read( 'plugin/topersia-render/includes/topersia-g-home.php' );
$w53_row  = $w53_read( 'plugin/topersia-render/includes/topersia-g-home-row.php' );
$w53_hmcs = $w53_read( 'plugin/topersia-render/assets/topersia-g-home.css' );
$w53_art  = $w53_read( 'plugin/topersia-render/includes/topersia-g-article.php' );
$w53_stpl = $w53_read( 'plugin/topersia-render/templates/news-single.php' );
$w53_sch  = $w53_read( 'plugin/topersia-schema/topersia-schema.php' );
$w53_i18n = $w53_read( 'scripts/i18n/topersia-render.php' );
$w53_ucs  = $w53_read( 'plugin/topersia-user-core/includes/class-topersia-jetreviews-setup.php' );
$w53_ucf  = $w53_read( 'plugin/topersia-user-core/includes/class-topersia-review-form.php' );
$w53_uca  = $w53_read( 'plugin/topersia-user-core/includes/class-topersia-assets.php' );
$w53_ucj  = $w53_read( 'plugin/topersia-user-core/includes/class-topersia-jetengine.php' );
$w53_ucw  = $w53_read( 'plugin/topersia-user-core/includes/class-topersia-follow.php' );

/** متن یک تابع از «function نام(» تا آکولاد بستۀ خودش — برای خواندن یا اجرای جداگانۀ تابع خالص. */
$w53_fn = function ( $src, $name ) {
	$a = strpos( $src, 'function ' . $name . '(' );
	if ( false === $a ) {
		return '';
	}
	$depth = 0;
	$len   = strlen( $src );
	for ( $i = strpos( $src, '{', $a ); $i < $len; $i++ ) {
		if ( '{' === $src[ $i ] ) { $depth++; }
		if ( '}' === $src[ $i ] && 0 === --$depth ) {
			return substr( $src, $a, $i - $a + 1 );
		}
	}
	return '';
};

/* ── بند ۲: نوار ابزار یک‌خطی ─────────────────────────────────────────── */
$w53_bar = function ( $s ) {
	$p = array();
	foreach ( array( 'topersia_g_news_chips( $tp_chip_items', 'data-tp-more-btn', 'name="tp_q"', "topersia_g_loc_field( 'tp-hub-place'", "topersia_g_news_select( 'tp-hub-sort', 'tp_sort'", 'topersia_g_news_view_toggle()' ) as $k ) {
		$p[] = strpos( $s, $k );
	}
	$ok = ! in_array( false, $p, true );
	for ( $i = 1; $ok && $i < count( $p ); $i++ ) {
		$ok = $p[ $i ] > $p[ $i - 1 ];
	}
	return $ok && false !== strpos( $s, 'class="tp-news-filter tp-news-filter--min"' ) && false !== strpos( $s, 'data-tp-news-filter' )   /* فیلتر درجا، همان اسکریپت اخبار */
		&& false === strpos( $s, 'tp-news-filter__row' );                                                                              /* ردیف چندلایۀ قدیمی برنگشته */
};
tp441_mut( 'بند ۲: هاب — تراشه‌ها · دکمۀ «فیلتر» · جست‌وجو · ترکیب‌باکس مکان · مرتب‌سازی · شبکه/فهرست در یک نوار؛ بی ردیف چندلایه — جهش: بی ترکیب‌باکس مکان', $w53_bar, $w53_htpl,
	str_replace( "topersia_g_loc_field( 'tp-hub-place'", "topersia_g_x( 'tp-hub-place'", $w53_htpl ) );
t( 'بند ۲: /articles/ همان نوار — دکمۀ «فیلتر»، جست‌وجو و ترکیب‌باکس مکان (بازاستفاده، نه کد تازه)',
	false !== strpos( $w53_atpl, 'data-tp-more-btn' ) && false !== strpos( $w53_atpl, 'name="tp_q"' ) && false !== strpos( $w53_atpl, "topersia_g_loc_field( 'tp-hub-place'" ) );
$w53_more = function ( $j ) use ( $w53_hcss ) {
	return false !== strpos( $j, "root.querySelector( '[data-tp-more-btn]' )" ) && false !== strpos( $j, "root.querySelector( '[data-tp-more]' )" )
		&& false !== strpos( $w53_hcss, '.tp-hub-more[hidden]{display:none}' ) && false !== strpos( $w53_hcss, '.tp-hub-more__btn[hidden]{display:none}' );
};
tp441_mut( 'بند ۲ موبایل: جست‌وجو و مکان زیر یک دکمۀ «فیلتر» (بی JS همیشه دیدنی) — جهش: بی دکمه', $w53_more, $w53_njs,
	str_replace( "'[data-tp-more-btn]'", "'[data-tp-x]'", $w53_njs ) );

/* ── بند ۳: مرتب‌سازی وسعت ────────────────────────────────────────────── */
$w53_area = function ( $s ) {
	return false !== strpos( $s, "in_array( \$f, array( 'area_km2', 'total_area_km2' ), true )" )
		&& false !== strpos( $s, "if ( '' !== \$row['area'] ) {\n\t\t\t\$out['area_desc']" )                                             /* فقط نوع دارای فیلد وسعت */
		&& false !== strpos( $s, "\$out['area_asc']" )
		&& false !== strpos( $s, "if ( ( null === \$x ) !== ( null === \$y ) ) {\n\t\t\t\t\treturn null === \$x ? 1 : -1;" )             /* بی‌وسعت آخر */
		&& false !== strpos( $s, 'return ( $asc ? $x < $y : $x > $y ) ? -1 : 1;' );
};
tp441_mut( 'بند ۳: «وسعت» در هر دو جهت فقط برای نوع دارای area_km2 / total_area_km2؛ بی‌وسعت آخر — جهش: گزینه برای هر نوع', $w53_area, $w53_hub,
	str_replace( "if ( '' !== \$row['area'] ) {\n\t\t\t\$out['area_desc']", "if ( true ) {\n\t\t\t\$out['area_desc']", $w53_hub ) );

/* ── بند ۴: کاشی با عکس ──────────────────────────────────────────────── */
$w53_img = function ( $s ) {
	return false !== strpos( $s, '$img = topersia_g_news_real_img( topersia_g_card_image( $id ) );' )                                    /* عکس خود مورد */
		&& false !== strpos( $s, "metadata_exists( 'post', \$pid, '_tp_sample' ) ? 0 : topersia_g_news_real_img( topersia_g_card_image( \$pid ) )" )   /* عکس نیا، هرگز نمونه */
		&& false !== strpos( $s, "'loading' => 'lazy'" ) && false !== strpos( $s, "'sizes' => '(max-width: 600px) 100vw" )
		&& false !== strpos( $s, "( '' !== \$pic ? \$pic : topersia_g_icon( 'pin' ) )" )                                                 /* وگرنه آیکون نوع */
		&& false === strpos( $s, 'picsum' ) && false === strpos( $s, 'placeholder.com' );
};
tp441_mut( 'بند ۴: عکس مورد ← عکس نیا (نه نمونه) ← آیکون؛ تنبل با sizes؛ هیچ نشانی بیرونی — جهش: عکس نیای نمونه هم', $w53_img, $w53_hub,
	str_replace( "metadata_exists( 'post', \$pid, '_tp_sample' ) ? 0 : ", '', $w53_hub ) );

/* ── بند ۵: فهرست دو ستونه ───────────────────────────────────────────── */
$w53_rows = function ( $c ) {
	return false !== strpos( $c, '.tp-hub-grid.tp-news-grid--rows{grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:1fr}' )
		&& (bool) preg_match( '/@media \(max-width:720px\)\{\s*html \.tp-page\.tp-news-page \.tp-hub-grid\.tp-news-grid--rows\{grid-template-columns:minmax\(0,1fr\)\}/', $c );
};
tp441_mut( 'بند ۵: نمای فهرست — دسکتاپ ۲ ستون هم‌قد، موبایل ۱ ستون — جهش: دسکتاپ یک ستون', $w53_rows, $w53_hcss,
	str_replace( '.tp-news-grid--rows{grid-template-columns:repeat(2,minmax(0,1fr));grid-auto-rows:1fr}', '.tp-news-grid--rows{grid-template-columns:repeat(1,minmax(0,1fr));grid-auto-rows:1fr}', $w53_hcss ) );

/* ── بند ۶ و ۶ب: زیرعنوان «نوع · شهرستان · در استان» ─────────────────── */
$w53_sub = function ( $s ) {
	return false !== strpos( $s, 'foreach ( (array) topersia_g_crumb_chain( $id ) as $pid ) {' ) && false !== strpos( $s, "if ( 'tp_province' === get_post_type( \$pid ) ) {" )   /* استان از رابطه */
		&& false !== strpos( $s, "\$prov = 'tp_province' === \$type ? 0 : topersia_g_hub_province_of( \$id );" )                         /* استان: فقط نوع */
		&& false !== strpos( $s, "'<a href=\"' . esc_url( (string) get_permalink( \$prov ) ) . '\">' . esc_html( topersia_g_title( \$prov ) ) . '</a>'" )
		&& strpos( $s, '$county = topersia_g_hub_county_of( $id );' ) < strpos( $s, "\$prov = 'tp_province' === \$type" );               /* شهرستان پیش از استان */
};
tp441_mut( 'بند ۶: زیرعنوان از رابطه (مسیر راهنما)، نام استان پیوند، برای استان فقط نوع — جهش: استان هم «در استان» می‌گیرد', $w53_sub, $w53_hub,
	str_replace( "\$prov = 'tp_province' === \$type ? 0 : topersia_g_hub_province_of( \$id );", '$prov = topersia_g_hub_province_of( $id );', $w53_hub ) );
$w53_cnty = function ( $s ) {
	return false !== strpos( $s, "if ( 'tp_village' !== get_post_type( \$id ) ) {\n\t\t\treturn '';" )
		&& false !== strpos( $s, "topersia_g_rel_ids( \$id, array( array( 'id' => 76, 'dir' => 'parents' ) ) )" )                        /* JetEngine #76 شهرستان ← روستا؛ دروازۀ منتشرشده */
		&& false !== strpos( $s, 'return ! topersia_g_sample_hidden( $c );' )                                                           /* نمونه فقط در پیش‌نمایش */
		&& false !== strpos( $s, "sprintf( __( 'شهرستان %s', 'topersia' ), \$name )" );
};
tp441_mut( 'بند ۶ب: «شهرستان Y» روستا از رابطۀ ۷۶، فقط منتشرشده و غیرنمونه، بی پیوند (شهرستان صفحه ندارد) — جهش: شهرستان نمونه هم', $w53_cnty, $w53_hub,
	str_replace( 'return ! topersia_g_sample_hidden( $c );', 'return true;', $w53_hub ) );
$w53_skin = $w53_read( 'reference/Jetengine_Skin.json' );
t( 'بند ۶ب: رابطۀ شهرستان (والد) ← روستا (فرزند) در Jetengine_Skin.json هست',
	(bool) preg_match( '/"id":"76".{0,3000}?parent_object.{0,12}posts::tp_county.{0,400}?child_object.{0,12}posts::tp_village/s', $w53_skin )
	|| (bool) preg_match( '/"id":"76".{0,3000}?child_object.{0,12}posts::tp_village.{0,400}?parent_object.{0,12}posts::tp_county/s', $w53_skin ) );

/* ── بند ۷: جهت فلش تراشه‌ها و نوار پیشرفت ───────────────────────────── */
$w53_arw = function ( $j ) use ( $w53_nui ) {
	return false !== strpos( $j, "var rtl  = getComputedStyle( list ).direction === 'rtl';" )
		&& false !== strpos( $j, 'list.scrollBy( { left: ( rtl ? -1 : 1 ) * sign * list.clientWidth * 0.8' )                            /* جهت از صفحه، نه ثابت */
		&& false !== strpos( $w53_nui, '$a   = topersia_g_arrows();' ) && false !== strpos( $w53_nui, "\$btn( 'prev', \$a['prev']" ) && false !== strpos( $w53_nui, "\$btn( 'next', \$a['next']" )
		&& false !== strpos( $w53_nui, '<ul class="tp-news-chips__list" data-tp-prog>' )                                                /* نوار پیشرفت زیر هر ردیف */
		&& false !== strpos( $j, 'window.TopersiaProg( root );' );
};
tp441_mut( 'بند ۷: فلش تراشه از جهت صفحه (P5-D243) و نوار پیشرفت سفارشی زیر هر ردیف، پس از جایگزینی بی‌رفرش هم — جهش: جهت ثابت', $w53_arw, $w53_njs,
	str_replace( '( rtl ? -1 : 1 ) * sign', 'sign', $w53_njs ) );
t( 'بند ۷: سازندۀ نوار پیشرفت برای بیرون در دسترس است (window.TopersiaProg) و ردیف تراشه سه ستون صریح دارد',
	false !== strpos( $w53_zjs, 'window.TopersiaProg = function ( scope ) {' )
	&& false !== strpos( $w53_ncss, '.tp-news-chips{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center}' ) );

/* ── بند ۸: عنوان و title هاب ────────────────────────────────────────── */
if ( ! function_exists( 'topersia_g_hub_fit' ) ) {
	eval( $w53_fn( $w53_hub, 'topersia_g_hub_fit' ) );   // phpcs:ignore — تابع خالص همین مخزن
}
t( 'بند ۸ رفتار: title ≤ ۶۰ و description ≤ ۱۶۰ — متن بلند فقط اگر جا شود، وگرنه شکل کوتاه',
	'کوتاه' === topersia_g_hub_fit( str_repeat( 'ب', 61 ), 'کوتاه', 60 ) && str_repeat( 'ب', 60 ) === topersia_g_hub_fit( str_repeat( 'ب', 60 ), 'کوتاه', 60 )
	&& 'k' === topersia_g_hub_fit( str_repeat( 'x', 161 ), 'k', 160 ) );
$w53_ttl = function ( $s ) use ( $w53_i18n ) {
	return false !== strpos( $s, "__( '%s در ایران: همۀ مکان‌ها و مطالب', 'topersia' )" )
		&& false !== strpos( $s, "__( '%s: فهرست کامل با جست‌وجو و فیلتر استان', 'topersia' )" )
		&& false !== strpos( $s, "__( '%s در ایران؛ فهرست کامل مکان‌ها، راهنماها و اخبار', 'topersia' )" )
		&& false !== strpos( $s, "__( '%s؛ فهرست کامل به تفکیک استان و شهر', 'topersia' )" )
		&& false !== strpos( $s, "\$brand = function_exists( 'topersia_g_brand' ) ? (string) topersia_g_brand() : 'ToPersia';" )         /* برند به زبان صفحه */
		&& false !== strpos( $s, "return topersia_g_hub_fit( \$long . ' | ' . \$brand, topersia_g_hub_fit( \$short . ' | ' . \$brand, \$short, 60 ), 60 );" )
		&& false !== strpos( $s, "add_filter( 'rank_math/frontend/title'" ) && false !== strpos( $s, "add_filter( 'rank_math/frontend/description'" )   /* فقط از صافی ما */
		&& false === strpos( $s, 'همۀ مکان‌ها و مطالب «' )                                                                              /* شکل کهنه هیچ‌جا */
		&& false !== strpos( $w53_i18n, "'en' => '%s in Iran: all places and articles in one place'" )
		&& false !== strpos( $w53_i18n, "'ar' => '%s في إيران: كل الأماكن والمواد في مكان واحد'" );
};
tp441_mut( 'بند ۸: H1، title و توضیح هاب با %s سه‌زبانه، برند زبان صفحه، سقف طول، فقط از صافی Rank Math — جهش: بی سقف ۶۰', $w53_ttl, $w53_hub,
	str_replace( "topersia_g_hub_fit( \$short . ' | ' . \$brand, \$short, 60 ), 60 );", "\$short . ' | ' . \$brand, 600 );", $w53_hub ) );

/* ── بند ۹: «مشاهدۀ همه» و هاب گروهی ─────────────────────────────────── */
$w53_grp = function ( $s ) {
	return false !== strpos( $s, "'attractions' => array( 'slug' => 'attractions', 'types' => array_values( array_unique( \$attr ) )" )   /* همان نوع‌های اسلایدر جاذبه‌ها */
		&& false !== strpos( $s, "'events'      => array( 'slug' => 'events', 'types' => function_exists( 'topersia_g_home_event_types' ) ? topersia_g_home_event_types() : array()" )
		&& false !== strpos( $s, "add_rewrite_rule( '^' . \$gslug . '/?\$', 'index.php?tp_hubgroup=' . \$gkey, 'top' );" )
		&& false !== strpos( $s, "add_rewrite_rule( '^' . \$gslug . '/page/([0-9]+)/?\$'" )
		&& false !== strpos( $s, "|| '' !== topersia_g_hub_group();" )                                                                  /* هاب است: همان قالب، noindex و اسکیما */
		&& false !== strpos( $s, "if ( '' !== \$seas || '' !== \$grp ) {\n\t\t\t\$q->set( 'post_type', topersia_g_hub_types() );" )
		&& false !== strpos( $s, "return array_values( array_intersect( (array) topersia_g_hub_groups()[ \$grp ]['types'], array_keys( \$reg ) ) );" );
};
tp441_mut( 'بند ۹: هاب گروهی /attractions/ و /events/ با همان قالب هاب (نوع‌ها از فهرست صفحۀ اول)، صفحه‌بندی — جهش: بی قاعدۀ بازنویسی', $w53_grp, $w53_hub,
	str_replace( "add_rewrite_rule( '^' . \$gslug . '/?\$', 'index.php?tp_hubgroup=' . \$gkey, 'top' );", '', $w53_hub ) );
$w53_all = function ( $s ) use ( $w53_row, $w53_art ) {
	return false !== strpos( $s, "topersia_g_hub_group_url( 'attractions' )" ) && false !== strpos( $s, '$attr_cards, count( $groups ) ), $attr_more,' )   /* جاذبه‌ها ← /attractions/ */
		&& false !== strpos( $s, "get_post_type_archive_link( 'tp_city' )" )                                                            /* مقاصد ← /city/ */
		&& false !== strpos( $s, "topersia_g_hub_link_for( 'tp_province' )" )                                                           /* استان‌ها ← /province/ */
		&& false !== strpos( $s, "get_post_type_archive_link( 'tpc_news' )" )
		&& false !== strpos( $w53_row, "topersia_g_hub_group_url( 'events' )" ) && false !== strpos( $w53_row, "topersia_g_hub_link_for( 'tp_route' )" )
		&& false !== strpos( $w53_art, "get_post_type_archive_link( 'tpc_article' )" );
};
tp441_mut( 'بند ۹: «مشاهدۀ همه» هر اسلایدر صفحۀ اول به هاب خودش — استان، مقاصد، جاذبه‌ها، رویدادها، سفرها، اخبار، مقالات — جهش: جاذبه‌ها بی پیوند', $w53_all, $w53_home,
	str_replace( '$attr_cards, count( $groups ) ), $attr_more,', '$attr_cards, count( $groups ) ), array(),', $w53_home ) );
t( 'بند ۹ CSS: دو دکمۀ ردیف عنوان استان‌ها کنار هم، هدف لمسی ۴۴px',
	false !== strpos( $w53_hmcs, '.tp-section-head > .tp-home-more{display:inline-flex;flex:0 0 auto;align-items:center;gap:12px;white-space:nowrap}' )
	&& false !== strpos( $w53_hmcs, '.tp-home-more > :is(.tp-more,.tp-home-slider__all){min-block-size:44px;margin:0}' ) );

/* ── بند ۱۰: صفحۀ تکی = اسکلت صفحۀ استان ─────────────────────────────── */
$w53_page = $w53_fn( $w53_zon, 'topersia_g_sc_page' );
$w53_one  = $w53_fn( $w53_art, 'topersia_g_news_single' );
$w53_skel = function ( $s ) use ( $w53_page, $w53_stpl ) {
	$same = array(
		'<nav class="tp-snav" aria-label="',
		'<div class="tp-wrap tp-snav__in" data-tp-no-prog>',
		'<main id="tp-main" class="tp-main"><div class="tp-wrap">',
		'<div class="tp-content-grid">',
		'<div class="tp-main-col">',
		'<aside class="tp-rail" aria-label="',
	);
	$pos = array();
	foreach ( $same as $k ) {
		if ( false === strpos( $w53_page, $k ) ) {                                                                                    /* همان نشانه‌گذاری صفحۀ موجودیت */
			return false;
		}
		$pos[] = strpos( $s, $k );
	}
	$ok = ! in_array( false, $pos, true );
	for ( $i = 1; $ok && $i < count( $pos ); $i++ ) {
		$ok = $pos[ $i ] > $pos[ $i - 1 ];
	}
	return $ok && false !== strpos( $s, 'topersia_g_mobilebar( $id, $nav, $rendered )' )                                                /* همان نوار پایین و برگۀ بخش‌ها */
		&& false !== strpos( $s, 'topersia_g_icon( topersia_g_section_icon( $zid ) )' )
		&& false !== strpos( $w53_stpl, 'echo topersia_g_news_hero( topersia_g_news_hero_img( topersia_g_news_image( $tp_id ) ), $tp_crumbs, get_the_title( $tp_id ), topersia_g_news_sum( $tp_id ), $tp_extra );' )   /* هیرو دست‌نخورده */
		&& false !== strpos( $w53_stpl, 'echo topersia_g_news_single( $tp_id, $tp_text );' )
		&& false === strpos( $w53_stpl, 'tp-news-cols' ) && false === strpos( $w53_stpl, 'topersia_g_news_aside' );                     /* چیدمان P5-D568 رفت */
};
tp441_mut( 'بند ۱۰: صفحۀ تکی خبر/مقاله همان اسکلت [tp_page] — snav ← main ← content-grid ← main-col + rail ← نوار پایین موبایل؛ هیرو همان — جهش: بی نوار پایین موبایل', $w53_skel, $w53_one,
	str_replace( 'topersia_g_mobilebar( $id, $nav, $rendered )', "''", $w53_one ) );
$w53_zone = function ( $s ) {
	$list = array(
		"array( 'tp-text', __( 'متن', 'topersia' ), \$text )", "array( 'tp-reviews', __( 'نظرات', 'topersia' )", "array( 'tp-rel-news', topersia_g_news_l( 'related' ), \$related )",
		"array( 'tp-actions', ''", "array( 'tp-topics', ''", "array( 'tp-rel-places', __( 'مکان‌های مرتبط', 'topersia' )", "array( 'tp-latest', ''", "array( 'tp-sources', __( 'منبع', 'topersia' )",
	);
	$last = 0;
	foreach ( $list as $k ) {
		$p = strpos( $s, $k );
		if ( false === $p || $p < $last ) {
			return false;
		}
		$last = $p;
	}
	return false !== strpos( $s, "return '' !== trim( (string) \$z[2] );" )                                                             /* بخش بی‌داده نمی‌آید */
		&& false !== strpos( $s, "topersia_g_sc_reviews( array( 'id' => \$id ) )" ) && false !== strpos( $s, "topersia_g_sc_actionbar( array( 'id' => \$id ) )" )   /* همان اجزای صفحۀ استان */
		&& false !== strpos( $s, '<article class="tp-news-body">' );
};
tp441_mut( 'بند ۱۰: ستون اصلی متن ← نظرات ← مرتبط‌ها؛ ستون کناری اشتراک · موضوع و برچسب · مکان‌ها · تازه‌ترین · منبع؛ اجزا همان صفحۀ استان — جهش: منبع پیش از مکان‌ها', $w53_zone, $w53_art,
	str_replace( array( "array( 'tp-rel-places', __( 'مکان‌های مرتبط', 'topersia' )", "array( 'tp-sources', __( 'منبع', 'topersia' )" ), array( "array( 'tp-sources-x', __( 'منبع', 'topersia' )", "array( 'tp-rel-places', __( 'مکان‌های مرتبط', 'topersia' )" ), $w53_art ) );
$w53_box = function ( $s ) {
	return false !== strpos( $s, "return topersia_g_section( 'tp-' . \$key, \$title, '', \$body, 'tp-rail-card tp-news-box tp-news-box--' . \$key );" )   /* همان پوستۀ کارت صفحۀ موجودیت */
		&& false !== strpos( $s, "topersia_g_news_box( 'rel-places'" ) && false !== strpos( $s, "topersia_g_news_box( 'sources'" )
		&& false !== strpos( $s, "define( 'TOPERSIA_NEWS_ASIDE_LATEST', 5 );" )
		&& false !== strpos( $s, 'rel="nofollow noopener" target="_blank"' ) && false !== strpos( $s, 'wp_http_validate_url( $r[1] )' )
		&& false !== strpos( $s, "implode( '<span class=\"tp-news-metaline__sep\" aria-hidden=\"true\">·</span>', \$parts )" );           /* سطر فراداده با جداکننده */
};
tp441_mut( 'بند ۱۰: کارت‌های ستون کناری با topersia_g_section (section.tp-rail-card، h3)، لنگر tp-…؛ منبع با nofollow و نشانی معتبر — جهش: پوستۀ جدا', $w53_box, $w53_art,
	str_replace( "return topersia_g_section( 'tp-' . \$key,", "return topersia_g_x( 'tp-' . \$key,", $w53_art ) );

/* ── بند ۱۱: نوار بخش‌ها ─────────────────────────────────────────────── */
$w53_nav = function ( $s ) {
	return false !== strpos( $s, "return array( 'tp-text', 'tp-rel-places', 'tp-sources', 'tp-reviews', 'tp-rel-news' );" )              /* متن · مکان‌ها · منبع · نظرات · مرتبط‌ها */
		&& false !== strpos( $s, "if ( isset( \$rendered[ \$zid ] ) && '' !== \$rendered[ \$zid ]['nav'] ) {" )                          /* فقط بخش رندرشده */
		&& false !== strpos( $s, 'if ( count( $nav ) > 1 ) {' );
};
tp441_mut( 'بند ۱۱: نوار بخش‌ها به ترتیب «متن · مکان‌های مرتبط · منبع · نظرات · خبرهای مرتبط»، فقط بخش دارای داده — جهش: پیوند به بخش رندرنشده', $w53_nav, $w53_art,
	str_replace( "if ( isset( \$rendered[ \$zid ] ) && '' !== \$rendered[ \$zid ]['nav'] ) {", 'if ( true ) {', $w53_art ) );
t( 'بند ۱۱: هر بخش تازه آیکون ثابت خودش را دارد و برچسب‌ها در کاتالوگ سه‌زبانه‌اند',
	false !== strpos( $w53_zon, "'tp-text'        => 'info'," ) && false !== strpos( $w53_zon, "'tp-rel-places'  => 'pin'," ) && false !== strpos( $w53_zon, "'tp-rel-news'    => 'news'," )
	&& false !== strpos( $w53_i18n, "'متن' => array( 'en' => 'Text'" ) && false !== strpos( $w53_i18n, "'منبع' => array( 'en' => 'Source'" ) && false !== strpos( $w53_i18n, "'نظرات' => array( 'en' => 'Comments'" ) );

/* ── بند ۱۲: نظرِ بی ستاره ───────────────────────────────────────────── */
if ( ! class_exists( 'ToPersia_JetReviews_Setup' ) ) {
	require_once $w53_root . '/plugin/topersia-user-core/includes/class-topersia-jetreviews-setup.php';
}
$w53_fam = ToPersia_JetReviews_Setup::families();
t( 'بند ۱۲ رفتار: خانوادۀ «خبر و مقاله» با دو نوع، بی معیار و بی ستاره؛ پنج خانوادۀ پیشین همان‌اند',
	array( 'destination', 'nature', 'heritage', 'event', 'border', 'content' ) === array_keys( $w53_fam )
	&& array( 'tpc_news', 'tpc_article' ) === $w53_fam['content']['types'] && array() === $w53_fam['content']['criteria'] && false === $w53_fam['content']['stars']
	&& ToPersia_JetReviews_Setup::starless( 'tpc_news' ) && ToPersia_JetReviews_Setup::starless( 'tpc_article' )
	&& ! ToPersia_JetReviews_Setup::starless( 'tp_city' ) && ToPersia_JetReviews_Setup::allowed( 'tp_city' ) && ! ToPersia_JetReviews_Setup::allowed( 'tp_county' )
	&& array() === ToPersia_JetReviews_Setup::type_fields( 'tpc_news' )
	&& isset( ToPersia_JetReviews_Setup::post_types()['tpc_news'], ToPersia_JetReviews_Setup::post_types()['tpc_article'] )
	&& false === ToPersia_JetReviews_Setup::type_settings( 'tpc_news', true )['structuredata'] );
/* رفتار ToPersia_Review_Form::prepare() برای خبر و مقاله در tests/assert-uc.php است (این‌جا کلاس stub آزمون ۴٫۳٫۰ بار شده). */
$w53_rev = $w53_fn( $w53_zon, 'topersia_g_sc_reviews' );
$w53_pln = function ( $s ) use ( $w53_zon, $w53_sch ) {
	$a   = strpos( $s, 'if ( $plain && $count ) {' );
	$b   = false !== $a ? strpos( $s, "\n\t\t}\n", $a ) : false;
	$blk = ( false !== $a && false !== $b ) ? substr( $s, $a, $b - $a ) : '';
	return '' !== $blk && false === strpos( $blk, 'tp-rate__stars' ) && false === strpos( $blk, 'tp-rate__score' ) && false === strpos( $blk, 'tp-bar' )   /* بی ستاره، میانگین و توزیع */
		&& false !== strpos( $blk, "_n( '%s نظر', '%s نظر', (int) \$count, 'topersia' )" )
		&& false !== strpos( $s, 'if ( ! $count && ! $criteria && ! $plain ) {' )
		&& false !== strpos( $s, '$form = ( $criteria || $plain ) ? topersia_g_review_form( $pid, $criteria ) : \'\';' )
		&& false !== strpos( $w53_zon, "method_exists( 'ToPersia_Review_Form', 'starless' )" )
		&& false !== strpos( $w53_zon, "( '' !== \$rows ? '<div class=\"tp-review-form__crit\">' . \$rows . '</div>' : '' )" )           /* فرم بی ردیف معیار */
		&& 0 === preg_match( "/'@type'\s*=>\s*'(AggregateRating|Review)'/", $w53_sch );                                                 /* اسکیما هیچ */
};
tp441_mut( 'بند ۱۲: همان جزء نظرات صفحۀ استان برای خبر/مقاله — فقط نام و متن و شمار، بی ستاره و بی AggregateRating/Review — جهش: ستاره در فهرست', $w53_pln, $w53_rev,
	str_replace( "'<div class=\"tp-cmt\">' . ( '' !== \$author ? '<b>' . esc_html( \$author ) . '</b>' : '' ) . '<p>'", "'<div class=\"tp-cmt\"><span class=\"tp-rate__stars\"></span><p>'", $w53_rev ) );
$w53_uc = function ( $s ) use ( $w53_ucf, $w53_ucj, $w53_ucw ) {
	return false !== strpos( $s, 'ToPersia_JetReviews_Setup::allowed((string) $post->post_type)' )                                      /* اسکریپت user-core روی خبر و مقاله */
		&& false !== strpos( $w53_ucf, '! self::allowed( $post->post_type ) ) {' )
		&& false !== strpos( $w53_ucf, 'if ( ! self::allowed( $post_type ) || ( ! $criteria && ! self::starless( $post_type ) ) ) {' )
		&& false !== strpos( $w53_ucj, "return str_starts_with(\$type, 'tp_') || in_array(\$type, array('tpc_news', 'tpc_article'), true);" )
		&& 3 === substr_count( $w53_ucj, 'self::is_entity_type(' ) && 2 === substr_count( $w53_ucw, 'self::is_entity_type( $post->post_type )' );
};
tp441_mut( 'بند ۱۲ و ۱۳: user-core روی خبر/مقاله بار می‌شود؛ ثبت نظر، ذخیره و دنبال‌کردن این دو نوع را می‌پذیرد (یک قاعدۀ نوع) — جهش: اسکریپت فقط برای tp_', $w53_uc, $w53_uca,
	str_replace( 'ToPersia_JetReviews_Setup::allowed((string) $post->post_type)', 'false', $w53_uca ) );

/* ── بند ۱۳: موبایل ─────────────────────────────────────────────────── */
$w53_mob = function ( $j ) use ( $w53_art, $w53_ncss ) {
	return false !== strpos( $j, "var crumb  = page.querySelector( '[data-tp-actionbar-slot]' ) || page.querySelector( '.tp-crumb' );" )   /* همان اسکریپت «نوار کنش» */
		&& false !== strpos( $j, "var narrow = window.matchMedia( '(max-width: 720px)' );" )
		&& false !== strpos( $w53_art, '<div class="tp-actionbar-slot" data-tp-actionbar-slot></div>' )
		&& strpos( $w53_art, '<div class="tp-actionbar-slot" data-tp-actionbar-slot></div>' ) < strpos( $w53_art, ". '<div class=\"tp-content-grid\">'" )   /* بالای محتوا، مثل صفحۀ استان */
		&& false !== strpos( $w53_ncss, '.tp-news-single .tp-rail > #tp-actions{order:-1}' )
		&& false !== strpos( $w53_ncss, '.tp-news-single .tp-actionbar-slot:empty{display:none}' );
};
tp441_mut( 'بند ۱۳: موبایل — جعبۀ اشتراک/ذخیره/دنبال‌کردن با همان اسکریپت به بالای محتوا می‌آید (نه درون سربرگ)؛ دسکتاپ نخستین کارت ستون کناری — جهش: بی جای‌نگهدار', $w53_mob, $w53_zjs,
	str_replace( "page.querySelector( '[data-tp-actionbar-slot]' ) || ", '', $w53_zjs ) );
$w53_css = function ( $c ) {
	$a   = strpos( $c, '/* P5-D573 — اسکلت همان صفحۀ استان است' );
	$b   = strpos( $c, '/* P5-D579 — ردیف تراشه' );
	$blk = ( false !== $a && false !== $b ) ? substr( $c, $a, $b - $a ) : '';
	return '' !== $blk && 0 === preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|letter-spacing/i', (string) preg_replace( '/#tp-actions/', '', $blk ) )   /* فقط توکن */
		&& false === strpos( $c, '.tp-news-cols{' ) && false === strpos( $c, '.tp-news-aside' )                                         /* شبکۀ جدای P5-D568 رفت */
		&& false !== strpos( $blk, '.tp-main-col .tp-news-grid{grid-template-columns:repeat(2,minmax(0,1fr))}' )
		&& (bool) preg_match( '/@media \(max-width:600px\)\{\s*html \.tp-page\.tp-news-single \.tp-main-col \.tp-news-grid\{grid-template-columns:minmax\(0,1fr\)\}/', $blk );
};
tp441_mut( 'بند ۱۰ و ۱۳ CSS: شبکه و ستون‌ها از قالب موجودیت (بی .tp-news-cols)؛ مرتبط‌ها ۲ ستون، موبایل ۱؛ فقط توکن — جهش: رنگ خام', $w53_css, $w53_ncss,
	str_replace( '.tp-news-single .tp-actionbar-slot:empty{display:none}', '.tp-news-single .tp-actionbar-slot:empty{display:none;color:#333}', $w53_ncss ) );

/* ── بند ۱۴: نویسندۀ اسکیمای مقاله ───────────────────────────────────── */
if ( ! function_exists( 'topersia_brand_names' ) ) {
	require_once $w53_root . '/plugin/topersia-core/includes/topersia-brand.php';
}
if ( ! function_exists( 'topersia_schema_article_author' ) ) {
	foreach ( array( 'topersia_schema_org_words', 'topersia_schema_brand_names', 'topersia_schema_is_org_name', 'topersia_schema_article_author' ) as $w53_f ) {
		eval( $w53_fn( $w53_sch, $w53_f ) );   // phpcs:ignore — تابع خالص همین مخزن
	}
}
$w53_org = array( '@id' => 'https://topersia.com/#organization' );
t( 'بند ۱۴ رفتار: خالی یا نام برند ← همان Organization سایت؛ نام سازمان ← Organization با نام؛ فقط نام شخص ← Person',
	$w53_org === topersia_schema_article_author( '', $w53_org ) && $w53_org === topersia_schema_article_author( '  ', $w53_org )
	&& $w53_org === topersia_schema_article_author( 'ToPersia', $w53_org ) && $w53_org === topersia_schema_article_author( topersia_brand_names()['fa'], $w53_org ) && $w53_org === topersia_schema_article_author( topersia_brand_names()['ar'], $w53_org )
	&& array( '@type' => 'Organization', 'name' => 'خبرگزاری ایرنا' ) === topersia_schema_article_author( 'خبرگزاری ایرنا', $w53_org )
	&& array( '@type' => 'Organization', 'name' => 'Tehran Times' ) === topersia_schema_article_author( 'Tehran Times', $w53_org )
	&& array( '@type' => 'Organization', 'name' => 'وزارة السياحة' ) === topersia_schema_article_author( 'وزارة السياحة', $w53_org )
	&& array( '@type' => 'Person', 'name' => 'علی رضایی' ) === topersia_schema_article_author( 'علی رضایی', $w53_org )
	&& array( '@type' => 'Person', 'name' => 'Jane Timeson' ) === topersia_schema_article_author( 'Jane Timeson', $w53_org )              /* واژۀ کامل، نه زیررشته */
	&& array( '@type' => 'Person', 'name' => 'نمونه' ) === topersia_schema_article_author( 'نمونه', $w53_org ) );
$w53_auth = function ( $s ) {
	return false !== strpos( $s, "'author'           => topersia_schema_article_author( \$auth, \$org )," )
		&& false !== strpos( $s, "'publisher'        => array( '@id' => \$org['@id'] )," )
		&& false !== strpos( $s, "return array( '@type' => topersia_schema_is_org_name( \$name ) ? 'Organization' : 'Person', 'name' => \$name );" );
};
tp441_mut( 'بند ۱۴: Article.author از یک تابع — Organization برای خالی/سازمان، Person فقط برای شخص؛ ناشر همان Organization سایت — جهش: همیشه Person', $w53_auth, $w53_sch,
	str_replace( "'author'           => topersia_schema_article_author( \$auth, \$org ),", "'author'           => array( '@type' => 'Person', 'name' => \$auth ),", $w53_sch ) );

/* ── بندهای ۱۶ و ۱۷: پاورقی المنتوری — چهار شورت‌کد، رنگ از توکن ───────── */
$w53_ft    = $w53_read( 'plugin/topersia-render/includes/topersia-g-footer.php' );
$w53_ftcss = $w53_read( 'plugin/topersia-render/assets/topersia-g-footer.css' );
$w53_tok   = $w53_read( 'plugin/topersia-render/assets/topersia-tokens.css' );
$w53_main  = $w53_read( 'plugin/topersia-render/topersia-render.php' );
if ( ! function_exists( 'topersia_demo_on' ) ) {
	require_once $w53_root . '/plugin/topersia-render/includes/topersia-g-demo.php';
}
if ( ! function_exists( 'topersia_g_sc_footer_ads' ) ) {
	require_once $w53_root . '/plugin/topersia-render/includes/topersia-g-footer.php';
}
$w53_ft_prev = isset( $GLOBALS['tp_options']['topersia_footer_blocks'] ) ? $GLOBALS['tp_options']['topersia_footer_blocks'] : null;
$w53_ft_set  = function ( $v ) { $GLOBALS['tp_options']['topersia_footer_blocks'] = $v; };
$w53_ad      = function ( $i, $on = true ) { return array( 'img' => 100 + $i, 'url' => 'https://ad' . $i . '.example/', 'alt' => 'ad ' . $i, 'on' => $on ); };

$w53_ok = true;
for ( $n = 0; $n <= 4; $n++ ) {
	$ads = array();
	for ( $i = 0; $i < 4; $i++ ) {
		$ads[] = $i < $n ? $w53_ad( $i ) : $w53_ad( $i, false );
	}
	$w53_ft_set( array( 'ads' => $ads, 'demo_ads' => 0 ) );
	$h = topersia_g_sc_footer_ads();
	$w53_ok = $w53_ok && ( 0 === $n ? '' === $h
		: ( false !== strpos( $h, 'tp-ft-ads--n' . $n . '"' ) && $n === substr_count( $h, '<a class="tp-ft-ad"' )
			&& $n === substr_count( $h, 'rel="sponsored noopener"' ) && $n === substr_count( $h, 'target="_blank"' ) && $n === substr_count( $h, 'tp-ft-ad__tag' )
			&& false !== strpos( $h, 'width="' ) ) );
}
$w53_ft_set( array( 'ads' => array( array( 'img' => 0, 'url' => 'https://x.example/', 'on' => 1 ), array( 'img' => 5, 'url' => '', 'on' => 1 ), array( 'img' => 5, 'url' => 'javascript:alert(1)', 'on' => 1 ), $w53_ad( 3, false ) ), 'demo_ads' => 0 ) );
$w53_ok = $w53_ok && '' === topersia_g_sc_footer_ads();
t( 'بند ۱۷ [tp_footer_ads] رفتار: ۰ تا ۴ — صفر هیچ خروجی، n ← tp-ft-ads--n{n}، هر کدام sponsored noopener + _blank + «تبلیغ» + ابعاد؛ غیرفعال، بی تصویر، بی نشانی و javascript: کنار می‌روند؛ پیش‌نمایش خاموش ← بی نمونه', $w53_ok );
tp441_mut( 'بند ۱۷: فقط تبلیغ فعالِ پر نمایش داده می‌شود — جهش: شرط «فعال» برداشته شود',
	function ( $s ) { return false !== strpos( $s, "return \$a['on'] && \$a['img'] > 0 && '' !== \$a['url'];" ); }, $w53_ft,
	str_replace( "return \$a['on'] && \$a['img'] > 0 && '' !== \$a['url'];", "return \$a['img'] > 0 && '' !== \$a['url'];", $w53_ft ) );

$w53_ft_set( array( 'partners' => array( array( 'img' => 7, 'name' => 'Alpha', 'url' => 'https://alpha.example/' ), array(), array( 'img' => 8, 'name' => 'Beta', 'url' => '', 'dark' => 1 ) ) ) );
$h = topersia_g_sc_footer_partners();
$w53_okp = 2 === substr_count( $h, '<li class="tp-ft-row__item">' ) && false !== strpos( $h, 'همکاران ما' ) && 1 === substr_count( $h, 'rel="noopener"' )
	&& false !== strpos( $h, 'tp-ft-logo tp-ft-logo--dark' ) && false !== strpos( $h, 'alt="Alpha"' );
$w53_ft_set( array() );
$w53_okp = $w53_okp && '' === topersia_g_sc_footer_partners() && '' === topersia_g_sc_footer_social() && '' === topersia_g_sc_footer_ads();
$w53_ft_set( array( 'social' => array( array( 'url' => 'https://www.instagram.com/x/' ), array( 'url' => 'ftp://bad' ), array( 'url' => 'https://t.me/x', 'label' => 'Telegram' ) ) ) );
$h = topersia_g_sc_footer_social();
$w53_okp = $w53_okp && 2 === substr_count( $h, 'tp-ft-social__a' ) && false !== strpos( $h, 'aria-label="instagram.com"' ) && false !== strpos( $h, 'aria-label="Telegram"' ) && false === strpos( $h, 'ftp:' );
$w53_ft_set( array( 'brand' => array( 'text_fa' => '' ) ) );
$h = topersia_g_sc_footer_brand();
$w53_okp = $w53_okp && false !== strpos( $h, 'توپرشیا مرجع جامع اطلاعات ایران' ) && false === strpos( $h, '<img' );
$w53_ft_set( array( 'brand' => array( 'logo' => 9, 'text_fa' => 'متن ویژه' ) ) );
$h = topersia_g_sc_footer_brand();
$w53_okp = $w53_okp && false !== strpos( $h, 'متن ویژه' ) && false !== strpos( $h, 'tp-ft-brand__logo' );
t( 'بند ۱۷ همکاران، شبکه‌ها، برند: بخش خالی ← هیچ خروجی؛ لوگوی بی تصویر کنار؛ لوگوی روشن tp-ft-logo--dark؛ برچسب شبکه از نام یا میزبان؛ فقط http(s)؛ متن برند از تنظیمات وگرنه پیش‌فرض کاتالوگ', $w53_okp );
if ( null === $w53_ft_prev ) {
	unset( $GLOBALS['tp_options']['topersia_footer_blocks'] );
} else {
	$w53_ft_set( $w53_ft_prev );
}

tp441_mut( 'بند ۱۷: چهار شورت‌کد ثبت و پرونده بار می‌شود؛ نمونۀ نمایشی فقط از topersia_demo_on — جهش: شرط پیش‌نمایش برداشته شود',
	function ( $s ) use ( $w53_main ) {
		return false !== strpos( $w53_main, "'includes/topersia-g-footer.php'" )
			&& 4 === preg_match_all( "/add_shortcode\( 'tp_footer_(ads|brand|partners|social)'/", $s )
			&& false !== strpos( $s, "return function_exists( 'topersia_demo_on' ) && topersia_demo_on();" );
	}, $w53_ft, str_replace( "return function_exists( 'topersia_demo_on' ) && topersia_demo_on();", 'return true;', $w53_ft ) );

$w53_ftok = function ( $css ) {
	$body = preg_replace( '#/\*.*?\*/#s', '', $css );
	return '' !== $body && false === strpos( $body, '!important' ) && ! preg_match( '/#[0-9a-fA-F]{3,8}\b|rgba?\(/', $body )
		&& false !== strpos( $body, 'html:root [data-elementor-type="footer"]{' ) && false !== strpos( $body, 'var(--tp-ft-row-h)' )
		&& preg_match( '/\.tp-ft-ads--n4\{--tp-ft-ad-cols:4/', $body ) && preg_match( '/@media \(max-width:767px\)\{\s*\.tp-ft-ads--n3,\.tp-ft-ads--n4\{--tp-ft-ad-cols:2/', $body );
};
tp441_mut( 'بند ۱۶ CSS پاورقی: محدود به [data-elementor-type="footer"]، بی !important، بی رنگ خام (فقط --tp-ft-*)، ردیف هم‌ارتفاع، ۴ ستون و ۲×۲ همراه — جهش: رنگ خام', $w53_ftok, $w53_ftcss,
	str_replace( 'color:var(--tp-ft-link);', 'color:#7AD4CD;', $w53_ftcss ) );
tp441_mut( 'بند ۱۶ توکن: --tp-ft-* در یک بلوک برای هر سه حالت — جهش: حالت تیره جا بماند',
	function ( $s ) { return (bool) preg_match( '/html\[data-tp-mode="evening"\],\s*html\[data-tp-mode="dark"\]\{\s*--tp-ft-bg:/', $s ); }, $w53_tok,
	preg_replace( '/,\s*html\[data-tp-mode="dark"\]\{\s*--tp-ft-bg:/', '{--tp-ft-bg:', $w53_tok ) );

tp441_mut( 'بند ۱۹ گرم‌کن: شمار ساخت فقط پس از نوشتن واقعی قطعه (درون ob_start) و فقط برای مکانی که در پالایش است (پرکاربرد در کش، نه شمارندۀ بازدید) — جهش: بی اعتبارسنجی',
	function ( $s ) {
		$flat = (string) preg_replace( '/\s+/', ' ', $s );
		return false !== strpos( $flat, "if ( false !== file_put_contents( \$dir . '/' . \$key . '.html', \$html, LOCK_EX ) && \$place ) { topersia_frag_built( \$place );" )
			&& 1 === substr_count( $s, 'topersia_frag_built( $place )' )
			&& (bool) preg_match( '/function topersia_frag_built\( \$place_id \) \{.*?topersia_g_news_filter_options\(\).*?if \( ! \$valid \) \{\s*return;/s', $s )
			&& false === strpos( $s, "\xD9\xBE\xD8\xB1\xD8\xA8\xD8\xA7\xD8\xB2\xD8\xAF\xDB\x8C\xD8\xAF" );   /* واژۀ ممنوع بند ۱۹c، به بایت تا در خود آزمون هم نیاید */
	}, $w53_nui, str_replace( 'if ( ! $valid ) {', 'if ( false ) {', $w53_nui ) );
