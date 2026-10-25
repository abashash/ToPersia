/**
 * گزارش دیداری «پیش / پس» — استاندارد دائمی (کاربر، ۲۵ اکتبر؛ دستهٔ ۲۶ بند ۰).
 * هر دسته‌ای که تغییر دیدنی دارد پیش از گزارش این را اجرا می‌کند. فقط محلی: هیچ چیز
 * روی اینترنت منتشر نمی‌شود و هیچ درخواستی جز topersia.local نمی‌رود.
 *
 *   node scripts/visual-report.js --batch=26 --phase=before --before-ref=HEAD --page=استان=http://topersia.local/province/isfahan/
 *   node scripts/visual-report.js --batch=26 --phase=after  --page=استان=http://topersia.local/province/isfahan/
 *
 *   --page=برچسب=نشانی[@گزینشگر]   تکرارپذیر؛ نشانی فارسی — en و ar از WPML (scripts/visual-report-urls.php)؛
 *                                  «@.tp-stats» فقط از همان عنصر عکس می‌گیرد (بی آن، تمام صفحه)
 *   --before-ref=<git ref>         فقط با phase=before: افزونه‌های ما در آن ref با git archive ← robocopy /MIR /R:5 /W:2
 *                                  در geo ← cmp همهٔ پرونده‌ها (قاعدهٔ Lighthouse، دستهٔ ۲۰) ← عکس ← در پایان، حتی با خطا،
 *                                  نسخهٔ workspace با همان روش و همان cmp برمی‌گردد
 *   --langs=fa,en,ar  --sizes=1440,390  --mode=light|evening|dark
 *
 * خروجی: tests/.screens/<batch>/ (بیرون از git) — عکس‌ها، report.json و index.html با «پیش» و «پس» کنار هم
 * برای هر صفحه × زبان × پهنا؛ پایان اجرا پیوند file:// برگه و نشانی محلی هر صفحه را چاپ می‌کند.
 * کد خروج: ۰ سالم · ۱ عکسی نیامد یا پاسخ ۲۰۰ نبود · ۲ محیط یا کپی ناقص.
 */

'use strict';

const fs   = require( 'node:fs' );
const os   = require( 'node:os' );
const path = require( 'node:path' );
const { execFileSync, spawnSync } = require( 'node:child_process' );
const { pathToFileURL } = require( 'node:url' );
const ENV = require( '../tests/env.js' );
const { chromium } = require( '../tests/pw.js' );

const WS      = path.resolve( __dirname, '..' );
const PLUGINS = [ 'topersia-render', 'topersia-core', 'topersia-user-core', 'topersia-ai-summarizer', 'topersia-schema' ];
const GEO_PL  = path.join( ENV.wp, 'wp-content', 'plugins' );

const arg = ( k, d ) => { const a = process.argv.find( x => x.startsWith( '--' + k + '=' ) ); return a ? a.slice( k.length + 3 ) : d; };
const all = k => process.argv.filter( x => x.startsWith( '--' + k + '=' ) ).map( x => x.slice( k.length + 3 ) );

const BATCH = arg( 'batch', '' );
const PHASE = arg( 'phase', '' );
const REF   = arg( 'before-ref', '' );
const LANGS = arg( 'langs', 'fa,en,ar' ).split( ',' ).filter( Boolean );
const SIZES = arg( 'sizes', '1440,390' ).split( ',' ).map( Number ).filter( Boolean );
const MODE  = arg( 'mode', 'light' );
const PAGES = all( 'page' ).map( p => {
	const i = p.indexOf( '=' );
	const rest = p.slice( i + 1 );
	const at = rest.indexOf( '@' );
	return { label: p.slice( 0, i ), url: at > 0 ? rest.slice( 0, at ) : rest, sel: at > 0 ? rest.slice( at + 1 ) : '' };
} );

