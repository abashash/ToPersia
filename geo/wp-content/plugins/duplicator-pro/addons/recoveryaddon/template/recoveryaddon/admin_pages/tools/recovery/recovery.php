<?php

use Duplicator\Addons\RecoveryAddon\Controllers\RecoveryController;
use Duplicator\Addons\RecoveryAddon\Package\RecoveryPackage;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Views\TplMng;
use Duplicator\Addons\RecoveryAddon\Views\RecoveryViewHelper;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var ControllersManager $ctrlMng
 * @var TplMng $tplMng
 */

$blur = $tplMng->getDataValueBool('blur');

$recoverPackage      = RecoveryPackage::getRecoverPackage();
$recoverPackageId    = RecoveryPackage::getRecoverPackageId();
$recoverablePackages = RecoveryPackage::getRecoverablesPackages();

?>
<h2 class="margin-bottom-0">
    <?php RecoveryViewHelper::disasterIcon(); ?>&nbsp;<?php esc_html_e("Disaster Recovery", 'duplicator-pro'); ?>
</h2>
<hr/>

<p class="margin-bottom-1">
    <?php esc_html_e("Quickly restore this site to a specific Backup in time.", 'duplicator-pro'); ?>
    <span class="link-style dup-global-help">
        <?php esc_html_e("Need more help?", 'duplicator-pro'); ?>
    </span>
</p>
<div class="dupli-recovery-details-max-width-wrapper <?php echo ($blur ? 'dup-mock-blur' : ''); ?>" >
    <?php if (RecoveryPackage::isDisallow()) { ?>
        <p>
            <?php esc_html_e("The import function is disabled", 'duplicator-pro'); ?>
        </p>
        <?php
        return;
    }
    ?>
    <form id="dupli-recovery-form" method="post">
        <?php
        RecoveryController::renderRecoveryWidget([
            'selector'   => true,
            'subtitle'   => '',
            'copyLink'   => true,
            'copyButton' => true,
            'launch'     => true,
            'download'   => true,
            'info'       => true,
        ]);
        ?>
    </form>
</div>
<?php
$tplMng->render('recoveryaddon/admin_pages/tools/recovery/widget/recovery-widget-scripts');
