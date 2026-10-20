/**
 * خلیج فارس — بازآزمایی روی برچسب‌های واقعاً رندرشده (P5-D364، دستهٔ ۲۰ بند ۴)  (فقط topersia.local)
 *
 *   node tests/map-gulf-live.js [--only=chromium|firefox] [--styles=offline,online] [--mutate=1]   (جهش: باید بیفتد)
 *
 * fa · en · ar × هر دو سبک (آفلاین PMTiles و آنلاین OpenFreeMap) × سه حالت رنگ (light · evening · dark) × بزرگنمایی ۳ تا ۱۰،
 * نقشه روی خلیج فارس (۲۷٫۰ شمالی، ۵۱٫۵ شرقی). در هر گام همهٔ عارضه‌های نماد رندرشده (`queryRenderedFeatures`) خوانده و متن
 * نمایش‌داده‌شدهٔ هر کدام از خودِ text-field لایه با ارزیاب درون صفحه ساخته می‌شود. **هر برچسبی که «الخليج العربي» یا «Arabian Gulf»
 * دارد = شکست.** همچنین گزارش می‌شود برچسب خلیج فارس در هر گام دیده شد یا نه (در بزرگنمایی‌هایی که داده برچسب دارد).
 *
 * ارزیاب: get · has · == · != · ! · all · any · case · match · coalesce · concat · format · literal · to-string · string · zoom · step ·
 * interpolate (عدد پله‌ای) · is-supported-script — بیش از آنچه basemaps و OpenFreeMap در text-field به کار می‌برند. عبارت ناشناخته ← خطای آزمون (نه پذیرش خاموش).
 */
'use strict';

const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const STYLES = ( ARGS.styles || 'offline,online' ).split( ',' );
const X = Number( process.env.TP_TIMEOUT_X || 1 );
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok || ! note ? '' : '   ← ' + note ) ); };

const BAD_RE = /الخليج العربي|Arabian Gulf/i;
const GOOD = { fa: 'خلیج فارس', en: 'Persian Gulf', ar: 'الخليج الفارسي' };

