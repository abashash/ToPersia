<?php

/**
 * Duplicator Backup row in table Backups list
 */

use Duplicator\Addons\RecoveryAddon\Package\RecoveryStatus;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Package\DupPackage;
use Duplicator\Addons\RecoveryAddon\Views\RecoveryViewHelper;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var ControllersManager $ctrlMng
 * @var TplMng $tplMng
 */
$package = $tplMng->getDataValueObjRequired('package', DupPackage::class);

$tooltipContent = __('A Backup Recovery Point status is not required to be enabled and in some cases is desirable.', 'duplicator-pro') . ' ' .
    __('For example you may want to backup only your database.', 'duplicator-pro') . ' ' .
    __(
        'In this case you can still run a database only install, however the ability to use the recovery point installer will be unavailable.',
        'duplicator-pro'
    );
?>

<h3 class="dup-title margin-top-0">
    <?php RecoveryViewHelper::disasterIcon(true, 'maroon'); ?>&nbsp;
    <?php esc_html_e('Disaster Recovery - Isn\'t Available for this Backup', 'duplicator-pro'); ?>
    <sup>
        <i class="fas fa-question-circle fa-xs"
            data-tooltip-title="<?php esc_attr_e('Recovery', 'duplicator-pro'); ?>"
            data-tooltip="<?php echo esc_attr($tooltipContent); ?>">
        </i>
    </sup>
</h3>

<?php $tplMng->render('recoveryaddon/parts/recovery/package_info_mini'); ?>
<hr class="margin-top-1 margin-bottom-1">

<?php
$recoverStatus = new RecoveryStatus($package);
$tplMng->render('recoveryaddon/parts/recovery/exclude_data_box', ['recoverStatus' => $recoverStatus]);
