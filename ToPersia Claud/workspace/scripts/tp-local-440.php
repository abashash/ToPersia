<?php
/**
 * نسخهٔ محلی ۴٫۴٫۰ (فاز ۱) — فقط روی topersia.local.
 *
 * هیچ‌چیز از این پرونده به سایت زنده نمی‌رود: نگهبانِ آغاز بیرون از
 * `topersia.local` با محیط `local` اجرا نمی‌شود. هر گام جداست و اجرای
 * دوباره‌اش بی‌خطر است.
 *
 *   ai-db      ارتقای جدول خلاصه‌ساز: کلید یکتای کهنهٔ post_language — پیش و پس      ۱٫۱۰
 *   ai-clean   خروجی‌های بی‌معنای ذخیره‌شده — ردیف جدول و متای کهنهٔ همتا            ۱٫۱۰
 *   he-count   شمار «هٔ» دونویسه و «ۀ» در محتوای پایگاه‌داده — فقط گزارش           ۱٫۹
 *   admin      کاربر مدیر محلی با wp_insert_user                                    ۱٫۱۱
 *
 *   php scripts/tp-local-440.php ai-db ai-clean he-count
 *   php scripts/tp-local-440.php admin --login=<نام> --email=<نشانی>
 *
 * گام admin: نام و نشانی فقط از خط فرمان می‌آیند و گذرواژه همین‌جا با
 * wp_generate_password ساخته می‌شود؛ هر دو فقط یک بار روی خروجی استاندارد
 * چاپ می‌شوند — نه در پرونده، نه در لاگ، نه در پایگاه‌داده جز درهم‌سازیِ
 * خودِ وردپرس. کاربرِ موجود دوباره ساخته نمی‌شود و گذرواژه‌اش عوض نمی‌شود.
 */

require_once dirname( __DIR__ ) . '/tests/env.php';

$_SERVER['HTTP_HOST']      = TP_HOST;
$_SERVER['SERVER_NAME']    = TP_HOST;
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

const TP_WP = TP_WP_DIR . '/';

$args  = array_slice( $argv, 1 );
$steps = array_values( array_filter( $args, function ( $a ) { return 0 !== strpos( $a, '--' ); } ) );
$opts  = array();
foreach ( $args as $a ) {
	if ( preg_match( '/^--([a-z]+)=(.*)$/', $a, $m ) ) {
		$opts[ $m[1] ] = $m[2];
	}
}

if ( ! $steps || array_diff( $steps, array( 'ai-db', 'ai-clean', 'he-count', 'admin' ) ) ) {
	fwrite( STDERR, "گام؟ ai-db ai-clean he-count admin\n" );
	exit( 2 );
}

require TP_WP . 'wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخهٔ محلی — topersia.local، محیط local، پایگاه topersia_local\n" );
	exit( 2 );
}

global $wpdb;

/* ── ai-db ─────────────────────────────────────────────────────────── */

function tp440_ai_db() {

	global $wpdb;

	if ( ! class_exists( 'TP_AI_DB' ) ) {
		echo "ai-db: خلاصه‌ساز فعال نیست\n";
		return;
	}

	$table = TP_AI_DB::summaries_table();
	$has   = function () use ( $wpdb, $table ) {
		return (bool) $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name = %s", TP_AI_DB::LEGACY_INDEX ) );
	};

	$before = $has();
	$ver    = get_option( TP_AI_DB::VERSION_KEY );

	/* همان روالی که افزونه در plugins_loaded صدا می‌زند — نه SQL دستی */
	TP_AI_DB::install();

	$after  = $has();
	$again  = TP_AI_DB::drop_legacy_index();   /* تکرارپذیر: بار دوم هیچ ALTER */

	printf( "ai-db: %s — post_language پیش %s، پس %s؛ اجرای دوباره %s؛ نسخهٔ طرح %s ← %s\n",
		$table, $before ? 'بود' : 'نبود', $after ? 'هست' : 'نیست', $again ? 'باز حذف کرد!' : 'هیچ کاری نکرد',
		var_export( $ver, true ), get_option( TP_AI_DB::VERSION_KEY ) );
}

/* ── ai-clean ──────────────────────────────────────────────────────── */

