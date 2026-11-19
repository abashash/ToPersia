/**
 * دستۀ 54 بند ۱۵ (و ۱۸) — افزونۀ تبلیغ اختصاصی روی نسخۀ محلی (فقط topersia.local).
 *
 *   node tests/w54-local.js [--shots | --shots-only]
 *
 * ▸ جایگاه‌ها — خانه، هاب (/attractions/ شبکه و فهرست، /news/، /articles/)، تک خبر، استان، و خبر بلند موقت (میان متن)؛
 *   fa · en · ar × ۳۹۰ · ۷۶۸ · ۱۴۴۰، مهمان:
 *     تراکم (≥۷۶۸ حداکثر ۳، موبایل ۲؛ پاورقی جدا)، فاصلۀ ≥ دو صفحه‌نمایش میان واحدهای جریان اصلی (ستون کناری کنار متن بیرون)،
 *     نخستین صفحه‌نمایش بی واحد (جز «زیر نوار ابزار هاب» در ≥۷۶۸)، lazy (نخستین صفحه‌نمایش بی lazy؛ دورتر از دو صفحه‌نمایش lazy)،
 *     بی CLS: بلندی واحد از DOMContentLoaded تا پس از دیده‌شدن، هر باز شدن یکسره زیر صفحه‌نمایش، CLS کل ≤ ۰٫۱؛
 *     بی سرریز افقی؛ برچسب و جای تبلیغ به زبان صفحه (کاتالوگ)؛ data-nosnippet؛ rel="sponsored noopener" target=_blank؛ alt؛ <picture>
 * ▸ صفحه‌های بی تبلیغ: حقوقی، جست‌وجو، ۴۰۴، /advertise/ — هیچ واحد درون محتوا، بی tp-ads.js؛ /advertise/ noindex، تماس، بی قیمت عددی
 * ▸ کنتراست سه حالت (light · evening · dark): برچسب و جای تبلیغ، پاورقی، برچسب تبلیغ واقعی، نشانۀ جایگاه خالی (مدیر)، /advertise/
 * ▸ شمارش با تبلیغ آزمونی واقعی (`_tp_test_ad`): نمایش ۵۰٪ × ۱ ثانیه با sendBeacon، یک بار، دید کوتاه نه؛ /ad-go/ ۳۰۲ + شمارش،
 *   تکراری، ربات، پیش‌واکشی، مدیر؛ نمایشی و ناموجود ۴۰۴؛ REST توکن بد، ربات، جایگاه ناجور و ۴۱۳ — همه در پایان پاک می‌شود
 * ▸ پنل (مدیر آزمون): گزارش با همان شمار پایگاه‌داده، CSV بی تزریق فرمول، فهرست؛ مشترک ممنوع؛ یادداشت شمار فقط مدیر؛
 *   جایگاه خالی فقط مدیر (یک نمونۀ نمایشی موقتاً متوقف و عیناً برگردانده)
 * ▸ --shots: عکس «پس» فقط fa (بند ۱۸) ← tests/.screens/54/ (بیرون از git)
 *
 * مرورگرهای سنجش UA «headless» دارند و REST نمایششان بسته است؛ فقط گذر شمارش (۴) نمایش می‌فرستد — شمار پایگاه‌داده قطعی می‌ماند.
 * یک زنجیره در هر زمان؛ هم‌زمان هیچ درخواست دیگری به سایت. کد خروج: ۰ سبز · ۱ افتادن · ۲ محیط ناقص.
 */
