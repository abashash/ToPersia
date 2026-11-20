<?php
/**
 * دستۀ 55-د (P5-D645 تا D649) — از assert.php بار می‌شود. بند ۲ (P5-D649 ب): پیش‌بررسی Action Scheduler در tp-backup.
 * حکم تابع خالص است و با دادۀ ساختگی سنجیده می‌شود؛ بازپخش حادثهٔ ۲۰ نوامبر (rc=4) و جهش پنجره. وضعیت زنده:
 * `php scripts/tp-backup.php --as-check` (فقط می‌خواند).
 */

echo "\nدستۀ 55-د — پیش‌بررسی Action Scheduler پیش از پشتیبان\n" . str_repeat( '─', 78 ) . "\n";

require_once dirname( __DIR__ ) . '/scripts/lib/tp-backup-lib.php';

$w55d_now  = 1795000000;
$w55d_ret  = 2678400;
$w55d_fact = function ( array $ago, array $extra = array() ) use ( $w55d_now, $w55d_ret ) {
	/* $ago: چند ثانیه پیش آخرین تلاش هر ردیف complete بوده؛ مشمول = بیش از دورۀ نگه‌داری */
	return array_merge( array(
		'table'                  => true,
		'retention'              => $w55d_ret,
		'now'                    => $w55d_now,
		'purgeable_last_attempt' => array_map( function ( $s ) use ( $w55d_now ) { return gmdate( 'Y-m-d H:i:s', $w55d_now - $s ); }, $ago ),
		'stale_in_progress'      => 0,
		'pending_due'            => 0,
	), $extra );
};

$w55d_a = tpb_as_verdict( $w55d_fact( array( 3600, 86400 ) ), 1800 );
t( 'P5-D649 ب: ردیف‌های تازه (۱ ساعت و ۱ روز پیش) ← آرام؛ زمان تا نخستین مشمول‌شدن حساب می‌شود', $w55d_a['stable'] && 0 === $w55d_a['now'] && 0 === $w55d_a['soon'] && $w55d_ret - 86400 === $w55d_a['next_in'] );

$w55d_b = tpb_as_verdict( $w55d_fact( array( $w55d_ret + 60 ) ), 1800 );
t( 'P5-D649 ب: ردیفی که همین حالا مشمول است ← ناپایدار', ! $w55d_b['stable'] && 1 === $w55d_b['now'] );

/* بازپخش حادثه: ردیفی که ۵ دقیقه پیش از پایان دورۀ نگه‌داری است، میان اثر انگشت پیش و پس مشمول می‌شد */
$w55d_c = tpb_as_verdict( $w55d_fact( array( $w55d_ret - 300 ) ), 1800 );
$w55d_c0 = tpb_as_verdict( $w55d_fact( array( $w55d_ret - 300 ) ), 0 );
t( 'P5-D649 ب: بازپخش rc=4 — ردیف ۵ دقیقه به مشمول‌شدن با پنجرۀ ۳۰ دقیقه ← ناپایدار؛ جهش: پنجرۀ صفر آن را نمی‌بیند (همان کد ۴)', ! $w55d_c['stable'] && 1 === $w55d_c['soon'] && $w55d_c0['stable'] );

$w55d_e = tpb_as_verdict( $w55d_fact( array( $w55d_ret - 1800 ) ), 1800 );
$w55d_f = tpb_as_verdict( $w55d_fact( array( $w55d_ret - 1801 ) ), 1800 );
t( 'P5-D649 ب: مرز پنجره — درست در ۱۸۰۰ ثانیه ناپایدار، یک ثانیه بیشتر آرام', ! $w55d_e['stable'] && $w55d_f['stable'] );

$w55d_g = tpb_as_verdict( $w55d_fact( array(), array( 'stale_in_progress' => 2 ) ), 1800 );
t( 'P5-D649 ب: اجرای in-progress گیرکرده (پاک‌ساز failed می‌کند) ← ناپایدار', ! $w55d_g['stable'] && 2 === $w55d_g['stale'] );