async function run( browser, lang, style, mode ) {
	const ctx = await browser.newContext( { viewport: { width: 1200, height: 800 } } );
	await ctx.addInitScript( ( [ online, mode ] ) => {
		try { sessionStorage.setItem( 'tp-map-online', online ? '1' : '0' ); localStorage.setItem( 'tp-mode', mode ); } catch ( e ) {}
	}, [ 'online' === style, mode ] );
	const page = await ctx.newPage();
	page.setDefaultTimeout( 240000 * X );
	await page.goto( ENV.base + ( { fa: '', en: '/en', ar: '/ar' }[ lang ] ) + '/province/isfahan/', { waitUntil: 'load' } );
	await page.evaluate( m => document.documentElement.setAttribute( 'data-tp-mode', m ), mode );
	await page.evaluate( () => document.querySelector( '#tp-map' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
	await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-live]' ); return b && b.tpMap && b.tpMap.isStyleLoaded(); }, null, { timeout: 180000 * X } );
	const isOnline = await page.evaluate( () => !! document.querySelector( '[data-tp-map-live]' ).tpOnline );
	/* جهش (--mutate=1): text-field هر لایهٔ نماد ← نام خام دادهٔ OSM (بی gulfWrap) — آزمون باید بیفتد */
	if ( ARGS.mutate ) {
		await page.evaluate( l => { const m = document.querySelector( '[data-tp-map-live]' ).tpMap; m.getStyle().layers.filter( x => 'symbol' === x.type && x.layout && x.layout[ 'text-field' ] ).forEach( x => m.setLayoutProperty( x.id, 'text-field', [ 'coalesce', [ 'get', 'name:' + l ], [ 'get', 'name' ] ] ) ); }, lang );
	}
	const steps = [];

	for ( let z = 3; z <= 10; z++ ) {
		await page.evaluate( zz => { const m = document.querySelector( '[data-tp-map-live]' ).tpMap; m.jumpTo( { center: [ 51.5, 27.0 ], zoom: zz } ); }, z );
		await page.waitForFunction( () => { const m = document.querySelector( '[data-tp-map-live]' ).tpMap; return m.loaded() && m.areTilesLoaded(); }, null, { timeout: 60000 * X } ).catch( () => {} );
		await page.waitForTimeout( 700 );
		steps.push( await page.evaluate( zz => {
			const m = document.querySelector( '[data-tp-map-live]' ).tpMap;
			const unknown = new Set();
			function ev( e, p ) {
				if ( ! Array.isArray( e ) ) { return e; }
				const a = i => ev( e[ i ], p );
				switch ( e[ 0 ] ) {
					case 'get': return p[ e[ 1 ] ] === undefined ? null : p[ e[ 1 ] ];
					case 'has': return p[ e[ 1 ] ] !== undefined;
					case '==': return a( 1 ) === a( 2 );
					case '!=': return a( 1 ) !== a( 2 );
					case '!': return ! a( 1 );
					case 'all': return e.slice( 1 ).every( x => ev( x, p ) === true );
					case 'any': return e.slice( 1 ).some( x => ev( x, p ) === true );
					case 'case': { for ( let i = 1; i + 1 < e.length; i += 2 ) { if ( ev( e[ i ], p ) === true ) { return ev( e[ i + 1 ], p ); } } return ev( e[ e.length - 1 ], p ); }
					case 'match': { const v = a( 1 ); for ( let i = 2; i + 1 < e.length; i += 2 ) { const k = e[ i ]; if ( Array.isArray( k ) ? k.indexOf( v ) >= 0 : k === v ) { return ev( e[ i + 1 ], p ); } } return ev( e[ e.length - 1 ], p ); }
					case 'coalesce': { for ( const x of e.slice( 1 ) ) { const v = ev( x, p ); if ( v !== null && v !== undefined && v !== '' ) { return v; } } return null; }
					case 'concat': return e.slice( 1 ).map( x => { const v = ev( x, p ); return v === null ? '' : String( v ); } ).join( '' );
					case 'format': { let s = ''; for ( let i = 1; i < e.length; i++ ) { if ( e[ i ] && 'object' === typeof e[ i ] && ! Array.isArray( e[ i ] ) ) { continue; } const v = ev( e[ i ], p ); s += v === null ? '' : String( v ); } return s; }
					case 'literal': return e[ 1 ];
					case 'to-string': case 'string': { const v = a( 1 ); return v === null ? '' : String( v ); }
					case 'zoom': return zz;
					case 'is-supported-script': return true;   /* مرورگرهای آزمون هر سه خط را دارند */
					case 'step': { const v = a( 1 ); let out = ev( e[ 2 ], p ); for ( let i = 3; i + 1 < e.length; i += 2 ) { if ( v >= e[ i ] ) { out = ev( e[ i + 1 ], p ); } } return out; }
					case 'interpolate': { const v = a( 2 ); let out = ev( e[ 4 ], p ); for ( let i = 3; i + 1 < e.length; i += 2 ) { if ( v >= e[ i ] ) { out = ev( e[ i + 1 ], p ); } } return out; }
					default: unknown.add( e[ 0 ] ); return '';
				}
			}
			const shown = [];
			for ( const f of m.queryRenderedFeatures() ) {
				const l = f.layer;
				if ( ! l || 'symbol' !== l.type || ! l.layout || ! l.layout[ 'text-field' ] ) { continue; }
				const t = ev( m.getLayoutProperty( l.id, 'text-field' ), f.properties );
				if ( t && 'string' === typeof t ) { shown.push( { layer: l.id, text: t, names: Object.keys( f.properties ).filter( k => /^name/.test( k ) ).map( k => k + '=' + f.properties[ k ] ).join( ' | ' ).slice( 0, 200 ) } ); }
			}
			return { z: zz, shown, unknown: [ ...unknown ] };
		}, z ) );
	}
	await ctx.close();
	return { isOnline, steps };
}

( async () => {
	console.log( 'START ' + new Date().toISOString() );
	const found = [];
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ].filter( b => ! ARGS.only || ARGS.only === b[ 0 ] ) ) {
		const browser = await type.launch();
		console.log( `\n── ${ name } ──` );
		try {
			for ( const style of STYLES ) {
				for ( const lang of [ 'fa', 'en', 'ar' ] ) {
					for ( const mode of [ 'light', 'evening', 'dark' ] ) {
						const r = await run( browser, lang, style, mode );
						if ( 'online' === style && ! r.isOnline ) {
							console.log( `  ·  ${ name } ${ style } ${ lang } ${ mode }: سبک آنلاین در دسترس نبود (OpenFreeMap) — آفلاین بار شد؛ این ترکیب سنجیده نشد` );
							continue;
						}
						const offend = [], unknown = new Set(), gulfSeen = [];
						for ( const s of r.steps ) {
							s.unknown.forEach( u => unknown.add( u ) );
							for ( const x of s.shown ) {
								if ( BAD_RE.test( x.text ) ) { offend.push( `z${ s.z } ${ x.layer }: «${ x.text }» (${ x.names })` ); }
								if ( x.text === GOOD[ lang ] ) { gulfSeen.push( s.z ); }
							}
						}
						offend.forEach( o => found.push( `${ name } ${ style } ${ lang } ${ mode } ${ o }` ) );
						check( 0 === offend.length && 0 === unknown.size,
							`${ name } ${ style } ${ lang } ${ mode }: بزرگنمایی ۳–۱۰ هیچ برچسب «الخليج العربي/Arabian Gulf» — «${ GOOD[ lang ] }» دیده‌شده در z ${ [ ...new Set( gulfSeen ) ].join( ',' ) || '—' }`,
							( offend.slice( 0, 3 ).join( ' ; ' ) + ( unknown.size ? ' · عبارت ناشناخته: ' + [ ...unknown ].join( ',' ) : '' ) ).slice( 0, 600 ) );
					}
				}
			}
		} finally { await browser.close(); }
	}
	if ( found.length ) { console.log( '\nیافته‌ها:\n' + found.slice( 0, 40 ).join( '\n' ) ); }
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
