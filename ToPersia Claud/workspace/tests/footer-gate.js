'use strict';
/**
 * گیت پاورقی — دستۀ 53-پ بند ۱۸ و P5-D606، روی نسخۀ محلی (کروم، Playwright از ./pw.js).
 *
 *   node tests/footer-gate.js            # همه
 *   TP_TIMEOUT_X=3 node tests/footer-gate.js
 *
 * برای هر زبان (fa · en · ar) و هر شمار تبلیغ ۰ تا ۴ (جای نمونه در حالت پیش‌نمایش؛ گزینۀ demo_ads موقتاً، در پایان
 * حتی با خطا برمی‌گردد) در ۳۹۰ و ۳۲۰:
 *   ▸ هیچ شورت‌کد خام ([tp_footer… · [html_block) در صفحه
 *   ▸ شمار خانۀ تبلیغ = همان شمار؛ هیچ سرریز افقی (scrollWidth و لبۀ هر عنصر پاورقی)
 * در ۱۴۴۰، هر زبان:
 *   ▸ نمایش آیکون شبکه‌ها (P5-D606): هر پیوند یا SVG با قاب ناصفر دارد، یا <i> با ::before غیرتهی و قلمِ بارگذاری‌شدۀ
 *     همان خانواده؛ حرف جایگزین (tp-ft-social__ch) افتادن است
 *   ▸ کنتراست متن پاورقی ≥ ۴٫۵ و آیکون تک‌رنگ ≥ ۳ در هر سه حالت (light · evening · dark)؛ لوگوی رنگی برند معاف (WCAG ۱٫۴٫۱۱)
 * دستۀ 53-ت (P5-D612 تا P5-D616)، سه زبان × سه حالت:
 *   ▸ بی رنگ‌مایۀ سبز: هیچ زمینه‌ای در پاورقی ۷۵ تا ۱۷۰ درجه نیست (تصویر و آیکون برند معاف)
 *   ▸ کاشی شبکه‌ها و کاشی همکاران هر کدام یک رنگ در هر حالت؛ رنگ هر برند ≥ ۳:۱ روی کاشی همان حالت
 *   ▸ شبکه‌ها [7,7] و همکاران ردیف‌های سه‌تایی از شمار لوگوهای option (۵ ← [3,2]، ۶ ← [3,3]) در ۳۲۰ · ۳۹۰ · ۷۶۸ · ۱۴۴۰، وسط‌چین؛ بلندی ردیف همکاران = بلندی ردیف شبکه‌ها
 *   ▸ عنوان‌ها text-align:start و نویسه‌ها از لبۀ آغاز (fa و ar راست، en چپ)
 *   ▸ جعبۀ برند فقط لوگو + یک بند متن، لوگو در لبۀ آغاز به جهت زبان؛ سرریز افقی در ۳۲۰ · ۳۹۰ · ۷۶۸
 * منوهای پاورقی (P5-D617)، سه زبان در ۱۴۴۰:
 *   ▸ هیچ پیوند نمایشی پوسته (شهرهای دمو، href="#" یا تهی) و هیچ ویجت wd_extra_menu_list
 *   ▸ عنوان هر ستون به زبان صفحه (کش عنصر المنتور برای هر زبان جدا نیست — toggle زنده کشیده می‌شود)
 *   ▸ ستون‌ها و پیوندها همان خروجی topersia_g_ft_menu_url در همان زبان؛ ستون بی پیوند اصلاً نمی‌آید، هیچ toggle تهی
 *   ▸ هر پیوند ۲۰۰ بی تغییر مسیر و پیشوند زبانش همان زبان صفحه؛ برچسب en لاتین، ar بی حرف ویژۀ فارسی
 *   ▸ سمت سرور: اگر نخستین ستون (که شیوه‌نامۀ el-toggle وودمارت را می‌نویسد) پنهان شود، شیوه‌نامه می‌ماند — با جهش
 * رجیستری پاورقی (دستۀ 55-ب بند ۳ · P5-D643)، سه زبان در ۱۴۴۰:
 *   ▸ حداکثر پنج ستون منو، هر ستون حداکثر TOPERSIA_FOOTER_COL_MAX (۸) پیوند؛ گروه‌ها و پیوندها همان سهم سرور (topersia_g_ft_share)
 *   ▸ هر گروهی جز گروه هم‌نام toggle سرعنوان <p> دیدنی بلافاصله پیش از فهرستش و aria-labelledby همان؛ هیچ فهرست تهی
 *   ▸ هیچ پیوند /advertise/ در پاورقی؛ شمار پیوند منو هر زبان در menu-counts.json؛ fa دست‌کم ۳۰
 *   ▸ ۳۹۰، fa: هر toggle منو آکاردئون — بسته تا کلیک، با کلیک پیوندهایش دیدنی
 * عکس هر ترکیب در tests/.screens/footer-gate/ (بیرون از git)؛ «پس» fa در ۱۴۴۰ · ۷۶۸ · ۳۹۰ × سه حالت.
 *
 * کد خروج: ۰ سبز · ۱ افتادن · ۲ محیط ناقص.
 */
const ENV  = require( './env.js' );
const { chromium } = require( './pw.js' );
const { execFileSync } = require( 'child_process' );
const fs   = require( 'fs' );
const path = require( 'path' );

const X     = Math.max( 1, Number( process.env.TP_TIMEOUT_X || 1 ) );
const SHOTS = path.join( __dirname, '.screens', 'footer-gate' );
const LANGS = { fa: '/', en: '/en/', ar: '/ar/' };
const MODES = [ 'light', 'evening', 'dark' ];

if ( 'topersia.local' !== ENV.host ) {
	console.error( 'فقط topersia.local' );
	process.exit( 2 );
}

const php = args => execFileSync( ENV.php, args, { cwd: ENV.workspace, encoding: 'utf8', maxBuffer: 1 << 26 } );
const wp  = code => php( [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + ENV.base, 'eval', code ] );

let fails = 0, passes = 0;
const COUNTS = {};   /* شمار پیوند منو هر زبان (P5-D643) ← SHOTS/menu-counts.json برای گزارش */
const ok  = ( cond, msg ) => { if ( cond ) { passes++; console.log( '  ✅ ' + msg ); } else { fails++; console.log( '  ❌ ' + msg ); } };

