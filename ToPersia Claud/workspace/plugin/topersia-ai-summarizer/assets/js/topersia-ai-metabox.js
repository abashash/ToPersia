/**
 * ToPersia AI — metabox «ToPersia AI»: Generate / Regenerate one output.
 * 1.3.0 · P5-D358: the admin makes the summaries; admin AJAX, own nonce.
 */
(function () {
	'use strict';

	var cfg = window.tpasGen || {};

	function run(box, row, btn) {
		var msg = box.querySelector('.tpas-gen__msg');
		var status = row.querySelector('.tpas-gen__status');
		var data = new URLSearchParams();

		data.set('action', 'tpas_admin_generate');
		data.set('nonce', box.getAttribute('data-nonce') || '');
		data.set('post', box.getAttribute('data-post') || '0');
		data.set('type', row.getAttribute('data-type') || 'summary');

		btn.disabled = true;
		if (msg) {
			msg.textContent = cfg.working || '';
		}

		fetch(cfg.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: data.toString()
		})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				var d = (json && json.data) || {};
				if (status && d.status) {
					status.setAttribute('data-status', String(d.status));
					status.textContent = String(d.label || d.status);
				}
				if (json && json.success) {
					btn.textContent = cfg.regenerate || btn.textContent;
				}
				if (msg) {
					msg.textContent = String(json && json.success ? (cfg.done || '') : (d.message || cfg.error || ''));
				}
			})
			.catch(function () {
				if (msg) {
					msg.textContent = cfg.error || '';
				}
			})
			.then(function () {
				btn.disabled = false;
			});
	}

	document.addEventListener('click', function (e) {
		var btn = e.target && e.target.closest ? e.target.closest('.tpas-gen__go') : null;
		if (!btn || !cfg.ajax) {
			return;
		}
		var box = btn.closest('.tpas-gen');
		var row = btn.closest('.tpas-gen__row');
		if (box && row) {
			e.preventDefault();
			run(box, row, btn);
		}
	});
})();
