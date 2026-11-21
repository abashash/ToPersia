/**
 * دستۀ 56-الف بند ۵ — گیت ساختاری تبلیغ میان‌فهرستی (P5-D641 · P5-D650). فقط topersia.local، Chromium.
 *
 *   node tests/ads-infeed-gate.js [--only=fa,en,ar] [--quick]
 *
 * فهرست قالب‌ها از خود سایت می‌آید (scripts/local/tp-ads-infeed-pages.php: هر هاب ثبت‌نامه، گروه، فصل، برچسب،
 * اخبار، مقالات، خبرهای موجودیت) و همان فهرست حسابرسی docs/ops/ADS-INFEED-AUDIT.md است. برای هر صفحه × fa/en/ar × ۱۴۴۰ ·
 * ۷۶۸ · ۳۹۰ × شبکه/فهرست، پیش و پس از پالایش و «نمایش بیشتر»:
 *   ▸ جای هر آگهی دیده‌شده = خانۀ ۶، ۱۸، ۳۰ یا ۴۲ بر پایۀ کارت‌های دیده‌شده؛ سقف ۴؛ هرگز آخرین خانه؛ زیر ۶ کارت هیچ؛
 *   ▸ صفحۀ ≥ ۶ کارت در پیش‌نمایش جایگاه دارد (نمونه یا نشانگر «جایگاه تبلیغ · خالی») — قالب تازه بی جایگاه قرمز است؛
 *   ▸ آگهی بیرون از JSON-LD و شمار اعلام‌شده؛ data-nosnippet؛ پیوند آگهی rel="sponsored noopener"؛
 *   ▸ نمای فهرست: آگهی یک ردیف تمام‌عرض؛ CLS بارگذاری < ۰٫۱.
 * ایستا: هر قالب templates/*.php که شبکۀ کارت (data-tp-news-items) دارد topersia_g_ad_feed را صدا می‌زند.
 * جهش (درون همان اجرا): جابه‌جایی یک آگهی در DOM، و قالب بی topersia_g_ad_feed در سنجش ایستا — هر دو باید قرمز شوند.
 *
 * یک زنجیره در هر زمان؛ هم‌زمان هیچ درخواست دیگری به سایت. کد خروج: ۰ سبز · ۱ افتادن · ۲ محیط ناقص.
 */
'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const cp   = require( 'child_process' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE   = ENV.base.replace( /\/$/, '' );
const X      = Math.max( 1, Number( process.env.TP_TIMEOUT_X || 1 ) );
const ONLY   = ( process.argv.find( a => a.startsWith( '--only=' ) ) || '' ).slice( 7 ).split( ',' ).filter( Boolean );
const QUICK  = process.argv.includes( '--quick' );
const LANGS  = [ [ 'fa', '' ], [ 'en', '/en' ], [ 'ar', '/ar' ] ].filter( l => ! ONLY.length || ONLY.includes( l[0] ) );
const WIDTHS = [ [ 1440, 900 ], [ 768, 1024 ], [ 390, 844 ] ];

let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; if ( ! ok || ! QUICK ) { console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( note ? '   ← ' + note : '' ) ); } };

