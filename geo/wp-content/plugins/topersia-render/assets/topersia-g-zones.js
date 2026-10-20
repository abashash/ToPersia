/*!
 * ToPersia — رفتار قالب دستۀ G
 * نسخه: 3.0.0 — انتقال مستقیم از اسکریپت‌های فایل مرجع.  P5-D219
 *
 * منتقل شد: ارتفاع هدر · حالت فعال نوار بخش‌ها · اسلایدرها · مدیر مودال
 * (قفل body، Esc، دام فوکوس، کلیک بیرون) · گالری و نمای تک‌عکس · بلوک
 * هوش مصنوعی · نوار کنش و جابه‌جایی دسکتاپ/موبایل · نوار پایین موبایل و
 * برگۀ بخش‌ها · دکمۀ «N مورد دیگر» · هوای سه روز.
 *
 * منتقل نشد: دادۀ ساختگی مرجع — aiData · TP · TP_REL · نمونۀ هوا.
 * داده از PHP می‌آید، یا از نقطۀ REST.
 */
( function () {
	'use strict';

	var page = document.querySelector( '.tp-page' );

	if ( ! page ) {
		return;
	}

	/* ══ زبان صفحه — ۴٫۴٫۲ ═══════════════════════════════════════════════
	   پیام‌ها با منبع فارسی خواسته می‌شوند و ترجمه‌شان را PHP از کاتالوگ
	   `topersia` روی `TopersiaG.i18n` گذاشته (فارسی: جدول خالی). رقم به خطِ
	   صفحه: fa «۰–۹» · ar «٠–٩» · latn.
	   ────────────────────────────────────────────────────────────────── */
	var CFG    = window.TopersiaG || {};
	var I18N   = CFG.i18n || {};
	var DIGITS = { fa: '۰۱۲۳۴۵۶۷۸۹', ar: '٠١٢٣٤٥٦٧٨٩' }[ CFG.digits || 'fa' ] || '';

	function t( s ) {
		return Object.prototype.hasOwnProperty.call( I18N, s ) ? I18N[ s ] : s;
	}

	/* همان، برای جایی که نام `t` متغیرِ محلی است */
	var tr = t;

	function digits( v ) {
		return DIGITS ? String( v ).replace( /[0-9]/g, function ( d ) { return DIGITS[ d ]; } ) : String( v );
	}

	/* ══ لایۀ بیرونی — P5-D228 ═════════════════════════════════════════
	   `position:fixed` نسبت به نزدیک‌ترین نیایی حساب می‌شود که transform
	   یا filter یا contain دارد، نه نسبت به صفحه — و کانتینر المنتور گاهی
	   دارد. آن‌جا نوار پایین با صفحه «می‌لغزد». پس نوار پایین، برگۀ بخش‌ها
	   و همۀ مودال‌ها به body منتقل می‌شوند.

	   ظرفشان همان کلاس `tp-page` را دارد تا همۀ قاعده‌های `.tp-page …`
	   همچنان بخورند، و خودش `display:contents` است: جعبه نمی‌سازد، پس نه
	   padding صفحه را می‌گیرد و نه مبنای `fixed` می‌شود.
	   ────────────────────────────────────────────────────────────────── */
	var portal = document.createElement( 'div' );

	portal.className = 'tp-page tp-portal';

	[].slice.call( page.querySelectorAll( '.tp-mnav, .tp-sheet, .tp-modal, .tp-gallery-modal' ) ).forEach( function ( el ) {
		portal.appendChild( el );
	} );

	if ( portal.firstChild ) {
		document.body.appendChild( portal );
	}

	/* کلیکِ واگذارشده هم از صفحه می‌آید هم از لایۀ بیرونی. */
	function onBoth( type, fn ) {
		page.addEventListener( type, fn );
		portal.addEventListener( type, fn );
	}

	/* ══ هدر چسبان و نوار بخش‌ها — P5-D223 ════════════════════════════════
	   بالای نوار = پایینِ واقعیِ هدر چسبانِ وودمارت، بی‌فاصله.

	   «ارتفاع هدر» کافی نبود: وودمارت هدر را یا خودش می‌چسباند
	   (`.whb-sticked`) یا نسخۀ دومی از آن را از بالا می‌لغزاند
	   (`.whb-clone`)، و در حالت «پنهان هنگام اسکرول» هدر بالا و پایین
	   می‌رود. نوار مدیریت وردپرس هم ۳۲ پیکسل بالاتر از همه است. پس پایینِ
	   هر چیزِ ثابت یا چسبانی که همین حالا به لبۀ بالای صفحه چسبیده
	   اندازه گرفته می‌شود — در هر فریمِ اسکرول، و تا پایان هر گذار.

	   سه عدد روی `.tp-page` می‌نشیند:
	     --tp-snav-top   پایینِ هدر
	     --tp-header-h   همان — ستون کناری چسبان از آن می‌خواند
	     --tp-snav-h     بلندی نوار؛ لنگر هر بخش = هدر + نوار
	   ────────────────────────────────────────────────────────────────── */
	/* -۱ یعنی «هنوز اندازه نگرفته» — تا نخستین اندازه، حتی صفر، نوشته شود. */
	var snavTop = -1;
	var snavH   = -1;

	( function () {

		var nav   = page.querySelector( '.tp-snav' );
		var HEADS = '#wpadminbar, .whb-header, .whb-main-header, .whb-clone, .whb-sticky-row,'
			+ ' header, .site-header, #masthead';
		var heads = [];
		var stuck = null;
		var until = 0;
		var busy  = false;

		function collectHeads() {
			heads = [].slice.call( document.querySelectorAll( HEADS ) ).filter( function ( el ) {
				return ! page.contains( el );
			} );
		}

		/*
		 * پایینِ «پشتۀ بالا» — هر چیزِ ثابت یا چسبانی که به لبۀ بالا یا
		 * به پایینِ چیزِ بالاییِ دیگری چسبیده است.
		 *
		 * نوار مدیریت ۰ تا ۳۲ است و هدرِ وودمارت زیرش از ۳۲ شروع می‌شود؛
		 * شرطِ «بالایش صفر باشد» هدر را نمی‌شمرد و نوار زیرِ هدر می‌رفت.
		 * پس از بالا به پایین روی هم چیده می‌شوند و هر کدام که به پایینِ
		 * پشته برسد، پشته را بلندتر می‌کند.
		 */
		function headerBottom() {

			var boxes = [];

			for ( var i = 0; i < heads.length; i ++ ) {

				var cs = window.getComputedStyle( heads[ i ] );

				if ( 'fixed' !== cs.position && 'sticky' !== cs.position ) { continue; }
				if ( 'none' === cs.display || 'hidden' === cs.visibility || 0 === parseFloat( cs.opacity ) ) { continue; }

				var r = heads[ i ].getBoundingClientRect();

				if ( r.height >= 1 && r.bottom > 0 ) {
					boxes.push( r );
				}
			}

			boxes.sort( function ( a, b ) { return a.top - b.top; } );

			var bottom = 0;

			for ( var k = 0; k < boxes.length; k ++ ) {
				if ( boxes[ k ].top <= bottom + 1 && boxes[ k ].bottom > bottom ) {
					bottom = boxes[ k ].bottom;
				}
			}

			return Math.max( 0, Math.round( bottom ) );
		}

		/*
		 * همۀ خواندن‌ها اول، همۀ نوشتن‌ها بعد — P5-D370.  پیش از این `setProperty` وسطِ `offsetHeight` و
		 * `getBoundingClientRect` بود: هر خواندنِ پس از نوشتن، محاسبۀ سبکِ کل صفحه را همان‌دم اجبار می‌کرد
		 * (ردگیری موبایل ۴× کندتر: ۷ تا ۸ بار، ≈۱۰۵۰ms سبک در استان). حالا یک بار خوانده و یک بار نوشته می‌شود و
		 * محاسبۀ سبک تا فریمِ بعد می‌ماند. مقادیر همان‌اند؛ فقط ترتیب عوض شده.
		 */
		function apply() {

			/* ── خواندن ── */
			var top = headerBottom();
			var h   = nav ? nav.offsetHeight : 0;
			var now = null;

			if ( nav ) {

				/* چسبیده یعنی جای طبیعیِ نوار از خطِ چسبیدن بالاتر رفته. */
				var prev    = nav.previousElementSibling;
				var natural = prev
					? prev.getBoundingClientRect().bottom + ( parseFloat( window.getComputedStyle( nav ).marginTop ) || 0 )
					: nav.getBoundingClientRect().top;

				now = h > 0 && natural < top - 0.5;
			}

			/* ── نوشتن ── */
			if ( top !== snavTop ) {
				snavTop = top;
				page.style.setProperty( '--tp-snav-top', top + 'px' );
				page.style.setProperty( '--tp-header-h', top + 'px' );
			}

			if ( ! nav ) {
				return;
			}

			/* روی موبایل نوار پنهان است و بلندی‌اش صفر — لنگرِ بخش فقط هدر.  P5-D228 */
			if ( h !== snavH ) {
				snavH = h;
				page.style.setProperty( '--tp-snav-h', h + 'px' );
			}

			if ( now !== stuck ) {
				stuck = now;
				nav.classList.toggle( 'tp-is-stuck', now );
			}
		}

		/*
		 * گذارِ هدر وودمارت چند صد میلی‌ثانیه طول می‌کشد؛ تا پایانش هر فریم.
		 * فهرست هدرها سرِ هر دور تازه می‌شود: وودمارت نسخۀ دوم (`.whb-clone`)
		 * را با نخستین اسکرول می‌سازد، نه هنگام بارگذاری.
		 */
		function settle() {

			until = Date.now() + 450;

			/*
			 * فهرست هر بار تازه می‌شود، حتی وسطِ دوری که در جریان است: وودمارت
			 * نسخۀ دوم را درست هنگام اسکرول می‌سازد، و دورِ جاری با فهرستِ
			 * کهنه آن را نمی‌دید — نوار زیرِ هدر می‌ماند. عکسِ صحنه گرفتش.
			 */
			collectHeads();

			if ( busy ) {
				return;
			}

			busy = true;

			( function frame() {
				apply();
				if ( Date.now() < until ) {
					window.requestAnimationFrame( frame );
				} else {
					busy = false;
				}
			} )();
		}

		collectHeads();
		apply();

		window.addEventListener( 'scroll', settle, { passive: true } );
		window.addEventListener( 'resize', settle, { passive: true } );
		window.addEventListener( 'load', settle, { once: true } );

		/*
		 * چسبیدن و رهاشدن با کلاس یا سبکِ خودِ هدر عوض می‌شود، گاهی بی هیچ
		 * اسکرولی، و گاهی هدر دیرتر از این اسکریپت ساخته می‌شود. پس کل سند
		 * پاییده می‌شود ولی فقط تغییرِ درونِ یکی از هدرها — نه درونِ صفحۀ
		 * خودمان — دورِ اندازه‌گیری را راه می‌اندازد.
		 */
		if ( window.MutationObserver ) {

			new MutationObserver( function ( records ) {

				for ( var i = 0; i < records.length; i ++ ) {

					var t = records[ i ].target;

					if ( 1 !== t.nodeType || page.contains( t ) ) {
						continue;
					}

					if ( t.closest( HEADS ) ) {
						settle();
						return;
					}

					/* هدری که همین حالا به سند افزوده شد — مثل نسخۀ دومِ وودمارت */
					var added = records[ i ].addedNodes;

					for ( var j = 0; j < added.length; j ++ ) {
						if ( 1 === added[ j ].nodeType && ( added[ j ].matches( HEADS ) || added[ j ].querySelector( HEADS ) ) ) {
							settle();
							return;
						}
					}
				}
			} ).observe( document.body, {
				attributes: true, attributeFilter: [ 'class', 'style' ], childList: true, subtree: true,
			} );
		}
	} )();

	/* ══ حالت فعال نوار بخش‌ها ═══════════════════════════════════════════
	   فهرست بخش‌ها از خودِ پیوندهای نوار می‌آید، نه از یک آرایۀ ثابت:
	   نوار را PHP از ناحیه‌هایی می‌سازد که واقعاً محتوا داشتند، پس هر
	   فهرست ثابتی روی سیزده نوع پست غلط می‌شد.

	   ۲ه٫۱ · P5-D310 — حرکت نرم، ظاهر ایستا عیناً مرجع:
	   · کلیک ← scrollIntoView نرم؛ فاصلۀ سربرگ و نوار از scroll-margin-top بخش.
	   · بخش فعال با IntersectionObserver روی یک خط درست زیر نوار؛ تغییرها در
	     requestAnimationFrame جمع می‌شوند (بی لرزش در اسکرول تند).
	   · نشانگر فعال می‌لغزد: قرصی همان شکل پیوند فعال از جای پیشین با transform به
	     جای تازه می‌رود و پس از پایان کنار می‌رود — در حال سکون همان کلاس tp-is-active
	     مرجع است، پس سبک‌های محاسبه‌شده با مرجع یکی می‌مانند.
	   · prefers-reduced-motion ← همه فوری.
	   ────────────────────────────────────────────────────────────────── */
	( function () {

		var nav = page.querySelector( '.tp-snav' );

		if ( ! nav ) {
			return;
		}

		var links = [].slice.call( nav.querySelectorAll( 'a' ) );
		var byId  = {};
		var ids   = [];

		links.forEach( function ( a ) {

			var href = a.getAttribute( 'href' ) || '';

			if ( '#' === href.charAt( 0 ) ) {
				byId[ href.slice( 1 ) ] = a;
				ids.push( href.slice( 1 ) );
			}
		} );

		if ( ! ids.length ) {
			return;
		}

		var lockUntil = 0;
		var strip     = nav.querySelector( '.tp-snav__in' );
		var calmQuery = window.matchMedia ? window.matchMedia( '(prefers-reduced-motion: reduce)' ) : null;
		var current   = null;
		var ghost     = null;

		function calm() {
			return !! ( calmQuery && calmQuery.matches );
		}

		/*
		 * پیوند فعال به میانۀ نوار می‌آید — فقط افقی.  ۴٫۲٫۱ بند ۳
		 *
		 * پیش از این `scrollIntoView({block:'nearest'})` بود. «نزدیک‌ترین»
		 * برای عنصری درون نوار چسبان یعنی مرورگر گاهی **صفحه** را هم عمودی
		 * جابه‌جا می‌کرد تا پیوند را «دیدنی» کند — همان پرشِ هنگام اسکرول
		 * رو به بالا. حالا فقط خودِ نوار افقی می‌لغزد و صفحه دست نمی‌خورد.
		 */
		function reveal( link ) {

			if ( ! strip || strip.scrollWidth <= strip.clientWidth + 1 ) {
				return;
			}

			var box   = strip.getBoundingClientRect();
			var one   = link.getBoundingClientRect();
			var delta = ( one.left + one.width / 2 ) - ( box.left + box.width / 2 );

			if ( Math.abs( delta ) < 2 ) {
				return;
			}

			strip.scrollTo( { left: strip.scrollLeft + delta, behavior: calm() ? 'auto' : 'smooth' } );
		}

		/*
		 * قرص لغزان: هم‌شکل پیوند فعال پیشین (سبک محاسبه‌شدۀ همان)، درون نوار چسبان (جعبۀ
		 * موقعیت‌دار) و زیر پیوندها؛ با transform از قاب پیشین به قاب تازه می‌رود و در
		 * پایان کلاس فعال روی پیوند تازه می‌نشیند. فقط وقتی نوار افقی جا شده — وگرنه
		 * لغزیدن نرم خود نوار (reveal) همان حرکت است.
		 */
		function slide( from, to, done ) {

			if ( ghost ) {
				ghost.remove();
				ghost = null;
			}

			if ( calm() || ! from || ! to || from === to || ! strip || strip.scrollWidth > strip.clientWidth + 1 ) {
				done();
				return;
			}

			var base = nav.getBoundingClientRect();
			var a    = from.getBoundingClientRect();
			var b    = to.getBoundingClientRect();
			var cs   = window.getComputedStyle( from );

			ghost = document.createElement( 'span' );
			ghost.className = 'tp-snav__ghost';
			ghost.setAttribute( 'aria-hidden', 'true' );
			ghost.style.cssText = 'position:absolute;pointer-events:none;top:' + ( b.top - base.top ) + 'px;left:' + ( b.left - base.left ) + 'px;'
				+ 'width:' + b.width + 'px;height:' + b.height + 'px;border-radius:' + cs.borderRadius + ';background:' + cs.backgroundColor + ';box-shadow:' + cs.boxShadow + ';'
				+ 'transform-origin:0 50%;transform:translateX(' + ( a.left - b.left ) + 'px) scaleX(' + ( b.width ? a.width / b.width : 1 ) + ');';
			nav.insertBefore( ghost, nav.firstChild );

			var end = function () {
				if ( ! ghost ) { return; }
				ghost.remove();
				ghost = null;
				done();
			};

			window.requestAnimationFrame( function () {
				window.requestAnimationFrame( function () {
					if ( ! ghost ) { return; }
					ghost.style.transition = 'transform .28s cubic-bezier(.2,.7,.2,1)';
					ghost.style.transform  = 'none';
					ghost.addEventListener( 'transitionend', end, { once: true } );
					window.setTimeout( end, 450 );
				} );
			} );
		}

		function setActive( id ) {

			var link = byId[ id ];

			if ( ! link || link === current ) {
				return;
			}

			var prev = current;
			current  = link;

			reveal( link );
			slide( prev, link, function () {
				if ( current !== link ) { return; }
				links.forEach( function ( a ) { a.classList.toggle( 'tp-is-active', a === link ); } );
			} );

			/* پیوند پیشین فوراً عادی؛ قرص جای پس‌زمینه‌اش را تا پایان لغزش می‌گیرد */
			if ( prev && prev !== link ) {
				prev.classList.remove( 'tp-is-active' );
			}
		}
		/* خط بخش فعال: هدر + نوار + کمی نفس — همان آستانۀ پیشین */
		function line() {
			return Math.max( 0, snavTop ) + ( snavH > 0 ? snavH : nav.offsetHeight ) + 24;
		}

		var lockTimer = 0;

		function pick() {

			/* قفلِ پس از کلیک: پایانش دوباره بسنج — IntersectionObserver فقط هنگام عبور خبر می‌دهد، نه با اتمام قفل */
			if ( Date.now() < lockUntil ) {
				window.clearTimeout( lockTimer );
				lockTimer = window.setTimeout( later, lockUntil - Date.now() + 40 );
				return;
			}

			var threshold = line();
			var bestId    = null;
			var bestTop   = -Infinity;

			ids.forEach( function ( id ) {

				var el = document.getElementById( id );

				if ( ! el ) {
					return;
				}

				var top = el.getBoundingClientRect().top;

				if ( top <= threshold && top > bestTop ) {
					bestTop = top;
					bestId  = id;
				}
			} );

			setActive( bestId || ids[ 0 ] );
		}

		/* IntersectionObserver روی خط یک‌پیکسلی زیر نوار؛ هر عبور بخشی از خط ← یک pick در فریم بعد */
		var queued = false;
		function later() {
			if ( ! queued ) {
				queued = true;
				window.requestAnimationFrame( function () { queued = false; pick(); } );
			}
		}

		var io = null;
		function observe() {

			if ( ! ( 'IntersectionObserver' in window ) ) {
				return false;
			}

			if ( io ) {
				io.disconnect();
			}

			var top = Math.round( line() );
			var bottom = Math.max( 0, window.innerHeight - top - 1 );

			io = new IntersectionObserver( later, { rootMargin: '-' + top + 'px 0px -' + bottom + 'px 0px', threshold: 0 } );

			ids.forEach( function ( id ) {
				var el = document.getElementById( id );
				if ( el ) { io.observe( el ); }
			} );

			return true;
		}

		/*
		 * اسکرول نرم تا بخش، با فاصلۀ هدر چسبان و نوار — ۲ه٫۱ · P5-D310
		 *
		 * `scroll-margin-top` هنگام کلیک خوانده می‌شود، و بالای صفحه هدر وودمارت هنوز چسبان
		 * نیست (--tp-snav-top صفر) — پس اسکرولِ نرم ۵۱ پیکسل کوتاه می‌رسید و بخش زیر هدر
		 * می‌ماند (لنگر بومی هم همین‌طور بود). هدف را همین‌جا حساب می‌کنیم و تا پایان
		 * اسکرول هر بار که هدر عوض شد دوباره نشانه می‌گیریم.
		 */
		var goingTo = 0;

		/* هر ورودی کاربر اسکرولِ در جریان را رها می‌کند — صفحه را به بخش برنمی‌گردانیم */
		[ 'wheel', 'touchstart', 'keydown', 'mousedown' ].forEach( function ( type ) {
			window.addEventListener( type, function () { goingTo ++; }, { passive: true } );
		} );

		function goTo( el ) {

			var token  = ++goingTo;
			var began  = Date.now();
			var aim    = -1;
			var steadyAt = 0;
			var lastY  = window.pageYOffset || 0;
			var dir    = 0;
			var aimedAt = 0;

			/* بخشِ ستون کناریِ چسبان (sticky) هرگز بالای نوار نمی‌رسد: اگر در دید است نمی‌رویم، وگرنه یک‌بار — نه تعقیبِ بی‌پایان */
			function pinned() {
				for ( var p = el; p && p !== document.body; p = p.parentElement ) {
					if ( 'sticky' === window.getComputedStyle( p ).position ) {
						return true;
					}
				}
				return false;
			}

			var pin = pinned();

			function want() {
				return Math.max( 0, Math.round( el.getBoundingClientRect().top + ( window.pageYOffset || 0 )
					- ( Math.max( 0, snavTop ) + ( snavH > 0 ? snavH : nav.offsetHeight ) ) ) );
			}

			function step() {

				if ( token !== goingTo ) {
					return;
				}

				var here = window.pageYOffset || 0;

				/* صفحه را کس دیگری (اسکریپت، نوار پیمایش…) از مسیر ما بیرون برد ← رها کن؛ تعقیب تنها برای تغییر قدِ هدر است نه بازگرداندن صفحه */
				if ( aim >= 0 && ( ( dir && Date.now() - aimedAt > 400 && ( here - lastY ) * dir < -2 ) || ( steadyAt && Math.abs( here - lastY ) > 2 ) ) ) {
					goingTo ++;
					return;
				}

				lastY = here;

				var y = want();

				if ( pin && aim < 0 ) {
					var r  = el.getBoundingClientRect();
					var by = Math.max( 0, snavTop ) + ( snavH > 0 ? snavH : nav.offsetHeight );
					if ( r.top >= by && r.bottom <= window.innerHeight ) {
						return;
					}
				}

				if ( aim < 0 || ( ! pin && Math.abs( y - aim ) > 2 ) ) {
					aim    = y;
					aimedAt = Date.now();
					dir    = y > here ? 1 : ( y < here ? -1 : 0 );
					steadyAt = 0;
					window.scrollTo( { top: y, behavior: calm() ? 'auto' : 'smooth' } );
				} else if ( Math.abs( ( window.pageYOffset || 0 ) - aim ) <= 1 ) {
					steadyAt = steadyAt || Date.now();
				} else {
					steadyAt = 0;
				}

				/* رسیده و حدود ۶۰۰ms آرام ← پایان (نه شمار فریم: روی ماشین کند فریم‌ها دیر می‌آیند)؛ سقف ۱٫۸ ثانیه */
				if ( ( ! steadyAt || Date.now() - steadyAt <= 600 ) && Date.now() - began < 1800 ) {
					window.requestAnimationFrame( step );
				}
			}

			step();
		}

		links.forEach( function ( a ) {
			a.addEventListener( 'click', function ( e ) {
				var href = a.getAttribute( 'href' ) || '';
				if ( '#' !== href.charAt( 0 ) ) { return; }
				var el = document.getElementById( href.slice( 1 ) );
				if ( ! el ) { return; }
				e.preventDefault();
				setActive( href.slice( 1 ) );
				lockUntil = Date.now() + 1600;
				goTo( el );
				if ( window.history && window.history.replaceState ) {
					window.history.replaceState( null, '', href );
				}
			} );
		} );

		window.addEventListener( 'hashchange', function () {
			var id = ( location.hash || '' ).replace( '#', '' );
			if ( id ) {
				setActive( id );
				lockUntil = Date.now() + 1200;
				if ( byId[ id ] && document.getElementById( id ) ) { goTo( document.getElementById( id ) ); }
			}
		} );

		/* بی IntersectionObserver همان شنوندۀ اسکرول پیشین */
		if ( ! observe() ) {
			window.addEventListener( 'scroll', later, { passive: true } );
		}

		window.addEventListener( 'resize', function () { lockUntil = 0; observe(); later(); }, { passive: true } );

		if ( location.hash && byId[ location.hash.replace( '#', '' ) ] ) {
			setActive( location.hash.replace( '#', '' ) );
		} else {
			pick();
		}

		window.addEventListener( 'load', function () { observe(); pick(); }, { once: true } );
	} )();

	/* ══ اسلایدرها — P5-D243 (جایگزین P5-D226) ══════════════════════════
	   فلش به سمتی اشاره می‌کند که محتوای دیده‌نشده آن‌جاست: در راست‌به‌چپ
	   «بعدی» فلش چپ «‹» است و کارت‌های پنهانِ سمت چپ را وارد دید می‌کند؛ در
	   چپ‌به‌راست فلش راست «›» و کارت‌های سمت راست. جهت از `direction`
	   خودِ فهرست خوانده می‌شود، نه از زبان. در راست‌به‌چپ `scrollLeft` از
	   صفر رو به منفی می‌رود، پس «بعدی» آن‌جا منفی است و در چپ‌به‌راست مثبت.
	   دکمه‌ای که کاری نمی‌کند غیرفعال است: در آغاز «قبلی»، در پایان «بعدی»،
	   و اگر فهرست جا شد هر دو. جای کاربر با `Math.abs` خوانده می‌شود.
	   ────────────────────────────────────────────────────────────────── */
	( function () {

		var groups = {};

		Array.prototype.forEach.call( page.querySelectorAll( '[data-target]' ), function ( btn ) {

			var id = btn.getAttribute( 'data-target' );

			( groups[ id ] = groups[ id ] || [] ).push( btn );

			btn.addEventListener( 'click', function () {

				var el = document.getElementById( id );

				if ( ! el || btn.disabled ) {
					return;
				}

				var next   = '1' === btn.getAttribute( 'data-dir' );
				var rtl    = 'rtl' === window.getComputedStyle( el ).direction;
				var amount = Math.round( el.clientWidth * 0.85 ) * ( next === rtl ? -1 : 1 );

				el.scrollBy( { left: amount, behavior: 'smooth' } );
			} );
		} );

		Object.keys( groups ).forEach( function ( id ) {

			var el = document.getElementById( id );

			if ( ! el ) {
				return;
			}

			var btns    = groups[ id ];
			var pending = false;

			function sync() {

				pending = false;

				var max = el.scrollWidth - el.clientWidth;
				var pos = Math.abs( el.scrollLeft );

				btns.forEach( function ( b ) {

					var next = '1' === b.getAttribute( 'data-dir' );
					var off  = max <= 1 || ( next ? pos >= max - 1 : pos <= 1 );

					if ( off === b.disabled ) {
						return;
					}

					/* فوکوس روی دکمه‌ای که همین حالا غیرفعال شد، به همتایش می‌رود. */
					if ( off && document.activeElement === b ) {
						btns.forEach( function ( o ) { if ( o !== b ) { o.disabled = false; o.focus(); } } );
					}

					b.disabled = off;
				} );
			}

			function later() {
				if ( ! pending ) {
					pending = true;
					window.requestAnimationFrame( sync );
				}
			}

			el.addEventListener( 'scroll', later, { passive: true } );
			window.addEventListener( 'resize', later, { passive: true } );
			window.addEventListener( 'load', sync, { once: true } );
			sync();
		} );
	} )();

	/* ══ کشیدن اسلایدر با موس — P5-D277 ════════════════════════════════
	   فقط موس (`pointerType`): لمس و صفحه‌کلید همان رفتار بومی‌اند. پنج
	   پیکسل پیش از شروع، تا کلیکِ لرزان کلیک بماند. هنگام کشیدن چسبیدن
	   خاموش است (`tp-is-dragging`) و جابه‌جایی همان `scrollLeft` است، پس جهت
	   خودبه‌خود P5-D243 است: در راست‌به‌چپ کشیدن از چپ به راست = بعدی. پس از
	   رها کردن، به نزدیک‌ترین کارت می‌چسبد و فلش‌ها از رویداد `scroll` همان
	   بالا به‌روز می‌شوند. کلیکِ پس از کشیدن هیچ پیوندی را باز نمی‌کند.
	   ────────────────────────────────────────────────────────────────── */
	( function () {

		var STEP = 5;

		/* لبۀ آغاز نزدیک‌ترین کارت — `scroll-snap-align:start` در هر دو جهت. */
		function snap( el ) {

			var box  = el.getBoundingClientRect();
			var rtl  = 'rtl' === window.getComputedStyle( el ).direction;
			var best = null;

			Array.prototype.forEach.call( el.children, function ( card ) {
				var r = card.getBoundingClientRect();
				var d = rtl ? r.right - box.right : r.left - box.left;
				if ( null === best || Math.abs( d ) < Math.abs( best ) ) {
					best = d;
				}
			} );

			if ( null !== best && Math.abs( best ) > 1 ) {
				el.scrollBy( { left: best, behavior: 'smooth' } );
			}
		}

		Array.prototype.forEach.call( page.querySelectorAll( '.tp-slider' ), function ( el ) {

			var id = null, x0 = 0, s0 = 0, dragging = false, moved = false;

			/* تصویر و پیوندِ کشیدنیِ بومی شبح می‌سازد و رویدادهای اشاره‌گر را لغو می‌کند. */
			el.addEventListener( 'dragstart', function ( e ) { e.preventDefault(); } );

			el.addEventListener( 'pointerdown', function ( e ) {

				if ( 'mouse' !== e.pointerType || 0 !== e.button ) {
					return;
				}

				id = e.pointerId;
				x0 = e.clientX;
				s0 = el.scrollLeft;
				dragging = false;
				moved = false;
			} );

			el.addEventListener( 'pointermove', function ( e ) {

				if ( e.pointerId !== id ) {
					return;
				}

				var dx = e.clientX - x0;

				if ( ! dragging ) {

					if ( Math.abs( dx ) < STEP ) {
						return;
					}

					dragging = true;
					moved    = true;
					el.setPointerCapture( id );
					el.classList.add( 'tp-is-dragging' );
				}

				el.scrollLeft = s0 - dx;
			} );

			function end( e ) {

				if ( e.pointerId !== id ) {
					return;
				}

				id = null;

				if ( ! dragging ) {
					return;
				}

				dragging = false;
				el.classList.remove( 'tp-is-dragging' );
				snap( el );

				/* کلیک درست پس از pointerup می‌آید؛ اگر نیامد، کلیکِ بعدی (حتی Enter) نباید بیفتد. */
				window.setTimeout( function () { moved = false; }, 0 );
			}

			el.addEventListener( 'pointerup', end );
			el.addEventListener( 'pointercancel', end );

			/* کلیکِ پایانِ کشیدن — در فاز گرفتن، پیش از پیوند. */
			el.addEventListener( 'click', function ( e ) {
				if ( moved ) {
					moved = false;
					e.preventDefault();
					e.stopPropagation();
				}
			}, true );
		} );
	} )();

	/* ══ مدیر مودال ═════════════════════════════════════════════════════
	   قفل روی `body` و `documentElement` هر دو: iOS فقط دومی را می‌فهمد
	   و اندروید فقط اولی را.
	   ────────────────────────────────────────────────────────────────── */
	var lastFocus = null;
	var openStack = [];

	function openModal( modal, focusEl ) {

		if ( ! modal ) {
			return;
		}

		if ( ! openStack.length ) {
			lastFocus = document.activeElement;
			document.body.style.overflow = 'hidden';
			document.documentElement.style.overflow = 'hidden';
		}

		openStack.push( modal );
		modal.classList.add( 'tp-show' );

		if ( focusEl && focusEl.focus ) {
			focusEl.focus();
		}
	}

	function closeModal( modal ) {

		if ( ! modal ) {
			return;
		}

		modal.classList.remove( 'tp-show' );
		openStack = openStack.filter( function ( one ) { return one !== modal; } );

		/* مودالی که با بستن کاری دارد — مثل شاسیِ پخش که باید بایستد.  P5-D222 */
		if ( 'function' === typeof modal.tpOnClose ) {
			modal.tpOnClose();
		}

		if ( openStack.length ) {
			return;
		}

		document.body.style.overflow = '';
		document.documentElement.style.overflow = '';

		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus();
		}

		lastFocus = null;
	}

	function trapFocus( modal ) {

		if ( ! modal ) {
			return;
		}

		modal.addEventListener( 'keydown', function ( e ) {

			if ( 'Tab' !== e.key || ! modal.classList.contains( 'tp-show' ) ) {
				return;
			}

			var f = modal.querySelectorAll( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );

			if ( ! f.length ) {
				return;
			}

			var first = f[ 0 ];
			var last  = f[ f.length - 1 ];

			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		} );
	}

	var mapModal    = document.getElementById( 'tpMapModal' );
	var galleryBox  = document.getElementById( 'tpGalleryModal' );
	var soloBox     = document.getElementById( 'tpGallerySolo' );

	[ mapModal, galleryBox, soloBox ].forEach( trapFocus );

	[ mapModal, galleryBox, soloBox ].forEach( function ( modal ) {

		if ( ! modal ) {
			return;
		}

		modal.addEventListener( 'click', function ( e ) {
			if ( e.target === modal ) { closeModal( modal ); }
		} );
	} );

	document.addEventListener( 'keydown', function ( e ) {

		if ( 'Escape' !== e.key || ! openStack.length ) {
			return;
		}

		/* یک پله عقب، نه همه با هم. */
		closeModal( openStack[ openStack.length - 1 ] );
	} );

	page.addEventListener( 'click', function ( e ) {

		var target = e.target.closest ? e.target : e.target.parentNode;

		if ( ! target || ! target.closest ) {
			return;
		}

		if ( target.closest( '[data-tp-map-open]' ) && mapModal ) {
			openModal( mapModal, mapModal.querySelector( '[data-tp-close]' ) );
		}

		if ( target.closest( '[data-tp-gallery-open]' ) && galleryBox ) {
			openModal( galleryBox, galleryBox.querySelector( '[data-tp-close]' ) );
		}
	} );

	document.addEventListener( 'click', function ( e ) {

		if ( ! e.target.closest ) {
			return;
		}

		var x = e.target.closest( '[data-tp-close]' );

		if ( ! x ) {
			return;
		}

		var modal = x.closest( '.tp-modal, .tp-gallery-modal, .tp-sheet' );

		if ( modal && modal.classList.contains( 'tp-sheet' ) ) {
			modal.removeAttribute( 'open' );
			return;
		}

		closeModal( modal );
	} );

	/* ══ گالری — نمای تک‌عکس ════════════════════════════════════════════
	   کلیک روی هر کاشی، چه در موزاییک چه در مودال، همان عکس را در
	   اندازۀ کامل نشان می‌دهد. فهرست عکس‌ها را PHP روی مودال گذاشته،
	   پس هر دو جا از یک ترتیب می‌خوانند.
	   ────────────────────────────────────────────────────────────────── */
	( function () {

		if ( ! soloBox || ! galleryBox ) {
			return;
		}

		var shots = [];

		try {
			shots = JSON.parse( galleryBox.getAttribute( 'data-tp-shots' ) || '[]' );
		} catch ( err ) {
			shots = [];
		}

		if ( ! shots.length ) {
			return;
		}

		var img     = soloBox.querySelector( '.tp-solo__img' );
		var title   = soloBox.querySelector( '[data-tp-solo-title]' );
		var cap     = soloBox.querySelector( '[data-tp-solo-caption]' );
		var counter = soloBox.querySelector( '[data-tp-solo-count]' );
		var stage   = soloBox.querySelector( '[data-tp-solo-stage]' );
		var at      = 0;
		var n       = shots.length;
		var fa      = digits;

		/*
		 * همسایه‌ها از پیش بار می‌شوند — هر دو نسخه — تا «بعدی» منتظر شبکه
		 * نماند.  بند ۸ · P5-D226
		 */
		function preload( index ) {
			var one = shots[ ( ( index % n ) + n ) % n ];
			if ( ! one ) { return; }
			[ one.m, one.src ].forEach( function ( url ) {
				if ( url ) {
					var pre = new Image();
					pre.src = url;
				}
			} );
		}

		var token = 0;

		/*
		 * دو سرِ فهرست به هم می‌رسند: بعد از آخری، اولی؛ قبل از اولی، آخری.
		 *
		 * تعویض بی‌درنگ است.  P5-D226 — نسخۀ ۱۰۲۴ که کاشی بار کرده (`m`)
		 * همان لحظه می‌نشیند؛ نسخۀ کامل جدا بار و `decode()` می‌شود و فقط
		 * وقتی آماده است جایش را می‌گیرد — اگر کاربر تا آن موقع جلو نرفته
		 * باشد. پس هیچ‌وقت قاب خالی یا نیمه‌کشیده دیده نمی‌شود.
		 */
		function show( index ) {

			at = ( ( index % n ) + n ) % n;

			var shot = shots[ at ];
			var mine = ++ token;
			var full = shot.src || '';
			var fast = shot.m || full;

			img.setAttribute( 'src', fast );
			img.setAttribute( 'alt', shot.alt || '' );

			/* ابعاد صریح با هر عکس — جعبه پیش از بار شدن جا دارد.  P5-D246 */
			if ( shot.w && shot.h ) {
				img.setAttribute( 'width', shot.w );
				img.setAttribute( 'height', shot.h );
			}

			if ( full && full !== fast ) {

				var big = new Image();

				big.src = full;

				var swap = function () {
					if ( mine === token ) { img.setAttribute( 'src', full ); }
				};

				if ( big.decode ) {
					big.decode().then( swap, function () {} );
				} else {
					big.onload = swap;
				}
			}

			if ( title ) { title.textContent = shot.t || ''; }
			if ( cap ) {
				cap.textContent = shot.c || '';
				/* عکس تجمیعی: موجودیت منبع با پیوند به صفحه‌اش.  P5-D274 */
				if ( shot.n ) {
					if ( cap.textContent ) { cap.appendChild( document.createTextNode( ' · ' ) ); }
					var src = document.createElement( shot.u ? 'a' : 'span' );
					if ( shot.u ) { src.setAttribute( 'href', shot.u ); }
					src.className = 'tp-solo__src';
					src.textContent = shot.n;
					cap.appendChild( src );
				}
			}
			if ( counter ) { counter.textContent = fa( at + 1 ) + ' / ' + fa( n ); }

			preload( at + 1 );
			preload( at - 1 );
		}

		/* راست‌به‌چپ؟ از `direction` خودِ نما، نه از زبان.  P5-D243 */
		function rtl() {
			return 'rtl' === window.getComputedStyle( soloBox ).direction;
		}

		/*
		 * کشیدن انگشت: محتوای دیده‌نشده از سمتِ «بعدی» وارد می‌شود. در
		 * راست‌به‌چپ کشیدن از چپ به راست بعدی است، در چپ‌به‌راست از راست به
		 * چپ.  P5-D243
		 */
		if ( stage ) {

			var sx = 0, sy = 0, touching = false;

			stage.addEventListener( 'touchstart', function ( e ) {
				if ( 1 !== e.touches.length ) { touching = false; return; }
				touching = true;
				sx = e.touches[0].clientX;
				sy = e.touches[0].clientY;
			}, { passive: true } );

			stage.addEventListener( 'touchend', function ( e ) {

				if ( ! touching || ! e.changedTouches.length ) {
					return;
				}

				touching = false;

				var dx = e.changedTouches[0].clientX - sx;
				var dy = e.changedTouches[0].clientY - sy;

				if ( Math.abs( dx ) < 40 || Math.abs( dx ) < Math.abs( dy ) ) {
					return;
				}

				show( ( rtl() ? dx > 0 : dx < 0 ) ? at + 1 : at - 1 );
			}, { passive: true } );

			/* همان کشیدن با موس — همان آستانه، همان جهت.  P5-D277 */
			var mx = 0, my = 0, mid = null;

			stage.addEventListener( 'dragstart', function ( e ) { e.preventDefault(); } );

			/* گرفتن اشاره‌گر فقط پس از پنج پیکسل و هرگز از روی دکمه — وگرنه کلیکِ «قبلی/بعدی» به صحنه می‌رسید، نه به دکمه. */
			stage.addEventListener( 'pointerdown', function ( e ) {
				if ( 'mouse' !== e.pointerType || 0 !== e.button || ( e.target.closest && e.target.closest( 'button' ) ) ) { return; }
				mid = e.pointerId;
				mx  = e.clientX;
				my  = e.clientY;
			} );

			stage.addEventListener( 'pointermove', function ( e ) {
				if ( e.pointerId !== mid || stage.classList.contains( 'tp-is-dragging' ) || Math.abs( e.clientX - mx ) < 5 ) { return; }
				stage.setPointerCapture( mid );
				stage.classList.add( 'tp-is-dragging' );
			} );

			function mouseEnd( e ) {

				if ( e.pointerId !== mid ) {
					return;
				}

				mid = null;
				stage.classList.remove( 'tp-is-dragging' );

				var dx = e.clientX - mx;
				var dy = e.clientY - my;

				if ( 'pointercancel' === e.type || Math.abs( dx ) < 40 || Math.abs( dx ) < Math.abs( dy ) ) {
					return;
				}

				show( ( rtl() ? dx > 0 : dx < 0 ) ? at + 1 : at - 1 );
			}

			stage.addEventListener( 'pointerup', mouseEnd );
			stage.addEventListener( 'pointercancel', mouseEnd );
		}

		/* کاشی هم در موزاییک صفحه است هم در مودالِ گالریِ لایۀ بیرونی. */
		onBoth( 'click', function ( e ) {

			if ( ! e.target.closest ) {
				return;
			}

			var tile = e.target.closest( '[data-tp-shot]' );

			if ( ! tile ) {
				return;
			}

			var id = tile.getAttribute( 'data-tp-shot' );

			for ( var i = 0; i < shots.length; i ++ ) {
				if ( String( shots[ i ].id ) === String( id ) ) { show( i ); break; }
			}

			openModal( soloBox, soloBox.querySelector( '[data-tp-close]' ) );
		} );

		soloBox.addEventListener( 'click', function ( e ) {

			if ( ! e.target.closest ) {
				return;
			}

			if ( e.target.closest( '[data-tp-shot-next]' ) ) { show( at + 1 ); }
			if ( e.target.closest( '[data-tp-shot-prev]' ) ) { show( at - 1 ); }
		} );

		document.addEventListener( 'keydown', function ( e ) {

			if ( ! soloBox.classList.contains( 'tp-show' ) ) {
				return;
			}

			/* کلیدِ هم‌جهتِ فلشِ «بعدی» بعدی است: راست‌به‌چپ ArrowLeft، چپ‌به‌راست ArrowRight.  P5-D243 */
			var forward = rtl() ? 'ArrowLeft' : 'ArrowRight';
			var back    = rtl() ? 'ArrowRight' : 'ArrowLeft';

			if ( forward === e.key ) { show( at + 1 ); }
			if ( back === e.key ) { show( at - 1 ); }
		} );
	} )();

	/* ══ صوت و ویدیو — P5-D222 ══════════════════════════════════════════
	   یوتیوب، آپارات و پروندۀ ویدیو در شاسیِ تمام‌صفحه پخش می‌شوند.
	   `iframe` یا `<video>` فقط با کلیک ساخته می‌شود — پیش از آن هیچ
	   درخواستی به سرویس بیرونی نمی‌رود — و با بستن برداشته می‌شود، پس
	   پخش هم می‌ایستد. نشانیِ جاسازی را سرور از شناسۀ معتبر ساخته است.
	   ────────────────────────────────────────────────────────────────── */
	( function () {

		var box = document.getElementById( 'tpMediaSolo' );

		if ( ! box ) {
			return;
		}

		var stage = box.querySelector( '[data-tp-media-stage]' );
		var name  = box.querySelector( '[data-tp-media-name]' );

		trapFocus( box );

		box.addEventListener( 'click', function ( e ) {
			if ( e.target === box ) { closeModal( box ); }
		} );

		box.tpOnClose = function () {
			stage.innerHTML = '';
		};

		page.addEventListener( 'click', function ( e ) {

			var t = e.target.closest ? e.target.closest( '[data-tp-media]' ) : null;

			if ( ! t ) {
				return;
			}

			e.preventDefault();

			var kind  = t.getAttribute( 'data-tp-media-kind' );
			var src   = t.getAttribute( 'data-tp-media' ) || '';
			var title = t.getAttribute( 'data-tp-media-title' ) || '';
			var el;

			if ( 'video' === kind ) {
				el = document.createElement( 'video' );
				el.controls = true;
				el.autoplay = true;
				el.setAttribute( 'playsinline', '' );
			} else {
				el = document.createElement( 'iframe' );
				el.setAttribute( 'allow', 'autoplay; encrypted-media; picture-in-picture; fullscreen' );
				el.setAttribute( 'allowfullscreen', '' );
				el.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );
				el.setAttribute( 'title', title );
			}

			el.src = src;
			stage.innerHTML = '';
			stage.appendChild( el );

			if ( name ) { name.textContent = title; }

			openModal( box, box.querySelector( '[data-tp-close]' ) );
		} );
	} )();

	/* ══ مودالِ فهرست — P5-D221 ══════════════════════════════════════════
	   «همه شهرها / همه جاذبه‌ها / همه فرهنگ». کارت‌ها و پیوندها در HTML
	   سرورند؛ این‌جا فقط باز و بسته می‌شود و جست‌وجو پنهان و پیدا می‌کند.

	   نام پیش از مقایسه یکدست می‌شود، هم نام و هم پرسش:
	     ي ى ← ی · ك ← ک · ۀ ← ه · نیم‌فاصله و فاصله حذف · اِعراب حذف
	     (اِصفَهان = اصفهان) · رقم فارسی و عربی ← لاتین
	   «ۀ» یک‌نویسه (U+06C0) و «ه» + همزۀ بالا (U+0647 U+0654) هر دو «ه»
	   می‌شوند — کد U+06C0 می‌نویسد، داده هنوز دونویسه دارد  P5-D248.
	   فاصله هم حذف می‌شود چون «نوش آباد» و «نوش‌آباد» یک نام‌اند.
	   ────────────────────────────────────────────────────────────────── */
	/*
	 * \u062A\u0627\u06A9\u0631\u062F\u0646 \u0639\u0628\u0627\u0631\u062A \u062C\u0633\u062A\u200C\u0648\u062C\u0648 \u2014 P5-D265. \u0642\u0627\u0639\u062F\u0647\u200C\u0647\u0627 \u0627\u06CC\u0646\u200C\u062C\u0627 \u0646\u0648\u0634\u062A\u0647 \u0646\u0645\u06CC\u200C\u0634\u0648\u0646\u062F: \u062C\u062F\u0648\u0644\u0650
	 * `topersia_search_fold_rules()` \u0647\u0633\u062A\u0647 \u0627\u0633\u062A \u06A9\u0647 \u0628\u0627 `TopersiaG.fold` \u0645\u06CC\u200C\u0631\u0633\u062F\u060C
	 * \u0648 \u0647\u0645\u0627\u0646 \u062C\u062F\u0648\u0644 \u062F\u0631 PHP \u0646\u0627\u0645\u0650 \u0627\u0639\u0636\u0627 \u0631\u0627 \u0628\u0631\u0627\u06CC `data-tp-fold` \u062A\u0627 \u06A9\u0631\u062F\u0647. \u06CC\u06A9 \u0642\u0627\u0639\u062F\u0647\u060C
	 * \u062F\u0648 \u0627\u062C\u0631\u0627\u06A9\u0646\u0646\u062F\u06C0 \u06A9\u0648\u0686\u06A9 \u2014 \u062F\u0648 \u067E\u06CC\u0627\u062F\u0647\u200C\u0633\u0627\u0632\u06CC\u0650 \u0642\u0627\u0639\u062F\u0647 \u0646\u0647. \u0628\u06CC \u0647\u0633\u062A\u0647\u060C \u0641\u0642\u0637 \u062D\u0631\u0648\u0641 \u06A9\u0648\u0686\u06A9.
	 */
	function tpHex( n ) { return ( '000' + Number( n ).toString( 16 ) ).slice( -4 ); }

	var TP_FOLD = ( ( window.TopersiaG && window.TopersiaG.fold ) || [] ).map( function ( r ) {
		if ( r.l ) { return { l: 1 }; }
		if ( 'number' === typeof r.d ) { return { d: r.d, re: new RegExp( '[\\u' + tpHex( r.d ) + '-\\u' + tpHex( r.d + 9 ) + ']', 'g' ) }; }
		var cls = ( r.c || [] ).map( function ( c ) { return '\\u' + tpHex( c ); } ).join( '' )
			+ ( r.r || [] ).map( function ( x ) { return '\\u' + tpHex( x[0] ) + ( x[1] !== x[0] ? '-\\u' + tpHex( x[1] ) : '' ); } ).join( '' )
			+ ( r.s ? '\\s' : '' );
		return { re: new RegExp( '[' + cls + ']' + ( r.s ? '+' : '' ), 'g' ), t: String( r.t || '' ) };
	} );

	function tpNorm( s ) {
		s = String( s || '' );
		if ( ! TP_FOLD.length ) { return s.toLowerCase(); }
		TP_FOLD.forEach( function ( r ) {
			if ( r.l ) { s = s.toLowerCase(); }
			else if ( undefined !== r.d ) { s = s.replace( r.re, function ( ch ) { return String( ch.charCodeAt( 0 ) - r.d ); } ); }
			else { s = s.replace( r.re, r.t ); }
		} );
		return s;
	}

	( function () {

		var lists = [].slice.call( portal.querySelectorAll( '[data-tp-list]' ) );

		if ( ! lists.length ) {
			return;
		}

		var fa = digits;

		lists.forEach( function ( box ) {

			trapFocus( box );

			/* پرده خودِ مودال است، مثل گالری: کلیک روی آن می‌بندد. */
			box.addEventListener( 'click', function ( e ) {
				if ( e.target === box ) { closeModal( box ); }
			} );

			var q      = box.querySelector( '[data-tp-list-q]' );
			var count  = box.querySelector( '[data-tp-list-count]' );
			var empty  = box.querySelector( '.tp-list__empty' );
			var blocks = [].slice.call( box.querySelectorAll( '.tp-list__block' ) );
			var items  = [].slice.call( box.querySelectorAll( '.tp-list__item' ) ).map( function ( li ) {
				var b = li.querySelector( 'b' );
				/* نامِ تاشده با تابع PHP هسته، اگر هست  P5-D265 */
				return { el: li, key: li.hasAttribute( 'data-tp-fold' ) ? li.getAttribute( 'data-tp-fold' ) : tpNorm( b ? b.textContent : li.textContent ) };
			} );
			var total  = items.length;

			box.tpFilter = function () {

				var needle = q ? tpNorm( q.value ) : '';
				var shown  = 0;

				items.forEach( function ( it ) {
					var ok = '' === needle || -1 !== it.key.indexOf( needle );
					it.el.hidden = ! ok;
					if ( ok ) { shown ++; }
				} );

				/* سرفصل خانواده‌ای که عضوِ پیدایی ندارد هم پنهان می‌شود */
				blocks.forEach( function ( b ) {
					b.hidden = ! b.querySelector( '.tp-list__item:not([hidden])' );
				} );

				if ( empty ) { empty.hidden = shown > 0; }

				/* هر دو شکلِ شمار از سرور، به زبان صفحه و با شکل جمعش  ۴٫۴٫۲ */
				if ( count ) {
					count.textContent = '' === needle
						? ( count.getAttribute( 'data-tp-count-all' ) || fa( total ) )
						: ( count.getAttribute( 'data-tp-count-of' ) || '%1$s / %2$s' ).replace( '%1$s', fa( shown ) ).replace( '%2$s', fa( total ) );
				}
			};

			if ( q ) {
				q.addEventListener( 'input', box.tpFilter );
			}
		} );

		/* `.more` پیوند است — به خودِ مودال؛ این‌جا جلوی پرش گرفته می‌شود. */
		page.addEventListener( 'click', function ( e ) {

			var a = e.target.closest ? e.target.closest( '[data-tp-list-open]' ) : null;

			if ( ! a ) {
				return;
			}

			var box = document.getElementById( ( a.getAttribute( 'href' ) || '' ).replace( '#', '' ) );

			if ( ! box ) {
				return;
			}

			e.preventDefault();

			/* هر بار از نو: فهرست کامل، جست‌وجوی خالی، فوکوس روی جعبه */
			var q = box.querySelector( '[data-tp-list-q]' );

			if ( q ) { q.value = ''; }
			if ( box.tpFilter ) { box.tpFilter(); }

			box.scrollTop = 0;
			openModal( box, q );
		} );
	} )();

	/* ══ بلوک هوش مصنوعی ════════════════════════════════════════════════ */
	( function () {

		var out = page.querySelector( '[data-tp-ai-out]' );

		if ( ! out ) {
			return;
		}

		var data = {};

		try {
			data = JSON.parse( out.getAttribute( 'data-tp-ai' ) || '{}' );
		} catch ( err ) {
			data = {};
		}

		var body  = out.querySelector( 'p' );
		var label = out.querySelector( '[data-tp-ai-type]' );

		Array.prototype.forEach.call( page.querySelectorAll( '[data-ai]' ), function ( btn ) {

			btn.addEventListener( 'click', function () {

				var kind = btn.getAttribute( 'data-ai' );

				if ( 'copy' === kind ) {

					if ( navigator.clipboard && navigator.clipboard.writeText ) {

						navigator.clipboard.writeText( body.textContent || '' ).then( function () {

							var was = btn.innerHTML;

							btn.textContent = t( 'کپی شد ✓' );
							btn.disabled    = true;

							window.setTimeout( function () {
								btn.innerHTML = was;
								btn.disabled  = false;
							}, 1500 );
						} ).catch( function () {} );
					}

					return;
				}

				var mark = function () {
					if ( label ) {
						label.textContent = btn.textContent.trim();
					}

					Array.prototype.forEach.call( page.querySelectorAll( '[data-ai]' ), function ( b ) {
						if ( 'copy' !== b.getAttribute( 'data-ai' ) ) {
							b.classList.toggle( 'tp-is-active', b === btn );
						}
					} );
				};

				if ( data[ kind ] ) {
					body.textContent = data[ kind ];
					mark();
					return;
				}

				/* خروجی ذخیره نیست ← کاری نمی‌کند: صفحه برای هیچ‌کس تولید نمی‌خواهد و هیچ درخواستی نمی‌فرستد؛ دکمۀ هر نوع فقط وقتی هست که خروجی ذخیره‌شده‌اش هست. تولید فقط مدیر از متاباکس «ToPersia AI».  P5-D358 */
			} );
		} );
	} )();

	/* ══ مسیریابی — آخرین انتخاب اول  P5-D350 ═══════════════════════════════
	   سایت موقعیت کاربر را نمی‌خواهد: مبدأ را اپِ مقصد از دستگاه می‌گیرد. تنها چیزِ نگه‌داشته‌شده نامِ آخرین دکمه در localStorage
	   همین مرورگر است (داخل try/catch؛ بی آن، ترتیب سرور می‌ماند) — آن دکمه اول می‌نشیند. */
	( function () {

		var box = page.querySelector( '[data-tp-nav-box]' );
		var KEY = 'tp-nav-last';

		if ( ! box ) {
			return;
		}

		function read() {
			try { return window.localStorage.getItem( KEY ) || ''; } catch ( e ) { return ''; }
		}

		function write( v ) {
			try { window.localStorage.setItem( KEY, v ); } catch ( e ) {}
		}

		var last = read();
		var tile = last ? box.querySelector( 'a[data-tp-nav="' + last.replace( /[^a-z]/g, '' ) + '"]' ) : null;

		if ( tile && box.firstElementChild !== tile ) {
			box.insertBefore( tile, box.firstElementChild );
		}

		box.addEventListener( 'click', function ( e ) {
			var a = e.target && e.target.closest ? e.target.closest( 'a[data-tp-nav]' ) : null;
			if ( a ) { write( a.getAttribute( 'data-tp-nav' ) ); }
		} );
	} )();

	/* ══ فرم نظر — P5-D234 ══════════════════════════════════════════════
	   این فایل فقط فرم را می‌فرستد؛ نوشتن کارِ user-core است
	   (`topersia_submit_review`، نانس خودش، همیشه در انتظار تأیید مدیر).
	   ────────────────────────────────────────────────────────────────── */
	Array.prototype.forEach.call( page.querySelectorAll( '[data-tp-review-form]' ), function ( form ) {

		var note = form.querySelector( '.tp-review-form__note' );

		form.addEventListener( 'submit', function ( e ) {

			e.preventDefault();

			var c = window.ToPersiaUserCore;

			if ( ! c || 'function' !== typeof c.call ) {
				return;
			}

			var scores = [];
			var rows   = form.querySelectorAll( '.tp-review-form__row' );

			for ( var i = 0; i < rows.length; i ++ ) {

				var picked = rows[ i ].querySelector( 'input:checked' );

				if ( ! picked ) {
					note.textContent = t( 'به همۀ معیارها امتیاز بدهید.' );
					return;
				}

				scores.push( picked.value );
			}

			var text = ( form.querySelector( '[name="content"]' ).value || '' ).trim();

			if ( text.length < 10 ) {
				note.textContent = t( 'متن نظر دست‌کم ده نویسه باشد.' );
				return;
			}

			var send = form.querySelector( '.tp-review-form__send' );
			send.disabled = true;

			c.call( 'topersia_submit_review', {
				post_id: form.getAttribute( 'data-tp-post' ) || '',
				scores: scores,
				title: form.querySelector( '[name="title"]' ).value || '',
				content: text
			} ).then( function ( r ) {

				if ( r && r.success ) {
					var done = document.createElement( 'p' );
					done.className   = 'tp-review-invite';
					done.textContent = ( r.data && r.data.message ) || '';
					form.parentNode.replaceChild( done, form );
					return;
				}

				note.textContent = ( r && r.data && r.data.message ) || t( 'ثبت ممکن نشد.' );
				send.disabled    = false;
			} ).catch( function () {
				note.textContent = t( 'ارتباط برقرار نشد.' );
				send.disabled    = false;
			} );
		} );
	} );

	/* ══ کاشی‌های ستون کناری — P5-D244 · P5-D344 ═════════════════════════
	   پیش‌فرض سه ستون. اگر متنِ حتی یک کاشیِ یک کارت بیش از دو خط شد، کل همان
	   کارت دوستونه می‌شود. شمارش خط از ارتفاع واقعی عنصر ÷ ارتفاع خط (نه از
	   پهنا). واژه هرگز شکسته نمی‌شود (word-break:normal)؛ پس واژه‌ای که از پهنای
	   کاشی بلندتر است سرریز می‌کند و آن هم دوستونه‌اش می‌کند (جایگزین معیار
	   سرریزِ تنهای P5-D244: سرریز فقط نگهبان واژۀ ناشکستنی است). هر بار در
	   سه ستون سنجیده می‌شود، پس با پهن‌تر شدن ستون به سه برمی‌گردد.
	   ResizeObserver فقط تغییر پهنا را می‌شمارد تا عوض‌کردنِ ستون‌ها دور
	   بی‌پایان نسازد؛ رسیدن قلم (`document.fonts.ready`) هم دوباره می‌سنجد.
	   ────────────────────────────────────────────────────────────────── */
	function lineCount( el ) {

		var cs = window.getComputedStyle( el );
		var lh = parseFloat( cs.lineHeight );

		if ( ! lh ) {
			lh = parseFloat( cs.fontSize ) * 1.3;
		}

		return Math.round( el.getBoundingClientRect().height / lh );
	}

	function fitTiles( grid ) {

		grid.classList.remove( 'tp-tiles--2' );

		for ( var i = 0; i < grid.children.length; i ++ ) {

			var tile = grid.children[ i ];

			if ( tile.hidden ) {
				continue;
			}

			if ( tile.scrollWidth > tile.clientWidth + 1 ) {
				grid.classList.add( 'tp-tiles--2' );
				return;
			}

			var texts = tile.querySelectorAll( 'b, .tp-tile__k, .tp-tile__sub' );

			for ( var j = 0; j < texts.length; j ++ ) {
				if ( lineCount( texts[ j ] ) > 2 ) {
					grid.classList.add( 'tp-tiles--2' );
					return;
				}
			}
		}
	}
	( function () {

		var grids = page.querySelectorAll( '[data-tp-tiles]' );

		if ( ! grids.length ) {
			return;
		}

		var all = function () { Array.prototype.forEach.call( grids, fitTiles ); };

		if ( window.ResizeObserver ) {

			var seen = [];
			var ro   = new ResizeObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					var w = Math.round( entry.contentRect.width );
					var i = Array.prototype.indexOf.call( grids, entry.target );
					if ( w && seen[ i ] !== w ) {
						seen[ i ] = w;
						fitTiles( entry.target );
					}
				} );
			} );

			Array.prototype.forEach.call( grids, function ( grid ) { ro.observe( grid ); } );
		} else {
			all();
			window.addEventListener( 'resize', all, { passive: true } );
		}

		if ( document.fonts && document.fonts.ready ) {
			document.fonts.ready.then( all );
		}
	} )();

	/* ══ دکمۀ «N مورد دیگر» ═════════════════════════════════════════════ */
	/*
	 * «{N} مورد دیگر» — دستۀ بعدی، نه همه.  P5-D244
	 * N کمینۀ گام (شش) و باقی‌مانده است؛ پس از آخرین دسته دکمه برداشته
	 * می‌شود. کاشی‌های تازه هم سنجیده می‌شوند.
	 */
	Array.prototype.forEach.call( page.querySelectorAll( '[data-tp-more]' ), function ( btn ) {

		btn.addEventListener( 'click', function () {

			var card  = btn.closest( '.tp-rail-card' ) || btn.parentNode;
			var step  = parseInt( btn.getAttribute( 'data-tp-step' ), 10 ) || Infinity;
			var shown = 0;

			Array.prototype.forEach.call( card.querySelectorAll( '[data-tp-rest-item][hidden]' ), function ( one ) {
				if ( shown < step ) {
					one.removeAttribute( 'hidden' );
					shown ++;
				}
			} );

			var left = card.querySelectorAll( '[data-tp-rest-item][hidden]' ).length;
			var grid = card.querySelector( '[data-tp-tiles]' );

			if ( grid ) {
				fitTiles( grid );
			}

			if ( ! left ) {
				btn.remove();
				return;
			}

			var n = btn.querySelector( '[data-tp-more-n]' );

			if ( n ) {
				n.textContent = digits( Math.min( step, left ) );
			}
		} );
	} );

	/* ══ نوار کنش ═══════════════════════════════════════════════════════
	   یک عنصر، دو جای نشستن — در دسکتاپ کارتی در ستون کناری، در موبایل
	   نواری بالای مسیر راهنما. دو نسخه یعنی دو وضعیت «ذخیره‌شده» که با
	   هم واگرا می‌شوند.
	   ────────────────────────────────────────────────────────────────── */
	( function () {

		var bar = page.querySelector( '[data-tp-actionbar]' );

		if ( ! bar ) {
			return;
		}

		var home   = bar.parentNode;
		var crumb  = page.querySelector( '.tp-crumb' );
		var narrow = window.matchMedia( '(max-width: 720px)' );

		function place() {

			if ( narrow.matches ) {

				if ( crumb && bar.nextSibling !== crumb ) {
					crumb.parentNode.insertBefore( bar, crumb );
				}

				bar.className = 'tp-actionbar';

			} else {

				if ( bar.parentNode !== home ) {
					home.appendChild( bar );
				}

				bar.className = 'tp-rail-card tp-actionbar tp-actionbar--rail';
			}
		}

		place();

		if ( narrow.addEventListener ) {
			narrow.addEventListener( 'change', place );
		} else if ( narrow.addListener ) {
			narrow.addListener( place );
		}

		var note = bar.querySelector( '.tp-act__note' );

		function say( text ) {

			if ( ! note ) {
				return;
			}

			note.textContent = text;

			window.setTimeout( function () {
				if ( note.textContent === text ) { note.textContent = ''; }
			}, 2600 );
		}

		/* ── اشتراک — پنل آیکون شبکه‌ها  P5-D239 ────────────────────────
		   روی دستگاهِ دارای hover، CSS با :hover و :focus-within بازش می‌کند؛
		   ضربه (لمس) و کلیک با `tp-is-open` باز و بسته می‌کنند. Escape و کلیک
		   بیرون می‌بندند. هیچ اسکریپت شخص ثالثی بار نمی‌شود؛ پیوندها نشانی
		   استاندارد اشتراک‌اند و سرور ساخته. */
		var wrap  = bar.querySelector( '[data-tp-share-wrap]' );
		var share = bar.querySelector( '[data-tp-share]' );

		if ( wrap && share ) {

			var setOpen = function ( on ) {
				wrap.classList.toggle( 'tp-is-open', on );
				share.setAttribute( 'aria-expanded', on ? 'true' : 'false' );
			};

			share.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				setOpen( ! wrap.classList.contains( 'tp-is-open' ) );
			} );

			document.addEventListener( 'click', function ( e ) {
				if ( ! wrap.contains( e.target ) ) { setOpen( false ); }
			} );

			wrap.addEventListener( 'keydown', function ( e ) {
				if ( 'Escape' === e.key ) { setOpen( false ); share.focus(); }
			} );

			var copy = wrap.querySelector( '[data-tp-share-copy]' );

			if ( copy ) {
				copy.addEventListener( 'click', function () {
					var url = copy.getAttribute( 'data-tp-url' ) || location.href;
					if ( navigator.clipboard && navigator.clipboard.writeText ) {
						navigator.clipboard.writeText( url ).then( function () { say( t( 'نشانی کپی شد.' ) ); } ).catch( function () {} );
					}
				} );
			}

			/* «بیشتر»: فقط لمسی و فقط اگر مرورگر اشتراکِ بومی دارد. */
			var more   = wrap.querySelector( '[data-tp-share-more]' );
			var coarse = window.matchMedia && window.matchMedia( '(pointer: coarse)' ).matches;

			if ( more && navigator.share && coarse ) {
				more.hidden = false;
				more.addEventListener( 'click', function () {
					navigator.share( { title: document.title, url: ( copy && copy.getAttribute( 'data-tp-url' ) ) || location.href } ).catch( function () {} );
				} );
			}
		}

		/* ── ذخیره و دنبال‌کردن — API user-core  P5-D237 · P5-D238 ───────
		   این فایل فقط دکمه دارد؛ نوشتن، نانس و پنجرۀ ورود مال user-core
		   است. مهمان ← رویداد `topersia:login-required` که پنجرۀ ورود/ثبت‌نام
		   user-core را باز می‌کند؛ بازگشت به همین صفحه. */
		var save   = bar.querySelector( '[data-tp-save]' );
		var follow = bar.querySelector( '[data-tp-follow]' );
		var post   = bar.getAttribute( 'data-tp-post' ) || '';
		var ptype  = bar.getAttribute( 'data-tp-type' ) || '';

		function uc() { return window.ToPersiaUserCore || null; }

		function logged() { var c = uc(); return !! ( c && c.loggedIn ); }

		function call( action, extra ) {

			var c = uc();

			if ( c && 'function' === typeof c.call ) {
				return c.call( action, extra );
			}

			var f = new URLSearchParams();
			f.set( 'action', action );
			f.set( 'nonce', ( c && c.nonce ) || '' );
			Object.keys( extra ).forEach( function ( k ) { f.set( k, extra[ k ] ); } );

			return fetch( ( c && c.ajaxUrl ) || '/wp-admin/admin-ajax.php', {
				method: 'POST', credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: f.toString()
			} ).then( function ( r ) { return r.json(); } );
		}

		function needLogin( reason ) {

			var c = uc();

			if ( c && 'function' === typeof c.openLogin ) {
				c.openLogin( { reason: reason, redirect: location.href } );
				return;
			}

			if ( c && c.loginUrl ) {
				location.href = c.loginUrl + ( c.loginUrl.indexOf( '?' ) < 0 ? '?' : '&' ) + 'redirect_to=' + encodeURIComponent( location.href );
			}
		}

		function pressed( btn, on ) {

			if ( ! btn ) {
				return;
			}

			btn.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			btn.classList.toggle( 'tp-is-on', on );

			if ( btn === follow ) {
				var t = follow.querySelector( '[data-tp-follow-label]' );
				if ( t ) { t.textContent = on ? tr( 'دنبال می‌کنید' ) : tr( 'دنبال‌کردن' ); }
			}
		}

		if ( save ) {

			save.addEventListener( 'click', function () {

				if ( ! logged() ) {
					needLogin( 'save' );
					return;
				}

				var on = 'true' === save.getAttribute( 'aria-pressed' );

				save.disabled = true;

				call( on ? 'topersia_remove_saved_place' : 'topersia_save_place', { post_id: post, post_type: ptype } ).then( function ( r ) {
					if ( r && r.success ) {
						pressed( save, ! on );
						say( ( r.data && r.data.message ) || '' );
					} else {
						say( ( r && r.data && r.data.message ) || t( 'ذخیره ممکن نشد.' ) );
					}
				} ).catch( function () {
					say( t( 'ارتباط برقرار نشد.' ) );
				} ).then( function () {
					save.disabled = false;
				} );
			} );
		}

		if ( follow ) {

			follow.addEventListener( 'click', function () {

				if ( ! logged() ) {
					needLogin( 'follow' );
					return;
				}

				var on = 'true' === follow.getAttribute( 'aria-pressed' );

				follow.disabled = true;

				call( 'topersia_follow_entity', { post_id: post, post_type: ptype, mode: on ? 'unfollow' : 'follow' } ).then( function ( r ) {
					if ( r && r.success ) {
						pressed( follow, ! on );
						say( ( r.data && r.data.message ) || '' );
					} else {
						say( ( r && r.data && r.data.message ) || t( 'دنبال‌کردن ممکن نشد.' ) );
					}
				} ).catch( function () {
					say( t( 'ارتباط برقرار نشد.' ) );
				} ).then( function () {
					follow.disabled = false;
				} );
			} );
		}

		/* وضعیت واردشده پس از بارگذاری — صفحه کش می‌شود، پس در HTML نیست. */
		if ( save || follow ) {
			window.addEventListener( 'load', function () {
				if ( ! logged() ) {
					return;
				}
				call( 'topersia_entity_state', { post_id: post, post_type: ptype } ).then( function ( r ) {
					if ( r && r.success && r.data ) {
						pressed( save, !! r.data.saved );
						pressed( follow, !! r.data.following );
					}
				} ).catch( function () {} );
			}, { once: true } );
		}
	} )();

	/* ══ نوار پایین موبایل و برگۀ بخش‌ها ══════════════════════════════ */
	( function () {

		var mnav = portal.querySelector( '.tp-mnav' );

		if ( ! mnav ) {
			return;
		}

		var sheet = document.getElementById( 'tpSheet' );

		/*
		 * آشکارشدن با نخستین اسکرولِ بیش از 24px، و ماندن برای همیشه.
		 * ۴٫۳٫۰ بند ۱۲ — شنونده پس از بار اول برداشته می‌شود؛ بالا رفتنِ دوباره
		 * نوار را پنهان نمی‌کند. اگر صفحه از پیش اسکرول‌خورده باز شد (پیوند
		 * لنگری، بازگشت)، همان لحظه پیداست.
		 */
		var onScroll = function () {
			if ( ( window.pageYOffset || document.documentElement.scrollTop || 0 ) > 24 ) {
				mnav.classList.add( 'tp-mnav--on' );
				window.removeEventListener( 'scroll', onScroll );
			}
		};

		window.addEventListener( 'scroll', onScroll, { passive: true } );
		onScroll();

		mnav.addEventListener( 'click', function ( e ) {

			var btn = e.target.closest ? e.target.closest( '[data-tp-go]' ) : null;

			if ( btn ) {

				var key = btn.getAttribute( 'data-tp-go' );
				/* ۲ه٫۱ · P5-D310 — با prefers-reduced-motion بی حرکت */
				var how = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth';

				if ( 'top' === key ) {
					window.scrollTo( { top: 0, behavior: how } );
					return;
				}

				if ( 'save' === key ) {
					var s = page.querySelector( '[data-tp-save]' );
					if ( s ) { s.click(); }
					return;
				}

				var el = document.getElementById( key );

				if ( el ) {
					el.scrollIntoView( { behavior: how, block: 'start' } );
				}

				return;
			}

			if ( e.target.closest && e.target.closest( '[data-tp-sheet-open]' ) && sheet ) {
				sheet.setAttribute( 'open', '' );
			}
		} );

		if ( sheet ) {

			/* آیتم حالا آیکون و برچسب دارد؛ کلیک روی هر کدام هم برگه را می‌بندد.  P5-D228 */
			sheet.addEventListener( 'click', function ( e ) {
				var t = e.target.closest ? e.target : e.target.parentNode;
				if ( t && ( t.hasAttribute( 'data-tp-close' ) || t.closest( 'a' ) ) ) {
					sheet.removeAttribute( 'open' );
				}
			} );

			document.addEventListener( 'keydown', function ( e ) {
				if ( 'Escape' === e.key ) { sheet.removeAttribute( 'open' ); }
			} );
		}
	} )();

	/* ══ نشانگر نقشه ════════════════════════════════════════════════════ */
	Array.prototype.forEach.call( page.querySelectorAll( '.tp-map-pin' ), function ( pin ) {

		pin.addEventListener( 'click', function () {

			pin.style.transform = 'translate(-50%,-50%) scale(1.2)';

			window.setTimeout( function () {
				pin.style.transform = 'translate(-50%,-50%)';
			}, 160 );
		} );
	} );

	/* ══ نقشۀ زنده — لایۀ آداپتور  P5-D195 · P5-D241 ═════════════════
	   صفحه فقط با `TopersiaMap` حرف می‌زند؛ کتابخانه و منبع کاشی پشت آن‌اند
	   و پیکربندی‌شان را سرور روی ظرف گذاشته (`data-tp-map-cfg`).
	     ارائه‌دهنده   MapLibre GL JS 5 + PMTiles خودمیزبان، نقشۀ پایۀ Protomaps
	     برچسب        زبان صفحه (name:fa · name:en · name:ar …)، نبود ← name
	     بارگذاری      نه در head: کتابخانه فقط وقتی کارت دیده شد یا مودال باز شد
	   ────────────────────────────────────────────────────────────────── */
	var TopersiaMap = window.TopersiaMap || ( function () {

		var ready = null;

		function css( href ) {
			return new Promise( function ( ok ) {
				var l = document.createElement( 'link' );
				l.rel = 'stylesheet';
				l.href = href;
				l.onload = ok;
				l.onerror = ok;
				document.head.appendChild( l );
			} );
		}

		function js( src ) {
			return new Promise( function ( ok, no ) {
				var s = document.createElement( 'script' );
				s.src = src;
				s.async = false;
				s.onload = ok;
				s.onerror = no;
				document.head.appendChild( s );
			} );
		}

		function load( cfg ) {

			if ( ready ) {
				return ready;
			}

			var b = cfg.base;

			ready = Promise.all( [ css( b + 'maplibre-gl.css' ), js( b + 'maplibre-gl.js' ), js( b + 'pmtiles.js' ), js( b + 'basemaps.js' ) ] ).then( function () {

				var ml = window.maplibregl;

				if ( ! ml || ! window.pmtiles || ! window.basemaps ) {
					throw new Error( 'map' );
				}

				ml.addProtocol( 'pmtiles', new window.pmtiles.Protocol().tile );

				/* شکل‌دادن متن فارسی و عربی — تنبل: فقط وقتی برچسب راست‌به‌چپ هست. */
				try {
					if ( 'unavailable' === ml.getRTLTextPluginStatus() ) {
						ml.setRTLTextPlugin( b + 'mapbox-gl-rtl-text.js', true );
					}
				} catch ( e ) {}

				return ml;
			} );

			ready.catch( function () { ready = null; } );

			return ready;
		}

		/* حالت عصر و شب ← سبک تیرۀ Protomaps؛ روز ← روشن. حالت از `data-tp-mode` صفحه (همان کلید کاربر).  P5-D346 */
		function flavorOf() {
			var m = document.documentElement.getAttribute( 'data-tp-mode' ) || 'light';
			return ( 'dark' === m || 'evening' === m ) ? 'dark' : 'light';
		}

		/*
		 * نقشۀ آنلاین با پشتیبان آفلاین — P5-D353
		 * ترتیب پیش‌فرض: آنلاین اول (OpenFreeMap، بی کلید)، PMTiles خودمیزبان پشتیبان. هنگام ساخت نقشه **یک** درخواست آزمایشی (خودِ style آنلاین) با مهلت
		 * کوتاه (cfg.online.probe_ms، ۲٫۵ ثانیه)؛ خطا، مهلت یا پاسخ غیر ۲۰۰ ← همان لحظه آفلاین، بی تلاش دوباره، بی پیام خطا. نتیجه در sessionStorage
		 * (۰ ← صفحه‌های بعدیِ همین بازدید مستقیم آفلاین؛ بازدید بعدی دوباره یک بار می‌سنجد). هیچ درخواستی پیش از دیده‌شدن نقشه نیست (mount پس از دید).
		 */
		var ONLINE_KEY = 'tp-map-online';
		var STYLE_KEY  = 'tp-map-style';
		var onlineMemo = {};

		function sget( k ) {
			try { return window.sessionStorage.getItem( k ) || ''; } catch ( e ) { return ''; }
		}

		function sset( k, v ) {
			try { window.sessionStorage.setItem( k, v ); } catch ( e ) {}
		}

		/*
		 * خلیج فارس در هر زبانی که داده‌اش نام دیگری دارد — «الخليج العربي» در name و name:ar داده OSM — برچسب سایت است.  P5-D364
		 * عارضه را با کلید پایدار می‌شناسیم (name:en = Persian Gulf یا name:fa یا name:latin)، نه با موقعیت؛ هر دو سبک (آفلاین و آنلاین) از همین
		 * یک تابع می‌گذرند. متن‌ها با escape یونیکد تا کاتالوگ رشته‌های فارسیِ رابط (tp-i18n) آن‌ها را رشتۀ رابط نگیرد: برچسب نقشه داده است، نه رابط.
		 */
		var GULF_NAME = { fa: '\u062e\u0644\u06cc\u062c \u0641\u0627\u0631\u0633', en: 'Persian Gulf', ar: '\u0627\u0644\u062e\u0644\u064a\u062c \u0627\u0644\u0641\u0627\u0631\u0633\u064a' };

		/* text-field نوع `formatted` است: شاخۀ جایگزین format می‌شود؛ سبک آنلاین که text-field ساده دارد، شاخۀ اصلی هم format می‌شود تا هر دو شاخه هم‌نوع باشند */
		function gulfWrap( field, lang, plain ) {
			return [ 'case',
				[ 'any',
					[ '==', [ 'get', 'name:en' ], 'Persian Gulf' ],
					[ '==', [ 'get', 'name:latin' ], 'Persian Gulf' ],
					[ '==', [ 'get', 'name:fa' ], GULF_NAME.fa ]
				],
				[ 'format', GULF_NAME[ lang ] || GULF_NAME.en, {} ],
				plain ? [ 'format', field, {} ] : field
			];
		}

		/*
		 * خط‌های مرزِ disputed در هر دو سبک پنهان‌اند — ابوموسی و تنب‌ها فقط درون مرز ایران دیده شوند.  P5-D365
		 * آفلاین (Protomaps، لایۀ boundaries): به فیلتر هر لایه «بی disputed» اضافه می‌شود؛ آنلاین (OpenFreeMap، لایۀ boundary): لایۀ ویژۀ
		 * خط‌های disputed (فیلتر disputed == 1) پنهان می‌شود (لایه‌های دیگر آن‌ها را از پیش حذف می‌کنند). با کلید داده، نه موقعیت.
		 */
		function hideDisputed( layer ) {

			if ( 'boundaries' === layer[ 'source-layer' ] ) {

				/* فیلتر basemaps v4 قدیمی‌شکل است (["<=","kind_detail",2])؛ شکل را می‌شناسیم تا ترکیب نامعتبر نسازیم */
				var f      = layer.filter;
				var legacy = Array.isArray( f ) && ( 'string' === typeof f[ 1 ] || ( -1 !== [ 'all', 'any', 'none' ].indexOf( f[ 0 ] ) && Array.isArray( f[ 1 ] ) && 'string' === typeof f[ 1 ][ 1 ] ) );

				if ( ! f ) {
					layer.filter = [ '!', [ 'to-boolean', [ 'get', 'disputed' ] ] ];
				} else if ( legacy ) {
					layer.filter = [ 'all', f, [ '!=', 'disputed', true ], [ '!=', 'disputed', 1 ] ];
				} else {
					layer.filter = [ 'all', f, [ '!', [ 'to-boolean', [ 'get', 'disputed' ] ] ] ];
				}
			} else if ( 'boundary' === layer[ 'source-layer' ] && layer.filter && -1 !== JSON.stringify( layer.filter ).indexOf( '[\"==\",[\"get\",\"disputed\"],1]' ) ) {
				layer.layout = Object.assign( {}, layer.layout, { visibility: 'none' } );
			}

			return layer;
		}

		/*
		 * خوانایی نقشه — P5-D380 (دستۀ ۲۳؛ نسخۀ «پس» را کاربر تأیید کرد — پیش‌فرض دائمی، بی پرچم localStorage).
		 * برچسب: رنگ متن و هالۀ تو‌پر ۱٫۶px به رنگ زمینۀ همان حالت (تضاد ≥ ۴٫۵:۱)، آب آبی خوانا؛ راه: رنگ به تفکیک درجه
		 * با تضاد ≥ ۳:۱ با زمینه و پهنای ۱٫۲ برابر؛ جاده‌های فرعی کم‌رنگ‌تر از این نمی‌شوند. عدد هدف هر ۱۸ ترکیب
		 * (دو سبک × سه حالت × سه بزرگنمایی) را tests/map-legibility.js می‌سنجد و جزو گیت‌هاست.
		 * راه برگشت: MAP_LEGIBLE = false (تنها نقطۀ خاموش‌کردن).
		 */
		var MAP_LEGIBLE = true;

		/* پهنا ×۱٫۲ — عبارت zoom باید سطح بالا بماند، پس خروجی‌های عددی interpolate/step ضرب می‌شوند، نه خود عبارت */
		function widen( w ) {
			if ( 'number' === typeof w ) { return w * 1.2; }
			if ( ! Array.isArray( w ) ) { return w; }
			var c = w.slice(), i;
			if ( 0 === String( c[ 0 ] ).indexOf( 'interpolate' ) ) { for ( i = 4; i < c.length; i += 2 ) { c[ i ] = widen( c[ i ] ); } return c; }
			if ( 'step' === c[ 0 ] ) { for ( i = 2; i < c.length; i += 2 ) { c[ i ] = widen( c[ i ] ); } return c; }
			return w;
		}

		function legible( layer, dark ) {

			var id = layer.id || '';

			if ( 'symbol' === layer.type && layer.layout && layer.layout[ 'text-field' ] ) {
				var water = /water|ocean|lake|river|sea/.test( id );
				layer.paint = Object.assign( {}, layer.paint, {
					'text-color': water ? ( dark ? '#a9d3f5' : '#1b4a72' ) : ( dark ? '#eef2f0' : '#1c2427' ),
					'text-halo-color': dark ? '#141c1f' : '#ffffff',
					'text-halo-width': 1.6,
					'text-halo-blur': 0.2
				} );
			}

			if ( 'line' === layer.type && /road|highway|transportation/.test( id ) && ! /casing|tunnel|pier|runway|rail/.test( id ) ) {
				var major = /motorway|trunk|primary|highway_major/.test( id );
				var mid   = /secondary|tertiary/.test( id );
				var w     = layer.paint && undefined !== layer.paint[ 'line-width' ] ? layer.paint[ 'line-width' ] : 1;
				layer.paint = Object.assign( {}, layer.paint, {
					'line-color': major ? ( dark ? '#dd9a62' : '#a8521f' ) : ( mid ? ( dark ? '#b9b2a6' : '#6f6455' ) : ( dark ? '#8f897f' : '#857a6c' ) ),
					'line-width': widen( w )
				} );
			}

			return layer;
		}

		/* برچسب‌های سبک آنلاین به زبان صفحه با برگشت به name؛ فارسی و عربی بی letter-spacing */
		function localize( st, lang ) {

			( st.layers || [] ).forEach( function ( l ) {

				hideDisputed( l );

				if ( ! l.layout ) {
					return;
				}

				if ( l.layout[ 'text-field' ] ) {
					l.layout[ 'text-field' ] = gulfWrap( [ 'coalesce', [ 'get', 'name:' + lang ], [ 'get', 'name' ] ], lang, true );
				}

				if ( ( 'fa' === lang || 'ar' === lang ) && undefined !== l.layout[ 'text-letter-spacing' ] ) {
					delete l.layout[ 'text-letter-spacing' ];
				}
			} );

			if ( MAP_LEGIBLE ) {
				var dk = 'dark' === flavorOf();
				( st.layers || [] ).forEach( function ( l ) { legible( l, dk ); } );
			}

			return st;
		}

		function onlineStyle( cfg ) {

			var o = cfg.online;

			if ( ! o || 'offline-first' === cfg.order || '0' === sget( ONLINE_KEY ) || ! window.fetch ) {
				return Promise.resolve( null );
			}

			var url = 'dark' === flavorOf() ? o.style_dark : o.style_light;

			if ( onlineMemo[ url ] ) {
				return Promise.resolve( localize( JSON.parse( onlineMemo[ url ] ), cfg.lang || 'fa' ) );
			}

			var ctl   = window.AbortController ? new AbortController() : null;
			var timer = window.setTimeout( function () { if ( ctl ) { ctl.abort(); } }, parseInt( o.probe_ms, 10 ) || 2500 );

			return window.fetch( url, { signal: ctl ? ctl.signal : undefined, credentials: 'omit' } ).then( function ( r ) {
				window.clearTimeout( timer );
				if ( ! r.ok ) { throw new Error( 'http' ); }
				return r.json();
			} ).then( function ( st ) {
				sset( ONLINE_KEY, '1' );
				onlineMemo[ url ] = JSON.stringify( st );
				return localize( st, cfg.lang || 'fa' );
			} ).catch( function () {
				window.clearTimeout( timer );
				sset( ONLINE_KEY, '0' );
				return null;
			} );
		}

		/* آفلاین: PMTiles خودمیزبان. kind: base · transit (خط آهن و ایستگاه برجسته) · labels (برچسب‌ها روی ماهواره) */
		function offlineStyle( cfg, kind ) {

			var base = new URL( cfg.base, location.href ).href;
			var lang = cfg.lang || 'fa';
			var lay  = mapLayers( lang );
			var src  = {
				protomaps: {
					type: 'vector',
					url: 'pmtiles://' + cfg.tiles,
					attribution: '<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">' + ( cfg.attribution || '© OpenStreetMap' ) + '</a>'
				}
			};

			if ( 'transit' === kind ) {

				lay = lay.map( function ( l ) {

					if ( 'roads_rail' === l.id ) {
						l.minzoom = 3;
						l.paint = Object.assign( {}, l.paint, { 'line-color': '#B9871A', 'line-width': [ 'interpolate', [ 'linear' ], [ 'zoom' ], 3, 1.2, 8, 2.4, 14, 5 ], 'line-opacity': 1 } );
						delete l.paint[ 'line-dasharray' ];
					} else if ( 0 === ( l.id || '' ).indexOf( 'roads_' ) && 'line' === l.type ) {
						l.paint = Object.assign( {}, l.paint, { 'line-opacity': 0.3 } );
					} else if ( 'pois' === l.id ) {
						l.filter = [ 'all', [ 'in', [ 'get', 'kind' ], [ 'literal', [ 'station', 'aerodrome' ] ] ], [ '>=', [ 'zoom' ], [ '+', [ 'get', 'min_zoom' ], 0 ] ] ];
						l.layout = Object.assign( {}, l.layout, { 'text-size': [ 'interpolate', [ 'linear' ], [ 'zoom' ], 6, 11, 14, 14 ] } );
					}

					return l;
				} );
			}

			if ( 'labels' === kind ) {

				lay = lay.filter( function ( l ) { return 'symbol' === l.type; } ).map( function ( l ) {
					l.paint = Object.assign( {}, l.paint, { 'text-color': '#ffffff', 'text-halo-color': 'rgba(0,0,0,.75)', 'text-halo-width': 1.4 } );
					return l;
				} );

				src.sat = { type: 'raster', tiles: [ cfg.sat ], tileSize: 256, maxzoom: 19, attribution: '© MapTiler © OpenStreetMap contributors' };
				lay.unshift( { id: 'tp-sat', type: 'raster', source: 'sat' } );
			}

			return {
				version: 8,
				glyphs: base + 'fonts/{fontstack}/{range}.pbf',
				/* اسپرایت نمادهای جادّه/نشان: نسخۀ تیره از منبع رسمی Protomaps basemaps-assets (v4)، خودمیزبان؛ P5-D353 */
				sprite: base + 'sprites/' + ( 'dark' === flavorOf() ? 'dark' : 'light' ),
				sources: src,
				layers: lay
			};
		}

		/* سبک انتخاب‌شده ← { style, online }؛ transit همیشه آفلاین (ارائه‌دهندۀ بی‌کلید ندارد)، satellite فقط با ارائه‌دهندۀ کلیددار پیکربندی‌شده */
		function resolveStyle( cfg, sel ) {

			if ( 'transit' === sel ) {
				return Promise.resolve( { style: offlineStyle( cfg, 'transit' ), online: false, sel: sel } );
			}

			if ( 'satellite' === sel && cfg.sat ) {
				return Promise.resolve( { style: offlineStyle( cfg, 'labels' ), online: true, sel: sel } );
			}

			return onlineStyle( cfg ).then( function ( st ) {
				return st ? { style: st, online: true, sel: 'base' } : { style: offlineStyle( cfg, 'base' ), online: false, sel: 'base' };
			} );
		}

		function savedSel( cfg ) {
			var v = '';
			try { v = window.localStorage.getItem( STYLE_KEY ) || ''; } catch ( e ) {}
			return ( cfg.styles || [] ).indexOf( v ) >= 0 ? v : 'base';
		}

		/*
		 * لایه‌های سبک — P5-D352
		 *   قلم Italic: «Noto Sans Italic» بسته برای بازه‌های عربی ۲۶–۲۸ بایتی تهی است (آب، جزیره، آدرس) ← Regular؛ بی ایتالیک نمی‌ماند.
		 *   letter-spacing: برای فارسی و عربی نه (حروف متصل را می‌گسلد)؛ لاتین همان.
		 *   pois: سبک پیش‌فرض Protomaps فقط فهرست کوتاهی از kind را نشان می‌دهد و «هتل»، «مکان مذهبی» را نه؛ دسته‌های اصلی روشن می‌شوند —
		 *   جاذبه، موزه، مکان مذهبی، پارک، هتل، رستوران و کافه، فرودگاه، ایستگاه — با برچسب به زبان صفحه، در هر سه حالت رنگ (پالت flavor).
		 */
		var POI_KINDS = [ 'attraction', 'museum', 'place_of_worship', 'park', 'hotel', 'restaurant', 'cafe', 'aerodrome', 'station' ];

		function mapLayers( lang ) {

			var flavor = Object.assign( {}, window.basemaps.namedFlavor( flavorOf() ) );
			flavor.italic = 'Noto Sans Regular';

			return window.basemaps.layers( 'protomaps', flavor, { lang: lang } ).map( function ( layer ) {

				hideDisputed( layer );

				if ( ( 'fa' === lang || 'ar' === lang ) && layer.layout && undefined !== layer.layout[ 'text-letter-spacing' ] ) {
					delete layer.layout[ 'text-letter-spacing' ];
				}

				/* فقط برچسب‌های آب: برخی لایه‌های دیگر text-field با «zoom» در سطح بالا دارند و case دورشان سبک را نامعتبر می‌کند */
				if ( 0 === ( layer.id || '' ).indexOf( 'water' ) && layer.layout && layer.layout[ 'text-field' ] ) {
					layer.layout[ 'text-field' ] = gulfWrap( layer.layout[ 'text-field' ], lang );
				}

				if ( MAP_LEGIBLE ) {
					legible( layer, 'dark' === flavorOf() );
				}

				if ( 'pois' === layer.id ) {
					layer.filter = [ 'all', [ 'in', [ 'get', 'kind' ], [ 'literal', POI_KINDS ] ], [ '>=', [ 'zoom' ], [ '+', [ 'get', 'min_zoom' ], 0 ] ] ];
				}

				return layer;
			} );
		}

		/* ── نشانگرها: آیکون هر خانواده، خوشه‌بندی داخلی GeoJSON خودِ MapLibre، پنجرۀ کوچک  P5-D346 ── */
		var FAM = {
			city:       'M3 21h18v-2H3v2zM5 19h2V9H5v10zm6 0h2V5h-2v14zm6 0h2v-7h-2v7z',
			attraction: 'M12 2 2 8v2h20V8L12 2zM4 11v7h3v-7H4zm6.5 0v7h3v-7h-3zM17 11v7h3v-7h-3zM2 20v2h20v-2H2z',
			nature:     'M14 6l-3.75 5 2.85 3.8-1.6 1.2C9.81 13.75 7 10 7 10l-6 8h22L14 6z',
			district:   'M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z',
			village:    'M12 3 2 12h3v8h6v-6h2v6h6v-8h3L12 3z'
		};

		function famImage( fam, color, ratio ) {

			var size = Math.round( 34 * ratio );
			var cv   = document.createElement( 'canvas' );
			cv.width = cv.height = size;

			var c = cv.getContext( '2d' );
			c.beginPath();
			c.arc( size / 2, size / 2, size / 2 - 2 * ratio, 0, Math.PI * 2 );
			c.fillStyle = color;
			c.fill();
			c.lineWidth = 2 * ratio;
			c.strokeStyle = '#ffffff';
			c.stroke();
			c.save();
			c.translate( size / 2 - 10 * ratio, size / 2 - 10 * ratio );
			c.scale( 20 * ratio / 24, 20 * ratio / 24 );
			c.fillStyle = '#ffffff';
			c.fill( new Path2D( FAM[ fam ] ) );
			c.restore();

			return c.getImageData( 0, 0, size, size );
		}

		function safeUrl( u ) {
			return /^(https?:)?\/\//.test( u ) || '/' === ( u || '' ).charAt( 0 ) ? u : '';
		}

		function popupNode( p ) {

			var box = document.createElement( 'div' );
			box.className = 'tp-mp';

			if ( p.i && safeUrl( p.i ) ) {
				var im = document.createElement( 'img' );
				im.className = 'tp-mp__img';
				im.alt       = '';
				im.loading   = 'lazy';
				im.src       = p.i;
				box.appendChild( im );
			}

			var name = document.createElement( safeUrl( p.u ) ? 'a' : 'span' );
			name.className   = 'tp-mp__name';
			name.textContent = p.n || '';

			if ( safeUrl( p.u ) ) {
				name.setAttribute( 'href', p.u );
			}

			var kind = document.createElement( 'span' );
			kind.className   = 'tp-mp__kind';
			kind.textContent = p.t || '';

			box.appendChild( name );
			box.appendChild( kind );

			return box;
		}

		function addPoints( map, ml, box, interactive ) {

			var data = box.tpPoints;

			if ( ! data || ! data.features || ! data.features.length || map.getSource( 'tp-pts' ) ) {
				return;
			}

			var tint  = getComputedStyle( box ).getPropertyValue( '--tp-primary' ).trim() || '#0e7c74';
			var ratio = window.devicePixelRatio >= 2 ? 2 : 1;

			Object.keys( FAM ).forEach( function ( f ) {
				if ( ! map.hasImage( 'tp-fam-' + f ) ) {
					map.addImage( 'tp-fam-' + f, famImage( f, tint, ratio ), { pixelRatio: ratio } );
				}
			} );

			map.addSource( 'tp-pts', { type: 'geojson', data: data, cluster: true, clusterRadius: 46, clusterMaxZoom: 11 } );

			map.addLayer( { id: 'tp-pts-cluster', type: 'circle', source: 'tp-pts', filter: [ 'has', 'point_count' ],
				paint: { 'circle-color': tint, 'circle-radius': [ 'step', [ 'get', 'point_count' ], 16, 10, 20, 50, 26 ], 'circle-stroke-width': 2, 'circle-stroke-color': '#ffffff' } } );
			map.addLayer( { id: 'tp-pts-count', type: 'symbol', source: 'tp-pts', filter: [ 'has', 'point_count' ],
				layout: { 'text-field': [ 'get', 'point_count_abbreviated' ], 'text-font': [ 'Noto Sans Regular' ], 'text-size': 13, 'text-allow-overlap': true },
				paint: { 'text-color': '#ffffff' } } );
			/*
			 * نام نشانگر — خوانا و بی هم‌پوشانی (P5-D387): زیر نماد، قلم همان سبک (Noto Sans Regular — بازۀ عربی دارد، P5-D352)،
			 * هاله به رنگ سطح و متن به رنگ متنِ حالت جاری؛ نامی که جا ندارد پنهان می‌شود (`text-optional`) نه روی دیگری.
			 */
			var ink  = getComputedStyle( box ).getPropertyValue( '--tp-text' ).trim() || '#1f2a2e';
			var halo = getComputedStyle( box ).getPropertyValue( '--tp-surface' ).trim() || '#ffffff';
			map.addLayer( { id: 'tp-pts-icon', type: 'symbol', source: 'tp-pts', filter: [ '!', [ 'has', 'point_count' ] ],
				layout: { 'icon-image': [ 'concat', 'tp-fam-', [ 'get', 'k' ] ], 'icon-allow-overlap': true, 'icon-size': 1,
					'text-field': [ 'get', 'n' ], 'text-font': [ 'Noto Sans Regular' ], 'text-size': 12.5, 'text-anchor': 'top', 'text-offset': [ 0, 1.35 ],
					'text-max-width': 9, 'text-optional': true, 'text-allow-overlap': false, 'text-ignore-placement': false, 'text-padding': 3 },
				paint: { 'text-color': ink, 'text-halo-color': halo, 'text-halo-width': 1.6, 'text-halo-blur': 0.3 } } );

			if ( ! interactive ) {
				return;
			}

			var pop = null;

			map.on( 'click', 'tp-pts-cluster', function ( e ) {
				var f = map.queryRenderedFeatures( e.point, { layers: [ 'tp-pts-cluster' ] } )[ 0 ];
				map.getSource( 'tp-pts' ).getClusterExpansionZoom( f.properties.cluster_id ).then( function ( z ) {
					map.easeTo( { center: f.geometry.coordinates, zoom: z } );
				} ).catch( function () {} );
			} );

			map.on( 'click', 'tp-pts-icon', function ( e ) {

				var f = e.features && e.features[0];

				if ( ! f ) {
					return;
				}

				if ( pop ) { pop.remove(); }

				pop = new ml.Popup( { offset: 18, maxWidth: '240px' } ).setLngLat( f.geometry.coordinates.slice() ).setDOMContent( popupNode( f.properties ) ).addTo( map );
			} );

			[ 'tp-pts-cluster', 'tp-pts-icon' ].forEach( function ( id ) {
				map.on( 'mouseenter', id, function () { map.getCanvas().style.cursor = 'pointer'; } );
				map.on( 'mouseleave', id, function () { map.getCanvas().style.cursor = ''; } );
			} );
		}

		function loadPoints( map, ml, box, cfg, interactive ) {

			if ( ! cfg.points || ! window.fetch ) {
				return;
			}

			fetch( cfg.points ).then( function ( r ) { return r.ok ? r.json() : null; } ).then( function ( d ) {
				if ( d && d.features ) {
					box.tpPoints = d;
					addPoints( map, ml, box, interactive );
				}
			} ).catch( function () {} );
		}

		/*
		 * مرز محدوده — P5-D351. GeoJSON ساده‌شدۀ همان موجودیت (لایۀ مشتق از OpenStreetMap، scripts/tp-boundaries.php) فقط وقتی
		 * سرور می‌داند فایلش هست (cfg.bound) و فقط وقتی نقشه در دید است (mount پس از IntersectionObserver / کلیک)؛ خط مرز و پرشدگی کم‌رنگ
		 * با رنگ برند صفحه؛ نقشه روی همان محدوده تنظیم می‌شود. روی هر دو سبک (آنلاین و آفلاین) و پس از تغییر حالت رنگ دوباره می‌نشیند.
		 */
		function addBound( map, ml, box ) {

			var d = box.tpBound;

			if ( ! d ) {
				return;
			}

			var tint = getComputedStyle( box ).getPropertyValue( '--tp-hover' ).trim() || getComputedStyle( box ).getPropertyValue( '--tp-primary' ).trim() || '#177E7A';

			if ( ! map.getSource( 'tp-bound' ) ) {
				map.addSource( 'tp-bound', { type: 'geojson', data: d, attribution: '© OpenStreetMap contributors' } );
			}

			if ( ! map.getLayer( 'tp-bound-fill' ) ) {
				map.addLayer( { id: 'tp-bound-fill', type: 'fill', source: 'tp-bound', paint: { 'fill-color': tint, 'fill-opacity': 0.08 } } );
				map.addLayer( { id: 'tp-bound-line', type: 'line', source: 'tp-bound', paint: { 'line-color': tint, 'line-width': 2, 'line-opacity': 0.85 }, layout: { 'line-join': 'round' } } );
			}
		}

		function boundsOf( d ) {

			var b = [ 180, 90, -180, -90 ];

			( function walk( c ) {
				if ( 'number' === typeof c[ 0 ] ) {
					b = [ Math.min( b[ 0 ], c[ 0 ] ), Math.min( b[ 1 ], c[ 1 ] ), Math.max( b[ 2 ], c[ 0 ] ), Math.max( b[ 3 ], c[ 1 ] ) ];
					return;
				}
				c.forEach( walk );
			}( ( d.geometry || d.features[ 0 ].geometry ).coordinates ) );

			return b;
		}

		function loadBound( map, ml, box, cfg ) {

			if ( ! cfg.bound || ! window.fetch ) {
				return;
			}

			fetch( cfg.bound ).then( function ( r ) { return r.ok ? r.json() : null; } ).then( function ( d ) {
				if ( ! d ) {
					return;
				}
				box.tpBound = d;
				addBound( map, ml, box );
				try {
					var b = boundsOf( d );
					map.fitBounds( [ [ b[ 0 ], b[ 1 ] ], [ b[ 2 ], b[ 3 ] ] ], { padding: 24, duration: 0, maxZoom: 12 } );
				} catch ( e ) {}
			} ).catch( function () {} );
		}

		/* یک نقشه با نشانگر؛ تعاملی فقط در مودال. قول با رویداد load وفا می‌شود. */
		function mount( box, cfg, lnglat, interactive ) {

			return load( cfg ).then( function ( ml ) {

				return resolveStyle( cfg, interactive ? savedSel( cfg ) : 'base' ).then( function ( first ) {

				box.tpOnline = first.online;

				var map = new ml.Map( {
					container: box,
					style: first.style,
					center: lnglat,
					zoom: cfg.zoom || 8,
					interactive: !! interactive,
					attributionControl: { compact: false },
					/* دکمه‌ها و aria-label نقشه و نشانگر به زبان صفحه — از کاتالوگ سرور.  P5-D278 */
					locale: cfg.locale || {}
				} );

				/* برای آزمون و افزونه‌های دیگر: همان نقشه روی ظرفش. */
				box.tpMap = map;

				var tint = getComputedStyle( box ).getPropertyValue( '--tp-primary' ).trim();

				new ml.Marker( tint ? { color: tint } : {} ).setLngLat( lnglat ).addTo( map );

				if ( interactive ) {
					map.addControl( new ml.NavigationControl( { showCompass: false } ), 'top-left' );
				}

				/*
				 * اعمال یک سبک روی نقشۀ زنده — بارگذاری کامل ← style.load؛ نشانگرها و مرز از دادۀ همان دریافت دوباره می‌نشینند
				 * (با diff نشانگرها بی‌صدا حذف می‌شدند). آنلاین که کاشی‌اش خطا بدهد یک‌بار فوری آفلاین می‌شود — بی پیام، بی تلاش دوباره.
				 */
				var fell = false;

				function reapply() {
					map.once( 'style.load', function () { addPoints( map, ml, box, interactive ); addBound( map, ml, box ); } );
				}

				function fallBack() {

					if ( fell || ! box.tpOnline ) {
						return;
					}

					fell = true;
					box.tpOnline = false;
					sset( ONLINE_KEY, '0' );
					map.setStyle( offlineStyle( cfg, 'base' ), { diff: false } );
					reapply();
				}

				map.on( 'error', function ( e ) {
					if ( box.tpOnline && e && e.error && ( e.sourceId || e.error.status >= 400 ) ) {
						fallBack();
					}
				} );

				function applySel( sel ) {
					resolveStyle( cfg, sel ).then( function ( r ) {
						box.tpOnline = r.online;
						box.tpSel    = r.sel;
						map.setStyle( r.style, { diff: false } );
						reapply();
						if ( box.tpStyleBtns ) {
							Array.prototype.forEach.call( box.tpStyleBtns, function ( b ) { b.setAttribute( 'aria-pressed', b.getAttribute( 'data-style' ) === r.sel ? 'true' : 'false' ); } );
						}
					} );
				}

				box.tpFlavor = flavorOf();
				box.tpSel    = first.sel;

				/* انتخاب‌گر سبک: فقط نقشۀ تعاملی (مودال)؛ هر سبک فقط هنگام انتخاب بار می‌شود؛ انتخاب در localStorage */
				if ( interactive ) {
					var bar = box.parentNode ? box.parentNode.querySelector( '[data-tp-map-styles]' ) : null;

					if ( bar ) {
						box.tpStyleBtns = bar.querySelectorAll( 'button[data-style]' );
						Array.prototype.forEach.call( box.tpStyleBtns, function ( b ) {
							b.setAttribute( 'aria-pressed', b.getAttribute( 'data-style' ) === first.sel ? 'true' : 'false' );
							b.addEventListener( 'click', function () {
								var v = b.getAttribute( 'data-style' );
								try { window.localStorage.setItem( STYLE_KEY, v ); } catch ( e ) {}
								applySel( v );
							} );
						} );
					}
				}

				/* تغییر حالت (روز · عصر · شب) در جا */
				if ( window.MutationObserver ) {
					new MutationObserver( function () {
						var f = flavorOf();
						if ( f === box.tpFlavor ) {
							return;
						}
						box.tpFlavor = f;
						applySel( box.tpSel || 'base' );
					} ).observe( document.documentElement, { attributes: true, attributeFilter: [ 'data-tp-mode' ] } );
				}

				return new Promise( function ( ok ) {
					map.once( 'load', function () {
						loadPoints( map, ml, box, cfg, interactive );
						loadBound( map, ml, box, cfg );
						ok( map );
					} );
				} );

				} );
			} );
		}

		return { adapter: 'maplibre-pmtiles', load: load, mount: mount };
	}() );

	window.TopersiaMap = TopersiaMap;

	function tpLngLat( el ) {
		var p   = ( el.getAttribute( 'data-tp-coords' ) || '' ).split( ',' );
		var lat = parseFloat( p[0] );
		var lon = parseFloat( p[1] );
		return ( isFinite( lat ) && isFinite( lon ) ) ? [ lon, lat ] : null;
	}

	function tpMapCfg( el ) {
		try { return JSON.parse( el.getAttribute( 'data-tp-map-cfg' ) || '{}' ); } catch ( e ) { return {}; }
	}

	/* کارت کناری: غیرتعاملی، وقتی به دید رسید. تا آن موقع SVG مرجع. */
	( function () {

		var cards = page.querySelectorAll( '[data-tp-map][data-tp-map-cfg]' );

		if ( ! cards.length ) {
			return;
		}

		function go( el ) {

			var box = el.querySelector( '[data-tp-map-live]' );
			var ll  = tpLngLat( el );
			var cfg = tpMapCfg( el );

			if ( ! box || ! ll || ! cfg.base ) {
				return;
			}

			TopersiaMap.mount( box, cfg, ll, false ).then( function () {
				el.classList.add( 'tp-map-inner--live' );
			} ).catch( function () {} );
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			Array.prototype.forEach.call( cards, go );
			return;
		}

		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( en ) {
				if ( en.isIntersecting ) {
					io.unobserve( en.target );
					go( en.target );
				}
			} );
		}, { rootMargin: '200px 0px' } );

		Array.prototype.forEach.call( cards, function ( c ) { io.observe( c ); } );
	}() );

	/* مودال: نقشۀ تعاملی، فقط با کلیک «باز کردن نقشۀ کامل». */
	page.addEventListener( 'click', function ( e ) {

		if ( ! e.target.closest || ! e.target.closest( '[data-tp-map-open]' ) ) {
			return;
		}

		var full = document.querySelector( '[data-tp-map-full]' );

		if ( ! full || full.getAttribute( 'data-tp-mounted' ) ) {
			return;
		}

		var ll  = tpLngLat( full );
		var cfg = tpMapCfg( full );

		if ( ! ll || ! cfg.base ) {
			return;
		}

		full.setAttribute( 'data-tp-mounted', '1' );

		TopersiaMap.mount( full, cfg, ll, true ).then( function ( map ) {
			if ( full.parentNode ) { full.parentNode.classList.add( 'tp-modal__map--live' ); }
			map.resize();
		} ).catch( function () {
			full.removeAttribute( 'data-tp-mounted' );
		} );
	} );

	/* ══ هوای سه روز ════════════════════════════════════════════════════
	   دادۀ زنده است و داخل HTML کش‌شده نمی‌آید، وگرنه هر بازدیدکننده
	   هوای روزِ ساختِ کش را می‌بیند. پس پس از بارگذاری از نقطۀ REST
	   خوانده می‌شود — همان چیزی که مرجع با `demo` جایش را نگه داشته بود.
	   ────────────────────────────────────────────────────────────────── */
	( function () {

		var box = page.querySelector( '[data-tp-weather]' );

		if ( ! box || ! window.fetch ) {
			return;
		}

		var root = ( window.TopersiaG && window.TopersiaG.rest ) || '';

		if ( ! root ) {
			return;
		}

		var note = box.parentNode.querySelector( '[data-tp-wx-note]' );

		var ICON = {
			sun:  '<circle cx="12" cy="12" r="4.4"/><path d="M12 2.6v2.2M12 19.2v2.2M2.6 12h2.2M19.2 12h2.2M5.4 5.4l1.6 1.6M17 17l1.6 1.6M18.6 5.4L17 7M7 17l-1.6 1.6"/>',
			part: '<circle cx="9" cy="9" r="3.4"/><path d="M6.5 18h9a3.5 3.5 0 0 0 0-7 5 5 0 0 0-9.6 1.4A3 3 0 0 0 6.5 18z"/>',
			wind: '<path d="M3 8h11a3 3 0 1 0-3-3M3 13h15a3 3 0 1 1-3 3M3 18h8"/>',
			rain: '<path d="M6.5 15h9a3.5 3.5 0 0 0 0-7 5 5 0 0 0-9.6 1.4A3 3 0 0 0 6.5 15z"/><path d="M8 18l-1 2M12 18l-1 2M16 18l-1 2"/>',
			snow: '<path d="M6.5 15h9a3.5 3.5 0 0 0 0-7 5 5 0 0 0-9.6 1.4A3 3 0 0 0 6.5 15z"/><path d="M9 19h.01M12 20h.01M15 19h.01"/>'
		};

		var fa = digits;

		fetch( root + '?coords=' + encodeURIComponent( box.getAttribute( 'data-tp-coords' ) || '' ) )
			.then( function ( r ) { return r.ok ? r.json() : null; } )
			.then( function ( data ) {

				if ( ! data || ! data.days || ! data.days.length ) {
					if ( note ) { note.textContent = ''; }
					return;
				}

				box.innerHTML = data.days.map( function ( x ) {

					return '<div class="tp-wx__day"><span class="tp-wx__d">' + x.d + '</span>'
						+ '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round">'
						+ ( ICON[ x.i ] || ICON.sun ) + '</svg>'
						+ '<b>' + fa( x.t ) + '°</b><span class="tp-wx__n">' + fa( x.n ) + '°</span>'
						+ '<span class="tp-wx__s">' + x.s + '</span></div>';
				} ).join( '' );

				if ( note ) {
					note.textContent = data.note || '';
				}

				/*
				 * انیمیشن سربرگ از همین پاسخ — نه فراخوان دوم.  ۴٫۳٫۰ بند ۳
				 * `mount` خودش حرکت را می‌سنجد (prefers-reduced-motion،
				 * data-motion، data-calm) و هر وضعیت را یک‌بار در نشست نشان
				 * می‌دهد. تا ۴٫۲٫۳ هیچ‌کس `mount` را صدا نمی‌زد.
				 */
				var fx   = window.TopersiaWeatherFx;
				var hero = page.querySelector( '.tp-hero' );

				if ( data.fx && hero && fx && 'function' === typeof fx.mount ) {
					try { fx.mount( hero, data.fx ); } catch ( err ) {}
				}
			} )
			.catch( function () {
				if ( note ) { note.textContent = ''; }
			} );
	} )();

	/* ══ کیفیت هوا ══════════════════════════════════════════════════════
	   دادۀ زنده است و در HTML کش‌شده نمی‌آید: اسکلتِ هم‌اندازه («—») سرِ جایش است و پس از
	   بارگذاری از `topersia/v1/air` پر می‌شود — هیچ جابه‌جایی‌ای نیست (CLS صفر). خطا ← همان «—».
	   رده، برچسب (زبان نشانی) و رنگ از پاسخ؛ رنگ با توکن `--tp-aqi-N` (کلاس رده).  P5-D345
	   ────────────────────────────────────────────────────────────────── */
	( function () {

		var box = page.querySelector( '[data-tp-air]' );
		var root = ( window.TopersiaG && window.TopersiaG.air ) || '';

		if ( ! box || ! root || ! window.fetch ) {
			return;
		}

		var ll = ( box.getAttribute( 'data-tp-coords' ) || '' ).split( ',' );

		function done() {
			box.classList.remove( 'is-load' );
		}

		function put( sel, text ) {
			var el = box.querySelector( sel );
			if ( el ) { el.textContent = text; }
		}

		function num( v ) {
			return null === v || undefined === v ? '—' : digits( Math.round( v ) ) + ' µg/m³';
		}

		if ( ll.length < 2 ) {
			done();
			return;
		}

		fetch( root + '?lat=' + encodeURIComponent( ll[0].trim() ) + '&lng=' + encodeURIComponent( ll[1].trim() ) )
			.then( function ( r ) { return r.ok ? r.json() : null; } )
			.then( function ( d ) {

				if ( ! d || 'number' !== typeof d.aqi ) {
					done();
					return;
				}

				box.setAttribute( 'data-tp-cat', d.cat );
				put( '[data-tp-air-idx]', digits( d.aqi ) );
				put( '[data-tp-air-cat]', d.label || '—' );
				put( '[data-tp-air-pm25]', num( d.pm25 ) );
				put( '[data-tp-air-pm10]', num( d.pm10 ) );
				put( '[data-tp-air-dust]', num( d.dust ) );
				/* ساعت به‌روزرسانی به وقت ایران (P5-D381)؛ متن کامل از سرور به زبان صفحه */
				put( '[data-tp-air-time]', d.updated || '—' );
				done();
			} )
			.catch( done );
	} )();

}() );
