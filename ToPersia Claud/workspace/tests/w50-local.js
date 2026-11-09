/**
 * دستۀ 50 — صفحه‌های خبر با یک سبک (P5-D541 · P5-D542 · P5-D544 · P5-D546) روی نسخۀ محلی (فقط topersia.local).
 *
 *   node tests/w50-local.js
 *
 * /news/ و /province/isfahan/news/ در fa · en · ar، و یک تک‌خبر fa:
 *   ۱۴۴۰ و ۳۹۰ × روز · عصر · شب، و ۳۲۰ روز — بی سرریز افقی؛ فاصلۀ صفر هدر تا سربرگ؛
 *   تراشه‌ها یک خط؛ هر فیلد پالایش برچسب دارد و ≥ ۴۴px؛ فهرست کشویی بی ظاهر مرورگر.
 * بی‌رفرش (fa · ۱۴۴۰): نشان window پس از کلیک تراشه و تغییر فهرست می‌ماند؛ نشانی عوض
 *   می‌شود؛ «بازگشت» نشانی و فرم را برمی‌گرداند؛ aria-live شمار را می‌گوید؛ تمرکز روی تراشه؛
 *   دو کلیک پشت‌سرهم ← آخری برنده؛ خطای شبکه ← بارگذاری عادی؛ بی JS پیوند و فرم GET کار می‌کنند؛
 *   پاسخ قطعه noindex و بی هدر سایت؛ حالت پالایش‌شدۀ رندر سرور noindex با canonical بی‌پالایش.
 * عکس «پس»: tests/.screens/50/ (بیرون از git).
 */
'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE = ENV.base;
const X    = Number( process.env.TP_TIMEOUT_X || 1 );
const PFX  = { fa: '', en: '/en', ar: '/ar' };
const DIR  = { fa: 'rtl', en: 'ltr', ar: 'rtl' };
const SHOT = path.join( __dirname, '.screens', '50' );
let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( ok || ! note ? '' : '   ← ' + note ) ); };
const go = ( p, u ) => p.goto( u, { waitUntil: 'load', timeout: 120000 * X } );
const settle = ms => new Promise( r => setTimeout( r, ms ) );
const until = async ( p, fn, arg, ms = 30000 ) => { try { await p.waitForFunction( fn, arg, { timeout: ms * X } ); return true; } catch ( e ) { return false; } };

const probe = () => {
	const root = document.querySelector( '.tp-page.tp-news-page' );
	if ( ! root ) { return null; }
	const W = document.documentElement.clientWidth;
	const hero = root.querySelector( '.tp-news-hero' );
	const heads = [ ...document.querySelectorAll( 'header' ) ].filter( h => ! h.closest( '.tp-page' ) && h.getBoundingClientRect().height > 0 );
	const hb = heads.length ? Math.max( ...heads.map( h => h.getBoundingClientRect().bottom + window.scrollY ) ) : 0;
	const lis = [ ...root.querySelectorAll( '.tp-news-bar .tp-news-chips__list > li' ) ].map( l => Math.round( l.getBoundingClientRect().top ) );
	const list = root.querySelector( '.tp-news-bar .tp-news-chips__list' );
	const over = [ ...root.querySelectorAll( '*' ) ].filter( e => {
		if ( e.closest( '.tp-news-chips__list' ) && ! e.matches( '.tp-news-chips__list' ) ) { return false; }   /* درون نوار اسکرولی عمداً بیرون می‌زند */
		const r = e.getBoundingClientRect(); return r.width && ( r.right > W + 1 || r.left < -1 );
	} ).slice( 0, 3 ).map( e => e.className || e.tagName );
	const fields = [ ...root.querySelectorAll( '.tp-news-filter__f :is(select,input:not([type=hidden]))' ) ];
	return {
		sw: document.documentElement.scrollWidth, cw: W, over,
		dir: root.getAttribute( 'dir' ), lang: document.documentElement.lang, mode: document.documentElement.getAttribute( 'data-tp-mode' ),
		gap: hero ? Math.round( hero.getBoundingClientRect().top + window.scrollY - hb ) : null,
		oneLine: new Set( lis ).size <= 1, chips: lis.length,
		listFits: list ? list.getBoundingClientRect().right <= W + 1 && list.getBoundingClientRect().left >= -1 : true,
		labels: fields.every( f => f.id && document.querySelector( 'label[for="' + f.id + '"]' ) ),
		h44: fields.every( f => f.getBoundingClientRect().height >= 44 ),
		fields: fields.length,
		app: [ ...root.querySelectorAll( '.tp-news-sel select' ) ].every( s => 'none' === getComputedStyle( s ).appearance ),
		catsNum: [ ...root.querySelectorAll( '.tp-news-cats a, .tp-news-chips a' ) ].some( a => /[0-9۰-۹٠-٩]/.test( a.textContent ) ),
		cards: root.querySelectorAll( '.tp-news-card' ).length, feat: root.querySelectorAll( '.tp-news-feat' ).length,
		imgs: [ ...root.querySelectorAll( 'img' ) ].map( i => i.currentSrc || i.src ),
	};
};

