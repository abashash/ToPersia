/**
 * دستۀ 55 — آزمون مرورگری روی نسخۀ محلی (فقط topersia.local). Chromium و Firefox × ۱۴۴۰ · ۷۶۸ · ۳۹۰.
 *
 *   node tests/w55-local.js [--only=search,…]
 *
 * ▸ search (بند ۴) — جعبۀ جست‌وجوی نوار هاب (/attractions/ · /articles/ · /news/)، combobox مکان و جست‌وجوی هدر وودمارت:
 *   کلیک، تایپ، blur، Escape، لمس (۷۶۸ و ۳۹۰ با hasTouch). هر کنش سه نمونه (۵۰ · ۳۰۰ · ۹۰۰ms): فیلد در DOM و دیدنی.
 *   ظرف «فیلتر» موبایل (≤۷۲۰) فقط با کلیک بیرون یا Escape بسته می‌شود؛ فهرست مکان هم. کالیبراسیون: همان آشکارساز
 *   روی صفحه‌ای که ظرف را با کلیک برمی‌دارد (رفتار پیش از رفع) باید «ناپدید» بگوید، وگرنه سبزِ بقیه معنا ندارد.
 * ▸ list (بند ۵) — نمای فهرستی همۀ هاب‌ها و فهرست‌های خبر/مقاله: بندانگشتی توکن، ستون، دو سطر خلاصه، بلندی ردیف (هدف دسکتاپ < ۱۴۰px).
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
			await clickOutside( page );
			await wait( 250 );
			r = await stays( page, LOC );
			t( `${ tag } مکان: کلیک بیرون ← فهرست بسته، فیلد می‌ماند`, r.ok && ! ( await isOpen( page, LIST ) ), brief( r.last ) );
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
		{ k: 'هاب خبر', u: '/news/', q: '#tp-news-q', panel: false, locScope: '[data-tp-news-filter]' },
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

( async () => {
	const t0 = Date.now();
	console.log( 'START w55-local ' + new Date().toISOString() );
	if ( want( 'search' ) ) { await search(); }
	if ( want( 'list' ) ) { await list(); }
	console.log( '\n' + '─'.repeat( 78 ) + '\nدستۀ 55: ' + pass + ' گذشت · ' + fail + ' افتاد · ' + Math.round( ( Date.now() - t0 ) / 1000 ) + ' ثانیه' );
	console.log( 'END w55-local' );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
