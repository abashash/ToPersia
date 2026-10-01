(function ($) {

    var mapTableIDsAndTypes = {
        plugins: 'plugin',
        themes: 'theme',
    }

    var wordpressAPI = {}

    /**
     * Wordpress API: Actions
     */
    wordpressAPI.actions = {
        'update-selected': {
            action: 'update',
            ajaxAction: 'update-%s',

            errorMessage: AbzarwpApp.__('Update failed: %s', 'abzarwp-app'),

            successHook: 'wp-%s-update-success',
            errorHook: 'wp-%s-update-error',
            startHook: 'wp-%s-updating',

            requestFilesystemCredentials: true,
        },
        'install-selected': {
            action: 'install',
            ajaxAction: 'install-%s',

            errorMessage: AbzarwpApp.__('Installation failed: %s', 'abzarwp-app'),

            successHook: 'wp-%s-install-success',
            errorHook: 'wp-%s-install-error',
            startHook: 'wp-%s-installing',

            requestFilesystemCredentials: true,
        },
        'delete-selected': {
            action: 'delete',
            ajaxAction: 'delete-%s',

            errorMessage: AbzarwpApp.__('Deletion failed: %s', 'abzarwp-app'),

            successHook: 'wp-%s-delete-success',
            errorHook: 'wp-%s-delete-error',
            startHook: 'wp-%s-deleting',

            requestFilesystemCredentials: true,
        },
        'activate-selected': {
            action: 'activate',
            ajaxAction: 'activate-%s',

            errorMessage: AbzarwpApp.__('Activation failed: %s', 'abzarwp-app'),

            successHook: 'wp-%s-activate-success',
            errorHook: 'wp-%s-activate-error',
            startHook: 'wp-%s-activating',

            requestFilesystemCredentials: false,
        },
        'deactivate-selected': {
            action: 'deactivate',
            ajaxAction: 'deactivate-%s',

            errorMessage: AbzarwpApp.__('Deactivation failed: %s', 'abzarwp-app'),

            successHook: 'wp-%s-deactivate-success',
            errorHook: 'wp-%s-deactivate-error',
            startHook: 'wp-%s-deactivating',

            requestFilesystemCredentials: false,
        },
        'enable-update-selected': {
            ajaxAction: AbzarwpApp.config.ajaxActions.ToggleProductUpdateOption,
        },
        'disable-update-selected': {
            ajaxAction: AbzarwpApp.config.ajaxActions.ToggleProductUpdateOption,
        },
    }

    /**
     * Wordpress API: Custom Ajax URL
     *
     * wordpressAPI.ajaxUrls[action][item_id] = url
     */

    wordpressAPI.ajaxUrls = {}
    wordpressAPI.setAjaxUrl = function (action, item_id, url) {

        if (
            _.isEmpty(action) ||
            _.isEmpty(item_id) ||
            _.isEmpty(url)
        ) {
            return false
        }

        if (typeof wordpressAPI.ajaxUrls[action] === 'undefined') {
            wordpressAPI.ajaxUrls[action] = {}
        }

        wordpressAPI.ajaxUrls[action][item_id] = url

        return true
    }

    /**
     * Filter Jquery Ajax Request URL
     */
    $.ajaxPrefilter(function (options, originalOptions, jqXHR) {

        if (
            typeof options.data !== 'undefined' &&
            typeof originalOptions.data !== 'undefined' &&
            typeof originalOptions.data.abzarwp_action !== 'undefined' &&
            typeof originalOptions.data.abzarwp_item_id !== 'undefined'
        ) {

            var action = originalOptions.data.abzarwp_action,
                item_id = originalOptions.data.abzarwp_item_id

            if (
                typeof wordpressAPI.ajaxUrls !== 'undefined' &&
                typeof wordpressAPI.ajaxUrls[action] !== 'undefined' &&
                typeof wordpressAPI.ajaxUrls[action][item_id] !== 'undefined'
            ) {

                options.url = wordpressAPI.ajaxUrls[action][item_id]
                options.type = 'GET'
                options.dataTypes = ['json']

                options.data = {
                    _fs_nonce: wp.updates.filesystemCredentials.fsNonce,
                    username: wp.updates.filesystemCredentials.ftp.username,
                    password: wp.updates.filesystemCredentials.ftp.password,
                    hostname: wp.updates.filesystemCredentials.ftp.hostname,
                    connection_type: wp.updates.filesystemCredentials.ftp.connectionType,
                    public_key: wp.updates.filesystemCredentials.ssh.publicKey,
                    private_key: wp.updates.filesystemCredentials.ssh.privateKey,
                }

                if (options.processData) {
                    options.data = $.param(options.data)
                }

            }

        }

        return options
    })


    /**
     * Wordpress API: Ajax
     *
     * @param {Function:jQuery}   $row
     * @param {Function:jQuery}   $checkbox
     * @param {Function:jQuery}   $button
     * @param {Object}            callback
     * @param {Function}          callback.error
     * @param {Function}          callback.success
     * @param {Function}          callback.before
     * @param {Function}          callback.after
     * @param {String}            action
     * @param {String}            tableID
     *
     * @see wp.updates.ajax
     *
     * @see wp.updates.updateTheme
     * @see wp.updates.updatePlugin
     *
     * @see wp.updates.installPlugin
     * @see wp.updates.installTheme
     *
     * @see wp.updates.deletePlugin
     * @see wp.updates.deleteTheme
     */
    wordpressAPI.ajax = function ($row, $checkbox, $button, callback, action, tableID) {

        if ($button().length && $button().attr('data-ajax-use-href') === 'true') {

            var linkHref = $button().attr('href')

            if (_.isEmpty(linkHref)) {
                action = ''
            } else {
                wordpressAPI.setAjaxUrl(
                    action,
                    $checkbox().val(),
                    linkHref,
                )
            }

        }

        if (
            typeof mapTableIDsAndTypes[tableID] === "undefined" ||
            typeof wordpressAPI.actions[action] === "undefined"
        ) {

            if (_.isFunction(callback.error)) {
                callback.error()
            }

            return
        }

        var args = {},
            options = wordpressAPI.actions[action],
            type = mapTableIDsAndTypes[tableID]

        options = _.extend({
            action: '',
            ajaxAction: '',

            errorMessage: '',
            successMessage: '',

            successHook: '',
            errorHook: '',
            startHook: '',

            requestFilesystemCredentials: false,

            startCallback: null,
            successCallback: null,
            errorCallback: null,

            data: {},

        }, options)

        var rawAction = options.action,
            ajaxAction = AbzarwpApp.sprintf(options.ajaxAction, type),
            successHook = AbzarwpApp.sprintf(options.successHook, type),
            errorHook = AbzarwpApp.sprintf(options.errorHook, type),
            startHook = AbzarwpApp.sprintf(options.startHook, type),
            requestFilesystemCredentials = options.requestFilesystemCredentials

        var startCallback = options.startCallback,
            successCallback = options.successCallback,
            errorCallback = options.errorCallback

        args = {

            abzarwp_item_id: $checkbox().val(),
            abzarwp_tab_id: tableID,
            abzarwp_action: action,
            [type]: $row().attr('data-' + type),
            slug: $row().attr('data-slug'),

        }

        /**
         * Ajax: Success
         */
        args.success = function (response) {

            var successMessage

            if (!_.isEmpty(options.successMessage)) {
                successMessage = options.successMessage
            } else {
                successMessage = AbzarwpApp.ajax.getResponseMessage(response, true)
            }

            if (_.isFunction(successCallback)) {
                successCallback.call(this, response, $button, tableID)
            }

            if (_.isFunction(callback.success)) {
                callback.success.call(this, successMessage, response)
            }

            if (!_.isEmpty(successHook)) {
                AbzarwpApp.$document.trigger(successHook, response)
            }

        }

        /**
         * Ajax: Error
         */
        args.error = function (response) {

            // this method override in app.js
            if (wp.updates.maybeHandleCredentialError(response)) {

                return
            }

            var errorMessage = null

            if (AbzarwpApp.ajax.isValidResponse(response)) {

                var responseMessage = AbzarwpApp.ajax.getResponseMessage(response, false)

                if (!_.isEmpty(options.errorMessage)) {
                    if (!_.isEmpty(responseMessage)) {
                        errorMessage = AbzarwpApp.sprintf(
                            options.errorMessage,
                            responseMessage,
                        )
                    }
                } else {
                    errorMessage = responseMessage
                }

            }

            if (_.isFunction(errorCallback)) {
                errorCallback.call(this, response, $button, tableID)
            }

            if (_.isFunction(callback.error)) {
                callback.error.call(this, errorMessage, response)
            }

            if (!_.isEmpty(errorHook)) {
                AbzarwpApp.$document.trigger(errorHook, response)
            }

        }

        // Set Custom Data
        if (!_.isEmpty(options.data)) {
            _.forEach(options.data, function (value, key) {

                if (value.startsWith('data-')) {
                    value = $row.attr(value)
                }

                args[key] = value
            })
        }

        if (_.isFunction(startCallback)) {
            if (!startCallback.call(this, response, $button, tableID)) {
                return
            }
        }

        if (requestFilesystemCredentials) {
            wp.updates.maybeRequestFilesystemCredentials()
        }

        if (!_.isEmpty(startHook)) {
            AbzarwpApp.$document.trigger(startHook, args)
        }

        wp.updates.ajax(ajaxAction, args)

    }


    /**
     * Register Product Actions
     */
    $.each(mapTableIDsAndTypes, function (tableID, type) {

        _.extend(AbzarwpApp.actions, {[tableID]: {}})

        if (typeof AbzarwpApp.actions[tableID] === 'undefined') {
            AbzarwpApp.actions[tableID] = {}
        }

        _.extend(AbzarwpApp.actions[tableID], {

                /**
                 * Update Product
                 *
                 * @param {jQuery} $row
                 * @param {jQuery} $checkbox
                 * @param {jQuery} $button
                 * @param {Object} callback
                 *
                 * @see wp.updates.installPlugin
                 * @see wp.updates.installTheme
                 */
                'update-selected': AbzarwpApp.appendFunction(wordpressAPI.ajax,
                    function ($row, $checkbox, $button, callback) {


                    },
                ),


                /**
                 * Install Product
                 *
                 * @param {jQuery} $row
                 * @param {jQuery} $checkbox
                 * @param {jQuery} $button
                 * @param {Object} callback
                 *
                 * @see wp.updates.installPlugin
                 * @see wp.updates.installTheme
                 */
                'install-selected': AbzarwpApp.appendFunction(wordpressAPI.ajax,
                    function ($row, $checkbox, $button, callback) {

                        callback.error = AbzarwpApp.appendFunction(
                            callback.error,
                            function (errorMessage, response) {

                                if (response === 'cancel') {
                                    errorMessage = AbzarwpApp.__('Install canceled.', 'abzarwp-app')
                                }

                                return arguments
                            },
                        )

                    },
                ),


                /**
                 * Delete Product
                 *
                 * @param {jQuery} $row
                 * @param {jQuery} $checkbox
                 * @param {jQuery} $button
                 * @param {Object} callback
                 * @param {string} action
                 *
                 * @see wp.updates.deletePlugin
                 * @see wp.updates.deleteTheme
                 */
                'delete-selected': AbzarwpApp.appendFunction(wordpressAPI.ajax,
                    function ($row, $checkbox, $button, callback, action) {

                        if (!$button().is(":focus")) {

                            var confirmMessage = 'plugin' === type ?
                                AbzarwpApp.__('Are you sure you want to delete the selected plugins and their data?') :
                                AbzarwpApp.__('Caution: These themes may be active on other sites in the network. Are you sure you want to proceed?')

                            if (!AbzarwpApp.confirm(confirmMessage, action)) {
                                action = ''
                                return arguments
                            }

                        }

                        // show success delete message
                        callback.success = AbzarwpApp.appendFunction(
                            callback.success,
                            function (successMessage, response) {

                                var isDeleted = true

                                // make sure product deleted successfully
                                if (_.isObject(response) && typeof response.abzarwp_table_row !== 'undefined') {
                                    isDeleted = false
                                }

                                // check is product remove from list
                                if (
                                    typeof wordpressAPI.ajaxUrls !== 'undefined' &&
                                    typeof wordpressAPI.ajaxUrls[action] !== 'undefined' &&
                                    typeof wordpressAPI.ajaxUrls[action][$checkbox().val()] !== 'undefined'
                                ) {
                                    isDeleted = true
                                }

                                // replace row with delete message
                                if (isDeleted) {

                                    if (!_.isObject(response)) {
                                        response = {

                                            message: response,
                                        }
                                    }

                                    var name = $row().find('.column-name label').text(),
                                        icon = '<i class="dashicons dashicons-trash me-2 text-danger"></i>',
                                        message = AbzarwpApp.sprintf(AbzarwpApp.__('%s was successfully deleted.', 'abzarwp-app'), `<strong>${icon}${name}</strong>`)

                                    response.abzarwp_table_row = `<tr class="search-exclude"><td colspan="20">${message}</td></tr>`

                                }

                                return arguments
                            },
                        )


                    },
                ),


                /**
                 * Activate Product
                 *
                 * @param {jQuery} $row
                 * @param {jQuery} $checkbox
                 * @param {jQuery} $button
                 * @param {Object} callback
                 * @param {string} action
                 *
                 */
                'activate-selected': AbzarwpApp.appendFunction(wordpressAPI.ajax,
                    function ($row, $checkbox, $button, callback, action) {

                    },
                ),


                /**
                 * Deactivate Product
                 *
                 * @param {jQuery} $row
                 * @param {jQuery} $checkbox
                 * @param {jQuery} $button
                 * @param {Object} callback
                 * @param {string} action
                 *
                 */
                'deactivate-selected': AbzarwpApp.appendFunction(wordpressAPI.ajax,
                    function ($row, $checkbox, $button, callback, action) {

                    },
                ),


                /**
                 * Enable Update
                 *
                 * @param {jQuery} $row
                 * @param {jQuery} $checkbox
                 * @param {jQuery} $button
                 * @param {Object} callback
                 * @param {string} action
                 *
                 */
                'enable-update-selected': AbzarwpApp.appendFunction(wordpressAPI.ajax,
                    function ($row, $checkbox, $button, callback, action) {

                    },
                ),


                /**
                 * Disable Update
                 *
                 * @param {jQuery} $row
                 * @param {jQuery} $checkbox
                 * @param {jQuery} $button
                 * @param {Object} callback
                 * @param {string} action
                 *
                 */
                'disable-update-selected': AbzarwpApp.appendFunction(wordpressAPI.ajax,
                    function ($row, $checkbox, $button, callback, action) {

                    },
                ),

            },
        )


    })

})(jQuery)