/**
 * دستۀ 55 — آزمون مرورگری روی نسخۀ محلی (فقط topersia.local). Chromium و Firefox × ۱۴۴۰ · ۷۶۸ · ۳۹۰.
 *
 *   node tests/w55-local.js [--only=search,…]
 *
 * ▸ search (بند ۴) — جعبۀ جست‌وجوی نوار هاب (/attractions/ · /articles/ · /news/)، combobox مکان و جست‌وجوی هدر وودمارت:
 *   کلیک، تایپ، blur، Escape، لمس (۷۶۸ و ۳۹۰ با hasTouch). هر کنش سه نمونه (۵۰ · ۳۰۰ · ۹۰۰ms): فیلد در DOM و دیدنی.
 *   ظرف «فیلتر» موبایل (≤۷۲۰) فقط با کلیک بیرون یا Escape بسته می‌شود؛ فهرست مکان هم. کالیبراسیون: همان آشکارساز
 *   روی صفحه‌ای که ظرف را با کلیک برمی‌دارد (رفتار پیش از رفع) باید «ناپدید» بگوید، وگرنه سبزِ بقیه معنا ندارد.
 * ▸ toolbar (بند ۱۳، یافتۀ عکس) — ردیف ابزار /city/ و /articles/ در ۳۲۰ · ۳۹۰ · ۶۰۰ · ۷۲۰ · ۷۶۸: هر کنترل درون صفحه، بسته و با ظرف
 *   «فیلتر» باز؛ متن «فیلتر» در سه حالت رنگ (بسته · باز · هاور) ≥ ۴٫۵. کالیبراسیون با CSS پیش از رفع.
 * ▸ list (بند ۵) — نمای فهرستی همۀ هاب‌ها و فهرست‌های خبر/مقاله: بندانگشتی توکن، ستون، دو سطر خلاصه، بلندی ردیف (هدف دسکتاپ < ۱۴۰px).
 * ▸ paging (دستۀ 56-الف بند ۱) — fa/en/ar × ۱۴۴۰ · ۷۶۸ · ۳۹۰: شمار نمایش = اعلام، صفحۀ ۱ پر، تک‌صفحه تا گنجایش، بی کاشی خالی؛ --mut با هستۀ پیش از رفع قرمز.
 * ▸ shots (بند ۱۳، فقط با --only=shots) — عکس «پس» fa: هاب شهرها شبکه/فهرست، هاب خبر، جست‌وجوی فوکوس، فوتر × سه حالت ← tests/.screens/55/
 *
 * یک زنجیره در هر زمان؛ هم‌زمان هیچ درخواست دیگری به سایت. کد خروج: ۰ سبز · ۱ افتادن · ۲ محیط ناقص.
 */
'use strict';

const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE  = ENV.base.replace( /\/$/, '' );
const X     = Math.max( 1, Number( process.env.TP_TIMEOUT_X || 1 ) );
const ONLY  = ( process.argv.find( a => a.startsWith( '--only=' ) ) || '' ).slice( 7 ).split( ',' ).filter( Boolean );
const want  = k => ! ONLY.length || ONLY.includes( k );
const VIEWS = [ [ 1440, 900 ], [ 768, 1024 ], [ 390, 844 ] ];
const NARROW = 720;   /* topersia-g-news.js: (max-width: 720px) */

let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( note ? '   ← ' + note : '' ) ); };
const wait = ms => new Promise( r => setTimeout( r, ms ) );

/* وضعیت یک فیلد در صفحه: در DOM، دیدنی (هیچ نیای display:none · visibility:hidden · hidden · opacity 0، اندازۀ نا‌صفر)، فوکوس */
const STATE = sel => {
	const el = document.querySelector( sel );
	if ( ! el ) { return { found: false, vis: false }; }
	const r = el.getBoundingClientRect();
	let vis = el.isConnected && r.width > 0 && r.height > 0;
	for ( let x = el; x && vis; x = x.parentElement ) {
		const cs = getComputedStyle( x );
		if ( 'none' === cs.display || 'hidden' === cs.visibility || x.hidden || 0 === Number( cs.opacity ) ) { vis = false; }
	}
	return { found: true, vis, focus: document.activeElement === el, value: 'value' in el ? el.value : '' };
};

/* پس از هر کنش سه نمونه؛ «ماند» یعنی هر سه در DOM و دیدنی */
const stays = async ( page, sel ) => {
	const s = [];
	for ( const ms of [ 50, 250, 600 ] ) { await wait( ms ); s.push( await page.evaluate( STATE, sel ) ); }
	return { ok: s.every( x => x.found && x.vis ), last: s[ s.length - 1 ] };
};
const brief = r => r.found ? ( r.vis ? 'دیدنی' : 'پنهان' ) + ( r.focus ? ' · فوکوس' : '' ) : 'از DOM رفت';

const center = ( page, sel ) => page.evaluate( s => { const e = document.querySelector( s ); if ( e ) { e.scrollIntoView( { block: 'center', behavior: 'instant' } ); } }, sel );
const isOpen = ( page, sel ) => page.evaluate( s => { const e = document.querySelector( s ); return !! e && ! e.hidden && 'none' !== getComputedStyle( e ).display; }, sel );

/* کلیک بیرون: روی عنوان صفحه (h1)، که نه در ظرف فیلتر است و نه در combobox */
const clickOutside = async page => {
	const h = page.locator( 'main h1, .tp-page h1, h1' ).first();
	await h.evaluate( e => e.scrollIntoView( { block: 'center', behavior: 'instant' } ) );
	await h.click( { position: { x: 4, y: 4 } } );
};

async function searchField( page, ctx, tag, sel, touch ) {
	await center( page, sel );
	await page.click( sel );
	let r = await stays( page, sel );
	t( `${ tag } کلیک ← فیلد می‌ماند و فوکوس دارد`, r.ok && r.last.focus, brief( r.last ) );
	await page.keyboard.type( 'اصفهان', { delay: 20 } );
	r = await stays( page, sel );
	t( `${ tag } تایپ ← فیلد می‌ماند و مقدار دارد`, r.ok && 'اصفهان' === r.last.value, brief( r.last ) + ' · «' + r.last.value + '»' );
	await page.$eval( sel, e => e.blur() );
	r = await stays( page, sel );
	t( `${ tag } blur ← فیلد می‌ماند`, r.ok, brief( r.last ) );
	if ( touch ) {
		await center( page, sel );
		await page.tap( sel );
		r = await stays( page, sel );
		t( `${ tag } لمس ← فیلد می‌ماند و فوکوس دارد`, r.ok && r.last.focus, brief( r.last ) );
	}
}

async function searchPage( page, ctx, b, w, P, touch ) {
	const tag = `${ b } ${ w } ${ P.k }:`;
	await page.goto( BASE + P.u, { waitUntil: 'load', timeout: 60000 * X } );
	await page.waitForSelector( P.q, { state: 'attached', timeout: 20000 * X } );
	const narrow = w <= NARROW && P.panel;
	const BTN = '[data-tp-more-btn]', PANEL = '[data-tp-filter-panel]';
	if ( narrow ) {
		const closed = ! ( await isOpen( page, PANEL ) );
		t( `${ tag } موبایل ← ظرف فیلتر بسته و دکمۀ «فیلتر» دیدنی`, closed && ( await isOpen( page, BTN ) ) );
		await center( page, BTN );
		await ( touch ? page.tap( BTN ) : page.click( BTN ) );
		t( `${ tag } دکمۀ «فیلتر» ← ظرف باز`, await isOpen( page, PANEL ) );
	}
	await searchField( page, ctx, tag + ' جست‌وجو', P.q, touch );

	/* Escape: فیلد خالی؛ موبایل ← ظرف بسته و فوکوس به دکمه؛ دسکتاپ ← فیلد می‌ماند */
	await page.fill( P.q, '' );
	await page.focus( P.q );
	await page.keyboard.press( 'Escape' );
	if ( narrow ) {
		const st = await page.evaluate( ( [ b2, p2 ] ) => ( { exp: document.querySelector( b2 ).getAttribute( 'aria-expanded' ), hid: document.querySelector( p2 ).hidden, foc: document.activeElement === document.querySelector( b2 ) } ), [ BTN, PANEL ] );
		t( `${ tag } Escape ← ظرف بسته، فوکوس به «فیلتر»`, 'false' === st.exp && st.hid && st.foc, JSON.stringify( st ) );
		/* کلیک درون ظرف (برچسب) آن را نمی‌بندد؛ کلیک بیرون می‌بندد */
		await center( page, BTN );
		await page.click( BTN );
		await page.click( PANEL + ' label' );
		await wait( 200 );
		t( `${ tag } کلیک درون ظرف (برچسب) ← باز می‌ماند`, await isOpen( page, PANEL ) );
		await clickOutside( page );
		await wait( 200 );
		t( `${ tag } کلیک بیرون ← ظرف بسته`, ! ( await isOpen( page, PANEL ) ) );
		await center( page, BTN );
		await page.click( BTN );
	} else {
		const r = await stays( page, P.q );
		t( `${ tag } Escape ← فیلد می‌ماند`, r.ok, brief( r.last ) );
	}

	/* combobox مکان، اگر این صفحه دارد */
	const LOC = ( P.locScope || '' ) + ' .tp-loc__in';
	if ( await page.$( LOC ) ) {
		const LIST = ( P.locScope || '' ) + ' .tp-loc__list';
		await searchField( page, ctx, tag + ' مکان', LOC, touch );
		await page.fill( LOC, '' );
		await page.focus( LOC );
		await page.keyboard.type( 'اصف', { delay: 40 } );
		let listed = true;
		try { await page.waitForSelector( LIST + ':not([hidden]) [role="option"]', { timeout: 15000 * X } ); } catch ( e ) { listed = false; }
		t( `${ tag } مکان: سه نویسه ← فهرست باز`, listed );
		if ( listed ) {
			await page.$eval( LOC, e => e.blur() );
			await wait( 300 );
			let r = await stays( page, LOC );
			t( `${ tag } مکان: blur بی مقصد ← فیلد می‌ماند و فهرست بسته نمی‌شود`, r.ok && ( await isOpen( page, LIST ) ), brief( r.last ) );
			await page.focus( LOC );
			await page.keyboard.press( 'Escape' );
			r = await stays( page, LOC );
			t( `${ tag } مکان: Escape ← فهرست بسته، فیلد و مقدار می‌مانند`, r.ok && ! ( await isOpen( page, LIST ) ) && 'اصف' === r.last.value, brief( r.last ) );
			if ( narrow ) {
				await page.keyboard.press( 'Escape' );
				r = await stays( page, LOC );
				t( `${ tag } مکان: Escape دوم ← مقدار پاک، ظرف فیلتر باز می‌ماند`, r.ok && '' === r.last.value && ( await isOpen( page, PANEL ) ), brief( r.last ) );
			}
			await page.fill( LOC, '' );
			await page.focus( LOC );
			await page.keyboard.type( 'اصف', { delay: 40 } );
			await page.waitForSelector( LIST + ':not([hidden]) [role="option"]', { timeout: 15000 * X } ).catch( () => {} );
			if ( narrow ) { await page.click( PANEL + ' .tp-news-filter__f--q label' ); } else { await clickOutside( page ); }
			await wait( 250 );
			r = await stays( page, LOC );
			t( `${ tag } مکان: کلیک ${ narrow ? 'درون ظرف، بیرون از combobox' : 'بیرون' } ← فهرست بسته، فیلد می‌ماند`, r.ok && ! ( await isOpen( page, LIST ) ) && ( ! narrow || await isOpen( page, PANEL ) ), brief( r.last ) );
		}
	}
}

