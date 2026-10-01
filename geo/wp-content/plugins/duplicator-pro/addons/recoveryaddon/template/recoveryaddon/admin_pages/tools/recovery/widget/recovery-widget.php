<?php

use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var ControllersManager $ctrlMng
 * @var TplMng $tplMng
 */

$displayDetails      = $tplMng->getDataValueBool('displayDetails');
$selector            = $tplMng->getDataValueBool('selector');
$recoverablePackages = $tplMng->getDataValueArray('recoverablePackages');
?>
<div class="dupli-recovery-widget-wrapper" >
    <?php if ($displayDetails) { ?>
    <div class="dupli-recovery-point-details margin-bottom-1">
        <?php $tplMng->render('recoveryaddon/admin_pages/tools/recovery/widget/recovery-widget-details'); ?>
    </div>
    <?php } ?>
    <?php if ($selector) {
        $tplMng->render('recoveryaddon/admin_pages/tools/recovery/widget/recovery-widget-selector');
    } ?>
    <div class="dupli-recovery-point-actions">
        <?php if ($recoverablePackages) {
            $tplMng->render('recoveryaddon/admin_pages/tools/recovery/widget/recovery-widget-link-actions');
        } ?>
    </div>
</div>
