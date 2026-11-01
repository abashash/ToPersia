<?php
/**
 * P5-D499: uid با مبدأ ثابت. منطق خالص با فرایند جدا (تا ثابت مبدأ را بشود عوض کرد) و گارد متن منبع، با جهش.
 * از assert.php بار می‌شود؛ t و tp441_mut از آن‌جاست.
 */
echo "\nP5-D499 — uid با مبدأ ثابت\n";

$p499_root = dirname( __DIR__ );
$p499_core = (string) file_get_contents( $p499_root . '/plugin/topersia-core/topersia-core.php' );

/* فقط سه تابع خالص را از منبع واقعی برمی‌دارد */
$p499_fns = function ( $src ) {
	$out = '';
	foreach ( array( 'topersia_uid_path', 'topersia_uid_build', 'topersia_uid_key' ) as $fn ) {
		if ( preg_match( '/function ' . $fn . '\(.*?\n}\n/s', $src, $m ) ) { $out .= $m[0]; }
	}
	return $out;
};
$p499_run = function ( $src, $origin, $expr ) use ( $p499_fns ) {
	$f = tempnam( sys_get_temp_dir(), 'p499' );
	file_put_contents( $f, "<?php\ndefine( 'TOPERSIA_CANONICAL_ORIGIN', " . var_export( $origin, true ) . " );\ndefine( 'TOPERSIA_UID_BASE', rtrim( TOPERSIA_CANONICAL_ORIGIN, '/' ) . '/e' );\n" . $p499_fns( $src ) . "\necho json_encode( {$expr}, JSON_UNESCAPED_SLASHES );\n" );
	$o = (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $f ) );
	@unlink( $f );
	return json_decode( $o, true );
};
$p499_paths = function ( $src ) use ( $p499_run ) {
	return $p499_run( $src, 'https://topersia.com', "array( topersia_uid_path( 'http://topersia.local/e/province/isfahan' ), topersia_uid_path( 'https://topersia.com/e/province/isfahan/' ), topersia_uid_path( 'province/isfahan' ), topersia_uid_path( '/e/city/x' ), topersia_uid_path( 'https://topersia.com/id/x' ), topersia_uid_path( 'just-slug' ), topersia_uid_path( '' ) )" )
		=== array( 'province/isfahan', 'province/isfahan', 'province/isfahan', 'city/x', '', 'just-slug', '' );
};
tp441_mut( 'topersia_uid_path میزبان را نادیده می‌گیرد؛ مسیر و نامک همان می‌مانند؛ نشانی بیرون از /e/ خالی — جهش: حذف قاعدهٔ نشانی بیگانه', $p499_paths, $p499_core,
	str_replace( "if ( preg_match( '~^https?://~i', \$uid ) ) {\n\t\treturn '';", "if ( false ) {\n\t\treturn '';", $p499_core ) );

$p499_build = function ( $src ) use ( $p499_run ) {
	return 'https://topersia.com/e/city/x' === $p499_run( $src, 'https://topersia.com', "topersia_uid_build( 'http://topersia.local/e/city/x' )" )
		&& 'http://t.test/e/city/x' === $p499_run( $src, 'http://t.test/', "topersia_uid_build( 'city/x' )" )
		&& '' === $p499_run( $src, 'https://topersia.com', "topersia_uid_build( '' )" );
};
tp441_mut( 'topersia_uid_build همیشه مبدأ ثابت (قابل تغییر با ثابت)؛ ورودی محلی محلی نمی‌ماند؛ خالی خالی — جهش: برگرداندن همان ورودی', $p499_build, $p499_core,
	str_replace( "return '' === \$path ? '' : TOPERSIA_UID_BASE . '/' . \$path;", "return '' === \$path ? '' : 'http://topersia.local/e/' . \$path;", $p499_core ) );

$p499_keys = function ( $src ) use ( $p499_run ) {
	return $p499_run( $src, 'https://topersia.com', "array( topersia_uid_key( 'http://a/e/x/y' ) === topersia_uid_key( 'https://b/e/x/y' ), topersia_uid_key( 'https://a/id/1' ) === topersia_uid_key( 'https://a/id/2' ) )" ) === array( true, false );
};
tp441_mut( 'topersia_uid_key: یک مسیر با دو میزبان یکی؛ دو نشانی بیرون از /e/ یکی نمی‌شوند — جهش: فقط مسیر', $p499_keys, $p499_core,
	str_replace( "return '' === \$path ? trim( (string) \$uid ) : \$path;", 'return $path;', $p499_core ) );

