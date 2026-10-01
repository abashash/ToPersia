<?php

/**
 * Duplicator Backup row in table Backups list
 */

use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryController;
use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryPackagesController;
use Duplicator\Controllers\PackagesPageController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;
use Duplicator\Package\DupPackage;
use Duplicator\Addons\RecoveryAddon\Views\RecoveryViewHelper;

/**
 * Variables
 *
 * @var ControllersManager $ctrlMng
 * @var TplMng $tplMng
 */
$package        = $tplMng->getDataValueObjRequired('package', DupPackage::class);
$isRecoverPoint = (RecoveryPackage::getRecoverPackageId() === $package->getId());

$colorClass = ($isRecoverPoint ? 'green' : '');
?>
<h3 class="dup-title margin-top-0">
    <?php RecoveryViewHelper::disasterIcon(true, $colorClass); ?>&nbsp;
    <?php
    if ($isRecoverPoint) {
        esc_html_e('Disaster Recovery - Is Set on this Backup', 'duplicator-pro');
    } else {
        esc_html_e('Disaster Recovery - Is Available for this Backup', 'duplicator-pro');
    }
    ?>
</h3>

<?php $tplMng->render('recoveryaddon/parts/recovery/package_info_mini'); ?>
<hr class="margin-top-1 margin-bottom-1">

<?php if ($isRecoverPoint) {
    RecoveryController::renderRecoveryWidget([
        'details'    => false,
        'selector'   => false,
        'subtitle'   => '',
        'copyLink'   => false,
        'copyButton' => true,
        'launch'     => false,
        'download'   => true,
        'info'       => true,
    ]);
} else { ?>
    <form method="post" action="<?php PackagesPageController::getInstance()->getPageUrl(); ?>">
        <?php PackagesPageController::getInstance()->getActionByKey(RecoveryPackagesController::ACTION_SET_RECOVERY_POINT)->getActionNonceFileds(); ?>
        <input type="hidden" name="recovery_package" value="<?php echo (int) $package->getId(); ?>">
        <div class="dupli-recovery-widget-wrapper">
            <div class="dupli-recovery-point-actions">
                <div class="dupli-recovery-buttons">
                    <button
                        class="button primary dupli-btn-set-recovery margin-0"
                        type="submit">
                        <span><?php RecoveryViewHelper::disasterIcon(); ?>&nbsp;
                            <?php esc_html_e("Set Disaster Recovery", 'duplicator-pro'); ?></span>&nbsp;
                        <i
                            class="fa-solid fa-question-circle fa-sm dark-gray-color dup-base-color white"
                            data-tooltip-title="<?php esc_attr_e("Activate Recovery", 'duplicator-pro'); ?>"
                            data-tooltip="<?php esc_attr_e("This action will set this Backup as the active Disaster Recovery Backup.", 'duplicator-pro'); ?>"
                            aria-expanded="false">
                        </i>
                    </button>
                </div>
            </div>
        </div>
    </form>
<?php }
