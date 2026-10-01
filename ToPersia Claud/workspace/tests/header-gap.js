/**
 * فاصلهٔ بالای سربرگ روی زنجیرهٔ واقعیِ سایت — ۴٫۲٫۲ بند ۳ · ۴٫۲٫۳ بند ۱
 *
 * روی صفحهٔ واقعیِ نسخهٔ محلی، نه هارنس: همان هدر وودمارت، همان
 * کانتینرهای المنتور، همان کلاس‌های body. عدد = لبهٔ بالای `.tp-hero`
 * منهای لبهٔ پایینِ `header.whb-header`، در اسکرولِ صفر، با
 * `getBoundingClientRect`. زنجیره فقط توضیح است: برای هر نیا
 * margin/padding/border بالا با `getComputedStyle`، و هر خواهرِ پیشینی
 * که میانِ هدر و سربرگ جا گرفته.
 *
 *   node tests/header-gap.js http://topersia.local/province/isfahan/ [url…]
 *
 * کروم از TP_CHROME یا مسیرهای معمول. خروجی JSON روی stdout.
 */

'use strict';

const fs = require( 'node:fs' );
const { launch } = require( './cdp' );

const CHROMES = [
	process.env.TP_CHROME,
	'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
	'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe'
].filter( Boolean );

const WIDTHS = [
	{ w: 1440, h: 900, mobile: false },
	{ w: 390, h: 844, mobile: true }
];

const PROBE = `( () => {
	scrollTo( 0, 0 );
	const header = document.querySelector( 'header.whb-header' );
	const hero   = document.querySelector( '.tp-page .tp-hero' );
	if ( ! header || ! hero ) { return { error: 'header or hero missing', header: !! header, hero: !! hero }; }
	const hb = header.getBoundingClientRect().bottom;
	const ht = hero.getBoundingClientRect().top;
	const px = v => Math.round( parseFloat( v ) * 100 ) / 100 || 0;
	const name = el => el.tagName.toLowerCase() + ( el.id ? '#' + el.id : '' ) +
		( el.classList.length ? '.' + [ ...el.classList ].slice( 0, 3 ).join( '.' ) : '' );
	const chain = [];
	for ( let el = hero; el && el !== document.documentElement; el = el.parentElement ) {
		if ( el.contains( header ) ) { break; }
		const cs = getComputedStyle( el );
		const before = [];
		for ( let s = el.previousElementSibling; s; s = s.previousElementSibling ) {
			if ( s.contains( header ) || s === header ) { break; }
			const r = s.getBoundingClientRect();
			const scs = getComputedStyle( s );
			if ( r.height > 0 && 'absolute' !== scs.position && 'fixed' !== scs.position ) {
				before.push( { el: name( s ), h: px( r.height ), mt: px( scs.marginTop ), mb: px( scs.marginBottom ) } );
			}
		}
		chain.push( {
			el: name( el ),
			mt: px( cs.marginTop ), pt: px( cs.paddingTop ), bt: px( cs.borderTopWidth ),
			top: px( el.getBoundingClientRect().top ),
			before
		} );
	}
	const hcs = getComputedStyle( header );
	return {
		gap: Math.round( ( ht - hb ) * 100 ) / 100,
		headerBottom: px( hb ), heroTop: px( ht ),
		header: { mb: px( hcs.marginBottom ), position: hcs.position },
		chain
	};
} )()`;

( async () => {

	const urls = process.argv.slice( 2 );
	if ( ! urls.length ) {
		console.error( 'نشانی بده: node tests/header-gap.js http://topersia.local/province/isfahan/' );
		process.exit( 2 );
	}

	const chrome = CHROMES.find( p => fs.existsSync( p ) );
	if ( ! chrome ) { console.error( 'کروم پیدا نشد — TP_CHROME' ); process.exit( 2 ); }

	const browser = await launch( chrome );
	const out = [];

	try {
		for ( const url of urls ) {
			for ( const vp of WIDTHS ) {

				const page = await browser.open( 'about:blank' );

				/* هیچ درخواستی به سایت زنده.  ۴٫۲٫۲ بند ۰ */
				await page.send( 'Network.enable' );
				await page.send( 'Network.setBlockedURLs', { urls: [ '*://topersia.com/*', '*://www.topersia.com/*' ] } );
				await page.send( 'Emulation.setDeviceMetricsOverride', {
					width: vp.w, height: vp.h, deviceScaleFactor: 1, mobile: vp.mobile
				} );
				if ( vp.mobile ) {
					await page.send( 'Emulation.setTouchEmulationEnabled', { enabled: true, maxTouchPoints: 5 } );
				}

				/* نسخهٔ محلی با xdebug کند است — بارِ صفحه تا ۱۲۰ ثانیه */
				await page.navigate( url, 120000 );
				await page.waitFor( 'document.fonts && document.fonts.status === "loaded"', 20000 );
				await new Promise( r => setTimeout( r, 600 ) );

				const res = await page.evaluate( PROBE );
				out.push( Object.assign( { url, width: vp.w }, res ) );
				await page.close();
			}
		}
	} finally {
		await browser.close();
	}

	console.log( JSON.stringify( out, null, 1 ) );
} )().catch( e => { console.error( e.message ); process.exit( 1 ); } );
