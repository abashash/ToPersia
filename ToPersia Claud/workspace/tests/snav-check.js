#!/usr/bin/env node
/**
 * هدر چسبان و نوار بخش‌ها — در مرورگر، نه با خواندنِ متن.  P5-D223 · ۴٫۲٫۱ بند ۳
 *
 * چرا مرورگر: هر سه باگی که این راننده می‌پاید رفتارند، نه متن.
 *   فاصله میان هدر و نوار     فقط با اندازه‌گیریِ واقعیِ هدرِ وودمارت دیده می‌شود
 *   پرشِ صفحه هنگام اسکرول    `scrollIntoView` در نوار چسبان صفحه را عمودی می‌کشید
 *   لنگر بخش زیر نوار         `scroll-margin` باید «هدر + نوار» باشد، نه عدد مرجع
 *   مودالِ فهرست              باز، فوکوس، قفل، جست‌وجوی فارسی، دام Tab، Esc  P5-D221
 *
 * هدرِ وودمارت در صفحهٔ آزمون نیست، پس همان شکلی که وودمارت می‌سازد
 * این‌جا ساخته می‌شود: `.whb-header` که با اسکرول `.whb-sticked` می‌گیرد و
 * نسخهٔ دومِ `.whb-clone` که با گذار از بالا می‌لغزد. نوار مدیریت وردپرس
 * (`#wpadminbar`) هم جدا سنجیده می‌شود.
 *
 * موردِ شاهد دارد: بی‌هدر، بالای نوار باید صفر باشد — وگرنه آزمونی که
 * همیشه ۶۴ ببیند هم سبز می‌داد.  P5-D193
 *
 * اجرا:  node tests/snav-check.js <chrome> <file-url صفحهٔ استان>
 */

'use strict';

const { launch } = require( './cdp.js' );

/* داور لایهٔ بیرونی (P5-D228) — جدا تا پیش از هر صفحه روی نمونه‌های شناخته‌شده کالیبره شود (دستهٔ ۲۶ بند ۲). */
function portalOk( r ) {
	const kinds = [ r.mnav, r.sheet, r.modals ];
	const total = kinds.reduce( ( a, k ) => a + k.n, 0 );
	if ( ! total ) { return ! r.exists; }
	return r.exists && 'contents' === r.display && r.pageFirst && kinds.every( k => k.n === k.in );
}
{
	const k = ( n, i ) => ( { n, in: i } );
	const ok = { exists: true, display: 'contents', pageFirst: true, mnav: k( 1, 1 ), sheet: k( 1, 1 ), modals: k( 6, 6 ) };
	const cases = [
		[ 'کامل', ok, true ],
		[ 'بی مودال (برنامه/ثبت)', Object.assign( {}, ok, { modals: k( 0, 0 ) } ), true ],
		[ 'هیچ (تقویم)', { exists: false, display: '', pageFirst: true, mnav: k( 0, 0 ), sheet: k( 0, 0 ), modals: k( 0, 0 ) }, true ],
		[ 'جهش: مودال بیرون ظرف', Object.assign( {}, ok, { modals: k( 6, 5 ) } ), false ],
		[ 'جهش: نوار بی ظرف', { exists: false, display: '', pageFirst: true, mnav: k( 1, 0 ), sheet: k( 0, 0 ), modals: k( 0, 0 ) }, false ],
		[ 'جهش: ظرف جعبه می‌سازد', Object.assign( {}, ok, { display: 'block' } ), false ],
		[ 'جهش: ظرف تهی', { exists: true, display: 'contents', pageFirst: true, mnav: k( 0, 0 ), sheet: k( 0, 0 ), modals: k( 0, 0 ) }, false ],
	];
	const bad = cases.filter( ( [ , r, want ] ) => portalOk( r ) !== want ).map( c => c[0] );
	if ( bad.length ) { console.error( '  ✖ داور لایهٔ بیرونی نادرست: ' + bad.join( ' · ' ) ); process.exit( 2 ); }
}

const chrome = process.argv[2];
const url    = process.argv[3];

if ( ! chrome || ! url ) {
	process.stderr.write( 'کاربرد: node tests/snav-check.js <chrome> <url>\n' );
	process.exit( 2 );
}

/*
 * صفحهٔ واقعیِ نسخهٔ محلی — ۴٫۲٫۳ بند ۱ · P5-D203.
 * هدر وودمارتِ ساختگی ساخته نمی‌شود: هدر واقعی، اسکرولِ واقعیِ چرخ
 * (`Input.dispatchMouseEvent`)، و دادهٔ خودِ صفحه به‌جای دادهٔ هارنس.
 */
const LIVE = /^https?:\/\//.test( url );

/* ── داخل صفحه ─────────────────────────────────────────────────────── */

const HELPERS = `
	window.__tp = {
		wait: ms => new Promise( r => setTimeout( r, ms ) ),
		page: () => document.querySelector( '.tp-page' ),
		nav:  () => document.querySelector( '.tp-snav' ),
		num:  v => parseFloat( v ) || 0,
		state() {
			const p = this.page(), n = this.nav();
			return {
				y: Math.round( scrollY ),
				top: this.num( p.style.getPropertyValue( '--tp-snav-top' ) ),
				h: this.num( p.style.getPropertyValue( '--tp-snav-h' ) ),
				navTop: Math.round( n.getBoundingClientRect().top * 10 ) / 10,
				stuck: n.classList.contains( 'tp-is-stuck' ),
				display: getComputedStyle( n ).display
			};
		},
		async go( y ) {
			document.documentElement.style.scrollBehavior = 'auto';
			scrollTo( 0, y );
			await this.wait( 600 );
		},
		header( withAdmin ) {
			const bar = withAdmin ? '<div id="wpadminbar" style="position:fixed;top:0;left:0;right:0;height:32px;z-index:99999"></div>' : '';
			const off = withAdmin ? 32 : 0;
			const h = document.createElement( 'div' );
			h.id = 'tp-fake-top';
			h.innerHTML = bar + '<header class="whb-header whb-sticky-clone">'
				+ '<div class="whb-main-header" style="height:90px"></div>'
				+ '<div class="whb-clone whb-main-header" style="position:fixed;top:' + off + 'px;left:0;right:0;height:64px;'
				+ 'transform:translateY(-150%);transition:transform .25s linear;z-index:390"></div></header>';
			document.body.insertBefore( h, document.body.firstChild );
			return h;
		},
		stick( on ) {
			const head = document.querySelector( '.whb-header' ), clone = document.querySelector( '.whb-clone' );
			head.classList.toggle( 'whb-sticked', on );
			clone.classList.toggle( 'whb-sticked', on );
			clone.style.transform = on ? 'none' : 'translateY(-150%)';
		},
		clear() { const h = document.getElementById( 'tp-fake-top' ); if ( h ) { h.remove(); } },
		HEADS: '#wpadminbar, .whb-header, .whb-main-header, .whb-clone, .whb-sticky-row, .site-header, #masthead',
		/* پایینِ پشتهٔ هدرهای ثابت و چسبانِ بیرون از صفحهٔ ما */
		realHeader() {
			const p = this.page();
			const boxes = [].slice.call( document.querySelectorAll( this.HEADS ) )
				.filter( el => ! p.contains( el ) && ! el.closest( '.tp-portal' ) )
				.map( el => {
					const cs = getComputedStyle( el );
					if ( ( 'fixed' !== cs.position && 'sticky' !== cs.position ) || 'none' === cs.display
						|| 'hidden' === cs.visibility || 0 === parseFloat( cs.opacity ) ) { return null; }
					const r = el.getBoundingClientRect();
					return r.height >= 1 && r.bottom > 0 ? r : null;
				} ).filter( Boolean ).sort( ( a, b ) => a.top - b.top );
			let bottom = 0;
			for ( const b of boxes ) { if ( b.top <= bottom + 1 && b.bottom > bottom ) { bottom = b.bottom; } }
			return Math.round( bottom * 10 ) / 10;
		},
		/*
		 * لبهٔ بالای نوار، دیده‌شده — نه حساب‌شده: پیکسلِ درست بالای نوار باید
		 * مال هدر باشد (بی‌فاصله) و پیکسلِ درست زیرش مال خودِ نوار (بی‌رو‌هم).
		 */
		edge() {
			const n = this.nav(), r = n.getBoundingClientRect(), x = Math.round( innerWidth / 2 );
			const above = r.top > 1 ? document.elementFromPoint( x, r.top - 1 ) : null;
			const below = document.elementFromPoint( x, r.top + 2 );
			return {
				y: Math.round( scrollY ), navTop: Math.round( r.top * 10 ) / 10, header: this.realHeader(),
				stuck: n.classList.contains( 'tp-is-stuck' ),
				aboveIsHeader: r.top <= 1 || !! ( above && above.closest( this.HEADS ) ),
				belowIsNav: !! ( below && below.closest( '.tp-snav' ) )
			};
		}
	};
	true;
`;

