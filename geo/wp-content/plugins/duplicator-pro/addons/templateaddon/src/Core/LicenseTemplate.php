<?php

declare(strict_types=1);

namespace Duplicator\Addons\TemplateAddon\Core;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\ProBase\AbstractLicense;

/**
 * License feature gate for template management.
 *
 * This constant represents a license-level feature entitlement (which plans include this feature),
 * NOT a WordPress capability (which users can perform this action).
 */
final class LicenseTemplate
{
    /**
     * License feature: template management (create/edit/delete backup configuration presets)
     */
    const FEATURE_TEMPLATE = 1015;

    /**
     * Return true if template management is enabled for current license
     *
     * @return bool
     */
    public static function can(): bool
    {
        return self::licenseCanFeature(self::FEATURE_TEMPLATE);
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
            case self::FEATURE_TEMPLATE:
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
            default:
                return false;
        }
    }

    /**
     * Return upsell URL for template upgrade
     *
     * @return string
     */
    public static function getUpsellURL(): string
    {
        return License::getUpsellURL();
    }
}
