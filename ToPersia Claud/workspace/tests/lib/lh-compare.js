'use strict';
/**
 * مقایسهٔ فهرست درخواست دو نسخهٔ ایستا، پس از نرمال‌سازی ver= (P5-D496 · PERF.md).
 * خالص و بی وابستگی تا واحد آزموده شود؛ lighthouse.js خودش را هنگام بار اجرا می‌کند و قابل بارگذاری نیست.
 * فقط ver= نرمال می‌شود؛ ?v= کاشی و مرز، range و body تفاوت واقعی می‌مانند.
 */

const norm = k => k.replace( /([?&])ver=[^&\s]*/g, '$1ver=*' );

function compareRequests( a, b ) {
	const group = l => l.reduce( ( m, k ) => {
		const n = norm( k );
		( m[ n ] = m[ n ] || [] ).push( k );
		return m;
	}, {} );
	const A = group( a ), B = group( b ), onlyA = [], onlyB = [], normalized = [];
	for ( const n of new Set( [ ...Object.keys( A ), ...Object.keys( B ) ] ) ) {
		const x = ( A[ n ] || [] ).slice().sort(), y = ( B[ n ] || [] ).slice().sort();
		for ( let i = x.length; i < y.length; i++ ) { onlyB.push( y[ i ] ); }
		for ( let i = y.length; i < x.length; i++ ) { onlyA.push( x[ i ] ); }
		for ( let i = 0; i < Math.min( x.length, y.length ); i++ ) {
			if ( x[ i ] !== y[ i ] ) { normalized.push( { a: x[ i ], b: y[ i ] } ); }
		}
	}
	return { equal: ! onlyA.length && ! onlyB.length, onlyA, onlyB, normalized };
}

/** کلید جفت‌شده با وضعیت متفاوت: دارایی شکسته امتیاز را جابه‌جا می‌کند و فهرست کلید آن را نمی‌بیند. */
function statusDiffs( ea, eb ) {
	const cmp = compareRequests( Object.keys( ea ), Object.keys( eb ) );
	const moved = new Map( cmp.normalized.map( n => [ n.a, n.b ] ) );
	const out = [];
	for ( const a of Object.keys( ea ) ) {
		const b = moved.has( a ) ? moved.get( a ) : a;
		if ( eb[ b ] && ea[ a ].status !== eb[ b ].status ) { out.push( { key: a, a: ea[ a ].status, b: eb[ b ].status } ); }
	}
	return out;
}

module.exports = { norm, compareRequests, statusDiffs };
