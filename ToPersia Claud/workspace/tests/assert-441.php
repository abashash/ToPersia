<?php
/**
 * ادعاهای ۴٫۴٫۱ — فاز ۲ از `docs/SPRINT-4.4-PHASES.md`، بخش render (۲٫۰).
 *
 * از assert.php پس از assert-440 بار می‌شود و همان شمارنده‌ها و همان دادۀ
 * آزمون را دارد؛ هر دستکاری دادۀ آزمون همین‌جا برمی‌گردد.
 *
 * هر ادعای تازه آزمون جهش دارد (بند ۰-۹ · پذیرش فاز ۲): هر ادعا تابعی است
 * که یک بار روی خروجی واقعی می‌گذرد و یک بار روی نسخۀ جهش‌یافته — همان
 * باگ یا همان رفتار پیشین — و باید بیفتد. ادعایی که نمی‌تواند بیفتد،
 * اطمینان کاذب است. ادعاهای رفتاری (اندازۀ دیدنی، جهت) در behaviour.js.
 */

echo "\n۴٫۴٫۱ — فاز ۲، render\n" . str_repeat( '─', 78 ) . "\n";

$root441 = dirname( __DIR__ );
$css441  = file_get_contents( TP_PLUGIN_DIR . 'assets/topersia-g-template.css' );
$src441  = file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );

/** نیمۀ افزودۀ شیوه‌نامه — نیمۀ منتقل‌شده آینۀ مرجع است و قاعدۀ مرده‌اش می‌ماند (P5-D219). */
function tp441_added( $css ) {
	$a = strpos( $css, 'TP-ADDED-START' );
	$b = strpos( $css, 'TP-ADDED-END' );
	return ( false !== $a && false !== $b ) ? substr( $css, $a, $b - $a ) : '';
}

/** ادعا روی واقعی درست و روی جهش نادرست — هر دو لازم. */
function tp441_mut( $label, callable $claim, $real, $mutant, $note = '' ) {
	$ok  = (bool) $claim( $real );
	$bad = (bool) $claim( $mutant );
	t( $label, $ok && ! $bad, ( $ok ? '' : 'روی خروجی واقعی افتاد' ) . ( $bad ? ' · جهش را نگرفت' : '' ) . ( $note ? ' · ' . $note : '' ) );
}

/* ══ ۲٫۰٫۲ — کارت اسلایدر  P5-D266 ══════════════════════════════════════ */

/*
 * بلندی ثابت بدنه باید دقیقاً جمعِ خط‌ها و فاصله‌ها باشد؛ اندازه‌ها و
 * فاصله‌ها از خودِ شیوه‌نامه خوانده می‌شوند (نیمۀ منتقل‌شده)، ارتفاع خط‌ها
 * از قاعدۀ P5-D266. اگر روزی اندازۀ عنوان یا فاصلۀ پاورقی عوض شود و ثابتِ
 * calc نه، این ادعا می‌افتد.
 */
