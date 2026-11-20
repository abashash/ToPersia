/**
 * دستۀ 55-ب بند ۷ — عکس «پس» فقط fa (سند است، نه گیت). روش همان w55-local.js --only=shots (دستۀ 55 بند ۱۳).
 *
 *   TP_TIMEOUT_X=2 node tests/shots-55b.js
 *
 * ▸ هاب شهرها /city/ با پنل فیلتر باز (P5-D642: لایۀ شناور زیر «فیلتر») — ۳۹۰ و ۳۲۰؛ پنجرۀ دید، نه تمام صفحه.
 * ▸ پاورقی (رجیستری P5-D643) × سه حالت رنگ (localStorage tp-mode، همان topersia-display.js) — ۱۴۴۰ · ۷۶۸ · ۳۹۰؛
 *   آکاردئون ۳۹۰ بسته است، پس در ۳۹۰ یک عکس هم با همۀ ستون‌های منو باز (همان سرِ toggle که footer-gate می‌زند).
 * ← tests/.screens/55b/ (بیرون از git) با index.html. کد خروج: ۰ همه گرفته شد · ۱ عکسی کم است · ۲ محیط.
 */
'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) {
	console.error( 'فقط topersia.local' );
	process.exit( 2 );
}

const BASE   = ENV.base.replace( /\/$/, '' );
const X      = Math.max( 1, Number( process.env.TP_TIMEOUT_X || 1 ) );
const DIR    = path.join( __dirname, '.screens', '55b' );
const FOOT   = '[data-elementor-type="footer"]';
const HEADS  = FOOT + ' .wd-el-toggle:has(nav.tp-ft-menu) .wd-el-toggle-head';
const wait   = ms => new Promise( r => setTimeout( r, ms ) );
const center = ( page, sel ) => page.evaluate( s => { const e = document.querySelector( s ); if ( e ) { e.scrollIntoView( { block: 'center', behavior: 'instant' } ); } }, sel );
const isOpen = ( page, sel ) => page.evaluate( s => { const e = document.querySelector( s ); return !! e && ! e.hidden && 'none' !== getComputedStyle( e ).display; }, sel );

let pass = 0, fail = 0;
const t = ( label, ok ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label ); };

/* بارگذاری تنبل: یک گذر تا پایین و برگشت، سپس آرام‌شدن */
const settlePage = async page => {
	await page.evaluate( async () => { const H = document.documentElement.scrollHeight; for ( let y = 0; y < H; y += 700 ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 120 ) ); } window.scrollTo( { top: 0, behavior: 'instant' } ); } );
	await wait( 1200 * X );
};
const hideFixed = ( page, keep ) => page.evaluate( k => { const kp = k ? document.querySelector( k ) : null; document.querySelectorAll( 'body *' ).forEach( e => { if ( kp && kp.contains( e ) ) { return; } const p = getComputedStyle( e ).position; if ( 'fixed' === p || 'sticky' === p ) { e.style.setProperty( 'display', 'none', 'important' ); } } ); }, keep );

