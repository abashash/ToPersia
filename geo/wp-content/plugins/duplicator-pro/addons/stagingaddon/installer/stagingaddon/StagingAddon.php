<?php

/**
 * Staging Installer addon class
 */

namespace Duplicator\Installer\Addons\StagingAddon;

use Duplicator\Installer\Core\Addons\InstAbstractAddonCore;
use Duplicator\Installer\Core\Hooks\HooksMng;

/**
 * Staging Installer addon class
 */
class StagingAddon extends InstAbstractAddonCore
{
    /**
     * Main init addon
     *
     * @return void
     */
    public function init(): void
    {
        HooksMng::getInstance()->addFilter(
            'duplicator_installer_migrate_data_extra',
            [
                self::class,
                'addStagingMigrateData',
            ],
            10,
            2
        );
    }

    /**
     * Add staging data to migrate data extra
     *
     * @param array<string, mixed> $extra         Extra data array
     * @param array<string, mixed> $overwriteData Overwrite site data from params
     *
     * @return array<string, mixed>
     */
    public static function addStagingMigrateData(array $extra, array $overwriteData): array
    {
        $extra['staging'] = [
            'enabled'     => !empty($overwriteData['stagingMode']),
            'mainSiteUrl' => $overwriteData['mainSiteUrl'] ?? '',
            'pageUrl'     => $overwriteData['stagingPageUrl'] ?? '',
            'identifier'  => $overwriteData['stagingIdentifier'] ?? '',
            'colorScheme' => $overwriteData['colorScheme'] ?? 'fresh',
            'title'       => $overwriteData['stagingTitle'] ?? '',
        ];

        return $extra;
    }

    /**
     * Get addon main folder
     *
     * @return string
     */
    public static function getAddonPath(): string
    {
        return __DIR__;
    }

    /**
     * Get addon main file
     *
     * @return string
     */
    public static function getAddonFile(): string
    {
        return __FILE__;
    }
}
