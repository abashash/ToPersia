/* ToPersia AI Summarizer - Gutenberg blocks (server-rendered). */
(function (wp) {
	'use strict';

	if (!wp || !wp.blocks) {
		return;
	}

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var registerBlockType = wp.blocks.registerBlockType;

	var SERVICES = [
		{ label: 'ChatGPT', value: 'chatgpt' },
		{ label: 'Perplexity', value: 'perplexity' },
		{ label: 'Claude', value: 'claude' },
		{ label: 'Grok', value: 'grok' },
		{ label: 'Copilot', value: 'copilot' },
		{ label: 'Gemini', value: 'gemini' },
		{ label: 'DeepSeek', value: 'deepseek' }
	];

	registerBlockType('tpas/buttons', {
		title: __('ToPersia AI buttons', 'topersia-ai-summarizer'),
		description: __('Buttons that open AI assistants with a structured-summary prompt.', 'topersia-ai-summarizer'),
		icon: 'superhero',
		category: 'widgets',
		keywords: ['ToPersia', 'AI', 'summary'],
		attributes: {
			services: { type: 'array', default: [] },
			with_copy: { type: 'boolean', default: true }
		},
		supports: { html: false },
		edit: function (props) {
			var attrs = props.attributes;
			var set = props.setAttributes;

			function toggleService(value) {
				var current = attrs.services || [];
				if (current.indexOf(value) !== -1) {
					set({ services: current.filter(function (s) { return s !== value; }) });
				} else {
					set({ services: current.concat([value]) });
				}
			}

			var controls = el(InspectorControls, {},
				el(PanelBody, { title: __('Assistants', 'topersia-ai-summarizer'), initialOpen: true },
					SERVICES.map(function (s) {
						return el(ToggleControl, {
							key: s.value,
							label: s.label,
							checked: (attrs.services || []).indexOf(s.value) !== -1,
							onChange: toggleService.bind(null, s.value)
						});
					})
				)
			);

			var preview = el('div', { className: 'tpas-block-placeholder' },
				el('p', { style: { fontWeight: '600', margin: '0 0 6px' } }, __('ToPersia AI buttons', 'topersia-ai-summarizer')),
				el('p', { style: { margin: '0', color: '#555' } }, __('Rendered on the front end.', 'topersia-ai-summarizer'))
			);

			return [controls, preview];
		},
		save: function () { return null; }
	});

	registerBlockType('tpas/panel', {
		title: __('ToPersia AI panel', 'topersia-ai-summarizer'),
		description: __('On-page AI brief generated from the current page.', 'topersia-ai-summarizer'),
		icon: 'format-chat',
		category: 'widgets',
		keywords: ['ToPersia', 'AI', 'brief'],
		attributes: {
			types: { type: 'array', default: [] },
			default_type: { type: 'string', default: '' }
		},
		supports: { html: false },
		edit: function (props) {
			var attrs = props.attributes;
			var set = props.setAttributes;

			var controls = el(InspectorControls, {},
				el(PanelBody, { title: __('Output type', 'topersia-ai-summarizer'), initialOpen: true },
					el(SelectControl, {
						label: __('Default type', 'topersia-ai-summarizer'),
						value: attrs.default_type || 'summary',
						options: [
							{ label: __('Structured summary', 'topersia-ai-summarizer'), value: 'summary' },
							{ label: __('Key points', 'topersia-ai-summarizer'), value: 'keypoints' },
							{ label: __('Travel plan', 'topersia-ai-summarizer'), value: 'plan' }
						],
						onChange: function (value) { set({ default_type: value }); }
					})
				)
			);

			var preview = el('div', { className: 'tpas-block-placeholder' },
				el('p', { style: { fontWeight: '600', margin: '0 0 6px' } }, __('ToPersia AI panel', 'topersia-ai-summarizer')),
				el('p', { style: { margin: '0', color: '#555' } }, __('Rendered on the front end.', 'topersia-ai-summarizer'))
			);

			return [controls, preview];
		},
		save: function () { return null; }
	});
})(window.wp);