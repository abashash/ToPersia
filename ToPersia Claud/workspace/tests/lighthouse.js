/**
 * سنجش کارایی روی نسخۀ ایستای صفحه — P5-D264 · SPRINT-4.4 بند ۲٫۰٫۷ · قاعدۀ ۰-۸
 *
 *   node tests/lighthouse.js --record --snapshot=DIR [--label=…] [url …]
 *   node tests/lighthouse.js --snapshot=DIR [--runs=5] [--label=…]
 *   node tests/lighthouse.js --ab=DIR_A,DIR_B [--runs=5]
 *
 * چرا ایستا: در فاز ۱ همان کد یک روز ۵۵ و روز بعد ۲۶ گرفت، چون TTFB سرور
 * WAMP از ۴ به ۱۲ ثانیه رفت. عدد Lighthouse آن‌جا بیشتر حال سرور را می‌سنجید
 * تا صفحه را. پس صفحه یک بار ضبط می‌شود و بعد از یک سرور ایستای محلی سرو
 * می‌شود؛ TTFB سرور جدا گزارش می‌شود.
 *
 * --record  هر نشانی یک بار موبایل و یک بار دسکتاپ از خود WAMP بار می‌شود و
 *           **هر** پاسخ (HTML، CSS، JS، قلم، تصویر، REST هوا، و دو شیوه‌نامۀ
 *           Google Fonts المنتور) با سرآیندها و بایت‌های خام — فشرده همان‌طور
 *           که آپاچی فرستاد — در DIR نوشته می‌شود. کلید: کلاس عامل کاربر
 *           (موبایل / دسکتاپ، چون وودمارت برای موبایل HTML دیگری می‌دهد) ·
 *           روش · نشانی کامل · Range · هش بدنه. سپس TTFB سرور: یک گرم‌کن و
 *           پنج نمونه برای هر صفحه و هر REST هوا، و نسخۀ افزونه‌های سایت محلی.
 *
 * بی --record  کروم با `--host-resolver-rules` همۀ درگاه‌های ۸۰ و ۴۴۳ را به
 *           سرور ایستای همین فرایند می‌برد (همان سازوکار Web Page Replay)؛
 *           پس هیچ درخواستی به آپاچی یا اینترنت نمی‌رسد. درخواستی که در
 *           نسخۀ ایستا نیست ۴۰۴ می‌گیرد و فهرست می‌شود؛ پذیرش یعنی صفر.
 *           میانۀ هر معیار از RUNS اجرا، جدا برای هر معیار.
 *
 * --ab      دو نسخۀ ایستا یک‌درمیان (A، B، A، B …) — همان ماشین و همان دقیقه.
 *           بودجه: هیچ امتیازی در B بیش از ۲ واحد زیر A نیست.
 *
 * Lighthouse از TP_LIGHTHOUSE (مسیر cli/index.js) یا از NODE_PATH؛ کرومیوم از
 * CHROME_PATH یا Playwright. گواهی خودامضای سرور HTTPS در --record با
 * openssl در DIR ساخته می‌شود (کروم با `--ignore-certificate-errors`).
 * DIR زیر `tests/.lh-snapshots/` است و در git نمی‌آید.
 *
 * کد خروج: ۰ سالم · ۱ افتادن (بودجه، یا درخواست بیرون از نسخۀ ایستا) · ۲ محیط ناقص
 */

'use strict';

const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const http = require( 'node:http' );
const https = require( 'node:https' );
const crypto = require( 'node:crypto' );
const { execFile, execFileSync } = require( 'node:child_process' );
/* پیش از نخستین کاربرد (LOCAL_PLUGINS · URLS) — پایین‌تر بود و در TDZ می‌افتاد؛ ۰ب٫۱ Lighthouse را اجرا نکرد */
const ENV = require( './env.js' );   /* مسیرها و نشانی پایه — tests/env.json (P5-D285) */

