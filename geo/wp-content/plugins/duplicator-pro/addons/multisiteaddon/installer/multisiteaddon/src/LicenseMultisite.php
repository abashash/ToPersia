<?php

declare(strict_types=1);

namespace Duplicator\Installer\Addons\MultisiteAddon;

use Duplicator\Installer\Addons\ProBase\AbstractLicense;

/**
 * License feature tier checks for multisite.
 *
 * Single source of truth for multisite feature entitlements.
 * Used by both plugin-side and installer-side code.
 */
final class LicenseMultisite
{
    /**
     * Check if basic multisite is available for the given license type
     *
     * @param int $licenseType AbstractLicense::TYPE_* constant
     *
     * @return bool
     */
    public static function canMultisite(int $licenseType): bool
    {
        return in_array(
            $licenseType,
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
    }

    /**
     * Check if multisite plus is available for the given license type (Business/Gold/Pro/Elite)
     *
     * @param int $licenseType AbstractLicense::TYPE_* constant
     *
     * @return bool
     */
    public static function canMultisitePlus(int $licenseType): bool
    {
        return in_array(
            $licenseType,
            [
                AbstractLicense::TYPE_BUSINESS,
                AbstractLicense::TYPE_BUSINESS_AUTO,
                AbstractLicense::TYPE_GOLD,
                AbstractLicense::TYPE_PRO,
                AbstractLicense::TYPE_ELITE,
                AbstractLicense::TYPE_ENTERPRISE,
            ]
        );
    }
}
