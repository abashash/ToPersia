<?php

declare(strict_types=1);

namespace Duplicator\Addons\BrandAddon\Core;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\ProBase\AbstractLicense;

/**
 * License feature gate for brand management
 */
final class LicenseBrand
{
    /**
     * Return true if branding is enabled for current license
     *
     * @return bool
     */
    public static function can(): bool
    {
        return in_array(
            License::getType(),
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
    }

    /**
     * Return upsell URL for brand upgrade
     *
     * @return string
     */
    public static function getUpsellURL(): string
    {
        return License::getUpsellURL();
    }
}
