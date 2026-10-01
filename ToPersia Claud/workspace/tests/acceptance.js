/**
 * پذیرش قالب — مرجع در برابر صفحهٔ ما، با getComputedStyle.  P5-D219
 *
 * چشمی نیست: برای هر عنصر متناظر (.x در مرجع ↔ .tp-x در ما) همان
 * ویژگی‌هایی که خواسته شده سنجیده و مقایسه می‌شوند. متن‌ها مقایسه
 * نمی‌شوند. سه ترکیب: کروم ۱۴۴۰ · کروم ۳۹۰ · فایرفاکس ۱۴۴۰.
 *
 *   node tests/acceptance.js <پوشهٔ صفحه‌ها>
 *
 * این تنها جای پروژه است که Playwright به کار می‌رود — خواستهٔ صریح
 * بند ۶. هارنس کنتراست روی CDP می‌ماند.
 *
 * تناظر
 * ─────
 * هر عنصر با «مسیرش» از نزدیک‌ترین بخشِ شناسه‌دار شناخته می‌شود:
 * شناسه و کلاس‌های مرجع با پیشوند `tp-` نگاشته می‌شوند، پس مسیرِ دو سو
 * هم‌شکل است. nامین عنصرِ یک مسیر در مرجع با nامین همان مسیر در ما جفت
 * می‌شود. شمار موارد به داده بستگی دارد (مرجع پنج جاذبه دارد، دادهٔ
 * آزمون سه‌تا)، پس فقط جفت‌های موجود در هر دو سو مقایسه می‌شوند.
 *
 * رنگ
 * ───
 * هر رنگ از هر دو سو از یک بوم می‌گذرد و به rgba عددی می‌رسد — کروم
 * `color-mix` را `color(srgb …)` برمی‌گرداند و فایرفاکس `rgb(…)`.
 *
 * ناهمخوانیِ رنگی که عیناً یکی از جفت‌های «جدول نگاشت رنگ متن» باشد
 * جداگانه شمرده می‌شود: آن جایگزینی عمدی و ثبت‌شده است (P5-D188 ·
 * P5-D184 · P5-D191) و فهرستش در گزارش می‌آید. هر ناهمخوانی دیگری —
 * چه رنگ، چه اندازه، چه چیدمان — شکست است.
 */
'use strict';

const path = require( 'path' );
const fs   = require( 'fs' );
const { chromium, firefox } = require( 'playwright' );

const DIR = path.resolve( process.argv[2] || '.' );

/*
 * صفحهٔ واقعیِ نسخهٔ محلی — ۴٫۲٫۳ بند ۱ · P5-D203.
 *   node tests/acceptance.js <پوشه> http://topersia.local/province/isfahan/
 * «ما» از همان نشانی می‌آید: پشتهٔ CSS واقعی، دادهٔ واقعی، کلاس‌های بدنهٔ
 * واقعی. درخواست به همان میزبان باز است و به هر جای دیگر بسته.
 */
const OURS      = process.argv[3] || '';
const OURS_HOST = OURS ? new URL( OURS ).host : '';

const RUNS = [
	{ name: 'کروم ۱۴۴۰',    engine: chromium, width: 1440, height: 900 },
	{ name: 'کروم ۳۹۰',     engine: chromium, width: 390,  height: 844 },
	{ name: 'فایرفاکس ۱۴۴۰', engine: firefox,  width: 1440, height: 900 },
];

