/**
 * دستۀ 55-ه بند ۶ — عکس «پس» فقط fa (سند است، نه گیت). روش همان tests/shots-55b.js.
 *
 *   TP_TIMEOUT_X=2 node tests/shots-55e.js
 *
 * ▸ پاورقی (P5-D651: چهار ستون × پنج، نوار پایین) — ۱۴۴۰ (ستون‌ها همیشه باز) و ۳۹۰ بسته و باز (آکاردئون، کلیک روی سر هر ستون).
 * ▸ منوی اصلی (بند ۴) با زیرمنو — ۱۴۴۰: هاور روی نخستین دکمۀ والد (زیرمنوی وودمارت باز)؛ ۳۹۰: منوی موبایل باز و
 *   نخستین والد با کلیک روی دکمه‌اش باز. پنجرۀ دید، نه تمام صفحه.
 * ← tests/.screens/55e/ (بیرون از git) با index.html. کد خروج: ۰ همه گرفته شد · ۱ عکسی کم است · ۲ محیط.
 */
'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) {
	console.error( 'فقط topersia.local' );
	process.exit( 2 );
}

const BASE  = ENV.base.replace( /\/$/, '' );
const X     = Math.max( 1, Number( process.env.TP_TIMEOUT_X || 1 ) );
const DIR   = path.join( __dirname, '.screens', '55e' );
const FOOT  = '[data-elementor-type="footer"]';
const HEADS = FOOT + ' .wd-el-toggle:has(nav.tp-ft-menu) .wd-el-toggle-head';
const BTN   = 'nav.wd-header-main-nav ul.wd-nav-main > li > a[data-tp-nav-btn]';
const MBTN  = 'ul.wd-nav-mobile > li > a[data-tp-nav-btn]';
const wait  = ms => new Promise( r => setTimeout( r, ms ) );

let pass = 0, fail = 0;
const t = ( label, ok ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label ); };

const settlePage = async page => {
	await page.evaluate( async () => { const H = document.documentElement.scrollHeight; for ( let y = 0; y < H; y += 700 ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 120 ) ); } window.scrollTo( { top: 0, behavior: 'instant' } ); } );
	await wait( 1200 * X );
};
const hideFixed = ( page, keep ) => page.evaluate( k => { const kp = k ? document.querySelector( k ) : null; document.querySelectorAll( 'body *' ).forEach( e => { if ( kp && kp.contains( e ) ) { return; } const p = getComputedStyle( e ).position; if ( 'fixed' === p || 'sticky' === p ) { e.style.setProperty( 'display', 'none', 'important' ); } } ); }, keep );
/* نخستین نمونۀ دیدنی (نه رونوشت پنهان چسبان) نشان می‌خورد؛ متن آن برمی‌گردد، یا null اگر نبود */
const mark = ( page, sel, tag ) => page.evaluate( ( [ s, g ] ) => {
	const vis = e => { const r = e.getBoundingClientRect(), cs = getComputedStyle( e ); return r.width > 0 && r.height > 0 && r.bottom > 0 && r.top < innerHeight && 'visible' === cs.visibility && Number( cs.opacity ) > 0.1; };
	document.querySelectorAll( '[data-tp-t="' + g + '"]' ).forEach( e => e.removeAttribute( 'data-tp-t' ) );
	const el = [ ...document.querySelectorAll( s ) ].find( vis );
	if ( el ) { el.setAttribute( 'data-tp-t', g ); }
	return el ? ( el.querySelector( '.nav-link-text' ) || el ).textContent.trim() : null;
}, [ sel, tag ] );

