/**
 * پرسش‌های HTTP/مرورگر LQ-PERF-01 · 06 · 07 و LQ-SCH-01 · 02 (و canonical) — فقط خواندنی  (فقط topersia.local)
 *
 *   node tests/lq-http.js
 *
 * LQ-PERF-01  فهرست link rel=stylesheet و script src صفحهٔ استان و شهر (کروم، مهمان)
 * LQ-PERF-06  جای کارت نقشه در ۳۹۰×۸۴۴: top کارت در برابر innerHeight (و فاصله از لبهٔ ۲۰۰px)
 * LQ-PERF-07  شمار عنصر DOM در ۱۴۴۰ و ۳۹۰
 * LQ-SCH-01   شمار نویسهٔ عربی‌خط در JSON-LD صفحهٔ استان در fa · en · ar
 * LQ-SCH-02   hreflang و canonical هر سه زبان
 */
'use strict';

const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );
const ARABIC = /[؀-ۿݐ-ݿﭐ-﷿ﹰ-﻿]/g;

( async () => {
	const browser = await chromium.launch();
	const out = [];
	const page = async ( vp ) => { const c = await browser.newContext( { viewport: vp } ); const p = await c.newPage(); p.setDefaultTimeout( 240000 ); return { c, p }; };
	try {
		/* PERF-01 · 06 · 07 */
		for ( const [ label, path ] of [ [ 'استان', '/province/isfahan/' ], [ 'شهر', '/city/isfahan/' ] ] ) {
			for ( const [ vlabel, vp ] of [ [ 'دسکتاپ', { width: 1440, height: 900 } ], [ 'موبایل', { width: 390, height: 844 } ] ] ) {
				const { c, p } = await page( vp );
				const reqs = [];
				p.on( 'request', r => { if ( /\.(css|js)(\?|$)/.test( r.url() ) && /topersia\.local/.test( r.url() ) ) { reqs.push( r.url().replace( /^https?:\/\/[^/]+/, '' ).replace( /\?.*$/, '' ) ); } } );
				await p.goto( ENV.base + path, { waitUntil: 'load' } );
				await p.waitForTimeout( 1500 );
				const r = await p.evaluate( () => {
					const m = document.querySelector( '[data-tp-map]' ) || document.querySelector( '#tp-map' );
					const b = m ? m.getBoundingClientRect() : null;
					return { dom: document.querySelectorAll( '*' ).length, mapTop: b ? Math.round( b.top ) : null, ih: innerHeight, links: [ ...document.querySelectorAll( 'link[rel=stylesheet]' ) ].map( l => l.href.replace( /^https?:\/\/[^/]+/, '' ).replace( /\?.*$/, '' ) ), scripts: [ ...document.querySelectorAll( 'script[src]' ) ].map( s => ( s.defer ? '[defer] ' : s.async ? '[async] ' : '' ) + s.src.replace( /^https?:\/\/[^/]+/, '' ).replace( /\?.*$/, '' ) + ( s.closest( 'head' ) ? ' (head)' : '' ) ) };
				} );
				out.push( `LQ-PERF-07 | ${ label } ${ vlabel }: DOM = ${ r.dom } عنصر` );
				if ( 'موبایل' === vlabel ) { out.push( `LQ-PERF-06 | ${ label }: کارت نقشه top=${ r.mapTop }px، innerHeight=${ r.ih }px ← ${ null === r.mapTop ? 'کارت نیست' : r.mapTop <= r.ih + 200 ? 'درون اولین دید یا ۲۰۰px (IO در بار نخست سوار می‌کند)' : 'بیرون از ۲۰۰px (پس از اسکرول سوار می‌شود)' }` ); }
				if ( 'دسکتاپ' === vlabel ) {
					const host = ( u ) => u.replace( /^\/assets\/(plugins|themes)\//, '$1/' );
					out.push( `LQ-PERF-01 | ${ label }: ${ r.links.length } شیوه‌نامه · ${ r.scripts.length } اسکریپت‌ با src\n   CSS: ${ r.links.map( host ).join( ' · ' ) }\n   JS: ${ r.scripts.map( host ).join( ' · ' ) }` );
				}
				await c.close();
			}
		}

		/* SCH-01 · 02 */
		for ( const [ lang, pre ] of [ [ 'fa', '' ], [ 'en', '/en' ], [ 'ar', '/ar' ] ] ) {
			const { c, p } = await page( { width: 1440, height: 900 } );
			await p.goto( ENV.base + pre + '/province/isfahan/', { waitUntil: 'load' } );
			const r = await p.evaluate( () => ( {
				ld: [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].map( s => s.textContent ).join( '\n' ),
				hl: [ ...document.querySelectorAll( 'link[rel=alternate][hreflang]' ) ].map( l => l.getAttribute( 'hreflang' ) + '=' + l.getAttribute( 'href' ).replace( /^https?:\/\/[^/]+/, '' ) ),
				can: ( document.querySelector( 'link[rel=canonical]' ) || {} ).href || '(none)',
			} ) );
			const n = ( r.ld.match( ARABIC ) || [] ).length;
			out.push( `LQ-SCH-01 | ${ lang }: نویسهٔ عربی‌خط در JSON-LD = ${ n } (${ r.ld.length } نویسه کل)` );
			out.push( `LQ-SCH-02 | ${ lang }: hreflang = ${ r.hl.length ? r.hl.join( ' · ' ) : '(هیچ)' } · canonical = ${ r.can.replace( /^https?:\/\/[^/]+/, '' ) }` );
			await c.close();
		}
	} finally { await browser.close(); }
	console.log( out.join( '\n' ) );
} )();
