/**
 * دستۀ 55-ب بند ۴ — منوی اصلی سربرگ روی نسخۀ محلی (کروم ۱۴۴۰ و ۳۹۰؛ فایرفاکس ۱۴۴۰).
 *
 *   TP_TIMEOUT_X=2 node tests/nav-main-local.js
 *
 * ۱) دادۀ واقعی: در هیچ منوی سربرگ (رومیزی، رونوشت چسبان، موبایل) پیوند «#» یا <a> بی href که دکمه نیست نیست؛
 *    منوی رومیزی هر زبان = درخت منوی همان زبان (سطح اول و زیرمنو، دستۀ 55-ه)، سقف ۶ · ۸ · ۱۵۰، هر نشانی ۲۰۰؛ دکمه‌ها
 *    دقیقاً والدهای دارای زیرمنو؛ en/ar بی حرف ویژۀ فارسی (en بی هیچ حرف عربی).
 *    تعویض‌گر زبان WPML (li.wpml-ls-item) از سنجش نشت بیرون است — نام هر زبان به خط خودش درست است.
 * ۲) دکمه: زیر «فرهنگ و مردم» (بی‌مقصد) در هر زبان یک آیتم فرزند موقت (`_tp_test_nav`) ← والد <a role="button"
 *    tabindex="0" aria-expanded aria-haspopup> بی href با عنوان همان زبان (پس منوی ترجمۀ WPML است که دیده می‌شود)؛
 *    ۱۴۴۰: هاور ← aria-expanded=true و زیرمنو دیدنی، بیرون ← false؛ Enter ← باز، Escape ← بسته و فوکوس روی دکمه.
 *    ۳۹۰: در منوی موبایل کلیک دکمه پیکان وودمارت را می‌زند (li.opener-page) و aria-expanded همان را می‌گوید.
 *    آیتم‌های موقت در finally (و پیش از آغاز، از اجرای ناتمام) پاک می‌شوند — فقط همان‌ها که نشان دارند.
 * کد خروج: ۰ سالم · ۱ افتادن · ۲ محیط ناقص.
 */
'use strict';

const ENV = require( './env.js' );
const { chromium, firefox } = require( './pw.js' );
const { execFileSync } = require( 'child_process' );

const X     = Math.max( 1, Number( process.env.TP_TIMEOUT_X || 1 ) );
const LANGS = { fa: '/', en: '/en/', ar: '/ar/' };
const PARENT_FA     = 'فرهنگ و مردم';
const CHILD_TITLE   = { fa: 'آزمون منو', en: 'Menu test', ar: 'اختبار القائمة' };
const PERSIAN_ONLY  = /[پچژگکی]/;   // پ چ ژ گ ک ی
const ARABIC_SCRIPT = /[؀-ۿ]/;

if ( 'topersia.local' !== ENV.host ) {
	console.error( 'فقط topersia.local' );
	process.exit( 2 );
}

const php = args => execFileSync( ENV.php, args, { cwd: ENV.workspace, encoding: 'utf8', maxBuffer: 1 << 26 } );
const wp  = code => php( [ ENV.wpCli, '--path=' + ENV.wp, '--url=' + ENV.base, 'eval', code ] ).trim().split( '\n' ).pop();

let fails = 0, passes = 0;
let TREES = {};   // درخت مورد انتظار هر زبان (TREE_PHP)
const ok = ( cond, msg ) => {
	if ( cond ) {
		passes++;
		console.log( '  ✅ ' + msg );
	} else {
		fails++;
		console.log( '  ❌ ' + msg );
	}
};