/* کالیبراسیون: رفتار پیش از رفع (ظرف با کلیک برداشته می‌شود) باید «ناپدید» گزارش شود */
async function calibrate( page, b ) {
	await page.goto( BASE + '/attractions/', { waitUntil: 'load', timeout: 60000 * X } );
	await page.evaluate( () => { const p = document.querySelector( '[data-tp-filter-panel]' ); if ( p ) { p.addEventListener( 'click', () => p.remove() ); } } );
	await center( page, '#tp-hub-q' );
	await page.click( '#tp-hub-q' );
	const r = await stays( page, '#tp-hub-q' );
	t( `${ b } کالیبراسیون: آشکارساز برداشته‌شدن ظرف با کلیک (باگ پیش از رفع) را «ناپدید» می‌گوید`, ! r.ok, brief( r.last ) );
}

async function search() {
	console.log( '\nبند ۴ — جعبۀ جست‌وجو با کلیک ناپدید نمی‌شود؛ بستن فقط با کلیک بیرون یا Escape\n' + '─'.repeat( 78 ) );
	const PAGES = [
		{ k: 'هاب جاذبه‌ها', u: '/attractions/', q: '#tp-hub-q', panel: true, locScope: '#tp-hub-form' },
		{ k: 'هاب مقاله', u: '/articles/', q: '#tp-hub-q', panel: true, locScope: '[data-tp-filter-panel]' },
		{ k: 'هاب خبر', u: '/news/', q: '#tp-news-q', panel: true, locScope: '[data-tp-news-filter]' },
	];
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ] ) {
		const br = await type.launch();
		try {
			{
				const ctx = await br.newContext( { viewport: { width: 1440, height: 900 } } );
				await calibrate( await ctx.newPage(), name );
				await ctx.close();
			}
			for ( const [ w, h ] of VIEWS ) {
				const touch = w < 1024;
				const ctx = await br.newContext( { viewport: { width: w, height: h }, hasTouch: touch } );
				const page = await ctx.newPage();
				page.setDefaultTimeout( 15000 * X );
				for ( const P of PAGES ) {
					try { await searchPage( page, ctx, name, w, P, touch ); } catch ( e ) { t( `${ name } ${ w } ${ P.k }: بی خطای اجرا`, false, String( e.message ).split( '\n' )[0] ); }
				}
				try { await headerSearch( page, name, w, touch ); } catch ( e ) { t( `${ name } ${ w } هدر: بی خطای اجرا`, false, String( e.message ).split( '\n' )[0] ); }
				await ctx.close();
			}
		} finally {
			await br.close();
		}
	}
}

/* جست‌وجوی هدر وودمارت (پوستۀ شخص ثالث): ۱۴۴۰ فرم درون‌خطی در .whb-header؛ پهنای کم (کاوش ۱۸ نوامبر، کروم و فایرفاکس ۷۶۸/۳۹۰)
 * هدر فرم ندارد و جست‌وجو در منوی کناری است (.mobile-nav.wd-side-hidden، بیرون از صفحه تا دکمۀ «Open mobile menu»؛ باز = wd-opened). */
const MNAV = '.mobile-nav.wd-side-hidden';
const navOpen = page => page.evaluate( s => { const n = document.querySelector( s ); return !! n && n.classList.contains( 'wd-opened' ) && n.getBoundingClientRect().right > 0; }, MNAV );
async function headerSearch( page, b, w, touch ) {
	await page.goto( BASE + '/attractions/', { waitUntil: 'load', timeout: 60000 * X } );
	const tag = `${ b } ${ w } هدر وودمارت:`;
	const IN = '.whb-header .wd-header-search-form input.s';
	const inline = await page.evaluate( s => Array.prototype.some.call( document.querySelectorAll( s ), e => e.getBoundingClientRect().width > 0 ), IN );
	if ( inline ) {
		const sel = await page.evaluate( s => { const all = document.querySelectorAll( s ); for ( let i = 0; i < all.length; i++ ) { if ( all[ i ].getBoundingClientRect().width > 0 ) { all[ i ].setAttribute( 'data-tp-w55', '1' ); return true; } } return false; }, IN ) ? '[data-tp-w55]' : IN;
		await page.evaluate( () => window.scrollTo( 0, 0 ) );
		await searchField( page, null, tag, sel, false );
		await page.focus( sel );
		await page.keyboard.press( 'Escape' );
		const r = await stays( page, sel );
		t( `${ tag } Escape ← فیلد می‌ماند`, r.ok, brief( r.last ) );
		return;
	}
	const burger = page.locator( '.whb-header .wd-header-mobile-nav a' ).filter( { visible: true } ).first();
	if ( ! await burger.count() ) { t( `${ tag } دکمۀ منوی موبایل پیدا شد`, false, 'نیست' ); return; }
	await page.evaluate( () => window.scrollTo( 0, 0 ) );
	await burger.click();
	await wait( 700 );
	t( `${ tag } دکمۀ منو ← منوی کناری باز`, await navOpen( page ) );
	const sel = MNAV + ' input.s';
	/* هر کنش: فیلد در DOM و دیدنی و منوی کناری هنوز باز */
	const keep = async () => { const r = await stays( page, sel ); return { ok: r.ok && await navOpen( page ), last: r.last }; };
	await page.click( sel );
	let r = await keep();
	t( `${ tag } کلیک ← فیلد و منو می‌مانند، فوکوس دارد`, r.ok && r.last.focus, brief( r.last ) );
	await page.keyboard.type( 'اصفهان', { delay: 20 } );
	r = await keep();
	t( `${ tag } تایپ ← فیلد و منو می‌مانند و مقدار دارد`, r.ok && 'اصفهان' === r.last.value, brief( r.last ) + ' · «' + r.last.value + '»' );
	await page.$eval( sel, e => e.blur() );
	r = await keep();
	t( `${ tag } blur ← فیلد و منو می‌مانند`, r.ok, brief( r.last ) );
	if ( touch ) {
		await page.tap( sel );
		r = await keep();
		t( `${ tag } لمس ← فیلد و منو می‌مانند، فوکوس دارد`, r.ok && r.last.focus, brief( r.last ) );
	}
	/* Escape منوی کناری را می‌بندد (رفتار خود وودمارت، بستنِ مجاز)؛ فیلد از DOM نمی‌رود و مقدار می‌ماند */
	await page.focus( sel );
	await page.keyboard.press( 'Escape' );
	await wait( 600 );
	const e = await page.evaluate( STATE, sel );
	t( `${ tag } Escape ← منو بسته؛ فیلد در DOM با مقدار`, ! await navOpen( page ) && e.found && 'اصفهان' === e.value, ( e.found ? 'در DOM' : 'از DOM رفت' ) + ' · «' + e.value + '»' );
}

/* ── بند ۵: نمای فهرستی تحریریه ─────────────────────────────────────────────
 * انتخاب «فهرست» در localStorage (کلید tp-news-view، همان news.js) پیش از بارگذاری. هر صفحه، هر پهنا:
 *   بندانگشتی دقیقاً توکن (۱۲۰×۹۰ بالای ۷۲۰، ۸۸×۶۶ تا ۷۲۰) · دو ستون / یک ستون · خلاصه حداکثر دو سطر ·
 *   بلندی هر ردیف (جایگاه تبلیغ جدا: نسبت ۴:۳ خودش را دارد) — هدف دسکتاپ زیر ۱۴۰px · بی سرریز افقی.
 *   نمای شبکه (بی انتخاب): سطر «نوع · در X» و خلاصه پنهان. */
