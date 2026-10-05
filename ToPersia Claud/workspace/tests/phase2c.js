/**
 * فاز ۲ج SPRINT-4.4 — ادعاهای دیدنی، کروم و فایرفاکس Playwright، روی صفحهٔ واقعیِ محلی.
 *
 *   node tests/phase2c.js [--only=title,stats,…]
 *
 *   stats   ۲ج٫۱ — هر نوعِ دارای کارت آمار: هیچ مقدار دو خط نمی‌شود؛ ستون‌ها به شمار خانه‌ها
 *   title   ۲ج٫۴ — تیتر کشور («ایران») در ۱۴۴۰ و ۳۹۰ یک خط
 *   gallery ۲ج٫۵ — گالری تجمیعی کشور: عکس استان با منبع؛ پیوند منبع در نمای تمام‌صفحه و کلیکش
 *
 * «یک خط» با ارتفاع دیدنی سنجیده می‌شود: سطرهای متن با Range (هر سطر یک
 * مستطیل با `top` تازه)، نه ارتفاع جعبه. درخواست به topersia.com بسته است.
 * کد خروج: ۰ سالم · ۱ افتادن.
 */

const { chromium, firefox } = require( 'playwright' );
const ENV = require( './env.js' );   /* مسیرها و نشانی پایه — tests/env.json (P5-D285) */
const { execFileSync } = require( 'child_process' );
const path = require( 'path' );

const BASE = ENV.base;
const PHP  = ENV.php;

/* نشانیِ هر نوع با کارت آمار — از وردپرس محلی (tests/phase2c-urls.php) */
const STAT_PAGES = ( () => {
	try { return JSON.parse( execFileSync( PHP, [ path.join( __dirname, 'phase2c-urls.php' ) ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'ignore' ] } ) ); }
	catch ( e ) { return {}; }
} )();
const ONLY = ( ( process.argv.find( a => a.startsWith( '--only=' ) ) || '' ).slice( 7 ) ).split( ',' ).filter( Boolean );
const want = k => ! ONLY.length || ONLY.includes( k );

let pass = 0, fail = 0;
function check( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { fail ++; console.log( '  ❌ ' + label + ( note ? '   ← ' + note : '' ) ); }
}

async function open( browser, path, width ) {
	const ctx  = await browser.newContext( { viewport: { width, height: 900 } } );
	await ctx.route( /topersia\.com|picsum\.photos/, r => r.abort() );
	const page = await ctx.newPage();
	await page.goto( BASE + path, { waitUntil: 'domcontentloaded', timeout: 240000 } );
	await page.evaluate( () => document.fonts && document.fonts.ready );
	return { ctx, page };
}

/* شمار سطرِ دیدنیِ متن هر عنصر — مستطیل‌های Range با `top` متمایز (±۲). */
/* شمار سطرِ دیدنیِ متن هر عنصر: مستطیل‌های سطرِ گره‌های متنی، خوشه به مرکز عمودی
   (بازۀ نیم‌ارتفاع سطر) — جعبۀ عنصر و جعبۀ متنِ یک سطر دو سطر شمرده نمی‌شوند. */
const LINES = `( el => {
	const mids = [];
	const walk = document.createTreeWalker( el, NodeFilter.SHOW_TEXT );
	for ( let n = walk.nextNode(); n; n = walk.nextNode() ) {
		if ( ! n.textContent.trim() ) continue;
		const r = document.createRange(); r.selectNodeContents( n );
		for ( const b of r.getClientRects() ) {
			if ( b.width < 1 || b.height < 1 ) continue;
			const m = b.top + b.height / 2;
			if ( ! mids.some( x => Math.abs( x - m ) < b.height / 2 ) ) mids.push( m );
		}
	}
	return mids.length;
} )`;

