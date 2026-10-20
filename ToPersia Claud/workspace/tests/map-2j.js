/**
 * دستهٔ ۲ در مرورگر — مسیریابی، لایهٔ مکان‌ها، قلم Italic، مرز محدوده  P5-D350 · P5-D351 · P5-D352
 *
 *   node tests/map-2j.js [--shots]
 *
 *   ۱ مسیریابی: شهر اصفهان (نقطه‌ای، مختصات) ← ۳ پیوند؛ گاوخونی (پهنه، متن نام + والد) ← ۲ پیوند؛ کشور ← هیچ؛ نشانی‌ها برابر الگوی مستند؛
 *     کلیک آخرین انتخاب را در localStorage می‌نویسد و پس از بارگذاری آن دکمه اول است؛ بی geolocation؛ fa · en · ar.
 *   ۲ نقشهٔ کامل (آفلاین): در fa · en · ar و سه حالت رنگ — لایهٔ pois با kindهای اصلی (هتل، مکان مذهبی، … ) برچسب دارد؛ letter-spacing برای fa و ar در هیچ
 *     لایه نیست؛ هیچ لایه‌ای قلم «Noto Sans Italic» ندارد؛ بازهٔ عربی Regular هست؛ «خلیج فارس» و «دریای خزر» در fa و ar دیده می‌شوند (با --shots عکس).
 *   ۳ مرز: فایل GeoJSON موقتِ ساختهٔ همین اجرا برای استان اصفهان ← cfg.bound، منبع و لایهٔ tp-bound، نقشه روی همان محدوده؛ پایان پاک‌شده.
 * عکس‌ها: tests/.screens/2j/ (بیرون از git).
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

const SHOTS = process.argv.includes( '--shots' );
const DIR   = path.join( __dirname, '.screens', '2j' );
const WS    = path.resolve( __dirname, '..' );
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + ( note || '' ) ) ); };
const sleep = ms => new Promise( r => setTimeout( r, ms ) );

/* بیشینهٔ عرض جغرافیایی سرآیند PMTiles (v3: Int32 در بایت ۱۱۴، ×۱۰⁻⁷) */
const TILES_MAX_LAT = ( () => { try { const fd = fs.openSync( path.join( ENV.wp, 'wp-content', 'uploads', 'tiles', 'iran.pmtiles' ), 'r' ); const h = Buffer.alloc( 127 ); fs.readSync( fd, h, 0, 127, 0 ); fs.closeSync( fd ); return h.readInt32LE( 114 ) / 1e7; } catch ( e ) { return 99; } } )();

async function open( browser, url, mode ) {
	const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
	const page = await ctx.newPage();
	/* این آزمون نقشهٔ آفلاین (PMTiles) را می‌سنجد؛ از دستهٔ ۳ پیش‌فرض آنلاین اول است ← آنلاین در sessionStorage خاموش (آنلاین: tests/map-3.js) */
	await page.addInitScript( () => { try { sessionStorage.setItem( 'tp-map-online', '0' ); } catch ( e ) {} } );
	if ( mode ) { await page.addInitScript( m => { try { localStorage.setItem( 'tp-mode', m ); } catch ( e ) {} }, mode ); }
	await page.goto( ENV.base + url, { waitUntil: 'load', timeout: 240000 } );
	return { ctx, page };
}

