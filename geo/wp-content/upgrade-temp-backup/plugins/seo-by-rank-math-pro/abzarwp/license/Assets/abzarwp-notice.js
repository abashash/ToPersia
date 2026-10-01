/**
 * AbzarWP License Notice System - Unified JavaScript
 *
 * Handles:
 * - Tooltip positioning (RTL-aware with viewport detection)
 * - Plugin row error injection on plugins.php
 * - Deactivation notice dismiss cleanup
 */
(function ($) {
	'use strict';

	var isRTL = document.documentElement.dir === 'rtl' ||
		document.documentElement.getAttribute('lang') === 'fa' ||
		document.documentElement.getAttribute('lang') === 'fa-IR';

	/* ======================================================================
	   Tooltip Module
	   ====================================================================== */
	var AbzarWPTooltip = {

		activeTooltip: null,
		hideTimer: null,
		HIDE_DELAY: 250,
		GAP: 12,

		init: function () {
			this.bindNoticeTooltips();
			this.bindPluginRowErrors();
		},

		bindNoticeTooltips: function () {
			var self = this;

			$(document).on('mouseenter', '.abzarwp-notice__list-item', function () {
				var $item = $(this);
				var $tooltip = $item.find('.abzarwp-tooltip');
				if ($tooltip.length) {
					self.show($item[0], $tooltip[0]);
				}
			});

			$(document).on('mouseleave', '.abzarwp-notice__list-item', function () {
				self.scheduleHide();
			});

			$(document).on('mouseenter', '.abzarwp-tooltip', function () {
				self.cancelHide();
			});

			$(document).on('mouseleave', '.abzarwp-tooltip', function () {
				self.scheduleHide();
			});
		},

		show: function (trigger, tooltip) {
			var self = this;
			self.cancelHide();

			if (self.activeTooltip && self.activeTooltip !== tooltip) {
				self.hideImmediate(self.activeTooltip);
			}

			self.position(trigger, tooltip);
			tooltip.classList.add('abzarwp-tooltip--visible');
			self.activeTooltip = tooltip;
		},

		position: function (trigger, tooltip) {
			var triggerRect = trigger.getBoundingClientRect();
			var tooltipWidth = 360;
			var viewportW = window.innerWidth;
			var viewportH = window.innerHeight;
			var noticeBox = trigger.closest('.abzarwp-notice');
			var noticeRect = noticeBox ? noticeBox.getBoundingClientRect() : null;

			var top, left, arrowClass;

			if (isRTL) {
				// In RTL, prefer opening near the right side of trigger first.
				left = triggerRect.right + this.GAP;
				arrowClass = 'abzarwp-tooltip--arrow-left';

				if (left + tooltipWidth > viewportW - 10) {
					left = triggerRect.left - tooltipWidth - this.GAP;
					arrowClass = 'abzarwp-tooltip--arrow-right';
				}
			} else {
				left = triggerRect.right + this.GAP;
				arrowClass = 'abzarwp-tooltip--arrow-left';

				if (left + tooltipWidth > viewportW - 10) {
					left = triggerRect.left - tooltipWidth - this.GAP;
					arrowClass = 'abzarwp-tooltip--arrow-right';
				}
			}

			top = triggerRect.top + (triggerRect.height / 2);

			var tooltipHeight = tooltip.offsetHeight || 300;
			var halfH = tooltipHeight / 2;

			if (top - halfH < 10) {
				top = halfH + 10;
			}
			if (top + halfH > viewportH - 10) {
				top = viewportH - halfH - 10;
			}

			// Keep tooltip inside notice box when possible.
			if (noticeRect) {
				var minLeft = noticeRect.left + 8;
				var maxLeft = noticeRect.right - tooltipWidth - 8;
				if (maxLeft >= minLeft) {
					if (left < minLeft) {
						left = minLeft;
					}
					if (left > maxLeft) {
						left = maxLeft;
					}
				}
			}

			tooltip.className = tooltip.className
				.replace(/abzarwp-tooltip--arrow-(left|right)/g, '')
				.trim();
			tooltip.classList.add(arrowClass);

			tooltip.style.position = 'fixed';
			tooltip.style.left = left + 'px';
			tooltip.style.top = top + 'px';
			tooltip.style.transform = 'translateY(-50%)';
		},

		scheduleHide: function () {
			var self = this;
			self.cancelHide();
			self.hideTimer = setTimeout(function () {
				if (self.activeTooltip) {
					self.hideImmediate(self.activeTooltip);
					self.activeTooltip = null;
				}
			}, self.HIDE_DELAY);
		},

		cancelHide: function () {
			if (this.hideTimer) {
				clearTimeout(this.hideTimer);
				this.hideTimer = null;
			}
		},

		hideImmediate: function (tooltip) {
			tooltip.classList.remove('abzarwp-tooltip--visible');
		},

		/* ------------------------------------------------------------------
		   Plugin Row Error Injection (plugins.php)
		   ------------------------------------------------------------------ */
		bindPluginRowErrors: function () {
			if (typeof abzarwpPluginRowData === 'undefined') {
				return;
			}

			var data = abzarwpPluginRowData;
			var pluginSlugs = data.slugs || [];
			var pluginsData = data.plugins || {};
			var domain = data.domain || '';
			var errorText = data.errorText || '';
			var self = this;

			function debounce(fn, wait) {
				var t;
				return function () {
					clearTimeout(t);
					t = setTimeout(fn, wait);
				};
			}

			function ensureError(pluginSlug) {
				var $row = $('tr[data-plugin="' + pluginSlug + '"]');
				if (!$row.length) return;

				var $desc = $row.find('.plugin-description');
				if ($desc.find('.abzarwp-row-error').length) return;

				var pluginInfo = pluginsData[pluginSlug];
				var fileId = pluginInfo ? String(pluginInfo.file_id || '').replace(/[^a-zA-Z0-9_-]/g, '') : '';
				var buyUrl = fileId ? 'https://api.abzarwp.com/wp-json/abzarwp-app/v1/file/buy?file_id=' + encodeURIComponent(fileId) : '';
				var domainUrl = fileId ? 'https://files.abzarwp.com/download/' + encodeURIComponent(fileId) : '';

				var $container = $('<div class="abzarwp-row-error"></div>');
				var $errorP = $('<p class="abzarwp-row-error__text"></p>').text(errorText);
				var $helpBtn = $(
					'<span class="abzarwp-row-error__help">' +
					'<span class="dashicons dashicons-editor-help"></span>' +
					'<span class="abzarwp-row-error__help-text">\u0631\u0627\u0647\u0646\u0645\u0627</span>' +
					'</span>'
				);

				var tooltipHtml = self.buildTooltipHTML(domain, buyUrl, domainUrl);
				var $tooltip = $(tooltipHtml);

				$container.append($errorP).append($helpBtn).append($tooltip);
				$desc.append($container);

				var hideTimeout;

				$helpBtn.on('mouseenter', function () {
					clearTimeout(hideTimeout);
					self.show($helpBtn[0], $tooltip[0]);
				});

				$helpBtn.on('mouseleave', function () {
					clearTimeout(hideTimeout);
					hideTimeout = setTimeout(function () {
						self.hideImmediate($tooltip[0]);
					}, self.HIDE_DELAY);
				});

				$tooltip.on('mouseenter', function () {
					clearTimeout(hideTimeout);
				});

				$tooltip.on('mouseleave', function () {
					clearTimeout(hideTimeout);
					hideTimeout = setTimeout(function () {
						self.hideImmediate($tooltip[0]);
					}, self.HIDE_DELAY);
				});
			}

			function checkAll() {
				var slugs = Array.isArray(pluginSlugs) ? pluginSlugs : [pluginSlugs];
				slugs.forEach(ensureError);
			}

			$(document).ready(function () {
				var pluginTable = document.querySelector('#the-list');
				if (!pluginTable) return;

				checkAll();

				var debouncedCheck = debounce(checkAll, 200);
				var observer = new MutationObserver(debouncedCheck);
				observer.observe(pluginTable, {
					childList: true,
					subtree: true,
					attributes: true,
					attributeFilter: ['style', 'class']
				});
			});
		},

		buildTooltipHTML: function (domain, buyUrl, domainUrl) {
			return '<div class="abzarwp-tooltip abzarwp-tooltip--arrow-left">' +
				'<div class="abzarwp-tooltip__bridge"></div>' +
				'<div class="abzarwp-tooltip__header">' +
				'<span class="abzarwp-tooltip__question">\u0628\u0631\u0627\u06cc \u062f\u0627\u0645\u0646\u0647 </span>' +
				'<strong class="abzarwp-tooltip__domain">' + $('<span>').text(domain).html() + '</strong>' +
				'</div>' +
				'<div class="abzarwp-tooltip__content">' +
				'<strong>\u0627\u06af\u0631 \u0642\u0628\u0644\u0627\u064b \u0627\u06cc\u0646 \u0627\u0641\u0632\u0648\u0646\u0647 \u0631\u0627 \u0628\u0631\u0627\u06cc \u062f\u0627\u0645\u0646\u0647\u200c\u06cc <span class="abzarwp-tooltip__domain-inline">' + $('<span>').text(domain).html() + '</span> \u062e\u0631\u06cc\u062f\u0627\u0631\u06cc \u06a9\u0631\u062f\u0647\u200c\u0627\u06cc\u062f:</strong>' +
				'<br>\u06f1. \u0631\u0648\u06cc <a href="' + domainUrl + '" target="_blank" class="abzarwp-tooltip__link">\u062b\u0628\u062a \u062f\u0627\u0645\u0646\u0647</a> \u06a9\u0644\u06cc\u06a9 \u06a9\u0646\u06cc\u062f.' +
				'<br>\u06f2. \u067e\u0633 \u0627\u0632 \u062b\u0628\u062a \u062f\u0627\u0645\u0646\u0647\u060c \u062f\u0631 \u0647\u0645\u06cc\u0646 \u0635\u0641\u062d\u0647 \u0631\u0648\u06cc <strong>\u0628\u0631\u0631\u0633\u06cc \u0645\u062c\u062f\u062f \u0648\u0636\u0639\u06cc\u062a</strong> \u06a9\u0644\u06cc\u06a9 \u0646\u0645\u0627\u06cc\u06cc\u062f.' +
				'<br><br>' +
				'<strong>\u0627\u06af\u0631 \u0647\u0646\u0648\u0632 \u0627\u06cc\u0646 \u0627\u0641\u0632\u0648\u0646\u0647 \u0631\u0627 \u0628\u0631\u0627\u06cc \u062f\u0627\u0645\u0646\u0647\u200c\u06cc <span class="abzarwp-tooltip__domain-inline">' + $('<span>').text(domain).html() + '</span> \u062e\u0631\u06cc\u062f\u0627\u0631\u06cc \u0646\u06a9\u0631\u062f\u0647\u200c\u0627\u06cc\u062f:</strong>' +
				'<br>\u06f1. \u0631\u0648\u06cc <a href="' + buyUrl + '" target="_blank" class="abzarwp-tooltip__link">\u062e\u0631\u06cc\u062f \u0633\u0631\u06cc\u0639</a> \u06a9\u0644\u06cc\u06a9 \u06a9\u0646\u06cc\u062f.' +
				'<br>\u06f2. \u067e\u0633 \u0627\u0632 \u062a\u06a9\u0645\u06cc\u0644 \u062e\u0631\u06cc\u062f\u060c \u0648\u0627\u0631\u062f \u067e\u0646\u0644 \u0627\u0628\u0632\u0627\u0631 \u0648\u0631\u062f\u067e\u0631\u0633 \u0634\u0648\u06cc\u062f \u0648 \u062f\u0627\u0645\u0646\u0647\u200c\u06cc <span class="abzarwp-tooltip__domain-inline">' + $('<span>').text(domain).html() + '</span> \u0631\u0627 \u062b\u0628\u062a \u0646\u0645\u0627\u06cc\u06cc\u062f.' +
				'<br>\u06f3. \u0631\u0648\u06cc <strong>\u0628\u0631\u0631\u0633\u06cc \u0645\u062c\u062f\u062f \u0648\u0636\u0639\u06cc\u062a</strong> \u06a9\u0644\u06cc\u06a9 \u06a9\u0646\u06cc\u062f.' +
				'</div>' +
				'</div>';
		}
	};

	/* ======================================================================
	   Deactivation Notice Dismiss Handler
	   ====================================================================== */
	$(document).on('click', '.abzarwp-notice--deactivated .notice-dismiss', function () {
		if (typeof abzarwpCleanupNonce !== 'undefined') {
			$.post(ajaxurl, {
				action: 'abzarwp_cleanup_deactivated_list',
				nonce: abzarwpCleanupNonce
			});
		}
	});

	/* ======================================================================
	   Initialize
	   ====================================================================== */
	$(document).ready(function () {
		AbzarWPTooltip.init();
	});

})(jQuery);
