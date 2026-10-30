/**
 * مرتب‌سازی پنجرهٔ فهرست و برگه‌های «در یک نگاه» روی صفحهٔ واقعی محلی — دستهٔ 36 · P5-D465 · P5-D466. فقط می‌خواند.
 *
 *   node tests/sort-tabs-local.js
 *
 * کشور (پنجرهٔ استان‌ها) و استان (پنجرهٔ شهرها) × fa · en · ar × ۱۴۴۰ و ۳۹۰ در کروم:
 *   نوار مرتب‌سازی با aria-pressed و هدف ≥۴۴؛ همهٔ عضوها و پیوندها پیش و پس از هر ترتیب یکی؛
 *   الفبا = Intl.Collator زبان صفحه؛ جمعیت/وسعت نزولی و ناموجود آخر؛ «پیش‌فرض» و هر بازشدن = ترتیب سرور.
 * برگه‌ها: tablist/tab/tabpanel، ←/→ از جهت صفحه، Home/End، فقط یک پنل دیده می‌شود و هر سه متن در سند است.
 */
'use strict';

const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

const BASE = ENV.base;
const PAGES = [
	{ name: 'کشور', fa: '/country/iran/', sel: '#tp-list-provinces' },
	{ name: 'استان', fa: '/province/isfahan/', sel: '#tp-list-places' },
];
const LANGS = { fa: '', en: '/en', ar: '/ar' };
let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( ok || ! note ? '' : '   ← ' + note ) ); };

