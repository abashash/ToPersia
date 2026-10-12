/**
 * آزمون رفتاری ۴٫۴٫۱ — کروم و فایرفاکس Playwright، روی صفحهٔ واقعیِ محلی
 * و صفحهٔ چپ‌به‌راستِ هارنس.
 *
 *   node tests/behaviour.js [url …]
 *
 *   P5-D243 فلش‌ها   تعریف کاربر: در صفحهٔ فارسی و عربی، آغاز اسلایدر، فلش
 *                    چپ «‹» فعال است. «بعدی» = «‹» سمت چپ، «قبلی» = «›» سمت
 *                    راست و در آغاز غیرفعال؛ «بعدی» کارتِ پنهانِ چپ را کامل
 *                    پیش می‌آورد. تمام‌صفحه: «بعدی» لبهٔ چپ، ArrowLeft بعدی،
 *                    کشیدن از چپ به راست بعدی. چپ‌به‌راست همه برعکس — روی
 *                    صفحهٔ `province-ltr` هارنس. ۱۴۴۰ و ۳۹۰.
 *   P5-D244 کاشی     سرریز ← دوستونه؛ جا شد ← سه ستون؛ «{N} مورد دیگر» دسته‌به‌دسته
 *   P5-D245 توکن     در هر سه حالت، پس‌زمینهٔ هر کاشی و دکمهٔ پنجرهٔ فهرست
 *                    همان کاشی روزِ هواست؛ متن روی کاشی ≥ ۴٫۵
 *   P5-D246 کارت     بلندی کارت‌های هر اسلایدر، فرهنگ، پنجرهٔ گالری و پنجرهٔ
 *                    فهرست ±۱ پیکسل یکی؛ هر <img> width و height
 *   P5-D266 کارت     تصویر کارت اسلایدر به اندازهٔ دیدنی ۲۰۰ (موبایل ۱۸۰)، بدنه‌ها
 *                    یک‌اندازه، بی توضیح — اندازهٔ دیدنی، نه اندازهٔ عنصر
 *   P5-D267 تا P5-D270  دعوت هوش مصنوعی و پنجرهٔ ورود، تاریخ منبع، کاشی مسیرها،
 *                    متن دوطرفه — با عکس ۳۹۰ اگر TP_SHOTS
 *   ۲٫۰٫۸ زبان       /en/ و /ar/ واقعیِ استان و شهر اصفهان: جهت، شرط زبان WPML
 *                    برای هر پیوند درونی؛ هم‌مرز فارسی یک بار (P5-D247)
 *   ۱٫۵ خبر          جعبهٔ تصویر ۱۱۲×۸۴ و در موبایل ۸۸×۶۶
 *   §۱۲ نوار پایین · §۵ اشتراک و مهمان · §۱ نقشه · §۳ انیمیشن هوا  (۴٫۳٫۰)
 *
 * درخواست به topersia.com بسته است. کد خروج: ۰ سالم · ۱ افتادن.
 */

const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );   /* مسیرها و نشانی پایه — tests/env.json (P5-D285) */
const { execFileSync } = require( 'child_process' );
const fs   = require( 'fs' );
const os   = require( 'os' );
const path = require( 'path' );

const URLS = process.argv.slice( 2 ).filter( a => /^https?:/.test( a ) );
if ( ! URLS.length ) {
	URLS.push( ENV.base + '/province/isfahan/', ENV.base + '/city/isfahan/' );
}

let pass = 0, fail = 0;
function check( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { fail ++; console.log( '  ❌ ' + label + ( note ? '   ← ' + note : '' ) ); }
}
function info( t ) { console.log( '  · ' + t ); }

/* صفحه‌های هارنس — همان tests/preview.php، استان راست‌به‌چپ و چپ‌به‌راست */
function harness() {
	const dir = fs.mkdtempSync( path.join( os.tmpdir(), 'tp-behaviour-' ) );
	const wamp = ENV.php;
	const php = process.env.TP_PHP || ( fs.existsSync( wamp ) ? wamp : 'php' );
	execFileSync( php, [ path.join( __dirname, 'preview.php' ), dir ], { stdio: 'ignore' } );
	const url = f => 'file:///' + path.join( dir, f ).replace( /\\/g, '/' );
	return { rtl: url( 'province.html' ), ltr: url( 'province-ltr.html' ), more: url( 'province-more.html' ) };
}

async function open( browser, url, opts ) {
	const ctx = await browser.newContext( Object.assign( { viewport: { width: 1440, height: 900 } }, opts || {} ) );
	await ctx.route( /topersia\.com/, r => r.abort() );
	const page = await ctx.newPage();
	await page.goto( url, { waitUntil: 'load', timeout: 120000 } );
	await page.waitForTimeout( 500 );
	return { ctx, page };
}

/* ── P5-D346 نشانگرهای نقشه: خوشه، آیکون، پنجره، حالت تیره، زبان ── */
async function mapPoints( browser, url, tag ) {

	const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
	await ctx.route( /topersia\.com/, r => r.abort() );
	const page = await ctx.newPage();
	await page.goto( url, { waitUntil: 'load', timeout: 240000 } );

	const opener = page.locator( '[data-tp-map-open]' ).first();

	if ( ! await opener.count() ) {
		info( `${ tag }: دکمهٔ نقشهٔ کامل نیست — نشانگر سنجیده نشد` );
		await ctx.close();
		return;
	}

	const cfg = await page.evaluate( () => { const m = document.querySelector( '[data-tp-map-full]' ); try { return JSON.parse( m.getAttribute( 'data-tp-map-cfg' ) ); } catch ( e ) { return {}; } } );

	if ( ! cfg.points ) {
		info( `${ tag }: این نوع نشانگر ندارد (points در پیکربندی نیست)` );
		await ctx.close();
		return;
	}

	const rest = await page.evaluate( async u => ( await ( await fetch( u ) ).json() ), cfg.points );
	await opener.scrollIntoViewIfNeeded();
	await opener.click();
	await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-full]' ); return b && b.tpMap && b.tpMap.getSource( 'tp-pts' ) && b.tpMap.isStyleLoaded(); }, null, { timeout: 120000 } );
	await page.waitForTimeout( 1500 );

	const st = await page.evaluate( () => {
		const m = document.querySelector( '[data-tp-map-full]' ).tpMap;
		const src = m.getSource( 'tp-pts' );
		return {
			cluster: !! ( src.serialize() || {} ).cluster, n: ( src.serialize().data.features || [] ).length,
			layers: [ 'tp-pts-cluster', 'tp-pts-count', 'tp-pts-icon' ].map( l => !! m.getLayer( l ) ),
			images: [ 'city', 'attraction', 'nature', 'district', 'village' ].map( f => m.hasImage( 'tp-fam-' + f ) ),
			rendered: m.queryRenderedFeatures( { layers: [ 'tp-pts-cluster', 'tp-pts-icon' ] } ).length,
		};
	} );
	check( `${ tag } نقشه: نشانگرها از map-points (${ rest.features.length }) با خوشه‌بندی داخلی، سه لایه و پنج آیکون خانواده؛ چیزی روی بوم دیده می‌شود`,
		rest.features.length > 0 && st.cluster && st.n === rest.features.length && st.layers.every( Boolean ) && st.images.every( Boolean ) && st.rendered > 0, JSON.stringify( { st, rest: rest.features.length } ) );

	/* کلیک خوشه ← بزرگ‌نمایی؛ کلیک نقطه ← پنجره */
	const pick = ( layer ) => page.evaluate( ( l ) => {
		const box = document.querySelector( '[data-tp-map-full]' ), m = box.tpMap;
		const f = m.queryRenderedFeatures( { layers: [ l ] } )[ 0 ];
		if ( ! f ) { return null; }
		const p = m.project( f.geometry.coordinates ), r = m.getCanvas().getBoundingClientRect();
		return { x: r.left + p.x, y: r.top + p.y, z: m.getZoom() };
	}, layer );

	const cl = await pick( 'tp-pts-cluster' );
	if ( cl ) {
		await page.mouse.click( cl.x, cl.y );
		await page.waitForTimeout( 1500 );
		const z = await page.evaluate( () => document.querySelector( '[data-tp-map-full]' ).tpMap.getZoom() );
		check( `${ tag } نقشه: کلیک خوشه بزرگ‌نمایی می‌کند (${ cl.z.toFixed( 1 ) } ← ${ z.toFixed( 1 ) })`, z > cl.z + 0.4 );
	} else {
		info( `${ tag }: خوشه‌ای در دید نبود (همه نقطه‌اند)` );
	}

	let pt = await pick( 'tp-pts-icon' );
	if ( ! pt ) {
		await page.evaluate( () => { const m = document.querySelector( '[data-tp-map-full]' ).tpMap; m.jumpTo( { zoom: 13 } ); } );
		await page.waitForTimeout( 1200 );
		pt = await pick( 'tp-pts-icon' );
	}

	if ( pt ) {
		await page.mouse.click( pt.x, pt.y );
		await page.waitForSelector( '.maplibregl-popup .tp-mp__name', { timeout: 20000 } ).catch( () => {} );
		const pop = await page.evaluate( () => {
			const n = document.querySelector( '.maplibregl-popup .tp-mp__name' ), k = document.querySelector( '.maplibregl-popup .tp-mp__kind' );
			return n ? { name: n.textContent, href: n.getAttribute( 'href' ) || '', kind: k ? k.textContent : '', bg: getComputedStyle( document.querySelector( '.maplibregl-popup-content' ) ).backgroundColor } : null;
		} );
		check( `${ tag } نقشه: کلیک نقطه پنجرهٔ کوچک با نام، نوع و پیوند به صفحهٔ همان موجودیت باز می‌کند («${ pop && pop.name }» · ${ pop && pop.kind })`,
			pop && pop.name && pop.kind && /^https?:\/\//.test( pop.href ) && rest.features.some( f => f.properties.n === pop.name ), JSON.stringify( pop ) );
	} else {
		check( `${ tag } نقشه: دست‌کم یک نقطهٔ تکی برای کلیک پیدا شد`, false );
	}

	/* حالت‌ها: روز ← روشن؛ عصر و شب ← تیره؛ نشانگرها پس از عوض‌شدن سبک می‌مانند */
	const flavors = [];
	for ( const mode of [ 'dark', 'evening', 'light' ] ) {
		await page.evaluate( m => document.documentElement.setAttribute( 'data-tp-mode', m ), mode );
		await page.waitForFunction( () => { const b = document.querySelector( '[data-tp-map-full]' ); return b.tpMap.isStyleLoaded() && b.tpMap.getSource( 'tp-pts' ); }, null, { timeout: 60000 } );
		await page.waitForTimeout( 800 );
		flavors.push( await page.evaluate( () => { const b = document.querySelector( '[data-tp-map-full]' ); return { f: b.tpFlavor, bg: b.tpMap.getPaintProperty( b.tpMap.getStyle().layers[ 0 ].id, 'background-color' ), pts: !! b.tpMap.getLayer( 'tp-pts-icon' ) }; } ) );
	}
	check( `${ tag } نقشه: شب و عصر ← سبک تیره، روز ← روشن (رنگ زمینهٔ سبک عوض می‌شود)؛ نشانگرها پس از عوض‌شدن سبک می‌مانند`,
		'dark' === flavors[0].f && 'dark' === flavors[1].f && 'light' === flavors[2].f && flavors[0].bg !== flavors[2].bg && flavors.every( x => x.pts ), JSON.stringify( flavors ) );

	await ctx.close();
}