/* «فرهنگ و مردم» در #19 و ترجمه‌اش در هر زبان: شناسه، عنوان همان زبان، منوی دربرگیرنده (از جدول، بی صافی زبان WPML) */
const PARENTS_PHP = `global $wpdb; $o = array();
foreach ( wp_get_nav_menu_items( 19 ) as $i ) {
	if ( ${ JSON.stringify( PARENT_FA ) } !== $i->post_title ) { continue; }
	$trid = apply_filters( "wpml_element_trid", null, $i->ID, "post_nav_menu_item" );
	foreach ( (array) apply_filters( "wpml_get_element_translations", null, $trid, "post_nav_menu_item" ) as $l => $r ) {
		$p = get_post( $r->element_id );
		$m = (int) $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_id FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tr.object_id = %d AND tt.taxonomy = %s", $r->element_id, "nav_menu" ) );
		$o[ $l ] = array( "id" => (int) $r->element_id, "title" => $p ? $p->post_title : "", "menu" => $m );
	}
}
echo wp_json_encode( $o, JSON_UNESCAPED_UNICODE );`;
const CLEAN_PHP = `$n = 0; foreach ( get_posts( array( "post_type" => "nav_menu_item", "post_status" => "any", "numberposts" => -1, "suppress_filters" => true, "meta_key" => "_tp_test_nav", "fields" => "ids" ) ) as $id ) { wp_delete_post( $id, true ); $n++; } echo $n;`;
/* دستۀ 55-ه بند ۵ — درخت مورد انتظار هر زبان از منوی همان زبان (#19 و ترجمۀ هم‌trid WPML)، با همان قاعدۀ رندر
 * (includes/topersia-g-nav.php): آیتم «#»/تهی بی فرزند دیدنی نمی‌آید؛ والد «#» با فرزند دیدنی دکمه (نشانی null).
 * [ [ عنوان، نشانی|null، [ [ عنوان، نشانی ] … ] ] … ] به ترتیب منو؛ زبان پیش از خواندن عوض می‌شود (adjust id WPML). */
const TREE_PHP = `global $wpdb; $o = array();
$tt   = (int) $wpdb->get_var( "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = 19 AND taxonomy = 'nav_menu'" );
$trid = (int) apply_filters( "wpml_element_trid", null, $tt, "tax_nav_menu" );
foreach ( (array) apply_filters( "wpml_get_element_translations", null, $trid, "tax_nav_menu" ) as $l => $r ) {
	do_action( "wpml_switch_language", $l );
	$mid  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_taxonomy_id = %d", $r->element_id ) );
	$all  = array_values( array_filter( (array) wp_get_nav_menu_items( $mid ), function ( $i ) { return "publish" === $i->post_status; } ) );
	$link = function ( $i ) { $u = trim( (string) $i->url ); return "" === $u || "#" === $u ? null : $u; };
	$tree = array();
	foreach ( $all as $i ) {
		if ( (int) $i->menu_item_parent ) { continue; }
		$kids = array();
		foreach ( $all as $k ) {
			if ( (int) $k->menu_item_parent === (int) $i->ID && null !== $link( $k ) ) { $kids[] = array( $k->title, $link( $k ) ); }
		}
		if ( null !== $link( $i ) || $kids ) { $tree[] = array( $i->title, $link( $i ), $kids ); }
	}
	$o[ $l ] = $tree;
}
echo wp_json_encode( $o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );`;
const TOP_MAX = 6, CHILD_MAX = 8, LINK_MAX = 150;   // همان TP_MM_* در scripts/tp-main-menu.php
/* زبان پیش از نوشتن عوض می‌شود: WPML شناسۀ منو را به ترجمۀ زبان جاری برمی‌گرداند (wp_get_nav_menu_object) — بی آن
 * فرزند en/ar در #19 می‌نشیند. منوی واقعی آیتم از جدول خوانده و برگردانده می‌شود تا آزمون همان را بسنجد. */
const addChild = ( lang, par ) => JSON.parse( wp( `global $wpdb; do_action( "wpml_switch_language", ${ JSON.stringify( lang ) } );
$id = wp_update_nav_menu_item( ${ par.menu }, 0, array( "menu-item-title" => ${ JSON.stringify( CHILD_TITLE[ lang ] ) }, "menu-item-url" => ${ JSON.stringify( ENV.base + LANGS[ lang ] ) }, "menu-item-type" => "custom", "menu-item-status" => "publish", "menu-item-parent-id" => ${ par.id } ) );
if ( is_wp_error( $id ) || ! $id ) { echo wp_json_encode( array( "id" => 0, "menu" => 0 ) ); return; }
update_post_meta( $id, "_tp_test_nav", 1 );
do_action( "wpml_set_element_language_details", array( "element_id" => $id, "element_type" => "post_nav_menu_item", "trid" => false, "language_code" => ${ JSON.stringify( lang ) } ) );
$m = (int) $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_id FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tr.object_id = %d AND tt.taxonomy = %s", $id, "nav_menu" ) );
echo wp_json_encode( array( "id" => (int) $id, "menu" => $m ) );` ) );

