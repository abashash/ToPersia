/**
 * پروب فقط‌خواندنی نقشهٔ آفلاین — دستهٔ ۲ · P5-D352: چه لایه و چه kind در PMTiles هست و سبک کدام را پنهان می‌کند؛ «هتل هالی» تهران.
 *   node tests/map-probe-2j.js [lat,lng ...]
 */
'use strict';
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );
( async () => {
	const b = await chromium.launch();
	const p = await ( await b.newContext( { viewport: { width: 1440, height: 900 } } ) ).newPage();
	await p.goto( ENV.base + '/province/tehran/', { waitUntil: 'load', timeout: 240000 } );
	await p.evaluate( () => document.querySelector( '[data-tp-map-open]' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
	await p.click( '[data-tp-map-open]' );
	await p.waitForFunction( () => { const x = document.querySelector( '[data-tp-map-full]' ); return x && x.tpMap && x.tpMap.isStyleLoaded(); }, null, { timeout: 120000 } );
	const spots = process.argv.slice( 2 ).length ? process.argv.slice( 2 ) : [ '35.7060346,51.4174273' ];
	for ( const sp of spots ) {
		const [ lat, lng ] = sp.split( ',' ).map( Number );
		for ( const z of [ 14, 15, 16 ] ) {
			const r = await p.evaluate( async ( [ lat, lng, z ] ) => {
				const m = document.querySelector( '[data-tp-map-full]' ).tpMap;
				m.jumpTo( { center: [ lng, lat ], zoom: z } );
				await new Promise( ok => { const t = setTimeout( ok, 6000 ); m.once( 'idle', () => { clearTimeout( t ); ok(); } ); } );
				const all = m.querySourceFeatures( 'protomaps', { sourceLayer: 'pois' } );
				const kinds = {}; all.forEach( f => { kinds[ f.properties.kind ] = ( kinds[ f.properties.kind ] || 0 ) + 1; } );
				const near = all.filter( f => { const c = f.geometry.coordinates; return Math.abs( c[ 1 ] - lat ) < 0.002 && Math.abs( c[ 0 ] - lng ) < 0.002; } ).map( f => ( { kind: f.properties.kind, kind_detail: f.properties.kind_detail, min_zoom: f.properties.min_zoom, name: f.properties.name, fa: f.properties[ 'name:fa' ], en: f.properties[ 'name:en' ] } ) );
				const hally = all.filter( f => /هالی|Hally/i.test( JSON.stringify( f.properties ) ) ).map( f => ( { kind: f.properties.kind, name: f.properties.name, min_zoom: f.properties.min_zoom, ll: f.geometry.coordinates } ) );
				const rendered = m.queryRenderedFeatures( { layers: [ 'pois' ] } ).length;
				const lay = m.getStyle().layers.filter( l => l[ 'source-layer' ] === 'pois' ).map( l => ( { id: l.id, filterKinds: JSON.stringify( l.filter ).length } ) );
				return { z, hally, total: all.length, kindsN: Object.keys( kinds ).length, nearHere: near.length, renderedPoisLayer: rendered, poisLayers: lay };
			}, [ lat, lng, z ] );
			console.log( sp + ' z' + z + ' ' + JSON.stringify( r ) );
		}
	}
	await b.close();
} )();
