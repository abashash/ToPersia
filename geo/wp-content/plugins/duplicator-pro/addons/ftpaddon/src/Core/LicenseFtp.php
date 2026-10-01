<?php

declare(strict_types=1);

namespace Duplicator\Addons\FtpAddon\Core;

use Duplicator\Addons\ProBase\License\License;
use Duplicator\Installer\Addons\ProBase\AbstractLicense;

/**
 * License feature gate for FTP/SFTP storage
 */
final class LicenseFtp
{
    /**
     * Return true if FTP/SFTP storage is enabled for current license
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
     * Return upsell URL for FTP/SFTP storage upgrade
     *
     * @return string
     */
    public static function getUpsellURL(): string
    {
        return License::getUpsellURL();
    }
}