'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const http = require( 'http' );
const { spawnSync } = require( 'child_process' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE  = ENV.base.replace( /\/$/, '' );
const ROOT  = path.join( __dirname, '..' );
const X     = Math.max( 1, Number( process.env.TP_TIMEOUT_X || 1 ) );
const SHOTS_ONLY = process.argv.includes( '--shots-only' );   /* فقط عکس «پس» — پس از رفعی که فقط دیدنی است */
const SHOTS = SHOTS_ONLY || process.argv.includes( '--shots' );
const SHOTS_DONE = Symbol( 'shots-done' );
const SHOT  = path.join( __dirname, '.screens', '54' );
const VIEWS = [ [ 390, 844 ], [ 768, 1024 ], [ 1440, 900 ] ];
const LANGS = [ 'fa', 'en', 'ar' ];
const NEWS1 = '/news/isfahan-children-film-festival-38/';
const PAGES = [
	{ k: 'خانه', u: '/' },
	{ k: 'هاب جاذبه‌ها', u: '/attractions/', views: [ 'grid', 'list' ] },
	{ k: 'هاب خبر', u: '/news/' },
	{ k: 'هاب مقاله', u: '/articles/' },
	{ k: 'تک خبر', u: NEWS1 },
	{ k: 'استان', u: '/province/isfahan/' },
];
const TXT = {
	fa: { tag: 'تبلیغ', demo: 'نمونۀ نمایشی', ph: 'جای تبلیغ', cta: 'تماس بگیرید.', empty: 'جایگاه خالی: پیش از مرتبط‌ها' },
	en: { tag: 'Ad', demo: 'Demo sample', ph: 'Ad space', cta: 'Contact us.', empty: 'Empty placement: Before related items' },
	ar: { tag: 'إعلان', demo: 'نموذج توضيحي', ph: 'مساحة إعلانية', cta: 'تواصلوا معنا.', empty: 'موضع فارغ: قبل المحتوى ذي الصلة' },
};
const UA     = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0 Safari/537.36 tp-w54';
const UA_OFF = UA + ' headless-w54';   /* topersia_ads_is_bot() ← بی شمارش */
const MODES  = [ 'light', 'evening', 'dark' ];

let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( note ? '   ← ' + note : '' ) ); };
const head = s => console.log( '\n' + s + '\n' + '─'.repeat( 78 ) );
const settle = ms => new Promise( r => setTimeout( r, ms * X ) );
const digits = s => String( s ).replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ).replace( /[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf( d ) ).replace( /[٬,]/g, '' );
const phpArr = a => 'array(' + a.map( s => JSON.stringify( s ) ).join( ',' ) + ')';

/* ── وردپرس ── */
const wp = code => {
	const r = spawnSync( ENV.php, [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + BASE, 'eval', code ], { encoding: 'utf8', timeout: 180000 * X } );
	if ( 0 !== r.status ) { throw new Error( 'wp-cli: ' + String( r.stderr || r.stdout || '' ).slice( 0, 300 ) ); }
	return String( r.stdout || '' ).split( /\r?\n/ ).filter( l => ! /translations_api|^Warning:|^PHP Warning:/.test( l ) ).join( '\n' ).trim();
};
const login = user => {
	const r = spawnSync( ENV.php, [ path.join( __dirname, 'local-login.php' ) ].concat( user ? [ user ] : [] ), { encoding: 'utf8', cwd: ROOT, timeout: 120000 * X } );
	if ( 0 !== r.status ) { throw new Error( 'local-login: ' + String( r.stderr || '' ).slice( 0, 200 ) ); }
	return JSON.parse( r.stdout );
};
const cookieHeader = c => c.cookies.map( x => x.name + '=' + x.value ).join( '; ' );
const stats = id => JSON.parse( wp( 'global $wpdb; $t = topersia_ads_tables(); echo wp_json_encode( $wpdb->get_results( $wpdb->prepare( "SELECT slot, lang, imp, clk FROM {$t[\'day\']} WHERE ad_id = %d ORDER BY slot, lang", ' + Number( id ) + ' ), ARRAY_A ) );' ) || '[]' );
const sum = ( rows, k, f ) => rows.filter( r => ! f || f( r ) ).reduce( ( a, r ) => a + Number( r[ k ] ), 0 );

/* ── HTTP بی مرورگر (کلیک و REST) ── */
const req = ( method, u, o = {} ) => new Promise( res => {
	const url  = new URL( u );
	const body = null == o.body ? null : String( o.body );
	const hd   = Object.assign( { 'User-Agent': o.ua || UA, Connection: 'close' }, o.cookie ? { Cookie: o.cookie } : {}, body ? { 'Content-Type': 'text/plain;charset=UTF-8', 'Content-Length': Buffer.byteLength( body ) } : {}, o.headers || {} );
	/* بی keep-alive: عامل سراسری node 19+ سوکت را نگه می‌دارد و آپاچی سوکت بیکار را می‌بندد ← هر درخواست دوم ECONNRESET (وضعیت ۰) */
	const q    = http.request( { hostname: url.hostname, port: url.port || 80, path: url.pathname + url.search, method, headers: hd, agent: false }, r => {
		let d = '';
		r.setEncoding( 'utf8' );
		r.on( 'data', c => { d += c; } );
		r.on( 'end', () => res( { status: r.statusCode, headers: r.headers, body: d } ) );
	} );
	q.on( 'error', e => res( { status: 0, headers: {}, body: String( e ) } ) );
	q.setTimeout( 90000 * X, () => q.destroy( new Error( 'timeout' ) ) );
	if ( body ) { q.write( body ); }
	q.end();
} );

/* ── در صفحه ── */
function snapshot() {
	const vw = innerWidth, vh = innerHeight, sy = scrollY;
	const side = e => { const r = e.closest( '.tp-rail' ); return !! r && r.getBoundingClientRect().width < 0.6 * vw; };
	const units = [ ...document.querySelectorAll( '.tp-ad' ) ].map( e => {
		const b = e.getBoundingClientRect(), cs = getComputedStyle( e );
		const img = e.querySelector( 'img.tp-ad__img' ), a = e.querySelector( 'a.tp-ad__a' ), tag = e.querySelector( '.tp-ad__tag' ), ph = e.querySelector( '.tp-ad__ph' );
		return {
			slot: ( String( e.className ).match( /tp-ad--(home-mid|hub-top|hub-infeed|single-inline|single-rail|single-end|entity-rail|entity-mid)\b/ ) || [] )[1] || '?',
			vis: b.height > 0 && 'none' !== cs.display && 'hidden' !== cs.visibility,
			top: Math.round( b.top + sy ), bot: Math.round( b.bottom + sy ), l: Math.round( b.left ), r: Math.round( b.right ), h: Math.round( b.height ),
			side: side( e ), demo: e.classList.contains( 'tp-ad--demo' ), nosnip: e.hasAttribute( 'data-nosnippet' ),
			tag: tag ? tag.textContent.trim() : '', ph: ph ? ph.textContent.trim() : '',
			rel: a ? a.getAttribute( 'rel' ) : null, target: a ? a.getAttribute( 'target' ) : null, href: a ? a.getAttribute( 'href' ) : null,
			alt: img ? img.getAttribute( 'alt' ) : null, lazy: img ? img.getAttribute( 'loading' ) : null,
			iw: img ? img.getAttribute( 'width' ) : null, ih: img ? img.getAttribute( 'height' ) : null, pic: !! e.querySelector( 'picture' ), data: e.getAttribute( 'data-tp-ad' ),
		};
	} );
	return {
		vw, vh, docW: document.documentElement.scrollWidth, units,
		off: [ ...document.querySelectorAll( '.tp-ad-slot[data-tp-ad-off]' ) ].map( s => String( s.className ).replace( /.*tp-ad-slot--(\S+).*/, '$1' ) + ':' + s.getAttribute( 'data-tp-ad-off' ) ),
		js: !! document.querySelector( 'script[src*="tp-ads.js"]' ), slots: document.querySelectorAll( '.tp-ad-slot' ).length,
		empty: [ ...document.querySelectorAll( '.tp-ad-empty' ) ].filter( e => e.getBoundingClientRect().height > 0 ).map( e => e.textContent.trim() ),
		comment: ( document.documentElement.outerHTML.match( /<!-- tp-ads page=(\S+) d=(\d+) m=(\d+) footer=(\d+) -->/ ) || [] ).slice( 1 ),
		ft: [ ...document.querySelectorAll( '.tp-ft-ad' ) ].filter( e => e.getBoundingClientRect().height > 0 ).length,
		/* پاورقی: پیوند واقعی (topersia-ads یا پیشین render) یا کادر نمایشی پیشین render — هر دو data-nosnippet */
		ftAll: [ ...document.querySelectorAll( '.tp-ft-ad' ) ].every( a => a.hasAttribute( 'data-nosnippet' ) && ( a.classList.contains( 'tp-ft-ad--demo' )
			? ! a.querySelector( 'a' ) && !! a.querySelector( '.tp-ft-ad__tag' )
			: 'A' === a.tagName && 'sponsored noopener' === a.getAttribute( 'rel' ) && '_blank' === a.getAttribute( 'target' ) && !! ( a.querySelector( 'img' ) || {} ).alt ) ),
		lang: document.documentElement.lang,
	};
}

/* پیش از هر بارگذاری: CLS، بلندی واحدها در DOMContentLoaded، جای هر باز شدن */
/* CLS تبلیغ = جابه‌جایی‌ای که گره جابه‌جاشده‌اش خود واحد/جایگاه/پاورقی تبلیغ است، یا تا ۲۰۰ms پس از باز شدن یک جایگاه رخ داد؛
   بقیۀ CLS صفحه (مثلاً نمای فهرست هاب) جدا با منشأ گزارش می‌شود، چون از این دسته نیست */
function initProbe() {
	window.__tp = { cls: 0, adCls: 0, h0: null, opens: [], src: {} };
	window.__tpEls = [];   /* گرهٔ جایگاه‌های بازشده — جدا از __tp چون گره سریال‌پذیر نیست */
	const name = n => n && 1 === n.nodeType ? n.tagName.toLowerCase() + ( n.id ? '#' + n.id : '' ) + ( n.classList.length ? '.' + [ ...n.classList ].slice( 0, 2 ).join( '.' ) : '' ) : '#text';
	try {
		new PerformanceObserver( l => {
			for ( const e of l.getEntries() ) {
				if ( e.hadRecentInput ) { continue; }
				window.__tp.cls += e.value;
				const nodes = ( e.sources || [] ).map( s => s.node ).filter( Boolean );
				/* سبب تبلیغ = جایگاهی که ≤۲۰۰ms پیش با جعبه باز شد و گرهٔ جابه‌جاشده پس از آن در سند است (هلش داده). جابه‌جایی خودِ
				   واحد یا ظرفش از تغییری بالای آن است، نه از تبلیغ؛ تغییر اندازهٔ خودِ واحد را سنجهٔ h0/h1 می‌گیرد. */
				/* گرهٔ درون ظرف position:fixed (نوار مدیر وردپرس که پایان body است) از جریان سند بیرون است؛ جایگاه هلش نمی‌دهد */
				const fixedIn = n => { for ( let x = 1 === n.nodeType ? n : n.parentElement; x && x !== document.body; x = x.parentElement ) { if ( 'fixed' === getComputedStyle( x ).position ) { return true; } } return false; };
				const after = ( el, n ) => n !== el && ! n.contains( el ) && !! ( el.compareDocumentPosition( n ) & Node.DOCUMENT_POSITION_FOLLOWING ) && ! el.contains( n ) && ! fixedIn( n );
				const ad = window.__tpEls.some( o => e.startTime - o.t >= 0 && e.startTime - o.t < 200 && nodes.some( n => after( o.el, n ) ) );
				if ( ad ) { window.__tp.adCls += e.value; }
				nodes.slice( 0, 2 ).forEach( n => { const k = name( n ); window.__tp.src[ k ] = ( window.__tp.src[ k ] || 0 ) + e.value; } );
			}
		} ).observe( { type: 'layout-shift', buffered: true } );
	} catch ( e ) {}
	new MutationObserver( ms => {
		for ( const m of ms ) {
			const s = m.target;
			if ( s.classList && s.classList.contains( 'tp-ad-slot' ) && s.classList.contains( 'tp-ad-on' ) && ! s.__tpSeen ) {
				s.__tpSeen = 1;
				/* h = 0: جایگاهِ فقط دستگاه دیگر (CSS دستگاه پنهانش کرده) — باز شدنش هیچ جعبه و جابه‌جایی‌ای ندارد */
				const b = s.getBoundingClientRect();
				window.__tp.opens.push( { slot: String( s.className ).replace( /.*tp-ad-slot--(\S+).*/, '$1' ), top: Math.round( b.top ), h: Math.round( b.height ), vh: innerHeight, t: performance.now() } );
				if ( b.height > 0 ) { window.__tpEls.push( { el: s, t: performance.now() } ); }
			}
		}
	} ).observe( document, { subtree: true, attributes: true, attributeFilter: [ 'class' ] } );
	document.addEventListener( 'DOMContentLoaded', () => {
		window.__tp.h0 = [ ...document.querySelectorAll( '.tp-ad' ) ].map( e => Math.round( e.getBoundingClientRect().height ) );
	} );
}

/* هر واحد دیدنی را به میانۀ صفحه‌نمایش می‌برد تا تصویر lazy بیاید؛ سپس بلندی‌ها */
async function scrollPass( p ) {
	await p.evaluate( async () => {
		const els = [ ...document.querySelectorAll( '.tp-ad' ) ].filter( e => e.getBoundingClientRect().height > 0 );
		for ( const e of els ) {
			e.scrollIntoView( { behavior: 'instant', block: 'center' } );
			await new Promise( r => setTimeout( r, 300 ) );
		}
		await Promise.all( [ ...document.querySelectorAll( '.tp-ad img' ) ].filter( i => i.getBoundingClientRect().height > 0 ).map( i => i.complete ? 0 : new Promise( r => { i.onload = i.onerror = r; setTimeout( r, 4000 ); } ) ) );
		window.scrollTo( { top: 0, behavior: 'instant' } );
	} );
	await settle( 300 );
	/* عکس «پس» بی کاوشگر (probe) باز می‌شود — __tp آن‌جا نیست */
	return p.evaluate( () => { const tp = window.__tp || { h0: null, cls: 0, adCls: 0, opens: [], src: {} }; return { h1: [ ...document.querySelectorAll( '.tp-ad' ) ].map( e => Math.round( e.getBoundingClientRect().height ) ), h0: tp.h0, cls: tp.cls, adCls: tp.adCls, opens: tp.opens,
		src: Object.entries( tp.src ).sort( ( a, b ) => b[1] - a[1] ).slice( 0, 3 ).map( ( [ k, v ] ) => k + ' ' + v.toFixed( 3 ) ).join( ' · ' ) }; } );
}

/* کنتراست متن برگ‌ها زیر گزینشگر — رنگ و زمینۀ ترکیب‌شده (آلفا) تا نخستین زمینۀ کدر */
function contrastOf( sel ) {
	const parse = c => {
		let m = String( c ).match( /^rgba?\(([^)]+)\)/ );
		if ( m ) { const p = m[1].split( /[\s,/]+/ ).filter( Boolean ).map( Number ); return [ p[0], p[1], p[2], p.length > 3 ? p[3] : 1 ]; }
		m = String( c ).match( /^color\(srgb ([^)]+)\)/ );
		if ( m ) { const p = m[1].split( /[\s/]+/ ).filter( Boolean ).map( Number ); return [ p[0] * 255, p[1] * 255, p[2] * 255, p.length > 3 ? p[3] : 1 ]; }
		return null;
	};
	const lum = c => { const f = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow( ( v + 0.055 ) / 1.055, 2.4 ); }; return 0.2126 * f( c[0] ) + 0.7152 * f( c[1] ) + 0.0722 * f( c[2] ); };
	const bgOf = e => {
		const layers = [];
		for ( let n = e; n && 1 === n.nodeType; n = n.parentElement ) {
			const c = parse( getComputedStyle( n ).backgroundColor );
			if ( c && c[3] > 0 ) { layers.push( c ); if ( c[3] >= 1 ) { break; } }
		}
		let base = [ 255, 255, 255 ];
		for ( let i = layers.length - 1; i >= 0; i-- ) { const c = layers[ i ]; base = [ 0, 1, 2 ].map( k => c[ k ] * c[3] + base[ k ] * ( 1 - c[3] ) ); }
		return base;
	};
	const leaves = [ ...document.querySelectorAll( sel ) ].flatMap( r => [ r, ...r.querySelectorAll( '*' ) ] )
		.filter( e => [ ...e.childNodes ].some( n => 3 === n.nodeType && n.textContent.trim() ) && e.getBoundingClientRect().height > 0 && 'hidden' !== getComputedStyle( e ).visibility );
	return [ ...new Set( leaves ) ].map( e => {
		const cs = getComputedStyle( e ), bg = bgOf( e );
		let fg = parse( cs.color );
		if ( ! fg ) { return { t: e.textContent.trim().slice( 0, 24 ), ratio: 0, need: 4.5 }; }
		fg = [ 0, 1, 2 ].map( k => fg[ k ] * fg[3] + bg[ k ] * ( 1 - fg[3] ) );
		const a = lum( fg ), b = lum( bg ), fs = parseFloat( cs.fontSize ), big = fs >= 24 || ( fs >= 18.66 && Number( cs.fontWeight ) >= 700 );
		return { t: e.textContent.trim().slice( 0, 24 ), ratio: Math.round( ( Math.max( a, b ) + 0.05 ) / ( Math.min( a, b ) + 0.05 ) * 100 ) / 100, need: big ? 3 : 4.5 };
	} );
}

