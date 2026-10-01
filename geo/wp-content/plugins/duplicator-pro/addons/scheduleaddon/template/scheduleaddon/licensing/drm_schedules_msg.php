<?php

/**
 * Schedule DRM message for license expired notice
 */

use Duplicator\Addons\ScheduleAddon\Core\LicenseSchedule;
use Duplicator\Addons\ScheduleAddon\Core\ScheduleDrmHandler;

defined("ABSPATH") or die("");

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$daysLeft = ScheduleDrmHandler::getDaysTillScheduleDRM();

if ($daysLeft === false) {
    return;
}

if ($daysLeft >= 0) {
    ?>
    <u>
        <?php
        if (LicenseSchedule::can()) {
            $message  = sprintf(
                _n(
                    'Scheduled Backups are going to be disabled <b><em>in %d day</em></b>.',
                    'Scheduled Backups are going to be disabled <b><em>in %d days</em></b>.',
                    $daysLeft,
                    'duplicator-pro'
                ),
                $daysLeft
            );
            $message .= __(' Please renew your license to assure your backups are not interrupted.', 'duplicator-pro');
            echo wp_kses(
                $message,
                [
                    'b'  => [],
                    'em' => [],
                ]
            );
        } else {
            esc_html_e(
                'All automatic backups have been disabled. Please renew your license to re-enable them.',
                'duplicator-pro'
            );
        }
        ?>
    </u>
    <?php
} else {
    esc_html_e(
        'Scheduled Backups.',
        'duplicator-pro'
    );
}
