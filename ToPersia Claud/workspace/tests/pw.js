/**
 * Playwright با پیش‌فرض‌های محیط محلی — هر آزمونِ مرورگری از این‌جا می‌گیرد، نه از `playwright`.
 *
 * بی پروکسی سیستم: کروم تنظیم پروکسی ویندوز را می‌خواند. ۱۱ اکتبر برنامهٔ VPN کاربر
 * (happ-xray) پروکسی سیستم گذاشت و همهٔ درخواست‌های topersia.local با ۵۰۳ (بدنهٔ تهی،
 * `proxy-connection: close`) برمی‌گشت، در حالی که curl سالم بود. آزمونی که فقط به
 * topersia.local می‌رود هرگز نباید از پروکسی بگذرد: کروم `--no-proxy-server`، فایرفاکس
 * `network.proxy.type = 0`.
 */
'use strict';

const pw = require( 'playwright' );

function patch( type, extra ) {

	if ( type.__tpDirect ) {
		return;
	}

	const launch = type.launch.bind( type );

	/* TP_TIMEOUT_X (ماشین کند): مهلت پیش‌فرض هر کنش و انتظار Playwright هم ضرب می‌شود، نه فقط مهلت‌های خودِ راننده */
	const X = Math.max( 1, parseFloat( process.env.TP_TIMEOUT_X || '1' ) || 1 );

	type.launch = async function ( options ) {
		const browser = await launch( extra( options || {} ) );
		if ( X > 1 ) {
			const nc = browser.newContext.bind( browser );
			browser.newContext = async function ( o ) { const c = await nc( o ); c.setDefaultTimeout( 30000 * X ); return c; };
			const np = browser.newPage.bind( browser );
			browser.newPage = async function ( o ) { const p = await np( o ); p.setDefaultTimeout( 30000 * X ); return p; };
		}
		return browser;
	};

	type.__tpDirect = true;
}

patch( pw.chromium, o => Object.assign( {}, o, { args: [ '--no-proxy-server' ].concat( o.args || [] ) } ) );
patch( pw.firefox, o => Object.assign( {}, o, { firefoxUserPrefs: Object.assign( { 'network.proxy.type': 0 }, o.firefoxUserPrefs || {} ) } ) );

module.exports = pw;
