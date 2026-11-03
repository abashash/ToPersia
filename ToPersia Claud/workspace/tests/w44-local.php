<?php
/**
 * دستهٔ 44 روی وردپرس محلی: SITE_ID از خاستگاه، uid هر پست و شهرستان نمونه، سرفصل هر رابطه در گروه «مرتبط» (P5-D504)،
 * سه نوع موج ۲ج (P5-D485) و سند انحراف TA (P5-D505). پست نمی‌سازد و در پایگاه چیزی نمی‌نویسد: فقط «آزمایشی» (plan)،
 * خواندن صفحه، و دو فرایند فرزند WP-CLI با خاستگاه ساختگی که فقط می‌خوانند.
 *
 *   php wp-cli.phar --path=… --url=http://topersia.local eval-file tests/w44-local.php
 */
if ( 'local' !== wp_get_environment_type() ) { echo "فقط محلی\n"; exit( 2 ); }
require_once __DIR__ . '/env.php';
require_once dirname( __DIR__ ) . '/scripts/lib/tp-rel-coverage-lib.php';
global $wpdb;
$pass = 0; $fail = 0;
$t   = function ( $label, $ok, $note = '' ) use ( &$pass, &$fail ) { $ok ? $pass++ : $fail++; echo '  ' . ( $ok ? '✅' : '❌' ) . ' ' . $label . ( $ok || '' === $note ? '' : '   ← ' . $note ) . "\n"; };
$ws  = dirname( __DIR__ );
$cat = include $ws . '/scripts/i18n/topersia-render.php';
$get = function ( $id, $lang ) {
	$url = apply_filters( 'wpml_permalink', get_permalink( $id ), $lang );
	$r   = wp_remote_get( $url, array( 'timeout' => 120 ) );
	return array( $url, (int) wp_remote_retrieve_response_code( $r ), html_entity_decode( (string) wp_remote_retrieve_body( $r ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
};
$tr  = function ( $fa, $lang ) use ( $cat ) { return 'fa' === $lang ? $fa : (string) ( $cat[ $fa ][ $lang ] ?? '' ); };

/* ۱ — SITE_ID از خاستگاه: فرایند فرزند با خاستگاه دیگر، از راه --require پیش از وردپرس */
$t( 'SITE_ID همان خاستگاه بی «/» پایانی؛ پایهٔ uid همان SITE_ID/e', TOPERSIA_SITE_ID === rtrim( TOPERSIA_CANONICAL_ORIGIN, '/' ) && TOPERSIA_UID_BASE === TOPERSIA_SITE_ID . '/e', TOPERSIA_SITE_ID );
$child = function ( $pre ) {
	$req = tempnam( sys_get_temp_dir(), 'w44r' );
	$evf = tempnam( sys_get_temp_dir(), 'w44e' );
	file_put_contents( $req, "<?php\n" . $pre );
	file_put_contents( $evf, '<?php echo "\n@@" . wp_json_encode( array( TOPERSIA_SITE_ID, TOPERSIA_UID_BASE, topersia_uid_build( "x/y" ), topersia_schema_organization()["@id"], topersia_schema_website()["publisher"]["@id"] ) ) . "\n";' );
	$out = array();
	exec( escapeshellarg( TP_PHP ) . ' ' . escapeshellarg( TP_WP_CLI ) . ' --path=' . escapeshellarg( TP_WP_DIR ) . ' --url=' . escapeshellarg( TP_BASE_URL ) . ' --require=' . escapeshellarg( $req ) . ' eval-file ' . escapeshellarg( $evf ) . ' 2>&1', $out );
	@unlink( $req );
	@unlink( $evf );
	foreach ( $out as $line ) { if ( 0 === strpos( $line, '@@' ) ) { return json_decode( substr( $line, 2 ), true ); } }
	return implode( ' ', array_slice( $out, -3 ) );
};
$follows = function ( $v, $origin ) { $o = rtrim( $origin, '/' ); return array( $o, $o . '/e', $o . '/e/x/y', $o . '/#organization', $o . '/#organization' ) === $v; };
$o1 = 'https://origin.test/';
$v1 = $child( "define( 'TOPERSIA_CANONICAL_ORIGIN', '{$o1}' );\n" );
$v2 = $child( "define( 'TOPERSIA_CANONICAL_ORIGIN', '{$o1}' );\ndefine( 'TOPERSIA_SITE_ID', 'https://topersia.com' );\n" );
$t( 'خاستگاه دیگر در فرایند فرزند: SITE_ID، پایهٔ uid، uid ساخته، @id سازمان و ناشر وب‌سایت همه از همان — جهش: SITE_ID ثابت از پیش می‌افتد',
	$follows( $v1, $o1 ) && is_array( $v2 ) && ! $follows( $v2, $o1 ), wp_json_encode( array( $v1, $v2 ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

/* ۲ — uid هر پست و شهرستان نمونه */
$nouid = function ( $key ) use ( $wpdb ) {
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s WHERE p.post_type LIKE 'tp\\_%%' AND p.post_status NOT IN ( 'trash', 'auto-draft', 'inherit' ) AND ( m.meta_value IS NULL OR m.meta_value = '' )", $key ) );
};
$t( 'هر پست tp_ (بی زباله و پیش‌نویس خودکار) entity_uid دارد — جهش: کلید دیگر می‌افتد', 0 === $nouid( 'entity_uid' ) && 0 < $nouid( 'entity_uid_w44' ), (string) $nouid( 'entity_uid' ) );
$cty = $wpdb->get_col( "SELECT m2.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_status <> 'trash' LEFT JOIN {$wpdb->postmeta} m2 ON m2.post_id = m.post_id AND m2.meta_key = 'entity_uid' WHERE m.meta_key = '_tp_sample_county'" );
$t( 'شهرستان نمونه: سه زبان با یک uid، همان county/sample-county', 3 === count( $cty ) && array( topersia_uid_build( 'county/sample-county' ) ) === array_values( array_unique( $cty ) ), implode( ' | ', $cty ) );

/* ۳ — P5-D504: سرفصل هر رابطه از هر سو */
$rows = tp_rel_heading_rows( $cat );
$m1   = topersia_g_relmap();
foreach ( $m1['tp_calendar']['related']['rels'] as &$rel ) { if ( 244 === (int) $rel['id'] && 'children' === $rel['dir'] ) { $rel['title'] = ''; } }
unset( $rel );
$c1 = $cat;
unset( $c1['رویدادهای اثرگذار بر برنامه‌ریزی سفر'] );
$t( 'هر سوی با یال عنوان سه‌زبانه یا دلیل مکتوب دارد — جهش: عنوان #244 در تقویم یا ترجمه‌اش حذف شود می‌افتد',
	array() === tp_rel_heading_gaps( $rows ) && array( '244:tp_calendar:children' ) === tp_rel_heading_gaps( tp_rel_heading_rows( $cat, $m1 ) ) && array( '244:tp_calendar:children' ) === tp_rel_heading_gaps( tp_rel_heading_rows( $c1 ) ), implode( ' · ', tp_rel_heading_gaps( $rows ) ) );
$tsv = (string) @file_get_contents( $ws . '/docs/ops/REL-HEADINGS-2026-11.tsv' );
/* شکل جدول، بی دو ستون شمار یال که عکس لحظه‌اند: یال محلی تازه گیت را قرمز نمی‌کند، عنوان یا ردیف عوض‌شده می‌کند (بازبینی دستهٔ 44) */
$shape  = function ( $rs ) { return tp_rel_heading_shape( tp_rel_heading_tsv( $rs ) ); };
$counts = array_map( function ( $r ) { $r['real'] += 7; $r['sample'] += 1; return $r; }, $rows );
/* هر ستونِ شکل جداگانه: جهشِ همان یک ستونِ ردیف اول باید شکل را عوض کند (بازبینی دوم دستهٔ 44) */
$blind = array();
foreach ( array( 'id', 'p', 'c', 'page', 'group', 'fa', 'en', 'ar', 'why' ) as $k ) {
	$mut = $rows; $mut[0][ $k ] = 'x' . $mut[0][ $k ];
	if ( tp_rel_heading_shape( $tsv ) === $shape( $mut ) ) { $blind[] = $k; }
}
$t( 'REL-HEADINGS-2026-11.tsv همان شکل خروجی کد است؛ شمار یال سنجیده نمی‌شود — جهش: ردیفی کم شود یا هر ستون شکل (رابطه، والد، فرزند، صفحه، گروه، عنوان fa·en·ar، دلیل) عوض شود می‌افتد، فقط شمار عوض شود نه',
	'' !== $tsv && tp_rel_heading_shape( $tsv ) === $shape( $rows ) && tp_rel_heading_shape( $tsv ) === $shape( $counts ) && tp_rel_heading_shape( $tsv ) !== $shape( array_slice( $rows, 1 ) ) && array() === $blind, $blind ? 'ستون بی‌اثر: ' . implode( ',', $blind ) : '' );

$titles = function ( $pid ) {
	$out = array();
	foreach ( topersia_g_group( $pid, 'related' )['rels'] ?? array() as $rel ) {
		if ( topersia_g_rel_ids( $pid, array( $rel ) ) ) { $out[ (string) ( $rel['title'] ?? '' ) ] = true; }
	}
	return array_keys( $out );
};
$best = array( 0, 0 );
foreach ( topersia_g_relmap() as $type => $groups ) {
	if ( empty( $groups['related']['rels'] ) ) { continue; }
	foreach ( get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false ) ) as $pid ) {
		$n = count( $titles( $pid ) );
		if ( $n > $best[0] ) { $best = array( $n, (int) $pid ); }
	}
}
$cards = function ( $h ) {
	preg_match_all( '#<section id="tp-related(?:-\d+)?"[^>]*>(.*?)</section>#su', $h, $sec );
	$got = array();
	foreach ( $sec[1] as $s ) { if ( preg_match( '#<h3 id="[^"]+">(.*?)</h3>#su', $s, $m ) ) { $got[] = trim( strip_tags( $m[1] ) ); } }
	return array( $got, implode( '', $sec[1] ) );
};
$page_ok = function ( $h, $want ) use ( $cards ) {
	list( $got, $inner ) = $cards( $h );
	preg_match_all( '#<h3 id="[^"]*Title">(.*?)</h3>#su', $h, $h3 );
	preg_match_all( '#<h2[^>]*>(.*?)</h2>#su', $h, $h2 );
	$h3t = array_map( function ( $s ) { return trim( strip_tags( $s ) ); }, $h3[1] );
	$h2t = array_map( function ( $s ) { return trim( strip_tags( $s ) ); }, $h2[1] );
	$a = $got; $b = $want; sort( $a ); sort( $b );
	if ( $a !== $b ) { return 'کارت‌ها: ' . implode( ' · ', $got ); }
	if ( count( $h3t ) !== count( array_unique( $h3t ) ) ) { return 'سرفصل تکراری'; }
	if ( array_intersect( $got, $h2t ) ) { return 'هم‌نام h2'; }
	return false === strpos( $inner, 'tp-more--rail' ) ? true : 'پیوند «همه» بی بایگانی';
};
$arch = array_filter( get_post_types( array(), 'objects' ), function ( $o ) { return 0 === strpos( $o->name, 'tp_' ) && $o->has_archive; } );
$want = $titles( $best[1] );
echo "  · صفحهٔ با بیشترین سرفصل «مرتبط»: #{$best[1]} (" . count( $want ) . ') ' . get_permalink( $best[1] ) . "\n";
foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
	$tid = (int) apply_filters( 'wpml_object_id', $best[1], get_post_type( $best[1] ), false, $l );
	list( $url, $code, $h ) = $get( $tid, $l );
	$wl  = array_map( function ( $fa ) use ( $tr, $l ) { return $tr( $fa, $l ); }, $want );
	$ok  = $page_ok( $h, $wl );
	if ( 'fa' === $l ) {
		$dup = preg_replace( '#(<section id="tp-related"[^>]*>.*?</section>)#su', '$1$1', $h, 1 );
		$ok  = true === $ok && true !== $page_ok( $dup, $wl ) ? true : ( true === $ok ? 'جهش نیفتاد' : $ok );
	}
	$t( "{$l}: هر رابطه کارت و سرفصل خودش به زبان صفحه، بی تکرار، نه هم‌نام h2، بی «همه» بی بایگانی" . ( 'fa' === $l ? ' — جهش: کارت دوباره بیاید می‌افتد' : '' ),
		$tid && 200 === $code && count( $wl ) >= 2 && ! in_array( '', $wl, true ) && ! $arch && true === $ok, "{$url} {$code} " . ( true === $ok ? '' : $ok ) );
}

/* ۴ — P5-D485: سه نوع موج ۲ج */
$wave  = array_filter( topersia_g_map(), function ( $f ) { return ! empty( $f['wave2c'] ); } );
$types = array( 'tp_sports_event' => 'sports-event', 'tp_seasonal_event' => 'seasonal-event', 'tp_religious_event' => 'religious-event' );
$tag   = 'w44-' . strtolower( wp_generate_password( 6, false, false ) );
$pack  = function ( $type, $post ) {
	$tpl = topersia_pack_template( $type, 'fa' );
	return topersia_pack_parse( wp_json_encode( array( 'meta' => $tpl['meta'], 'posts' => array( $post ) ) ) );
};
$req   = function ( $p ) { return array_keys( array_filter( (array) $p['fields'], function ( $r ) { return 'required' === ( $r['status'] ?? '' ); } ) ); };
$src   = array( array( 'source_name' => 'منبع', 'source_url' => 'https://example.org/a', 'source_access_date' => '2026-10-20', 'source_row_status' => 'active' ) );
$keys  = array();
foreach ( $types as $type => $seg ) {
	$sc = topersia_pack_schema( $type );
	$keys[ $type ] = array_values( array_intersect( array_keys( $wave ), array_keys( (array) ( $sc['fields'] ?? array() ) ) ) );
	$t( "{$type}: طرح last_verified_date و source_references را الزامی می‌داند", ! empty( $sc['fields']['last_verified_date']['required'] ) && ! empty( $sc['fields']['source_references']['required'] ) );
	$base = array( 'entity_uid' => topersia_uid_build( $seg . '/' . $tag ), 'slug' => $tag, 'language' => 'fa', 'title' => 'آزمون ' . $tag );
	$p0   = topersia_pack_plan_post( $pack( $type, $base + array( 'fields' => array() ) ), 0 );
	$p1   = topersia_pack_plan_post( $pack( $type, $base + array( 'fields' => array( 'last_verified_date' => '2026-10-20', 'source_references' => $src ) ) ), 0 );
	$t( "{$type}: پست تازه بی تاریخ و منبع «الزامی خالی»؛ با هر دو نه — جهش: همان پرونده با دو فیلد",
		in_array( 'last_verified_date', $req( $p0 ), true ) && in_array( 'source_references', $req( $p0 ), true ) && ! array_intersect( array( 'last_verified_date', 'source_references' ), $req( $p1 ) ), wp_json_encode( array( $req( $p0 ), $req( $p1 ) ) ) );
}
/* انتظار کلید به کلید از STRUCTURE-LOCK، نه فقط شمار: جابه‌جایی دو کلید میان دو نوع با همان شمار هم دیده شود (بازبینی دستهٔ 44) */
$lock   = json_decode( (string) @file_get_contents( $ws . '/docs/ops/STRUCTURE-LOCK.json' ), true );
$sorted = function ( $by ) { foreach ( $by as &$ks ) { sort( $ks ); } unset( $ks ); return $by; };
$expect = array_fill_keys( array_keys( $types ), array() );
foreach ( array_keys( $wave ) as $k ) {
	foreach ( (array) ( $lock['fields'][ $k ]['post_types'] ?? array() ) as $pt ) { if ( isset( $expect[ $pt ] ) ) { $expect[ $pt ][] = $k; } }
}
$expect = $sorted( $expect );
$swap   = $keys;
$swap['tp_religious_event'] = array_map( function ( $k ) { return 'annual_date_rule' === $k ? 'optimal_conditions' : $k; }, $swap['tp_religious_event'] );
$t( 'هر ۱۰ کلید موج در اسکیمای زندهٔ همان نوعی که STRUCTURE-LOCK می‌گوید: ورزشی ۱، فصلی ۴، مذهبی ۵ — جهش: جابه‌جایی کلید با همان شمار می‌افتد',
	is_array( $lock ) && $expect === $sorted( $keys ) && $expect !== $sorted( $swap ) && array( 1, 4, 5 ) === array_map( 'count', array_values( $keys ) ) && 10 === count( $wave ), wp_json_encode( $keys ) );

$sample = function ( $type, $empty ) {
	$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true, 'meta_key' => '_tp_sample', 'meta_value' => 1 ) );
	foreach ( $ids as $id ) {
		if ( $empty === (bool) get_post_meta( $id, '_tp_sample_empty', true ) && 'fa' === apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'post_' . $type ) ) ) { return (int) $id; }
	}
	return 0;
};
$labels_in = function ( $h, $ks, $lang ) use ( $wave, $tr ) {
	$miss = array();
	foreach ( $ks as $k ) { $lb = $tr( $wave[ $k ]['label'], $lang ); if ( '' === $lb || false === strpos( $h, $lb ) ) { $miss[] = $k; } }
	return $miss;
};
/* کنترل مثبت: همان پست رندر شده، نه برگهٔ خطا یا جای دیگر؛ بی این «هیچ برچسبی نیست» روی هر صفحهٔ شکسته هم سبز است (بازبینی دستهٔ 44) */
$is_page = function ( $h, $id ) {
	return $id && 1 === preg_match( '/<body[^>]*class="[^"]*\bpostid-' . (int) $id . '\b/', $h ) && false !== strpos( $h, 'tp-page' ) && false !== strpos( $h, '</html>' ) && false === strpos( $h, 'id="error-page"' );
};
$broken = '<!DOCTYPE html><html><body id="error-page"><div class="wp-die-message">x</div></body></html>';
/* جهش فقط شناسه: همان صفحه با شناسهٔ پست دیگر نباید همان پست شمرده شود (بازبینی دوم دستهٔ 44) */
$other  = function ( $h, $id ) { return str_replace( 'postid-' . $id, 'postid-' . ( $id + 1 ), $h ); };
foreach ( $keys as $type => $ks ) {
	$full  = $sample( $type, false );
	$empty = $sample( $type, true );
	$t( "{$type}: نمونهٔ کامل و نمونهٔ خالی هست", $full && $empty, "{$full} · {$empty}" );
	foreach ( $ks as $k ) {
		$t( "{$type}: {$k} در نمونهٔ کامل مقدار دارد و در نمونهٔ خالی نه", ( '' !== topersia_g_text( $full, $k ) || array() !== topersia_g_rows( $full, $k ) ) && '' === (string) get_post_meta( $empty, $k, true ), "{$full} · {$empty}" );
	}
	foreach ( array( 'fa', 'en', 'ar' ) as $l ) {
		$fid = (int) apply_filters( 'wpml_object_id', $full, $type, false, $l );
		$eid = (int) apply_filters( 'wpml_object_id', $empty, $type, false, $l );
		list( $fu, $fc, $fh ) = $get( $fid, $l );
		list( $eu, $ec, $eh ) = $get( $eid, $l );
		$miss = $labels_in( $fh, $ks, $l );
		$cut  = $ks ? $labels_in( str_replace( $tr( $wave[ $ks[0] ]['label'], $l ), 'x', $fh ), $ks, $l ) : array();
		$t( "{$type} {$l}: صفحهٔ کامل برچسب هر فیلد به زبان صفحه و مهر بازبینی دارد — جهش: برچسبی نیاید یا شناسهٔ پست دیگر باشد می‌افتد",
			$fid && 200 === $fc && $is_page( $fh, $fid ) && ! $is_page( $other( $fh, $fid ), $fid ) && array() === $miss && array( $ks[0] ) === $cut && false !== strpos( $fh, '<p class="tp-verified">' ), "{$fu} {$fc} " . ( $is_page( $fh, $fid ) ? '' : 'نشان صفحه نیست ' ) . implode( ',', $miss ) );
		$e_ok = function ( $h ) use ( $is_page, $eid, $ks, $labels_in, $l ) { return $is_page( $h, $eid ) && $ks === $labels_in( $h, $ks, $l ) && false === strpos( $h, 'tp-verified' ); };
		$t( "{$type} {$l}: صفحهٔ خالی همان پست است و هیچ برچسب و مهری نمی‌سازد — جهش: برگهٔ خطا یا شناسهٔ پست دیگر سبز نمی‌شود", $eid && 200 === $ec && $e_ok( $eh ) && ! $e_ok( $broken ) && ! $e_ok( $other( $eh, $eid ) ), "{$eu} {$ec}" . ( $is_page( $eh, $eid ) ? '' : ' نشان صفحه نیست' ) );
	}
}