$w55d_h = tpb_as_verdict( $w55d_fact( array(), array( 'pending_due' => 3 ) ), 1800 );
t( 'P5-D649 ب: pending سررسیده فقط هشدار است (به درخواست وب نیاز دارد) — آرام ولی در خروجی می‌آید', $w55d_h['stable'] && 3 === $w55d_h['pending_due'] && 1 === count( $w55d_h['lines'] ) );

$w55d_i = tpb_as_verdict( array( 'table' => true, 'now' => $w55d_now ), 1800 );
t( 'P5-D649 ب: جدول بی ردیف ← آرام و بی خطا', $w55d_i['stable'] && null === $w55d_i['next_in'] );

/* ── سیم‌کشی در tp-backup.php و فقط‌خواندن بودن واقعیت‌یاب ── */
$w55d_bk = (string) @file_get_contents( dirname( __DIR__ ) . '/scripts/tp-backup.php' );
$w55d_fs = (string) @file_get_contents( dirname( __DIR__ ) . '/scripts/lib/tp-backup-as.php' );
tp441_mut( 'P5-D649 ب: tp-backup پیش از ساخت پوشهٔ بسته پیش‌بررسی را اجرا می‌کند، ناپایدار ← کد ۵ مگر --allow-as-purge، و --as-check هیچ بسته‌ای نمی‌سازد — جهش: بی فراخوانی یا پس از ساخت بسته می‌افتد',
	function ( $s ) {
		$p = strpos( $s, 'tpb_as_preflight(' );
		$m = strpos( $s, "\$name = 'full-'" );
		return false !== $p && false !== $m && $p < $m
			&& false !== strpos( $s, "tpb_fail( 'Action Scheduler ناپایدار است" ) && false !== strpos( $s, "', 5 );" )
			&& false !== strpos( $s, "empty( \$opt['allow-as-purge'] )" )
			&& false !== strpos( $s, "if ( ! empty( \$opt['as-check'] ) ) { exit( \$as_ok ? 0 : 5 ); }" );
	}, $w55d_bk, str_replace( 'tpb_as_preflight(', 'tpb_as_preflight_off(', $w55d_bk ) );
t( 'P5-D649 ب: واقعیت‌یاب AS فقط SELECT می‌زند (هیچ INSERT/UPDATE/DELETE/DROP)', 1 === preg_match( '/SELECT COUNT/', $w55d_fs ) && 0 === preg_match( '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE)\b/i', $w55d_fs ) );

/* ── بند ۵ (P5-D646): اصلاح تصویر — نخست پشتیبان نوشته و بازخوانده می‌شود، سپس پاک (نه جایگزین)؛ alt تصویر ترکیبی «تصویرسازی» دارد ── */
$w55d_if = (string) @file_get_contents( dirname( __DIR__ ) . '/scripts/tp-image-fix.php' );
tp441_mut( 'P5-D646: tp-image-fix پیش از پاک‌کردن پشتیبان می‌نویسد و بازمی‌خواند، فقط delete_post_meta (بی update_post_meta تصویر)، فقط topersia.local، alt #3762 واژهٔ «تصویرسازی» دارد — جهش: پاک‌کردن پیش از پشتیبان می‌افتد',
	function ( $s ) {
		$w = strpos( $s, 'file_put_contents( $file' );
		$d = strpos( $s, 'delete_post_meta( $pid, $k );' );
		return false !== $w && false !== $d && $w < $d && false !== strpos( $s, "'topersia.local' !== wp_parse_url" )
			&& false !== strpos( $s, 'پشتیبان خوانده‌شده با دادهٔ زنده یکی نیست' ) && 0 === preg_match( '/update_post_meta\( \$pid/', $s )
			&& 1 === preg_match( "/3762 => '[^']*تصویرسازی/u", $s );
	}, $w55d_if, str_replace( 'file_put_contents( $file', 'xx_removed( $file', $w55d_if ) );