$card441 = function ( $css ) {
	$num = function ( $re ) use ( $css ) { return preg_match( $re, $css, $m ) ? (float) $m[1] : null; };
	$fs_tag   = $num( '/html \.tp-page \.tp-card-slide__tag\{[^}]*font-size:calc\(([\d.]+) \* var\(--tp-rem\)\)/' );
	$fs_title = $num( '/html \.tp-page \.tp-card-slide__title\{[^}]*font-size:calc\(([\d.]+) \* var\(--tp-rem\)\)/' );
	$fs_foot  = $num( '/html \.tp-page \.tp-card-slide__foot\{[^}]*font-size:calc\(([\d.]+) \* var\(--tp-rem\)\)/' );
	$lh_tag   = $num( '/html body \.tp-page \.tp-card-slide__tag\{line-height:([\d.]+);/' );
	$lh_title = $num( '/html body \.tp-page \.tp-card-slide__title\{line-height:([\d.]+)\}/' );
	$lh_foot  = $num( '/html body \.tp-page \.tp-card-slide__foot\{margin-block-start:auto;line-height:([\d.]+);/' );
	$k        = $num( '/html body \.tp-page \.tp-card-slide__body\{[^}]*block-size:calc\(60px \+ ([\d.]+) \* var\(--tp-rem\)\)/' );
	if ( null === $fs_tag || null === $fs_title || null === $fs_foot || null === $lh_tag || null === $lh_title || null === $lh_foot || null === $k ) {
		return false;
	}
	/* ۶۰ پیکسل: بالشتک ۱۶+۱۸، زیر برچسب ۶، زیر عنوان ۶، بالای پاورقی ۱۴ — همه از نیمۀ منتقل‌شده */
	$px = $num( '/html \.tp-page \.tp-card-slide__body\{[^}]*padding:(\d+)px \d+px \d+px/' ) + $num( '/html \.tp-page \.tp-card-slide__body\{[^}]*padding:\d+px \d+px (\d+)px/' )
		+ $num( '/html \.tp-page \.tp-card-slide__tag\{[^}]*margin-bottom:(\d+)px/' ) + $num( '/html \.tp-page \.tp-card-slide__title\{[^}]*margin:0 0 (\d+)px/' )
		+ $num( '/html \.tp-page \.tp-card-slide__foot\{[^}]*margin-top:(\d+)px/' );
	return abs( $fs_tag * $lh_tag + 2 * $fs_title * $lh_title + $fs_foot * $lh_foot - $k ) < 0.0005 && 60.0 === (float) $px
		&& false !== strpos( $css, 'html body .tp-page .tp-card-slide__art{position:relative;inset:auto;flex:none;block-size:200px}' )
		&& (bool) preg_match( '/@media\(max-width:720px\)\{\s*html body \.tp-page \.tp-card-slide__art\{block-size:180px\}\s*\}/', $css )
		&& false !== strpos( $css, 'html body .tp-page .tp-card-slide{justify-content:flex-start;min-block-size:0}' )
		&& false === strpos( tp441_added( $css ), 'tp-card-slide__desc' );
};
tp441_mut( 'کارت اسلایدر: هنر در جریان عادی ۲۰۰ (موبایل ۱۸۰)؛ بدنۀ ثابت = جمعِ دقیقِ یک خط برچسب، دو خط عنوان، یک خط پاورقی و ۶۰ پیکسل فاصله؛ بی توضیح  P5-D266',
	$card441, $css441, str_replace( 'html .tp-page .tp-card-slide__title{font-size:calc(1.05', 'html .tp-page .tp-card-slide__title{font-size:calc(1.15', $css441 ) );

$sl441 = r( 20, 'tp_slider', array( 'rel' => 'attractions' ) );
tp441_mut( 'کارت اسلایدر: هر کارت — هنر، سپس بدنه با برچسب · عنوان · پاورقی؛ هیچ `tp-card-slide__desc`  P5-D266',
	function ( $h ) {
		return (bool) preg_match_all( '#<(?:a|article) class="tp-card-slide"[^>]*><div class="tp-card-slide__art[^"]*" aria-hidden="true">.*?</div><div class="tp-card-slide__body">(?:<span class="tp-card-slide__tag">[^<]*</span>)?<h3 class="tp-card-slide__title">#su', $h, $m )
			&& substr_count( $h, 'class="tp-card-slide"' ) === count( $m[0] ) && false === strpos( $h, 'tp-card-slide__desc' );
	},
	$sl441, str_replace( '</h3>', '</h3><p class="tp-card-slide__desc">توضیح</p>', $sl441 ) );

/* ══ ۲٫۰٫۳ — بلوک هوش مصنوعی  P5-D267 ═══════════════════════════════════ */

$GLOBALS['tp_ai_fake'] = array(); $GLOBALS['tp_ai_calls'] = array(); $GLOBALS['tp_ai_internal'] = false;
$aiG441 = r( 20, 'tp_ai' );
$GLOBALS['tp_user'] = 7;
$aiL441 = r( 20, 'tp_ai' );
$GLOBALS['tp_user'] = 0;

