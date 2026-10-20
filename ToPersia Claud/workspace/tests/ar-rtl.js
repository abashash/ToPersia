/**
 * راست‌چینی عربی — قاعدهٔ دائمی (دستهٔ ۲۰ بند ۵)  (فقط topersia.local)
 *
 *   node tests/ar-rtl.js [--only=chromium|firefox]
 *
 * صفحه‌های استان · شهر · جاذبه در fa و ar (۱۴۴۰ و ۳۹۰)، همراه پنجره‌های باز (فهرست «همه …»، گالری) و پاپ‌آپ نقشه اگر نشانگری هست:
 *   ادعا  در /ar/ هیچ متن محتوا `direction:ltr` یا `text-align:left` (یا start در بافت ltr) ندارد — جز عدد، نشانی، کد و لاتین
 *         (برگی که متنش حرف عربی/فارسی ندارد) و بخش‌هایی که عمداً `dir="ltr"` دارند (`bdi`/`[dir=ltr]` برای نام لاتین).
 *   مقایسه  برای هر کلاس `tp-*` که در هر دو زبان هست، جهت و تراز رایانه‌ای fa و ar جفت می‌شود؛ هر ناهمخوانی فهرست می‌شود.
 *   فلش اسلایدر: «بعدی» در هر دو زبان سمت چپ (P5-D243).
 */
'use strict';

const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );
const { execFileSync } = require( 'child_process' );
const path = require( 'path' );
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const X = Number( process.env.TP_TIMEOUT_X || 1 );
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok || ! note ? '' : '   ← ' + note ) ); };
const URLS = JSON.parse( execFileSync( ENV.php || 'php', [ path.join( __dirname, 'local-urls.php' ) ], { encoding: 'utf8' } ) );

async function scan( page ) {
	return page.evaluate( () => {
		const RTL = /[؀-ۿݐ-ݿﭐ-﷿ﹰ-﻿]/;
		const roots = [ ...document.querySelectorAll( '.tp-page' ) ];
		const offend = [];
		const byClass = {};
		const seen = new Set();
		for ( const root of roots ) {
			for ( const el of root.querySelectorAll( '*' ) ) {
				if ( seen.has( el ) ) { continue; }
				seen.add( el );
				const cs = getComputedStyle( el );
				if ( 'none' === cs.display || 'hidden' === cs.visibility ) { continue; }
				const own = [ ...el.childNodes ].filter( t => 3 === t.nodeType ).map( t => t.textContent ).join( '' ).trim();
				const cls = [ ...el.classList ].find( c => /^tp-/.test( c ) );
				if ( cls ) { ( byClass[ cls ] = byClass[ cls ] || new Set() ).add( cs.direction + '/' + cs.textAlign ); }
				if ( ! own || ! RTL.test( own ) ) { continue; }                     /* عدد، نشانی، کد، لاتین ← بیرون */
				if ( el.closest( '[dir="ltr"]' ) && ! RTL.test( el.closest( '[dir="ltr"]' ).getAttribute( 'lang' ) || '' ) ) {
					/* `dir=ltr` عمدی است فقط اگر خودِ متن عربی/فارسی ندارد — پس این‌جا (متن RTL زیر ltr) شکست است */
				}
				const ltr = 'ltr' === cs.direction;
				const left = 'left' === cs.textAlign || ( 'start' === cs.textAlign && ltr ) || ( '-webkit-left' === cs.textAlign );
				if ( ltr || left ) {
					offend.push( ( cls || el.tagName.toLowerCase() ) + ' [' + cs.direction + '/' + cs.textAlign + '] «' + own.slice( 0, 40 ) + '»' );
				}
			}
		}
		const out = {};
		Object.keys( byClass ).forEach( k => { out[ k ] = [ ...byClass[ k ] ].sort().join( ' ' ); } );
		return { offend, byClass: out };
	} );
}

