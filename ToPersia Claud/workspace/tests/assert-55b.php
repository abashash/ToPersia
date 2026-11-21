<?php
/**
 * دستۀ 55-ب (P5-D640 تا P5-D644) — از assert.php بار می‌شود؛ فقط خواندن کد، هر ادعا با جهش.
 * رفتار روی وردپرس واقعی: tests/season-resave-local.js (ذخیرۀ مجدد در پیشخوان) · tests/w55-local.js --only=toolbar ·
 * گیت پاورقی. پیکربندی زندۀ JetEngine با `tp-season-field.php check` سنجیده می‌شود، نه این‌جا.
 */

echo "\nدستۀ 55-ب — فصل خالی، فیلتر موبایل، رجیستری پاورقی، منوی اصلی\n" . str_repeat( '─', 78 ) . "\n";

$w55b_root = dirname( __DIR__ );
$w55b_read = function ( $p ) use ( $w55b_root ) {
	return (string) @file_get_contents( $w55b_root . '/' . $p );
};

/* ── بند ۲ (P5-D644): «بهترین فصل» گزینۀ خالی «—» دارد، اجباری نیست، پیش‌فرض خالی ──────────────
 * tp-season-field.php فقط با wp eval-file بار می‌شود: سه تابع هدف از سورس بیرون کشیده و همان کد اجرا می‌شود (نه رونوشت). */
$w55b_sf     = $w55b_read( 'scripts/tp-season-field.php' );
$w55b_sf_fns = function ( $src ) {
	if ( ! preg_match( "/const TP_SF_PLACEHOLDER = '([^']*)';/u", $src, $ph ) ) {
		return null;
	}
	$body = array();
	foreach ( array( 'tp_sf_multi', 'tp_sf_required', 'tp_sf_ok' ) as $fn ) {
		if ( ! preg_match( '/function ' . $fn . '\( array \$f \) \{\n(.*?)\n\}/s', $src, $m ) ) {
			return null;
		}
		$body[ $fn ] = str_replace( array( 'tp_sf_multi(', 'tp_sf_required(', 'TP_SF_PLACEHOLDER' ), array( '$multi(', '$req(', var_export( $ph[1], true ) ), $m[1] );
	}
	$multi = eval( 'return function ( array $f ) {' . $body['tp_sf_multi'] . '};' );   // phpcs:ignore Squiz.PHP.Eval
	$req   = eval( 'return function ( array $f ) {' . $body['tp_sf_required'] . '};' );   // phpcs:ignore Squiz.PHP.Eval
	return eval( 'return function ( array $f ) use ( $multi, $req ) {' . $body['tp_sf_ok'] . '};' );   // phpcs:ignore Squiz.PHP.Eval
};
$w55b_sf_ok = function ( $src ) use ( $w55b_sf_fns ) {
	$ok = $w55b_sf_fns( $src );
	if ( ! $ok ) {
		return false;
	}
	$single = array( 'type' => 'select', 'is_multiple' => false, 'is_required' => false, 'default_val' => '', 'placeholder' => '—' );
	return $ok( $single )
		&& $ok( array_merge( $single, array( 'is_required' => 'false' ) ) )                       /* JetEngine رشته هم می‌نویسد */
		&& ! $ok( array_merge( $single, array( 'is_required' => true ) ) )                        /* اجباری ← نادرست */
		&& ! $ok( array_merge( $single, array( 'is_required' => 'true' ) ) )
		&& ! $ok( array_merge( $single, array( 'placeholder' => '' ) ) )                          /* بی گزینۀ خالی ← «بهار» برگزیده می‌شود */
		&& ! $ok( array_diff_key( $single, array( 'placeholder' => 1 ) ) )
		&& ! $ok( array_merge( $single, array( 'default_val' => 'spring' ) ) )                    /* پیش‌فرض یک فصل ← نادرست */
		&& $ok( array( 'type' => 'select', 'is_multiple' => true, 'is_required' => false ) )       /* چندانتخابی (کشور): بی انتخاب همان تهی */
		&& ! $ok( array( 'type' => 'select', 'is_multiple' => true, 'is_required' => true ) );
};
tp441_mut( 'بند ۲ (P5-D644): هدف «بهترین فصل» — غیراجباری، پیش‌فرض خالی، تک‌انتخابی با گزینۀ «—»؛ چندانتخابی فقط غیراجباری — جهش: بی شرط گزینۀ خالی (همان پیکربندی پیشین که «بهار» می‌نوشت)',
	$w55b_sf_ok, $w55b_sf, str_replace( "return tp_sf_multi( \$f ) || TP_SF_PLACEHOLDER === (string) ( \$f['placeholder'] ?? '' );", 'return true;', $w55b_sf ) );

