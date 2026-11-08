/**
 * دستۀ 47 — روی نسخۀ محلی (فقط topersia.local): بازدارنده‌های راه‌اندازی و صفحۀ اول.
 *
 *   node tests/w47-local.js            ← جای‌نگهدار حریم خصوصی فقط هشدار
 *   node tests/w47-local.js --launch   ← جای‌نگهدار «[تکمیل …]» یا TODO در صفحۀ منتشرشده قرمز
 *
 * بخش ۱: نام برند در <title>، og و JSON-LD (P5-D518)؛ جملۀ abuse@ در شرایط و تماس و نبود «گزارش مشکل»
 * (P5-D524)؛ نبود پاورقی افزونه (P5-D528، جای P5-D520)؛ فاصلۀ لبۀ پایین هدر تا لبۀ بالای هیرو = ۰ در ۱۴۴۰ و ۳۹۰
 * با getBoundingClientRect (P5-D525) و جهش: padding برگردانده شود باید دیده شود.
 */
'use strict';

const { chromium } = require( './pw.js' );
const ENV = require( './env.js' );

if ( 'topersia.local' !== ENV.host ) { console.error( 'فقط topersia.local' ); process.exit( 2 ); }

const BASE   = ENV.base;
const LAUNCH = process.argv.includes( '--launch' );
const X      = Number( process.env.TP_TIMEOUT_X || 1 );
let pass = 0, fail = 0;
const t = ( label, ok, note ) => { ok ? pass++ : fail++; console.log( '  ' + ( ok ? '✅' : '❌' ) + ' ' + label + ( ok || ! note ? '' : '   ← ' + note ) ); };
const warn = s => console.log( '  ⚠ ' + s );

