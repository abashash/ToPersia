<?php
/**
 * صفحه‌های ثابت سه‌زبانه از متن تأییدشده — دستۀ 45 بخش الف·۱
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file scripts/tp-legal-pages.php   (TP_DRY=1 فقط گزارش)
 *
 * فقط محلی. متن عیناً از `docs/legal/PAGES-TEXT-2026-11-04.md` (بند ۱ شرایط، ۲ درباره، ۳ تماس) و
 * `docs/legal/PRIVACY-DRAFT.md` (بخش ۲). هیچ متنی ساخته نمی‌شود؛ فقط این تبدیل‌های ثابت:
 *   ۱. خط یادداشت (`>`) و جداکننده (`---`) و سرفصل بخش‌بندی فایل بیرون می‌ماند.
 *   ۲. یادداشت‌های درون‌متنی برای مشاور حقوقی (فهرست بسته در $notes) برداشته می‌شود.
 *   ۳. بند Google Analytics فقط وقتی Site Kit فعال است (یادداشت بخش ۲ PRIVACY-DRAFT):
 *      بی Site Kit، بند فهرست، سطر جدول و جملۀ دارای «Google Analytics» برداشته می‌شود.
 *   ۴. {تاریخ انتشار} / «آخرین به‌روزرسانی» = تاریخ انتشار محلی صفحۀ فارسی، میلادی، رقم زبان صفحه.
 *   ۵. جای‌نگهدار «[تکمیل به دست کاربر …]» دست نمی‌خورد و هشدار چاپ می‌شود.
 * ایدمپوتنت: صفحه با نامک پیدا و به‌روز می‌شود؛ حریم خصوصی همان صفحۀ #wp_page_for_privacy_policy.
 */

if ( ! defined( 'ABSPATH' ) || 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }

$dry  = (bool) getenv( 'TP_DRY' );
$root = dirname( __DIR__ );
$txt  = (string) file_get_contents( $root . '/docs/legal/PAGES-TEXT-2026-11-04.md' );
$prv  = (string) file_get_contents( $root . '/docs/legal/PRIVACY-DRAFT.md' );

if ( '' === $txt || '' === $prv ) { echo "فایل متن نیست\n"; exit( 2 ); }

$txt = str_replace( "\r\n", "\n", $txt );
$prv = str_replace( "\r\n", "\n", $prv );
$warn = array();

/* ─── ابزار ─── */

$digits = function ( $s, $l ) {
	$set = array( 'fa' => array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ), 'ar' => array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ) );
	return isset( $set[ $l ] ) ? str_replace( range( 0, 9 ), $set[ $l ], (string) $s ) : (string) $s;
};

$date = function ( $ts, $l ) use ( $digits ) {
	$m = array(
		'fa' => array( 'ژانویه', 'فوریه', 'مارس', 'آوریل', 'مه', 'ژوئن', 'ژوئیه', 'اوت', 'سپتامبر', 'اکتبر', 'نوامبر', 'دسامبر' ),
		'en' => array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ),
		'ar' => array( 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر' ),
	);
	return $digits( gmdate( 'j', $ts ) . ' ' . $m[ $l ][ (int) gmdate( 'n', $ts ) - 1 ] . ' ' . gmdate( 'Y', $ts ), $l );
};

$inline = function ( $s ) {
	$s = esc_html( $s );
	$s = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s );
	$s = preg_replace( '/`([^`]+)`/u', '<code>$1</code>', $s );
	return $s;
};

