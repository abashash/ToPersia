<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon;

use Duplicator\Addons\ScheduleAddon\Ajax\ServicesSchedule;
use Duplicator\Addons\ScheduleAddon\AutoTune\AutoTuneScheduleState;
use Duplicator\Addons\ScheduleAddon\Controllers\SchedulePageController;
use Duplicator\Addons\ScheduleAddon\Controllers\ScheduleSettingsController;
use Duplicator\Addons\ScheduleAddon\Core\CapSchedule;
use Duplicator\Addons\ScheduleAddon\Core\LicenseSchedule;
use Duplicator\Addons\ScheduleAddon\Models\ActivityLog\LogEventSchedule;
use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Addons\ScheduleAddon\Models\SchedulePackageRelation;
use Duplicator\Addons\ScheduleAddon\Package\SchedulePackageHandler;
use Duplicator\Addons\ScheduleAddon\Settings\ScheduleSettings;
use Duplicator\Addons\ProBase\License\DupliLicenseException;
use Duplicator\Core\Addons\AbstractAddonCore;
use Duplicator\Core\Bootstrap;
use Duplicator\Core\Controllers\AbstractMenuPageController;
use Duplicator\Core\MigrationMng;
use Duplicator\Core\Views\TplMng;
use Duplicator\Models\ActivityLog\LogUtils;
use Duplicator\Package\AbstractPackage;
use Duplicator\Package\AutoTune\AutoTuneManager;
use Duplicator\Package\AutoTune\AutoTuneSessionEntity;
use Duplicator\Utils\Logging\DupLog;
use Duplicator\Views\PackageScreen;
use Exception;

/**
 * Schedule addon class
 *
 * Adds scheduled backup support: create, manage, and execute
 * automated backup schedules with cron-based processing.
 */
class ScheduleAddon extends AbstractAddonCore
{
    const ADDON_PATH = __DIR__;

    const FLAG_SCHEDULE         = 'SCHEDULE';
    const FLAG_SCHEDULE_RUN_NOW = 'SCHEDULE_RUN_NOW';

