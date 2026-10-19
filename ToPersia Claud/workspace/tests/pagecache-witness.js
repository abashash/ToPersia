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
	await b.close();
	for ( const r of rows ) {
		console.log( r.u + ' | HTML مهمان ' + r.guest + ' بایت · کاربر ' + r.user + ' بایت | خطوط فقط‌کاربر ' + r.onlyUser + ' · فقط‌مهمان ' + r.onlyGuest );
		console.log( '   در HTML مهمان یافت شد: ' + ( r.found.join( ' · ' ) || '—' ) );
		r.sampleUser.forEach( x => console.log( '   فقط کاربر: ' + x ) );
	}
} )();
