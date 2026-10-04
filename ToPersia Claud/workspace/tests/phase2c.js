/**
 * فاز ۲ج SPRINT-4.4 — ادعاهای دیدنی، کروم و فایرفاکس Playwright، روی صفحهٔ واقعیِ محلی.
 *
 *   node tests/phase2c.js [--only=title,stats,…]
 *
 *   stats   ۲ج٫۱ — هر نوعِ دارای کارت آمار: هیچ مقدار دو خط نمی‌شود؛ ستون‌ها به شمار خانه‌ها
 *   title   ۲ج٫۴ — تیتر کشور («ایران») در ۱۴۴۰ و ۳۹۰ یک خط
 *
 * «یک خط» با ارتفاع دیدنی سنجیده می‌شود: سطرهای متن با Range (هر سطر یک
 * مستطیل با `top` تازه)، نه ارتفاع جعبه. درخواست به topersia.com بسته است.
 * کد خروج: ۰ سالم · ۱ افتادن.
 */

const { chromium, firefox } = require( 'playwright' );
const { execFileSync } = require( 'child_process' );
const path = require( 'path' );

const BASE = 'http://topersia.local';
const PHP  = process.env.TP_PHP || 'C:\\wamp64\\bin\\php\\php8.3.28\\php.exe';

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

		await browser.close();
	}

	console.log( '\n' + '─'.repeat( 78 ) + `\n  گذشته: ${ pass }   افتاده: ${ fail }` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
