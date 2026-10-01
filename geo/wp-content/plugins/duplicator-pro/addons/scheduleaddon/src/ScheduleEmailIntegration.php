<?php

/**
 * Schedule email summary integration
 */

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon;

use Duplicator\Addons\ScheduleAddon\Models\ScheduleEntity;
use Duplicator\Core\Views\TplMng;
use Duplicator\Models\Storages\AbstractStorageEntity;

/**
 * Handles email summary schedule tracking and rendering.
 */
class ScheduleEmailIntegration
{
    const OPTION_TRACKED_IDS = 'dupli_opt_email_schedule_ids';

    /**
     * Track a newly created schedule for email summary
     *
     * @param ScheduleEntity $schedule The newly created schedule
     *
     * @return void
     */
    public static function onScheduleCreate(ScheduleEntity $schedule): void
    {
        $trackedIds   = self::getTrackedIds();
        $trackedIds[] = $schedule->getId();
        $trackedIds   = array_unique($trackedIds);

        update_option(self::OPTION_TRACKED_IDS, $trackedIds, false);
    }

    /**
     * Remove a deleted schedule from tracking
     *
     * @param int $scheduleId The ID of the deleted schedule
     *
     * @return void
     */
    public static function onScheduleDelete(int $scheduleId): void
    {
        $trackedIds = self::getTrackedIds();
        $trackedIds = array_values(array_filter(
            $trackedIds,
            fn(int $id): bool => $id !== $scheduleId
        ));

        if (empty($trackedIds)) {
            delete_option(self::OPTION_TRACKED_IDS);
        } else {
            update_option(self::OPTION_TRACKED_IDS, $trackedIds, false);
        }
    }

    /**
     * Get the tracked schedule IDs from the option
     *
     * @return int[]
     */
    private static function getTrackedIds(): array
    {
        $ids = get_option(self::OPTION_TRACKED_IDS, []);
        if (!is_array($ids)) {
            return [];
        }

        return array_map('intval', $ids);
    }

    /**
     * Clear the tracked schedule IDs
     *
     * @return void
     */
    private static function clearTrackedIds(): void
    {
        delete_option(self::OPTION_TRACKED_IDS);
    }

    /**
     * Add 'schedules' to the new entity messages in email summary
     *
     * @param string[] $messages Existing messages
     *
     * @return string[]
     */
    public static function addNewEntityMessage(array $messages): array
    {
        if (!empty(self::getTrackedIds())) {
            $messages[] = __('schedules', 'duplicator-pro');
        }

        return $messages;
    }

    /**
     * Render schedule table in email summary
     *
     * @return void
     */
    public static function renderEmailSummarySection(): void
    {
        $schedules = self::buildSchedulesData();
        // Clear tracked IDs unconditionally: even if no schedules exist (all were deleted),
        // the tracking option must be cleaned up to avoid stale data.
        self::clearTrackedIds();

        if (empty($schedules)) {
            return;
        }

        TplMng::getInstance()->render('scheduleaddon/mail/email_summary_section', ['schedules' => $schedules]);
    }

    /**
     * Build schedule data array for email summary
     *
     * @return array<int, array{id: int, name: string, active: bool, storages: string[], is_new: bool}>
     */
    private static function buildSchedulesData(): array
    {
        $schedules    = [];
        $trackedIds   = self::getTrackedIds();
        $allSchedules = ScheduleEntity::getAll();

        foreach ($allSchedules as $schedule) {
            $storageNames = [];
            foreach ($schedule->storage_ids as $storageId) {
                $storage = AbstractStorageEntity::getById($storageId);
                if ($storage !== false) {
                    $storageNames[] = $storage->getName();
                }
            }

            $schedules[] = [
                'id'       => $schedule->getId(),
                'name'     => $schedule->name,
                'active'   => $schedule->isActive(),
                'storages' => $storageNames,
                'is_new'   => in_array($schedule->getId(), $trackedIds, true),
            ];
        }

        return $schedules;
    }
}
