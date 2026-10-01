<?php

/**
 * Template button in packages toolbar
 */

use Duplicator\Addons\TemplateAddon\Controllers\TemplateToolsController;
use Duplicator\Core\CapMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

if (!CapMng::can(CapMng::CAP_CREATE, false)) {
    return;
}

$templateUrl = TemplateToolsController::getTemplateListURL();
?>
<a href="<?php echo esc_url($templateUrl); ?>"
    class="button hollow secondary small dupli-toolbar-templates"
    data-tooltip-title="<?php esc_attr_e("Templates", 'duplicator-pro'); ?>"
    data-tooltip="<?php esc_attr_e("Create Backup Templates with Preset Configurations.", 'duplicator-pro'); ?>"
>
    <i class="far fa-clone fa-fw"></i>
</a>