( async () => {

	const browser = await chromium.launch();

	for ( const vp of [ { w: 1440, h: 900, m: false }, { w: 390, h: 844, m: true } ] ) {

		const ctx = await browser.newContext( { viewport: { width: vp.w, height: vp.h }, isMobile: vp.m, hasTouch: vp.m } );
		const page = await ctx.newPage();

		for ( const p of PAGES ) {
			for ( const [ lang, pre ] of Object.entries( LANGS ) ) {

				const url = BASE + pre + p.fa;
				const tag = `${p.name} [${lang}] ${vp.w}`;
				const r = await page.goto( url, { waitUntil: 'domcontentloaded' } );

				if ( ! r || 200 !== r.status() ) { console.log( `  · ${tag}: ${r ? r.status() : 'بی پاسخ'} — رد شد` ); continue; }

				await page.waitForSelector( '.tp-page', { timeout: 60000 } );

				const has = await page.evaluate( s => !! document.querySelector( s ), p.sel );
				if ( ! has ) { console.log( `  · ${tag}: پنجرهٔ فهرست ندارد (ترجمه/دادهٔ ناکافی) — رد شد` ); continue; }

				const snap = () => page.evaluate( s => {
					const box = document.querySelector( s );
					return [].map.call( box.querySelectorAll( '.tp-list__item' ), li => ( {
						n: li.querySelector( 'b' ).textContent, href: ( li.querySelector( 'a' ) || {} ).href || '',
						pop: li.getAttribute( 'data-tp-pop' ), area: li.getAttribute( 'data-tp-area' ) } ) );
				}, p.sel );
				const open = async () => {
					await page.evaluate( s => { const a = document.querySelector( '[data-tp-list-open][href="' + s + '"]' ); a.click(); }, p.sel );
					await page.waitForTimeout( 200 );
				};
				const press = async by => {
					await page.evaluate( ( s, b ) => document.querySelector( s + ' [data-tp-sort-by="' + b + '"]' ).click(), p.sel, by );
					await page.waitForTimeout( 100 );
				};

				const base = await snap();
				await open();

				const bar = await page.evaluate( s => [].map.call( document.querySelectorAll( s + ' [data-tp-sort-by]' ), b => {
					const r = b.getBoundingClientRect();
					return { by: b.getAttribute( 'data-tp-sort-by' ), pressed: b.getAttribute( 'aria-pressed' ), h: r.height, w: r.width };
				} ), p.sel );
				t( `${tag}: چهار دکمه با aria-pressed (فقط «پیش‌فرض» فشرده) و هدف لمسی ≥۴۴`,
					4 === bar.length && bar.every( b => b.h >= 44 && b.w >= 44 ) && 'true' === bar[ 0 ].pressed && bar.slice( 1 ).every( b => 'false' === b.pressed ), JSON.stringify( bar ) );

				const sameSet = ( a, b ) => a.length === b.length && JSON.stringify( a.map( x => x.href ).sort() ) === JSON.stringify( b.map( x => x.href ).sort() );

				await press( 'alpha' );
				const al = await snap();
				const coll = new Intl.Collator( lang );
				t( `${tag}: الفبا = Intl.Collator(${lang})؛ همان اعضا و پیوندها`,
					sameSet( al, base ) && al.every( ( x, i ) => 0 === i || coll.compare( al[ i - 1 ].n, x.n ) <= 0 ), al.slice( 0, 4 ).map( x => x.n ).join( '|' ) );

				for ( const [ by, attr ] of [ [ 'pop', 'pop' ], [ 'area', 'area' ] ] ) {
					await press( by );
					const s = await snap();
					const vals = s.map( x => null === x[ attr ] ? NaN : parseFloat( x[ attr ] ) );
					const firstNaN = vals.findIndex( v => isNaN( v ) );
					const tailOk = -1 === firstNaN || vals.slice( firstNaN ).every( v => isNaN( v ) );
					const desc = vals.filter( v => ! isNaN( v ) ).every( ( v, i, a ) => 0 === i || a[ i - 1 ] >= v );
					const pressed = await page.evaluate( ( q, b ) => document.querySelector( q + ' [data-tp-sort-by="' + b + '"]' ).getAttribute( 'aria-pressed' ), p.sel, by );
					t( `${tag}: ${by} نزولی، ناموجود آخر، همان اعضا`, sameSet( s, base ) && tailOk && desc && 'true' === pressed, vals.slice( 0, 5 ).join( ',' ) );
				}

				await press( 'default' );
				t( `${tag}: «پیش‌فرض» ترتیب سرور را برمی‌گرداند`, JSON.stringify( ( await snap() ).map( x => x.href ) ) === JSON.stringify( base.map( x => x.href ) ) );

				await press( 'pop' );
				await page.evaluate( s => document.querySelector( s + ' [data-tp-close]' ).click(), p.sel );
				await open();
				const again = await snap();
				const pr = await page.evaluate( s => document.querySelector( s + ' [data-tp-sort-by="default"]' ).getAttribute( 'aria-pressed' ), p.sel );
				t( `${tag}: بازشدن دوباره = ترتیب پیش‌فرض`, JSON.stringify( again.map( x => x.href ) ) === JSON.stringify( base.map( x => x.href ) ) && 'true' === pr );
				await page.evaluate( s => document.querySelector( s + ' [data-tp-close]' ).click(), p.sel );
			}
		}

		/* برگه‌ها: صفحهٔ شهر/استان با بلوک «در یک نگاه» */
		for ( const [ lang, pre ] of Object.entries( LANGS ) ) {

			const tag = `برگه‌ها [${lang}] ${vp.w}`;
			const r = await page.goto( BASE + pre + '/province/isfahan/', { waitUntil: 'domcontentloaded' } );

			if ( ! r || 200 !== r.status() ) { continue; }

			await page.waitForSelector( '.tp-page', { timeout: 60000 } );
			const info = await page.evaluate( () => {
				const tabs = [].slice.call( document.querySelectorAll( '.tp-ai__tabs [role="tab"]' ) );
				return { n: tabs.length, panels: document.querySelectorAll( '[role="tabpanel"][data-tp-ai-panel]' ).length,
					texts: [].map.call( document.querySelectorAll( '[data-tp-ai-panel]' ), p => p.textContent.trim().length > 0 ),
					rtl: 'rtl' === getComputedStyle( document.querySelector( '.tp-ai__tabs' ) || document.body ).direction,
					h: tabs.map( x => x.getBoundingClientRect().height ) };
			} );

			if ( info.n < 2 ) { console.log( `  · ${tag}: کمتر از دو برگه — رد شد` ); continue; }

			t( `${tag}: ${info.n} برگه و ${info.panels} پنل، همه متن‌دار در سند؛ هدف ≥۴۴`, info.n === info.panels && info.texts.every( Boolean ) && info.h.every( h => h >= 44 ), JSON.stringify( info ) );

			const state = () => page.evaluate( () => ( {
				sel: [].map.call( document.querySelectorAll( '.tp-ai__tabs [role="tab"]' ), b => b.getAttribute( 'aria-selected' ) ),
				tab: [].map.call( document.querySelectorAll( '.tp-ai__tabs [role="tab"]' ), b => b.getAttribute( 'tabindex' ) ),
				vis: [].map.call( document.querySelectorAll( '[data-tp-ai-panel]' ), p => ! p.hidden ),
				act: ( document.activeElement.getAttribute( 'data-ai' ) || '' ) } ) );

			await page.evaluate( () => document.querySelector( '.tp-ai__tabs [role="tab"]' ).focus() );
			const fwd = info.rtl ? 'ArrowLeft' : 'ArrowRight', bwd = info.rtl ? 'ArrowRight' : 'ArrowLeft';
			const s0 = await state();
			await page.keyboard.press( fwd );
			const s1 = await state();
			await page.keyboard.press( bwd );
			const s2 = await state();
			await page.keyboard.press( 'End' );
			const s3 = await state();
			await page.keyboard.press( 'Home' );
			const s4 = await state();
			const one = s => 1 === s.sel.filter( x => 'true' === x ).length && 1 === s.tab.filter( x => '0' === x ).length && 1 === s.vis.filter( Boolean ).length;
			t( `${tag}: جهت (${fwd}=بعدی)، Home/End، یک برگهٔ انتخاب‌شده، فوکوس گردان، یک پنل دیده`,
				[ s0, s1, s2, s3, s4 ].every( one ) && 'true' === s0.sel[ 0 ] && 'true' === s1.sel[ 1 ] && 'true' === s2.sel[ 0 ]
				&& 'true' === s3.sel[ info.n - 1 ] && 'true' === s4.sel[ 0 ] && s1.vis[ 1 ],
				JSON.stringify( [ s0, s1, s2, s3, s4 ] ) );
		}

		await ctx.close();
	}

	await browser.close();
	console.log( '─'.repeat( 78 ) + `\n  گذشته: ${pass}   افتاده: ${fail}` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 1 ); } );
