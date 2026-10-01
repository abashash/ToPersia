<?php

/**
 * Backup directory migration from backups-dup-pro to duplicator-backups
 */

declare(strict_types=1);

namespace Duplicator\Addons\ProLegacy;

use Duplicator\Libs\Snap\SnapIO;
use Duplicator\Utils\Logging\DupLog;
use Throwable;

/**
 * Handles migration of backup directory from old to new name
 *
 * Must run BEFORE initEntities (priority 50) which calls initStorageDirectory()
 * that creates the backup directory if it doesn't exist.
 *
 * Race condition handling: During upgrade, DupLog::trace() calls from earlier
 * priority hooks (e.g., migrateOptionPrefixes at priority 12) may initialize
 * TraceLogMng which creates the new directory structure prematurely. This class
 * handles that case by moving any contents from the new directory back to the
 * old one before performing the rename.
 */
class BackupDirMigration
{
    const OLD_DIR_NAME = 'backups-dup-pro';
    const NEW_DIR_NAME = 'duplicator-backups';

    /**
     * Initialize migration hook
     *
     * Priority 45: Before initEntities (50) which calls
     * StoragesUtil::initDefaultStorage() -> initStorageDirectory()
     *
     * @return void
     */
    public static function init(): void
    {
        add_action('duplicator_upgrade', [self::class, 'migrate'], 45, 4);
    }

    /**
     * Migrate backup directory from old name to new name
     *
     * If the new directory already exists (created prematurely by trace logging),
     * copies its contents to the old directory first, then performs the rename.
     *
     * @param string       $oldVariant previous variant identifier, '' on fresh install
     * @param false|string $oldVersion previous Duplicator version, false on fresh install
     * @param string       $newVariant current variant identifier
     * @param string       $newVersion current Duplicator version
     *
     * @return void
     */
    public static function migrate($oldVariant, $oldVersion, $newVariant, $newVersion): void
    {
        // Skip for new installations
        if ($oldVersion === false) {
            return;
        }

        try {
            $contentDir = untrailingslashit(wp_normalize_path((string) realpath(WP_CONTENT_DIR)));
            $oldPath    = $contentDir . '/' . self::OLD_DIR_NAME;
            $newPath    = $contentDir . '/' . self::NEW_DIR_NAME;

            if (!is_dir($oldPath)) {
                return;
            }

            if (is_dir($newPath)) {
                // If new directory exists, copy its contents into old directory first
                if (!SnapIO::rcopy($newPath, $oldPath)) {
                    DupLog::trace("LEGACY MIGRATION: Failed to copy contents from new directory to old: " . $newPath);
                    return;
                }
                if (!SnapIO::rrmdir($newPath)) {
                    DupLog::trace("LEGACY MIGRATION: Failed to remove new directory after merge: " . $newPath);
                    return;
                }
            }

            if (SnapIO::rename($oldPath, $newPath)) {
                DupLog::trace("LEGACY MIGRATION: Renamed backup directory from " . self::OLD_DIR_NAME . " to " . self::NEW_DIR_NAME);
            } else {
                DupLog::trace("LEGACY MIGRATION: Failed to rename backup directory");
            }
        } catch (Throwable $e) {
            DupLog::trace("LEGACY MIGRATION: Error migrating backup directory: " . $e->getMessage());
        }
    }
}
