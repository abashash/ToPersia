<?php

declare(strict_types=1);

namespace Duplicator\Addons\CapabilitiesAddon\Core;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\ProBase\AbstractLicense;

/**
 * License feature gates for capabilities management.
 *
 * These constants represent license-level feature entitlements (which plans include this feature),
 * NOT WordPress capabilities (which users can perform this action).
 */
final class LicenseCapabilities
{
    /**
     * License feature: basic capabilities management (custom role assignments)
     */
    const FEATURE_CAPABILITIES_MNG = 1008;

    /**
     * License feature: advanced capabilities management (per-user assignments)
     */
    const FEATURE_CAPABILITIES_MNG_PLUS = 1009;

    /**
     * Return true if capabilities management is enabled for current license
     *
     * @return bool
     */
    public static function can(): bool
    {
        return self::licenseCanFeature(self::FEATURE_CAPABILITIES_MNG);
    }

    /**
     * Return true if advanced (per-user) capabilities management is enabled for current license
     *
     * @return bool
     */
    public static function canPlus(): bool
    {
        return self::licenseCanFeature(self::FEATURE_CAPABILITIES_MNG_PLUS);
    }

    /**
     * Check if current license tier includes the given feature
     *
     * Currently both features resolve to the same tier list. They are kept separate
     * so that per-user assignments (PLUS) can be restricted to higher tiers in the future
     * without changing the public API.
     *
     * @param int $feature Feature constant
     *
     * @return bool
     */
    private static function licenseCanFeature(int $feature): bool
    {
        $license = License::getType();

        switch ($feature) {
            case self::FEATURE_CAPABILITIES_MNG:
            case self::FEATURE_CAPABILITIES_MNG_PLUS:
                return in_array(
                    $license,
                    [
                        AbstractLicense::TYPE_BUSINESS,
                        AbstractLicense::TYPE_BUSINESS_AUTO,
                        AbstractLicense::TYPE_GOLD,
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
     * Return upsell URL for capabilities upgrade
     *
     * @return string
     */
    public static function getUpsellURL(): string
    {
        return License::getUpsellURL();
    }
}
