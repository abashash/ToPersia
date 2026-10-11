/**
 * صفحۀ «سرویس‌های خارجی» هسته در پیشخوان واقعی — ۲ج٫۱۱
 *
 *   node tests/services-admin.js [--shots=before|after] [--only=chromium|firefox]
 *
 * مدیر آزمون (tests/local-login.php tp_test_admin — هیچ گذرواژه‌ای). زبان
 * پیشخوانش در هر دور fa_IR (راست‌به‌چپ) یا en_US (چپ‌به‌راست) می‌شود و در پایان
 * به مقدار پیشین برمی‌گردد (tests/services-admin-state.php). فقط در کروم فرم یک
 * بار بی‌تغییر و یک بار با سه تغییر «ذخیره» می‌شود؛ گزینۀ خام پیش از آن عکس گرفته
 * و در پایان برگردانده می‌شود.
 *
 * ادعا، در هر دو جهت و در ۱۴۴۰ · ۱۲۸۰ · ۱۰۲۴:
 *   - هر خانۀ جدول (سر و بدنه، سطرهای پیدا) متن یا کنترل دیدنی دارد: پهنا و
 *     بلندی بیش از صفر، کنتراست متن ≥ ۴٫۵ روی پس‌زمینۀ واقعی، و بی سرریز پنهان
 *     (نه بریده‌شدن با overflow، نه بیرون‌زدن از خانه، نه متنی که در فیلد جا نشود).
 *   - صفحه سرریز افقی ندارد و جدول در پهنای خودش جا می‌شود (هیچ خانه‌ای پشت اسکرول نیست).
 *   - خانه‌ها vertical-align:middle؛ ستون‌های کوتاه وسط‌چین؛ ستون‌های متنی start.
 *   - مقدار لاتین در <bdi dir="ltr">؛ شیوه‌نامۀ صفحه left/right ثابت ندارد.
 *   - جهت صفحه همان جهت پیشخوان است.
 *
 *   - «ذخیره»: نام فیلدها با رادیو و textarea همان ماند؛ مقدار بلندِ ذخیره‌شده پیداست.
 *
 * عکس‌ها: tests/.screens/2c11/<shots>-<browser>-<fa|en>-<width>.png (بیرون از git).
 * خطای JS افزونۀ دیگری که در همۀ صفحه‌های پیشخوان هست فقط گزارش می‌شود (ℹ︎).
 */

'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium, firefox } = require( './pw.js' );
const ENV = require( './env.js' );

const WS    = path.resolve( __dirname, '..' );
const PHP   = ENV.php;
const PAGE  = ENV.base + '/geo/wp-admin/admin.php?page=topersia-services';
const ARGS  = Object.fromEntries( process.argv.slice( 2 ).map( a => a.replace( /^--/, '' ).split( '=' ) ) );
const SHOTS = ARGS.shots || 'after';
const DIR   = path.join( WS, 'tests', '.screens', '2c11' );
const WIDTHS = [ 1440, 1280, 1024 ];
const LANGS  = [ [ 'fa', 'fa_IR', 'rtl' ], [ 'en', 'en_US', 'ltr' ] ];

let pass = 0, fail = 0;
const foreign = new Set();
function check( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { fail ++; console.log( '  ❌ ' + label + ( note ? '   ← ' + note : '' ) ); }
}
function head( t ) { console.log( '\n' + t + '\n' + '─'.repeat( 78 ) ); }
function php( args ) { return execFileSync( PHP, args, { cwd: WS, encoding: 'utf8' } ); }

