/* ToPersia AI Summarizer - Elementor widget helpers (widget is server-rendered). */
(function () {
	'use strict';

	if (!window.elementor) {
		return;
	}

	elementor.hooks.addAction('panel/open_editor/widget/tpas_ai', function (panel) {
		if (panel && panel.$el && panel.$el.find) {
			panel.$el.find('.elementor-control-mode select').trigger('change');
		}
	});
})();