/* بلوک‌های مارک‌داون → HTML؛ «## » هر بخش را در section با سرفصل موجود قالب می‌گذارد. */
$render = function ( array $lines ) use ( $inline ) {

	$blocks = array(); $cur = array();
	foreach ( $lines as $ln ) {
		if ( '' === trim( $ln ) ) { if ( $cur ) { $blocks[] = $cur; $cur = array(); } continue; }
		if ( 0 === strpos( $ln, '## ' ) ) { if ( $cur ) { $blocks[] = $cur; $cur = array(); } $blocks[] = array( $ln ); continue; }
		/* فهرست بی سطر خالی پس از پاراگراف (بند ۲ «درباره») بلوک جدا می‌شود. */
		if ( $cur && ( 0 === strpos( $ln, '- ' ) ) !== ( 0 === strpos( $cur[0], '- ' ) ) && 0 !== strpos( $ln, '|' ) ) { $blocks[] = $cur; $cur = array(); }
		$cur[] = $ln;
	}
	if ( $cur ) { $blocks[] = $cur; }

	$out = ''; $open = false; $n = 0; $intro = '';
	foreach ( $blocks as $b ) {
		if ( 1 === count( $b ) && 0 === strpos( $b[0], '## ' ) ) {
			$out .= ( $open ? '</div></section>' : '' ) . '<section class="tp-section tp-legal__sec" aria-labelledby="tp-legal-' . ( ++$n ) . '"><div class="tp-section-head"><div><h2 id="tp-legal-' . $n . '">' . $inline( substr( $b[0], 3 ) ) . '</h2></div></div><div class="tp-longform">';
			$open = true; continue;
		}
		$h = '';
		if ( 0 === strpos( $b[0], '- ' ) ) {
			$h = '<ul>'; foreach ( $b as $ln ) { $h .= '<li>' . $inline( preg_replace( '/^- /', '', $ln ) ) . '</li>'; } $h .= '</ul>';
		} elseif ( 0 === strpos( $b[0], '|' ) ) {
			$rows = array_values( array_filter( $b, function ( $r ) { return ! preg_match( '/^\|[\s|:-]+\|$/', $r ); } ) );
			$cells = function ( $r ) { return array_map( 'trim', explode( '|', trim( $r, " |" ) ) ); };
			$h = '<div class="tp-data-table" tabindex="0" role="group"><table><thead><tr>';
			foreach ( $cells( $rows[0] ) as $c ) { $h .= '<th scope="col">' . $inline( $c ) . '</th>'; }
			$h .= '</tr></thead><tbody>';
			foreach ( array_slice( $rows, 1 ) as $r ) { $h .= '<tr>'; foreach ( $cells( $r ) as $c ) { $h .= '<td>' . $inline( $c ) . '</td>'; } $h .= '</tr>'; }
			$h .= '</tbody></table></div>';
		} else {
			$h = '<p>' . implode( '<br>', array_map( $inline, $b ) ) . '</p>';
		}
		if ( $open ) { $out .= $h; } else { $intro .= $h; }
	}

	return ( '' !== $intro ? '<div class="tp-section tp-longform">' . $intro . '</div>' : '' ) . $out . ( $open ? '</div></section>' : '' );
};

/* ─── استخراج متن ─── */

$chunks = array_map( 'trim', preg_split( '/\n---\n/u', $txt ) );
$need   = array( 2 => '## ۱. شرایط', 3 => '### en', 4 => '### ar', 5 => '## ۲. درباره', 6 => '## ۳. تماس' );
foreach ( $need as $i => $head ) {
	if ( ! isset( $chunks[ $i ] ) || 0 !== strpos( $chunks[ $i ], $head ) ) { echo "ساختار PAGES-TEXT عوض شده (بخش $i باید با «{$head}» شروع شود)\n"; exit( 1 ); }
}

$docs = array();   /* $docs[slug][lang] = [ title, lines[] ] */

/* شرایط: سه تکه. */
foreach ( array( 'fa' => $chunks[2], 'en' => $chunks[3], 'ar' => $chunks[4] ) as $l => $c ) {
	$ls = explode( "\n", $c ); $title = ''; $keep = array();
	foreach ( $ls as $ln ) {
		if ( preg_match( '/^(## ۱\. شرایط|### (fa|en|ar)\s*$)/u', $ln ) ) { continue; }
		if ( '' === $title && preg_match( '/^# (.+)$/u', $ln, $m ) ) { $title = trim( $m[1] ); continue; }
		$keep[] = $ln;
	}
	$docs['terms'][ $l ] = array( $title, $keep );
}

/* درباره: «**fa — عنوان: X**» · «**en — X**» · «**ar — X**». */
$cur = null;
foreach ( explode( "\n", $chunks[5] ) as $ln ) {
	if ( preg_match( '/^\*\*(fa|en|ar) — (?:عنوان: )?(.+)\*\*$/u', $ln, $m ) ) { $cur = $m[1]; $docs['about'][ $cur ] = array( trim( $m[2] ), array() ); continue; }
	if ( $cur ) { $docs['about'][ $cur ][1][] = $ln; }
}

