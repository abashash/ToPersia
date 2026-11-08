/* صفحۀ اول · دستۀ 47 — بی کتابخانه، سقف ۴KB. تاکردن از جدول هسته (TopersiaG.fold). بی JS: پیوندها و noscript. */
( function () {
	'use strict';
	var d = document, home = d.querySelector( '.tp-home' );
	if ( ! home ) { return; }
	var $ = function ( s, c ) { return ( c || home ).querySelector( s ); };
	function each( s, f, c ) { [].forEach.call( ( c || home ).querySelectorAll( s ), f ); }
	function on( el, evs, f, o ) { evs.split( ' ' ).forEach( function ( e ) { el.addEventListener( e, f, o ); } ); }

	/* اسلایدرها: نوار topersia_g_slider_bar() و دستگیرۀ topersia-g-zones.js (P5-D243، P5-D529) — اینجا تکرار نمی‌شود. */

	var h = function ( n ) { return '\\u' + ( '000' + n.toString( 16 ) ).slice( -4 ); };
	var F = ( ( window.TopersiaG && TopersiaG.fold ) || [] ).map( function ( r ) {
		if ( r.l ) { return { l: 1 }; }
		if ( 'number' === typeof r.d ) { return { d: r.d, re: new RegExp( '[' + h( r.d ) + '-' + h( r.d + 9 ) + ']', 'g' ) }; }
		return { re: new RegExp( '[' + ( r.c || [] ).map( h ).join( '' ) + ( r.r || [] ).map( function ( x ) { return h( x[0] ) + '-' + h( x[1] ); } ).join( '' ) + ( r.s ? '\\s' : '' ) + ']' + ( r.s ? '+' : '' ), 'g' ), t: String( r.t || '' ) };
	} );
	function fold( s ) {
		s = String( s || '' );
		if ( ! F.length ) { return s.toLowerCase(); }
		F.forEach( function ( r ) {
			s = r.l ? s.toLowerCase() : undefined !== r.d ? s.replace( r.re, function ( c ) { return String( c.charCodeAt( 0 ) - r.d ); } ) : s.replace( r.re, r.t );
		} );
		return s;
	}

	function modal( dlg, back ) {
		on( dlg, 'click', function ( e ) { if ( e.target === dlg || e.target.closest( '[data-tp-close]' ) ) { dlg.close(); } } );
		on( dlg, 'close', function () { var o = back(); if ( o ) { o.focus(); } } );
	}

	var pd = d.getElementById( 'tp-home-provdlg' ), po = $( '[data-tp-provdlg]' );
	if ( pd && po && pd.showModal ) {
		po.hidden = false;
		on( po, 'click', function () { pd.showModal(); } );
		modal( pd, function () { return po; } );
		var q = $( 'input', pd ), out = $( '[aria-live]', pd );
		on( q, 'input', function () {
			var v = fold( q.value ), n = 0;
			each( 'li', function ( li ) { var ok = ! v || fold( li.getAttribute( 'data-tp-fold' ) || li.textContent ).indexOf( v ) > -1; li.hidden = ! ok; n += ok; }, pd );
			out.textContent = out.getAttribute( 'data-tp-n' ).replace( '%s', n );
		} );
	}

	var map = $( '.tp-home-map' ), tip = map && $( '.tp-home-map__tip', map );
	if ( tip ) {
		each( '[data-tp-tip]', function ( a ) {
			on( a, 'pointerenter focus touchstart', function () {
				var r = a.getBoundingClientRect(), m = map.getBoundingClientRect();
				tip.textContent = a.getAttribute( 'data-tp-tip' );
				tip.style.left = Math.round( r.left + r.width / 2 - m.left ) + 'px';
				tip.style.top = Math.round( r.top - m.top ) + 'px';
				tip.hidden = false;
			}, { passive: true } );
			on( a, 'pointerleave blur', function () { tip.hidden = true; } );
		}, map );
	}

	var gd = d.getElementById( 'tp-home-photodlg' ), op = null;
	if ( gd && gd.showModal ) {
		modal( gd, function () { return op; } );
		each( '.tp-home-photo[data-tp-full]', function ( a ) {
			on( a, 'click', function ( e ) {
				var img = $( 'img', gd ), s = $( 'img', a );
				e.preventDefault();
				img.src = a.getAttribute( 'data-tp-full' );
				img.alt = s ? s.alt : '';
				img.width = a.getAttribute( 'data-tp-w' );
				img.height = a.getAttribute( 'data-tp-h' );
				$( 'h2', gd ).textContent = a.getAttribute( 'data-tp-title' );
				$( 'p', gd ).textContent = a.getAttribute( 'data-tp-desc' ) || '';
				$( '.tp-home-photodlg__go', gd ).href = a.href;
				op = a;
				gd.showModal();
			} );
		} );
	}
} )();
