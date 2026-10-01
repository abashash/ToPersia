<?php

/**
 * Template for DupCloud "Connect via License" button and JS.
 * Rendered by ProBase into the DupCloud storage auth form.
 */

use Duplicator\Addons\ProBase\DupCloud\DupCloudQuickConnect;

defined('ABSPATH') || exit;

?>
<button
    id="dupli-dupcloud-license-connect-btn"
    type="button"
    class="button secondary hollow margin-bottom-0"
>
    <i class="fa fa-key"></i>
    <?php
    printf(
        /* translators: %s: plugin name */
        esc_html__('Connect via %s License', 'duplicator-pro'),
        esc_html(DUPLICATOR____NAME)
    );
    ?>
</button>
&nbsp;<strong><?php esc_html_e('or', 'duplicator-pro'); ?></strong>&nbsp;
<script>
    jQuery(document).ready(function($) {
        DupliJs.Storage.DupCloud = DupliJs.Storage.DupCloud || {};

        DupliJs.Storage.DupCloud.LicenseConnect = function($button, $skipNoLicenses = false) {
            var originalText = $button.html();
            $button.html('<i class="fa fa-spinner fa-spin"></i> <?php esc_html_e("Getting tokens...", 'duplicator-pro'); ?>').prop('disabled', true);

            DupliJs.Util.ajaxWrapper({
                action: '<?php echo esc_js(DupCloudQuickConnect::AJAX_ACTION_QUICK_CONNECT); ?>',
                nonce: '<?php echo esc_js(wp_create_nonce(DupCloudQuickConnect::AJAX_ACTION_QUICK_CONNECT)); ?>'
            },
            function(result, data, funcData, textStatus, jqXHR) {
                if (result.success) {
                    var tokens = funcData.tokens;
                    var modalHtml = funcData.html;

                    if (tokens.length === 0) {
                        $button.html(originalText).prop('disabled', false);
                        if ($skipNoLicenses == false) {
                            DupliJs.Storage.DupCloudLicense.showModal(modalHtml);
                        }
                    } else if (tokens.length === 1) {
                        DupliJs.Storage.DupCloudLicense.autoConnect(tokens[0]);
                    } else {
                        DupliJs.Storage.DupCloudLicense.showModal(modalHtml, tokens);
                    }
                } else {
                    $button.html(originalText).prop('disabled', false);
                    if (data.message && $skipNoLicenses == false) {
                        DupliJs.addAdminMessage(data.message, 'error');
                    }
                }
            });
        }

        $('#dupli-dupcloud-license-connect-btn').click(function(event) {
            event.stopPropagation();
            var $button = $(this);
            DupliJs.Storage.DupCloud.LicenseConnect($button);
            return false;
        });
    });

    DupliJs.Storage.DupCloudLicense = DupliJs.Storage.DupCloudLicense || {};

    DupliJs.Storage.DupCloudLicense.showModal = function(htmlContent, tokens) {
        tokens = tokens || null;
        var modal = new DuplicatorModalBox({
            htmlContent: htmlContent,
            closeInContent: true,
            closeColor: "#000",
            openCallback: function(content, modalObj) {
                if (tokens && tokens.length > 1) {
                    jQuery(content).find('[data-token-select]').click(function() {
                        var tokenIndex = jQuery(this).data('token-select');
                        if (tokens[tokenIndex]) {
                            modalObj.close();
                            DupliJs.Storage.DupCloudLicense.autoConnect(tokens[tokenIndex]);
                        }
                    });
                }
            }
        });
        modal.open();
    };

    DupliJs.Storage.DupCloudLicense.autoConnect = function(storageToken) {
        jQuery('#dupcloud-compound-token').val(storageToken.token);
        setTimeout(function() {
            jQuery('#dupcloud-finalize-setup').click();
        }, 100);
    };
</script>
