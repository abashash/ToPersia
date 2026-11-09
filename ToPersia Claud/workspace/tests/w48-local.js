/**
 * دستۀ 48 بخش ۱ — رفتار صفحۀ اول روی نسخۀ محلی (فقط topersia.local).
 *
 *   node tests/w48-local.js
 *
 * P5-D529: هر اسلایدر یک ردیف (top یکسان همۀ کارت‌ها) در ۱۴۴۰ · ۱۰۲۴ · ۷۶۸ · ۳۹۰؛ نوار و دستگیرۀ
 *          topersia-g-zones.js (P5-D243): «بعدی» در fa نویسۀ ‹ و در en نویسۀ ›، کلیک «بعدی» scrollLeft را
 *          در جهت خواندن می‌برد (fa منفی، en مثبت)، «قبلی» در آغاز و «بعدی» در پایان غیرفعال.
 *          اسلایدر با کمتر از ۲ کارت نوار ندارد (topersia_g_slider_bar).
 * P5-D530: سه ستون راهنما | اخبار | پرسش‌ها هم‌قد در ۱۴۴۰ و ۱۰۲۴، پس از باز کردن پرسش هم؛ زیر ۹۰۰ عمودی.
 * P5-D531: بی #tp-home-account.  P5-D528: بی .tp-footer.  گالری: آخرین ردیف تک‌عکس ندارد.
 */
'use strict';

const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE = ENV.base;
const X    = Number( process.env.TP_TIMEOUT_X || 1 );
const PFX  = { fa: '', en: '/en', ar: '/ar' };
const NEXT = { fa: '‹', en: '›', ar: '‹' };
let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( ok || ! note ? '' : '   ← ' + note ) ); };

const probe = () => {
	const out = { sliders: {}, trio: [], photos: [] };
	document.querySelectorAll( '.tp-home .tp-home-slider' ).forEach( s => {
		const kids = [ ...s.children ];
		const bar  = s.nextElementSibling && s.nextElementSibling.classList.contains( 'tp-slider-bar' ) ? s.nextElementSibling : null;
		out.sliders[ s.id ] = {
			n: kids.length,
			tops: [ ...new Set( kids.map( c => Math.round( c.getBoundingClientRect().top ) ) ) ],
			max: s.scrollWidth - s.clientWidth,
			bar: bar ? [ ...bar.querySelectorAll( 'button[data-target]' ) ].map( b => ( { dir: b.dataset.dir, dis: b.disabled, g: b.textContent.trim(), label: b.getAttribute( 'aria-label' ) || '' } ) ) : null,
		};
	} );
	out.trio = [ ...document.querySelectorAll( '.tp-home-trio > .tp-home-col' ) ].map( c => { const b = c.getBoundingClientRect(); return { top: Math.round( b.top ), left: Math.round( b.left ), h: Math.round( b.height ) }; } );
	out.photos = [ ...document.querySelectorAll( '.tp-home-photo' ) ].map( a => { const b = a.getBoundingClientRect(); return { top: Math.round( b.top ), w: Math.round( b.width ) }; } );
	out.acct = !! document.getElementById( 'tp-home-account' );
	out.foot = !! document.querySelector( '.tp-footer' );
	out.sw = document.documentElement.scrollWidth; out.cw = document.documentElement.clientWidth;
	return out;
};