async function fullMap( page ) {
	await page.evaluate( () => document.querySelector( '[data-tp-map-open]' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
	await page.click( '[data-tp-map-open]' );
	await page.waitForFunction( () => { const x = document.querySelector( '[data-tp-map-full]' ); return x && x.tpMap && x.tpMap.isStyleLoaded(); }, null, { timeout: 120000 } );
	/* مرز موجودیت (P5-D351) ناهمگام می‌رسد و نقشه را روی محدوده می‌بندد؛ jumpTo پیش از آن بعداً لغو می‌شد (از وقتی GeoJSON شهر اصفهان ساخته شد). پس نخست مرز نشسته و حرکت ایستاده. */
	await page.waitForFunction( () => { const m = document.querySelector( '[data-tp-map-full]' ).tpMap; return m.getSource( 'tp-bound' ) && ! m.isMoving(); }, null, { timeout: 20000 } ).catch( () => {} );
	await page.waitForTimeout( 1500 );
}

async function view( page, lat, lng, z ) {
	return page.evaluate( async ( [ lat, lng, z ] ) => {
		const m = document.querySelector( '[data-tp-map-full]' ).tpMap;
		m.jumpTo( { center: [ lng, lat ], zoom: z } );
		await new Promise( ok => { const t = setTimeout( ok, 8000 ); m.once( 'idle', () => { clearTimeout( t ); ok(); } ); } );
		return true;
	}, [ lat, lng, z ] );
}

( async () => {
	const browser = await chromium.launch();
	console.log( 'START ' + new Date().toISOString() );
	try {
		/* ── ۱ مسیریابی ─────────────────────────────────────────────── */
		console.log( '\n── مسیریابی ──' );
		for ( const [ lang, pre ] of [ [ 'fa', '' ], [ 'en', '/en' ], [ 'ar', '/ar' ] ] ) {
			let { ctx, page } = await open( browser, pre + '/city/isfahan/' );
			const city = await page.evaluate( () => [ ...document.querySelectorAll( '#tp-routing a[data-tp-nav]' ) ].map( a => [ a.dataset.tpNav, a.href, a.target, a.rel, a.textContent.trim() ] ) );
			check( 3 === city.length && 'google,waze,neshan' === city.map( c => c[ 0 ] ).join()
				&& /^https:\/\/www\.google\.com\/maps\/dir\/\?api=1&destination=[\d.]+,[\d.]+$/.test( city[ 0 ][ 1 ] ) && /^https:\/\/waze\.com\/ul\?ll=[\d.]+,[\d.]+&navigate=yes$/.test( city[ 1 ][ 1 ] ) && /^https:\/\/nshn\.ir\/\?lat=[\d.]+&lng=[\d.]+$/.test( city[ 2 ][ 1 ] )
				&& city.every( c => '_blank' === c[ 2 ] && /noopener/.test( c[ 3 ] ) ),
				`${ lang } شهر اصفهان (مختصات): Google · Waze · نشان با الگوی مستند؛ برچسب‌ها ${ city.map( c => c[ 4 ] ).join( ' · ' ) }`, JSON.stringify( city ) );
			const noGeo = await page.evaluate( () => ! /geolocation/.test( document.documentElement.innerHTML ) );
			check( noGeo, `${ lang }: صفحه geolocation نمی‌خواهد`, '' );

			/* آخرین انتخاب: کلیک روی «ویز» (در برگهٔ تازه باز نشود: پیش‌گیری از ناوبری) → بارگذاری دوباره: ویز اول */
			await page.evaluate( () => { document.querySelector( '#tp-routing a[data-tp-nav="waze"]' ).addEventListener( 'click', e => e.preventDefault(), true ); } );
			await page.click( '#tp-routing a[data-tp-nav="waze"]' );
			const stored = await page.evaluate( () => { try { return localStorage.getItem( 'tp-nav-last' ); } catch ( e ) { return 'blocked'; } } );
			await page.reload( { waitUntil: 'load' } );
			await sleep( 600 );
			const first = await page.evaluate( () => ( document.querySelector( '#tp-routing a[data-tp-nav]' ) || {} ).dataset.tpNav );
			check( 'waze' === stored && 'waze' === first, `${ lang }: آخرین انتخاب در localStorage و آن دکمه اول`, stored + '/' + first );
			await page.evaluate( () => { try { localStorage.removeItem( 'tp-nav-last' ); } catch ( e ) {} } );
			await ctx.close();

			if ( 'fa' === lang ) {
				( { ctx, page } = await open( browser, '/water-body/gavkhouni-lake/' ) );
				const wb = await page.evaluate( () => [ ...document.querySelectorAll( '#tp-routing a[data-tp-nav]' ) ].map( a => [ a.dataset.tpNav, decodeURIComponent( a.href ) ] ) );
				/* P5-D383: Google و Waze همان متن «نام، والد مکانی» (P5-D350، نه مرکز هندسی)؛ نشان فقط مختصات — نقطهٔ نماینده‌ای که خودِ geo_coordinates گاوخونی دارد */
				const gav = await page.evaluate( () => { const p = ( document.querySelector( '[data-tp-coords]' ) || {} ).getAttribute ? document.querySelector( '[data-tp-coords]' ).getAttribute( 'data-tp-coords' ) : ''; return p; } );
				check( 3 === wb.length && 'google,waze,neshan' === wb.map( c => c[ 0 ] ).join() && /destination=.+،.+/.test( wb[ 0 ][ 1 ] ) && /q=.+،.+&navigate=yes/.test( wb[ 1 ][ 1 ] ) && ! /destination=\d/.test( wb[ 0 ][ 1 ] )
					&& /^https:\/\/nshn\.ir\/\?lat=-?\d+(\.\d+)?&lng=-?\d+(\.\d+)?$/.test( wb[ 2 ][ 1 ] ),
					'گاوخونی (پهنه): Google و Waze با متن «نام، والد مکانی» — نه مرکز هندسی؛ نشان با نقطهٔ نماینده (P5-D383)', JSON.stringify( [ wb, gav ] ) );
				await ctx.close();
				( { ctx, page } = await open( browser, '/country/islamic-republic-of-iran/' ) );
				check( 0 === await page.locator( '#tp-routing' ).count(), 'کشور: باکس مسیریابی ندارد', '' );
				await ctx.close();
			}
		}

		/* ── ۲ نقشهٔ آفلاین ───────────────────────────────────────────── */
		console.log( '\n── نقشه (آفلاین) ──' );
		fs.mkdirSync( DIR, { recursive: true } );
		for ( const [ lang, pre ] of [ [ 'fa', '' ], [ 'en', '/en' ], [ 'ar', '/ar' ] ] ) {
			for ( const mode of [ 'light', 'evening', 'dark' ] ) {
				const { ctx, page } = await open( browser, pre + '/city/isfahan/', mode );
				await fullMap( page );
				await view( page, 32.6546, 51.668, 15 );   /* مرکز اصفهان: میدان نقش جهان و هتل‌ها */
				const r = await page.evaluate( () => {
					const m = document.querySelector( '[data-tp-map-full]' ).tpMap;
					const layers = m.getStyle().layers;
					const spacing = layers.filter( l => l.layout && undefined !== l.layout[ 'text-letter-spacing' ] ).length;
					const fonts = JSON.stringify( layers.map( l => l.layout && l.layout[ 'text-font' ] ) );
					const pois = layers.find( l => 'pois' === l.id );
					const feats = m.queryRenderedFeatures( { layers: [ 'pois' ] } );
					const kinds = {}; feats.forEach( f => { kinds[ f.properties.kind ] = 1; } );
					return { spacing, italic: /Noto Sans Italic/.test( fonts ), poisKinds: JSON.stringify( pois.filter ), rendered: feats.length, kinds: Object.keys( kinds ), field: JSON.stringify( pois.layout[ 'text-field' ] ), flavorDark: document.documentElement.getAttribute( 'data-tp-mode' ) };
				} );
				const mainKinds = [ 'attraction', 'museum', 'place_of_worship', 'park', 'hotel', 'restaurant', 'cafe', 'aerodrome', 'station' ];
				check( r.rendered > 0 && r.kinds.every( k => mainKinds.includes( k ) ) && ( 'fa' === lang || 'ar' === lang ? 0 === r.spacing : true ) && ! r.italic && r.field.includes( lang ),
					`${ lang } ${ mode }: لایهٔ pois ${ r.rendered } برچسب با kindهای اصلی (${ r.kinds.join( ',' ) })؛ letter-spacing ${ r.spacing } لایه؛ بی قلم Italic؛ برچسب از name:${ lang }`, JSON.stringify( r ).slice( 0, 400 ) );
				if ( SHOTS && 'light' === mode ) { await sleep( 800 ); await page.screenshot( { path: path.join( DIR, `pois-${ lang }.jpg` ), type: 'jpeg', quality: 80 } ); }
				await ctx.close();
			}
		}

		/* قلم Regular: بازهٔ عربی هست */
		const gl = await ( await fetch( ENV.base + '/assets/plugins/topersia-render/assets/map/fonts/Noto%20Sans%20Regular/1536-1791.pbf' ) ).status;
		check( 200 === gl, 'بستهٔ قلم Noto Sans Regular بازهٔ عربی (۱۵۳۶–۱۷۹۱) دارد', String( gl ) );

		/* خلیج فارس و دریای خزر در fa و ar */
		for ( const lang of [ 'fa', 'ar' ] ) {
			const { ctx, page } = await open( browser, ( 'fa' === lang ? '' : '/ar' ) + '/city/isfahan/', 'light' );
			await fullMap( page );
			for ( const [ name, lat, lng, z ] of [ [ 'persian-gulf', 27.0, 51.5, 6 ], [ 'caspian', 41.0, 50.5, 5 ] ] ) {
				await view( page, lat, lng, z );
				const r = await page.evaluate( () => {
					const m = document.querySelector( '[data-tp-map-full]' ).tpMap;
					const f = m.queryRenderedFeatures( { layers: [ 'water_label_ocean', 'water_label_lakes', 'water_waterway_label' ].filter( id => m.getLayer( id ) ) } );
					return { n: f.length, names: [ ...new Set( f.map( x => x.properties[ 'name:' + document.documentElement.lang.slice( 0, 2 ) ] || x.properties.name ) ) ].slice( 0, 4 ) };
				} );
				if ( 'caspian' === name && TILES_MAX_LAT < 42.1 ) {
					console.log( `  ℹ︎ ${ lang } caspian: برچسب «دریای خزر» (نقطهٔ برچسب ۴۱٫۹۹°N) در PMTiles فعلی نیست — bbox فایل تا ${ TILES_MAX_LAT }°N است و بالاتر کاشی ندارد؛ با ساخت تازه‌ای که bbox تا ۴۲٫۵ دارد (scripts/tp-tiles-update) دیده می‌شود` );
					if ( SHOTS ) { await sleep( 600 ); await page.screenshot( { path: path.join( DIR, `${ name }-${ lang }.jpg` ), type: 'jpeg', quality: 80 } ); }
					continue;
				}
				check( r.n > 0, `${ lang } ${ name }: برچسب آب (${ r.names.join( ' · ' ) }) رندر می‌شود`, JSON.stringify( r ) );
				if ( SHOTS ) { await sleep( 600 ); await page.screenshot( { path: path.join( DIR, `${ name }-${ lang }.jpg` ), type: 'jpeg', quality: 80 } ); }
			}
			await ctx.close();
		}

		/* ── ۳ مرز ───────────────────────────────────────────────────── */
		console.log( '\n── مرز محدوده ──' );
		const up = JSON.parse( execFileSync( ENV.php, [ ENV.wpCli, '--path=' + ENV.wp, 'eval', 'echo json_encode( array( "dir" => wp_get_upload_dir()["basedir"], "uid" => get_post_meta( 99, "entity_uid", true ) ) );' ], { cwd: WS, encoding: 'utf8' } ) );
		const key = String( up.uid ).replace( /^https?:\/\/[^/]+\/e\//, '' ).replace( /\//g, '__' );
		const dir = path.join( up.dir, 'tiles', 'boundaries' );
		const file = path.join( dir, key + '.geojson' );
		const had = fs.existsSync( file );
		if ( had ) { console.log( '  ℹ︎ فایل مرز واقعی هست — آزمون مرز از آن استفاده می‌کند و پاکش نمی‌کند' ); }
		else {
			fs.mkdirSync( dir, { recursive: true } );
			/* فقط برای آزمون: مستطیل ساختگی دور اصفهان — پس از آزمون پاک می‌شود */
			fs.writeFileSync( file, JSON.stringify( { type: 'Feature', properties: {}, geometry: { type: 'MultiPolygon', coordinates: [ [ [ [ 49.5, 30.0 ], [ 55.5, 30.0 ], [ 55.5, 34.5 ], [ 49.5, 34.5 ], [ 49.5, 30.0 ] ] ] ] } } ) );
		}
		try {
			const { ctx, page } = await open( browser, '/province/isfahan/', 'light' );
			const cfg = await page.evaluate( () => JSON.parse( document.querySelector( '[data-tp-map-cfg]' ).getAttribute( 'data-tp-map-cfg' ) ) );
			check( !! cfg.bound && /tiles\/boundaries\/province__isfahan\.geojson\?v=\d+$/.test( cfg.bound ), 'استان اصفهان: cfg.bound به فایل مرز همین موجودیت (نسخه در نشانی)', String( cfg.bound ) );
			await fullMap( page );
			await sleep( 1500 );
			const r = await page.evaluate( () => {
				const m = document.querySelector( '[data-tp-map-full]' ).tpMap;
				const b = m.getBounds();
				return { src: !! m.getSource( 'tp-bound' ), fill: !! m.getLayer( 'tp-bound-fill' ), line: !! m.getLayer( 'tp-bound-line' ), zoom: m.getZoom(), w: b.getEast() - b.getWest() };
			} );
			check( r.src && r.fill && r.line && r.w > 4, 'نقشهٔ کامل: منبع و لایه‌های خط + پرشدگی مرز نشسته و نقشه روی همان محدوده تنظیم شده', JSON.stringify( r ) );
			/* تغییر حالت رنگ: مرز دوباره می‌نشیند */
			await page.evaluate( () => document.documentElement.setAttribute( 'data-tp-mode', 'dark' ) );
			await page.waitForFunction( () => { const m = document.querySelector( '[data-tp-map-full]' ).tpMap; return m.isStyleLoaded() && m.getLayer( 'tp-bound-line' ); }, null, { timeout: 60000 } ).catch( () => {} );
			const again = await page.evaluate( () => !! document.querySelector( '[data-tp-map-full]' ).tpMap.getLayer( 'tp-bound-line' ) );
			check( again, 'پس از تغییر حالت رنگ (شب) مرز دوباره روی سبک تازه می‌نشیند', '' );
			if ( SHOTS ) { await sleep( 600 ); await page.screenshot( { path: path.join( DIR, 'bound-isfahan.jpg' ), type: 'jpeg', quality: 80 } ); }
			await ctx.close();
		} finally {
			if ( ! had ) { fs.rmSync( file, { force: true } ); try { fs.rmdirSync( dir ); } catch ( e ) {} }
		}
		check( had || ! fs.existsSync( file ), 'پاک‌سازی: فایل مرز موقتِ آزمون برداشته شد', '' );
	} finally { await browser.close(); }

	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
