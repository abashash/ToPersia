/**
 * دستۀ 52 — روی نسخۀ محلی (فقط topersia.local).
 *
 *   node tests/w52-local.js
 *
 * صفحۀ اول ۱۴۴۰: ردیف [تجربه‌ها | مقاصد] ۱+۲، [رویداد | سفر | فصل] ۱:۱:۱٫۵، [تصویر | اخبار | مقالات] ۲+۱+۱، هم‌قد؛
 *   ۸ کارت تجربه در ۲ ردیف؛ هر فهرست ۳ مورد با بندانگشتی؛ ۹ آیکون راهنما در یک ردیف؛ بی نقطه و بی tilt.
 * فصل‌ها: ترتیب بهار، تابستان، پاییز، زمستان از آغاز سطر — fa (راست‌به‌چپ)، en (چپ‌به‌راست) و ۳۹۰؛
 *   هر کارت فقط به /season/{فصل}/، ۲۰۰، و شمار صفحه = شمار کارت؛ بازنویسی سه زبان؛ نامک ناشناس ۴۰۴.
 * چرخش روزانۀ عکس: همان روز یکسان، روز بعد دیگر (جهش تاریخ، روی استخر واقعی عکس‌ها با wp-cli).
 * هاب نوع، برچسب، فصل و /articles/: بی ردیف فیلتر — تراشه + مرتب‌سازی + «نمایش» + شبکه/فهرست؛ /news/ فیلترهایش را دارد.
 * فیلتر اخبار (P5-D571): پاسخ مسیر سبک < ۳۰۰ms و جایگزینی < ۱۰۰ms پس از پاسخ.
 * صفحۀ تکی خبر و مقاله: دو ستون (اصلی در آغاز سطر)، ترتیب ستون کناری، سطر فراداده با جداکننده، مرتبط‌ها تمام‌عرض، ۳۹۰ یک ستون.
 * عکس «پس» فقط fa: tests/.screens/52/ (بیرون از git) — صفحۀ اول ۱۴۴۰ و ۳۹۰، یک صفحۀ فصل، یک خبر تکی.
 */
'use strict';

const fs   = require( 'fs' );
const path = require( 'path' );
const http = require( 'http' );
const { spawnSync } = require( 'child_process' );
const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE = ENV.base.replace( /\/$/, '' );
const X    = Number( process.env.TP_TIMEOUT_X || 1 );
const SHOT = path.join( __dirname, '.screens', '52' );
const SEAS = [ 'spring', 'summer', 'autumn', 'winter' ];
let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( ok || ! note ? '' : '   ← ' + note ) ); };
const go = ( p, u ) => p.goto( u, { waitUntil: 'load', timeout: 120000 * X } );
const settle = ms => new Promise( r => setTimeout( r, ms ) );
const near = ( a, b, tol ) => Math.abs( a - b ) <= tol;
const digits = s => Number( String( s ).replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ).replace( /[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf( d ) ).replace( /[^0-9]/g, '' ) || NaN );

const status1 = u => new Promise( res => {
	const q = http.get( u, { headers: { 'User-Agent': 'tp-w52' } }, r => { r.resume(); res( r.statusCode ); } );
	q.on( 'error', () => res( 0 ) );
	q.setTimeout( 60000 * X, () => { q.destroy(); res( 0 ); } );
} );
const status = async u => { const a = await status1( u ); return 200 === a ? a : status1( u ); };

/* رفتار PHP روی وردپرس واقعی — فقط خواندن */
const wp = code => {
	const r = spawnSync( ENV.php, [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + BASE, 'eval', code ], { encoding: 'utf8', timeout: 180000 * X } );
	const m = String( r.stdout || '' ).match( /\{.*\}/s );
	try { return m ? JSON.parse( m[0] ) : null; } catch ( e ) { return null; }
};

/* صفحۀ اول آماده برای اندازه‌گیری: بخش‌های content-visibility دیدنی */
const homeReady = async p => {
	await p.addStyleTag( { content: '.tp-home-lazy{content-visibility:visible!important}' } );
	await settle( 1200 );
};

