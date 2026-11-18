/**
 * دستۀ 53 — روی نسخۀ محلی (فقط topersia.local). بند ۲۱.
 *
 *   node tests/w53-local.js
 *
 * هاب‌ها (/city/ · /province/ · /attractions/ · /tags/nature-tourism/ · /season/spring/ · /articles/)، fa ۱۴۴۰:
 *   ▸ نوار ابزار یک‌خطی: جست‌وجو + ترکیب‌باکس مکان + مرتب‌سازی + شبکه/فهرست، هم‌تراز (بند ۲)؛ ۳۹۰: دکمۀ «فیلتر»
 *   ▸ «وسعت» فقط در هاب نوع دارای فیلد وسعت (بند ۳)
 *   ▸ هر کاشی عکس هیرو محلی با ابعاد صریح یا جایگزین نوع (بند ۴)
 *   ▸ فهرست: ۲ ستون در ۱۴۴۰، ۱ ستون در ۳۹۰ (بند ۵)
 *   ▸ زیرعنوان /city/: «… در استان X» با پیوند به هاب استان (بند ۶)
 * تراشه‌ها (/tags/nature-tourism/ و /news/) در fa · en · ar، ۳۹۰: دکمۀ «بعدی» به سوی پایان منطقی می‌برد و زیر هر ردیف نوار پیشرفت (بند ۷)
 * عنوان هاب سه‌زبانه، بی «همهٔ مکان‌ها و مطالب»، انگلیسی بی حرف فارسی (بند ۸)
 * «مشاهده همه» هر اسلایدر صفحۀ اول ← هاب همان نوع، ۲۰۰ (بند ۹)
 * صفحۀ تکی خبر: اسکلت صفحۀ استان (ناوبری بخش‌ها، اشتراک/ذخیره، منابع، نظرات)، ترتیب ناوبری بند ۱۱، نظرات (بند ۱۰ تا ۱۳)
 * پاورقی ۰ تا ۴ تبلیغ: tests/footer-gate.js (جدا؛ این‌جا فقط عکس ۴ و ۱ تبلیغ).
 * عکس «پس» فقط fa (بند ۲۲): tests/.screens/53/ (بیرون از git).
 *
 * کد خروج: ۰ سبز · ۱ افتادن · ۲ محیط ناقص.
 */
'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const http = require( 'http' );
const { spawnSync } = require( 'child_process' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE = ENV.base.replace( /\/$/, '' );
const X    = Math.max( 1, Number( process.env.TP_TIMEOUT_X || 1 ) );
const SHOT = path.join( __dirname, '.screens', '53' );
const HUBS = [
	{ u: '/city/', area: true, sub: true },
	{ u: '/province/', area: true, noLoc: true },   /* هاب استان پالایش مکان ندارد (topersia_g_hub_row: loc=false برای کشور/منطقه/استان) */
	{ u: '/attractions/' },
	{ u: '/tags/nature-tourism/' },
	{ u: '/season/spring/' },
	{ u: '/articles/', area: false },
];
let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( note ? '   ← ' + note : '' ) ); };
const settle = ms => new Promise( r => setTimeout( r, ms ) );

const status1 = u => new Promise( res => {
	const q = http.get( u, { headers: { 'User-Agent': 'tp-w53' } }, r => { r.resume(); res( r.statusCode ); } );
	q.on( 'error', () => res( 0 ) );
	q.setTimeout( 60000 * X, () => { q.destroy(); res( 0 ); } );
} );
const status = async u => { const a = await status1( u ); return 200 === a ? a : status1( u ); };

const wp = code => {
	const r = spawnSync( ENV.php, [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + BASE, 'eval', code ], { encoding: 'utf8', timeout: 180000 * X } );
	if ( 0 !== r.status ) { throw new Error( 'wp-cli: ' + String( r.stderr || '' ).slice( 0, 200 ) ); }
	return String( r.stdout || '' ).trim();
};
const setDemo = n => wp( '$o = get_option( "topersia_footer_blocks", array() ); $o = is_array( $o ) ? $o : array(); $o["demo_ads"] = ' + Number( n ) + '; update_option( "topersia_footer_blocks", $o, false );' );