/* همۀ منوهای سربرگ: رومیزی (و رونوشت چسبان) و موبایل */
function probeNav() {
	const out = [];
	document.querySelectorAll( 'ul.wd-nav-main, ul.wd-nav-mobile' ).forEach( ul => {
		const kind = ul.classList.contains( 'wd-nav-mobile' ) ? 'mobile' : 'main';
		ul.querySelectorAll( 'li.menu-item > a' ).forEach( a => {
			const li = a.parentElement;
			out.push( {
				kind,
				text: ( a.querySelector( '.nav-link-text' ) || a ).textContent.trim(),
				href: a.getAttribute( 'href' ),
				role: a.getAttribute( 'role' ),
				tab: a.getAttribute( 'tabindex' ),
				exp: a.getAttribute( 'aria-expanded' ),
				pop: a.getAttribute( 'aria-haspopup' ),
				top: li.parentElement === ul,
				ls: li.classList.contains( 'wpml-ls-item' ),
			} );
		} );
	} );
	return out;
}

/* درخت منوی رومیزی (نخستین ul.wd-nav-main سربرگ؛ رونوشت چسبان پس از آن می‌آید): سطح اول و زیرمنوی هر کدام، بی تعویض‌گر زبان */
function probeTree() {
	const ul = document.querySelector( 'nav.wd-header-main-nav ul.wd-nav-main' );
	if ( ! ul ) {
		return null;
	}
	const txt = a => ( a.querySelector( '.nav-link-text' ) || a ).textContent.trim();
	return [ ...ul.querySelectorAll( ':scope > li.menu-item' ) ].filter( li => ! li.classList.contains( 'wpml-ls-item' ) ).map( li => {
		const a = li.querySelector( ':scope > a' );
		return [ txt( a ), a.getAttribute( 'href' ), [ ...li.querySelectorAll( ':scope ul li.menu-item > a' ) ].map( k => [ txt( k ), k.getAttribute( 'href' ) ] ) ];
	} );
}

/* نخستین نمونۀ دیدنی درون پنجره را نشان می‌زند تا رونوشت پنهان چسبان برگزیده نشود */
function markVisible( [ sel, tag ] ) {
	const vis = e => {
		const r = e.getBoundingClientRect(), cs = getComputedStyle( e );
		return r.width > 0 && r.height > 0 && r.bottom > 0 && r.top < innerHeight && 'visible' === cs.visibility && Number( cs.opacity ) > 0.1;
	};
	document.querySelectorAll( '[data-tp-t="' + tag + '"]' ).forEach( e => e.removeAttribute( 'data-tp-t' ) );
	const el = [ ...document.querySelectorAll( sel ) ].find( vis );
	if ( el ) {
		el.setAttribute( 'data-tp-t', tag );
	}
	return !! el;
}

const leakFree = ( lang, items ) => items.filter( i => ! i.ls ).every( i => ( 'en' === lang ? ! ARABIC_SCRIPT.test( i.text ) : ! PERSIAN_ONLY.test( i.text ) ) );
const badLinks = items => items.filter( i => '#' === ( i.href || '' ).trim() || ( null === i.href && 'button' !== i.role ) );

async function session( browserType ) {
	const login   = JSON.parse( php( [ 'tests/local-login.php' ] ) );
	const browser = await browserType.launch();
	const ctx     = await browser.newContext();
	await ctx.addCookies( login.cookies );
	const page = await ctx.newPage();
	const go   = async ( url, w ) => {
		await page.setViewportSize( { width: w, height: 900 } );
		const res = await page.goto( url, { waitUntil: 'load', timeout: 180000 * X } );
		await page.waitForTimeout( 600 * X );
		return res ? res.status() : 0;
	};
	return { browser, ctx, page, go };
}

