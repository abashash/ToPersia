<?php
/**
 * فاز ۲ج SPRINT-4.4 روی وردپرسِ واقعیِ محلی — ادعاهایی که دادۀ واقعی می‌خواهند.
 *
 *   php tests/phase2c-local.php
 *
 * نگهبان: فقط topersia.local، محیط local، پایگاه topersia_local. هر دادۀ آزمون
 * همین‌جا ساخته و در پایان پاک می‌شود (پست موقت، متا، ردیف رابطه).
 *
 *   ۲ج٫۴  قالب کشور  P5-D281 · P5-D282
 *
 * هر ادعا جهش دارد. کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص
 */

$_SERVER['HTTP_HOST']      = 'topersia.local';
$_SERVER['SERVER_NAME']    = 'topersia.local';
$_SERVER['SERVER_PORT']    = 80;
$_SERVER['REQUEST_URI']    = '/';
$_SERVER['REMOTE_ADDR']    = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

require 'C:/wamp64/www/topersia/geo/wp-load.php';

if ( 'local' !== wp_get_environment_type() || false === strpos( home_url(), 'topersia.local' ) || 'topersia_local' !== DB_NAME ) {
	fwrite( STDERR, "فقط نسخۀ محلی\n" );
	exit( 2 );
}

if ( ! function_exists( 'topersia_g_type_matrix' ) || ! function_exists( 'topersia_relation' ) ) {
	fwrite( STDERR, "render فاز ۲ج یا هسته روی نسخۀ محلی نیست — اول همگام‌سازی\n" );
	exit( 2 );
}

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;

function t( $label, $ok, $note = '' ) {
	if ( $ok ) { $GLOBALS['pass'] ++; echo "  ✅ {$label}\n"; }
	else       { $GLOBALS['fail'] ++; echo "  ❌ {$label}" . ( '' !== $note ? "   ← {$note}" : '' ) . "\n"; }
}

function mut( $label, callable $claim, $real, $mutant, $note = '' ) {
	$ok  = (bool) $claim( $real );
	$bad = (bool) $claim( $mutant );
	t( $label, $ok && ! $bad, ( $ok ? '' : 'روی رفتار واقعی افتاد' ) . ( $bad ? ' · جهش را نگرفت' : '' ) . ( '' !== $note ? ' · ' . $note : '' ) );
}

function section( $s ) { echo "\n{$s}\n" . str_repeat( '─', 78 ) . "\n"; }

$cleanup = array( 'posts' => array(), 'rel' => array() );
$GLOBALS['cleanup'] = &$cleanup;

register_shutdown_function( function () use ( &$cleanup ) {
	foreach ( $cleanup['rel'] as $r ) {
		$rel = topersia_relation( $r[0] );
		if ( $rel ) { $rel->delete_rows( $r[1], $r[2] ); }
	}
	foreach ( $cleanup['posts'] as $p ) {
		wp_delete_post( $p, true );
	}
} );

function tmp_post( $type, $title, $status = 'publish', $meta = array() ) {
	$id = wp_insert_post( array( 'post_type' => $type, 'post_status' => $status, 'post_title' => $title, 'post_content' => '' ), true );
	if ( is_wp_error( $id ) ) {
		fwrite( STDERR, 'tmp_post: ' . $id->get_error_message() . "\n" );
		exit( 2 );
	}
	/* پستِ ساختۀ خط فرمان زبان WPML نمی‌گیرد و دروازۀ زبان (P5-D247) کنارش می‌گذارد — مثل ویرایشگر، فارسی */
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_' . $type, 'trid' => false, 'language_code' => 'fa' ) );
	foreach ( $meta as $k => $v ) { update_post_meta( $id, $k, $v ); }
	$GLOBALS['cleanup']['posts'][] = $id;
	return (int) $id;
}

function tmp_rel( $rel_id, $parent, $child ) {
	$rel = topersia_relation( $rel_id );
	if ( ! $rel ) {
		fwrite( STDERR, "رابطۀ {$rel_id} نیست\n" );
		exit( 2 );
	}
	$rel->update( $parent, $child );
	$GLOBALS['cleanup']['rel'][] = array( $rel_id, $parent, $child );
}