/* ── P5-D345 کیفیت هوا: اسکلتِ هم‌اندازه، پر شدن پس از بارگذاری بی جابه‌جایی، خطا ← «—»، رنگ رده از توکن ── */
async function airBox( browser, url, tag ) {

	for ( const [ label, fail ] of [ [ 'پاسخ', false ], [ 'خطا', true ] ] ) {

		const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
		await ctx.addInitScript( CLS_INIT );
		await ctx.route( /topersia\.com/, r => r.abort() );
		/* پاسخ تا لحظه‌ای که آزمون اسکلت را سنجید و آرامشِ صفحه (کارت هوا و …) را گرفت معلق می‌ماند؛ فقط جابه‌جاییِ همین پر شدن شمرده می‌شود */
		let release;
		const gate = new Promise( z => { release = z; } );
		await ctx.route( /topersia\/v1\/air/, async r => {
			await gate;
			return fail ? r.abort() : r.fulfill( { status: 200, contentType: 'application/json', body: JSON.stringify( { aqi: 160, cat: 4, label: 'ناسالم', pm25: 55.4, pm10: 80.2, dust: 12, model: 'برآورد مدل CAMS' } ) } );
		} );
		const page = await ctx.newPage();
		await page.goto( url, { waitUntil: 'load', timeout: 240000 } );

		const box = page.locator( '[data-tp-air]' );

		if ( ! await box.count() ) {
			check( `${ tag } کیفیت هوا (${ label }): جعبه در صفحه هست`, false );
			await ctx.close();
			continue;
		}

		await page.waitForTimeout( 2500 );
		const skel = await box.evaluate( e => ( { h: e.getBoundingClientRect().height, load: e.classList.contains( 'is-load' ), idx: e.querySelector( '[data-tp-air-idx]' ).textContent } ) );
		const cls0 = await page.evaluate( () => window.__tpCls );
		release();
		await page.waitForFunction( () => ! document.querySelector( '[data-tp-air]' ).classList.contains( 'is-load' ), null, { timeout: 60000 } );
		await page.waitForTimeout( 500 );
		const after = await box.evaluate( e => {
			const probe = document.createElement( 'span' );
			probe.style.color = 'var(--tp-aqi-4)';
			document.body.appendChild( probe );
			const token = getComputedStyle( probe ).color;
			probe.remove();
			return {
				h: e.getBoundingClientRect().height, idx: e.querySelector( '[data-tp-air-idx]' ).textContent, cat: e.querySelector( '[data-tp-air-cat]' ).textContent,
				attr: e.getAttribute( 'data-tp-cat' ), pm: e.querySelector( '[data-tp-air-pm25]' ).textContent, dust: e.querySelector( '[data-tp-air-dust]' ).textContent,
				color: getComputedStyle( e.querySelector( '[data-tp-air-cat]' ) ).color, token,
				load: e.classList.contains( 'is-load' ),
			};
		} );
		const cls = ( await page.evaluate( () => window.__tpCls ) ) - cls0;

		check( `${ tag } کیفیت هوا (${ label }): پیش از پاسخ اسکلتِ «—» بود و پس از آن بی جابه‌جایی (ارتفاع ${ Math.round( skel.h ) }→${ Math.round( after.h ) }، CLS پر شدن ${ cls.toFixed( 5 ) })`,
			skel.load && '—' === skel.idx && Math.abs( skel.h - after.h ) <= 1 && cls <= 0.0001, JSON.stringify( { skel, after, cls } ) );

		if ( fail ) {
			check( `${ tag } کیفیت هوا (خطا): همهٔ خانه‌ها «—» می‌مانند، بی رده و بی رنگ، و اسکلت برداشته می‌شود`,
				! after.load && '—' === after.idx && '—' === after.cat && '—' === after.pm && '—' === after.dust && ! after.attr, JSON.stringify( after ) );
		} else {
			check( `${ tag } کیفیت هوا (پاسخ): ۱۶۰ ← «ناسالم» رده ۴؛ ذره‌ها با µg/m³؛ رنگ برچسب همان توکن --tp-aqi-4`,
				'۱۶۰' === after.idx && 'ناسالم' === after.cat && '4' === after.attr && /۵۵\s*µg\/m³/.test( after.pm ) && /۱۲\s*µg\/m³/.test( after.dust ) && after.color === after.token, JSON.stringify( after ) );
		}

		await ctx.close();
	}
}

/* ── P5-D243 اسلایدر ──────────────────────────────────────────────── */
async function sliders( page, tag ) {

	const ids = await page.$$eval( '.tp-slider-nav button[data-dir="1"]', bs => bs.map( b => b.getAttribute( 'data-target' ) ) );
	let tested = 0;

	for ( const id of ids ) {

		const next = page.locator( `.tp-slider-nav button[data-dir="1"][data-target="${ id }"]` );
		const prev = page.locator( `.tp-slider-nav button[data-dir="-1"][data-target="${ id }"]` );

		if ( await next.isDisabled() ) { info( tag + ' ' + id + ': جا شد — فلش‌ها بی‌موضوع' ); continue; }

		/* وسط دید، تا نوار پایین یا هدر رویش نیفتد. `instant`: صفحه `scroll-behavior:smooth` دارد و
		   اسکرولِ نرم تا یک ثانیه طول می‌کشد — کلیک موشی پیش از رسیدن، روی هیچ فرود می‌آمد. */
		await page.evaluate( i => document.querySelector( `[data-target="${ i }"]` ).scrollIntoView( { block: 'center', behavior: 'instant' } ), id );
		await page.waitForTimeout( 400 );

		const meta = await page.evaluate( i => {
			const n = document.querySelector( `.tp-slider-nav button[data-dir="1"][data-target="${ i }"]` );
			const p = document.querySelector( `.tp-slider-nav button[data-dir="-1"][data-target="${ i }"]` );
			return {
				rtl: 'rtl' === getComputedStyle( document.getElementById( i ) ).direction,
				nextX: n.getBoundingClientRect().left, prevX: p.getBoundingClientRect().left,
				nextGlyph: n.textContent.trim(), prevGlyph: p.textContent.trim(),
				aria: [ p.getAttribute( 'aria-label' ), n.getAttribute( 'aria-label' ) ],
				domPrevFirst: !! ( p.compareDocumentPosition( n ) & Node.DOCUMENT_POSITION_FOLLOWING )
			};
		}, id );

		const snap = () => page.evaluate( i => {
			const t = document.getElementById( i ), tr = t.getBoundingClientRect(), cards = [ ...t.children ];
			const rs = cards.map( c => c.getBoundingClientRect() );
			return {
				x0: rs[0].left,
				left: rs.map( r => r.left ), right: rs.map( r => r.right ),
				vis: rs.map( ( r, n ) => r.right > tr.left + 2 && r.left < tr.right - 2 ? n : -1 ).filter( n => n >= 0 ),
				full: rs.map( ( r, n ) => r.left >= tr.left - 2 && r.right <= tr.right + 2 ? n : -1 ).filter( n => n >= 0 ),
				trLeft: tr.left, trRight: tr.right
			};
		}, id );

		const rtl = meta.rtl;
		const a = await snap();
		const prevAtStart = await prev.isDisabled();
		const nextAtStart = ! ( await next.isDisabled() );
		const nb = await next.boundingBox();
		const pb = await prev.boundingBox();

		await page.mouse.click( nb.x + nb.width / 2, nb.y + nb.height / 2 );
		await page.waitForTimeout( 900 );
		const b = await snap();
		const prevAfter = ! ( await prev.isDisabled() );

		/* کارتی که پیش‌تر کامل دیده نمی‌شد و پشتِ لبهٔ پایانِ خط بود (راست‌به‌چپ: چپ)، حالا کامل */
		const revealed = b.full.filter( n => a.full.indexOf( n ) < 0 && ( rtl ? a.left[ n ] < a.trLeft - 1 : a.right[ n ] > a.trRight + 1 ) );
		const moved = b.x0 - a.x0;

		await page.mouse.click( pb.x + pb.width / 2, pb.y + pb.height / 2 );
		await page.waitForTimeout( 900 );
		const c = await snap();

		const glyphs = rtl ? '‹' === meta.nextGlyph && '›' === meta.prevGlyph && meta.nextX < meta.prevX
			: '›' === meta.nextGlyph && '‹' === meta.prevGlyph && meta.nextX > meta.prevX;

		check( `${ tag } ${ id }: ${ rtl ? 'راست‌به‌چپ «بعدی» = «‹» چپ، فعال در آغاز؛ «قبلی» = «›» راست، غیرفعال' : 'چپ‌به‌راست «بعدی» = «›» راست، فعال در آغاز؛ «قبلی» = «‹» چپ، غیرفعال' }؛ DOM همان ترتیب؛ «قبلی»/«بعدی»  P5-D243`,
			glyphs && prevAtStart && nextAtStart && meta.domPrevFirst && 'قبلی' === meta.aria[0] && 'بعدی' === meta.aria[1],
			JSON.stringify( Object.assign( { prevAtStart, nextAtStart }, meta ) ) );
		check( `${ tag } ${ id }: «بعدی» کارت پنهانِ ${ rtl ? 'چپ' : 'راست' } را کامل پیش می‌آورد؛ «قبلی» فعال می‌شود و به آغاز برمی‌گرداند  P5-D243`,
			revealed.length > 0 && ( rtl ? moved > 20 : moved < -20 ) && prevAfter && Math.abs( c.x0 - a.x0 ) <= 2 && ( await prev.isDisabled() ),
			JSON.stringify( { moved: Math.round( moved ), revealed, prevAfter, back: Math.round( c.x0 - a.x0 ) } ) );
		tested ++;
	}

	return tested;
}

/* ── P5-D243 نمای تمام‌صفحه و کشیدن انگشت ─────────────────────────── */
async function solo( page, tag, touch ) {

	const tile = await page.$( '[data-tp-shot]' );

	if ( ! tile ) { info( tag + ': گالری ندارد — تمام‌صفحه بی‌موضوع' ); return; }

	await tile.scrollIntoViewIfNeeded();
	await tile.click();
	await page.waitForTimeout( 400 );

	const cnt = () => page.$eval( '[data-tp-solo-count]', e => e.textContent.trim().replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ).split( '/' ).map( s => parseInt( s, 10 ) ) );
	const pos = await page.evaluate( () => {
		const box = document.getElementById( 'tpGallerySolo' );
		const n = box.querySelector( '[data-tp-shot-next]' ), p = box.querySelector( '[data-tp-shot-prev]' );
		return {
			rtl: 'rtl' === getComputedStyle( box ).direction,
			next: n.getBoundingClientRect().left, prev: p.getBoundingClientRect().left,
			glyphN: n.textContent.trim(), glyphP: p.textContent.trim(),
			aria: [ p.getAttribute( 'aria-label' ), n.getAttribute( 'aria-label' ) ],
			domPrevFirst: !! ( p.compareDocumentPosition( n ) & Node.DOCUMENT_POSITION_FOLLOWING )
		};
	} );
	const fwd = pos.rtl ? 'ArrowLeft' : 'ArrowRight';
	const bwd = pos.rtl ? 'ArrowRight' : 'ArrowLeft';

	const c0 = await cnt();
	await page.click( '#tpGallerySolo [data-tp-shot-next]' );
	await page.waitForTimeout( 150 );
	const c1 = await cnt();
	await page.keyboard.press( fwd );
	await page.waitForTimeout( 150 );
	const c2 = await cnt();
	await page.keyboard.press( bwd );
	await page.waitForTimeout( 150 );
	const c3 = await cnt();

	const side = pos.rtl ? pos.next < pos.prev && '‹' === pos.glyphN && '›' === pos.glyphP
		: pos.next > pos.prev && '›' === pos.glyphN && '‹' === pos.glyphP;

	check( `${ tag }: تمام‌صفحه — «بعدی» ${ pos.rtl ? 'لبهٔ چپ «‹»، ArrowLeft بعدی و ArrowRight قبلی' : 'لبهٔ راست «›»، ArrowRight بعدی و ArrowLeft قبلی' }؛ DOM همان ترتیب  P5-D243`,
		side && pos.domPrevFirst && 'قبلی' === pos.aria[0] && 'بعدی' === pos.aria[1] && c1[0] === c0[0] + 1 && c2[0] === c1[0] + 1 && c3[0] === c1[0],
		JSON.stringify( { pos, c0, c1, c2, c3 } ) );

	if ( touch ) {
		const sw = await page.evaluate( () => {
			const st = document.querySelector( '[data-tp-solo-stage]' );
			const cnt = () => parseInt( document.querySelector( '[data-tp-solo-count]' ).textContent.replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ), 10 );
			const fire = ( type, x ) => {
				const t = new Touch( { identifier: 1, target: st, clientX: x, clientY: 300 } );
				st.dispatchEvent( new TouchEvent( type, { touches: 'touchend' === type ? [] : [ t ], changedTouches: [ t ], bubbles: true } ) );
			};
			const a = cnt(); fire( 'touchstart', 80 ); fire( 'touchend', 260 );    /* چپ به راست */
			const b = cnt(); fire( 'touchstart', 260 ); fire( 'touchend', 80 );    /* راست به چپ */
			return { a, b, c: cnt() };
		} ).catch( e => ( { err: e.message } ) );
		check( `${ tag }: کشیدن انگشت ${ pos.rtl ? 'از چپ به راست = بعدی، راست به چپ = قبلی' : 'از راست به چپ = بعدی، چپ به راست = قبلی' }  P5-D243`,
			pos.rtl ? sw.b === sw.a + 1 && sw.c === sw.a : sw.b === sw.a - 1 && sw.c === sw.a, JSON.stringify( sw ) );
	}

	await page.keyboard.press( 'Escape' );
	await page.waitForTimeout( 200 );
}

/* ── P5-D277 کشیدن با موس — اسلایدر و نمای تمام‌صفحه ───────────────────
   ۲۰۰ پیکسل به سوی «بعدی» (راست‌به‌چپ: چپ به راست · چپ‌به‌راست: راست به
   چپ). کلیکِ پایانِ کشیدن هیچ پیوندی را باز نمی‌کند؛ کلیکِ بی‌کشیدن باز
   می‌کند. شنوندۀ پایانیِ سند نتیجۀ کلیک را می‌گیرد و خودش ناوبری را می‌بندد،
   پس صفحه می‌ماند و هارنسِ file:// هم سنجیده می‌شود. */
