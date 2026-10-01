<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\Core;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Addons\ProBase\Models\LicenseData;

/**
 * Handles DRM grace period for scheduled backups.
 *
 * After license expiration, schedules remain active for a grace period
 * (SCHEDULE_DRM_DELAY_DAYS) before being disabled.
 */
class ScheduleDrmHandler
{
    const SCHEDULE_DRM_DELAY_DAYS = 14;

    /**
     * Check if the license type qualifies for schedule DRM delay
     *
     * @return bool True if license type allows schedule DRM delay
     */
    protected static function haveLicenseScheduleDelayToDRM(): bool
    {
        $licenseType = LicenseData::getInstance()->getLicenseType();
        if (is_multisite()) {
            $noScheduleDRMTypes = [
                License::TYPE_UNKNOWN,
                License::TYPE_UNLICENSED,
                License::TYPE_BASIC,
                License::TYPE_PLUS,
            ];
        } else {
            $noScheduleDRMTypes = [
                License::TYPE_UNKNOWN,
                License::TYPE_UNLICENSED,
            ];
        }
        return !in_array($licenseType, $noScheduleDRMTypes);
    }

    /**
     * Get the number of days until the schedule DRM activation
     *
     * @return int|false Days left until DRM activation, or -1 if no grace period applies
     */
    public static function getDaysTillScheduleDRM()
    {
        if (!apply_filters('duplicator_has_active_automated_tasks', false)) {
            return -1;
        }
        $status = LicenseData::getInstance()->getStatus();
        if (!in_array($status, [LicenseData::STATUS_VALID, LicenseData::STATUS_EXPIRED])) {
            return -1;
        }
        if (!self::haveLicenseScheduleDelayToDRM()) {
            return -1;
        }
        if (($expiresDays = LicenseData::getInstance()->getExpirationDays()) === false) {
            return -1;
        }
        return self::SCHEDULE_DRM_DELAY_DAYS + $expiresDays;
    }
}