async function openAll( page ) {
	/* پنجره‌های فهرست و گالری را یکی‌یکی باز و اسکن کن */
	const results = [];
	const openers = await page.$$( '[data-tp-list-open], [data-tp-gallery-open]' );
	for ( const o of openers.slice( 0, 4 ) ) {
		try {
			await o.evaluate( e => e.scrollIntoView( { block: 'center', behavior: 'instant' } ) );
			await o.click( { timeout: 5000 } );
			await page.waitForTimeout( 500 );
			results.push( await scan( page ) );
			await page.keyboard.press( 'Escape' );
			await page.waitForTimeout( 300 );
		} catch ( e ) { /* بازکنندهٔ پنهان */ }
	}
	return results;
}

async function mapPop( page ) {
	/* نقشهٔ ستون کناری تعاملی نیست؛ پاپ‌آپ فقط در نقشهٔ کامل («باز کردن نقشهٔ کامل») — همان را باز می‌کنیم */
	try {
		const open = await page.$( '[data-tp-map-open]' );
		if ( ! open ) { return { found: false, why: 'بی دکمهٔ نقشهٔ کامل', offend: [] }; }
		await open.evaluate( e => e.scrollIntoView( { block: 'center', behavior: 'instant' } ) );
		await open.click();
		await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-full]' ); return b && b.tpMap && b.tpMap.isStyleLoaded() && b.tpMap.getLayer( 'tp-pts-icon' ); }, null, { timeout: 90000 * X } );
		await page.waitForTimeout( 2000 );
		const pt = await page.evaluate( () => {
			const b = document.querySelector( '[data-tp-map-full]' ), m = b.tpMap;
			const f = m.queryRenderedFeatures( { layers: [ 'tp-pts-icon' ] } )[ 0 ];
			if ( ! f ) { return null; }
			const p = m.project( f.geometry.coordinates ), r = m.getCanvas().getBoundingClientRect();
			return { x: r.left + p.x, y: r.top + p.y };
		} );
		if ( ! pt ) { return { found: false, why: 'هیچ نشانگر رندرشده‌ای در زبان ar (داده)', offend: [] }; }
		await page.mouse.click( pt.x, pt.y );
		await page.waitForSelector( '.maplibregl-popup .tp-mp', { timeout: 10000 * X } );
		return page.evaluate( () => {
			const RTL = /[\u0600-\u06FF]/, offend = [];
			for ( const el of document.querySelectorAll( '.maplibregl-popup .tp-mp, .maplibregl-popup .tp-mp *' ) ) {
				const own = [ ...el.childNodes ].filter( t => 3 === t.nodeType ).map( t => t.textContent ).join( '' ).trim();
				if ( ! own || ! RTL.test( own ) ) { continue; }
				const cs = getComputedStyle( el );
				if ( 'ltr' === cs.direction || 'left' === cs.textAlign ) { offend.push( el.className + ' [' + cs.direction + '/' + cs.textAlign + ']' ); }
			}
			return { found: true, offend };
		} );
	} catch ( e ) { return { found: false, why: String( e.message || e ).slice( 0, 80 ), offend: [] }; }
}
async function slider( page ) {
	return page.evaluate( () => {
		const next = document.querySelector( '.tp-slider-nav [data-dir="1"]' );
		const prev = document.querySelector( '.tp-slider-nav [data-dir="-1"]' );
		if ( ! next || ! prev ) { return null; }
		return { nextLeft: next.getBoundingClientRect().left < prev.getBoundingClientRect().left, next: next.textContent.trim(), prev: prev.textContent.trim() };
	} );
}