$w55b_sf_apply = function ( $s ) {
	$a = strpos( $s, "if ( 'apply' === \$tp_mode ) {" );
	$r = strpos( $s, "if ( 'restore' === \$tp_mode ) {" );
	if ( false === $a || false === $r || $r < $a ) {
		return false;
	}
	$blk = substr( $s, $a, $r - $a );
	$put = strpos( $blk, 'file_put_contents( $tp_file' );
	$ver = strpos( $blk, "\$back['defs'] != json_decode" );
	$upd = strpos( $blk, 'update_option(' );
	return false !== $put && false !== $ver && false !== $upd && $put < $ver && $ver < $upd && 1 === substr_count( $blk, 'update_option(' )
		&& false !== strpos( $blk, "\t\t\t\$f['is_required'] = false;\n\t\t\t\$f['default_val'] = '';\n\t\t\tif ( ! tp_sf_multi( \$f ) ) {\n\t\t\t\t\$f['placeholder'] = TP_SF_PLACEHOLDER;" )
		&& false !== strpos( $s, "exit( 'check' === \$tp_mode && \$bad ? 1 : 0 );" )
		&& false !== strpos( $s, "if ( is_array( \$f ) && 'best_season' === ( \$f['name'] ?? '' ) ) {" );
};
tp441_mut( 'بند ۲: apply پیش از هر نوشتن پشتیبان می‌نویسد و بازمی‌خواند؛ فقط ردیف‌های best_season؛ check برای هر تعریف بی هدف کد ۱ — جهش: نوشتن پیش از پشتیبان',
	$w55b_sf_apply, $w55b_sf,
	preg_replace( "/(\t\\\$dump = array\()/", "\tupdate_option( TP_SF_OPTION, \$boxes );\n\$1", $w55b_sf, 1 ) );

/* ── P5-D640: روسی زبان چهارم — رشتۀ پیش از ۵۵-ب در خط‌پایۀ ثابت بی ترجمه مجاز؛ هر رشتۀ تازه ru می‌خواهد ──────────────
 * رفتار خودِ tp-i18n.php --check با خط‌پایه‌های گوناگون (TP_I18N_RU_BASELINE)؛ خروجی واقعی را assert-442 می‌سنجد. */
$w55b_ru_base = json_decode( $w55b_read( 'scripts/i18n/ru-baseline.json' ), true );
$w55b_ru_run  = function ( $base ) use ( $w55b_root ) {
	if ( ! is_array( $base ) || ! $base ) {
		return array( -1, '' );
	}
	$tmp = tempnam( sys_get_temp_dir(), 'tp55bru' ) . '.json';
	file_put_contents( $tmp, json_encode( array_values( $base ), JSON_UNESCAPED_UNICODE ) );
	putenv( 'TP_I18N_RU_BASELINE=' . $tmp );
	exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $w55b_root . '/scripts/tp-i18n.php' ) . ' --check 2>&1', $out, $code );
	putenv( 'TP_I18N_RU_BASELINE' );
	@unlink( $tmp );
	return array( (int) $code, implode( "\n", $out ) );
};
$w55b_ru_ok = function ( $base ) use ( $w55b_ru_run ) {
	list( $code, $out ) = $w55b_ru_run( $base );
	return 0 === $code && false !== strpos( $out, 'روسی' );
};
tp441_mut( 'P5-D640: روسی — رشتۀ بیرون از خط‌پایه بی ترجمۀ ru قرمز است («رشتۀ تازه») — جهش: خط‌پایه یک مدخل کم',
	$w55b_ru_ok, $w55b_ru_base, is_array( $w55b_ru_base ) ? array_slice( $w55b_ru_base, 1 ) : array() );
tp441_mut( 'P5-D640: خط‌پایۀ ru فقط کوتاه می‌شود — مدخل رشته‌ای که دیگر نیست قرمز است — جهش: یک مدخل ساختگی',
	$w55b_ru_ok, $w55b_ru_base, is_array( $w55b_ru_base ) ? array_merge( $w55b_ru_base, array( 'رشتۀ ساختگی که در هیچ پرونده‌ای نیست ۵۵ب' ) ) : array() );
$w55b_ru_new = is_array( $w55b_ru_base ) ? $w55b_ru_run( array_slice( $w55b_ru_base, 1 ) ) : array( -1, '' );
t( 'P5-D640: پیام خطای رشتۀ تازه نام تصمیم را می‌برد (کاربر می‌داند چرا قرمز است)', false !== strpos( $w55b_ru_new[1], 'رشتۀ تازه، P5-D640' ), mb_substr( $w55b_ru_new[1], 0, 160 ) );
$w55b_ru_po = $w55b_read( 'plugin/topersia-render/languages/topersia-ru_RU.po' );
t( 'P5-D640: کاتالوگ ru_RU ساخته شده — سه شکل جمع روسی در سربرگ', false !== strpos( $w55b_ru_po, 'nplurals=3;' ) && is_file( $w55b_root . '/plugin/topersia-render/languages/topersia-ru_RU.mo' ) && is_file( $w55b_root . '/plugin/topersia-render/languages/topersia-ru_RU.l10n.php' ) );

/* ── بند ۱ (P5-D642): فیلتر موبایل — لایۀ شناور زیر دکمه، استان (select) و شهر (combobox) جدا ──────────────
 * رفتار در مرورگر: w55-local --only=toolbar (کروم و فایرفاکس، چهار پهنا، fa/en/ar) و --only=search. این‌جا فقط کد، هر ادعا با جهش. */
