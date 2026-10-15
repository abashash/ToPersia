/**
 * برگزارکننده و وب‌سایت رسمی رویداد — کاشی‌های کارت حقایق و همخوانی با JSON-LD — P5-D361  (فقط topersia.local)
 *
 *   node tests/event-organizer.js [--only=chromium|firefox] [--shots=1]
 *
 * داده: tests/event-dates-local.php setup (پست‌های موقت، پاک‌سازی در پایان حتی با خطا).
 *   • کاشی «برگزارکننده» (organizer_name همان پست؛ ترجمهٔ بی مقدار ← هیچ) و «وب‌سایت رسمی» (ترجمهٔ بی‌مقدار از اصل؛ https معتبر؛ متن پیوند نام دامنه؛ rel=noopener)
 *   • همخوانی: organizer.name و sameAs گرهٔ Event در JSON-LD فقط وقتی هست که همان متن/پیوند روی صفحه دیده می‌شود؛ گرهٔ Thing هیچ‌کدام ندارد ولی کاشی می‌آید
 *   • جهش: همان تابع همخوانی روی صفحهٔ بی کاشی باید رد کند
 *   • سرریز افقی در ۱۴۴۰ و ۳۹۰
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
const SHOT = path.join( __dirname, '.screens', 'event-organizer' );
const wp   = ( ...a ) => execFileSync( ENV.php, [ ...CLI, 'eval-file', 'tests/event-dates-local.php', ...a ], { cwd: WS, encoding: 'utf8' } ).trim().split( '\n' ).pop();
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + ( note || '' ) ) ); };

const LABEL = { org: /^(برگزارکننده|Organizer|الجهة المنظِّمة)$/, web: /^(وب‌سایت رسمی|Official website|الموقع الرسمي)$/ };

/* خواندن صفحه: کاشی‌ها و JSON-LD */
const read = ( page, labels ) => page.evaluate( ( L ) => {
	const tiles = {};
	document.querySelectorAll( '#tp-geo .tp-tile, #tp-geo [class*="tile"]' ).forEach( t => {
		const k = ( t.querySelector( 'span, small, .tp-tile__k' ) || {} ).textContent;
		if ( k ) { tiles[ k.trim() ] = t; }
	} );
	const all = [ ...document.querySelectorAll( '#tp-geo a, #tp-geo b, #tp-geo span, #tp-geo p, #tp-geo div' ) ];
	const find = ( re ) => { const lab = all.find( e => e.children.length === 0 && re.test( e.textContent.trim() ) ); return lab ? lab.parentElement : null; };
	const o = find( new RegExp( L.org ) ), w = find( new RegExp( L.web ) );
	const a = w && w.matches( 'a[href]' ) ? w : null;
	const ld = [];
	document.querySelectorAll( 'script[type="application/ld+json"]' ).forEach( s => { try { const j = JSON.parse( s.textContent ); ( j[ '@graph' ] || [ j ] ).forEach( x => ld.push( x ) ); } catch ( e ) {} } );
	const main = ld.find( x => [].concat( x[ '@type' ] || [] ).some( t => /Event|Festival/.test( t ) ) ) || null;
	return {
		orgText: o ? o.textContent.replace( LabelStrip( L.org ), '' ).trim() : null,
		webText: a ? ( a.querySelector( 'b' ) || a ).textContent.trim() : null, webHref: a ? a.getAttribute( 'href' ) : null, webRel: a ? a.getAttribute( 'rel' ) : null,
		event: main ? { organizer: main.organizer ? main.organizer.name : null, sameAs: main.sameAs || null } : null,
		hasThing: ! main,
		overflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
		body: document.body.innerText,
	};
	function LabelStrip( src ) { return new RegExp( src.replace( /^\^|\$$/g, '' ) ); }
}, { org: LABEL.org.source, web: LABEL.web.source } );

