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

		function apply() {

			var top = headerBottom();

			if ( top !== snavTop ) {
				snavTop = top;
				page.style.setProperty( '--tp-snav-top', top + 'px' );
				page.style.setProperty( '--tp-header-h', top + 'px' );
			}

			if ( ! nav ) {
				return;
			}

			/* روی موبایل نوار پنهان است و بلندی‌اش صفر — لنگرِ بخش فقط هدر.  P5-D228 */
			var h = nav.offsetHeight;

			if ( h !== snavH ) {
				snavH = h;
				page.style.setProperty( '--tp-snav-h', h + 'px' );
			}

			/* چسبیده یعنی جای طبیعیِ نوار از خطِ چسبیدن بالاتر رفته. */
			var prev    = nav.previousElementSibling;
			var natural = prev
				? prev.getBoundingClientRect().bottom + ( parseFloat( window.getComputedStyle( nav ).marginTop ) || 0 )
				: nav.getBoundingClientRect().top;
			var now     = h > 0 && natural < top - 0.5;

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
			var steady = 0;

			function want() {
				return Math.max( 0, Math.round( el.getBoundingClientRect().top + ( window.pageYOffset || 0 )
					- ( Math.max( 0, snavTop ) + ( snavH > 0 ? snavH : nav.offsetHeight ) ) ) );
			}

			function step() {

				if ( token !== goingTo ) {
					return;
				}

				var y = want();

				if ( Math.abs( y - aim ) > 2 ) {
					aim    = y;
					steady = 0;
					window.scrollTo( { top: y, behavior: calm() ? 'auto' : 'smooth' } );
				} else if ( Math.abs( ( window.pageYOffset || 0 ) - aim ) <= 1 ) {
					steady ++;
				} else {
					steady = 0;
				}

				/* رسیده و چند فریم ماندگار ← پایان؛ سقف ۱٫۸ ثانیه */
				if ( steady < 8 && Date.now() - began < 1800 ) {
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

				/*
				 * ذخیره نیست: فقط واردشده می‌سازد، سمت سرور، از نقطۀ خودِ
				 * خلاصه‌ساز با نانس — همان کش مشترکِ (پست، نوع، زبان) و سقف‌های
				 * روزانه‌ای که «هستۀ نمایش» پیش از آن می‌سنجد.  ۴٫۳٫۰ بند ۴
				 * مهمان این ویژگی را ندارد (`data-tp-ai-gen` برایش چاپ نمی‌شود).
				 */
				var nonce = out.getAttribute( 'data-tp-ai-gen' );

				if ( ! nonce || ! window.fetch || btn.disabled ) {
					return;
				}

				var form = new URLSearchParams();
				form.set( 'action', 'tpas_ai_generate' );
				form.set( 'nonce', nonce );
				form.set( 'post', out.getAttribute( 'data-tp-ai-post' ) || '' );
				form.set( 'type', 'bullets' === kind ? 'keypoints' : kind );
				form.set( 'lang', out.getAttribute( 'data-tp-ai-lang' ) || 'fa' );
				form.set( 'url', out.getAttribute( 'data-tp-ai-url' ) || location.href );

				btn.disabled     = true;
				body.textContent = t( 'در حال ساختن…' );
				mark();

				fetch( out.getAttribute( 'data-tp-ai-ajax' ) || '/wp-admin/admin-ajax.php', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: form.toString()
				} ).then( function ( r ) { return r.json(); } ).then( function ( r ) {

					if ( r && r.success && r.data && r.data.text ) {
						data[ kind ] = String( r.data.text )
							.replace( /<[^>]*>/g, '' ).replace( /\*\*|__/g, '' )
							.replace( /^[\t ]*[-*]\s+/gm, '• ' ).trim();
						body.textContent = data[ kind ];
						return;
					}

					body.textContent = ( r && r.data && r.data.message ) ? r.data.message : t( 'ساختن ممکن نشد.' );
				} ).catch( function () {
					body.textContent = t( 'ارتباط برقرار نشد. دوباره تلاش کنید.' );
				} ).then( function () {
					btn.disabled = false;
				} );
			} );
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

	/* ══ کاشی‌های ستون کناری — P5-D244 ══════════════════════════════════
	   سه ستون، مثل «استان‌های هم‌مرز». اگر در یک کارت حتی یک کاشی سرریز کند
	   — نامی که با `word-break:normal` و بی‌خط‌تیره نمی‌تواند وسط واژه بشکند —
	   همان کارت دو ستونه می‌شود. هر بار در سه ستون سنجیده می‌شود، پس با
	   پهن‌تر شدن ستون به سه برمی‌گردد. ResizeObserver فقط تغییر پهنا را
	   می‌شمارد تا عوض‌کردنِ ستون‌ها دور بی‌پایان نسازد؛ رسیدن قلم هم دوباره
	   می‌سنجد، چون پهنای واژه با قلم عوض می‌شود.
	   ────────────────────────────────────────────────────────────────── */
	function fitTiles( grid ) {

		grid.classList.remove( 'tp-tiles--2' );

		for ( var i = 0; i < grid.children.length; i ++ ) {

			var tile = grid.children[ i ];

			if ( ! tile.hidden && tile.scrollWidth > tile.clientWidth + 1 ) {
				grid.classList.add( 'tp-tiles--2' );
				return;
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

		function style( cfg ) {

			var base = new URL( cfg.base, location.href ).href;

			return {
				version: 8,
				glyphs: base + 'fonts/{fontstack}/{range}.pbf',
				sprite: base + 'sprites/light',
				sources: {
					protomaps: {
						type: 'vector',
						url: 'pmtiles://' + cfg.tiles,
						attribution: '<a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">' + ( cfg.attribution || '© OpenStreetMap' ) + '</a>'
					}
				},
				layers: window.basemaps.layers( 'protomaps', window.basemaps.namedFlavor( 'light' ), { lang: cfg.lang || 'fa' } )
			};
		}

		/* یک نقشه با نشانگر؛ تعاملی فقط در مودال. قول با رویداد load وفا می‌شود. */
		function mount( box, cfg, lnglat, interactive ) {

			return load( cfg ).then( function ( ml ) {

				var map = new ml.Map( {
					container: box,
					style: style( cfg ),
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

				return new Promise( function ( ok ) {
					map.once( 'load', function () { ok( map ); } );
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

}() );
