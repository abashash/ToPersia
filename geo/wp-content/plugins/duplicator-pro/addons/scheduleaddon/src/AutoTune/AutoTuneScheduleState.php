<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\AutoTune;

use Duplicator\Models\DynamicGlobalEntity;

/**
 * Persists schedules suspended by AutoTune until they are reactivated.
 */
final class AutoTuneScheduleState
{
    private const DEACTIVATED_SCHEDULES_KEY = 'schedule_autotune_deactivated';

    /**
     * @return array<int, string> Schedule id => name pending reactivation
     */
    public static function getDeactivatedSchedules(): array
    {
        $result = [];
        foreach (DynamicGlobalEntity::getInstance()->getValArray(self::DEACTIVATED_SCHEDULES_KEY) as $id => $name) {
            $result[(int) $id] = (string) $name;
        }

        return $result;
    }

    /**
     * @param array<int, string> $schedules Schedule id => name, empty to clear
     *
     * @return bool True on success
     */
    public static function setDeactivatedSchedules(array $schedules): bool
    {
        return DynamicGlobalEntity::getInstance()->setValArray(self::DEACTIVATED_SCHEDULES_KEY, $schedules, true);
    }

    /**
     * Keep AutoTune runtime state out of settings reset and migration.
     *
     * @param string[] $keys Keys already excluded from the operation
     *
     * @return string[]
     */
    public static function appendRuntimeSkipKey(array $keys): array
    {
        $keys[] = self::DEACTIVATED_SCHEDULES_KEY;
        return array_values(array_unique($keys));
    }

    /**
     * @return string Dynamic global key used by the state
     */
    public static function getStorageKey(): string
    {
        return self::DEACTIVATED_SCHEDULES_KEY;
    }
}