/* تماس: «**fa**» … ؛ عنوان در فایل فقط فارسی است («تماس با ما»)؛ en و ar برچسب رابط. */
$ctitle = array( 'fa' => 'تماس با ما', 'en' => 'Contact us', 'ar' => 'اتصل بنا' );
$cur = null;
foreach ( explode( "\n", $chunks[6] ) as $ln ) {
	if ( preg_match( '/^\*\*(fa|en|ar)\*\*$/u', $ln, $m ) ) { $cur = $m[1]; $docs['contact'][ $cur ] = array( $ctitle[ $cur ], array() ); continue; }
	if ( $cur ) { $docs['contact'][ $cur ][1][] = $ln; }
}

/* حریم خصوصی: PRIVACY-DRAFT بخش ۲. */
if ( ! preg_match( '/^## بخش ۲ — .*?$(.*?)^## بخش ۳ /msu', $prv, $sec ) ) { echo "بخش ۲ PRIVACY-DRAFT پیدا نشد\n"; exit( 1 ); }
$parts = preg_split( '/^### .*\((fa|en|ar)\)\s*$/mu', $sec[1], -1, PREG_SPLIT_DELIM_CAPTURE );
if ( ! function_exists( 'is_plugin_active' ) ) { require_once ABSPATH . 'wp-admin/includes/plugin.php'; }
$sitekit = is_plugin_active( 'google-site-kit/google-site-kit.php' );
$notes   = array( ' (این جمله را مشاور حقوقی تأیید کند.)', ' (To be confirmed by legal counsel.)', ' (يؤكد ذلك المستشار القانوني.)' );
$dropped = array();
for ( $i = 1; $i < count( $parts ); $i += 2 ) {
	$l = $parts[ $i ]; $title = ''; $keep = array();
	foreach ( explode( "\n", $parts[ $i + 1 ] ) as $ln ) {
		if ( 0 === strpos( $ln, '>' ) || '---' === trim( $ln ) ) { continue; }
		if ( '' === $title && preg_match( '/^# (.+)$/u', $ln, $m ) ) { $title = trim( $m[1] ); continue; }
		$ln = str_replace( $notes, '', $ln );
		if ( ! $sitekit && false !== strpos( $ln, 'Google Analytics' ) ) {
			if ( preg_match( '/^(- |\|)/u', $ln ) ) { $dropped[] = "$l: " . mb_substr( $ln, 0, 60 ); continue; }
			$ln = preg_replace_callback( '/\s*[^.]*Google Analytics[^.]*\./u', function ( $m ) use ( &$dropped, $l ) { $dropped[] = "$l: " . mb_substr( trim( $m[0] ), 0, 60 ); return ''; }, $ln );
		}
		$keep[] = $ln;
	}
	$docs['privacy-policy'][ $l ] = array( $title, $keep );
}

foreach ( array( 'terms', 'about', 'contact', 'privacy-policy' ) as $s ) {
	foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
		if ( empty( $docs[ $s ][ $l ][0] ) || ! $docs[ $s ][ $l ][1] ) { echo "متن $s/$l نیست\n"; exit( 1 ); }
		foreach ( $docs[ $s ][ $l ][1] as $ln ) {
			if ( preg_match( '/\[(تکمیل به دست کاربر|to be completed by the owner|يُستكمل من المالك)[^\]]*\]/u', $ln, $m ) ) { $warn[] = "$s/$l: {$m[0]}"; }
		}
	}
}

/* ─── نوشتن ─── */

if ( ! $dry ) { kses_remove_filters(); }

$find = function ( $slug, $lang ) {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->prefix}icl_translations t ON t.element_id = p.ID AND t.element_type = 'post_page' WHERE p.post_type = 'page' AND p.post_name = %s AND t.language_code = %s AND p.post_status IN ('publish','draft','pending','private') ORDER BY p.ID", $slug, $lang ) );
	return $ids ? (int) $ids[0] : 0;
};

