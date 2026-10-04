<?php
/**
 * جست‌وجو و جایگزینیِ نشانی روی دیتابیسِ نسخهٔ محلی — ۴٫۲٫۲
 *
 * هم‌ارزِ `wp search-replace --all-tables --precise`، چون WP-CLI روی این
 * ماشین نیست. فقط برای `topersia_local`؛ هرگز روی دیتابیسِ زنده.
 *
 *   سریالی      با تجزیهٔ واقعیِ قالبِ سریالی، نه regex: فقط رشته‌های مقدار
 *               عوض می‌شوند و طولشان از نو نوشته می‌شود؛ کلیدِ آرایه و نامِ
 *               کلاس دست نمی‌خورند؛ شیءِ کلاسی که بار نشده هم سالم می‌ماند
 *               چون هرگز `unserialize` نمی‌شود — فقط خوانده و بازنویسی
 *   تودرتو      رشته‌ای که خودش سریالی است، بازگشتی
 *   JSON        شکلِ گریختهٔ `https:\/\/…` هم جفتِ خودش را دارد — دادهٔ
 *               المنتور همین شکل است و `--precise` آن را نمی‌گرفت
 *   ایمنی       هر مقدارِ سریالیِ بازنویسی‌شده پیش از نوشتن با `unserialize`
 *               آزموده می‌شود؛ اگر خراب شد، ردیف دست نمی‌خورد و گزارش می‌شود
 *
 * اجرا:
 *   php scripts/tp-local-sr.php            فقط شمارش (خشک)
 *   php scripts/tp-local-sr.php --write    نوشتن
 */

require_once dirname( __DIR__ ) . '/tests/env.php';

$WRITE = in_array( '--write', $argv, true );
$DB    = 'topersia_local';

if ( 'topersia_local' !== $DB ) {
	fwrite( STDERR, "فقط topersia_local\n" );
	exit( 2 );
}

$PAIRS = array(
	'https://topersia.com'   => TP_BASE_URL,
	'https:\\/\\/topersia.com' => 'http:\\/\\/topersia.local',
);

$db = new mysqli( '127.0.0.1', 'root', '', $DB, 3306 );
$db->set_charset( 'utf8mb4' );

/* ── تجزیه‌گرِ سریالی ─────────────────────────────────────────────── */

class TP_SR_Fail extends Exception {}

function tp_sr_num( $s, &$i, $end_char ) {
	$end = strpos( $s, $end_char, $i );
	if ( false === $end ) { throw new TP_SR_Fail( 'پایان عدد' ); }
	$n = substr( $s, $i, $end - $i );
	$i = $end + 1;
	return $n;
}

/** یک مقدار را از `$i` می‌خواند و نسخهٔ بازنویسی‌شده‌اش را برمی‌گرداند. */
function tp_sr_node( $s, &$i, $pairs, $as_key = false ) {

	if ( ! isset( $s[ $i ] ) ) { throw new TP_SR_Fail( 'پایان ناگهانی' ); }

	$t = $s[ $i ];

	switch ( $t ) {

		case 'N':
			if ( 'N;' !== substr( $s, $i, 2 ) ) { throw new TP_SR_Fail( 'N' ); }
			$i += 2;
			return 'N;';

		case 'b': case 'i': case 'd': case 'r': case 'R':
			if ( ':' !== $s[ $i + 1 ] ) { throw new TP_SR_Fail( $t ); }
			$i += 2;
			$v = tp_sr_num( $s, $i, ';' );
			return $t . ':' . $v . ';';

		case 's':
			if ( ':' !== $s[ $i + 1 ] ) { throw new TP_SR_Fail( 's' ); }
			$i  += 2;
			$len = (int) tp_sr_num( $s, $i, ':' );
			if ( '"' !== $s[ $i ] ) { throw new TP_SR_Fail( 's"' ); }
			$str = substr( $s, $i + 1, $len );
			if ( strlen( $str ) !== $len || '";' !== substr( $s, $i + 1 + $len, 2 ) ) { throw new TP_SR_Fail( 'طول رشته' ); }
			$i += 1 + $len + 2;
			$new = $as_key ? $str : tp_sr_value( $str, $pairs );
			return 's:' . strlen( $new ) . ':"' . $new . '";';

		case 'a':
			$i += 2;
			$n  = (int) tp_sr_num( $s, $i, ':' );
			if ( '{' !== $s[ $i ] ) { throw new TP_SR_Fail( 'a{' ); }
			$i++;
			$out = 'a:' . $n . ':{';
			for ( $k = 0; $k < $n; $k++ ) {
				$out .= tp_sr_node( $s, $i, $pairs, true );
				$out .= tp_sr_node( $s, $i, $pairs );
			}
			if ( '}' !== $s[ $i ] ) { throw new TP_SR_Fail( 'a}' ); }
			$i++;
			return $out . '}';

		case 'O':
			$i   += 2;
			$clen = (int) tp_sr_num( $s, $i, ':' );
			$cls  = substr( $s, $i + 1, $clen );
			$i   += 1 + $clen + 2;             /* "Class": */
			$n    = (int) tp_sr_num( $s, $i, ':' );
			if ( '{' !== $s[ $i ] ) { throw new TP_SR_Fail( 'O{' ); }
			$i++;
			$out = 'O:' . $clen . ':"' . $cls . '":' . $n . ':{';
			for ( $k = 0; $k < $n; $k++ ) {
				$out .= tp_sr_node( $s, $i, $pairs, true );
				$out .= tp_sr_node( $s, $i, $pairs );
			}
			if ( '}' !== $s[ $i ] ) { throw new TP_SR_Fail( 'O}' ); }
			$i++;
			return $out . '}';

		case 'C':                                  /* C:len:"Class":plen:{…} — دست‌نخورده */
			$start = $i;
			$i    += 2;
			$clen  = (int) tp_sr_num( $s, $i, ':' );
			$i    += 1 + $clen + 2;
			$plen  = (int) tp_sr_num( $s, $i, ':' );
			$i    += 1 + $plen + 1;
			return substr( $s, $start, $i - $start );

		case 'E':                                  /* E:len:"Enum:Case"; */
			$start = $i;
			$i    += 2;
			$elen  = (int) tp_sr_num( $s, $i, ':' );
			$i    += 1 + $elen + 2;
			return substr( $s, $start, $i - $start );
	}

	throw new TP_SR_Fail( 'نوع ناشناس ' . $t );
}

