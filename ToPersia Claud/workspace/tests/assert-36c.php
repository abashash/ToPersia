<?php
/**
 * دستهٔ 36-ج — ایرادهای صفحهٔ ایران (P5-D479). از assert.php بار می‌شود؛ PHP خالص با stub.
 *
 * واحد پول «ریال» از currency_note کوتاه · منطقهٔ زمانی فقط در «اطلاعات جغرافیایی» (assert-35) · خلاصهٔ کوتاهِ بلندتر از سقف
 * هیرو در «درباره» کامل · مرز کشور = اجتماع مرز استان‌ها (شطرنجی، بی منبع تازه) · هشدارهای درون‌ریز (منبع‌ها و سقف واژهٔ توضیح).
 * رفتار روی صفحهٔ واقعی: tests/iran-local.php.
 */

echo "\nدستهٔ 36-ج — صفحهٔ ایران  P5-D479\n" . str_repeat( '─', 78 ) . "\n";

/* ── واحد پول: currency_note فقط یک نام کوتاه («ریال») ← همان نام؛ بی رابطه ── */
$GLOBALS['tp_posts'][9601] = array( 'type' => 'tp_country', 'title' => 'کشور آزمون ارز', 'url' => 'https://topersia.com/country/cu/' );
$GLOBALS['tp_meta'][9601]  = array( 'currency_note' => 'ریال' );
$cu_plain = topersia_g_stat_derived( 9601, 'currency:unit' );
$GLOBALS['tp_meta'][9601]  = array( 'currency_note' => 'یادداشت بلند دربارهٔ ارز که جمله است.' );
$cu_long  = topersia_g_stat_derived( 9601, 'currency:unit' );
$GLOBALS['tp_meta'][9601]  = array( 'currency_note' => 'ریال (IRR)' );
$cu_code  = topersia_g_stat_derived( 9601, 'currency:unit' );
$GLOBALS['tp_meta'][9601]  = array( 'currency_note' => '' );
$cu_none  = topersia_g_stat_derived( 9601, 'currency:unit' );
$cu36_ok = function ( $x ) {
	return is_array( $x[0] ) && 'ریال' === wp_strip_all_tags( $x[0][1] )          /* «ریال» ساده */
		&& null === $x[1]                                                          /* جمله ← هیچ (نه حدس) */
		&& is_array( $x[2] ) && 'ریال' === wp_strip_all_tags( $x[2][1] ) && false === strpos( $x[2][1], 'IRR' )   /* «ریال (IRR)» ← «ریال» */
		&& null === $x[3];                                                         /* بی یادداشت ← هیچ */
};
tp441_mut( 'واحد پول (سربرگ کشور): currency_note=«ریال» ← «ریال»؛ جمله ← هیچ؛ «ریال (IRR)» ← «ریال»؛ خالی ← هیچ — جهش: رفتار پیشین («ریال» ساده ← —) می‌افتد',
	$cu36_ok, array( $cu_plain, $cu_long, $cu_code, $cu_none ), array( null, $cu_long, $cu_code, $cu_none ) );

/* ── خلاصهٔ کوتاه: «درباره» کامل و بی سقف هیرو ── */
$sum36 = 'ایران سرزمینی کهن در جنوب‌غربی آسیا با وسعت بسیار زیاد و دارنده یکی از غنی‌ترین و دیرپاترین تمدن‌های بشری است. ' . str_repeat( 'این کشور پل ارتباطی میان شرق و غرب بوده است. ', 8 ) . 'پایان.';
$GLOBALS['tp_posts'][9602] = array( 'type' => 'tp_country', 'title' => 'کشور آزمون خلاصه', 'url' => 'https://topersia.com/country/sm/' );
$GLOBALS['tp_meta'][9602]  = array( 'short_summary' => $sum36 );
$intro36 = topersia_g_overview_intro( 9602 );
$intro_ok = function ( $h ) use ( $sum36 ) {
	return false !== strpos( $h, '<p class="tp-lead">' . esc_html( $sum36 ) . '</p>' )
		&& mb_strlen( $sum36, 'UTF-8' ) > TOPERSIA_HERO_LINE_MAX * 2                         /* بلندتر از سقف هیرو (D458) */
		&& false === strpos( $h, '…' );                                                         /* بی کوتاه‌سازی */
};
tp441_mut( 'خلاصهٔ کوتاه: متن کامل (چند برابر سقف هیرو) در آغاز «درباره» بی «…»؛ سقف هیرو فقط برای خط نام‌های هیرو است — جهش: برش به سقف هیرو می‌افتد',
	$intro_ok, $intro36, str_replace( esc_html( $sum36 ), esc_html( mb_substr( $sum36, 0, TOPERSIA_HERO_LINE_MAX ) ) . '…', $intro36 ) );