async function drag( page, tag ) {

	const id = await page.evaluate( () => {
		const s = [ ...document.querySelectorAll( '.tp-slider' ) ].find( e => e.scrollWidth > e.clientWidth + 4 && e.querySelector( 'a.tp-card-slide' ) );
		if ( ! s ) { return ''; }
		s.scrollIntoView( { behavior: 'instant', block: 'center' } );
		window.__tpClicks = [];
		window.__tpProbe = e => {
			const a = e.target.closest ? e.target.closest( 'a[href]' ) : null;
			window.__tpClicks.push( { a: !! a, prevented: e.defaultPrevented } );
			e.preventDefault();
		};
		document.addEventListener( 'click', window.__tpProbe );
		return s.id;
	} );

	if ( ! id ) { info( tag + ': اسلایدرِ لبریزِ پیوندی برای کشیدن نبود' ); return; }

	const el  = page.locator( '#' + id );
	const box = await el.boundingBox();
	const st0 = await el.evaluate( e => ( { left: e.scrollLeft, rtl: 'rtl' === getComputedStyle( e ).direction, cursor: getComputedStyle( e ).cursor,
		card: getComputedStyle( e.querySelector( 'a.tp-card-slide' ) ).cursor, imgs: [ ...e.querySelectorAll( 'img' ) ].every( i => 'false' === i.getAttribute( 'draggable' ) ) } ) );
	const dx  = st0.rtl ? 200 : -200;
	const x0  = box.x + box.width / 2 - dx / 2;
	const y   = box.y + Math.min( 90, box.height / 3 );

	await page.mouse.move( x0, y );
	await page.mouse.down();
	let during = null;
	for ( let i = 1; i <= 10; i ++ ) {
		await page.mouse.move( x0 + dx * i / 10, y );
		if ( 5 === i ) {
			during = await el.evaluate( e => ( { cls: e.classList.contains( 'tp-is-dragging' ), cursor: getComputedStyle( e ).cursor, select: getComputedStyle( e ).userSelect || getComputedStyle( e ).webkitUserSelect, snap: getComputedStyle( e ).scrollSnapType } ) );
		}
	}
	await page.mouse.up();
	await page.waitForTimeout( 1200 );

	const st1 = await el.evaluate( e => {
		const c = e.getBoundingClientRect(), rtl = 'rtl' === getComputedStyle( e ).direction;
		return {
			left: e.scrollLeft,
			snapped: [ ...e.children ].some( k => { const r = k.getBoundingClientRect(); return Math.abs( rtl ? r.right - c.right : r.left - c.left ) <= 2; } ),
			select: getComputedStyle( e ).userSelect || getComputedStyle( e ).webkitUserSelect, cls: e.classList.contains( 'tp-is-dragging' )
		};
	} );
	const prevOn  = await page.evaluate( i => { const b = document.querySelector( `[data-target="${ i }"][data-dir="-1"]` ); return !! b && ! b.disabled; }, id );
	const clicks1 = await page.evaluate( () => window.__tpClicks.slice() );
	const moved   = Math.abs( st1.left - st0.left ) >= 120 && ( st0.rtl ? st1.left < st0.left : st1.left > st0.left );
	const noNav   = clicks1.every( c => ! c.a || c.prevented );

	check( `${ tag }: کشیدن ۲۰۰px با موس اسلایدر را به «بعدی» می‌برد، می‌چسبد، فلش «قبلی» فعال می‌شود و هیچ پیوندی باز نمی‌شود  P5-D277 · P5-D243`,
		moved && st1.snapped && prevOn && noNav, JSON.stringify( { st0, st1, prevOn, clicks1 } ) );
	check( `${ tag }: نشانگر grab (کارت هم)، هنگام کشیدن grabbing و user-select:none و بی چسبیدن؛ پس از رها کردن متن انتخاب‌پذیر؛ هر <img> draggable=false  P5-D277`,
		'grab' === st0.cursor && 'grab' === st0.card && st0.imgs && during && during.cls && 'grabbing' === during.cursor && 'none' === during.select
		&& /none/.test( during.snap ) && ! st1.cls && 'none' !== st1.select, JSON.stringify( { st0, during, st1 } ) );

	/* کلیکِ بی‌کشیدن روی کارتی که کامل پیداست — پیوند باز می‌شود (شنونده نمی‌گذارد صفحه برود) */
	await page.evaluate( () => { window.__tpClicks = []; } );
	const target = await el.evaluate( e => {
		const c = e.getBoundingClientRect();
		const a = [ ...e.querySelectorAll( 'a.tp-card-slide' ) ].find( k => { const r = k.getBoundingClientRect(); return r.left >= c.left - 1 && r.right <= c.right + 1; } );
		if ( ! a ) { return null; }
		const r = a.getBoundingClientRect();
		return { x: r.left + r.width / 2, y: r.top + Math.min( 60, r.height / 3 ) };
	} );
	if ( target ) { await page.mouse.click( target.x, target.y ); }
	const clicks2 = await page.evaluate( () => window.__tpClicks.slice() );
	check( `${ tag }: کلیکِ بی‌کشیدن پیوند کارت را باز می‌کند  P5-D277`,
		!! target && 1 === clicks2.length && clicks2[0].a && ! clicks2[0].prevented, JSON.stringify( clicks2 ) );
	await page.evaluate( () => document.removeEventListener( 'click', window.__tpProbe ) );

	/* نمای تمام‌صفحه: کشیدن موس روی صحنه — همان جهت، دکمه‌ها هنوز با کلیک کار می‌کنند */
	const tile = await page.$( '[data-tp-shot]' );
	if ( ! tile ) { return; }
	await tile.scrollIntoViewIfNeeded();
	await tile.click();
	await page.waitForTimeout( 400 );
	const cnt = () => page.$eval( '[data-tp-solo-count]', e => parseInt( e.textContent.replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ), 10 ) );
	const sb  = await page.locator( '[data-tp-solo-stage] img' ).boundingBox();
	const c0  = await cnt();
	const sx  = sb.x + sb.width / 2, sy = sb.y + sb.height / 2;
	await page.mouse.move( sx - dx / 2, sy );
	await page.mouse.down();
	for ( let i = 1; i <= 8; i ++ ) { await page.mouse.move( sx - dx / 2 + dx * i / 8, sy ); }
	await page.mouse.up();
	await page.waitForTimeout( 250 );
	const c1 = await cnt();
	await page.click( '#tpGallerySolo [data-tp-shot-next]' );
	await page.waitForTimeout( 150 );
	const c2 = await cnt();
	check( `${ tag }: تمام‌صفحه — کشیدن موس به سوی «بعدی» عکس بعد؛ دکمۀ «بعدی» همچنان با کلیک  P5-D277`,
		c1 === c0 + 1 && c2 === c1 + 1, JSON.stringify( { c0, c1, c2 } ) );
	await page.keyboard.press( 'Escape' );
	await page.waitForTimeout( 200 );
}

/* ── P5-D245 توکن کاشی در سه حالت ────────────────────────────────── */
async function tileTokens( page, tag ) {

	await page.waitForFunction( () => document.querySelector( '[data-tp-weather] .tp-wx__day:not(.tp-is-load)' ), null, { timeout: 60000 } ).catch( () => {} );

	const keep = await page.evaluate( () => document.documentElement.getAttribute( 'data-tp-mode' ) );
	const res  = {};

	for ( const mode of [ 'light', 'evening', 'dark' ] ) {
		await page.evaluate( m => document.documentElement.setAttribute( 'data-tp-mode', m ), mode );
		/* گذار .16s کاشی‌ها — پیش از پایانش رنگِ میانه خوانده می‌شد */
		await page.waitForTimeout( 700 );
		res[ mode ] = await page.evaluate( () => {
			const sel = {
				'روز هوا': '[data-tp-weather] .tp-wx__day:not(.tp-is-load)',
				'کاشی': '.tp-tiles .tp-tile',
				'هم‌مرز': '.tp-neighbors a',
				'مسیر': '#tp-routes .tp-tile',
				'اطلاعات سفر': '.tp-practical-grid a',
				'فرهنگ': '.tp-culture-card__list a',
				'جست‌وجوی فهرست': '.tp-list__q',
				'عضو فهرست': '.tp-list__card',
				'بستن فهرست': '.tp-gallery-modal--list .tp-gallery-modal__head > .tp-gallery-modal__close'
			};
			const rgb = s => ( s.match( /[\d.]+/g ) || [] ).slice( 0, 3 ).map( Number );
			const lum = c => { const l = c.map( v => { v /= 255; return v <= 0.04045 ? v / 12.92 : Math.pow( ( v + 0.055 ) / 1.055, 2.4 ); } ); return 0.2126 * l[0] + 0.7152 * l[1] + 0.0722 * l[2]; };
			const ratio = ( a, b ) => { const x = lum( a ), y = lum( b ); return ( Math.max( x, y ) + 0.05 ) / ( Math.min( x, y ) + 0.05 ); };
			const bg = {}, edge = {}, low = [];
			for ( const k in sel ) {
				const el = document.querySelector( sel[ k ] );
				if ( ! el ) { continue; }
				const cs = getComputedStyle( el );
				bg[ k ] = cs.backgroundColor;
				/* جعبهٔ جست‌وجو روی پردهٔ تیره قابِ --tp-tile-muted دارد (مرز فیلد ≥ ۳) */
				if ( 'جست‌وجوی فهرست' !== k ) { edge[ k ] = cs.borderTopColor; }
			}
			/* متنِ روی کاشی — هر برگِ متنی، روی پس‌زمینهٔ کاشیِ خودش */
			[ '.tp-tiles .tp-tile', '.tp-neighbors a', '.tp-practical-grid a', '.tp-culture-card__list a', '.tp-list__card' ].forEach( s => {
				document.querySelectorAll( s ).forEach( t => {
					const back = rgb( getComputedStyle( t ).backgroundColor );
					[ t ].concat( [ ...t.querySelectorAll( 'b, span' ) ] ).forEach( e => {
						if ( ! e.textContent.trim() || e.querySelector( 'b, span' ) && e !== t ) { return; }
						const r = ratio( rgb( getComputedStyle( e ).color ), back );
						if ( r < 4.5 ) { low.push( s + ' «' + e.textContent.trim().slice( 0, 12 ) + '» ' + r.toFixed( 2 ) ); }
					} );
				} );
			} );
			return { bg, edge, low: low.slice( 0, 4 ) };
		} );
	}

	await page.evaluate( m => m ? document.documentElement.setAttribute( 'data-tp-mode', m ) : document.documentElement.removeAttribute( 'data-tp-mode' ), keep );

	for ( const mode of [ 'light', 'evening', 'dark' ] ) {
		const bg = res[ mode ].bg, edge = res[ mode ].edge, ref = bg['روز هوا'], refEdge = edge['روز هوا'];
		const odd = Object.keys( bg ).filter( k => bg[ k ] !== ref ).concat( Object.keys( edge ).filter( k => edge[ k ] !== refEdge ).map( k => k + ' (قاب)' ) );
		check( `${ tag } ${ mode }: پس‌زمینهٔ هر کاشی، جست‌وجو و دکمهٔ پنجرهٔ فهرست همان کاشی روزِ هواست؛ قاب هم، جز جعبهٔ جست‌وجو (${ Object.keys( bg ).length } گونه)  P5-D245`,
			!! ref && Object.keys( bg ).length >= 5 && ! odd.length, JSON.stringify( { bg, edge } ) );
		check( `${ tag } ${ mode }: متن روی کاشی ≥ ۴٫۵  P5-D245`, ! res[ mode ].low.length, res[ mode ].low.join( ' · ' ) );
	}
}

/* ── P5-D244 دوستونهٔ جایگزین ────────────────────────────────────── */
async function tileFit( page, tag ) {

	const n = await page.$$eval( '[data-tp-tiles]', gs => gs.length );
	if ( ! n ) { info( tag + ': کاشی ندارد' ); return; }

	const cols = () => page.evaluate( () => {
		const g = document.querySelector( '[data-tp-tiles]' ), t = g.querySelector( '.tp-tile:not([hidden])' ), cs = getComputedStyle( t );
		return { two: g.classList.contains( 'tp-tiles--2' ), cols: getComputedStyle( g ).gridTemplateColumns.split( ' ' ).length, wb: cs.wordBreak, ow: cs.overflowWrap, hy: cs.hyphens };
	} );
	/* پهنای شبکه عوض می‌شود تا ResizeObserver واقعی صدا بخورد — همان که با چرخاندن گوشی */
	const nudge = d => page.evaluate( dd => {
		const g = document.querySelector( '[data-tp-tiles]' );
		g.style.inlineSize = dd ? ( g.getBoundingClientRect().width - dd ) + 'px' : '';
	}, d );

	const before = await cols();
	const keep = await page.evaluate( () => {
		const b = document.querySelector( '[data-tp-tiles] .tp-tile:not([hidden]) b' ), k = b.textContent;
		b.textContent = 'ابرفرهنگستان\u200cشناسی\u200cترینان\u200cگونه\u200cوارهٔ\u200cناشکستنی';
		return k;
	} );
	await nudge( 3 );
	await page.waitForTimeout( 350 );
	const over = await cols();
	await page.evaluate( k => { document.querySelector( '[data-tp-tiles] .tp-tile:not([hidden]) b' ).textContent = k; }, keep );
	await nudge( 0 );
	await page.waitForTimeout( 350 );
	const after = await cols();

	check( `${ tag }: سه ستون؛ نامِ ناشکستنی سرریز می‌کند ← همان کارت دوستونه؛ جا شد ← باز سه ستون؛ word-break:normal و hyphens:none  P5-D244`,
		3 === before.cols && ! before.two && over.two && 2 === over.cols && ! after.two && 3 === after.cols
		&& 'normal' === before.wb && 'none' === before.hy && 'normal' === before.ow,
		JSON.stringify( { before, over, after } ) );
}

