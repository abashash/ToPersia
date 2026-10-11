/**
 * ۲ه٫۳ · P5-D331 ب — آزمون ویرایشگر واقعی پیشخوان برای best_season چندانتخابی.
 *
 *   node tests/wpml-editor-local.js
 *
 * با مدیر آزمون (tests/local-login.php tp_test_admin — هیچ گذرواژه‌ای)، در ویرایشگر کلاسیک وردپرس:
 *   الف) فیلد زندهٔ `best_season` کشور (select چندانتخابی، واژه‌نامهٔ ۳۴) — «بهار» و «پاییز» انتخاب می‌شود
 *   ب) چک‌باکسِ موقتِ جت‌انجین (`tp_tmp_best_season_cb`، ذخیره به‌صورت نگاشت true/false — شکل خطرناک گزارش #1663)
 * سپس «به‌روزرسانی»ِ پست فارسی؛ پست‌های en و ar (با همان trid) را `tests/wpml-editor-local.php verify` می‌خواند.
 * تصاویر: tests/.screens/2f-editor/. پست‌ها و تنظیم‌های موقت در پایان (حتی با خطا) برمی‌گردند. فقط محلی، فقط یک بار.
 */
'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

const WS   = path.resolve( __dirname, '..' );
const SHOT = path.join( __dirname, '.screens', '2f-editor' );
const CLI  = [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + ENV.base ];

function wp( act ) {
	return execFileSync( ENV.php, [ ...CLI, 'eval-file', 'tests/wpml-editor-local.php', act ], { cwd: WS, encoding: 'utf8' } ).trim();
}

fs.mkdirSync( SHOT, { recursive: true } );