$once441 = function ( $h ) {
	return 1 === substr_count( $h, 'این مقصد را در یک نگاه بفهمید' ) && 1 === substr_count( $h, '<h2>این مقصد را در یک نگاه بفهمید</h2>' )
		&& false === strpos( $h, 'tp-ai__kicker' )
		&& false === strpos( $h, 'خروجی از دادۀ ساختاریافتۀ همین صفحه ساخته می‌شود' )
		&& 1 === substr_count( $h, 'صرفاً از دادۀ موجود همین صفحه ساخته شده است' )
		&& (bool) preg_match( '#<div class="tp-ai__out" data-tp-ai-out.*?</div></div><p class="tp-ai__disclaimer">[^<]+</p></div>$#su', preg_replace( '#</section>$#', '', $h ) );
};
tp441_mut( 'AI: عنوان یک بار (بی کیکر)؛ جملۀ منشأ یک بار، کوچک، زیر خروجی — مهمان  P5-D267',
	$once441, $aiG441, str_replace( '<h2>', '<div class="tp-ai__kicker">این مقصد را در یک نگاه بفهمید</div><h2>', $aiG441 ) );
tp441_mut( 'AI: عنوان یک بار؛ جملۀ منشأ یک بار زیر خروجی — واردشده  P5-D267',
	$once441, $aiL441, str_replace( '</h2>', '</h2><p>خروجی از دادۀ ساختاریافتۀ همین صفحه ساخته می‌شود.</p>', $aiL441 ) );

$guest441 = function ( $h ) {
	return (bool) preg_match( '#<h2>[^<]+</h2><div class="tp-ai__actions"#u', $h )
		&& false === strpos( $h, 'tp-ai__cta-text' ) && false === strpos( $h, 'tp-ai__signup' ) && false === strpos( $h, 'data-tp-login=' )
		&& false === strpos( $h, 'tp-ai__login' ) && false === strpos( $h, '>ورود</a>' ) && false === strpos( $h, 'امکانات بیشتر' );
};
tp441_mut( 'AI: مهمان — بی دعوت و بی دکمهٔ «ثبت‌نام / ورود»؛ فقط خروجی ذخیره‌شده و «کپی خروجی»  P5-D358 (برداشتهٔ P5-D267)',
	$guest441, $aiG441, str_replace( '</h2>', '</h2><p class="tp-ai__cta-text">برای دسترسی به امکانات بیشتر هوش مصنوعی ثبت‌نام کنید</p>', $aiG441 ) );
tp441_mut( 'AI: واردشده — هیچ دعوت و هیچ دکمۀ ثبت‌نام  P5-D267',
	function ( $h ) { return '' !== $h && false === strpos( $h, 'tp-ai__cta-text' ) && false === strpos( $h, 'tp-ai__signup' ) && false === strpos( $h, 'امکانات بیشتر' ); },
	$aiL441, str_replace( '</h2>', '</h2><p class="tp-ai__cta-text">برای دسترسی به امکانات بیشتر هوش مصنوعی ثبت‌نام کنید</p>', $aiL441 ) );

tp441_mut( 'AI: دکمۀ دعوت هم‌قالبِ دکمه‌های ردیف (شعاع ۱۱، بلندی ۴۰) با --tp-accent و --tp-on-accent؛ فوکوس سفید؛ متن دعوت بی letter-spacing؛ هیچ قاعدۀ «ورود» متنی  P5-D267',
	function ( $css ) {
		return (bool) preg_match( '/html \.tp-page \.tp-ai__signup\{[^}]*min-block-size:40px;[^}]*border-radius:11px;background:var\(--tp-accent\);color:var\(--tp-on-accent\);[^}]*letter-spacing:normal\}/', $css )
			&& false !== strpos( $css, 'html .tp-page .tp-ai__signup:hover{background:var(--tp-accent-strong);border-color:var(--tp-accent-strong);color:var(--tp-on-accent)}' )
			&& false !== strpos( $css, 'html .tp-page .tp-ai__signup:focus-visible{outline:2px solid #fff;outline-offset:2px}' )
			&& (bool) preg_match( '/html \.tp-page \.tp-ai p\.tp-ai__cta-text\{[^}]*letter-spacing:normal\}/', $css )
			&& false === strpos( $css, 'tp-ai__login' );
	},
	$css441, $css441 . "\nhtml .tp-page .tp-ai__login{color:#fff;text-decoration:underline}" );

/* ══ ۲٫۰٫۴ — تاریخ راستی‌آزمایی  P5-D268 ═════════════════════════════════ */

