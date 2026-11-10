/**
 * دستۀ 51 — روی نسخۀ محلی (فقط topersia.local).
 *
 *   node tests/w51-local.js
 *
 * /news/: پالایش زیر بلوک بالا (هیرو ← بالا ← پالایش ← نتایج)؛ ردیف پالایش فعال زیر کل بلوک با «پاک‌کردن همه»؛
 *   H1 با نام مکان (/news/ ← ایران، /province/isfahan/news/ ← اصفهان)؛ combobox مکان: ۳ نویسه، بالا/پایین، Esc.
 * صفحۀ اول ۱۴۴۰ و ۳۹۰: برای هر اسلایدر دقیقاً یک نوار (P5-D550 · P5-D559) و نوار بومی پنهان؛ بی سرریز افقی؛
 *   ردیف سه‌ستونی و [ایران در تصویر | اخبار] هم‌قد (۱۴۴۰)؛ نقطه‌های کلاژ (P5-D561).
 * پیوندها ۲۰۰: کارت فصل (?season=)، برچسب و تجربه (/tags/)، آیکون راهنما (هاب)، هر «مشاهدۀ همه».
 * بازنویسی: هاب /province/ در fa · en · ar، برچسب /tags/desert-tours/ در fa · en · ar؛ نامک ناموجود ۴۰۴.
 * عکس «پس»: tests/.screens/51/ (بیرون از git).
 */
'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const http = require( 'http' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE = ENV.base.replace( /\/$/, '' );
const X    = Number( process.env.TP_TIMEOUT_X || 1 );
const SHOT = path.join( __dirname, '.screens', '51' );
let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( ok || ! note ? '' : '   ← ' + note ) ); };
const go = ( p, u ) => p.goto( u, { waitUntil: 'load', timeout: 120000 * X } );
const settle = ms => new Promise( r => setTimeout( r, ms ) );
const until = async ( p, fn, arg, ms = 20000 ) => { try { await p.waitForFunction( fn, arg, { timeout: ms * X } ); return true; } catch ( e ) { return false; } };

/* وضعیت HTTP بی پروکسی؛ پس از بازنویسی تازه، نخستین درخواست گاهی ۴۰۴ است ← یک بار دیگر */
const status1 = u => new Promise( res => {
	const q = http.get( u, { headers: { 'User-Agent': 'tp-w51' } }, r => { r.resume(); res( r.statusCode ); } );
	q.on( 'error', () => res( 0 ) );
	q.setTimeout( 60000 * X, () => { q.destroy(); res( 0 ); } );
} );
const status = async u => { const a = await status1( u ); return 200 === a ? a : status1( u ); };
const local = u => { try { return 'topersia.local' === new URL( u ).hostname; } catch ( e ) { return false; } };