/* ── راننده ── */
( async () => {
	let env;
	try {
		env = JSON.parse( wp( 'global $wpdb; echo wp_json_encode( array( "on" => function_exists( "topersia_ads_enabled" ) && topersia_ads_enabled(), "tbl" => function_exists( "topersia_ads_table_ok" ) && topersia_ads_table_ok(), "demo" => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_type = \'tp_ad\' AND p.post_status = \'publish\' WHERE m.meta_key = \'_tp_demo\' AND m.meta_value = \'1\'" ), "preview" => function_exists( "topersia_demo_on" ) && topersia_demo_on(), "left" => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN (\'_tp_test_ad\', \'_tp_test_post\')" ) ) );' ) );
	} catch ( e ) {
		console.error( 'محیط ناقص: ' + e.message );
		process.exit( 2 );
	}
	if ( ! env.on || ! env.tbl || ! env.demo || ! env.preview ) {
		console.error( 'محیط ناقص: افزونه/جدول/نمونۀ نمایشی/پیش‌نمایش ' + JSON.stringify( env ) );
		process.exit( 2 );
	}
	if ( env.left ) {
		console.error( 'دادۀ آزمون پیشین مانده (_tp_test_ad / _tp_test_post): ' + env.left + ' — پیش از اجرا پاک شود' );
		process.exit( 2 );
	}

	const ALT = JSON.parse( wp( '$out = array(); foreach ( ' + phpArr( PAGES.map( p => p.u ).concat( [ '/advertise/' ] ) ) + ' as $p ) { $u = home_url( $p ); $pid = "/" === $p ? 0 : url_to_postid( $u ); foreach ( array( "en", "ar" ) as $l ) { if ( $pid ) { $tid = apply_filters( "wpml_object_id", $pid, get_post_type( $pid ), false, $l ); $out[ $p ][ $l ] = ( $tid && "publish" === get_post_status( $tid ) ) ? apply_filters( "wpml_permalink", get_permalink( $tid ), $l, true ) : ""; } else { $out[ $p ][ $l ] = apply_filters( "wpml_permalink", $u, $l ); } } } echo wp_json_encode( $out );' ) );
	const urlOf = ( u, l ) => 'fa' === l ? BASE + u : ( ALT[ u ] && ALT[ u ][ l ] ) || '';

	const cleanup = [];
	const trBefore = new Set( JSON.parse( wp( 'global $wpdb; echo wp_json_encode( $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE \'\\\\_transient\\\\_tp\\\\_ad%\'" ) );' ) || '[]' ) );
	const br = await chromium.launch();
	const density = {};
	const clsNote = [];

	/* زمینۀ سنجش: UA بی‌شمارش و REST نمایش بسته — شمار پایگاه‌داده فقط از گذر شمارش */
	const ctxOff = async ( w, h, o = {} ) => {
		const ctx = await br.newContext( { viewport: { width: w, height: h }, userAgent: UA_OFF } );
		await ctx.route( /\/wp-json\/topersia-ads\/v1\/imp/, r => r.abort() );
		if ( o.probe ) { await ctx.addInitScript( initProbe ); }
		if ( o.view ) { await ctx.addInitScript( v => { try { localStorage.setItem( 'tp-news-view', v ); } catch ( e ) {} }, o.view ); }
		if ( o.mode ) { await ctx.addInitScript( m => { try { localStorage.setItem( 'tp-mode', m ); } catch ( e ) {} }, o.mode ); }
		if ( o.cookies ) { await ctx.addCookies( o.cookies ); }
		return ctx;
	};
	console.log( '\nدستۀ 54 — افزونۀ تبلیغ، نسخۀ محلی ' + new Date().toISOString() );

	try {
		/* ── ۰. عکس «پس» (بند ۱۸) — پیش از هر دادۀ آزمون، فقط fa ── */
		if ( SHOTS ) {
			head( 'عکس «پس» fa ← tests/.screens/54/' );
			fs.mkdirSync( SHOT, { recursive: true } );
			const list = [ [ 'home', '/', '' ], [ 'hub-grid', '/attractions/', 'grid' ], [ 'hub-list', '/attractions/', 'list' ], [ 'news-single', NEWS1, '' ], [ 'province', '/province/isfahan/', '' ], [ 'advertise', '/advertise/', '' ] ];
			const rows = [];
			for ( const [ w, h ] of [ [ 1440, 900 ], [ 390, 844 ] ] ) {
				for ( const [ name, u, view ] of list ) {
					const ctx = await ctxOff( w, h, { view } );
					const p = await ctx.newPage();
					await p.goto( BASE + u, { waitUntil: 'load', timeout: 120000 * X } );
					await settle( 1500 );
					await scrollPass( p );
					const f = name + '-' + w + '.png';
					await p.screenshot( { path: path.join( SHOT, f ), fullPage: true } );
					rows.push( [ name, w, BASE + u + ( view ? ' (' + view + ')' : '' ), f ] );
					await ctx.close();
				}
			}
			const adm = login( 'tp_test_admin' );
			const actx = await ctxOff( 1440, 900, { cookies: adm.cookies } );
			const ap = await actx.newPage();
			for ( const [ name, u ] of [ [ 'admin-report', '/geo/wp-admin/admin.php?page=topersia-ads' ], [ 'admin-list', '/geo/wp-admin/edit.php?post_type=tp_ad' ] ] ) {
				await ap.goto( BASE + u, { waitUntil: 'load', timeout: 120000 * X } );
				await ap.screenshot( { path: path.join( SHOT, name + '-1440.png' ), fullPage: true } );
				rows.push( [ name, 1440, BASE + u, name + '-1440.png' ] );
			}
			await actx.close();
			fs.writeFileSync( path.join( SHOT, 'index.html' ), '<!doctype html><meta charset="utf-8"><title>دستۀ 54 — پس</title><body dir="rtl" style="font-family:sans-serif"><h1>دستۀ 54 — عکس «پس» (fa)</h1>'
				+ rows.map( r => '<h2>' + r[0] + ' · ' + r[1] + '</h2><p dir="ltr">' + r[2] + '</p><a href="' + r[3] + '"><img src="' + r[3] + '" style="max-width:' + ( 1440 === r[1] ? 720 : 390 ) + 'px;border:1px solid #888"></a>' ).join( '' ) + '</body>' );
			t( 'عکس «پس»: ' + rows.length + ' عکس + index.html', rows.length >= 14 );
			if ( SHOTS_ONLY ) { throw SHOTS_DONE; }
		}

		/* ── ۱. جایگاه‌ها: سه زبان × سه پهنا ── */
		head( '۱. جایگاه‌ها — تراکم، فاصله، نخستین صفحه‌نمایش، lazy، CLS، سرریز، برچسب، ویژگی‌ها (مهمان)' );
		const checkPage = async ( label, url, w, h, lang, view, opt = {} ) => {
			const ctx = await ctxOff( w, h, { probe: true, view, cookies: opt.cookies } );
			const p   = await ctx.newPage();
			const res = await p.goto( url, { waitUntil: 'load', timeout: 120000 * X } );
			await settle( 1200 );
			const s   = await p.evaluate( snapshot );
			const m   = await scrollPass( p );
			await ctx.close();
			const vis    = s.units.filter( u => u.vis );
			const stream = vis.filter( u => ! u.side ).sort( ( a, b ) => a.top - b.top );
			const gaps   = stream.slice( 1 ).map( ( u, i ) => u.top - stream[ i ].bot );
			const cap    = w >= 768 ? 3 : 2;
			const bad    = [];
			if ( 200 !== ( res ? res.status() : 0 ) ) { bad.push( 'وضعیت ' + ( res ? res.status() : 0 ) ); }
			if ( vis.length > cap ) { bad.push( 'تراکم ' + vis.length + '>' + cap ); }
			gaps.forEach( ( g, i ) => { if ( g < 2 * s.vh ) { bad.push( 'فاصله ' + stream[ i ].slot + '→' + stream[ i + 1 ].slot + ' ' + ( g / s.vh ).toFixed( 2 ) + ' صفحه‌نمایش' ); } } );
			vis.forEach( u => { if ( u.top < s.vh && ! ( 'hub-top' === u.slot && w >= 768 ) ) { bad.push( 'نخستین صفحه‌نمایش: ' + u.slot + ' @' + u.top ); } } );
			vis.filter( u => ! u.demo ).forEach( u => {
				if ( u.top < s.vh && 'lazy' === u.lazy ) { bad.push( 'lazy در نخستین صفحه‌نمایش: ' + u.slot ); }
				/* «زیر نوار ابزار هاب» تنها جایگاه نخستین صفحه‌نمایش است و سرور جای دقیقش را نمی‌داند ← بی lazy (fetchpriority=low) */
				if ( u.top > 2 * s.vh && 'lazy' !== u.lazy && 'hub-top' !== u.slot ) { bad.push( 'بی lazy دور از صفحه‌نمایش: ' + u.slot ); }
				if ( 'sponsored noopener' !== u.rel || '_blank' !== u.target || ! /\/ad-go\/\d+\/\?s=/.test( u.href || '' ) || ! u.alt || ! u.iw || ! u.ih || ! u.pic ) { bad.push( 'ویژگی واحد واقعی ' + u.slot + ' ' + JSON.stringify( [ u.rel, u.target, u.href, u.alt, u.iw, u.ih, u.pic ] ) ); }
				if ( TXT[ lang ].tag !== u.tag ) { bad.push( 'برچسب «' + u.tag + '»' ); }
			} );
			vis.filter( u => u.demo ).forEach( u => { if ( TXT[ lang ].demo !== u.tag || TXT[ lang ].ph !== u.ph ) { bad.push( 'برچسب نمایشی «' + u.tag + '/' + u.ph + '»' ); } } );
			s.units.forEach( u => { if ( ! u.nosnip ) { bad.push( 'بی data-nosnippet: ' + u.slot ); } } );
			if ( ! s.ftAll ) { bad.push( 'ویژگی واحد پاورقی' ); }
			if ( s.docW > s.vw + 1 ) { bad.push( 'سرریز سند ' + s.docW + '>' + s.vw ); }
			vis.forEach( u => { if ( u.l < -1 || u.r > s.vw + 1 ) { bad.push( 'سرریز واحد ' + u.slot ); } } );
			( m.h0 || [] ).forEach( ( hh, i ) => { if ( hh > 0 && Math.abs( hh - m.h1[ i ] ) > 1 ) { bad.push( 'بلندی واحد ' + i + ' ' + hh + '→' + m.h1[ i ] ); } } );
			m.opens.forEach( o => { if ( o.h > 0 && o.top < o.vh ) { bad.push( 'باز شدن درون صفحه‌نمایش: ' + o.slot + ' @' + o.top ); } } );
			if ( m.adCls > 0.001 ) { bad.push( 'CLS تبلیغ ' + m.adCls.toFixed( 3 ) + ' (' + m.src + ')' ); }
			if ( m.cls > 0.1 ) { clsNote.push( label + ' ' + lang + ' ' + w + ( view ? ' ' + view : '' ) + ': کل ' + m.cls.toFixed( 3 ) + '، تبلیغ ' + m.adCls.toFixed( 3 ) + ' ← ' + m.src ); }
			if ( ! opt.cookies && ( s.comment.length || s.empty.length ) ) { bad.push( 'نشانۀ مدیر برای مهمان' ); }
			if ( s.slots && ! s.js ) { bad.push( 'جایگاه هست ولی tp-ads.js نیست' ); }
			t( label + ' ' + lang + ' ' + w + ( view ? ' ' + view : '' ) + ' — ' + vis.length + ' واحد' + ( vis.length ? ' (' + vis.map( u => u.slot + ( u.demo ? '' : '*' ) ).join( '، ' ) + ')' : '' ) + ' · CLS تبلیغ ' + m.adCls.toFixed( 3 ) + ' / کل ' + m.cls.toFixed( 3 ),
				! bad.length, bad.join( ' · ' ) || ( s.off.length ? 'بسته: ' + s.off.join( ' ' ) : '' ) );
			return { s, m, vis };
		};

		for ( const pg of PAGES ) {
			for ( const lang of LANGS ) {
				const url = urlOf( pg.u, lang );
				if ( ! url ) { console.log( '  ·  ' + pg.k + ' ' + lang + ' — ترجمۀ منتشرشده نیست' ); continue; }
				for ( const [ w, h ] of VIEWS ) {
					for ( const view of ( pg.views || [ '' ] ) ) {
						const r = await checkPage( pg.k, url, w, h, lang, view );
						const key = pg.k + ( view ? ' (' + view + ')' : '' );
						if ( 'fa' === lang ) { density[ key ] = Object.assign( density[ key ] || {}, { [ w ]: r.vis.length, ft: r.s.ft } ); }
					}
				}
			}
		}

		/* میان متن: هیچ خبر محلی ≥۳ بند ندارد ← خبر بلند موقت (منتشر، fa، نشان _tp_test_post) و پاک‌کردن بی‌درنگ */
		const longOut = wp( '$b = ""; for ( $i = 1; $i <= 12; $i++ ) { $b .= "<p>" . str_repeat( "این بند آزمونی دستۀ ۵۴ برای سنجش جای تبلیغ میان متن خبر است و هیچ جای دیگری دیده نمی‌شود. ", 6 ) . "</p>\\n\\n"; } $id = wp_insert_post( array( "post_type" => "tpc_news", "post_status" => "publish", "post_title" => "آزمون جای تبلیغ میان متن", "post_name" => "w54-inline-test", "post_content" => $b ), true ); if ( is_wp_error( $id ) ) { echo ""; return; } update_post_meta( $id, "_tp_test_post", 1 ); do_action( "wpml_set_element_language_details", array( "element_id" => $id, "element_type" => "post_tpc_news", "trid" => false, "language_code" => "fa" ) ); echo $id . " " . get_permalink( $id );' );
		const [ longId, longLink ] = longOut.split( /\s+/ );
		if ( Number( longId ) > 0 ) {
			const dropLong = () => wp( 'wp_delete_post( ' + Number( longId ) + ', true );' );
			cleanup.push( dropLong );
			for ( const [ w, h ] of VIEWS ) {
				const r = await checkPage( 'خبر بلند (میان متن)', longLink, w, h, 'fa', '' );
				t( '  میان متن: در HTML پس از بند سوم و باز ' + w, r.s.units.some( u => 'single-inline' === u.slot ) && r.vis.some( u => 'single-inline' === u.slot ), r.s.off.join( ' ' ) );
				density[ 'خبر بلند' ] = Object.assign( density[ 'خبر بلند' ] || {}, { [ w ]: r.vis.length, ft: r.s.ft } );
			}
			const src = await req( 'GET', longLink, { ua: UA_OFF } );
			/* سومین </p> از نخستین بند خودِ متن خبر، نه از آغاز سند (سربرگ هم بند دارد) */
			const p0  = src.body.indexOf( '<p>این بند آزمونی' );
			let i3    = p0;
			for ( let k = 0; k < 3 && i3 >= 0; k++ ) { i3 = src.body.indexOf( '</p>', i3 + 1 ); }
			i3 = i3 >= 0 ? i3 + 4 : -1;
			const at  = src.body.indexOf( 'tp-ad-slot--single-inline', Math.max( 0, p0 ) );
			const nextP = src.body.indexOf( '<p>', i3 );
			t( '  میان متن درست پس از سومین </p> متن و پیش از بند چهارم', p0 > 0 && i3 > 0 && at > i3 && at < nextP && at - i3 < 200, 'p0 ' + p0 + ' · فاصله ' + ( at - i3 ) + ' · بند بعد ' + ( nextP - i3 ) );
			dropLong();
			cleanup.pop();
		} else {
			t( 'خبر بلند موقت ساخته شد', false, longOut.slice( 0, 160 ) );
		}

		/* ── ۲. صفحه‌های بی تبلیغ، /advertise/، robots ── */
		head( '۲. صفحه‌های بی تبلیغ، /advertise/، robots' );
		const none = [ [ 'شرایط', '/terms/' ], [ 'حریم خصوصی', '/privacy-policy/' ], [ 'جست‌وجو', '/?s=' + encodeURIComponent( 'اصفهان' ) ], [ '۴۰۴', '/w54-no-such-page/' ], [ 'تبلیغ در توپرشیا', '/advertise/' ], [ 'تبلیغ en', '/en/advertise/' ], [ 'تبلیغ ar', '/ar/advertise/' ] ];
		const nctx = await ctxOff( 1440, 900 );
		const np = await nctx.newPage();
		for ( const [ k, u ] of none ) {
			const res = await np.goto( BASE + u, { waitUntil: 'load', timeout: 120000 * X } );
			const s = await np.evaluate( snapshot );
			t( k + ' — بی واحد درون محتوا و بی tp-ads.js (وضعیت ' + ( res ? res.status() : 0 ) + '، پاورقی ' + s.ft + ')', ! s.units.length && ! s.slots && ! s.js && ! s.empty.length );
		}
		await nctx.close();
		for ( const lang of LANGS ) {
			const u = urlOf( '/advertise/', lang );
			for ( const [ w, h ] of [ [ 390, 844 ], [ 1440, 900 ] ] ) {
				const ctx = await ctxOff( w, h );
				const p = await ctx.newPage();
				const res = await p.goto( u, { waitUntil: 'load', timeout: 120000 * X } );
				const a = await p.evaluate( () => {
					const box = document.querySelector( '.tp-adv' ), txt = box ? box.innerText : '';
					return { box: !! box, txt, robots: ( document.querySelector( 'meta[name="robots"]' ) || {} ).content || '', h: document.querySelectorAll( '.tp-adv h2' ).length, docW: document.documentElement.scrollWidth, vw: innerWidth, lang: document.documentElement.lang };
				} );
				const price   = /(\d[\d,٬.۰-۹٠-٩]*\s*(تومان|ریال|دلار|USD|IRR|\$|€))|((تومان|ریال|\$|€)\s*[\d۰-۹٠-٩])/.test( a.txt );
				const letters = 'en' === lang ? /[؀-ۿ]/.test( a.txt ) : ( 'ar' === lang ? /[پچژگکی]/.test( a.txt ) : false );
				t( '/advertise/ ' + lang + ' ' + w + ' — ۲۰۰، noindex، چهار بخش، «' + TXT[ lang ].cta + '»، بی قیمت عددی، بی سرریز' + ( 'fa' !== lang ? '، بی حرف زبان دیگر' : '' ),
					200 === ( res ? res.status() : 0 ) && a.box && /noindex/.test( a.robots ) && a.txt.includes( TXT[ lang ].cta ) && a.h >= 4 && ! price && ! letters && a.docW <= a.vw + 1,
					JSON.stringify( { robots: a.robots, h2: a.h, price, letters, docW: a.docW, lang: a.lang } ) );
				await ctx.close();
			}
		}
		const rb = await req( 'GET', BASE + '/robots.txt' );
		t( 'robots.txt — Disallow: /ad-go/', 200 === rb.status && /Disallow: \/ad-go\//.test( rb.body ) );

		/* ── ۳. کنتراست سه حالت (مهمان، نمونۀ نمایشی) ── */
		head( '۳. کنتراست سه حالت — متن ≥ ۴٫۵ (درشت ≥ ۳)' );
		const contrast = async ( label, url, sel, w = 1440, h = 900, cookies = null, scrollTo = '' ) => {
			for ( const mode of MODES ) {
				const ctx = await ctxOff( w, h, { mode, cookies } );
				const p = await ctx.newPage();
				await p.goto( url, { waitUntil: 'load', timeout: 120000 * X } );
				await settle( 900 );
				if ( scrollTo ) { await p.evaluate( s => { const e = document.querySelector( s ); if ( e ) { e.scrollIntoView( { behavior: 'instant', block: 'center' } ); } }, scrollTo ); await settle( 700 ); }
				const got = await p.evaluate( () => document.documentElement.getAttribute( 'data-tp-mode' ) );
				const r = await p.evaluate( contrastOf, sel );
				const low = r.filter( x => x.ratio < x.need );
				const min = r.length ? Math.min( ...r.map( x => x.ratio ) ) : 0;
				t( label + ' — ' + mode + ' (' + r.length + ' متن، کمینه ' + min + ')', got === mode && r.length > 0 && ! low.length, got !== mode ? 'حالت ' + got : low.map( x => '«' + x.t + '» ' + x.ratio + '<' + x.need ).join( ' · ' ) );
				await ctx.close();
			}
		};
		await contrast( 'خانه: برچسب و جای تبلیغ نمایشی', BASE + '/', '.tp-ad-on .tp-ad__tag, .tp-ad-on .tp-ad__ph', 1440, 900, null, '.tp-ad-on' );
		await contrast( 'هاب: زیر نوار ابزار', BASE + '/attractions/', '.tp-ad-slot--hub-top .tp-ad__tag, .tp-ad-slot--hub-top .tp-ad__ph' );
		await contrast( 'خانه ۳۹۰: برچسب و جای تبلیغ', BASE + '/', '.tp-ad-on .tp-ad__tag, .tp-ad-on .tp-ad__ph', 390, 844, null, '.tp-ad-on' );
		await contrast( 'پاورقی: برچسب تبلیغ', BASE + '/', '.tp-ft-ad__tag', 1440, 900, null, '.tp-ft-ads' );
		for ( const lang of LANGS ) {
			await contrast( '/advertise/ ' + lang + ': همۀ متن', urlOf( '/advertise/', lang ), '.tp-adv' );
		}
		await contrast( '/advertise/ fa ۳۹۰: همۀ متن', BASE + '/advertise/', '.tp-adv', 390, 844 );

		/* ── ۴. تبلیغ آزمونی واقعی: lazy، ویژگی‌ها، شمارش ── */
		head( '۴. تبلیغ آزمونی واقعی — نمایش، کلیک، REST' );
		const adId = Number( wp( 'global $wpdb; $att = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type = \'attachment\' AND post_mime_type IN (\'image/jpeg\',\'image/webp\',\'image/png\') ORDER BY ID DESC LIMIT 1" ); if ( ! $att ) { echo 0; return; } $id = wp_insert_post( array( "post_type" => "tp_ad", "post_status" => "publish", "post_title" => "=W54 آزمون" ), true ); if ( is_wp_error( $id ) ) { echo 0; return; } update_post_meta( $id, "_tp_test_ad", 1 ); update_post_meta( $id, "_tp_ad", array( "advertiser" => "w54", "url" => "' + BASE + '/terms/?w54=1", "slots" => array( "home-mid", "hub-top" ), "img" => array( "wide" => $att, "mwide" => $att, "rect" => $att, "card" => $att ), "alt" => array( "fa" => "آگهی آزمونی دستۀ ۵۴", "en" => "Batch 54 test ad", "ar" => "إعلان اختبار الدفعة 54" ), "langs" => array( "all" ), "device" => "all", "pages" => array( "all" ), "start" => 0, "end" => time() + 3 * DAY_IN_SECONDS, "cap_imp" => 0, "cap_clk" => 0, "weight" => 10, "priority" => 9, "status" => "active" ) ); topersia_ads_bump(); echo $id;' ) );
		t( 'تبلیغ آزمونی ساخته شد #' + adId, adId > 0 );
		if ( adId ) {
			cleanup.push( () => wp( 'global $wpdb; $t = topersia_ads_tables(); wp_delete_post( ' + adId + ', true ); $wpdb->delete( $t["day"], array( "ad_id" => ' + adId + ' ) ); $wpdb->delete( $t["month"], array( "ad_id" => ' + adId + ' ) ); topersia_ads_bump();' ) );
		}
		const demoId = Number( wp( 'global $wpdb; echo (int) $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = \'_tp_demo\' AND meta_value = \'1\' ORDER BY post_id LIMIT 1" );' ) );
		const adm = login( 'tp_test_admin' );

		if ( adId ) {
			/* هاب ۱۴۴۰: «زیر نوار ابزار» واقعی، نخستین صفحه‌نمایش، بی lazy؛ خانه: میانۀ صفحه lazy با <source> موبایل */
			for ( const lang of LANGS ) {
				const r = await checkPage( 'هاب با تبلیغ واقعی', urlOf( '/attractions/', lang ), 1440, 900, lang, '' );
				const u = r.vis.find( x => 'hub-top' === x.slot && ! x.demo );
				t( '  hub-top واقعی ' + lang + ': نخستین صفحه‌نمایش، بی lazy، data-tp-ad و /ad-go/' + adId + ' با جایگاه و زبان', !! u && u.top < 900 && 'lazy' !== u.lazy && u.data === adId + '|hub-top|' + lang && new RegExp( '/ad-go/' + adId + '/\\?s=hub-top&l=' + lang ).test( u.href ), JSON.stringify( u || {} ).slice( 0, 200 ) );
			}
			const rh = await checkPage( 'خانه با تبلیغ واقعی', BASE + '/', 1440, 900, 'fa', '' );
			const uh = rh.vis.find( x => 'home-mid' === x.slot && ! x.demo );
			t( '  home-mid واقعی: lazy، دور از نخستین صفحه‌نمایش', !! uh && 'lazy' === uh.lazy && uh.top > 900, JSON.stringify( uh || {} ).slice( 0, 200 ) );
			await checkPage( 'خانه با تبلیغ واقعی', BASE + '/', 390, 844, 'fa', '' );
			const src = await req( 'GET', BASE + '/', { ua: UA_OFF } );
			t( '  <picture> با <source media="(max-width: 767px)"> برای نسبت موبایل', /<source media="\(max-width: 767px\)" srcset="[^"]+" width="\d+" height="\d+">/.test( src.body ) );
			await contrast( 'برچسب تبلیغ واقعی روی تصویر', BASE + '/attractions/', '.tp-ad-slot--hub-top .tp-ad__tag' );

			/* نمایش: ≥۵۰٪ × ۱ ثانیه ← sendBeacon ← یک ردیف؛ دید کوتاه نه؛ دوباره دیدن در همان بارگذاری نه */
			const gctx = await br.newContext( { viewport: { width: 1440, height: 900 }, userAgent: UA + ' guest-imp' } );
			const gp = await gctx.newPage();
			const beacons = [];
			gp.on( 'request', q => { if ( /\/wp-json\/topersia-ads\/v1\/imp/.test( q.url() ) && 'POST' === q.method() ) { beacons.push( q.postData() || '' ); } } );
			await gp.goto( BASE + '/', { waitUntil: 'load', timeout: 120000 * X } );
			await settle( 800 );
			const sel = '[data-tp-ad="' + adId + '|home-mid|fa"]';
			const into = () => gp.evaluate( s => { const e = document.querySelector( s ); if ( e ) { e.scrollIntoView( { behavior: 'instant', block: 'center' } ); } return !! e; }, sel );
			const top = () => gp.evaluate( () => window.scrollTo( { top: 0, behavior: 'instant' } ) );
			await into();
			await new Promise( r => setTimeout( r, 400 ) );
			await top();
			await settle( 3500 );
			const short = sum( stats( adId ), 'imp' );
			t( 'دید کوتاه (۰٫۴ ثانیه) شمرده نمی‌شود', 0 === short && ! beacons.some( b => b.includes( adId + '|' ) ), 'imp=' + short + ' beacon=' + beacons.length );
			await into();
			await settle( 1300 + 2600 );
			let rows = stats( adId );
			t( 'نمایش ≥۵۰٪ × ۱ ثانیه ← sendBeacon ← imp=1 (home-mid · fa)', 1 === sum( rows, 'imp', r => 'home-mid' === r.slot && 'fa' === r.lang ) && beacons.some( b => b.includes( adId + '|home-mid|fa' ) ), JSON.stringify( rows ) + ' beacons=' + beacons.length );
			await top();
			await settle( 500 );
			await into();
			await settle( 3800 );
			rows = stats( adId );
			t( 'دوباره دیدن در همان بارگذاری شمرده نمی‌شود', 1 === sum( rows, 'imp' ), JSON.stringify( rows ) );
			const token = await gp.evaluate( () => ( window.TopersiaAds || {} ).t || '' );
			const restU = await gp.evaluate( () => ( window.TopersiaAds || {} ).u || '' );

			/* کلیک در مرورگر: برگۀ تازه، بی opener، مقصد */
			await into();
			const [ pop ] = await Promise.all( [ gp.waitForEvent( 'popup', { timeout: 60000 * X } ), gp.click( sel + ' a.tp-ad__a' ) ] );
			await pop.waitForLoadState( 'load', { timeout: 120000 * X } ).catch( () => {} );
			const popInfo = await pop.evaluate( () => ( { url: location.href, noOpener: null === window.opener } ) ).catch( () => ( {} ) );
			t( 'کلیک مرورگر: برگۀ تازه، بی opener، به مقصد', /\/terms\/\?w54=1/.test( popInfo.url || '' ) && true === popInfo.noOpener, JSON.stringify( popInfo ) );
			await settle( 500 );
			rows = stats( adId );
			t( 'کلیک مرورگر شمرده شد (clk=1)', 1 === sum( rows, 'clk' ), JSON.stringify( rows ) );
			await gctx.close();

			/* کلیک بی مرورگر */
			const go = BASE + '/ad-go/' + adId + '/?s=home-mid&l=fa';
			let c = await req( 'GET', go, { ua: UA + ' guest-2' } );
			t( '/ad-go/ مهمان تازه ← ۳۰۲ به مقصد، X-Robots-Tag noindex', 302 === c.status && /\/terms\/\?w54=1/.test( c.headers.location || '' ) && /noindex/.test( c.headers['x-robots-tag'] || '' ), c.status + ' ' + c.headers.location );
			t( '  شمرده شد (clk=2)', 2 === sum( stats( adId ), 'clk' ) );
			c = await req( 'GET', go, { ua: UA + ' guest-2' } );
			t( '  همان بازدیدکننده در ۳۰ دقیقه ← ۳۰۲، شمرده نه', 302 === c.status && 2 === sum( stats( adId ), 'clk' ), c.status + ' clk ' + sum( stats( adId ), 'clk' ) + ' ' + c.body.slice( 0, 80 ) );
			c = await req( 'GET', go, { ua: 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' } );
			t( '  ربات ← ۳۰۲، شمرده نه', 302 === c.status && 2 === sum( stats( adId ), 'clk' ), c.status + ' clk ' + sum( stats( adId ), 'clk' ) );
			c = await req( 'GET', go, { ua: UA + ' guest-pf', headers: { 'Sec-Purpose': 'prefetch' } } );
			t( '  پیش‌واکشی ← ۳۰۲، شمرده نه', 302 === c.status && 2 === sum( stats( adId ), 'clk' ), c.status + ' clk ' + sum( stats( adId ), 'clk' ) + ' ' + c.body.slice( 0, 80 ) );
			c = await req( 'GET', go, { ua: UA + ' admin', cookie: cookieHeader( adm ) } );
			t( '  مدیر تبلیغ ← ۳۰۲، شمرده نه', 302 === c.status && 2 === sum( stats( adId ), 'clk' ), c.status + ' clk ' + sum( stats( adId ), 'clk' ) );
			c = await req( 'GET', BASE + '/ad-go/' + demoId + '/?s=home-mid&l=fa', { ua: UA + ' guest-3' } );
			const c2 = await req( 'GET', BASE + '/ad-go/99999999/', { ua: UA + ' guest-3' } );
			t( '  نمونۀ نمایشی و ناموجود ← ۴۰۴', 404 === c.status && 404 === c2.status, c.status + ' / ' + c2.status );

			/* REST */
			const post = ( body, ua ) => req( 'POST', restU || BASE + '/wp-json/topersia-ads/v1/imp', { ua: ua || UA + ' rest', body } );
			let r = await post( JSON.stringify( { t: token, a: [ adId + '|hub-top|en' ] } ) );
			t( 'REST توکن روز ← ۲۰۴ و imp (hub-top · en) = 1', 204 === r.status && 1 === sum( stats( adId ), 'imp', x => 'hub-top' === x.slot && 'en' === x.lang ), r.status + ' ' + JSON.stringify( stats( adId ) ) );
			r = await post( JSON.stringify( { t: 'bad', a: [ adId + '|hub-top|en' ] } ) );
			t( '  توکن بد ← ۲۰۴، شمرده نه', 204 === r.status && 1 === sum( stats( adId ), 'imp', x => 'en' === x.lang ), r.status + ' ' + r.body.slice( 0, 80 ) );
			r = await post( JSON.stringify( { t: token, a: [ adId + '|hub-top|en' ] } ), 'Mozilla/5.0 (compatible; bingbot/2.0)' );
			t( '  ربات با توکن ← شمرده نه', 204 === r.status && 1 === sum( stats( adId ), 'imp', x => 'en' === x.lang ), r.status + ' ' + r.body.slice( 0, 80 ) );
			r = await post( JSON.stringify( { t: token, a: [ demoId + '|home-mid|fa', adId + '|entity-mid|fa', adId + '|hub-top|xx' ] } ) );
			t( '  نمایشی، جایگاه نه از آن تبلیغ، زبان ناجور ← شمرده نه', 2 === sum( stats( adId ), 'imp' ) && 0 === sum( stats( demoId ), 'imp' ), JSON.stringify( stats( adId ) ) );
			r = await post( JSON.stringify( { t: token, a: [ 'x'.repeat( 5000 ) ] } ) );
			t( '  بدنۀ بیش از ۴۰۹۶ بایت ← ۴۱۳', 413 === r.status, r.status );
			const meta = JSON.parse( wp( 'echo wp_json_encode( array( (int) get_post_meta( ' + adId + ', "_tp_ad_imp", true ), (int) get_post_meta( ' + adId + ', "_tp_ad_clk", true ) ) );' ) );
			t( 'شمار کل پست (برای سقف) = جمع جدول: imp 2 · clk 2', 2 === meta[0] && 2 === meta[1], JSON.stringify( meta ) );

			/* ── ۵. پنل ── */
			head( '۵. پنل — مدیر آزمون، مشترک' );
			const actx = await ctxOff( 1440, 900, { cookies: adm.cookies } );
			const ap = await actx.newPage();
			let res = await ap.goto( BASE + '/geo/wp-admin/admin.php?page=topersia-ads', { waitUntil: 'load', timeout: 120000 * X } );
			const rep = await ap.evaluate( id => {
				const row = [ ...document.querySelectorAll( '.tp-ads-table tbody tr' ) ].find( tr => tr.querySelector( 'a[href*="post=' + id + '&"]' ) );
				const csv = document.querySelector( 'a[href*="action=tp_ads_csv"]' );
				return { nums: row ? [ ...row.querySelectorAll( '.tp-ads-num' ) ].map( td => td.textContent.trim() ) : null, csv: csv ? csv.href : '', h2: [ ...document.querySelectorAll( '.tp-ads-wrap h2' ) ].map( h => h.textContent.trim() ) };
			}, adId );
			t( 'گزارش: ۲۰۰، ردیف تبلیغ آزمونی با همان شمار جدول (نمایش ۲، کلیک ۲)، «پایان نزدیک» و «جایگاه‌های خالی»', 200 === ( res ? res.status() : 0 ) && !! rep.nums && '2' === digits( rep.nums[0] ) && '2' === digits( rep.nums[1] ) && rep.h2.length >= 2, JSON.stringify( rep ).slice( 0, 240 ) );
			const csv = await req( 'GET', rep.csv, { cookie: cookieHeader( adm ) } );
			t( 'CSV: text/csv، ردیف تبلیغ، «=» آغاز خانه خنثی', 200 === csv.status && /text\/csv/.test( csv.headers['content-type'] || '' ) && csv.body.includes( "'=W54" ) && ! /(^|[,;\n]"?)=W54/.test( csv.body ), csv.status + ' ' + csv.headers['content-type'] );
			res = await ap.goto( BASE + '/geo/wp-admin/edit.php?post_type=tp_ad', { waitUntil: 'load', timeout: 120000 * X } );
			const lst = await ap.evaluate( () => ( { rows: document.querySelectorAll( '#the-list tr' ).length, txt: document.body.innerText.includes( '=W54' ) } ) );
			t( 'فهرست tp_ad: ۲۰۰ و تبلیغ آزمونی دیده می‌شود', 200 === ( res ? res.status() : 0 ) && lst.txt && lst.rows > 1, JSON.stringify( lst ) );
			await actx.close();
			const sub = login();
			const sd = await req( 'GET', BASE + '/geo/wp-admin/admin.php?page=topersia-ads', { cookie: cookieHeader( sub ) } );
			const sc = await req( 'GET', BASE + '/geo/wp-admin/admin-post.php?action=tp_ads_csv', { cookie: cookieHeader( sub ) } );
			t( 'مشترک: گزارش و CSV ممنوع', 200 !== sd.status && ! /text\/csv/.test( sc.headers['content-type'] || '' ), sd.status + ' / ' + sc.status );

			/* یادداشت شمار فقط برای مدیر (شمار سرور، پیش از جای JS) */
			const am = await checkPage( 'خانه (مدیر)', BASE + '/', 1440, 900, 'fa', '', { cookies: adm.cookies } );
			t( '  یادداشت شمار واحد فقط برای مدیر: ' + am.s.comment.join( ' ' ), 4 === am.s.comment.length && 'home' === am.s.comment[0] && Number( am.s.comment[1] ) <= 3 && Number( am.s.comment[2] ) <= 2 );
		}

		head( '۶. جایگاه خالی — فقط مدیر (یک نمونۀ نمایشی موقتاً متوقف)' );
		const endId = Number( wp( 'global $wpdb; echo (int) $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = \'_tp_demo_key\' AND meta_value = \'ads-single-end-1\' LIMIT 1" );' ) );
		if ( endId ) {
			const orig = wp( 'echo base64_encode( maybe_serialize( get_post_meta( ' + endId + ', "_tp_ad", true ) ) );' );
			const restore = () => wp( 'update_post_meta( ' + endId + ', "_tp_ad", maybe_unserialize( base64_decode( "' + orig + '" ) ) ); topersia_ads_bump(); echo base64_encode( maybe_serialize( get_post_meta( ' + endId + ', "_tp_ad", true ) ) );' );
			cleanup.push( restore );
			wp( '$a = get_post_meta( ' + endId + ', "_tp_ad", true ); $a["status"] = "paused"; update_post_meta( ' + endId + ', "_tp_ad", $a ); topersia_ads_bump();' );
			const ga = await checkPage( 'تک خبر (مهمان، end متوقف)', BASE + NEWS1, 1440, 900, 'fa', '' );
			t( '  مهمان: بی نشانۀ جایگاه خالی', ! ga.s.empty.length );
			const aa = await checkPage( 'تک خبر (مدیر، end متوقف)', BASE + NEWS1, 1440, 900, 'fa', '', { cookies: adm.cookies } );
			t( '  مدیر: «' + TXT.fa.empty + '»', aa.s.empty.includes( TXT.fa.empty ), JSON.stringify( aa.s.empty ) );
			await contrast( 'نشانۀ جایگاه خالی (مدیر)', BASE + NEWS1, '.tp-ad-empty', 1440, 900, adm.cookies, '.tp-ad-empty' );
			const back = restore();
			cleanup.pop();
			t( '  نمونۀ نمایشی عیناً برگشت', back === orig );
		} else {
			t( 'نمونۀ نمایشی ads-single-end-1 هست', false );
		}
	} catch ( e ) {
		if ( SHOTS_DONE !== e ) { t( 'اجرا بی خطا', false, String( e && e.stack || e ).slice( 0, 400 ) ); }
	} finally {
		for ( const f of cleanup.reverse() ) {
			try { f(); } catch ( e ) { t( 'پاک‌سازی', false, String( e ).slice( 0, 200 ) ); }
		}
		try {
			const now = JSON.parse( wp( 'global $wpdb; echo wp_json_encode( $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE \'\\\\_transient\\\\_tp\\\\_ad%\'" ) );' ) || '[]' );
			const fresh = now.filter( n => ! trBefore.has( n ) ).map( n => n.replace( /^_transient_/, '' ) );
			if ( fresh.length ) { wp( 'foreach ( ' + phpArr( fresh ) + ' as $k ) { delete_transient( $k ); }' ); }
			const left = JSON.parse( wp( 'global $wpdb; echo wp_json_encode( array( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN (\'_tp_test_ad\', \'_tp_test_post\')" ), (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_name LIKE \'w54-inline-test%\'" ) ) );' ) );
			t( 'پاک‌سازی: بی تبلیغ و خبر آزمونی، ' + fresh.length + ' گذرای تازه پاک شد', 0 === left[0] && 0 === left[1], JSON.stringify( left ) );
		} catch ( e ) {
			t( 'پاک‌سازی', false, String( e ).slice( 0, 200 ) );
		}
		await br.close();
	}

	head( 'تراکم — شمار واحدهای دیدنی در هر صفحه (fa، مهمان؛ پاورقی جدا)' );
	for ( const [ k, v ] of Object.entries( density ) ) {
		console.log( '  ' + k.padEnd( 22 ) + ' ۳۹۰: ' + ( v[390] ?? '—' ) + '   ۷۶۸: ' + ( v[768] ?? '—' ) + '   ۱۴۴۰: ' + ( v[1440] ?? '—' ) + '   پاورقی: ' + ( v.ft ?? '—' ) );
	}
	if ( clsNote.length ) {
		head( 'CLS کل صفحه بالای ۰٫۱ — از تبلیغ نیست (منشأ: گره‌های جابه‌جاشده)' );
		clsNote.forEach( s => console.log( '  · ' + s ) );
	}
	console.log( '\n' + '─'.repeat( 78 ) + '\nدستۀ 54: ' + pass + ' گذشت · ' + fail + ' افتاد' );
	process.exit( fail ? 1 : 0 );
} )();