/* ── P5-D244 «{N} مورد دیگر» ─────────────────────────────────────── */
async function more( page, tag ) {

	const has = await page.$( '.tp-rail-card [data-tp-more]' );
	if ( ! has ) { info( tag + ': هیچ کارتی بیش از شش کاشی ندارد — «مورد دیگر» بی‌موضوع' ); return; }

	const id = await page.evaluate( () => document.querySelector( '.tp-rail-card [data-tp-more]' ).closest( '.tp-rail-card' ).id );
	const st = () => page.evaluate( i => {
		const c = document.getElementById( i ), b = c.querySelector( '[data-tp-more]' );
		const tiles = [ ...c.querySelectorAll( '[data-tp-tiles] > *' ) ];
		return {
			total: tiles.length, shown: tiles.filter( t => ! t.hidden && 'none' !== getComputedStyle( t ).display ).length,
			n: b ? parseInt( b.querySelector( '[data-tp-more-n]' ).textContent.replace( /[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf( d ) ), 10 ) : 0,
			btn: !! b
		};
	}, id );

	const steps = [ await st() ];
	let guard = 0;
	while ( steps[ steps.length - 1 ].btn && guard ++ < 20 ) {
		await page.locator( `#${ id } [data-tp-more]` ).scrollIntoViewIfNeeded();
		await page.locator( `#${ id } [data-tp-more]` ).click();
		await page.waitForTimeout( 200 );
		steps.push( await st() );
	}

	const ok = 6 === steps[0].shown && steps.every( ( s, i ) => ! s.btn || s.n === Math.min( 6, s.total - s.shown ) )
		&& steps.slice( 1 ).every( ( s, i ) => s.shown - steps[ i ].shown === Math.min( 6, steps[ i ].total - steps[ i ].shown ) )
		&& ! steps[ steps.length - 1 ].btn && steps[ steps.length - 1 ].shown === steps[0].total;
	check( `${ tag } #${ id }: شش پیدا؛ هر ضربه N = کمینهٔ شش و باقی‌مانده؛ پس از آخرین دسته دکمه نیست  P5-D244`, ok, JSON.stringify( steps ) );

	/*
	 * ۲ه٫۴ · P5-D313 — همان کارت: پیوند «همه …» کنار عنوان، پنجرۀ فهرست با همۀ اعضا و جست‌وجو.
	 * باز با کلیک، شمار برابر همهٔ کاشی‌ها، جست‌وجو فیلتر می‌کند، Esc می‌بندد و فوکوس به پیوند برمی‌گردد.
	 */
	const link = page.locator( `#${ id } .tp-rail-card__head [data-tp-list-open]` );
	if ( ! await link.count() ) { check( `${ tag } #${ id }: پیوند «همه …» کنار عنوان هست`, false, 'پیوند نیست' ); return; }
	const total = steps[0].total;
	const shape = await page.evaluate( i => {
		const h = document.getElementById( i ).querySelector( '.tp-rail-card__head' );
		const a = h.querySelector( '[data-tp-list-open]' ), t = h.querySelector( 'h3' );
		const ra = a.getBoundingClientRect(), rt = t.getBoundingClientRect(), rh = h.getBoundingClientRect();
		/* سربرگ مرجع flex با space-between است: نمایشش با سربرگ کارت‌های بی پیوند یکی بماند، h3 پیش از پیوند در DOM، و پیوند در انتهای سطر */
		const other = [ ...document.querySelectorAll( '.tp-rail-card__head' ) ].find( x => x !== h && ! x.querySelector( '[data-tp-list-open]' ) );
		const rtl   = 'rtl' === getComputedStyle( h ).direction;
		return {
			sameRow: ra.top < rt.bottom && ra.bottom > rt.top, inside: ra.left >= rh.left - 1 && ra.right <= rh.right + 1,
			hStyle: getComputedStyle( h ).display, refStyle: other ? getComputedStyle( other ).display : 'flex',
			afterH3: !! ( t.compareDocumentPosition( a ) & Node.DOCUMENT_POSITION_FOLLOWING ),
			atEnd: rtl ? ra.left < rt.left : ra.left > rt.left, ctl: a.getAttribute( 'aria-controls' )
		};
	}, id );
	await link.scrollIntoViewIfNeeded();
	await link.click();
	const modal = page.locator( '#' + shape.ctl );
	await modal.waitFor( { state: 'visible', timeout: 15000 } );
	const all = await modal.evaluate( m => ( { n: m.querySelectorAll( '.tp-list__item' ).length, tot: parseInt( m.getAttribute( 'data-tp-total' ), 10 ) } ) );
	const q = modal.locator( '[data-tp-list-q]' );
	const firstName = await modal.locator( '.tp-list__item b' ).first().textContent();
	await q.fill( firstName.trim().slice( 0, 3 ) );
	await page.waitForTimeout( 250 );
	const found = await modal.evaluate( m => [ ...m.querySelectorAll( '.tp-list__item' ) ].filter( li => ! li.hidden && 'none' !== getComputedStyle( li ).display ).length );
	await page.keyboard.press( 'Escape' );
	await page.waitForTimeout( 300 );
	const closed = await modal.isHidden();
	check( `${ tag } #${ id }: «همه …» پس از h3 در DOM، هم‌ردیف و در انتهای سطرِ سربرگ (display سربرگ مرجع بی‌تغییر)؛ پنجره ${ all.n } از ${ total } عضو، جست‌وجو فیلتر می‌کند، Esc می‌بندد  P5-D313`,
		shape.sameRow && shape.inside && shape.hStyle === shape.refStyle && shape.afterH3 && shape.atEnd && all.n === total && all.tot === total && found >= 1 && found <= all.n && closed,
		JSON.stringify( { shape, all, found, closed } ) );
}

/*
 * ── P5-D266 ارتفاع دیدنی تصویر ──────────────────────────────────────
 * «از بالای کارت تا بالای بدنه» — نه ارتفاع عنصر هنر. P5-D246 ارتفاع عنصر
 * را سنجید (همه ۱۶۲) و ندید که بدنه از پایین رویش می‌نشیند: ۸۶ · ۱۳۶ ·
 * ۱۴۹. پس ادعا یک بار روی صفحه و یک بار روی جهشِ همان چیدمانِ مرجع
 * (هنر absolute، بدنۀ آزاد) سنجیده می‌شود؛ جهش باید بیفتد.
 */
function visibleArt() {
	const rows = [];
	document.querySelectorAll( '.tp-slider' ).forEach( s => s.querySelectorAll( '.tp-card-slide' ).forEach( c => {
		const b = c.querySelector( '.tp-card-slide__body' );
		rows.push( Math.round( ( b.getBoundingClientRect().top - c.getBoundingClientRect().top ) * 10 ) / 10 );
	} ) );
	return { n: rows.length, min: rows.length ? Math.min( ...rows ) : 0, max: rows.length ? Math.max( ...rows ) : 0, all: [ ...new Set( rows ) ] };
}
async function visible( page, tag, mobile ) {
	const want = mobile ? 180 : 200;
	const real = await page.evaluate( visibleArt );
	/* جهش: چیدمان مرجع (کارت ۲۸۰، هنر absolute، بدنۀ آزاد) و یک کارت با بدنۀ بلندتر —
	   همان شرطی که در عکس کاربر عکس‌ها را نابرابر کرد */
	const st   = await page.addStyleTag( { content:
		'html body .tp-page .tp-card-slide{justify-content:flex-end!important;height:280px!important}'
		+ 'html body .tp-page .tp-card-slide__art{position:absolute!important;inset:0 0 auto 0!important;block-size:162px!important}'
		+ 'html body .tp-page .tp-card-slide__body{block-size:auto!important}' } );
	await page.evaluate( () => {
		const b = document.querySelector( '.tp-slider .tp-card-slide__body' );
		if ( b ) { const p = document.createElement( 'p' ); p.className = 'tp-mut-266'; p.textContent = 'توضیح بلندتر یک کارت، دو خط، مثل توضیح‌های پیش از P5-D266'; p.style.margin = '6px 0 0'; b.appendChild( p ); }
	} );
	const mut  = await page.evaluate( visibleArt );
	await page.evaluate( () => document.querySelectorAll( '.tp-mut-266' ).forEach( e => e.remove() ) );
	await st.evaluate( e => e.remove() );
	const ok  = x => x.n > 1 && x.max - x.min <= 1;
	check( `${ tag }: ارتفاع دیدنی تصویر (بالای کارت تا بالای بدنه) در همۀ کارت‌های همۀ اسلایدرها یکی (±۱) و برابر هنر ${ want } + قاب؛ جهشِ چیدمان مرجع می‌افتد  P5-D266`,
		ok( real ) && Math.abs( real.min - ( want + 1 ) ) <= 1 && ! ok( mut ),
		JSON.stringify( { real, mut } ) );
}

/* ── P5-D246 یکسانی کارت‌ها و ۱٫۵ تصویر خبر ─────────────────────── */
async function cards( page, tag, mobile ) {

	await visible( page, tag, mobile );

	const r = await page.evaluate( () => {
		const spread = els => { const h = els.map( e => e.getBoundingClientRect().height ); return h.length ? Math.max( ...h ) - Math.min( ...h ) : 0; };
		const sliders = [ ...document.querySelectorAll( '.tp-slider' ) ].map( s => ( {
			id: s.id, n: s.children.length, spread: spread( [ ...s.querySelectorAll( '.tp-card-slide' ) ] ),
			art: [ ...s.querySelectorAll( '.tp-card-slide__art' ) ].map( a => Math.round( a.getBoundingClientRect().height ) ),
			body: spread( [ ...s.querySelectorAll( '.tp-card-slide__body' ) ] ),
			desc: s.querySelectorAll( '.tp-card-slide__desc' ).length
		} ) );
		const culture = spread( [ ...document.querySelectorAll( '.tp-culture-grid > *' ) ] );
		/* فقط تصویرهای خودمان — هدر و پاورقیِ وودمارت از این قالب نیستند */
		const imgs = [ ...document.querySelectorAll( '.tp-page img' ) ].filter( i => ! i.getAttribute( 'width' ) || ! i.getAttribute( 'height' ) ).map( i => i.className || i.getAttribute( 'src' ) );
		const thumbs = [ ...document.querySelectorAll( '.tp-news .tp-thumb' ) ].map( t => { const b = t.getBoundingClientRect(); return [ Math.round( b.width * 10 ) / 10, Math.round( b.height * 10 ) / 10, !! t.querySelector( 'img' ) ]; } );
		const timgs = [ ...document.querySelectorAll( '.tp-news .tp-thumb img' ) ].filter( i => ! i.getAttribute( 'srcset' ) || 'lazy' !== i.getAttribute( 'loading' ) ).length;
		return { sliders, culture, imgs, thumbs, timgs };
	} );

	const art = mobile ? 180 : 200;
	check( `${ tag }: هر اسلایدر — کارت‌ها و بدنه‌ها ±۱ هم‌قد، هنر ${ art }، بی توضیح  P5-D246 · P5-D266`,
		r.sliders.length > 0 && r.sliders.every( s => s.spread <= 1 && s.body <= 1 && ! s.desc && s.art.every( h => Math.abs( h - art ) <= 1 ) ), JSON.stringify( r.sliders ) );
	check( `${ tag }: کارت‌های فرهنگ ±۱ هم‌قد  P5-D246`, r.culture <= 1, String( r.culture ) );
	check( `${ tag }: هر <img> قالب width و height صریح دارد  P5-D246`, ! r.imgs.length, r.imgs.slice( 0, 4 ).join( ' · ' ) );

	if ( r.thumbs.length ) {
		const want = mobile ? [ 88, 66 ] : [ 112, 84 ];
		check( `${ tag }: جعبهٔ تصویر خبر ${ want.join( '×' ) } (۴:۳)، با تصویر و بی‌تصویر؛ تصویر با srcset و lazy  ۱٫۵`,
			r.thumbs.every( t => Math.abs( t[0] - want[0] ) <= 0.5 && Math.abs( t[1] - want[1] ) <= 0.5 ) && ! r.timgs, JSON.stringify( r.thumbs ) );
	} else {
		info( tag + ': بخش خبر ندارد' );
	}

	/* پنجرهٔ گالری و پنجرهٔ فهرست — باز، سنجیده، بسته */
	if ( await page.$( '[data-tp-gallery-open]' ) ) {
		await page.locator( '[data-tp-gallery-open]' ).first().scrollIntoViewIfNeeded();
		await page.locator( '[data-tp-gallery-open]' ).first().click();
		await page.waitForTimeout( 500 );
		const g = await page.evaluate( () => { const h = [ ...document.querySelectorAll( '#tpGalleryModal .tp-gallery-modal__item' ) ].map( e => e.getBoundingClientRect().height ); return { n: h.length, spread: h.length ? Math.max( ...h ) - Math.min( ...h ) : 0, wide: document.querySelectorAll( '#tpGalleryModal .tp-gallery-modal__item--wide' ).length }; } );
		check( `${ tag }: پنجرهٔ گالری — همهٔ کاشی‌ها، پهن و معمولی، ±۱ هم‌قد  P5-D246`, g.n > 0 && g.spread <= 1, JSON.stringify( g ) );
		await page.keyboard.press( 'Escape' );
		await page.waitForTimeout( 250 );
	}
	if ( await page.$( '[data-tp-list-open]' ) ) {
		await page.locator( '[data-tp-list-open]' ).first().scrollIntoViewIfNeeded();
		await page.locator( '[data-tp-list-open]' ).first().click();
		await page.waitForTimeout( 500 );
		const l = await page.evaluate( () => { const h = [ ...document.querySelectorAll( '.tp-gallery-modal--list.tp-show .tp-list__card, .tp-gallery-modal--list[aria-hidden="false"] .tp-list__card' ) ].filter( e => e.offsetParent ).map( e => e.getBoundingClientRect().height ); return { n: h.length, spread: h.length ? Math.max( ...h ) - Math.min( ...h ) : 0 }; } );
		check( `${ tag }: پنجرهٔ فهرست — اعضا ±۱ هم‌قد  P5-D246`, l.n > 0 && l.spread <= 1, JSON.stringify( l ) );
		await page.keyboard.press( 'Escape' );
		await page.waitForTimeout( 250 );
	}
}

/* ── §۱۲ نوار پایین ────────────────────────────────────────────────── */
async function mnav( page, tag ) {

	const state = () => page.evaluate( () => {
		const m = document.querySelector( '.tp-mnav' ), r = m.getBoundingClientRect(), cs = getComputedStyle( m );
		return { gap: Math.round( innerHeight - r.bottom ), vis: cs.visibility, on: m.classList.contains( 'tp-mnav--on' ), y: Math.round( scrollY ), dur: cs.transitionDuration, prop: cs.transitionProperty, op: cs.opacity };
	} );

	await page.evaluate( () => window.scrollTo( 0, 0 ) );
	await page.waitForTimeout( 300 );
	const top = await state();

	await page.mouse.wheel( 0, 10 );
	await page.waitForTimeout( 300 );
	const small = await state();

	await page.mouse.wheel( 0, 60 );
	await page.waitForTimeout( 60 );
	const moving = await state();
	await page.waitForTimeout( 400 );
	const shown = await state();

	await page.evaluate( () => window.scrollTo( 0, 0 ) );
	await page.waitForTimeout( 400 );
	const back = await state();

	check( `${ tag }: نوار پایین بالای صفحه پنهان، زیر 24px هم پنهان  §۱۲`,
		'hidden' === top.vis && top.gap < -40 && ! small.on && 'hidden' === small.vis, JSON.stringify( { top, small } ) );
	check( `${ tag }: نخستین اسکرولِ بیش از 24px ← پیدا، با انیمیشن کوتاه transform و opacity (۲ه٫۱)، روی لبهٔ پایین  §۱۲`,
		shown.on && 'visible' === shown.vis && Math.abs( shown.gap ) <= 1 && /0\.24s|240ms/.test( shown.dur ) && /opacity/.test( shown.prop ) && '1' === shown.op && '0' === top.op, JSON.stringify( { moving, shown } ) );
	check( `${ tag }: برگشتن به بالا ← همچنان پیدا  §۱۲`,
		0 === back.y && back.on && 'visible' === back.vis && Math.abs( back.gap ) <= 1, JSON.stringify( back ) );
}

/* ── ۲ه٫۱ · P5-D310 — نوار بخش‌ها نرم ─────────────────────────────────
   کلیک ← بخش زیر سربرگ و نوار با فاصلۀ درست؛ قرص فعال در چند فریم می‌لغزد و سپس
   همان کلاس فعال مرجع؛ نوار افقی (جایی که جا نمی‌شود) در چند فریم پیوسته؛ کاهش حرکت
   ← فوری و بی قرص؛ هیچ جابه‌جایی چیدمان (CLS) بی ورودی کاربر. */
const CLS_INIT = () => {
	window.__tpCls = 0;
	try {
		new PerformanceObserver( l => { for ( const e of l.getEntries() ) { if ( ! e.hadRecentInput ) { window.__tpCls += e.value; } } } )
			.observe( { type: 'layout-shift', buffered: true } );
	} catch ( e ) { window.__tpCls = -1; }
};

async function snav( browser, url, tag, width, reduced ) {

	const ctx  = await browser.newContext( { viewport: { width, height: 900 }, reducedMotion: reduced ? 'reduce' : 'no-preference' } );
	await ctx.addInitScript( CLS_INIT );
	await ctx.route( /topersia\.com|picsum\.photos/, r => r.abort() );
	const page = await ctx.newPage();
	await page.goto( url, { waitUntil: 'load', timeout: 240000 } );
	await page.waitForTimeout( 600 );

	/* فقط بخش‌های ستون اصلی — ستون کناری چسبان است و بخشش زیر نوار نمی‌نشیند */
	const links = await page.$$eval( '.tp-snav a[href^="#"]', as => as.map( a => a.getAttribute( 'href' ) ).filter( h => { const el = document.querySelector( h ); return el && el.closest( '.tp-main-col' ); } ) );
	if ( links.length < 2 ) { info( `${ tag }: نوار بخش‌ها کمتر از دو پیوند ستون اصلی دارد — سنجیده نشد` ); await ctx.close(); return; }

	/* پیوند سوم از آخر: دور از بالا، تا لغزش هم عمودی هم افقی باشد */
	/* نوار جا می‌شود: دومین بخش ستون اصلی (دور از بالا و ته صفحه). نوار لبریز: پیوندی که از میانۀ نوار دورتر است، تا لغزش افقی لازم باشد */
	const href = await page.evaluate( ( ls ) => {
		const strip = document.querySelector( '.tp-snav__in' );
		if ( ! strip || strip.scrollWidth <= strip.clientWidth + 1 ) { return ls[ 1 ]; }
		const box = strip.getBoundingClientRect(), mid = box.left + box.width / 2, room = strip.scrollWidth - strip.clientWidth;
		const rtl = 'rtl' === getComputedStyle( strip ).direction, lo = rtl ? -room : 0, hi = rtl ? 0 : room;
		let best = ls[ 1 ], far = -1;
		for ( const h of ls ) {
			const r = document.querySelector( '.tp-snav a[href="' + h + '"]' ).getBoundingClientRect();
			const to = Math.min( hi, Math.max( lo, strip.scrollLeft + ( r.left + r.width / 2 - mid ) ) );   /* جایی که لغزش واقعاً می‌رسد */
			const d = Math.abs( to - strip.scrollLeft );
			if ( d > far ) { far = d; best = h; }
		}
		return best;
	}, links );

	/* نمونه‌برداری هر فریم از scrollY، scrollLeft نوار و قرص لغزان، از لحظۀ کلیک */
	await page.evaluate( h => {
		window.__tpSamples = [];
		window.__tpCls = 0;   /* فقط بازۀ همین تعامل */
		const strip = document.querySelector( '.tp-snav__in' );
		const t0 = performance.now();
		const tick = () => {
			const g = document.querySelector( '.tp-snav__ghost' );
			window.__tpSamples.push( { t: Math.round( performance.now() - t0 ), y: Math.round( scrollY ), x: strip ? Math.round( strip.scrollLeft ) : 0, g: g ? g.style.transform : null } );
			if ( performance.now() - t0 < 1600 ) { requestAnimationFrame( tick ); }
		};
		requestAnimationFrame( tick );
		document.querySelector( '.tp-snav a[href="' + h + '"]' ).click();
	}, href );
	/* اسکرولِ نرمِ صفحهٔ ۱۲هزار پیکسلی روی ماشین کند بیش از ۲ ثانیه می‌کشد: تا آرام‌شدنِ اسکرول (۶۰۰ms بی‌حرکت، سقف ۸ ثانیه) صبر، نه زمان ثابت */
	await page.evaluate( () => new Promise( done => {
		let last = -1, since = Date.now(); const t0 = Date.now();
		( function poll() {
			const y = Math.round( window.pageYOffset );
			if ( y !== last ) { last = y; since = Date.now(); }
			if ( Date.now() - since > 600 && Date.now() - t0 > 900 || Date.now() - t0 > 8000 ) { done(); return; }
			setTimeout( poll, 100 );
		} )();
	} ) );

	const r = await page.evaluate( h => {
		const sec   = document.querySelector( h );
		const nav   = document.querySelector( '.tp-snav' );
		const strip = document.querySelector( '.tp-snav__in' );
		const link  = document.querySelector( '.tp-snav a[href="' + h + '"]' );
		const s     = window.__tpSamples;
		const ys    = [ ...new Set( s.map( p => p.y ) ) ];
		const xs    = [ ...new Set( s.map( p => p.x ) ) ];
		const gs    = s.filter( p => p.g ).map( p => p.g );
		return {
			gap: Math.round( sec.getBoundingClientRect().top - nav.getBoundingClientRect().bottom ),
			left: Math.round( document.documentElement.scrollHeight - innerHeight - scrollY ),
			active: link.classList.contains( 'tp-is-active' ),
			ghostLeft: !! document.querySelector( '.tp-snav__ghost' ),
			ys: ys.length, xs: xs.length, ghostFrames: gs.length, ghostMoves: new Set( gs ).size,
			overflow: !! strip && strip.scrollWidth > strip.clientWidth + 1,
			firstMove: ( s.find( p => p.y !== s[0].y ) || {} ).t,
			cls: Math.round( window.__tpCls * 1000 ) / 1000,
		};
	}, href );

	const sz = `${ tag } ${ width }${ reduced ? ' کاهش حرکت' : '' }`;
	check( `${ sz }: کلیک «${ href }» ← بخش درست زیر نوار چسبان (فاصله ${ r.gap }px)، پیوند فعال، قرص پس از لغزش رفت  P5-D310`,
		( Math.abs( r.gap ) <= 6 || ( r.left <= 2 && r.gap >= -6 ) ) && r.active && ! r.ghostLeft, JSON.stringify( r ) );

	if ( reduced ) {
		check( `${ sz }: بی حرکت — اسکرول در یک گام، بی قرص لغزان، نوار افقی یک‌باره`,
			r.ys <= 2 && 0 === r.ghostFrames && r.xs <= 2, JSON.stringify( r ) );
	} else {
		check( `${ sz }: اسکرول نرم — بیش از سه موقعیت میانی پیش از رسیدن`, r.ys > 3, JSON.stringify( r ) );
		if ( r.overflow ) {
			check( `${ sz }: نوار افقی بخش فعال را در چند فریم پیوسته به دید می‌آورد (scrollLeft ${ r.xs } مقدار)، نه یک‌باره`, r.xs > 3, JSON.stringify( r ) );
		} else {
			check( `${ sz }: نشانگر فعال می‌لغزد — قرص در ${ r.ghostFrames } فریم با transform ${ r.ghostMoves } مقدار، نه پرش کلاس`,
				r.ghostFrames >= 3 && r.ghostMoves >= 2, JSON.stringify( r ) );
		}
	}
	check( `${ sz }: CLS صفر (بی ورودی کاربر)`, 0 === r.cls, JSON.stringify( r ) );
	await ctx.close();
}

/* ── §۵ اشتراک و مهمان ─────────────────────────────────────────────── */
async function share( page, tag, touch ) {

	const panelShown = () => page.$eval( '[data-tp-share-panel]', p => 'none' !== getComputedStyle( p ).display );

	await page.locator( '[data-tp-share]' ).scrollIntoViewIfNeeded();

	if ( ! touch ) {
		await page.hover( '[data-tp-share]' );
		await page.waitForTimeout( 150 );
		const byHover = await panelShown();
		await page.mouse.move( 5, 5 );
		await page.waitForTimeout( 150 );
		const gone = ! ( await panelShown() );
		await page.focus( '[data-tp-share]' );
		const byFocus = await panelShown();
		await page.evaluate( () => document.activeElement.blur() );
		check( `${ tag }: اشتراک — hover و focus پنل را باز می‌کنند؛ بیرون، بسته  §۵ · P5-D239`, byHover && gone && byFocus, JSON.stringify( { byHover, gone, byFocus } ) );
	} else {
		await page.tap( '[data-tp-share]' );
		await page.waitForTimeout( 150 );
		const byTap = await panelShown();
		await page.tap( 'h1' ).catch( () => {} );
		await page.waitForTimeout( 150 );
		check( `${ tag }: اشتراک — ضربه پنل را باز می‌کند و ضربهٔ بیرون می‌بندد  §۵ · P5-D239`, byTap && ! ( await panelShown() ) );
	}

	const nets = await page.$$eval( '[data-tp-share-to]', as => as.map( a => [ a.getAttribute( 'data-tp-share-to' ), a.rel, getComputedStyle( a.querySelector( '.wd-icon' ), '::before' ).fontFamily ] ) );
	check( `${ tag }: شش شبکه، noopener، آیکون از woodmart-font  §۵`,
		6 === nets.length && nets.every( n => /noopener/.test( n[1] ) && /woodmart-font/.test( n[2] ) ), JSON.stringify( nets.map( n => n[0] + ':' + n[2] ) ) );

	for ( const [ sel, why ] of [ [ '[data-tp-save]', 'این مکان' ], [ '[data-tp-follow]', 'خبرهای این مکان' ] ] ) {
		if ( ! await page.$( sel ) ) { check( `${ tag }: ${ sel } هست`, false ); continue; }
		await page.locator( sel ).scrollIntoViewIfNeeded();
		await page.click( sel );
		await page.waitForTimeout( 250 );
		const m = await page.evaluate( () => { const d = document.getElementById( 'tp-uc-login' ); return d ? { open: ! d.hidden, why: ( d.querySelector( '[data-tp-uc-reason]' ) || {} ).textContent || '', redirect: ( d.querySelector( '[data-tp-auth]' ) || { getAttribute: () => '' } ).getAttribute( 'data-tp-redirect' ) } : null; } );
		check( `${ tag }: مهمان ${ sel } ← پنجرهٔ ورود/ثبت‌نام user-core با دلیل و بازگشت به همین صفحه  §۵`,
			m && m.open && m.why.indexOf( why ) >= 0 && m.redirect === page.url(), JSON.stringify( m ) );
		await page.keyboard.press( 'Escape' );
		await page.waitForTimeout( 150 );
	}
}

/* ── §۱ نقشه ──────────────────────────────────────────────────────── */
async function map( page, tag ) {
	if ( ! await page.$( '[data-tp-map-cfg]' ) ) { info( tag + ': پیکربندی نقشهٔ زنده نیست' ); return; }
	await page.locator( '[data-tp-map]' ).first().scrollIntoViewIfNeeded();
	const live = await page.waitForSelector( '.tp-map-inner--live', { timeout: 60000 } ).then( () => true ).catch( () => false );
	const m = await page.evaluate( () => { const b = document.querySelector( '[data-tp-map-live]' ); return b && b.tpMap ? { dnd: b.tpMap.dragPan.isEnabled(), fa: JSON.stringify( b.tpMap.getStyle().layers ).indexOf( 'name:fa' ) >= 0 } : null; } );
	check( `${ tag }: نقشهٔ کارت پس از دیده‌شدن زنده — غیرتعاملی، برچسب name:fa  §۱`, live && m && ! m.dnd && m.fa, JSON.stringify( m ) );
}

/* ── §۳ انیمیشن هوا ─────────────────────────────────────────────────── */
async function weatherFx( browser, url, tag, reduced ) {
	const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 }, reducedMotion: reduced ? 'reduce' : 'no-preference' } );
	await ctx.route( /topersia\.com/, r => r.abort() );
	await ctx.route( /\/wp-json\/topersia\/v1\/weather/, r => r.fulfill( { contentType: 'application/json', body: JSON.stringify( {
		days: [ { d: 'امروز', t: 18, n: 9, i: 'rain', s: 'بارانی', k: 'rain' }, { d: 'فردا', t: 20, n: 10, i: 'sun', s: 'آفتابی', k: 'clear' }, { d: 'پس‌فردا', t: 21, n: 11, i: 'sun', s: 'آفتابی', k: 'clear' } ],
		fx: { state: 'rain', windDeg: 270, windKph: 20, precipMm: 3, isDay: 1 }, note: 'آزمون', src: 'MET Norway'
	} ) } ) );
	/*
	 * لایه پنج ثانیه می‌ماند و بعد برداشته می‌شود؛ رویداد load صفحهٔ محلی
	 * (بی‌کش، با ۴۰۴ کُندِ payments.png وودمارت) دیرتر از آن می‌رسد. پس لایه
	 * همان لحظهٔ افزوده‌شدن گرفته می‌شود، و ۷۰۰ms بعد پیکسل‌های روشنِ canvas
	 * و شفافیتِ لایه خوانده می‌شود.
	 */
	await ctx.addInitScript( () => {
		window.__tpFx = null;
		new MutationObserver( () => {
			const l = document.querySelector( '.tp-hero .tp-wx-fx' );
			if ( ! l || window.__tpFx ) { return; }
			window.__tpFx = { cls: l.className, lit: -1 };
			setTimeout( () => {
				const c = l.querySelector( 'canvas' ); let n = 0;
				if ( c ) {
					const d = c.getContext( '2d' ).getImageData( 0, 0, c.width, c.height ).data;
					for ( let i = 3; i < d.length; i += 4 ) { if ( d[ i ] > 0 ) { n ++; } }
				}
				window.__tpFx.lit = n;
				window.__tpFx.opacity = parseFloat( getComputedStyle( l ).opacity );
				window.__tpFx.underOverlay = !! ( l.nextElementSibling && l.nextElementSibling.classList.contains( 'tp-hero__overlay' ) );
			}, 700 );
		} ).observe( document, { childList: true, subtree: true } );
	} );
	const page = await ctx.newPage();
	await page.goto( url, { waitUntil: 'domcontentloaded', timeout: 120000 } );
	/* پاسخ هوا رسیده و روزها به‌جای سه اسکلتِ `tp-is-load` چیده شده‌اند —
	   mount همان‌جا صدا زده می‌شود؛ پس «بی لایه» در کاهش حرکت واقعاً یعنی
	   پاسخ پردازش شد و لایه ساخته نشد */
	await page.waitForFunction( () => document.querySelector( '[data-tp-weather] .tp-wx__day:not(.tp-is-load)' ), null, { timeout: 60000 } );
	await page.waitForFunction( () => ! window.__tpFx || window.__tpFx.lit >= 0, null, { timeout: 5000 } ).catch( () => {} );
	await page.waitForTimeout( 800 );
	const fx = await page.evaluate( () => window.__tpFx );
	await ctx.close();
	return fx ? { layer: /tp-wx-fx--rain/.test( fx.cls ), lit: fx.lit, opacity: fx.opacity, underOverlay: fx.underOverlay } : { layer: false, lit: 0 };
}

