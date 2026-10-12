<?php
declare( strict_types=1 );

/**
 * tp-relay ۱٫۰٫۱ — رلهٔ کار بین Claude Code محلی (WAMP) و جلسهٔ ابری Claude Code.
 * تصمیم‌ها: OPS-D001 … OPS-D009 — راهنما: docs/relay/RELAY.md
 *
 * محلی — بی نیاز به هوش مصنوعی؛ وقتی سهمیهٔ هفتگی تمام شده هم کار می‌کند (OPS-D003):
 *   php tp-relay.php status [--fetch]
 *   php tp-relay.php preflight
 *   php tp-relay.php to-cloud [--task="…" | --task-file=PATH] [--all] [--wip] [--no-launch] [--dry-run]
 *   php tp-relay.php from-cloud [BRANCH] [--gates] [--squash]
 *   php tp-relay.php gates [--record]
 * ابری — از قلاب SessionStart در .claude/settings.json ریشه:
 *   php tp-relay.php session-start [--force]
 *
 * پرونده‌های ماشینی فقط افزودنی‌اند و با merge=union ادغام می‌شوند (docs/relay/.gitattributes)؛
 * CURRENT.md دو بخش جدا دارد — هر طرف فقط بخش خودش را ویرایش می‌کند — تا ادغام squash بی‌تداخل بماند.
 *
 * کد خروج: ۰ سالم · ۱ افتادن · ۲ ورودی یا محیط نادرست
 */

const TP_RELAY_VERSION = '1.0.1';
const TP_WIN_ROOT      = 'C:/wamp64/www/topersia/'; // فقط برای سنجش طول مسیر در ویندوز

$GLOBALS['tp_dry'] = false;

// ─────────────────────────────────────────────────────────────── ابزار پایه

function tp_out( string $s = '' ): void {
	fwrite( STDOUT, $s . "\n" );
}

function tp_err( string $s ): void {
	fwrite( STDERR, $s . "\n" );
}

function tp_is_cloud(): bool {
	return 'true' === getenv( 'CLAUDE_CODE_REMOTE' );
}

function tp_workspace(): string {
	return str_replace( '\\', '/', dirname( __DIR__, 2 ) );
}

function tp_docs(): string {
	return tp_workspace() . '/docs/relay';
}

/**
 * اجرای فرمان بی پوسته (آرایه) — مسیرهای فاصله‌دار و فارسی بی نقل‌قول.
 *
 * @return array{0:int,1:string,2:string} کد، خروجی، خطا
 */
function tp_run( array $cmd, ?string $cwd = null ): array {
	$spec = array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) );
	$p    = @proc_open( $cmd, $spec, $pipes, $cwd );
	if ( ! is_resource( $p ) ) {
		return array( 127, '', 'اجرا نشد: ' . implode( ' ', $cmd ) );
	}
	$out = stream_get_contents( $pipes[1] );
	$err = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	return array( proc_close( $p ), (string) $out, (string) $err );
}

function tp_git( array $args, ?string $cwd = null ): array {
	return tp_run( array_merge( array( 'git' ), $args ), $cwd ?? tp_root() );
}

/** فرمانی که چیزی را عوض می‌کند — در --dry-run فقط چاپ می‌شود. */
function tp_mut( array $cmd, ?string $cwd = null ): array {
	if ( $GLOBALS['tp_dry'] ) {
		tp_out( '  [dry] ' . implode( ' ', array_map( 'tp_q', $cmd ) ) );
		return array( 0, '', '' );
	}
	return tp_run( $cmd, $cwd ?? tp_root() );
}

function tp_q( string $s ): string {
	return preg_match( '/[\s"\']/', $s ) ? '"' . str_replace( '"', '\"', $s ) . '"' : $s;
}

function tp_write( string $path, string $content ): void {
	if ( $GLOBALS['tp_dry'] ) {
		tp_out( '  [dry] نوشتن ' . tp_rel( $path ) . ' (' . strlen( $content ) . ' بایت)' );
		return;
	}
	if ( ! is_dir( dirname( $path ) ) ) {
		mkdir( dirname( $path ), 0777, true );
	}
	file_put_contents( $path, str_replace( "\r\n", "\n", $content ) );
}

function tp_root(): string {
	static $root = null;
	if ( null === $root ) {
		[ $c, $o ] = tp_run( array( 'git', 'rev-parse', '--show-toplevel' ), tp_workspace() );
		$env       = getenv( 'CLAUDE_PROJECT_DIR' );
		$root      = 0 === $c ? trim( $o ) : ( $env ? (string) $env : '' );
		$root      = rtrim( str_replace( '\\', '/', $root ), '/' );
	}
	return $root;
}

function tp_rel( string $abs ): string {
	$r = tp_root() . '/';
	return 0 === strpos( $abs, $r ) ? substr( $abs, strlen( $r ) ) : $abs;
}

function tp_branch(): string {
	return trim( tp_git( array( 'rev-parse', '--abbrev-ref', 'HEAD' ) )[1] );
}

function tp_short( string $ref = 'HEAD' ): string {
	return trim( tp_git( array( 'rev-parse', '--short=8', $ref ) )[1] );
}

function tp_default_branch(): string {
	[ $c, $o ] = tp_git( array( 'symbolic-ref', '--quiet', '--short', 'refs/remotes/origin/HEAD' ) );
	return 0 === $c && '' !== trim( $o ) ? (string) preg_replace( '#^origin/#', '', trim( $o ) ) : 'main';
}

function tp_ref_exists( string $ref ): bool {
	return 0 === tp_git( array( 'rev-parse', '--verify', '--quiet', $ref ) )[0];
}

