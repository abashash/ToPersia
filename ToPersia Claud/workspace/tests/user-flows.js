/**
 * جریان‌های کاربر آزمون — ۴٫۳٫۰ پذیرش، روی topersia.local.
 *
 *   node tests/user-flows.js
 *
 *   مهمان      ذخیره، دنبال‌کردن، نظر و «ورود / ثبت‌نام»ِ هوش مصنوعی ← پنجرهٔ
 *              ورود user-core با دلیل. شمار فراخوانی تولید هوش مصنوعی هنگام
 *              بار صفحه و با کلیک: صفر — در مرورگر (admin-ajax)، در سرور
 *              (http-probe.log: هیچ میزبان API هوش مصنوعی)، و در پایگاه‌داده
 *              (کش خلاصه‌ساز دست نخورد). ربات بی‌جاوااسکریپت و درخواست
 *              دست‌ساز مهمان به نقطهٔ تولید هم.
 *   واردشده   کوکی نشستِ تازه از tests/local-login.php — هیچ گذرواژه‌ای.
 *              ذخیره ← ردیف جدول user-core و کارت پنل؛ دنبال‌کردن ← متای کاربر
 *              و «دنبال‌شده‌ها» با خبر و لغو؛ نظر ← ردیف JetReviews با
 *              approved=0، نویسندهٔ کاربر آزمون، شناسهٔ زبان اصلی؛ هوش مصنوعی
 *              ← تولید سمت سرور با نانس، کش مشترک، شمارش سقف، ۴۲۹ روی سقف.
 *
 * پایان: ذخیره و دنبال‌کردن برگردانده می‌شوند؛ خروجی‌هایی که این اجرا ساخت و
 * مصرف امروز به پیش از اجرا (tests/local-ai-reset.php). نظرِ در انتظار تأیید
 * می‌ماند تا مدیر ببیند؛ اجرای بعدی مسیر «ثبت شده است» را می‌سنجد.
 *
 * متن موتور داخلیِ خلاصه‌ساز (افزونهٔ جدا) «یافتهٔ بیرون از دامنه» است:
 * شمرده و گزارش می‌شود، آزمون را نمی‌اندازد.
 * درخواست به topersia.com بسته است. کد خروج: ۰ سالم · ۱ افتادن.
 */

'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );   /* مسیرها و نشانی پایه — tests/env.json (P5-D285) */

const WS    = path.resolve( __dirname, '..' );
const PHP   = ENV.php;
const PROBE = ENV.root + '/http-probe.log';
const PROV  = { url: ENV.base + '/province/isfahan/', id: 99 };
const CITY  = { url: ENV.base + '/city/isfahan/', id: 399 };
const BOT   = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

/* میزبان‌های API که خلاصه‌ساز می‌شناسد (class-tp-ai-api.php) */
const AI_HOSTS = /api\.openai\.com|openai\.azure\.com|api\.anthropic\.com|generativelanguage\.googleapis\.com|api\.deepseek\.com|api\.x\.ai|api\.perplexity\.ai|gateway\.example\.com/;

/* آغاز اجرا — پاک‌سازیِ پایان فقط چیزی را برمی‌دارد که پس از این ساخته شد */
const SINCE = Math.floor( Date.now() / 1000 ) - 5;

let pass = 0, fail = 0, warned = 0;
function check( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { fail ++; console.log( '  ❌ ' + label + ( note ? '   ← ' + note : '' ) ); }
}
/* یافتهٔ بیرون از دامنهٔ این نسخه — شمرده و گزارش می‌شود، ولی آزمون را نمی‌اندازد */
function warn( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { warned ++; console.log( '  ⚠️ ' + label + ( note ? '   ← ' + note : '' ) ); }
}
function info( t ) { console.log( '  · ' + t ); }
function head( t ) { console.log( '\n' + t + '\n' + '─'.repeat( 78 ) ); }

