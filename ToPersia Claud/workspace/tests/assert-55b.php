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
