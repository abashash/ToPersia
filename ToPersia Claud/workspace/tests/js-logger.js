'use strict';
/**
 * ثبت‌کنندۀ خطای مرورگرِ هسته — SPRINT-4.4 بند ۲٫۵ الف، روی topersia.local.
 *
 *   node tests/js-logger.js
 *
 * ثبت‌کننده فقط برای مدیر است؛ پس با مدیرِ آزمون `tp_test_admin` (کوکی از
 * tests/local-login.php، بی گذرواژه — هرگز مدیر واقعی). هر سناریو دو بار، در
 * کروم و فایرفاکس: با هستۀ ۴٫۱۰٫۰ (پیش از اصلاح — `reference/topersia-core.php`) و ۴٫۱۱٫۰
 * (workspace)، روی همان سایت. جای‌گزینی موقت است و در پایان، حتی با خطا،
 * ۴٫۱۱٫۰ برمی‌گردد.
 *
 *   ترک بی‌درنگ      صفحۀ اصلی باز و، همین که تکۀ پس‌زمینۀ المنتور درخواست شد، رها —
 *                    رفتن به صفحۀ دیگر، بارگذاری دوباره، about:blank. پیش از اصلاح
 *                    ثبت می‌شود، پس از اصلاح نه. همان پرونده‌های ردیف‌های زنده
 *                    (`chunks/background-slideshow.min.js` و `background-video`)؛ پاسخشان
 *                    را پراکسیِ کوچکِ همین آزمون چهار ثانیه نگه می‌دارد (شبکۀ کند، در
 *                    لایۀ شبکه — رهگیریِ Playwright قطع درخواست را در فایرفاکس دیگرگونه
 *                    گزارش می‌کند) تا هنگام رفتن حتماً در راه باشند؛ وگرنه بازتولید به
 *                    بخت زمان‌بندی بسته بود. پراکسی فقط topersia.local را به آپاچی
 *                    می‌رساند، HTTPS را دست‌نخورده تونل می‌کند و topersia.com را نه. فایرفاکس
 *                    بارگذاری‌های در راه را درست پس از beforeunload قطع می‌کند (خطای
 *                    SCRIPT و «failed to load chunk» المنتور)؛ کروم برای بارگذاری
 *                    قطع‌شده خطا نمی‌دهد و بازتولید نمی‌کند.
 *   پس از beforeunload  خطا نگه داشته می‌شود؛ صفحه ماند (رفتن لغو شد) ← پس از
 *                    ده ثانیه یک بار ثبت — قطعی، با beforeunload ساختگی
 *   پس از pagehide   یک بارگذاری شکستۀ واقعی — ثبت نمی‌شود
 *   تکرار            دو خطای یکسان در یک بازدید — یک ارسال
 *   بی نام           عنصری که ویژگی src دارد و خصیصۀ src ندارد — نشانی از getAttribute
 *   یک ردیف          هر ارسال یک ردیف (بافر لاگ پس از تخلیه خالی می‌شود)
 *   عادی             صفحۀ اصلی، استان و شهر تا پایان load — هیچ خطای منبع
 *
 * شمار از جدول لاگ (کانال js، کاربر آزمون، شناسه‌های تازه). ۴۰۴ واقعیِ
 * payments.png با پیشوند /site/ (دادۀ پوستۀ وودمارت، نه کد ما) جدا گزارش
 * می‌شود. تصویرهای جانشین picsum.photos (از این شبکه ۴۰۳) یک PNG کوچک
 * می‌گیرند و هیچ درخواستی به topersia.com نمی‌رود.
 *
 * کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص
 */

const ENV = require( './env.js' );   /* مسیرها و نشانی پایه — tests/env.json (P5-D285) */

const fs   = require( 'node:fs' );
const http = require( 'node:http' );
const net  = require( 'node:net' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );

let chromium, firefox;
try {
	( { chromium, firefox } = require( 'playwright' ) );
} catch ( e ) {
	console.error( 'playwright پیدا نشد — NODE_PATH باید به node_modules کش npx برسد' );
	process.exit( 2 );
}