/**
 * تغییرهای ثبت‌نشده از porcelain -z.
 *
 * @return array<int,array{0:string,1:string}> [XY, مسیر]
 */
function tp_dirty(): array {
	[ , $o ] = tp_git( array( 'status', '--porcelain', '-z', '--untracked-files=all' ) );
	$tok     = explode( "\0", $o );
	$out     = array();
	for ( $i = 0, $n = count( $tok ); $i < $n; $i++ ) {
		if ( strlen( $tok[ $i ] ) < 4 ) {
			continue;
		}
		$xy    = substr( $tok[ $i ], 0, 2 );
		$out[] = array( $xy, substr( $tok[ $i ], 3 ) );
		if ( 'R' === $xy[0] || 'C' === $xy[0] ) {
			++$i; // مسیر پیشین نام‌گذاری دوباره
		}
	}
	return $out;
}

/** @return string[] */
function tp_lines( string $s ): array {
	return array_values( array_filter( explode( "\n", str_replace( "\r", '', $s ) ), 'strlen' ) );
}

function tp_tehran(): string {
	return ( new DateTimeImmutable( 'now', new DateTimeZone( 'Asia/Tehran' ) ) )->format( 'Y-m-d H:i' );
}

function tp_parse( array $argv ): array {
	$cmd  = $argv[1] ?? 'help';
	$opt  = array();
	$args = array();
	foreach ( array_slice( $argv, 2 ) as $a ) {
		if ( preg_match( '/^--([a-z-]+)(?:=(.*))?$/s', $a, $m ) ) {
			$opt[ $m[1] ] = $m[2] ?? true;
		} else {
			$args[] = $a;
		}
	}
	return array( $cmd, $opt, $args );
}

// ───────────────────────────────── پرونده‌های رله: CURRENT (انسانی) · HISTORY و GATES (افزودنی)

/** جایگزینی میان دو نشانگر در CURRENT.md؛ نشانگر نبود ← فایل دست نمی‌خورد. */
function tp_current_block( string $tag, string $body ): bool {
	$f   = tp_docs() . '/CURRENT.md';
	$src = (string) @file_get_contents( $f );
	$re  = '#(<!-- relay:' . preg_quote( $tag, '#' ) . ' -->)(.*?)(<!-- /relay:' . preg_quote( $tag, '#' ) . ' -->)#s';
	if ( ! preg_match( $re, $src ) ) {
		tp_err( "⚠ نشانگر relay:{$tag} در CURRENT.md نیست — دستور جلسه نوشته نشد." );
		return false;
	}
	$new = preg_replace_callback(
		$re,
		static fn( $m ) => $m[1] . "\n" . rtrim( $body ) . "\n" . $m[3],
		$src,
		1
	);
	tp_write( $f, (string) $new );
	return true;
}

function tp_cell( string $s ): string {
	return str_replace( array( '|', "\n" ), array( '\|', ' ' ), $s );
}

function tp_append_row( string $file, array $cells ): void {
	$f    = tp_docs() . '/' . $file;
	$line = '| ' . implode( ' | ', array_map( 'tp_cell', $cells ) ) . " |\n";
	if ( $GLOBALS['tp_dry'] ) {
		tp_out( "  [dry] افزودن به {$file}: " . trim( $line ) );
		return;
	}
	file_put_contents( $f, $line, FILE_APPEND );
}

function tp_last_row( string $file ): ?string {
	$rows = array_values( array_filter( tp_lines( (string) @file_get_contents( tp_docs() . '/' . $file ) ), static fn( $l ) => 0 === strpos( $l, '| 20' ) ) );
	return $rows ? end( $rows ) : null;
}

// ───────────────────────────────────────────── preflight — نام‌های قابل‌حمل (OPS-D006)

function tp_utf16_len( string $s ): int {
	$n = preg_match_all( '/./us', $s );
	$n = false === $n ? strlen( $s ) : $n;
	return $n + (int) preg_match_all( '/[\x{10000}-\x{10FFFF}]/u', $s );
}

/** نام UTF-8 که یک بار با CP437 خوانده و دوباره ذخیره شده — پیشنهاد نام درست. */
function tp_suggest_name( string $part ): ?string {
	if ( ! function_exists( 'iconv' ) ) {
		return null;
	}
	$b = @iconv( 'UTF-8', 'CP437', $part );
	if ( false === $b || ! preg_match( '//u', $b ) || strlen( $b ) > 255 || $b === $part ) {
		return null;
	}
	return $b;
}

/**
 * @param string[] $paths
 * @return array<int,array{0:string,1:string,2:string,3:?string}> سطح، مسیر، علت، پیشنهاد
 */
