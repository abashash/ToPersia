/**
 * میزبان‌های بیرونی مسیر رندر — P5-D359  (فقط topersia.local؛ بی VPN)
 *
 *   node tests/ext-hosts.js [--assert] [--out=tests/.screens/ext-hosts.json]
 *
 * استان و شهر اصفهان، fa و en، ۱۴۴۰ و ۳۹۰، کروم: هر درخواستی که میزبانش topersia.local نیست فهرست می‌شود
 * (بار صفحه + اسکرول تا پایین تا بارگذاری تنبل هم دیده شود + ۶ ثانیه آرام‌ماندن)، هر میزبان با:
 *   • شمار و نمونهٔ نشانی، وضعیت پاسخ (یا شکست)، صفحه‌هایی که صدایش زدند
 *   • بازبودن از همین ماشین (HEAD/GET کوتاه، بی پروکسی) — بی VPN
 * --assert: ادعای P5-D359 — هیچ درخواستی به fonts.googleapis.com و fonts.gstatic.com، و هیچ میزبانِ ناباز از ایران در مسیر رندر.
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const X = Math.max( 1, parseFloat( process.env.TP_TIMEOUT_X || '1' ) || 1 );
const sleep = ms => new Promise( r => setTimeout( r, ms ) );
const OWN = new Set( [ 'topersia.local' ] );

const PAGES = [ '/province/isfahan/', '/city/isfahan/', '/en/province/isfahan/', '/en/city/isfahan/' ];
const SIZES = [ [ 1440, 900 ], [ 390, 844 ] ];

async function probe( host ) {
	const t0 = Date.now();
	const ctl = new AbortController();
	const timer = setTimeout( () => ctl.abort(), 9000 );
	try {
		const r = await fetch( 'https://' + host + '/', { method: 'GET', signal: ctl.signal, redirect: 'manual' } );
		return { open: true, status: r.status, ms: Date.now() - t0 };
	} catch ( e ) {
		return { open: false, error: String( ( e.cause && e.cause.code ) || e.name || e.message ).slice( 0, 40 ), ms: Date.now() - t0 };
	} finally { clearTimeout( timer ); }
}

( async () => {
	console.log( 'START ' + new Date().toISOString() );
	const hosts = {};
	const browser = await chromium.launch();

	try {
		for ( const [ w, h ] of SIZES ) {
			for ( const p of PAGES ) {
				const ctx = await browser.newContext( { viewport: { width: w, height: h } } );
				const page = await ctx.newPage();
				page.setDefaultTimeout( 120000 * X );
				const label = p + ' @' + w;
				page.on( 'request', r => {
					let u; try { u = new URL( r.url() ); } catch ( e ) { return; }
					if ( ! /^https?:$/.test( u.protocol ) || OWN.has( u.hostname ) ) { return; }
					const e = hosts[ u.hostname ] || ( hosts[ u.hostname ] = { count: 0, types: {}, pages: new Set(), sample: [], failed: 0, status: {} } );
					e.count++; e.types[ r.resourceType() ] = ( e.types[ r.resourceType() ] || 0 ) + 1; e.pages.add( label );
					if ( e.sample.length < 3 ) { e.sample.push( r.url().slice( 0, 160 ) ); }
				} );
				page.on( 'requestfailed', r => { try { const e = hosts[ new URL( r.url() ).hostname ]; if ( e ) { e.failed++; } } catch ( x ) {} } );
				page.on( 'response', r => { try { const e = hosts[ new URL( r.url() ).hostname ]; if ( e ) { e.status[ r.status() ] = ( e.status[ r.status() ] || 0 ) + 1; } } catch ( x ) {} } );
				await page.goto( ENV.base + p, { waitUntil: 'load', timeout: 240000 * X } ).catch( () => {} );
				await page.evaluate( async () => { for ( let y = 0; y < document.body.scrollHeight; y += 700 ) { window.scrollTo( 0, y ); await new Promise( r => setTimeout( r, 120 ) ); } window.scrollTo( 0, 0 ); } ).catch( () => {} );
				await sleep( 6000 );
				await ctx.close();
				console.log( '  · ' + label + ' — میزبان بیرونی تا اینجا: ' + Object.keys( hosts ).length );
			}
		}
	} finally { await browser.close(); }

	const names = Object.keys( hosts ).sort();
	const rows = [];
	for ( const n of names ) { const e = hosts[ n ]; const pr = await probe( n ); rows.push( { host: n, count: e.count, failed: e.failed, status: e.status, types: e.types, pages: [ ...e.pages ].length, sample: e.sample, probe: pr } ); }

	console.log( '\nmیزبان بیرونی | درخواست | شکست | وضعیت‌ها | از این ماشین' );
	for ( const r of rows ) { console.log( `  ${ r.host } | ${ r.count } | ${ r.failed } | ${ JSON.stringify( r.status ) } | ${ r.probe.open ? 'باز ' + r.probe.status : 'بسته ' + r.probe.error } (${ r.probe.ms }ms)` ); }
	const out = ARGS.out || path.join( __dirname, '.screens', 'ext-hosts.json' );
	fs.mkdirSync( path.dirname( out ), { recursive: true } );
	fs.writeFileSync( out, JSON.stringify( rows, null, 1 ) );

	let bad = 0;
	if ( ARGS.assert !== undefined ) {
		const g = rows.filter( r => /^fonts\.(googleapis|gstatic)\.com$/.test( r.host ) );
		const closed = rows.filter( r => ! r.probe.open );
		console.log( '\n  ' + ( g.length ? '❌' : '✅' ) + ' هیچ درخواستی به fonts.googleapis.com و fonts.gstatic.com   ' + ( g.length ? '← ' + g.map( r => r.host + '×' + r.count ).join() : '' ) );
		console.log( '  ' + ( closed.length ? '❌' : '✅' ) + ' هیچ میزبانِ ناباز از ایران در مسیر رندر   ' + ( closed.length ? '← ' + closed.map( r => r.host ).join() : '' ) );
		bad = ( g.length ? 1 : 0 ) + ( closed.length ? 1 : 0 );
	}
	console.log( `\nEND ${ new Date().toISOString() } — ${ rows.length } میزبان بیرونی${ ARGS.assert !== undefined ? ` — افتاده: ${ bad }` : '' }` );
	process.exit( bad ? 1 : 0 );
} )();
