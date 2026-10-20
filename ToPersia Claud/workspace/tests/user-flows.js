/**
 * جریان‌های کاربر آزمون — ۴٫۳٫۰ پذیرش، روی topersia.local.
 *
 *   node tests/user-flows.js
 *   TP_UF_BROWSER=firefox node tests/user-flows.js   ← جریان واردشده و نانس مهمان در فایرفاکس (پیش‌فرض کروم؛ ۱۹ب)
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
const MAIN = 'firefox' === process.env.TP_UF_BROWSER ? firefox : chromium;   /* مرورگر جریان واردشده — ۱۹ب */
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

			/* P5-D382: مهمان هم هر سه خروجی ذخیره‌شده (خلاصه · نکات · برنامه) را می‌بیند؛ دکمهٔ هر نوع فقط با خروجی؛ پیوند «موتور:» هیچ */
			check( 'هوش مصنوعی مهمان: خروجی‌های ذخیره‌شده (هر نوعی که هست، خلاصه اول) با دکمهٔ همان نوع — بی نانس، بی موتور، بی دعوت و بی دکمهٔ ثبت‌نام (P5-D382 · P5-D358)؛ نیست ← هم بی‌خروجی  §۴',
				! ai || ( ! ai.nonce && 'summary' === ai.keys[ 0 ] && ai.keys.every( k => [ 'summary', 'bullets', 'plan' ].includes( k ) ) && ai.keys.concat( 'copy' ).join() === ai.btns.join() && 0 === ai.engines
				&& '' === ai.cta.trim() && '' === ai.signup.trim() && 0 === ai.signups && ! ai.login ), JSON.stringify( ai ) );

			/* کلیک روی هر دکمهٔ خروجی — باز هم هیچ درخواست تولیدی */
			for ( const b of await page.$$( '[data-ai]:not([data-ai="copy"])' ) ) { await b.click(); }
			await page.waitForTimeout( 400 );

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
			! /data-tp-ai-gen/.test( html ) && ! /tpas_ai/.test( html ) && ! /tp-ai__signup/.test( html ) );
	}

	/* ── P5-D374: نانس ورود در HTML مهمان نیست؛ هنگام «ارسال کد» از REST بی‌کش می‌آید ── */
	head( 'مهمان — نانس ورود از REST بی‌کش' );
	{
		const browser = await MAIN.launch();
		const { ctx } = await context( browser );
		const page = await load( ctx, PROV.url );
		const n0 = await page.evaluate( () => ( { nonce: window.ToPersiaUserCore && window.ToPersiaUserCore.nonce, url: ( window.ToPersiaUserCore && window.ToPersiaUserCore.nonceUrl ) || '', tpas: ( window.tpasFront && window.tpasFront.nonce ) } ) );
		check( 'HTML مهمان: ToPersiaUserCore.nonce و tpasFront.nonce تهی؛ نشانی REST نانس هست', '' === n0.nonce && '' === n0.tpas && /uc-nonce/.test( n0.url ), JSON.stringify( n0 ) );
		const seen = [];
		page.on( 'request', r => {
			const u = r.url();
			if ( /uc-nonce/.test( u ) ) { seen.push( 'GET nonce' ); }
			if ( /admin-ajax/.test( u ) && 'POST' === r.method() && /topersia_send_otp/.test( r.postData() || '' ) ) { const m = ( r.postData() || '' ).match( /nonce=([0-9a-f]+)/ ); seen.push( 'POST send_otp nonce=' + ( m ? m[ 1 ].length : 0 ) ); }
		} );
		await page.evaluate( () => window.ToPersiaUserCore && window.ToPersiaUserCore.openLogin && window.ToPersiaUserCore.openLogin( { reason: 'review' } ) );
		await page.waitForSelector( '#tp-uc-login:not([hidden]) #tp-mobile', { timeout: 60000 } );
		await page.fill( '#tp-uc-login #tp-mobile', '09000000099' );
		await page.click( '#tp-uc-login #tp-send-otp' );
		await page.waitForTimeout( 6000 );
		check( 'پیش از ارسال کد، نانس از REST گرفته می‌شود و POST با نانس پرمقدار می‌رود', 2 === seen.length && 'GET nonce' === seen[ 0 ] && ( ( seen[ 1 ] || '' ).match( /^POST send_otp nonce=(\d+)$/ ) || [ 0, 0 ] )[ 1 ] >= 8, JSON.stringify( seen ) );
		await ctx.close();
		await browser.close();
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

/* ══ ورود ایمیلی — کاربر واقعی در مرورگر (۱٫۱۴٫۰ · P5-D391) ════════════
   صفحهٔ انگلیسی (پیش‌فرض: زبانهٔ ایمیل). هیچ نامه‌ای نمی‌رود (ثابت‌های SMTP محلی نیستند)؛ کد از نامه خوانده نمی‌شود:
   توکن از پاسخ AJAX مرورگر و کد شناخته‌شده با tests/local-email-code.php (HMAC) روی همان توکن می‌نشیند.
   سناریو: زبانه و فلش‌ها · ارسال · کد غلط · منقضی · سقف · ثبت‌نام · ورود دوباره همان حساب. حساب در پایان پاک می‌شود. */
async function emailFlow() {
	const ec   = ( ...a ) => JSON.parse( php( [ 'tests/local-email-code.php' ].concat( a.map( String ) ) ) );
	const uniq = 'tpuf' + Date.now().toString( 36 );
	const mail = uniq + '@example.org';
	const t0   = Math.floor( Date.now() / 1000 ) - 2;
	const browser = await MAIN.launch();
	try {
		head( 'ورود ایمیلی — مهمان در مرورگر (en)' );
		const EN = ENV.base + '/en/province/isfahan/';
		const send = async ( page ) => {
			const resP = page.waitForResponse( r => isAction( r, 'topersia_email_code_send' ), { timeout: 60000 } );
			await page.click( '#tp-uc-login #tp-send-email' );
			const res = await resP;
			return { status: res.status(), body: await res.json().catch( () => ( {} ) ) };
		};
		const verify = async ( page, code ) => {
			await page.fill( '#tp-uc-login #tp-otp', code );
			const resP = page.waitForResponse( r => isAction( r, 'topersia_email_code_verify' ), { timeout: 60000 } );
			await page.click( '#tp-uc-login #tp-verify-otp' );
			const res = await resP;
			await page.waitForTimeout( 400 );
			return { status: res.status(), msg: await page.textContent( '#tp-uc-login #tp-auth-message' ) };
		};
		const openAt = async ( ctx ) => {
			const page = await load( ctx, EN );
			await page.evaluate( () => window.ToPersiaUserCore.openLogin( { reason: 'review' } ) );
			await page.waitForSelector( '#tp-uc-login:not([hidden]) #tp-email', { timeout: 60000 } );
			return page;
		};

		let { ctx } = await context( browser );
		let page = await openAt( ctx );
		const ui = await page.evaluate( () => {
			const q = s => document.querySelector( '#tp-uc-login ' + s );
			return { sel: q( '[aria-selected="true"]' ).getAttribute( 'data-tp-method' ), mobileHidden: q( '#tp-auth-panel-mobile' ).hidden, dir: q( '#tp-email' ).getAttribute( 'dir' ),
				focus: document.activeElement && document.activeElement.id, tablist: !! q( '[role="tablist"][aria-label]' ), code: q( '#tp-otp' ).getAttribute( 'autocomplete' ) };
		} );
		check( 'صفحهٔ en: زبانهٔ ایمیل پیش‌فرض، پنل موبایل پنهان، ورودی ایمیل dir=ltr و فوکوس روی آن؛ الگوی ARIA tabs؛ کد one-time-code',
			'email' === ui.sel && ui.mobileHidden && 'ltr' === ui.dir && 'tp-email' === ui.focus && ui.tablist && 'one-time-code' === ui.code, JSON.stringify( ui ) );
		await page.focus( '#tp-uc-login #tp-auth-tab-email' );
		await page.keyboard.press( 'ArrowLeft' );
		const k1 = await page.evaluate( () => document.querySelector( '#tp-uc-login [aria-selected="true"]' ).getAttribute( 'data-tp-method' ) );
		await page.keyboard.press( 'ArrowRight' );
		const k2 = await page.evaluate( () => document.querySelector( '#tp-uc-login [aria-selected="true"]' ).getAttribute( 'data-tp-method' ) );
		check( 'فلش‌ها جهت صفحه را می‌گیرند (ltr: چپ ← موبایل، راست ← ایمیل)  P5-D243', 'mobile' === k1 && 'email' === k2, k1 + '/' + k2 );

		await page.fill( '#tp-uc-login #tp-email', mail );
		let s = await send( page );
		const shown = await page.textContent( '#tp-uc-login #tp-auth-message' );
		check( 'ارسال کد: ۲۰۰ با توکن و پیام یکسان «اگر نشانی درست باشد…»؛ جعبهٔ کد باز شد', 200 === s.status && s.body.success && 32 === ( s.body.data.token || '' ).length && /If the address is valid/.test( shown || '' )
			&& await page.isVisible( '#tp-uc-login #tp-otp' ), JSON.stringify( s ) );
		ec( 'set', s.body.data.token, '135790' );
		let v = await verify( page, '135792' );
		check( 'کد غلط: ۴۲۲ و پیام خطا؛ هنوز مهمان', 422 === v.status && /incorrect/i.test( v.msg || '' ) && ! ( await ctx.cookies() ).some( c => /^wordpress_logged_in/.test( c.name ) ), JSON.stringify( v ) );
		ec( 'expire', s.body.data.token );
		v = await verify( page, '135790' );
		check( 'کد منقضی: ۴۱۰ و پیام «منقضی»', 410 === v.status && /expired/i.test( v.msg || '' ), JSON.stringify( v ) );

		ec( 'cap', mail );
		s = await send( page );
		check( 'سقف هر نشانی: ۴۲۹ با پیام «بعداً دوباره تلاش کنید»، بی توکن', 429 === s.status && ! s.body.success && /later/i.test( ( s.body.data || {} ).message || '' ), JSON.stringify( s ) );
		ec( 'cleanup', uniq );

		s = await send( page );
		ec( 'set', s.body.data.token, '246801' );
		await page.fill( '#tp-uc-login #tp-otp', '246801' );
		const nav = page.waitForNavigation( { timeout: 120000 } ).catch( () => null );
		await page.click( '#tp-uc-login #tp-verify-otp' );
		await nav;
		const logged = ( await ctx.cookies() ).some( c => /^wordpress_logged_in/.test( c.name ) );
		const st1 = ec( 'state', mail, t0 );
		check( 'ثبت‌نام با ایمیل: نشست ساخته شد و به همان صفحه برگشت؛ حساب مشترک تازه با نام خنثی و زمان تأیید', logged && /\/en\/province\/isfahan\//.test( page.url() ) && st1.user > 0 && 'subscriber' === st1.role && st1.neutral && st1.verified, JSON.stringify( [ logged, page.url(), st1 ] ) );
		const html = await page.content();
		check( 'صفحهٔ واردشده: ایمیل کاربر در HTML نیست (نام عمومی، JSON-LD، پنل)', ! html.includes( uniq ), '' );
		await ctx.close();

		( { ctx } = await context( browser ) );
		page = await openAt( ctx );
		await page.fill( '#tp-uc-login #tp-email', mail );
		ec( 'cool', mail );
		s = await send( page );
		ec( 'set', s.body.data.token, '112358' );
		await page.fill( '#tp-uc-login #tp-otp', '112358' );
		const nav2 = page.waitForNavigation( { timeout: 120000 } ).catch( () => null );
		await page.click( '#tp-uc-login #tp-verify-otp' );
		await nav2;
		const st2 = ec( 'state', mail, t0 );
		check( 'ورود دوباره با همان ایمیل ← همان حساب', ( await ctx.cookies() ).some( c => /^wordpress_logged_in/.test( c.name ) ) && st2.user === st1.user, JSON.stringify( [ st1.user, st2.user ] ) );
		check( 'هیچ ایمیلی از این ماشین نرفت: لاگ «فرستاده شد» صفر (ثبت‌شده‌ها: ' + st2.captured + ')', 0 === st2.sent, JSON.stringify( st2 ) );
		await ctx.close();
	} finally {
		const rm = ec( 'cleanup', uniq );
		info( 'حساب ایمیلی آزمون پاک شد: ' + rm.removed );
		await browser.close();
	}
}

/* ══ واردشده ════════════════════════════════════════════════════════ */
async function member() {

	const login = JSON.parse( php( [ 'tests/local-login.php' ] ) );
	const browser = await MAIN.launch();
	const { ctx, gen } = await context( browser, login.cookies );
	info( 'مرورگر جریان واردشده: ' + ( MAIN === firefox ? 'فایرفاکس' : 'کروم' ) );
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
	check( 'واردشده: بی نانس تولید (P5-D358)؛ دکمه‌ها فقط برای خروجی ذخیره‌شده؛ هیچ پیوند «موتور:» (P5-D382)؛ بی دعوت و بی پنجرهٔ ورود  §۴',
		me.logged && ! me.modal && ! me.nonce && me.btns.every( b => [ 'summary', 'bullets', 'plan', 'copy' ].includes( b ) ) && 0 === me.engines && ! me.cta, JSON.stringify( me ) );

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

	/* ── هوش مصنوعی: واردشدهٔ عادی هم چیزی نمی‌سازد (P5-D358) — کلیک روی هر دکمه، صفر درخواست tpas_ai_generate ── */
	const n0 = gen.length;
	for ( const b of await page.$$( '[data-ai]:not([data-ai="copy"])' ) ) { await b.click(); }
	await page.waitForTimeout( 400 );
	check( 'هوش مصنوعی واردشده (غیرمدیر): صفر درخواست tpas_ai_generate — بار صفحه و کلیک روی هر دکمه  P5-D358', gen.length === n0 && 0 === n0, gen.join( ' | ' ) );

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
	} ) );
	check( 'بار دوباره: ذخیره و دنبال‌کردن از سرور فشرده؛ نظر «پس از تأیید مدیر» و منتشرنشده',
		st.success && st.data.saved && st.data.following && 'true' === again.save && 'true' === again.follow
		&& ! again.form && /پس از تأیید مدیر/.test( again.note ) && ! again.shownPublic, JSON.stringify( { state: st, again } ) );

	/* سقف روزانهٔ هر کاربر بی‌اثر است و تولید فقط مدیر (P5-D358) — سنجش سقف ۴۲۹ برداشته شد؛ گیت غیرمدیر در assert-440 (۴۰۳) */

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

	/* ── عکس نمایه: بارگذاری از پنل، بی gravatar — P5-D377 ── */
	head( 'عکس نمایه — بارگذاری محلی، بی gravatar' );
	{
		const avDir = path.join( ENV.wp, 'wp-content', 'uploads', 'topersia-avatars' );
		const files = () => ( fs.existsSync( avDir ) ? fs.readdirSync( avDir ).filter( x => /\.webp$/.test( x ) ) : [] );
		php( [ 'tests/local-av-reset.php' ] );   /* سقف ده بارگذاری در ساعت — اجرای پشت‌سرهم آزمون */
		const before = files().length;
		const grav = [];
		ctx.on( 'request', r => { if ( /gravatar/i.test( r.url() ) ) { grav.push( r.url() ); } } );
		const sample = path.join( require( 'os' ).tmpdir(), 'tp-avatar-flow.jpg' );
		php( [ '-r', '$i=imagecreatetruecolor(500,300);imagefilledrectangle($i,0,0,500,300,imagecolorallocate($i,30,120,90));imagejpeg($i,$argv[1],90);', sample ] );
		page = await load( ctx, ENV.base + '/account/' );
		const ptab = page.locator( '[data-tp-tab="profile"]' ).first();
		if ( await ptab.count() ) { await ptab.click(); }
		await page.waitForSelector( '#tp-avatar-file', { state: 'attached', timeout: 60000 } );
		const src0 = await page.getAttribute( '.tp-avatar-big', 'src' );
		check( 'پیش از بارگذاری: شمایل خنثی از افزونه (نه gravatar)، ابعاد صریح', /assets\/img\/avatar-/.test( src0 ) && '96' === await page.getAttribute( '.tp-avatar-big', 'width' ) && '96' === await page.getAttribute( '.tp-avatar-big', 'height' ), src0 );
		await page.setInputFiles( '#tp-avatar-file', sample );
		await page.waitForFunction( () => /topersia-avatars\/[a-f0-9]{24}-96\.webp/.test( ( document.querySelector( '.tp-avatar-big' ) || {} ).src || '' ), null, { timeout: 90000 } );
		check( 'پس از بارگذاری: نشانی عکس WebP در uploads/topersia-avatars با نام تصادفی', files().length === before + 2, files().join( ',' ) );
		const src1 = await page.getAttribute( '.tp-avatar-big', 'src' );
		const ok = await page.evaluate( async u => { const r = await fetch( u, { cache: 'no-store' } ); return [ r.status, r.headers.get( 'content-type' ) ]; }, src1 );
		check( 'فایل عکس خوانده می‌شود (image/webp)', 200 === ok[ 0 ] && /image\/webp/.test( ok[ 1 ] ), JSON.stringify( ok ) );
		await page.click( '#tp-avatar-remove' );
		await page.waitForFunction( () => /assets\/img\/avatar-/.test( ( document.querySelector( '.tp-avatar-big' ) || {} ).src || '' ), null, { timeout: 60000 } );
		check( 'حذف عکس: فایل‌ها پاک شد و شمایل خنثی برگشت', files().length === before, files().join( ',' ) );
		check( 'هیچ درخواستی به gravatar رفت', 0 === grav.length, grav.join( ' ' ) );
		fs.unlinkSync( sample );
	}
	/* ── حریم خصوصی: درخواست صدور و حذف از پنل، با تأیید دومرحله‌ای — P5-D375 ── */
	head( 'حریم خصوصی — پنل · تنظیمات' );
	const dr = () => JSON.parse( php( [ 'tests/local-dr.php' ] ) );
	await php( [ 'tests/local-dr.php', 'reset' ] );
	page = await load( ctx, ENV.base + '/account/' );
	const tab = page.locator( '[data-tp-tab="settings"]' ).first();
	if ( await tab.count() ) { await tab.click(); }
	await page.waitForSelector( '[data-tp-dr="export"]', { state: 'attached', timeout: 60000 } );
	check( 'پنل: دکمه‌های «درخواست نسخه از داده‌ها» و «درخواست حذف حساب» هست و پیش از کلیک درخواستی نیست',
		1 === await page.locator( '[data-tp-dr="export"]' ).count() && 1 === await page.locator( '[data-tp-dr="erase"]' ).count() && ! dr().export && ! dr().erase );
	await page.click( '[data-tp-dr="export"]' );
	await page.waitForSelector( '#tp-dr-confirm:not([hidden])', { timeout: 60000 } );
	check( 'گام اول فقط پنجرهٔ تأیید را باز می‌کند؛ هنوز درخواستی ثبت نشده', ! dr().export );
	await page.click( '#tp-dr-cancel' );
	check( 'انصراف: پنجره بسته و بی درخواست', await page.locator( '#tp-dr-confirm' ).isHidden() && ! dr().export );
	/* ۱۹ب — بی انتظار زمانی و بی تکیه بر کلاس پیام (پیام پیش از پاسخ «success» تهی می‌گیرد — علت ناپایداری):
	   منتظر خودِ پاسخ گام «confirm» همان نوع درخواست می‌مانیم، سپس دکمهٔ غیرفعال. */
	const drStep = ( type, step ) => r => /admin-ajax\.php/.test( r.url() ) && 'POST' === r.request().method()
		&& /(^|&)action=topersia_data_request(&|$)/.test( r.request().postData() || '' ) && new RegExp( '(^|&)type=' + type + '(&|$)' ).test( r.request().postData() || '' )
		&& new RegExp( '(^|&)step=' + step + '(&|$)' ).test( r.request().postData() || '' );
	const drRequest = async type => {
		const [ startRes ] = await Promise.all( [ page.waitForResponse( drStep( type, 'start' ), { timeout: 90000 } ), page.click( '[data-tp-dr="' + type + '"]' ) ] );
		const start = await startRes.json();
		await page.waitForSelector( '#tp-dr-confirm:not([hidden])', { timeout: 60000 } );
		const [ confRes ] = await Promise.all( [ page.waitForResponse( drStep( type, 'confirm' ), { timeout: 90000 } ), page.click( '#tp-dr-confirm-btn' ) ] );
		const conf = await confRes.json();
		await page.waitForFunction( t => { const b = document.querySelector( '[data-tp-dr="' + t + '"]' ); return b && b.disabled && document.getElementById( 'tp-dr-confirm' ).hidden; }, type, { timeout: 60000 } );
		return { start: !! start.success, conf: !! conf.success, msg: ( conf.data && conf.data.message ) || '' };
	};
	const ex = await drRequest( 'export' );
	check( 'گام دوم: درخواست صدور داده ثبت شد (پاسخ موفق، دکمه غیرفعال)', ex.start && ex.conf && dr().export && await page.locator( '[data-tp-dr="export"]' ).isDisabled() && /success/.test( await page.getAttribute( '#tp-dr-message', 'class' ) ), JSON.stringify( [ ex, dr() ] ) );
	const er = await drRequest( 'erase' );
	check( 'گام دوم: درخواست حذف حساب ثبت شد؛ حساب آزمون هنوز هست (اجرا با مدیر)', er.start && er.conf && dr().erase && JSON.parse( php( [ 'tests/local-state.php' ] ) ).user > 0, JSON.stringify( [ er, dr() ] ) );
	const rm = JSON.parse( php( [ 'tests/local-dr.php', 'reset' ] ) );
	info( 'درخواست‌های آزمون برداشته شد: ' + rm.removed );
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
		await emailFlow();
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