/**
 * بی‌معنا یعنی یکی از این‌ها — قاعده، نه فهرست شناسه:
 *   خالی
 *   متای خام: دادهٔ سریال‌شده، کلیدِ برچسب‌خوردهٔ بیرون از واژه‌نامه («Copied
 *     Media Ids»، WPML، وضعیت)، یا «Array»
 *   زبان و خط ناهمخوان: خروجیِ en با بیشتر حروف عربی‌نویسه، یا fa/ar با
 *     بیشتر حروف لاتین
 */
function tp440_meaningless( $text, $lang ) {

	$text = trim( wp_strip_all_tags( (string) $text ) );

	if ( '' === $text ) {
		return 'خالی';
	}

	if ( preg_match( '/a:\d+:\{|s:\d+:"|\bCopied Media Ids\b|\bWpml\b|_wpml|\bContent Status\b|:\s*Array\b/u', $text ) ) {
		return 'متای خام';
	}

	$letters = preg_replace( '/[^\p{L}]+/u', '', $text );
	$n       = max( 1, mb_strlen( $letters ) );
	$arabic  = preg_match_all( '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $letters ) / $n;

	if ( 'en' === $lang && $arabic > 0.5 ) {
		return 'زبان en، متن فارسی';
	}

	if ( in_array( $lang, array( 'fa', 'ar' ), true ) && $arabic < 0.5 ) {
		return 'زبان ' . $lang . '، متن لاتین';
	}

	return '';
}

function tp440_ai_clean() {

	global $wpdb;

	if ( ! class_exists( 'TP_AI_DB' ) ) {
		echo "ai-clean: خلاصه‌ساز فعال نیست\n";
		return;
	}

	$table = TP_AI_DB::summaries_table();
	$rows  = $wpdb->get_results( "SELECT id, post_id, type, language, content FROM {$table}" );
	$gone  = 0;

	foreach ( $rows as $r ) {

		$why = tp440_meaningless( $r->content, $r->language );

		if ( '' === $why ) {
			continue;
		}

		$wpdb->delete( $table, array( 'id' => (int) $r->id ), array( '%d' ) );

		/* متای کهنهٔ همتا — وگرنه get_cached همان را از آن‌جا برمی‌گرداند */
		$key  = '_tpas_ai_' . $r->type . '_' . $r->language;
		$meta = get_post_meta( (int) $r->post_id, $key, true );
		$mdel = is_array( $meta ) && '' !== tp440_meaningless( isset( $meta['text'] ) ? $meta['text'] : '', $r->language )
			? delete_post_meta( (int) $r->post_id, $key ) : false;

		printf( "ai-clean: ردیف #%d پست %d %s/%s — %s؛ متای همتا %s\n", $r->id, $r->post_id, $r->type, $r->language, $why, $mdel ? 'هم' : 'نبود یا معنادار' );
		$gone ++;
	}

	/* متای کهنهٔ بی‌ردیف هم با همان قاعده */
	$metas = $wpdb->get_results( "SELECT meta_id, post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_tpas\\_ai\\_%'" );

	foreach ( $metas as $m ) {

		if ( ! preg_match( '/^_tpas_ai_([a-z]+)_([a-z]{2})$/', $m->meta_key, $mm ) ) {
			continue;
		}

		$v   = maybe_unserialize( $m->meta_value );
		$why = tp440_meaningless( is_array( $v ) && isset( $v['text'] ) ? $v['text'] : ( is_string( $v ) ? $v : '' ), $mm[2] );

		if ( '' !== $why ) {
			delete_metadata_by_mid( 'post', (int) $m->meta_id );
			printf( "ai-clean: متای %s پست %d — %s\n", $m->meta_key, $m->post_id, $why );
			$gone ++;
		}
	}

	printf( "ai-clean: %d خروجی بی‌معنا پاک شد؛ %d ردیف معنادار ماند\n", $gone, (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) );
}

/* ── he-count ──────────────────────────────────────────────────────── */

function tp440_he_count() {

	global $wpdb;

	$two = "\u{0647}\u{0654}";
	$one = "\u{06C0}";

	$cols = array(
		'posts · عنوان'           => array( $wpdb->posts, 'post_title', "post_type NOT IN ('revision','auto-draft')" ),
		'posts · متن'             => array( $wpdb->posts, 'post_content', "post_type NOT IN ('revision','auto-draft')" ),
		'posts · چکیده'           => array( $wpdb->posts, 'post_excerpt', "post_type NOT IN ('revision','auto-draft')" ),
		'postmeta'                => array( $wpdb->postmeta, 'meta_value', "meta_key NOT LIKE '\\_edit\\_%'" ),
		'terms · نام'             => array( $wpdb->terms, 'name', '1=1' ),
		'term_taxonomy · توضیح'   => array( $wpdb->term_taxonomy, 'description', '1=1' ),
		'options'                 => array( $wpdb->options, 'option_value', "option_name NOT LIKE '\\_transient%' AND option_name NOT LIKE '\\_site\\_transient%'" ),
	);

	$sum = array( 0, 0 );

	foreach ( $cols as $label => $c ) {

		list( $table, $col, $where ) = $c;

		/* REPLACE حساس به نویسه است، نه به هم‌ترازی — «هٔ» و «ۀ» جدا شمرده می‌شوند */
		$n2 = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE( SUM( ( CHAR_LENGTH( `{$col}` ) - CHAR_LENGTH( REPLACE( `{$col}`, %s, '' ) ) ) / 2 ), 0 ) FROM {$table} WHERE {$where} AND `{$col}` LIKE BINARY %s",
			$two, '%' . $wpdb->esc_like( $two ) . '%'
		) );
		$n1 = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE( SUM( CHAR_LENGTH( `{$col}` ) - CHAR_LENGTH( REPLACE( `{$col}`, %s, '' ) ) ), 0 ) FROM {$table} WHERE {$where} AND `{$col}` LIKE BINARY %s",
			$one, '%' . $wpdb->esc_like( $one ) . '%'
		) );

		printf( "he-count: %-24s «هٔ» دونویسه %7d · «ۀ» یک‌نویسه %6d\n", $label, $n2, $n1 );
		$sum[0] += $n2;
		$sum[1] += $n1;
	}

	$pub = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COALESCE( SUM( ( CHAR_LENGTH( post_content ) + CHAR_LENGTH( post_title ) - CHAR_LENGTH( REPLACE( post_content, %s, '' ) ) - CHAR_LENGTH( REPLACE( post_title, %s, '' ) ) ) / 2 ), 0 ) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type NOT IN ('revision','attachment','nav_menu_item')",
		$two, $two
	) );

	printf( "he-count: جمع «هٔ» دونویسه %d · «ۀ» یک‌نویسه %d — از این میان در عنوان و متن پست‌های منتشرشده %d. داده دست نخورد.\n", $sum[0], $sum[1], $pub );
}

