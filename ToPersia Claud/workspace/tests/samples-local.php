<?php
/**
 * «نمونهٔ کامل» هر ۴۱ نوع — دستهٔ ۳۰ بند ۲. روی وردپرس محلی؛ فقط می‌خواند (صفحه‌ها با GET).
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/samples-local.php [nohttp] [md=<پرونده>]
 *
 * هر نوع: نمونه با `_tp_sample` و noindex، نسخهٔ en و ar منتشرشده؛ ۱۰۰٪ فیلدهای غیرفنی پر (ریپیتر: هر زیرفیلد ردیف
 * اول)؛ فیلدهای translate در ترجمه پر؛ هر رابطه دست‌کم ۲ (یا سقف رابطه) مگر کمبود دلیل‌دار (هدف کمتر از ۲ پست
 * دارد، یا همهٔ هدف‌ها والد تکی دارند)؛ سربرگ ۲۴۰۰×۱۲۰۰ و ۶ گالری WebP با alt سه‌زبانه؛ سه خروجی «در یک نگاه»
 * در بازهٔ TPAS_* بی پرچم بازبینی؛ و روی صفحهٔ هر زبان: ۲۰۰، هیچ «—» در ۴ خانه، هر JSON-LD معتبر ساختاری
 * (@context، و @type در هر گره). «فنی» = excluded اسکیمای بسته + external_reference_uri (sameAs ساختگی گمراه می‌کند).
 */

/* نشانی زبانِ خودِ پست — get_permalink در wp-cli پیشوند زبان WPML را نمی‌گذارد */
$tp_url = function ( $id, $l ) { do_action( 'wpml_switch_language', $l ); $u = get_permalink( $id ); do_action( 'wpml_switch_language', 'fa' ); return $u; };
$http = ! in_array( 'nohttp', (array) ( $args ?? array() ), true );
$mdf  = '';
foreach ( (array) ( $args ?? array() ) as $a ) { if ( 0 === strpos( $a, 'md=' ) ) { $mdf = substr( $a, 3 ); } }

$pass = 0; $fail = 0;
$t = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) {
	$ok ? $pass++ : $fail++;
	echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n";
};
$filled = function ( $v, $d ) {
	if ( 'repeater' === ( $d['type'] ?? '' ) ) {
		if ( ! is_array( $v ) || ! $v ) { return false; }
		$row = reset( $v );
		foreach ( array_keys( (array) ( $d['fields'] ?? array() ) ) as $sk ) { if ( ! isset( $row[ $sk ] ) || '' === $row[ $sk ] || array() === $row[ $sk ] ) { return false; } }
		return true;
	}
	return is_array( $v ) ? (bool) array_filter( $v ) : '' !== trim( (string) $v );
};

global $wpdb;
$types = array_keys( topersia_g_stat_cells() );
$smp   = array();
foreach ( $wpdb->get_results( "SELECT p.ID, p.post_type FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_tp_sample' WHERE p.post_type <> 'attachment' AND p.post_status = 'publish'", ARRAY_A ) as $r ) {
	$l = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => (int) $r['ID'], 'element_type' => $r['post_type'] ) );
	$smp[ $r['post_type'] ][ $l ?: 'fa' ] = (int) $r['ID'];
}
$rels = jet_engine()->relations->get_active_relations();

