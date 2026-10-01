<?php

/**
 * Disaster Recovery section injected into import step 2
 */

use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng $tplMng
 */

$recoverPackage = RecoveryPackage::getRecoverPackage();
$recoverId      = RecoveryPackage::getRecoverPackageId();
$isOutToDate    = ($recoverPackage instanceof RecoveryPackage) ? $recoverPackage->isOutToDate() : true;

if ($recoverId === false || $isOutToDate) {
    $badgeClass = 'badge-warn';
    $badgeLabel = 'Notice';
} else {
    $badgeClass = 'badge-pass';
    $badgeLabel = 'Good';
}
?>
<div class="dupli-import-box closable opened" >
    <div class="box-title" >
        <?php esc_html_e('Disaster Recovery', 'duplicator-pro'); ?>
        <div class="badge <?php echo esc_attr($badgeClass); ?> margin-right-1">
            <?php echo esc_html($badgeLabel); ?>
        </div>
    </div>
    <div class="box-content">
        <div id="dupli-recovery-details-select-entry" class="dupli-recovery-info-set" >
            <?php
            \Duplicator\Addons\RecoveryAddon\Controllers\RecoveryController::renderWidgetWithScripts([
                'selector'   => true,
                'subtitle'   => '',
                'copyLink'   => true,
                'copyButton' => true,
                'launch'     => false,
                'download'   => true,
                'info'       => true,
            ]);
            ?>
        </div>
        <hr>

        <div class="dupli-recovery-not-required">
            <i class="far fa-arrow-alt-circle-right"></i>
            <?php
            esc_html_e(
                'The Recovery Point is not mandatory to perform an import.
                However, it can assist in restoring this site if there is a problem during install.
                If you have no need to recover this site then you can continue without creating the Recovery Point.',
                'duplicator-pro'
            );
            ?>
        </div>

    </div>
</div><br/>
