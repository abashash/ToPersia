<?php
/**
 * همهٔ نوع‌ها — بند ۱ نسخهٔ ۴٫۲٫۱.
 *
 * «هر نوع عمومی، حتی با متا و رابطهٔ خالی، بی‌خطا رندر می‌شود.»
 *
 * آزمون‌های دیگر چند پستِ پرداده را رندر می‌کنند؛ خطای ۵۰۰ نسخهٔ ۴٫۲٫۰
 * درست از همین شکاف رد شد — استان سالم بود و بقیه نه. این‌جا برای هر نوع
 * عمومی `tp_` که در اسکین هست یک پست ساخته می‌شود و `[tp_page]` و تک‌تک
 * شورت‌کدها رندر می‌شوند؛ هر خطا، هشدار، اخطار و استثنا ثبت می‌شود.
 *
 * سه حالت:
 *   خالی   بی‌متا و بی‌رابطه — خواستهٔ بند ۱
 *   پُر    هر فیلد اسکین با مقداری هم‌شکلِ ذخیرهٔ JetEngine، و هر رابطهٔ
 *          اسکین با یک پست از نوعِ سرِ دیگر
 *   ناجور  همان پُر، ولی هر مقدار ساده رشته‌ای غیرعددی و هر آرایه تودرتو —
 *          دادهٔ زنده همیشه تمیز نیست و PHP 8 روی ریاضیِ رشتهٔ غیرعددی
 *          خطای مرگبار می‌دهد، نه هشدار
 *
 * فرقش با stubs.php: وردپرسِ این‌جا «زنده‌تر» است. هر رابطهٔ اسکین شیء
 * رابطه برمی‌گرداند نه false، هر نوع پست برچسب دارد، و jet_engine()
 * واژه‌نامه‌های اسکین را می‌دهد. مسیرهایی که آزمون‌های دیگر هرگز اجرا
 * نمی‌کردند همین‌ها بودند.
 *
 * اجرا:
 *   php tests/alltypes.php           گزارش خوانا · کد خروج ۰ یا ۱
 *   php tests/alltypes.php --json    برای assert.php
 *   php tests/alltypes.php --all-php همین آزمون زیر هر PHP موجود در wamp
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '0' );

$TP_JSON = in_array( '--json', $argv, true );

/* ── زیر هر نسخهٔ PHP ──────────────────────────────────────────────── */

if ( in_array( '--all-php', $argv, true ) ) {

	require_once __DIR__ . '/env.php';
	/* همۀ PHPهای کنار PHP اصلی env.json — P5-D285 */
	$bins = glob( dirname( dirname( TP_PHP ) ) . '/php*/php.exe' );
	$code = 0;

	foreach ( (array) $bins as $bin ) {

		$cmd = escapeshellarg( $bin ) . ' ' . escapeshellarg( __FILE__ ) . ' --json';
		$out = shell_exec( $cmd );
		$res = json_decode( (string) $out, true );

		if ( ! is_array( $res ) ) {
			echo '✗ ', basename( dirname( $bin ) ), " — خروجی خوانا نبود:\n", substr( (string) $out, 0, 2000 ), "\n";
			$code = 1;
			continue;
		}

		$bad = count( $res['problems'] );
		echo ( $bad ? '✗ ' : '✓ ' ), str_pad( $res['php'], 8 ), '  ', $res['renders'], ' رندر · ',
			$res['types'], ' نوع · ', $bad, " مشکل\n";

		foreach ( array_slice( $res['problems'], 0, 12 ) as $p ) {
			echo '    ', $p['mode'], ' · ', $p['type'], ' · ', $p['tag'], ' — ', $p['kind'], ': ', $p['msg'], ' @ ', $p['at'], "\n";
		}

		if ( $bad ) {
			$code = 1;
		}
	}

	exit( $code );
}

/* ── ثبت هر خطا ────────────────────────────────────────────────────── */

$GLOBALS['tp_at_ctx']      = array( 'mode' => '-', 'type' => '-', 'tag' => 'bootstrap' );
$GLOBALS['tp_at_problems'] = array();

function tp_at_note( $kind, $msg, $at ) {
	$c = $GLOBALS['tp_at_ctx'];
	$GLOBALS['tp_at_problems'][] = array(
		'mode' => $c['mode'],
		'type' => $c['type'],
		'tag'  => $c['tag'],
		'kind' => $kind,
		'msg'  => $msg,
		'at'   => $at,
	);
}

set_error_handler( function ( $no, $str, $file, $line ) {
	$names = array(
		E_WARNING => 'Warning', E_NOTICE => 'Notice', E_DEPRECATED => 'Deprecated',
		E_USER_WARNING => 'Warning', E_USER_NOTICE => 'Notice', E_USER_DEPRECATED => 'Deprecated',
		E_RECOVERABLE_ERROR => 'Error',
	);
	tp_at_note( isset( $names[ $no ] ) ? $names[ $no ] : 'E' . $no, $str, basename( $file ) . ':' . $line );
	return true;
} );