    /**
     * Initialize the addon
     *
     * @return void
     */
    public function init(): void
    {
        ScheduleSettings::registerDefaults();
        add_filter('duplicator_dynamic_data_skip_reset', [AutoTuneScheduleState::class, 'appendRuntimeSkipKey']);
        add_filter('duplicator_dynamic_skip_data_export', [AutoTuneScheduleState::class, 'appendRuntimeSkipKey']);
        CapSchedule::register();
        ScheduleSettingsController::register();

        add_action('duplicator_register_types', [ScheduleEntity::class, 'registerType']);

        // Page registration
        add_filter('duplicator_menu_pages', [$this, 'addMenuPage']);

        // Template resolution
        add_filter('duplicator_template_file', [self::class, 'getTemplateFile'], 10, 2);

        // Package flags
        add_filter('duplicator_package_update_flags', [self::class, 'setScheduleFlags'], 10, 2);
        add_filter('duplicator_package_origin_group', [self::class, 'resolveOriginGroup'], 10, 2);
        add_filter('duplicator_package_flags_legend', [self::class, 'addLegendEntries']);

        // Package type string + scheduled check
        add_filter('duplicator_package_type_string', [self::class, 'resolvePackageTypeString'], 10, 2);

        // License check for scheduled builds
        add_action('duplicator_build_start', [self::class, 'checkScheduleLicense']);

        // License expired notice: schedule DRM message
        add_action('duplicator_license_expired_features_list', [self::class, 'renderExpiredScheduleMessage']);

        // Schedule processing
        add_action('duplicator_runner_pre_process', [ScheduleProcessor::class, 'processSchedules']);

        // Post-build processing
        add_action('duplicator_package_after_set_status', [SchedulePackageHandler::class, 'onStatusChange'], 10, 2);

        // Activity log
        add_action('duplicator_register_activity_log_types', [self::class, 'registerLogTypes']);
        add_filter('duplicator_backup_create_log_data', [self::class, 'addScheduleNameToLogData'], 10, 2);
        add_action('duplicator_backup_create_log_extra_fields', [self::class, 'renderScheduleNameLogField']);

        // Dashboard widget
        add_action('duplicator_dashboard_widget_sections', [ScheduleDashboardWidget::class, 'renderSection']);

        // Plugin deactivation
        add_action('duplicator_after_deactivation', [self::class, 'deactivateAllSchedules']);

        // AutoTune session lifecycle: schedules are suspended while a session runs
        add_action(AutoTuneManager::BEFORE_START_ACTION, [self::class, 'onAutoTuneStart']);
        add_action(AutoTuneManager::AFTER_STOP_ACTION, [self::class, 'onAutoTuneStop']);

        // Post-migration
        add_action(MigrationMng::HOOK_FIRST_LOGIN_AFTER_INSTALL, [self::class, 'updateNextSchedules']);

        // Upgrade
        add_action('duplicator_upgrade', [self::class, 'createSchedulePackageTable'], 10, 4);
        add_action('duplicator_upgrade', [self::class, 'resaveAllSchedules'], 10001, 4);

        // NameFormat
        add_filter('duplicator_name_format_result', [self::class, 'resolveScheduleNameTag']);
        add_filter('duplicator_name_format_tags', [self::class, 'registerNameFormatTag']);

        // Email summary
        add_filter('duplicator_email_summary_new_entity_messages', [ScheduleEmailIntegration::class, 'addNewEntityMessage']);
        add_action('duplicator_email_summary_extra_sections', [ScheduleEmailIntegration::class, 'renderEmailSummarySection']);
        add_action('duplicator_after_schedule_create', [ScheduleEmailIntegration::class, 'onScheduleCreate']);
        add_action('duplicator_after_schedule_delete', [ScheduleEmailIntegration::class, 'onScheduleDelete']);

        // Package deletion cleanup
        add_action('duplicator_package_after_delete', function (int $id): void {
            SchedulePackageRelation::deleteByPackageId($id);
        });
        add_action('duplicator_packages_after_bulk_delete', function (array $ids): void {
            SchedulePackageRelation::deleteByPackageIds($ids);
        });

        // Storage cleanup + delete confirm UI
        add_action('duplicator_storage_after_delete_cleanup', [self::class, 'cleanupStorageFromSchedules']);
        add_action('duplicator_after_storage_list', [SchedulePageController::class, 'renderStorageDeleteScheduleCheck']);

        // DRM active tasks check
        add_filter('duplicator_has_active_automated_tasks', [self::class, 'hasActiveSchedules']);

        // Settings export/import
        add_filter('duplicator_settings_export_data', [self::class, 'addSettingsExportData']);
        add_filter('duplicator_settings_import_opts', [self::class, 'addSettingsImportDependencies']);
        add_action('duplicator_settings_import_data', [self::class, 'importSettingsData'], 10, 5);
        add_action('duplicator_import_settings_checkboxes', [self::class, 'renderImportCheckbox']);

        // Help context
        add_filter('duplicator_help_page_context', [self::class, 'addHelpContext'], 10, 3);

        // Capability labels
        add_filter('duplicator_capability_labels', [self::class, 'addCapabilityLabels']);

        // Disable schedule capability on staging sites
        add_filter('duplicator_staging_disabled_caps', [self::class, 'addStagingDisabledCaps']);

        // Upgrade entity classes
        add_filter('duplicator_upgrade_entity_classes', [self::class, 'addUpgradeEntityClasses']);

        // Usage stats
        ScheduleUsageStats::register();

        // Telemetry: enrich backup_* events with execution_type
        ScheduleTelemetryIntegration::register();

        // AJAX services
        (new ServicesSchedule())->init();
    }

    /**
     * Add schedule page to the menu
     *
     * @param AbstractMenuPageController[] $pages Menu pages
     *
     * @return AbstractMenuPageController[]
     */
    public function addMenuPage(array $pages): array
    {
        $pages[]  = SchedulePageController::getInstance();
        $pageSlug = SchedulePageController::getInstance()->getMenuHookSuffix();

        add_action('admin_print_scripts-' . $pageSlug, [Bootstrap::class, 'enqueueScripts']);
        add_action('admin_print_styles-' . $pageSlug, [Bootstrap::class, 'enqueueStyles']);

        return $pages;
    }

    /**
     * Return template file path for addon templates
     *
     * @param string $path    Current path
     * @param string $slugTpl Template slug
     *
     * @return string
     */
    public static function getTemplateFile(string $path, string $slugTpl): string
    {
        if (strpos($slugTpl, 'scheduleaddon/') === 0) {
            return self::getAddonPath() . '/template/' . $slugTpl . '.php';
        }

        return $path;
    }