/* P5-D615: شبکه‌ها دو ردیف ۷تایی در هر پهنا، همکاران ردیف‌های سه‌تایی (P5-D609)، هر دو وسط؛ بلندی هر ردیف همکاران = بلندی هر ردیف شبکه‌ها.
   شمار همکاران از option (لوگوی دارای تصویر)، نه عدد ثابت: ۵ لوگو ← [3,2]، ۶ ← [3,3]؛ لوگوی گم‌شده در خروجی هم می‌افتد */
let PARTNERS = -1;
const partnerRows = n => Array.from( { length: Math.ceil( n / 3 ) }, ( _, i ) => Math.min( 3, n - 3 * i ) ).join();
const layoutOk = ( L, lang, w ) => {
	const ph = L.partners.heights, sh = L.social.heights;
	const same = 1 === ph.length && 1 === sh.length && Math.abs( ph[0] - sh[0] ) <= 1;
	const want = partnerRows( PARTNERS );
	ok( '7,7' === L.social.rows.join() && L.social.centred && PARTNERS > 0 && want === L.partners.rows.join() && L.partners.centred && 1 === L.partners.sizes.length && same,
		`چیدمان · ${ lang } · ${ w }: شبکه‌ها [${ L.social.rows }] (انتظار [7,7]) وسط ${ L.social.centred }؛ همکاران [${ L.partners.rows }] (انتظار [${ want }] از ${ PARTNERS } لوگو) وسط ${ L.partners.centred } کاشی ${ L.partners.sizes.join( '/' ) }؛ بلندی ردیف همکاران ${ ph.join( '/' ) } = شبکه‌ها ${ sh.join( '/' ) }` );
};
/* P5-D614: عنوان‌ها از لبۀ آغاز به جهت زبان (fa و ar راست، en چپ) */
const headsOk = ( L, lang, w ) => {
	const want = 'en' === lang ? 'ltr' : 'rtl';
	const bad  = L.heads.filter( h => 'start' !== h.ta || h.dir !== want || ! h.atStart );
	ok( L.heads.length >= 2 && ! bad.length, `جهت عنوان · ${ lang } · ${ w }: ${ L.heads.length } عنوان، جهت ${ want }، ناهمخوان ${ bad.length }` + ( bad.length ? ' ← ' + bad.slice( 0, 4 ).map( h => '«' + h.text + '» ' + h.ta + ' ' + h.dir + ' آغاز ' + h.atStart ).join( ' | ' ) : '' ) );
};

/* P5-D617 · P5-D643: عنوان ستون به ترتیب dest · guide · season · about · legal — مستقل از کد، از کاتالوگ بازبینی‌شده */
const MENU_COLS   = [ 'dest', 'guide', 'season', 'about', 'legal' ];
const MENU_TITLES = {
	fa: [ 'مقصدها', 'راهنمای سفر', 'ایران در چهار فصل', 'دربارۀ توپرشیا', 'حقوقی' ],
	en: [ 'Destinations', 'Travel guide', 'Iran in four seasons', 'About ToPersia', 'Legal' ],
	ar: [ 'الوجهات', 'دليل السفر', 'إيران في الفصول الأربعة', 'عن توبيرشيا', 'قانوني' ],
};
const COL_MAX = 8, COLS_MAX = 5, FA_MIN = 30;   /* TOPERSIA_FOOTER_COL_MAX · پنج toggle · هدف «دست‌کم ۳۰ پیوند واقعی در fa» */
/* خروجی سرور در هر زبان: ستون ← { u: نشانی‌ها، g: گروه‌های دارای پیوند } — همان رجیستری، همان سهم topersia_g_ft_share و همان
   برش شورت‌کد، پس از wpml_switch_language. در WP-CLI home_url با تعویض زبان عوض نمی‌شود؛ wpml_permalink همان تبدیل صفحه را
   می‌دهد و روی نشانی ازپیش‌زبان‌دار بی‌اثر است (دستۀ 55 بند ۱۰). */
const menuExpect = lang => JSON.parse( wp( 'do_action( "wpml_switch_language", "' + lang + '" ); $o = array(); foreach ( topersia_g_ft_menu_cols() as $k => $c ) { $l = array(); foreach ( $c[1] as $g ) { $l[ $g ] = topersia_g_ft_links( $g ); } $t = topersia_g_ft_share( array_map( "count", $l ) ); $o[ $k ] = array( "u" => array(), "g" => array() ); foreach ( $l as $g => $ls ) { foreach ( array_slice( $ls, 0, $t[ $g ] ) as $it ) { $o[ $k ]["u"][] = apply_filters( "wpml_permalink", $it[1], "' + lang + '" ); } if ( $t[ $g ] > 0 ) { $o[ $k ]["g"][] = $g; } } } echo wp_json_encode( $o );' ).trim().split( '\n' ).pop() );
/* سمت سرور: ستون dest تهی ساخته می‌شود؛ پاورقی بی آن کشیده می‌شود و شیوه‌نامۀ el-toggle باید بماند. mut = بی بازگرداندن after_render */
const keepCss = mut => JSON.parse( wp( '$mut = ' + ( mut ? 1 : 0 ) + '; add_filter( "elementor/widget/render_content", function ( $h, $w ) { return ( "wd_toggle" === $w->get_name() && "dest" === topersia_g_ft_toggle_menu_col( (array) $w->get_raw_data() ) ) ? str_replace( "tp-ft-menu__a", "tp-ft-menu__x", $h ) : $h; }, 5, 2 ); if ( $mut ) { remove_all_actions( "elementor/frontend/widget/after_render" ); } $o = \\Elementor\\Plugin::$instance->frontend->get_builder_content_for_display( 6128 ); preg_match_all( "#wd-el-toggle-title[^>]*>\\s*([^<]*?)\\s*<#u", $o, $t ); echo wp_json_encode( array( "titles" => $t[1], "css" => preg_match_all( "#<link[^>]+id=\\"wd-el-toggle(-rtl)?-css\\"#", $o ), "raw" => false !== strpos( $o, "[tp_footer_menu" ) || false !== strpos( $o, "[elementor-element" ) ) );' ).trim().split( '\n' ).pop() );

const setDemo = n =>wp( '$o = get_option( "topersia_footer_blocks", array() ); $o = is_array( $o ) ? $o : array(); $o["demo_ads"] = ' + Number( n ) + '; update_option( "topersia_footer_blocks", $o, false );' );