function php( args ) { return execFileSync( PHP, args, { cwd: WS, encoding: 'utf8' } ); }
function state() { return JSON.parse( php( [ 'tests/local-state.php', String( PROV.id ), String( CITY.id ) ] ) ); }

/* درخواستِ تولید هوش مصنوعی از مرورگر */
function isGen( req ) {
	return /admin-ajax\.php/.test( req.url() ) && /(^|&)action=tpas_ai_generate(&|$)/.test( req.postData() || '' );
}
function isAction( res, action ) {
	return /admin-ajax\.php/.test( res.url() ) && new RegExp( '(^|&)action=' + action + '(&|$)' ).test( res.request().postData() || '' );
}

async function context( browser, cookies ) {
	const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
	await ctx.route( /topersia\.com/, r => r.abort() );
	if ( cookies ) { await ctx.addCookies( cookies ); }
	const gen = [];
	ctx.on( 'request', r => { if ( isGen( r ) ) { gen.push( r.postData() ); } } );
	return { ctx, gen };
}

async function load( ctx, url ) {
	const page = await ctx.newPage();
	await page.goto( url, { waitUntil: 'load', timeout: 180000 } );
	/* تا ته صفحه، تا هر چیزِ تنبل (نقشه، تصویر) هم بار شود */
	await page.evaluate( async () => {
		for ( let y = 0; y < document.documentElement.scrollHeight; y += 700 ) {
			window.scrollTo( 0, y );
			await new Promise( r => setTimeout( r, 50 ) );
		}
		window.scrollTo( 0, 0 );
	} );
	await page.waitForTimeout( 600 );
	return page;
}

async function modal( page ) {
	return page.evaluate( () => {
		const d = document.getElementById( 'tp-uc-login' );
		if ( ! d ) { return null; }
		const c = d.querySelector( '[data-tp-auth]' );
		return { open: ! d.hidden, why: ( d.querySelector( '[data-tp-uc-reason]' ) || {} ).textContent || '', back: c ? c.getAttribute( 'data-tp-redirect' ) : '' };
	} );
}

async function loginAsks( page, sel, why, tag ) {
	const el = page.locator( sel ).first();
	await el.scrollIntoViewIfNeeded();
	await el.click();
	await page.waitForTimeout( 250 );
	const m = await modal( page );
	check( `${ tag } ← پنجرهٔ ورود/ثبت‌نام user-core، دلیل «${ why }»، بازگشت به همین صفحه`,
		m && m.open && m.why.indexOf( why ) >= 0 && m.back === page.url(), JSON.stringify( m ) );
	await page.keyboard.press( 'Escape' );
	await page.waitForTimeout( 150 );
}

