/* ToPersia AI Summarizer - front-end behavior. */
(function () {
	'use strict';

	var cfg = window.tpasFront || { ajax: '', nonce: '' };

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	function trackClick(service, post) {
		if (!cfg.ajax || !cfg.nonce || !service || !post) {
			return;
		}
		var data = new URLSearchParams();
		data.set('action', 'tpas_track');
		data.set('nonce', cfg.nonce);
		data.set('service', service);
		data.set('post', post);

		fetch(cfg.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: data.toString()
		}).catch(function () {});
	}

	function copyText(text, successLabel, button) {
		if (!text) {
			return;
		}

		var done = function () {
			if (button && successLabel) {
				var original = button.dataset.label || button.textContent;
				button.textContent = successLabel;
				if (original) {
					button.dataset.label = original;
				}
				setTimeout(function () {
					button.textContent = original;
				}, 1600);
			}
		};

		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(done).catch(function () { fallbackCopy(text, done); });
		} else {
			fallbackCopy(text, done);
		}
	}

	function fallbackCopy(text, done) {
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild(ta);
		ta.select();
		try {
			document.execCommand('copy');
		} catch (e) {}
		document.body.removeChild(ta);
		if (done) {
			done();
		}
	}

	/* ---------------------------------------------------------------
	   External buttons component (.tpas)
	   --------------------------------------------------------------- */
	function initButtons() {
		var groups = document.querySelectorAll('.tpas[data-tpas-tracking="1"]');
		groups.forEach(function (group) {
			group.addEventListener('click', function (event) {
				var btn = event.target.closest('.tpas__btn[data-tpas-service]');
				if (btn) {
					trackClick(btn.getAttribute('data-tpas-service'), btn.getAttribute('data-tpas-post'));
				}
			});
		});

		document.querySelectorAll('.tpas__copy[data-tpas-prompt]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				copyText(btn.getAttribute('data-tpas-prompt'), cfg.copied || 'Copied to clipboard.', btn);
			});
		});
	}

	/* ---------------------------------------------------------------
	   On-page AI panel (.tpas-ai)
	   --------------------------------------------------------------- */
	function renderOutput(panel, html) {
		var out = panel.querySelector('.tpas-ai__out');
		if (!out) {
			return;
		}

		var textWrap = panel.querySelector('.tpas-ai__out-text');
		if (textWrap) {
			textWrap.innerHTML = html;
			var toolbar = out.querySelector('.tpas-ai__out-toolbar');
			if (!toolbar) {
				toolbar = document.createElement('div');
				toolbar.className = 'tpas-ai__out-toolbar';
				out.appendChild(toolbar);
			}
			var msg = panel.tpasMsg || {};
			toolbar.innerHTML =
				'<button type="button" class="tpas-ai__regenerate" data-tpas-action="generate">' + (msg.regenerate || '') + '</button>' +
				'<button type="button" class="tpas-ai__btn--copy" data-tpas-action="copy">' + (msg.copy || '') + '</button>';
		}

		wirePanel(panel);
	}

	function showError(panel, message) {
		var textWrap = panel.querySelector('.tpas-ai__out-text');
		if (!textWrap) {
			return;
		}
		textWrap.setAttribute('data-tpas-state', 'error');
		textWrap.innerHTML = '<p class="tpas-ai__error">' + message + '</p>';
	}

	function currentType(panel) {
		var active = panel.querySelector('.tpas-ai__btn.is-active[data-tpas-type]');
		return active ? active.getAttribute('data-tpas-type') : panel.tpasConfig.default;
	}

	function generate(panel, type) {
		var cfgData = panel.tpasConfig;
		var textWrap = panel.querySelector('.tpas-ai__out-text');
		if (!textWrap || !cfg.ajax || !cfg.nonce) {
			return;
		}

		if (!cfgData.enabled) {
			return;
		}

		textWrap.setAttribute('data-tpas-state', 'loading');
		textWrap.textContent = cfgData.msg.loading;

		var data = new URLSearchParams();
		data.set('action', 'tpas_ai_generate');
		data.set('nonce', cfg.nonce);
		data.set('post', cfgData.post || 0);
		data.set('type', type || 'summary');
		data.set('lang', cfgData.lang || 'en');
		// 1.2.2 · P5-D356: no page URL is sent - the server uses the post's own permalink.

		fetch(cfg.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: data.toString()
		})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				if (json && json.success) {
					textWrap.setAttribute('data-tpas-state', 'content');
					panel.tpasText = json.data.text;
					renderOutput(panel, json.data.html, json.data);
				} else {
					var msg = (json && json.data && json.data.message) ? json.data.message : cfgData.msg.error;
					panel.tpasText = '';
					showError(panel, msg);
				}
			})
			.catch(function () {
				panel.tpasText = '';
				showError(panel, cfgData.msg.error);
			});
	}

	function markType(panel, type) {
		panel.querySelectorAll('.tpas-ai__btn[data-tpas-type]').forEach(function (btn) {
			var on = btn.getAttribute('data-tpas-type') === type;
			btn.classList.toggle('is-active', on);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
	}

	function wirePanel(panel) {
		/* Type buttons */
		panel.querySelectorAll('.tpas-ai__btn[data-tpas-type]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var type = btn.getAttribute('data-tpas-type');
				markType(panel, type);
				if (panel.tpasConfig.enabled && panel.tpasConfig.ready) {
					generate(panel, type);
				}
			});
		});

		/* Generate + regenerate */
		panel.querySelectorAll('.tpas-ai__cta, .tpas-ai__regenerate').forEach(function (btn) {
			btn.addEventListener('click', function () {
				generate(panel, currentType(panel));
			});
		});

		/* Copy the generated result (not a raw prompt - none is exposed here) */
		panel.querySelectorAll('.tpas-ai__btn--copy').forEach(function (copyBtn) {
			copyBtn.addEventListener('click', function () {
				if (panel.tpasText) {
					copyText(panel.tpasText, panel.tpasConfig.msg.copied, copyBtn);
				}
			});
		});
	}

	function initPanels() {
		document.querySelectorAll('.tpas-ai[data-tpas-json]').forEach(function (panel) {
			var config = {};
			try {
				config = JSON.parse(panel.getAttribute('data-tpas-json'));
			} catch (e) {
				return;
			}

			panel.tpasConfig = config;
			panel.tpasMsg = config.msg || {};
			panel.tpasText = '';

			markType(panel, config.default || 'summary');
			wirePanel(panel);

			if (config.auto) {
				setTimeout(function () {
					generate(panel, currentType(panel));
				}, 400);
			}
		});
	}

	function contenttype(panel) {
		return currentType(panel);
	}

	ready(function () {
		initButtons();
		initPanels();
	});
})();