( async () => {
	console.log( 'START ' + new Date().toISOString() );
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ].filter( b => ! ARGS.only || ARGS.only === b[ 0 ] ) ) {
		const browser = await type.launch();
		console.log( `\n── ${ name } ──` );
		try {
			for ( const w of [ 1440, 390 ] ) {
				for ( const kind of Object.keys( URLS ) ) {
					if ( ! URLS[ kind ].ar || ! URLS[ kind ].fa ) { continue; }
					const res = {};
					for ( const lang of [ 'fa', 'ar' ] ) {
						const ctx = await browser.newContext( { viewport: { width: w, height: 900 } } );
						const page = await ctx.newPage();
						page.setDefaultTimeout( 240000 * X );
						await page.goto( URLS[ kind ][ lang ], { waitUntil: 'load' } );
						await page.waitForTimeout( 1500 );
						const main = await scan( page );
						const modals = await openAll( page );
						res[ lang ] = { main, modals, slider: await slider( page ), pop: 'ar' === lang ? await mapPop( page ) : null, dir: await page.evaluate( () => document.documentElement.dir || getComputedStyle( document.body ).direction ) };
						await ctx.close();
					}
					const ar = res.ar;
					const off = ar.main.offend.concat( ...ar.modals.map( m => m.offend ) );
					check( 'rtl' === ar.dir && 0 === off.length, `${ name } ${ w } ${ kind } /ar/: هیچ متن محتوای عربی با direction:ltr یا text-align:left (صفحه + ${ ar.modals.length } پنجره)`, [ ...new Set( off ) ].slice( 0, 6 ).join( ' · ' ) );
					/* ناهمخوانی وقتی شکست است که ar «کمتر راست‌به‌چپ» از fa باشد (ltr یا left جایی که fa rtl/right/start دارد).
					   عکسش (ar راست‌به‌چپ جایی که fa طراحیِ چپ‌به‌راستِ مرجع دارد — سطر مختصات P5-D278) فقط گزارش می‌شود. */
					const rtlish = v => v.split( ' ' ).every( x => /^rtl\//.test( x ) && ! /\/(left|-webkit-left)$/.test( x ) );
					const diff = [], note = [];
					for ( const k of Object.keys( ar.main.byClass ) ) {
						const fv = res.fa.main.byClass[ k ], av = ar.main.byClass[ k ];
						if ( ! fv || fv === av ) { continue; }
						( rtlish( fv ) && ! rtlish( av ) ? diff : note ).push( k + ' fa(' + fv + ') ar(' + av + ')' );
					}
					check( 0 === diff.length, `${ name } ${ w } ${ kind }: هیچ کلاس tp- در ar کمتر از fa راست‌به‌چپ نیست`, diff.slice( 0, 6 ).join( ' · ' ) );
					if ( note.length ) { console.log( '     · تفاوت عمدی (ar راست‌به‌چپ، fa طراحی مرجع): ' + note.slice( 0, 4 ).join( ' · ' ) ); }
					if ( ar.pop && ! ar.pop.found ) {
						console.log( '     · پاپ‌آپ نقشه سنجیده نشد: ' + ar.pop.why );
					} else if ( ar.pop ) {
						check( 0 === ar.pop.offend.length && ar.pop.found, `${ name } ${ w } ${ kind } /ar/: پاپ‌آپ نشانگر نقشه راست‌به‌چپ`, ar.pop.offend.slice( 0, 4 ).join( ' · ' ) );
					}
					if ( ! ar.slider || ! res.fa.slider ) {
						console.log( '     · فلش اسلایدر مقایسه نشد: ' + ( ar.slider ? '' : 'ar ' ) + ( res.fa.slider ? '' : 'fa ' ) + 'اسلایدر دوکارته ندارد (داده)' );
					}
					if ( ar.slider && res.fa.slider ) {
						check( ar.slider.nextLeft === res.fa.slider.nextLeft && ar.slider.next === res.fa.slider.next, `${ name } ${ w } ${ kind }: فلش اسلایدر ar همان fa («بعدی» ${ ar.slider.next } سمت ${ ar.slider.nextLeft ? 'چپ' : 'راست' })`, JSON.stringify( [ res.fa.slider, ar.slider ] ) );
					}
				}
			}
		} finally { await browser.close(); }
	}
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
