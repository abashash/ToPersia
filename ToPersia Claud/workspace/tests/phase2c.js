/**
 * فاز ۲ج SPRINT-4.4 — ادعاهای دیدنی، کروم و فایرفاکس Playwright، روی صفحهٔ واقعیِ محلی.
 *
 *   node tests/phase2c.js [--only=title,stats,…]
 *
 *   stats   ۲ج٫۱ — هر نوعِ دارای کارت آمار: هیچ مقدار دو خط نمی‌شود؛ ستون‌ها به شمار خانه‌ها
 *   title   ۲ج٫۴ — تیتر کشور («ایران») در ۱۴۴۰ و ۳۹۰ یک خط
 *   gallery ۲ج٫۵ — گالری تجمیعی کشور: عکس استان با منبع؛ پیوند منبع در نمای تمام‌صفحه و کلیکش
 *   lang    ۲ج٫۸ — هر نوع در /en/ و /ar/ (ترجمۀ موجود یا موقت — tests/phase2c-lang.php): در متن
 *           و aria-label · title · alt هیچ رقم فارسی؛ در /en/ هیچ حرف عربی‌خط جز درون lang="fa|ar"؛
 *           در /ar/ حرف ویژۀ فارسی فقط هشدار. نقشۀ fa · en · ar: برچسب کاشی، دکمه‌ها، aria-label
 *           و انتساب به زبان صفحه، با عکس در tests/.screens/ (کروم: همه؛ فایرفاکس: نقشه)
 *
 * «یک خط» با ارتفاع دیدنی سنجیده می‌شود: سطرهای متن با Range (هر سطر یک
 * مستطیل با `top` تازه)، نه ارتفاع جعبه. درخواست به topersia.com بسته است.
 * کد خروج: ۰ سالم · ۱ افتادن.
 */

const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );   /* مسیرها و نشانی پایه — tests/env.json (P5-D285) */
const { execFileSync } = require( 'child_process' );
const path = require( 'path' );
const fs = require( 'fs' );
const SCREENS = path.join( __dirname, '.screens' );

const BASE = ENV.base;
const PHP  = ENV.php;

/* نشانیِ هر نوع با کارت آمار — از وردپرس محلی (tests/phase2c-urls.php) */
const STAT_PAGES = ( () => {
	try { return JSON.parse( execFileSync( PHP, [ path.join( __dirname, 'phase2c-urls.php' ) ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'ignore' ] } ) ); }
	catch ( e ) { return {}; }
} )();
const ONLY = ( ( process.argv.find( a => a.startsWith( '--only=' ) ) || '' ).slice( 7 ) ).split( ',' ).filter( Boolean );
const want = k => ! ONLY.length || ONLY.includes( k );

let pass = 0, fail = 0;
function check( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { fail ++; console.log( '  ❌ ' + label + ( note ? '   ← ' + note : '' ) ); }
}

async function open( browser, path, width, cookies ) {
	const ctx  = await browser.newContext( { viewport: { width, height: 900 } } );
	if ( cookies ) { await ctx.addCookies( cookies ); }   /* پیش‌نمایش پیش‌نویس — مدیر آزمون */
	await ctx.route( /topersia\.com|picsum\.photos/, r => r.abort() );
	const page = await ctx.newPage();
	await page.goto( BASE + path, { waitUntil: 'domcontentloaded', timeout: 240000 } );
	await page.evaluate( () => document.fonts && document.fonts.ready );
	return { ctx, page };
}

/* شمار سطرِ دیدنیِ متن هر عنصر — مستطیل‌های Range با `top` متمایز (±۲). */
/* شمار سطرِ دیدنیِ متن هر عنصر: مستطیل‌های سطرِ گره‌های متنی، خوشه به مرکز عمودی
   (بازۀ نیم‌ارتفاع سطر) — جعبۀ عنصر و جعبۀ متنِ یک سطر دو سطر شمرده نمی‌شوند. */