const BRAND = { fa: 'توپرشیا', en: 'ToPersia', ar: 'توبيرشيا' };
const OLD_AR = 'توبرشيا';   /* شکل دیگر — فقط در alternateName، هرگز در متن دیدنی (اصلاح P5-D518) */
const PFX   = { fa: '', en: '/en', ar: '/ar' };
const SENT  = {
	fa: 'برای گزارش خطا، محتوای ناقض حقوق یا درخواست حذف، به abuse@topersia.com ایمیل بفرستید.',
	en: 'To report an error, infringing content or a removal request, email abuse@topersia.com.',
	ar: 'للإبلاغ عن خطأ أو محتوى مخالف أو طلب حذف، راسلونا على abuse@topersia.com.',
};
const decode = s => s.replace( /&#0*39;|&#x27;/g, "'" ).replace( /&quot;/g, '"' ).replace( /&amp;/g, '&' ).replace( /&#8211;/g, '–' ).replace( /&#8212;/g, '—' );
const text   = h => decode( h.replace( /<script[\s\S]*?<\/script>|<style[\s\S]*?<\/style>/g, '' ).replace( /<[^>]+>/g, ' ' ) ).replace( /\s+/g, ' ' );

( async () => {
	const b = await chromium.launch();
	try {
		const ctx = await b.newContext();
		const get = async u => { const r = await ctx.request.get( BASE + u, { timeout: 60000 * X } ); return [ r.status(), await r.text() ]; };

		console.log( '\nبخش ۱ — نام برند (P5-D518)' );
		for ( const l of [ 'fa', 'en', 'ar' ] ) {
			for ( const u of [ PFX[ l ] + '/', PFX[ l ] + '/terms/', PFX[ l ] + '/province/isfahan/' ] ) {
				const [ c, h ] = await get( u );
				const title = decode( ( h.match( /<title>([^<]*)<\/title>/ ) || [] )[ 1 ] || '' );
				const site  = decode( ( h.match( /<meta property="og:site_name" content="([^"]*)"/ ) || [] )[ 1 ] || '' );
				const ogt   = decode( ( h.match( /<meta property="og:title" content="([^"]*)"/ ) || [] )[ 1 ] || '' );
				const latin = 'en' !== l && /(^|\s)ToPersia(\s|$)/.test( title.replace( /ToPersia \([^)]*\)/, '' ) );
				t( `${l} ${u}: <title> و og:title با «${BRAND[ l ]}»، og:site_name = «${BRAND[ l ]}»`, 200 === c && title.includes( BRAND[ l ] ) && ogt.includes( BRAND[ l ] ) && site === BRAND[ l ] && ! latin, `${c} title=${title} og=${ogt} site=${site}` );
				/* اصلاح P5-D518: «توبرشيا» فقط در alternateName — نه در عنوان، متا، بدنه یا پاورقی (کل HTML جز JSON-LD). */
				const vis = h.replace( /<script type="application\/ld\+json"[^>]*>[\s\S]*?<\/script>/g, '' );
				t( `${l} ${u}: املای پیشین «${OLD_AR}» در هیچ متن دیدنی و متا نیست`, ! vis.includes( OLD_AR ), ( vis.match( new RegExp( '.{0,40}' + OLD_AR + '.{0,40}' ) ) || [ '' ] )[ 0 ] );
			}
			const [ , h ] = await get( PFX[ l ] + '/' );
			const lds = [ ...h.matchAll( /<script type="application\/ld\+json"[^>]*>([\s\S]*?)<\/script>/g ) ].map( m => { try { return JSON.parse( m[ 1 ] ); } catch ( e ) { return null; } } ).filter( Boolean );
			const nodes = lds.flatMap( j => j[ '@graph' ] || [ j ] );
			const org = nodes.find( n => 'Organization' === n[ '@type' ] ), web = nodes.find( n => 'WebSite' === n[ '@type' ] );
			const altOk = n => n && 'ToPersia' === n.name && JSON.stringify( n.alternateName ) === JSON.stringify( [ 'توپرشیا', 'توبيرشيا', 'توبرشيا' ] );
			t( `${l} صفحۀ اول: Organization و WebSite — name ToPersia، alternateName [توپرشیا، توبيرشيا، توبرشيا]`, altOk( org ) && altOk( web ), JSON.stringify( { org, web } ).slice( 0, 300 ) );
			/* P5-D520 جایگزین شد: P5-D528 — افزونه در پاورقی هیچ خروجی ندارد (option topersia_footer_credits پیش‌فرض «0») */
			t( `${l}: پاورقی افزونه نیست — بی .tp-footer (P5-D528؛ متن کپی‌رایت از تنظیم خود وودمارت می‌آید و دست نمی‌خورد)`, ! /class="tp-footer/.test( h ) );
		}

		console.log( '\nبخش ۱ — «گزارش مشکل» (P5-D524) و جای‌نگهدار' );
		for ( const l of [ 'fa', 'en', 'ar' ] ) {
			const want = { terms: 2, contact: 1 };
			for ( const slug of [ 'terms', 'contact', 'about', 'privacy-policy' ] ) {
				const [ c, h ] = await get( PFX[ l ] + '/' + slug + '/' );
				const body = text( ( h.match( /<main[\s\S]*<\/main>/ ) || [ h ] )[ 0 ] );
				const n = body.split( SENT[ l ] ).length - 1;
				if ( want[ slug ] ) { t( `${l} /${slug}/: جملۀ abuse@ عیناً ${want[ slug ]} بار`, 200 === c && n === want[ slug ], `${c} n=${n}` ); }
				t( `${l} /${slug}/: بی ارجاع «گزارش مشکل»`, 200 === c && ! /گزارش مشکل|Report a problem|الإبلاغ عن مشكلة/.test( body ) );
				const ph = body.match( /\[(?:تکمیل|to be completed|يُستكمل)[^\]]*\]|\bTODO\b/g ) || [];
				if ( LAUNCH ) { t( `${l} /${slug}/: --launch — هیچ جای‌نگهدار`, 0 === ph.length, ph.join( ' · ' ) ); }
				else if ( ph.length ) { warn( `${l} /${slug}/: ${ph.length} جای‌نگهدار (با --launch قرمز): ${ph.join( ' · ' )}` ); }
			}
		}

		console.log( '\nبخش ۱ — هدر و هیرو (P5-D525)' );
		const probe = () => {
			scrollTo( 0, 0 );
			const h = document.querySelector( 'header.whb-header' ), hero = document.querySelector( '.tp-home-hero' );
			if ( ! h || ! hero ) { return null; }
			const general = h.querySelector( '.whb-general-header' );
			const mid = general ? ( general.getBoundingClientRect().top + general.getBoundingClientRect().bottom ) / 2 : 0;
			const off = general ? [ ...general.querySelectorAll( '.whb-column > *' ) ].filter( e => e.getBoundingClientRect().height ).map( e => Math.abs( ( e.getBoundingClientRect().top + e.getBoundingClientRect().bottom ) / 2 - mid ) ) : [];
			return { gap: hero.getBoundingClientRect().top - h.getBoundingClientRect().bottom, header: h.getBoundingClientRect().height, offMax: off.length ? Math.max( ...off ) : -1 };
		};
		for ( const [ w, hgt, mob ] of [ [ 1440, 900, false ], [ 390, 844, true ] ] ) {
			const c2 = await b.newContext( { viewport: { width: w, height: hgt }, isMobile: mob, hasTouch: mob } );
			const p = await c2.newPage();
			for ( const l of [ 'fa', 'en', 'ar' ] ) {
				await p.goto( BASE + PFX[ l ] + '/', { waitUntil: 'load', timeout: 90000 * X } );
				const r = await p.evaluate( probe );
				t( `${w} · ${l}: لبۀ پایین هدر = لبۀ بالای هیرو (فاصله ${r && Math.round( r.gap * 10 ) / 10}px، هدر ${r && Math.round( r.header )}px)`, r && Math.abs( r.gap ) < 0.5, JSON.stringify( r ) );
				t( `${w} · ${l}: عنصرهای ردیف اصلی هدر هم‌تراز عمودی (بیشینۀ انحراف از وسط ${r && Math.round( r.offMax )}px)`, r && r.offMax >= 0 && r.offMax <= 1, JSON.stringify( r ) );
			}
			await p.addStyleTag( { content: 'html .tp-page.tp-home > .tp-main{padding-block-start:44px !important}' } );
			const m = await p.evaluate( probe );
			t( `${w} · جهش: padding بالای .tp-main برگردد، فاصله دیده می‌شود`, m && m.gap > 10, JSON.stringify( m ) );
			await c2.close();
		}

		console.log( '\nبخش ۲ — صفحۀ اول طبق مرجع (فقط دادۀ واقعی)' );
		for ( const l of [ 'fa', 'en', 'ar' ] ) {
			const [ , h ] = await get( PFX[ l ] + '/' );
			const main = ( h.match( /<main[\s\S]*<\/main>/ ) || [ '' ] )[ 0 ];
			const imgs = [ ...main.matchAll( /<img\b[^>]*>/g ) ].map( m => m[ 0 ] );
			const ext  = imgs.filter( i => /\ssrc="https?:\/\/(?!topersia\.local)/.test( i ) );
			t( `${l}: هیچ تصویر بیرونی در صفحۀ اول`, 0 === ext.length, ext.slice( 0, 2 ).join( ' ' ) );
			const lazyBad = imgs.filter( i => ! /tp-home-hero__img|tp-home-photodlg__img/.test( i ) && ( ! /loading="lazy"/.test( i ) || ! /\swidth="\d+"/.test( i ) || ! /\sheight="\d+"/.test( i ) ) );
			t( `${l}: هر <img> جز هیرو lazy با width و height`, 0 === lazyBad.length, lazyBad.slice( 0, 2 ).join( ' ' ) );
			const tips = [ ...main.matchAll( /data-tp-tip="([^"]*)"/g ) ].map( m => decode( m[ 1 ] ) );
			/* هر استان دارای صفحه به این زبان یک پیوند و یک راهنما؛ fa هر ۳۱. بقیه کم‌رنگ و بی پیوند (ترجمۀ منتشرنشده). */
			const links = ( main.match( /<a class="tp-home-map__a"/g ) || [] ).length;
			t( `${l}: راهنمای نقشه برای هر استان دارای صفحه (${tips.length} از ${links} پیوند)${'fa' === l ? '، fa هر ۳۱' : ''}، بی «۰»`, tips.length === links && links > 0 && ( 'fa' !== l || 31 === links ) && tips.every( s => s.trim() && ! /(^|\s)[0۰٠](\s)/.test( s ) ), tips.length + '/' + links + ' ' + tips.filter( s => /(^|\s)[0۰٠]\s/.test( s ) ).join( ' | ' ) );
			const dlg = ( main.match( /<ul id="tp-home-provlist"[\s\S]*?<\/ul>/ ) || [ '' ] )[ 0 ];
			const ns  = ( main.match( /<noscript><ul class="tp-home-provall[\s\S]*?<\/ul><\/noscript>/ ) || [ '' ] )[ 0 ];
			t( `${l}: پنجرۀ استان‌ها ۳۱ ردیف و همان فهرست در noscript`, 31 === ( dlg.match( /<li\b/g ) || [] ).length && 31 === ( ns.match( /<li\b/g ) || [] ).length );
			t( `${l}: ردیف بی صفحه بی پیوند`, ( dlg.match( /<li class="tp-home-provall__off"[^>]*><span/g ) || [] ).length === ( dlg.match( /tp-home-provall__off/g ) || [] ).length );
			const dest = ( main.match( /class="tp-home-dest"/g ) || [] ).length;
			t( `${l}: محبوب‌ترین مقاصد ۰ یا ۴ تا ۱۲ کارت (${dest})`, 0 === dest || ( dest >= 4 && dest <= 12 ) );
			const photos = [ ...main.matchAll( /<a class="tp-home-photo"[^>]*>/g ) ].map( m => m[ 0 ] );
			const fulls  = photos.map( a => ( a.match( /data-tp-full="([^"]*)"/ ) || [] )[ 1 ] );
			t( `${l}: ایران در تصویر ۰ یا ≥ ۴ عکس یکتا، هر کدام عنوان و پیوند (${photos.length})`, ( 0 === photos.length || photos.length >= 4 ) && new Set( fulls ).size === fulls.length && photos.every( a => /data-tp-title="[^"]+"/.test( a ) && /href="http/.test( a ) ) && ( 0 === photos.length ) === ! /id="tp-home-photodlg"/.test( main ) );
			t( `${l}: بی نمونه — هیچ پیوند «sample» یا متن نمونه`, ! /\bsample\b|lorem|نمونه‌ای/i.test( text( main ) ) );
		}
		for ( const [ w, hgt, mob ] of [ [ 1440, 900, false ], [ 390, 844, true ], [ 320, 700, true ] ] ) {
			const c2 = await b.newContext( { viewport: { width: w, height: hgt }, isMobile: mob, hasTouch: mob } );
			const p = await c2.newPage();
			for ( const l of ( 320 === w ? [ 'fa' ] : [ 'fa', 'en', 'ar' ] ) ) {
				await p.goto( BASE + PFX[ l ] + '/', { waitUntil: 'load', timeout: 90000 * X } );
				const r = await p.evaluate( () => {
					const R = s => { const e = document.querySelector( s ); return e ? e.getBoundingClientRect() : null; };
					const hero = R( '.tp-home-hero' ), st = R( '#tp-home-stats' ), mp = R( '.tp-home-prov__map' ), tl = R( '.tp-home-prov__tiles' );
					return { sw: document.documentElement.scrollWidth, cw: document.documentElement.clientWidth,
						half: hero && st ? ( st.top + st.height / 2 ) - hero.bottom : null,
						eq: mp && tl ? Math.abs( mp.height - tl.height ) : null, side: mp && tl ? Math.abs( mp.top - tl.top ) < 1 : null,
						btns: [ ...document.querySelectorAll( '#tp-home-provslide + .tp-slider-bar button[data-target],[data-tp-provdlg]' ) ].filter( e => ! e.hidden ).length,
						prov: document.querySelectorAll( '#tp-home-provslide > *' ).length,
						js: !! [ ...document.scripts ].find( s => /topersia-g-home\.js/.test( s.src ) ) };
				} );
				t( `${w} · ${l}: بی سرریز افقی (${r.sw} ≤ ${r.cw})`, r.sw <= r.cw, JSON.stringify( r ) );
				if ( 320 === w ) { continue; }
				t( `${w} · ${l}: نوار شمار دقیقاً نیمه روی لبۀ هیرو (${r.half === null ? '—' : Math.round( r.half * 10 ) / 10}px)`, null === r.half || Math.abs( r.half ) <= 1, JSON.stringify( r ) );
				if ( 1440 === w ) { t( `1440 · ${l}: ستون نقشه و ستون کاشی‌ها کنار هم و هم‌قد (اختلاف ${Math.round( r.eq )}px)`, r.side && r.eq <= 1, JSON.stringify( r ) ); }
				t( `${w} · ${l}: JS صفحۀ اول بار شد و دکمه‌های اسلایدر و «همۀ استان‌ها» پیدا شدند`, r.js && ( r.prov >= 2 ? 3 : 1 ) === r.btns, JSON.stringify( r ) );
				if ( 'fa' !== l ) { continue; }
				const sl = await p.evaluate( async () => {
					const box = document.getElementById( 'tp-home-provslide' ), x0 = box.scrollLeft;
					const nx = document.querySelector( '[data-target="tp-home-provslide"][data-dir="1"]' );
					if ( nx ) { nx.click(); }
					await new Promise( z => setTimeout( z, 1200 ) );
					return { x0, x1: box.scrollLeft, max: box.scrollWidth - box.clientWidth };
				} );
				t( `${w} · fa: دکمۀ «بعدی» اسلایدر را جابه‌جا می‌کند`, sl.max <= 0 || Math.abs( sl.x1 - sl.x0 ) > 20, JSON.stringify( sl ) );
				await p.click( '[data-tp-provdlg]' );
				const dg = await p.evaluate( () => { const d = document.getElementById( 'tp-home-provdlg' ); return { open: d.open, modal: d.matches( ':modal' ), n: d.querySelectorAll( 'li' ).length }; } );
				t( `${w} · fa: «همۀ استان‌ها» پنجرۀ مودال با ۳۱ استان باز می‌کند`, dg.open && dg.modal && 31 === dg.n, JSON.stringify( dg ) );
				await p.fill( '#tp-home-provq', 'اصفهان' );
				const sq = await p.evaluate( () => [ ...document.querySelectorAll( '#tp-home-provlist li' ) ].filter( li => ! li.hidden ).map( li => li.textContent.trim() ) );
				t( `${w} · fa: جست‌وجوی «اصفهان» فقط اصفهان (${sq.join( '، ' )})`, 1 === sq.length && /اصفهان/.test( sq[ 0 ] ), sq.join( '، ' ) );
				await p.keyboard.press( 'Escape' );
				const ac = await p.evaluate( () => ( { open: document.getElementById( 'tp-home-provdlg' ).open, focus: document.activeElement && document.activeElement.hasAttribute( 'data-tp-provdlg' ) } ) );
				t( `${w} · fa: Esc پنجره را می‌بندد و تمرکز به دکمه برمی‌گردد`, ! ac.open && ac.focus, JSON.stringify( ac ) );
				if ( 1440 === w ) {
					const tp = await p.evaluate( async () => {
						const a = document.querySelector( '.tp-home-map [data-tp-tip]' ), before = document.querySelector( '.tp-home-prov' ).getBoundingClientRect().height;
						a.scrollIntoView( { behavior: 'instant', block: 'center' } );
						a.focus();
						const tip = document.querySelector( '.tp-home-map__tip' );
						return { shown: ! tip.hidden, txt: tip.textContent, want: a.getAttribute( 'data-tp-tip' ), shift: Math.abs( document.querySelector( '.tp-home-prov' ).getBoundingClientRect().height - before ) };
					} );
					t( `1440 · fa: فوکوس صفحه‌کلید روی استان راهنما را نشان می‌دهد، بی جابه‌جایی چیدمان (${tp.txt})`, tp.shown && tp.txt === tp.want && tp.shift < 0.5, JSON.stringify( tp ) );
					const ph = await p.$( '.tp-home-photo[data-tp-full]' );
					if ( ph ) {
						await ph.evaluate( e => e.scrollIntoView( { behavior: 'instant', block: 'center' } ) );
						await ph.click();
						const pg = await p.evaluate( () => { const d = document.getElementById( 'tp-home-photodlg' ); return { open: d.open, h2: d.querySelector( 'h2' ).textContent, img: d.querySelector( 'img' ).getAttribute( 'src' ), go: d.querySelector( '.tp-home-photodlg__go' ).href }; } );
						t( `1440 · fa: کلیک عکس پنجرۀ عکس با عنوان، تصویر بزرگ و پیوند موجودیت باز می‌کند`, pg.open && pg.h2 && /^http/.test( pg.img ) && /^http/.test( pg.go ), JSON.stringify( pg ) );
						await p.keyboard.press( 'Escape' );
					}
				}
			}
			await c2.close();
		}
	} finally {
		await b.close();
	}
	console.log( '\n' + '─'.repeat( 78 ) + `\n  گذشته: ${pass}   افتاده: ${fail}` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
