/**
 * P5-D500 — سرریز افقی موبایل، ۳۹۰ و ۳۲۰ با isMobile، چهار صفحه و نمونهٔ نوع‌های تازه.
 *
 * شرط: سند سرریز ندارد (scrollWidth ≤ clientWidth)، صفحه به افق نمی‌رود، و هیچ عنصر برش‌نخوردهٔ `.tp-page` از پهنا بیرون نیست.
 * body.scrollWidth گزارش می‌شود، نه حکم: ریشه‌اش گزینهٔ پاورقی وودمارت است، بیرون از کد ما (P5-D500).
 * جهش: عنصر پهن درون صفحه و برداشتن برش ریشه هر دو باید دیده شوند.
 *
 *   node tests/overflow-h.js
 */
'use strict';

const { execFileSync } = require( 'child_process' );
const path = require( 'path' );
const { chromium } = require( './pw.js' );
const env = require( './env.js' );

const BASE = env.base;
const PAGES = { 'ایران': '/country/iran/', 'استان': '/province/isfahan/', 'شهر': '/city/isfahan/', 'نقش جهان': '/monument/naqsh-e-jahan-square/' };

const samples = JSON.parse( execFileSync( env.php, [ env.wpCli, '--path=' + env.wp, '--url=' + BASE, 'eval-file', path.join( __dirname, 'overflow-h-urls.php' ) ], { encoding: 'utf8' } ).trim() );
const WANT = [ 'tp_currency', 'tp_visa', 'tp_health_safety', 'tp_transport', 'tp_calendar', 'tp_festival', 'tp_protected_area' ];

const probe = () => {
	const de = document.documentElement, vw = de.clientWidth;
	const before = window.scrollX;
	window.scrollTo( { left: 200, top: 0, behavior: 'instant' } );
	const moved = window.scrollX; window.scrollTo( { left: before, top: 0, behavior: 'instant' } );
	const wide = [];
	for ( const e of document.querySelectorAll( '.tp-page *' ) ) {
		const r = e.getBoundingClientRect();
		if ( ! r.width || ( r.right <= vw + .5 && r.left >= -.5 ) ) { continue; }
		const s = getComputedStyle( e );
		if ( 'fixed' === s.position || 'none' === s.display ) { continue; }
		let clip = false;
		for ( let n = e.parentElement; n && n !== document.documentElement; n = n.parentElement ) {
			const o = getComputedStyle( n ).overflowX;
			if ( 'visible' !== o && n !== document.body ) { clip = true; break; }
		}
		if ( ! clip ) { wide.push( e.tagName + '.' + String( e.className ).slice( 0, 40 ) + ' r=' + Math.round( r.right ) ); }
	}
	return { deSW: de.scrollWidth, deCW: de.clientWidth, bodySW: document.body.scrollWidth, moved, wide: wide.slice( 0, 5 ) };
};
const verdict = r => r.deSW <= r.deCW && 0 === r.moved && 0 === r.wide.length;

( async () => {
	let pass = 0, fail = 0;
	const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( ok || ! note ? '' : '   ← ' + note ) ); };
	const missing = WANT.filter( k => ! samples[ k ] );
	t( 'نمونهٔ هر هفت نوع پیدا شد', ! missing.length, missing.join( ' ' ) );
	const list = { ...PAGES };
	for ( const k of WANT ) { if ( samples[ k ] ) { list[ k ] = samples[ k ]; } }

	const b = await chromium.launch();
	try {
		for ( const width of [ 390, 320 ] ) {
			const ctx = await b.newContext( { viewport: { width, height: 800 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } );
			const page = await ctx.newPage();
			for ( const [ name, url ] of Object.entries( list ) ) {
				const res = await page.goto( BASE + url, { waitUntil: 'load' } );
				const r = await page.evaluate( probe );
				t( `${width} · ${name}: بی سرریز افقی (سند ${r.deSW}/${r.deCW}، body ${r.bodySW})`, 200 === res.status() && verdict( r ), JSON.stringify( r ) );
			}
			/* جهش روی آخرین صفحهٔ ایران */
			await page.goto( BASE + PAGES['ایران'], { waitUntil: 'load' } );
			await page.evaluate( () => {
				const d = document.createElement( 'div' ); d.style.cssText = 'width:900px;height:10px'; d.className = 'tp-mut';
				document.querySelector( '.tp-page' ).appendChild( d );
			} );
			const m1 = await page.evaluate( probe );
			t( `${width} · جهش: عنصر پهن درون صفحه دیده می‌شود`, ! verdict( m1 ), JSON.stringify( m1 ) );
			const m2 = await page.evaluate( () => {
				document.documentElement.style.overflowX = 'visible'; document.body.style.overflowX = 'visible';
				const d = document.createElement( 'div' ); d.style.cssText = 'position:absolute;left:0;top:0;width:900px;height:10px';
				document.body.appendChild( d );
				return { deSW: document.documentElement.scrollWidth, deCW: document.documentElement.clientWidth };
			} );
			t( `${width} · جهش: بی برش ریشه سند سرریز می‌کند`, m2.deSW > m2.deCW, JSON.stringify( m2 ) );
			await ctx.close();
		}
	} finally {
		await b.close();
	}
	console.log( '─'.repeat( 78 ) );
	console.log( `  گذشته: ${pass}   افتاده: ${fail}` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