function local_get( $path ) {
	$res = wp_remote_get( 'http://topersia.local' . $path, array( 'timeout' => 300, 'redirection' => 0 ) );
	return is_wp_error( $res ) ? array( 'code' => 0, 'body' => '' ) : array( 'code' => (int) wp_remote_retrieve_response_code( $res ), 'body' => (string) wp_remote_retrieve_body( $res ) );
}

/** HTML بی `<script>` — قالب‌های Vue و پیکربندی JS افزونه‌ها «خروجی دیدنی» نیستند. */
function visible_dom( $html ) {
	return (string) preg_replace( '~<script\b[^>]*>.*?</script>~su', '', $html );
}

/** یک بخش با شناسه — از `<section id>` تا بستنش. */
function section_html( $html, $id ) {
	return preg_match( '~<section id="' . preg_quote( $id, '~' ) . '"[^>]*>.*?</section>~su', $html, $m ) ? $m[0] : '';
}

/** شمار اعضای یک مودالِ فهرست (`data-tp-total`)، یا -1. */
function modal_total( $html, $id ) {
	return preg_match( '~id="' . preg_quote( $id, '~' ) . '"[^>]*data-tp-total="(\d+)"~', $html, $m ) ? (int) $m[1] : -1;
}

/** فاز: صفحۀ یک پست، درون همین فرایند (بدنۀ [tp_page]) — با صافی‌های موقت جهش. */
function page_in( $post_id, $filters = array() ) {
	foreach ( $filters as $hook => $fn ) { add_filter( $hook, $fn, 99 ); }
	$html = (string) topersia_g_sc_page( array( 'id' => $post_id ) );
	foreach ( $filters as $hook => $fn ) { remove_filter( $hook, $fn, 99 ); }
	return $html;
}

echo 'topersia-render ' . TOPERSIA_RENDER_VERSION . " — روی topersia.local\n";

$country = (int) ( get_posts( array( 'post_type' => 'tp_country', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) )[0] ?? 0 );
$province_any = (int) ( get_posts( array( 'post_type' => 'tp_province', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) )[0] ?? 0 );

if ( ! $country || ! $province_any ) {
	fwrite( STDERR, "کشور یا استانِ منتشرشده در نسخۀ محلی نیست\n" );
	exit( 2 );
}

/* ══ ۲ج٫۴ — قالب کشور ═══════════════════════════════════════════════ */
section( '۲ج٫۴ — قالب کشور  P5-D281 · P5-D282' );

$c_path = wp_make_link_relative( get_permalink( $country ) );
$p_path = wp_make_link_relative( get_permalink( $province_any ) );
$c_http = local_get( $c_path );
$p_http = local_get( $p_path );

t( 'صفحۀ کشور و استان ۲۰۰', 200 === $c_http['code'] && 200 === $p_http['code'], $c_http['code'] . ' · ' . $p_http['code'] );

$c_dom = visible_dom( $c_http['body'] );
$p_dom = visible_dom( $p_http['body'] );

/* هوا: نه کارت، نه پرونده‌های هوا و انیمیشن — جهش: صفحۀ استان که هر دو را دارد */
mut( 'کشور: نه کارت هوا، نه topersia-g-weather*.js (انیمیشن هوا بی آن ساخته نمی‌شود)',
	function ( $h ) { return false === strpos( visible_dom( $h ), 'data-tp-weather' ) && false === strpos( $h, 'topersia-g-weather' ) && false === strpos( $h, 'tp-wx-fx' ); },
	$c_http['body'], $p_http['body'] );

/* همان، از مسیر کد: سطر ماتریس بی `tp-weather` در off ← کارت برمی‌گردد */
$no_off = function ( $rows ) { $rows['tp_country']['off'] = array(); return $rows; };
mut( 'کشور: کارت هوا را ماتریس برمی‌دارد، نه CSS — جهش: off خالی',
	function ( $h ) { return false === strpos( $h, 'data-tp-weather' ) && false === strpos( $h, 'id="tp-weather"' ); },
	page_in( $country ), page_in( $country, array( 'topersia_g_type_matrix' => $no_off ) ) );