/* ── ۲٫۰٫۳ تا ۲٫۰٫۶ — بلوک AI، تاریخ راستی‌آزمایی، کاشی مسیر، تراز دوطرفه ── */
async function phase2( page, tag, mobile ) {

	/* P5-D267 — مهمان: هر جمله یک بار؛ یک دکمه کنار «کپی خروجی»؛ پنجرۀ user-core با بازگشت */
	const ai = await page.evaluate( () => {
		const box = document.querySelector( '#tp-ai' );
		if ( ! box ) { return null; }
		const row = box.querySelector( '.tp-ai__actions' ), s = row && row.querySelector( '.tp-ai__signup' ), copy = row && row.querySelector( '[data-ai="copy"]' );
		return {
			h2: box.querySelectorAll( 'h2' ).length,
			heading: ( box.textContent.match( /این مقصد را در یک نگاه بفهمید/g ) || [] ).length,
			origin: ( box.textContent.match( /از دادۀ (?:موجود|ساختاریافتۀ) همین صفحه/g ) || [] ).length,
			invite: !! box.querySelector( 'h2 + p.tp-ai__cta-text' ),
			nextToCopy: !! ( s && copy && copy.nextElementSibling === s ),
			sameRow: !! ( s && copy && Math.abs( s.getBoundingClientRect().top - copy.getBoundingClientRect().top ) <= 2 ),
			login: box.querySelectorAll( '.tp-ai__login' ).length,
			text: s ? s.textContent.trim() : ''
		};
	} );
	if ( ! ai ) {
		info( tag + ': بلوک AI ندارد' );
	} else {
		check( `${ tag }: AI — عنوان و جملۀ منشأ هر کدام یک بار؛ دعوت زیر عنوان؛ «ثبت‌نام / ورود» کنار «کپی خروجی» در همان ردیف؛ «ورود» متنی نیست  P5-D267`,
			1 === ai.h2 && 1 === ai.heading && 1 === ai.origin && ai.invite && ai.nextToCopy && ( ai.sameRow || mobile ) && ! ai.login && 'ثبت‌نام / ورود' === ai.text, JSON.stringify( ai ) );
		await page.evaluate( () => document.querySelector( '#tp-ai .tp-ai__signup' ).scrollIntoView( { block: 'center', behavior: 'instant' } ) );
		await page.click( '#tp-ai .tp-ai__signup' );
		await page.waitForTimeout( 300 );
		const m = await page.evaluate( () => { const d = document.getElementById( 'tp-uc-login' ); return d ? { open: ! d.hidden, why: ( ( d.querySelector( '[data-tp-uc-reason]' ) || {} ).textContent || '' ).trim(), redirect: ( d.querySelector( '[data-tp-auth]' ) || { getAttribute: () => '' } ).getAttribute( 'data-tp-redirect' ) } : null; } );
		check( `${ tag }: AI — «ثبت‌نام / ورود» پنجرۀ ورود/ثبت‌نام user-core را با بازگشت به همین صفحه باز می‌کند  P5-D267`,
			!! m && m.open && m.redirect === page.url(), JSON.stringify( m ) );
		await page.keyboard.press( 'Escape' );
		await page.waitForTimeout( 150 );
	}

	/* P5-D268 — تاریخ زیر مقدار «وضعیت محتوا»، در همان خانه */
	const d = await page.evaluate( () => {
		const src = document.querySelector( '#tp-sources' );
		if ( ! src ) { return null; }
		const t = src.querySelector( '.tp-uid__date' ), badge = src.querySelectorAll( '.tp-verified' ).length;
		if ( ! t ) { return { date: false, badge }; }
		const b = t.parentElement, r = document.createRange();
		r.selectNodeContents( b.firstChild );
		return { date: true, badge, cell: b.tagName, label: b.parentElement.firstElementChild.textContent.trim(), below: t.getBoundingClientRect().top >= r.getBoundingClientRect().bottom - 1, dt: t.getAttribute( 'datetime' ) };
	} );
	if ( d && d.date ) {
		check( `${ tag }: منابع — تاریخ راستی‌آزمایی زیر مقدار «وضعیت محتوا» در همان خانه، ماشین‌خوان؛ نشان جداگانه نیست  P5-D268`,
			'B' === d.cell && 'وضعیت محتوا' === d.label && d.below && /^\d{4}-\d{2}-\d{2}$/.test( d.dt ) && ! d.badge, JSON.stringify( d ) );
	} else if ( d ) {
		check( `${ tag }: منابع — بی تاریخ فقط وضعیت، بی نشان جداگانه  P5-D268`, ! d.badge, JSON.stringify( d ) );
	}

	/* P5-D269 — کاشی مسیر وسط‌چین، در شبکۀ کاشی */
	const rt = await page.evaluate( () => {
		const t = document.querySelector( '#tp-routes .tp-tile' );
		if ( ! t ) { return null; }
		const cs = getComputedStyle( t ), g = t.parentElement, b = t.querySelector( 'b' ), sub = t.querySelector( '.tp-tile__sub' );
		const mid = r => ( r.left + r.right ) / 2, tr = t.getBoundingClientRect();
		return { ta: cs.textAlign, ai: cs.alignItems, grid: g.hasAttribute( 'data-tp-tiles' ), cols: getComputedStyle( g ).gridTemplateColumns.split( ' ' ).length,
			name: Math.round( Math.abs( mid( b.getBoundingClientRect() ) - mid( tr ) ) ), sub: sub ? Math.round( Math.abs( mid( sub.getBoundingClientRect() ) - mid( tr ) ) ) : -1,
			below: sub ? sub.getBoundingClientRect().top >= b.getBoundingClientRect().bottom - 1 : null };
	} );
	if ( rt ) {
		check( `${ tag }: مسیرها — کاشی P5-D244 وسط‌چین: نام، زیرش ایستگاه‌ها؛ ${ rt.cols } ستون  P5-D269`,
			'center' === rt.ta && 'center' === rt.ai && rt.grid && rt.cols >= 2 && rt.name <= 1 && rt.sub <= 1 && false !== rt.below, JSON.stringify( rt ) );
	}

	/* P5-D270 — تراز دوطرفۀ محاسبه‌شده؛ سرفصل و کاشی نه */
	const j = await page.evaluate( () => {
		const q = s => document.querySelector( s ), out = {};
		for ( const [ k, s ] of [ [ 'about', '#tp-overview .tp-longform p' ], [ 'faq', '#tp-faq details p' ], [ 'ai', '#tp-ai .tp-ai__out p' ], [ 'news', '#tp-news .tp-news p' ] ] ) {
			if ( q( s ) ) { const cs = getComputedStyle( q( s ) ); out[ k ] = cs.textAlign + '/' + cs.textAlignLast; }
		}
		out.heading = q( '#tp-overview .tp-longform h3' ) ? getComputedStyle( q( '#tp-overview .tp-longform h3' ) ).textAlign : '';
		out.h2 = getComputedStyle( q( '.tp-page h2' ) ).textAlign;
		out.tile = q( '.tp-tile' ) ? getComputedStyle( q( '.tp-tile' ) ).textAlign : '';
		out.disclaimer = q( '.tp-ai__disclaimer' ) ? getComputedStyle( q( '.tp-ai__disclaimer' ) ).textAlign : '';
		return out;
	} );
	const J = [ 'about', 'faq', 'ai', 'news' ].filter( k => j[ k ] );
	check( `${ tag }: تراز دوطرفه روی ${ J.join( ' · ' ) } (justify، خط آخر از آغاز)؛ سرفصل، کاشی و جملۀ تک‌خطی نه  P5-D270`,
		J.length >= 3 && J.every( k => 'justify/start' === j[ k ] ) && 'justify' !== j.heading && 'justify' !== j.h2 && 'justify' !== j.tile && 'justify' !== j.disclaimer, JSON.stringify( j ) );

	/* عکس ۳۹۰ برای دیدن فاصلۀ میان واژه‌ها در ستون باریک — TP_SHOTS=پوشه */
	if ( mobile && process.env.TP_SHOTS ) {
		for ( const id of [ 'tp-overview', 'tp-faq', 'tp-ai' ] ) {
			const el = page.locator( '#' + id );
			if ( await el.count() ) {
				await page.evaluate( i => document.getElementById( i ).scrollIntoView( { block: 'start', behavior: 'instant' } ), id );
				await page.waitForTimeout( 300 );
				await el.screenshot( { path: path.join( process.env.TP_SHOTS, tag.replace( /\s+/g, '-' ) + '-' + id + '.png' ) } ).catch( () => {} );
			}
		}
	}
}

