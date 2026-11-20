/**
 * دستۀ 55-ب بند ۲ (P5-D644) — «ذخیرۀ مجدد یک موجودیت در پیشخوان مقدار خالی را نگه می‌دارد».
 *
 * مدیر آزمون (tests/local-login.php tp_test_admin) صفحۀ ویرایش چند موجودیت واقعی fa با «بهترین فصل» خالی را باز می‌کند،
 * بی هیچ دست‌کاری «به‌روزرسانی» را می‌زند و مقدار خام best_season را از پایگاه‌داده می‌خواند.
 * پیش از هر چیز از همان پست‌ها و ترجمه‌هایشان عکس کامل گرفته می‌شود (tests/local-post-snap.php) و در پایان، حتی با خطا،
 * عیناً برمی‌گردد — هیچ ردی نمی‌ماند.
 *
 *   node tests/season-resave-local.js --phase=before   ← کالیبراسیون، پیش از تغییر پیکربندی: باید «بهار» نوشته شود (یعنی آزمون باگ را می‌بیند)
 *   node tests/season-resave-local.js --phase=after    ← پس از تغییر: گزینۀ نخست «—» با مقدار تهی، انتخاب‌شده، و پس از ذخیره همچنان تهی
 *   --ids=135,1944  (اختیاری؛ پیش‌فرض: یک پست از هر نوع در TYPES با local-post-snap.php pick)
 *   --browser=firefox  (پیش‌فرض chromium؛ مرورگر کاربر فایرفاکس است)
 *
 * کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص. فقط topersia.local.
 */
'use strict';

const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );

const WS      = path.resolve( __dirname, '..' );
const PHP     = ENV.php;
const ARGS    = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const PHASE   = ARGS.phase || 'after';
const BROWSER = ARGS.browser || 'chromium';
const TYPES = 'tp_city,tp_province,tp_village,tp_monument,tp_mountain';
const X     = Math.max( 1, parseFloat( process.env.TP_TIMEOUT_X || '1' ) || 1 );

let pass = 0, fail = 0;
function check( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { fail ++; console.log( '  ❌ ' + label + ( note ? '   ← ' + note : '' ) ); }
}
function php( args ) { return execFileSync( PHP, args, { cwd: WS, encoding: 'utf8' } ); }