( async () => {
	fs.mkdirSync( DIR, { recursive: true } );
	console.log( 'START shots-55e ' + new Date().toISOString() + ' ← ' + DIR );
	const rows = [];
	const br   = await chromium.launch();
	const open = async ( w, h ) => {
		const ctx  = await br.newContext( { viewport: { width: w, height: h } } );
		const page = await ctx.newPage();
		page.setDefaultTimeout( 30000 * X );
		const st = ( await page.goto( BASE + '/', { waitUntil: 'load', timeout: 120000 * X } ) ).status();
		await settlePage( page );
		return { ctx, page, st };
	};
	try {
		/* منوی اصلی با زیرمنو — رومیزی */
		{
			const { ctx, page, st } = await open( 1440, 900 );
			const title = await mark( page, BTN, 'btn' );
			let shown = false;
			if ( title ) {
				await page.locator( '[data-tp-t="btn"]' ).first().hover();
				await wait( 800 * X );
				shown = await page.evaluate( () => { const b = document.querySelector( '[data-tp-t="btn"]' ); const d = b && b.parentElement.querySelector( ':scope > .wd-dropdown' ); const cs = d && getComputedStyle( d ); return !! cs && 'visible' === cs.visibility && Number( cs.opacity ) > 0.5 && 'true' === b.getAttribute( 'aria-expanded' ); } );
			}
			const f = 'menu-submenu-1440.png';
			await page.screenshot( { path: path.join( DIR, f ) } );
			rows.push( { name: 'منوی اصلی · زیرمنوی «' + title + '»', w: 1440, url: BASE + '/', f, note: 'هاور، aria-expanded=true' } );
			t( `منوی اصلی ۱۴۴۰: وضعیت ${ st }، زیرمنوی «${ title }» باز`, 200 === st && shown );
			await ctx.close();
		}
		/* منوی اصلی با زیرمنو — موبایل */
		{
			const { ctx, page, st } = await open( 390, 844 );
			const burger = await mark( page, '.wd-header-mobile-nav > a', 'burger' );
			let title = '', opened = false;
			if ( null !== burger ) {
				await page.locator( '[data-tp-t="burger"]' ).first().click();
				await wait( 900 * X );
				title = await page.evaluate( s => { const b = document.querySelector( s ); return b ? ( b.querySelector( '.nav-link-text' ) || b ).textContent.trim() : ''; }, MBTN );
				if ( title ) {
					await page.locator( MBTN ).first().click();
					await wait( 800 * X );
					/* dropdown: li.opener-page؛ drilldown (سازندۀ سربرگ): زیرمنو wd-drilldown-show */
					opened = await page.evaluate( s => { const b = document.querySelector( s ); return !! b && ( b.parentElement.classList.contains( 'opener-page' ) || !! b.parentElement.querySelector( ':scope > .wd-sub-menu.wd-drilldown-show' ) ) && 'true' === b.getAttribute( 'aria-expanded' ); }, MBTN );
				}
			}
			const f = 'menu-submenu-390.png';
			await page.screenshot( { path: path.join( DIR, f ) } );
			rows.push( { name: 'منوی موبایل · زیرمنوی «' + title + '»', w: 390, url: BASE + '/', f, note: 'کلیک روی دکمۀ والد' } );
			t( `منوی موبایل ۳۹۰: وضعیت ${ st }، زیرمنوی «${ title }» باز`, 200 === st && opened );
			await ctx.close();
		}
		/* پاورقی — ۱۴۴۰، و ۳۹۰ بسته و باز */
		for ( const [ w, h ] of [ [ 1440, 900 ], [ 390, 844 ] ] ) {
			const { ctx, page, st } = await open( w, h );
			await page.evaluate( () => window.scrollTo( { top: document.documentElement.scrollHeight, behavior: 'instant' } ) );
			await wait( 1000 * X );
			await hideFixed( page, FOOT );
			const foot = await page.$( FOOT );
			const f    = `footer-${ w }${ w < 768 ? '-closed' : '' }.png`;
			if ( foot ) {
				await foot.screenshot( { path: path.join( DIR, f ) } );
			}
			rows.push( { name: 'پاورقی' + ( w < 768 ? ' (بسته)' : '' ), w, url: BASE + '/', f, note: '' } );
			let heads = 0;
			if ( foot && w < 768 ) {
				heads = await page.locator( HEADS ).count();
				for ( let i = 0; i < heads; i++ ) {
					const head = page.locator( HEADS ).nth( i );
					await head.evaluate( e => e.scrollIntoView( { behavior: 'instant', block: 'center' } ) );
					await head.click();
					await wait( 700 * X );
				}
				const f2 = `footer-${ w }-open.png`;
				await foot.screenshot( { path: path.join( DIR, f2 ) } );
				rows.push( { name: 'پاورقی (ستون‌ها باز)', w, url: BASE + '/', f: f2, note: heads + ' ستون' } );
			}
			t( `پاورقی ${ w }: وضعیت ${ st }` + ( w < 768 ? `، ستون‌های بازشده ${ heads }` : '' ), 200 === st && !! foot && ( w >= 768 || 4 === heads ) );
			await ctx.close();
		}
	} finally {
		await br.close();
	}
	const esc = s => String( s ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' );
	fs.writeFileSync( path.join( DIR, 'index.html' ), '<!doctype html><meta charset="utf-8"><title>دستۀ 55-ه — پس</title><body dir="rtl" style="font-family:sans-serif">'
		+ '<h1>دستۀ 55-ه — عکس «پس» (fa) · ' + esc( new Date().toISOString() ) + '</h1>'
		+ rows.map( r => '<h2>' + esc( r.name ) + ' · ' + r.w + '</h2><p dir="ltr">' + esc( r.url ) + ( r.note ? ' — ' + esc( r.note ) : '' ) + '</p><a href="' + r.f + '"><img src="' + r.f + '" style="max-width:' + Math.min( r.w, 720 ) + 'px;border:1px solid #888"></a>' ).join( '' ) + '</body>' );
	console.log( '  ← ' + path.join( DIR, 'index.html' ) );
	console.log( `END shots-55e — گذشته: ${ pass }   افتاده: ${ fail }` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