const LIST_PAGES = [
	{ k: 'هاب جاذبه‌ها', u: '/attractions/' },
	{ k: 'هاب شهر', u: '/city/' },
	{ k: 'هاب استان', u: '/province/' },
	{ k: 'خبر', u: '/news/' },
	{ k: 'مقاله', u: '/articles/' },
	{ k: 'خبر استان اصفهان', u: '/province/isfahan/news/' },
];
const ROWS = [];   /* برای جدول پایانی گزارش */
const MEASURE = () => {
	const grid = document.querySelector( '[data-tp-news-items]' );
	if ( ! grid ) { return null; }
	const px = v => parseFloat( v ) || 0;
	const cards = Array.prototype.filter.call( grid.children, c => c.matches( '.tp-news-card' ) && c.getBoundingClientRect().height > 0 );
	const ads   = Array.prototype.filter.call( grid.children, c => ! c.matches( '.tp-news-card' ) && c.getBoundingClientRect().height > 0 );
	const lines = el => { if ( ! el ) { return 0; } const cs = getComputedStyle( el ); return 'none' === cs.display ? 0 : Math.round( el.getBoundingClientRect().height / px( cs.lineHeight ) ); };
	const hs = cards.map( c => Math.round( c.getBoundingClientRect().height * 10 ) / 10 );
	const thumbs = cards.map( c => { const m = c.querySelector( '.tp-news-card__media' ); if ( ! m ) { return ''; } const r = m.getBoundingClientRect(); return Math.round( r.width ) + '×' + Math.round( r.height ); } );
	const lefts = new Set( cards.slice( 0, 6 ).map( c => Math.round( c.getBoundingClientRect().left ) ) );
	const sum = cards.map( c => lines( c.querySelector( '.tp-news-card__sum' ) ) );
	const sub = cards.map( c => { const s = c.querySelector( '.tp-news-card__sub, .tp-hub-card__sub' ); return s && 'none' !== getComputedStyle( s ).display ? s.textContent.trim() : ''; } );
	const newsSub = cards.filter( c => { const s = c.querySelector( '.tp-news-card__sub' ); return s && 'none' !== getComputedStyle( s ).display; } ).length;
	const hubSum  = cards.filter( c => { const s = c.matches( '.tp-hub-card' ) && c.querySelector( '.tp-news-card__sum' ); return s && 'none' !== getComputedStyle( s ).display; } ).length;
	/* بالای کارت (بندانگشتی + نشان «نمونۀ نمایشی» در پیش‌نمایش) نباید از خود بندانگشتی بلندتر شود */
	const topOver = Math.max( 0, ...cards.map( c => { const t = c.querySelector( '.tp-news-card__top' ), m = c.querySelector( '.tp-news-card__media' ); return t && m ? Math.round( t.getBoundingClientRect().height - m.getBoundingClientRect().height ) : 0; } ) );
	const demo = cards.filter( c => c.querySelector( '.tp-demo-badge' ) ).length;
	const sorted = hs.slice().sort( ( a, b ) => a - b );
	return {
		rows: grid.classList.contains( 'tp-news-grid--rows' ), n: cards.length, ads: ads.length,
		max: sorted.length ? sorted[ sorted.length - 1 ] : 0, med: sorted.length ? sorted[ Math.floor( sorted.length / 2 ) ] : 0, min: sorted.length ? sorted[0] : 0,
		thumbs: Array.from( new Set( thumbs ) ), cols: lefts.size, sumMax: Math.max( 0, ...sum ), subN: sub.filter( Boolean ).length, subEx: sub.find( Boolean ) || '',
		newsSub, hubSum, topOver, demo,
		over: document.documentElement.scrollWidth - window.innerWidth,
	};
};
async function list() {
	console.log( '\nبند ۵ — نمای فهرستی تحریریه: بندانگشتی ثابت، دو ستون/یک ستون، بلندی ردیف\n' + '─'.repeat( 78 ) );
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ] ) {
		const br = await type.launch();
		try {
			/* نمای شبکه (پیش‌فرض): خلاصۀ کاشی هاب و سطر تازۀ «موضوع · در X» خبر پنهان؛ زیرعنوان کاشی هاب (دستۀ ۵۳ بند ۶) در شبکه هم هست */
			{
				const ctx = await br.newContext( { viewport: { width: 1440, height: 900 } } );
				const page = await ctx.newPage();
				for ( const u of [ '/attractions/', '/news/' ] ) {
					await page.goto( BASE + u, { waitUntil: 'load', timeout: 60000 * X } );
					const g = await page.evaluate( MEASURE );
					t( `${ name } 1440 شبکه (پیش‌فرض) ${ u }: نه فهرست؛ خلاصۀ کاشی هاب و «موضوع · در X» خبر پنهان`, !! g && ! g.rows && 0 === g.hubSum && 0 === g.newsSub, g ? `rows=${ g.rows } خلاصۀ هاب=${ g.hubSum } زیرعنوان خبر=${ g.newsSub }` : 'بی شبکه' );
				}
				await ctx.close();
			}
			for ( const [ w, h ] of VIEWS ) {
				const ctx = await br.newContext( { viewport: { width: w, height: h } } );
				await ctx.addInitScript( () => { try { window.localStorage.setItem( 'tp-news-view', 'list' ); } catch ( e ) { /* بی localStorage */ } } );
				const page = await ctx.newPage();
				page.setDefaultTimeout( 15000 * X );
				const wide = w > NARROW;
				const TW = wide ? 120 : 88, TH = wide ? 90 : 66;
				for ( const P of LIST_PAGES ) {
					const tag = `${ name } ${ w } ${ P.k }:`;
					try {
						await page.goto( BASE + P.u, { waitUntil: 'load', timeout: 60000 * X } );
						await page.waitForSelector( '.tp-news-grid--rows', { timeout: 15000 * X } );
						await page.evaluate( () => document.fonts && document.fonts.ready );
						const m = await page.evaluate( MEASURE );
						ROWS.push( Object.assign( { b: name, w, k: P.k }, m ) );
						const thumbOk = m.thumbs.length > 0 && m.thumbs.every( s => s === TW + '×' + TH );
						t( `${ tag } فهرست با ${ m.n } ردیف؛ بندانگشتی ${ TW }×${ TH } (توکن)`, m.rows && m.n > 0 && thumbOk, m.thumbs.join( ' ' ) );
						t( `${ tag } ${ wide ? 'دو ستون' : 'یک ستون' }`, m.cols === ( wide && m.n > 1 ? 2 : 1 ), 'ستون: ' + m.cols );
						t( `${ tag } خلاصه حداکثر دو سطر`, m.sumMax <= 2, 'بیشینه ' + m.sumMax );
						t( `${ tag } بالای ردیف = خود بندانگشتی (نشان «نمونۀ نمایشی» روی آن، نه زیرش)`, m.topOver <= 1, `اضافه ${ m.topOver }px · ${ m.demo } ردیف نمایشی` );
						t( `${ tag } بی سرریز افقی`, m.over <= 0, m.over + 'px' );
						if ( wide ) {
							t( `${ tag } بلندی ردیف زیر ۱۴۰px (بیشینه ${ m.max } · میانه ${ m.med })`, m.max > 0 && m.max < 140, `کمینه ${ m.min }` + ( m.ads ? ` · ${ m.ads } جایگاه تبلیغ جدا` : '' ) );
						} else {
							console.log( `  ℹ️ ${ tag } بلندی ردیف بیشینه ${ m.max } · میانه ${ m.med } · کمینه ${ m.min }` );
						}
					} catch ( e ) {
						t( `${ tag } بی خطای اجرا`, false, String( e.message ).split( '\n' )[0] );
					}
				}
				await ctx.close();
			}
		} finally {
			await br.close();
		}
	}
	console.log( '\nجدول بلندی ردیف (px) — مرورگر · پهنا · صفحه · ردیف · بیشینه · میانه · کمینه · بندانگشتی · ستون · «نوع · در X» (نمونه)' );
	ROWS.forEach( r => console.log( `  ${ r.b } · ${ r.w } · ${ r.k } · ${ r.n } · ${ r.max } · ${ r.med } · ${ r.min } · ${ r.thumbs.join( '/' ) } · ${ r.cols } · نمایشی ${ r.demo } · ${ r.subN }/${ r.n } «${ r.subEx.replace( /\s+/g, ' ' ).slice( 0, 60 ) }»` ) );
}

/* ── بند ۶: شمار هاب — گیت ─────────────────────────────────────────────────────
 * HTTP (مهمان): /province/ همۀ موارد بی صفحه‌بندی و بی «نمایش بیشتر»؛ /province/page/2/ ← ۳۰۱ /province/؛
 *   ?page=N ← ۳۰۱ /…/page/N/ با پالایش جاری؛ هاب‌های دیگر ۴۸ کاشی + <a href rel=next> به صفحۀ بعد؛ صفحۀ آخر بی دکمه.
 * مرورگر: کلیک ← ۴۸ کارت تازه در همان شبکه، جایگاه تبلیغ تازه نه، فوکوس روی نخستین کارت تازه، اعلان زنده؛ Enter هم. */
