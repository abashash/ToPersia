/**
 * ترتیب دیداری باکس‌های پنل چپ، دسکتاپ ۱۴۴۰ و موبایل ۳۹۰ — دستهٔ ۱ · P5-D347
 *
 *   node tests/panel-order-2i.js [--shots]
 *
 * برای هر نوع پستی که پست منتشرشدهٔ فارسی دارد (tests/panel-urls-2i.php؛ نوع‌های بی پست گزارش می‌شوند، نه سنجیده):
 *   دسکتاپ — باکس‌های ستون کناری از بالا به پایین = دکمه‌ها ← AI ← نقشه ← مسیریابی ← شهرهای مسیر ← بهترین زمان سفر ← هوا ← کیفیت هوا ←
 *            جغرافیا ← هم‌مرزها ← سایر کاشی‌ها ← روایت ← منابع؛ AI در ستون کناری است نه ستون اصلی.
 *   موبایل — ستون راست (AI بالا) کامل پیش از پنل چپ؛ پنل: نقشه ← مسیریابی ← شهرهای مسیر ← هوا ← کیفیت هوا ← بهترین زمان ← جغرافیا ←
 *            هم‌مرزها ← سایر کاشی‌ها ← روایت ← منابع؛ نظرها آخر. بی اسکرول افقی صفحه؛ بی تکرار شناسه.
 * با --shots عکس ۱۴۴۰ و ۳۹۰ استان تهران، شهر اصفهان، گاوخونی و جادهٔ ابریشم در tests/.screens/2i/ (بیرون از git).
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

const WS    = path.resolve( __dirname, '..' );
const SHOTS = process.argv.includes( '--shots' );
const DIR   = path.join( WS, 'tests', '.screens', '2i' );

const RAIL = [ 'tp-actions', 'tp-ai', 'tp-map', 'tp-routing', 'tp-route-cities', 'tp-season', 'tp-weather', 'tp-aqi', 'tp-geo', 'tp-neighbors', '@tiles', 'tp-audio', 'tp-sources' ];
const MOB  = [ 'tp-map', 'tp-routing', 'tp-route-cities', 'tp-weather', 'tp-aqi', 'tp-season', 'tp-geo', 'tp-neighbors', '@tiles', 'tp-audio', 'tp-sources' ];

const rank = ( id, list ) => { const i = list.indexOf( id ); return i < 0 ? list.indexOf( '@tiles' ) : i; };

async function sample( browser, url, mobile ) {
	const ctx = await browser.newContext( mobile ? { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } : { viewport: { width: 1440, height: 900 } } );
	const page = await ctx.newPage();
	await page.goto( ENV.base + url, { waitUntil: 'load', timeout: 240000 } );
	await page.evaluate( async () => { for ( let y = 0; y < document.documentElement.scrollHeight; y += 800 ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 80 ) ); } window.scrollTo( { top: 0, behavior: 'instant' } ); } );
	await page.waitForTimeout( 1200 );
	const r = await page.evaluate( () => {
		const ids = Array.from( document.querySelectorAll( '.tp-page [id^="tp-"]' ) ).map( e => e.id );
		const out = { dup: ids.filter( ( x, i ) => ids.indexOf( x ) !== i ), hscroll: document.documentElement.scrollWidth - innerWidth, rail: [], main: [] };
		const top = e => Math.round( e.getBoundingClientRect().top + scrollY );
		document.querySelectorAll( '.tp-rail > *' ).forEach( e => { if ( e.offsetHeight ) { out.rail.push( { id: e.id, top: top( e ), left: Math.round( e.getBoundingClientRect().left ) } ); } } );
		document.querySelectorAll( '.tp-main-col > *' ).forEach( e => { if ( e.offsetHeight ) { out.main.push( { id: e.id, top: top( e ) } ); } } );
		return out;
	} );
	await ctx.close();
	return r;
}

( async () => {
	const urls = JSON.parse( execFileSync( ENV.php, [ ENV.wpCli, '--path=' + ENV.wp, 'eval-file', 'tests/panel-urls-2i.php' ], { cwd: WS, encoding: 'utf8' } ) );
	const browser = await chromium.launch();
	let bad = 0, n = 0;
	const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + note ) ); };

	console.log( 'START ' + new Date().toISOString() + ' — ' + Object.keys( urls ).length + ' نوع با پست منتشرشدهٔ فارسی (بقیهٔ ۴۲ نوع پست ندارند: سنجیده نمی‌شوند)' );

	try {
		for ( const [ type, url ] of Object.entries( urls ) ) {

			const d = await sample( browser, url, false );
			const dr = d.rail.map( x => x.id || 'tp-actions' ).filter( x => x );
			const dsorted = d.rail.slice().sort( ( a, b ) => a.top - b.top ).map( x => x.id || 'tp-actions' );
			const want = dsorted.slice().sort( ( a, b ) => rank( a, RAIL ) - rank( b, RAIL ) );
			check( JSON.stringify( dsorted ) === JSON.stringify( want ) && ! d.main.some( x => 'tp-ai' === x.id ) && ( ! dr.includes( 'tp-ai' ) || dsorted.indexOf( 'tp-ai' ) <= 1 ),
				`${ type } ۱۴۴۰: پنل چپ ← ${ dsorted.join( ' · ' ) }؛ AI در پنل، نه ستون راست`, dsorted.join( ' ' ) + ' ≠ ' + want.join( ' ' ) );
			check( 0 === d.dup.length && ! dsorted.includes( 'tp-capital-of' ), `${ type } ۱۴۴۰: بی شناسهٔ تکراری و بی باکس «مرکزیت»`, d.dup.join( ',' ) );

			const m = await sample( browser, url, true );
			const all = m.main.concat( m.rail ).filter( x => x.id && 'tp-actions' !== x.id ).sort( ( a, b ) => a.top - b.top ).map( x => x.id );
			const first = all[0];
			const iMap = all.findIndex( x => MOB.includes( x ) || ! [ 'tp-ai', 'tp-overview', 'tp-gallery', 'tp-attractions', 'tp-places', 'tp-provinces', 'tp-districts', 'tp-culture', 'tp-news', 'tp-faq', 'tp-reviews' ].includes( x ) && ! x.startsWith( 'tp-prog' ) );
			const panel = all.filter( x => ! [ 'tp-ai', 'tp-overview', 'tp-gallery', 'tp-attractions', 'tp-places', 'tp-provinces', 'tp-districts', 'tp-culture', 'tp-news', 'tp-faq', 'tp-reviews' ].includes( x ) && ! x.startsWith( 'tp-prog' ) );
			const panelWant = panel.slice().sort( ( a, b ) => rank( a, MOB ) - rank( b, MOB ) );
			const lastIsReviews = ! all.includes( 'tp-reviews' ) || 'tp-reviews' === all[ all.length - 1 ];
			const rightFirst = iMap < 0 || all.slice( 0, iMap ).every( x => ! panel.includes( x ) );
			check( ( ! all.includes( 'tp-ai' ) || 'tp-ai' === first ) && JSON.stringify( panel ) === JSON.stringify( panelWant ) && lastIsReviews && rightFirst && m.hscroll <= 0 && 0 === m.dup.length,
				`${ type } ۳۹۰: AI بالا · ستون راست کامل · سپس پنل چپ ← ${ panel.join( ' · ' ) } · نظرها آخر · بی اسکرول افقی (${ m.hscroll })`, all.join( ' ' ) );
		}

		if ( SHOTS ) {
			fs.mkdirSync( DIR, { recursive: true } );
			for ( const [ name, url ] of [ [ 'tehran', '/province/tehran/' ], [ 'isfahan-city', '/city/isfahan/' ], [ 'gavkhouni', '/water-body/gavkhouni-lake/' ], [ 'silk-road', '/route/silk-road-in-iran/' ] ] ) {
				for ( const [ w, h, mob ] of [ [ 1440, 900, false ], [ 390, 844, true ] ] ) {
					const ctx = await browser.newContext( mob ? { viewport: { width: w, height: h }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } : { viewport: { width: w, height: h } } );
					const p = await ctx.newPage();
					await p.goto( ENV.base + url, { waitUntil: 'load', timeout: 240000 } );
					await p.evaluate( async () => { for ( let y = 0; y < document.documentElement.scrollHeight; y += 800 ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 80 ) ); } window.scrollTo( { top: 0, behavior: 'instant' } ); } );
					await p.waitForTimeout( 1500 );
					await p.screenshot( { path: path.join( DIR, `${ name }-${ w }.jpg` ), type: 'jpeg', quality: 62, fullPage: true } );
					await ctx.close();
				}
			}
		}
	} finally { await browser.close(); }

	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
