<?php

declare(strict_types=1);

namespace Duplicator\Addons\ImportAddon\Core;

use Duplicator\Core\CapMng;

/**
 * Import addon capabilities
 */
class CapImport
{
    const CAP_IMPORT = CapMng::CAP_PREFIX . 'import';

    /**
     * Register capability filters
     *
     * @return void
     */
    public static function register(): void
    {
        if (!defined('DUPLICATOR_DISABLE_CAP_IMPORT')) {
            define('DUPLICATOR_DISABLE_CAP_IMPORT', false);
        }

        add_filter('duplicator_capabilities_default', [self::class, 'addDefaultCapability']);
        add_filter('duplicator_capabilities_info', [self::class, 'addCapabilityInfo']);
    }

    /**
     * Add import capability to default capabilities
     *
     * @param array<string, array{roles: string[], users: int[]}> $caps Default capabilities
     *
     * @return array<string, array{roles: string[], users: int[]}>
     */
    public static function addDefaultCapability(array $caps): array
    {
        $caps[self::CAP_IMPORT] = [
            'roles' => CapMng::getDefaultRoles(),
            'users' => [],
        ];

        return $caps;
    }

    /**
     * Add import capability info
     *
     * @param array<string,array{parent:string,label:string,desc:string,disConstName:string}> $capsInfo Capabilities info
     *
     * @return array<string,array{parent:string,label:string,desc:string,disConstName:string}>
     */
    public static function addCapabilityInfo(array $capsInfo): array
    {
        $capsInfo[self::CAP_IMPORT] = [
            'parent'       => CapMng::CAP_BACKUP_RESTORE,
            'label'        => __('Backup Import', 'duplicator-pro'),
            'desc'         => __(
                'The capability to import a Backup and overwrite the current site.',
                'duplicator-pro'
            ),
            'disConstName' => 'DUPLICATOR_DISABLE_CAP_IMPORT',
        ];

        return $capsInfo;
    }

    /**
     * Add import capability labels
     *
     * @param array<string, string> $labels Existing capability labels
     *
     * @return array<string, string>
     */
    public static function addCapabilityLabels(array $labels): array
    {
        $labels[self::CAP_IMPORT]     = __('Import Backups', 'duplicator-pro');
        $labels['import_chunk_size']  = __('Import chunk size', 'duplicator-pro');
        $labels['import_custom_path'] = __('Import custom path', 'duplicator-pro');

        return $labels;
    }

    /**
     * Register import capability as disabled on staging sites
     *
     * @param string[] $caps Capabilities disabled on staging sites
     *
     * @return string[]
     */
    public static function addStagingDisabledCaps(array $caps): array
    {
        $caps[] = self::CAP_IMPORT;
        return $caps;
    }
}