const PER = 48;
const MORE_HUBS = [ { k: 'هاب شهر', u: '/city/' }, { k: 'هاب جاذبه‌ها', u: '/attractions/' } ];
const tilesIn = html => ( html.match( /<article class="tp-news-card tp-hub-card">/g ) || [] ).length;
const moreHref = html => { const m = html.match( /<a class="tp-hub-loadmore__a" href="([^"]+)" rel="next" data-tp-load-more/ ); return m ? m[1].replace( /&#038;|&amp;/g, '&' ) : ''; };
const lastPage = html => { const n = ( html.match( /<nav class="tp-news-pages"[^>]*>(.*?)<\/nav>/s ) || [ '', '' ] )[1].match( /\/page\/(\d+)\//g ) || []; return n.reduce( ( a, s ) => Math.max( a, +s.replace( /\D/g, '' ) ), 1 ); };
async function more() {
	console.log( '\nبند ۶ — شمار هاب: استان همه، بقیه ۴۸ با «نمایش بیشتر» خزنده‌پذیر\n' + '─'.repeat( 78 ) );
	{
		const r = await get( '/province/' ), h = r.body.toString( 'utf8' ), n = tilesIn( h );
		/* کل از data-tp-news-msg («نتیجه: ۳۱»، رقم فارسی) — همان found_posts کوئری */
		const raw = ( h.match( /data-tp-news-msg="[^"]*?([۰-۹0-9]+)"/ ) || [ '', '' ] )[1];
		const total = raw ? +raw.replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ) : -1;
		t( `هاب استان: همۀ موارد در یک صفحه (${ n } کاشی از ${ total }، ۳۱ استان)`, 200 === r.code && n >= 31 && n === total, `کاشی ${ n } · کل ${ total }` );
		t( 'هاب استان: بی صفحه‌بندی و بی «نمایش بیشتر»', ! /class="tp-news-pages"/.test( h ) && ! /data-tp-load-more/.test( h ) );
		const p2 = await get( '/province/page/2/' );
		t( 'هاب استان: /province/page/2/ ← ۳۰۱ /province/ (بی نشانی دوم برای همان فهرست)', 301 === p2.code && /\/province\/$/.test( String( p2.headers.location || '' ) ), `${ p2.code } ${ p2.headers.location || '' }` );
	}
	for ( const H of MORE_HUBS ) {
		const r = await get( H.u ), h = r.body.toString( 'utf8' ), n = tilesIn( h ), href = moreHref( h ), last = lastPage( h );
		t( `${ H.k }: صفحۀ ۱ دقیقاً ${ PER } کاشی`, 200 === r.code && PER === n, String( n ) );
		t( `${ H.k }: «نمایش بیشتر» = <a href> واقعی صفحۀ ۲ با rel=next؛ صفحه‌بندی پیوندی هم هست`, new RegExp( H.u.replace( /\//g, '\\/' ) + 'page\\/2\\/$' ).test( href ) && last >= 2, `${ href } · آخرین ${ last }` );
		const q = await get( H.u + '?page=2&tp_q=' + encodeURIComponent( 'ا' ) );
		const loc = decodeURIComponent( String( q.headers.location || '' ) );
		t( `${ H.k }: ?page=2 ← ۳۰۱ ${ H.u }page/2/ با پالایش جاری`, 301 === q.code && loc.includes( H.u + 'page/2/' ) && loc.includes( 'tp_q=ا' ), `${ q.code } ${ loc }` );
		const l = await get( H.u + 'page/' + last + '/' ), lh = l.body.toString( 'utf8' ), ln = tilesIn( lh );
		t( `${ H.k }: صفحۀ آخر (${ last }) ۲۰۰، ۱ تا ${ PER } کاشی، بی «نمایش بیشتر»`, 200 === l.code && ln >= 1 && ln <= PER && ! /data-tp-load-more/.test( lh ), `${ l.code } · ${ ln } کاشی` );
	}
	const COUNT = () => { const i = document.querySelector( '[data-tp-news-items]' ); return i ? { cards: i.querySelectorAll( ':scope > .tp-news-card' ).length, other: i.querySelectorAll( ':scope > :not(.tp-news-card)' ).length } : { cards: -1, other: -1 }; };
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ] ) {
		const br = await type.launch();
		try {
			for ( const [ w, h ] of VIEWS ) {
				const ctx = await br.newContext( { viewport: { width: w, height: h } } );
				const page = await ctx.newPage();
				page.setDefaultTimeout( 30000 * X );
				const H = MORE_HUBS[0], tag = `${ name } ${ w } ${ H.k }:`;
				try {
					await page.goto( BASE + H.u, { waitUntil: 'load', timeout: 90000 * X } );
					const a0 = await page.evaluate( COUNT );
					const btn = await page.evaluate( () => { const a = document.querySelector( 'a[data-tp-load-more]' ); if ( ! a ) { return null; } a.scrollIntoView( { block: 'center', behavior: 'instant' } ); const r = a.getBoundingClientRect(); return { h: r.height, href: a.getAttribute( 'href' ) }; } );
					t( `${ tag } «نمایش بیشتر» دیدنی، هدف لمسی ≥ ۴۴px`, !! btn && btn.h >= 44, btn ? btn.h + 'px' : 'نیست' );
					if ( ! btn ) { continue; }
					if ( 1440 === w ) {
						await page.focus( 'a[data-tp-load-more]' );
						await page.keyboard.press( 'Enter' );
					} else {
						await page.click( 'a[data-tp-load-more]' );
					}
					await page.waitForFunction( n => { const i = document.querySelector( '[data-tp-news-items]' ); return i && i.querySelectorAll( ':scope > .tp-news-card' ).length > n; }, a0.cards, { timeout: 60000 * X } );
					await page.waitForFunction( () => ! document.querySelector( 'a[data-tp-load-more][aria-busy="true"]' ) );
					const a1 = await page.evaluate( COUNT );
					const st = await page.evaluate( () => {
						const i = document.querySelector( '[data-tp-news-items]' ), cards = i.querySelectorAll( ':scope > .tp-news-card' );
						const ae = document.activeElement, nx = document.querySelector( 'a[data-tp-load-more]' );
						const live = document.querySelector( '[data-tp-news-live]' );
						return { url: location.pathname, focusIdx: Array.prototype.indexOf.call( cards, ae && ae.closest ? ae.closest( '.tp-news-card' ) : null ), live: live ? live.textContent.trim() : '', next: nx ? nx.getAttribute( 'href' ) : '', over: document.documentElement.scrollWidth - window.innerWidth,
							ids: Array.prototype.map.call( cards, c => ( c.querySelector( '.tp-news-card__title a' ) || {} ).href || '' ) };
					} );
					const uniq = new Set( st.ids ).size;
					t( `${ tag } ${ 1440 === w ? 'Enter' : 'کلیک' } ← ${ a1.cards - a0.cards } کارت تازه در همان شبکه (${ a0.cards }→${ a1.cards })، بی تکرار`, a0.cards === PER && a1.cards > a0.cards && a1.cards - a0.cards <= PER && uniq === a1.cards, `یکتا ${ uniq }` );
					t( `${ tag } جایگاه تبلیغ میان‌فهرستی صفحۀ بعد افزوده نشد`, a1.other === a0.other, `${ a0.other }→${ a1.other }` );
					t( `${ tag } فوکوس روی نخستین کارت تازه؛ اعلان زنده؛ نشانی صفحه همان`, st.focusIdx === a0.cards && '' !== st.live && st.url === new URL( BASE + H.u ).pathname, `فوکوس ${ st.focusIdx } · «${ st.live }» · ${ st.url }` );
					t( `${ tag } دکمه به صفحۀ بعدی رفت یا (صفحۀ آخر) برداشته شد؛ بی سرریز افقی`, ( '' === st.next || /\/page\/3\/$/.test( st.next ) ) && st.over <= 0, `${ st.next || 'برداشته شد' } · سرریز ${ st.over }` );
				} catch ( e ) {
					t( `${ tag } بی خطای اجرا`, false, String( e.message ).split( '\n' )[0] );
				} finally {
					await ctx.close();
				}
			}
		} finally {
			await br.close();
		}
	}
}

/* ── بند ۷: CLS — منشأ هر جابه‌جایی و گیت ≤ ۰٫۱ ─────────────────────────────────
 * فقط Chromium: فایرفاکس LayoutShift ندارد (PerformanceObserver «layout-shift» پشتیبانی نمی‌شود) — در گزارش گفته می‌شود.
 * هاب‌ها و فهرست‌ها (شبکه و فهرست) و تک‌خبر/تک‌مقاله × ۱۴۴۰/۷۶۸/۳۹۰، مهمان: بارگذاری تا load + ۲s، سپس پیمایش تا پایین
 *   با گام یک صفحه‌نمایش (instant، ۴۰۰ms درنگ). هر ورودی: زمان، مقدار، فاز، سه گرهٔ نخست با مستطیل پیش و پس.
 * حکم: مجموع همهٔ ورودی‌ها بی ورودی کاربر ≤ ۰٫۱ (سخت‌گیرتر از بزرگ‌ترین پنجرهٔ جلسهٔ web-vitals، که جدا گزارش می‌شود). */
const CLS_LIMIT = 0.1;
const CLS_PAGES = [
	{ k: 'هاب استان', u: '/province/', views: true },
	{ k: 'هاب شهر', u: '/city/', views: true },
	{ k: 'هاب جاذبه‌ها', u: '/attractions/', views: true },
	{ k: 'خبرها', u: '/news/', views: true },
	{ k: 'مقاله‌ها', u: '/articles/', views: true },
	{ k: 'تک‌خبر', from: '/news/' },
	{ k: 'تک‌مقاله', from: '/articles/' },
];
const CLS_INIT = () => {
	window.__cls = [];
	window.__clsPh = 'بارگذاری';
	const nm = n => n && 1 === n.nodeType ? n.tagName.toLowerCase() + ( n.id ? '#' + n.id : '' ) + ( n.classList.length ? '.' + [ ...n.classList ].slice( 0, 3 ).join( '.' ) : '' ) : '#text';
	const rc = r => r ? Math.round( r.x ) + ',' + Math.round( r.y ) + ' ' + Math.round( r.width ) + '×' + Math.round( r.height ) : '';
	try {
		new PerformanceObserver( l => {
			for ( const e of l.getEntries() ) {
				window.__cls.push( { t: Math.round( e.startTime ), v: e.value, inp: e.hadRecentInput, ph: window.__clsPh, src: ( e.sources || [] ).slice( 0, 3 ).map( s => ( { n: nm( s.node ), p: rc( s.previousRect ), c: rc( s.currentRect ) } ) ) } );
			}
		} ).observe( { type: 'layout-shift', buffered: true } );
	} catch ( e ) { /* بی LayoutShift */ }
};
const sessionCls = es => {   /* بزرگ‌ترین پنجرهٔ جلسه (فاصلهٔ ۱s، سقف ۵s) — تعریف web-vitals */
	let best = 0, cur = 0, first = 0, last = 0;
	es.forEach( e => {
		if ( cur && ( e.t - last > 1000 || e.t - first > 5000 ) ) { cur = 0; }
		if ( ! cur ) { first = e.t; }
		cur += e.v; last = e.t; best = Math.max( best, cur );
	} );
	return best;
};
const firstCard = html => ( html.match( /<h3 class="tp-news-card__title"><a href="([^"]+)"/ ) || [ '', '' ] )[1].replace( /&#038;|&amp;/g, '&' );
async function cls() {
	console.log( '\nبند ۷ — CLS: منشأ هر جابه‌جایی (Chromium؛ فایرفاکس LayoutShift ندارد) — حکم مجموع ≤ ' + CLS_LIMIT + '\n' + '─'.repeat( 78 ) );
	const pages = [];
	for ( const P of CLS_PAGES ) {
		if ( ! P.from ) { pages.push( P ); continue; }
		const u = firstCard( ( await get( P.from ) ).body.toString( 'utf8' ) );
		if ( u ) { pages.push( { k: P.k, u: u.replace( /^https?:\/\/[^/]+/, '' ) } ); } else { console.log( `  ℹ️ ${ P.k }: کارتی در ${ P.from } نیست — سنجیده نشد` ); }
	}
	const origins = {};
	const br = await chromium.launch();
	try {
		for ( const [ w, h ] of VIEWS ) {
			for ( const P of pages ) {
				for ( const view of P.views ? [ 'grid', 'list' ] : [ '' ] ) {
					const ctx = await br.newContext( { viewport: { width: w, height: h } } );
					if ( view ) { await ctx.addInitScript( v => { try { window.localStorage.setItem( 'tp-news-view', v ); } catch ( e ) { /* بی localStorage */ } }, view ); }
					await ctx.addInitScript( CLS_INIT );
					const page = await ctx.newPage();
					page.setDefaultTimeout( 30000 * X );
					const tag = `${ w } ${ P.k }${ view ? ' (' + ( 'list' === view ? 'فهرست' : 'شبکه' ) + ')' : '' }`;
					try {
						await page.goto( BASE + P.u, { waitUntil: 'load', timeout: 90000 * X } );
						await wait( 2000 );
						await page.evaluate( () => { window.__clsPh = 'پیمایش'; } );
						const H = await page.evaluate( () => document.documentElement.scrollHeight );
						for ( let y = 0; y < H; y += h ) {
							await page.evaluate( yy => window.scrollTo( { top: yy, behavior: 'instant' } ), y );
							await wait( 400 );
						}
						await wait( 600 );
						const es = ( await page.evaluate( () => window.__cls ) ).filter( e => ! e.inp );
						const sum = es.reduce( ( a, e ) => a + e.v, 0 ), ses = sessionCls( es );
						es.forEach( e => e.src.slice( 0, 1 ).forEach( s => { const k = e.ph + ' · ' + s.n; origins[ k ] = origins[ k ] || { v: 0, pages: new Set(), ex: '' }; origins[ k ].v += e.v; origins[ k ].pages.add( tag ); origins[ k ].ex = origins[ k ].ex || `${ s.p } → ${ s.c }`; } ) );
						const top = es.slice().sort( ( a, b ) => b.v - a.v ).slice( 0, 3 ).map( e => `${ e.v.toFixed( 3 ) }@${ e.t }ms ${ e.ph } ${ e.src.map( s => s.n + ' [' + s.p + ' → ' + s.c + ']' ).join( ' + ' ) }` ).join( ' | ' );
						t( `chromium ${ tag }: CLS کل ${ sum.toFixed( 3 ) } ≤ ${ CLS_LIMIT } (پنجرهٔ جلسه ${ ses.toFixed( 3 ) })`, sum <= CLS_LIMIT, top || 'بی جابه‌جایی' );
					} catch ( e ) {
						t( `chromium ${ tag }: بی خطای اجرا`, false, String( e.message ).split( '\n' )[0] );
					} finally {
						await ctx.close();
					}
				}
			}
		}
	} finally {
		await br.close();
	}
	console.log( '\nمنشأ جابه‌جایی‌ها (فاز · گرهٔ نخست ← مجموع مقدار · شمار ترکیب · نمونهٔ مستطیل)' );
	Object.entries( origins ).sort( ( a, b ) => b[1].v - a[1].v ).forEach( ( [ k, o ] ) => console.log( `  ${ o.v.toFixed( 3 ) } · ${ k } · ${ o.pages.size } ترکیب · ${ o.ex } · مثلاً ${ [ ...o.pages ].slice( 0, 3 ).join( '، ' ) }` ) );
}

/* ── بند ۶: وزن HTML · زمان قطعه · LCP (پیش/پس) — فقط گزارش، حکم نه ────────────────
 * node tests/w55-local.js --only=perf [--label=پیش]
 *   وزن   GET مهمان بی فشرده‌سازی (Accept-Encoding: identity): بایت HTML و gzip همان (zlib) · شمار کاشی صفحۀ ۱
 *   قطعه  ?tp_partial=1 از وردپرس (سه بار، میانه) و همان از مسیر سبک fragment.php (کش پرونده‌ای؛ ۲۰۴ = نبود)
 *   LCP   PerformanceObserver (buffered) پس از load + ۱٫۵s، سه بار میانه — کروم و فایرفاکس × ۱۴۴۰ و ۳۹۰ */
const PERF_PAGES = [
	{ k: 'هاب استان', u: '/province/' },
	{ k: 'هاب شهر', u: '/city/' },
	{ k: 'هاب جاذبه‌ها', u: '/attractions/' },
];
/* ── دستۀ 56-الف بند ۱ — صفحه‌بندی پس از پالایش: شمار نمایش‌داده‌شده = شمار اعلام‌شده، صفحۀ ۱ تا گنجایشش پر،
   هیچ صفحۀ غیرآخر ردیف ناقص (کاشی خالی) ندارد. پیش از رفع: /news/ بی‌پالایش بلوک بالا ۷ خبر از ۱۲ را برمی‌داشت ←
   شبکۀ ۵ کاشی در ۴ ستون (۳ خانۀ خالی) و ۲ خبر در صفحۀ ۲. جهش: --mut هستۀ topersia-g-news.php را موقتاً با نسخۀ
   HEAD~ (پیش از رفع، TP_MUT_REF) عوض می‌کند و باید قرمز شود؛ در finally برمی‌گردد. */
const PAGING_URLS = [
	{ k: 'اخبار', u: '/news/' }, { k: 'اخبار ۱۲ ماه', u: '/news/?tp_when=12m' }, { k: 'اخبار قدیمی‌تر', u: '/news/?tp_sort=old' },
	{ k: 'اخبار شهر', u: '/news/?tp_kind=tp_city' }, { k: 'مقالات', u: '/articles/' },
	{ k: 'هاب شهر «ا»', u: '/city/?tp_q=' + encodeURIComponent( 'ا' ) }, { k: 'هاب جاذبه «ا»', u: '/attractions/?tp_q=' + encodeURIComponent( 'ا' ) },
];
const digits = s => +String( s ).replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ).replace( /[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf( d ) ).replace( /\D/g, '' );
const announced = h => { const m = h.match( /data-tp-news-msg="([^"]*)"/ ); return m && /[0-9۰-۹٠-٩]/.test( m[1] ) ? digits( m[1] ) : -1; };
const itemsIn = h => ( h.match( /<article class="tp-news-(?:card|feat|mini)[ "]/g ) || [] ).length;
const featIn  = h => ( h.match( /<article class="tp-news-(?:feat|mini)[ "]/g ) || [] ).length;
const pageUrl = ( u, n ) => { const [ p, q ] = u.split( '?' ); return p + ( n > 1 ? 'page/' + n + '/' : '' ) + ( q ? '?' + q : '' ); };
async function pagingServer() {
	for ( const L of [ '', '/en', '/ar' ] ) {
		for ( const P of PAGING_URLS ) {
			const u = L + P.u, r = await get( u ), h = r.body.toString( 'utf8' ), tag = `${ L || '/fa' } ${ P.k }`;
			if ( 200 !== r.code ) { t( `${ tag }: ${ u } در دسترس`, 404 === r.code, `کد ${ r.code } (۴۰۴ = این زبان این فهرست را ندارد)` ); continue; }
			const total = announced( h ), last = lastPage( h );
			let sum = itemsIn( h );
			const grid1 = sum - featIn( h );
			for ( let n = 2; n <= last; n++ ) { sum += itemsIn( ( await get( pageUrl( u, n ) ) ).body.toString( 'utf8' ) ); }
			t( `${ tag }: شمار نمایش‌داده‌شده در همۀ صفحه‌ها = شمار اعلام‌شده`, total >= 0 && sum === total, `نمایش ${ sum } · اعلام ${ total } · صفحه ${ last }` );
			const per = /^\/(?:news|articles)\//.test( P.u ) ? 12 : PER;
			t( `${ tag }: صفحۀ ۱ تا گنجایش پر (شبکه = min(باقی، ${ per }))؛ بلوک بالا جای شبکه را نمی‌گیرد`, grid1 === Math.min( Math.max( 0, total - featIn( h ) ), per ), `شبکه ${ grid1 } · بالا ${ featIn( h ) } · کل ${ total }` );
			if ( total <= per ) { t( `${ tag }: کل ≤ ${ per } ← تک‌صفحه، بی صفحه‌بندی و «نمایش بیشتر»`, 1 === last && ! /data-tp-load-more/.test( h ), `صفحه ${ last }` ); }
			const after = await get( pageUrl( u, last + 1 ) );
			t( `${ tag }: صفحۀ ${ last + 1 } ← ۴۰۴ یا ۳۰۱`, 404 === after.code || 301 === after.code, String( after.code ) );
		}
	}
}
async function pagingBrowser() {
	const br = await chromium.launch();
	try {
		for ( const L of [ '', '/en', '/ar' ] ) {
			for ( const [ w, hh ] of VIEWS ) {
				const ctx = await br.newContext( { viewport: { width: w, height: hh } } ), page = await ctx.newPage();
				for ( const P of PAGING_URLS ) {
					const res = await page.goto( BASE + L + P.u, { waitUntil: 'load', timeout: 120000 * X } );
					if ( ! res || 200 !== res.status() ) { continue; }
					const g = await page.evaluate( () => {
						const i = document.querySelector( '[data-tp-news-items]' );
						if ( ! i ) { return null; }
						const vis = e => { const r = e.getBoundingClientRect(), cs = getComputedStyle( e ); return 'none' !== cs.display && r.width > 0 && r.height > 0; };
						const kids = [ ...i.children ].filter( vis );
						const cols = 'grid' === getComputedStyle( i ).display ? getComputedStyle( i ).gridTemplateColumns.split( ' ' ).filter( Boolean ).length : 1;
						/* جایگاه تبلیغ (.tp-ad-slot) کاشی خالی نیست: در پیش‌نمایش نشانگرش برای همه دیده می‌شود (P5-D650) و یک خانه است */
						const ads = kids.filter( e => e.matches( '.tp-ad-slot' ) ).length;
						return { cols, ads, cards: kids.filter( e => e.matches( '.tp-news-card' ) ).length, empty: kids.filter( e => ! e.matches( '.tp-ad-slot' ) && ( ! e.matches( '.tp-news-card' ) || ! e.querySelector( 'a[href]' ) ) ).length,
							more: !! document.querySelector( '.tp-news-pages a[rel="next"], .tp-news-pages a[href*="/page/"], a[data-tp-load-more]' ) };
					} );
					if ( ! g ) { continue; }
					const tag = `${ w } ${ L || '/fa' } ${ P.k }`;
					t( `${ tag }: بی کاشی خالی یا جانگهدار دیدنی برای عموم`, 0 === g.empty, `خالی ${ g.empty }` );
					/* با جایگاه تبلیغ چیدن ردیف کار موتور مکان‌یابی است (بند ۳، گیت ads-infeed-gate.js)؛ این‌جا فقط شبکۀ بی جایگاه */
					if ( g.more && 0 === g.ads ) { t( `${ tag }: صفحۀ غیرآخر بی ردیف ناقص (${ g.cards } کاشی + ${ g.ads } جایگاه در ${ g.cols } ستون)`, 0 === ( g.cards + g.ads ) % g.cols, `${ g.cards + g.ads } % ${ g.cols }` ); }
				}
				await ctx.close();
			}
		}
	} finally {
		await br.close();
	}
}
async function paging() {
	const MUT = process.argv.includes( '--mut' );
	console.log( `\nدستۀ 56-الف بند ۱ — صفحه‌بندی پس از پالایش${ MUT ? ' (جهش: هستۀ پیش از رفع — باید قرمز شود)' : '' }\n` + '─'.repeat( 78 ) );
	const fs = require( 'fs' ), cp = require( 'child_process' );
	const live = require( 'path' ).join( ENV.wp, 'wp-content/plugins/topersia-render/includes/topersia-g-news.php' );
	const keep = MUT ? fs.readFileSync( live ) : null;
	try {
		if ( MUT ) {
			const ref = process.env.TP_MUT_REF || 'HEAD';
			fs.writeFileSync( live, cp.execFileSync( 'git', [ 'show', ref + ':ToPersia Claud/workspace/plugin/topersia-render/includes/topersia-g-news.php' ], { cwd: ENV.root, maxBuffer: 1 << 26 } ) );
		}
		const p0 = pass, f0 = fail;
		await pagingServer();
		await pagingBrowser();
		if ( MUT ) {
			const red = fail - f0;
			console.log( `  جهش: ${ red } ادعا قرمز شد (${ pass - p0 } سبز)` );
			pass = p0; fail = f0;
			t( 'جهش: هستۀ پیش از رفع دست‌کم یک ادعای صفحه‌بندی را قرمز می‌کند', red > 0, String( red ) );
		}
	} finally {
		if ( keep ) { fs.writeFileSync( live, keep ); }
	}
}

const http = require( 'http' );
const zlib = require( 'zlib' );
const get = ( path, headers ) => new Promise( ( ok, no ) => {
	const t = process.hrtime.bigint();
	const r = http.get( { host: '127.0.0.1', port: 80, path, headers: Object.assign( { Host: ENV.host, 'Accept-Encoding': 'identity' }, headers || {} ) }, res => {
		const parts = [];
		res.on( 'data', c => parts.push( c ) );
		res.on( 'end', () => ok( { code: res.statusCode, headers: res.headers, body: Buffer.concat( parts ), ms: Number( process.hrtime.bigint() - t ) / 1e6 } ) );
	} );
	r.on( 'error', no );
	r.setTimeout( 90000 * X, () => r.destroy( new Error( 'timeout' ) ) );
} );
const med = a => { const s = a.slice().sort( ( x, y ) => x - y ); return s.length ? s[ Math.floor( s.length / 2 ) ] : 0; };
async function perf() {
	const label = ( process.argv.find( a => a.startsWith( '--label=' ) ) || '--label=' ).slice( 8 );
	console.log( `\nبند ۶ — وزن HTML، زمان قطعه، LCP ${ label ? '(' + label + ')' : '' }\n` + '─'.repeat( 78 ) );
	const fragPath = '/geo/wp-content/plugins/topersia-render/fragment.php';
	for ( const P of PERF_PAGES ) {
		const full = await get( P.u );
		const html = full.body.toString( 'utf8' );
		const tiles = ( html.match( /<article class="tp-news-card tp-hub-card">/g ) || [] ).length;
		const pager = /class="tp-news-pages"/.test( html ), more = /data-tp-load-more/.test( html );
		t( `${ P.k }: صفحۀ کامل ۲۰۰`, 200 === full.code, String( full.code ) );
		const wp = [], fr = [];
		let partBytes = 0, fragCode = 0;
		for ( let i = 0; i < 3; i++ ) {
			const p = await get( P.u + '?tp_partial=1' );
			wp.push( p.ms );
			partBytes = p.body.length;
			const f = await get( fragPath + '?u=' + encodeURIComponent( P.u ) );
			fr.push( f.ms );
			fragCode = f.code;
		}
		console.log( `  📏 ${ label } · ${ P.k } · HTML ${ ( full.body.length / 1024 ).toFixed( 1 ) }KB (gzip ${ ( zlib.gzipSync( full.body ).length / 1024 ).toFixed( 1 ) }KB) · کاشی ${ tiles } · صفحه‌بندی ${ pager ? 'دارد' : 'ندارد' } · «نمایش بیشتر» ${ more ? 'دارد' : 'ندارد' } · قطعۀ وردپرس ${ ( partBytes / 1024 ).toFixed( 1 ) }KB میانۀ ${ Math.round( med( wp ) ) }ms · مسیر سبک ${ fragCode } میانۀ ${ Math.round( med( fr ) ) }ms` );
	}
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ] ) {
		const br = await type.launch();
		try {
			for ( const [ w, h ] of [ [ 1440, 900 ], [ 390, 844 ] ] ) {
				for ( const P of PERF_PAGES ) {
					const lcp = [];
					let el = '';
					for ( let i = 0; i < 3; i++ ) {
						const ctx = await br.newContext( { viewport: { width: w, height: h } } );
						await ctx.addInitScript( () => {
							window.__tpLcp = { t: 0, el: '' };
							try {
								new PerformanceObserver( l => { l.getEntries().forEach( e => { window.__tpLcp = { t: e.startTime, el: e.element ? ( e.element.tagName + '.' + String( e.element.className || '' ).split( ' ' )[0] ) : e.url || '' }; } ); } ).observe( { type: 'largest-contentful-paint', buffered: true } );
							} catch ( e ) { /* مرورگر LCP ندارد */ }
						} );
						const page = await ctx.newPage();
						await page.goto( BASE + P.u, { waitUntil: 'load', timeout: 90000 * X } );
						await wait( 1500 );
						const r = await page.evaluate( () => window.__tpLcp );
						lcp.push( r.t );
						el = r.el;
						await ctx.close();
					}
					console.log( `  ⏱️ ${ label } · ${ name } ${ w } · ${ P.k } · LCP میانۀ ${ Math.round( med( lcp ) ) }ms (${ lcp.map( Math.round ).join( '/' ) }) · ${ el }` );
				}
			}
		} finally {
			await br.close();
		}
	}
}

