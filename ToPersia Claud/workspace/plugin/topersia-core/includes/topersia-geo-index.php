<?php
/**
 * نمایۀ مشتق مکانی — جدول `topersia_geo` (P5-D334 · P5-D346).
 *
 * یک ردیف برای هر پست منتشرشدۀ زبان پیش‌فرض که مختصات معتبر دارد: post_id · post_type · lat · lng (نقطه یا مرکز شکل) ·
 * geom_kind · province_id و city_id از زنجیرۀ R01 · index_version. حقیقت همچنان `geo_coordinates` پست است؛ این جدول فقط
 * مشتق است، همیشه از نو ساخته می‌شود (`wp topersia geo-index rebuild`) و هرگز منبع نوشتن نیست. مختصات از تابع مرکزی
 * `topersia-geo.php` (DMS، دهدهی، شکل). زنجیرۀ R01 را افزونۀ نمایش از صافی `topersia_geo_index_chain` می‌دهد (هسته به
 * افزونۀ نمایش وابسته نیست).
 *
 * ساختار: A (DECIMAL + نمایۀ ترکیبی) — انتخاب از بنچمارک ۲۰۰ هزار ردیفی MariaDB 10.11 (docs/GEO-BENCH-2F.md).
 * فقط محلی تا ممیزی پیوست الف بند ۶ (همان دروازۀ جدول کش): `topersia_geo_index_enabled()`.
 *
 * @package ToPersia_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'TOPERSIA_GEO_INDEX_VERSION' ) ) {
	define( 'TOPERSIA_GEO_INDEX_VERSION', 1 );
}

/** دروازۀ محلی؛ صافی برای آزمون. */
function topersia_geo_index_enabled() {
	return (bool) apply_filters( 'topersia_geo_index_enabled', 'local' === wp_get_environment_type() );
}

function topersia_geo_index_table() {
	global $wpdb;
	return $wpdb->prefix . 'topersia_geo';
}

/** نوع‌هایی که نقطه می‌گیرند: همۀ نوع‌های عمومی `tp_` جز شهرستان (بی‌صفحه). */
function topersia_geo_index_types() {

	$types = array();

	foreach ( get_post_types( array( 'public' => true ) ) as $t ) {
		if ( 0 === strpos( $t, 'tp_' ) && 'tp_county' !== $t ) {
			$types[] = $t;
		}
	}

	return (array) apply_filters( 'topersia_geo_index_types', $types );
}

/** ساختن/به‌روز کردن جدول (dbDelta). */
function topersia_geo_index_install( $force = false ) {

	if ( ! topersia_geo_index_enabled() ) {
		return false;
	}

	if ( ! $force && (string) TOPERSIA_GEO_INDEX_VERSION === (string) get_option( 'topersia_geo_index_schema' ) ) {
		return true;
	}

	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	dbDelta( 'CREATE TABLE ' . topersia_geo_index_table() . " (
		post_id BIGINT UNSIGNED NOT NULL,
		post_type VARCHAR(40) NOT NULL DEFAULT '',
		lat DECIMAL(8,6) NOT NULL,
		lng DECIMAL(9,6) NOT NULL,
		geom_kind VARCHAR(12) NOT NULL DEFAULT 'point',
		province_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		city_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		index_version SMALLINT UNSIGNED NOT NULL DEFAULT 1,
		PRIMARY KEY  (post_id),
		KEY ll (lat,lng),
		KEY tll (post_type,lat,lng),
		KEY prov (province_id,post_type),
		KEY city (city_id,post_type)
	) " . $wpdb->get_charset_collate() . ';' );

	update_option( 'topersia_geo_index_schema', (string) TOPERSIA_GEO_INDEX_VERSION, false );

	return true;
}

/** آیا پست در زبان پیش‌فرض است (ترجمه‌ها مختصات را کپی می‌کنند و ردیف جدا نمی‌خواهند). */
function topersia_geo_index_is_default_lang( $post_id ) {

	$default = apply_filters( 'wpml_default_language', null );

	if ( ! $default ) {
		return true;
	}

	$lang = apply_filters( 'wpml_element_language_code', null, array( 'element_id' => (int) $post_id, 'element_type' => (string) get_post_type( $post_id ) ) );

	return ! $lang || $lang === $default;
}

/**
 * ردیف نمایۀ یک پست — یا null (منتشرنشده، زبان دیگر، بی مختصات معتبر، بیرون از ایران، نوع بیرون از فهرست).
 *
 * @return array|null
 */
