/**
 * خلاصه‌ساز 1.3.0 · P5-D358 — تولید فقط به دست مدیر، روی پیشخوان واقعی  (فقط topersia.local)
 *
 *   node tests/ai-admin-local.js [--only=chromium|firefox]
 *
 * با مدیر آزمون (tests/local-login.php tp_test_admin) و یک شهر موقتِ منتشرشده (tests/ai-admin-local.php):
 *   ۱ متاباکس «ToPersia AI»: سه ردیف (خلاصه · نکات · برنامه) به زبان خودِ پست
 *   ۲ دکمهٔ «Generate» ← تولید با موتور داخلی، وضعیت ردیف «تازه»، دکمه «Regenerate»، ردیف در پایگاه؛ نانس نادرست رد می‌شود
 *   ۳ کنش گروهی «Generate AI summary» در فهرست پست‌ها ← فقط خروجی‌های نساخته وارد صف می‌شوند (hook tpas_generate، گروه topersia)؛ تازه دوباره نمی‌آید
 *   ۴ اجرای صف (run_job) ← ردیف‌ها ساخته می‌شوند
 *   ۵ صفحهٔ عمومی همان پست هیچ درخواست tpas_ai_generate نمی‌فرستد
 * همه‌چیز در پایان، حتی با خطا، برمی‌گردد. در مرورگر دوم پست موقتِ تازه ساخته می‌شود.
 */
'use strict';

const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );

const WS   = path.resolve( __dirname, '..' );
const CLI  = [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + ENV.base ];
const ARGS = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const wp   = ( ...a ) => JSON.parse( execFileSync( ENV.php, [ ...CLI, 'eval-file', 'tests/ai-admin-local.php', ...a ], { cwd: WS, encoding: 'utf8' } ).trim().split( '\n' ).pop() );
let bad = 0, n = 0;
const check = ( ok, label, note ) => { n++; if ( ! ok ) { bad++; } console.log( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + ( note || '' ) ) ); };