( async () => {
	const b = await chromium.launch();
	try {
		for ( const [ w, h ] of [ [ 1440, 900 ], [ 1024, 800 ], [ 768, 1000 ], [ 390, 844 ] ] ) {
			const mob = w < 768;
			const ctx = await b.newContext( { viewport: { width: w, height: h }, isMobile: mob, hasTouch: mob } );
			const p = await ctx.newPage();
			const errs = [];
			p.on( 'pageerror', e => errs.push( e.message ) );
			for ( const l of [ 'fa', 'en' ] ) {
				console.log( `\n${w} · ${l}` );
				await p.goto( BASE + PFX[ l ] + '/', { waitUntil: 'load', timeout: 120000 * X } );
				await p.waitForTimeout( 600 );
				const r = await p.evaluate( probe );
				t( `بی سرریز افقی صفحه (${r.sw} ≤ ${r.cw})`, r.sw <= r.cw );
				t( 'P5-D531: کادر حساب کاربری نیست', ! r.acct );
				t( 'P5-D528: پاورقی افزونه نیست', ! r.foot );
				const ids = Object.keys( r.sliders );
				t( `P5-D529: اسلایدرها پیدا شدند (${ids.join( '، ' ) || '—'})`, ids.length >= 1 && ids.every( id => /^tp-home-(prov|dest|attr|photo)slide$/.test( id ) ), JSON.stringify( r.sliders ) );
				for ( const id of ids ) {
					const s = r.sliders[ id ];
					/* P5-D533 (دستۀ 49): استان‌ها از ۱۰۲۴ به بالا دو ردیف؛ بقیه همیشه یک ردیف */
					const rows2 = 'tp-home-provslide' === id && w >= 1024;
					t( `${id}: ${rows2 ? 'دو' : 'یک'} ردیف — ${s.n} کارت، top یکتا ${s.tops.length}`, rows2 ? ( s.n < 2 ? 1 === s.tops.length : 2 === s.tops.length ) : 1 === s.tops.length, s.tops.join( ' ' ) );
					if ( s.n < 2 ) { t( `${id}: کمتر از ۲ کارت ← بی نوار`, null === s.bar ); continue; }
					const prev = s.bar && s.bar.find( x => '-1' === x.dir ), next = s.bar && s.bar.find( x => '1' === x.dir );
					t( `${id}: نوار با «قبلی» و «بعدی» و aria-label`, !! ( prev && next && prev.label && next.label && prev.label !== next.label ), JSON.stringify( s.bar ) );
					if ( ! prev || ! next ) { continue; }
					t( `${id}: نویسۀ «بعدی» ${NEXT[ l ]} (جهت ${'fa' === l ? 'راست‌به‌چپ' : 'چپ‌به‌راست'})`, NEXT[ l ] === next.g, next.g );
					t( `${id}: «قبلی» در آغاز غیرفعال`, prev.dis );
					t( `${id}: «بعدی» ${s.max > 1 ? 'فعال (سرریز هست)' : 'غیرفعال (سرریز نیست)'}`, ( s.max > 1 ) === ! next.dis, `max=${s.max}` );
					if ( s.max <= 1 ) { continue; }
					const mv = await p.evaluate( async id => {
						const box = document.getElementById( id ), bar = box.nextElementSibling;
						box.scrollIntoView( { behavior: 'instant', block: 'center' } );
						const x0 = box.scrollLeft;
						bar.querySelector( 'button[data-dir="1"]' ).click();
						let x1 = x0;
						for ( let i = 0; i < 30; i++ ) { await new Promise( z => setTimeout( z, 100 ) ); if ( box.scrollLeft === x1 && x1 !== x0 ) { break; } x1 = box.scrollLeft; }
						const prevOn = ! bar.querySelector( 'button[data-dir="-1"]' ).disabled;
						box.scrollTo( { left: 'rtl' === getComputedStyle( box ).direction ? -box.scrollWidth : box.scrollWidth, behavior: 'instant' } );
						await new Promise( z => setTimeout( z, 400 ) );
						const endNextOff = bar.querySelector( 'button[data-dir="1"]' ).disabled;
						box.scrollTo( { left: 0, behavior: 'instant' } );
						return { x0, x1, prevOn, endNextOff, dir: getComputedStyle( box ).direction };
					}, id );
					const want = 'rtl' === mv.dir ? mv.x1 < mv.x0 - 20 : mv.x1 > mv.x0 + 20;
					t( `${id}: کلیک «بعدی» scrollLeft را ${'rtl' === mv.dir ? 'منفی' : 'مثبت'} می‌برد (${mv.x0} ← ${Math.round( mv.x1 )})`, want && mv.dir === ( 'fa' === l ? 'rtl' : 'ltr' ), JSON.stringify( mv ) );
					t( `${id}: پس از کلیک «قبلی» فعال؛ در پایان «بعدی» غیرفعال`, mv.prevOn && mv.endNextOff, JSON.stringify( mv ) );
				}
				/* P5-D530 — سه ستون */
				if ( r.trio.length ) {
					await p.evaluate( () => document.querySelector( '.tp-home-trio' ).scrollIntoView( { behavior: 'instant', block: 'start' } ) );
					await p.waitForTimeout( 500 );
					const c0 = ( await p.evaluate( probe ) ).trio;
					if ( w >= 900 ) {
						const hs = c0.map( c => c.h ), tops = new Set( c0.map( c => c.top ) );
						t( `P5-D530: سه ستون کنار هم و هم‌قد (${hs.join( ' · ' )})`, 3 === c0.length && 1 === tops.size && Math.max( ...hs ) - Math.min( ...hs ) <= 1, JSON.stringify( c0 ) );
						const opened = await p.evaluate( () => { const d = document.querySelector( '.tp-home-trio details:not([open])' ); if ( ! d ) { return false; } d.open = true; return true; } );
						if ( opened ) {
							await p.waitForTimeout( 300 );
							const c1 = ( await p.evaluate( probe ) ).trio, h1 = c1.map( c => c.h );
							t( `P5-D530: پس از باز کردن پرسش هم هم‌قد (${h1.join( ' · ' )})`, Math.max( ...h1 ) - Math.min( ...h1 ) <= 1 && h1[ 0 ] >= hs[ 0 ], JSON.stringify( c1 ) );
						} else {
							t( 'P5-D530: پرسشی برای باز کردن نیست (آزموده نشد)', true );
						}
					} else {
						const tops = c0.map( c => c.top );
						t( `P5-D530: زیر ۹۰۰ عمودی (top ${tops.join( ' < ' )})`, tops.every( ( v, i ) => 0 === i || v > tops[ i - 1 ] ) && new Set( c0.map( c => c.left ) ).size === 1, JSON.stringify( c0 ) );
					}
				}
				/* گالری — دستۀ 49: اسلایدر یک‌ردیفه (در sliders بالا: tp-home-photoslide) */
			}
			t( `${w}: بی خطای JS`, ! errs.length, errs.join( ' | ' ) );
			await ctx.close();
		}
	} finally {
		await b.close();
	}
	console.log( '\n' + '─'.repeat( 78 ) + `\n  گذشته: ${pass}   افتاده: ${fail}` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
