/**
 * عکس پذیرش ۲و٫۲ (اصلاح ۱۲ اکتبر) — مسیر راهنما بی کشور، کاشی والد مکانی در «اطلاعات جغرافیایی»، باکس شهرهای مسیر، «دربارهٔ …».
 *
 *   node tests/relations-shots-2f.js
 *
 * صفحه‌ها: گاوخونی (آب) · شهر اصفهان · استان اصفهان · قلم‌زنی (صنایع دستی، بی زنجیرهٔ مکانی) · قنات مزدآباد (محوطه)؛
 * فارسی همه، انگلیسی استان و شهر (ترجمهٔ en موجود). ۱۴۴۰ و ۳۹۰ × روز و شب. هر ترکیب یک عکس بالای صفحه (مسیر راهنما + سربرگ + کاشی‌ها)
 * و برای ۱۴۴۰ و ۳۹۰ روزِ فارسی یک عکس تمام‌صفحه (ستون کنار). خروجی tests/.screens/2f-accept/ + index.html (بیرون از git).
 * فقط خواندن؛ هیچ داده‌ای نوشته نمی‌شود.
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

const DIR   = path.join( __dirname, '.screens', '2f-accept' );
const PAGES = [
	[ 'gavkhouni', 'fa', '/water-body/gavkhouni-lake/' ],
	[ 'city-isfahan', 'fa', '/city/isfahan/' ],
	[ 'silk-road', 'fa', '/?p=95' ],
	[ 'qanat-ancient-site', 'fa', '/?p=1954' ],
];
const SIZES = [ [ 1440, 900, false ], [ 390, 844, true ] ];
const MODES = [ 'light' ];
const sleep = ms => new Promise( r => setTimeout( r, ms ) );

( async () => {
	fs.mkdirSync( DIR, { recursive: true } );
	const browser = await chromium.launch();
	const rows = [];
	let fail = 0;
	try {
		for ( const [ name, lang, url ] of PAGES ) {
			for ( const [ w, h, mobile ] of SIZES ) {
				const ctx = await browser.newContext( mobile ? { viewport: { width: w, height: h }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } : { viewport: { width: w, height: h } } );
				for ( const mode of MODES ) {
					const page = await ctx.newPage();
					await page.addInitScript( m => { try { localStorage.setItem( 'tp-mode', m ); } catch ( e ) {} }, mode );
					try {
						await page.goto( ENV.base + url, { waitUntil: 'load', timeout: 180000 } );
						await page.waitForSelector( '.tp-wx__day:not(.tp-is-load)', { timeout: 15000 } ).catch( () => {} );
						await sleep( 700 );
						const base = name + '-' + lang + '-' + w + '-' + mode;
						await page.screenshot( { path: path.join( DIR, base + '-top.jpg' ), type: 'jpeg', quality: 80 } );
						const info = await page.evaluate( () => ( {
							tiles: [ ...document.querySelectorAll( '#tp-geo a.tp-tile' ) ].map( a => a.textContent ), about: ( document.querySelector( '#tp-overview h2' ) || {} ).textContent || '', route: [ ...document.querySelectorAll( '#tp-route-cities a.tp-tile' ) ].map( a => a.textContent ),
							crumb: [ ...document.querySelectorAll( '.tp-crumb a, .tp-crumb [aria-current]' ) ].map( e => e.textContent ).join(' › '),
							rail: [ ...document.querySelectorAll( '.tp-rail section[id^="tp-rel-"] h3' ) ].map( e => e.textContent ),
							mode: document.documentElement.getAttribute( 'data-tp-mode' ),
						} ) );
						if ( info.mode !== mode ) { fail++; console.log( '  ❌ ' + base + ': حالت ' + info.mode ); }
						let full = '';
						if ( true ) {
							await page.evaluate( async () => { for ( let y = 0; y < document.documentElement.scrollHeight; y += 700 ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 100 ) ); } window.scrollTo( { top: 0, behavior: 'instant' } ); } );
							await sleep( 600 );
							full = base + '-full.jpg';
							await page.screenshot( { path: path.join( DIR, full ), type: 'jpeg', quality: 70, fullPage: true } );
						}
						rows.push( { base, info, full } );
						console.log( '  ✓ ' + base + ' — مسیر: ' + info.crumb + ' · کاشی‌های جغرافیایی: ' + ( info.tiles.join( ' | ' ) || '(کارت یا والد ندارد)' ) + ' · ' + info.about + ( info.route.length ? ' · شهرهای مسیر: ' + info.route.join( ' ← ' ) : '' ) );
					} catch ( e ) { fail++; console.log( '  ❌ ' + name + '-' + lang + '-' + w + '-' + mode + ': ' + e.message ); }
					await page.close();
				}
				await ctx.close();
			}
		}
	} finally { await browser.close(); }

	fs.writeFileSync( path.join( DIR, 'index.html' ), '<!doctype html><meta charset="utf-8"><title>عکس پذیرش ۲و٫۲</title><body style="font:14px sans-serif;background:#eee">'
		+ rows.map( r => '<h4>' + r.base + ' — ' + r.info.tiles.join( ' | ' ) + '</h4><img src="' + r.base + '-top.jpg" style="max-width:48%;border:1px solid #888">' + ( r.full ? '<img src="' + r.full + '" style="max-width:48%;border:1px solid #888">' : '' ) ).join( '' ) );
	console.log( '\nعکس‌ها: ' + DIR + ' — افتاده: ' + fail );
	process.exit( fail ? 1 : 0 );
} )();
