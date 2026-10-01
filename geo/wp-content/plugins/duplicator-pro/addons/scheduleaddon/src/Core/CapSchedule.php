<?php

declare(strict_types=1);

namespace Duplicator\Addons\ScheduleAddon\Core;

use Duplicator\Core\CapMng;

/**
 * Schedule addon capabilities
 */
class CapSchedule
{
    const CAP_SCHEDULE = CapMng::CAP_PREFIX . 'schedule';

    /**
     * Register capability filters
     *
     * @return void
     */
    public static function register(): void
    {
        if (!defined('DUPLICATOR_DISABLE_CAP_SCHEDULE')) {
            define('DUPLICATOR_DISABLE_CAP_SCHEDULE', false);
        }

        add_filter('duplicator_capabilities_default', [self::class, 'addDefaultCapability']);
        add_filter('duplicator_capabilities_info', [self::class, 'addCapabilityInfo']);
    }

    /**
     * Add schedule capability to default capabilities
     *
     * @param array<string, array{roles: string[], users: int[]}> $caps Default capabilities
     *
     * @return array<string, array{roles: string[], users: int[]}>
     */
    public static function addDefaultCapability(array $caps): array
    {
        $caps[self::CAP_SCHEDULE] = [
            'roles' => CapMng::getDefaultRoles(),
            'users' => [],
        ];

        return $caps;
    }

    /**
     * Add schedule capability info
     *
     * @param array<string,array{parent:string,label:string,desc:string,disConstName:string}> $capsInfo Capabilities info
     *
     * @return array<string,array{parent:string,label:string,desc:string,disConstName:string}>
     */
    public static function addCapabilityInfo(array $capsInfo): array
    {
        $capsInfo[self::CAP_SCHEDULE] = [
            'parent'       => CapMng::CAP_CREATE,
            'label'        => __('Manage Schedules', 'duplicator-pro'),
            'desc'         => __('The capability to manage Backup Schedules.', 'duplicator-pro'),
            'disConstName' => 'DUPLICATOR_DISABLE_CAP_SCHEDULE',
        ];

        return $capsInfo;
    }
}
