<?php
/**
 * یاور `tests/duplicator-local.js` — فقط topersia.local.  ۲ج٫۱۲
 *
 *   php tests/duplicator-local-state.php snapshot          عکس جدول entities (تنظیم‌ها و الگوها)، کف شناسۀ پشتیبان، پرونده‌های پوشۀ Duplicator
 *   php tests/duplicator-local-state.php dirs              پوشه‌های Duplicator: هست، نوشتنی (همین کاربر خط فرمان)
 *   php tests/duplicator-local-state.php state <floor>     پشتیبان‌های تازه (شناسه > کف): نام، وضعیت، پرونده‌ها روی دیسک
 *   php tests/duplicator-local-state.php delete <floor>    پاک‌کردن پشتیبان‌های تازه با API خود Duplicator (پرونده و ردیف)
 *   php tests/duplicator-local-state.php sweep             (stdin: عکس) هر پرونده و پوشۀ تازۀ پوشۀ Duplicator که delete() جا گذاشت
 *   php tests/duplicator-local-state.php restore           (stdin: عکس) داده‌های entities به پیش از آزمون؛ entity تازه پاک
 *
 * فقط پشتیبانی که آزمون ساخته (شناسه بالای کف) پاک می‌شود. تنظیم زنده دست نمی‌خورد —
 * این پایگاه‌دادۀ محلی است. هیچ پرونده‌ای از این پوشه به git نمی‌رود (.gitignore و گارد).
 */

require_once __DIR__ . '/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require TP_WP_DIR . '/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

if ( ! defined( 'DUPLICATOR_SSDIR_PATH' ) || ! class_exists( '\Duplicator\Package\DupPackage' ) ) {
	fwrite( STDERR, "Duplicator Pro بار نشده\n" );
	exit( 2 );
}

global $wpdb;

$cmd      = isset( $argv[1] ) ? $argv[1] : '';
$entities = $wpdb->prefix . 'duplicator_entities';
$backups  = $wpdb->prefix . 'duplicator_backups';

/** پرونده‌های پوشۀ Duplicator، نسبی. */
function tp_dup_files() {
	$root = wp_normalize_path( DUPLICATOR_SSDIR_PATH );
	$out  = array();
	if ( ! is_dir( $root ) ) {
		return $out;
	}
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $f ) {
		$out[] = ltrim( substr( wp_normalize_path( $f->getPathname() ), strlen( $root ) ), '/' );
	}
	sort( $out );
	return $out;
}

if ( 'snapshot' === $cmd ) {
	$rows = $wpdb->get_results( "SELECT id, data FROM {$entities} ORDER BY id", ARRAY_A );
	echo wp_json_encode( array(
		'entities' => array_map( function ( $r ) { return array( (int) $r['id'], base64_encode( (string) $r['data'] ) ); }, $rows ),
		'floor'    => (int) $wpdb->get_var( "SELECT COALESCE( MAX(id), 0 ) FROM {$backups}" ),
		'at'       => time(),
		'files'    => tp_dup_files(),
	) );
	exit( 0 );
}

if ( 'dirs' === $cmd ) {
	$out = array();
	foreach ( array( 'ssdir' => DUPLICATOR_SSDIR_PATH, 'tmp' => DUPLICATOR_SSDIR_PATH_TMP, 'tmp_import' => DUPLICATOR_SSDIR_PATH_TMP_IMPORT, 'imports' => DUPLICATOR_DEPLOY_PATH, 'logs' => DUPLICATOR_LOGS_PATH ) as $k => $d ) {
		$out[ $k ] = array( 'path' => wp_normalize_path( $d ), 'dir' => is_dir( $d ), 'writable' => is_writable( $d ) );
	}
	echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES );
	exit( 0 );
}

$floor = isset( $argv[2] ) ? (int) $argv[2] : -1;

if ( in_array( $cmd, array( 'state', 'delete' ), true ) && $floor < 0 ) {
	fwrite( STDERR, "کف شناسه لازم است\n" );
	exit( 2 );
}

if ( 'state' === $cmd ) {
	$out = array();
	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$backups} WHERE id > %d ORDER BY id", $floor ) ) as $id ) {
		$p = \Duplicator\Package\DupPackage::getById( (int) $id );
		if ( ! $p ) {
			continue;
		}
		$files = array();
		foreach ( array( 'archive' => \Duplicator\Package\AbstractPackage::FILE_TYPE_ARCHIVE, 'installer' => \Duplicator\Package\AbstractPackage::FILE_TYPE_INSTALLER ) as $k => $type ) {
			$path          = (string) $p->getLocalPackageFilePath( $type );
			$files[ $k ]   = array( 'name' => basename( $path ), 'exists' => '' !== $path && is_file( $path ), 'bytes' => '' !== $path && is_file( $path ) ? filesize( $path ) : 0 );
		}
		$out[] = array( 'id' => (int) $id, 'name' => $p->getName(), 'status' => (int) $p->getStatus(), 'files' => $files );
	}
	echo wp_json_encode( array( 'backups' => $out, 'files' => tp_dup_files() ), JSON_UNESCAPED_SLASHES );
	exit( 0 );
}