/* ── admin ─────────────────────────────────────────────────────────── */

function tp440_admin( $opts ) {

	$login = isset( $opts['login'] ) ? sanitize_user( $opts['login'], true ) : '';
	$email = isset( $opts['email'] ) ? sanitize_email( $opts['email'] ) : '';

	if ( '' === $login || '' === $email || ! is_email( $email ) || ! preg_match( '/@topersia\.local$/', $email ) ) {
		fwrite( STDERR, "admin: --login= و --email=…@topersia.local لازم است\n" );
		exit( 2 );
	}

	if ( username_exists( $login ) || email_exists( $email ) ) {
		echo "admin: کاربر هست — دوباره ساخته نشد و گذرواژه‌اش عوض نشد\n";
		return;
	}

	$pass = wp_generate_password( 20, true, false );
	$id   = wp_insert_user( array(
		'user_login'   => $login,
		'user_pass'    => $pass,
		'user_email'   => $email,
		'role'         => 'administrator',
		'display_name' => $login,
	) );

	if ( is_wp_error( $id ) ) {
		fwrite( STDERR, 'admin: ' . $id->get_error_message() . "\n" );
		exit( 1 );
	}

	$u = get_userdata( $id );

	/* تنها جایی که گذرواژه دیده می‌شود — خروجی استاندارد همین اجرا */
	printf( "admin: شناسه %d · نقش %s · گذرواژه‌ی ۲۰ نویسه‌ای درست است: %s\n", $id, implode( ',', $u->roles ),
		wp_check_password( $pass, $u->user_pass, $id ) ? 'بله' : 'نه' );
	echo "admin-credential: ", $login, ' ', $pass, "\n";
}

foreach ( $steps as $step ) {
	switch ( $step ) {
		case 'ai-db':    tp440_ai_db(); break;
		case 'ai-clean': tp440_ai_clean(); break;
		case 'he-count': tp440_he_count(); break;
		case 'admin':    tp440_admin( $opts ); break;
	}
}