/* نظر: نه بخش نظر، نه DOM JetReviews؛ JSON-LD بی Review و AggregateRating */
$ld_types = function ( $h ) {
	preg_match_all( '~<script type="application/ld\+json"[^>]*>(.*?)</script>~su', $h, $m );
	$out = array();
	foreach ( $m[1] as $j ) {
		preg_match_all( '~"@type"\s*:\s*"([A-Za-z]+)"~', $j, $tt );
		$out = array_merge( $out, $tt[1] );
	}
	return array_values( array_unique( $out ) );
};
$reviews_free = function ( $h ) use ( $ld_types ) {
	$dom = visible_dom( $h );
	return false === strpos( $dom, 'id="tp-reviews"' ) && ! preg_match( '~class="[^"]*jet-reviews~', $dom )
		&& ! array_intersect( array( 'Review', 'AggregateRating' ), $ld_types( $h ) ) && in_array( 'Country', $ld_types( $h ), true );
};
mut( 'کشور: نه #tp-reviews، نه DOM JetReviews، نه Review/AggregateRating در JSON-LD — جهش: صفحۀ استان',
	$reviews_free, $c_http['body'], $p_http['body'], implode( ',', $ld_types( $c_http['body'] ) ) );

$jr = get_option( 'jet-reviews-settings' );
t( 'P5-D234: پیکربندی نوع‌به‌نوع JetReviews کشور را نمی‌پذیرد (allowed-post-types · type-settings)',
	isset( $jr['allowed-post-types']['tp_country'] ) && 'false' === $jr['allowed-post-types']['tp_country']
	&& empty( $jr['tp_country-type-settings']['allowed'] )
	&& class_exists( 'ToPersia_JetReviews_Setup' ) && '' === ToPersia_JetReviews_Setup::family_of( 'tp_country' ) );

/* تیتر: فقط نام کوتاه، بی نام نوع — جهش: title=kind */
$name_fa = trim( (string) get_post_meta( $country, 'name_fa', true ) );
$kind    = topersia_g_kind_label( $country );
$h1      = function ( $h ) { return preg_match( '~<h1>(.*?)</h1>~su', $h, $m ) ? trim( wp_strip_all_tags( $m[1] ) ) : ''; };
$c_hero  = (string) topersia_g_sc_hero( array( 'id' => $country ) );
add_filter( 'topersia_g_type_matrix', $kind_title = function ( $rows ) { $rows['tp_country']['title'] = 'kind'; return $rows; }, 99 );
$c_hero_kind = (string) topersia_g_sc_hero( array( 'id' => $country ) );
remove_filter( 'topersia_g_type_matrix', $kind_title, 99 );
mut( 'کشور: تیتر فقط نام کوتاه («' . $name_fa . '») — بی «' . $kind . '» و بی نام رسمی؛ جهش: title=kind',
	function ( $h ) use ( $h1, $name_fa, $kind ) { return '' !== $name_fa && $name_fa === $h1( $h ) && false === strpos( $h1( $h ), $kind ); },
	$c_hero, $c_hero_kind, $h1( $c_hero ) );

$official_fa = trim( (string) get_post_meta( $country, 'official_name_fa', true ) );
t( 'کشور: نام رسمی زبان صفحه (' . $official_fa . ') در ردیف نام‌های زیر تیتر می‌ماند',
	'' !== $official_fa && (bool) preg_match( '~<p class="tp-hero__alt"><span lang="fa">' . preg_quote( esc_html( $official_fa ), '~' ) . '</span>~u', $c_hero ) );

/* استان‌ها: هشت کارت + مودال با همۀ استان‌های منتشرشده (شمارش مستقل از رابطۀ ۷۱) */
$rel71        = topersia_relation( 71 );
$prov_all     = array_map( 'absint', (array) $rel71->get_children( $country, 'ids' ) );
$prov_publish = array_values( array_filter( $prov_all, function ( $id ) {
	return 'publish' === get_post_status( $id ) && 'fa' === apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $id, 'element_type' => 'tp_province' ) );
} ) );

/* یک استانِ پیش‌نویسِ متصل — دروازه باید کنارش بگذارد */
$draft_prov = tmp_post( 'tp_province', 'استان آزمون ۲ج', 'draft' );
tmp_rel( 71, $country, $draft_prov );

