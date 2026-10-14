/**
 * گزارش برچسب‌های جغرافیایی نقشهٔ آفلاین — P5-D364  (فقط می‌خواند؛ فقط topersia.local)
 *
 *   node tests/map-geo-report.js [--lang=fa|en|ar] [--shots=1]
 *
 * برای هر پهنه: ویژگی‌های لایهٔ water/earth/places با نام (name · name:fa · name:en · name:ar) از PMTiles فعلی؛ و نزدیک‌ترین
 * خط‌های لایهٔ boundaries (نوع، kind_detail، disputed) تا ببینیم جزیره‌ها درون مرز ایران رسم شده‌اند یا نه. هیچ‌چیز تغییر نمی‌کند.
 * --shots: عکس نقشه روی هر پهنه (tests/.screens/geo/).
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const LANG = ARGS.lang || 'fa';

const SPOTS = [
	{ key: 'persian-gulf', c: [ 51.5, 27.5 ], z: [ 5, 6 ], re: /Persian Gulf|Arabian Gulf|خلیج فارس|الخليج/i },
	{ key: 'gulf-of-oman', c: [ 58.5, 24.8 ], z: [ 5, 6 ], re: /Gulf of Oman|Sea of Oman|دریای عمان|خلیج عمان|بحر عمان|خليج عمان/i },
	{ key: 'strait-of-hormuz', c: [ 56.5, 26.6 ], z: [ 7, 8 ], re: /Hormuz|هرمز|هرمز/i },
	{ key: 'caspian', c: [ 51.0, 41.5 ], z: [ 5, 6 ], re: /Caspian|خزر|قزوين|Каспий/i },
	{ key: 'abu-musa', c: [ 55.03, 25.87 ], z: [ 10, 12 ], re: /Abu Musa|ابوموسی|أبو موسى|ابو موسى/i },
	{ key: 'greater-tunb', c: [ 55.30, 26.26 ], z: [ 10, 12 ], re: /Tunb|تنب|طنب/i },
	{ key: 'lesser-tunb', c: [ 55.15, 26.24 ], z: [ 10, 12 ], re: /Tunb|تنب|طنب/i },
];

( async () => {
	const browser = await chromium.launch();
	const ctx = await browser.newContext( { viewport: { width: 900, height: 700 } } );
	await ctx.addInitScript( () => { try { sessionStorage.setItem( 'tp-map-online', '0' ); } catch ( e ) {} } );
	const page = await ctx.newPage();
	page.setDefaultTimeout( 240000 );
	const base = { fa: '', en: '/en', ar: '/ar' }[ LANG ];
	await page.goto( ENV.base + base + '/city/isfahan/', { waitUntil: 'load' } );
	await page.evaluate( () => document.querySelector( '#tp-map' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
	await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-live]' ); return b && b.tpMap && b.tpMap.isStyleLoaded(); }, null, { timeout: 120000 } );
	const shots = path.join( __dirname, '.screens', 'geo' );
	if ( ARGS.shots ) { fs.mkdirSync( shots, { recursive: true } ); }

	const out = [];
	for ( const s of SPOTS ) {
		const r = await page.evaluate( async ( s ) => {
			const map = document.querySelector( '[data-tp-map-live]' ).tpMap;
			const src = Object.keys( map.getStyle().sources ).find( k => map.getStyle().sources[ k ].type === 'vector' );
			const idle = () => new Promise( r => { const t = setTimeout( r, 20000 ); map.once( 'idle', () => { clearTimeout( t ); r(); } ); } );
			const found = {}; const bounds = {};
			const re = new RegExp( s.re.source, 'i' );
			for ( const z of s.z ) {
				map.jumpTo( { center: s.c, zoom: z } ); await idle(); await new Promise( r => setTimeout( r, 700 ) );
				for ( const l of [ 'water', 'earth', 'places', 'pois' ] ) {
					for ( const f of map.querySourceFeatures( src, { sourceLayer: l } ) ) {
						const p = f.properties || {};
						if ( re.test( [ p.name, p[ 'name:fa' ], p[ 'name:en' ], p[ 'name:ar' ] ].join( ' ' ) ) ) { found[ [ l, p.kind || '', p.name || '', p[ 'name:fa' ] || '', p[ 'name:en' ] || '', p[ 'name:ar' ] || '' ].join( ' | ' ) ] = 1; }
					}
				}
				for ( const f of map.querySourceFeatures( src, { sourceLayer: 'boundaries' } ) ) {
					const p = f.properties || {};
					bounds[ [ p.kind || '', p.kind_detail || '', p.disputed ? 'disputed' : '', p[ 'pmap:min_admin_level' ] || '', p.name || '' ].join( ' | ' ) ] = 1;
				}
			}
			return { found: Object.keys( found ), bounds: Object.keys( bounds ).slice( 0, 8 ) };
		}, { c: s.c, z: s.z, re: { source: s.re.source } } );
		out.push( Object.assign( { spot: s.key }, r ) );
		if ( ARGS.shots ) { await page.locator( '#tp-map' ).screenshot( { path: path.join( shots, `${ s.key }-${ LANG }.png` ) } ); }
	}
	console.log( JSON.stringify( { lang: LANG, out }, null, 1 ) );
	await browser.close();
} )();
