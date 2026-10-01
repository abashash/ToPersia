<?php

/**
 * Template button in schedules toolbar
 */

use Duplicator\Addons\TemplateAddon\Controllers\TemplateToolsController;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$templateUrl = TemplateToolsController::getTemplateListURL();
?>
<a
    href="<?php echo esc_url($templateUrl); ?>"
    class="button hollow secondary small dup-schedule-templates"
    title="<?php esc_attr_e("Templates", 'duplicator-pro') ?>">
    <i class="far fa-clone"></i>
</a>
