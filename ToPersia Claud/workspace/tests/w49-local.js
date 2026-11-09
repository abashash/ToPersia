/**
 * دستۀ 49 بخش ۱ — صفحۀ اول و جست‌وجوی سراسری روی نسخۀ محلی (فقط topersia.local؛ فقط خواندن).
 *
 *   node tests/w49-local.js
 *
 * ۱۴۴۰ · ۱۰۲۴ · ۷۶۸ · ۳۹۰ × fa · en:
 *   P5-D533  استان‌ها ≥ ۱۰۲۴ دو ردیف و هم‌قد کارت نقشه؛ زیر آن یک ردیف؛ بقیۀ اسلایدرها یک ردیف.
 *            دکمۀ هر بخش در ردیف عنوان، روبروی آن، یک خط، ≥ ۴۴px — با عنوان بلند تزریقی هم.
 *   بند ۴    فاصلۀ لبۀ پایین نوار شمار (یا هیرو بی نوار) تا عنوان استان‌ها ≤ ۴۸ دسکتاپ، ≤ ۳۲ موبایل؛ CLS صفر.
 *   بند ۵    ≥ ۱۴px میان کارت و نوار اسکرول (≥ ۷۶۹)؛ موبایل نوار پنهان؛ فضای سایه بالای کارت.
 *   بند ۲    ایران در تصویر: اسلایدر یک‌ردیفه، ۴ تا ۱۲ عکس یکتا با alt و پیوند، یا هیچ.
 *   P5-D537  متن کپی‌رایت پاورقی: ≥ ۱۲px، بی سرریز؛ ۱۴۴۰ یک خط؛ ۷۶۸ ≤ ۲ خط؛ ۳۹۰ گزارش (با متن کنونی ۲ خط ناممکن).
 *   P5-D536  فرم جست‌وجو زیر زیرعنوان هیرو؛ ارسال با «ك» نتیجه می‌دهد؛ noindex؛ هرگز نمونه.
 */
'use strict';

const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE = ENV.base;
const X    = Number( process.env.TP_TIMEOUT_X || 1 );
const PFX  = { fa: '', en: '/en' };
let pass = 0, fail = 0, warn = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( ok || ! note ? '' : '   ← ' + note ) ); };
const w8 = ( label ) => { warn++; console.log( '  ⚠ ' + label ); };

/* اندازه‌گیری درون صفحه */
const probe = () => {
	const q = s => document.querySelector( s ), R = e => e ? e.getBoundingClientRect() : null;
	const hero = R( q( '.tp-home-hero' ) ), st = R( q( '.tp-home-stats' ) ), title = R( q( '#tp-home-provincesTitle' ) );
	const out = { gap: title ? Math.round( title.top - ( st ? st.bottom : hero.bottom ) ) : null, hasStats: !! st };
	const ps = q( '#tp-home-provslide' ), map = q( '.tp-home-prov__map' ), tiles = q( '.tp-home-prov__tiles' );
	out.prov = ps ? { n: ps.children.length, tops: [ ...new Set( [ ...ps.children ].map( c => Math.round( R( c ).top ) ) ) ].length,
		mapH: map ? Math.round( R( map ).height ) : 0, tilesH: tiles ? Math.round( R( tiles ).height ) : 0, side: map && tiles ? Math.abs( R( map ).top - R( tiles ).top ) < 4 : false } : null;
	out.sliders = [ ...document.querySelectorAll( '.tp-home .tp-home-slider' ) ].map( s => {
		const cs = getComputedStyle( s ), kid = s.firstElementChild;
		return { id: s.id, n: s.children.length, tops: [ ...new Set( [ ...s.children ].map( c => Math.round( R( c ).top ) ) ) ].length,
			pb: parseFloat( cs.paddingBottom ), pt: parseFloat( cs.paddingTop ), sbw: cs.scrollbarWidth,
			gapBar: kid ? Math.round( R( s ).bottom - ( s.offsetHeight - s.clientHeight ) - Math.max( ...[ ...s.children ].map( c => R( c ).bottom ) ) ) : 0 };
	} );
	out.heads = [ ...document.querySelectorAll( '.tp-home-sec > .tp-section-head' ) ].map( h => {
		const h2 = h.querySelector( 'h2' ), b = h.querySelector( ':scope > .tp-more, :scope > .tp-home-slider__all' );
		if ( ! b || b.hidden ) { return { id: h.parentElement.id, btn: false }; }
		const rh = R( h2 ), rb = R( b ), lh = parseFloat( getComputedStyle( b ).lineHeight ) || 24;
		const rtl = 'rtl' === getComputedStyle( h ).direction;
		return { id: h.parentElement.id, btn: true, sameRow: rb.top < rh.bottom && rb.bottom > rh.top,
			opposite: rtl ? rb.right <= rh.left + 1 : rb.left >= rh.right - 1, h: Math.round( rb.height ), oneLine: b.scrollHeight < 2 * lh + 4 && rb.height < 2 * lh + 20,
			inside: rb.left >= R( h ).left - 1 && rb.right <= R( h ).right + 1 };
	} );
	const ph = [ ...document.querySelectorAll( '#tp-home-photoslide .tp-home-photo' ) ];
	out.photos = { n: ph.length, uniq: new Set( ph.map( a => a.getAttribute( 'data-tp-full' ) ) ).size, alt: ph.every( a => { const i = a.querySelector( 'img' ); return i && '' !== i.alt.trim(); } ),
		href: ph.every( a => /^https?:/.test( a.href ) ), sec: !! q( '#tp-home-gallery' ), legacy: !! q( '.tp-home-photos' ) };
	const lead = R( q( '.tp-home-hero__lead' ) ), form = q( '.tp-home-hero form[role="search"]' );
	out.search = form ? { below: ! lead || R( form ).top >= lead.bottom - 1, name: !! form.querySelector( 'input[name="s"]' ), btnH: Math.round( R( form.querySelector( 'button' ) ).height ), label: !! form.querySelector( 'label[for]' ) } : null;
	const fp = q( '.wd-copyrights .wd-col-start p' );
	if ( fp ) {
		const cs = getComputedStyle( fp ), fs = parseFloat( cs.fontSize ), lh = parseFloat( cs.lineHeight ) || fs * 1.7;
		out.foot = { fs, lines: Math.round( fp.getBoundingClientRect().height / lh ), over: fp.scrollWidth > fp.clientWidth + 1 };
	}
	out.sw = document.documentElement.scrollWidth; out.cw = document.documentElement.clientWidth;
	return out;
};