/* ── سنجه‌ها ───────────────────────────────────────────────────────── */

const rows = [];
let bad = 0;

function check( label, ok, got ) {
	rows.push( ( ok ? '  ✅ ' : '  ❌ ' ) + label + ( ok ? '' : '   ← ' + got ) );
	if ( ! ok ) { bad++; }
}

const near = ( a, b, tol ) => Math.abs( a - b ) <= ( tol || 1 );

( async () => {

	let browser;

	try {
		browser = await launch( chrome );
	} catch ( e ) {
		process.stderr.write( 'کروم بالا نیامد: ' + e.message + '\n' );
		process.exit( 2 );
	}

	try {

		for ( const vp of [ { w: 1440, h: 900, name: 'دسکتاپ ۱۴۴۰' }, { w: 390, h: 844, name: 'موبایل ۳۹۰', mobile: true } ] ) {

			const page = await browser.open( 'about:blank' );

			await page.send( 'Emulation.setDeviceMetricsOverride', {
				width: vp.w, height: vp.h, deviceScaleFactor: 1, mobile: !! vp.mobile
			} );
			await page.send( 'Emulation.setFocusEmulationEnabled', { enabled: true } );

			/*
			 * عکس‌های دادهٔ آزمون نشانیِ ساختگی دارند و بار نمی‌شوند؛ عکسی که
			 * بار نشده بلندیِ صفر دارد و هر سنجهٔ چیدمانِ نمای تمام‌صفحه
			 * بی‌معنا سبز می‌شد. پس هر درخواستِ عکس یک SVGِ واقعیِ ۱۲۰۰×۸۰۰
			 * می‌گیرد — همان نسبتی که عکسِ زنده دارد.
			 */
			if ( ! LIVE ) {
				const SVG = Buffer.from( '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800">'
					+ '<rect width="1200" height="800" fill="#7a8f86"/></svg>' ).toString( 'base64' );
				page.on( 'Fetch.requestPaused', ev => {
					page.send( 'Fetch.fulfillRequest', {
						requestId: ev.requestId, responseCode: 200, body: SVG,
						responseHeaders: [ { name: 'Content-Type', value: 'image/svg+xml' } ]
					} ).catch( () => {} );
				} );
				await page.send( 'Fetch.enable', { patterns: [ { urlPattern: '*example.test*' }, { urlPattern: '*wp-content/uploads*' } ] } );
			}

			/* هیچ درخواستی به سایت زنده — قلم‌های مرجع نشانیِ topersia.com دارند.  ۴٫۲٫۲ بند ۰ */
			if ( LIVE ) {
				await page.send( 'Network.enable' );
				await page.send( 'Network.setBlockedURLs', { urls: [ '*://topersia.com/*', '*://www.topersia.com/*' ] } );
			}

			if ( LIVE && vp.mobile ) {
				await page.send( 'Emulation.setTouchEmulationEnabled', { enabled: true, maxTouchPoints: 5 } );
			}

			await page.navigate( url, LIVE ? 120000 : 15000 );
			await page.evaluate( HELPERS );
			if ( LIVE ) {
				await page.waitFor( 'document.fonts && document.fonts.status === "loaded"', 20000 );
				await page.evaluate( '__tp.wait( 800 )' );
			}

			rows.push( '\n  ' + vp.name );

			/* صحنه‌ای که پهنای خواسته را نگرفت، هر سنجهٔ موبایل را بی‌معنا می‌کند —
			   بی `meta viewport` کرومِ موبایل‌نما ۹۸۰ فرض می‌کند و همه سبز می‌شد. */
			const width = await page.evaluate( 'innerWidth' );
			check( 'پهنای صحنه ' + vp.w + ' است', width === vp.w, width );

			/* ── لایهٔ بیرونی — P5-D228 ── */
			const portal = await page.evaluate( `(() => {
				const p = document.querySelector( 'body > .tp-page.tp-portal' );
				const kind = sel => { const a = [].slice.call( document.querySelectorAll( sel ) ); return { n: a.length, in: a.filter( el => !! p && p.contains( el ) ).length }; };
				return {
					exists: !! p, display: p ? getComputedStyle( p ).display : '',
					mnav: kind( '.tp-mnav' ), sheet: kind( '.tp-sheet' ), modals: kind( '.tp-modal, .tp-gallery-modal' ),
					pageFirst: document.querySelector( '.tp-page' ) !== p
				};
			})()` );
			/* صفحهٔ کم‌بخش (کمتر از دو بخش در ناوبری) نوار پایین و برگه ندارد و بی فهرست/گالری مودال ندارد —
			   پس ادعا «هر چه هست در ظرف» است، نه «همه هست» (دستهٔ ۲۶ بند ۲). */
			check( 'لایهٔ بیرونی: هر نوار پایین، برگهٔ بخش‌ها و مودالی که صفحه دارد در ظرفی در body — display:contents؛ بی هیچ‌کدام، بی ظرف',
				portalOk( portal ), JSON.stringify( portal ) );

			if ( vp.mobile ) {
				await mobileChecks( page );
			} else {
				await desktopChecks( page );
			}

			/* دکمهٔ «بالا»ی وودمارت */
			const stt = await page.evaluate( `(() => {
				const b = document.createElement( 'a' ); b.className = 'scrollToTop'; document.body.appendChild( b );
				const d = getComputedStyle( b ).display; b.remove(); return d;
			})()` );
			check( vp.mobile ? 'دکمهٔ scrollToTop وودمارت روی موبایل پنهان' : 'دکمهٔ scrollToTop روی دسکتاپ دست‌نخورده',
				vp.mobile ? 'none' === stt : 'none' !== stt, stt );

			await commonChecks( page, vp );

			await page.close();
		}

	} catch ( e ) {
		rows.push( '  ⚠ ' + e.message );
		bad++;
	} finally {
		await browser.close();
	}

	process.stdout.write( '\nهدر چسبان، نوار بخش‌ها، ناوبری موبایل، اسلایدر و گالری — P5-D223 · P5-D221 · P5-D243 · P5-D228\n' + '─'.repeat( 78 ) + rows.join( '\n' ) + '\n' );
	process.exit( bad ? 1 : 0 );
} )();

/*
 * ═══ دسکتاپ روی صفحهٔ واقعی — هدر واقعیِ وودمارت، اسکرولِ واقعیِ چرخ ═══
 * ۴٫۲٫۲ بند ۳: «پرش صفحه هنگام اسکرول به بالا باید در اسکرول واقعی صفر
 * باشد.» هر گامِ چرخ باید دقیقاً همان اندازه صفحه را جابه‌جا کند؛ هر گامی
 * که بیش یا کم برود، پرش است. در هر گام نوار باید درست زیرِ هدر باشد —
 * دیده‌شده با `elementFromPoint`، نه فقط حساب‌شده.
 */