/* ── بند ۱ دستۀ 55-ب (P5-D642) · بند ۱۳ دستۀ 55: ردیف ابزار و لایۀ شناور «فیلتر» ─────────────────────────
 * هاب شهر · /articles/ · /news/ × fa · en · ar (نشانی از سوئیچر WPML، a[hreflang]) × ۳۲۰ · ۳۹۰ · ۷۲۰ · ۷۶۸، کروم و فایرفاکس.
 * هر کنترل دیدنی درون [0, clientWidth] و سند بی سرریز — بسته و باز (برش ریشه سرریز را پنهان می‌کند، پس overflow-h.js نمی‌دید).
 * ≤۷۲۰: لایه absolute و زیر دکمه، شبکۀ دوستونه — جست‌وجو تمام‌عرض، سپس جفت‌ها (استان | شهر … مرتب‌سازی | «نمایش») به ترتیب
 * جهت صفحه؛ هر هدف ≥ ۴۴؛ بالای سندیِ نتیجه‌ها پیش و پس از باز شدن یکی (محتوا جابه‌جا نمی‌شود)؛ فوکوس به نخستین فیلد؛ کلیک
 * درون فیلد و برچسب نمی‌بندد؛ Escape (فوکوس به دکمه) و کلیک بیرون می‌بندند. ۷۶۸: بی دکمه، ظرف دیدنی در جریان.
 * متن «فیلتر» در سه حالت رنگ، بسته · باز · هاور ≥ ۴٫۵ روی زمینۀ واقعی (رنگ‌های نیمه‌شفاف روی نیاکان ترکیب می‌شوند).
 * بی JS: استان برگزیده و شهرِ بیرون از آن ← پالایش استان؛ شهرِ درون آن می‌ماند.
 * کالیبراسیون (کروم ۳۹۰، /city/ fa): هر آشکارساز با CSS یا اسکریپت خراب‌کنندۀ خودش باید بیفتد — وگرنه سبزِ صفحه‌ها بی‌معناست. */
