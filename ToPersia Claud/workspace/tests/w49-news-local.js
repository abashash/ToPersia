/**
 * دستۀ 49 بخش ۲ — آرشیو خبر /news/ (P5-D534) در مرورگر، روی نسخۀ محلی (فقط topersia.local؛ فقط خواندن).
 *
 *   node tests/w49-news-local.js
 *
 * fa · en · ar × ۱۴۴۰ و ۳۹۰ × روز · عصر · شب:
 *   بی سرریز افقی؛ dir و lang از زبان صفحه؛ fa: یک برگزیده + سه کنار، نشان موضوع، زمان مطالعه؛
 *   تراشۀ موضوع بی شمار؛ هر فیلد پالایش برچسب دارد و ≥ ۴۴px؛ en/ar: خالی آرام به زبان خودش.
 *   دکمۀ شبکه/فهرست فقط با JS، و کلیک آن کلاس فهرست را می‌گذارد (aria-pressed).
 *   نمونه هرگز: هیچ <img> صفحه نشانی «sample» ندارد (شناسۀ پیوست در w48-news-local.php).
 * کنتراست سه حالت: php tests/contrast.php --url=http://topersia.local/news/
 * یک عکس «پس» (fa · ۱۴۴۰ · روز): tests/.screens/49/news-fa-1440.png (بیرون از git).
 */
'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE = ENV.base;
const X    = Number( process.env.TP_TIMEOUT_X || 1 );
const PFX  = { fa: '', en: '/en', ar: '/ar' };
const DIR  = { fa: 'rtl', en: 'ltr', ar: 'rtl' };
let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( ok || ! note ? '' : '   ← ' + note ) ); };

const probe = () => {
	const q = s => document.querySelector( s ), root = q( '.tp-news-archive' );
	if ( ! root ) { return null; }
	const W = document.documentElement.clientWidth;
	const over = [ ...root.querySelectorAll( '*' ) ].filter( e => { const r = e.getBoundingClientRect(); return r.width && ( r.right > W + 1 || r.left < -1 ) && ! e.closest( '.tp-news-hero' ); } )
		.slice( 0, 3 ).map( e => e.className );
	const fields = [ ...root.querySelectorAll( '.tp-news-filter :is(select,input:not([type=hidden]))' ) ];
	return {
		sw: document.documentElement.scrollWidth, cw: W, over,
		dir: root.getAttribute( 'dir' ) || getComputedStyle( root ).direction, lang: document.documentElement.lang,
		feat: root.querySelectorAll( '.tp-news-feat' ).length, mini: root.querySelectorAll( '.tp-news-mini' ).length, card: root.querySelectorAll( '.tp-news-card' ).length,
		topics: [ ...root.querySelectorAll( '.tp-news-topic' ) ].every( b => '' !== b.textContent.trim() ),
		read: [ ...root.querySelectorAll( '.tp-news-feat, .tp-news-mini, .tp-news-card' ) ].every( a => a.querySelector( '.tp-news-read' ) ),
		chipNum: [ ...root.querySelectorAll( '.tp-news-chips a' ) ].some( a => /[0-9۰-۹٠-٩]/.test( a.textContent ) ),
		labels: fields.every( f => f.id && root.querySelector( 'label[for="' + f.id + '"]' ) ),
		h44: fields.every( f => f.getBoundingClientRect().height >= 44 ),
		empty: ( q( '.tp-news-empty' ) || {} ).textContent || '',
		view: q( '[data-tp-news-view]' ) ? ! q( '[data-tp-news-view]' ).hidden : null,
		imgs: [ ...root.querySelectorAll( 'img' ) ].map( i => i.currentSrc || i.src ),
		mode: document.documentElement.getAttribute( 'data-tp-mode' ),
	};
};

