/**
 * دستۀ 47 — روی نسخۀ محلی (فقط topersia.local): بازدارنده‌های راه‌اندازی و صفحۀ اول.
 *
 *   node tests/w47-local.js            ← جای‌نگهدار حریم خصوصی فقط هشدار
 *   node tests/w47-local.js --launch   ← جای‌نگهدار «[تکمیل …]» یا TODO در صفحۀ منتشرشده قرمز
 *
 * بخش ۱: نام برند در <title>، og و JSON-LD (P5-D518)؛ جملۀ abuse@ در شرایط و تماس و نبود «گزارش مشکل»
 * (P5-D524)؛ خط اعتبار پاورقی (P5-D520)؛ فاصلۀ لبۀ پایین هدر تا لبۀ بالای هیرو = ۰ در ۱۴۴۰ و ۳۹۰
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
			const copy = text( ( h.match( /<p class="tp-footer__copy">([\s\S]*?)<\/p>/ ) || [] )[ 1 ] || '' );
			t( `${l}: کپی‌رایت پاورقی با نام ${BRAND[ l ]}`, 'en' === l ? /^© \S+ ToPersia\./.test( copy ) && ! /توپرشیا|توبيرشيا|توبرشيا/.test( copy ) : copy.includes( `ToPersia (${BRAND[ l ]})` ) && ( copy.match( new RegExp( BRAND[ l ], 'g' ) ) || [] ).length >= 2, copy.slice( 0, 120 ) );
			t( `${l}: خط اعتبار پاورقی هست (option topersia_footer_credits پیش‌فرض روشن — P5-D520)`, /<p class="tp-footer__credit">[\s\S]*?amesterida\.com[\s\S]*?bashash\.ir/.test( h ) );
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

		if ( 'function' === typeof globalThis.w47Section2 ) { await globalThis.w47Section2( { b, get, t, warn, BASE, PFX, X } ); }
	} finally {
		await b.close();
	}
	console.log( '\n' + '─'.repeat( 78 ) + `\n  گذشته: ${pass}   افتاده: ${fail}` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
