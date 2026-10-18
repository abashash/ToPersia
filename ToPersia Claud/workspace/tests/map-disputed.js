/**
 * مرز disputed پنهان — P5-D365  (فقط topersia.local)
 *
 *   node tests/map-disputed.js [--only=chromium|firefox] [--shots=1]
 *
 *   • آفلاین (PMTiles): داده کنار ابوموسی و تنب‌ها خط `disputed` دارد (گواه مثبت)، ولی هیچ خط disputed رندر نمی‌شود؛ فیلتر هر لایهٔ boundaries «بی disputed» است
 *   • آنلاین (style ساختگی با لایه‌های boundary_2 · boundary_3 · boundary_disputed از OpenFreeMap): لایهٔ ویژهٔ disputed پنهان، دو لایهٔ دیگر دست‌نخورده
 *   • --shots: عکس ابوموسی، تنب بزرگ و تنب کوچک در fa · en · ar (tests/.screens/disputed/)
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + ( note || '' ) ) ); };
const BASE = { fa: '', en: '/en', ar: '/ar' };
const SPOTS = { 'abu-musa': [ 55.03, 25.87, 11 ], 'greater-tunb': [ 55.30, 26.26, 11 ], 'lesser-tunb': [ 55.15, 26.24, 11 ] };

const STUB = { version: 8, name: 'stub', sources: { ofm: { type: 'geojson', data: { type: 'FeatureCollection', features: [] } } }, layers: [
	{ id: 'bg', type: 'background', paint: { 'background-color': '#eee' } },
	{ id: 'boundary_3', type: 'line', source: 'ofm', 'source-layer': 'boundary', filter: [ 'all', [ '>=', [ 'get', 'admin_level' ], 3 ], [ '<=', [ 'get', 'admin_level' ], 6 ], [ '!=', [ 'get', 'maritime' ], 1 ], [ '!=', [ 'get', 'disputed' ], 1 ], [ '!', [ 'has', 'claimed_by' ] ] ] },
	{ id: 'boundary_2', type: 'line', source: 'ofm', 'source-layer': 'boundary', filter: [ 'all', [ '==', [ 'get', 'admin_level' ], 2 ], [ '!=', [ 'get', 'maritime' ], 1 ], [ '!=', [ 'get', 'disputed' ], 1 ], [ '!', [ 'has', 'claimed_by' ] ] ] },
	{ id: 'boundary_disputed', type: 'line', source: 'ofm', 'source-layer': 'boundary', filter: [ 'all', [ '!=', [ 'get', 'maritime' ], 1 ], [ '==', [ 'get', 'disputed' ], 1 ] ] },
] };

async function live( page ) {
	await page.evaluate( () => document.querySelector( '#tp-map' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
	await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-live]' ); return b && b.tpMap && b.tpMap.isStyleLoaded(); }, null, { timeout: 120000 } );
}

( async () => {
	console.log( 'START ' + new Date().toISOString() );
	const shots = path.join( __dirname, '.screens', 'disputed' );
	if ( ARGS.shots ) { fs.mkdirSync( shots, { recursive: true } ); }
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ].filter( b => ! ARGS.only || ARGS.only === b[ 0 ] ) ) {
		const browser = await type.launch();
		console.log( '\n── ' + name + ' ──' );
		try {
			/* آفلاین */
			const ctx = await browser.newContext( { viewport: { width: 1000, height: 760 } } );
			await ctx.addInitScript( () => { try { sessionStorage.setItem( 'tp-map-online', '0' ); } catch ( e ) {} } );
			const page = await ctx.newPage();
			page.setDefaultTimeout( 240000 );
			await page.goto( ENV.base + '/city/isfahan/', { waitUntil: 'load' } );
			await live( page );
			const r = await page.evaluate( async () => {
				const map = document.querySelector( '[data-tp-map-live]' ).tpMap;
				const src = Object.keys( map.getStyle().sources ).find( k => map.getStyle().sources[ k ].type === 'vector' );
				const layers = map.getStyle().layers.filter( l => 'boundaries' === l[ 'source-layer' ] );
				const idle = () => new Promise( r => { const t = setTimeout( r, 20000 ); map.once( 'idle', () => { clearTimeout( t ); r(); } ); } );
				const out = { layers: layers.length, filtered: layers.every( l => -1 !== JSON.stringify( l.filter ).indexOf( '"disputed"' ) ), srcDisputed: 0, renderedDisputed: 0, renderedAny: 0 };
				for ( const c of [ [ 55.03, 25.87 ], [ 55.30, 26.26 ], [ 55.15, 26.24 ] ] ) {
					map.jumpTo( { center: c, zoom: 9 } ); await idle(); await new Promise( r => setTimeout( r, 700 ) );
					out.srcDisputed += map.querySourceFeatures( src, { sourceLayer: 'boundaries' } ).filter( f => f.properties && f.properties.disputed ).length;
					const rf = map.queryRenderedFeatures( { layers: layers.map( l => l.id ) } );
					out.renderedAny += rf.length; out.renderedDisputed += rf.filter( f => f.properties && f.properties.disputed ).length;
				}
				return out;
			} );
			check( r.layers >= 1 && r.filtered && r.srcDisputed > 0 && 0 === r.renderedDisputed, `${ name } آفلاین: داده خط disputed دارد (${ r.srcDisputed } ویژگی؛ گواه مثبت) ولی هیچ‌کدام رندر نمی‌شود؛ فیلتر همهٔ ${ r.layers } لایهٔ boundaries «بی disputed»؛ خطوط دیگر رندر می‌شوند (${ r.renderedAny })`, JSON.stringify( r ) );
			await ctx.close();

			/* عکس‌ها */
			if ( ARGS.shots && 'chromium' === name ) {
				for ( const lang of [ 'fa', 'en', 'ar' ] ) {
					const c2 = await browser.newContext( { viewport: { width: 700, height: 760 } } );
					await c2.addInitScript( () => { try { sessionStorage.setItem( 'tp-map-online', '0' ); } catch ( e ) {} } );
					const p2 = await c2.newPage(); p2.setDefaultTimeout( 240000 );
					await p2.goto( ENV.base + BASE[ lang ] + '/city/isfahan/', { waitUntil: 'load' } );
					await live( p2 );
					await p2.waitForTimeout( 5000 );   /* نقشهٔ کارت پس از بار هنوز به نشانگر می‌چرخد؛ نخست آرام می‌گیرد */
					for ( const [ key, [ lon, lat, z ] ] of Object.entries( SPOTS ) ) {
						await p2.evaluate( async ( [ lon, lat, z ] ) => { const m = document.querySelector( '[data-tp-map-live]' ).tpMap; m.jumpTo( { center: [ lon, lat ], zoom: z - 1 } ); await new Promise( r => { const t = setTimeout( r, 20000 ); m.once( 'idle', () => { clearTimeout( t ); r(); } ); } ); await new Promise( r => setTimeout( r, 2500 ) ); }, [ lon, lat, z ] );
						await p2.locator( '#tp-map' ).screenshot( { path: path.join( shots, `${ key }-${ lang }.png` ) } );
					}
					await c2.close();
				}
			}

			/* آنلاین */
			const c3 = await browser.newContext( { viewport: { width: 1000, height: 760 } } );
			const p3 = await c3.newPage();
			p3.setDefaultTimeout( 240000 );
			await p3.route( /tiles\.openfreemap\.org\/styles\//, rt => rt.fulfill( { status: 200, contentType: 'application/json', body: JSON.stringify( STUB ) } ) );
			await p3.route( /(tiles|fonts)\.openfreemap\.org\/(?!styles\/)/, rt => rt.abort() );
			await p3.goto( ENV.base + '/city/isfahan/', { waitUntil: 'load' } );
			await live( p3 );
			const o = await p3.evaluate( () => { const m = document.querySelector( '[data-tp-map-live]' ).tpMap; const vis = id => m.getLayoutProperty( id, 'visibility' ) || 'visible'; return { online: !! document.querySelector( '[data-tp-map-live]' ).tpOnline, disputed: vis( 'boundary_disputed' ), two: vis( 'boundary_2' ), three: vis( 'boundary_3' ) }; } );
			check( o.online && 'none' === o.disputed && 'visible' === o.two && 'visible' === o.three, `${ name } آنلاین: لایهٔ boundary_disputed پنهان؛ boundary_2 و boundary_3 دست‌نخورده`, JSON.stringify( o ) );
			await c3.close();
		} finally { await browser.close(); }
	}
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