    /**
     * Register activity log types for schedule events
     *
     * @return void
     */
    public static function registerLogTypes(): void
    {
        LogUtils::registerLogType(LogEventSchedule::getType(), LogEventSchedule::class);
    }

    /**
     * Render schedule DRM message in license expired notice
     *
     * @return void
     */
    public static function renderExpiredScheduleMessage(): void
    {
        ?>
        <li><?php TplMng::getInstance()->render('scheduleaddon/licensing/drm_schedules_msg'); ?></li>
        <?php
    }

    /**
     * Add schedule name to backup create activity log data
     *
     * @param array<string, mixed> $data    Log data
     * @param AbstractPackage      $package The package
     *
     * @return array<string, mixed>
     */
    public static function addScheduleNameToLogData(array $data, AbstractPackage $package): array
    {
        $relation = SchedulePackageRelation::getByPackageId($package->getId());
        if ($relation !== null) {
            $data['scheduleName'] = $relation['schedule_name'];
        }

        return $data;
    }

    /**
     * Render schedule name field in backup create activity log
     *
     * @param array<string, mixed> $data Log data
     *
     * @return void
     */
    public static function renderScheduleNameLogField(array $data): void
    {
        if (empty($data['scheduleName'])) {
            return;
        }
        ?>
        <div class="dup-log-type-wrapper">
            <strong><?php esc_html_e('Schedule Name:', 'duplicator-pro'); ?></strong>
            <span class="dup-log-type">
                <?php echo esc_html($data['scheduleName']); ?>
            </span>
        </div>
        <?php
    }

    /**
     * Deactivate all schedules on plugin deactivation
     *
     * Resets next_run_time to prevent orphaned cron executions.
     *
     * @return void
     */
    public static function deactivateAllSchedules(): void
    {
        $activeSchedules = ScheduleEntity::getActive();
        foreach ($activeSchedules as $schedule) {
            $schedule->next_run_time = -1;
            $schedule->save();
        }
    }

    /**
     * Deactivate the active schedules when an AutoTune session starts and
     * remember them in the addon storage.
     *
     * @return void
     */
    public static function onAutoTuneStart(): void
    {
        $deactivated = AutoTuneScheduleState::getDeactivatedSchedules();
        $failedIds   = [];
        foreach (ScheduleEntity::getActive() as $schedule) {
            $schedule->setActive(false);
            if ($schedule->save()) {
                $deactivated[$schedule->getId()] = $schedule->name;
            } else {
                $failedIds[] = $schedule->getId();
                DupLog::trace("AUTOTUNE: cannot deactivate schedule {$schedule->getId()}");
            }
        }

        if (!AutoTuneScheduleState::setDeactivatedSchedules($deactivated)) {
            throw new Exception('Cannot persist the schedules deactivated by AutoTune.');
        }
        if ($failedIds !== []) {
            throw new Exception('Cannot deactivate every active schedule before AutoTune starts.');
        }
    }

    /**
     * Reactivate schedules after an AutoTune session stops.
     *
     * @param AutoTuneSessionEntity $session The closed session
     *
     * @return void
     */
    public static function onAutoTuneStop(AutoTuneSessionEntity $session): void
    {
        $deactivated = AutoTuneScheduleState::getDeactivatedSchedules();
        $remaining   = [];
        foreach ($deactivated as $scheduleId => $scheduleName) {
            $schedule = ScheduleEntity::getById($scheduleId);
            if ($schedule === false) {
                continue;
            }
            $schedule->setActive(true);
            if (!$schedule->isActive() || !$schedule->save()) {
                $remaining[$scheduleId] = $scheduleName;
                DupLog::trace("AUTOTUNE: cannot reactivate schedule {$scheduleId}");
            }
        }

        if (!AutoTuneScheduleState::setDeactivatedSchedules($remaining)) {
            DupLog::trace('AUTOTUNE: cannot update the schedules pending reactivation.');
        }
    }

    /**
     * Recalculate active schedules next run time after migration
     *
     * @return void
     */
    public static function updateNextSchedules(): void
    {
        $schedules = ScheduleEntity::getActive();
        foreach ($schedules as $schedule) {
            $schedule->updateNextRuntime();
        }
    }

