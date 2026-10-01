#!/usr/bin/env node
/**
 * دروازهٔ حرکت و «یک‌بار در هر نشست» — در مرورگر، نه در node.  P5-D197
 *
 * چرا مرورگر: `motionAllowed()` را می‌شود در node صدا زد و سبز گرفت،
 * ولی آنچه اهمیت دارد در node وجود ندارد — `prefers-reduced-motion`
 * واقعی، ترتیب اجرای اسکریپت‌ها نسبت به نشانه‌های روی `<html>`، و
 * ذخیره‌گاه نشست که با پیمایش صفحه زنده می‌ماند.
 *
 * سنجه سخت‌گیرانه است: لایه باید **اصلاً ساخته نشود**. ساخته‌شدن و
 * پنهان‌ماندن قبول نیست — حلقه پشتش می‌چرخد و کسی که حرکت را خاموش
 * کرده باز هم باتری و پردازنده می‌دهد.
 *
 * اجرا:  node tests/wx-motion-check.js <chrome> <file-url صحنه>
 */

'use strict';

const { launch } = require( './cdp.js' );

function fail( m ) { process.stderr.write( m + '\n' ); process.exit( 2 ); }

/*
 * سه مانع، به‌اضافهٔ یک مورد بی‌مانع.
 *
 * موردِ بی‌مانع لوکس نیست: بدون آن، آزمونی که هیچ‌وقت لایه را نبیند هم
 * سبز می‌دهد. همان درسِ صفحهٔ کالیبراسیون — آزمونی که نمی‌تواند بیفتد،
 * چیزی را تضمین نمی‌کند.  P5-D193
 */
const GATES = [
	{
		key: 'control',
		label: 'بدون مانع (شاهد)',
		query: 'session=clear&state=rain',
		media: null,
		expect: true
	},
	{
		key: 'reduce',
		label: 'prefers-reduced-motion: reduce',
		query: 'session=clear&state=rain',
		media: 'reduce',
		expect: false,
		also: a => a.reduce === true || 'رسانه در مرورگر جا نیفتاد'
	},
	{
		key: 'motion',
		label: 'data-motion="off"',
		query: 'session=clear&state=rain&motion=off',
		media: null,
		expect: false,
		also: a => 'off' === a.motionAttr || 'نشانه روی <html> ننشست'
	},
	{
		key: 'calm',
		label: 'حالت calm',
		query: 'session=clear&state=rain&calm=on',
		media: null,
		expect: false,
		also: a => 'on' === a.calmAttr || 'نشانه روی <html> ننشست'
	}
];

/* استان بارانی، بعد همان، بعد کویر برفی — روی یک برگه و با پیمایش واقعی */
const TRIP = [
	{ label: 'استان بارانی — بار اول', query: 'session=clear&state=rain', expect: true },
	{ label: 'همان استان — بار دوم',   query: 'state=rain',               expect: false },
	{ label: 'کویر برفی — وضعیت تازه', query: 'state=snow',               expect: true },
	{ label: 'همان کویر — بار دوم',    query: 'state=snow',               expect: false }
];

/** آنچه از یک بار بارشدن صفحه انتظار داریم. */
function judge( a, expect ) {

	const bad = [];

	if ( ! a ) { return [ 'صحنه چیزی گزارش نکرد' ]; }

	if ( a.mounted !== expect ) {
		bad.push( expect ? 'سوار نشد' : 'سوار شد' );
	}

	/* مهم‌تر از پاسخِ تابع: عنصر در DOM هست یا نیست */
	if ( a.layer !== expect ) {
		bad.push( expect ? 'لایه در DOM نیست' : 'لایه در DOM ساخته شد' );
	}

	if ( a.canvas !== expect ) {
		bad.push( expect ? 'canvas ساخته نشد' : 'canvas ساخته شد' );
	}

	if ( a.kidsAfter !== a.kidsBefore + ( expect ? 1 : 0 ) ) {
		bad.push( `شمار فرزندان سربرگ ${a.kidsBefore} → ${a.kidsAfter}` );
	}

	return bad;
}

async function main() {

	const chrome = process.argv[ 2 ];
	const base = process.argv[ 3 ];

	if ( ! chrome || ! base ) {
		fail( 'اجرا: node tests/wx-motion-check.js <chrome> <file-url صحنه>' );
	}

	const browser = await launch( chrome );
	const rows = [];

	try {

		/* ── سه مانع، هرکدام روی برگهٔ تازه ───────────────────────── */

		for ( const gate of GATES ) {

			const page = await browser.open( 'about:blank' );

			try {
				await page.send( 'Emulation.setEmulatedMedia', {
					features: gate.media
						? [ { name: 'prefers-reduced-motion', value: gate.media } ]
						: []
				} );

				await page.navigate( base + '?' + gate.query );

				const ok = await page.waitFor( 'typeof window.__auto === "function"' );
				if ( ! ok ) { throw new Error( 'صحنه آماده نشد' ); }

				const a = await page.evaluate( 'JSON.stringify(__auto())' );
				const auto = a ? JSON.parse( a ) : null;

				const bad = judge( auto, gate.expect );

				if ( ! gate.expect && auto && true === auto.stored ) {
					bad.push( 'سهمیهٔ نشست خرج شد' );
				}
				if ( gate.expect && auto && true !== auto.stored ) {
					bad.push( 'در نشست ثبت نشد' );
				}

				if ( gate.also && auto ) {
					const extra = gate.also( auto );
					if ( true !== extra ) { bad.push( extra ); }
				}

				rows.push( { group: 'gate', label: gate.label, bad, auto } );

			} finally {
				await page.close();
			}
		}

		/* ── سفرِ نشست: یک برگه، چهار پیمایش ───────────────────────── */

		const page = await browser.open( 'about:blank' );

		try {
			for ( const stop of TRIP ) {

				await page.navigate( base + '?' + stop.query );

				const ok = await page.waitFor( 'typeof window.__auto === "function"' );
				if ( ! ok ) { throw new Error( 'صحنه آماده نشد' ); }

				const a = await page.evaluate( 'JSON.stringify(__auto())' );
				const auto = a ? JSON.parse( a ) : null;

				rows.push( {
					group: 'trip',
					label: stop.label,
					bad: judge( auto, stop.expect ),
					auto
				} );
			}
		} finally {
			await page.close();
		}

	} finally {
		await browser.close();
	}

	/* ── گزارش ─────────────────────────────────────────────────── */

	let failures = 0;

	console.log( '\nدروازهٔ حرکت و سهمیهٔ نشست — در مرورگر' );
	console.log( '─'.repeat( 78 ) );

	let group = '';

	for ( const row of rows ) {

		if ( row.group !== group ) {
			group = row.group;
			console.log(
				'gate' === group
					? '  سه مانع — لایه نباید ساخته شود'
					: '\n  سهمیهٔ نشست — کلید به وضعیت است نه به نشست'
			);
		}

		const bad = row.bad.length > 0;
		if ( bad ) { failures ++; }

		console.log( `    ${bad ? '❌' : '✅'} ${row.label}` );

		for ( const b of row.bad ) { console.log( `         ↳ ${b}` ); }
	}

	console.log( '─'.repeat( 78 ) );
	console.log( `  مورد: ${rows.length}   افتاده: ${failures}` );

	process.exit( failures ? 1 : 0 );
}

main().catch( e => fail( 'خطا: ' + ( e && e.message ? e.message : e ) ) );