async function run( browserType, name, widths, withButton, parents ) {
	const { browser, page, go } = await session( browserType );
	try {
		for ( const [ lang, p ] of Object.entries( LANGS ) ) {
			for ( const w of widths ) {
				const st    = await go( ENV.base + p, w );
				const items = await page.evaluate( probeNav );
				const bad   = badLinks( items );
				ok( 200 === st && ! bad.length, `${ name } · ${ withButton ? 'با فرزند موقت' : 'دادۀ واقعی' } · ${ lang } · ${ w }: ${ items.length } پیوند منو، بی «#» و بی <a> بی href غیر دکمه (${ bad.length })` + ( bad.length ? ' ← ' + bad.map( b => b.kind + ':' + b.text ).join( ' | ' ) : '' ) );
				if ( 'fa' !== lang ) {
					ok( leakFree( lang, items ), `${ name } · ${ lang } · ${ w }: بی نشت فارسی در عنوان منو [${ [ ...new Set( items.filter( i => ! i.ls ).map( i => i.text ) ) ].join( '، ' ) || '—' }]` );
				}
				/* دستۀ 55-ه: دکمه‌ها دقیقاً والدهای بی‌مقصدی که به همین زبان فرزند دیدنی دارند (و با فرزند موقت، والد آن) */
				const want  = [ ...new Set( ( TREES[ lang ] || [] ).filter( t => null === t[1] && t[2].length ).map( t => t[0] ).concat( withButton ? [ parents[ lang ].title ] : [] ) ) ].sort();
				const btns  = items.filter( i => 'button' === i.role );
				const texts = [ ...new Set( btns.map( b => b.text ) ) ].sort();
				ok( btns.every( b => null === b.href && '0' === b.tab && 'false' === b.exp && 'true' === b.pop ) && JSON.stringify( texts ) === JSON.stringify( want ),
					`${ name } · ${ withButton ? 'با فرزند موقت' : 'دادۀ واقعی' } · ${ lang } · ${ w }: دکمه‌ها [${ texts.join( '، ' ) || '—' }] = والدهای دارای زیرمنو [${ want.join( '، ' ) || '—' }]؛ ${ btns.length } نمونه، بی href، tabindex 0، aria-expanded false، aria-haspopup true` );
			}
		}
		if ( withButton ) {
			await behaviour( page, go, name );
		}
	} catch ( e ) {
		fails++;
		console.log( '  ❌ خطا (' + name + '): ' + ( e && e.message ? e.message.split( '\n' )[0] : e ) );
	} finally {
		await browser.close();
	}
}

