/**
 * عکس پذیرش دستهٔ ۱۲ اکتبر — گاوخونی · شهر اصفهان · استان اصفهان؛ ۱۴۴۰ و ۳۹۰؛ روز و شب.
 *
 *   node tests/batch-shots-2f.js
 *
 * هر ترکیب: صفحهٔ کامل (کارت اطلاعات جغرافیایی با مرکزیت، کاشی مسیر فقط نام، جعبهٔ کیفیت هوا، دربارهٔ، مسیر راهنما) و برای استان و شهر
 * عکس نقشهٔ کامل با نشانگر و خوشه. هوا از REST واقعی می‌آید و پیش از عکس صبر می‌شود. خروجی tests/.screens/2f-batch/ (بیرون از git).
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

const DIR   = path.join( __dirname, '.screens', '2f-batch' );
const PAGES = [ [ 'gavkhouni', '/water-body/gavkhouni-lake/', false ], [ 'city-isfahan', '/city/isfahan/', true ], [ 'province-isfahan', '/province/isfahan/', true ] ];
const SIZES = [ [ 1440, 900, false ], [ 390, 844, true ] ];
const sleep = ms => new Promise( r => setTimeout( r, ms ) );

( async () => {
	fs.rmSync( DIR, { recursive: true, force: true } );
	fs.mkdirSync( DIR, { recursive: true } );
	const browser = await chromium.launch();
	let fail = 0;
	try {
		for ( const [ name, url, withMap ] of PAGES ) {
			for ( const [ w, h, mobile ] of SIZES ) {
				const ctx = await browser.newContext( mobile ? { viewport: { width: w, height: h }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } : { viewport: { width: w, height: h } } );
				for ( const mode of [ 'light', 'dark' ] ) {
					const page = await ctx.newPage();
					await page.addInitScript( m => { try { localStorage.setItem( 'tp-mode', m ); } catch ( e ) {} }, mode );
					try {
						await page.goto( ENV.base + url, { waitUntil: 'load', timeout: 240000 } );
						await page.waitForSelector( '.tp-wx__day:not(.tp-is-load)', { timeout: 20000 } ).catch( () => {} );
						await page.waitForFunction( () => { const a = document.querySelector( '[data-tp-air]' ); return ! a || ! a.classList.contains( 'is-load' ); }, null, { timeout: 30000 } ).catch( () => {} );
						await page.evaluate( async () => { for ( let y = 0; y < document.documentElement.scrollHeight; y += 700 ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 100 ) ); } window.scrollTo( { top: 0, behavior: 'instant' } ); } );
						await sleep( 700 );
						const base = name + '-' + w + '-' + mode;
						await page.screenshot( { path: path.join( DIR, base + '.jpg' ), type: 'jpeg', quality: 70, fullPage: true } );
						const info = await page.evaluate( () => ( {
							about: ( document.querySelector( '#tp-overview h2' ) || {} ).textContent || '',
							crumb: [ ...document.querySelectorAll( '.tp-crumb a, .tp-crumb [aria-current]' ) ].map( e => e.textContent ).join( ' › ' ),
							geo: [ ...document.querySelectorAll( '#tp-geo a.tp-tile' ) ].map( a => a.textContent ).join( ' | ' ),
							air: ( document.querySelector( '[data-tp-air-idx]' ) || {} ).textContent + ' ' + ( document.querySelector( '[data-tp-air-cat]' ) || {} ).textContent,
							routes: [ ...document.querySelectorAll( '#tp-routes a.tp-tile' ) ].map( a => a.textContent ).join( ' | ' ),
						} ) );
						console.log( '  ✓ ' + base + ' — ' + JSON.stringify( info ) );

						if ( withMap && ! mobile ) {
							const open = page.locator( '[data-tp-map-open]' ).first();
							if ( await open.count() ) {
								await open.scrollIntoViewIfNeeded();
								await open.click();
								await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-full]' ); return b && b.tpMap && b.tpMap.getSource( 'tp-pts' ) && b.tpMap.isStyleLoaded(); }, null, { timeout: 90000 } ).catch( () => {} );
								await sleep( 2500 );
								await page.screenshot( { path: path.join( DIR, base + '-map.jpg' ), type: 'jpeg', quality: 80 } );
							}
						}
					} catch ( e ) { fail++; console.log( '  ❌ ' + name + '-' + w + '-' + mode + ': ' + e.message ); }
					await page.close();
				}
				await ctx.close();
			}
		}
	} finally { await browser.close(); }
	console.log( '\nعکس‌ها: ' + DIR + ' — افتاده: ' + fail );
	process.exit( fail ? 1 : 0 );
} )();