/* ══ مهمان ══════════════════════════════════════════════════════════ */
async function guest() {

	const s0 = state();
	fs.writeFileSync( PROBE, '' );

	for ( const [ name, type ] of [ [ 'کروم', chromium ], [ 'فایرفاکس', firefox ] ] ) {

		const browser = await type.launch();

		for ( const one of [ PROV, CITY ] ) {

			head( `مهمان — ${ name } — ${ one.url.replace( /^https?:\/\/[^/]+/, '' ) }` );

			const { ctx, gen } = await context( browser );
			const page = await load( ctx, one.url );

			const ai = await page.evaluate( () => {
				const out = document.querySelector( '[data-tp-ai-out]' );
				if ( ! out ) { return null; }
				let data = {};
				try { data = JSON.parse( out.getAttribute( 'data-tp-ai' ) || '{}' ); } catch ( e ) {}
				return {
					nonce: out.hasAttribute( 'data-tp-ai-gen' ),
					keys: Object.keys( data ),
					btns: [ ...document.querySelectorAll( '[data-ai]' ) ].map( b => b.getAttribute( 'data-ai' ) ),
					/* ۴٫۴٫۱ P5-D267 — جملۀ دعوت زیر سرفصل و یک دکمۀ «ثبت‌نام / ورود» در ردیف دکمه‌ها؛ پیوند جدای «ورود» نیست */
					cta: ( document.querySelector( '.tp-ai > p.tp-ai__cta-text' ) || {} ).textContent || '',
					signup: ( document.querySelector( '.tp-ai__actions a.tp-ai__signup[data-tp-login="ai"]' ) || {} ).textContent || '',
					signups: document.querySelectorAll( '.tp-ai a.tp-ai__signup' ).length,
					login: !! document.querySelector( '.tp-ai a.tp-ai__login' ),
					engines: document.querySelectorAll( '.tp-ai__engine[target="_blank"]' ).length
				};
			} );

			check( 'هوش مصنوعی مهمان: فقط خلاصهٔ ذخیره‌شده، بی نانس تولید، بی موتور؛ دعوت زیر سرفصل و یک دکمۀ «ثبت‌نام / ورود»  §۴ · P5-D235 · P5-D267',
				ai && ! ai.nonce && 'summary' === ai.keys.join() && 'summary,copy' === ai.btns.join() && 0 === ai.engines
				&& 'برای دسترسی به امکانات بیشتر هوش مصنوعی ثبت‌نام کنید' === ai.cta.trim()
				&& 'ثبت‌نام / ورود' === ai.signup.trim() && 1 === ai.signups && ! ai.login, JSON.stringify( ai ) );

			/* کلیک روی هر دکمهٔ خروجی — باز هم هیچ درخواست تولیدی */
			for ( const b of await page.$$( '[data-ai]:not([data-ai="copy"])' ) ) { await b.click(); }
			await page.waitForTimeout( 400 );

			await loginAsks( page, '.tp-ai__actions .tp-ai__signup', 'هوش مصنوعی', 'مهمان «ثبت‌نام / ورود» هوش مصنوعی' );

			const rv = await page.evaluate( () => {
				const s = document.getElementById( 'tp-reviews' );
				return s ? { first: ( s.querySelector( '.tp-rate__first' ) || {} ).textContent || '', form: !! s.querySelector( '[data-tp-review-form]' ), invite: !! s.querySelector( '[data-tp-login="review"]' ), agg: /AggregateRating/.test( document.documentElement.innerHTML ) } : null;
			} );
			check( 'نظرها مهمان: «نخستین نظر را شما بنویسید» + دعوت به ورود، بی فرم، بی AggregateRating  §۷ · P5-D234',
				rv && 'نخستین نظر را شما بنویسید' === rv.first.trim() && ! rv.form && rv.invite && ! rv.agg, JSON.stringify( rv ) );

			await loginAsks( page, '#tp-reviews [data-tp-login="review"]', 'نظر', 'مهمان «وارد شوید» نظر' );
			/* «ۀ» یکپارچه (U+06C0) — P5-D248؛ «ذخیره» دیگر زیررشتهٔ «ذخیرۀ» نیست */
			await loginAsks( page, '[data-tp-save]', 'ذخیرۀ این مکان', 'مهمان «ذخیره»' );
			await loginAsks( page, '[data-tp-follow]', 'دنبال', 'مهمان «دنبال‌کردن»' );

			check( 'مهمان: صفر درخواست tpas_ai_generate از مرورگر — بار صفحه و کلیک  §۴', 0 === gen.length, gen.join( ' | ' ) );

			/* درخواست دست‌ساز مهمان به نقطهٔ تولید — خلاصه‌ساز nopriv ندارد */
			if ( 'کروم' === name ) {
				const ajax = await page.evaluate( () => ( window.ToPersiaUserCore || {} ).ajaxUrl || '' );
				const r = await ctx.request.post( ajax, { form: { action: 'tpas_ai_generate', post: String( one.id ), type: 'plan', lang: 'fa' } } );
				const body = ( await r.text() ).trim();
				check( 'مهمانِ دست‌ساز به tpas_ai_generate ← رد (۴۰۰، «0»)، بی تولید  §۴', 400 === r.status() && '0' === body, r.status() + ' ' + body.slice( 0, 80 ) );
			}

			await ctx.close();
		}

		await browser.close();
	}

	/* ربات: بی‌جاوااسکریپت، فقط HTML */
	head( 'ربات — Googlebot، بی‌جاوااسکریپت' );
	for ( const one of [ PROV, CITY ] ) {
		const html = await ( await fetch( one.url, { headers: { 'User-Agent': BOT } } ) ).text();
		check( `ربات ${ one.url.replace( /^https?:\/\/[^/]+/, '' ) }: فقط خروجی ذخیره‌شده، بی نانس تولید`,
			/data-tp-ai-out/.test( html ) && ! /data-tp-ai-gen/.test( html ) && ! /tpas_ai/.test( html ) );
	}

	const s1 = state();
	const lines = fs.readFileSync( PROBE, 'utf8' ).split( '\n' ).filter( Boolean );
	const guestAi = lines.filter( l => /\tguest\t/.test( l ) && AI_HOSTS.test( l ) );
	const hosts = [ ...new Set( lines.map( l => l.split( '\t' )[3] ) ) ];

	head( 'مهمان — شمار فراخوانی API هوش مصنوعی' );
	check( 'سرور: صفر فراخوانی به میزبان API هوش مصنوعی در همهٔ بارهای مهمان و ربات  §۴', 0 === guestAi.length, guestAi.join( ' | ' ) );
	check( 'پایگاه‌داده: کش خلاصه‌ساز دست نخورد، نه جدول نه متا — بی تولید آنی  §۴',
		s0.summaries.rows === s1.summaries.rows && s0.summaries.updated === s1.summaries.updated
		&& s0.summaries.meta === s1.summaries.meta && s0.summaries.meta_max === s1.summaries.meta_max, JSON.stringify( [ s0.summaries, s1.summaries ] ) );
	check( 'سقف‌ها: مهمان از مصرف روزانهٔ سایت چیزی کم نکرد', s0.ai.site_used === s1.ai.site_used, JSON.stringify( [ s0.ai, s1.ai ] ) );
	info( 'میزبان‌های بیرونی که سرور هنگام این بارها صدا زد: ' + ( hosts.join( '، ' ) || 'هیچ' ) );
}

