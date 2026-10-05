/**
 * زبانۀ «بستۀ محتوا» در پیشخوانِ واقعی — topersia-core ۴٫۱۲٫۰ · فاز ۲ب · P5-D272
 *
 *   node tests/pack-admin.js
 *
 * کروم و فایرفاکس، با مدیر آزمون (tests/local-login.php tp_test_admin — هیچ
 * گذرواژه‌ای). مسیر کامل کاربر: بارگیری قالب و JSON Schema و برون‌بری پست،
 * بارگذاری پرونده، گزارش آزمایشی دسته‌دسته (AJAX)، «اجرا» دسته‌دسته، و
 * «برگرداندن» پس از تازه‌کردن صفحه. نقطه‌های AJAX برای مهمان و مشترک بسته.
 * ۲ج٫۹ (P5-D283): بخش «درون‌ریزی ترجمه» — قالب ترجمۀ شهر فارسیِ موقت، ردِ
 * پروندۀ فارسی، آزمایشی، اجرا (پست en با همان trid) و برگرداندن.
 *
 * هر داده‌ای که ساخته می‌شود عنوانش نشان «آزمون پیشخوان <tag>» دارد و در پایان
 * پاک می‌شود؛ گزینۀ «آخرین درون‌ریزی» به مقدار پیش از آزمون برمی‌گردد.
 * هیچ درخواستی به topersia.com نمی‌رود.
 */

'use strict';

const fs = require( 'node:fs' );
const os = require( 'node:os' );
const path = require( 'node:path' );
const { execFileSync } = require( 'node:child_process' );
const { chromium, firefox } = require( 'playwright' );
const ENV = require( './env.js' );   /* مسیرها و نشانی پایه — tests/env.json (P5-D285) */

const WS   = path.resolve( __dirname, '..' );
const PHP  = ENV.php;
const BASE = ENV.base;
const TAB  = BASE + '/geo/wp-admin/admin.php?page=topersia&tab=pack';

let pass = 0, fail = 0;
function check( label, ok, note ) {
	if ( ok ) { pass ++; console.log( '  ✅ ' + label ); }
	else      { fail ++; console.log( '  ❌ ' + label + ( note ? '   ← ' + note : '' ) ); }
}
function head( t ) { console.log( '\n' + t + '\n' + '─'.repeat( 78 ) ); }
function php( args ) { return execFileSync( PHP, args, { cwd: WS, encoding: 'utf8' } ); }

/* پروندۀ آزمون: یک پست با گزینۀ نادرست، همان پست تکراری، و یک پست پاکیزه */
function packFile( tag ) {
	const base = ( name, en, season ) => ( {
		entity_uid: '', slug: '', language: 'fa', translation_of: '', title: '',
		fields: {
			name_fa: name, name_en: en, name_ar: 'اختبار', overview_text: '<p>' + 'واژه '.repeat( 820 ) + '</p>',
			population: 4321, area_km2: 9, best_season: season, meta_title: name, meta_description: 'آزمون', transport_access: 'جاده',
			geo_coordinates: '32.1, 51.2',
			source_references: [ { source_name: 'Wikipedia', source_url: 'https://en.wikipedia.org/wiki/Iran', source_access_date: '2026-09-29', source_row_status: '' } ],
		},
		relations: { 73: [ { name: 'شهرستان آزمون پیشخوان ' + tag, type: 'tp_county' } ] },
	} );
	const one = base( 'شهر یکم آزمون پیشخوان ' + tag, 'Admin Test One ' + tag, 'late-spring' );
	const two = base( 'شهر دوم آزمون پیشخوان ' + tag, 'Admin Test Two ' + tag, 'spring' );
	return JSON.stringify( { meta: { schema: 'topersia-geo-import/2.0', post_type: 'tp_city', language: 'fa' }, posts: [ one, one, two ] } );
}

