#!/usr/bin/env node
/**
 * کنتراست متن سربرگ زیر انیمیشن هوا — روی CSS تولیدی.  P5-D197
 *
 * چرا جدا از `contrast.php`: کاوشگر آن‌جا زمینه را از **نیاکان** عنصر
 * می‌سازد، و لایهٔ انیمیشن نیایِ متن نیست — خواهرِ `.tp-hero__in` است،
 * مثل خود `overlay` و `fallback`. پس آن کاوشگر لایه را اصلاً نمی‌بیند و
 * با انیمیشن و بی‌آن عدد یکسان می‌دهد. آن سبز، شاهد نیست.
 *
 * این‌جا به‌جای حدس، پیکسل واقعی خوانده می‌شود: از سربرگ عکس گرفته
 * می‌شود در حالی که **متن پنهان است**، پس آنچه می‌ماند دقیقاً زمینه‌ای
 * است که متن رویش می‌نشیند — با همهٔ لایه‌ها، گرادیان‌ها و فلاش.
 *
 * کُند است (نزدیک هشتاد ثانیه) و برای همین در مسیر تند نیست:
 * `php tests/contrast.php --deep` صدایش می‌زند.
 *
 * اجرا:  node tests/wx-contrast-check.js <chrome> <file-url صحنه>
 */

'use strict';

const { launch } = require( './cdp.js' );

const MODES = [ 'light', 'evening', 'dark' ];

/*
 * `TP_WX_ONLY=snow` فقط همان وضعیت را می‌سنجد — برای وقتی که یک مقدار
 * کالیبره می‌شود و اجرای کامل نود ثانیه‌ای هر بار لازم نیست. پایه همیشه
 * می‌ماند چون بدون آن «نسبت به پایه» بی‌معنا می‌شود.
 */
const ONLY = ( process.env.TP_WX_ONLY || '' ).trim();
const STATES = [ null, 'rain', 'snow', 'storm', 'dust', 'fog' ]
	.filter( s => ! ONLY || null === s || s === ONLY );
const TEXT_MIN = 4.5;

/* چند نمونه به ازای هر ترکیب — ذره‌ها تصادفی‌اند و یک فریم حکم نیست */
const SAMPLES = 6;

function fail( m ) { process.stderr.write( m + '\n' ); process.exit( 2 ); }