/* ══ واردشده ════════════════════════════════════════════════════════ */
async function member() {

	const login = JSON.parse( php( [ 'tests/local-login.php' ] ) );
	const browser = await chromium.launch();
	const { ctx, gen } = await context( browser, login.cookies );
	const s0 = state();

	head( `واردشده — کاربر آزمون #${ login.user } — ${ PROV.url.replace( /^https?:\/\/[^/]+/, '' ) }` );

	let page = await load( ctx, PROV.url );

	const me = await page.evaluate( () => {
		const out = document.querySelector( '[data-tp-ai-out]' );
		return {
			logged: !! ( window.ToPersiaUserCore && window.ToPersiaUserCore.loggedIn ),
			modal: !! document.getElementById( 'tp-uc-login' ),
			nonce: !! ( out && out.getAttribute( 'data-tp-ai-gen' ) ),
			btns: [ ...document.querySelectorAll( '[data-ai]' ) ].map( b => b.getAttribute( 'data-ai' ) ),
			engines: document.querySelectorAll( '.tp-ai__engine[target="_blank"]' ).length,
			/* ۴٫۴٫۱ P5-D267 — واردشده: نه جملۀ دعوت، نه دکمۀ «ثبت‌نام / ورود» */
			cta: !! document.querySelector( '.tp-ai__cta, .tp-ai__cta-text, .tp-ai__signup' )
		};
	} );
	check( 'واردشده: نانس تولید، سه خروجی، موتورها؛ بی دعوت و بی پنجرهٔ ورود  §۴',
		me.logged && ! me.modal && me.nonce && 'summary,bullets,plan,copy' === me.btns.join() && me.engines > 0 && ! me.cta, JSON.stringify( me ) );

	/* ── ذخیره ── */
	let res = page.waitForResponse( r => isAction( r, 'topersia_save_place' ) );
	await page.click( '[data-tp-save]' );
	let body = await ( await res ).json();
	let s = state();
	check( 'ذخیره ← پاسخ موفق، دکمه فشرده، ردیف جدول user-core با شناسهٔ زبان اصلی  §۵ · P5-D237',
		body.success && 'true' === await page.getAttribute( '[data-tp-save]', 'aria-pressed' ) && s.saved.indexOf( PROV.id ) >= 0, JSON.stringify( { body, saved: s.saved } ) );

	/* ── دنبال‌کردن ── */
	res = page.waitForResponse( r => isAction( r, 'topersia_follow_entity' ) );
	await page.click( '[data-tp-follow]' );
	body = await ( await res ).json();
	s = state();
	check( 'دنبال‌کردن ← پاسخ موفق، «دنبال می‌کنید»، متای کاربر با شناسهٔ زبان اصلی  §۵ · P5-D238',
		body.success && 'true' === await page.getAttribute( '[data-tp-follow]', 'aria-pressed' )
		&& 'دنبال می‌کنید' === ( await page.textContent( '[data-tp-follow-label]' ) ).trim() && s.followed.indexOf( PROV.id ) >= 0,
		JSON.stringify( { body, followed: s.followed } ) );

	/* ── نظر ── */
	const form = await page.$( '#tp-reviews [data-tp-review-form]' );
	if ( form ) {
		const rows = await page.$$( '#tp-reviews .tp-review-form__row' );
		for ( let i = 0; i < rows.length; i ++ ) {
			await page.locator( '#tp-reviews .tp-review-form__row' ).nth( i ).locator( 'input[value="' + ( 4 + ( i % 2 ) ) + '"]' ).check( { force: true } );
		}
		await page.fill( '#tp-reviews [name="title"]', 'آزمون پذیرش ۴٫۳٫۰' );
		await page.fill( '#tp-reviews [name="content"]', 'این نظر را کاربر آزمونِ نسخهٔ محلی برای سنجش جریان ثبت نظر نوشته است؛ نباید منتشر شود.' );
		res = page.waitForResponse( r => isAction( r, 'topersia_submit_review' ) );
		await page.click( '#tp-reviews .tp-review-form__send' );
		body = await ( await res ).json();
		await page.waitForTimeout( 300 );
		s = state();
		const mine = s.reviews.filter( r => r.post_id === PROV.id );
		check( `نظر ← پاسخ موفق، فرم جای خود را به پیام داد، ردیف JetReviews: approved=0، نویسنده #${ login.user }، شناسهٔ اصلی  §۷ · P5-D234`,
			body.success && ! await page.$( '#tp-reviews [data-tp-review-form]' ) && 1 === mine.length && 0 === mine[0].approved && rows.length > 0,
			JSON.stringify( { body, criteria: rows.length, mine } ) );
	} else {
		info( 'نظرِ کاربر آزمون برای این مکان از اجرای پیشین ثبت است — فرم نیامد' );
		s = state();
		const mine = s.reviews.filter( r => r.post_id === PROV.id );
		check( 'نظر: یک ردیف در انتظار تأیید برای کاربر آزمون', 1 === mine.length && 0 === mine[0].approved, JSON.stringify( mine ) );
	}

	/* ── هوش مصنوعی: تولیدِ خروجیِ ذخیره‌نشده ── */
	const aiState = async () => page.evaluate( () => {
		const out = document.querySelector( '[data-tp-ai-out]' );
		return Object.keys( JSON.parse( out.getAttribute( 'data-tp-ai' ) || '{}' ) );
	} );
	const have = await aiState();
	const want = [ 'bullets', 'plan' ].find( k => have.indexOf( k ) < 0 );
	const typeOf = k => ( 'bullets' === k ? 'keypoints' : k ) + ':fa';

	if ( want ) {
		const before = state();
		res = page.waitForResponse( r => isAction( r, 'tpas_ai_generate' ) );
		await page.click( `[data-ai="${ want }"]` );
		body = await ( await res ).json();
		await page.waitForTimeout( 300 );
		const shown = ( await page.textContent( '[data-tp-ai-out] p' ) ).trim();
		const after = state();
		made.push( PROV.id + ':' + typeOf( want ).replace( ':fa', '' ) );
		check( `هوش مصنوعی واردشده: «${ want }» سمت سرور با نانس ساخته شد، نشان داده شد، در کش مشترکِ (پست، نوع، زبان) نشست  §۴`,
			body.success && shown.length > 20 && shown !== 'در حال ساختن…' && after.summaries.posts[ PROV.id ].indexOf( typeOf( want ) ) >= 0,
			JSON.stringify( { body: { success: body.success, engine: body.data && body.data.engine }, shown: shown.slice( 0, 60 ), cache: after.summaries.posts[ PROV.id ] } ) );
		check( 'سقف: یک واحد از مصرف امروزِ کاربر و سایت  §۴',
			after.ai.user_used === before.ai.user_used + 1 && after.ai.site_used === before.ai.site_used + 1, JSON.stringify( [ before.ai, after.ai ] ) );
		/* متنِ موتور داخلیِ خلاصه‌ساز — افزونهٔ جدا، بیرون از دامنهٔ ۴٫۳٫۰ */
		warn( 'متن ساخته‌شده معنادار است — نه متای خام یا آرایهٔ سریال‌شدهٔ PHP (موتور داخلیِ خلاصه‌ساز)',
			! /\ba:\d+:\{|Media Ids|Content Status/.test( shown ), shown.replace( /\s+/g, ' ' ).slice( 0, 160 ) );

		const n = gen.length;
		await page.click( '[data-ai="summary"]' );
		await page.click( `[data-ai="${ want }"]` );
		await page.waitForTimeout( 400 );
		check( 'بار دوم از حافظهٔ همان صفحه — بی درخواست تازه', gen.length === n );
	} else {
		info( 'هر سه خروجی استان در کش است — تولید تازه‌ای نماند' );
	}

	/* ── بار دوبارهٔ صفحه: وضعیت از سرور، خروجی ساخته‌شده از کش ──
	   وضعیتِ ذخیره و دنبال پس از load از topersia_entity_state می‌آید (صفحه
	   کش‌پذیر است)؛ پاسخ آن صبر می‌خواهد، نه زمان ثابت. */
	await page.close();
	const stateSeen = new Promise( ok => {
		const on = r => { if ( isAction( r, 'topersia_entity_state' ) ) { ctx.off( 'response', on ); ok( r ); } };
		ctx.on( 'response', on );
	} );
	page = await load( ctx, PROV.url );
	const st = await ( await Promise.race( [ stateSeen, new Promise( ( ok, no ) => setTimeout( () => no( new Error( 'topersia_entity_state نیامد' ) ), 60000 ) ) ] ) ).json();
	await page.waitForTimeout( 200 );
	const again = await page.evaluate( () => ( {
		save: document.querySelector( '[data-tp-save]' ).getAttribute( 'aria-pressed' ),
		follow: document.querySelector( '[data-tp-follow]' ).getAttribute( 'aria-pressed' ),
		note: ( document.querySelector( '#tp-reviews .tp-review-invite' ) || {} ).textContent || '',
		form: !! document.querySelector( '#tp-reviews [data-tp-review-form]' ),
		shownPublic: [ ...document.querySelectorAll( '#tp-reviews .tp-cmt p' ) ].some( p => /کاربر آزمونِ نسخهٔ محلی/.test( p.textContent ) ),
		ai: Object.keys( JSON.parse( document.querySelector( '[data-tp-ai-out]' ).getAttribute( 'data-tp-ai' ) || '{}' ) )
	} ) );
	check( 'بار دوباره: ذخیره و دنبال‌کردن از سرور فشرده؛ نظر «پس از تأیید مدیر» و منتشرنشده',
		st.success && st.data.saved && st.data.following && 'true' === again.save && 'true' === again.follow
		&& ! again.form && /پس از تأیید مدیر/.test( again.note ) && ! again.shownPublic, JSON.stringify( { state: st, again } ) );
	if ( want ) {
		check( 'بار دوباره: خروجیِ ساخته‌شده از کش مشترک در دادهٔ صفحه', again.ai.indexOf( want ) >= 0, again.ai.join() );
	}

	/* ── سقف روزانه: مصرف روی سقف ← ۴۲۹، بی تولید ── */
	head( `واردشده — سقف روزانه — ${ CITY.url.replace( /^https?:\/\/[^/]+/, '' ) }` );
	const cityPage = await load( ctx, CITY.url );
	const cityHave = await cityPage.evaluate( () => Object.keys( JSON.parse( document.querySelector( '[data-tp-ai-out]' ).getAttribute( 'data-tp-ai' ) || '{}' ) ) );
	const cityWant = [ 'bullets', 'plan' ].find( k => cityHave.indexOf( k ) < 0 );

	if ( cityWant ) {
		const cap = state().ai.caps.user;
		const prev = parseInt( php( [ 'tests/local-ai-used.php', String( cap ) ] ), 10 );
		const before = state();
		res = cityPage.waitForResponse( r => isAction( r, 'tpas_ai_generate' ) );
		await cityPage.click( `[data-ai="${ cityWant }"]` );
		let r = await res;
		body = await r.json();
		await cityPage.waitForTimeout( 300 );
		const after = state();
		check( `روی سقف (${ cap }): ۴۲۹ با پیام سقف کاربر، بی تولید، بی شمارش  §۴`,
			429 === r.status() && body.data && 'user' === body.data.cap && /سقف روزان\u06C0 شما/.test( ( await cityPage.textContent( '[data-tp-ai-out] p' ) ) )
			&& after.summaries.rows === before.summaries.rows && after.ai.user_used === cap && after.ai.site_used === before.ai.site_used,
			JSON.stringify( { status: r.status(), body, before: before.ai, after: after.ai } ) );

		php( [ 'tests/local-ai-used.php', String( prev ) ] );
		res = cityPage.waitForResponse( x => isAction( x, 'tpas_ai_generate' ) );
		await cityPage.click( `[data-ai="${ cityWant }"]` );
		r = await res;
		body = await r.json();
		made.push( CITY.id + ':' + typeOf( cityWant ).replace( ':fa', '' ) );
		const later = state();
		check( 'زیر سقف: همان درخواست ساخته شد و یک واحد شمرده شد',
			200 === r.status() && body.success && later.ai.user_used === prev + 1 && later.summaries.posts[ CITY.id ].indexOf( typeOf( cityWant ) ) >= 0,
			JSON.stringify( { status: r.status(), used: later.ai, cache: later.summaries.posts[ CITY.id ] } ) );
	} else {
		info( 'هر سه خروجی شهر در کش است — سنجش سقف روی این اجرا ممکن نشد' );
	}
	await cityPage.close();

	/* ── پنل حساب: ذخیره‌شده‌ها و دنبال‌شده‌ها ── */
	const account = await page.evaluate( () => window.ToPersiaUserCore.accountUrl );
	head( `واردشده — پنل حساب — ${ account.replace( /^https?:\/\/[^/]+/, '' ) }` );
	const dash = await load( ctx, account );

	await dash.click( '.tp-tab[data-tp-tab="saved"]' );
	await dash.waitForTimeout( 300 );
	const saved = await dash.evaluate( u => {
		const p = document.getElementById( 'tp-panel-saved' );
		return { visible: p && ! p.hidden && p.offsetParent !== null, cards: [ ...p.querySelectorAll( '.tp-saved-card' ) ].map( c => [ ( c.querySelector( 'h3' ) || {} ).textContent, ( c.querySelector( 'a' ) || {} ).href ] ), ok: [ ...p.querySelectorAll( '.tp-saved-card a' ) ].some( a => a.href === u ) };
	}, PROV.url );
	check( 'پنل «ذخیره‌شده‌ها»: کارت همین مکان با پیوند  §۵ · P5-D237', saved.visible && saved.ok, JSON.stringify( saved ) );

	await dash.click( '.tp-tab[data-tp-tab="following"]' );
	await dash.waitForTimeout( 300 );
	const fol = await dash.evaluate( u => {
		const p = document.getElementById( 'tp-panel-following' );
		const card = [ ...p.querySelectorAll( '[data-tp-follow-card]' ) ].find( c => ( c.querySelector( 'h3 a' ) || {} ).href === u );
		return { visible: p && ! p.hidden && p.offsetParent !== null, found: !! card, title: card ? card.querySelector( 'h3' ).textContent.trim() : '', news: card ? [ ...card.querySelectorAll( '.tp-follow-news li a' ) ].map( a => a.textContent.trim() ) : [], unfollow: !! ( card && card.querySelector( '[data-tp-unfollow]' ) ), badge: ( document.querySelector( '[data-tp-follow-count]' ) || {} ).textContent };
	}, PROV.url );
	check( 'پنل «دنبال‌شده‌ها»: همین مکان، آخرین خبرها، دکمهٔ لغو  §۵ · P5-D238',
		fol.visible && fol.found && fol.news.length > 0 && fol.unfollow, JSON.stringify( fol ) );

	res = dash.waitForResponse( r => isAction( r, 'topersia_follow_entity' ) );
	await dash.click( '[data-tp-follow-card] [data-tp-unfollow]' );
	body = await ( await res ).json();
	await dash.waitForTimeout( 300 );
	s = state();
	check( 'لغو از پنل ← کارت برداشته شد، متای کاربر خالی از این مکان',
		body.success && ! ( await dash.$$( '[data-tp-follow-card]' ) ).length && s.followed.indexOf( PROV.id ) < 0, JSON.stringify( { body, followed: s.followed } ) );

	/* ذخیره برگردانده می‌شود — دکمهٔ همان صفحهٔ مکان */
	res = page.waitForResponse( r => isAction( r, 'topersia_remove_saved_place' ) );
	await page.click( '[data-tp-save]' );
	body = await ( await res ).json();
	s = state();
	check( 'برداشتن ذخیره از صفحهٔ مکان ← ردیف جدول برداشته شد',
		body.success && 'false' === await page.getAttribute( '[data-tp-save]', 'aria-pressed' ) && s.saved.indexOf( PROV.id ) < 0, JSON.stringify( { body, saved: s.saved } ) );

	info( 'مصرف امروز کاربر آزمون در این اجرا: ' + s.ai.user_used + ' از ' + s.ai.caps.user + ' · سایت: ' + s.ai.site_used + ' از ' + s.ai.caps.site );
	info( 'نظرِ در انتظار تأیید (می‌ماند تا مدیر ببیند): ' + JSON.stringify( s.reviews ) );

	await ctx.close();
	await browser.close();
	return s0;
}

/* خروجی‌هایی که این اجرا ساخت — پست:نوع — برای پاک‌سازی پایان */
const made = [];

( async () => {
	const base = state();
	try {
		await guest();
		await member();
	} finally {
		/* ذخیره و دنبال را خودِ جریان برگرداند؛ این‌جا خروجی‌های ساخته‌شده و
		   مصرف امروز به پیش از اجرا برمی‌گردند. نظر می‌ماند. */
		const undone = JSON.parse( php( [ 'tests/local-ai-reset.php', String( SINCE ), String( base.ai.user_used ), String( base.ai.site_used ) ].concat( made ) ) );
		head( 'پاک‌سازی' );
		info( 'برداشته شد: ' + ( undone.join( '، ' ) || 'هیچ' ) + ' · مصرف امروز به ' + base.ai.user_used + ' و ' + base.ai.site_used + ' برگشت' );
	}
	console.log( '─'.repeat( 78 ) + `\n  گذشته: ${ pass }   افتاده: ${ fail }   یافتهٔ بیرون از دامنه: ${ warned }` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 1 ); } );