$src20_441 = r( 20, 'tp_sources' );
$date441   = function ( $h ) {
	return false !== strpos( $h, '<div><span>وضعیت محتوا</span><b>راستی‌آزمایی‌شده<time class="tp-uid__date" datetime="2025-06-02">۱۲ خرداد ۱۴۰۴</time></b></div>' )
		&& false === strpos( $h, 'tp-verified' ) && false === strpos( $h, 'آخرین راستی‌آزمایی' );
};
tp441_mut( 'منابع: تاریخ راستی‌آزمایی زیر «راستی‌آزمایی‌شده» در همان خانه، با `<time datetime>`؛ نشان جداگانه نیست  P5-D268',
	$date441, $src20_441, str_replace( '</ol>', '</ol><span class="tp-verified">✓ آخرین راستی‌آزمایی: ۱۲ خرداد ۱۴۰۴</span>', $src20_441 ) );

$keep441 = $GLOBALS['tp_meta'][20]['last_verified_date'];
$GLOBALS['tp_meta'][20]['last_verified_date'] = '1749081600';
$ts441 = r( 20, 'tp_sources' );
unset( $GLOBALS['tp_meta'][20]['last_verified_date'] );
$nod441 = r( 20, 'tp_sources' );
$GLOBALS['tp_meta'][20]['last_verified_date'] = $keep441;
t( 'منابع: تاریخ timestamp هم ماشین‌خوان است (۱۵ خرداد ۱۴۰۴ ← 2025-06-05)؛ بی تاریخ فقط وضعیت  P5-D268',
	false !== strpos( $ts441, '<b>راستی‌آزمایی‌شده<time class="tp-uid__date" datetime="2025-06-05">۱۵ خرداد ۱۴۰۴</time></b>' )
	&& false !== strpos( $nod441, '<div><span>وضعیت محتوا</span><b>راستی‌آزمایی‌شده</b></div>' ) && false === strpos( $nod441, '<time' ) );

t( 'منابع: تاریخ متن ریز است و --tp-text می‌گیرد (P5-D184)، نمایش بلوکی زیر مقدار، مقدار به لبۀ پایانی  P5-D268',
	false !== strpos( $css441, 'html .tp-page .tp-uid__date{display:block;margin-block-start:2px;font-size:calc(.72 * var(--tp-rem));font-weight:600;color:var(--tp-text)}' )
	&& false !== strpos( $css441, 'html .tp-page .tp-uid b{text-align:end}' ) );

/* ══ ۲٫۰٫۵ — کاشی مسیرها  P5-D269 ════════════════════════════════════════ */

$p20_441 = r( 20, 'tp_page' );
$rt441   = preg_match( '#<section id="tp-routes".*?</section>#su', $p20_441, $rm441 ) ? $rm441[0] : '';
tp441_mut( 'مسیرها: کاشی P5-D244 در `[data-tp-tiles]` (سه ستون، دوستونۀ خودکار، «N مورد دیگر»)؛ نام پررنگ، زیرش ایستگاه‌ها؛ بی `.tp-routes`  P5-D269',
	function ( $h ) {
		return (bool) preg_match( '#<div class="tp-tiles" data-tp-tiles>(<a href="[^"]+" class="tp-tile"><b>[^<]+</b>(<span class="tp-tile__sub">[^<]+</span>)?</a>)+</div>#u', $h )
			&& false === strpos( $h, 'class="tp-routes"' ) && false === strpos( $h, 'tp-tile__k' );
	},
	$rt441, str_replace( 'class="tp-tiles"', 'class="tp-routes"', $rt441 ) );

/* بیش از شش مسیر: همان «N مورد دیگر» کاشی‌ها */
$keep_rel441 = $GLOBALS['tp_rel'];
for ( $i441 = 0; $i441 < 8; $i441 ++ ) {
	$GLOBALS['tp_posts'][ 1500 + $i441 ] = array( 'type' => 'tp_route', 'title' => 'مسیر آزمون ' . $i441, 'url' => 'https://topersia.com/route-' . $i441 . '/' );
	$GLOBALS['tp_rel']['172']['parents'][20][] = 1500 + $i441;
}
$rt8_441 = r( 20, 'tp_relgrid', array( 'rel' => 'routes' ) );
$GLOBALS['tp_rel'] = $keep_rel441;
t( 'مسیرها: شش کاشی پیدا، بقیه `hidden`، دکمۀ «N مورد دیگر» — همان قاعدۀ P5-D244  P5-D269',
	6 === preg_match_all( '#<a href="[^"]+" class="tp-tile"><b>#u', $rt8_441 ) && 3 === substr_count( $rt8_441, 'hidden data-tp-rest-item' )
	&& false !== strpos( $rt8_441, 'data-tp-more data-tp-step="6" data-tp-rest="3"' ) );

