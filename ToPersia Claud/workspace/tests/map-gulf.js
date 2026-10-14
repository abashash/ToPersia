/**
 * برچسب خلیج فارس — P5-D364  (فقط topersia.local)
 *
 *   node tests/map-gulf.js [--only=chromium|firefox]
 *
 * هر دو سبک، هر سه زبان: عارضهٔ دریا با کلید پایدار (name:en = Persian Gulf · name:fa · name:latin)، با داده‌ای که name و name:ar آن «الخليج العربي» است،
 *   fa ← خلیج فارس · en ← Persian Gulf · ar ← الخليج الفارسي؛ عارضهٔ دیگر (دریای عمان) دست‌نخورده با همان زبان.
 *   • آفلاین: نقشهٔ واقعی (PMTiles)؛ عبارت text-field هر لایهٔ نماد از خودِ نقشه خوانده و با ارزیاب کوچک روی ویژگی‌های ساختگی سنجیده می‌شود.
 *   • آنلاین: style آنلاین با route جایگزین (یک لایهٔ نماد)، تا همان مسیر localize اجرا شود، بی اینترنت.
 * ارزیاب فقط case · any · == · get · coalesce · literal را می‌شناسد؛ هر عبارت دیگر (متن اصلی لایه در basemaps) «<ORIG>» می‌شود: یعنی شاخهٔ غیرخلیج دست‌نخورده به آن می‌رسد.
 */
'use strict';

const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + ( note || '' ) ) ); };

const WANT = { fa: 'خلیج فارس', en: 'Persian Gulf', ar: 'الخليج الفارسي' };
const GULF = { name: 'الخليج العربي', 'name:fa': WANT.fa, 'name:en': 'Persian Gulf', 'name:ar': 'الخليج العربي' };
const OMAN = { name: 'Gulf of Oman', 'name:fa': 'دریای عمان', 'name:en': 'Gulf of Oman', 'name:ar': 'بحر عمان' };
const LATIN_ONLY = { name: 'x', 'name:latin': 'Persian Gulf' };   /* سبک آنلاین: فقط name:latin */

/* ارزیاب کوچک — درون صفحه. eval فقط روی همین ثابت خودِ آزمون (EVAL) در صفحهٔ آزمون است؛ هیچ ورودی بیرونی ندارد. */
const EVAL = `( function ( expr, props ) {
	function ev( e ) {
		if ( ! Array.isArray( e ) ) { return e; }
		switch ( e[ 0 ] ) {
			case 'get': return props[ e[ 1 ] ] === undefined ? null : props[ e[ 1 ] ];
			case '==': return ev( e[ 1 ] ) === ev( e[ 2 ] );
			case 'any': return e.slice( 1 ).some( x => ev( x ) === true );
			case 'coalesce': { for ( const x of e.slice( 1 ) ) { const v = ev( x ); if ( v !== null && v !== undefined && v !== '' ) { return v; } } return null; }
			case 'case': { for ( let i = 1; i + 1 < e.length; i += 2 ) { if ( ev( e[ i ] ) === true ) { return ev( e[ i + 1 ] ); } } return ev( e[ e.length - 1 ] ); }
			case 'literal': return e[ 1 ];
			case 'format': return e[ 1 ] && Array.isArray( e[ 1 ] ) ? ev( e[ 1 ] ) : e[ 1 ];
			default: return '<ORIG>';   /* عبارت اصلیِ لایه (basemaps) — فقط می‌خواهیم بدانیم شاخهٔ «غیر خلیج فارس» به آن می‌رسد */
		}
	}
	return ev( expr );
} )`;

const labelsOf = ( props ) => ( { props, ev: EVAL } );

