<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\Core;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\ProBase\AbstractLicense;

/**
 * License feature gates for schedule management.
 *
 * These constants represent license-level feature entitlements (which plans include this feature),
 * NOT WordPress capabilities (which users can perform this action).
 */
final class LicenseSchedule
{
    /**
     * License feature: schedule management (create/manage scheduled backups)
     */
    const FEATURE_SCHEDULE = 1013;

    /**
     * License feature: hourly schedule frequency
     */
    const FEATURE_SCHEDULE_HOURLY = 1002;

    /**
     * Return true if schedule management is enabled for current license
     *
     * Includes DRM grace period: even if the base license has expired,
     * schedules remain active until the DRM countdown reaches zero.
     *
     * @return bool
     */
    public static function can(): bool
    {
        if (self::licenseCanFeature(self::FEATURE_SCHEDULE)) {
            return true;
        }

        if (ScheduleDrmHandler::getDaysTillScheduleDRM() >= 0) {
            return true;
        }

        return false;
    }

    /**
     * Return true if hourly schedule frequency is enabled for current license
     *
     * @return bool
     */
    public static function canHourly(): bool
    {
        return self::licenseCanFeature(self::FEATURE_SCHEDULE_HOURLY);
    }

    /**
     * Check if current license tier includes the given feature
     *
     * @param int $feature Feature constant
     *
     * @return bool
     */
    private static function licenseCanFeature(int $feature): bool
    {
        $license = License::getType();

        switch ($feature) {
            case self::FEATURE_SCHEDULE:
                if (is_multisite()) {
                    return in_array(
                        $license,
                        [
                            AbstractLicense::TYPE_PERSONAL,
                            AbstractLicense::TYPE_FREELANCER,
                            AbstractLicense::TYPE_FREELANCER_AUTO,
                            AbstractLicense::TYPE_BUSINESS,
                            AbstractLicense::TYPE_BUSINESS_AUTO,
                            AbstractLicense::TYPE_GOLD,
                            AbstractLicense::TYPE_PRO,
                            AbstractLicense::TYPE_ELITE,
                            AbstractLicense::TYPE_ENTERPRISE,
                        ]
                    );
                } else {
                    return $license > 0;
                }
            case self::FEATURE_SCHEDULE_HOURLY:
                return in_array(
                    $license,
                    [
                        AbstractLicense::TYPE_FREELANCER,
                        AbstractLicense::TYPE_FREELANCER_AUTO,
                        AbstractLicense::TYPE_BUSINESS,
                        AbstractLicense::TYPE_BUSINESS_AUTO,
                        AbstractLicense::TYPE_GOLD,
                        AbstractLicense::TYPE_PLUS,
                        AbstractLicense::TYPE_PRO,
                        AbstractLicense::TYPE_ELITE,
                        AbstractLicense::TYPE_ENTERPRISE,
                    ]
                );
            default:
                return false;
        }
    }

    /**
     * Return upsell URL for schedule upgrade
     *
     * @return string
     */
    public static function getUpsellURL(): string
    {
        return License::getUpsellURL();
    }
}