$w55b_r   = 'plugin/topersia-render/';
$w55b_tpl = array(
	'hub-archive.php'     => array( $w55b_read( $w55b_r . 'templates/hub-archive.php' ), 'tp-hub-sort' ),
	'article-archive.php' => array( $w55b_read( $w55b_r . 'templates/article-archive.php' ), 'tp-news-sort' ),
	'news-archive.php'    => array( $w55b_read( $w55b_r . 'templates/news-archive.php' ), 'tp-news-sort' ),
);
$w55b_inpanel = function ( $t, $sort ) {
	$pan = strpos( $t, 'data-tp-filter-panel>' );
	$srt = strpos( $t, "topersia_g_news_select( '" . $sort . "'" );
	$go  = strpos( $t, '<div class="tp-news-filter__go">' );
	$end = strpos( $t, '</form>' );
	return false !== $pan && false !== $srt && false !== $go && false !== $end && $pan < $srt && $srt < $go && $go < $end;
};
foreach ( $w55b_tpl as $w55b_n => $w55b_t ) {
	tp441_mut( 'بند ۱: ' . $w55b_n . ' — مرتب‌سازی و «نمایش» درون ظرف [data-tp-filter-panel] (ردیف «مرتب‌سازی | نمایش» لایۀ شناور) — جهش: مرتب‌سازی بیرون از ظرف',
		function ( $t ) use ( $w55b_inpanel, $w55b_t ) { return $w55b_inpanel( $t, $w55b_t[1] ); }, $w55b_t[0],
		str_replace( "topersia_g_news_select( '" . $w55b_t[1] . "'", "topersia_g_news_select( 'tp-x-sort'", $w55b_t[0] ) );
}

$w55b_loc = $w55b_read( $w55b_r . 'includes/topersia-g-loc.php' );
tp441_mut( 'بند ۱: دو فیلد جدا — select استان (name=tp_prov، «همۀ استان‌ها») و combobox فقط شهر با برچسب «شهر» و نگه‌دار «نام شهر»؛ استان جاری در ناحیۀ جای‌گزینی — جهش: بی name=tp_prov',
	function ( $s ) {
		return false !== strpos( $s, '" name="tp_prov" data-tp-loc-prov-sel>' ) && false !== strpos( $s, "esc_html__( 'همۀ استان‌ها', 'topersia' )" )
			&& false !== strpos( $s, "esc_html__( 'شهر', 'topersia' ) . '</label>'" ) && false !== strpos( $s, "esc_attr__( 'نام شهر', 'topersia' )" )
			&& false !== strpos( $s, '<span hidden data-tp-loc-prov="' ) && false === strpos( $s, "'نام استان یا شهر'" );
	}, $w55b_loc, str_replace( '" name="tp_prov" data-tp-loc-prov-sel>', '" data-tp-loc-prov-sel>', $w55b_loc ) );
$w55b_prov_ok = function ( $s ) {
	return false !== strpos( $s, "if ( \$prov && topersia_g_loc_prov_of( \$row['id'] ) !== \$prov ) {\n\t\t\t\tcontinue;" )
		&& false !== strpos( $s, "'prov' => array( 'required' => false, 'type' => 'integer', 'default' => 0 )," )
		&& false !== strpos( $s, "if ( \$prov && ( 'tp_province' !== get_post_type( \$prov ) || ! topersia_g_published( array( \$prov ) ) ) ) {" )
		&& false !== strpos( $s, "( ! \$prov || topersia_g_loc_prov_of( \$row['id'] ) === \$prov )" );
};
tp441_mut( 'بند ۱: REST و تطبیق بی JS فقط شهرهای استان برگزیده؛ prov ناموجود یا منتشرنشده نادیده (۰) — جهش: بی پالایش استان در جست‌وجو',
	$w55b_prov_ok, $w55b_loc, str_replace( "if ( \$prov && topersia_g_loc_prov_of( \$row['id'] ) !== \$prov ) {\n\t\t\t\tcontinue;", "if ( false ) {\n\t\t\t\tcontinue;", $w55b_loc ) );
t( 'بند ۱: جهش — prov بی دروازۀ منتشرشده می‌افتد', ! $w55b_prov_ok( str_replace( ' || ! topersia_g_published( array( $prov ) )', '', $w55b_loc ) ) );

$w55b_news = $w55b_read( $w55b_r . 'includes/topersia-g-news.php' );
tp441_mut( 'بند ۱: بی JS — استان برگزیده و شهرِ بیرون از آن ← پالایش استان (شهر پیشین کنار می‌رود)؛ tp_prov متغیر پرس‌وجوست — جهش: شهر بیرون از استان می‌ماند',
	function ( $s ) {
		return false !== strpos( $s, "} elseif ( \$prov && ( ! \$id || topersia_g_loc_prov_of( \$id ) !== \$prov ) ) {\n\t\t\t\$id = \$prov;" )
			&& false !== strpos( $s, "\$vars[] = 'tp_prov';" );
	}, $w55b_news, str_replace( '( ! $id || topersia_g_loc_prov_of( $id ) !== $prov )', '( ! $id )', $w55b_news ) );

$w55b_njs = $w55b_read( $w55b_r . 'assets/topersia-g-news.js' );
tp441_mut( 'بند ۱: نشانی بی‌رفرش فقط tp_place — tp_prov حذف و (بی شهر) به tp_place تبدیل؛ کلید کش قطعه همان فهرست بسته می‌ماند — جهش: tp_prov در نشانی می‌ماند',
	function ( $j ) {
		return false !== strpos( $j, "u.searchParams.delete( 'tp_prov' );" ) && false !== strpos( $j, "if ( prov && ! u.searchParams.get( 'tp_place' ) ) {\n\t\t\tu.searchParams.set( 'tp_place', prov );" );
	}, $w55b_njs, str_replace( "u.searchParams.delete( 'tp_prov' );", '', $w55b_njs ) );
