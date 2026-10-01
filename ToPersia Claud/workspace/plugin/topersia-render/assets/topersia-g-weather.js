/*!
 * ToPersia — پایۀ مشترک انیمیشن هوا.  P5-D197
 *
 * دو نیمه دارد که عمداً از هم جدا مانده‌اند:
 *
 *   نیم‌رخ   ریاضی خالص، بدون DOM. پاسخ هوا را به عدد تبدیل می‌کند و
 *            با node مستقیم آزمون‌پذیر است.
 *   نمایش    فقط وقتی DOM هست. لایه را می‌سازد، می‌چرخاند، و کامل
 *            برش می‌دارد.
 *
 * هیچ‌چیزش در HTML اولیه نیست. سربرگ عنصر LCP است و تا پاسخ هوا نرسد
 * این فایل چیزی روی صفحه نمی‌گذارد.
 */
( function ( global ) {
	'use strict';

	/* فقط این پنج وضعیت انیمیشن دارند. clear و cloudy و hot و cold عمداً
	   هیچ‌چیز نشان نمی‌دهند — آسمان صاف که ذره ندارد. */
	var ANIMATED = { rain: 1, snow: 1, storm: 1, dust: 1, fog: 1 };

	var RUN_MS  = 5000;
	var FADE_MS = 600;

	/* سقف کجی. ۳۵ درجه بود و به ۲۸ رسید: بالاتر از آن تصویر کج به نظر
	   می‌رسد نه بادگیر. */
	var TILT_MAX_DEG = 28;

	/* سرعتی که کجی را اشباع می‌کند، و سرعتی که بزرگیِ باد را */
	var TILT_FULL_KPH = 40;
	var MAG_FULL_KPH  = 60;

	/**
	 * رنگ ذره و سقف پراکندگیِ آلفا در هر وضعیت.
	 *
	 * این‌جا می‌نشیند نه در رندرکننده، چون دو مصرف‌کننده دارد: کشیدن، و
	 * فریمِ بدترین‌حالتِ آزمون. دو نسخه یعنی روزی که یکی عوض شود و آزمون
	 * بی‌صدا خوش‌بین بماند — همان سبزِ بی‌معنی که `P5-D193` و `P5-D198`
	 * دربارۀ آن‌اند.  P5-D199
	 *
	 * رنگ مه فقط در CSS کشیده می‌شود؛ این‌جا برای همین قرینه است و یک
	 * ادعا می‌گوید با قاعدۀ `.tp-wx-fx--fog` یکی مانده.
	 */
	var PARTICLE = {
		rain:  { rgb: '255,255,255', jitter: 1.4 },
		storm: { rgb: '255,255,255', jitter: 1.4 },
		snow:  { rgb: '255,255,255', jitter: 1.3 },
		dust:  { rgb: '198,176,138', jitter: 1 },
		fog:   { rgb: '226,222,214', jitter: 1 }
	};

	/** آلفای لایۀ iاُمِ غبار — لایۀ دوم رقیق‌تر است. */
	function dustLayerAlpha( base, i ) { return base * ( 1 - i * 0.3 ); }

	function num( value, fallback ) {
		value = parseFloat( value );
		return isFinite( value ) ? value : fallback;
	}

	function clamp( value, low, high ) {
		return value < low ? low : ( value > high ? high : value );
	}

	function lerp( a, b, t ) { return a + ( b - a ) * t; }

	/**
	 * کجی از **مؤلفۀ شرقی-غربیِ** باد.
	 *
	 * جهت باد در هواشناسی «از کجا می‌وزد» است، پس بردار خودش به سمت
	 * deg+180 می‌رود و مؤلفۀ شرقی‌اش -sin(deg) است.
	 *
	 * باد شمالی یا جنوبی مؤلفۀ افقی ندارد، پس کجی نمی‌سازد — و همین
	 * درست است، چون نمای ما دوبعدی و از پهلوست. ولی سرعت را از این
	 * تابع نگیر؛ برای آن `magnitude` هست.
	 *
	 * باد صفر ⇒ کجی صفر. عمودی، نه کجِ پیش‌فرض.
	 */
	function tiltOf( windDeg, windKph ) {

		var speed = clamp( num( windKph, 0 ), 0, 200 );
		if ( speed < 1 ) { return 0; }

		var deg = num( windDeg, NaN );
		if ( ! isFinite( deg ) ) { return 0; }

		var east = -Math.sin( deg * Math.PI / 180 );
		var drift = east * clamp( speed / TILT_FULL_KPH, 0, 1 );

		return clamp(
			Math.atan( drift ),
			-TILT_MAX_DEG * Math.PI / 180,
			TILT_MAX_DEG * Math.PI / 180
		);
	}

	/**
	 * بزرگیِ باد، بی‌اعتنا به جهت — بین صفر و یک.
	 *
	 * این چیزی است که سرعت و کشیدگی از آن می‌آید. بدون این، باد ۶۰ از
	 * شمال باران «عمودیِ آرام» نشان می‌داد: کجی صفر درست بود، ولی
	 * آرام‌بودنش غلط.
	 */
	function magnitude( windKph ) {
		return clamp( num( windKph, 0 ) / MAG_FULL_KPH, 0, 1 );
	}

	/**
	 * شدت بارش، نرمال‌شده بین صفر و یک.
	 *
	 * مرزها از طبقه‌بندی متعارف: نم‌نم تا ۰٫۵، ملایم تا ۲٫۵، متوسط تا
	 * ۷٫۶، بالاتر رگبار. پیوسته است نه پله‌ای.
	 */
	function intensityOf( mmPerHour ) {
		return clamp( num( mmPerHour, 0 ) / 7.6, 0, 1 );
	}

	/** شب؟ پاسخ `is_day` را می‌دهد؛ هرچه غیر از صفر و false یعنی روز. */
	function isNight( wx ) {
		return 0 === wx.isDay || false === wx.isDay;
	}

	/**
	 * نیم‌رخ انیمیشن از پاسخ هوا.
	 *
	 * @param {Object} wx   state · windDeg · windKph · precipMm · isDay
	 * @param {Object} box  width · height
	 */
	function profile( wx, box ) {

		wx  = wx || {};
		box = box || { width: 0, height: 0 };

		if ( ! ANIMATED[ wx.state ] ) { return null; }

		var tilt = tiltOf( wx.windDeg, wx.windKph );
		var mag  = magnitude( wx.windKph );
		var i    = intensityOf( wx.precipMm );
		var area = Math.max( 0, num( box.width, 0 ) * num( box.height, 0 ) );
		var perArea = area / 3600;

		/* سربرگ شب تیره‌تر است و همان آلفا سنگین‌تر دیده می‌شود */
		var dim = isNight( wx ) ? 0.7 : 1;

		var out = {
			state: wx.state,
			tilt: tilt,
			intensity: i,
			wind: mag,
			night: isNight( wx )
		};

		if ( 'rain' === wx.state || 'storm' === wx.state ) {
			out.count  = Math.round( clamp( perArea * lerp( 0.35, 1.0, i ), 12, 150 ) );
			out.speed  = lerp( 300, 760, i ) * lerp( 1, 1.6, mag );
			out.length = lerp( 6, 22, i ) * lerp( 1, 1.5, mag );
			out.alpha  = lerp( 0.14, 0.42, i ) * dim;
			out.width  = lerp( 0.9, 1.4, i );

		} else if ( 'snow' === wx.state ) {
			/* برف بسیار کندتر می‌افتد و به‌جای رگه، دانۀ گرد است */
			out.count  = Math.round( clamp( perArea * lerp( 0.30, 0.8, i ), 10, 110 ) );
			out.speed  = lerp( 18, 46, i ) * lerp( 1, 1.35, mag );
			out.radius = lerp( 1.1, 2.6, i );
			/* بیشینه ۰٫۷۲ بود و به ۰٫۵۴ رسید.  P5-D204
			   برف تنها وضعیتی بود که سقفِ قطعی را زیر آستانه می‌برد —
			   `.tp-eyebrow` روز ۴٫۲۱ و شب ۴٫۴۱. دانۀ گرد و کند سطح
			   بیشتری از رگۀ باران می‌پوشاند، پس همان آلفا سنگین‌تر
			   می‌نشیند. توقف‌های ماسک دست نخورد چون هر پنج انیمیشن را
			   پوشش می‌دهند؛ فقط برف پایین آمد.
			   با ۰٫۵۴: روز ۴٫۶۳ · عصر ۵٫۵۷ · شب ۴٫۹۲ */
			out.alpha  = lerp( 0.34, 0.54, i ) * dim;
			out.sway   = lerp( 10, 26, i );          /* دامنۀ حرکت جانبی */
			out.swayHz = lerp( 0.12, 0.26, 1 - i );  /* آرام، نه لرزان */

		} else if ( 'dust' === wx.state ) {
			/* غبار ذره نیست؛ پرده است. دو لایۀ بافت که افقی می‌لغزند. */
			out.layers = 2;
			out.speed  = lerp( 26, 92, mag );        /* پیکسل بر ثانیه */
			out.alpha  = lerp( 0.16, 0.30, mag ) * dim;

		} else if ( 'fog' === wx.state ) {
			/* مه هیچ ذره‌ای ندارد. فقط شیب ماتی که آرام نفس می‌کشد. */
			out.alpha    = 0.22 * dim;
			out.breathMs = 4200;
		}

		return out;
	}

	/**
	 * روشن‌ترین پرکردنِ ممکن برای این نیم‌رخ — رنگ ذره در بیشینۀ آلفا،
	 * روی تمام سطح.  P5-D199
	 *
	 * بالادستِ هر چیدمان تصادفی است: هیچ فریم واقعی نمی‌تواند روشن‌تر از
	 * این شود. آزمونِ کنتراست به‌جای نمونه‌برداری از فریم‌های تصادفی،
	 * همین یکی را می‌سنجد — یک فریم، قطعی.
	 *
	 * چرا این کافی است و «روشن‌ترین» با «بدترین» یکی می‌شود: ماسک یک
	 * شیب است، پس همین فریم در ارتفاع‌های مختلفِ نوار متن **همۀ**
	 * آلفاهای بین صفر و سقف را می‌سازد. اگر جایی زمینه از روشناییِ متن
	 * رد شود، در همین یک فریم پیدا می‌شود.
	 *
	 * @return {Object|null}  rgb · alpha
	 */
	function peak( prof ) {

		if ( ! prof ) { return null; }

		var p = PARTICLE[ prof.state ];
		if ( ! p ) { return null; }

		var a = num( prof.alpha, 0 );

		if ( 'dust' === prof.state ) {
			/* دو لایه روی هم می‌نشینند: ۱−(۱−a₀)(۱−a₁) */
			a = 1 - ( 1 - dustLayerAlpha( a, 0 ) ) * ( 1 - dustLayerAlpha( a, 1 ) );
		} else {
			a = a * p.jitter;
		}

		return { rgb: p.rgb, alpha: clamp( a, 0, 1 ) };
	}

	/* ── از اینجا به بعد DOM لازم است ─────────────────────────────── */

	var hasDom = typeof document !== 'undefined' && !! document.documentElement;

	/**
	 * سه‌گانۀ حرکت. اگر هرکدام خاموشی خواست، لایه اصلاً ساخته نمی‌شود.
	 */
	function motionAllowed() {

		if ( ! hasDom ) { return false; }

		try {
			if ( global.matchMedia && global.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				return false;
			}
		} catch ( e ) {}

		var root = document.documentElement;

		if ( 'off' === root.getAttribute( 'data-motion' ) ) { return false; }
		if ( 'on' === root.getAttribute( 'data-calm' ) ) { return false; }

		return true;
	}

	/**
	 * یک بار در هر نشست، **به ازای هر وضعیت**.
	 *
	 * کلید به وضعیت است نه به نشست: اگر کاربر استان بارانی را ببیند و
	 * بعد کویر برفی را، هر دو باید یک‌بار دیده شوند.
	 */
	function firstTimeFor( state ) {

		var key = 'tp-wx-fx-' + state;

		try {
			if ( global.sessionStorage.getItem( key ) ) { return false; }
			global.sessionStorage.setItem( key, '1' );
		} catch ( e ) {
			/* حالت ناشناس یا ذخیره‌سازی بسته — بگذار اجرا شود */
		}

		return true;
	}

	global.TopersiaWeatherFx = {
		ANIMATED: ANIMATED,
		RUN_MS: RUN_MS,
		FADE_MS: FADE_MS,
		TILT_MAX_DEG: TILT_MAX_DEG,
		PARTICLE: PARTICLE,
		dustLayerAlpha: dustLayerAlpha,
		peak: peak,
		profile: profile,
		tiltOf: tiltOf,
		magnitude: magnitude,
		intensityOf: intensityOf,
		isNight: isNight,
		motionAllowed: motionAllowed,
		firstTimeFor: firstTimeFor
	};

}( typeof window !== 'undefined' ? window : this ) );
