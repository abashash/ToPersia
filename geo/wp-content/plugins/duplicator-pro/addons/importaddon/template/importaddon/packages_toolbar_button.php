<?php

/**
 * Import button in packages toolbar
 */

use Duplicator\Addons\ImportAddon\Controllers\ImportPageController;
use Duplicator\Addons\ImportAddon\Core\CapImport;
use Duplicator\Core\CapMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

if (!CapMng::can(CapImport::CAP_IMPORT, false)) {
    return;
}
?>
<a href="<?php echo esc_url(ImportPageController::getImportPageLink()); ?>"
    id="btn-logs-dialog"
    class="button hollow secondary small dupli-toolbar-import"
    data-tooltip-title="<?php esc_attr_e("Import", 'duplicator-pro'); ?>"
    data-tooltip="<?php esc_attr_e("Import a backup from a file or link.", 'duplicator-pro'); ?>"
>
    <i class="fas fa-arrow-alt-circle-down fa-fw"></i>
</a>
