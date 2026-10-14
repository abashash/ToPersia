/**
 * منوی اصلی بی مگامنو — P5-D362  (فقط topersia.local)
 *
 *   node tests/menu-shots.js
 *
 * سربرگ و منوی «مقاصد و جغرافیا» در fa و en، ۱۴۴۰ و ۳۹۰، کروم: عکس در tests/.screens/megamenu/.
 * سنجش: کشوی عادی (نه مگامنو): در ۱۴۴۰ با hover چند پیوند زیرمنو دیده می‌شود و هیچ تصویر بیرونی، هیچ `.wd-dropdown-menu .wd-sub-menu` خالی؛
 * در ۳۹۰ پس از باز کردن منو همان مورد باز می‌شود؛ بی سرریز افقی؛ هیچ درخواست به picsum.
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

const SHOT = path.join( __dirname, '.screens', 'megamenu' );
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + ( note || '' ) ) ); };

( async () => {
	console.log( 'START ' + new Date().toISOString() );
	fs.mkdirSync( SHOT, { recursive: true } );
	const browser = await chromium.launch();
	try {
		for ( const lang of [ 'fa', 'en' ] ) {
			for ( const [ w, h ] of [ [ 1440, 900 ], [ 390, 844 ] ] ) {
				const ctx = await browser.newContext( { viewport: { width: w, height: h } } );
				const page = await ctx.newPage();
				page.setDefaultTimeout( 240000 );
				const ext = [];
				page.on( 'request', r => { if ( /picsum\.photos/.test( r.url() ) ) { ext.push( r.url() ); } } );
				await page.goto( ENV.base + ( 'en' === lang ? '/en/province/isfahan/' : '/province/isfahan/' ), { waitUntil: 'load' } );
				const tag = `${ lang }-${ w }`;

				if ( w > 800 ) {
					const item = page.locator( '#menu-main-menu-1 > li, .wd-nav-main > li' ).first();
					await item.hover();
					await page.waitForTimeout( 900 );
				} else {
					const burger = page.locator( '.wd-tools-element.wd-header-mobile-nav a, .wd-header-mobile-nav a' ).first();
					await burger.click();
					await page.waitForTimeout( 900 );
				}
				const r = await page.evaluate( () => {
					const links = [ ...document.querySelectorAll( '.wd-sub-menu a, .sub-sub-menu a' ) ].filter( a => a.offsetParent !== null );
					return {
						links: links.length,
						imgs: [ ...document.querySelectorAll( '.wd-dropdown-menu img, .wd-sub-menu img, .mobile-nav img' ) ].length,
						mega: !! document.querySelector( '.wd-dropdown-menu .wd-nav-mega, .menu-mega-dropdown .container' ),
						overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
					};
				} );
				await page.screenshot( { path: path.join( SHOT, `${ tag }.png` ) } );
				check( 0 === ext.length && ! r.overflow, `${ lang } @${ w }: بی درخواست picsum، بی سرریز افقی؛ زیرمنو: ${ r.links } پیوند، ${ r.imgs } تصویر، مگامنو: ${ r.mega }`, JSON.stringify( [ ext, r ] ) );
				await ctx.close();
			}
		}
	} finally { await browser.close(); }
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
