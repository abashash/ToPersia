<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon;

use Duplicator\Addons\ScheduleAddon\Core\LicenseSchedule;
use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Package\AbstractPackage;
use Duplicator\Package\Create\BuildComponents;
use Throwable;
use VendorDuplicator\Amk\JsonSerialize\JsonSerialize;

/**
 * Tracks schedule-specific usage statistics.
 *
 * Persisted in its own WP option. Hooks into build events to track scheduled
 * builds, and into duplicator_telemetry_snapshot to output the data.
 */
class ScheduleUsageStats
{
    const OPTION_KEY = 'dupli_opt_schedule_usage_stats';

    /** @var ?self */
    private static $instance = null;

    /** @var int */
    private $buildCount = 0;
    /** @var int */
    private $buildLastDate = 0;
    /** @var int */
    private $buildFailedCount = 0;
    /** @var int */
    private $buildFailedLastDate = 0;
    /** @var int */
    private $buildCompFullCount = 0;
    /** @var int */
    private $buildCompDbOnlyCount = 0;
    /** @var int */
    private $buildCompMdOnlyCount = 0;
    /** @var int */
    private $buildCompCustomCount = 0;
    /** @var int */
    private $buildCompCustomOnlyActiveCount = 0;

    /**
     * Class constructor
     */
    private function __construct()
    {
        if (($data = get_option(self::OPTION_KEY)) !== false) {
            JsonSerialize::unserializeToObj($data, $this);
        }
    }

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register hooks
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('duplicator_telemetry_package_build', [self::class, 'onPackageBuild']);
        add_action('duplicator_build_fail', [self::class, 'onBuildFail'], 10, 3);
        add_filter('duplicator_telemetry_snapshot', [self::class, 'addTelemetryData']);
    }

    /**
     * `duplicator_build_fail` listener: record the failed scheduled build unless it was cancelled.
     *
     * @param AbstractPackage $package        Failed Backup
     * @param int             $previousStatus Status the build was in when it failed
     * @param Throwable       $exception      Failure cause
     *
     * @return void
     */
    public static function onBuildFail(AbstractPackage $package, int $previousStatus, Throwable $exception): void
    {
        if ($previousStatus >= AbstractPackage::STATUS_COPIEDPACKAGE) {
            return;
        }
        self::onPackageBuild($package, $previousStatus);
    }

    /**
     * Track build stats for scheduled packages
     *
     * Consumes core's successful-build notification and pre-transfer failures.
     * Only tracks packages whose execution type is schedule-driven; a
     * cancelled build is neither a build nor a failure.
     *
     * @param AbstractPackage $package        The package
     * @param ?int            $previousStatus Status before the failure from `duplicator_build_fail`, null on completion
     *
     * @return void
     */
    public static function onPackageBuild(AbstractPackage $package, ?int $previousStatus = null): void
    {
        $execType = $package->getExecutionType();
        if (
            $execType !== ScheduleEntity::EXECUTION_TYPE_SCHEDULED &&
            $execType !== ScheduleEntity::EXECUTION_TYPE_RUN_NOW
        ) {
            return;
        }

        if (AbstractPackage::isCancellationStatus($previousStatus ?? $package->getStatus())) {
            return;
        }

        $instance = self::getInstance();

        if (in_array($package->getStatus(), [AbstractPackage::STATUS_COPIEDPACKAGE, AbstractPackage::STATUS_COMPLETE], true)) {
            $instance->buildCount++;
            $instance->buildLastDate = time();

            switch (BuildComponents::getActionFromComponents($package->components)) {
                case BuildComponents::COMP_ACTION_ALL:
                    $instance->buildCompFullCount++;
                    break;
                case BuildComponents::COMP_ACTION_DB:
                    $instance->buildCompDbOnlyCount++;
                    break;
                case 'media':
                    $instance->buildCompMdOnlyCount++;
                    break;
                case 'custom':
                    if (
                        array_intersect(
                            $package->components,
                            [
                                BuildComponents::COMP_PLUGINS_ACTIVE,
                                BuildComponents::COMP_THEMES_ACTIVE,
                            ]
                        )
                    ) {
                        $instance->buildCompCustomOnlyActiveCount++;
                    } else {
                        $instance->buildCompCustomCount++;
                    }
                    break;
            }
        } else {
            $instance->buildFailedCount++;
            $instance->buildFailedLastDate = time();
        }

        $instance->save();
    }

    /**
     * Add schedule history and configuration to telemetry.
     *
     * @param array<string, mixed> $data Current snapshot
     *
     * @return array<string, mixed>
     */
    public static function addTelemetryData(array $data): array
    {
        $instance = self::getInstance();

        $data['schedules_build_count']                         = $instance->buildCount;
        $data['schedules_build_last_date']                     = $instance->buildLastDate == 0
            ? null
            : date('Y-m-d H:i:s', $instance->buildLastDate);
        $data['schedules_build_failed_count']                  = $instance->buildFailedCount;
        $data['schedules_build_failed_last_date']              = $instance->buildFailedLastDate == 0
            ? null
            : date('Y-m-d H:i:s', $instance->buildFailedLastDate);
        $data['schedules_build_comp_full_count']               = $instance->buildCompFullCount;
        $data['schedules_build_comp_dbonly_count']             = $instance->buildCompDbOnlyCount;
        $data['schedules_build_comp_mdonly_count']             = $instance->buildCompMdOnlyCount;
        $data['schedules_build_comp_custom_count']             = $instance->buildCompCustomCount;
        $data['schedules_build_comp_custom_only_active_count'] = $instance->buildCompCustomOnlyActiveCount;

        return array_merge($data, self::getScheduleConfigInfos());
    }

    /**
     * Return per-schedule configuration stats (frequency and enabled/disabled breakdown)
     *
     * @return array<string, mixed>
     */
    private static function getScheduleConfigInfos(): array
    {
        $result = [
            'can_use_adv_schedules'    => LicenseSchedule::canHourly(),
            'schedules_hourly_count'   => 0,
            'schedules_daily_count'    => 0,
            'schedules_weekly_count'   => 0,
            'schedules_monthly_count'  => 0,
            'schedules_disabled_count' => 0,
            'schedules_enabled_count'  => 0,
        ];

        if (($schedules = ScheduleEntity::getAll()) === false) {
            $schedules = [];
        }

        foreach ($schedules as $schedule) {
            if (!$schedule->isActive()) {
                $result['schedules_disabled_count']++;
                continue;
            }

            $result['schedules_enabled_count']++;
            switch ($schedule->repeat_type) {
                case ScheduleEntity::REPEAT_HOURLY:
                    $result['schedules_hourly_count']++;
                    break;
                case ScheduleEntity::REPEAT_DAILY:
                    $result['schedules_daily_count']++;
                    break;
                case ScheduleEntity::REPEAT_MONTHLY:
                    $result['schedules_monthly_count']++;
                    break;
                case ScheduleEntity::REPEAT_WEEKLY:
                    $result['schedules_weekly_count']++;
                    break;
            }
        }

        return $result;
    }

    /**
     * Save to database
     *
     * @return bool
     */
    private function save(): bool
    {
        return update_option(self::OPTION_KEY, JsonSerialize::serialize($this, JSON_PRETTY_PRINT));
    }
}