    /**
     * Create the schedule_packages table during upgrade
     *
     * @param string       $oldVariant previous variant identifier, '' on fresh install
     * @param false|string $oldVersion previous version, false on fresh install
     * @param string       $newVariant current variant
     * @param string       $newVersion current version
     *
     * @return void
     */
    public static function createSchedulePackageTable($oldVariant, $oldVersion, $newVariant, $newVersion): void
    {
        SchedulePackageRelation::createTable();
    }

    /**
     * Resave all schedule entities on upgrade
     *
     * Runs at priority 10001, after core resaveAllEntities at 10000.
     *
     * @param string       $oldVariant previous variant identifier, '' on fresh install
     * @param false|string $oldVersion previous version, false on fresh install
     * @param string       $newVariant current variant
     * @param string       $newVersion current version
     *
     * @return void
     */
    public static function resaveAllSchedules($oldVariant, $oldVersion, $newVariant, $newVersion): void
    {
        $schedules = ScheduleEntity::getAll();
        if ($schedules === false) {
            return;
        }

        foreach ($schedules as $schedule) {
            $schedule->save();
        }
    }

    /**
     * Resolve the %schedulename% placeholder in name format
     *
     * @param string $parsed The partially parsed name format string
     *
     * @return string
     */
    public static function resolveScheduleNameTag(string $parsed): string
    {
        if (strpos($parsed, '%schedulename%') === false) {
            return $parsed;
        }

        $schedule = ScheduleProcessor::getCurrentSchedule();
        $name     = ($schedule !== null) ? $schedule->name : '';

        return str_replace('%schedulename%', sanitize_file_name($name), $parsed);
    }

    /**
     * Register the schedulename tag description for the name format UI
     *
     * @param array<string, string> $tags Existing name format tags
     *
     * @return array<string, string>
     */
    public static function registerNameFormatTag(array $tags): array
    {
        $tags['schedulename'] = __('Backup Schedule Name', 'duplicator-pro');

        return $tags;
    }

    /**
     * Return true if there are active schedules
     *
     * @param bool $hasActive Current value
     *
     * @return bool
     */
    public static function hasActiveSchedules(bool $hasActive): bool
    {
        if ($hasActive) {
            return true;
        }

        return count(ScheduleEntity::getActive()) > 0;
    }

    /**
     * Set schedule flags on package via filter
     *
     * @param string[]        $flags   Current flags array
     * @param AbstractPackage $package The package
     *
     * @return string[]
     */
    public static function setScheduleFlags(array $flags, AbstractPackage $package): array
    {
        // Past early phase: nothing to do
        if ($package->getStatus() > AbstractPackage::STATUS_PRE_PROCESS) {
            return $flags;
        }

        $flags = array_values(array_diff($flags, [self::FLAG_SCHEDULE, self::FLAG_SCHEDULE_RUN_NOW]));

        switch ($package->getExecutionType()) {
            case ScheduleEntity::EXECUTION_TYPE_SCHEDULED:
                $flags[] = self::FLAG_SCHEDULE;
                break;
            case ScheduleEntity::EXECUTION_TYPE_RUN_NOW:
                $flags[] = self::FLAG_SCHEDULE_RUN_NOW;
                break;
        }

        return $flags;
    }

    /**
     * The schedule execution type of a package, or null when a schedule did not create it.
     * Rows stored before execution metadata existed are recognized by their persisted flags.
     *
     * @param AbstractPackage $package The package
     *
     * @return ?string ScheduleEntity::EXECUTION_TYPE_SCHEDULED, ScheduleEntity::EXECUTION_TYPE_RUN_NOW or null
     */
    private static function getScheduleExecutionType(AbstractPackage $package): ?string
    {
        $type = $package->getExecutionType();
        if ($type === ScheduleEntity::EXECUTION_TYPE_SCHEDULED || $type === ScheduleEntity::EXECUTION_TYPE_RUN_NOW) {
            return $type;
        }
        if ($type !== AbstractPackage::EXECUTION_TYPE_MANUAL) {
            return null;
        }
        if ($package->hasFlag(self::FLAG_SCHEDULE)) {
            return ScheduleEntity::EXECUTION_TYPE_SCHEDULED;
        }
        if ($package->hasFlag(self::FLAG_SCHEDULE_RUN_NOW)) {
            return ScheduleEntity::EXECUTION_TYPE_RUN_NOW;
        }

        return null;
    }