( async () => {

	fs.mkdirSync( SHOT, { recursive: true } );
	console.log( '\nدستۀ 51 — نسخۀ محلی\n' + '─'.repeat( 78 ) );
	const br = await chromium.launch();
	const ctx = await br.newContext( { viewport: { width: 1440, height: 900 } } );
	const p = await ctx.newPage();

	/* ── /news/ ── */
	await go( p, BASE + '/news/' );
	const order = await p.evaluate( () => {
		const q = s => document.querySelector( s );
		const before = ( a, b ) => !! ( a && b && ( a.compareDocumentPosition( b ) & Node.DOCUMENT_POSITION_FOLLOWING ) );
		const h = q( '.tp-news-hero' ), top = q( '.tp-news-topwrap' ), f = q( '.tp-news-filter' ), r = q( '.tp-news-results' );
		return { ok: before( h, top ) && before( top, f ) && before( f, r ), actf: !! q( '.tp-actf:not([hidden]) .tp-actf__chip' ) || ! ( q( '.tp-actf' ) || {} ).hidden, h1: ( q( 'h1' ) || {} ).textContent || '' };
	} );
	t( 'پالایش: هیرو ← بالا ← پالایش ← نتایج', order.ok );
	t( 'بی پالایش ← ظرف ردیف فعال پنهان و بی تراشه (ظرف جای‌گزینی همیشه هست)', ! order.actf );
	t( 'H1 /news/ با «ایران»', /ایران/.test( order.h1 ), order.h1 );

	await go( p, BASE + '/news/?tp_topic=destinations' );
	const act = await p.evaluate( () => {
		const a = document.querySelector( '.tp-actf' ), f = document.querySelector( '.tp-news-filter' );
		return { has: !! a, chip: !! ( a && a.querySelector( '.tp-actf__chip' ) ), clear: !! ( a && a.querySelector( '.tp-actf__clear' ) ),
			below: !! ( a && f && ( f.compareDocumentPosition( a ) & Node.DOCUMENT_POSITION_FOLLOWING ) && ! f.contains( a ) ),
			cur: !! document.querySelector( '.tp-news-chips__list a[aria-current="page"][data-tp-topic="destinations"]' ) };
	} );
	t( 'تراشۀ فعال: ردیف زیر کل بلوک پالایش، تراشۀ حذف‌شدنی + «پاک‌کردن همه»', act.has && act.chip && act.clear && act.below, JSON.stringify( act ) );
	t( 'تراشۀ موضوع جاری aria-current', act.cur );

	await go( p, BASE + '/province/isfahan/news/' );
	const h1p = await p.evaluate( () => ( document.querySelector( 'h1' ) || {} ).textContent || '' );
	t( 'H1 خبر استان با «اصفهان»', /اصفهان/.test( h1p ), h1p );

	/* combobox */
	await go( p, BASE + '/news/' );
	const inp = await p.$( '.tp-loc__in' );
	if ( inp ) {
		await inp.click();
		await inp.type( 'اص' );
		await settle( 900 );
		const two = await p.evaluate( () => document.querySelectorAll( '.tp-loc__list:not([hidden]) [role="option"]' ).length );
		await inp.type( 'ف' );
		const opened = await until( p, () => document.querySelectorAll( '.tp-loc__list:not([hidden]) [role="option"]' ).length > 0 );
		await p.keyboard.press( 'ArrowDown' );
		const ad = await p.evaluate( () => { const i = document.querySelector( '.tp-loc__in' ); return [ i.getAttribute( 'aria-expanded' ), i.getAttribute( 'aria-activedescendant' ) || '' ]; } );
		await p.keyboard.press( 'Escape' );
		const closed = await p.evaluate( () => 'false' === document.querySelector( '.tp-loc__in' ).getAttribute( 'aria-expanded' ) );
		t( 'combobox: زیر ۳ نویسه بی گزینه', 0 === two, String( two ) );
		t( 'combobox: ۳ نویسه ← listbox باز، پایین ← activedescendant', opened && 'true' === ad[0] && '' !== ad[1], JSON.stringify( ad ) );
		t( 'combobox: Esc ← بسته', closed );
	} else {
		t( 'combobox: فیلد مکان روی /news/', false );
	}

	/* ── صفحۀ اول ── */
	const links = { season: new Set(), tag: new Set(), guide: new Set(), more: new Set() };

	for ( const w of [ 1440, 390 ] ) {

		await p.setViewportSize( { width: w, height: 1440 === w ? 900 : 844 } );
		await go( p, BASE + '/' );
		await until( p, () => document.querySelectorAll( '.tp-prog' ).length > 0 );
		await settle( 600 );

		const r = await p.evaluate( () => {
			const S = [ ...document.querySelectorAll( '.tp-slider, .tp-home-slider, [data-tp-prog]' ) ].filter( s => ! s.hasAttribute( 'data-tp-no-prog' ) );
			const bad = S.filter( s => {
				let n = 0, e = s.nextElementSibling;
				while ( e && e.classList.contains( 'tp-prog' ) ) { n++; e = e.nextElementSibling; }
				const native = 'none' !== getComputedStyle( s ).scrollbarWidth && s.scrollWidth > s.clientWidth;
				return 1 !== n || native;
			} ).map( s => s.id || s.className );
			const H = el => el ? Math.round( el.getBoundingClientRect().height ) : -1;
			const row = document.querySelector( '.tp-home-row3' );
			const pair = [ document.querySelector( '#tp-home-gallery' ), document.querySelector( '#tp-home-news' ) ];
			const href = s => [ ...document.querySelectorAll( s ) ].map( a => a.href );
			return {
				sliders: S.length, bars: document.querySelectorAll( '.tp-prog' ).length, bad,
				ov: document.documentElement.scrollWidth > document.documentElement.clientWidth,
				row: row ? [ ...row.children ].map( H ) : [], pair: pair.map( H ),
				empty: [ ...document.querySelectorAll( '.tp-home-season.tp-is-empty' ) ].every( e => 'A' !== e.tagName ),
				season: href( 'a.tp-home-season' ), tag: href( '.tp-home-exp a, .tp-tags__a' ), guide: href( '#tp-home-guide .tp-home-guide__a' ), more: href( '.tp-home .tp-more' ),
				dots: document.querySelectorAll( '.tp-home-dots:not([hidden]) button' ).length, slides: document.querySelectorAll( '#tp-home-photoslide .tp-home-collage' ).length,
			};
		} );

		t( w + ': هر اسلایدر دقیقاً یک نوار، نوار بومی پنهان', 0 === r.bad.length && r.bars === r.sliders, r.sliders + '/' + r.bars + ' ' + r.bad.join( ',' ) );
		t( w + ': بی سرریز افقی', ! r.ov );
		t( w + ': فصل خالی بی پیوند', r.empty );
		t( w + ': نقطۀ کلاژ = شمار اسلاید (دو به بالا)', r.slides < 2 ? 0 === r.dots : r.dots === r.slides, r.slides + '/' + r.dots );
		if ( 1440 === w ) {
			t( '1440: ردیف سه‌ستونی هم‌قد', r.row.length > 0 && Math.max( ...r.row ) - Math.min( ...r.row ) <= 2, r.row.join( ',' ) );
			t( '1440: ایران در تصویر هم‌قد ستون اخبار', r.pair[0] > 0 && Math.abs( r.pair[0] - r.pair[1] ) <= 2, r.pair.join( ',' ) );

			/* نقطه ← اسلاید، و برگشت */
			if ( r.slides > 1 ) {
				const dot = async k => {
					await p.evaluate( k => { const s = document.querySelector( '#tp-home-photoslide' ); s.scrollIntoView( { behavior: 'instant', block: 'center' } ); document.querySelectorAll( '.tp-home-dots button' )[ k ].click(); }, k );
					await until( p, k => document.querySelectorAll( '.tp-home-dots button' )[ k ].getAttribute( 'aria-current' ) === 'true', k, 8000 );
					await settle( 500 );
					return p.evaluate( k => { const s = document.querySelector( '#tp-home-photoslide' ), c = s.children[ k ].getBoundingClientRect(), b = s.getBoundingClientRect(); return c.left >= b.left - 2 && c.right <= b.right + 2; }, k );
				};
				t( '1440: نقطۀ آخر ← اسلاید آخر، نقطۀ نخست ← برگشت', await dot( r.slides - 1 ) && await dot( 0 ) );
			}
			await p.screenshot( { path: path.join( SHOT, 'home-1440.png' ), fullPage: true } );
		} else {
			await p.screenshot( { path: path.join( SHOT, 'home-390.png' ), fullPage: true } );
		}
		for ( const k of Object.keys( links ) ) { r[ k ].forEach( u => links[ k ].add( u ) ); }
	}

	await p.setViewportSize( { width: 1440, height: 900 } );
	await go( p, BASE + '/desert/maranjab-desert/' );
	( await p.evaluate( () => [ ...document.querySelectorAll( '.tp-tags__a' ) ].map( a => a.href ) ) ).forEach( u => links.tag.add( u ) );
	await br.close();

	/* ── پیوندها ۲۰۰ ── */
	const name = { season: 'کارت فصل', tag: 'برچسب و تجربه', guide: 'آیکون راهنما', more: '«مشاهدۀ همه»' };
	for ( const k of Object.keys( links ) ) {
		const all = [ ...links[ k ] ];
		const bad = [];
		for ( const u of all ) {
			if ( ! local( u ) ) { bad.push( 'بیرونی ' + u ); continue; }
			const s = await status( u );
			if ( 200 !== s ) { bad.push( s + ' ' + u ); }
		}
		t( name[ k ] + ': ' + all.length + ' پیوند، همه ۲۰۰', all.length > 0 && 0 === bad.length, bad.slice( 0, 3 ).join( ' · ' ) );
		if ( 'season' === k ) { t( 'کارت فصل با ?season=', all.every( u => /[?&]season=(spring|summer|autumn|winter)\b/.test( u ) ) ); }
	}

	/* ── بازنویسی ── */
	for ( const u of [ '/province/', '/en/province/', '/ar/province/', '/tags/desert-tours/', '/en/tags/desert-tours/', '/ar/tags/desert-tours/' ] ) {
		t( 'بازنویسی ' + u + ' ← ۲۰۰', 200 === await status( BASE + u ) );
	}
	t( 'برچسب ناموجود ← ۴۰۴', 404 === await status1( BASE + '/tags/tp-w51-nope/' ) );
	t( 'صفحۀ هاب بیرون از بازه ← ۴۰۴', 404 === await status1( BASE + '/city/page/99/' ) );

	console.log( '─'.repeat( 78 ) + '\n  گذشته: ' + pass + '   افتاده: ' + fail + '\n  عکس: ' + SHOT );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
