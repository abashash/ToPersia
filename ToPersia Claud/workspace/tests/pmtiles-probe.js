/**
 * سنجش پروندهٔ PMTiles فعلی روی نقشهٔ آفلاین واقعی — بند ۵ (۱۴ اکتبر)  (فقط topersia.local؛ فقط می‌خواند)
 *
 *   node tests/pmtiles-probe.js [--label=متن]
 *
 * نقشهٔ آفلاین (tp-map-online=0) روی /city/isfahan/ بالا می‌آید و با jumpTo:
 *   • «دریای خزر» و «خلیج فارس»: ویژگی‌های لایهٔ water/places که name یا name:fa یا name:ar دارند (نام به سه زبان)
 *   • هتل هالی (OSM node 4960920121، ‎35.7060،51.4174، tourism=hotel): در لایهٔ pois در z14 و رندر روی نقشه
 * خروجی JSON یک خط برای مقایسهٔ پیش و پس.
 */
'use strict';

const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );

( async () => {
	const browser = await chromium.launch();
	const ctx = await browser.newContext( { viewport: { width: 1200, height: 800 } } );
	await ctx.addInitScript( () => { try { sessionStorage.setItem( 'tp-map-online', '0' ); } catch ( e ) {} } );
	const page = await ctx.newPage();
	page.setDefaultTimeout( 240000 );
	await page.goto( ENV.base + '/city/isfahan/', { waitUntil: 'load' } );
	await page.evaluate( () => document.querySelector( '#tp-map' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
	await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-live]' ); return b && b.tpMap && b.tpMap.isStyleLoaded(); }, null, { timeout: 120000 } );

	const out = await page.evaluate( async () => {
		const map = document.querySelector( '[data-tp-map-live]' ).tpMap;
		const src = Object.keys( map.getStyle().sources ).find( k => k !== 'sat' && map.getStyle().sources[ k ].type === 'vector' );
		const idle = () => new Promise( r => { let t = setTimeout( r, 20000 ); map.once( 'idle', () => { clearTimeout( t ); r(); } ); } );
		const jump = async ( c, z ) => { map.jumpTo( { center: c, zoom: z } ); await idle(); await new Promise( r => setTimeout( r, 600 ) ); };
		const names = ( layers, filter ) => {
			const set = {};
			for ( const l of layers ) {
				for ( const f of map.querySourceFeatures( src, { sourceLayer: l } ) ) {
					const p = f.properties || {};
					if ( ! filter( p ) ) { continue; }
					set[ [ p.name || '', p[ 'name:fa' ] || '', p[ 'name:ar' ] || '', p.kind || '', l ].join( ' | ' ) ] = 1;
				}
			}
			return Object.keys( set ).slice( 0, 12 );
		};
		const res = { src, caspian: [], gulf: [], hali: null };

		const cas = /Caspian|خزر|قزوین|بحر قزوين|Каспий/i;
		for ( const z of [ 4, 5, 6 ] ) { await jump( [ 50.5, 41.0 ], z ); res.caspian = res.caspian.concat( names( [ 'water', 'places' ], p => cas.test( [ p.name, p[ 'name:fa' ], p[ 'name:ar' ], p[ 'name:en' ] ].join( ' ' ) ) ) ); }
		/* برچسب دیدنیِ دریای خزر در بزرگنمایی‌های میانی — لایهٔ نمادِ رندرشده، نه فقط داده */
		const txt = map.getStyle().layers.filter( l => l.type === 'symbol' && l.layout && l.layout[ 'text-field' ] ).map( l => l.id );
		res.caspianRendered = {};
		for ( const [ c, z ] of [ [ [ 51.0, 38.5 ], 6 ], [ [ 51.0, 38.5 ], 7 ], [ [ 51.5, 38.0 ], 8 ], [ [ 52.0, 37.0 ], 7 ] ] ) {
			await jump( c, z );
			const r = map.queryRenderedFeatures( { layers: txt.filter( id => /water_label|places_region|places_country|places_locality/.test( id ) ) } );
			res.caspianRendered[ z + '@' + c.join( ',' ) ] = [ ...new Set( r.map( f => f.layer.id + ':' + ( f.properties[ 'name:fa' ] || f.properties.name ) ) ) ].filter( x => /water_label/.test( x ) );
		}
		const gulf = /Persian Gulf|خلیج فارس|الخليج الفارسي|الخليج العربي/i;
		for ( const z of [ 4, 5, 6 ] ) { await jump( [ 51.5, 27.5 ], z ); res.gulf = res.gulf.concat( names( [ 'water', 'places' ], p => gulf.test( [ p.name, p[ 'name:fa' ], p[ 'name:ar' ], p[ 'name:en' ] ].join( ' ' ) ) ) ); }
		res.caspian = [ ...new Set( res.caspian ) ]; res.gulf = [ ...new Set( res.gulf ) ];

		await jump( [ 51.4174, 35.7060 ], 17 );
		const hali = names( [ 'pois' ], p => /هالی|Hali|Haali/i.test( [ p.name, p[ 'name:fa' ], p[ 'name:en' ] ].join( ' ' ) ) );
		const pt = map.project( [ 51.4174, 35.7060 ] );
		const box = [ [ pt.x - 30, pt.y - 30 ], [ pt.x + 30, pt.y + 30 ] ];
		const rendered = map.queryRenderedFeatures( box ).filter( f => /pois|label/i.test( f.layer.id ) ).map( f => f.layer.id + ':' + ( f.properties.name || '' ) + ':' + ( f.properties.kind || '' ) ).slice( 0, 8 );
		res.hali = { source: hali, rendered };
		return res;
	} );
	console.log( JSON.stringify( Object.assign( { label: ARGS.label || '' }, out ), null, 1 ) );
	await browser.close();
} )();