const LINES = `( el => {
	const mids = [];
	const walk = document.createTreeWalker( el, NodeFilter.SHOW_TEXT );
	for ( let n = walk.nextNode(); n; n = walk.nextNode() ) {
		if ( ! n.textContent.trim() ) continue;
		const r = document.createRange(); r.selectNodeContents( n );
		for ( const b of r.getClientRects() ) {
			if ( b.width < 1 || b.height < 1 ) continue;
			const m = b.top + b.height / 2;
			if ( ! mids.some( x => Math.abs( x - m ) < b.height / 2 ) ) mids.push( m );
		}
	}
	return mids.length;
} )`;

/*
 * ۲ج٫۸ — هر متن و ویژگی رابط درون صفحۀ ما (.tp-page و ظرف portal، با مودال‌های پنهان):
 * رقم فارسی · حرف عربی‌خط بیرون از lang="fa|ar" · حرف ویژۀ فارسی بیرون از lang="fa".
 * بیرون از .tp-page (سربرگ و پانویس پوسته) فقط شمرده می‌شود — آگاهی، نه حکم.
 */
const SCAN = `( () => {
	const faDigit = /[\\u06F0-\\u06F9]/;
	const arab    = /[\\u0600-\\u06FF\\u0750-\\u077F\\u08A0-\\u08FF\\uFB50-\\uFDFF\\uFE70-\\uFEFF]/;
	const faOnly  = /[\\u067E\\u0686\\u0698\\u06AF\\u06CC\\u06A9]/;
	const out = { digits: [], arabic: [], faLetters: [], texts: 0, outside: 0 };
	const where = el => { const p = el.closest( '[id]' ); return ( p ? '#' + p.id : '' ) + ' ' + ( el.getAttribute( 'class' ) || el.tagName ).toString().split( ' ' )[0]; };
	const langOf = el => { const l = el.closest( '[lang]' ); return l ? l.getAttribute( 'lang' ) : ''; };
	const take = ( s, el, kind ) => {
		const lg = langOf( el );
		const sn = kind + ' ' + where( el ) + ': ' + s.trim().replace( /\\s+/g, ' ' ).slice( 0, 50 );
		if ( faDigit.test( s ) ) { out.digits.push( sn ); }
		if ( arab.test( s ) && ! /^(fa|ar)\\b/i.test( lg ) ) { out.arabic.push( sn ); }
		if ( faOnly.test( s ) && ! /^fa\\b/i.test( lg ) ) { out.faLetters.push( sn ); }
	};
	const skip = n => n.parentElement && n.parentElement.closest( 'script,style,template,noscript' );
	const roots = [ ...document.querySelectorAll( '.tp-page' ) ].filter( r => ! r.parentElement.closest( '.tp-page' ) );
	for ( const root of roots ) {
		const w = document.createTreeWalker( root, NodeFilter.SHOW_TEXT );
		for ( let n = w.nextNode(); n; n = w.nextNode() ) {
			if ( ! skip( n ) && n.textContent.trim() ) { out.texts++; take( n.textContent, n.parentElement, 'text' ); }
		}
		for ( const el of root.querySelectorAll( '[aria-label],[title],[alt],[placeholder]' ) ) {
			for ( const a of [ 'aria-label', 'title', 'alt', 'placeholder' ] ) { const v = el.getAttribute( a ); if ( v ) { take( v, el, a ); } }
		}
	}
	const b = document.createTreeWalker( document.body, NodeFilter.SHOW_TEXT );
	for ( let n = b.nextNode(); n; n = b.nextNode() ) {
		if ( ! skip( n ) && n.parentElement && ! n.parentElement.closest( '.tp-page' ) && arab.test( n.textContent ) && ! /^(fa|ar)\\b/i.test( langOf( n.parentElement ) ) ) { out.outside++; }
	}
	return out;
} )()`;

/* ۲ج٫۸ — ترجمۀ en و ar هر نوع: موجود، یا موقت (tests/phase2c-lang.php)؛ پاک‌سازی در finally */
function langPages() {
	return JSON.parse( execFileSync( PHP, [ path.join( __dirname, 'phase2c-lang.php' ), 'setup' ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'inherit' ] } ) );
}
function langCleanup( L ) {
	const arg = Buffer.from( JSON.stringify( { made: L.made, run: L.run, floor: L.floor, post_floor: L.post_floor } ) ).toString( 'base64' );
	return JSON.parse( execFileSync( PHP, [ path.join( __dirname, 'phase2c-lang.php' ), 'cleanup', arg ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'inherit' ] } ) );
}

