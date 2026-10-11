/**
 * عکس پذیرش پایان فاز ۲ج — کشور، استان، شهر و یک نوع نقطه‌ای (بنای تاریخی)،
 * در سه حالت رنگ (روز · عصر · شب)، ۱۴۴۰ و ۳۹۰، فارسی و انگلیسی — کروم.
 *
 *   node tests/acceptance-shots.js
 *
 * نشانی‌ها از tests/phase2c-lang.php setup (ترجمۀ en موجود، یا موقت با درون‌ریزی
 * ترجمۀ ۲ج٫۹ و انتشار)؛ پست‌های موقت در پایان، حتی با خطا، پاک می‌شوند. حالت رنگ
 * با کلید کاربر (localStorage «tp-mode»، بارگذاری تازه برای هر حالت). پیش از عکس، صفحه تا ته
 * پیمایش می‌شود تا تصویرهای تنبل بار شوند، و کارت هوا تا پاسخ /weather صبر می‌کند.
 *
 * خروجی: tests/.screens/2c-accept/<نوع>-<fa|en>-<پهنا>-<حالت>.jpg (بیرون از git)
 * و index.html همان پوشه برای دیدن کنار هم.
 */

'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

const WS    = path.resolve( __dirname, '..' );
const PHP   = ENV.php;
const DIR   = path.join( WS, 'tests', '.screens', '2c-accept' );
const TYPES = [ 'tp_country', 'tp_province', 'tp_city', 'tp_monument' ];
const MODES = [ 'light', 'evening', 'dark' ];
const SIZES = [ [ 1440, 900, false ], [ 390, 844, true ] ];

const sleep = ms => new Promise( r => setTimeout( r, ms ) );
let fail = 0;

( async () => {

	fs.mkdirSync( DIR, { recursive: true } );
	const L = JSON.parse( execFileSync( PHP, [ path.join( __dirname, 'phase2c-lang.php' ), 'setup' ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'inherit' ] } ) );
	const browser = await chromium.launch();
	const rows = [];

	try {
		for ( const type of TYPES ) {
			const page4 = L.pages[ type ];
			if ( ! page4 ) { console.log( '  ❌ ' + type + ' — نشانی نیست' ); fail ++; continue; }
			for ( const lang of [ 'fa', 'en' ] ) {
				for ( const [ w, h, mobile ] of SIZES ) {
					const ctx  = await browser.newContext( mobile ? { viewport: { width: w, height: h }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } : { viewport: { width: w, height: h } } );   /* ۳۹۰ همان behaviour.js */
					const url = /^https?:/.test( page4[ lang ] ) ? page4[ lang ] : ENV.base + page4[ lang ];   /* setup نشانی نسبی می‌دهد */
					let res = null;
					/*
					 * هر حالت یک بارگذاری تازه با همان کلید کاربر (localStorage «tp-mode») —
					 * بازیابی پیش از نخستین رنگ‌آمیزی و کلید سربرگ همان حالت را نشان می‌دهند.
					 */
					for ( const mode of MODES ) {
						const page = await ctx.newPage();
						await page.addInitScript( m => { try { localStorage.setItem( 'tp-mode', m ); } catch ( e ) {} }, mode );
						res = await page.goto( url, { waitUntil: 'load', timeout: 120000 } );
						await page.waitForSelector( '.tp-wx__day:not(.tp-is-load)', { timeout: 20000 } ).catch( () => {} );
						/* تصویرهای تنبل */
						await page.evaluate( async () => {
							const step = Math.max( 400, window.innerHeight * 0.8 );
							for ( let y = 0; y < document.documentElement.scrollHeight; y += step ) {
								window.scrollTo( { top: y, behavior: 'instant' } );
								await new Promise( r => setTimeout( r, 120 ) );
							}
							window.scrollTo( { top: 0, behavior: 'instant' } );
						} );
						await sleep( 900 );
						const got  = await page.evaluate( () => document.documentElement.getAttribute( 'data-tp-mode' ) );
						const file = type.replace( 'tp_', '' ) + '-' + lang + '-' + w + '-' + mode + '.jpg';
						await page.screenshot( { path: path.join( DIR, file ), fullPage: true, type: 'jpeg', quality: 70 } );
						rows.push( { type, lang, w, mode, file, status: res ? res.status() : 0, url: page4[ lang ] } );
						if ( got !== mode ) { fail ++; console.log( '  ❌ حالت ' + mode + ' اعمال نشد (' + got + ') — ' + file ); }
						await page.close();
					}
					const ok = res && 200 === res.status();
					if ( ! ok ) { fail ++; }
					console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + type + ' · ' + lang + ' · ' + w + ' — ' + ( res ? res.status() : 'بی پاسخ' ) + ' · ' + page4[ lang ].replace( ENV.base, '' ) );
					await ctx.close();
				}
			}
		}
	} finally {
		await browser.close();
		const arg = Buffer.from( JSON.stringify( { made: L.made, run: L.run, floor: L.floor, post_floor: L.post_floor } ) ).toString( 'base64' );
		const left = JSON.parse( execFileSync( PHP, [ path.join( __dirname, 'phase2c-lang.php' ), 'cleanup', arg ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'inherit' ] } ) );
		console.log( '  پاک‌سازی ترجمه‌های موقت: ' + JSON.stringify( left ) );
	}

	/* برگۀ کنار هم */
	const cell = r => '<figure><img loading="lazy" src="' + r.file + '" alt=""><figcaption>' + r.mode + '</figcaption></figure>';
	let html = '<!doctype html><meta charset="utf-8"><title>2c acceptance</title><style>body{font:14px system-ui;margin:16px}h2{margin:24px 0 8px}div{display:flex;gap:8px;align-items:flex-start;overflow-x:auto}figure{margin:0}img{width:300px;border:1px solid #ccc}</style>';
	for ( const type of TYPES ) {
		for ( const lang of [ 'fa', 'en' ] ) {
			for ( const [ w ] of SIZES ) {
				const set = rows.filter( r => r.type === type && r.lang === lang && r.w === w );
				if ( set.length ) { html += '<h2>' + type + ' · ' + lang + ' · ' + w + '</h2><div>' + set.map( cell ).join( '' ) + '</div>'; }
			}
		}
	}
	fs.writeFileSync( path.join( DIR, 'index.html' ), html );
	console.log( '\n  عکس: ' + rows.length + ' · افتاده: ' + fail + ' · ' + DIR );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