/* ─── داخل صفحه اجرا می‌شود ─────────────────────────────────────────── */
function collect( side ) {

	const PROPS = [
		'font-size', 'font-weight', 'line-height', 'color', 'background-color',
		'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
		'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
		'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width',
		'border-top-style', 'border-right-style', 'border-bottom-style', 'border-left-style',
		'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color',
		'border-top-left-radius', 'border-top-right-radius',
		'border-bottom-right-radius', 'border-bottom-left-radius',
		'display', 'grid-template-columns', 'row-gap', 'column-gap',
		/* سایه در فهرست خواستهٔ بند ۶ نبود؛ افزوده شد چون نگاشت «هم‌مقدار» سایه‌ها را هم در بر می‌گیرد */
		'box-shadow',
	];

	/* ارتفاع فقط برای این سه — خواستهٔ بند ۶ */
	const HEIGHT_OF = [ 'hero', 'gallery-main', 'card-slide__art' ];

	const cv = document.createElement( 'canvas' );
	cv.width = cv.height = 1;
	const cx = cv.getContext( '2d', { willReadFrequently: true } );

	function rgba( s ) {
		if ( ! s || 'transparent' === s ) { return '0,0,0,0'; }
		cx.clearRect( 0, 0, 1, 1 );
		cx.fillStyle = '#000';
		cx.fillStyle = s;
		cx.fillRect( 0, 0, 1, 1 );
		const d = cx.getImageData( 0, 0, 1, 1 ).data;
		/* آلفا از خودِ رشته، نه از پیکسل — روی بوم خالی دقیق‌تر است */
		const a = Math.round( d[3] / 255 * 100 ) / 100;
		if ( 0 === a ) { return '0,0,0,0'; }
		return [ d[0], d[1], d[2], a ].join( ',' );
	}

	/* نام مرجع → نام ما */
	/* همان جدول برخوردِ مولد: `.tp-more`ِ ازپیش‌پیشونددارِ مرجع نام تازه گرفت. */
	const COLLIDE = { 'tp-more': 'tp-more-rest' };

	function map( name ) {
		if ( 'ref' !== side ) { return name; }
		if ( COLLIDE[ name ] ) { return COLLIDE[ name ]; }
		return 0 === name.indexOf( 'tp-' ) ? name : 'tp-' + name;
	}

	/*
	 * کلاس‌هایی که ما هرگز جفتشان را نداریم یا وضعیت‌اند، از کلید بیرون می‌مانند.
	 * `tp-justify` (P5-D270، ۴٫۴٫۱) فقط تراز را عوض می‌کند؛ بی این، «دربارهٔ»،
	 * پاسخ پرسش‌ها و خروجی هوش مصنوعی امضای تازه می‌گرفتند و بی‌صدا از جفت
	 * بیرون می‌افتادند — همان رخدادی که یک بار نیمی از قالب را از سنجش بیرون برد.
	 */
	const IGNORE = /^tp-justify$/;

	/*
	 * امضای یک عنصر: مجموعهٔ کلاس‌هایش، و فقط اگر کلاس ندارد نام برچسبش.
	 *
	 * برچسب عمداً در امضای عنصرِ کلاس‌دار نیست: `.ai__engine` در مرجع
	 * `<button>` است و در ما `<a>` (موتور بیرونی باز می‌شود)، و `.tp-gps-btn`
	 * به حکمِ P5-D207 پیوند است. سبک از کلاس می‌آید؛ اگر برچسب در کلید بود،
	 * این جفت‌ها اصلاً سنجیده نمی‌شدند.
	 */
	function sig( el ) {
		const cls = Array.from( el.classList ).map( map ).filter( c => ! IGNORE.test( c ) ).sort();
		return cls.length ? '.' + cls.join( '.' ) : el.tagName.toLowerCase();
	}

	/*
	 * نوار پایین، برگهٔ بخش‌ها و همهٔ مودال‌ها از P5-D228 در ظرفی در body‌اند
	 * (`.tp-page.tp-portal`). اگر فقط نخستین `.tp-page` خوانده می‌شد، همهٔ
	 * آن‌ها بی‌صدا از سنجش بیرون می‌ماندند — همان خطایی که یک بار نیمی از
	 * قالب را بیرون گذاشت. ظرف هم ریشه است، با همان کلیدِ `page`.
	 */
	const roots = 'ref' === side ? [ document.body ] : Array.from( document.querySelectorAll( '.tp-page' ) );
	const out   = {};

	/* بخش‌ها و پوستهٔ مرجع که منتقل نشدند */
	const SKIP = 'ref' === side ? '.top, .foot, .skip, script, style, svg *' : 'script, style, svg *';

	for ( const root of roots ) {
	for ( const el of root.querySelectorAll( '*' ) ) {

		if ( el.matches( SKIP ) ) { continue; }
		if ( el.closest( 'ref' === side ? '.top, .foot' : '.tp-x-none' ) ) { continue; }

		/*
		 * مسیر از نزدیک‌ترین **بخشِ** شناسه‌دار — نه هر شناسه‌ای.
		 *
		 * نسخهٔ اول روی هر شناسه لنگر می‌انداخت. شناسه‌های قلابِ
		 * جاوااسکریپتِ مرجع (`sliderAttractions` · `sectionNav` · `aiOut` ·
		 * `openMap` …) با شناسه‌های ما یکی نیستند، پس زیردرختشان کلیدِ
		 * دیگری می‌گرفت و **هیچ‌وقت جفت نمی‌شد**: همهٔ `.card-slide` ها،
		 * نوار آمار و نوار بخش‌ها بی‌صدا از سنجش بیرون ماندند و صفرِ جدول
		 * بخشی‌اش خالی بود. شناسهٔ بخش‌ها اما یک‌به‌یک نگاشته می‌شود
		 * (`#ai` ← `#tp-ai`) و همان لنگرِ قابل‌اعتماد است.
		 */
		const chain = [];
		let cur = el;
		let anchor = '';

		while ( cur && cur !== root ) {
			if ( cur !== el && 'SECTION' === cur.tagName && cur.id ) {
				anchor = '#' + map( cur.id );
				break;
			}
			/* خودِ بخش هم با شناسه‌اش جفت می‌شود، نه با شمارهٔ ترتیبش —
			   وگرنه ششمین بخش مرجع (اخبار) با ششمین بخش ما (نظرها) جفت می‌شد. */
			chain.unshift( sig( cur ) + ( 'SECTION' === cur.tagName && cur.id ? '#' + map( cur.id ) : '' ) );
			cur = cur.parentElement;
		}

		const key = ( anchor || 'page' ) + ' ' + chain.join( ' > ' );
		const cs  = getComputedStyle( el );
		const rec = {};

		for ( const p of PROPS ) {
			let v = cs.getPropertyValue( p );
			if ( /color$/.test( p ) ) { v = rgba( v ); }
			rec[ p ] = v;
		}

		/* شمار فرزندان — شبکهٔ ضمنی به ازای هر فرزند یک ستون می‌سازد */
		rec._kids = el.childElementCount;

		const cls = Array.from( el.classList ).map( map );
		if ( HEIGHT_OF.some( h => cls.indexOf( 'tp-' + h ) > -1 ) ) {
			rec.height = Math.round( el.getBoundingClientRect().height * 10 ) / 10 + 'px';
		}

		/* رنگ مرز وقتی مرزی نیست، معنا ندارد و از رنگ متن ارث می‌برد */
		for ( const s of [ 'top', 'right', 'bottom', 'left' ] ) {
			if ( 'none' === rec[ 'border-' + s + '-style' ] ) { delete rec[ 'border-' + s + '-color' ]; }
		}

		( out[ key ] = out[ key ] || [] ).push( rec );
	}
	}

	return out;
}