const args   = process.argv.slice( 2 );
const opt    = ( name, def ) => {
	const a = args.find( x => x.startsWith( '--' + name + '=' ) );
	return a ? a.slice( name.length + 3 ) : def;
};
const RECORD = args.includes( '--record' );
const RUNS   = Math.max( 1, parseInt( opt( 'runs', '5' ), 10 ) || 5 );
const LABEL  = opt( 'label', '' );
const SNAP   = opt( 'snapshot', '' );
const AB     = opt( 'ab', '' ).split( ',' ).filter( Boolean );
const URLS   = args.filter( a => /^https?:/.test( a ) );
const BUDGET = 2;
const LOCAL_PLUGINS = process.env.TP_LOCAL_PLUGINS || ENV.wp + '/wp-content/plugins';

function die( code, msg ) {
	console.error( msg );
	process.exit( code );
}

if ( ! SNAP && 2 !== AB.length ) {
	die( 2, 'یکی لازم است: --snapshot=DIR یا --ab=DIR_A,DIR_B' );
}

if ( ! URLS.length ) {
	URLS.push( ENV.base + '/province/isfahan/', ENV.base + '/city/isfahan/' );
}

function findLighthouse() {
	if ( process.env.TP_LIGHTHOUSE ) {
		return process.env.TP_LIGHTHOUSE;
	}
	try {
		return require.resolve( 'lighthouse/cli/index.js' );
	} catch ( e ) {
		return '';
	}
}

const LH = findLighthouse();
if ( ! LH || ! fs.existsSync( LH ) ) {
	die( 2, 'Lighthouse پیدا نشد: TP_LIGHTHOUSE=…/lighthouse/cli/index.js' );
}
const LH_VERSION = require( path.join( path.dirname( LH ), '..', 'package.json' ) ).version;
const CHROME     = process.env.CHROME_PATH || require( 'playwright' ).chromium.executablePath();

/* ── نسخۀ ایستا ── */

const sha1 = b => crypto.createHash( 'sha1' ).update( b ).digest( 'hex' );

/*
 * سرآیندهای یک‌گامی و alt-svc نمی‌مانند: alt-svc کروم را به QUIC روی ۴۴۳
 * می‌فرستد که این‌جا سروری ندارد. content-length را خود سرور می‌نویسد.
 */
const DROP = new Set( [ 'connection', 'keep-alive', 'transfer-encoding', 'content-length', 'proxy-connection', 'upgrade', 'alt-svc' ] );

function keep( headers ) {
	const out = {};
	for ( const [ k, v ] of Object.entries( headers ) ) {
		if ( ! DROP.has( k ) ) {
			out[ k ] = v;
		}
	}
	return out;
}

class Store {

	constructor( dir, create ) {
		this.dir    = path.resolve( dir );
		this.bodies = path.join( this.dir, 'bodies' );
		this.cache  = new Map();
		if ( create ) {
			fs.rmSync( this.dir, { recursive: true, force: true } );
			fs.mkdirSync( this.bodies, { recursive: true } );
			this.m = { format: 1, created: new Date().toISOString(), entries: {} };
		} else {
			const f = path.join( this.dir, 'manifest.json' );
			if ( ! fs.existsSync( f ) ) {
				die( 2, 'نسخۀ ایستا نیست: ' + f + ' — اول --record' );
			}
			this.m = JSON.parse( fs.readFileSync( f, 'utf8' ) );
		}
	}

	get( key ) {
		const e = this.m.entries[ key ];
		if ( ! e ) {
			return null;
		}
		if ( ! this.cache.has( key ) ) {
			this.cache.set( key, fs.readFileSync( path.join( this.bodies, e.file ) ) );
		}
		return { status: e.status, headers: e.headers, body: this.cache.get( key ) };
	}

	put( key, status, headers, body ) {
		const file = sha1( key ) + '.bin';
		fs.writeFileSync( path.join( this.bodies, file ), body );
		this.m.entries[ key ] = { status, headers, file, bytes: body.length };
		this.cache.set( key, body );
	}

	save( meta ) {
		Object.assign( this.m, meta );
		fs.writeFileSync( path.join( this.dir, 'manifest.json' ), JSON.stringify( this.m, null, 1 ) );
	}
}

function keyOf( scheme, req, body ) {
	const ua = /Mobile/.test( req.headers['user-agent'] || '' ) ? 'm' : 'd';
	let k = ua + ' ' + req.method + ' ' + scheme + '://' + String( req.headers.host || '' ).toLowerCase() + req.url;
	if ( req.headers.range ) {
		k += ' range=' + req.headers.range;
	}
	if ( body.length ) {
		k += ' body=' + sha1( body );
	}
	return k;
}