/*
 * ── ۲٫۰٫۸ — صفحه‌های واقعی ترجمه: جهت و شرط زبان WPML ─────────────
 * ترجمه‌های #99 و #399 (scripts/tp-local-441.php). جهت از خودِ صفحه: هر
 * عنصر `.tp-page` جهت صفحه را دارد، مگر درونِ عنصری با `dir` صریح — آن‌ها
 * جدا فهرست و سنجیده می‌شوند. شرط زبان: هر پیوند درونیِ `.tp-page` به
 * همان زبان است؛ رابطه‌های بی‌نسخه در این زبان نمی‌آیند.
 */
async function langPage( browser, url, want, tag ) {

	const s = await open( browser, url );
	const r = await s.page.evaluate( dir => {
		const pages = [ ...document.querySelectorAll( '.tp-page' ) ];
		const odd = [], explicit = [];
		pages.forEach( p => p.querySelectorAll( '*' ).forEach( e => {
			const own = e.closest( '[dir]' );
			if ( e.hasAttribute( 'dir' ) ) { explicit.push( e.getAttribute( 'dir' ) + ' ' + ( e.getAttribute( 'class' ) || e.tagName ).split( ' ' )[0] ); }
			if ( own && own !== document.documentElement && p.contains( own ) ) { return; }
			if ( 'svg' === e.tagName.toLowerCase() || e.closest( 'svg' ) ) { return; }
			if ( getComputedStyle( e ).direction !== dir ) { odd.push( ( e.getAttribute( 'class' ) || e.tagName ).split( ' ' )[0] ); }
		} ) );
		/* پیوند ورود (`data-tp-login`) پنجره باز می‌کند؛ href فقط جایگزینِ بی‌جاوااسکریپت است و
		   به برگۀ حساب زبان جاری می‌رود، یا اگر آن زبان برگه ندارد، به اصل — جدا گزارش می‌شود */
		const links = [ ...new Set( pages.flatMap( p => [ ...p.querySelectorAll( 'a[href^="' + location.origin + '/"]:not([data-tp-login])' ) ].map( a => new URL( a.href ).pathname ) ) ) ];
		const login = [ ...new Set( pages.flatMap( p => [ ...p.querySelectorAll( 'a[data-tp-login][href^="' + location.origin + '/"]' ) ].map( a => new URL( a.href ).pathname ) ) ) ];
		return {
			lang: document.documentElement.lang, dir: getComputedStyle( pages[0] ).direction,
			odd: [ ...new Set( odd ) ], explicit: [ ...new Set( explicit ) ], links, login,
			h1: ( document.querySelector( '.tp-page h1' ) || { textContent: '' } ).textContent.trim()
		};
	}, want.dir );
	await s.ctx.close();

	const wrongDir = r.explicit.filter( x => ! x.startsWith( want.dir ) && ! /^ltr tp-lat|^ltr tp-hero__en|^ltr span|^ltr SPAN/.test( x ) );
	check( `${ tag } ${ want.path }: <html lang="${ want.lang }…">، جهت ${ want.dir }؛ هر عنصر صفحه همان جهت؛ هیچ dir صریحِ وارونه  ۲٫۰٫۸`,
		0 === r.lang.indexOf( want.lang ) && want.dir === r.dir && ! r.odd.length && ! wrongDir.length, JSON.stringify( { lang: r.lang, dir: r.dir, odd: r.odd.slice( 0, 8 ), wrongDir } ) );
	check( `${ tag } ${ want.path }: شرط زبان WPML — هر پیوند درونی به ${ want.prefix }؛ رابطۀ بی‌نسخه در این زبان نمی‌آید (${ r.links.length } پیوند)  P5-D247 · ۲٫۰٫۸`,
		r.links.length > 0 && r.links.every( l => 0 === l.indexOf( want.prefix ) ), JSON.stringify( r.links ) );
	info( `${ tag } ${ want.path }: h1 «${ r.h1 }»` );
	if ( r.login.some( l => 0 !== l.indexOf( want.prefix ) ) ) {
		info( `${ tag } ${ want.path }: جایگزینِ بی‌جاوااسکریپتِ پیوند ورود به ${ r.login.join( ' · ' ) } — برگۀ حساب این زبان ندارد (داده)` );
	}
	return r;
}

