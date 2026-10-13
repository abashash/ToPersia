/**
 * تاریخ رویداد روی صفحهٔ E1 · E3 · E5 — P5-D357  (فقط topersia.local)
 *
 *   node tests/event-dates.js [--only=chromium|firefox] [--shots]
 *
 * داده: tests/event-dates-local.php setup (پست‌های موقت، پاک‌سازی در پایان حتی با خطا).
 *   • سربرگ: «بازه برگزاری» با <time datetime> — fa شمسی با رقم فارسی، en میلادی با رقم لاتین، ar میلادی با رقم عربی
 *   • هر دو تاریخ ← «از … تا …»؛ آغاز = پایان ← یک تاریخ؛ فقط آغاز ← همان؛ فقط پایان ← «تا …»؛ هیچ‌کدام ← هیچ‌چیز
 *   • بلوک هویت (کارت حقایق): «تاریخ آغاز» و «تاریخ پایان» با <time>
 *   • --shots: عکس ۱۴۴۰ و ۳۹۰ در tests/.screens/event-dates/
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );

const WS   = path.resolve( __dirname, '..' );
const CLI  = [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + ENV.base ];
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const SHOT = path.join( __dirname, '.screens', 'event-dates' );
const wp   = ( ...a ) => execFileSync( ENV.php, [ ...CLI, 'eval-file', 'tests/event-dates-local.php', ...a ], { cwd: WS, encoding: 'utf8' } ).trim().split( '\n' ).pop();
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + ( note || '' ) ) ); };

/* مقدارهای مورد انتظار — از قاعدهٔ شمسی/میلادی، نه از خروجی خودِ افزونه */
const EXP = {
	e1: { iso: [ '2027-09-21', '2027-09-24' ], fa: [ '۳۰ شهریور ۱۴۰۶', '۲ مهر ۱۴۰۶' ], en: [ 'September 21, 2027', 'September 24, 2027' ], ar: [ '٢١', '٢٤', '٢٠٢٧' ] },
	e3: { iso: [ '2027-03-05', '2027-03-06' ], fa: [ '۱۴ اسفند ۱۴۰۵', '۱۵ اسفند ۱۴۰۵' ], en: [ 'March 5, 2027', 'March 6, 2027' ], ar: [ '٥', '٦', '٢٠٢٧' ] },
	e5: { iso: [ '2027-06-10', '2027-06-10' ], fa: [ '۲۰ خرداد ۱۴۰۶' ], en: [ 'June 10, 2027' ], ar: [ '١٠', '٢٠٢٧' ] },
};

const read = page => page.evaluate( () => {
	const when = document.querySelector( '.tp-hero__when' );
	const times = [ ...document.querySelectorAll( 'time[datetime^="2027-"]' ) ].map( t => t.getAttribute( 'datetime' ) );
	const hero = document.querySelector( '.tp-hero' );
	const heroTimes = hero ? [ ...hero.querySelectorAll( 'time[datetime]' ) ].map( t => t.getAttribute( 'datetime' ) ) : [];
	const text = document.body.innerText;
	return {
		when: when ? when.textContent.replace( /\s+/g, ' ' ).trim() : null,
		heroTimes, times,
		startTile: /تاریخ آغاز|Start date|تاريخ البدء/.test( text ),
		endTile: /تاریخ پایان|End date|تاريخ الانتهاء/.test( text ),
	};
} );