function forward( scheme, req, body ) {
	return new Promise( ( resolve, reject ) => {
		const host    = String( req.headers.host || '' ).toLowerCase();
		const [ hostname, port ] = host.split( ':' );
		const headers = Object.assign( {}, req.headers );
		/* پاسخ کامل ضبط شود، نه ۳۰۴ */
		delete headers['if-none-match'];
		delete headers['if-modified-since'];
		const lib = 'https' === scheme ? https : http;
		const r = lib.request( {
			hostname,
			port: port || ( 'https' === scheme ? 443 : 80 ),
			method: req.method,
			path: req.url,
			headers,
			servername: hostname,
			timeout: 300000,
		}, res => {
			const chunks = [];
			res.on( 'data', c => chunks.push( c ) );
			res.on( 'end', () => resolve( { status: res.statusCode, headers: keep( res.headers ), body: Buffer.concat( chunks ) } ) );
			res.on( 'error', reject );
		} );
		r.on( 'error', reject );
		r.on( 'timeout', () => r.destroy( new Error( 'timeout' ) ) );
		r.end( body );
	} );
}

/*
 * درخواست صفحه یا درخواست پس‌زمینۀ خود کروم؟ کروم بی‌سر حتی با پرچم‌های
 * chrome-launcher به accounts.google.com · update.googleapis.com ·
 * android.clients.google.com · clients2.google.com می‌رود. این‌ها از صفحه
 * نیستند و Referer ندارند. صفحه = میزبان نشانی سنجیده، یا میزبانی که
 * Refererش صفحه است (گذرا: قلمِ شیوه‌نامۀ Google Fonts هم صفحه است).
 * پس‌زمینه هرگز به اینترنت نمی‌رود، ضبط نمی‌شود و ۴۰۴ محلی می‌گیرد؛
 * جدا شمرده و چاپ می‌شود تا اگر درخواستی از صفحه بی‌Referer بود، دیده شود.
 */
function isPage( req, hosts ) {
	const host = String( req.headers.host || '' ).toLowerCase();
	if ( hosts.has( host ) ) {
		return true;
	}
	let ref = '';
	try {
		ref = new URL( req.headers.referer || '' ).host.toLowerCase();
	} catch ( e ) {
		return false;
	}
	if ( hosts.has( ref ) ) {
		hosts.add( host );
		return true;
	}
	return false;
}

function handler( scheme, store, record, stats, hosts ) {
	return ( req, res ) => {
		const chunks = [];
		req.on( 'data', c => chunks.push( c ) );
		req.on( 'end', async () => {
			const body = Buffer.concat( chunks );
			const key  = keyOf( scheme, req, body );
			if ( ! isPage( req, hosts ) ) {
				const h = String( req.headers.host || '?' ).toLowerCase();
				stats.background[ h ] = ( stats.background[ h ] || 0 ) + 1;
				res.writeHead( 404, { 'content-length': 0, 'cache-control': 'no-store' } );
				res.end();
				return;
			}
			try {
				let hit = store.get( key );
				if ( ! hit && record ) {
					hit = await forward( scheme, req, body );
					store.put( key, hit.status, hit.headers, hit.body );
					stats.recorded ++;
				}
				if ( ! hit ) {
					stats.misses.push( key );
					res.writeHead( 404, { 'content-type': 'text/plain', 'content-length': 0, 'cache-control': 'no-store' } );
					res.end();
					return;
				}
				stats.served ++;
				const empty = 204 === hit.status || 304 === hit.status || 'HEAD' === req.method;
				const h     = Object.assign( {}, hit.headers );
				if ( ! ( 204 === hit.status || 304 === hit.status ) ) {
					h['content-length'] = hit.body.length;
				}
				res.writeHead( hit.status, h );
				res.end( empty ? undefined : hit.body );
			} catch ( e ) {
				stats.errors.push( key + ' — ' + e.message );
				res.writeHead( 502, { 'content-length': 0 } );
				res.end();
			}
		} );
	};
}

function listen( server ) {
	return new Promise( ( resolve, reject ) => {
		server.once( 'error', reject );
		server.listen( 0, '127.0.0.1', () => resolve( server.address().port ) );
	} );
}