async function liveDesktopChecks( page ) {

	const top0 = await page.evaluate( '(async () => { await __tp.go( 0 ); return __tp.state(); })()' );
	check( 'بالای صفحه: نوار نچسبیده', false === top0.stuck, JSON.stringify( top0 ) );
	check( 'دسکتاپ: نوار بخش‌ها دیده می‌شود', 'none' !== top0.display, top0.display );

	const wheel = dy => page.send( 'Input.dispatchMouseEvent', { type: 'mouseWheel', x: 720, y: 450, deltaX: 0, deltaY: dy } );

	for ( let i = 0; i < 14; i++ ) { await wheel( 300 ); await page.evaluate( '__tp.wait( 140 )' ); }
	await page.evaluate( '__tp.wait( 1000 )' );

	const down = await page.evaluate( '__tp.edge()' );
	/* هدرِ وودمارت (`whb-scroll-stick`) رو به پایین پنهان می‌شود؛ پس بالای نوار
	   همان «پایینِ هدر»ی است که هست — صفر هم درست است. */
	check( 'اسکرول واقعی رو به پایین: نوار چسبیده، درست زیرِ هدرِ واقعی — نه فاصله نه رو‌هم',
		down.stuck && near( down.navTop, down.header, 1 ) && down.aboveIsHeader && down.belowIsNav, JSON.stringify( down ) );

	const steps = [];
	for ( let i = 0; i < 8; i++ ) {
		const y0 = await page.evaluate( 'Math.round( scrollY )' );
		await wheel( -120 );
		await page.evaluate( '__tp.wait( 500 )' );
		const e = await page.evaluate( '__tp.edge()' );
		steps.push( { d: e.y - y0, header: e.header, gap: Math.round( ( e.navTop - e.header ) * 10 ) / 10, seen: e.aboveIsHeader && e.belowIsNav, stuck: e.stuck } );
	}
	const base  = steps[0].d;
	const jumps = steps.filter( s => Math.abs( s.d - base ) > 2 );
	check( 'اسکرول واقعی رو به بالا: پرش صفحه صفر — هر گامِ چرخ همان اندازه  ۴٫۲٫۲ بند ۳',
		base < 0 && ! jumps.length, JSON.stringify( steps.map( s => s.d ) ) );
	check( 'اسکرول واقعی رو به بالا: نوار در هر گام درست زیرِ هدر — نه فاصله نه رو‌هم',
		steps.every( s => ! s.stuck || ( Math.abs( s.gap ) <= 1 && s.seen ) ), JSON.stringify( steps ) );
	/* شاهد: هدرِ واقعی دست‌کم در یک گام دیده شد — وگرنه «زیرِ هدر» چیزی نسنجیده.  P5-D193 */
	/*
	 * هدرِ وودمارت فقط وقتی می‌چسبد که دست‌کم یک ردیفش `whb-sticky-row` باشد
	 * (یا نسخهٔ `whb-sticky-clone`). روی نسخهٔ محلی — همان تنظیمِ سایت زنده —
	 * هر دو ردیف `whb-not-sticky-row` اند و هدر اصلاً نمی‌چسبد؛ آن‌وقت «زیرِ
	 * هدر» یعنی لبهٔ بالای صفحه و شاهد بی‌موضوع است. حالتِ هدرِ چسبان را
	 * هارنس با هدرِ وودمارت‌شکل می‌سنجد.
	 */
	const canStick = await page.evaluate( `!! document.querySelector( '.whb-header .whb-sticky-row, .whb-sticky-clone' )` );
	if ( canStick ) {
		check( 'شاهد: هدرِ چسبانِ وودمارت در اسکرولِ رو به بالا واقعاً پیدا شد',
			steps.some( s => s.header > 0 ), JSON.stringify( steps.map( s => s.header ) ) );
	} else {
		rows.push( '  · هدر: هیچ ردیفِ هدرِ وودمارت چسبان نیست (whb-not-sticky-row) — هدر با صفحه می‌رود و نوار به لبهٔ بالا می‌چسبد' );
	}

	/* لنگر: کلیک روی پیوندِ نوار، بالای بخش = هدر + نوار */
	const anchor = await page.evaluate( `(async () => {
		document.documentElement.style.scrollBehavior = 'auto';
		/* بخشی که زیرش جا هست تا به بالا برسد — نزدیکِ ته صفحه نمی‌تواند */
		const fits = a => { const t = document.getElementById( a.getAttribute( 'href' ).slice( 1 ) );
			return t && t.getBoundingClientRect().top + scrollY + innerHeight + 200 < document.documentElement.scrollHeight; };
		const links = [].slice.call( __tp.nav().querySelectorAll( 'a' ) );
		const a = links.slice( 2 ).filter( fits )[0] || links[1];
		const id = a.getAttribute( 'href' ).slice( 1 );
		a.click(); await __tp.wait( 1200 );
		const s = __tp.state();
		s.header = __tp.realHeader();
		const tgt = document.getElementById( id );
		s.sectionTop = Math.round( tgt.getBoundingClientRect().top * 10 ) / 10;
		s.id = id;
		/* بخشِ ستون کناریِ چسبان (۲و) به بالای نوار نمی‌رسد؛ شرط: در دید، زیر نوار */
		s.pinned = false;
		for ( let p = tgt; p && p !== document.body; p = p.parentElement ) { if ( 'sticky' === getComputedStyle( p ).position ) { s.pinned = true; } }
		s.visible = tgt.getBoundingClientRect().top >= s.top + s.h - 2 && tgt.getBoundingClientRect().bottom <= innerHeight;
		return s;
	})()` );
	check( 'پیوند نوار: بالای بخش درست زیر نوار — هدرِ واقعی + نوار',
		near( anchor.top, anchor.header, 1 ) && ( anchor.pinned ? anchor.visible : near( anchor.sectionTop, anchor.header + anchor.h, 1.5 ) ), JSON.stringify( anchor ) );
}