register_shutdown_function( function () {
	$e = error_get_last();
	if ( ! $e || ! in_array( $e['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) {
		return;
	}
	tp_at_note( 'Fatal', $e['message'], basename( $e['file'] ) . ':' . $e['line'] );
	$c = $GLOBALS['tp_at_ctx'];
	fwrite( STDOUT, json_encode( array(
		'php'      => PHP_VERSION,
		'types'    => 0,
		'renders'  => 0,
		'problems' => $GLOBALS['tp_at_problems'],
		'fatal'    => $c,
	), JSON_UNESCAPED_UNICODE ) . "\n" );
} );

/* ── وردپرسِ آزمون، و آنچه وردپرسِ زنده دارد و آن ندارد ───────────── */

require __DIR__ . '/stubs.php';

$SKIN = json_decode( file_get_contents( dirname( __DIR__ ) . '/reference/Jetengine_Skin.json' ), true );

if ( ! is_array( $SKIN ) || empty( $SKIN['post_types'] ) ) {
	fwrite( STDERR, "اسکین خوانده نشد\n" );
	exit( 2 );
}

/* واژه‌نامه‌ها، به همان شکلی که JetEngine می‌دهد. */
$GLOSS = array();
foreach ( $SKIN['glossaries'] as $g ) {
	$fields = is_string( $g['fields'] ) ? json_decode( $g['fields'], true ) : $g['fields'];
	$GLOSS[ (string) $g['id'] ] = is_array( $fields ) ? $fields : array();
}

if ( ! function_exists( 'jet_engine' ) ) {
	/** جت‌انجینِ زنده: فقط همان بخشی که لایهٔ نمایش لمس می‌کند. */
	class TP_AT_Glossary_Data {
		public function get_items() {
			$out = array();
			foreach ( $GLOBALS['tp_at_gloss'] as $id => $fields ) {
				$out[] = array( 'id' => $id, 'fields' => $fields );
			}
			return $out;
		}
	}
	function jet_engine() {
		static $engine = null;
		if ( null === $engine ) {
			$engine                   = new stdClass();
			$engine->glossaries       = new stdClass();
			$engine->glossaries->data = new TP_AT_Glossary_Data();
		}
		return $engine;
	}
}
$GLOBALS['tp_at_gloss'] = $GLOSS;

if ( ! function_exists( 'get_the_author_meta' ) ) {
	function get_the_author_meta( $field = '', $user_id = 0 ) { return 'کاربر ' . absint( $user_id ); }
}
if ( ! function_exists( 'get_post_comments_feed_link' ) ) {
	function get_post_comments_feed_link( $post_id = 0 ) { return get_permalink( $post_id ) . 'feed/'; }
}

require TP_PLUGIN_DIR . 'includes/topersia-g-map.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-weather.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-zones.php';
require TP_PLUGIN_DIR . 'includes/topersia-g-relreg.php';

/* ── اسکین: نوع‌ها، فیلدها، رابطه‌ها ────────────────────────────────── */

$TYPES = array();
foreach ( $SKIN['post_types'] as $pt ) {
	$args   = @unserialize( $pt['args'] );
	$labels = @unserialize( $pt['labels'] );
	if ( empty( $args['public'] ) || 0 !== strpos( $pt['slug'], 'tp_' ) ) {
		continue;
	}
	$TYPES[ $pt['slug'] ] = isset( $labels['singular_name'] ) ? (string) $labels['singular_name'] : $pt['slug'];
}
ksort( $TYPES );

/* برچسب مفردِ هر نوع — stubs.php به این نگاه می‌کند، مثل وردپرسِ زنده. */
$GLOBALS['tp_pt_labels'] = $TYPES;

$FIELDS = array();
foreach ( $SKIN['meta_boxes'] as $mb ) {
	$args = is_string( $mb['args'] ) ? json_decode( $mb['args'], true ) : $mb['args'];
	$mf   = is_string( $mb['meta_fields'] ) ? json_decode( $mb['meta_fields'], true ) : $mb['meta_fields'];
	$for  = isset( $args['allowed_post_type'] ) ? (array) $args['allowed_post_type'] : array();
	foreach ( $for as $type ) {
		foreach ( (array) $mf as $f ) {
			if ( is_array( $f ) && ! empty( $f['name'] ) && 'field' === ( isset( $f['object_type'] ) ? $f['object_type'] : 'field' ) ) {
				$FIELDS[ $type ][ $f['name'] ] = $f;
			}
		}
	}
}
foreach ( $SKIN['post_types'] as $pt ) {
	$mf = @unserialize( $pt['meta_fields'] );
	foreach ( (array) $mf as $f ) {
		if ( is_array( $f ) && ! empty( $f['name'] ) ) {
			$FIELDS[ $pt['slug'] ][ $f['name'] ] = $f;
		}
	}
}

$RELS = array();
foreach ( $SKIN['relations'] as $row ) {
	$a = @unserialize( $row['args'] );
	if ( ! is_array( $a ) || empty( $a['parent_object'] ) || empty( $a['child_object'] ) ) {
		continue;
	}
	$RELS[ (string) $row['id'] ] = array(
		'parent' => preg_replace( '/^posts::/', '', $a['parent_object'] ),
		'child'  => preg_replace( '/^posts::/', '', $a['child_object'] ),
	);
}

/* ── مقدارِ هم‌شکلِ ذخیرهٔ JetEngine ─────────────────────────────────── */

/**
 * مقدارِ یک فیلد، همان شکلی که get_post_meta در سایت برمی‌گرداند.
 *
 * @param array  $f     تعریف فیلد از اسکین.
 * @param string $title نام پست — برای متن.
 * @param bool   $odd   حالت ناجور.
 */
function tp_at_value( $f, $title, $odd ) {

	global $GLOSS;

	$type = isset( $f['type'] ) ? $f['type'] : 'text';
	$name = $f['name'];

	$choices = array();
	if ( ! empty( $f['glossary_id'] ) && isset( $GLOSS[ (string) $f['glossary_id'] ] ) ) {
		foreach ( $GLOSS[ (string) $f['glossary_id'] ] as $row ) {
			$choices[] = (string) $row['value'];
		}
	} elseif ( ! empty( $f['options'] ) && is_array( $f['options'] ) ) {
		foreach ( $f['options'] as $row ) {
			if ( isset( $row['key'] ) ) {
				$choices[] = (string) $row['key'];
			}
		}
	}
	if ( ! $choices ) {
		$choices = array( 'first', 'second' );
	}

	/*
	 * ناجور: ریپیتر همان کلیدهای واقعی‌اش را دارد ولی هر برگ ناجور است —
	 * وگرنه کد اصلاً به برگ نمی‌رسید و «بی‌خطا» چیزی نمی‌گفت.
	 *   odd  هر برگ رشتهٔ غیرعددی
	 *   arr  هر برگ آرایه — شکلی که با عوض‌شدن قالبِ مقدارِ یک فیلد پیش می‌آید
	 */
	if ( $odd ) {
		if ( 'repeater' === $type ) {
			$row = array();
			foreach ( (array) ( isset( $f['repeater-fields'] ) ? $f['repeater-fields'] : array() ) as $sub ) {
				if ( is_array( $sub ) && ! empty( $sub['name'] ) ) {
					$row[ $sub['name'] ] = tp_at_value( $sub, $title, $odd );
				}
			}
			return array( 'item-0' => $row, 'item-1' => 'رشته' );
		}
		return 'arr' === $odd ? array( 'x' => array( 'y' ), 0 => 'z' ) : 'نامعلوم — x';
	}

	switch ( $type ) {

		case 'number':
			return '2150';

		case 'switcher':
			return 'true';

		case 'date':
			return ! empty( $f['is_timestamp'] ) ? (string) mktime( 0, 0, 0, 3, 21, 2024 ) : '2024-03-21';

		case 'select':
			return ! empty( $f['is_multiple'] ) ? array_slice( $choices, 0, 2 ) : $choices[0];

		case 'checkbox':
			$out = array();
			foreach ( $choices as $i => $c ) {
				$out[ $c ] = $i < 2 ? 'true' : 'false';
			}
			return ! empty( $f['is_array'] ) ? array_slice( $choices, 0, 2 ) : $out;

		case 'media':
			$fmt = isset( $f['value_format'] ) ? $f['value_format'] : 'id';
			if ( 'both' === $fmt ) {
				return array( 'id' => '701', 'url' => 'https://topersia.com/wp-content/uploads/701.mp3' );
			}
			return 'url' === $fmt ? 'https://topersia.com/wp-content/uploads/701.jpg' : '701';

		case 'gallery':
			$fmt = isset( $f['value_format'] ) ? $f['value_format'] : 'id';
			if ( 'both' === $fmt ) {
				return array( array( 'id' => 801, 'url' => 'https://topersia.com/801.jpg' ), array( 'id' => 802, 'url' => 'https://topersia.com/802.jpg' ) );
			}
			return 'url' === $fmt ? 'https://topersia.com/801.jpg,https://topersia.com/802.jpg' : '801,802,803';

		case 'repeater':
			$rows = array();
			for ( $i = 0; $i < 2; $i++ ) {
				$row = array();
				foreach ( (array) ( isset( $f['repeater-fields'] ) ? $f['repeater-fields'] : array() ) as $sub ) {
					if ( is_array( $sub ) && ! empty( $sub['name'] ) ) {
						$row[ $sub['name'] ] = tp_at_value( $sub, $title . ' ' . ( $i + 1 ), false );
					}
				}
				$rows[ 'item-' . $i ] = $row;
			}
			return $rows;

		case 'wysiwyg':
			return '<p>متن نمونه دربارهٔ ' . $title . '.</p>';
	}

	/* text · textarea — چند نام شکل خاص دارند. */
	if ( 'geo_coordinates' === $name ) {
		return '32.6546,51.6680';
	}
	if ( preg_match( '/(^|_)(url|link|website|source)(_|$)/', $name ) ) {
		return 'https://example.test/' . $name;
	}
	if ( preg_match( '/year/', $name ) ) {
		return '1979';
	}

	return 'متن نمونه — ' . $title;
}

/* ── ساخت پست‌ها ──────────────────────────────────────────────────── */

/**
 * جهانِ یک حالت را از نو می‌سازد.
 *
 * @return array شناسهٔ پستِ اصلیِ هر نوع.
 */
function tp_at_world( $mode ) {

	global $TYPES, $FIELDS, $RELS;

	$GLOBALS['tp_posts'] = array();
	$GLOBALS['tp_meta']  = array();
	$GLOBALS['tp_rel']   = array();

	/* هر رابطهٔ اسکین در سایت شیء دارد، حتی بی‌ردیف. */
	foreach ( $RELS as $id => $r ) {
		$GLOBALS['tp_rel'][ $id ] = array( 'children' => array(), 'parents' => array() );
	}

	$next  = 1000;
	$mains = array();

	$make = function ( $type, $title, $status = '', $password = '' ) use ( &$next, $mode, $FIELDS ) {
		$id = $next++;
		$GLOBALS['tp_posts'][ $id ] = array(
			'type'  => $type,
			'title' => $title,
			'url'   => 'https://topersia.com/' . str_replace( '_', '-', $type ) . '-' . $id . '/',
		);
		if ( '' !== $status ) {
			$GLOBALS['tp_posts'][ $id ]['status'] = $status;
		}
		if ( '' !== $password ) {
			$GLOBALS['tp_posts'][ $id ]['password'] = $password;
		}
		if ( 'empty' !== $mode && isset( $FIELDS[ $type ] ) ) {
			foreach ( $FIELDS[ $type ] as $name => $f ) {
				$value = tp_at_value( $f, $title, in_array( $mode, array( 'rich', 'obj', 'many' ), true ) ? false : $mode );
				/*
				 * obj: همان دادهٔ پر، ولی هر آرایهٔ کلیددار شیء است — شکلِ
				 * متایی که با json_decode بی‌`true` ساخته و ذخیره شده. PHP
				 * رشته‌کردنِ شیء را هشدار نمی‌کند، Error می‌اندازد.
				 */
				$GLOBALS['tp_meta'][ $id ][ $name ] = 'obj' === $mode ? json_decode( json_encode( $value ) ) : $value;
			}
		}
		return $id;
	};

	foreach ( $TYPES as $type => $label ) {
		$mains[ $type ] = $make( $type, 'نمونهٔ ' . $label );
	}

	if ( 'empty' === $mode ) {
		return $mains;
	}

	/*
	 * هر رابطه: یک پست در سرِ دیگر، از هر دو سو. JetEngine شناسه را رشته می‌دهد.
	 *
	 * و چهار پستِ منتشرنشده در همان رابطه — پیش‌نویس، خصوصی، در انتظار
	 * بازبینی، رمزدار — با نشان TPHIDDEN در نام و در هر متنِ متایشان. هیچ‌کدام
	 * نباید در هیچ خروجی بیاید: نه کارت، نه نام، نه عضو پنجره.  P5-D247
	 */
	$hidden = array( array( 'draft', '' ), array( 'private', '' ), array( 'pending', '' ), array( 'publish', 'رمز' ) );

	/* ۲ه٫۴ · P5-D313 — «many»: ده عضوِ منتشرشده برای هر رابطه، تا هر بخش کارتی از سقفش (هشت اسلایدر · شش کاشی) بگذرد */
	$per = 'many' === $mode ? 10 : 1;

	foreach ( $mains as $type => $pid ) {
		foreach ( $RELS as $rid => $r ) {
			if ( $r['parent'] === $type ) {
				for ( $k = 0; $k < $per; $k++ ) {
				$other = isset( $TYPES[ $r['child'] ] ) ? $make( $r['child'], 'فرزند ' . $rid . ( $k ? ' · ' . ( $k + 1 ) : '' ) ) : 0;
				if ( $other ) {
					$GLOBALS['tp_rel'][ $rid ]['children'][ $pid ][]  = (string) $other;
					$GLOBALS['tp_rel'][ $rid ]['parents'][ $other ][] = (string) $pid;
				}
				}
				if ( $other ) {
					foreach ( $hidden as $h ) {
						$ghost = $make( $r['child'], 'TPHIDDEN ' . $h[0] . ' ' . $rid, $h[0], $h[1] );
						$GLOBALS['tp_rel'][ $rid ]['children'][ $pid ][]  = (string) $ghost;
						$GLOBALS['tp_rel'][ $rid ]['parents'][ $ghost ][] = (string) $pid;
					}
				}
			}
			if ( $r['child'] === $type ) {
				for ( $k = 0; $k < $per; $k++ ) {
				$other = isset( $TYPES[ $r['parent'] ] ) ? $make( $r['parent'], 'والد ' . $rid . ( $k ? ' · ' . ( $k + 1 ) : '' ) ) : 0;
				if ( $other ) {
					$GLOBALS['tp_rel'][ $rid ]['children'][ $other ][] = (string) $pid;
					$GLOBALS['tp_rel'][ $rid ]['parents'][ $pid ][]    = (string) $other;
				}
				}
				if ( $other ) {
					foreach ( $hidden as $h ) {
						$ghost = $make( $r['parent'], 'TPHIDDEN ' . $h[0] . ' ' . $rid, $h[0], $h[1] );
						$GLOBALS['tp_rel'][ $rid ]['children'][ $ghost ][] = (string) $pid;
						$GLOBALS['tp_rel'][ $rid ]['parents'][ $pid ][]    = (string) $ghost;
					}
				}
			}
		}
	}

	return $mains;
}

/* ── رندر ──────────────────────────────────────────────────────────── */

$renders = 0;
$sizes   = array();
$relmap  = topersia_g_relmap();

/*
 * ۲ج٫۱ · P5-D275 — هر نوع ناحیه‌های استان را با همان ترتیب می‌گیرد.
 *
 * ترتیب ستون‌ها از مرجع؛ هم‌جایگاه‌ها: استان‌های کشور، محله‌های شهر و
 * برنامه‌های نشان جای `#places` و پس از آن، همسایه‌های متنی جای «هم‌مرزها»؛
 * رابطه‌ای که مرجع نامش را نبرده (گذر مرزی، زیرشاخه، مرکزیت) ته همان ستون.
 * ناحیۀ ناشناس، ترتیب وارونه، یا بخشی بی هیچ محتوا = ایراد.
 */
$TP_ORDER = array(
	'main' => array( 'tp-ai', 'tp-overview', 'tp-gallery', 'tp-attractions', 'tp-places', 'tp-provinces', 'tp-districts', 'tp-programmes', 'tp-prog-', 'tp-culture', 'tp-news', 'tp-faq', 'tp-reviews' ),
	'rail' => array( 'tp-actions', 'tp-map', 'tp-season', 'tp-weather', 'tp-audio', 'tp-geo', 'tp-badges', 'tp-nature', 'tp-ancient', 'tp-villages', 'tp-practical', 'tp-neighbors', 'tp-routes', 'tp-sources', 'tp-border', 'tp-subranges', 'tp-capital-of' ),
);

function tp_at_order_bad( $html ) {

	$bad = array();
	$col = array(
		'main' => preg_match( '~<div class="tp-main-col">(.*?)</div><aside class="tp-rail"~su', (string) $html, $m ) ? $m[1] : '',
		'rail' => preg_match( '~<aside class="tp-rail"[^>]*>(.*)</aside>~su', (string) $html, $m ) ? $m[1] : '',
	);

	foreach ( $col as $name => $part ) {

		preg_match_all( '~<section id="(tp-[a-z0-9-]+)"[^>]*>(.*?)</section>~su', $part, $secs, PREG_SET_ORDER );

		$last = -1;

		foreach ( $secs as $s ) {

			$id  = 0 === strpos( $s[1], 'tp-prog-' ) ? 'tp-prog-' : $s[1];
			$pos = array_search( $id, $GLOBALS['TP_ORDER'][ $name ], true );

			if ( false === $pos ) {
				$bad[] = $name . ': ناحیۀ ناشناس ' . $s[1];
				continue;
			}

			if ( $pos < $last ) {
				$bad[] = $name . ': ' . $s[1] . ' پیش از جایگاهش';
			}

			$last = max( $last, $pos );

			if ( '' === trim( wp_strip_all_tags( $s[2] ) ) && ! preg_match( '~<(img|svg|iframe|video|audio|canvas)\b~', $s[2] ) ) {
				$bad[] = $name . ': ' . $s[1] . ' خالی';
			}
		}
	}

	return $bad;
}

/**
 * ۲ه٫۴ · P5-D313 — «همه …» برای هر بخش کارتی که عضو بیشتری از نمایش دارد.
 *
 * اسلایدرها هشت کارت، کاشی‌های ستون کناری شش (`topersia_g_list_cap`). برای هر ناحیۀ
 * اسلایدر/کاشیِ صفحه: اعضای منتشرشده > سقف ← پیوندِ `data-tp-list-open` به `#tp-list-<کلید>` و
 * پنجرۀ همان شناسه با `data-tp-total` برابر شمار اعضا؛ ≤ سقف ← نه پیوند نه پنجره. شمارندهٔ
 * بخش‌های سنجیده‌شده در `$GLOBALS['tp_at_more']` تا ادعا تهی نماند.
 */
function tp_at_more_bad( $pid, $html ) {

	$bad  = array();
	$rail = array( 'villages', 'nature', 'ancient', 'routes', 'subranges', 'border' );

	foreach ( topersia_g_layout( $pid ) as $zones ) {
		foreach ( $zones as $z ) {

			$fn  = isset( $z['fn'] ) ? $z['fn'] : '';
			$key = isset( $z['atts']['rel'] ) ? (string) $z['atts']['rel'] : '';

			if ( '' === $key || ! ( 'topersia_g_sc_slider' === $fn || ( 'topersia_g_sc_relgrid' === $fn && in_array( $key, $rail, true ) ) ) ) {
				continue;
			}

			$res = topersia_g_rel_items( $pid, $key, 0 );
			$tot = (int) $res['total'];
			$cap = topersia_g_list_cap( $key );

			if ( ! $tot ) {
				continue;
			}

			$link  = false !== strpos( $html, 'href="#tp-list-' . $key . '"' ) && false !== strpos( $html, 'data-tp-list-open' );
			$modal = (bool) preg_match( '~id="tp-list-' . preg_quote( $key, '~' ) . '"[^>]*data-tp-total="(\d+)"~', $html, $m );

			$GLOBALS['tp_at_more']['sections'] = ( isset( $GLOBALS['tp_at_more']['sections'] ) ? $GLOBALS['tp_at_more']['sections'] : 0 ) + 1;

			if ( $tot > $cap ) {
				$GLOBALS['tp_at_more']['over'] = ( isset( $GLOBALS['tp_at_more']['over'] ) ? $GLOBALS['tp_at_more']['over'] : 0 ) + 1;
				if ( ! $link ) { $bad[] = $key . ': ' . $tot . ' عضو > سقف ' . $cap . ' ولی پیوند «همه …» نیست'; }
				if ( ! $modal || (int) $m[1] !== $tot ) { $bad[] = $key . ': پنجرۀ فهرست نیست یا شمارش با ' . $tot . ' برابر نیست'; }
			} elseif ( $link || $modal ) {
				$bad[] = $key . ': ' . $tot . ' عضو ≤ سقف ' . $cap . ' ولی پیوند یا پنجره آمده';
			}
		}
	}

	return $bad;
}

foreach ( array( 'empty', 'rich', 'odd', 'arr', 'obj', 'many' ) as $mode ) {

	$mains = tp_at_world( $mode );

	foreach ( $mains as $type => $pid ) {

		$GLOBALS['tp_current'] = $pid;

		$calls = array();
		foreach ( array_keys( $GLOBALS['tp_shortcode'] ) as $tag ) {
			if ( 'tp_slider' === $tag || 'tp_relgrid' === $tag ) {
				foreach ( array_keys( isset( $relmap[ $type ] ) ? $relmap[ $type ] : array() ) as $key ) {
					$calls[] = array( $tag, array( 'id' => $pid, 'rel' => $key ), $tag . ':' . $key );
				}
				continue;
			}
			$calls[] = array( $tag, array( 'id' => $pid ), $tag );
		}

		foreach ( $calls as $call ) {

			$GLOBALS['tp_at_ctx'] = array( 'mode' => $mode, 'type' => $type, 'tag' => $call[2] );

			/*
			 * [tp_page] هر ناحیه را در topersia_g_safe می‌پیچد، پس خطای یک
			 * ناحیه دیگر به این‌جا نمی‌رسد و فقط در ثبت می‌نشیند. «بی‌خطا»
			 * یعنی ثبت هم خالی بماند — وگرنه نگهبان خطا را از آزمون هم
			 * پنهان می‌کرد.
			 */
			$logged = count( $GLOBALS['tp_log'] );

			try {
				/*
				 * وردپرس برای شورت‌کدِ بی‌ویژگی رشتهٔ خالی می‌دهد، نه آرایه —
				 * `shortcode_parse_atts('')`. قالب المنتور `[tp_page]` را
				 * همین‌طور صدا می‌زند، پس یک بار هم همین‌طور.
				 */
				if ( 'tp_page' === $call[0] ) {
					$bare = call_user_func( $GLOBALS['tp_shortcode']['tp_page'], '', '', 'tp_page' );
					if ( 0 !== strpos( (string) $bare, '<div class="tp-page">' ) ) {
						tp_at_note( 'Shape', 'tp_page با ویژگیِ خالیِ وردپرس صفحه نساخت', '-' );
					}
				}

				$html = tp_do( $call[0], $call[1] );
				$renders++;

				foreach ( array_slice( $GLOBALS['tp_log'], $logged ) as $entry ) {
					if ( 0 === strpos( $entry, '[error/render]' ) ) {
						tp_at_note( 'Caught', substr( $entry, 15 ), 'topersia_g_safe' );
					}
				}
				if ( ! is_string( $html ) ) {
					tp_at_note( 'Type', 'خروجی رشته نیست: ' . gettype( $html ), '-' );
				}
				/* پستِ منتشرنشده در خروجی عمومی — P5-D247 */
				if ( is_string( $html ) && false !== strpos( $html, 'TPHIDDEN' ) ) {
					preg_match( '/TPHIDDEN [a-z]+ \d+/', $html, $leak );
					tp_at_note( 'Leak', 'پست منتشرنشده در خروجی: ' . ( $leak ? $leak[0] : 'TPHIDDEN' ), '-' );
				}
				if ( 'tp_page' === $call[0] ) {
					$sizes[ $mode ][ $type ] = strlen( (string) $html );
					if ( 0 !== strpos( (string) $html, '<div class="tp-page">' ) ) {
						tp_at_note( 'Shape', 'صفحه با پوستهٔ .tp-page شروع نشد', '-' );
					}
					/* ۲ه٫۴ · P5-D313 — هر بخش کارتی با اعضای بیش از سقف: پیوند «همه …» و پنجرۀ فهرست با همان شمار؛ زیر سقف: نه پنجره */
					if ( 'many' === $mode ) {
						foreach ( tp_at_more_bad( $pid, $html ) as $why ) {
							tp_at_note( 'More', $why, 'P5-D313' );
						}
						$GLOBALS['tp_at_many_html'][ $type ] = $html;
						$GLOBALS['tp_at_many_pid'][ $type ]  = $pid;
					}
					/* ۲ج٫۱ — ترتیب و بی‌خالیِ ناحیه‌ها؛ در «پر» که بیشترین ناحیه را دارد */
					if ( 'rich' === $mode ) {
						foreach ( tp_at_order_bad( $html ) as $why ) {
							tp_at_note( 'Order', $why, 'P5-D275' );
						}
						$GLOBALS['tp_at_orders'][ $type ] = $html;
					}
				}
			} catch ( Throwable $e ) {
				tp_at_note( get_class( $e ), $e->getMessage(), basename( $e->getFile() ) . ':' . $e->getLine() );
			}
		}
	}
}

$GLOBALS['tp_at_ctx'] = array( 'mode' => '-', 'type' => '-', 'tag' => 'done' );

/* جهش ادعای ترتیب: صفحۀ استان با ستون اصلیِ وارونه و یک بخش خالی باید ایراد بگیرد */
$tp_order_real = isset( $GLOBALS['tp_at_orders']['tp_province'] ) ? $GLOBALS['tp_at_orders']['tp_province'] : '';
$tp_order_mut  = preg_replace_callback( '~(<div class="tp-main-col">)(.*?)(</div><aside class="tp-rail")~su', function ( $m ) {
	preg_match_all( '~<section id="tp-[a-z0-9-]+".*?</section>~su', $m[2], $s );
	return $m[1] . implode( '', array_reverse( $s[0] ) ) . $m[3];
}, $tp_order_real );
if ( '' === $tp_order_real || tp_at_order_bad( $tp_order_real ) || ! tp_at_order_bad( $tp_order_mut )
	|| ! tp_at_order_bad( str_replace( '</div><aside class="tp-rail"', '<section id="tp-reviews" class="tp-section"><div> </div></section></div><aside class="tp-rail"', $tp_order_real ) ) ) {
	tp_at_note( 'Mutant', 'ادعای ترتیب ناحیه‌ها جهش (ستون وارونه · بخش خالی) را نگرفت', 'P5-D275' );
}

/*
 * ۲ه٫۴ — شمارنده‌ها پیش از جهش (جهش خودش تابع را صدا می‌زند) و جهش‌ها: بی پیوند، بی پنجره و شمار نادرست
 * باید ایراد بگیرند. ادعا تهی نماند: همهٔ نوع‌ها در «many» رندر شده‌اند و دست‌کم ده بخش از سقف گذشته و
 * سنجیده شده.
 */
$tp_more_stat = isset( $GLOBALS['tp_at_more'] ) ? $GLOBALS['tp_at_more'] : array( 'sections' => 0, 'over' => 0 );
$tp_many_all  = isset( $GLOBALS['tp_at_many_html'] ) ? $GLOBALS['tp_at_many_html'] : array();
$tp_many_pid  = isset( $GLOBALS['tp_at_many_pid']['tp_province'] ) ? $GLOBALS['tp_at_many_pid']['tp_province'] : 0;
$tp_many_html = isset( $tp_many_all['tp_province'] ) ? $tp_many_all['tp_province'] : '';

if ( count( $tp_many_all ) !== count( $TYPES ) || ! isset( $tp_more_stat['over'] ) || $tp_more_stat['over'] < 10 ) {
	tp_at_note( 'Mutant', 'ادعای «همه …» تهی ماند: ' . count( $tp_many_all ) . ' نوع در «many»، ' . ( isset( $tp_more_stat['over'] ) ? $tp_more_stat['over'] : 0 ) . ' بخش بیش از سقف', 'P5-D313' );
}

if ( ! $tp_many_pid || '' === $tp_many_html || tp_at_more_bad( $tp_many_pid, $tp_many_html )
	|| ! tp_at_more_bad( $tp_many_pid, str_replace( 'data-tp-list-open', 'data-x', $tp_many_html ) )
	|| ! tp_at_more_bad( $tp_many_pid, preg_replace( '~id="tp-list-villages"~', 'id="x-villages"', $tp_many_html ) )
	|| ! tp_at_more_bad( $tp_many_pid, preg_replace( '~(id="tp-list-villages"[^>]*data-tp-total=")\d+~', '${1}3', $tp_many_html ) ) ) {
	tp_at_note( 'Mutant', 'ادعای «همه …» جهش (بی پیوند · بی پنجره · شمار نادرست) را نگرفت', 'P5-D313' );
}

/*
 * حکم.
 *
 *   empty · rich · odd · obj   هیچ چیز — نه خطا، نه هشدار، نه اخطار
 *   arr                        هیچ خطای مرگبار. هشدارِ «Array to string» شمرده
 *                              می‌شود ولی حکم نمی‌دهد: آرایه در فیلدی که
 *                              JetEngine رشته نگه می‌دارد دادهٔ واقعی نیست؛ این
 *                              حالت فقط برای این است که ۵۰۰ از آن درنیاید
 */
$fail = array();
$soft = 0;

foreach ( $GLOBALS['tp_at_problems'] as $p ) {
	if ( 'arr' === $p['mode'] && 'Warning' === $p['kind'] ) {
		$soft++;
		continue;
	}
	$fail[] = $p;
}

$result = array(
	'php'      => PHP_VERSION,
	'types'    => count( $TYPES ),
	'renders'  => $renders,
	'problems' => $fail,
	'soft'     => $soft,
	'more'     => $tp_more_stat,
	'manyTypes' => count( $tp_many_all ),
	'modes'    => array_keys( $sizes ),
	'sizes'    => $sizes,
);

if ( $TP_JSON ) {
	echo json_encode( $result, JSON_UNESCAPED_UNICODE ), "\n";
	exit( $fail ? 1 : 0 );
}

echo 'PHP ', PHP_VERSION, ' · ', count( $TYPES ), ' نوع عمومی · ', $renders, " رندر\n\n";

$by = array();
foreach ( $fail as $p ) {
	$k = $p['kind'] . ': ' . $p['msg'] . ' @ ' . $p['at'];
	$by[ $k ][] = $p['mode'] . '·' . $p['type'] . '·' . $p['tag'];
}

foreach ( $by as $k => $where ) {
	echo '✗ ', $k, "\n    ", count( $where ), ' بار — ', implode( ' | ', array_slice( array_unique( $where ), 0, 6 ) ), "\n";
}

if ( ! $by ) {
	echo '✓ ۲ه٫۴ «همه …»: ', $result['manyTypes'], ' نوع در «many» · ', $tp_more_stat['sections'], ' بخش سنجیده · ', $tp_more_stat['over'], " بخش بیش از سقف — هر کدام پیوند و پنجرهٔ هم‌شمار؛ زیر سقف بی پنجره؛ جهش‌ها گرفته شد\n";
	echo "✓ خالی · پر · ناجور · شیء — هیچ خطا، هشدار یا اخطاری\n";
	echo '✓ آرایه در همه‌جا — هیچ خطای مرگبار (', $soft, " هشدارِ تبدیل آرایه، بی‌حکم)\n";
}

exit( $by ? 1 : 0 );