$miss = array(); $tri = array(); $noidx = array(); $empty = array(); $trempty = array(); $short = array(); $accepted = array(); $img = array(); $ai = array();
$rows = array();
foreach ( $types as $type ) {
	$s = $smp[ $type ] ?? array();
	if ( empty( $s['fa'] ) ) { $miss[] = $type; continue; }
	$pid = $s['fa'];
	foreach ( array( 'en', 'ar' ) as $l ) { if ( empty( $s[ $l ] ) || (int) apply_filters( 'wpml_object_id', $pid, $type, false, $l ) !== $s[ $l ] ) { $tri[] = $type . ':' . $l; } }
	foreach ( $s as $l => $id ) { if ( ! in_array( 'noindex', (array) get_post_meta( $id, 'rank_math_robots', true ), true ) ) { $noidx[] = $type . ':' . $l; } }
	$sc   = topersia_pack_schema( $type );
	$tech = array_merge( (array) ( $sc['excluded'] ?? array() ), array( 'external_reference_uri' ) );
	$n = 0; $f = 0;
	foreach ( $sc['fields'] as $k => $d ) {
		if ( in_array( $k, $tech, true ) ) { continue; }
		$n++;
		if ( $filled( get_post_meta( $pid, $k, true ), $d ) ) { $f++; } else { $empty[] = $type . '.' . $k; }
		if ( 'translate' === ( $d['wpml'] ?? '' ) ) {
			foreach ( array( 'en', 'ar' ) as $l ) { if ( ! empty( $s[ $l ] ) && ! $filled( get_post_meta( $s[ $l ], $k, true ), $d ) ) { $trempty[] = $type . ':' . $l . '.' . $k; } }
		}
	}
	/* روابط */
	$rc = 0; $rn = 0;
	foreach ( $sc['relations'] as $rk => $r ) {
		$o = $rels[ $r['id'] ] ?? null;
		if ( ! $o ) { continue; }
		$rn++;
		$ids  = (array) ( 'parents' === $r['direction'] ? $o->get_parents( $pid, 'ids' ) : $o->get_children( $pid, 'ids' ) );
		$c    = count( $ids );
		$rc  += $c;
		$need = $r['max'] ? min( 2, $r['max'] ) : 2;
		if ( $c >= $need ) { continue; }
		$pool = get_posts( array( 'post_type' => $r['target'], 'numberposts' => -1, 'post_status' => 'publish', 'fields' => 'ids', 'suppress_filters' => false ) );
		$pool = array_diff( array_map( 'intval', $pool ), array( $pid ) );
		$rt   = $o->get_args( 'type' );
		$free = 'children' === $r['direction'] && in_array( $rt, array( 'one_to_many', 'one_to_one' ), true )
			? array_filter( $pool, function ( $x ) use ( $o, $ids ) { return in_array( $x, array_map( 'intval', $ids ), true ) || ! $o->get_parents( $x, 'ids' ); } )
			: $pool;
		/* همان قاعدهٔ مولد: پست واقعی فقط در چندبه‌چند یا والد واقعی فرزندِ نمونه در یک‌به‌چند */
		$real_ok = 'many_to_many' === $rt || ( 'parents' === $r['direction'] && 'one_to_many' === $rt );
		if ( ! $real_ok ) { $free = array_filter( $free, function ( $x ) use ( $ids ) { return in_array( $x, array_map( 'intval', $ids ), true ) || get_post_meta( $x, '_tp_sample', true ); } ); }
		$why = count( $pool ) < $need ? 'هدف ' . $r['target'] . ' فقط ' . count( $pool ) . ' پست دارد'
			: ( count( $free ) < $need ? ( $real_ok ? 'هر ' . $r['target'] . ' والد تکی دارد' : 'تک‌والد: نمونه والد پست واقعی نمی‌شود و نمونهٔ آزاد ' . $r['target'] . ' کم است' ) : '' );
		$lab = $type . ' → ' . $r['target'] . ' (' . $r['id'] . ' ' . $r['direction'] . '): ' . $c;
		if ( $why && $c >= min( $need, count( $free ) ) ) { $accepted[] = $lab . ' — ' . $why; } else { $short[] = $lab; }
	}
	/* تصویر */
	$hero = (int) get_post_meta( $pid, 'hero_image', true );
	$m    = wp_get_attachment_metadata( $hero );
	$gal  = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $pid, 'featured_image_gallery', true ) ) ) );
	$ok   = $hero && 2400 === (int) ( $m['width'] ?? 0 ) && 1200 === (int) ( $m['height'] ?? 0 ) && 'image/webp' === get_post_mime_type( $hero ) && 6 === count( $gal );
	foreach ( array_merge( array( $hero ), $gal ) as $aid ) {
		foreach ( array( 'en', 'ar' ) as $l ) {
			$tid = (int) apply_filters( 'wpml_object_id', $aid, 'attachment', false, $l );
			if ( ! $tid || $tid === $aid || '' === (string) get_post_meta( $tid, '_wp_attachment_image_alt', true ) ) { $ok = false; }
		}
		if ( '' === (string) get_post_meta( $aid, '_wp_attachment_image_alt', true ) || ! get_post_meta( $aid, '_tp_sample', true ) ) { $ok = false; }
	}
	if ( ! $ok ) { $img[] = $type; }
	/* در یک نگاه */
	foreach ( $s as $l => $id ) {
		foreach ( array( 'summary', 'keypoints', 'plan' ) as $at ) {
			$g = function_exists( 'tpas_get_summary' ) ? tpas_get_summary( $id, $at, $l ) : null;
			if ( ! $g || ! empty( $g['review'] ) ) { $ai[] = $type . ':' . $l . '.' . $at . ( $g ? ' (' . $g['chars'] . ')' : '' ); }
		}
	}
	$rows[ $type ] = array( 'ids' => $s, 'fill' => $n ? round( 100 * $f / $n ) : 100, 'n' => $n, 'rel' => $rc, 'reln' => $rn );
}
$t( 'هر ۴۱ نوع یک نمونه (منتشرشده، `_tp_sample`)', ! $miss && 41 === count( $types ), implode( ' ', $miss ) );
$t( 'سه‌زبانه: en و ar منتشرشده و پیوندخورده با WPML', ! $tri, implode( ' ', $tri ) );
$t( 'noindex (rank_math_robots) روی هر نسخه', ! $noidx, implode( ' ', $noidx ) );
$t( '۱۰۰٪ فیلدهای غیرفنی پر (ریپیتر: هر زیرفیلد)', ! $empty, implode( ' ', array_slice( $empty, 0, 20 ) ) );
$t( 'فیلدهای translate در en و ar پر', ! $trempty, implode( ' ', array_slice( $trempty, 0, 20 ) ) );
$t( 'هر رابطه دست‌کم ۲ (یا سقف) — جز کمبود دلیل‌دار', ! $short, implode( ' · ', array_slice( $short, 0, 20 ) ) );
$t( 'تصویر: سربرگ ۲۴۰۰×۱۲۰۰ WebP، ۶ گالری، alt سه‌زبانه', ! $img, implode( ' ', $img ) );
/* دستهٔ 32 بند 5: ویدیوی نمونه پروندهٔ محلی است (پخش‌شدنی)، نه example.org */
$vbad = array();
foreach ( $rows as $type => $r ) {
	foreach ( $r['ids'] as $l => $id ) {
		$v = (string) get_post_meta( $id, 'video_sample', true );
		$p = (string) parse_url( $v, PHP_URL_PATH );
		$loc = $p ? wp_normalize_path( ABSPATH . ltrim( preg_replace( '#^/assets/#', 'wp-content/', $p ), '/' ) ) : '';
		if ( false !== strpos( $v, 'example.org' ) || 'webm' !== pathinfo( $p, PATHINFO_EXTENSION ) || ! is_file( $loc ) ) { $vbad[] = $type . ':' . $l; }
	}
}
$t( 'ویدیوی نمونه: پروندهٔ webm محلی که روی دیسک هست، نه example.org', ! $vbad, implode( ' ', array_slice( $vbad, 0, 10 ) ) );
$t( '«در یک نگاه»: سه خروجی در بازه، هر زبان', ! $ai, implode( ' ', array_slice( $ai, 0, 20 ) ) );
foreach ( $accepted as $a ) { echo '  · کمبود پذیرفته: ' . $a . "\n"; }