function tp_name_issues( array $paths, bool $win_path_len = true ): array {
	$issues   = array();
	$reserved = '/^(con|prn|aux|nul|com[0-9]|lpt[0-9])(\..*)?$/i';
	$seen     = array();
	$lower    = static fn( string $x ): string => function_exists( 'mb_strtolower' ) ? mb_strtolower( $x, 'UTF-8' ) : strtolower( $x );

	foreach ( $paths as $p ) {
		if ( '' === $p ) {
			continue;
		}
		$parts = explode( '/', $p );
		foreach ( $parts as $part ) {
			if ( strlen( $part ) > 255 ) {
				$issues[] = array( 'FAIL', $p, 'یک جزء نام ' . strlen( $part ) . ' بایت است (سقف لینوکس و ابر ۲۵۵) — checkout ابری شکست می‌خورد', tp_suggest_name( $part ) );
			}
			if ( preg_match( '/[<>:"|?*\\\\\x00-\x1F]/', $part ) ) {
				$issues[] = array( 'FAIL', $p, 'نویسهٔ ناسازگار با ویندوز (<>:"|?* یا \\) — checkout محلی شکست می‌خورد', null );
			}
			if ( preg_match( '/[. ]$/', $part ) ) {
				$issues[] = array( 'FAIL', $p, 'جزء نام با نقطه یا فاصله تمام می‌شود — ویندوز نگهش نمی‌دارد', null );
			}
			if ( preg_match( $reserved, $part ) ) {
				$issues[] = array( 'FAIL', $p, 'نام رزرو ویندوز (CON · PRN · AUX · NUL · COMn · LPTn)', null );
			}
		}
		$acc = ''; // برخورد بزرگی/کوچکی — پرونده و پوشه‌های میانی
		foreach ( $parts as $part ) {
			$acc = '' === $acc ? $part : $acc . '/' . $part;
			$k   = $lower( $acc );
			if ( isset( $seen[ $k ] ) && $seen[ $k ] !== $acc ) {
				$issues[] = array( 'FAIL', $acc, 'با «' . $seen[ $k ] . '» فقط در بزرگی/کوچکی حروف فرق دارد — در ویندوز یکی می‌شوند', null );
				break;
			}
			$seen[ $k ] = $acc;
		}
		if ( $win_path_len && tp_utf16_len( TP_WIN_ROOT . $p ) > 259 ) {
			$issues[] = array( 'WARN', $p, 'مسیر کامل در ویندوز از ۲۶۰ نویسه می‌گذرد (core.longpaths لازم است)', null );
		}
	}
	return $issues;
}

/** پرونده‌های پیگیری‌شده‌ای که هرگز نباید در git باشند — آینهٔ .gitignore ریشه و git-guard. */
function tp_sensitive( array $paths ): array {
	$re = '#(^|/)(wp-config\.php|\.env(\.[^/]+)?|id_(rsa|ed25519|ecdsa)|auth\.json|secrets?\.json|credentials?\.json)$|\.(sql|sql\.gz|sql\.zip|daf|p12|pfx|key)$#i';
	return array_values( array_filter( $paths, static fn( $p ) => (bool) preg_match( $re, $p ) ) );
}

function tp_preflight( bool $quiet_ok = false ): int {
	if ( '' === tp_root() ) {
		tp_err( '❌ این‌جا مخزن git نیست.' );
		return 2;
	}
	[ , $o ] = tp_git( array( '-c', 'core.quotepath=false', 'ls-files', '-z' ) );
	$paths   = array_values( array_filter( explode( "\0", $o ), 'strlen' ) );
	if ( ! $paths ) {
		tp_err( '❌ index خالی است (git ls-files چیزی نداد) — مخزن ناقص یا checkout شکسته.' );
		return 2;
	}
	$fail = 0;
	$warn = 0;
	foreach ( tp_name_issues( $paths ) as [ $lvl, $p, $why, $sug ] ) {
		if ( 'FAIL' === $lvl ) {
			++$fail;
			tp_out( "❌ {$why}\n   {$p}" . ( $sug ? "\n   نام پیشنهادی (رمزگشایی CP437 ← UTF-8): {$sug}" : '' ) );
		} elseif ( ++$warn <= 5 ) {
			tp_out( "⚠ {$why}\n   {$p}" );
		}
	}
	if ( $warn > 5 ) {
		tp_out( '⚠ … و ' . ( $warn - 5 ) . ' هشدار طول مسیر دیگر' );
	}
	foreach ( tp_sensitive( $paths ) as $p ) {
		++$fail;
		tp_out( "❌ پروندهٔ حساس در git پیگیری می‌شود: {$p}" );
	}
	[ $c, $url ] = tp_git( array( 'remote', 'get-url', 'origin' ) );
	if ( 0 !== $c ) {
		++$fail;
		tp_out( '❌ remote به نام origin نیست — جلسهٔ ابری از GitHub clone می‌کند (OPS-D001).' );
	} elseif ( ! preg_match( '#github\.com[/:]#i', $url ) ) {
		++$warn;
		tp_out( '⚠ origin روی GitHub نیست: ' . trim( $url ) );
	}
	if ( 0 === $fail ) {
		if ( ! $quiet_ok ) {
			tp_out( '✅ preflight: ' . count( $paths ) . ' پروندهٔ پیگیری‌شده؛ نام‌ها در ویندوز و لینوکس قابل‌حمل؛ بی پروندهٔ حساس' . ( $warn ? " ({$warn} هشدار)" : '' ) );
		}
		return 0;
	}
	tp_out( "❌ preflight: {$fail} مشکل بازدارنده." );
	return 1;
}

// ───────────────────────────────────────────────────────────────── گیت‌ها (OPS-D004)

/** @return array{status:string,pass?:int,fail?:int,tail?:string} */
function tp_php_suite( string $file ): array {
	$w = tp_workspace();
	if ( ! is_file( "{$w}/{$file}" ) ) {
		return array( 'status' => 'نیست' );
	}
	[ $c, $o, $e ] = tp_run( array( PHP_BINARY, $file ), $w );
	$all           = $o . $e;
	$r             = array( 'status' => 0 === $c ? 'سبز' : ( 2 === $c ? 'محیط ناقص' : 'قرمز' ) );
	if ( preg_match_all( '/گذشته:\s*(\d+)\s+افتاده:\s*(\d+)/u', $all, $m ) ) {
		$r['pass'] = (int) end( $m[1] );
		$r['fail'] = (int) end( $m[2] );
	}
	if ( 0 !== $c ) {
		$r['tail'] = implode( "\n", array_slice( tp_lines( $all ), -25 ) );
	}
	return $r;
}

