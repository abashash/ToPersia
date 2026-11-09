/**
 * آرشیو خبر — P5-D534 · دستۀ 49
 *
 * ۱. دکمۀ شبکه/فهرست: کلاس `tp-news-grid--rows` روی فهرست؛ انتخاب در
 *    localStorage همین مرورگر (اگر در دسترس نبود، بی‌صدا پیش‌فرض شبکه).
 *    بی جاوااسکریپت دکمه‌ها پنهان می‌مانند و شبکه سر جایش است.
 * پالایش و ترتیب فقط با دکمۀ «نمایش» فرستاده می‌شوند — تغییر select خودش صفحه را عوض
 * نمی‌کند (WCAG 3.2.2).
 */
( function () {
	'use strict';

	var KEY = 'tp-news-view';

	function read() {
		try {
			return window.localStorage.getItem( KEY ) === 'list' ? 'list' : 'grid';
		} catch ( e ) {
			return 'grid';
		}
	}

	function write( v ) {
		try {
			window.localStorage.setItem( KEY, v );
		} catch ( e ) { /* حالت خصوصی یا دادۀ مسدود — پیش‌فرض بس است */ }
	}

	function apply( box, items, v ) {
		items.classList.toggle( 'tp-news-grid--rows', v === 'list' );
		box.querySelectorAll( '[data-tp-view]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-pressed', b.getAttribute( 'data-tp-view' ) === v ? 'true' : 'false' );
		} );
	}

	function init() {
		var box   = document.querySelector( '[data-tp-news-view]' );
		var items = document.querySelector( '[data-tp-news-items]' );
		if ( box && items ) {
			box.hidden = false;
			apply( box, items, read() );
			box.addEventListener( 'click', function ( e ) {
				var b = e.target.closest( '[data-tp-view]' );
				if ( ! b ) {
					return;
				}
				var v = b.getAttribute( 'data-tp-view' ) === 'list' ? 'list' : 'grid';
				write( v );
				apply( box, items, v );
			} );
		}

	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