/* رفتار دکمه — fa، رومیزی و موبایل */
async function behaviour( page, go, name ) {
	await go( ENV.base + '/', 1440 );
	/* رونوشت چسبان وودمارت پس از بارگذاری ساخته می‌شود و نشان را هم رونوشت می‌کند؛ پس پیش از هر کنش دوباره نشان زده می‌شود */
	const BTN_SEL = 'nav.wd-header-main-nav ul.wd-nav-main > li > a[data-tp-nav-btn]';
	const tag     = () => page.evaluate( markVisible, [ BTN_SEL, 'btn' ] );
	const found   = await tag();
	const title   = found ? await page.evaluate( () => { const b = document.querySelector( '[data-tp-t="btn"]' ); return ( b.querySelector( '.nav-link-text' ) || b ).textContent.trim(); } ) : '';
	ok( found, `${ name } · fa · ۱۴۴۰: نخستین دکمۀ والد «${ title }» در سربرگ دیدنی است` );
	if ( ! found ) {
		return;
	}
	const btn   = async () => {
		await tag();
		return page.locator( '[data-tp-t="btn"]' ).first();
	};
	const state = async () => {
		await tag();
		return page.evaluate( () => {
			const b  = document.querySelector( '[data-tp-t="btn"]' );
			const d  = b && b.parentElement.querySelector( ':scope > .wd-dropdown' );
			const cs = d ? getComputedStyle( d ) : null;
			return { exp: b ? b.getAttribute( 'aria-expanded' ) : '', open: !! cs && 'visible' === cs.visibility && Number( cs.opacity ) > 0.5, focus: !! b && document.activeElement === b };
		} );
	};
	await ( await btn() ).hover();
	await page.waitForTimeout( 700 );
	const h1 = await state();
	await page.mouse.move( 5, 880 );
	await page.waitForTimeout( 700 );
	const h0 = await state();
	ok( 'true' === h1.exp && h1.open && 'false' === h0.exp && ! h0.open, `${ name } · fa · ۱۴۴۰ · هاور «${ title }»: aria-expanded ${ h1.exp } زیرمنو ${ h1.open ? 'باز' : 'بسته' }؛ بیرون ${ h0.exp } ${ h0.open ? 'باز' : 'بسته' }` );
	await ( await btn() ).focus();
	await page.keyboard.press( 'Enter' );
	await page.waitForTimeout( 700 );
	const k1 = await state();
	await page.keyboard.press( 'Escape' );
	await page.waitForTimeout( 700 );
	const k0 = await state();
	ok( 'true' === k1.exp && k1.open && 'false' === k0.exp && ! k0.open && k0.focus,
		`${ name } · fa · ۱۴۴۰ · صفحه‌کلید: Enter ← ${ k1.exp } ${ k1.open ? 'باز' : 'بسته' }؛ Escape ← ${ k0.exp } ${ k0.open ? 'باز' : 'بسته' }، فوکوس روی دکمه ${ k0.focus }` );

	await go( ENV.base + '/', 390 );
	const burger = await page.evaluate( markVisible, [ '.wd-header-mobile-nav > a', 'burger' ] );
	ok( burger, `${ name } · fa · ۳۹۰: دکمۀ منوی موبایل دیدنی است` );
	if ( ! burger ) {
		return;
	}
	await page.evaluate( markVisible, [ '.wd-header-mobile-nav > a', 'burger' ] );
	await page.locator( '[data-tp-t="burger"]' ).first().click();
	await page.waitForTimeout( 900 );
	const msel = 'ul.wd-nav-mobile > li > a[data-tp-nav-btn]';
	/* چیدمان منوی موبایل از سازندۀ سربرگ: dropdown (li.opener-page، بستن با کلیک دوباره) یا drilldown (زیرمنو جای سطح اول، بستن با «بازگشت») */
	const mst  = () => page.evaluate( s => {
		const b = document.querySelector( s );
		if ( ! b ) {
			return null;
		}
		const li = b.parentElement, sub = li.querySelector( ':scope > .wd-sub-menu' );
		const vis = e => { const r = e.getBoundingClientRect(); return r.width > 0 && r.height > 0 && 'visible' === getComputedStyle( e ).visibility; };
		const drill = !! b.closest( 'ul.wd-layout-drilldown' );
		return { exp: b.getAttribute( 'aria-expanded' ), drill, opener: !! li.querySelector( ':scope > .wd-nav-opener' ),
			open: drill ? !! ( sub && sub.classList.contains( 'wd-drilldown-show' ) && vis( sub ) ) : li.classList.contains( 'opener-page' ),
			btnVis: vis( b ) };
	}, msel );
	await page.locator( msel ).first().click();
	await page.waitForTimeout( 700 );
	const m1 = await mst();
	if ( m1 && m1.drill ) {
		await page.evaluate( s => { const a = document.querySelector( s ).parentElement.querySelector( ':scope > .wd-sub-menu > .wd-drilldown-back > a' ); if ( a ) { a.setAttribute( 'data-tp-t', 'mback' ); } }, msel );
		await page.locator( '[data-tp-t="mback"]' ).click();
	} else {
		await page.locator( msel ).first().click();
	}
	await page.waitForTimeout( 700 );
	const m0 = await mst();
	ok( m1 && m1.opener && m1.open && 'true' === m1.exp && m0 && ! m0.open && 'false' === m0.exp && m0.btnVis,
		`${ name } · fa · ۳۹۰ · منوی موبایل (${ m1 && m1.drill ? 'drilldown' : 'dropdown' }): پیکان وودمارت ${ m1 && m1.opener }؛ کلیک ← باز ${ m1 && m1.open } aria ${ m1 && m1.exp }؛ ${ m1 && m1.drill ? 'بازگشت' : 'کلیک دوباره' } ← ${ m0 && m0.open } aria ${ m0 && m0.exp }، دکمه دیدنی ${ m0 && m0.btnVis }` );
}

/* دستۀ 55-ه بند ۵: منوی رومیزی هر زبان = درخت منوی همان زبان (سطح اول و زیرمنو، عنوان و نشانی، به همان ترتیب)؛ سقف
 * سطح اول ≤ TOP_MAX، هر زیرمنو ≤ CHILD_MAX، کل ≤ LINK_MAX؛ fa ساختار سطح اول بند ۴ را دارد؛ هر نشانی ۲۰۰ */