/* صفحه‌ها */
$galbad = array(); $dash = array(); $ld = array(); $bad = array(); $urls = array(); $knownhit = array();
/* دستهٔ 32: فهرست «ایراد رندر شناخته» خالی شد (گیت زبان برچسب گزینه را به زبان صفحه ترجمه می‌کند)؛ استثنای تازه نباید برگردد */
$known = array();
if ( $http ) {
	foreach ( $rows as $type => $r ) {
		foreach ( $r['ids'] as $l => $id ) {
			$u = $tp_url( $id, $l );
			$urls[ $type ][ $l ] = $u;
			$res = wp_remote_get( $u, array( 'timeout' => 90, 'redirection' => 0, 'sslverify' => false ) );
			$code = is_wp_error( $res ) ? $res->get_error_message() : wp_remote_retrieve_response_code( $res );
			$html = is_wp_error( $res ) ? '' : wp_remote_retrieve_body( $res );
			if ( ! preg_match( '#<html[^>]*\blang="' . $l . '#', $html ) ) { $bad[] = $type . ':' . $l . ' (زبان صفحه)'; continue; }
			if ( 200 !== $code || false === strpos( $html, 'class="tp-stats"' ) ) { $bad[] = $type . ':' . $l . ' ' . $code; continue; }
			preg_match_all( '#data-tp-k="([^"]+)"><span class="tp-stat__k">[^<]*</span><b class="tp-stat__v"[^>]*>\s*—\s*</b>#u', $html, $dm );
			foreach ( $dm[1] as $ck ) {
				$id3 = $l . ':' . $type . ':' . $ck;
				if ( isset( $known[ $id3 ] ) ) { $knownhit[ $id3 ] = 1; } else { $dash[] = $id3; }
			}
			if ( 'fa' !== $l && preg_match( '#alt="(?:Image|صورة) [0-9٠-٩]+"#u', $html ) ) { $galbad[] = $type . ':' . $l; }   /* دستهٔ 32 بند 3: پیوست ترجمهٔ همان زبان، نه «Image n» */
			preg_match_all( '#<script type="application/ld\+json"[^>]*>(.*?)</script>#s', $html, $mm );
			if ( ! $mm[1] ) { $ld[] = $type . ':' . $l . ' (هیچ)'; }
			foreach ( $mm[1] as $j ) {
				$o = json_decode( $j, true );
				$nodes = is_array( $o ) ? ( $o['@graph'] ?? ( isset( $o[0] ) ? $o : array( $o ) ) ) : null;
				$okj = is_array( $o ) && ( isset( $o['@context'] ) || isset( $o[0]['@context'] ) ) && $nodes;
				foreach ( (array) $nodes as $nd ) { if ( ! is_array( $nd ) || empty( $nd['@type'] ) ) { $okj = false; } }
				if ( ! $okj ) { $ld[] = $type . ':' . $l . ' ' . ( null === $o ? json_last_error_msg() : 'ساختار' ); }
			}
		}
	}
	$t( 'گالری en و ar شرح پیوستِ همان زبان را دارد، نه «Image n» (نگاشت wpml_object_id)', ! $galbad, implode( ' ', $galbad ) );
	$t( 'هر صفحه ۲۰۰ با ۴ خانه', ! $bad, implode( ' ', $bad ) );
	$t( 'هیچ «—» در ۴ خانه (هر نوع، هر زبان) — جز ایراد شناختهٔ دلیل‌دار', ! $dash, implode( ' ', $dash ) );
	$stale = array_diff_key( $known, $knownhit );
	$t( 'فهرست ایراد شناخته کهنه نیست (هر مورد هنوز رخ می‌دهد)', ! $stale, implode( ' ', array_keys( $stale ) ) );
	foreach ( $knownhit as $k => $x ) { echo '  · ایراد شناخته: ' . $k . ' — ' . $known[ $k ] . "
"; }
	$t( 'JSON-LD معتبر ساختاری (@context، @type هر گره)', ! $ld, implode( ' ', array_slice( $ld, 0, 20 ) ) );
}

if ( $mdf ) {
	$o = "| نوع | fa | en | ar | پرشدن | رابطه (یال/رابطه‌ها) |\n|---|---|---|---|---|---|\n";
	foreach ( $rows as $type => $r ) {
		$cell = function ( $l ) use ( $r, $tp_url ) { return isset( $r['ids'][ $l ] ) ? '[' . $r['ids'][ $l ] . '](' . $tp_url( $r['ids'][ $l ], $l ) . ')' : '—'; };
		$o .= '| `' . $type . '` | ' . $cell( 'fa' ) . ' | ' . $cell( 'en' ) . ' | ' . $cell( 'ar' ) . ' | ' . $r['fill'] . '٪ (' . $r['n'] . ') | ' . $r['rel'] . ' / ' . $r['reln'] . " |\n";
	}
	file_put_contents( $mdf, $o . "\n" . ( $accepted ? "کمبود پذیرفتهٔ رابطه:\n\n- " . implode( "\n- ", $accepted ) . "\n" : '' ) );
}
echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
if ( $fail ) { exit( 1 ); }