/* ─── جفت‌های رنگِ عمدی ────────────────────────────────────────────── */
function allowedPairs( page ) {

	return page.evaluate( () => {

		const cv = document.createElement( 'canvas' );
		cv.width = cv.height = 1;
		const cx = cv.getContext( '2d', { willReadFrequently: true } );

		function rgba( s ) {
			cx.clearRect( 0, 0, 1, 1 );
			cx.fillStyle = '#000';
			cx.fillStyle = s;
			cx.fillRect( 0, 0, 1, 1 );
			const d = cx.getImageData( 0, 0, 1, 1 ).data;
			const a = Math.round( d[3] / 255 * 100 ) / 100;
			if ( 0 === a ) { return '0,0,0,0'; }
			return [ d[0], d[1], d[2], a ].join( ',' );
		}

		function tok( name ) {
			const probe = document.createElement( 'span' );
			probe.style.color = 'var(' + name + ')';
			document.querySelector( '.tp-page' ).appendChild( probe );
			const v = getComputedStyle( probe ).color;
			probe.remove();
			return rgba( v );
		}

		/*
		 * «جدول نگاشت رنگ متن» — همان جدول‌های مولد:
		 *   $TEXT_VARS · $SMALL_ON_2 · ایراد `.tp-altnames` مرجع
		 * سمت چپ مقدار روزِ مرجع است، سمت راست توکنِ ما.
		 */
		const P = [
			[ '#177E7A', '--tp-primary-text',  'رنگ متن برند ← --tp-primary-text  P5-D188' ],
			[ '#3F7C4E', '--tp-success-text',  'رنگ متن ok ← --tp-success-text  P5-D188' ],
			[ '#B9871A', '--tp-accent-text',   'رنگ متن طلایی ← --tp-accent-text  P5-D188' ],
			[ '#8b938d', '--tp-text-2',        'muted-2 ← --tp-text-2 (۲٫۷۵ بود)' ],
			[ '#6F6B64', '--tp-text',          'متن ریز روی سطح دوم ← --tp-text  P5-D184' ],
			[ '#8b938d', '--tp-text',          'متن ریز روی سطح دوم ← --tp-text  P5-D184' ],
			[ '#6F6B64', 'rgba(255,255,255,.76)', 'ایراد مرجع: .tp-altnames روی سربرگ  P5-D187' ],
			[ '#177E7A', '--tp-s-spring-text', 'متن فصل ← نسخهٔ متن  P5-D153' ],
			[ '#B9871A', '--tp-s-summer-text', 'متن فصل ← نسخهٔ متن  P5-D153' ],
			[ '#98502B', '--tp-s-autumn-text', 'متن فصل ← نسخهٔ متن  P5-D153' ],
			[ '#2E6E8E', '--tp-s-winter-text', 'متن فصل ← نسخهٔ متن  P5-D153' ],
		];

		return P.map( p => ( {
			from: rgba( p[0] ),
			to: 0 === p[1].indexOf( '--' ) ? tok( p[1] ) : rgba( p[1] ),
			why: p[2],
		} ) );
	} );
}