async function download( page, click ) {
	const [ dl ] = await Promise.all( [ page.waitForEvent( 'download', { timeout: 120000 } ), click() ] );
	const file = await dl.path();
	return { name: dl.suggestedFilename(), json: JSON.parse( fs.readFileSync( file, 'utf8' ) ) };
}

async function run( type, name ) {

	const tag   = ( name === 'کروم' ? 'c' : 'f' ) + Date.now().toString( 36 );
	const setup = JSON.parse( php( [ 'tests/pack-admin-state.php', 'setup', tag ] ) );
	const login = JSON.parse( php( [ 'tests/local-login.php', 'tp_test_admin' ] ) );
	const sub   = JSON.parse( php( [ 'tests/local-login.php' ] ) );
	const browser = await type.launch();

	try {
		head( `${ name } — زبانۀ «بستۀ محتوا»` );

		const ctx = await browser.newContext( { viewport: { width: 1440, height: 900 }, acceptDownloads: true } );
		await ctx.route( /topersia\.com/, r => r.abort() );
		await ctx.addCookies( login.cookies );
		const page = await ctx.newPage();
		page.on( 'dialog', d => d.accept() );

		await page.goto( TAB, { waitUntil: 'domcontentloaded', timeout: 240000 } );
		const cards = await page.$$eval( '.tp-pack-card h3', h => h.map( x => x.textContent.trim() ) );
		check( `${ name }: پنج کارت — قالب برای هوش مصنوعی · برون‌بری پست موجود · درون‌ریزی · قالب ترجمه · درون‌ریزی ترجمه؛ قالب اصلی بی گزینش زبان`,
			JSON.stringify( cards ) === JSON.stringify( [ 'قالب برای هوش مصنوعی', 'برون‌بری پست موجود', 'درون‌ریزی', 'قالب ترجمه', 'درون‌ریزی ترجمه' ] ) && ! ( await page.$( 'select[name=lang]' ) ),
			JSON.stringify( cards ) );

		/* ══ ترجمه — ۲ج٫۹ ══ */
		const nav = () => page.waitForNavigation( { waitUntil: 'domcontentloaded', timeout: 240000 } );
		await page.selectOption( 'select[name=tlang]', 'en' );
		await page.fill( 'form:has(input[name=what][value=translation]) input[name=post]', String( setup.city ) );
		const trTpl = await download( page, () => page.click( 'form:has(input[name=what][value=translation]) button' ) );
		const tp    = trTpl.json.posts && trTpl.json.posts[0] ? trTpl.json.posts[0] : { fields: {} };
		check( `${ name }: «قالب ترجمه» — topersia-geo-translation/1.0، en، translation_of؛ short_summary با source فارسی و value خالی؛ population (copy) نیست`,
			'topersia-geo-translation/1.0' === trTpl.json.meta.schema && 'en' === trTpl.json.meta.language && !! tp.translation_of
			&& tp.fields.short_summary && /ساختگی/.test( tp.fields.short_summary.source ) && '' === tp.fields.short_summary.value && ! ( 'population' in tp.fields ),
			trTpl.name );

		const tmp = path.join( os.tmpdir(), 'pack-' + tag + '.json' );
		fs.writeFileSync( tmp, packFile( tag ) );
		await page.setInputFiles( 'input[name=topersia_pack_tr_file]', tmp );
		await Promise.all( [ nav(), page.click( 'button[name=topersia_pack_tr_upload]' ) ] );
		const rejMain = await page.evaluate( () => ( document.querySelector( '.notice-error' ) || {} ).textContent || '' );
		check( `${ name }: پروندۀ فارسی در «درون‌ریزی ترجمه» رد — «این پروندۀ درون‌ریزی اصلی (فارسی) است»`,
			/درون‌ریزی اصلی \(فارسی\)/.test( rejMain ), rejMain.slice( 0, 120 ) );

		tp.title.value                = 'Admin Translation ' + tag;
		tp.fields.short_summary.value = 'A made-up city for the admin translation test.';
		tp.fields.population          = { source: 777, value: 1 };
		const tmpTr = path.join( os.tmpdir(), 'tr-' + tag + '.json' );
		fs.writeFileSync( tmpTr, JSON.stringify( trTpl.json ) );
		await page.setInputFiles( 'input[name=topersia_pack_tr_file]', tmpTr );
		await Promise.all( [ nav(), page.click( 'button[name=topersia_pack_tr_upload]' ) ] );
		await page.waitForFunction( () => /گزارش کامل شد/.test( ( document.getElementById( 'tp-pack-state' ) || {} ).textContent || '' ), null, { timeout: 240000 } );
		const trRep = await page.evaluate( () => {
			const p = document.querySelector( '#tp-pack-report .tp-pack-post' );
			return {
				head: ( [ ...document.querySelectorAll( 'h2' ) ].find( h => /گزارش آزمایشی/.test( h.textContent ) ) || {} ).textContent || '',
				action: p ? p.dataset.action : '',
				pop: p ? ( ( [ ...p.querySelectorAll( 'tr' ) ].find( tr => /population/.test( tr.textContent ) ) || {} ).textContent || '' ) : '',
				run: ! document.getElementById( 'tp-pack-run' ).disabled,
			};
		} );
		check( `${ name }: گزارش آزمایشی ترجمه — ساخت؛ population «فیلد غیرترجمه‌ای در فایل ← نادیده»؛ «اجرا» روشن`,
			/ترجمه/.test( trRep.head ) && 'create' === trRep.action && /فیلد غیرترجمه‌ای در فایل/.test( trRep.pop ) && trRep.run, JSON.stringify( trRep ).slice( 0, 300 ) );

		await page.click( '#tp-pack-run' );
		await page.waitForFunction( () => /اجرا تمام شد/.test( document.getElementById( 'tp-pack-state' ).textContent ), null, { timeout: 480000 } );
		const trState = JSON.parse( php( [ 'tests/pack-admin-state.php', 'state', tag ] ) );
		const enPost  = trState.posts.find( p => 'en' === p.lang ) || {};
		const faCity  = trState.posts.find( p => p.id === setup.city ) || {};
		check( `${ name }: «اجرا»ی ترجمه — پست en پیش‌نویس با needs_verification، همان trid، population از پست فارسی (777، نه 1)، نامک همان`,
			'draft' === enPost.status && 'needs_verification' === enPost.content && !! faCity.trid && enPost.trid === faCity.trid && '777' === enPost.pop && enPost.slug === faCity.slug,
			JSON.stringify( enPost ) );

		await page.goto( TAB, { waitUntil: 'domcontentloaded', timeout: 240000 } );
		const trLast = await page.evaluate( () => ( [ ...document.querySelectorAll( 'p' ) ].find( p => /^imp-/.test( p.textContent.trim() ) ) || {} ).textContent || '' );
		if ( await page.$( '#tp-pack-undo' ) ) {
			await page.click( '#tp-pack-undo' );
			await page.waitForFunction( () => /برگردانده شد/.test( document.getElementById( 'tp-pack-undo-state' ).textContent ), null, { timeout: 240000 } );
		}
		const trUndone = ( JSON.parse( php( [ 'tests/pack-admin-state.php', 'state', tag ] ) ).posts.find( p => 'en' === p.lang ) || {} ).status;
		check( `${ name }: «آخرین درون‌ریزی» با نشان «ترجمه»؛ برگرداندن پست en را به زباله برد`,
			/ترجمه/.test( trLast ) && 'trash' === trUndone, trLast.slice( 0, 120 ) + ' · ' + trUndone );

		/* بارگیری‌ها */
		await page.selectOption( 'select[name=type]', 'tp_city' );
		const tpl = await download( page, () => page.click( 'button[name=what][value=template]' ) );
		check( `${ name }: «پروندۀ قالب» — topersia-pack-tp_city-fa.json، schema 2.0، دستور فارسی، یک پست خالی`,
			'topersia-pack-tp_city-fa.json' === tpl.name && 'topersia-geo-import/2.0' === tpl.json.meta.schema && 'tp_city' === tpl.json.meta.post_type
			&& /منبع/.test( tpl.json.instructions[0] ) && 1 === tpl.json.posts.length && '' === tpl.json.posts[0].fields.name_fa, tpl.name );

		const sch = await download( page, () => page.click( 'button[name=what][value=schema]' ) );
		check( `${ name }: «JSON Schema» — draft 2020-12، post_type ثابت tp_city`,
			'https://json-schema.org/draft/2020-12/schema' === sch.json.$schema && 'tp_city' === sch.json.properties.meta.properties.post_type.const, sch.name );

		await page.fill( 'form:has(input[name=what][value=post]) input[name=post]', '399' );
		const exp = await download( page, () => page.click( 'form:has(input[name=what][value=post]) button' ) );
		check( `${ name }: «برون‌بری» #399 — نامک isfahan، رابطۀ ۷۳ با نام و uid`,
			'isfahan' === exp.json.posts[0].slug && Array.isArray( exp.json.posts[0].relations[ '73' ] ) && !! exp.json.posts[0].relations[ '73' ][0].uid, exp.name );

		/* بارگذاری و گزارش آزمایشی — همان پروندۀ فارسی که بخش ترجمه رد کرد */
		await page.setInputFiles( 'input[name=topersia_pack_file]', tmp );
		await Promise.all( [ page.waitForNavigation( { waitUntil: 'domcontentloaded', timeout: 240000 } ), page.click( 'button[name=topersia_pack_upload]' ) ] );
		await page.waitForFunction( () => /گزارش کامل شد/.test( ( document.getElementById( 'tp-pack-state' ) || {} ).textContent || '' ), null, { timeout: 240000 } );

		const rep = await page.evaluate( () => {
			const posts = [ ...document.querySelectorAll( '#tp-pack-report .tp-pack-post' ) ];
			return {
				actions: posts.map( p => p.dataset.action ),
				bad: posts.map( p => p.classList.contains( 'has-bad' ) ),
				season: ( [ ...posts[0].querySelectorAll( 'tr' ) ].find( tr => /best_season/.test( tr.textContent ) ) || {} ).textContent || '',
				dup: posts[1] ? posts[1].textContent : '',
				summary: document.getElementById( 'tp-pack-summary' ).textContent,
				run: ! document.getElementById( 'tp-pack-run' ).disabled,
			};
		} );
		check( `${ name }: گزارش آزمایشی دسته‌دسته — ساخت · رد · ساخت؛ best_season «گزینۀ نامعتبر واژه‌نامه»، پست دوم «تکراری»، پست سوم بی خطا؛ «اجرا» روشن`,
			'create,skip,create' === rep.actions.join( ',' ) && /گزینۀ نامعتبر واژه‌نامه/.test( rep.season ) && /تکراری/.test( rep.dup )
			&& true === rep.bad[0] && true === rep.bad[1] && false === rep.bad[2] && rep.run, JSON.stringify( rep ).slice( 0, 300 ) );

		const before = JSON.parse( php( [ 'tests/pack-admin-state.php', 'state', tag ] ) );
		/* شهرهای فارسیِ همین درون‌ریزی — نه شهر و ترجمۀ آزمون ترجمه */
		const fresh = p => 'tp_city' === p.type && p.id !== setup.city && 'fa' === p.lang;
		check( `${ name }: آزمایشی هیچ چیز ننوشت — هیچ شهر تازه`, 0 === before.posts.filter( fresh ).length, JSON.stringify( before.posts.filter( fresh ) ) );

		/* اجرا */
		await page.click( '#tp-pack-run' );
		await page.waitForFunction( () => /اجرا تمام شد/.test( document.getElementById( 'tp-pack-state' ).textContent ), null, { timeout: 480000 } );
		const done = await page.evaluate( () => document.getElementById( 'tp-pack-state' ).textContent );
		const after = JSON.parse( php( [ 'tests/pack-admin-state.php', 'state', tag ] ) );
		const cities = after.posts.filter( fresh );
		check( `${ name }: «اجرا» — دو شهر تازه، هر دو پیش‌نویس با needs_verification، زبان fa، فرزند شهرستان موقت؛ best_season نادرست نوشته نشد`,
			/2 ساخت/.test( done ) && /1 رد/.test( done ) && 2 === cities.length
			&& cities.every( c => 'draft' === c.status && 'needs_verification' === c.content && 'fa' === c.lang && 1 === c.parents.length && c.parents[0] === setup.county )
			&& cities.some( c => '' === c.season ) && cities.some( c => 'spring' === c.season ), done + ' ' + JSON.stringify( cities ) );

		/* برگرداندن — پس از تازه‌کردن، از بخش «آخرین درون‌ریزی» */
		await page.goto( TAB, { waitUntil: 'domcontentloaded', timeout: 240000 } );
		const hasUndo = await page.$( '#tp-pack-undo' );
		check( `${ name }: «آخرین درون‌ریزی» شناسۀ اجرا و دکمۀ برگرداندن را نشان می‌دهد`, !! hasUndo && ( await page.content() ).includes( after.last_run.run ), after.last_run && after.last_run.run );
		if ( hasUndo ) {
			await page.click( '#tp-pack-undo' );
			await page.waitForFunction( () => /برگردانده شد/.test( document.getElementById( 'tp-pack-undo-state' ).textContent ), null, { timeout: 240000 } );
		}
		const undone = JSON.parse( php( [ 'tests/pack-admin-state.php', 'state', tag ] ) );
		const ucities = undone.posts.filter( fresh );
		check( `${ name }: «برگرداندن» — هر دو شهر تازه در زباله و بی یال؛ گزینه «برگردانده شد»`,
			2 === ucities.length && ucities.every( c => 'trash' === c.status && 0 === c.parents.length ) && !! ( undone.last_run && undone.last_run.undone ), JSON.stringify( ucities ) );

		await ctx.close();

		/* نقطه‌های AJAX برای مهمان و مشترک بسته */
		const anon = await browser.newContext();
		const a = await anon.request.post( BASE + '/geo/wp-admin/admin-ajax.php', { form: { action: 'topersia_pack_plan', token: 'x', offset: '0' } } );
		const subCtx = await browser.newContext();
		await subCtx.addCookies( sub.cookies );
		const s = await subCtx.request.post( BASE + '/geo/wp-admin/admin-ajax.php', { form: { action: 'topersia_pack_run', token: 'x', offset: '0', _wpnonce: 'x' } } );
		const sBody = await s.text();
		let sMsg = '';
		try { sMsg = JSON.parse( sBody ).data.message; } catch ( e ) { sMsg = sBody.slice( 0, 80 ); }
		check( `${ name }: AJAX بسته — مهمان «0» (بی نقطۀ nopriv)، مشترک ۴۰۳ «دسترسی ندارید»`,
			'0' === ( await a.text() ).trim() && 403 === s.status() && /دسترسی ندارید/.test( sMsg ), a.status() + ' · ' + s.status() + ' ' + sMsg );
		await anon.close();
		await subCtx.close();

		fs.unlinkSync( tmp );
		fs.unlinkSync( tmpTr );
	} finally {
		await browser.close();
		const left = JSON.parse( php( [ 'tests/pack-admin-state.php', 'cleanup', tag, setup.last_run, String( setup.floor ), String( setup.post_floor ) ] ) );
		if ( left.left ) { check( `${ name }: پاک‌سازی`, false, left.left + ' پست ماند' ); }
	}
}

( async () => {
	for ( const [ name, type ] of [ [ 'کروم', chromium ], [ 'فایرفاکس', firefox ] ] ) {
		await run( type, name );
	}
	console.log( '─'.repeat( 78 ) + `\n  گذشته: ${ pass }   افتاده: ${ fail }` );
	process.exit( fail ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 1 ); } );
