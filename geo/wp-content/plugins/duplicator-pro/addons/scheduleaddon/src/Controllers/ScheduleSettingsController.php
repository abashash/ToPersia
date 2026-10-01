<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\Controllers;

use Duplicator\Addons\ScheduleAddon\Settings\ScheduleSettings;
use Duplicator\Controllers\SettingsPageController;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Controllers\SubMenuItem;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\ActivityLog\LogEventSettingsChange;
use Duplicator\Utils\ActivityLog\SettingsChangeTracker;

/**
 * Handles the Schedule tab in Settings page
 */
class ScheduleSettingsController
{
    const L2_SLUG_SCHEDULE     = 'schedule';
    const ACTION_SAVE_SCHEDULE = 'save-schedule';

    /**
     * Register hooks for settings page integration
     *
     * @return void
     */
    public static function register(): void
    {
        $pageSlug = ControllersManager::SETTINGS_SUBMENU_SLUG;

        add_filter('duplicator_sub_menu_items_' . $pageSlug, [self::class, 'addSubMenu']);
        add_filter('duplicator_page_actions_' . $pageSlug, [self::class, 'addPageActions']);
        add_action('duplicator_render_page_content_' . $pageSlug, [self::class, 'renderContent'], 10, 2);
        add_filter('duplicator_settings_change_sub_type_labels', [self::class, 'addSettingsChangeLabel']);
        add_filter('duplicator_capability_labels', [self::class, 'addSettingsLabels']);
    }

    /**
     * Add schedule submenu to settings page
     *
     * @param SubMenuItem[] $subMenus Existing sub menus
     *
     * @return SubMenuItem[]
     */
    public static function addSubMenu(array $subMenus): array
    {
        $subMenus[] = new SubMenuItem(self::L2_SLUG_SCHEDULE, __('Schedules', 'duplicator-pro'), '', true, 40);
        return $subMenus;
    }

    /**
     * Add schedule save action to settings page
     *
     * @param PageAction[] $actions Existing actions
     *
     * @return PageAction[]
     */
    public static function addPageActions(array $actions): array
    {
        $actions[] = new PageAction(
            self::ACTION_SAVE_SCHEDULE,
            [
                self::class,
                'saveSchedule',
            ],
            [
                ControllersManager::SETTINGS_SUBMENU_SLUG,
                self::L2_SLUG_SCHEDULE,
            ]
        );

        return $actions;
    }

    /**
     * Render schedule settings content
     *
     * @param string[] $currentLevelSlugs Current menu slugs
     * @param string   $innerPage         Current inner page
     *
     * @return void
     */
    public static function renderContent(array $currentLevelSlugs, string $innerPage): void
    {
        if (($currentLevelSlugs[1] ?? '') !== self::L2_SLUG_SCHEDULE) {
            return;
        }

        TplMng::getInstance()->render(
            'scheduleaddon/admin_pages/settings/schedule/schedule',
            [
                'emailBuildMode'           => ScheduleSettings::getEmailBuildMode(),
                'notificationEmailAddress' => ScheduleSettings::getNotificationEmailAddress(),
            ]
        );
    }

    /**
     * Save schedule settings
     *
     * @return array<string, mixed>
     */
    public static function saveSchedule(): array
    {
        $result        = ['saveSuccess' => false];
        $changesTraker = new SettingsChangeTracker();

        // Track email build mode changes
        $newEmailBuildMode = SnapUtil::sanitizeIntInput(
            SnapUtil::INPUT_REQUEST,
            ScheduleSettings::SEND_EMAIL_ON_BUILD_MODE_KEY,
            ScheduleSettings::EMAIL_BUILD_MODE_FAILURE
        );
        if (
            !in_array(
                $newEmailBuildMode,
                [
                    ScheduleSettings::EMAIL_BUILD_MODE_NEVER,
                    ScheduleSettings::EMAIL_BUILD_MODE_FAILURE,
                    ScheduleSettings::EMAIL_BUILD_MODE_ALL,
                ],
                true
            )
        ) {
            $newEmailBuildMode = ScheduleSettings::EMAIL_BUILD_MODE_FAILURE;
        }
        $emailBuildModeOptions = [
            ScheduleSettings::EMAIL_BUILD_MODE_NEVER   => __('Never', 'duplicator-pro'),
            ScheduleSettings::EMAIL_BUILD_MODE_FAILURE => __('On Failure', 'duplicator-pro'),
            ScheduleSettings::EMAIL_BUILD_MODE_ALL     => __('Always', 'duplicator-pro'),
        ];
        $changesTraker->addChange(
            ScheduleSettings::SEND_EMAIL_ON_BUILD_MODE_KEY,
            ScheduleSettings::getEmailBuildMode(),
            $newEmailBuildMode,
            'optionChanged',
            $emailBuildModeOptions
        );
        ScheduleSettings::setEmailBuildMode($newEmailBuildMode, false);

        // Track notification email address changes
        $newEmailAddress = sanitize_email(
            SnapUtil::sanitizeTextInput(
                SnapUtil::INPUT_REQUEST,
                ScheduleSettings::NOTIFICATION_EMAIL_ADDRESS_KEY,
                ''
            )
        );
        $changesTraker->addChange(
            ScheduleSettings::NOTIFICATION_EMAIL_ADDRESS_KEY,
            ScheduleSettings::getNotificationEmailAddress(),
            $newEmailAddress,
            'fieldChanged'
        );

        if (ScheduleSettings::setNotificationEmailAddress($newEmailAddress)) {
            $result['saveSuccess']    = true;
            $result['successMessage'] = __('Schedule settings updated.', 'duplicator-pro');

            $changesTraker->createLog('schedule');
        } else {
            $result['errorMessage'] = __('Can\'t update schedule settings.', 'duplicator-pro');
        }

        return $result;
    }

    /**
     * Add schedule setting labels to the shared activity-label registry.
     *
     * @param array<string, string> $labels Existing labels
     *
     * @return array<string, string>
     */
    public static function addSettingsLabels(array $labels): array
    {
        $labels[ScheduleSettings::SEND_EMAIL_ON_BUILD_MODE_KEY]   = __('Send Email On Build Mode', 'duplicator-pro');
        $labels[ScheduleSettings::NOTIFICATION_EMAIL_ADDRESS_KEY] = __('Notification Email Address', 'duplicator-pro');

        return $labels;
    }

    /**
     * Add schedule label to settings change sub-type labels
     *
     * @param array<string, string> $labels Existing labels
     *
     * @return array<string, string>
     */
    public static function addSettingsChangeLabel(array $labels): array
    {
        $labels['schedule'] = __('Schedule', 'duplicator-pro');

        return $labels;
    }
}