/**
 * گیت‌های همین محیط.
 *
 * @return array{0:bool,1:string} سبز؟ · خلاصهٔ یک‌خطی برای GATES.md
 */
function tp_gates(): array {
	$cloud = tp_is_cloud();
	$w     = tp_workspace();
	$res   = array();
	tp_out( 'گیت‌های رله — حالت ' . ( $cloud ? 'ابری' : 'محلی' ) );

	$res[] = array( 'name' => 'preflight', 'status' => 0 === tp_preflight( true ) ? 'سبز' : 'قرمز', 'req' => true );

	$bad = array();
	$n   = 0;
	foreach ( array( 'plugin', 'scripts' ) as $dir ) {
		if ( ! is_dir( "{$w}/{$dir}" ) ) {
			continue;
		}
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( "{$w}/{$dir}", FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $f ) {
			if ( 'php' !== strtolower( $f->getExtension() ) ) {
				continue;
			}
			++$n;
			[ $c, $o ] = tp_run( array( PHP_BINARY, '-l', $f->getPathname() ) );
			if ( 0 !== $c ) {
				$bad[] = tp_rel( str_replace( '\\', '/', $f->getPathname() ) ) . ' — ' . trim( (string) strtok( $o, "\n" ) );
			}
		}
	}
	$res[] = array( 'name' => 'php -l', 'status' => $bad ? 'قرمز' : 'سبز', 'pass' => $n - count( $bad ), 'fail' => count( $bad ), 'req' => true, 'tail' => implode( "\n", $bad ) );

	foreach ( array( 'tests/assert.php', 'tests/assert-uc.php' ) as $suite ) {
		tp_out( "  … {$suite}" );
		$res[] = array( 'name' => basename( $suite ), 'req' => true ) + tp_php_suite( $suite );
	}

	if ( ! $cloud || getenv( 'TP_CHROME' ) ) {
		tp_out( '  … tests/contrast.php' );
		$res[] = array( 'name' => 'contrast.php', 'req' => true ) + tp_php_suite( 'tests/contrast.php' );
	} else {
		$res[] = array( 'name' => 'contrast.php', 'status' => 'فقط محلی', 'req' => false );
	}

	$ok    = true;
	$parts = array();
	foreach ( $res as $r ) {
		$hard = ! empty( $r['req'] ) && 'نیست' !== $r['status'];
		$mark = 'سبز' === $r['status'] ? '✅' : ( $hard ? '❌' : '➖' );
		$ok   = $ok && '❌' !== $mark;
		$cnt  = isset( $r['pass'] ) ? " {$r['pass']}/" . ( $r['pass'] + $r['fail'] ) : '';
		tp_out( "  {$mark} {$r['name']}: {$r['status']}{$cnt}" );
		if ( '❌' === $mark && ! empty( $r['tail'] ) ) {
			tp_out( (string) preg_replace( '/^/m', '      ', $r['tail'] ) );
		}
		$parts[] = $r['name'] . ' ' . $r['status'] . $cnt;
	}
	if ( $cloud ) {
		tp_out( '  ➖ پذیرش، رفتار، *-local.php، WPML، Lighthouse و فایرفاکس فقط محلی‌اند — در CURRENT.md زیر «در انتظار تأیید محلی» بیاور.' );
	}
	tp_out( $ok ? '✅ گیت‌های این محیط سبزند.' : '❌ گیت قرمز — پیش از commit یا ادغام درست شود.' );
	return array( $ok, implode( ' · ', $parts ) );
}

function cmd_gates( array $opt ): int {
	[ $ok, $summary ] = tp_gates();
	if ( ! empty( $opt['record'] ) ) {
		tp_append_row( 'GATES.md', array( tp_tehran(), tp_is_cloud() ? 'ابری' : 'محلی', '`' . tp_branch() . '`', '`' . tp_short() . '`', $ok ? '✅' : '❌', $summary ) );
		tp_out( '  ثبت شد در docs/relay/GATES.md' );
	}
	return $ok ? 0 : 1;
}

// ──────────────────────────────────────────────────────────────────────── وضعیت

function cmd_status( array $opt ): int {
	if ( '' === tp_root() ) {
		tp_err( '❌ این‌جا مخزن git نیست.' );
		return 2;
	}
	if ( ! empty( $opt['fetch'] ) ) {
		tp_git( array( 'fetch', 'origin', '--prune', '--quiet' ) );
	}
	$default = tp_default_branch();
	$branch  = tp_branch();
	tp_out( 'tp-relay ' . TP_RELAY_VERSION . ' — حالت ' . ( tp_is_cloud() ? 'ابری' : 'محلی' ) );
	tp_out( "  شاخه: {$branch} @ " . tp_short() . " · شاخهٔ اصلی: {$default}" );
	tp_out( '  تغییر ثبت‌نشده: ' . count( tp_dirty() ) . ' پرونده' );
	if ( tp_ref_exists( "refs/remotes/origin/{$branch}" ) ) {
		$lr = preg_split( '/\s+/', trim( tp_git( array( 'rev-list', '--left-right', '--count', "origin/{$branch}...HEAD" ) )[1] ) );
		tp_out( "  نسبت به origin/{$branch}: " . (int) ( $lr[1] ?? 0 ) . ' جلو · ' . (int) ( $lr[0] ?? 0 ) . ' عقب' );
	} else {
		tp_out( "  origin/{$branch} نیست (push نشده)" );
	}
	$base        = tp_ref_exists( "refs/heads/{$default}" ) ? $default : "origin/{$default}";
	[ , $refs ]  = tp_git( array( 'for-each-ref', '--sort=-committerdate', '--format=%(refname:short)|%(committerdate:relative)|%(subject)', 'refs/remotes/origin/claude', 'refs/remotes/origin/relay' ) );
	$list        = tp_lines( $refs );
	tp_out( '  شاخه‌های رله و ابری روی origin: ' . ( $list ? count( $list ) : 'هیچ' ) . ( empty( $opt['fetch'] ) ? ' (بی --fetch شاید کهنه)' : '' ) );
	foreach ( array_slice( $list, 0, 10 ) as $l ) {
		[ $ref, $when, $subj ] = explode( '|', $l, 3 ) + array( '', '', '' );
		$ahead = (int) trim( tp_git( array( 'rev-list', '--count', "{$base}..{$ref}" ) )[1] );
		tp_out( "    • {$ref} — {$when} — {$ahead} commit جلوتر از {$default} — {$subj}" );
	}
	$h = tp_last_row( 'HISTORY.md' );
	$g = tp_last_row( 'GATES.md' );
	tp_out( '  آخرین تحویل: ' . ( $h ?? 'هیچ' ) );
	tp_out( '  آخرین گیت ثبت‌شده: ' . ( $g ?? 'هیچ' ) );
	return 0;
}