/* ── مرز کشور: اجتماع شطرنجی ── */
require_once dirname( __DIR__ ) . '/scripts/lib/tp-boundaries-lib.php';
$sq = function ( $x0, $y0, $x1, $y1 ) { return array( 'type' => 'Polygon', 'coordinates' => array( array( array( $x0, $y0 ), array( $x1, $y0 ), array( $x1, $y1 ), array( $x0, $y1 ), array( $x0, $y0 ) ) ) ); };
/* دو استان هم‌مرز با شکاف باریک (ساده‌سازی جدا لبهٔ مشترک را نقطه‌به‌نقطه یکی نمی‌گذارد) + یک جزیره دور */
$two   = tpb_union_raster( array( $sq( 50, 30, 52, 32 ), $sq( 52.003, 30, 54, 32 ), $sq( 56, 28, 56.5, 28.5 ) ), 0.01 );
$donut = array( 'type' => 'Polygon', 'coordinates' => array( $sq( 50, 30, 54, 34 )['coordinates'][0], array( array( 51, 31 ), array( 51, 33 ), array( 53, 33 ), array( 53, 31 ), array( 51, 31 ) ) ) );
$d_fill = tpb_union_raster( array( $donut, $sq( 51.2, 31.2, 51.3, 31.3 ) ), 0.01, true );
$d_none = tpb_union_raster( array( $donut ), 0.01, false );
$bbox36 = function ( $rings ) { $b = array( INF, INF, -INF, -INF ); foreach ( $rings as $r ) { foreach ( $r as $p ) { $b = array( min( $b[0], $p[0] ), min( $b[1], $p[1] ), max( $b[2], $p[0] ), max( $b[3], $p[1] ) ); } } return $b; };
$u_ok = function ( $x ) use ( $bbox36 ) {
	list( $two, $fill, $none ) = $x;
	$b = $bbox36( $two );
	return 2 === count( $two )                                                              /* دو استان به‌هم‌چسبیده ← یک حلقه + جزیره */
		&& abs( $b[0] - 50 ) < 0.02 && abs( $b[1] - 28 ) < 0.02 && abs( $b[2] - 56.5 ) < 0.02 && abs( $b[3] - 32 ) < 0.02
		&& 1 === count( $fill )                                                             /* سوراخ بسته پر می‌شود (بی لکهٔ داخلی) */
		&& 2 === count( $none );                                                            /* بی پرکردن: حلقهٔ سوراخ هم می‌ماند — شاهدِ اینکه پرکردن کار می‌کند */
};
tp441_mut( 'مرز کشور: اجتماع شطرنجی — دو استان با شکاف باریک یک حلقه، جزیره حلقهٔ جدا، سوراخ بسته پر؛ bbox درست — جهش: بی پرکردن سوراخ می‌افتد',
	$u_ok, array( $two, $d_fill, $d_none ), array( $two, $d_none, $d_none ) );

/* ── نقشه: entity_uid خالی ← کلید مرز از نوع و نامک ── */
$z36 = (string) file_get_contents( TP_PLUGIN_DIR . 'includes/topersia-g-zones.php' );
$fb_ok = function ( $src ) {
	return false !== strpos( $src, "\$uid  = ( '' !== \$seg && preg_match( '/^[a-z0-9-]+\$/', \$slug )" ) && false !== strpos( $src, "? \$seg . '__' . \$slug : '';" );
};
t( 'نقشهٔ کشور: بی entity_uid کلید مرز از «پایهٔ نوع__نامک» (country__iran) ساخته می‌شود تا نقشه مرز و fitBounds بگیرد — جهش: بی جایگزین می‌افتد',
	$fb_ok( $z36 ) && ! $fb_ok( str_replace( "? \$seg . '__' . \$slug : '';", "? '' : '';", $z36 ) ) );

