<?php

declare(strict_types=1);

namespace Duplicator\Addons\DropboxAddon\Core;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\ProBase\AbstractLicense;

/**
 * License feature gate for Dropbox storage
 */
final class LicenseDropbox
{
    /**
     * Return true if Dropbox storage is enabled for current license
     *
     * @return bool
     */
    public static function can(): bool
    {
        if (is_multisite()) {
            return in_array(
                License::getType(),
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

        return License::getType() > 0;
    }

    /**
     * Return upsell URL for Dropbox storage upgrade
     *
     * @return string
     */
    public static function getUpsellURL(): string
    {
        return License::getUpsellURL();
    }
}
