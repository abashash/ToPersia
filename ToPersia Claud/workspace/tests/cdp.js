/**
 * لولهٔ مشترک CDP — راه‌اندازی کروم بی‌سر و یک برگه که می‌شود رانَدش.
 *
 * چرا ماژول شد: سه راننده داشتیم و هر سه همین چهل خط را از نو نوشته
 * بودند. وقتی کروم ۱۵۳ رفتار را عوض کرد، باید سه جا وصله می‌شد.
 *
 * فقط لوله است: چیزی دربارهٔ کنتراست یا هوا نمی‌داند.  P5-D198
 */

'use strict';

const { spawn } = require( 'node:child_process' );
const os = require( 'node:os' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );

const READY_MS = 15000;

const FLAGS = [
	'--headless',
	'--disable-gpu',
	'--no-sandbox',
	'--no-first-run',
	'--no-default-browser-check',
	'--disable-extensions',
	'--hide-scrollbars',
	'--force-device-scale-factor=1',
	'--window-size=1440,900',
	/* بدون این، صفحهٔ file:// نه CSS همسایه را می‌خواند نه ذخیره‌گاه
	   نشست را — و آزمون بی‌صدا چیز دیگری می‌سنجد */
	'--allow-file-access-from-files'
];

/**
 * کشتنِ کلِ درختِ فرایند.
 *
 * `child.kill()` تنها راه‌اندازِ کروم را می‌کشد. روی ویندوز رندرکننده و
 * GPU و سرویس‌ها زنده می‌مانند و به هیچ‌کس وصل نیستند. یک نشستِ آزمون
 * ۱۰۶ درختِ یتیم جا گذاشت — ۸۳۱ فرایند — و همان مجموعه‌ای که ۸ ثانیه
 * طول می‌کشید به ۱۹ تا ۴۸ ثانیه رسید.  P5-D199
 */
function killTree( child ) {

	return new Promise( resolve => {

		if ( ! child || ! child.pid ) { return resolve(); }

		if ( 'win32' === process.platform ) {
			const killer = spawn( 'taskkill', [ '/PID', String( child.pid ), '/T', '/F' ], { stdio: 'ignore' } );
			killer.on( 'exit', () => resolve() );
			killer.on( 'error', () => { try { child.kill( 'SIGKILL' ); } catch ( e ) {} resolve(); } );
			return;
		}

		try { child.kill( 'SIGKILL' ); } catch ( e ) {}
		resolve();
	} );
}

/** یک نشست CDP: پاسخِ فرمان‌ها و رویدادها. */
function session( wsUrl ) {

	const ws = new WebSocket( wsUrl );
	const pending = new Map();
	const listeners = new Map();
	let seq = 0;

	const ready = new Promise( ( resolve, reject ) => {
		ws.addEventListener( 'open', () => resolve() );
		ws.addEventListener( 'error', () => reject( new Error( 'اتصال CDP باز نشد' ) ) );
	} );

	ws.addEventListener( 'message', ev => {

		let msg;
		try { msg = JSON.parse( ev.data ); } catch ( e ) { return; }

		if ( msg.id && pending.has( msg.id ) ) {
			const { resolve, reject } = pending.get( msg.id );
			pending.delete( msg.id );
			msg.error ? reject( new Error( msg.error.message ) ) : resolve( msg.result );
			return;
		}

		if ( msg.method && listeners.has( msg.method ) ) {
			for ( const fn of listeners.get( msg.method ) ) { fn( msg.params ); }
		}
	} );

	return {
		ready,
		send( method, params ) {
			const id = ++seq;
			return new Promise( ( resolve, reject ) => {
				pending.set( id, { resolve, reject } );
				ws.send( JSON.stringify( { id, method, params: params || {} } ) );
			} );
		},
		on( method, fn ) {
			if ( ! listeners.has( method ) ) { listeners.set( method, [] ); }
			listeners.get( method ).push( fn );
		},
		off( method, fn ) {
			const list = listeners.get( method );
			if ( ! list ) { return; }
			const i = list.indexOf( fn );
			if ( i >= 0 ) { list.splice( i, 1 ); }
		},
		close() { try { ws.close(); } catch ( e ) {} }
	};
}