    /**
     * Resolve the origin group (icon and label) of schedule-created packages; other
     * packages pass through untouched.
     *
     * @param ?array{icon: string, label: string, details: string} $origin  Origin resolved so far
     * @param AbstractPackage                                      $package The package
     *
     * @return ?array{icon: string, label: string, details: string}
     */
    public static function resolveOriginGroup(?array $origin, AbstractPackage $package): ?array
    {
        switch (self::getScheduleExecutionType($package)) {
            case ScheduleEntity::EXECUTION_TYPE_SCHEDULED:
                $label = esc_html__('Schedule Backup', 'duplicator-pro');
                break;
            case ScheduleEntity::EXECUTION_TYPE_RUN_NOW:
                $label = esc_html__('Schedule Backup (Run Now)', 'duplicator-pro');
                break;
            default:
                return $origin;
        }

        $scheduleName = $package->getExecutionSource();

        return [
            'icon'    => 'fa-solid fa-clock',
            'label'   => $label,
            'details' => $scheduleName === null ? '' : esc_html(sprintf(
                /* translators: %s: schedule name */
                __('Schedule: %s', 'duplicator-pro'),
                $scheduleName
            )),
        ];
    }

    /**
     * Append schedule-related entries to the Status column legend.
     *
     * @param array<int, array{icon: string, label: string, section?: string}> $entries Existing legend entries
     *
     * @return array<int, array{icon: string, label: string, section?: string}>
     */
    public static function addLegendEntries(array $entries): array
    {
        $entries[] = [
            'icon'    => '<i class="fa-solid fa-clock"></i>',
            'label'   => __('Schedule Backup (scheduled or Run Now)', 'duplicator-pro'),
            'section' => PackageScreen::FLAGS_SECTION_ORIGIN,
        ];
        return $entries;
    }

    /**
     * Resolve the package type string for schedule packages
     *
     * @param string          $typeString Current type string
     * @param AbstractPackage $package    The package
     *
     * @return string
     */
    public static function resolvePackageTypeString(string $typeString, AbstractPackage $package): string
    {
        switch (self::getScheduleExecutionType($package)) {
            case ScheduleEntity::EXECUTION_TYPE_SCHEDULED:
                return __('Schedule', 'duplicator-pro');
            case ScheduleEntity::EXECUTION_TYPE_RUN_NOW:
                return __('Schedule (Run Now)', 'duplicator-pro');
            default:
                return $typeString;
        }
    }

    /**
     * Check schedule license before build starts
     *
     * @param AbstractPackage $package The package
     *
     * @return void
     *
     * @throws DupliLicenseException If not licensed for schedules
     */
    public static function checkScheduleLicense(AbstractPackage $package): void
    {
        // Cron-driven runs are blocked without a license, Run Now is a user
        // action and stays allowed.
        if ($package->getExecutionType() === ScheduleEntity::EXECUTION_TYPE_SCHEDULED && !LicenseSchedule::can()) {
            throw new DupliLicenseException(
                "Can't process package schedule " . $package->getId() . " because Duplicator isn't licensed",
                DupliLicenseException::CODE_SCHEDULE_LICENSE_BLOCKED,
                __(
                    'The scheduled backup was blocked because the Duplicator license is expired or invalid.
                    Renew the license to resume scheduled backups.',
                    'duplicator-pro'
                )
            );
        }
    }

    /**
     * Remove deleted storage from all schedules
     *
     * @param int $storageId The deleted storage ID
     *
     * @return void
     */
    public static function cleanupStorageFromSchedules(int $storageId): void
    {
        ScheduleEntity::listCallback(function (ScheduleEntity $schedule) use ($storageId): void {
            if (($key = array_search($storageId, $schedule->storage_ids)) !== false) {
                $key = (int) $key;
                array_splice($schedule->storage_ids, $key, 1);
                if (count($schedule->storage_ids) === 0) {
                    $schedule->setActive(false);
                }
                $schedule->save();
            }
        });
    }