/*
 * ناهمخوانیِ عمدیِ غیررنگی — تصمیمِ ثبت‌شده، نه خطا.
 *
 * هر مورد دقیق است: کدام اجرا، کدام کلید، کدام ویژگی، و هر دو مقدار. اگر
 * مقدار دیگری دربیاید — مثلاً نوار روی موبایل `flex` شود — شکست است.
 */
/*
 * P5-D228 نوار بخش‌ها را روی موبایل دوباره پنهان کرد — همان مرجع — پس سه
 * موردِ P5-D223 («نوار روی موبایل دیده و چسبان») از این فهرست رفتند.
 */
const HEAD_SECTIONS = /^#tp-[a-z-]+ \.tp-section-head$/;
const EVERY_RUN     = [ 'کروم ۱۴۴۰', 'کروم ۳۹۰', 'فایرفاکس ۱۴۴۰' ];

const INTENT = [
	/* ۴٫۲٫۳ بند ۳ — فاصلهٔ کارت‌های موبایل. مرجع `gap:0` داشت و فاصله را
	   روی انتخاب‌گری گذاشته بود که هرگز نمی‌خورد؛ حالا ۲۸ روی شبکه، و
	   margin پایینِ بخش صفر تا دو برابر نشود. */
	{
		run: 'کروم ۳۹۰', key: /^page \.tp-main > \.tp-wrap > \.tp-content-grid$/, prop: 'row-gap', ref: '0px', ours: '28px',
		why: '۴٫۲٫۳ بند ۳ — فاصلهٔ ردیفیِ ۲۸ روی شبکهٔ موبایل',
	},
	{
		run: 'کروم ۳۹۰', key: /^page \.tp-main > \.tp-wrap > \.tp-content-grid > \.tp-main-col > (?:\.[a-z-]+)*\.tp-section#tp-[a-z-]+$/,
		prop: 'margin-bottom', ref: '52px', ours: '0px',
		why: '۴٫۲٫۳ بند ۳ — margin پایینِ بخش صفر؛ فاصله از شبکه می‌آید',
	},
	/* ۴٫۲٫۳ بند ۴ — سرفصل ردیفی روی موبایل: h2 راست، .more چپ، راهنما زیرشان.
	   مرجع ستونی‌اش می‌کرد. ستون‌های شبکه به پهنای متن بسته‌اند، پس الگو. */
	{ run: 'کروم ۳۹۰', key: HEAD_SECTIONS, prop: 'display', ref: 'flex', ours: 'grid', why: '۴٫۲٫۳ بند ۴ — سرفصل ردیفی روی موبایل' },
	{ run: 'کروم ۳۹۰', key: HEAD_SECTIONS, prop: 'row-gap', ref: '8px', ours: '4px', why: '۴٫۲٫۳ بند ۴ — سرفصل ردیفی روی موبایل' },
	{ run: 'کروم ۳۹۰', key: HEAD_SECTIONS, prop: 'column-gap', ref: '8px', ours: '12px', why: '۴٫۲٫۳ بند ۴ — سرفصل ردیفی روی موبایل' },
	{ run: 'کروم ۳۹۰', key: HEAD_SECTIONS, prop: 'grid-template-columns', ref: 'none', ours: /^\d+(?:\.\d+)?px \d+(?:\.\d+)?px$/, why: '۴٫۲٫۳ بند ۴ — سرفصل ردیفی روی موبایل' },
	{ run: 'کروم ۳۹۰', key: /^#tp-[a-z-]+ \.tp-section-head > div$/, prop: 'display', ref: 'block', ours: 'contents', why: '۴٫۲٫۳ بند ۴ — h2 و راهنما آیتمِ همان شبکه‌اند' },
].concat( EVERY_RUN.map( run => ( {
	/* P5-D229 — ردیف «شناسهٔ موجودیت» (`b.tp-lat`، ۷۰۰) رفت؛ نخستین `.tp-lat`
	   ما حالا پیوندِ «مرجع خارجی» است (۴۰۰) و با همان ردیفِ رفتهٔ مرجع جفت می‌شود. */
	run, key: /^#tp-sources \.tp-rail-card__body > \.tp-uid > div > \.tp-lat$/, prop: 'font-weight', ref: '700', ours: '400',
	why: 'P5-D229 — ردیف «شناسهٔ موجودیت» حذف شد',
} ) ) ).concat( ( () => {

	/*
	 * ۴٫۴٫۰ — ناهمخوانی‌هایی که SPRINT-4.4 فاز ۱ خواسته؛ هر کدام دقیق.
	 *
	 * P5-D245 توکن کاشی: «هم‌مرز»، «مسیرها»، «اطلاعات سفر» و کاشی‌های تازهٔ
	 *   P5-D244 (که با کاشی `.neighbors` مرجع جفت می‌شوند — twins پایین) از
	 *   --tp-surface-2 بی‌قاب (#efe9dd) به کاشی روزِ هوا: --tp-bg (#FAF7F1)،
	 *   قاب ۱ پیکسلیِ --tp-border، شعاع ۱۲. تراشهٔ خانوادهٔ فرهنگ از قرص
	 *   شفافِ ۹۹۹ پیکسلی با قاب نیمه‌شفاف به همان کاشی. همه در حالت روزِ مرجع.
	 * P5-D246 عنوان کارت اسلایدر دو خطی است — display:-webkit-box با line-clamp،
	 *   که هر دو موتور `flow-root` گزارش می‌کنند. توضیح از ۴٫۴٫۱ نیست (P5-D266).
	 *
	 * P5-D266 (۴٫۴٫۱) هنر در جریان صفحه با بلندی ثابت ۲۰۰ (موبایل ۱۸۰) به‌جای
	 *   ۱۶۲ ِ absolute؛ بدنه ستونِ flex با بلندی ثابت؛ پاورقی با margin خودکار
	 *   ته بدنه — مقدار حل‌شده‌اش از اندازهٔ متن می‌آید، پس الگو.
	 */
	const TILE    = /^#tp-[a-z-]+ \.tp-rail-card__body > \.tp-(?:(?:practical-grid|neighbors) > a|tiles > \.tp-tile)$/;
	const CULT    = /^#tp-culture \.tp-culture-grid > \.tp-culture-card > \.tp-culture-card__list > li > a$/;
	const CLAMP   = /^#tp-[a-z-]+ \.tp-slider-wrap > \.tp-slider > \.tp-card-slide > \.tp-card-slide__body > \.tp-card-slide__title$/;
	const SLIDE   = '^#tp-[a-z-]+ \\.tp-slider-wrap > \\.tp-slider > \\.tp-card-slide > \\.tp-card-slide__';
	const W266    = 'P5-D266 — کارت اسلایدر: هنر در جریان با بلندی دیدنی ثابت، بدنهٔ ثابت، بی توضیح';
	const SIDES   = [ 'top', 'right', 'bottom', 'left' ];
	const CORNERS = [ 'top-left', 'top-right', 'bottom-right', 'bottom-left' ];
	const W245    = 'P5-D245 — کاشی از توکن کاشی (--tp-tile-*)';

	const rows = [].concat(
		[ { key: TILE, prop: 'background-color', ref: '239,233,221,1', ours: '250,247,241,1' } ],
		SIDES.map( s => ( { key: TILE, prop: 'border-' + s + '-width', ref: '0px', ours: '1px' } ) ),
		SIDES.map( s => ( { key: TILE, prop: 'border-' + s + '-style', ref: 'none', ours: 'solid' } ) ),
		/* شعاع مرجع هم‌مرز ۱۱. مسیرها (۱۳ در مرجع) از P5-D269 کاشی‌اند و با هم‌مرز جفت می‌شوند */
		CORNERS.map( c => ( { key: TILE, prop: 'border-' + c + '-radius', ref: '11px', ours: '12px' } ) ),
		[ { key: CULT, prop: 'background-color', ref: '0,0,0,0', ours: '250,247,241,1' } ],
		SIDES.map( s => ( { key: CULT, prop: 'border-' + s + '-color', ref: '221,212,200,0.8', ours: '221,213,200,1' } ) ),
		CORNERS.map( c => ( { key: CULT, prop: 'border-' + c + '-radius', ref: '999px', ours: '12px' } ) )
	).map( r => Object.assign( r, { why: W245 } ) ).concat(
		[ { key: CLAMP, prop: 'display', ref: 'block', ours: 'flow-root', why: 'P5-D246 — عنوان کارت دو خطی (line-clamp)' } ],
		[
			{ key: new RegExp( SLIDE + 'body$' ), prop: 'display', ref: 'block', ours: 'flex', why: W266 },
			{ key: new RegExp( SLIDE + 'body > \\.tp-card-slide__foot$' ), prop: 'margin-top', ref: '14px', ours: /^\d{1,2}(?:\.\d+)?px$/, why: W266 + ' — پاورقی ته بدنه (margin خودکار)' },
		]
	);

	/* بلندی هنر: ۲۰۰ در ۱۴۴۰، ۱۸۰ در ۳۹۰ */
	const art = { key: new RegExp( SLIDE + 'art$' ), prop: 'height', ref: '162px', why: W266 };

	return [].concat(
		...EVERY_RUN.map( run => rows.map( r => Object.assign( { run }, r ) ) ),
		EVERY_RUN.map( run => Object.assign( { run, ours: 'کروم ۳۹۰' === run ? '180px' : '200px' }, art ) )
	);
} )() );

/*
 * P5-D244 — کاشی‌های ستون کناری «عیناً کاشی `.neighbors` مرجع»اند: «پهنه‌های
 * طبیعی»، «محوطه‌های باستانی»، «روستاها»، «اطلاعات جغرافیایی»، «نشان‌ها» و هر
 * شبکهٔ رابطهٔ ستون کناری. مرجع این کارت‌ها را با `.grid` و `.geo-grid`
 * ساخته بود، پس کاشیِ ما هم‌نامِ مرجعی ندارد و بی این جفت، بی‌صدا از سنجش
 * بیرون می‌ماند. هر کدام با کاشی «استان‌های هم‌مرز» مرجع جفت می‌شود: ظرف با
 * `.neighbors`، کاشی با `a`، برچسب با `.n-dir`، نام با `b`. اگر جفتِ مرجع
 * پیدا نشود، خودش یک ناهمخوانی است — نه رد شدنِ بی‌صدا.
 */
const TWINS = [
	[ /^#tp-[a-z-]+ \.tp-rail-card__body > \.tp-tiles$/, '#tp-neighbors .tp-rail-card__body > .tp-neighbors' ],
	[ /^#tp-[a-z-]+ \.tp-rail-card__body > \.tp-tiles > \.tp-tile$/, '#tp-neighbors .tp-rail-card__body > .tp-neighbors > a' ],
	[ /^#tp-[a-z-]+ \.tp-rail-card__body > \.tp-tiles > \.tp-tile > \.tp-tile__k$/, '#tp-neighbors .tp-rail-card__body > .tp-neighbors > a > .tp-n-dir' ],
	/* P5-D269 (۴٫۴٫۱) — زیرعنوان کاشی مسیر (ایستگاه‌ها یا شهر) «مثل استان‌های هم‌مرز» */
	[ /^#tp-[a-z-]+ \.tp-rail-card__body > \.tp-tiles > \.tp-tile > \.tp-tile__sub$/, '#tp-neighbors .tp-rail-card__body > .tp-neighbors > a > .tp-n-dir' ],
	[ /^#tp-[a-z-]+ \.tp-rail-card__body > \.tp-tiles > \.tp-tile > b$/, '#tp-neighbors .tp-rail-card__body > .tp-neighbors > a > b' ],
];

function twins( A, B ) {

	const missing = [];

	for ( const key of Object.keys( B ) ) {

		if ( A[ key ] ) { continue; }

		const t = TWINS.find( x => x[0].test( key ) );

		if ( ! t ) { continue; }

		if ( ! A[ t[1] ] ) { missing.push( key + ' ⇐ ' + t[1] ); continue; }

		A[ key ] = B[ key ].map( ( r, i ) => A[ t[1] ][ i % A[ t[1] ].length ] );
	}

	return missing;
}

/*
 * برابری دو مقدار محاسبه‌شده.
 *
 * طول‌ها با رواداری ۰٫۱ پیکسل — یک‌دهم پیکسلِ دستگاه، زیر هر چیزی که
 * چشم ببیند. کروم اندازه‌ها را در واحد ۱/۶۴ پیکسل گرد می‌کند و تقسیمِ `1fr`
 * میان سه ستون گاهی ۰٫۰۱۶ پیکسل جابه‌جا می‌شود بسته به اینکه ستون‌ها
 * پُرند یا خالی. هر اختلافی بزرگ‌تر از این، شکست است.
 */
function same( x, y ) {

	if ( x === y ) { return true; }

	const nx = String( x ).match( /-?\d+(\.\d+)?px/g );
	const ny = String( y ).match( /-?\d+(\.\d+)?px/g );

	if ( ! nx || ! ny || nx.length !== ny.length ) { return false; }
	if ( String( x ).replace( /-?\d+(\.\d+)?px/g, '#' ) !== String( y ).replace( /-?\d+(\.\d+)?px/g, '#' ) ) { return false; }

	for ( let i = 0; i < nx.length; i++ ) {
		if ( Math.abs( parseFloat( nx[ i ] ) - parseFloat( ny[ i ] ) ) > 0.1 ) { return false; }
	}

	return true;
}

/*
 * اسکرولِ واقعیِ چرخ روی صفحهٔ واقعی — هر سه ترکیب، فایرفاکس هم.
 * ۴٫۲٫۲ بند ۳: «پرش صفحه هنگام اسکرول به بالا باید در اسکرول واقعی صفر
 * باشد.» مرورگر کاربر فایرفاکس است و هارنسِ CDP فقط کروم را می‌راند؛
 * Playwright این‌جا هست، پس همین‌جا. هر گامِ چرخ رو به بالا باید صفحه را
 * همان اندازهٔ گامِ نخست جابه‌جا کند؛ و اگر نوار چسبیده است، بالایش همان
 * پایینِ هدری است که جاوااسکریپت اندازه گرفته.
 */
async function realScroll( page, run ) {

	await page.evaluate( () => { document.documentElement.style.scrollBehavior = 'auto'; scrollTo( 0, 0 ); } );
	await page.mouse.move( Math.round( run.width / 2 ), Math.round( run.height / 2 ) );

	for ( let i = 0; i < 12; i++ ) { await page.mouse.wheel( 0, 300 ); await page.waitForTimeout( 140 ); }
	await page.waitForTimeout( 1000 );

	const steps = [];

	for ( let i = 0; i < 8; i++ ) {
		const y0 = await page.evaluate( () => Math.round( scrollY ) );
		await page.mouse.wheel( 0, -120 );
		await page.waitForTimeout( 550 );
		const s = await page.evaluate( () => {
			const n = document.querySelector( '.tp-snav' ), p = document.querySelector( '.tp-page' );
			const hidden = ! n || 'none' === getComputedStyle( n ).display;
			return {
				y: Math.round( scrollY ), hidden, stuck: !! n && n.classList.contains( 'tp-is-stuck' ),
				navTop: n ? Math.round( n.getBoundingClientRect().top * 10 ) / 10 : 0,
				top: parseFloat( p.style.getPropertyValue( '--tp-snav-top' ) ) || 0,
			};
		} );
		steps.push( { d: s.y - y0, gap: s.hidden ? 0 : Math.round( ( s.navTop - s.top ) * 10 ) / 10, stuck: s.stuck } );
	}

	const base = steps[0].d;

	return {
		steps,
		jump: base < 0 ? steps.filter( s => Math.abs( s.d - base ) > 2 ).length : steps.length,
		gap: steps.filter( s => s.stuck && Math.abs( s.gap ) > 1 ).length,
	};
}

async function one( run ) {

	const browser = await run.engine.launch();

	try {

		const ctx = await browser.newContext( {
			viewport: { width: run.width, height: run.height },
			deviceScaleFactor: 1,
			reducedMotion: 'reduce',
		} );

		/* هیچ درخواست بیرونی: قلم و عکس و اسکریپت گوگل. دو سو یکسان.
		   صفحهٔ واقعی: فقط میزبانِ خودش باز است. */
		await ctx.route( /^https?:\/\//, r => ( OURS_HOST && new URL( r.request().url() ).host === OURS_HOST ) ? r.continue() : r.abort() );

		const ref = await ctx.newPage();
		await ref.goto( 'file:///' + path.join( DIR, 'reference.html' ).replace( /\\/g, '/' ) );
		await ref.waitForTimeout( 900 );

		const ours = await ctx.newPage();
		if ( OURS ) {
			await ours.goto( OURS, { waitUntil: 'load', timeout: 180000 } );
			await ours.waitForTimeout( 1500 );
			/* روزهای هوا پس از پاسخ /weather چیده می‌شوند و تا آن موقع سه
			   اسکلتِ `.tp-wx__day.tp-is-load` جایشان است. پاسخ نسخهٔ محلی چند
			   ثانیه پس از load می‌رسد (و اگر کش پروکسی کهنه باشد، پس از MET
			   Norway)؛ بی این انتظار، کارت هوا در یک ترکیب سنجیده می‌شد و در
			   دیگری نه. پاسخِ ناموفق اسکلت‌ها را نگه می‌دارد؛ پس سقف دارد.
			   ۴٫۳٫۰ بند ۲ */
			await ours.waitForFunction( () => {
				const box = document.querySelector( '[data-tp-weather]' );
				return ! box || box.querySelector( '.tp-wx__day:not(.tp-is-load)' );
			}, null, { timeout: 60000 } ).catch( () => {} );
		} else {
			await ours.goto( 'file:///' + path.join( DIR, 'province.html' ).replace( /\\/g, '/' ) );
			await ours.waitForTimeout( 900 );
		}

		const A = await ref.evaluate( collect, 'ref' );
		const B = await ours.evaluate( collect, 'ours' );
		const allow = await allowedPairs( ours );
		const lost  = twins( A, B );   /* P5-D244 */

		const diffs = lost.map( k => ( { key: k, prop: 'twin', ref: 'کاشی .neighbors مرجع', ours: 'پیدا نشد' } ) ), expected = [];
		let compared = 0, pairs = 0;
		const onlyRef = [], onlyOurs = [];

		for ( const key of Object.keys( A ) ) {

			if ( ! B[ key ] ) { onlyRef.push( key ); continue; }

			const n = Math.min( A[ key ].length, B[ key ].length );

			for ( let i = 0; i < n; i++ ) {

				pairs++;

				const a = A[ key ][ i ], b = B[ key ][ i ];

				for ( const p of Object.keys( a ) ) {

					if ( '_' === p.charAt( 0 ) || ! ( p in b ) ) { continue; }

					/*
					 * ستون‌های شبکهٔ ضمنی (اسلایدر، شبکهٔ کارت‌ها) به شمار فرزندان
					 * بسته‌اند: مرجع پنج جاذبه دارد و دادهٔ آزمون سه، پس فهرست
					 * ستون‌ها فرق دارد بی‌آنکه سبکی فرق کرده باشد. فقط وقتی
					 * سنجیده می‌شود که شمار فرزندان یکی باشد.
					 */
					if ( 'grid-template-columns' === p && a._kids !== b._kids ) { continue; }

					compared++;

					if ( same( a[ p ], b[ p ] ) ) { continue; }

					/*
					 * صفحهٔ واقعی: پهنای ستونِ `auto` و `1fr` را متنِ خودِ داده
					 * می‌سازد — ردیف روایت در مرجع «۴:۱۲» دارد و در داده چیز دیگری.
					 * آنچه سبک است شمارِ ستون‌هاست، نه پهنای حل‌شده‌شان.
					 */
					if ( OURS && 'grid-template-columns' === p
						&& String( a[ p ] ).split( ' ' ).length === String( b[ p ] ).split( ' ' ).length ) { continue; }

					const row = { key: key + ( n > 1 ? ' [' + ( i + 1 ) + ']' : '' ), prop: p, ref: a[ p ], ours: b[ p ] };

					const hit = /color$/.test( p ) && allow.find( x => x.from === a[ p ] && x.to === b[ p ] );
					const meant = INTENT.find( x => x.run === run.name && x.key.test( key ) && x.prop === p
						&& x.ref === a[ p ] && ( x.ours instanceof RegExp ? x.ours.test( b[ p ] ) : x.ours === b[ p ] ) );

					if ( hit ) { row.why = hit.why; expected.push( row ); }
					else if ( meant ) { row.why = meant.why; expected.push( row ); }
					else { diffs.push( row ); }
				}
			}
		}

		for ( const key of Object.keys( B ) ) {
			if ( ! A[ key ] ) { onlyOurs.push( key ); }
		}

		const motion = OURS ? await realScroll( ours, run ) : null;

		return { run: run.name, pairs, compared, diffs, expected, onlyRef, onlyOurs, motion };

	} finally {
		await browser.close();
	}
}

( async () => {

	const all = [];

	for ( const run of RUNS ) {
		all.push( await one( run ) );
	}

	const report = OURS ? 'acceptance-' + OURS.replace( /[^a-z0-9]+/gi, '-' ).replace( /^-+|-+$/g, '' ) + '.json' : 'acceptance.json';
	fs.writeFileSync( path.join( DIR, report ), JSON.stringify( all, null, 1 ) );

	let bad = 0;

	for ( const r of all ) {
		const m = r.motion
			? `\tاسکرول واقعی: گام ${ r.motion.steps[0].d }px · پرش ${ r.motion.jump } · فاصلهٔ نوار ${ r.motion.gap }`
			: '';
		console.log( `${ r.run }\tجفت ${ r.pairs }\tسنجش ${ r.compared }\tناهمخوان ${ r.diffs.length }\tعمدی ${ r.expected.length }${ m }` );
		bad += r.diffs.length + ( r.motion ? r.motion.jump + r.motion.gap : 0 );
	}

	process.exit( bad ? 1 : 0 );
} )().catch( e => { console.error( e ); process.exit( 2 ); } );
