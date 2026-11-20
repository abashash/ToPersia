/* ToPersia Ads — بازچینی کارت میان‌فهرستی، P5-D641 (دستۀ 56-الف بند ۳). فقط صفحۀ هاب/فهرست.
   همان قاعدۀ topersia_ads_feed_positions(): خانه‌های TopersiaAdsFeed.cells بر پایۀ کارت‌های دیده‌شده (آگهی هم خانه است)،
   سقف شمار خانه‌ها، هرگز آخرین خانه، زیر TopersiaAdsFeed.min کارت هیچ. اعداد از topersia_ads_feed_rule() — قاعدۀ دوم ننویس.
   پس از پالایش (تعویض ناحیه)، «نمایش بیشتر»، مرتب‌سازی یا پنهان‌شدن کارت از نو. تعویض شبکه/فهرست و شمار ستون
   خانه را عوض نمی‌کند (دورۀ ۱۲ هم‌تراز ۲/۳/۴ ستون). آگهی بیرون از شمارش، JSON-LD و ناوبری صفحه‌کلید است: فقط .tp-news-card شمرده می‌شود. */
( function () {
	'use strict';
	var R = window.TopersiaAdsFeed || {}, CELLS = R.cells || [], MIN = R.min || 0, FEED = 'tp-ad-slot--hub-infeed';
	var shown = function ( e ) {
		return e.matches( '.tp-news-card' ) && ! e.hidden && 'none' !== getComputedStyle( e ).display;
	};
	var feed = function () {
		document.querySelectorAll( '[data-tp-news-items]' ).forEach( function ( box ) {
			var kids  = [].slice.call( box.children );
			var cards = kids.filter( shown );
			kids.filter( function ( e ) {
				return e.classList.contains( FEED );
			} ).forEach( function ( s, m ) {
				var before = cards.length >= MIN && m < CELLS.length ? CELLS[ m ] - 1 - m : -1;
				var at     = before >= 0 && before < cards.length ? cards[ before ] : null;
				s.hidden = ! at;
				if ( at && s.nextElementSibling !== at ) {
					box.insertBefore( s, at );
				}
			} );
		} );
	};
	feed();
	var wait = 0;
	new MutationObserver( function () {
		clearTimeout( wait );
		wait = setTimeout( feed, 100 );
	} ).observe( document.body, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'hidden', 'class' ] } );
}() );
