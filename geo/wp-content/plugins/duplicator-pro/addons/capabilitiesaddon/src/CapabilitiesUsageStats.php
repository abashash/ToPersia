<?php

declare(strict_types=1);

namespace Duplicator\Addons\CapabilitiesAddon;

use Duplicator\Addons\CapabilitiesAddon\Core\LicenseCapabilities;
use Duplicator\Core\CapMng;

/**
 * Capabilities addon usage statistics contribution
 */
class CapabilitiesUsageStats
{
    /**
     * Register hooks
     *
     * @return void
     */
    public static function register(): void
    {
        add_filter('duplicator_telemetry_snapshot', [self::class, 'addTelemetryData']);
    }

    /**
     * Add capability adoption fields to telemetry.
     *
     * @param array<string, mixed> $data Current snapshot
     *
     * @return array<string, mixed>
     */
    public static function addTelemetryData(array $data): array
    {
        $data['can_use_adv_permissions']           = LicenseCapabilities::canPlus();
        $data['have_custom_adv_permissions']       = !CapMng::getInstance()->isDefault();
        $data['have_custom_users_adv_permissions'] = CapMng::getInstance()->hasUsersCapabilities();

        return $data;
    }
}