/* ═══ دسکتاپ — نوار بخش‌ها و هدر چسبان  P5-D223 ═══════════════════════ */
async function desktopChecks( page ) {

	/* صفحهٔ کم‌بخش (کمتر از دو بخش در ناوبری) نه نوار بخش‌ها دارد نه نوار پایین — هر دو از یک فهرست
	   (`count( $nav ) > 1`)؛ پس سنجه‌های ناوبری بی‌موضوع‌اند، ولی یکی بی دیگری ایراد است (دستهٔ ۲۶ بند ۲). */
	const navHere = await page.evaluate( '[ !! document.querySelector( ".tp-snav" ), !! document.querySelector( ".tp-mnav" ) ]' );
	check( 'نوار بخش‌ها و نوار پایین با هم: هر دو یا هیچ‌کدام (کمتر از دو بخش)', navHere[0] === navHere[1], JSON.stringify( navHere ) );
	if ( ! navHere[0] ) {
		rows.push( '  · دسکتاپ: این صفحه کمتر از دو بخش دارد و نوار بخش‌ها ندارد — سنجه‌های نوار و هدر چسبان بی‌موضوع' );
		return;
	}

	if ( LIVE ) {
		return liveDesktopChecks( page );
	}

			/* شاهد: بی‌هدر، بالای نوار صفر و بی‌فاصله */
			const top0 = await page.evaluate( '(async () => { await __tp.go( 0 ); return __tp.state(); })()' );
			check( 'بالای صفحه: نوار نچسبیده', false === top0.stuck, JSON.stringify( top0 ) );
			check( 'دسکتاپ: نوار بخش‌ها دیده می‌شود', 'none' !== top0.display, top0.display );

			const deep0 = await page.evaluate( '(async () => { await __tp.go( 2600 ); return __tp.state(); })()' );
			check( 'بی‌هدر: نوار چسبیده، درست روی لبهٔ بالا', deep0.stuck && 0 === deep0.top && near( deep0.navTop, 0 ), JSON.stringify( deep0 ) );
			check( 'بلندی نوار اندازه گرفته شد', deep0.h > 20, JSON.stringify( deep0 ) );

			/* هدرِ وودمارت که با اسکرول می‌لغزد */
			const glued = await page.evaluate( `(async () => {
				__tp.header( false ); await __tp.go( 2650 );
				__tp.stick( true ); await __tp.wait( 700 );
				const s = __tp.state();
				s.cloneBottom = document.querySelector( '.whb-clone' ).getBoundingClientRect().bottom;
				return s;
			})()` );
			check( 'هدر چسبید: بالای نوار = پایینِ واقعیِ هدر (۶۴)، بی‌فاصله',
				64 === glued.top && near( glued.navTop, glued.cloneBottom ), JSON.stringify( glued ) );

			const gone = await page.evaluate( '(async () => { __tp.stick( false ); await __tp.wait( 700 ); return __tp.state(); })()' );
			check( 'هدر پنهان شد (پنهان‌هنگام‌اسکرول): نوار دوباره به لبهٔ بالا برمی‌گردد',
				0 === gone.top && near( gone.navTop, 0 ), JSON.stringify( gone ) );

			await page.evaluate( '__tp.clear()' );

			/*
			 * هدری که **وسطِ** دورِ اندازه‌گیری ساخته می‌شود — همان کاری که
			 * وودمارت با `.whb-clone` هنگام اسکرول می‌کند. دورِ جاری فهرستِ
			 * کهنه داشت و نوار زیر هدر می‌ماند؛ آزمونِ بالا هدر را بعد از
			 * پایانِ دور می‌افزود و این را نمی‌دید.
			 */
			const midLoop = await page.evaluate( `(async () => {
				document.documentElement.style.scrollBehavior = 'auto';
				scrollTo( 0, 2500 ); await __tp.wait( 40 );
				scrollTo( 0, 2520 );
				const h = document.createElement( 'div' ); h.id = 'tp-fake-top';
				h.innerHTML = '<header class="whb-header whb-sticked"><div class="whb-clone whb-main-header whb-sticked" style="position:fixed;top:0;left:0;right:0;height:64px;z-index:390"></div></header>';
				document.body.insertBefore( h, document.body.firstChild );
				scrollTo( 0, 2540 );
				await __tp.wait( 700 );
				const s = __tp.state(); __tp.clear(); scrollTo( 0, 2560 ); await __tp.wait( 600 );
				return s;
			})()` );
			check( 'هدری که وسطِ اسکرول ساخته می‌شود هم دیده می‌شود — نوار زیرش نمی‌ماند',
				64 === midLoop.top && near( midLoop.navTop, 64 ), JSON.stringify( midLoop ) );

			/* نوار مدیریت + هدر */
			const admin = await page.evaluate( `(async () => {
				__tp.header( true ); await __tp.go( 2700 );
				__tp.stick( true ); await __tp.wait( 700 );
				return __tp.state();
			})()` );
			check( 'با نوار مدیریت: ۳۲ + ۶۴ = ۹۶', 96 === admin.top && near( admin.navTop, 96 ), JSON.stringify( admin ) );

			/* لنگر: کلیک روی پیوندِ نوار، بالای بخش = هدر + نوار */
			const anchor = await page.evaluate( `(async () => {
				document.documentElement.style.scrollBehavior = 'auto';
				const a = __tp.nav().querySelector( 'a[href="#tp-faq"]' ) || __tp.nav().querySelector( 'a:last-child' );
				const id = a.getAttribute( 'href' ).slice( 1 );
				a.click(); await __tp.wait( 900 );
				const s = __tp.state();
				s.sectionTop = Math.round( document.getElementById( id ).getBoundingClientRect().top * 10 ) / 10;
				s.active = ( __tp.nav().querySelector( '.tp-is-active' ) || {} ).getAttribute && __tp.nav().querySelector( '.tp-is-active' ).getAttribute( 'href' );
				s.id = id;
				return s;
			})()` );
			check( 'پیوند نوار: بالای بخش درست زیر نوار — هدر + نوار',
				near( anchor.sectionTop, anchor.top + anchor.h, 1.5 ), JSON.stringify( anchor ) );
			check( 'پیوند نوار: همان پیوند فعال شد', '#' + anchor.id === anchor.active, JSON.stringify( anchor ) );

			await page.evaluate( '__tp.clear()' );

			/*
			 * بی‌پرش: عوض‌شدنِ پیوند فعال صفحه را عمودی جابه‌جا نمی‌کند.  بند ۳
			 *
			 * نوار عمداً تنگ می‌شود تا سرریز کند؛ وگرنه لغزش افقی لازم نبود
			 * و آزمون چیزی نمی‌سنجید. پیوند فعال در یک گام اسکرولِ کوچک عوض
			 * می‌شود و بعد صفحه نباید از جایی که کاربر گذاشته تکان بخورد.
			 */
			const still = await page.evaluate( `(async () => {
				const strip = __tp.nav().querySelector( '.tp-snav__in' );
				strip.style.maxWidth = '260px';
				await __tp.go( 0 );
				const ids = [].map.call( __tp.nav().querySelectorAll( 'a' ), a => a.getAttribute( 'href' ).slice( 1 ) );
				const last = document.getElementById( ids[ ids.length - 1 ] );
				/* هدف در دسترس — ته صفحه اسکرول کوتاه‌تر است */
				const y = Math.min( Math.round( last.getBoundingClientRect().top + scrollY - 200 ), document.documentElement.scrollHeight - innerHeight - 5 );
				const left0 = strip.scrollLeft;
				await __tp.go( y );
				const y1 = Math.round( scrollY );
				await __tp.wait( 900 );
				const y2 = Math.round( scrollY );
				return { y, y1, y2, overflow: strip.scrollWidth > strip.clientWidth, slid: strip.scrollLeft !== left0,
					active: ( __tp.nav().querySelector( '.tp-is-active' ) || { getAttribute: () => '' } ).getAttribute( 'href' ) };
			})()` );
			check( 'پیوند فعال عوض شد و نوار افقی لغزید', still.overflow && still.slid, JSON.stringify( still ) );
			check( 'صفحه عمودی نپرید — scrollY همان ماند', still.y1 === still.y2 && near( still.y1, still.y, 2 ), JSON.stringify( still ) );
}