    /**
     * Render schedule import checkbox in settings import page
     *
     * @return void
     */
    public static function renderImportCheckbox(): void
    {
        ?>
        <td>
            <input
                onclick="DupliJs.Tools.ChangeImportButtonState();DupliJs.Tools.SchedulesImportClicked();"
                type="checkbox"
                name="import-opts[]"
                id="import-schedules" value="schedules"
                class="margin-0">
            <label for="import-schedules">
                <?php esc_html_e("Schedules", 'duplicator-pro'); ?>
            </label>
        </td>
        <script>
            jQuery(document).ready(function($) {
                DupliJs.Tools.SchedulesImportClicked = function() {
                    if (document.getElementById('import-schedules').checked) {
                        document.getElementById('import-templates').checked = true;
                        document.getElementById('import-storages').checked = true;
                        document.getElementById('import-templates').disabled = true;
                        document.getElementById('import-storages').disabled = true;
                    } else {
                        document.getElementById('import-templates').disabled = false;
                        document.getElementById('import-storages').disabled = false;
                    }
                }
            });
        </script>
        <?php
    }

    /**
     * Force storage and template import when schedules are imported,
     * as schedules reference storage and template IDs
     *
     * @param string[] $opts Selected import options
     *
     * @return string[]
     */
    public static function addSettingsImportDependencies(array $opts): array
    {
        if (in_array('schedules', $opts)) {
            $opts = array_unique(array_merge($opts, ['templates', 'storages']));
        }

        return $opts;
    }

    /**
     * Add schedule data to settings export payload
     *
     * @param array<string, mixed> $exportData Current export data
     *
     * @return array<string, mixed>
     */
    public static function addSettingsExportData(array $exportData): array
    {
        $schedules = ScheduleEntity::getAll();
        if ($schedules === false) {
            $schedules = [];
        }

        $exportData['schedules'] = $schedules;

        return $exportData;
    }

    /**
     * Import schedule data from settings import
     *
     * @param array<string, mixed> $data        Imported data
     * @param string               $version     Import version
     * @param array<int, int>      $storageMap  Old-to-new storage ID mapping
     * @param array<int, int>      $templateMap Old-to-new template ID mapping
     * @param string[]             $opts        Selected import options
     *
     * @return void
     */
    public static function importSettingsData(
        array $data,
        string $version,
        array $storageMap,
        array $templateMap,
        array $opts
    ): void {
        if (!in_array('schedules', $opts)) {
            return;
        }

        if (!isset($data['schedules']) || !is_array($data['schedules'])) {
            return;
        }

        foreach ($data['schedules'] as $scheduleData) {
            $schedule = new ScheduleEntity();
            $schedule->settingsImport(
                $scheduleData,
                $version,
                [
                    'storage_map'  => $storageMap,
                    'template_map' => $templateMap,
                ]
            );

            $schedule->save();
        }
    }

    /**
     * Add help context for schedule pages
     *
     * @param string      $context   Current help context
     * @param string|null $pageSlug  Current page slug
     * @param string|null $innerPage Current inner page
     *
     * @return string
     */
    public static function addHelpContext(string $context, ?string $pageSlug, ?string $innerPage): string
    {
        if ($pageSlug !== SchedulePageController::SCHEDULES_SUBMENU_SLUG) {
            return $context;
        }

        if ($innerPage === SchedulePageController::INNER_PAGE_EDIT) {
            return 'schedule_edit';
        }

        return 'schedules';
    }

    /**
     * Add schedule capability labels
     *
     * @param array<string, string> $labels Existing capability labels
     *
     * @return array<string, string>
     */
    public static function addCapabilityLabels(array $labels): array
    {
        $labels[CapSchedule::CAP_SCHEDULE] = __('Manage Schedules', 'duplicator-pro');

        return $labels;
    }

    /**
     * Register schedule capability as disabled on staging sites
     *
     * @param string[] $caps Currently disabled capabilities
     *
     * @return string[]
     */
    public static function addStagingDisabledCaps(array $caps): array
    {
        $caps[] = CapSchedule::CAP_SCHEDULE;
        return $caps;
    }

    /**
     * Add schedule entity class to upgrade entity classes list
     *
     * @param array<int, class-string> $classes Entity class list
     *
     * @return array<int, class-string>
     */
    public static function addUpgradeEntityClasses(array $classes): array
    {
        $classes[] = ScheduleEntity::class;

        return $classes;
    }

    /**
     * Get addon path
     *
     * @return string
     */
    public static function getAddonPath(): string
    {
        return __DIR__;
    }

    /**
     * Get addon file
     *
     * @return string
     */
    public static function getAddonFile(): string
    {
        return __FILE__;
    }
}
