/**
 * فهرست شاهد «بیرون از کش» — SC2 · P5-D372  (فقط topersia.local؛ فقط می‌خواند)
 *
 *   node tests/pagecache-witness.js
 *
 * HTML سرور استان و شهر (fa · en) برای مهمان و برای کاربر آزمون واردشده را می‌گیرد و می‌سنجد:
 *   ۱ هر داده‌ی زنده/شخصی (هوا، کیفیت هوا، «برای شما»، ذخیره‌ها، دنبال‌شده‌ها، نظر در انتظار تأیید) در HTML مهمان نیست؛
 *     پس از سمت کاربر با REST/JS بار می‌شود و کش مهمان آن را نگه نمی‌دارد
 *   ۲ HTML مهمان با HTML کاربر واردشده چه فرقی دارد (فهرست «هر چه در HTML هست»، تغییر نمی‌دهد)
 */
'use strict';
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );
const { execFileSync } = require( 'child_process' );
const path = require( 'path' );

( async () => {
	const login = JSON.parse( execFileSync( ENV.php || 'php', [ path.join( __dirname, 'local-login.php' ) ], { encoding: 'utf8' } ) );
	const b = await chromium.launch();
	const guest = await b.newContext(); const user = await b.newContext(); await user.addCookies( login.cookies );
	const get = async ( ctx, u ) => ( await ctx.request.get( ENV.base + u, { timeout: 240000 } ) ).text();
	const MARK = {
		'ردیف‌های هوا با عدد دما (°)': /tp-wx__day(?![^"]*is-load)[^>]*>[^<]*\d+\s*°/,
		'اسکلت هوا (تا پاسخ REST)': /tp-wx__day tp-is-load/,
		'آدرس REST هوا': /\/wp-json\/topersia\/v1\/weather/,
		'کیفیت هوا (AQI) با عدد': /tp-air[^>]*>[^<]*\d{1,3}\s*(?:<|AQI)/,
		'«برای شما»': /برای شما/,
		'دکمهٔ ذخیره‌ای که حالت «ذخیره‌شده» دارد': /data-tp-saved="1"|aria-pressed="true"[^>]*data-tp-save/,
		'دکمهٔ دنبال‌کردنی که حالت «دنبال‌شده» دارد': /data-tp-followed="1"|aria-pressed="true"[^>]*data-tp-follow/,
		'نظر در انتظار تأیید': /در انتظار تأیید/,
		'nonce وردپرس': /_wpnonce|"nonce":"[0-9a-f]{8,}/,
		'نام کاربر واردشده': /tp_test_430|display_name/,
	};
	const rows = [];
	for ( const u of [ '/province/isfahan/', '/en/province/isfahan/', '/city/isfahan/', '/en/city/isfahan/' ] ) {
		const g = await get( guest, u ); const l = await get( user, u );
		const found = Object.entries( MARK ).filter( ( [ , re ] ) => re.test( g ) ).map( ( [ k ] ) => k );
		const gl = new Set( g.split( '\n' ).map( x => x.trim() ).filter( Boolean ) ); const ul = new Set( l.split( '\n' ).map( x => x.trim() ).filter( Boolean ) );
		const onlyUser = [ ...ul ].filter( x => ! gl.has( x ) ); const onlyGuest = [ ...gl ].filter( x => ! ul.has( x ) );
		rows.push( { u, guest: g.length, user: l.length, found, onlyUser: onlyUser.length, onlyGuest: onlyGuest.length, sampleUser: onlyUser.slice( 0, 8 ).map( x => x.replace( /\s+/g, ' ' ).slice( 0, 110 ) ) } );
	}

	/* ── P5-D374: HTML مهمان هیچ nonce از کد ما ندارد (استان · شهر · جاذبه × fa · en · ar) ── */
	const urls = JSON.parse( execFileSync( ENV.php || 'php', [ path.join( __dirname, 'local-urls.php' ) ], { encoding: 'utf8' } ) );
	const OURS = /\b(tpasFront|ToPersiaUserCore|ToPersiaDisplaySync|TopersiaG|ToPersiaG|tpasAdmin|ToPersiaRender)\b[^;]{0,600}?["']?nonce["']?\s*[:=]\s*["'][0-9a-fA-F]{6,}["']/;
	const THIRD = /(ElementorProFrontendConfig|elementorFrontendConfig|rankMath|wpApiSettings)[^;]{0,900}?["']nonce["']\s*:\s*["'][0-9a-fA-F]{6,}["']/g;
	let bad = 0, checked = 0; const third = {};
	for ( const [ page, langs ] of Object.entries( urls ) ) {
		for ( const [ lang, u ] of Object.entries( langs ) ) {
			const html = await ( await guest.request.get( u, { timeout: 240000 } ) ).text(); checked ++;
			const m = html.match( OURS );
			if ( m ) { bad ++; console.log( '  ❌ ' + page + ' ' + lang + ': nonce از کد ما در HTML مهمان — ' + m[ 0 ].replace( /[0-9a-fA-F]{6,}/g, '######' ).slice( 0, 140 ) ); }
			( html.match( THIRD ) || [] ).forEach( x => { const k = x.slice( 0, x.search( /[ ={]/ ) ); third[ k ] = ( third[ k ] || 0 ) + 1; } );
		}
	}
	console.log( bad ? '  ❌ nonce کد ما در HTML مهمان: ' + bad + ' از ' + checked + ' صفحه' : '  ✅ HTML مهمان ' + checked + ' صفحه (استان · شهر · جاذبه × fa · en · ar): هیچ nonce از کد ما نیست' );
	console.log( '  · nonceِ شخص ثالث در HTML مهمان (دست نخورد): ' + ( Object.entries( third ).map( ( [ k, n ] ) => k + '×' + n ).join( ' · ' ) || 'هیچ' ) );
	/* نقطهٔ REST نانس: بی‌کش */
	const rr = await guest.request.get( ENV.base + '/wp-json/topersia/v1/uc-nonce', { timeout: 240000 } );
	const rh = rr.headers(); const rj = await rr.json();
	const restOk = rr.ok() && /^[0-9a-f]{8,}$/.test( rj.nonce || '' ) && /no-store/.test( rh[ 'cache-control' ] || '' ) && 'no-cache' === rh[ 'x-litespeed-cache-control' ];
	console.log( ( restOk ? '  ✅' : '  ❌' ) + ' REST نانس مهمان: ' + rr.status() + ' · Cache-Control: ' + ( rh[ 'cache-control' ] || '-' ) + ' · X-LiteSpeed-Cache-Control: ' + ( rh[ 'x-litespeed-cache-control' ] || '-' ) );
	if ( bad || ! restOk ) { process.exitCode = 1; }
	await b.close();
	for ( const r of rows ) {
		console.log( r.u + ' | HTML مهمان ' + r.guest + ' بایت · کاربر ' + r.user + ' بایت | خطوط فقط‌کاربر ' + r.onlyUser + ' · فقط‌مهمان ' + r.onlyGuest );
		console.log( '   در HTML مهمان یافت شد: ' + ( r.found.join( ' · ' ) || '—' ) );
		r.sampleUser.forEach( x => console.log( '   فقط کاربر: ' + x ) );
	}
} )();