t( 'مسیرها: خطِ زیرِ نام متن ریز کاشی است (--tp-tile-text، P5-D184) با اندازه و بلندی خط برچسب کوچک (پذیرش با `.n-dir` مرجع جفتش می‌کند)؛ روی هاور رنگ نام  P5-D269',
	false !== strpos( $css441, 'html .tp-page .tp-tile__sub{font-size:calc(.6 * var(--tp-rem));color:var(--tp-tile-text);font-weight:700;line-height:1.2}' )
	&& false !== strpos( $css441, 'html .tp-page .tp-tile__k{font-size:calc(.6 * var(--tp-rem));color:var(--tp-tile-text);font-weight:700;line-height:1.2}' )
	&& false !== strpos( $css441, 'html .tp-page a.tp-tile[href]:hover .tp-tile__sub{color:var(--tp-primary-text)}' )
	&& false === strpos( tp441_added( $css441 ), '.tp-routes' ) );

/* ══ ۲٫۰٫۶ — تراز دوطرفه  P5-D270 ════════════════════════════════════════ */

/*
 * فهرست بسته: `tp-justify` فقط روی این چهار جا (و متن گفت‌وگوی پادکست در
 * فاز ۴). هر جای دیگر — سرفصل، برچسب، دکمه، کاشی — افتادن است.
 */
$just441 = function ( $pages ) {
	$allowed = array(
		'#^<article class="tp-longform tp-justify"#',
		'#^<p class="tp-justify">#',
	);
	$seen = array();
	foreach ( $pages as $h ) {
		preg_match_all( '#<[a-z0-9]+\b[^>]*\btp-justify\b[^>]*>#', $h, $m, PREG_OFFSET_CAPTURE );
		foreach ( $m[0] as $hit ) {
			$ok = false;
			foreach ( $allowed as $re ) {
				$ok = $ok || (bool) preg_match( $re, $hit[0] );
			}
			if ( ! $ok ) {
				return false;
			}
			/* جای هر <p class="tp-justify">: پاسخ پرسش، خروجی AI یا متن خبر */
			if ( 0 === strpos( $hit[0], '<p' ) ) {
				$before = substr( $h, max( 0, $hit[1] - 400 ), min( 400, $hit[1] ) );
				if ( preg_match( '#</summary>$#', $before ) ) { $seen['faq'] = 1; }
				elseif ( preg_match( '#aria-live="polite">$#', $before ) ) { $seen['ai'] = 1; }
				elseif ( preg_match( '#</time>$#', $before ) || preg_match( '#</h3>$#', $before ) ) { $seen['news'] = 1; }
				else { return false; }
			} else {
				$seen['about'] = 1;
			}
		}
	}
	return array( 'about' => 1, 'faq' => 1, 'ai' => 1, 'news' => 1 ) == $seen;
};
$GLOBALS['tp_filters']['topersia_g_news_pre_query'] = array( function ( $pre, $place ) {
	return 20 === (int) $place ? array( 'ids' => array( 901, 902 ), 'total' => 2 ) : $pre;
} );
$pages441 = array( r( 20, 'tp_page' ), r( 20, 'tp_news' ), r( 27, 'tp_page' ), r( 30, 'tp_page' ), r( 40, 'tp_page' ) );
unset( $GLOBALS['tp_filters']['topersia_g_news_pre_query'] );
tp441_mut( 'تراز دوطرفه: `tp-justify` فقط بدنۀ «درباره»، پاسخ پرسش، خروجی AI و متن خبر — هر چهار حاضر، هیچ جای دیگر  P5-D270',
	$just441, $pages441, array( str_replace( '<h2>', '<h2 class="tp-justify">', $pages441[0] ) ) + $pages441 );

