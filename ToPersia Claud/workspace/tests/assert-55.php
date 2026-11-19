<?php
/**
 * دستۀ 55 (P5-D626 تا P5-D637) — از assert.php بار می‌شود؛ فقط خواندن کد، هر ادعا با جهش.
 * رفتار روی وردپرس واقعی: tests/w55-local.js (Chromium و Firefox × ۱۴۴۰/۷۶۸/۳۹۰).
 */

echo "\nدستۀ 55 — جعبۀ جست‌وجو، نمای فهرستی، شمار هاب، CLS، فصل، تصویر مشترک، پاورقی\n" . str_repeat( '─', 78 ) . "\n";

require_once __DIR__ . '/env.php';
$w55_root = dirname( __DIR__ );
$w55_read = function ( $p ) use ( $w55_root ) {
	return (string) @file_get_contents( $w55_root . '/' . $p );
};
$w55_r      = 'plugin/topersia-render/';
$w55_zjs    = $w55_read( $w55_r . 'assets/topersia-g-zones.js' );
$w55_njs    = $w55_read( $w55_r . 'assets/topersia-g-news.js' );
$w55_ljs    = $w55_read( $w55_r . 'assets/topersia-g-loc.js' );
$w55_hub    = $w55_read( $w55_r . 'templates/hub-archive.php' );
$w55_art    = $w55_read( $w55_r . 'templates/article-archive.php' );

/* ── بند ۱: پشتیبان کامل (format 2) — رازِ پروندۀ سایت با نام و محتوا ─────
 * اجرای نخست با صافی فقط‌نام دو cacert.pem عمومی و قالب wp-config-check.php دوپلیکیتور را انداخت؛ اکنون رفتار روی پروندۀ واقعی سنجیده می‌شود. */
require_once $w55_root . '/scripts/lib/tp-backup-lib.php';
$w55_fx  = rtrim( str_replace( '\\', '/', sys_get_temp_dir() ), '/' ) . '/tp-w55-secret-' . getmypid();
@mkdir( $w55_fx, 0777, true );
$w55_fix = array(   /* نام ← [محتوا، راز است؟] */
	'wp-config.php'         => array( "<?php define( 'DB_NAME', 'x' );", true ),
	'cacert.pem'            => array( "-----BEGIN CERTIFICATE-----\nMIIBszCCAVmgAwIBAgIU\n-----END CERTIFICATE-----\n", false ),
	/* نشان کلید خصوصی در زمان اجرا ساخته می‌شود تا git-guard روی خودِ آزمون خطای مثبت ندهد؛ بدنه ساختگی و کوتاه است */
	'server.pem'            => array( '-----BEGIN ' . implode( ' ', array( 'PRIVATE', 'KEY' ) ) . "-----\nAAAA\n-----END -----\n", true ),
	'wp-config-sample.php'  => array( "<?php define( 'DB_PASSWORD', 'password_here' ); define( 'AUTH_KEY', 'put your unique phrase here' );", false ),
	'wp-config-check.php'   => array( "<?php echo 'wp-config check';", false ),
	'wp-config-old.php'     => array( "<?php define( 'DB_PASSWORD', 'S3cr3t-real-value' );", true ),
	'.gitignore'            => array( "node_modules\n", false ),
	'.env'                  => array( "KEY=1\n", true ),
	'id_rsa'                => array( "x", true ),
);
foreach ( $w55_fix as $w55_n => $w55_v ) { file_put_contents( $w55_fx . '/' . $w55_n, $w55_v[0] ); }
$w55_secret = function ( callable $fn ) use ( $w55_fx, $w55_fix ) {
	foreach ( $w55_fix as $n => $v ) {
		if ( (bool) $fn( 'geo/x/' . $n, $w55_fx . '/' . $n ) !== $v[1] ) {
			return false;
		}
	}
	return true;
};
tp441_mut( 'بند ۱: site.zip — wp-config.php همیشه بیرون؛ cacert.pem عمومی، wp-config-sample/check و .gitignore درون؛ .pem با PRIVATE KEY، wp-config با رمز واقعی، .env و id_rsa بیرون — جهش: صافی فقط‌نام (اجرای نخست)',
	$w55_secret, 'tpb_site_secret', function ( $rel, $abs ) { return tpb_secret_file( basename( $rel ) ); } );
foreach ( array_keys( $w55_fix ) as $w55_n ) { @unlink( $w55_fx . '/' . $w55_n ); }
@rmdir( $w55_fx );

$w55_bk  = $w55_read( 'scripts/tp-backup.php' );
$w55_rt  = $w55_read( 'scripts/tp-restore-test.php' );
$w55_pkg = function ( $b ) use ( $w55_rt ) {
	return false !== strpos( $b, "tpb_zip_map( \$dir . '/site.zip', \$sm['map'] )" )
		&& false !== strpos( $b, "'contents'     => array( 'file' => 'contents.tsv'" )
		&& (bool) preg_match( '/\|pem\|crt\)\|\\\\\.htaccess\)\$\/i/', $b )
		&& false !== strpos( $b, "\$sm['map']['env/wp-config.names.txt']" )
		&& false !== strpos( $w55_rt, "is_file( \$x . '/geo/wp-config.php' )" )
		&& false !== strpos( $w55_rt, "rt_unzip_check( \$dir . '/site.zip', \$x, \$sf['files'], \$contents['site.zip'] )" );
};
tp441_mut( 'بند ۱: بسته site.zip و contents.tsv (sha256 هر پرونده) دارد، .pem و .crt پویش کلید می‌شوند، فقط نام ثابت‌های wp-config؛ آزمون بازگردانی sha256 هر پرونده و نبود wp-config را می‌سنجد — جهش: بی پویش pem',
	$w55_pkg, $w55_bk, str_replace( '|pem|crt)', ')', $w55_bk ) );