async function offline( browser, lang ) {
	const ctx = await browser.newContext( { viewport: { width: 1200, height: 800 } } );
	await ctx.addInitScript( () => { try { sessionStorage.setItem( 'tp-map-online', '0' ); } catch ( e ) {} } );
	const page = await ctx.newPage();
	page.setDefaultTimeout( 240000 );
	await page.goto( ENV.base + ( { fa: '', en: '/en', ar: '/ar' }[ lang ] ) + '/city/isfahan/', { waitUntil: 'load' } );
	await page.evaluate( () => document.querySelector( '#tp-map' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
	await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-live]' ); return b && b.tpMap && b.tpMap.isStyleLoaded(); }, null, { timeout: 120000 } );
	const r = await page.evaluate( ( [ ev, gulf, oman ] ) => {
		const f = eval( ev );
		const map = document.querySelector( '[data-tp-map-live]' ).tpMap;
		const layers = map.getStyle().layers.filter( l => l.type === 'symbol' && l.layout && l.layout[ 'text-field' ] && /^water/.test( l.id ) );
		const ocean = layers.find( l => 'water_label_ocean' === l.id );
		return {
			count: layers.length,
			online: !! document.querySelector( '[data-tp-map-live]' ).tpOnline,
			gulf: layers.map( l => f( l.layout[ 'text-field' ], gulf ) ),
			oman: ocean ? f( ocean.layout[ 'text-field' ], oman ) : null,
		};
	}, [ EVAL, GULF, OMAN ] );
	await ctx.close();
	return r;
}

async function online( browser, lang ) {
	const ctx = await browser.newContext( { viewport: { width: 1200, height: 800 } } );
	const page = await ctx.newPage();
	page.setDefaultTimeout( 240000 );
	const stub = { version: 8, name: 'stub', sources: { ofm: { type: 'geojson', data: { type: 'FeatureCollection', features: [] } } },
		layers: [ { id: 'bg', type: 'background', paint: { 'background-color': '#eee' } }, { id: 'lbl', type: 'symbol', source: 'ofm', layout: { 'text-field': [ 'get', 'name' ] } } ] };
	await page.route( /tiles\.openfreemap\.org\/styles\//, r => r.fulfill( { status: 200, contentType: 'application/json', body: JSON.stringify( stub ) } ) );
	await page.route( /tiles\.openfreemap\.org\/(?!styles\/)/, r => r.abort() );
	await page.goto( ENV.base + ( { fa: '', en: '/en', ar: '/ar' }[ lang ] ) + '/city/isfahan/', { waitUntil: 'load' } );
	await page.evaluate( () => document.querySelector( '#tp-map' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
	await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-live]' ); return b && b.tpMap && b.tpMap.isStyleLoaded(); }, null, { timeout: 120000 } );
	const r = await page.evaluate( ( [ ev, gulf, oman, latin ] ) => {
		const f = eval( ev );
		const map = document.querySelector( '[data-tp-map-live]' ).tpMap;
		const l = map.getStyle().layers.find( x => 'lbl' === x.id );
		return { online: !! document.querySelector( '[data-tp-map-live]' ).tpOnline, gulf: f( l.layout[ 'text-field' ], gulf ), oman: f( l.layout[ 'text-field' ], oman ), latin: f( l.layout[ 'text-field' ], latin ) };
	}, [ EVAL, GULF, OMAN, LATIN_ONLY ] );
	await ctx.close();
	return r;
}

( async () => {
	console.log( 'START ' + new Date().toISOString() );
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ].filter( b => ! ARGS.only || ARGS.only === b[ 0 ] ) ) {
		const browser = await type.launch();
		console.log( `\n── ${ name } ──` );
		try {
			for ( const lang of [ 'fa', 'en', 'ar' ] ) {
				const o = await offline( browser, lang );
				check( ! o.online && o.count >= 2 && o.gulf.every( g => g === WANT[ lang ] ) && !! o.oman && o.oman !== WANT[ lang ],
					`${ name } آفلاین ${ lang }: هر ${ o.count } لایهٔ نماد، عارضهٔ خلیج فارس ← «${ WANT[ lang ] }»؛ دریای عمان دست‌نخورده «${ o.oman }»`, JSON.stringify( o ).slice( 0, 300 ) );
				const on = await online( browser, lang );
				check( on.online && on.gulf === WANT[ lang ] && on.latin === WANT[ lang ] && on.oman !== WANT[ lang ],
					`${ name } آنلاین ${ lang }: همین قاعده — name:en و name:latin ← «${ WANT[ lang ] }»؛ عارضهٔ دیگر «${ on.oman }»`, JSON.stringify( on ) );
			}
		} finally { await browser.close(); }
	}
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