$c_page = page_in( $country );
$sec    = section_html( $c_page, 'tp-provinces' );

$modal_real = (string) topersia_g_list_modal( $country, 'provinces' );

mut( 'استان‌ها: اسلایدر ۸ کارت؛ پیوند «همه استان‌ها ←» مودال را باز می‌کند؛ مودال = همۀ استان‌های منتشرشده (' . count( $prov_publish ) . ')',
	function ( $h ) use ( $prov_publish, $modal_real ) {
		$s = section_html( $h, 'tp-provinces' );
		return 8 === substr_count( $s, 'class="tp-card-slide"' ) && false !== strpos( $s, 'href="#tp-list-provinces"' )
			&& count( $prov_publish ) > 8 && count( $prov_publish ) === modal_total( $modal_real, 'tp-list-provinces' );
	},
	$c_page, str_replace( 'href="#tp-list-provinces"', 'href="#"', $c_page ),
	substr_count( $sec, 'class="tp-card-slide"' ) . ' کارت' );
$gate_off   = count( topersia_g_rel_ids( $country, array( array( 'id' => 71, 'dir' => 'children' ) ), false ) );
mut( 'استان‌ها: مودال فقط منتشرشده‌ها — استانِ پیش‌نویسِ متصل نمی‌آید (جهش: دروازه خاموش، ' . $gate_off . ')',
	function ( $n ) use ( $prov_publish ) { return count( $prov_publish ) === $n; },
	modal_total( $modal_real, 'tp-list-provinces' ), $gate_off );

/* کارت آمار: چهار خانۀ کوتاه */
$stats = function ( $h ) {
	preg_match_all( '~<span class="tp-stat__k">(.*?)</span><b class="tp-stat__v">(.*?)</b>~su', $h, $m, PREG_SET_ORDER );
	return array_map( function ( $x ) { return array( trim( wp_strip_all_tags( $x[1] ) ), trim( wp_strip_all_tags( $x[2] ) ) ); }, $m );
};
$c_stats = $stats( (string) topersia_g_sc_stats( array( 'id' => $country ) ) );
$count_cell = topersia_g_stat_cell( $country, 'count:provinces' );
mut( 'کارت آمار کشور: جمعیت · مساحت · شمار استان‌ها · منطقۀ زمانی — شمار از منتشرشده‌ها (' . topersia_g_fa( count( $prov_publish ) ) . ')؛ جهش: دروازه خاموش',
	function ( $cells ) use ( $prov_publish ) {
		return 4 === count( $cells )
			&& array( 'جمعیت', 'مساحت', 'شمار استان‌ها', 'منطقۀ زمانی' ) === array_column( $cells, 0 )
			&& topersia_g_fa( count( $prov_publish ) ) === $cells[2][1];
	},
	$c_stats, array( $c_stats[0], $c_stats[1], array( 'شمار استان‌ها', topersia_g_fa( $gate_off ) ), $c_stats[3] ),
	implode( ' | ', array_map( function ( $c ) { return $c[0] . '=' . $c[1]; }, $c_stats ) ) );

/* همسایه‌ها: یک کاشی برای هر سطر فیلد، بی پیوند */
$raw_nb   = (string) get_post_meta( $country, 'neighboring_countries', true );
$nb_lines = array_values( array_filter( array_map( 'trim', preg_split( '/\R/u', $raw_nb ) ), 'strlen' ) );
$nb_html  = section_html( $c_page, 'tp-neighbors' );
$tiles_of = function ( $h ) { preg_match_all( '~<a[^>]*><b>(.*?)</b></a>~su', $h, $m ); return $m; };
mut( 'همسایه‌ها: ' . count( $nb_lines ) . ' کاشی در ستون کناری، هر کاشی یک کشور (جداکنندۀ داده: خط CRLF)، بی href',
	function ( $h ) use ( $tiles_of, $nb_lines ) {
		$m = $tiles_of( $h );
		return '' !== $h && false !== strpos( $h, 'class="tp-neighbors"' ) && $nb_lines === array_map( 'html_entity_decode', $m[1] )
			&& false === strpos( $h, 'href=' );
	},
	$nb_html, str_replace( '</b></a><a><b>', '، ', $nb_html ),
	"\\r\\n × " . substr_count( $raw_nb, "\r\n" ) );

