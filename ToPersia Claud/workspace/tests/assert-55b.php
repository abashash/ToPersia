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