async function main() {

	const chrome = process.argv[ 2 ];
	const url = process.argv[ 3 ];

	if ( ! chrome || ! url ) {
		fail( 'اجرا: node tests/wx-contrast-check.js <chrome> <file-url صحنه>' );
	}

	const browser = await launch( chrome );
	const rows = [];

	try {

		const page = await browser.open( url );

		if ( ! await page.waitFor( 'typeof window.__setup === "function"', 20000 ) ) {
			throw new Error( 'صحنه آماده نشد — دارایی‌ها کنار فایل هستند؟' );
		}

		/** یک عکس از سربرگ با متنِ پنهان، و بدترین پیکسل زیر هر متن. */
		async function shoot() {

			const targets = await page.evaluate( 'JSON.stringify(__targets())' );
			const box = JSON.parse( await page.evaluate( 'JSON.stringify(__heroRect())' ) );

			await page.evaluate( '__hideText(true)' );
			await new Promise( r => setTimeout( r, 40 ) );

			const shot = await page.send( 'Page.captureScreenshot', {
				format: 'png',
				clip: { x: box.x, y: box.y, width: box.width, height: box.height, scale: 1 },
				captureBeyondViewport: true
			} );

			await page.evaluate( '__hideText(false)' );

			return JSON.parse( await page.evaluate(
				`__measure("data:image/png;base64,${shot.data}", ${targets}).then(r => JSON.stringify(r))`
			) );
		}

		function keepWorst( into, measured ) {
			for ( const it of measured ) {
				const prev = into.get( it.name );
				if ( ! prev || it.worst < prev.worst ) { into.set( it.name, it ); }
			}
		}

		for ( const mode of MODES ) {
			for ( const state of STATES ) {

				const row = { mode, state, look: null, gate: null };

				/* ── ظاهر: چند فریم تصادفی، فقط برای دیدن ────────────
				   دیگر دروازه نیست. همان ترکیب یک اجرا ۵٫۰۵ می‌داد و
				   اجرای بعد ۵٫۶۴، بی‌آنکه خطی از کد عوض شده باشد. */
				let mounted = await page.evaluate(
					`__setup(${JSON.stringify( mode )}, ${JSON.stringify( state )})`
				);

				if ( state && ! mounted ) {
					row.error = 'لایه ساخته نشد';
					rows.push( row );
					continue;
				}

				const look = new Map();

				for ( let sample = 0; sample < SAMPLES; sample ++ ) {
					await new Promise( r => setTimeout( r, 0 === sample ? 420 : 240 ) );
					/* طوفان را نیمی از نمونه‌ها با برقِ روشن می‌بیند */
					if ( 'storm' === state ) { await page.evaluate( `__flash(${sample % 2 === 0})` ); }
					keepWorst( look, await shoot() );
				}

				row.look = Array.from( look.values() );

				if ( ! state ) {
					/* بی‌انیمیشن چیزی برای پرکردن نیست — همین پایه است */
					row.gate = row.look;
					rows.push( row );
					continue;
				}

				/* ── دروازه: فریم سقف، قطعی ──────────────────────────
				   لایه از نو سوار می‌شود تا بومِ زنده سر جایش باشد، بعد
				   با رنگ ذره در بیشینهٔ آلفا پر می‌شود.  P5-D199 */
				mounted = await page.evaluate(
					`__setup(${JSON.stringify( mode )}, ${JSON.stringify( state )})`
				);

				const peak = JSON.parse(
					await page.evaluate( 'JSON.stringify(__worst())' ) || 'null'
				);

				if ( ! mounted || ! peak ) {
					row.error = 'فریم سقف ساخته نشد';
					rows.push( row );
					continue;
				}

				row.peak = peak;
				await new Promise( r => setTimeout( r, 120 ) );

				const gate = new Map();
				keepWorst( gate, await shoot() );
				row.gate = Array.from( gate.values() );

				rows.push( row );
			}
		}

		await page.close();

	} finally {
		await browser.close();
	}

	/* ── گزارش ─────────────────────────────────────────────────── */

	const modeName = { light: 'روز', evening: 'عصر', dark: 'شب' };
	const worstOf = items => Math.min( ...items.map( i => i.worst ) );

	let worstGate = Infinity, failures = 0, broken = 0;
	const base = {};

	console.log( '\nکنتراست متن سربرگ زیر انیمیشن هوا — CSS تولیدی، پیکسل واقعی' );
	console.log( '─'.repeat( 78 ) );
	console.log( '  آستانه ۴٫۵ · دروازه «سقف» است: رنگ ذره در بیشینهٔ آلفا، تمام سطح' );
	console.log( '  «نمونه» شش فریم تصادفی است و فقط برای دیدن — حکم نمی‌دهد  P5-D199\n' );

	for ( const row of rows ) {

		const label = row.state || 'بدون انیمیشن';

		if ( row.error ) {
			console.log( `  ⚠ ${modeName[ row.mode ]} · ${label} — ${row.error}` );
			failures ++;
			continue;
		}

		const gate = worstOf( row.gate );
		const look = worstOf( row.look );

		if ( gate < worstGate ) { worstGate = gate; }
		if ( ! row.state ) { base[ row.mode ] = gate; }

		const delta = base[ row.mode ] !== undefined && row.state
			? ( gate - base[ row.mode ] ).toFixed( 2 ).padStart( 6 )
			: '     —';

		const bad = gate < TEXT_MIN;
		if ( bad ) { failures ++; }

		console.log(
			`  ${bad ? '❌' : '✅'} ${modeName[ row.mode ].padEnd( 4 )} ${label.padEnd( 14 )}` +
			`سقف ${String( gate.toFixed( 2 ) ).padStart( 6 )}   ` +
			`نمونه ${String( look.toFixed( 2 ) ).padStart( 6 )}   نسبت به پایه ${delta}`
		);

		if ( bad ) {
			for ( const it of row.gate ) {
				if ( it.worst < TEXT_MIN ) {
					console.log( `       ↳ ${it.name} ${it.size}px  ${it.worst}  پیکسل ${it.worstPixel}` );
				}
			}
		}

		/*
		 * خودآزمونِ فرضِ P5-D199.
		 *
		 * ادعا این است که سقف بالادستِ هر فریم واقعی است. اگر نمونه‌ای
		 * تصادفی بدتر از سقف درآمد، ادعا غلط است و دروازه بی‌اعتبار —
		 * نه اینکه آن ترکیب بد باشد. این باید بلند فریاد بزند.
		 */
		if ( row.state && look < gate - 0.05 ) {
			broken ++;
			console.log(
				`       ‼ نمونهٔ تصادفی (${look.toFixed( 2 )}) از سقف (${gate.toFixed( 2 )}) بدتر`
				+ ' درآمد — فرضِ سقف غلط است، دروازه بی‌اعتبار'
			);
		}
	}

	console.log( '─'.repeat( 78 ) );
	console.log( `  کمترین سقف: ${worstGate.toFixed( 2 )}   افتاده: ${failures}` );

	if ( broken ) {
		console.log( `  ‼ فرضِ سقف در ${broken} ترکیب نقض شد — عددها حکم نیستند` );
	}

	process.exit( failures || broken ? 1 : 0 );
}

main().catch( e => fail( 'خطا: ' + ( e && e.message ? e.message : e ) ) );