// ─────────────────────────────────────────────────────────── تحویل به ابر (OPS-D003)

function cmd_to_cloud( array $opt ): int {
	if ( tp_is_cloud() ) {
		tp_err( '❌ to-cloud فقط روی ماشین محلی است. در ابر: gates --record، به‌روزکردن بخش ابری CURRENT.md، commit و push.' );
		return 2;
	}
	if ( 0 !== tp_preflight() ) {
		tp_err( 'تحویل متوقف شد: اول preflight را سبز کن (OPS-D006).' );
		return 1;
	}
	// OPS-D009: to-cloud همهٔ تغییرهای پیگیری‌شده را برمی‌دارد و شاخه را عوض می‌کند؛ بی --wip فقط روی درخت تمیز
	if ( empty( $opt['wip'] ) ) {
		$tracked = 0;
		foreach ( tp_dirty() as [ $xy ] ) {
			if ( '??' !== $xy ) {
				$tracked++;
			}
		}
		if ( $tracked > 0 ) {
			tp_err( "درخت کار تمیز نیست ({$tracked} پرونده). کار موازی: جلسه را از claude.ai/code روی main باز کن. تحویل همین کار نیمه‌کاره: --wip" );
			return 2;
		}
	}
	$from  = tp_branch();
	$head  = tp_short();
	$stamp = ( new DateTimeImmutable( 'now', new DateTimeZone( 'Asia/Tehran' ) ) )->format( 'Ymd-Hi' );
	$relay = "relay/{$stamp}";
	for ( $i = 2; tp_ref_exists( "refs/heads/{$relay}" ) || tp_ref_exists( "refs/remotes/origin/{$relay}" ); $i++ ) {
		$relay = "relay/{$stamp}-{$i}";
	}

	// تغییر پیگیری‌شده همه‌جا با add -u؛ پروندهٔ تازه فقط درون workspace مگر --all
	$ws_rel    = tp_rel( tp_workspace() ) . '/';
	$files     = array();
	$new_files = array();
	$left_out  = array();
	foreach ( tp_dirty() as [ $xy, $path ] ) {
		if ( '??' === $xy ) {
			if ( empty( $opt['all'] ) && 0 !== strpos( $path, $ws_rel ) ) {
				$left_out[] = $path;
				continue;
			}
			$new_files[] = $path;
		}
		$files[] = $path;
	}

	tp_out( "تحویل به ابر: {$from} @ {$head} ← {$relay}" );
	tp_out( '  پرونده‌های تحویلی: ' . count( $files ) );
	foreach ( $left_out as $p ) {
		tp_out( "  ⚠ پروندهٔ تازهٔ بیرون از workspace اضافه نشد (برای افزودن: --all): {$p}" );
	}

	[ $c, , $e ] = tp_mut( array( 'git', 'switch', '-c', $relay ) );
	if ( 0 !== $c ) {
		tp_err( '❌ ساخت شاخه شکست خورد: ' . trim( $e ) );
		return 1;
	}

	$task = null;
	if ( ! empty( $opt['task-file'] ) ) {
		$task = (string) @file_get_contents( (string) $opt['task-file'] );
	} elseif ( isset( $opt['task'] ) && is_string( $opt['task'] ) ) {
		$task = $opt['task'];
	}
	tp_current_block(
		'task',
		null !== $task && '' !== trim( $task )
			? trim( $task )
			: "(خالی) — ادامهٔ «قدم بعدی» بخش محلی همین فایل؛ تحویل {$stamp} از `{$from}`."
	);
	tp_append_row( 'HISTORY.md', array( tp_tehran(), '`to-cloud`', '`' . $relay . '`', '`' . $head . '`', count( $files ) . ' پرونده از `' . $from . '`' ) );

	$docs_rel = tp_rel( tp_docs() );
	tp_mut( array( 'git', 'add', '-u' ) );
	[ $c, , $e ] = tp_mut( array_merge( array( 'git', 'add', '--' ), $new_files, array( $docs_rel ) ) );
	if ( 0 !== $c ) {
		tp_err( '❌ git add شکست خورد: ' . trim( $e ) );
		return 1;
	}
	[ $c, $o, $e ] = tp_mut( array( 'git', 'commit', '-m', "relay(to-cloud): {$relay} — WIP از {$from} @ {$head}" ) );
	if ( 0 !== $c ) {
		tp_err( "❌ commit رد شد (گارد پیش از commit؟):\n" . trim( $o . "\n" . $e ) );
		tp_err( "شاخهٔ {$relay} ساخته شده و تغییرها staged مانده‌اند؛ پس از رفع، commit و push کن." );
		return 1;
	}
	[ $c, , $e ] = tp_mut( array( 'git', 'push', '-u', 'origin', $relay ) );
	if ( 0 !== $c ) {
		tp_err( '❌ push شکست خورد: ' . trim( $e ) );
		return 1;
	}
	tp_out( $GLOBALS['tp_dry'] ? "[dry] {$relay} ساخته و push نشد (آزمون خشک)." : "✅ {$relay} روی GitHub است." );

	$prompt = "ToPersia relay handoff on branch {$relay}. First read ToPersia Claud/workspace/CLAUDE.md and ToPersia Claud/workspace/docs/relay/CURRENT.md, then do exactly the task in its cloud section. Follow the cloud-mode rules from the SessionStart context. Reply in Persian.";
	$bin    = getenv( 'TP_CLAUDE_BIN' ) ?: 'claude';
	$line   = escapeshellarg( $bin ) . ' --cloud ' . escapeshellarg( $prompt );

	if ( ! empty( $opt['no-launch'] ) || $GLOBALS['tp_dry'] ) {
		tp_out( "\nجلسهٔ ابری را خودت باز کن — یکی از این دو:" );
		tp_out( "  ۱. ترمینال، از ریشهٔ مخزن و روی همین شاخه:\n     {$line}" );
		tp_out( "  ۲. claude.ai/code ← مخزن abashash/ToPersia ← شاخهٔ {$relay} ← همان متن انگلیسی بالا." );
		return 0;
	}
	tp_out( "\nباز کردن جلسهٔ ابری…" );
	$cwd = getcwd();
	chdir( tp_root() );
	passthru( $line, $rc );
	chdir( (string) $cwd );
	if ( 0 !== $rc ) {
		tp_err( "⚠ claude --cloud با کد {$rc} برگشت. اگر claude در PATH نیست TP_CLAUDE_BIN را بده، یا از claude.ai/code شاخهٔ {$relay} را انتخاب کن." );
		return 1;
	}
	tp_out( 'پیشرفت در claude.ai/code یا اپ موبایل. پس از پایان: php tp-relay.php from-cloud' );
	return 0;
}