const TB_PAGES  = [ { k: 'هاب شهر', u: '/city/' }, { k: 'مقاله‌ها', u: '/articles/' }, { k: 'خبر', u: '/news/' } ];
const TB_LANGS  = [ 'fa', 'en', 'ar' ];
const TB_WIDTHS = [ 320, 390, 720, 768 ];
const TB_HIT    = 44;
const TB_CAL    = {
	flow:  { k: 'لایه در جریان صفحه ← نتیجه‌ها جابه‌جا', css: '@media (max-width:720px){html.tp-js .tp-page.tp-news-page .tp-hub-more{position:static !important}}' },
	edge:  { k: 'لایه بیرون از لبه ← کنترل بیرون از صفحه', css: '@media (max-width:720px){html.tp-js .tp-page.tp-news-page .tp-hub-more{inset-inline:-60px !important}}' },
	small: { k: 'هدف ۳۰ پیکسلی ← هدف زیر ۴۴', css: '@media (max-width:720px){html .tp-page.tp-news-page .tp-hub-more :is(input,select,button){box-sizing:border-box !important;block-size:30px !important;min-block-size:0 !important;padding-block:0 !important}}' },
	pairs: { k: 'شبکۀ یک‌ستونه ← بی جفت', css: '@media (max-width:720px){html .tp-page.tp-news-page .tp-hub-more{grid-template-columns:minmax(0,1fr) !important}}' },
	split: { k: '«نمایش» سطر جدا ← مرتب‌سازی بی جفت', css: '@media (max-width:720px){html .tp-page.tp-news-page .tp-hub-more > .tp-news-filter__go{grid-column:1 / -1 !important}}' },
	ink:   { k: 'زمینۀ دکمۀ باز هم‌رنگ متن ← «فیلتر» نامرئی', css: 'html .tp-page.tp-news-page .tp-news-filter .tp-hub-more__btn[aria-expanded="true"]{background:var(--tp-primary-text) !important}' },
	focus: { k: 'فوکوس پس از باز شدن برداشته شود ← بی فوکوس نخستین فیلد', js: true },
};
const tbMeasure = page => page.evaluate( HIT => {
	const pan = document.querySelector( '[data-tp-filter-panel]' );
	const f = pan.closest( 'form' ), de = document.documentElement, cw = de.clientWidth;
	const vis = e => {
		const r = e.getBoundingClientRect();
		if ( ! r.width || ! r.height ) { return false; }
		for ( let x = e; x; x = x.parentElement ) { const s = getComputedStyle( x ); if ( 'none' === s.display || 'hidden' === s.visibility ) { return false; } }
		return true;
	};
	const nm = e => e.id || e.name || ( e.className || '' ).toString().split( ' ' )[0] || e.tagName;
	const els = [ ...f.querySelectorAll( 'input:not([type="hidden"]),select,button' ), document.querySelector( '.tp-news-view' ) ].filter( e => e && vis( e ) );
	const out = els.filter( e => { const r = e.getBoundingClientRect(); return r.left < -0.5 || r.right > cw + 0.5; } )
		.map( e => nm( e ) + '@' + Math.round( e.getBoundingClientRect().left ) );
	const parse = s => {
		let m = String( s ).match( /rgba?\(([^)]+)\)/ );
		if ( m ) { const p = m[1].split( /[\s,/]+/ ).filter( Boolean ).map( Number ); return [ p[0], p[1], p[2], p.length > 3 ? p[3] : 1 ]; }
		m = String( s ).match( /color\(srgb ([^)]+)\)/ );
		if ( m ) { const p = m[1].split( /[\s/]+/ ).filter( Boolean ).map( Number ); return [ p[0] * 255, p[1] * 255, p[2] * 255, p.length > 3 ? p[3] : 1 ]; }
		return null;
	};
	const onto = ( a, b ) => { const al = a[3] + b[3] * ( 1 - a[3] ); return al ? [ 0, 1, 2 ].map( i => ( a[ i ] * a[3] + b[ i ] * b[3] * ( 1 - a[3] ) ) / al ).concat( al ) : [ 0, 0, 0, 0 ]; };
	const lum = c => { const v = c.slice( 0, 3 ).map( x => { x /= 255; return x <= 0.03928 ? x / 12.92 : Math.pow( ( x + 0.055 ) / 1.055, 2.4 ); } ); return 0.2126 * v[0] + 0.7152 * v[1] + 0.0722 * v[2]; };
	const btn = f.querySelector( '[data-tp-more-btn]' );
	let ratio = null, pair = '';
	if ( btn && btn.getBoundingClientRect().width ) {
		const layers = [];
		for ( let e = btn; e; e = e.parentElement ) { const c = parse( getComputedStyle( e ).backgroundColor ); if ( c && c[3] > 0 ) { layers.push( c ); if ( c[3] >= 1 ) { break; } } }
		let bg = [ 255, 255, 255, 1 ];
		for ( let i = layers.length - 1; i >= 0; i-- ) { bg = onto( layers[ i ], bg ); }
		const fg = onto( parse( getComputedStyle( btn ).color ) || [ 0, 0, 0, 1 ], bg );
		const a = lum( fg ), b = lum( bg );
		ratio = Math.round( ( Math.max( a, b ) + 0.05 ) / ( Math.min( a, b ) + 0.05 ) * 100 ) / 100;
		pair = getComputedStyle( btn ).color + ' / ' + getComputedStyle( btn ).backgroundColor;
	}
	/* btn از سنجش کنتراست بالا */
	const ps   = getComputedStyle( pan ), pr = pan.getBoundingClientRect();
	const open = ! pan.hidden && 'none' !== ps.display;
	const dir  = getComputedStyle( f ).direction;
	const inner = pan.clientWidth - parseFloat( ps.paddingLeft ) - parseFloat( ps.paddingRight );
	const role = e => e.classList.contains( 'tp-news-filter__f--q' ) ? 'q' : e.classList.contains( 'tp-loc-prov' ) ? 'prov' : e.classList.contains( 'tp-loc' ) ? 'loc'
		: e.classList.contains( 'tp-news-filter__go' ) ? 'go' : e.querySelector( 'select[name="tp_sort"]' ) ? 'sort' : 'f';
	const rows = [];
	[ ...pan.children ].filter( vis ).forEach( e => {
		const r = e.getBoundingClientRect(), k = { n: role( e ), l: r.left, w: r.width, t: Math.round( r.bottom ) };   /* لبۀ پایین: «نمایش» align-self:end دارد */
		const row = rows.find( x => Math.abs( x.t - k.t ) <= 2 );
		if ( row ) { row.k.push( k ); } else { rows.push( { t: k.t, k: [ k ] } ); }
	} );
	const with2 = ( a, b ) => rows.some( r => r.k.some( k => a === k.n ) && r.k.some( k => b === k.n ) );
	const layout = rows.length > 1 && 1 === rows[0].k.length && 'q' === rows[0].k[0].n && rows[0].k[0].w >= inner - 1
		&& rows.slice( 1 ).every( r => ( 2 === r.k.length && ( 'rtl' === dir ? r.k[0].l > r.k[1].l : r.k[0].l < r.k[1].l ) ) || ( 1 === r.k.length && 'f' === r.k[0].n && r.k[0].w >= inner - 1 ) )
		&& with2( 'prov', 'loc' ) && with2( 'sort', 'go' );
	const first = [ ...pan.querySelectorAll( 'input:not([type="hidden"]),select,textarea' ) ].find( vis );
	const small = [ ...pan.querySelectorAll( 'input:not([type="hidden"]),select,button' ), btn ].filter( e => e && vis( e ) && e.getBoundingClientRect().height < HIT - 0.5 )
		.map( e => nm( e ) + '=' + Math.round( e.getBoundingClientRect().height ) );
	const res = document.querySelector( '.tp-news-results' );
	return {
		out, ratio, pair, mode: de.getAttribute( 'data-tp-mode' ) || '', open, dir, layout, small,
		btnVis: !! btn && vis( btn ),
		pos: ps.position,
		gap: btn && vis( btn ) ? Math.round( pr.top - btn.getBoundingClientRect().bottom ) : null,
		cols: 'none' === ps.gridTemplateColumns ? 1 : ps.gridTemplateColumns.split( ' ' ).filter( Boolean ).length,
		rows: rows.map( r => r.k.map( k => k.n + ':' + Math.round( k.l ) + '+' + Math.round( k.w ) ).join( ' ' ) ),
		resTop: res ? Math.round( res.getBoundingClientRect().top + window.scrollY ) : null,
		sw: de.scrollWidth, cw,
		focusFirst: !! first && document.activeElement === first,
		focusBtn: !! btn && document.activeElement === btn,
		firstId: first ? nm( first ) : '—',
	};
}, TB_HIT );
const TB_BTN = '[data-tp-more-btn]', TB_PANEL = '[data-tp-filter-panel]';
const tbOpen = async page => {
	await center( page, TB_BTN );
	await page.click( TB_BTN );
	await page.mouse.move( 1, 1 );   /* اندازۀ «باز» بی هاور */
	await wait( 400 );
};