function die( msg ) { console.error( '  ✖ ' + msg ); process.exit( 2 ); }
if ( ! /^[\w.-]+$/.test( BATCH ) ) { die( '--batch=<نام دسته> لازم است (حرف، رقم، . - _)' ); }
if ( ! [ 'before', 'after' ].includes( PHASE ) ) { die( '--phase=before|after لازم است' ); }
if ( REF && 'before' !== PHASE ) { die( '--before-ref فقط با --phase=before' ); }
if ( ! PAGES.length ) { die( 'دست‌کم یک --page=برچسب=نشانی' ); }
for ( const p of PAGES ) {
	let u; try { u = new URL( p.url ); } catch ( e ) { die( 'نشانی نادرست: ' + p.url ); }
	if ( u.hostname !== ENV.host ) { die( 'فقط ' + ENV.host + ' — نه ' + u.hostname ); }   /* سیاست «فقط محلی» */
	if ( ! /^[^\s/\\:*?"<>|]+$/.test( p.label ) ) { die( 'برچسب بی فاصله و نویسهٔ مسیر: ' + p.label ); }
}

const DIR  = path.join( WS, 'tests', '.screens', BATCH );
const MANI = path.join( DIR, 'report.json' );
const sleep = ms => new Promise( r => setTimeout( r, ms ) );

/* ── کپی با robocopy و cmp — همان روش A/B لایت‌هاوس ─────────────────────────── */
function files( dir, base = dir, out = [] ) {
	for ( const e of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		const f = path.join( dir, e.name );
		if ( e.isDirectory() ) { files( f, base, out ); } else { out.push( path.relative( base, f ) ); }
	}
	return out;
}
function mirror( src, dst ) {
	const r = spawnSync( 'robocopy', [ src, dst, '/MIR', '/R:5', '/W:2', '/NFL', '/NDL', '/NJH', '/NJS', '/NP' ], { encoding: 'utf8' } );
	if ( null === r.status || r.status >= 8 ) { throw new Error( 'robocopy ' + path.basename( src ) + ' خروج ' + r.status ); }
	const a = files( src );
	const bad = a.filter( f => { const d = path.join( dst, f ); return ! fs.existsSync( d ) || ! fs.readFileSync( path.join( src, f ) ).equals( fs.readFileSync( d ) ); } );
	const extra = files( dst ).length - a.length;
	if ( bad.length || extra ) { throw new Error( 'cmp ' + path.basename( src ) + ': ' + bad.length + ' ناهمسان · ' + extra + ' اضافه — ' + bad.slice( 0, 3 ).join( ' ' ) ); }
	return a.length;
}
function deployRef( ref ) {
	const tmp = fs.mkdtempSync( path.join( os.tmpdir(), 'tp-visual-' ) );
	const tar = path.join( tmp, 'ref.tar' );
	const have = PLUGINS.filter( p => fs.existsSync( path.join( GEO_PL, p ) ) );
	execFileSync( 'git', [ '-C', WS, 'archive', '--format=tar', '-o', tar, ref, ...have.map( p => 'plugin/' + p ) ] );
	execFileSync( 'tar', [ '-xf', 'ref.tar' ], { cwd: tmp } );   /* نسبی: tar گیت «C:» را میزبان دور می‌گیرد */
	const root = files( tmp ).map( f => f.split( path.sep ) ).find( s => s.includes( 'plugin' ) );
	const base = path.join( tmp, ...root.slice( 0, root.indexOf( 'plugin' ) + 1 ) );
	const log = have.map( p => p + ' ' + mirror( path.join( base, p ), path.join( GEO_PL, p ) ) );
	console.log( '  «پیش» = ' + ref + ' (' + execFileSync( 'git', [ '-C', WS, 'rev-parse', '--short', ref ], { encoding: 'utf8' } ).trim() + ') · robocopy + cmp: ' + log.join( ' · ' ) );
	return tmp;
}
function restore() {
	const log = PLUGINS.filter( p => fs.existsSync( path.join( GEO_PL, p ) ) ).map( p => p + ' ' + mirror( path.join( WS, 'plugin', p ), path.join( GEO_PL, p ) ) );
	console.log( '  بازگشت workspace · robocopy + cmp: ' + log.join( ' · ' ) );
}

/* ── عکس ──────────────────────────────────────────────────────────────────── */
async function shoot( browser, url, w, sel, file ) {
	const mobile = w < 768;
	const ctx  = await browser.newContext( mobile ? { viewport: { width: w, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 } : { viewport: { width: w, height: 900 } } );
	const page = await ctx.newPage();
	await page.addInitScript( m => { try { localStorage.setItem( 'tp-mode', m ); } catch ( e ) {} }, MODE );
	let status = 0, err = '';
	try {
		const res = await page.goto( url, { waitUntil: 'load', timeout: 120000 } );
		status = res ? res.status() : 0;
		await page.waitForSelector( '.tp-wx__day:not(.tp-is-load)', { timeout: 20000 } ).catch( () => {} );
		await page.evaluate( async () => {   /* تصویرهای تنبل */
			const step = Math.max( 400, window.innerHeight * 0.8 );
			for ( let y = 0; y < document.documentElement.scrollHeight; y += step ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 120 ) ); }
			window.scrollTo( { top: 0, behavior: 'instant' } );
		} );
		await sleep( 900 );
		if ( sel ) {
			const el = page.locator( sel ).first();
			if ( ! await el.count() ) { throw new Error( 'گزینشگر نیست: ' + sel ); }
			await el.scrollIntoViewIfNeeded();
			await el.screenshot( { path: file, type: 'jpeg', quality: 80 } );
		} else {
			await page.screenshot( { path: file, fullPage: true, type: 'jpeg', quality: 70 } );
		}
	} catch ( e ) { err = String( e.message || e ).split( '\n' )[ 0 ]; }
	await ctx.close();
	return { status, err };
}

function esc( s ) { return String( s ).replace( /[&<>"]/g, c => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' } )[ c ] ); }
function writeIndex( m ) {
	const pages = [ ...new Set( m.shots.map( s => s.label ) ) ];
	let h = '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>گزارش دیداری ' + esc( m.batch ) + '</title>'
		+ '<style>body{font:14px system-ui,Tahoma;margin:16px;background:#fff;color:#111}h1{font-size:20px}h2{margin:28px 0 6px;font-size:17px}h3{margin:14px 0 6px;font-size:14px;color:#333}'
		+ '.row{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start}figure{margin:0;border:1px solid #ccc;padding:6px;min-width:0}figcaption{font-size:12px;margin-bottom:4px;direction:ltr;text-align:left;word-break:break-all}'
		+ 'img{width:100%;height:auto;display:block}.w390 img{max-width:390px}.miss{color:#a00;padding:24px;text-align:center}a{color:#0645ad}</style>'
		+ '<h1>گزارش دیداری — دستهٔ ' + esc( m.batch ) + '</h1><p>«پیش»: ' + esc( m.before.ref || 'همان نسخهٔ محلی هنگام اجرا' ) + ' · ' + esc( m.before.at || '—' ) + ' — «پس»: ' + esc( m.after.at || '—' ) + ' · حالت ' + esc( m.mode ) + '. فقط محلی؛ منتشر نشده.</p>';
	for ( const label of pages ) {
		h += '<h2>' + esc( label ) + '</h2>';
		for ( const lang of m.langs ) {
			for ( const w of m.sizes ) {
				const one = ph => m.shots.find( s => s.label === label && s.lang === lang && s.w === w && s.phase === ph );
				const fig = ( ph, name ) => {
					const s = one( ph );
					if ( ! s ) { return '<figure><figcaption>' + name + '</figcaption><div class="miss">گرفته نشد</div></figure>'; }
					if ( ! s.url ) { return '<figure><figcaption>' + name + '</figcaption><div class="miss">ترجمهٔ منتشرشده نیست</div></figure>'; }
					return '<figure class="w' + w + '"><figcaption>' + name + ' · ' + s.status + ' · <a href="' + esc( s.url ) + '">' + esc( s.url ) + '</a>' + ( s.err ? ' · <b>' + esc( s.err ) + '</b>' : '' ) + '</figcaption>'
						+ ( s.file && ! s.err ? '<a href="' + esc( s.file ) + '"><img loading="lazy" src="' + esc( s.file ) + '" alt="' + esc( label + ' ' + lang + ' ' + w + ' ' + name ) + '"></a>' : '<div class="miss">بی عکس</div>' ) + '</figure>';
				};
				h += '<h3>' + lang + ' · ' + w + 'px</h3><div class="row">' + fig( 'before', 'پیش' ) + fig( 'after', 'پس' ) + '</div>';
			}
		}
	}
	fs.writeFileSync( path.join( DIR, 'index.html' ), h );
}

( async () => {
	fs.mkdirSync( DIR, { recursive: true } );
	let m = { batch: BATCH, mode: MODE, langs: LANGS, sizes: SIZES, before: {}, after: {}, shots: [] };
	try { m = Object.assign( m, JSON.parse( fs.readFileSync( MANI, 'utf8' ) ) ); } catch ( e ) {}
	m.langs = [ ...new Set( [ ...m.langs, ...LANGS ] ) ];
	m.sizes = [ ...new Set( [ ...m.sizes, ...SIZES ] ) ];

	const b64 = Buffer.from( JSON.stringify( PAGES.map( p => p.url ) ) ).toString( 'base64' );
	const urls = JSON.parse( execFileSync( ENV.php, [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + ENV.base, 'eval-file', path.join( __dirname, 'visual-report-urls.php' ), b64 ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'ignore' ] } ) );

	let tmp = '', fail = 0;
	const browser = await chromium.launch();
	try {
		if ( REF ) { tmp = deployRef( REF ); }
		m[ PHASE ] = { ref: REF ? REF + ' ' + execFileSync( 'git', [ '-C', WS, 'rev-parse', '--short', REF ], { encoding: 'utf8' } ).trim() : ( 'before' === PHASE ? '' : 'workspace' ), at: new Date().toISOString().slice( 0, 16 ).replace( 'T', ' ' ) };
		for ( const [ i, p ] of PAGES.entries() ) {
			for ( const lang of LANGS ) {
				for ( const w of SIZES ) {
					const url = 'fa' === lang ? p.url : ( urls[ i ][ lang ] || '' );
					const file = PHASE + '-' + p.label + '-' + lang + '-' + w + '.jpg';
					m.shots = m.shots.filter( s => ! ( s.label === p.label && s.lang === lang && s.w === w && s.phase === PHASE ) );
					if ( ! url ) { m.shots.push( { label: p.label, lang, w, phase: PHASE, url: '', file: '', status: 0, err: '' } ); console.log( '  ⚠️ ' + p.label + ' · ' + lang + ' — ترجمهٔ منتشرشده نیست' ); continue; }
					const r = await shoot( browser, url, w, p.sel, path.join( DIR, file ) );
					const ok = 200 === r.status && ! r.err;
					if ( ! ok ) { fail ++; }
					m.shots.push( { label: p.label, lang, w, phase: PHASE, url, file, status: r.status, err: r.err } );
					console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + PHASE + ' · ' + p.label + ' · ' + lang + ' · ' + w + ' — ' + r.status + ( r.err ? ' · ' + r.err : '' ) );
				}
			}
		}
	} finally {
		await browser.close();
		if ( REF ) {
			try { restore(); } catch ( e ) { console.error( '  ✖ بازگشت ناقص: ' + e.message ); process.exitCode = 2; }
			if ( tmp ) { fs.rmSync( tmp, { recursive: true, force: true } ); }
		}
		fs.writeFileSync( MANI, JSON.stringify( m, null, 1 ) );
		writeIndex( m );
	}
	console.log( '\n  برگه: ' + pathToFileURL( path.join( DIR, 'index.html' ) ).href );
	for ( const [ i, p ] of PAGES.entries() ) { console.log( '  ' + p.label + ': ' + LANGS.map( l => ( 'fa' === l ? p.url : urls[ i ][ l ] ) || ( l + ' —' ) ).join( ' · ' ) ); }
	process.exit( process.exitCode || ( fail ? 1 : 0 ) );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
