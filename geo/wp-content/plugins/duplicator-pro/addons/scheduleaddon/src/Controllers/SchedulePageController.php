<?php

/**
 * Schedule page controller
 */

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\Controllers;

use Duplicator\Addons\ScheduleAddon\Core\CapSchedule;
use Duplicator\Addons\ScheduleAddon\Core\LicenseSchedule;
use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Core\Controllers\ControllersManager;
use Duplicator\Core\Controllers\AbstractMenuPageController;
use Duplicator\Core\Controllers\PageAction;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\TemplateEntity;
use Duplicator\Package\PackageUtils;
use Duplicator\Views\PackageScreen;

class SchedulePageController extends AbstractMenuPageController
{
    const SCHEDULES_SUBMENU_SLUG = ControllersManager::MAIN_MENU_SLUG . '-schedules';

    const INNER_PAGE_LIST = 'schedules';
    const INNER_PAGE_EDIT = 'edit';

    /*
     * action types
     */
    const ACTION_EDIT_SAVE = 'save';
    const ACTION_EDIT_COPY = 'copy-schedule';

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $this->parentSlug   = ControllersManager::MAIN_MENU_SLUG;
        $this->pageSlug     = self::SCHEDULES_SUBMENU_SLUG;
        $this->pageTitle    = __('Schedule Backups', 'duplicator-pro');
        $this->menuLabel    = __('Schedule Backups', 'duplicator-pro');
        $this->capatibility = CapSchedule::CAP_SCHEDULE;
        $this->menuPos      = 30;

