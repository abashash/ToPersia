/*!
 * ToPersia User Core — همگام‌سازی نمایش و خوانایی با حساب کاربری
 *
 * هر هفت کنترل پنل «نمایش و خوانایی» سربرگ سایت همگام می‌شود: حالت رنگ،
 * اندازۀ متن، فاصلۀ متن، پهنای ستون متن، کنتراست بالا، کاهش شدت رنگ و
 * نور، توقف حرکت و پخش خودکار.
 *
 * این فایل مستقل از topersia-display.js است و رقیبش نیست: همان کلیدها
 * و همان صفت‌های <html> را می‌خواند/می‌نویسد، فقط منبع اضافه‌ای برای
 * سینک با حساب کاربر اضافه می‌کند. اگر افزونۀ «هستۀ نمایش» نصب نباشد،
 * این فایل بی‌خطر است — فقط چیزی برای اعمال‌کردن پیدا نمی‌کند.
 */
(function () {
	'use strict';

	if (typeof document === 'undefined' || !window.ToPersiaDisplaySync) { return; }

	var cfg = window.ToPersiaDisplaySync;
	var root = document.documentElement;
	var SYNCED_KEYS = ['mode', 'scale', 'spacing', 'measure', 'contrast', 'calm', 'motion'];
	var TOGGLE_ON_VALUE = { contrast: 'high', calm: 'on', motion: 'off' };

	function put(key, value) {
		try { window.localStorage.setItem('tp-' + key, value); } catch (e) {}
	}
	function read(key) {
		try { return window.localStorage.getItem('tp-' + key); } catch (e) { return null; }
	}

	function prefersDark() {
		try { return !!(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches); }
		catch (e) { return false; }
	}

	/* همان منطق apply() هستۀ نمایش — چون آن فایل ممکن است هنوز اجرا
	   نشده یا اصلاً بارگذاری نشده باشد، اینجا یک نسخۀ مستقل لازم است. */
	function apply(key, value) {
		if (key === 'mode' && value)  { root.setAttribute('data-tp-mode', value === 'auto' ? (prefersDark() ? 'dark' : 'light') : value); return; }
		if (key === 'scale' && value) { root.style.setProperty('--tp-scale', value); return; }
		if (key === 'spacing' && value) { root.setAttribute('data-spacing', value); return; }
		if (key === 'measure' && value) { root.setAttribute('data-measure', value); return; }
		if (key === 'contrast' || key === 'calm' || key === 'motion') {
			if (value) { root.setAttribute('data-' + key, value); } else { root.removeAttribute('data-' + key); }
		}
	}

	/* ۱ — اگر حساب کاربر ترجیحی دارد که این دستگاه هنوز نمی‌داند
	   (دستگاه تازه، یا localStorage پاک شده)، همینجا وارد می‌کنیم. */
	function importAccountPrefs() {
		var account = window.ToPersiaDisplayPrefs;
		if (!account || typeof account !== 'object') { return; }

		SYNCED_KEYS.forEach(function (key) {
			if (!(key in account)) { return; }

			var accountVal = account[key];
			var deviceVal = read(key);

			/* دستگاه چیزی برای این کلید ندارد؛ مقدار حساب برنده است. */
			if (deviceVal === null) {
				put(key, accountVal);
				apply(key, accountVal);
			}
		});
	}

	/* ۲ — از این به بعد، هر تغییری که در همین دستگاه اتفاق بیفتد (از
	   طریق هستۀ نمایش یا هر جای دیگر که رویداد را پخش کند) در حساب
	   کاربر هم ذخیره می‌شود. با تأخیر کوتاه، تا کلیک‌های پشت‌سرهم روی یک
	   گروه رادیویی هرکدام یک درخواست جدا نسازند. */
	var pending = {};
	var timer = null;

	function flush() {
		var keys = Object.keys(pending);
		if (!keys.length) { return; }

		keys.forEach(function (key) {
			var payload = new URLSearchParams();
			payload.set('action', 'topersia_save_display_prefs');
			payload.set('nonce', cfg.nonce || '');
			payload.set('key', key);
			payload.set('value', pending[key]);

			fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: payload }).catch(function () {});
		});

		pending = {};
	}

	document.addEventListener('topersia:display-changed', function (event) {
		var detail = event && event.detail;
		if (!detail || !detail.key || SYNCED_KEYS.indexOf(detail.key) === -1) { return; }

		pending[detail.key] = detail.value;

		window.clearTimeout(timer);
		timer = window.setTimeout(flush, 600);
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', importAccountPrefs);
	} else {
		importAccountPrefs();
	}

	/* برای فرم داخل پنل کاربری (که همین apply/put/dispatch را عیناً
	   دوباره پیاده می‌کند تا به ترتیب بارگذاری اسکریپت‌ها وابسته نباشد)
	   این توابع را در دسترس می‌گذاریم. */
	window.ToPersiaDisplaySync.apply = apply;
	window.ToPersiaDisplaySync.put = put;
	window.ToPersiaDisplaySync.read = read;
	window.ToPersiaDisplaySync.toggleOnValue = TOGGLE_ON_VALUE;
})();
