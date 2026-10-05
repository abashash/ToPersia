/**
 * Duplicator Pro محلی — «Temp directory writable — Fail»  ۲ج٫۱۲
 *
 *   node tests/duplicator-local.js
 *
 * فقط topersia.local، مدیر آزمون (tests/local-login.php tp_test_admin — هیچ
 * گذرواژه‌ای)، کروم. پیش از آزمون از جدول entities (تنظیم‌ها و الگوی [Manual Mode]
 * که صفحۀ «Add New» ذخیره می‌کند) عکس گرفته و در پایان برگردانده می‌شود.
 *
 *   ۱. پوشه‌های Duplicator (duplicator-backups، tmp، tmp/import، imports، logs) هست و نوشتنی.
 *   ۲. «Backups ← Add New» بی «Requirements: Fail» (در بافت Apache، نه خط فرمان).
 *   ۳. یک بستۀ آزمایشی فقط پایگاه‌داده، فقط ذخیره‌گاه پیش‌فرض محلی (ذخیره‌گاه‌های
 *      ابری تیک نمی‌خورند): اسکن، ساخت، وضعیت ۱۰۰، بایگانی و نصب‌کننده روی دیسک.
 *   ۴. پاک‌کردن همان بسته با API خود Duplicator؛ هیچ پرونده‌اش نمی‌ماند.
 *   ۵. هیچ پروندۀ پوشۀ Duplicator در `git status` نیست (P5-D284).
 *
 * چرا رابط، نه `wp duplicator build`: در خط فرمان مسیر خانه («/») از
 * SCRIPT_FILENAME ساخته می‌شود و فرمان پیش از ساخت به همان chdir می‌کند؛ در
 * Apache مسیر خانه درست است (C:/wamp64/www/topersia/). فرمان CLI تنظیم سراسری
 * موتور بایگانی را هم عوض می‌کند.
 *
 * عکس‌ها: tests/.screens/2c12/ (بیرون از git).
 */

'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium } = require( 'playwright' );
const ENV = require( './env.js' );

const WS   = path.resolve( __dirname, '..' );
const PHP  = ENV.php;
const LIST = ENV.base + '/geo/wp-admin/admin.php?page=duplicator';
const DIR  = path.join( WS, 'tests', '.screens', '2c12' );

let pass = 0, fail = 0;
function check( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { fail ++; console.log( '  ❌ ' + label + ( note ? '   ← ' + note : '' ) ); }
}
function head( t ) { console.log( '\n' + t + '\n' + '─'.repeat( 78 ) ); }
function php( args, input ) { return execFileSync( PHP, args, { cwd: WS, encoding: 'utf8', input } ); }
function state( floor ) { return JSON.parse( php( [ 'tests/duplicator-local-state.php', 'state', String( floor ) ] ) ); }
function git( args ) { return execFileSync( 'git', args, { cwd: ENV.root, encoding: 'utf8' } ); }
const sleep = ms => new Promise( r => setTimeout( r, ms ) );