async function serve( store, record ) {
	const stats = { recorded: 0, served: 0, misses: [], errors: [], background: {} };
	/* ضبط: از میزبان نشانی‌ها؛ پخش: همان میزبان‌هایی که ضبط صفحه شمرد */
	const hosts = new Set( store.m.pageHosts || URLS.map( u => new URL( u ).host.toLowerCase() ) );
	const tls   = {
		key:  fs.readFileSync( path.join( store.dir, 'tls-key.pem' ) ),
		cert: fs.readFileSync( path.join( store.dir, 'tls-cert.pem' ) ),
	};
	const s1 = http.createServer( handler( 'http', store, record, stats, hosts ) );
	const s2 = https.createServer( tls, handler( 'https', store, record, stats, hosts ) );
	const p1 = await listen( s1 );
	const p2 = await listen( s2 );
	return {
		stats,
		hosts,
		rules: 'MAP *:80 127.0.0.1:' + p1 + ',MAP *:443 127.0.0.1:' + p2 + ',EXCLUDE localhost',
		close() {
			for ( const s of [ s1, s2 ] ) {
				s.close();
				s.closeAllConnections();
			}
		},
	};
}

function openssl() {
	const list = [ process.env.TP_OPENSSL, 'openssl', 'C:/Program Files/Git/mingw64/bin/openssl.exe', 'C:/Program Files/Git/usr/bin/openssl.exe' ];
	for ( const bin of list.filter( Boolean ) ) {
		try {
			execFileSync( bin, [ 'version' ], { stdio: 'ignore' } );
			return bin;
		} catch ( e ) {
			/* بعدی */
		}
	}
	die( 2, 'openssl پیدا نشد (برای گواهی خودامضای سرور ایستا): TP_OPENSSL=…' );
}

function makeCert( dir ) {
	execFileSync( openssl(), [
		'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '3650',
		'-keyout', path.join( dir, 'tls-key.pem' ), '-out', path.join( dir, 'tls-cert.pem' ),
		'-subj', '/CN=tp-lh-snapshot',
	], { stdio: 'ignore' } );
}

/* ── Lighthouse ── */

function lighthouse( url, desktop, out, rules ) {

	const flags = [
		LH,
		url,
		'--output=json',
		'--output-path=' + out,
		'--quiet',
		'--only-categories=performance,accessibility,best-practices,seo',
		'--blocked-url-patterns=*topersia.com*',
		/* --no-proxy-server: بی آن کروم ویندوز WPAD می‌پرسد (http://wpad/wpad.dat) که درخواست صفحه نیست */
		'--chrome-flags=--headless=new --no-sandbox --disable-gpu --no-proxy-server --ignore-certificate-errors --host-resolver-rules="' + rules + '"',
		'--max-wait-for-load=120000',
	];

	if ( desktop ) {
		flags.push( '--preset=desktop' );
	}

	const env = Object.assign( {}, process.env, { CHROME_PATH: CHROME } );

	return new Promise( ( resolve, reject ) => {
		execFile( process.execPath, flags, { env, maxBuffer: 256 * 1024 * 1024, timeout: 600000 }, ( err, stdout, stderr ) => {
			/*
			 * ویندوز: chrome-launcher پس از بسته‌شدن کروم پوشۀ موقتش را پاک
			 * می‌کند و کروم هنوز پرونده‌ای را نگه داشته — EPERM در destroyTmp.
			 * گزارش پیش از آن نوشته شده؛ فقط همین خطا بخشوده می‌شود، و فقط
			 * وقتی JSON کامل هست.
			 */
			if ( err ) {
				const why = String( stderr || '' );
				if ( ! /destroyTmp/.test( why ) || ! /EPERM/.test( why ) || ! fs.existsSync( out ) ) {
					reject( new Error( ( why || err.message ).slice( -3000 ) ) );
					return;
				}
			}
			const report = JSON.parse( fs.readFileSync( out, 'utf8' ) );
			if ( report.runtimeError ) {
				reject( new Error( 'Lighthouse: ' + report.runtimeError.code + ' — ' + report.runtimeError.message ) );
				return;
			}
			resolve( report );
		} );
	} );
}

