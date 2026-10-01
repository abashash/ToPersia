/*!
 * ToPersia — نمایش انیمیشن هوا روی سربرگ.  P5-D197
 *
 * نیم‌رخ را از `topersia-g-weather.js` می‌گیرد و می‌کشد. پنج وضعیت،
 * سه تکنیک:
 *
 *   rain · storm   رگه روی canvas
 *   snow           دانۀ گرد روی canvas، با تاب جانبی
 *   dust           دو لایۀ بافت که افقی می‌لغزند — ذره نیست، پرده است
 *   fog            هیچ ذره‌ای؛ شیب ماتی که با CSS نفس می‌کشد
 *
 * برقِ storm فلاش CSS است نه ذره، و زمان‌بندی‌اش عمداً نامنظم:
 * دو فلاش نزدیک، بعد مکث بلند. برق دوره‌ای فوراً مصنوعی دیده می‌شود.
 *
 * پنج ثانیه، بعد محو، بعد همه‌چیز برداشته می‌شود — نه `display:none`
 * با حلقه‌ای که پشتش می‌چرخد.
 */
( function ( global ) {
	'use strict';

	var FX = global.TopersiaWeatherFx;

	if ( ! FX || typeof document === 'undefined' ) { return; }

	function rand( a, b ) { return a + Math.random() * ( b - a ); }

	/*
	 * رنگ و سقف پراکندگی از پایۀ مشترک می‌آیند، نه از عدد خام این‌جا.
	 * آزمونِ بدترین‌حالت از همان‌ها می‌خواند و اگر دو نسخه شوند، روزی
	 * یکی عوض می‌شود و آزمون بی‌صدا خوش‌بین می‌ماند.  P5-D199
	 */
	function dustRgb() { return FX.PARTICLE.dust.rgb; }
	function jitter( prof ) { return FX.PARTICLE[ prof.state ].jitter; }

	/** بافت غبار — یک‌بار ساخته می‌شود و بعد فقط می‌لغزد. */
	function dustTexture( w, h ) {

		var c = document.createElement( 'canvas' );
		c.width = Math.max( 2, Math.round( w ) );
		c.height = Math.max( 2, Math.round( h ) );

		var g = c.getContext( '2d' );

		/* رشته‌های افقیِ کشیده و نرم. تکِ ذره دیده نمی‌شود؛ آنچه
		   می‌ماند حس پرده است. */
		for ( var i = 0; i < 90; i++ ) {

			var y  = rand( 0, c.height );
			var len = rand( c.width * 0.18, c.width * 0.62 );
			var x  = rand( -len * 0.3, c.width );
			var th = rand( 6, 26 );

			var grad = g.createLinearGradient( x, y, x + len, y );
			grad.addColorStop( 0, 'rgba(' + dustRgb() + ',0)' );
			grad.addColorStop( 0.5, 'rgba(' + dustRgb() + ',' + rand( 0.05, 0.16 ).toFixed( 3 ) + ')' );
			grad.addColorStop( 1, 'rgba(' + dustRgb() + ',0)' );

			g.fillStyle = grad;
			g.fillRect( x, y - th / 2, len, th );
		}

		return c;
	}

	/**
	 * لایه را می‌سازد و می‌چرخاند.
	 *
	 * @param {Element} hero  خودِ .tp-hero
	 * @param {Object}  wx    پاسخ هوا
	 * @return {Object|null}  کنترل‌کننده، یا null اگر نباید ساخته شود
	 */
	function mount( hero, wx ) {

		if ( ! hero ) { return null; }
		if ( ! FX.motionAllowed() ) { return null; }

		var overlay = hero.querySelector( '.tp-hero__overlay' );
		var rect = hero.getBoundingClientRect();
		var prof = FX.profile( wx, { width: rect.width, height: rect.height } );

		if ( ! prof ) { return null; }
		if ( ! FX.firstTimeFor( prof.state ) ) { return null; }

		var layer = document.createElement( 'div' );
		layer.className = 'tp-wx-fx tp-wx-fx--' + prof.state;
		layer.setAttribute( 'aria-hidden', 'true' );

		var canvas = null, ctx = null;

		if ( 'fog' !== prof.state ) {
			canvas = document.createElement( 'canvas' );
			layer.appendChild( canvas );
		}

		var flash = null;

		if ( 'storm' === prof.state ) {
			flash = document.createElement( 'div' );
			flash.className = 'tp-wx-fx__flash';
			layer.appendChild( flash );
		}

		/* پیش از overlay تزریق می‌شود تا زیرش بنشیند و کنتراست متن
		   سربرگ دست‌نخورده بماند */
		hero.insertBefore( layer, overlay );

		var dpr = Math.min( global.devicePixelRatio || 1, 2 );
		var w = rect.width, h = rect.height;

		if ( canvas ) {
			canvas.width = Math.round( w * dpr );
			canvas.height = Math.round( h * dpr );
			ctx = canvas.getContext( '2d' );
			ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );
		}

		/* ── دانه‌ها ──────────────────────────────────────────────── */

		var parts = [];
		var texture = null;
		var offsets = [];

		if ( 'rain' === prof.state || 'storm' === prof.state ) {
			for ( var i = 0; i < prof.count; i++ ) {
				parts.push( {
					x: rand( 0, w ), y: rand( -h, h ),
					len: prof.length * rand( 0.7, 1.3 ),
					v: prof.speed * rand( 0.8, 1.2 ),
					a: prof.alpha * rand( 0.6, jitter( prof ) )
				} );
			}
		} else if ( 'snow' === prof.state ) {
			for ( var s = 0; s < prof.count; s++ ) {
				parts.push( {
					x: rand( 0, w ), y: rand( -h, h ),
					r: prof.radius * rand( 0.65, 1.35 ),
					v: prof.speed * rand( 0.75, 1.25 ),
					a: prof.alpha * rand( 0.6, jitter( prof ) ),
					phase: rand( 0, Math.PI * 2 ),
					hz: prof.swayHz * rand( 0.7, 1.3 )
				} );
			}
		} else if ( 'dust' === prof.state ) {
			texture = dustTexture( w, h );
			for ( var d = 0; d < prof.layers; d++ ) {
				offsets.push( {
					x: -d * ( w / prof.layers ),
					v: prof.speed * ( 1 + d * 0.45 ),
					a: FX.dustLayerAlpha( prof.alpha, d )
				} );
			}
		}

		/* ── حلقه ─────────────────────────────────────────────────── */

		var raf = 0, running = true, stopped = false;
		var startedAt = 0, last = 0;
		var fadeTimer = 0, flashTimer = 0;

		var tan = Math.tan( prof.tilt );
		var rgb = FX.PARTICLE[ prof.state ].rgb;

		function drawRain( dt ) {
			ctx.clearRect( 0, 0, w, h );
			ctx.lineCap = 'round';
			ctx.lineWidth = prof.width;
			for ( var i = 0; i < parts.length; i++ ) {
				var p = parts[ i ];
				p.y += p.v * dt;
				p.x += p.v * tan * dt;
				if ( p.y - p.len > h ) { p.y = -p.len; p.x = rand( 0, w ); }
				if ( p.x < -40 ) { p.x = w + 40; } else if ( p.x > w + 40 ) { p.x = -40; }
				ctx.strokeStyle = 'rgba(' + rgb + ',' + p.a.toFixed( 3 ) + ')';
				ctx.beginPath();
				ctx.moveTo( p.x, p.y );
				ctx.lineTo( p.x - p.len * tan, p.y - p.len );
				ctx.stroke();
			}
		}

		function drawSnow( dt, now ) {
			ctx.clearRect( 0, 0, w, h );
			var t = now / 1000;
			for ( var i = 0; i < parts.length; i++ ) {
				var p = parts[ i ];
				p.y += p.v * dt;
				/* رانش باد به‌اضافۀ تابِ آرام — نه لرزش */
				p.x += p.v * tan * dt + Math.cos( t * p.hz * Math.PI * 2 + p.phase ) * prof.sway * dt;
				if ( p.y - p.r > h ) { p.y = -p.r; p.x = rand( 0, w ); }
				if ( p.x < -20 ) { p.x = w + 20; } else if ( p.x > w + 20 ) { p.x = -20; }
				ctx.fillStyle = 'rgba(' + rgb + ',' + p.a.toFixed( 3 ) + ')';
				ctx.beginPath();
				ctx.arc( p.x, p.y, p.r, 0, Math.PI * 2 );
				ctx.fill();
			}
		}

		function drawDust( dt ) {
			ctx.clearRect( 0, 0, w, h );
			for ( var i = 0; i < offsets.length; i++ ) {
				var o = offsets[ i ];
				o.x -= o.v * dt;
				if ( o.x <= -w ) { o.x += w; }
				ctx.globalAlpha = o.a;
				ctx.drawImage( texture, o.x, 0, w, h );
				ctx.drawImage( texture, o.x + w, 0, w, h );
			}
			ctx.globalAlpha = 1;
		}

		function frame( now ) {

			if ( ! running ) { return; }

			if ( ! startedAt ) { startedAt = now; last = now; }

			var dt = Math.min( ( now - last ) / 1000, 0.05 );
			last = now;

			if ( now - startedAt >= FX.RUN_MS ) { stop(); return; }

			if ( 'snow' === prof.state ) { drawSnow( dt, now ); }
			else if ( 'dust' === prof.state ) { drawDust( dt ); }
			else { drawRain( dt ); }

			raf = global.requestAnimationFrame( frame );
		}

		/* برق — نامنظم. دو فلاش نزدیک، بعد مکثی بلند. */
		function scheduleFlash() {

			if ( ! running || ! flash ) { return; }

			flash.classList.add( 'is-lit' );

			flashTimer = global.setTimeout( function () {

				if ( ! flash ) { return; }
				flash.classList.remove( 'is-lit' );

				/* گاهی بلافاصله یکی دیگر، گاهی نه */
				var twin = Math.random() < 0.55;
				var gap = twin ? rand( 90, 180 ) : rand( 900, 2200 );

				flashTimer = global.setTimeout( scheduleFlash, gap );

			}, rand( 55, 110 ) );
		}

		function teardown() {
			if ( layer && layer.parentNode ) { layer.parentNode.removeChild( layer ); }
			layer = null; canvas = null; ctx = null;
			parts = []; offsets = []; texture = null; flash = null;
		}

		function stop() {

			if ( stopped ) { return; }
			stopped = true;
			running = false;

			if ( raf ) { global.cancelAnimationFrame( raf ); raf = 0; }
			if ( flashTimer ) { global.clearTimeout( flashTimer ); flashTimer = 0; }

			if ( layer ) {
				layer.classList.remove( 'is-on' );
				fadeTimer = global.setTimeout( teardown, FX.FADE_MS );
			}

			document.removeEventListener( 'visibilitychange', onHide );
		}

		function onHide() {
			if ( document.hidden ) { stop(); }
		}

		document.addEventListener( 'visibilitychange', onHide );

		/* مه حلقه ندارد — CSS نفس می‌کشد */
		if ( 'fog' === prof.state ) {
			layer.style.setProperty( '--tp-fog-a', prof.alpha.toFixed( 3 ) );
			layer.style.setProperty( '--tp-fog-ms', prof.breathMs + 'ms' );
			global.setTimeout( stop, FX.RUN_MS );
		} else {
			raf = global.requestAnimationFrame( frame );
		}

		if ( 'storm' === prof.state ) {
			flashTimer = global.setTimeout( scheduleFlash, rand( 300, 900 ) );
		}

		global.requestAnimationFrame( function () {
			if ( layer ) { layer.classList.add( 'is-on' ); }
		} );

		return {
			state: prof.state,
			profile: prof,
			stop: stop,
			isRunning: function () { return running; },
			hasLayer: function () { return !! layer; }
		};
	}

	FX.mount = mount;
	FX.dustTexture = dustTexture;

}( typeof window !== 'undefined' ? window : this ) );