/* ── در صفحه ── */
function probeHub() {
	const vis = e => { if ( ! e ) { return false; } const r = e.getBoundingClientRect(), cs = getComputedStyle( e ); return r.width > 0 && r.height > 0 && 'hidden' !== cs.visibility && 'none' !== cs.display; };
	const bar  = document.querySelector( '.tp-news-bar' );
	const q    = bar && bar.querySelector( 'input[type="search"], input[name="q"], input[name="tp_q"]' );
	const loc  = bar && bar.querySelector( '.tp-loc__in' );
	const sel  = bar && bar.querySelector( 'select' );
	const view = document.querySelector( '[data-tp-news-view]' );
	const parts = [ q, loc, sel, view ];
	const mids = parts.filter( vis ).map( e => { const r = e.getBoundingClientRect(); return r.top + r.height / 2; } );
	const opts = sel ? [ ...sel.options ].map( o => o.value ) : [];
	const cards = [ ...document.querySelectorAll( '.tp-news-card' ) ];
	const media = cards.map( c => {
		const m = c.querySelector( '.tp-news-card__media' );
		const i = m && m.querySelector( 'img' );
		if ( i ) { const src = i.currentSrc || i.src || ''; return /^https?:\/\/topersia\.local\//.test( src ) && i.getAttribute( 'width' ) && i.getAttribute( 'height' ) ? 'img' : 'bad:' + src.slice( 0, 60 ); }
		return m && ( m.querySelector( 'svg' ) || 'none' !== getComputedStyle( m ).backgroundImage ) ? 'fallback' : 'none';
	} );
	const subs = [ ...document.querySelectorAll( '.tp-hub-card__sub' ) ].map( s => ( { txt: s.textContent.trim(), prov: !! s.querySelector( 'a[href*="/province/"]' ) } ) );
	const filterBtn = [ ...document.querySelectorAll( '.tp-news-bar button, .tp-news-bar summary' ) ].find( b => vis( b ) && /فیلتر|filter|تصفية|فلتر/i.test( b.textContent ) );
	return {
		bar: !! bar, parts: parts.map( vis ), oneLine: mids.length >= 3 && Math.max( ...mids ) - Math.min( ...mids ) <= 12,
		area: opts.includes( 'area_asc' ) && opts.includes( 'area_desc' ), opts,
		cards: cards.length, media, subs, filterBtn: !! filterBtn,
		h1: ( document.querySelector( 'h1' ) || { textContent: '' } ).textContent.trim(),
		oldRow: !! document.querySelector( '.tp-hub-filters, .tp-filter-rows' ),
	};
}

function columns() {
	const xs = [ ...document.querySelectorAll( '.tp-news-card' ) ].filter( c => c.getBoundingClientRect().width > 0 ).map( c => Math.round( c.getBoundingClientRect().left / 8 ) );
	return [ ...new Set( xs ) ].length;
}

function probeChips() {
	const rows = [ ...document.querySelectorAll( '.tp-news-chips' ) ].filter( r => r.getBoundingClientRect().width > 0 );
	return rows.map( r => {
		const list = r.querySelector( '.tp-news-chips__list' );
		const navs = [ ...r.querySelectorAll( '.tp-news-chips__nav' ) ];
		const sib  = r.nextElementSibling;
		const hasProg = !! r.querySelector( '[class*="prog"]' ) || !! ( sib && /prog/.test( String( sib.className ) ) );
		return { over: list ? list.scrollWidth > list.clientWidth + 2 : false, navs: navs.length, prog: hasProg, dir: getComputedStyle( r ).direction };
	} );
}

/* ── راننده ── */
( async () => {
	let prevDemo;
	try {
		prevDemo = wp( '$o = get_option( "topersia_footer_blocks", array() ); echo isset( $o["demo_ads"] ) ? (int) $o["demo_ads"] : 2;' );
	} catch ( e ) {
		console.error( 'محیط ناقص: ' + e.message );
		process.exit( 2 );
	}
	fs.mkdirSync( SHOT, { recursive: true } );
	console.log( '\nدستۀ 53 — نسخۀ محلی ' + new Date().toISOString() + '\n' + '─'.repeat( 78 ) );
	const br  = await chromium.launch();
	const ctx = await br.newContext( { viewport: { width: 1440, height: 900 } } );
	const p   = await ctx.newPage();
	const go  = async ( u, w ) => {
		await p.setViewportSize( { width: w || 1440, height: 900 } );
		const r = await p.goto( u, { waitUntil: 'load', timeout: 180000 * X } );
		await settle( 700 * X );
		return r ? r.status() : 0;
	};
	/* عکس: صفحۀ کامل، یا یک عنصر با پنهان‌کردن موقت هر fixed/sticky بیرون از آن */
	const shot = async ( file, sel ) => {
		await p.evaluate( s => {
			const target = s ? document.querySelector( s ) : null;
			document.querySelectorAll( 'body *' ).forEach( e => {
				if ( target && ( target.contains( e ) || e.contains( target ) ) ) { return; }
				const pos = getComputedStyle( e ).position;
				if ( ( s && ( 'fixed' === pos || 'sticky' === pos ) ) || 'wpadminbar' === e.id ) { e.setAttribute( 'data-tp-shot-hide', '1' ); e.style.setProperty( 'display', 'none', 'important' ); }
			} );
		}, sel || null );
		if ( sel ) {
			await p.evaluate( s => { const e = document.querySelector( s ); if ( e ) { e.scrollIntoView( { behavior: 'instant', block: 'start' } ); } }, sel );
			await settle( 1200 * X );
			const el = await p.$( sel );
			if ( el ) { await el.screenshot( { path: path.join( SHOT, file ) } ); }
		} else {
			await p.screenshot( { path: path.join( SHOT, file ), fullPage: true } );
		}
		await p.evaluate( () => document.querySelectorAll( '[data-tp-shot-hide]' ).forEach( e => { e.style.removeProperty( 'display' ); e.removeAttribute( 'data-tp-shot-hide' ); } ) );
	};

	try {
		/* ── هاب‌ها ── */
		for ( const h of HUBS ) {
			const st = await go( BASE + h.u, 1440 );
			const H  = await p.evaluate( probeHub );
			t( `هاب ${ h.u } · ۱۴۴۰: نوار ابزار یک‌خطی (بند ۲)`, 200 === st && H.bar && H.oneLine && H.parts[0] && ( h.noLoc ? ! H.parts[1] : H.parts[1] ) && H.parts[2] && ! H.oldRow,
				`وضعیت ${ st }، دیدنی [جست‌وجو ${ H.parts[0] }، مکان ${ H.parts[1] }، مرتب‌سازی ${ H.parts[2] }، نما ${ H.parts[3] }]، ردیف قدیمی ${ H.oldRow }` );
			if ( undefined !== h.area ) {
				t( `هاب ${ h.u }: گزینۀ «وسعت» ${ h.area ? 'هست' : 'نیست' } (بند ۳)`, H.area === h.area, 'گزینه‌ها ' + H.opts.join( '، ' ) );
			}
			const badM = H.media.filter( m => 'img' !== m && 'fallback' !== m );
			t( `هاب ${ h.u }: هر کاشی عکس هیرو محلی یا جایگزین نوع (بند ۴)`, H.cards > 0 && ! badM.length, `${ H.cards } کاشی، عکس ${ H.media.filter( m => 'img' === m ).length }، جایگزین ${ H.media.filter( m => 'fallback' === m ).length }` + ( badM.length ? '، ناجور ' + badM.slice( 0, 3 ).join( ' | ' ) : '' ) );
			if ( h.sub ) {
				const noProv = H.subs.filter( s => ! s.prov );
				t( `هاب ${ h.u }: زیرعنوان «… در استان X» با پیوند هاب استان (بند ۶)`, H.subs.length > 0 && noProv.length <= Math.floor( H.subs.length * 0.1 ),
					`${ H.subs.length } زیرعنوان، بی پیوند استان ${ noProv.length }؛ نمونه «${ ( H.subs[0] || {} ).txt || '' }»` );
			}
			if ( '/city/' === h.u ) { await shot( 'city-grid-1440.png' ); }
			if ( '/tags/nature-tourism/' === h.u ) { await shot( 'tag-nature-tourism-1440.png' ); }
			/* فهرست: ۲ ستون ۱۴۴۰، ۱ ستون ۳۹۰ */
			const listBtn = await p.$( '[data-tp-news-view] [data-tp-view="list"]' );
			if ( listBtn && await listBtn.isVisible() ) {
				await listBtn.evaluate( b => b.scrollIntoView( { behavior: 'instant', block: 'center' } ) );
				await listBtn.click();
				await settle( 600 * X );
				const c14 = await p.evaluate( columns );
				if ( '/city/' === h.u ) { await shot( 'city-list-1440.png' ); }
				await p.setViewportSize( { width: 390, height: 900 } );
				await settle( 800 * X );
				const c39 = await p.evaluate( columns );
				if ( '/city/' === h.u ) { await shot( 'city-list-390.png' ); }
				t( `هاب ${ h.u }: فهرست ۲ ستون در ۱۴۴۰ و ۱ ستون در ۳۹۰ (بند ۵)`, 2 === c14 && 1 === c39, `ستون‌ها ${ c14 } / ${ c39 }` );
				await p.evaluate( () => { const b = document.querySelector( '[data-tp-news-view] [data-tp-view="grid"]' ); if ( b ) { b.click(); } } );
			} else {
				t( `هاب ${ h.u }: دکمۀ نمای فهرست دیدنی (بند ۵)`, false, 'نیست' );
			}
			/* ۳۹۰: دکمۀ فیلتر */
			await go( BASE + h.u, 390 );
			const M = await p.evaluate( probeHub );
			t( `هاب ${ h.u } · ۳۹۰: دکمۀ «فیلتر» (جست‌وجو${ h.noLoc ? '' : ' و مکان' } جمع‌شده)`, M.filterBtn && ! M.parts[0] && ! M.parts[1], `فیلتر ${ M.filterBtn }، جست‌وجو دیدنی ${ M.parts[0] }، مکان دیدنی ${ M.parts[1] }` );
			if ( '/city/' === h.u ) { await shot( 'city-grid-390.png' ); }
		}

		/* ── تراشه‌ها: جهت فلش و نوار پیشرفت (بند ۷) ── */
		for ( const pg of [ '/tags/nature-tourism/', '/news/', '/articles/' ] ) {
			for ( const [ lang, pre ] of [ [ 'fa', '' ], [ 'en', '/en' ], [ 'ar', '/ar' ] ] ) {
				const st = await go( BASE + pre + pg, 390 );
				if ( 200 !== st ) { t( `تراشه ${ pg } · ${ lang }`, 'fa' !== lang, `وضعیت ${ st } (ترجمۀ منتشرنشده «نیست»)` ); continue; }
				const C = await p.evaluate( probeChips );
				/* بی تراشه فقط وقتی پذیرفته است که هیچ خبر این زبان موضوعی نداشته باشد (topersia_g_news_chips: کمتر از دو مورد ← هیچ) — داده، نه کد */
				if ( ! C.length ) {
					const sw = 'do_action( "wpml_switch_language", "' + lang + '" ); ';
					const nt = '/news/' === pg ? Number( wp( sw + 'echo count( topersia_g_news_topics() );' ) )
						: '/articles/' === pg ? Number( wp( sw + '$q = new WP_Query( array( "post_type" => "tpc_article", "post_status" => "publish", "posts_per_page" => 1, "fields" => "ids", "suppress_filters" => false ) ); echo (int) $q->found_posts;' ) ) : -1;
					t( `تراشه ${ pg } · ${ lang }: ردیف تراشه`, 0 === nt, 0 === nt ? ( '/articles/' === pg ? 'آزموده نشد — این زبان هیچ مقالۀ منتشرشده ندارد (داده)' : 'آزموده نشد — هیچ خبرِ این زبان موضوع ندارد (داده)' ) : 'نیست' );
					continue;
				}
				t( `تراشه ${ pg } · ${ lang } · ۳۹۰: نوار پیشرفت زیر هر ردیف`, C.every( r => r.prog ), C.map( r => 'پیشرفت ' + r.prog ).join( '، ' ) );
				/* فلش‌ها زیر ۷۶۸ عمداً پنهان‌اند (topersia-g-news.css؛ موبایل = کشیدن + نوار پیشرفت) ← در ۱۰۲۴. «بعدی» = سوی پایان منطقی (rtl ← چپ) */
				await go( BASE + pre + pg, 1024 );
				const R = await p.evaluate( async () => {
					const row = [ ...document.querySelectorAll( '.tp-news-chips' ) ].find( r => r.getBoundingClientRect().width > 0 );
					const list = row.querySelector( '.tp-news-chips__list' );
					const rtl = 'rtl' === getComputedStyle( list ).direction;
					if ( list.scrollWidth <= list.clientWidth + 2 ) { return { skip: true }; }
					list.scrollTo( { left: 0, behavior: 'instant' } );
					await new Promise( r => setTimeout( r, 300 ) );
					const navs = [ ...row.querySelectorAll( '.tp-news-chips__nav' ) ].filter( n => ! n.hidden && n.getBoundingClientRect().width > 0 );
					if ( ! navs.length ) { return { nonav: true }; }
					const sorted = navs.slice().sort( ( a, b ) => a.getBoundingClientRect().left - b.getBoundingClientRect().left );
					const next = rtl ? sorted[0] : sorted[ sorted.length - 1 ];
					const before = list.scrollLeft;
					next.click();
					await new Promise( r => setTimeout( r, 900 ) );
					const after = list.scrollLeft;
					return { rtl, before, after, moved: rtl ? after < before - 4 : after > before + 4, label: next.getAttribute( 'aria-label' ) || next.textContent.trim() };
				} );
				t( `تراشه ${ pg } · ${ lang } · ۱۰۲۴: فلش «بعدی» (${ R.skip || R.nonav ? '—' : R.rtl ? 'چپ' : 'راست' }) به سوی پایان منطقی`, !! R.skip || ( ! R.nonav && R.moved ),
					R.skip ? 'ردیف سرریز ندارد — آزموده نشد' : R.nonav ? 'دکمۀ دیدنی در آغاز ردیف نیست' : `scrollLeft ${ Math.round( R.before ) } ← ${ Math.round( R.after ) }، برچسب «${ R.label }»` );
			}
		}

		/* ── عنوان سه‌زبانه (بند ۸) ── */
		for ( const [ lang, pre ] of [ [ 'fa', '' ], [ 'en', '/en' ], [ 'ar', '/ar' ] ] ) {
			const st = await go( BASE + pre + '/city/', 1440 );
			const h1 = await p.evaluate( () => ( document.querySelector( 'h1' ) || { textContent: '' } ).textContent.trim() );
			const okL = 'en' === lang ? ! /[؀-ۿ]/.test( h1 ) : 'ar' === lang ? ! /[پچژگ]/.test( h1 ) : true;
			t( `عنوان /city/ · ${ lang }: بی «همهٔ مکان‌ها و مطالب» و به زبان صفحه`, 200 === st && h1.length > 3 && ! /همه[ۀٔ]? مکان‌ها و مطالب/.test( h1 ) && okL, `«${ h1 }»` );
		}

		/* ── «مشاهده همه» صفحۀ اول (بند ۹) ── */
		await go( BASE + '/', 1440 );
		const more = await p.evaluate( () => [ ...document.querySelectorAll( 'a.tp-more' ) ].map( a => {
			const sec = a.closest( 'section[id], div[id]' );
			return { href: a.href, sec: sec ? sec.id : '' };
		} ) );
		const expect = { province: '/province/', cities: '/city/', attraction: '/attractions/', events: '/events/', trips: '/route/', route: '/route/', news: '/news/', articles: '/articles/' };
		t( '«مشاهده همه» صفحۀ اول: هر اسلایدر یکی', more.length >= 7, more.length + ' پیوند' );
		for ( const m of more ) {
			const st  = await status( m.href );
			const key = Object.keys( expect ).find( k => String( m.sec ).includes( k ) );
			t( `«مشاهده همه» ${ m.sec || '?' } ← ${ m.href.replace( BASE, '' ) }`, 200 === st && ( ! key || m.href.endsWith( expect[ key ] ) ), `وضعیت ${ st }` + ( key ? `، انتظار ${ expect[ key ] }` : '، بخش ناشناس' ) );
		}

		/* ── صفحۀ تکی خبر = اسکلت صفحۀ استان (بند ۱۰ تا ۱۳) ── */
		const skel = () => ( {
			snav: !! document.querySelector( '.tp-snav' ), share: !! document.querySelector( '.tp-share' ), act: document.querySelectorAll( '.tp-act' ).length,
			src: !! document.querySelector( '.tp-sources-rail' ), rev: !! document.querySelector( '#tp-reviews' ), invite: !! document.querySelector( '.tp-review-invite' ),
			nav: [ ...document.querySelectorAll( '.tp-snav a[href^="#"]' ) ].map( a => a.getAttribute( 'href' ) ),
			stars: !! document.querySelector( '#tp-reviews [class*="star"], #tp-reviews input[type="radio"]' ),
			ld: [ ...document.querySelectorAll( 'script[type="application/ld+json"]' ) ].some( s => /AggregateRating|"Review"/.test( s.textContent ) ),
		} );
		await go( BASE + '/province/isfahan/', 1440 );
		const P = await p.evaluate( skel );
		const NEWS = '/news/isfahan-children-film-festival-38/';
		await go( BASE + NEWS, 1440 );
		const N = await p.evaluate( skel );
		t( 'خبر تکی: اجزای اسکلت صفحۀ استان (ناوبری بخش‌ها، اشتراک، ذخیره/دنبال‌کردن، منابع، نظرات)',
			N.snav === P.snav && N.share === P.share && N.act === P.act && N.src === P.src && N.rev === P.rev && N.invite === P.invite,
			`استان ${ JSON.stringify( { s: P.snav, sh: P.share, a: P.act, src: P.src, r: P.rev } ) } · خبر ${ JSON.stringify( { s: N.snav, sh: N.share, a: N.act, src: N.src, r: N.rev } ) }` );
		const order = [ '#tp-text', '#tp-rel-places', '#tp-sources', '#tp-reviews', '#tp-rel-news' ];
		const navK  = N.nav.filter( h => order.includes( h ) );
		t( 'خبر تکی: ترتیب ناوبری «متن · مکان‌های مرتبط · منبع · نظرات · خبرهای مرتبط» (بند ۱۱)', navK.join() === order.filter( h => navK.includes( h ) ).join() && navK.includes( '#tp-text' ) && navK.includes( '#tp-reviews' ), N.nav.join( ' ' ) );
		t( 'خبر تکی: نظر بی ستاره و بی AggregateRating/Review (بند ۱۲)', N.rev && ! N.stars && ! N.ld, `ستاره ${ N.stars }، اسکیمای نظر ${ N.ld }` );
		await shot( 'news-single-1440.png' );
		await go( BASE + NEWS, 390 );
		const N3 = await p.evaluate( () => { const s = document.querySelector( '.tp-share' ); const r = s ? s.getBoundingClientRect() : null; return { share: !! r && r.width > 0 && r.right <= document.documentElement.clientWidth + 1 && r.left >= -1, scroll: document.documentElement.scrollWidth - document.documentElement.clientWidth }; } );
		t( 'خبر تکی · ۳۹۰: جعبۀ اشتراک در پهنای صفحه، بی سرریز (بند ۱۳)', N3.share && N3.scroll <= 1, `سرریز ${ N3.scroll }px` );
		await shot( 'news-single-390.png' );

		/* ── عکس پاورقی با ۴ و ۱ تبلیغ (بند ۲۲) — رفتار در footer-gate.js ── */
		for ( const n of [ 4, 1 ] ) {
			setDemo( n );
			for ( const w of [ 1440, 390 ] ) {
				await go( BASE + '/', w );
				await p.evaluate( () => window.scrollTo( { top: document.documentElement.scrollHeight, behavior: 'instant' } ) );
				await shot( `footer-${ w }-ads${ n }.png`, '[data-elementor-type="footer"]' );
			}
		}
	} catch ( e ) {
		fail++;
		console.log( '  ❌ خطا: ' + ( e && e.message ? e.message : e ) );
	} finally {
		try { setDemo( prevDemo ); } catch ( e ) { fail++; console.log( '  ❌ برگرداندن demo_ads نشد: ' + e.message ); }
		await br.close();
	}
	console.log( '─'.repeat( 78 ) + `\nEND w53-local — گذشته: ${ pass }   افتاده: ${ fail }   عکس‌ها: ${ SHOT }` );
	process.exit( fail ? 1 : 0 );
} )();
