<?php

declare(strict_types=1);

namespace Duplicator\Addons\ProBase\Core;

use Duplicator\Core\CapMng;

/**
 * License capability (Pro-only)
 */
class CapLicense
{
    const CAP_LICENSE = CapMng::CAP_PREFIX . 'license';

    /**
     * Register capability filters
     *
     * @return void
     */
    public static function register(): void
    {
        if (!defined('DUPLICATOR_DISABLE_CAP_LICENSE')) {
            define('DUPLICATOR_DISABLE_CAP_LICENSE', false);
        }

        add_filter('duplicator_capabilities_default', [self::class, 'addDefaultCapability']);
        add_filter('duplicator_capabilities_info', [self::class, 'addCapabilityInfo']);
        add_filter('duplicator_capabilities_protected', [self::class, 'addProtectedCapability']);
    }

    /**
     * Add license capability to default capabilities
     *
     * @param array<string, array{roles: string[], users: int[]}> $caps Default capabilities
     *
     * @return array<string, array{roles: string[], users: int[]}>
     */
    public static function addDefaultCapability(array $caps): array
    {
        $caps[self::CAP_LICENSE] = [
            'roles' => CapMng::getDefaultRoles(),
            'users' => [],
        ];

        return $caps;
    }

    /**
     * Add license capability info
     *
     * @param array<string,array{parent:string,label:string,desc:string,disConstName:string}> $capsInfo Capabilities info
     *
     * @return array<string,array{parent:string,label:string,desc:string,disConstName:string}>
     */
    public static function addCapabilityInfo(array $capsInfo): array
    {
        $capsInfo[self::CAP_LICENSE] = [
            'parent'       => CapMng::CAP_SETTINGS,
            'label'        => __('License Settings', 'duplicator-pro'),
            'desc'         => __('The capability to change the license settings.', 'duplicator-pro'),
            'disConstName' => 'DUPLICATOR_DISABLE_CAP_LICENSE',
        ];

        return $capsInfo;
    }

    /**
     * Mark license capability as protected so users with it can't strip it from themselves
     *
     * @param string[] $protected Protected capability keys
     *
     * @return string[]
     */
    public static function addProtectedCapability(array $protected): array
    {
        $protected[] = self::CAP_LICENSE;
        return $protected;
    }
}