( async () => {
	fs.mkdirSync( DIR, { recursive: true } );
	console.log( 'START shots-55b ' + new Date().toISOString() + ' ← ' + DIR );
	const rows = [];
	const br   = await chromium.launch();
	const open = async ( w, h, ls ) => {
		const ctx = await br.newContext( { viewport: { width: w, height: h } } );
		await ctx.addInitScript( kv => { try { Object.keys( kv ).forEach( k => window.localStorage.setItem( k, kv[ k ] ) ); } catch ( e ) { /* بی localStorage */ } }, ls );
		const page = await ctx.newPage();
		page.setDefaultTimeout( 30000 * X );
		return { ctx, page };
	};
	try {
		/* هاب شهرها با پنل فیلتر باز */
		for ( const [ w, h ] of [ [ 390, 844 ], [ 320, 700 ] ] ) {
			const { ctx, page } = await open( w, h, {} );
			const st = ( await page.goto( BASE + '/city/', { waitUntil: 'load', timeout: 120000 * X } ) ).status();
			await settlePage( page );
			if ( ! ( await isOpen( page, '[data-tp-filter-panel]' ) ) ) {
				await center( page, '[data-tp-more-btn]' );
				await page.click( '[data-tp-more-btn]' );
				await wait( 600 * X );
			}
			const opened = await isOpen( page, '[data-tp-filter-panel]' );
			await page.evaluate( () => { const b = document.querySelector( '[data-tp-more-btn]' ); if ( b ) { b.scrollIntoView( { block: 'start', behavior: 'instant' } ); window.scrollBy( 0, -80 ); } } );
			await wait( 400 );
			const f = `city-filter-open-${ w }.png`;
			await page.screenshot( { path: path.join( DIR, f ) } );
			rows.push( { name: 'city-filter-open', w, url: BASE + '/city/', f, note: 'پنل فیلتر ' + ( opened ? 'باز' : 'بسته!' ) } );
			t( `هاب شهرها ${ w }: وضعیت ${ st }، پنل فیلتر باز`, 200 === st && opened );
			await ctx.close();
		}
		/* پاورقی × سه حالت رنگ */
		for ( const [ w, h ] of [ [ 1440, 900 ], [ 768, 1024 ], [ 390, 844 ] ] ) {
			for ( const mode of [ 'light', 'evening', 'dark' ] ) {
				const { ctx, page } = await open( w, h, { 'tp-mode': mode } );
				const st = ( await page.goto( BASE + '/', { waitUntil: 'load', timeout: 120000 * X } ) ).status();
				await settlePage( page );
				await page.evaluate( () => window.scrollTo( { top: document.documentElement.scrollHeight, behavior: 'instant' } ) );
				await wait( 1000 * X );
				const got = await page.evaluate( () => document.documentElement.getAttribute( 'data-tp-mode' ) );
				await hideFixed( page, FOOT );
				const foot = await page.$( FOOT );
				const f    = `footer-${ mode }-${ w }.png`;
				if ( foot ) {
					await foot.screenshot( { path: path.join( DIR, f ) } );
				}
				rows.push( { name: 'footer-' + mode, w, url: BASE + '/', f, note: 'data-tp-mode=' + got } );
				/* آکاردئون موبایل: هر ستون منو با کلیک روی سرش، مثل کاربر */
				let heads = 0;
				if ( foot && w < 768 ) {
					heads = await page.locator( HEADS ).count();
					for ( let i = 0; i < heads; i++ ) {
						const head = page.locator( HEADS ).nth( i );
						await head.evaluate( e => e.scrollIntoView( { behavior: 'instant', block: 'center' } ) );
						await head.click();
						await wait( 700 * X );
					}
					const f2 = `footer-${ mode }-${ w }-open.png`;
					await foot.screenshot( { path: path.join( DIR, f2 ) } );
					rows.push( { name: 'footer-' + mode + ' (ستون‌ها باز)', w, url: BASE + '/', f: f2, note: 'data-tp-mode=' + got + ' · ' + heads + ' ستون' } );
				}
				t( `پاورقی ${ mode } ${ w }: وضعیت ${ st }، حالت صفحه ${ got }` + ( w < 768 ? `، ستون‌های بازشده ${ heads }` : '' ), 200 === st && !! foot && mode === got && ( w >= 768 || heads > 0 ) );
				await ctx.close();
			}
		}
	} finally {
		await br.close();
	}
	const esc = s => String( s ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' );
	fs.writeFileSync( path.join( DIR, 'index.html' ), '<!doctype html><meta charset="utf-8"><title>دستۀ 55-ب — پس</title><body dir="rtl" style="font-family:sans-serif">'
		+ '<h1>دستۀ 55-ب — عکس «پس» (fa) · ' + esc( new Date().toISOString() ) + '</h1>'
		+ rows.map( r => '<h2>' + esc( r.name ) + ' · ' + r.w + '</h2><p dir="ltr">' + esc( r.url ) + ( r.note ? ' — ' + esc( r.note ) : '' ) + '</p><a href="' + r.f + '"><img src="' + r.f + '" style="max-width:' + Math.min( r.w, 720 ) + 'px;border:1px solid #888"></a>' ).join( '' ) + '</body>' );
	console.log( '  ← ' + path.join( DIR, 'index.html' ) );
	console.log( `END shots-55b — گذشته: ${ pass }   افتاده: ${ fail }` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
