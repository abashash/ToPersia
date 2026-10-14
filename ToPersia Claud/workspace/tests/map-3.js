/**
 * نقشهٔ آنلاین با پشتیبان آفلاین، سبک‌ها — دستهٔ ۳ · P5-D353
 *
 *   node tests/map-3.js [--only=chromium|firefox]
 *
 *   • آنلاین اول: هنگام دیده‌شدن نقشه دقیقاً **یک** درخواست آزمایشی (style) به tiles.openfreemap.org؛ پیش از دیده‌شدن هیچ.
 *   • برگشت سریع (شبیه‌سازی): خطا · مهلت (> ۲٫۵ ثانیه) · پاسخ غیر ۲۰۰ ← فوراً آفلاین؛ دقیقاً یک درخواست آنلاین؛ بی تلاش دوباره؛ بی پیام خطا.
 *   • sessionStorage: پس از شکست، صفحهٔ دوم همان بازدید صفر درخواست آنلاین (مستقیم آفلاین)؛ بازدید تازه (زمینهٔ تازه) دوباره یک بار می‌سنجد.
 *   • سبک‌ها: پایه (آنلاین) · حمل‌ونقل و قطار (آفلاین؛ خط آهن برجسته) · ماهواره فقط با ارائه‌دهندهٔ پیکربندی‌شده؛ انتخاب در localStorage؛ هر سبک فقط هنگام انتخاب.
 *   • حالت شب: style تیرهٔ آنلاین. راست‌به‌چپ: برچسب از name:fa.
 */
'use strict';

const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );

const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const sleep = ms => new Promise( r => setTimeout( r, ms ) );
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + ( note || '' ) ) ); };

const ONLINE = /tiles\.openfreemap\.org|fonts\.openfreemap\.org/;

/* سرور آنلاین ساختگی — نتیجهٔ probe از شبکهٔ واقعی نیاید (probe ۲٫۵ ثانیه‌ای روی اتصال سرد گاهی رد می‌شد):
 *   موفق  ← هر style (روشن یا تیره) با پاسخ ۲۰۰ و یک لایهٔ نماد؛ هر درخواست دیگر به این میزبان قطع
 *   ناموفق ← مسیر «شکست» پایین‌تر با route خودش (خطا · مهلت · ۵۰۰) این را می‌پوشاند (آخرین route برنده است)
 * خودِ مهلت و منطق probe در نقشه دست‌نخورده است. */
const STUB = { version: 8, name: 'stub', sources: { ofm: { type: 'geojson', data: { type: 'FeatureCollection', features: [] } } },
	layers: [ { id: 'bg', type: 'background', paint: { 'background-color': '#eee' } }, { id: 'lbl', type: 'symbol', source: 'ofm', layout: { 'text-field': [ 'get', 'name' ] } } ] };

