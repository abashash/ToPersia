<?php

/**
 * Schedule section in the dashboard widget
 */

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon;

use Duplicator\Addons\ScheduleAddon\Controllers\SchedulePageController;
use Duplicator\Addons\ScheduleAddon\Core\CapSchedule;
use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Core\CapMng;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Addons\ScheduleAddon\ScheduleProcessor;

/**
 * Renders the schedule section in the dashboard widget.
 *
 * Hooked to the `duplicator_dashboard_widget_sections` action.
 */
class ScheduleDashboardWidget
{
    /**
     * Render the schedule section in the dashboard widget
     *
     * @return void
     */
    public static function renderSection(): void
    {
        if (!CapMng::can(CapSchedule::CAP_SCHEDULE, false)) {
            return;
        }

        $allScheduleIds = ScheduleEntity::getIds();
        if ($allScheduleIds === false) {
            $allScheduleIds = [];
        }

        $activeSchedules = ScheduleEntity::getActive();
        $numSchedules    = count($allScheduleIds);
        $numEnabled      = count($activeSchedules);

        $nextRunTime = ScheduleProcessor::calculateEarliestScheduleRunTime($activeSchedules);

        $nextScheduleString = '';
        if ($nextRunTime >= 0) {
            $nextDate           = date_i18n(get_option('date_format'), $nextRunTime);
            $nextHours          = date_i18n(get_option('time_format'), $nextRunTime);
            $nextScheduleString = $nextDate . ' ' . $nextHours;
        }

        $scheduleUrl = ControllersManager::getMenuLink(SchedulePageController::SCHEDULES_SUBMENU_SLUG);
        ?>
        <li class="dup-flex-content">
            <span class="dup-section-label-fixed-width" >
                <span class="dashicons dashicons-update gray"></span>
                <a href="<?php echo esc_url($scheduleUrl); ?>"><?php
                    echo esc_html(sprintf(
                        _n(
                            '%s Schedule',
                            '%s Schedules',
                            $numSchedules,
                            'duplicator-pro'
                        ),
                        $numSchedules
                    ));
                            ?></a>
            </span>
            <span>
                <?php esc_html_e('Enabled', 'duplicator-pro'); ?>:
                <b class="<?php echo ($numEnabled ? 'green' : 'maroon'); ?>">
                    <?php echo (int) $numEnabled; ?>
                </b>
                <?php if (strlen($nextScheduleString) > 0) { ?>
                    - <?php esc_html_e('Next', 'duplicator-pro'); ?>: <b><?php echo esc_html($nextScheduleString); ?></b>
                <?php } ?>
            </span>
        </li>
        <?php
    }
}
