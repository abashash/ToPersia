#!/usr/bin/env node
/**
 * رانندهٔ کروم بی‌سر برای آزمون کنتراست.  P5-D194
 *
 * چرا این فایل هست: تا کروم ۱۵۲ با `--dump-dom` کار می‌کردیم. کروم ۱۵۳
 * آن را حذف کرد و آزمون یک‌شبه مرد — بی‌صدا، با کد خروج صفر و خروجی
 * خالی. CDP قرارداد پایدارِ خود مرورگر است و این‌طور نمی‌شکند.
 *
 * لولهٔ CDP از `cdp.js` می‌آید. پیش‌تر همین چهل خط در سه راننده تکرار
 * شده بود و هر تغییر رفتارِ مرورگر باید سه جا وصله می‌شد.  P5-D199
 *
 * اجرا:  node tests/contrast-run.js <chrome> <file-url> [<file-url> …]
 * خروجی: یک JSON در stdout — آرایه‌ای هم‌ترتیب با آدرس‌های ورودی
 */

'use strict';

const { launch } = require( './cdp.js' );

const PROBE_MS = 20000 * Math.max( 1, parseFloat( process.env.TP_TIMEOUT_X ) || 1 );   /* ضریب ماشین کند — cdp.js */

function fail( message ) {
	process.stderr.write( message + '\n' );
	process.exit( 2 );
}

/**
 * یک صفحه را می‌خواند و نتیجهٔ کاوشگر را برمی‌گرداند.
 *
 * هدفِ تازه برای هر صفحه — حالت یکی روی دیگری نشت نکند.
 */
async function readPage( browser, url ) {

	/*
	 * صفحهٔ واقعیِ نسخهٔ محلی — ۴٫۲٫۳ بند ۱.  P5-D203
	 * کاوشگر در HTML سایت نیست؛ پس از `load` تزریق می‌شود. همان پشتهٔ CSS
	 * واقعی، همان دادهٔ واقعی، همان کلاس‌های بدنه.
	 */
	const live = /^https?:\/\//.test( url );
	const page = await browser.open( live ? 'about:blank' : url );

	try {
		if ( live ) {
			/* هیچ درخواستی به سایت زنده — قلم‌های مرجع نشانیِ topersia.com دارند.  ۴٫۲٫۲ بند ۰ */
			await page.send( 'Network.enable' );
			await page.send( 'Network.setBlockedURLs', { urls: [ '*://topersia.com/*', '*://www.topersia.com/*' ] } );
			await page.send( 'Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false } );
			await page.navigate( url, 120000 );
			await page.waitFor( 'document.fonts && document.fonts.status === "loaded"', 20000 );
			/* جعبهٔ کیفیت هوا (P5-D345) دادهٔ زنده دارد: تا پر شدنش (یا برداشته‌شدن اسکلت پس از خطا) صبر، وگرنه رنگ رده سنجیده نمی‌شود */
			await page.waitFor( '(function(){var a=document.querySelector("[data-tp-air]");return !a||!a.classList.contains("is-load")})()', 45000 ).catch( function () {} );
			await page.evaluate( require( 'fs' ).readFileSync( require( 'path' ).join( __dirname, 'contrast-probe.js' ), 'utf8' ) + ';true' );
		}

		/* کاوشگر نتیجه را روی <body> می‌گذارد؛ منتظرش می‌مانیم به‌جای
		   اینکه مدتی را حدس بزنیم. */
		const value = await page.waitValue(
			'document.body && document.body.getAttribute("data-tp-contrast")',
			live ? 90000 : PROBE_MS
		);

		return value ? JSON.parse( value ) : { error: 'کاوشگر نتیجه‌ای نگذاشت' };

	} finally {
		await page.close();
	}
}

async function main() {

	const chrome = process.argv[ 2 ];
	const urls = process.argv.slice( 3 );

	if ( ! chrome || ! urls.length ) {
		fail( 'اجرا: node tests/contrast-run.js <chrome> <file-url> …' );
	}

	const browser = await launch( chrome );
	const results = [];

	try {
		for ( const url of urls ) {
			results.push( await readPage( browser, url ) );
		}
	} finally {
		await browser.close();
	}

	process.stdout.write( JSON.stringify( results ) );
}

main().catch( err => fail( 'خطا: ' + ( err && err.message ? err.message : err ) ) );