( async () => {
	const b = await chromium.launch();
	try {
		for ( const [ w, h ] of [ [ 1440, 900 ], [ 1024, 800 ], [ 768, 1000 ], [ 390, 844 ] ] ) {
			const mob = w < 768;
			const ctx = await b.newContext( { viewport: { width: w, height: h }, isMobile: mob, hasTouch: mob } );
			const p = await ctx.newPage();
			const errs = [];
			p.on( 'pageerror', e => errs.push( e.message ) );
			await p.addInitScript( () => {
				window.__tpCls = 0;
				try { new PerformanceObserver( l => { for ( const e of l.getEntries() ) { if ( ! e.hadRecentInput ) { window.__tpCls += e.value; } } } ).observe( { type: 'layout-shift', buffered: true } ); } catch ( e ) {}
			} );
			for ( const l of [ 'fa', 'en' ] ) {
				console.log( `\n${w} · ${l}` );
				await p.goto( BASE + PFX[ l ] + '/', { waitUntil: 'load', timeout: 120000 * X } );
				await p.waitForTimeout( 600 );
				/* همۀ بخش‌های تنبل یک بار دیده شوند (content-visibility) و CLS در پیمایش هم سنجیده شود */
				await p.evaluate( async () => { for ( const s of document.querySelectorAll( '.tp-home-sec, .tp-home-trio' ) ) { s.scrollIntoView( { behavior: 'instant' } ); await new Promise( r => setTimeout( r, 120 ) ); } window.scrollTo( 0, 0 ); await new Promise( r => setTimeout( r, 300 ) ); } );
				const r = await p.evaluate( probe );
				const cap = w >= 768 ? 48 : 32;
				t( `بی سرریز افقی صفحه (${r.sw} ≤ ${r.cw})`, r.sw <= r.cw );
				t( `بند ۴: فاصلۀ ${r.hasStats ? 'نوار شمار' : 'هیرو'} تا عنوان استان‌ها ${r.gap}px ≤ ${cap}`, null !== r.gap && r.gap >= 0 && r.gap <= cap, String( r.gap ) );
				/* بی نوار شمار: همان صفحه، نوار برداشته */
				const g2 = await p.evaluate( () => { const s = document.querySelector( '.tp-home-stats' ); if ( ! s ) { return null; } const par = s.parentNode, nx = s.nextSibling; s.remove();
					const v = Math.round( document.querySelector( '#tp-home-provincesTitle' ).getBoundingClientRect().top - document.querySelector( '.tp-home-hero' ).getBoundingClientRect().bottom ); par.insertBefore( s, nx ); return v; } );
				if ( null !== g2 ) { t( `بند ۴: بی نوار شمار، فاصلۀ هیرو تا عنوان ${g2}px ≤ ${cap}`, g2 >= 0 && g2 <= cap, String( g2 ) ); }
				const cls = await p.evaluate( () => window.__tpCls );
				t( `CLS در بار و پیمایش ${cls.toFixed( 4 )} (صفر)`, cls < 0.005, String( cls ) );

				if ( r.prov ) {
					const two = w >= 1024 && r.prov.n >= 2;
					t( `P5-D533: استان‌ها ${two ? 'دو' : 'یک'} ردیف (${r.prov.tops} ردیف، ${r.prov.n} کارت)`, two ? 2 === r.prov.tops : 1 === r.prov.tops );
					if ( two && r.prov.side && r.prov.mapH ) { t( `P5-D533: ستون استان‌ها هم‌قد کارت نقشه (${r.prov.tilesH} ≈ ${r.prov.mapH})`, Math.abs( r.prov.tilesH - r.prov.mapH ) <= 2 ); }
				}
				for ( const s of r.sliders.filter( x => 'tp-home-provslide' !== x.id ) ) { t( `P5-D533: ${s.id} یک ردیف (${s.n} کارت)`, 1 === s.tops ); }
				for ( const s of r.sliders ) {
					if ( w > 768 ) { t( `بند ۵: ${s.id} — فاصلۀ کارت تا نوار ${s.gapBar}px ≥ ۱۴، فضای سایه بالا ${s.pt}px`, s.gapBar >= 14 && s.pt >= 4, JSON.stringify( s ) ); }
					else { t( `بند ۵: ${s.id} — موبایل نوار اسکرول پنهان (${s.sbw})، فضای سایه ${s.pt}px`, 'none' === s.sbw && s.pt >= 4 ); }
				}
				const hb = r.heads.filter( x => x.btn );
				t( `P5-D533: ${hb.length} دکمۀ بخش (${hb.map( x => x.id ).join( '، ' )})`, hb.length >= 1 && ( 'fa' !== l || hb.some( x => 'tp-home-provinces' === x.id ) ), JSON.stringify( r.heads ) );
				for ( const x of hb ) { t( `P5-D533: ${x.id} — دکمه در ردیف عنوان، روبرو، یک خط، ${x.h}px ≥ ۴۴`, x.sameRow && x.opposite && x.oneLine && x.h >= 44 && x.inside, JSON.stringify( x ) ); }
				/* عنوان بلند: عنوان می‌شکند، دکمه نه */
				const long = await p.evaluate( () => { const h2 = document.querySelector( '#tp-home-provincesTitle' ); if ( ! h2 ) { return null; } const o = h2.textContent; h2.textContent = ( o + ' ' ).repeat( 16 ); const h = h2.closest( '.tp-section-head' ), b = h.querySelector( ':scope > .tp-more, :scope > .tp-home-slider__all' );
					const rb = b && ! b.hidden ? b.getBoundingClientRect() : null, rh = h2.getBoundingClientRect(), lh = parseFloat( getComputedStyle( h2 ).lineHeight ) || 30;
					const v = { wrap: rh.height > 1.5 * lh, btnOne: rb ? rb.height < 2 * ( parseFloat( getComputedStyle( b ).lineHeight ) || 24 ) + 20 : null, row: rb ? rb.top < rh.bottom && rb.bottom > rh.top : null, over: document.documentElement.scrollWidth > document.documentElement.clientWidth };
					h2.textContent = o; return v; } );
				if ( long && null !== long.btnOne ) { t( 'P5-D533: عنوان بلند می‌شکند، دکمه یک خط و در همان ردیف، بی سرریز', long.wrap && long.btnOne && long.row && ! long.over, JSON.stringify( long ) ); }

				t( `بند ۲: ایران در تصویر — ${r.photos.n} عکس یکتا (۰ یا ۴ تا ۱۲)، alt و پیوند، بی گرید قدیم`, ( 0 === r.photos.n ? ! r.photos.sec : r.photos.n >= 4 && r.photos.n <= 12 && r.photos.uniq === r.photos.n && r.photos.alt && r.photos.href ) && ! r.photos.legacy, JSON.stringify( r.photos ) );
				t( 'P5-D536: فرم جست‌وجو زیر زیرعنوان هیرو، name=s، برچسب، دکمه ≥ ۴۴', !! r.search && r.search.below && r.search.name && r.search.label && r.search.btnH >= 44, JSON.stringify( r.search ) );

				if ( r.foot ) {
					t( `P5-D537: پاورقی ${r.foot.fs}px ≥ ۱۲، بی سرریز`, r.foot.fs >= 12 && ! r.foot.over, JSON.stringify( r.foot ) );
					if ( w >= 1440 ) { t( `P5-D537: ۱۴۴۰ متن کپی‌رایت یک خط (${r.foot.lines})`, 1 === r.foot.lines ); }
					else if ( w >= 768 ) { t( `P5-D537: ${w} متن کپی‌رایت ≤ ۲ خط (${r.foot.lines}) — یک خط با ≥ ۱۲px ناممکن`, r.foot.lines <= 2 ); w8( `${w}: یک خط فقط با فونت زیر ۸px جا می‌شد؛ ${r.foot.lines} خط در ${r.foot.fs}px` ); }
					else { w8( `${w}: ${r.foot.lines} خط در ${r.foot.fs}px — دو خط با ≥ ۱۲px برای متن کنونی ناممکن (کوتاه‌کردن متن تنظیم وودمارت تصمیم کاربر است)` ); }
				}
			}
			t( `${w}: بی خطای JS`, ! errs.length, errs.join( ' | ' ) );
			await ctx.close();
		}

		/* جست‌وجو — ارسال فرم هیرو، ك/ي، نمونه هرگز، noindex */
		console.log( '\nجست‌وجو' );
		const ctx = await b.newContext( { viewport: { width: 1440, height: 900 } } );
		const p = await ctx.newPage();
		await p.goto( BASE + '/', { waitUntil: 'load', timeout: 120000 * X } );
		await p.fill( '#tp-home-q', 'كاشان' );
		await Promise.all( [ p.waitForNavigation( { waitUntil: 'load', timeout: 120000 * X } ), p.click( '.tp-home-hero .tp-search__go' ) ] );
		const s1 = await p.evaluate( () => ( { url: location.href, tiles: document.querySelectorAll( '.tp-search-page .tp-tile' ).length, robots: ( document.querySelector( 'meta[name="robots"]' ) || {} ).content || '', h1: ( document.querySelector( 'h1' ) || {} ).textContent || '' } ) );
		t( `فرم هیرو ← ?s= و نتیجه با «ك» (${s1.tiles} کاشی)`, /[?&]s=/.test( s1.url ) && s1.tiles >= 1, JSON.stringify( s1 ) );
		t( 'نتیجه noindex', /noindex/.test( s1.robots ), s1.robots );
		await p.goto( BASE + '/?s=' + encodeURIComponent( 'هرمز' ), { waitUntil: 'load', timeout: 120000 * X } );
		const s2 = await p.evaluate( () => [ ...document.querySelectorAll( '.tp-search-page .tp-tile' ) ].map( a => a.getAttribute( 'href' ) || '' ) );
		t( `نمونه هرگز: «هرمز» بی جزیرۀ نمونه (${s2.length} کاشی)`, s2.every( u => ! /\/island\//.test( decodeURIComponent( u ) ) ), s2.join( ' ' ) );
		await p.goto( BASE + '/?s=' + encodeURIComponent( 'zzقqqنیست' ), { waitUntil: 'load', timeout: 120000 * X } );
		t( 'بی نتیجه ← «نتیجه‌ای نیست.»', await p.evaluate( () => /نتیجه‌ای نیست/.test( document.body.textContent ) && ! document.querySelector( '.tp-search-page .tp-tile' ) ) );
		await p.goto( BASE + '/en/?s=' + encodeURIComponent( 'isfahan' ), { waitUntil: 'load', timeout: 120000 * X } );
		const s3 = await p.evaluate( () => ( { n: document.querySelectorAll( '.tp-search-page .tp-tile' ).length, lang: document.documentElement.lang, fa: /[؀-ۿ]/.test( ( document.querySelector( '.tp-search-page .tp-tiles' ) || {} ).textContent || '' ) } ) );
		t( `en: نتیجۀ همان زبان (${s3.n} کاشی، بی متن فارسی در کاشی‌ها)`, s3.n >= 1 && ! s3.fa, JSON.stringify( s3 ) );
		await ctx.close();
	} finally {
		await b.close();
	}
	console.log( `\nw49-local: ${pass} سبز · ${fail} قرمز · ${warn} هشدار` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
