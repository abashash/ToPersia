/**
 * فاز ۲ج SPRINT-4.4 — ادعاهای دیدنی، کروم و فایرفاکس Playwright، روی صفحهٔ واقعیِ محلی.
 *
 *   node tests/phase2c.js [--only=title,stats,…]
 *
 *   title   ۲ج٫۴ — تیتر کشور («ایران») در ۱۴۴۰ و ۳۹۰ یک خط
 *
 * «یک خط» با ارتفاع دیدنی سنجیده می‌شود: سطرهای متن با Range (هر سطر یک
 * مستطیل با `top` تازه)، نه ارتفاع جعبه. درخواست به topersia.com بسته است.
 * کد خروج: ۰ سالم · ۱ افتادن.
 */

const { chromium, firefox } = require( 'playwright' );

const BASE = 'http://topersia.local';
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
const LINES = `( el => {
	const r = document.createRange(); r.selectNodeContents( el );
	const tops = [];
	for ( const b of r.getClientRects() ) {
		if ( b.width < 1 ) continue;
		if ( ! tops.some( t => Math.abs( t - b.top ) < 2 ) ) tops.push( b.top );
	}
	return tops.length;
} )`;

( async () => {
	for ( const [ bname, engine ] of [ [ 'کروم', chromium ], [ 'فایرفاکس', firefox ] ] ) {

		const browser = await engine.launch();
		console.log( '\n' + bname + '\n' + '─'.repeat( 78 ) );

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
