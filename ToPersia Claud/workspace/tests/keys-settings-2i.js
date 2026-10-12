/**
 * صفحهٔ تنظیمات user-core (کلید Mediana) و خلاصه‌ساز (کلید موتور AI) در پیشخوان واقعی — P5-D355 · R38 · R56
 *
 *   node tests/keys-settings-2i.js [--only=chromium|firefox]
 *
 * مدیر آزمون بی‌گذرواژه (tests/local-login.php tp_test_admin). گزینه‌های خام پیش از شروع گرفته و در پایان عیناً برمی‌گردند
 * (tests/keys-state-2i.php؛ مقدار و autoload). کلید آزمایشی ساختگی است؛ مقدار هیچ کلید واقعی چاپ یا خوانده نمی‌شود.
 * هر مرورگر: ذخیرهٔ خالی (بی تغییر) · کلید تازه (ذخیره؛ در HTML صفحه نیست؛ placeholder «ذخیره شده»؛ autoload=off) ·
 * ذخیرهٔ خالی دوباره (کلید می‌ماند) · پاک‌کردن (تیک) ← کلید رفت.
 */
'use strict';

const { execFileSync } = require( 'node:child_process' );
const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );

const WS   = require( 'node:path' ).resolve( __dirname, '..' );
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const NEEDLE = 'TESTKEY-r38-0123456789abcdef';
const php  = ( args, input ) => execFileSync( ENV.php, args, { cwd: WS, encoding: 'utf8', input } );
const probe = () => JSON.parse( php( [ 'tests/keys-state-2i.php', 'probe', NEEDLE ] ) );

let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + ( note || '' ) ) ); };

async function mediana( page, tag ) {
	const url = ENV.base + '/geo/wp-admin/admin.php?page=topersia-user-core';
	const save = async ( value, clear ) => {
		await page.goto( url, { waitUntil: 'load', timeout: 120000 } );
		await page.click( '[data-tp-tab="conn"]' );
		if ( null !== value ) { await page.fill( 'input[name="topersia_mediana_api_key"]', value ); }
		if ( clear ) { await page.check( 'input[name="topersia_mediana_api_key_clear"]' ); }
		await Promise.all( [ page.waitForNavigation( { waitUntil: 'load', timeout: 120000 } ), page.click( 'form[action="options.php"] input[type=submit]' ) ] );
	};

	await save( '', false );
	let p = probe().topersia_mediana_api_key;
	check( ! p.has && 'off' === p.autoload, `${ tag } Mediana: ذخیرهٔ خالی کلیدی نمی‌سازد؛ autoload=off`, JSON.stringify( p ) );

	await save( NEEDLE, false );
	p = probe().topersia_mediana_api_key;
	await page.goto( url, { waitUntil: 'load', timeout: 120000 } );
	await page.click( '[data-tp-tab="conn"]' );
	const html = await page.content();
	const ph = await page.getAttribute( 'input[name="topersia_mediana_api_key"]', 'placeholder' );
	check( p.has && 'off' === p.autoload, `${ tag } Mediana: کلید تازه ذخیره شد؛ autoload=off`, JSON.stringify( p ) );
	check( ! html.includes( NEEDLE ) && '' === ( await page.inputValue( 'input[name="topersia_mediana_api_key"]' ) ), `${ tag } Mediana: مقدار کلید در HTML صفحه و در فیلد نیست`, 'کلید در HTML' );
	check( !! ph && /ذخیره/.test( ph ), `${ tag } Mediana: placeholder «ذخیره شده — …»`, ph );

	await save( '', false );
	check( probe().topersia_mediana_api_key.has, `${ tag } Mediana: ذخیرهٔ خالی کلید قبلی را نگه می‌دارد`, '' );

	await save( '', true );
	p = probe().topersia_mediana_api_key;
	check( ! p.has && 0 === p.len, `${ tag } Mediana: تیک «پاک‌کردن» تنها راه حذف است و کلید رفت`, JSON.stringify( p ) );
}

async function summarizer( page, tag ) {
	const url = ENV.base + '/geo/wp-admin/admin.php?page=topersia-ai&tab=panel';
	const open = async () => { await page.goto( url, { waitUntil: 'load', timeout: 120000 } ); };
	const submit = async () => Promise.all( [ page.waitForNavigation( { waitUntil: 'load', timeout: 120000 } ), page.click( 'input[name="submit_panel"], input[type=submit][name="submit_panel"]' ) ] );

	await open();
	const before = ( await page.content() ).includes( NEEDLE );
	await page.click( '#tpas-add-engine' );
	const row = page.locator( '#tpas-engines-body tr' ).last();
	await row.locator( 'select[data-tpas-provider]' ).selectOption( { index: 1 } );
	await row.locator( 'input[type=password]' ).fill( NEEDLE );
	await submit();
	let p = probe().tpas_settings;
	check( ! before && p.has && 'off' === p.autoload, `${ tag } خلاصه‌ساز: کلید تازهٔ موتور ذخیره شد؛ autoload=off`, JSON.stringify( p ) );

	await open();
	const html = await page.content();
	const ph = await page.getAttribute( '#tpas-engines-body tr:last-child input[type=password]', 'placeholder' );
	check( ! html.includes( NEEDLE ) && '' === ( await page.inputValue( '#tpas-engines-body tr:last-child input[type=password]' ) ), `${ tag } خلاصه‌ساز: مقدار کلید در HTML پیشخوان نیست`, 'کلید در HTML' );
	check( !! ph && /Saved/.test( ph ), `${ tag } خلاصه‌ساز: placeholder «Saved — type a new value to change it»`, ph );

	await submit();
	check( probe().tpas_settings.has, `${ tag } خلاصه‌ساز: ذخیرهٔ خالی کلید قبلی را نگه می‌دارد`, '' );

	await open();
	await page.check( '#tpas-engines-body tr:last-child input[type=checkbox][name$="[api_key_clear]"]' );
	await submit();
	p = probe().tpas_settings;
	check( ! p.has, `${ tag } خلاصه‌ساز: «Clear stored key» کلید را پاک کرد`, JSON.stringify( p ) );
}

( async () => {
	const login = JSON.parse( php( [ 'tests/local-login.php', 'tp_test_admin' ] ) );
	const snap = php( [ 'tests/keys-state-2i.php', 'snapshot' ] );
	console.log( 'START ' + new Date().toISOString() );
	try {
		for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ].filter( b => ! ARGS.only || ARGS.only === b[0] ) ) {
			const browser = await type.launch();
			try {
				const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
				await ctx.addCookies( login.cookies );
				const page = await ctx.newPage();
				console.log( `\n── ${ name } ──` );
				await mediana( page, name );
				await summarizer( page, name );
			} finally { await browser.close(); }
		}
	} finally {
		php( [ 'tests/keys-state-2i.php', 'restore' ], snap );
		const after = php( [ 'tests/keys-state-2i.php', 'snapshot' ] );
		check( after === snap, 'برگرداندن: گزینه‌های خام عیناً همان پیش از آزمون (مقدار و autoload)', 'متفاوت' );
	}
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
