<?php



defined("ABSPATH") or die("");

use Duplicator\Addons\ScheduleAddon\Controllers\SchedulePageController;
use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Models\Storages\StoragesUtil;

/**
 * Variables
 *
 * @var \Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var \Duplicator\Core\Views\TplMng  $tplMng
 */

$blur     = $tplMng->getDataValueBool('blur');
$schedule = $tplMng->getDataValueObjRequired('schedule', ScheduleEntity::class);

?>
<form
    id="dup-schedule-form"
    class="dup-monitored-form <?php echo ($blur ? 'dup-mock-blur' : ''); ?>"
    action="<?php echo esc_url(ControllersManager::getCurrentLink()); ?>"
    method="post"
    data-parsley-ui-enabled="true">
    <?php $tplMng->render('scheduleaddon/admin_pages/schedules/parts/edit_toolbar'); ?>
    <?php $tplMng->getAction(SchedulePageController::ACTION_EDIT_SAVE)->getActionNonceFileds(); ?>
    <input type="hidden" name="schedule_id" value="<?php echo (int) $schedule->getId(); ?>">

    <!-- ===============================
    SETTINGS -->
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><label><?php esc_html_e('Schedule Name', 'duplicator-pro'); ?></label></th>
            <td>
                <input
                    type="text"
                    id="schedule-name"
                    class="width-medium"
                    name="name"
                    value="<?php echo esc_attr($schedule->name); ?>"
                    required data-parsley-group="standard"
                    autocomplete="off">
            </td>
        </tr>
        <input type="hidden" id="schedule-template-id" name="template_id" value="<?php echo (int) $schedule->template_id; ?>">
        <?php do_action('duplicator_schedule_edit_after_name', $schedule); ?>
        <tr>
            <th scope="row"><label><?php esc_html_e('Storage', 'duplicator-pro'); ?></label></th>
            <td>
                <!-- ===============================
                STORAGE -->
                <?php $tplMng->render(
                    'parts/storage/select_list',
                    [
                        'selectListData' => StoragesUtil::buildSelectListData([], $schedule->storage_ids),
                        'showAddNew'     => false,
                    ]
                ); ?>
            </td>
        </tr>
        <tr valign="top">
            <td colspan="2">
                <?php
                $tplMng->render(
                    'scheduleaddon/admin_pages/schedules/parts/repeats_options',
                    [
                        'repeat_type'  => $schedule->repeat_type,
                        'run_every'    => $schedule->run_every,
                        'day_of_month' => $schedule->day_of_month,
                        'weekdays'     => [
                            'mon' => $schedule->isDaySet('mon'),
                            'tue' => $schedule->isDaySet('tue'),
                            'wed' => $schedule->isDaySet('wed'),
                            'thu' => $schedule->isDaySet('thu'),
                            'fri' => $schedule->isDaySet('fri'),
                            'sat' => $schedule->isDaySet('sat'),
                            'sun' => $schedule->isDaySet('sun'),
                        ],
                        'start_hour'   => $schedule->getStartTimePiece(0),
                        'start_minute' => $schedule->getStartTimePiece(1),
                    ]
                );
                ?>
            </td>
        </tr>
        <?php do_action('duplicator_schedule_edit_extra_info', $schedule); ?>
        <tr valign="top">
            <th scope="row"><label for="schedule-active"><?php esc_html_e('Activated', 'duplicator-pro'); ?></label></th>
            <td>
                <input name="_active" id="schedule-active" type="checkbox" <?php checked($schedule->isActive()); ?> class="margin-0">
                <label for="schedule-active"><?php esc_html_e('Enable This Schedule', 'duplicator-pro'); ?></label><br />
                <i class="dupli-edit-info"> <?php esc_html_e('When checked this schedule will run', 'duplicator-pro'); ?></i>
            </td>
        </tr>
    </table><br />
    <button
        id="dupli-save-schedule"
        class="button primary small"
        type="submit">
        <?php esc_html_e('Save Schedule', 'duplicator-pro'); ?>
    </button>
</form>

<script>
    jQuery(document).ready(function($) {
        $('#dup-schedule-form').parsley({
            excluded: ':disabled'
        });

        jQuery('#schedule-name').focus().select();
    });
</script>