/* ── ایستا: قالب شبکۀ کارت ← topersia_g_ad_feed ── */
const TPL = path.join( ENV.workspace, 'plugin/topersia-render/templates' );
const staticOk = src => ! /data-tp-news-items/.test( src ) || /topersia_g_ad_feed\s*\(/.test( src );
function staticCheck() {
	console.log( '\nایستا — هر قالب شبکۀ کارت از رندرر مشترک جایگاه می‌گذرد\n' + '─'.repeat( 78 ) );
	for ( const f of fs.readdirSync( TPL ).filter( x => x.endsWith( '.php' ) ) ) {
		const src = fs.readFileSync( path.join( TPL, f ), 'utf8' );
		if ( /data-tp-news-items/.test( src ) ) { t( `قالب ${ f }: شبکۀ کارت ← topersia_g_ad_feed()`, staticOk( src ) ); }
	}
	const sample = fs.readFileSync( path.join( TPL, 'hub-archive.php' ), 'utf8' ).replace( /topersia_g_ad_feed\s*\(/g, 'array_values(' );
	t( 'جهش ایستا: قالب با شبکۀ کارت و بی topersia_g_ad_feed ← قرمز', ! staticOk( sample ) );
}

/* ── سنجش DOM یک صفحه (درون مرورگر) ── */
const PROBE = () => {
	const box = document.querySelector( '[data-tp-news-items]' );
	if ( ! box ) { return null; }
	const vis = e => { const r = e.getBoundingClientRect(), cs = getComputedStyle( e ); return ! e.hidden && 'none' !== cs.display && r.width > 0 && r.height > 0; };
	const kids = [ ...box.children ].filter( vis );
	let cards = 0, ads = 0;
	const cells = [], wide = [];
	kids.forEach( ( e, i ) => {
		if ( e.matches( '.tp-news-card' ) ) { cards++; return; }
		if ( e.matches( '.tp-ad-slot--hub-infeed' ) ) {
			ads++;
			cells.push( i + 1 );
			wide.push( Math.abs( e.getBoundingClientRect().width - box.getBoundingClientRect().width ) < 2 );
		}
	} );
	const last = kids.length ? kids[ kids.length - 1 ] : null;
	const ld = [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].map( s => s.textContent ).join( '\n' );
	const links = [ ...box.querySelectorAll( '.tp-ad-slot a[href]' ) ];
	const msg = ( document.querySelector( '[data-tp-news-msg]' ) || { getAttribute: () => '' } ).getAttribute( 'data-tp-news-msg' ) || '';
	const n = s => +String( s ).replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ).replace( /[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf( d ) ).replace( /\D/g, '' );
	let items = -1;
	try {
		const walk = o => { if ( ! o || 'object' !== typeof o ) { return; } if ( 'ItemList' === o['@type'] && Array.isArray( o.itemListElement ) ) { items = Math.max( items, o.itemListElement.length ); } Object.values( o ).forEach( walk ); };
		document.querySelectorAll( 'script[type="application/ld+json"]' ).forEach( s => walk( JSON.parse( s.textContent ) ) );
	} catch ( e ) { /* JSON-LD نامعتبر را گیت‌های اسکیما می‌گیرند */ }
	return {
		cards, ads, cells, wide, items,
		lastIsAd: !! last && last.matches( '.tp-ad-slot' ),
		adInLd: links.some( a => ld.includes( a.getAttribute( 'href' ) ) ) || /tp-ad-|"Advertisement"|جایگاه تبلیغ/.test( ld ),
		rel: links.every( a => 'sponsored noopener' === a.getAttribute( 'rel' ) ),
		snip: [ ...box.querySelectorAll( '.tp-ad-slot--hub-infeed' ) ].every( s => s.hasAttribute( 'data-nosnippet' ) || s.querySelector( '[data-nosnippet]' ) ),
		announced: /[0-9۰-۹٠-٩]/.test( msg ) ? n( msg ) : -1,
		rows: box.classList.contains( 'tp-news-grid--rows' ),
	};
};

let RULE = null;
const expectCells = cards => cards < RULE.min ? [] : RULE.cells.filter( ( c, m ) => c - 1 - m < cards );
function judge( tag, g, preview ) {
	const want = expectCells( g.cards );
	t( `${ tag }: آگهی در خانه‌های ${ want.join( '،' ) || '—' } (${ g.cards } کارت)`, JSON.stringify( g.cells ) === JSON.stringify( want ), `دیده‌شده [${ g.cells }]` );
	t( `${ tag }: سقف ۴، هرگز آخرین خانه`, g.ads <= 4 && ! g.lastIsAd );
	if ( preview && g.cards >= RULE.min ) { t( `${ tag }: پیش‌نمایش ← جایگاه دارد (قالب بی جایگاه قرمز)`, g.ads > 0, `${ g.ads } جایگاه` ); }
	t( `${ tag }: آگهی بیرون از JSON-LD؛ data-nosnippet؛ rel=sponsored noopener`, ! g.adInLd && g.snip && g.rel );
	if ( g.items >= 0 ) { t( `${ tag }: ItemList فقط کارت (${ g.items } ≤ کارت‌ها)`, g.items <= Math.max( g.cards, g.announced ), `ItemList ${ g.items } · کارت ${ g.cards }` ); }
	if ( g.announced >= 0 ) { t( `${ tag }: شمار اعلام‌شده بی آگهی`, g.announced >= g.cards, `اعلام ${ g.announced } · کارت ${ g.cards } · آگهی ${ g.ads }` ); }
	if ( g.rows && g.ads ) { t( `${ tag }: نمای فهرست ← آگهی یک ردیف تمام‌عرض`, g.wide.every( Boolean ) ); }
}

function sitePages() {
	const out = cp.execFileSync( ENV.php, [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + ENV.base, 'eval-file', path.join( ENV.workspace, 'scripts/local/tp-ads-infeed-pages.php' ) ], { maxBuffer: 1 << 24 } ).toString();
	return JSON.parse( out.slice( out.indexOf( '{' ) ) );
}

const loadCls = page => page.evaluate( () => new Promise( ok => {
	let s = 0;
	try {
		new PerformanceObserver( l => l.getEntries().forEach( e => { if ( ! e.hadRecentInput ) { s += e.value; } } ) ).observe( { type: 'layout-shift', buffered: true } );
	} catch ( e ) { ok( -1 ); return; }
	setTimeout( () => ok( s ), 1500 );
} ) );

async function onePage( page, L, pre, pg, P, state ) {
	const res = await page.goto( BASE + pre + pg.u, { waitUntil: 'load', timeout: 120000 * X } ).catch( () => null );
	/* ۴۰۴ در زبان دیگر یعنی این زبان این فهرست را ندارد (پوشش در گزارش حسابرسی)؛ هر چیز دیگر — مهلت، ۵۰۰، یا ۴۰۴ در fa — افتادن است، نه پرش بی‌صدا */
	if ( ! res || 200 !== res.status() ) {
		const code = res ? res.status() : 0;
		if ( ! ( 404 === code && 'fa' !== L ) ) { t( `${ L } ${ pg.k }: بارگذاری ۲۰۰`, false, code ? `کد ${ code }` : 'مهلت' ); }
		return;
	}
	const c = await loadCls( page );
	t( `${ L } ${ pg.k }: CLS بارگذاری < ۰٫۱`, c >= 0 && c < 0.1, Number( c ).toFixed( 3 ) );
	for ( const [ w, h ] of ( QUICK ? [ WIDTHS[0] ] : WIDTHS ) ) {
		await page.setViewportSize( { width: w, height: h } );
		for ( const view of [ 'grid', 'list' ] ) {
			const btn = await page.$( `[data-tp-view="${ view }"]` );
			if ( btn ) { await btn.click().catch( () => {} ); await page.waitForTimeout( 250 ); }
			const g = await page.evaluate( PROBE );
			if ( ! g ) { return; }
			if ( P.preview && g.cards >= RULE.min ) { state.eligible = true; }
			judge( `${ L } ${ w } ${ pg.k } (${ 'list' === view ? 'فهرست' : 'شبکه' })`, g, P.preview );
			if ( ! btn ) { break; }
		}
	}
	await page.setViewportSize( { width: WIDTHS[0][0], height: WIDTHS[0][1] } );
	const gridBtn = await page.$( '[data-tp-view="grid"]' );
	if ( gridBtn ) { await gridBtn.click().catch( () => {} ); }
	/* «نمایش بیشتر»: کارت تازه، جای آگهی بر پایۀ همۀ کارت‌ها؛ آگهی صفحۀ بعد افزوده نمی‌شود */
	const more = await page.$( 'a[data-tp-load-more]' );
	if ( more ) {
		await more.evaluate( a => a.scrollIntoView( { behavior: 'instant', block: 'center' } ) );
		const n0 = ( await page.evaluate( PROBE ) ).cards;
		await more.click();
		await page.waitForFunction( n => document.querySelectorAll( '[data-tp-news-items] > .tp-news-card' ).length > n, n0, { timeout: 60000 * X } ).catch( () => {} );
		await page.waitForTimeout( 400 );
		judge( `${ L } ${ pg.k } پس از «نمایش بیشتر»`, await page.evaluate( PROBE ), false );
	}
	/* پالایش سمت کاربر: دو کارت پنهان ← بازچینی بر پایۀ کارت‌های دیده‌شده */
	const hid = await page.evaluate( () => { const c = document.querySelectorAll( '[data-tp-news-items] > .tp-news-card' ); if ( c.length > 3 ) { c[1].hidden = true; c[2].hidden = true; } return c.length; } );
	if ( hid > 3 ) {
		await page.waitForTimeout( 400 );
		judge( `${ L } ${ pg.k } پس از پنهان‌شدن دو کارت`, await page.evaluate( PROBE ), false );
	}
	/* جهش DOM، یک بار در اجرا: آگهی نخست دو خانه جلوتر ← قرمز؛ سپس tp-ads-feed.js برمی‌گرداند */
	if ( ! state.mut ) {
		const moved = await page.evaluate( () => {
			const s = document.querySelector( '[data-tp-news-items] > .tp-ad-slot--hub-infeed:not([hidden])' );
			const a = s && s.nextElementSibling, b = a && a.nextElementSibling;
			if ( ! b ) { return false; }
			s.parentNode.insertBefore( s, b.nextElementSibling );
			return true;
		} );
		if ( moved ) {
			const m = await page.evaluate( PROBE );
			t( 'جهش DOM: آگهی دو خانه جابه‌جا ← سنجش خانه قرمز می‌شود', JSON.stringify( m.cells ) !== JSON.stringify( expectCells( m.cards ) ), `[${ m.cells }] / [${ expectCells( m.cards ) }]` );
			await page.waitForTimeout( 600 );
			const back = await page.evaluate( PROBE );
			t( 'بازچینی tp-ads-feed.js جابه‌جایی را برمی‌گرداند', JSON.stringify( back.cells ) === JSON.stringify( expectCells( back.cards ) ), `[${ back.cells }]` );
			state.mut = true;
		}
	}
	/* پالایش سمت سرور */
	if ( pg.f ) {
		const r2 = await page.goto( BASE + pre + pg.f, { waitUntil: 'load', timeout: 120000 * X } ).catch( () => null );
		const g = r2 && 200 === r2.status() ? await page.evaluate( PROBE ) : null;
		if ( g ) { judge( `${ L } ${ pg.k } پس از پالایش`, g, P.preview ); }
	}
}

( async () => {
	const t0 = Date.now();
	console.log( 'START ads-infeed-gate ' + new Date().toISOString() );
	staticCheck();
	const P = sitePages();
	RULE = P.rule;
	t( 'قاعدۀ سایت = P5-D641 (خانه‌های ۶، ۱۸، ۳۰، ۴۲؛ کمینه ۶)', '[6,18,30,42]' === JSON.stringify( RULE.cells ) && 6 === RULE.min, JSON.stringify( RULE ) );
	t( 'نسخۀ محلی در پیش‌نمایش است (جایگاه برای همه دیدنی؛ بی آن فقط مدیر سنجیده می‌شد)', true === P.preview, String( P.preview ) );
	const state = { mut: false, eligible: false };
	const br = await chromium.launch();
	try {
		for ( const [ L, pre ] of LANGS ) {
			console.log( `\n${ L } — ${ P.pages.length } قالب\n` + '─'.repeat( 78 ) );
			const ctx = await br.newContext( { viewport: { width: WIDTHS[0][0], height: WIDTHS[0][1] } } );
			const page = await ctx.newPage();
			for ( const pg of P.pages ) {
				await onePage( page, L, pre, pg, P, state );
			}
			await ctx.close();
		}
	} finally {
		await br.close();
	}
	/* زبانی که هیچ فهرست ۶کارتی ندارد (ترجمه‌ها هنوز کم‌اند) آگهی ندارد و جهش جایی برای اجرا ندارد — «آزموده نشد»، نه سبز */
	if ( state.eligible ) {
		t( 'جهش DOM اجرا شد (دست‌کم یک صفحه با آگهی)', state.mut );
	} else {
		console.log( '  ⚠ جهش DOM آزموده نشد: در این زبان‌ها هیچ فهرستی ۶ کارت یا بیشتر ندارد' );
	}
	console.log( '\n' + '─'.repeat( 78 ) + `\nads-infeed-gate: ${ pass } گذشت · ${ fail } افتاد · ${ Math.round( ( Date.now() - t0 ) / 1000 ) } ثانیه\nEND ads-infeed-gate` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
