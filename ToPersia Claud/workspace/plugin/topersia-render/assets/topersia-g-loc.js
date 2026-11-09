/**
 * فیلتر مکان — combobox متنی (P5-D549 · دستۀ 51). نشانه‌گذاری: topersia_g_loc_field() در topersia-g-loc.php.
 *
 * پس از ۳ نویسه از REST فقط‌خواندنی (حداکثر ۸، استان‌ها اول، نوع کنار هر نتیجه) فهرست می‌سازد.
 * کلیدها: پایین/بالا جابه‌جایی (aria-activedescendant)، Enter انتخاب، Esc بستن و بار دوم پاک‌کردن.
 * انتخاب ← tp_place پنهان در ناحیۀ `data-tp-news-swap="loc"` + ارسال فرم؛ topersia-g-news.js همان ارسال را
 * بی‌رفرش انجام می‌دهد (P5-D546) و ناحیۀ loc را با تراشۀ حذف‌شدنی از پاسخ سرور جای‌گزین می‌کند.
 * بی JS: همان فیلد متنی `tp_loc` با GET و تطبیق fold سرور.
 */
( function () {
	'use strict';

	var MIN   = 3;
	var timer = null;
	var ctrl  = null;

	function widget( el ) {
		return el && el.closest ? el.closest( '[data-tp-loc]' ) : null;
	}

	function parts( w ) {
		return {
			input: w.querySelector( '.tp-loc__in' ),
			list:  w.querySelector( '.tp-loc__list' ),
			live:  w.querySelector( '[data-tp-loc-live]' ),
			swap:  w.querySelector( '[data-tp-news-swap="loc"]' )
		};
	}

	function opts( p ) {
		return Array.prototype.slice.call( p.list.querySelectorAll( '[role="option"]:not([aria-disabled="true"])' ) );
	}

	function close( p ) {
		p.list.hidden = true;
		p.input.setAttribute( 'aria-expanded', 'false' );
		p.input.removeAttribute( 'aria-activedescendant' );
	}

	function active( p, i ) {
		var all = opts( p );
		all.forEach( function ( o, k ) {
			o.setAttribute( 'aria-selected', k === i ? 'true' : 'false' );
		} );
		if ( all[ i ] ) {
			p.input.setAttribute( 'aria-activedescendant', all[ i ].id );
			all[ i ].scrollIntoView( { block: 'nearest' } );
		} else {
			p.input.removeAttribute( 'aria-activedescendant' );
		}
	}

	function current( p ) {
		var id = p.input.getAttribute( 'aria-activedescendant' );
		return id ? document.getElementById( id ) : null;
	}

	function render( w, p, items ) {
		var base = p.input.id + '-o';
		p.list.textContent = '';
		if ( ! items.length ) {
			var none = document.createElement( 'li' );
			none.setAttribute( 'role', 'option' );
			none.setAttribute( 'aria-disabled', 'true' );
			none.className = 'tp-loc__none';
			none.textContent = w.getAttribute( 'data-tp-loc-none' ) || '';
			p.list.appendChild( none );
		}
		items.forEach( function ( it, k ) {
			var li = document.createElement( 'li' );
			li.id = base + k;
			li.className = 'tp-loc__opt';
			li.setAttribute( 'role', 'option' );
			li.setAttribute( 'aria-selected', 'false' );
			li.setAttribute( 'data-id', String( it.id ) );
			var n = document.createElement( 'span' );
			n.className = 'tp-loc__name';
			n.textContent = it.name;
			var t = document.createElement( 'span' );
			t.className = 'tp-loc__type';
			t.textContent = it.type;
			li.appendChild( n );
			li.appendChild( t );
			p.list.appendChild( li );
		} );
		p.list.hidden = false;
		p.input.setAttribute( 'aria-expanded', 'true' );
		if ( p.live ) {
			p.live.textContent = items.length ? ( w.getAttribute( 'data-tp-loc-count' ) || '%s' ).replace( '%s', String( items.length ) ) : ( w.getAttribute( 'data-tp-loc-none' ) || '' );
		}
	}

	function query( w, p ) {
		var q = p.input.value.trim();
		if ( q.length < MIN ) {
			close( p );
			return;
		}
		if ( ctrl ) {
			ctrl.abort();
		}
		ctrl = typeof window.AbortController === 'function' ? new window.AbortController() : null;
		var mine = ctrl;
		var u = new URL( w.getAttribute( 'data-tp-loc-api' ), window.location.href );
		u.searchParams.set( 'q', q );
		window.fetch( u.href, { credentials: 'same-origin', signal: mine ? mine.signal : undefined } )
			.then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( 'HTTP ' + r.status );
				}
				return r.json();
			} )
			.then( function ( d ) {
				if ( mine === ctrl && p.input.value.trim() === q ) {
					render( w, p, d && Array.isArray( d.items ) ? d.items : [] );
				}
			} )
			.catch( function () {} );   /* بی فهرست، فیلد متنی با ارسال عادی کار می‌کند */
	}

	function pick( w, p, li ) {
		var id = li && li.getAttribute( 'data-id' );
		if ( ! id ) {
			return;
		}
		var hid = p.swap.querySelector( 'input[name="tp_place"]' );
		if ( ! hid ) {
			hid = document.createElement( 'input' );
			hid.type = 'hidden';
			hid.name = 'tp_place';
			p.swap.appendChild( hid );
		}
		hid.value = id;
		p.input.value = '';   /* نشانی فقط tp_place می‌گیرد؛ تراشه از پاسخ سرور می‌آید */
		close( p );
		var f = p.input.form;
		if ( f ) {
			if ( typeof f.requestSubmit === 'function' ) {
				f.requestSubmit();
			} else {
				f.submit();
			}
		}
	}

	document.addEventListener( 'input', function ( e ) {
		var w = widget( e.target );
		if ( ! w || ! e.target.classList.contains( 'tp-loc__in' ) ) {
			return;
		}
		var p = parts( w );
		window.clearTimeout( timer );
		timer = window.setTimeout( function () { query( w, p ); }, 180 );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		var w = widget( e.target );
		if ( ! w || ! e.target.classList.contains( 'tp-loc__in' ) ) {
			return;
		}
		var p   = parts( w );
		var all = opts( p );
		var cur = all.indexOf( current( p ) );
		if ( 'ArrowDown' === e.key || 'ArrowUp' === e.key ) {
			e.preventDefault();
			if ( p.list.hidden ) {
				query( w, p );
				return;
			}
			if ( all.length ) {
				active( p, 'ArrowDown' === e.key ? ( cur + 1 ) % all.length : ( cur <= 0 ? all.length - 1 : cur - 1 ) );
			}
		} else if ( 'Enter' === e.key ) {
			if ( ! p.list.hidden && cur >= 0 ) {
				e.preventDefault();
				pick( w, p, all[ cur ] );
			}
			/* بی گزینۀ فعال: ارسال عادی فرم با tp_loc — سرور با fold تطبیق می‌دهد */
		} else if ( 'Escape' === e.key ) {
			if ( ! p.list.hidden ) {
				e.preventDefault();
				close( p );
			} else if ( p.input.value ) {
				e.preventDefault();
				p.input.value = '';
			}
		}
	} );

	/* mousedown تا فوکوس از فیلد نرود؛ انتخاب با کلیک */
	document.addEventListener( 'mousedown', function ( e ) {
		var li = e.target.closest ? e.target.closest( '.tp-loc__opt' ) : null;
		if ( li && widget( li ) ) {
			e.preventDefault();
		}
	} );
	document.addEventListener( 'click', function ( e ) {
		var li = e.target.closest ? e.target.closest( '.tp-loc__opt' ) : null;
		var w  = li ? widget( li ) : null;
		if ( w ) {
			pick( w, parts( w ), li );
		}
	} );

	document.addEventListener( 'focusout', function ( e ) {
		var w = widget( e.target );
		if ( w && e.target.classList.contains( 'tp-loc__in' ) ) {
			window.setTimeout( function () {
				if ( ! w.contains( document.activeElement ) ) {
					close( parts( w ) );
				}
			}, 120 );
		}
	} );
}() );