/* درون صفحه: هر خانه، هر متن و هر کنترل — اندازه، کنتراست، بریدگی */
function probe() {

	const cv = document.createElement( 'canvas' );
	const cx = cv.getContext( '2d' );

	function rgba( s ) {
		const m = String( s ).match( /rgba?\(([^)]+)\)/ );
		if ( ! m ) { return [ 0, 0, 0, 0 ]; }
		const p = m[1].split( /[\s,/]+/ ).filter( Boolean ).map( parseFloat );
		return [ p[0], p[1], p[2], p.length > 3 ? p[3] : 1 ];
	}
	function over( f, b ) {
		const a = f[3];
		return [ f[0] * a + b[0] * ( 1 - a ), f[1] * a + b[1] * ( 1 - a ), f[2] * a + b[2] * ( 1 - a ), 1 ];
	}
	function bgOf( el ) {
		const layers = [];
		for ( let e = el; e && 1 === e.nodeType; e = e.parentElement ) {
			const c = rgba( getComputedStyle( e ).backgroundColor );
			if ( c[3] > 0.02 ) { layers.push( c ); }
			if ( c[3] > 0.99 ) { break; }
		}
		let base = [ 255, 255, 255, 1 ];
		for ( let i = layers.length - 1; i >= 0; i -- ) { base = over( layers[ i ], base ); }
		return base;
	}
	function lum( a ) {
		const f = v => ( v /= 255, v <= 0.03928 ? v / 12.92 : Math.pow( ( v + 0.055 ) / 1.055, 2.4 ) );
		return 0.2126 * f( a[0] ) + 0.7152 * f( a[1] ) + 0.0722 * f( a[2] );
	}
	function ratio( a, b ) {
		const l1 = lum( a ), l2 = lum( b );
		return Math.round( ( ( Math.max( l1, l2 ) + 0.05 ) / ( Math.min( l1, l2 ) + 0.05 ) ) * 100 ) / 100;
	}
	function opacity( el ) {
		let o = 1;
		for ( let e = el; e && 1 === e.nodeType; e = e.parentElement ) { o *= parseFloat( getComputedStyle( e ).opacity ); }
		return o;
	}
	function contrast( el, color ) {
		const bg = bgOf( el );
		const fg = rgba( color );
		return ratio( over( [ fg[0], fg[1], fg[2], fg[3] * opacity( el ) ], bg ), bg );
	}
	/* بخشی از مستطیل که نیای بُرنده (overflow غیر visible) آن را نشان نمی‌دهد */
	function clipped( el, r ) {
		for ( let e = el.parentElement; e && e !== document.body; e = e.parentElement ) {
			const cs = getComputedStyle( e );
			if ( 'visible' === cs.overflowX && 'visible' === cs.overflowY ) { continue; }
			const c = e.getBoundingClientRect();
			if ( r.left < c.left - 1 || r.right > c.right + 1 || r.top < c.top - 1 || r.bottom > c.bottom + 1 ) {
				return ( e.className ? '.' + String( e.className ).split( ' ' )[0] : e.tagName.toLowerCase() ) + ' (' + cs.overflowX + ')';
			}
		}
		return '';
	}
	function textWidth( el, text ) {
		const cs = getComputedStyle( el );
		cx.font = cs.fontStyle + ' ' + cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily;
		return cx.measureText( text ).width;
	}
	function shown( el ) {
		const cs = getComputedStyle( el );
		const r = el.getBoundingClientRect();
		return r.width > 0 && r.height > 0 && 'visible' === cs.visibility && el.getClientRects().length > 0;
	}

	const table = document.querySelector( 'table.tp-services' );
	const scroll = table ? table.parentElement : null;
	const cells = [];
	const rows = table ? [ ...table.querySelectorAll( 'tr' ) ].filter( tr => ! tr.hidden ) : [];

	rows.forEach( ( tr, ri ) => {
		[ ...tr.children ].forEach( ( td, ci ) => {
			const items = [];
			const tdRect = td.getBoundingClientRect();

			/* متن‌ها — هر گرهٔ متنی غیرخالی جدا، بیرون از کنترل‌ها */
			const walker = document.createTreeWalker( td, NodeFilter.SHOW_TEXT );
			for ( let n = walker.nextNode(); n; n = walker.nextNode() ) {
				const t = n.nodeValue.trim();
				const p = n.parentElement;
				if ( ! t || p.closest( 'select,button,script,style,.screen-reader-text' ) ) { continue; }
				const range = document.createRange();
				range.selectNodeContents( n );
				const r = range.getBoundingClientRect();
				const cs = getComputedStyle( p );
				const vis = r.width > 0 && r.height > 0 && 'visible' === cs.visibility;
				const out = r.left < tdRect.left - 1 || r.right > tdRect.right + 1;
				items.push( { kind: 'text', text: t.slice( 0, 40 ), vis, ratio: vis ? contrast( p, cs.color ) : 0, clip: vis ? ( clipped( p, r ) || ( out ? 'بیرون از خانه' : '' ) ) : '' } );
			}

			/* کنترل‌ها — متن دیدنی‌شان مقدار، نگه‌دار یا گزینۀ برگزیده است */
			td.querySelectorAll( 'input:not([type=hidden]),select,button,textarea' ).forEach( el => {
				const vis = shown( el );
				const r = el.getBoundingClientRect();
				const cs = getComputedStyle( el );
				let text = '', color = cs.color;
				if ( 'SELECT' === el.tagName ) {
					text = el.selectedIndex >= 0 ? el.options[ el.selectedIndex ].text : '';
				} else if ( 'BUTTON' === el.tagName ) {
					text = el.textContent.trim();
				} else if ( [ 'checkbox', 'radio' ].includes( el.type ) ) {
					text = '';
				} else {
					text = el.value || el.placeholder || '';
					if ( ! el.value && el.placeholder ) { color = getComputedStyle( el, '::placeholder' ).color; }
				}
				let fits = true;
				if ( vis && text && 'TEXTAREA' === el.tagName ) {
					/* می‌شکند؛ جا شدن یعنی بی سرریز عمودی — نگه‌دار هم با همان سنجه */
					const v = el.value;
					if ( ! v ) { el.value = el.placeholder; }
					fits = el.scrollHeight <= el.clientHeight + 1;
					if ( ! v ) { el.value = ''; }
				} else if ( vis && text && 'BUTTON' !== el.tagName ) {
					const pad = parseFloat( cs.paddingLeft ) + parseFloat( cs.paddingRight ) + parseFloat( cs.borderLeftWidth ) + parseFloat( cs.borderRightWidth );
					fits = textWidth( el, text ) <= r.width - pad + 1;
				}
				if ( vis && 'BUTTON' === el.tagName ) { fits = el.scrollWidth <= el.clientWidth + 1; }
				const out = r.left < tdRect.left - 1 || r.right > tdRect.right + 1;
				items.push( {
					kind: el.tagName.toLowerCase() + ( el.type && 'SELECT' !== el.tagName ? ':' + el.type : '' ),
					text: text.slice( 0, 40 ), vis, disabled: el.disabled,
					ratio: vis && text && ! el.disabled ? contrast( el, color ) : null,
					clip: vis ? ( clipped( el, r ) || ( out ? 'بیرون از خانه' : '' ) || ( fits ? '' : 'متن در فیلد جا نمی‌شود' ) ) : '',
				} );
			} );

			const cs = getComputedStyle( td );
			cells.push( {
				row: tr.getAttribute( 'data-service' ) || ( tr.parentElement.tagName.toLowerCase() + ri ),
				col: ci, tag: td.tagName.toLowerCase(), valign: cs.verticalAlign, align: cs.textAlign,
				width: Math.round( tdRect.width ), items,
			} );
		} );
	} );

	/* مقدار لاتین: هر نشانی، شناسه و کلید پوشیده‌ای که متن خانه است در bdi[dir=ltr] */
	const latin = [];
	if ( table ) {
		const walker = document.createTreeWalker( table, NodeFilter.SHOW_TEXT );
		for ( let n = walker.nextNode(); n; n = walker.nextNode() ) {
			const t = n.nodeValue.trim();
			if ( ! t || ! /[A-Za-z0-9•]/.test( t ) || /[؀-ۿ]/.test( t ) ) { continue; }
			if ( n.parentElement.closest( 'th, button, select, script, style' ) ) { continue; }
			const b = n.parentElement.closest( 'bdi' );
			latin.push( { text: t.slice( 0, 50 ), ok: !! b && 'ltr' === b.getAttribute( 'dir' ) } );
		}
	}

	/* left/right ثابت در شیوه‌نامۀ خود صفحه */
	const physical = [];
	document.querySelectorAll( '.tp-services-page style' ).forEach( s => {
		const m = s.textContent.match( /(?:^|[;{\s])(?:margin|padding|border)-(?:left|right)\s*:|(?:^|[;{\s])(?:left|right)\s*:|text-align\s*:\s*(?:left|right)|float\s*:\s*(?:left|right)/g );
		if ( m ) { physical.push( ...m.map( x => x.trim() ) ); }
	} );

	const wrap = document.querySelector( '.tp-services-page' );
	return {
		htmlDir: document.documentElement.dir || 'ltr',
		wrapDir: wrap ? getComputedStyle( wrap ).direction : '',
		pageOverflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
		tableOverflow: scroll ? scroll.scrollWidth - scroll.clientWidth : -1,
		rows: rows.length, cells, latin, physical,
	};
}

/* ستون‌های کوتاه با شمارۀ ستون (از صفر) — همان ترتیب <colgroup> */
const SHORT = [ 'c-on', 'c-status', 'c-ttl', 'c-test' ];

async function run( type, name ) {

	head( 'مرورگر: ' + name );
	const login   = JSON.parse( php( [ 'tests/local-login.php', 'tp_test_admin' ] ) );
	const browser = await type.launch();

	try {
		for ( const [ lang, locale, dir ] of LANGS ) {
			php( [ 'tests/services-admin-state.php', 'set', locale ] );

			for ( const w of WIDTHS ) {
				const ctx  = await browser.newContext( { viewport: { width: w, height: 900 } } );
				await ctx.addCookies( login.cookies );
				const page = await ctx.newPage();
				/*
				 * خطای JS این صفحه (اسکریپت درون‌خطی یا افزونه‌های ما) شکست است؛ خطای افزونۀ
				 * دیگری که در همۀ صفحه‌های پیشخوان هست (۵ اکتبر: abzarwp/localize درون
				 * elementor-pro — React #200) فقط گزارش می‌شود.
				 */
				const errs = [];
				page.on( 'pageerror', e => {
					const s = String( e.stack || e.message );
					const files = s.match( /https?:\/\/[^\s)]+?\.js/g ) || [];
					const plug = files.find( f => /\/plugins\//.test( f ) ) || files[0] || '';
					if ( ! files.length || files.some( f => /topersia-(core|render)/.test( f ) ) ) { errs.push( e.message.slice( 0, 160 ) ); }
					else { foreign.add( e.message.slice( 0, 30 ) + ' @ ' + plug.replace( /^.*\/plugins\//, '' ) ); }
				} );
				const res = await page.goto( PAGE, { waitUntil: 'load' } );
				await page.evaluate( () => document.fonts.ready );

				const tag = name + ' · ' + lang + ' · ' + w;
				check( tag + ' — صفحه باز شد (۲۰۰)', res && 200 === res.status(), res && res.status() );
				fs.mkdirSync( DIR, { recursive: true } );
				await page.screenshot( { path: path.join( DIR, SHOTS + '-' + ( 'کروم' === name ? 'chromium' : 'firefox' ) + '-' + lang + '-' + w + '.png' ), fullPage: true } );

				const p = await page.evaluate( probe );
				const cols = await page.evaluate( () => [ ...document.querySelectorAll( 'table.tp-services col' ) ].map( c => c.className ) );

				check( tag + ' — جهت صفحه همان جهت پیشخوان (' + dir + ')', p.htmlDir === dir && p.wrapDir === dir, 'html ' + p.htmlDir + ' · صفحه ' + p.wrapDir );
				check( tag + ' — صفحه سرریز افقی ندارد', p.pageOverflow <= 0, p.pageOverflow + 'px' );
				check( tag + ' — جدول در پهنای خودش جا می‌شود', p.tableOverflow <= 0, p.tableOverflow + 'px پشت اسکرول' );
				check( tag + ' — سطرهای پیدا', p.rows > 1, p.rows );

				const empty = [], low = [], cut = [], valign = [], align = [];
				for ( const c of p.cells ) {
					const at = c.row + '/' + c.col;
					const vis = c.items.filter( i => i.vis );
					if ( ! vis.length ) { empty.push( at + ( c.items.length ? ' (' + c.items.map( i => i.kind ).join( ',' ) + ')' : '' ) ); }
					vis.filter( i => null !== i.ratio && i.ratio < 4.5 ).forEach( i => low.push( at + ' «' + i.text + '» ' + i.ratio ) );
					vis.filter( i => i.clip ).forEach( i => cut.push( at + ' «' + i.text + '» ' + i.clip ) );
					if ( 'middle' !== c.valign ) { valign.push( at + ' ' + c.valign ); }
					const short = SHORT.includes( cols[ c.col ] );
					const want = short ? 'center' : 'start';
					if ( c.align !== want && ! ( 'start' === want && c.align === ( 'rtl' === dir ? 'right' : 'left' ) ) ) { align.push( at + ' ' + c.align + '≠' + want ); }
				}
				check( tag + ' — هر خانه متن یا کنترل دیدنی دارد', ! empty.length, empty.slice( 0, 6 ).join( ' · ' ) );
				check( tag + ' — کنتراست هر متن ≥ ۴٫۵', ! low.length, low.slice( 0, 6 ).join( ' · ' ) );
				check( tag + ' — بی سرریز پنهان', ! cut.length, cut.slice( 0, 6 ).join( ' · ' ) + ( cut.length > 6 ? ' … (' + cut.length + ')' : '' ) );
				check( tag + ' — همهٔ خانه‌ها vertical-align:middle', ! valign.length, valign.slice( 0, 4 ).join( ' · ' ) + ( valign.length > 4 ? ' … (' + valign.length + ')' : '' ) );
				check( tag + ' — ستون‌های کوتاه وسط، متنی start', ! align.length, align.slice( 0, 4 ).join( ' · ' ) + ( align.length > 4 ? ' … (' + align.length + ')' : '' ) );

				if ( 1440 === w ) {
					const bad = p.latin.filter( l => ! l.ok );
					check( tag + ' — مقدار لاتین در bdi[dir=ltr] (' + p.latin.length + ')', p.latin.length > 0 && ! bad.length, bad.slice( 0, 5 ).map( l => '«' + l.text + '»' ).join( ' · ' ) );
					check( tag + ' — شیوه‌نامۀ صفحه left/right ثابت ندارد', ! p.physical.length, p.physical.slice( 0, 5 ).join( ' · ' ) );
				}
				check( tag + ' — بی خطای JS از این صفحه', ! errs.length, errs.join( ' | ' ) );

				await ctx.close();
			}
		}
	} finally {
		await browser.close();
	}
}

/*
 * «ذخیره» بی هیچ تغییر — نام فیلدها با رادیو و textarea همان ماند؟ پیکربندی مؤثر هر
 * سرویس (و شکاف دومش) پس از ذخیره همان پیش از آن است. گزینۀ خام پیش از آزمون
 * عکس گرفته و در پایان برگردانده می‌شود.
 */
async function saveRoundTrip() {

	head( 'ذخیرۀ بی‌تغییر — رفت‌وبرگشت فرم (کروم)' );
	const login  = JSON.parse( php( [ 'tests/local-login.php', 'tp_test_admin' ] ) );
	const snap   = php( [ 'tests/services-admin-state.php', 'snapshot' ] );
	const before = php( [ 'tests/services-admin-state.php', 'effective' ] );
	const browser = await chromium.launch();
	try {
		const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
		await ctx.addCookies( login.cookies );
		const page = await ctx.newPage();
		const save = async () => {
			await Promise.all( [ page.waitForNavigation( { waitUntil: 'load' } ), page.click( 'input[type=submit][value="ذخیره"]' ) ] );
			return page.locator( '.tp-services-page .notice-info' ).first().innerText().catch( () => '' );
		};
		await page.goto( PAGE, { waitUntil: 'load' } );
		const note  = await save();
		const after = php( [ 'tests/services-admin-state.php', 'effective' ] );
		check( 'پیام «ذخیره شد» بی خطای سطر', /ذخیره شد/.test( note ) && ! /نیست|ناشناخته/.test( note ), note );
		check( 'پیکربندی مؤثر هر سرویس و شکاف دوم پس از ذخیره همان پیش از آن', before === after, after.slice( 0, 200 ) );

		/* رادیو، textarea و عدد واقعاً فرستاده می‌شوند؛ مقدار بلندِ ذخیره‌شده در ۱۰۲۴ تمام پیداست */
		await page.check( 'input[name="services[weather][provider]"][value="open-meteo"]' );
		await page.fill( 'textarea[name="services[ai.tts][model]"]', 'tp-test-model-' + 'x'.repeat( 24 ) );
		await page.fill( 'input[name="services[routing][ttl]"]', '2592000' );
		/* Enter در textarea مثل input فرم را می‌فرستد و خط تازه نمی‌سازد */
		await Promise.all( [ page.waitForNavigation( { waitUntil: 'load' } ), page.press( 'textarea[name="services[ai.tts][model]"]', 'Enter' ) ] );
		const note2 = await page.locator( '.tp-services-page .notice-info' ).first().innerText().catch( () => '' );
		const e2 = JSON.parse( php( [ 'tests/services-admin-state.php', 'effective' ] ) );
		const e0 = JSON.parse( before );
		/* weather#2 مشتق است: پیش‌فرض شکاف دوم «آداپتور دیگرِ» اولی است — حالا MET Norway */
		const touched = [ 'weather', 'weather' + '#2', 'ai.tts', 'routing' ];
		const diff = Object.keys( e0 ).filter( k => ! touched.includes( k ) && JSON.stringify( e0[ k ] ) !== JSON.stringify( e2[ k ] ) );
		check( 'رادیو و textarea و عدد با Enter در textarea ذخیره شدند (هوا ← Open-Meteo، مدل صدا بی خط تازه، TTL مسیریابی) و بقیه دست نخورد',
			/ذخیره شد/.test( note2 ) && 'open-meteo' === e2.weather[0] && 'met-norway' === e2[ 'weather#2' ][0] && 'tp-test-model-' + 'x'.repeat( 24 ) === e2['ai.tts'][3] && 2592000 === e2.routing[5] && ! diff.length,
			JSON.stringify( [ e2.weather[0], e2[ 'weather#2' ][0], e2['ai.tts'][3], e2.routing[5], diff ] ) );
		await page.setViewportSize( { width: 1024, height: 900 } );
		await page.goto( PAGE, { waitUntil: 'load' } );
		const p2  = await page.evaluate( probe );
		const cut = [];
		p2.cells.forEach( c => c.items.filter( i => i.vis && i.clip ).forEach( i => cut.push( c.row + '/' + c.col + ' «' + i.text + '» ' + i.clip ) ) );
		const shownModel = await page.inputValue( 'textarea[name="services[ai.tts][model]"]' );
		check( '۱۰۲۴: مقدار ذخیره‌شدۀ بلند (مدل ۳۸ نویسه، TTL ۲۵۹۲۰۰۰) دیده می‌شود و بریده نیست', ! cut.length && shownModel.length === 38, cut.slice( 0, 4 ).join( ' · ' ) );
	} finally {
		await browser.close();
		execFileSync( PHP, [ 'tests/services-admin-state.php', 'restore' ], { cwd: WS, input: snap, encoding: 'utf8' } );
		check( 'گزینۀ خام سرویس‌ها برگشت', php( [ 'tests/services-admin-state.php', 'snapshot' ] ) === snap );
	}
}

( async () => {

	const was = JSON.parse( php( [ 'tests/services-admin-state.php', 'get' ] ) ).locale;
	try {
		if ( ! ARGS.only || 'chromium' === ARGS.only ) { await run( chromium, 'کروم' ); await saveRoundTrip(); }
		if ( ! ARGS.only || 'firefox' === ARGS.only )  { await run( firefox, 'فایرفاکس' ); }
	} finally {
		php( [ 'tests/services-admin-state.php', 'set', was ] );
		const now = JSON.parse( php( [ 'tests/services-admin-state.php', 'get' ] ) ).locale;
		check( 'زبان پیشخوان مدیر آزمون برگشت (' + ( was || 'پیش‌فرض' ) + ')', now === was, now );
	}

	foreign.forEach( f => console.log( '  ℹ︎ خطای افزونۀ دیگر (نه این صفحه): ' + f ) );
	console.log( '\n  گذشته: ' + pass + '   افتاده: ' + fail );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
