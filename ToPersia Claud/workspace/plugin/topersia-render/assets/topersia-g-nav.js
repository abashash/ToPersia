/**
 * منوی سربرگ — والدِ بی‌مقصدِ دارای زیرمنو «دکمه» است، نه پیوند «#» (دستۀ 55-ب بند ۴؛ includes/topersia-g-nav.php).
 * aria-expanded همیشه حال واقعی زیرمنو را می‌گوید:
 *   رومیزی، هاور (wd-event-hover)  نشانگر روی آیتم ← باز؛ Enter/فاصله زیرمنو را با wd-opened باز و بسته می‌کند.
 *   رومیزی، کلیک (wd-event-click)  دست‌گیرۀ خود وودمارت wd-opened را روی li می‌گذارد؛ Enter/فاصله همان کلیک است.
 *   موبایل                          بازکردن با پیکان وودمارت (.wd-nav-opener ← li.opener-page)؛ کلیک و Enter روی دکمه همان پیکان.
 *   موبایل، drilldown (سازندۀ سربرگ) همان پیکان ← .wd-sub-menu.wd-drilldown-show؛ بستن با «بازگشت» وودمارت (.wd-drilldown-back).
 *   هر تغییر کلاس درون منوی موبایل aria را هم‌گام می‌کند (MutationObserver) — «بازگشت» دکمۀ ما نیست.
 * Escape زیرمنو را می‌بندد و فوکوس را به دکمه برمی‌گرداند؛ فوکوس که از آیتم بیرون رفت، بازِ صفحه‌کلیدی بسته می‌شود.
 * گوش روی document — سربرگ چسبان وودمارت رونوشت همان منوست.
 */
( function () {
	'use strict';

	var BTN = 'a[data-tp-nav-btn]';

	function parts( btn ) {
		var li = btn && btn.parentElement;
		if ( ! li || 'LI' !== li.tagName ) {
			return null;
		}
		return {
			btn: btn,
			li: li,
			drop: li.querySelector( ':scope > .wd-dropdown' ),
			opener: li.querySelector( ':scope > .wd-nav-opener' ),
			back: li.querySelector( ':scope > .wd-sub-menu > .wd-drilldown-back > a' ),
		};
	}

	function isOpen( p ) {
		if ( p.opener ) {
			return p.li.classList.contains( 'opener-page' ) || !! p.li.querySelector( ':scope > .wd-sub-menu.wd-drilldown-show' );
		}
		return p.li.classList.contains( 'wd-opened' ) || !! ( p.drop && p.drop.classList.contains( 'wd-opened' ) ) ||
			( p.li.classList.contains( 'wd-event-hover' ) && p.li.matches( ':hover' ) );
	}

	function sync( p ) {
		p.btn.setAttribute( 'aria-expanded', isOpen( p ) ? 'true' : 'false' );
	}

	function syncAll() {
		document.querySelectorAll( BTN ).forEach( function ( b ) {
			var p = parts( b );
			if ( p ) {
				sync( p );
			}
		} );
	}

	/** نزدیک‌ترین دکمه‌ای که آیتمش el را دربر دارد. */
	function owner( el ) {
		var li = el && el.closest ? el.closest( 'li' ) : null;
		while ( li ) {
			var b = li.querySelector( ':scope > ' + BTN );
			if ( b ) {
				return parts( b );
			}
			li = li.parentElement ? li.parentElement.closest( 'li' ) : null;
		}
		return null;
	}

	function setDrop( p, open ) {
		if ( p.drop ) {
			p.drop.classList.toggle( 'wd-opened', open );
		}
	}

	document.addEventListener( 'click', function ( e ) {
		var b = e.target.closest ? e.target.closest( BTN ) : null;
		var p = parts( b );
		if ( ! p ) {
			return;
		}
		e.preventDefault();
		if ( p.opener ) {
			p.opener.click();
		}
		setTimeout( function () {
			sync( p );
		}, 0 );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		var b = e.target.closest ? e.target.closest( BTN ) : null;
		var p;
		if ( b && ( 'Enter' === e.key || ' ' === e.key ) ) {
			p = parts( b );
			if ( ! p ) {
				return;
			}
			e.preventDefault();
			if ( p.opener || p.li.classList.contains( 'wd-event-click' ) ) {
				b.click();
				return;
			}
			setDrop( p, ! ( p.drop && p.drop.classList.contains( 'wd-opened' ) ) );
			sync( p );
			return;
		}
		if ( 'Escape' !== e.key ) {
			return;
		}
		p = owner( e.target );
		if ( ! p || ! isOpen( p ) ) {
			return;
		}
		if ( p.back && p.li.querySelector( ':scope > .wd-sub-menu.wd-drilldown-show' ) ) {
			p.back.click();
		} else if ( p.opener && p.li.classList.contains( 'opener-page' ) ) {
			p.opener.click();
		} else if ( p.li.classList.contains( 'wd-opened' ) ) {
			p.btn.click();
		}
		setDrop( p, false );
		p.btn.focus();
		setTimeout( function () {
			sync( p );
		}, 0 );
	} );

	document.addEventListener( 'focusout', function ( e ) {
		var p = owner( e.target );
		if ( p && ! ( e.relatedTarget && p.li.contains( e.relatedTarget ) ) ) {
			setDrop( p, false );
			sync( p );
		}
	} );

	var queued = false;
	function onPointer( e ) {
		if ( queued || ! owner( e.target ) ) {
			return;
		}
		queued = true;
		requestAnimationFrame( function () {
			queued = false;
			syncAll();
		} );
	}
	document.addEventListener( 'mouseover', onPointer );
	document.addEventListener( 'mouseout', onPointer );

	var pending = false;
	function onMutate() {
		if ( pending ) {
			return;
		}
		pending = true;
		requestAnimationFrame( function () {
			pending = false;
			syncAll();
		} );
	}
	function watch() {
		if ( 'function' !== typeof MutationObserver ) {
			return;
		}
		document.querySelectorAll( 'ul.wd-nav-mobile' ).forEach( function ( ul ) {
			new MutationObserver( onMutate ).observe( ul, { subtree: true, attributes: true, attributeFilter: [ 'class' ] } );
		} );
	}
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', watch );
	} else {
		watch();
	}
}() );