( async () => {
	fs.mkdirSync( SHOT, { recursive: true } );
	const b = await chromium.launch();
	try {
		const single = await ( async () => {
			const c = await b.newContext(); const p = await c.newPage();
			await go( p, BASE + '/news/' );
			const u = await p.evaluate( () => ( document.querySelector( '.tp-news-feat__title a, .tp-news-card__title a' ) || {} ).href || '' );
			await c.close(); return u;
		} )();
		const PAGES = [ [ 'آرشیو', '/news/', true ], [ 'خبرهای استان', '/province/isfahan/news/', true ], [ 'تک‌خبر', single.replace( BASE, '' ), false ] ];
		const RUNS = [];
		for ( const w of [ 1440, 390 ] ) { for ( const m of [ 'light', 'evening', 'dark' ] ) { RUNS.push( [ w, m ] ); } }
		RUNS.push( [ 320, 'light' ] );

		for ( const [ w, mode ] of RUNS ) {
			const mob = w < 768;
			const ctx = await b.newContext( { viewport: { width: w, height: mob ? 844 : 900 }, isMobile: mob, hasTouch: mob } );
			const p = await ctx.newPage();
			const errs = [];
			p.on( 'pageerror', e => errs.push( e.message ) );
			await p.addInitScript( m => { try { localStorage.setItem( 'tp-mode', m ); } catch ( e ) {} }, mode );
			for ( const [ name, rel, multi ] of PAGES ) {
				for ( const l of multi ? [ 'fa', 'en', 'ar' ] : [ 'fa' ] ) {
					const url = BASE + PFX[ l ] + rel;
					const res = await go( p, url );
					await settle( 400 );
					const r = await p.evaluate( probe );
					console.log( `\n${w} · ${mode} · ${l} · ${name}` );
					if ( ! r ) { t( `${rel} با قالب ما (${res.status()})`, false ); continue; }
					t( `۲۰۰، حالت ${r.mode}، بی سرریز افقی (${r.sw} ≤ ${r.cw})`, 200 === res.status() && r.mode === mode && r.sw <= r.cw && ! r.over.length, r.over.join( ' | ' ) );
					t( `فاصلۀ هدر تا سربرگ صفر (${r.gap}px)`, null !== r.gap && Math.abs( r.gap ) <= 1 );
					t( `dir ${r.dir} و lang ${r.lang}`, r.dir === DIR[ l ] && 0 === r.lang.indexOf( l ) );
					if ( multi ) {
						t( `تراشه‌ها یک خط (${r.chips}) و نوار درون صفحه؛ دسته و تراشه بی شمار`, r.oneLine && r.listFits && ! r.catsNum );
						t( `${r.fields} فیلد: برچسب‌دار، ≥ ۴۴px، فهرست کشویی بی ظاهر مرورگر`, r.fields > 0 && r.labels && r.h44 && r.app );
					}
					t( 'نمونه هرگز: بی تصویر نمونه یا بیرونی', r.imgs.every( u => 0 === u.indexOf( BASE ) && ! /sample/i.test( u ) ), r.imgs.filter( u => /sample/i.test( u ) || 0 !== u.indexOf( BASE ) ).join( ' ' ) );
					if ( 'fa' === l && 'light' === mode && 1440 !== w ) {
						await p.screenshot( { path: path.join( SHOT, `${name === 'آرشیو' ? 'news' : name === 'تک‌خبر' ? 'single' : 'place'}-fa-${w}.png` ), fullPage: true } );
					}
					if ( 'fa' === l && 'light' === mode && 1440 === w ) {
						await p.screenshot( { path: path.join( SHOT, `${name === 'آرشیو' ? 'news' : name === 'تک‌خبر' ? 'single' : 'place'}-fa-1440.png` ), fullPage: true } );
					}
				}
			}
			t( `بی خطای جاوااسکریپت (${w} · ${mode})`, ! errs.length, errs.join( ' | ' ) );
			await ctx.close();
		}

		/* ── تک‌خبر: محتوا ── */
		{
			console.log( '\nتک‌خبر — محتوا' );
			const ctx = await b.newContext( { viewport: { width: 1440, height: 900 } } ); const p = await ctx.newPage();
			await go( p, single );
			const s = await p.evaluate( () => {
				const q = s => document.querySelector( s );
				const src = q( '.tp-news-hero .tp-news-source a' );
				return {
					h1: ( q( '.tp-news-hero h1' ) || {} ).textContent || '', when: !! q( '.tp-news-hero time' ), read: !! q( '.tp-news-hero .tp-news-read' ),
					src: src ? [ src.href, src.rel, src.target ] : null, srcN: document.querySelectorAll( '.tp-news-source' ).length,
					places: [ ...document.querySelectorAll( '.tp-news-places a' ) ].map( a => a.href ), rel: document.querySelectorAll( '.tp-news-related .tp-news-card' ).length,
					body: ( q( '.tp-news-body' ) || {} ).textContent || '', wd: !! q( '.wd-single-post-header' ),
				};
			} );
			t( `سربرگ: H1، تاریخ، زمان مطالعه؛ منبع یک بار با پیوند بیرونی nofollow در برگۀ تازه`, '' !== s.h1.trim() && s.when && s.read && s.src && ! s.src[ 0 ].startsWith( BASE ) && /nofollow/.test( s.src[ 1 ] ) && '_blank' === s.src[ 2 ] && 1 === s.srcN, JSON.stringify( s.src ) + ' n=' + s.srcN );
			t( `نشان مکان‌ها پیوند به صفحۀ مکان (${s.places.length})؛ متن؛ «خبرهای مرتبط» با همان کارت (${s.rel})؛ بی قالب وودمارت`, s.places.length > 0 && s.places.every( u => 0 === u.indexOf( BASE ) ) && s.body.trim().length > 40 && s.rel > 0 && ! s.wd );
			await ctx.close();
		}

		/* ── بی‌رفرش ── */
		for ( const [ name, rel, slug ] of [ [ 'آرشیو', '/news/', 'events' ], [ 'خبرهای استان', '/province/isfahan/news/', 'events' ] ] ) {
			console.log( `\nبی‌رفرش — ${name}` );
			const ctx = await b.newContext( { viewport: { width: 1440, height: 900 } } ); const p = await ctx.newPage();
			const errs = []; p.on( 'pageerror', e => errs.push( e.message ) );
			await go( p, BASE + rel );
			await p.evaluate( () => { window.__tp50 = 1; } );
			await p.evaluate( s => document.querySelector( '.tp-news-bar .tp-news-chips a[data-tp-topic="' + s + '"]' ).click(), slug );
			const ok1 = await until( p, s => location.search.indexOf( 'tp_topic=' + s ) > -1 && document.querySelector( '[data-tp-news-swap="results"]' ).getAttribute( 'aria-busy' ) === 'false', slug );
			const a = await p.evaluate( () => ( {
				mark: window.__tp50, live: document.querySelector( '[data-tp-news-live]' ).textContent,
				cur: ( document.querySelector( '.tp-news-bar .tp-news-chips a[aria-current]' ) || {} ).dataset, focus: document.activeElement.getAttribute( 'data-tp-topic' ),
				cards: document.querySelectorAll( '.tp-news-results .tp-news-card' ).length, hid: ( document.querySelector( 'form[data-tp-news-filter] input[name=tp_topic]' ) || {} ).value,
			} ) );
			t( `کلیک تراشه: نشانی ?tp_topic=${slug}، نشان window ماند (بی بارگذاری کامل)، ${a.cards} کارت`, ok1 && 1 === a.mark && a.cards > 0, JSON.stringify( a ) );
			t( `aria-live «${a.live}»، تراشۀ فعال و تمرکز روی همان تراشه، فیلد پنهان فرم همگام`, '' !== a.live.trim() && a.cur && a.cur.tpTopic === slug && a.focus === slug && a.hid === slug );
			await p.selectOption( '#tp-news-sort', 'old' );
			const ok2 = await until( p, () => location.search.indexOf( 'tp_sort=old' ) > -1 && document.querySelector( '[data-tp-news-swap="results"]' ).getAttribute( 'aria-busy' ) === 'false' );
			const m2 = await p.evaluate( () => [ window.__tp50, location.search ] );
			t( `تغییر فهرست ترتیب: نشانی ${m2[ 1 ]}، بی بارگذاری کامل`, ok2 && 1 === m2[ 0 ] && m2[ 1 ].indexOf( 'tp_topic=' ) > -1 );
			await p.goBack();
			const ok3 = await until( p, s => location.search === '?tp_topic=' + s && document.querySelector( '#tp-news-sort' ).value === '' , slug );
			await p.goBack();
			const ok4 = await until( p, () => '' === location.search && ! document.querySelector( '.tp-news-bar .tp-news-chips a[aria-current][data-tp-topic]:not([data-tp-topic=""])' ) );
			t( '«بازگشت» دو گام: نشانی و فرم و تراشۀ فعال برمی‌گردند، هنوز بی بارگذاری کامل', ok3 && ok4 && 1 === await p.evaluate( () => window.__tp50 ) );
			if ( '/news/' === rel ) {
				await p.selectOption( '#tp-news-when', '7d' );
				const ok5 = await until( p, () => location.search.indexOf( 'tp_when=7d' ) > -1 && document.querySelector( '[data-tp-news-swap="results"]' ).getAttribute( 'aria-busy' ) === 'false' );
				const e5 = await p.evaluate( () => [ !! document.querySelector( '.tp-news-empty' ), document.querySelector( '[data-tp-news-live]' ).textContent, !! document.querySelector( '.tp-news-filter__reset' ) ] );
				t( `بازۀ ۷ روز (خبرها کهنه‌ترند): پیام آرام، اعلام «${e5[ 1 ]}»، «پاک‌کردن پالایش» پیدا`, ok5 && e5[ 0 ] && e5[ 2 ] );
				await p.evaluate( () => document.querySelector( '.tp-news-filter__reset' ).click() );
				await until( p, () => '' === location.search && document.querySelector( '[data-tp-news-swap="results"]' ).getAttribute( 'aria-busy' ) === 'false' );
				/* دو درخواست پشت‌سرهم: آخری برنده */
				await p.evaluate( () => { const c = [ ...document.querySelectorAll( '.tp-news-bar .tp-news-chips a[data-tp-topic]' ) ].filter( x => x.dataset.tpTopic ); c[ 0 ].click(); c[ c.length - 1 ].click(); } );
				const last = await p.evaluate( () => { const c = [ ...document.querySelectorAll( '.tp-news-bar .tp-news-chips a[data-tp-topic]' ) ].filter( x => x.dataset.tpTopic ); return c[ c.length - 1 ].dataset.tpTopic; } );
				await until( p, s => location.search.indexOf( 'tp_topic=' + s ) > -1 && document.querySelector( '[data-tp-news-swap="results"]' ).getAttribute( 'aria-busy' ) === 'false', last );
				await settle( 1500 );
				const r6 = await p.evaluate( () => [ location.search, ( document.querySelector( '.tp-news-bar .tp-news-chips a[aria-current]' ) || {} ).dataset.tpTopic ] );
				t( `دو کلیک پشت‌سرهم: آخری برنده (${r6[ 0 ]})`, r6[ 0 ] === '?tp_topic=' + last && r6[ 1 ] === last );
			}
			t( 'بی خطای جاوااسکریپت', ! errs.length, errs.join( ' | ' ) );
			/* خطای شبکه ← بارگذاری عادی */
			await go( p, BASE + rel );
			await p.route( /tp_partial=1/, r => r.abort() );
			await p.evaluate( () => { window.__tp50 = 1; } );
			await Promise.all( [ p.waitForNavigation( { timeout: 60000 * X } ).catch( () => null ), p.evaluate( s => document.querySelector( '.tp-news-bar .tp-news-chips a[data-tp-topic="' + s + '"]' ).click(), slug ) ] );
			await p.waitForLoadState( 'load' );
			const f = await p.evaluate( () => [ window.__tp50, location.search ] );
			t( `خطای شبکه ← بارگذاری عادی همان نشانی (${f[ 1 ]})`, undefined === f[ 0 ] && f[ 1 ] === '?tp_topic=' + slug );
			await ctx.close();
		}

		/* ── بی جاوااسکریپت ── */
		{
			console.log( '\nبی جاوااسکریپت' );
			const ctx = await b.newContext( { viewport: { width: 1440, height: 900 }, javaScriptEnabled: false } ); const p = await ctx.newPage();
			await go( p, BASE + '/news/' );
			await Promise.all( [ p.waitForNavigation(), p.click( '.tp-news-bar .tp-news-chips a[data-tp-topic="events"]' ) ] );
			const c1 = await p.evaluate( () => [ location.search, document.querySelectorAll( '.tp-news-card' ).length ] );
			t( `پیوند تراشه: ${c1[ 0 ]} با ${c1[ 1 ]} کارت`, '?tp_topic=events' === c1[ 0 ] && c1[ 1 ] > 0 );
			await p.selectOption( '#tp-news-when', '12m' );
			await Promise.all( [ p.waitForNavigation(), p.click( '#tp-news-form button[type=submit]' ) ] );
			const c2 = await p.evaluate( () => [ location.search, document.querySelectorAll( '.tp-news-card' ).length ] );
			t( `فرم GET: ${decodeURIComponent( c2[ 0 ] )} (موضوع و بازه با هم)`, /tp_topic=events/.test( c2[ 0 ] ) && /tp_when=12m/.test( c2[ 0 ] ) && c2[ 1 ] > 0 );
			await ctx.close();
		}

		/* ── قطعه و نمایه ── */
		{
			console.log( '\nقطعه و نمایه' );
			const ctx = await b.newContext(); const p = await ctx.newPage();
			const pr = await ctx.request.get( BASE + '/news/?tp_topic=events&tp_partial=1' );
			const ph = await pr.text();
			t( 'پاسخ قطعه: ۲۰۰، X-Robots-Tag noindex، no-store، بی <head> و هدر سایت، با ناحیۀ نتیجه', 200 === pr.status() && /noindex/.test( pr.headers()[ 'x-robots-tag' ] || '' ) && /no-store/.test( pr.headers()[ 'cache-control' ] || '' ) && ! /<head[\s>]|wp-block-library|whb-header/i.test( ph ) && /data-tp-news-swap="results"/.test( ph ) );
			await go( p, BASE + '/news/?tp_topic=events' );
			const seo = await p.evaluate( () => [ ( document.querySelector( 'meta[name=robots]' ) || {} ).content || '', ( document.querySelector( 'link[rel=canonical]' ) || {} ).href || '' ] );
			/* Rank Math روی صفحۀ noindex canonical نمی‌نویسد (همان حکم w48-news-local: مقدار صافی از تابع سنجیده می‌شود)؛ اگر نوشته شد باید بی‌پالایش باشد */
			t( `پالایش‌شدۀ رندر سرور: robots «${seo[ 0 ]}»، canonical ${seo[ 1 ] || '(نوشته نشد — Rank Math روی noindex)'}`, /noindex/.test( seo[ 0 ] ) && ( '' === seo[ 1 ] || seo[ 1 ].replace( /\/$/, '' ) === ( BASE + '/news' ) ) );
			await ctx.close();
		}
	} finally {
		await b.close();
	}
	console.log( `\nعکس‌های «پس»: ${SHOT}` );
	console.log( `\nw50-local: ${pass} سبز · ${fail} قرمز` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