t( 'تراز دوطرفه: یک کلاس — justify، خط آخر از آغاز، inter-word؛ سرفصل درون بدنه نه؛ هیچ قاعدۀ justify روی `p` سراسری  P5-D270',
	false !== strpos( $css441, 'html .tp-page .tp-justify{text-align:justify;text-align-last:start;text-justify:inter-word}' )
	&& (bool) preg_match( '/html \.tp-page \.tp-justify h2,\s*html \.tp-page \.tp-justify h3,\s*html \.tp-page \.tp-justify h4,\s*html \.tp-page \.tp-justify h5,\s*html \.tp-page \.tp-justify h6\{text-align:start;text-align-last:auto\}/', $css441 )
	&& 1 === substr_count( $css441, 'text-align:justify' ) );

/* ══ ۲٫۰٫۸ — شرط زبان WPML روی شناسۀ زبان دیگر  P5-D247 ═══════════════════
   یافتۀ دادۀ ترجمه: همگام‌ساز WPML جت‌انجین پس از هر ترجمه ردیف «ترجمه ←
   طرف مقابل» می‌سازد؛ صفحۀ فارسیِ هم‌مرز اصفهان آن را سه بار نشان داد
   (فارسی، انگلیسی، عربی). دروازه باید نسخۀ زبان جاری را برگرداند، یک بار.
   جهش: همان دروازۀ فاز ۱ که شناسۀ ورودی را برمی‌گرداند. */

$GLOBALS['tp_posts'][1601] = array( 'type' => 'tp_province', 'title' => 'Isfahan Province', 'url' => 'https://topersia.com/en/isfahan-province/' );
$GLOBALS['tp_posts'][1602] = array( 'type' => 'tp_province', 'title' => 'محافظة أصفهان', 'url' => 'https://topersia.com/ar/isfahan-province/' );
$GLOBALS['tp_posts'][1603] = array( 'type' => 'tp_route', 'title' => 'Silk Road', 'url' => 'https://topersia.com/en/silk-road/' );
$wpml441 = array(
	/* شناسه ← [زبان خودش، نسخه در هر زبان] */
	20 => array( 'fa', array( 'fa' => 20, 'en' => 1601, 'ar' => 1602 ) ),
	1601 => array( 'en', array( 'fa' => 20, 'en' => 1601, 'ar' => 1602 ) ),
	1602 => array( 'ar', array( 'fa' => 20, 'en' => 1601, 'ar' => 1602 ) ),
	66 => array( 'fa', array( 'fa' => 66, 'en' => 1603 ) ),
	1603 => array( 'en', array( 'fa' => 66, 'en' => 1603 ) ),
	65 => array( 'fa', array( 'fa' => 65 ) ),
);
$lang441 = 'fa';
$GLOBALS['tp_filters']['wpml_current_language'] = array( function () use ( &$lang441 ) { return $lang441; } );
$GLOBALS['tp_filters']['wpml_object_id']        = array( function ( $id, $type = '', $orig = false, $lang = null ) use ( $wpml441 ) {
	if ( ! isset( $wpml441[ (int) $id ] ) ) { return $id; }
	$v = $wpml441[ (int) $id ][1];
	return isset( $v[ $lang ] ) ? $v[ $lang ] : ( $orig ? $id : null );
} );
$gate441 = function ( callable $published ) use ( &$lang441 ) {
	$lang441 = 'fa';
	$fa      = $published( array( 65, 20, 1601, 1602, 1603 ) );
	$lang441 = 'en';
	$en      = $published( array( 20, 1602, 65, 66 ) );
	$lang441 = 'fa';
	return array( 65, 20, 66 ) === $fa && array( 1601, 1603 ) === $en;
};
/* دروازۀ فاز ۱: شناسۀ ورودی را نگه می‌داشت */
$old_gate441 = function ( array $ids ) use ( &$lang441 ) {
	$out = array();
	foreach ( $ids as $one ) {
		$one = absint( $one );
		if ( ! $one || isset( $out[ $one ] ) || ! topersia_g_is_live( $one ) ) { continue; }
		$here = absint( apply_filters( 'wpml_object_id', $one, get_post_type( $one ), false, $lang441 ) );
		if ( ! $here || ( $here !== $one && ! topersia_g_is_live( $here ) ) ) { continue; }
		$out[ $one ] = $one;
	}
	return array_values( $out );
};
$ok441  = $gate441( 'topersia_g_published' );
$bad441 = $gate441( $old_gate441 );