/* تک سازنده */
$p499_one_build = function ( $core ) { return false !== strpos( $core, "topersia_uid_build( \$segment . '/' . \$slug )" ) && 1 === substr_count( $core, "TOPERSIA_UID_BASE . '/'" ); };
tp441_mut( 'تنها ساخت uid از topersia_uid_build است — جهش: ساخت با الحاق ثابت', $p499_one_build, $p499_core,
	str_replace( "topersia_uid_build( \$segment . '/' . \$slug )", "TOPERSIA_UID_BASE . '/' . \$segment . '/' . \$slug", $p499_core ) );

/* هیچ تطبیق دقیق با میزبان در درون‌ریز */
$p499_pack = (string) file_get_contents( $p499_root . '/plugin/topersia-core/includes/topersia-pack.php' );
$p499_pack_ok = function ( $s ) { return false === strpos( $s, "'meta_key'         => 'entity_uid'" ) && false !== strpos( $s, 'topersia_uid_find_ids( $uid )' ) && false !== strpos( $s, "'uid:' . \$plang . ':' . topersia_uid_key(" ) && false !== strpos( $s, "topersia_uid_key( \$c['uid'] ) === topersia_uid_key( \$uid )" ); };
tp441_mut( 'درون‌ریز بسته با مسیر تطبیق می‌دهد؛ کلید تکرار و هشدار یال هم — جهش: تطبیق دقیق meta_value', $p499_pack_ok, $p499_pack,
	str_replace( 'topersia_uid_find_ids( $uid )', "array(), 'meta_key'         => 'entity_uid'", $p499_pack ) );

/* @id و خروجی‌ها */
$p499_schema = (string) file_get_contents( $p499_root . '/plugin/topersia-schema/topersia-schema.php' );
$p499_s_ok = function ( $s ) { return false !== strpos( $s, "topersia_uid_build( \$uid ) : ''" ) && false !== strpos( $s, "return '' !== \$canonical ? \$canonical : \$uid;" ); };
tp441_mut( 'topersia_schema_uid میزبان خروجی را به مبدأ ثابت نرمال می‌کند — جهش: برگرداندن خام', $p499_s_ok, $p499_schema, str_replace( "return '' !== \$canonical ? \$canonical : \$uid;", 'return $uid;', $p499_schema ) );

$p499_scan = array( 'plugin/topersia-core', 'plugin/topersia-render', 'plugin/topersia-schema', 'plugin/topersia-ai-summarizer', 'plugin/topersia-user-core', 'scripts' );
$p499_bad  = array();
foreach ( $p499_scan as $dir ) {
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $p499_root . '/' . $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		if ( 'php' !== $file->getExtension() ) { continue; }
		$src = (string) file_get_contents( $file->getPathname() );
		if ( preg_match( "/'meta_key'\s*=>\s*'entity_uid'\s*,\s*'meta_value'/", $src ) || preg_match( "/meta_key\s*=\s*'entity_uid'\s+AND\s+meta_value\s*=\s*%s\s+LIMIT/", $src ) || preg_match( "~home_url\(\s*'/e/~", $src ) ) {
			$p499_bad[] = str_replace( $p499_root . '/', '', str_replace( '\\', '/', $file->getPathname() ) );
		}
	}
}
t( 'هیچ کد ما uid را با home_url نمی‌سازد و با meta_value دقیق نمی‌جوید', ! $p499_bad, implode( ' · ', $p499_bad ) );

/* ابزار مهاجرت */
$p499_tool = (string) file_get_contents( $p499_root . '/scripts/tp-fix-uid-host.php' );
$p499_t_ok = function ( $s ) { return false !== strpos( $s, "in_array( 'apply'," ) && false !== strpos( $s, "if ( ! \$apply ) {" ) && false !== strpos( $s, "'local' !== wp_get_environment_type()" ) && false !== strpos( $s, 'count( $owners ) > 1' ) && false !== strpos( $s, '$r->meta_value !== $want' ); };
tp441_mut( 'ابزار مهاجرت: پیش‌فرض آزمایشی، نوشتن فقط محلی، تعارض کنار می‌ماند، بی‌تغییر بازنویسی نمی‌شود — جهش: نوشتن بی apply', $p499_t_ok, $p499_tool, str_replace( "if ( ! \$apply ) {", "if ( false ) {", $p499_tool ) );

/* جابه‌جایی میزبان پایگاه‌داده uid را محلی نکند */
$p499_sr = (string) file_get_contents( $p499_root . '/scripts/tp-local-sr.php' );
t( 'tp-local-sr نشانی /e/ را در postmeta بازنویسی نمی‌کند', false !== strpos( $p499_sr, "'postmeta' === substr( \$table, -8 )" ) );