/* ۵ — P5-D505: ستون نوع جت و کلاس WPML سند انحراف همان پایگاه زنده */
$doc = (string) @file_get_contents( $ws . '/docs/ops/TA-I18N-DEVIATIONS.md' );
$xml = (string) @file_get_contents( dirname( $ws ) . '/project-knowledge/ToPersia-wpml-config-v1_13.xml' );
preg_match_all( '#<custom-field action="([a-z-]+)">([^<]+)</custom-field>#', $xml, $mm );
$X = array_combine( $mm[2], $mm[1] );
preg_match_all( '#<custom-field action="([a-z-]+)">([^<]+)</custom-field>#', (string) get_option( 'wpml-tm-custom-xml' ), $mm );
$CX = array_combine( $mm[2], $mm[1] );
$jet = array();
foreach ( get_post_types( array(), 'names' ) as $pt ) {
	if ( 0 === strpos( $pt, 'tp_' ) ) { foreach ( (array) ( topersia_pack_schema( $pt )['fields'] ?? array() ) as $k => $d ) { $jet[ $k ][ $pt ] = (string) ( $d['type'] ?? '' ); } }
}
$doc_bad = function ( $doc ) use ( $X, $CX, $jet ) {
	$bad = array(); $n = 0;
	foreach ( explode( "\n", $doc ) as $line ) {
		if ( 0 !== strpos( $line, '| `' ) ) { continue; }
		$c = array_map( 'trim', explode( ' | ', trim( $line, "| \r" ) ) );
		if ( count( $c ) < 6 || ! preg_match( '/^`([a-z_0-9]+)`$/', $c[0], $k ) ) { $bad[] = 'ردیف'; continue; }
		$k = $k[1]; $n++;
		preg_match_all( '/`(tp_[a-z_]+)`/', $c[1], $pts );
		preg_match( '/جت `([a-z\-]+)`/u', $c[3], $j );
		preg_match( '/WPML `([a-z\-]+)`/u', $c[3], $w );
		preg_match( '/^`([a-z\-]+)`/', $c[4], $x );
		foreach ( $pts[1] as $pt ) { if ( ( $jet[ $k ][ $pt ] ?? '' ) !== ( $j[1] ?? '?' ) ) { $bad[] = "{$k}:{$pt}:جت"; } }
		if ( ! $pts[1] ) { $bad[] = "{$k}:نوع"; }
		if ( ( $CX[ $k ] ?? '' ) !== ( $w[1] ?? '?' ) ) { $bad[] = "{$k}:WPML"; }
		if ( ( $X[ $k ] ?? '' ) !== ( $x[1] ?? '?' ) ) { $bad[] = "{$k}:v1_13"; }
	}
	return $n ? $bad : array( 'بی ردیف' );
};
$doc_mut = preg_replace( '/(\| `key_species` \|.*?)جت `textarea`/u', '$1جت `wysiwyg`', $doc, 1 );
$t( 'TA-I18N-DEVIATIONS: نوع جت هر نوع پست، کلاس WPML پایگاه و کلاس v1_13 هر ردیف همان پایگاه زنده — جهش: نوع یک ردیف عوض شود می‌افتد',
	'' !== $doc && array() === $doc_bad( $doc ) && array( 'key_species:tp_protected_area:جت' ) === $doc_bad( $doc_mut ), implode( ' · ', $doc_bad( $doc ) ) );

echo str_repeat( '─', 78 ) . "\n";
printf( "  گذشته: %d   افتاده: %d\n", $pass, $fail );
exit( $fail ? 1 : 0 );