( async () => {
	console.log( 'START ' + new Date().toISOString() );
	fs.mkdirSync( SHOT, { recursive: true } );
	let made = null; const ids = [];

	try {
		made = JSON.parse( wp( 'setup' ) );
		for ( const by of Object.values( made ) ) { for ( const v of Object.values( by ) ) { ids.push( v.id ); } }

		for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ].filter( b => ! ARGS.only || ARGS.only === b[ 0 ] ) ) {
			console.log( '\n── ' + name + ' ──' );
			const browser = await type.launch();
			try {
				const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
				const page = await ctx.newPage();
				page.setDefaultTimeout( 240000 );

				for ( const key of [ 'e1', 'e3', 'e5' ] ) {
					for ( const lang of [ 'fa', 'en', 'ar' ] ) {
						await page.goto( made[ key ][ lang ].url, { waitUntil: 'load' } );
						const r = await read( page );
						const e = EXP[ key ];
						const want = e[ lang ];
						const textOk = 'ar' === lang ? want.every( s => r.when && r.when.includes( s ) ) : want.every( s => r.when && r.when.includes( s ) );
						const single = e.iso[ 0 ] === e.iso[ 1 ];
						const rangeWord = { fa: 'از', en: 'From', ar: 'من' }[ lang ];
						check( r.when && textOk && ( single ? ! new RegExp( rangeWord + ' ' ).test( r.when ) : r.when.includes( rangeWord ) ) && r.heroTimes.join() === ( single ? e.iso[ 0 ] : e.iso.join() ),
							`${ name } ${ key.toUpperCase() } ${ lang }: سربرگ «بازه برگزاری» — ${ single ? 'یک تاریخ' : 'از … تا …' }، <time datetime> ${ e.iso.join( ' – ' ) }`, JSON.stringify( r ).slice( 0, 220 ) );
						check( r.startTile && r.endTile && r.times.filter( t => t === e.iso[ 0 ] ).length >= 2,
							`${ name } ${ key.toUpperCase() } ${ lang }: بلوک هویت — «تاریخ آغاز» و «تاریخ پایان» با <time>`, JSON.stringify( { s: r.startTile, e: r.endTile, t: r.times } ) );

						if ( ARGS.shots !== undefined && 'chromium' === name && 'e1' === key ) {
							await page.screenshot( { path: path.join( SHOT, `${ key }-${ lang }-1440.png` ) } );
						}
					}
				}

				/* بی تاریخ / فقط آغاز / فقط پایان */
				await page.goto( made.none.fa.url, { waitUntil: 'load' } );
				let r = await read( page );
				check( null === r.when && 0 === r.times.length && ! r.startTile && ! r.endTile, `${ name }: E1 بی تاریخ — نه سطر سربرگ، نه <time>، نه کاشی آغاز/پایان`, JSON.stringify( r ).slice( 0, 200 ) );
				await page.goto( made.only_s.fa.url, { waitUntil: 'load' } );
				r = await read( page );
				check( r.when && r.when.includes( '۳۰ شهریور ۱۴۰۶' ) && ! / تا /.test( r.when ) && r.startTile && ! r.endTile, `${ name }: فقط آغاز — همان تاریخ بی «تا»، فقط کاشی آغاز`, JSON.stringify( r ).slice( 0, 200 ) );
				await page.goto( made.only_e.fa.url, { waitUntil: 'load' } );
				r = await read( page );
				check( r.when && /تا ۲ مهر ۱۴۰۶/.test( r.when ) && ! r.startTile && r.endTile, `${ name }: فقط پایان — «تا ۲ مهر ۱۴۰۶»، فقط کاشی پایان`, JSON.stringify( r ).slice( 0, 200 ) );

				if ( ARGS.shots !== undefined && 'chromium' === name ) {
					const m = await browser.newContext( { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 } );
					const mp = await m.newPage();
					mp.setDefaultTimeout( 240000 );
					for ( const [ key, lang ] of [ [ 'e1', 'fa' ], [ 'e1', 'en' ], [ 'e1', 'ar' ], [ 'e3', 'fa' ], [ 'e5', 'fa' ] ] ) {
						await mp.goto( made[ key ][ lang ].url, { waitUntil: 'load' } );
						await mp.screenshot( { path: path.join( SHOT, `${ key }-${ lang }-390.png` ) } );
						const ov = await mp.evaluate( () => document.documentElement.scrollWidth > document.documentElement.clientWidth );
						check( ! ov, `${ name } ${ key.toUpperCase() } ${ lang } @390: بی سرریز افقی`, '' );
					}
					await m.close();
				}
				await ctx.close();
			} finally { await browser.close(); }
		}
	} catch ( e ) { check( false, 'خطا', String( e && e.stack || e ).slice( 0, 400 ) ); }
	finally {
		if ( ids.length ) { const c = JSON.parse( wp( 'cleanup', ids.join() ) ); check( 0 === c.left, `پاک‌سازی: ${ c.deleted } پست موقت پاک شد`, JSON.stringify( c ) ); }
	}
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