function row( r ) {
	return {
		perf: r.categories.performance.score * 100,
		a11y: r.categories.accessibility.score * 100,
		bp:   r.categories['best-practices'].score * 100,
		seo:  r.categories.seo.score * 100,
		lcp:  r.audits['largest-contentful-paint'].numericValue,
		cls:  r.audits['cumulative-layout-shift'].numericValue,
		tbt:  r.audits['total-blocking-time'].numericValue,
		fcp:  r.audits['first-contentful-paint'].numericValue,
		srt:  r.audits['server-response-time'] ? r.audits['server-response-time'].numericValue : NaN,
	};
}

function median( list ) {
	const s = list.slice().sort( ( a, b ) => a - b );
	const m = Math.floor( s.length / 2 );
	return s.length % 2 ? s[ m ] : ( s[ m - 1 ] + s[ m ] ) / 2;
}

const pad = ( s, n ) => ( String( s ) + ' '.repeat( n ) ).slice( 0, n );

/* ── TTFB سرور، جدا ── */

function ttfbOnce( url ) {
	return new Promise( ( resolve, reject ) => {
		const t0 = process.hrtime.bigint();
		const r  = http.get( url, { agent: false, headers: { 'accept-encoding': 'gzip', 'user-agent': 'tp-ttfb' } }, res => {
			const ms = Number( process.hrtime.bigint() - t0 ) / 1e6;
			res.resume();
			res.on( 'end', () => resolve( { ms, status: res.statusCode } ) );
		} );
		r.on( 'error', reject );
		r.setTimeout( 300000, () => r.destroy( new Error( 'timeout' ) ) );
	} );
}

async function ttfb( url ) {
	await ttfbOnce( url );
	const samples = [];
	let status    = 0;
	for ( let i = 0; i < 5; i ++ ) {
		const t = await ttfbOnce( url );
		samples.push( Math.round( t.ms ) );
		status = t.status;
	}
	return { url, status, median: median( samples ), samples };
}

function pluginVersions() {
	const out = {};
	for ( const [ slug, file ] of [
		[ 'topersia-render', 'topersia-render.php' ],
		[ 'topersia-user-core', 'topersia-user-core.php' ],
		[ 'topersia-ai-summarizer', 'topersia-ai-summarizer.php' ],
		[ 'topersia-core', 'topersia-core.php' ],
	] ) {
		try {
			const head = fs.readFileSync( path.join( LOCAL_PLUGINS, slug, file ), 'utf8' ).slice( 0, 4000 );
			const m    = head.match( /^[ \t*]*Version:\s*(\S+)/m );
			out[ slug ] = m ? m[1] : '?';
		} catch ( e ) {
			out[ slug ] = '—';
		}
	}
	return out;
}

function git() {
	try {
		return execFileSync( 'git', [ 'describe', '--tags', '--always', '--dirty' ], { cwd: __dirname, encoding: 'utf8' } ).trim();
	} catch ( e ) {
		return '?';
	}
}