/* ── درون‌ریز: هشدار منبع‌ها ── */
/* پروندهٔ بسته stub آزمون‌های دیگر را بازتعریف می‌کند؛ فقط تابع‌های هشدار از متن برداشته می‌شوند */
$pk36 = (string) file_get_contents( TP_PLUGIN_DIR . '../topersia-core/includes/topersia-pack.php' );
foreach ( array( 'topersia_pack_registrable', 'topersia_pack_host', 'topersia_pack_known_orgs', 'topersia_pack_source_warnings', 'topersia_pack_description_word_cap' ) as $fn36 ) {
	if ( ! function_exists( $fn36 ) && preg_match( '/\x0Afunction ' . $fn36 . '\(.*?\x0A\}\x0A/s', $pk36, $m36 ) ) { eval( $m36[0] ); }
}
$src_clean = array(
	array( 'source_name' => 'مرکز میراث جهانی یونسکو (UNESCO)', 'source_url' => 'https://whc.unesco.org/en/statesparties/ir' ),
	array( 'source_name' => 'مرکز آمار ایران', 'source_url' => 'https://www.amar.org.ir/' ),
	array( 'source_name' => 'Encyclopaedia Britannica', 'source_url' => 'https://www.britannica.com/place/Iran' ),
);
$src_same = array(
	array( 'source_name' => 'دانشنامه ایرانیکا', 'source_url' => 'https://www.iranicaonline.org/articles/a/' ),
	array( 'source_name' => 'دانشنامه ایرانیکا (۲)', 'source_url' => 'https://www.iranicaonline.org/articles/b/' ),
	array( 'source_name' => 'یک سازمان ناشناخته', 'source_url' => 'https://www.iranicaonline.org/articles/c/' ),
);
$src_mis = array(
	array( 'source_name' => 'مرکز میراث جهانی یونسکو (UNESCO)', 'source_url' => 'https://www.iranicaonline.org/articles/iran-i/' ),
	array( 'source_name' => 'مرکز آمار ایران', 'source_url' => 'https://www.amar.org.ir/' ),
	array( 'source_name' => 'Britannica', 'source_url' => 'https://www.britannica.com/x' ),
);
$src_sub = array(
	array( 'source_name' => 'UNESCO سایت‌ها', 'source_url' => 'https://whc.unesco.org/a' ), array( 'source_name' => 'UNESCO سایت ۲', 'source_url' => 'https://whc.unesco.org/b' ),
	array( 'source_name' => 'UNESCO ناملموس', 'source_url' => 'https://ich.unesco.org/c' ), array( 'source_name' => 'Wikipedia', 'source_url' => 'https://en.wikipedia.org/d' ),
);
$w_sub = topersia_pack_source_warnings( $src_sub );
$w_clean = topersia_pack_source_warnings( $src_clean );
$w_same  = topersia_pack_source_warnings( $src_same );
$w_mis   = topersia_pack_source_warnings( $src_mis );
$w_ok = function ( $x ) {
	return array() === $x[0]                                                                  /* منابع متنوع و درست ← بی هشدار */
		&& 1 === count( $x[1] ) && false !== strpos( $x[1][0], 'یک دامنه' )   /* ۳ از ۳ یک دامنه ← فقط هشدار الف (نام‌ها با دامنه می‌خوانند یا ناشناس‌اند) */
		&& 1 === count( $x[2] ) && false !== strpos( $x[2][0], 'UNESCO' ) && false !== strpos( $x[2][0], 'unesco.org' );    /* یونسکو با دامنهٔ ایرانیکا ← هشدار ب */
};
t( 'درون‌ریز: هشدار الف بر پایهٔ دامنهٔ ثبت‌شده — whc. و ich.unesco.org با هم بیش از نیمی ← یک هشدار (unesco.org)', 1 === count( $w_sub ) && false !== strpos( $w_sub[0], 'unesco.org' ) && false !== strpos( $w_sub[0], '3 از 4' ) );
tp441_mut( 'درون‌ریز: هشدار الف (بیش از نیمی از منبع‌ها یک دامنه) و ب (نام سازمان با دامنهٔ نشانی نمی‌خواند)؛ منابع درست بی هشدار؛ هیچ‌کدام مانع نیست — جهش: هشدار ب حذف شود می‌افتد',
	$w_ok, array( $w_clean, $w_same, $w_mis ), array( $w_clean, $w_same, array() ) );

/* ── درون‌ریز: سقف واژهٔ توضیح مشخصات ── */
$cap_ok = function ( $x ) {
	return 30 === $x[0] && 50 === $x[1] && 40 === $x[2] && 0 === $x[3] && 0 === $x[4];   /* «حداقل … ۳۰۰ تا ۷۰۰» = کمینه، نه سقف */
};
$caps = array(
	topersia_pack_description_word_cap( 'توضیح یک‌تا‌دو جمله‌ای ... بین ۱۵ تا ۳۰ کلمه. بدون تعریف و تمجید.' ),
	topersia_pack_description_word_cap( 'حداکثر ۵۰ کلمه' ),
	topersia_pack_description_word_cap( 'Short text, up to 40 words.' ),
	topersia_pack_description_word_cap( 'متنی بی سقف' ),
	topersia_pack_description_word_cap( 'بدنهٔ اصلی. حداقل طول بسته به موجودیت متفاوت است (معمولاً ۳۰۰ تا ۷۰۰ کلمه). با پاراگراف‌بندی منظم بنویسید.' ),
);
tp441_mut( 'درون‌ریز: سقف واژه از توضیح فیلد خوانده می‌شود — «بین ۱۵ تا ۳۰ کلمه» ← ۳۰ · «حداکثر ۵۰ کلمه» ← ۵۰ · «up to 40 words» ← ۴۰ · بی سقف ← ۰ — جهش: تنها کمینه خوانده شود می‌افتد',
	$cap_ok, $caps, array( 15, 50, 40, 0, 700 ) );