( async () => {
	console.log( 'START ' + new Date().toISOString() );
	const login = JSON.parse( execFileSync( ENV.php, [ 'tests/local-login.php', 'tp_test_admin' ], { cwd: WS, encoding: 'utf8' } ) );

	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ].filter( b => ! ARGS.only || ARGS.only === b[ 0 ] ) ) {
		console.log( '\n── ' + name + ' ──' );
		let id = 0;
		const browser = await type.launch();
		try {
			id = wp( 'setup' ).id;
			const before = wp( 'state', String( id ) );
			check( id > 0 && 0 === before.rows.length && 0 === before.pending.length, name + ': پست موقت #' + id + ' منتشر شد؛ بی خروجی و بی کار در صف', JSON.stringify( before ) );

			const ctx = await browser.newContext( { viewport: { width: 1440, height: 1100 } } );
			await ctx.addCookies( login.cookies );
			const page = await ctx.newPage();
			page.setDefaultTimeout( 120000 );
			await page.route( /admin-ajax\.php/, r => ( /action=heartbeat/.test( r.request().postData() || '' ) ? r.fulfill( { status: 200, contentType: 'application/json', body: '{}' } ) : r.continue() ) );

			/* ۱ متاباکس */
			await page.goto( ENV.base + '/geo/wp-admin/post.php?post=' + id + '&action=edit', { waitUntil: 'domcontentloaded' } );
			await page.waitForSelector( '.tpas-gen' );
			const box = await page.evaluate( () => ( { rows: [ ...document.querySelectorAll( '.tpas-gen__row' ) ].map( r => r.dataset.type + ':' + r.querySelector( '.tpas-gen__status' ).dataset.status + ':' + r.querySelector( '.tpas-gen__go' ).textContent.trim() ), lang: document.querySelector( '.tpas-gen code' ).textContent } ) );
			check( 'summary:none:Generate,keypoints:none:Generate,plan:none:Generate' === box.rows.join() && 'fa' === box.lang, name + ': متاباکس «ToPersia AI» — سه ردیف «ساخته نشده» به زبان پست (fa)', JSON.stringify( box ) );

			/* ۲ Generate / Regenerate */
			const resp = page.waitForResponse( r => /admin-ajax/.test( r.url() ) && /tpas_admin_generate/.test( r.request().postData() || '' ) );
			await page.locator( '.tpas-gen__row[data-type="summary"] .tpas-gen__go' ).click();
			const body = await ( await resp ).json();
			await page.waitForFunction( () => document.querySelector( '.tpas-gen__row[data-type="summary"] .tpas-gen__status' ).dataset.status === 'fresh' );
			const after = await page.evaluate( () => document.querySelector( '.tpas-gen__row[data-type="summary"] .tpas-gen__go' ).textContent.trim() );
			check( body.success && 'Regenerate' === after, name + ': دکمهٔ «Generate» خلاصه را ساخت؛ وضعیت «تازه» و دکمه «Regenerate»', JSON.stringify( body ).slice( 0, 160 ) );
			const st1 = wp( 'state', String( id ) );
			check( st1.rows.some( r => 'summary' === r.type && 'fa' === r.language ), name + ': ردیف خلاصه به زبان پست در پایگاه', JSON.stringify( st1.rows ) );

			const forged = await ctx.request.post( ENV.base + '/geo/wp-admin/admin-ajax.php', { form: { action: 'tpas_admin_generate', nonce: 'bad', post: String( id ), type: 'plan' } } );
			check( [ 400, 403 ].includes( forged.status() ), name + ': نانس نادرست ← رد', String( forged.status() ) );

			/* ۳ کنش گروهی */
			await page.goto( ENV.base + '/geo/wp-admin/edit.php?post_type=tp_city&s=' + encodeURIComponent( 'شهر آزمون خلاصه‌ساز' ), { waitUntil: 'domcontentloaded' } );
			await page.check( '#cb-select-' + id );
			await page.selectOption( '#bulk-action-selector-top', 'tpas_generate' );
			await Promise.all( [ page.waitForURL( /tpas_q=/ ), page.click( '#doaction' ) ] );
			const notice = ( await page.textContent( '.notice-info' ) ).trim();
			const st2    = wp( 'state', String( id ) );
			const types  = st2.pending.map( p => p.type ).sort().join();
			check( 'keypoints,plan' === types && st2.pending.every( p => 'topersia' === p.group && 'fa' === p.lang ), name + ': کنش گروهی — فقط نکات و برنامه به صف (hook tpas_generate، گروه topersia، زبان پست)؛ خلاصهٔ تازه نه', types + ' | ' + JSON.stringify( st2.pending ) );
			check( /queued: 2/.test( notice ) && /already fresh: 1/.test( notice ), name + ': گزارش کنش گروهی در پیشخوان', notice.slice( 0, 150 ) );

			/* ۴ اجرای صف */
			const ran = wp( 'run-queue', String( id ) );
			const st3 = wp( 'state', String( id ) );
			check( 0 === st3.pending.length && [ 'keypoints', 'plan', 'summary' ].every( t => st3.rows.some( r => r.type === t ) ), name + ': اجرای صف — هر سه خروجی ساخته شد، صف خالی (' + ran.ran + ' کار)', JSON.stringify( [ ran, st3 ] ) );

			/* ۵ صفحهٔ عمومی */
			const pub = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
			const pp  = await pub.newPage();
			pp.setDefaultTimeout( 120000 );
			const gen = [];
			pp.on( 'request', r => { if ( /admin-ajax\.php/.test( r.url() ) && /tpas_ai_generate/.test( r.postData() || '' ) ) { gen.push( r.url() ); } } );
			await pp.goto( ENV.base + '/?p=' + id, { waitUntil: 'load' } );
			check( 0 === gen.length, name + ': صفحهٔ عمومی همان پست هیچ درخواست tpas_ai_generate نمی‌فرستد', gen.join() );
			await pub.close();
			await ctx.close();
		} catch ( e ) { check( false, name + ': خطا', String( e && e.message || e ).slice( 0, 300 ) ); }
		finally {
			await browser.close();
			if ( id ) { const c = wp( 'cleanup', String( id ) ); check( c.cleaned && ! c.post && 0 === Object.keys( c.left ).length, name + ': پاک‌سازی — پست موقت، ردیف‌ها و کارهای صف برگشت', JSON.stringify( c ) ); }
		}
	}
	console.log( `\nEND ${ new Date().toISOString() } — گذشته: ${ n - bad }   افتاده: ${ bad }` );
	process.exit( bad ? 1 : 0 );
} )();