/* نشانی en و ar هر صفحه از سوئیچر زبان نسخۀ fa (WPML)؛ نبودِ ترجمه «نیست» نوشته می‌شود و می‌افتد، نه حدس */
async function tbUrls() {
	const br = await chromium.launch(), list = [];
	try {
		const page = await br.newPage();
		page.setDefaultTimeout( 20000 * X );
		for ( const P of TB_PAGES ) {
			await page.goto( BASE + P.u, { waitUntil: 'domcontentloaded', timeout: 60000 * X } );
			const alt = await page.evaluate( () => { const o = {}; document.querySelectorAll( 'a.wpml-ls-link[hreflang]' ).forEach( a => { o[ a.getAttribute( 'hreflang' ) ] = a.href; } ); return o; } );
			for ( const L of TB_LANGS ) {
				const u = 'fa' === L ? BASE + P.u : alt[ L ];
				if ( u && 0 === u.indexOf( BASE + '/' ) ) { list.push( { k: P.k + ' ' + L, u } ); } else if ( ! u ) { console.log( `  ℹ️ ${ P.k } ${ L }: ترجمۀ منتشرشده‌ای نیست (سوئیچر پیوند ندارد) — سنجیده نشد` ); } else { t( `${ P.k } ${ L }: نشانی ترجمه از همین سایت`, false, u ); }
			}
		}
	} finally {
		await br.close();
	}
	for ( const L of [ 'en', 'ar' ] ) {
		t( `سوئیچر: دست‌کم یک صفحۀ ${ L } سنجیده می‌شود`, list.some( x => x.k.endsWith( ' ' + L ) ) );
	}
	return list;
}

async function tbCalibrate( br ) {
	for ( const [ key, c ] of Object.entries( TB_CAL ) ) {
		const ctx = await br.newContext( { viewport: { width: 390, height: 844 } } );
		const page = await ctx.newPage();
		page.setDefaultTimeout( 20000 * X );
		try {
			await page.goto( BASE + '/city/', { waitUntil: 'load', timeout: 60000 * X } );
			if ( c.css ) { await page.addStyleTag( { content: c.css } ); }
			if ( c.js ) {
				await page.evaluate( () => document.addEventListener( 'click', e => {
					if ( e.target.closest && e.target.closest( '[data-tp-more-btn]' ) ) { setTimeout( () => { if ( document.activeElement ) { document.activeElement.blur(); } }, 0 ); }
				} ) );
			}
			await page.mouse.move( 1, 1 );
			await wait( 300 );
			const a = await tbMeasure( page );
			await tbOpen( page );
			const o = await tbMeasure( page );
			const caught = {
				flow: null !== a.resTop && Math.abs( o.resTop - a.resTop ) > 1,
				edge: o.out.length > 0,
				small: o.small.length > 0,
				pairs: ! ( 2 === o.cols && o.layout ),
				split: ! ( 2 === o.cols && o.layout ),
				ink: null !== o.ratio && o.ratio < 4.5,
				focus: ! o.focusFirst,
			}[ key ];
			t( `chromium کالیبراسیون — ${ c.k }: آشکارساز می‌گیرد`, caught, `نتیجه ${ a.resTop }→${ o.resTop } · بیرون ${ o.out.join( ' ' ) || '—' } · کوچک ${ o.small.join( ' ' ) || '—' } · ستون ${ o.cols } · نسبت ${ o.ratio } · فوکوس ${ o.focusFirst }` );
		} catch ( e ) { t( `chromium کالیبراسیون ${ key }: بی خطای اجرا`, false, String( e.message ).split( '\n' )[0] ); }
		await ctx.close();
	}
}

async function tbCase( page, tag, w ) {
	await page.mouse.move( 1, 1 );
	await wait( 300 );
	const a = await tbMeasure( page );
	t( `${ tag } بسته: همۀ کنترل‌ها درون صفحه، سند بی سرریز`, 0 === a.out.length && a.sw <= a.cw, ( a.out.join( ' ' ) || '—' ) + ` · ${ a.sw }/${ a.cw }` );
	if ( w > NARROW ) {
		t( `${ tag } پهن: بی دکمۀ «فیلتر»، ظرف دیدنی در جریان`, ! a.btnVis && a.open && 'absolute' !== a.pos, `دکمه ${ a.btnVis } · ${ a.pos }` );
		return;
	}
	t( `${ tag } موبایل: ظرف بسته، دکمه دیدنی و ≥ ۴۴`, ! a.open && a.btnVis && 0 === a.small.length, a.small.join( ' ' ) || '—' );
	await tbOpen( page );
	const o = await tbMeasure( page );
	t( `${ tag } باز: لایۀ absolute زیر دکمه، کنترل‌ها درون صفحه، سند بی سرریز`, o.open && 'absolute' === o.pos && null !== o.gap && o.gap >= 0 && o.gap <= 16 && 0 === o.out.length && o.sw <= o.cw,
		`${ o.pos } · فاصله ${ o.gap } · ${ o.out.join( ' ' ) || '—' } · ${ o.sw }/${ o.cw }` );
	t( `${ tag } باز: دوستونه — جست‌وجو تمام‌عرض، استان | شهر، مرتب‌سازی | «نمایش» (${ o.dir })`, 2 === o.cols && o.layout, o.rows.join( ' / ' ) );
	t( `${ tag } باز: هر هدف ≥ ۴۴`, 0 === o.small.length, o.small.join( ' ' ) || '—' );
	t( `${ tag } باز: نتیجه‌ها جابه‌جا نمی‌شوند`, null !== a.resTop && Math.abs( o.resTop - a.resTop ) <= 1, `${ a.resTop } → ${ o.resTop }` );
	t( `${ tag } باز: فوکوس روی نخستین فیلد`, o.focusFirst, o.firstId );
	const q = TB_PANEL + ' .tp-news-filter__f--q input';
	await center( page, q );
	await page.click( q );
	await page.click( TB_PANEL + ' .tp-news-filter__f--q label' );
	await wait( 250 );
	t( `${ tag } کلیک درون فیلد و برچسب ← باز می‌ماند`, await isOpen( page, TB_PANEL ) );
	await page.fill( q, '' );
	await page.focus( q );
	await page.keyboard.press( 'Escape' );
	await wait( 250 );
	const e = await tbMeasure( page );
	t( `${ tag } Escape ← بسته، فوکوس به «فیلتر»`, ! e.open && e.focusBtn, `باز ${ e.open } · فوکوس دکمه ${ e.focusBtn }` );
	await tbOpen( page );
	await clickOutside( page );
	await wait( 250 );
	t( `${ tag } کلیک بیرون ← بسته`, ! ( await isOpen( page, TB_PANEL ) ) );
}

/* بی JS (GET): استان برگزیده + شهرِ بیرون از آن ← پالایش استان؛ شهرِ درون آن می‌ماند؛ همۀ کنترل‌ها دیدنی و درون صفحه */
async function tbNoJs( br, name ) {
	const ctx = await br.newContext( { viewport: { width: 390, height: 844 }, javaScriptEnabled: false } );
	const page = await ctx.newPage();
	page.setDefaultTimeout( 20000 * X );
	const state = () => page.evaluate( () => ( {
		prov: ( document.querySelector( 'select[name="tp_prov"]' ) || {} ).value || '',
		place: ( document.querySelector( '.tp-loc__keep input[name="tp_place"]' ) || {} ).value || '',
	} ) );
	try {
		for ( const u of [ '/news/', '/city/' ] ) {
			const tag = `${ name } بی JS ${ u }`;
			await page.goto( BASE + u, { waitUntil: 'load', timeout: 60000 * X } );
			const info = await page.evaluate( () => {
				const o = {};
				document.querySelectorAll( 'select[name="tp_prov"] option' ).forEach( op => { o[ op.textContent.trim() ] = op.value; } );
				const l = document.querySelector( '[data-tp-loc-api]' );
				return { ids: o, api: l ? l.getAttribute( 'data-tp-loc-api' ) : '' };
			} );
			const A = info.ids[ 'اصفهان' ];
			const city = async ( q, p ) => {
				const r = await page.request.get( info.api + ( info.api.includes( '?' ) ? '&' : '?' ) + 'q=' + encodeURIComponent( q ) + '&prov=' + p );
				const j = r.ok() ? await r.json() : {};
				return ( ( j.items || [] ).find( i => q === i.name ) || {} ).id || 0;
			};
			const cA = A ? await city( 'اصفهان', A ) : 0;
			let B = 0, cB = 0, nB = '';   /* شهرِ منتشرشدۀ هم‌نامِ استانی دیگر، از داده (شهر «تهران» پیش‌نویس است) — نه نام ثابت */
			for ( const [ n, id ] of Object.entries( info.ids ) ) {
				if ( ! id || id === A || 'اصفهان' === n ) { continue; }
				const c = await city( n, id );
				if ( c ) { B = id; cB = c; nB = n; break; }
			}
			if ( ! A || ! B || ! cA || ! cB || 0 !== info.api.indexOf( BASE + '/' ) ) {
				t( `${ tag }: استان و شهر اصفهان و شهر استانی دیگر پیدا شد`, false, JSON.stringify( { A, B, cA, cB, api: info.api } ) );
				continue;
			}
			await page.goto( BASE + u + '?tp_prov=' + A + '&tp_place=' + cB, { waitUntil: 'load', timeout: 60000 * X } );
			let s = await state();
			t( `${ tag }: استان اصفهان + شهر ${ nB } ← پالایش استان (شهر کنار رفت)`, String( A ) === s.prov && '' === s.place, JSON.stringify( s ) );
			await page.goto( BASE + u + '?tp_prov=' + A + '&tp_place=' + cA, { waitUntil: 'load', timeout: 60000 * X } );
			s = await state();
			t( `${ tag }: استان اصفهان + شهر اصفهان ← شهر می‌ماند`, String( A ) === s.prov && String( cA ) === s.place, JSON.stringify( s ) );
			const m = await tbMeasure( page );
			t( `${ tag }: ظرف دیدنی، همۀ کنترل‌ها درون صفحه، سند بی سرریز`, m.open && 0 === m.out.length && m.sw <= m.cw, ( m.out.join( ' ' ) || '—' ) + ` · ${ m.sw }/${ m.cw }` );
		}
	} catch ( e ) { t( `${ name } بی JS: بی خطای اجرا`, false, String( e.message ).split( '\n' )[0] ); }
	await ctx.close();
}