/* topersia_g_map() ایستا حافظه دارد و صافی وسط فرایند به آن نمی‌رسد؛ جهش همان شکلِ خروجیِ ناحیۀ details پیشین است */
mut( 'همسایه‌ها: فیلد دیگر در «درباره» تکرار نمی‌شود (ناحیۀ neighbors در FIELDMAP) — جهش: <h3> ناحیۀ details پیشین',
	function ( $h ) { return 'neighbors' === topersia_g_map()['neighboring_countries']['zone'] && false === strpos( section_html( $h, 'tp-overview' ), '<h3>کشورهای همسایه</h3>' ); },
	$c_page, preg_replace( '~(<section id="tp-overview"[^>]*>)~', '$1<h3>کشورهای همسایه</h3><p>' . esc_html( $nb_lines[0] ) . '</p>', $c_page ) );

/* نشان‌ها: یک اسلایدر برای هر برنامۀ دارای عضو؛ فهرست برنامه‌ها از رکوردهای D */
$programmes = topersia_g_programmes( true );
$prog_codes = array_column( $programmes, 'code' );
$records    = get_posts( array( 'post_type' => 'tp_designation', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false ) );

t( 'نشان‌ها: ' . count( $records ) . ' رکورد D، ' . count( $prog_codes ) . ' برنامه با عضو: ' . implode( ' · ', $prog_codes ),
	count( $records ) >= count( $prog_codes ) && count( $prog_codes ) > 0 );

$wh_expected = count( get_posts( array( 'post_type' => array( 'tp_monument', 'tp_religious_site', 'tp_protected_area', 'tp_natural_feature', 'tp_ancient_site', 'tp_landscape', 'tp_desert', 'tp_forest_plain' ), 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => false, 'meta_query' => array( array( 'key' => 'world_heritage', 'value' => 'true' ) ) ) ) );
$wh_index = array_search( 'world_heritage', $prog_codes, true );
$wh_ids   = false === $wh_index ? array() : topersia_g_programme_ids( 'world_heritage', 0 === $wh_index );
$wh_sec   = $wh_ids ? section_html( $c_page, $wh_ids['section'] ) : '';
$wh_modal = (string) topersia_g_programme_modals( $country );
t( 'میراث جهانی: ' . min( 8, $wh_expected ) . ' کارت؛ مودال = ' . $wh_expected . ' عضو منتشرشدۀ پرس‌وجوی D1 (شمارش مستقل)',
	$wh_ids && min( 8, $wh_expected ) === substr_count( $wh_sec, 'class="tp-card-slide"' )
	&& ( $wh_expected <= 8 ? -1 === modal_total( $wh_modal, $wh_ids['list'] ) : $wh_expected === modal_total( $wh_modal, $wh_ids['list'] ) ),
	substr_count( $wh_sec, 'class="tp-card-slide"' ) . ' کارت · مودال ' . ( $wh_ids ? modal_total( $wh_modal, $wh_ids['list'] ) : '—' ) );

/* داده‌محور: برنامۀ بی عضو نمی‌آید؛ یک عضو منتشرشده ← اسلایدرش می‌آید؛ پیش‌نویس ← دوباره نه */
$nm_code = 'national_monument';
$before  = in_array( $nm_code, array_column( topersia_g_programmes( true ), 'code' ), true );
$mount   = tmp_post( 'tp_mountain', 'کوه آزمون ۲ج', 'publish', array( 'national_monument' => 'true' ) );
$with    = in_array( $nm_code, array_column( topersia_g_programmes( true ), 'code' ), true );
wp_update_post( array( 'ID' => $mount, 'post_status' => 'draft' ) );
$after   = in_array( $nm_code, array_column( topersia_g_programmes( true ), 'code' ), true );
t( 'نشان‌ها از داده: «اثر طبیعی ملی» بی عضو نمی‌آید → با یک کوهِ منتشرشده می‌آید → با پیش‌نویس‌شدنش نه  P5-D247',
	! $before && $with && ! $after, var_export( array( $before, $with, $after ), true ) );

