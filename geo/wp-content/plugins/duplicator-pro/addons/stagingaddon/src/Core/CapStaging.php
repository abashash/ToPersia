<?php

declare(strict_types=1);

namespace Duplicator\Addons\StagingAddon\Core;

use Duplicator\Core\CapMng;

/**
 * Staging addon capabilities
 */
class CapStaging
{
    const CAP_STAGING = CapMng::CAP_PREFIX . 'staging';

    /**
     * Register capability filters
     *
     * @return void
     */
    public static function register(): void
    {
        if (!defined('DUPLICATOR_DISABLE_CAP_STAGING')) {
            define('DUPLICATOR_DISABLE_CAP_STAGING', false);
        }

        add_filter('duplicator_capabilities_default', [self::class, 'addDefaultCapability']);
        add_filter('duplicator_capabilities_info', [self::class, 'addCapabilityInfo']);
    }

    /**
     * Add staging capability to default capabilities
     *
     * @param array<string, array{roles: string[], users: int[]}> $caps Default capabilities
     *
     * @return array<string, array{roles: string[], users: int[]}>
     */
    public static function addDefaultCapability(array $caps): array
    {
        $caps[self::CAP_STAGING] = [
            'roles' => CapMng::getDefaultRoles(),
            'users' => [],
        ];

        return $caps;
    }

    /**
     * Add staging capability info
     *
     * @param array<string,array{parent:string,label:string,desc:string,disConstName:string}> $capsInfo Capabilities info
     *
     * @return array<string,array{parent:string,label:string,desc:string,disConstName:string}>
     */
    public static function addCapabilityInfo(array $capsInfo): array
    {
        $capsInfo[self::CAP_STAGING] = [
            'parent'       => CapMng::CAP_BASIC,
            'label'        => __('Staging Sites', 'duplicator-pro'),
            'desc'         => __('The capability to create and manage staging sites.', 'duplicator-pro'),
            'disConstName' => 'DUPLICATOR_DISABLE_CAP_STAGING',
        ];

        return $capsInfo;
    }
}
