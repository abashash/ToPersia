/**
 * کاوشگر کنتراست — داخل صفحه اجرا می‌شود و نتیجه را روی <body> می‌گذارد.
 *
 * چیزی که این را از یک بررسی ایستای CSS جدا می‌کند: رنگ واقعیِ رندرشده را
 * می‌خواند. توکن‌ها، color-mix، color(srgb ...)، ارث‌بری، و لایه‌های
 * نیمه‌شفاف همه در همین‌جا حل می‌شوند.  P5-D191
 */
(function () {
	'use strict';

	/*
	 * انتقال و انیمیشن پیش از هر سنجشی خاموش می‌شوند.
	 *
	 * وگرنه با عوض‌کردن حالت رنگ، `getComputedStyle` مقدارِ وسطِ انتقال را
	 * برمی‌گرداند نه مقصد را. کارت فرهنگ `transition:.24s` روی همهٔ
	 * ویژگی‌ها دارد و در حالت شب رنگِ روز را گزارش می‌کرد — افتادنی که
	 * اصلاً وجود نداشت.
	 */
	var freeze = document.createElement('style');
	freeze.textContent = '*,*::before,*::after{'
		+ 'transition:none !important;animation:none !important;'
		+ 'transition-duration:0s !important;animation-duration:0s !important}';
	document.head.appendChild(freeze);

	var cv = document.createElement('canvas');
	cv.width = cv.height = 1;
	var cx = cv.getContext('2d', { willReadFrequently: true });

	/* هر نحو رنگی CSS را به sRGB واقعی می‌برد — از rgb() تا color(srgb ...) */
	function px(s) {
		cx.clearRect(0, 0, 1, 1);
		cx.fillStyle = '#000';
		cx.fillRect(0, 0, 1, 1);
		cx.fillStyle = s;
		cx.fillRect(0, 0, 1, 1);
		var d = cx.getImageData(0, 0, 1, 1).data;
		return [d[0], d[1], d[2]];
	}

	function alphaOf(s) {
		cx.clearRect(0, 0, 1, 1);
		cx.fillStyle = s;
		cx.fillRect(0, 0, 1, 1);
		return cx.getImageData(0, 0, 1, 1).data[3] / 255;
	}

	/*
	 * رنگ و آلفا را از هم جدا می‌کند.
	 *
	 * `px()` رنگ را روی سیاه می‌نشاند، پس برای رنگ نیمه‌شفاف مقدارِ
	 * تیره‌شده (c×a) را می‌دهد نه خودِ c. اگر همان را به `over()` بدهیم،
	 * آلفا دوبار اعمال می‌شود و پوششِ غیرسیاه تیره‌تر از واقع درمی‌آید.
	 * روی rgba(0,0,0,…) نامرئی است و به همین دلیل تا کالیبراسیون
	 * P5-D193 پیدا نشد.
	 *
	 * با دو بار نشاندن — یک‌بار روی سیاه و یک‌بار روی سفید — هم آلفا
	 * به‌دست می‌آید هم رنگِ اصلی.
	 */
	function rgba(s) {

		var onBlack = px(s);

		cx.clearRect(0, 0, 1, 1);
		cx.fillStyle = '#fff';
		cx.fillRect(0, 0, 1, 1);
		cx.fillStyle = s;
		cx.fillRect(0, 0, 1, 1);
		var d = cx.getImageData(0, 0, 1, 1).data;
		var onWhite = [d[0], d[1], d[2]];

		/* میانگین سه کانال، تا خطای گردکردن هشت‌بیتی کمتر بچربد */
		var a = 0;
		for (var i = 0; i < 3; i++) { a += 1 - (onWhite[i] - onBlack[i]) / 255; }
		a = a / 3;

		if (a < 0.004) { return [0, 0, 0, 0]; }
		if (a > 0.996) { return [onBlack[0], onBlack[1], onBlack[2], 1]; }

		return [onBlack[0] / a, onBlack[1] / a, onBlack[2] / a, a];
	}

	function over(f, b) {
		var a = f[3];
		return [f[0] * a + b[0] * (1 - a), f[1] * a + b[1] * (1 - a), f[2] * a + b[2] * (1 - a), 1];
	}

	/* تیره‌ترین توقفِ یک گرادیان — پوشش رسانه معمولاً همین است */
	function darkestStop(img) {
		var m = img.match(/rgba?\([^)]+\)/g);
		if (!m) { return null; }
		var list = m.map(rgba).filter(function (c) { return c[3] > 0.05; });
		if (!list.length) { return null; }
		return list.reduce(function (a, b) {
			return (a[0] + a[1] + a[2]) * a[3] < (b[0] + b[1] + b[2]) * b[3] ? a : b;
		});
	}

	/*
	 * بدترین توقفِ گرادیان برای یک رنگ متن.
	 *
	 * «تیره‌ترین» همیشه بدترین نیست: متن سفید روی توقفِ روشن می‌افتد، نه
	 * روی تیره. پس توقفی برگردانده می‌شود که کمترین نسبت را با متن
	 * می‌دهد — همان پیکسلی که کاربر بدترین حالتش را می‌بیند.
	 *
	 * بی‌این، بلوک هوش مصنوعی (گرادیان سبز تیره با متن سفید) و کاشی
	 * گالری (گرادیان طلایی–سبز با برچسب سفید) هر دو با زمینهٔ صفحه
	 * سنجیده می‌شدند و عدد ۱٫۰۵ می‌دادند — عددی که هیچ ربطی به آنچه
	 * روی صفحه است ندارد.
	 */
	function worstStop(img, text) {
		var m = img.match(/rgba?\([^)]+\)/g);
		if (!m) { return null; }
		var list = m.map(rgba).filter(function (c) { return c[3] > 0.05; });
		if (!list.length) { return null; }
		if (!text) { return darkestStop(img); }
		return list.reduce(function (a, b) {
			return ratio([a[0], a[1], a[2]], text) <= ratio([b[0], b[1], b[2]], text) ? a : b;
		});
	}

	/*
	 * شبه‌عنصر فقط وقتی پس‌زمینه است که واقعاً بپوشاند.
	 *
	 * موقعیت و inset کافی نیست: نوار گرادیانی سه‌پیکسلیِ بالای کارت هم
	 * `position:absolute` دارد و هم `inset-inline:0`، ولی پشتِ متن نیست.
	 * پس ابعاد رندرشده‌اش با ابعاد میزبان سنجیده می‌شود.
	 */
	function covers(el, ps) {
		if ('none' === ps.content) { return false; }
		if ('absolute' !== ps.position && 'fixed' !== ps.position) { return false; }

		var host = el.getBoundingClientRect();
		if (host.width < 1 || host.height < 1) { return false; }

		var w = parseFloat(ps.width), h = parseFloat(ps.height);
		if (isNaN(w) || isNaN(h)) { return false; }

		return w >= host.width * 0.9 && h >= host.height * 0.9;
	}

	/* لایه‌ها را تا اولین پس‌زمینهٔ کدر جمع و از پایین به بالا ترکیب می‌کند */
	function stack(el, text) {
		var layers = [], e = el;
		while (e && e.nodeType === 1) {
			var cs = getComputedStyle(e);
			['::after', '::before'].forEach(function (pe) {
				var ps = getComputedStyle(e, pe);
				if (!covers(e, ps)) { return; }
				if ('none' !== ps.backgroundImage) {
					var d = worstStop(ps.backgroundImage, text);
					if (d) { layers.push(d); }
				}
				var pc = rgba(ps.backgroundColor);
				if (pc[3] > 0.02) { layers.push(pc); }
			});

			/*
			 * گرادیانِ خودِ عنصر — نه فقط شبه‌عنصرش.  P5-D225
			 *
			 * `.ai` و `.card-slide__art` و `.gallery-main__item` هر سه
			 * `background:linear-gradient(...)` روی خود عنصر دارند و
			 * `background-color` شفاف. کاوشگر آنها را رد می‌کرد و تا
			 * زمینهٔ صفحه بالا می‌رفت؛ متن سفید روی سبز تیره عدد ۱٫۰۵
			 * می‌گرفت.
			 */
			var solidImg = false;

			if ('none' !== cs.backgroundImage) {
				var own = worstStop(cs.backgroundImage, text);
				if (own) {
					layers.push(own);
					solidImg = own[3] > 0.99;
				}
			}

			var c = rgba(cs.backgroundColor);
			if (c[3] > 0.02) { layers.push(c); }
			if (c[3] > 0.99 || solidImg) { break; }
			e = e.parentElement;
		}
		var base = [255, 255, 255, 1];
		for (var i = layers.length - 1; i >= 0; i--) { base = over(layers[i], base); }
		return base;
	}

	function lum(a) {
		function f(v) {
			v /= 255;
			return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
		}
		return 0.2126 * f(a[0]) + 0.7152 * f(a[1]) + 0.0722 * f(a[2]);
	}

	function ratio(a, b) {
		var l1 = lum(a), l2 = lum(b);
		return Math.round(((Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05)) * 100) / 100;
	}

	/*
	 * رنگ‌های ثابتِ مرجع که کاربر نگه داشت — ۴٫۲٫۲ بند ۴.
	 * «رنگ‌های محافظت‌شدهٔ بلوک AI و شمارهٔ کارت مثل مرجع می‌مانند.»
	 * فقط برچسب می‌زند؛ سنجش دست نمی‌خورد. فهرست بسته است و assert.php آن را می‌پاید.
	 */
	function zoneOf(el) {
		if (el.closest && el.closest('#tp-ai')) { return 'بلوک هوش مصنوعی'; }
		if (el.closest && el.closest('.tp-card-slide__no')) { return 'شمارهٔ کارت'; }
		return '';
	}

	function label(el) {
		var c = (el.className || '').toString().trim().split(/\s+/).slice(0, 2).join('.');
		return c ? '.' + c : el.tagName.toLowerCase();
	}

	/* ── حالت‌هایی که سنجش ایستا نمی‌بیند ───────────────────────────── */

	function forceStates() {
		var undo = [];
		document.querySelectorAll('.tp-snav a').forEach(function (e) {
			if (e.classList.contains('tp-is-active')) { return; }
			e.classList.add('tp-is-active');
			undo.push(function () { e.classList.remove('tp-is-active'); });
		});
		document.querySelectorAll('[aria-pressed]').forEach(function (e) {
			var was = e.getAttribute('aria-pressed');
			e.setAttribute('aria-pressed', 'true');
			undo.push(function () { e.setAttribute('aria-pressed', was); });
		});
		/*
		 * مودالِ فهرست و نمای تمام‌صفحه — پنهان‌اند و هرگز سنجیده نمی‌شدند.
		 * متنشان روی پردهٔ تیرهٔ خودشان است؛ باز می‌شوند تا همان پرده
		 * زمینهٔ سنجش باشد.  P5-D198 · P5-D221 · بند ۸
		 */
		document.querySelectorAll('.tp-gallery-modal--list, .tp-gallery-modal--solo').forEach(function (m) {
			if (m.classList.contains('tp-show')) { return; }
			m.classList.add('tp-show');
			undo.push(function () { m.classList.remove('tp-show'); });
		});
		return undo;
	}

	/*
	 * وزن یک سلکتور — سه‌تاییِ (شناسه، کلاس، عنصر) در یک عدد.
	 *
	 * دقتش برای داوری کافی است: شناسه‌ها، سپس کلاس و صفت و شبه‌کلاس،
	 * سپس نام عنصر و شبه‌عنصر. `:is()` و `:not()` وزنِ مشخص‌ترین عضو را
	 * می‌گیرند، ولی آن‌قدر نادرند که تقریبِ ساده بس است — و اگر روزی نبود،
	 * موردِ کالیبراسیون قرمز می‌دهد.
	 */
	/** محتوای یک شبه‌کلاسِ تابعی — با توجه به پرانتزهای تودرتو. */
	function argOf(sel, at) {
		var open = sel.indexOf('(', at);
		if (open < 0) { return null; }
		var depth = 0;
		for (var i = open; i < sel.length; i++) {
			if ('(' === sel[i]) { depth++; }
			else if (')' === sel[i]) {
				depth--;
				if (0 === depth) { return { body: sel.slice(open + 1, i), end: i + 1 }; }
			}
		}
		return null;
	}

	function weigh(sel) {

		var ids = 0, cls = 0, els = 0, i = 0;

		while (i < sel.length) {

			/* `:where()` هیچ وزنی ندارد — نه خودش نه محتوایش */
			var w = /^:where\(/i.exec(sel.slice(i));
			if (w) {
				var a = argOf(sel, i);
				i = a ? a.end : i + w[0].length;
				continue;
			}

			/* `:is()` · `:not()` · `:has()` وزنِ مشخص‌ترین عضوشان را می‌گیرند */
			var f = /^:(is|not|has)\(/i.exec(sel.slice(i));
			if (f) {
				var b = argOf(sel, i);
				if (b) {
					var best = 0;
					var depth = 0, buf = '', parts = [];
					for (var k = 0; k < b.body.length; k++) {
						var ch = b.body[k];
						if ('(' === ch || '[' === ch) { depth++; }
						else if (')' === ch || ']' === ch) { depth--; }
						if (',' === ch && 0 === depth) { parts.push(buf); buf = ''; continue; }
						buf += ch;
					}
					parts.push(buf);
					for (var q = 0; q < parts.length; q++) {
						var pw = weigh(parts[q].trim());
						if (pw > best) { best = pw; }
					}
					ids += Math.floor(best / 10000);
					cls += Math.floor((best % 10000) / 100);
					els += best % 100;
					i = b.end;
					continue;
				}
				i += f[0].length;
				continue;
			}

			if ('#' === sel[i]) {
				var m1 = /^#[\w-]+/.exec(sel.slice(i));
				if (m1) { ids++; i += m1[0].length; continue; }
			}
			if ('.' === sel[i]) {
				var m2 = /^\.[\w-]+/.exec(sel.slice(i));
				if (m2) { cls++; i += m2[0].length; continue; }
			}
			if ('[' === sel[i]) {
				var close = sel.indexOf(']', i);
				cls++;
				i = close < 0 ? sel.length : close + 1;
				continue;
			}
			if (':' === sel[i] && ':' === sel[i + 1]) {
				var m3 = /^::[\w-]+/.exec(sel.slice(i));
				if (m3) { els++; i += m3[0].length; continue; }
			}
			if (':' === sel[i]) {
				var m4 = /^:[\w-]+/.exec(sel.slice(i));
				if (m4) { cls++; i += m4[0].length; continue; }
			}

			var m5 = /^[a-zA-Z][\w-]*/.exec(sel.slice(i));
			if (m5 && (0 === i || /[\s>+~(,]/.test(sel[i - 1]))) {
				els++; i += m5[0].length; continue;
			}

			i++;
		}

		return ids * 10000 + cls * 100 + els;
	}

	/*
	 * قاعده‌های :hover از خود شیت خوانده و موقتاً inline اعمال می‌شوند —
	 * ولی فقط **برنده**.
	 *
	 * نسخهٔ اول هر قاعدهٔ منطبق را جداگانه اعمال می‌کرد، از جمله بازنده‌ها.
	 * چون اعمالِ inline از هر قاعده‌ای قوی‌تر است، رنگی سنجیده می‌شد که
	 * هرگز روی صفحه نمی‌آید. با آمدن CSS زندهٔ قالب (P5-D202) سی‌ودو
	 * افتادنِ دروغین ساخت: وودمارت `a:hover{color:var(--wd-link-color-hover)}`
	 * دارد با وزن (۰,۱,۱) که همیشه زیر `.tp-page .tp-snav a:hover`
	 * (۰,۳,۱) می‌ماند و هیچ‌وقت رنگ نمی‌دهد.
	 *
	 * حالا برای هر عنصر و هر ویژگی، برنده بر پایهٔ !important، بعد وزن،
	 * بعد ترتیب آبشار انتخاب می‌شود.  P5-D203
	 */
	function hoverProbes() {

		var best = new Map();
		var order = 0;
		var baseRules = [];
		var BASE = '\u0000base';

		function consider(el, prop, value, important, spec, seq, sel) {

			if (!value) { return; }

			var slot = best.get(el);
			if (!slot) { slot = { el: el, sel: sel, color: null, bg: null, _c: null, _b: null }; best.set(el, slot); }

			var key = 'color' === prop ? '_c' : '_b';
			var cur = slot[key];
			var cand = { important: important, spec: spec, seq: seq };

			if (cur
				&& !(cand.important && !cur.important)
				&& (cur.important && !cand.important
					|| cand.spec < cur.spec
					|| (cand.spec === cur.spec && cand.seq < cur.seq))) { return; }

			slot[key] = cand;
			slot['color' === prop ? 'color' : 'bg'] = value;
			slot.sel = sel;
		}

		for (var s = 0; s < document.styleSheets.length; s++) {

			var rules;
			try { rules = document.styleSheets[s].cssRules; } catch (err) { continue; }
			if (!rules) { continue; }

			for (var r = 0; r < rules.length; r++) {

				var rule = rules[r];
				order++;

				if (!rule.selectorText) { continue; }

				/*
				 * قاعدهٔ بی‌هاور هم در داوری است.  P5-D225
				 *
				 * هاور فقط وقتی رنگ را عوض می‌کند که از قاعدهٔ عادیِ همان
				 * عنصر سنگین‌تر باشد. `a:hover` وودمارت (۰,۱,۱) از
				 * `html .tp-page a` (۰,۱,۲) سبک‌تر است و روی صفحه هیچ اثری
				 * ندارد، ولی کاوشگر فقط هاورها را با هم می‌سنجید و آن را
				 * برنده می‌گرفت.
				 */
				if (rule.selectorText.indexOf(':hover') < 0) {
					var bcol = rule.style.getPropertyValue('color');
					var bbg  = rule.style.getPropertyValue('background-color') || rule.style.getPropertyValue('background')
						|| rule.style.getPropertyValue('background-image');
					if (bcol || bbg) {
						baseRules.push({ rule: rule, order: order, col: bcol, bg: bbg });
					}
					continue;
				}

				var col = rule.style.getPropertyValue('color');

				/*
				 * `background:linear-gradient(...)` در قاعده به دو لانگ‌هند باز
				 * می‌شود: تصویرِ گرادیان و رنگِ `initial`. رنگ «initial» راست
				 * است ولی شفاف — پیش از P5-D225 همان برداشته می‌شد و گرادیان
				 * گم می‌شد. پس اگر تصویر گرادیان است، همان پرِ هاور است.
				 */
				var bgi = rule.style.getPropertyValue('background-image');
				var bgc = rule.style.getPropertyValue('background-color');
				var bg  = (bgi && /gradient\(/.test(bgi)) ? bgi
					: ((bgc && 'initial' !== bgc) ? bgc : rule.style.getPropertyValue('background'));
				if (!col && !bg) { continue; }

				/* هر شاخهٔ کاما وزن خودش را دارد */
				var branches = rule.selectorText.split(',');

				for (var b = 0; b < branches.length; b++) {

					var one = branches[b].trim();
					if (one.indexOf(':hover') < 0) { continue; }

					var plain = one.replace(/:hover/g, '');
					var els;
					try { els = document.querySelectorAll(plain); } catch (err2) { continue; }

					var spec = weigh(one);

					for (var i = 0; i < els.length; i++) {
						consider(els[i], 'color', col, 'important' === rule.style.getPropertyPriority('color'), spec, order, one);
						consider(els[i], 'bg', bg, 'important' === rule.style.getPropertyPriority('background-color'), spec, order, one);
					}
				}
			}
		}

		/* دور دوم: قاعده‌های عادی که به همین عنصرها می‌خورند، در همان داوری */
		best.forEach(function (slot, el) {
			for (var q = 0; q < baseRules.length; q++) {
				var br = baseRules[q];
				var parts = br.rule.selectorText.split(',');
				for (var pi = 0; pi < parts.length; pi++) {
					var sel = parts[pi].trim();
					var hit = false;
					try { hit = el.matches(sel); } catch (err3) { hit = false; }
					if (!hit) { continue; }
					var sp = weigh(sel);
					if (br.col) {
						consider(el, 'color', BASE, 'important' === br.rule.style.getPropertyPriority('color'), sp, br.order, sel);
					}
					if (br.bg) {
						consider(el, 'bg', BASE, 'important' === br.rule.style.getPropertyPriority('background-color'), sp, br.order, sel);
					}
				}
			}
		});

		/* برندهٔ عادی یعنی هاور در این ویژگی چیزی عوض نمی‌کند */
		best.forEach(function (slot) {
			if (BASE === slot.color) { slot.color = null; }
			if (BASE === slot.bg) { slot.bg = null; }
		});

		var list = Array.from(best.values()).filter(function (s) { return s.color || s.bg; });
		/* نگاشت برای پیداکردن حالتِ هاورِ نیاکان */
		list.byEl = best;
		return list;
	}

	/*
	 * پسِ‌زمینهٔ هاور را روی عنصر می‌نشاند — رنگ یا گرادیان.  P5-D225
	 *
	 * `background:linear-gradient(...)` در هاور به `backgroundColor`
	 * نمی‌نشیند؛ مرورگر مقدار نامعتبر را بی‌صدا دور می‌اندازد و عنصر روی
	 * پس‌زمینهٔ عادی‌اش سنجیده می‌شود. فهرست فرهنگ همین بود: متن سفیدِ
	 * هاور روی کرمِ عادی، ۱٫۱۵.
	 *
	 * @return {Function} برگرداننده
	 */
	function applyBg(el, value) {
		var prevC = el.style.backgroundColor, prevI = el.style.backgroundImage;
		if (/gradient\(/.test(value)) {
			el.style.backgroundColor = 'transparent';
			el.style.backgroundImage = value.replace(/^[^,(]*?(?=(linear|radial|conic|repeating)-gradient)/, '');
		} else {
			el.style.backgroundColor = value;
		}
		return function () {
			el.style.backgroundColor = prevC;
			el.style.backgroundImage = prevI;
		};
	}

	/*
	 * نسبت متن با زمینه‌اش — با آلفای خودِ متن.  P5-D225
	 *
	 * `px()` رنگ را روی سیاه می‌نشاند. برای متن نیمه‌شفاف این یعنی سفیدِ
	 * ۶۲٪ خاکستریِ ۹e شمرده می‌شد، نه سفیدی که از زمینهٔ سبز رنگ گرفته —
	 * و نسبتی که ربطی به صفحه نداشت. متن روی همان زمینه‌ای ترکیب می‌شود
	 * که واقعاً رویش نشسته.
	 */
	function textRatio(el, cs) {
		var tc = rgba(cs.color);
		var bg = stack(el, [tc[0], tc[1], tc[2]]);
		var fg = over(tc, bg);
		return { ratio: ratio(fg, bg), fg: fg, bg: bg };
	}

	/* ── سنجش ─────────────────────────────────────────────────────── */

	var TEXT_MIN = 4.5, UI_MIN = 3.0;
	var fails = [], counts = { text: 0, ui: 0, state: 0, hover: 0 };

	function visible(el) {
		if (!el.offsetParent && 'fixed' !== getComputedStyle(el).position) { return false; }
		var r = el.getBoundingClientRect();
		return r.width > 0 && r.height > 0;
	}

	/*
	 * برگِ متنی — عنصری که متن دارد و جز آیکون فرزندی ندارد.  P5-D225
	 *
	 * شرطِ پیشین «هیچ فرزند» بود. از P5-D223 هر پیوند نوار بخش‌ها یک
	 * `<svg>` کنار متنش دارد و با آن شرط **کل متن نوار** بی‌صدا از سنجش
	 * بیرون می‌افتاد — نه شکستی، نه هشداری. آیکون متن نیست و متنِ کنارش
	 * همچنان متنِ خودِ همان عنصر است.
	 */
	function textLeaf(el) {
		for (var k = 0; k < el.children.length; k++) {
			if ('svg' !== el.children[k].tagName.toLowerCase()) { return false; }
		}
		return !!el.textContent.trim();
	}

	function checkText(mode, state, scope) {
		var els = document.querySelectorAll(scope || '.tp-page *');
		for (var i = 0; i < els.length; i++) {
			var el = els[i];
			if (!textLeaf(el)) { continue; }
			if (!visible(el)) { continue; }
			var cs = getComputedStyle(el);
			if (alphaOf(cs.color) < 0.5) { continue; }
			counts.text++;
			var r = textRatio(el, cs).ratio;
			if (r < TEXT_MIN) {
				fails.push({ kind: 'متن', mode: mode, state: state, zone: zoneOf(el), sel: label(el),
					size: Math.round(parseFloat(cs.fontSize) * 10) / 10, ratio: r, need: TEXT_MIN,
					sample: el.textContent.trim().slice(0, 18) });
			}
		}
	}

	/*
	 * عنصر رابط — فقط کنترلی که **متن ندارد**.
	 *
	 * کنترل متن‌دار با خود متن شناخته می‌شود و بررسی متن پوششش می‌دهد.
	 * اگر هر لینک و کارتی را هم بگیریم آزمون پر از مثبت کاذب می‌شود، و
	 * قرمزِ بی‌معنا از نبودِ آزمون بدتر است.
	 *
	 * مرزِ شناسایی می‌تواند پر باشد یا حلقه یا خط بیرونی — هرکدام که
	 * بیشتر از زمینه جدا می‌شود، همان ملاک است.
	 */
	function checkUi(mode, state) {
		var els = document.querySelectorAll('.tp-page button, .tp-page input, .tp-page select, .tp-page [role="button"]');
		for (var i = 0; i < els.length; i++) {
			var el = els[i];
			if (!visible(el)) { continue; }
			if (el.textContent.trim()) { continue; }
			var cs = getComputedStyle(el);
			var parent = el.parentElement ? stack(el.parentElement) : [255, 255, 255, 1];
			var best = 0, via = '';

			if (alphaOf(cs.backgroundColor) > 0.02) {
				var fill = over(rgba(cs.backgroundColor), parent);
				var rf = ratio([fill[0], fill[1], fill[2]], parent);
				if (rf > best) { best = rf; via = 'پر'; }
			}
			var bw = parseFloat(cs.borderTopWidth) || 0;
			if (bw > 0 && alphaOf(cs.borderTopColor) > 0.02) {
				var ring = over(rgba(cs.borderTopColor), parent);
				var rr = ratio([ring[0], ring[1], ring[2]], parent);
				if (rr > best) { best = rr; via = 'حلقه'; }
			}
			var ow = parseFloat(cs.outlineWidth) || 0;
			if (ow > 0 && 'none' !== cs.outlineStyle && alphaOf(cs.outlineColor) > 0.02) {
				var ol = over(rgba(cs.outlineColor), parent);
				var ro = ratio([ol[0], ol[1], ol[2]], parent);
				if (ro > best) { best = ro; via = 'خط بیرونی'; }
			}
			if (!via) { continue; }

			counts.ui++;
			if (best < UI_MIN) {
				fails.push({ kind: 'عنصر رابط', mode: mode, state: state, zone: zoneOf(el), sel: label(el),
					size: 0, ratio: best, need: UI_MIN,
					sample: (el.getAttribute('aria-label') || '').slice(0, 14) + ' [' + via + ']' });
			}
		}
	}

	/*
	 * نشانگر حالت — خودِ تفاوتِ حالت باید دیده شود.
	 *
	 * اگر پس‌زمینهٔ «فعال» از پس‌زمینهٔ «عادی» کمتر از ۳٫۰ جدا باشد،
	 * کاربر نمی‌فهمد کدام بخش فعال است. بررسی ایستا این را هرگز
	 * نمی‌بیند چون حالت اصلاً در HTML نیست.
	 */
	function checkStateDelta(mode, before) {
		for (var i = 0; i < before.length; i++) {
			var rec = before[i];
			if (!visible(rec.el)) { continue; }
			var now = rgba(getComputedStyle(rec.el).backgroundColor);
			if (now[3] < 0.02 && rec.bg[3] < 0.02) { continue; }
			var parent = rec.el.parentElement ? stack(rec.el.parentElement) : [255, 255, 255, 1];
			var a = over(rec.bg, parent), b = over(now, parent);
			if (Math.abs(a[0] - b[0]) + Math.abs(a[1] - b[1]) + Math.abs(a[2] - b[2]) < 3) { continue; }
			counts.state++;
			var r = ratio([a[0], a[1], a[2]], [b[0], b[1], b[2]]);
			if (r < UI_MIN) {
				fails.push({ kind: 'نشانگر حالت', mode: mode, state: 'فعال در برابر عادی',
					zone: zoneOf(rec.el), sel: label(rec.el), size: 0, ratio: r, need: UI_MIN,
					sample: rec.el.textContent.trim().slice(0, 18) });
			}
		}
	}

	function checkHover(mode) {
		/*
		 * فقط عنصرهای قالب خودمان — همان دامنهٔ checkText و checkUi (`.tp-page *`).
		 * روی صفحهٔ هارنس فرقی نداشت چون چیزی بیرون از قالب نبود؛ روی صفحهٔ
		 * واقعی هاورِ دکمه‌های وودمارت را هم می‌شمرد. خودِ داوری دست نخورده و
		 * کالیبراسیون (که `hoverProbes()` را بی‌این صافی می‌خواند) همان است.
		 */
		var all = hoverProbes();
		var probes = all.filter(function (p) { return p.el.closest && p.el.closest('.tp-page'); });
		/* نقشهٔ کامل عنصر ← هاور برای نیاکان می‌ماند — پرِ والدِ بیرونی هم زمینه است.  P5-D209 */
		probes.byEl = all.byEl;
		for (var i = 0; i < probes.length; i++) {
			var p = probes[i];
			if (!visible(p.el)) { continue; }
			var prevC = p.el.style.color;
			if (p.color) { p.el.style.color = p.color; }
			var undoSelf = p.bg ? applyBg(p.el, p.bg) : null;

			/*
			 * هاور روی یک عنصر، نیاکانش را هم هاور می‌کند.
			 *
			 * نسخهٔ پیشین فقط خودِ عنصر را حالت‌دار می‌کرد، پس متنی که
			 * رنگش با هاورِ **والد** عوض می‌شود، روی پس‌زمینهٔ عادیِ والد
			 * سنجیده می‌شد — دو حالت نیمه‌قاطی که روی صفحه هرگز با هم
			 * نمی‌آیند. دکمهٔ منبع ترجیحی دقیقاً همین شکل است: پر از آنِ
			 * والد است و متن از آنِ فرزند.  P5-D209
			 */
			var undoUp = [];
			var up = p.el.parentElement;

			while (up && up.nodeType === 1) {
				var slot = probes.byEl && probes.byEl.get(up);
				if (slot && slot.bg) {
					undoUp.push(applyBg(up, slot.bg));
				}
				up = up.parentElement;
			}
			var cs = getComputedStyle(p.el);
			var hasText = p.el.textContent.trim() && !p.el.children.length;
			if (hasText && alphaOf(cs.color) > 0.5) {
				counts.hover++;
				var r = textRatio(p.el, cs).ratio;
				if (r < TEXT_MIN) {
					fails.push({ kind: 'متن', mode: mode, state: 'hover', zone: zoneOf(p.el), sel: p.sel,
						size: Math.round(parseFloat(cs.fontSize) * 10) / 10, ratio: r, need: TEXT_MIN,
						sample: p.el.textContent.trim().slice(0, 18) });
				}
			}
			p.el.style.color = prevC;
			if (undoSelf) { undoSelf(); }
			for (var u = 0; u < undoUp.length; u++) {
				undoUp[u]();
			}
		}
	}

	/*
	 * حالت کالیبراسیون — P5-D193
	 *
	 * عمداً از همان stack() و ratio() تولیدی استفاده می‌کند. اگر مسیر
	 * جداگانه‌ای می‌ساخت، چیزی را می‌سنجید که در سنجش واقعی به کار نمی‌رود
	 * و کل آزمون بی‌معنا می‌شد.
	 */
	if (document.body.hasAttribute('data-tp-calibrate')) {

		var cal = [];
		var probes = document.querySelectorAll('[data-expect]');
		var hoverMap = null;

		for (var ci = 0; ci < probes.length; ci++) {

			var cel = probes[ci];
			var cbg;
			var cfg;

			/*
			 * موردِ هاور از همان `hoverProbes()` تولیدی می‌گذرد، نه از
			 * مسیر جدا. این‌طور اگر داوریِ آبشار دوباره خراب شود — مثلاً
			 * قاعدهٔ بازنده اعمال شود — همین‌جا قرمز می‌دهد.  P5-D203
			 */
			if (cel.hasAttribute('data-expect-hover')) {

				if (!hoverMap) {
					hoverMap = new Map();
					hoverProbes().forEach(function (p) { hoverMap.set(p.el, p); });
				}

				var hp = hoverMap.get(cel);
				var prevCol = cel.style.color;

				if (hp && hp.color) { cel.style.color = hp.color; }

				/* خودِ عنصر هم پرِ هاورش را می‌گیرد — همان applyBg تولیدی. */
				var undoCal = [];
				if (hp && hp.bg) { undoCal.push(applyBg(cel, hp.bg)); }

				/* نیاکان هم حالت‌دار می‌شوند — همان مسیر تولیدی.  P5-D209 */
				var upCal = cel.parentElement;

				while (upCal && upCal.nodeType === 1) {
					var slotCal = hoverMap.get(upCal);
					if (slotCal && slotCal.bg) {
						undoCal.push(applyBg(upCal, slotCal.bg));
					}
					upCal = upCal.parentElement;
				}

				var tr = textRatio(cel, getComputedStyle(cel));
				cfg = tr.fg;
				cbg = tr.bg;

				cel.style.color = prevCol;
				for (var uc = 0; uc < undoCal.length; uc++) {
					undoCal[uc]();
				}

			} else {
				var tr2 = textRatio(cel, getComputedStyle(cel));
				cfg = tr2.fg;
				cbg = tr2.bg;
			}
			cal.push({
				name: cel.getAttribute('data-expect-case'),
				expect: parseFloat(cel.getAttribute('data-expect')),
				expectFg: cel.getAttribute('data-expect-fg'),
				expectBg: cel.getAttribute('data-expect-bg'),
				/* گزینشِ برگِ متنی هم کالیبره می‌شود، نه فقط نسبت.  P5-D225 */
				expectLeaf: cel.hasAttribute('data-expect-leaf'),
				gotLeaf: textLeaf(cel),
				gotFg: [cfg[0], cfg[1], cfg[2]].map(function (v) { return Math.round(v * 100) / 100; }).join(','),
				gotBg: [cbg[0], cbg[1], cbg[2]].map(function (v) { return Math.round(v * 100) / 100; }).join(','),
				gotRatio: ratio(cfg, cbg)
			});
		}

		document.body.setAttribute('data-tp-contrast', JSON.stringify({
			calibration: cal
		}));
		return;
	}

	var root = document.documentElement;
	var modes = ['light', 'evening', 'dark'];

	for (var m = 0; m < modes.length; m++) {
		root.setAttribute('data-tp-mode', modes[m]);

		checkText(modes[m], 'عادی');
		checkUi(modes[m], 'عادی');

		/* پس‌زمینهٔ عادیِ هر کنترلِ حالت‌دار را پیش از فعال‌کردن نگه می‌داریم */
		var watched = [];
		document.querySelectorAll('.tp-page .tp-snav a, .tp-page [aria-pressed]').forEach(function (e) {
			watched.push({ el: e, bg: rgba(getComputedStyle(e).backgroundColor) });
		});

		var undo = forceStates();
		checkText(modes[m], 'فعال');
		checkUi(modes[m], 'فعال');
		checkStateDelta(modes[m], watched);
		for (var u = 0; u < undo.length; u++) { undo[u](); }

		/*
		 * نوار چسبیده — سطحِ دیگری زیر همان پیوندها.  P5-D223
		 * فقط خودِ نوار دوباره سنجیده می‌شود؛ بقیهٔ صفحه با چسبیدن عوض
		 * نمی‌شود و شکست‌هایش دو بار شمرده نمی‌شد.
		 */
		var stuckNav = document.querySelectorAll('.tp-page .tp-snav:not(.tp-is-stuck)');
		stuckNav.forEach(function (n) { n.classList.add('tp-is-stuck'); });
		checkText(modes[m], 'چسبیده', '.tp-page .tp-snav *');
		stuckNav.forEach(function (n) { n.classList.remove('tp-is-stuck'); });

		checkHover(modes[m]);
	}

	root.setAttribute('data-tp-mode', 'light');

	document.body.setAttribute('data-tp-contrast', JSON.stringify({
		title: (document.title || '').split('—')[0].trim(),
		counts: counts,
		fails: fails
	}));
})();
