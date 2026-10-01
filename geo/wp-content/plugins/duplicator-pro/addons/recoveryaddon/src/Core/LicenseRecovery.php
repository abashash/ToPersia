<?php

declare(strict_types=1);

namespace Duplicator\Addons\RecoveryAddon\Core;

use Duplicator\Addons\ProBase\License\License;

/**
 * License feature gate for recovery point management.
 *
 * Recovery is available on any paid license tier.
 */
final class LicenseRecovery
{
    /**
     * Return true if recovery is enabled for current license
     *
     * @return bool
     */
    public static function can(): bool
    {
        return License::getType() > 0;
    }

    /**
     * Return upsell URL for recovery upgrade
     *
     * @return string
     */
    public static function getUpsellURL(): string
    {
        return License::getUpsellURL();
    }
}
