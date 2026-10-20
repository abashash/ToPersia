/**
 * خوانایی نقشه — سنجش عددی و عکس پیش/پس (P5-D380، دستهٔ ۲۰ بند ۱۰ — پیش‌نمایش)  (فقط topersia.local)
 *
 *   node tests/map-legibility.js [--styles=offline,online] [--shots=DIR] [--legible=0|1] [--only=chromium]
 *
 * برای هر سبک × حالت رنگ (light · evening · dark) × بزرگنمایی (۶ · ۹ · ۱۲) روی استان اصفهان (fa):
 *   متن   هر لایهٔ نماد دارای text-field: تضاد text-color با text-halo-color (هاله بی‌پهنا ← با زمینه) — هدف ≥ ۴٫۵:۱
 *   راه   هر لایهٔ خطی راه (نام لایه road/highway/transportation/roads): تضاد line-color با رنگ زمینه — هدف ≥ ۳:۱
 * رنگ از getPaintProperty با ارزیاب کوچک (literal · rgb/hsl/hex · interpolate/step روی zoom · case/match ← نخستین شاخه).
 * --legible=1 پیش‌نمایش پیشنهادی را با `localStorage.tp-map-legible=1` روشن می‌کند (فقط اگر رندر آن را بشناسد).
 * --shots=DIR از هر ترکیب (و fa/ar، ۱۴۴۰ و ۳۹۰، استان و شهر اصفهان) عکس نقشه می‌گیرد.
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const STYLES = ( ARGS.styles || 'offline,online' ).split( ',' );
const X = Number( process.env.TP_TIMEOUT_X || 1 );
const LEG = '1' === ARGS.legible;
const SHOTS = ARGS.shots || '';

const MEASURE = ( zz ) => {
	const m = document.querySelector( '[data-tp-map-live]' ).tpMap;
	const parse = c => {
		if ( 'string' !== typeof c ) { return null; }
		const d = document.createElement( 'canvas' ).getContext( '2d' ); d.fillStyle = '#000'; d.fillStyle = c; const v = d.fillStyle;
		if ( /^#/.test( v ) ) { const n = parseInt( v.slice( 1 ), 16 ); return [ n >> 16 & 255, n >> 8 & 255, n & 255, 1 ]; }
		const r = v.match( /[\d.]+/g ); return r ? [ +r[ 0 ], +r[ 1 ], +r[ 2 ], r[ 3 ] === undefined ? 1 : +r[ 3 ] ] : null;
	};
	const ev = e => {
		if ( ! Array.isArray( e ) ) { return e; }
		switch ( e[ 0 ] ) {
			case 'literal': return e[ 1 ];
			case 'interpolate': case 'interpolate-hcl': case 'interpolate-lab': { let out = ev( e[ 4 ] ); for ( let i = 3; i + 1 < e.length; i += 2 ) { if ( zz >= e[ i ] ) { out = ev( e[ i + 1 ] ); } } return out; }
			case 'step': { let out = ev( e[ 2 ] ); for ( let i = 3; i + 1 < e.length; i += 2 ) { if ( zz >= e[ i ] ) { out = ev( e[ i + 1 ] ); } } return out; }
			case 'case': return ev( e[ 2 ] );
			case 'match': return ev( e[ 3 ] );
			case 'coalesce': return ev( e[ 1 ] );
			case 'rgb': case 'rgba': return 'rgba(' + e.slice( 1 ).map( ev ).join( ',' ) + ( 'rgb' === e[ 0 ] ? ',1' : '' ) + ')';
			case 'hsl': case 'hsla': return e[ 0 ] + '(' + e.slice( 1 ).map( ( v, i ) => i ? v + '%' : v ).join( ',' ) + ')';
			default: return null;
		}
	};
	const lum = c => { const f = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow( ( v + 0.055 ) / 1.055, 2.4 ); }; return 0.2126 * f( c[ 0 ] ) + 0.7152 * f( c[ 1 ] ) + 0.0722 * f( c[ 2 ] ); };
	const over = ( fg, bg ) => [ 0, 1, 2 ].map( i => fg[ i ] * fg[ 3 ] + bg[ i ] * ( 1 - fg[ 3 ] ) ).concat( 1 );
	const ratio = ( a, b ) => { const l1 = lum( a ), l2 = lum( b ); return ( Math.max( l1, l2 ) + 0.05 ) / ( Math.min( l1, l2 ) + 0.05 ); };
	const layers = m.getStyle().layers.filter( l => { const mn = l.minzoom || 0, mx = l.maxzoom || 24; return zz >= mn && zz < mx && 'none' !== ( l.layout || {} ).visibility; } );
	const bgL = layers.find( l => 'background' === l.type );
	const earth = layers.find( l => 'fill' === l.type && /earth|land/.test( l.id ) );
	let bg = parse( ev( bgL ? m.getPaintProperty( bgL.id, 'background-color' ) : null ) ) || [ 255, 255, 255, 1 ];
	const ec = earth ? parse( ev( m.getPaintProperty( earth.id, 'fill-color' ) ) ) : null;
	if ( ec ) { bg = over( ec, bg ); }
	const text = [], road = [];
	for ( const l of layers ) {
		if ( 'symbol' === l.type && l.layout && l.layout[ 'text-field' ] ) {
			const tc = parse( ev( m.getPaintProperty( l.id, 'text-color' ) ) ) || [ 0, 0, 0, 1 ];
			const hw = Number( ev( m.getPaintProperty( l.id, 'text-halo-width' ) ) || 0 );
			const hc = parse( ev( m.getPaintProperty( l.id, 'text-halo-color' ) ) );
			const back = hw > 0 && hc ? over( hc, bg ) : bg;
			text.push( { id: l.id, r: Math.round( ratio( over( tc, back ), back ) * 100 ) / 100, halo: hw } );
		}
		if ( 'line' === l.type && /road|highway|transportation|roads/.test( l.id ) && ! /casing|tunnel/.test( l.id ) ) {
			const lc = parse( ev( m.getPaintProperty( l.id, 'line-color' ) ) );
			if ( lc ) { road.push( { id: l.id, r: Math.round( ratio( over( lc, bg ), bg ) * 100 ) / 100, w: ev( m.getPaintProperty( l.id, 'line-width' ) ) } ); }
		}
	}
	return { text, road, bg };
};

async function open( browser, style, mode, lang, w, url ) {
	const ctx = await browser.newContext( { viewport: { width: w, height: 900 } } );
	await ctx.addInitScript( ( [ online, mode, leg ] ) => { try { sessionStorage.setItem( 'tp-map-online', online ? '1' : '0' ); localStorage.setItem( 'tp-mode', mode ); localStorage.setItem( 'tp-map-legible', leg ? '1' : '0' ); } catch ( e ) {} }, [ 'online' === style, mode, LEG ] );
	const page = await ctx.newPage();
	page.setDefaultTimeout( 240000 * X );
	await page.goto( ENV.base + ( 'ar' === lang ? '/ar' : '' ) + url, { waitUntil: 'load' } );
	await page.evaluate( m => document.documentElement.setAttribute( 'data-tp-mode', m ), mode );
	await page.click( '[data-tp-map-open]' );
	await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-full]' ); return b && b.tpMap && b.tpMap.isStyleLoaded(); }, null, { timeout: 180000 * X } );
	await page.evaluate( () => { const f = document.querySelector( '[data-tp-map-full]' ); f.setAttribute( 'data-tp-map-live', '' ); document.querySelectorAll( '[data-tp-map-live]' ).forEach( b => { if ( b !== f ) { b.removeAttribute( 'data-tp-map-live' ); } } ); } );
	return { ctx, page, online: await page.evaluate( () => !! document.querySelector( '[data-tp-map-full]' ).tpOnline ) };
}

( async () => {
	console.log( 'START ' + new Date().toISOString() + ( LEG ? ' — پیش‌نمایش خوانایی روشن' : ' — وضع فعلی' ) );
	const browser = await chromium.launch();
	const table = [];
	try {
		for ( const style of STYLES ) {
			for ( const mode of [ 'light', 'evening', 'dark' ] ) {
				const { ctx, page, online } = await open( browser, style, mode, 'fa', 1440, '/province/isfahan/' );
				if ( 'online' === style && ! online ) { console.log( `  · ${ style } ${ mode }: سبک آنلاین در دسترس نبود` ); await ctx.close(); continue; }
				for ( const z of [ 6, 9, 12 ] ) {
					await page.evaluate( zz => { const m = document.querySelector( '[data-tp-map-full]' ).tpMap; m.jumpTo( { center: [ 51.67, 32.65 ], zoom: zz } ); }, z );
					await page.waitForFunction( () => { const m = document.querySelector( '[data-tp-map-full]' ).tpMap; return m.loaded(); }, null, { timeout: 60000 * X } ).catch( () => {} );
					await page.waitForTimeout( 800 );
					const r = await page.evaluate( MEASURE, z );
					const tmin = r.text.length ? Math.min( ...r.text.map( t => t.r ) ) : null, rmin = r.road.length ? Math.min( ...r.road.map( t => t.r ) ) : null;
					const tbad = r.text.filter( t => t.r < 4.5 ), rbad = r.road.filter( t => t.r < 3 );
					table.push( { style, mode, z, tmin, rmin, tbad: tbad.length, tn: r.text.length, rbad: rbad.length, rn: r.road.length } );
					console.log( `  ${ tbad.length || rbad.length ? '✗' : '✓' } ${ style } ${ mode } z${ z }: متن کمینه ${ tmin } (${ tbad.length }/${ r.text.length } زیر ۴٫۵) · راه کمینه ${ rmin } (${ rbad.length }/${ r.road.length } زیر ۳)`
						+ ( tbad.length ? '  ← ' + tbad.slice( 0, 4 ).map( t => t.id + ':' + t.r ).join( ' ' ) : '' ) + ( rbad.length ? '  ← ' + rbad.slice( 0, 4 ).map( t => t.id + ':' + t.r ).join( ' ' ) : '' ) );
				}
				await ctx.close();
			}
		}
		if ( SHOTS ) {
			fs.mkdirSync( SHOTS, { recursive: true } );
			for ( const style of STYLES ) {
				for ( const url of [ '/province/isfahan/', '/city/isfahan/' ] ) {
					for ( const lang of [ 'fa', 'ar' ] ) {
						for ( const mode of [ 'light', 'evening', 'dark' ] ) {
							for ( const w of [ 1440, 390 ] ) {
								const { ctx, page, online } = await open( browser, style, mode, lang, w, url );
								if ( 'online' === style && ! online ) { await ctx.close(); continue; }
								for ( const z of [ 6, 9, 12 ] ) {
									await page.evaluate( zz => { const m = document.querySelector( '[data-tp-map-full]' ).tpMap; m.jumpTo( { center: [ 51.67, 32.65 ], zoom: zz } ); }, z );
									await page.waitForFunction( () => document.querySelector( '[data-tp-map-full]' ).tpMap.loaded(), null, { timeout: 60000 * X } ).catch( () => {} );
									await page.waitForTimeout( 900 );
									const el = await page.$( '[data-tp-map-full]' );
									await el.screenshot( { path: path.join( SHOTS, [ style, url.split( '/' )[ 1 ], lang, mode, w, 'z' + z ].join( '-' ) + '.png' ) } );
								}
								await ctx.close();
							}
						}
					}
				}
			}
		}
	} finally { await browser.close(); }
	fs.writeFileSync( path.join( __dirname, '.map-legibility-' + ( LEG ? 'after' : 'before' ) + '.json' ), JSON.stringify( table, null, 1 ) );
	const bad = table.filter( t => t.tbad || t.rbad ).length;
	console.log( `\nEND ${ new Date().toISOString() } — ترکیب‌ها: ${ table.length } · زیر هدف: ${ bad }` );
} )();