        add_filter('duplicator_page_actions_' . $this->pageSlug, [$this, 'pageActions']);
        add_filter('duplicator_page_template_data_' . $this->pageSlug, [$this, 'updatePackagePageTitle']);
        add_action('duplicator_render_page_content_' . $this->pageSlug, [$this, 'renderContent'], 10, 2);
        add_action('duplicator_before_render_page_' . $this->pageSlug, [$this, 'beforeRenderPage'], 10, 2);
    }

    /**
     * Return actions for current page
     *
     * @param PageAction[] $actions actions lists
     *
     * @return PageAction[]
     */
    public function pageActions($actions)
    {
        $actions[] = new PageAction(
            self::ACTION_EDIT_SAVE,
            [
                $this,
                'saveSchedule',
            ],
            [$this->pageSlug],
            self::INNER_PAGE_EDIT
        );
        $actions[] = new PageAction(
            self::ACTION_EDIT_COPY,
            [
                $this,
                'copySchedule',
            ],
            [$this->pageSlug],
            self::INNER_PAGE_EDIT
        );
        return $actions;
    }

    /**
     * Set Backup object before render pages
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page, empty if not set
     *
     * @return void
     */
    public function beforeRenderPage($currentLevelSlugs, $innerPage): void
    {
        TplMng::getInstance()->setGlobalValue('blur', !LicenseSchedule::can());
    }

    /**
     * Set Backup page title
     *
     * @param array<string, mixed> $renderData template global data
     *
     * @return array<string, mixed>
     */
    public function updatePackagePageTitle($renderData)
    {
        switch ($this->getCurrentInnerPage()) {
            case self::INNER_PAGE_EDIT:
                break;
            case self::INNER_PAGE_LIST:
            default:
                $renderData['pageTitle']             = __('Schedule Backup', 'duplicator-pro');
                $renderData['templateSecondaryPart'] = 'scheduleaddon/admin_pages/schedules/schedule_create_button';
                break;
        }
        return $renderData;
    }

    /**
     * Save schedule
     *
     * @return array<string, mixed>
     */
    public function saveSchedule(): array
    {
        $result = [
            'saveSuccess'      => false,
            'actionScheduleId' => SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'schedule_id', -1),
        ];

        $schedule_id = $result['actionScheduleId'];
        $schedule    = $schedule_id == -1 ? new ScheduleEntity() : ScheduleEntity::getById($schedule_id);

        if ($schedule == false) {
            $result['errorMessage'] = __('Schedule not found', 'duplicator-pro');
            return $result;
        }

        $wasActive = $schedule->isActive();
        $schedule->setFromInput(SnapUtil::INPUT_REQUEST);
        if (!$wasActive && $schedule->isActive() && PackageUtils::isBackupCreationBlocked($blockMessage)) {
            $result['errorMessage'] = $blockMessage;
            return $result;
        }

        if ($schedule->save() == false) {
            $result['errorMessage'] = __('It is not possible to update the schedule settings', 'duplicator-pro');
        } else {
            $result['saveSuccess']    = true;
            $result['successMessage'] = __('Schedule saved', 'duplicator-pro');
        }

        $result['actionScheduleId'] = $schedule->getId();

        return $result;
    }

    /**
     * Save schedule
     *
     * @return array<string, mixed>
     */
    public function copySchedule(): array
    {
        $result = [
            'saveSuccess'      => false,
            'actionScheduleId' => SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'schedule_id', -1),
        ];

        $schedule_id = $result['actionScheduleId'];
        $source_id   = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'dupli-source-schedule-id', -1);

        if ($source_id == -1 || !ScheduleEntity::exists($source_id)) {
            $result['errorMessage'] = __('Schedule to copy not found', 'duplicator-pro');
            return $result;
        }

        $schedule = $schedule_id == -1 ? new ScheduleEntity() : ScheduleEntity::getById($schedule_id);

        if ($schedule == false) {
            $result['errorMessage'] = __('Schedule not found', 'duplicator-pro');
            return $result;
        }

        $wasActive = $schedule->isActive();
        $schedule->copyFromSourceId($source_id);
        if (!$wasActive && $schedule->isActive() && PackageUtils::isBackupCreationBlocked($blockMessage)) {
            $result['errorMessage'] = $blockMessage;
            return $result;
        }

        if ($schedule->save() == false) {
            $result['errorMessage'] = __('It is not possible to update the schedule settings', 'duplicator-pro');
        } else {
            $result['saveSuccess']    = true;
            $result['successMessage'] = __('Schedule copied.', 'duplicator-pro');
        }

        $result['actionScheduleId'] = $schedule->getId();

        return $result;
    }

    /**
     * Render page content
     *
     * @param string[] $currentLevelSlugs current menu slugs
     * @param string   $innerPage         current inner page, empty if not set
     *
     * @return void
     */
    public function renderContent($currentLevelSlugs, $innerPage): void
    {
        switch ($this->getCurrentInnerPage()) {
            case self::INNER_PAGE_EDIT:
                $scheduleId = TplMng::getInstance()->getGlobalValue(
                    'actionScheduleId',
                    SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'schedule_id', -1)
                );
                if ($scheduleId == -1) {
                    $schedule        = new ScheduleEntity();
                    $defaultTemplate = TemplateEntity::getDefaultTemplate();
                    if ($defaultTemplate !== null) {
                        $schedule->template_id = $defaultTemplate->getId();
                    }
                    $schedule->setActive(true); // Active by default
                } else {
                    $schedule = ScheduleEntity::getById($scheduleId);
                }
                TplMng::getInstance()->render(
                    'scheduleaddon/admin_pages/schedules/schedule_edit',
                    ['schedule' => $schedule]
                );
                break;
            case self::INNER_PAGE_LIST:
            default:
                $schedules = ScheduleEntity::getAll();
                $schedules = ($schedules === false ? [] : $schedules);

                $schedulesFlagsIcons = [];
                foreach ($schedules as $schedule) {
                    $schedulesFlagsIcons[$schedule->getId()] = self::getScheduleFlagsIcons($schedule);
                }

                TplMng::getInstance()->render(
                    'scheduleaddon/admin_pages/schedules/schedule_list',
                    [
                        'schedules'           => $schedules,
                        'schedulesFlagsIcons' => $schedulesFlagsIcons,
                    ]
                );
                break;
        }
    }

    /**
     * Get schedule edit URL
     *
     * @return string
     */
    public function getEditBaseUrl(): string
    {
        return ControllersManager::getMenuLink(
            self::SCHEDULES_SUBMENU_SLUG,
            null,
            null,
            [ControllersManager::QUERY_STRING_INNER_PAGE => 'edit']
        );
    }

    /**
     * Get schedule edit URL
     *
     * @param int $scheduleId schedule id, if -1 then new schedule will be created
     *
     * @return string
     */
    public function getEditUrl($scheduleId = -1): string
    {
        return ControllersManager::getMenuLink(
            self::SCHEDULES_SUBMENU_SLUG,
            null,
            null,
            [
                ControllersManager::QUERY_STRING_INNER_PAGE => 'edit',
                'schedule_id'                               => $scheduleId,
            ]
        );
    }

    /**
     * Get schedule copy action URL
     *
     * @param int $scheduleId schedule id, if -1 then new schedule will be created
     * @param int $sourceId   source schedule id, if -1 then source if ins't add on URL
     *
     * @return string
     */
    public function getCopyActionUrl($scheduleId = -1, $sourceId = -1): string
    {

        $action    = $this->getActionByKey(self::ACTION_EDIT_COPY);
        $extraArgs = [
            ControllersManager::QUERY_STRING_INNER_PAGE => 'edit',
            'action'                                    => $action->getKey(),
            '_wpnonce'                                  => $action->getNonce(),
            'schedule_id'                               => $scheduleId,
        ];
        if ($sourceId > -1) {
            $extraArgs['dupli-source-schedule-id'] = $sourceId;
        }
        return ControllersManager::getMenuLink(
            self::SCHEDULES_SUBMENU_SLUG,
            null,
            null,
            $extraArgs
        );
    }

    /**
     * Render storage delete confirmation schedule check
     *
     * @return void
     */
    public static function renderStorageDeleteScheduleCheck(): void
    {
        TplMng::getInstance()->render(
            'scheduleaddon/storage/delete_confirm_schedule_check',
            [
                'nonce' => wp_create_nonce('duplicator_schedule_get_storage_links'),
            ]
        );
    }

    /**
     * Build the flags cell icons of a schedule from its template and storage locality.
     *
     * @param ScheduleEntity $schedule The schedule
     *
     * @return array<int, array{icon: string, class: string, tooltip: string, tooltipTitle: string, onclick: string}>
     */
    public static function getScheduleFlagsIcons(ScheduleEntity $schedule): array
    {
        $locality = $schedule->getStorageLocality();
        return self::buildScheduleFlagsIcons($schedule->getTemplate(), $locality['local'], $locality['remote']);
    }

    /**
     * Build the flags cell icons of a schedule: the shape and security lock of its
     * template plus the local/remote storage icons, matching the Backups list icons.
     *
     * @param TemplateEntity|false $template         The schedule template, false when missing
     * @param bool                 $hasLocalStorage  True when the schedule saves to a local storage
     * @param bool                 $hasRemoteStorage True when the schedule saves to a remote storage
     *
     * @return array<int, array{icon: string, class: string, tooltip: string, tooltipTitle: string, onclick: string}>
     */
    public static function buildScheduleFlagsIcons($template, bool $hasLocalStorage, bool $hasRemoteStorage): array
    {
        if ($template === false) {
            return [];
        }

        $templateIcons = PackageScreen::getTemplateFlagsIcons($template);

        $icons   = [];
        $icons[] = $templateIcons['shape'];
        if ($hasLocalStorage) {
            $icons[] = [
                'icon'         => 'fa-solid fa-hard-drive',
                'class'        => '',
                'tooltip'      => esc_attr__('The schedule saves Backups to a local storage', 'duplicator-pro'),
                'tooltipTitle' => '',
                'onclick'      => '',
            ];
        }
        if ($hasRemoteStorage) {
            $icons[] = [
                'icon'         => 'fa-solid fa-cloud',
                'class'        => '',
                'tooltip'      => esc_attr__('The schedule saves Backups to a remote storage', 'duplicator-pro'),
                'tooltipTitle' => '',
                'onclick'      => '',
            ];
        }
        $icons[] = $templateIcons['security'];

        return $icons;
    }

    /**
     * Build the legend entries of the schedule flags icons: the template entries plus
     * the local/remote storage entries, filtered for addon entries.
     *
     * @return array<int, array{icon: string, label: string, section: string}>
     */
    public static function getScheduleFlagsLegendEntries(): array
    {
        $entries   = PackageScreen::getTemplateFlagsLegendEntries();
        $entries[] = [
            'icon'    => '<i class="fa-solid fa-hard-drive"></i>',
            'label'   => __('The schedule saves Backups to a local storage', 'duplicator-pro'),
            'section' => PackageScreen::FLAGS_SECTION_STORAGE,
        ];
        $entries[] = [
            'icon'    => '<i class="fa-solid fa-cloud"></i>',
            'label'   => __('The schedule saves Backups to a remote storage', 'duplicator-pro'),
            'section' => PackageScreen::FLAGS_SECTION_STORAGE,
        ];

        /**
         * Filter the Schedules list flags legend entries. Addons append their own;
         * a missing `section` key places the entry in the last section.
         *
         * @param array<int, array{icon: string, label: string, section?: string}> $entries
         */
        return apply_filters('duplicator_schedule_flags_legend', $entries);
    }
}
