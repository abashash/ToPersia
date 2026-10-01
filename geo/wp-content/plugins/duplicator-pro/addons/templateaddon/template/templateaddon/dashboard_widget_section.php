<?php

/**
 * Template section in dashboard widget
 */

use Duplicator\Addons\TemplateAddon\Controllers\TemplateToolsController;
use Duplicator\Core\CapMng;
use Duplicator\Models\TemplateEntity;

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

$templatesURL = TemplateToolsController::getTemplateListURL();
if (($templates = TemplateEntity::getAllWithoutManualMode()) === false) {
    $templates = [];
}
$numTemplates = count($templates);
?>
<li>
    <span class="dup-section-label-fixed-width" >
        <span class="dashicons dashicons-admin-settings gary"></span>
        <a href="<?php echo esc_url($templatesURL); ?>"><?php
            echo esc_html(sprintf(
                _n(
                    '%s Template',
                    '%s Templates',
                    $numTemplates,
                    'duplicator-pro'
                ),
                $numTemplates
            ));
            ?>
        </a>
    </span>
</li>