async function structure() {
	const { browser, ctx, page, go } = await session( chromium );
	const urls = new Map();
	try {
		for ( const [ lang, p ] of Object.entries( LANGS ) ) {
			const want = TREES[ lang ] || [];
			await go( ENV.base + p, 1440 );
			const seen = await page.evaluate( probeTree );
			const kids = want.map( t => t[2].length );
			const all  = want.reduce( ( n, t ) => n + 1 + t[2].length, 0 );
			ok( !! seen && JSON.stringify( seen ) === JSON.stringify( want ),
				`${ lang } · ۱۴۴۰: منوی رومیزی = درخت منوی ${ lang } — ${ want.map( t => t[0] + ( t[2].length ? ' (' + t[2].length + ')' : '' ) ).join( '، ' ) || '—' }` + ( seen && JSON.stringify( seen ) !== JSON.stringify( want ) ? ' ← دیده: ' + JSON.stringify( seen ) : '' ) );
			ok( want.length <= TOP_MAX && ( ! kids.length || Math.max( ...kids ) <= CHILD_MAX ) && all <= LINK_MAX,
				`${ lang }: سقف — سطح اول ${ want.length } (≤ ${ TOP_MAX })، بیشینۀ زیرمنو ${ kids.length ? Math.max( ...kids ) : 0 } (≤ ${ CHILD_MAX })، کل ${ all } (≤ ${ LINK_MAX })` );
			want.forEach( t => [ [ t[0], t[1] ], ...t[2] ].forEach( ( [ n, u ] ) => u && urls.set( u, lang + ' «' + n + '»' ) ) );
		}
		const top = ( TREES.fa || [] ).map( t => t[0] );
		ok( [ 'مقصدها', 'تجربه‌ها', 'فرهنگ و مردم', 'راهنمای سفر', 'مجله' ].every( t => top.includes( t ) ) && ( TREES.fa || [] ).filter( t => 'مجله' === t[0] ).every( t => /\/news\/$/.test( t[1] || '' ) ),
			`fa: سطح اول بند ۴ [${ top.join( '، ' ) }] — «مجله» ← هاب اخبار` );
		for ( const [ u, who ] of urls ) {
			const r = await ctx.request.get( u, { timeout: 180000 * X } );
			ok( 200 === r.status(), `${ who } ${ u } ← ${ r.status() }` );
		}
	} catch ( e ) {
		fails++;
		console.log( '  ❌ خطا (ساختار منو): ' + ( e && e.message ? e.message.split( '\n' )[0] : e ) );
	} finally {
		await browser.close();
	}
}

( async () => {
	let parents;
	try {
		wp( CLEAN_PHP );
		parents = JSON.parse( wp( PARENTS_PHP ) );
		TREES   = JSON.parse( wp( TREE_PHP ) );
	} catch ( e ) {
		console.error( 'محیط ناقص: ' + e.message );
		process.exit( 2 );
	}
	if ( ! [ 'fa', 'en', 'ar' ].every( l => parents[ l ] && parents[ l ].id && parents[ l ].menu ) ) {
		console.error( 'محیط ناقص: «' + PARENT_FA + '» با منو در fa/en/ar نیست — نخست scripts/tp-main-menu.php apply ← ' + JSON.stringify( parents ) );
		process.exit( 2 );
	}
	console.log( 'START nav-main ' + new Date().toISOString() );

	await structure();
	await run( chromium, 'کروم', [ 1440, 390 ], false, parents );
	await run( firefox, 'فایرفاکس', [ 1440 ], false, parents );

	const made = [];
	try {
		for ( const lang of Object.keys( LANGS ) ) {
			let r;
			try {
				r = addChild( lang, parents[ lang ] );
			} catch ( e ) {
				r = { id: 0, menu: 0 };
			}
			ok( r.id > 0 && r.menu === parents[ lang ].menu, `آیتم فرزند موقت ${ lang } زیر ${ parents[ lang ].id } «${ parents[ lang ].title }» ← ${ r.id } در منوی ${ r.menu } (باید ${ parents[ lang ].menu })` );
			made.push( r.id );
		}
		await run( chromium, 'کروم', [ 1440, 390 ], true, parents );
		await run( firefox, 'فایرفاکس', [ 1440 ], true, parents );
	} finally {
		const n = Number( wp( CLEAN_PHP ) );
		ok( n === made.filter( Boolean ).length, `پاک‌سازی: ${ n } آیتم موقت پاک شد (ساخته ${ made.filter( Boolean ).length })` );
	}
	console.log( `END nav-main — گذشته: ${ passes }   افتاده: ${ fails }` );
	process.exit( fails ? 1 : 0 );
} )();
