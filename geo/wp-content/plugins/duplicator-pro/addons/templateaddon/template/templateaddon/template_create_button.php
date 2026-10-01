<?php

/**
 * Template creation button
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

if (CapMng::can(CapMng::CAP_CREATE, false)) {
    $edit_template_url = TemplateToolsController::getTemplateEditURL();

    $tipContent = __(
        'Create a new Template.',
        'duplicator-pro'
    );
    ?>  
    <span
        data-tooltip="<?php echo esc_attr($tipContent); ?>"
    >
        <a  
            href="<?php echo esc_url($edit_template_url); ?>"
            id="dupli-create-new" 
            class="button primary small margin-0"
        >
            <?php esc_html_e('Add New', 'duplicator-pro'); ?>
        </a>
    </span>
    <?php
}