const WS    = path.resolve( __dirname, '..' );
const PHP   = ENV.php;
const MYSQL = ENV.mysql;
const LIVE  = ENV.wp + '/wp-content/plugins/topersia-core/topersia-core.php';
const NEW   = path.join( WS, 'plugin', 'topersia-core', 'topersia-core.php' );
/* هستۀ ۴٫۱۰٫۰ «پیش از اصلاح» — رونوشت بی‌تغییر بکاپ در reference/. تاریخچۀ git پیش از درون‌ریزی
   ۱ اکتبر (commit 53c6df5) در مخزن تازه نیست؛ ۰ب٫۲ (P5-D284) */
const OLD_FILE = path.join( WS, 'reference', 'topersia-core.php' );
const HOME  = ENV.base + '/';
const PAGES = [ HOME, HOME + 'province/isfahan/', HOME + 'city/isfahan/' ];
const TABLE = 'jwWSU9v_topersia_log';
const KNOWN_404 = /\/site\/wp-content\/themes\/woodmart\/images\/payments\.png$/;
const RESOURCE  = 'بارگذاری منبع شکست';
const TEARDOWN  = /^(?:بارگذاری منبع شکست|وعد)/;   /* خطای منبع، یا وعدۀ رسیدگی‌نشدۀ همان بارگذاری قطع‌شده */
const ROUNDS    = 3;
const CHUNK     = /\/chunks\/background-(?:slideshow|video)\.min\.js/;