( async () => {
	const b = await chromium.launch();
	try {
		for ( const [ w, h ] of [ [ 1440, 900 ], [ 390, 844 ] ] ) {
			for ( const mode of [ 'light', 'evening', 'dark' ] ) {
				const mob = w < 768;
				const ctx = await b.newContext( { viewport: { width: w, height: h }, isMobile: mob, hasTouch: mob } );
				const p = await ctx.newPage();
				const errs = [];
				p.on( 'pageerror', e => errs.push( e.message ) );
				await p.addInitScript( m => { try { localStorage.setItem( 'tp-mode', m ); localStorage.removeItem( 'tp-news-view' ); } catch ( e ) {} }, mode );
				for ( const l of [ 'fa', 'en', 'ar' ] ) {
					console.log( `\n${w} · ${mode} · ${l}` );
					const res = await p.goto( BASE + PFX[ l ] + '/news/', { waitUntil: 'load', timeout: 120000 * X } );
					await p.evaluate( async () => { for ( let y = 0; y < document.documentElement.scrollHeight; y += 500 ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 60 ) ); } window.scrollTo( { top: 0, behavior: 'instant' } ); } );
					const r = await p.evaluate( probe );
					if ( ! r ) { t( `/news/ با قالب ما (${res.status()})`, false ); continue; }
					t( `۲۰۰، حالت ${r.mode}، بی سرریز افقی (${r.sw} ≤ ${r.cw})`, 200 === res.status() && r.mode === mode && r.sw <= r.cw && ! r.over.length, r.over.join( ' | ' ) );
					t( `dir ${r.dir} و lang ${r.lang} از زبان صفحه`, r.dir === DIR[ l ] && 0 === r.lang.indexOf( l ) );
					t( 'هر فیلد پالایش برچسب دارد و ≥ ۴۴px', r.labels && r.h44 );
					t( 'نمونه هرگز: بی تصویر نمونه یا بیرونی', r.imgs.every( u => 0 === u.indexOf( BASE ) && ! /sample/i.test( u ) ), r.imgs.filter( u => /sample/i.test( u ) || 0 !== u.indexOf( BASE ) ).join( ' ' ) );
					if ( 'fa' === l ) {
						t( `برگزیده ${r.feat} + کنار ${r.mini} (+ شبکه ${r.card})؛ نشان موضوع و زمان مطالعه روی همه`, 1 === r.feat && 3 === r.mini && r.topics && r.read );
						t( 'تراشه‌های موضوع بی شمار', ! r.chipNum );
						if ( 1440 === w && 'light' === mode ) {
							/* بی‌پالایش با ۴ خبر همه در برگزیده‌اند و شبکه‌ای نیست ← دکمه نیست؛ روی صفحۀ پالایش‌شده (شبکه) سنجیده می‌شود */
							t( 'بی شبکه، بی دکمۀ نما', r.card > 0 || null === r.view );
							await p.goto( BASE + '/news/?tp_kind=tp_city', { waitUntil: 'load', timeout: 120000 * X } );
							const r2 = await p.evaluate( probe );
							t( `صفحۀ پالایش‌شده: شبکه ${r2.card} کارت، بی برگزیده، دکمۀ نما با JS پیدا`, r2.card > 0 && 0 === r2.feat && true === r2.view );
							const v = await p.evaluate( () => { const btn = document.querySelector( '[data-tp-view="list"]' ); btn.click(); return { rows: document.querySelector( '[data-tp-news-items]' ).classList.contains( 'tp-news-grid--rows' ), pressed: btn.getAttribute( 'aria-pressed' ) }; } );
							t( 'کلیک «فهرستی»: کلاس فهرست و aria-pressed', v.rows && 'true' === v.pressed, JSON.stringify( v ) );
							await p.evaluate( () => document.querySelector( '[data-tp-view="grid"]' ).click() );
							await p.goto( BASE + '/news/', { waitUntil: 'load', timeout: 120000 * X } );
							await p.evaluate( async () => { for ( let y = 0; y < document.documentElement.scrollHeight; y += 500 ) { window.scrollTo( { top: y, behavior: 'instant' } ); await new Promise( r => setTimeout( r, 60 ) ); } window.scrollTo( { top: 0, behavior: 'instant' } ); } );
							const dir = path.join( __dirname, '.screens', '49' );
							fs.mkdirSync( dir, { recursive: true } );
							await p.screenshot( { path: path.join( dir, 'news-fa-1440.png' ), fullPage: true } );
							console.log( '  ↳ عکس پس: ' + path.join( dir, 'news-fa-1440.png' ) );
						}
					} else {
						t( `خالی آرام به زبان خودش («${r.empty.trim()}»)، بی متن فارسی`, r.feat + r.mini + r.card > 0 || ( '' !== r.empty.trim() && ! /[پچژگ]/.test( r.empty ) ) );
					}
				}
				t( `بی خطای جاوااسکریپت (${w} · ${mode})`, ! errs.length, errs.join( ' | ' ) );
				await ctx.close();
			}
		}
	} finally {
		await b.close();
	}
	console.log( `\nw49-news-local: ${pass} سبز · ${fail} قرمز` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
