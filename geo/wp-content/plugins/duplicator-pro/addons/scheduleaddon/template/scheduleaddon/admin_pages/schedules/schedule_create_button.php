<?php

/**
 * Duplicator Backup row in table Backups list
 */

use Duplicator\Addons\ScheduleAddon\Controllers\SchedulePageController;
use Duplicator\Addons\ScheduleAddon\Core\CapSchedule;
use Duplicator\Core\CapMng;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$blur = $tplMng->getDataValueBool('blur');

if (CapMng::can(CapSchedule::CAP_SCHEDULE, false) && !$blur) {
    $scheduleEditBaseURL = SchedulePageController::getInstance()->getEditBaseUrl();
    $tipContent          = __(
        'Create a new Scheduled Backup.',
        'duplicator-pro'
    );
    ?>  
    <span
        class="dup-new-package-wrapper"
        data-tooltip="<?php echo esc_attr($tipContent); ?>"
    >
        <a  
            href="<?php echo esc_url($scheduleEditBaseURL); ?>"
            id="dupli-create-new" 
            class="button primary tiny margin-bottom-0"
        >
           <?php esc_html_e('Add New', 'duplicator-pro'); ?>
        </a>
    </span>
     <?php
}