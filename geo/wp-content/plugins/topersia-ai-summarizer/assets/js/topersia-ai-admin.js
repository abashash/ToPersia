/* ToPersia AI Summarizer - settings screen behavior. */
(function () {
	'use strict';

	var cfg = window.tpasAdmin || { ajax: '', nonce: '' };
	var customProviders = cfg.customProviders || ['custom', 'azure'];

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	function toggleRowCustomFields(row) {
		var select = row.querySelector('[data-tpas-provider]');
		var needsCustomFields = select && customProviders.indexOf(select.value) !== -1;
		row.querySelectorAll('[data-tpas-custom]').forEach(function (cell) {
			cell.style.display = needsCustomFields ? '' : 'none';
		});
	}

	function toggleCustomColumnHeader() {
		var body = document.getElementById('tpas-engines-body');
		var header = document.querySelector('#tpas-engines-table thead [data-tpas-custom]');
		if (!header) {
			return;
		}
		var anyNeedsCustom = body && Array.prototype.some.call(
			body.querySelectorAll('[data-tpas-provider]'),
			function (select) { return customProviders.indexOf(select.value) !== -1; }
		);
		header.style.display = anyNeedsCustom ? '' : 'none';
	}

	function testEngineRow(row, button) {
		var result = row.querySelector('.tpas-engine-test-result');
		if (!result || !cfg.ajax || !cfg.nonce) {
			return;
		}

		var select = row.querySelector('[data-tpas-provider]');
		var keyEl = row.querySelector('input[type="password"]');
		var idEl = row.querySelector('input[type="hidden"]');
		var modelEl = row.querySelector('input[name*="[model]"]');
		var baseEl = row.querySelector('input[name*="[base_url]"]');

		button.disabled = true;
		result.className = 'tpas-engine-test-result';
		result.textContent = cfg.testing || 'Testing\u2026';

		var data = new URLSearchParams();
		data.set('action', 'tpas_test_key');
		data.set('nonce', cfg.nonce);
		data.set('provider', select ? select.value : '');
		data.set('id', idEl ? idEl.value : '');
		if (keyEl && keyEl.value) {
			data.set('api_key', keyEl.value);
		}
		if (baseEl && baseEl.value) {
			data.set('base_url', baseEl.value);
		}
		if (modelEl && modelEl.value) {
			data.set('model', modelEl.value);
		}

		fetch(cfg.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: data.toString()
		})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				button.disabled = false;
				if (json && json.success) {
					result.className = 'tpas-engine-test-result is-success';
					result.textContent = cfg.success + (json.data && json.data.message ? ' \u2014 ' + json.data.message : '');
				} else {
					result.className = 'tpas-engine-test-result is-error';
					result.textContent = cfg.error + ((json && json.data && json.data.message) ? ' \u2014 ' + json.data.message : '');
				}
			})
			.catch(function () {
				button.disabled = false;
				result.className = 'tpas-engine-test-result is-error';
				result.textContent = cfg.error || 'Connection failed.';
			});
	}

	function bindRow(row) {
		var select = row.querySelector('[data-tpas-provider]');
		if (select) {
			select.addEventListener('change', function () {
				toggleRowCustomFields(row);
				toggleCustomColumnHeader();
			});
		}

		var testBtn = row.querySelector('.tpas-engine-test');
		if (testBtn) {
			testBtn.addEventListener('click', function () {
				testEngineRow(row, testBtn);
			});
		}

		var removeBtn = row.querySelector('.tpas-engine-remove');
		if (removeBtn) {
			removeBtn.addEventListener('click', function () {
				row.remove();
				toggleCustomColumnHeader();
			});
		}

		toggleRowCustomFields(row);
	}

	function addEngineRow() {
		var template = document.getElementById('tpas-engine-template');
		var body = document.getElementById('tpas-engines-body');
		if (!template || !body) {
			return;
		}

		var clone = template.content.cloneNode(true);
		var row = clone.querySelector('tr.tpas-engine-row');
		if (!row) {
			return;
		}

		// Give this brand-new row a unique client-side id. The server
		// (TP_AI_Options::sanitize_engines()) recognises the "new_" prefix
		// and swaps it for a real, stable id on save.
		var clientId = 'new_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);

		row.setAttribute('data-id', clientId);
		row.querySelectorAll('[name]').forEach(function (field) {
			field.name = field.name.replace('[internal_engines][new]', '[internal_engines][' + clientId + ']');
		});
		var idField = row.querySelector('input[type="hidden"]');
		if (idField) {
			idField.value = clientId;
		}
		var radio = row.querySelector('input[type="radio"]');
		if (radio) {
			radio.value = clientId;
		}

		body.appendChild(row);
		bindRow(row);
		toggleCustomColumnHeader();
	}

	function initTabs() {
		var tabs = document.querySelectorAll('.tpas-nav__tab');
		var panels = document.querySelectorAll('.tpas-tab-panel');
		if (!tabs.length || !panels.length) {
			return;
		}

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				var name = tab.getAttribute('data-tpas-tab');

				tabs.forEach(function (t) {
					var on = t === tab;
					t.classList.toggle('is-active', on);
					t.setAttribute('aria-selected', on ? 'true' : 'false');
				});

				panels.forEach(function (panel) {
					panel.classList.toggle('is-active', panel.getAttribute('data-tpas-panel') === name);
				});
			});
		});
	}

	ready(function () {
		initTabs();

		document.querySelectorAll('#tpas-engines-body tr.tpas-engine-row').forEach(bindRow);
		toggleCustomColumnHeader();

		var addBtn = document.getElementById('tpas-add-engine');
		if (addBtn) {
			addBtn.addEventListener('click', addEngineRow);
		}
	});
})();
