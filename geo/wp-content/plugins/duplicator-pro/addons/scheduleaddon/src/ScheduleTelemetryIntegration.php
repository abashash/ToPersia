<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon;

use Duplicator\Utils\UsageStatistics\Telemetry\TelemetryEvents;
use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Models\GlobalEntity;
use Duplicator\Utils\UsageStatistics\StatsUtil;

/**
 * Bridges ScheduleAddon into the core telemetry pipeline.
 *
 * Emits schedule-owned telemetry events; backup_build events carry the
 * execution type from the package execution info directly.
 */
class ScheduleTelemetryIntegration
{
    /**
     * Register filter hooks. Called from ScheduleAddon::init().
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('duplicator_after_schedule_create', [self::class, 'onScheduleCreated'], 100, 1);
    }

    /**
     * Emit `schedule_created` when a new schedule entity is persisted. The
     * event is owned by ScheduleAddon (not probase) because the schedule
     * concept is addon-scoped — core has no compile-time view of it.
     *
     * @param ScheduleEntity $schedule Newly created schedule
     *
     * @return void
     */
    public static function onScheduleCreated(ScheduleEntity $schedule): void
    {
        $frequency  = self::resolveFrequency($schedule->repeat_type);
        $template   = $schedule->getTemplate();
        $components = ($template !== false) ? $template->components : [];

        TelemetryEvents::track(
            'schedule',
            'created',
            [
                'frequency'      => $frequency,
                'components'     => StatsUtil::getStatsComponents($components),
                'archive_engine' => self::resolveArchiveEngine(),
                'storage_count'  => count($schedule->storage_ids),
            ],
            $schedule
        );
    }

    /**
     * Map the integer `repeat_type` to the stable English label expected by
     * the worker. Localized labels would break worker-side `GROUP BY` so we
     * never call `getRepeatText()`.
     *
     * @param int $repeatType ScheduleEntity::REPEAT_* enum
     *
     * @return string
     */
    private static function resolveFrequency(int $repeatType): string
    {
        switch ($repeatType) {
            case ScheduleEntity::REPEAT_HOURLY:
                return 'hourly';
            case ScheduleEntity::REPEAT_DAILY:
                return 'daily';
            case ScheduleEntity::REPEAT_WEEKLY:
                return 'weekly';
            case ScheduleEntity::REPEAT_MONTHLY:
                return 'monthly';
            default:
                return 'unknown';
        }
    }

    /**
     * Resolve the archive engine from the global setting — schedules don't
     * carry their own `archive_build_mode` / `ziparchive_mode`; they inherit
     * both at run time, so the global is the value the build will use.
     *
     * @return string
     */
    private static function resolveArchiveEngine(): string
    {
        $global = GlobalEntity::getInstance();
        return TelemetryEvents::archiveEngineName($global->getBuildMode(), $global->getZipArchiveMode());
    }
}
