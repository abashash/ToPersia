/**
 * صفحه‌های فهرست خبر — /news/ و /{نوع}/{نامک}/news/ · P5-D534 · دستۀ 49 · دستۀ 50
 *
 * ۱. شبکه/فهرست: کلاس `tp-news-grid--rows`؛ انتخاب در localStorage همین مرورگر
 *    (در دسترس نبود ← بی‌صدا شبکه). بی شبکه دکمه پنهان می‌ماند.
 * ۲. تراشه‌ها (P5-D542): یک خط اسکرولی؛ تراشۀ فعال در دید؛ محوشدن لبه‌ای که محتوا دارد؛
 *    پیکان‌های دسکتاپ فقط وقتی سرریز هست. جهت از getComputedStyle (P5-D243).
 * ۳. بی‌رفرش (P5-D546): کلیک تراشه، دسته، صفحه و «پاک‌کردن»، ارسال فرم و تغییر هر فهرست
 *    کشویی ← همین نشانی با `tp_partial=1`؛ فقط ناحیه‌های `data-tp-news-swap` جای‌گزین می‌شوند.
 *    history.pushState و popstate نشانی، «بازگشت» و اشتراک را درست نگه می‌دارند؛ aria-live
 *    شمار را می‌گوید؛ تمرکز روی تراشه یا عنوان نتیجه می‌ماند. درخواست تازه قبلی را لغو می‌کند
 *    (AbortController — آخرین برنده است). خطای شبکه ← پیام و بارگذاری عادی همان نشانی.
 *    بی جاوااسکریپت پیوندها و فرم GET همان کار را می‌کنند.
 */