/* ═══ موبایل — ناوبری و چیدمان  P5-D228 · ۴٫۲٫۳ بند ۳ و ۴ ═══════════════ */
async function mobileChecks( page ) {

	/* صفحهٔ کم‌بخش (کمتر از دو بخش در ناوبری) نه نوار بخش‌ها دارد نه نوار پایین — هر دو از یک فهرست
	   (`count( $nav ) > 1`)؛ پس سنجه‌های ناوبری بی‌موضوع‌اند، ولی یکی بی دیگری ایراد است (دستهٔ ۲۶ بند ۲). */
	const navHere = await page.evaluate( '[ !! document.querySelector( ".tp-snav" ), !! document.querySelector( ".tp-mnav" ) ]' );
	check( 'نوار بخش‌ها و نوار پایین با هم: هر دو یا هیچ‌کدام (کمتر از دو بخش)', navHere[0] === navHere[1], JSON.stringify( navHere ) );
	navBlock: {
	if ( ! navHere[1] ) {
		rows.push( '  · موبایل: این صفحه کمتر از دو بخش دارد و نوار پایین و برگه ندارد — سنجه‌های ناوبری بی‌موضوع' );
		break navBlock;
	}

	const m0 = await page.evaluate( `(async () => {
		await __tp.go( 0 );
		return {
			snav: getComputedStyle( __tp.nav() ).display,
			h: __tp.page().style.getPropertyValue( '--tp-snav-h' ).trim(),
			mnav: getComputedStyle( document.querySelector( '.tp-mnav' ) ).display
		};
	})()` );
	check( 'موبایل: نوار بخش‌های بالا پنهان و بلندی‌اش صفر — نوار پایین ناوبری است',
		'none' === m0.snav && '0px' === m0.h && 'none' !== m0.mnav, JSON.stringify( m0 ) );

	/*
	 * نوار پایین همیشه ثابت است — حتی وقتی نیای صفحه transform دارد.
	 * کانتینر المنتور گاهی دارد و آن‌جا `fixed` نسبت به همان نیا حساب
	 * می‌شد: نوار با صفحه می‌لغزید. در سه نقطهٔ اسکرول، پایینِ نوار باید
	 * خودِ لبهٔ پایین باشد.
	 */
	/*
	 * صفحهٔ آزمون ظرفی میان body و `.tp-page` ندارد؛ همان ظرفِ المنتور
	 * این‌جا ساخته می‌شود و transform می‌گیرد. شاهد: یک `fixed` درونِ همان
	 * ظرف باید از لبهٔ پایین دور شود — وگرنه transform اثری نداشته و آزمون
	 * چیزی نسنجیده.  P5-D193
	 */
	const fixed = await page.evaluate( `(async () => {
		const p = __tp.page(), host = document.createElement( 'div' );
		host.className = 'e-con';
		p.parentNode.insertBefore( host, p );
		host.appendChild( p );
		host.style.transform = 'translateZ(0)';
		const witness = document.createElement( 'div' );
		witness.style.cssText = 'position:fixed;bottom:0;left:0;width:10px;height:10px';
		p.appendChild( witness );
		/* ۴٫۳٫۰ بند ۱۲: بالای صفحه پنهان، پس از نخستین اسکرول روی لبه و ماندگار —
		   حتی با برگشتن به بالا. بی کلاس tp-js (هارنس، بی‌جاوااسکریپت) از آغاز پیداست. */
		const js = document.documentElement.classList.contains( 'tp-js' );
		const bar = [], wit = [], vis = [];
		for ( const y of [ 0, 900, 2400, 0 ] ) {
			await __tp.go( y );
			await new Promise( r => setTimeout( r, 260 ) );
			const mn = document.querySelector( '.tp-mnav' );
			bar.push( Math.round( innerHeight - mn.getBoundingClientRect().bottom ) );
			vis.push( getComputedStyle( mn ).visibility );
			wit.push( Math.round( innerHeight - witness.getBoundingClientRect().bottom ) );
		}
		witness.remove();
		host.style.transform = '';
		host.parentNode.insertBefore( p, host );
		host.remove();
		return { js, bar, vis, wit };
	})()` );
	const onEdge = fixed.js ? fixed.bar.slice( 1 ) : fixed.bar;
	check( 'موبایل: نوار پایین پس از نخستین اسکرول روی لبهٔ پایین و ماندگار — حتی زیر ظرفی با transform  P5-D228 · ۴٫۳٫۰ بند ۱۲',
		onEdge.every( d => Math.abs( d ) <= 1 ) && ( ! fixed.js || ( fixed.bar[0] < -40 && 'hidden' === fixed.vis[0] && fixed.vis.slice( 1 ).every( v => 'visible' === v ) ) )
		&& fixed.wit.some( d => Math.abs( d ) > 50 ), JSON.stringify( fixed ) );

	/*
	 * برگهٔ «بخش‌ها»: هر آیتم آیکون ثابت بخشش را دارد؛ کلیک روی **برچسب**
	 * (نه خودِ پیوند) هم برگه را می‌بندد؛ بخش درست زیر هدر می‌نشیند، چون
	 * نوار بالا نیست.
	 */
	const sheet = await page.evaluate( `(async () => {
		const live = ${ LIVE };
		if ( live ) { await __tp.go( 2650 ); await __tp.wait( 700 ); }
		else { __tp.header( false ); await __tp.go( 2650 ); __tp.stick( true ); await __tp.wait( 700 ); }
		document.querySelector( '.tp-mnav [data-tp-sheet-open]' ).click(); await __tp.wait( 120 );
		const box = document.getElementById( 'tpSheet' );
		const open = box.hasAttribute( 'open' ) && 'none' !== getComputedStyle( box ).display;
		const items = [].map.call( box.querySelectorAll( '.tp-sheet__p a' ), a => ( {
			href: a.getAttribute( 'href' ), svg: 1 === a.querySelectorAll( ':scope > svg' ).length,
			label: ( a.querySelector( ':scope > span' ) || {} ).textContent || ''
		} ) );
		/* بخشی که زیرش جا هست تا به بالا برسد */
		const fits = a => { const t = document.getElementById( a.getAttribute( 'href' ).slice( 1 ) );
			return t && t.getBoundingClientRect().top + scrollY + innerHeight + 200 < document.documentElement.scrollHeight; };
		const links = [].slice.call( box.querySelectorAll( '.tp-sheet__p a' ) );
		const a = live ? ( links.slice( 2 ).filter( fits )[0] || links[1] )
			: ( box.querySelector( '.tp-sheet__p a[href="#tp-faq"]' ) || box.querySelector( '.tp-sheet__p li:last-child a' ) );
		const id = a.getAttribute( 'href' ).slice( 1 );
		document.documentElement.style.scrollBehavior = 'auto';
		a.querySelector( 'span' ).click(); await __tp.wait( 1200 );
		const out = { open, items, closed: ! box.hasAttribute( 'open' ), id,
			sectionTop: Math.round( document.getElementById( id ).getBoundingClientRect().top * 10 ) / 10,
			header: live ? __tp.realHeader()
				: Math.round( document.querySelector( '.whb-clone' ).getBoundingClientRect().bottom * 10 ) / 10 };
		__tp.clear();
		return out;
	})()` );
	check( 'برگهٔ بخش‌ها: باز می‌شود و هر آیتم آیکون ثابت بخشش را کنار برچسب دارد  P5-D228',
		sheet.open && sheet.items.length >= ( LIVE ? 2 : 8 ) && sheet.items.every( x => x.svg && '' !== x.label ), JSON.stringify( sheet.items.filter( x => ! x.svg || '' === x.label ) ) );
	check( 'برگهٔ بخش‌ها: کلیک روی برچسب برگه را می‌بندد و بخش درست زیر هدر می‌نشیند',
		sheet.closed && near( sheet.sectionTop, sheet.header, 1.5 ), JSON.stringify( { closed: sheet.closed, id: sheet.id, top: sheet.sectionTop, header: sheet.header } ) );
	}

	/* سرفصل‌ها: «همه … ←» در ردیفِ عنوان.  ۴٫۲٫۳ بند ۴ */
	const heads = await page.evaluate( `(async () => {
		await __tp.go( 0 );
		return [].map.call( document.querySelectorAll( '.tp-section-head' ), h => {
			const t = h.querySelector( 'h2' ), m = h.querySelector( '.tp-more' ), l = h.querySelector( '.tp-lead' );
			if ( ! t || ! m || ! t.getClientRects().length ) { return null; }
			const a = t.getBoundingClientRect(), b = m.getBoundingClientRect(), c = l ? l.getBoundingClientRect() : null;
			return { id: ( h.closest( 'section' ) || {} ).id, sameRow: a.top < b.bottom && b.top < a.bottom,
				h2Right: a.left >= b.right - 1, leadBelow: ! c || c.top >= Math.max( a.bottom, b.bottom ) - 1 };
		} ).filter( Boolean );
	})()` );
	if ( LIVE && ! heads.length ) {
		rows.push( '  · موبایل: این صفحه هیچ «همه … ←» ندارد — سنجهٔ سرفصل بی‌موضوع' );
	} else {
		check( 'موبایل: هر «همه … ←» در ردیفِ عنوان — h2 راست، .more چپ، متن راهنما زیرشان',
			heads.length >= ( LIVE ? 1 : 2 ) && heads.every( x => x.sameRow && x.h2Right && x.leadBelow ), JSON.stringify( heads ) );
	}

	/* فاصلهٔ هر دو باکسِ پشت‌سرهم در کل صفحه.  ۴٫۲٫۳ بند ۳ */
	const gaps = await page.evaluate( `(() => {
		const grid = document.querySelector( '.tp-content-grid' );
		const boxes = [].slice.call( grid.querySelectorAll( ':scope > .tp-main-col > *, :scope > .tp-rail > *' ) )
			.filter( el => el.getClientRects().length && 'none' !== getComputedStyle( el ).display )
			.map( el => ( { id: el.id || el.className, r: el.getBoundingClientRect() } ) )
			.filter( b => b.r.height > 0 )
			.sort( ( a, b ) => a.r.top - b.r.top );
		const out = [];
		for ( let i = 1; i < boxes.length; i++ ) {
			out.push( { a: boxes[ i - 1 ].id, b: boxes[ i ].id, gap: Math.round( boxes[ i ].r.top - boxes[ i - 1 ].r.bottom ) } );
		}
		return out;
	})()` );
	if ( LIVE && ! gaps.length ) {
		rows.push( '  · موبایل: صفحه فقط یک باکس دارد — سنجهٔ فاصلهٔ دو باکس بی‌موضوع' );
		return;
	}
	const tight = gaps.filter( g => g.gap < 16 );
	check( 'موبایل: فاصلهٔ هر دو باکسِ پشت‌سرهم دست‌کم ۱۶ پیکسل — کل صفحه',
		gaps.length >= ( LIVE ? 1 : 6 ) && ! tight.length, JSON.stringify( tight.length ? tight : { pairs: gaps.length, min: Math.min.apply( null, gaps.map( g => g.gap ) ) } ) );
}