if ( 'delete' === $cmd ) {
	$done = array();
	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$backups} WHERE id > %d ORDER BY id", $floor ) ) as $id ) {
		$p = \Duplicator\Package\DupPackage::getById( (int) $id );
		if ( ! $p ) {
			$done[ (int) $id ] = false;
			continue;
		}
		$hash              = (string) $p->getNameHash();
		$done[ (int) $id ] = (bool) $p->delete();
		/* پرونده‌های موقت و لاگ همین بسته (پیشوند نام‌هش خودش) — delete() بستۀ نیمه‌کاره آن‌ها را جا می‌گذارد */
		if ( strlen( $hash ) > 20 ) {
			foreach ( array( DUPLICATOR_SSDIR_PATH_TMP, DUPLICATOR_LOGS_PATH, DUPLICATOR_SSDIR_PATH ) as $d ) {
				foreach ( (array) glob( wp_normalize_path( $d ) . '/' . $hash . '_*' ) as $f ) {
					if ( is_file( $f ) ) {
						unlink( $f );
					}
				}
			}
		}
	}
	echo wp_json_encode( array( 'deleted' => $done, 'left' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$backups} WHERE id > %d", $floor ) ) ) );
	exit( 0 );
}

/* هر پرونده و پوشۀ تازه زیر duplicator-backups که در عکس نبود — فقط ساختۀ همین آزمون */
if ( 'sweep' === $cmd ) {
	$snap = json_decode( (string) file_get_contents( 'php://stdin' ), true );
	if ( ! is_array( $snap ) || ! isset( $snap['files'], $snap['at'] ) ) {
		fwrite( STDERR, "عکس نامعتبر\n" );
		exit( 2 );
	}
	$root    = wp_normalize_path( DUPLICATOR_SSDIR_PATH );
	$keep    = array_flip( $snap['files'] );
	$removed = array();
	foreach ( tp_dup_files() as $rel ) {
		/* فقط پرونده‌ای که پس از عکس ساخته یا نوشته شد — نه پروندۀ هم‌زمان کس دیگر پیش از آزمون */
		if ( ! isset( $keep[ $rel ] ) && is_file( $root . '/' . $rel ) && filemtime( $root . '/' . $rel ) >= (int) $snap['at'] - 2 ) {
			unlink( $root . '/' . $rel );
			$removed[] = $rel;
		}
	}
	/* پوشه‌ای که حالا خالی است و در عکس هیچ پرونده‌ای زیرش نبود */
	$dirs = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $dirs as $d ) {
		if ( ! $d->isDir() ) {
			continue;
		}
		$rel = ltrim( substr( wp_normalize_path( $d->getPathname() ), strlen( $root ) ), '/' );
		$had = false;
		foreach ( $snap['files'] as $f ) {
			if ( 0 === strpos( $f, $rel . '/' ) ) {
				$had = true;
				break;
			}
		}
		if ( ! $had && 2 === count( (array) scandir( $d->getPathname() ) ) ) {
			rmdir( $d->getPathname() );
			$removed[] = $rel . '/';
		}
	}
	echo wp_json_encode( array( 'removed' => $removed ), JSON_UNESCAPED_SLASHES );
	exit( 0 );
}

if ( 'restore' === $cmd ) {
	$snap = json_decode( (string) file_get_contents( 'php://stdin' ), true );
	if ( ! is_array( $snap ) || empty( $snap['entities'] ) ) {
		fwrite( STDERR, "عکس نامعتبر\n" );
		exit( 2 );
	}
	$ids = array();
	foreach ( $snap['entities'] as $e ) {
		$ids[] = (int) $e[0];
		$wpdb->update( $entities, array( 'data' => base64_decode( $e[1] ) ), array( 'id' => (int) $e[0] ) );
	}
	$extra = $wpdb->get_col( "SELECT id FROM {$entities} WHERE id NOT IN (" . implode( ',', array_map( 'intval', $ids ) ) . ')' );
	foreach ( $extra as $id ) {
		$wpdb->delete( $entities, array( 'id' => (int) $id ) );
	}
	$same = true;
	foreach ( $snap['entities'] as $e ) {
		$same = $same && base64_decode( $e[1] ) === (string) $wpdb->get_var( $wpdb->prepare( "SELECT data FROM {$entities} WHERE id = %d", (int) $e[0] ) );
	}
	echo wp_json_encode( array( 'restored' => $same, 'removed_new' => array_map( 'intval', $extra ) ) );
	exit( 0 );
}

fwrite( STDERR, "دستور: snapshot | dirs | state <floor> | delete <floor> | sweep | restore\n" );
exit( 2 );
