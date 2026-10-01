// Follow the UMD template https://github.com/umdjs/umd/blob/master/templates/returnExportsGlobal.js
(function (root, factory) {
        'use strict'
        if (typeof define === 'function' && define.amd) {

            // AMD. Make globaly available as well
            define(['jquery', 'ClipboardJS'], function (jquery, ClipboardJS) {
                return (root.AbzarwpApp = factory(jquery, ClipboardJS))
            })

        } else if (typeof module === 'object' && module.exports) {
            // Node / Browserify
            //isomorphic issue

            var jQuery = (typeof window != 'undefined') ? window.jQuery : undefined
            if (!jQuery) {
                jQuery = require('jquery')
                if (!jQuery.fn) jQuery.fn = {}
            }

            var ClipboardJS = (typeof window != 'undefined')
                ? window.ClipboardJS
                : undefined
            if (!ClipboardJS) {
                ClipboardJS = require('ClipboardJS')
            }

            module.exports = factory(jQuery, ClipboardJS)
        } else {
            // Browser globals
            root.AbzarwpApp = factory(root.jQuery, root.ClipboardJS)
        }
    }(this,

        /**
         *
         * @param {JQueryStatic} $
         * @param ClipboardJS
         * @return {*|{}}
         */

        function ($, ClipboardJS) {

            'use strict'

            /**
             * Core
             */
            window.AbzarwpApp = {}

            /**
             * Ensure the global `wp` object exists.
             *
             * @namespace wp
             */
            window.wp = window.wp || {}

            /**
             * Private Vars
             */

            function i18n_mock(x) {
                return x
            }

            var __ = wp.i18n.__ || i18n_mock,
                _x = wp.i18n._x || i18n_mock,
                sprintf = wp.i18n.sprintf || i18n_mock

            var refreshDashboardAjax = null,
                confirmsCache = {}

            /**
             * Public Vars
             */

            AbzarwpApp.config = window.AbzarwpAppJsVars || {}

            AbzarwpApp.$document = $(document)
            AbzarwpApp.$window = $(window)

            AbzarwpApp.__ = __
            AbzarwpApp._x = _x
            AbzarwpApp.sprintf = sprintf

            AbzarwpApp.ajax = wp.ajax
            AbzarwpApp.ajaxUrlPattern = 'abzarwp-app-'
            AbzarwpApp.ajaxNonce = AbzarwpApp.config.ajaxNonce
            AbzarwpApp.ajaxActions = AbzarwpApp.config.ajaxActions
            AbzarwpApp.ajaxFailMessage = __('An error has occurred. Please reload the page and try again.', 'abzarwp-app')

            AbzarwpApp.actions = {}

            /**
             * Init Core Function
             */

            if (typeof window.addLoadEvent === 'function') {
                window.addLoadEvent(function () {
                    AbzarwpApp.init()
                })
            } else {
                $(function () {
                    AbzarwpApp.init()
                })
            }

            /**
             * Register Methods
             */

            AbzarwpApp.init = function () {

                /**
                 * Init Resizable Card
                 * ex: use in debug card
                 */
                if (typeof $.fn.resizable !== 'undefined') {
                    $('.card.card-resizable-n').resizable({
                        handles: 'n',
                        containment: "document",
                        minHeight: 150,
                    })
                }

                /**
                 * We have some js errors ...
                 */
                if (typeof wp.updates === 'undefined') {

                    AbzarwpApp.hidePageLoading()

                    return alert('برخی از فایل های مورد نیاز به دلیل خطای php لود نشده است. در این حالت برخی از امکانات افزونه کار نخواهد کرد. برای مشاهده و رفع خطا ها حالت اشکال زدایی در وردپرس را فعال کنید')
                }

                /**
                 * Init Scripts
                 */

                AbzarwpApp.initBackToTopBtn()
                AbzarwpApp.initAjaxDismissNotice()

                AbzarwpApp.initAjax()
                AbzarwpApp.initSelect2()
                AbzarwpApp.initClipboardJS()
                AbzarwpApp.initBootstrapTooltip()

                AbzarwpApp.initTabs()
                AbzarwpApp.initToggleInputsVisibility()
                AbzarwpApp.initToggleCardsViewMode()
                AbzarwpApp.initSearchCards()

                AbzarwpApp.initTablesRowFilter()
                AbzarwpApp.initTablesRowActions()
                AbzarwpApp.initTablesBulkActions()

                AbzarwpApp.initAddProductsModal()
                AbzarwpApp.initLoginWithoutPasswordModal()

                AbzarwpApp.initRequestFilesystemCredentialsModal()

                AbzarwpApp.registerEvents()

                /**
                 * Hide Page Loading
                 */
                AbzarwpApp.hidePageLoading()

                /**
                 * App init complete
                 */
                AbzarwpApp.$document.trigger('abzarwp-app-init')

            }

            /**
             * Get App Element
             */
            AbzarwpApp.getApp = function () {
                return $('#abzarwp-app')
            }

            /**
             * Get Active Tab
             */

            AbzarwpApp.getActiveTab = function () {
                return $('.tab-pane.active.show')
            }

            AbzarwpApp.getActiveTabID = function () {

                if (!AbzarwpApp.getActiveTab().length) {
                    return false
                }

                return AbzarwpApp.getActiveTab().attr('id').substr(16)
            }

            AbzarwpApp.setActiveTab = function (tabID) {

                if (!tabID) {
                    return
                }

                // Insert URL Param
                if (history.pushState && typeof URLSearchParams !== 'undefined') {

                    var searchParams = new URLSearchParams(window.location.search)
                    searchParams.set('tab', tabID)

                    var newurl = window.location.protocol + '//' + window.location.host +
                        window.location.pathname + '?' + searchParams.toString()
                    window.history.pushState({path: newurl}, '', newurl)

                }

            }

            AbzarwpApp.getActiveTabCards = function () {
                return AbzarwpApp.getActiveTab().find('.abzarwp-app-cards')
            }

            AbzarwpApp.getAllTabsCards = function () {
                return $('.abzarwp-app-cards')
            }

            /**
             * Page Loading
             */

            AbzarwpApp.getPageLoading = function () {
                return $('#abzarwp-app-loading')
            }

            AbzarwpApp.showPageLoading = function () {
                // $('body').removeClass('abzarwp-app-loading-done')
                AbzarwpApp.getApp().addClass('overflow-hidden vh-100')
                AbzarwpApp.getPageLoading().removeClass('d-none').fadeIn()
            }

            AbzarwpApp.hidePageLoading = function () {
                AbzarwpApp.getPageLoading().fadeOut(function () {
                    $(this).addClass('d-none')

                    AbzarwpApp.getApp().removeClass('overflow-hidden vh-100')
                    // $('body').addClass('abzarwp-app-loading-done')
                })
            }

            /**
             * Loading Button
             * @param {jQuery} btn
             */
            AbzarwpApp.btnLoading = function (btn) {

                var spinner = btn.siblings('.btn-loading')

                if (spinner.length <= 0) {
                    spinner = $(
                        '<button class="btn btn-loading" type="button" disabled></button>')

                    spinner.hide()

                    btn.wrap('<div class="d-flex gap-2"></div>').after(spinner)

                }

                var classList = btn.attr('class') || '',
                    is_outline = classList.indexOf('btn-outline-') > -1,
                    class_prefix = is_outline ? '-outline' : ''

                function flush() {

                    var removeClass = 'd-none btn-icon text-black-50'

                    $.each([
                        'btn',
                        'btn-outline',
                        'border',
                        'text',
                    ], function (index, key) {
                        removeClass += ` ${key}-primary ${key}-secondary ${key}-success ${key}-danger ${key}-warning ${key}-info ${key}-light ${key}-dark`
                    })

                    spinner.removeClass(removeClass)

                    if (!btn.hasClass('btn')) {
                        spinner.addClass('d-none')
                    } else {
                        spinner.show()
                    }

                    // spinner.height(btn[0].)

                    btn.prop('disabled', false).removeClass('disabled')
                }

                return {
                    show: function () {
                        flush()
                        btn.prop('disabled', true).addClass('disabled')
                        spinner.addClass(btn.attr('class'))
                        spinner.html(
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>')
                    },

                    hide: function () {
                        flush()
                        spinner.hide()
                    },

                    success: function (msg) {
                        flush()
                        spinner.addClass(`btn${class_prefix}-success border-success btn-icon`)
                        spinner.html('<span class="dashicons dashicons-yes"></span>')

                        if (is_outline) {
                            spinner.addClass('text-success')
                        }

                        if (msg) {
                            spinner.append(msg)
                        }

                    },

                    error: function (msg) {
                        flush()
                        spinner.addClass(`btn${class_prefix}-danger border-danger btn-icon`)
                        spinner.html('<span class="dashicons dashicons-no"></span>')

                        if (is_outline) {
                            spinner.addClass('text-danger')
                        }

                        if (msg) {
                            spinner.append(msg)
                        }

                    },

                    destroy: function () {
                        flush()
                        spinner.remove()
                    },
                }

            }

            /**
             * Ajax Config
             */
            AbzarwpApp.initAjax = function () {

                /**
                 * Set Ajax Nonce
                 */
                $.ajaxPrefilter(function (options, originalOptions, jqXHR) {

                    if (
                        typeof options.data !== 'undefined' &&
                        typeof originalOptions.data !== 'undefined' &&
                        typeof originalOptions.data.action !== 'undefined' &&
                        originalOptions.data.action.startsWith(AbzarwpApp.ajaxUrlPattern)
                    ) {

                        if (_.isObject(originalOptions.data)) {

                            options.data = originalOptions.data

                            options.data['_wpnonce'] = AbzarwpApp.ajaxNonce
                            options.data['_ajax_nonce'] = AbzarwpApp.ajaxNonce

                            if (options.processData) {
                                options.data = $.param(options.data)
                            }

                        }

                    } else if (options.data && options.data.indexOf(AbzarwpApp.ajaxUrlPattern) !== -1) {
                        options.data += '&_wpnonce=' + AbzarwpApp.ajaxNonce
                        options.timeout = 0
                    }

                    return options
                })

                /**
                 * Pulls available jobs from the queue and runs them.
                 *
                 * @see wp.updates.queueChecker
                 */
                var queueChecker = wp.updates.queueChecker
                wp.updates.queueChecker = function () {
                    var job

                    if (wp.updates.ajaxLocked || !wp.updates.queue.length) {
                        return
                    }

                    job = wp.updates.queue.shift()

                    wp.updates.currentAjaxJob = job

                    wp.updates.ajax(job.action, job.data)
                }


                /**
                 * Handles credentials errors if it could not connect to the filesystem.
                 *
                 * @see wp.updates.maybeHandleCredentialError
                 *
                 * @param {Object} response              Response from the server.
                 * @param {string} response.errorCode    Error code for the error that occurred.
                 * @param {string} response.errorMessage The error that occurred.
                 * @return {boolean} Whether there is an error that needs to be handled or not.
                 */
                var maybeHandleCredentialError = wp.updates.maybeHandleCredentialError
                wp.updates.maybeHandleCredentialError = function (response) {

                    if (!AbzarwpApp.ajax.isValidResponse(response)) {
                        return false
                    }

                    // Handles credentials errors if it could not connect to the filesystem.
                    if (
                        wp.updates.shouldRequestFilesystemCredentials &&
                        typeof response.errorCode !== 'undefined' &&
                        'unable_to_connect_to_filesystem' === response.errorCode
                    ) {

                        if (
                            typeof wp.updates.currentAjaxJob !== 'undefined' &&
                            !_.isObject(wp.updates.currentAjaxJob)
                        ) {
                            wp.updates.queue.unshift(wp.updates.currentAjaxJob)
                        }

                        wp.updates.filesystemCredentials.available = false
                        wp.updates.showErrorInCredentialsForm(response.errorMessage)
                        wp.updates.requestFilesystemCredentials()

                        return true
                    }

                    return false
                }


                /**
                 * Validates an Ajax response to ensure it's a proper object.
                 *
                 * If the response deems to be invalid, an admin notice is being displayed.
                 *
                 * @param {(Object|string)} response              Response from the server.
                 * @param {function=}       response.always       Optional. Callback for when the Deferred is resolved or rejected.
                 * @param {string=}         response.statusText   Optional. Status message corresponding to the status code.
                 * @param {string=}         response.responseText Optional. Request response as text.
                 * @param {string}          action                Type of action the response is referring to. Can be 'delete',
                 *                                                'update' or 'install'.
                 *
                 * @see wp.updates.isValidResponse
                 */
                var isValidResponse = wp.updates.isValidResponse
                AbzarwpApp.ajax.isValidResponse = function (response) {

                    // Make sure the response is a valid data object and not a Promise object.
                    return !(_.isObject(response) && _.isFunction(response.always))

                }


                /**
                 * Get Ajax Response
                 */
                AbzarwpApp.ajax.getResponseMessage = function (response, isSuccess, $deep = 0) {

                    if (_.indexOf(['cancel'], response) !== -1) {
                        return null
                    }

                    var message = null

                    if ((_.isString(response) || _.isNumber(response)) && _.indexOf(['0', '-1', 0, -1], response) !== -1) {
                        message = AbzarwpApp.ajaxFailMessage
                    } else if (_.isString(response)) {
                        message = response
                    } else if ('undefined' !== typeof response.data && $deep < 1) {
                        return AbzarwpApp.ajax.getResponseMessage(response.data, isSuccess, $deep + 1)
                    } else if ('undefined' !== typeof response.errorMessage) {
                        message = response.errorMessage
                    } else if ('undefined' !== typeof response.message) {
                        message = response.message
                    } else if ('undefined' !== typeof response.readyState && 0 === response.readyState) {
                        message = __('Connection lost or the server is busy. Please try again later.')
                    } else if (_.isString(response.responseText) && '' !== response.responseText) {
                        message = response.responseText
                    } else if (_.isString(response.statusText)) {
                        message = response.statusText
                    } else if (!_.isEmpty(response)) {
                        message = response
                    }

                    if (_.isObject(message)) {

                        // for debug only
                        // message = JSON.stringify(response)
                        // message = `<code class="rounded-1">${message}</code>`

                        message = null

                    }

                    if (_.isEmpty(message)) {

                        if (isSuccess) {
                            message = __('Success!', 'abzarwp-app')
                        } else {
                            message = AbzarwpApp.ajaxFailMessage
                        }

                    }

                    return message
                }

                /**
                 * Adds or updates an admin notice.
                 *
                 * @param {Object}  data
                 * @param {*=}      data.selector      Optional. Selector of an element to be replaced with the admin notice.
                 * @param {string=} data.id            Optional. Unique id that will be used as the notice's id attribute.
                 * @param {string=} data.className     Optional. Class names that will be used in the admin notice.
                 * @param {string=} data.message       Optional. The message displayed in the notice.
                 * @param {number=} data.successes     Optional. The amount of successful operations.
                 * @param {number=} data.errors        Optional. The amount of failed operations.
                 * @param {Array=}  data.errorMessages Optional. Error messages of failed operations.
                 * @param {jQuery}  $form              Optional. notice container.
                 *
                 */
                var addAdminNotice = wp.updates.addAdminNotice
                wp.updates.addAdminNotice = function (data, $form) {

                    var $notice = $(data.selector),
                        $headerEnd = $('.wp-header-end'),
                        $adminNotice

                    delete data.selector
                    $adminNotice = wp.updates.adminNotice(data)

                    // Check if this admin notice already exists.
                    if (!$notice.length) {
                        $notice = $('#' + data.id)
                    }

                    if (!$notice.length) {
                        $adminNotice = $($adminNotice).wrap('<div class="wp-notices-wrap"></div>').parent()
                    }

                    $adminNotice.slideUp(function () {

                        if ($notice.length) {
                            $notice.replaceWith($adminNotice)
                        } else if ($form && $form.length) {
                            $form.before($adminNotice)
                        } else if ($headerEnd.length) {
                            $headerEnd.after($adminNotice)
                        } else {
                            if ('customize' === pagenow) {
                                $('.customize-themes-notifications').append($adminNotice)
                            } else {
                                $('.wrap').find('> h1').after($adminNotice)
                            }
                        }

                        $adminNotice.delay(10).slideDown(function () {

                            AbzarwpApp.$document.trigger('wp-updates-notice-added')

                            $('html, body').animate({scrollTop: 0})

                        })

                    })

                }

            }

            /**
             * Select2
             */
            AbzarwpApp.initSelect2 = function () {

                if (typeof $.fn.select2 === 'undefined') {
                    return
                }

                // Select2 Default Options
                // @see https://select2.org/configuration/defaults
                $.fn.select2.defaults.set('width', '100%')
                // $.fn.select2.defaults.set("close-on-select", false)
                // $.fn.select2.defaults.set("ajax--cache", true)
                // $.fn.select2.defaults.set("ajax--data-type", "json")

                AbzarwpApp.$document.on('abzarwp-init-select2', function () {

                    $('.abzarwp-app-select2').each(function () {
                        $(this).select2({
                            theme: 'bootstrap-5',
                            placeholder: __('Select', 'abzarwp-app'),
                            dropdownParent: $(this).parent(),
                        })
                    })

                }).trigger('abzarwp-init-select2')

                // init select2 after ajax
                AbzarwpApp.$document.ajaxComplete(function (event, xhr, args) {

                    var data = args.data || ''

                    if (data.indexOf('action=abzarwp-app-') !== -1) {
                        AbzarwpApp.$document.trigger('abzarwp-init-select2')
                    }

                })

                // fix select2 modal scroll bug
                $(document).on('select2:close', '.abzarwp-app-select2', function (e) {
                    var evt = "scroll.select2"
                    $(e.target).parents().off(evt)
                    $(window).off(evt)
                })

            }

            /**
             * Bootstrap Tooltip
             */
            AbzarwpApp.initBootstrapTooltip = function () {

                if (!bootstrap || typeof bootstrap.Tooltip === 'undefined') {
                    return
                }

                new bootstrap.Tooltip(document.body, {
                    html: true,
                    selector: '[data-bs-toggle="tooltip"]',
                })

            }

            /**
             * ClipboardJS
             */
            AbzarwpApp.initClipboardJS = function () {

                if (!ClipboardJS) {
                    return
                }

                var clipboard = new ClipboardJS(
                    '.abzarwp-copy-button:not(.disabled):not(disabled)')

                clipboard.on('success', function (e) {

                    var triggerElement = $(e.trigger),
                        successElement = $('<span class="text-success"></span>').text(__('Copied!', 'abzarwp-app'))

                    // Clear the selection and move focus back to the trigger.
                    e.clearSelection()

                    // Handle ClipboardJS focus bug, see https://github.com/zenorocha/clipboard.js/issues/680
                    triggerElement.trigger('focus')

                    // Show success visual feedback.
                    triggerElement.after(successElement)
                    triggerElement.hide()

                    // Hide success visual feedback after 3 seconds since last success.
                    setTimeout(function () {
                        successElement.remove()
                        triggerElement.show()
                    }, 3000)

                })

            }

            /**
             * Tabs
             */
            AbzarwpApp.initTabs = function () {

                AbzarwpApp.$window.on('shown.bs.tab', function (e) {

                    var tab = $(e.target),
                        tabID = tab.attr('data-bs-target'),
                        currentTab = $('[data-bs-target="' + tabID + '"]:not(.active)')

                    currentTab
                        .addClass('active')
                        .siblings()
                        .removeClass('active')

                    $('.active[data-bs-toggle="tab"]:not([data-bs-target="' + tabID + '"])')
                        .removeClass('active')

                    AbzarwpApp.setActiveTab(tabID.substr(17))

                })

                AbzarwpApp.$window.on(
                    'hide.bs.modal',
                    function () {
                        AbzarwpApp.setActiveTab(AbzarwpApp.getActiveTabID())
                    },
                )

            }

            /**
             * Toggle Inputs Visibility after change value
             */
            AbzarwpApp.initToggleInputsVisibility = function () {

                var toggleNodeNameSelect = 'select[data-toggle-input]',
                    toggleNodeNameCheckbox = 'input[type="checkbox"][data-toggle-input]'

                // Select
                AbzarwpApp.$document.on(
                    'change',
                    toggleNodeNameSelect,
                    function (e) {

                        var optionSelected = $('option:selected', this),
                            targetID = optionSelected.attr('data-toggle-target')

                        if (!targetID) {
                            return
                        }

                        var target = $(targetID)

                        if (target.length <= 0) {
                            return
                        }

                        target.siblings().hide()
                        target.removeClass('d-none').show()

                    },
                )

                // Checkbox
                AbzarwpApp.$document.on(
                    'change',
                    toggleNodeNameCheckbox,
                    function (e) {

                        var checked = $(this).prop('checked'),
                            targetID = $(this).attr('data-toggle-target')

                        if (!targetID) {
                            return
                        }

                        var target = $(targetID)

                        if (target.length <= 0) {
                            return
                        }

                        if (checked) {
                            target.hide().removeClass('d-none').slideDown()
                        } else {
                            target.slideUp()
                        }

                    },
                )

                $(toggleNodeNameCheckbox).trigger('change')

            }

            /**
             * Toggle Cards View Mode
             */
            AbzarwpApp.initToggleCardsViewMode = function () {

                AbzarwpApp.$document.on(
                    'change',
                    'input[type="radio"][name="abzarwp-app-view-mode"]',
                    function () {

                        var row,
                            classes,
                            mode = $(this).val()

                        // row = getActiveTabCards()
                        row = AbzarwpApp.getAllTabsCards()

                        if (!row.length) {
                            return
                        }

                        classes = row.data('row-cols-classes')

                        if (!classes) {
                            classes = (row.attr('class').match(/(^|\s)row-cols-\S+/g) ||
                                []).join(
                                ' ')
                            classes = $.trim(classes)
                            row.data('row-cols-classes', classes)
                        }

                        if (mode === 'grid') {
                            row.addClass(classes)
                        } else {
                            row.removeClass(classes)
                            row.addClass('row-cols-1')
                        }

                        row.attr('data-view-mode', mode)

                    },
                )

            }

            /**
             * Search Cards
             * use 'search-here' and 'search-card' class to search in any element like tables rows.
             * use 'search-exclude' to always show an item in search result.
             * Ex: <tr class="search-card"><th class="search-here">text</th><td>other text</td></tr>
             */
            AbzarwpApp.initSearchCards = function () {

                var minimuminputlength = 3,
                    searchTimeout

                AbzarwpApp.$document.on('keydown', function (e) {
                    if (e.ctrlKey && e.key === '/') {
                        e.preventDefault()

                        $('.abzarwp-app-search').trigger('focus')
                    }
                })

                // clear search after switch tabs
                AbzarwpApp.$window.on('shown.bs.tab', function (e) {
                    $('.abzarwp-app-search').val('')
                    doSearch('')
                })

                var getFuzzyMatcher = function (term) {
                    var pattern = term.split(' ')

                    return function (string) {

                        var result = pattern.filter(function (query) {
                            return string.search(query) !== -1
                        })

                        return result.length === pattern.length
                    }
                }

                var doSearch = function (term) {

                    var matcher,
                        $titles = AbzarwpApp.getActiveTab().find('.search-here'),
                        highlightOptions = {className: 'text-highlight'}

                    if ($titles.length === 0) {
                        return
                    }

                    term = $.trim(term).toLowerCase()

                    matcher = getFuzzyMatcher(term)

                    $titles.each(function () {

                        var $title = $(this),
                            searchData = $.trim($title.text()).toLowerCase()

                        var $card = $title.closest('.search-card'),
                            $cardChilds = $card.nextUntil('.search-card', '.search-exclude'),
                            $cardNoItem = $card.siblings('.no-items')

                        if ($cardChilds.length > 0) {
                            $card = $card.add($cardChilds)
                        }

                        var $debug_tab = $card.find('.health-check-accordion-trigger'),
                            $debug_items = $card.find('.health-check-table > tbody > tr'),
                            is_debug = $debug_tab.length

                        $card.show().addClass('search-result')
                        $title.unhighlight(highlightOptions)
                        $card.find('.disable-input').prop('disabled', false).removeClass('disable-input')

                        if (is_debug) {

                            // close debug accordion
                            if ($debug_tab.is('[aria-expanded="true"]'))
                                $debug_tab.trigger('click')

                            if ($debug_items.length) {
                                $debug_items.show()
                            }

                        }

                        // hide "no item found" in table
                        $cardNoItem.hide()

                        $title.highlight(term.split(' '), highlightOptions)

                        if (term.length >= minimuminputlength) {

                            if (false === matcher(searchData)) {

                                $card.hide().removeClass('search-result')

                                // disable checkbox only for products table
                                // $card.find(':input:not(:disabled)').prop('disabled', true).addClass('disable-input')
                                $card.find('.check-column input[type="checkbox"]:input:not(:disabled)')
                                    .prop('disabled', true)
                                    .addClass('disable-input')

                                // show "no item found" in table
                                if ($card.siblings('.search-result').length <= 0) {
                                    $cardNoItem.removeClass('d-none').show()
                                }

                            } else {

                                if (is_debug) {

                                    // open debug accordion
                                    if ($debug_tab.is('[aria-expanded="false"]'))
                                        $debug_tab.trigger('click')

                                    if ($debug_items.length) {

                                        $debug_items.each(function () {

                                            if (!$(this).find('.text-highlight').length) {
                                                // $(this).hide()
                                            }

                                        })

                                    }

                                }

                            }
                        }

                    })

                }

                // do search when user start typing
                AbzarwpApp.$document.on(
                    'input',
                    '.abzarwp-app-search',
                    function () {

                        var term = $(this).val()

                        if (searchTimeout) {
                            clearTimeout(searchTimeout)
                        }

                        searchTimeout = setTimeout(function () {
                            doSearch(term)
                        }, 500)

                    },
                )

            }

            /**
             * Table: Filter Rows
             */
            AbzarwpApp.initTablesRowFilter = function () {

                AbzarwpApp.$document.on(
                    'change',
                    'select[data-filter-rows]',
                    function () {

                        var $this = $(this),
                            status = $this.val(),
                            $form = $this.closest('form'),
                            $table = $form.find('table'),
                            $rows = $table.find('tbody > tr'),
                            $items_count = $form.find('.tablenav-pages'),
                            $noItem = $table.find('.no-items')

                        $rows.hide()
                        $items_count.hide()
                        $noItem.show().removeClass('d-none')

                        // $rows = $rows.filter(function () {
                        //
                        //     var row_status = $(this).attr('data-status')
                        //
                        //     return _.includes(row_status.split('|'), status)
                        //
                        // })

                        if (status !== '-1') {
                            $rows = $rows.filter(
                                '[data-status*="|' + status + '|"],[data-status*="|' + status +
                                '|"] + .search-exclude')
                            $items_count = $items_count.filter(
                                '[data-status="' + status + '"]')
                        } else {
                            $items_count = $items_count.filter(':not([data-status])')
                        }

                        $rows.show()
                        $items_count.show().removeClass('d-none')

                        if ($rows.length > 0) {
                            $noItem.hide()
                        }

                    },
                )

            }

            /**
             * Table: Row Actions
             */
            AbzarwpApp.initTablesRowActions = function () {

                AbzarwpApp.$document.on(
                    'click',
                    '[data-row-action]:not(.force-click)',
                    function (e) {
                        e.preventDefault()
                        e.stopPropagation()

                        var $button = $(this),
                            $row = $button.closest('tr'),
                            action = $button.attr('data-row-action'),
                            tableID = $button.closest('form').find('[name="table_id"]').val()

                        if ($row.hasClass('row-notice')) {
                            $row = $row.prev('tr[data-id]')
                        }

                        if (false === AbzarwpApp.doTableAction($row, tableID, action)) {

                            /**
                             * Enable Handle Action Confirm Alert With Browser
                             */
                            if ($button.is('[data-confirm]')) {
                                $button.attr('onclick', $button.attr('data-confirm'))
                                $button.removeAttr('data-confirm')
                            }

                            $button.addClass('force-click')
                            $button.get(0).click()

                        }

                    },
                )

                /**
                 * Enable Handle Action Confirm Alert With JS
                 */
                AbzarwpApp.$document.on(
                    'abzarwp-app-init',
                    function () {

                        $('[data-row-action][onclick]:not(.force-click)').each(function () {
                            $(this)
                                .attr(
                                    'data-confirm',
                                    $(this).attr('onclick'),
                                )
                                .removeAttr('onclick')
                        })

                    },
                )

            }

            /**
             * Table: Bulk Actions
             */
            AbzarwpApp.initTablesBulkActions = function () {

                AbzarwpApp.$document.on('input', '.abzarwp-app-table-form select[name^="action"]', function () {

                    var $form = $(this).closest('.abzarwp-app-table-form')

                    if ($form.length) {
                        $form.find('select[name^="action"]').val($(this).val()).trigger('change')
                    }

                })

                AbzarwpApp.$document.on(
                    'submit',
                    '.abzarwp-app-table-form:not(.force-submit)',
                    function (e) {
                        e.preventDefault()

                        var $form = $(this),
                            tableID = $form.find('[name="table_id"]').val(),
                            action = $form.find('[name="action"]').val(),
                            action2 = $form.find('[name="action2"]').val(),
                            $notices = $form.siblings('.wp-notices-wrap')

                        if (!action || action == '-1') {
                            action = action2
                        }

                        if (!action || action == '-1') {
                            return false
                        }

                        if ($notices.length) {

                            $notices.slideUp(function () {
                                $(this).remove()
                                $form.trigger('submit')
                            })

                            return false
                        }

                        var checkbox_selector = '[name="ids[]"]:checked:not(:disabled)',
                            $selected = $form.find(checkbox_selector),
                            $rows = $selected.closest('tr'),
                            btnLoading = AbzarwpApp.btnLoading($form.find('[type="submit"]'))

                        /**
                         * Validate Items
                         */

                        var oldRowsCount = $rows.length,
                            newRowsCount = $rows.length,
                            RowsFilterSelector = []

                        if ($rows.length) {

                            var mapActionsValidation = AbzarwpApp.config.tableBulkActionsValidation[tableID] || null,
                                appSlug = AbzarwpApp.config.appSlug || null,
                                appSlugValidActions = AbzarwpApp.config.appSlugValidActions || null

                            if (mapActionsValidation && typeof mapActionsValidation[action] !== 'undefined') {

                                var validStatus = mapActionsValidation[action]

                                if (!_.isArray(validStatus)) {
                                    validStatus = validStatus.split() // convert string to array !
                                }

                                _.each(validStatus, function (status) {
                                    RowsFilterSelector.push('[data-status*="|' + status + '|"]')
                                })

                            }

                            if (appSlug && appSlugValidActions && $rows.filter('[data-slug="' + appSlug + '"]').length) {

                                if (!_.includes(appSlugValidActions, action)) {
                                    RowsFilterSelector = ['[data-slug!="' + appSlug + '"]']
                                }

                            }

                        }

                        if (!_.isEmpty(RowsFilterSelector)) {

                            if (_.isArray(RowsFilterSelector)) {
                                RowsFilterSelector = RowsFilterSelector.join(',')
                            }

                            $rows = $rows.filter(RowsFilterSelector)
                            newRowsCount = $rows.length

                            if (oldRowsCount !== newRowsCount) {

                                $form.find(`tr:not(${RowsFilterSelector}) ${checkbox_selector}`)
                                    .trigger('click')

                                if (newRowsCount) {
                                    $form.trigger('submit')
                                }

                                return false
                            }

                        }


                        /**
                         * Bail if there were no items selected.
                         */

                        if ($rows.length <= 0) {

                            wp.updates.addAdminNotice({
                                id: 'no-items-selected',
                                className: 'notice-error is-dismissible',
                                message: __('Please select at least one item to perform this action on.'),
                            }, $form)

                            return
                        }

                        /**
                         * Clear Confirm Cache
                         */

                        AbzarwpApp.clearConfirmCache(action)

                        /**
                         * Scroll to first row
                         */

                        var scrollTo = $rows.first().prev('tr'),
                            scrollTopOffset = 0

                        if (!scrollTo.length) {
                            scrollTo = $rows.first()
                            scrollTopOffset = -50
                        }

                        if (scrollTo.length) {
                            $('html, body').stop().animate({scrollTop: scrollTo.offset().top + scrollTopOffset}, 'fast')
                        }

                        /**
                         * Loop Selected
                         */

                        $rows.each(function () {

                            AbzarwpApp.doTableAction($(this), tableID, action)

                        })

                    },
                )

            }

            AbzarwpApp.defaultTableBulkActionHandler = function () {

            }

            /**
             *
             * @param {jQuery} $rowEl
             * @param {String} tableID
             * @param {String}action
             * @return {boolean}
             */
            AbzarwpApp.doTableAction = function ($rowEl, tableID, action) {

                /**
                 * Find Action Handler
                 */

                if (
                    !tableID ||
                    !action ||
                    typeof AbzarwpApp.actions[tableID] === 'undefined' ||
                    typeof AbzarwpApp.actions[tableID][action] !== 'function'
                ) {
                    return false
                }

                var Handler = AbzarwpApp.actions[tableID][action]

                var $row = function () {
                        return $rowEl
                    },

                    $form = function () {
                        return $row().closest('form')
                    },

                    rowID = function () {
                        return $row().attr('data-id')
                    },

                    $rowNotice = function () {
                        return $row().next('.row-notice')
                    },

                    $rowNoticeList = function () {
                        return $rowNotice().find('.notice-list')
                    },

                    $rowCheckbox = function () {
                        return $row().find('input[name="ids[]"]')
                    },

                    $rowSpinner = function () {
                        return $rowCheckbox().siblings('.spinner-border')
                    },

                    $rowActions = function () {
                        return $row().find('.column-primary .row-actions')
                    },

                    $actionButton = function () {
                        return $rowActions().find('[data-row-action="' + action + '"]')
                    },

                    maybeUpdateTableHTML = function (response) {

                        if (_.isObject(response) &&
                            typeof response !== 'undefined'
                        ) {

                            var inputValues = []

                            // update table row
                            if (typeof response.abzarwp_table_row !== 'undefined') {

                                var $oldRow = $row()

                                $rowNotice().remove()

                                $rowEl = $(response.abzarwp_table_row)

                                $oldRow.replaceWith($row())

                                AbzarwpApp.$document.trigger('abzarwp-app-init')

                            }

                            // update table nav
                            if (typeof response.abzarwp_table_nav !== 'undefined') {

                                _.forEach(['top', 'bottom'], function (which) {

                                    if (typeof response.abzarwp_table_nav[which] === 'undefined') {
                                        return
                                    }

                                    var $oldTableNav = $form().find('.tablenav.' + which),
                                        $newTableNav = $(response.abzarwp_table_nav[which]),
                                        oldInputValues = $oldTableNav.find(':input').serializeArray()

                                    inputValues.push.apply(inputValues, oldInputValues)

                                    $oldTableNav.replaceWith($newTableNav)

                                })

                            }

                            // update table form
                            if (typeof response.abzarwp_table_form !== 'undefined') {

                                var $oldTableForm = $form(),
                                    $newTableForm = $(response.abzarwp_table_form),
                                    oldInputValues = $oldTableForm.find('.tablenav').find(':input').serializeArray()

                                inputValues.push.apply(inputValues, oldInputValues)

                                $rowEl = $newTableForm.find('tr[data-id="' + rowID() + '"]')

                                $oldTableForm.replaceWith($newTableForm)

                            }

                            // set checkbox prev status
                            $rowCheckbox().prop('checked', isChecked)

                            // fill inputs
                            if (!_.isEmpty(inputValues)) {
                                _.forEach(inputValues, function (input) {

                                    $form().find(`[name="${input.name}"]`).val(input.value).trigger('change')

                                })
                            }

                        }
                    }

                var noticeID = `${action}-${$rowCheckbox().val()}-`

                /**
                 * Save Checkbox Status
                 */
                var isChecked = $rowCheckbox().is(':checked')

                /**
                 * Callback
                 */

                var callback = {

                    before: function () {

                        /**
                         * Before Callback
                         */

                        var promise = $.Deferred()

                        // Confirm Do Action
                        // only for single action . not for bulk actions.
                        if (
                            $actionButton().is(':focus') &&
                            $actionButton().is('[data-confirm]')
                        ) {

                            try {

                                var confirm = new Function($actionButton().attr('data-confirm'))

                                if (!confirm()) {
                                    promise.reject()
                                    return promise
                                }

                            } catch (error) {
                                alert(error)
                                promise.reject()
                                return promise
                            }

                        }

                        // Hide Prev Action Results
                        var notices = $rowNoticeList().find('.row-notice-actions-result')

                        if (notices.length) {
                            notices.slideUp(function () {
                                $(this).remove()
                                promise.resolve()
                            })
                        } else {
                            promise.resolve()
                        }

                        // Disable Form
                        AbzarwpApp.disableForm($row())

                        // Disable Add Product Button
                        AbzarwpApp.toggleAddProductsBtn(false)

                        $rowSpinner().show().removeClass('d-none')

                        $rowCheckbox().hide()
                        $rowActions().hide()

                        return promise
                    },

                    success: function (successMessage, response) {

                        callback.after(true, successMessage, response)

                    },

                    error: function (errorMessage, response) {

                        callback.after(false, errorMessage, response)

                    },

                    after: function (isSuccess, message, response) {

                        /**
                         * Update Row
                         */
                        maybeUpdateTableHTML(response)

                        /**
                         * Show Result Message
                         */

                        if (!_.isEmpty(message)) {

                            var $notice,
                                noticeType,
                                noticeClass = 'is-dismissible notice-alt row-notice-actions-result'

                            try {
                                $notice = $(message)
                            } catch (error) {
                            }

                            if (
                                $notice &&
                                $notice.length && (
                                    $notice.hasClass('notice') ||
                                    $notice.hasClass('updated') ||
                                    $notice.hasClass('error')
                                )
                            ) {

                                $notice.addClass(noticeClass + ' notice')

                                if ($notice.hasClass('updated')) {
                                    $notice.removeClass('updated')
                                        .addClass('notice-success')

                                    isSuccess = true
                                }

                                if ($notice.hasClass('error')) {
                                    $notice.removeClass('error')
                                        .addClass('notice-error')

                                    isSuccess = false
                                }

                                noticeType = isSuccess ? 'success' : 'error'
                                noticeID += noticeType

                                $notice.attr('id', noticeID)

                            } else {

                                noticeType = isSuccess ? 'success' : 'error'
                                noticeID += noticeType

                                $notice = $(wp.updates.adminNotice({
                                    id: noticeID,
                                    className: `notice-${noticeType} ${noticeClass}`,
                                    message: message,
                                }))

                            }

                            $rowNotice().show().removeClass('d-none')
                            $rowNoticeList().append($notice.hide().delay(10).slideDown())

                        }


                        /**
                         * After Callback
                         */

                        // Enable Form
                        AbzarwpApp.enableForm($row())

                        // Enable Add Product Button
                        AbzarwpApp.toggleAddProductsBtn(true)

                        $rowSpinner().hide()
                        $rowCheckbox().show()
                        $rowActions().fadeIn()

                        // if (isSuccess && isChecked) {
                        //     $rowCheckbox()
                        //         .trigger('click')
                        // }

                        // Make WP Notices Dismissible
                        AbzarwpApp.$document.trigger('wp-updates-notice-added')

                    },
                }

                /**
                 * Do Action
                 */

                $.when(callback.before()).done(function () {

                    Handler.call(this,
                        $row,
                        $rowCheckbox,
                        $actionButton,
                        callback,
                        action,
                        tableID,
                    )

                })

                return true
            }

            AbzarwpApp.disableForm = function ($form) {

                $form.find(':input:not(:disabled)').prop('disabled', true).addClass('input-disabled')

            }

            AbzarwpApp.enableForm = function ($form) {

                $form.find('.input-disabled').prop('disabled', false).removeClass('input-disabled')

            }

            AbzarwpApp.confirm = function (message, action) {

                if (typeof confirmsCache[action] !== 'undefined') {
                    return confirmsCache[action]
                }

                var result = window.confirm(message)

                if (!_.isEmpty(action)) {
                    confirmsCache[action] = result
                }

                return result
            }

            AbzarwpApp.clearConfirmCache = function (action) {

                if (!_.isEmpty(action)) {
                    if (typeof confirmsCache[action] !== 'undefined') {
                        delete confirmsCache[action]
                    }
                } else {
                    confirmsCache = {}
                }

            }

            AbzarwpApp.appendFunction = function (func1, func2) {

                if (
                    _.isFunction(func1) &&
                    _.isFunction(func2)
                ) {

                    return function () {

                        var args = func2.apply(this, arguments)

                        if (args === undefined) {
                            args = arguments
                        }

                        if (args !== false) {
                            func1.apply(this, args)
                        }

                    }

                }

                return func1
            }

            /**
             * Add Product Modal
             *
             * @action get_products
             * @action add_products
             */
            AbzarwpApp.initAddProductsModal = function () {

                var addProductsAjax = null,
                    addProductsSelected = null,
                    installAddedProducts = null,
                    $addProductsModal = $('#abzarwp-app-add-products-modal'),
                    $addProductsList = $('#abzarwp-app-add-products-list'),
                    $addProductsForm = $('#abzarwp-app-add-products-form'),
                    $addProductsSelected = $('#abzarwp-app-add-products-selected'),
                    $addProductsClearSelected = $('#abzarwp-app-add-products-clear'),
                    addProductsInputs = 'input[type="checkbox"]:not(:disabled)',
                    $addProductsModalLoading = $addProductsModal.find('.abzarwp-app-loading')

                /**
                 * Get User Purchases After Open Modal
                 * @event show.bs.modal
                 * @event hide.bs.modal
                 */
                $addProductsModal.on(
                    'show.bs.modal hide.bs.modal',
                    addProductsModal__GetUserPurchases,
                )

                /**
                 * Install Added Products After Close Modal
                 * @event hide.bs.modal
                 */
                $addProductsModal.on(
                    'hide.bs.modal',
                    addProductsModal__InstallAddedProducts,
                )

                /**
                 * Refresh User Purchases
                 * @event click
                 */
                AbzarwpApp.$document.on(
                    'click',
                    '#abzarwp-app-refresh-purchases',
                    addProductsModal__GetUserPurchases,
                )

                /**
                 * Add Selected Product
                 * @event change
                 */
                $addProductsList.on(
                    'change',
                    addProductsInputs,
                    addProductsModal__RefreshForm,
                )

                /**
                 * Add Products Form
                 * @event submit
                 */
                $addProductsForm.on(
                    'submit',
                    addProductsModal__SubmitForm,
                )

                /**
                 * Clear Selected Products
                 * @event click
                 */
                $addProductsClearSelected.on(
                    'click',
                    addProductsModal__ClearSelected,
                )

                /**
                 * Open Add Product Modal by default
                 */
                if (AbzarwpApp.config.openAddProductModal) {
                    $('#abzarwp-app-add-products-modal').modal('show')
                }

                function addProductsModal__GetUserPurchases(e) {

                    if (
                        addProductsAjax &&
                        e && e.type === 'hide'
                    ) {

                        if (addProductsAjax) {
                            addProductsAjax.abort()
                        }

                        // rejected
                        // resolved
                        if (addProductsAjax.state() === 'rejected') {
                            addProductsAjax = null
                        }

                        return
                    }

                    if (
                        addProductsAjax &&
                        e && e.type === 'show'
                    ) {
                        return
                    }

                    $addProductsModalLoading.show()
                    $addProductsModalLoading.siblings().hide()

                    addProductsAjax = AbzarwpApp.ajax.post(
                        AbzarwpApp.ajaxActions.GetUserProducts)

                    addProductsAjax.done(function (html) {

                        $addProductsList.html(html)

                    })

                    addProductsAjax.fail(function () {

                        $addProductsList.html(`<div class="card card-body border-danger text-danger">${AbzarwpApp.ajaxFailMessage}</div>`)

                    })

                    addProductsAjax.always(function () {

                        $addProductsModalLoading.hide()
                        $addProductsModalLoading.siblings().show()

                        addProductsModal__RefreshForm()

                    })

                }

                function addProductsModal__RefreshForm() {

                    addProductsSelected = $addProductsList.find(
                        addProductsInputs + ':checked')

                    var selected = addProductsSelected

                    addProductsModal__ShowAlert('info', selected.length)

                    if (selected.length > 0) {

                        // Clone Selected Products to Add Products Form
                        selected = selected.clone().attr('type', 'hidden')

                        $addProductsSelected.html(selected)
                        $addProductsForm.removeClass('d-none').show()

                    } else {

                        $addProductsSelected.html('')
                        $addProductsForm.hide()

                    }

                }

                function addProductsModal__SubmitForm(e) {
                    e.preventDefault()

                    var $form = $(this),
                        formData = $form.serializeArray()

                    var btn = $form.find('[type="submit"]'),
                        spinner = AbzarwpApp.btnLoading(btn)

                    spinner.show()

                    formData.push({
                        name: 'action',
                        value: AbzarwpApp.ajaxActions.RegisterProducts,
                    })

                    AbzarwpApp.ajax.post(formData).done(function (data) {

                        spinner.success()
                        addProductsModal__ShowAlert('success', data)

                        if (addProductsSelected && addProductsSelected.length) {
                            addProductsSelected.prop('checked', true)
                            addProductsSelected.prop('disabled', true)

                            if (installAddedProducts && installAddedProducts.length) {
                                installAddedProducts = installAddedProducts.add(addProductsSelected)
                            } else {
                                installAddedProducts = addProductsSelected
                            }

                        }

                        AbzarwpApp.refreshDashboard()

                    }).fail(function (data) {

                        spinner.error()
                        addProductsModal__ShowAlert('error', data)

                    })

                }

                function addProductsModal__ClearSelected() {
                    $('[data-input-id] input[type="checkbox"]').prop('checked', false).trigger('change')
                }

                function addProductsModal__ShowAlert(type, data) {

                    if (!_.includes(['info', 'success', 'error'], type)) {
                        return
                    }

                    if (
                        true === _.isEmpty(data) &&
                        false === _.isNumber(data)
                    ) {
                        data = {}
                    }

                    var title = data.title || data,
                        message = data.message || null,
                        product_ids = data.product_ids || null

                    var $alertTitle = $addProductsForm.find('.alert-type-' + type),
                        $alertMessage = $addProductsForm.find('.alert-type-message'),
                        $alerts = $addProductsForm.find('[class*="alert-type-"]')

                    var $submitBtn = $addProductsForm.find('[type="submit"]'),
                        $spinner = $addProductsForm.find('.btn-loading')

                    var color_type = type

                    if (type === 'error') {
                        color_type = 'danger'
                    }

                    $alerts.hide()
                    $submitBtn.prop('disabled', type === 'success')

                    // Mark Selected Products

                    if (type !== 'info') {

                        var input_label = '[data-input-id]',
                            input_badge = ' > .form-check-label > .badge',
                            input_colors = input_label + ',' + input_label + input_badge

                        $(input_colors).removeClass('text-danger text-success')
                        $(input_label + input_badge).addClass('text-muted')

                        if (product_ids) {

                            $(input_label).each(function () {

                                var input_id = $(this).attr('data-input-id')

                                if (_.includes(product_ids, input_id)) {
                                    $(this).addClass('text-' + color_type)
                                    $(this).find(input_badge).addClass('text-' + color_type).removeClass('text-muted')
                                }

                            })

                        }

                    }

                    if (type === 'info') {

                        $alertTitle.find('.count').html(title)

                        // remove btn spinner
                        $spinner.remove()

                    } else if (title) {

                        $alertTitle.html(title)

                    }

                    if (false === _.isEmpty(message)) {

                        if (false === _.isString(message)) {
                            message = JSON.stringify(message)
                            message = '<pre class="m-0">' + message + '</pre>'
                        }

                        $alertMessage.find('.card').removeClass(
                            'border-danger border-success border-info text-danger text-success text-info').addClass('border-' + color_type + ' text-' + color_type).html(message)

                        $alertMessage.show()

                    }

                    $alertTitle.show()

                }

                function addProductsModal__InstallAddedProducts() {

                    if (refreshDashboardAjax) {

                        if (refreshDashboardAjax.state() === 'pending') {
                            return AbzarwpApp.$document.one('abzarwp-app-init', addProductsModal__InstallAddedProducts)
                        }

                        if (refreshDashboardAjax.state() !== 'resolved') {
                            return
                        }

                    }

                    if (!installAddedProducts || !installAddedProducts.length) {
                        return
                    }

                    installAddedProducts.each(function () {

                        var file_id

                        try {
                            file_id = $(this).val().split('|')[2]
                        } catch (error) {
                            return
                        }

                        if (!file_id) {
                            return
                        }

                        $('tr[data-id="' + file_id + '"] .row-actions a[data-row-action="install-selected"]')
                            .trigger('click')

                    })

                    installAddedProducts = null

                }
            }

            AbzarwpApp.toggleAddProductsBtn = function (show) {

                $('[data-bs-target="#abzarwp-app-add-products-modal"]').prop('disabled', !show)

            }

            /**
             * Login Without Password Modal
             */
            AbzarwpApp.getLoginWithoutPasswordTable = function () {
                return $('#abzarwp-app-lwp-table')
            }

            AbzarwpApp.initLoginWithoutPasswordModal = function () {

                /**
                 * Open Login Without Password Modal by default
                 */
                if (AbzarwpApp.config.openLoginWithoutPasswordModal) {
                    $('#abzarwp-app-login-links-modal').modal('show')
                }

                /**
                 * @action create_link
                 * @action expire_link
                 * @action revoke_all
                 */
                AbzarwpApp.$document.on(
                    'click',
                    '[data-ajax-lwp]',
                    function (e) {

                        e.preventDefault()

                        if (!AbzarwpApp.getLoginWithoutPasswordTable().length) {
                            return
                        }

                        var $btn = $(this),
                            spinner = AbzarwpApp.btnLoading($btn),
                            action = $btn.attr('data-action'),
                            token = $btn.attr('data-token')

                        var ajaxAction = AbzarwpApp.ajaxActions.LoginWithoutPassword

                        if (
                            action !== 'create_link' &&
                            !confirm(__('Are you sure?', 'abzarwp-app'))
                        ) {
                            return
                        }

                        spinner.show()

                        AbzarwpApp.ajax.post(ajaxAction, {
                            call: action,
                            token: token,
                        }).done(function (html) {
                            spinner.success()
                            AbzarwpApp.getLoginWithoutPasswordTable().replaceWith(html)
                        }).fail(function (msg) {
                            spinner.error(msg)
                        })

                    },
                )

            }

            /**
             * WP Request Filesystem Credentials Modal
             */
            AbzarwpApp.initRequestFilesystemCredentialsModal = function () {

                var modal = $('#request-filesystem-credentials-dialog'),
                    modalOpen = wp.updates.requestForCredentialsModalOpen,
                    modalClose = wp.updates.requestForCredentialsModalClose

                /**
                 * Open/Close credentials modal.
                 */

                wp.updates.requestForCredentialsModalOpen = function () {
                    modalOpen()
                    modal.modal('show')
                }

                wp.updates.requestForCredentialsModalClose = function () {
                    modalClose()
                    modal.modal('hide')
                }

                /**
                 * Handles events after the credential modal was closed.
                 *
                 * @param {Event}  event Event interface.
                 * @param {string} job   The install/update.delete request.
                 */

                AbzarwpApp.$document.on('credential-modal-cancel', function (e, job) {

                    if (_.isFunction(job.data.error)) {
                        job.data.error('cancel')
                    }

                })

            }

            /**
             * Refresh All Dashboard Tabs
             * Notice: Modals not refreshed.
             */
            AbzarwpApp.refreshDashboard = function () {

                if (refreshDashboardAjax) {
                    refreshDashboardAjax.abort()
                }

                AbzarwpApp.showPageLoading()

                refreshDashboardAjax = AbzarwpApp.ajax.post(
                    AbzarwpApp.ajaxActions.GetDashboard)

                refreshDashboardAjax.done(function (html) {

                    if (html) {
                        AbzarwpApp.getApp().replaceWith(html)
                        AbzarwpApp.$document.trigger('abzarwp-app-init')
                    }

                })

                refreshDashboardAjax.fail(function () {
                    alert(AbzarwpApp.ajaxFailMessage)
                })

                refreshDashboardAjax.always(function () {
                    AbzarwpApp.hidePageLoading()
                })

            }

            /**
             * Save Settings
             */
            AbzarwpApp.saveSettings = function (form) {

                var $form = $(form),
                    formData = $form.serializeArray()

                var btn = $form.find('[type="submit"]'),
                    spinner = AbzarwpApp.btnLoading(btn)

                spinner.show()

                formData.push({
                    name: 'action',
                    value: AbzarwpApp.ajaxActions.SaveSettings,
                })

                AbzarwpApp.ajax.post(formData).done(spinner.success).fail(spinner.error)

            }

            /**
             * Ajax Dismisses Notices
             */
            AbzarwpApp.initAjaxDismissNotice = function () {

                AbzarwpApp.$document.on(
                    'close.bs.alert',
                    '[data-notice-id]',
                    function () {

                        var notice_id = $(this).attr('data-notice-id')

                        if (!notice_id) {
                            return
                        }

                        AbzarwpApp.ajax.post(AbzarwpApp.ajaxActions.DismissNotice, {
                            notice_id: notice_id,
                        })

                    },
                )

            }

            /**
             * Back to top button
             */
            AbzarwpApp.initBackToTopBtn = function () {

                var backToTopBtn = '#abzarwp-app-backtotop'

                AbzarwpApp.$window.on('scroll resize load shown.bs.tab', function () {

                    var $backToTopBtn = $(backToTopBtn),
                        $backToTopBtnSpan = $backToTopBtn.find('>span')

                    if (!$backToTopBtn.length) {
                        return
                    }

                    var scrollTop = AbzarwpApp.$window.scrollTop(),
                        docHeight = AbzarwpApp.$document.height(),
                        winHeight = AbzarwpApp.$window.height(),

                        scrollPercent = Math.round(
                            100 * (scrollTop / (docHeight - winHeight))),
                        scrollDeg = 3.6 * scrollPercent

                    if (180 >= scrollDeg) {
                        $backToTopBtnSpan.css('background-image',
                            'linear-gradient(' + (scrollDeg + 90) +
                            'deg, transparent 50%, #fff 50%),linear-gradient(90deg, #fff 50%, transparent 50%)')
                    } else {
                        $backToTopBtnSpan.css('background-image',
                            'linear-gradient(' + (scrollDeg - 90) +
                            'deg, transparent 50%, var(--bs-primary) 50%),linear-gradient(90deg, #fff 50%, transparent 50%)')
                    }

                    if (150 < scrollTop) {
                        $backToTopBtn.removeClass('d-none')
                        $backToTopBtn.show()
                    } else {
                        $backToTopBtn.hide()
                    }

                })

                AbzarwpApp.$document.on('click', backToTopBtn, function (e) {
                    e.preventDefault()

                    $('html, body').stop().animate({scrollTop: 0}, 'fast')
                })

            }

            /**
             * Register Events
             */
            AbzarwpApp.registerEvents = function () {

                /**
                 * Ajax Submit Settings Form
                 */
                AbzarwpApp.$document.on(
                    'submit',
                    'form#abzarwp-app-settings',
                    function (e) {
                        e.preventDefault()

                        AbzarwpApp.saveSettings(this)
                    },
                )

                /**
                 * Ajax Check for Updates
                 */
                AbzarwpApp.$document.on(
                    'click',
                    '#abzarwp-app-btn-check-for-updates',
                    function (e) {
                        e.preventDefault()
                        e.stopPropagation()

                        var btn = $(this),
                            spinner = AbzarwpApp.btnLoading(btn)

                        spinner.show()
                        btn.hide()

                        AbzarwpApp
                            .ajax.post(AbzarwpApp.ajaxActions.CheckForUpdates)
                            .done(function () {

                                spinner.success()

                                // refresh page
                                location.reload()

                            })
                            .fail(function () {
                                btn.show()
                                spinner.error()
                            })
                    },
                )

            }

            return AbzarwpApp // singleton instance
        },
    )
)

if (!String.prototype.startsWith) {
    Object.defineProperty(String.prototype, 'startsWith', {
        value: function (search, rawPos) {
            var pos = rawPos > 0 ? rawPos | 0 : 0
            return this.substring(pos, pos + search.length) === search
        },
    })
}