( async () => {

	fs.mkdirSync( SHOT, { recursive: true } );
	console.log( '\nدستۀ 52 — نسخۀ محلی\n' + '─'.repeat( 78 ) );
	const br  = await chromium.launch();
	const ctx = await br.newContext( { viewport: { width: 1440, height: 900 } } );
	const p   = await ctx.newPage();

	/* ── صفحۀ اول ۱۴۴۰ ── */
	await go( p, BASE + '/' );
	await homeReady( p );
	const home = await p.evaluate( () => {
		const box = s => { const e = document.querySelector( s ); if ( ! e ) { return null; } const r = e.getBoundingClientRect(); return { l: r.left, r: r.right, w: r.width, h: r.height, t: r.top + scrollY }; };
		const all = s => Array.prototype.slice.call( document.querySelectorAll( s ) );
		const exp = all( '.tp-home-exp .tp-home-guide__a' ).map( e => Math.round( e.getBoundingClientRect().top + scrollY ) );
		const guide = all( '.tp-home-guide--nine .tp-home-guide__a' );
		const srcs = all( '#tp-home-photoslide .tp-home-photo img' ).map( i => ( i.currentSrc || i.src ).replace( /-\d+x\d+(\.\w+)$/, '$1' ) );
		return {
			exp: box( '#tp-home-exp' ), cities: box( '#tp-home-cities' ), ev: box( '#tp-home-events' ), tr: box( '#tp-home-trips' ), se: box( '#tp-home-seasons' ),
			gal: box( '#tp-home-gallery' ), news: box( '#tp-home-news' ), art: box( '#tp-home-articles' ),
			expRows: exp.filter( ( v, i, a ) => a.indexOf( v ) === i ).length, expN: exp.length,
			evN: all( '#tp-home-events li' ).length, trN: all( '#tp-home-trips li' ).length, thumbs: all( '.tp-home-row3 .tp-home-event' ).every( a => !! a.querySelector( '.tp-home-event__thumb' ) ),
			extImg: all( '.tp-home img' ).filter( i => ! /^https?:\/\/topersia\.local\//.test( i.currentSrc || i.src ) && ! /^data:/.test( i.src ) ).length,
			guideN: guide.length, guideRows: guide.map( e => Math.round( e.getBoundingClientRect().top ) ).filter( ( v, i, a ) => a.indexOf( v ) === i ).length,
			guideHrefs: guide.map( a => a.href ),
			dots: all( '.tp-home-dots, [data-tp-dots]' ).length, bar: !! document.querySelector( '#tp-home-gallery .tp-slider-bar' ),
			seasons: all( '.tp-home-season' ).map( e => ( { k: ( e.className.match( /tp-home-season--(\w+)/ ) || [] )[1], x: Math.round( e.getBoundingClientRect().left ), href: e.getAttribute( 'href' ) || '', n: ( e.querySelector( 'small' ) || {} ).textContent || '', svg: !! e.querySelector( 'svg.tp-home-season__art' ) } ) ),
			more: all( '.tp-home-mag .tp-home-col' ).map( c => { const h = c.querySelector( '.tp-section-head' ), m = c.querySelector( '.tp-section-head .tp-more' ); return ! m || ( h && Math.abs( h.getBoundingClientRect().top - m.getBoundingClientRect().top ) < 40 ); } ),
			photos: srcs.length, photosUniq: srcs.filter( ( v, i, a ) => a.indexOf( v ) === i ).length,
			sw: document.documentElement.scrollWidth, cw: document.documentElement.clientWidth,
		};
	} );
	t( 'بند ۱: [تجربه‌ها | مقاصد] نسبت ۱+۲، تجربه‌ها در آغاز سطر (راست)', !! home.exp && !! home.cities && near( home.cities.w / home.exp.w, 2, 0.15 ) && home.exp.l > home.cities.l, JSON.stringify( [ home.exp, home.cities ] ) );
	t( 'بند ۱: دو ستون هم‌قد', !! home.exp && !! home.cities && near( home.exp.h, home.cities.h, 2 ) );
	t( 'بند ۲: ۸ کارت تجربه در ۲ ردیف', 8 === home.expN && 2 === home.expRows, home.expN + ' کارت · ' + home.expRows + ' ردیف' );
	t( 'بند ۳: رویدادها و سفرها هر کدام ۳ مورد، هر مورد با بندانگشتی', 3 === home.evN && 3 === home.trN && home.thumbs, home.evN + ' · ' + home.trN );
	t( 'بند ۳: ستون فصل پهن‌تر (۱ : ۱ : ۱٫۵) و هر سه هم‌قد', !! home.ev && !! home.tr && !! home.se && near( home.se.w / home.ev.w, 1.5, 0.12 ) && near( home.ev.w, home.tr.w, 2 ) && near( home.ev.h, home.se.h, 2 ) && near( home.tr.h, home.se.h, 2 ),
		JSON.stringify( [ home.ev, home.tr, home.se ] ) );
	t( 'بند ۳: هیچ تصویر بیرونی در صفحۀ اول', 0 === home.extImg, String( home.extImg ) );
	t( 'بند ۴: ترتیب فصل‌ها در fa از راست: بهار، تابستان، پاییز، زمستان — هر کدام با SVG خود افزونه',
		home.seasons.map( s => s.k ).join() === SEAS.join() && home.seasons.every( ( s, i, a ) => ! i || s.x < a[ i - 1 ].x ) && home.seasons.every( s => s.svg ), JSON.stringify( home.seasons.map( s => [ s.k, s.x ] ) ) );
	t( 'بند ۵: کارت فصل فقط به /season/{فصل}/', home.seasons.filter( s => s.href ).length >= 2 && home.seasons.every( s => ! s.href || new RegExp( '/season/' + s.k + '/$' ).test( s.href ) ), JSON.stringify( home.seasons.map( s => s.href ) ) );
	t( 'بند ۶: دقیقاً ۹ آیکون راهنما در یک ردیف', 9 === home.guideN && 1 === home.guideRows, home.guideN + ' · ' + home.guideRows + ' ردیف' );
	t( 'بند ۷: [تصویر | اخبار | مقالات] نسبت ۲+۱+۱ و هم‌قد', !! home.gal && !! home.news && !! home.art && near( home.gal.w / home.news.w, 2, 0.15 ) && near( home.news.w, home.art.w, 2 ) && near( home.gal.h, home.news.h, 2 ) && near( home.art.h, home.news.h, 2 ),
		JSON.stringify( [ home.gal, home.news, home.art ] ) );
	t( 'بند ۷: «مشاهدۀ همه» روبه‌روی عنوان هر ستون', home.more.every( Boolean ) );
	t( 'بند ۸: بی نقطه؛ فلش و نوار پیشرفت هست', 0 === home.dots && home.bar );
	t( 'بند ۸: عکس‌های کلاژ یکتا', home.photos >= 4 && home.photos === home.photosUniq, home.photosUniq + ' از ' + home.photos );
	t( 'صفحۀ اول ۱۴۴۰ بی سرریز افقی', home.sw <= home.cw, home.sw + ' > ' + home.cw );

	/* بند ۹: هاور راهنما و تجربه — بی transform */
	const tilt = [];
	for ( const sel of [ '.tp-home-guide--nine .tp-home-guide__a', '.tp-home-exp .tp-home-guide__a' ] ) {
		const el = p.locator( sel ).first();
		await el.evaluate( e => e.scrollIntoView( { behavior: 'instant', block: 'center' } ) );
		await el.hover();
		await settle( 400 );
		tilt.push( await el.evaluate( e => [ getComputedStyle( e ).transform, getComputedStyle( e.parentNode ).perspective ] ) );
	}
	t( 'بند ۹: هاور راهنما و تجربیات بی کج‌شدن و بی جابه‌جایی', tilt.every( x => 'none' === x[0] && 'none' === x[1] ), JSON.stringify( tilt ) );

	await p.mouse.move( 0, 0 );
	await p.evaluate( () => window.scrollTo( { top: 0, behavior: 'instant' } ) );
	await p.screenshot( { path: path.join( SHOT, 'after-home-1440.png' ), fullPage: true } );

	for ( const u of home.guideHrefs ) {
		t( 'بند ۶: آیکون راهنما ۲۰۰ — ' + decodeURI( new URL( u ).pathname ), 200 === await status( u ) );
	}

	/* ── فصل‌ها: ۲۰۰، محتوای درست، بازنویسی سه زبان ── */
	for ( const s of home.seasons ) {
		if ( ! s.href ) { continue; }
		await go( p, s.href );
		const sp = await p.evaluate( () => ( {
			msg: ( document.querySelector( '[data-tp-news-swap="results"]' ) || { getAttribute: () => '' } ).getAttribute( 'data-tp-news-msg' ) || '',
			tiles: document.querySelectorAll( '.tp-hub-tiles .tp-tile' ).length,
		} ) );
		t( 'بند ۵: /season/' + s.k + '/ — شمار صفحه = شمار کارت صفحۀ اول (' + s.n + ')، کاشی دارد', sp.tiles > 0 && digits( sp.msg ) === digits( s.n ), sp.msg + ' · ' + sp.tiles + ' کاشی' );
		if ( 'spring' === s.k ) {
			await p.screenshot( { path: path.join( SHOT, 'after-season-spring-1440.png' ), fullPage: true } );
		}
	}
	for ( const pre of [ '', '/en', '/ar' ] ) {
		for ( const s of SEAS ) {
			t( 'بند ۵: بازنویسی ' + pre + '/season/' + s + '/ ← ۲۰۰', 200 === await status( BASE + pre + '/season/' + s + '/' ) );
		}
	}
	t( 'بند ۵: نامک فصل ناشناس ← ۴۰۴', 404 === await status1( BASE + '/season/tp-w52-nope/' ) );

	/* ── en: ترتیب فصل‌ها از چپ ── */
	await go( p, BASE + '/en/' );
	await homeReady( p );
	const en = await p.evaluate( () => Array.prototype.map.call( document.querySelectorAll( '.tp-home-season' ), e => [ ( e.className.match( /tp-home-season--(\w+)/ ) || [] )[1], Math.round( e.getBoundingClientRect().left ) ] ) );
	t( 'بند ۴: en — همان ترتیب، از چپ به راست (یا ستون فصل در en نیست)', ! en.length || ( en.map( x => x[0] ).join() === SEAS.join() && en.every( ( x, i, a ) => ! i || x[1] > a[ i - 1 ][1] ) ), JSON.stringify( en ) );

	/* ── ۳۹۰ و ۳۲۰ ── */
	const m = await ( await br.newContext( { viewport: { width: 390, height: 844 } } ) ).newPage();
	await go( m, BASE + '/' );
	await homeReady( m );
	const mob = await m.evaluate( () => {
		const all = s => Array.prototype.slice.call( document.querySelectorAll( s ) );
		const rows = s => all( s ).map( e => Math.round( e.getBoundingClientRect().top + scrollY ) ).filter( ( v, i, a ) => a.indexOf( v ) === i ).length;
		const top = s => { const e = document.querySelector( s ); return e ? e.getBoundingClientRect().top + scrollY : -1; };
		return { seasons: all( '.tp-home-season' ).map( e => ( e.className.match( /tp-home-season--(\w+)/ ) || [] )[1] ), expRows: rows( '.tp-home-exp .tp-home-guide__a' ), guideRows: rows( '.tp-home-guide--nine .tp-home-guide__a' ),
			stack: top( '#tp-home-exp' ) < top( '#tp-home-cities' ) && top( '#tp-home-gallery' ) < top( '#tp-home-news' ) && top( '#tp-home-news' ) < top( '#tp-home-articles' ),
			sw: document.documentElement.scrollWidth, cw: document.documentElement.clientWidth };
	} );
	t( '۳۹۰: ردیف‌ها زیر هم، تجربه‌ها پیش از مقاصد', mob.stack );
	t( '۳۹۰: تجربه‌ها ۲×۴ و راهنما ۳×۳', 4 === mob.expRows && 3 === mob.guideRows, mob.expRows + ' · ' + mob.guideRows );
	t( '۳۹۰: فصل‌ها همان ترتیب', mob.seasons.join() === SEAS.join(), mob.seasons.join() );
	t( '۳۹۰: بی سرریز افقی', mob.sw <= mob.cw, mob.sw + ' > ' + mob.cw );
	await m.evaluate( () => window.scrollTo( { top: 0, behavior: 'instant' } ) );
	await m.screenshot( { path: path.join( SHOT, 'after-home-390.png' ), fullPage: true } );
	await m.setViewportSize( { width: 320, height: 720 } );
	await settle( 600 );
	const m320 = await m.evaluate( () => [ document.documentElement.scrollWidth, document.documentElement.clientWidth ] );
	t( '۳۲۰: بی سرریز افقی', m320[0] <= m320[1], m320.join( ' > ' ) );
	await m.setViewportSize( { width: 390, height: 844 } );

	/* ── بند ۸: چرخش روزانه روی استخر واقعی (جهش تاریخ) ── */
	const rot = wp( '$real = topersia_g_home_real( array_keys( topersia_g_hub_registry() ), -1, false ); $own = topersia_g_home_photo_pool( $real );'
		+ ' $a = topersia_g_home_photo_order( $own, "2026-11-16" ); $b = topersia_g_home_photo_order( $own, "2026-11-16" ); $c = topersia_g_home_photo_order( $own, "2026-11-17" );'
		+ ' echo wp_json_encode( array( "pool" => count( $own ), "same" => $a === $b, "next" => $a !== $c, "uniq" => count( array_unique( array_column( $a, 1 ) ) ) === count( $a ), "n" => count( $a ),'
		+ ' "demo" => count( array_filter( $own, function ( $o ) { return metadata_exists( "post", $o, "_tp_sample" ); } ) ), "ttl" => topersia_g_home_photo_ttl() ) );' );
	t( 'بند ۸: همان روز همان ترکیب؛ روز بعد ترکیب دیگر (استخر واقعی)', !! rot && rot.same && rot.next && rot.uniq && rot.n >= 4, JSON.stringify( rot ) );
	t( 'بند ۸: استخر فقط از موجودیت‌های غیرنمونه؛ کش تا نیمه‌شب (≤ یک روز)', !! rot && 0 === rot.demo && rot.ttl > 0 && rot.ttl <= 86400, JSON.stringify( rot ) );

	/* ── بند ۱۲: بی ردیف فیلتر ── */
	for ( const u of [ '/province/', '/tags/desert-tours/', '/season/spring/', '/articles/' ] ) {
		await go( p, BASE + u );
		await settle( 400 );
		const bar = await p.evaluate( () => ( {
			sort: !! document.querySelector( 'main select[name="tp_sort"]' ), go: !! document.querySelector( 'main .tp-news-filter__go button[type="submit"]' ),
			view: !! document.querySelector( 'main [data-tp-news-view] [data-tp-view="grid"]' ) && !! document.querySelector( 'main [data-tp-news-view] [data-tp-view="list"]' ),
			extra: document.querySelectorAll( 'main input[type="search"], main input[name="tp_q"], main input[name="tp_loc"], main select[name="season"], main select[name="tp_kind"], main select[name="tp_when"]' ).length,
		} ) );
		t( 'بند ۱۲: ' + u + ' — مرتب‌سازی + «نمایش» + شبکه/فهرست، بی جست‌وجو/مکان/فصل', bar.sort && bar.go && bar.view && 0 === bar.extra, JSON.stringify( bar ) );
	}
	await go( p, BASE + '/news/' );
	t( 'بند ۱۲: /news/ سراسری فیلترهای خودش را دارد', await p.evaluate( () => !! document.querySelector( 'main input[name="tp_q"]' ) && !! document.querySelector( 'main select[name="tp_when"]' ) ) );

	/* ── بند ۱۳: زمان فیلتر اخبار — قطعه یک بار از وردپرس ساخته می‌شود، سپس کلیک از مسیر سبک ── */
	const topic = await p.evaluate( () => { const a = document.querySelector( '[data-tp-news-swap="chips"] a[data-tp-topic]:not([aria-current]):not([data-tp-topic=""])' ); return a ? a.getAttribute( 'data-tp-topic' ) : ''; } );
	const frag  = await p.evaluate( () => ( document.querySelector( '.tp-news-page' ) || { getAttribute: () => '' } ).getAttribute( 'data-tp-news-frag' ) || '' );
	t( 'بند ۱۳: صفحۀ /news/ نشانی مسیر سبک را اعلام می‌کند و موضوعی برای کلیک هست', /fragment\.php$/.test( frag ) && '' !== topic, frag + ' · ' + topic );
	if ( topic && frag ) {
		await status( BASE + '/news/?tp_partial=1&tp_topic=' + encodeURIComponent( topic ) );   /* پرکردن کش */
		await go( p, BASE + '/news/' );
		const seen = [];
		p.on( 'response', r => {
			const u = decodeURIComponent( r.url() );
			if ( u.includes( 'tp_topic=' + topic ) && ( /fragment\.php/.test( u ) || /tp_partial=1/.test( u ) ) ) {
				seen.push( [ /fragment\.php/.test( u ) ? 'frag' : 'wp', r.status(), Math.round( r.request().timing().responseStart ) ] );
			}
		} );
		const tm = await p.evaluate( tp => new Promise( res => {
			const out = {}, of = window.fetch;
			const root = document.querySelector( '[data-tp-news-swap="results"]' ).parentNode;
			new MutationObserver( () => { if ( ! out.swap ) { out.swap = performance.now(); setTimeout( () => res( out ), 80 ); } } ).observe( root, { childList: true } );
			window.fetch = function () { return of.apply( this, arguments ).then( r => { r.clone().text().then( () => { if ( ! out.body ) { out.body = performance.now(); } } ); return r; } ); };
			const a = document.querySelector( '[data-tp-news-swap="chips"] a[data-tp-topic="' + tp + '"]' );
			a.scrollIntoView( { behavior: 'instant', block: 'center' } );
			out.click = performance.now();
			a.click();
			setTimeout( () => res( out ), 30000 );
		} ), topic );
		await settle( 300 );
		const hit = seen.find( x => 'frag' === x[0] && 200 === x[1] );
		t( 'بند ۱۳: پاسخ قطعه از مسیر سبک، < ۳۰۰ms', !! hit && hit[2] < 300, JSON.stringify( seen ) );
		t( 'بند ۱۳: کلیک تا جایگزینی < ۴۰۰ms و جایگزینی < ۱۰۰ms پس از پاسخ', !! tm.swap && tm.swap - tm.click < 400 && ( ! tm.body || tm.swap - tm.body < 100 ),
			JSON.stringify( { clickToSwap: tm.swap ? Math.round( tm.swap - tm.click ) : null, afterBody: tm.body && tm.swap ? Math.round( tm.swap - tm.body ) : null } ) );
		t( 'بند ۱۳: تراشۀ کلیک‌شده فعال و نشانی به‌روز', await p.evaluate( tp => !! document.querySelector( '[data-tp-news-swap="chips"] a[aria-current="page"][data-tp-topic="' + tp + '"]' ) && new URL( location.href ).searchParams.get( 'tp_topic' ) === tp, topic ) );
		t( 'بند ۱۳: جست‌وجوی آزاد در مسیر سبک کش نمی‌شود (۲۰۴)', 204 === await status1( frag + '?u=' + encodeURIComponent( '/news/?tp_q=x' ) ) );
	}

	/* ── بندهای ۱۴ تا ۱۸: صفحۀ تکی ── */
	await go( p, BASE + '/province/isfahan/news/' );
	const one = await p.evaluate( () => { const a = Array.prototype.find.call( document.querySelectorAll( '.tp-news-card__title a' ), x => ! /demo-/.test( x.href ) ); return a ? a.href : ''; } );
	for ( const [ kind, u ] of [ [ 'خبر', one ], [ 'مقاله', BASE + '/articles/demo-article-1/' ] ] ) {
		if ( ! u ) { t( 'صفحۀ تکی ' + kind + ' پیدا شد', false ); continue; }
		await go( p, u );
		await settle( 800 );
		const s = await p.evaluate( () => {
			const box = q => { const e = document.querySelector( q ); if ( ! e ) { return null; } const r = e.getBoundingClientRect(); return { l: r.left, r: r.right, t: r.top + scrollY, b: r.bottom + scrollY, w: r.width }; };
			const ml = document.querySelector( '.tp-news-metaline' );
			const kids = ml ? Array.prototype.slice.call( ml.children ) : [];
			return { main: box( '.tp-news-main' ), aside: box( '.tp-news-aside' ), rel: box( '.tp-news-related' ), wrap: box( '.tp-news-cols' ),
				order: Array.prototype.map.call( document.querySelectorAll( '.tp-news-aside > *' ), e => [ e.id || ( e.className.match( /tp-news-box--(\w+)/ ) || [] )[1], Math.round( e.getBoundingClientRect().top + scrollY ) ] ).sort( ( a, b ) => a[1] - b[1] ).map( x => x[0] ),
				meta: ml ? ml.textContent : '', center: ml ? getComputedStyle( ml ).justifyContent : '',
				/* میان هر دو بخش دقیقاً یک جداکننده — تاریخ و زمان مطالعه هرگز چسبیده نیستند */
				seps: kids.length >= 3 && kids.every( ( k, i ) => ( 1 === i % 2 ) === k.classList.contains( 'tp-news-metaline__sep' ) ) && 1 === kids.length % 2,
				srcTop: !! document.querySelector( '.tp-news-hero .tp-news-source' ), body: !! document.querySelector( '.tp-news-main .tp-news-body' ),
				ext: Array.prototype.map.call( document.querySelectorAll( '.tp-news-box--source a' ), a => a.rel ), rating: /AggregateRating|"@type":\s*"Review"/.test( document.documentElement.innerHTML ) };
		} );
		t( kind + ' — بند ۱۴: دو ستون، ستون اصلی راست و ستون کناری چپ', !! s.main && !! s.aside && s.main.l > s.aside.r - 1 && s.main.w > s.aside.w, JSON.stringify( [ s.main, s.aside ] ) );
		const want = [ 'tp-actions', 'topics', 'places', 'latest', 'source' ].filter( k => s.order.includes( k ) );
		t( kind + ' — بند ۱۵: ترتیب ستون کناری (اشتراک · موضوع و برچسب · مکان‌ها · تازه‌ترین · منبع؛ بی‌داده پنهان)', 'tp-actions' === s.order[0] && s.order.join() === want.join(), s.order.join() );
		t( kind + ' — بند ۱۶: سطر فراداده وسط‌چین، جداکننده میان هر دو بخش؛ منبع بالای متن نیست', 'center' === s.center && s.seps && ! s.srcTop, s.meta );
		t( kind + ' — بند ۱۷: متن در ستون اصلی؛ بی AggregateRating و Review', s.body && ! s.rating );
		t( kind + ' — بند ۱۸: مرتبط‌ها (اگر هست) تمام‌عرض زیر هر دو ستون', ! s.rel || ( s.rel.t >= Math.max( s.main.b, s.aside.b ) - 1 && near( s.rel.w, s.wrap.w, 2 ) ), JSON.stringify( s.rel ) );
		t( kind + ' — بند ۱۵: پیوند منبع با rel مناسب', s.ext.every( r => /nofollow/.test( r ) && /noopener/.test( r ) ), s.ext.join( ' | ' ) );
		if ( 'خبر' === kind ) {
			await p.evaluate( () => window.scrollTo( { top: 0, behavior: 'instant' } ) );
			await p.screenshot( { path: path.join( SHOT, 'after-news-single-1440.png' ), fullPage: true } );
		}
		await go( m, u );
		await settle( 800 );
		const sm = await m.evaluate( () => { const a = document.querySelector( '.tp-news-aside' ), b = document.querySelector( '.tp-news-body' ); return { below: !! a && !! b && a.getBoundingClientRect().top >= b.getBoundingClientRect().bottom - 1, sw: document.documentElement.scrollWidth, cw: document.documentElement.clientWidth }; } );
		t( kind + ' — بند ۱۴ در ۳۹۰: ستون کناری زیر متن، بی سرریز', sm.below && sm.sw <= sm.cw, JSON.stringify( sm ) );
	}

	/* ── بند ۱۱: مقاله‌ها ── */
	for ( const u of [ '/articles/', '/en/articles/', '/ar/articles/', '/province/isfahan/articles/' ] ) {
		t( 'بند ۱۱: ' + u + ' ← ۲۰۰', 200 === await status( BASE + u ) );
	}
	await go( p, BASE + '/articles/' );
	const arch = await p.evaluate( () => ( { h1: ( document.querySelector( 'h1' ) || {} ).textContent || '', cards: document.querySelectorAll( '.tp-news-card' ).length, badge: document.querySelectorAll( '.tp-news-card .tp-demo-badge' ).length,
		robots: ( document.querySelector( 'meta[name="robots"]' ) || {} ).content || '' } ) );
	t( 'بند ۱۱: /articles/ — H1 «مقالات گردشگری ایران»، ۸ مقالۀ نمایشی با نشان، noindex در پیش‌نمایش', /مقالات گردشگری/.test( arch.h1 ) && 8 === arch.cards && 8 === arch.badge && /noindex/.test( arch.robots ), JSON.stringify( arch ) );

	console.log( '─'.repeat( 78 ) + '\n  گذشته: ' + pass + '   افتاده: ' + fail + '\n  عکس: ' + SHOT );
	await br.close();
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
