(function($) {
	var $panel = $('#vc_ui-panel-edit-element');
	var $wrapperPanel;

	$panel.on('vcPanel.shown', function() {
		$wrapperPanel = $(this);

		function initSpacingWrapper($wrapper) {
			if ($wrapper.data('wdCssEditorInitialized')) {
				return;
			}

			var vc = window.vc;
			if (!vc || !vc.CssEditor) {
				return;
			}

			var deviceLinkedState = {};

			function getActiveOnion() {
				return $wrapper.find('.vc_layout-onion.xts-active[data-device]');
			}

			var cssEditor = new vc.CssEditor({ el: $wrapper[0] });

			cssEditor.initUnitSelectors = function() {
				if (!vc.formComponents || !vc.formComponents.unitSelector) {
					return;
				}
				this.$el.find('.vc_layout-onion[data-device]').each(function() {
					vc.formComponents.unitSelector.init($(this), {
						onUnitChange: function() {
							setMainValue($wrapper);
						}
					});
				});
			};

			cssEditor.getLayerUnit = function(layer) {
				var $sel = getActiveOnion().find('.wpb-unit-selector[data-layer="' + layer + '"]');
				return $sel.length ? ($sel.val() || 'px') : 'px';
			};

			cssEditor.syncLinkedInputs = function($input, value) {
				if (this.simplify) {
					var layer = $input.data('attribute');
					if (layer) {
						getActiveOnion().find('[data-attribute="' + layer + '"]').val(value);
					}
				}
			};

			cssEditor.toggleLink = function(e) {
				e.preventDefault();
				var $btn = $(e.currentTarget);
				$btn.toggleClass('linked');
				var isLinked = $btn.hasClass('linked');
				var device = getActiveOnion().data('device');
				deviceLinkedState[device] = isLinked;
				this.simplify = isLinked;

				if (isLinked) {
					$wrapper.addClass('vc_simplified');
					var $activeOnion = getActiveOnion();
					['margin', 'border', 'padding'].forEach(function(layer) {
						var topVal = $activeOnion.find('[data-attribute="' + layer + '"].vc_top').val();
						if (topVal) {
							$activeOnion.find('[data-attribute="' + layer + '"]:not(.vc_top)').val(topVal);
						}
					});
					var trVal = $activeOnion.find('[name="border_radius_top_right"]').val();
					if (trVal) {
						$activeOnion.find('.vc_border-radius-corners input').val(trVal);
					}
				} else {
					$wrapper.removeClass('vc_simplified');
				}

				setMainValue($wrapper);
			};

			cssEditor.render();

			$wrapper.on('input', '.vc_layout-onion[data-device] input', function() {
				setMainValue($wrapper);
			});

			$wrapper.data('wdCssEditorInitialized', true);
			$wrapper.data('deviceLinkedState', deviceLinkedState);
			$wrapper.data('cssEditor', cssEditor);
		}

		$('.vc_wrapper-param-type-css_editor .vc_layout-onion').addClass('xts-active');

		$('.xts-layout-onion-tabs > span').on('click', function() {
			var $this = $(this);
			var device = $this.data('value');
			var $closestWrapper = $this.closest('.wd-responsive-spacing-wrapper');
			var deviceLinkedState = $closestWrapper.data('deviceLinkedState') || {};
			var cssEditor = $closestWrapper.data('cssEditor');

			$this.siblings().removeClass('xts-active');
			$this.addClass('xts-active');

			$closestWrapper.removeClass('vc_simplified');

			if ('desktop' === device) {
				$wrapperPanel.find('.vc_layout-onion').removeClass('xts-active');
				$wrapperPanel.find('.vc_wrapper-param-type-css_editor .vc_layout-onion').addClass('xts-active');
			} else {
				$wrapperPanel.find('.vc_layout-onion').removeClass('xts-active');
				var $onion = $closestWrapper.find('.vc_layout-onion[data-device="' + device + '"]');
				$onion.addClass('xts-active');

				if (deviceLinkedState[device]) {
					$closestWrapper.addClass('vc_simplified');
				}

				$onion.find('.vc_css-editor-link-toggle').toggleClass('linked', !!deviceLinkedState[device]);

				if (cssEditor) {
					cssEditor.simplify = !!deviceLinkedState[device];
				}
			}
		});

		$('.wd-responsive-spacing-wrapper').each(function() {
			var $this = $(this);
			initSpacingWrapper($this);
			setInputsValue($this);
			setMainValue($this);
		});

		function setMainValue($this) {
			var $mainInput = $this.find('.wd-responsive-spacing-value');
			var results = {
				param_type : 'woodmart_responsive_spacing',
				selector_id: $('.woodmart-css-id').val(),
				shortcode  : $panel.attr('data-vc-shortcode'),
				data       : {}
			};

			$this.find('.wd-responsive-spacing').each(function() {
				var $onion = $(this);
				var device = $onion.data('device');

				results.data[device] = {};

				$onion.find('input').each(function(index, elm) {
					var $elm = $(elm);
					var value = $elm.val();
					var name = $elm.data('name');

					if (!value || !name) {
						return;
					}

					var layer = $elm.data('attribute');
					var unit = 'px';

					if (layer && layer !== 'border-radius') {
						var $unitSel = $onion.find('.wpb-unit-selector[data-layer="' + layer + '"]');
						if ($unitSel.length) {
							unit = $unitSel.val() || 'px';
						}
					}

					results.data[device][name] = value + unit;
				});
			});

			if ($.isEmptyObject(results.data)) {
				results = '';
			} else {
				results = window.btoa(JSON.stringify(results));
			}

			$mainInput.val(results).trigger('change');
		}

		function setInputsValue($this) {
			var $mainInput = $this.find('.wd-responsive-spacing-value');
			var mainInputVal = $mainInput.val();

			if (!mainInputVal) {
				return;
			}

			var parseVal = JSON.parse(window.atob(mainInputVal));

			$.each(parseVal.data, function(device, value) {
				if (!value) {
					return;
				}

				$.each(value, function(key, val) {
					var $input = $this.find('[data-device="' + device + '"]').find('[data-name="' + key + '"]');

					if (!$input.length) {
						return;
					}

					var match = String(val).match(/^(-?[\d.]+)(.*)$/);
					if (match) {
						$input.val(match[1]);

						if (match[2]) {
							var layer = $input.data('attribute');
							if (layer && layer !== 'border-radius') {
								var $unitSel = $this.find('[data-device="' + device + '"]').find('.wpb-unit-selector[data-layer="' + layer + '"]');
								$unitSel.val(match[2]);
								if ($unitSel.data('select2')) {
									$unitSel.trigger('change.select2');
								}
							}
						}
					} else {
						$input.val(val);
					}
				});
			});
		}
	});

})(jQuery);