/* ══ ۲ج٫۲ — اسلایدر حداکثر ۸ کارت ═════════════════════════════════════ */
section( '۲ج٫۲ — اسلایدر: حداکثر ۸ کارت؛ بیشتر ← پنجره با همۀ منتشرشده‌ها  P5-D276' );

/* هر نوع صفحه‌دار با پست منتشرشدۀ محلی: پرعضوترین پستش (بیشترین رابطه) */
$over   = array();
$wrong  = array();
$seen   = array();
$sliders_total = 0;
foreach ( array_keys( topersia_g_kind_labels() ) as $type ) {
	if ( in_array( $type, topersia_g_no_page_types(), true ) ) { continue; }
	$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => 3, 'fields' => 'ids', 'suppress_filters' => false ) );
	/* پرداده‌ترین پست‌های محلی (LOCAL-DATA اصفهان) هم — سه پست نخست هر نوع اغلب بی رابطه‌اند */
	$ids = array_unique( array_merge( $ids, get_posts( array( 'post_type' => $type, 'name' => 'isfahan', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) ) ) );
	foreach ( $ids as $one ) {
		$html = page_in( $one );
		preg_match_all( '~<section id="(tp-[a-z0-9-]+)" class="tp-section">(.*?)</section>~su', $html, $secs, PREG_SET_ORDER );
		foreach ( $secs as $s ) {
			$n = substr_count( $s[2], 'class="tp-card-slide"' );
			if ( ! $n ) { continue; }
			$sliders_total ++;
			$seen[ $type ] = true;
			if ( $n > 8 ) { $over[] = $type . '#' . $one . ' ' . $s[1] . ':' . $n; }
			$key = substr( $s[1], 3 );
			if ( isset( topersia_g_relmap()[ $type ][ $key ] ) ) {
				$total = topersia_g_rel_items( $one, $key, 0 )['total'];
				$modal = modal_total( (string) topersia_g_list_modal( $one, $key ), 'tp-list-' . $key );
				$link  = false !== strpos( $s[2], 'href="#tp-list-' . $key . '"' );
				if ( $total > 8 ? ( $modal !== $total || ! $link ) : ( -1 !== $modal || $link ) ) { $wrong[] = $type . '#' . $one . ' ' . $key . ' total ' . $total . ' modal ' . $modal; }
			}
		}
	}
}
mut( 'هیچ اسلایدری در هیچ نوعی بیش از ۸ کارت ندارد (' . $sliders_total . ' اسلایدر در ' . count( $seen ) . ' نوع)',
	function ( $o ) use ( $sliders_total ) { return $sliders_total > 0 && ! $o; }, $over, array( 'tp_province#1 tp-places:12' ), implode( ' | ', $over ) );
mut( 'هر اسلایدرِ رابطه با بیش از ۸ عضو: پیوند + پنجره با دقیقاً N منتشرشده؛ ۸ یا کمتر: نه پیوند نه پنجره',
	function ( $w ) { return ! $w; }, $wrong, array( 'tp_province attractions total 3 modal 3' ), implode( ' | ', $wrong ) );

/* استثنا: برنامۀ بیش از سقف (۱۰۰) — بی پنجره؛ فقط شمار و پیوند به صفحۀ خود برنامه. سقف با صافی به ۱۰ */
add_filter( 'topersia_g_programme_list_max', $ten = function () { return 10; } );
$c_small  = page_in( $country );
$wh_small = $wh_ids ? section_html( $c_small, $wh_ids['section'] ) : '';
$wh_url   = $wh_index !== false ? topersia_g_programmes()[ $wh_index ]['url'] : '';
$wh_modal_small = (string) topersia_g_programme_modals( $country );
remove_filter( 'topersia_g_programme_list_max', $ten );
mut( 'برنامۀ بیش از سقف: بی پنجره؛ پیوند «همه ' . topersia_g_fa( $wh_expected ) . ' مورد ←» به صفحۀ خودِ برنامه (سقف آزمون ۱۰)',
	function ( $p ) use ( $wh_url, $wh_ids, $wh_expected ) {
		return '' !== $wh_url && false !== strpos( $p[0], '<a class="tp-more" href="' . esc_url( $wh_url ) . '">همه ' . topersia_g_fa( $wh_expected ) . ' مورد ←</a>' )
			&& -1 === modal_total( $p[1], $wh_ids['list'] ) && false === strpos( $p[0], 'data-tp-list-open' );
	},
	array( $wh_small, $wh_modal_small ), array( $wh_sec, $wh_modal ) );

