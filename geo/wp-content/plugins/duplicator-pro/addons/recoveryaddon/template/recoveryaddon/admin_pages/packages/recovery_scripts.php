<?php

/**
 * Recovery addon scripts for packages page.
 * Renders recovery widget scripts, UiDialogs, and JS click handlers.
 */

use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryToolsController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Views\UI\UiDialog;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

/* ------------------------------------------
 * ALERT: Recovery > toolbar button        */
$toolBarRecoveryButtonInfo               = new UiDialog();
$toolBarRecoveryButtonInfo->showButtons  = false;
$toolBarRecoveryButtonInfo->height       = 600;
$toolBarRecoveryButtonInfo->width        = 600;
$toolBarRecoveryButtonInfo->title        = __('Disaster Recovery', 'duplicator-pro');
$toolBarRecoveryButtonInfo->templatePath = 'recoveryaddon/admin_pages/packages/recovery_info/info';
$toolBarRecoveryButtonInfo->initAlert();

/* ------------------------------------------
 * ALERT: Recovery                         */
$availableRecoveryBox              = new UiDialog();
$availableRecoveryBox->title       = __('Disaster Recovery Available', 'duplicator-pro');
$availableRecoveryBox->boxClass    = 'dup-recovery-box-info';
$availableRecoveryBox->showButtons = false;
$availableRecoveryBox->width       = 600;
$availableRecoveryBox->height      = 400;
$availableRecoveryBox->message     = '';
$availableRecoveryBox->initAlert();

$unavailableRecoveryBox              = new UiDialog();
$unavailableRecoveryBox->title       = __('Disaster Recovery Unavailable', 'duplicator-pro');
$unavailableRecoveryBox->boxClass    = 'dup-recovery-box-info';
$unavailableRecoveryBox->showButtons = false;
$unavailableRecoveryBox->width       = 600;
$unavailableRecoveryBox->height      = 700;
$unavailableRecoveryBox->message     = '';
$unavailableRecoveryBox->initAlert();

$tplMng->render('recoveryaddon/admin_pages/tools/recovery/widget/recovery-widget-scripts');

?>
<script>
jQuery(document).ready(function($) {
    // Toolbar recovery button click
    $('.dupli-toolbar-recovery-info').click(function() {
        if ($(this).hasClass('dup-recovery-unset')) {
            <?php $toolBarRecoveryButtonInfo->showAlert(); ?>
        } else {
            let openUrl = <?php echo json_encode($ctrlMng->getMenuLink($ctrlMng::TOOLS_SUBMENU_SLUG, RecoveryToolsController::L2_SLUG_RECOVERY)); ?>;
            window.open(openUrl, "_self");
        }
    });

    // Recovery box click (per-package disaster flag icon)
    $(document).on('click', '.dupli-btn-open-recovery-box', function(event) {
        event.preventDefault();

        let packageId = $(this).data('package-id');

        DupliJs.Util.ajaxWrapper({
                action: 'duplicator_get_recovery_box_content',
                packageId: packageId,
                nonce: '<?php echo esc_js(wp_create_nonce('duplicator_get_recovery_box_content')); ?>'
            },
            function(result, data, funcData, textStatus, jqXHR) {
                if (funcData.success) {
                    let boxContent = funcData.content;
                    if (funcData.isRecoverable) {
                        <?php
                        $availableRecoveryBox->updateMessage('boxContent');
                        $availableRecoveryBox->showAlert();
                        ?>
                        $('.dupli-recovery-download-launcher').off().click(function() {
                            DupliJs.Pack.downloadLauncher();
                        });
                    } else {
                        <?php
                        $unavailableRecoveryBox->updateMessage('boxContent');
                        $unavailableRecoveryBox->showAlert();
                        ?>
                    }
                } else {
                    DupliJs.addAdminMessage(funcData.message, 'error');
                }
                return '';
            }
        );

        return false;
    });
});
</script>