/*
 * ── ۴٫۴٫۲ پنجرۀ گالری بی خانۀ خالی ─────────────────────────────────
 * کاشی پهن (نخستین و نهمین) دو ستون می‌گیرد و ستون‌ها auto-fill است؛ در
 * پنج ستون (۱۴۴۰ به بالا) و دو ستون (۳۹۰) کاشی پهن در ستون آخر جا نمی‌شد و
 * خانۀ سیاه می‌ماند (عکس کاربر، ۱۹۲۰). اشغال هر ردیف از جعبۀ خودِ کاشی‌ها
 * خوانده می‌شود؛ جز ردیف پایانی هیچ خانه‌ای خالی نیست. جهش: همان صفحه با
 * `grid-auto-flow:row` (۴٫۴٫۱) باید خانۀ خالی نشان دهد.
 */
function galleryHoles() {
	const grid = document.querySelector( '#tpGalleryModal .tp-gallery-modal__grid' );
	const items = [ ...grid.querySelectorAll( '.tp-gallery-modal__item' ) ];
	const cs = getComputedStyle( grid );
	const cols = cs.gridTemplateColumns.split( ' ' ).filter( Boolean ).length;
	const gap = parseFloat( cs.columnGap || 0 );
	const g = grid.getBoundingClientRect();
	const colW = ( g.width - ( cols - 1 ) * gap ) / cols;
	const rtl = 'rtl' === cs.direction;
	const rows = [];
	items.map( e => e.getBoundingClientRect().top ).sort( ( a, b ) => a - b ).forEach( t => { if ( ! rows.length || t - rows[ rows.length - 1 ] > 40 ) { rows.push( t ); } } );
	const occ = {};
	let last = 0;
	items.forEach( e => {
		const r = e.getBoundingClientRect();
		const row = rows.findIndex( x => Math.abs( x - r.top ) <= 40 );
		const c0 = Math.round( ( rtl ? g.right - r.right : r.left - g.left ) / ( colW + gap ) );
		const span = Math.round( ( r.width + gap ) / ( colW + gap ) );
		for ( let c = c0; c < c0 + span; c ++ ) { occ[ row + ':' + c ] = true; }
		last = Math.max( last, row );
	} );
	const holes = [];
	for ( let row = 0; row < last; row ++ ) { for ( let c = 0; c < cols; c ++ ) { if ( ! occ[ row + ':' + c ] ) { holes.push( row + ':' + c ); } } }
	return { cols, items: items.length, wide: grid.querySelectorAll( '.tp-gallery-modal__item--wide' ).length, holes };
}

async function gallery442( browser, url, tag ) {
	const widths = [ 1920, 1440, 1180, 900, 390 ];
	const real = [], mutant = [];
	for ( const w of widths ) {
		for ( const mut of [ false, true ] ) {
			const s = await open( browser, url, { viewport: { width: w, height: 1000 } } );
			if ( ! await s.page.$( '[data-tp-gallery-open]' ) ) { await s.ctx.close(); info( `${ tag }: گالری ندارد` ); return; }
			if ( mut ) { await s.page.addStyleTag( { content: 'html body .tp-page .tp-gallery-modal__grid{grid-auto-flow:row !important}' } ); }
			const opener = s.page.locator( '[data-tp-gallery-open]' ).first();
			await opener.evaluate( e => e.scrollIntoView( { behavior: 'instant', block: 'center' } ) );
			await opener.click();
			await s.page.waitForTimeout( 600 );
			( mut ? mutant : real ).push( Object.assign( { w }, await s.page.evaluate( galleryHoles ) ) );
			await s.ctx.close();
		}
	}
	check( `${ tag }: پنجرۀ گالری در ۱۹۲۰ · ۱۴۴۰ · ۱۱۸۰ · ۹۰۰ · ۳۹۰ جز ردیف پایانی هیچ خانۀ خالی ندارد؛ جهش (بی dense، ۴٫۴٫۱) خانۀ خالی دارد  ۴٫۴٫۲`,
		real.every( r => r.items > 0 && ! r.holes.length ) && mutant.some( r => r.holes.length ),
		JSON.stringify( { real: real.map( r => [ r.w, r.cols, r.holes.length ] ), mutant: mutant.map( r => [ r.w, r.cols, r.holes.length ] ) } ) );
}

/*
 * ── ۴٫۴٫۲ زبان صفحه روی /en/ و /ar/ واقعی ──────────────────────────
 * درون `.tp-page` و لایۀ بیرونی (مودال‌ها، نوار پایین، برگۀ بخش‌ها) هیچ
 * رشتۀ رابطِ کاتالوگ به فارسی نمانده — متن گره، aria، title، placeholder.
 * رشته‌های رابط از خودِ `languages/topersia.pot` خوانده می‌شوند؛ نامِ
 * موجودیت و متنِ داده رابط نیست. هدرِ وودمارت و مگامنو از این افزونه
 * نیستند (منوی وردپرس و ویجت HTML المنتور — فاز ۷) و جدا شمرده می‌شوند.
 */
