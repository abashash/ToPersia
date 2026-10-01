/*!
 * ToPersia — پنل نمایش و خوانایی
 * نسخه: 3.0.0  ·  P5-D151 · P5-D156 · P5-D157 · P5-D158
 *
 * قاعده: این فایل هرگز HTML سرور را عوض نمی‌کند. فقط صفت روی <html> و
 * مقدار متغیرهای CSS. پس کش صفحه و CDN دست‌نخورده می‌مانند.
 *
 * بازیابی پیش از نخستین رنگ‌آمیزی در اسکریپت درون‌خطی افزونه انجام می‌شود.
 *
 * گروه‌های انحصاری با معناشناسی رادیویی کار می‌کنند، نه دکمۀ فشرده:
 * صفحه‌خوان «۲ از ۴» می‌گوید و کلید جهت‌دار میان گزینه‌ها حرکت می‌کند.
 */
(function () {
	'use strict';

	if (typeof document === 'undefined' || !document.documentElement) { return; }

	var root = document.documentElement;

	/* ── ذخیره‌سازی ──────────────────────────────────────────────── */
	function put(key, value) {
		try { window.localStorage.setItem('tp-' + key, value); } catch (e) {}

		/* اگر لایۀ حساب کاربری (هستۀ کاربری) روی صفحه بارگذاری شده، این
		   رویداد را می‌شنود و در حساب کاربر همگام می‌کند. اگر آن افزونه
		   نباشد، هیچ شنونده‌ای نیست و این خط بی‌اثر است — بدون هیچ وابستگی
		   مستقیم بین دو افزونه. */
		try {
			document.dispatchEvent(new CustomEvent('topersia:display-changed', { detail: { key: key, value: value } }));
		} catch (e) {}
	}
	function read(key) {
		try { return window.localStorage.getItem('tp-' + key); } catch (e) { return null; }
	}

	/* ── اعلام برای صفحه‌خوان ────────────────────────────────────── */
	function announce(message) {
		var live = document.getElementById('tp-a11y-status');
		if (!live) { return; }
		live.textContent = '';
		window.setTimeout(function () { live.textContent = message; }, 40);
	}

	/* ── اعمال یک تنظیم ─────────────────────────────────────────── */
	function prefersDark() {
		try { return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); }
		catch (e) { return false; }
	}

	function apply(key, value) {
		if (key === 'mode') {
			root.setAttribute('data-tp-mode', value === 'auto' ? (prefersDark() ? 'dark' : 'light') : value);
			return;
		}
		if (key === 'scale')   { root.style.setProperty('--tp-scale', value); return; }
		if (key === 'spacing') { root.setAttribute('data-spacing', value); return; }
		if (key === 'measure') { root.setAttribute('data-measure', value); return; }
	}

	/* «توقف حرکت» باید پخش خودکار را هم متوقف کند.
	   متاباکس مشترک video_sample و audio_narration دارد، پس رسانه واقعاً هست. */
	function freezeMedia(off) {
		if (!off) { return; }
		var media = document.querySelectorAll('video, audio');
		for (var i = 0; i < media.length; i++) {
			try {
				media[i].autoplay = false;
				media[i].removeAttribute('autoplay');
				if (!media[i].paused) { media[i].pause(); }
			} catch (e) {}
		}
	}

	/* ── گروه رادیویی ───────────────────────────────────────────── */
	function radios(group) {
		return group.querySelectorAll('[role="radio"]');
	}

	function select(group, target, tell) {
		var list = radios(group);
		for (var i = 0; i < list.length; i++) {
			var on = (list[i] === target);
			list[i].setAttribute('aria-checked', on ? 'true' : 'false');
			list[i].setAttribute('tabindex', on ? '0' : '-1');
		}

		var key = target.getAttribute('data-tp-set');
		var val = target.getAttribute('data-tp-val');
		apply(key, val);
		put(key, val);

		if (tell) {
			announce((group.getAttribute('data-tp-label') || '') + ': ' + (target.textContent || '').trim());
		}
	}

	function bindGroup(group) {
		var list = radios(group);

		for (var i = 0; i < list.length; i++) {
			list[i].addEventListener('click', function () { select(group, this, true); });
		}

		group.addEventListener('keydown', function (event) {
			var keys = ['ArrowRight', 'ArrowLeft', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
			if (keys.indexOf(event.key) === -1) { return; }

			var items = radios(group);
			if (!items.length) { return; }

			var current = 0;
			for (var i = 0; i < items.length; i++) {
				if (items[i] === document.activeElement) { current = i; }
			}

			var next = current;
			if (event.key === 'Home') { next = 0; }
			else if (event.key === 'End') { next = items.length - 1; }
			else if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') { next = (current + 1) % items.length; }
			else { next = (current - 1 + items.length) % items.length; }

			event.preventDefault();
			select(group, items[next], true);
			items[next].focus();
		});
	}

	/* ── بازتاب وضعیت ذخیره‌شده روی دکمه‌ها ─────────────────────── */
	function sync() {
		var saved = {
			mode:    read('mode')    || 'auto',
			scale:   read('scale')   || '1',
			spacing: read('spacing') || 'normal',
			measure: read('measure') || 'standard'
		};

		var keys = ['mode', 'scale', 'spacing', 'measure'];
		for (var k = 0; k < keys.length; k++) {
			var key   = keys[k];
			/* کلید حالت رنگ در سربرگ است، نه داخل پنل — پس کل سند جست‌وجو می‌شود */
			var group = document.querySelector('[role="radiogroup"][data-tp-key="' + key + '"]');
			if (!group) { continue; }

			var target = group.querySelector('[data-tp-val="' + saved[key] + '"]') || radios(group)[0];
			if (target) { select(group, target, false); }
		}

		var toggles = ['contrast', 'calm', 'motion'];
		for (var t = 0; t < toggles.length; t++) {
			var btn = document.querySelector('[data-tp-toggle="' + toggles[t] + '"]');
			if (!btn) { continue; }
			var on = !!root.getAttribute('data-' + toggles[t]);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
			if (toggles[t] === 'motion') { freezeMedia(on); }
		}
	}

	/* ── راه‌اندازی ─────────────────────────────────────────────── */
	function init() {
		var panel = document.getElementById('tp-a11y-panel');
		var all   = document.querySelectorAll('.tp-a11y-btn');

		/* ۱ — گروه‌های رادیویی، هر جای صفحه.
		   کلید حالت رنگ در سربرگ است و پنل ممکن است اصلاً وجود نداشته باشد. */
		var groups = document.querySelectorAll('[role="radiogroup"][data-tp-key]');
		for (var g = 0; g < groups.length; g++) { bindGroup(groups[g]); }

		/* ۲ — کلیدهای دوحالته */
		var toggles = document.querySelectorAll('[data-tp-toggle]');
		for (var j = 0; j < toggles.length; j++) {
			toggles[j].addEventListener('click', function () {
				var key = this.getAttribute('data-tp-toggle');
				var on  = this.getAttribute('data-tp-on') || 'on';
				var was = this.getAttribute('aria-pressed') === 'true';

				if (was) { root.removeAttribute('data-' + key); }
				else     { root.setAttribute('data-' + key, on); }

				this.setAttribute('aria-pressed', was ? 'false' : 'true');
				put(key, was ? '' : on);
				if (key === 'motion') { freezeMedia(!was); }

				announce((this.textContent || '').trim() + ' — ' +
					(was ? this.getAttribute('data-tp-off-text') : this.getAttribute('data-tp-on-text')));
			});
		}

		/* ۳ — پیش‌تنظیم و بازنشانی، فقط اگر پنل هست */
		var preset = document.querySelector('.tp-a11y__preset');
		if (preset) {
			preset.addEventListener('click', function () {
				var wanted = { scale: '1.3', spacing: 'loose', measure: 'narrow' };
				var keys   = ['scale', 'spacing', 'measure'];
				for (var i = 0; i < keys.length; i++) {
					var group  = document.querySelector('[role="radiogroup"][data-tp-key="' + keys[i] + '"]');
					if (!group) { continue; }
					var target = group.querySelector('[data-tp-val="' + wanted[keys[i]] + '"]');
					if (target) { select(group, target, false); }
				}
				announce(preset.getAttribute('data-tp-done') || '');
			});
		}

		var reset = document.querySelector('.tp-a11y__reset');
		if (reset) {
			reset.addEventListener('click', function () {
				var offs = ['contrast', 'calm', 'motion'];
				for (var i = 0; i < offs.length; i++) {
					root.removeAttribute('data-' + offs[i]);
					put(offs[i], '');
				}
				root.style.removeProperty('--tp-scale');
				put('mode', 'auto'); put('scale', '1');
				put('spacing', 'normal'); put('measure', 'standard');
				sync();
				announce(reset.getAttribute('data-tp-done') || '');
			});
		}

		/* ۴ — وضعیت اولیه */
		sync();

		/* ۵ — پنل: اگر نبود، دکمه‌اش هم نباید بماند */
		if (!panel) {
			for (var h = 0; h < all.length; h++) { all[h].hidden = true; }
			return;
		}
		if (!all.length) { return; }

		if (panel.parentNode !== document.body) { document.body.appendChild(panel); }
		panel.classList.add('tp-a11y--float');

		var active = all[0];

		function headerOf(el) {
			return el.closest('.whb-clone')
				|| el.closest('.whb-header')
				|| el.closest('header, .site-header, .whb-main-header')
				|| el;
		}

		function place() {
			var anchor = headerOf(active);
			var box    = anchor.getBoundingClientRect();
			var mode   = '';
			try { mode = window.getComputedStyle(anchor).position; } catch (e) {}

			var stuck = ('fixed' === mode || 'sticky' === mode);
			var top   = stuck ? box.bottom
				: box.bottom + (window.pageYOffset || document.documentElement.scrollTop || 0);

			panel.style.position = stuck ? 'fixed' : 'absolute';
			panel.style.top      = Math.round(Math.max(top, 0)) + 'px';
			panel.style.left     = '0px';
			panel.style.right    = '0px';
		}

		function open(yes) {
			for (var i = 0; i < all.length; i++) {
				all[i].setAttribute('aria-expanded', yes ? 'true' : 'false');
			}
			panel.hidden = !yes;
			if (yes) { place(); }
		}

		for (var b = 0; b < all.length; b++) {
			all[b].addEventListener('click', function (event) {
				event.preventDefault();
				active = this;
				open(this.getAttribute('aria-expanded') !== 'true');
			});
		}

		document.addEventListener('keydown', function (event) {
			if (event.key !== 'Escape' || panel.hidden) { return; }
			open(false);
			active.focus();
		});

		document.addEventListener('click', function (event) {
			if (panel.hidden || panel.contains(event.target)) { return; }
			for (var i = 0; i < all.length; i++) {
				if (all[i].contains(event.target)) { return; }
			}
			open(false);
		});

		var ticking = false;
		function reposition() {
			if (panel.hidden || ticking) { return; }
			ticking = true;
			window.requestAnimationFrame(function () { place(); ticking = false; });
		}
		window.addEventListener('resize', reposition);
		window.addEventListener('scroll', reposition, true);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
