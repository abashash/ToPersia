<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon;

use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;

/**
 * Handles schedule processing for the ScheduleAddon.
 *
 * Hooks into the core runner's pre-process action to evaluate and
 * trigger active schedules. Also provides static state so that
 * NameFormat can resolve %schedulename% during package construction.
 */
class ScheduleProcessor
{
    /** @var ?ScheduleEntity The schedule currently being processed */
    private static $currentSchedule = null;

    /**
     * Process active schedules.
     *
     * Hooked to `duplicator_runner_pre_process`.
     * Calculates the earliest run time among active schedules and,
     * if that time has passed, processes every active schedule.
     *
     * @return void
     */
    public static function processSchedules(): void
    {
        $schedules     = self::getActiveSchedules();
        $next_run_time = self::calculateEarliestScheduleRunTime($schedules);

        if ($next_run_time !== -1 && $next_run_time <= time()) {
            foreach ($schedules as $schedule) {
                $schedule->process();
            }
        }
    }

    /**
     * Get active schedules.
     *
     * Applies the `duplicator_get_active_schedules` filter so that
     * other code can modify the list.
     *
     * @return ScheduleEntity[]
     */
    public static function getActiveSchedules(): array
    {
        return apply_filters('duplicator_get_active_schedules', ScheduleEntity::getActive());
    }

    /**
     * Calculate the earliest next-run time across all active schedules.
     *
     * If a schedule's next_run_time is -1, its runtime is recalculated
     * before comparison. Returns -1 when no active schedule has a valid
     * next-run time.
     *
     * @param ?ScheduleEntity[] $schedules Pre-loaded schedules, or null to load from DB
     *
     * @return int Earliest timestamp, or -1 if none
     */
    public static function calculateEarliestScheduleRunTime(?array $schedules = null): int
    {
        $next_run_time = PHP_INT_MAX;

        foreach ($schedules ?? self::getActiveSchedules() as $schedule) {
            if ($schedule->next_run_time == -1) {
                $schedule->updateNextRuntime();
            }

            if ($schedule->next_run_time !== -1 && $schedule->next_run_time < $next_run_time) {
                $next_run_time = $schedule->next_run_time;
            }
        }

        if ($next_run_time == PHP_INT_MAX) {
            $next_run_time = -1;
        }

        return $next_run_time;
    }

    /**
     * Set the schedule currently being processed.
     *
     * Used during package creation so that NameFormat can resolve the
     * %schedulename% placeholder.
     *
     * @param ?ScheduleEntity $schedule The current schedule, or null
     *
     * @return void
     */
    public static function setCurrentSchedule(?ScheduleEntity $schedule): void
    {
        self::$currentSchedule = $schedule;
    }

    /**
     * Get the schedule currently being processed.
     *
     * @return ?ScheduleEntity
     */
    public static function getCurrentSchedule(): ?ScheduleEntity
    {
        return self::$currentSchedule;
    }

    /**
     * Clear the current schedule reference.
     *
     * @return void
     */
    public static function clearCurrentSchedule(): void
    {
        self::$currentSchedule = null;
    }
}