( async () => {

	fs.mkdirSync( DIR, { recursive: true } );
	const snapRaw = php( [ 'tests/duplicator-local-state.php', 'snapshot' ] );
	const snap    = JSON.parse( snapRaw );
	const floor   = snap.floor;
	const login   = JSON.parse( php( [ 'tests/local-login.php', 'tp_test_admin' ] ) );
	const browser = await chromium.launch();
	let built = null;

	try {
		head( '۱ — پوشه‌ها' );
		const dirs = JSON.parse( php( [ 'tests/duplicator-local-state.php', 'dirs' ] ) );
		for ( const [ k, d ] of Object.entries( dirs ) ) {
			check( k + ' هست و نوشتنی — ' + d.path.replace( /^.*\/wp-content\//, 'wp-content/' ), d.dir && d.writable, JSON.stringify( d ) );
		}

		head( '۲ — Add New در Apache' );
		const ctx  = await browser.newContext( { viewport: { width: 1440, height: 1000 } } );
		await ctx.addCookies( login.cookies );
		const page = await ctx.newPage();
		await page.goto( LIST, { waitUntil: 'load', timeout: 120000 } );
		const href = await page.getAttribute( 'a[href*="inner_page=new1"]', 'href' );
		await page.goto( new URL( href, ENV.base ).href, { waitUntil: 'load', timeout: 120000 } );
		const step1 = await page.evaluate( () => ( document.querySelector( '#wpbody-content' ) || document.body ).innerText );
		await page.screenshot( { path: path.join( DIR, 'new1.png' ), fullPage: true } );
		check( 'بی «Requirements: Fail» و بی «Temp directory writable … Fail»', ! /Requirements:\s*Fail/i.test( step1 ) && ! /Temp directory writable\s*Fail/i.test( step1 ),
			( step1.match( /Requirements:[^\n]*\n[^]*?(?=General)/ ) || [ '' ] )[0].slice( 0, 160 ) );
		check( 'ذخیره‌گاه پیش‌فرض: مسیر ویندوزیِ همین نسخه', /C:\/wamp64\/www\/topersia\/geo\/wp-content\/duplicator-backups/.test( step1 ) );

		head( '۳ — بستۀ آزمایشی فقط پایگاه‌داده' );
		/* پیش‌تنظیم «Database Only» خود Duplicator — جعبه‌های اجزا زیر برچسب سفارشی‌اند */
		await page.click( 'label[for="dup-component-shortcut-action-database"]' );
		const comps = await page.evaluate( () => Object.fromEntries( [ 'db', 'core', 'plugins', 'themes', 'uploads', 'other' ].map( c => [ c, document.querySelector( '#package_component_' + c ).checked ] ) ) );
		check( 'اجزا: فقط پایگاه‌داده', comps.db && ! comps.core && ! comps.plugins && ! comps.themes && ! comps.uploads && ! comps.other, JSON.stringify( comps ) );
		for ( const id of [ 13, 10, 11 ] ) {
			if ( await page.isChecked( '#dup-chkbox-' + id ) ) { await page.uncheck( '#dup-chkbox-' + id ); }
		}
		await page.check( '#dup-chkbox-3' );
		const only = await page.evaluate( () => [ ...document.querySelectorAll( 'input[name="_storage_ids[]"]:checked' ) ].map( e => e.value ) );
		check( 'فقط ذخیره‌گاه پیش‌فرض محلی تیک دارد (ابری‌ها نه)', 1 === only.length && '3' === only[0], only.join( ',' ) );

		await Promise.all( [ page.waitForNavigation( { waitUntil: 'load', timeout: 120000 } ), page.click( '#button-next' ) ] );
		const build = page.locator( '#dup-build-button' );
		await build.waitFor( { state: 'visible', timeout: 300000 } );
		await sleep( 1500 );
		const step2 = await page.evaluate( () => ( document.querySelector( '#wpbody-content' ) || document.body ).innerText );
		await page.screenshot( { path: path.join( DIR, 'new2-scan.png' ), fullPage: true } );
		check( 'اسکن تمام شد و دکمۀ ساخت هست', await build.isEnabled(), step2.slice( 0, 200 ) );

		await build.click();
		const t0 = Date.now();
		let last = null, seen = '';
		while ( Date.now() - t0 < 600000 ) {
			await sleep( 4000 );
			last = state( floor );
			const b = last.backups[0];
			if ( b && ! seen.endsWith( ' ' + b.status ) ) { seen += ' ' + b.status; }
			if ( b && ( 100 === b.status || b.status < 0 ) ) { break; }
		}
		console.log( '  ℹ︎ وضعیت‌ها:' + seen );
		/* صفحۀ پیشرفت ساخت عکس‌پذیر نیست (Page.captureScreenshot) — عکس از فهرست پشتیبان‌ها */
		await page.goto( LIST, { waitUntil: 'load', timeout: 120000 } );
		await page.screenshot( { path: path.join( DIR, 'built.png' ), fullPage: false } ).catch( e => console.log( '  ℹ︎ عکس پس از ساخت گرفته نشد: ' + e.message.split( '\n' )[0] ) );
		built = last && last.backups[0];
		check( 'ساخته شد: وضعیت ۱۰۰ در ' + Math.round( ( Date.now() - t0 ) / 1000 ) + ' ثانیه', !! built && 100 === built.status, JSON.stringify( last && last.backups ) );
		check( 'بایگانی و نصب‌کننده روی دیسک ذخیره‌گاه پیش‌فرض', !! built && built.files.archive.exists && built.files.archive.bytes > 0 && built.files.installer.exists,
			built ? JSON.stringify( built.files ) : '' );

		head( '۴ و ۵ — git و پاک‌کردن' );
		const porcelain = git( [ 'status', '--porcelain', '--untracked-files=all', '--', 'geo/wp-content/duplicator-backups' ] );
		check( 'هیچ پروندۀ بسته در git status نیست (پوشه نادیده؛ قفل ردیابی‌شده دست نخورد)', '' === porcelain.trim(), porcelain.trim().split( '\n' ).slice( 0, 4 ).join( ' · ' ) );
		if ( built && built.files.archive.name && built.files.installer.name ) {
			const ign = git( [ 'check-ignore', 'geo/wp-content/duplicator-backups/' + built.files.archive.name, 'geo/wp-content/duplicator-backups/' + built.files.installer.name ] ).trim().split( '\n' ).length;
			check( 'بایگانی و نصب‌کننده هر دو نادیده در git', 2 === ign );
		}
		await ctx.close();
	} finally {
		await browser.close();
		/* ساختِ نیمه‌کاره (شکست آزمون) تا یک دقیقه فرصت پایان دارد، تا پس از پاک‌کردن پرونده‌ای نسازد */
		for ( let i = 0; i < 15; i ++ ) {
			const b = state( floor ).backups[0];
			if ( ! b || 100 === b.status || b.status < 0 ) { break; }
			await sleep( 4000 );
		}
		const del   = JSON.parse( php( [ 'tests/duplicator-local-state.php', 'delete', String( floor ) ] ) );
		/* هر پرونده‌ای که آزمون در پوشۀ Duplicator ساخت و delete() نبرد (tmp/original_files_* رونوشت wp-config دارد) */
		const sweep = JSON.parse( php( [ 'tests/duplicator-local-state.php', 'sweep' ], snapRaw ) );
		const left  = state( floor );
		const extra = left.files.filter( f => ! snap.files.includes( f ) );
		const lost  = snap.files.filter( f => ! left.files.includes( f ) );
		if ( sweep.removed.length ) { console.log( '  ℹ︎ جاروب پس از delete(): ' + sweep.removed.join( ' · ' ) ); }
		check( 'بستۀ آزمایشی پاک شد — ردیف و هر پرونده‌اش؛ پوشه همان پیش از آزمون (' + Object.keys( del.deleted ).join( ',' ) + ')', 0 === del.left && ! left.backups.length && ! extra.length && ! lost.length, JSON.stringify( { del, extra, lost } ) );
		const res = JSON.parse( php( [ 'tests/duplicator-local-state.php', 'restore' ], snapRaw ) );
		check( 'تنظیم‌ها و الگوهای Duplicator به پیش از آزمون برگشت', true === res.restored, JSON.stringify( res ) );
	}

	console.log( '\n  گذشته: ' + pass + '   افتاده: ' + fail );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
