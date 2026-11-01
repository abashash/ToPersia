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

module.exports = { norm, compareRequests };