// ─────────────────────────────────────────────────────── برگشت از ابر (OPS-D004)

function cmd_from_cloud( array $args, array $opt ): int {
	if ( tp_is_cloud() ) {
		tp_err( '❌ from-cloud فقط روی ماشین محلی است.' );
		return 2;
	}
	$default = tp_default_branch();
	tp_out( 'دریافت از GitHub…' );
	[ $c, , $e ] = tp_git( array( 'fetch', 'origin', '--prune' ) );
	if ( 0 !== $c ) {
		tp_err( '❌ fetch شکست خورد: ' . trim( $e ) );
		return 1;
	}
	$base = tp_ref_exists( "refs/heads/{$default}" ) ? $default : "origin/{$default}";

	if ( empty( $args ) ) {
		[ , $refs ] = tp_git( array( 'for-each-ref', '--sort=-committerdate', '--format=%(refname:short)|%(committerdate:relative)|%(subject)', 'refs/remotes/origin/claude', 'refs/remotes/origin/relay' ) );
		$n          = 0;
		foreach ( tp_lines( $refs ) as $l ) {
			[ $ref, $when, $subj ] = explode( '|', $l, 3 ) + array( '', '', '' );
			$ahead = (int) trim( tp_git( array( 'rev-list', '--count', "{$base}..{$ref}" ) )[1] );
			if ( 0 === $ahead ) {
				continue;
			}
			++$n;
			tp_out( '  • ' . preg_replace( '#^origin/#', '', $ref ) . " — {$when} — {$ahead} commit — {$subj}" );
		}
		tp_out( $n ? "\nبازبینی: php tp-relay.php from-cloud <شاخه> --gates" : "هیچ شاخهٔ ابری یا رله‌ای جلوتر از {$default} نیست." );
		return 0;
	}

	$b = (string) preg_replace( '#^(refs/remotes/origin/|origin/)#', '', $args[0] );
	if ( ! tp_ref_exists( "refs/remotes/origin/{$b}" ) ) {
		tp_err( "❌ origin/{$b} پیدا نشد." );
		return 2;
	}
	$tracked_dirty = array_filter( tp_dirty(), static fn( $d ) => '??' !== $d[0] );
	if ( $tracked_dirty ) {
		tp_err( '❌ درخت کار تمیز نیست (' . count( $tracked_dirty ) . ' پروندهٔ پیگیری‌شدهٔ تغییرکرده) — اول commit یا stash کن (همان شرط teleport).' );
		return 2;
	}

	$mb      = trim( tp_git( array( 'merge-base', $base, "origin/{$b}" ) )[1] );
	$cloud_f = tp_lines( tp_git( array( '-c', 'core.quotepath=false', 'diff', '--name-only', $mb, "origin/{$b}" ) )[1] );
	$local_f = tp_lines( tp_git( array( '-c', 'core.quotepath=false', 'diff', '--name-only', $mb, $base ) )[1] );
	$relay_d = tp_rel( tp_docs() ) . '/';
	$overlap = array_values( array_filter( array_intersect( $cloud_f, $local_f ), static fn( $p ) => 0 !== strpos( $p, $relay_d ) ) );

	tp_out( "شاخهٔ {$b}: " . count( $cloud_f ) . " پرونده تغییر از نقطهٔ جدایی با {$default} (" . substr( $mb, 0, 8 ) . ')' );
	tp_out( rtrim( tp_git( array( '-c', 'core.quotepath=false', 'diff', '--stat=120', $mb, "origin/{$b}" ) )[1] ) );
	if ( $overlap ) {
		tp_out( "⚠ هم‌پوشانی مسیر — این پرونده‌ها در {$default} هم پس از جدایی عوض شده‌اند (lane شکسته شده؛ پیش از ادغام دستی ببین):" );
		foreach ( $overlap as $p ) {
			tp_out( "   {$p}" );
		}
	}
	$bad = array_filter( tp_name_issues( $cloud_f, false ), static fn( $i ) => 'FAIL' === $i[0] );
	foreach ( $bad as [ , $p, $why ] ) {
		tp_out( "❌ نام ناسازگار ساختهٔ ابر: {$why}\n   {$p}" );
	}

	$review = 'review/' . str_replace( '/', '-', $b );
	[ $c, , $e ] = tp_git( array( 'switch', '-C', $review, "origin/{$b}" ) );
	if ( 0 !== $c ) {
		tp_err( '❌ ساخت شاخهٔ بازبینی شکست خورد: ' . trim( $e ) );
		return 1;
	}
	tp_out( "✅ روی {$review} هستی (کپی محلی origin/{$b})." );

	$gates = null;
	if ( ! empty( $opt['gates'] ) ) {
		$gates = tp_gates(); // ثبت نمی‌شود تا شاخهٔ بازبینی تمیز بماند؛ پس از squash ثبت می‌شود
	}

	if ( ! empty( $opt['squash'] ) ) {
		if ( null === $gates || ! $gates[0] || $bad ) {
			tp_err( '❌ --squash فقط همراه --gates سبز و بی نام ناسازگار. ادغام انجام نشد.' );
			return 1;
		}
		[ $c, , $e ] = tp_git( array( 'switch', $default ) );
		if ( 0 !== $c ) {
			tp_err( '❌ ' . trim( $e ) );
			return 1;
		}
		[ $c, $o, $e ] = tp_git( array( 'merge', '--squash', "origin/{$b}" ) );
		if ( 0 !== $c ) {
			tp_err( "❌ ادغام تداخل دارد:\n" . trim( $o . "\n" . $e ) . "\nبرگشت: git merge --abort  (یا git reset --merge)" );
			return 1;
		}
		$tip = tp_short( "origin/{$b}" );
		tp_append_row( 'GATES.md', array( tp_tehran(), 'محلی', '`' . $b . '`', '`' . $tip . '`', '✅', $gates[1] ) );
		tp_append_row( 'HISTORY.md', array( tp_tehran(), '`from-cloud`', '`' . $b . '`', '`' . $tip . '`', "squash در `{$default}` — staged، commit با عامل محلی" ) );
		tp_git( array( 'add', '--', tp_rel( tp_docs() ) ) );
		tp_out( "✅ تغییرهای {$b} روی {$default} staged شدند — commit نشده." );
		tp_out( '   بعد: آزمون‌های «در انتظار تأیید محلی» ← commit با پیام دسته‌ای ← hash در docs/COMMIT-LOG.md (قاعدهٔ git پروژه).' );
		return 0;
	}

	tp_out( "\nقدم بعد:" );
	tp_out( '  • آزمون‌های فقط-محلی این تغییر — فهرستشان در «در انتظار تأیید محلی» بخش ابری CURRENT.md.' );
	tp_out( "  • ادغام: php tp-relay.php from-cloud {$b} --gates --squash" );
	tp_out( '  • ادامهٔ همان گفت‌وگو در ترمینال: claude --teleport (جلسه را از فهرست انتخاب کن)' );
	return null === $gates || $gates[0] ? 0 : 1;
}