function potMsgids() {
	const src = fs.readFileSync( path.join( __dirname, '..', 'plugin', 'topersia-render', 'languages', 'topersia.pot' ), 'utf8' );
	const out = new Set();
	src.replace( /(?:^|\n)(msgctxt "[^"]*"\n)?msgid "((?:[^"\\]|\\.)+)"\n(?:msgstr|msgid_plural)/g, ( m, ctx, id ) => { if ( ! /%/.test( id ) && /[؀-ۿ]/.test( id ) ) { out.add( id.replace( /\\"/g, '"' ) ); } return m; } );
	return [ ...out ];
}

/* واژه‌هایی که ترجمۀ عربی‌شان همان فارسی است («مارس» · «آبان» · «محدود») — در صفحۀ عربی رابطِ جامانده نیستند */
function sameInAr() {
	const src = fs.readFileSync( path.join( __dirname, '..', 'plugin', 'topersia-render', 'languages', 'topersia-ar.po' ), 'utf8' );
	const out = [];
	src.replace( /msgid "((?:[^"\\]|\\.)+)"\nmsgstr "((?:[^"\\]|\\.)*)"/g, ( m, id, str ) => { if ( id === str ) { out.push( id ); } return m; } );
	return out;
}

async function lang442( browser, want, tag, ui, same ) {
	const r = await langProbe( browser, want.url, ui, same );
	/* جهش: همان صفحه بی کاتالوگ — نسخۀ فارسی همان پست. کاوشگر باید آن‌جا رشتۀ رابط فارسی ببیند */
	const fa = await langProbe( browser, want.fa, ui, [] );
	check( `${ tag } ${ want.path }: هیچ رشتۀ رابط فارسی در صفحه و لایۀ بیرونی — متن، aria، title، placeholder؛ جهش (همان صفحه بی کاتالوگ، ${ want.fa.replace( /^https?:\/\/[^/]+/, '' ) }) می‌افتد  ۴٫۴٫۲`,
		! r.left.length && fa.left.length >= 20, r.left.slice( 0, 6 ).join( ' · ' ) + ( fa.left.length >= 20 ? '' : ` · جهش فقط ${ fa.left.length }` ) );
	check( `${ tag } ${ want.path }: تیتر «${ want.h1 }» بی پیشوند فارسی، eyebrow «${ want.eyebrow }»  ۴٫۴٫۲ · P5-D224`, want.h1 === r.h1 && want.eyebrow === r.eyebrow.trim(), JSON.stringify( [ r.eyebrow, r.h1 ] ) );
	if ( 'ltr' === want.dir ) {
		check( `${ tag } ${ want.path }: تیتر چپ‌به‌راست فاصلۀ واژه دارد — letter-spacing ≥ −۰٫۰۳em، word-spacing > ۰ (مرجعِ فارسی −۰٫۰۷em دو واژه را یکی می‌کرد)  ۴٫۴٫۲`,
			'ltr' === r.dir && r.ws > 0, JSON.stringify( { ls: r.ls, ws: r.ws } ) );
	}
	info( `${ tag } ${ want.path }: هدر وودمارت و مگامنو ${ r.mega } برچسب فارسی — منوی وردپرس (ترجمۀ منو در WPML) و ویجت HTML المنتور (فاز ۷)، نه این افزونه` );
}

async function langProbe( browser, url, ui, same ) {
	const s = await open( browser, url );
	const r = await s.page.evaluate( ( { ui, same } ) => {
		const roots = [ ...document.querySelectorAll( '.tp-page' ) ];
		const texts = new Map();
		const add = ( t, where ) => { t = ( t || '' ).trim(); if ( t ) { texts.set( t, where ); } };
		roots.forEach( root => {
			const w = document.createTreeWalker( root, NodeFilter.SHOW_TEXT );
			for ( let n = w.nextNode(); n; n = w.nextNode() ) {
				if ( n.parentElement.closest( 'script,style,svg' ) ) { continue; }
				add( n.nodeValue, ( n.parentElement.className || n.parentElement.tagName ).toString().split( ' ' )[0] );
			}
			root.querySelectorAll( '[aria-label],[title],[placeholder],[alt]' ).forEach( e => [ 'aria-label', 'title', 'placeholder', 'alt' ].forEach( a => add( e.getAttribute( a ), '@' + a ) ) );
		} );
		const left = ui.filter( m => ! same.includes( m ) && texts.has( m ) ).map( m => m + ' ← ' + texts.get( m ) );
		const mega = [ ...document.querySelectorAll( '.tp-mega, .woodmart-nav-link' ) ].filter( e => /[؀-ۿ]/.test( e.textContent ) && ! /^(فارسی|العربية)$/.test( e.textContent.trim() ) ).length;
		const h = document.querySelector( '.tp-hero h1' );
		const hcs = h ? getComputedStyle( h ) : null;
		return { left, mega, h1: h ? h.textContent : '', eyebrow: ( document.querySelector( '.tp-eyebrow' ) || {} ).textContent || '', ws: hcs ? parseFloat( hcs.wordSpacing ) : 0, ls: hcs ? parseFloat( hcs.letterSpacing ) : 0, dir: hcs ? hcs.direction : '' };
	}, { ui, same } );
	await s.ctx.close();
	return r;
}

/* تیتر ترجمه از name_{زبان} (copy WPML)، نوع در eyebrow — تا ۲ج٫۸ ترجمه‌های محلی فیلد copy نداشتند و تیتر عنوان پست بود */
const LANG442 = [
	{ url: ENV.base + '/en/province/isfahan/', fa: ENV.base + '/province/isfahan/', path: '/en/province/isfahan/', dir: 'ltr', h1: 'Isfahan', eyebrow: 'Province', lang: 'en' },
	{ url: ENV.base + '/ar/province/isfahan/', fa: ENV.base + '/province/isfahan/', path: '/ar/province/isfahan/', dir: 'rtl', h1: 'أصفهان', eyebrow: 'محافظة', lang: 'ar' },
	{ url: ENV.base + '/en/city/isfahan/', fa: ENV.base + '/city/isfahan/', path: '/en/city/isfahan/', dir: 'ltr', h1: 'Isfahan', eyebrow: 'City', lang: 'en' },
	{ url: ENV.base + '/ar/city/isfahan/', fa: ENV.base + '/city/isfahan/', path: '/ar/city/isfahan/', dir: 'rtl', h1: 'أصفهان', eyebrow: 'مدينة', lang: 'ar' },
];

/* هم‌مرز فارسیِ اصفهان: اصفهان یک بار، با وجود ترجمه‌هایش — دروازۀ زبان  P5-D247 */
async function neighborOnce( browser, tag ) {
	const s = await open( browser, ENV.base + '/province/chaharmahal-and-bakhtiari/' );
	const n = await s.page.evaluate( () => [ ...document.querySelectorAll( '#tp-neighbors a' ) ].map( a => ( { name: a.querySelector( 'b' ).textContent.trim(), href: new URL( a.href ).pathname } ) ) );
	await s.ctx.close();
	const isf = n.filter( x => /اصفهان|Isfahan|أصفهان/.test( x.name ) );
	check( `${ tag }: هم‌مرزهای چهارمحال — اصفهان یک بار، با نام و پیوند فارسی، با وجود ترجمه‌های انگلیسی و عربی‌اش  P5-D247`,
		1 === isf.length && 'اصفهان' === isf[0].name && '/province/isfahan/' === isf[0].href, JSON.stringify( n ) );
}

const LANG_PAGES = [
	{ url: ENV.base + '/en/province/isfahan/', path: '/en/province/isfahan/', lang: 'en', dir: 'ltr', prefix: '/en/' },
	{ url: ENV.base + '/ar/province/isfahan/', path: '/ar/province/isfahan/', lang: 'ar', dir: 'rtl', prefix: '/ar/' },
	{ url: ENV.base + '/en/city/isfahan/', path: '/en/city/isfahan/', lang: 'en', dir: 'ltr', prefix: '/en/' },
	{ url: ENV.base + '/ar/city/isfahan/', path: '/ar/city/isfahan/', lang: 'ar', dir: 'rtl', prefix: '/ar/' },
];

/*
 * `TP_ONLY=ff-sliders` — فقط سنجهٔ پیکان اسلایدرهای هارنس در فایرفاکس ۱۴۴۰ (برای بازاجرای دو شکست پیکان، بی اجرای ۱۷دقیقه‌ای کل مجموعه).
 * شمارش و کد خروج همان است.
 */
const ONLY = process.env.TP_ONLY || '';

( async () => {

	const hx = harness();

	for ( const [ name, type ] of [ [ 'کروم', chromium ], [ 'فایرفاکس', firefox ] ].filter( b => 'ff-sliders' !== ONLY || 'فایرفاکس' === b[0] ) ) {

		const browser = await type.launch();
		const mobile = 'کروم' === name
			? { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }
			: { viewport: { width: 390, height: 844 }, hasTouch: true };

		/* ── هارنس: همان استان، راست‌به‌چپ و چپ‌به‌راست — P5-D243 ── */
		for ( const [ label, url ] of [ [ 'هارنس راست‌به‌چپ', hx.rtl ], [ 'هارنس چپ‌به‌راست', hx.ltr ] ].filter( () => ! [ 'air', 'map', 'lang' ].includes( ONLY ) ) ) {
			console.log( `\n${ name } — ${ label }\n` + '─'.repeat( 78 ) );
			for ( const [ size, opts, touch ] of [ [ '۱۴۴۰', null, false ], [ '۳۹۰', mobile, true ] ].filter( z => ! ONLY || '۱۴۴۰' === z[0] ) ) {
				const s = await open( browser, url, opts );
				if ( ! await sliders( s.page, `${ name } ${ size }` ) ) { check( `${ name } ${ size }: دست‌کم یک اسلایدرِ لبریز سنجیده شد`, false ); }
				if ( ! ONLY ) { await solo( s.page, `${ name } ${ size }`, touch ); }
				if ( ! touch && ! ONLY ) { await drag( s.page, `${ name } ${ size } ${ label }` ); }
				await s.ctx.close();
			}
		}

		/* ── هارنس: چهارده روستا — «{N} مورد دیگر» و دوستونه، جایی که صفحهٔ محلی بیش از شش کاشی ندارد  P5-D244 ── */
		console.log( `\n${ name } — هارنس چهارده روستا\n` + '─'.repeat( 78 ) );
		for ( const [ size, opts ] of [ [ '۱۴۴۰', null ], [ '۳۹۰', mobile ] ].filter( () => ! ONLY ) ) {
			const s = await open( browser, hx.more, opts );
			await more( s.page, `${ name } ${ size }` );
			await tileFit( s.page, `${ name } ${ size }` );
			await s.ctx.close();
		}

		for ( const url of URLS ) {

			const where = url.replace( /^https?:\/\/[^/]+/, '' );
			console.log( `\n${ name } — ${ where }\n` + '─'.repeat( 78 ) );

			if ( 'lang' === ONLY ) {
				continue;
			}

			if ( 'air' === ONLY ) {
				await airBox( browser, url, name );
				continue;
			}

			if ( 'map' === ONLY ) {
				await mapPoints( browser, url, name );
				continue;
			}

			const html = ONLY ? '' : await ( await fetch( url ) ).text();
			if ( ! ONLY ) check( `${ name }: MapLibre در HTML اولیه نیست — فقط هنگام نیاز  §۱`, html.indexOf( 'maplibre-gl' ) < 0 );

			/* دسکتاپ ۱۴۴۰ */
			let s = await open( browser, url );
			const n = await sliders( s.page, name + ' ۱۴۴۰' );
			if ( ONLY ) { await s.ctx.close(); continue; }
			if ( ! n ) { info( 'اسلایدرِ لبریز در ۱۴۴۰ نبود' ); }
			await solo( s.page, name + ' ۱۴۴۰', false );
			await drag( s.page, name + ' ۱۴۴۰' );
			await tileTokens( s.page, name + ' ۱۴۴۰' );
			await tileFit( s.page, name + ' ۱۴۴۰' );
			await more( s.page, name + ' ۱۴۴۰' );
			await cards( s.page, name + ' ۱۴۴۰', false );
			await phase2( s.page, name + ' ۱۴۴۰', false );
			await share( s.page, name + ' ۱۴۴۰', false );
			await map( s.page, name + ' ۱۴۴۰' );
			await s.ctx.close();

			/* موبایل ۳۹۰ — کروم با شبیه‌سازی موبایل، فایرفاکس در همان اندازه با لمس */
			s = await open( browser, url, mobile );
			await mnav( s.page, name + ' ۳۹۰' );
			await sliders( s.page, name + ' ۳۹۰' );
			await solo( s.page, name + ' ۳۹۰', true );
			await tileFit( s.page, name + ' ۳۹۰' );
			await cards( s.page, name + ' ۳۹۰', true );
			await phase2( s.page, name + ' ۳۹۰', true );
			await share( s.page, name + ' ۳۹۰', true );
			await s.ctx.close();

			/* ۲ه٫۱ — نوار بخش‌ها نرم: ۱۴۴۰ (نوار جا می‌شود ← قرص لغزان)، ۹۰۰ (نوار لبریز ← لغزش افقی)، و کاهش حرکت */
			await snav( browser, url, name, 1440, false );
			await snav( browser, url, name, 900, false );
			await snav( browser, url, name, 1440, true );

			/* انیمیشن هوا — حرکت روشن و کاهش حرکتِ سیستم */
			await airBox( browser, url, name );
			await mapPoints( browser, url, name );

			const on  = await weatherFx( browser, url, name, false );
			const off = await weatherFx( browser, url, name, true );
			check( `${ name }: انیمیشن هوا از پاسخ /weather روی سربرگ دیده می‌شود؛ با کاهش حرکت نه  §۳`,
				on.layer && on.lit > 0 && on.opacity > 0 && on.underOverlay && ! off.layer, JSON.stringify( { on, off } ) );
		}

		/* ── ۲٫۰٫۸ — ترجمه‌های واقعی: چپ‌به‌راست، راست‌به‌چپ عربی، شرط زبان WPML ── */
		if ( ! process.argv.slice( 2 ).length && ( ! ONLY || 'lang' === ONLY ) ) {
			console.log( `\n${ name } — ترجمه‌های واقعی #99 و #399\n` + '─'.repeat( 78 ) );
			for ( const want of LANG_PAGES ) {
				await langPage( browser, want.url, want, name );
			}
			await neighborOnce( browser, name );

			/* ── ۴٫۴٫۲ — زبان صفحه و پنجرۀ گالری ── */
			console.log( `\n${ name } — ۴٫۴٫۲ زبان صفحه و گالری\n` + '─'.repeat( 78 ) );
			const ui = potMsgids(), same = sameInAr();
			for ( const want of LANG442 ) {
				await lang442( browser, want, name, ui, 'ar' === want.lang ? same : [] );
			}
			await gallery442( browser, URLS[0], name );
		}

		await browser.close();
	}

	console.log( '─'.repeat( 78 ) + `\n  گذشته: ${ pass }   افتاده: ${ fail }` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 1 ); } );