/** اگر کل رشته یک مقدار سریالیِ کامل است، نسخهٔ بازنویسی‌شده؛ وگرنه null. */
function tp_sr_serialized( $str, $pairs ) {

	if ( strlen( $str ) < 2 || ! preg_match( '/^(?:[aOsibdNCE]:|N;)/', $str ) ) {
		return null;
	}

	try {
		$i   = 0;
		$out = tp_sr_node( $str, $i, $pairs );
		return $i === strlen( $str ) ? $out : null;
	} catch ( TP_SR_Fail $e ) {
		return null;
	}
}

function tp_sr_value( $str, $pairs ) {

	$ser = tp_sr_serialized( $str, $pairs );

	if ( null !== $ser ) {
		return $ser;
	}

	return strtr( $str, $pairs );
}

/* ── گذر روی جدول‌ها ──────────────────────────────────────────────── */

$tables  = array();
$res     = $db->query( "SHOW TABLES" );
while ( $row = $res->fetch_row() ) { $tables[] = $row[0]; }

$total   = 0;
$rows_ch = 0;
$broken  = array();

foreach ( $tables as $table ) {

	$pk   = array();
	$cols = array();

	$r = $db->query( "SHOW COLUMNS FROM `{$table}`" );
	while ( $c = $r->fetch_assoc() ) {
		if ( 'PRI' === $c['Key'] ) { $pk[] = $c['Field']; }
		if ( preg_match( '/char|text|blob|json/i', $c['Type'] ) ) { $cols[] = $c['Field']; }
	}

	if ( ! $pk || ! $cols ) {
		continue;
	}

	foreach ( $cols as $col ) {

		$where = array();
		foreach ( array_keys( $PAIRS ) as $needle ) {
			$where[] = "`{$col}` LIKE '%" . $db->real_escape_string( addcslashes( $needle, '%_\\' ) ) . "%'";
		}

		$sel = "SELECT " . implode( ',', array_map( function ( $k ) { return "`{$k}`"; }, $pk ) ) . ", `{$col}` AS v FROM `{$table}` WHERE " . implode( ' OR ', $where );
		$q   = $db->query( $sel );

		if ( ! $q ) { fwrite( STDERR, "⚠ {$table}.{$col}: " . $db->error . "\n" ); continue; }

		while ( $row = $q->fetch_assoc() ) {

			$old = (string) $row['v'];
			$new = tp_sr_value( $old, $PAIRS );

			if ( $new === $old ) { continue; }

			/* مقدارِ سریالی باید سریالی بماند */
			$was_ser = null !== tp_sr_serialized( $old, array() );
			if ( $was_ser && false === @unserialize( $new, array( 'allowed_classes' => false ) ) && 'b:0;' !== $new ) {
				$broken[] = "{$table}.{$col} " . json_encode( array_intersect_key( $row, array_flip( $pk ) ) );
				continue;
			}

			$total += substr_count( $old, 'topersia.com' ) - substr_count( $new, 'topersia.com' );
			$rows_ch++;

			if ( $WRITE ) {
				$set  = "UPDATE `{$table}` SET `{$col}` = '" . $db->real_escape_string( $new ) . "' WHERE ";
				$cond = array();
				foreach ( $pk as $k ) { $cond[] = "`{$k}` = '" . $db->real_escape_string( (string) $row[ $k ] ) . "'"; }
				if ( ! $db->query( $set . implode( ' AND ', $cond ) ) ) {
					fwrite( STDERR, "⚠ UPDATE {$table}.{$col}: " . $db->error . "\n" );
				}
			}
		}
	}
}

printf( "%s — ردیف: %d · جایگزینی: %d · سریالیِ رد‌شده: %d\n", $WRITE ? 'نوشته شد' : 'خشک', $rows_ch, $total, count( $broken ) );
foreach ( $broken as $b ) { echo "  ✗ ", $b, "\n"; }
