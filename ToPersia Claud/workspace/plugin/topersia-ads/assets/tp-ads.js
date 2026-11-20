/* ToPersia Ads — جای واحد و شمارش نمایش (دستۀ 54 بند ۷ و ۹). بی JS بیرونی؛ کمتر از ۲ کیلوبایت gzip.
   جای واحد (بند ۷): هر جایگاه جز «زیر نوار ابزار هاب» بسته رندر می‌شود (tp-ads.css) و این‌جا فقط وقتی باز می‌شود که
   یکسره زیر صفحه‌نمایش جاری باشد — پس نخستین صفحه‌نمایش موبایل بی تبلیغ است و باز شدن هیچ CLS ندارد — و دست‌کم دو
   صفحه‌نمایش با پایین واحد باز پیشین فاصله داشته باشد. ستون کناری کنار متن (باریک‌تر از ۶۰٪ پهنا) از قاعدۀ فاصله بیرون است.
   هر جایگاه یک بار تصمیم می‌گیرد؛ بسته‌مانده `data-tp-ad-off` = screen · gap می‌گیرد.
   نمایش = دست‌کم ۵۰٪ واحد، ۱ ثانیه پیوسته. واحد بسته یا پنهان (نسخۀ دستگاه دیگر) هرگز تقاطع ندارد. هر واحد یک بار.
   ارسال گروهی با sendBeacon (text/plain) به REST بی‌نام با توکن روزانه؛ قطعۀ نتایجِ تازه با MutationObserver. */
( function () {
	'use strict';
	/* EDGE: حاشیۀ سایۀ کارت پسین (دستۀ 55 بند ۷) */
	var C = window.TopersiaAds || {}, GAP = 2, EDGE = 32, TOP = 'tp-ad-slot--hub-top', ON = 'tp-ad-on', FEED = 'tp-ad-slot--hub-infeed';
	var abs = function ( e ) {
		return e.getBoundingClientRect().top + window.scrollY;
	};
	/* جای جایگاه بسته = بالای نخستین هم‌زاد پسینی که جعبه دارد (همان خانه‌ای که واحد باز می‌گیرد) */
	var pos = function ( s ) {
		var n = s;
		if ( s.classList.contains( ON ) || s.classList.contains( TOP ) ) {
			return abs( s );
		}
		do {
			n = n.nextElementSibling;
		} while ( n && ! n.getClientRects().length );
		return n ? abs( n ) : s.parentElement.getBoundingClientRect().bottom + window.scrollY;
	};
	var place = function () {
		var vh = window.innerHeight, sy = window.scrollY, last = null;
		[].map.call( document.querySelectorAll( '.tp-ad-slot' ), function ( s ) {
			var rail = s.closest( '.tp-rail' );
			return { s: s, y: pos( s ), side: !! rail && rail.getBoundingClientRect().width < 0.6 * window.innerWidth };
		} ).sort( function ( a, b ) {
			return a.y - b.y;
		} ).forEach( function ( o ) {
			var s = o.s, y, why, r;
			if ( ! s.tpP && ! s.classList.contains( TOP ) && ! s.classList.contains( FEED ) ) {
				s.tpP = 1;
				y = pos( s );
				why = y < sy + vh + EDGE ? 'screen' : ( ! o.side && null !== last && y - last < GAP * vh ? 'gap' : '' );
				if ( why ) {
					s.setAttribute( 'data-tp-ad-off', why );
				} else {
					s.classList.add( ON );
				}
			}
			r = s.getBoundingClientRect();
			if ( ! o.side && r.height > 0 ) {
				last = r.bottom + sy;
			}
		} );
	};
	place();

	var count = !! C.u && 'IntersectionObserver' in window;
	var q = [], timer = 0;
	var send = function () {
		timer = 0;
		while ( q.length ) {
			var body = JSON.stringify( { t: C.t, a: q.splice( 0, 20 ) } );
			if ( ! ( navigator.sendBeacon && navigator.sendBeacon( C.u, body ) ) && window.fetch ) {
				fetch( C.u, { method: 'POST', body: body, keepalive: true, credentials: 'omit' } ).catch( function () {} );
			}
		}
	};
	var io = count && new IntersectionObserver( function ( list ) {
		list.forEach( function ( e ) {
			var el = e.target;
			if ( e.isIntersecting && e.intersectionRatio >= 0.5 ) {
				if ( ! el.tpT ) {
					el.tpT = setTimeout( function () {
						el.tpT = 0;
						if ( 'visible' !== document.visibilityState ) {
							return;
						}
						io.unobserve( el );
						q.push( el.getAttribute( 'data-tp-ad' ) );
						timer = timer || setTimeout( send, 2000 );
					}, 1000 );
				}
			} else if ( el.tpT ) {
				clearTimeout( el.tpT );
				el.tpT = 0;
			}
		} );
	}, { threshold: [ 0, 0.5 ] } );
	var scan = function () {
		if ( ! io ) {
			return;
		}
		document.querySelectorAll( '[data-tp-ad]' ).forEach( function ( el ) {
			if ( ! el.tpO ) {
				el.tpO = 1;
				io.observe( el );
			}
		} );
	};
	scan();
	var wait = 0;
	new MutationObserver( function () {
		clearTimeout( wait );
		wait = setTimeout( function () {
			place();
			scan();
		}, 200 );
	} ).observe( document.body, { childList: true, subtree: true } );
	var flush = function () {
		if ( q.length ) {
			clearTimeout( timer );
			send();
		}
	};
	document.addEventListener( 'visibilitychange', function () {
		if ( 'hidden' === document.visibilityState ) {
			flush();
		}
	} );
	window.addEventListener( 'pagehide', flush );
}() );