( function () {
	'use strict';

	var KEY = 'tp-news-view';
	var root, live, ctrl = null;

	function reduced() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

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

	/* ── شبکه/فهرست ── */
	function applyView() {
		var box   = root.querySelector( '[data-tp-news-view]' );
		var items = root.querySelector( '[data-tp-news-items]' );
		if ( ! box ) {
			return;
		}
		box.hidden = ! items;
		if ( ! items ) {
			return;
		}
		var v = read();
		items.classList.toggle( 'tp-news-grid--rows', v === 'list' );
		box.querySelectorAll( '[data-tp-view]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-pressed', b.getAttribute( 'data-tp-view' ) === v ? 'true' : 'false' );
		} );
	}

	/* ── تراشه‌ها ── */
	function chipState( nav ) {
		var list = nav.querySelector( '.tp-news-chips__list' );
		var all  = list ? list.querySelectorAll( 'li' ) : [];
		if ( ! all.length ) {
			return;
		}
		var lr   = list.getBoundingClientRect();
		var over = list.scrollWidth > list.clientWidth + 1;
		var minL = Infinity, maxR = -Infinity;
		Array.prototype.forEach.call( all, function ( li ) {
			var r = li.getBoundingClientRect();
			minL = Math.min( minL, r.left );
			maxR = Math.max( maxR, r.right );
		} );
		var hasL = over && minL < lr.left - 1;
		var hasR = over && maxR > lr.right + 1;
		list.toggleAttribute( 'data-tp-l', hasL );
		list.toggleAttribute( 'data-tp-r', hasR );
		var rtl  = getComputedStyle( list ).direction === 'rtl';
		var prev = nav.querySelector( '[data-tp-chips="prev"]' );
		var next = nav.querySelector( '[data-tp-chips="next"]' );
		if ( prev && next ) {
			prev.hidden = next.hidden = ! over;
			/* «قبلی» سوی آغاز است: راست در راست‌به‌چپ */
			prev.disabled = ! ( rtl ? hasR : hasL );
			next.disabled = ! ( rtl ? hasL : hasR );
		}
	}

	function chipReveal( list ) {
		var cur = list.querySelector( '[aria-current]' );
		if ( ! cur ) {
			return;
		}
		var lr = list.getBoundingClientRect(), cr = cur.getBoundingClientRect(), pad = 28;
		if ( cr.left < lr.left + pad ) {
			list.scrollLeft -= lr.left + pad - cr.left;
		} else if ( cr.right > lr.right - pad ) {
			list.scrollLeft += cr.right - ( lr.right - pad );
		}
	}

	function initChips() {
		root.querySelectorAll( '[data-tp-news-chips]' ).forEach( function ( nav ) {
			var list = nav.querySelector( '.tp-news-chips__list' );
			if ( ! list || nav.hasAttribute( 'data-tp-ready' ) ) {
				return;
			}
			nav.setAttribute( 'data-tp-ready', '' );
			chipReveal( list );
			chipState( nav );
			list.addEventListener( 'scroll', function () { chipState( nav ); }, { passive: true } );
			nav.addEventListener( 'click', function ( e ) {
				var b = e.target.closest( '[data-tp-chips]' );
				if ( ! b ) {
					return;
				}
				var rtl  = getComputedStyle( list ).direction === 'rtl';
				var sign = b.getAttribute( 'data-tp-chips' ) === 'next' ? 1 : -1;
				list.scrollBy( { left: ( rtl ? -1 : 1 ) * sign * list.clientWidth * 0.8, behavior: reduced() ? 'auto' : 'smooth' } );
			} );
		} );
	}

	/* ── بی‌رفرش ── */
	function path( p ) {
		return p.replace( /\/page\/\d+\/?$/, '/' ).replace( /\/+$/, '' );
	}

	function same( url ) {
		return url.origin === window.location.origin && path( url.pathname ) === path( root.getAttribute( 'data-tp-news-base' ) || '' );
	}

	function formUrl( form ) {
		var u  = new URL( form.getAttribute( 'action' ) || window.location.href, window.location.href );
		var fd = new FormData( form );
		u.search = '';
		fd.forEach( function ( v, k ) {
			if ( k !== 'tp_partial' && String( v ).trim() !== '' ) {
				u.searchParams.set( k, String( v ).trim() );
			}
		} );
		return u;
	}

	/* مقدار فرم‌ها از نشانی — پس از «بازگشت» یا کلیک تراشه هم درست بماند */
	function syncForms( url ) {
		var p = url.searchParams;
		root.querySelectorAll( 'form[data-tp-news-filter]' ).forEach( function ( f ) {
			var hid = f.querySelector( 'input[type="hidden"][name="tp_topic"]' );
			if ( p.get( 'tp_topic' ) ) {
				if ( ! hid ) {
					hid = document.createElement( 'input' );
					hid.type = 'hidden';
					hid.name = 'tp_topic';
					f.insertBefore( hid, f.firstChild );
				}
				hid.value = p.get( 'tp_topic' );
			} else if ( hid ) {
				hid.parentNode.removeChild( hid );
			}
			Array.prototype.forEach.call( f.elements, function ( el ) {
				if ( ! el.name || el.type === 'hidden' || el.type === 'submit' || el.tagName === 'BUTTON' ) {
					return;
				}
				/* combobox مکان پس از هر بار خالی می‌ماند؛ مکان در ردیف پالایش‌های فعال است (P5-D557) */
				el.value = el.name === 'tp_loc' ? '' : ( p.get( el.name ) || '' );
				if ( el.tagName === 'SELECT' && el.selectedIndex < 0 ) {
					el.selectedIndex = 0;
				}
			} );
		} );
	}

	function busy( on ) {
		var res = root.querySelector( '[data-tp-news-swap="results"]' );
		if ( res ) {
			res.setAttribute( 'aria-busy', on ? 'true' : 'false' );
		}
	}

	function load( href, push, focus ) {
		var url  = new URL( href, window.location.href );
		var part = new URL( url.href );
		part.searchParams.set( 'tp_partial', '1' );
		if ( ctrl ) {
			ctrl.abort();
		}
		ctrl = typeof window.AbortController === 'function' ? new window.AbortController() : null;
		var mine = ctrl;
		busy( true );
		window.fetch( part.href, { credentials: 'same-origin', signal: mine ? mine.signal : undefined } )
			.then( function ( r ) {
				if ( ! r.ok ) {
					throw new Error( 'HTTP ' + r.status );
				}
				return r.text();
			} )
			.then( function ( html ) {
				if ( mine !== ctrl ) {
					return;   /* درخواست تازه‌تری در راه است */
				}
				var doc = new DOMParser().parseFromString( html, 'text/html' );
				if ( ! doc.querySelector( '[data-tp-news-swap="results"]' ) ) {
					throw new Error( 'no results region' );
				}
				doc.querySelectorAll( '[data-tp-news-swap]' ).forEach( function ( n ) {
					var old = root.querySelector( '[data-tp-news-swap="' + n.getAttribute( 'data-tp-news-swap' ) + '"]' );
					if ( old ) {
						old.parentNode.replaceChild( document.importNode( n, true ), old );
					}
				} );
				if ( push ) {
					window.history.pushState( { tpNews: 1 }, '', url.href );
				}
				syncForms( url );
				initChips();
				applyView();
				busy( false );   /* ناحیۀ تازه: پایان بارگیری صریح اعلام می‌شود */
				var res = root.querySelector( '[data-tp-news-swap="results"]' );
				var t   = res.getAttribute( 'data-tp-news-title' );
				if ( t ) {
					document.title = t;
				}
				if ( live ) {
					live.textContent = res.getAttribute( 'data-tp-news-msg' ) || '';
				}
				ctrl = null;
				var chip = typeof focus === 'string' ? root.querySelector( '[data-tp-news-swap="chips"] a[data-tp-topic="' + focus.replace( /["\\]/g, '' ) + '"]' ) : null;
				if ( chip ) {
					chip.focus( { preventScroll: true } );
				} else if ( focus !== false ) {
					var h = res.querySelector( '[data-tp-news-focus]' );
					if ( h ) {
						h.focus( { preventScroll: true } );
						if ( h.getBoundingClientRect().top < 0 ) {
							h.scrollIntoView( { block: 'start', behavior: reduced() ? 'auto' : 'smooth' } );
						}
					}
				}
			} )
			.catch( function ( e ) {
				if ( e && e.name === 'AbortError' ) {
					return;
				}
				busy( false );
				if ( live ) {
					live.textContent = root.getAttribute( 'data-tp-news-err' ) || '';
				}
				window.location.assign( url.href );   /* بازگشت به GET عادی */
			} );
	}

	function init() {
		root = document.querySelector( '.tp-news-page[data-tp-news-base]' );
		if ( ! root ) {
			return;
		}
		live = root.querySelector( '[data-tp-news-live]' );
		applyView();
		initChips();
		var t = null;
		window.addEventListener( 'resize', function () {
			window.clearTimeout( t );
			t = window.setTimeout( function () { root.querySelectorAll( '[data-tp-news-chips]' ).forEach( chipState ); }, 120 );
		} );

		root.addEventListener( 'click', function ( e ) {
			var b = e.target.closest( '[data-tp-view]' );
			if ( b ) {
				var box   = root.querySelector( '[data-tp-news-view]' );
				var items = root.querySelector( '[data-tp-news-items]' );
				if ( box && items ) {
					write( b.getAttribute( 'data-tp-view' ) === 'list' ? 'list' : 'grid' );
					applyView();
				}
				return;
			}
			if ( e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || ! window.fetch || ! window.history.pushState ) {
				return;
			}
			var a = e.target.closest( 'a[href]' );
			if ( ! a || ! ( a.hasAttribute( 'data-tp-news-nav' ) || a.closest( '[data-tp-news-nav]' ) ) || a.target === '_blank' ) {
				return;
			}
			var url = new URL( a.href, window.location.href );
			if ( ! same( url ) ) {
				return;
			}
			e.preventDefault();
			load( url.href, true, a.closest( '[data-tp-news-swap="chips"]' ) ? a.getAttribute( 'data-tp-topic' ) : true );
		} );

		root.addEventListener( 'submit', function ( e ) {
			var f = e.target;
			if ( ! f.hasAttribute( 'data-tp-news-filter' ) || ! window.fetch || ! window.history.pushState ) {
				return;
			}
			var url = formUrl( f );
			if ( ! same( url ) ) {
				return;
			}
			e.preventDefault();
			load( url.href, true, true );
		} );

		/* تغییر فهرست کشویی همان ارسال است (P5-D546)؛ اعلام aria-live اجباری است */
		root.addEventListener( 'change', function ( e ) {
			var el = e.target;
			if ( el.tagName !== 'SELECT' || ! el.form || ! el.form.hasAttribute( 'data-tp-news-filter' ) || ! window.fetch || ! window.history.pushState ) {
				return;
			}
			var url = formUrl( el.form );
			if ( same( url ) ) {
				load( url.href, true, false );
			}
		} );

		if ( window.history.replaceState ) {
			window.history.replaceState( { tpNews: 1 }, '', window.location.href );
		}
		window.addEventListener( 'popstate', function ( e ) {
			if ( e.state && e.state.tpNews ) {
				load( window.location.href, false, false );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