$w55b_focus = function ( $j ) {
	return false !== strpos( $j, "if ( open && first() ) {\n\t\t\t\t\tfirst().focus();" )
		&& false !== strpos( $j, 'moreBtn.focus();   /* فوکوس هرگز روی عنصر پنهان نمی‌ماند */' )
		&& false !== strpos( $j, "if ( ! morePanel.contains( e.target ) && ! moreBtn.contains( e.target ) ) {\n\t\t\t\t\tshut( false );" )
		&& false !== strpos( $j, "if ( 'Escape' !== e.key || ( ! morePanel.contains( e.target ) && e.target !== moreBtn ) || morePanel.querySelector( '.tp-loc__list:not([hidden])' ) ) {" );
};
tp441_mut( 'بند ۱: باز ← فوکوس نخستین فیلد؛ بستن (Escape · «نمایش» · کلیک بیرون با فوکوس درون) ← فوکوس به دکمه؛ کلیک درون هرگز نمی‌بندد — جهش: بی فوکوس هنگام باز',
	$w55b_focus, $w55b_njs, str_replace( "if ( open && first() ) {\n\t\t\t\t\tfirst().focus();", "if ( open && first() ) {\n\t\t\t\t\tvoid 0;", $w55b_njs ) );
t( 'بند ۱: جهش — Escape بی گارد فهرست مکان می‌افتد', ! $w55b_focus( str_replace( " || morePanel.querySelector( '.tp-loc__list:not([hidden])' )", '', $w55b_njs ) ) );

$w55b_ljs = $w55b_read( $w55b_r . 'assets/topersia-g-loc.js' );
tp441_mut( 'بند ۱: combobox شهر استانِ select را به REST می‌دهد و تغییر استان شهر پیشین را پیش از ارسال کنار می‌گذارد (capture) — جهش: بی پارامتر prov',
	function ( $j ) {
		return false !== strpos( $j, "u.searchParams.set( 'prov', sel.value );" )
			&& 1 === preg_match( "/document\.addEventListener\( 'change', function \( e \) \{\n\t\tvar sel = e\.target;\n\t\tif \( ! sel \|\| ! sel\.hasAttribute \|\| ! sel\.hasAttribute\( 'data-tp-loc-prov-sel' \).*?hid\.parentNode\.removeChild\( hid \);.*?\}, true \);/s", $j );
	}, $w55b_ljs, str_replace( "u.searchParams.set( 'prov', sel.value );", '', $w55b_ljs ) );