/* ══ ۲ج٫۶ — «نشان‌ها» فقط عنوان رسمی ══════════════════════════════════ */
section( '۲ج٫۶ — «نشان‌ها» فقط عنوان رسمی  P5-D279' );

$honours  = array( 'world_heritage', 'biosphere_reserve', 'national_monument', 'unesco_creative_city', 'tourism_target_village' );
$badge_of = function ( $map ) { return array_keys( array_filter( $map, function ( $f ) { return 'badge' === $f['zone'] && 'flag' === $f['kind']; } ) ); };
$fmap     = topersia_g_map();
$old_map  = $fmap;
$old_map['railway_station']['zone'] = 'badge';
mut( 'FIELDMAP: پرچمِ ناحیۀ badge دقیقاً پنج عنوان رسمی — جهش: ایستگاه راه‌آهن در نشان‌ها',
	function ( $m ) use ( $badge_of, $honours ) { $b = $badge_of( $m ); sort( $b ); $h = $honours; sort( $h ); return $b === $h; },
	$fmap, $old_map, implode( ' · ', $badge_of( $fmap ) ) );
t( 'FIELDMAP: ایستگاه راه‌آهن و منطقۀ آزاد ← travel · آتشفشانی ← card · فرودگاه ← travel; هیچ متاکلید عوض نشد',
	'travel' === $fmap['railway_station']['zone'] && 'travel' === $fmap['free_zone']['zone'] && 'card' === $fmap['volcanic']['zone']
	&& 'travel' === $fmap['airport_name']['zone'] && 'travel' === $fmap['airport_iata']['zone'] );

/* «در هیچ نوعی»: هر نوعی که پرچمی دارد، با همۀ پرچم‌هایش روشن — کارت نشان‌ها فقط عنوان رسمی */
$all_flags  = array_keys( array_filter( $fmap, function ( $f ) { return 'flag' === $f['kind']; } ) );
$hon_labels = array_map( function ( $k ) use ( $fmap ) { return $fmap[ $k ]['label']; }, $honours );
$bad_types  = array();
$checked    = array();
foreach ( array_keys( topersia_g_kind_labels() ) as $type ) {
	if ( 'tp_county' === $type ) { continue; }
	$own = array_values( array_intersect( $all_flags, array_keys( topersia_pack_schema( $type )['fields'] ) ) );
	if ( ! $own ) { continue; }
	$tmp = tmp_post( $type, 'آزمون نشان ۲ج', 'draft', array_fill_keys( $own, 'true' ) );
	preg_match_all( '~<b>(.*?)</b>~u', (string) topersia_g_sc_badges( array( 'id' => $tmp ) ), $bm );
	$extra = array_diff( $bm[1], $hon_labels );
	$checked[] = $type;
	if ( $extra ) { $bad_types[] = $type . ': ' . implode( '،', $extra ); }
}
mut( 'نشان‌ها: در هیچ نوعی (' . count( $checked ) . ' نوع پرچم‌دار، همۀ پرچم‌ها روشن) پرچمی جز پنج عنوان رسمی نمی‌آید',
	function ( $bad ) use ( $checked ) { return count( $checked ) >= 10 && ! $bad; },
	$bad_types, array( 'tp_city: ایستگاه راه‌آهن' ), implode( ' | ', $bad_types ) );

$city_t = tmp_post( 'tp_city', 'شهر آزمون ۲ج', 'draft', array( 'railway_station' => 'true', 'free_zone' => 'true', 'unesco_creative_city' => 'true', 'airport_name' => 'فرودگاه بین‌المللی آزمون', 'airport_iata' => 'TST', 'population' => '1000', 'area_km2' => '10', 'elevation_m' => '1500' ) );
$prac = (string) topersia_g_sc_relgrid( array( 'id' => $city_t, 'rel' => 'practical' ) );
mut( 'اطلاعات سفر: ایستگاه راه‌آهن و منطقۀ آزاد «بله» — وضعیت، نه نشان؛ بی پیوند',
	function ( $h ) { return false !== strpos( $h, 'ایستگاه راه‌آهن: بله' ) && false !== strpos( $h, 'منطقۀ آزاد: بله' ) && false !== strpos( $h, 'id="tp-practical"' ); },
	$prac, str_replace( 'ایستگاه راه‌آهن: بله', '', $prac ) );