$pack36 = (string) file_get_contents( TP_PLUGIN_DIR . '../topersia-core/includes/topersia-pack.php' );
$hook_ok = function ( $src ) {
	return false !== strpos( $src, 'topersia_pack_description_word_cap( isset( $def[\'description\'] )' ) && false !== strpos( $src, 'topersia_pack_source_warnings( $fields[\'source_references\'] )' )
		&& false !== strpos( $src, '(مانع نیست)' );
};
t( 'درون‌ریز: هر دو هشدار در برنامهٔ هر پست می‌نشینند (notes) و مانع نیستند — جهش: قلاب منبع‌ها حذف شود می‌افتد',
	$hook_ok( $pack36 ) && ! $hook_ok( str_replace( 'topersia_pack_source_warnings( $fields[\'source_references\'] )', 'array()', $pack36 ) ) );

/* ── منابع (P5-D480): تا ۳۰ ردیف، ۱۰ دیدنی، بقیه در «همهٔ منابع» تاشوی HTML سرور ── */
$GLOBALS['tp_posts'][9603] = array( 'type' => 'tp_country', 'title' => 'کشور آزمون منابع', 'url' => 'https://topersia.com/country/src/' );
$mk_src = function ( $n ) { $r = array(); for ( $i = 1; $i <= $n; $i++ ) { $r[] = array( 'source_name' => 'منبع ' . $i, 'source_url' => 'https://example' . $i . '.org/p' ); } return $r; };
$sec36 = function ( $n ) use ( $mk_src ) { $GLOBALS['tp_meta'][9603] = array( 'source_references' => $mk_src( $n ) ); return topersia_g_sc_sources( array( 'id' => 9603 ) ); };
$s5 = $sec36( 5 ); $s23 = $sec36( 23 ); $s40 = $sec36( 40 );
$src_ok = function ( $x ) {
	list( $a, $b, $c ) = $x;
	$cnt = function ( $h, $re ) { return preg_match_all( $re, $h ); };
	return 0 === strpos( $a, '<section' ) && 5 === $cnt( $a, '#<li><a href="https://example\d+\.org/p"#' ) && false === strpos( $a, 'tp-src__all' )          /* ≤۱۰: بی تاشو */
		&& 1 === preg_match( '#<ol>(?:<li>.*?</li>){10}</ol><details class="tp-src__all"><summary>[^<]+</summary><ol start="11">(?:<li>.*?</li>){13}</ol></details>#su', $b )   /* ۲۳ ← ۱۰ + تاشوی ۱۳ */
		&& 1 === preg_match( '#<ol>(?:<li>.*?</li>){10}</ol><details class="tp-src__all"><summary>[^<]+</summary><ol start="11">(?:<li>.*?</li>){20}</ol></details>#su', $c )   /* ۴۰ ← سقف ۳۰ کل */
		&& 30 === $cnt( $c, '#<li><a href="https://example\d+\.org/p"#' ) && false === strpos( $c, 'example31.org' );
};
tp441_mut( 'منابع: ≤۱۰ بی تاشو؛ ۲۳ ردیف ← ۱۰ دیدنی + «همهٔ منابع» تاشوی ۱۳ ردیف (HTML سرور، ol start=11)؛ ۴۰ ردیف ← سقف ۳۰ — جهش: سقف ۳۰ حذف شود می‌افتد',
	$src_ok, array( $s5, $s23, $s40 ), array( $s5, $s23, $sec36( 40 ) . str_repeat( '<li><a href="https://example99.org/p">x</a></li>', 5 ) ) );
unset( $GLOBALS['tp_posts'][9603], $GLOBALS['tp_meta'][9603] );

unset( $GLOBALS['tp_posts'][9601], $GLOBALS['tp_posts'][9602], $GLOBALS['tp_meta'][9601], $GLOBALS['tp_meta'][9602] );