( async () => {
	if ( ! /topersia\.local/.test( ENV.base ) ) { console.log( 'فقط topersia.local' ); process.exit( 2 ); }
	if ( ! [ 'before', 'after' ].includes( PHASE ) ) { console.log( '--phase=before|after' ); process.exit( 2 ); }

	const ids = ARGS.ids
		? ARGS.ids.split( ',' ).map( Number ).filter( Boolean )
		: Object.values( JSON.parse( php( [ 'tests/local-post-snap.php', 'pick', TYPES ] ) ) );
	if ( ids.length < 3 ) { console.log( 'کمتر از سه موجودیت با فصل خالی پیدا شد' ); process.exit( 2 ); }

	const pre = JSON.parse( php( [ 'tests/local-post-snap.php', 'season', ...ids.map( String ) ] ) );
	for ( const id of ids ) {
		if ( pre[ id ] !== null && pre[ id ] !== '' ) { console.log( 'فصل #' + id + ' خالی نیست: ' + pre[ id ] ); process.exit( 2 ); }
	}

	const snapFile = path.join( os.tmpdir(), 'tp-season-resave-' + process.pid + '.json' );
	console.log( 'START season-resave ' + PHASE + ' · ' + ids.join( ',' ) );
	console.log( php( [ 'tests/local-post-snap.php', 'save', snapFile, ...ids.map( String ) ] ).trim() );

	const login = JSON.parse( php( [ 'tests/local-login.php', 'tp_test_admin' ] ) );
	const browser = await ( 'firefox' === BROWSER ? firefox : chromium ).launch();
	let restored = false, swallowed = 0;
	try {
		const ctx = await browser.newContext( { viewport: { width: 1440, height: 1000 } } );
		await ctx.addCookies( login.cookies );
		const page = await ctx.newPage();

		for ( const id of ids ) {
			console.log( '\n#' + id + '\n' + '─'.repeat( 60 ) );
			await page.goto( ENV.base + '/geo/wp-admin/post.php?post=' + id + '&action=edit&lang=fa', { waitUntil: 'domcontentloaded', timeout: 60000 * X } );
			const sel = page.locator( 'select[name="best_season"]' );
			await sel.waitFor( { state: 'attached', timeout: 30000 * X } );
			const st = await sel.evaluate( s => ( {
				value: s.value,
				first: s.options.length ? { v: s.options[ 0 ].value, t: s.options[ 0 ].textContent.trim() } : null,
				required: s.required,
				n: s.options.length,
			} ) );
			console.log( '  گزینش در صفحه: «' + st.value + '» · گزینۀ نخست «' + ( st.first ? st.first.t : '' ) + '»=' + JSON.stringify( st.first ? st.first.v : null ) + ' · required=' + st.required );

			if ( 'before' === PHASE ) {
				check( 'کالیبراسیون: بی گزینۀ تهی، مرورگر یک فصل را برمی‌گزیند', '' !== st.value && st.first && '' !== st.first.v, JSON.stringify( st ) );
			} else {
				check( 'گزینۀ نخست «—» با مقدار تهی', st.first && '' === st.first.v && '—' === st.first.t, JSON.stringify( st.first ) );
				check( 'مقدار تهی انتخاب‌شده است', '' === st.value, st.value );
				check( 'فیلد اجباری نیست', ! st.required );
			}

			/* بی هیچ دست‌کاری: «به‌روزرسانی» — اگر اعتبارسنجی مرورگر جلوی ارسال را بگیرد، فیلدهای نامعتبر نام برده می‌شوند */
			const invalid = await page.evaluate( () => Array.from( document.querySelectorAll( '#post :invalid' ) ).filter( e => e.name ).map( e => e.name ) );
			check( 'فرم بی فیلد نامعتبر است (ارسال ممکن)', ! invalid.length, invalid.join( ',' ) );
			if ( invalid.length ) { continue; }

			/* Rank Math (seo-by-rank-math/assets/admin/js/classic.js) نخستین submit را نگه می‌دارد، متای خودش را ذخیره می‌کند و
			 * requestSubmit() می‌زند؛ وقتی چیزی برای ذخیره ندارد همان در میکروتسکِ همان submit نخست اجرا می‌شود و مرورگر طبق
			 * مشخصات HTML («firing submission events») آن را نادیده می‌گیرد — کلیک نخست بی‌اثر است و کاربر دوباره می‌زند. */
			await page.locator( '#publish' ).scrollIntoViewIfNeeded();
			const nav  = page.waitForURL( u => /[?&]message=\d+/.test( u.toString() ), { timeout: 90000 * X } );
			const post = page.waitForRequest( r => 'POST' === r.method() && /\/wp-admin\/post\.php$/.test( r.url() ), { timeout: 10000 * X } ).then( () => true, () => false );
			await page.locator( '#publish' ).click();
			if ( ! await post ) {
				swallowed ++;
				console.log( '  ⚠ کلیک نخست «به‌روزرسانی» ارسالی نساخت (Rank Math) — کلیک دوم، همان کاری که کاربر می‌کند' );
				await page.locator( '#publish' ).click();
			}
			await nav;
			const now = JSON.parse( php( [ 'tests/local-post-snap.php', 'season', String( id ) ] ) )[ id ];
			console.log( '  پس از ذخیره در پایگاه‌داده: ' + JSON.stringify( now ) );
			if ( 'before' === PHASE ) {
				check( 'کالیبراسیون: ذخیره فصلی را نوشت که کاربر نگذاشته بود (باگ دیده می‌شود)', now !== null && now !== '', JSON.stringify( now ) );
			} else {
				check( 'پس از ذخیره همچنان تهی', now === null || now === '', JSON.stringify( now ) );
			}
		}
	} catch ( e ) {
		check( 'اجرا بی خطا', false, e.message.split( '\n' )[ 0 ] );
	} finally {
		await browser.close();
		try {
			console.log( '\n' + php( [ 'tests/local-post-snap.php', 'restore', snapFile ] ).trim() );
			restored = true;
		} catch ( e ) {
			console.log( '\n❌ برگرداندن: ' + ( e.stdout || e.message ) );
		}
		check( 'همه‌چیز عیناً برگشت (posts · meta · terms · رابطه · ترجمه · بازنگری)', restored );
		if ( restored ) { fs.rmSync( snapFile, { force: true } ); } else { console.log( 'عکس نگه داشته شد: ' + snapFile ); }
	}

	if ( swallowed ) { console.log( '\n⚠ باید بدانید: کلیک نخست «به‌روزرسانی» در ' + swallowed + ' از ' + ids.length + ' صفحه ارسالی نساخت (Rank Math، شخص ثالث — دست نخورد)' ); }
	console.log( '\nEND season-resave ' + PHASE + ' ' + BROWSER + ' — ' + pass + ' سبز · ' + fail + ' قرمز' );
	process.exit( fail ? 1 : 0 );
} )();