function topersia_geo_index_row( $post_id ) {

	$post_id = (int) $post_id;
	$type    = (string) get_post_type( $post_id );

	if ( ! $post_id || 'publish' !== get_post_status( $post_id ) || ! in_array( $type, topersia_geo_index_types(), true ) || ! topersia_geo_index_is_default_lang( $post_id ) ) {
		return null;
	}

	$p = topersia_geo_parse( get_post_meta( $post_id, 'geo_coordinates', true ) );

	if ( 'valid' !== $p['status'] ) {
		return null;
	}

	$chain    = array_map( 'intval', (array) apply_filters( 'topersia_geo_index_chain', array(), $post_id ) );
	$province = 0;
	$city     = 0;

	foreach ( $chain as $id ) {
		$t = get_post_type( $id );
		if ( 'tp_province' === $t ) { $province = $id; }
		if ( 'tp_city' === $t ) { $city = $id; }
	}

	return array(
		'post_id'       => $post_id,
		'post_type'     => $type,
		'lat'           => $p['lat'],
		'lng'           => $p['lng'],
		'geom_kind'     => $p['kind'],
		'province_id'   => $province,
		'city_id'       => $city,
		'index_version' => (int) TOPERSIA_GEO_INDEX_VERSION,
	);
}

/** گواهِ تازگی: هر نوشتن عددش را عوض می‌کند — کلید کش `map-points` از آن می‌آید. */
function topersia_geo_index_stamp( $bump = false ) {

	if ( $bump ) {
		update_option( 'topersia_geo_index_stamp', (string) microtime( true ), false );
	}

	return (string) get_option( 'topersia_geo_index_stamp', '0' );
}

/** یک پست را در نمایه بگذارد (REPLACE) یا بردارد. */
function topersia_geo_index_put( $post_id ) {

	if ( ! topersia_geo_index_enabled() ) {
		return false;
	}

	global $wpdb;
	$table = topersia_geo_index_table();
	$row   = topersia_geo_index_row( $post_id );

	if ( ! $row ) {
		$wpdb->delete( $table, array( 'post_id' => (int) $post_id ), array( '%d' ) );
		topersia_geo_index_stamp( true );
		return false;
	}

	$wpdb->replace( $table, $row, array( '%d', '%s', '%f', '%f', '%s', '%d', '%d', '%d' ) );
	topersia_geo_index_stamp( true );

	return true;
}

/** بازسازی کامل: خالی ← همۀ نوع‌ها ← همۀ پست‌های منتشرشده. */
function topersia_geo_index_rebuild() {

	if ( ! topersia_geo_index_enabled() ) {
		return array( 'error' => 'محلی نیست' );
	}

	global $wpdb;
	topersia_geo_index_install( true );
	$table = topersia_geo_index_table();
	$wpdb->query( "TRUNCATE TABLE {$table}" );

	$n = 0;

	foreach ( topersia_geo_index_types() as $type ) {

		$ids = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true, 'orderby' => 'ID', 'order' => 'ASC' ) );

		foreach ( $ids as $id ) {

			$row = topersia_geo_index_row( $id );

			if ( $row ) {
				$wpdb->insert( $table, $row, array( '%d', '%s', '%f', '%f', '%s', '%d', '%d', '%d' ) );
				$n++;
			}
		}
	}

	topersia_geo_index_stamp( true );

	return array( 'rows' => $n, 'fingerprint' => topersia_geo_index_fingerprint() );
}

/** اثرانگشت محتوای جدول — برای آزمون «پاک و بازسازی ← همان خروجی». */
function topersia_geo_index_fingerprint() {

	global $wpdb;
	$table = topersia_geo_index_table();
	$rows  = $wpdb->get_results( "SELECT post_id,post_type,lat,lng,geom_kind,province_id,city_id,index_version FROM {$table} ORDER BY post_id", ARRAY_N );

	return md5( wp_json_encode( $rows ) ) . ':' . count( $rows );
}

/**
 * گزارش کیفیت مختصات به تفکیک نوع — پست‌های منتشرشدۀ زبان پیش‌فرض.
 * valid · absent (تهی) · outside (بیرون از ایران) · invalid (قالب ناخوانا) · duplicate (پست‌هایی که مختصاتشان عیناً با پست دیگری
 * از همان نوع یکی است، به‌جز نخستین).
 *
 * @return array<string,array>
 */