/* ── بند ۶ (P5-D649 الف): ستون پنجم «کاوش در ایران» با سرعنوان گروه نخستش «چهار فصل»؛ سه‌زبانه + ru؛ ترازبندی ردیف‌ها بی تغییر پهنا ── */
$w55d_ft  = (string) @file_get_contents( dirname( __DIR__ ) . '/plugin/topersia-render/includes/topersia-g-footer.php' );
$w55d_cat = (string) @file_get_contents( dirname( __DIR__ ) . '/scripts/i18n/topersia-render.php' );
$w55d_css = (string) @file_get_contents( dirname( __DIR__ ) . '/plugin/topersia-render/assets/topersia-g-footer.css' );
tp441_mut( 'P5-D649: عنوان ستون season «کاوش در ایران» (جدا از گروه) و گروه seasons «چهار فصل»؛ عنوان ستون از نخستین گروه نمی‌آید — جهش: ستون دوباره از عنوان گروه',
	function ( $s ) {
		return false !== strpos( $s, "'کاوش در ایران' => __( 'کاوش در ایران', 'topersia' )," )
			&& false !== strpos( $s, "'seasons' => array( __( 'چهار فصل', 'topersia' ), 'season' )," )
			&& false !== strpos( $s, "\$titles = array( 'season' => topersia_g_ft_toggle_titles()['کاوش در ایران'] );" )
			&& false !== strpos( $s, "isset( \$titles[ \$def[1] ] ) ? \$titles[ \$def[1] ] : \$def[0]" );
	}, $w55d_ft, str_replace( "isset( \$titles[ \$def[1] ] ) ? \$titles[ \$def[1] ] : \$def[0]", "\$def[0]", $w55d_ft ) );
t( 'P5-D649: «کاوش در ایران» و «چهار فصل» در کاتالوگ با en لاتین، ar بی حرف ویژهٔ فارسی و ru سیریلیک',
	1 === preg_match( "/'کاوش در ایران' => array\( 'en' => '[A-Za-z ]+', 'ar' => '[^']+', 'ru' => '[^']*\p{Cyrillic}[^']*' \)/u", $w55d_cat )
	&& 1 === preg_match( "/'چهار فصل' => array\( 'en' => '[A-Za-z ]+', 'ar' => '[^']+', 'ru' => '[^']*\p{Cyrillic}[^']*' \)/u", $w55d_cat )
	&& 0 === preg_match( '/[پچژگکی]/u', ( preg_match( "/'کاوش در ایران' => array\( 'en' => '[^']+', 'ar' => '([^']+)'/u", $w55d_cat, $w55d_m ) ? $w55d_m[1] : 'ی' ) ) );
tp441_mut( 'P5-D649: ردیف‌های منو روی شبکهٔ عمودی — هر پیوند یک گام --tp-ft-menu-h، سرعنوان گروه دو گام، فقط ≥۱۰۲۴؛ هیچ پهنای ستونی در بلوک تازه نیست — جهش: بی گام ثابت',
	function ( $c ) {
		$p = strpos( $c, 'ترازبندی ردیف‌های منوی پاورقی' );
		if ( false === $p ) { return false; }
		$b = substr( $c, $p );
		return 1 === preg_match( '/@media \(min-width:1024px\)\{\s*html:root \[data-elementor-type="footer"\] \.tp-ft-menu__a\{\s*display:flex;[^\n]*\n\s*block-size:var\(--tp-ft-menu-h\);/', $b )
			&& false !== strpos( $b, 'block-size:calc(2 * var(--tp-ft-menu-h));' ) && 0 === preg_match( '/(?<![-a-z])(?:width|inline-size|flex-basis):/', $b );
	}, $w55d_css, str_replace( 'block-size:var(--tp-ft-menu-h);', 'min-block-size:var(--tp-ft-menu-h);', $w55d_css ) );
