<?php

/**
 * ProLegacy addon - Unified legacy compatibility system
 */

declare(strict_types=1);

namespace Duplicator\Addons\ProLegacy;

/**
 * ProLegacy Addon Class
 *
 * Provides unified legacy compatibility system:
 * - Hook forwarding: old → new hook names (40 public hooks)
 * - Options & user meta migration during upgrade
 * - Temporary files cleanup (dup_pro_* prefixes)
 * - Upgrade coordination and logging
 *
 * @link https://duplicator.com/
 */
class ProLegacy extends \Duplicator\Core\Addons\AbstractAddonCore
{
    /**
     * Initialize addon
     *
     * @return void
     */
    public function init(): void
    {
        VersionMigration::init();
        BackupDirMigration::init();
        LegacyUpgrade::init();
        LegacyTelemetryUpgrade::init();
        LegacySettingsImport::init();
        HookForwarding::init();
        LegacyFiles::init();
    }

    /**
     * Get addon directory path
     *
     * @return string
     */
    public static function getAddonPath(): string
    {
        return __DIR__;
    }

    /**
     * Get addon main file path
     *
     * @return string
     */
    public static function getAddonFile(): string
    {
        return __FILE__;
    }
}