/* PNG یک پیکسل */
const PNG = Buffer.from( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64' );

let pass = 0, fail = 0;
function check( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { fail ++; console.log( '  ❌ ' + label + ( note ? '   ← ' + note : '' ) ); }
}
function info( t ) { console.log( '  · ' + t ); }
function head( t ) { console.log( '\n' + t + '\n' + '─'.repeat( 78 ) ); }
const sleep = ms => new Promise( r => setTimeout( r, ms ) );

function sql( q ) {
	return execFileSync( MYSQL, [ '-uroot', '--default-character-set=utf8mb4', '-N', '-B', 'topersia_local', '-e', q ], { encoding: 'utf8' } );
}
function maxId() { return parseInt( sql( `SELECT COALESCE(MAX(id),0) FROM ${ TABLE }` ).trim(), 10 ) || 0; }

/* ردیف‌های تازۀ ثبت‌کنندۀ JS برای کاربر آزمون */
function rows( since, user ) {
	const out = sql( `SELECT id, request_id, message, COALESCE(JSON_UNQUOTE(JSON_EXTRACT(context,'$.file')),''), COALESCE(JSON_UNQUOTE(JSON_EXTRACT(context,'$.page')),'') FROM ${ TABLE } WHERE id > ${ since } AND channel = 'js' AND user_id = ${ user } ORDER BY id` );
	/* mysql.exe ویندوز سطر را با CRLF می‌بندد — بی \r آخرین ستون (page) هرگز برابر نمی‌شد */
	return out.split( /\r?\n/ ).filter( Boolean ).map( l => {
		const [ id, rid, message, file, page ] = l.split( '\t' );
		return { id: +id, rid, message, file, page };
	} );
}
const sends = list => new Set( list.map( x => x.rid ) ).size;

/*
 * هر درخواست ثبت یک بارگذاری کامل وردپرس است (چند ثانیه روی نسخۀ محلی) و
 * keepalive پس از رفتن هم می‌رسد: دست‌کم هشت ثانیه، سپس تا شمار چهار ثانیه ثابت بماند.
 */
async function settle( since, user ) {
	await sleep( 8000 );
	let last = -1, still = 0;
	for ( let i = 0; i < 40 && still < 4; i ++ ) {
		const n = rows( since, user ).length;
		still = n === last ? still + 1 : 0;
		last = n;
		await sleep( 1000 );
	}
	return rows( since, user );
}

/* ── نسخۀ هسته روی سایت محلی ── */
const OLD_SRC = fs.readFileSync( OLD_FILE );
const NEW_SRC = fs.readFileSync( NEW );

if ( ! /Version:\s+4\.10\.0/.test( OLD_SRC.toString( 'utf8' ) ) ) {
	console.error( 'هستۀ ۴٫۱۰٫۰ در ' + OLD_FILE + ' پیدا نشد' );
	process.exit( 2 );
}
if ( ! fs.readFileSync( LIVE ).equals( NEW_SRC ) ) {
	console.error( 'هستۀ نسخۀ محلی با workspace یکی نیست — اول همگام‌سازی' );
	process.exit( 2 );
}

function install( which ) {
	fs.writeFileSync( LIVE, which === 'old' ? OLD_SRC : NEW_SRC );
}

/* OPcache هر دو ثانیه زمان پرونده را می‌سنجد؛ تا ثبت‌کنندۀ درست در HTML دیده شود */
async function confirm( ctx, which ) {
	for ( let i = 0; i < 20; i ++ ) {
		await sleep( 2500 );
		const r = await ctx.request.get( HOME + '?tp-jslog=' + Date.now(), { timeout: 180000 } );
		const html = await r.text();
		if ( html.includes( 'ToPersia js log' ) && ( which === 'new' ) === html.includes( 'leaving=0,held=[]' ) ) { return true; }
	}
	return false;
}

/*
 * پراکسیِ شبکۀ کند. HTTP فقط برای topersia.local و به آپاچی همین ماشین؛ دو
 * تکۀ پس‌زمینۀ المنتور چهار ثانیه نگه داشته می‌شوند (اگر مرورگر زودتر رها
 * کرد، هرگز فرستاده نمی‌شوند). HTTPS با CONNECT تونل می‌شود، جز topersia.com.
 */
function slowProxy() {
	const agent  = new http.Agent( { keepAlive: true } );
	const server = http.createServer( ( req, res ) => {
		let u;
		try { u = new URL( req.url ); } catch ( e ) { res.writeHead( 400 ); res.end(); return; }
		if ( ENV.host !== u.hostname ) { res.writeHead( 204 ); res.end(); return; }
		const headers = Object.assign( {}, req.headers );
		delete headers[ 'proxy-connection' ];
		const go = () => {
			const up = http.request( { host: '127.0.0.1', port: 80, method: req.method, path: u.pathname + u.search, headers, agent }, r => {
				res.writeHead( r.statusCode, r.headers );
				r.pipe( res );
			} );
			up.on( 'error', () => { try { res.writeHead( 502 ); res.end(); } catch ( e ) {} } );
			req.pipe( up );
		};
		if ( CHUNK.test( u.pathname ) ) {
			const t = setTimeout( go, 4000 );
			res.on( 'close', () => clearTimeout( t ) );
		} else {
			go();
		}
	} );
	server.on( 'connect', ( req, sock, head ) => {
		const [ host, port ] = String( req.url ).split( ':' );
		if ( /(^|\.)topersia\.com$/i.test( host ) ) { sock.end( 'HTTP/1.1 403 Forbidden\r\n\r\n' ); return; }
		const up = net.connect( +port || 443, host, () => {
			sock.write( 'HTTP/1.1 200 Connection Established\r\n\r\n' );
			if ( head && head.length ) { up.write( head ); }
			up.pipe( sock );
			sock.pipe( up );
		} );
		up.on( 'error', () => sock.destroy() );
		sock.on( 'error', () => up.destroy() );
	} );
	return new Promise( ok => server.listen( 0, '127.0.0.1', () => ok( server ) ) );
}

async function context( browser, cookies ) {
	const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
	await ctx.route( /topersia\.com/, r => r.fulfill( { status: 204, body: '' } ) );
	await ctx.route( /picsum\.photos/, r => r.fulfill( { status: 200, contentType: 'image/png', body: PNG } ) );
	await ctx.route( /\/tp-jslog-test\//, r => r.fulfill( { status: 404, contentType: 'text/plain', body: '' } ) );
	await ctx.addCookies( cookies );
	return ctx;
}

/* ── سناریوها ── */

/** صفحۀ اصلی باز، و همین که تکۀ پس‌زمینۀ المنتور درخواست شد رها — در راه است. */
async function leave( ctx, how ) {
	const page  = await ctx.newPage();
	const chunk = page.waitForRequest( CHUNK, { timeout: 180000 } ).catch( () => null );
	await page.goto( HOME, { waitUntil: 'commit', timeout: 180000 } );
	if ( ! await chunk ) {
		throw new Error( 'تکۀ پس‌زمینۀ المنتور روی صفحۀ اصلی درخواست نشد' );
	}
	if ( how === 'reload' ) {
		await page.reload( { waitUntil: 'commit', timeout: 180000 } ).catch( () => {} );
	} else {
		await page.goto( how === 'blank' ? 'about:blank' : HOME + 'province/isfahan/', { waitUntil: 'commit', timeout: 180000 } ).catch( () => {} );
	}
	await sleep( 1500 );
	await page.close();
}

/** بارگذاری کامل و اسکرول تا ته. */
async function full( ctx, url ) {
	const page = await ctx.newPage();
	await page.goto( url, { waitUntil: 'load', timeout: 240000 } );
	await page.evaluate( async () => {
		for ( let y = 0; y < document.documentElement.scrollHeight; y += 700 ) {
			window.scrollTo( 0, y );
			await new Promise( r => setTimeout( r, 60 ) );
		}
	} );
	await sleep( 2000 );
	return page;
}

/** یک تصویر شکسته در صفحه — تا خطایش رسید. */
function broken( page, src ) {
	return page.evaluate( src => new Promise( done => {
		const i = document.createElement( 'img' );
		i.onerror = i.onload = () => done();
		i.src = src;
		document.body.appendChild( i );
	} ), src );
}

/** سازوکارها، قطعی — روی صفحۀ کامل‌بارشده. pagehide آخر، چون ثبت را می‌بندد. */
async function mechanisms( page, tag, user ) {

	const out   = {};
	const since = maxId();

	/* تکرار و بی نام */
	await broken( page, '/tp-jslog-test/dup-' + tag + '.png' );
	await broken( page, '/tp-jslog-test/dup-' + tag + '.png' );
	await page.evaluate( tag => {
		const x = document.createElement( 'tp-jslog-x' );
		x.setAttribute( 'src', '/tp-jslog-test/attr-' + tag + '.js' );
		document.body.appendChild( x );
		x.dispatchEvent( new Event( 'error' ) );
	}, tag );

	/* پس از beforeunload: نگه داشته؛ صفحه ماند ← پس از ده ثانیه یک بار */
	await page.evaluate( () => window.dispatchEvent( new Event( 'beforeunload' ) ) );
	await broken( page, '/tp-jslog-test/held-' + tag + '.png' );
	await sleep( 6000 );
	out.heldEarly = rows( since, user ).filter( x => x.file.includes( 'held-' + tag ) );
	await sleep( 9000 );

	/* پس از pagehide: یک بارگذاری شکستۀ واقعی */
	await page.evaluate( () => window.dispatchEvent( new PageTransitionEvent( 'pagehide', { persisted: false } ) ) );
	await broken( page, '/tp-jslog-test/after-' + tag + '.png' );

	const all = await settle( since, user );
	out.dup   = all.filter( x => x.file.includes( 'dup-' + tag ) );
	out.attr  = all.filter( x => x.message.includes( 'TP-JSLOG-X' ) );
	out.held  = all.filter( x => x.file.includes( 'held-' + tag ) );
	out.after = all.filter( x => x.file.includes( 'after-' + tag ) );
	out.all   = all;
	return out;
}

( async () => {

	const login = JSON.parse( execFileSync( PHP, [ path.join( __dirname, 'local-login.php' ), 'tp_test_admin' ], { encoding: 'utf8' } ) );
	const user  = login.user;
	const START = maxId();   /* ردیف‌های همین اجرا در پایان پاک می‌شوند — لاگ پیشخوان بی نویز آزمون */
	const res   = {};   /* res[browser][version] */
	const proxy = await slowProxy();
	const PROXY = { server: 'http://127.0.0.1:' + proxy.address().port };

	console.log( 'ثبت‌کنندۀ خطای مرورگر — ۴٫۱۰٫۰ در برابر ۴٫۱۱٫۰، مدیر آزمون #' + user );

	try {
		for ( const which of [ 'old', 'new' ] ) {

			install( which );

			for ( const [ name, type ] of [ [ 'کروم', chromium ], [ 'فایرفاکس', firefox ] ] ) {

				const browser = await type.launch( { proxy: PROXY } );
				const ctx     = await context( browser, login.cookies );

				if ( ! await confirm( ctx, which ) ) {
					await browser.close();
					throw new Error( 'نسخۀ ' + which + ' هسته روی سایت دیده نشد (OPcache؟)' );
				}

				const r = { leave: {}, mech: null, normal: [] };
				head( ( which === 'old' ? '۴٫۱۰٫۰ (پیش از اصلاح)' : '۴٫۱۱٫۰' ) + ' — ' + name );

				/* ترک بی‌درنگ */
				for ( const how of [ 'go', 'reload', 'blank' ] ) {
					const since = maxId();
					for ( let i = 0; i < ROUNDS; i ++ ) { await leave( ctx, how ); }
					const got = ( await settle( since, user ) ).filter( x => TEARDOWN.test( x.message ) && x.page === '/' );
					r.leave[ how ] = got;
					const known = got.filter( x => KNOWN_404.test( x.file ) ).length;
					info( 'ترک بی‌درنگ (' + how + ' × ' + ROUNDS + '): ' + got.length + ' ردیف در ' + sends( got ) + ' ارسال' + ( known ? ' — ' + known + ' از آن‌ها ۴۰۴ واقعی payments.png' : '' )
						+ ( got.length ? '   ' + [ ...new Set( got.filter( x => ! KNOWN_404.test( x.file ) ).map( x => x.message.replace( RESOURCE + ': ', '' ).slice( 0, 48 ) + ' ' + ( x.file.split( '/' ).pop().split( '?' )[0] || '' ) ) ) ].slice( 0, 6 ).join( ' · ' ) : '' ) );
				}

				/* عادی */
				const since = maxId();
				let last;
				for ( const url of PAGES ) {
					if ( last ) { await last.close(); }
					last = await full( ctx, url );
				}
				r.normal = ( await settle( since, user ) ).filter( x => x.message.startsWith( RESOURCE ) );
				info( 'بارگذاری عادی (' + PAGES.length + ' صفحه): ' + r.normal.length + ' ردیف در ' + sends( r.normal ) + ' ارسال'
					+ ( r.normal.length ? '   ' + [ ...new Set( r.normal.map( x => x.message.replace( RESOURCE + ': ', '' ) + ' ' + x.file ) ) ].join( ' · ' ) : '' ) );

				/* سازوکارها */
				const tag = which + '-' + ( type === chromium ? 'cr' : 'ff' ) + '-' + Date.now();
				r.mech = await mechanisms( last, tag, user );
				info( 'سازوکار: تکرار ← ' + sends( r.mech.dup ) + ' ارسال · بی نام ← «' + r.mech.attr.map( x => x.file ).join( '|' ) + '» · پس از beforeunload ← '
					+ r.mech.heldEarly.length + ' ردیف در شش ثانیه، ' + r.mech.held.length + ' ردیف پس از پانزده · پس از pagehide ← ' + r.mech.after.length + ' ردیف' );
				info( 'ردیف به ازای هر ارسال: ' + r.mech.all.length + ' ردیف در ' + sends( r.mech.all ) + ' ارسال' );

				await last.close();
				await browser.close();
				( res[ name ] = res[ name ] || {} )[ which ] = r;
			}
		}
	} finally {
		install( 'new' );
		proxy.close();
	}

	/* ── ادعاها: هر کدام روی ۴٫۱۱٫۰ درست و روی ۴٫۱۰٫۰ (جهش: پیش از اصلاح) نادرست ── */
	head( 'ادعاها' );

	const unknown = list => list.filter( x => ! KNOWN_404.test( x.file ) );
	const reproduced = [];

	for ( const name of Object.keys( res ) ) {

		const o = res[ name ].old, n = res[ name ].new;
		const oldLeave = Object.values( o.leave ).reduce( ( s, l ) => s + unknown( l ).length, 0 );
		const newLeave = Object.values( n.leave ).reduce( ( s, l ) => s + unknown( l ).length, 0 );
		if ( oldLeave ) { reproduced.push( name ); }

		check( name + ' — ترک بی‌درنگ صفحۀ اصلی (سه شیوه × ' + ROUNDS + '): ۴٫۱۱٫۰ هیچ خطای منبع یا وعدۀ قطع‌شده‌ای ثبت نمی‌کند'
			+ ( oldLeave ? '؛ ۴٫۱۰٫۰ در همان سناریو ' + oldLeave + ' ردیف ثبت کرد' : '' ),
			0 === newLeave, newLeave + ' ردیف' );
		if ( ! oldLeave ) {
			info( name + ' — ۴٫۱۰٫۰ هم در ترک بی‌درنگ چیزی ثبت نکرد: این مرورگر برای بارگذاری قطع‌شده رویداد خطا نمی‌دهد؛ سازوکارهای پایین قطعی‌اند' );
		}

		check( name + ' — پس از beforeunload خطا نگه داشته می‌شود؛ صفحه ماند (رفتن لغو شد) ← پس از ده ثانیه یک بار ثبت؛ جهش ۴٫۱۰٫۰ بی‌درنگ',
			0 === n.mech.heldEarly.length && 1 === sends( n.mech.held ) && o.mech.heldEarly.length > 0,
			'۴٫۱۱٫۰: ' + n.mech.heldEarly.length + ' ← ' + n.mech.held.length + ' · ۴٫۱۰٫۰: ' + o.mech.heldEarly.length );
		check( name + ' — پس از pagehide یک بارگذاری شکستۀ واقعی ثبت نمی‌شود؛ جهش ۴٫۱۰٫۰ ثبت می‌کند',
			0 === n.mech.after.length && o.mech.after.length > 0, '۴٫۱۱٫۰: ' + n.mech.after.length + ' · ۴٫۱۰٫۰: ' + o.mech.after.length );
		check( name + ' — دو خطای یکسان در یک بازدید یک ارسال؛ جهش ۴٫۱۰٫۰ دو',
			1 === sends( n.mech.dup ) && 2 === sends( o.mech.dup ), '۴٫۱۱٫۰: ' + sends( n.mech.dup ) + ' · ۴٫۱۰٫۰: ' + sends( o.mech.dup ) );
		check( name + ' — عنصری که فقط ویژگی src دارد، نشانی را از getAttribute می‌گیرد؛ جهش ۴٫۱۰٫۰ بی نام',
			n.mech.attr.length > 0 && n.mech.attr.every( x => /\/tp-jslog-test\/attr-/.test( x.file ) ) && o.mech.attr.length > 0 && o.mech.attr.every( x => '' === x.file ),
			'۴٫۱۱٫۰: «' + n.mech.attr.map( x => x.file ).join( '|' ) + '» · ۴٫۱۰٫۰: «' + o.mech.attr.map( x => x.file ).join( '|' ) + '»' );
		check( name + ' — هر ارسال یک ردیف (بافر لاگ پس از تخلیه خالی)؛ جهش ۴٫۱۰٫۰ دو ردیف با یک شناسۀ درخواست',
			n.mech.all.length === sends( n.mech.all ) && o.mech.all.length === 2 * sends( o.mech.all ),
			'۴٫۱۱٫۰: ' + n.mech.all.length + '/' + sends( n.mech.all ) + ' · ۴٫۱۰٫۰: ' + o.mech.all.length + '/' + sends( o.mech.all ) );

		const nu = unknown( n.normal );
		check( name + ' — بارگذاری عادی صفحۀ اصلی، استان و شهر هیچ خطای منبع ثبت نمی‌کند (جز ۴۰۴ واقعیِ دادۀ پوسته، جدا)',
			0 === nu.length, nu.map( x => x.message + ' ' + x.file ).join( ' · ' ) );
		const known = n.normal.filter( x => KNOWN_404.test( x.file ) );
		if ( known.length ) {
			info( name + ' — ۴۰۴ واقعی: IMG ‏payments.png با پیشوند /site/، ' + known.length + ' ردیف در ' + PAGES.length + ' صفحه — نشانی در xts-woodmart-options (دادۀ پوسته، نه کد ما)' );
		}
	}

	info( reproduced.length
		? 'ترک بی‌درنگ با ۴٫۱۰٫۰ بازتولید شد در: ' + reproduced.join( '، ' ) + ' — پیش از اصلاح ثبت، پس از اصلاح نه'
		: 'ترک بی‌درنگ با ۴٫۱۰٫۰ در هیچ مرورگری بازتولید نشد' );
	check( 'ترک بی‌درنگ دست‌کم در یک مرورگر با ۴٫۱۰٫۰ بازتولید شد — وگرنه ادعای «پس از اصلاح نه» چیزی نمی‌سنجید', reproduced.length > 0 );

	/* هستۀ محلی دوباره همان workspace است */
	check( 'پایان: هستۀ نسخۀ محلی دوباره عیناً ۴٫۱۱٫۰ ِ workspace است', fs.readFileSync( LIVE ).equals( NEW_SRC ) );

	sql( `DELETE FROM ${ TABLE } WHERE id > ${ START } AND user_id = ${ user } AND channel = 'js'` );
	info( 'ردیف‌های لاگِ همین اجرا پاک شد (کاربر آزمون #' + user + ')' );

	console.log( '\n' + '─'.repeat( 78 ) + '\n  گذشته: ' + pass + '   افتاده: ' + fail );
	process.exit( fail ? 1 : 0 );

} )().catch( e => {
	try { install( 'new' ); } catch ( _ ) {}
	console.error( e );
	process.exit( 2 );
} );