async function ctxPage( browser, mode ) {
	const ctx = await browser.newContext( { viewport: { width: 1440, height: 700 } } );
	const page = await ctx.newPage();
	await page.route( /tiles\.openfreemap\.org\/styles\//, r => r.fulfill( { status: 200, contentType: 'application/json', body: JSON.stringify( STUB ) } ) );
	await page.route( /(tiles|fonts)\.openfreemap\.org\/(?!styles\/)/, r => r.abort() );
	if ( mode ) { await page.addInitScript( m => { try { if ( ! localStorage.getItem( 'tp-mode' ) ) { localStorage.setItem( 'tp-mode', m ); } } catch ( e ) {} }, mode ); }
	const reqs = [];
	page.on( 'request', r => { if ( ONLINE.test( r.url() ) ) { reqs.push( r.url() ); } } );
	return { ctx, page, reqs };
}

const styleReqs = reqs => reqs.filter( u => /\/styles\//.test( u ) );
const open = async page => { await page.goto( ENV.base + '/city/isfahan/', { waitUntil: 'load', timeout: 240000 } ); };
const toCard = async page => { await page.evaluate( () => document.querySelector( '#tp-map' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) ); };
const live = async page => page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-live]' ); return b && b.tpMap && b.tpMap.isStyleLoaded(); }, null, { timeout: 60000 } );
const kind = page => page.evaluate( () => { const m = document.querySelector( '[data-tp-map-live]' ).tpMap; const s = m.getStyle().sources; return { src: Object.keys( s ), online: !! document.querySelector( '[data-tp-map-live]' ).tpOnline, ss: ( () => { try { return sessionStorage.getItem( 'tp-map-online' ); } catch ( e ) { return 'x'; } } )() }; } );

( async () => {
	console.log( 'START ' + new Date().toISOString() );
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ].filter( b => ! ARGS.only || ARGS.only === b[0] ) ) {
		const browser = await type.launch();
		console.log( `\n── ${ name } ──` );
		try {
			/* آنلاین اول + بی درخواست پیش از دید */
			let { ctx, page, reqs } = await ctxPage( browser );
			await open( page );
			await sleep( 1800 );
			check( 0 === reqs.length, `${ name }: پیش از دیده‌شدن نقشه هیچ درخواستی به ارائه‌دهندهٔ آنلاین نمی‌رود`, reqs.join( ' ' ) );
			await toCard( page );
			await live( page );
			let k = await kind( page );
			check( 1 === styleReqs( reqs ).length && k.online && ! k.src.includes( 'protomaps' ) && '1' === k.ss, `${ name }: آنلاین اول — یک درخواست آزمایشی (style)، نقشه از OpenFreeMap؛ sessionStorage=۱`, JSON.stringify( [ styleReqs( reqs ), k ] ) );
			const lab = await page.evaluate( () => { const l = document.querySelector( '[data-tp-map-live]' ).tpMap.getStyle().layers.filter( x => x.layout && x.layout[ 'text-field' ] ); return JSON.stringify( l[ 0 ].layout[ 'text-field' ] ); } );
			check( /name:fa/.test( lab ), `${ name }: برچسب نقشهٔ آنلاین از name:fa با برگشت به name`, lab );
			await ctx.close();

			/* شکست‌ها: خطا · مهلت · ۵۰۰ */
			for ( const [ label, handler ] of [
				[ 'خطای شبکه', r => r.abort() ],
				[ 'مهلت (پاسخ پس از ۴ ثانیه)', async r => { await sleep( 4000 ); await r.abort().catch( () => {} ); } ],
				[ 'پاسخ ۵۰۰', r => r.fulfill( { status: 500, body: 'x' } ) ],
			] ) {
				( { ctx, page, reqs } = await ctxPage( browser ) );
				await page.route( ONLINE, handler );
				await open( page );
				await toCard( page );
				const t0 = Date.now();
				await live( page );
				const dt = Date.now() - t0;
				k = await kind( page );
				check( 1 === styleReqs( reqs ).length && ! k.online && k.src.includes( 'protomaps' ) && '0' === k.ss && dt < 7000, `${ name } ${ label }: یک درخواست آنلاین، سپس فوری آفلاین (${ dt }ms)؛ بی تلاش دوباره؛ sessionStorage=۰`, JSON.stringify( [ styleReqs( reqs ).length, k, dt ] ) );
				const shown = await page.evaluate( () => ( document.querySelector( '[data-tp-map-live]' ).textContent || '' ).trim() );
				check( '' === shown || ! /خطا|error|failed/i.test( shown ), `${ name } ${ label }: بی پیام خطا برای کاربر`, shown.slice( 0, 60 ) );

				/* صفحهٔ دوم همان بازدید: صفر درخواست آنلاین */
				const before = reqs.length;
				await page.goto( ENV.base + '/province/isfahan/', { waitUntil: 'load', timeout: 240000 } );
				await page.evaluate( () => document.querySelector( '#tp-map' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
				await live( page );
				check( reqs.length === before && ! ( await kind( page ) ).online, `${ name } ${ label }: صفحهٔ دوم همان بازدید مستقیم آفلاین — صفر درخواست آنلاین تازه`, String( reqs.length - before ) );
				await ctx.close();
			}

			/* بازدید تازه دوباره یک بار می‌سنجد */
			( { ctx, page, reqs } = await ctxPage( browser ) );
			await open( page ); await toCard( page ); await live( page );
			check( 1 === styleReqs( reqs ).length, `${ name }: بازدید تازه (زمینهٔ تازه) دوباره دقیقاً یک بار می‌سنجد`, String( styleReqs( reqs ).length ) );
			await ctx.close();

			/* حالت شب: style تیره */
			( { ctx, page, reqs } = await ctxPage( browser, 'dark' ) );
			await open( page ); await toCard( page ); await live( page );
			check( styleReqs( reqs ).some( u => /\/styles\/dark/.test( u ) ), `${ name }: حالت شب ← style تیرهٔ آنلاین`, styleReqs( reqs ).join( ' ' ) );
			await ctx.close();

			/* انتخاب‌گر سبک در مودال */
			( { ctx, page, reqs } = await ctxPage( browser ) );
			await open( page );
			await page.evaluate( () => document.querySelector( '[data-tp-map-open]' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
			await page.click( '[data-tp-map-open]' );
			await page.waitForFunction( () => { const x = document.querySelector( '[data-tp-map-full]' ); return x && x.tpMap && x.tpMap.isStyleLoaded(); }, null, { timeout: 90000 } );
			const btns = await page.evaluate( () => [ ...document.querySelectorAll( '[data-tp-map-styles] button' ) ].map( b => b.dataset.style + ':' + b.getAttribute( 'aria-pressed' ) ) );
			check( 'base:true,transit:false' === btns.join(), `${ name }: انتخاب‌گر سبک — پایه، حمل‌ونقل و قطار (ماهواره بی ارائه‌دهندهٔ پیکربندی‌شده نیست)`, btns.join() );
			const beforeT = styleReqs( reqs ).length;
			await page.click( '[data-tp-map-styles] [data-style="transit"]' );
			await page.waitForFunction( () => { const m = document.querySelector( '[data-tp-map-full]' ).tpMap; return m.isStyleLoaded() && m.getLayer( 'roads_rail' ); }, null, { timeout: 60000 } );
			const tr = await page.evaluate( () => { const m = document.querySelector( '[data-tp-map-full]' ).tpMap; return { rail: m.getPaintProperty( 'roads_rail', 'line-color' ), road: m.getPaintProperty( 'roads_major', 'line-opacity' ), ls: ( () => { try { return localStorage.getItem( 'tp-map-style' ); } catch ( e ) { return 'x'; } } )(), online: document.querySelector( '[data-tp-map-full]' ).tpOnline } } );
			check( '#B9871A' === tr.rail && 0.3 === tr.road && 'transit' === tr.ls && ! tr.online, `${ name }: «حمل‌ونقل و قطار» آفلاین — خط آهن برجسته، جاده کم‌رنگ؛ انتخاب در localStorage`, JSON.stringify( tr ) );
			check( styleReqs( reqs ).length === beforeT, `${ name }: انتخاب سبک آفلاین درخواست آنلاین تازه نمی‌سازد`, '' );
			await page.click( '[data-tp-map-styles] [data-style="base"]' );
			await page.waitForFunction( () => { const m = document.querySelector( '[data-tp-map-full]' ).tpMap; return m.isStyleLoaded() && document.querySelector( '[data-tp-map-full]' ).tpOnline; }, null, { timeout: 60000 } );
			check( true, `${ name }: بازگشت به «پایه» ← دوباره آنلاین`, '' );
			await ctx.close();
		} finally { await browser.close(); }
	}
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