/* صفحه: هم‌مرزهای استان یزد (۶۵) — اصفهان و دو ترجمه‌اش همه رابطه دارند؛ یک کاشی */
$keep_rel441 = $GLOBALS['tp_rel'];
$GLOBALS['tp_rel']['102']['parents'][65]  = array( 20, 1601, 1602 );
$GLOBALS['tp_rel']['102']['children'][65] = array();
$yazd441 = r( 65, 'tp_relgrid', array( 'rel' => 'neighbors' ) );
$GLOBALS['tp_rel'] = $keep_rel441;
unset( $GLOBALS['tp_filters']['wpml_current_language'], $GLOBALS['tp_filters']['wpml_object_id'], $GLOBALS['tp_posts'][1601], $GLOBALS['tp_posts'][1602], $GLOBALS['tp_posts'][1603] );

t( 'منتشرشده: رابطه به ترجمۀ زبان دیگر ← نسخۀ زبان جاری، هر موجودیت یک بار (فارسی: اصفهان و دو ترجمه‌اش ← یک اصفهان؛ مسیر انگلیسی ← مسیر فارسی؛ انگلیسی: بی‌ترجمه‌ها نه)؛ جهش — دروازۀ فاز ۱ — می‌افتد  P5-D247',
	$ok441 && ! $bad441, ( $ok441 ? '' : 'دروازۀ فعلی نادرست' ) . ( $bad441 ? ' · جهش را نگرفت' : '' ) );
t( 'منتشرشده: صفحۀ هم‌مرزِ فارسی اصفهان را یک بار نشان می‌دهد، با نام و پیوند فارسی  P5-D247',
	1 === substr_count( $yazd441, '<b>استان اصفهان</b>' ) && false === strpos( $yazd441, 'Isfahan' ) && false === strpos( $yazd441, 'أصفهان' )
	&& 1 === substr_count( $yazd441, 'href="https://topersia.com/isfahan-province/"' ), mb_substr( strip_tags( $yazd441 ), 0, 160 ) );

/* ══ نسخه‌ها — چهار پروژه ═══════════════════════════════════════════════
   هسته در بکاپ سه عدد متفاوت داشت (سربرگ 4.10.0 · ثابت 4.8.0 · جایگزینِ
   برون‌بری لاگ 4.9.0)؛ از 4.11.0 هر سه یکی.  SPRINT-4.4 فاز ۲ */

$v441 = array(
	'render'     => array( TP_PLUGIN_DIR . 'topersia-render.php', '4.5.5', array( '/^ \* Version:\s*(\S+)/m', "/define\( 'TOPERSIA_RENDER_VERSION', '([^']+)' \)/" ) ),
	'user-core'  => array( dirname( TP_PLUGIN_DIR ) . '/topersia-user-core/topersia-user-core.php', '1.12.1', array( '/^ \* Version:\s*(\S+)/m', "/define\('TOPERSIA_USER_CORE_VERSION', '([^']+)'\)/" ) ),
	'summarizer' => array( dirname( TP_PLUGIN_DIR ) . '/topersia-ai-summarizer/topersia-ai-summarizer.php', '1.3.2', array( '/^\s*\*\s*Version:\s*(\S+)/m', "/define\(\s*'TP_AI_VERSION',\s*'([^']+)'/" ) ),
	'core'       => array( dirname( TP_PLUGIN_DIR ) . '/topersia-core/topersia-core.php', '4.14.0', array(
		'/^ \* Version:\s*(\S+)/m',
		"/define\( 'TOPERSIA_CORE_VERSION', '([^']+)' \);/",
		"/defined\( 'TOPERSIA_CORE_VERSION' \) \? TOPERSIA_CORE_VERSION : '([^']+)'/",
	) ),
);
$vbad441 = array();
foreach ( $v441 as $name => $spec ) {
	$src = is_file( $spec[0] ) ? file_get_contents( $spec[0] ) : '';
	$got = array();
	foreach ( $spec[2] as $re ) {
		$got[] = preg_match( $re, $src, $vm ) ? $vm[1] : '?';
	}
	if ( array_unique( $got ) !== array( $spec[1] ) ) {
		$vbad441[] = $name . ' ' . implode( '/', $got );
	}
}
t( 'نسخه: چهار پروژهٔ ما — هر جا که نسخه نوشته شده یکی (هسته: سربرگ، ثابت، برون‌بری لاگ)',
	array() === $vbad441, implode( ' · ', $vbad441 ) );