/* ═══ هر دو اندازه — مودال فهرست، اسلایدر، تمام‌صفحه، گالری، پخش ═══════ */
async function commonChecks( page, vp ) {

			/*
			 * مودالِ فهرست — P5-D221.
			 * کلید واقعی از CDP، نه رویدادِ ساختگی: دام فوکوس و Esc به
			 * `keydown`ِ واقعیِ مرورگر گوش می‌دهند.
			 */
			const key = async ( k, code, kc, shift ) => {
				const base = { key: k, code, windowsVirtualKeyCode: kc, modifiers: shift ? 8 : 0 };
				await page.send( 'Input.dispatchKeyEvent', Object.assign( { type: 'keyDown' }, base ) );
				await page.send( 'Input.dispatchKeyEvent', Object.assign( { type: 'keyUp' }, base ) );
			};

			/* مودالِ فهرست: روی هارنس «همه شهرها»؛ روی صفحهٔ واقعی نخستین «همه …» که هست */
			const LIST = LIVE
				? await page.evaluate( `( ( document.querySelector( '[data-tp-list-open]' ) || { getAttribute: () => '' } ).getAttribute( 'href' ) || '' ).replace( '#', '' )` )
				: 'tp-list-places';

			if ( ! LIST ) {
				rows.push( '  · فهرست: این صفحه «همه … ←» ندارد — سنجهٔ مودال فهرست این‌جا بی‌موضوع است' );
			} else {

			const opened = await page.evaluate( `(async () => {
				await __tp.go( 0 );
				const a = document.querySelector( '[data-tp-list-open][href="#${ LIST }"]' );
				if ( ! a ) { return { none: true }; }
				a.focus(); a.click(); await __tp.wait( 300 );
				const box = document.getElementById( '${ LIST }' ), nav = __tp.nav().getBoundingClientRect();
				const hit = document.elementFromPoint( Math.round( nav.left + nav.width / 2 ), Math.round( Math.max( 1, nav.top + 5 ) ) );
				const bar = document.querySelector( '.tp-mnav' ).getBoundingClientRect();
				const hitBar = bar.height ? document.elementFromPoint( Math.round( bar.left + bar.width / 2 ), Math.round( bar.top + bar.height / 2 ) ) : null;
				return {
					shown: box.classList.contains( 'tp-show' ) && 'none' !== getComputedStyle( box ).display,
					focus: document.activeElement === box.querySelector( '[data-tp-list-q]' ),
					locked: 'hidden' === getComputedStyle( document.documentElement ).overflow || 'hidden' === document.documentElement.style.overflow,
					covers: !! ( hit && box.contains( hit ) ) && ( ! hitBar || box.contains( hitBar ) ),
					items: box.querySelectorAll( '.tp-list__item' ).length,
					first: ( box.querySelector( '.tp-list__item b' ) || {} ).textContent || ''
				};
			})()` );
			check( 'فهرست: «همه … ←» مودال را باز می‌کند، فوکوس روی جست‌وجو، بدنه قفل',
				! opened.none && opened.shown && opened.focus && opened.locked, JSON.stringify( opened ) );
			check( 'فهرست: مودال روی نوار بخش‌ها و نوار پایین می‌نشیند، نه زیرشان', opened.covers, JSON.stringify( opened ) );

			/*
			 * جست‌وجو. هارنس: «اِصفَهان» با اِعراب و «نوش آباد» با فاصله.
			 * صفحهٔ واقعی: نامِ نخستین مورد با اِعراب روی حرف دوم و ی/ک عربی —
			 * یکدست‌سازی باید همان را پیدا کند.
			 */
			const found = await page.evaluate( `(async () => {
				const box = document.getElementById( '${ LIST }' ), q = box.querySelector( '[data-tp-list-q]' );
				const seen = () => [].filter.call( box.querySelectorAll( '.tp-list__item' ), li => ! li.hidden ).map( li => li.textContent );
				const live = ${ LIVE }, first = ${ JSON.stringify( opened.first || '' ) };
				const messy = first.replace( /ی/g, 'ي' ).replace( /ک/g, 'ك' ).replace( /^(.)(.)/, '$1$2' + String.fromCharCode( 0x650 ) );
				q.value = live ? messy : 'اِصفَهان'; q.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				const a = seen(), countA = box.querySelector( '[data-tp-list-count]' ).textContent;
				q.value = live ? '' : 'نوش آباد'; q.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				const b = seen();
				q.value = 'zzqq'; q.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				const c = seen(), emptyShown = ! box.querySelector( '.tp-list__empty' ).hidden;
				q.value = ''; q.dispatchEvent( new Event( 'input', { bubbles: true } ) );
				return { first, messy, a, countA, b, c: c.length, emptyShown, all: seen().length, emptyAfter: box.querySelector( '.tp-list__empty' ).hidden };
			})()` );
			if ( LIVE ) {
				check( 'فهرست: نامِ نخستین مورد با اِعراب و ی/ک عربی همان را پیدا می‌کند',
					found.a.length > 0 && found.a.some( t => -1 !== t.indexOf( found.first ) ), JSON.stringify( { first: found.first, messy: found.messy, hits: found.a.length } ) );
			} else {
				check( 'فهرست: «اِصفَهان» با اِعراب همان «اصفهان» را پیدا می‌کند و شمار عوض می‌شود',
					found.a.length > 0 && found.a.every( t => -1 !== t.indexOf( 'اصفهان' ) ) && -1 !== found.countA.indexOf( ' از ' ), JSON.stringify( found ) );
				check( 'فهرست: «نوش آباد» با فاصله همان «نوش‌آباد» با نیم‌فاصله است',
					1 === found.b.length && -1 !== found.b[0].indexOf( 'نوش‌آباد' ), JSON.stringify( found.b ) );
			}
			check( 'فهرست: بی‌نتیجه، «موردی پیدا نشد»؛ پرسشِ خالی همه را برمی‌گرداند',
				0 === found.c && found.emptyShown && opened.items === found.all && found.emptyAfter, JSON.stringify( found ) );

			/* دام فوکوس: Tab از آخرین کنترل به نخستین برمی‌گردد */
			await page.evaluate( `(() => { const f = document.getElementById( '${ LIST }' ).querySelectorAll( 'button, [href], input' ); f[ f.length - 1 ].focus(); })()` );
			await key( 'Tab', 'Tab', 9 );
			const trapped = await page.evaluate( `document.getElementById( '${ LIST }' ).contains( document.activeElement )` );
			check( 'فهرست: دام فوکوس — Tab از مودال بیرون نمی‌رود', true === trapped, trapped );

			await key( 'Escape', 'Escape', 27 );
			await page.evaluate( '__tp.wait( 200 )' );
			const closed = await page.evaluate( `({
				shown: document.getElementById( '${ LIST }' ).classList.contains( 'tp-show' ),
				back: document.activeElement === document.querySelector( '[data-tp-list-open][href="#${ LIST }"]' ),
				unlocked: 'hidden' !== document.documentElement.style.overflow
			})` );
			check( 'فهرست: Esc می‌بندد، فوکوس به «همه … ←» برمی‌گردد، قفل باز می‌شود',
				! closed.shown && closed.back && closed.unlocked, JSON.stringify( closed ) );
			}

			/*
			 * ── اسلایدر — P5-D243 (جایگزین P5-D226) ──
			 * در صفحهٔ فارسی و عربی، آغاز اسلایدر، فلش چپ «‹» فعال است: «بعدی» =
			 * «‹» سمت چپ، «قبلی» = «›» سمت راست و در آغاز غیرفعال. حرکت همان
			 * پیشین است — «بعدی» کارتِ نخست را به راست می‌برد و کارتِ پنهانِ چپ
			 * پیدا می‌شود. ترتیب DOM همان ترتیب دیداری: «قبلی» سپس «بعدی». در
			 * چپ‌به‌راست همه برعکس. جهت از خودِ صفحه خوانده می‌شود.
			 */
			const sl = await page.evaluate( `(async () => {
				await __tp.go( 0 );
				const navs = [].slice.call( document.querySelectorAll( '.tp-slider-nav' ) ).map( nav => {
					const next = nav.querySelector( 'button[data-dir="1"]' ), prev = nav.querySelector( 'button[data-dir="-1"]' );
					const el = document.getElementById( next.getAttribute( 'data-target' ) );
					return { nav, next, prev, el, over: el.scrollWidth > el.clientWidth + 1 };
				} );
				const pick = navs.filter( x => x.over )[0];
				if ( ! pick ) { return { none: true, allOff: navs.every( x => x.next.disabled && x.prev.disabled ) }; }
				const rtl = 'rtl' === getComputedStyle( pick.el ).direction;
				pick.el.scrollIntoView( { block: 'center' } ); await __tp.wait( 200 );
				const rn = pick.next.getBoundingClientRect(), rp = pick.prev.getBoundingClientRect();
				const card = pick.el.firstElementChild, x0 = card.getBoundingClientRect().left;
				const st = () => ( { nextOff: pick.next.disabled, prevOff: pick.prev.disabled } );
				const s0 = st();
				pick.next.click(); await __tp.wait( 800 );
				const x1 = card.getBoundingClientRect().left;
				const s1 = st();
				pick.el.scrollTo( { left: ( rtl ? -1 : 1 ) * pick.el.scrollWidth, behavior: 'auto' } ); await __tp.wait( 300 );
				const s2 = st();
				pick.el.scrollTo( { left: 0, behavior: 'auto' } ); await __tp.wait( 300 );
				const s3 = st();
				return {
					rtl, nextText: pick.next.textContent, prevText: pick.prev.textContent,
					nextLeft: rn.left < rp.left, domPrevFirst: pick.prev.nextElementSibling === pick.next,
					aria: [ pick.prev.getAttribute( 'aria-label' ), pick.next.getAttribute( 'aria-label' ) ],
					spanDir: getComputedStyle( pick.next.querySelector( 'span' ) ).direction,
					moved: Math.round( x1 - x0 ), s0, s1, s2, s3
				};
			})()` );
			if ( sl.none ) {
				check( 'اسلایدر: هیچ فهرستی سرریز نمی‌کند — پس هر دو دکمه غیرفعال', sl.allOff, JSON.stringify( sl ) );
			} else {
				check( 'اسلایدر: راست‌به‌چپ «بعدی» = «‹» سمت چپ، «قبلی» = «›» سمت راست (چپ‌به‌راست برعکس)؛ DOM همان ترتیب؛ نویسه در ltr  P5-D243',
					( sl.rtl ? '‹' === sl.nextText && '›' === sl.prevText && sl.nextLeft : '›' === sl.nextText && '‹' === sl.prevText && ! sl.nextLeft )
					&& sl.domPrevFirst && 'قبلی' === sl.aria[0] && 'بعدی' === sl.aria[1] && 'ltr' === sl.spanDir, JSON.stringify( sl ) );
				check( 'اسلایدر: «بعدی» کارت پنهانِ سمتِ پایانِ خط را پیش می‌آورد — راست‌به‌چپ محتوا به راست  P5-D243',
					sl.rtl ? sl.moved > 20 : sl.moved < -20, JSON.stringify( sl ) );
				check( 'اسلایدر: آغاز «قبلی» غیرفعال و «بعدی» فعال؛ پس از جلو رفتن «قبلی» فعال؛ پایان «بعدی» غیرفعال؛ بازگشت به آغاز باز «قبلی» غیرفعال  P5-D243',
					sl.s0.prevOff && ! sl.s0.nextOff && ! sl.s1.prevOff && sl.s2.nextOff && ! sl.s2.prevOff && sl.s3.prevOff && ! sl.s3.nextOff,
					JSON.stringify( [ sl.s0, sl.s1, sl.s2, sl.s3 ] ) );
			}

			/* ── عکس تمام‌صفحه — ۴٫۲٫۱ بند ۸ · P5-D243 (جایگزین P5-D226) ── */
			const solo = await page.evaluate( `(async () => {
				const made = [];
				const Orig = window.Image;
				window.Image = function () { const i = new Orig(); made.push( i ); return i; };
				await __tp.go( 0 );
				/* کاشیِ بزرگِ موزاییک دوباره عکس است.  P5-D227 */
				const tile = document.querySelector( '.tp-gallery-main [data-tp-shot${ LIVE ? '' : '="601"' }]' );
				if ( ! tile || ! document.getElementById( 'tpGallerySolo' ) ) { window.Image = Orig; return { none: true }; }
				const shots = JSON.parse( document.getElementById( 'tpGalleryModal' ).getAttribute( 'data-tp-shots' ) );
				tile.click();
				const im = document.querySelector( '#tpGallerySolo .tp-solo__img' );
				/* نسخهٔ ۱۰۲۴ همان لحظه؛ نسخهٔ کامل پس از decode.  P5-D226 */
				const src0 = im.getAttribute( 'src' );
				let swapped = false;
				for ( let i = 0; i < 60; i++ ) { if ( im.getAttribute( 'src' ) === shots[0].src && im.complete && im.naturalWidth ) { swapped = true; break; } await __tp.wait( 50 ); }
				await __tp.wait( 100 );
				const box = document.getElementById( 'tpGallerySolo' );
				const read = () => ( {
					count: box.querySelector( '[data-tp-solo-count]' ).textContent,
					title: box.querySelector( '[data-tp-solo-title]' ).textContent,
					cap: box.querySelector( '[data-tp-solo-caption]' ).textContent,
					src: box.querySelector( '.tp-solo__img' ).getAttribute( 'src' )
				} );
				const first = read();
				const pre = made.map( i => i.src );
				box.querySelector( '[data-tp-shot-next]' ).click();
				const second = read();
				box.querySelector( '[data-tp-shot-prev]' ).click();
				box.querySelector( '[data-tp-shot-prev]' ).click();
				const wrapped = read();
				box.querySelector( '[data-tp-shot-next]' ).click();
				const img = box.querySelector( '.tp-solo__img' ).getBoundingClientRect();
				const prevB = box.querySelector( '[data-tp-shot-prev]' ), nextB = box.querySelector( '[data-tp-shot-next]' );
				const prev = prevB.getBoundingClientRect(), next = nextB.getBoundingClientRect();
				const capBox = box.querySelector( '.tp-solo__cap' ).getBoundingClientRect();
				window.Image = Orig;
				return {
					shown: box.classList.contains( 'tp-show' ), first, second, wrapped, n: shots.length,
					fast: src0 === ( shots[0].m || shots[0].src ) && ( ${ LIVE } || shots[0].m !== shots[0].src ), swapped, src0,
					t0: shots[0].t || '', c0: shots[0].c || '',
					loaded: im.naturalWidth, imgH: Math.round( img.height ),
					preNext: [ shots[1].m, shots[1].src ].every( u => pre.some( s => -1 !== s.indexOf( u ) ) ),
					prePrev: [ shots[ shots.length - 1 ].m, shots[ shots.length - 1 ].src ].every( u => pre.some( s => -1 !== s.indexOf( u ) ) ),
					fit: getComputedStyle( box.querySelector( '.tp-solo__img' ) ).objectFit,
					rtl: 'rtl' === getComputedStyle( box ).direction,
					nextLeft: next.right < img.left + img.width / 2, prevRight: prev.left > img.left + img.width / 2,
					nextText: nextB.textContent, prevText: prevB.textContent,
					spanDir: getComputedStyle( nextB.querySelector( 'span' ) ).direction,
					midY: Math.abs( ( prev.top + prev.height / 2 ) - ( img.top + img.height / 2 ) ) < 2,
					capBelow: capBox.top >= img.bottom - 1 && capBox.height > 0
				};
			})()` );
			const faN = v => String( v ).replace( /[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[ d ] );
			if ( solo.none ) {
				rows.push( '  · تمام‌صفحه: این صفحه گالری ندارد — سنجه‌های تمام‌صفحه بی‌موضوع' );
			} else {
			check( 'تمام‌صفحه: شمار «۱ / N»، عنوان و توضیحِ پیوست زیر عکس',
				solo.shown && '۱ / ' + faN( solo.n ) === solo.first.count && solo.capBelow
				&& ( LIVE ? solo.t0 === solo.first.title && solo.c0 === solo.first.cap
					: 'میدان نقش جهان' === solo.first.title && 'میدان امام، ۱۶۰۲' === solo.first.cap ), JSON.stringify( solo.first ) );
			check( 'تمام‌صفحه: کاشیِ موزاییکِ صفحه هم نمای تمام‌صفحه را در لایهٔ بیرونی باز می‌کند  P5-D228', solo.shown, JSON.stringify( solo.first ) );
			check( 'تمام‌صفحه: بعدی ← «۲ / N»؛ قبل از اولی، آخری (چرخهٔ دوسر)',
				'۲ / ' + faN( solo.n ) === solo.second.count && faN( solo.n ) + ' / ' + faN( solo.n ) === solo.wrapped.count,
				JSON.stringify( [ solo.second.count, solo.wrapped.count ] ) );
			check( 'تمام‌صفحه: نسخهٔ ۱۰۲۴ کاشی همان لحظه، نسخهٔ کامل پس از decode جایش  P5-D226',
				solo.fast && solo.swapped, JSON.stringify( { src0: solo.src0, fast: solo.fast, swapped: solo.swapped } ) );
			check( 'تمام‌صفحه: همسایه‌ها — بعدی و قبلی، هر دو نسخه — از پیش بار می‌شوند', solo.preNext && solo.prePrev, JSON.stringify( { preNext: solo.preNext, prePrev: solo.prePrev } ) );
			check( 'تمام‌صفحه: عکس واقعاً بار شد و بلندی دارد — وگرنه سنجهٔ چیدمان بی‌معناست',
				solo.loaded > 0 && solo.imgH > 100, JSON.stringify( { loaded: solo.loaded, imgH: solo.imgH } ) );
			check( 'تمام‌صفحه: contain؛ راست‌به‌چپ لبهٔ چپ «‹» = بعدی و لبهٔ راست «›» = قبلی (چپ‌به‌راست برعکس)، ltr، وسطِ عمودی عکس  P5-D243',
				'contain' === solo.fit
				&& ( solo.rtl ? solo.nextLeft && solo.prevRight && '‹' === solo.nextText && '›' === solo.prevText
					: ! solo.nextLeft && ! solo.prevRight && '›' === solo.nextText && '‹' === solo.prevText )
				&& 'ltr' === solo.spanDir && solo.midY, JSON.stringify( solo ) );

			/* کلید واقعی، هم‌سوی فلش: راست‌به‌چپ ArrowLeft = «‹» = بعدی و ArrowRight = قبلی؛ چپ‌به‌راست برعکس.  P5-D243 */
			const cnt = () => page.evaluate( `document.querySelector( '#tpGallerySolo [data-tp-solo-count]' ).textContent` );
			const fwd = solo.rtl ? [ 'ArrowLeft', 'ArrowLeft', 37 ] : [ 'ArrowRight', 'ArrowRight', 39 ];
			const bwd = solo.rtl ? [ 'ArrowRight', 'ArrowRight', 39 ] : [ 'ArrowLeft', 'ArrowLeft', 37 ];
			const k0 = await cnt();
			await key( fwd[0], fwd[1], fwd[2] );
			const k1 = await cnt();
			await key( bwd[0], bwd[1], bwd[2] );
			const k2 = await cnt();
			check( 'تمام‌صفحه: راست‌به‌چپ ArrowLeft یکی جلو و ArrowRight یکی عقب (چپ‌به‌راست برعکس)  P5-D243',
				'۱ / ' + faN( solo.n ) === k0 && '۲ / ' + faN( solo.n ) === k1 && k0 === k2, [ k0, k1, k2 ].join( ' → ' ) );

			/* هیچ گذار یا پویانماییِ گالری بیش از ۱۵۰ms نیست.  P5-D226 */
			const dur = await page.evaluate( `(() => {
				const ms = v => Math.max.apply( null, String( v ).split( ',' ).map( s => parseFloat( s ) * ( /ms/.test( s ) ? 1 : 1000 ) || 0 ) );
				const out = {};
				[ '.tp-gallery-main__item', '.tp-gallery-modal__item', '.tp-gallery-modal__cap', '.tp-gallery-modal__close', '.tp-solo__nav' ].forEach( s => {
					const el = document.querySelector( s );
					if ( ! el ) { return; }
					out[ s ] = ms( getComputedStyle( el ).transitionDuration );
					out[ s + '::after' ] = ms( getComputedStyle( el, '::after' ).transitionDuration );
				} );
				const m = document.getElementById( 'tpGallerySolo' );
				out.show = ms( getComputedStyle( m ).animationDuration );
				return out;
			})()` );
			check( 'گالری: هیچ گذار یا پویانمایی بیش از ۱۵۰ms نیست — کاشی، مودال، زیرنویس، بستن، باز شدن  P5-D226',
				Object.keys( dur ).length >= 9 && Object.keys( dur ).every( k => dur[ k ] <= 150 ), JSON.stringify( dur ) );

			if ( vp.mobile ) {
				await page.send( 'Emulation.setTouchEmulationEnabled', { enabled: true, maxTouchPoints: 5 } );
				const box = await page.evaluate( `(() => { const r = document.querySelector( '#tpGallerySolo .tp-solo__img' ).getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2 }; })()` );
				const before = await page.evaluate( `document.querySelector( '#tpGallerySolo [data-tp-solo-count]' ).textContent` );
				await page.send( 'Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [ { x: box.x - 90, y: box.y } ] } );
				await page.send( 'Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [ { x: box.x + 90, y: box.y + 4 } ] } );
				await page.send( 'Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] } );
				await page.evaluate( '__tp.wait( 150 )' );
				const after = await page.evaluate( `document.querySelector( '#tpGallerySolo [data-tp-solo-count]' ).textContent` );
				check( 'تمام‌صفحه: کشیدنِ انگشت از چپ به راست = بعدی در راست‌به‌چپ — همان حرکت پیشین  P5-D243', before !== after && '۲ / ' + faN( solo.n ) === after, before + ' → ' + after );
			}

			await key( 'Escape', 'Escape', 27 );
			}

			/* ── روایت صوتی و تصویری — P5-D227 (جایگزین P5-D222) ── */
			const media = await page.evaluate( `(async () => {
				await __tp.wait( 150 );
				/* فقط پخش‌کننده‌های خودمان — صفحهٔ واقعی ممکن است iframe دیگری داشته باشد */
				const count = () => [].filter.call( document.querySelectorAll( 'iframe, video' ), el => !! el.closest( '.tp-page' ) ).length;
				const before = count();
				const audio = document.querySelector( '.tp-audio__item audio' );
				const box = document.getElementById( 'tpMediaSolo' );
				const card = !! document.getElementById( 'tp-audio' );
				const galleryMedia = document.querySelectorAll( '.tp-gallery-main [data-tp-media]' ).length;
				const kinds = [].map.call( document.querySelectorAll( '#tp-audio .tp-audio__kind' ), k => k.getAttribute( 'data-tp-kind' ) + ':' + ( k.querySelector( 'svg' ) ? 1 : 0 ) );
				const play = async el => {
					el.click();
					await __tp.wait( 150 );
					const f = box.querySelector( 'iframe' ) || box.querySelector( 'video' );
					const out = { shown: box.classList.contains( 'tp-show' ), src: f && f.getAttribute( 'src' ),
						name: box.querySelector( '[data-tp-media-name]' ).textContent, url: location.href };
					box.querySelector( '[data-tp-close]' ).click();
					await __tp.wait( 150 );
					return out;
				};
				const vrow  = document.querySelector( '#tp-audio a[data-tp-media][data-tp-media-title^="ویدیوی"]' );
				const other = [].filter.call( document.querySelectorAll( '#tp-audio a[data-tp-media]' ), x => x !== vrow )[0];
				const a = vrow && box ? await play( vrow ) : null;
				const afterClose = box ? box.querySelectorAll( 'iframe, video' ).length : 0;
				const b = other && box ? await play( other ) : null;
				return { card, before, hasAudio: !! audio, preload: audio && audio.getAttribute( 'preload' ), controls: !! ( audio && audio.controls ),
					a, afterClose, b, galleryMedia, kinds, finalFrames: count() };
			})()` );
			const EMBED = /^https:\/\/www\.(?:youtube-nocookie\.com\/embed\/|aparat\.com\/video\/video\/embed\/videohash\/)/;
			if ( LIVE && ! media.card ) {
				rows.push( '  · روایت: این صفحه کارتِ روایت ندارد — سنجه‌های پخش بی‌موضوع' );
				check( 'روایت: گالری هیچ رسانه‌ای ندارد  P5-D227', 0 === media.galleryMedia, JSON.stringify( media ) );
			} else {
				check( 'روایت: گالری هیچ رسانه‌ای ندارد؛ کارتِ روایت هر ردیف را با آیکونِ نوعش می‌آورد  P5-D227',
					0 === media.galleryMedia && media.kinds.length >= ( LIVE ? 1 : 3 ) && media.kinds.every( k => /:1$/.test( k ) )
					&& ( LIVE || ( media.kinds.indexOf( 'audio:1' ) > -1 && media.kinds.indexOf( 'video:1' ) > -1 ) ),
					JSON.stringify( { galleryMedia: media.galleryMedia, kinds: media.kinds } ) );
				check( 'پخش: پیش از کلیک هیچ iframe یا video نیست؛ پخش‌کنندهٔ صوت preload="none"',
					0 === media.before && ( ( LIVE && ! media.hasAudio ) || ( 'none' === media.preload && media.controls ) ), JSON.stringify( media ) );
				if ( media.a ) {
					check( 'پخش: ویدیوی نمونه از کارتِ روایت در شاسیِ تمام‌صفحه پخش می‌شود  P5-D227',
						media.a.shown && ( LIVE ? EMBED : /^https:\/\/www\.aparat\.com\/video\/video\/embed\/videohash\// ).test( media.a.src || '' ) && '' !== media.a.name,
						JSON.stringify( media.a ) );
				} else if ( ! LIVE ) {
					check( 'پخش: ویدیوی نمونه در کارتِ روایت هست', false, 'ردیف ویدیو پیدا نشد' );
				}
				check( 'پخش: بستن، iframe را برمی‌دارد — پخش می‌ایستد', 0 === media.afterClose && 0 === media.finalFrames, JSON.stringify( media ) );
				if ( media.b ) {
					check( 'پخش: ردیفِ دیگرِ ویدیویی در همان شاسی، بی‌رفتن از صفحه',
						media.b.shown && ( LIVE ? EMBED : /^https:\/\/www\.youtube-nocookie\.com\/embed\// ).test( media.b.src || '' ) && -1 === media.b.url.indexOf( 'youtube' ),
						JSON.stringify( media.b ) );
				}
			}

}
