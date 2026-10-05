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
 *   ۲ج٫۵  گالری تجمیعی و صف هسته  P5-D274 · P5-D300 · P5-D290 — کارهای صفی که
 *         آزمون می‌سازد در پایان اجرا و ردیفشان پاک می‌شود
 *   ۲ج٫۱۰ نامک از نام انگلیسی — سه مسیر ساخت، ادعای دائمی، فهرست ناهمخوان
 *
 * هر ادعا جهش دارد. کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص
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

/* as_floor: کف صف هسته پیش از آزمون — هر کار topersia بالاتر از آن ساختۀ همین آزمون است (۲ج٫۵) */
require_once __DIR__ . '/queue-sweep.php';
$cleanup = array( 'posts' => array(), 'rel' => array(), 'as_floor' => tp_queue_floor() );
$GLOBALS['cleanup'] = &$cleanup;

register_shutdown_function( function () use ( &$cleanup ) {
	foreach ( $cleanup['rel'] as $r ) {
		$rel = topersia_relation( $r[0] );
		if ( $rel ) { $rel->delete_rows( $r[1], $r[2] ); }
	}
	foreach ( $cleanup['posts'] as $p ) {
		wp_delete_post( $p, true );
	}
	/* پاک‌شدن پست موقت والدهای تجمیعی را در صف گذاشت — اجرا، و ردیف‌های صفِ ساختۀ آزمون پاک */
	tp_queue_sweep( $cleanup['as_floor'] );
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
	$res = wp_remote_get( TP_BASE_URL . $path, array( 'timeout' => 300, 'redirection' => 0 ) );
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
mut( 'کارت آمار کشور: جمعیت · مساحت · شمار استان‌ها · زبان رسمی — شمار از منتشرشده‌ها (' . topersia_g_fa( count( $prov_publish ) ) . ')؛ جهش: دروازه خاموش',
	function ( $cells ) use ( $prov_publish ) {
		return 4 === count( $cells )
			&& array( 'جمعیت', 'مساحت', 'شمار استان‌ها', 'زبان رسمی' ) === array_column( $cells, 0 )
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

/* ══ ۲ج٫۵ — گالری تجمیعی ═════════════════════════════════════════════ */
section( '۲ج٫۵ — گالری تجمیعی: صف هسته، پایین به بالا، منبع، فقط منتشرشده  P5-D274 · P5-D300 · P5-D290' );

$g5_hook  = 'topersia_gallery_agg_rebuild';
$g5_ready = $cleanup['as_floor'] >= 0 && function_exists( 'topersia_g_gallery_agg_enabled' );
t( 'صف واحد هسته آماده — Action Scheduler ' . ( class_exists( 'ActionScheduler_Versions' ) ? ActionScheduler_Versions::instance()->latest_version() : '؟' ) . '، گروه «topersia»؛ لایۀ مشتق در local روشن  P5-D290 · P5-D300',
	$g5_ready && defined( 'TOPERSIA_QUEUE_GROUP' ) && 'topersia' === TOPERSIA_QUEUE_GROUP && topersia_g_gallery_agg_enabled() );

/* پیوست‌های تصویری آزاد: نه در گالری کشور، نه اصفهان — و تصویرِ درست (کاشی می‌سازد) */
$g5_isf  = (int) ( get_posts( array( 'post_type' => 'tp_province', 'name' => 'isfahan', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => false ) )[0] ?? 0 );
$g5_used = array_merge( topersia_g_gallery_own_ids( $country ), $g5_isf ? topersia_g_gallery_own_ids( $g5_isf ) : array() );
$g5_free = array();
foreach ( get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post_status' => 'inherit', 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'suppress_filters' => false ) ) as $g5_one ) {
	if ( ! in_array( (int) $g5_one, $g5_used, true ) && topersia_g_gallery_shots( array( (int) $g5_one ) ) ) {
		$g5_free[] = (int) $g5_one;
	}
}

if ( ! $g5_ready || ! $g5_isf || count( $g5_free ) < 8 ) :
	t( '۲ج٫۵ آزموده نشد — صف، اصفهان یا هشت پیوست تصویری آزاد نیست', false, count( $g5_free ) . ' پیوست آزاد' );
else :

list( $a0, $a1, $a2, $a3, $a4, $a5, $a6, $a7 ) = $g5_free;

$g5_drain = function () use ( $g5_hook ) {
	$n = 0;
	for ( $i = 0; $i < 8; $i++ ) {
		$k = topersia_queue_run_now( $g5_hook );
		if ( ! $k ) { break; }
		$n += $k;
	}
	return $n;
};
$g5_rows = function ( $id ) {
	return array_map( function ( $r ) { return $r['id'] . '@' . $r['src']; }, topersia_g_gallery_agg_stored( $id ) );
};
/* کارهای بازسازیِ ساخته‌شده پس از $floor، هر وضعیتی — اجرای هم‌زمان WP-Cron محلی ادعا را نمی‌شکند */
$g5_jobs = function ( $floor ) use ( $g5_hook ) {
	global $wpdb;
	$out = array();
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT args, scheduled_date_gmt, status FROM {$wpdb->prefix}actionscheduler_actions WHERE hook = %s AND action_id > %d AND status IN ( 'pending', 'in-progress', 'complete' )", $g5_hook, $floor ) ) as $r ) {
		$args  = json_decode( $r->args, true );
		$out[] = array( 'post' => (int) ( $args['post'] ?? 0 ), 'at' => strtotime( $r->scheduled_date_gmt . ' UTC' ) );
	}
	return $out;
};

/* کارهای مانده از بخش‌های پیشین (پست موقت ۲ج٫۴ و ۲ج٫۶) — صف از صفر */
$g5_drain();
$g5_country_before = topersia_g_gallery_agg_stored( $country );
$g5_isf_before     = topersia_g_gallery_agg_stored( $g5_isf );
$g5_floor          = (int) $wpdb->get_var( "SELECT COALESCE( MAX( action_id ), 0 ) FROM {$wpdb->prefix}actionscheduler_actions" );
$g5_post_mark      = count( $cleanup['posts'] );
$g5_rel_mark       = count( $cleanup['rel'] );

/* چیدمان: کشور ← استان P ← شهرستان K ← شهر C ← بنا M (رابطه‌های ۷۱ · ۷۲ · ۷۳ · ۸۴) */
$g5_P = tmp_post( 'tp_province', 'استان آزمون ۲ج٫۵' );
$g5_K = tmp_post( 'tp_county', 'شهرستان آزمون ۲ج٫۵' );
$g5_C = tmp_post( 'tp_city', 'شهر آزمون ۲ج٫۵' );
$g5_M = tmp_post( 'tp_monument', 'بنای آزمون ۲ج٫۵' );
tmp_rel( 71, $country, $g5_P );
tmp_rel( 72, $g5_P, $g5_K );
tmp_rel( 73, $g5_K, $g5_C );
tmp_rel( 84, $g5_C, $g5_M );
update_post_meta( $g5_M, 'featured_image_gallery', $a1 . ',' . $a2 . ',' . $a3 );
update_post_meta( $g5_C, 'featured_image_gallery', (string) $a0 );

/* صف: یک کار برای هر پله، پایین به بالا — بی ذخیرۀ پست، فقط از رابطه و گالری */
$g5_order = function ( $jobs ) use ( $g5_C, $g5_P, $country ) {
	$at = array();
	foreach ( array( $g5_C, $g5_P, $country ) as $id ) {
		$mine = array_values( array_filter( $jobs, function ( $j ) use ( $id ) { return $j['post'] === $id; } ) );
		if ( 1 !== count( $mine ) ) { return false; }
		$at[] = $mine[0]['at'];
	}
	return $at[0] < $at[1] && $at[1] < $at[2];
};
$g5_j = $g5_jobs( $g5_floor );
mut( 'ردیف رابطه و گالریِ تازه (بی ذخیرۀ پست) یک کار برای هر پله می‌سازد — شهر، استان، کشور؛ تکراری‌ها ادغام؛ زمان‌ها پایین به بالا  P5-D290',
	$g5_order, $g5_j, array_merge( $g5_j, array( array( 'post' => $g5_P, 'at' => 0 ) ) ),
	count( $g5_j ) . ' کار' );

/* P5-D300 — هیچ محاسبه‌ای در بارگذاری: پیش از صف گالری استان خالی است؛ جهش = ساختن در همان بارگذاری */
mut( 'پیش از اجرای صف گالری استان آزمون خالی است — صفحه فقط متای مشتق را می‌خواند  P5-D300',
	function ( $shots ) { return array() === $shots; },
	topersia_g_gallery_shots_for( $g5_P ), topersia_g_gallery_shots( wp_list_pluck( topersia_g_gallery_agg_build( $g5_P ), 'id' ) ) );

$g5_drain();
$g5_c_rows = $g5_rows( $g5_C );
$g5_p_rows = $g5_rows( $g5_P );
$g5_k_rows = $g5_rows( $country );
mut( 'پس از صف: شهر ← دو عکس از سه عکس بنا (حداکثر ۲ از هر فرزند)؛ استان ← عکس شهر، سپس نخستین عکس بنا؛ کشور هر دو — منبع صاحب عکس است، نه فرزند میانی',
	function ( $x ) use ( $a0, $a1, $a2, $g5_C, $g5_M ) {
		return array( "$a1@$g5_M", "$a2@$g5_M" ) === $x[0]
			&& array( "$a0@$g5_C", "$a1@$g5_M" ) === $x[1]
			&& in_array( "$a0@$g5_C", $x[2], true ) && in_array( "$a1@$g5_M", $x[2], true );
	},
	array( $g5_c_rows, $g5_p_rows, $g5_k_rows ),
	array( $g5_c_rows, array( "$a0@$g5_C", "$a1@$g5_C" ), $g5_k_rows ),
	'شهر ' . implode( ' ', $g5_c_rows ) . ' · استان ' . implode( ' ', $g5_p_rows ) );

/* نمای تمام‌صفحه و کپشن: نام و نشانی منبع (n · u)؛ عکس خود بی منبع */
$g5_modal = (string) topersia_g_modals( $g5_P );
$g5_json  = preg_match( "~data-tp-shots='([^']*)'~", $g5_modal, $g5_m ) ? (array) json_decode( html_entity_decode( $g5_m[1], ENT_QUOTES ), true ) : array();
$g5_by    = array_column( $g5_json, null, 'id' );
$g5_src_ok = function ( $by ) use ( $a0, $a1, $g5_C, $g5_M ) {
	return isset( $by[ $a0 ], $by[ $a1 ] )
		&& topersia_g_name( $g5_C ) === $by[ $a0 ]['n'] && get_permalink( $g5_C ) === $by[ $a0 ]['u']
		&& topersia_g_name( $g5_M ) === $by[ $a1 ]['n'] && get_permalink( $g5_M ) === $by[ $a1 ]['u'];
};
mut( 'گالری کامل استان: هر عکس تجمیعی نام و نشانی صفحۀ منبع دارد (n · u) — شهر برای عکس شهر، بنا برای عکس بنا',
	$g5_src_ok, $g5_by, array( $a0 => array( 'n' => '', 'u' => '' ) + ( $g5_by[ $a0 ] ?? array() ) ) + $g5_by );
$g5_gal  = (string) topersia_g_sc_gallery( array( 'id' => $g5_P ) );
$g5_lab  = (string) ( topersia_g_gallery_shots( array( $a0 ) )[0]['label'] ?? '' );
t( 'کاشی گالری: سطر دوم نام منبع («· ' . topersia_g_name( $g5_C ) . '») — کاشی بی برچسب سطری ندارد',
	'' === $g5_lab ? false === strpos( $g5_gal, 'tp-gallery-main__meta' ) : false !== strpos( $g5_gal, 'tp-gallery-main__meta">· ' . esc_html( topersia_g_name( $g5_C ) ) ),
	'برچسب پیوست: ' . ( '' === $g5_lab ? 'خالی' : $g5_lab ) );

/* JSON-LD: image فقط hero_image خود موجودیت؛ گالری تجمیعی هرگز در اسکیما */
update_post_meta( $g5_P, 'hero_image', $a7 );
$g5_graph = (string) wp_json_encode( topersia_schema_build( $g5_P ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
$g5_agg_urls = array_map( function ( $id ) { return (string) wp_get_attachment_image_src( $id, 'full' )[0]; }, array_column( topersia_g_gallery_agg_stored( $g5_P ), 'id' ) );
mut( 'JSON-LD استان: image همان hero_image خودش؛ هیچ عکس تجمیعی (' . count( $g5_agg_urls ) . ') در اسکیما نیست',
	function ( $g ) use ( $a7, $g5_agg_urls ) {
		if ( ! $g5_agg_urls || false === strpos( $g, (string) wp_get_attachment_image_src( $a7, 'full' )[0] ) ) { return false; }
		foreach ( $g5_agg_urls as $u ) { if ( false !== strpos( $g, $u ) ) { return false; } }
		return true;
	},
	$g5_graph, $g5_graph . ( $g5_agg_urls ? $g5_agg_urls[0] : '' ) );

/* آبشار: کار والد پیش از فرزند اجرا شد (ادغام ترتیب را تضمین نمی‌کند) ← بازسازی فرزند والد را دوباره در صف می‌گذارد */
update_post_meta( $g5_M, 'featured_image_gallery', $a4 . ',' . $a1 . ',' . $a2 . ',' . $a3 );
$g5_pj = topersia_queue_pending( $g5_hook, array( 'post' => $g5_P ) );
$g5_cj = topersia_queue_pending( $g5_hook, array( 'post' => $g5_C ) );
if ( $g5_pj ) { ActionScheduler::runner()->process_action( $g5_pj[0], 'topersia-test' ); }
$g5_stale = $g5_rows( $g5_P );
if ( $g5_cj ) { ActionScheduler::runner()->process_action( $g5_cj[0], 'topersia-test' ); }
$g5_again = (bool) topersia_queue_pending( $g5_hook, array( 'post' => $g5_P ) );
$g5_drain();
$g5_fresh = $g5_rows( $g5_P );
mut( 'ترتیب بد (والد پیش از فرزند): استان اول دادۀ کهنه می‌سازد، بازسازیِ شهر استان را دوباره در صف می‌گذارد و گالری استان تازه می‌شود',
	function ( $x ) use ( $a0, $a1, $a4, $g5_C, $g5_M ) {
		return in_array( "$a1@$g5_M", $x[0], true ) && $x[1] && array( "$a0@$g5_C", "$a4@$g5_M" ) === $x[2];
	},
	array( $g5_stale, $g5_again, $g5_fresh ), array( $g5_stale, false, $g5_stale ),
	'کهنه ' . implode( ' ', $g5_stale ) . ' · تازه ' . implode( ' ', $g5_fresh ) );

/* /en/: منبع به ترجمۀ منتشرشده نگاشت می‌شود؛ منبعِ بی ترجمۀ انگلیسی کنار می‌رود؛ پیوست بی نسخۀ زبان همان اصل */
$g5_tr = function ( $src, $lang, $title ) {
	$type = get_post_type( $src );
	$trid = apply_filters( 'wpml_element_trid', null, $src, 'post_' . $type );
	$id   = wp_insert_post( array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_name' => sanitize_title( $title ), 'post_content' => '' ), true );
	do_action( 'wpml_set_element_language_details', array( 'element_id' => $id, 'element_type' => 'post_' . $type, 'trid' => $trid, 'language_code' => $lang, 'source_language_code' => 'fa' ) );
	$GLOBALS['cleanup']['posts'][] = (int) $id;
	return (int) $id;
};
$g5_P_en  = $g5_tr( $g5_P, 'en', 'Test Province 2c5' );
$g5_C_en  = $g5_tr( $g5_C, 'en', 'Test City 2c5' );
$g5_lang  = apply_filters( 'wpml_current_language', null );
do_action( 'wpml_switch_language', 'en' );
$g5_en    = array_column( topersia_g_gallery_shots_for( $g5_P_en ), null, 'id' );
$g5_c_url = (string) get_permalink( $g5_C_en );
/* پیوستِ رسانۀ ترجمه‌شدۀ WPML به نسخۀ همین زبان می‌رود؛ بی نسخه، همان اصل */
$g5_a0_en = (int) apply_filters( 'wpml_object_id', $a0, 'attachment', true );
$g5_a4_en = (int) apply_filters( 'wpml_object_id', $a4, 'attachment', true );
do_action( 'wpml_switch_language', $g5_lang );
mut( '/en/: عکس شهر با نام و نشانی ترجمۀ انگلیسی شهر و پیوستِ نسخۀ انگلیسی (' . $a0 . ' ← ' . $g5_a0_en . ')؛ عکس بنای بی ترجمۀ انگلیسی کنار می‌رود  P5-D247',
	function ( $b ) use ( $g5_a0_en, $g5_a4_en, $g5_c_url ) {
		return isset( $b[ $g5_a0_en ] ) && 'Test City 2c5' === $b[ $g5_a0_en ]['src_name'] && $g5_c_url === $b[ $g5_a0_en ]['src_url']
			&& false !== strpos( $g5_c_url, '/en/' ) && ! isset( $b[ $g5_a4_en ] );
	},
	$g5_en, $g5_en + array( $g5_a4_en => array( 'src_name' => topersia_g_name( $g5_M ), 'src_url' => '' ) ),
	( $g5_en[ $g5_a0_en ]['src_url'] ?? '—' ) );

/* فقط منتشرشده: بنا پیش‌نویس ← دروازۀ نمایش بی‌درنگ؛ پس از صف از ذخیره هم */
wp_update_post( array( 'ID' => $g5_M, 'post_status' => 'draft' ) );
$g5_c_mid    = array_column( topersia_g_gallery_shots_for( $g5_C ), 'id' );
$g5_c_stored = $g5_rows( $g5_C );
$g5_drain();
mut( 'بنا پیش‌نویس شد: حتی پیش از صف عکسش در گالری شهر نیست؛ پس از صف از ذخیرۀ شهر و استان هم رفت  P5-D247',
	function ( $x ) use ( $a1, $a4, $g5_M ) {
		return ! array_intersect( array( $a1, $a4 ), $x[0] ) && in_array( "$a4@$g5_M", $x[1], true ) && array() === $x[2] && ! preg_grep( '~@' . $g5_M . '$~', $x[3] );
	},
	array( $g5_c_mid, $g5_c_stored, $g5_rows( $g5_C ), $g5_rows( $g5_P ) ),
	array( array_merge( $g5_c_mid, array( $a4 ) ), $g5_c_stored, $g5_rows( $g5_C ), $g5_rows( $g5_P ) ) );

/* سقف ۲۴: اصفهانِ واقعی و یک شهرِ تازه زیر شهرستانی تازه */
$g5_K2 = tmp_post( 'tp_county', 'شهرستان آزمون ۲ج٫۵ ب' );
$g5_C2 = tmp_post( 'tp_city', 'شهر آزمون ۲ج٫۵ ب', 'publish', array( 'featured_image_gallery' => $a5 . ',' . $a6 ) );
tmp_rel( 72, $g5_isf, $g5_K2 );
tmp_rel( 73, $g5_K2, $g5_C2 );
$g5_drain();
$g5_isf_own  = count( topersia_g_gallery_own_ids( $g5_isf ) );
$g5_isf_ids  = array_column( topersia_g_gallery_shots_for( $g5_isf ), 'id' );
$g5_room     = max( 0, TOPERSIA_GALLERY_MAX - $g5_isf_own );
$g5_isf_want = array_slice( array( $a5, $a6 ), 0, min( 2, $g5_room ) );
mut( 'سقف ۲۴: اصفهان با ' . topersia_g_fa( $g5_isf_own ) . ' عکس خود ' . topersia_g_fa( count( $g5_isf_want ) ) . ' عکس از شهر تازه می‌گیرد — کل ' . topersia_g_fa( min( TOPERSIA_GALLERY_MAX, $g5_isf_own + 2 ) ),
	function ( $ids ) use ( $g5_isf_want, $g5_isf_own, $a5, $a6 ) {
		return count( $ids ) === min( TOPERSIA_GALLERY_MAX, $g5_isf_own + 2 ) && array_values( array_intersect( $ids, array( $a5, $a6 ) ) ) === $g5_isf_want;
	},
	$g5_isf_ids, array_merge( $g5_isf_ids, array( $a6 ) ) );

/* هیچ گالری‌ای تکراری، بیش از ۲۴ یا منبعِ منتشرنشده در ذخیره ندارد */
$g5_sweep = array();
foreach ( array( $country, $g5_isf, $g5_P, $g5_C, $g5_C2 ) as $g5_one ) {
	$att = array_column( topersia_g_gallery_shots_for( $g5_one ), 'id' );
	if ( count( $att ) !== count( array_unique( $att ) ) ) { $g5_sweep[] = '#' . $g5_one . ' تکراری'; }
	if ( count( $att ) > TOPERSIA_GALLERY_MAX ) { $g5_sweep[] = '#' . $g5_one . ' ' . count( $att ); }
	foreach ( topersia_g_gallery_agg_stored( $g5_one ) as $r ) {
		if ( 'publish' !== get_post_status( $r['src'] ) ) { $g5_sweep[] = '#' . $g5_one . ' منبع #' . $r['src']; }
	}
}
mut( 'کشور، اصفهان و چیدمان آزمون: هیچ عکس تکراری، هیچ گالری بیش از ۲۴، هیچ منبع منتشرنشده در ذخیره',
	function ( $s ) { return ! $s; }, $g5_sweep, array( '#' . $country . ' تکراری' ), implode( ' | ', $g5_sweep ) );

/* شهر پیش‌نویس ← عکسش بی‌درنگ از گالری استان و پس از صف از ذخیرۀ استان و کشور */
wp_update_post( array( 'ID' => $g5_C, 'post_status' => 'draft' ) );
$g5_p_mid = array_column( topersia_g_gallery_shots_for( $g5_P ), 'id' );
$g5_drain();
mut( 'شهر پیش‌نویس شد: عکسش بی‌درنگ از گالری استان کنار رفت و پس از صف از ذخیرۀ استان و کشور هم  P5-D247',
	function ( $x ) use ( $a0, $g5_C ) { return ! in_array( $a0, $x[0], true ) && array() === $x[1] && ! in_array( "$a0@$g5_C", $x[2], true ); },
	array( $g5_p_mid, $g5_rows( $g5_P ), $g5_rows( $country ) ),
	array( $g5_p_mid, array( "$a0@$g5_C" ), $g5_rows( $country ) ) );

/* دروازۀ محلی: خاموش ← فقط عکس‌های خود و هیچ کاری در صف */
add_filter( 'topersia_gallery_agg_enabled', '__return_false' );
$g5_off   = count( topersia_g_gallery_shots_for( $country ) );
$g5_off_q = topersia_g_gallery_agg_schedule( $country );
remove_filter( 'topersia_gallery_agg_enabled', '__return_false' );
$g5_on    = count( topersia_g_gallery_shots_for( $country ) );
$g5_own   = count( topersia_g_gallery_own_ids( $country ) );
mut( 'دروازۀ محلی (P5-D300): خاموش ← گالری کشور فقط ' . topersia_g_fa( $g5_own ) . ' عکس خودش و هیچ کاری در صف؛ روشن ← ' . topersia_g_fa( $g5_on ),
	function ( $x ) use ( $g5_own ) { return $x[0] === $g5_own && 0 === $x[1] && $x[2] > $g5_own; },
	array( $g5_off, $g5_off_q, $g5_on ), array( $g5_on, $g5_off_q, $g5_on ) );

/* پیشخوان: کارت فقط برای manage_options؛ admin-post با توان و nonce — رد ۴۰۳، بی کار در صف */
$g5_adm = get_user_by( 'login', 'tp_test_admin' );
$g5_sub = get_user_by( 'login', 'tp_test_430' );
if ( $g5_adm && $g5_sub ) {
	$g5_card = function ( $uid ) { wp_set_current_user( $uid ); ob_start(); topersia_g_gallery_agg_admin_card(); return (string) ob_get_clean(); };
	$g5_die  = function () { return function ( $message, $title = '', $args = array() ) { unset( $message, $title ); throw new RuntimeException( 'die:' . ( is_array( $args ) ? (int) ( $args['response'] ?? 0 ) : (int) $args ) ); }; };
	$g5_post = function ( $uid, $nonce ) use ( $g5_die ) {
		wp_set_current_user( $uid );
		$_REQUEST['_wpnonce'] = $nonce;
		add_filter( 'wp_die_handler', $g5_die );
		try { do_action( 'admin_post_topersia_gallery_agg_rebuild_all' ); $r = 'بی‌رد'; } catch ( RuntimeException $e ) { $r = $e->getMessage(); }
		remove_filter( 'wp_die_handler', $g5_die );
		unset( $_REQUEST['_wpnonce'] );
		return $r;
	};
	$g5_c_adm = $g5_card( $g5_adm->ID );
	$g5_c_sub = $g5_card( $g5_sub->ID );
	$g5_r_sub = $g5_post( $g5_sub->ID, wp_create_nonce( 'topersia_gallery_agg_rebuild_all' ) );
	$g5_r_bad = $g5_post( $g5_adm->ID, 'bad' );
	wp_set_current_user( 0 );
	mut( 'کارت «بازسازی همه» فقط برای manage_options؛ admin-post: مشترک با nonce درست ← ۴۰۳، مدیر با nonce نادرست ← ۴۰۳؛ هیچ کاری در صف',
		function ( $x ) {
			return false !== strpos( $x[0], 'name="action" value="topersia_gallery_agg_rebuild_all"' ) && false !== strpos( $x[0], 'name="_wpnonce"' )
				&& '' === $x[1] && 'die:403' === $x[2] && 'die:403' === $x[3] && ! $x[4];
		},
		array( $g5_c_adm, $g5_c_sub, $g5_r_sub, $g5_r_bad, topersia_queue_pending( 'topersia_gallery_agg_rebuild_all' ) ),
		array( $g5_c_adm, $g5_c_adm, $g5_r_sub, $g5_r_bad, array() ),
		$g5_r_sub . ' · ' . $g5_r_bad );
} else {
	t( 'پیشخوان «بازسازی همه»: حساب آزمون مدیر یا مشترک نیست — آزموده نشد', false );
}

/* پاک‌سازی همین‌جا: چیدمان برداشته، صف اجرا ← ذخیرۀ کشور و اصفهان عیناً پیش از آزمون */
foreach ( array_reverse( array_slice( $cleanup['rel'], $g5_rel_mark ) ) as $r ) {
	topersia_relation( $r[0] )->delete_rows( $r[1], $r[2] );
}
foreach ( array_slice( $cleanup['posts'], $g5_post_mark ) as $p ) {
	wp_delete_post( $p, true );
}
$g5_drain();
$g5_after = array( topersia_g_gallery_agg_stored( $country ), topersia_g_gallery_agg_stored( $g5_isf ) );
mut( 'پاک‌سازی: پس از برداشتن چیدمان و اجرای صف، ذخیرۀ تجمیعی کشور و اصفهان عیناً همان پیش از آزمون',
	function ( $x ) use ( $g5_country_before, $g5_isf_before ) { return $x[0] === $g5_country_before && $x[1] === $g5_isf_before; },
	$g5_after, array( array_merge( $g5_after[0], array( array( 'id' => $a0, 'src' => $g5_C ) ) ), $g5_after[1] ) );

/* زبان: کار صفی که در بافت انگلیسی اجرا شود همان گالری زبان اصلی را می‌سازد؛ جهش = ساختنِ بی سنجاق زبان */
do_action( 'wpml_switch_language', 'en' );
$g5_en_rows = topersia_g_gallery_agg_rebuild( $country, false );
$g5_raw_en  = topersia_g_gallery_agg_build( $country );
do_action( 'wpml_switch_language', $g5_lang );
mut( 'بازسازی همیشه به زبان اصلی: در بافت انگلیسی همان ردیف‌های فارسی کشور ذخیره می‌شود (فرزندان و منبع از زبان اصلی)',
	function ( $rows ) use ( $g5_country_before, $country ) { return $rows === $g5_country_before && $rows === topersia_g_gallery_agg_stored( $country ); },
	$g5_en_rows, $g5_raw_en,
	'بی سنجاق: ' . wp_json_encode( $g5_raw_en ) );

/* «بازسازی همه» از صف: یک کار برای هر پستِ منتشرشدۀ زبان اصلی، پله‌به‌پله؛ اجرا ← کشور همان پیشین */
$g5_floor2 = (int) $wpdb->get_var( "SELECT COALESCE( MAX( action_id ), 0 ) FROM {$wpdb->prefix}actionscheduler_actions" );
$g5_ids    = topersia_g_gallery_agg_all_ids();
$g5_n      = topersia_g_gallery_agg_queue_all();
$g5_fan    = $g5_jobs( $g5_floor2 );
$g5_steps  = function ( $jobs ) use ( $g5_ids ) {
	$at = array();
	foreach ( $jobs as $j ) { $at[ $j['post'] ] = $j['at']; }
	$max = array();
	$min = array();
	foreach ( $g5_ids as $type => $ids ) {
		$t = array_values( array_intersect_key( $at, array_flip( $ids ) ) );
		if ( count( $t ) !== count( $ids ) ) { return false; }
		$max[ $type ] = $t ? max( $t ) : 0;
		$min[ $type ] = $t ? min( $t ) : PHP_INT_MAX;
	}
	return $max['tp_city'] < $min['tp_province'] && $max['tp_province'] < $min['tp_country'];
};
$g5_total = array_sum( array_map( 'count', $g5_ids ) );
mut( '«بازسازی همه» از صف: ' . topersia_g_fa( $g5_total ) . ' کار — یکی برای هر شهر، استان و کشورِ منتشرشده؛ شهرها پیش از استان‌ها، استان‌ها پیش از کشور',
	function ( $x ) use ( $g5_steps, $g5_total ) { return $x[0] === $g5_total && count( $x[1] ) === $g5_total && $g5_steps( $x[1] ); },
	array( $g5_n, $g5_fan ), array( $g5_n, array_map( function ( $j ) { $j['at'] = 0; return $j; }, $g5_fan ) ) );
$g5_drain();
t( '«بازسازی همه» اجرا شد: ذخیرۀ کشور و اصفهان همان پیشین',
	topersia_g_gallery_agg_stored( $country ) === $g5_country_before && topersia_g_gallery_agg_stored( $g5_isf ) === $g5_isf_before );

endif;

/* ══ ۲ج٫۱۰ — نامک از نام انگلیسی ════════════════════════════════════ */
section( '۲ج٫۱۰ — نامک از نام انگلیسی: ادعای دائمی، سه مسیر ساخت، فهرست ناهمخوان' );

global $wpdb;

/* ادعای دائمی — هر پست نوع‌های ToPersia؛ پست‌های موقت همین آزمون (پیش‌نویسِ بی نام انگلیسی) بیرون */
$s10_own  = array_flip( array_map( 'intval', $cleanup['posts'] ) );
$s10_rows = $wpdb->get_results( "SELECT ID, post_type, post_status, post_name FROM {$wpdb->posts} WHERE post_type LIKE 'tp\\_%' AND post_status NOT IN ( 'auto-draft', 'inherit', 'trash' ) ORDER BY post_type, ID" );
$s10_bad  = array();
$s10_list = array();
$s10_num  = array();
$s10_n    = 0;
foreach ( $s10_rows as $r ) {
	if ( isset( $s10_own[ (int) $r->ID ] ) ) {
		continue;
	}
	$s10_n++;
	$slug = (string) $r->post_name;
	if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
		$s10_bad[] = '#' . $r->ID . ' ' . $r->post_type . ' «' . $slug . '»';
		continue;
	}
	$want = '';
	foreach ( array( 'official_name_en', 'name_en', 'page_title' ) as $k ) {
		$v = trim( (string) get_post_meta( $r->ID, $k, true ) );
		if ( '' !== $v ) {
			$want = sanitize_title( $v );
			break;
		}
	}
	if ( ctype_digit( str_replace( '-', '', $slug ) ) ) {
		$s10_num[] = '#' . $r->ID . ' ' . $r->post_type . ' [' . $r->post_status . '] «' . $slug . '» — نام انگلیسی «' . get_post_meta( $r->ID, 'name_en', true ) . '»';
	} elseif ( '' !== $want && $want !== $slug ) {
		$s10_list[] = '#' . $r->ID . ' ' . $r->post_type . ' [' . $r->post_status . '] ' . $slug . ' ← ' . $want;
	}
}
mut( 'ادعای دائمی: هر پست نوع‌های ToPersia (' . topersia_g_fa( $s10_n ) . '، بی زباله و پیش‌نویس خودکار) نامک ^[a-z0-9-]+$ دارد',
	function ( $bad ) { return ! $bad; }, $s10_bad, array( '#1 tp_city «%d8%a7%d8%b5%d9%81%d9%87%d8%a7%d9%86»' ), implode( ' | ', array_slice( $s10_bad, 0, 8 ) ) );

/* ۱ — ساخت دستی: جت‌انجین متای متاباکس را در save_post (اولویت ۱۰) می‌نویسد، پیش از قلاب نامک (۹۹۹) */
$s10_jet = function ( $id ) {
	if ( 'tp_city' === get_post_type( $id ) && '' === (string) get_post_meta( $id, 'name_en', true ) ) {
		update_post_meta( $id, 'name_en', 'Manual Slug Twoc Ten' );
	}
};
add_action( 'save_post', $s10_jet, 10 );
$s10_m = tmp_post( 'tp_city', 'شهر نامک دستی ۲ج٫۱۰', 'draft' );
remove_action( 'save_post', $s10_jet, 10 );

/* ۲ — درون‌ریز ۲ب بی نامک صریح؛ ۳ — ترجمۀ ۲ج٫۹ و انتشار هر دو */
$s10_run = 'imp-test-2c10-' . strtolower( wp_generate_password( 6, false, false ) );
$s10_r   = topersia_pack_apply( topersia_pack_parse( wp_json_encode( array(
	'meta'  => array( 'schema' => TOPERSIA_PACK_SCHEMA, 'post_type' => 'tp_city', 'language' => 'fa' ),
	'posts' => array( array( 'title' => '', 'fields' => array( 'name_fa' => 'شهر نامک درون‌ریزی ۲ج٫۱۰', 'name_en' => 'Import Slug Twoc Ten' ), 'relations' => array() ) ),
) ) ), 0, $s10_run );
$s10_i = (int) $s10_r['target'];
$cleanup['posts'][] = $s10_i;

$s10_tpl = topersia_pack_tr_export( array( $s10_i ), 'en' );
$s10_tpl['posts'][0]['title']['value'] = 'Import Slug Twoc Ten';
$s10_tr = topersia_pack_tr_apply( topersia_pack_tr_parse( wp_json_encode( $s10_tpl ) ), 0, $s10_run );
$s10_t  = (int) $s10_tr['target'];
$cleanup['posts'][] = $s10_t;
$s10_t_draft = get_post_field( 'post_name', $s10_t );
wp_update_post( array( 'ID' => $s10_i, 'post_status' => 'publish' ) );
wp_update_post( array( 'ID' => $s10_t, 'post_status' => 'publish' ) );

$s10_got = array(
	'manual'   => get_post_field( 'post_name', $s10_m ),
	'import'   => get_post_field( 'post_name', $s10_i ),
	'uid'      => (string) get_post_meta( $s10_i, 'entity_uid', true ),
	'tr_draft' => $s10_t_draft,
	'tr_pub'   => get_post_field( 'post_name', $s10_t ),
	'fa_pub'   => get_post_field( 'post_name', $s10_i ),
);
mut( 'سه مسیر: ساخت دستی (متا در save_post) ← manual-slug-twoc-ten؛ درون‌ریز ۲ب بی نامک ← نسخۀ نامکیِ name_en و entity_uid با همان؛ ترجمۀ ۲ج٫۹ همان نامک فارسی — پیش‌نویس و پس از انتشار هر دو، بی پسوند -2',
	function ( $g ) {
		return 'manual-slug-twoc-ten' === $g['manual'] && sanitize_title( 'Import Slug Twoc Ten' ) === $g['import'] && false !== strpos( $g['uid'], '/city/' . $g['import'] )
			&& $g['import'] === $g['tr_draft'] && $g['import'] === $g['tr_pub'] && $g['import'] === $g['fa_pub'];
	},
	$s10_got, array_merge( $s10_got, array( 'tr_pub' => $s10_got['tr_pub'] . '-2' ) ), wp_json_encode( $s10_got ) );

/* آزمایشی: نامک صریحِ ناهمخوان و پست بی نام انگلیسی — آگاهی، نه مانع؛ هیچ نوشتن */
$s10_plan = function ( $posts ) {
	$d = topersia_pack_parse( wp_json_encode( array( 'meta' => array( 'schema' => TOPERSIA_PACK_SCHEMA, 'post_type' => 'tp_city', 'language' => 'fa' ), 'posts' => $posts ) ) );
	return array( topersia_pack_plan_post( $d, 0 ), topersia_pack_plan_post( $d, 1, topersia_pack_pending( $d, 1 ) ) );
};
$s10_p  = $s10_plan( array(
	array( 'slug' => 'wrong-slug-twoc-ten', 'fields' => array( 'name_fa' => 'شهر نامک صریح ۲ج٫۱۰', 'name_en' => 'Right Name Twoc Ten' ) ),
	array( 'slug' => '', 'fields' => array( 'name_fa' => 'شهر بی نام انگلیسی ۲ج٫۱۰' ) ),
) );
$s10_ok = $s10_plan( array(
	array( 'slug' => 'right-name-twoc-ten', 'fields' => array( 'name_fa' => 'شهر نامک صریح ۲ج٫۱۰', 'name_en' => 'Right Name Twoc Ten' ) ),
	array( 'slug' => '', 'fields' => array( 'name_fa' => 'شهر با نام انگلیسی ۲ج٫۱۰', 'name_en' => 'With English Twoc Ten' ) ),
) );
mut( 'آزمایشی: نامک صریحِ ناهمخوان با نام انگلیسی و پستِ بی نام انگلیسی هر کدام یادداشت می‌گیرند — ساخت سر جایش (دو موجودیت هم‌نام نامک جدا می‌خواهند)',
	function ( $x ) {
		return 'create' === $x[0]['action'] && preg_grep( '/با نام انگلیسی نمی‌خواند/u', $x[0]['notes'] )
			&& 'create' === $x[1]['action'] && preg_grep( '/بی نام انگلیسی/u', $x[1]['notes'] );
	},
	$s10_p, $s10_ok );

/* فهرست برای گزارش — منتشرشده فقط فهرست؛ تغییر با ۳۰۱ Rank Math و تصمیم کاربر (فاز ۱۰) */
echo '  · نامک لاتینِ ناهمخوان با نام انگلیسی: ' . count( $s10_list ) . "\n";
foreach ( $s10_list as $line ) {
	echo '      ' . $line . "\n";
}
echo '  · نامک تمام‌عددی: ' . count( $s10_num ) . "\n";
foreach ( $s10_num as $line ) {
	echo '      ' . $line . "\n";
}

$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . topersia_log_table() . " WHERE channel = 'import' AND context LIKE %s", '%' . $wpdb->esc_like( '"run":"' . $s10_run . '"' ) . '%' ) );

/* ══ پایان ══════════════════════════════════════════════════════════ */
echo "\n" . str_repeat( '─', 78 ) . "\n  گذشته: {$GLOBALS['pass']}   افتاده: {$GLOBALS['fail']}\n";
exit( $GLOBALS['fail'] ? 1 : 0 );