$w55b_hcss = $w55b_read( $w55b_r . 'assets/topersia-g-hub.css' );
$w55b_ovl  = function ( $c ) {
	$at = strpos( $c, '@media (max-width:720px){' . "\n" . '  /* دستۀ 55-ب بند ۱ (P5-D642) — لایۀ شناور' );
	if ( false === $at || ! preg_match( '/\{(.*?)\n\}/s', substr( $c, $at ), $m ) ) {
		return false;
	}
	$b = $m[1];
	return false !== strpos( $b, 'html.tp-js .tp-page.tp-news-page .tp-hub-more{position:absolute;z-index:40;inset-inline:0;inset-block-start:calc(100% + 8px);' )
		&& false !== strpos( $b, 'border:1px solid var(--tp-tile-border);border-radius:var(--tp-tile-radius,12px);background:var(--tp-bg);box-shadow:var(--tp-shadow)}' )
		&& false !== strpos( $b, ':is(.tp-news-bar__tools,.tp-news-filterrow):has([data-tp-filter-panel]){position:relative}' )
		&& false !== strpos( $b, '.tp-hub-more{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));' )
		&& false !== strpos( $b, '.tp-hub-more > .tp-news-filter__f--q{grid-column:1 / -1}' )
		&& 0 === preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(/i', $b );   /* رنگ و سایه فقط از توکن */
};
tp441_mut( 'بند ۱: ≤۷۲۰ لایۀ شناور absolute زیر ردیف ابزار (محتوا جابه‌جا نمی‌شود)، قاب و سایه و زمینه از توکن، شبکۀ دوستونه با جست‌وجوی تمام‌عرض — جهش: سایۀ خام',
	$w55b_ovl, $w55b_hcss, str_replace( 'box-shadow:var(--tp-shadow)}', 'box-shadow:0 8px 24px rgba(0,0,0,.2)}', $w55b_hcss ) );
t( 'بند ۱: جهش — لایه در جریان صفحه (بی absolute) می‌افتد', ! $w55b_ovl( str_replace( '.tp-hub-more{position:absolute;', '.tp-hub-more{position:static;', $w55b_hcss ) ) );
$w55b_pair = function ( $c ) {
	$at = strpos( $c, '@media (max-width:720px){' . "\n" . '  /* دستۀ 55-ب بند ۱ (P5-D642) — لایۀ شناور' );
	if ( false === $at || ! preg_match( '/\{(.*?)\n\}/s', substr( $c, $at ), $m ) ) {
		return false;
	}
	return false !== strpos( $m[1], '.tp-hub-more > .tp-news-filter__f:has(select[name="tp_sort"]){grid-column-start:1}' )
		&& false !== strpos( $m[1], '.tp-hub-more:not(:has(select[name="tp_kind"])) > .tp-news-filter__f:has(select[name="tp_when"]){grid-column:1 / -1}' );
};
tp441_mut( 'بند ۱: ≤۷۲۰ «مرتب‌سازی | نمایش» همیشه یک ردیف؛ /news/ بی «نوع مکان» ← «بازۀ زمانی» تمام‌عرض — جهش: مرتب‌سازی بی سطر تازه',
	$w55b_pair, $w55b_hcss, str_replace( '{grid-column-start:1}', '{}', $w55b_hcss ) );
t( 'بند ۱: جهش — «بازۀ زمانی» تنها بی تمام‌عرض می‌افتد', ! $w55b_pair( str_replace( '> .tp-news-filter__f:has(select[name="tp_when"]){grid-column:1 / -1}', '> .tp-news-filter__f:has(select[name="tp_when"]){}', $w55b_hcss ) ) );
$w55b_nui = $w55b_read( $w55b_r . 'includes/topersia-g-news-ui.php' );
tp441_mut( 'بند ۱: /news/ شیوه‌نامۀ هاب (دکمه و لایۀ شناور) را بار می‌کند — جهش: بی topersia-g-hub',
	function ( $s ) {
		return 1 === preg_match( "/if \( topersia_g_news_is_archive\(\) && ! topersia_g_news_is_article\(\) \) \{.*?wp_enqueue_style\( 'topersia-g-hub',[^\n]*\n\t\t\}\n\t\}, 31 \);/s", $s );
	}, $w55b_nui, preg_replace( "/\t\t\twp_enqueue_style\( 'topersia-g-hub',[^\n]*\n(\t\t\}\n\t\}, 31 \);)/", '$1', $w55b_nui ) );

/* ── بند ۳ (P5-D643) ← دستۀ 55-ه (P5-D651): رجیستری منوی پاورقی — چهار ستون × پنج، نوار پایین ───────────────────────────
 * ساختار ستون‌ها در assert-55 (بند ۱۰) و کاتالوگ چهارزبانه در assert-53؛ رفتار روی وردپرس واقعی (دقیقاً پنج یا استثنای صریح،
 * پیوند مرده، ستون خالی، نوار) در tests/footer-gate.js. */
require_once TP_PLUGIN_DIR . 'includes/topersia-g-home.php';
require_once TP_PLUGIN_DIR . 'includes/topersia-g-footer.php';
$w55b_ft  = $w55b_read( $w55b_r . 'includes/topersia-g-footer.php' );
$w55b_fn  = function ( $s, $sig ) {
	return preg_match( '/function ' . preg_quote( $sig, '/' ) . ' \{.*?\n\t}\n/s', $s, $m ) ? $m[0] : '';
};

t( 'بند ۳ · P5-D651: سهم ستون نوبتی — هر گروه دارای پیوند دست‌کم یک خانه، جمع هرگز بیش از ۵، گروه تهی صفر',
	array( 'exp' => 2, 'events' => 2, 'seasons' => 1 ) === topersia_g_ft_share( array( 'exp' => 4, 'events' => 5, 'seasons' => 4 ) )
	&& array( 'dest' => 5 ) === topersia_g_ft_share( array( 'dest' => 11 ) )
	&& array( 'culture' => 2, 'food' => 1, 'guide' => 1, 'mag' => 1 ) === topersia_g_ft_share( array( 'culture' => 9, 'food' => 1, 'guide' => 8, 'mag' => 2 ) )
	&& array( 'a' => 0, 'b' => 3 ) === topersia_g_ft_share( array( 'a' => 0, 'b' => 3 ) )
	&& array( 'exp' => 0, 'events' => 1, 'seasons' => 1 ) === topersia_g_ft_share( array( 'exp' => 0, 'events' => 1, 'seasons' => 1 ) ) );
tp441_mut( 'بند ۳ · P5-D651: سهم از TOPERSIA_FOOTER_COL_MAX (۵) و نوبتی؛ پیوندهای ستون (topersia_g_ft_col_links) هر گروه را به سهمش می‌بُرد و شورت‌کد همان را چاپ می‌کند — جهش: بی سقف ستون',
	function ( $s ) use ( $w55b_fn ) {
		$sh = $w55b_fn( $s, 'topersia_g_ft_share( array $have )' );
		$cl = $w55b_fn( $s, 'topersia_g_ft_col_links( $col )' );
		$sc = $w55b_fn( $s, 'topersia_g_sc_footer_menu( $atts = array() )' );
		return 5 === TOPERSIA_FOOTER_COL_MAX && false !== strpos( $s, "define( 'TOPERSIA_FOOTER_COL_MAX', 5 );" )
			&& false !== strpos( $sh, '$left = TOPERSIA_FOOTER_COL_MAX;' ) && false !== strpos( $sh, 'if ( $left > 0 && $take[ $g ] < $n ) {' )
			&& false !== strpos( $cl, "\$take = topersia_g_ft_share( array_map( 'count', \$lists ) );" ) && false !== strpos( $cl, 'foreach ( array_slice( $links, 0, $take[ $g ] ) as $it ) {' )
			&& false !== strpos( $sc, 'foreach ( topersia_g_ft_col_links( $key ) as $it ) {' );
	}, $w55b_ft, str_replace( 'foreach ( array_slice( $links, 0, $take[ $g ] ) as $it ) {', 'foreach ( $links as $it ) {', $w55b_ft ) );

$w55b_o = topersia_g_ft_opts( array(
	'off'   => array( 'type:tp_village', 'bogus:x', 'type:tp_village', array( 'type:tp_island' ), 'page:copyright' ),
	'picks' => array( 5, 7 ),
) );
t( 'بند ۳ · P5-D651: گزینۀ off فقط شناسۀ شناختۀ رجیستری، یکتا؛ «پیوندهای ویژه» برداشته شد (picks دیگر در گزینه نیست)؛ بی ورودی off تهی',
	array( 'type:tp_village', 'page:copyright' ) === $w55b_o['off'] && ! array_key_exists( 'picks', $w55b_o )
	&& array() === topersia_g_ft_opts( array() )['off'], wp_json_encode( $w55b_o ) );
tp441_mut( 'بند ۳: پیشخوان — خاموش = سطرهای فرستاده منهای تیک‌خورده، سپس پاک‌کن opts؛ هر سطر رجیستری یک تیک «نمایش»، بی خانۀ «پیوندهای ویژه» — جهش: خاموش از همۀ سطرها',
	function ( $s ) use ( $w55b_fn ) {
		$sv = $w55b_fn( $s, 'topersia_g_ft_admin_save()' );
		$pg = $w55b_fn( $s, 'topersia_g_ft_admin_page()' );
		return false !== strpos( $sv, 'return empty( $show[ $id ] ); } ) );' ) && false !== strpos( $sv, '$opts = topersia_g_ft_opts( $in );' ) && false !== strpos( $sv, "check_admin_referer( 'topersia_footer_save' );" )
			&& false !== strpos( $pg, '<input type="hidden" name="tp_ft[rows][]" value="\' . esc_attr( $r[\'id\'] ) . \'">' )
			&& false !== strpos( $pg, "\$cb( 'tp_ft[show][' . \$r['id'] . ']', ! in_array( \$r['id'], \$o['off'], true ) )" )
			&& false === strpos( $s, 'tp_ft[picks]' ) && false === strpos( $s, 'TOPERSIA_FOOTER_PICKS' );
	}, $w55b_ft, str_replace( 'return empty( $show[ $id ] ); } ) );', 'return true; } ) );', $w55b_ft ) );

tp441_mut( 'بند ۳: «پیوند واقعی» — هاب با topersia_g_home_real( …, false )، فصل با _tp_sample NOT EXISTS، واژه با شمار بی نمونه، شمار «مقصدها» با همان صافی — جهش: صافی نمونه باز',
	function ( $s ) use ( $w55b_fn ) {
		return false !== strpos( $w55b_fn( $s, 'topersia_g_ft_real_mq()' ), "return array( array( 'key' => '_tp_sample', 'compare' => 'NOT EXISTS' ) );" )
			&& false !== strpos( $w55b_fn( $s, 'topersia_g_ft_real_count( array $ids )' ), "! metadata_exists( 'post', (int) \$id, '_tp_sample' )" )
			&& false !== strpos( $w55b_fn( $s, 'topersia_g_ft_exp( $min = 1 )' ), 'topersia_g_ft_real_count( topersia_g_tag_items( $t->term_id ) ) >= max( 1, (int) $min )' )
			&& false !== strpos( $w55b_fn( $s, 'topersia_g_ft_type_count( $type )' ), "'meta_query'       => topersia_g_ft_real_mq()," );
	}, $w55b_ft, str_replace( "'compare' => 'NOT EXISTS' ) );\n\t}", "'compare' => 'EXISTS' ) );\n\t}", $w55b_ft ) );

tp441_mut( 'بند ۳: پیوند تکراری، تهی یا بیرون از میزبان سایت نمی‌آید؛ سطر خاموش پیشخوان رد می‌شود — جهش: بی حذف تکراری',
	function ( $s ) use ( $w55b_fn ) {
		$l = $w55b_fn( $s, 'topersia_g_ft_links( $group )' );
		return false !== strpos( $l, "|| in_array( \$r['id'], \$opts['off'], true ) ) {" )
			&& false !== strpos( $l, "if ( '' === \$it[0] || '' === \$it[1] || isset( \$seen[ \$it[1] ] ) || ( '' !== \$home && \$host !== (string) wp_parse_url( \$home, PHP_URL_HOST ) ) ) {" );
	}, $w55b_ft, str_replace( '|| isset( $seen[ $it[1] ] ) ', '', $w55b_ft ) );

tp441_mut( 'بند ۳ · P5-D651: هر ستون یک فهرست بی سرعنوان گروه (سرتیتر فقط عنوان toggle)، گروه هر آیتم در data-tp-ft-group؛ نوار پایین [tp_footer_bar] با nav برچسب‌دار و فقط صفحۀ منتشرشده — جهش: سرعنوان گروه برگردد',
	function ( $s ) use ( $w55b_fn ) {
		$sc = $w55b_fn( $s, 'topersia_g_sc_footer_menu( $atts = array() )' );
		$br = $w55b_fn( $s, 'topersia_g_sc_footer_bar()' );
		return false === strpos( $s, 'tp-ft-menu__h' ) && false === strpos( $s, 'aria-labelledby' )
			&& 1 === substr_count( $sc, '<ul class="tp-ft-menu__list" role="list">' ) && false !== strpos( $sc, 'data-tp-ft-group="\' . esc_attr( $it[2] ) . \'"' )
			&& false !== strpos( $s, "add_shortcode( 'tp_footer_bar', 'topersia_g_sc_footer_bar' );" )
			&& false !== strpos( $br, "topersia_g_ft_links( 'bar' )" ) && false !== strpos( $br, "esc_attr__( 'پیوندهای پایین پاورقی', 'topersia' )" ) && false !== strpos( $br, "if ( '' === \$items ) {\n\t\t\treturn '';" );
	}, $w55b_ft, str_replace( "\$items .= '<li class=\"tp-ft-menu__item\"", "\$items .= '<p class=\"tp-ft-menu__h\"></p><li class=\"tp-ft-menu__item\"", $w55b_ft ) );

$w55b_fcss = $w55b_read( $w55b_r . 'assets/topersia-g-footer.css' );
tp441_mut( 'بند ۳ · P5-D651: نوار پایین از توکن پاورقی (--tp-ft-link · --tp-ft-menu-fs · --tp-ft-menu-h · --tp-ft-gap)، بی رنگ خام؛ قاعدۀ سرعنوان گروه برداشته شد — جهش: رنگ خام',
	function ( $c ) {
		return false === strpos( $c, 'tp-ft-menu__h' )
			&& 1 === preg_match( '/\.tp-ft-bar__list\{display:flex;flex-wrap:wrap;align-items:center;gap:0 calc\(3 \* var\(--tp-ft-gap\)\);/', $c )
			&& 1 === preg_match( '/\.tp-ft-bar__a\{\s*display:inline-flex;\s*align-items:center;\s*min-block-size:var\(--tp-ft-menu-h\);\s*font-size:var\(--tp-ft-menu-fs\);\s*line-height:1\.4;\s*color:var\(--tp-ft-link\);\s*\}/', $c );
	}, $w55b_fcss, preg_replace( '/(\.tp-ft-bar__a\{[^}]*color:)var\(--tp-ft-link\);/', '$1#333;', $w55b_fcss ) );

/* ── بند ۴: منوی اصلی #19 — والد بی‌مقصد با زیرمنو دکمه، بی زیرمنو پنهان؛ ترجمۀ WPML از جدول ────────────────────────────
 * رفتار روی وردپرس واقعی: tests/nav-main-local.js (fa/en/ar × ۱۴۴۰ و ۳۹۰؛ دکمه با آیتم فرزند موقت). هم‌خوانی داده با هدف:
 * `wp eval-file scripts/tp-main-menu.php check`. بدنۀ تابع‌ها از سورس بیرون کشیده و همان کد اجرا می‌شود، پس جهش رفتار را عوض می‌کند. */
require_once TP_PLUGIN_DIR . 'includes/topersia-g-nav.php';
$w55b_nav  = $w55b_read( $w55b_r . 'includes/topersia-g-nav.php' );
$w55b_body = function ( $src, $sig, $params ) {
	if ( ! preg_match( '/function ' . preg_quote( $sig, '/' ) . ' \{\n(.*?)\n\t}\n/s', $src, $m ) ) {
		return null;
	}
	return eval( 'return function ( ' . $params . ' ) {' . $m[1] . '};' );   // phpcs:ignore Squiz.PHP.Eval
};
$w55b_it = function ( $id, $parent, $url, $type = 'custom' ) {
	return (object) array( 'ID' => $id, 'menu_item_parent' => $parent, 'url' => $url, 'type' => $type );
};
$w55b_items = array(
	$w55b_it( 1, 0, '#' ),                        // بی‌مقصد، بی فرزند ← پنهان
	$w55b_it( 2, 0, '#' ),                        // بی‌مقصد با فرزند ← می‌ماند (دکمه)
	$w55b_it( 3, 2, 'http://topersia.local/news/' ),
	$w55b_it( 4, 0, '' ),                         // بی‌مقصد که تنها فرزندش هم بی‌مقصد است ← هر دو پنهان (تکرار تا پایدار)
	$w55b_it( 5, 4, '#' ),
	$w55b_it( 6, 0, '#' ),                        // بی‌مقصد با بلوک وودمارت ← می‌ماند
	$w55b_it( 7, 0, '', 'post_type' ),            // صفحه/نوع — نشانی خودش را دارد
	$w55b_it( 8, 0, 'http://topersia.local/events/' ),
);
$w55b_nav_ids = function ( $fn ) use ( $w55b_items ) {
	$GLOBALS['tp_meta'][6]['_menu_item_block'] = '99';
	$out = is_callable( $fn ) ? array_map( function ( $i ) { return $i->ID; }, $fn( $w55b_items ) ) : null;
	unset( $GLOBALS['tp_meta'][6] );
	return $out;
};
tp441_mut( 'بند ۴: آیتم سفارشی «#»/تهی بی فرزند و بی بلوک برداشته می‌شود، تا پایدار شدن تکرار؛ با فرزند یا بلوک و آیتم غیر custom می‌ماند — جهش: یک دور',
	function ( $s ) use ( $w55b_body, $w55b_nav_ids ) {
		return array( 2, 3, 6, 7, 8 ) === $w55b_nav_ids( $w55b_body( $s, 'topersia_g_nav_objects( $items )', '$items' ) );
	}, $w55b_nav, str_replace( '} while ( count( $items ) < $before );', '} while ( false );', $w55b_nav ) );

$w55b_a = '<a href="#" class="woodmart-nav-link"><span class="nav-link-text">X</span></a><a href="#">Y</a>';
tp441_mut( 'بند ۴: والد بی‌مقصد دارای زیرمنو دکمه است — بی href، role="button" tabindex="0" aria-expanded="false" aria-haspopup="true"، کلاس وودمارت می‌ماند، فقط نخستین <a> — جهش: href می‌ماند',
	function ( $s ) use ( $w55b_body, $w55b_a ) {
		$fn  = $w55b_body( $s, 'topersia_g_nav_button( $html )', '$html' );
		$out = is_callable( $fn ) ? $fn( $w55b_a ) : '';
		return '<a class="woodmart-nav-link" role="button" tabindex="0" aria-expanded="false" aria-haspopup="true" data-tp-nav-btn><span class="nav-link-text">X</span></a><a href="#">Y</a>' === $out;
	}, $w55b_nav, str_replace( '(?:href|role|tabindex', '(?:role|tabindex', $w55b_nav ) );
t( 'بند ۴: start_el فقط آیتم بی‌مقصد را دکمه می‌کند؛ آیتم با مقصد دست‌نخورده',
	false !== strpos( topersia_g_nav_start_el( $w55b_a, $w55b_it( 2, 0, '#' ) ), 'role="button"' )
	&& $w55b_a === topersia_g_nav_start_el( $w55b_a, $w55b_it( 8, 0, 'http://topersia.local/events/' ) )
	&& $w55b_a === topersia_g_nav_start_el( $w55b_a, $w55b_it( 7, 0, '', 'post_type' ) ) );
tp441_mut( 'بند ۴: پروندۀ منو در فهرست بارگذاری render است — جهش: بیرون از فهرست',
	function ( $s ) {
		return false !== strpos( $s, "'includes/topersia-g-footer.php', 'includes/topersia-g-nav.php'," );
	}, $w55b_read( $w55b_r . 'topersia-render.php' ), str_replace( "'includes/topersia-g-nav.php', ", '', $w55b_read( $w55b_r . 'topersia-render.php' ) ) );

$w55b_mm_src = $w55b_read( 'scripts/i18n/main-menu.php' );
$w55b_mm_ok  = function ( $src ) {
	$tbl = eval( '?>' . $src );   // phpcs:ignore Squiz.PHP.Eval
	if ( ! is_array( $tbl ) || ! isset( $tbl['_menu'] ) || count( $tbl ) < 6 ) {
		return false;
	}
	foreach ( $tbl as $fa => $row ) {
		if ( ! is_array( $row ) || array( 'en', 'ar', 'ru' ) !== array_keys( $row )
			|| ! preg_match( '/^[A-Z][A-Za-z ]*$/', (string) $row['en'] )
			|| ! preg_match( '/^[\x{0621}-\x{064A} ]+$/u', (string) $row['ar'] )
			|| ! preg_match( '/^[\x{0400}-\x{04FF} ]+$/u', (string) $row['ru'] ) ) {
			return false;
		}
	}
	return true;
};
tp441_mut( 'بند ۴: جدول منوی اصلی — هر عنوان en (لاتین) · ar (حرف عربی، بی پ چ ژ گ ک ی فارسی) · ru (سیریلیک) — جهش: «ی» فارسی در عربی',
	$w55b_mm_ok, $w55b_mm_src, str_replace( "'دليل السفر'", "'دلیل السفر'", $w55b_mm_src ) );

$w55b_mm = $w55b_read( 'scripts/tp-main-menu.php' );
tp441_mut( 'بند ۴: tp-main-menu فقط topersia.local؛ apply نخست رونوشت را می‌نویسد و بازمی‌خواند، سپس هر نوشتن؛ نشانی دستی غیر «#» بازنویسی نمی‌شود — جهش: بازنویسی هر نشانی',
	function ( $s ) {
		$ap = (string) strstr( (string) strstr( $s, "if ( 'apply' === \$tp_mode ) {" ), "if ( 'restore' === \$tp_mode ) {", true );
		$bk = strpos( $ap, 'file_get_contents( $tp_file ) !== $tp_snap' );
		$w1 = strpos( $ap, 'update_post_meta(' );
		return false !== strpos( $s, "if ( 'topersia.local' !== (string) wp_parse_url( home_url(), PHP_URL_HOST ) ) {" )
			&& false !== $bk && false !== $w1 && $bk < $w1 && $bk < (int) strpos( $ap, 'wp_create_nav_menu(' )
			&& false !== strpos( $ap, "if ( '#' !== \$want && in_array( trim( \$it['url'] ), array( '', '#' ), true ) ) {" );
	}, $w55b_mm, str_replace( "if ( '#' !== \$want && in_array( trim( \$it['url'] ), array( '', '#' ), true ) ) {", "if ( '#' !== \$want ) {", $w55b_mm ) );
tp441_mut( 'بند ۴: مقصد آیتم هاب‌دار فقط از topersia_g_ft_menu_url (هاب با محتوای واقعی همان زبان)، وگرنه «#»؛ هر آیتم ترجمه هم‌trid اصل — جهش: نشانی هاب بی سنجش محتوا',
	function ( $s ) {
		return false !== strpos( $s, '$url = (string) topersia_g_ft_menu_url( $hub[0], $hub[1] );' )
			&& false !== strpos( $s, "return '' === \$url ? '#' : \$url;" )
			&& false !== strpos( $s, "'element_type' => 'post_nav_menu_item', 'trid' => \$trid, 'language_code' => \$l, 'source_language_code' => \$tp_fa" );
	}, $w55b_mm, str_replace( '$url = (string) topersia_g_ft_menu_url( $hub[0], $hub[1] );', '$url = (string) home_url( "/{$hub[1]}/" );', $w55b_mm ) );