/* ── در صفحه ─────────────────────────────────────────────────────────── */
function probeLayout() {
	const foot = document.querySelector( '[data-elementor-type="footer"]' );
	const html = document.documentElement.innerHTML;
	const vw   = document.documentElement.clientWidth;
	const bad  = [];
	if ( foot ) {
		/* فقط محتوای دیدنی (متن، تصویر، آیکون، پیوند) — ظرف‌های تمام‌عرض وودمارت (wd-section-stretch) عمداً با حاشیۀ منفی
		   از لبه بیرون می‌زنند و بریده می‌شوند؛ سرریز واقعی را scrollWidth سند و لبۀ همین برگ‌ها می‌گیرد. */
		/* لبۀ دیدنی: برای عنصر متنی گسترۀ خود نویسه‌ها (Range)، نه جعبه — ظرف wd-section-stretch وودمارت 100vw است و در
		   کروم بی‌سر با نوار اسکرول کلاسیک ۱۵px از سند پهن‌تر، ولی متن وسط‌چین درونش بریده نمی‌شود. مرز = پهنای سند. */
		const edge = Math.min( vw, document.documentElement.scrollWidth );
		foot.querySelectorAll( 'img, svg, i, a, h1, h2, h3, h4, p, li, span, input, button' ).forEach( el => {
			let r = el.getBoundingClientRect();
			if ( ! r.width || ! r.height || 'hidden' === getComputedStyle( el ).visibility || el.closest( 'svg' ) !== el && el.closest( 'svg' ) ) { return; }
			if ( [ ...el.childNodes ].some( n => 3 === n.nodeType && n.textContent.trim() ) && ! el.querySelector( 'img, svg, i' ) ) { const g = document.createRange(); g.selectNodeContents( el ); r = g.getBoundingClientRect(); }
			if ( r.right > edge + 1 || r.left < -1 ) { bad.push( el.tagName.toLowerCase() + '.' + ( 'string' === typeof el.className ? el.className : '' ) + ' < ' + String( el.parentElement ? el.parentElement.className : '' ).slice( 0, 60 ) + ' ' + Math.round( r.left ) + '…' + Math.round( r.right ) + ' «' + ( el.textContent || '' ).trim().slice( 0, 20 ) + '»' ); }
		} );
	}
	/* چیدمان همکاران و شبکه‌ها (P5-D609 · P5-D610): شمار هر ردیف و وسط‌چینی (فاصلۀ دو سوی ردیف از ظرف ≤ ۲px) */
	const rowsOf = sel => {
		const box = foot ? foot.querySelector( sel ) : null;
		if ( ! box ) { return { rows: [], centred: false, sizes: [] }; }
		const B = box.getBoundingClientRect(), by = {};
		[ ...box.children ].forEach( c => { const r = c.getBoundingClientRect(); const k = Math.round( r.top ); ( by[ k ] = by[ k ] || [] ).push( r ); } );
		const rows = Object.keys( by ).sort( ( a, b ) => a - b ).map( k => by[ k ] );
		const centred = rows.every( rs => { const l = Math.min( ...rs.map( r => r.left ) ) - B.left, rr = B.right - Math.max( ...rs.map( r => r.right ) ); return Math.abs( l - rr ) <= 2; } );
		return { rows: rows.map( rs => rs.length ), centred, sizes: [ ...new Set( rows.flat().map( r => Math.round( r.width ) + '×' + Math.round( r.height ) ) ) ], heights: [ ...new Set( rows.flat().map( r => Math.round( r.height ) ) ) ] };
	};
	/* P5-D614 جهت عنوان: هر عنوان دیدنی پاورقی text-align:start و نویسه‌هایش از لبۀ آغاز جعبه (راست در rtl، چپ در ltr) */
	const heads = foot ? [ ...foot.querySelectorAll( '.tp-ft-h, .wd-el-toggle-title' ) ].filter( h => h.getBoundingClientRect().width > 0 ).map( h => {
		const cs = getComputedStyle( h ), B = h.getBoundingClientRect();
		const tn = [ ...h.childNodes ].find( n => 3 === n.nodeType && n.textContent.trim() );
		if ( ! tn ) { return { text: h.textContent.trim().slice( 0, 24 ), ta: cs.textAlign, dir: cs.direction, atStart: false }; }
		const g = document.createRange(); g.selectNodeContents( tn ); const T = g.getBoundingClientRect();
		const atStart = 'rtl' === cs.direction ? Math.abs( B.right - parseFloat( cs.paddingRight ) - parseFloat( cs.borderRightWidth ) - T.right ) <= 3 : Math.abs( T.left - B.left - parseFloat( cs.paddingLeft ) - parseFloat( cs.borderLeftWidth ) ) <= 3;
		return { text: tn.textContent.trim().slice( 0, 24 ), ta: cs.textAlign, dir: cs.direction, atStart };
	} ) : [];
	/* P5-D616 جعبۀ برند: فقط لوگو و یک بند متن؛ لوگو در لبۀ آغاز (به جهت زبان) */
	const bx = foot ? foot.querySelector( '.tp-ft-brand' ) : null;
	let brand = null;
	if ( bx && bx.getBoundingClientRect().width > 0 ) {
		const B = bx.getBoundingClientRect(), lg = bx.querySelector( '.tp-ft-brand__logo' ), dir = getComputedStyle( bx ).direction;
		const R = lg ? lg.getBoundingClientRect() : null;
		brand = { kids: [ ...bx.children ].map( c => c.className ).join( ' + ' ), p: bx.querySelectorAll( 'p' ).length, name: !! bx.querySelector( '.tp-ft-brand__name' ), dir,
			logoStart: R ? ( 'rtl' === dir ? Math.abs( B.right - R.right ) <= 2 : Math.abs( R.left - B.left ) <= 2 ) : false, ta: getComputedStyle( bx ).textAlign };
	}
	return {
		heads,
		brand,
		partners: rowsOf( '.tp-ft-partners .tp-ft-row' ),
		social:   rowsOf( '.tp-ft-social__grid' ),
		foot:   !! foot,
		raw:    /\[tp_footer|\[html_block/.test( html ),
		scroll: document.documentElement.scrollWidth - vw,
		ads:    foot ? foot.querySelectorAll( '.tp-ft-ad' ).length : -1,
		parts:  foot ? [ 'tp-ft-brand', 'tp-ft-partners', 'tp-ft-social' ].filter( c => foot.querySelector( '.' + c ) ) : [],
		bad:    bad.slice( 0, 6 ),
	};
}

/* P5-D617 منوها: هر toggle پاورقی — عنوان، منو، پیوندها، پُری؛ و هر رد پیوند نمایشی پوسته */
function probeMenus() {
	const foot = document.querySelector( '[data-elementor-type="footer"]' );
	if ( ! foot ) { return null; }
	const DEMO = /New York|London|San Francisco|Cockfosters|Las Vegas|Los Angeles|Chicago|Paris|Berlin/i;
	const toggles = [ ...foot.querySelectorAll( '.wd-el-toggle' ) ].map( t => {
		const nav  = t.querySelector( 'nav.tp-ft-menu' );
		const body = t.querySelector( '.wd-el-toggle-content-inner' );
		return {
			title:  ( ( t.querySelector( '.wd-el-toggle-title' ) || {} ).textContent || '' ).trim(),
			menu:   !! nav,
			label:  nav ? nav.getAttribute( 'aria-label' ) : '',
			links:  nav ? [ ...nav.querySelectorAll( 'a.tp-ft-menu__a' ) ].map( a => ( { href: a.href, text: a.textContent.trim() } ) ) : [],
			filled: !! ( body && body.querySelector( 'a[href], img, p, li' ) ),
			brand:  !! t.querySelector( '.tp-ft-brand' ),
			/* P5-D643: هر فهرست گروه — شمار پیوند، و سرعنوانش (aria-labelledby ← p.tp-ft-menu__h دیدنی، درست پیش از فهرست) */
			groups: nav ? [ ...nav.querySelectorAll( 'ul.tp-ft-menu__list' ) ].map( u => {
				const id = u.getAttribute( 'aria-labelledby' ) || '';
				const h  = id ? nav.querySelector( '#' + CSS.escape( id ) ) : null;
				return { g: u.getAttribute( 'data-tp-ft-group' ) || '', n: u.querySelectorAll( 'a.tp-ft-menu__a' ).length, id, head: h ? h.textContent.trim() : '',
					headOk: ! id || ( !! h && h.matches( 'p.tp-ft-menu__h' ) && /^tp-/.test( id ) && h.nextElementSibling === u && '' !== h.textContent.trim() && 'none' !== getComputedStyle( h ).display ) };
			} ) : [],
			heads:  nav ? nav.querySelectorAll( 'p.tp-ft-menu__h' ).length : 0,
		};
	} );
	return {
		toggles,
		advert: [ ...foot.querySelectorAll( 'a[href]' ) ].filter( a => /\/advertise\/?$/.test( new URL( a.href, location.href ).pathname ) ).map( a => a.textContent.trim().slice( 0, 24 ) ),
		demo:   [ ...foot.querySelectorAll( '.wd-el-toggle a' ) ].filter( a => DEMO.test( a.textContent ) || ! a.getAttribute( 'href' ) || '#' === a.getAttribute( 'href' ).trim() ).map( a => '«' + a.textContent.trim().slice( 0, 24 ) + '» ' + a.getAttribute( 'href' ) ),
		text:   DEMO.test( foot.textContent ),
		legacy: foot.querySelectorAll( '[data-widget_type^="wd_extra_menu_list"]' ).length,
	};
}

async function probeIcons() {
	await document.fonts.ready;
	const loaded = [ ...document.fonts ].filter( f => 'loaded' === f.status ).map( f => f.family.replace( /["']/g, '' ) );
	const out = [];
	document.querySelectorAll( '[data-elementor-type="footer"] .tp-ft-social__a' ).forEach( a => {
		const name = a.getAttribute( 'aria-label' ) || a.href;
		const svg  = a.querySelector( 'svg' );
		const i    = a.querySelector( 'i' );
		if ( a.querySelector( '.tp-ft-social__ch' ) ) { out.push( { name, ok: false, why: 'حرف جایگزین' } ); return; }
		if ( svg ) {
			const r = svg.getBoundingClientRect();
			const shapes = svg.querySelectorAll( 'path,polygon,circle,use' ).length;
			out.push( { name, ok: r.width >= 12 && r.height >= 12 && shapes > 0, why: 'svg ' + Math.round( r.width ) + '×' + Math.round( r.height ) + ' شکل ' + shapes, brand: svg.classList.contains( 'tp-ft-social__svg--brand' ) } );
			return;
		}
		if ( i ) {
			const b   = getComputedStyle( i, '::before' );
			const fam = b.fontFamily.split( ',' )[0].replace( /["']/g, '' ).trim();
			const r   = i.getBoundingClientRect();
			const has = '' !== b.content && 'none' !== b.content && 'normal' !== b.content;
			out.push( { name, ok: has && loaded.includes( fam ) && r.width > 0, why: 'i ' + fam + ( loaded.includes( fam ) ? ' بارگذاری‌شده' : ' بارگذاری‌نشده' ) + ' content=' + b.content } );
			return;
		}
		out.push( { name, ok: false, why: 'بی آیکون' } );
	} );
	/* رنگ برند و دو ردیف هم‌شمار (خواست کاربر، دستۀ 53-پ) */
	const as   = [ ...document.querySelectorAll( '[data-elementor-type="footer"] .tp-ft-social__a' ) ];
	const tops = {};
	as.forEach( a => { const t = Math.round( a.getBoundingClientRect().top ); tops[ t ] = ( tops[ t ] || 0 ) + 1; } );
	const fill = as.filter( a => { const cs = getComputedStyle( a ); return a.classList.contains( 'tp-ft-social__a--brand' ) && ( 'none' !== cs.backgroundImage || ! /rgba\(0, 0, 0, 0\)|transparent/.test( cs.backgroundColor ) ); } ).length;
	const logos = [ ...document.querySelectorAll( '[data-elementor-type="footer"] .tp-ft-logo' ) ].map( l => Math.round( l.getBoundingClientRect().height ) );
	return { icons: out, rows: Object.values( tops ), fill, total: as.length, logoH: logos };
}

async function probeContrast( mode ) {
	document.documentElement.setAttribute( 'data-tp-mode', mode );
	await new Promise( r => setTimeout( r, 900 ) );
	/* رنگ محاسبه‌شده: rgb()/rgba() یا color(srgb r g b / a) — color-mix (حالت سوم) به شکل دوم برمی‌گردد */
	const rgb  = s => {
		s = String( s );
		let m = s.match( /rgba?\(([^)]+)\)/ );
		if ( m ) { const a = m[1].split( /[\s,/]+/ ).filter( Boolean ).map( parseFloat ); return { r: a[0], g: a[1], b: a[2], a: a.length > 3 ? a[3] : 1 }; }
		m = s.match( /color\(srgb\s+([^)]+)\)/ );
		if ( m ) { const a = m[1].split( /[\s/]+/ ).filter( Boolean ).map( parseFloat ); return { r: a[0] * 255, g: a[1] * 255, b: a[2] * 255, a: a.length > 3 ? a[3] : 1 }; }
		return null;
	};
	const lum  = c => { const f = v => { v /= 255; return v <= .03928 ? v / 12.92 : Math.pow( ( v + .055 ) / 1.055, 2.4 ); }; return .2126 * f( c.r ) + .7152 * f( c.g ) + .0722 * f( c.b ); };
	const over = ( t, u ) => ( { r: t.r * t.a + u.r * ( 1 - t.a ), g: t.g * t.a + u.g * ( 1 - t.a ), b: t.b * t.a + u.b * ( 1 - t.a ), a: 1 } );
	const bgOf = el => { const st = []; for ( let e = el; e; e = e.parentElement ) { const c = rgb( getComputedStyle( e ).backgroundColor ); if ( c && c.a > 0 ) { st.push( c ); if ( c.a >= 1 ) { break; } } } let base = { r: 255, g: 255, b: 255, a: 1 }; for ( let k = st.length - 1; k >= 0; k-- ) { base = over( st[ k ], base ); } return base; };
	const ratio = ( a, b ) => { const L1 = lum( a ), L2 = lum( b ); return ( Math.max( L1, L2 ) + .05 ) / ( Math.min( L1, L2 ) + .05 ); };
	const foot = document.querySelector( '[data-elementor-type="footer"]' );
	const rows = [];
	/* P5-D612: هیچ زمینه‌ای در پاورقی رنگ‌مایۀ سبز (۷۵ تا ۱۷۰ درجه) ندارد — خود پاورقی و هر گره؛ تصویر (لوگو، تبلیغ) و
	   آیکون برند (svg، i) معاف. رنگ‌مایه فقط وقتی معنا دارد که رنگ خاکستری محض نباشد (اختلاف بیشینه و کمینه ≥ ۱٪) */
	const hueOf = c => { const r = c.r / 255, g = c.g / 255, b = c.b / 255, mx = Math.max( r, g, b ), d = mx - Math.min( r, g, b ); if ( d < .01 ) { return -1; } let h = mx === r ? ( ( g - b ) / d ) % 6 : mx === g ? ( b - r ) / d + 2 : ( r - g ) / d + 4; return ( h * 60 + 360 ) % 360; };
	const green = [];
	[ foot, ...foot.querySelectorAll( '*' ) ].forEach( el => {
		if ( el.closest( 'svg' ) || el.matches( 'img, i, picture, video' ) ) { return; }
		const c = rgb( getComputedStyle( el ).backgroundColor );
		if ( ! c || c.a <= 0 ) { return; }
		const h = hueOf( c );
		if ( h >= 75 && h <= 170 ) { green.push( ( 'string' === typeof el.className ? el.className.split( ' ' )[0] : el.tagName ) + ' ' + getComputedStyle( el ).backgroundColor + ' ' + Math.round( h ) + '°' ); }
	} );
	/* P5-D613: کاشی شبکه‌ها در هر حالت یک زمینه و یک قاب */
	const socTiles = [ ...new Set( [ ...foot.querySelectorAll( '.tp-ft-social__a' ) ].map( a => { const cs = getComputedStyle( a ); return cs.backgroundColor + ' / ' + cs.borderTopColor; } ) ) ];
	rows.push( { kind: 'meta', green, socTiles, ratio: 99, min: 0 } );
	foot.querySelectorAll( '*' ).forEach( el => {
		const r = el.getBoundingClientRect();
		if ( ! r.width || ! r.height ) { return; }
		const txt = [ ...el.childNodes ].filter( n => 3 === n.nodeType && n.textContent.trim() ).map( n => n.textContent.trim() ).join( ' ' );
		const B = bgOf( el );
		const tag = el.closest( 'h1,h2,h3,h4,.elementor-heading-title,.tp-ft-h' ) ? 'عنوان' : el.closest( '.tp-ft-brand' ) ? 'متن برند' : el.closest( '.tp-ft-ads' ) ? 'تبلیغ' : el.closest( 'a' ) ? 'پیوند' : 'متن';
		if ( txt ) {
			const fg = over( rgb( getComputedStyle( el ).color ), B );
			rows.push( { kind: 'text', tag, sample: txt.slice( 0, 28 ), ratio: ratio( fg, B ), min: 4.5 } );
		} else if ( el.matches( '.tp-ft-social__a--brand' ) ) {   /* P5-D610: رنگ برند روی کاشی خودش ≥ ۳:۱ */
			const fg = rgb( getComputedStyle( el ).color ), T = bgOf( el );
			rows.push( { kind: 'icon', tag: 'شبکه', sample: el.getAttribute( 'aria-label' ) || '', ratio: ratio( fg, T ), min: 3 } );
		} else if ( el.matches( '.tp-ft-logo' ) ) {   /* کاشی همکار: یکسان در هر سه حالت */
			rows.push( { kind: 'tile', tag: 'کاشی', sample: getComputedStyle( el ).backgroundColor, ratio: 99, min: 0 } );
		} else if ( el.matches( '.tp-ft-social__a:not(.tp-ft-social__a--brand) > i, .tp-ft-social__a:not(.tp-ft-social__a--brand) > svg:not(.tp-ft-social__svg--brand)' ) ) {   /* آیکون رنگ برند = نشان تجاری، معاف (WCAG ۱٫۴٫۱۱) */
			const fg = over( rgb( getComputedStyle( el ).color ), B );
			rows.push( { kind: 'icon', sample: el.parentElement.getAttribute( 'aria-label' ) || '', ratio: ratio( fg, B ), min: 3 } );
		}
	} );
	return rows.map( r => ( { ...r, ratio: Math.round( r.ratio * 100 ) / 100 } ) );
}

/* ── راننده ──────────────────────────────────────────────────────────── */
( async () => {
	let login, prev;
	try {
		login = JSON.parse( php( [ 'tests/local-login.php' ] ) );
		prev  = wp( '$o = get_option( "topersia_footer_blocks", array() ); echo isset( $o["demo_ads"] ) ? (int) $o["demo_ads"] : 2;' ).trim();
		PARTNERS = Number( wp( 'echo count( array_filter( topersia_g_ft_opts()["partners"], function ( $p ) { return $p["img"] > 0; } ) );' ).trim() );
	} catch ( e ) {
		console.error( 'محیط ناقص: ' + e.message );
		process.exit( 2 );
	}
	fs.mkdirSync( SHOTS, { recursive: true } );
	const browser = await chromium.launch();
	const ctx     = await browser.newContext();
	await ctx.addCookies( login.cookies );
	const page = await ctx.newPage();
	const go   = async ( url, w ) => {
		await page.setViewportSize( { width: w, height: 900 } );
		const res = await page.goto( url, { waitUntil: 'load', timeout: 180000 * X } );
		await page.waitForTimeout( 800 * X );
		return res ? res.status() : 0;
	};
	/* عکس پاورقی: پایین صفحه، صبر تا آرام‌شدن بارگذاری تنبل، سربرگ چسبان فقط هنگام عکس پنهان */
	const shot = async file => {
		await page.evaluate( () => window.scrollTo( { top: document.documentElement.scrollHeight, behavior: 'instant' } ) );
		await page.waitForTimeout( 1500 * X );
		await page.evaluate( () => window.scrollTo( { top: document.documentElement.scrollHeight, behavior: 'instant' } ) );
		/* هر عنصر fixed/sticky بیرون از پاورقی (سربرگ، نوار مدیر، ناوبری بخش‌ها) فقط هنگام عکس display:none و سپس برمی‌گردد */
		await page.evaluate( () => { const foot = document.querySelector( '[data-elementor-type="footer"]' ); document.querySelectorAll( 'body *' ).forEach( e => { if ( foot && foot.contains( e ) ) { return; } const p = getComputedStyle( e ).position; if ( 'fixed' === p || 'sticky' === p ) { e.setAttribute( 'data-tp-shot-hide', e.style.getPropertyValue( 'display' ) + '|' + e.style.getPropertyPriority( 'display' ) ); e.style.setProperty( 'display', 'none', 'important' ); } } ); } );
		const f = await page.$( '[data-elementor-type="footer"]' );
		if ( f ) { await f.screenshot( { path: file } ); }
		await page.evaluate( () => document.querySelectorAll( '[data-tp-shot-hide]' ).forEach( e => { const [ v, p ] = e.getAttribute( 'data-tp-shot-hide' ).split( '|' ); if ( v ) { e.style.setProperty( 'display', v, p ); } else { e.style.removeProperty( 'display' ); } e.removeAttribute( 'data-tp-shot-hide' ); } ) );
	};
	console.log( 'START footer-gate ' + new Date().toISOString() );
	try {
		/* ۰) سمت سرور (P5-D617) — ستون نخست تهی: بی آن کشیده می‌شود و شیوه‌نامۀ el-toggle وودمارت می‌ماند؛ جهش بی after_render می‌افتد */
		const K0 = keepCss( false ), K1 = keepCss( true );
		ok( 1 === K0.css && ! K0.raw && ! K0.titles.includes( MENU_TITLES.fa[0] ) && K0.titles.includes( MENU_TITLES.fa[ MENU_TITLES.fa.length - 1 ] ),
			`ستون نخست تهی (سرور): عنوان‌ها [${ K0.titles.join( '، ' ) }] بی «${ MENU_TITLES.fa[0] }»، شیوه‌نامۀ el-toggle ${ K0.css } (انتظار ۱)، شورت‌کد خام ${ K0.raw }` );
		ok( 0 === K1.css, `جهش — بی بازگرداندن شیوه‌نامۀ ستون پنهان، شیوه‌نامه ${ K1.css } (انتظار ۰؛ پس آزمون بالا تمیز می‌دهد)` );

		/* ۱) سرریز و شمار تبلیغ — ۰ تا ۴، سه زبان، ۳۹۰ و ۳۲۰ */
		for ( let n = 0; n <= 4; n++ ) {
			setDemo( n );
			for ( const [ lang, p ] of Object.entries( LANGS ) ) {
				for ( const w of [ 390, 320, 768 ] ) {
					const st = await go( ENV.base + p, w );
					const L  = await page.evaluate( probeLayout );
					ok( 200 === st && L.foot && ! L.raw && L.ads === n && 3 === L.parts.length && L.scroll <= 1 && ! L.bad.length,
						`تبلیغ ${ n } · ${ lang } · ${ w }: وضعیت ${ st }، تبلیغ ${ L.ads }، بخش‌ها ${ L.parts.length }/۳، بی شورت‌کد خام ${ ! L.raw }، سرریز ${ L.scroll }px` + ( L.bad.length ? ' ← ' + L.bad.join( ' | ' ) : '' ) );
					if ( 2 === n ) {   /* P5-D609 · P5-D615 — یک بار برای هر زبان و پهنا */
						layoutOk( L, lang, w );
						headsOk( L, lang, w );
					}
					if ( 'fa' === lang && ( 1 === n || 4 === n ) ) {
						await shot( path.join( SHOTS, `fa-${ w }-ads${ n }.png` ) );
					}
				}
			}
		}
		setDemo( prev );

		/* ۲) آیکون‌ها و کنتراست — ۱۴۴۰ */
		const table = {};
		for ( const [ lang, p ] of Object.entries( LANGS ) ) {
			await go( ENV.base + p, 1440 );
			const L = await page.evaluate( probeLayout );
			layoutOk( L, lang, 1440 );
			headsOk( L, lang, 1440 );
			const wantDir = 'en' === lang ? 'ltr' : 'rtl';
			ok( !! L.brand && ! L.brand.name && 1 === L.brand.p && /^tp-ft-brand__home \+ tp-ft-brand__text$/.test( L.brand.kids ) && L.brand.dir === wantDir && L.brand.logoStart && 'start' === L.brand.ta,
				`جعبۀ برند · ${ lang } (P5-D616): ${ L.brand ? 'فرزندان «' + L.brand.kids + '»، بند ' + L.brand.p + '، نام زیر لوگو ' + L.brand.name + '، جهت ' + L.brand.dir + ' (انتظار ' + wantDir + ')، لوگو در لبۀ آغاز ' + L.brand.logoStart + '، text-align ' + L.brand.ta : 'دیده نشد' }` );
			/* P5-D617 منوها — عنوان به زبان صفحه، همان پیوندهای سرور، ستون تهی نه، پیوند نمایشی نه، هر پیوند ۲۰۰ و هم‌زبان */
			const M     = await page.evaluate( probeMenus );
			const exp   = menuExpect( lang );
			const T     = MENU_TITLES[ lang ];
			const menus = M ? M.toggles.filter( t => t.menu ) : [];
			const want  = MENU_COLS.filter( c => exp[ c ] && exp[ c ].u.length );
			const rows  = want.map( ( c, i ) => {
				const t = menus[ i ] || { title: '', label: '', links: [], groups: [] }, wt = T[ MENU_COLS.indexOf( c ) ];
				return { c, ok: t.title === wt && t.label === wt && JSON.stringify( t.links.map( l => l.href ) ) === JSON.stringify( exp[ c ].u ) && JSON.stringify( t.groups.map( g => g.g ) ) === JSON.stringify( exp[ c ].g ),
					got: t.title + ' (' + t.links.length + ' · ' + t.groups.map( g => g.g ).join( '+' ) + ')', wt: wt + ' (' + exp[ c ].u.length + ' · ' + exp[ c ].g.join( '+' ) + ')', n: exp[ c ].u.length };
			} );
			/* P5-D643 — سقف ستون و پیوند، سرعنوان گروه، فهرست تهی نه، /advertise/ نه؛ گروه هم‌نام toggle بی سرعنوان، بقیه با سرعنوان */
			const grpBad = menus.flatMap( t => t.groups.filter( g => ! g.headOk || ! g.n || ( g.id ? g.head === t.title : false ) ).map( g => t.title + '/' + g.g + ( g.n ? '' : ' تهی' ) + ( g.headOk ? '' : ' سرعنوان ' + ( g.id || '—' ) ) ) );
			const headN  = menus.reduce( ( s, t ) => s + t.groups.filter( g => g.id ).length, 0 );
			ok( M && menus.length <= COLS_MAX && menus.every( t => t.links.length <= COL_MAX ) && ! grpBad.length && menus.every( t => t.heads === t.groups.filter( g => g.id ).length ) && ! M.advert.length,
				`رجیستری · ${ lang } (P5-D643): ${ menus.length } ستون (≤ ${ COLS_MAX })، پیوند هر ستون [${ menus.map( t => t.links.length ).join( '، ' ) }] (≤ ${ COL_MAX })، ${ headN } سرعنوان گروه با aria-labelledby، ناهمخوان ${ grpBad.length }، پیوند /advertise/ ${ M ? M.advert.length : '—' }` + ( grpBad.length ? ' ← ' + grpBad.slice( 0, 5 ).join( ' | ' ) : '' ) );
			const total  = menus.reduce( ( s, t ) => s + t.links.length, 0 );
			COUNTS[ lang ] = { total, cols: Object.fromEntries( menus.map( t => [ t.title, t.links.length ] ) ), groups: Object.fromEntries( menus.flatMap( t => t.groups.map( g => [ g.g, g.n ] ) ) ) };
			if ( 'fa' === lang ) {
				ok( total >= FA_MIN, `شمار پیوند واقعی منو · fa: ${ total } (هدف ≥ ${ FA_MIN })` );
			} else {
				console.log( `     شمار پیوند منو · ${ lang }: ${ total } (با ترجمۀ بیشتر خودکار رشد می‌کند)` );
			}
			ok( M && ! M.demo.length && ! M.text && 0 === M.legacy, `منو · ${ lang }: بی پیوند نمایشی (${ M ? M.demo.length : '—' }${ M && M.demo.length ? ' ← ' + M.demo.slice( 0, 4 ).join( ' | ' ) : '' })، بی متن شهرهای دمو ${ M ? ! M.text : '—' }، ویجت wd_extra_menu_list ${ M ? M.legacy : '—' }` );
			ok( menus.length === want.length && rows.every( r => r.ok ),
				`منو · ${ lang }: ستون‌ها [${ rows.map( r => r.wt + ' ' + r.n ).join( '، ' ) }] = صفحه [${ menus.map( t => t.title + ' ' + t.links.length ).join( '، ' ) }]` + ( rows.some( r => ! r.ok ) ? ' ← ناهمخوان ' + rows.filter( r => ! r.ok ).map( r => r.c + ': ' + r.got + ' ≠ ' + r.wt ).join( ' | ' ) : '' ) );
			const others = M ? M.toggles.filter( t => ! t.menu ) : [];
			/* دستۀ 55 بند ۱۰: جعبۀ برند از محتوایش (.tp-ft-brand) شناخته می‌شود، نه از عنوان — عنوانش انتخاب کاربر است و در ذخیرۀ
			   #6128 تهی شد؛ فقط نباید هم‌نام ستون «دربارۀ توپرشیا» باشد (خواستۀ بند ۱۰: دوتایی نشود). */
			const tAbout = T[ MENU_COLS.indexOf( 'about' ) ];
			ok( M && M.toggles.every( t => t.filled ) && 1 === others.length && others.every( t => t.brand && t.title !== tAbout ),
				`منو · ${ lang }: هیچ toggle تهی (${ M ? M.toggles.filter( t => ! t.filled ).map( t => '«' + t.title + '»' ).join( ' ' ) || 'هیچ' : '—' })؛ toggle بی منو فقط یک جعبۀ برند، نه هم‌نام «${ tAbout }» (${ others.map( t => '«' + t.title + '» برند ' + t.brand ).join( ' | ' ) })` );
			const script = 'en' === lang ? /^[\x20-\x7E]+$/ : /[؀-ۿ]/;
			const links  = menus.flatMap( t => t.links.map( l => ( { ...l, col: t.title } ) ) );
			const badL   = [];
			for ( const l of links ) {
				const u  = new URL( l.href );
				const r  = await ctx.request.get( l.href, { maxRedirects: 0, timeout: 120000 * X } );
				const pf = 'fa' === lang ? ! /^\/(en|ar)\//.test( u.pathname ) : u.pathname.startsWith( '/' + lang + '/' );
				const tx = script.test( l.text ) && ! ( 'ar' === lang && /[پچژگکی]/.test( l.text ) );
				if ( 200 !== r.status() || ! pf || ENV.host !== u.hostname || ! tx ) { badL.push( l.text + ' ' + u.pathname + ' ' + r.status() + ( pf ? '' : ' پیشوند' ) + ( tx ? '' : ' خط' ) ); }
			}
			ok( links.length > 0 && ! badL.length, `پیوند منو · ${ lang }: ${ links.length } پیوند، هر کدام ۲۰۰ بی تغییر مسیر، پیشوند ${ 'fa' === lang ? 'بی /en/ و /ar/' : '/' + lang + '/' }، برچسب به خط زبان` + ( badL.length ? ' ← ' + badL.join( ' | ' ) : '' ) );
			console.log( '     پیوندهای نهایی ' + lang + ': ' + menus.map( t => t.title + ' ← ' + t.links.map( l => l.text + ' ' + new URL( l.href ).pathname ).join( '، ' ) ).join( ' ‖ ' ) );

			const P     = await page.evaluate( probeIcons );
			const icons = P.icons;
			const badI  = icons.filter( x => ! x.ok );
			ok( icons.length >= 14 && ! badI.length, `آیکون شبکه‌ها · ${ lang }: ${ icons.length } پیوند، ${ icons.filter( x => x.brand ).length } لوگوی SVG رنگی، نمایش‌نیافته ${ badI.length }` + ( badI.length ? ' ← ' + badI.map( x => x.name + ' (' + x.why + ')' ).join( ' | ' ) : '' ) );
			ok( P.fill === P.total && 2 === P.rows.length && Math.abs( P.rows[0] - P.rows[1] ) <= 1, `شبکه‌ها · ${ lang }: رنگ برند ${ P.fill }/${ P.total }، ردیف‌ها [${ P.rows.join( '، ' ) }]` );
			ok( PARTNERS === P.logoH.length && P.logoH.every( h => h <= 48 ) && 1 === new Set( P.logoH ).size, `همکاران · ${ lang } · ۱۴۴۰: بلندی کاشی‌ها [${ P.logoH.join( '، ' ) }] یکسان و ≤ ۴۸ (کوچک‌تر از ۱۰۴ پیشین — P5-D615)` );
			for ( const mode of MODES ) {
				const rows = await page.evaluate( probeContrast, mode );
				table[ lang + ' · ' + mode ] = rows;
				const low  = rows.filter( r => r.ratio < r.min );
				const tiles = [ ...new Set( rows.filter( r => 'tile' === r.kind ).map( r => r.sample ) ) ];
				ok( 1 === tiles.length, `کاشی همکاران · ${ lang } · ${ mode }: یک رنگ (${ tiles.join( ' / ' ) })` );
				const meta = rows.find( r => 'meta' === r.kind ) || { green: [ 'بی سنجه' ], socTiles: [] };
				ok( ! meta.green.length, `بی رنگ‌مایۀ سبز · ${ lang } · ${ mode } (P5-D612): زمینه‌های ۷۵ تا ۱۷۰ درجه ${ meta.green.length }` + ( meta.green.length ? ' ← ' + meta.green.slice( 0, 5 ).join( ' | ' ) : '' ) );
				ok( 1 === meta.socTiles.length, `کاشی شبکه‌ها · ${ lang } · ${ mode } (P5-D613): یک زمینه و قاب (${ meta.socTiles.join( ' ، ' ) })` );
				const minT = Math.min( ...rows.filter( r => 'text' === r.kind ).map( r => r.ratio ) );
				const ic   = rows.filter( r => 'icon' === r.kind ).map( r => r.ratio );
				const minI = ic.length ? Math.min( ...ic ) : '— (همه رنگ برند)';
				ok( rows.length > 0 && ! low.length, `کنتراست پاورقی · ${ lang } · ${ mode }: ${ rows.length } مورد، کمینۀ متن ${ minT }، کمینۀ آیکون ${ minI }` + ( low.length ? ' ← ' + low.slice( 0, 5 ).map( r => r.kind + ' «' + r.sample + '» ' + r.ratio ).join( ' | ' ) : '' ) );
				if ( 'fa' === lang ) {
					await shot( path.join( SHOTS, `fa-1440-${ mode }.png` ) );
				}
			}
		}
		fs.writeFileSync( path.join( SHOTS, 'contrast.json' ), JSON.stringify( table, null, 1 ) );
		fs.writeFileSync( path.join( SHOTS, 'menu-counts.json' ), JSON.stringify( COUNTS, null, 1 ) );
		console.log( '     شمار پیوند منو: ' + Object.entries( COUNTS ).map( ( [ l, c ] ) => l + ' ' + c.total ).join( ' · ' ) );

		/* ۲ب) موبایل آکاردئون (P5-D643) — fa در ۳۹۰: هر toggle منو (wd-state-closed-sm) بسته تا کلیک؛ کلیک سر ← همۀ پیوندهایش دیدنی */
		await go( ENV.base + '/', 390 );
		const acc = () => page.evaluate( () => [ ...document.querySelectorAll( '[data-elementor-type="footer"] .wd-el-toggle' ) ].filter( t => t.querySelector( 'nav.tp-ft-menu' ) ).map( t => {
			const a = [ ...t.querySelectorAll( 'a.tp-ft-menu__a' ) ];
			return { title: ( ( t.querySelector( '.wd-el-toggle-title' ) || {} ).textContent || '' ).trim(), n: a.length, seen: a.filter( x => x.getBoundingClientRect().height > 0 && 'hidden' !== getComputedStyle( x ).visibility ).length };
		} ) );
		const A0 = await acc(), A1 = [];
		for ( let i = 0; i < A0.length; i++ ) {
			const head = page.locator( '[data-elementor-type="footer"] .wd-el-toggle:has(nav.tp-ft-menu) .wd-el-toggle-head' ).nth( i );
			await head.evaluate( e => e.scrollIntoView( { behavior: 'instant', block: 'center' } ) );
			await head.click();
			await page.waitForTimeout( 800 * X );
			A1.push( ( await acc() )[ i ] );
			await head.click();
			await page.waitForTimeout( 600 * X );
		}
		ok( A0.length > 0 && A0.every( t => 0 === t.seen ) && A1.length === A0.length && A1.every( t => t.n > 0 && t.seen === t.n ),
			`آکاردئون موبایل · fa · ۳۹۰: ${ A0.length } ستون؛ پیش از کلیک دیدنی [${ A0.map( t => t.seen ).join( '، ' ) }] (انتظار ۰)؛ پس از کلیک [${ A1.map( t => t.title + ' ' + t.seen + '/' + t.n ).join( '، ' ) }]` );
		/* ۳) عکس «پس» fa در ۷۶۸ و ۳۹۰، سه حالت (۱۴۴۰ در بخش ۲) — دستۀ 53-ت بند ۱۰ */
		for ( const w of [ 768, 390 ] ) {
			await go( ENV.base + '/', w );
			for ( const mode of MODES ) {
				await page.evaluate( m => document.documentElement.setAttribute( 'data-tp-mode', m ), mode );
				await page.waitForTimeout( 900 );
				await shot( path.join( SHOTS, `fa-${ w }-${ mode }.png` ) );
			}
		}
	} catch ( e ) {
		fails++;
		console.log( '  ❌ خطا: ' + ( e && e.message ? e.message : e ) );
	} finally {
		try { setDemo( prev ); } catch ( e ) { fails++; console.log( '  ❌ برگرداندن demo_ads نشد: ' + e.message ); }
		await browser.close();
	}
	console.log( `END footer-gate — گذشته: ${ passes }   افتاده: ${ fails }` );
	process.exit( fails ? 1 : 0 );
} )();
