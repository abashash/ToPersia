<?php

/**
 * Schedule Addon installer-side class
 */

namespace Duplicator\Installer\Addons\ScheduleAddon;

use Duplicator\Installer\Core\Addons\InstAbstractAddonCore;
use Duplicator\Installer\Core\InstState;
use Duplicator\Installer\Core\Params\PrmMng;
use Duplicator\Installer\Utils\Log\Log;
use DUPX_ArchiveConfig;
use DUPX_DB;
use DUPX_DB_Functions;

/**
 * Installer-side schedule addon
 *
 * Handles cleanup of the schedule_packages relationship table
 * during restoration.
 */
class ScheduleAddon extends InstAbstractAddonCore
{
    const TABLE_SUFFIX = 'duplicator_addon_schedule_packages';

    /**
     * Initialize the installer-side addon
     *
     * @return void
     */
    public function init(): void
    {
        add_action(
            'duplicator_installer_after_cleanup_packages',
            [
                self::class,
                'cleanupSchedulePackages',
            ]
        );
    }

    /**
     * Clean up the schedule_packages table after package cleanup
     *
     * In fresh install mode: empty the entire table.
     * In restore backup mode: delete only the current package's relation.
     *
     * @return void
     */
    public static function cleanupSchedulePackages(): void
    {
        $dbh   = DUPX_DB_Functions::getInstance()->dbConnection();
        $table = mysqli_real_escape_string($dbh, self::getTableName());

        if (!DUPX_DB::tableExists($dbh, $table)) {
            return;
        }

        if (InstState::isRestoreBackup()) {
            $packageId = DUPX_ArchiveConfig::getInstance()->packInfo->packageId;
            DUPX_DB::mysqli_query($dbh, 'DELETE FROM `' . $table . '` WHERE `package_id` = ' . (int) $packageId);
            Log::info(sprintf('SCHEDULE PACKAGES RELATION DELETED FOR PACKAGE ID %d', $packageId));
        } else {
            $count = DUPX_DB::chunksDelete($dbh, $table, '1 = 1');
            Log::info(sprintf('SCHEDULE PACKAGES RELATIONS DELETED [ROWS:%6d]', $count));
        }
    }

    /**
     * Get the schedule_packages table name with prefix
     *
     * @return string
     */
    private static function getTableName(): string
    {
        $prefix = PrmMng::getInstance()->getValue(PrmMng::PARAM_DB_TABLE_PREFIX);
        return $prefix . self::TABLE_SUFFIX;
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
