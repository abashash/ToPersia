<?php

/**
 * Duplicator Backup row in table Backups list
 */

use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryController;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */
?>
<div class="dupli-toolbar-recovery-info margin-bottom-1">
    <?php
    if (RecoveryPackage::getRecoverPackageId() === false) {
        $tplMng->render('recoveryaddon/admin_pages/packages/recovery_info/no_recovery_set');
    } else {
        RecoveryController::renderRecoveryWidget(
            [
                'selector'   => false,
                'subtitle'   => '',
                'copyLink'   => true,
                'copyButton' => true,
                'launch'     => true,
                'download'   => true,
                'info'       => true,
            ]
        );
    }
    ?>
</div>