/* همخوانی: JSON-LD ⇒ متن دیدنی */
const consistent = ( r ) => {
	if ( ! r.event ) { return true; }
	const orgOk = ! r.event.organizer || ( r.orgText && r.orgText.includes( r.event.organizer ) );
	const sa = [].concat( r.event.sameAs || [] );
	const sameOk = sa.every( u => u === r.webHref || /wikidata|wikipedia/.test( u ) );
	return !! orgOk && sameOk;
};

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
				const go = async ( key, lang ) => { await page.goto( made[ key ][ lang ].url, { waitUntil: 'load' } ); return read( page ); };

				/* برگزارکننده در هر سه زبان، با متای همان زبان */
				const want = { fa: 'سازمان آزمون', en: 'Test Organizer', ar: 'منظمة الاختبار' };
				for ( const lang of [ 'fa', 'en', 'ar' ] ) {
					const r = await go( 'org', lang );
					check( r.orgText === want[ lang ] && r.webText === 'example.org' && 'https://www.example.org/events/2027' === r.webHref && /noopener/.test( r.webRel || '' ),
						`${ name } ${ lang }: کاشی برگزارکننده «${ want[ lang ] }» و «وب‌سایت رسمی» با متن پیوند نام دامنه (example.org)، rel=noopener`, JSON.stringify( [ r.orgText, r.webText, r.webHref, r.webRel ] ) );
					check( r.event && r.event.organizer === want[ lang ] && [].concat( r.event.sameAs || [] ).includes( 'https://www.example.org/events/2027' ) && consistent( r ),
						`${ name } ${ lang }: JSON-LD Event: organizer و sameAs ← همان متن و پیوندِ دیدنی روی صفحه`, JSON.stringify( r.event ) );
					if ( 'fa' === lang || 'en' === lang ) { check( ! r.overflow, `${ name } ${ lang } @1440: بی سرریز افقی`, '' ); }
					if ( ARGS.shots && 'chromium' === name ) { await page.screenshot( { path: path.join( SHOT, `org-${ lang }-1440.png` ) } ); }
				}

				/* ترجمهٔ بی برگزارکننده: هیچ کاشی و هیچ organizer؛ نشانی رسمی از اصل */
				let r = await go( 'org_gap', 'en' );
				check( null === r.orgText && ! ( r.event && r.event.organizer ) && 'gap.example.org' === r.webText && [].concat( ( r.event || {} ).sameAs || [] ).includes( 'https://gap.example.org/' ) && consistent( r ),
					`${ name } en: ترجمهٔ بی برگزارکننده ← بی کاشی و بی organizer؛ نشانی رسمی (ترجمه خالی) از اصل: کاشی و sameAs هر دو`, JSON.stringify( [ r.orgText, r.webText, r.event ] ) );
				r = await go( 'org_gap', 'fa' );
				check( r.orgText === 'سازمان آزمون' && r.event && r.event.organizer === 'سازمان آزمون', `${ name } fa: همان رویداد روی اصل فارسی برگزارکننده دارد`, JSON.stringify( [ r.orgText, r.event ] ) );

				/* نشانی نامعتبر: http و دامنهٔ خودمان */
				for ( const key of [ 'url_http', 'url_own' ] ) {
					r = await go( key, 'fa' );
					check( null === r.webText && ! ( r.event && r.event.sameAs && [].concat( r.event.sameAs ).some( u => /insecure|topersia\.com\/festival/.test( u ) ) ), `${ name } ${ key }: نه کاشی «وب‌سایت رسمی»، نه sameAs`, JSON.stringify( [ r.webText, r.event ] ) );
				}

				/* گرهٔ Thing: کاشی می‌آید، JSON-LD organizer و sameAs ندارد */
				r = await go( 'org_thing', 'fa' );
				check( r.hasThing && 'سازمان گذشته' === r.orgText && 'past.example.org' === r.webText, `${ name }: رویداد گذشته (گرهٔ Thing) — کاشی‌ها می‌آیند، JSON-LD چیزی از آن‌ها ندارد`, JSON.stringify( [ r.hasThing, r.orgText, r.webText ] ) );

				/* جهش: صفحه‌ای که JSON-LD organizer دارد ولی متنش روی صفحه نیست، رد می‌شود */
				const good = await go( 'org', 'fa' );
				check( consistent( good ) && ! consistent( Object.assign( {}, good, { orgText: null } ) ) && ! consistent( Object.assign( {}, good, { webHref: null } ) ),
					`${ name }: جهش — بی متن برگزارکننده یا بی پیوند، تابع همخوانی رد می‌کند`, '' );

				/* ۳۹۰ */
				const m = await browser.newContext( { viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 } );
				const mp = await m.newPage();
				mp.setDefaultTimeout( 240000 );
				for ( const lang of [ 'fa', 'en', 'ar' ] ) {
					await mp.goto( made.org[ lang ].url, { waitUntil: 'load' } );
					const ov = await mp.evaluate( () => document.documentElement.scrollWidth > document.documentElement.clientWidth );
					check( ! ov, `${ name } ${ lang } @390: بی سرریز افقی با هر دو کاشی`, '' );
					if ( ARGS.shots && 'chromium' === name ) { await mp.screenshot( { path: path.join( SHOT, `org-${ lang }-390.png` ) } ); }
				}
				await m.close();
				await ctx.close();
			} finally { await browser.close(); }
		}
	} catch ( e ) { check( false, 'خطا', String( e && e.stack || e ).slice( 0, 500 ) ); }
	finally {
		if ( ids.length ) { const c = JSON.parse( wp( 'cleanup', ids.join() ) ); check( 0 === c.left, `پاک‌سازی: ${ c.deleted } پست موقت پاک شد`, JSON.stringify( c ) ); }
	}
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
