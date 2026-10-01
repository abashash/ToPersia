<?php

use Duplicator\Addons\ImportAddon\Controllers\ImportPageController;
use Duplicator\Package\AbstractPackageDeployer;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */
$importObj         = $tplMng->getDataValueObjRequired('importObj', AbstractPackageDeployer::class);
$iframeSrc         = $tplMng->getDataValueStringRequired('iframeSrc');
$importFailMessage = '';

if (!$importObj->isImportable($importFailMessage)) {
    ?>
    <div class="wrap dup-styles">
        <h1>
            <?php esc_html_e("Install Backup", 'duplicator-pro'); ?>
        </h1>
        <div class="dupli-import-installer-content-wrapper" >
            <p class="orangered">
                <?php echo esc_html($importFailMessage); ?>
            </p>
        </div>
    </div>
<?php } else { ?>
    <div id="dupli-import-installer-wrapper" class="dup-styles" >
        <div id="dupli-import-installer-top-bar" >
            <a href="<?php echo esc_url(ImportPageController::getImportPageLink()); ?>" class="button secondary hollow small margin-bottom-0" >
                <i class="fa fa-caret-left"></i> <?php esc_html_e("Back to Import", 'duplicator-pro'); ?>
            </a>&nbsp;
            <?php do_action('duplicator_import_installer_top_bar'); ?>
        </div>
        <div id="dupli-import-installer-modal" class="no-display"></div>
        <iframe id="dupli-import-installer-iframe" src="<?php echo esc_url($iframeSrc); ?>" ></iframe>
    </div>
    <?php
}