function topersia_geo_index_report() {

	$out = array();

	foreach ( topersia_geo_index_types() as $type ) {

		$ids  = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true ) );
		$r    = array( 'posts' => 0, 'valid' => 0, 'absent' => 0, 'outside' => 0, 'invalid' => 0, 'duplicate' => 0 );
		$seen = array();

		foreach ( $ids as $id ) {

			if ( ! topersia_geo_index_is_default_lang( $id ) ) {
				continue;
			}

			$r['posts']++;
			$p = topersia_geo_parse( get_post_meta( $id, 'geo_coordinates', true ) );
			$r[ $p['status'] ]++;

			if ( 'valid' === $p['status'] ) {
				$k = $p['lat'] . ',' . $p['lng'];
				if ( isset( $seen[ $k ] ) ) { $r['duplicate']++; } else { $seen[ $k ] = 1; }
			}
		}

		if ( $r['posts'] ) {
			$out[ $type ] = $r;
		}
	}

	return $out;
}

/**
 * ردیف‌های یک زمینه (R01) برای خانواده‌ای از نوع‌ها: استان یا شهر.
 *
 * @param string $scope  province · city
 * @param int    $id     شناسۀ استان یا شهر (زبان پیش‌فرض).
 * @param array  $types  نوع‌های پست.
 * @param int    $limit  سقف.
 * @return array<int,array> post_id · post_type · lat · lng
 */
function topersia_geo_index_context( $scope, $id, array $types, $limit = 500 ) {

	global $wpdb;

	if ( ! topersia_geo_index_enabled() || ! $types ) {
		return array();
	}

	$col = 'city' === $scope ? 'city_id' : 'province_id';
	$in  = implode( ',', array_fill( 0, count( $types ), '%s' ) );
	$sql = "SELECT post_id, post_type, lat, lng FROM " . topersia_geo_index_table() . " WHERE {$col} = %d AND post_type IN ({$in}) ORDER BY post_type, post_id LIMIT %d";

	return (array) $wpdb->get_results( $wpdb->prepare( $sql, array_merge( array( (int) $id ), $types, array( (int) $limit ) ) ), ARRAY_A );
}

/* ── همگام‌سازی تدریجی: ذخیره/حذف/تغییر وضعیت ───────────────────────── */

add_action( 'save_post', function ( $post_id, $post = null ) {

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! topersia_geo_index_enabled() ) {
		return;
	}

	$type = get_post_type( $post_id );

	if ( $type && 0 === strpos( $type, 'tp_' ) ) {
		topersia_geo_index_put( $post_id );
	}
}, 30, 2 );

add_action( 'before_delete_post', function ( $post_id ) {

	if ( ! topersia_geo_index_enabled() ) {
		return;
	}

	global $wpdb;
	$wpdb->delete( topersia_geo_index_table(), array( 'post_id' => (int) $post_id ), array( '%d' ) );
	topersia_geo_index_stamp( true );
} );

add_action( 'transition_post_status', function ( $new, $old, $post ) {

	if ( 'publish' !== $new && 'publish' === $old && topersia_geo_index_enabled() && $post && 0 === strpos( (string) $post->post_type, 'tp_' ) ) {
		global $wpdb;
		$wpdb->delete( topersia_geo_index_table(), array( 'post_id' => (int) $post->ID ), array( '%d' ) );
		topersia_geo_index_stamp( true );
	}
}, 10, 3 );

/* ── WP-CLI ──────────────────────────────────────────────────────────── */

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	/**
	 * نمایۀ مشتق مکانی — فقط محلی.
	 */
	class Topersia_Geo_Index_CLI {

		/** wp topersia geo-index rebuild — جدول را خالی و از نو می‌سازد. */
		public function rebuild() {
			$r = topersia_geo_index_rebuild();
			isset( $r['error'] ) ? WP_CLI::error( $r['error'] ) : WP_CLI::success( $r['rows'] . ' ردیف · ' . $r['fingerprint'] );
		}

		/** wp topersia geo-index verify — اثرانگشت، خالی‌کردن، بازسازی، اثرانگشت: باید برابر باشد. */
		public function verify() {
			global $wpdb;

			$a = topersia_geo_index_rebuild();

			/* جدول را کامل پاک می‌کنیم (نه فقط خالی) و از صفر می‌سازیم */
			$wpdb->query( 'DROP TABLE IF EXISTS ' . topersia_geo_index_table() );
			delete_option( 'topersia_geo_index_schema' );

			$b = topersia_geo_index_rebuild();
			isset( $a['fingerprint'] ) && $a['fingerprint'] === $b['fingerprint'] ? WP_CLI::success( 'برابر: ' . $a['fingerprint'] ) : WP_CLI::error( 'نابرابر' );
		}

		/** wp topersia geo-index report — کیفیت مختصات به تفکیک نوع (JSON). */
		public function report() {
			WP_CLI::line( wp_json_encode( topersia_geo_index_report(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
		}
	}

	WP_CLI::add_command( 'topersia geo-index', 'Topersia_Geo_Index_CLI' );
}