$mount_v = tmp_post( 'tp_mountain', 'کوه آزمون ۲ج', 'draft', array( 'volcanic' => 'true', 'national_monument' => 'true', 'national_monument_year' => '1398' ) );
$facts_v = (string) topersia_g_sc_facts( array( 'id' => $mount_v ) );
$badge_v = (string) topersia_g_sc_badges( array( 'id' => $mount_v ) );
mut( 'آتشفشانی: کارت حقایق با مقدار «بله»، نه نشان؛ اثر طبیعی ملی نشان با سالش',
	function ( $p ) { return (bool) preg_match( '~<span class="tp-tile__k">آتشفشانی</span><b>بله</b>~u', $p[0] ) && false === strpos( $p[1], 'آتشفشانی' ) && false !== strpos( $p[1], 'اثر طبیعی ملی' ) && false !== strpos( $p[1], '۱۳۹۸' ); },
	array( $facts_v, $badge_v ), array( $facts_v, $badge_v . '<b>آتشفشانی</b>' ) );

$mount_off = tmp_post( 'tp_mountain', 'کوه آزمون خاموش ۲ج', 'draft', array( 'volcanic' => 'false', 'elevation_m' => '2000' ) );
t( 'پرچمِ خاموش در کارت حقایق نمی‌آید (نه «آتشفشانی: خیر»، نه برچسب خالی)',
	false === strpos( (string) topersia_g_sc_facts( array( 'id' => $mount_off ) ), 'آتشفشانی' ) );

/* ══ ۲ج٫۷ — سربرگ شهر ═══════════════════════════════════════════════ */
section( '۲ج٫۷ — سربرگ شهر  P5-D282' );

$st_city = $stats( (string) topersia_g_sc_stats( array( 'id' => $city_t ) ) );
mut( 'شهر: نام و کد فرودگاه در کارت آمار نیستند؛ خانۀ آزاد «ارتفاع از سطح دریا» (تا کیفیت هوای فاز ۳)',
	function ( $cells ) { $k = array_column( $cells, 0 ); return ! array_intersect( array( 'نام فرودگاه', 'کد فرودگاه' ), $k ) && in_array( 'ارتفاع از سطح دریا', $k, true ); },
	$st_city, array_merge( array( array( 'نام فرودگاه', 'فرودگاه بین‌المللی آزمون' ) ), $st_city ),
	implode( ' | ', array_column( $st_city, 0 ) ) );
t( 'شهر: نام کامل و کد IATA در «اطلاعات سفر»',
	false !== strpos( $prac, 'نام فرودگاه: فرودگاه بین‌المللی آزمون' ) && false !== strpos( $prac, 'کد فرودگاه: <span class="tp-lat" dir="ltr">TST</span>' ) );

/* شهر واقعی بی سال بنیان: سه خانه، شبکه سه‌ستونه — بی جای خالی (P5-D275) */
$isf_city = (int) ( get_posts( array( 'post_type' => 'tp_city', 'name' => 'isfahan', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) )[0] ?? 0 );
$isf_html = (string) topersia_g_sc_stats( array( 'id' => $isf_city ) );
$isf_n    = count( $stats( $isf_html ) );
t( 'شهر اصفهان: ' . $isf_n . ' خانه و data-tp-n برابر آن — شبکه به شمار خانه‌ها',
	$isf_n >= 1 && false !== strpos( $isf_html, 'data-tp-n="' . $isf_n . '"' ), implode( ' | ', array_column( $stats( $isf_html ), 0 ) ) );

/* ══ پایان ══════════════════════════════════════════════════════════ */
echo "\n" . str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['pass']}   افتاده: {$GLOBALS['fail']}\n";
exit( $GLOBALS['fail'] ? 1 : 0 );
