<?php

declare(strict_types=1);

namespace Duplicator\Addons\StagingAddon\Core;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\ProBase\AbstractLicense;

/**
 * Staging license capability handler
 */
final class LicenseStaging
{
    /**
     * Return true if staging capability is enabled for current license
     *
     * @return bool
     */
    public static function can(): bool
    {
        $license = License::getType();

        return in_array(
            $license,
            [
                AbstractLicense::TYPE_PRO,
                AbstractLicense::TYPE_ELITE,
                AbstractLicense::TYPE_ENTERPRISE,
            ]
        );
    }

    /**
     * Return upsell URL for staging upgrade
     *
     * @return string
     */
    public static function getUpsellURL(): string
    {
        return License::getUpsellURL();
    }
}