async function toolbar() {
	console.log( '\nبند ۱ (P5-D642) · بند ۱۳ — ردیف ابزار و لایۀ شناور «فیلتر»: درون صفحه، زیر دکمه، دوستونه، ≥۴۴، بی جابه‌جایی، فوکوس\n' + '─'.repeat( 78 ) );
	const BTN = TB_BTN, openPanel = tbOpen;
	const URLS = await tbUrls();
	for ( const [ name, type ] of [ [ 'chromium', chromium ], [ 'firefox', firefox ] ] ) {
		const br = await type.launch();
		try {
			if ( 'chromium' === name ) { await tbCalibrate( br ); }
			for ( const w of TB_WIDTHS ) {
				const ctx = await br.newContext( { viewport: { width: w, height: 844 } } );
				const page = await ctx.newPage();
				page.setDefaultTimeout( 20000 * X );
				for ( const P of URLS ) {
					const tag = `${ name } ${ w } ${ P.k }`;
					try {
						await page.goto( P.u, { waitUntil: 'load', timeout: 60000 * X } );
						await tbCase( page, tag, w );
					} catch ( e ) { t( `${ tag }: بی خطای اجرا`, false, String( e.message ).split( '\n' )[0] ); }
				}
				await ctx.close();
			}
			await tbNoJs( br, name );
			for ( const mode of [ 'light', 'evening', 'dark' ] ) {
				const ctx = await br.newContext( { viewport: { width: 390, height: 844 } } );
				await ctx.addInitScript( m => { try { window.localStorage.setItem( 'tp-mode', m ); } catch ( e ) { /* بی localStorage */ } }, mode );
				const page = await ctx.newPage();
				page.setDefaultTimeout( 20000 * X );
				const tag = `${ name } 390 ${ mode } «فیلتر»`;
				try {
					await page.goto( BASE + '/city/', { waitUntil: 'load' } );
					await page.mouse.move( 1, 1 );
					await wait( 400 );
					const c = await tbMeasure( page );
					await openPanel( page );
					const o = await tbMeasure( page );
					await page.hover( BTN );
					await wait( 400 );
					const h = await tbMeasure( page );
					const ok = [ c, o, h ].every( m => null !== m.ratio && m.ratio >= 4.5 );
					t( `${ tag }: متن بسته · باز · هاور ≥ ۴٫۵`, mode === c.mode && ok, `حالت ${ c.mode } · بسته ${ c.ratio } · باز ${ o.ratio } · هاور ${ h.ratio } (${ h.pair })` );
				} catch ( e ) { t( `${ tag }: بی خطای اجرا`, false, String( e.message ).split( '\n' )[0] ); }
				await ctx.close();
			}
		} finally {
			await br.close();
		}
	}
}

/* ── بند ۱۳: عکس «پس» فقط fa — فقط با --only=shots (سند است، نه گیت) ──────────────────────────────────
 * هاب شهرها (شبکه و فهرست، انتخاب کاربر در localStorage tp-news-view)، هاب خبر، نوار جست‌وجوی هاب در حالت فوکوس
 * (≤۷۲۰ نخست دکمۀ «فیلتر»)، فوتر × سه حالت رنگ (localStorage tp-mode، همان topersia-display.js) — ۱۴۴۰ · ۷۶۸ · ۳۹۰.
 * ← tests/.screens/55/ (بیرون از git) با index.html. */
async function shots() {
	const fs   = require( 'fs' );
	const path = require( 'path' );
	const DIR  = path.join( __dirname, '.screens', '55' );
	fs.mkdirSync( DIR, { recursive: true } );
	console.log( '\nبند ۱۳ — عکس «پس» fa ← tests/.screens/55/\n' + '─'.repeat( 78 ) );
	const rows = [];
	const br   = await chromium.launch();
	const open = async ( w, h, ls ) => {
		const ctx = await br.newContext( { viewport: { width: w, height: h } } );
		await ctx.addInitScript( kv => { try { Object.keys( kv ).forEach( k => window.localStorage.setItem( k, kv[ k ] ) ); } catch ( e ) { /* بی localStorage */ } }, ls );
		const page = await ctx.newPage();
		page.setDefaultTimeout( 30000 * X );
		return { ctx, page };
	};
	/* بارگذاری تنبل: یک گذر تا پایین و برگشت، سپس آرام‌شدن */
	const settlePage = async page => {
		await page.evaluate( async () => { const H = document.documentElement.scrollHeight; for ( let y = 0; y < H; y += 700 ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 120 ) ); } window.scrollTo( { top: 0, behavior: 'instant' } ); } );
		await wait( 1200 * X );
	};
	const hideFixed = ( page, keep ) => page.evaluate( k => { const kp = k ? document.querySelector( k ) : null; document.querySelectorAll( 'body *' ).forEach( e => { if ( kp && kp.contains( e ) ) { return; } const p = getComputedStyle( e ).position; if ( 'fixed' === p || 'sticky' === p ) { e.style.setProperty( 'display', 'none', 'important' ); } } ); }, keep );
	try {
		for ( const [ w, h ] of VIEWS ) {
			/* هاب شهرها — شبکه و فهرست؛ هاب خبر */
			for ( const [ name, u, ls ] of [ [ 'city-grid', '/city/', { 'tp-news-view': 'grid' } ], [ 'city-list', '/city/', { 'tp-news-view': 'list' } ], [ 'news-hub', '/news/', {} ] ] ) {
				const { ctx, page } = await open( w, h, ls );
				const st = ( await page.goto( BASE + u, { waitUntil: 'load', timeout: 120000 * X } ) ).status();
				await settlePage( page );
				const f = `${ name }-${ w }.png`;
				await page.screenshot( { path: path.join( DIR, f ), fullPage: true } );
				const view = await page.evaluate( () => document.querySelector( '.tp-news-grid--rows' ) ? 'list' : 'grid' );
				rows.push( { name, w, url: BASE + u, f, note: name.startsWith( 'city' ) ? 'نما: ' + view : '' } );
				t( `عکس ${ name } ${ w }: وضعیت ${ st }` + ( name.startsWith( 'city' ) ? `، نما ${ view }` : '' ), 200 === st && ( ! name.startsWith( 'city' ) || name.endsWith( view ) ) );
				await ctx.close();
			}
			/* نوار جست‌وجوی هاب شهرها در حالت فوکوس (کلید Tab نه — کلیک واقعی، مثل کاربر) */
			{
				const { ctx, page } = await open( w, h, {} );
				await page.goto( BASE + '/city/', { waitUntil: 'load', timeout: 120000 * X } );
				await page.waitForSelector( '#tp-hub-q', { state: 'attached' } );
				if ( w <= NARROW && ! ( await isOpen( page, '[data-tp-filter-panel]' ) ) ) {
					await center( page, '[data-tp-more-btn]' );
					await page.click( '[data-tp-more-btn]' );
				}
				await center( page, '#tp-hub-q' );
				await page.click( '#tp-hub-q' );
				await wait( 400 );
				const focus = await page.evaluate( () => document.activeElement && 'tp-hub-q' === document.activeElement.id );
				const f = `search-focus-${ w }.png`;
				await page.screenshot( { path: path.join( DIR, f ) } );
				rows.push( { name: 'search-focus', w, url: BASE + '/city/', f, note: 'فوکوس روی #tp-hub-q' } );
				t( `عکس search-focus ${ w }: فیلد فوکوس دارد`, focus );
				await ctx.close();
			}
			/* فوتر × سه حالت رنگ */
			for ( const mode of [ 'light', 'evening', 'dark' ] ) {
				const { ctx, page } = await open( w, h, { 'tp-mode': mode } );
				await page.goto( BASE + '/', { waitUntil: 'load', timeout: 120000 * X } );
				await settlePage( page );
				await page.evaluate( () => window.scrollTo( { top: document.documentElement.scrollHeight, behavior: 'instant' } ) );
				await wait( 1000 * X );
				const got = await page.evaluate( () => document.documentElement.getAttribute( 'data-tp-mode' ) );
				await hideFixed( page, '[data-elementor-type="footer"]' );
				const foot = await page.$( '[data-elementor-type="footer"]' );
				const f = `footer-${ mode }-${ w }.png`;
				if ( foot ) { await foot.screenshot( { path: path.join( DIR, f ) } ); }
				rows.push( { name: 'footer-' + mode, w, url: BASE + '/', f, note: 'data-tp-mode=' + got } );
				t( `عکس footer ${ mode } ${ w }: حالت صفحه ${ got }`, !! foot && mode === got );
				await ctx.close();
			}
		}
	} finally {
		await br.close();
	}
	const esc = s => String( s ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' );
	fs.writeFileSync( path.join( DIR, 'index.html' ), '<!doctype html><meta charset="utf-8"><title>دستۀ 55 — پس</title><body dir="rtl" style="font-family:sans-serif">'
		+ '<h1>دستۀ 55 — عکس «پس» (fa) · ' + esc( new Date().toISOString() ) + '</h1>'
		+ rows.map( r => '<h2>' + esc( r.name ) + ' · ' + r.w + '</h2><p dir="ltr">' + esc( r.url ) + ( r.note ? ' — ' + esc( r.note ) : '' ) + '</p><a href="' + r.f + '"><img src="' + r.f + '" style="max-width:' + Math.min( r.w, 720 ) + 'px;border:1px solid #888"></a>' ).join( '' ) + '</body>' );
	console.log( '  ← ' + path.join( DIR, 'index.html' ) );
}

( async () => {
	const t0 = Date.now();
	console.log( 'START w55-local ' + new Date().toISOString() );
	if ( ONLY.includes( 'shots' ) ) { await shots(); }   /* فقط با درخواست صریح — عکس «پس» بند ۱۳ */
	if ( want( 'search' ) ) { await search(); }
	if ( want( 'toolbar' ) ) { await toolbar(); }
	if ( want( 'list' ) ) { await list(); }
	if ( want( 'more' ) ) { await more(); }
	if ( want( 'cls' ) ) { await cls(); }
	if ( want( 'paging' ) ) { await paging(); }   /* دستۀ 56-الف بند ۱ */
	if ( ONLY.includes( 'perf' ) ) { await perf(); }   /* فقط با درخواست صریح — سنجش است، نه گیت */
	console.log( '\n' + '─'.repeat( 78 ) + '\nدستۀ 55: ' + pass + ' گذشت · ' + fail + ' افتاد · ' + Math.round( ( Date.now() - t0 ) / 1000 ) + ' ثانیه' );
	console.log( 'END w55-local' );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