( async () => {

	let ids = null, browser = null, code = 0;

	try {
		ids = JSON.parse( wp( 'setup' ).split( '\n' ).pop() );
		console.log( 'پست‌های موقت:', JSON.stringify( ids ) );

		const login = JSON.parse( execFileSync( ENV.php, [ 'tests/local-login.php', 'tp_test_admin' ], { cwd: WS, encoding: 'utf8' } ) );
		browser = await chromium.launch();
		const ctx = await browser.newContext( { viewport: { width: 1440, height: 1100 } } );
		await ctx.addCookies( login.cookies );
		const page = await ctx.newPage();
		page.setDefaultTimeout( 120000 );
		await page.route( /admin-ajax\.php/, r => ( /action=heartbeat/.test( r.request().postData() || '' ) ? r.fulfill( { status: 200, contentType: 'application/json', body: '{}' } ) : r.continue() ) );

		await page.goto( ENV.base + '/geo/wp-admin/post.php?post=' + ids.fa + '&action=edit', { waitUntil: 'domcontentloaded' } );
		await page.waitForSelector( '#post' );

		/* آنچه واقعاً در صفحه است — برای گزارش */
		const dom = await page.evaluate( () => ( {
			select: [ ...document.querySelectorAll( '[name^="best_season"]' ) ].map( e => e.tagName + ':' + e.name + ( e.multiple ? ':multiple' : '' ) + ':' + [ ...e.options || [] ].map( o => o.value ).join( '/' ) ),
			checks: [ ...document.querySelectorAll( '[name^="tp_tmp_best_season_cb"]' ) ].map( e => e.type + ':' + e.name + ':' + e.value ),
		} ) );
		console.log( 'DOM:', JSON.stringify( dom ) );
		await page.screenshot( { path: path.join( SHOT, '1-before.png' ), fullPage: false } );

		/* الف) انتخاب چندگانهٔ زنده: بهار و پاییز */
		/* select2 (cx-ui-select): همان رابط کاربر — باز کردن فهرست و کلیک روی هر گزینه */
		const labels = await page.evaluate( () => ( [ 'spring', 'autumn' ] ).map( v => { const o = document.querySelector( '#best_season option[value="' + v + '"]' ); return o ? o.textContent.trim() : ''; } ) );
		console.log( 'برچسب گزینه‌ها:', JSON.stringify( labels ) );
		for ( const label of labels ) {
			await page.locator( '#best_season + .select2 .select2-selection' ).click();
			await page.locator( '.select2-results__option', { hasText: label } ).first().click();
		}

		/* ب) چک‌باکس‌ها: بهار و پاییز */
		/* چک‌باکس‌های جت‌انجین: برچسب‌های دیدنیِ همان جعبه — بهار و پاییز */
		const boxq = page.locator( '.postbox:has-text("آزمون موقت best_season")' );
		for ( const label of [ 'spring', 'autumn' ] ) {
			await boxq.locator( 'label.cx-checkbox-label', { hasText: label } ).first().click();
		}
		await page.locator( '#tp_tmp_best_season_cb, [name^="tp_tmp_best_season_cb"]' ).first().scrollIntoViewIfNeeded().catch( () => {} );
		await page.screenshot( { path: path.join( SHOT, '2-selected.png' ), fullPage: true } );

		/* ذخیرهٔ واقعی: دکمهٔ «به‌روزرسانی»/«ذخیرهٔ پیش‌نویس» همان فرم پیشخوان */
		const t0 = Date.now();
		page.on( 'request', r => { if ( 'POST' === r.method() && /post\.php/.test( r.url() ) ) { console.log( 'POST post.php آغاز', Math.round( ( Date.now() - t0 ) / 1000 ) + 's' ); } } );
		/* کلیک روی «Save Draft» در این محیط POST نمی‌فرستد (کنترل‌کنندهٔ Rank Math پس از updateMeta فرم را ثبت نمی‌کند)؛ فرم واقعی پیشخوان با همان فیلدهای پرشده به‌صورت بومی ارسال می‌شود */
		await Promise.all( [ page.waitForNavigation( { waitUntil: 'domcontentloaded', timeout: 300000 } ), page.evaluate( () => document.querySelector( 'form#post' ).submit() ) ] );
		console.log( 'ذخیره (ناوبری پس از POST):', Math.round( ( Date.now() - t0 ) / 1000 ) + 's' );
		await page.waitForSelector( '#message, .notice-success, #post', { timeout: 60000 } ).catch( () => {} );
		await page.screenshot( { path: path.join( SHOT, '3-after-save.png' ), fullPage: false } );

		const after = await page.evaluate( () => ( {
			notice: ( document.querySelector( '#message, .notice-success' ) || {} ).innerText || '',
			selected: [ ...document.querySelectorAll( 'select[name^="best_season"] option:checked' ) ].map( o => o.value ),
			checked: [ ...document.querySelectorAll( 'input[type=checkbox][name^="tp_tmp_best_season_cb"]:checked' ) ].map( e => e.value ),
		} ) );
		console.log( 'پس از ذخیره (فرم دوباره بارگذاری‌شده):', JSON.stringify( after ) );

		const v = JSON.parse( wp( 'verify' ).split( '\n' ).pop() );
		console.log( 'متا:', JSON.stringify( v ) );

		const eq = ( a, b ) => JSON.stringify( a ) === JSON.stringify( b );
		const rows = [
			[ 'best_season (select چندگانهٔ زنده)', 'best_season' ],
			[ 'چک‌باکس موقت (نگاشت true/false)', 'tp_tmp_best_season_cb' ],
		];
		let pass = 0, fail = 0;
		for ( const [ label, key ] of rows ) {
			const fa = v.fa && v.fa[ key ], en = v.en && v.en[ key ], ar = v.ar && v.ar[ key ];
			const saved = fa && ( Array.isArray( fa ) ? fa.length : Object.keys( fa ).length );
			const ok = saved && eq( fa, en ) && eq( fa, ar );
			console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ' — fa=' + JSON.stringify( fa ) + ' en=' + JSON.stringify( en ) + ' ar=' + JSON.stringify( ar ) );
			ok ? pass++ : fail++;
		}
		console.log( '\nگذشته: ' + pass + '   افتاده: ' + fail );
		code = fail ? 1 : 0;

	} catch ( e ) {
		console.log( 'خطا:', e.message.split( /\n/ )[0] );
		try { const pg = browser && browser.contexts()[0] && browser.contexts()[0].pages()[0]; if ( pg ) {
			await pg.screenshot( { path: path.join( SHOT, '9-error.png' ), fullPage: true } );
			console.log( 'وضعیت صفحه:', JSON.stringify( await pg.evaluate( () => ( { url: location.href, buttons: [ ...document.querySelectorAll( '#save-post, #publish' ) ].map( b => b.id + ':' + ( b.value || b.textContent ) + ':' + b.disabled ), notices: [ ...document.querySelectorAll( '.notice, #message, .error' ) ].map( n => n.innerText.slice( 0, 120 ) ), sel: [ ...document.querySelectorAll( '#best_season option:checked' ) ].map( o => o.value ), checks: [ ...document.querySelectorAll( 'input[name^="tp_tmp_best_season_cb"]' ) ].map( i => i.name + '=' + i.value + ':' + i.type + ':' + i.checked ) } ) ) ) ); } } catch ( e2 ) {}
		code = 2;
	} finally {
		if ( browser ) { await browser.close(); }
		try { console.log( 'پاک‌سازی:', wp( 'cleanup' ).split( '\n' ).pop() ); } catch ( e ) { console.log( 'پاک‌سازی شکست:', e.message ); }
	}

	process.exit( code );
} )();