/* منتظر روزهای هوا (پاسخ /weather) — اگر کارت هوا هست */
async function weatherSettled( page ) {
	if ( await page.$( '.tp-wx__day' ) ) {
		await page.waitForSelector( '.tp-wx__day:not(.tp-is-load)', { timeout: 30000 } ).catch( () => {} );
	}
}

( async () => {
	/* وصلۀ ۲ج و۱ · P5-D305 — پنهان‌شدن بی‌صدا نه: شمار شکاف ترجمۀ هر زبان، پیش از ترجمه‌های موقت، هشدار (نه شکست) */
	if ( want( 'lang' ) ) {
		const G = JSON.parse( execFileSync( PHP, [ path.join( __dirname, 'phase2c-lang.php' ), 'gaps' ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'inherit' ] } ) );
		for ( const [ lang, c ] of Object.entries( G ) ) {
			console.log( `  ⚠︎ شکاف ترجمه ${ lang }: ${ c.missing } پست فارسی بی ترجمه · ${ c.partial } ترجمۀ شکاف‌دار (${ c.cells } خانه) — wp topersia i18n-gaps --lang=${ lang }` );
		}
	}
	const L = want( 'lang' ) ? langPages() : null;
	/* ترجمۀ پیش‌نویسِ موجود (بازبینی کاربر) با پیش‌نمایش مدیر آزمون دیده می‌شود، نه با انتشار — وصلۀ ۲ج و۴ */
	const ADMIN = L && ( Object.values( L.pages ).some( r => r.preview && r.preview.length ) || ( L.preview_fixture && L.preview_fixture.en ) )
		? JSON.parse( execFileSync( PHP, [ path.join( __dirname, 'local-login.php' ), 'tp_test_admin' ], { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'inherit' ] } ) ).cookies : null;
	const asOf = ( row, lang ) => ( row.preview || [] ).includes( lang ) ? ADMIN : null;
	try {
	for ( const [ bname, engine ] of [ [ 'کروم', chromium ], [ 'فایرفاکس', firefox ] ] ) {

		const browser = await engine.launch();
		console.log( '\n' + bname + '\n' + '─'.repeat( 78 ) );

		/* ۲ج٫۱ — هیچ مقدار کارت آمار در ۱۴۴۰ و ۳۹۰ دو خط نمی‌شود؛ هر نوعی که کارت آمار دارد  P5-D282 */
		if ( want( 'stats' ) ) {
			for ( const [ type, { url, cells } ] of Object.entries( STAT_PAGES ) ) {
				for ( const width of [ 1440, 390 ] ) {
					const { ctx, page } = await open( browser, url, width );
					const got = await page.$$eval( '.tp-stats .tp-stat', ( els, lines ) => els.map( el => {
						const v = el.querySelector( '.tp-stat__v' );
						const b = el.getBoundingClientRect();
						return { v: v.textContent.trim(), lines: eval( lines )( v ), w: Math.round( b.width ) };
					} ), LINES );
					const wrap = got.filter( c => c.lines !== 1 );
					const grid = await page.evaluate( () => { const el = document.querySelector( '.tp-stats' ); return el ? { n: el.getAttribute( 'data-tp-n' ), cols: getComputedStyle( el ).gridTemplateColumns.split( ' ' ).length } : { n: null, cols: 0 }; } );
					const fit  = width > 900 ? grid.cols === got.length : grid.cols <= 2;
					check( `۲ج٫۱ ${ type } ${ width }: ${ got.length } خانۀ آمار، هر مقدار یک خط؛ ستون‌ها به شمار خانه‌ها  P5-D282 · P5-D275`,
						got.length === cells && ! wrap.length && fit, JSON.stringify( { wrap, grid } ) );
					await ctx.close();
				}
			}
		}

		if ( want( 'title' ) ) {
			for ( const width of [ 1440, 390 ] ) {
				const { ctx, page } = await open( browser, '/country/islamic-republic-of-iran/', width );
				const h1 = await page.$eval( '.tp-hero h1', ( el, lines ) => ( { text: el.textContent.trim(), lines: eval( lines )( el ) } ), LINES );
				check( `۲ج٫۴ تیتر کشور «${ h1.text }» در ${ width } یک خط  P5-D281`, 1 === h1.lines && 'ایران' === h1.text, h1.lines + ' سطر' );
				await ctx.close();
			}
		}

		/* ۲ج٫۵ — عکس تجمیعیِ کشور: در نمای تمام‌صفحه کپشن پیوند صفحۀ منبع دارد؛ کلیک همان صفحه  P5-D274 */
		if ( want( 'gallery' ) ) {
			for ( const width of [ 1440, 390 ] ) {
				const { ctx, page } = await open( browser, '/country/islamic-republic-of-iran/', width );
				const shots = await page.evaluate( () => {
					const m = document.getElementById( 'tpGalleryModal' );
					try { return m ? JSON.parse( m.getAttribute( 'data-tp-shots' ) || '[]' ) : []; } catch ( e ) { return []; }
				} );
				const agg = shots.filter( s => s.n );
				let solo = null;
				if ( agg.length ) {
					await page.click( '[data-tp-gallery-open]' );
					await page.waitForSelector( '#tpGalleryModal.tp-show' );
					await page.locator( `#tpGalleryModal [data-tp-shot="${ agg[0].id }"]` ).click();
					await page.waitForSelector( '#tpGallerySolo.tp-show' );
					solo = await page.$eval( '#tpGallerySolo', el => {
						const cap = el.querySelector( '[data-tp-solo-caption]' );
						const a   = cap && cap.querySelector( '.tp-solo__src' );
						if ( ! a ) { return null; }
						const r = a.getBoundingClientRect();
						return {
							tag: a.tagName, href: a.getAttribute( 'href' ), text: a.textContent,
							inherit: getComputedStyle( a ).color === getComputedStyle( cap ).color,
							under: getComputedStyle( a ).textDecorationLine,
							inView: r.width > 0 && r.top >= 0 && r.bottom <= innerHeight && r.left >= 0 && r.right <= innerWidth,
						};
					} );
					if ( solo ) {
						await Promise.all( [
							page.waitForNavigation( { waitUntil: 'domcontentloaded', timeout: 240000 } ),
							page.click( '#tpGallerySolo .tp-solo__src' ),
						] );
					}
				}
				const landed = page.url();
				check( `۲ج٫۵ ${ width }: گالری کشور ${ shots.length } عکس، ${ agg.length } تجمیعی از «${ agg.length ? agg[0].n : '—' }»؛ کپشن تمام‌صفحه پیوند منبع (رنگ کپشن، زیرخط، در قاب) و کلیکش ← صفحۀ منبع  P5-D274`,
					agg.length >= 1 && agg.length < shots.length && agg.every( s => s.u && s.n ) && !! solo && 'A' === solo.tag && solo.href === agg[0].u
						&& solo.text === agg[0].n && solo.inherit && /underline/.test( solo.under ) && solo.inView && landed === agg[0].u,
					JSON.stringify( { n: shots.length, agg: agg.map( s => s.id + ':' + s.n ), solo, landed } ) );
				await ctx.close();
			}
		}

		/* ۲ج٫۸ — همه‌چیز به زبان صفحه  P5-D278 */
		if ( L ) {

			/* هر نوع در /en/ و /ar/ — کروم (متن از سرور و کاتالوگ JS است، نه از موتور مرورگر) */
			if ( engine === chromium ) {
				let outside = 0;
				const warn  = [];
				for ( const [ type, row ] of Object.entries( L.pages ) ) {
					for ( const lang of [ 'en', 'ar' ] ) {
						if ( ! row[ lang ] ) {
							check( `۲ج٫۸ ${ type } /${ lang }/: صفحۀ ترجمه هست`, false, 'ترجمه ساخته نشد' );
							continue;
						}
						const { ctx, page } = await open( browser, row[ lang ], 1440, asOf( row, lang ) );
						await weatherSettled( page );
						const s = await page.evaluate( SCAN );
						outside += s.outside;
						if ( 'en' === lang ) {
							check( `۲ج٫۸ ${ type } /en/: ${ s.texts } متن — هیچ رقم فارسی، هیچ حرف عربی‌خط جز درون lang="fa|ar"  P5-D278`,
								s.texts > 20 && ! s.digits.length && ! s.arabic.length, JSON.stringify( { url: row.en, digits: s.digits.slice( 0, 4 ), arabic: s.arabic.slice( 0, 6 ) } ) );
						} else {
							check( `۲ج٫۸ ${ type } /ar/: ${ s.texts } متن — هیچ رقم فارسی  P5-D278`,
								s.texts > 20 && ! s.digits.length, JSON.stringify( { url: row.ar, digits: s.digits.slice( 0, 6 ) } ) );
							if ( s.faLetters.length ) { warn.push( `${ type }: ${ s.faLetters.length } — ${ s.faLetters.slice( 0, 3 ).join( ' | ' ) }` ); }
						}
						await ctx.close();
					}
				}
				/* وصلۀ ۲ج و۲ · P5-D306 — «شمار استان‌ها»ی کشور در هر زبان همان شمار فارسی، با رقم همان زبان */
				const country = L.pages.tp_country || {};
				const provCell = async ( url, label, cookies ) => {
					const { ctx, page } = await open( browser, url, 1440, cookies );
					const v = await page.evaluate( l => {
						const c = [ ...document.querySelectorAll( '.tp-stat' ) ].find( s => ( s.querySelector( '.tp-stat__k' ) || {} ).textContent === l );
						return c ? c.querySelector( '.tp-stat__v' ).textContent.trim() : '';
					}, label );
					await ctx.close();
					return v;
				};
				const toLatin = s => s.replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ).replace( /[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf( d ) );
				if ( country.fa && country.en && country.ar ) {
					const v6 = { fa: await provCell( country.fa, 'شمار استان‌ها' ), en: await provCell( country.en, 'Provinces', asOf( country, 'en' ) ), ar: await provCell( country.ar, 'عدد المحافظات', asOf( country, 'ar' ) ) };
					check( `و۲ کشور: «شمار استان‌ها» در /en/ و /ar/ همان شمار فارسی (${ v6.fa }) با رقم همان زبان — ${ v6.en } · ${ v6.ar }  P5-D306`,
						'' !== v6.fa && /^[۰-۹]+$/.test( v6.fa ) && /^[0-9]+$/.test( v6.en ) && /^[٠-٩]+$/.test( v6.ar )
						&& toLatin( v6.fa ) === v6.en && toLatin( v6.ar ) === v6.en && Number( v6.en ) > 1, JSON.stringify( v6 ) );
				} else {
					check( 'و۲ کشور: صفحه‌های fa · en · ar هست', false, JSON.stringify( country ) );
				}
				/*
				 * نگهبان رفتاری پیش‌نمایش — wpml_should_filter_preview_lang فقط در کد WPML است، نه در مستند:
				 * کشور موقتِ دو استانی با ترجمۀ انگلیسی پیش‌نویس؛ پیش‌نمایش مدیر باید ۲ نشان دهد (گراف انگلیسی صفر)  P5-D306
				 */
				const fx = L.preview_fixture || {};
				if ( fx.en ) {
					const vfx = await provCell( fx.en, 'Provinces', ADMIN );
					check( `و۲ پیش‌نمایش ترجمۀ پیش‌نویس (${ fx.status }): «Provinces» ${ vfx || '—' } = ${ fx.want } از گراف فارسی، نه گراف زبان پیش‌نمایش  P5-D306`,
						String( fx.want ) === vfx && 'draft' === fx.status, fx.en );
				} else {
					check( 'و۲ پیش‌نمایش: چیدمان آزمون ساخته شد', false, JSON.stringify( fx ) );
				}
				console.log( `  · هشدار /ar/ — حرف ویژۀ فارسی (پ چ ژ گ ی ک) بیرون از lang="fa": ${ warn.length } نوع` );
				warn.forEach( w => console.log( '      ' + w ) );
				console.log( `  · آگاهی — متن عربی‌خطِ بیرون از صفحۀ ما (سربرگ و پانویس پوسته) در همۀ صفحه‌های /en/ و /ar/: ${ outside } گره` );
			}

			/* نقشۀ fa · en · ar روی استان اصفهان — هر دو مرورگر، با عکس */
			fs.mkdirSync( SCREENS, { recursive: true } );
			const prov = L.pages.tp_province || {};
			const want8 = {
				fa: { zin: 'بزرگ‌نمایی', zout: 'کوچک‌نمایی', map: 'نقشه', marker: 'نشانگر نقشه', attrib: 'مشارکت‌کنندگان OpenStreetMap' },
				en: { zin: 'Zoom in', zout: 'Zoom out', map: 'Map', marker: 'Map marker', attrib: 'OpenStreetMap contributors' },
				ar: { zin: 'تكبير', zout: 'تصغير', map: 'الخريطة', marker: 'علامة على الخريطة', attrib: 'مساهمو OpenStreetMap' },
			};
			for ( const lang of [ 'fa', 'en', 'ar' ] ) {
				if ( ! prov[ lang ] ) { check( `۲ج٫۸ نقشۀ ${ lang }: صفحۀ استان هست`, false ); continue; }
				const { ctx, page } = await open( browser, prov[ lang ], 1440, asOf( prov, lang ) );
				await page.locator( '[data-tp-map-open]' ).first().click();
				const ok = await page.waitForFunction( () => {
					const f = document.querySelector( '[data-tp-map-full]' );
					return !! ( f && f.tpMap && f.tpMap.loaded() && f.tpMap.areTilesLoaded() );
				}, null, { timeout: 90000 } ).then( () => true ).catch( () => false );
				await page.waitForTimeout( 1500 );
				const m = ok ? await page.evaluate( () => {
					const f   = document.querySelector( '[data-tp-map-full]' );
					const lay = JSON.stringify( f.tpMap.getStyle().layers );
					const at  = sel => { const e = f.querySelector( sel ); return e ? e : null; };
					return {
						label:  ( lay.match( /name:(fa|en|ar)/ ) || [] )[1] || '',
						zin:    ( at( '.maplibregl-ctrl-zoom-in' ) || {} ).title || '',
						zout:   ( at( '.maplibregl-ctrl-zoom-out' ) || {} ).title || '',
						map:    at( '.maplibregl-canvas' ) ? at( '.maplibregl-canvas' ).getAttribute( 'aria-label' ) : '',
						marker: at( '.maplibregl-marker' ) ? at( '.maplibregl-marker' ).getAttribute( 'aria-label' ) : '',
						attrib: ( at( '.maplibregl-ctrl-attrib-inner' ) || {} ).textContent || '',
					};
				} ) : null;
				const w = want8[ lang ];
				const shot = path.join( SCREENS, `2c8-map-${ lang }-${ engine === chromium ? 'chrome' : 'firefox' }.png` );
				if ( ok ) { await page.locator( '[data-tp-map-full]' ).screenshot( { path: shot } ); }
				check( `۲ج٫۸ ${ bname } نقشۀ ${ lang }: برچسب کاشی name:${ lang }، دکمه‌های بزرگ‌نمایی، aria-label نقشه و نشانگر و انتساب به زبان صفحه؛ عکس ${ path.basename( shot ) }  P5-D278`,
					!! m && lang === m.label && w.zin === m.zin && w.zout === m.zout && w.map === m.map && w.marker === m.marker && m.attrib.includes( w.attrib ),
					JSON.stringify( m ) );
				await ctx.close();
			}
		}

		await browser.close();
	}
	} finally {
		if ( L ) {
			const left = langCleanup( L );
			if ( left.left ) { check( '۲ج٫۸ پاک‌سازی ترجمه‌های موقت', false, left.left + ' پست ماند' ); }
		}
	}

	console.log( '\n' + '─'.repeat( 78 ) + `\n  گذشته: ${ pass }   افتاده: ${ fail }` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