// ───────────────────────────────────────────────────── آغاز جلسهٔ ابری (قلاب)

function cmd_session_start( array $opt ): int {
	if ( ! tp_is_cloud() && empty( $opt['force'] ) ) {
		return 0; // محلی: بی اثر
	}
	$root = tp_root();
	$w    = tp_workspace();
	$tmp  = rtrim( str_replace( '\\', '/', sys_get_temp_dir() ), '/' ) . '/tp-env.cloud.json';
	$env  = array(
		'_'         => 'ساختهٔ tp-relay session-start برای جلسهٔ ابری — commit نمی‌شود. سرور و پایگاه‌داده نیست؛ آزمون‌های محلی «محیط ناقص» برمی‌گردانند.',
		'root'      => $root,
		'wp'        => $root . '/geo',
		'base'      => 'http://topersia.local',
		'workspace' => $w,
		'php'       => PHP_BINARY,
		'mysql'     => '',
		'db_port'   => 3306,
		'wp_cli'    => '',
	);
	file_put_contents( $tmp, json_encode( $env, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
	$ef = getenv( 'CLAUDE_ENV_FILE' );
	if ( $ef ) {
		file_put_contents( $ef, "export TP_ENV='{$tmp}'\nexport TP_RUN_MODE=cloud\n", FILE_APPEND );
	}

	$task = '';
	if ( preg_match( '#<!-- relay:task -->(.*?)<!-- /relay:task -->#s', (string) @file_get_contents( tp_docs() . '/CURRENT.md' ), $m ) ) {
		$task = trim( $m[1] );
	}
	if ( strlen( $task ) > 6000 ) {
		$task = (string) preg_replace( '/^(.{0,3000}).*$/us', '$1', $task ) . "\n…(ادامه در CURRENT.md)";
	}
	$branch = tp_branch();
	$ws     = tp_rel( $w );
	$envset = $ef ? "TP_ENV برای همهٔ فرمان‌ها به `{$tmp}` اشاره می‌کند." : "TP_ENV خودکار تنظیم نشد؛ آزمون‌ها را با `TP_ENV={$tmp}` اجرا کن.";

	$ctx = <<<TXT
حالت ابری ToPersia — رله ۱٫۰ (OPS-D001…OPS-D009) · شاخه: {$branch}

۱. اول بخوان: `{$ws}/CLAUDE.md` و `{$ws}/docs/relay/CURRENT.md`. کار این جلسه «دستور این جلسهٔ ابری» در بخش ابری است؛ اگر نوشته «(خالی)»، از «قدم بعدی» بخش محلی ادامه بده؛ اگر آن هم روشن نیست بپرس — حدس نزن.
۲. این ماشین کامپیوتر کاربر نیست: WAMP، MariaDB، topersia.local، کروم و فایرفاکس این‌جا نیستند. {$envset}
۳. گیت این محیط: `php "{$ws}/scripts/relay/tp-relay.php" gates` (preflight · php -l · assert.php · assert-uc.php). contrast · acceptance · behaviour · user-flows · *-local.php · WPML · Lighthouse فقط محلی‌اند.
۴. هرگز نگو آزمونی سبز است که این‌جا اجرا نشده؛ هر آزمون محلیِ لازم را در «در انتظار تأیید محلی» بخش ابری CURRENT.md بنویس.
۵. فقط روی شاخهٔ همین جلسه commit و push کن — نه main، نه برچسب، نه Pull Request مگر کاربر بخواهد.
۶. مالکیت مسیرها (lane) در CURRENT.md را رعایت کن. در CURRENT.md فقط «بخش ابری» را ویرایش کن.
۷. افزونه‌ها و پوستهٔ شخص ثالث زیر `geo/` را دست نزن. هیچ کلید، داده، لاگ، بکاپ یا دیتابیسی در git نیاید.
۸. نام پرونده: هر جزء زیر ۲۵۵ بایت، بی <>:"|?*، بی نام رزرو ویندوز (CON · NUL · COM1 …).
۹. پایان هر نوبت: `gates --record` ← «انجام‌شده»، «قدم بعدی» و «در انتظار تأیید محلی» در بخش ابری CURRENT.md ← commit با پیام دسته‌ای ← push ← در پاسخ آخر: شاخه، hash، خلاصهٔ گیت.
۱۰. اعتبار ابری محدود است: در گام‌های تحویل‌پذیر جلو برو و اکتشاف بی‌هدف نکن.
۱۱. زبان پاسخ فارسی؛ قواعد خروجی CLAUDE.md پابرجاست.
TXT;
	if ( '' !== $task && 0 !== strpos( $task, '(خالی' ) ) {
		$ctx .= "\n\n── دستور این جلسه (از CURRENT.md) ──\n" . $task;
	}
	echo json_encode(
		array(
			'hookSpecificOutput' => array(
				'hookEventName'     => 'SessionStart',
				'additionalContext' => $ctx,
			),
		),
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
	);
	return 0;
}

// ───────────────────────────────────────────────────────────────────── راهنما

function cmd_help(): int {
	$v = TP_RELAY_VERSION;
	tp_out(
		<<<TXT
tp-relay {$v} — رلهٔ محلی ⇄ ابری ToPersia (docs/relay/RELAY.md)

  status [--fetch]           شاخه، شاخه‌های رله و ابری، آخرین تحویل و گیت
  preflight                  نام‌های قابل‌حمل ویندوز/لینوکس و پروندهٔ حساس
  to-cloud                   WIP ← شاخهٔ relay/… ← push ← claude --cloud
      --task="…" | --task-file=PATH   دستور جلسه در بخش ابری CURRENT.md
      --all                  پروندهٔ تازهٔ بیرون از workspace هم
      --wip                  تحویل کار نیمه‌کارهٔ درخت کثیف (بی آن، درخت کثیف کد ۲ می‌دهد — OPS-D009)
      --no-launch | --dry-run
  from-cloud [BRANCH]        بی شاخه: فهرست؛ با شاخه: بازبینی روی review/…
      --gates                گیت‌های محلی
      --squash               ادغام squash در شاخهٔ اصلی (staged؛ commit با عامل محلی)
  gates [--record]           گیت‌های همین محیط؛ --record یک سطر در GATES.md
  session-start [--force]    فقط قلاب جلسهٔ ابری
TXT
	);
	return 0;
}

// ───────────────────────────────────────────────────────────────────── اجرا

[ $tp_cmd, $tp_opt, $tp_args ] = tp_parse( $argv );
$GLOBALS['tp_dry']             = ! empty( $tp_opt['dry-run'] );

switch ( $tp_cmd ) {
	case 'status':
		exit( cmd_status( $tp_opt ) );
	case 'preflight':
		exit( tp_preflight() );
	case 'gates':
		exit( cmd_gates( $tp_opt ) );
	case 'to-cloud':
		exit( cmd_to_cloud( $tp_opt ) );
	case 'from-cloud':
		exit( cmd_from_cloud( $tp_args, $tp_opt ) );
	case 'session-start':
		exit( cmd_session_start( $tp_opt ) );
	case 'help':
	case '--help':
	case '-h':
		exit( cmd_help() );
	default:
		tp_err( "فرمان ناشناخته: {$tp_cmd}" );
		cmd_help();
		exit( 2 );
}