function printSnapshot( store, tag ) {
	const m     = store.m;
	const e     = Object.values( m.entries );
	const hosts = {};
	for ( const k of Object.keys( m.entries ) ) {
		const h = k.split( ' ' )[2].replace( /^https?:\/\//, '' ).split( '/' )[0];
		hosts[ h ] = ( hosts[ h ] || 0 ) + 1;
	}
	console.log( ( tag ? tag + ' ' : '' ) + 'نسخۀ ایستا: ' + path.basename( store.dir ) + ' · ضبط ' + m.created
		+ ' · git ' + m.git + ( m.label ? ' · ' + m.label : '' ) );
	console.log( '  افزونه‌های سایت محلی هنگام ضبط: ' + Object.entries( m.plugins || {} ).map( ( [ k, v ] ) => k + ' ' + v ).join( ' · ' ) );
	const st = {};
	for ( const x of e ) {
		st[ x.status ] = ( st[ x.status ] || 0 ) + 1;
	}
	console.log( '  ' + e.length + ' پاسخ · ' + e.reduce( ( a, x ) => a + x.bytes, 0 ).toLocaleString( 'en-US' ) + ' بایت خام · '
		+ Object.entries( hosts ).map( ( [ h, n ] ) => h + ' ' + n ).join( ' · ' )
		+ ' · وضعیت ' + Object.entries( st ).map( ( [ s, n ] ) => s + '×' + n ).join( ' ' ) );
	console.log( '  پس‌زمینۀ خود کروم هنگام ضبط (بی Referer صفحه؛ ۴۰۴ محلی، نه ضبط، نه اینترنت): '
		+ ( Object.entries( m.background || {} ).map( ( [ h, n ] ) => h + ' ' + n ).join( ' · ' ) || 'هیچ' ) );
	for ( const t of m.ttfb || [] ) {
		console.log( '  TTFB سرور هنگام ضبط ' + pad( t.url.replace( /^https?:\/\/[^/]+/, '' ), 58 ) + ' ' + t.status
			+ ' · میانه ' + t.median + ' ms · نمونه‌ها ' + t.samples.join( ' · ' ) );
	}
}

/* ── اجرا ── */

async function record() {

	const store = new Store( SNAP, true );
	makeCert( store.dir );
	const srv   = await serve( store, true );
	const tmp   = fs.mkdtempSync( path.join( os.tmpdir(), 'tp-lh-' ) );
	let n       = 0;

	try {
		for ( const url of URLS ) {
			for ( const desktop of [ false, true ] ) {
				await lighthouse( url, desktop, path.join( tmp, 'rec-' + ( n ++ ) + '.json' ), srv.rules );
			}
		}
	} finally {
		srv.close();
		fs.rmSync( tmp, { recursive: true, force: true } );
	}

	if ( srv.stats.errors.length ) {
		die( 1, 'ضبط ناقص:\n' + srv.stats.errors.join( '\n' ) );
	}

	/* TTFB صفحه‌ها و هر REST هوا که صفحه خواست */
	const rest = [ ...new Set( Object.keys( store.m.entries )
		.map( k => k.split( ' ' )[2] )
		.filter( u => /\/wp-json\/topersia\/v1\/weather/.test( u ) ) ) ];
	const t = [];
	for ( const u of URLS.concat( rest ) ) {
		t.push( await ttfb( u ) );
	}

	store.save( {
		label: LABEL,
		urls: URLS,
		pageHosts: [ ...srv.hosts ],
		background: srv.stats.background,
		git: git(),
		plugins: pluginVersions(),
		lighthouse: LH_VERSION,
		ttfb: t,
	} );
	printSnapshot( store, '' );
}

async function measure( dirs ) {

	const sets = [];
	for ( const d of dirs ) {
		const store = new Store( d, false );
		sets.push( { store, srv: await serve( store, false ), rows: {} } );
	}

	const tmp = fs.mkdtempSync( path.join( os.tmpdir(), 'tp-lh-' ) );
	const urls = sets[0].store.m.urls;
	let n = 0;

	console.log( 'کارایی روی نسخۀ ایستا — P5-D264 · Lighthouse ' + LH_VERSION + ' · کرومیوم '
		+ path.basename( path.dirname( path.dirname( CHROME ) ) ) + ' · میانۀ ' + RUNS + ' اجرا' + ( LABEL ? ' · ' + LABEL : '' ) );
	console.log( 'موبایل: گلوگاه شبیه‌سازی‌شدۀ پیش‌فرض · دسکتاپ: --preset=desktop · هر درخواست از نسخۀ ایستا؛ هیچ‌چیز به آپاچی یا اینترنت نمی‌رسد' );
	console.log( 'امتیاز ۰–۱۰۰ · LCP · FCP · TBT به میلی‌ثانیه · «پاسخ» = server-response-time خود Lighthouse روی سرور ایستا' );
	if ( sets.length > 1 ) {
		console.log( 'جفت هم‌زمان: A و B یک‌درمیان، هر اجرای A بی‌درنگ پیش از اجرای B' );
	}
	console.log( '' );

	try {
		for ( const url of urls ) {
			for ( const desktop of [ false, true ] ) {
				const k = url + ( desktop ? ' d' : ' m' );
				for ( const s of sets ) {
					s.rows[ k ] = [];
				}
				for ( let i = 0; i < RUNS; i ++ ) {
					for ( const s of sets ) {
						const r = await lighthouse( url, desktop, path.join( tmp, 'lh-' + ( n ++ ) + '.json' ), s.srv.rules );
						s.rows[ k ].push( row( r ) );
					}
				}
			}
		}
	} finally {
		for ( const s of sets ) {
			s.srv.close();
		}
		fs.rmSync( tmp, { recursive: true, force: true } );
	}

	let fail = 0;
	const tags = sets.length > 1 ? [ 'A', 'B' ] : [ '' ];

	sets.forEach( ( s, i ) => {
		printSnapshot( s.store, tags[ i ] );
		console.log( '' );
		console.log( pad( 'صفحه', 22 ) + pad( 'حالت', 9 ) + pad( 'کارایی', 8 ) + pad( 'دسترس', 7 ) + pad( 'روش', 6 ) + pad( 'سئو', 6 )
			+ pad( 'LCP', 8 ) + pad( 'CLS', 7 ) + pad( 'TBT', 7 ) + pad( 'FCP', 8 ) + pad( 'پاسخ', 6 ) + 'کارایی هر اجرا' );
		for ( const [ k, rows ] of Object.entries( s.rows ) ) {
			const m = x => median( rows.map( r => r[ x ] ) );
			const [ url, ff ] = k.split( ' ' );
			console.log( pad( url.replace( /^https?:\/\/[^/]+/, '' ), 22 ) + pad( 'd' === ff ? 'دسکتاپ' : 'موبایل', 9 )
				+ pad( Math.round( m( 'perf' ) ), 8 ) + pad( Math.round( m( 'a11y' ) ), 7 ) + pad( Math.round( m( 'bp' ) ), 6 ) + pad( Math.round( m( 'seo' ) ), 6 )
				+ pad( Math.round( m( 'lcp' ) ), 8 ) + pad( m( 'cls' ).toFixed( 3 ), 7 ) + pad( Math.round( m( 'tbt' ) ), 7 ) + pad( Math.round( m( 'fcp' ) ), 8 )
				+ pad( Math.round( m( 'srt' ) ), 6 ) + rows.map( r => Math.round( r.perf ) ).join( ' · ' ) );
		}
		const st = s.srv.stats;
		console.log( '  سرور ایستا: ' + st.served + ' پاسخ · بیرون از نسخۀ ایستا: ' + st.misses.length + ( st.errors.length ? ' · خطا: ' + st.errors.length : '' )
			+ ' · پس‌زمینۀ کروم: ' + ( Object.entries( st.background ).map( ( [ h, n ] ) => h + ' ' + n ).join( ' · ' ) || 'هیچ' ) );
		for ( const x of [ ...new Set( st.misses ) ] ) {
			console.log( '    ✗ ' + x );
		}
		if ( st.misses.length || st.errors.length ) {
			fail = 1;
		}
		console.log( '' );
	} );

	if ( sets.length > 1 ) {
		console.log( '══ بودجه — B منهای A (میانۀ امتیاز) · سقف افت ' + BUDGET + ' واحد ══' );
		for ( const k of Object.keys( sets[0].rows ) ) {
			const [ url, ff ] = k.split( ' ' );
			const parts = [];
			for ( const [ x, name ] of [ [ 'perf', 'کارایی' ], [ 'a11y', 'دسترس' ], [ 'bp', 'روش' ], [ 'seo', 'سئو' ] ] ) {
				const a = Math.round( median( sets[0].rows[ k ].map( r => r[ x ] ) ) );
				const b = Math.round( median( sets[1].rows[ k ].map( r => r[ x ] ) ) );
				const d = b - a;
				parts.push( name + ' ' + ( d >= 0 ? '+' : '' ) + d );
				if ( d < -BUDGET ) {
					fail = 1;
				}
			}
			console.log( url.replace( /^https?:\/\/[^/]+/, '' ) + ' ' + ( 'd' === ff ? 'دسکتاپ' : 'موبایل' ) + ' — ' + parts.join( ' · ' ) );
		}
		console.log( fail ? 'بودجه شکست (یا درخواستی بیرون از نسخۀ ایستا رفت).' : 'هیچ امتیازی بیش از ' + BUDGET + ' واحد پایین نیامد.' );
	}

	process.exitCode = fail;
}

( RECORD ? record() : measure( AB.length ? AB : [ SNAP ] ) ).catch( e => die( 1, String( e && e.stack || e ) ) );