$report = array();
foreach ( array( 'terms', 'about', 'contact', 'privacy-policy' ) as $slug ) {

	$fa = 'privacy-policy' === $slug ? (int) get_option( 'wp_page_for_privacy_policy' ) : $find( $slug, 'fa' );
	$fa_post = $fa ? get_post( $fa ) : null;
	/* تاریخ انتشار محلی: نخستین اجرای همین اسکریپت (نه تاریخ ساخت پیش‌نویس)؛ در متای صفحۀ فارسی می‌ماند. */
	$ts = $fa ? (int) get_post_meta( $fa, '_tp_legal_published', true ) : 0;
	$first = ! $ts;
	$ts = $ts ? $ts : time();
	$trid = $fa ? apply_filters( 'wpml_element_trid', null, $fa, 'post_page' ) : null;
	$tr = $trid ? (array) apply_filters( 'wpml_get_element_translations', null, $trid, 'post_page' ) : array();

	foreach ( array( 'fa', 'en', 'ar' ) as $l ) {

		list( $title, $lines ) = $docs[ $slug ][ $l ];
		$stamp = $date( $ts, $l );
		$lines = array_map( function ( $ln ) use ( $stamp ) {
			$ln = str_replace( array( '{تاریخ انتشار}', '{publication date}', '{تاريخ النشر}' ), $stamp, $ln );
			return preg_replace( '/^(\*\*(?:آخرین به‌روزرسانی|Last updated|آخر تحديث):\*\*) .+$/u', '$1 ' . $stamp, $ln );
		}, $lines );

		/* نخستین پاراگراف متن (بی نشانه) = توضیح متا؛ برای تماس سه سطر نخست. */
		$plain = array_values( array_filter( array_map( function ( $ln ) { return trim( preg_replace( '/^- /', '', str_replace( array( '**', '`' ), '', $ln ) ) ); }, $lines ), function ( $ln ) { return '' !== $ln && 0 !== strpos( $ln, '#' ) && 0 !== strpos( $ln, '|' ) && false === strpos( $ln, ' · ' ) && ! preg_match( '/^(ناشر|تماس|آخرین به‌روزرسانی|Publisher|Contact|Last updated|الناشر|التواصل|آخر تحديث):/u', $ln ); } ) );
		$desc  = 'contact' === $slug ? implode( ' · ', array_slice( $plain, 0, 3 ) ) : $plain[0];

		$html = '<!-- wp:html --><div class="tp-page tp-legal"><main id="tp-main" class="tp-main"><div class="tp-wrap">'
			. '<section class="tp-section tp-legal__hero"><h1>' . esc_html( $title ) . '</h1></section>'
			. $render( $lines ) . '</div></main></div><!-- /wp:html -->';

		$id = 'fa' === $l ? $fa : ( isset( $tr[ $l ] ) ? (int) $tr[ $l ]->element_id : $find( $slug, $l ) );
		$arr = array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $html, 'post_excerpt' => $desc, 'comment_status' => 'closed', 'ping_status' => 'closed' );
		if ( $first ) { $arr['post_date'] = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $ts ) ); $arr['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $ts ); }

		if ( $dry ) { $report[] = sprintf( '%-15s %s #%d  %s', $slug, $l, $id, $title ); continue; }

		if ( $id ) { $arr['ID'] = $id; $id = wp_update_post( wp_slash( $arr ), true ); }
		else { $id = wp_insert_post( wp_slash( $arr ), true ); }
		if ( is_wp_error( $id ) ) { echo "خطا $slug/$l: " . $id->get_error_message() . "\n"; exit( 1 ); }

		if ( 'fa' === $l ) {
			$fa = $id;
			if ( ! $trid ) { do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_page', 'trid' => false, 'language_code' => 'fa' ) ); $trid = apply_filters( 'wpml_element_trid', null, $id, 'post_page' ); }
		} else {
			do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_page', 'trid' => $trid, 'language_code' => $l, 'source_language_code' => 'fa' ) );
		}

		/* WPML نامک تکراری را «-2» می‌کند تا زبان روشن شود؛ پس از زبان دوباره همان نامک. */
		if ( get_post_field( 'post_name', $id ) !== $slug ) { wp_update_post( array( 'ID' => $id, 'post_name' => $slug ) ); }

		if ( 'fa' === $l ) { update_post_meta( $id, '_tp_legal_published', $ts ); }
		update_post_meta( $id, '_woodmart_title_off', '1' );
		update_post_meta( $id, 'rank_math_description', $desc );
		delete_post_meta( $id, 'rank_math_robots' );
		clean_post_cache( $id );
		$report[] = sprintf( '%-15s %s #%d  %s  %s', $slug, $l, $id, get_permalink( $id ), get_post_field( 'post_name', $id ) );
	}
}

echo implode( "\n", $report ), "\n";
echo "Site Kit: " . ( $sitekit ? 'فعال — بند GA آمد' : 'نیست — بند GA برداشته شد (' . count( $dropped ) . ')' ) . "\n";
foreach ( $dropped as $d ) { echo "  − $d\n"; }
foreach ( $warn as $w ) { echo "  ⚠ جای‌نگهدار: $w\n"; }