/** برگه — همان نشست، به‌اضافهٔ کارهایی که هر راننده لازم دارد. */
function wrap( cdp, port, id ) {

	const page = {

		send: cdp.send,
		on: cdp.on,

		/**
		 * یک عبارت را در صفحه اجرا می‌کند و مقدارش را برمی‌گرداند.
		 *
		 * استثنای داخل صفحه این‌جا پرتاب می‌شود، نه اینکه `undefined`
		 * برگردد — وگرنه خطای صفحه به‌جای شکست، سبزِ بی‌معنی می‌دهد.
		 */
		async evaluate( expression ) {
			const out = await cdp.send( 'Runtime.evaluate', {
				expression, returnByValue: true, awaitPromise: true
			} );
			if ( out.exceptionDetails ) {
				const d = out.exceptionDetails;
				throw new Error(
					d.text + ' — ' + ( d.exception && d.exception.description || '' )
				);
			}
			return out.result ? out.result.value : null;
		},

		/** می‌رود و تا رویداد `load` منتظر می‌ماند — نه تا مدتی حدسی. */
		navigate( url, timeoutMs ) {
			return new Promise( ( resolve, reject ) => {

				const timer = setTimeout( () => {
					cdp.off( 'Page.loadEventFired', done );
					reject( new Error( 'صفحه بار نشد: ' + url ) );
				}, timeoutMs || 15000 );

				function done() {
					clearTimeout( timer );
					cdp.off( 'Page.loadEventFired', done );
					resolve();
				}

				cdp.on( 'Page.loadEventFired', done );
				cdp.send( 'Page.navigate', { url } ).catch( reject );
			} );
		},

		/** تا وقتی عبارت درست شود — برای آماده‌شدن اسکریپت‌های صفحه. */
		async waitFor( expression, timeoutMs ) {
			return null !== await page.waitValue( expression, timeoutMs );
		},

		/**
		 * مثل `waitFor` ولی خودِ مقدار را می‌دهد.
		 *
		 * منتظرماندن برای نتیجه‌ای که صفحه می‌گذارد، بهتر از حدس‌زدن
		 * مدت است: یک‌بار با `--virtual-time-budget` حدس زدیم و آزمون
		 * روی ماشین کندتر بی‌صدا خالی برگشت.
		 */
		async waitValue( expression, timeoutMs ) {
			const deadline = Date.now() + ( timeoutMs || 10000 );
			for (;;) {
				const value = await page.evaluate( expression );
				if ( value ) { return value; }
				if ( Date.now() > deadline ) { return null; }
				await new Promise( r => setTimeout( r, 100 ) );
			}
		},

		async close() {
			cdp.close();
			try { await fetch( `http://127.0.0.1:${port}/json/close/${id}` ); } catch ( e ) {}
		}
	};

	return page;
}

/**
 * کروم را بالا می‌آورد و دسته‌ای می‌دهد که برگه باز می‌کند.
 *
 * @param {string} chrome  مسیر اجرایی
 * @return {Promise<Object>}  { port, open(url), close() }
 */
async function launch( chrome ) {

	const port = 9400 + Math.floor( Math.random() * 400 );
	const profile = fs.mkdtempSync( path.join( os.tmpdir(), 'tp-cdp-' ) );

	const child = spawn(
		chrome,
		FLAGS.concat( [
			`--remote-debugging-port=${port}`,
			`--user-data-dir=${profile}`,
			'about:blank'
		] ),
		{ stdio: 'ignore' }
	);

	const deadline = Date.now() + READY_MS;
	let browserWs = '';

	for (;;) {
		try {
			const res = await fetch( `http://127.0.0.1:${port}/json/version` );
			if ( res.ok ) {
				const info = await res.json();
				browserWs = info.webSocketDebuggerUrl || '';
				break;
			}
		} catch ( e ) { /* هنوز بالا نیامده */ }

		if ( Date.now() > deadline ) {
			await killTree( child );
			throw new Error( 'کروم بالا نیامد یا پورت اشکال‌زدایی باز نشد' );
		}
		await new Promise( r => setTimeout( r, 120 ) );
	}

	return {

		port,

		/** برگهٔ تازه — حالتِ یکی روی دیگری نشت نکند. */
		async open( url ) {

			const created = await fetch(
				`http://127.0.0.1:${port}/json/new?${encodeURIComponent( url || 'about:blank' )}`,
				{ method: 'PUT' }
			).then( r => r.json() );

			const cdp = session( created.webSocketDebuggerUrl );
			await cdp.ready;

			await cdp.send( 'Runtime.enable' );
			await cdp.send( 'Page.enable' );

			return wrap( cdp, port, created.id );
		},

		/**
		 * بستنِ کامل.
		 *
		 * `Browser.close` تنها راهِ مطمئن است، نه تعارف. کشتنِ درخت روی
		 * ویندوز کار نمی‌کند چون فرایندی که ما spawn کردیم خودش زود
		 * می‌میرد و بچه‌هایش به والدِ دیگری می‌چسبند — `taskkill /T` روی
		 * آن PID به هیچ‌کس نمی‌خورد. با همین اشتباه هر اجرا سی فرایند جا
		 * می‌گذاشت.
		 *
		 * سنجهٔ مرگ هم خودِ نقطهٔ اشکال‌زدایی است: تا وقتی جواب می‌دهد،
		 * مرورگر زنده است. این‌طور به‌جای انتظار ثابت، دقیقاً همان‌قدر
		 * صبر می‌کنیم که لازم است.  P5-D199
		 */
		async close() {

			if ( browserWs ) {
				try {
					const b = session( browserWs );
					await Promise.race( [
						b.ready,
						new Promise( ( _, rej ) => setTimeout( () => rej( new Error( 'دیر' ) ), 1000 ) )
					] );
					b.send( 'Browser.close' ).catch( () => {} );
				} catch ( e ) { /* وصل نشد — زور می‌ماند */ }
			}

			const until = Date.now() + 3000;

			for (;;) {
				try {
					await fetch( `http://127.0.0.1:${port}/json/version` );
				} catch ( e ) {
					break;            /* دیگر جواب نمی‌دهد — رفت */
				}
				if ( Date.now() > until ) { break; }
				await new Promise( r => setTimeout( r, 50 ) );
			}

			await killTree( child );

			/* نمایه پس از مرگ فرایند پاک می‌شود؛ پیش از آن قفل است */
			try { fs.rmSync( profile, { recursive: true, force: true } ); } catch ( e ) {}
		}
	};
}

module.exports = { launch, session, FLAGS };
