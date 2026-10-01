<?php



defined("ABSPATH") or die("");

use Duplicator\Addons\ScheduleAddon\Controllers\ScheduleSettingsController;
use Duplicator\Addons\ScheduleAddon\Settings\ScheduleSettings;
use Duplicator\Core\Controllers\ControllersManager;

/**
 * Variables
 *
 * @var Duplicator\Core\Controllers\ControllersManager $ctrlMng
 * @var Duplicator\Core\Views\TplMng $tplMng
 */

$emailBuildMode           = $tplMng->getDataValueIntRequired('emailBuildMode');
$notificationEmailAddress = $tplMng->getDataValueStringRequired('notificationEmailAddress');
?>
<form
    id="dup-settings-form"
    action="<?php echo esc_attr(ControllersManager::getCurrentLink()); ?>"
    method="post" data-parsley-validate>
    <?php $tplMng->getAction(ScheduleSettingsController::ACTION_SAVE_SCHEDULE)->getActionNonceFileds(); ?>
    <h3 class="title"><?php esc_html_e("Schedule Notifications", 'duplicator-pro'); ?></h3>
    <hr size="1" />
    <div class="dup-settings-wrapper margin-bottom-1">
        <label class="lbl-larger" for="send_email_on_build_mode">
            <?php esc_html_e("Send Build Email", 'duplicator-pro'); ?>
        </label>
        <div class="margin-bottom-1">
            <input
                type="radio"
                name="send_email_on_build_mode"
                id="send_email_on_build_mode_never"
                class="margin-0"
                value="<?php echo (int) ScheduleSettings::EMAIL_BUILD_MODE_NEVER; ?>"
                <?php checked($emailBuildMode, ScheduleSettings::EMAIL_BUILD_MODE_NEVER); ?>>
            <label for="send_email_on_build_mode_never"><?php esc_attr_e("Never", 'duplicator-pro'); ?></label> &nbsp;
            <input
                type="radio"
                name="send_email_on_build_mode"
                id="send_email_on_build_mode_failure"
                class="margin-0"
                value="<?php echo (int) ScheduleSettings::EMAIL_BUILD_MODE_FAILURE; ?>"
                <?php checked($emailBuildMode, ScheduleSettings::EMAIL_BUILD_MODE_FAILURE); ?>>
            <label for="send_email_on_build_mode_failure"><?php esc_attr_e("On Failure", 'duplicator-pro'); ?></label> &nbsp;
            <input
                type="radio"
                name="send_email_on_build_mode"
                id="send_email_on_build_mode_always"
                class="margin-0"
                value="<?php echo (int) ScheduleSettings::EMAIL_BUILD_MODE_ALL; ?>"
                <?php checked($emailBuildMode, ScheduleSettings::EMAIL_BUILD_MODE_ALL); ?>>
            <label for="send_email_on_build_mode_always"><?php esc_attr_e("Always", 'duplicator-pro'); ?></label> &nbsp;
            <p class="description">
                <?php
                esc_html_e("When to send emails after a scheduled build.", 'duplicator-pro');
                ?>
            </p>
        </div>

        <label class="lbl-larger" for="notification_email_address">
            <?php esc_html_e("Email Address", 'duplicator-pro'); ?>
        </label>
        <div class="margin-bottom-1">
            <input
                data-parsley-errors-container="#notification_email_address_error_container"
                data-parsley-type="email"
                type="email"
                name="notification_email_address"
                id="notification_email_address"
                class="width-large margin-bottom-0"
                value="<?php echo esc_attr($notificationEmailAddress); ?>">
            <p class="description">
                <?php
                if (is_multisite()) {
                    esc_html_e('Network administrator email will be used if empty.', 'duplicator-pro');
                } else {
                    esc_html_e('Administrator default email will be used if empty.', 'duplicator-pro');
                }
                ?>
            </p>
            <div id="notification_email_address_error_container" class="duplicator-error-container"></div>
        </div>
    </div>
    <hr>
    <p class="submit dupli-save-submit">
        <input
            type="submit"
            name="submit"
            id="submit"
            class="button primary small"
            value="<?php esc_attr_e('Save Settings', 'duplicator-pro') ?>">
    </p>
</form>