( async () => {
	for ( const [ bname, engine ] of [ [ 'کروم', chromium ], [ 'فایرفاکس', firefox ] ] ) {

		const browser = await engine.launch();
		console.log( '\n' + bname + '\n' + '─'.repeat( 78 ) );

		/* ۲ج٫۱ — هیچ مقدار کارت آمار در ۱۴۴۰ و ۳۹۰ دو خط نمی‌شود؛ هر نوعی که کارت آمار دارد  P5-D282 */
		if ( want( 'stats' ) ) {
			for ( const [ type, { url, cells } ] of Object.entries( STAT_PAGES ) ) {
				for ( const width of [ 1440, 390 ] ) {
					const { ctx, page } = await open( browser, url, width );
					const got = await page.$$eval( '.tp-stats .tp-stat', ( els, lines ) => els.map( el => {
						const v = el.querySelector( '.tp-stat__v' );
						const b = el.getBoundingClientRect();
						return { v: v.textContent.trim(), lines: eval( lines )( v ), w: Math.round( b.width ) };
					} ), LINES );
					const wrap = got.filter( c => c.lines !== 1 );
					const grid = await page.evaluate( () => { const el = document.querySelector( '.tp-stats' ); return el ? { n: el.getAttribute( 'data-tp-n' ), cols: getComputedStyle( el ).gridTemplateColumns.split( ' ' ).length } : { n: null, cols: 0 }; } );
					const fit  = width > 900 ? grid.cols === got.length : grid.cols <= 2;
					check( `۲ج٫۱ ${ type } ${ width }: ${ got.length } خانۀ آمار، هر مقدار یک خط؛ ستون‌ها به شمار خانه‌ها  P5-D282 · P5-D275`,
						got.length === cells && ! wrap.length && fit, JSON.stringify( { wrap, grid } ) );
					await ctx.close();
				}
			}
		}

		if ( want( 'title' ) ) {
			for ( const width of [ 1440, 390 ] ) {
				const { ctx, page } = await open( browser, '/country/islamic-republic-of-iran/', width );
				const h1 = await page.$eval( '.tp-hero h1', ( el, lines ) => ( { text: el.textContent.trim(), lines: eval( lines )( el ) } ), LINES );
				check( `۲ج٫۴ تیتر کشور «${ h1.text }» در ${ width } یک خط  P5-D281`, 1 === h1.lines && 'ایران' === h1.text, h1.lines + ' سطر' );
				await ctx.close();
			}
		}

		/* ۲ج٫۵ — عکس تجمیعیِ کشور: در نمای تمام‌صفحه کپشن پیوند صفحۀ منبع دارد؛ کلیک همان صفحه  P5-D274 */
		if ( want( 'gallery' ) ) {
			for ( const width of [ 1440, 390 ] ) {
				const { ctx, page } = await open( browser, '/country/islamic-republic-of-iran/', width );
				const shots = await page.evaluate( () => {
					const m = document.getElementById( 'tpGalleryModal' );
					try { return m ? JSON.parse( m.getAttribute( 'data-tp-shots' ) || '[]' ) : []; } catch ( e ) { return []; }
				} );
				const agg = shots.filter( s => s.n );
				let solo = null;
				if ( agg.length ) {
					await page.click( '[data-tp-gallery-open]' );
					await page.waitForSelector( '#tpGalleryModal.tp-show' );
					await page.locator( `#tpGalleryModal [data-tp-shot="${ agg[0].id }"]` ).click();
					await page.waitForSelector( '#tpGallerySolo.tp-show' );
					solo = await page.$eval( '#tpGallerySolo', el => {
						const cap = el.querySelector( '[data-tp-solo-caption]' );
						const a   = cap && cap.querySelector( '.tp-solo__src' );
						if ( ! a ) { return null; }
						const r = a.getBoundingClientRect();
						return {
							tag: a.tagName, href: a.getAttribute( 'href' ), text: a.textContent,
							inherit: getComputedStyle( a ).color === getComputedStyle( cap ).color,
							under: getComputedStyle( a ).textDecorationLine,
							inView: r.width > 0 && r.top >= 0 && r.bottom <= innerHeight && r.left >= 0 && r.right <= innerWidth,
						};
					} );
					if ( solo ) {
						await Promise.all( [
							page.waitForNavigation( { waitUntil: 'domcontentloaded', timeout: 240000 } ),
							page.click( '#tpGallerySolo .tp-solo__src' ),
						] );
					}
				}
				const landed = page.url();
				check( `۲ج٫۵ ${ width }: گالری کشور ${ shots.length } عکس، ${ agg.length } تجمیعی از «${ agg.length ? agg[0].n : '—' }»؛ کپشن تمام‌صفحه پیوند منبع (رنگ کپشن، زیرخط، در قاب) و کلیکش ← صفحۀ منبع  P5-D274`,
					agg.length >= 1 && agg.length < shots.length && agg.every( s => s.u && s.n ) && !! solo && 'A' === solo.tag && solo.href === agg[0].u
						&& solo.text === agg[0].n && solo.inherit && /underline/.test( solo.under ) && solo.inView && landed === agg[0].u,
					JSON.stringify( { n: shots.length, agg: agg.map( s => s.id + ':' + s.n ), solo, landed } ) );
				await ctx.close();
			}
		}

		await browser.close();
	}

	console.log( '\n' + '─'.repeat( 78 ) + `\n  گذشته: ${ pass }   افتاده: ${ fail }` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
